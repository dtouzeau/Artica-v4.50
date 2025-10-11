<?php
register_shutdown_function('shutdown');
$GLOBALS["DEBUG_MEM"]=true;
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
$GLOBALS["DEBUG_MEM_FILE"]="{$GLOBALS["ARTICALOGDIR"]}/postfix-logger.debug";


error_log("Memory: START AT ".round(((memory_get_usage()/1024)/1000),2) ." line:".__LINE__);
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
error_log("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after includes class.ini.inc line:".__LINE__);
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
error_log("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after includes class.os.system.inc line:".__LINE__);
include_once(dirname(__FILE__).'/framework/frame.class.inc');
error_log("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after includes frame.class.inc line: ".__LINE__);
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
error_log("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after includes class.unix.inc line: ".__LINE__);
include_once(dirname(__FILE__).'/framework/class.settings.inc');
error_log("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after includes class.settings.inc line: ".__LINE__);
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
error_log("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after includes class.sockets.inc line: ".__LINE__);
include_once(dirname(__FILE__).'/ressources/class.postfix.maillog.inc');
error_log("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after includes class.postfix.maillog.inc line: ".__LINE__);

include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.postfix.builder.inc');
@mkdir("{$GLOBALS["ARTICALOGDIR"]}/MGREYSTATS");
$set=new settings_inc();
$GLOBALS["CLASS_SETTINGS"]=$set;
error_log("Memory: FINISH ".round(((memory_get_usage()/1024)/1000),2) ." after includes line: ".__LINE__);

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;}

$unix=new unix();
if(!$GLOBALS["VERBOSE"]) {
    $pidfile = "/etc/artica-postfix/" . basename(__FILE__) . ".pid";
    $pid = @file_get_contents($pidfile);
    if ($unix->process_exists($pid)) {
        writelogs("Already running pid $pid, Aborting");
        exit();
    }
    $pid = getmypid();
    error_log("running $pid ",0);
    file_put_contents($pidfile, $pid);
}
error_log("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after unix() declaration line: ".__LINE__);
$sock=new sockets();
$q=new postgres_sql();
$q->SMTP_TABLES();
$GLOBALS["CLASS_POSTGRES"]=$q;
$GLOBALS["CLASS_SOCKETS"]=$sock;
@chown("/home/artica/SQLITE/MIMEDEFANG_STATS", "postfix");
@chgrp("/home/artica/SQLITE/MIMEDEFANG_STATS", "postfix");

$GLOBALS["DEBUGMIMEFILTER"]=false;
$GlobalIptablesEnabled=$sock->GET_INFO("GlobalIptablesEnabled");
if(!is_numeric($GlobalIptablesEnabled)){$GlobalIptablesEnabled=1;}
error_log("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after sockets() declaration line: ".__LINE__);
$users=new settings_inc();
error_log("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after usersMenus() declaration line: ".__LINE__);
$_GET["server"]=$users->hostname;
$GLOBALS["MYHOSTNAME"]=$users->hostname;
$_GET["IMAP_HACK"]=array();

$GLOBALS["AMAVIS_INSTALLED"]=false;
$GLOBALS["CLASS_POSTFIX_SQL"]=new mysql_postfix_builder();
$GLOBALS["POP_HACK"]=array();
$GLOBALS["SMTP_HACK"]=array();
$GLOBALS["PHP5_BIN"]=LOCATE_PHP5_BIN2();
$GLOBALS["LN_BIN"]=$unix->find_program("ln");
$GLOBALS["POSTFIX_BIN"]=$unix->find_program("postfix");
$GLOBALS["iptables"]=$unix->find_program("iptables");
$DEBUGMIMEFILTER=intval($sock->GET_INFO("DebugMimeFilter"));
if($DEBUGMIMEFILTER==1){$GLOBALS["DEBUGMIMEFILTER"]=true;}


$GLOBALS["EnablePostfixAutoBlock"]=trim($sock->GET_INFO("EnablePostfixAutoBlock"));
if(!is_numeric($GLOBALS["EnablePostfixAutoBlock"])){$GLOBALS["EnablePostfixAutoBlock"]=1;}
$GLOBALS["PostfixNotifyMessagesRestrictions"]=$sock->GET_INFO("PostfixNotifyMessagesRestrictions");
$GLOBALS["GlobalIptablesEnabled"]=$GlobalIptablesEnabled;
$GLOBALS["PopHackEnabled"]=$sock->GET_INFO("PopHackEnabled");
$GLOBALS["PopHackCount"]=$sock->GET_INFO("PopHackCount");
$GLOBALS["DisableMailBoxesHack"]=$sock->GET_INFO("DisableMailBoxesHack");
$GLOBALS["EnableArticaSMTPStatistics"]=$sock->GET_INFO("EnableArticaSMTPStatistics");
$GLOBALS["ActAsASyslogSMTPClient"]=$sock->GET_INFO("ActAsASyslogSMTPClient");
$GLOBALS["EnableStopPostfix"]=$sock->GET_INFO("EnableStopPostfix");
$GLOBALS["EnableAmavisDaemon"]=$sock->GET_INFO("EnableAmavisDaemon");
$GLOBALS["PostfixRemoveConnections"]=unserialize(base64_decode($sock->GET_INFO("PostfixRemoveConnections")));
if(!is_numeric($GLOBALS["EnableStopPostfix"])){$GLOBALS["EnableStopPostfix"]=0;}
if(!is_numeric($GLOBALS["EnableArticaSMTPStatistics"])){$GLOBALS["EnableArticaSMTPStatistics"]=1;}
if(!is_numeric($GLOBALS["ActAsASyslogSMTPClient"])){$GLOBALS["ActAsASyslogSMTPClient"]=0;}
if(!is_numeric($GLOBALS["EnableAmavisDaemon"])){$GLOBALS["EnableAmavisDaemon"]=0;}
if(!is_numeric($GLOBALS["DisableMailBoxesHack"])){$GLOBALS["DisableMailBoxesHack"]=0;}
if($GLOBALS["PopHackEnabled"]==null){$GLOBALS["PopHackEnabled"]=1;}
if($GLOBALS["PopHackCount"]==null){$GLOBALS["PopHackCount"]=10;}
$GLOBALS["MYPATH"]=dirname(__FILE__);
$GLOBALS["SIEVEC_PATH"]=$unix->LOCATE_SIEVEC();
$GLOBALS["SMTP_HACK_CONFIG_RATE"]["NAME_SERVICE_NOT_KNOWN"]=10;
$GLOBALS["SMTP_HACK_CONFIG_RATE"]["SASL_LOGIN"]=15;
$GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"]=5;
$GLOBALS["SMTP_HACK_CONFIG_RATE"]["USER_UNKNOWN"]=10;
$GLOBALS["SMTP_HACK_CONFIG_RATE"]["BLOCKED_SPAM"]=5;
$GLOBALS["SMTP_HACK_CONFIG_RATE"]["SMTPHACK_TIMEOUT"]=10;
$GLOBALS["SMTP_HACK_CONFIG_RATE"]["SMTPHACK_RESOLUTION_FAILURE"]=2;


$GLOBALS["CLASS_UNIX"]=$unix;
$GLOBALS["postfix_bin_path"]=$unix->find_program("postfix");
$GLOBALS["postconf_bin_path"]=$unix->find_program("postconf");
$GLOBALS["CHOWN"]=$unix->find_program("chown");
$GLOBALS["GROUPADD"]=$unix->find_program("groupadd");
$GLOBALS["CHMOD"]=$unix->find_program("chmod");
$GLOBALS["fuser"]=$unix->find_program("fuser");
$GLOBALS["kill"]=$unix->find_program("kill");
$GLOBALS["NOHUP_PATH"]=$unix->find_program("nohup");
$GLOBALS["NETSTAT_PATH"]=$unix->find_program("netstat");
$GLOBALS["TOUCH_PATH"]=$unix->find_program("touch");
$GLOBALS["POSTMAP_PATH"]=$unix->find_program("postmap");
$GLOBALS["maillog_tools"]=new maillog_tools();
@mkdir("{$GLOBALS["ARTICALOGDIR"]}/smtp-connections",0755,true);
@mkdir("/etc/artica-postfix/cron.1",0755,true);
@mkdir("/etc/artica-postfix/cron.2",0755,true);
$users=null;
$sock=null;
$unix=null;
error_log("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after all declarations ".__LINE__);
@mkdir("/home/artica/postfix/realtime-events");




$postgres=new postgres_sql();
$postgres->SMTP_TABLES();
if(!isset($GLOBALS["maillog_tools"])){$GLOBALS["maillog_tools"]=new maillog_tools();}
if($GLOBALS["VERBOSE"]){echo "Open PIPE...\n";}

ini_set('html_errors',0);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
ini_set("log_errors", 1);
ini_set("error_log", "/var/log/postfix.log");
error_log("Starting service...",0);
$pipe = fopen("php://stdin", "r");
while(!feof($pipe)){
	$buffer=fgets($pipe, 4096);
	if($GLOBALS["VERBOSE"]){echo "Parseline(".strlen($buffer)."bytes\n";}

    try {
        Parseline($buffer);
    } catch (Exception $e) {
        error_log("FATAL ERROR ".$e->getMessage());
        if(function_exists("debug_backtrace")){
            $trace=debug_backtrace();
            foreach ($trace as $index=>$ligne){
                $sourcefile=basename($ligne["file"]);
                $sourcefunction=$ligne["function"];
                $sourceline=$ligne["line"];
                error_log("FATAL ERROR BY $sourcefile $sourcefunction line $sourceline");
            }
        }

    }
	$buffer=null;
}

fclose($pipe);
error_log("Shutdown...",0);
exit();

function shutdown() {
    $error = error_get_last();
    $lastfunc=null;$type=null;$message=null;
    if(!isset($error["file"])){$error["file"]=basename(__FILE__);}
    if(isset($error["type"])){$type=trim($error["type"]);}
    if(isset($error["message"])){$message= trim($error["message"]);}
    if($message==null){return;}
    $file = $error["file"];
    error_log("$file: Last function: `$lastfunc` Fatal, stopped with error $type $message",0);
    if(function_exists("openlog")){openlog("postfix-logger", LOG_PID , LOG_SYSLOG);}
    if(function_exists("syslog")){ syslog(true, "$file: Last function: `$lastfunc` Fatal, stopped with error $type $message");}
    if(function_exists("closelog")){closelog();}
}


