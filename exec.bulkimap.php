<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FLUSH"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--flush#",implode(" ",$argv))){$GLOBALS["FLUSH"]=true;}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');

include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if($argv[1]=="serialize"){serialize_uid($argv[2]);}
perform();



function perform(){
	$sock=new sockets();
	$ldap=new clladp();
	$array=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ImapBulkImapExport")));
	$ImapBulkImapExportEnable=$sock->GET_INFO("ImapBulkImapExportEnable");
	if(!is_numeric($ImapBulkImapExportEnable)){$ImapBulkImapExportEnable=0;}
	if(!is_numeric($array["BULK_IMAP_ARTICA_PORT"])){$array["BULK_IMAP_ARTICA_PORT"]=9000;}
	if(!is_numeric($array["BULK_IMAP_PORT"])){$array["BULK_IMAP_PORT"]=143;}
	if($array["BULK_IMAP_ARTICA_ADMIN"]==null){$array["BULK_IMAP_ARTICA_ADMIN"]="Manager";}	
	if(!is_numeric($array["BULK_IMAP_ARTICA"])){$array["BULK_IMAP_ARTICA"]=0;}
	if($array["BULK_IMAP_ARTICA"]==1){ExportDatabase($array["BULK_IMAP_SERVER"],$array["BULK_IMAP_ARTICA_PORT"],$array["BULK_IMAP_ARTICA_ADMIN"],$array["BULK_IMAP_ARTICA_PASS"]);}
	
	$ous=$ldap->hash_get_ou();
	while (list($num,$org)=each($ous)){
		ExportMailBoxes($org,$array["BULK_IMAP_SERVER"],$array["BULK_IMAP_PORT"],$array["BULK_IMAP_ZARAFA"]);
	}
	
	
	
	
}

function ExportMailBoxes($ou,$imapserver,$imapport,$zarafa=0){
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".$ou.".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	$unix=new unix();
	if($unix->process_exists($pid,basename(__FILE__))){events("Already executed pid $pid",null,__FUNCTION__,__LINE__);return;}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);	
	
	
	$sql="DELETE FROM exports WHERE `function`='".__FUNCTION__.'"';
	$q=new mysql();
	$q->QUERY_SQL($sql,'artica_events');
	
	$unix=new unix();
	$imapsync=$unix->find_program("imapsync");
	$ldap=new clladp();
	events("[$imapserver:$imapport]: Exporting mailboxes from $ou organizations",null,__FUNCTION__,__LINE__);
	$users=$ldap->Hash_all_mailboxesActives($ou);
	while (list($uid,$password)=each($users)){
		$t=time();
		$tokens=array();
		$tokens[]=$imapsync;
		if($zarafa==1){$tokens[]="--noauthmd5";}
		$tokens[]="--allowsizemismatch";
		$tokens[]="--host1 127.0.0.1:143";
		$tokens[]="--user1 $uid";
		$tokens[]="--password1 \"$password\"";
		$tokens[]="--host2 $imapserver:$imapport";
		$tokens[]="--user2 $uid";
		$tokens[]="--password2 \"$password\"";
		if($zarafa==1){$tokens[]="--sep2 /";}
		if($zarafa==1){$tokens[]="--prefix2 \"\"";}
				
		events("[$uid]: Exporting $uid -> $imapserver:$imapport start...",null,__FUNCTION__,__LINE__);
		$results=array();
		exec(@implode(" ", $tokens)." 2>&1",$results);
		$time=$unix->distanceOfTimeInWords($t,time(),true);
		events("[$uid]: Exporting $uid -> $imapserver:$imapport end $time",@implode("\n", $results),__FUNCTION__,__LINE__);
		
	}
	
}



function ExportDatabase($server,$port,$admin,$password){
	
	
	$ldap=new clladp();
		ExportOrganizations($server,$port,$admin,$password);
		$ous=$ldap->hash_get_ou();
		while (list($num,$org)=each($ous)){
			ExportUsers($org,$server,$port,$admin,$password);
		}
	
}


