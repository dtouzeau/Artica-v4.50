<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

    if(isset($_GET["popup"])){popup();exit;}

js();

function js(){

    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog2("{apply_uninstall}","$page?popup=yes",550);

}

function popup(){
    $tpl=new template_admin();
    $config["PROGRESS_FILE"]=PROGRESS_DIR."/speedtests.execute.progress";
    $config["LOG_FILE"]=PROGRESS_DIR."/speedtests.log";
    $config["CMD"]="/speedtests/run";
    $config["TITLE"]="{apply_uninstall}";
    $config["AFTER"]="dialogInstance2.close();LoadAjaxTiny('bandwidth-dashboard','fw.system.status.php?bandwidth=yes');";
    $prgress=base64_encode(serialize($config));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=bandwidth-progress-run')";

    $html[]="<div id='bandwidth-progress-run'></div>";
    $html[]="<script>";
    $html[]=$jsrestart;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);



}