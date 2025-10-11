<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["RELOAD"]=false;
$GLOBALS["NOCHECK"]=false;
$GLOBALS["pidStampReload"]="/etc/artica-postfix/pids/".basename(__FILE__).".Stamp.reload.time";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(count($argv)>0){
    if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
    if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
    if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
    if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
    if(preg_match("#--nocheck#",implode(" ",$argv),$re)){$GLOBALS["NOCHECK"]=true;}
    $GLOBALS["ARGVS"]=implode(" ",$argv);
}




$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');
include_once(dirname(__FILE__).'/ressources/class.nginx.params.inc');
include_once(dirname(__FILE__).'/ressources/class.nginx.reverse.http.inc');

    if(isset($argv[1])){
        if($argv[1]=="--cache-disk-scan"){cache_disk_scan();exit;}
        if($argv[1]=="--dump-modules"){$GLOBALS["OUTPUT"]=true;dump_nginx_params();exit;}
        if($argv[1]=="--reconfigure-all-reboot"){$GLOBALS["OUTPUT"]=true;reconfigure_all();exit;}
        if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
        if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
        if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
        if($argv[1]=="--restart-build"){$GLOBALS["OUTPUT"]=true;restart_build();exit();}
        if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
        if($argv[1]=="--force-restart"){$GLOBALS["OUTPUT"]=true;force_restart();exit();}
        if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;$GLOBALS["RECONFIGURE"]=true;build();exit();}
        if($argv[1]=="--main"){$GLOBALS["OUTPUT"]=true;$GLOBALS["RECONFIGURE"]=true;build();exit();}
        if($argv[1]=="--status"){die();}
        if($argv[1]=="--rotate"){exit;}
        if($argv[1]=="--awstats"){awstats();exit;}
        if($argv[1]=="--framework"){$GLOBALS["OUTPUT"]=true;framework();exit;}
        if($argv[1]=="--tests-sources"){test_sources();exit;}
        if($argv[1]=="--patch"){patching();exit;}
        if($argv[1]=="--upgrade-from"){upgrade_from($argv[2]);exit;}
        if($argv[1]=="--default-one"){exit;}

        if($argv[1]=="--purge-cache"){$GLOBALS["OUTPUT"]=true;purge_cache($argv[2]);exit;}
        if($argv[1]=="--purge-all-caches"){$GLOBALS["OUTPUT"]=true;purge_all_caches();exit;}
        if($argv[1]=="--import-file"){$GLOBALS["OUTPUT"]=true;import_file();exit;}
        if($argv[1]=="--import-bulk"){$GLOBALS["OUTPUT"]=true;import_bulk();exit;}
        if($argv[1]=="--upgrade1"){upgrade_procedure();exit;}
        if($argv[1]=="--mem"){exit;}
        if($argv[1]=="--mymem"){exit;}
        if($argv[1]=="--syslog"){exit;}
        if($argv[1]=="--reconfigure-all-sites"){reconfigure_all_sites();exit;}
        if($argv[1]=="--proxy-protocol"){patch_proxy_protocol();exit;}
        if($argv[1]=="--rotatemodsec"){rotate_modsec_log();exit;}
        if($argv[1]=="--caches-dir"){build_caches_dir_exec();exit;}
        if($argv[1]=="--dirs"){check_directories();exit;}
}

	
	echo "Unable to understand this command\n";
	echo "Should be:\n";
	echo "--framework...........: Build framework\n";
	echo "--caches-status.......: Build caches status\n";
	echo "--build-default.......: Build default website\n";
	


function nginx_version():string{
	if(isset($GLOBALS["nginx_version"])){return $GLOBALS["nginx_version"];}
	$unix=new unix();
	$bin=$unix->find_program("nginx");
	if(!is_file($bin)){return "0";}

	exec("$bin -v 2>&1",$array);
	foreach ($array as $line){
		if(preg_match("#\/([0-9\.\-]+)#i", $line,$re)){
			$GLOBALS["nginx_version"]=$re[1];
			return $re[1];}
			if($GLOBALS['VERBOSE']){echo "nginx_version(), $line, not found \n";}
	}
 return "";
}
function cache_disk_scan_progress($text,$pourc):bool{
    _out($text);
    $unix=new unix();
   return $unix->framework_progress($pourc,$text,"nginx.scan.progress");
}
function cache_disk_scan():bool{
    cache_disk_scan_progress("{analyze}",50);
    system("/usr/sbin/artica-phpfpm-service -nginx-cache -debug");
    return cache_disk_scan_progress("{analyze} {success}",100);
}
function upgrade_procedure(){
    $unix           = new unix();

    upgrade_procedure_progress("{reconfigure}",15);
    shell_exec("/usr/local/sbin/reverse-proxy -nginx-reconfigure");
    upgrade_procedure_progress("{installing}",80);
    shell_exec("/usr/sbin/artica-phpfpm-service -nginx-all");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NGINX_MIGRATION_440",0);
    upgrade_procedure_progress("{reconfigure} {done}",100);
}
function upgrade_procedure_progress($text,$pourc){
    _out($text);
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"nginx.reconfigure.progress");
}

function rotate_modsec_log():bool{
    return false;
    $unix=new unix();
    $echo=$unix->find_program("echo");
    $ModSecuritySecRotate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecuritySecRotate"));
    $tfile="/var/log/modsec_audit.log";
    $tsize=@filesize($tfile);
    $tsize=$unix->FormatBytes($tsize/1024);

    if($ModSecuritySecRotate==0){
        shell_exec("$echo \"\" >$tfile");
        squid_admin_mysql(1,"Removing Web Application Firewall realtime log file ($tsize)",null,__FILE__,__LINE__);
        reload_usr1();
        return true;
    }

    $working_dir="/home/artica/modsec-rotate";
    if(!is_dir("$working_dir")){@mkdir($working_dir,0755,true);}
    $cp=$unix->find_program("cp");
    $target_file="$working_dir/".time().".log";
    shell_exec("$cp -f $tfile $target_file");
    shell_exec("$echo \"\" >$tfile");
    squid_admin_mysql(1,"moving/backup Web Application Firewall realtime log file ($tsize)",null,__FILE__,__LINE__);
    reload_usr1();
    return true;

}

