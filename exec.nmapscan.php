<?php

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if(is_file("/etc/artica-postfix/AS_KIMSUFFI")){echo "AS_KIMSUFFI!\n";exit();}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if($argv[1]=='--parse'){parsefile("/etc/artica-postfix/{$argv[2]}.map",$argv[3]);exit();}
if($argv[1]=="--scan-nets"){scannetworks();exit;}
if($argv[1]=="--scan-results"){nmap_scan_results();exit;}
if($argv[1]=="--scan-period"){nmap_scan_period();exit;}
if($argv[1]=="--scan-single"){nmap_scan_single($argv[2],$argv[3]);exit;}
if($argv[1]=="--scan-ping"){nmap_scan_pingnet();exit;}

$GLOBALS["COMPUTER"]=$argv[1];
$GLOBALS["COMPUTER"]=str_replace('$',"",$GLOBALS["COMPUTER"]);
if($GLOBALS["COMPUTER"]==null){echo "no computer name set {$argv[1]}!\n";exit();}

$users=new usersMenus();
$sock=new sockets();
$NmapScanEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NmapScanEnabled"));

if($NmapScanEnabled==0){
	echo basename(__FILE__)." !!!!!!!!!!!! DISABLED !!!!!!!!!!!!\n";
	build_progress("{disabled}",110);
	exit();
}

$unix=new unix();

$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".time";

$pid=$unix->get_pid_from_file($pidfile);
if($unix->process_exists($pid)){if($GLOBALS["VERBOSE"]){echo "Already $pid running, aborting...\n";}return;}

@file_put_contents($pidfile, getmypid());
@file_put_contents($pidtime, time());


if(!is_file($users->NMAP_PATH)){echo "Unable to stat nmap binary file...\n";exit;}
$computer=new computers($GLOBALS["COMPUTER"].'$');
echo "Scanning \"{$GLOBALS["COMPUTER"]}\":[$computer->ComputerIP] (".__LINE__.")\n";
if($computer->ComputerIP=="0.0.0.0"){$computer->ComputerIP=null;}
if($computer->ComputerIP==null){$computer->ComputerIP=gethostbyname($GLOBALS["COMPUTER"]);}
if($computer->ComputerIP<>null){$cdir=$computer->ComputerIP;}else{$cdir=$GLOBALS["COMPUTER"];}
echo "Scanning $cdir and save results to /etc/artica-postfix/$cdir.map (".__LINE__.")\n";
$cmd=$users->NMAP_PATH." -v -F -PE -Pn -O $cdir -oG --system-dns --version-light 2>&1";
echo "Executing $cmd (".__LINE__.")\n";
exec($cmd,$results);
@file_put_contents("/etc/artica-postfix/$cdir.map", @implode("\n", $results));

echo "Parsing results for $cdir (".__LINE__.")\n";
if(!is_file("/etc/artica-postfix/$cdir.map")){echo "Unable to stat /etc/artica-postfix/$cdir.map (".__LINE__.")\n";exit;}

parsefile("/etc/artica-postfix/$cdir.map",$GLOBALS["COMPUTER"]);   


function parsefile($filename,$uid,$perc=0){
	if($perc==0){$perc=10;}
	if($GLOBALS["VERBOSE"]){echo __LINE__."] Parsing file $filename\n";}
	$datas=file_get_contents($filename);
	$tbl=explode("\n",$datas);
	if(!is_array($tbl)){return null;}
	$ComputerMacAddress=null;
	$ComputerRunning=null;
	$ComputerMachineType=null;
	$ComputerOS=null;
	$cpid=null;
	
	foreach ($tbl as $num=>$ligne){
		if(trim($ligne)==null){continue;}
		if(preg_match("#([0-9]+).+?open\s+(.+)#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo __LINE__."] PORT: {$re[1]} -> {$re[2]} ///////////////////\n";}
			$PORTS[$re[1]]=$re[2];
			continue;
		}
		
		if(preg_match("#^Running:(.+)#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo __LINE__."] Running: {$re[1]}\n";}
			$ComputerRunning=$re[1];
			continue;
		}
		
		if(preg_match("#^OS details:(.+)#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo __LINE__."] OS details: {$re[1]}\n";}
			$ComputerOS=$re[1];
			continue;
		}	
		if(preg_match("#^MAC Address:(.+).+?\((.+?)\)#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo __LINE__."] MAC Address: {$re[1]}\n";}
			$ComputerMacAddress=trim(strtolower($re[1]));
			$ComputerMachineType=$re[2];
			continue;
		}

		if(preg_match("#([0-9]+).+?open\s+(.+)#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo __LINE__."] PORT: {$re[1]} -> {$re[2]} ///////////////////\n";}
			$PORTS[$re[1]]=$re[2];
			continue;
		}
		
		
		
		if(preg_match("#^MAC Address:(.+)#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo __LINE__."] MAC Address: {$re[1]}\n";}
			$ComputerMacAddress=trim(strtolower($re[1]));
			continue;
		}

		if(preg_match("#^MAC Address:\s+(.+?)\s+#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo __LINE__."] MAC Address: {$re[1]}\n";}
			$ComputerMacAddress=$re[1];
			continue;
		}
		
		if(preg_match("#^Aggressive OS guesses:\s+(.+)#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo __LINE__."] ******* Aggressive OS guesses: {$re[1]}\n";}
			$OSD=explode("-",$re[1]);
			
			while (list ($num, $xline) = each ($OSD) ){
				if($GLOBALS["VERBOSE"]){echo __LINE__."] $xline\n";}
				if(preg_match("#Apple iOS#", $xline)){$ComputerOS="Apple Mac OS";break;}
				if(preg_match("#Apple iPhone#", $xline)){$ComputerOS="Apple iPhone";break;}
				if(preg_match("#Apple Mac OS#", $xline)){$ComputerOS="Apple Mac OS";break;}
				
			}
			continue;
		}
		
		 
		if($GLOBALS["VERBOSE"]){echo __LINE__."] \"$ligne\" Not parsed...\n";}
		
	}
	
	
	if($ComputerMacAddress<>null){
		$computer=new computers();
		$cpid=$computer->ComputerIDFromMAC($ComputerMacAddress);
		build_progress("Analyze $ComputerMacAddress ",$perc+5);
		
	}
	
	
	if($GLOBALS["VERBOSE"]){echo " xxxxxxxxxxxx  ".count($PORTS)." ports xxxxxxxxxxxx\n";}
	if(count($PORTS)>0){
		AddPorts($PORTS, $ComputerMacAddress);
	}
	
	if($cpid==null){$cpid=$uid;}
	echo "Save infos for $cpid (".__LINE__.")\n";
	echo "ComputerMacAddress: $ComputerMacAddress (".__LINE__.")\n";
	echo "ComputerOS: $ComputerOS (".__LINE__.")\n";
	
	build_progress("Adding {$cpid}$ ",$perc+5);
	$computer=new computers($cpid."$");
	if($ComputerMacAddress<>null){$computer->ComputerMacAddress=$ComputerMacAddress;}
	if($ComputerOS<>null){$computer->ComputerOS=$ComputerOS;}
	if($ComputerRunning<>null){$computer->ComputerRunning=$ComputerRunning;}
	if($ComputerMachineType<>null){$computer->ComputerMachineType=$ComputerMachineType;}
	if(is_array($array)){
		$computer->ComputerOpenPorts=base64_encode(serialize($array));
	}
	echo "Update it has $cpid with MAC $ComputerMacAddress (".__LINE__.")\n";
	if(!$computer->Edit(basename(__FILE__))){
		echo "Failed to save infos for $cpid (".__LINE__.")\n";
	}
	build_progress("Done...",$perc+5);
	echo $datas;
	
}

