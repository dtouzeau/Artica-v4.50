<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["search"])){search();exit;}

page();

function ifisright():bool{
	$users=new usersMenus();
	if($users->AsWebMaster){return true;}
    if($users->AsWebSecurity){return true;}
    if($users->AsWebMonitor){return true;}
	if($users->AsWebStatisticsAdministrator){return true;}
    return false;
}
function start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!ifisright()){
        echo $tpl->div_error("{ERROR_NO_PRIVS2}");
        return false;
    }
    $html[]="<div class='row'><div class='ibox-content'>";
    $html[]=$tpl->search_block($page,"","events","table-nginx-search-events","&table=yes");
    $html[]="<div id='table-nginx-search-events'></div>
	</div>
	</div>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{APP_NGINX} {service_events}",ico_eye,"",
        "$page?start=yes","web-events","progress-nginx-service-events-restart",true,"table-loader-nginx-service-events");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_NGINX} {service_events}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function parseRows($line):array{

    $ARRAY["TIME"]=0;
    $ARRAY["TYPE"]="unkown";
    $ARRAY["EVENTS"]=$line;
    $ARRAY["DATE"]="";
    if(!preg_match("#(.+?)\s+\[(.+?)\]\s+.*?:\s+(.+)#i",$line,$matches)){
        return $ARRAY;
    }
    $ARRAY["TIME"]=strtotime($matches[1]);
    $ARRAY["TYPE"]=$matches[2];
    $ARRAY["EVENTS"]=$matches[3];
    $ARRAY["DATE"]=$matches[1];
    return $ARRAY;
}

function search(){
	$tpl=new template_admin();
    if(!ifisright()){
        echo $tpl->div_error("{ERROR_NO_PRIVS2}");
        return false;
    }

    $t=time();
    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    if(strlen($search["S"])<2){$search["S"]="*";}
    $search["S"]=str_replace("%",".*",$search["S"]);
    $ss=urlencode(base64_encode($search["S"]));

    $MAX=intval($search["MAX"]);
    if($MAX==0){$MAX=250;}

    $EndPoint="/reverse-proxy/events/$ss/$MAX";
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX($EndPoint);
    $LEVELS["INFO"]="<span class='label label-default'>INFO</span>";

    $LEVELS["warn"]="<span class='label label-default'>WARN.</span>";
    $LEVELS["alert"]="<span class='label label-warning'>ALERT</span>";
    $LEVELS["notice"]=  "<span class='label label-default'>NOTE.</span>";
    $LEVELS["unknown"]= "<span class='label label-default'>UNKN.</span>";
    $LEVELS["fatal"]=   "<span class='label label-danger' >ERROR</span>";
    $LEVELS["emerg"]=   "<span class='label label-danger' >FATAL</span>";

    $LEVELS["error"]="<span class='label label-danger'>ERROR</span>";

    $LEVELS["success"]="<span class='label label-primary'>Success</span>";
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead><tr>";
    $html[]="<th class='center'>{date}</th>";
    $html[]="<th class='center'>&nbsp;</th>";
    $html[]="<th>{events}</th>";
    $html[]="</tr></thead><tbody id='nev-rows'>";


    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>$data".json_last_error_msg());
        return false;
    }
    // json_decode() rend NULL quand le daemon ne repond pas: lire ->Status
    // dessus est une erreur fatale en PHP 8.
    if(!is_object($json) || !isset($json->Status) || !$json->Status){
        echo $tpl->div_error(is_object($json) && isset($json->Error)?htmlspecialchars($json->Error):"{unknown_error}");
        return false;
    }
    $logs=(isset($json->Logs) && is_array($json->Logs))?$json->Logs:array();
    foreach ($logs as $line){
        if(trim((string)$line)==""){continue;}
        if($GLOBALS['VERBOSE']){echo "FOUND $line\n";}
        $Main=parseRows($line);
        // $LEVELS etait indexe sans garde: un niveau inconnu emettait un
        // avertissement en PHP 8 et laissait la cellule vide.
        $html[]="<tr>"
            ."<td class='center awc-nowrap awc-mono'>".htmlspecialchars($tpl->time_to_date($Main["TIME"]))."</td>"
            ."<td class='center awc-nowrap'>".($LEVELS[$Main["TYPE"]] ?? htmlspecialchars((string)$Main["TYPE"]))."</td>"
            ."<td>".htmlspecialchars((string)$Main["EVENTS"])."</td>"
            ."</tr>";
    }

    // Le pied donnait colspan='5' a un tableau de TROIS colonnes, et
    // l'initialisation footable ciblait #nginx-event-table alors que le tableau
    // s'appelle #$t: elle ne s'appliquait donc a rien et la pagination restait
    // vide.
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>&nbsp;</span>"
           ."<span class='awc-pg' id='nev-pager'></span></div>";
    $html[]="</div></div>";

    // awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    ."<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>"
	    .awc_pager_js(100,"nev-rows","nev-pager");
	return true;
	
	
}