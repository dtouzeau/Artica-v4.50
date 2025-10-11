<?php
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");
include_once(dirname(__FILE__)."/ressources/class.wifidog.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
include_once(dirname(__FILE__)."/ressources/class.ActiveDirectory.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);

$HotSpotTemplateID=99999;
$wifidog_templates=new wifidog_templates($HotSpotTemplateID);
$html=$wifidog_templates->build("");
if(!is_dir("/home/artica/web_templates")){@mkdir("/home/artica/web_templates",0755,true);}
@file_put_contents("/home/artica/web_templates/hotspot.template",$html);
echo "/home/artica/web_templates/hotspot.ERROR_STYLE\n";
@file_put_contents("/home/artica/web_templates/hotspot.ERROR_STYLE",$wifidog_templates->ERROR_STYLE);
$q=new lib_sqlite("/home/squid/hotspot/database.db");
$unix=new unix();


$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `vouchers` (
			`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`created` INTEGER,
			`member` TEXT UNIQUE NOT NULL ,
			`password` TEXT NOT NULL,
			`ttl` INTEGER NOT NULL,
			`expire` INTEGER NOT NULL,
			`enabled` INTEGER NOT NULL DEFAULT '1',
			`bandwidth` INTEGER NOT NULL DEFAULT '0',
			`hotspotKey` TEXT
			) ");

if(!$q->FIELD_EXISTS("vouchers","created")){
    $q->QUERY_SQL("ALTER TABLE vouchers add created INTEGER");
}
if(!$q->FIELD_EXISTS("vouchers","hotspotkey")){
    $q->QUERY_SQL("ALTER TABLE vouchers add hotspotkey TEXT");
}
if(!is_dir("/var/log/hotspot/forms")){
    @mkdir("/var/log/hotspot/forms",0755,true);
}
echo " * * * *  ADDED FORMS * * * *\n";
added_forms();
$css=$wifidog_templates->CSS_99999();
@file_put_contents("/home/artica/web_templates/hotspot.css",$css);

$HotSpotAutoCustomForm=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAutoCustomForm"));

@file_put_contents("/home/artica/web_templates/hotspot.LabelUsername","$wifidog_templates->LabelUsername");
@file_put_contents("/home/artica/web_templates/hotspot.LabelPassword","$wifidog_templates->LabelPassword");
@file_put_contents("/home/artica/web_templates/hotspot.LabelVoucher","$wifidog_templates->LabelVoucher");
@file_put_contents("/home/artica/web_templates/hotspot.SubmitButton","$wifidog_templates->SubmitButton");
@file_put_contents("/home/artica/web_templates/hotspot.LabelEmail",$wifidog_templates->LabelEmail);
@file_put_contents("/home/artica/web_templates/hotspot.DomainAccount","$wifidog_templates->DomainAccount");
@file_put_contents("/home/artica/web_templates/hotspot.backGroundField",$wifidog_templates->backGroundField);
@file_put_contents("/home/artica/web_templates/hotspot.RegisterTitle",$wifidog_templates->RegisterTitle);
@file_put_contents("/home/artica/web_templates/hotspot.RegisterButton",$wifidog_templates->RegisterButton);
@file_put_contents("/home/artica/web_templates/hotspot.ErrorInvalidMail",$wifidog_templates->ErrorInvalidMail);
@file_put_contents("/home/artica/web_templates/hotspot.LoginTitle",$wifidog_templates->LoginTitle);
@file_put_contents("/home/artica/web_templates/hotspot.MainTitle",$wifidog_templates->MainTitle);
@file_put_contents("/home/artica/web_templates/hotspot.SubWelcome",$wifidog_templates->SubWelcome);
@file_put_contents("/home/artica/web_templates/hotspot.REGISTER_MESSAGE_EXPLAIN",$wifidog_templates->REGISTER_MESSAGE_EXPLAIN);
@file_put_contents("/home/artica/web_templates/hotspot.REGISTER_SUBJECT",$wifidog_templates->REGISTER_SUBJECT);
@file_put_contents("/home/artica/web_templates/hotspot.REGISTER_MESSAGE",$wifidog_templates->REGISTER_MESSAGE);
@file_put_contents("/home/artica/web_templates/hotspot.REGISTER_MESSAGE_SUCCESS",$wifidog_templates->REGISTER_MESSAGE_SUCCESS);
@file_put_contents("/home/artica/web_templates/hotspot.REGISTER_MESSAGE_TIMEOUT",$wifidog_templates->REGISTER_MESSAGE_TIMEOUT);


@file_put_contents("/home/artica/web_templates/hotspot.authentication_failed",$wifidog_templates->authentication_failed);

@file_put_contents("/home/artica/web_templates/hotspot.LabelVoucher",$wifidog_templates->LabelVoucher);
@file_put_contents("/home/artica/web_templates/hotspot.SessionExpired",$wifidog_templates->SessionExpired);
@file_put_contents("/home/artica/web_templates/hotspot.VoucherExplain",$wifidog_templates->VoucherExplain);
@file_put_contents("/home/artica/web_templates/hotspot.VoucherDevice",$wifidog_templates->VoucherDevice);
@file_put_contents("/home/artica/web_templates/hotspot.SuccessWelcome",$wifidog_templates->SuccessWelcome);
@file_put_contents("/home/artica/web_templates/hotspot.SuccessTitle",$wifidog_templates->SuccessTitle);
@file_put_contents("/home/artica/web_templates/hotspot.TERMS_EXPLAIN",$wifidog_templates->TERMS_EXPLAIN);
@file_put_contents("/home/artica/web_templates/hotspot.TERMS_CONDITIONS",$wifidog_templates->TERMS_CONDITIONS);
@file_put_contents("/home/artica/web_templates/hotspot.TERMS_TITLE",$wifidog_templates->TERMS_TITLE);
@file_put_contents("/home/artica/web_templates/hotspot.AcceptButton",$wifidog_templates->AcceptButton);
@file_put_contents("/home/artica/web_templates/hotspot.DivLeftBGColor",$wifidog_templates->DivLeftBGColor);
@file_put_contents("/home/artica/web_templates/hotspot.DivLeftColor",$wifidog_templates->DivLeftColor);
@file_put_contents("/home/artica/web_templates/hotspot.WelcomeMessageActiveDirectory","$wifidog_templates->WelcomeMessageActiveDirectory");
@file_put_contents("/home/artica/web_templates/hotspot.ArticaSplashHotSpotRedirectText",$wifidog_templates->ArticaSplashHotSpotRedirectText);
@file_put_contents("/home/artica/web_templates/hotspot.RegisterTitle",$wifidog_templates->RegisterTitle);
echo "/home/artica/web_templates/hotspot.RegisterTitle\n";
if($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
    @file_put_contents("/home/artica/web_templates/hotspot.Lic", 1);
}else{
    @file_put_contents("/home/artica/web_templates/hotspot.Lic",0);
}


@file_put_contents("/home/artica/web_templates/hotspot.loginform",@implode("\n",$wifidog_templates->enterprise_login_form()));

$ActiveDirectoryConnections=array();

@file_put_contents("/home/artica/web_templates/hotspot.SavedTime",time());

if(IsAdConnected()){
    echo "Active Directory (Active)\n";
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
    $ActiveDirectoryConnections=build_default_connection($ActiveDirectoryConnections);
    $CountOf=count($ActiveDirectoryConnections);
    echo "Active Directory Connections Count = $CountOf\n";
}else{
    echo "Active Directory (Inactive/not installed)\n";
}

$f=array();
foreach ($ActiveDirectoryConnections as $index=>$HASH) {
    $name = null;
    if (isset($HASH["NAME"])) {$name = $HASH["NAME"];}
    if(!isset($HASH["LDAP_SSL"])){$HASH["LDAP_SSL"]=0;}

    if(isset($HASH["ADNETIPADDR"])){
        if(IP::isValid($HASH["ADNETIPADDR"])){
            $HASH["LDAP_SERVER"]=$HASH["ADNETIPADDR"];
        }
    }

    if(!isset($HASH["LDAP_SERVER"])){continue;}
    if($HASH["LDAP_SERVER"]==null){continue;}
    if(!isset($HASH["LDAP_PORT"])){continue;}
    if(intval($HASH["LDAP_PORT"])==0){continue;}

    if(!isset($HASH["LDAP_DN"])){continue;}
    if($HASH["LDAP_DN"]==null){continue;}
    if(!isset($HASH["LDAP_PASSWORD"])){continue;}
    if($HASH["LDAP_PASSWORD"]==null){continue;}
    if(!isset($HASH["LDAP_SUFFIX"])){continue;}
    if($HASH["LDAP_SUFFIX"]==null){continue;}



    $host   = $HASH["LDAP_SERVER"];
    $port   = intval($HASH["LDAP_PORT"]);
    $user   = $HASH["LDAP_DN"];
    $ssl   = $HASH["LDAP_SSL"];
    $password = base64_encode($HASH["LDAP_PASSWORD"]);
    if($name==null){$name="$user@$host:$port";}
    $suffix = base64_encode($HASH["LDAP_SUFFIX"]);
    $name=base64_encode($name);
    $f[]="$host|$port|$user|$password|$suffix|$name|$ssl";

}
@file_put_contents("/home/artica/web_templates/hotspot.AdCnx",@implode("\n",$f));
echo "/home/artica/web_templates/hotspot.AdCnx done\n";

$Groups=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotLimitAdGroups"));
$zGps=array();
if(!is_array($Groups)){$Groups=array();}
foreach ($Groups as $DN=>$NONE) {
    if (trim($DN) == null) {
        continue;
    }
    $zGps[] = $DN;
}
@file_put_contents("/home/artica/web_templates/hotspot.AdGps",@implode("\n",$zGps));
$Groups=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotLimitDeniedAdGroups"));
$zGps=array();
if(!is_array($Groups)){$Groups=array();}
foreach ($Groups as $DN=>$NONE) {
    if (trim($DN) == null) {
        continue;
    }
    $zGps[] = $DN;
}
@file_put_contents("/home/artica/web_templates/hotspot.AdGpsDenied",@implode("\n",$zGps));





echo "/home/artica/web_templates/hotspot.AdGps done\n";
echo "Building templates done\n";
$q=new lib_sqlite("/home/squid/hotspot/database.db");
if(!$q->FIELD_EXISTS("sessions","created")){$q->QUERY_SQL("ALTER TABLE sessions ADD created INTEGER");}


$HotSpotTemplateID=999998;
$wifidog_templates=new wifidog_templates($HotSpotTemplateID);
$html=$wifidog_templates->build("");
if(!is_dir("/home/artica/web_templates")){@mkdir("/home/artica/web_templates",0755,true);}
@file_put_contents("/home/artica/web_templates/hotspot.wifi4eu.template",$html);
echo "/home/artica/web_templates/hotspot.ERROR_STYLE\n";
@file_put_contents("/home/artica/web_templates/hotspot.ERROR_STYLE",$wifidog_templates->ERROR_STYLE);

$css=$wifidog_templates->CSS_999998();
@file_put_contents("/home/artica/web_templates/hotspot_wifi4eu.css",$css);
@file_put_contents("/home/artica/web_templates/hotspot_wifi4eu.WIFI4EUTEXTH1","$wifidog_templates->WIFI4EUTEXTH1");
@file_put_contents("/home/artica/web_templates/hotspot.wifi4eu.WIFI4EUTEXTH2","$wifidog_templates->WIFI4EUTEXTH2");
@file_put_contents("/home/artica/web_templates/hotspot.wifi4eu.WIFI4EUTEXTBTN","$wifidog_templates->WIFI4EUTEXTBTN");
@file_put_contents("/home/artica/web_templates/hotspot.wifi4eu.WIFI4UEENABLETERMS","$wifidog_templates->WIFI4UEENABLETERMS");
@file_put_contents("/home/artica/web_templates/hotspot.wifi4eu.WIFI4UETERMSTEXT","$wifidog_templates->WIFI4UETERMSTEXT");
@file_put_contents("/home/artica/web_templates/hotspot.wifi4eu.WIFI4UETERMSCONTENT","$wifidog_templates->WIFI4UETERMSCONTENT");

@file_put_contents("/home/artica/web_templates/hotspot.wifi4eu.WIFI4UEENABLEPRIVACY","$wifidog_templates->WIFI4UEENABLEPRIVACY");
@file_put_contents("/home/artica/web_templates/hotspot.wifi4eu.WIFI4UEPRIVACYTEXT","$wifidog_templates->WIFI4UEPRIVACYTEXT");
@file_put_contents("/home/artica/web_templates/hotspot.wifi4eu.WIFI4UEPRIVACYCONTENT","$wifidog_templates->WIFI4UEPRIVACYCONTENT");
@file_put_contents("/home/artica/web_templates/hotspot.wifi4eu.WIFI4UEERRORTEXT","$wifidog_templates->WIFI4UEERRORTEXT");


function IsAdConnected():bool{
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        echo "\t---\tLicense Error [FALSE]\n";
        return false;
    }
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    echo "\t---\tEnableActiveDirectoryFeature.........:[$EnableActiveDirectoryFeature]\n";
    echo "\t---\tEnableKerbAuth.......................:[$EnableKerbAuth]\n";
    echo "\t---\tLockActiveDirectoryToKerberos........:[$LockActiveDirectoryToKerberos]\n";
    echo "\t---\tHaClusterClient......................:[$HaClusterClient]\n";



    if($HaClusterClient==1){return true;}
    if($LockActiveDirectoryToKerberos==1){return true;}
    if($EnableKerbAuth==1){return true;}
    if($EnableActiveDirectoryFeature==1){return true;}
    return false;
}

function added_forms():bool{

    $q=new lib_sqlite("/home/squid/hotspot/database.db");

    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `forms` (
			`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`label` TEXT,
			`format` TEXT NOT NULL ,
			`mandatory` INTEGER NOT NULL DEFAULT 0,
			`enabled` INTEGER NOT NULL DEFAULT '1',
			 params TEXT NULL,
            `zorder` INTEGER NOT NULL DEFAULT '0'
			) ");

    $results=$q->QUERY_SQL("SELECT * FROM forms WHERE enabled=1 ORDER BY zorder ASC");
    $f=array();
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $label=$ligne["label"];
        $format=$ligne["format"];
        $mandatory=$ligne["mandatory"];
        $params=$ligne["params"];
        $f[]="$ID|$label|$format|$mandatory|$params";


    }

    @file_put_contents("/home/artica/web_templates/hotspot.AddedFields",@implode("\n",$f));
    return true;

}

