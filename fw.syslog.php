<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.modsectools.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
include_once("ressources/class.resolv.conf.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["form"])){tabs();exit;}
if(isset($_GET["form-search"])){form_search();exit;}
if(isset($_GET["top"])){top();exit;}
if(isset($_GET["banned"])){banned();exit;}
if(isset($_GET["unban-js"])){unban_js();exit;}
if(isset($_GET["zoom-js"])){zoomjs();exit;}
if(isset($_GET["zoom-popup"])){zoom_popup();exit;}
page();



function zoomjs(){
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $ip     = $_GET["zoom-js"];
    $ipenc  = urlencode($ip);
    $tpl->js_dialog($ip,"$page?zoom-popup=$ipenc");

}

function zoom_popup(){
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $ip     = $_GET["zoom-popup"];
    $ipenc  = urlencode($ip);
    $resolv = new resolv_conf(true);
    $hostname = $resolv->gethostbyaddr($ip);
    if($resolv->mysql_error<>null){
        $html[]=$tpl->div_error($resolv->mysql_error);
    }

    // Pentest 2026-11-04 : nom inverse (PTR choisi par le proprietaire de l'IP : donnee externe) affiche sans encodage (XSS stockee).
    $html[]="<H1>".req_html($ip)."</H1>";
    $html[]="<H2>".req_html($hostname)."</H2>";

    echo $tpl->_ENGINE_parse_body($html);

}

function page(): bool{
    $page=CurrentPageName();
    $tpl=new template_admin();


    $html[]= $tpl->page_header("{firewall_events}: {syslog}","fas fa-eye","{firewall_events_explain}",
        "$page?form=yes","firewall-events","progress-fw-events",false,"table-fw-syslog");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
// Deux onglets : le journal (recherche) et le classement « Top ».
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{events}"]="$page?form-search=yes";
    $array["{fw_events_top}"]="$page?top=24";
    $array["{fw_banned}"]="$page?banned=yes";
    echo $tpl->tabs_default($array);
}

// Onglet « Bannis » : sources arrêtées par le blocage automatique (GET
// /firewall/autoban), avec déblocage (DELETE /firewall/autoban/{ip}).
function banned(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/autoban"),true);
    $html=array();
    $html[]="<div id='fw-banned' style='margin-top:10px'>";
    if(!is_array($json) || empty($json["Status"])){
        $html[]=$tpl->div_error("{fw_api_no_answer}");
        $html[]="</div>";
        echo $tpl->_ENGINE_parse_body(implode("\n",$html));
        return;
    }
    $rows=is_array($json["Data"] ?? null)?$json["Data"]:array();
    $html[]="<table class='table table-striped'><thead><tr><th>{ipaddr}</th><th>{country}</th><th>{hostname}</th><th>{fw_ban_remaining}</th><th>&nbsp;</th></tr></thead><tbody>";
    if(count($rows)==0){$html[]="<tr><td colspan='5'>{no_data}</td></tr>";}
    foreach($rows as $r){
        $ip=(string)($r["ip"] ?? "");
        if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)===false){continue;}
        $rem=intval($r["remaining"] ?? 0);
        $remTxt=$rem>=3600?sprintf("%dh%02d",intdiv($rem,3600),intdiv($rem%3600,60)):sprintf("%d min",max(1,intdiv($rem,60)));
        $country=req_html((string)($r["country"] ?? ""));
        $rdns=req_html((string)($r["rdns"] ?? ""));
        $btn=$tpl->button_autnonome("{fw_unban}","Loadjs('$page?unban-js=".urlencode($ip)."')",ico_unlock,"AsFirewallManager",0,"btn-warning");
        $html[]="<tr><td><strong>$ip</strong></td><td>$country</td><td>$rdns</td><td>$remTxt</td><td style='width:1%'>$btn</td></tr>";
    }
    $html[]="</tbody></table></div>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
}

