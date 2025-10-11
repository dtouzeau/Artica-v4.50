<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");

if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["infos"])){infos();exit;}
if(isset($_GET["logoff"])){logoff();exit;}
if(isset($_GET["disconnect"])){disconnect();exit;}
if(isset($_GET["connect"])){connect();exit;}
if(isset($_GET["restart"])){restart();exit;}


writelogs_framework("urbackup... Unable to understand the query",__FUNCTION__,__FILE__,__LINE__);
function status():bool{
    $unix=new unix();
    return $unix->framework_exec("exec.satus.php --urbackup-server","urbackup.exec");
}



function restart():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.urbackup-server.php --restart","urbackup.progress","urbackup.log");
}



function openvpn_syslog(){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);

	$unix=new unix();
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$MAIN=unserialize(base64_decode($_GET["syslog"]));
	$PROTO_P=null;
	
	foreach ($MAIN as $val=>$key){
		
		writelogs_framework("$val, $key",__FUNCTION__,__FILE__,__LINE__);
		$MAIN[$key]=str_replace(".", "\.", $MAIN[$key]);
		$MAIN[$key]=str_replace("*", ".*?", $MAIN[$key]);
	
	}
	
	$max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
	$date=$MAIN["DATE"];
	$PROTO=$MAIN["PROTO"];
	$SRC=$MAIN["SRC"];
	$DST=$MAIN["DST"];
	$SRCPORT=$MAIN["SRCPORT"];
	$DSTPORT=$MAIN["DSTPORT"];
	$IN=$MAIN["IN"];
	$OUT=$MAIN["OUT"];
	$MAC=$MAIN["MAC"];
	if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}
	if($SRC<>null){$SRC_P=".*?\/$SRC.*?";}
	if($SRCPORT<>null){$SRCPORT_P=".*?:$SRCPORT.*?";}
	if($MAIN["C"]==0){$TERM_P=$TERM;}
	
	
	$mainline="{$TERM_P}{$SRC_P}{$PROTO_P}{$SRCPORT_P}";
	if($TERM<>null){
		if($MAIN["C"]>0){
			$mainline="($mainline|$TERM)";
		}
	}
	
	$search="$date.*?$mainline";
	$cmd="$grep -iE '$search' /var/log/openvpn/openvpn.log |$tail -n $max >/usr/share/artica-postfix/ressources/logs/web/openvpn.syslog 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}


function enable_service(){
	$GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/openvpn.enable.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/openvpn.enable.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.openvpn.enable.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function rebuild_certificate() {
	$GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/openvpn.enable.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/openvpn.enable.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.openvpn.enable.php --certificate >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function disable_service(){
	$GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/openvpn.enable.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/openvpn.enable.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.openvpn.disable.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
	
function vpn_client_running(){
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
	$id=$_GET["is-client-running"];
	$pid=trim(@file_get_contents("/etc/artica-postfix/openvpn/clients/$id/pid"));
	$unix=new unix();
	writelogs_framework("/etc/artica-postfix/openvpn/clients/$id/pid -> $pid",__FUNCTION__,__FILE__,__LINE__);
	
	if($unix->process_exists($pid)){
		echo "<articadatascgi>TRUE</articadatascgi>";
		return;
	}
	writelogs_framework("$id: pid $pid",__FUNCTION__,__FILE__,__LINE__);
	
	exec($unix->find_program("pgrep") ." -l -f \"openvpn.+?clients\/2\/settings.ovpn\" 1>&1",$results);
	foreach ($results as $num=>$ligne){
		if(preg_match("#^([0-9]+)\s+.*openvpn#",$ligne)){
			writelogs_framework("pid= preg_match= {$re[1]}",__FUNCTION__,__FILE__,__LINE__);
			echo "<articadatascgi>TRUE</articadatascgi>";
			return;
		}
	}
	writelogs_framework("$pid NOT RUNNING",__FUNCTION__,__FILE__,__LINE__);
}	


function BuildWindowsClient(){
	$uid=$_GET["build-vpn-user"];
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/openvpn.client.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/openvpn.client.log";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.openvpn.build-client.php \"$uid\" >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}


function ChangeCommonName($commonname){

if(!is_file("/etc/artica-postfix/openvpn/openssl.cnf")){
	echo "<articadatascgi>ERROR: Unable to stat /etc/artica-postfix/openvpn/openssl.cnf</articadatascgi>";
	return false;
}
	
$tbl=explode("\n",@file_get_contents("/etc/artica-postfix/openvpn/openssl.cnf"));
foreach ($tbl as $num=>$ligne){
	if(preg_match("#^commonName_default#",$ligne)){
		$tbl[$num]="commonName_default=\t$commonname";
	}
}

@file_put_contents("/etc/artica-postfix/openvpn/openssl.cnf",implode("\n",$tbl));
return true;
}

function vpn_client_events(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$tail=$unix->find_program("tail");
	$cmd=trim("$tail -n 300 /etc/artica-postfix/openvpn/clients/{$_GET["ID"]}/log 2>&1 ");
	
	exec($cmd,$results);		
	writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function vpn_client_hup(){
	$pid=@file_get_contents("/etc/artica-postfix/openvpn/clients/{$_GET["ID"]}/pid");
	$unix=new unix();
	$kill=$unix->find_program("kill");
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");		
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.openvpn.php --client-configure-start {$_GET["ID"]} 2>&1 &");
	if($unix->process_exists($pid)){unix_system_kill_force($pid);}
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$cmd");	
	
}

function vpn_client_reconfigure(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.openvpn.php --client-conf 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$cmd");
	
}


?>