function scannetworks(){
	
	if(system_is_overloaded(basename(__FILE__))){
		writelogs("{OVERLOADED_SYSTEM}, aborting",__FUNCTION__,__FILE__,__LINE__);
		return;
	}	
	
	$unix=new unix();
	$sock=new sockets();
	$nmap=$unix->find_program("nmap");
	$cdir=array();
	if(!is_file($nmap)){return false;}
	$NmapScanEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NmapScanEnabled"));
	$NmapRotateMinutes=$sock->GET_INFO("NmapRotateMinutes");
	
	if(!is_numeric($NmapRotateMinutes)){$NmapRotateMinutes=60;}
	if($NmapRotateMinutes<5){$NmapRotateMinutes=5;}
	$NmapFastScan=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NmapFastScan"));
	if($NmapScanEnabled==0){return;}
	if(!$GLOBALS["VERBOSE"]){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
		$pidtime="/etc/artica-postfix/pids/exec.nmapscan.php.time";
		
		if($unix->file_time_min($pidtime)<$NmapRotateMinutes){
			if($GLOBALS["VERBOSE"]){echo "No time to be executed\n";}
			return;
		}
		
		
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid)){
			if($GLOBALS["VERBOSE"]){echo "Already $pid running, aborting...\n";}
			return;
		}
		
		@file_put_contents($pidfile, getmypid());
		@file_put_contents($pidtime, time());
	}
	
	$net=new networkscanner();
    foreach ($net->networklist as $num=>$maks){if(trim($maks)==null){continue;}$hash[$maks]=$maks;}
    foreach ($hash as $num=>$maks){if(!$net->Networks_disabled[$maks]){if($GLOBALS["VERBOSE"]){echo "Network: $maks OK\n";}$cdir[]=$maks;}}
	if(count($cdir)==0){if($GLOBALS["VERBOSE"]){echo "No network, aborting...";}return;}
	
	if($NmapFastScan==1){
		while (list ($num, $maks) = each ($cdir)){
			arp_scanner($maks,true);
		}
		return;
	}
	
	
	$cmd=$unix->NMAP_CMDLINE(trim(@implode(" ", $cdir)), "/etc/artica-postfix/nmap.map")." 2>&1";

	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	exec($cmd,$results);
	
	foreach ($results as $index=>$ligne){
		if(preg_match("#\(([0-9]+).+?hosts.+?scanned in(.+)#", $ligne,$re)){
			$hosts=$re[1];
			$time=trim($re[2]);
			nmap_logs("$hosts scanned in $time",@implode("\n", $results));
			break;
		}
	}
	
	nmap_scan_results();
	
}

function nmap_scan_pingnet_progress($text,$pourc){
	$cachefile="/usr/share/artica-postfix/ressources/logs/nmap.pingnet.progress";
	echo "{$pourc}%)  $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	
	
}