function unban_js(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $ip=(string)($_GET["unban-js"] ?? "");
    if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)===false){echo "alert('invalid address');";return;}
    $resp=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/firewall/autoban/".rawurlencode($ip)),true);
    if(!is_array($resp) || empty($resp["Status"])){
        $err=req_js((string)($resp["Error"] ?? "API"));
        echo "alert('$err');";
        return;
    }
    admin_tracks("Unbanned $ip from the firewall automatic blocking");
    echo "LoadAjax('fw-banned','$page?banned=yes');";
}

// Classement des règles, sources et ports sur une période (GET
// /firewall/events/top, calculé par le daemon depuis firewall.log).
function top(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $hours=intval($_GET["top"]);
    if(!in_array($hours,array(1,24,168))){$hours=24;}
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/events/top?hours=$hours"),true);
    if(!is_array($json) || empty($json["Status"])){
        echo $tpl->div_error("{error}<hr>".req_html((string)($json["Error"] ?? "API")));
        return;
    }
    $d=$json["Data"];
    $html=array();
    $html[]="<div id='fw-events-top'>";
    $btns=array();
    foreach(array(1=>"1h",24=>"24h",168=>"{last_7_days}") as $h=>$label){
        $cls=$h==$hours?"btn-primary":"btn-default";
        $btns[]="<button class='btn $cls btn-sm' onclick=\"LoadAjax('fw-events-top','$page?top=$h');\">$label</button>";
    }
    $html[]="<div style='margin:10px 0'>".implode("&nbsp;",$btns)."&nbsp;&nbsp;"
        .intval($d["events"] ?? 0)." {events}, ".intval($d["rejected"] ?? 0)." {rejected}</div>";
    $html[]="<div class='row'>";
    $html[]=top_table($tpl,"{fw_top_rules}",$d["rules"] ?? array(),"rule");
    $html[]=top_table($tpl,"{fw_top_sources}",$d["sources"] ?? array(),"src");
    $html[]=top_table($tpl,"{fw_top_ports}",$d["ports"] ?? array(),"port");
    $html[]="</div></div>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
}

function top_table($tpl,$title,$rows,$kind):string{
    $h=array();
    $h[]="<div class='col-md-4'><h3>$title</h3>";
    $h[]="<table class='table table-striped'><tbody>";
    if(!is_array($rows) || count($rows)==0){
        $h[]="<tr><td>{no_data}</td></tr>";
    }
    foreach((is_array($rows)?$rows:array()) as $r){
        $count=intval($r["count"] ?? 0);
        $key=req_html((string)($r["key"] ?? ""));
        if($kind=="rule"){
            $action=($r["action"] ?? "")=="reject"
                ?"<span class='label label-danger'>DENY</span>"
                :"<span class='label label-info'>LOG</span>";
            $id=intval($r["rule_id"] ?? 0);
            $label=$id>0?req_html((string)($r["label"] ?? "")):"Default";
            // Règle supprimée depuis : son nom n'existe plus, aucun lien.
            if($id>0 && $label===""){$label="{deleted} (#$id)";}
            elseif($id>0){$label=$tpl->td_href($label,null,"Loadjs('fw.rules.php?ruleid-js=$id')");}
            $h[]="<tr><td style='width:1%'>$action</td><td>$label</td><td style='width:1%;text-align:right'><strong>$count</strong></td></tr>";
            continue;
        }
        if($kind=="src"){
            $key=$tpl->td_href($key,"","Loadjs('fw.modsecurity.threats.php?zoom-ip-js=$key')");
        }
        $h[]="<tr><td>$key</td><td style='width:1%;text-align:right'><strong>$count</strong></td></tr>";
    }
    $h[]="</tbody></table></div>";
    return implode("\n",$h);
}

function form_search():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $search="";
    if(isset($_SESSION["FW_SEARCH"])){
        $search=$_SESSION["FW_SEARCH"];
    }
    echo $tpl->search_block($page,null,null,"value=$search");
    return true;

}



