<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",@implode(" ", $argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.milter.greylist.inc');


include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.fetchmail.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");


if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--mysql-defaults"){mysql_defaults();build();exit;}
if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--restart"){restart();exit;}

function build(){
	$q=new postgres_sql();
	$f[]="# /etc/milter-regex.conf ".date("Y-m-d H:i:s");
	$f[]="";
	$f[]="accept";
	$f[]="connect // /127.0.0.1/";
	$f[]="";
	$f[]="# whitelist some criteria first";
	$f[]="accept";
	$f[]="helo /whitelist/";
	$f[]="helo /WORLD/";
	
	$sql="SELECT * FROM miltergreylist_acls WHERE method='whitelist' AND type='from'";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = pg_fetch_assoc($results)) {
		$description=$ligne["description"];
		$pattern=$ligne["pattern"];
		$f[]="accept";
		$pattern=str_replace(".", "\.", $pattern);
		$pattern=str_replace("*", ".*?", $pattern);
		$f[]="envfrom /$pattern/i";
	}
	$sql="SELECT * FROM miltergreylist_acls WHERE method='whitelist' AND type='domain'";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = pg_fetch_assoc($results)) {
		$description=$ligne["description"];
		$pattern=$ligne["pattern"];
		$f[]="accept";
		$pattern=str_replace(".", "\.", $pattern);
		$pattern=str_replace("*", ".*?", $pattern);
		$f[]="helo /$pattern/i";
		$f[]="connect /$pattern/i";
	}	
	
	
	
	
	$sql="SELECT * FROM miltergreylist_acls WHERE method='whitelist' AND type='envfrom'";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = pg_fetch_assoc($results)) {
		$description=$ligne["description"];
		$pattern=$ligne["pattern"];
		$f[]="accept";
		$f[]="envfrom /$pattern/i";
	}

    $sql="SELECT * FROM miltergreylist_acls WHERE method='whitelist' AND type='rcpt'";
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        $pattern=$ligne["pattern"];
        $pattern=str_replace("'", "", $pattern);
        $pattern=str_replace(".", "\.", $pattern);
        $pattern=str_replace("*", ".*?", $pattern);
        $f[]="accept";
        $f[]="envrcpt /$pattern/i";
    }
	
	
	$sql="SELECT * FROM miltergreylist_acls WHERE method='whitelist' AND type='envsubject'";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = pg_fetch_assoc($results)) {
		$description=$ligne["description"];
		$pattern=$ligne["pattern"];
		$f[]="accept";
		$f[]="header /Subject/ /$pattern/i";
	}	
	
	$sql="SELECT * FROM miltergreylist_acls WHERE method='whitelist' AND type='envbody'";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = pg_fetch_assoc($results)) {
		$description=$ligne["description"];
		$pattern=$ligne["pattern"];
		$f[]="accept";
		$f[]="body /$pattern/i";
	}	
	
	
	$f[]="";
	$f[]="# ####################### END WHITELIST ########################";
	
	
	
	
	$sql="SELECT * FROM miltergreylist_acls WHERE method='blacklist' AND type='from'";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	while ($ligne = pg_fetch_assoc($results)) {
		$description=$ligne["description"];
		$pattern=$ligne["pattern"];
		$f[]="reject \"BLK[{$ligne["id"]}] $description\"";
		$pattern=str_replace(".", "\.", $pattern);
		$pattern=str_replace("*", ".*?", $pattern);
		$f[]="envfrom /$pattern/i";
	}
	
	$sql="SELECT * FROM miltergreylist_acls WHERE method='blacklist' AND type='domain'";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = pg_fetch_assoc($results)) {
		$description=$ligne["description"];
		$pattern=$ligne["pattern"];
		$f[]="reject \"BLK[{$ligne["id"]}] $description\"";
		$pattern=str_replace(".", "\.", $pattern);
		$pattern=str_replace("*", ".*?", $pattern);
		$f[]="helo /$pattern/i";
		$f[]="connect /$pattern/i";
	}

    $sql="SELECT * FROM miltergreylist_acls WHERE method='blacklist' AND type='rcpt'";
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        $pattern=$ligne["pattern"];
        $pattern=str_replace("'", "", $pattern);
        $pattern=str_replace(".", "\.", $pattern);
        $pattern=str_replace("*", ".*?", $pattern);
        $f[]="reject \"BLK[{$ligne["id"]}] $description\"";
        $f[]="envrcpt /$pattern/i";
    }
	
	
	
	$sql="SELECT * FROM miltergreylist_acls WHERE method='blacklist' AND type='envfrom'";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = pg_fetch_assoc($results)) {
		$description=$ligne["description"];
		$pattern=$ligne["pattern"];
		$f[]="reject \"BLK[{$ligne["id"]}] $description\"";
		$f[]="envfrom /$pattern/i";
	}	
	
	$sql="SELECT * FROM miltergreylist_acls WHERE method='blacklist' AND type='envsubject'";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = pg_fetch_assoc($results)) {
		$description=$ligne["description"];
		$pattern=$ligne["pattern"];
		$f[]="reject \"BLK[{$ligne["id"]}] $description\"";
		$f[]="header /Subject/ /$pattern/i";
	}	
	$sql="SELECT * FROM miltergreylist_acls WHERE method='blacklist' AND type='envbody'";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = pg_fetch_assoc($results)) {
		$description=$ligne["description"];
		$pattern=$ligne["pattern"];
		$f[]="reject \"BLK[{$ligne["id"]}] $description\"";
		$f[]="body /$pattern/i";
	}	
	
	
	
	
	$f[]="";
	$f[]="#tempfail \"Sender IP address not resolving\"";
	$f[]="#connect /\[.*\..*\]/ //";
	$f[]="";


	
	
	
	
	//$f[]="# reject things that look like they might come from a dynamic address";
	//$f[]="reject \"Dynamic Network ID1\"";
	//$f[]="connect /[0-9][0-9]*\-[0-9][0-9]*\-[0-9][0-9]*/ //";
	//$f[]="reject \"Dynamic Network ID2\"";
	//$f[]="connect /[0-9][0-9]*\.[0-9][0-9]*\.[0-9][0-9]*/ //";
	//$f[]="reject \"Dynamic Network ID3\"";
	//$f[]="connect /[0-9]{12}/e //";
	
	$f[]="";
	$f[]="# This is rather pointless, some receivers do callback checks using <>";
	$f[]="# and refuse service if you're not accepting <> (which is RFC compliant";
	$f[]="# for bounces). And sendmail itself will enforce legitimate format for";
	$f[]="# non-empty forms (enforcing a @, checking the domain, etc.).";
	$f[]="#reject \"Malformed MAIL FROM (not an email address or <>)\"";
	$f[]="#envfrom /(<>|<.*@.*>)/en";
	$f[]="";
	$f[]="reject \"Malformed RCPT TO (not an email address, not <.*@.*>)\"";
	$f[]="envrcpt /<(.*@.*|Postmaster)>/ein";
	$f[]="";
	//$f[]="reject \"HTML mail not accepted\"";
	//$f[]="( header ,^Content-Type\$,i ,^text/html,i or body ,^Content-Type: text/html,i ) and not header ,^From\$, ,deraadt,";
	$f[]="";
	$f[]="reject \"Swen worm (caps)\"";
	$f[]="header /^(TO|FROM|SUBJECT)\$/e // and not header /^From\$/i /telus.blackberry.net/";
	$f[]="";
	$f[]="#reject \"Swen worm (boundary)\"";
	$f[]="#header /^Content-Type\$/i /boundary=\"Boundary_(ID_/i";
	$f[]="#header /^Content-Type\$/i /boundary=\"[a-z]*\"/";
	$f[]="";
	$f[]="reject \"Swen worm (body)\"";
	$f[]="body ,^Content-Type: audio/x-wav; name=\"[a-z]*\.[a-z]*\",i";
	$f[]="body ,^Content-Type: application/x-msdownload; name=\"[a-z]*\.[a-z]*\",i";
	$f[]="";
	//$f[]="reject \"Unwanted (executable) attachment type\"";
	//$f[]="header ,^Content-Type\$, ,multipart/mixed, and body ,^Content-Type: application/, and body ,name=\".*\.(pif|exe|scr|com|bat|rar)\"\$,e";
	$f[]="";
	$f[]="reject \"Opt-out 'mailing list', spam, get lost (otcjournal)\"";
	$f[]="header /^X-List-Host\$/ /otcjournal/i";
	$f[]="header /^List-Owner\$/ /smallcapnetwork/i";
	$f[]="";
	$f[]="reject \"sonicsurf.ch spam, get lost\"";
	$f[]="header /^Received\$/ /\[195\.129\.5[89]\..*\]/";
	$f[]="";
	$f[]="reject \"Eat your socks, you fscking spammer.\"";
	$f[]="body /^The New Media Publishers AG/i";
	$f[]="body /^New.*Media.*Publisher/i";
	$f[]="body /^Socks and more AG/i";
	$f[]="body /^Business Corp\. for W\.& L\. AG/i";
	$f[]="body /Horizon *Business *Corp/";
	$f[]="body /Postfach, 6062 Wilen/i";
	$f[]="body /041.*661.*17.*(18|19|20)/e";
	$f[]="body /043.*317.*02.*8[0-9]/";
	$f[]="body /0_4_1_/";
	$f[]="body /W_i_l_e_n/i";
	$f[]="body ,^Ort/Datum:.*____,";
	$f[]="";
	
	@file_put_contents("/etc/milter-regex.conf", @implode("\n", $f));
	@chown("/etc/milter-regex.conf","postfix");
	
}

