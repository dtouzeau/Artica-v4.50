<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["posix_getuid"]=0;
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.maincf.multi.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__).  "/ressources/smtp/class.smtp.loader.inc");
include_once(dirname(__FILE__).'/ressources/class.mime.parser.inc');
include_once(dirname(__FILE__).'/ressources/class.rfc822.addresses.inc');
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;echo "VERBOSED !!! \n";}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if($argv[1]=="--send"){SendTest($argv[2]);exit();}
// http://forum.artica.fr/viewtopic.php?f=25&t=5612

function SendTest($Key){
	$GLOBALS["WRITETOFILE"]=dirname(__FILE__)."/ressources/logs/$Key.log";
	$sock=new sockets();
	$unix=new unix();
	$datas=unserialize(base64_decode($sock->GET_INFO($Key)));
	$listen_addr=null;
	$recipient=$datas["smtp_dest"];
	$sender=$datas["smtp_sender"];
	smtp::events("Resolving From $sender to: $recipient",__FUNCTION__,__FILE__,__LINE__);
	if(preg_match("#(.+?)@(.+)#", $recipient,$re)){$domainname=$re[2];}

	if(!is_numeric($datas["smtp_auth"])){$datas["smtp_auth"]=0;}
	$TargetHostname=null;
	$servername=$datas["servername"];
	$BinDTO="127.0.0.1";
	
	if($servername<>"master"){
		$instance=$servername;
		$main=new maincf_multi($servername);
		$listen_addr=$main->ip_addr;
		$BinDTO=$listen_addr;
	}else{
		$instance=$unix->hostname_g();
	}
	
	$smtp=new smtp();
	$NOresolvMX=false;
	if($datas["smtp_auth"]==1){	$TargetHostname=$datas["relay"];}
	if($datas["smtp_local"]==1){
		$TargetHostname=inet_interfaces();
		if(preg_match("#all#is", $TargetHostname)){$TargetHostname="127.0.0.1";}
		smtp::events("Local, instance $servername: Sock to `$TargetHostname`",__FUNCTION__,__FILE__,__LINE__);
		if($servername<>"master"){
			smtp::events("Local, instance $servername: changed to inet_interfaces()::$TargetHostname",__FUNCTION__,__FILE__,__LINE__);
			$TargetHostname=$listen_addr;
		}
	}
	
	if($TargetHostname==null){
		$TargetHostname=$smtp->ResolveMXDomain($domainname);
		smtp::events("Resolving $domainname = `$TargetHostname` bind address: $BinDTO",__FUNCTION__,__FILE__,__LINE__);
	}
	
	$params["helo"]=$instance;
	$params["bindto"]=$BinDTO;
	$params["debug"]=true;
	
	smtp::events("smtp_auth: {$datas["smtp_auth"]}, user:{$params["user"]},relay:{$datas["relay"]} ",__FUNCTION__,__FILE__,__LINE__);
	
	smtp::events("Me: HELO: $instance",__FUNCTION__,__FILE__,__LINE__);
	
	if($datas["smtp_auth"]==1){
		$params["auth"]=true;
		$params["user"]=$datas["smtp_auth_user"];
		$params["pass"]=$datas["smtp_auth_passwd"];
		if(trim($datas["relay"])==null){if($TargetHostname<>null){$datas["relay"]=$TargetHostname;}}
		$TargetHostname=$datas["relay"];
		
	}	
	$params["host"]=$TargetHostname;
	if(!$smtp->connect($params)){
		smtp::events("Error $smtp->error_number: Could not connect to `$TargetHostname` $smtp->error_text",__FUNCTION__,__FILE__,__LINE__);
		return;
	}	
	
	$boundary = md5(uniqid(microtime(), TRUE));
	
$body[]="Return-Path: <$sender>";
$body[]="X-Original-To: $recipient";
$body[]="Date: ". date("D, d M Y H:i:s"). " +0100 (CET)";
$body[]="From: $sender (Mail Delivery System)";
$body[]="Subject: Test Message";
$body[]="To: $recipient";

$body[]="";
$body[]="";
$body[]="This is the mail system at host $instance.";
$body[]="";
$body[]="I'm glade to inform you that your message is";
$body[]=" delivered to you...";
$body[]="";
$body[]="For further assistance, please send mail to postmaster.";
$body[]="";
$body[]="If you do so, please include this problem report. You can";
$body[]="delete your own text from the attached returned message.";
$body[]="";
$body[]="                   The mail system";
$body[]="";
$body[]="";	
$body[]="";
$finalbody=@implode("\r\n", $body);

	if(!$smtp->send(array("from"=>$sender,"recipients"=>$recipient,"body"=>$finalbody,"headers"=>null))){
			smtp::events("Error $smtp->error_number: Could not send to `$TargetHostname` $smtp->error_text",__FUNCTION__,__FILE__,__LINE__);
			$smtp->quit();
			return;
	}
	
	smtp::events("Success sending message trough [{$TargetHostname}:25]",__FUNCTION__,__FILE__,__LINE__);
	$smtp->quit();
	smtp::events("Test message Success From=<$sender> to=<$recipient> ",__FUNCTION__,__FILE__,__LINE__);
	chmod($GLOBALS["WRITETOFILE"], 0775);
}

function inet_interfaces(){
	$f=file("/etc/postfix/main.cf");
	while (list ($key, $line) = each ($f) ){
		$line=str_replace("\r\n", "", $line);
		$line=str_replace("\r", "", $line);
		$line=str_replace("\n", "", $line);
		if(preg_match("#^inet_interfaces.*?=(.*)#", $line,$re)){
			$re[1]=trim($re[1]);
			if($GLOBALS["VERBOSE"]){echo "F:$line -> `{$re[1]}`\n";}
			$inet_interfaces=trim($re[1]);
			$inet_interfaces=str_replace("\r\n", "", $inet_interfaces);
			$inet_interfaces=str_replace("\r", "", $inet_interfaces);
			$inet_interfaces=str_replace("\n", "", $inet_interfaces);			
			
			
			if(strpos($inet_interfaces, ",")>0){
				$tr=explode(",",$inet_interfaces);
				if(trim($tr[0])=="all"){$tr[0]="127.0.0.1";}
				if($GLOBALS["VERBOSE"]){echo "F:$line -> `{$tr[0]}`\n";}
				return $tr[0];
			}
			
			if(strpos($inet_interfaces, " ")>0){
				$tr=explode(" ",$inet_interfaces);
				if(trim($tr[0])=="all"){$tr[0]="127.0.0.1";}
				
				if($GLOBALS["VERBOSE"]){echo "F:$line -> `{$tr[0]}`\n";}
				return $tr[0];
			}
			if($GLOBALS["VERBOSE"]){echo "F:$line -> `{$re[1]}`\n";}
			return $re[1];
			
		}
	}
	
}

