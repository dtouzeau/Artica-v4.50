<?php
$GLOBALS["VERBOSE"]=false;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.contacts.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

if($argv[1]=="--white"){imapopen_White($argv[2]);exit();}
if($argv[1]=="--black"){imapopen_black($argv[2]);exit();}
start();

function start(){
	$unix=new unix();

	if(!$GLOBALS["VERBOSE"]){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}
	}
	$ldap=new clladp();
	$pattern="(&(objectclass=userAccount))";
	$attr=array();
	$sr =@ldap_search($ldap->ldap_connection,"dc=organizations,$ldap->suffix",$pattern,$attr);
	$hash=ldap_get_entries($ldap->ldap_connection,$sr);
	$unix=new unix();
	
	$users_array=array();
	
	if(!is_array($hash)){
		blackwhite_admin_mysql(0,"Unable to obtain users from LDAP server",$ldap->ldap_last_error,__FILE__,__LINE__);
		return;
		
	}	
	
	for($i=0;$i<$hash["count"];$i++){
		$usersArray[$hash[$i]["uid"][0]]=true;
	
	}
	
	while (list ($uid, $none) = each ($usersArray)){
		imapopen_White($uid);
		imapopen_black($uid);
	}
	
	
}





function imapopen_White($uid){
	$SentMailBox=array();
	$t=time();
	$F=array();
	$mailbox="{127.0.0.1:143/imap}";
	$ct=new user($uid);
	$imapLink =imap_open("{$mailbox}INBOX",$uid,$ct->password);
	if(! $imapLink ){
		blackwhite_admin_mysql(0,"$uid: $mailbox connection failed",imap_last_error(),__FILE__,__LINE__);
		return;
	}	
	
	$list = imap_list($imapLink, $mailbox, "*");
	if (is_array($list)) {
		foreach ($list as $val) {
			
			$folder=imap_utf7_decode($val);
			if($GLOBALS["VERBOSE"]){echo "$uid: \"$folder\"\n";}
			if(preg_match("#Sent\s+Items#i", $folder)){$SentMailBox[]=$val;break;}
			if(preg_match("#\/Sent#i", $folder)){$SentMailBox[]=$val;break;}
			if(preg_match("#\/Sent Messages#i", $folder)){$SentMailBox[]=$val;break;}
			if(preg_match("#\/.*?l.*?ments envoy.*?s#i", $folder)){$SentMailBox[]=$val;break;}
				
		}
	}else{
		blackwhite_admin_mysql(0,"$uid: imap_list $mailbox failed",imap_last_error(),__FILE__,__LINE__);
		$imapClose = imap_close($imapLink);
		return;
	}

	
	if(count($SentMailBox)==0){
		if($GLOBALS["VERBOSE"]){echo "$uid: imap_list failed to find Sent Items folder\n";}
		blackwhite_admin_mysql(0,"$uid: imap_list failed to find Sent Items folder",imap_last_error(),__FILE__,__LINE__);
		$imapClose = imap_close($imapLink);
		return;
	}
	$imapClose = imap_close($imapLink);
	
	
	while (list ($indexMailbox, $MailBoxPattern) = each ($SentMailBox)){
		$imapLink =imap_open($MailBoxPattern,$uid,$ct->password);
	
		if(! $imapLink ){
			blackwhite_admin_mysql(0,"$uid: Failed to connect to $SentMailBox",imap_last_error(),__FILE__,__LINE__);
			continue;
		}
	
	
		$mailBoxInfos = imap_check($imapLink);
		if(!$mailBoxInfos){ echo "mailbox $uid Fetch infos failed...\n"; }
		$MessagesCount=$mailBoxInfos->Nmsgs;
		$mailList = imap_fetch_overview($imapLink,"0:".$mailBoxInfos->Nmsgs);
		if(!isset($mailList)){
			blackwhite_admin_mysql(0,"$uid: Failed to open $SentMailBox",imap_last_error(),__FILE__,__LINE__);
			$imapClose = imap_close($imapLink);
			continue;
		}
	
		$num = imap_num_msg($imapLink);
	
		for($mid=0;$mid<$num;$mid++){
			$h = imap_header($imapLink,$mid);
			if(!isset($h->toaddress)){
				if($GLOBALS['VERBOSE']){echo "Message #$mid/$num : No such recipient..\n";}
				continue;
			}
			$toaddress=trim(strtolower(imap_utf7_decode($h->toaddress)));
			$toaddress=str_replace('"', "", $toaddress);
			
			if(preg_match("#<(.+?)>#", $toaddress,$re)){$toaddress=trim($re[1]);}
			if(preg_match("#^(.+?)@(.+?)\.(.+?)$#", $toaddress,$re)){ $toaddress="{$re[1]}@{$re[2]}.{$re[3]}"; }else{continue;}
			if(isset($ct->amavisWhitelistSender[$toaddress])){continue;}
			$F[$toaddress]=true;
			
		}
		
		$imapClose = imap_close($imapLink);
		
	}
	
	
	if(count($F)==0){return;}
	$ldap=new clladp();
	while (list ($email, $none) = each ($F)){
		if($ct->add_whitelist($email)){
			$Added[]=$email;
		}
	
	}
	
	if(count($Added)>0){
		$unix=new unix();
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		blackwhite_admin_mysql(2,"$uid: ". count($Added)." whitelist emails from outbox took $took",@implode("\n", $Added),__FILE__,__LINE__);
	}
}


