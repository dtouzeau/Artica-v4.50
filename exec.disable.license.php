<?php
define("syslogSuff",base64_decode("QVJUSUNBX0xJQ0VOU0U="));
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.maincf.multi.inc');
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}



$GLOBALS["CLASS_UNIX"]=new unix();
$php=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
$CORP_LICENSE=$GLOBALS["CLASS_UNIX"]->CORP_LICENSE();
if($CORP_LICENSE){echo "License Active....\n";die();}

$GLOBALS["CLASS_UNIX"]->ToSyslog("License info: License failed: disable all services....",false,syslogSuff);
$UfdbcatEnableArticaDB=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbcatEnableArticaDB"));
$EnableOpenLDAPRestFul=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAPRestFul"));
$SQUIDRESTFulEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDRESTFulEnabled"));
$EnableRESTFulSystem=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRESTFulSystem"));
$EnableCategoriesRESTFul=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCategoriesRESTFul"));
$EnableMilterGreylistExternalDB=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMilterGreylistExternalDB"));
$EnablePDNSRESTFul=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNSRESTFul"));
$EnableLocalUfdbCatService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));
$EnableIPSECService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("STRONGSWAN_INSTALLED"));
$APP_KEEPALIVED_ENABLE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE"));
$APP_KEEPALIVED_ENABLE_SLAVE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE"));
$GLOBALS["CLASS_SOCKETS"]->SET_INFO("AsElasticClient",0);


if($EnableLocalUfdbCatService==1){
    squid_admin_mysql(0,"Disable Categories service, License error",null,__FILE__,__LINE__);
    shell_exec("/usr/sbin/artica-phpfpm-service -install-dncatz");
}
if($EnableIPSECService==1){
    squid_admin_mysql(0,"Disable IPSEC service, License error",null,__FILE__,__LINE__);
    shell_exec("$php /usr/share/artica-postfix/exec.strongswan.disable.php");
}
if($APP_KEEPALIVED_ENABLE==1){
    squid_admin_mysql(0,"Disable KEEPALIVED Master, License error",null,__FILE__,__LINE__);
    shell_exec("$php /usr/share/artica-postfix/exec.keepalived.php --disable");
}

if($APP_KEEPALIVED_ENABLE_SLAVE==1){
    squid_admin_mysql(0,"Disable KEEPALIVED Slave, License error",null,__FILE__,__LINE__);
    shell_exec("$php /usr/share/artica-postfix/exec.keepalived.php --disable-secondary_node");
}

if($UfdbcatEnableArticaDB==1) {
    squid_admin_mysql(0,"Disable Artica Database, License error",null,__FILE__,__LINE__);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UfdbcatEnableArticaDB", 0);
}
if($EnableOpenLDAPRestFul==1) {
    squid_admin_mysql(0,"Disable RESTFULL For OpenLDAP, License error",null,__FILE__,__LINE__);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableOpenLDAPRestFul", 0);
}
if($SQUIDRESTFulEnabled==1) {
    squid_admin_mysql(0,"Disable RESTFULL For Proxy, License error",null,__FILE__,__LINE__);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUIDRESTFulEnabled", 0);
}
if($EnableRESTFulSystem==1) {
    squid_admin_mysql(0,"Disable RESTFULL For System, License error",null,__FILE__,__LINE__);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableRESTFulSystem", 0);
}
if($EnableCategoriesRESTFul==1) {
    squid_admin_mysql(0,"Disable RESTFULL For Categories, License error",null,__FILE__,__LINE__);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableCategoriesRESTFul", 0);
}
if($EnableMilterGreylistExternalDB==1) {
    squid_admin_mysql(0,"Disable using Artica Reputation server, License error",null,__FILE__,__LINE__);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableMilterGreylistExternalDB", 0);
}
if($EnablePDNSRESTFul==1) {
    squid_admin_mysql(0,"Disable RESTFULL For PowerDNS, License error",null,__FILE__,__LINE__);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnablePDNSRESTFul", 0);
}


if(is_file("/etc/init.d/filebeat")){
    squid_admin_mysql(0,"Uninstall ElasticSearch client, License error",null,__FILE__,__LINE__);
    shell_exec("$php /usr/share/artica-postfix/exec.filebeat.php --uninstall");
}

if(is_file("/etc/init.d/proxy-pac")){
    squid_admin_mysql(0, "Uninstall Proxy PAC Web server (No license )",null,__FILE__,__LINE__);
    shell_exec("/usr/sbin/artica-phpfpm-service -uninstall-proxypac");
    return;
}



