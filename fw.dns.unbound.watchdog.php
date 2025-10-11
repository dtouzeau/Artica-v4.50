<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["EnableUnBoundWatchdog"])){Save();exit;}


page();



function page(){


    $html[]="<h1 style='margin-top:10px'>{watchdog}</h1>";

    $title="{dns_service}: {watchdog}";
    $EnableUnBoundWatchdog=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnBoundWatchdog");
    $UnBoundWatchdogIPsTxt=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundWatchdogIPs");
    if($UnBoundWatchdogIPsTxt==null){
        $UnBoundWatchdogIPsTxt="crawl-66-249-66-*.googlebot.com,crawl-66-249-65-*.googlebot.com";
    }

    $tpl=new template_admin();
   $form[]=$tpl->field_checkbox("EnableUnBoundWatchdog","{ENABLE_VIRTUALBOX_WATCHDOG}",$EnableUnBoundWatchdog);
    $form[]=$tpl->field_text("UnBoundWatchdogIPs","{remote_hosts}",$UnBoundWatchdogIPsTxt);

    if(isset($_GET["dnsdist"])){
        $title = "{APP_DNSDIST}: {watchdog}";
        $TINY_ARRAY["TITLE"] =$title;
        $TINY_ARRAY["ICO"] = "fa-solid fa-shield-dog";
        $TINY_ARRAY["EXPL"] = "{APP_DNSDIST_EXPLAIN2}";
        $TINY_ARRAY["BUTTONS"] = null;
        $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";
        $title=null;
    }else{

        $TINY_ARRAY["TITLE"] ="{watchdog}";
        $TINY_ARRAY["ICO"] = "fa-solid fa-shield-dog";
        $TINY_ARRAY["EXPL"] = '...';
        $TINY_ARRAY["BUTTONS"] = null;
        $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";
    }

    echo $tpl->form_outside($title,$form,null,"{apply}","Loadjs('fw.dns.unbound.restart.php');","AsDnsAdministrator");
    echo "<script>$jstiny</script>";


}


function Save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();

}