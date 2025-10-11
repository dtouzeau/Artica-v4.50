<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["SQUID"]=false;


include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;
	$GLOBALS["OUTPUT"]=true;
	$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--squid#",implode(" ",$argv))){$GLOBALS["SQUID"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
if($argv[1]=="--scandisk"){$GLOBALS["OUTPUT"]=true;scandisk();exit();}
if($argv[1]=="--purge"){$GLOBALS["OUTPUT"]=true;purge($argv[2]);exit();}
if($argv[1]=="--delete"){$GLOBALS["OUTPUT"]=true;delete($argv[2]);exit();}
if($argv[1]=="--features"){$GLOBALS["OUTPUT"]=true;ExplodeFeatures($argv[2]);exit();}
if($argv[1]=="--wsus-on"){$GLOBALS["OUTPUT"]=true;wsus_on();exit();}

function PID_NUM(){
	$filename="/var/run/hypercache-service.pid";
	$pid=trim(@file_get_contents($filename));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF("/usr/local/HyperCache/sbin/hypercache-service");
}

function restart(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	$php=$unix->LOCATE_PHP5_BIN();
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: HyperCache Proxy Artica task running PID $pid since {$time}mn\n";}
		build_progress(110, "{failed2}");
		return;
	}
	@file_put_contents($pidfile, getmypid());
	build_progress(50, "{stopping_service}");
	stop(true);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: HyperCache Proxy {building_settings}..\n";}
	build_progress(80, "{reconfigure}");
	build();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: HyperCache Proxy...\n";}
	build_progress(90, "{starting_service}");
	if(!start(true)){
		build_progress(110, "{failed2}");
		return;
	}
	
	
	build_progress(95, "{reconfigure_proxy_service}");
	system("$php /usr/share/artica-postfix/exec.squid.global.access.php --parents");
	
	
	build_progress(97, "{templates}");
	system("$php /usr/share/artica-postfix/exec.squid.templates.php --nginx");
	system("/etc/init.d/hypercache-tail restart");
	build_progress(100, "{success}");

}

function wsus_on(){
	
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableWindowsUpdate", 1);
	build_progress(50, "{stopping_service}");
	stop(true);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: HyperCache Proxy {building_settings}..\n";}
	build_progress(80, "{reconfigure}");
	build();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: HyperCache Proxy...\n";}
	build_progress(90, "{starting_service}");
	if(!start(true)){
		build_progress(110, "{failed2}");
		return;
	}
	build_progress(100, "{success}");
}


function reload(){
	$unix=new unix();

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		start(true);
		return;
	}
	
	
	build();
	shell_exec("/usr/local/HyperCache/sbin/hypercache-service -c /etc/hypercache/hypercache.conf -s reload >/dev/null 2>&1");
	if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: HyperCache Proxy reloading success...\n";}

}

function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: HyperCache Proxy Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();
	$HyperCacheProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheProxyPort"));
	if($HyperCacheProxyPort==0){$HyperCacheProxyPort=2928;}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: HyperCache Proxy already stopped...\n";}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: HyperCache Proxy Checks $HyperCacheProxyPort TCP Port\n";}
		$unix->KILL_PROCESSES_BY_PORT($HyperCacheProxyPort);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: HyperCache Proxy Checks $HyperCacheProxyPort TCP Port done\n";}
		return true;
		}



	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$kill=$unix->find_program("kill");



	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: HyperCache Proxy Shutdown pid $pid...\n";}
	shell_exec("/usr/local/HyperCache/sbin/hypercache-service -c /etc/hypercache/hypercache.conf -s stop >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: HyperCache Proxy waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: HyperCache Proxy success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: HyperCache Proxy shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: HyperCache Proxy waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: HyperCache Proxy success...\n";}
		$unix->KILL_PROCESSES_BY_PORT($HyperCacheProxyPort);
		return true;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: HyperCache Proxy failed...\n";}

}



function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: HyperCache Proxy Already Artica task running PID $pid since {$time}mn\n";}
			return false;
		}
		@file_put_contents($pidfile, getmypid());
	}


	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: HyperCache Proxy Service already started $pid since {$timepid}Mn...\n";}
		return true;
	}
	
	$nohup=$unix->find_program("nohup");
	$fuser=$unix->find_program("fuser");
	$kill=$unix->find_program("kill");
	$results=array();
	$FUSERS=array();
	$HyperCacheProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheProxyPort"));
	if($HyperCacheProxyPort==0){$HyperCacheProxyPort=2928;}

	
	
	$unix->KILL_PROCESSES_BY_PORT($HyperCacheProxyPort);
	$cmd="/usr/local/HyperCache/sbin/hypercache-service -c /etc/hypercache/hypercache.conf";

	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	shell_exec($cmd);

	for($i=0;$i<6;$i++){
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: HyperCache Proxy Service service waiting $i/6...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: HyperCache Proxy Service service Success...\n";}
		return true;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: HyperCache Proxy Service service failed...\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmd\n";}
	return false;
}