function arp_scanner($net,$insert=false){
	if(!is_file("/usr/bin/arp-scan")){
		if(!isset($GLOBALS["DEBIAN_INSTALL_PACKAGE_ARP_SCAN"])){
			$unix=new unix();
			$unix->DEBIAN_INSTALL_PACKAGE("arp-scan");
			$GLOBALS["DEBIAN_INSTALL_PACKAGE_ARP_SCAN"]=true;
		}
		if(!is_file("/usr/bin/arp-scan")){return array();}
	}
	exec("/usr/bin/arp-scan --quiet --retry=1 $net 2>&1",$results);
	$MAIN=array();
	foreach ($results as $num=>$line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^([0-9]+).([0-9]+).([0-9]+).([0-9]+)\s+(.+?)\s+(.+)#", $line,$re)){continue;}
		$ipaddr="{$re[1]}.{$re[2]}.{$re[3]}.{$re[4]}";
		$mac=$re[5];
		$vendor=$re[6];
		echo "Found $ipaddr -> $mac ( $vendor )\n";
		$date=date("Y-m-d H:i:s");
		$GLOBALS[$mac]["IP"]=$ipaddr;
		$GLOBALS[$mac]["MACHINE_TYPE"]=$vendor;
		
		$MAIN[]="('$ipaddr','$mac','$vendor','$date')";
	}
	
	if(!$insert){return $MAIN;}
	if(count($MAIN)==0){return;}
	
	
	while (list ($mac, $array) = each ($MAIN)){
		
	$cmp=new computers();
	$uid=$cmp->ComputerIDFromMAC($mac);
	$array["HOSTNAME"]=gethostbyname($array["IP"]);
	$ipaddr=$array["IP"];
	if(preg_match("#^[0-9\.]+$#", $array["HOSTNAME"])){$array["HOSTNAME"]=null;}
	
	
	if($uid<>null){
			if($GLOBALS["VERBOSE"]){echo "$mac = $uid\n";}
			$cmp=new computers($uid);
				
			$ldap_ipaddr=$cmp->ComputerIP;
			$ComputerRealName=$cmp->ComputerRealName;
			if($GLOBALS["VERBOSE"]){echo "$mac = $uid\nLDAP:$ldap_ipaddr<>NMAP:$ipaddr\nLDAP CMP:$ComputerRealName<>NMAP:{$array["HOSTNAME"]}";}
			
			if($array["HOSTNAME"]<>null){
				$EXPECTED_UID=strtoupper($array["HOSTNAME"])."$";
				if($EXPECTED_UID<>$uid){
					$RAISON[]="UID: $uid is different from $EXPECTED_UID";
					nmap_logs("EDIT UID: $mac:[{$array["HOSTNAME"]}] ($ipaddr)",@implode("\n", $array)."\n".@implode("\n", $RAISON),$uid);
					$cmp->update_uid($EXPECTED_UID);
				}
			}
			
			
			if($ldap_ipaddr<>$ipaddr){
				writelogs("Change $ldap_ipaddr -> to $ipaddr for  $cmp->uid",__FUNCTION__,__FILE__,__LINE__);
				$RAISON[]="LDAP IP ADDR: $ldap_ipaddr is different from $ipaddr";
				$RAISON[]="DN: $cmp->dn";
				$RAISON[]="UID: $cmp->uid";
				$RAISON[]="MAC: $cmp->ComputerMacAddress";
				if(!$cmp->update_ipaddr($ipaddr)){$RAISON[]="ERROR:$cmp->ldap_last_error";}
				nmap_logs("EDIT IP: $mac:[{$array["HOSTNAME"]}] ($ipaddr)",@implode("\n", $array)."\n".@implode("\n", $RAISON),$uid);
		
			}
	
				
			continue;		
				
			}
			
		if($array["HOSTNAME"]<>null){$uid="{$array["HOSTNAME"]}$";}else{continue;}
		
		
		nmap_logs("ADD NEW: $mac:[{$array["HOSTNAME"]}] ($ipaddr)",@implode("\n", $array)."\n".@implode("\n", $RAISON),"$uid");
		$cmp=new computers();
		$cmp->ComputerIP=$ipaddr;
		$cmp->ComputerMacAddress=$mac;
		$cmp->uid="$uid";
		$cmp->ComputerRunning=1;
		$cmp->ComputerMachineType=$array["MACHINE_TYPE"];
		$cmp->Add();
			
	}
	
	
	
}


