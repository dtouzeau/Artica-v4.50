<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}
if(isset($_GET["confirm-popup"])){confirm_popup();exit;}
if(isset($_GET["confirm-js"])){confirm_js();exit;}

js();

function js(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $tpl->js_error("{STATS_CATEGORIES_LICENSE}");
        return;
    }


    $tpl->js_prompt("{UseCloudArticaCategories} ?",
        "{UseCloudArticaCategories_explain}","fas fa-folders",$page,null,"Loadjs('$page?confirm-js=yes');","none",null);


}

function confirm_js(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $tpl->js_dialog5("{UseCloudArticaCategories}","$page?confirm-popup=yes");

}

function confirm_popup(){
    $t=time();

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/filebeat.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/filebeat.log";

    $ARRAY["CMD"]="filebeat.php?cloud-install=yes";
    $ARRAY["TITLE"]="{UseCloudArticaCategories}";
    $ARRAY["AFTER"]="location.reload(true);";
    $prgress=base64_encode(serialize($ARRAY));
    $jsafter="Loadjs('fw.progress.php?content=$prgress&mainid=$t')";


    $html="<div id='$t'></div><script>$jsafter</script>";
    echo $html;

}

//dialogInstance5.close();