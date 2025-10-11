exec<?php
$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["FORCE"]=true;$GLOBALS["OUTPUT"]=true;
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
$GLOBALS["debug"]=true;
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
}
$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
if($EnableIntelCeleron==1){build_progress(110,"Intel Celeron mode...");exit();}

include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.booster.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.watchdog.inc");
include_once(dirname(__FILE__)."/ressources/class.influx.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/externals/Net/DNS2.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");

if(system_is_overloaded()){
    squid_admin_mysql(1, "{$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: {OVERLOADED_SYSTEM}, aborting task",ps_report(),__FILE__,__LINE__);
    build_progress(110,"{overloaded}");exit();}


if($argv[1]=="--stats"){exit();exit;}






CHECK_DNS_SYSTEMS();
function build_progress($pourc,$text){
	$cachefile="/usr/share/artica-postfix/ressources/logs/admin.dashboard.dnsperfs.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "{$pourc}% $text\n";
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function CHECK_DNS_SYSTEMS(){

	
	
	$unix=new unix();
	include_once(dirname(__FILE__)."/ressources/class.influx.inc");

	$pidtime="/etc/artica-postfix/settings/Daemons/NameBenchReport";
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableDNSPerfs")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDNSPerfs", 0);}
	$EnableDNSPerfs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSPerfs"));
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	if($EnableIntelCeleron==1){$EnableDNSPerfs=0;}
	
	build_progress(10,"{running}");
	
	if($EnableDNSPerfs==0){
		build_progress(110,"{disabled}");
		echo "EnableDNSPerfs -> Disabled\n";
		exit();
	}

	$unix=new unix();
	$GLOBALS["MYHOSTNAME"]=$unix->hostname_g();
	if(!$GLOBALS["FORCE"]){
		$pid=$unix->get_pid_from_file($pidFile);
		if($unix->process_exists($pid)){
			if($unix->PROCCESS_TIME_MIN($pid,10)<2){return;}
		}
	}

	if($GLOBALS["VERBOSE"]){echo "pidtime =$pidtime\n";}

	@file_put_contents($pidFile, getmypid());
	$time=$unix->file_time_min($pidtime);
	
	$size=@filesize("/etc/artica-postfix/settings/Daemons/NameBenchReport");
	echo "Size: $size bytes\n";

	$pid=$unix->PIDOF_PATTERN("namebench.py");
	if($unix->process_exists($pid)){
		build_progress(110,"Already running");
		if($unix->PROCCESS_TIME_MIN($pid,10)<2){return;}
	}
	

	@unlink($pidtime);
	@file_put_contents($pidtime, time());

	
	
	$q=new mysql_squid_builder();
	if($q->TABLE_EXISTS("dnsperfs")){
		$q->QUERY_SQL("TRUNCATE TABLE dnsperfs");
		$q->QUERY_SQL("DROP TABLE dnsperfs");
	}
	if($q->TABLE_EXISTS("dnsperfs_week")){
		$q->QUERY_SQL("TRUNCATE TABLE dnsperfs_week");
		$q->QUERY_SQL("DROP TABLE dnsperfs_week");
	}
	if($q->TABLE_EXISTS("dashboard_dnsperf_day")){
		$q->QUERY_SQL("TRUNCATE TABLE dashboard_dnsperf_day");
		$q->QUERY_SQL("DROP TABLE dashboard_dnsperf_day");
	}
	if($q->TABLE_EXISTS("dashboard_dnsperf_month")){
		$q->QUERY_SQL("TRUNCATE TABLE dashboard_dnsperf_month");
		$q->QUERY_SQL("DROP TABLE dashboard_dnsperf_month");
	}
	
	$q=new postgres_sql();
	if($q->TABLE_EXISTS("dnsperfs")){$q->QUERY_SQL("DROP TABLE dnsperfs");}
	
	$namebench=$unix->find_program("namebench");
	if(!is_file($namebench)){$namebench=$unix->find_program("namebench.py");}
	
	
	if(!is_file($namebench)){
		build_progress(20,"{installing}");
		InstallNameBench();
		if(!is_file("/usr/local/bin/namebench.py")){
			build_progress(110,"{installing} {failed}");
			squid_admin_mysql(1, "Unable to install NameBench", @implode("\n", $GLOBALS["ERRORSNB"]),__FILE__,__LINE__);
			return;
		}
		$namebench="/usr/local/bin/namebench.py";
	}
	build_config();
	$NAMEBENCH_ARRAY=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NAMEBENCH_ARRAY"));
	$DNS=array();
	while (list ($KEY, $VAL) = each ($NAMEBENCH_ARRAY) ){
		if(preg_match("#^DNS[0-9]+#", $KEY)){$DNS[$VAL]=true;}
		
	}

	if(count($DNS)==0){
		build_progress(110,"{failed} NO DNS set");
		return;
	}
	while (list ($KEY, $VAL) = each ($DNS) ){$FINAL[]=$KEY;}
	build_progress(21,"{generate_report} ".@implode(" ", $FINAL). " {please_wait}...");
	$nohup=$unix->find_program("nohup");
	
	$tmpcsv=$unix->FILE_TEMP();
	$cmdline="$nohup $namebench -o $pidtime -t html -x -P 2 -y 2 -q 80 -O ".@implode(" ", $FINAL)." >$tmpcsv 2>&1 &";
	echo $cmdline."\n";
	system($cmdline);
	
	$c=21;
	for($i=0;$i<360;$i++){
		$pid=$unix->PIDOF_PATTERN($namebench);
		if(!$unix->process_exists($pid)){break;}
		$c++;
		if($c>98){$c=98;}
		$time=$unix->PROCESS_TTL_TEXT($pid);
		$mem=$unix->PROCESS_MEMORY($pid);
		build_progress($c,"{running} {$mem}MB Time:$time");
		sleep(1);
	}
	
	
	
	$size=@filesize("/etc/artica-postfix/settings/Daemons/NameBenchReport");
	if($size<100){
		build_progress(110,"{failed}");
		echo "Size: $size\n";
		return;
	}
	
	build_progress(100,"{success}");
	
	
	$rm=$unix->find_program("rm");
	system("$rm  /tmp/namebench*.csv");
	@chmod($pidtime ,0755);

	


}

