<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["PROGRESS"]=false;
if(is_array($argv)){
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if(preg_match("#--no-reload#",implode(" ",$argv))){$GLOBALS["NORELOAD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;$GLOBALS["OUTPUT"]=true;}
	
	
}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.dhcpd.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.iptables-chains.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.nics.inc');
include_once(dirname(__FILE__) . '/ressources/class.resolv.conf.inc');
include_once(dirname(__FILE__) . '/ressources/class.baseunix.inc');
include_once(dirname(__FILE__) . '/ressources/class.munin.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");

$GLOBALS["ASROOT"]=true;
if(isset($argv[1])){
if($argv[1]=='--bind'){exit();}
	if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
	if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
	if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
	if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
	if($argv[1]=="--reload-if-run"){$GLOBALS["OUTPUT"]=true;reload_if_run();exit();}
	if($argv[1]=="--wizard"){$GLOBALS["OUTPUT"]=true;$GLOBALS["PROGRESS"]=true;wizard();exit();}
	if($argv[1]=="--uninstall"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}
	if($argv[1]=="--pdns"){$GLOBALS["OUTPUT"]=true;DHCPDInPowerDNS();exit();}

}




function build_progress_gb($text){
	$PROGRESS_FILE=PROGRESS_DIR."/wizard.progress";
	$array["POURC"]=26;
	$array["TEXT"]=$text;
	@file_put_contents($PROGRESS_FILE, serialize($array));
	@chmod($PROGRESS_FILE,0755);
}

function build_progress($text,$pourc){
	build_progress_gb($text);
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/dhcpd.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	if($GLOBALS["PROGRESS"]){sleep(1);}

}

function uninstall():bool{
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();

	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDHCPServer", 0);
    shell_exec("/usr/sbin/artica-phpfpm-service -uninstall-dhcpd");
	@unlink("/etc/monit/conf.d/APP_DHCPD.monitrc");
    UNIX_RESTART_CRON();

	if(is_file("/etc/init.d/snmpd")){
        shell_exec("/etc/init.d/snmpd restart");
    }



	build_progress("{disable_feature} {removing}",90);
	$q=new postgres_sql();
	$q->QUERY_SQL("TRUNCATE TABLE dhcpd_leases");
	$q->QUERY_SQL("TRUNCATE TABLE dhcpd_hosts");
	build_progress("{disable_feature} {removing}",95);
	if(is_file("/etc/init.d/munin-node")){system("$php /usr/share/artica-postfix/exec.munin.php --reconfigure");}
	
	build_progress("{disable_feature} {success}",100);
    return true;
}






function install_dhcpd(){
	$unix=new unix();

    shell_exec("/usr/sbin/artica-phpfpm-service -install-dhcpd");
	shell_exec("/etc/init.d/cron reload");

}

function mandatories_dirs(){
    $directories[]="/var/run/dhcp3-server";
    $directories[]="/var/lib/dhcp3";
    $directories[]="/etc/dhcp3";
    return $directories;
}

function wizard(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress("{enable_service}",5);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDHCPServer", 1);

	if(is_file("/etc/init.d/isc-dhcp-relay")){
	    shell_exec("/usr/sbin/artica-phpfpm-service -uninstall-dhcrelay");
    }

    $unix->SystemCreateUser("dhcpd","dhcpd");
    build_progress("{starting_service}",60);
    $directories=mandatories_dirs();
    foreach ($directories as $dirpath){
        if(!is_dir($dirpath)){@mkdir($dirpath,0755,true);}
        $unix->chown_func("dhcpd","dhcpd", "$dirpath/*");
        $unix->chmod_func(0755, "$dirpath");
    }


	install_dhcpd();

	$DHCPWizard=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPWizard"));
	$dhcp=new dhcpd(0,1);
	
		
		if(!isset($DHCPWizard["NIC"])){$DHCPWizard["NIC"]="eth0";}
		
		if(!isset($DHCPWizard["SUBNET"])){
			
			$nic=new system_nic($DHCPWizard["NIC"]);
			$ndns=new resolv_conf();
			if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)\.#", $nic->IPADDR,$re)){
				$DHCPWizard["SUBNET"]="{$re[1]}.{$re[2]}.{$re[3]}.0";
			}
			$DHCPWizard["NETMASK"]=$nic->NETMASK;
			$DHCPWizard["RANGE1"]="{$re[1]}.{$re[2]}.{$re[3]}.30";
			$DHCPWizard["RANGE2"]="{$re[1]}.{$re[2]}.{$re[3]}.254";
			$DHCPWizard["DNS1"]=$ndns->MainArray["DNS1"];
			$DHCPWizard["DNS2"]=$ndns->MainArray["DNS2"];
			$DHCPWizard["DOMAINNAME"]="local";
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DHCPWizard", serialize($DHCPWizard));
		}
		
		echo "Listen nic: {$DHCPWizard["NIC"]}\n";
		echo "Network: {$DHCPWizard["SUBNET"]}/{$DHCPWizard["NETMASK"]} {$DHCPWizard["RANGE1"]}-{$DHCPWizard["RANGE2"]}\n";
		if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)\.#", $DHCPWizard["SUBNET"],$re)){
			$DHCPWizard["SUBNET"]="{$re[1]}.{$re[2]}.{$re[3]}.0";
			
		}
		
		
		echo "NIC.......: {$DHCPWizard["NIC"]}\n";
		echo "DOMAINNAME: {$DHCPWizard["DOMAINNAME"]}\n";
		echo "NETMASK...: {$DHCPWizard["NETMASK"]}\n";
		echo "RANGE1....: {$DHCPWizard["RANGE1"]}\n";
		echo "RANGE2....: {$DHCPWizard["RANGE2"]}\n";
		echo "GATEWAY...: {$DHCPWizard["GATEWAY"]}\n";
		
		$dhcp->listen_nic=$DHCPWizard["NIC"];
		$dhcp->ddns_domainname=$DHCPWizard["DOMAINNAME"];
		$dhcp->netmask=$DHCPWizard["NETMASK"];
		$dhcp->range1=$DHCPWizard["RANGE1"];
		$dhcp->range2=$DHCPWizard["RANGE2"];
		$dhcp->subnet=$DHCPWizard["SUBNET"];
		$dhcp->gateway=$DHCPWizard["GATEWAY"];
		$dhcp->DNS_1=$DHCPWizard["DNS1"];
		$dhcp->DNS_2=$DHCPWizard["DNS2"];
		build_progress("{save_configuration}",10);
		$dhcp->Save(true);
	

	
	build_progress("{stopping_service}",15);
	stop(true);
	build_progress("{starting_service}",60);
	if(!start(true)){build_progress("{starting_service}  {failed}",100);return;}

	
	build_progress("{starting_service}",95);
	if(is_file("/etc/init.d/munin-node")){system("$php /usr/share/artica-postfix/exec.munin.php --reconfigure");}

    if(is_file("/etc/init.d/snmpd")){
        shell_exec("/etc/init.d/snmpd restart");
    }
	
	build_progress("{starting_service}  {success}",100);
}

