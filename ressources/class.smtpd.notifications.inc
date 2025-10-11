<?php
require_once(dirname(__FILE__)."/externals/PHPMailer/PHPMailer.inc");
require_once(dirname(__FILE__)."/externals/PHPMailer/SMTP.inc");
require_once(dirname(__FILE__)."/externals/PHPMailer/Exception.inc");

function smtp_defaults():array{
    if(!function_exists("json_decode")){
        return array();
    }
    $sock=new sockets();
    $data=$sock->REST_API("/system/notification/read");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo json_last_error_msg();
        return array();

    }
    if(!$json->Status){
        echo "Framework return false!\n";
        return array();
    }
    if(!property_exists($json,"Info")){
        return array();
    }

    $ini=new Bs_IniHandler();

    if(!is_null($json->Info)) {
        $IniData = base64_decode($json->Info);
        $ini->loadString($IniData);
    }
    if(!isset($ini->_params["SMTP"]["smtp_server_port"])){$ini->_params["SMTP"]["smtp_server_port"]=25;}
    if(!isset($ini->_params["SMTP"]["smtp_sender"])){$ini->_params["SMTP"]["smtp_sender"]=null;}
    if(!isset($ini->_params["SMTP"]["smtp_server_name"])){$ini->_params["SMTP"]["smtp_server_name"]=null;}
    if(!isset($ini->_params["SMTP"]["smtp_dest"])){$ini->_params["SMTP"]["smtp_dest"]=null;}
    if(!isset($ini->_params["SMTP"]["smtp_auth_user"])){$ini->_params["SMTP"]["smtp_auth_user"]=null;}
    if(!isset($ini->_params["SMTP"]["smtp_auth_passwd"])){$ini->_params["SMTP"]["smtp_auth_passwd"]=null;}
    if(!isset($ini->_params["SMTP"]["tls_enabled"])){$ini->_params["SMTP"]["tls_enabled"]=0;}
    if(!isset($ini->_params["SMTP"]["ssl_enabled"])){$ini->_params["SMTP"]["ssl_enabled"]=0;}

    if($ini->_params["SMTP"]["smtp_server_port"]==null){$ini->_params["SMTP"]["smtp_server_port"]=25;}
    if($ini->_params["SMTP"]["smtp_sender"]==null){$ini->_params["SMTP"]["smtp_sender"]="artica@".php_uname('n');}

    $UfdbguardSMTPNotifs=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbguardSMTPNotifs")));
    if(!is_array($UfdbguardSMTPNotifs)){$UfdbguardSMTPNotifs=array();}
    if(!isset($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}
    if(!is_numeric($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}
    if(!isset($UfdbguardSMTPNotifs["smtp_server_name"])){$UfdbguardSMTPNotifs["smtp_server_name"]=$ini->_params["SMTP"]["smtp_server_name"];}
    if(!isset($UfdbguardSMTPNotifs["smtp_server_port"])){$UfdbguardSMTPNotifs["smtp_server_port"]=$ini->_params["SMTP"]["smtp_server_port"];}
    if(!isset($UfdbguardSMTPNotifs["smtp_sender"])){$UfdbguardSMTPNotifs["smtp_sender"]=$ini->_params["SMTP"]["smtp_sender"];}
    if(!isset($UfdbguardSMTPNotifs["smtp_dest"])){$UfdbguardSMTPNotifs["smtp_dest"]=$ini->_params["SMTP"]["smtp_dest"];}
    if(!isset($UfdbguardSMTPNotifs["smtp_auth_user"])){$UfdbguardSMTPNotifs["smtp_auth_user"]=$ini->_params["SMTP"]["smtp_auth_user"];}
    if(!isset($UfdbguardSMTPNotifs["smtp_auth_passwd"])){$UfdbguardSMTPNotifs["smtp_auth_passwd"]=$ini->_params["SMTP"]["smtp_auth_passwd"];}
    if(!isset($UfdbguardSMTPNotifs["tls_enabled"])){$UfdbguardSMTPNotifs["tls_enabled"]=$ini->_params["SMTP"]["tls_enabled"];}
    if(!isset($UfdbguardSMTPNotifs["ssl_enabled"])){$UfdbguardSMTPNotifs["ssl_enabled"]=$ini->_params["SMTP"]["ssl_enabled"];}
    if(!is_numeric($UfdbguardSMTPNotifs["smtp_server_port"])){$UfdbguardSMTPNotifs["smtp_server_port"]=25;}
    if(!isset($UfdbguardSMTPNotifs["use_login"])){$UfdbguardSMTPNotifs["use_login"]=0;}
    if(!isset($UfdbguardSMTPNotifs["smtp2go"])){$UfdbguardSMTPNotifs["smtp2go"]=0;}
    if(!isset($UfdbguardSMTPNotifs["webapi"])){$UfdbguardSMTPNotifs["webapi"]="";}
    if(!isset($UfdbguardSMTPNotifs["gmailjson"])){$UfdbguardSMTPNotifs["gmailjson"]="";}
    if(!isset($UfdbguardSMTPNotifs["smtp_proxy_outgoing"])){$UfdbguardSMTPNotifs["smtp_proxy_outgoing"]="";}
    if(!isset($UfdbguardSMTPNotifs["smtp_proxy_debug"])){
        $UfdbguardSMTPNotifs["smtp_proxy_debug"]=0;
    }
    if(!isset($UfdbguardSMTPNotifs["warning_events"])){
        $UfdbguardSMTPNotifs["warning_events"]=0;
    }




    if(strlen($UfdbguardSMTPNotifs["smtp_sender"])<3){
        $UfdbguardSMTPNotifs["smtp_sender"]="root@localhost.localdomain";
    }

    return $UfdbguardSMTPNotifs;



}

function artica_smtpd_event($text,$recipients=null){
    $to=null;
    if($recipients<>null){$to=" to=\"[$recipients]\"";}
    $logfile="/var/log/artica-postfix/artica-smtpd.log";
    $date=date("Y-m-d H:i:s");
    $line="time=\"$date\" level=info msg=\"$text\"$to";
    $f = @fopen($logfile, 'a');
    @fwrite($f, "$line\n");
    @fclose($f);

}