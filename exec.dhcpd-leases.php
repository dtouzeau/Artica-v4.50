#!/usr/bin/php -q
<?php
ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}


include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
include_once(dirname(__FILE__) . '/ressources/class.hosts.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}
$GLOBALS["CLASS_SOCKETS"]=new sockets();}


if(isset($argv[1])) {
    if ($argv[1] == "commit") {
        update_commit($argv[2], $argv[3], $argv[4]);
        die(0);
    }
}

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$cache_file="/etc/artica-postfix/dhcpd.leases.dmp";
$unix=new unix();



	
if($unix->process_exists(@file_get_contents($pidfile,basename(__FILE__)))){
	build_progress_scan("Already executed.",110);
	if($GLOBALS["VERBOSE"]){echo " --> Already executed.. ". @file_get_contents($pidfile). " aborting the process\n";}
	exit();
}
@file_put_contents($pidfile, getmypid());

if(!$GLOBALS["FORCE"]){
	$TimeFile=$unix->file_time_min($cache_file);
	if($TimeFile<30){
		build_progress_scan("{$TimeFile}Mn, require 30mn",110);
		if($GLOBALS["VERBOSE"]){echo " {$TimeFile}Mn, require 30mn\n";}
		exit();
	}
}

$pid=dhcpd_pid();
if(!$unix->process_exists($pid)){
	build_progress_scan("DHCPD service not running",110);
	if($GLOBALS["VERBOSE"]){echo " --> DHCPD service not running...\n";}
	return;
}

$ptime=$unix->PROCCESS_TIME_MIN($pid);
if($ptime<2){
	build_progress_scan("DHCPD service is running only since 2mn",110);
	if($GLOBALS["VERBOSE"]){echo " --> DHCPD service running before 2mn...\n";}
	return;
}



$GLOBALS["nmblookup"]=$unix->find_program("nmblookup");
if($argv[1]=="lookup"){echo "{$argv[2]}:".nmblookup($argv[2],$argv[3])."\n";exit();}

if($argv[1]=='--single-computer'){exit();}
if($GLOBALS["VERBOSE"]){
	echo " --> Argument={$argv[1]}\n";
	echo " --> Force={$GLOBALS["FORCE"]}\n";

}

$sock=new sockets();
$EnableDHCPServer=$sock->GET_INFO('EnableDHCPServer');
$ComputersAllowDHCPLeases=$sock->GET_INFO("ComputersAllowDHCPLeases");
if($ComputersAllowDHCPLeases==null){$ComputersAllowDHCPLeases=1;}
if($EnableDHCPServer==0){writelogs("EnableDHCPServer is disabled, aborting...","MAIN",__FILE__,__LINE__);exit();}
if($ComputersAllowDHCPLeases==0){
	build_progress_scan("ComputersAllowDHCPLeases is disabled",110);
	if($GLOBALS["VERBOSE"]){echo " -->ComputersAllowDHCPLeases is disabled -> die()\n";}
	writelogs("ComputersAllowDHCPLeases is disabled, aborting...","MAIN",__FILE__,__LINE__);
	exit();
}

localsyslog("Check changed leases...");
if(!$GLOBALS["FORCE"]){if($GLOBALS["VERBOSE"]){echo " -->Changed()\n";}if(!Changed()){exit();}}

$datas=@file_get_contents("/var/lib/dhcp3/dhcpd.leases");
$md5Tampon=md5_file("/var/lib/dhcp3/dhcpd.leases");
$md5Local=md5_file("/etc/artica-postfix/dhcpd.leases.dmp");




dhcpd_logs("dhcpd.leases.dmp: $md5Local / $md5Tampon");

if($GLOBALS["VERBOSE"]){echo " --> MD5LOCAL=$md5Local / MD5Tampon=$md5Tampon\n";}

if(!$GLOBALS["FORCE"]){
	if($md5Local==$md5Tampon){
		if($GLOBALS["VERBOSE"]){echo " --> $md5Local == $md5Tampon, abort\n";}
		exit();
	}
}

@unlink($cache_file);
@file_put_contents($cache_file,$md5Tampon);


writelogs("LOCAL:$md5Local !== REMOTE:$md5Tampon","MAIN",__FILE__,__LINE__);
write_syslog("integrity of dhcpd.leases has been modified ( from $md5Local to $md5Tampon), analyze the leases",basename(__FILE__));
build_progress_scan("Scanning",15);
if($GLOBALS["VERBOSE"]){echo " --> CleanFile()\n";}
CleanFile();
if($GLOBALS["VERBOSE"]){echo " --> /var/lib/dhcp3/dhcpd.leases\n";}
$datas=@file_get_contents("/var/lib/dhcp3/dhcpd.leases");
build_progress_scan("Scanning",20);

