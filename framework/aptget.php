<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["check"])){CHECK();exit;}
if(isset($_GET["reload"])){reload();exit;}
if(isset($_GET["rebuild-database"])){rebuild_database();exit;}
if(isset($_GET["replic"])){replic_artica_servers();exit;}
if(isset($_GET["digg"])){digg();exit;}
if(isset($_GET["repair-tables"])){repair_tables();exit;}
if(isset($_GET["build-smooth-tenir"])){reload_tenir();exit;}
if(isset($_GET["reconfigure"])){reconfigure();exit;}
if(isset($_GET["import-file"])){import_fromfile();exit;}
if(isset($_GET["events-query"])){events_query();exit;}
if(isset($_GET["uninstall"])){uninstall_itchart();exit;}
if(isset($_GET["install"])){install_itchart();exit;}
if(isset($_GET["activate-ufdb"])){activate_ufdb();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["poweradmin-install"])){poweradmin_install();exit;}
if(isset($_GET["poweradmin-disable"])){poweradmin_disable();exit;}
if(isset($_GET["poweradmin-enable"])){poweradmin_enable();exit;}
if(isset($_GET["reload-poweradmin"])){poweradmin_reload();exit;}
if(isset($_GET["activate-dsc"])){dsc_install();exit;}
if(isset($_GET["disable-dsc"])){dsc_uninstall();exit;}
if(isset($_GET["apt-get-systemd"])){REMOVE_SYSTEMD();exit;}
if(isset($_GET["restart-recusor"])){recursor_restart();exit;}
if(isset($_GET["debian-mirror"])){debian_mirror_install();exit;}
if(isset($_GET["debian-mirror-uninstall"])){debian_mirror_uninstall();}
if(isset($_GET["apt-mirror-ini-status"])){APT_MIRROR_STATUS();exit;}
if(isset($_GET["apt-mirror-schedule"])){APT_MIRROR_SCHEDULE();exit;}
if(isset($_GET["apt-mirror-stop"])){APT_MIRROR_STOP();exit;}
if(isset($_GET["apt-mirror-start"])){APT_MIRROR_START();exit;}
if(isset($_GET["apt-mirror-save"])){APT_MIRROR_CONFIG();exit;}
if(isset($_GET["debian-mirror-remove"])){APT_MIRROR_REMOVE();exit;}
if(isset($_GET["debian-mirror-ismove"])){APT_MIRROR_MOVE_RUNING();exit;}
if(isset($_GET["apt-mirror-move"])){APT_MIRROR_MOVE_START();exit;}
if(isset($_GET["config-client"])){APT_CLIENT_CONF();exit;}
if(isset($_GET["syslog-mirror"])){SearchInSyslog();exit;}

writelogs_framework("Unable to understand the query ".@implode(" ",$_GET),__FUNCTION__,__FILE__,__LINE__);





function CHECK(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/aptget.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/aptget.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.apt-get.php --print-upgrade --force >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	

}

function APT_CLIENT_CONF(){
    system("/usr/sbin/artica-phpfpm-service -sources-list");
}




function activate_ufdb(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/pdns.first.install";
	$GLOBALS["CACHEFILE"]=$GLOBALS["PROGRESS_FILE"];
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/pdns.first.install.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdns_server.install.php --install-ufdb >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}





function debian_mirror_install(){
    $unix=new unix();
    $unix->framework_execute("exec.apt-mirror.php --install",
        "debian-mirror.progress",
        "debian-mirror.log");
}
function debian_mirror_uninstall(){
    $unix=new unix();
    $unix->framework_execute("exec.apt-mirror.php --uninstall",
        "debian-mirror.progress",
        "debian-mirror.log");
}
function APT_MIRROR_REMOVE(){
    $unix=new unix();
    $unix->framework_execute("exec.apt-mirror.php --rmdir",
        "debian-mirror.progress",
        "debian-mirror.log");
}

function APT_MIRROR_STATUS(){
    shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --apt-mirror --nowachdog");
}
function APT_MIRROR_STOP(){
    $unix=new unix();
    $pid=$unix->PIDOF_PATTERN("perl /bin/apt-mirror");
    writelogs("apt-mirror, pid=$pid",__FUNCTION__,__FILE__,__LINE__);
    if(!$unix->process_exists($pid)){return true;}
    writelogs("apt-mirror, kill=$pid",__FUNCTION__,__FILE__,__LINE__);
    $unix->KILL_PROCESS($pid,9);
    $unix->framework_exec("exec.apt-mirror.php --stop2");
    return true;
}
function APT_MIRROR_START(){
    $unix=new unix();
    $unix->framework_exec("exec.apt-mirror.php --perform");
}
function APT_MIRROR_MOVE_RUNING(){
    $unix=new unix();
    $pids=$unix->PIDOF_PATTERN("rsync -arctuxzv --remove-source-files");
    writelogs_framework("rsync --> $pids....",__FUNCTION__,__FILE__,__LINE__);
    $FILE=PROGRESS_DIR."/APT_MIRROR_MOVE_RUNING";
    if($unix->process_exists($pids)){
        @file_put_contents($FILE,1);
        return true;
    }
    @file_put_contents($FILE,0);
    return true;
}
function APT_MIRROR_MOVE_START(){
    $unix=new unix();
    $unix->framework_exec("exec.apt-mirror.php --move-folder");
}