function buildline($ligne){
	$pattern=$ligne["pattern"];
	if(trim($pattern)==null){
		echo "Pattern is null rule {$ligne["zmd5"]}\n";
	}
	$description=$ligne["description"];
	
	$method=$ligne["method"];
	$token[]="i";
	if($ligne["reverse"]==1){$token[]="n";}
	if($ligne["extended"]==1){$token[]="e";}
	$tokens=@implode("", $token);
	if($method=="envfrom"){return "envfrom /$pattern/$tokens";}
	if($method=="envrcpt"){return "envrcpt /$pattern/$tokens";}	
	if($method=="helo"){return "helo /$pattern/$tokens";}
	if($method=="body"){return "body /$pattern/$tokens";}
	if($method=="header"){return "header $pattern$tokens";}	
	if($method=="connect"){return "connect /$pattern/$tokens //";}
	if($method=="subject"){return "header /^Subject$/ /$pattern/$tokens";}
}

function restart(){
	$unix=new unix();
	build_progress(50,"{stopping_service}");
	system("/etc/init.d/milter-regex stop");
	build_progress(90,"{starting_service}");
	system("/etc/init.d/milter-regex start");
	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		build_progress(110,"{starting_service} {failed}");
		return;
	}
	build_progress(100,"{starting_service} {success}");
	
}