$md5        = md5($datas);
$unix       = new unix();


$GLOBALS["FIXIPHOST"]=false;
    if(!dhcp_lease_list()){
        build_progress_scan("{failed}",110);
        return;
    }
	build_progress_scan("{done}",100);
	events("Set content cache has $md5","main",__LINE__);
	$sock->SET_INFO('DHCPLeaseMD5',$md5);



function events($text,$function,$line=null){
		writelogs($text,$function,__FILE__,$line);
}


function hostname_from_dhcpd_hosts($mac){
	if($mac==null){return null;}
	$q=new postgres_sql();
	$sql="SELECT hostname FROM dhcpd_hosts WHERE mac='$mac' ORDER BY updated DESC LIMIT 1";
	$ligne=@pg_fetch_array($q->QUERY_SQL($sql));
	return $ligne["hostname"];
}


function Changed(){
	if(!is_file("/var/lib/dhcp3/dhcpd.leases")){
		if($GLOBALS["VERBOSE"]){echo " --> unable to stat /var/lib/dhcp3/dhcpd.leases\n";}
		return false;
	}
	$sock=new sockets();
	@chown("/var/lib/dhcp3/dhcpd.leases", "dhcpd");
	$DHCPLeaseMD5=$sock->GET_INFO('DHCPLeaseMD5');
	if($DHCPLeaseMD5==null){return true;}
	$datas=@file_get_contents("/var/lib/dhcp3/dhcpd.leases");
	$md5=md5($datas);
	if($GLOBALS["VERBOSE"]){echo " --> $DHCPLeaseMD5 Current: $md5\n";}
	if(trim($DHCPLeaseMD5)==$md5){
		if($GLOBALS["VERBOSE"]){echo " --> Not changed\n";}
		return false;
	}
	return true;
}

function CleanFile(){
	$datas=@file_get_contents("/var/lib/dhcp3/dhcpd.leases");
	if($GLOBALS["VERBOSE"]){echo " --> /var/lib/dhcp3/dhcpd.leases ". strlen($datas)." bytes\n";}
	$tbl=explode("\n",$datas);
	foreach ($tbl as $num=>$ligne){
		if(preg_match("#^\##",$ligne)){
			unset($tbl[$num]);
		}
	}
	writelogs("/var/lib/dhcp3/dhcpd.leases cleaned",__FUNCTION__,__FILE__,__LINE__);
	if($GLOBALS["VERBOSE"]){echo " --> /var/lib/dhcp3/dhcpd.leases cleaned...\n";}
	@file_put_contents("/var/lib/dhcp3/dhcpd.leases",implode("\n",$tbl));
}

function update_computer($ip,$mac,$name){
	$sock=new sockets();	
	$ComputersAllowDHCPLeases=$sock->GET_INFO("ComputersAllowDHCPLeases");
	if($ComputersAllowDHCPLeases==null){$ComputersAllowDHCPLeases=1;}
	if($ComputersAllowDHCPLeases==0){localsyslog("`ComputersAllowDHCPLeases` Aborting updating the LDAP database");return;}	
	
	$mac=trim($mac);
	$name=trim(strtolower($name));
	$ip=trim($ip);
	if($ip==null){return;}
	if($mac==null){return;}
	if($name==null){return;}
	$mac=strtolower(str_replace("-", ":", $mac));
	$ipClass=new IP();
	if($ipClass->isIPAddress($name)){
		localsyslog("`$name` is a TCP IP address, aborting updating the LDAP database");return;
	}
	
	
	
	$ip=nmblookup($name,$ip);
	$dhcp=new dhcpd();
	$GLOBALS["domain"]=$dhcp->ddns_domainname;	
	
	$comp=new computers();
	$uid=$comp->ComputerIDFromMAC($mac);
	
	if(strpos($name, ".")>0){
		$NAMETR=explode(".",$name);
		$name=$NAMETR[0];
		unset($NAMETR[0]);
		$GLOBALS["domain"]=@implode(".", $NAMETR);
	}
	
	if($ipClass->isIPAddress($uid)){	
		$comp=new computers($uid);
		localsyslog("Removing computer ($uid) $mac");
		$comp->DeleteComputer();
		$uid=null;
		$uid=$comp->ComputerIDFromMAC($mac);
	}
	
	localsyslog("$mac -> uid:`$uid`");
	
	if($uid==null){
		$add=true;
		$uid="$name$";
		$comp=new computers();
		$comp->ComputerRealName=$name;
		$comp->ComputerMacAddress=$mac;
		$comp->ComputerIP=$ip;
		$comp->DnsZoneName=$GLOBALS["domain"];
		$comp->uid=$uid;
		$ComputerRealName=$name;
		localsyslog("Create new computer $name[$ip] ($uid) $mac in domain $comp->DnsZoneName");
		$comp->Add();

	}else{
		$comp=new computers($uid);
		if(strpos($comp->ComputerRealName, ".")>0){
			$NAMETR=explode(".",$name);
			$comp->ComputerRealName=$NAMETR[0];
		}
		
		if($comp->ComputerRealName==null){$comp->ComputerRealName=$name;}
		if($ipClass->isIPAddress($comp->ComputerRealName)){$comp->ComputerRealName=$name;}
		$comp->ComputerIP=$ip;
		$comp->DnsZoneName=$GLOBALS["domain"];
		localsyslog("Update computer $comp->ComputerRealName[$ip] ($uid) $mac in domain $comp->DnsZoneName");
		$comp->Edit();
		
	}
	
	
	$dns=new pdns($GLOBALS["domain"]);
	$dns->EditIPName(strtolower($name),$ip,'A',$mac);	

}


