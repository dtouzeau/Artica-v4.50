<?php
// Post SP10 - 13/10/2026
//SP139
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");

$f=array();
foreach ($_GET as $num=>$line){
	if($GLOBALS["VERBOSE"]){echo "$num=$line\n";}
	$f[]="$num=$line";
}
writelogs_framework("Get query " .@implode(",",$f),"main()",__FILE__,__LINE__);

if(isset($_GET["SetServerTime"])){SetServerTime();exit;}
if(isset($_GET["TCP_NICS_STATUS_ARRAY"])){TCP_NICS_STATUS_ARRAY();exit;}
if(isset($_GET["restart-artica-status"])){RestartArticaStatus();exit;}
if(isset($_GET["iptables-save"])){iptables_save();exit;}

if(isset($_GET["wake-on-lan"])){WakeOnLan();exit;}

//snort



if(isset($_GET["ping"])){ping();exit;}

//autofs

//resolv

if(isset($_GET["sarg-passwords"])){SARG_PASSWORDS();exit;}

//syslog
if(isset($_GET["syslog-master-mode"])){syslog_master_mode();exit;}

//PDNS

//DNSMASQ

//iscsi

//UpdateUtility


//stunnel
if(isset($_GET["stunnel-restart"])){STUNNEL_RESTART();exit;}

//artica-meta
if(isset($_GET["artica-meta-user"])){artica_meta_user();exit;}
if(isset($_GET["artica-meta-export-dns"])){artica_meta_user_export_dns();exit;}
if(isset($_GET["artica-meta-computer"])){artica_meta_computer();exit;}
if(isset($_GET["artica-meta-fetchmail-rules"])){artica_meta_fetchmail_rules();exit;}
if(isset($_GET["artica-meta-ovpn"])){artica_meta_ovpn();exit;}
if(isset($_GET["artica-meta-openvpn-sites"])){artica_meta_export_openvpn_sites();exit;}
//organizations

//iptables

//apt-mirror

//qos
if(isset($_GET["qos-compile"])){qos_compile();exit;}

//ddclient

//saslauthd
if(isset($_GET["milter-greylist-ini-status"])){MILTER_GREYLIST_INI_STATUS();exit;}
if(isset($_GET["milter-greylist-reconfigure"])){milter_greylist_reconfigure();exit;}


// 

if(isset($_GET["Global-Applications-Status"])){Global_Applications_Status();exit;}
if(isset($_GET["system-debian-kernel"])){system_debian_kernel();exit;}
//clamav

//reportsF

//pure-ftpd

if(isset($_GET["pure-ftpd-users"])){pureftpd_users();exit;}

//NFS

//rsync
if(isset($_GET["rsyncd-conf"])){rsync_load_config();exit;}
if(isset($_GET["rsync-save-conf"])){rsync_save_conf();exit;}

//Install/Uninstall

//fetchmail
if(isset($_GET["restart-fetchmail"])){RestartFetchmail();exit;}

//Ad importation

//certitifcate
if(isset($_GET["ChangeSSLCertificate"])){ChangeSSLCertificate();exit;}
if(isset($_GET["postfix-freeze"])){postfix_freeze();exit;}

//cluebringer

//postmulti

//virtualbox

//safeBox
//ntpd
//zabix
//cyr
//WIFI

//openssh

//SQUID

if(isset($_GET["squid-status"])){SQUID_STATUS();exit;}



//samba
if(isset($_GET["smb-logon-scripts"])){samba_logon_scripts();exit;}

//postfix
if(isset($_GET["postfixQueues"])){postfixQueues();exit;}
if(isset($_GET["postfix-others-values"])){postfix_others_values();exit;}
if(isset($_GET["postfix-luser-relay"])){postfix_luser_relay();exit;}

if(isset($_GET["restart-openvpn-server"])){RestartOpenVPNServer();exit;}
if(isset($_GET["openvpn-status"])){openvpn_server_status();exit;}

if(isset($_GET["restart-strongswan-server"])){RestartstrongSwanServer();exit;}

//assp
if(isset($_GET["rsync-reconfigure"])){rsync_reconfigure();exit;}

//mailman

//DHCPD
if(isset($_GET["restart-dhcpd"])){RestartDHCPDService();exit;}