function DHCPD_omapi(){
	$unix=new unix();
	if($GLOBALS["OUTPUT"]){$prefix="Starting......: ".date("H:i:s")." [INIT]: ";}
	$rm=$unix->find_program("rm");
	
	if(is_file("/etc/artica-postfix/settings/Daemons/DHCPDOmApi")){
		$DHCPDOmApi=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDOmApi"));
		if(strlen($DHCPDOmApi)<5){@unlink("/etc/artica-postfix/settings/Daemons/DHCPDOmApi");}
	}
	
	if(is_file("/etc/artica-postfix/settings/Daemons/DHCPDOmApi")){
		if(strlen($DHCPDOmApi)>5){
			echo "{$prefix}OMAPI Key: ". substr($DHCPDOmApi,0,27)."...\n";
			echo "{$prefix}OMAPI Key: ". base64_encode($DHCPDOmApi)."\n";
			return true;
		}
	}
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/DHCPDOmApi")){
	
		$dnssec_keygen=$unix->find_program("dnssec-keygen");
	
		if(!is_file($dnssec_keygen)){
			echo "{$prefix}Fatal !!! dnssec-keygen no such binary !!\n";
			return false;
		}
		$TEMPDIR=$unix->TEMP_DIR()."/".time();
		@mkdir($TEMPDIR,0755,true);
		echo "{$prefix}Generating keys...\n";
		$key=trim(exec("$dnssec_keygen -K $TEMPDIR -r /dev/urandom -a HMAC-MD5 -b 512 -n HOST omapi_key 2>&1"));
		
		echo "{$prefix}Got key $key\n";
		if(!is_file("$TEMPDIR/$key.private")){
			echo "$TEMPDIR/$key.private no such file!!!\n";
			shell_exec("$rm -rf $TEMPDIR");
			return false;
		}
		if(!is_file("$TEMPDIR/$key.key")){
			echo "$TEMPDIR/$key.key no such file!!!\n";
			shell_exec("$rm -rf $TEMPDIR");
			return false;
		}
		$private=trim(@file_get_contents("$TEMPDIR/$key.private"));
		$keydata=trim(@file_get_contents("$TEMPDIR/$key.key"));
		shell_exec("$rm -rf $TEMPDIR");
		
		
		$tb=explode("\n",$private);
		foreach ($tb as $line){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#^Key:\s+(.+)#", $line,$re)){
			echo "{$prefix}Private: {$re[1]}\n";
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DHCPDOmApi", $re[1]);
			}
		}

	}
	
	echo "{$prefix}DHCPDOmApi: SUCCESS!\n";
	return true;
		
	
}