function __resolvers_resolv(){
	$f=explode("\n",@file_get_contents("/etc/resolv.conf"));
	
	foreach ( $f as $index=>$line ){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#nameserver\s+(.+)#", $line,$re)){
			$DNS[$re[1]]=true;
		}
	
	}
	
	while (list ($index, $line) = each ($DNS) ){
		$final[]=$index;
	}
	return $final;
}

function resolvers(){
	$noresol=false;
	$SquidDNSUseSystem=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDNSUseSystem"));
	$SquidDNSUseLocalDNSService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDNSUseLocalDNSService"));

	if($SquidDNSUseSystem==1){return __resolvers_resolv();}
	if($SquidDNSUseLocalDNSService==0){$EnableDNSMASQ=0;}

	$SquidNameServer1=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNameServer1");
	$SquidNameServer2=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNameServer2");
	if($SquidNameServer1==null){
		$dns_nameservers[]="8.8.8.8";
		return $dns_nameservers;}

	$dns_nameservers[]=$SquidNameServer1;
	if($SquidNameServer2<>null){$dns_nameservers[]=$SquidNameServer2;}
	return $dns_nameservers;

}

function ExplodeFeatures(){
	
	exec("/usr/local/HyperCache/sbin/hypercache-service -V 2>&1",$results);
	
	while (list ($cacheNum, $ligne) = each ($results) ){
		if(preg_match("#configure arguments:(.+)#", $ligne,$re)){
			$args=$re[1];
		}
	
	}
	
	$tb=explode("--",$args);
	$MAIN=array();
	$MAIN["ngx_pagespeed"]=false;
	while (list ($cacheNum, $ligne) = each ($tb) ){
		
		if(preg_match("#ngx_pagespeed#", $ligne)){
			$MAIN["ngx_pagespeed"]=true;
		}
	}
	
	return $MAIN;
	
}

