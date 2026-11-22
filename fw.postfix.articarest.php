<?php

include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"] = new sockets();


if(isset($_GET["smtpd-client-restrictions"])){smtpd_client_restrictions();}

// $GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/smtpd/restrictions/0");

function smtpd_client_restrictions():bool{
    $tpl=new template_admin();
    if(!isset($_GET["instance-id"])){
        $_GET["instance-id"]=0;
    }
    $instanceid=intval( $_GET["instance-id"]);
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/smtpd/restrictions/$instanceid");



    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        $text="{error}<br>".json_last_error_msg()."<br>{$GLOBALS["CLASS_SOCKETS"]}->mysql_error";
        return $tpl->js_error($text);

    }

    if (!$json->Status){
        $text="Status = false <br>{$GLOBALS["CLASS_SOCKETS"]}->mysql_error";
        return $tpl->js_error($text);

    }
    $tpl->js_executed_background("{success}");
    return admin_tracks("Apply smtpd_client_restrictions compilation for SMTP instance id $instanceid");
}