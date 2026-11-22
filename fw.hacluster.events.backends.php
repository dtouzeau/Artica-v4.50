<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["search"])){search();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html=$tpl->page_header(
		"{backends_events}",
		ico_eye,
		"{hacluster_backends_events_explain}",
		"$page?start=yes",
		"hacluster-backend-events",
		"progress-firehol-restart",false,
		"table-hacluster-backend-events"
	);

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_HACLUSTER} {backends_events}",$html);
		echo $tpl->build_firewall();
		return;
	}
	echo $tpl->_ENGINE_parse_body($html);
}

function start():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html=array();
	$html[]="<div style='margin-top:10px'>";
	$html[]=$tpl->search_block($page,"","","","");
	$html[]="</div>";
	echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
	return true;
}

function search():bool{
	$sock=new sockets();
	$tpl=new template_admin();
	$max=0;$date=null;$c=0;
	
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}

    $data=$sock->REST_API("/hacluster/clients/events/$rp/$search");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<hr>".json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        echo $tpl->div_error("{error}<br>Framework return false!<hr>$json->Error");
        return false;
    }

	$rows=array();
	if(!isset($json->Logs) || !is_array($json->Logs)){ $json->Logs=array(); }
	if(count($json->Logs)>3){$_SESSION["HACLUSTER_BACKENDS_SEARCH"]=$_GET["search"];}

    $tpl=new template_admin();

    foreach ($json->Logs as $line){
		$line=trim($line);
		if(!preg_match("#^([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+(.+?)\s+hacluster-client\[([0-9]+)\]:(.+)#", $line,$re)){
			VERBOSE("unparsable: $line",__LINE__);
			continue;}

		$xtime=strtotime($re[1] ." ".$re[2]." ".$re[3]);
		$FTime=date("Y-m-d H:i:s",$xtime);
		$curDate=date("Y-m-d");
		$FTime=trim(str_replace($curDate, "", $FTime));
		$hostname=$re[4];
		$pid=$re[5];
		// Pentest 2026-11-04 : ligne syslog hacluster-client (donnee externe) affichee sans encodage (XSS stockee).
		// Encodee AVANT la decoration : les marqueurs remplaces ensuite ne contiennent ni < > & ni quote.
		$line=req_html(trim($re[6]));
        $label=null;
        $label_class="label-default";


		if(preg_match("#success#i", $line)){
            $line="<span class='text-success'>$line</span>";
            $label_class="label-primary";
        }
        $STAMP=false;
		if(preg_match("#(ALERT|CPU Usage high|DOWN|fatal|corrupted|copy_failed|unable_to_copy|missing|failed|Cannot contact|\[ERROR|Error)#i", $line)){
            $label_class="label-danger";
			$line="<span class='text-danger'>$line</span>";
            $STAMP=true;
		}
        if(!$STAMP) {
            if (preg_match("#(Emergency mode)#i", $line)) {
                $label_class="label-danger";
                $line = "<span class='text-danger'>$line</span>";
            }
        }
        if(strpos("  $line","[WARNING]")>0){
            $label_class="label-warning";
            $line=str_replace("[WARNING]","",$line);
        }


        if(!$STAMP) {
            if (preg_match("#(CPU Usage medium|triggered)#i", $line)) {
                $label_class="label-warning";
                $line = "<span class='text-warning'>$line</span>";
            }
        }
        if(!$STAMP){
            if (preg_match("#(Status to UP)#i", $line)) {
                $label_class="label-info";
                $line = "<span class='text-info'>$line</span>";
            }

        }

        if(preg_match("#HaClusterClient#",$line)){
            $label="<span class='label $label_class'>HaCluster Client</span>&nbsp;";
            $line=str_replace("HaClusterClient","",$line);

        }
        if(strpos($line,"ClusterClientHTTP.go")>0){
            $line=str_replace("ClusterClientHTTP.go","",$line);
            $line="<span class='label label-success'>Cluster</span>&nbsp;$line";
        }
        if(strpos("  $line","[CLUSTER_CLIENT]:")>0){
            $line=str_replace("[CLUSTER_CLIENT]:","",$line);
            $line="<span class='label label-success'>Cluster</span>&nbsp;$line";
        }


        if(strpos($line,"Proxy service:")>0){
            $line=str_replace("Proxy service:","",$line);
            $line="<span class='label $label_class'>{APP_SQUID}</span>&nbsp;$line";
        }
        if(strpos($line,"[exec.squid.php.storedir.php}]:")>0){
            $line=str_replace("[exec.squid.php.storedir.php}]:","",$line);
            $line="<span class='label $label_class'>{APP_SQUID}</span>&nbsp;$line";
        }


        $line=str_replace("[SERVICE]:","<span class='label label-info'>Service</span>&nbsp;",$line);
        $line=str_replace("[ERROR] ","",$line);
        $line=str_replace("SquidTools.","",$line);
        $line=str_replace("Operations.go","",$line);
        $line=str_replace("SquidTools.go","",$line);
        $line=str_replace("InetChecks.go","",$line);
        $line=str_replace("go[main.main.func2:","",$line);
        $line=str_replace("acluster/ClusterClient.","",$line);
        $line=str_replace("acluster/ClusterClientHTTP.","",$line);
        $line=str_replace("InetChecks.","",$line);
        $line=str_replace("main.","",$line);
        $line=str_replace("HaClusterTool.","",$line);
        $line=str_replace("CpuCheck.go","",$line);
        $line=str_replace("[CpuCheck.","[",$line);

        
        $line=str_replace("[HACLIENT]:","<span class='label $label_class'>HaCluster Client</span>&nbsp;",$line);
        $line=str_replace("[HaCluster Client]:","<span class='label $label_class'>HaCluster Client</span>&nbsp;",$line);
        $line=str_replace("HaClusterTool.go","",$line);
        $line=str_replace("ClusterClient.go","",$line);
		$line=$tpl->_ENGINE_parse_body($line);
		$rows[]=hacluster_backend_event_row($FTime,$pid,$hostname,$label.$line);
	}

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
	$html[]="<th class='awc-nowrap' style='width:1%'>{date}</th>";
	$html[]="<th class='awc-nowrap' style='width:1%'>PID</th>";
	$html[]="<th class='awc-nowrap' style='width:1%'>{backend}</th>";
	$html[]="<th>{events}</th>";
	$html[]="</tr></thead><tbody id='awc-rows'>";
	if(count($rows)==0){ $html[]="<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>"; }
	else { $html[]=implode("\n",$rows); }
	$html[]="</tbody></table></div>";
	$query=trim((string)@file_get_contents(PROGRESS_DIR."/hacluster-clients.syslog.query"));
	$html[]="<div class='awc-foot'><span>{showing} ".count($rows)." {items}</span>";
	if($query!==""){ $html[]="<span class='awc-muted' style='font-style:italic'>".req_html($query)."</span>"; }
	$html[]="<span class='awc-pg' id='awc-pager'></span></div>";
	$html[]="</div></div>";

	// TINY : titre + boutons, emis en DERNIER, apres le tableau (skill §2, §7d)
	$page=CurrentPageName();
	$topbuttons=array();
	$topbuttons[]=array("LoadAjax('table-hacluster-backend-events','$page?start=yes')",ico_refresh,"{refresh}");
	$TINY=array();
	$TINY["TITLE"]="{APP_HACLUSTER}&nbsp;&raquo;&nbsp;{backends_events}";
	$TINY["ICO"]=ico_eye;
	$TINY["EXPL"]="{hacluster_backends_events_explain}";
	$TINY["URL"]="hacluster-backend-events";
	$TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
	$tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";

	// CSS, scripts et pager concatenes AUTOUR du parse, jamais dedans (skill §17 regle 1)
	$js="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$tiny</script>";
	echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$js.awc_pager_js(50);
	return true;
}

// une ligne du journal (skill §13b) : $line est deja du HTML construit (et parse) par search()
function hacluster_backend_event_row(string $FTime,string $pid,string $hostname,string $line):string{
	$h ="<tr>";
	$h.="<td class='awc-nowrap awc-mono'>".req_html($FTime)."</td>";
	$h.="<td class='awc-nowrap awc-mono'>".req_html($pid)."</td>";
	$h.="<td class='awc-nowrap'><div class='awc-chip'><span class='awc-chip-ico'><i class='fas fa-server'></i></span><span class='awc-seen'>".req_html($hostname)."</span></div></td>";
	$h.="<td>$line</td>";
	$h.="</tr>";
	return $h;
}