function Parseline($buffer){
if(is_file("/etc/artica-postfix/DO_NOT_DETECT_POSTFIX")){
	if($GLOBALS["VERBOSE"]){echo "DO_NOT_DETECT_POSTFIX -> return;\n";}
	return;
}	
$buffer=trim($buffer);
if($buffer==null){return null;}



if(preg_match("#qmgr\[.*?:\s+([0-9A-Z]+): removed#", $buffer,$re)){$GLOBALS["maillog_tools"]->event_messageid_removed($re[1]);return;}

if(strpos($buffer,'config file "/etc/mail/greylist.conf"')>0){return;} 
if(strpos($buffer,"]: fatal: Usage:postmulti")>0){return;} 
if(strpos($buffer,"warning: non-SMTP command from unknown")>0){return;} 
if(strpos($buffer,"Do you need to run 'sa-update'?")>0){amavis_sa_update($buffer);return;}
if(strpos($buffer,"Passed CLEAN {AcceptedOpenRelay}")>0){return;} 
if(strpos($buffer,"Passed BAD-HEADER-1 {RelayedInternal}")>0){return;} 
if(strpos($buffer,"Valid PID file (")>0){return;} 
if(strpos($buffer,"]: SA dbg:")>0){return;} 
if(strpos($buffer,") SA dbg:")>0){return;} 
if(strpos($buffer,"enabling PIX workarounds: disable_esmtp delay_dotcrlf")>0){return;} 
if(strpos($buffer,"]: child: exiting: idle for")>0){return;} 
if(strpos($buffer,"]: master: child")>0){return;} 
if(strpos($buffer,") 2822.From: <")>0){return;} 
if(strpos($buffer,") Connecting to LDAP server")>0){return;} 
if(strpos($buffer,") connect_to_ldap: connected")>0){return;} 
if(strpos($buffer,") connect_to_ldap: bind")>0){return;} 
if(strpos($buffer,") Passed CLEAN, AM.PDP-SOCK [")>0){return;} 
if(strpos($buffer,"mode select: signing")>0){return;} 
if(strpos($buffer,"Starting worker process for POP3 request")>0){return;} 
if(strpos($buffer,": Accepted connection from")>0){return;} 
if(strpos($buffer,"]: Not authorized for command:")>0){return;} 
if(strpos($buffer,"milter-greylist: GeoIP failed to lookup ip")>0){return;} 
if(strpos($buffer,": Number of messages in the queue")>0){return;} 
if(strpos($buffer,") inspect_dsn: is a DSN")>0){return;}
if(strpos($buffer,": decided action=DUNNO NULL")>0){return;} 
if(strpos($buffer,"Mail::SpamAssassin::Plugin::Check")>0){return;} 
if(strpos($buffer,"vnStat daemon")>0){return;} 
if(strpos($buffer,"aliases.db: duplicate entry")>0){return;} 
if(strpos($buffer,"DKIM-Signature\" header added")>0){return;}
if(strpos($buffer,"DKIM verification successful")>0){return;}
if(strpos($buffer,": decided action=PREPEND X-policyd-weight: using cached result;")>0){return;} 
if(strpos($buffer," mode select: verifying")>0){return;} 
if(strpos($buffer,"Message canceled by rule")>0){return;}
if(strpos($buffer,"no signing table match for")>0){return;}
if(strpos($buffer,"Connection closed because of timeout")>0){return;}
//if(strpos($buffer,") SPAM-TAG, <")>0){return;} 
if(strpos($buffer,") mail checking ended: version_server=")>0){return;} 
if(strpos($buffer,") check_header:")>0){return;} 
if(strpos($buffer,") dkim: FAILED Author")>0){return;} 
if(strpos($buffer,") dkim: VALID Sender signature")>0){return;} 
if(strpos($buffer,") collect banned table")>0){return;} 
if(strpos($buffer,") p.path")>0){return;}  
if(strpos($buffer,") ask_av Using (ClamAV-clamd): CONTSCAN")>0){return;} 
if(strpos($buffer,") ClamAV-clamd: Connecting to socket")>0){return;} 
if(strpos($buffer,") ClamAV-clamd: Sending CONTSCAN")>0){return;}  
if(strpos($buffer,") inspect_dsn:")>0){return;} 
if(strpos($buffer,"IO::Socket::INET")>0){return;} 
if(strpos($buffer,") smtp resp to greeting:")>0){return;} 
if(strpos($buffer,") smtp cmd> EHLO")>0){return;}  
if(strpos($buffer,") smtp resp to EHLO:")>0){return;} 
if(strpos($buffer,") smtp resp to RCPT (")>0){return;} 
if(strpos($buffer,"greylist: mi_stop=1")>0){return;} 
if(strpos($buffer,"smfi_main() returned 0")>0){return;} 
if(strpos($buffer,"Final database dump")>0){return;}
if(strpos($buffer,"refreshing the Postfix")>0){return;}
if(strpos($buffer,"class.auth.tail.inc")>0){return;}
if(strpos($buffer,"authenticated, bypassing greylisting")>0){return;}
if(strpos($buffer,"NEW message_id")>0){return;}
if(strpos($buffer,"Passed CLEAN {")>0){return;}
if(strpos($buffer,") Blocked SPAM {")>0){return;}
if(strpos($buffer,") Blocked SPAMMY {")>0){return;}
if(strpos($buffer,"does not resolve to address")>0){return;}
if(strpos($buffer,"skipped, still being delivered")>0){return;}
if(strpos($buffer,"(0,lock|fold_fix)")>0){return;}
if(strpos($buffer,"Insecure dependency in open while running with -T")>0){return;}
// ************************ DKIM DUTSBIN
if(strpos($buffer,"no signing domain match for")>0){return;}
if(strpos($buffer,"no signing subdomain match for")>0){return;}
if(strpos($buffer,"no signing keylist match for")>0){return;}
if(strpos($buffer,": no signature data")>0){return;}
if(strpos($buffer," not internal")>0){return;}
if(strpos($buffer," not authenticated")>0){return;}

// ************************ ZARAFA DUTSBIN
if(strpos($buffer,"]: Still waiting for 1 threads to exit")>0){return;}
if(preg_match("#zarafa-dagent\[.*?Delivered message to#",$buffer)){return;}
if(strpos($buffer,": Disconnecting client.")>0){return;}
if(strpos($buffer,"thread exiting")>0){return;}
if(strpos($buffer,"Started to create store")>0){return;}
if(strpos($buffer,": Sending e-mail for user")>0){return;}

// POSFIX DUSTBIN
if(strpos($buffer,"warning: group or other writable")>0){return;}
if(strpos($buffer,"overriding earlier entry")>0){return;}
if(strpos($buffer,"warning: regexp map")>0){return;}
if(strpos($buffer,": uid=0 from=<root>")>0){return;}
if(strpos($buffer,"> are SPF-compliant, bypassing greylist")>0){return;}
if(strpos($buffer,'lookup error for "')>0){return;}
if(strpos($buffer,'Tempfailing because filter instructed us to')>0){return;}
if(strpos($buffer,'running with backwards-compatible default')>0){return;}
if(strpos($buffer,'COMPATIBILITY_README.html')>0){return;}
if(strpos($buffer,'To disable backwards compatibility')>0){return;}
if(strpos($buffer,"stopping the Postfix mail system")>0){return;}
if(strpos($buffer,", configuration /etc/postfix")>0){return;}
if(strpos($buffer,"fatal: bad numerical configuration")>0){return;}

//if(strpos($buffer,") p00")>0){return;}  
//if(strpos($buffer,") TIMING [total")>0){return;} 
//if(strpos($buffer,") TIMING-SA total")>0){return;}   
if(strpos($buffer,"mailarchiver[")>0){return;}
if(strpos($buffer,") policy protocol:")>0){return;} 
if(strpos($buffer,"]: policy protocol:")>0){return;} 
if(strpos($buffer,") run_av (ClamAV-clamd)")>0){return;}
if(strpos($buffer,"Net::Server: Process Backgrounded")>0){return;}
if(strpos($buffer,"Net::Server:")>0){return;}
if(strpos($buffer,": No ext program for")>0){return;}
if(strpos($buffer,": SA info: zoom: able to use")>0){return;}
if(strpos($buffer,": warm restart on HUP [")>0){return;}
if(strpos($buffer,": starting. (warm)")>0){return;}
if(strpos($buffer,"user=postfix, EUID:")>0){return;}
if(strpos($buffer,"No \$altermime,")>0){return;}
if(strpos($buffer,"starting. /usr/local/sbin/amavisd")>0){return;}
if(strpos($buffer,"initializing Mail::SpamAssassin")>0){return;}
if(strpos($buffer,"Net::Server: Binding to UNIX socket file")>0){return;}
if(strpos($buffer,"SpamControl: init_pre_chroot on SpamAssassin done")>0){return;}
if(strpos($buffer,"Starting worker for LMTP request")>0){return;}
if(strpos($buffer,"LMTP thread exiting")>0){return;}
if(strpos($buffer,") truncating a message passed to SA at")>0){return;}
if(strpos($buffer,"loaded policy bank")>0){return;}
if(strpos($buffer,"process_request: fileno sock")>0){return;}
if(strpos($buffer,"AM.PDP  /var/amavis/")>0){return;}
if(strpos($buffer,"KASWARNING [NOLOGID]: mfhelo: HELO already set")>0){return;}
if(strpos($buffer,"Passed CLEAN {AcceptedInbound}")>0){return;}
if(strpos($buffer,"Blocked MTA-BLOCKED {TempFailedOutbound}")>0){return;}
if(strpos($buffer,") body hash: ")>0){return;}
//if(strpos($buffer,") spam_scan: score=")>0){return;}
if(strpos($buffer,") Cached virus check expired")>0){return;}
if(strpos($buffer,") blocking contents category is")>0){return;}
if(strpos($buffer,") do_notify_and_quar: ccat=")>0){return;}
if(strpos($buffer,") inspect_dsn: not a bounce")>0){return;}
if(strpos($buffer,") local delivery:")>0){return;} 
if(strpos($buffer,") DSN: NOTIFICATION: ")>0){return;}
if(strpos($buffer,") SEND via PIPE:")>0){return;}
if(strpos($buffer,"Discarding because filter instructed us to")>0){return;}
if(strpos($buffer,") Checking for banned types and")>0){return;}
if(strpos($buffer,"skipping mailbox user")>0){return;}
if(strpos($buffer,"artica-plugin:")>0){return;} 
if(strpos($buffer,"success delivered trough 192.168.1.228:33559")>0){return;}
if(strpos($buffer,"skiplist: checkpointed /var/lib/cyrus/user")>0){return;}
if(strpos($buffer,"starttls: TLSv1 with cipher AES256-SHA (256/256 bits new)")>0){return;}
if(strpos($buffer,"lost connection after CONNECT from unknown")>0){return null;}
if(strpos($buffer,"lost connection after DATA from unknown")>0){return null;}
if(strpos($buffer,"lost connection after RCPT")>0){return null;}
if(strpos($buffer,"created decompress buffer of")>0){return null;}
if(strpos($buffer,"created compress buffer of")>0){return null;}
if(strpos($buffer,"SQUAT returned")>0){return null;}
if(strpos($buffer,": lmtp connection preauth")>0){return null;}
if(strpos($buffer,"indexing mailbox user")>0){return null;}
if(strpos($buffer,"mystore: starting txn")>0){return null;}
if(strpos($buffer,"duplicate_mark:")>0){return null;}
if(strpos($buffer,"mystore: committing txn")>0){return null;}
if(strpos($buffer,"cyrus/tls_prune")>0){return null;}
if(strpos($buffer,"milter-greylist: reloading config file")>0){return null;}
if(strpos($buffer,"milter-greylist: reloaded config file")>0){return null;}
if(strpos($buffer,"skiplist: recovered")>0){return null;}
if(strpos($buffer,"milter-reject NOQUEUE < 451 4.7.1 Greylisting in action, please come back in")>0){return null;}
if(strpos($buffer,"extra modules loaded after daemonizing/chrooting")>0){return null;}
if(strpos($buffer,"exec: /usr/bin/php")>0){return;}
if(strpos($buffer,"rec_get: type N")>0){return;}
if(strpos($buffer,"Found decoder for ")>0){return;}
if(strpos($buffer,"Internal decoder for ")>0){return;}
if(strpos($buffer,"indexing mailboxes")>0){return;}
if(strpos($buffer,"decided action=DUNNO multirecipient-mail - already accepted by previous query")>0){return;}
if(strpos($buffer,"decided action=PREPEND X-policyd-weight: passed - too many local DNS-errors")>0){return;}
if(strpos($buffer,"DSN: FILTER 554 Spam, spam level")>0){return;}
if(strpos($buffer,"emailrelay: info: no more messages to send")>0){return;}
if(strpos($buffer,"spamd: connection from ip6-localhost")>0){return;}
if(strpos($buffer,"spamd: processing message")>0){return;}
if(strpos($buffer,"spamd: clean message")>0){return;}
if(strpos($buffer,"spamd: result:")>0){return;}
if(strpos($buffer,"prefork: child states: I")>0){return;}
if(strpos($buffer,"autowhitelisted for another")>0){return;}
//if(strpos($buffer,"spamd: identified spam")>0){return;}
if(strpos($buffer,"spamd: handled cleanup of child pid")>0){return;}
if(strpos($buffer,"open_on_specific_fd")>0){return;}
if(strpos($buffer,"rundown_child on")>0){return;}
if(strpos($buffer,"switch_to_my_time")>0){return;}
if(strpos($buffer,"%, total idle")>0){return;}
if(strpos($buffer,"exec.mailarchive.php[")>0){return;}
if(strpos($buffer,"do_notify_and_quarantine: spam level exceeds")>0){return;}
if(strpos($buffer,", DEAR_SOMETHING=")>0){return;}
if(strpos($buffer,", DIGEST_MULTIPLE=")>0){return;}
if(strpos($buffer,", BAD_ENC_HEADER=")>0){return;}
if(strpos($buffer,"dkim: VALID")>0){return;}
if(strpos($buffer,"SA info: pyzor:")>0){return;}
if(strpos($buffer,"DSN: sender is credible")>0){return;}
if(strpos($buffer,"mail_via_pipe")>0){return;}
if(strpos($buffer,") ...continue")>0){return;}
if(strpos($buffer,"Cached spam check expired")>0){return;}
if(strpos($buffer,") cached")>0){return;}
if(strpos($buffer,"extra modules loaded:")>0){return;}
if(strpos($buffer,"from MTA(smtp:[127.0.0.1]:10025): 250 2.0.0 Ok")>0){return;}
if(strpos($buffer,"Use of uninitialized value")>0){return;}
if(strpos($buffer,"DecodeShortURLs")>0){return;}
if(strpos($buffer,"FWD via SMTP: <")>0){return;}
if(strpos($buffer,"DKIM-Signature header added")>0){return;}
if(strpos($buffer,"Passed CLEAN, MYNETS LOCAL")>0){return;}
if(strpos($buffer,") Passed CLEAN, [")>0){return;}
if(strpos($buffer,") Passed BAD-HEADER, [")>0){return;}
if(strpos($buffer,") Checking: ")>0){return;}
if(strpos($buffer,") WARN: MIME::Parser error: unexpected end of header")>0){return;}
if(strpos($buffer,") Open relay? Nonlocal recips but not originating")>0){return;}
if(strpos($buffer,": not authenticated")>0){return;}
if(strpos($buffer,": dk_eom() returned status")>0){return;}
if(strpos($buffer,"ASN1_D2I_READ_BIO:not enough data")>0){return;}
if(strpos($buffer,"SpamControl: init_pre_fork on SpamAssassin done")>0){return;}
if(strpos($buffer,": warning: cidr map")>0){return;}
if(strpos($buffer,": Selected group:")>0){return;}
if(strpos($buffer,"Message entity scanning: message CLEAN")>0){return;}
if(strpos($buffer,"New connection on thread")>0){return;}
//if(strpos($buffer,"AM.PDP-SOCK/MYNETS")>0){return;}
if(strpos($buffer,": disconnect from")>0){return;} 
if(strpos($buffer,"sfupdates: KASINFO")>0){return;} 
if(strpos($buffer,": lost connection after CONNECT")>0){return;} 
if(strpos($buffer,"enabling PIX workarounds: disable_esmtp delay_dotcrlf")>0){return;} 
if(strpos($buffer,"Message Aborted!")>0){return;} 
if(strpos($buffer,"WHITELISTED [")>0){return;}
if(strpos($buffer,"COMMAND PIPELINING from")>0){return;}
if(strpos($buffer,"COMMAND COUNT LIMIT from [")>0){return;}
if(strpos($buffer,"]: warning: psc_cache_update:")>0){return;}
if(strpos($buffer,"]: PREGREET")>0){return;}
if(strpos($buffer,": PASS OLD [")>0){return;}
if(strpos($buffer,"]: DNSBL rank")>0){return;}
if(strpos($buffer,"]: HANGUP after")>0){return;}
if(strpos($buffer,": DISCONNECT [")>0){return;}
if(strpos($buffer,"KASNOTICE")>0){return;}
if(strpos($buffer,"KASINFO")>0){return;}
if(strpos($buffer,"]: PASS NEW [")>0){return;}
if(strpos($buffer,"]: COMMAND TIME LIMIT from")>0){return;}
if(strpos($buffer,"Client host triggers FILTER")>0){return;}
if(strpos($buffer,"Starting worker process for IMAP request")>0){return;}
if(strpos($buffer,"IMAP thread exiting")>0){return;}
if(strpos($buffer,"]: seen_db: user ")>0){return;}
if(strpos($buffer,"Client disconnected")>0){return;}
if(strpos($buffer,"starting the Postfix mail system")>0){return;}
if(strpos($buffer,"Postfix mail system is already running")>0){return;}
if(strpos($buffer,": Perl version")>0){return;}
if(strpos($buffer,": No decoder for")>0){return;}
if(strpos($buffer,"Using primary internal av scanner")>0){return;}
if(strpos($buffer,"starting.  /usr/local/sbin/amavisd")>0){return;}
if(strpos($buffer,") smtp resp to data-dot (")>0){return;}
if(strpos($buffer,") TIMING-SA total")>0){return;}
if(strpos($buffer,") sending SMTP response:")>0){return;}
if(strpos($buffer,") TIMING [total")>0){return;}
if(strpos($buffer,") Amavis::")>0){return;}
if(strpos($buffer,"] run_as_subprocess: child done")>0){return;}
if(strpos($buffer,"]: vstream_buf_get_ready:")>0){return;}
if(strpos($buffer,"]: > 127.0.0.1[")>0){return;}
if(strpos($buffer,"]: Using secondary internal")>0){return;}
if(strpos($buffer,"]: rec_get:")>0){return;}
if(strpos($buffer,") p004 1")>0){return;}
if(strpos($buffer,") p001 1")>0){return;}
if(strpos($buffer,") p002 1")>0){return;}
if(strpos($buffer,") p003 1")>0){return;}
if(strpos($buffer,") SPAM-TAG,")>0){return;}
if(strpos($buffer,"]: send attr")>0){return;} 
if(strpos($buffer,") (!)FWD from <")>0){return;} 
if(strpos($buffer,") bounce rescued by:")>0){return;}
if(strpos($buffer,") smtp session: setting")>0){return;}
if(strpos($buffer,") smtp cmd> MAIL FROM:")>0){return;}
if(strpos($buffer,") smtp cmd> RCPT TO:")>0){return;}
if(strpos($buffer,") smtp connection cache")>0){return;}
if(strpos($buffer,") spam_scan: score=")>0){return;}
if(strpos($buffer,") smtp session reuse,")>0){return;}
if(strpos($buffer,") smtp cmd> NOOP")>0){return;}
if(strpos($buffer,") smtp resp to NOOP")>0){return;}
if(strpos($buffer,") smtp cmd> DATA")>0){return;}
if(strpos($buffer,") smtp resp to MAIL")>0){return;}
if(strpos($buffer,") smtp resp to DATA:")>0){return;}
if(strpos($buffer,") smtp cmd> QUIT")>0){return;}
if(strpos($buffer,") smtp session most")>0){return;}
if(strpos($buffer,") smtp resp to RCPT")>0){return;}
if(strpos($buffer,") inspect_dsn:")>0){return;}
if(strpos($buffer,"IO::Socket::INET")>0){return;}
if(strpos($buffer,") smtp resp to greeting")>0){return;}
if(strpos($buffer,") smtp cmd> EHLO")>0){return;}
if(strpos($buffer,") smtp resp to EHLO:")>0){return;}
if(strpos($buffer,") smtp resp to RCPT (")>0){return;}
if(strpos($buffer,"exiting on SIGTERM/SIGINT")>0){return;}
if(strpos($buffer,": ready for work")>0){return;}
if(strpos($buffer,": process started")>0){return;}
if(strpos($buffer,"]: entered child_init_hook")>0){return;}
if(strpos($buffer,"Discarding because of virus")>0){return;}
if(strpos($buffer,"]: SpamControl: init_child on SpamAssassin done")>0){return;}
if(preg_match("#kavmilter\[.+?\[tid.+?New message from:#",$buffer,$re)){return null;}
if(preg_match("#assp\[.+?LDAP Results#",$buffer,$re)){return null;}
if(preg_match("#amavis\[[0-9]+\]:\s+\([0-9\-]+\) FWD from <#",$buffer,$re)){return null;}
if(preg_match("#smtpd\[.+?\]: disconnect from#",$buffer,$re)){return null;}
if(preg_match("#smtpd\[.+?\]:.+?enabling PIX workarounds#",$buffer,$re)){return null;}
if(preg_match("#milter-greylist:.+?skipping greylist#",$buffer,$re)){return null;}
if(preg_match("#milter-greylist:\s+\(.+?greylisted entry timed out#",$buffer,$re)){return null;}

if(preg_match("#assp.+?\[MessageOK\]#",$buffer,$re)){return null;}
if(preg_match("#assp.+?\[NoProcessing\]#",$buffer,$re)){return null;}
if(preg_match("#passed trough amavis and event is saved#",$buffer,$re)){return null;}
if(preg_match("#assp.+?AdminUpdate#",$buffer,$re)){return null;}
if(preg_match("#last message repeated.+?times#",$buffer,$re)){return null;}
if(preg_match("#cyrus\/master.+?about to exec#",$buffer,$re)){return null;}
if(preg_match("#cyrus\/.+?open: user#",$buffer,$re)){return null;}
if(preg_match("#cyrus\/lmtpunix.+?accepted connection#",$buffer,$re)){return null;}
if(preg_match("#cyrus\/lmtpunix.+?Delivered:#",$buffer,$re)){return null;}
if(preg_match("#cyrus\/master.+?process.+?exited#",$buffer,$re)){return null;}
if(preg_match("#lmtpunix.+?mystore: starting txn#",$buffer,$re)){return null;}
if(preg_match("#lmtpunix.+?duplicate_mark#",$buffer,$re)){return null;}
if(preg_match("#lmtpunix.+?mystore: committing txn#",$buffer,$re)){return null;}
if(preg_match("#ctl_cyrusdb.+?archiving#",$buffer,$re)){return null;}
if(preg_match("#assp.+?LDAP - found.+?in LDAPlist;#",$buffer,$re)){return null;}
if(preg_match("#anvil.+?statistics: max#",$buffer,$re)){return null;}
if(preg_match("#smfi_getsymval failed for#",$buffer)){return null;}
if(preg_match("#cyrus\/imap\[.+?Expunged\s+[0-9]+\s+message.+?from#",$buffer)){return null;}
if(preg_match("#cyrus\/imap\[.+?seen_db:\s+#",$buffer)){return null;}
if(preg_match("#cyrus\/[pop3|imap]\[.+?SSL_accept\(#",$buffer)){return null;}
if(preg_match("#cyrus\/[pop3|imap]\[.+?starttls:#",$buffer)){return null;}
if(preg_match("#cyrus\/[pop3|imap]\[.+?:\s+inflate#",$buffer)){return null;}
if(preg_match("#cyrus\/imap.*?fetching\s+user_.+? entry for '#",$buffer)){return null;}
if(preg_match("#cyrus\/.+?\[.+?:\s+accepted connection$#",$buffer)){return null;}
if(preg_match("#cyrus\/.+?\[.+?:\s+deflate\(#",$buffer)){return null;}
if(preg_match("#cyrus\/.+?\[.+?:\s+\=>\s+compressed to#",$buffer)){return null;}
if(preg_match("#filter-module\[.+?:\s+KASINFO#",$buffer)){return null;}
if(preg_match("#exec\.mailbackup\.php#",$buffer)){return null;}
if(preg_match("#kavmilter\[.+?\]:\s+Loading#",$buffer)){return null;}
if(preg_match("#DBERROR: init.+?on berkeley#",$buffer)){return null;}
if(preg_match("#FATAL: lmtpd: unable to init duplicate delivery database#",$buffer)){return null;}
if(preg_match("#skiplist: checkpointed.+?annotations\.db#",$buffer)){return null;}
if(preg_match("#duplicate_prune#",$buffer)){return null;}
if(preg_match("#cyrus\/cyr_expire\[[0-9]+#",$buffer)){return null;}
if(preg_match("#cyrus\/imap.+?SSL_accept#",$buffer)){return null;}
if(preg_match("#cyrus\/pop3.+?SSL_accept#",$buffer)){return null;}
if(preg_match("#cyrus\/imap.+?:\s+executed#",$buffer)){return null;}
if(preg_match("#cyrus\/ctl_cyrusdb.+?recovering cyrus databases#",$buffer)){return null;}
if(preg_match("#cyrus.+?executed#",$buffer)){return null;}
if(preg_match("#postfix\/.+?refreshing the Postfix mail system#",$buffer)){return null;}
if(preg_match("#master.+?reload -- version#",$buffer)){return null;}
if(preg_match("#SQUAT failed#",$buffer)){return null;}
if(preg_match("#lmtpunix.+?sieve\s+runtime\s+error\s+for#",$buffer)){return null;}
if(preg_match("#imapd:Loading hard-coded DH parameters#",$buffer)){return null;}
if(preg_match("#ctl_cyrusdb.+?checkpointing cyrus databases#",$buffer)){return null;}
if(preg_match("#idle for too long, closing connection#",$buffer)){return null;}
if(preg_match("#amavis\[.+?Found#",$buffer)){return null;}
if(preg_match("#amavis\[.+?Module\s+#",$buffer)){return null;}
if(preg_match("#amavis\[.+?\s+loaded$#",trim($buffer))){return null;}

if(preg_match("#amavis\[.+?\s+Internal decoder#",trim($buffer))){return null;}
if(preg_match("#amavis\[.+?\s+Creating db#",trim($buffer))){return null;}
if(preg_match("#smtpd\[.+? warning:.+?address not listed for hostname#",$buffer)){return null;}
if(preg_match("#zarafa-dagent\[.+?Delivered message to#",$buffer)){return null;}
if(preg_match("#postfix\/policyd-weight\[.+?SPAM#",$buffer)){return null;}
if(preg_match("#postfix\/policyd-weight\[.+?decided action=550#",$buffer)){return null;}
if(preg_match("#cyrus\/lmtp\[.+?Delivered#",$buffer)){return null;}
if(preg_match("#ESMTP::.+?\/var\/amavis\/tmp\/amavis#",$buffer)){return null;}
if(preg_match("#zarafa-dagent.+?Client disconnected#",$buffer)){return null;}
if(preg_match("#zarafa-dagent.+?Failed to resolve recipient#",$buffer)){return null;}

// MIMEDFANG
if(strpos($buffer,"MIMEDefang alive. workersReservedForLoopback")>0){return;}
if(strpos($buffer,"Received SIGTERM: Stopping workers and terminating")>0){return;}
if(strpos($buffer,"started; minWorkers=")>0){return;}
if(strpos($buffer,": mi_stop=1")>0){return;}
if(strpos($buffer,"resource usage: req=")>0){return;}
if(strpos($buffer,"]: Reap: worker")>0){return;}
if(strpos($buffer,"]: Killing idle worker")>0){return;}
if(strpos($buffer,"Bringing workers up to minWorkers")>0){return;}
if(strpos($buffer,"stderr: netset: cannot include")>0){return;}
if(strpos($buffer,"MySQL: from=<")>0){return;}
if(strpos($buffer,"Checking Forged message:")>0){return;}
if(strpos($buffer,"if_is_AnAutoreply")>0){return;}
if(strpos($buffer,"is_in_myNetwork")>0){return;}
if(strpos($buffer,"PostGreSQL Connect:")>0){return;}
if(strpos($buffer,"Processing: Next")>0){return;}
if(strpos($buffer,"MimeDefangAutoCompress ==")>0){return;}
if(strpos($buffer,"ROM mimedefang_antivirus")>0){return;}
if(strpos($buffer,": Virus scanner 0")>0){return;}
if(strpos($buffer,"isSpamAssassin()")>0){return;}
if(strpos($buffer,"filter_end(")>0){return;}
if(strpos($buffer,"Backup Message: ID:")>0){return;}
if(strpos($buffer,"INSERT INTO mimedefang_stats")>0){return;}
if(strpos($buffer,"isBackup ID == ")>0){return;}
if(strpos($buffer,": Outgoing message ->")>0){return;}
if(strpos($buffer,": MimeDefangDisclaimer =")>0){return;}
if(strpos($buffer,": MimeDefangClamav ==")>0){return;}
if(strpos($buffer,": filter_begin()")>0){return;}
if(strpos($buffer,": is_whitelisted_addr:")>0){return;}
if(strpos($buffer,": filter() [")>0){return;}
if(strpos($buffer,"]: : AlwaysScan")>0){return;}
if(strpos($buffer,": SpamAssassin hit:")>0){return;}
if(strpos($buffer,": Infected:0 Clamac:")>0){return;}
if(strpos($buffer,": Infected:1 Clamac:")>0){return;}
if(strpos($buffer,"Discarding because of virus")>0){return;}
if(strpos($buffer,"Tempfailing because filter instructed us to")>0){return;}
if(strpos($buffer,"filter:  tempfail=1")>0){return;}
if(strpos($buffer,"SKIP FOR AV")>0){return;}
if(strpos($buffer, "MGREYSTATS")>0){$md5=md5($buffer);@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/MGREYSTATS/$md5", $buffer);return;}

if($GLOBALS["VERBOSE"]){echo "PREG_MATCH START...\n";}

// ---------------------------------------------------------------------------------------------------------------
if(stripos($buffer,"opendkim")>0){
	include_once(dirname(__FILE__).'/ressources/class.opendkim.maillog.inc');
	if(parse_opendkim($buffer)){return;}
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#MIMEDEFANG=<(.+?)>#", $buffer,$re)){
	$re[1]=str_replace("NOW()", "'".date("Y-m-d H:i:s")."'", $re[1]);
	$data=explode("|||",$re[1]);
	$data[]="'Content Filter'";
	$sql="INSERT INTO smtplog (zdate,msgid,ipaddr,relay_s,frommail,fromdomain,tomail,todomain,subject,size,spamscore,spamreport,disclaimer,backuped,infected,filtered,whitelisted,compressed,stripped,smtp_code,reason) VALUES (".@implode(",", $data).")";
	$GLOBALS["CLASS_POSTGRES"]->QUERY_SQL($sql);
	if(!$GLOBALS["CLASS_POSTGRES"]->ok){
		events(__CLASS__." Line.".__LINE__.": PostgreSQL Error {$GLOBALS["CLASS_POSTGRES"]->mysql_error}");
		events($sql);
	}
	return true;
}
//-------------------------------------------------------------------------------------------------------------
if(preg_match("#ARTICA-ACTION: QUARANTINE <(.+?)>#", $buffer,$re)){
	shell_exec("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.mimedefang.quarantine.php --path \"{$re[1]}\" >/dev/null 2>&1 &");
	return true;
}
//---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?): to=<(.*?)>, relay=(.+?)\[(.+?)\]:.*?status=bounced.*?said.*?Recipient address rejected#", $buffer,$re)){
    $ARRAY["MESSAGE_ID"]=$re[1];
    $ARRAY["RECIPIENT"]=$re[2];
    $ARRAY["REFUSED"]=1;
    $ARRAY["REJECTED"]="Recipient rejected";
    $GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
    if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
    return true;
}

//---------------------------------------------------------------------------------------------------------------
if(preg_match("#bounce\[.*?:\s+(.+?): sender non-delivery notification:\s+([A-Z0-9]+)#", $buffer,$re)){
    $ARRAY["MESSAGE_ID"]=$re[2];
    $ARRAY["SENDER"]="Postmaster";
    $ARRAY["RECIPIENT"]=null;
    $ARRAY["REFUSED"]=1;
    $ARRAY["REJECTED"]="non-delivery";
    $GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
    if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
    return true;

}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+warning:\s+problem talking to server\s+127\.0\.0\.1:10032:#", $buffer,$re)){
    $file="/etc/artica-postfix/croned.1/artica-policy.service.time";
    if(file_time_min($file)>2){
        @unlink($file);
        @file_put_contents($file,time());
        $cmdline="{$GLOBALS["NOHUP_PATH"]} /etc/init.d/artica-policy restart >/dev/null 2>&1 &";
        events($cmdline);
        shell_exec($cmdline);
    }
    return true;
}

if(preg_match("#mimedefang\[.*?NOQUEUE: Error from multiplexor: error: No free workers#",$buffer,$re)) {


}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+to=<(.*?)>,\s+orig_to=<(.*?)>,\s+relay=(.*?)\[(.+?)\].*?status=sent#",$buffer,$re)){
    $ARRAY["MESSAGE_ID"]=$re[1];
    $ARRAY["RECIPIENT"]=$re[3];
    $ARRAY["IPADDR"]=$re[4];
    $ARRAY["HOSTNAME"]=$re[5];
    $ARRAY["REFUSED"]=0;
    $ARRAY["SENT"]=1;
    $ARRAY["REJECTED"]="Redirected";
    $GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
    if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
    return true;

}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: artica-policy: from\s+(.*?)\[(.+?)\]\s+from=<(.*?)>\s+to=<(.+?)>\s+whitelisted-(.+)#",$buffer,$re)){
    $ARRAY["MESSAGE_ID"]=null;
    $ARRAY["RECIPIENT"]=$re[4];
    $ARRAY["SENDER"]=$re[3];
    $ARRAY["IPADDR"]=$re[2];
    $ARRAY["HOSTNAME"]=$re[1];
    $ARRAY["REFUSED"]=0;
    $ARRAY["SENT"]=0;
    $ARRAY["REJECTED"]="Policy accept:{$re[5]}";
    $GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
    if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
    return true;
}

// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]: (.+?):\s+discard:.*?header.*?\s+from\s+(.*?)\[(.+?)\]; from=<(.*?)> to=<(.*?)>.*?helo=<(.*?)>:\s+(.+)#",$buffer,$re)) {
    $ARRAY["MESSAGE_ID"]=$re[1];
    $ARRAY["RECIPIENT"]=$re[5];
    $ARRAY["SENDER"]=$re[4];
    $ARRAY["IPADDR"]=$re[3];
    $ARRAY["HOSTNAME"]=$re[2];
    $ARRAY["REFUSED"]=1;
    $ARRAY["SENT"]=0;
    $ARRAY["REJECTED"]="Discard:{$re[7]}";
    if($ARRAY["HOSTNAME"]=="unknown"){$ARRAY["HOSTNAME"]=$re[6];}
    $GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
    if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
    return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]: NOQUEUE: milter-reject: MAIL from\s+(.+?)\[(.+?)\]:.*?Service unavailable.*?from=<(.*?)>.*?helo=<(.*?)>#",$buffer,$re)) {
    $ARRAY["MESSAGE_ID"]=null;
    $ARRAY["RECIPIENT"]=null;
    $ARRAY["SENDER"]=$re[3];
    $ARRAY["IPADDR"]=$re[2];
    $ARRAY["HOSTNAME"]=$re[1];
    $ARRAY["REFUSED"]=1;
    $ARRAY["SENT"]=0;
    $ARRAY["REJECTED"]="Service unavailable";
    if($ARRAY["HOSTNAME"]=="unknown"){$ARRAY["HOSTNAME"]=$re[4];}
    $GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
    if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
    return true;

}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+to=<(.+?)>, relay=none,.*?status=bounced\s+\(.*?loops back to myself#",$buffer,$re)) {
    $ARRAY["MESSAGE_ID"]=$re[1];
    $ARRAY["RECIPIENT"]=$re[2];
    $ARRAY["IPADDR"]="127.0.0.1";
    $ARRAY["HOSTNAME"]="localhost";
    $ARRAY["REFUSED"]=1;
    $ARRAY["SENT"]=0;
    $ARRAY["REJECTED"]="loops back to myself";
    $GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
    if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
    return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]: NOQUEUE: milter-reject:.*?from\s+(.*?)\[(.+?)\]:.*?Go away.*?from=<(.*?)> to=<(.*?)>.*?helo=<(.*?)>#",$buffer,$re)) {
    $ARRAY["MESSAGE_ID"]=null;
    $ARRAY["SENDER"]=$re[3];
    $ARRAY["RECIPIENT"]=$re[4];
    $ARRAY["HOSTNAME"]=$re[1];
    $ARRAY["IPADDR"]=$re[2];
    $ARRAY["REFUSED"]=1;
    $ARRAY["SENT"]=0;
    $ARRAY["REJECTED"]="Blacklisted";
    if($ARRAY["HOSTNAME"]=="unknown"){$ARRAY["HOSTNAME"]=$re[5];}
    $GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
    if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
    return true;
}

// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+to=<(.*?)>,\s+relay=(.+?)\[(.+?)\].*?status=(bounced|deferred).*?said:\s+[0-9]+\s+[0-9\.]+\s+(.+?)\s+\(in#",$buffer,$re)) {
    $ARRAY["MESSAGE_ID"]=$re[1];
    $ARRAY["SENDER"]=null;
    $ARRAY["RECIPIENT"]=$re[2];
    $ARRAY["HOSTNAME"]=$re[3];
    $ARRAY["IPADDR"]=$re[4];
    $ARRAY["REFUSED"]=1;
    $ARRAY["SENT"]=0;
    $ARRAY["REJECTED"]=$re[6];
    $GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
    if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
    return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+to=<(.*?)>,\s+relay.*?status=deferred.*?connect to\s+(.+?)\[(.+?)\].*?No route to host#",$buffer,$re)) {
    $ARRAY["MESSAGE_ID"]=$re[1];
    $ARRAY["SENDER"]=null;
    $ARRAY["RECIPIENT"]=$re[2];
    $ARRAY["HOSTNAME"]=$re[3];
    $ARRAY["IPADDR"]=$re[4];
    $ARRAY["REFUSED"]=1;
    $ARRAY["SENT"]=0;
    $ARRAY["REJECTED"]="No route to host";
    $GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
    if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
    return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+NOQUEUE:\s+reject.*?from\s+(.+?)\[(.+?)\].*?: Client host rejected:\s+Artica Reputation Blacklisted\s+(.+?);\s+from=<(.*?)>\s+to=<(.*?)>.*?helo=<(.*?)>#",$buffer,$re)) {
    $ARRAY["MESSAGE_ID"]=null;
    $ARRAY["SENDER"]=$re[4];
    $ARRAY["RECIPIENT"]=$re[5];
    $ARRAY["HOSTNAME"]=$re[1];
    $ARRAY["IPADDR"]=$re[2];
    $ARRAY["REFUSED"]=1;
    $ARRAY["SENT"]=0;
    $ARRAY["REJECTED"]="Artica Reputation {$re[3]}";
    if($ARRAY["HOSTNAME"]=="unknown"){$ARRAY["HOSTNAME"]=$re[6];}
    $GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
    if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
    return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+to=<(.*?)>,\s+relay=(.+?)\[(.+?)\].*?status=.*?refused to talk to me#",$buffer,$re)) {
    $ARRAY["MESSAGE_ID"]=$re[1];
    $ARRAY["RECIPIENT"]=$re[2];
    $ARRAY["HOSTNAME"]=$re[3];
    $ARRAY["IPADDR"]=$re[4];
    $ARRAY["REFUSED"]=1;
    $ARRAY["SENT"]=0;
    $ARRAY["REJECTED"]="Communication refused";
    $GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
    if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
    return true;

}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+SCRINFO:\s+(.+?)\s+from=<(.+?)>\s+to=<(.*?)>\s+([0-9\.]+).*?X-Spam-Status#",$buffer,$re)) {
    $ARRAY["MESSAGE_ID"]=$re[1];
    $ARRAY["HOSTNAME"]=$re[2];
    $ARRAY["SENDER"]=$re[3];
    $ARRAY["RECIPIENT"]=$re[4];
    $ARRAY["SPAMSCORE"]=$re[5];
    $ARRAY["REJECTED"]="Probable SPAM";
    $ARRAY["REFUSED"]=0;
    $ARRAY["SENT"]=1;
    $ARRAY["IPADDR"]=gethostbyname($ARRAY["HOSTNAME"]);
    $GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
    if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
    return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+NOQUEUE: reject:.*?from (.+?)\[(.+?)\]:.*?<(.+?)>: Relay access denied;.*?helo=<(.+?)>#",$buffer,$re)) {
    $ARRAY["MESSAGE_ID"]=null;
    $ARRAY["RECIPIENT"]=$re[3];
    $ARRAY["HOSTNAME"]=$re[1];
    $ARRAY["IPADDR"]=$re[2];
    $ARRAY["REJECTED"]="Relay access denied";
    $ARRAY["REFUSED"]=1;
    $ARRAY["SENT"]=0;
    if($ARRAY["HOSTNAME"]=="unknown"){$ARRAY["HOSTNAME"]=$re[4];}
    $GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
    if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
    return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+redirect:\s+header Subject:\s+(.+?)\s+from\s+(.+?)\[(.+?)\];\s+from=<(.+?)>\s+to=<(.+?)>.*?helo=<(.+?)>:\s+(.+)#",$buffer,$re)){
    $ARRAY["MESSAGE_ID"]=$re[1];
    $ARRAY["SENDER"]=$re[5];
    $ARRAY["RECIPIENT"]=$re[6];
    $ARRAY["HOSTNAME"]=$re[3];
    $ARRAY["IPADDR"]=$re[4];
    $ARRAY["IPADDR"]=$re[4];
    $ARRAY["SUBJECT"]=$re[2];
    if($ARRAY["HOSTNAME"]=="unknown"){$ARRAY["HOSTNAME"]=$re[8];}
    $ARRAY["REDIRECTED_TO"]=$re[8];
    $ARRAY["REJECTED"]="Redirect:Subject";
    $ARRAY["REFUSED"]=1;
    $ARRAY["SENT"]=0;
    $GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
    if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
    return true;
}
// ---------------------------------------------------------------------------------------------------------------