function DHCPDInPowerDNS(){
	$unix=new unix();
	$rm=$unix->find_program("rm");
	if($GLOBALS["OUTPUT"]){$prefix="Starting......: ".date("H:i:s")." [INIT]: ";}
	$DHCPDInPowerDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDInPowerDNS"));
	if($DHCPDInPowerDNS==0){echo "DHCPDInPowerDNS == 0...\n";return true;}
	$ini=new Bs_IniHandler("/etc/artica-postfix/settings/Daemons/ArticaDHCPSettings");
	$domain=$ini->_params["SET"]["ddns_domainname"];
	
	if($domain==null){echo "{$prefix}DHCPDInPowerDNS == No domain!!!\n";return false;}
	
	if(is_file("/etc/artica-postfix/settings/Daemons/DHCPDDDNSKey")){
		$DHCPDDDNSKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDDDNSKey"));
		if(strlen($DHCPDDDNSKey)<5){@unlink("/etc/artica-postfix/settings/Daemons/DHCPDDDNSKey");}
	}
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/DHCPDDDNSKey")){
	
		$dnssec_keygen=$unix->find_program("dnssec-keygen");
	
		if(!is_file($dnssec_keygen)){
			echo "{$prefix}Fatal !!! dnssec-keygen no such binary !!\n";
			return false;
		}
		
		$TEMPDIR=$unix->TEMP_DIR()."/".time();
		@mkdir($TEMPDIR,0755,true);
		echo "{$prefix}Generating keys...\n";
		$key=trim(exec("$dnssec_keygen -K $TEMPDIR -a hmac-md5 -b 128 -n USER dhcpdupdate 2>&1"));
		
		echo "{$prefix}Got key $key\n";
		if(!is_file("$TEMPDIR/$key.private")){
			echo "$TEMPDIR/$key.private no such file!!!\n";
			shell_exec("$rm -rf $TEMPDIR");
			return false;
		}
		if(!is_file("$TEMPDIR/$key.key")){
			echo "$TEMPDIR/$key.key no such file!!!\n";
			shell_exec("$rm -rf $TEMPDIR");
			return false;
		}
		$private=trim(@file_get_contents("$TEMPDIR/$key.private"));
		$keydata=trim(@file_get_contents("$TEMPDIR/$key.key"));
		shell_exec("$rm -rf $TEMPDIR");
		
		
		$tb=explode("\n",$private);
		foreach ($tb as $line){
			$line=trim($line);
			if($line==null){continue;}
			if(preg_match("#^Key:\s+(.+)#", $line,$re)){
				echo "{$prefix}Private: {$re[1]}\n";
				$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DHCPDDDNSKey", $re[1]);
			}
		}
	
	}
	
	$DHCPDDDNSKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDDDNSKey"));
	
	echo "{$prefix}Key....: $DHCPDDDNSKey\n";
	echo "{$prefix}Domain.: $domain\n";
	
	$q=new mysql_pdns();
	$dhcpd=new dhcpd();
	$range=$dhcpd->ExplodeMyRange("default");
	
	
	$domain_id=$q->GetDomainID($domain);
	if(!$q->AllowDNSUpdates($domain,"127.0.0.0/8")){
		echo "{$prefix}Failed for $domain\n";
		return false;
	}
	
	$IPS=$unix->NETWORK_ALL_INTERFACES(true);
    foreach ($IPS as $ipadr=>$none){
		if($ipadr=="127.0.0.1"){continue;}
		if($ipadr=="127.0.0.154"){continue;}
		$q->AllowDNSUpdates($domain,"$ipadr");
	
	}
	reset($IPS);
	
	
	if(!$q->AllowTSIGUpdates($domain,"dhcpdupdate")){
		echo "{$prefix}Failed for TSIG $domain\n";
		return false;
	}
	
	

	$domain="{$range[3]}.{$range[2]}.{$range[1]}.in-addr.arpa";
	echo "{$prefix}Domain.: $domain\n";
	if(!$q->AllowDNSUpdates($domain,"127.0.0.0/8")){
		echo "{$prefix}AllowDNSUpdates:: Failed for $domain\n";
		return false;
	}


    foreach ($IPS as $ipadr=>$none){
		if($ipadr=="127.0.0.1"){continue;}
		if($ipadr=="127.0.0.154"){continue;}
		$q->AllowDNSUpdates($domain,"$ipadr");
		
	}
	
	
	if(!$q->AllowTSIGUpdates($domain,"dhcpdupdate")){
		echo "{$prefix}Failed for TSIG $domain\n";
		return false;
	}	
	
	if(!$q->sigkeys("dhcpdupdate", $DHCPDDDNSKey)){
		echo "{$prefix}Sigkeys dhcpdupdate failed\n";
		return false;
	}
	
	echo "{$prefix}DHCPDInPowerDNS: SUCCESS!\n";
	return true;
	
}






