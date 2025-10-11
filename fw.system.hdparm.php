<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["none"])){exit;}
if(isset($_GET["perform"])){perform_js();exit;}
if(isset($_GET["perform-popup"])){perform_popup();exit;}
if(isset($_GET["results"])){perform_results();exit;}
js();


function js(){
    $dev=$_GET["dev"];
    $devenco=urlencode($dev);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog_confirm_action("{hdparm_explain}","none","none","Loadjs('$page?perform=$devenco')");
}
function perform_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $dev=$_GET["perform"];
    $devenco=urlencode($dev);
    $tpl->js_dialog2("{disk_performance} $dev","$page?perform-popup=$devenco",650);
}
function perform_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $dev=$_GET["perform-popup"];
    $devenco=urlencode($dev);
    $md5=md5($devenco);
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/hdparm.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/hdparm.progress.txt";
    $ARRAY["CMD"]="hd.php?hdparm=$devenco";
    $ARRAY["TITLE"]="{disk_performance}";
    $ARRAY["AFTER"]="LoadAjax('$md5-results','$page?results=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $rescan_hd="Loadjs('fw.progress.php?content=$prgress&mainid=$md5')";

    $html[]="<div id='$md5'></div>";
    $html[]="<div id='$md5-results'></div>";
    $html[]="<script>$rescan_hd</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function perform_results(){
    $tpl=new template_admin();
    $HDPARM_RESULTS=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HDPARM_RESULTS"));
    $Value=intval($HDPARM_RESULTS["VALUE"]);
    $unit=$HDPARM_RESULTS["UNIT"];

    $results=$tpl->widget_rouge("{disk_performance}<br> {very_low}","{$HDPARM_RESULTS["VALUE"]} $unit");

    if($Value>60){
        $results=$tpl->widget_jaune("{disk_performance}<br> {poor} ({virtual_machine})","{$HDPARM_RESULTS["VALUE"]} $unit");
    }
    if($Value>80){
        $results=$tpl->widget_vert("{disk_performance}<br> {medium} ({virtual_machine} SSD)","{$HDPARM_RESULTS["VALUE"]} $unit");
    }
    if($Value>129){
        $results=$tpl->widget_vert("{disk_performance}<br> {medium} (SD Cards)","{$HDPARM_RESULTS["VALUE"]} $unit");
    }
    if($Value>160){
        $results=$tpl->widget_vert("{disk_performance}<br> {good} (SD Cards)","{$HDPARM_RESULTS["VALUE"]} $unit");
    }

    if($Value>160){
        $results=$tpl->widget_vert("{disk_performance}<br> {good}++ (SATA 6Gb/s)","{$HDPARM_RESULTS["VALUE"]} $unit");
    }
    if($Value>300){
        $results=$tpl->widget_vert("{disk_performance}<br> {good}++ (SAS 10Gb/s / SSD)","{$HDPARM_RESULTS["VALUE"]} $unit");
    }
    if($Value>1000){
        $results=$tpl->widget_vert("{disk_performance}<br> {good}++ (SSD!!)","{$HDPARM_RESULTS["VALUE"]} $unit");
    }
    echo "<center>";
    echo $tpl->_ENGINE_parse_body($results);
    echo "</center>";
}