function build(){
	
	$unix=new unix();
	if($unix->ServerRunSince()<3){
		return;
	}
	
	
	$q=new mysql_squid_builder();
	$q->CreateHyperCacheTables();
	
	$ln=$unix->find_program("ln");
	

	
	

	
$hypercache_caches=$q->COUNT_ROWS("hypercache_caches");
$GLOBALS["hypercache_caches_list"]=array();
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: HyperCache Proxy Service $hypercache_caches cache(s)\n";}
	
	if($hypercache_caches==0){
		
		$sql="INSERT IGNORE INTO hypercache_caches(directory,levels,keys_zone,keys_zone_size,inactive,max_size) VALUES ('/home/artica/hypercache/MainDisk0','1:2','MainDisk0','32','120','4775')";
		$q->QUERY_SQL($sql);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: HyperCache Proxy Service Creating MainDisk0 (/home/artica/hypercache/MainDisk0)\n";}
		if(!$q->ok){
			echo "FATAL WHILE CREATING DEFAULT CACHE\n$q->mysql_error\n";
			return false;
		}
	}
	
	$results=$q->QUERY_SQL("SELECT * FROM hypercache_caches");
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$keys_zone=$ligne["keys_zone"];
		$directory=$ligne["directory"];
		$ID=$ligne["ID"];
		$GLOBALS["hypercache_caches_list"][$ID]=$keys_zone;

		if(!is_dir($directory)){
			@mkdir($directory,0755,true);
			@chown($directory, "squid");
			@chgrp($directory, "squid");
		}

		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: HyperCache Proxy Service Cache {$ligne["ID"]} [$keys_zone] \"$directory\"\n";}
		
		$line="$directory keys_zone=$keys_zone:{$ligne["keys_zone_size"]}m levels={$ligne["levels"]} max_size={$ligne["max_size"]}m"; 
		$line=$line." inactive={$ligne["inactive"]}d loader_files={$ligne["loader_files"]} loader_sleep={$ligne["loader_sleep"]}ms loader_threshold={$ligne["loader_threshold"]}ms use_temp_path=off;";
		$MAINCACHES[$keys_zone]=$line;
		
	}

	$ffolders[]="/etc/hypercache";
	$ffolders[]="/home/artica/hypercache/PageSpeed";
	$ffolders[]="/home/artica/hypercache/files";
	$ffolders[]="/home/artica/hypercache/tmp";
	$ffolders[]="/var/log/hypercache-service";
	foreach ($ffolders as $directory){
		@mkdir($directory,0755,true);
		@chown($directory,"squid");
		@chgrp($directory,"squid");
	}
	
	$EnableWindowsUpdate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWindowsUpdate"));
	$WindowsUpdateRetention=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsUpdateRetention"));
	if($WindowsUpdateRetention==0){$WindowsUpdateRetention=365;}
	$HyperCacheProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheProxyPort"));
	$HyperCacheProxyGzip=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheProxyGzip"));
	$HyperCacheOutgoingAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheOutgoingAddr");
	$HyperCacheListenAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheListenAddress"));
	$HyperCacheResolvers=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheResolvers"));
	$HyperCacheResolveTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheResolveTTL"));
	if($HyperCacheResolveTTL==0){$HyperCacheResolveTTL=5;}
	if($HyperCacheListenAddress==null){$HyperCacheListenAddress="127.0.0.1";}
	
	if($HyperCacheProxyPort==0){$HyperCacheProxyPort=2928;}
	
	$resolvers=resolvers();
	//https://lab.nexedi.cn/nexedi/zimbra/blob/master/ThirdParty/nginx/nginx-1.2.0zimbra/src/os/unix/ngx_setproctitle.c
	$f[]="user squid;";
	$f[]="worker_processes auto;";
	$f[]="worker_rlimit_nofile 65535;";

	$f[]="pid /var/run/hypercache-service.pid;";
	$f[]="error_log  /var/log/hypercache-service/error.log warn;";
	$f[]="";
	$f[]="events {";
	$f[]="\tworker_connections  8096;";
	$f[]="\tmulti_accept        on;";
	$f[]="\tuse                 epoll;";
	$f[]="}";
	$f[]="http {";
	$f[]="\tsendfile on;";
	$f[]="\ttcp_nopush on;";
	$f[]="\ttcp_nodelay on;";
	$f[]="\tkeepalive_timeout 65;";
	$f[]="\tresolver_timeout {$HyperCacheResolveTTL}s;";
	$f[]="\ttypes_hash_max_size 2048;";
	$f[]="\tdefault_type application/octet-stream;";
	$f[]="\tproxy_ignore_headers Expires Cache-Control Vary;";
	$f[]="\tlog_format hypercachelog '\$msec \$request_time \$remote_addr \$upstream_cache_status/\$status \$bytes_sent \$request_method \"\$scheme://\$proxy_host\$uri\$is_args\$args\"';";
	$f[]="\taccess_log /var/log/hypercache-service/access.log hypercachelog;";
	$f[]="\terror_log /var/log/hypercache-service/error.log;";
	$f[]="";
	$f[]="";
	$f[]="\ttypes {";
	$f[]="\t\ttext/html                             html htm shtml;";
	$f[]="\t\ttext/css                              css;";
	$f[]="\t\ttext/xml                              xml;";
	$f[]="    image/gif                             gif;";
	$f[]="    image/jpeg                            jpeg jpg;";
	$f[]="    application/javascript                js;";
	$f[]="    application/atom+xml                  atom;";
	$f[]="    application/rss+xml                   rss;";
	$f[]="";
	$f[]="    text/mathml                           mml;";
	$f[]="    text/plain                            txt;";
	$f[]="    text/vnd.sun.j2me.app-descriptor      jad;";
	$f[]="    text/vnd.wap.wml                      wml;";
	$f[]="    text/x-component                      htc;";
	$f[]="";
	$f[]="    image/png                             png;";
	$f[]="    image/tiff                            tif tiff;";
	$f[]="    image/vnd.wap.wbmp                    wbmp;";
	$f[]="    image/x-icon                          ico;";
	$f[]="    image/x-jng                           jng;";
	$f[]="    image/x-ms-bmp                        bmp;";
	$f[]="    image/svg+xml                         svg svgz;";
	$f[]="    image/webp                            webp;";
	$f[]="";
	$f[]="    application/font-woff                 woff;";
	$f[]="    application/java-archive              jar war ear;";
	$f[]="    application/json                      json;";
	$f[]="    application/mac-binhex40              hqx;";
	$f[]="    application/msword                    doc;";
	$f[]="    application/pdf                       pdf;";
	$f[]="    application/postscript                ps eps ai;";
	$f[]="    application/rtf                       rtf;";
	$f[]="    application/vnd.apple.mpegurl         m3u8;";
	$f[]="    application/vnd.ms-excel              xls;";
	$f[]="    application/vnd.ms-fontobject         eot;";
	$f[]="    application/vnd.ms-powerpoint         ppt;";
	$f[]="    application/vnd.wap.wmlc              wmlc;";
	$f[]="    application/vnd.google-earth.kml+xml  kml;";
	$f[]="    application/vnd.google-earth.kmz      kmz;";
	$f[]="    application/x-7z-compressed           7z;";
	$f[]="    application/x-cocoa                   cco;";
	$f[]="    application/x-java-archive-diff       jardiff;";
	$f[]="    application/x-java-jnlp-file          jnlp;";
	$f[]="    application/x-makeself                run;";
	$f[]="    application/x-perl                    pl pm;";
	$f[]="    application/x-pilot                   prc pdb;";
	$f[]="    application/x-rar-compressed          rar;";
	$f[]="    application/x-redhat-package-manager  rpm;";
	$f[]="    application/x-sea                     sea;";
	$f[]="    application/x-shockwave-flash         swf;";
	$f[]="    application/x-stuffit                 sit;";
	$f[]="    application/x-tcl                     tcl tk;";
	$f[]="    application/x-x509-ca-cert            der pem crt;";
	$f[]="    application/x-xpinstall               xpi;";
	$f[]="    application/xhtml+xml                 xhtml;";
	$f[]="    application/xspf+xml                  xspf;";
	$f[]="    application/zip                       zip;";
	$f[]="";
	$f[]="    application/octet-stream              bin exe dll;";
	$f[]="    application/octet-stream              deb;";
	$f[]="    application/octet-stream              dmg;";
	$f[]="    application/octet-stream              iso img;";
	$f[]="    application/octet-stream              msi msp msm;";
	$f[]="";
	$f[]="    application/vnd.openxmlformats-officedocument.wordprocessingml.document    docx;";
	$f[]="    application/vnd.openxmlformats-officedocument.spreadsheetml.sheet          xlsx;";
	$f[]="    application/vnd.openxmlformats-officedocument.presentationml.presentation  pptx;";
	$f[]="";
	$f[]="    audio/midi                            mid midi kar;";
	$f[]="    audio/mpeg                            mp3;";
	$f[]="    audio/ogg                             ogg;";
	$f[]="    audio/x-m4a                           m4a;";
	$f[]="    audio/x-realaudio                     ra;";
	$f[]="";
	$f[]="    video/3gpp                            3gpp 3gp;";
	$f[]="    video/mp2t                            ts;";
	$f[]="    video/mp4                             mp4;";
	$f[]="    video/mpeg                            mpeg mpg;";
	$f[]="    video/quicktime                       mov;";
	$f[]="    video/webm                            webm;";
	$f[]="    video/x-flv                           flv;";
	$f[]="    video/x-m4v                           m4v;";
	$f[]="    video/x-mng                           mng;";
	$f[]="    video/x-ms-asf                        asx asf;";
	$f[]="    video/x-ms-wmv                        wmv;";
	$f[]="    video/x-msvideo                       avi;";
	$f[]="\t}";	
	$divisor=100/count($MAINCACHES);
	$divisor=round($divisor)-1;

	
	while (list ($cacheNum, $ligne) = each ($MAINCACHES) ){
		$f[]="	proxy_cache_path $ligne";
		
	}
	
	$f[]="";
	$f[]="	split_clients \$uri \$cachedisk {";
	reset($MAINCACHES);
	while (list ($cacheNum, $ligne) = each ($MAINCACHES) ){
		$f[]="		{$divisor}%  \"$cacheNum\";";
	}
	$f[]="	}";
	$f[]="";
	$f[]="";
	if($HyperCacheProxyGzip==1){
		$f[]="	gzip on;";
		$f[]="	gzip_static on;";
		$f[]="	gzip_comp_level 6;";
		$f[]="	gzip_disable .msie6.;";
		$f[]="	gzip_vary on;";
		$f[]="	gzip_types text/plain text/css text/xml text/javascript application/json application/x-javascript application/xml application/xml+rss;";
		$f[]="	gzip_proxied expired no-cache no-store private auth;";
		$f[]="	gzip_buffers 16 8k;";
		$f[]="	gzip_http_version 1.1;";
	}
	
	

	
	$slice[]="\t\tslice 1024k;";
	$slice[]="\t\tproxy_set_header  Range \$slice_range;";
	$slice[]="\t\tproxy_cache_key \$http_host\$uri\$slice_range;";
	$slice[]="\t\tproxy_set_header If-Range \$http_if_range;";
	$GLOBALS["slice_text"]=@implode("\n", $slice);
	
	
	if(!$q->FIELD_EXISTS("hypercache_rules", "proxy_cache_valid")){$q->QUERY_SQL("ALTER TABLE `hypercache_rules` ADD `proxy_cache_valid` smallint(5) NOT NULL DEFAULT 15");}
	
	
	
	if($HyperCacheResolvers==null){$HyperCacheResolvers=@implode(" ", $resolvers);}
	$UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
	if($UnboundEnabled==1){$HyperCacheResolvers="127.0.0.1";}

	$mainHead[]="\t\tset \$mainhost \$http_host;";
	$mainHead[]="\t\tif ( \$http_host ~ (?<ydomain>[a-zA-Z0-9\-_]+)\.(?<tld>[^\.]+)$ ) {";
	$mainHead[]="\t\t\tset \$mainhost \$ydomain.\$tld;";
	$mainHead[]="\t\t}";
	$mainHead_text=@implode("\n", $mainHead);
	$GLOBALS["mainHead_text"]=$mainHead_text;
	
	if($HyperCacheOutgoingAddr<>null){$common[]="		proxy_bind $HyperCacheOutgoingAddr;";}
	
	$common[]="\t\tadd_header X-Cache-Status \$upstream_cache_status;";
	$common[]="\t\tproxy_cache_methods GET HEAD;";
	
	$common[]="\t\troot /home/squid/nginx_squid_cache/files;";
	$common[]="\t\tresolver $HyperCacheResolvers;";
	$common[]="\t\tresolver_timeout {$HyperCacheResolveTTL}s;";
	$common[]="\t\tproxy_cache_revalidate off;";
	$common[]="\t\tproxy_cache_lock on;";
	$common[]="\t\tproxy_cache_lock_timeout 1h;";
	$common[]="\t\tproxy_cache_min_uses 1;";
	$common[]="\t\tproxy_cache_use_stale error timeout invalid_header updating http_500 http_502 http_503 http_504;";
	$common[]="\t\tproxy_hide_header Etag;";
	$common[]="\t\tproxy_pass  \$scheme://\$http_host\$uri\$is_args\$args;";
	$common[]="\t\tproxy_temp_path \"/home/artica/hypercache/tmp\";";
	$common[]="\t\tproxy_ignore_client_abort off;";
	$common[]="\t\tproxy_redirect off;";
	$GLOBALS["common_text"]=@implode("\n", $common);
	
	$f[]="";
	$f[]="";
	$f[]="server {";
	$f[]="\tlisten {$HyperCacheListenAddress}:$HyperCacheProxyPort;";
	$f[]="\terror_page 500 502 503 504 /Err500HyperCache.html;";
	$f[]="";
	
	$f[]="\tlocation ~* /Err500HyperCache.html{";
	$f[]="\t\troot  /usr/share/squid-langpack/templates;";
	$f[]="\t}";
	$f[]="";
	$f[]="\terror_page 404 /Err404HyperCache.html;";
	$f[]="\tlocation ~*  /Err400HyperCache.html{";
	$f[]="\t\troot  /usr/share/squid-langpack/templates;";
	$f[]="\t}";
	
	$f[]="";
	$f[]="\terror_page 400 /Err400BDHyperCache.html;";
	$f[]="";
	$f[]="\tlocation ~* /Err400BDHyperCache.html{";
	$f[]="\t\troot  /usr/share/squid-langpack/templates;";
	$f[]="\t}";
		
	

	$f[]="";
	$f[]="\tlocation  /squid-internal-periodic/store_digest{";
	$f[]="\t\taccess_log off;";
	$f[]="\t\treturn 404;";
	$f[]="\t}";
	$f[]="";
	$f[]="}";
	
