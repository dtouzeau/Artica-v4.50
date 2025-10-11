<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
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
    $html[]=$tpl->_ENGINE_parse_body("
			<table id='$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{events}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;


    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>$data".json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }
    $td1="style='width:1%' nowrap";
    foreach ($json->Logs as $line){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $text_class="";
        if(trim($line)==null){continue;}
        if($GLOBALS['VERBOSE']){echo "FOUND $line\n";}
       $Main=parseRows($line);
        $datetext=$tpl->time_to_date($Main["TIME"]);
        $label=$LEVELS[$Main["TYPE"]];
        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td class=\"$text_class\" $td1>$datetext</td>";
        $html[]="<td class=\"$text_class\" $td1>$label</td>";
        $html[]="<td class=\"$text_class\" style='width:99%'>{$Main["EVENTS"]}</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='5'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table><div><i></i></div>";
	$html[]="
	<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('#nginx-event-table').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	return true;
	
	
}