function nmap_scan_pingnet(){
	nmap_scan_pingnet_progress("{ping_networks}",5);
	$unix=new unix();
	$sock=new sockets();
	$nmap=$unix->find_program("nmap");
	$nohup=$unix->find_program("nohup");
	$NmapTimeOutPing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NmapTimeOutPing"));
	$NmapFastScan=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NmapFastScan"));
	if($NmapTimeOutPing==0){$NmapTimeOutPing=60;}
	$MaxTime=10;
	$net=new networkscanner();


    foreach ($net->networklist as $num=>$maks){if(trim($maks)==null){continue;}$hash[$maks]=$maks;}
    foreach ($hash as $num=>$maks){if(!$net->Networks_disabled[$maks]){if($GLOBALS["VERBOSE"]){echo "Network: $maks OK\n";}$cdir[]=$maks;}}
	if(count($cdir)==0){nmap_scan_pingnet_progress("No network",110);return;}
	$nets=trim(@implode(" ", $cdir));
	nmap_scan_pingnet_progress("Scanning Networks $nets",10);
	echo "Scanning Networks $nets\n";
	$TMP=$unix->FILE_TEMP();
	$NmapTimeOutPing++;
	$prc=10;
	
	
	nmap_scan_pingnet_progress("{fast_scan}: $NmapFastScan",6);
	$IP=new IP();
	
	if(is_file("/home/artica/SQLITE/nmapping.db")){@unlink("/home/artica/SQLITE/nmapping.db");}
	$q=new lib_sqlite("/home/artica/SQLITE/nmapping.db");
	@chown("/home/artica/SQLITE/nmapping.db", "www-data");
	$sql="CREATE TABLE IF NOT EXISTS `nmapping` (
			`network` VARCHAR( 128 ) NOT NULL ,
			`ipaddr` VARCHAR( 40 ) PRIMARY KEY,
			`hostname` VARCHAR( 128 ) NULL ,
			`mac` VARCHAR( 90 ) NULL ,
			`vendor` VARCHAR( 128 ) NULL )";
	$q->QUERY_SQL($sql);
	$FPING_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FPING_INSTALLED"));
	
	while (list ($num, $cd) = each ($cdir)){
		$prc=$prc+5;
		if($prc>99){$prc=99;}
		nmap_scan_pingnet_progress("Scanning Network $cd",$prc);
		$CONTINUE=true;
		$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
		$ligne=$q->mysqli_fetch_array("SELECT netinfos,scannable,pingable FROM networks_infos WHERE ipaddr='$cd'");
	
		if($ligne["pingable"]==1){
			nmap_scan_pingnet_progress("{fast_scan}:  - ping -",$prc);
			scan_fping_network($cd,$prc);
		}
		
		if($ligne["scannable"]==0){
			nmap_scan_pingnet_progress("Scannable:  None",$prc);
			continue;
		}
		

		
		if($NmapFastScan==1){
			nmap_scan_pingnet_progress("$cd -> arp-scan",$prc);
			$f1=arp_scanner($cdir);
			if(count($f1)>0){
				while (list ($num, $line) = each ($f1)){$f[]=$line;}
				nmap_scan_pingnet_progress("Continue --> FALSE",$prc);
				$CONTINUE=false;
			}
		}
		
		if(!class_exists("SimpleXMLElement")){
			nmap_scan_pingnet_progress("Missing:  php-xml",$prc);
			continue;
		}
		
		
		
		if($CONTINUE){
			nmap_scan_pingnet_progress("{launch} nmap scanner",$prc);
			echo "$nmap -T4 -sP -oX $TMP $cd\n";
			$ssql=array();
			system("$nohup $nmap -T4 -sP -oX $TMP $cd >/dev/null 2>&1 &");
			
			for($i=1;$i<$NmapTimeOutPing;$i++){
				$prc++;
				if($prc>70){$prc=70;}
				
				$pid=$unix->PIDOF("$nmap");
				nmap_scan_pingnet_progress("$cd Waiting scanner PID $pid $i/$NmapTimeOutPing",$prc);
				if(!$unix->process_exists($pid)){break;}
				echo "Waiting scanner PID $pid $i/$NmapTimeOutPing\n";
				sleep(1);
				
			}
			$pid=$unix->PIDOF("$nmap");
			if($unix->process_exists($pid)){
				$prc++;
				if($prc>70){$prc=70;}
				echo "Timed-Out scanner PID $pid\n";
				nmap_scan_pingnet_progress("$cd Timed Out!!",$prc);
				sleep(3);
				$unix->KILL_PROCESS($pid,9);
				continue;
			}
			
			
			$date=date("Y-m-d H:i:s");
			$xmlstr=@file_get_contents($TMP);
			@unlink($TMP);
			nmap_scan_pingnet_progress("$cd open $TMP",$prc++);
			$XMLZ = new SimpleXMLElement($xmlstr);
			
			foreach ($XMLZ->host as $Hostz) {
				if($prc>70){$prc=70;}
				$hostname=$Hostz->hostnames->hostname["name"][0];
				$ipaddr=mysql_escape_string2($Hostz->address[0]["addr"][0]);
				$mac=mysql_escape_string2($Hostz->address[1]["addr"][0]);
				nmap_scan_pingnet_progress("$cd $hostname $ipaddr",$prc++);
				
				$vendor=mysql_escape_string2($Hostz->address[1]["vendor"][0]);
				$ssql[]="('$cd','$ipaddr','$hostname','$mac','$vendor')";
				echo "('$cd','$ipaddr','$hostname','$mac','$vendor')\n";
				
				if(!$IP->isValid($ipaddr)){continue;}
				if(!$IP->IsvalidMAC($mac)){continue;}
				$hosts=new hosts($mac);
				$hosts->ipaddr=$ipaddr;
				if($hosts->fullhostname==null){$hosts->fullhostname=$hostname;}
				$hosts->Save();
				
			}
		
		}
		
		if(count($ssql)>0){
			
			$q=new lib_sqlite("/home/artica/SQLITE/nmapping.db");
			$sql="INSERT OR IGNORE INTO nmapping (`network`,`ipaddr`,`hostname`,`mac`,`vendor`) VALUES ". @implode(",", $ssql);
			if($GLOBALS["VERBOSE"]){echo "************** \n $sql \n ***********************\n";}
			
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error." in line ".__LINE__."\n";}
			$ssql=array();
			
		}
		
	}

	
	$prc=$prc+5;
	if($prc>99){$prc=99;}
	nmap_scan_pingnet_progress("{importing_hosts}",$prc);
	nmap_scan_pingnet_progress("{done}",100);
}




