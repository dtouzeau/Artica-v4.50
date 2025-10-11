<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["pulsereverse-status-left"])){pulse_reverse_status_left();exit;}
page();
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_VERSION");
    $html=$tpl->page_header("PulseReverse v$version",
        "fas fa-code-branch",
        "{APP_PULSE_REVERSE_EXPLAIN}",
        "$page?table=yes",
        "pulsereverse-status",
        "progress-pulsereverse-restart");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("PulseReverse v$version",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function top_buttons():array{
    $tpl=new template_admin();

    $topbuttons[]=array("Loadjs('fw.hacluster.backends.php?backend-js=0');",
        ico_plus,"{new_backend}");

    $jsrestart=$tpl->framework_buildjs("/pulsereverse/restart",
        "pulsereverse.progress",
        "pulsereverse.progress.txt",
        "progress-pulsereverse-restart"
    );

    $jsreload=$tpl->framework_buildjs("/pulsereverse/reconfigure",
        "pulsereverse.progress",
        "pulsereverse.progress.txt",
        "progress-pulsereverse-restart"

    );

    $jsstop=$tpl->framework_buildjs("/hacluster/server/stop",
        "hacluster-stop.progress",
        "hacluster.progress.txt",
        "progress-hacluster-restart"

    );

    $jsstart=$tpl->framework_buildjs("/hacluster/server/start",
        "hacluster-stop.progress",
        "hacluster.progress.txt",
        "progress-hacluster-restart"

    );
    $topbuttons[]=array($jsrestart, ico_refresh,"{restart}");
    $topbuttons[]=array($jsreload,ico_retweet,"{reload}");
    //$topbuttons[]=array($jsstop, ico_stop,"{stop_service}");
    //$topbuttons[]=array($jsstart, ico_run,"{start_service}");

    return $topbuttons;
}
function pulse_reverse_status_left():bool{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/pulsereverse/status"));
    $PULSEREVERSE_CONFIG_FAILED=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PULSEREVERSE_CONFIG_FAILED");

    if(strlen($PULSEREVERSE_CONFIG_FAILED)>5){
        $html[]=$tpl->div_error(base64_decode($PULSEREVERSE_CONFIG_FAILED));
    }

    $jsrestart=$tpl->framework_buildjs("/pulsereverse/restart",
        "pulsereverse.progress",
        "pulsereverse.progress.txt",
        "progress-pulsereverse-restart"
    );

    if(!$json->Status){
        $html[]=$tpl->widget_rouge($json->Error,"{error}");
    }else{
        $ini=new Bs_IniHandler();
        $ini->loadString($json->Info);
        $html[]=$tpl->SERVICE_STATUS($ini, "APP_PULSE_REVERSE",$jsrestart);
    }
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

    return true;
}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $jsRefres=$tpl->RefreshInterval_js("pulsereverse-table-status-left",$page,"pulsereverse-status-left=yes");

    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_VERSION");
    $TINY_ARRAY["TITLE"]="PulseReverse v$version";
    $TINY_ARRAY["ICO"]="fas fa-code-branch";
    $TINY_ARRAY["EXPL"]="{APP_PULSE_REVERSE_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons(top_buttons());


    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:350px'>";
    $html[]="<div id='pulsereverse-table-status-left'></div>";
    $html[]="</td>";
    $html[]="<td style='vertical-align:top;width:99%;padding-left:10px'>";
    $html[]="<div id='pulsereverse-table-status-top'></div>";
    $html[]="<div id='pulsereverse-table-status-center'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]=$jsRefres;
   // $html[]="LoadAjaxSilent('pulsereverse-table-status-center','$page?hacluster-table-status-center=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);


}