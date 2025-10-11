<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}

if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
}
if(preg_match("#--show#",implode(" ",$argv))){$GLOBALS["SHOW"]=true;}
if($argv[1]=="--sync"){sync($argv[2]);exit;}
if($argv[1]=="--cron"){cron();exit;}
if($argv[1]=="--stop"){cron($argv[2]);exit;}
if($argv[1]=="--alltasks"){cronAll();exit;}




exit();

function stop($id){
$unix=new unix();	
$sql="SELECT * FROM imapsync WHERE ID='$id'";
	$q=new mysql();
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){
		write_syslog("Mysql error $q->mysql_error",__FILE__);
		exit();
	}
	$pid_org=$ligne["pid"];
	$ligne["imap_server"]=str_replace(".","\.",$ligne["imap_server"]);
	$ligne["username"]=str_replace(".","\.",$ligne["username"]);	
	exec($unix->find_program("pgrep")." -f \"imapsync.+?--host1 {$ligne["imap_server"]}.+?--user1 {$ligne["username"]}\"",$pids);

	while (list ($index, $pid) = each ($pids) ){
		if($pid>5){unix_system_kill_force($pid);}
	}
	
	unix_system_kill_force($pid_org);
}

function sync($id,$nopid=false){
	$unix=new unix();
	$users=new usersMenus();
	squid_admin_mysql(2, "Mail synchronization: Running task $id",__FUNCTION__,__FILE__,__LINE__);
	$GLOBALS["unique_id"]=$id;
	$ASOfflineImap=false;
	$sql="SELECT * FROM imapsync WHERE ID='$id'";
	$q=new mysql();
	$took1=time();
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){
		squid_admin_mysql(2, "Mysql error $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		exit();
	}
	$pid=$ligne["pid"];
	if(!$nopid){
		if($unix->process_exists($pid)){return;}
	}
	update_pid(getmypid());
	
	if(!is_file($unix->find_program("imapsync"))){
		if(!is_file($unix->find_program("offlineimap"))){
			update_status(-1,"Could not find imapsync/offlineimap program");
			return;
		}
	}
	update_status(1,"Executed");
	include_once(dirname(__FILE__).'/ressources/class.user.inc');
	$ct=new user($ligne["uid"]);
	$parameters=unserialize(base64_decode($ligne["parameters"]));
	$parameters["sep"]=trim($parameters["sep"]);
	$parameters["sep2"]=trim($parameters["sep2"]);
	$parameters["maxage"]=trim($parameters["maxage"]);
	if($parameters["maxage"]==null){$parameters["maxage"]=0;}
	if($parameters["UseOfflineImap"]==1){$ASOfflineImap=true;}
	
	$offlineImapConf[]="[general]";
	$offlineImapConf[]="metadata = /var/lib/offlineimap/{$ligne["uid"]}";
	if($ASOfflineImap){@mkdir("/var/lib/offlineimap/{$ligne["uid"]}",null,true);}
	$offlineImapConf[]="accounts = Myaccount";
	$offlineImapConf[]="maxsyncaccounts = 1";
	$offlineImapConf[]="ui =Noninteractive.Basic, Noninteractive.Quiet";
	$offlineImapConf[]="ignore-readonly = no";
	$offlineImapConf[]="socktimeout = 60";
	$offlineImapConf[]="fsync = true";
	$offlineImapConf[]="";
	$offlineImapConf[]="[ui.Curses.Blinkenlights]";
	$offlineImapConf[]="statuschar = .";
	$offlineImapConf[]="";
	$offlineImapConf[]="[Account Myaccount]";
	$offlineImapConf[]="localrepository = TargetServer";
	$offlineImapConf[]="remoterepository = SourceServer";
	$offlineImapConf[]="# autorefresh = 5";
	$offlineImapConf[]="# quick = 10";
	$offlineImapConf[]="# presynchook = imapfilter";
	$offlineImapConf[]="# postsynchook = notifysync.sh";
	$offlineImapConf[]="# presynchook = imapfilter -c someotherconfig.lua";
	$offlineImapConf[]="# maxsize = 2000000";
	if($parameters["maxage"]>0){
		$offlineImapConf[]="maxage = {$parameters["maxage"]}";
	}	
	
	
	$array_folders=unserialize(base64_decode($ligne["folders"]));
	if($users->cyrus_imapd_installed){$local_mailbox=true;}
	if($users->ZARAFA_INSTALLED){$local_mailbox=true;}
	
	if($local_mailbox){
		if($parameters["local_mailbox"]==null){$parameters["local_mailbox"]=1;}
		if($parameters["local_mailbox_source"]==null){$parameters["local_mailbox_source"]=1;}
	}else{
		$parameters["local_mailbox"]=0;
		$parameters["local_mailbox_source"]=0;
	}
	
	if($parameters["dest_imap_server"]==null){
		if($parameters["local_mailbox"]==1){
			$parameters["dest_imap_server"]="127.0.0.1";
		}
	}
	
		
	if(!$local_mailbox){
		if($parameters["remote_imap_server"]==null){
			if($GLOBALS["VERBOSE"]){echo "unable to get SOURCE imap server\n";}
			update_status(-1,"unable to get SOURCE imap server");
			return;
		}
		
	  if($parameters["dest_imap_server"]==null){
	  		if($GLOBALS["VERBOSE"]){echo "Cyrus: $user->cyrus_imapd_installed; Zarafa:$user->ZARAFA_INSTALLED\n";}
			if($GLOBALS["VERBOSE"]){echo "unable to get DESTINATION imap server Local server:$local_mailbox; Parms:local_mailbox={$parameters["local_mailbox"]}\n";}
			update_status(-1,"unable to get DESTINATION imap server");
			return;
		}		
	}
	
	
	if($parameters["local_mailbox_source"]==0){
		if($parameters["remote_imap_server"]==null){
			if($GLOBALS["VERBOSE"]){echo "unable to get SOURCE imap server\n";}
			update_status(-1,"unable to get SOURCE imap server");
			return;
		}
	}
	
	if($parameters["local_mailbox"]==0){
		if($parameters["dest_imap_server"]==null){
			if($GLOBALS["VERBOSE"]){echo "unable to get DESTINATION imap server\n";}
			update_status(-1,"unable to get destination DESTINATION server");
			return;
		}
	}	
	
	if($parameters["local_mailbox"]==1){
		if($parameters["local_mailbox_source"]==1){
			if($GLOBALS["VERBOSE"]){echo "DESTINATION imap mailbox cannot be the same has SOURCE imap mailbox\n";}
		 	update_status(-1,"DESTINATION imap mailbox cannot be the same has SOURCE imap mailbox");
		 	return;
		}
	}
	
	if($parameters["local_mailbox"]==1){
			$host2="127.0.0.1";
			$user2=$ct->uid;
			$password2=$ct->password;
			$md52=md5("$host2$user2$password2");
	}else{
			$host2=$parameters["dest_imap_server"];
			$user2=$parameters["dest_imap_username"];
			$password2=$parameters["dest_imap_password"];
			$md52=md5("$host2$user2$password2");
	}
	
	if($parameters["local_mailbox_source"]==1){
			$host1="127.0.0.1";
			$user1=$ct->uid;
			$password1=$ct->password;
			$md51=md5("$host1$user1$password1");
	}else{
			$host1=$ligne["imap_server"];
			$user1=$ligne["username"];
			$password1=$ligne["password"];
			$md51=md5("$host1$user1$password1");
	}
	
	if($md51==$md52){
		if($GLOBALS["VERBOSE"]){echo "DESTINATION imap mailbox cannot be the same has SOURCE imap mailbox\n";}
		update_status(-1,"DESTINATION imap mailbox cannot be the same has SOURCE imap mailbox");
		return;
	}	
	
	
	

	
	
	$offlineImapUseSSL1="no";
	$offlineImapUseSSL1port="143";
	$offlineImapUseSSL2="no";
	$offlineImapUseSSL2port="143";
	$expunge1="no";
	
	
	if($parameters["use_ssl"]==1){$ssl1=" --ssl1";$offlineImapUseSSL1="yes";$offlineImapUseSSL1port="993";}
	if($parameters["dest_use_ssl"]==1){$ssl2=" --ssl2";$offlineImapUseSSL2="yes";$offlineImapUseSSL2port="993";}
	
	if($parameters["syncinternaldates"]==null){$parameters["syncinternaldates"]=1;}
	if($parameters["noauthmd5"]==null){$parameters["noauthmd5"]=1;}
	if($parameters["allowsizemismatch"]==null){$parameters["allowsizemismatch"]=1;}
	if($parameters["nosyncacls"]==null){$parameters["nosyncacls"]=1;}
	if($parameters["skipsize"]==null){$parameters["skipsize"]=0;}
	if($parameters["nofoldersizes"]==null){$parameters["nofoldersizes"]=1;}
	
	if($parameters["useSep1"]==null){$parameters["useSep1"]=0;}
	if($parameters["useSep2"]==null){$parameters["useSep2"]=0;}
	if($parameters["usePrefix1"]==null){$parameters["usePrefix1"]=0;}
	if($parameters["usePrefix2"]==null){$parameters["usePrefix2"]=0;}
	if($parameters["delete_messages"]==1){$delete=" --delete --expunge1";$expunge1="yes";}
	if($parameters["syncinternaldates"]==1){$syncinternaldates=" --syncinternaldate";}
	if($parameters["noauthmd5"]==1){$noauthmd5=" --noauthmd5";}
	if($parameters["allowsizemismatch"]==1){$allowsizemismatch=" --allowsizemismatch";}
	if($parameters["nosyncacls"]==1){$nosyncacls=" --nosyncacls";}
	if($parameters["skipsize"]==1){$skipsize=" --skipsize";}
	if($parameters["nofoldersizes"]==1){$nofoldersizes=" --nofoldersizes";}
	if($parameters["useSep1"]==1){$sep=" --sep1 \"{$parameters["sep"]}\"";}
	if($parameters["useSep2"]==1){$sep2=" --sep2 \"{$parameters["sep2"]}\"";}
	if($parameters["usePrefix1"]==1){$prefix1=" --prefix1 \"{$parameters["prefix1"]}\"";}
	if($parameters["usePrefix2"]==1){$prefix2=" --prefix2 \"{$parameters["prefix2"]}\"";}
	if($parameters["maxage"]>0){$maxage=" --maxage {$parameters["maxage"]}";}	
	
	
	
	if(count($array_folders["FOLDERS"])>0){
		while (list($num,$folder)=each($array_folders["FOLDERS"])){
			if(trim($folder)==null){continue;}
			$cleaned[trim($folder)]=trim($folder);
		}
		while (list($num,$folder)=each($cleaned)){
			$foldersr[]=$folder;
			$offlineImapFolders[]="'$folder'";
		
		}
		$folders_replicate=@implode(" --folder ",$foldersr);
		
		
		
	}
	$offlineImapConf[]="";
	$offlineImapConf[]="[Repository SourceServer]";
	$offlineImapConf[]="type = IMAP";
	$offlineImapConf[]="remotehost = $host1";
	$offlineImapConf[]="ssl = $offlineImapUseSSL1";
	$offlineImapConf[]="remoteport = $offlineImapUseSSL1port";
	$offlineImapConf[]="remoteuser = $user1";
	$offlineImapConf[]="remotepass = $password1";
	$offlineImapConf[]="# reference = Mail";
	$offlineImapConf[]="maxconnections = 1";
	$offlineImapConf[]="holdconnectionopen = no";
	$offlineImapConf[]="# keepalive = 60";
	$offlineImapConf[]="expunge = $expunge1";
	$offlineImapConf[]="subscribedonly = no";
	if($parameters["useSep1"]==1){
		$offlineImapConf[]="sep = {$parameters["sep"]}";
	}
	$offlineImapConf[]="nametrans = lambda foldername: re.sub('^INBOX\.*', '.', foldername)";	
	if(count($offlineImapFolders)>0){
	$offlineImapConf[]="folderincludes = [". @implode(",",$offlineImapFolders)."]";
	}
		
	$offlineImapConf[]="";
	$offlineImapConf[]="[Repository TargetServer]";
	$offlineImapConf[]="type = IMAP";
	$offlineImapConf[]="remotehost = $host2";
	$offlineImapConf[]="ssl = $offlineImapUseSSL2";
	$offlineImapConf[]="remoteport = $offlineImapUseSSL2port";
	$offlineImapConf[]="remoteuser = $user2";
	$offlineImapConf[]="remotepass = $password2";
	$offlineImapConf[]="# reference = Mail";
	$offlineImapConf[]="maxconnections = 1";
	$offlineImapConf[]="holdconnectionopen = no";
	$offlineImapConf[]="# keepalive = 60";
	$offlineImapConf[]="expunge = no";
	$offlineImapConf[]="subscribedonly = no";	
	if($parameters["useSep2"]==1){
		$offlineImapConf[]="sep = {$parameters["sep2"]}";
	}
	$offlineImapConf[]="nametrans = lambda foldername: re.sub('^INBOX\.*', '.', foldername)";	
	if(count($offlineImapFolders)>0){
	$offlineImapConf[]="folderincludes = [". @implode(",",$offlineImapFolders)."]";
	}	
	
	

	$file_temp="/usr/share/artica-postfix/ressources/logs/imapsync.$id.logs";
	
	$imapsyncbin=$unix->find_program("imapsync");
	$offlineimap=$unix->find_program("offlineimap");
	
	$cmd="$imapsyncbin --buffersize 8192000$nosyncacls --subscribe$syncinternaldates";
	$cmd=$cmd." --host1 $host1 --user1 \"$user1\" --password1 \"$password1\"$ssl1$prefix1$sep --host2 $host2 --user2 \"$user2\"";
	$cmd=$cmd." --password2 \"$password2\"$ssl2$prefix2$sep2$folders_replicate$delete$noauthmd5$allowsizemismatch$skipsize$nofoldersizes$maxage >$file_temp 2>&1";
	
	
	if($ASOfflineImap){
		if(is_file($file_temp)){@unlink($file_temp);}
		if(!is_file($offlineimap)){update_status(-1,"Could not find offlineimap program");return;}
		$cmd="$offlineimap -o -u Noninteractive.Basic -c /tmp/offlineimap-{$ligne["uid"]} -l $file_temp";
		@file_put_contents("/tmp/offlineimap-{$ligne["uid"]}",@implode("\n",$offlineImapConf));
		if($GLOBALS["VERBOSE"]){
			$cmd_show=$cmd."\n";
			$datas=@implode("\n",$offlineImapConf);
			$datas=str_replace("remotepass = $password2","remotepass = MASKED",$datas);
			$datas=str_replace("remotepass = $password1","remotepass = MASKED",$datas);
			echo "$cmd_show\nConfiguration file:\n---------------------------\n\n$datas";
			return;
		}
		
	}else{
		if(!is_file($imapsyncbin)){update_status(-1,"Could not find imapsunc program");return;}
	}
	
	
	if($GLOBALS["VERBOSE"]){
		$cmd_show=$cmd;
		$cmd_show=str_replace("--password1 $password1","--password1 MASKED",$cmd_show);
		$cmd_show=str_replace("--password2 $password2","--password2 MASKED",$cmd_show);
		echo "$cmd_show\n";
		return;
	}
	
	
	
	shell_exec($cmd);
	update_status(0,addslashes(@file_get_contents($file_temp)));
	$took=$unix->distanceOfTimeInWords($took1,time(),true);
	squid_admin_mysql(2, "Task id $id done {took} $took",__FUNCTION__,__FILE__,__LINE__);
	
}