function search(){
    $tpl=new template_admin();
    $_SESSION["FW_SEARCH"]=$_GET["search"];
	$MAIN=$tpl->format_search_protocol($_GET["search"]);

    $sock=new sockets();
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}

    $data=$sock->REST_API("/firewall/events/$rp/$search");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<hr>".json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->div_error("{error}<br>Framework return false!<hr>$json->Error");
    }

    $filename=PROGRESS_DIR."/firehol.syslog";
	$date_text=$tpl->_ENGINE_parse_body("{date}");
	$rule_text=$tpl->_ENGINE_parse_body("{rule}");
	$src=$tpl->_ENGINE_parse_body("{src}");
	$srcport=$tpl->_ENGINE_parse_body("{source_port}");
	$dst=$tpl->_ENGINE_parse_body("{dst}");
	$dstport=$tpl->_ENGINE_parse_body("{destination_port}");
	$incoming=$tpl->_ENGINE_parse_body("{incoming2}");
	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th nowrap>$date_text</th>
        	<th></th>
			<th>$rule_text</th>
            <th>IN</th>
            <th>$src</th>
             <th nowrap>$srcport</th>
            <th>OUT</th>
            <th>$dst</th>
            <th nowrap>$dstport</th>
        </tr>
  	</thead>
	<tbody>
