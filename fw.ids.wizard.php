<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["NetDataListenPort"])){Save();exit;}
if(isset($_GET["status"])){Status();exit;}
if(isset($_GET["ndpid-flat-config"])){flat_config();exit;}
if(isset($_GET["ndpi-top-status"])){Status_top();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $suricata_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SURICATA_VERSION");

    $html= $tpl->page_header("{IDS} v$suricata_version",ico_sensor,"{about_ids}","$page?table=yes","ids-wizard","progress-suricata-restart",false,"table-loader-suricata");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{IDS}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;


}


function table(){
    $page=CurrentPageName();
	$tpl=new template_admin();


    $html[]="<p style='text-align:left;font-size:16px'>{suricata_market_explain}</p>";

    $after= "document.location.href='/ids';";
    $jsinstall=$tpl->framework_buildjs("/suricata/install",
        "suricata.progress","suricata.progress.txt",
        "progress-suricata-restart",$after);

    $html[]="<div style='margin:30px;text-align:right'>";
    $html[]=$tpl->button_autnonome("{install}",$jsinstall,ico_cd,"AsFirewallManager",350,"btn-primary",80);
    $html[]="</div>";


    $suricata_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SURICATA_VERSION");

    $topbuttons=array();

    $TINY_ARRAY["TITLE"]="{IDS} v$suricata_version";
    $TINY_ARRAY["ICO"]=ico_sensor;
    $TINY_ARRAY["EXPL"]="{about_ids}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>";
    $html[]="LoadAjaxSilent('ndpid-flat-config','$page?ndpid-flat-config=yes');";
    $html[]=$jstiny;
    $html[]="</script>";
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}