function imapopen_black($uid){
	
	// Sent Items
	$F=array();
	$JunkMailbox=null;
	$mailbox="{127.0.0.1:143/imap}";
	$ct=new user($uid);
	$imapLink =imap_open("{$mailbox}INBOX",$uid,$ct->password);
	if(! $imapLink ){
		blackwhite_admin_mysql(0,"$uid: Failed to open mailbox",imap_last_error(),__FILE__,__LINE__);
		return;
	}
	
	
	$list = imap_list($imapLink, $mailbox, "*");
	if (is_array($list)) {
		foreach ($list as $val) {
			$folder=imap_utf7_decode($val);
			if($GLOBALS["VERBOSE"]){echo "imapopen_black:: FIND: \"$folder\"\n";}
			if(preg_match("#Junk\s+E-mail#", $folder)){$JunkMailbox=$val;break;}
			if(preg_match("#Courrier\s+ind.*?sirable#", $folder)){$JunkMailbox=$val;break;}
			
		}
	}else{
		echo "imap_list failed: " . imap_last_error() . "\n";
		blackwhite_admin_mysql(0,"$uid: imap_list $mailbox failed",imap_last_error(),__FILE__,__LINE__);
		$imapClose = imap_close($imapLink);
		return;
	}	
	
	if($JunkMailbox==null){
		echo "imap_list failed to find Junk folder\n";
		blackwhite_admin_mysql(1,"$uid: imap_list failed to find Junk folder",imap_last_error(),__FILE__,__LINE__);
		$imapClose = imap_close($imapLink);
		return;
	}
	$imapClose = imap_close($imapLink);
	
	if($GLOBALS["VERBOSE"]){echo "imapopen_black:: imap_open: \"$JunkMailbox\"\n";}
	
	$imapLink =imap_open("$JunkMailbox",$uid,$ct->password);
	
	if(! $imapLink ){
		blackwhite_admin_mysql(1,"$uid: Failed to open mailbox $JunkMailbox",imap_last_error(),__FILE__,__LINE__);
		return;
	}
	
	
	$mailBoxInfos = imap_check($imapLink);
	if(!$mailBoxInfos){ 
		blackwhite_admin_mysql(1,"$uid: mailbox $uid Fetch infos failed.",imap_last_error(),__FILE__,__LINE__);
		$imapClose = imap_close($imapLink);
		return;
	}
	
	
	$MessagesCount=$mailBoxInfos->Nmsgs;
	$mailList = imap_fetch_overview($imapLink,"0:".$mailBoxInfos->Nmsgs);
	if(!isset($mailList)){
		blackwhite_admin_mysql(2,"$uid: Failed to open mailbox $JunkMailbox",imap_last_error(),__FILE__,__LINE__);
		$imapClose = imap_close($imapLink);
		return;
	}
	
	$num = imap_num_msg($imapLink);
	if($GLOBALS["VERBOSE"]){echo "imapopen_black:: imap_open: MESSAGES: $num\n";}
	
	for($mid=0;$mid<$num;$mid++){
		
		$h = imap_header($imapLink,$mid);
		if(!isset($h->fromaddress)){continue;}
		$fromaddress=trim(strtolower($h->fromaddress));
		$fromaddress=str_replace('"', "", $fromaddress);
		
		if(preg_match("#<(.+?)>#", $fromaddress,$re)){$fromaddress=trim($re[1]);}
		if(preg_match("#^(.+?)@(.+?)\.(.+?)$#", $fromaddress,$re)){ $fromaddress="{$re[1]}@{$re[2]}.{$re[3]}"; }else{continue;}
		if(isset($ct->amavisWhitelistSender[$fromaddress])){continue;}
		if(isset($ct->amavisBlacklistSender[$fromaddress])){continue;}
		$F[$fromaddress]=true;
		
	}
	
	
	if(count($F)==0){
		echo "No Messages, return\n";
		$imapClose = imap_close($imapLink);
		return;
	}
	
	$ldap=new clladp();
	while (list ($email, $none) = each ($F)){
		if($ct->add_blacklist($email)){
			$Added[]=$email;
		}
		
	}
	
	@imap_delete($imapLink,'1:*');   // to clear out an entire mailbox.
	@imap_expunge($imapLink);
	
	if(count($Added)>0){
		blackwhite_admin_mysql(2,"$uid: ".count($Added)." blacklisted senders",@implode("\n", $Added),__FILE__,__LINE__);
	}
	
}