function Events($text){
	$unix=new unix();
	$unix->ToSyslog($text);
	
	
}
function mini_bench_to($arg_t, $arg_ra=false){
	$tttime=round((end($arg_t)-$arg_t['start'])*1000,4);
	if ($arg_ra) $ar_aff['total_time']=$tttime;
	else return $tttime;
	$prv_cle='start';
	$prv_val=$arg_t['start'];

	foreach ($arg_t as $cle=>$val)
	{
		if($cle!='start')
		{
			$prcnt_t=round(((round(($val-$prv_val)*1000,4)/$tttime)*100),1);
			if ($arg_ra) $ar_aff[$prv_cle.' -> '.$cle]=$prcnt_t;
			$aff.=$prv_cle.' -> '.$cle.' : '.$prcnt_t." %\n";
			$prv_val=$val;
			$prv_cle=$cle;
		}
	}
	if ($arg_ra) return $ar_aff;
	return $aff;
}
function InstallNameBench(){
    $NoInternetAccess=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoInternetAccess"));
    if($NoInternetAccess==1){return;}
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$unix=new unix();
	
	$DIRTEMP=$unix->TEMP_DIR();
	
	$curl=new ccurl("http://articatech.net/download/namebench-1.31.tar.gz");
	if(!$curl->GetFile("$DIRTEMP/namebench-1.31.tar.gz")){
		$GLOBALS["ERRORSNB"][]="Failed to download http://articatech.net/download/namebench-1.31.tar.gz $curl->error";
		return;
	}
	
	$GLOBALS["ERRORSNB"][]="Downloading success...\n";
	$tar=$unix->find_program("tar");
	shell_exec("$tar xf $DIRTEMP/namebench-1.31.tar.gz -C /");
	@unlink("$DIRTEMP/namebench-1.31.tar.gz");
	
}