function dump_nginx_params(){
	
		$unix=new unix();
		$ARRAY=$unix->NGINX_COMPILE_PARAMS();
		

	foreach ($ARRAY["MODULES"] as $a=>$b){echo "Module: \"$a\"\n";}
    foreach ($ARRAY["ARGS"] as $a=>$b){echo "Params: \"$a\"\n";}


    if(!isset($ARRAY["MODULES"]["ngx_http_geoip2_module-1.0"])){
        echo "NginxHTTPGeoIP2: \"0\"\n";
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NginxHTTPGeoIP2", 0);
    }else{
        echo "NginxHTTPGeoIP2: \"1\"\n";
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NginxHTTPGeoIP2", 1);
    }
    if(!isset($ARRAY["MODULES"]["ngx_http_modsecurity-1.0"])){
        echo "NginxHTTPModSecurity: \"0\"\n";
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NginxHTTPModSecurity", 0);
    }else{
        echo "NginxHTTPModSecurity: \"1\"\n";
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NginxHTTPModSecurity", 1);
    }



	if(!isset($ARRAY["ARGS"]["HTTP_SUB_MODULE"])){
        echo "NginxHTTPSubModule: \"0\"\n";
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NginxHTTPSubModule", 0);
    }else{
        echo "NginxHTTPSubModule: \"1\"\n";
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NginxHTTPSubModule", 1);
    }

	if(!isset($ARRAY["ARGS"]["WITH-HTTP_AUTH_REQUEST_MODULE"])){
		echo "NginxHTTPAuthRequest: \"0\"\n";
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NginxHTTPAuthRequest", 0);
	}else{
		echo "NginxHTTPAuthRequest: \"1\"\n";
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NginxHTTPAuthRequest", 1);
	}
    if(!isset($ARRAY["ARGS"]["HTTP_V2_MODULE"])){
        echo "NginxHTTPV2Module: \"0\"\n";
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NginxHTTPV2Module", 0);
    }else{
        echo "NginxHTTPV2Module: \"1\"\n";
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NginxHTTPV2Module", 1);
    }
}

function build_progress_upgrade_from($text,$pourc){
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"nginx.upgrade.progress");
}

function upgrade_from($fname):bool{
    $unix=new unix();
    $fullpath="/usr/share/artica-postfix/ressources/conf/upload/$fname";
    $dtpath="/usr/share/artica-postfix/ressources/conf/upload/nginx.tar.gz";
    if(!is_file($fullpath)){
        build_progress_upgrade_from("$fname no such file...",110);
        return false;
    }

    @copy($fullpath,$dtpath);
    @unlink($fullpath);

    $MINZ["/usr/lib/x86_64-linux-gnu/liblua5.2.so.0"]="liblua5.2-0";
    $MINZ["/usr/lib/x86_64-linux-gnu/libfuzzy.so.2"]="libfuzzy2";

    foreach ($MINZ as $path=>$package){
        if(!is_file($path)){
            build_progress_upgrade_from("$fname {installing} $package",20);
            $unix->DEBIAN_INSTALL_PACKAGE($package);
        }
    }

    foreach ($MINZ as $path=>$package){
        if(!is_file($path)){
            build_progress_upgrade_from("$fname {missing} $package",110);
            @unlink($dtpath);
            return false;
        }
    }


    $TEMPDIR=$unix->TEMP_DIR()."/nginx";
    $rm=$unix->find_program("rm");
    $tar=$unix->find_program("tar");
    $cp=$unix->find_program("cp");
    if(is_dir($TEMPDIR)){
        shell_exec("$rm -rf $TEMPDIR");
    }
    @mkdir($TEMPDIR,0755,true);
    build_progress_upgrade_from("$fname {extracting_package}",30);
    echo "$tar xf \"$dtpath\" -C $TEMPDIR/";
    shell_exec("$tar xf \"$dtpath\" -C $TEMPDIR/");
    @unlink($dtpath);

    $directories[]="/usr/sbin";
    $directories[]="/opt/verynginx";
    $directories[]="/usr/share/nginx";
    $directories[]="/usr/local/modsecurity";

    foreach ($directories as $dir){
        build_progress_upgrade_from("$fname {installing}",60);
        $srcdir="$TEMPDIR$dir";
        if(!is_dir($srcdir)){
            build_progress_upgrade_from("Missing $srcdir",110);
            shell_exec("$rm -rf $TEMPDIR");
            return false;
        }
        if(!is_dir($dir)){@mkdir($dir,0755,true);}
        shell_exec("$cp -rfd $srcdir/* $dir/");
    }
    if(is_file("/etc/init.d/nginx")) {
        build_progress_upgrade_from("{restarting_service}", 70);
        shell_exec("/etc/init.d/nginx restart");
    }

    build_progress_upgrade_from("{restarting_service}", 80);
    shell_exec("/etc/init.d/artica-status restart --force");
    $nginx=$unix->find_program("nginx");
    if(!is_file($nginx)){
        build_progress_upgrade_from("{failed} binary not found", 110);
        return false;
    }
    build_progress_upgrade_from("{cleaning}", 90);
    shell_exec("$rm -rf $TEMPDIR");
    $version=null;
    exec("$nginx -V 2>&1",$results);

    foreach ($results as $value){
        if(preg_match("#nginx version: .*?\/([0-9\.]+)#", $value,$re)){
            $version=$re[1];
        }
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_NGINX_VERSION", $version);
    build_progress_upgrade_from("$fname {success} v.$version",100);
    return true;

}

function security_limit($APACHE_USER){
    $unix=new unix();
    if (!method_exists("unix","SystemSecurityLimitsConf")){
        return;
    }
    $unix->SystemSecurityLimitsConf();
}
	
function touch_pid($pid=0):bool{

    $pidpath="/var/run/nginx.pid";
    if(is_file($pidpath)){
        @chmod($pidpath,0755);
        if($pid>0){@file_put_contents($pidpath,$pid);}
        return true;
    }
    @touch($pidpath);
    @chmod($pidpath,0755);
    if($pid>0){@file_put_contents($pidpath,$pid);}
    return true;
}

function lua_package_path():bool{
    $f=explode("\n",@file_get_contents("/etc/nginx/nginx.conf"));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^lua_package_path#",$line)){
            _out("LUA reference [OK]");
            return true;
        }
    }
    _out("LUA reference [BAD]");
    build(true);
    return true;

}



