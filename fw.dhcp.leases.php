<?php
// stamp 01/11/2026
// Post SP10 - 13/10/2026
//Artica Service Pack 8 - Sep 2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpmetrics.inc");

// Les repartiteurs ?delete-rule-js= / delete-remove (delete_js(), delete_remove())
// et ?enable-signature= / ?enable-firewall= appelaient des fonctions definies
// NULLE PART: fatal « Call to undefined function ». Retires.
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["search-form"])){search_form();exit;}
if(isset($_GET["search"])){table();exit;}
page();

function search_form():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'>".$tpl->search_block($page)."</div>";
    return true;
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{leases}",ico_timeout,"{leases_explain}","$page?search-form=yes","dhcp-leases","progress-dhcpleases-restart",true);
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_DHCP} {leases}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

// Search term -> SQL LIKE pattern. postgres_sql has no bound parameters, so the
// term is reduced to the characters a MAC, an IP or a hostname can carry: no
// quote, no backslash, no % can reach the SQL text.
function dlea_like($raw):string{
    $s=strtolower(trim((string)$raw));
    $s=preg_replace('/[^a-z0-9.:_\-* ]/','',$s);
    $s=trim(substr($s,0,64));
    if(strlen($s)<2){return "";}
    $s=str_replace("*","%","%$s%");
    return preg_replace('/%+/','%',$s);
}

// Leases the DHCP server holds right now, per the DHCP metrics collector
// (it reads the live lease file). -1 = unknown.
function dlea_pool_used():int{
    $json=dhcpm_api("/dhcpd/metrics/pools");
    if(!is_object($json) || !isset($json->data->current->samples)){return -1;}
    $used=0;
    foreach($json->data->current->samples as $s){$used+=intval($s->used);}
    return $used;
}

