<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

$GLOBALS["AS_ROOT"]=true;
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["BY_SYSLOG"]=false;
$GLOBALS["ALL"]=false;
$GLOBALS["SCHEDULE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["OUTPUT"]=true;$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--syslog#",implode(" ",$argv),$re)){$GLOBALS["BY_SYSLOG"]=true;}
if(preg_match("#--all#",implode(" ",$argv),$re)){$GLOBALS["ALL"]=true;}
if(preg_match("#--schedule#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE"]=true;}

if($GLOBALS["VERBOSE"]){echo "Loading includes...\n";}


include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');

if($argv[1]=="--disconnect"){xdisconnect();exit;}

xconnect();


function build_progress($text,$pourc){
	if($GLOBALS["VERBOSE"]){echo "{$pourc}% $text\n";}

	echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service {$pourc}% $text\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/c-icap.connect.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function xdisconnect(){
	$unix=new unix();
	build_progress("{disconnecting}",20);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSecurityAppliance",0);
	
	build_progress("{disable_icap_services}...",40);
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$q->QUERY_SQL("UPDATE c_icap_services SET enabled=0 WHERE ID=4");
	$q->QUERY_SQL("UPDATE c_icap_services SET enabled=0 WHERE ID=3");
	
	build_progress("{reconfiguring_proxy_service}...",50);
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	build_progress("{reconfiguring_proxy_service}...{done}",100);
}

function xconnect(){
	$unix=new unix();
	
	$SecurityApplianceIPaddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SecurityApplianceIPaddr");
	$cicapclient=$unix->find_program("c-icap-client");
	
	
	if(!is_file($cicapclient)){
		build_progress("c-icap-client no such binary",110);
		return;
		
	}
	
	build_progress("{testing_connection}",20);
	$filetemp=$unix->FILE_TEMP();
	if(is_file($filetemp)){@unlink($filetemp);}
	$cmd="$cicapclient -s \"info?view=text\" -i $SecurityApplianceIPaddr -p 1345  -req http://articatech.com -o $filetemp";
	echo $cmd."\n";
	
	system($cmd);
	if(!is_file($filetemp)){
		build_progress("{testing_connection} {failed}",110);
		return;
	}
	
	$verif=false;
	$f=explode("\n",@file_get_contents($filetemp));
	while (list ( $index,$line) = each ($f)){
		echo "$line\n";
		if(preg_match("#Children number:\s+([0-9]+)#i", $line)){$verif=true;break;}
		
	}
	if(!$verif){
		build_progress("{verify_connection} {failed}",110);
		return;
	}
	build_progress("{connecting}...",30);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CicapEnabled", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableeCapClamav", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSecurityAppliance",1);
	
	build_progress("{enable_icap_services}...",40);
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	
	$q->QUERY_SQL("UPDATE c_icap_services SET ipaddr='$SecurityApplianceIPaddr',enabled=1 WHERE ID=4");
	$q->QUERY_SQL("UPDATE c_icap_services SET ipaddr='$SecurityApplianceIPaddr',enabled=1 WHERE ID=3");
	
	$q->QUERY_SQL("UPDATE c_icap_services SET enabled=0 WHERE ID=1");
	$q->QUERY_SQL("UPDATE c_icap_services SET enabled=0 WHERE ID=2");
	
	build_progress("{reconfiguring_proxy_service}...",50);
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	build_progress("{reconfiguring_proxy_service}...{done}",100);
	
	
	
}