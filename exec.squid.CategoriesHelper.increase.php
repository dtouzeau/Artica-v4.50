<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
$GLOBALS["VERBOSE"]=false;
$GLOBALS["makeQueryForce"]=false;
$GLOBALS["FORCE"]=false;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");




xstart();



function xstart(){
	$cacheTime="/etc/artica-postfix/pids/".basename(__FILE__);
	$unix=new unix();
	
	$TimeExec=$unix->file_time_min($cacheTime);
	if($TimeExec<5){exit();}
	@unlink($cacheTime);
	@file_put_contents($cacheTime, time());
	
	$CategoriesHelperChildrenMax=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesHelperChildrenMax"));
	$CategoriesHelperChildren_NEW=$CategoriesHelperChildrenMax+5;
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CategoriesHelperChildrenMax", $CategoriesHelperChildren_NEW);
    squid_admin_mysql(0, "Increase Categories Helper from $CategoriesHelperChildrenMax to $CategoriesHelperChildrenMax [action=reload]", null,__FILE__,__LINE__);
    system("/usr/sbin/artica-phpfpm-service -reload-proxy");
}