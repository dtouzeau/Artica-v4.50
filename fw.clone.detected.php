<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
$GLOBALS["CLASS_SOCKETS"]       = new sockets();
$users                          = new usersMenus();
$tpl                            = new template_admin();
if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();exit();}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["results"])){results();exit;}
js();


function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog6("{system}::{clone_detected} ", "$page?popup=yes",650);
}

function results(){
    $tpl=new template_admin();
    $GRUBPC_DEVICE_ERROR=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("GRUBPC_DEVICE_ERROR");

    if($GRUBPC_DEVICE_ERROR=="DISK"){
        $html[]=$tpl->div_error("{GRUBPC_DEVICE_ERROR}");
        $html[]="<center style='margin:20px'>";
        $html[]=$tpl->button_autnonome("wiki.articatech.com", "s_PopUpFull('https://wiki.articatech.com/maintenance/troubleshooting/clone-detected','1024','900');", "fas fa-file-code","AsSystemAdministrator",335);
        $html[]="</center>";
    }else{
        $html[]=$tpl->div_explain("{system_safe}");
    }

    echo $tpl->_ENGINE_parse_body($html);

}

function popup(){
    $page=CurrentPageName();
    $t=time();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/clone.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/clone.log";
    $ARRAY["CMD"]="hd.php?clonage=yes";
    $ARRAY["TITLE"]="{system} {checking} {clone_detected}";
    $ARRAY["AFTER"]="LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');LoadAjax('progress-clone-$t','$page?results=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-clone-$t')";
    $html="<div id='progress-clone-$t'></div><script>$jsrestart</script>";
    echo $html;
}