function update_pid($pid){
	$q=new mysql();
	$date=date('Y-m-d H:i:s');
	$sql="UPDATE imapsync SET pid='$pid',zDate='$date' WHERE ID={$GLOBALS["unique_id"]}";
	$q->QUERY_SQL($sql,"artica_backup");
}
function update_status($int,$text){
	$q=new mysql();
	$date=date('Y-m-d H:i:s');
	$sql="UPDATE imapsync SET state='$int',state_event='$text',zDate='$date' WHERE ID={$GLOBALS["unique_id"]}";
	$q->QUERY_SQL($sql,"artica_backup");
}

function cronAll(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$timefile=$unix->file_time_min($pidfile);
		system_admin_events(basename(__FILE__).": Already executed pid $pid since $timefile minutes.. aborting the process",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	@unlink($pidfile);
	@file_put_contents($pidfile, getmypid());	
	
	foreach (glob("/etc/cron.d/imapsync-*") as $filename) {@unlink($filename);}
	$sql="SELECT ID FROM imapsync";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		sync($ligne["ID"],true);
		
	}
	
	
}


function cron(){
	$unix=new unix();
	$q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
	foreach (glob("/etc/cron.d/imapsync-*") as $filename) {@unlink($filename);}
	
	$ligne=$q->mysqli_fetch_array("SELECT ID FROM system_schedules WHERE TaskType=60 LIMIT 1");
	if($ligne["ID"]>0){return;}
	
	$sql="SELECT CronSchedule,ID FROM imapsync";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){return null;}
	
	$php5=LOCATE_PHP5_BIN2();
 	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){	
 		if(trim($ligne["CronSchedule"]==null)){continue;}
 		$f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
		$f[]="MAILTO=\"\"";
		$f[]="{$ligne["CronSchedule"]}  root $php5 ".__FILE__." --sync {$ligne["ID"]}";
		$f[]="";
		@file_put_contents("/etc/cron.d/imapsync-{$ligne["ID"]}",implode("\n",$f));
		echo "Starting......: ".date("H:i:s")." Daemon (cron) set IMAP synchronization for task {$ligne["ID"]}\n";
		@chmod("/etc/cron.d/imapsync-{$ligne["ID"]}",640);
		unset($f);
 	}
 	echo "Starting......: ".date("H:i:s")." Daemon (cron) set securities permissions on /etc/cron.d\n";
 	shell_exec("/bin/chmod 640 /etc/cron.d/* >/dev/null 2>&1");
 	shell_exec("/etc/init.d/cron reload");
	
}





?>