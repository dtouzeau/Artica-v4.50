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


if(isset($_GET["WindowsUpdateCachingDir"])){WindowsUpdateCachingDir();exit;}
if(isset($_GET["changedir"])){changedir();exit;}
if(isset($_GET["whitelist"])){whitelist();exit;}
if(isset($_GET["build"])){build();exit;}
if(isset($_GET["partition"])){partition();exit;}
if(isset($_GET["enable"])){enable();exit;}
if(isset($_GET["disable"])){disable();exit;}
if(isset($_GET["services-status"])){services_status();exit;}
if(isset($_GET["enable-progress"])){enable_progress();exit;}


foreach ($_GET as $num=>$line){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);

//-----------------------------------------------------------------------------------------------------------------------------------
function squidclient_mgr_storedir(){
	$unix=new unix();
	$data=$unix->squidclient("storedir",true);
	@unlink("/usr/share/artica-postfix/ressources/logs/web/storedir.cache");
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/storedir.cache", $data);
	
	
}

function WindowsUpdateCachingDir(){
	
	if(is_link("/home/squid/WindowsUpdate")){
		echo "<articadatascgi>". base64_encode(readlink("/home/squid/WindowsUpdate"))."</articadatascgi>";
		return;
	}
	
	echo "<articadatascgi>". base64_encode("/home/squid/WindowsUpdate")."</articadatascgi>";
}