function table(){
	$tpl=new template_admin();
    $page=CurrentPageName();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $jsfunc=$function==""?"blur()":"$function()";

    $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
    if($DisablePostGres==1){
        $installjs=$tpl->framework_buildjs(
            "/postgresql/install","postgres.progress","postgres.log",
            "progress-dhcpleases-restart",
            $jsfunc
        );
        $btn=$tpl->button_autnonome("{install} {APP_POSTGRES}",$installjs,ico_cd,"AsSystemAdministrator",240,"btn-warning");
        $install="<div style='text-align:right;margin-top:20px'>$btn</div>";
        $html[]=$tpl->div_warning("{APP_POSTGRES} {missing}||{need_postgresql_1}<hr>$install");
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }

    $where="";
    $like=dlea_like($_GET["search"] ?? "");
    if($like!=""){
        $where="WHERE (TEXT(l.mac) LIKE '$like' OR host(l.ipaddr) LIKE '$like' OR LOWER(l.hostname) LIKE '$like' OR LOWER(h.fullhostname) LIKE '$like' OR LOWER(h.proxyalias) LIKE '$like')";
    }

    $q=new postgres_sql();
    // dhcpd writes lease times in UTC and dhcpd_leases stores them as
    // "timestamp without time zone": compare with the UTC clock.
    // QUERY_SQL returns false when PostgreSQL is down: pg_fetch_assoc(false) is a
    // TypeError in PHP 8, not silenced by @.
    $resT=$q->QUERY_SQL("SELECT COUNT(*) AS tcount, SUM(CASE WHEN ends > (NOW() AT TIME ZONE 'UTC') THEN 1 ELSE 0 END) AS tactive FROM dhcpd_leases");
    $ligneT=($resT===false)?false:pg_fetch_assoc($resT);
    $total=0;$activeInList=0;
    if(is_array($ligneT)){$total=intval($ligneT["tcount"]);$activeInList=intval($ligneT["tactive"]);}

    // One LEFT JOIN instead of one hostsnet query per lease. dhcpbroadcast is
    // created by articarest (hostnets.PatchTables): only SELECT it when present.
    $bcCol=$q->FIELD_EXISTS("hostsnet","dhcpbroadcast")?",h.dhcpbroadcast":"";
	$sql="SELECT l.mac,l.hostname,l.starts,l.ends,l.ipaddr,h.mac AS hmac,h.fullhostname,h.proxyalias,h.dhcpfixed$bcCol
        FROM dhcpd_leases l LEFT JOIN hostsnet h ON h.mac=l.mac $where ORDER BY l.starts DESC LIMIT 1000";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
        echo $tpl->div_error(dhcpm_h($q->mysql_error));
        return false;
	}

	$cmp=new computers();
    $CallBack=base64_encode($function);
	$icocp=ico_computer;
    $now=time();
    $rows=array();
	while ($ligne = pg_fetch_assoc($results)) {
		$mac=strtolower(trim((string)$ligne["mac"]));
		if($mac==""){continue;}
        $ip=trim((string)$ligne["ipaddr"]);
        $clientName=trim((string)$ligne["hostname"]);
        if($clientName=="-na-" || $clientName=="unknown"){$clientName="";}

		$uid=$cmp->ComputerIDFromMAC($mac);
        $MANU=$tpl->MacToVendor($mac);

		$t1=leases_utc_ts($ligne["starts"]);
		$end=leases_utc_ts($ligne["ends"]);
        $active=$end>$now;

        $known=trim((string)$ligne["hmac"])!="";
        if($known){
            $jshost="Loadjs('fw.edit.computer.php?mac=".urlencode($mac)."&CallBackFunction=$CallBack')";
            $name=trim((string)$ligne["fullhostname"]);
            if($name==""){$name=$clientName;}
            $alias=trim((string)$ligne["proxyalias"]);
            $acticon="fas fa-pen";$acttitle="{edit}";
        }else{
            $ffields=array();
            $ffields[]="mac=".urlencode($mac);
            $ffields[]="hostname=".urlencode($clientName);
            $ffields[]="ipaddr=".urlencode($ip);
            $ffields[]="CallBackFunction=$CallBack";
            $jshost="Loadjs('fw.add.computer.php?".@implode("&",$ffields)."')";
            $name=$clientName;
            $alias="";
            $acticon=ico_plus;$acttitle="{new_computer}";
        }
        $nameHtml=$name==""?"<span class='awc-muted'>{unknown}</span>":dhcpm_h($name);
        if($alias!=""){$nameHtml.=" <span class='awc-muted'>(".dhcpm_h($alias).")</span>";}

        $meta=dhcpm_h($mac);
        if($MANU<>null){$meta.=" &middot; ".dhcpm_h($MANU);}
        if($uid<>null){$meta.=" &middot; ".dhcpm_h($uid);}

        $flags="";
        if($known && intval($ligne["dhcpfixed"])==1){
            $flags="<span class='awc-tag' style='margin-left:6px'>{reserved}</span>";
        }
        if($known && intval($ligne["dhcpbroadcast"] ?? 0)==1){
            $flags.="<span class='awc-tag' style='margin-left:6px' title='{dbc_always_broadcast}'>{dbc_broadcast}</span>";
        }

        $startHtml="<span class='awc-muted'>-</span>";
        if($t1>0){
            $startHtml="<div class='awc-seen'>".dhcpm_h(distanceOfTimeInWords($t1,$now))."</div>"
                ."<div class='awc-seen-sub'>".leases_local_date($t1,$ligne["starts"])."</div>";
        }
        $endHtml="<span class='awc-muted'>-</span>";
        if($end>0){
            $rel=$active?dhcpm_h(distanceOfTimeInWords($now,$end)):"-";
            $endHtml="<div class='awc-seen'>$rel</div><div class='awc-seen-sub'>".leases_local_date($end,$ligne["ends"])."</div>";
        }
        $badge=$active?"<span class='awc-badge awc-badge-on'>{active2}</span>":"<span class='awc-badge dlea-badge-off'>{expired}</span>";

        $h="<tr>";
        $h.="<td><div class='awc-ident'><span class='awc-ico".($known?"":" awc-ico-off")."'><i class='$icocp'></i></span><span>"
            ."<span class='awc-name'><a href=\"javascript:blur();\" OnClick=\"$jshost\">$nameHtml</a></span>"
            ."<div class='awc-meta'>$meta</div></span></div></td>";
        $h.="<td><span class='dlea-ip'>".dhcpm_h($ip)."</span>$flags</td>";
        $h.="<td>$startHtml</td>";
        $h.="<td>$endHtml</td>";
        $h.="<td class='center'>$badge</td>";
        $h.="<td><div class='awc-acts'><a class='awc-act' title='$acttitle' href=\"javascript:blur();\" OnClick=\"$jshost\"><i class='$acticon'></i></a></div></td>";
        $h.="</tr>";
        $rows[]=$h;
	}

    $html=array();
    // Coherence: this table is a snapshot of the lease file refreshed every
    // 30 minutes by articarest; the metrics collector counts the live file.
    $poolUsed=dlea_pool_used();
    if($poolUsed>=0 && $like=="" && $poolUsed!=$activeInList){
        $html[]=$tpl->div_warning("{dlea_out_of_date}||{dlea_active_on_server}: <strong>$poolUsed</strong> &middot; {dlea_active_in_list}: <strong>$activeInList</strong><br>{dlea_out_of_date_explain}");
    }
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{hostname}</th><th>{addr}</th><th>{start_time}</th><th>{end_time}</th><th class='center'>{status}</th><th style='text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='dlea-rows'>";
    if(count($rows)==0){
        $html[]="<tr><td colspan='6'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} ".count($rows)." / $total {items}</span><span class='awc-pg' id='dlea-pager'></span></div>";
    $html[]="</div></div>";

    $jsrestart=$tpl->framework_buildjs("/dhcpd/service/leases",
        "dhcpd.leases.progress","dhcpd.leases.log","progress-dhcpleases-restart",$jsfunc);
    $jsClean=$tpl->framework_buildjs("/dhcpd/leases/flush",
        "dhcdp.leases.empty.progress","dhcdp.leases.empty.progress.txt","progress-dhcpleases-restart",$jsfunc);

    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{leases}";
    $TINY_ARRAY["ICO"]=ico_timeout;
    $TINY_ARRAY["EXPL"]="{leases_explain}";
    $TINY_ARRAY["URL"]="dhcp-leases";
    $topbuttons=array();
    $topbuttons[] = array($jsrestart, "fa fa-repeat", "{rescan}");
    $topbuttons[] = array($jsClean, ico_trash, "{purgeAll}");
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $style="<style>
.dlea-badge-off{background:#e8ecec;color:#3d4947}
.dlea-ip{font-size:14px;font-weight:600;color:#111d23;white-space:nowrap}
</style>";
    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$jstiny</script>";
    echo awc_table_style().$style.$tpl->_ENGINE_parse_body(@implode("\n",$html)).$script.awc_pager_js(25,"dlea-rows","dlea-pager");
    return true;
}

function leases_utc_ts($v):int{
    $v=trim((string)$v);
    if($v==""){return 0;}
    $t=strtotime($v." UTC");
    return $t===false?0:$t;
}

function leases_local_date(int $ts,$raw):string{
    // Pentest 2026-11-04 : starts/ends (donnee externe) : encodage octet-sur (UTF-8 invalide rendait "").
    if($ts<=0){return req_html((string)$raw);}
    return date("Y-m-d H:i:s",$ts);
}
