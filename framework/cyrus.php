<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
foreach ($_GET as $num=>$ligne){$a[]="$num=$ligne";}
writelogs_framework(@implode(" - ",$a),__FUNCTION__,__FILE__,__LINE__);

if(isset($_GET["service-cmds"])){service_cmds();exit;}
if(isset($_GET["backup-test-nas"])){backup_test_nas();exit;}
if(isset($_GET["create-mbx"])){create_mailbox();exit;}
if(isset($_GET["imapd-conf"])){imapd_conf();exit;}
if(isset($_GET["cyrus-events"])){cyrus_events();exit;}
if(isset($_GET["cyrquota"])){cyrus_quota();exit;}
if(isset($_GET["mailboxlist-domain"])){cyrus_mailboxlist_domain();exit;}
if(isset($_GET["mailboxlist"])){cyrus_mailboxlist();exit;}
if(isset($_GET["sync-services"])){sync_services();exit;}

writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	
function service_cmds(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$cmds=$_GET["service-cmds"];
	$results[]="Position: $cmds";
	
	if($cmds=="restart"){
		exec("/usr/share/artica-postfix/bin/artica-install --reconfigure-cyrus 2>&1",$results);
		
		
	}else{
		exec("/etc/init.d/artica-postfix $cmds imap 2>&1",$results);
	}
	if(is_file("/var/run/saslauthd/mux")){@chmod("/var/run/saslauthd/mux", 0777);}
	if(is_dir("/var/run/saslauthd")){@chmod("/var/run/saslauthd", 0755);}
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}

function restart(){

	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup /usr/share/artica-postfix/bin/artica-install --reconfigure-cyrus >/dev/null 2>&1 &");
	if(is_file("/var/run/saslauthd/mux")){@chmod("/var/run/saslauthd/mux", 0777);}
	if(is_dir("/var/run/saslauthd")){@chmod("/var/run/saslauthd", 0755);}
	
}

function imapd_conf(){
	@unlink("/usr/share/artica-postfix/ressources/logs/web/imapd.conf");
	@copy("/etc/imapd.conf", "/usr/share/artica-postfix/ressources/logs/web/imapd.conf");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/imapd.conf", 0777);
}

function backup_test_nas(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.cyrus.backup.php --test-nas --verbose 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function create_mailbox(){
	$MailBoxMaxSize=$_GET["MailBoxMaxSize"];
	@unlink("/usr/share/artica-postfix/ressources/logs/cyrus.mbx.progress");
	@chmod("/usr/share/artica-postfix/ressources/logs/cyrus.mbx.progress",0777);
	
	@unlink("/usr/share/artica-postfix/ressources/logs/web/cyrus.mbx.txt");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/cyrus.mbx.txt",0777);

	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php /usr/share/artica-postfix/exec.cyrus.creatembx.php --create-mbx \"{$_GET["uid"]}\" \"$MailBoxMaxSize\">/usr/share/artica-postfix/ressources/logs/web/cyrus.mbx.txt 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
		
	
}

function cyrus_events(){
	
		$unix=new unix();
		$search=$_GET["search"];
		$rp=intval($_GET["rp"]);
		$eth=$_GET["eth"];
		$logfile="/usr/share/artica-postfix/ressources/logs/web/cyrus.log";
	
		
		if($search<>null){
				$search="\s+cyrus\/.*?$search";
			}else{
				$search="\s+cyrus\/";
			}
		
	
		$grep=$unix->find_program("grep");
		$tail=$unix->find_program("tail");
		if($search==null){
			$cmdline="$tail -n $rp /var/log/mail.log >$logfile 2>&1";
			writelogs_framework($cmdline,__FUNCTION__,__FILE__,__LINE__);
			shell_exec($cmdline);
			@chmod($logfile,0777);
			return;
		}
	
		if($search<>null){
			$cmdline="$grep --binary-files=text -Ei \"$search\" /var/log/mail.log|$tail -n $rp  >$logfile 2>&1";
			writelogs_framework($cmdline,__FUNCTION__,__FILE__,__LINE__);
			shell_exec($cmdline);
			@chmod($logfile,0777);
			return;
	
		}
}	

function cyrus_quota(){
	$unix=new unix();
	$cyrquota=$unix->LOCATE_CYRUS_QUOTA();
	$su=$unix->find_program("su");
	$logfile="/usr/share/artica-postfix/ressources/logs/web/cyrquota.log";
	$cmd="$cyrquota";
	
	exec($cmd,$results);
	@file_put_contents($logfile, @implode("\n", $results));
	writelogs_framework($cmd ." - ".@filesize($logfile)." bytes ".@count( $results),__FUNCTION__,__FILE__,__LINE__);
	@chmod($logfile,0777);
	return;
	
	
}
function cyrus_mailboxlist_domain(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	exec("$php5 /usr/share/artica-postfix/exec.cyrus.php --listmailboxes-domains {$_GET["mailboxlist-domain"]}",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}	
function cyrus_mailboxlist(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	exec("$php5 /usr/share/artica-postfix/exec.cyrus.php --listmailboxes",$results);
	writelogs_framework(count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function sync_services(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/cyrus.sync.progress";
$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/cyrus.sync.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	
	system("$nohup $php5 /usr/share/artica-postfix/exec.cyrus.sync.php >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$nohup $php5 /usr/share/artica-postfix/exec.cyrus.sync.php >{$GLOBALS["LOGSFILES"]} 2>&1 & ",__FUNCTION__,__FILE__,__LINE__);
	
}