function nmblookup($hostname,$ip){
	if(trim($hostname)==null){return $ip;}
	$hostname=str_replace('$','',$hostname);
	if($GLOBALS["nmblookup"]==null){
		$unix=new unix();
		$GLOBALS["nmblookup"]=$unix->find_program("nmblookup");
	}
	
	if($GLOBALS["nmblookup"]==null){
		if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> Could not found binary\n";}
		return $ip;
	}
	if(preg_match("#^[0-9]+\.[0-9]+.[0-9]+\.[0-9]+$#",$hostname)){
		if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> hostname match IP string, aborting\n";}
		return $ip;
	}
	
	if(preg_match("#([0-9]+)\.([0-9]+).([0-9]+)\.([0-9]+)#",$ip,$re)){
		$broadcast="{$re[1]}.{$re[2]}.{$re[3]}.255";
	}else{
		if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> $ip not match for broadcast addr\n";}
		return $ip;
	}
	
	if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> broadcast=$broadcast\n";}
	$cmd="{$GLOBALS["nmblookup"]} -B $broadcast $hostname";
	if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> $cmd\n";}
	exec($cmd,$results);
	
	foreach ($results as $num=>$ligne){
		if(preg_match("#Got a positive name query response from\s+([0-9\.]+)#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> {$re[1]}\n";}
			return $re[1];
		}
	}
	if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> NO MATCH\n";}
	return $ip;
}


function localsyslog($text){
	dhcpd_logs($text);
	
}

function update_commit($ip,$mac,$hostname=null){
	$domain=null;
	$ipClass=new IP();

	$DHCPDAutomaticFixIPAddresses=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDAutomaticFixIPAddresses"));
    $DHCPAddNewComputers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPAddNewComputers"));
    if($DHCPDAutomaticFixIPAddresses==1){$DHCPAddNewComputers=1;}

	dhcpd_logs("$mac: COMMIT: $hostname:[$ip]");
	$hostname=str_replace(" ", "-", $hostname);

	if($DHCPAddNewComputers==0){return;}
	
	if(!$ipClass->isValid($ip)){
		squid_admin_mysql(1, "$mac $hostname -> invalid ip $ip", null,__FILE__,__LINE__);
		return;
	}
	
	$macZ=explode(":",$mac);
	while (list ($num, $ligne) = each ($macZ) ){
		if(strlen($ligne)==1){$macZ[$num]="0$ligne";}
		
	}
	$mac=@implode(":", $macZ);
	$hosts=new hosts($mac);
	
	if($hosts->fullhostname<>null){
		if(strpos($hosts->fullhostname, ".")>0){
			$tt=explode(".",$hosts->fullhostname);
			$hostname1=$tt[0];
			unset($tt[0]);
			$domain=@implode(".", $tt);
		}
		
	}
	
	if($hostname==null){$hostname=$hostname1;}
	
	if(strpos($hostname, ".")>0){
		dhcpd_logs("$mac: EXPLODE -> $hostname");
		$tt=explode(".",$hostname);
		$hostname=$tt[0];
		unset($tt[0]);
		$domain=@implode(".", $tt);
		
	}
	
	dhcpd_logs("$mac: fullhostname=$hostname");
	$hosts->fullhostname=$hostname;
	
	
	if($domain<>null){
		$hosts->fullhostname=$hostname.".$domain";
		$hosts->hostname=$hostname;
	}
	
	$hosts->dhcpfixed=$DHCPDAutomaticFixIPAddresses;
	$hosts->ipaddr=$ip;
	$hosts->Save();
	
	CreateComputerLogs($ip,$mac,$hostname);
	
	if(!$hosts->ok){
		dhcpd_logs("$mac: $hosts->mysql_error");
		squid_admin_mysql(1, "Unable to update host entry from DHCP", "$hosts->mysql_error\nMAC:$mac\nIP:$ip\nHostname:$hostname",__FILE__,__LINE__);
	}
}


