<?php
ini_set('error_reporting', E_ALL);
if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);	
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	
	$GLOBALS["VERBOSE"]=true;
}
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["simulate"])){cicap_simulate();exit;}
if(isset($_GET["DisableICAPTemporary"])){DisableICAPTemporary();exit;}
if(isset($_GET["access-real"])){access_real();exit;}
if(isset($_GET["searchlogs"])){searchlogs();exit;}
if(isset($_GET["icap-events"])){searchlogs_icap();exit;}
if(isset($_GET["restart"])){restart_progress();exit;}
if(isset($_GET["reload"])){reload();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["connect"])){connect();exit;}
if(isset($_GET["disconnect"])){disconnect();exit;}
if(isset($_GET["reconfigure"])){reconfigure();exit;}
if(isset($_GET["install-progress"])){install_progress();exit;}
if(isset($_GET["uninstall-progress"])){uninstall_progress();exit;}
if(isset($_GET["connect-progress"])){connect_progress();exit;}
if(isset($_GET["disconnect-progress"])){disconnect_progress();exit;}
if(isset($_GET["kwts-install"])){kwts_install();exit;}
if(isset($_GET["kwts-uninstall"])){kwts_uninstall();exit;}
if(isset($_GET["install-clamav"])){clamav_install();exit;}
if(isset($_GET["uninstall-clamav"])){clamav_uninstall();exit;}

if(isset($_GET["uninstall-sandbox"])){sandbox_uninstall();exit;}
if(isset($_GET["install-sandbox"])){sandbox_install();exit;}
if(isset($_GET["checks"])){cicap_checks();exit;}
if(isset($_GET["sandbox-file"])){sandbox_file();exit;}
if(isset($_GET["sandbox-events"])){searchlogs_sandbox();exit;}
if(isset($_GET["clients-scan"])){clients_scan();exit;}

foreach ($_GET as $a=>$b){
    $c[]="$a -> $b";
}
writelogs_framework("unable to understand ".@implode(", ",$c)." query !!!!! ",__FUNCTION__,__FILE__,__LINE__);

function install_switch(){
	$CicapEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled"));
	if($CicapEnabled==1){
		install_progress();
	}else{
		uninstall_progress();
	}
	
}

function clients_scan(){
    $unix=new unix();
    $unix->framework_exec("exec.squid.watchdog.php --icap");
}

function kwts_install(){
    $unix=new unix();
    $unix->framework_execute("exec.c-icap.install.php --kwts-install","kwts.progress","kwts.log");
}
function kwts_uninstall(){
    $unix=new unix();
    $unix->framework_execute("exec.c-icap.install.php --kwts-uninstall","kwts.progress","kwts.log");
}
function clamav_install(){
    $unix=new unix();
    $unix->framework_execute("exec.c-icap.install.php --install-clamav","cicap.install.progress","cicap.install.log");
}
function sandbox_install(){
    $unix=new unix();
    $unix->framework_execute("exec.c-icap.install.php --install-sandbox","cicap.install.progress","cicap.install.log");
}
function clamav_uninstall(){
    $unix=new unix();
    $unix->framework_execute("exec.c-icap.install.php --uninstall-clamav","cicap.install.progress","cicap.install.log");
}
function sandbox_uninstall(){
    $unix=new unix();
    $unix->framework_execute("exec.c-icap.install.php --uninstall-sandbox","cicap.install.progress","cicap.install.log");
}
function install_progress():bool{
    $unix=new unix();
    $addon="";
    if(isset($_GET["with-clamav"])){
        $addon=" --with-clamav";
    }

    $unix->framework_execute("exec.c-icap.install.php --install$addon","cicap.install.progress","cicap.install.log");
    return true;
}
function uninstall_progress(){
    $unix=new unix();
    $unix->framework_execute("exec.c-icap.install.php --uninstall","cicap.install.progress","cicap.install.log");
}