if(preg_match("#\]:\s+(.+?):\s+to=<(.+?)>,.*?status=deferred.*?to\s+(.+?)\[(.+?)\].*?:[0-9]+:\s+(.+?)\)#",$buffer,$re)){
    $ARRAY["MESSAGE_ID"]=$re[1];
    $ARRAY["RECIPIENT"]=$re[2];
    $ARRAY["HOSTNAME"]=$re[3];
    $ARRAY["IPADDR"]=$re[4];
    $ARRAY["REJECTED"]=$re[5];
    $ARRAY["REFUSED"]=1;
    $ARRAY["SENT"]=0;
    $GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
    if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
    return true;
}

// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+to=<(.*?)>,\s+relay=(.*?)\[(.+?)\].*?status=sent\s+#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=$re[1];
    $ARRAY["RECIPIENT"]=$re[2];
    $ARRAY["HOSTNAME"]=$re[3];
    $ARRAY["IPADDR"]=$re[4];
    $ARRAY["REJECTED"]="Sent";
    $ARRAY["REFUSED"]=0;
    $ARRAY["SENT"]=1;
    $GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
    if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
    return true;
}
// ---------------------------------------------------------------------------------------------------------------

if(preg_match("#\]:\s+(.+?):\s+conversation with (.*?)\[(.+?)\]\s+timed out while sending\s+([A-Z\s]+)#",$buffer,$re)){
    $ARRAY["MESSAGE_ID"]=$re[1];
    $ARRAY["IPADDR"]=$re[3];
    $ARRAY["HOSTNAME"]=$re[2];
    $ARRAY["REFUSED"]=1;
    $ARRAY["REJECTED"]="Timeout after ".trim($re[4]);
    $GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
    if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
    return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+to=<(.*?)>,\s+relay=(.*?)\[(.+?)\].*?status=deferred.*?said.*?cannot find your reverse hostname,#",$buffer,$re)){
    $ARRAY["MESSAGE_ID"]=$re[1];
    $ARRAY["RECIPIENT"]=$re[2];
    $ARRAY["IPADDR"]=$re[4];
    $ARRAY["HOSTNAME"]=$re[3];
    $ARRAY["REFUSED"]=1;
    $ARRAY["REJECTED"]="Reverse Hostname failed";
    $GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
    if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
    return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+host\s+(.*?)\[(.+?)\].*said.*?cannot find your reverse hostname#",$buffer,$re)) {
    $ARRAY["MESSAGE_ID"] = $re[1];
    $ARRAY["HOSTNAME"] = $re[2];
    $ARRAY["IPADDR"] = $re[3];
    $ARRAY["REJECTED"] = "Reverse Hostname failed";
    $GLOBALS["maillog_tools"]->berkleydb_relatime_write(null, $ARRAY);
    if ($GLOBALS["VERBOSE"]) {
        echo "FINISH\n";
    }
    return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+NOQUEUE:\s+reject:\s+RCPT\s+from\s+(.*?)\[(.+?)\]:.*?blocked using\s+(.+?);.*?from=<(.*?)>\s+to=<(.*?)>.*?helo=<(.*?)>#",$buffer,$re)){
    $ARRAY["MESSAGE_ID"]=null;
    $ARRAY["HOSTNAME"]=$re[1];
    $ARRAY["IPADDR"]=$re[2];
    if($ARRAY["HOSTNAME"]=="unknown"){$ARRAY["HOSTNAME"]=$re[6];}
    $ARRAY["SENDER"]=$re[4];
    $ARRAY["RECIPIENT"]=$re[5];
    $ARRAY["REJECTED"]="rbl:{$re[3]}";
    $ARRAY["REFUSED"]=1;
    $GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
    if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
    return true;
}

// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+milter-reject.*?from\s+(.+?)\[(.+?)\]:.*?BLK\[([0-9]+)\].*?from=<(.*?)>\s+to=<(.*?)>\s+.*?helo=<(.*?)>#", $buffer,$re)){
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["HOSTNAME"]=$re[2];
	$ARRAY["IPADDR"]=$re[3];
	if($ARRAY["HOSTNAME"]=="unknown"){$ARRAY["HOSTNAME"]=$re[7];}
	$ARRAY["ACLID"]=$re[4];
	$ARRAY["SENDER"]=$re[5];
	$ARRAY["RECIPIENT"]=$re[6];
	$ARRAY["REJECTED"]="Blacklisted";
	$ARRAY["REFUSED"]=1;
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+NOQUEUE: milter-reject: MAIL from (.*?)\[(.+?)\]:.*?5\.7\.1\s+BLK\[([0-9]+)\].*?from=<(.*?)>.*?helo=<(.*?)>#", $buffer,$re)){
	$ARRAY["MESSAGE_ID"]=null;
	$ARRAY["HOSTNAME"]=$re[1];
	$ARRAY["IPADDR"]=$re[2];
	$ARRAY["ACLID"]=$re[3];
	$ARRAY["SENDER"]=$re[4];
	$ARRAY["REJECTED"]="Blacklisted";
	$ARRAY["REFUSED"]=1;
	if($ARRAY["HOSTNAME"]=="unknown"){$ARRAY["HOSTNAME"]=$re[5];}
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+BACKUP:\s+hostname=<(.+?)>\s+ip=<(.+?)>\s+.*?file=<(.+?)>\s+from=<(.+?)>\s+to=<(.+?)>\s+#", $buffer,$re)){
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["HOSTNAME"]=$re[2];
	$ARRAY["IPADDR"]=$re[3];
	$filename=$re[4];
	$ARRAY["SENDER"]=$re[5];
	$ARRAY["SEQUENCE"]=2;
	$ARRAY["REJECTED"]="Message backuped";
	
	$recipients=explode(";",$re[6]);
	foreach ($recipients as $to){
		if(trim($to)==null){continue;}
		$ARRAY["RECIPIENT"]=$to;
		$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	}
	$cmdline="{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.mimedefang.quarantine.php --path \"{$filename}\" >/dev/null 2>&1 &";
	events($cmdline);
	shell_exec($cmdline);
	return true;
}
//---------------------------------------------------------------------------------------------------------------
if(preg_match("#\[.*?:\s+(.+?): to=<(.*?)>, relay=(.+?)\[(.+?)\].*?status=deferred.*?timed out#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["RECIPIENT"]=$re[2];
	$ARRAY["HOSTNAME"]=$re[3];
	$ARRAY["IPADDR"]=$re[4];
	$ARRAY["REFUSED"]=1;
	$ARRAY["REJECTED"]="Relay Timed Out";
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
//---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+to=<(.*?)>,\s+relay=(.*?)\[(.+?)\]:.*?status=bounced.*?Client does not have permissions to send as this sender#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["RECIPIENT"]=$re[2];
	$ARRAY["HOSTNAME"]=$re[3];
	$ARRAY["IPADDR"]=$re[4];
	$ARRAY["REFUSED"]=1;
	$ARRAY["REJECTED"]="Permission denied";
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
//---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+to=<(.*?)>,\s+relay=(.*?)\[(.+?)\]:.*?status=deferred.*?SASL authentication failed#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["RECIPIENT"]=$re[2];
	$ARRAY["HOSTNAME"]=$re[3];
	$ARRAY["IPADDR"]=$re[4];
	$ARRAY["REFUSED"]=1;
	$ARRAY["REJECTED"]="Authentication failed";
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
//---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+to=<(.*?)>,\s+relay=(.*?)\[(.*?)\].*?status=sent#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["RECIPIENT"]=$re[2];
	$ARRAY["HOSTNAME"]=$re[3];
	$ARRAY["IPADDR"]=$re[4];
	$ARRAY["SENT"]=1;
	$ARRAY["REFUSED"]=0;
	$ARRAY["REJECTED"]="Sent";
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
//---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+to=<(.*?)>,\s+relay=.*?status=deferred.*?connect to (.*?)\[(.*?)\]:.*?Connection refused#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["RECIPIENT"]=$re[2];
	$ARRAY["HOSTNAME"]=$re[3];
	$ARRAY["IPADDR"]=$re[4];
	$ARRAY["REFUSED"]=1;
	$ARRAY["REJECTED"]="Connection refused";
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+to=<(.*?)>,\s+relay=(.*?)\[(.+?)\]:.*?status=bounced.*?Client was not authenticated#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["RECIPIENT"]=$re[2];
	$ARRAY["HOSTNAME"]=$re[3];
	$ARRAY["IPADDR"]=$re[4];
	$ARRAY["REFUSED"]=1;
	$ARRAY["REJECTED"]="Authentication required";
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]: warning: Illegal address syntax from\s+(.*?)\[([0-9\.]+)\] in MAIL command: <(.+?)>#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=null;
	$ARRAY["HOSTNAME"]=$re[1];
	$ARRAY["IPADDR"]=$re[2];
	$ARRAY["SENDER"]=$re[3];
	$ARRAY["REFUSED"]=1;
	$ARRAY["REJECTED"]="Bad Syntax";
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]: warning: Illegal address syntax from\s+(.*?)\[([0-9\.]+)\] in RCPT command: <(.+?)>#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=null;
	$ARRAY["HOSTNAME"]=$re[1];
	$ARRAY["IPADDR"]=$re[2];
	$ARRAY["RECIPIENT"]=$re[3];
	$ARRAY["REFUSED"]=1;
	$ARRAY["REJECTED"]="Bad Syntax";
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+.*?Quarantine:\s+.*?from\s+(.*?):.*?SPAM\s+\[(.+?)\];\s+.*?\[(.+?)\] using ClamAV;.*?from=<(.+?)>\s+to=<(.+?)>\s+#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["HOSTNAME"]=$re[2];
	$ARRAY["REJECTED"]=$re[3];
	$ARRAY["IPADDR"]=$re[4];
	$ARRAY["SENDER"]=$re[5];
	$ARRAY["RECIPIENT"]=$re[6];
	$ARRAY["SEQUENCE"]=2;
	$ARRAY["REFUSED"]=1;
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: reject: RCPT from\s+(.*?)\[(.+?)\]:.*?Client host rejected: Go Away.*?rule id\s+([0-9]+);\s+from=<(.*?)>\s+to=<(.*?)>.*?helo=<(.*?)>#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=null;
	$ARRAY["HOSTNAME"]=$re[1];
	$ARRAY["IPADDR"]=$re[2];
	$ARRAY["SENDER"]=$re[4];
	$ARRAY["RECIPIENT"]=$re[5];
	if($ARRAY["HOSTNAME"]=="unknown"){$ARRAY["HOSTNAME"]=$re[6];}
	$ARRAY["ACLID"]=$re[3];
	$ARRAY["REJECTED"]="Blacklisted";
	$ARRAY["SEQUENCE"]=0;
	$ARRAY["REFUSED"]=1;
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: reject:.+?from.+?\[([0-9\.]+)\]:.+?<(.+?)>:\s+Recipient address rejected: User unknown in local recipient table;\s+from=<(.+?)>\s+to=<(.+?)>#",$buffer,$re)){
	$ARRAY["REJECTED"]="User unknown";
	//$reason,$from=null,$to=null,$server,$ipaddr=null
	$ARRAY["HOSTNAME"]=$re[1];
	$ARRAY["IPADDR"]=$re[2];
	$ARRAY["SENDER"]=$re[3];
	$ARRAY["RECIPIENT"]=$re[4];
	$ARRAY["SEQUENCE"]=0;
	$ARRAY["REFUSED"]=1;
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
if(preg_match("#NOQUEUE: reject: RCPT from.+?<(.+?)>: Recipient address rejected: User unknown in relay recipient table;.+?to=<(.+?)> proto=SMTP#",
		$buffer,$re)){
	
	$ARRAY["REJECTED"]="User unknown";
	$ARRAY["HOSTNAME"]=$re[1];
	$ARRAY["IPADDR"]=$re[2];
	$ARRAY["RECIPIENT"]=$re[2];
	$ARRAY["SEQUENCE"]=0;
	$ARRAY["REFUSED"]=1;
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;

}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+NOQUEUE:\s+accept:\s+from\s+(.*?)\s+ip=<(.*?)> Globally Whitelisted; from=<(.*?)> proto=milter#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["HOSTNAME"]=$re[2];
	$ARRAY["IPADDR"]=$re[3];
	$ARRAY["SENDER"]=$re[4];
	$ARRAY["REJECTED"]="Whitelisted";
	$ARRAY["SEQUENCE"]=2;
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+.*?END-OF-MESSAGE\s+from\s+(.*?)\[(.+?)\]:.*?DISCARD action;\s+from=<(.+?)>\s+to=<(.+?)>.*?helo=<(.+?)>#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["HOSTNAME"]=$re[2];
	$ARRAY["IPADDR"]=$re[3];
	$ARRAY["SENDER"]=$re[4];
	$ARRAY["RECIPIENT"]=$re[5];
	$ARRAY["REJECTED"]="Discard";
	$ARRAY["REFUSED"]=1;
	$ARRAY["SEQUENCE"]=2;
	if($ARRAY["HOSTNAME"]=="unknown"){$ARRAY["HOSTNAME"]=$re[6];}
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}

// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+NOQUEUE:\s+Quarantine:.*?from\s+(.*?):.*?score\s+([0-9\.]+);\s+Client host\s+\[(.*?)\]\s+blocked using Spamassassin;\s+from=<(.*?)>\s+to=<(.*?)>#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["HOSTNAME"]=$re[2];
	$ARRAY["SPAMSCORE"]=$re[3];
	$ARRAY["IPADDR"]=$re[4];
	$ARRAY["SENDER"]=$re[5];
	$ARRAY["RECIPIENT"]=$re[6];
	$ARRAY["REJECTED"]="Quarantine";
	$ARRAY["REFUSED"]=1;
	$ARRAY["SEQUENCE"]=2;
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\/postsuper.*?:\s+(.+?): removed#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["REFUSED"]=1;
	$ARRAY["REJECTED"]="Removed from queue";
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+SCRINFO:\s+(.*?)\s+from=<(.*?)>\s+to=<(.*?)>\s+([0-9\.]+).*?QUARANTINE#",$buffer,$re)){return true;}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+NOQUEUE: reject: [A-Za-z+]+\s+from\s+(.*?)\[(.+?)\]:\s+.*?Client host rejected:\s+cannot find your hostname,\s+.*?from=<(.*?)>\s+to=<(.*?)>.*?helo=<(.*?)>#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=null;
	$ARRAY["HOSTNAME"]=$re[1];
	$ARRAY["IPADDR"]=$re[2];
	$ARRAY["REFUSED"]=1;
	$ARRAY["REJECTED"]="Hostname not found";
	$ARRAY["SENDER"]=$re[3];
	$ARRAY["RECIPIENT"]=$re[4];
	if($ARRAY["HOSTNAME"]=="unknown"){$ARRAY["HOSTNAME"]=$re[5];}
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+milter-reject: END-OF-MESSAGE from\s+(.*?)\[(.+?)\]:.*?Service unavailable.*?try again later.*?from=<(.*?)>\s+to=<(.*?)>.*?helo=<(.*?)>#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["HOSTNAME"]=$re[2];
	$ARRAY["IPADDR"]=$re[3];
	$ARRAY["REFUSED"]=1;
	$ARRAY["REJECTED"]="Service unavailable";
	$ARRAY["SENDER"]=$re[4];
	$ARRAY["RECIPIENT"]=$re[5];
	if($ARRAY["HOSTNAME"]=="unknown"){$ARRAY["HOSTNAME"]=$re[6];}
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
	
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#stderr: DBI connect\('(.+?)',.*?unable to open database file#",$buffer,$re)){
	$filepath=$re[1];
	$dirname=dirname($filepath);
	if(!is_dir($dirname)){@mkdir($dirname,0755,true);}
	@chown($dirname,"postfix");
	@chgrp($dirname, "postfix");
	@chmod($dirname, 0755);
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?): Error from multiplexor: ERR No response from worker#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["REFUSED"]=1;
	$ARRAY["REJECTED"]="Scanner Engine error";
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+lost connection after\s+(.+?)\s+from\s+(.+)\[(.+?)\]#",$buffer,$re)){
	$ARRAY["IPADDR"]=$re[3];
	$ARRAY["HOSTNAME"]=$re[2];
	$ARRAY["REFUSED"]=1;
	$ARRAY["REJECTED"]="Disconnect after {$re[1]}";
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]: timeout after\s+(.+?)\s+from\s+(.*?)\[(.+)\]#",$buffer,$re)){
	$ARRAY["IPADDR"]=$re[3];
	$ARRAY["HOSTNAME"]=$re[2];
	$ARRAY["REFUSED"]=1;
	$ARRAY["REJECTED"]="Timeout after {$re[1]}";
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#warning: connect to Milter service unix:(.+?): Permission denied#",$buffer,$re)){
	$filepath=$re[1];
	squid_admin_mysql(1, "Permission denied on ".basename($filepath)." action=fix issue", $filepath,__FILE__,__LINE__);
	@chown($filepath,"postfix");
	@chgrp($filepath, "postfix");
	@chmod($filepath, 0755);
	system("/usr/sbin/postfix reload");
	return true;
}
if(preg_match("#Host offered STARTTLS: \[(.+?)\]#",$buffer,$re)){
	
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+NOQUEUE:\s+reject:\s+RCPT from\s+(.*?)\[(.+?)]:.*?:\s+Sender address rejected:.*?from=<(.*?)>\s+to=<(.*?)>.*?helo=<(.*?)>#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=null;
	$ARRAY["HOSTNAME"]=$re[1];
	$ARRAY["IPADDR"]=$re[2];
	$ARRAY["SENDER"]=$re[3];
	$ARRAY["RECIPIENT"]=$re[4];
	$ARRAY["REFUSED"]=1;
	$ARRAY["REJECTED"]="Sender rejected";
	if($ARRAY["HOSTNAME"]=="unknown"){$ARRAY["HOSTNAME"]=$re[5];}
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH ".__LINE__."\n";}
	return true;
	
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+NOQUEUE:\s+reject: RCPT from\s+(.*?)\[(.+?)\]:.*?<(.*?)>: Relay access denied; from=<(.+?)> to=<(.*?)> proto=.*?helo=<(.+?)>#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=null;
	$ARRAY["HOSTNAME"]=$re[1];
	$ARRAY["IPADDR"]=$re[2];
	$ARRAY["RECIPIENT"]=$re[3];
	$ARRAY["SENDER"]=$re[4];
	if($ARRAY["HOSTNAME"]=="unknown"){$ARRAY["HOSTNAME"]=$re[6];}
	$ARRAY["REJECTED"]="Relay access denied";
	$ARRAY["REFUSED"]=1;
	if($ARRAY["RECIPIENT"]==null){$ARRAY["RECIPIENT"]=$re[5];}
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH ".__LINE__."\n";}
	return true;
	
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+host\s+(.*?)\[(.+?)\]\s+said.*?<(.*?)>:\s+Recipient address rejected#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["HOSTNAME"]=$re[2];
	$ARRAY["IPADDR"]=$re[3];
	$ARRAY["RECIPIENT"]=$re[4];
	$ARRAY["REJECTED"]="Bad Recipient";
	$ARRAY["REFUSED"]=1;
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+lost connection with\s+(.+?)\[(.+?)\]\s+while\s+receiving\s+the\s+initial\s+server\s+greeting#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["HOSTNAME"]=$re[2];
	$ARRAY["IPADDR"]=$re[3];
	$ARRAY["REJECTED"]="Lost Connection";
	$ARRAY["REFUSED"]=1;
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]: NOQUEUE: reject: RCPT from\s+(.*?)\[(.+?)\]:\s+.*?Service unavailable;.*?blocked using.*?;\s+(.+?);\s+from=<(.*?)>\s+to=<(.+?)>.*?helo=<(.*?)>#",$buffer,$re)){
	$ARRAY["HOSTNAME"]=$re[1];
	$ARRAY["IPADDR"]=$re[2];
	if(preg_match("#Barracuda Reputation#", $re[3])){$re[3]="Barracuda Reputation";}
	$ARRAY["REJECTED"]="rbl:{$re[3]}";
	$ARRAY["SENDER"]=$re[4];
	$ARRAY["RECIPIENT"]=$re[5];
	$ARRAY["REFUSED"]=1;
	$helo=trim($re[6]);
	if($ARRAY["HOSTNAME"]=="unknown"){if($helo<>null){$ARRAY["HOSTNAME"]=$helo;}}
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#milter-discard: END-OF-MESSAGE from\s+(.*?)\[(.+?)\]: milter triggers DISCARD action; from=<(.*?)> to=<(.+?)> proto=ESMTP helo=<(.+?)>#",$buffer,$re)){
	$date=date("Y-m-d H:i:s");
	$hostname=$re[1];
	$ARRAY["IPADDR"]=$re[2];
	$ARRAY["REJECTED"]="Antispam discard";
	$ARRAY["SENDER"]=$re[3];
	$ARRAY["RECIPIENT"]=$re[4];
	$ARRAY["REFUSED"]=1;
	$helo=$re[5];
	if($hostname=="unknown"){$hostname=$helo;}
	$ARRAY["HOSTNAME"]=$hostname;
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
//---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]: (.+?): to=<(.*?)>,.*?status=deferred.*?connect to\s+(.*?)\[(.+?)\].*?timed out#",$buffer,$re)){
	if($GLOBALS["VERBOSE"]){echo "$buffer matches in LINE ".__LINE__."\n";}
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["RECIPIENT"]=$re[2];
	$ARRAY["HOSTNAME"]=$re[3];
	$ARRAY["IPADDR"]=$re[4];
	$ARRAY["REFUSED"]=1;
	$ARRAY["REJECTED"]="Relay Timed Out";
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write($ARRAY["MESSAGE_ID"],$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
	
}
//---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+connect to (.*?)\[(.+?)]:.*?Connection timed out#",$buffer,$re)){
	if($GLOBALS["VERBOSE"]){echo "$buffer matches in LINE ".__LINE__."\n";}
	$ARRAY["MESSAGE_ID"]=null;
	$ARRAY["HOSTNAME"]=$re[1];
	$ARRAY["IPADDR"]=$re[2];
	$ARRAY["REFUSED"]=1;
	$ARRAY["REJECTED"]="Connection Timed Out";
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write($ARRAY["MESSAGE_ID"],$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
	
}
//---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]: (.+?): from=<(.*?)>, size=([0-9]+),.*?\(queue#",$buffer,$re)){
	if($GLOBALS["VERBOSE"]){echo "$buffer matches in LINE ".__LINE__."\n";}
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["SENDER"]=$re[2];
	$ARRAY["SIZE"]=$re[3];
	$ARRAY["REJECTED"]="Sender";
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write($ARRAY["MESSAGE_ID"],$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}