function build($nocheck=false):bool{
    $unix=new unix();
    system("/usr/local/sbin/reverse-proxy -nginx-reconfigure -debug");
    return $unix->framework_exec("exec.status.php --nginx");

}

function clean_pagespeed_in_subconf($path=null):bool{
    if(!is_file($path)){return false;}
    $zz=explode("\n",@file_get_contents($path));
    $newT=array();
    foreach ($zz as $line){
        if(preg_match("#^include\s+.*?pagespeed#",trim($line))){continue;}
        $newT[]=$line;
    }
    @file_put_contents($path,@implode("\n",$newT));
    return true;
}

function build_caches_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"nginx.caches-dir.progress");
}

function build_caches_dir_is_global():bool{
    $f = explode("\n", @file_get_contents("/etc/nginx/nginx.conf"));
    foreach ($f as $line) {
        $line = trim($line);
        if (preg_match("#include\s+.*?caches\.conf#", $line)) {
            return true;
        }
    }
    return false;
}

function check_directories():bool{
    $unix=new unix();
    $pidTime="/etc/artica-postfix/pids/". basename(__FILE__).".".__FUNCTION__.".time";
    if($unix->file_time_min($pidTime)<1){return false;}
    @unlink($pidTime);
    @file_put_contents($pidTime, time());

    $f[]="/etc/nginx/stream.d";
    $f[]="/etc/nginx/reverse.d";
    $f[]="/etc/nginx/backends.d";
    $f[]="/etc/nginx/conf.d";
    $f[]="/etc/nginx/sites-enabled";
    $f[]="/etc/nginx/local-sites";
    $f[]="/etc/nginx/wordpress";
    $f[]="/etc/nginx/ElasticSearch";
    $f[]="/etc/nginx/fastcgi";
    $f[]="/etc/nginx/caches";



    foreach ($f as $directory){
        if(!is_dir($directory)){
            @mkdir($directory,0755,true);
        }
        @chmod($directory,0755);
        @chown($directory,"www-data");
    }
    $fname[]="/etc/nginx/caches.conf";
    $fname[]="/etc/nginx/ElasticSearch/ElasticSearch.conf";

    foreach ($fname as $filepath){
        if(!is_file($filepath)){@touch($filepath);}
        @chown($filepath,"www-data");
    }
    return true;
}

function build_caches_dir_exec(){
    $tfile="/etc/nginx/caches.conf";
    if(is_file($tfile)){
        $md51=crc32_file($tfile);
    }
    build_caches_progress(15,"{building}");

    if(!build_caches_dir_is_global()){
        build();
        build_caches_progress(100,"{building} {success}");
        return false;
    }


    if(!build_caches_dir()){
        build_caches_progress(110,"{building} {failed}");
        return false;
    }
    build_caches_progress(50,"{building} {checking}");
    $md52=crc32_file($tfile);
    if($md51==$md52){
        build_caches_progress(100,"{building} {nothing_to_do}");
        return true;
    }
    if(!build_nginx_check_conf()){
        build_caches_progress(110,"{building} {checking} {failed}");
        @file_put_contents($tfile,"# Error when compiling");
        return false;
    }
    build_caches_progress(50,"{building} {reloading}");
    reload();
    build_caches_progress(100,"{building} {success}");
}
function build_nginx_check_conf(){
    $unix=new unix();
    $nginx=$unix->find_program("nginx");
    echo "Testing configuration...\n";
    exec("$nginx -c /etc/nginx/nginx.conf -t 2>&1",$results);
    foreach ($results as $line){
        echo "'$line'\n";
        if(preg_match("#the configuration file.*?syntax is ok#",$line)){
            return true;
        }
        if(preg_match("#configuration file.*?test is successful#",$line)){
            return true;
        }

    }
    return false;
}

function build_caches_dir(){
    $tfile="/etc/nginx/caches.conf";
    @file_put_contents($tfile,"");
    return false;
    $NginxProxyStorePath=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxProxyStorePath"));
    if($NginxProxyStorePath==null){$NginxProxyStorePath="/home/nginx";}
    if(!is_dir($NginxProxyStorePath)) {
        @mkdir($NginxProxyStorePath, 0755, true);
    }
    $unix=new unix();
    $phpcache="$NginxProxyStorePath/php";
    if(!is_dir($phpcache)){
        @mkdir($phpcache,0755,true);
        $unix->chown_func("www-data","www-data", $phpcache);
    }

    $tfile="/etc/nginx/caches.conf";
    $nginxCachesDir=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginxCachesDir"));
    if($nginxCachesDir==0) {
        @file_put_contents($tfile, "#Caches are disabled");
        return true;
    }

    $NginxPHPCacheSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxPHPCacheSize"));
    if($NginxPHPCacheSize==0){$NginxPHPCacheSize=2;}

    $f[]="fastcgi_cache_path $phpcache levels=1:2 keys_zone=php:500m inactive=60m max_size={$NginxPHPCacheSize}G;";
    @file_put_contents($tfile,@implode("\n",$f));
    return true;
}

