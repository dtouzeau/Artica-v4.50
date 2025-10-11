<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="PDNS server";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');
include_once(dirname(__FILE__) . "/ressources/class.mysql.powerdns.inc");



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;$GLOBALS["RECONFIGURE"]=true;exit();}
if($argv[1]=="--restart-all"){$GLOBALS["OUTPUT"]=true;restart_all();exit();}



function pdns_recursor_pid(){
	$unix=new unix();
	$pid=trim(@file_get_contents("/var/run/pdns/pdns_recursor.pid"));
	if($unix->process_exists($pid)){return $pid;}
	$recursorbin=$unix->find_program("pdns_recursor");
	return $unix->PIDOF($recursorbin);

}


function reload(){
    system("/usr/sbin/artica-phpfpm-service -reload-pdns");
}

function build_progress($pourc,$text){
	$cachefile=PROGRESS_DIR."/pdns.restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "{$pourc}% $text\n";
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		build_progress(110, "{restart_later}");
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	build_progress(25, "{stopping_service}");
	if(!stop(true)){
		build_progress(110, "{stopping_service} {failed}");
		return;
	}
	$php5=$unix->LOCATE_PHP5_BIN();
    build_progress(55, "{reconfigure}");
	shell_exec("$php5 /usr/share/artica-postfix/exec.pdns.php --mysql");
	sleep(1);
	build_progress(80, "{starting_service}");
	if(!start(true)){
		build_progress(110, "{starting_service} {failed}");
		return;
		
	}
	build_progress(100, "{starting_service} {success}");
	
}
function restart_all(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		build_progress(110, "{restart_later}");
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	build_progress(25, "{stopping_service}");
	if(!stop(true)){
		build_progress(110, "{stopping_service} {failed}");
		return;
	}
	$php5=$unix->LOCATE_PHP5_BIN();
	build_progress(55, "{reconfigure}");
	shell_exec("$php5 /usr/share/artica-postfix/exec.pdns.php --mysql");
	sleep(1);
	build_progress(80, "{starting_service}");
	if(!start(true)){
		build_progress(110, "{starting_service} {failed}");
		return;
	
	}
	$PowerDNSEnableRecursor=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableRecursor"));
	if($PowerDNSEnableRecursor==0){
		build_progress(100, "{starting_service} {success}");
		return;
	}
	build_progress(90, "{restarting_service} {APP_PDNS_RECURSOR}");
	system("$php5 /usr/share/artica-postfix/exec.pdns_recursor.php --restart");
	$pid=pdns_recursor_pid();
	if(!$unix->process_exists($pid)){
		build_progress(110, "{restarting_service} {APP_PDNS_RECURSOR} {failed}");
		return;
	}
	build_progress(100, "{starting_service} {success}");
}
function start($aspid=false){
    system("/usr/sbin/artica-phpfpm-service -start-pdns");
    return true;
}
function stop($aspid=false){
	system("/usr/sbin/artica-phpfpm-service -stop-pdns");
	return true;
}
function tldextract(){
    $pythonroot         = "/usr/lib/python2.7/site-packages";
    $srcfile            = ARTICA_ROOT."/bin/install/tldextract.tar.gz";
    $textfile           = "$pythonroot/tldextract/tldextract.py";
    $unix               = new unix();
    $tldpackage         = "$pythonroot/tld/__init__.py";
    $srctld             = ARTICA_ROOT."/bin/install/tld-0.12.5-py27-none-any.whl";
    $pip                = $unix->find_program("pip");

    if(is_dir("$pythonroot/tldextract-0.1-py2.7.egg")){
        $rm=$unix->find_program("rm");
        shell_exec("$rm -rf $pythonroot/tldextract-0.1-py2.7.egg");
    }

    if(!is_file($tldpackage)){
        if(is_file($srctld)) {
            shell_exec("$pip install $srctld");
        }
    }




    if(is_file($textfile)){return true;}
    if(!is_file($srcfile)){return false;}


    $tar = $unix->find_program("tar");
    shell_exec("$tar xf $srcfile -C /");
    if(is_file($textfile)){
        squid_admin_mysql(2,"Success {installing} tldextract python package",null,__FILE__,__LINE__);
        return true;
    }
    squid_admin_mysql(2,"Failed {installing} tldextract python package",null,__FILE__,__LINE__);
    return false;


}


function PowerDNSListenAddr(){
	$unix=new unix();
	
	#https://gist.github.com/sokratisg/10069682
	if(!is_file("/etc/artica-postfix/settings/Daemons/PowerDNSListenAddr")){return "0.0.0.0";}
	$t=array();
	$ipA=explode("\n", $GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSListenAddr"));
	while (list ($line2,$ip) = each ($ipA) ){
		if(trim($ip)==null){continue;}
		if(!$unix->isIPAddress($ip)){continue;}
		if(!$unix->IS_IPADDR_EXISTS($ip)){continue;}
		$t[$ip]=$ip;
	}
	
	if(count($t)==0){return "0.0.0.0";}
	
	$LOCAL_ADDRESSES=array();
	while (list ($a,$b) = each ($t) ){
		
		$LOCAL_ADDRESSES[]=$a;
		$cdirlist[]=$a;
	}
	
	return @implode(",", $LOCAL_ADDRESSES);
	
}