/*	$TYPE[0]="{hostname_or_domain}";
	$TYPE[1]="Windows Update";xy_ignore_headers Expires
*/	$CLIENTS_RULE=array();
	$ADD_TO_DEFAULTS=array();
	$sql="SELECT * FROM hypercache_rules WHERE enabled=1 ORDER by zOrder";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$slice=false;
		$cacheid=$ligne["cacheid"];
		$pattern=$ligne["pattern"];
		$siteslist=$ligne["siteslist"];
		$extlists=$ligne["extlists"];           	
		$description=utf8_encode($ligne["description"]);
		$type=$ligne["type"];
		$key_zone=$GLOBALS["hypercache_caches_list"][$cacheid];
		$proxy_cache_valid=intval($ligne["proxy_cache_valid"]);
		if($proxy_cache_valid==0){$proxy_cache_valid=15;}
		$f[]="";
		$f[]="# --------------------------- $description ------------------------";
		
		if($type==1){
			$slice=true;
			$siteslist_pattern="\tserver_name   ~(windowsupdate|microsoft)\.com$;";
			$siteslist="windowsupdate.com\nmicrosoft.com";
			$extlists="dat\npsf\ncab\nexe\nzip";
			$CLIENTS_RULE[]=build_pattern("delivery.mp.microsoft.com/filestreamingservice/files",null);
			
		}
		
		$CLIENTS_RULE[]=build_pattern($siteslist,$extlists);
		
		
		
		
		if($type==0){
			$siteslist_pattern=build_pattern_sites($siteslist);
			if($siteslist_pattern==null){
				$ADD_TO_DEFAULTS[]=build_location($ligne,$slice);
				continue;
			}
		}
		
		
		$f[]="server {";
		$f[]="\tlisten {$HyperCacheListenAddress}:$HyperCacheProxyPort;";
		$f[]=$siteslist_pattern;
		$f[]="\tadd_header X-Cache-Rule \"$description\";";
		$f[]="\tproxy_ignore_headers Expires Cache-Control Vary;";
		$f[]="\tproxy_cache $key_zone;";
		$f[]="\tproxy_cache_key \$http_host\$uri;";
		$f[]="\tproxy_cache_valid 200 {$proxy_cache_valid}d;";
		$f[]="\tproxy_cache_valid 206 {$proxy_cache_valid}d;";
		$f[]="\tproxy_cache_valid 302 1m;";
		$f[]="\tproxy_cache_valid 301 1m;";
		$f[]="\tproxy_cache_valid 404 1m;";
		$f[]="\tproxy_cache_valid 500 1m;";
		$f[]=build_location($ligne,$slice);
		$f[]="";
		$f[]="";
		
		
		
		
		$f[]="\tlocation / {";
		$f[]=$GLOBALS["mainHead_text"];
		$f[]="\t\tproxy_ignore_headers Expires Cache-Control Vary;";
		$f[]="\t\tadd_header X-Cache-Rule \"$description (Default)\";";
		$f[]="\tproxy_cache_valid 200 {$proxy_cache_valid}d;";
		$f[]="\tproxy_cache_valid 206 {$proxy_cache_valid}d;";
		$f[]="\tproxy_cache_valid 302 1m;";
		$f[]="\tproxy_cache_valid 301 1m;";
		$f[]="\tproxy_cache_valid 404 1m;";
		$f[]="\tproxy_cache_valid 500 1m;";
		$f[]="\tproxy_cache $key_zone;";
		$f[]=$mainHead_text;
		$f[]=$GLOBALS["common_text"];
		$f[]="\t}";
		$f[]="}";
		$f[]="# -----------------------------------------------------------------";
		$f[]="";
		$f[]="";
	}
	
	$f[]="";
	$f[]="";
	$f[]="server {";
	$f[]="\tlisten {$HyperCacheListenAddress}:$HyperCacheProxyPort;";
	$f[]="\tserver_name  ~.*;";
	if(count($ADD_TO_DEFAULTS)>0){
		$f[]=@implode("\n", $ADD_TO_DEFAULTS);
	}
	$f[]="";
	$f[]="\tlocation / {";
	$f[]=$GLOBALS["mainHead_text"];
	$f[]="\t\tadd_header X-Cache-Rule \"Default\";";
	$f[]="\t\tproxy_cache            	\$cachedisk;";
	$f[]=$mainHead_text;
	$f[]=$GLOBALS["slice_text"];
	$f[]="\t\texpires modified +1d;";
	$f[]=$GLOBALS["common_text"];
	$f[]="\t}";
	$f[]="}";
	$f[]="";
	$f[]="#---------------- END HTTP BLOCK---------------- ";	
	$f[]="}";
	@file_put_contents("/etc/squid3/hypercache-client.conf", @implode("\n", $CLIENTS_RULE));
	@file_put_contents("/etc/hypercache/hypercache.conf", @implode("\n", $f));
}

