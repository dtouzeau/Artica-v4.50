<?php
$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
if($EnableIntelCeleron==1){die("EnableIntelCeleron==1\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FLUSH"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--flush#",implode(" ",$argv))){$GLOBALS["FLUSH"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.dhcpd.inc');
include_once(dirname(__FILE__) . '/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(system_is_overloaded(basename(__FILE__))){squid_admin_mysql(1, "{OVERLOADED_SYSTEM}, aborting the task...", ps_report(), __FILE__, __LINE__);exit();}
if($argv[1]=='--tomysql'){scanarp_mysql();exit;}

scanarp();



function scanarp():bool{
    $GLOBALS["CLASS_USERS"]=new usersMenus();
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
    if(!$GLOBALS["CLASS_USERS"]->ARPD_INSTALLED){
        if($GLOBALS["VERBOSE"]){echo __FUNCTION__." ARPD_INSTALLED = FALSE\n";}
        return false;
    }
    $EnableArpDaemon=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArpDaemon");
    if(!is_numeric($EnableArpDaemon)){$EnableArpDaemon=1;}
    if($EnableArpDaemon==0){
        if($GLOBALS["VERBOSE"]){echo __FUNCTION__." EnableArpDaemon = $EnableArpDaemon\n";}
        return false;
    }
    if(!is_file("/var/lib/arpd/arpd.db")){
        return false;
    }

    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
    $unix=new unix();
    $me=basename(__FILE__);
    $pid=$unix->get_pid_from_file($pidfile);

    if($unix->process_exists($pid,$me)){
        if($GLOBALS["VERBOSE"]){echo " $pid --> Already executed.. aborting the process\n";}
        $time=$unix->PROCCESS_TIME_MIN($pid);
        squid_admin_mysql(2, "Already executed pid $pid since {$time}Mn.. aborting the process",__FUNCTION__,__FILE__,__LINE__,"system");
        exit();
    }
    @file_put_contents($pidfile, getmypid());

    $GLOBALS["CLASS_UNIX"]=$unix;
    $GLOBALS["nmblookup"]=$unix->find_program("nmblookup");
    $GLOBALS["arpd"]=$unix->find_program("arpd");
    $GLOBALS["arp"]=$unix->find_program("arp");
    $GLOBALS["ARP_DB"]="/var/lib/arpd/arpd.db";
    $GLOBALS["CACHE_DB"]="/etc/artica-postfix/arpd.cache";
    $GLOBALS["EnableMacAddressFilter"]=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMacAddressFilter"));
    if(!is_numeric($GLOBALS["EnableMacAddressFilter"])){$GLOBALS["EnableMacAddressFilter"]=1;}

    $ArpdArray=unserialize(base64_decode(@file_get_contents($GLOBALS["CACHE_DB"])));
    if($GLOBALS["FLUSH"]){$ArpdArray=array();}
    if(!is_array($ArpdArray)){$ArpdArray=array();}
    if(!isset($ArpdArray["LAST"])){$ArpdArray["LAST"]=0;}

    $last_modified = filemtime($GLOBALS["ARP_DB"]);
    $TimeArpd=$ArpdArray["LAST"];
    if($TimeArpd==$last_modified){events("$TimeArpd -> $last_modified No modification time",__FUNCTION__,__LINE__);return;}
    events("Scanning ARP table....",__FUNCTION__,__LINE__);
    $ArpdArray["LAST"]=$last_modified;

    exec("{$GLOBALS["arpd"]} -l 2>&1",$results);
    events("{$GLOBALS["arpd"]} -l return " . count($results)." element(s)",__FUNCTION__,__LINE__);
    foreach ($results as $num=>$ligne){
        if(preg_match("#unexpected file type or format#", $ligne)){@unlink($GLOBALS["ARP_DB"]);@unlink($GLOBALS["CACHE_DB"]);shell_exec("/etc/init.d/arpd restart");exit();}
        if(!preg_match("#^[0-9]+\s+\s+(.+?)\s+(.+)#", $ligne,$re)){if($GLOBALS["VERBOSE"]){echo "line: $num, unexpected line..\n";}continue;}
        if(preg_match("#FAILED:#", $re[2])){continue;}

        $mac=$re[2];
        $ipaddr=$re[1];
        if($GLOBALS["VERBOSE"]){echo "line: $num, MAC:$mac -> $ipaddr\n";}
        if(isset($ArpdArray["MACS"][$mac])){if($GLOBALS["VERBOSE"]){echo "MAC:$mac Already cached, aborting....\n";}continue;}
        $ArpdArray["MACS"][$mac]=true;
        $cmp=new computers();

        $uid=$cmp->ComputerIDFromMAC($mac);
        if($GLOBALS["VERBOSE"]){echo "line: $num, MAC:$mac -> $uid\n";}
        if($uid==null){
            $res2=array();
            $computer_name=null;
            events("It is time to add $mac/$ipaddr in database",__FUNCTION__,__LINE__);
            exec("{$GLOBALS["arp"]} -a $ipaddr 2>&1",$res2);
            if(preg_match("#^(.+?)\s+\(#",trim(@implode("", $res2)),$rz)){$computer_name=$rz[1];}
            if(strlen($computer_name)<3){$computer_name=$ipaddr;}
            $cmp->uid="$computer_name$";
            $cmp->ComputerIP=$ipaddr;
            $cmp->ComputerMacAddress=$mac;
            squid_admin_mysql(2, "adding/editing $computer_name with MAC:$mac", __FUNCTION__, __FILE__, __LINE__, "network");
            $cmp->Add();
        }else{
            if($GLOBALS["FLUSH"]){
                $res2=array();
                $cmp=new computers($uid);
                $computer_name=null;
                events("It is time to edit $uid/$mac/$ipaddr in database",__FUNCTION__,__LINE__);

                exec("{$GLOBALS["arp"]} -a $ipaddr 2>&1",$res2);
                if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["arp"]} -a $ipaddr 2>&1 = >". trim(@implode("", $res2));}
                if(preg_match("#^(.+?)\s+\(#",trim(@implode("", $res2)),$rz)){$computer_name=$rz[1];}else{if($GLOBALS["VERBOSE"]){echo "Unable to find computer name\n";}}
                if(strlen($computer_name)<3){$computer_name=$ipaddr;}
                if($GLOBALS["VERBOSE"]){echo "line: $num, UID:$mac -> $uid\n";}
                if($GLOBALS["VERBOSE"]){echo "line: $num, NAME:$computer_name -> $uid\n";}
                squid_admin_mysql(2, "adding/editing $computer_name with MAC:$mac", __FUNCTION__, __FILE__, __LINE__, "network");
                $cmp->ComputerIP=$ipaddr;
                $cmp->ComputerMacAddress=$mac;
                $cmp->Add();
                }

        }

        if(system_is_overloaded(basename(__FILE__))){
            @file_put_contents($GLOBALS["CACHE_DB"], base64_encode(serialize($ArpdArray)));
            squid_admin_mysql(1, "{OVERLOADED_SYSTEM}, aborting the task...", ps_report(), __FILE__, __LINE__);
            exit();
        }


        @file_put_contents($GLOBALS["CACHE_DB"], base64_encode(serialize($ArpdArray)));
        $nice=EXEC_NICE();
        $unix=new unix();
        $nohup=$unix->find_program("nohup");
        $php5=$unix->LOCATE_PHP5_BIN();
        shell_exec("$nohup $nice $php5 ".__FILE__." --tomysql schedule-id={$GLOBALS["SCHEDULE_ID"]} >/dev/null 2>&1 &");

    }
    return true;

}
//========================================================================================================================================================
function syslog_status($text){$file="arpscan";if(!function_exists('syslog')){return null;}openlog($file, LOG_PID | LOG_PERROR, LOG_LOCAL0);syslog(LOG_INFO, $text);closelog();}
//========================================================================================================================================================

function scanarp_mysql(){
	$unix=new unix();
	$t=time();

	if(systemMaxOverloaded()){return;}
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$unix=new unix();
	$me=basename(__FILE__);
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,$me)){
		if($GLOBALS["VERBOSE"]){echo " --> Already executed.. $pid aborting the process\n";}
		squid_admin_mysql(2, "--> Already executed.. $pid aborting the process", __FUNCTION__, __FILE__, __LINE__, "network");
		exit();
	}
	
	$sock=new sockets();
	$EnableArpDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArpDaemon"));
	$ArpdKernelLevel=$sock->GET_INFO("ArpdKernelLevel");
	if(!is_numeric($EnableArpDaemon)){$EnableArpDaemon=1;}
	
	$articastatus_pidfile="/etc/artica-postfix/exec.status.php.pid";
	$pid=$unix->get_pid_from_file($articastatus_pidfile);
	if(!$unix->process_exists($pid)){
		syslog_status("artica status doesn't run, start it, old pid was: $pid");
		shell_exec("/etc/init.d/artica-status start");
	}
	
	$list=$unix->PIDOF_PATTERN_ALL($me);
	if(count($list)>2){
		squid_admin_mysql(2, "--> Already executed..". count($list)." Processes executed");
		exit(); 
	}
	
	@file_put_contents($pidfile, getmypid());	
	
	
	$arpbin=$unix->find_program("arp");
	$arpdbin=$unix->find_program("arpd");
	if(!is_file($arpbin)){echo "arp, no such binary...\n";return;}
	exec("$arpbin -a 2>&1",$results);
	if($GLOBALS["VERBOSE"]){echo "$arpbin -a 2>&1\n";}
	$prefix="INSERT INTO arpcache (`mac`,`ipaddr`,`hostname`,`HWtype`,`iface`) VALUES ";
	
	foreach ($results as $num=>$ligne){
		if(preg_match("#^(.+?)\s+\((.+?)\)\s+.+?\s+(.+?)\s+\[(.+?)\]\s+.+?\s+(.+)#", $ligne,$re)){
			$mac=trim($re[3]);
			$hostname=trim($re[1]);
			$ipaddr=trim($re[2]);
			$HWtype=trim($re[4]);
			$iface=trim($re[5]);
			if($GLOBALS["VERBOSE"]){echo "MATCH `$ligne` '$mac','$ipaddr','$hostname','$HWtype','$iface'\n";}
			$f[]="('$mac','$ipaddr','$hostname','$HWtype','$iface')";
			continue;
		}
		if(preg_match("#^([a-z0-9\.\-\_\?]+)\s+\((.+?)\).+?incomplete.+?[a-z]+\s+(.+)$#", $ligne,$re)){
			
			$mac=null;
			$hostname=trim($re[1]);
			$ipaddr=trim($re[2]);
			$HWtype=null;
			$iface=trim($re[3]);
			if($GLOBALS["VERBOSE"]){echo "MATCH `$ligne` '$mac','$ipaddr','$hostname','$HWtype','$iface'\n";}
			$f[]="('$mac','$ipaddr','$hostname','$HWtype','$iface')";
			continue;
		}
		
		
		if($GLOBALS["VERBOSE"]){echo "No match `$ligne`\n";}
		
		
	}
	
	if(is_file($arpdbin)){
		$results=array();
		exec("$arpdbin -l 2>&1",$results);
		foreach ($results as $num=>$ligne){
			if(preg_match("#^[0-9]+\s+([0-9\.]+)\s+(.+)#",$ligne,$re)){
				$mac=trim($re[2]);
				if(preg_match("#FAILED:#", $mac)){$mac=null;}
				$hostname=null;
				$ipaddr=trim($re[1]);
				$HWtype=null;
				$iface="arpd";	
				$f[]="('$mac','$ipaddr','$hostname','$HWtype','$iface')";
				continue;			
			}
			
		}
	}
	
	
	
	
	if(count($f)>0){
		$q=new mysql();
		$q->QUERY_SQL("TRUNCATE TABLE `arpcache`","artica_backup");
		if($GLOBALS["VERBOSE"]){echo count($f)." entries\n";}
		$sql=$prefix.@implode(",", $f);
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			squid_admin_mysql(2, "Fatal, $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "network");
			return;
		}
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		system_admin_events(count($f)." ARP entries added into MySQL server", __FUNCTION__, __FILE__, __LINE__, "network");		
	}
				 
	
	
	
	
}



function events($text,$function=null,$line=0){
	$filename=basename(__FILE__);
	if(!isset($GLOBALS["CLASS_UNIX"])){include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}$GLOBALS["CLASS_UNIX"]=new unix();}
	$GLOBALS["CLASS_UNIX"]->events("$filename $function:: $text (L.$line)",null);
}