function nmap_scan_results(){
	if(!is_file("/etc/artica-postfix/nmap.map")){return;}
	$f=explode("\n", @file_get_contents("/etc/artica-postfix/nmap.map"));
	$ipaddr=null;
	$computer=array();
	foreach ($f as $index=>$ligne){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if($ligne=="PORT  STATE  SERVICE"){continue;}
		if(strpos("    $ligne", "Network Distance:")>0){continue;}
		if(strpos("    $ligne", "tcp closed tcpmux")>0){continue;}
		if(strpos("    $ligne", "Too many fingerprints match")>0){continue;}
		if(strpos("    $ligne", "OS detection performed. Please report")>0){continue;}
		if(strpos("    $ligne", "OSScan results may be unreliable")>0){continue;}
		if(strpos("    $ligne", "/tcp filtered")>0){continue;}
		
		
		if(preg_match("#Nmap scan report for\s+(.+?)\s+\(([0-9\.]+)#", $ligne,$re)){
			$ipaddr=$re[2];
			$computer[$ipaddr]["IPADDR"]=$re[2];
			$computer[$ipaddr]["HOSTNAME"]=trim($re[1]);
			if($GLOBALS["VERBOSE"]){echo "Found IP:$ipaddr hostname=`{$re[1]}` in `$ligne`\n";}
			$LOGS[]="Found $ipaddr hostname= {$re[1]}";
			continue;
		}
		
		if(preg_match("#Interesting ports on (.*?)\s+\(([0-9\.]+)\)#", $ligne,$re)){
			$ipaddr=$re[2];
			$computer[$ipaddr]["IPADDR"]=$re[2];
			$computer[$ipaddr]["HOSTNAME"]=trim($re[1]);
			if($GLOBALS["VERBOSE"]){echo "Found IP:$ipaddr hostname=`{$re[1]}` in `$ligne`\n";}
			$LOGS[]="Found $ipaddr hostname= {$re[1]}";
			continue;
		}
		
		if(preg_match("#Interesting ports on ([0-9\.]+):#", $ligne,$re)){
			$ipaddr=$re[1];
			$computer[$ipaddr]["IPADDR"]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "Found IP:$ipaddr only in `$ligne`\n";}
			$LOGS[]="Found $ipaddr only";
			continue;
		}
		
		
		if(preg_match("#Nmap scan report for ([0-9\.]+)$#", trim($ligne),$re)){
			$ipaddr=$re[1];
			$computer[$ipaddr]["IPADDR"]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "[$ipaddr]: Found IP address `$ipaddr` without computername in `$ligne`\n";}
			$LOGS[]="Found $ipaddr without computername ";
			continue;
		}
		
		if(preg_match("#^MAC Address:\s+([0-9A-Z:]+)$#",trim($ligne),$re)){
			if(trim($ipaddr)==null){continue;}
			if(isset($MACSSCAN[trim($re[1])])){continue;}
			$computer[$ipaddr]["MAC"]=trim($re[1]);
			$LOGS[]="Found $ipaddr with mac {$re[1]} ";
			if($GLOBALS["VERBOSE"]){echo "[$ipaddr]: Found mac {$re[1]} in `$ligne`\n";}
			$MACSSCAN[trim($re[1])]=true;
			continue;
		}
		
		if(preg_match("#^MAC Address:(.+).+?\((.+?)\)#",$ligne,$re)){
			if(trim($ipaddr)==null){continue;}
			if(isset($MACSSCAN[trim($re[1])])){continue;}
			$MACSSCAN[trim($re[1])]=true;
			$computer[$ipaddr]["MAC"]=trim($re[1]);
			$computer[$ipaddr]["MACHINE_TYPE"]=trim($re[2]);
			if($GLOBALS["VERBOSE"]){echo "[$ipaddr]: Found mac {$re[1]} and machine type {$re[2]} in `$ligne`\n";}
			$LOGS[]="Found $ipaddr with mac {$re[1]} and machine type {$re[2]}";
			continue;
		}

		if(preg_match("#^Running:(.+)#",$ligne,$re)){
			if(trim($ipaddr)==null){continue;}
			if($GLOBALS["VERBOSE"]){echo "Found running in `$line`\n";}
			$computer[$ipaddr]["RUNNING"]=trim($re[1]);
			continue;
		}
		
		if(preg_match("#^OS details:(.+)#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo "[$ipaddr]: Found OS {$re[1]} in `$ligne`\n";}
			$LOGS[]="Found $ipaddr with OS {$re[1]}";
			$computer[$ipaddr]["OS"]=trim($re[1]);
			continue;
		}	

		if($GLOBALS["VERBOSE"]){echo "[$ipaddr]: Not understood in `$ligne`\n";}
		
		
	}
	nmap_logs(count($f). " analyzed lines",@implode("\n", $LOGS));
	
	
	$c=0;

	
	$prefix_sql="INSERT IGNORE INTO computers_lastscan (`MAC`, `zDate`,`ipaddr`,`hostname`,`Info`) VALUES ";
	
	while (list ($ipaddr, $array) = each ($computer) ){
		if(!isset($array["MAC"])){continue;}
		$mac=trim($array["MAC"]);
		if(isset($already[$mac])){continue;}
		if($mac==null){continue;}
		$c++;
		$already[$mac]=true;
		
		$ldap_ipaddr=null;
		$ComputerRealName=null;
		$uid=null;
		$RAISON=array();
		if(!isset($array["HOSTNAME"])){$array["HOSTNAME"]=null;}
		if(!isset($array["OS"])){$array["OS"]=null;}
		if(!isset($array["RUNNING"])){$array["RUNNING"]=null;}
		if(!isset($array["MACHINE_TYPE"])){$array["MACHINE_TYPE"]=null;}
		$date=date('Y-m-d H:i:s');
		
		$infos=addslashes($array["OS"]. " Type:{$array["MACHINE_TYPE"]} ");
		
		$SQLAD[]="('$mac','$date','$ipaddr','{$array["HOSTNAME"]}','$infos')";
	
		$cmp=new computers(null);
		$uid=$cmp->ComputerIDFromMAC($mac);
		if($uid<>null){
			if($GLOBALS["VERBOSE"]){echo "$mac = $uid\n";}
			$cmp=new computers($uid);
			
			$ldap_ipaddr=$cmp->ComputerIP;
			$ComputerRealName=$cmp->ComputerRealName;
			if($GLOBALS["VERBOSE"]){echo "$mac = $uid\nLDAP:$ldap_ipaddr<>NMAP:$ipaddr\nLDAP CMP:$ComputerRealName<>NMAP:{$array["HOSTNAME"]}";}
			if($array["HOSTNAME"]<>null){
				$EXPECTED_UID=strtoupper($array["HOSTNAME"])."$";
				if($EXPECTED_UID<>$uid){
					$RAISON[]="UID: $uid is different from $EXPECTED_UID";
					nmap_logs("EDIT UID: $mac:[{$array["HOSTNAME"]}] ($ipaddr)",@implode("\n", $array)."\n".@implode("\n", $RAISON),$uid);
					$cmp->update_uid($EXPECTED_UID);
				}
			}
			if($ldap_ipaddr<>$ipaddr){
				writelogs("Change $ldap_ipaddr -> to $ipaddr for  $cmp->uid",__FUNCTION__,__FILE__,__LINE__);
				$RAISON[]="LDAP IP ADDR: $ldap_ipaddr is different from $ipaddr";
				$RAISON[]="DN: $cmp->dn";
				$RAISON[]="UID: $cmp->uid";
				$RAISON[]="MAC: $cmp->ComputerMacAddress";
				if(!$cmp->update_ipaddr($ipaddr)){$RAISON[]="ERROR:$cmp->ldap_last_error";}
				nmap_logs("EDIT IP: $mac:[{$array["HOSTNAME"]}] ($ipaddr)",@implode("\n", $array)."\n".@implode("\n", $RAISON),$uid);
				
			}
			if($array["OS"]<>null){
				if(strtolower($cmp->ComputerOS=="Unknown")){$cmp->ComputerOS=null;}
				if($cmp->ComputerOS==null){
					$RAISON[]="LDAP OS: $cmp->ComputerOS is different from {$array["OS"]}";
					nmap_logs("EDIT OS: $mac:[{$array["HOSTNAME"]}] ($ipaddr)",@implode("\n", $array)."\n".@implode("\n", $RAISON),$uid);
					$cmp->update_OS($array["OS"]);
				}
			}
			
			
			
		}else{
			if($array["HOSTNAME"]<>null){$uid="{$array["HOSTNAME"]}$";}else{continue;}
			nmap_logs("ADD NEW: $mac:[{$array["HOSTNAME"]}] ($ipaddr)",@implode("\n", $array)."\n".@implode("\n", $RAISON),"$uid");
			$cmp=new computers();
			$cmp->ComputerIP=$ipaddr;
			$cmp->ComputerMacAddress=$mac;
			$cmp->uid="$uid";
			$cmp->ComputerOS=$array["OS"];
			$cmp->ComputerRunning=$array["RUNNING"];
			$cmp->ComputerMachineType=$array["MACHINE_TYPE"];
			$cmp->Add();
		}
		
		
		
		
		
	}
	
	if($GLOBALS["VERBOSE"]){echo "*** ".count($SQLAD). " MYsql queries...***\n";}
	squid_admin_mysql(2, "$c hosts analyzed in networks",__FUNCTION__,__FILE__,__LINE__,"nmap");
	nmap_logs("$c hosts analyzed in networks",@file_get_contents("/etc/artica-postfix/nmap.map"),null);
	if(count($SQLAD)>0){
		
		$q=new mysql();
		$q->QUERY_SQL("DROP TABLE computers_lastscan","artica_backup");
		$q->check_storage_table(true);
		$final=$prefix_sql.@implode(",", $SQLAD);
		if($GLOBALS["VERBOSE"]){echo "*** $final ***\n";}
		$q->QUERY_SQL($prefix_sql.@implode(",", $SQLAD),"artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	@unlink("/etc/artica-postfix/nmap.map");
	//print_r($computer);
	
}



function nmap_logs($subject,$text,$uid=null){
	$subject=addslashes($subject);
	$text=addslashes($text);
	if($GLOBALS["VERBOSE"]){echo $subject."\n";}
	$sql="INSERT INTO nmap_events (subject,text,uid) VALUES ('$subject','$text','$uid');";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_events");
}
function nmap_scan_period(){
	if(system_is_overloaded(basename(__FILE__))){
		
		writelogs("{OVERLOADED_SYSTEM}, aborting",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/exec.nmapscan.php.nmap_scan_period.pid";
	$pidtime="/etc/artica-postfix/pids/exec.nmapscan.php.nmap_scan_period.time";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){exit();}
	
	@unlink($pidfile);
	@file_put_contents($pidfile, getmypid());
	
	$sock=new sockets();
	$EnableScanComputersNet=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableScanComputersNet"));
	
	if($EnableScanComputersNet==0){exit();}
	
	
	$EnableScanComputersNetSchedule=$sock->GET_INFO("EnableScanComputersNetSchedule");
	if(!is_numeric($EnableScanComputersNetSchedule)){$EnableScanComputersNetSchedule=15;}
	if($EnableScanComputersNetSchedule<5){$EnableScanComputersNetSchedule=5;}	
	
	$time=$unix->file_time_min($pidtime);
	if($time<$EnableScanComputersNetSchedule){exit();}
	@unlink($pidtime);@file_put_contents($pidtime, time());
	
	
	$sql="SELECT MACADDR,IPADDRESS FROM networks";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"ocsweb");
	$computer=new computers();
	if(!$q->ok){if(preg_match("#Unknown database#", $q->mysql_error)){$sock=new sockets();$sock->getFrameWork("services.php?mysql-ocs=yes");$results=$q->QUERY_SQL($sql,"ocsweb");}return;}
	if(!$q->FIELD_EXISTS("networks", "isActive", "ocsweb")){$q->QUERY_SQL("ALTER TABLE `networks` ADD `isActive` SMALLINT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( `isActive` ) ","ocsweb");}
	$users=new usersMenus();
	if(!is_file("$users->NMAP_PATH")){return null;}
	
	$cmp=new computers();
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$MACADDR=$ligne["MACADDR"];
		$IPADDRESS=$ligne["IPADDRESS"];
		$cmd=$users->NMAP_PATH." -v -F -PE -Pn -O $IPADDRESS  --system-dns --version-light 2>&1";
		$resultsScan=array();
		exec($cmd,$resultsScan);
		$PORTS=array();
		$osDetails=null;
		$uid=null;
		$UpTime=null;
		$LIVE=false;
		$MACSSCAN=null;
		while (list ($index, $line) = each ($resultsScan) ){
			if(preg_match("#Nmap scan report for.+?host down#", $line)){
				if($GLOBALS["VERBOSE"]){echo "$MACADDR ($IPADDRESS) DOWN\n";}
				nmap_scan_period_save($IPADDRESS,$MACADDR,0);
				break;
			}
			
			
			if(preg_match("#([0-9]+).+?open\s+(.+)#",$line,$re)){
				$PORTS[$re[1]]=$re[2];
				continue;
			}

			if(preg_match("#^OS details:(.+)#",$line,$re)){				
				$osDetails=trim($re[1]);
				if(preg_match("#Microsoft.+?Windows.+?7#i",$osDetails)){$osDetails="Windows 7";}	
				continue;
			}

			if(preg_match("#^Uptime guess:\s+(.+)#",$line,$re)){
				$UpTime=$re[1];
				continue;
			}
			
			if(preg_match("#^MAC Address:\s+([0-9A-Z:]+)$#",trim($line),$re)){
				$MACSSCAN=trim(strtolower($re[1]));
				continue;
			}
		
			if(preg_match("#^MAC Address:(.+).+?\((.+?)\)#",$line,$re)){
				$MACSSCAN=trim(strtolower($re[1]));
				continue;
			}			
			
			
		}
		
		
		
		if(count($PORTS)>0){
			AddPorts($PORTS, $MACADDR);
			if(is_array($PORTS)){
				$uid=$cmp->ComputerIDFromMAC($MACADDR);
				$cmp=new computers($uid);
				$portser=serialize($PORTS);
				$cmp->UpdateComputerOpenPorts(base64_encode($portser));
				$PORTS=array();
				$LIVE=true;
			}
			
		}
		
		if($MACADDR=="unknown"){if($MACSSCAN<>null){$MACADDR=$MACSSCAN;}}
		
		if($osDetails<>null){if($uid==null){$uid=$cmp->ComputerIDFromMAC($MACADDR);$cmp=new computers($uid);}if($cmp->ComputerOS<>$osDetails){$cmp->update_OS($osDetails);}$LIVE=true;}
		if($UpTime<>null){if($uid==null){$uid=$cmp->ComputerIDFromMAC($MACADDR);$cmp=new computers($uid);}$cmp->UpdateComputerUpTime($UpTime);$LIVE=true;}
		if($LIVE){
			if($GLOBALS["VERBOSE"]){echo "$IPADDRESS/$MACADDR ".count($PORTS)." ports ($osDetails) TTL:$UpTime\n";}
			nmap_scan_period_save($IPADDRESS,$MACADDR,1);$LIVE=false;continue;
		}
		if($GLOBALS["VERBOSE"]){echo "$IPADDRESS/$MACADDR DOWN\n";}
	
	}	
}

