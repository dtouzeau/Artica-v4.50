<?php
$GLOBALS["VERBOSE"]=false;
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
	include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
	include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
	include_once(dirname(__FILE__).'/ressources/class.computers.inc');
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	include_once(dirname(__FILE__).'/ressources/class.mount.inc');
	include_once(dirname(__FILE__).'/framework/frame.class.inc');
	include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');

if(is_file("/etc/artica-postfix/AS_KIMSUFFI")){echo "AS_KIMSUFFI!\n";exit();}	
if(preg_match("#--verbose#",implode(' ',$argv))){$GLOBALS["VERBOSE"]=true;}
if($argv[1]=="--schedules"){set_computer_schedules();exit;}
if($argv[1]=="--import-list"){importcomputersFromList();exit;}		
$computer=$argv[1];


LaunchScan($computer);

function LaunchScan($host){
	$debug=$_GET["D"];
	if(strpos($host,'$')==0){$host=$host.'$';}
	events("LaunchScan(): Scanning $host");
	
	$computer=new computers($host);
	$ini=new Bs_IniHandler();
	$ini->loadString($computer->KasperkyAVScanningDatas);
	$commandline=BuildOptionCommandLine($ini,$computer->ComputerRealName);
	
	$ini=new Bs_IniHandler();
	$ini->loadString($computer->ComputerCryptedInfos);
	$username=$ini->_params["ACCOUNT"]["USERNAME"];
	$password=$ini->_params["ACCOUNT"]["PASSWORD"];
	$ping=new ping($computer->ComputerIP);
	if(!$ping->Isping()){
		events("LaunchScan(): unable to ping computer...");
		return false;
	}
	events("LaunchScan(): to ping computer OK...");
	
	if(smbmount($computer->ComputerIP,$username,$password,$commandline)){
		
	}else{
	events("LaunchScan(): unable to mount $computer->ComputerIP");
	}
	
}

function build_progress($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/ocs.import.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function arp_scan_IpToMac($ipaddr){
	if(!is_file("/usr/bin/arp-scan")){return null;}
	$ipaddr_regex=str_replace(".", "\.", $ipaddr);
	exec("/usr/bin/arp-scan --quiet --retry=1 $ipaddr 2>&1",$results);
	foreach ($results as $num=>$line){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#$ipaddr_regex\s+(.+)#", $line,$re)){
			return trim($re[1]);
		}
		
	}
	
	
}


function importcomputersFromList(){
	$unix=new unix();
	
	if(!is_file("/usr/bin/arp-scan")){
		$unix->DEBIAN_INSTALL_PACKAGE("arp-scan");
		
	}
	
	
	
	
	$sock=new sockets();
	$ipClass=new IP();
	$tbl=explode("\n",$sock->GET_INFO("ComputerListToImport"));
	
	$CountOfLines=count($tbl);
	
	build_progress("$CountOfLines {computers}",10);
	
	echo "[".__LINE__."] $CountOfLines lines\n";
	
	
	$i=0;
	$z=0;
	$max=$CountOfLines;
	$FAILED=0;
	$SUCCESS=0;
	foreach ($tbl as $num=>$line){
		$z++;
		$prc=($z/$CountOfLines)*100;
		$prc=round($prc);
		if($prc<10){$prc=10;}
		if($prc>90){$prc=90;}
		
		$proxy_alias=null;
		$computername=null;
		$IPADDR=null;
		$MAC=null;
		//pc001,192.168.1.5,d8:9e:3f:34:2d:8d,jhon_pc[br]
		$EXPLODED=explode(",",$line);
		$computername=$EXPLODED[0];
		$IPADDR=$EXPLODED[1];
		$MAC=$EXPLODED[2];
		$MAC=str_replace("-", ":", $MAC);
		$MAC=strtolower($MAC);
		
		if(isset($EXPLODED[3])){$proxy_alias=trim(strtolower($EXPLODED[3]));}
		
		
		
		$computername=trim($computername);
		
		
		if($MAC==null){$MAC=arp_scan_IpToMac($IPADDR);}
		
		if($computername==null){
			echo "[".__LINE__."] Computer Name is null, aborting\n";
			$FAILED++;
			continue;
		}
		
	
		if(!$ipClass->isValid($IPADDR)){$IPADDR=null;}
		if(!$ipClass->IsvalidMAC($MAC)){$MAC=null;}
		build_progress("$computername $IPADDR/$MAC/$proxy_alias",$prc);
		
		$cmp=new computers();
		
		if($MAC<>null){$uid=$cmp->ComputerIDFromMAC($MAC);}else{$uid="$computername$";}
		if($uid==null){$uid="$computername$";}
		
		if($IPADDR==null){
			echo "Try to resolve $computername\n";
			$IPADDR=@gethostbyname($computername);
			if(!$ipClass->isValid($IPADDR)){$IPADDR=null;}
		}
		
		$cmp=new computers($uid);
		if($IPADDR<>null){$cmp->ComputerIP=$IPADDR;}
		if($MAC<>null){$cmp->ComputerMacAddress=$MAC;}
		$cmp->ComputerRealName=$computername;
		if(!$cmp->Add()){
			echo "$computername: $cmp->ldap_error\n";
			$FAILED++;}else{$SUCCESS++;}
		$i=$i+1;
	}
	
	echo "Success: $SUCCESS\n";
	echo "Failed : $FAILED\n";
	build_progress("$SUCCESS {added_computers}",95);
	sleep(10);
	build_progress("{done}",100);
	
}