//---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+to=<(.*?)>,\s+relay=(.*?)\[(.+?)\].*?status=bounced.*?: Relay access denied#",$buffer,$re)){
	if($GLOBALS["VERBOSE"]){echo "$buffer matches in LINE ".__LINE__."\n";}
	$ARRAY["REJECTED"]="Relay access denied";
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["RECIPIENT"]=$re[2];
	$ARRAY["HOSTNAME"]=$re[3];
	$ARRAY["IPADDR"]=$re[4];
	$ARRAY["REFUSED"]=1;
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write($ARRAY["MESSAGE_ID"],$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
	
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: reject: RCPT from\s+(.*?)\[(.*?)\]:\s+554.*?blocked using\s+(.*?); Client host blocked using\s+(.*?),.*?from=<(.*?)> to=<(.*?)>.*?helo=<(.*?)>#",$buffer,$re)){
	$date=date("Y-m-d H:i:s");
	if($GLOBALS["VERBOSE"]){echo "$buffer matches in LINE ".__LINE__."\n";}
	$hostname=$re[1];
	$ARRAY["IPADDR"]=$re[2];
	$Service=$re[3];
	$Service2=$re[4];
	$ARRAY["SENDER"]=$re[5];
	$ARRAY["RECIPIENT"]=$re[6];
	$helo=$re[7];
	if($hostname=="unknown"){$hostname=$helo;}
	if(strlen($Service2)>3){$Service=$Service2;}
	$ARRAY["REJECTED"]="rbl:$Service";
	$ARRAY["HOSTNAME"]=$hostname;
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write($ARRAY["MESSAGE_ID"],$ARRAY);
	return true;
}
//---------------------------------------------------------------------------------------------------------------
if(preg_match("#(smtpd|postscreen)\[.*?connect from\s+(.*?)\[(.+?)\]#",$buffer,$re)){
	if($GLOBALS["VERBOSE"]){echo "$buffer matches in LINE ".__LINE__."\n";}
	if($re[3]=="127.0.0.1"){return true;}
	if(isset($GLOBALS["PostfixRemoveConnections"][$re[3]])){return true;}
	$ARRAY["MESSAGE_ID"]=null;
	$ARRAY["REJECTED"]="Connection start";
	$ARRAY["HOSTNAME"]=$re[2];
	$ARRAY["IPADDR"]=$re[3];
	$ARRAY["SEQUENCE"]=0;
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write($ARRAY["MESSAGE_ID"],$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
//---------------------------------------------------------------------------------------------------------------
if(preg_match("#\[.*?:\s+(.+?):\s+to=<(.*?)>,\s+relay=(.*?)\[(.+?)\].*?status=(.+?)\s+.*?said:.*?:\s+(.+?):#", $buffer,$re)){
	if($GLOBALS["VERBOSE"]){echo "$buffer matches in LINE ".__LINE__."\n";}
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["RECIPIENT"]=$re[2];
	$ARRAY["RELAIS_DEST"]=$re[3];
	$ARRAY["IPADDR"]=$re[4];
	$ARRAY["REJECTED"]=$re[6];
	$ARRAY["SEQUENCE"]=100;
	if($re[5]=="bounced"){$ARRAY["REFUSED"]=1;}
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write($ARRAY["MESSAGE_ID"],$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: reject: RCPT from (.*?)\[(.+?)\]: 45.*?Relay access denied; from=<(.+?)> to=<(.+?)>.*?helo=<(.*?)>#",$buffer,$re)){
	$postgres=new postgres_sql();
	$ARRAY["HOSTNAME"]=$re[1];
	$ARRAY["IPADDR"]=$re[2];
	$ARRAY["SENDER"]=$re[3];
	$ARRAY["RECIPIENT"]=$re[4];
	$ARRAY["SEQUENCE"]=0;
	$ARRAY["REJECTED"]="Relay access denied";
	$ARRAY["REFUSED"]=1;
	if($ARRAY["HOSTNAME"]==null){$ARRAY["HOSTNAME"]=$re[5];}
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	return true;
}

//---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+client=(.*?)\[(.+?)\]#", $buffer,$re)){
	if($GLOBALS["VERBOSE"]){echo "$buffer matches in LINE ".__LINE__."\n";}
	if($re[3]=="127.0.0.1"){return true;}
	if(isset($GLOBALS["PostfixRemoveConnections"][$re[3]])){return true;}
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["HOSTNAME"]=$re[2];
	$ARRAY["IPADDR"]=$re[3];
	$ARRAY["REJECTED"]="Connection accepted";
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write($ARRAY["MESSAGE_ID"],$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
//---------------------------------------------------------------------------------------------------------------
if(preg_match("#\[.*?:\s+(.+?):\s+message-id=<(.*?)>#", $buffer,$re)){
	return true;
}
//---------------------------------------------------------------------------------------------------------------
if(preg_match("#\]:\s+(.+?):\s+milter-reject:\s+END-OF-MESSAGE from\s+(.*?)\[(.+?)\]:.*?virus found\s+(.+?);\s+from=<(.*?)>\s+to=<(.*?)>\s+.*?helo=<(.*?)>#", $buffer,$re)){
	if($GLOBALS["VERBOSE"]){echo "$buffer matches in LINE ".__LINE__."\n";}
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["HOSTNAME"]=$re[2];
	$ARRAY["IPADDR"]=$re[3];
	$ARRAY["REJECTED"]="Infected:{$re[4]}";
	$ARRAY["SENDER"]=$re[5];
	$ARRAY["RECIPIENT"]=$re[6];
	if($ARRAY["HOSTNAME"]=="unknown"){$ARRAY["HOSTNAME"]=$re[7];}
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write($ARRAY["MESSAGE_ID"],$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
//---------------------------------------------------------------------------------------------------------------
if(preg_match("#smtp\[.*?:\s+(.+?):\s+to=<(.*?)>, relay=(.*?)\[(.+?)\].*?status=(.+?)\s+\((.*?)\)#",$buffer,$re)){
	if($GLOBALS["VERBOSE"]){echo "$buffer matches in LINE ".__LINE__."\n";}
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["RECIPIENT"]=$re[2];
	$ARRAY["IPADDR"]=$re[3];
	$ARRAY["HOSTNAME"]=$re[4];
	if($re[5]=="sent"){$MAIN_ARRAY["SENT"]=true;}
	$ARRAY["SEQUENCE"]=100;
	if(preg_match("#Ok:(.+)#", $re[6],$ri)){
		$ARRAY["REJECTED"]=$ri[1];
		$GLOBALS["maillog_tools"]->berkleydb_relatime_write($ARRAY["MESSAGE_ID"],$ARRAY);
		if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
		return;
		}
	$ARRAY["REJECTED"]=$re[6];
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write($ARRAY["MESSAGE_ID"],$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
//---------------------------------------------------------------------------------------------------------------
if(preg_match("#smtpd\[.*?:\s+(.+?):\s+client=(.*?)\[(.+?)\]#",$buffer,$re)){
	if($GLOBALS["VERBOSE"]){echo "$buffer matches in LINE ".__LINE__."\n";}
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["IPADDR"]=$re[3];
	$ARRAY["HOSTNAME"]=$re[2];
	$ARRAY["REJECTED"]="Connection";
	$ARRAY["SEQUENCE"]=0;
	
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write($ARRAY["MESSAGE_ID"],$ARRAY);
	if($GLOBALS["VERBOSE"]){echo "FINISH\n";}
	return true;
}
//---------------------------------------------------------------------------------------------------------------
if(preg_match("#\[.*?:\s+(.+?):\s+.*?END-OF-MESSAGE from\s+(.+?)\[(.+?)\].*?Message infected virus found\s+(.+?);\s+from=<(.*?)>\s+to=<(.*?)>.*?helo=<(.*?)>#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["SENDER"]=$re[5];
	$ARRAY["SIZE"]=0;
	$ARRAY["SEQUENCE"]=100;
	$ARRAY["REJECTED"]=$re[4];
	$ARRAY["IPADDR"]=$re[3];
	$ARRAY["REFUSED"]="Infected:{$re[4]}";
	$ARRAY["RECIPIENT"]=$re[6];
	$ARRAY["HOSTNAME"]=$re[2];
	if($ARRAY["HOSTNAME"]=="unknown"){$ARRAY["HOSTNAME"]=$re[7];}
	if($GLOBALS["DEBUGMIMEFILTER"]){error_log("L".__LINE__." berkleydb_relatime_write({$ARRAY["MESSAGE_ID"]}",0);}
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write($ARRAY["MESSAGE_ID"],$ARRAY);
	return true;
}

//---------------------------------------------------------------------------------------------------------------
if(preg_match("#smtp\[.*?:\s+(.+?):\s+to=<(.*?)>,\s+relay=(.+?)\[(.+?)\].*?status=(.+?)\s+\(.*?said:.*?:\s+(.+?):#",$buffer,$re)){
	$ARRAY["MESSAGE_ID"]=$re[1];
	$ARRAY["SENDER"]=null;
	$ARRAY["SIZE"]=0;
	$ARRAY["SEQUENCE"]=50;
	$ARRAY["REJECTED"]=$re[6];
	$ARRAY["IPADDR"]=$re[4];
	$ARRAY["RELAIS_DEST"]=$re[3];
	$ARRAY["REFUSED"]=1;
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write($ARRAY["MESSAGE_ID"],$ARRAY);
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: (discard|Quarantine): RCPT from\s+(.*?):.*?Message infected \[(.*?)\];.*?\[(.*?)\].*?from=<(.*?)> to=<(.*?)>#",$buffer,$re)){
	$date=date("Y-m-d H:i:s");
	$postgres=new postgres_sql();
	$ARRAY["HOSTNAME"]=$re[2];
	$ARRAY["IPADDR"]=$re[4];
	$ARRAY["REFUSED"]="Infected:{$re[3]}";
	$MAIN_ARRAY["SENDER"]=$re[5];
	$ARRAY["RECIPIENT"]=$re[6];
	$helo=$re[2];
	if($ARRAY["HOSTNAME"]=="unknown"){$ARRAY["HOSTNAME"]=$helo;}
	$GLOBALS["maillog_tools"]->berkleydb_relatime_write(null,$ARRAY);
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: MXCommand: connect: Connection refused: Is multiplexor running#",$buffer,$re)){
	$file="/etc/artica-postfix/pids/NOQUEUE.MXCommand.Connection.refused.multiplexor.running".__LINE__.".err";
	$timefile=file_time_min($file);
	if($timefile>0){
		error_log("Connection refused: Is multiplexor running ?? --> restart [OK] {$timefile}Mn",0);
		squid_admin_mysql(1, "Policies service: (multiplexor running ?) Connection refused [ {action} = {restart} ]", $buffer,__FILE__,__LINE__);
		shell_exec("{$GLOBALS["NOHUP_PATH"]} /etc/init.d/mimedefang restart >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
		return;
	}
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#Slave [0-9]+ stderr: bayes: cannot open bayes databases (.*?)\/bayes_.*?: lock failed: Interrupted system call#",$buffer,$re)){
	squid_admin_mysql(1, "Spamassassin: bayes issue (lock failed) [action=notify]", $buffer,__FILE__,__LINE__);
	return;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#mimedefang-multiplexor.*?exited normally with status 255 \(SLAVE DIED UNEXPECTEDLY\)#",$buffer,$re)){
	squid_admin_mysql(1, "Policies Service: Issue on reloading SLAVE DIED UNEXPECTEDLY [action=reload]", $buffer,__FILE__,__LINE__);
	shell_exec("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.mimedefang.php && /etc/init.d/mimedefang reload");
	return;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#Can't call method \"finish\" on an undefined value at#",$buffer,$re)){
	squid_admin_mysql(1, "Policies Service: Issue on reloading [action=reload]", $buffer,__FILE__,__LINE__);
	shell_exec("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.mimedefang.php && /etc/init.d/mimedefang reload");
	return;
}
// ---------------------------------------------------------------------------------------------------------------



if(preg_match("#cannot load Certification Authority data, CAfile=\"\/etc\/ssl\/certs\/postfix\/ca\.csr\": disabling TLS support#i",$buffer)){
	$RBL=$re[1];
	$file="/etc/artica-postfix/pids/cannot.load.Certification.Authoritydata.time";
	$timefile=file_time_min($file);
	if($timefile>10){
		@unlink($file);@file_put_contents($file, time());
		squid_admin_mysql(0, "Warning: Issue on TLS /etc/ssl/certs/postfix/ca.csr is not available [action=rebuild ssl to default]", "$buffer",__FILE__,__LINE__);
		system("/usr/share/artica-postfix/exec.postfix.default-certificate.php &");
	}
	return true;
	
}
// ---------------------------------------------------------------------------------------------------------------

if(preg_match("#RBL lookup error: Host or domain name not found. Name service error for name=[0-9\.]+\.(.+)\s+type=#",$buffer,$re)){
	$RBL=$re[1];
	$file="/etc/artica-postfix/pids/RBL.service.$RBL.unavailable.time";
	$timefile=file_time_min($file);
	if($timefile>5){@unlink($file);@file_put_contents($file, time());squid_admin_mysql(0, "Warning: RBL service $RBL unavailable, you should remove it", "It seems that $RBL did not respond correctly,\ntry using nslookup 127.0.0.2.blackholes.mail-abuse.org\nplease remove it using\nhttps://yourarticaserver:port/postfix-publicrbl\n$buffer",__FILE__,__LINE__);}
	return true;
	
}
// ---------------------------------------------------------------------------------------------------------------
	if(preg_match("#NOQUEUE: reject: RCPT from (.*?):\s+554.*?BadFromMyDomains.*?Client host \[(.*?)\] blocked using (.+?); from=<(.*?)> to=<(.*?)>#",$buffer,$re)){
		$date=date("Y-m-d H:i:s");
		$postgres=new postgres_sql();
		$hostname=$re[1];
		$ipaddr=$re[2];
		$reason="Domain Forged in Header";
		$mailfrom=$re[4];
		$mailto=$re[5];
		$postgres->smtprefused($hostname,$mailfrom,$mailto,$ipaddr,$reason);
		return true;
	}
	

// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: reject: RCPT from (.*?):\s+554.*?Client host \[(.*?)\] blocked using Spamassassin.*?from=<(.*?)> to=<(.*?)>#",$buffer,$re)){
	$date=date("Y-m-d H:i:s");
	$postgres=new postgres_sql();
	$hostname=$re[1];
	$ipaddr=$re[1];
	$reason="Antispam denied";
	$mailfrom=$re[3];
	$mailto=$re[4];
	$helo=$re[5];
	if($hostname=="unknown"){$hostname=gethostbyaddr($ipaddr);}
	$postgres->smtprefused($hostname,$mailfrom,$mailto,$ipaddr,$reason);
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: Forged: RCPT from (.*?):\s+554.*?Client host \[(.*?)\] blocked using Forged.*?from=<(.*?)> to=<(.*?)> proto=milter#",$buffer,$re)){
	$date=date("Y-m-d H:i:s");
	$postgres=new postgres_sql();
	$hostname=$re[1];
	$ipaddr=$re[2];
	$reason="Forged";
	$mailfrom=$re[3];
	$mailto=$re[4];
	$helo=$re[5];
	if($hostname=="unknown"){$hostname=gethostbyaddr($ipaddr);}
	$postgres->smtprefused($hostname,$mailfrom,$mailto,$ipaddr,$reason);
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: milter-reject: RCPT from (.*?)\[(.*?)\]: 451.*?Greylisting in action.*?; from=<(.*?)> to=<(.*?)>.*?helo=<(.*?)>#",$buffer,$re)){
	$date=date("Y-m-d H:i:s");
	$postgres=new postgres_sql();
	$hostname=$re[1];
	$ipaddr=$re[2];
	$reason="Greylisted";
	$mailfrom=$re[3];
	$mailto=$re[4];
	$helo=$re[5];
	$postgres->smtprefused($hostname,$mailfrom,$mailto,$ipaddr,$reason);
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#mimedefang.*?Could not connect to clamd daemon#",$buffer,$re)){
	error_log("Antivirus issue while checking mail [action=restart clamd]",0);
	$file="/etc/artica-postfix/pids/mimedefang.Could.not.connect.to.clamd.daemon";
	$timefile=file_time_min($file);
	if($timefile>0){
		squid_admin_mysql(0, "Antivirus issue while checking mail [action=restart clamd]", $buffer,__FILE__,__LINE__);
		shell_exec("{$GLOBALS["NOHUP_PATH"]} /etc/init.d/clamav-daemon restart >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
		return;
	}
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#warning: connect to Milter service unix:.*?mimedefang\.sock: No such file or directory#",$buffer,$re)){
	error_log("mimedefang.sock: No such file or directory --> restart ?",0);
	$file="/etc/artica-postfix/pids/Milter.service.mimedefang.".__LINE__.".sock";
	$timefile=file_time_min($file);
	if($timefile>0){
		error_log("mimedefang.sock: No such file or directory --> restart [OK] {$timefile}Mn",0);
		squid_admin_mysql(1, "mimedefang.sock: No such file or directory [ {action} = {restart} ]", null,__FILE__,__LINE__);
		shell_exec("{$GLOBALS["NOHUP_PATH"]} /etc/init.d/mimedefang restart >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
		return;
	}
}

// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#warning: connect to Milter service unix:.*?milter-greylist\.sock: Connection refused#",$buffer,$re)){
	error_log("milter-greylist.sock: Connection refused --> restart ?",0);
	$file="/etc/artica-postfix/pids/Milter.service.miltergreylist.".__LINE__.".sock";
	$timefile=file_time_min($file);
	if($timefile>0){
		error_log("milter-greylist.sock: --> restart [OK] {$timefile}Mn",0);
		squid_admin_mysql(1, "milter-greylist.sock: Connection refused [ {action} = {restart} ]", null,__FILE__,__LINE__);
		shell_exec("{$GLOBALS["NOHUP_PATH"]} /etc/init.d/milter-greylist restart >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
		return;
	}
}

// ---------------------------------------------------------------------------------------------------------------

if(preg_match("#NOQUEUE: reject: RCPT from unknown\[(.*?)\]: 450 4.7.1 Client host rejected: cannot find your reverse hostname.*?from=<(.*?)> to=<(.*?)>.*?helo=<(.*?)>#",$buffer,$re)){
	$date=date("Y-m-d H:i:s");
	$postgres=new postgres_sql();
	$ipaddr=$re[1];
	$mailfrom=$re[2];
	$mailto=$re[3];
	$hostname=$re[4];
	$reason="Reverse not found";
	$postgres->smtprefused($hostname,$mailfrom,$mailto,$ipaddr,$reason);
	return true;
}

// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: reject: RCPT from\s+(.*?)\[([0-9\.]+)\]:\s+.*?Server configuration error;\s+from=<(.+?)>\s+to=<(.+?)>\s+#",$buffer,$re)){
	$date=date("Y-m-d H:i:s");
	$postgres=new postgres_sql();
	$ipaddr=$re[2];
	$mailfrom=$re[3];
	$mailto=$re[4];
	$hostname=$re[1];
	$reason="Server configuration error";
	$postgres->smtprefused($hostname,$mailfrom,$mailto,$ipaddr,$reason);
	return true;
	
}
// ---------------------------------------------------------------------------------------------------------------



// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: reject:\s+RCPT from (.*?)\[([0-9\.]+)\].*?Client host rejected: Go Away.+?from=<(.*?)>\s+to=<(.*?)>#",$buffer,$re)){
	$date=date("Y-m-d H:i:s");
	$postgres=new postgres_sql();
	$hostname=$re[1];
	$ipaddr=$re[2];
	$mailfrom=$re[3];
	$mailto=$re[4];
	$reason="Blacklisted";
	$VALUES="('$date','$hostname','$mailfrom','$mailto','$ipaddr','$reason')";
	$postgres->smtprefused($hostname,$mailfrom,$mailto,$ipaddr,$reason);
	return true;
}
// ---------------------------------------------------------------------------------------------------------------

if(preg_match("#NOQUEUE: reject: RCPT from unknown\[(.*?)\]: 450.*?<(.*?)>: Sender address rejected: Domain not found; from=<(.*?)> to=<(.*?)>.*?helo=<(.*?)>#",$buffer,$re)){
	$postgres=new postgres_sql();
	$hostname=$re[5];
	$ipaddr=$re[1];
	$mailfrom=$re[3];
	$mailto=$re[4];
	$reason="Unknown sender domain";
	$postgres->smtprefused($hostname,$mailfrom,$mailto,$ipaddr,$reason);
	return true;	
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: reject: RCPT from (.*?)\[(.*?)\]: 450.*?Sender address rejected: Domain not found; from=<(.*?)> to=<(.*?)>#",$buffer,$re)){
	$postgres=new postgres_sql();
	$hostname=$re[1];
	$ipaddr=$re[2];
	$mailfrom=$re[3];
	$mailto=$re[4];
	$reason="Unknown sender domain";
	$postgres->smtprefused($hostname,$mailfrom,$mailto,$ipaddr,$reason);
	return true;	
}
// ---------------------------------------------------------------------------------------------------------------

if(preg_match("#NOQUEUE: reject: RCPT from unknown\[(.*?)\]: 450.*?Client host rejected: cannot find your reverse hostname,.*?from=<(.*?)> to=<(.*?)>#",$buffer,$re)){
	$postgres=new postgres_sql();
	$hostname="Unknown";
	$ipaddr=$re[1];
	$mailfrom=$re[2];
	$mailto=$re[3];
	$reason="Unknown reverse hostname";
	$postgres->smtprefused($hostname,$mailfrom,$mailto,$ipaddr,$reason);
	return true;	
}




if(preg_match("#NOQUEUE: reject: RCPT from unknown\[(.+?)\]: 5.*?blocked using\s+(.+?);.*?from=<(.+?)> to=<(.+?)>.*?helo=<(.+?)>#",$buffer,$re)){
	$postgres=new postgres_sql();
	$hostname=$re[5];
	$ipaddr=$re[1];
	$mailfrom=$re[3];
	$mailto=$re[4];
	$reason=$re[2];
	$postgres->smtprefused($hostname,$mailfrom,$mailto,$ipaddr,$reason);
	return true;
}

if(preg_match("#NOQUEUE: NullSender: RCPT from\s+(.*?): 5.*?Message NullSender; Client host \[(.+?)\] blocked using NullSender; from=<(.*?)> to=<(.*?)>#",$buffer,$re)){
	$postgres=new postgres_sql();
	$hostname=$re[1];
	$ipaddr=$re[2];
	$mailfrom=$re[3];
	$mailto=$re[4];
	$reason="NullSender";
	$postgres->smtprefused($hostname,$mailfrom,$mailto,$ipaddr,$reason);
	return true;
}
	
if(preg_match("#reject#",$buffer)){
	error_log("NOT TRAPPED \"$buffer\"",0);
	
}


if(preg_match("#unknown group name:\s+postdrop#i", $buffer,$re)){
	shell_exec("{$GLOBALS["GROUPADD"]} postdrop >/dev/null 2>&1");
	return;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#warning: SASL authentication problem: unable to open Berkeley db \/etc\/sasldb2: Permission denied#", $buffer,$re)){
	$file="/etc/artica-postfix/pids/SASL.authentication.problem.".__LINE__.".time";
	$timefile=file_time_min($file);
	if($timefile>3){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("smtpd_sasl_path", "smtpd");
		shell_exec("{$GLOBALS["postconf_bin_path"]} -e \"smtpd_sasl_path=smtpd\"");
		shell_exec("{$GLOBALS["NOHUP_PATH"]} /etc/init.d/postfix reload >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
		return;
	}
}

if(preg_match("#smtpd.*?warning: No server certs available. TLS won't be enabled#", $buffer,$re)){
	$file="/etc/artica-postfix/pids/postfix.No.server.certs.available.".__LINE__.".time";
	$timefile=file_time_min($file);
	if($timefile>3){
		
		
	}
}

// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#fatal: scan_dir_push: open directory .*?: Permission denied#", $buffer,$re)){
	shell_exec("{$GLOBALS["POSTFIX_BIN"]} set-permissions");
	shell_exec("{$GLOBALS["NOHUP_PATH"]} /etc/init.d/postfix restart >/dev/null 2>&1 &");
	return;
}
// ---------------------------------------------------------------------------------------------------------------

if(preg_match("#warning: SASL authentication problem: unable to open Berkeley db\s+(.+?):\s+Permission denied#", $buffer,$re)){
	$GLOBALS["CLASS_UNIX"]->chown_func("postfix","postfix", "{$re[1]}");
	return;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#hash.*? open database\s+(.*?)\.db: No such file or directory#", $buffer,$re)){
	if(!is_file($GLOBALS["postconf_bin_path"])){return;}
	error_log("Missing hash database {$re[1]} -> build it",0);
	@file_put_contents($re[1], "\n");
	shell_exec("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["postconf_bin_path"]} hash:{$re[1]} >/dev/null 2>&1 &");
	return;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#cyrus.*?DBERROR: opening (.*?)\.seen: cyrusdb error#", $buffer,$re)){
	error_log("cyrus, corrupted seen file {$re[1]}.seen",0);
	@unlink("{$re[1]}.seen");
	return;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#connect to.*?\[(.*?)lmtp\]:\s+Permission denied#", $buffer)){
	error_log("{$re[1]}/lmtp, permission denied, apply postfix:postfix",0);
	$GLOBALS["CLASS_UNIX"]->chown_func("postfix","postfix", "{$re[1]}/lmtp");
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#warning: connect \#[0-9]+\s+to subsystem private\/cyrus: No such file or directory#", $buffer)){
	error_log("Cyrus unconfigured, reconfigure it...",0);
	$file="/etc/artica-postfix/pids/cyrus-subsystem.".__LINE__.".time";
	$timefile=file_time_min($file);
	if($timefile>3){shell_exec_maillog("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --imap-sockets >/dev/null 2>&1 &");}
		@unlink($file);
		@file_put_contents($file, time());
	
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if (preg_match("#warning.*?connect to Milter service.*?unix:.*?artica-milter\/milter.sock.*?No such file or directory#", $buffer)){
    error_log("Issue on Artica Milter, restart it",0);
    $file="/etc/artica-postfix/pids/Artica.milter.".__LINE__.".time";
    $timefile=file_time_min($file);
    if($timefile>2){
        shell_exec_maillog("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.artica-milter.php --restart >/dev/null 2>&1 &");
    }
    @unlink($file);
    @file_put_contents($file, time());
    return true;
}
// ---------------------------------------------------------------------------------------------------------------


if(preg_match("#postfix-script\[.+?: the Postfix mail system is not running#", $buffer)){
	if($GLOBALS["EnableStopPostfix"]==0){
		$file="/etc/artica-postfix/pids/postfix-script.start.time";
		$timefile=file_time_min($file);
		if($timefile>1){
			shell_exec_maillog("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["postfix_bin_path"]} start >/dev/null 2>&1 &");}
			@unlink($file);
			@file_put_contents($file, time());
		} 
		return;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#master.*?fatal: bind (.+?)\s+port\s+([0-9]+):\s+Address already in use#", $buffer,$re)){
	
	$port=$re[2];
	error_log("Port conflict on $port",0);
	exec("{$GLOBALS["fuser"]} $port/tcp 2>&1",$results);
	
	foreach ($results as $num=>$ligne){
		if(preg_match("#:\s+([0-9]+)#", $ligne,$re)){
			$tokill=$re[1];
			error_log("Killing PID $tokill",0);
			shell_exec_maillog("{$GLOBALS["kill"]} -9 $tokill");
		}
	}
	
	if($GLOBALS["EnableStopPostfix"]==0){
		$file="/etc/artica-postfix/pids/postfix-script.start.".__LINE__.".time";
		$timefile=file_time_min($file);
		if($timefile>1){
			shell_exec_maillog("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["postfix_bin_path"]} start >/dev/null 2>&1 &");}
			@unlink($file);
			@file_put_contents($file, time());
		} 
		return;
}
// ---------------------------------------------------------------------------------------------------------------
if(strpos($buffer,"fatal: mail system startup failed")>0){
	$sock=new sockets();
	if($GLOBALS["EnableStopPostfix"]==0){
		$file="/etc/artica-postfix/pids/postfix-script.start.".__LINE__.".time";
		$timefile=file_time_min($file);
		if($timefile>1){shell_exec_maillog("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["postfix_bin_path"]} start >/dev/null 2>&1 &");}
			@unlink($file);
			@file_put_contents($file, time());
		}
	return;	
}
// ---------------------------------------------------------------------------------------------------------------



$p=new postfix_maillog_buffer($buffer);if($p->parse()){$p=null;return true;}




if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_CYRUS_INSTALLED"))==1){
    $EnableCyrusImap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCyrusImap"));
    if($EnableCyrusImap==1){
	    if(!class_exists("cyrus_maillog")){
	        include_once(dirname(__FILE__)."/ressources/class.cyrus.maillog.inc");
	    }
        $p=new cyrus_maillog($buffer);if($p->ParseBuffer()){$p=null;return true;}
    }
}


if(preg_match("#createuser\[.+?User store\s+'(.+?)'\s+createdi#",$buffer,$re)){
	//$this->squid_admin_mysql(0,"Zarafa server new store created for {$re[1]}",$buffer,"mailbox",0);
	return;
}

// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#clamav-milter.*?No clamd server appears to be available#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/clamav-milter.".md5("No clamd server appears to be available");
	$timefile=file_time_min($file);
	if($timefile>5){
		squid_admin_mysql(0, "Milter Antivirus issue! [action=update signatures]", $buffer,__FILE__,__LINE__);
		$cmd="{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.freshclam.php --execute >/dev/null 2>&1 &";
		@unlink($file);@file_put_contents($file,"#");
		error_log("$cmd",0);
		shell_exec_maillog($cmd);
	}
	return;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#milter-greylist:.+?bind failed: Address already in use#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/milter-greylist.".md5("cannot start MX sync, bind failed: Address already in use");
	$timefile=file_time_min($file);
	if($timefile>5){
		squid_admin_mysql(0,"milter-greylist: double service issue",
		"milter-greylist\n$buffer\nArtica will restart milter-greylist service",__FILE__,__LINE__);
		@unlink($file);@file_put_contents($file,"#");
		$cmd="{$GLOBALS["NOHUP_PATH"]} /etc/init.d/milter-greylist restart >/dev/null 2>&1 &";
		error_log("$cmd",0);
		shell_exec_maillog($cmd);
		
	}
	return;
}


if(strpos($buffer,"inet_interfaces: no local interface found")>0){
	$file="/etc/artica-postfix/croned.1/postfix.error.inet_interfaces";
	error_log("inet_interfaces issues $buffer",0);	
	$timefile=file_time_min($file);
	if($timefile>10){
        squid_admin_mysql(0,"{$re[1]}: misconfiguration on inet_interfaces",
		"Postfix claim \n$buffer\n\nIf this event is resended\nplease Check Artica Technology support service.",__FILE__,__LINE__);
		@unlink($file);@file_put_contents($file,"#");
		$cmd=trim("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --interfaces >/dev/null 2>&1 &");
		error_log("$cmd",0);
		shell_exec_maillog($cmd);
		}
	return;	
}

if(preg_match("#mail_queue_enter.*?create file maildrop\/.*?Permission denied#", $buffer,$re)){
	chgrp("/var/spool/postfix/public", "postdrop");
	chgrp("/var/spool/postfix/maildrop", "maildrop");
	shell_exec("{$GLOBALS["CHMOD"]} 1730 /var/spool/postfix/maildrop");
	shell_exec("{$GLOBALS["postfix_bin_path"]} stop && {$GLOBALS["postfix_bin_path"]} start");
	return;
}
	
if(preg_match("#(.+?)\/smtpd\[.+?fatal:\s+config variable inet_interfaces#", $buffer)){
	$file="/etc/artica-postfix/croned.1/postfix.error.inet_interfaces";
	error_log("inet_interfaces issues' '{$re[1]}'",0);
	$timefile=file_time_min($file);
	if($timefile>10){
		squid_admin_mysql(0,"{$re[1]}: misconfiguration on inet_interfaces",
		"Postfix claim \n$buffer\n\nIf this event is resended\nplease Check Artica Technology support service.",__FILE__,__LINE__);
		@unlink($file);@file_put_contents($file,"#");
		if($re[1]=="postfix"){
			$cmd=trim("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --interfaces >/dev/null 2>&1 &");
			error_log("$cmd",0);
			shell_exec_maillog($cmd);
		}else{
			$cmd=trim("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix-multi.php >/dev/null 2>&1 &");
			error_log("$cmd",0);
			shell_exec_maillog($cmd);
		}
	}
	return;			
}

	if(preg_match("#\]:\s+bayes: cannot open bayes databases\s+(.+?)\/bayes_.+?R\/.+?: tie failed.+?Permission denied#", $buffer,$re)){
		error_log("cannot open bayes databases , Permission denied' '{$re[1]}/bayes_*'",0);
		shell_exec_maillog("/bin/chown postfix:postfix {$re[1]}/bayes*");
		return;
	}


	if(preg_match("#\]:\s+bayes: cannot open bayes databases\s+(.+?)\/bayes_.+?R\/O: tie failed#", $buffer,$re)){
		error_log("cannot open bayes databases , unlink '{$re[1]}/bayes_seen' '{$re[1]}/bayes_toks'",0);
		if(is_file("{$re[1]}/bayes_seen")){@unlink("{$re[1]}/bayes_seen");}
		if(is_file("{$re[1]}/bayes_toks")){@unlink("{$re[1]}/bayes_toks");}
		return;
	}
	
	



	
	
	if(preg_match("#postfix-(.+?)\/smtpd\[[0-9]+\]:\s+warning:\s+connect to Milter service unix:(.+?):\s+Connection refused#", $buffer,$re)){
		
		error_log("Postfix: {$re[2]} socket issue Connection refused... (line ".__LINE__.")",0);
		$file="/etc/artica-postfix/croned.1/postfix.{$re[1]}.". md5($re[2]).".sock.No.such.file.or.directory";
		$timefile=file_time_min($file);
		if($timefile>5){
			$cmd=trim("{$GLOBALS["NOHUP_PATH"]} /bin/chown postfix:postfix {$re[2]} >/dev/null 2>&1 &");
			error_log("Postfix:{$re[1]}: $cmd",0);
			shell_exec_maillog($cmd);
		}
		return;
	}


	if(preg_match("#\[.+?:\s+connect to 127\.0\.0\.1\[127\.0\.0\.1\]:2003:\s+Connection refused#", $buffer,$re)){
		$file="/etc/artica-postfix/croned.1/postfix.port.2003.Connection.refused";
		$timefile=file_time_min($file);
		if($timefile>5){
				squid_admin_mysql(0,"Postfix: Connect to zarafa LMTP port Connection refused zarafa-lmtp will be restarted",
				"postfix claim \n$buffer\nArtica will try to restart zarafa-lmtp daemon.",__FILE__,__LINE__);
				shell_exec_maillog(trim("{$GLOBALS["NOHUP_PATH"]} /etc/init.d/artica-postfix restart zarafa-lmtp >/dev/null 2>&1 &"));
				@unlink($file);@file_put_contents($file,"#");
			}else{error_log("Postfix: Connect to zarafa LMTP port Connection refused: {$timefile}Mn/5Mn",0);}
		return;			
		}




	



if(preg_match("#cyrus\/.+?\[[0-9]+]#",$buffer)){
	include_once(dirname(__FILE__)."/ressources/class.cyrus.maillog.inc");
	$cyrus=new cyrus_maillog();
	if($cyrus->ParseBuffer($buffer)){return;}
	}
	
if(preg_match("#master\[.+?fatal: bind 127.0.0.1 port 33559: Address already in use#", $buffer,$re)){
	error_log("Postfix: bind 127.0.0.1 port 33559: Address already in use -> startit",0);
	shell_exec_maillog(trim("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["postfix_bin_path"]} start >/dev/null 2>&1 &"));
	return;
}	


if(preg_match("#postqueue.+?warning: Mail system is down#", $buffer,$re)){
	$sock=new sockets();
	$EnableStopPostfix=$sock->GET_INFO("EnableStopPostfix");
	if(!is_numeric($EnableStopPostfix)){$EnableStopPostfix=0;}
	if($EnableStopPostfix==0){
		error_log("Postfix: Mail system is down:  -> startit",0);
		shell_exec_maillog(trim("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["postfix_bin_path"]} start >/dev/null 2>&1 &"));
	}
	
	return;
}	
	
if(preg_match("#postscreen.+?warning: database\s+(.+?):\s+could not delete entry for#", $buffer,$re)){
	error_log("Postscreen: Cache database failed",0);
	if(is_file($re[1])){
		@unlink($re[1]);
		squid_admin_mysql(1,"Postfix: postscreen_cache_map problem",
		"postfix claim \n$buffer\nArtica have deleted {$re[1]} file to fix this issue.",__FILE__,__LINE__);
	}
}


if(preg_match("#fatal: dict_open: unsupported dictionary type: pcre:\s+Is the postfix-pcre package installed#i",$buffer,$re)){
	error_log("Postfix: pcre missing",0);
	$file="/etc/artica-postfix/croned.1/postfix.pcre.missing";
	$timefile=file_time_min($file);
	if($timefile>20){
        squid_admin_mysql(0,"Postfix: pcre missing",
		"postfix claim \n$buffer\nArtica will try to upgrade postfix.",__FILE__,__LINE__);
		shell_exec_maillog(trim("{$GLOBALS["NOHUP_PATH"]} /usr/share/artica-postfix/bin/artica-make APP_POSTFIX >/dev/null 2>&1 &"));
		@unlink($file);@file_put_contents($file,"#");
	}else{error_log("Postfix: pcre missing: {$timefile}Mn/20Mn",0);}
	return;			
}

if(preg_match("#zarafa-server.+?The recommended upgrade procedure is to use the zarafa7-upgrade commandline tool#",$buffer,$re)){
	
	$cmd=trim("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.zarafa-migrate.php --upgrade-7 >/dev/null 2>&1 &");
	error_log("zarafa-server, need to upgrade... -> $cmd",0);
	shell_exec_maillog($cmd);
}


if(preg_match("#zarafa-gateway.+?POP3, POP3S, IMAP and IMAPS are all four disabled#",$buffer,$re)){
	error_log("Zarafa-gateway No services enabled...???",0);
	$file="/etc/artica-postfix/croned.1/zarafa-gateway.no.services";
	$timefile=file_time_min($file);
	if($timefile>10){
		squid_admin_mysql(0,"Zarafa mail server: No mailbox protocol ?",
		"Zarafa claim \n$buffer\nYou have disabled all mailboxes protocols.\nMeans that zarafa-gateway is not necessary ???\nAre you sure ??","mailbox");
		@unlink($file);@file_put_contents($file,"#");
	}else{error_log("Postfix: Zarafa-gateway No services enabled...: {$timefile}Mn/10Mn",0);}
	return;			
}











if(preg_match("#warning:.+?then you may have to chmod a\+r\s+(.+?)$#",$buffer,$re)){
	error_log("chmod a+r {$re[1]}",0);
	shell_exec_maillog("/bin/chmod a+r {$re[1]}");
	return;
}

if(preg_match("#imaps\[.+?Fatal error: tls_start_servertls.+?failed#",$buffer,$re)){
	error_log("Cyrus-imap : IMAP SSL FAILED",0);
	$file="/etc/artica-postfix/croned.1/imaps.error.tls_start_servertls";
	$timefile=file_time_min($file);
	if($timefile>5){
		shell_exec_maillog(trim("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.cyrus.php --imaps-failed >/dev/null 2>&1 &"));
		@unlink($file);
		@unlink($file);@file_put_contents($file,"#");
	}else{error_log("Cyrus-imap wait:{$timefile}Mn/5Mn",0);}
	return;		
}

if(preg_match("#fatal: file.+?main\.cf: parameter setgid_group: unknown group name:\s+(.+)#",$buffer,$re)){
	error_log("Postfix : group name {$re[1]} problem",0);
	$file="/etc/artica-postfix/croned.1/postfix.group.{$re[1]}.error";
	$timefile=file_time_min($file);
	if($timefile>5){
		squid_admin_mysql(0,"Postfix: group {$re[1]} is not available",
		"Postfix claim \n$buffer\nArtica will try create this group.",__FILE__,__LINE__);
		$unix=new unix();
		$groupadd=$unix->find_program("groupadd");
		shell_exec_maillog("$groupadd {$re[1]}&");
		@unlink($file);@file_put_contents($file,"#");
	}else{error_log("Postfix: Postfix: group {$re[1]} is not available: {$timefile}Mn/5Mn",0);}
	return;		
}


if(preg_match("#fatal: parameter inet_interfaces: no local interface found for ([0-9\.]+)#i",$buffer,$re)){
	error_log("Postfix : NIC {$re[1]} problem",0);
	$file="/etc/artica-postfix/croned.1/postfix.interface.{$re[1]}.error";
	$timefile=file_time_min($file);
	if($timefile>5){
		squid_admin_mysql(0,"Postfix: Interface {$re[1]} is not available",
		"Postfix claim \n$buffer\nArtica will try to restore TCP/IP interfaces.",__FILE__,__LINE__);
		@unlink("/etc/artica-postfix/MEM_INTERFACES");
		shell_exec_maillog(trim("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.virtuals-ip.php >/dev/null 2>&1 &"));
		@unlink($file);
		@unlink($file);@file_put_contents($file,"#");
	}else{error_log("Postfix: Interface {$re[1]} is not available: {$timefile}Mn/5Mn",0);}
	return;		
}


if(preg_match("#qmgr\[.+?fatal: incorrect version of Berkeley DB: compiled against.+?run-time linked against#i",$buffer,$re)){
	error_log("Postfix : incorrect version of Berkeley DB",0);
	$file="/etc/artica-postfix/croned.1/qmgr.error.Berkeley";
	$timefile=file_time_min($file);
	if($timefile>5){
		squid_admin_mysql(0,"Postfix: incorrect version of Berkeley DB",
		"Postfix claim \n$buffer\nArtica will upgrade/re-install your postfix version.",__FILE__,__LINE__);
		@unlink($file);
		shell_exec_maillog(trim("{$GLOBALS["NOHUP_PATH"]} /usr/share/artica-postfix/bin/artica-make APP_POSTFIX 2>&1 &"));
		@unlink($file);@file_put_contents($file,"#");
	}else{error_log("Postfix : incorrect version of Berkeley DB wait:{$timefile}Mn/5Mn",0);}
	return;		
}
if(preg_match('#smtpd\[.+? warning: unknown smtpd restriction: "(.+?)"#',$buffer,$re)){
	error_log("Postfix : incorrect parameters on smtpd restriction",0);
	$file="/etc/artica-postfix/croned.1/smtpd.error.restriction." .md5($re[1]);
	$timefile=file_time_min($file);
	if($timefile>5){
		squid_admin_mysql(0,"Postfix: incorrect parameters on smtpd restriction",
		"Postfix claim \n$buffer\nArtica will try to fix the problem.\nif this error is sended again, please contact Artica Support team.",__FILE__,__LINE__);
		@unlink($file);
		shell_exec_maillog(trim("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --smtp-sender-restrictions &"));
		@unlink($file);@file_put_contents($file,"#");
	}else{error_log("Postfix : incorrect parameters on smtpd restriction wait:{$timefile}Mn/5Mn",0);}
	return;		
}
if(preg_match('#spamc\[.+?connect to spamd on (.+?)\s+failed,.+?Connection refused#',$buffer,$re)){
	error_log("Spamassassin : {$re[1]} Connection refused",0);
	$file="/etc/artica-postfix/croned.1/spamc.error.cnx.refused." .md5($re[1]);
	$timefile=file_time_min($file);
	if($timefile>5){
		squid_admin_mysql(0,"Spamassassin: Connection refused on {$re[1]}",
		"Spamassassin claim \n$buffer\nYou should have less issues and better performances using Amavisd-new instead Spamassassin only",__FILE__,__LINE__);
		@unlink($file);
		@unlink($file);@file_put_contents($file,"#");
	}else{error_log("Spamassassin : {$re[1]} Connection refused wait:{$timefile}Mn/5Mn",0);}
	return;		
}





if(preg_match("#smtpd\[.+?warning: connect to 127.0.0.1:54423: Connection refused#",$buffer,$re)){
	error_log("restart Artica-policy",0);
	shell_exec_maillog("/etc/init.d/artica-postfix restart artica-policy &");
	return;
}



if(preg_match("#nss_wins\[.+?connect from (.+?)\[(.+?)\]#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection($re[1],$re[2]);
	return;
}

if(preg_match("#nss_wins\[.+?warning: (.+?):\s+address not listed for hostname\s+(.+?)$#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection_error($re[2],$re[1],"ADDR_NOT_LISTED1");
	return;
}

if(preg_match("#postscreen\[.+?CONNECT from \[(.+?)\]#i",$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection(null,$re[1]);
	return;
}



if(preg_match("#dnsblog\[.+?addr\s+(.+?)\s+listed by domain#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection_error(null,$re[1],"RBL");
	return;
}

if(preg_match("#nss_wins\[.+?warning: (.+?):\s+hostname\s+(.+?)\s+verification failed: Name or service not known#",$buffer,$re)){
	//"verification failed: Name or service not known"
	$GLOBALS["maillog_tools"]->Postfix_Addconnection_error($re[2],$re[1],"VERIFY_FAILED1");
	return;
}


if(strpos($buffer,"connect to Milter service inet:127.0.0.1:1052: Connection refused")>0){
	error_log("KavMilter stopped !",0);
	$md5=md5("connect to Milter service inet:127.0.0.1:1052: Connection refused");
	$file="/etc/artica-postfix/croned.1/postfix.milter.$md5";
	$timefile=file_time_min($file);
	if($timefile>5){
		squid_admin_mysql(0,"Postfix: Kaspersky Antivirus For Postfix daemon is not available",
		"Postfix claim \n$buffer\nArtica will restart it's daemon.",__FILE__,__LINE__);
		@unlink($file);
		shell_exec_maillog("/etc/init.d/kavmilterd restart &");
		file_put_contents($file,"#");
		
	}else{
		error_log("connect to Milter service inet:127.0.0.1:1052: Connection refused :{$timefile}Mn/5Mn to wait",0);
	}
	return;	
}



if(preg_match("#postfix.+?fatal: non-null host address bits in.+?([0-9\.\/]+)\", perhaps you should use \"(.+?)\"\s+instead#",$buffer,$re)){
	error_log("NetWork & Nics, need to change from {$re[1]} to {$re[2]}",0);
	$md5=md5("{$re[1]}{$re[2]}");
	$file="/etc/artica-postfix/croned.1/postfix.network.$md5";
	$timefile=file_time_min($file);
	if($timefile>5){
		squid_admin_mysql(0,"Postfix: Bad network parameter you have set {$re[1]} you need to set {$re[2]} instead !",
		"Postfix claim \n$buffer\n",__FILE__,__LINE__);
		@unlink($file);
		file_put_contents($file,"#");
	}else{
		error_log("Bad network parameter you have set {$re[1]} you need to set {$re[2]} instead :{$timefile}Mn",0);
	}
	return;	
}

if(preg_match("#postfix\/master\[.+?fatal:\s+open lock file\s+(.+?): unable to set exclusive lock: Resource temporarily unavailable#",$buffer,$re)){
	error_log("postfix: {$re[1]}, unable to set exclusive lock",0);
	$re[1]=trim($re[1]);
	$md5=md5("postfix: {$re[1]} unable to set exclusive lock");
	$file="/etc/artica-postfix/croned.1/postfix.error.$md5";
	$timefile=file_time_min($file);
	if($timefile>5){
		exec("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --repair-locks",$results);
		squid_admin_mysql(0,"Postfix: {$re[1]} unable to set exclusive lock",
		"Postfix claim \n$buffer\nArtica tried to repair it\n".@implode("\n", $results),"postfix");
		if(is_file($re[1])){@unlink($re[1]);}
		@unlink($file);
		file_put_contents($file,"#");
	}else{
		error_log("postfix: {$re[1]} unable to set exclusive lock instead wait:{$timefile}Mn",0);
	}
	return;	
}
// ##########################  emailrelay 


if(preg_match("#emailrelay:\s+error:\s+polling:\s+cannot stat\(\)\s+file:\s+(.+)#",$buffer,$re)){
	error_log("emailrelay: ".basename($re[1])." corrupted file",0);
	shell_exec_maillog("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.emailrelay.php --corrupted \"{$re[1]}\" &");
	return;
}

if(preg_match("#emailrelay\[(.+?)\].+?emailrelay: error:\s+(.+)#",$buffer,$re)){
	if(strpos("$buffer","cannot stat")>0){return;}
	error_log("emailrelay PID {$re[1]} Error:Mass Mailing {$re[2]}",0);
	squid_admin_mysql(0,"emailrelay PID {$re[1]} Error:Mass Mailing {$re[2]}","emailrelay claim \n$buffer\nCheck your configuration file","emailrelay",0);
	return;
}
if(preg_match("#emailrelay\[(.+?)\].+?emailrelay: warning:\s+(.+)#",$buffer,$re)){
	if(strpos("$buffer","cannot stat")>0){return;}
	error_log("emailrelay PID {$re[1]} Error:Mass Mailing {$re[2]}",0);
	squid_admin_mysql(0,"emailrelay PID {$re[1]} Error:Mass Mailing {$re[2]}","emailrelay claim \n$buffer\nCheck your configuration file","emailrelay",0);
	return;
} 

// ##########################

if(strpos($buffer,"warning: to change inet_interfaces, stop and start Postfix")>0){
	error_log("inet_interfaces: restarting postfix",0);
	shell_exec_maillog("{$GLOBALS["postfix_bin_path"]} stop && {$GLOBALS["postfix_bin_path"]} start &");
	return;
}

if(preg_match("#(.+?)\/smtpd.+?fatal: bad string length.+? inet_interfaces =#",$buffer,$re)){
	
	if($re[1]=="postfix"){
		$instance="master";
		$cmd="{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --interfaces";
	}else{
		if(preg_match("#postfix-(.+)#",$re[1],$ri)){
			$instance=$ri[1];
		}
	}
	error_log("$instance:inet_interfaces is null ?? in postfix configuration file, try to repair",0);
	$file="/etc/artica-postfix/croned.1/postfix.$instance.inet_interfaces.null";
	$timefile=file_time_min($file);
	if($timefile>5){
		error_log("$cmd",0);
		squid_admin_mysql(0,"$instance: inet_interfaces missing data parameter","Postfix claim \n$buffer\nArtica will change value to \"all\"","postfix",0);
		shell_exec_maillog("$cmd &");	
		@unlink($file);
		file_put_contents($file,"#");
	}else{
		error_log("$instance: inet_interfaces is null ?? but require 5mn to wait current:{$timefile}Mn",0);
	}
	return;	
}

if(preg_match("#bounce\[.+?fatal: bad string length 0 < 1: myorigin#",$buffer,$re)){
	error_log("myorigin is null ?? in postfix configuration file, try to repair",0);
	$file="/etc/artica-postfix/croned.1/postfix.myorigin.null";
	$timefile=file_time_min($file);
	if($timefile>5){
		squid_admin_mysql(0,"Postfix: myorigin missing data parameter","Postfix claim \n$buffer\nArtica will change value","postfix",0);
		shell_exec_maillog("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --networks &");	
		@unlink($file);
		file_put_contents($file,"#");
	}else{
		error_log("myorigin is null ?? but require 5mn to wait current:{$timefile}Mn",0);
	}
	return;	
}

if(preg_match("#local\[.+?warning: dict_ldap_connect: Unable to bind to server (.+?)\s+#",$buffer,$re)){
	error_log("{$re[1]} unavailable",0);
	$file="/etc/artica-postfix/croned.1/postfix.ldap.failed";
	$timefile=file_time_min($file);
	if($timefile>5){
		squid_admin_mysql(0,"Postfix: LDAP server {$re[1]} unavailable","Postfix claim \n$buffer\nplease check the LDAP server database","postfix",0);
		@unlink($file);
		file_put_contents($file,"#");
	}else{
		error_log("$re[1]} unavailable but require 5mn to wait current:{$timefile}Mn",0);
	}
	return;	
}


if(preg_match("#postqueue\[.+?fatal: bad string length 0.+?:\s+(.+?)\s+#",$buffer,$re)){
	error_log("{$re[1]} is null ?? in postfix configuration file",0);
	$file="/etc/artica-postfix/croned.1/postfix.postdrop.permissions";
	if(file_time_min($file)>5){
		squid_admin_mysql(0,"Postfix: {$re[1]} missing data parameter","Postfix claim \n$buffer\nContact your support team in order to fix this issue.","postfix",0);
		@unlink($file);
		file_put_contents($file,"#");
	}
	return true;
}

if(preg_match("#postfix\/master\[.+?fatal: bind 0\.0\.0\.0 port 25: Address already in use#",$buffer,$re)){
	squid_admin_mysql(0,"Postfix will be restarted","Postfix claims, $buffer","postfix",0);
	shell_exec_maillog("/etc/init.d/postfix restart-single &");
	return true;
}

if(preg_match("#postfix\/postdrop\[.+?warning: mail_queue_enter: create file maildrop\/.+?:\s+Permission denied#",$buffer,$re)){
	error_log("Permission denied on maildrop queue",0);
	$file="/etc/artica-postfix/croned.1/postfix.postdrop.permissions";
	if(file_time_min($file)>10){
		squid_admin_mysql(0,"Postfix: Permissions problems on postdrop queue","Postfix claim \n$buffer\nArtica will try to fix it","postfix",0);
		shell_exec_maillog("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --postdrop-perms &");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return true;
}

if(preg_match("#smtp\[.+?host\s+(.+?)\[.+?said:\s+421\s+4\.2\.1\s+MSG=.+?\(DNS:NR\)#",$buffer,$re)){
	error_log("mail Refused from {$re[1]}",0);
	$file="/etc/artica-postfix/croned.1/postfix.{$re[1]}.refused";
	if(file_time_min($file)>10){
		squid_admin_mysql(0,"Postfix: your messages has been refused from {$re[1]}","Postfix claim \n$buffer\nCheck your smtp configuration in order to be compliance for {$re[1]}","postfix",0);
		@unlink($file);
		file_put_contents($file,"#");
	}
	return true;
}


if(preg_match("#smtpd\[.+?NOQUEUE: reject:\s+RCPT from\s+(.+?)\[(.+?)\]:.+?<(.+?)>:\s+Recipient address rejected: Mail appeared to be SPAM or forged.+?from=<(.+?)>#",$buffer,$re)){
		error_log("mail Refused from {$re[1]} for {$re[4]}",0);
		$file="/etc/artica-postfix/croned.1/postfix.{$re[1]}.refused";
		$GLOBALS["maillog_tools"]->event_message_reject_hostname("Forged",$re[4],$re[3],$re[2],$re[1]);
		if(file_time_min($file)>10){
			squid_admin_mysql(0,"Postfix: your messages has been refused from {$re[1]} ({$re[2]}) it seems your Forged your messages","Postfix claim \n$buffer\nCheck your smtp configuration in order to be compliance for {$re[1]}","postfix",0);
			@unlink($file);
			file_put_contents($file,"#");
		}
		
		return true;
}

if(preg_match('#ClamAV-clamd.*?FAILED.*?output="(.*?):.*?Permission denied#',$buffer,$re)){
	$filename=$re[1];
	$dirname=dirname($filename);
	@chmod($dirname, 0777);
	return true;
}

if(preg_match("#:\s+discard:\s+body\s+.*?\s+from\s+(.*?)\[(.*?)\];\s+from=<(.*?)>\s+to=<(.*?)>\s+.*?>:\s+MBL-([0-9]+)#" ,$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("MalwarePatrol.{$re[5]}",$re[3],$re[4],$re[1],$re[2]);
	return true;
}

if(preg_match("#cleanup\[[0-9]+\]:\s+.*?:\s+(reject|redirect|warning|discard|prepend|replace|info):\s+body\s+.*?from\s+(.*?)\[(.+?)\];\s+from=<(.*?)>\s+to=<(.*?)>\s+proto=#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname($re[1],$re[4],$re[5],$re[2],$re[3]);
	return true;
}

if(preg_match("#\[.+?NOQUEUE: reject: RCPT from.+?\[(.+?)\]:.+?Mail appeared to be SPAM or forged.+?from=<(.+?)>\s+to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("Forged",$re[2],$re[3],null,$re[1]);
	return;
}


if(preg_match("#postscreen\[.+?NOQUEUE: reject: RCPT from\s+\[(.+?)\].+?Service currently unavailable;\s+from=<(.*?)>,\s+to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("PostScreen",$re[2],$re[3],null,$re[1]);
	return true;
}

if(preg_match("#\[.+?:\s+NOQUEUE: reject: RCPT from.+?\[(.+?)\]:.+?Sender address rejected: blacklisted sender;\s+from=<(.*)>\s+to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("blacklisted",$re[2],$re[3],$re[1]);
	return true;
}
if(preg_match("#\]: NOQUEUE: reject: RCPT from.+?\[(.+?)\]:.+?Banned destination domain.+?from=<(.*?)>\s+to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("Banned domain",$re[2],$re[3],$re[1]);
	return true;
}


if(preg_match("#smtpd\[.+?NOQUEUE: reject: RCPT from.+?\[(.+?)\]:.+?Recipient address rejected: Your MTA is listed in too many DNSBLs.+?from=<(.+?)>\s+to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("DNSBL",$re[1],$re[3],$re[4]);
	return true;
}


if(preg_match("#smtpd\[.*?warning: connect to 127\.0\.0\.1:7777: Connection refused#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/postfix.connexion-refused.".__LINE__.".error";
	error_log("Postfix connexion refused from iredMail",0);
	if(file_time_min($file)>10){
		$cmd="{$GLOBALS["NOHUP_PATH"]} /etc/init.d/iredmail restart >/dev/null 2>&1 &";
		shell_exec_maillog(trim($cmd));
		squid_admin_mysql(0,"Postfix: Unable to connect to iRedMail","Postfix claim\n$buffer\nArtica will restart iredMail service","postfix",0);
		@unlink($file);
		file_put_contents($file,"#");
	}
	
	return true;
}




if(preg_match("#postfix\/smtp.+?connect to\s+(.+?)\[(.+?)\]:([0-9]+):\s+Connection refused#",$buffer,$re)){
	$md5=md5($re[1]);
	$file="/etc/artica-postfix/croned.1/postfix.connexion-refused.$md5.error";
	error_log("Postfix connexion refused from {$re[1]}",0);
	if(file_time_min($file)>10){
		squid_admin_mysql(0,"Postfix: Unable to connect to {$re[1]} on port {$re[3]}","Postfix claim\n$buffer\nPlease check if {$re[2]} is available","postfix",0);
		@unlink($file);
		file_put_contents($file,"#");		
	}
	return true;
	
}




if(preg_match("#cleanup\[.+?:\s+(.+?):\s+reject: body.+?\s+from.+?\[(.+?)\];\s+from=<(.*?)>\s+to=<(.+?)>.+?Message Body rejected#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_milter_reject($re[1],"Banned words",$re[1],$re[2],$buffer);
	return true;
}

if(preg_match("#postscreen.+?NOQUEUE: reject: RCPT from \[(.+?)\].+?Service unavailable;.+?blocked using.+?; from=<(.+?)>, to=<(.+?)>#",$buffer,$re)){
	error_log("PostScreen RBL :{$re[1]} from {$re[2]} to {$re[2]}",0);
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("PostScreen RBL",$re[2],$re[3],$re[1]);
	return;
}


if(strpos($buffer,"warning: cannot get certificate from file /etc/ssl/certs/postfix/ca.crt")>0){
	$file="/etc/artica-postfix/croned.1/postfix.certificate.error";
	error_log("Postfix certificate problems",0);
	if(file_time_min($file)>10){
		squid_admin_mysql(0,"Postfix: SSL certificate error","Postfix claim\n$buffer\nArtica try to rebuild the certificate.","postfix",0);
		shell_exec_maillog("/usr/share/artica-postfix/exec.postfix.default-certificate.php &");
		@unlink($file);
		file_put_contents($file,"#");			
	}
	return true;
}

if(preg_match("#NOQUEUE: reject: CONNECT from.+?\[(.+?)\].+?: Client host rejected: Server configuration error;#",$buffer,$re)){
	error_log("postfix fatal error {$re[1]} rejected",0);
	$file="/etc/artica-postfix/croned.1/postfix.Server.configuration.error";
	if(file_time_min($file)>10){
		squid_admin_mysql(0,"Postfix: Server configuration error mails from {$re[1]} has been rejected","Postfix claim\n$buffer\nPlease check your configuration.","postfix",0);
		@unlink($file);
		file_put_contents($file,"#");
	}
	return true;
}




if(preg_match("#NOQUEUE: reject: RCPT from.+?\[(.+?)\]:.+?Client host rejected: Access denied;\s+from=<(.+?)>\s+to=<(.+?)>#",$buffer,$re)){
	error_log("Access denied :{$re[1]} from {$re[2]} to {$re[2]}",0);
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("Access denied",$re[2],$re[3],$re[1]);
	return true;
}

if(preg_match("#postfix.+?:\s+(.+):\s+milter-discard: END-OF-MESSAGE\s+from.+?\[(.+?)\]:\s+milter triggers DISCARD action;\s+from=<(.*?)>\s+to=<(.+?)>\s+#",$buffer,$re)){
	error_log("Rejected :{$re[1]} from {$re[2]} to {$re[2]}",0);
	$GLOBALS["maillog_tools"]->event_DISCARD($re[1],$re[3],$re[4],$buffer,$re[2]);
	return true;
}

if(preg_match("#smtpd\[.+?NOQUEUE: reject: MAIL from.+?\[(.+?)\]:.+?Sender address rejected: Domain not found;\s+from=<(.+?)>#",$buffer,$re)){
	error_log("Domain not found :{$re[1]} from {$re[2]}",0);
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("Domain not found",$re[2],null,$re[1]);
	return true;
}
if(preg_match("#smtpd\[.+?NOQUEUE: reject: MAIL from.+?\[(.+?)\]:.+?Sender address rejected: Access denied;\s+from=<(.+?)>#",$buffer,$re)){
	error_log("Access denied :{$re[1]} from {$re[2]}",0);
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("Access denied",$re[2],null,$re[1]);
	return true;
}

if(preg_match("#postfix.+?: warning: (.+?): hostname.+?verification failed: Temporary failure in name resolution#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection_error(null,$re[1],"verification failed");
	return true;
}
if(preg_match("#smtpd\[.+?:\s+reject:\s+CONNECT from\s+(.+?)\[([0-9\.]+)\]:\s+554.+?Service unavailable;.+?blocked#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection_error($re[1],$re[2],"RBL");	
	return true;
}


if(preg_match("#smtpd\[.+?warning:\s+(.+?):\s+hostname\s+(.+?)\s+verification failed: Name or service not known#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection_error($re[2],$re[1],"Name or service not known");
	return true;
}

if(preg_match('#warning.+?\[([0-9\.]+)\]:\s+SASL LOGIN authentication failed: authentication failure#',$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection_error($re[1],$re[1],"Login failed");
	return true;
}

if(preg_match("#NOQUEUE: reject:.+?from.+?\[([0-9\.]+)\]:.+?Service unavailable.+?blocked using.+?from=<(.+?)> to=<(.+?)> proto#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("RBL",$re[2],$re[3],$re[1]);
	return true;
}
if(preg_match("#smtpd.+?reject: RCPT from.+?\[(.+?)\]:\s+550.+?:.+Recipient address rejected:.+?because of previous errors.+?from=<(.+?)>\s+to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("RBL",$re[2],$re[3],$re[1]);
	return true;
}


if(preg_match("#smtpd.+?reject: RCPT from.+?\[(.+?)\]:\s+554.+?:.+Sender address rejected:.+?FORGED MAIL.+?from=<(.+?)>\s+to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("FORGED",$re[2],$re[3],$re[1]);
	return true;
}

if(preg_match("#:\s+NOQUEUE: reject: RCPT from.+?\[(.+?)\]:\s+550.+?:\s+Recipient address rejected: Mail appears to be SPAM or forged.+?from=<(.+?)>\s+to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("RBL",$re[2],$re[3],$re[1]);	
	return true;
}
if(preg_match("#smtpd.+?reject: RCPT from unknown\[(.+?)\]:\s+550.+?:.+Recipient address rejected:.+?DNSBLs.+?from=<(.+?)>\s+to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("RBL",$re[2],$re[3],$re[1]);
	return true;
}


if(preg_match("#smtpd\[.+?warning: Illegal address syntax from.+?\[(.+?)\] in MAIL#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection_error(null,$re[1],"Illegal address");
	return true;
}


if(preg_match("#postfix\/lmtp\[.+?:\s+(.+?):\s+to=<(.+)>,\s+relay=([0-9\.]+)\[.+?:[0-9]+,.+?status=deferred.+?430 Authentication required#",$buffer,$re)){
	error_log("postfix LMTP error to {$re[2]}",0);
	$file="/etc/artica-postfix/croned.1/postfix.lmtp.auth.failed";
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Mailbox Authentication required",$re[3],$re[2]);
	if(file_time_min($file)>5){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			squid_admin_mysql(0,"Postfix: LMTP Error","Postfix\n$buffer\nArtica will reconfigure LMTP settings","postfix",0);
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["PHP5_BIN"]} {$GLOBALS["MYPATH"]}/exec.postfix.maincf.php --mailbox-transport");
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	
	return true;
	
}

if(preg_match("#postfix\/lmtp\[.+?:\s+connect to ([0-9\.]+)\[.+?:[0-9]+:\s+Connection refused#",$buffer)){
	error_log("postfix LMTP error",0);
	$file="/etc/artica-postfix/croned.1/postfix.lmtp.cnx.refused";
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"LMTP Error","127.0.0.1",$re[2]);
	if(file_time_min($file)>5){
        squid_admin_mysql(0,"Postfix: LMTP Error","Postfix\n$buffer\nArtica will reconfigure LMTP settings","postfix",0);
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["PHP5_BIN"]} {$GLOBALS["MYPATH"]}/exec.postfix.maincf.php --mailbox-transport");
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	
	return true;
}
if(preg_match("#postfix\/.+?:\s+warning:\s+problem talking to server\s+[0-9\.]+:12525:\s+Connection refused#",$buffer)){
	error_log("postfix policyd-weight error",0);
	$file="/etc/artica-postfix/croned.1/postfix.policyd-weight.conect.failed";
	
	if(file_time_min($file)>10){
		squid_admin_mysql(0,"Postfix: Policyd-weight server connection problem","Postfix\n$buffer\nArtica will reconfigure restart policyd-weight service","postfix",0);
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/policyd-weight start");
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	
	return true;
}

if(preg_match("#postfix\/postfix-script\[.+?\]: fatal: the Postfix mail system is not running#",$buffer,$re)){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["postfix_bin_path"]} start");
	}
	return true;
}

if(preg_match("#smtp\[.+? fatal: specify a password table via the.+?smtp_sasl_password_maps.+?configuration parameter#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/postfix.smtp_sasl_password_maps.error";
	error_log("postfix -> smtp_sasl_password_maps",0);
	if(file_time_min($file)>5){
		squid_admin_mysql(0,"Postfix configuration problem","Postfix claim\n$buffer\nArtica will disable SMTP Sasl feature","postfix",0);
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;	
}

if(preg_match("#amavis\[.+?TROUBLE.+?in child_init_hook: BDB can't connect db env.+?No such file or directory#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/amavis.BDB.error";
	error_log("amavis BDB ERROR",0);
	if(file_time_min($file)>5){
		squid_admin_mysql(0,"AMAVIS BDB Error","amavis claim\n$buffer\nArtica will restart amavis service","postfix",0);
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/amavis restart");
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;	
}
if(preg_match("#amavis\[.*?\]:.*?DIE.*?BDB\s+can't connect db.*?\/var(.+?): No such file or directory#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/amavis.BDB.error";
	error_log("amavis BDB ERROR",0);
	if(file_time_min($file)>5){
		squid_admin_mysql(0,"AMAVIS BDB Error","amavis claim\n$buffer\nArtica will restart amavis service","postfix",0);
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/amavis restart");
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;
}



if(preg_match("#amavis\[.+?custom checks error:\s+Insecure dependency in connect while running with -T switch at .+?/IO/Socket\.pm line 114#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/amavis.Compress-Raw-Zlib.error";
	error_log("amavis Compress-Raw-Zlib error -> check Compress-Raw-Zlib version",0);
	if(file_time_min($file)>5){
		squid_admin_mysql(0,"AMAVIS dependency Error","amavis claim\n$buffer\nArtica will try to check depencies, especially \Compress-Raw-Zlib\"","postfix",0);
		//THREAD_COMMAND_SET("/etc/init.d/amavis restart");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;	
}


if(preg_match("#amavis\[.+?connect_to_ldap: bind failed: LDAP_INVALID_CREDENTIALS#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/amavis.LDAP.error";
	error_log("amavis LDAP ERROR",0);
	if(file_time_min($file)>5){
		squid_admin_mysql(0,"AMAVIS LDAP connexion Error","amavis claim\n$buffer\nArtica will restart amavis service to reconfigure it","postfix",0);
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/amavis restart");
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;	
}

if(preg_match("#Decoding of p[0-9]+\s+\(.+?data, at least.+?failed, leaving it unpacked: Compress::Raw::Zlib version\s+(.+?)\s+required.+?this is only version\s+(.+?)\s+#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/amavis.Compress.Raw.Zlib.error";
	error_log("amavis Compress::Raw::Zlib need to be upgraded",0);
	if(file_time_min($file)>20){
		
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			squid_admin_mysql(0,"AMAVIS Compress::Raw::Zlib need to be upgraded from {$re[1]} to {$re[2]}","amavis claim\n$buffer\nArtica will install a newest Compress::Raw::Zlib version","postfix",0);
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-make APP_COMPRESS_ROW_ZLIB");
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;	
}

if(preg_match("#smtp\[.+?:\s+fatal: valid hostname or network address required in server description:(.+?)#",$buffer,$re)){
    squid_admin_mysql(0,"{$re[1]} Bad configuration parameters","Postfix claim\n$buffer\nPlease come back to the interface and check your configuration!","postfix");
	return;
}


if(preg_match("#.+?postfix-.+?\/master\[.+?:\s+fatal:\s+bind\s+[0-9\.]+\s+port\s+25:\s+Address already in use#",$buffer,$re)){
	error_log("Address already in use -> restart postfix",0);
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
		squid_admin_mysql(0,"Postfix will be restarted","Line: ". __LINE__."\nPostfix claims, $buffer","postfix");
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/postfix restart-single");
	}
	return null;	
}

if(preg_match("#postfix\/.+?warning:\s+(.+?)\s+and\s+(.+?)\s+differ#",$buffer,$re)){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/bin/cp -pf {$re[2]} {$re[1]}");}
	return true;
}

if(preg_match("#smtpd\[.+?warning:\s+connect to Milter service unix:(.+?):\s+Permission denied#",$buffer,$re)){
	error_log("chown postfix:postfix {$re[1]}",0);
	shell_exec_maillog("/bin/chown postfix:postfix {$re[1]} &");
	return;
}

if(preg_match("#spamd\[[0-9]+.+?Can.+?locate\s+Mail\/SpamAssassin\/CompiledRegexps\/body_[0-9]+\.pm#",$buffer,$re)){
	SpamAssassin_error_saupdate($buffer);
	return null;
}

if(preg_match("#zarafa-monitor.+?:\s+Unable to get store entry id for company\s+(.+?), error code#",$buffer,$re)){
	zarafa_store_error($buffer);
	return null;
}



if(preg_match("#postfix\/lmtp.+?:\s+(.+?):\s+to=<(.+?)>.+?lmtp.+?deferred.+?451.+?Mailbox has an invalid format#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Mailbox corrupted",null,$re[2]);
	mailbox_corrupted($buffer,$re[2]);
	return null;
	}
	

	
if(preg_match("#postfix\/lmtp.+?(.+?):\s+to=<(.+?)>.+?lmtp.+?status=deferred.+?452.+?Over quota#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Over quota",null,$re[2]);
	mailbox_overquota($buffer,$re[2]);
	return null;
	}	

if(preg_match("#postfix\/.+?:(.+?):\s+milter-reject: END-OF-MESSAGE\s+.+?Error in processing.+?ALL VIRUS SCANNERS FAILED;.+?from=<(.+?)>\s+to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_milter_reject($re[1],"antivirus failed",$re[1],$re[2],$buffer);
	clamav_error_restart($buffer);
	return null;	
	}

if(preg_match("#postfix\/.+?:(.+?):\s+to=<(.+?)>,.+?\[(.+?)\].+?status=deferred.+?virus_scan FAILED#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"antivirus failed",$re[3],$re[2]);
	return null;
	}
	
if(preg_match("#smtp\[[0-9]+\]:\s+(.+?):\s+to=<(.+?)>,\s+relay=127\.0\.0.+:[0-9]+,.+?deferred.+?451.+?during fwd-connect\s+\(Negative greeting#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Internal timed-out","127.0.0.1",$re[2]);
	$file="/etc/artica-postfix/croned.1/timedout-amavis";
	error_log("fwd-connect ERROR",0);
	if(file_time_min($file)>5){
		error_log("fwd-connect ERROR -> restarting Postfix",0);
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["postfix_bin_path"]} stop");
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["postfix_bin_path"]} start");
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;		
}
	
	
if(preg_match("#master\[.+?:\s+fatal:\s+binds\+(.+?)\s+port\s+(.+?).+?Address already in use#",$buffer,$re)){
	postfix_bind_error($re[1],$re[2],$buffer);
	return null;
}


if(preg_match("#postfix.+?\[.+?fatal: open\s+\/etc\/postfix-(.+?)\/main\.cf:\s+No such file or directory#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/instance-{$re[1]}.no-such-file";
	error_log("{$re[1]} -> bad main.cf ".dirname($re[1]));
	if(file_time_min($file)>5){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			squid_admin_mysql(0,"Postfix missing main.cf for {$re[1]} instance","Postfix claim\n$buffer\nArtica will reconfigure this instance","postfix",0);

		}
	@unlink($file);
	file_put_contents($file,"#");
	}
	return null;		
}

if(preg_match("#postmulti.+?fatal:.+?Failed to obtain all required /etc/postfix-(.+?)\/main\.cf parameters#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/instance-{$re[1]}.no-maincf-params";
	error_log("{$re[1]} -> bad main.cf ".dirname($re[1]));
	if(file_time_min($file)>5){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			squid_admin_mysql(0,"Postfix missing main.cf for {$re[1]} instance","Postfix claim\n$buffer\n","postfix",0);

		}
	@unlink($file);
	file_put_contents($file,"#");
	}
	return null;		
}
if(preg_match("#postfix-(.+?)\/postqueue\[.+?warning: Mail system is down#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/instance-{$re[1]}.down";
	$ftime=file_time_min($file);
	error_log("{$re[1]} -> system down ({$ftime}mn)",0);
	if($ftime>=5){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$cmd="{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix-multi.php --instance-start {$re[1]}";
			squid_admin_mysql(0,"Postfix {$re[1]} instance stopped","Postfix claim\n$buffer\nArtica will start this instance","postfix",0);
			error_log("$cmd",0);
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;		
}

if(preg_match("#postfix-(.+?)\/master\[.+?daemon started#",$buffer,$re)){
	error_log("{$re[1]} -> system start",0);
	squid_admin_mysql(0,"Postfix {$re[1]} instance started","Postfix notify\n$buffer\n","postfix",0);
	return null;		
}


if(preg_match("#postfix\[.+?fatal: parameter inet_interfaces: no local interface found for ([0-9\.]+)#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/inet_interfaces-{$re[1]}.down";
	$ftime=file_time_min($file);
	error_log("{$re[1]} -> interface down ({$ftime}mn)",0);
	if($ftime>=5){
		squid_admin_mysql(0,"Postfix interface {$re[1]} down","Postfix claim\n$buffer\n
		Check your configuration settings in order to see
		why \"{$re[1]}\" is not loaded",__FILE__,__LINE__);
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;
}

if(preg_match("#postmulti-script\[.+?warning: (.+?): please verify contents and remove by hand#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/". md5("{$re[1]}").".delete";
	$ftime=file_time_min($file);
	error_log("{$re[1]} -> delete",0);
	if($ftime>=5){
		if(is_dir($re[1])){
			if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
				$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/bin/rm -rf {$re[1]} &");
			}
			@unlink($file);
			file_put_contents($file,"#");
		}
	}
	return null;
}



if(preg_match("#.+?\/(.+?)\[.+?:\s+fatal:\s+open\s+(.+?):\s+No such file or directory#",$buffer,$re)){
	postfix_nosuch_fileor_directory($re[1],$re[2],$buffer);
	return null;
}
if(preg_match("#.+?\/(.+?)\[.+?:\s+fatal:\s+open\s+(.+?)\.db:\s+Bad file descriptor#",$buffer,$re)){
	postfix_baddb($re[1],$re[2],$buffer);
	return null;
}

if(preg_match("#postfix\/qmgr.+?:\s+(.+?):\s+from=<(.*?)>,\s+status=expired, returned to sender#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_finish($re[1],null,"expired","expired",$re[2],$buffer);
	return null;
}


if(preg_match("#postfix postmulti\[[0-9+]\]: fatal: No matching instances#",$buffer,$re)){
	multi_instances_reconfigure($buffer);
	return null;
}

if(preg_match('#NOQUEUE: reject: MAIL from.+?452 4.3.1 Insufficient system storage#',$buffer)){
	$file="/etc/artica-postfix/croned.1/postfix.storage.error";
	if(file_time_min($file)>10){
		squid_admin_mysql(0,"Postfix Insufficient storage disk space!!! ","Postfix claim: $buffer\n Please check your hard disk space !" ,"system",0);
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;
}


if(preg_match("#starting amavisd-milter.+?on socket#",$buffer)){
	squid_admin_mysql(0,"Amavisd New has been successfully started",$buffer,"system",0); 
	return;
}


if(preg_match("#kavmilter\[.+?\]:\s+Could not open pid file#",$buffer)){
	$file="/etc/artica-postfix/croned.1/postfix.kavmilter.pid.error";
		if(file_time_min($file)>10){
			error_log("Kaspersky Milter PID error",0);
			if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
				squid_admin_mysql(0,"Kaspersky Milter PID error","kvmilter claim $buffer\nArtica will try to restart it","postfix",0);
				$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/etc/init.d/artica-postfix restart kavmilter');
			}
			@unlink($file);
		}else{
			error_log("Kaspersky Milter PID error, but take action after 10mn",0);
		}	
	file_put_contents($file,"#");	
	return null;
	
}	


// HACK POP3
if(preg_match("#cyrus\/pop3\[.+?badlogin.+?.+?\[(.+?)\]\s+APOP.+?<(.+?)>.+?SASL.+?: user not found: could not find password#",$buffer,$re)){
	hackPOP($re[1],$re[2],$buffer);
	return;
	}
if(preg_match("#cyrus\/pop3\[.+?:\s+badlogin:\s+.+?\[(.+?)\]\s+plaintext\s+(.+?)\s+SASL.+?authentication failure:#",$buffer,$re)){
	hackPOP($re[1],$re[2],$buffer);
	return;
}

if(preg_match("#zarafa-gateway\[.+?: Failed to login from\s+(.+?)\s+with invalid username\s+\"(.+?)\"\s+or wrong password#",$buffer,$re)){
	hackPOP($re[1],$re[2],$buffer);
	return;
}


if(preg_match("#postfix\/.+?warning: TLS library problem.+?system library:fopen:No such file or directory.+?\('(.+?)',#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/postfix.tls.{$re[1]}.error";
		if(file_time_min($file)>5){
			error_log("TLS {$re[1]} No such file",0);
			if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
				squid_admin_mysql(0,"Postfix error TLS on {$re[1]} (no such file)","Postfix claim $buffer\nArtica will try to repair it by rebuilding certificate","postfix",0);
				$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/usr/share/artica-postfix/exec.postfix.default-certificate.php');
			}
			@unlink($file);
		}else{
			error_log("TLS {$re[1]} No such file failure, but take action after 5mn",0);
		}	
	return null;
}


if(preg_match("#smtpd.+?:\s+warning: SASL authentication failure: no secret in database#",$buffer)){
	$file="/etc/artica-postfix/croned.1/postfix.sasl.secret.error";
		if(file_time_min($file)>10){
			error_log("SASL authentication failure",0);
			if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
				squid_admin_mysql(0,"Postfix error SASL","Postfix claim $buffer\nArtica will try to repair it","postfix",0);
				$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/usr/share/artica-postfix/bin/artica-install --postfix-sasldb2');
			}
			@unlink($file);
		}else{
			error_log("SASL authentication failure, but take action after 10mn",0);
		}	
	return null;
	
}




if(preg_match("#postfix\/smtp\[.+?:\s+(.+?):\s+to=<(.+?)>.+?status=deferred\s+\(SASL authentication failed.+?\[(.+?)\]#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"authentication failed",$re[3],$re[2]);
	smtp_sasl_failed($re[3],$re[3],$buffer);
}


if(preg_match("#postfix\/smtp\[.+?:\s+(.+?):\s+to=<(.+?)>.+?status=bounced.+?.+?\[(.+?)\]\s+said:\s+554.+?http:\/\/#",$buffer,$re)){
	ImBlackListed($re[3],$buffer);
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Your are blacklisted",$re[3],$re[2]);
	return null;
}

if(preg_match("#postfix\/(cleanup|bounce|smtp|smtpd|flush|trivial-rewrite)\[.+?warning: database\s+(.+?)\.db\s+is older than source file\s+(.+)#",$buffer,$re)){
	postfix_compile_db($re[3],$buffer);
	return null;
}
if(preg_match("#postfix\/(cleanup|bounce|smtp|smtpd|flush|trivial-rewrite)\[.+?fatal: open database\s+(.+?)\.db:\s+No such file or directory#",$buffer,$re)){
	postfix_compile_missing_db($re[2],$buffer);
	return null;
}

if(preg_match("#postfix\/smtp\[.+?:\s+(.+?):\s+host.+?\[(.+?)\]\s+said:\s+[0-9]+\s+invalid sender domain#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection_error($re[1],$re[2],"invalid sender domain");
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"invalid sender domain",$re[2],null);
	return null;
}

if(preg_match("#warning: connect to Milter service unix:(.+?)clamav-milter.ctl: Connection refused#",$buffer,$re)){
	MilterClamavError($buffer,"$re[1]/clamav-milter.ctl");
	return null;
}



if(preg_match("#warning: connect to Milter service unix:(.+?)greylist.sock: No such file or directory#",$buffer,$re)){
	miltergreylist_error($buffer,"{$re[1]}/greylist.sock");
	return null;
}

if(preg_match("#postfix\/smtpd\[.+?warning: connect to Milter service unix:(.+?)milter-greylist.sock: No such file or directory#",$buffer,$re)){
	miltergreylist_error($buffer,"{$re[1]}/milter-greylist.sock");
	return null;
}






if(preg_match('#milter-greylist: greylist: Unable to bind to port (.+?): Permission denied#',$buffer,$re)){
	miltergreylist_error($buffer,$re[1]);
}

if(preg_match('#]:\s+(.+?): to=<(.+?)>.+?socket/lmtp\].+?status=deferred.+?lost connection with.+?end of data#',$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_finish($re[1],$re[2],"deferred","mailbox service error",null,$buffer);
	return null;
}




if(preg_match('#badlogin: \[(.+?)\] plaintext\s+(.+?)\s+SASL\(-13\): authentication failure: checkpass failed#',$buffer,$re)){
	if($GLOBALS["DisableMailBoxesHack"]==1){return;}
	if($GLOBALS["GlobalIptablesEnabled"]<>1){return;}
	$date=date('Y-m-d H');
	$_GET["IMAP_HACK"][$re[1]][$date]=$_GET["IMAP_HACK"][$re[1]][$date]+1;
	error_log("cyrus Hack:bad login {$re[1]}:{$_GET["IMAP_HACK"][$re[1]][$date]} retries",0);
	if($_GET["IMAP_HACK"][$re[1]][$date]>15){
		squid_admin_mysql(0,"Cyrus HACKING !!!!","Build iptables rule \"iptables -I INPUT -s {$re[1]} -j DROP\" for {$re[1]}!\nlaster error: $buffer","mailbox",0);
		shell_exec_maillog("iptables -I INPUT -s {$re[1]} -j DROP");
		error_log("IMAP Hack: -> iptables -I INPUT -s {$re[1]} -j DROP",0);
		unset($_GET["IMAP_HACK"][$re[1]]);
	}
	
	return null;
}



if(preg_match('#badlogin: \[(.+?)\] plaintext\s+(.+?)\s+SASL\(-1\): generic failure: checkpass failed#',$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/cyrus.checkpass.error";
	if(file_time_min($file)>10){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			squid_admin_mysql(0,"Cyrus auth error","Artica will restart messaging service\n\"$buffer\"","mailbox",0);
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/etc/init.d/cyrus-imapd restart');
		}
		@unlink($file);
	}
	return null;
}
if(preg_match('#cyrus\/lmtpunix.+?DBERROR:\s+opening.+?\.db:\s+Cannot allocate memory#',$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/cyrus.dberror.restart.error";
	if(file_time_min($file)>10){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			squid_admin_mysql(0,"Cyrus DBERROR error","Artica will restart messaging service\n\"$buffer\"","mailbox",0);
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/etc/init.d/cyrus-imapd restart');
		}
		@unlink($file);
	}
	return null;
}
if(preg_match('#cyrus\/imap.+?DBERROR.+?Open database handle:\s+(.+?)tls_sessions\.db#',$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/cyrus.dberror.tls_sessions.error";
	if(file_time_min($file)>10){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			squid_admin_mysql(0,"Cyrus DBERROR error","Artica will delete {$re[1]}tls_sessions.db file\n\"$buffer\"","mailbox",0);
			@unlink("{$re[1]}tls_sessions.db");
		}
		@unlink($file);
	}
	return null;
}


if(preg_match('#cyrus\/notify.+?DBERROR db[0-9]: PANIC: fatal region error detected; run recovery#',$buffer)){
	$file="/etc/artica-postfix/croned.1/cyrus.db.error";
	if(file_time_min($file)>10){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$buffer="Artica has detected a fatal error on cyrus\n$buffer\nArtica will try to repair it but it should not working\n";
			$buffer=$buffer."Perhaps you need to contact your support to correctly recover cyrus databases\n";
			$buffer=$buffer."Notice,read this topic : http://www.gradstein.info/software/how-to-recover-from-cyrus-when-you-have-some-db-errors/\n";
			THREAD_COMMAND_SET('/usr/share/artica-postfix/bin/artica-install --cyrus-recoverdb');
			squid_admin_mysql(0,"Cyrus database error !!",$buffer,"mailbox",0);
		}
		error_log("DBERROR detected, take action",0);
		@unlink($file);
		file_put_contents($file,"#");
	}else{
		error_log("(fatal region error detected; run recovery) DBERROR detected, but take action after 10mn",0);
	}
	return null;	
}


if(preg_match("#cyrus.+?DBERROR\s+db[0-9]+:\s+DB_AUTO_COMMIT may not be specified in non-transactional environment#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/cyrus.db.error";
	if(file_time_min($file)>10){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$buffer="Artica has detected a fatal error on cyrus\n$buffer\nArtica will try to repair it but it should not working\n";
			$buffer=$buffer."Perhaps you need to contact your support to correctly recover cyrus databases\n";
			$buffer=$buffer."Notice,read this topic : http://www.gradstein.info/software/how-to-recover-from-cyrus-when-you-have-some-db-errors/\n";
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/usr/share/artica-postfix/bin/artica-install --cyrus-ctl-cyrusdb');
			squid_admin_mysql(0,"Cyrus database error !!",$buffer,"mailbox",0);
		}
		error_log("DBERROR detected, take action",0);
		@unlink($file);
		file_put_contents($file,"#");
	}else{
		error_log("(DB_AUTO_COMMIT may not be specified in non-transactional) DBERROR detected, but take action after 10mn",0);
	}
	return null;
}

if(preg_match("#tlsmgr.+?fatal: open database .+?Stale NFS file handle#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/tlsmgr.Stale.NFS.file.handle";
	if(file_time_min($file)>10){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$buffer="Artica has detected a fatal error on Postfix (tls manager)\n$buffer\nTo fix this issue, you need to reboot the computer\n";
			$buffer=$buffer."In order to release locked file\nIf reboot trough Artica did not working, run this commandline :\nshutdown -rF now";
			squid_admin_mysql(0,"Stale NFS file handle !!",$buffer,"postfix",0);
			error_log("Stale NFS file handle",0);
			@unlink($file);
		}
		file_put_contents($file,"#");
	}else{
		error_log("tlsmgr:Stale NFS file handle, but take action after 10mn",0);
	}
	return null;
}






if(preg_match("#cyrus.+?:\s+DBERROR:\s+opening.+?mailboxes.db:\s+cyrusdb error#",$buffer)){
	$file="/etc/artica-postfix/croned.1/cyrus.db.error";
	if(file_time_min($file)>10){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$buffer="Artica has detected a fatal error on cyrus\n$buffer\nArtica will try to repair it but it should not working\n";
			$buffer=$buffer."Perhaps you need to contact your support to correctly recover cyrus databases\n";
			$buffer=$buffer."Notice,read this topic : http://www.gradstein.info/software/how-to-recover-from-cyrus-when-you-have-some-db-errors/\n";
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/usr/share/artica-postfix/bin/artica-install --cyrus-recoverdb');
			squid_admin_mysql(0,"Cyrus database error !!",$buffer,"mailbox",0);
		}
		@unlink($file);
		file_put_contents($file,"#");
	}else{
		error_log("DBERROR detected, but take action after 10mn",0);
	}
	return null;	
}
if(preg_match("#IMAP Login from\s+(.*?)\s+for user\s+(.+)#",$buffer,$re)){
	$service="imap";
	$server=trim($re[2]);
	$server_ip=null;
	$user=trim($re[4]);
	cyrus_imap_conx($service,$server,$server_ip,$user);
}


if(preg_match('#cyrus\/(.+?)\[.+?login:(.+?)\[(.+?)\]\s+(.+?)\s+.+?User#',$buffer,$re)){
	$service=trim($re[1]);
	$server=trim($re[2]);
	$server_ip=trim($re[3]);
	$user=trim($re[4]);
	cyrus_imap_conx($service,$server,$server_ip,$user);
	return null;
}

if(preg_match("#zarafa-gateway\[.+?:\s+IMAP Login from\s+(.+)\s+for user\s+(.+?)\s+#",$buffer,$re)){
	$service="IMAP";
	$server=trim($re[1]);
	$server_ip=trim($re[1]);
	$user=trim($re[2]);
	cyrus_imap_conx($service,$server,$server_ip,$user);
	return null;
}




if(preg_match('#cyrus\/ctl_mboxlist.+?DBERROR: reading.+?, assuming the worst#',$buffer)){
	$file="/etc/artica-postfix/croned.1/cyrus.db1.error";
	if(file_time_min($file)>10){
		$buffer="Artica has detected a fatal error on cyrus\n$buffer\n\n";
		squid_admin_mysql(0,"Cyrus database error !!",$buffer,"mailbox",0);
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;	
}
if(preg_match('#cyrus\/sync_client.+?Can not connect to server#',$buffer)){
	$file="/etc/artica-postfix/croned.1/cyrus.cluster.error";
	if(file_time_min($file)>10){
		$buffer="Artica has detected that the cyrus cluster replica is not available on cyrus\n$buffer\n\n";
		squid_admin_mysql(0,"Cyrus replica not available",$buffer,"mailbox",0);
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;	
}

if(preg_match('#cyrus\/sync_client.+?connect.+?failed: No route to host#',$buffer)){
	$file="/etc/artica-postfix/croned.1/cyrus.cluster.error";
	if(file_time_min($file)>10){
		$buffer="Artica has detected that the cyrus cluster replica is not available on cyrus\n$buffer\n\n";
		squid_admin_mysql(0,"Cyrus replica not available",$buffer,"mailbox",0);
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;	
}

if(preg_match('#warning: dict_ldap_connect: Unable to bind to server ldap#',$buffer)){
	$file="/etc/artica-postfix/croned.1/ldap.error";
	if(file_time_min($file)>10){
		squid_admin_mysql(0,"Postfix is unable to connect to ldap server ",$buffer,"system",0);
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;
}





if(preg_match('#service pop3 pid.+?in BUSY state and serving connection#',$buffer)){
	$file="/etc/artica-postfix/croned.1/pop3-busy.error";
	if(file_time_min($file)>10){
		squid_admin_mysql(0,"Pop3 service is overloaded","pop3 report:\n$buffer\nPlease,increase pop3 childs connections in artica Interface","mailbox",0);
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;
}

if(preg_match('#milter inet:[0-9\.]+:1052.+?Connection timed out#',$buffer)){
	$file="/etc/artica-postfix/croned.1/KAV-TIMEOUT.error";
	if(file_time_min($file)>10){
		squid_admin_mysql(0,"Postfix service Cannot connect to Kaspersky Antivirus milter",
		"it report:\n$buffer\nPlease,disable Kaspersky service or contact your support",
		"postfix");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;
}

if(preg_match('#milter unix:/var/run/milter-greylist/milter-greylist.sock.+?Connection timed out#',$buffer)){
	$file="/etc/artica-postfix/croned.1/miltergreylist-TIMEOUT.error";
	if(file_time_min($file)>10){
		squid_admin_mysql(0,"milter-greylist error",
		"it report:\n$buffer\nPlease,investigate what plugin cannot send to milter-greylist events",
		"postfix");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;
}


if(preg_match('#SASL authentication failure: cannot connect to saslauthd server#',$buffer)){
	$file="/etc/artica-postfix/croned.1/saslauthd.error";
	if(file_time_min($file)>10){
		squid_admin_mysql(0,"saslauthd failed to run","it report:\n$buffer\nThis error is fatal, nobody can be logged on the system.","mailbox",0);
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;
}


if(preg_match("#smtp.+?warning:\s+(.+?)\[(.+?)\]:\s+SASL DIGEST-MD5 authentication failed#",$buffer,$re)){
	$router_name=$re[1];
	$ip=$re[2];
	smtp_sasl_failed($router_name,$ip,$buffer);
	return null;
}



if(preg_match('#warning: connect to Milter service unix:/var/run/kas-milter.socket: Permission denied#',$buffer)){
	$file="/etc/artica-postfix/croned.1/kas-perms.error";
	if(file_time_min($file)>10){
		squid_admin_mysql(0,"Kaspersky Anti-spam socket error","it report:\n$buffer\nArtica will restart kas service...","postfix",0);
		@unlink($file);
		file_put_contents($file,"#");
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/etc/init.d/artica-postfix restart kas3');
		}
		
	}
	return null;
}


if(preg_match('#smtpd.+?warning: problem talking to server (.+?):\s+Connection refused#',$buffer,$re)){
	$pb=md5($re[1]);
	
	$file="/etc/artica-postfix/croned.1/postfix-talking.$pb.error";
	$time=file_time_min($file);
	if($time>10){
		error_log("Postfix routing error {$re[1]}",0);
		squid_admin_mysql(0,"Postfix routing error {$re[1]}","it report:\n$buffer\nPlease take a look of your routing table","postfix",0);
		@unlink($file);
		file_put_contents($file,"#");
	}
	error_log("Postfix routing error {$re[1]} (SKIP) $time/10mn",0);
	return null;
	
}



if(preg_match("#sync_client.+?connect\((.+?)\) failed: Connection refused#",$buffer,$re)){
$file="/etc/artica-postfix/croned.1/".md5($buffer);
	if(file_time_min($file)>10){
		squid_admin_mysql(0,"Cyrus replica {$re[1]} cluster failed","it report:\n$buffer\n
		please check your support, mails will not be delivered until replica is down !","mailbox");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;	
}


if(preg_match("#could not connect to amavisd socket /var/spool/postfix/var/run/amavisd-new/amavisd-new.sock: No such file or directory#",$buffer)){
	amavis_socket_error($buffer);
	return null;
	}
	
if(preg_match("#could not connect to amavisd socket.+?Connection timed out#",$buffer)){
	amavis_socket_error($buffer);
	return null;	
}

if(preg_match("#NOQUEUE: reject:.+?from.+?\[([0-9\.]+)\]:.+?Sender address rejected: Domain not found; from=<(.+?)> to=<(.+?)> proto#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("Domain not found",$re[2],$re[3],$re[1]);
	error_log("{$re[1]} Domain not found from=<{$re[2]}> to=<{$re[3]}>",0);
	return null;
	}
	


if(preg_match("#smtpd.+?NOQUEUE:.+?from.+?\[(.+?)\].+?Client host rejected.+?reverse hostname.+?from=<(.+?)>.+?to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("hostname not found",$re[2],$re[3],$re[1]);
	return null;
}

if(preg_match("#smtpd.+?NOQUEUE: reject.+?from.+?\[(.+?)\].+?Helo command rejected:.+?from=<(.+?)> to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("Helo command rejected",$re[2],$re[3],$re[1]);
	return null;
}
if(preg_match("#smtpd.+?NOQUEUE: reject.+?from.+?\[(.+?)\].+?4.3.5 Server configuration problem.+?from=<(.+?)> to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("Server configuration problem",$re[2],$re[3],$re[1]);
	return null;
}




if(preg_match("#postfix.+?\[.+?reject: header.+?from.+?\[([0-9\.]+)\];\s+from=<(.*?)>\s+to=<(.+?)>.+? too many rec.+?pients#",$buffer,$re)){
	error_log("too many recipients from {$re[2]} to {$re[3]}",0);
	if($GLOBALS["PostfixNotifyMessagesRestrictions"]==1){
		error_log("-> notification...",0);
		$GLOBALS["CLASS_UNIX"]->send_squid_admin_mysql(0,"Blocked message too many recipients from {$re[2]}","Postfix claims $buffer","postfix",0);
	}
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("too many recepients",$re[2],$re[3],$re[1]);
	return null;
}



if(preg_match("#cyrus.+?badlogin:\s+(.+?)\s+\[(.+?)\]\s+.+?\s+(.+?)\s+(.+)#",$buffer,$re)){
	$router=$re[1];
	$ip=$re[2];
	$user=$re[3];
	$error=$re[4];
	cyrus_bad_login($router,$ip,$user,$error);
	return null;
}



if(preg_match("#IOERROR.+?fstating sieve script\s+(.+?):\s+No such file or directory#",$buffer,$re)){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/bin/touch \"".trim($re[1])."\"");
	}
	return null;
}



if(preg_match("#smtp.+?\].+?([A-Z0-9]+):\s+to=<(.+?)>.+?status=deferred.+?\((.+?)command#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"deferred",$re[2],$re[3]);
	return null;
}



if(preg_match("#smtp.+?:\s+(.+?):\s+to=<(.+?)>,\s+relay=none,.+?status=deferred \(connect to .+?\[(.+?)\].+?Connection refused#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Connection refused",$re[2],$re[3]);
	return null;
}



if(preg_match("#smtp.+?\].+?([A-Z0-9]+):.+?SASL authentication failed#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Authentication failed");
	return null;
}
if(preg_match("#smtp.+?\].+?([A-Z0-9]+):.+?refused to talk to me.+?554 RBL rejection#",$buffer,$re)){
	ImBlackListed($re[2],$buffer);
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Your are blacklisted");
	return null;
}


if(preg_match("#smtp\[.+?:\s+(.+?):\s+to=<(.+?)>,\s+relay=.+?\[(.+?)\].+?status=deferred.+?refused to talk to me#",$buffer,$re)){
	ImBlackListed($re[3],$buffer);
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Your are blacklisted",$re[3],$re[2]);
	return null;
}

if(preg_match("#postfix\/bounce\[.+?:\s+(.+?):\s+sender non-delivery notification#",$buffer,$re)){
	error_log("{$re[1]} non-delivery",0);
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"non-delivery",null,null);
	return null;
	}	


if(preg_match("#smtp\[.+?\]:\s+(.+?):\s+to=<(.+?)>, relay=(.+?)\[.+?status=bounced\s+\(.+?loops back to myself#",$buffer,$re)){
	if(!is_dir("/etc/artica-postfix/croned.1")){@mkdir("/etc/artica-postfix/croned.1",0755,true);}
	
	$file="/etc/artica-postfix/croned.1/postfix.loops.back.to.myself";
	if(file_time_min($file)>10){
		shell_exec("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --urgency >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
	}
	
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"loops back to myself",$re[3],$re[2]);
	
	
	
	
	return null;
}

if(preg_match("#smtp\[.+?:\s+(.+?): host.+?\[(.+?)\] said.+?<(.+?)>:.+?Greylisting in action#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Greylisted",$re[2],$re[3]);		
	return null;	
}



if(preg_match("#smtp\[.+?:\s+(.+?):\s+host.+?\[(.+?)\]\s+refused to talk to me:#",$buffer,$re)){
	ImBlackListed($re[2],$buffer);
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Your are blacklisted",$re[2]);
	return null;
}

if(preg_match("#\/cleanup.*?:\s+([A-Z0-9]+):\s+redirect:.*?from\s+(.+?)\[([0-9\.]+)\];\s+from=<(.*?)>\s+to=<(.*?)>#", $buffer,$re)){
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Redirect",$re[2],$re[5],$re[4],$re[3]);
	return null;
}



if(preg_match('#milter-greylist:.+?:.+?addr.+?from <(.+?)> to <(.+?)> delayed for#',$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_rejected(md5($re[1].$re[2].date('Y-m d H is')),
			"Greylisting",$re[1],$re[2],$buffer);
	return null;
}

if(preg_match('#milter-greylist:.+?addr.+?\[(.+?)\] from <> to <(.+?)> delayed#',$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_rejected(md5($re[1].$re[2].date('Y-m d H is')),"Greylisting","unknown",$re[2],$buffer);
	return null;
}

if(preg_match('#milter-greylist: \(unknown id\): addr.+?\[(.+?)\] from\s+=(.+?)> to <(.+?)>\s+delayed#',$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_rejected(md5($re[1].$re[2].time()),"Greylisting",$re[2],$re[3],$buffer,$re[1]);
	return null;
}

if(preg_match("#assp.+?<(.+?)>\s+to:\s+(.+?)\s+recipient delayed#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_rejected(md5($re[1].$re[2].date('Y-m d H is')),"Greylisting",$re[1],$re[2],$buffer);
	return null;
}

if(preg_match("#assp.+?MessageScoring.+?<(.+?)>\s+to:\s+(.+?)\s+\[spam found\]#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_rejected(md5($re[1].$re[2].date('Y-m d H is')),"SPAM",$re[1],$re[2],$buffer);
	return null;
}
if(preg_match("#assp.+?MalformedAddress.+?<(.+?)>\s+to:\s+(.+?)\s+malformed address:'\|(.+?)'#",$buffer,$re)){
	eventsRTM("malformed address: $buffer");
	$GLOBALS["maillog_tools"]->event_message_rejected(md5($re[1].$re[2].date('Y-m d H is')),"malformed address (ASSP)",$re[1],$re[2],$buffer);
	return null;
}

if(preg_match("#assp.+?\[Extreme\]\s+(.+?)\s+<(.+?)>\s+to:\s+(.+?)\s+\[spam found\]#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_rejected(md5($re[1].$re[2].date('Y-m d H is')),"SPAM",$re[2],$re[3],$buffer,$re[1]);
	return null;	
}


if(preg_match("#assp.+?<(.*?)>\s+to:\s+(.+?)\s+bounce delayed#",$buffer,$re)){
	if($re[1]==null){$re[1]="Unknown";}
	$GLOBALS["maillog_tools"]->event_message_rejected(md5($re[1].$re[2].date('Y-m d H is')),"bounce delayed",$re[1],$re[2],$buffer);
}

if(preg_match("#assp.+?\[DNSBL\]\s+(.+?)\s+<(.*?)>\s+to:\s+(.+?)\s+#",$buffer,$re)){
	if($re[2]==null){$re[2]="Unknown";}
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("DNSBL",$re[2],$re[3],$re[1]);
	return null;
}
if(preg_match("#assp.+?\[URIBL\]\s+(.+?)\s+<(.*?)>\s+to:\s+(.+?)\s+#",$buffer,$re)){
	if($re[2]==null){$re[2]="Unknown";}
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("URIBL",$re[2],$re[3],$re[1]);
	return null;
}


if(preg_match("#assp.+?\[SpoofedSender\]\s+(.+?)\s+<(.*?)>\s+to:\s+(.+?)\s+.+?No Spoofing Allowed#",$buffer,$re)){
	if($re[2]==null){$re[2]="Unknown";}
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("SPOOFED",$re[2],$re[3],$re[1]);
	return null;
}
if(preg_match("#assp.+?\[InvalidHELO\]\s+(.+?)\s+<(.*?)>\s+to:\s+(.+?)\s+#",$buffer,$re)){
	if($re[2]==null){$re[2]="Unknown";}
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("BAD HELO",$re[2],$re[3],$re[1]);
	return null;
}







if(preg_match("#postfix\/lmtp.+?:\s+(.+?):\s+to=<(.+?)>.+?said:\s+550-Mailbox unknown#",$buffer,$re)){
	$id=$re[1];
	$to=$re[2];
	$GLOBALS["maillog_tools"]->event_message_milter_reject($id,"Mailbox unknown",null,$re[2],$buffer);
	mailbox_unknown($buffer,$to);
	return null;
}




if(preg_match('#postfix.+?cleanup.+?:\s+(.+?):\s+milter-reject: END-OF-MESSAGE.+4.6.0 Content scanner malfunction; from=<(.+?)> to=<(.+?)> proto=SMTP#',
$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_Content_scanner_malfunction($re[1],$re[2],$re[3]);
	return null;
}
if(preg_match("#postfix.+?cleanup.+?:\s+(.+?):\s+milter-discard.+?END-OF-MESSAGE.+?DISCARD.+?from=<(.+?)> to=<(.+?)> proto=SMTP#",
$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_DISCARD($re[1],$re[2],$re[3],$buffer);
	return null;
}

if(preg_match("#cleanup\[.+?:\s+(.+?):\s+milter-discard: END-OF-MESSAGE from.+?\[(.+?)\]:\s+milter triggers DISCARD action;\s+from=<(.+?)>\s+to=<(.+?)>#",
$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_DISCARD($re[1],$re[3],$re[4],$buffer,$re[2]);
	return null;
}
	


if(preg_match("#^([A-ZA-z]+)\s+([0-9]+)\s+([0-9\:]+).+?:\s+([A-Z0-9]+):\s+from=<(.*?)>, size=([0-9]+)#",$buffer,$re)){
	error_log("NEW MAIL {$re[4]} <{$re[5]}> ({$re[6]} bytes)",0);
	$GLOBALS["maillog_tools"]->event_message_from($re[4],$re[5],$re[6]);
	return null;
}

if(preg_match("#NOQUEUE: milter-reject: RCPT from.+?: 451 4.7.1 Greylisting in action, please come back in .+?; from=<(.+?)> to=<(.+?)> proto=SMTP#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("Greylisting",$re[1],$re[2]);
	return null;
}

if(preg_match("#^([A-ZA-z]+)\s+([0-9]+)\s+([0-9\:]+).+?:\s+([A-Z0-9]+):\s+milter-reject:.+?:(.+?)\s+from=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_milter_reject($re[4],$re[5],$re[6],null,$buffer);
	return null;
}




if(preg_match("#^([A-ZA-z]+)\s+([0-9]+)\s+([0-9\:]+).+?:\s+([A-Z0-9]+):\s+to=<(.+?)>,\s+orig_to=<.+?>,\s+relay=(.+?),\s+delay=.+?,\s+delays=.+?,\s+dsn=.+?,\s+status=([a-zA-Z]+)#"
,$buffer,$re)){
	if(preg_match('#\s+status=.+?\s+\((.+?)\)#',$buffer,$ri)){
		$bounce_error=$ri[1];
	}
   error_log("Finish {$re[4]} <{$re[5]}> ({$re[7]})",0);
   $GLOBALS["maillog_tools"]->event_finish($re[4],$re[5],$re[7],$bounce_error,null,$buffer);   
   return null;
	
}
if(preg_match("#^([A-ZA-z]+)\s+([0-9]+)\s+([0-9\:]+).+?:\s+([A-Z0-9]+):\s+to=<(.+?)>,\s+relay=(.+?),\s+delay=.+?,\s+delays=.+?,\s+dsn=.+?,\s+status=([a-zA-Z]+)#"
,$buffer,$re)){
	if(preg_match('#\s+status=.+?\s+\((.+?)\)#',$buffer,$ri)){
		$bounce_error=$ri[1];
	}
   $GLOBALS["maillog_tools"]->event_finish($re[4],$re[5],$re[7],$bounce_error,null,$buffer);   
   return null;	
}

	
//-------------------------------------------------------------- ERRORS

if(preg_match('#amavisd-milter.+?could not read from amavisd socket.+?\.sock:Connection timed out#',$buffer,$re)){
	amavis_socket_error($buffer);
	return null;
}

if(preg_match('#warning: milter unix.+?amavisd-milter.sock:.+SMFIC_MAIL reply packet header: Broken pipe#',$buffer,$re)){
	amavis_error_restart($buffer);
	return null;
}
if(preg_match('#sfupdates.+?KASERROR.+?keepup2date\s+failed.+?code.+?critical error#',$buffer,$re)){
	kas_error_update($buffer);
	return null;
}


if(preg_match('#lmtp.+?:\s+(.+?): to=<(.+?)>,.+?status=deferred.+?connect to .+?\[(.+?)\].+?No such file or directory#',
$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_milter_reject($re[1],"deferred",null,$re[1]);
	cyrus_socket_error($buffer,"$re[3]");
	return null;
}

if(preg_match('#lmtp.+?:(.+?):\s+to=<(.+?)>.+?said: 550-Mailbox unknown#',$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_milter_reject($re[1],"Mailbox unknown",null,$re[2]);
	mailbox_unknown($buffer,$re[2]);
	return null;
}

events_not_filtered("Not Filtered:\"$buffer\"");	
}




function events($text){
		error_log($text);
}
		

function eventsRTM($text){
		$pid=getmypid();
		$date=date('H:i:s');
		$logFile="{$GLOBALS["ARTICALOGDIR"]}/postfix-logger.sql.debug";
		$size=filesize($logFile);
		if($size>5000000){unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$date [$pid] $text\n");
		@fclose($f);	
		}
	
function cyrus_imap_conx($service,$hostname,$ip,$user){
	$time=time();
	
	error_log("$service-connection: $hostname - > $ip",0);
	$fam=new familysite();
	if($hostname==null){$hostname=$fam->GetComputerName($ip);}
	$curdate=date("YmdH");
	$zDate=date("Y-m-d H:i:s");
	$GLOBALS["CLASS_POSTFIX_SQL"]->postfix_buildhour_connections();
	$domain=$fam->GetFamilySites($hostname);
	$zmd5=md5("$time$hostname$ip");
	$tablename="{$curdate}_hmbx";

	$sql="INSERT IGNORE INTO `$tablename` (`zmd5`,`zDate`,`mbx_service`,`hostname`,`ipaddr`,`uid`,`imap_server`,`domain`)
	VALUES('$zmd5','$zDate','$service','$hostname','$ip','$user','{$GLOBALS["MYHOSTNAME"]}','$domain')";
	$GLOBALS["CLASS_POSTFIX_SQL"]->QUERY_SQL($sql);
}


function CyrusSocketErrot(){
	
	
}

function _MonthToInteger($month){
  $zText=$month;	
  $zText=str_replace('JAN', '01',$zText);
  $zText=str_replace('FEB', '02',$zText);
  $zText=str_replace('MAR', '03',$zText);
  $zText=str_replace('APR', '04',$zText);
  $zText=str_replace('MAY', '05',$zText);
  $zText=str_replace('JUN', '06',$zText);
  $zText=str_replace('JUL', '07',$zText);
  $zText=str_replace('AUG', '08',$zText);
  $zText=str_replace('SEP', '09',$zText);
  $zText=str_replace('OCT', '10',$zText);
  $zText=str_replace('NOV', '11',$zText);
  $zText=str_replace('DEC', '12',$zText);
  return $zText;	
}
function email_events($subject,$text,$context){
	$GLOBALS["CLASS_UNIX"]->send_email_events($subject,$text,$context);
	}
	
function interface_events($product,$line){
	$ini=new Bs_IniHandler();
	if(is_file("/usr/share/artica-postfix/ressources/logs/interface.events")){
		$ini->loadFile("/usr/share/artica-postfix/ressources/logs/interface.events");
	}
	$ini->set($product,'error',$line);
	$ini->saveFile("/usr/share/artica-postfix/ressources/logs/interface.events");
	@chmod("/usr/share/artica-postfix/ressources/logs/interface.events",0755);
	
}



function amavis_socket_error($line){
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
	error_log("AMAVIS SOCKET ERROR ! ($line)",0);
	$ftime=file_time_min($file);
	if($ftime<15){
		error_log("Unable to process new operation for amavis...waiting 15mn (current {$ftime}mn)",0);
		return null;
	}

	$unix=new unix();
	$stat=$unix->find_program("stat");
	exec("$stat /var/spool/postfix/var/run/amavisd-new/amavisd-new.sock 2>&1",$STATr);
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/amavis restart-milter");
		squid_admin_mysql(0,"{warning} Amavis socket is not available",$line." (Postfix claim that amavis socket is not available, 
	Artica will restart amavis \"milter\" service)
	Here it is the stat results:
	------------------------------------------
	file requested :/var/spool/postfix/var/run/amavisd-new/amavisd-new.sock
	".@implode("\n",$STATr)
	,"postfix");
	}
	@unlink($file);
	@mkdir("/etc/artica-postfix/cron.1");
	@unlink($file);@file_put_contents($file,"#");	
}

function mailbox_unknown($line,$to){
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.'.'.md5($to);
	if(file_time_min($file)<15){return null;}
	squid_admin_mysql(0,"{warning} unknown mailbox $to","Postfix claim: $to mailbox is not available you should create an alias or mailbox $line","mailbox",0);
	@unlink($file);
	@unlink($file);@file_put_contents($file,"#");	
	
}



 
function amavis_error_restart($buffer){
	error_log("amavis_error_restart:: $buffer",0);
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
	if(file_time_min($file)<15){
		error_log("amavis_error_restart:: wait 15mn",0);
		return null;
	}	
	email_events('Warning Amavis error',"Amavis claim that $buffer, Artica will restart amavis",'postfix');
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/amavis restart");
	}
	@unlink($file);
	file_put_contents($file,"#");	
	}
	
	function clamav_error_restart($buffer){
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
	if(file_time_min($file)<15){return null;}	
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
		email_events('Warning Clamad error',"Postfix claim that $buffer, Artica will restart clamav",'postfix');
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/artica-postfix restart clamd");
	}
	@unlink($file);
	file_put_contents($file,"#");	
	}	
	
function kas_error_update($buffer){
	error_log("kas_error_update:: $buffer",0);
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
	if(file_time_min($file)<15){return null;}	
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==1){return;}
	email_events('Kaspersky Anti-spam report failure when updating it`s database',"for your information: $buffer",'postfix');
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/artica-postfix restart kas3");
	@unlink($file);
	file_put_contents($file,"#");	
	}

function cyrus_generic_error($buffer,$subject){
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
	if(file_time_min($file)<15){return null;}	
	error_log("Cyrus error !! $buffer (cache=$file)",0);
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	squid_admin_mysql(0,"cyrus-imapd error: $subject","$buffer, Artica will restart cyrus",'mailbox');
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/cyrus-imapd restart");
	@unlink($file);
	file_put_contents($file,"#");
	
}

function cyrus_socket_error($buffer,$socket){
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
	if(file_time_min($file)<15){return null;}	
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	squid_admin_mysql(0,"cyrus-imapd socket error: $socket","Postfix claim \"$buffer\", Artica will restart cyrus",'mailbox');
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/etc/init.d/cyrus-imapd restart');
	@unlink($file);
	@unlink($file);@file_put_contents($file,"#");
}






function SpamAssassin_error_saupdate($buffer){
$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$timeFile=file_time_min($file);
	if($timeFile<15){
		error_log("*** $buffer ****",0);
		error_log("Spamassassin no operations, blocked by timefile $timeFile Mn!!!",0);
		return null;}	
	error_log("Spamassassin error time:$timeFile Mn!!!",0);
	squid_admin_mysql(0,"SpamAssassin error Regex","SpamAssassin claim \"$buffer\", Artica will run /usr/bin/sa-update to fix it",'postfix');
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-update --spamassassin --force");
	@unlink($file);
	@unlink($file);@file_put_contents($file,"#");	
	if(!is_file($file)){
		error_log("error writing time file:$file",0);
	}	
}

function miltergreylist_error($buffer,$socket){
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
	if(file_time_min($file)<15){return null;}	
	squid_admin_mysql(0,"Milter Greylist error: $socket","System claim \"$buffer\", Artica will restart milter-greylist",'postfix');
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/etc/init.d/milter-greylist restart');
	@unlink($file);
	@unlink($file);@file_put_contents($file,"#");
}



function MilterClamavError($buffer,$socket){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
	if(file_time_min($file)<15){return null;}	
	squid_admin_mysql(0,"Milter-clamav socket error: $socket","Postfix claim \"$buffer\", 
	Artica will grant postfix to this socket\but you can use amavis instead that will handle clamav antivirus scanner too",'postfix');
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/bin/chmod -R 775 ". dirname($socket));
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/bin/chown -R postfix:postfix ". dirname($socket));
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("postqueue -f");
	@unlink($file);
	@unlink($file);@file_put_contents($file,"#");	
	
}

function ImBlackListed($server,$buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.".".md5($server);
	if(file_time_min($file)<15){return null;}	
	squid_admin_mysql(0,"Your are blacklisted from $server","Postfix claim \"$buffer\", try to investigate why or contact our technical support",'postfix');
	@unlink($file);
	@unlink($file);@file_put_contents($file,"#");		
}


function postfix_compile_db($hash_file,$buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$unix=new unix();
	error_log("DB Problem -> $hash_file",0);
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.".".md5($hash_file);
	if(file_time_min($file)<5){return null;}
	
	if(!is_file($hash_file)){
		@file_put_contents($hash_file,"#");
	}
	$cmd=$unix->find_program("postmap"). " hash:$hash_file 2>&1";
	exec($cmd,$results);
	squid_admin_mysql(0,"Postfix Database problem","Postfix claim \"$buffer\", Artica has recompiled ".basename($hash_file)."\n".@implode("\n",$results),'postfix');
	error_log("DB Problem -> $hash_file -> $cmd",0);
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($unix->find_program("postfix"). " reload");		
	@unlink($file);
	@unlink($file);@file_put_contents($file,"#");		
	
}

function postfix_compile_missing_db($hash_file,$buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$unix=new unix();
	error_log("DB Problem -> $hash_file",0);
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.".".md5($hash_file);
	if(file_time_min($file)<5){return null;}
	
	if(!is_file($hash_file)){
		@file_put_contents($hash_file,"#");
	}
	
	squid_admin_mysql(0,"Postfix Database problem","Postfix claim \"$buffer\", Artica will create blanck file and recompile ".basename($hash_file),'postfix');
	$cmd=$unix->find_program("postmap"). " hash:$hash_file";
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);
	error_log("DB Problem -> $hash_file -> $cmd",0);
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($unix->find_program("postfix"). " reload");		
	@unlink($file);
	@unlink($file);@file_put_contents($file,"#");		
	
}

function cyrus_bad_login($router,$ip,$user,$error){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.".".md5("$router,$ip,$user,$error");
	if(file_time_min($file)<15){return null;}	
	@unlink($file);
	squid_admin_mysql(0,"User $user cannot login to mailbox","cyrus claim \"$error\" for $user (router:$router, ip:$ip),
	 please,send the right password to $user",'mailbox');
	@unlink($file);@file_put_contents($file,"#");		
}

function smtp_sasl_failed($router,$ip,$buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.".".md5("$router,$ip");
	error_log("SMTP authentication failed from $router ($ip)",0); 
	if(file_time_min($file)<15){return null;}
	@unlink($file);
	squid_admin_mysql(0,"SMTP authentication failed from $router","Postfix claim \"$buffer\" for ip address $ip",'postfix');
	@unlink($file);@file_put_contents($file,"#");		
}



function hackPOP($ip,$logon,$buffer){
	if($GLOBALS["DisableMailBoxesHack"]==1){return;}
	if($GLOBALS["PopHackEnabled"]==0){return;}
	if($GLOBALS["GlobalIptablesEnabled"]<>1){return;}
	$file="/etc/artica-postfix/croned.1/postfix.hackPop3.error";
	if($ip=="127.0.0.1"){return;}
	$GLOBALS["POP_HACK"][$ip]=intval($GLOBALS["POP_HACK"][$ip])+1;
	$count=intval($GLOBALS["POP_HACK"][$ip]);
	error_log("POP HACK {$ip} email={$logon} $count/{$GLOBALS["PopHackCount"]} failed",0);

	if(file_time_min($file)>10){
			squid_admin_mysql(0,"POPHACK {$ip}/{$logon} $count/{$GLOBALS["PopHackCount"]} failed",
			"Mailbox server claim $buffer\nAfter ( $count/{$GLOBALS["PopHackCount"]}) {$GLOBALS["PopHackCount"]} times failed, 
			a firewall rule will added","mailbox");
			@unlink($file);
		}else{
			error_log("User not found for mailbox {$ip}/{$logon} $count/{$GLOBALS["PopHackCount"]} failed",0);
		}	
	
	if($count>=$GLOBALS["PopHackCount"]){
		shell_exec_maillog("iptables -I INPUT -s {$ip} -j DROP");
		error_log("POP HACK RULE CREATED {$ip} $count/{$GLOBALS["PopHackCount"]} failed",0);
		squid_admin_mysql(0,"HACK pop3 from {$ip}","A firewall rule has been created and this IP:{$ip} is now denied ","mailbox",0);
		unset($GLOBALS["POP_HACK"][$ip]);
	}
	file_put_contents($file,"#");	
}


function zarafa_store_error($buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.".store.error";
	if(file_time_min($file)<3600){return null;}
	@unlink($file);
	$cmd=LOCATE_PHP5_BIN()."/usr/share/artica-postfix/exec.zarafa.build.stores.php";
	error_log("$cmd",0);
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);
	squid_admin_mysql(0,"Zarafa mailbox server store error","Zarafa claim \"$buffer\" Artica will try to reactivate stores and accounts",'mailbox');
	@unlink($file);@file_put_contents($file,"#");	
}

function postfix_nosuch_fileor_directory($service,$targetedfile,$buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.md5($targetedfile).".postfix.file";
	if(file_time_min($file)<15){return null;}	
	@unlink($file);
	
	$targetedfile=trim($targetedfile);
	if($targetedfile==null){return;}
	if(preg_match("#(.+?)\.db$#",$targetedfile,$re)){
		$unix=new unix();
		$postmap=$unix->find_program("postmap");
		$cmd="/bin/touch {$re[1]}";
		events(__FUNCTION__. " <$cmd>");
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);
		$cmd="$postmap hash:{$re[1]}";
		events(__FUNCTION__. " <$cmd>");
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);
		squid_admin_mysql(0,"missing database ". basename($targetedfile),"Service postfix/$service claim \"$buffer\" Artica will create a blank $targetedfile",'smtp');
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("postfix reload");
		@unlink($file);@file_put_contents($file,"#");	
		return;		
	 }
	

	
	$cmd="/bin/touch $targetedfile";
	error_log("$cmd",0);
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("postfix reload");
	squid_admin_mysql(0,"missing ". basename($targetedfile),"Service postfix/$service claim \"$buffer\" Artica will create a blank $targetedfile",'smtp');
	@unlink($file);@file_put_contents($file,"#");		
}
function postfix_baddb($service,$targetedfile,$buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.md5($targetedfile).".postfix.file";
	if(file_time_min($file)<15){return null;}	
	@unlink($file);
	$targetedfile=trim($targetedfile);
	if($targetedfile==null){return;}	
	$unix=new unix();
	$postmap=$unix->find_program("postmap");
	$cmd="$postmap hash:$targetedfile";
	events(__FUNCTION__. " <$cmd>");
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);
	squid_admin_mysql(0,"corrupted database ". basename($file),"Service postfix/$service claim \"$buffer\" Artica will rebuild $targetedfile.db",'smtp');
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("postfix reload");
	@unlink($file);@file_put_contents($file,"#");	
	return;			
}

function multi_instances_reconfigure($buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.".postfix.file";
	if(file_time_min($file)<15){return null;}	
	@unlink($file);
	$cmd="{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix-multi.php";
	events(__FUNCTION__. " <$cmd>");
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);	
	squid_admin_mysql(0,"multi-instances not correctly set","Service postfix claim \"$buffer\" Artica will rebuild multi-instances settings",'smtp');
	@unlink($file);@file_put_contents($file,"#");	
	return;		
}

function postfix_bind_error($ip,$port,$buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.md5("$ip:$port");
	if(file_time_min($file)<15){
		error_log("Postfix bind error, time-out",0);
		return null;
	}	
	@unlink($file);
	$cmd="{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix-multi.php --restart-all";
	events(__FUNCTION__. " <$cmd>");
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);	
	squid_admin_mysql(0,"Unable to bind $ip:$port","Service postfix claim \"$buffer\" Artica will restart all daemons to fix it",'smtp');
	@unlink($file);@file_put_contents($file,"#");	
	return;	
}



function mailbox_corrupted($buffer,$mail){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.md5($mail);
	if(file_time_min($file)<15){
		error_log("mailbox_corrupted <$mail>, time-out",0);
		return null;
	}	
	@unlink($file);
	squid_admin_mysql(0,"Corrupted mailbox $mail","Service postfix claim \"$buffer\" try to repair the mailbox or to use the command line
	turned out to be corrupted quota files:
	find ~cyrus -type f | grep quota\nremove the quota files for the affected mailbox(es)\nrun
	reconstruct -r -f user/mailboxoftheuser\n\n
	if you cannot perform this operation, you can open a ticket on artica technology company http://www.artica-technology.com' ",'mailbox');
	@unlink($file);@file_put_contents($file,"#");	
	return;		
}

function mailbox_overquota($buffer,$mail){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.md5($mail);
	if(file_time_min($file)<15){
		error_log("mailbox_overquota <$mail>, time-out",0);
		return null;
	}	
	@unlink($file);
	squid_admin_mysql(0,"mailbox $mail Over Quota","Service postfix claim \"$buffer\" try to increase quota for $mail' ",'mailbox');
	@unlink($file);@file_put_contents($file,"#");	
	return;		
}

function zarafa_rebuild_db($table,$buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
	if(file_time_min($file)<15){
		error_log("Zarafa missing table <$table>, time-out",0);
		return null;
	}	
	@unlink($file);
	squid_admin_mysql(0,"Zarafa missing Mysql table $table","Service Zarafa claim \"$buffer\" artica will destroy the zarafa database in order to let the Zarafa service create a new one' ",'mailbox');
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.mysql.build.php --rebuild-zarafa");
	@unlink($file);@file_put_contents($file,"#");	
	return;		
	
}


function events_not_filtered($text){
	error_log("Not filtered: $text",0);
	
}


function amavis_sa_update($buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php /usr/share/artica-postfix/exec.spamassassin.php --sa-update >/dev/null 2>&1 &";
	error_log("$cmd amavis_sa_update()",0);
	$file="/etc/artica-postfix/pids/".__FUNCTION__.".error.time";
	if(file_time_min($file)<15){error_log("-> detected $buffer, need to wait 15mn",0);return null;}	
	@unlink($file);
	@unlink($file);@file_put_contents($file,"#");	
	shell_exec_maillog(trim($cmd));
	error_log("$cmd",0);
	return;			
	
}

function shell_exec_maillog($cmd){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==1){
		error_log("`$cmd` will not be executed ActAsSMTPGatewayStatistics is enabled" );
		return;
	}
//SP139
	$timeExec="/etc/artica-postfix/pids/shell_exec_maillog.".md5($cmd).".time";
	
	$time=$GLOBALS["CLASS_UNIX"]->file_time_sec($timeExec);
	if($time<10){
		error_log("EXEC: cannot execute `$cmd` before 10s of interval" );
		return;
	}
	
	shell_exec($cmd);
	error_log("EXEC:`$cmd`" );
	@unlink($timeExec);
	@file_put_contents($timeExec, time());
}

 
?>