function build_location($ligne,$slice=false){
	$ID=$ligne["ID"];
	$extlists=trim($ligne["extlists"]);
	$cacheid=$ligne["cacheid"];
	$description=utf8_encode($ligne["description"]);
	$key_zone=$GLOBALS["hypercache_caches_list"][$cacheid];
	$proxy_cache_valid=intval($ligne["proxy_cache_valid"]);
	if($proxy_cache_valid==0){$proxy_cache_valid=15;}
	$type=$ligne["type"];
	$f=array();
	if($type==1){
		$extlists="dat\npsf\ncab\nexe\nzip";
		$f[]="# -------------------------------------------------------------------------------------------------";
		$f[]="\tlocation ~* \/filestreamingservice\/files\/* {";
		$f[]="\t\tproxy_ignore_headers Expires Cache-Control Vary;";
		$f[]=$GLOBALS["mainHead_text"];
		$f[]="\t\tproxy_set_header Range '';";
		$f[]="\t\tadd_header X-Cache-Rule \"$description (filestreamingservice)\";";
		$f[]="\t\tadd_header X-Cache-key \"\windowsupdate.com\$uri\";";
		$f[]="\t\tproxy_cache_key \windowsupdate.com\$uri;";
		$f[]="\t\tproxy_cache $key_zone;";
		
		$f[]="\t\tproxy_cache_valid 200 {$proxy_cache_valid}d;";
		$f[]="\t\tproxy_cache_valid 206 {$proxy_cache_valid}d;";
		$f[]="\t\tproxy_cache_valid 301 1m;";
		$f[]="\t\tproxy_cache_valid 404 1m;";
		$f[]="\t\tproxy_cache_valid 500 1m;";
		$f[]=$GLOBALS["common_text"];
		$f[]="\t}";
	}
	
	
	if($extlists==null){
		if(count($f)>0){return @implode("\n", $f);}
		return null;}
	
		
	$pattern=build_pattern_extensions($extlists);
	
	
	if($pattern==null){
		if(count($f)>0){return @implode("\n", $f);}
		return null;
	}
	
	
	
	$f[]="# -------------------------------------------------------------------------------------------------";
	$f[]="\tlocation ~* $pattern {";
	$f[]=$GLOBALS["mainHead_text"];
	$f[]="\t\tproxy_ignore_headers Expires Cache-Control Vary;";
	$f[]="\t\texpires {$proxy_cache_valid}d;";
	$f[]="\t\tadd_header X-Cache-Rule \"$description (location)\";";
	
	$f[]="\t\tproxy_set_header Range '';";
	$f[]="\t\tproxy_cache_valid 200 {$proxy_cache_valid}d;";
	$f[]="\t\tproxy_cache_valid 206 {$proxy_cache_valid}d;";
	$f[]="\t\tproxy_cache_valid 302 1m;";
	$f[]="\t\tproxy_cache_valid 301 1m;";
	$f[]="\t\tproxy_cache_valid 404 1m;";
	$f[]="\t\tproxy_cache_valid 500 1m;";
	$f[]="\t\tproxy_cache $key_zone;";
	if($type==1){
		$f[]="\t\tproxy_hide_header Etag;";
		$f[]="\t\tadd_header X-Cache-key \"\windowsupdate.com\$uri\";";
		$f[]="\t\tproxy_cache_key \windowsupdate.com\$uri;";
	}else{
		$f[]="\t\tadd_header X-Cache-key \"\$http_host\$uri\";";
		$f[]="\t\tproxy_cache_key \$http_host\$uri;";
	}
	$f[]=$GLOBALS["common_text"];
	$f[]="\t}";
	

	

	
	
	
	
	return @implode("\n", $f);
}