function ExportOrganizations($server,$port,$admin,$password){
	$sql="DELETE FROM exports WHERE `function`='".__FUNCTION__.'"';
	$q=new mysql();
	$q->QUERY_SQL($sql,'artica_events');	
	
	$ldap=new clladp();
	$ous=$ldap->hash_get_ou();
	$CountDeou=count($ous);
	if($CountDeou==0){
		events("[$admin@$server:$port]: No organization to export",null,__FUNCTION__,__LINE__);	
		return;
	}
	events("[$admin@$server:$port]: Exporting $CountDeou organization(s)",null,__FUNCTION__,__LINE__);
	$AuThMeth="?AuThMeth=".base64_encode(serialize(array("admin"=>$admin,"pass"=>$password)));
	
	
	$curl=new ccurl("https://$server:$port/import.users.listener.php$AuThMeth",true);
	$curl->parms["OUS"]=base64_encode(serialize($ous));
	if(!$curl->get()){
		events("[$admin@$server:$port]: Exporting $CountDeou organizations failed",$curl->error,__FUNCTION__,__LINE__);
		return false;
	}
	
	if(preg_match("#<ERROR>(.+?)</ERROR>#is", $curl->data,$re)){
		events("[$admin@$server:$port]: Exporting $CountDeou organizations failed",$re[1],__FUNCTION__,__LINE__);
		return false;		
		
	}
	
	if(preg_match("#<SUCCESS>(.+?)</SUCCESS>#is", $curl->data,$re)){
		events("[$admin@$server:$port]: Success Exporting $CountDeou organizations",$re[1],__FUNCTION__,__LINE__);
		return false;		
		
	}	
	echo $curl->data;
	return true;
	
}

function ExportUsers($ou,$server,$port,$admin,$password){
	$sql="DELETE FROM exports WHERE `function`='".__FUNCTION__.'"';
	$q=new mysql();
	$q->QUERY_SQL($sql,'artica_events');	
	
	$AuThMeth="?AuThMeth=".base64_encode(serialize(array("admin"=>$admin,"pass"=>$password)));
	$ldap=new clladp();
	$users=$ldap->Hash_all_mailboxesActives($ou);
	
	$CountDeUsers=count($users);
	events("[$admin@$server:$port]: Exporting $CountDeUsers active member(s) inside $ou",null,__FUNCTION__,__LINE__);
	while (list($uid,$password)=each($users)){
		$user=new user($uid);
	 	foreach($user as $key => $value) {
 			if($key=="uidNumber"){continue;}
 			if($key=="local_sid"){continue;}
 			if($key=="accountGroup"){continue;}
 			if($key=="group_id"){continue;}
 			$array[$uid][$key]=$value;
           
       	}
	}
	
	events("[$admin@$server:$port]: Exporting array of ".count($array)." items",null,__FUNCTION__,__LINE__);
	$curl=new ccurl("https://$server:$port/import.users.listener.php$AuThMeth",true);
	$curl->parms["MEMBERS"]=base64_encode(serialize($array));
	$curl->parms["OU"]=$ou;
	if(!$curl->get()){
		events("[$admin@$server:$port]: Exporting $CountDeUsers active member(s) inside $ou",$curl->error,__FUNCTION__,__LINE__);
		return false;
	}	
	
	if(preg_match("#<ERROR>(.+?)</ERROR>#is", $curl->data,$re)){
		events("[$admin@$server:$port]: Exporting $CountDeou organizations failed",$re[1],__FUNCTION__,__LINE__);
		return false;		
		
	}
	
	if(preg_match("#<SUCCESS>(.+?)</SUCCESS>#is", $curl->data,$re)){
		events("[$admin@$server:$port]: Success Exporting $CountDeou organizations",$re[1],__FUNCTION__,__LINE__);
		return false;		
		
	}	
		
}





function events($subject,$text,$function,$line){
	$q=new mysql();
	$file=basename(__FILE__);
	$date=date('Y-m-d H:i:s');
	$subject=addslashes($subject);
	$text=addslashes($text);
	$sql="INSERT IGNORE INTO exports(zDate,`function`,`line`,`filename`,`subject`,`description`)
	VALUES('$date','$function','$line','$file','$subject','$text');
	";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_events");
	writelogs("$subject $text",$function,$file,__LINE__);
	
}


