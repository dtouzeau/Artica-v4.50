<?php
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
$a=array();
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");
foreach ($_GET as $num=>$ligne){$a[]="$num=$ligne";}
if(isset($_GET["iptables-save"])){iptables_save();exit;}
if(isset($_GET["iptables-events"])){iptables_events();exit;}
if(isset($_GET["ethtool-k"])){ethtools_k();exit;}
if(isset($_GET["ethtool-edit"])){ethtools_edit();exit;}
if(isset($_GET["ifconfig-array"])){ifconfig_array();exit;}
if(isset($_GET["open-ports"])){open_ports();exit;}

if(isset($_GET["nmap-ping"])){nmap_ping();exit;}
if(isset($_GET["isFW"])){isFW();exit;}
if(isset($_GET["conntrack"])){conntrack();exit;}
if(isset($_GET["NetworkManager-check-redhat"])){NetworkManager_redhat();exit;}
if(isset($_GET["reconfigure-postfix-instances"])){postfix_reconfigures_multiples_instances();exit;}
if(isset($_GET["ping"])){pinghost();exit;}
if(isset($_GET["OpenVPNServerLogs"])){OpenVPN_ServerLogs();exit;}
if(isset($_GET["ipdeny"])){ipdeny();exit;}
if(isset($_GET["fw-inbound-rules"])){iptables_inbound();exit;}
if(isset($_GET["fw-spamhaus-rules"])){iptables_spamhausrules();exit;}
if(isset($_GET["fqdn"])){fqdn();exit;}
if(isset($_GET["iptaccount-installed"])){iptaccount_check();exit;}
if(isset($_GET["ifup-ifdown"])){ifup_ifdown();exit;}
if(isset($_GET["reconstruct-interface"])){reconstruct_interface();exit;}
if(isset($_GET["reconstruct-all-interfaces"])){reconstruct_all_interfaces();exit;}
if(isset($_GET["arp-delete"])){arptable_delete();exit;}
if(isset($_GET["arp-edit"])){arptable_edit();exit;}
if(isset($_GET["ifconfig"])){ifconfig();exit;}
if(isset($_GET["ifconfig6"])){ifconfig6();exit;}
if(isset($_GET["vde-restart"])){vde_restart();exit;}
if(isset($_GET["vde-status"])){vde_status();exit;}
if(isset($_GET["reconfigure-restart"])){reconfigure_restart_network();exit;}
if(isset($_GET["down-interface"])){down_interface();exit;}
if(isset($_GET["flush-arp-cache"])){flush_arp_cache();exit;}
if(isset($_GET["etc-hosts"])){etc_hosts();exit;}
if(isset($_GET["artica-ifup-content"])){artica_ifup_content();exit;}
if(isset($_GET["ucarp-down"])){ucarp_down();exit;}


if(isset($_GET["install-vlan"])){install_vlan();exit;}
if(isset($_GET["uninstall-vlan"])){uninstall_vlan();exit;}
if(isset($_GET["build-vlans"])){build_vlans();exit;}


if(isset($_GET["routes-build"])){build_routes();exit;}
if(isset($_GET["delayed-nets"])){delayed_net();exit;}

if(isset($_GET["masquerade-interfaces"])){masquerade_interfaces();exit;}
if(isset($_GET["crc32"])){build_crc32();}

//Bond
if(isset($_GET["reset-nics"])){reset_nic();exit;}
if(isset($_GET["add-bond-interface"])){add_bond_interface();exit;}
if(isset($_GET["bond-stats"])){bond_stats();exit;}


writelogs_framework("***** Unable to unserstand ".@implode("&",$a),__FUNCTION__,__FILE__,__LINE__);