function build_pattern_sites($siteslist){
	$xDOMS=array();
	$xEXT=array();
	if(trim($siteslist)==null){return null;}
	$zDOMs=explode("\n",$siteslist);
	foreach ($zDOMs as $line){
		if(trim($line)==null){continue;}
		if(is_regex($line)){
			$xDOMS[]="\tserver_name   ~$line";
			continue;
		}
		
		$line=str_replace(".", "\.", $line);
		$line=str_replace("*", ".*?", $line);
		$xDOMS[]="\tserver_name  ~$line";
	
	}	
	
	return @implode("\n", $xDOMS);
}

function build_pattern_extensions($extlists){
	$xEXT=array();
	$zEXT=explode("\n",$extlists);
	foreach ($zEXT as $line){
		if(trim($line)==null){continue;}
		if(is_regex($line)){
			$xEXT[]="$line";
			continue;
		}
		$line=str_replace(".", "\.", $line);
		$line=str_replace("*", ".*?", $line);
		$xEXT[]="\.$line";
	
	}
	
	if(count($xEXT)>0){
		return "(".@implode("|", $xEXT).")(\?|$)";
	}
	
}

function build_pattern($siteslist,$extlists){
	$xDOMS=array();
	$xEXT=array();
	$regex=null;
	$zDOMs=explode("\n",$siteslist);
	foreach ($zDOMs as $line){
		if(trim($line)==null){continue;}
		if(is_regex($line)){
			$xDOMS[]="$line";
			continue;
		}
		$line=str_replace(".", "\.", $line);
		$line=str_replace("*", ".*?", $line);
		$line=str_replace("/", "\/", $line);
		$xDOMS[]=$line;

	}
	$zEXT=explode("\n",$extlists);
	foreach ($zEXT as $line){
		if(trim($line)==null){continue;}
		if(is_regex($line)){
			$xEXT[]="reg:$line";
			continue;
		}
		$line=str_replace(".", "\.", $line);
		$line=str_replace("*", ".*?", $line);
		$line=str_replace("/", "\/", $line);
		$xEXT[]=$line;

	}

	if(count($xDOMS)>0){
		if(count($xDOMS)>1){
			$regex="(".@implode("|", $xDOMS).")";
		}else{
			$regex=@implode("", $xDOMS);
			
		}
	}
	
	if(count($xEXT)==0){return $regex;}
	http://static.nfl.com/static/content/public/image/fantasy/transparent/200x200/ATL.png
	
	if(count($xEXT)>1){
		$regex="$regex\/.*?\.(".@implode("|", $xEXT).")(\?|$)";	
	}else{
		$regex="$regex\/.*?\.".@implode("", $xEXT)."(\?|$)";
	}
	return $regex;
}