function disconnect_progress(){
    $unix=new unix();
    $unix->framework_execute("exec.c-icap.install.php --disconnect","cicap.install.progress","cicap.install.log");
}

function connect_progress(){
    $unix=new unix();
    $unix->framework_execute("exec.c-icap.install.php --connect","cicap.install.progress","cicap.install.log");
}

function cicap_checks(){
    $unix=new unix();
    $unix->framework_exec("exec.c-icap.install.php --cicap-checks");
}

function restart_progress(){
	$unix=new unix();
    $unix->framework_execute("exec.c-icap.php --restart","c-icap.restart.progress","c-icap.restart.progress.txt");

}
function cicap_simulate(){
    $unix=new unix();
    $fname=base64_encode($_GET["simulate"]);
    $php5=$unix->LOCATE_PHP5_BIN();
    writelogs_framework("exec.c-icap.php --simulate $fname" ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$php5 /usr/share/artica-postfix/exec.c-icap.php --simulate $fname");


}

function DisableICAPTemporary(){
	$DisableICAPTemporary=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableICAPTemporary");
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	if($DisableICAPTemporary==1){
		if(!is_file("/etc/squid3/icap.conf.bak")){
			@copy("/etc/squid3/icap.conf", "/etc/squid3/icap.conf.bak");
			@file_put_contents("/etc/squid3/icap.conf", "\n");
			squid_admin_mysql(1, "The ICAP feature was disabled", null,__FILE__,__LINE__);
			
			if( is_file($squidbin)){ 
				squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
				system("/usr/sbin/artica-phpfpm-service -reload-proxy");
			}
			
		}else{
			$size=@filesize("/etc/squid3/icap.conf");
			if($size>10){
				@unlink("/etc/squid3/icap.conf.bak");
				@copy("/etc/squid3/icap.conf", "/etc/squid3/icap.conf.bak");
			}
			@file_put_contents("/etc/squid3/icap.conf", null);
			squid_admin_mysql(1, "The ICAP feature was disabled", null,__FILE__,__LINE__);
			if( is_file($squidbin)){ 
				squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
				system("/usr/sbin/artica-phpfpm-service -reload-proxy");
			}
		}
		
	return;
	}
	
	if($DisableICAPTemporary==0){	
		if(!is_file("/etc/squid3/icap.conf.bak")){
			squid_admin_mysql(1, "The ICAP feature was re-enabeld [action=reconfigure]", null,__FILE__,__LINE__);
			$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &";
			writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
			shell_exec($cmd);
			return;
		}
		
		@unlink("/etc/squid3/icap.conf");
		@copy("/etc/squid3/icap.conf.bak", "/etc/squid3/icap.conf");
		squid_admin_mysql(1, "The ICAP feature was re-enabled", null,__FILE__,__LINE__);
		if( is_file($squidbin)){ 
			squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
			system("/usr/sbin/artica-phpfpm-service -reload-proxy");
		}
	}
	
}

function reconfigure(){
    $unix=new unix();
    $unix->framework_execute("exec.c-icap.php --reconfigure","c-icap.restart.progress","c-icap.restart.progress.txt");

}
function sandbox_file(){
    $file=base64_encode($_GET["sandbox-file"]);
    $unix=new unix();
    $unix->framework_exec("exec.c-icap.sandbox.php --upload $file &");
}

function reload(){
    $unix=new unix();
    $unix->framework_exec("exec.c-icap.php --reload");
}

function connect(){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/c-icap.connect.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/c-icap.connect.progress.txt";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");

	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.c-icap.connect.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function disconnect(){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/c-icap.connect.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/c-icap.connect.progress.txt";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.c-icap.connect.php --disconnect >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function access_real(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$targetfile="/usr/share/artica-postfix/ressources/logs/access.log.tmp";
	$query2=null;
	$sourceLog="/var/log/c-icap/access.log";
	if(isset($_GET["ViaMaster"])){
		$sourceLog="/var/log/squid/childs-access.log";
		$targetfile="/usr/share/artica-postfix/ressources/logs/ViaMaster.log.tmp";
	}

	$rp=intval($_GET["rp"]);
	writelogs_framework("access_real -> $rp search {$_GET["query"]} SearchString = {$_GET["SearchString"]}" ,__FUNCTION__,__FILE__,__LINE__);

	$query=$_GET["query"];
	if($_GET["SearchString"]<>null){
		$query2=$query;
		$query=$_GET["SearchString"];
	}

	$grep=$unix->find_program("grep");


	$cmd="$tail -n $rp $sourceLog >$targetfile 2>&1";

	if($query2<>null){
		$pattern2=str_replace(".", "\.", $query2);
		$pattern2=str_replace("*", ".*?", $pattern2);
		$pattern2=str_replace("/", "\/", $pattern2);
		$cmd2="$grep --binary-files=text -Ei \"$pattern2\"| ";
		$cmd3="$grep --binary-files=text -Ei \"$pattern2\"";
	}

	if($query<>null){
		if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
			$pattern=str_replace(".", "\.", $query);
			$pattern=str_replace("*", ".*?", $pattern);
			$pattern=str_replace("/", "\/", $pattern);
		}
	}
	if($pattern<>null){

		$cmd="$grep --binary-files=text -Ei \"$pattern\" $sourceLog|$cmd2$tail -n $rp  >$targetfile 2>&1";
	}else{
		if($cmd3<>null){
			$cmd="$cmd3 $sourceLog|$cmd2 $tail -n $rp  >$targetfile 2>&1";
		}

	}



	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	@chmod("$targetfile",0755);
}
function searchlogs_sandbox(){
    $search=trim(base64_decode($_GET["sandbox-events"]));
    $target_file=PROGRESS_DIR."/sandbox.log";
    $source_file="/var/log/squid/icap-sandbox.log";

    $unix=new unix();
    $tail=$unix->find_program("tail");
    $grep=$unix->find_program("grep");
    $rp=500;
    if(is_numeric($_GET["rp"])){$rp=intval($_GET["rp"]);}

    if($search==null){
        $cmd="$tail -n $rp $source_file >$target_file 2>&1";
        writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
        return;
    }

    $search=$unix->StringToGrep($search);
    $cmd="$grep --binary-files=text -i -E '$search' $source_file 2>&1|$tail -n $rp >$target_file 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$cmd");

}
function searchlogs_icap(){
    $search=trim(base64_decode($_GET["icap-events"]));
    $target_file=PROGRESS_DIR."/icap.log";
    $source_file="/var/log/squid/icap.log";

    $unix=new unix();
    $tail=$unix->find_program("tail");
    $grep=$unix->find_program("grep");
    $rp=intval($_GET["rp"]);
    if($rp==0){ $rp=500;}
    if($rp>1500){ $rp=1500;}

    if($search==null){
        $cmd="$tail -n $rp $source_file >$target_file 2>&1";
        writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
        return;
    }

    $search=$unix->StringToGrep($search);
    $cmd="$grep --binary-files=text -i -E '$search' $source_file 2>&1|$tail -n $rp >$target_file 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$cmd");

}

function searchlogs(){
	$search=trim(base64_decode($_GET["searchlogs"]));
    $target_file=PROGRESS_DIR."/c-icap.log";
    $source_file="/var/log/c-icap/c-icap-server.log";
	
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");
	$rp=500;
	if(is_numeric($_GET["rp"])){$rp=intval($_GET["rp"]);}

	if($search==null){
		$cmd="$tail -n $rp $source_file >$target_file 2>&1";
		writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
		return;
	}

	$search=$unix->StringToGrep($search);
	$cmd="$grep --binary-files=text -i -E '$search' $source_file 2>&1|$tail -n $rp >$target_file 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$cmd");
}

function status(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.status.php --c-icap --nowachdog >/usr/share/artica-postfix/ressources/logs/web/cicap.status 2>&1";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}