function pagespeed_remove(){
    $TargetDir="/etc/nginx/wordpress";
    if(!is_dir($TargetDir)){return;}
    if ($handle = opendir($TargetDir)) {
        while (false !== ($fileZ = readdir($handle))) {
            if ($fileZ == ".") {continue;}
            if ($fileZ == "..") {continue;}
            $fullpath = "$TargetDir/$fileZ";
            if (is_dir($fullpath)) {continue;}
            if (!preg_match("#pagespeed\.([0-9]+)\.module#", $fileZ, $re)) {continue;}
            $wordpress_id = $re[1];
            clean_pagespeed_in_subconf("/etc/nginx/wordpress/$wordpress_id.conf");
            @unlink($fullpath);
        }
    }
}





function patch_proxy_protocol(){
    $sock=new socksngix(0);
    $NginxProxyProtocol=intval($sock->GET_INFO("NginxProxyProtocol"));
    $dirs[]="reverse.d";
    $dirs[]="sites-enabled";
    $dirs[]="stream.d";

    foreach ($dirs as $subdir){
        $BaseWorkDir="/etc/nginx/$subdir";
        if($GLOBALS["VERBOSE"]){echo "Scanning $BaseWorkDir...\n";}
        if (!$handle = opendir($BaseWorkDir)) {continue;}
        while (false !== ($filename = readdir($handle))) {
            if ($filename == ".") {continue;}
            $tfile="$BaseWorkDir/$filename";
            if(!is_file($tfile)){continue;}
            if($GLOBALS["VERBOSE"]){echo "Scanning $tfile...\n";}
            if(!is_file($tfile)){_out("$tfile no such file!!");continue;}
            $CHANGE_FILE=false;
            $scontent=explode("\n",@file_get_contents($tfile));
            if(count($scontent)<10){
                _out("Warning, $tfile no data parsed!!");
                continue;
            }

            $final_content=array();
            foreach ($scontent as $sline){
                $sline=trim($sline);
                if($sline==null){continue;}
                if(!preg_match("#^listen\s+(.+?);#",$sline,$re)) {
                    $final_content[]=$sline;
                    continue;
                }
                if($NginxProxyProtocol==1){
                    if(!preg_match("#proxy_protocol#",$sline)){
                        $final_content[]="listen ".trim($re[1])." proxy_protocol;";
                        $CHANGE_FILE=true;
                        continue;
                    }
                    $final_content[]=$sline;
                    continue;
                }

                if(preg_match("#proxy_protocol#",$sline)){
                    $sline=str_ireplace("proxy_protocol","",$sline);
                    $CHANGE_FILE=true;
                    $final_content[]=$sline;
                    continue;
                }
                $final_content[]=$sline;
            }
             if(count($final_content)<10){
                _out("{warning} $tfile, final content as no array");
                 continue;
             }
             if($CHANGE_FILE) {
                 _out("Modify $tfile configuration to accept proxy protocol");
                 @file_put_contents($tfile, @implode("\n", $final_content));
                 continue;
             }
             if($GLOBALS["VERBOSE"]){
                 echo "$tfile nothing to be done\n";
             }

            }
            closedir($handle);
        }
}


function patching():bool{
    $RESTART=false;
    upgrade_procedure_progress("{reconfigure}",30);
    upgrade_procedure_progress("{reconfigure}",35);
    upgrade_procedure_progress("{reconfigure}",40);
    upgrade_procedure_progress("{reconfigure}",50);
    return true;
}

function authenticator():bool{return false;}
function ToSyslog($text){
	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}


function PID_NUM(){
    $unix=new unix();
	$filename="/var/run/nginx.pid";
	$pid=$unix->get_pid_from_file($filename);
	if($unix->process_exists($pid)){return $pid;}
	$pid=$unix->PIDOF("/usr/sbin/nginx");
	if($unix->process_exists($pid)){return $pid;}
	return 0;
}

function GHOSTS_PID(){
	$unix=new unix();
	$pidof=$unix->find_program("pidof");
	exec("$pidof /usr/sbin/nginx 2>&1",$results);
	
	$zpid=explode(" ",$results[0]);
	foreach ($zpid as $pid){
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Killing PID $pid\n";}
			$unix->KILL_PROCESS($pid,9);
		}
	}
}

//##############################################################################
function PID_PATH():string{
	return '/var/run/nginx.pid';
}
//##############################################################################


function reload():bool{
    $unix       = new unix();
    $MasterPid  = PID_NUM();
    $chown      = $unix->find_program("chown");

    system("$chown www-data:www-data /var/log/nginx/access.log");
    system("$chown www-data:www-data /var/log/nginx/error.log");
    if(!$unix->process_exists($MasterPid)){
        return start(true);
    }

    $nginx=$unix->find_program("nginx");
    echo "Reloading PID $MasterPid\n";
    system("$nginx -c /etc/nginx/nginx.conf -s reload");
    $MasterPid  = PID_NUM();
    if(!$unix->process_exists($MasterPid)){return false;}
    touch_pid($MasterPid);
    return true;
}