function build_pattern2($siteslist,$extlists){
	$xDOMS=array();
	$xEXT=array();
	$zDOMs=explode("\n",$siteslist);
	foreach ($zDOMs as $line){
		if(trim($line)==null){continue;}
		if(is_regex($line)){
			$xDOMS[]="reg:$line";
			continue;
		}
		
		$line=str_replace("*", ".*?", $line);
		$xDOMS[]=$line;

	}
	$zEXT=explode("\n",$extlists);
	foreach ($zEXT as $line){
		if(trim($line)==null){continue;}
		if(is_regex($line)){
			$xEXT[]="reg:$line";
			continue;
		}
		
		$line=str_replace("*", ".*?", $line);
		$xEXT[]=$line;

	}

	if(count($xDOMS)>0){
		$regex="(".@implode("|", $xDOMS).")";
	}
	if(count($xEXT)>0){
		$regex="$regex\/.*?(".@implode("|", $xEXT).")$";
	}
	return $regex;
}

function is_regex($pattern){
	$f[]="{";
	$f[]="[";
	$f[]="+";
	$f[]="\\";
	$f[]="?";
	$f[]="$";
	$f[]=".*";

    foreach ($f as $key=>$val){
		if(strpos(" $pattern", $val)>0){return true;}
	}
}