function nmap_scan_period_save($ipaddr,$mac,$status){
	$date=date('Y-m-d H:i:s');
	$q=new mysql();
	if($status==1){
		$sql="INSERT IGNORE INTO computers_available (zDate,ipaddr,MAC,live) VALUES ('$date','$ipaddr','$mac','$status')";
		$q->QUERY_SQL($sql,"artica_events");
	}
	$sql="UPDATE networks SET isActive='$status' WHERE MACADDR='$mac'";
	$q->QUERY_SQL($sql,"ocsweb");
	
}

function nmap_scan_single($mac,$ipaddrZ=null){
	$unix=new unix();
	$users=new usersMenus();
	if(!is_file($users->NMAP_PATH)){ build_progress("{operation_failed} err.".__LINE__,110); return;}
	if($mac=="00:00:00:00:00:00"){$mac=null;}
	$mac=trim(strtolower($mac));
	@unlink("/usr/share/artica-postfix/ressources/logs/web/nmap_single_progress.results");
	
	if($mac==null){
		if($ipaddrZ==null){
			build_progress("{operation_failed} err.",110); 
			return;
		}
	}
	
	build_progress("Determine IP addresses",5);
	
	if($ipaddrZ=="0.0.0.0"){$ipaddrZ=null;}
	
	if($ipaddrZ<>null){
		$ipaddr[$ipaddrZ]=true;
	}
	
	if($mac<>null){
		$computer =new hosts($mac);
		if($computer->ipaddr<>"0.0.0.0"){$ipaddr[$computer->ipaddr]=true;}
	}
		
	
	if(count($ipaddr)==0){
		build_progress("{operation_failed} no ip found err.".__LINE__,110);
		return;
	}
	
	build_progress("Scanning ".count($ipaddr)." nodes",10);
	
	$i=10;
	$NICE=EXEC_NICE();
	while (list ($IPADDRESS, $line) = each ($ipaddr) ){
		$i=$i+5;
		echo "Scanning $IPADDRESS\n";
		build_progress("Scanning $IPADDRESS",$i);
		if(!$unix->PingHostCMD($IPADDRESS)){
			if(count($ipaddr)==1){
				echo "Ping: $IPADDRESS -> Failed\n";
				build_progress("{operation_failed} Ping $IPADDRESS failed".__LINE__,110);
				return;
			}
			continue;}
			
		echo "Ping: $IPADDRESS -> Success\n";
		$cmd=trim($NICE." ".$users->NMAP_PATH." -v -F -PE -Pn -O $IPADDRESS  --system-dns --version-light 2>&1");
		build_progress("Scanning $IPADDRESS done...",$i);
		$resultsScan=array();
		exec($cmd,$resultsScan);
		$tmpfile=$unix->TEMP_DIR()."/nmap.$IPADDRESS.log";
		@file_put_contents($tmpfile, @implode("\n", $resultsScan));
		
		if(count($ipaddr)==1){
			@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/nmap_single_progress.results",@implode("\n", $resultsScan));
		}
		
		
		echo @implode("\n", $resultsScan);
		$array=ExecArrayToArray($resultsScan);
		if($GLOBALS["VERBOSE"]){echo "\nParsing ". count($array). " items in sarray\n";}
		
		if(!is_array($array)){continue;}
		if($array["MAC"]<>$mac){
			if($GLOBALS["VERBOSE"]){echo "{$array["MAC"]} <> $mac !!!\n";}
			continue;}
			
		if($GLOBALS["VERBOSE"]){echo " * * * * *  * * * *\n";}	
		build_progress("$mac:-> $IPADDRESS OK",$i+5);
		echo "$mac:-> $IPADDRESS OK\n";
		$data=base64_encode(serialize($array));
		
		$hosts=new hosts($mac);
		$hosts->scanreport=$data;
		$hosts->Save();
		build_progress("Analyze scan...",$i+5);
		if($GLOBALS["VERBOSE"]){echo "Parsing $tmpfile\n";}
		parsefile($tmpfile,null,$i);
		
		
	}
	build_progress("Done...",100);
	
		
	
}
function build_progress($text,$pourc){
	$cachefile="/usr/share/artica-postfix/ressources/logs/nmap.single.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	
}