//network

//WIFI

//imapSYnc

//cyrus

if(isset($_GET["reconfigure-cyrus"])){ReconfigureCyrusImapDaemon();exit;} // --reconfigure-cyrus


//apache

//postfix

if(isset($_GET["postfix-mutli-stat"])){postfix_multi_stat();exit;}

//samba
if(isset($_GET["samba-save-config"])){samba_save_config();exit;}
if(isset($_GET["samba-build-homes"])){samba_build_homes();exit;}
if(isset($_GET["restart-samba"])){samba_restart();exit;}
if(isset($_GET["samba-shares-list"])){samba_shares_list();exit;}
if(isset($_GET["samba-change-sid"])){samba_change_sid();exit;}
if(isset($_GET["samba-original-conf"])){samba_original_config();exit;}

if(isset($_GET["smbpass"])){samba_password();exit;}
if(isset($_GET["home-single-user"])){samba_build_home_single();exit;}

//dropbox

//

//squid;
if(isset($_GET["cicap-reconfigure"])){cicap_reconfigure();exit;}
if(isset($_GET["cicap-reload"])){cicap_reload();exit;}
if(isset($_GET["cicap-restart"])){cicap_restart();exit;}
if(isset($_GET["MalwarePatrolDatabasesCount"])){MalwarePatrolDatabasesCount();exit;}

//apache-groupware

if(isset($_GET["export-community-categories"])){dansguardian_community_categories();exit;}

//disks
if(isset($_GET["disks-list"])){disks_list();exit;}
if(isset($_GET["check-dev"])){DEV_CHECK();}

if(isset($_GET["chmod-access"])){chmod_access();exit;}
if(isset($_GET["acls-apply"])){acls_apply();exit;}
if(isset($_GET["acls-delete"])){acls_delete();exit;}

// cmd.php?fstab-acl=yes&acl=$acl&dev=$dev
if(isset($_GET["get-mounted-path"])){disk_get_mounted_point();exit;}
if(isset($_GET["fdisk-build-big-partitions"])){disk_format_big_partition();exit;}
if(isset($_GET["chown"])){directory_chown();exit;}


if(isset($_GET["filetype"])){file_type();exit;}
if(isset($_GET["mime-type"])){mime_type();exit;}

//etc/hosts
//computers
//tcp
// ifconfig-interfaces: migre vers le demon -> GET /system/network/aliases
// (networking/nic_handlers.go::interfaceLabels, labels netlink au lieu de
// `ifconfig -s`). Point d'entree supprime avec sa fonction.
if(isset($_GET["ifconfig-all-ips"])){ifconfig_all_ips();exit;}
if(isset($_GET["lspci"])){lspci();exit;}
if(isset($_GET["dfmoinsh"])){dfmoinsh();exit;}
if(isset($_GET["printenv"])){printenv();exit;}

if(isset($_GET["filestat"])){filestat();exit;}
if(isset($_GET["file-content"])){file_content();exit;}

//computers

//paths 
//dansguardian

if(isset($_GET["uri"])) {
	$uri = $_GET["uri"];

	switch ($uri) {
		case "GlobalApplicationsStatus":
			break;
		case "artica_version":
			artica_version();
			break;
		case "daemons_status":
			daemons_status();
			break;
		case "pid":
			echo "<articadatascgi>" . getmypid() . "</articadatascgi>";
			break;
		case "myhostname";
			myhostname();
			break;

		default:

			break;
	}
}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");



// Pentest 2026-11-04 : stat/file tournent en root. Sans borne, ils revelaient
// l'existence, le type, les droits et les dates de n'importe quel fichier.
// Retourne le chemin reel s'il designe un fichier sous l'une des racines, sinon "".
function cmd_allowed_file($path,$roots){
	$real=realpath((string)$path);
	if($real===false || !is_file($real)){return "";}
	foreach ($roots as $root){
		$base=realpath($root);
		if($base!==false && strpos($real,$base."/")===0){return $real;}
	}
	return "";
}
function cmd_quarantine_roots(){
	return array("/home/c-icap/VirSaveDir");
}