function dhcp_lease_list(){
    $f          = array();
    $q          = new postgres_sql();
    $prefix     = "INSERT INTO dhcpd_leases (mac,hostname,starts,ends,cltt,tstp,atsfp,ipaddr) VALUES";
    $q->QUERY_SQL("TRUNCATE TABLE dhcpd_leases");
   // $q->HOSTS_TABLES();



    if(!is_file("/usr/share/artica-postfix/bin/dhcp-lease-list.pl")){return false;}
    @chmod("/usr/share/artica-postfix/bin/dhcp-lease-list.pl",0755);

    if(!is_file("/usr/local/etc/oui.txt")){
        @mkdir("/usr/local/etc",0755,true);
        @copy("/usr/share/artica-postfix/ressources/databases/oui.txt","/usr/local/etc/oui.txt");
    }


    exec("/usr/share/artica-postfix/bin/dhcp-lease-list.pl --parsable 2>&1",$results);


    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#MAC\s+(.+?)\s+IP\s+([0-9\.]+)\s+HOSTNAME\s+(.+?)\s+BEGIN\s+(.+?)\s+END\s+(.+?)\s+MANUFACTURER\s+(.*)#",$line,$re)){
            echo "$line NOT Matches\n";
            continue;}

        $MAC=$re[1];
        $IP=$re[2];
        $hostname=trim($re[3]);
        $begin=$re[4];
        $end=$re[5];
        $MANUFACTURER=$re[7];
        if($hostname=="-NA-"){$hostname="unknown";}
        $hostname=str_replace("$", "", $hostname);
	    $hostname=trim(strtolower($hostname));

	    echo "[$IP]:$MAC ($hostname) $begin - $end\n";

        $begin_int=strtotime($begin);
        $end_int=strtotime($end);
        $f[]="('$MAC','$hostname','$begin','$end','$end','$end','$end','$IP')";






        $MAIN[$MAC]=array("IP"=>$IP,"HOTS"=>$hostname,"END"=>$end,"MANU"=>$MANUFACTURER);
        if(strlen($MANUFACTURER)>5){
            $hosts=new hosts($MAC);
            $hosts->vendor=$MANUFACTURER;
            $hosts->ipaddr=$IP;
            if($hosts->hostname==null){$hosts->hostname=$hostname;}
            $hosts->Save();
        }


    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DHCPD_LEASE_LIST_LAST",serialize($MAIN));

    if(count($f)>0) {
        echo "Inserting ".count($f)." elements\n";
        $sql = $prefix . @implode(",", $f) . " ON CONFLICT DO NOTHING";
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            echo $q->mysql_error."\n";
            return false;
        }

    }

    return true;




}



function CreateComputerLogs($ip,$mac,$hostname){
	

	$q=new postgres_sql();

	
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT mac FROM dhcpd_hosts WHERE mac='$mac'"));
	dhcpd_logs("$mac: dhcpd_hosts =  '{$ligne["mac"]}'");
	
	$time=date("Y-m-d H:i:s");
	if($ligne["mac"]==null){
		dhcpd_logs("CreateComputerLogs: $mac: INSERT ('$mac','$time','$time','$ip','$hostname')");
		$q->QUERY_SQL("INSERT INTO dhcpd_hosts (MAC,created,updated,ipaddr,hostname) VALUES('$mac','$time','$time','$ip','$hostname')");
		if(!$q->ok){squid_admin_mysql(0, "MySQL Error Line ".__LINE__, $q->mysql_error,__FILE__,__LINE__);}
	}else{
		dhcpd_logs("CreateComputerLogs: $mac: UPDATE ipaddr='$ip',hostname='$hostname',updated='$time'");
		$q->QUERY_SQL("UPDATE dhcpd_hosts SET ipaddr='$ip',hostname='$hostname',updated='$time' WHERE mac='$mac'");
		if(!$q->ok){squid_admin_mysql(0, "MySQL Error Line ".__LINE__, $q->mysql_error,__FILE__,__LINE__);}
		$q->QUERY_SQL("UPDATE hostsnet SET ipaddr='$ip' WHERE mac='$mac'");
		
	}
	
	
	
}

function build_progress_scan($text,$pourc){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/dhcpd.leases.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);


}

function dhcpd_logs($text){
	$unix=new unix();
	$unix->events($text,"/var/log/artica-dhcpd.log",false);
}
function dhcpd_pid(){
	$unix=new unix();
	$filename="/var/run/dhcpd.pid";
	$pid=$unix->get_pid_from_file($filename);
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($unix->DHCPD_BIN_PATH());
}


?>