function bond_stats(){
    $unix = new unix();
    $file = @file_get_contents("/proc/net/bonding/{$_GET["bond"]}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("Bond-Stats-{$_GET["bond"]}",$file);

}
function build_crc32(){
    include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
    $CRC32_INTERFACES_DB=crc32_file("/home/artica/SQLITE/interfaces.db");
    writelogs_framework("/home/artica/SQLITE/interfaces.db -> $CRC32_INTERFACES_DB",__FUNCTION__,__FILE__,__LINE__);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CRC32_INTERFACES_CURRENT",$CRC32_INTERFACES_DB);
}

function reset_nic(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd="$php5 /usr/share/artica-postfix/exec.virtuals-ip.php --reset-nic {$_GET["eth"]} {$_GET["ippref"]} >/dev/null 2>&1 &";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function add_bond_interface():bool{
    $unix=new unix();
    $bondEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableInterfaceBond"));
    $bondCount = 0;
    if($bondEnabled==1){
        $file = "/sys/class/net/bonding_masters";
        $content = file_get_contents($file);
        $arr = explode(" ", $content);

        $arrNumber=array();
        foreach ($arr as $values)
        {
            $int = (int) filter_var($values, FILTER_SANITIZE_NUMBER_INT);
            $arrNumber[$int] = $int;
        }
        $bondCount = $bondCount + intval(array_search(max($arrNumber), $arrNumber));
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("bondCount",$bondCount);
        $bondFinalCount=$bondCount+1;
        $echo=$unix->find_program("echo");
        exec("$echo \"+bond$bondFinalCount\" > /sys/class/net/bonding_masters");
        return true;

    }
    return true;
}

function flush_arp_cache():bool{
	$unix=new unix();
	$ip=$unix->find_program("ip");
	$results[]="$ip -s -s neigh flush all";
	exec("$ip -s -s neigh flush all 2>&1",$results);
	writelogs_framework("$ip -s -s neigh flush all 2>&1 ".count($results)." items",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
	return true;
}
function delayed_net():bool{
    $unix=new unix();
    $IfConfigdelayed=intval($_GET["delayed-nets"]);
    $bondEnabled=intval($_GET["bonding"]);
    $fname="/usr/local/sbin/network-delayed.sh";
    $logger=$unix->find_program("logger");
    $touch=$unix->find_program("touch");
    $rm=$unix->find_program("rm");
    $sh[]="#!/bin/sh";
    $sh[]="$rm -f /etc/artica-postfix/network-delayed >/dev/null 2>&1|| true";
    $sh[]="$touch /etc/artica-postfix/network-delayed";
    $sh[]="$logger -i -t network \"Starting Delayed Network feature.\" || true";
    $sh[]="/usr/sbin/artica-phpfpm-service -restart-network >/dev/null 2>&1";
    $sh[]="/usr/bin/php /usr/share/artica-postfix/exec.lighttpd.php --system-reload >/dev/null 2>&1";
    $sh[]="/etc/init.d/monit restart >/dev/null 2>&1";
    $sh[]="";
    $modprobe=$unix->find_program("modprobe");
    if($bondEnabled==1){
        exec("$modprobe bonding >/dev/null 2>&1");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableInterfaceBond",1);
    }
    if($bondEnabled==0){
        exec("$modprobe -r bonding >/dev/null 2>&1");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableInterfaceBond",0);
    }


    if($IfConfigdelayed==1){
        @file_put_contents($fname,@implode("\n",$sh));
        @chmod($fname,0755);
        return true;
    }
    if(is_file($fname)){@unlink($fname);}
    return true;
}



function masquerade_interfaces(){
    $unix=new unix();
    $MAIN=array();
    $iptables_save=$unix->find_program("iptables-save");
    exec("$iptables_save 2>&1",$results);
    foreach ($results as $line){
        if(preg_match("#\"MASQUERADE\.(.+?)\"#",$line,$re)){
            $MAIN[$re[1]]=true;
        }
    }

    @file_put_contents(PROGRESS_DIR."/MASQUERADE.eths",serialize($MAIN));

}





function NetworkManager_redhat(){
	$unix=new unix();
	$chkconfig=$unix->find_program("chkconfig");
	if(!is_file($chkconfig)){return;}
	exec("$chkconfig --list NetworkManager 2>&1",$results);
	echo "<articadatascgi>". @implode("\n",$results)."</articadatascgi>";
}


function ethtools_k(){
	$nic=$_GET["nic"];
	$unix=new unix();
	$ethtool=$unix->find_program("ethtool");
	shell_exec("$ethtool -k $nic >/usr/share/artica-postfix/ressources/logs/ethtool_$nic.txt 2>&1");
}
function ethtools_edit(){
	
	$unix=new unix();
	$ethtool=$unix->find_program("ethtool");
	$key=$_GET["key"];
	$val=$_GET["val"];
	$nic=$_GET["nic"];
	
	$cmdlines=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EthtoolsCommands"));
	
	
	$MAINT["generic-segmentation-offload"]="gso";
	$MAINT["generic-receive-offload"]="gro";
	$MAINT["tcp-segmentation-offload"]="tso";
	$MAINT["large-receive-offload"]="lro";
	$MAINT["rx-vlan-acceleration"]="rxvlan";
	$MAINT["tx-vlan-acceleration"]="txvlan";
	$MAINT["rx-ntuple-filters"]="ntuple";
	$MAINT["receive-hashing-offload"]="rxhash";
	$MAINT["tx-checksumming"]="tx";
	$MAINT["rx-checksumming"]="rx";
	$MAINT["scatter-gather"]="sg";
	$MAINT["udp-fragmentation-offload"]="ufo";

	if(isset($MAINT[$key])){$key=$MAINT[$key];}
	
	
	if($nic==null){
		writelogs_framework("$key $val -> NIC is null -> ".@implode(" - ", $_GET),__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$cmd="$ethtool -K $nic $key $val";
	$cmdlines[$cmd]=$cmd;
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EthtoolsCommands", serialize($cmdlines));
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function nmap_ping(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/nmap.pingnet.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/nmap.pingnet.progress.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);

	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nmapscan.php --scan-ping >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function install_vlan(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/vlans.progress";
    $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/vlans.install.log";
    @unlink($config["PROGRESS_FILE"]);
    @unlink($config["LOG_FILE"]);

    @touch($config["PROGRESS_FILE"]);
    @touch($config["LOG_FILE"]);

    @chmod($config["PROGRESS_FILE"], 0755);
    @chmod($config["LOG_FILE"], 0755);
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.vlans.php --install >{$config["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function build_vlans(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.vlans.php --build >/dev/null 2>&1 &";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function uninstall_vlan(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/vlans.progress";
    $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/vlans.install.log";
    @unlink($config["PROGRESS_FILE"]);
    @unlink($config["LOG_FILE"]);

    @touch($config["PROGRESS_FILE"]);
    @touch($config["LOG_FILE"]);

    @chmod($config["PROGRESS_FILE"], 0755);
    @chmod($config["LOG_FILE"], 0755);
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.vlans.php --uninstall >{$config["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}


function iptaccount_check(){
	$unix=new unix();
	$iptaccount=$unix->find_program("iptaccount");
	if(!is_file($iptaccount)){echo "<articadatascgi>FALSE</articadatascgi>";return;}
	exec("$iptaccount -a 2>&1",$results);
	foreach ($results as $ligne){
		if(preg_match("#failed: Can't get table names from kernel#", $ligne)){
			echo "<articadatascgi>FALSE</articadatascgi>";return;
		}
	}
	echo "<articadatascgi>TRUE</articadatascgi>";return;
}

function fqdn(){
	$unix=new unix();
	$hostname=$unix->FULL_HOSTNAME();
	echo "<articadatascgi>". base64_encode($hostname)."</articadatascgi>";
}

function postfix_reconfigures_multiples_instances(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	shell_exec(trim("$nohup $php /usr/share/artica-postfix/exec.virtuals-ip.php --postfix-instances >/dev/null 2>&1 &"));

}

function build_routes(){

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/reconfigure-newtork.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/exec.virtuals-ip.php.log";

    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);

    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);

    @chmod($ARRAY["PROGRESS_FILE"], 0755);
    @chmod($ARRAY["LOG_FILE"], 0755);

    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php /usr/share/artica-postfix/exec.virtuals-ip.php --routes-build >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}



function ifconfig(){
	$net=$_GET["ifconfig"];
	$unix=new unix();
	$ifconfig=$unix->find_program("ifconfig");
	exec("$ifconfig $net 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function ifconfig_array(){
	$Interface=$_GET["ifconfig-array"];
	$f=explode("\n",@file_get_contents("/proc/net/dev"));
	$MAIN["SPEED"]=@file_get_contents("/sys/class/net/$Interface/speed");
	
	foreach ($f as $line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^$Interface:\s+(.+)#", $line,$re)){continue;}
		$data=preg_split("/\s+/", $re[1]);
		$MAIN["RX"]=$data[0];
		$MAIN["DROP"]=$data[3];
		$MAIN["TX"]=$data[8];
	}
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/interface.array", serialize($MAIN));
	
}
function ifconfig6(){
	$net=$_GET["ifconfig6"];
	$unix=new unix();
	$ip=$unix->find_program("ip");	
	$cmd="$ip -6 address show $net 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	$array=array();
	foreach ($results as $ligne){
		if(preg_match("#inet6\s+(.*?)\s+scope#", $ligne,$re)){
			$array[$re[1]]=$re[1];
		}
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}

function pinghost(){
	$host=$_GET["ping"];
	$unix=new unix();
	if($unix->PingHost($host)){
		echo "<articadatascgi>TRUE</articadatascgi>";
	}
}


	
function ifup_ifdown(){
	$eth=$_GET["ifup-ifdown"];
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink("/etc/artica-postfix/MEM_INTERFACES");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.virtuals-ip.php --ifupifdown $eth >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}


function OpenVPN_ServerLogs(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$cmd=trim("$tail -n 300 /var/log/openvpn/openvpn.log 2>&1 ");
	
	exec($cmd,$results);		
	writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}


function iptables_inbound(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.postfix.iptables.php --perso >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	$cmd="$nohup $php /usr/share/artica-postfix/exec.iptables.php --dns >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function reconstruct_interface(){
	$eth=$_GET["reconstruct-interface"];
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink("/etc/artica-postfix/MEM_INTERFACES");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.virtuals-ip.php --reconstruct-interface $eth --sleep >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function reconstruct_all_interfaces(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink("/etc/artica-postfix/MEM_INTERFACES");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.virtuals-ip.php --sleep >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function arptable_edit(){
	$unix=new unix();
	$datas=unserialize(base64_decode($_GET["arp-edit"]));
	if(!is_array($datas)){
		writelogs_framework("Not an array",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$arpbin=$unix->find_program("arp");
	$host=$datas["ARP_IP"];
	$mac=$datas["ARP_MAC"];
	$cmd="$arpbin -d $host";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
			
	$cmd="$arpbin -s $host $mac";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	foreach ($results as $num=>$ligne){writelogs_framework($ligne,__FUNCTION__,__FILE__,__LINE__);}
	$cmd=trim("$php /usr/share/artica-postfix/exec.arpscan.php --tomysql >/dev/null 2>&1");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function arptable_delete(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$arpbin=$unix->find_program("arp");
	$host=$_GET["arp-delete"];
	$cmd="$arpbin -d $host";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
	$cmd=trim("$php /usr/share/artica-postfix/exec.arpscan.php --tomysql >/dev/null 2>&1");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}

function vde_restart(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/vde.status.html";
	$cmd=trim("$php /usr/share/artica-postfix/exec.initslapd.php --vde-switch >/dev/null 2>&1");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	@unlink($cachefile);
	@touch($cachefile);
	@chmod($cachefile,0755);
	$cmd="$nohup /etc/init.d/vde_switch restart >$cachefile 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function vde_status(){
}

function etc_hosts(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$php /usr/share/artica-postfix/exec.virtuals-ip.php --hosts 2>&1");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
	}	
function artica_ifup_content(){
	$datas=@file_get_contents("/etc/init.d/artica-ifup");
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";
	
}


function iptables_save(){
	$unix=new unix();
	$iptables=$unix->find_program("iptables-save");
	shell_exec("$iptables >/usr/share/artica-postfix/ressources/logs/web/iptables.save.html");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/iptables.save.html",0777);
	
}




function  reconfigure_restart_network(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	ToSyslog("kernel: [  Artica-Net] reconfigure Network [artica-ifup] (".basename(__FILE__)."/".__LINE__.")" );
	$cmd=trim("/etc/init.d/artica-ifup reconfigure");
	shell_exec("$nohup /etc/init.d/artica-ifup reconfigure --script=cmd.php/reconfigure_restart_network >/dev/null 2>&1 &");
}
function down_interface(){
	$down_interface=$_GET["down-interface"];
	$unix=new unix();
	$ifconfig=$unix->find_program("ifconfig");
	shell_exec("$ifconfig $down_interface down");
}
function iptables_spamhausrules(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.spamhausdrop.php --force >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function ucarp_down(){
	$unix=new unix();
	$interface=$_GET["ucarp-down"];
	$master=$_GET["master"];
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES(true);
	if(!isset($NETWORK_ALL_INTERFACES[$interface])){
		writelogs_framework("Interface $interface not up [OK]",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$nohup=$unix->find_program("nohup");
	$MN=unserialize(@file_get_contents("/usr/share/ucarp/ETH_LIST"));
    foreach ($MN as $eth=>$line){
		writelogs_framework("Interface $eth down [OK]",__FUNCTION__,__FILE__,__LINE__);
		$cmd="$nohup /usr/share/ucarp/vip-eth0-down.sh >/dev/null 2>&1";
		writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
		squid_admin_mysql(0, "Master [$master]: Ordered to shutdown $interface [OK]", null,__FILE__,__LINE__);
		echo "<articadatascgi>DOWN_OK</articadatascgi>";
	}
	
}

function conntrack(){
	
$handle=fopen("/proc/net/ip_conntrack",'r');
if(!$handle){die("DIE " .__FILE__." Line: ".__LINE__);}
if(!is_numeric($_GET["rp"])){$_GET["rp"]=25;}
$max=intval($_GET["rp"]);
if($max<5){$max=25;}
$pattern=null;

if($_GET["qtype"]<>null){
	if($_GET["query"]<>null){
		$pattern="#{$_GET["qtype"]}={$_GET["query"]}#";
		writelogs_framework($pattern,__FUNCTION__,__FILE__,__LINE__);
	}else{
		writelogs_framework("{$_GET["qtype"]} pattern is null",__FUNCTION__,__FILE__,__LINE__);
	}
}

$c=1;
while (!feof($handle)) {
	$value=trim(fgets($handle));
	if($value==null){continue;}
	if($pattern<>null){
		if(!preg_match($pattern, $value)){continue;}
	}
	$md5=md5($value);
	$values=explode(" ",$value);
	$array[$md5]["LINE"]=$value;
	$array[$md5]["COUNT"]="$c/$max";
	
	foreach ($values as $line){
		if($line==null){continue;}
		if(preg_match("#(.+?)=(.+)#", $line,$rz)){
			$key=$rz[1]; $xval=$rz[2];
			if(!isset($array[$md5]["$key"])){
				$array[$md5]["$key"]=$xval;
				continue;
			}
			continue;
		}
		
		if(preg_match("#([a-z]+)#", $line,$ri)){
			$array[$md5]["proto"]=$ri[1];
		}
		
		if(!isset($array[$md5]["status"])){
			if(preg_match("#([A-Z\_]+)#", $line,$ri)){
				$array[$md5]["status"]=$ri[1];
				continue;
			}
		}
		
		if(preg_match("#\[(.+?)\]#", $line,$ra)){
			$array[$md5]["status"]=$ra[1];
			continue;
			
		}
		
		
		
	}
	$c++;
	if($c>=$max){break;}

}
fclose($handle);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/conntrack.inc", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/conntrack.inc", 0755);
	
}
function open_ports(){

    shell_exec("/usr/bin/lsof -i -nPM >/usr/share/artica-postfix/ressources/logs/web/ports.txt 2>&1");
}


function iptables_events(){
	$unix=new unix();
	$search=$_GET["search"];
	$rp=intval($_GET["rp"]);
	$eth=$_GET["eth"];
	$logfile="/usr/share/artica-postfix/ressources/logs/web/iptables.log";
	
	if($eth<>null){
		if($search<>null){
			$search="($search.*?={$eth}|={$eth}.*?$search)";
		}else{
			$search="?={$eth}";
		}
	}
	
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	if($search==null){
			$cmdline="$tail -n $rp /var/log/iptables.log >$logfile 2>&1";
			writelogs_framework($cmdline,__FUNCTION__,__FILE__,__LINE__);
			shell_exec($cmdline);
			@chmod($logfile,0777);
			return;
	}
	
	if($search<>null){
		$cmdline="$grep --binary-files=text -Ei \"$search\" /var/log/iptables.log|$tail -n $rp  >$logfile 2>&1";
		writelogs_framework($cmdline,__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmdline);
		@chmod($logfile,0777);
		return;
		
	}
}

