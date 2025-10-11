<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.smtpd.notifications.inc");
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["restart-via-api"])){restart_via_api_js();exit;}
if(isset($_GET["restart-via-api-1"])){restart_via_api_1();exit;}
if(isset($_GET["restart-via-api-2"])){restart_via_api_2();exit;}

restart_via_api_js();
function restart_via_api_js():bool{
    $Config=$_GET["restart-via-api"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog_modal("{restarting_service}","$page?restart-via-api-1=$Config");
}
function restart_via_api_1():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $Config=$_GET["restart-via-api-1"];
    $html[]="<H1>{restarting}: {please_wait}</H1>";
    $html[]="<div id='restart_via_api_1' style='margin:30px'></div>";
    $html[]="<script>";
    $html[]="LoadAjax('restart_via_api_1','$page?restart-via-api-2=$Config');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function restart_via_api_2():bool{
    $Config=$_GET["restart-via-api-2"];
    $MAIN=unserialize(base64_decode($Config));
    $page=CurrentPageName();
    $tpl=new template_admin();
    $endpoint= $MAIN["ENDPOINT"];
    $after=$MAIN["AFTER"];

    $sock=new sockets();
    $data=$sock->REST_API($endpoint);

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        $html[]=$tpl->widget_rouge("{error}",json_last_error_msg());
        $html[]="<div style='text-align:right'>".$tpl->button_autnonome("{close}","DialogModal.close();",ico_lock)."</div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;

    }

    if (!$json->Status) {
        $html[]=$tpl->widget_rouge("{error}",$sock->mysql_error);
        $html[]="<div style='text-align:right'>".$tpl->button_autnonome("{close}","DialogModal.close();",ico_lock)."</div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    $tpl=new template_admin();
    $html[]="<script>";
    if(strlen($after)>3){
        $html[]=$after;
    }
    $html[]="DialogModal.close();";
    $html[]="</script>";
echo $tpl->_ENGINE_parse_body($html);
return true;
}