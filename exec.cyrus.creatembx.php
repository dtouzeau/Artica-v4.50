<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["AS_ROOT"]=true;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$dir=dirname(__FILE__);
	include_once($dir.'/ressources/class.templates.inc');
	include_once($dir.'/ressources/class.ldap.inc');
	include_once($dir.'/ressources/class.users.menus.inc');
	include_once($dir.'/ressources/class.artica.inc');
	include_once($dir.'/ressources/class.mysql.inc');	
	include_once($dir.'/ressources/class.ini.inc');
	include_once($dir.'/ressources/class.cyrus.inc');
	include_once($dir.'/ressources/class.cron.inc');
	include_once($dir.'/ressources/class.system.network.inc');
	include_once($dir.'/ressources/class.user.inc');
	if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
	
	

	
if($argv[1]=="--create-mbx"){CreateMBX($argv[2],$argv[3]);}
if($argv[1]=="--mbx-exists"){IfMailBoxExists($argv[2],$argv[3]);}	




function build_progress($text,$pourc){
	echo "******************** {$pourc}% $text ********************\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/cyrus.mbx.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function IfMailBoxExists($uid){
	$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	
	$cyr = new cyrus ( );
	$RealMailBox=$cyr->IfMailBoxExists($uid); 
	if($RealMailBox){echo "\n\n *********** FALSE ***********\n";}
	
}

	
function CreateMBX($uid,$MailBoxMaxSize=0){
	
		$cyrus=new cyrus();
		if(!$cyrus->MailBoxExists($uid)){
			build_progress("Check privileges...",10);
			if(!checkrights($uid,$MailBoxMaxSize)){
				build_progress("{error_creating_mailbox}",110);
				return;
			}
			
			build_progress("{create_mailbox2}",50);
			if(!$cyrus->CreateMailbox($uid,1,50)){
				build_progress("{error_creating_mailbox}",110);
				return;
			}
			
			build_progress("Building privileges",80);
			$cyrus=new cyrus();
			$cyrus->CreateACLS($uid);
			
			build_progress("{success}",100);
			
			echo $cyrus->cyrus_infos."\n";
			return;
			
			
		}
		build_progress("Building privileges",80);
		$cyrus=new cyrus();
		$cyrus->CreateACLS($uid);
		build_progress("$uid: {mailbox_already_exists} {success}",100);
}
function checkrights($uid,$MailBoxMaxSize){
	$tpl=new templates();
	$user=new user($uid);
	
		$acls[]="[mailbox]";
		$acls[]="l=1";
		$acls[]="r=1";
		$acls[]="s=1";
		$acls[]="w=1";
		$acls[]="i=1";
		$acls[]="p=1";
		$acls[]="c=1";
		$acls[]="d=1";
		$acls[]="a=1";

		$user=new user($uid);
		build_progress("Max mailbox size: $MailBoxMaxSize MB",10);
		$user->MailBoxMaxSize=$MailBoxMaxSize;
		$user->MailboxActive="TRUE";
		$user->MailboxSecurityParameters=@implode("\n", $acls);
	
		if(!$user->SaveCyrusMailboxesParameters()){
			echo $user->ldap_error."\n";
			echo "LDAP FAILED....!\n";
			return false;
		}
	
	
		if($user->MailboxActive<>"TRUE"){
			echo "$uid: Mailbox disabled ($user->MailboxActive\n";
			echo "LDAP FAILED....!\n";
			return false;
		}
		echo "$uid: checkrights(): Success...\n";
		return true;
		
	}