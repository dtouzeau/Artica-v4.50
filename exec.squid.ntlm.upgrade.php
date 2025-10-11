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
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$data=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams");
	$SquidClientParams=unserialize(base64_decode($data));
	$auth_param_ntlm_children=intval($SquidClientParams["auth_param_ntlm_children"]);
	$auth_param_ntlm_children_new=$auth_param_ntlm_children+5;
	
	if($auth_param_ntlm_children_new>300){
		squid_admin_mysql(0, "NTLM: FATAL MAX automatic value reached for NTLM children [action=reload]",null,__FILE__,__LINE__);
		$squidbin=$unix->LOCATE_SQUID_BIN();
		system("/usr/sbin/artica-phpfpm-service -reload-proxy");
		return;
		
	}
	
	$SquidClientParams["auth_param_ntlm_children"]=$auth_param_ntlm_children_new;
	$content=@file_get_contents("/etc/artica-postfix/pids/Too.many.queued.ntlmauthenticator.requests");
	squid_admin_mysql(0, "NTLM: FATAL Too few ntlmauthenticator adding 5 more from $auth_param_ntlm_children to $auth_param_ntlm_children_new",$content,__FILE__,__LINE__ );
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidClientParams", base64_encode(serialize($SquidClientParams)));
	system("$php /usr/share/artica-postfix/exec.squid.global.access.php --auth 2>&1");
	
}