function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/milter-regex.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("milter-regex");
	return $unix->PIDOF($Masterbin);
}

function install(){
	$unix=new unix();
	build_progress("{install_feature}",10);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableMilterRegex", 1);
	install_milter_regex();
	monit_install();
	build_progress(80,"{starting_service}");
	build();
	system("/etc/init.d/milter-regex start");
	build_progress(90,"{reconfigure_mta}");
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.postfix.maincf.php --milters");
	build_progress("{install_feature} {done}",100);
	
}
function monit_install(){
	$f=array();
	$f[]="check process APP_MILTER_REGEX with pidfile /var/run/milter-regex.pid";
	$f[]="\tstart program = \"/etc/init.d/milter-regex start\"";
	$f[]="\tstop program = \"/etc/init.d/milter-regex stop\"";
	$f[]="\tif failed unixsocket /var/run/milter-regex/milter-regex.sock then restart";

	$f[]="";

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring milter-greylist...\n";}
	@file_put_contents("/etc/monit/conf.d/APP_MILTER_REGEX.monitrc", @implode("\n", $f));
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");

}



function uninstall(){
	$unix=new unix();
	build_progress("{disable_feature}",10);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableMilterRegex", 0);
	remove_service("/etc/init.d/milter-regex");
	build_progress(90,"{reconfigure_mta}");
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.postfix.maincf.php --milters");
	if(is_file("/etc/monit/conf.d/APP_MILTER_REGEX.monitrc")){
		@unlink("/etc/monit/conf.d/APP_MILTER_REGEX.monitrc");
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	}
	build_progress("{disable_feature} {done}",100);

}
function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	
	if(is_numeric($text)){
		$array["POURC"]=$text;
		$array["TEXT"]=$pourc;
	}
	
	echo "[$pourc]: $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/milter-regex.reconfigure.progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/milter-regex.reconfigure.progress",0755);
	sleep(1);

}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");

	}

	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function install_milter_regex(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();

	if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){return;}

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          milter-regex";
	$f[]="# Required-Start:    \$local_fs \$network \$remote_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$network \$remote_fs \$syslog";
	$f[]="# X-Start-Before:    mail-transport-agent";
	$f[]="# X-Stop-After:      mail-transport-agent";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Milter plugin for regular expression filtering";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="# Author: Bastian Blank <bastian.blank@credativ.de>";
	$f[]="";
	$f[]="# Do NOT \"set -e\"";
	$f[]="";
	$f[]="# PATH should only include /usr/* if it runs after the mountnfs.sh script";
	$f[]="PATH=/sbin:/usr/sbin:/bin:/usr/bin";
	$f[]="DESC=\"Milter plugin for regular expression filtering\"";
	$f[]="NAME=milter-regex";
	$f[]="DAEMON=\"/usr/sbin/milter-regex\"";
	$f[]="DAEMON_ARGS=\"\"";
	$f[]="DAEMON_USER=postfix";
	$f[]="SOCKET_GROUP=postfix";
	$f[]="VERBOSE=\"yes\"";
	$f[]="PIDFILE=/var/run/milter-regex.pid";
	$f[]="SCRIPTNAME=/etc/init.d/\$NAME";
	$f[]="DAEMON_SOCKET=\"/var/run/milter-regex/milter-regex.sock\"";
	$f[]="DAEMON_ARGS=\"-c /etc/milter-regex.conf -u postfix -p \$DAEMON_SOCKET\"";
	$f[]="";
	$f[]="# Exit if the package is not installed";
	$f[]="if [ ! -f \"\$DAEMON\" ]; then";
	$f[]="\techo \"\$DAEMON no such file\"";
	$f[]="fi";


	$f[]="if [ ! -f \"/etc/artica-postfix/settings/Daemons/EnableMilterRegex\" ]; then";
	$f[]="\techo 0 >/etc/artica-postfix/settings/Daemons/EnableMilterRegex || true";
	$f[]="fi";
	$f[]="EnableMilterRegex=`cat /etc/artica-postfix/settings/Daemons/EnableMilterRegex`";


	$f[]="";
	$f[]="# Load the VERBOSE setting and other rcS variables";
	$f[]=". /lib/init/vars.sh";
	$f[]="";
	$f[]="# Define LSB log_* functions.";
	$f[]="# Depend on lsb-base (>= 3.2-14) to ensure that this file is present";
	$f[]="# and status_of_proc is working.";
	$f[]=". /lib/lsb/init-functions";
	$f[]="";

	$f[]="if [ \$EnableMilterRegex != 1 ]; then";
	$f[]="\tlog_daemon_msg \"\$DAEMON not enabled - \$EnableMilterRegex -\"";
	$f[]="\tlog_end_msg 1";
	$f[]="\texit 0";
	$f[]="fi";

	$f[]="#";
	$f[]="# Function that starts the daemon/service";
	$f[]="#";
	$f[]="do_start(){";
	$f[]="  if pidof -o %PPID -x \"\$DAEMON\">/dev/null; then";
	$f[]="	return 2";
	$f[]="  fi";
	$f[]="	$php /usr/share/artica-postfix/exec.milter-regex.php --build || true";
	$f[]="	chmod 0755 \$DAEMON || true";
	$f[]="	mkdir -p -m 700 /var/run/milter-regex || true";
	$f[]="	chown -R \$DAEMON_USER:\$SOCKET_GROUP /var/run/milter-regex || true";
	$f[]="	chmod 2750 /var/run/milter-regex || true";
	$f[]="	rm -f /var/run/milter-regex/milter-regex.sock || true";
	$f[]="	start-stop-daemon --start --quiet --pidfile \$PIDFILE --exec \$DAEMON --user \$DAEMON_USER --test > /dev/null || return 1";
	$f[]="	start-stop-daemon --start --quiet --pidfile \$PIDFILE --exec \$DAEMON --user \$DAEMON_USER --background --make-pidfile --chuid \$DAEMON_USER -- -d \$DAEMON_ARGS || return 2";
	$f[]="  if pidof -o %PPID -x \"\$DAEMON\">/dev/null; then";
	$f[]="	return 0";
	$f[]="  fi";
	$f[]="}";
	$f[]="";
	$f[]="#";
	$f[]="# Function that stops the daemon/service";
	$f[]="#";
	$f[]="do_stop(){";
	$f[]="	# Return";
	$f[]="	#   0 if daemon has been stopped";
	$f[]="	#   1 if daemon was already stopped";
	$f[]="	#   2 if daemon could not be stopped";
	$f[]="	#   other if a failure occurred";
	$f[]="	start-stop-daemon --stop --quiet --retry=TERM/30/KILL/5 --pidfile \$PIDFILE --name \$NAME --user \$DAEMON_USER";
	$f[]="	RETVAL=\"\$?\"";
	$f[]="	[ \"\$RETVAL\" = 2 ] && return 2";
	$f[]="	start-stop-daemon --stop --quiet --oknodo --retry=0/30/KILL/5 --exec \$DAEMON --user \$DAEMON_USER";
	$f[]="	[ \"\$?\" = 2 ] && return 2";
	$f[]="	# Many daemons don't delete their pidfiles when they exit.";
	$f[]="  if pidof -o %PPID -x \"\$DAEMON\">/dev/null; then";
	$f[]="	return 2";
	$f[]="  fi";
	$f[]="	rm -f \$PIDFILE  || true";
	$f[]="	rm -f /var/run/milter-regex/milter-regex.sock || true";
	$f[]="	return \"\$RETVAL\"";
	$f[]="}";
	$f[]="";
	$f[]="case \"\$1\" in";
	$f[]="  start)";
	$f[]="	log_action_begin_msg \"Starting \$DESC\"";
	$f[]="	do_start";
	$f[]="	case \"\$?\" in";
	$f[]="		0|1) log_end_msg 0 ;;";
	$f[]="		2) log_end_msg 1 ;;";
	$f[]="	esac";
	$f[]="	;;";
	$f[]="  stop)";
	$f[]="	log_action_begin_msg \"Stopping \$DESC\"";
	$f[]="	do_stop";
	$f[]="	case \"\$?\" in";
	$f[]="		0|1) log_end_msg 0 ;;";
	$f[]="		2) log_end_msg 1 ;;";
	$f[]="	esac";
	$f[]="	;;";
	$f[]="  status)";
	$f[]="	status_of_proc \"\$DAEMON\" \"\$NAME\" && exit 0 || exit \$?";
	$f[]="	;;";
	$f[]="  restart|force-reload)";
	$f[]="	log_daemon_msg \"Restarting \$DESC\" \"\$NAME\"";
	$f[]="	do_stop";
	$f[]="	case \"\$?\" in";
	$f[]="	  0|1)";
	$f[]="	rm -f \$PIDFILE  || true";
	$f[]="	rm -f /var/run/milter-regex/milter-regex.sock || true";
	$f[]="		do_start";
	$f[]="		case \"\$?\" in";
	$f[]="			0) log_end_msg 0 ;;";
	$f[]="			1) log_end_msg 1 ;; # Old process is still running";
	$f[]="			*) log_end_msg 1 ;; # Failed to start";
	$f[]="		esac";
	$f[]="		;;";
	$f[]="	  *)";
	$f[]="		# Failed to stop";
	$f[]="		log_end_msg 1";
	$f[]="		;;";
	$f[]="	esac";
	$f[]="	;;";
	$f[]="  *)";
	$f[]="	echo \"Usage: \$SCRIPTNAME {start|stop|status|restart|force-reload}\" >&2";
	$f[]="	exit 3";
	$f[]="	;;";
	$f[]="esac";
	$f[]="";
	$f[]=":";
	$f[]="";

	@file_put_contents("/etc/init.d/milter-regex", @implode("\n", $f));
	@chmod("/etc/init.d/milter-regex",0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec('/usr/sbin/update-rc.d -f milter-regex >/dev/null 2>&1');

	}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: milter-regex daemon success...\n";}



}