function build_default_connection($ActiveDirectoryConnections=array()):array{
    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    $active=new ActiveDirectory();

    if($array["LDAP_SERVER"]==null){
        if($array["fullhosname"]<>null){
            $array["LDAP_SERVER"]=$array["fullhosname"];
        }
    }
    if(!isset($array["LDAP_DN"])){
        if(isset($array["WINDOWS_SERVER_ADMIN"])){
            $array["LDAP_DN"]=$array["WINDOWS_SERVER_ADMIN"];
        }
    }
    if(!isset($array["LDAP_PORT"])){$array["LDAP_PORT"]=null;}
    if(!isset($array["LDAP_PASSWORD"])){
        if(isset($array["WINDOWS_SERVER_PASS"])){
            $array["LDAP_PASSWORD"]=$array["WINDOWS_SERVER_PASS"];
        }
    }

    if(!isset($array["LDAP_DN"])){$array["LDAP_DN"]=null;}
    if(!isset($array["LDAP_SUFFIX"])){$array["LDAP_SUFFIX"]=null;}
    if(!isset($array["LDAP_SERVER"])){$array["LDAP_SERVER"]=null;}
    if(!isset($array["LDAP_PORT"])){$array["LDAP_PORT"]=null;}
    if(!isset($array["LDAP_PASSWORD"])){$array["LDAP_PASSWORD"]=null;}
    if(!isset($array["LDAP_SSL"])){$array["LDAP_SSL"]=null;}


    if($array["LDAP_DN"]==null){$array["LDAP_DN"]=$active->ldap_dn_user;}
    if($array["LDAP_SUFFIX"]==null){$array["LDAP_SUFFIX"]=$active->suffix;}
    if($array["LDAP_SERVER"]==null){$array["LDAP_SERVER"]=$active->ldap_host;}
    if($array["LDAP_PORT"]==null){$array["LDAP_PORT"]=$active->ldap_port;}
    if($array["LDAP_PASSWORD"]==null){$array["LDAP_PASSWORD"]=$active->ldap_password;}
    if($array["LDAP_SSL"]==null){$array["LDAP_SSL"]=$active->ldap_ssl;}
    if(preg_match("#^(.+?)@(.+?)@$#",trim($array["LDAP_DN"]),$re)){$array["LDAP_DN"]="$re[1]@$re[2]";}
    $ActiveDirectoryConnections[]=$array;
    return $ActiveDirectoryConnections;
}