function scandisk(){
	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".scandisk.pid";
	$pidTime="/etc/artica-postfix/pids/exec.hypercache-server.php.scandisk.time";
	$unix=new unix();
	
	if(!$GLOBALS["FORCE"]){
		if(system_is_overloaded(basename(__FILE__))){
			squid_admin_mysql(1, "{OVERLOADED_SYSTEM}, aborting scanning HyperCache caches", null,__FILE__,__LINE__);
			exit();
		}
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid)){exit();}
		$time=$unix->file_time_min($pidTime);
		if($time<240){exit();}
		
	}
	
	
	@unlink($pidfile);
	@unlink($pidTime);
	@file_put_contents($pidfile, getmypid());
	@file_put_contents($pidTime,time());
	
	
	$q=new mysql_squid_builder();
	
	$results=$q->QUERY_SQL("SELECT ID,directory,keys_zone,max_size FROM hypercache_caches");
	$count=mysqli_num_rows($results);
	
	$TOTAL_SIZE=0;
	$TOTAL_MAX_SIZE=0;
	$c=0;
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$c++;
		$prc=$c/$count;
		$prc=$prc*100;
		
		if($prc>95){$prc=95;}
		$directory=$ligne["directory"];
		$keys_zone=$ligne["keys_zone"];
		echo "Scanning $directory\n";
		
		if(!is_dir($directory)){
			@mkdir($directory,0755,true);
			@chown($directory, "squid");
			@chgrp($directory, "squid");
		}
		
		build_progress($prc, "Scanning $keys_zone");
		
		$ID=$ligne["ID"];
		$max_size=$ligne["max_size"];
		$size=$unix->DIRSIZE_BYTES_NOCACHE($directory);
		$TOTAL_MAX_SIZE=$TOTAL_MAX_SIZE+$max_size;
		$TOTAL_SIZE=$TOTAL_SIZE+$size;
		
		echo "$directory == $size\n";
		
		$q->QUERY_SQL("UPDATE hypercache_caches SET CurrentSize='$size' WHERE ID=$ID");
		if(!$q->ok){
			echo $q->mysql_error;
			build_progress(110, "MySQL failed");
			return;
		}
		

	}
	$TOTAL_MAX_SIZE=$TOTAL_MAX_SIZE*1024;
	$TOTAL_SIZE=$TOTAL_SIZE/1024;
	
	$percent=round( ($TOTAL_SIZE/$TOTAL_MAX_SIZE)*100,2);
	echo "Global user: $percent\n";
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("HyperCacheServicePercent", $percent);
	
	build_progress(100, "{success}");
	
}

function purge($ID=0){
	$unix=new unix();
	$ID=intval($ID);
	echo "Purge ID: $ID\n";
	if($ID==0){
		build_progress(110, "{error}, Wrong ID");
		return;
	}
	$q=new mysql_squid_builder();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT directory,keys_zone FROM hypercache_caches WHERE ID='$ID'"));
	$rm=$unix->find_program("rm");
	echo "Remove {$ligne["directory"]}\n";
	build_progress(50, "{remove}, {$ligne["keys_zone"]}");
	system("$rm -rfv {$ligne["directory"]}/*");
	build_progress(90, "{reloading}");
	reload();
	$size=$unix->DIRSIZE_BYTES_NOCACHE($ligne["directory"]);
	$q->QUERY_SQL("UPDATE hypercache_caches SET CurrentSize='$size' WHERE ID=$ID");
	build_progress(100, "{done}");
}

function delete($ID=0){
	$unix=new unix();
	$ID=intval($ID);
	echo "Purge ID: $ID\n";
	if($ID==0){
	build_progress(110, "{error}, Wrong ID");
			return;
	}
	$q=new mysql_squid_builder();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT directory,keys_zone FROM hypercache_caches WHERE ID='$ID'"));
	$rm=$unix->find_program("rm");
	echo "Remove {$ligne["directory"]}\n";
	build_progress(50, "{remove}, {$ligne["keys_zone"]}");
	system("$rm -rfv {$ligne["directory"]}");
	build_progress(60, "{stopping_service}");
	stop(true);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: HyperCache Proxy {building_settings}..\n";}
	build_progress(80, "{reconfigure}");
	build();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: HyperCache Proxy...\n";}
	build_progress(90, "{starting_service}");
	if(!start(true)){
		build_progress(110, "{failed2}");
		return;
	}

	$q->QUERY_SQL("DELETE FROM hypercache_caches WHERE ID=$ID");
	build_progress(100, "{done}");
	
}

function build_progress($pourc,$text){
	$date=date("Y-m-d H:i:s");
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/hypercache.maintenance.progress";
	echo "$date: [{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
}
