<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.gluster.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");

/*http://www.r71.nl/index.php/kb/technical/17-mailserver-cyrus-imap-and-sendmail-installation
 *  EVENTS {
  #rrd index aanmaken van de emails dmv squatter
  squatter      cmd="squatter -r user" period=1440
}


# su - cyrus
# /usr/lib/cyrus-imapd/squatter -v -r user.roderick
 */

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(!Build_pid_func(__FILE__,"MAIN")){
	writelogs(basename(__FILE__).":Already executed.. aborting the process",basename(__FILE__),__FILE__,__LINE__);
	exit();
}



	$GLOBALS["uid"]=$argv[1];
	@unlink("/usr/share/artica-postfix/ressources/logs/cyr.repair.{$GLOBALS["uid"]}");
	events("order to repair mailbox of {$GLOBALS["uid"]}");
	
	$unix=new unix();
	$su=$unix->find_program("su");
	if(!is_file($su)){
		events("unable to locate su tool !");
		exit();
	}
	
	$cyrreconstruct=$unix->LOCATE_CYRRECONSTRUCT();
	if(!is_file($cyrreconstruct)){
		events("unable to locate CYRRECONSTRUCT tool !");
		exit();
	}	
	
	$cyrquota=$unix->LOCATE_CYRQUOTA();
		if(!is_file($cyrquota)){
		events("unable to locate cyrquota tool !");
		exit();
	}	

	$unixhierarchysep=$unix->IMAPD_GET('unixhierarchysep');
	
	$account="user.{$GLOBALS["uid"]}";
	if(strtolower($unixhierarchysep)=="yes"){$account="user/{$GLOBALS["uid"]}";}
	events("unixhierarchysep -> $unixhierarchysep ($account)");
	$queue_path=$unix->IMAPD_GET('partition-default');
	events("unixhierarchysep -> $unixhierarchysep ($account) on $queue_path");
	$first_letter=substr($GLOBALS["uid"],0,1);
	
	$user_path=str_replace('.','^',$GLOBALS["uid"]);
	$fpath="$queue_path/$first_letter/user/$user_path";
	events("mailbox path -> $fpath");
	if(is_file("$fpath/cyrus.seen")){
		events("Delete file $fpath/cyrus.seen");
		@unlink("$fpath/cyrus.seen");
	}
	$tmpf=$unix->FILE_TEMP();
	events("start repair mailbox...");
	$cmd="$su cyrus -c \"$cyrreconstruct -r -f $account\" >$tmpf 2>&1";
	shell_exec($cmd);
	$tbl=explode("\n",@file_get_contents($tmpf));
	@unlink($tmpf);
	foreach ($tbl as $num=>$ligne){
		events($ligne);
	}

	$cmd="$su cyrus -c \"$cyrquota -f\" >$tmpf 2>&1";
	shell_exec($cmd);
	$tbl=explode("\n",@file_get_contents($tmpf));
	@unlink($tmpf);
	foreach ($tbl as $num=>$ligne){
		events($ligne);
	}
	
	events("restart mailbox server...");
	shell_exec("/etc/init.d/cyrus-imapd restart");
	@chmod("/usr/share/artica-postfix/ressources/logs/cyr.repair.{$GLOBALS["uid"]}",0755);



function events($text){
		$pid=getmypid();
		$date=date("Y-m-d H:i:s");
		$text="$date [$pid] $text";
		$f=new debuglogs();
		echo $text ."\n";
		$f->events(basename(__FILE__)." $text","/usr/share/artica-postfix/ressources/logs/cyr.repair.{$GLOBALS["uid"]}");
		}
?>