function build_config(){
	$f[]="[alexa]";
	$f[]="name=Top 2,000 Websites (Alexa)";
	$f[]="max_mtime_days=0";
	$f[]="synthetic=1";
	$f[]="1=data/alexa-top-2000-domains.txt";
	$f[]="";
	$f[]="[cachemix]";
	$f[]="name=Cache Latency Test (50% hit, 50% miss)";
	$f[]="max_mtime_days=0";
	$f[]="include_duplicates=1";
	$f[]="synthetic=1";
	$f[]="1=data/cache-mix.txt";
	$f[]="";
	$f[]="[cachehit]";
	$f[]="name=Cache Latency Test (100% hit)";
	$f[]="max_mtime_days=0";
	$f[]="include_duplicates=1";
	$f[]="synthetic=1";
	$f[]="1=data/cache-hit.txt";
	$f[]="";
	$f[]="[cachemiss]";
	$f[]="name=Cache Latency Test (100% miss)";
	$f[]="max_mtime_days=0";
	$f[]="include_duplicates=1";
	$f[]="synthetic=1";
	$f[]="1=data/cache-miss.txt";
	$f[]="";
	$f[]="[chrome]";
	$f[]="name=Google Chrome";
	$f[]="1=%HOME%/Library/Application Support/Google/Chrome/Default/History";
	$f[]="2=%HOME%/.config/google-chrome/Default/History";
	$f[]="3=%APPDATA%/Google/Chrome/User Data/Default/History";
	$f[]="4=%USERPROFILE%/Local Settings/Application Data/Google/Chrome/User Data/Default/History";
	$f[]="";
	$f[]="[chromium]";
	$f[]="name=Chromium";
	$f[]="1=%HOME%/Library/Application Support/Chromium/Default/History";
	$f[]="2=%HOME%/.config/chromium/Default/History";
	$f[]="3=%APPDATA%/Chromium/User Data/Default/History";
	$f[]="4=%USERPROFILE%/Local Settings/Application Data/Chromium/User Data/Default/History";
	$f[]="";
	$f[]="[opera]";
	$f[]="name=Opera";
	$f[]="1=%HOME%/Library/Preferences/Opera Preferences/global_history.dat";
	$f[]="2=%HOME%/Library/Preferences/Opera Preferences 10/global_history.dat";
	$f[]="3=%APPDATA%/Opera/Opera/global_history.dat";
	$f[]="4=%HOME%/.opera/global_history.dat";
	$f[]="";
	$f[]="[safari]";
	$f[]="name=Apple Safari";
	$f[]="1=%HOME%/Library/Safari/History.plist";
	$f[]="2=%APPDATA%/Apple Computer/Safari/History.plist";
	$f[]="";
	$f[]="";
	$f[]="[firefox]";
	$f[]="name=Mozilla Firefox";
	$f[]="1=%HOME%/Library/Application Support/Firefox/Profiles/*/places.sqlite";
	$f[]="2=%HOME%/.mozilla/firefox/*/places.sqlite";
	$f[]="3=%APPDATA%/Mozilla/Firefox/Profiles/*/places.sqlite";
	$f[]="";
	$f[]="# firefox v2.0";
	$f[]="4=%HOME%/Library/Application Support/Firefox/Profiles/*/history.dat";
	$f[]="5=%HOME%/.mozilla/firefox/*/history.dat";
	$f[]="6=%APPDATA%/Mozilla/Firefox/Profiles/*/history.dat";
	$f[]="";
	$f[]="[flock]";
	$f[]="name=Flock";
	$f[]="1=%HOME%/Library/Application Support/Flock/Profiles/*/places.sqlite";
	$f[]="2=%HOME%/.flock/browser/Flock/*/places.sqlite";
	$f[]="3=%APPDATA%/Flock/Browser/Profiles/*/places.sqlite";
	$f[]="";
	$f[]="[seamonkey]";
	$f[]="name=Mozilla Seamonkey";
	$f[]="1=%HOME%/Library/Application Support/Seamonkey/Profiles/*/history.dat";
	$f[]="2=%HOME%/.mozilla/seamonkey/*/history.dat";
	$f[]="3=%HOME%/.mozilla/default/*/history.dat";
	$f[]="4=%APPDATA%/Mozilla/Seamonkey/Profiles/*/history.dat";
	$f[]="";
	$f[]="[internet_explorer]";
	$f[]="# XP";
	$f[]="name=Microsoft Internet Explorer";
	$f[]="1=%USERPROFILE%/Local Settings/History/History.IE5/index.dat";
	$f[]="2=%APPDATA%/Microsoft/Windows/History/History.IE5/index.dat";
	$f[]="3=%APPDATA%/Microsoft/Windows/History/Low/History.IE5/index.dat";
	$f[]="";
	$f[]="[epiphany]";
	$f[]="name=Epiphany";
	$f[]="1=%HOME%/.gnome2/epiphany/ephy-history.xml";
	$f[]="";
	$f[]="[galeon]";
	$f[]="name=Galeon";
	$f[]="1=%HOME%/.galeon/history*.xml";
	$f[]="";
	$f[]="[konqueror]";
	$f[]="name=Konqueror";
	$f[]="1=%HOME%/.kde4/share/apps/konqueror/konq_history";
	$f[]="2=%HOME%/.kde/share/apps/konqueror/konq_history";
	$f[]="";
	$f[]="[omniweb]";
	$f[]="name=OmniWeb";
	$f[]="1=%HOME%/Library/Application Support/OmniWeb ?/HistoryIndex.ox";
	$f[]="2=%HOME%/Library/Application Support/OmniWeb ?/History.plist";
	$f[]="";
	$f[]="[sunrise]";
	$f[]="name=Sunrise";
	$f[]="1=%HOME%/Library/Application Support/Sunrise*/History.plist";
	$f[]="";
	$f[]="[camino]";
	$f[]="name=Camino";
	$f[]="1=%HOME%/Library/Application Support/Camino/history.dat";
	$f[]="";
	$f[]="[icab]";
	$f[]="name=iCab";
	$f[]="1=%HOME%/Library/Preferences/iCab/iCab ? History";
	$f[]="";
	$f[]="[midori]";
	$f[]="name=Midori";
	$f[]="1=%HOME%/.config/midori/history.db";
	$f[]="";
	$f[]="[squid]";
	$f[]="name=Squid Cache";
	$f[]="1=/usr/local/squid/logs/access.log";
	$f[]="2=/var/log/squid/access_log";
	@mkdir("/usr/local/namebench/namebench/config",0755,true);
	@mkdir("/etc/namebench/config",0755,true);
	@mkdir("/etc/namebench/data",0755,true);
	@mkdir("/etc/namebench/templates",0755,true);
	
	
	$tmpls[]="ascii.tmpl";
	$tmpls[]="html.tmpl";
	$tmpls[]="resolv.conf.tmpl";
	$tmpls[]="style.css";
	
	while (list ($index, $fs) = each ($tmpls) ){
		if(is_file("/usr/local/namebench/namebench/templates/$fs")){
			@unlink("/etc/namebench/templates/$fs");
			@copy("/usr/local/namebench/namebench/templates/$fs", "/etc/namebench/templates/$fs");
			if(is_file("/etc/namebench/templates/$fs")){echo "$fs [OK]\n";}
		}
	}
	

	
	
	
	if(is_file("/usr/local/namebench/namebench/templates/html.tmpl")){
		@unlink("/etc/namebench/templates/html.tmpl");
		@copy("/usr/local/namebench/namebench/templates/html.tmpl", "/etc/namebench/templates/html.tmpl");
	}
	
	if(is_file("/usr/local/namebench/namebench/config/hostname_reference.cfg")){
		@unlink("/etc/namebench/config/namebench.cfg");
		@copy("/usr/local/namebench/namebench/config/hostname_reference.cfg", "/etc/namebench/config/hostname_reference.cfg");
	}
	
	if(is_file("/usr/local/namebench/namebench/config/namebench.cfg")){
		@unlink("/etc/namebench/config/namebench.cfg");
		@copy("/usr/local/namebench/namebench/config/namebench.cfg", "/etc/namebench/config/namebench.cfg");
	}
	
	if(is_file("/usr/local/namebench/namebench/data/alexa-top-2000-domains.txt")){
		@unlink("/etc/namebench/data/alexa-top-2000-domains.txt");
		@copy("/usr/local/namebench/namebench/data/alexa-top-2000-domains.txt", "/etc/namebench/data/alexa-top-2000-domains.txt");
	}
	

	if(is_file("/usr/local/namebench/namebench/data/cache-hit.txt")){
		@unlink("/etc/namebench/data/cache-mix.txt");
		@copy("/usr/local/namebench/namebench/data/cache-hit.txt", "/etc/namebench/data/cache-hit.txt");
		if(is_file("/etc/namebench/data/cache-hit.txt")){echo "data/cache-hit.txt [OK]\n";}
		
	}	
	if(is_file("/usr/local/namebench/namebench/data/cache-mix.txt")){
		@unlink("/etc/namebench/data/cache-mix.txt");
		@copy("/usr/local/namebench/namebench/data/cache-mix.txt", "/etc/namebench/data/cache-mix.txt");
	}
	if(is_file("/usr/local/namebench/namebench/data/cache-miss.txt")){
		@unlink("/etc/namebench/data/cache-miss.txt");
		@copy("/usr/local/namebench/namebench/data/cache-miss.txt", "/etc/namebench/data/cache-miss.txt");
	}

	@file_put_contents("/etc/namebench/config/data_sources.cfg", @implode("\n", $f));
	@file_put_contents("/usr/local/namebench/namebench/config/data_sources.cfg", @implode("\n", $f));
	
}