function WriteCOmputerBrowseProgress($pourc,$text){
	$ini=new Bs_IniHandler();
	$ini->set('NMAP','pourc',$pourc);
	$ini->set('NMAP','text',$text);
	$ini->saveFile('/usr/share/artica-postfix/ressources/logs/nmap.progress.ini');
	@chmod("/usr/share/artica-postfix/ressources/logs/nmap.progress.ini",0755);
}



function smbmount($host,$username,$password,$cmdline){

	if($username==null){
		events("smbmount(): using no user and password for remote connection is not supported");
		return false;
	}
	@mkdir("/opt/artica/mount/$host");
	$mount_point="/opt/artica/mount/$host";
	$f=new mount("/var/log/artica-postfix/computer-scan.debug");
	
	if(!$f->ismounted($mount_point)){
		events("smbmount(): not mounted...");
		$password_hidden=preg_replace("#.*#","*",$password);
		events("smbmount(): using $username $password_hidden\n");
	
		if($username<>null){$options=" -o username=$username,password=$password";}
		$cmd="mount -t smbfs$options //$host/c$ /opt/artica/mount/$host";
		events("smbmount(): $cmd");
		exec($cmd);
		
	}
	if($f->ismounted($mount_point)){
			events("smbmount(): $cmdline $mount_point");
			system("$cmdline $mount_point &");
			return true;
		}	
	
}


function BuildOptionCommandLine($ini,$computername){
	$debug=$_GET["D"];
	@mkdir("/usr/share/artica-postfix/ressources/logs/manual-scan");
	@chmod("/usr/share/artica-postfix/ressources/logs/manual-scan",0755);
	$cure=$ini->_params["scanner.options"]["cure"];
	if($ini->_params["scanner.options"]["Packed"]==1){$packed='P';}else{$packed='p';}
	if($ini->_params["scanner.options"]["Archives"]==1){$Archives='A';}else{$Archives='a';}
	if($ini->_params["scanner.options"]["SelfExtArchives"]==1){$SelfExtArchives='S';}else{$SelfExtArchives='s';}	
	if($ini->_params["scanner.options"]["MailBases"]==1){$MailBases='B';}else{$MailBases='b';}
	if($ini->_params["scanner.options"]["MailPlain"]==1){$MailPlain='M';}else{$MailPlain='m';}
	if($ini->_params["scanner.options"]["Heuristic"]==1){$Heuristic='E';}else{$Heuristic='e';}
	if($ini->_params["scanner.options"]["Recursion"]==1){$Recursion='-R';}else{$Recursion='-r';}
	$cmd="/opt/kaspersky/kav4samba/bin/kav4samba-kavscanner -f -e $packed$Archives$SelfExtArchives$MailBases$MailPlain$Heuristic $Recursion $cure";
	$cmd=$cmd . " -q -o /usr/share/artica-postfix/ressources/logs/manual-scan/$computername.results.log";
	if($debug){echo "BuildOptionCommandLine():: \"$cmd\"\n";}
	
	return $cmd;
	}
	
	
function set_computer_schedules(){
	if(is_file("/etc/artica-postfix/KASPERSKY_WEB_APPLIANCE")){exit();}
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		writelogs("set_computer_schedules:: already $pid running, die",__FUNCTION__,__FILE__,__LINE__);
		exit();
	}
	
	writelogs("set_computer_schedules:: starting",__FUNCTION__,__FILE__,__LINE__);
	$ldap=new clladp();
	$pattern="(&(objectClass=ArticaComputerInfos)(ComputerScanSchedule=*))";
	$attr=array("cn","ComputerScanSchedule","uid");
	$sr =@ldap_search($ldap->ldap_connection,$ldap->suffix,$pattern,$attr);
	if(!$sr){
		events("set_computer_schedules():: $ldap->ldap_last_error line: ".__LINE__);
		return false;
	}
	
	$hash=ldap_get_entries($ldap->ldap_connection,$sr);

	for($i=0;$i<$hash["count"];$i++){
		$uid=$hash[$i]["uid"][0];
		$computerscanschedule=$hash[$i]["computerscanschedule"][0];
		$filename="$uid";
		$filename=str_replace('.','',$filename);
		$filename=str_replace('$','',$filename);
		$filename=str_replace(' ','',$filename);
		$filename=str_replace('-','',$filename);
		$filename=str_replace('_','',$filename);
		sys_CRON_CREATE_SCHEDULE($computerscanschedule,LOCATE_PHP5_BIN()." ".__FILE__." $uid","artica-av-$filename");
		}
}

function events($text){
		$pid=getmypid();
		$date=date("H:i:s");
		$logFile="/var/log/artica-postfix/computer-scan.debug";
		$size=filesize($logFile);
		if($size>1000000){unlink($logFile);}
		$f = @fopen($logFile, 'a');
		$line="[$pid] $date $text\n";
		if($_GET["DEBUG"]){echo $line;}
		@fwrite($f,$line);
		@fclose($f);
}

	

?>