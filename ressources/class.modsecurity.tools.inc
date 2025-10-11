<?php

define('sModSecurityAction', array("DEFAULT"=>"{default}",
    "auditlog,pass"=>"{alert_and_pass}",
    "auditlog,deny,status:405"=>"{alert_and_block}"));



function GetModSecurityAction($serviceid):string{
    $sock=new socksngix($serviceid);
    $ModSecurityDefaultAction=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityAction");
    if($ModSecurityDefaultAction==null){$ModSecurityDefaultAction="auditlog,pass";}
    $ModSecurityAction=trim($sock->GET_INFO("ModSecurityAction"));
    if($ModSecurityAction==null){$ModSecurityAction=$ModSecurityDefaultAction;}
    return $ModSecurityAction;
}




