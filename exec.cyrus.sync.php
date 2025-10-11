<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
echo "Starting....".__LINE__."\n";

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}

$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}
echo "Starting....".__LINE__."\n";

include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
echo "Starting....".__LINE__."\n";

Start_sync();

function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/cyrus.sync.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}


function Start_sync(){
	build_progress("{restarting}: Saslauthd",20);
	system('/etc/init.d/saslauthd restart');
	sleep(2);
	
	build_progress("{restarting}: Cyrus-Imap",50);
	system('/etc/init.d/cyrus-imapd restart');
	sleep(2);
	
	build_progress("{restarting}: Postfix",80);
	system('/etc/init.d/postfix restart');
	sleep(2);
	
	build_progress("{listing_mailboxes}",90);
	$cyrus=new cyrus();
	$mbx=$cyrus->ListMailboxes($cn);
	while (list ($num, $box) = each ($mbx) ){
		echo "Found Mailbox \"$num\"\n";
		
	}
	sleep(10);
	build_progress("{done}",100);
}
?>