function APT_MIRROR_CONFIG(){
    $unix=new unix();
    $unix->framework_exec("exec.apt-mirror.php --build");
    $unix->framework_exec("exec.apt-mirror.php --restart");
}

function APT_MIRROR_SCHEDULE(){
    $cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.apt-mirror.php --schedules";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function replic_artica_servers(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.pdns.php --replic-artica 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);	
	
}
function digg(){
	$unix=new unix();
	$digg=$unix->find_program("dig");
	if(!is_file($digg)){
		echo "<articadatascgi>".base64_encode(serialize(array("dig, nos such binary")))."</articadatascgi>";
		return;
	}
	
	$hostname=$_GET["hostname"];
	$interface=$_GET["interface"];
	if($interface==null){$interface="127.0.0.1";}
	if($hostname==null){$hostname="www.google.com";}
	$cmd="$digg @$interface $hostname 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
	
}
function events_query(){
	$preprend=$_GET["prepend"];
	
	$pattern=trim(base64_decode($_GET["events-query"]));
	if($pattern=="yes"){$pattern=null;}
	$pattern=str_replace("  "," ",$pattern);
	$pattern=str_replace(" ","\s+",$pattern);
	$pattern=str_replace(".","\.",$pattern);
	$pattern=str_replace("*",".+?",$pattern);
	$pattern=str_replace("/","\/",$pattern);
	$syslogpath="/var/log/unbound.log";
	$maxrows=0;

	$unix=new unix();
	$grepbin=$unix->find_program("grep");
	$tail = $unix->find_program("tail");
	if($tail==null){return;}
	if(isset($_GET["prefix"])){
		if(trim($_GET["prefix"])<>null){
			if(strpos($_GET["prefix"], ",")>0){$_GET["prefix"]="(".str_replace(",", "|", $_GET["prefix"]).")";}
			$_GET["prefix"]=str_replace("*",".*?",$_GET["prefix"]);
			$pattern="{$_GET["prefix"]}.*?\[[0-9]+\].*?$pattern";
		}
	}
	
	if($preprend<>null){
		$grep="$grepbin '$preprend'";
		if(strpos($preprend, ",")>0){$grep="$grepbin -E '(".str_replace(",", "|", $preprend).")'";}
	}
	
	writelogs_framework("Pattern \"$pattern\"" ,__FUNCTION__,__FILE__,__LINE__);
	if(isset($_GET["rp"])){$maxrows=$_GET["rp"];}
	if($maxrows==0){$maxrows=500;}
	
	
	if(strlen($pattern)>1){
		if(($preprend<>null) && (strlen($preprend)>3)){
			$preprend="'".$preprend."'";
			if(strpos($preprend, ",")>0){$preprend=" -E '(".str_replace(",", "|", $preprend).")'";}
			$grep="$grepbin $preprend|$grepbin -i -E '$pattern'";}
			else{
				$grep="$grepbin -i -E '$pattern'";
			}
	}
	
	unset($results);
	$l=$unix->FILE_TEMP();
	
	if($grep<>null){
		$cmd="$tail -n 5000 $syslogpath|$grep|$tail -n $maxrows 2>&1";
	}else{
		$cmd="$tail -n $maxrows $syslogpath 2>&1";
	}
	
	
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	if(count($results)<3){
		$maxrows=$maxrows+2000;
		if($grep<>null){
			$cmd="$tail -n 5000 $syslogpath|$grep |$tail -n $maxrows 2>&1";
		}else{
			$cmd="$tail -n $maxrows $syslogpath 2>&1";
		}
		writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
		exec($cmd,$results);	
	}
	
	if(count($results)<3){
		$maxrows=$maxrows+5000;
		if($grep<>null){
			$cmd="$grep $syslogpath|$tail -n $maxrows 2>&1";
		}else{
			$cmd="$tail -n $maxrows $syslogpath 2>&1";
		}
		writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
		exec($cmd,$results);	
	}	
	
	
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/syslog.query", @implode("\n", $results));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/syslog.query", 0755);
	
}
function SearchInSyslog(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["syslog-mirror"]));
    $targetfile="/var/log/apt-mirror.log";
    $RFile=PROGRESS_DIR."/apt-mirror.syslog";
    $PFile=PROGRESS_DIR."/apt-mirror.syslog.pattern";

    foreach ($MAIN as $val=>$key){
        $MAIN[$val]=str_replace(".", "\.", $MAIN[$val]);
        $MAIN[$val]=str_replace("/", "\/", $MAIN[$val]);
        $MAIN[$val]=str_replace("*", ".*?", $MAIN[$val]);
    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    if($max==0){$max=100;}
    $date=$MAIN["DATE"];
    $search=$MAIN["TERM"];
    $search="$date.*?$search";
    $search=str_replace(".*?.*?",".*?",$search);


    $cmd="$grep --binary-files=text -i -E '$search' $targetfile |$tail -n $max >$RFile 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents($PFile, $search);
    shell_exec($cmd);

}