";

    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $results=$q->QUERY_SQL("SELECT * FROM pnic_bridges WHERE enabled=1");
    foreach ($results as $index=>$ligne) {
        $ID         = $ligne["ID"];
        $nicfrom    = $ligne["nic_from"];
        $nicto      = $ligne["nic_to"];

        $ROUTERS[$ID]="$nicfrom {to} $nicto";

    }


    $sql="SELECT ID,rulename FROM iptables_main";
    $results=$q->QUERY_SQL($sql);
    $RULESNAMES[-999]="Default";
    $RULESNAMES[-998]="MALFORMED BAD";
    $RULESNAMES[-997]="MALFORMED XMAS";
    $RULESNAMES[-996]="SYN FLOOD";
    $RULESNAMES[-995]="ICMP FLOOD";
    $RULESNAMES[-994]="NEW TCP w/o SYN";
    $RULESNAMES[-993]="DCHP Query";
    $RULESNAMES[-992]="Artica Web console";
    $RULESNAMES[-991]="MALFORMED NULL";
    foreach ($results as $index=>$ligne){
        if(preg_match("#<br><small>(.+?)<\/small>#",$ligne["rulename"],$re)){
            $ligne["rulename"]=str_replace("<br><small>{$re[1]}</small>","",$ligne["rulename"]);
        }

        $RULESNAMES[$ligne["ID"]]=$ligne["rulename"];
    }


	$data=explode("\n",@file_get_contents($filename));
	if(count($data)>3){$_SESSION["FW_SEARCH"]=$_GET["search"];}
	rsort($data);


	$trusted_network=$tpl->_ENGINE_parse_body("{trusted_network}");
    $PROXYRULES_IN_NAME=$tpl->_ENGINE_parse_body("{APP_SQUID} {inbound}");
    $PROXYRULES_OUT_NAME=$tpl->_ENGINE_parse_body("{APP_SQUID} {outbound}");

    foreach ($json->Logs as $line){
		$line=trim($line);
		$ruleid=0;
		$rulename=null;
		$ACTION=null;$OUT=null;
		// Champs du noyau propres à CETTE ligne : sans remise à zéro, un ICMP
		// (sans SPT/DPT) affichait les ports de la ligne précédente.
		$KEYS=array();
        $ico="";
		$FF=false;
		// Préfixes posés par le pare-feu Go : « ARTICA:REJECT: » (rejet, politique
		// ou action Drop) et « ARTICA:LOG:<id>: » (action « Log only »). Seules
		// les lignes FIREHOL: étaient lues : ces événements n'apparaissaient pas.
		$artica=null;
		if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.+?kernel.+?ARTICA:(REJECT|LOG|BAN):(?:([0-9]+):)?(.*)#", $line,$ra)){
			$artica=$ra;
			$re=array($ra[0],$ra[1],$ra[2],$ra[3],$ra[6]);
		}elseif(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.+?kernel.+?FIREHOL:(.+)#", $line,$re)){continue;}
		
		$date="$re[1] $re[2] ".date('Y')." $re[3]";
		$xtime=strtotime($date);
		$FTime=date("Y-m-d H:i:s",$xtime);
		$curDate=date("Y-m-d");
		$FTime=trim(str_replace($curDate, "", $FTime));
		// Pentest 2026-11-04 : ligne "kernel" du journal (tout processus local peut en ecrire une : donnee externe)
		// affichee sans encodage (XSS stockee). Encodee avant le decoupage : les marqueurs cherches ensuite
		// (PASS-, DENY-, SRC=...) ne contiennent ni < > & ni quote.
		$line=req_html($re[4]);
		$line=preg_replace('#^(TRANSIT|LIMIT):#','',$line);

		if($artica!==null){
			$aid=intval($artica[5] ?? 0);
			if($artica[4]=="REJECT"){
				$ACTION="<span class='label label-danger'>&nbsp;&nbsp;&nbsp;&nbsp;DENY&nbsp;&nbsp;&nbsp;&nbsp;</span>";
				$rulename="Default";
				// ARTICA:REJECT:TRANSIT: (politique du trafic traversant),
				// ARTICA:REJECT:<id>:LIMIT: (limite de connexions d'une règle).
				if(strpos((string)$artica[6],"TRANSIT:")===0){$rulename=$tpl->_ENGINE_parse_body("{fw_transit_policy}");}
				if(strpos((string)$artica[6],"LIMIT:")===0){$ACTION="<span class='label label-warning'>&nbsp;&nbsp;&nbsp;&nbsp;LIMIT&nbsp;&nbsp;&nbsp;&nbsp;</span>";}
			}elseif($artica[4]=="BAN"){
				// Blocage automatique : source bannie à cet instant.
				$ACTION="<span class='label label-danger'>&nbsp;&nbsp;&nbsp;&nbsp;BAN&nbsp;&nbsp;&nbsp;&nbsp;</span>";
				$rulename=$tpl->_ENGINE_parse_body("{fw_banned_auto}");
			}else{
				$ACTION="<span class='label label-info'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;LOG&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
			}
			if($aid>0){
				$rulename=isset($RULESNAMES[$aid])?$tpl->td_href($RULESNAMES[$aid],null,"Loadjs('fw.rules.php?ruleid-js=$aid')"):"Unknown ($aid)";
			}
		}

		if(preg_match("#SYN FLOOD:(.+)#",$line,$re)){
			$rulename="SYN FLOOD";
			$ACTION="<span class='label label-warning'>FLOOD</span>";
			$line=$re[2];
		}
		
		if(preg_match("#PASS-unknown:(.+)#",$line,$re)){
			$rulename="PASS UNKNOWN";
			$FF=true;
			$ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
			$line=$re[1];
		}
		
		if(preg_match("#RULE-([0-9]+):(.+)#", $line,$re)){
			$ruleid=$re[1];
			$line=$re[2];
		}
        if(preg_match("#NFQUEUE_BLOCK:(.+)#",$line,$re)){
            $rulename="NFQUEUE";
            $ico="<i class='text-danger fas fa-hockey-mask'></i>&nbsp;";
            $ACTION="<span class='label label-danger'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;DENY&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
            $line=$re[1];
        }
		
		if(preg_match("#OUT-.*?:(.+)#",$line,$re)){
			$rulename="PASS OUT";
			$FF=true;
			$ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
			$line=$re[1];
		}
        if(preg_match("#PROXYRULES_IN.*?:(.+)#",$line,$re)){
            $rulename=$PROXYRULES_IN_NAME;
            $FF=true;
            $ACTION="<span class='label label-danger'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;DENY&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
            $line=$re[1];
        }
        if(preg_match("#PROXYRULES_OUT.*?:(.+)#",$line,$re)){
            $rulename=$PROXYRULES_OUT_NAME;
            $FF=true;
            $ACTION="<span class='label label-danger'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;DENY&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
            $line=$re[1];
        }


        if(preg_match("#ACCEPT_-992.*?:(.+)#",$line,$re)){
            $rulename="Artica Web console";
            $FF=true;
            $ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
            $line=$re[1];
        }
		
		if(preg_match("#NEW TCP w/o SYN:(.+)#",$line,$re)){
			$rulename="NEW TCP w/o SYN";
			$FF=true;
			$ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
			$line=$re[1];
		}	
		
		if(preg_match("#DENY-(.+?)\/(.+?):(.+)#",$line,$re)){
			$rulename="Router";
			$FF=true;
			$ACTION="<span class='label label-danger'>&nbsp;&nbsp;&nbsp;&nbsp;DENY&nbsp;&nbsp;&nbsp;&nbsp;</span>";
			$line=$re[3];
		}	
		if(preg_match("#PASS-(.+?)\/(.+?):(.+)#",$line,$re)){
			$rulename="Router";
			$FF=true;
			$ACTION="<span class='label label-danger'>&nbsp;&nbsp;&nbsp;&nbsp;DENY&nbsp;&nbsp;&nbsp;&nbsp;</span>";
			$line=$re[3];
		}	

		if(preg_match("#IN-(.+?):(.+)#",$line,$re)){
			$re[1]=trim($re[1]);
			$rulename="$incoming {$re[1]}";
			$FF=true;
			$ACTION="<span class='label label-danger'>&nbsp;&nbsp;&nbsp;&nbsp;DENY&nbsp;&nbsp;&nbsp;&nbsp;</span>";
			$line=$re[2];
		}

		if(preg_match("#DROP_([0-9\-]+):(.+)#",$line,$re)){
            $re[1]=trim($re[1]);
            $ACTION="<span class='label label-danger'>&nbsp;&nbsp;&nbsp;&nbsp;DENY&nbsp;&nbsp;&nbsp;&nbsp;</span>";
            $line=trim($re[2]);

            if(!isset($RULESNAMES[$re[1]])){
                $rulename="Unknown ({$re[1]})";
            }else {
                $rulename = $RULESNAMES[$re[1]];
                if($re[1]>0) {
                    $rulename = $tpl->td_href($rulename, null,
                        "Loadjs('fw.rules.php?ruleid-js={$re[1]}}&function=".req_url($_GET["function"] ?? "")."')");
                }
            }
        }

		if(preg_match("#ACCEPT_([0-9]+):(.+)#",$line,$re)){
            $re[1]=trim($re[1]);
            $ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
            $line=$re[2];
            if(!isset($RULESNAMES[$re[1]])){
                $rulename="Unknown ({$re[1]})";
            }else {

                $rulename = $RULESNAMES[$re[1]];
                if($re[1]>0) {
                    $rulename = $tpl->td_href($rulename, null,
                        "Loadjs('fw.rules.php?ruleid-js={$re[1]}}&function=".req_url($_GET["function"] ?? "")."')");
                }
            }

        }
        if(preg_match("#NAT_([0-9]+):(.+)#",$line,$re)){
            $re[1]=trim($re[1]);
            $ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;NAT&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
            $line=$re[2];
            if(!isset($RULESNAMES[$re[1]])){
                $rulename="Unknown ({$re[1]})";
            }else {

                $rulename = $RULESNAMES[$re[1]];
                if($re[1]>0) {
                    $rulename = $tpl->td_href($rulename, null,
                        "Loadjs('fw.rules.php?ruleid-js={$re[1]}}&function=".req_url($_GET["function"] ?? "")."')");
                }
            }

        }
        if(preg_match("#FORWARD_([0-9]+):(.+)#",$line,$re)){
            $re[1]=trim($re[1]);
            $ACTION="<span class='label label-primary'>FORWARD</span>";
            $line=$re[2];
            if(!isset($ROUTERS[$re[1]])){
                $rulename="Unknown ({$re[1]})";
            }else {

                $rulename = $ROUTERS[$re[1]];
                if($re[1]>0) {
                    $rulename = $tpl->td_href($rulename, null,
                        "Loadjs('fw.bridges.php?ruleid-js={$re[1]}',true);");
                }
            }

        }
        if(preg_match("#TPROXY_([0-9]+):(.+)#",$line,$re)){
            $re[1]=trim($re[1]);
            $ACTION="<span class='label label-primary'>TPROXY</span>";
            $line=$re[2];
            if(!isset($RULESNAMES[$re[1]])){
                $rulename="Unknown ({$re[1]})";
            }else {
                $rulename = $RULESNAMES[$re[1]];
                if($re[1]>0) {
                    $rulename = $tpl->td_href($rulename, null,
                        "Loadjs('fw.rules.php?ruleid-js={$re[1]}}&function=".req_url($_GET["function"] ?? "")."')");
                }
            }

        }
        if(preg_match("#NAT_SNAT:(.+)#",$line,$re)){
            $re[1]=trim($re[1]);
            $ACTION="<span class='label label-primary'>SNAT</span>";
            $line=$re[1];
            $rulename="Default source NAT";

        }

        if(preg_match("#TRUSTEDNETS:(.+)#",$line,$re)){
            $re[1]=trim($re[1]);
            $ACTION="<span class='label label-primary'>TRUSTED</span>";
            $line=$re[1];
            $rulename=$trusted_network;

        }
        if(preg_match("#TRANSFERT:(.+)#",$line,$re)){
            $re[1]=trim($re[1]);
            $ACTION="<span class='label label-primary'>FORWARD</span>";
            $line=$re[1];
            $rulename="";

        }
        if(preg_match("#DENY_DHCP:(.+)#",$line,$re)){
            $re[1]=trim($re[1]);
            $ACTION="<span class='label label-danger'>DENY DHCP</span>";
            $line=$re[1];
            $rulename="Default";

        }



        if(preg_match("#MASQUERADE_([0-9]+):(.+)#",$line,$re)){
            $re[1]=trim($re[1]);
            $ACTION="<span class='label label-primary'>MASQUER.</span>";
            $line=$re[2];
            if(!isset($ROUTERS[$re[1]])){
                $rulename="Unknown ({$re[1]})";
            }else {

                $rulename = $ROUTERS[$re[1]];
                if($re[1]>0) {
                    $rulename = $tpl->td_href($rulename, null,
                        "Loadjs('fw.bridges.php?ruleid-js={$re[1]}',true);");
                }
            }

        }
        if(preg_match("#POLICY_REJECT:(.+)#",$line,$re)){
            $rulename = $tpl->_ENGINE_parse_body("{policy}: {interface}");
            $ACTION = "<span class='label label-danger'>&nbsp;&nbsp;&nbsp;&nbsp;DENY&nbsp;&nbsp;&nbsp;&nbsp;</span>";
            $line = $re[1];
        }

       if(preg_match("#POLICY_ACCEPT:(.+)#",$line,$re)){
            $rulename = $tpl->_ENGINE_parse_body("{policy}: {interface}");
            $ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
            $line = $re[1];
        }
        if(preg_match("#CLIWEB_ALLOW:(.+)#",$line,$re)){
            $rulename = $tpl->_ENGINE_parse_body("{client}: HTTP,SSL,FTP");
            $ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
            $line = $re[1];
        }
        if(preg_match("#CLIPING_ALLOW:(.+)#",$line,$re)){
            $rulename = $tpl->_ENGINE_parse_body("{client}: PING");
            $ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
            $line = $re[1];
        }
        if(preg_match("#CLIDNS_ALLOW:(.+)#",$line,$re)){
            $rulename = $tpl->_ENGINE_parse_body("{client}: DNS");
            $ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
            $line = $re[1];
        }
		if(preg_match("#PASS-(.+?):(.+)#",$line,$re)){
			$rulename="Default {$re[1]}";
			$FF=true;
			$ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
			$line=$re[2];
		}		
		if(preg_match("#DENY-(.+?):(.+)#",$line,$re)){
			$rulename="<strong>Default {$re[1]}</strong>";
			$FF=true;
			$ACTION="<span class='label label-danger'>&nbsp;&nbsp;&nbsp;&nbsp;DENY&nbsp;&nbsp;&nbsp;&nbsp;</span>";
			$line=$re[2];
		}
        if(preg_match("#CYBERCRIME_([A-Z]+)-(.+?):(.+)#",$line,$re)){
            $rulename="CYBERCRIME <small>({$re[1]})</small>";
            $ACTION="<span class='label label-danger'>&nbsp;&nbsp;&nbsp;&nbsp;DENY&nbsp;&nbsp;&nbsp;&nbsp;</span>";
            $line=$re[3];
        }
        if(preg_match("#CROWDSEC:(.+)#",$line,$re)){
            $rulename="<strong>CROWDSEC</strong>";
            $ACTION="<span class='label label-danger'>&nbsp;&nbsp;&nbsp;&nbsp;DENY&nbsp;&nbsp;&nbsp;&nbsp;</span>";
            $line=$re[1];
        }
        if(preg_match("#SSHD:(.+)#",$line,$re)){
            $rulename="<strong>OpenSSH</strong>";
            $ACTION="<span class='label label-danger'>&nbsp;&nbsp;&nbsp;&nbsp;DENY&nbsp;&nbsp;&nbsp;&nbsp;</span>";
            $line=$re[1];
        }
        if(preg_match("#PCAPDETECT:(.+)#",$line,$re)){
            $rulename="<strong>PCAP</strong>";
            $ACTION="<span class='label label-warning'>&nbsp;&nbsp;&nbsp;&nbsp;WARN&nbsp;&nbsp;&nbsp;&nbsp;</span>";
            $line=$re[1];
        }

		if($rulename==null){
			echo "<li>387 [$line]</li>\n";
		}
		
		$ri=preg_split("#\s+#", $line);
		foreach ($ri as $tron){
			if(!preg_match("#(.+?)=(.*)#", $tron,$re)){$KEYS[trim($tron)]=true;continue;}
			$KEYS[trim($re[1])]=trim($re[2]);
		}
		if(!isset($KEYS["IN"])){$KEYS["IN"]=null;}
		if(!isset($KEYS["OUT"])){$KEYS["OUT"]=null;}
		if($KEYS["IN"]<>null){
			if(!isset($INTERFACES[$KEYS["IN"]])){$nic=new system_nic($KEYS["IN"]);$INTERFACES[$KEYS["IN"]]="{$KEYS["IN"]} - $nic->NICNAME"; }
		}
		if($KEYS["OUT"]<>null){
			if(!isset($INTERFACES[$KEYS["OUT"]])){$nic=new system_nic($KEYS["OUT"]);$INTERFACES[$KEYS["OUT"]]="{$KEYS["OUT"]} - $nic->NICNAME"; }
		}	

		if($ruleid>0){
			if(!isset($INTERFACES["R{$ruleid}"])){
				$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
				$ligne=$q->mysqli_fetch_array("SELECT rulename,accepttype FROM iptables_main WHERE ID='$ruleid'");
				$INTERFACES["R{$ruleid}"]=$ligne["rulename"];
				$INTERFACES["A{$ruleid}"]=$ligne["accepttype"];
				$rulename=$ligne["rulename"];
			}else{
				$rulename=$INTERFACES["R{$ruleid}"];
			}
			
		}
		
		if(isset($INTERFACES["A{$ruleid}"])){
			if($INTERFACES["A{$ruleid}"]=="DROP"){$ACTION="<span class='label label-danger'>&nbsp;&nbsp;&nbsp;&nbsp;DENY&nbsp;&nbsp;&nbsp;&nbsp;</span>";}
			if($INTERFACES["A{$ruleid}"]=="RETURN"){$ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";}
			if($INTERFACES["A{$ruleid}"]=="ACCEPT"){$ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";}
			if($INTERFACES["A{$ruleid}"]=="LOG"){$ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";}
		}
		if($ACTION==null){
			$ACTION="<span class='label label-info'> - </span>";
		}

        if(isset($INTERFACES[$KEYS["OUT"]])) {
            $OUT = $INTERFACES[$KEYS["OUT"]];
        }
		$IN=$INTERFACES[$KEYS["IN"]] ?? null;
		$MAC_DEST=null;
		$MAC_SRC=null;
		if(isset($KEYS["MAC"])){
			$MACZ=explode(":",$KEYS["MAC"]);
			$MAC_DEST="&nbsp;|&nbsp;{$MACZ[0]}:{$MACZ[1]}:{$MACZ[2]}:{$MACZ[3]}:{$MACZ[4]}:{$MACZ[5]}";
			$MAC_SRC="&nbsp;|&nbsp;{$MACZ[6]}:{$MACZ[7]}:{$MACZ[8]}:{$MACZ[9]}:{$MACZ[10]}:{$MACZ[11]}";
		}
		
		
		
		
		if($OUT==null){$OUT=" - ";}
		if($IN==null){$IN=" - ";}

        $modtools=new modesctools();
        $modtools->hostinfo($KEYS["SRC"],true);
        $flag="";
        $hostname="";
        if(strlen($modtools->flag)>3){
            $flag="<img src='img/".$modtools->flag."'>&nbsp;&nbsp;";
        }
        if(strlen($modtools->hostname)>3){
            if(!preg_match("#^[0-9\.]+$#",$modtools->hostname)) {
                $hostname = "&nbsp;<small>(" . req_html($modtools->hostname) . ")</small>";
            }
        }

        // $KEYS vient de $line deja encodee : texte decode pour td_href (qui encode), URL encodee pour le JS.
        $SRC_RAW=html_entity_decode((string)($KEYS["SRC"] ?? ""),ENT_QUOTES,"ISO-8859-1");
        $src=$tpl->td_href($SRC_RAW,"","Loadjs('fw.modsecurity.threats.php?zoom-ip-js=".req_url($SRC_RAW)."')");



		$fleche=ico_arrow_right;
		$html[]="<tr>
				<td style='width:1%' nowrap>$FTime</td>
				<td style='width:1%' nowrap>$ACTION</td>
				<td style='width:1%' nowrap>$rulename</td>
				<td style='width:1%' nowrap>$IN</td>
                <td>$ico$flag$src$MAC_SRC$hostname</td>  
                <td style='width:1%' nowrap>".($KEYS["SPT"] ?? "-")."</td>                  
                <td style='width:1%' nowrap>$OUT</td>
                <td style='width:1%' nowrap>{$KEYS["DST"]}$MAC_DEST</td>
                <td style='text-align: right;width:1%' nowrap><i class='$fleche'></i>&nbsp;<strong style='font-size:14px'>".($KEYS["DPT"] ?? "-")."</strong></td>    
                </tr>";
		
	}
	
	$html[]="</tbody></table>";
	echo @implode("\n", $html);
	
	
	
}