function reconfigure_all_sites():bool{
    $unix       = new unix();
    $php        = $unix->LOCATE_PHP5_BIN();
    $pidfile    = "/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pid        = $unix->get_pid_from_file($pidfile);
    $q          = new lib_sqlite("/home/artica/SQLITE/nginx.db");

    if($unix->process_exists($pid,basename(__FILE__))){
        $time=$unix->PROCCESS_TIME_MIN($pid);
        build_progress("Already executed",110);
        _rec("Nginx Already Artica task running PID $pid since {$time}mn");
        return false;
    }
    @file_put_contents($pidfile,getmypid());

    $zpaths[]   = "/etc/nginx/sites-enabled";
    $zpaths[]   = "/etc/nginx/stream.d";
    $zpaths[]   = "/etc/nginx/upstream.d";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NginxEmergency",0);
    build_progress("Cleaning",10);

    foreach ($zpaths as $directory){
        if(!is_dir($directory)){continue;}
        if (!$handle = opendir($directory)) {continue;}
        while (false !== ($filename = readdir($handle))) {
            if($filename=="."){continue;}
            if($filename==".."){continue;}
            $TargetDir="$directory/$filename";
            if(!is_file($TargetDir)){continue;}
            _rec("Removing $filename");
            @unlink($TargetDir);
        }
    }

    $prc        = 10;
    $zprog      = 0;
    $results    = $q->QUERY_SQL("SELECT ID,servicename FROM nginx_services");
    $Max        = count($results);

    foreach ($results as  $ligne){
        build_progress("Cleaning",10);
        $zprog++;
        $xprc=$zprog/$Max;
        $xprc=round($xprc*100);
        if( $xprc>10 && $xprc<95 ){$prc=$xprc;}
        $servicename=$ligne["servicename"];
        $ID=$ligne["ID"];
        build_progress("building $servicename - $ID",$prc);
        system("/usr/sbin/artica-phpfpm-service -nginx-single $ID -debug");
    }

    $nginx_service=new nginx_service();

    build_progress("{reloading}...",95);
    if(!reload()){
        build_progress("{reloading} {failed}...",110);
        return false;
    }

    build_progress("{success}...",100);
    $nginx_service->backup_config();
    return true;
}

function force_restart(){
	$unix=new unix();
	$MasterPid=PID_NUM();
	$chown=$unix->find_program("chown");
	$php=$unix->LOCATE_PHP5_BIN();
	system("$chown www-data:www-data /var/log/nginx/access.log");
	system("$chown www-data:www-data /var/log/nginx/error.log");
	if(!$unix->process_exists($MasterPid)){
		start(true);
		return;
	}	
	if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: Reconfiguring PHP-FPM\n";}
	system("/usr/sbin/artica-phpfpm-service -reload-webconsole -debug >/dev/null 2>&1");
	system("$php /usr/share/artica-postfix/exec.lighttpd.php --fpm-reload >/dev/null 2>&1");
	
	$pids=$unix->PIDOF_PATTERN_ALL("nginx: worker process");
	$kill=$unix->find_program("kill");
	
	if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: Nginx Reloading master $MasterPid\n";}
	system("$kill -HUP $MasterPid");
	
	foreach ($pids as $pid=>$ofnone){
		$maitre=$unix->PROCESS_PPID($pid);
		if($maitre<>$MasterPid){continue;}
		if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: Nginx Shutdown child $pid\n";}
		system("$kill -15 $pid");
		
	}
		
	
}
function reconfigure_all(){
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.": OK...\n";}
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		build_progress("Already executed",110);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
    if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.": OK...\n";}

	build();
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.": OK...\n";}
	build_progress("{stopping_service}",80);
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.": OK...\n";}
	stop(true);
	build_progress("{starting_service}",90);
	start(true);
	build_progress("{done}",100);
	squid_admin_mysql(2, "Reconfiguring main service done [{action}={start}]", null,__FILE__,__LINE__);
}

function reload_usr1():bool{
    $unix=new unix();
    $pid=PID_NUM();
    if($unix->process_exists($pid)){
        squid_admin_mysql(1,"{APP_NGINX} {reloading} Pid $pid USR1",null,__FILE__,__LINE__);
        $kill=$unix->find_program("kill");
        shell_exec("$kill -USR1 $pid");
        return true;
    }

    squid_admin_mysql(0,"{APP_NGINX} {reloading} {failed} {not_running}",null,__FILE__,__LINE__);
    return true;
}

function restart(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	nginx_admin_mysql(1, "{restart} {APP_NGINX} [action=info]", null,__FILE__,__LINE__);
	stop(true);
	build();
	start(true);
}

function restart_build():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NGINX_SP663",1);
	build_progress_restart("{stopping_service}",50);
	shell_exec("/usr/sbin/artica-phpfpm-service -nginx-stop -debug");
    shell_exec("/usr/local/sbin/reverse-proxy -nginx-reconfigure -debug");
	build_progress_restart("{starting_service}",90);
    shell_exec("/usr/sbin/artica-phpfpm-service -nginx-start -debug");
	return build_progress_restart("{starting_service} {success}",100);
}


function start($aspid=false):bool{
    $unix=new unix();
    $bash=$unix->sh_command("/usr/sbin/artica-phpfpm-service -nginx-start");
    touch_pid();
	$unix->go_exec($bash);
    return true;
}
function _out($text):bool{
    $unix=new unix();
    $unix->ToSyslog("[START] $text",false,"nginx");
    $date=date("H:i:s");
    echo "Starting......: $date [INIT]: Nginx service: $text\n";
    return true;
}
function _rec($text):bool{
    $unix=new unix();
    $unix->ToSyslog("[BUILD] $text",false,"nginx");
    $date=date("H:i:s");
    echo "Reconfiguring.: $date [INIT]: Nginx service: $text\n";
    return true;
}