function nmap_scan_squid_mac($mac){
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT ipaddr,MAC FROM UserAutDB GROUP BY ipaddr,MAC HAVING MAC='$mac' AND LENGTH(ipaddr)>0");
	if(!$q->ok){echo $q->mysql_error;return;}
	$count=mysqli_num_rows($results);
	if($count==0){return;}
	$unix=new unix();
	$users=new usersMenus();
	$NICE=EXEC_NICE();
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){	
		
		$IPADDRESS=$ligne["ipaddr"];
		if(!$unix->PingHostCMD($IPADDRESS)){continue;}
		
		
		$cmd=trim($NICE." ".$users->NMAP_PATH." -v -F -PE -Pn -O $IPADDRESS  --system-dns --version-light 2>&1");
		$resultsScan=array();
		exec($cmd,$resultsScan);
		$array=ExecArrayToArray($resultsScan);
		if(!is_array($array)){continue;}
		if($array["MAC"]<>$mac){continue;}
		
		$hosts=new hosts($mac);
		echo "$mac:-> $IPADDRESS OK\n";
		$data=base64_encode(serialize($array));
		$hosts->scanreport=$data;
		$hosts->Save();
	}
	
}

function ExecArrayToArray($array){
	$osDetails=null;
	$UpTime=null;
	$MACSSCAN=null;
	$PORTS=array();
	if(count($array)<2){return;}
	foreach ($array as $index=>$line){
	
			if(preg_match("#Nmap scan report for.+?host down#", $line)){if($GLOBALS["VERBOSE"]){echo "DOWN\n";}return null;}
			
			
			if(preg_match("#([0-9]+)\/(tcp|udp).+?(open|filtered)\s+(.+)#",$line,$re)){
				$PORTS[$re[1]]=$re[4];
				continue;
			}

			if(preg_match("#^OS details:(.+)#",$line,$re)){				
				$osDetails=trim($re[1]);
				if(preg_match("#Microsoft.+?Windows.+?7#i",$osDetails)){$osDetails="Windows 7";}	
				continue;
			}

			if(preg_match("#^Uptime guess:\s+(.+)#",$line,$re)){
				$UpTime=$re[1];
				continue;
			}
			
			if(preg_match("#^MAC Address:\s+([0-9A-Z:]+)$#",trim($line),$re)){
				$MACSSCAN=trim(strtolower($re[1]));
				continue;
			}
		
			if(preg_match("#^MAC Address:(.+).+?\((.+?)\)#",$line,$re)){
				$MACSSCAN=trim(strtolower($re[1]));
				continue;
			}

			if(preg_match("#OS.+?i686-pc-linux-gnu#",$line,$re)){
				if($osDetails==null){$osDetails="Linux i686";}
			}
			
				
	}
	
	
	$array=array(
		"OS"=>$osDetails,"MAC"=>$MACSSCAN,"UPTIME"=>$UpTime,"PORTS"=>$PORTS);
	
	if(count($PORTS)>0){AddPorts($PORTS,$MACSSCAN);}
	
	
	return $array;
	

}

