<?php
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
$a=array();
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");
foreach ($_GET as $num=>$ligne){$a[]="$num=$ligne";}


if(isset($_GET["nmap-ping"])){nmap_ping();exit;}
if(isset($_GET["ping"])){pinghost();exit;}
if(isset($_GET["fqdn"])){fqdn();exit;}
if(isset($_GET["ifconfig"])){ifconfig();exit;}
if(isset($_GET["ucarp-down"])){ucarp_down();exit;}


// framework/ is a DEPRECATED entry point — do not add anything here.
//
// MIGRATED to the daemon, which validates its arguments instead of
// interpolating $_GET into a shell string:
//
//   add-bond-interface    -> GET /system/network/bond/add                 bondAdd()
//   bond-stats            -> GET /system/network/bond/stats/{iface}       bondRuntimeStats()
//   reset-nics            -> GET /system/network/interface/reset/{ifname} nicResetAddress()
//   delayed-nets          -> GET /system/network/delayed                  delayedNetwork()
//   masquerade-interfaces -> GET /system/network/masquerade               masqueradeInterfaces()
//   build-vlans           -> GET /system/network/vlan/build               vlanBuild()
//   reconfigure-restart   -> GET /system/network/reconfigure-restart      reconfigureAndRestart()
//   down-interface        -> GET /system/network/interface/down/{ifname}  nicDown()
//   ifconfig-array        -> GET /system/network/interface/counters/{ifname}  nicCounters()
//   ethtool-k             -> GET /system/network/interface/features/{ifname}  nicFeatures()
//   open-ports            -> GET /system/network/openports                openPortsHandler()
//
// REMOVED as dead code — no caller anywhere in the repository, verified against
// the whole tree and not just the "network.php?<key>=" call shape (no file
// builds this URL dynamically, and nothing includes this file):
//
//   flush-arp-cache  iptables-save   iptables-events  ethtool-edit
//   isFW             conntrack       etc-hosts        ifconfig6
//   NetworkManager-check-redhat      reconfigure-postfix-instances
//   OpenVPNServerLogs                fw-inbound-rules fw-spamhaus-rules
//   iptaccount-installed             reconstruct-all-interfaces
//   arp-delete       arp-edit        vde-restart      vde-status
//   artica-ifup-content              install-vlan     uninstall-vlan
//   crc32
//
// isFW had no function at all: reaching it was a PHP fatal error. crc32 was
// removed with its five callers: it maintained the Valkey key
// CRC32_INTERFACES_CURRENT for a comparison that is commented out in
// fw.icon.top.php, so nothing ever read what it computed.
//
// STILL LIVE below, each with at least one caller.

writelogs_framework("***** Unable to unserstand ".@implode("&",$a),__FUNCTION__,__FILE__,__LINE__);



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
function fqdn(){
	$unix=new unix();
	$hostname=$unix->FULL_HOSTNAME();
	echo "<articadatascgi>". base64_encode($hostname)."</articadatascgi>";
}

function ifconfig(){
	$net=$_GET["ifconfig"];
	$unix=new unix();
	$ifconfig=$unix->find_program("ifconfig");
	exec("$ifconfig ".escapeshellarg($net)." 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}


function pinghost(){
	$host=$_GET["ping"];
	$unix=new unix();
	if($unix->PingHost($host)){
		echo "<articadatascgi>TRUE</articadatascgi>";
	}
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