function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=PID_NUM();
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx (from FILE )service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service already stopped...\n";}
		GHOSTS_PID();
		return;
	}
	
	
	
	$pid=PID_NUM();
	$nginx=$unix->find_program("nginx");

	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service Shutdown pid $pid...\n";}
	shell_exec("$nginx -c /etc/nginx/nginx.conf -s quit >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service waiting pid:$pid $i/5...\n";}
		sleep(1);
        shell_exec("$nginx -c /etc/nginx/nginx.conf -s quit >/dev/null 2>&1");
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service shutdown - force - pid $pid...\n";}
    unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service waiting pid:$pid $i/5...\n";}
		sleep(1);
        unix_system_kill_force($pid);
	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service success...\n";}
		GHOSTS_PID();
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service failed...\n";}
	GHOSTS_PID();
}
function status():bool{
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime=PROGRESS_DIR."/nginx.status.acl";
	
	if(!$GLOBALS["FORCE"]){
		if($unix->file_time_min($pidTime)<5){return false;}
	}
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		$unix->ToSyslog("Already process running PID $pid since {$time}mn",basename(__FILE__));
		return false;
	}
	
	@file_put_contents($pidfile, getmypid());	
	@unlink($pidTime);
    if(!is_dir('/usr/share/artica-postfix/ressources/logs/web')){@mkdir('/usr/share/artica-postfix/ressources/logs/web',0755,true);}
	@file_put_contents($pidTime, time());
	@chmod($pidTime,0777);
	return true;
}




function awstats(){
	

}

function awstats_import_sql($servername){

}

function framework(){
	$unix=new unix();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx[".__LINE__."](".basename(__FILE__).") Framework...\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());	
	
	if(!is_file("/etc/artica-postfix/WORDPRESS_APPLIANCE")){
	$lighttpdbin=$unix->find_program("lighttpd");
		if(is_file($lighttpdbin)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx[".__LINE__."](".basename(__FILE__).") $lighttpdbin OK turn, to lighttpd...\n";}
			return;
		}

	}
	
	if(!is_file("/etc/php5/fpm/pool.d/framework.conf")){
		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php /usr/share/artica-postfix/exec.php-fpm.php --build");
	}
	
	if(!is_file("/etc/php5/fpm/pool.d/framework.conf")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx[".__LINE__."](".basename(__FILE__).") Unable to stat framework settings\n";}
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx[".__LINE__."](".basename(__FILE__).") building framework...\n";}
	$host=new nginx(47980);
	
	$host->set_proxy_disabled();
	$host->set_DocumentRoot("/usr/share/artica-postfix/framework");
	$host->set_framework();
	$host->build_proxy();

	$PID=PID_NUM();
	if(!$unix->process_exists($PID)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx[".__LINE__."](".basename(__FILE__).") not started, start it...\n";}
		start(true);
	}
	
	$kill=$unix->find_program("kill");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx[".__LINE__."](".basename(__FILE__).") reloading PID $PID\n";}
	shell_exec("$kill -HUP $PID >/dev/null 2>&1");
	
}

function test_sources(){
	$unix=new unix();
	
	if(!$GLOBALS["FORCE"]){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		if($GLOBALS["VERBOSE"]){echo "pidTime: $pidTime\n";} 
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		
		$pidTimeEx=$unix->file_time_min($pidTime);
		if($pidTimeEx<15){return;}
		@file_put_contents($pidfile, getmypid());
		@unlink($pidTime);
		@file_put_contents($pidTime, time());
	}
	
	$echo=$unix->find_program("echo");
	$nc=$unix->find_program("nc");
	
	$q=new mysql_squid_builder();
	
	if(!$q->FIELD_EXISTS("reverse_sources", "isSuccess")){
		$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `isSuccess` smallint(1) NOT NULL DEFAULT '1', ADD INDEX ( `isSuccess`)");
	}
	
	if(!$q->FIELD_EXISTS("reverse_sources", "isSuccesstxt")){
		$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `isSuccesstxt` TEXT");
	}

	if(!$q->FIELD_EXISTS("reverse_sources", "isSuccessTime")){
		$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `isSuccessTime` datetime");
	}	
	
	$sql="SELECT * FROM reverse_sources";
	$results=$q->QUERY_SQL($sql);
	
	
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$ipaddr=$ligne["ipaddr"];
		$ID=$ligne["ID"];
		$port=$ligne["port"];
		$IsSuccess=1;
		$linesrows=array();
		$cmdline="$echo -e -n \"GET / HTTP/1.1\\r\\n\" | $nc -q 2 -v  $ipaddr $port 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$ipaddr: $cmdline\n";}
		exec($cmdline,$linesrows);
		foreach ($linesrows as $b){
			if($GLOBALS["VERBOSE"]){echo "$ipaddr: $b\n";}
			if(preg_match("#failed#", $b)){$IsSuccess=0;}}
		reset($linesrows);
		$linesrowsText=mysql_escape_string2(base64_encode(serialize($linesrows)));
		$date=date("Y-m-d H:i:s");
		$q->QUERY_SQL("UPDATE reverse_sources SET isSuccess=$IsSuccess,isSuccesstxt='$linesrowsText',isSuccessTime='$date' WHERE ID=$ID");
		
	}
}