function mysql_defaults(){
	$q=new mysql();
	$defaults='a:15:{i:0;a:10:{s:4:"zmd5";s:32:"255751b8841af40879ed34a929912800";s:8:"instance";s:6:"master";s:6:"method";s:7:"envfrom";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:29:"austinbrooks[0-9]*@gmail\.com";s:11:"description";s:27:"You are a spamer from gMail";s:5:"zDate";s:19:"2015-08-23 21:48:42";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:1;a:10:{s:4:"zmd5";s:32:"17d14c5d74531d6c3f6cdb3882a638a4";s:8:"instance";s:6:"master";s:6:"method";s:7:"envfrom";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:31:"jeff.wooderson[0-9]*@gmail\.com";s:11:"description";s:27:"You are a spamer from gMail";s:5:"zDate";s:19:"2015-08-23 21:49:07";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:2;a:10:{s:4:"zmd5";s:32:"dbcd58ec5fbe924ba252bd080808ece8";s:8:"instance";s:6:"master";s:6:"method";s:7:"envfrom";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:16:"@grupoziike\.com";s:11:"description";s:29:"You are a spamer from godaddy";s:5:"zDate";s:19:"2015-08-23 21:49:48";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:3;a:10:{s:4:"zmd5";s:32:"9ac5a778b805ee40273384b88ad7dfb2";s:8:"instance";s:6:"master";s:6:"method";s:4:"helo";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:12:"127\.0\.0\.1";s:11:"description";s:42:"Spoofed HELO (my own IP address, nice try)";s:5:"zDate";s:19:"2015-08-23 21:50:43";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:4;a:10:{s:4:"zmd5";s:32:"3600983c0dec8252b64bb3bab8799cda";s:8:"instance";s:6:"master";s:6:"method";s:4:"helo";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:2:"\.";s:11:"description";s:37:"Malformed HELO (not a domain, no dot)";s:5:"zDate";s:19:"2015-08-23 22:00:59";s:7:"reverse";s:1:"1";s:8:"extended";s:1:"0";}i:5;a:10:{s:4:"zmd5";s:32:"d5cd851c1395801890940b3f87fe39d9";s:8:"instance";s:6:"master";s:6:"method";s:7:"envrcpt";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:20:"<(.*@.*|Postmaster)>";s:11:"description";s:53:"Malformed RCPT TO (not an email address, not <.*@.*>)";s:5:"zDate";s:19:"2015-08-23 22:04:40";s:7:"reverse";s:1:"1";s:8:"extended";s:1:"1";}i:6;a:10:{s:4:"zmd5";s:32:"dc982138e45ec1ef483ed667dc79f791";s:8:"instance";s:6:"master";s:6:"method";s:6:"header";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:30:"/^From\$/i /link-builder\.com/";s:11:"description";s:7:"Spammer";s:5:"zDate";s:19:"2015-08-23 22:08:07";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:7;a:10:{s:4:"zmd5";s:32:"f0759c221e95412988e06fe174c19105";s:8:"instance";s:6:"master";s:6:"method";s:7:"subject";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:19:"Expecting your mail";s:11:"description";s:8:"Go away!";s:5:"zDate";s:19:"2015-08-23 22:12:52";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:8;a:10:{s:4:"zmd5";s:32:"e7ec449e6c80e8388aa8b97c0139cc72";s:8:"instance";s:6:"master";s:6:"method";s:7:"subject";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:25:"Notice to appear in Court";s:11:"description";s:8:"Go away!";s:5:"zDate";s:19:"2015-08-23 22:13:20";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:9;a:10:{s:4:"zmd5";s:32:"e98626e0a6af45bb7640f6cd3fdd17d9";s:8:"instance";s:6:"master";s:6:"method";s:7:"subject";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:16:"You miss this \?";s:11:"description";s:8:"Go away!";s:5:"zDate";s:19:"2015-08-23 22:13:54";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:10;a:10:{s:4:"zmd5";s:32:"a7ea66a132d85b3bc150e471579b282f";s:8:"instance";s:6:"master";s:6:"method";s:7:"subject";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:31:"Say "Bye-Bye" to Adwords Budget";s:11:"description";s:8:"Go away!";s:5:"zDate";s:19:"2015-08-23 22:14:26";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:11;a:10:{s:4:"zmd5";s:32:"5ac8f1747798fa7ffbc74d0ebae0b524";s:8:"instance";s:6:"master";s:6:"method";s:7:"subject";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:32:"Website review and analysis for:";s:11:"description";s:8:"Go away!";s:5:"zDate";s:19:"2015-08-23 22:14:58";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:12;a:10:{s:4:"zmd5";s:32:"29ba904af3da72c4e76ebeb761fb136c";s:8:"instance";s:6:"master";s:6:"method";s:7:"subject";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:33:"^READ THIS IMPORTANT INFORMATION$";s:11:"description";s:36:"Please Get more info in your subject";s:5:"zDate";s:19:"2015-08-23 22:47:48";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:13;a:10:{s:4:"zmd5";s:32:"4877944166423fb711c1a6aa162b4414";s:8:"instance";s:6:"master";s:6:"method";s:4:"body";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:21:"emailtrack[0-9]*\.com";s:11:"description";s:20:"emailtrack is banned";s:5:"zDate";s:19:"2015-08-23 23:00:44";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:14;a:10:{s:4:"zmd5";s:32:"bc54c17e223173894a5bc4d54a569e25";s:8:"instance";s:6:"master";s:6:"method";s:4:"body";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:21:":\/\/go\.madmimi\.com";s:11:"description";s:24:"No spam from madmimi.com";s:5:"zDate";s:19:"2015-08-23 23:09:42";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}}';
	$MAIN=unserialize($defaults);
	while (list ($num, $ligne) = each ($MAIN) ){
		
		foreach ($ligne as $a=>$b){
			$ligne[$a]=mysql_escape_string2($b);
		}
		
		$description=$ligne["description"];
		$pattern=$ligne["pattern"];
		$method=$ligne["method"];
		$zmd5=$ligne["zmd5"];
		$instance=$ligne["instance"];
		$method=$ligne["method"];
		$type=$ligne["type"];
		$enabled=$ligne["enabled"];
		$reverse=$ligne["reverse"];
		$extended=$ligne["extended"];
		$zDate=$ligne["zDate"];
		
		$sql="INSERT INTO `milterregex_acls`
		(`zmd5`,`zDate`,`instance`,`method`,`type`,`pattern`,`description`,`enabled`,`reverse`,`extended`) VALUES
		('$zmd5','$zDate','$instance','$method','$type','$pattern','$description',$enabled,$reverse,$extended);";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){return;}
	}
	
	
}