function start($aspid=false){
	$unix=new unix();
	$LOGBIN="DHCP Server";


    shell_exec("/usr/sbin/artica-phpfpm-service -start-dhcpd");

	
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN service Success service started pid:$pid...\n";}
		build_progress("{starting_service}  {success}",100);
		return true;
	}

	build_progress("{starting_service}  {failed}",99);
	return false;
	
}
//##############################################################################
function restart(){
	$unix=new unix();
	$LOGBIN="DHCP Server";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if(!$GLOBALS["FORCE"]){
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN [RESTART] Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	}

	if(!BuildDHCP(true)){
        build_progress("{configuration} {failed}",110);
        return;

    }

	build_progress("{stopping_service}",10);
	stop(true);
	if($GLOBALS["PROGRESS"]){build_progress("{reconfigure_service}",50);}
    build_progress("{starting_service}",50);
	if(!start(true)){
		build_progress("{starting_service} {failed}",110);
	}
	build_progress("{starting_service} {success}",100);
}




function reload_if_run(){
	$pid=PID_NUM();
	$unix=new unix();
	if(!$unix->process_exists($pid)){exit();}
	reload();
}

function reload(){
	$unix=new unix();
	$LOGBIN="DHCP Server";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	$pid=PID_NUM();
	$time=$unix->PROCCESS_TIME_MIN($pid);
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	if(!BuildDHCP(true)){
        echo "Starting......: ".date("H:i:s")." [INIT]: Configuration failed\n";
        build_progress("{configuration} {failed}",110);
        return;
    }
	echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN reloading PID $pid since {$time}mn\n";
	stop(true);
	start(true);

}
//##############################################################################
function stop($aspid=false){
    shell_exec("/usr/sbin/artica-phpfpm-service -stop-dhcpd");
    return true;
}
//##############################################################################
function PID_NUM(){
	$filename=PID_PATH();
	$pid=trim(@file_get_contents($filename));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($unix->DHCPD_BIN_PATH());
}
//##############################################################################
function PID_PATH(){
	return '/var/run/dhcpd.pid';
}
//##############################################################################

function dhcp3Config(){
	return "/etc/dhcp3/dhcpd.conf";
	
}
?>