function scan_fping_network($network,$prc=0){
	$unix=new unix();
	$network=trim($network);
	if($network==null){return;}
	$fping=$unix->find_program("fping");
	$pid=$unix->PIDOF($fping);
	if($unix->process_exists($pid)){
		echo "Process already exists pid $pid\n";
		squid_admin_mysql(1,"Network scanner (ping): $network Scan aborted ($pid already exists)", null,__FILE__, __LINE__);
		return;}
		
	if(!is_dir("/etc/artica-postfix/cron.ping")){@mkdir("/etc/artica-postfix/cron.ping",0755,true);}	
	$filetime="/etc/artica-postfix/cron.ping/".md5($network);
	@unlink($filetime);
	@file_put_contents($filetime, time());
	
	
	$tempfile=$unix->FILE_TEMP();
	shell_exec("$fping -d -A -r 1 -i 10 -g $network >$tempfile 2>&1");
	
	
	$prefix="INSERT OR IGNORE INTO fping (network,hton,ipaddr,hostname,available) VALUES ";

	$t=time();
	$un=0;
	$av=0;
	$n=array();
	$f=explode("\n", @file_get_contents($tempfile));
	foreach ($f as $line){
		$line=trim($line);
		
		if(preg_match("#^([0-9\.]+)\s+\((.+?)\)\s+is\s+unreachable#", $line,$re)){
			$ipaddr=$re[1];
			$hton=ip2long($ipaddr);
			$hostname=$re[2];
			$available=0;
			
			$n[]="('$network','$hton','$ipaddr','$hostname','$available')";
			$un++;
			
		}
		if(preg_match("#^([0-9\.]+)\s+\((.+?)\)\s+is\s+alive#", $line,$re)){
			$ipaddr=$re[1];
			$hton=ip2long($ipaddr);
			$hostname=$re[2];
			$available=1;
			$n[]="('$network','$hton','$ipaddr','$hostname','$available')";
			$av++;	
		}		
	}
	
	
	
	squid_admin_mysql(2, "Success scan $network unavailable $un, available $av", null,__FILE__,__LINE__);
	
	$q=new lib_sqlite("/home/artica/SQLITE/fping.db");
	$q->QUERY_SQL("DELETE FROM fping WHERE network='$network'");
	if(count($n)>0){
		$q->QUERY_SQL($prefix." ".@implode(",", $n));
		if(!$q->ok){
			echo $q->mysql_error."\n";
			if($prc>0){nmap_scan_pingnet_progress("{fast_scan}: MySQL error!",$prc);}
			sleep(3);
			squid_admin_mysql(0, "MySQL error!", $q->mysql_error,__FILE__,__LINE__);
		}
	}
	


	
	$tot=$un+$av;
	$prc1=$av/$tot;
	$prc1=$prc1*100;
	$prc1=round($prc1,2);
	if($prc>0){nmap_scan_pingnet_progress("{fast_scan}:  $network unavailable $un, available $av ($prc1%)",$prc);}
	
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$q->QUERY_SQL("UPDATE networks_infos SET noping=$un,yesping=$av,prcping='$prc1' WHERE ipaddr='$network'");
	
	
	
	
}

function AddPorts($ports,$mac){
	$q=new mysql();
	
	$sql="CREATE TABLE IF NOT EXISTS `open_ports` (
			
			`mac` varchar(60) NOT NULL,
			`port` INT(100),
			`service` VARCHAR(40),
			KEY `service` (`service`),
			KEY `port` (`port`)
			
			)  ENGINE = MYISAM;";
	
	
	$q->QUERY_SQL($sql,"ocsweb");
	if(!$q->ok){echo "*********************\n".$q->mysql_error."\n*************************************\n";}
	
	$q->QUERY_SQL("DELETE FROM open_ports WHERE `mac`='$mac'","ocsweb");
	
	$f=array();
	while (list ($port, $service) = each ($ports) ){
		$f[]="('$port','$service','$mac')";
		
	}
	
	if(count($f)>0){
		$sql="INSERT INTO open_ports (`port`,`service`,`mac`) VALUES ".@implode(",", $f);
		if($GLOBALS["VERBOSE"]){echo $sql."\n";}
		$q->QUERY_SQL($sql,"ocsweb");
		
	}
}
	
	
?>