function purge_all_caches(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [PURGE]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	
	
	
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT directory FROM nginx_caches");
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$f[]=$ligne["directory"];
	}
	$f[]="/home/nginx/tmp";
	$rm=$unix->find_program("rm");
	foreach ($f as $value) {
		if(!is_dir($value)){continue;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Removing $value\n";}
		shell_exec("$rm -rf $value/*");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Removing $value OK\n";}
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Reloading service\n";}
	reload();
	
}




function purge_cache($ID){
	if(!is_numeric($ID)){return;}
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [PURGE]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	$q=new mysql_squid_builder();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT directory FROM nginx_caches WHERE ID='$ID'"));
	$directory=$ligne["directory"];
	if(!is_dir($directory)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [PURGE]: `$directory` no such directory\n";}
	}
	$rm=$unix->find_program("rm");
	shell_exec("$rm -rf \"$directory\"");
	@mkdir($directory,0755,true);
	reload();

	
}

function import_file(){
	$q=new mysql_squid_builder();
	$filename=PROGRESS_DIR."/nginx.import";
	if(!is_file($filename)){echo "$filename no such file\n";return;}
	
	$f=explode("\n",@file_get_contents($filename));
	
	$IpClass=new IP();
	foreach ($f as $index=>$line){
		if(trim($line)==null){continue;}
		if(strpos($line, ",")==0){continue;}
		$tr=explode(",",$line);
		if(count($tr)<2){continue;}
        $sourceserver_port  = 80;
		$sourceserver       = trim($tr[0]);
		$sitename           = trim($tr[1]);
        $sitename_port      = 80;
        if(!isset($tr[2])){$tr[2]=0;}
        if(!isset($tr[3])){$tr[3]=null;}
        $ssl                = $tr[2];
		$forceddomain       = $tr[3];
		if(!preg_match("#(.+?):([0-9]+)#", $sourceserver,$re)){
			if($ssl==1){$sourceserver_port=443;}
			if($ssl==0){$sourceserver_port=80;}
		}else{
			$sourceserver=trim($re[1]);
			$sourceserver_port=$re[2];
		}
		
		
		if(!preg_match("#(.+?):([0-9]+)#", $sitename,$re)){
			if($ssl==1){$sitename_port=443;}
			if($ssl==0){$sitename_port=80;}
		}else{
			$sitename=trim($re[1]);
			$sitename_port=$re[2];
		}	
		
		if($forceddomain<>null){$title_source=$forceddomain;}else{$title_source=$sourceserver;}
		echo "Importing $sitename ($sitename_port) -> $sourceserver ($sourceserver_port)\n";

		if($sitename==null){
			 echo "Local sitename is null\n";
			 continue;
		}
		
		if($sourceserver==null){
			echo "Source is null\n";
			continue;
		}	

		if(!$IpClass->isValid($sourceserver)){
			$tcp=gethostbyname($sourceserver);
			if(!$IpClass->isValid($tcp)){
				echo "Source $sourceserver cannot be resolved\n";
				continue;
			}	
		}
		
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT ID FROM reverse_sources WHERE ipaddr='$sourceserver' AND `port`='$sourceserver_port'"));
		$IDS=intval($ligne["ID"]);
		
		if($IDS==0){
			$sql="INSERT IGNORE INTO `reverse_sources` (`servername`,`ipaddr`,`port`,`ssl`,`enabled`,`forceddomain`)
			VALUES ('$title_source','$sourceserver','$sourceserver_port','$ssl',1,'$forceddomain')";
			$q->QUERY_SQL($sql);
			$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT ID FROM reverse_sources WHERE ipaddr='$sourceserver' AND `port`='$sourceserver_port'"));
			$IDS=intval($ligne["ID"]);
			
		}
		
		if($IDS==0){
			echo "Failed to add $sourceserver/$sourceserver_port/$forceddomain\n";
			continue;
		}

		
		// On attaque  le site web:
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT servername,cache_peer_id FROM reverse_www WHERE servername='$sitename'"));
		if(trim($ligne["servername"]<>null)){
			echo "$sitename already exists on cache ID : {$ligne["cache_peer_id"]}/$IDS\n";
			if($ligne["cache_peer_id"]<>$IDS){
				$q->QUERY_SQL("UPDATE reverse_www SET `cache_peer_id`=$IDS WHERE  servername='$sitename'");
			}
			continue;
		}
		
		$sql="INSERT IGNORE INTO `reverse_www` (`servername`,`cache_peer_id`,`port`,`ssl`) VALUES
		('$sitename','$IDS','$sitename_port','$ssl')";
	
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;continue;}
		
		
	}
	
	
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup $php5 ".__FILE__." --restart >/dev/null 2>&1 &");
	
	
	
}
function import_bulk(){
	$q=new mysql_squid_builder();
	$nginxSources=new nginx_sources();
	$nginx=new nginx();
	$filename=PROGRESS_DIR."/nginx.importbulk";
	if(!is_file($filename)){echo "$filename no such file\n";return;}	
	$CONF=unserialize(@file_get_contents($filename));
	
	
	
	if($CONF["RemoveOldImports"]==1){
		$results=$q->QUERY_SQL("SELECT ID FROM reverse_sources WHERE `Imported`=1");
		while ($ligne = mysqli_fetch_assoc($results)) {
			$nginxSources->DeleteSource($ligne["ID"]);
		}
		
		$results=$q->QUERY_SQL("SELECT servername FROM reverse_www WHERE `Imported`=1");
		while ($ligne = mysqli_fetch_assoc($results)) {
			$nginx->Delete_website($ligne["servername"],true);
		}		
		
		
	}
	
	$randomArray[1]="a";
	$randomArray[2]="b";
	$randomArray[3]="c";
	$randomArray[4]="d";
	$randomArray[5]="e";
	$randomArray[6]="f";
	$randomArray[7]="g";
	$randomArray[8]="h";
	$randomArray[9]="i";
	$randomArray[10]="j";
	$randomArray[11]="k";
	$randomArray[12]="l";
	$randomArray[13]="m";
	$randomArray[14]="n";
	$randomArray[15]="o";
	$randomArray[16]="p";
	$randomArray[17]="q";
	$randomArray[18]="r";
	$randomArray[19]="s";
	$randomArray[20]="t";
	$randomArray[21]="u";
	$randomArray[22]="v";
	$randomArray[23]="x";
	$randomArray[24]="y";
	$randomArray[25]="z";
	$RandomText=$CONF["RandomText"];
	$digitAdd=0;
	$webauth=null;
	$authentication_id=$CONF["authentication"];
	if(!is_numeric($authentication_id)){$authentication_id=0;}
	
	
	if($authentication_id>0){
		$AUTHENTICATOR["USE_AUTHENTICATOR"]=1;
		$AUTHENTICATOR["AUTHENTICATOR_RULEID"]=$authentication_id;
		$webauth=mysql_escape_string2(base64_encode(serialize($AUTHENTICATOR)));
	}
	
	
	
	if(preg_match("#\%sx([0-9]+)#", $RandomText,$re)){
		$digitAdd=intval($re[1]);
		$RandomText=str_replace("%sx{$re[1]}", "%s", $RandomText);
		
	}
	
	echo "Random: $RandomText\n";

	$f=explode("\n",$CONF["import"]);
	foreach ($f as $index=>$line){
		$line=trim(strtolower($line));
		if($line==null){continue;}
		if(preg_match("#^http.*?:\/#", $line)){
			$URZ=parse_url($line);
			if(!isset($URZ["host"])){echo "$line -> Unable to determine HOST, skipping\n";}
			$MAIN[$URZ["host"]]=$URZ["scheme"];
			continue;
			
		}
		$MAIN[$line]="http";
		
		
	}
	
	ksort($MAIN);
	$i=1;
	$Letter=1;
	$SUCCESS=0;
	$FAILED=0;
    foreach ($MAIN as $servername=>$proto){
		$LetterText=$randomArray[$Letter];
		$iText=$i;
		$ssl=0;
		if($digitAdd>0){$iText = sprintf("%1$0{$digitAdd}d", $i); }
		$SourceWeb=$RandomText;
		if($SourceWeb<>null){
			$SourceWeb=str_replace("%a", $LetterText, $SourceWeb);
			$SourceWeb=str_replace("%s", $iText, $SourceWeb);
			
		}else{
			$SourceWeb=$servername;
		}
		$sourceserver="$proto://$servername";
        $sourceserver_port  = 80;

		echo "$proto://$servername\n";
		if($proto=="http"){$sourceserver_port=80;}
		if($proto=="https"){$sourceserver_port=443;$ssl=1;}
		if(preg_match("#(.+?):([0-9]+)#", $servername,$re)){$sourceserver_port=$re[1];}
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT ID FROM reverse_sources WHERE ipaddr='$sourceserver' AND `port`='$sourceserver_port'"));
		$IDS=intval($ligne["ID"]);
		
		if($IDS==0){
			$sql="INSERT IGNORE INTO `reverse_sources` 
			(`servername`,`ipaddr`,`port`,`ssl`,`enabled`,`forceddomain`,`Imported`)
			VALUES ('$servername','$sourceserver','$sourceserver_port','$ssl',1,'$servername',1)";
			$q->QUERY_SQL($sql);
			$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT ID FROM reverse_sources WHERE ipaddr='$sourceserver' AND `port`='$sourceserver_port'"));
			$IDS=intval($ligne["ID"]);
				
		}
		
		if($IDS==0){
			echo "Failed to add $sourceserver/$sourceserver_port/$servername\n";
			$FAILED++;
			continue;
		}
		
		
		// On attaque  le site web:
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT servername,cache_peer_id FROM reverse_www WHERE servername='$SourceWeb'"));
		if(trim($ligne["servername"]<>null)){
			echo "$SourceWeb already exists on cache ID : {$ligne["cache_peer_id"]}/$IDS\n";
			if($ligne["cache_peer_id"]<>$IDS){
			$q->QUERY_SQL("UPDATE reverse_www SET `cache_peer_id`=$IDS WHERE  servername='$SourceWeb'");
			}
			$SUCCESS++;
			continue;
		}
		
		$sql="INSERT IGNORE INTO `reverse_www` (`servername`,`cache_peer_id`,`port`,`ssl`,`Imported`,`webauth`) VALUES
		('$SourceWeb','$IDS','$sourceserver_port','$ssl',1,'$webauth')";
		
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;$FAILED++;continue;}	
		$SUCCESS++;	
		
		
		$i++;
		$Letter++;
		if($Letter>25){$Letter=1;}
	}
	
	
	echo "$SUCCESS Imported sites, $FAILED failed\n";
	
}
function build_progress_restart($text,$pourc):bool{
	$filename=basename(__FILE__);
    $function = "-";
    $line     = 0;
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[0])){ $function=$trace[0]["function"]; $line=$trace[0]["line"]; }
		if(isset($trace[1])){ $function=$trace[1]["function"]; $line=$trace[1]["line"]; }
    }
	echo "[{$pourc}%] $filename $text ( $function Line $line)\n";
    $unix=new unix();
    return $unix->framework_progress($pourc,$text,"nginx.restart.progress");
}
function build_progress($text,$pourc){
	$filename=basename(__FILE__);
    $function   = "-";
    $line       = "-";
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
	
		if(isset($trace[0])){
			$function=$trace[0]["function"];
			$line=$trace[0]["line"];
		}
	
		if(isset($trace[1])){
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
		}
	
	
	
	}
	
	$unix=new unix();
    $unix->framework_progress($pourc,$text,"nginx.reconfigure.progress");
	echo "[{$pourc}%] $filename $text ( $function Line $line)\n";
	if($GLOBALS["OUTPUT"]){usleep(5000);}

}