//-----------------------------------------------------------------------------------------------------------------------------------
function watchdog_bandwidth(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php /usr/share/artica-postfix/exec.squid.watchdog.php --bandwidth-cron >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function squid_templates_background(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php php5 /usr/share/artica-postfix/exec.squid.templates.php --force --progress >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function enable(){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/hypercache.maintenance.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/squid.windowsupdate.enable.progress.log";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.hypercache-server.php --wsus-on >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function disable(){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/squid.windowsupdate.enable.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/squid.windowsupdate.enable.progress.log";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.windowsupdate.enable.php --disable >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function changedir(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/windowsupdate.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/windowsupdate.progress.log";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.windowsupdate.php --change-dir >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function services_status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --wsus >/usr/share/artica-postfix/ressources/logs/web/wsus.status";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function whitelist(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.windowsupdate.whitelist.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.windowsupdate.whitelist.progress.log";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.windowsupdate.php --whitelist >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function partition(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.windowsupdate.whitelist.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.windowsupdate.whitelist.progress.log";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.windowsupdate.php --partition --force --verbose >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function build(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.windowsupdate.enable.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.windowsupdate1.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	$array["POURC"]=2;$array["TEXT"]="{please_wait}";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/squid.windowsupdate1.progress", serialize($array));
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.windowsupdate.enable.php --reload >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}



function clean_logs_emergency(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.cleanlogs.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.cleanlogs.progress.txt";
	@mkdir("/usr/share/artica-postfix/ressources/logs/web",0755,true);
	@unlink($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["CACHEFILE"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.cleanlogs-emergency.php >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
	
}


function activedirectory_emergency_progress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.urgency.disable.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.ad.emergency.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["CACHEFILE"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));

	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.urgency.remove.php --ad-on >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}



//-----------------------------------------------------------------------------------------------------------------------------------
function remove_influx_db(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb.remove.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/influxdb.remove.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.influxdb.php --remove-db >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
//-----------------------------------------------------------------------------------------------------------------------------------
function test_ssl_port(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.testssl.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/squid.testssl.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.testssslports.php {$_GET["ID"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}






function SquidReloadInpublicAlias(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/SquidReloadInpublicAlias.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/SquidReloadInpublicAlias.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.php --SquidReloadInpublicAlias >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function disable_influx_db(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb.remove.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/influxdb.remove.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.influxdb.php --disable-db >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function enable_influx_db(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb.remove.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/influxdb.remove.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.influxdb.php --enable-db >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}



function allow_8083_port(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.ports.80.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.ports.80.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid80443.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}


function import_old_logs_files(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.statistics.import.local.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/squid.statistics.import.local.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.influx.import.php --scandir >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}



function it_chart_build(){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/ichart.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/itchart.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.itchart.php --build-rules >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function global_access_center(){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}



function squid_disable_ufdbemergency(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.urgency.disable.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.urgency.disable.progress.txt";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");

	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.urgency.remove.php --ufdb-off >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function squid_ports_conf(){
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid-ports.conf");
	@copy("/etc/squid3/listen_ports.conf","/usr/share/artica-postfix/ressources/logs/web/squid-ports.conf");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid-ports.conf",0755);
}
function squid_ssl_conf(){
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid-ssl.conf");
	@copy("/etc/squid3/ssl.conf","/usr/share/artica-postfix/ressources/logs/web/squid-ssl.conf");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid-ssl.conf",0755);	
	
}
function squid_externals_conf(){
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid-extern.conf");
	@copy("/etc/squid3/external_acls.conf","/usr/share/artica-postfix/ressources/logs/web/squid-extern.conf");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid-extern.conf",0755);
}

function squid_external_conf_save(){
	$unix=new unix();
	$datas=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/externals.conf");
	writelogs_framework(strlen($datas)/1024 ." Ko",__FUNCTION__,__FILE__,__LINE__);
	if($datas==null){
	
		echo "<articadatascgi>". base64_encode("Fatal NO CONTENT!!")."</articadatascgi>";
		return;
	}
	@unlink("/usr/share/artica-postfix/ressources/logs/web/externals.conf");
	@unlink("/etc/squid3/external_acls.bak");
	@copy("/etc/squid3/external_acls.conf","/etc/squid3/external_acls.bak");
	@file_put_contents("/etc/squid3/external_acls.conf", $datas);
	
	if(!test_squid_conf()){
		@unlink("/etc/squid3/external_acls.conf");
		@copy("/etc/squid3/external_acls.bak","/etc/squid3/external_acls.conf");
		return;
	}
	
	@unlink("/etc/squid3/external_acls.bak");
	squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
	$cmd="/etc/init.d/squid reload --script=".basename(__FILE__);
	shell_exec("$cmd >/dev/null 2>&1");
	
}

function test_squid_conf(){
	$unix=new unix();
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	$SQUID_CONFIG_PATH=$unix->SQUID_CONFIG_PATH();
	writelogs_framework("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",__FUNCTION__,__FILE__,__LINE__);
	exec("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",$results);
	foreach ($results as $index=>$ligne){
		if(strpos($ligne,"| WARNING:")>0){continue;}
		if(preg_match("#ERROR: Failed#", $ligne)){
			writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
			echo "<articadatascgi>". base64_encode("Squid `$ligne`, aborting configuration")."</articadatascgi>";
			return false;
		}
		
		if(preg_match("#Segmentation fault#", $ligne)){
			writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
			echo "<articadatascgi>". base64_encode("Squid `$ligne`, aborting configuration")."</articadatascgi>";
			return false;
		}
			
		if(preg_match("#(unrecognized|FATAL|Bungled)#", $ligne)){
			$f[]="Squid `$ligne`, aborting configuration, keep the old one...\n";
			writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
			if(preg_match("#line ([0-9]+):#", $ligne,$ri)){
				$Buggedline=$ri[1];
				$tt=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
				for($i=$Buggedline-2;$i<$Buggedline+2;$i++){
					$lineNumber=$i+1;
					if(trim($tt[$i])==null){continue;}
					$f[]="[line:$lineNumber]: {$tt[$i]}";
				}
			}
			
			echo "<articadatascgi>". base64_encode(@implode("\n", $f))."</articadatascgi>";
			return false;
		}
	}

	return true;
		
}

function squid_ssl_conf_save(){
	$unix=new unix();
	$datas=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/ssl.conf");
	writelogs_framework(strlen($datas)/1024 ." Ko",__FUNCTION__,__FILE__,__LINE__);
	if($datas==null){
	
		echo "<articadatascgi>". base64_encode("Fatal NO CONTENT!!")."</articadatascgi>";
		return;
	}
	@unlink("/usr/share/artica-postfix/ressources/logs/web/ssl.conf");
	
	@unlink("/etc/squid3/ssl.conf.bak");
	@copy("/etc/squid3/listen_ports.conf","/etc/squid3/ssl.conf.bak");
	
	
	@file_put_contents("/etc/squid3/ssl.conf", $datas);
	@chown("/etc/squid3/ssl.conf", "squid");
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	$SQUID_CONFIG_PATH=$unix->SQUID_CONFIG_PATH();
	writelogs_framework("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",__FUNCTION__,__FILE__,__LINE__);
	exec("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",$results);
	foreach ($results as $index=>$ligne){
		if(strpos($ligne,"| WARNING:")>0){continue;}
		if(preg_match("#ERROR: Failed#", $ligne)){
			writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
			@unlink("/etc/squid3/ssl.conf");
			@copy("/etc/squid3/ssl.conf.bak","/etc/squid3/ssl.conf");
			echo "<articadatascgi>". base64_encode("Squid `$ligne`, aborting configuration")."</articadatascgi>";
			return ;
		}
	
		if(preg_match("#Segmentation fault#", $ligne)){
			writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
			@unlink("/etc/squid3/ssl.conf");
			@copy("/etc/squid3/ssl.conf.bak","/etc/squid3/ssl.conf");
			echo "<articadatascgi>". base64_encode("Squid `$ligne`, aborting configuration")."</articadatascgi>";
			return ;
		}
			
			
		if(preg_match("#(unrecognized|FATAL|Bungled)#", $ligne)){
			$f[]="Squid `$ligne`, aborting configuration, keep the old one...\n";
			writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
			if(preg_match("#line ([0-9]+):#", $ligne,$ri)){
				$Buggedline=$ri[1];
				$tt=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
				for($i=$Buggedline-2;$i<$Buggedline+2;$i++){
					$lineNumber=$i+1;
					if(trim($tt[$i])==null){continue;}
					$f[]="[line:$lineNumber]: {$tt[$i]}";
				}
			}
			@unlink("/etc/squid3/ssl.conf");
			@copy("/etc/squid3/ssl.conf.bak","/etc/squid3/ssl.conf");
			echo "<articadatascgi>". base64_encode(@implode("\n", $f))."</articadatascgi>";
			return;
		}
	
	}
	@unlink("/etc/squid3/listen_ports.conf.bak");
	squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
	$cmd="/etc/init.d/squid reload --script=".basename(__FILE__);
	shell_exec("$cmd >/dev/null 2>&1");	
	
	
}

function squid_ports_conf_save(){
	$unix=new unix();
	$datas=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/squid_ports.conf");
	writelogs_framework(strlen($datas)/1024 ." Ko",__FUNCTION__,__FILE__,__LINE__);
	if($datas==null){

		echo "<articadatascgi>". base64_encode("Fatal NO CONTENT!!")."</articadatascgi>";
		return;
	}
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid_ports.conf");
	
	@unlink("/etc/squid3/listen_ports.conf.bak");
	@copy("/etc/squid3/listen_ports.conf","/etc/squid3/listen_ports.conf.bak");
	
	
	@file_put_contents("/etc/squid3/listen_ports.conf", $datas);
	@chown("/etc/squid3/listen_ports.conf", "squid");
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	$SQUID_CONFIG_PATH=$unix->SQUID_CONFIG_PATH();
	writelogs_framework("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",__FUNCTION__,__FILE__,__LINE__);
	exec("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",$results);
	foreach ($results as $index=>$ligne){
		if(strpos($ligne,"| WARNING:")>0){continue;}
		if(preg_match("#ERROR: Failed#", $ligne)){
			writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
			@unlink("/etc/squid3/listen_ports.conf");
			@copy("/etc/squid3/listen_ports.conf.bak","/etc/squid3/listen_ports.conf");
			echo "<articadatascgi>". base64_encode("Squid `$ligne`, aborting configuration")."</articadatascgi>";
			return ;
		}

		if(preg_match("#Segmentation fault#", $ligne)){
			writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
			@unlink("/etc/squid3/listen_ports.conf");
			@copy("/etc/squid3/listen_ports.conf.bak","/etc/squid3/listen_ports.conf");
			echo "<articadatascgi>". base64_encode("Squid `$ligne`, aborting configuration")."</articadatascgi>";
			return ;
		}
			
			
		if(preg_match("#(unrecognized|FATAL|Bungled)#", $ligne)){
			$f[]="Squid `$ligne`, aborting configuration, keep the old one...\n";
			writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
			if(preg_match("#line ([0-9]+):#", $ligne,$ri)){
				$Buggedline=$ri[1];
				$tt=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
				for($i=$Buggedline-2;$i<$Buggedline+2;$i++){
					$lineNumber=$i+1;
					if(trim($tt[$i])==null){continue;}
					$f[]="[line:$lineNumber]: {$tt[$i]}";
				}
			}
			@unlink("/etc/squid3/listen_ports.conf");
			@copy("/etc/squid3/listen_ports.conf.bak","/etc/squid3/listen_ports.conf");
			echo "<articadatascgi>". base64_encode(@implode("\n", $f))."</articadatascgi>";
			return;
		}

	}
	@unlink("/etc/squid3/listen_ports.conf.bak");
	squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
	$cmd="/etc/init.d/squid reload --script=".basename(__FILE__);
	shell_exec("$cmd >/dev/null 2>&1");

}



function ufdb_ini_status(){
	$unix=new unix();
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --ufdb --nowachdog >/usr/share/artica-postfix/ressources/interface-cache/UFDB_STATUS 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function create_cache_wizard(){
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.newcache.center.progress";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.cache.wizard.php > /usr/share/artica-postfix/ressources/logs/web/squid.newcache.center.log 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function cached_kerberos_tickets(){
	$unix=new unix();
	$klist=$unix->find_program("klist");
	exec("$klist -k /etc/squid3/krb5.keytab -t 2>&1",$results);
	$c=0;
	foreach ($results as $num=>$line){
		$line=trim($line);
		$tr=explode(" ",$line);
		if(!is_numeric($tr[0])){continue;}
		$num=trim($tr[0]);
		$date=trim($tr[1])." ".trim($tr[2]);
		$tickets=trim($tr[3]);
		$array[$c]["NUM"]=$num;
		$array[$c]["DATE"]=$date;
		$array[$c]["ticket"]=$tickets;
		$c++;
	}
	
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/kerberos-tickets-squid", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/kerberos-tickets-squid",0755);
	
	
	
}

?>