function file_type(){
$unix=new unix();
$res="";
$results=array();
$filetype=cmd_allowed_file(base64_decode($_GET["filetype"]),cmd_quarantine_roots());
if($filetype===""){echo "<articadatascgi></articadatascgi>";return;}
	// Pentest 2026-11-04 : argument quote (injection shell).
	exec($unix->find_program("file")." -- ".escapeshellarg($filetype),$results);	
foreach ($results as $num=>$line){
		if(preg_match("#.+?:\s+(.+?)$#",$line,$re)){
			$res=$re[1];break;
		}
	}
	echo "<articadatascgi>".base64_encode($res)."</articadatascgi>";	
}

function mime_type(){
$unix=new unix();
$res="";
$results=array();
// Appelants : quarantaine c-icap (exec.cicap.php) et export des noeuds (nodes.listener.php).
$roots=cmd_quarantine_roots();
$roots[]="/usr/share/artica-postfix/ressources/squid-export";
$filetype=cmd_allowed_file(base64_decode($_GET["mime-type"]),$roots);
if($filetype===""){echo "<articadatascgi></articadatascgi>";return;}
	// Pentest 2026-11-04 : argument quote (injection shell).
	exec($unix->find_program("file")." -i -b -- ".escapeshellarg($filetype),$results);	
foreach ($results as $num=>$line){
		if(preg_match("#.+?;.+?$#",$line,$re)){
			$res=$line;break;
		}
	}
	echo "<articadatascgi>".base64_encode($res)."</articadatascgi>";	
}	







function DEV_CHECK():bool{
	$dev=(string)$_GET["check-dev"];
	// Pentest 2026-11-04 : readlink en root ne resout plus que les liens de /dev
	// (volumes LVM/mapper). Un autre chemin est rendu tel quel, sans lecture.
	if(strpos($dev,"/dev/")===0 && strpos($dev,"..")===false && is_link($dev)){
		$link=readlink($dev);
		$dev=str_replace("../mapper","/dev/mapper",$link);
		echo "<articadatascgi>$dev</articadatascgi>";
		return true;
	}
	echo "<articadatascgi>$dev</articadatascgi>";
	return true;

}

function SQUID_STATUS(){
	$cachefile=PROGRESS_DIR."/squid.status";
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --squid --nowachdog >$cachefile";
	@chmod($cachefile,0755);
	writelogs_framework($cmd);
	shell_exec($cmd);
	echo "<articadatascgi>". @file_get_contents($cachefile)."</articadatascgi>";
}







function STUNNEL_RESTART(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.stunnel.php --restart >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$cmd");
}





function MILTER_GREYLIST_INI_STATUS(){
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --milter-greylist --nowachdog >/usr/share/artica-postfix/ressources/logs/web/greylist.status 2>&1";
	shell_exec($cmd);
	
	
}









