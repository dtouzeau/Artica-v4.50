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

@file_put_contents("/home/artica/web_templates/hotspot.SavedTime",time());

// hotspot.AdCnx (directory connections of the HotSpot / proxy LDAP
// authenticators) is written by the daemon, without the bind password this
// script used to put in it: GET /activedirectory/connections/hotspot.
$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/activedirectory/connections/hotspot"));
if($json && property_exists($json,"Status") && $json->Status){
    echo "/home/artica/web_templates/hotspot.AdCnx: ".intval($json->Count)." connection(s)\n";
}else{
    echo "/home/artica/web_templates/hotspot.AdCnx: ".(($json && property_exists($json,"Error"))?$json->Error:"daemon unreachable")."\n";
}

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