function rsync_reconfigure(){
	NOHUP_EXEC( LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.rsync-lvm.php");
}









	








	
function artica_version(){
	$datas=@file_get_contents("/usr/share/artica-postfix/VERSION");
	if(trim($datas)==null){$datas="0.00";}
	echo "<articadatascgi>$datas</articadatascgi>";
	  
}
function daemons_status(){

      if(!is_file('/usr/share/artica-postfix/ressources/logs/global.status.ini')){ 
            sys_exec('/etc/init.d/artica-status reload');
         }

      if(is_file('/usr/share/artica-postfix/ressources/logs/global.status.ini')){ 
            $datas=@file_get_contents("/usr/share/artica-postfix/ressources/logs/global.status.ini");
            echo "<articadatascgi>$datas</articadatascgi>";
            return ;
        }       
}

function myhostname():bool{
	$unix=new unix();
	$datas=$unix->hostname_g();
	sys_events(basename(__FILE__)."::{$_SERVER['REMOTE_ADDR']}:: myhostname ($datas)");
	 echo "<articadatascgi>$datas</articadatascgi>";
	 return true;
}

function RestartArticaStatus(){
	$unix=new unix();
	
	$unix->THREAD_COMMAND_SET("/etc/init.d/artica-status reload");
	$unix->THREAD_COMMAND_SET("/etc/init.d/monit restart");
}



function samba_restart(){
	NOHUP_EXEC("/etc/init.d/samba restart");	
}



function samba_save_config(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	@file_put_contents("/var/log/samba/log.smbd", "\n");
	$cmd=trim("$nohup ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.samba.php --build >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}




function samba_original_config(){
	$datas=@file_get_contents("/etc/samba/smb.conf");
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";	
}




function samba_build_homes(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.samba.php --homes");
}
function samba_build_home_single(){
	$uid=base64_decode($_GET["home-single-user"]);
	if($uid==null){return;}
	// Pentest 2026-11-04 : uid = nom simple (traversee /home/.. et injection shell).
	if(!preg_match('#^[A-Za-z0-9_.@$-]+$#',$uid) || strpos($uid,"..")!==false){return;}
	@mkdir("/home/$uid",0755,true);
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.samba.php --home ".escapeshellarg($uid));
}


function samba_change_sid(){
	$unix=new unix();
	$sid=$_GET["samba-change-sid"];
	// Pentest 2026-11-04 : SID valide uniquement (injection shell).
	if(!preg_match('#^S-[0-9]+(-[0-9]+)+$#',$sid)){return;}
	shell_exec($unix->LOCATE_NET_BIN_PATH()." setlocalsid $sid");
	if(!is_file("/etc/init.d/artica-process1")){return;}
	shell_exec("/etc/init.d/artica-process1 start");
}
function samba_password(){
	$password=base64_decode($_GET["smbpass"]);
	$file="/usr/share/artica-postfix/bin/install/smbldaptools/smbencrypt";
	$unix=new unix();
	$tmp=$unix->FILE_TEMP();
	$cmd=escapeshellarg($file)." ".escapeshellarg($password)." >".escapeshellarg($tmp)." 2>&1";
	shell_exec($cmd);
	$results=explode("\n",@file_get_contents($tmp));
	@unlink($tmp);
	writelogs_framework("SambaLoadpasswd ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	
	
	echo "<articadatascgi>". base64_encode(implode(" ",$results))."</articadatascgi>";
	
}

function samba_shares_list(){
	$ini=new iniFrameWork("/etc/samba/smb.conf");
	foreach ($ini->_params as $num=>$array){
		if(trim($array["path"])==null){continue;}
		if(!is_dir(trim($array["path"]))){continue;}
			$results[]=$array["path"];
	}
	
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
}







	



function RestartDHCPDService(){
	NOHUP_EXEC("/etc/init.d/isc-dhcp-server restart");		
}


function RestartFetchmail(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup /etc/init.d/fetchmail restart >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
}

function RestartOpenVPNServer(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart openvpn");
}

function RestartstrongSwanServer(){
	NOHUP_EXEC("/etc/init.d/ipsec restart");
}


function ReconfigureCyrusImapDaemon(){
	if(isset($_GET["force"])){$force=" --force";}
	NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-install --reconfigure-cyrus$force");
	NOHUP_EXEC("/etc/init.d/postfix restart-single");
}

function ChangeSSLCertificate(){
	NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-install --change-certificate");
	}









function postfixQueues(){
	$p=new postfix_system();
	$datas=serialize($p->getQueuesNumber());
	echo "<articadatascgi>".serialize($p->getQueuesNumber())."</articadatascgi>";
}








function postfix_multi_stat(){
	$instance=$_GET["postfix-mutli-stat"];
	$unix=new unix();
	$queue_directory=$unix->POSTCONF_MULTI_GET($instance,"queue_directory");
	$pidpath="$queue_directory/pid/master.pid";
	$pid=trim(@file_get_contents($pidpath));
	$path="/proc/$pid/exe";
	writelogs_framework("POSTFIX_MULTI_PID::queudir:$queue_directory [$instance] proc:$path  pid:($pidpath) $pid",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	
	
	$version=$unix->POSTFIX_VERSION();
	if($version==null){
		$array[0]=-2;
		$array[1]=$version;
		$array[2]=$pid;
		$array[3]=$path;
		echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";
		return ;
	}
	
	
	if(is_file($path)){
		
		$array[0]=1;
		$array[1]=$version;
		$array[2]=$pid;
		$array[3]=$path;
	}else{
		$pid=null;
		$array[0]=0;
		$array[1]=$version;
		$array[2]=null;
		$array[3]=$path;
	}
echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";	
}



function shellEscapeChars($path){
		$unix=new unix();
		return $unix->shellEscapeChars($path);	
}







function filestat(){
	$path=base64_decode($_GET["filestat"]);
	$path=utf8_encode($path);
	if(isset($_GET["filename"])){
		$filename=base64_decode($_GET["filename"]);
		$path=$path."/$filename";
	}
	
	// Pentest 2026-11-04 : stat root limite a la quarantaine c-icap (seul appelant : exec.cicap.php).
	$path=cmd_allowed_file($path,cmd_quarantine_roots());
	if($path===""){echo "<articadatascgi>".base64_encode(serialize(array()))."</articadatascgi>";return;}
	$unix=new unix();
	$array=$unix->alt_stat($path);
	if(!is_array($array)){writelogs_framework("ERROR stat -> $path",__FUNCTION__,__FILE__,__LINE__);}
	
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";	
}





















function disks_list(){
	$unix=new unix();
	$array=$unix->DISK_LIST();
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}
function disk_get_mounted_point(){
	$dev=base64_decode($_GET["get-mounted-path"]);
	$unix=new unix();
	echo "<articadatascgi>". base64_encode($unix->MOUNTED_PATH($dev))."</articadatascgi>";
}











function file_content(){
	// Pentest 2026-11-04 : lecture root limitee a la quarantaine c-icap (seul appelant : exec.cicap.php).
	$datas="";
	$base=realpath("/home/c-icap/VirSaveDir");
	$real=realpath(base64_decode($_GET["file-content"]));
	if($base!==false && $real!==false && strpos($real,$base."/")===0 && is_file($real)){
		$datas=@file_get_contents($real);
	}
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";	
}






function TCP_NICS_STATUS_ARRAY(){
	$unix=new unix();
	$ALLARRAY=$unix->NETWORK_ALL_INTERFACES();	
	writelogs_framework(" TCP_NICS_STATUS_ARRAY: ".count($ALLARRAY)." elements ",__FUNCTION__,__FILE__,__LINE__);
	$sortie=base64_encode(serialize($ALLARRAY));
	echo "<articadatascgi>$sortie</articadatascgi>";
}


function samba_logon_scripts(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.samba.php --logon-scripts >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec(trim($cmd));	
}



function MalwarePatrolDatabasesCount(){
	$datas=explode("\n",@file_get_contents("/etc/squid3/malwares.acl"));
	$count=0;
	foreach ( $datas as $num=>$line ){
		if(trim($line)==null){continue;}
		if(substr($line,0,1)=="#"){continue;}
		$count=$count+1;
	}
	echo "<articadatascgi>$count</articadatascgi>";
	
}































function ifconfig_all_ips(){
	$unix=new unix();
	$data=$unix->ifconfig_all_ips();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ifconfig_all_ips", serialize($data));
	echo "<articadatascgi>". base64_encode(serialize($data))."</articadatascgi>";
	
}












	
	
function lspci(){
	$unix=new unix();
	$lspci=$unix->find_program("lspci");
	exec("$lspci 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function dfmoinsh(){
	$unix=new unix();
	$prog=$unix->find_program("df");
	exec("$prog -h 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
}
function printenv(){
	$unix=new unix();
	$prog=$unix->find_program("printenv");
	exec("$prog 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
}


function disk_format_big_partition(){
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/system.partition.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/system.partition.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	
	@touch($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	
	
	@chmod($GLOBALS["LOGSFILES"], 0777);
	@chmod($GLOBALS["CACHEFILE"], 0777);
	$nofstab=null;
	if(isset($_GET["nofstab"])){$nofstab=" --nofstab ";}
	
	$cmd=LOCATE_PHP5_BIN2() ." /usr/share/artica-postfix/exec.system.build-partition.php --full \"{$_GET["dev"]}\" \"{$_GET["label"]}\" \"{$_GET["fs_type"]}\"{$nofstab} >{$GLOBALS["LOGSFILES"]} 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__);			
	NOHUP_EXEC($cmd);
}

function rsync_load_config(){
	$datas=@file_get_contents("/etc/rsync/rsyncd.conf");
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";		
}
function rsync_save_conf(){
	$datas=base64_decode($_GET["rsync-save-conf"]);
	@file_put_contents("/etc/rsync/rsyncd.conf",$datas);
	NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-install --reload-rsync");
}

function postfix_others_values(){
	$unix=new unix();
	$instance_id=intval($_GET["instance-id"]);
	$unix->framework_execute("exec.postfix.maincf.php --others-values --instance-id=$instance_id","postfix.othervalues.progress.log","postfix.othervalues.progress.log");
}




function cicap_reconfigure(){
	$unix=new unix();
	$unix->CICAP_SERVICE_EVENTS("Building ICAP service", __FILE__,__LINE__);
	if(isset($_GET["tenir"])){

		shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.c-icap.php --build");
		return;
	}
	
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.c-icap.php --build");
}

function cicap_reload(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart cicap");
}

function cicap_restart(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart cicap");
}





function dansguardian_community_categories(){
	$GLOBALS["SquidPerformance"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	if($GLOBALS["SquidPerformance"]>2){return;}
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.web-community-filter.php --export");
}





	
function postfix_luser_relay(){
	$unix=new unix();
	$instance_id=intval($_GET["instance-id"]);
	$unix->framework_exec("exec.postfix.maincf.php --luser-relay --reload --instance-id=$instance_id");
}

	

function milter_greylist_reconfigure(){
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	if(isset($_GET["hostname"])){
		if($_GET["hostname"]<>"master"){
			$cmdp=" --hostname={$_GET["hostname"]} --ou=\"{$_GET["ou"]}\"";
			$cmd="$nohup $php5 /usr/share/artica-postfix/exec.milter-greylist.php$cmdp --who=WebInterface >/dev/null 2>&1 &";
			writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
			shell_exec($cmd);
			return;
		}
	}

	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.milter-greylist.php --reload-single >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}


















function system_debian_kernel(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.apt-cache.kernel.php --detect");
}






	






function directory_chown(){
	$path=utf8_encode(base64_decode($_GET["chown"]));
	$uid=base64_decode($_GET["uid"]);
	// Pentest 2026-11-04 : chown root limite a /home/ (traversee) et arguments quotes (injection shell).
	if(!preg_match('#^[A-Za-z0-9_.$-]+$#',$uid)){echo "<articadatascgi>".base64_encode("Invalid user")."</articadatascgi>";return;}
	if(strpos($path,"/home/")!==0 || strpos($path,"..")!==false){echo "<articadatascgi>".base64_encode("Invalid path")."</articadatascgi>";return;}
	if(!is_dir($path)){@mkdir($path,0755,true);}
	$base=realpath("/home");
	$path=realpath($path);
	if($base===false || $path===false || strpos($path,$base."/")!==0){echo "<articadatascgi>".base64_encode("Invalid path")."</articadatascgi>";return;}
	
	$unix=new unix();
	$cmd=$unix->find_program("chown")." -- ".escapeshellarg($uid)." ".escapeshellarg($path)." 2>&1";
		
	exec($cmd,$results);
	writelogs_framework("\n$cmd\n". @implode("\n",$results) ,__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}




	













function openvpn_server_status(){
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --openvpn --nowachdog >/usr/share/artica-postfix/ressources/logs/web/openvpn.status 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	echo "<articadatascgi>". base64_encode(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/openvpn.status"))."</articadatascgi>";		
}















function iptables_save(){
	$unix=new unix();
	$iptables_save=$unix->find_program("iptables-save");
	$cmd="$iptables_save 2>&1";
	exec($cmd,$results);
	writelogs_framework(count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
	$socks=new sockets();
	$socks->SET_INFO("IPTABLES_SAVE_DUMP",serialize($results));
}




	








function SARG_PASSWORDS(){

}

function qos_compile(){
	//qos-compile
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup ". LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.qos.php --build >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}


	
	




function artica_meta_user(){
	$uid=$_GET["artica-meta-user"];
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.artica.meta.users.php --user \"$uid\" >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}

function artica_meta_computer(){
	$uid=$_GET["artica-meta-computer"];
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.artica.meta.users.php --computer \"$uid\" >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}
function artica_meta_fetchmail_rules(){
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.artica.meta.users.php --export-fetchmail-rules >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function artica_meta_ovpn(){
	$uid=$_GET["uid"];
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.artica.meta.users.php --ovpn \"$uid\"";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	NOHUP_EXEC($cmd);	
}



function artica_meta_user_export_dns(){
	$ArticaMetaEnabled=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaMetaEnabled"));
	if($ArticaMetaEnabled<>1){return;}
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.artica.meta.users.php --export-all-dns >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}

function artica_meta_export_openvpn_sites(){
	$ArticaMetaEnabled=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaMetaEnabled"));
	if($ArticaMetaEnabled<>1){return;}
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.artica.meta.users.php --export-openvpn-sites >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
	
}





//artica-meta-export-dns





function pureftpd_users(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");	
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.pureftpd.php >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}


function WakeOnLan(){
	$wol=new WakeOnLanClass(null);
	$wol->wake(base64_decode($_GET["wake-on-lan"]));
	echo "<articadatascgi>". base64_encode(@implode("\n",$wol->error))."</articadatascgi>";
	
}


function postfix_freeze(){

	$instance_id=intval($_GET["instance-id"]);
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/postqueue";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/postqueue.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);


	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.maincf.php --freeze --instance-id=$instance_id";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	NOHUP_EXEC($cmd);

}






function ping(){
	$ip=trim($_GET["ip"]);

	if(!preg_match("#^([0-9\.]+)$#",$ip)){
		echo "<articadatascgi>FALSE</articadatascgi>";
		return false;
	}

	$unix=new unix();
	
	$tmp=$unix->TEMP_DIR();
		if(trim($ip)==null){return false;}
		$ftmp="$tmp/". md5(__FILE__);
		exec("/bin/ping -q -c 1 -s 16 -W1 -Q 0x02 $ip >$ftmp 2>&1");
		$results=explode("\n",@file_get_contents($ftmp) );
		@unlink($ftmp);
		if(!is_array($results)){return false;}
		foreach ($results as $line){
			if(preg_match("#[0-9]+\s+[a-zA-Z]+\s+[a-zA-Z]+,\s+([0-9]+)\s+received#",$line,$re)){
				if($re[1]>0){
					$ping_check=true;
				}else{
					$ping_check=false;
				}
			}
		}
	if ($ping_check){echo "<articadatascgi>TRUE</articadatascgi>";return;}
	echo "<articadatascgi>FALSE</articadatascgi>";
	return true;
}




function SetServerTime(){
	$time=$_GET["SetServerTime"];
	$unix=new unix();
	$bin_date=$unix->find_program("date");
	$cmd="$bin_date $time 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". @implode("\n",$results)."</articadatascgi>";
}

function syslog_master_mode(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim($nohup." ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.syslog-engine.php --build-server >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}





















function chmod_access(){
	$unix=new unix();
	$stat=$unix->find_program("stat");
	$_GET["chmod-access"]=$unix->shellEscapeChars($_GET["chmod-access"]);
	$cmd="$stat {$_GET["chmod-access"]} 2>&1";
	exec($cmd,$results);
	writelogs_framework("$cmd = ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	foreach ($results as $index=>$line){
		if(preg_match("#Access:.+?\(([0-9]+)\/(.+?)\)\s+Uid:\s+\(.+?\/(.+?)\)\s+Gid:\s+\(.+?\/(.+?)\)#i",$line,$re)){
			writelogs_framework("$line MATCH",__FUNCTION__,__FILE__,__LINE__);
			echo "<articadatascgi>". base64_encode(serialize($re))."</articadatascgi>";
		}
	}
	
}


function acls_apply(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim($nohup. " ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.acls.php --acls-single {$_GET["acls-apply"]} >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function acls_delete(){
	$unix=new unix();
	$setfacl=$unix->find_program("setfacl");	
	$dir=$unix->shellEscapeChars($_GET["acls-delete"]);	
	$cmd="$setfacl -b $dir 2>&1";
	exec("$cmd",$events);	
}
function NOHUP_EXEC($cmdline){
	$cmdline=str_replace(">/dev/null 2>&1 &", "", $cmdline);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmdfinal="$nohup $cmdline >/dev/null 2>&1 &";
	writelogs_framework("$cmdfinal",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmdfinal);
	
}




?>
