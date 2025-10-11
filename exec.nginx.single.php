<?php
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');

$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);

$GLOBALS["AS_ROOT"]=true;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["REPLIC_CONF"]=false;
$GLOBALS["NO_RELOAD"]=false;
$GLOBALS["NO_BUILD_MAIN"]=false;
$GLOBALS["OUTPUT"]=true;
$GLOBALS["NOCHECK"]=false;
$GLOBALS["NO_TEST_CONFIG"]=false;

$GLOBALS["pidStampReload"]="/etc/artica-postfix/pids/".basename(__FILE__).".Stamp.reload.time";
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--replic-conf#",implode(" ",$argv),$re)){$GLOBALS["REPLIC_CONF"]=true;}
if(preg_match("#--no-reload#",implode(" ",$argv),$re)){$GLOBALS["NO_RELOAD"]=true;}
if(preg_match("#--no-buildmain#",implode(" ",$argv),$re)){$GLOBALS["NO_BUILD_MAIN"]=true;}
if(preg_match("#--nocheck#",implode(" ",$argv),$re)){$GLOBALS["NOCHECK"]=true;$GLOBALS["NO_TEST_CONFIG"]=true;}


include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.nginx.inc');
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.webcopy.inc");
include_once(dirname(__FILE__).'/ressources/class.squid.reverse.inc');
include_once(dirname(__FILE__)."/ressources/class.nginx.reverse.http.inc");
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');
include_once(dirname(__FILE__).'/ressources/class.nginx.tpl.inc');
if(isset($argv[1])){
    if($argv[1]=="--modsecurity-clean"){modsecurity_clean();exit;}
    if($argv[1]=="--modsecurity"){modsecurity_compile($argv[2]);exit;}
    if($argv[1]=="--remove"){remove_website($argv[2]);CleanWebAndProxies();exit;}
    if($argv[1]=="--clean"){CleanWebAndProxies();exit;}
    if($argv[1]=="--clean-reboot"){CleanWebAndProxies(true);exit;}
    if($argv[1]=="--all"){reconfigure_all_sites();exit;}
    if($argv[1]=="--elastic"){ElasticSearch();exit;}
    if($argv[1]=="--http2"){if(http2_feature()){echo "YES!\n";}else{echo "INCOMPATIBLE!\n";}exit;}
    if($argv[1]=="--export"){export_single_site($argv[2]);exit;}
    if($argv[1]=="--clean-single"){CleanSingleSite($argv[2]);exit;}
    if($argv[1]=="--reconfigure-all-sites"){reconfigure_all_sites();exit;}
    if($argv[1]=="--server-cert"){create_server_certificate($argv[2]);exit;}
    if($argv[1]=="--client-cert"){create_client_certificate($argv[2]);exit;}
    if($argv[1]=="--modsec-rules"){modsecurity_rules();exit;}
    if($argv[1]=="--default-one"){exit;}
    if($argv[1]=="--scanconf"){ScanDirectoriesConf();exit;}
    if($argv[1]=="--debug-prepare"){debug_prepare($argv[2]);exit;}
    if($argv[1]=="--restore-template"){RestoreTemplate($argv[2],$argv[3]);exit;}


    compile_site($argv[1]);
}




function RestoreTemplate($templateid,$serviceid):bool{
    $tpl=new NginxTemplates($templateid);
    return $tpl->RestoreSite($serviceid);
}

function debug_prepare_progress($prc,$text,$siteid){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"nginx.debug.$siteid.progress");
    return true;

}
function debug_prepare($siteid):bool{
    $srcfile="/var/log/nginx/$siteid.debug";
    $target_file=PROGRESS_DIR."/$siteid.debug.gz";
    if(!is_file($srcfile)){
        debug_prepare_progress(110,"$srcfile {no_such_file}",$siteid);
        return false;
    }
    if(is_file($target_file)){@unlink($target_file);}
    $unix=new unix();
    debug_prepare_progress(50,"{compressing}",$siteid);
    if(!$unix->compress($srcfile,$target_file)){
        debug_prepare_progress(110,$GLOBALS["COMPRESSOR_ERROR"],$siteid);
        if(is_file($target_file)){@unlink($target_file);}
        return false;
    }
    debug_prepare_progress(90,"{compressing}",$siteid);
    @chmod($target_file,0755);
    @chown($target_file,"www-data");
    @chgrp($target_file,"www-data");
    debug_prepare_progress(100,"{compressing} {success}",$siteid);
    return true;
}
function build_progress($text,$pourc){
	$filename=basename(__FILE__);
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/nginx-single.progress";
	echo "[$pourc%] $filename: $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["OUTPUT"]){usleep(5000);}

}
function modsecurity_progress($prc,$text):bool{
    $unix=new unix();
    $unix->framework_progress($prc,$text,"modsecurity-compile.progress");
    return true;
}

function ScanDirectoriesConf(){
    $unix=new unix();
    $MAINARRAY=array();
    $BaseDir="/etc/nginx/reverse.d";
    $files=$unix->DirFiles($BaseDir,"[0-9]+\.conf");
    foreach ($files as $filename=>$none){
        $Conf="$BaseDir/$filename";
        if($GLOBALS["VERBOSE"]){echo "Scanning $Conf\n";}
        $s=explode("\n",@file_get_contents($Conf));
        foreach ($s as $line){
            if(preg_match("#DirectoryID:([0-9]+):([0-9]+)#",$line,$re)){
                $MAINARRAY[$re[1]][$re[2]]=true;
            }
        }

    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NGNIX_PATHS_LIST",serialize($MAINARRAY));
}

function modsecurity_rules():bool{

    modsecurity_progress(20,"{compiling}");
    $f=explode("\n",@file_get_contents("/etc/nginx/modsecurities/loader.conf"));
    $CONF=false;
    foreach ($f as $line){
        if(preg_match("# ARTICA-RULES\.conf#",$line)){$CONF=true;break;}
    }

    system("/usr/sbin/artica-phpfpm-service -waf-global -debug");

    modsecurity_progress(50,"{checking}");
    $nginx_service = new nginx_service();
    if (!$nginx_service->TestTheWholeConfig()) {
        modsecurity_progress("Building configuration {failed}", 110);
        echo basename(__FILE__)." L.".__LINE__."\n";
        $nginx_service->rolling_back();
        return false;
    }

    modsecurity_progress(80,"{reloading}");
    if(!$nginx_service->reload()){
        modsecurity_progress("{reloading} {failed}", 110);
        echo basename(__FILE__)." L.".__LINE__."\n";
        $nginx_service->rolling_back();
        return false;
    }
    modsecurity_progress(70,"{backuping}");
    $nginx_service->backup_config();
    modsecurity_progress(100,"{success}");
    return true;
}

function DebugMode($SERVICE_ID):string{
    $sockngx            = new socksngix($SERVICE_ID);
    $debug = intval($sockngx->GET_INFO("Debug"));
    $f[] = "# Debug mode = $debug";
    if ($debug == 1) {
        $f[] = "\terror_log /var/log/nginx/$SERVICE_ID.debug debug;";
    }else{
        if(is_file("/var/log/nginx/$SERVICE_ID.debug")){
            @unlink("/var/log/nginx/$SERVICE_ID.debug");
        }
    }
    return @implode("\n",$f);
}

function modsecurity_clean(){
    $NginxHTTPModSecurity       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHTTPModSecurity"));
    $EnableModSecurityIngix     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableModSecurityIngix"));
    $GlobalEnable=1;
    if($NginxHTTPModSecurity==0){$GlobalEnable=0;}
    if($EnableModSecurityIngix==0){$GlobalEnable=0;}
    $main_path="/etc/nginx/modsecurities";
    $dir_handle = @opendir($main_path);
    while ($file = readdir($dir_handle)) {
        if ($file == '.') {continue;}
        if ($file == '..') {continue;}
        $fpath = "$main_path/$file";
        echo "Scanning $fpath\n";


        if(!preg_match("#(crs|loader|whiterules|modsecurity|locations)-([0-9]+)\.conf#",$file,$re)){continue;}
        if(is_dir($fpath)){continue;}
        $ID=$re[1];
        $loader="$main_path/loader-$ID.conf";
        $locations="$main_path/locations-$ID.conf";
        $modsecurity="$main_path/modsecurity-$ID.conf";
        $whiterules="$main_path/whiterules-$ID.conf";
        $crs="$main_path/crs-$ID.conf";
        if($GlobalEnable==0){
            @unlink($fpath);
            if(is_file($loader)){@unlink($loader);}
            if(is_file($locations)){@unlink($locations);}
            if(is_file($modsecurity)){@unlink($modsecurity);}
            if(is_file($whiterules)){@unlink($whiterules);}
            if(is_file($crs)){@unlink($crs);}
            continue;
        }
        $sockngix=new socksngix($ID);
        $EnableModSecurity=intval($sockngix->GET_INFO("EnableModSecurity"));
        if($EnableModSecurity==1){continue;}
        @unlink($fpath);
        if(is_file($loader)){@unlink($loader);}
        if(is_file($locations)){@unlink($locations);}
        if(is_file($modsecurity)){@unlink($modsecurity);}
        if(is_file($whiterules)){@unlink($whiterules);}
        if(is_file($crs)){@unlink($crs);}
    }
}
function modsecurity_compile($serviceid,$prc=0):bool{
    $unix=new unix();
    $reload=false;


    $nginx_service=new nginx_service();

    if($prc>0){
        modsecurity_progress($prc,"{building} Web Firewall...");
    }else {
        modsecurity_progress(10, "{building}...");
    }
    if($serviceid>0) {
        if($prc>0){
            modsecurity_progress($prc,"{building} Web Firewall ID:$serviceid...");
        }else {
            modsecurity_progress(80, "{building} ID:$serviceid...");
        }

        if($prc>0){
            modsecurity_progress($prc,"{building} Web Firewall ID:$serviceid OK...");
        }else {
            modsecurity_progress(80, "{building} Web Firewall ID:$serviceid OK...");
        }
        $mod_security=new mod_security($serviceid);
        $mod_security->build_nginx_configuration($serviceid);
        $reload = true;
    }else{
        $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
        $results=$q->QUERY_SQL("SELECT ID,servicename FROM nginx_services WHERE enabled=1");
        $c=10;
        foreach ($results as $index=>$ligne){
            $serviceid  =   $ligne["ID"];
            $servicename=$ligne["servicename"];
            $c++;
            if($c>90){$c=90;}
            modsecurity_progress($c,"{building} ID:$index: $serviceid $servicename...");
            $mod_security = new mod_security($serviceid);
            $mod_security->build_nginx_configuration($serviceid);
            $reload=true;
        }
    }
    if(isset($GLOBALS["NO_CHECK_MODSECURITY"])){
       return true;
    }


    if($reload) {
        if(!$GLOBALS["NO_RELOAD"]){
            if(!$nginx_service->TestTheWholeConfig()){
                echo basename(__FILE__)." L.".__LINE__."\n";
                $nginx_service->rolling_back();
                modsecurity_progress(90, "{failed}...");
                return true;
            }

            modsecurity_progress(90, "{reloading}...");
            $nginx_service->reload();
        }
    }
    modsecurity_progress(100,"{success}...");
    return true;
}
function _rec($text){
    $nginx_service=new nginx_service();
    $nginx_service->_out($text);
}

function reconfigure_all_sites_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"nginx.reconfigure.all.progress");

}

function reconfigure_error_pages_out($text):bool{
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("web-error-page", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}



function reconfigure_all_sites():bool{
    $GLOBALS["NOCHECK"]=true;
    $GLOBALS["NO_RELOAD"]=true;

    $unix       = new unix();
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
    $zpaths[]   = "/etc/nginx/root-directives";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NginxEmergency",0);
    reconfigure_all_sites_progress("Cleaning",10);

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
        reconfigure_all_sites_progress("building $servicename - $ID",$prc);
        compile_site($ID);
    }

    $nginx_service=new nginx_service();
    if(!$nginx_service->TestTheWholeConfig()){
        reconfigure_all_sites_progress("{failed}...",110);
        echo basename(__FILE__)." L.".__LINE__."\n";
        $nginx_service->rolling_back();
        $nginx_service->reload();
        return false;
    }

    reconfigure_all_sites_progress("{reloading} {global_settings}...",95);
    $nginx_service->reload();
    reconfigure_all_sites_progress("{success}...",100);
    $nginx_service->backup_config();
    return true;
}


function mainconfig_file($ID):string{
    $q              = new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT type,zorder FROM nginx_services WHERE ID=$ID");
    if(!isset($ligne["type"])){return "";}
    $Type=intval($ligne["type"]);
    $zorder=intval($ligne["zorder"]);

    $mainpath="/etc/nginx/sites-enabled/$zorder-$ID.conf";
    if($Type==5){$mainpath="/etc/nginx/stream.d/$zorder-$ID.conf";}
    if($Type==2){$mainpath="/etc/nginx/reverse.d/$zorder-$ID.conf";}
    return $mainpath;
}

function compile_site($ID,$norestart=false):bool{
    system("/usr/sbin/artica-phpfpm-service -nginx-single $ID -debug");
    return true;
}
function compile_site_old($ID,$norestart=false):bool{
    if($ID==0){build_progress("{failed2} Wrong ID $ID",110);return false;}
    @file_put_contents("/etc/artica-postfix/TESTNGINX_FAILED",0);
    if($norestart){$GLOBALS["NO_RELOAD"]=true;}

	$unix           = new unix();
	$ID             = intval($ID);
    $chown          = $unix->find_program("chown");
    $goodconf       = null;
    $SERVICE_ID     = $ID;
    $q              = new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $php5           = $unix->LOCATE_PHP5_BIN();
    $nohup          = $unix->find_program("nohup");

    if(!is_dir("/etc/nginx/root-directives")){@mkdir("/etc/nginx/root-directives",0755,true);}
    if(!is_dir("/etc/nginx/ElasticSearch")){@mkdir("/etc/nginx/ElasticSearch",0755,true);}
    if(!is_file("/etc/nginx/ElasticSearch/ElasticSearch.conf")){@touch("/etc/nginx/ElasticSearch/ElasticSearch.conf"); }

    $NGINX_SP663=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NGINX_SP663"));
    if($NGINX_SP663==0){
        shell_exec("$php5 /usr/share/artica-postfix/exec.nginx.php --restart-build  >/dev/null 2>&1 &");
    }



    if(!isset($GLOBALS["NGINX_FOLDERS"])) {
        $zpaths[] = "/var/lib/nginx/body";
        $zpaths[] = "/var/log/apache2";
        $zpaths[] = "/etc/nginx/sites-enabled";
        $zpaths[] = "/etc/nginx/stream.d";
        $zpaths[] = "/etc/nginx/upstream.d";
        $zpaths[] = "/var/lib/nginx";
        $zpaths[] = "/etc/nginx/root-directives";
        $zpaths[] = "/etc/nginx/ElasticSearch";

        foreach ($zpaths as $directory) {
            if (!is_dir($directory)) {
                @mkdir($directory, 0755, true);
            }
            system("$chown -R www-data:www-data $directory");
            @chown($directory, "www-data");
            @chgrp($directory, "www-data");

        }
        $GLOBALS["NGINX_FOLDERS"]=true;
    }

    $Types[1]="PHP web site";
    $Types[2]="{reverse_proxy}";
    $Types[3]="{HOTSPOT_WWW}";
    $Types[4]="{ARTICA_ADM}";
    $Types[5]="{TCP_FORWARD}";
    $Types[6]="{WEBFILTERING_ERROR_SERVICE}";
    $Types[7]="{DOH_WEB_SERVICE}";
    $Types[8]="{PROXY_PAC_SERVICE}";
    $Types[9]="{WEB_HTML_SERVICE}";
    //$Types[10]="{IT_charter}";
    $Types[11]="{APP_APT_MIRROR_WEB}";
    $Types[13]="ADFS 3.0";
	

    if(!$q->FIELD_EXISTS("nginx_services", "WebContent")){
        $q->QUERY_SQL("ALTER TABLE nginx_services ADD WebContent TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("nginx_services", "WebContentSize")){
        $q->QUERY_SQL("ALTER TABLE nginx_services ADD WebContentSize INTEGER DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("nginx_services", "WebDirectory")){
        $q->QUERY_SQL("ALTER TABLE nginx_services ADD WebDirectory TEXT NULL");
    }



	$ligne=$q->mysqli_fetch_array("SELECT * FROM nginx_services WHERE ID=$ID");
	$servicename=$ligne["servicename"];
	$Type=intval($ligne["type"]);
	$zorder=intval($ligne["zorder"]);
	$enabled=intval($ligne["enabled"]);
	$goodconf_old=$ligne["goodconf"];
	$goodconftime=$ligne["goodconftime"];
	$mainpath=mainconfig_file($ID);


    if(is_file("/etc/nginx/sites-enabled/-1.conf")) {
        @unlink("/etc/nginx/sites-enabled/-1.conf");
    }
	$action="reload";
	if(!is_file($mainpath)){$action="restart";}
    $UfdbUseInternalService=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalService");
    if($Type==6) {
        if ($UfdbUseInternalService == 0) {
            $enabled=0;
        }
    }

	build_progress("type: {$Types[$ligne["type"]]}",50);
	echo "$servicename Type........: $Type\n";
	echo "$servicename Order.......: $zorder\n";
	echo "$servicename Enabled.....: $enabled\n";
	echo "$servicename goodconf....: ".strlen($goodconf_old)." Bytes\n";
	echo "$servicename goodconftime: $goodconftime\n";
    echo "$servicename Config file.: $mainpath\n";

    $WebCopy=$q->mysqli_fetch_array("SELECT ID FROM httrack_sites WHERE serviceid=$ID");
    $WebCopyID=intval($WebCopy["ID"]);
    if($WebCopyID>0){
        shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.httptrack.php --single $ID >/dev/null 2>&1 &");

    }

	
	if($enabled==0){
	    $action="reload";
        echo "$servicename * * * * * * DISABLED * * * * * *\n";
        if(remove_website($SERVICE_ID)){ $GLOBALS["NO_RELOAD"]=false; }

        if(!$GLOBALS["NO_RELOAD"]) {
            build_progress("{reloading} NO_RELOAD=False",99);
            system("/etc/init.d/nginx $action");
            build_progress("{success} {disabled}",100);
        }
        ScanDirectoriesConf();
		return false;
	}

    echo "Building configuration using Type: $Type\n";
	switch ($Type) {
		case 1:$goodconf=base64_encode(build_local($ligne,$SERVICE_ID));break;
		case 2:$goodconf=base64_encode(build_httpreverse($ligne,$SERVICE_ID));break;
		case 3:$goodconf=base64_encode(build_local($ligne,$SERVICE_ID));break;
		case 4:$goodconf=base64_encode(build_local($ligne,$SERVICE_ID));break;
		case 5:$goodconf=base64_encode(build_stream($ligne,$SERVICE_ID));break;
		case 6:$goodconf=base64_encode(build_local($ligne,$SERVICE_ID));break;
        case 7:$goodconf=base64_encode(build_local($ligne,$SERVICE_ID));break;
        case 8:$goodconf=base64_encode(build_local($ligne,$SERVICE_ID));break;
        case 9:$goodconf=base64_encode(build_local($ligne,$SERVICE_ID));break;
        case 10:$goodconf=base64_encode(build_local($ligne,$SERVICE_ID));break;
        case 11:$goodconf=base64_encode(build_local($ligne,$SERVICE_ID));break;
        case 12:
                $confTMP=build_local($ligne,$SERVICE_ID);
                $goodconf=base64_encode($confTMP);
            break;
        case 13:$goodconf=base64_encode(build_adfs($ligne,$SERVICE_ID));break;


		default:break;
	}



	if($goodconf==null){
        build_progress("{failed}",110);
        return false;
    }
    if(!is_dir("/home/artica/nginx/compiled")){
        @mkdir("/home/artica/nginx/compiled",0644,true);
    }
    @file_put_contents("/home/artica/nginx/compiled/$SERVICE_ID.conf",base64_decode($goodconf));

    build_progress("Web Firewall",86);
    $GLOBALS["NO_CHECK_MODSECURITY"]=true;
    modsecurity_compile($SERVICE_ID,87);
    build_progress("{cleaning}...",88);
    CleanWebAndProxies();
    $nginx_service=new nginx_service();
    $q->QUERY_SQL("UPDATE nginx_services SET goodconftime=".time(). ",goodconf='$goodconf',badconf='' WHERE ID=$ID");
    if(!$q->ok){
        echo "******************* SQL ERROR *****************\n";
        echo $q->mysql_error."\n";
    }
    if(!$GLOBALS["NOCHECK"]) {
        echo "Testing the configuration....\n";
        build_progress("{testing_the_configuration} #$SERVICE_ID", 89);
        if (!$nginx_service->TestTheWholeConfig()) {
            build_progress("{testing_the_configuration} {failed} ERR.".__LINE__, 110);
            echo basename(__FILE__)." L.".__LINE__."\n";
            $nginx_service->rolling_back();
            ScanDirectoriesConf();
            return false;
        }
    }
    if(!$GLOBALS["NO_RELOAD"]) {
        build_progress("{reloading_service} #$SERVICE_ID...", 90);
        system("/etc/init.d/nginx $action");
    }

	build_progress("{success}",100);
	if($Type==6){
            $SquidGuardWebWebServiceID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebWebServiceID"));
            if($SquidGuardWebWebServiceID==$SERVICE_ID) {
                $php5 = $unix->LOCATE_PHP5_BIN();
                $cmd = trim("$php5 /usr/share/artica-postfix/exec.ufdbguard.rules.php");
                system($cmd);
            }
    }

    if(!$GLOBALS["NOCHECK"]) {
        $nginx_service->backup_config();
    }
    ScanDirectoriesConf();
	return true;
}

function buildservernames($ligne):string{
    $ID=$ligne["ID"];
    $Zhosts=explode("||",$ligne["hosts"]);
    $Zhosts2=array();
    foreach ($Zhosts as $servername){
        $servername=trim($servername);
        if($servername==null){continue;}
        if($servername=="*"){$servername="~^.*$";}
        if(preg_match("#^(.+?)>(.+)#",$servername,$re)){continue;}
        $Zhosts2[]=$servername;
    }
    $servicesnames=trim(@implode(" ", $Zhosts2));
    if($servicesnames==null){$servicesnames="_";}
    $f[]="\tserver_name $servicesnames;";
    $f[]=DebugMode($ID);
    return @implode("\n",$f);
}
function buildRedirectsServerNames($ligne):array{
    $Zhosts=explode("||",$ligne["hosts"]);
    $Zhosts2=array();
    foreach ($Zhosts as $servername){
        $servername=trim($servername);
        if($servername==null){continue;}
        if(!preg_match("#^(.+?)>(.+)#",$servername,$re)){continue;}
        $servername=$re[1];
        $redirects=$re[2];
        if($servername=="*"){$servername="~^.*$";}
        $Zhosts2[$servername]=$redirects;
    }
    return $Zhosts2;
}

function security_of_paths():string{

    $hack[]="Administrator/index";
    $hack[]="Joomla/administrator";
    $hack[]="Cms/administrator";
    $hack[]="joomla/administrator";
    $hack[]="Msd/index";
    $hack[]="MySqlDumper/index";
    $hack[]="Msd124stable/index";
    $hack[]="Msd1244/index";
    $hack[]="Mysqldumper/index";
    $hack[]="MySQLDumper/index";
    $hack[]="Mysql/index";
    $hack[]="mysql/index";
    $hack[]="Sql/index";
    $hack[]="sql/index";
    $hack[]="Cgi-bin/php";
    $hack[]="Cgi-bin/php5";
    $hack[]="Phpmyadmin/index";
    $hack[]="PhpMyAdmin/index";
    $hack[]="Myadmin/index";
    $hack[]="cgi-bin/.*?\.cgi";
    $f_rewrites=array();
    foreach ($hack as $path){
        $f_rewrites[] = "\tif (\$uri ~ $path){ return 407;}";


    }

    return @implode("\n",$f_rewrites);
}

function build_adfs($ligne,$SERVICE_ID):string{
    $DEFAULT_SERVER     = false;
    $sockngx            = new socksngix($SERVICE_ID);
    $zorder             = intval($ligne["zorder"]);
    $isDefault          = intval($ligne["isDefault"]);
    $mainpath           ="/etc/nginx/reverse.d/$zorder-$SERVICE_ID.conf";

    $sockngx->SET_INFO("UseSSL",1);
    $nginx_proxy_path=new nginx_proxy_path($SERVICE_ID,0);
    $nginx_proxy_path->build_backends();
    $nginx_service=new nginx_service();
    $server_name=$nginx_service->buildservernames($ligne);
    $HostHeader=$nginx_proxy_path->HostHeader();
    $AdfsForceRedirect=$sockngx->GET_INFO("AdfsForceRedirect");

    if (strlen($HostHeader)<3){
        $HostHeader="\$host";
    }


    if($isDefault==1){$DEFAULT_SERVER=true;}
    $XMSProxyHeader=$sockngx->GET_INFO("XMSProxyHeader");
    if(strlen($XMSProxyHeader)<3){
        $XMSProxyHeader="\$server_name";
    }


    $ssl_directives=ssl_directives($SERVICE_ID);
    $listen_port_directive=listen_port_directive($SERVICE_ID, true,$DEFAULT_SERVER);

    $f[]="server {";
    $f[]=proxy_upstream_name($SERVICE_ID);
    $f[]=http_useragents_directive($SERVICE_ID);
    $f[] = build_http_generic_harden($SERVICE_ID);
    $f[]=$listen_port_directive;
    $f[]=$server_name;
    $f[]=$ssl_directives;
    $pprxy[]="\t\tproxy_ssl_server_name on;";
    $pprxy[]="\t\tproxy_ssl_name $HostHeader;";
    $pprxy[]="\t\tproxy_set_header Host $HostHeader;";
    $pprxy[]="\t\tproxy_set_header X-Real-IP \$remote_addr;";
    $pprxy[]="\t\tproxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;";
    $pprxy[]="\t\tproxy_set_header X-MS-Proxy $XMSProxyHeader;";
    $pprxy[]="\t\tproxy_ssl_session_reuse off;";
    $pprxy[]="\t\tproxy_buffering off;";
    $pprxy[]="\t\tproxy_request_buffering off;";
    $pprxy[]="\t\tproxy_http_version 1.1;";
    if($AdfsForceRedirect==1) {
        $pprxy[] = "\t\tproxy_redirect https://$HostHeader https://$XMSProxyHeader;";
    }else{
        $pprxy[] = "\t\tproxy_redirect off;";
    }
    $pprxy[]="\t\tproxy_pass https://backends$SERVICE_ID;";
    $pprxystr=@implode("\n",$pprxy);
    $f[]="\tlocation /federationmetadata {";
    $f[]=$pprxystr;
    $f[]="\t}";
    $f[]="";
    $f[]="location /adfs {";
    $f[]=$pprxystr;
    $f[]="\t}";
    $f[]="";
    $f[]="\tlocation / {";
    $f[]="\treturn 302 /adfs/ls/idpinitiatedsignon;";
    $f[]="\t}";
    $f[]="}";
    echo "Saving $mainpath\n";
    @file_put_contents($mainpath,@implode("\n",$f));
    return @implode("\n",$f);
}

function build_local($ligne,$SERVICE_ID):string{
	$unix=new unix();
    $unix->framework_exec("exec.nginx.php --dirs");
    $f=array();$DEFAULT_SERVER=false;
    $WebCopyID=0;
	$array_path[1]="/home/www/$SERVICE_ID";
	$array_path[2]="/home/www/$SERVICE_ID";
	$array_path[3]="/usr/share/artica-postfix";
	$array_path[4]="/usr/share/artica-postfix";
	$array_path[6]="/usr/share/artica-postfix";
    $array_path[7]="/home/www/$SERVICE_ID";
    $array_path[8]="/home/www/$SERVICE_ID";
    $array_path[10]="/usr/share/artica-postfix";
    $array_path[11]="/usr/share/artica-postfix";

    $sockngx            = new socksngix($SERVICE_ID);
    $templs             = new nginx_templates($SERVICE_ID);
	$servicename        = $ligne["servicename"];
	$Type               = $ligne["type"];
	$zorder             = intval($ligne["zorder"]);
	$isDefault          = intval($ligne["isDefault"]);
    $DenyAccess         = intval($sockngx->GET_INFO("DenyAccess"));
    $Templates          = $templs->build();

    if($isDefault==1){$DEFAULT_SERVER=true;}

    if($Type==12){

        $WebCopyID=intval($sockngx->GET_INFO("WebCopyID"));
        if($WebCopyID==0){
            echo "No ID For WebCopy\n";
            remove_website($SERVICE_ID);
            return "";
        }

        $q      = new lib_sqlite("/home/artica/SQLITE/nginx.db");
        $WebCopyLigne=$q->mysqli_fetch_array("SELECT ID FROM httrack_sites WHERE ID=$WebCopyID");
        if(intval($WebCopyLigne["ID"])==0){
            echo "ID For WebCopy is removed from table\n";
            remove_website($SERVICE_ID);
            return "";
        }
        $WebCp=new webcopy($WebCopyID);
        $WebCopyDir=$WebCp->WebCopyDir();
        if($WebCopyDir==null){
            echo "ID For WebCopy directory is unknown\n";
            remove_website($SERVICE_ID);
            return "";
        }

        $array_path[12]=$WebCopyDir;

    }


	if($Type==9){

        if(trim($ligne["WebDirectory"])<>null) {
            $array_path[9]=$ligne["WebDirectory"];
        }else{
            $array_path[9]="/home/www/$SERVICE_ID";

        }

        echo "Target directory [$array_path[9]]\n";
        $WebContentSize=intval($ligne["WebContentSize"]);
        if($WebContentSize==0){echo "No content as been defined\n";return false;}
        $unzip=$unix->find_program("unzip");
        $chown=$unix->find_program("chown");
        $chmod=$unix->find_program("chmod");
        $find=$unix->find_program("find");
        $rm=$unix->find_program("rm");

        if(!is_file("$unzip")){echo "No unzip binary\n";return false;}
        $TargetDirectory=$array_path[9];
        $tempzip=$unix->FILE_TEMP().".zip";
        @file_put_contents($tempzip,base64_decode($ligne["WebContent"]));
        echo "Uncompress...\n";
        if(!is_dir($TargetDirectory)){@mkdir($TargetDirectory,0755,true);}
        system("$rm -rf $TargetDirectory/*");
        system("$unzip $tempzip -d $TargetDirectory/");
        @unlink($tempzip);
        echo "Apply permissions...\n";
        shell_exec("$chown -R www-data $TargetDirectory");
        shell_exec("$chmod -R 0644 -R $TargetDirectory");
        shell_exec("$find $TargetDirectory -type d -print0 |xargs -0 $chmod 0755");
	}



	$mainpath="/etc/nginx/sites-enabled/$zorder-$SERVICE_ID.conf";
	$root_path=$array_path[$Type];
	echo "build_local: $servicename Root........: $array_path[$Type]\n";
	echo "build_local: $servicename path........: $mainpath\n";
    echo "build_local: $servicename Default serv: $isDefault\n";
    echo "build_local: $servicename Hosts: {$ligne["hosts"]}\n";
	
	$Zhosts=explode("||",$ligne["hosts"]);
    $Zhosts2=array();
    $ZRedirects=array();
	foreach ($Zhosts as $servername){
        $servername=trim($servername);
        if($servername==null){continue;}
        if($servername=="*"){$DEFAULT_SERVER=true;continue;}
        if(preg_match("#^(.+?)>(.+)#",$servername,$re)){
            $ZRedirects[$re[1]]=$re[2];
            continue;

        }
        $Zhosts2[]=$servername;
    }


	$servicesnames=trim(@implode(" ", $Zhosts2));
	if($servicesnames==null){$servicesnames="_";}
	echo "$servicename Servers: $servicesnames\n";
	
	switch ($Type) {
        case 10:$index_file="itcharter.php";break;
		case 6:$index_file="ufdbguard.php";break;
        case 8:$index_file="proxy.pac";break;
        case 9:$index_file="index.htm index.html";break;
        case 12:$index_file="index.html";break;
		default:$index_file="index.php";break;
	}
	
	$ssl_directives=ssl_directives($SERVICE_ID);
    if(!isset($GLOBALS["CERTIFICATE_ENABLED"])){$GLOBALS["CERTIFICATE_ENABLED"]=false;}
	$CERTIFICATE_ENABLED=boolval($GLOBALS["CERTIFICATE_ENABLED"]);

    if($Type==7) {
        $f[] = "upstream dns-backend {";
        $f[] = "\tserver 127.0.0.1:9053;";
        $f[] = "}";
        $f[] = "";
        $f[] = "";
    }


    $pagespeed=intval($sockngx->GET_INFO("pagespeed"));
    $Redirect80To443=intval($sockngx->GET_INFO("Redirect80To443"));
    $global_redirect_uri=global_redirect_uri($SERVICE_ID);
    $listen_port_directive=listen_port_directive($SERVICE_ID, $CERTIFICATE_ENABLED,$DEFAULT_SERVER);

    if(count($ZRedirects)>0){
        foreach ($ZRedirects as $domain=>$redirects) {
            $f[] = "server {";
            $f[] = "\tserver_name $domain;";
            $f[] = $listen_port_directive;
            $f[] = "\treturn 302 \$scheme://$redirects\$request_uri;";
            $f[] = "}";
        }
    }


//---------------- LISTEN SECTION -----------------------------------
   if($CERTIFICATE_ENABLED){ $f[]="# Certificated enabled OK";}
    if($Redirect80To443==1){
        if($CERTIFICATE_ENABLED) {
            $f[] = "server {";
            $f[] = "\tserver_name $servicesnames;";
            $f[] = listen_port_Redirect80To443($SERVICE_ID,$DEFAULT_SERVER);
            $f[] = $global_redirect_uri;
            $f[] = $Templates;
            if($DenyAccess==1){$f[] = "\tdeny all;";}
            $f[] = "return 301 https://\$host\$request_uri;";
            $f[] = "}";
            $f[] = "";
        }
    }
    $f[]="#\t L.".__LINE__;
	$f[]="server {";
    $f[]=proxy_upstream_name($SERVICE_ID);
    $f[]="\tserver_name $servicesnames;";
    $f[]=DebugMode($SERVICE_ID);


	
	// ------------------------------------ PORTS ------------------------------------
	$f[] = $listen_port_directive;
    $f[] = http_useragents_directive($SERVICE_ID);
    $f[] = build_http_generic_harden($SERVICE_ID);
	$f[] = headers_security($SERVICE_ID);
	$f[] = $ssl_directives;
	$f[] = $global_redirect_uri;
	$f[] = gzip_directives($SERVICE_ID);
    if($pagespeed==1){
        $f[]=x_nginx_pagespeed($SERVICE_ID);
    }
	// ------------------------------------------------------------------------
	$f[] = ngx_stream_access_module($SERVICE_ID);
	$f[] = $Templates;
	$f[] = "\tindex $index_file;";
	$f[] = "\troot $root_path;";
    if($DenyAccess==1){$f[] = "\tdeny all;";}
	$f[]="";
    $f[]= cache_objects($SERVICE_ID);
    $f[]= default_paths($SERVICE_ID);
    $f[]= http_stub_status($SERVICE_ID);

	switch ($Type) {
		case 4:$f[]=proxy_path_artica();break;
		case 6:$f[]=weberror_path_artica();break;
		case 3:$f[]=hotspot_path_artica();break;
        case 7:$f[]=doh_path_artica();break;
        case 8:$f[]=_proxy_pac();break;
        case 9:$f[]=static_path($SERVICE_ID);break;
        case 10:$f[]=itcharter_path_artica();break;
        case 11:$f[]=debian_mirror_path();break;
        case 12:$f[]=static_path($SERVICE_ID,$Type,$WebCopyID);break;
		default:$f[]=standard_path($SERVICE_ID);break;
	}


	$f[]="}";
	
	echo "build_local: $servicename path........: $mainpath\n";
    $finaldata=@implode("\n",$f);
	@file_put_contents($mainpath, $finaldata);
	return $finaldata;
}



function http_stub_status($SERVICE_ID):string{

    if(!http_stub_status_feature()){
        return "# http_stub_status_module not compiled\n";
    }
    $f[]="# http_stub_status_module: $SERVICE_ID";
    $f[]="\tlocation = /basic_status_$SERVICE_ID {";
    if(http_modsecurity_status()){
        $f[]="\t\tmodsecurity off;";
    }
    $f[]="\t\taccess_log   off;";
    $f[]="\t\tallow 127.0.0.1;";
    $f[]="\t\tdeny all;";
    $f[]="\t\tstub_status;";
    $f[]="}";
    return @implode("\n",$f);

}

function build_stream($ligne,$SERVICE_ID){
	$unix=new unix();
	$servicename=$ligne["servicename"];
	$outgoingaddr=null;
	$zorder=intval($ligne["zorder"]);
	$mainpath="/etc/nginx/stream.d/$zorder-$SERVICE_ID.conf";
	echo "build_stream: $servicename path........: $mainpath\n";
	$q=new lib_sqlite("/home/artica/SQLITE/nginx.db");


	
//---------------- UPSTREAM SECTION -----------------------------------	

	$upstream_file="/etc/nginx/upstream.d/$zorder-$SERVICE_ID.conf";
	$UPS[]="upstream backends{$SERVICE_ID} {";
    $UPS[]="\thash \$remote_addr consistent;";
    $results=$q->QUERY_SQL("SELECT * FROM backends WHERE serviceid='$SERVICE_ID'");
    foreach ($results as $md5=>$ligne){
    	$hostname=$ligne["hostname"];
    	//$ID=intval($ligne["ID"]);
    	$port=$ligne["port"];
    	$f[]="# Backend: $hostname:$port";
    	$UPS[]="\tserver $hostname:$port;";
    
    }
	$UPS[]="}";
	@file_put_contents($upstream_file, @implode("\n", $UPS));
	$sockngx=new socksngix($SERVICE_ID);
	$limit_conn=intval($sockngx->GET_INFO("limit_conn"));
	$proxy_download_rate=intval($sockngx->GET_INFO("proxy_download_rate"));
	$proxy_upload_rate=intval($sockngx->GET_INFO("proxy_upload_rate"));
	$proxy_bind=$sockngx->GET_INFO("proxy_bind");
	if($proxy_bind<>null){$outgoingaddr=$unix->InterfaceToIPv4($proxy_bind);}

	$CERTIFICATE_ENABLED=$GLOBALS["CERTIFICATE_ENABLED"];


	
	$f[]="server {";
    $f[]=proxy_upstream_name($SERVICE_ID);
    $f[]=listen_port_directive($SERVICE_ID, $CERTIFICATE_ENABLED);
    $f[]=http_useragents_directive($SERVICE_ID);
    $f[] = build_http_generic_harden($SERVICE_ID);
	if($limit_conn>0){
		$f[]="\tlimit_conn\taddr {$limit_conn};";
		$f[]="\tlimit_conn_log_level error;";
	}
	if($proxy_download_rate>0){$f[]="\tproxy_download_rate {$proxy_download_rate}k;";}
	if($proxy_upload_rate>0){$f[]="\tproxy_upload_rate {$proxy_upload_rate}k;";}
	if($outgoingaddr<>null){$f[]="\tproxy_bind $outgoingaddr;";}
	
	$f[]=ngx_stream_access_module($SERVICE_ID);

	$f[]="\tproxy_pass backends{$SERVICE_ID};";
	$f[]="\taccess_log  /var/log/nginx/access.log awc_log;";
	$f[]="\terror_log   /var/log/nginx/error.log;";
	$f[]="}";
	@file_put_contents($mainpath, @implode("\n", $f));
	return @implode("\n", $f);
	
}
function listen_port_Redirect80To443($SERVICE_ID,$DEFAULT_SERVER){
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $unix=new unix();
    $results=$q->QUERY_SQL("SELECT * FROM stream_ports WHERE serviceid='$SERVICE_ID'");
    if(!$q->ok){$f[]="# MySQL Error! on line ".__LINE__;}

    foreach ($results as $index=>$ligne) {
        $opts = array();
        if ($DEFAULT_SERVER) {
            $opts[] = "default_server";
        }
        $ipaddr = null;
        $finalopt = ";";
        $interface = trim($ligne["interface"]);
        $port = intval($ligne["port"]);

        if ($port == 0) {
            continue;
        }
        if ($port == 443) {
            continue;
        }
        if ($interface <> null) {
            $ipaddr = $unix->InterfaceToIPv4($interface);
            $f[] = "# interface is not null $interface:$ipaddr on port $port [".__LINE__."]";
        }
        $f[] = "# $interface ($ipaddr) on port $port  [".__LINE__."]";
        $options = unserialize(base64_decode($ligne["options"]));
        if (!isset($options["udp"])) {$options["udp"] = 0;}
        if (!isset($options["http2"])) {$options["http2"] = 0;}
        if (!isset($options["spdy"])) {$options["spdy"] = 0;}
        if (!isset($options["ssl"])) {$options["ssl"] = 0;}

        if ($ipaddr == null) {$ipaddr = "*";}
        $udp = intval($options["udp"]);
        $ssl    = intval($options["ssl"]);


        if ($udp == 1) {continue;}
        if ($options["spdy"] == 1) {$options[] = "spdy";}
        if (count($opts) > 0) {$finalopt = " " . @implode(" ", $opts) . ";";}
        $f[] = "   listen $ipaddr:$port{$finalopt}";
    }

    return @implode("\n", $f);
}

function http2_feature():bool{
    if(isset($GLOBALS["http2_feature"])){return $GLOBALS["http2_feature"];}
    $unix=new unix();
    $nginx=$unix->find_program("nginx");
    exec("$nginx -V 2>&1",$results);
    foreach ($results as $line){
        if(!preg_match("#configure arguments:(.+)#",$line,$re)){continue;}
        if(preg_match("#with-http_v2_module#",$re[1])){
            $GLOBALS["http2_feature"]=true;
            return $GLOBALS["http2_feature"];}
    }
    $GLOBALS["http2_feature"]=false;
    return $GLOBALS["http2_feature"];
}
function http_stub_status_feature():bool{
    if(isset($GLOBALS["http_stub_status_module"])){return $GLOBALS["http_stub_status_module"];}
    $unix=new unix();
    $nginx=$unix->find_program("nginx");
    exec("$nginx -V 2>&1",$results);
    foreach ($results as $line){
        if(!preg_match("#configure arguments:(.+)#",$line,$re)){continue;}
        if(preg_match("#with-http_stub_status_module#",$re[1])){
            $GLOBALS["http_stub_status_module"]=true;
            return $GLOBALS["http_stub_status_module"];}
    }
    $GLOBALS["http_stub_status_module"]=false;
    return $GLOBALS["http_stub_status_module"];
}


function listen_port_force_http($SERVICE_ID,$DEFAULT_SERVER=false ):string{
    $default80=null;
    $unix=new unix();
    if($DEFAULT_SERVER){$default80=" default_server";}
    $results=listen_ports_sql($SERVICE_ID);
    $f=array();
    foreach ($results as $index=>$ligne) {
        $ipaddr = null;
        $interface = $ligne["interface"];
        $port = intval($ligne["port"]);
        if ($port <> 80) {
            continue;
        }
        if ($interface <> null) {
            $ipaddr = $unix->InterfaceToIPv4($interface);
        }
        $f[] = "\tlisten $ipaddr:80{$default80};";
    }
    if(count($f)>0){return @implode("\n",$f);}
    return "\tlisten 80{$default80};";
}

function listen_ports_sql($SERVICE_ID){
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    if(isset($GLOBALS["listen_ports_sql_$SERVICE_ID"])){return $GLOBALS["listen_ports_sql_$SERVICE_ID"];}
    $results=$q->QUERY_SQL("SELECT * FROM stream_ports WHERE serviceid='$SERVICE_ID'");
    $GLOBALS["listen_ports_sql_$SERVICE_ID"]=$results;
    return $results;
}

function listen_port_directive($SERVICE_ID,$CERTIFICATE_ENABLED,$DEFAULT_SERVER=false){

    $sockGeneral=new socksngix(0);
    $NginxProxyProtocol=intval($sockGeneral->GET_INFO("NginxProxyProtocol"));
    $sock=new socksngix($SERVICE_ID);
    $Redirect80To443=intval($sock->GET_INFO("Redirect80To443"));
	$http2_feature=http2_feature();
	$unix=new unix();
	$dir_default_server=null;

	if(!$http2_feature){
		$f[]="# ngx_http_v2_module not compiled";
	}

	$results=listen_ports_sql($SERVICE_ID);

	$f[]="# TCP redirector Service ID:$SERVICE_ID ".count($results)." Ports default_server:$DEFAULT_SERVER";
	
	foreach ($results as $index=>$ligne){
		$opts=array();
        if($DEFAULT_SERVER){$opts[]="default_server";}
		$ipaddr=null;
		$finalopt=";";
		$interface=trim($ligne["interface"]);
		$port=intval($ligne["port"]);
		$f[]="#\tInterface: [$interface] on port $port [".__LINE__."]";
        if($port==0){continue;}

        if($port==80){
            if($Redirect80To443==1){
                $f[]="# Skipped 80 port (Redirect80To443)";
                continue;
            }
        }

		if($interface<>null){
            $ipaddr=$unix->InterfaceToIPv4($interface);
            $f[]="#\tInterface: [$interface]= $ipaddr [".__LINE__."]";
        }
		$options=unserialize(base64_decode($ligne["options"]));
		if(!isset($options["udp"])){$options["udp"]=0;}
        if(!isset($options["http2"])){$options["http2"]=0;}
        if(!isset($options["spdy"])){$options["spdy"]=0;}
		if($ipaddr==null){$ipaddr="*";}
		$udp=intval($options["udp"]);
		$ssl=intval($options["ssl"]);
		if($udp==1){$opts[]="udp";}

		if($options["spdy"]==1){$options[]="spdy";}

        if($CERTIFICATE_ENABLED){
            if($ssl==1){
                $opts[]="ssl";
                if($options["http2"]==1){if($http2_feature){$opts[]="http2";}}
            }
        }
		if($NginxProxyProtocol==1){
		    if(!isset($options["proxy_protocol"])) {$options["proxy_protocol"]=1;}
		}
        if($NginxProxyProtocol==0) {$options["proxy_protocol"] = 0;}

		if(intval($options["proxy_protocol"])==1){
		    $opts[] = "proxy_protocol";
		}


		if(count($opts)>0){$finalopt=" ".@implode(" ", $opts).";";}
		$f[]="\tlisten $ipaddr:$port$finalopt";
	}
	return @implode("\n", $f);
}
function CleanSingleSite($serviceid){
   remove_website($serviceid);

}

function CleanWebAndProxies($reboot=false):bool{
    if(isset($GLOBALS[__FUNCTION__])){return true;}
    $GLOBALS[__FUNCTION__]=true;
	$unix=new unix();
	$FOUND=false;
	$q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
	$sql="SELECT zorder,ID FROM nginx_services WHERE enabled=1";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "*********** $q->mysql_error *****************\n";}
    $AIVAILABLE=array();
	foreach ($results as $index=>$ligne){
		$zorder=intval($ligne["zorder"]);
		$ID=$ligne["ID"];
        $AIVAILABLE[$ID]=true;
		if($GLOBALS["VERBOSE"]){echo "ADD:$index UPS[$zorder-$ID.conf]=True\n";}
		$UPS["$zorder-$ID.conf"]=true;
	}
	$files=$unix->DirFiles("/etc/nginx/stream.d");
	foreach ($files as $filename=>$nothing){
		if(isset($UPS[$filename])){continue;}
		echo "Removing /etc/nginx/stream.d/$filename\n";
		$FOUND=true;
		@unlink("/etc/nginx/stream.d/$filename");
	}

    $files=$unix->DirFiles("/etc/nginx/reverse.d");
    foreach ($files as $filename=>$nothing){
        if(isset($UPS[$filename])){continue;}
        echo "Removing /etc/nginx/reverse.d/$filename\n";
        $FOUND=true;
        @unlink("/etc/nginx/reverse.d/$filename");
    }
	
	$files=$unix->DirFiles("/etc/nginx/upstream.d");
	foreach ($files as $filename=>$nothing){
		if(isset($UPS[$filename])){continue;}
		echo "Removing /etc/nginx/upstream.d/$filename\n";
		$FOUND=true;
		@unlink("/etc/nginx/upstream.d/$filename");
	}

    $files=$unix->DirFiles("/etc/nginx/backends.d");
    foreach ($files as $filename=>$nothing){
        if(isset($UPS[$filename])){continue;}
        echo "Removing /etc/nginx/backends.d/$filename\n";
        $FOUND=true;
        @unlink("/etc/nginx/backends.d/$filename");
    }
	
	$files=$unix->DirFiles("/etc/nginx/sites-enabled");
	foreach ($files as $filename=>$nothing){
		if(isset($UPS[$filename])){continue;}
		echo "Removing /etc/nginx/sites-enabled/$filename\n";
		$FOUND=true;
		@unlink("/etc/nginx/sites-enabled/$filename");
	}
    $files=$unix->DirFiles("/var/log/nginx");
    foreach ($files as $filename=>$nothing){
        if(!preg_match("#^([0-9]+)\.debug#",$filename,$re)){continue;}
        $curID=intval($re[1]);
        if(isset($AIVAILABLE[$curID])){continue;}
        echo "Removing /var/log/nginx/$filename\n";
        $FOUND=true;
        @unlink("/etc/nginx/sites-enabled/$filename");
    }


	if(!$reboot){return true;}
	if($FOUND){
		system("/etc/init.d/nginx reload");
	}
    return true;
	
}

function CleanUpstreams(){
	$q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
	$sql="SELECT zorder,ID FROM nginx_services WHERE enabled=1 AND `type`='5'";
	$results=$q->QUERY_SQL($sql);
	
	foreach ($results as $index=>$ligne){
		$zorder=$ligne["zorder"];
		$ID=$ligne["ID"];
		$UPS["$zorder-$ID.conf"]=true;
	}
	
	$unix=new unix();
	$files=$unix->DirFiles("/etc/nginx/stream.d");
	foreach ($files as $filename=>$nothing){
		
	}
	
}

function ngx_stream_access_module($serviceid){

	$q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
	$results=$q->QUERY_SQL("SELECT * FROM ngx_stream_access_module WHERE serviceid='{$serviceid}' ORDER BY zorder");
	if(!$q->ok){return "";}
	
	$STATUS[0]="deny";
	$STATUS[1]="allow";
	$f=array();	
	if(count($results)==0){return "";}
	$TRCLASS=null;
	foreach ($results as $md5=>$ligne){
		$pattern=trim($ligne["item"]);
		if($pattern=="*"){$pattern="all";}
		$allow=$ligne["allow"];
		$f[]="\t{$STATUS[$allow]} $pattern;";
	}
	return @implode("\n", $f);
}

function PID_NUM(){
	$filename="/var/run/nginx.pid";
	$pid=trim(@file_get_contents($filename));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF_PATTERN("nginx: master process.*?/etc/nginx/nginx.conf");
}

function itcharter_path_artica(){
    $nginx_tools=new nginx_tools();
    $IF_PROXY_PAC=$nginx_tools->IF_PROXY_PAC();
    if(!$IF_PROXY_PAC){$f_php[]=_proxy_pac(true);}

    $f_php[]=_proxy_pac(true);
    $f_php[]="\trewrite ^/ITCharter/(.*)(?:/*)(.*)(?:/*)(.*)(?:/*)(.*) /itcharter.php?Token=$1&UserKey=$2&redirect=$3 last;";
    $f_php[]="\tlocation ~ /(fw|logon|index|logoff)\.(html|php) {";
    $f_php[]="\t\tdeny all;";
    $f_php[]="\t}";
    $f_php[]="";
    $f_php[]="\tlocation / {";
    $f_php[]="\t\trewrite ^([^.]*[^/])$ $1/ permanent;";
    $f_php[]="\t\ttry_files \$uri \$uri/ /itcharter.php =404;";
    $f_php[]=fastcgi_params("/var/run/phpfpm-articaserv.sock","itcharter.php");
    $f_php[]="}";


    $f_php[]="\tlocation ~ [^/]\.php(/|$) {";
    $f_php[]="\t\tfastcgi_split_path_info ^(.+?\.php)(/.*)$;";
    $f_php[]="\t\tif (!-f \$document_root\$fastcgi_script_name) {";
    $f_php[]="\t\t\treturn 404;";
    $f_php[]="\t\t}";
    $f_php[]=fastcgi_params("/var/run/nginx-phpfpm.sock");
    $f_php[]="\t}";

    return @implode("\n", $f_php);

}

function weberror_path_artica(){
    $nginx_tools=new nginx_tools();
    $IF_PROXY_PAC=$nginx_tools->IF_PROXY_PAC();
    if(!$IF_PROXY_PAC){$f_php[]=_proxy_pac(true);}
    $f_php[]=_proxy_pac(true);
	$f_php[]="";
	$f_php[]="\tlocation / {";
    $f_php[]="\t\tproxy_pass http://127.0.0.1:9577;";
    $f_php[]="\t\tproxy_set_header Host \$host;";
    $f_php[]="\t\tproxy_set_header X-Real-IP \$remote_addr;";
    $f_php[]="\t\tproxy_set_header X-Forwarded-For \$remote_addr;";
	$f_php[]="}";
	return @implode("\n", $f_php);
}

function sub_filters($serviceid=0):string{
    if($serviceid==0){return "";}
    $sock=new socksngix($serviceid);
    $sub_filters=unserialize(base64_decode($sock->GET_INFO("sub_filters")));
    $f_stub=array();
    if(count($sub_filters)==0) {return "";}
        foreach ($sub_filters as $num => $ligne) {
            $enable = intval($ligne["enable"]);
            $pattern = $ligne["pattern"];
            $replace = $ligne["replace"];
            if ($enable == 0) {
                continue;
            }
            $description = trim($ligne["description"]);
            if (strlen($pattern) == 0) {
                continue;
            }

            $description = str_replace("\n", " ", $description);
            $pattern = str_replace("'", "\'", $pattern);
            $replace = str_replace("'", "\'", $replace);
            $f_stub[] = "#\tRule $num: $description";
            $f_stub[] = "\tsub_filter '$pattern'  '$replace';";

        }
    return @implode("\n",$f_stub);
}

function static_path_WebCopy(){

}

function  static_path($serviceid,$Type=0,$WebCopyID=0){


    $f[]="\tlocation ~ [^/]\.php(/|$) {";
    $f[]="\t\t\treturn 404;";
    $f[]="\t}";

    $f[] = "\tlocation / {";
    $f[] = security_of_paths();
    $f[] = rewrite_directives($serviceid);
    $f[] = sub_filters($serviceid);
    $f[] = header_directives($serviceid);
    $f[] = "\ttry_files \$uri \$uri/ =404;";
    $f[] = "}";


}

function standard_path($serviceid=0):string{
    $nginx_tools=new nginx_tools();
    $IF_PROXY_PAC=$nginx_tools->IF_PROXY_PAC();
    if(!$IF_PROXY_PAC){$f_php[]=_proxy_pac(true);}
	$f_php[]="";
	$f_php[]="\tlocation ~ [^/]\.php(/|$) {";
    if($serviceid>0){
        $f_php[] = sub_filters($serviceid);
        $f_php[] = rewrite_directives($serviceid);
        $header_directives=header_directives($serviceid);
        if($header_directives<>null){
            $f_php[]=$header_directives;
        }
    }
	$f_php[]="\t\tfastcgi_split_path_info ^(.+?\.php)(/.*)$;";
	$f_php[]="\t\tif (!-f \$document_root\$fastcgi_script_name) {";
	$f_php[]="\t\t\treturn 404;";
	$f_php[]="\t\t}";
	$f_php[]=fastcgi_params("/var/run/nginx-phpfpm.sock",null,$serviceid);
	$f_php[]="\t}";
	
	return @implode("\n", $f_php);
	
}

function debian_mirror_path(){

    $f_php[]="\tlocation / {";
    $f_php[]="\t\tproxy_set_header     X-Real-IP \$remote_addr;";
    $f_php[]="\t\tproxy_set_header     X-Forwarded-For \$proxy_add_x_forwarded_for;";
    $f_php[]="\t\tproxy_set_header     Host \$http_host;";
    $f_php[]="\t\tproxy_set_header     X-NginX-Proxy true;";
    $f_php[]="\t\tproxy_http_version   1.1;";
    $f_php[]="\t\tproxy_set_header     Upgrade \$http_upgrade;";
    $f_php[]="\t\tproxy_redirect       off;";
    $f_php[]="\t\tproxy_set_header     X-Forwarded-Proto \$scheme;";
    $f_php[]="\t\tproxy_read_timeout   86400;";
    $f_php[]="\t\tproxy_pass           http://127.0.0.1:16324/ ;";
    $f_php[]="\t}";
    return @implode("\n", $f_php);

}

function doh_path_artica(){

    $f_php[]="\tlocation /dns-query {";
    $f_php[]="\t\tproxy_set_header     X-Real-IP \$remote_addr;";
    $f_php[]="\t\tproxy_set_header     X-Forwarded-For \$proxy_add_x_forwarded_for;";
    $f_php[]="\t\tproxy_set_header     Host \$http_host;";
    $f_php[]="\t\tproxy_set_header     X-NginX-Proxy true;";
    $f_php[]="\t\tproxy_http_version   1.1;";
    $f_php[]="\t\tproxy_set_header     Upgrade \$http_upgrade;";
    $f_php[]="\t\tproxy_redirect       off;";
    $f_php[]="\t\tproxy_set_header     X-Forwarded-Proto \$scheme;";
    $f_php[]="\t\tproxy_read_timeout   86400;";
    $f_php[]="\t\tproxy_pass           http://dns-backend/dns-query ;";
    $f_php[]="\t}";
    return @implode("\n", $f_php);

}


function _proxy_pac($norestrict=false){
    $f_php[]="";
    $f_php[]="";
    $f_php[]="\trewrite ^/ITCharter/(.*)(?:/*)(.*)(?:/*)(.*)(?:/*)(.*) /itcharter.php?Token=$1&UserKey=$2&redirect=$3 last;";
    $f_php[] = "\tlocation ~ /itcharter\.php {";
    $f_php[]="\t\troot /usr/share/artica-postfix;";
    $f_php[]="\t\tfastcgi_split_path_info ^(.+?\.php)(/.*)$;";
    $f_php[] = fastcgi_params("/var/run/phpfpm-articaserv.sock","itcharter.php");
    $f_php[] = "\t}";

    if(!$norestrict) {
        $f_php[] = "#\t Proxy Pac No restriction";
        $f_php[] = "\tlocation / {";
        $f_php[]=   security_of_paths();
        $f_php[] = "\tif (\$uri !~ (/(wpad|wspad|proxy)\.(dat|pac)$)){";
        $f_php[] = "\t\treturn 302 /proxy.pac;";
        $f_php[] = "\t\t}";
        $f_php[] = "\t}";
    }else{
        $f_php[]=   security_of_paths();
    }

    $f_php[]="";
    $f_php[]="\tlocation ~ /(wpad|wspad|proxy)\.(dat|pac)$ {";
    $f_php[]="\t\tproxy_pass http://127.0.0.1:9505;";
    $f_php[]="\t\tproxy_set_header Host \$host;";
    $f_php[]="\t\tproxy_set_header X-Real-IP \$remote_addr;";
    $f_php[]="\t\tproxy_set_header X-Forwarded-For \$remote_addr;";
    $f_php[]="\t}";



    return @implode("\n", $f_php);

}

function fastcgi_cache($ID){
    if(!is_dir("/etc/nginx/fastcgi")){@mkdir("/etc/nginx/fastcgi",0755,true);}
    $target_file="/etc/nginx/fastcgi/$ID.conf";

    if(!isset($GLOBALS["nginxCachesDir"])) {
        $GLOBALS["nginxCachesDir"] = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginxCachesDir"));
    }
    if($GLOBALS["nginxCachesDir"]==0){
        $f[]="# CGI Cache not enabled\n";
        @file_put_contents($target_file,@implode("\n",$f));
        return true;
    }

    $f[] = "\tset \$skip_cache 0;";
    $f[] = "\tif (\$request_method = POST) {";
    $f[] = "\t\tset \$skip_cache 1;";
    $f[] = "\t}";
    $f[] = "\tif (\$request_uri ~* \"/wp-admin/|/xmlrpc.php|wp-.*?.php|/feed/|index.php|sitemap(_index)?.xml\") {";
    $f[] = "\t\tset \$skip_cache 1;";
    $f[] = "\t}";
    $f[] = "\t# Don't use the cache for logged in users or recent commenters";
    $f[] = "\tif (\$http_cookie ~* \"comment_author|wordpress_[a-f0-9]+|wp-postpass|wordpress_no_cache|wordpress_logged_in\") {";
    $f[] = "\t\tset \$skip_cache 1;";
    $f[] = "\t}";

    $f[] = "\tif (\$http_cookie ~* \"PHPSESSID\"){";
    $f[] = "\t\tset \$skip_cache 1;";
    $f[] = "\t}";

    $nginxsock = new socksngix($ID);
    $proxy_cache_valid=intval($nginxsock->GET_INFO("proxy_cache_valid"));
    if($proxy_cache_valid==0){$proxy_cache_valid=4320;}

    $f[]="\t\tfastcgi_cache php;";
    $f[]="\t\tfastcgi_cache_revalidate on;";
    $f[]="\t\tfastcgi_cache_key \"\$scheme\$request_method\$host\$request_uri\";";
    $f[]="\t\tfastcgi_cache_valid 200 302 {$proxy_cache_valid}m;";
    $f[]="\t\tfastcgi_cache_bypass \$skip_cache;";
    $f[]="\t\tfastcgi_no_cache \$skip_cache;";
    @file_put_contents($target_file,@implode("\n",$f));
    return true;

}

function fastcgi_params($fastcgi_path=null,$index=null,$serviceid=0){

    if($serviceid>0) {
        fastcgi_cache($serviceid);
        $f_php[] = "\t\tinclude /etc/nginx/fastcgi/$serviceid.conf;";
    }
    $f_php[]="\t\tfastcgi_pass 	   unix:$fastcgi_path;";
    if($index<>null){
        $f_php[]="\t\tfastcgi_index $index;";
    }
    $f_php[]="\t\tfastcgi_buffers 8 16k;";
    $f_php[]="\t\tfastcgi_buffer_size 32k;";
    $f_php[]="\t\tfastcgi_read_timeout 300;";
    $f_php[]="\t\tfastcgi_connect_timeout 300;";
    $f_php[]="\t\tfastcgi_send_timeout 300;";
    $f_php[]="\t\tfastcgi_param   QUERY_STRING             \$query_string;";
    $f_php[]="\t\tfastcgi_param   REQUEST_METHOD           \$request_method;";
    $f_php[]="\t\tfastcgi_param   CONTENT_TYPE             \$content_type;";
    $f_php[]="\t\tfastcgi_param   CONTENT_LENGTH           \$content_length;";
    $f_php[]="";
    $f_php[]="\t\tfastcgi_param   SCRIPT_FILENAME          \$document_root\$fastcgi_script_name;";
    $f_php[]="\t\tfastcgi_param   SCRIPT_NAME              \$fastcgi_script_name;";
    $f_php[]="\t\tfastcgi_param   PATH_INFO                \$fastcgi_path_info;";
    $f_php[]="\t\tfastcgi_param   PATH_TRANSLATED          \$document_root\$fastcgi_script_name;";
    $f_php[]="\t\tfastcgi_param   REQUEST_URI              \$request_uri;";
    $f_php[]="\t\tfastcgi_param   DOCUMENT_URI             \$document_uri;";
    $f_php[]="\t\tfastcgi_param   DOCUMENT_ROOT            \$document_root;";
    $f_php[]="\t\tfastcgi_param   SERVER_PROTOCOL          \$server_protocol;";
    $f_php[]="";
    $f_php[]="\t\tfastcgi_param   GATEWAY_INTERFACE       CGI/1.1;";
    $f_php[]="\t\tfastcgi_param   SERVER_SOFTWARE         Artica/1.0;";
    $f_php[]="";
    $f_php[]="\t\tfastcgi_param   REMOTE_ADDR              \$remote_addr;";
    $f_php[]="\t\tfastcgi_param   REMOTE_PORT              \$remote_port;";
    $f_php[]="\t\tfastcgi_param   SERVER_ADDR              \$server_addr;";
    $f_php[]="\t\tfastcgi_param   SERVER_PORT              \$server_port;";
    $f_php[]="\t\tfastcgi_param   SERVER_NAME              \$server_name;";
    $f_php[]="";
    $f_php[]="\t\tfastcgi_param   HTTPS                    \$https;";
    $f_php[]="";
    $f_php[]="#\t\tPHP only, required if PHP was built with --enable-force-cgi-redirect";
    $f_php[]="\t\tfastcgi_param   REDIRECT_STATUS         200;";
    return @implode("\n",$f_php);
}

function hotspot_path_artica(){
    $nginx_tools=new nginx_tools();
    $IF_PROXY_PAC=$nginx_tools->IF_PROXY_PAC();
    if(!$IF_PROXY_PAC){$f_php[]=_proxy_pac(true);}
    $f_php[]=_proxy_pac(true);
    $f_php[]="";
    $f_php[]="\tlocation / {";
    $f_php[]="\t\tproxy_pass http://127.0.0.1:8577/;";
    $f_php[]="\t\tproxy_set_header Host \$host;";
    $f_php[]="\t\tproxy_set_header X-Real-IP \$remote_addr;";
    $f_php[]="\t\tproxy_set_header X-Forwarded-For \$remote_addr;";
    $f_php[]="}";
    return @implode("\n", $f_php);
}
function proxy_path_artica(){
	$unix=new unix();
	include_once(dirname(__FILE__)."/ressources/class.webconsole.params.inc");

	$LighttpdArticaListenInterface  = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaListenInterface"));
    $ArticaHttpsPort                = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpsPort"));
    $ArticaHttpUseSSL               = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpUseSSL"));

	if($LighttpdArticaListenInterface<>null){
		$ipaddr=$unix->InterfaceToIPv4($LighttpdArticaListenInterface);
		if($LighttpdArticaListenInterface=="lo"){$ipaddr="127.0.0.1";}
	}
	if($ipaddr==null){$ipaddr="127.0.0.1";}

	if($ArticaHttpsPort==0){$ArticaHttpsPort=9000;}
	$method="http";
	if($ArticaHttpUseSSL==1){$method="https";}
	$finalURI="$method://$ipaddr:$ArticaHttpsPort";
	
	$f_php[]="";
	$f_php[]="\tlocation / {";
	$f_php[]="\t\tproxy_pass $finalURI;";
	$f_php[]="\t\tproxy_set_header Host \$host;";
	$f_php[]="\t\tproxy_set_header X-Real-IP \$remote_addr;";
	$f_php[]="\t\tproxy_set_header X-Forwarded-For \$remote_addr;";
	$f_php[]="\t}";
	$f_php[]="";
    $f_php[]=_proxy_pac(true);
    $f_php[]="";
	return @implode("\n", $f_php);
	
}

function TestingSingleSite($pathToInclude){

    $unix=new unix();
    $nginxbin=$unix->find_program("nginx");

    $f[]="user  www-data;";
    $f[]="worker_processes  1;";
    $f[]="pid        /etc/nginx/nginx.testing.pid;";
    $f[]="events { ";
    $f[]="	worker_connections  1024;";
    $f[]="	}";
    $f[]="";
    $f[]="";
    $f[]="http {";
    $f[]="    include /etc/nginx/mime.types;";
    $f[]="    default_type  application/octet-stream;";
    $f[]="    sendfile        on;";
    $f[]="    keepalive_timeout  65;";
    $f[]="    include $pathToInclude;";
    $f[]="";
    $f[]="}";
    @file_put_contents("/etc/nginx/nginx.testing.conf",@implode("\n",$f));
    exec("$nginxbin -c /etc/nginx/nginx.testing.conf -t 2>&1",$testsR);
    foreach ($testsR as $line) {
        if (preg_match("#configuration file.*?test is successful#i", $line)) {
            echo "Starting......: " . date("H:i:s") . " [INIT]: Nginx[" . __LINE__ . "](" . basename(__FILE__) . ") testing configuration Success...\n";
            @unlink("/etc/nginx/nginx.testing.conf");
            return true;
        }
    }

    @unlink("/etc/nginx/nginx.testing.conf");
    return false;
}

function Make_site_failed($ID,$error){
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $error=$q->sqlite_escape_string2($error);
    $q->QUERY_SQL("UPDATE nginx_services SET goodconftime=0,goodconf='',badconf_error='$error' WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error."\n";}

}


function ssl_directives($ID){
	$unix=new unix();
	$sockngix                   = new socksngix($ID);
	$ssl_protocols              = $sockngix->GET_INFO("ssl_protocols");
	$ssl_ciphers                = $sockngix->GET_INFO("ssl_ciphers");
	$ssl_prefer_server_ciphers  = SwitchOnOff(intval($sockngix->GET_INFO("ssl_prefer_server_ciphers")));
	$ssl_buffer_size            = intval($sockngix->GET_INFO("ssl_buffer_size"));
	$hts_enabled                = intval($sockngix->GET_INFO("hts_enabled"));
    $ssl_certificate            = $sockngix->GET_INFO("ssl_certificate");
    $ssl_client_certificate     = trim($sockngix->GET_INFO("ssl_client_certificate"));


	if($ssl_buffer_size==0){$ssl_buffer_size=16;}
    if($ssl_protocols==null){$ssl_protocols="TLSv1.2 TLSv1.3";}
	if($ssl_ciphers==null){$ssl_ciphers="ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA256";}
	
	if($ssl_certificate==null){
		$f[]="# No certificate returned";
		$GLOBALS["CERTIFICATE_ENABLED"]=false;
		return @implode("\n",$f);
	}
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");

    $tcountssl=count_of_ssl_ports($ID);
    if($tcountssl==0){
        $f[]="# No ports using ssl stamped, find 443 certificate:\"$ssl_certificate\"";
        $ligne=$q->mysqli_fetch_array("SELECT count(*) as tcountssl FROM stream_ports WHERE serviceid=$ID and port=443");
        $tcountssl=intval($ligne["tcountssl"]);
        $f[]="# $tcountssl port(s) using 443 port certificate:\"$ssl_certificate\"";
    }
    if($tcountssl==0) {
        $GLOBALS["CERTIFICATE_ENABLED"] = false;
        return @implode("\n", $f);
    }

    $f[]="# $tcountssl ports using certificate \"$ssl_certificate\"";
	$nginx_certificate=new nginx_certificate($ssl_certificate,null);
	
	$f[]="#";
	$f[]="#\tSSL ---------------------------------- [".__LINE__."]";
	$f[]="#\tsslcertificate = <$ssl_certificate>";
	

	if(!is_file("/etc/nginx/certificates/dhparam.pem")){
		if(!is_dir("/etc/nginx/certificates")){@mkdir("/etc/nginx/certificates",0755,true);}
		$openssl=$unix->find_program("openssl");
		system("$openssl dhparam -outform PEM -out /etc/nginx/certificates/dhparam.pem 2048");

	}
	if(!is_file("/etc/nginx/certificates/dhparam.pem")){
		$f[]="\tssl_dhparam /etc/nginx/certificates/dhparam.pem;";
	}
	$f[]=$nginx_certificate->GetConf();
	$f[] = "\tssl_session_cache shared:SSL:50m;";
	$f[] = "\tssl_session_timeout  5m;";
    $f[] = "\tssl_protocols  $ssl_protocols;";
    $f[] = "\tssl_ciphers '$ssl_ciphers';";
    $f[] = "\tssl_prefer_server_ciphers $ssl_prefer_server_ciphers;";
    $f[] = "\tssl_buffer_size {$ssl_buffer_size}k;";

    $EnableClientCertificate=intval($sockngix->GET_INFO("EnableClientCertificate"));
    $f[] = "#\t * * * * Client Certificate #$ID [$EnableClientCertificate] * * * * ";

    if($EnableClientCertificate==1){
        $f[] = "#\t Feature activated";
    }else{
        $f[] = "#\t Feature Disabled";
    }
    if($ssl_client_certificate==null){
        $f[]="#\tNo Client Certificate defined";
        $EnableClientCertificate=0;
    }

    if($EnableClientCertificate==1){
        $nginx_certificate=new nginx_certificate($ssl_client_certificate,null);
        $f[]=$nginx_certificate->client_certificate();
    }
    $f[] = "#\t * * * * * * * * * * * * *  * * * *\n";


    if($hts_enabled==1){
        $f[]="\tadd_header Strict-Transport-Security \"max-age=31536000; includeSubDomains\" always;";
    }

	$GLOBALS["CERTIFICATE_ENABLED"]=true;
	return @implode("\n",$f);
	
}
function count_of_ssl_ports($serviceid):int{
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $results=$q->QUERY_SQL("SELECT options FROM stream_ports WHERE serviceid=$serviceid");
    $c=0;
    foreach ($results as $index=>$ligne){
        $options=unserialize(base64_decode($ligne["options"]));
        if(intval($options["ssl"])==1){
            $c++;
        }
    }
    return $c;
}

function SwitchOnOff($value){
	if($value==1){return "on";}
	if($value==0){return "off";}
	return "off";
}

function ElasticSearch(){
    $EnableElasticSearch            = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableElasticSearch"));

    if($EnableElasticSearch==0){
        if(is_file("/etc/nginx/ElasticSearch/ElasticSearch.conf")){
            @unlink("/etc/nginx/ElasticSearch/ElasticSearch.conf");
            @touch("/etc/nginx/ElasticSearch/ElasticSearch.conf");
        }
        return false;
    }

    if(!is_dir("/etc/nginx/certificates")){@mkdir("/etc/nginx/certificates",0755,true);}
    if(!is_dir("/etc/nginx/ElasticSearch")){@mkdir("/etc/nginx/ElasticSearch",0755,true);}
    $ElasticsearchBehindReverse     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchBehindReverse"));
    $ElasticSearchBehindHostname    = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchBehindHostname"));
    $ElasticSearchBehindCertificate = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchBehindCertificate"));
    $ElasticsearchAuthenticate      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchAuthenticate"));

    if($ElasticSearchBehindHostname==null){$ElasticSearchBehindHostname=gethostname();}

    if($ElasticsearchBehindReverse==0){
        @unlink("/etc/nginx/ElasticSearch/ElasticSearch.conf");
        @touch("/etc/nginx/ElasticSearch/ElasticSearch.conf");
        system("/etc/init.d/nginx reload");
        return false;
    }

    $f[]="";
    $f[]="server {";
    $f[]="    listen 443;";
    if($ElasticSearchBehindHostname=="*") {
        $f[] = "    server_name _;";
    }else{
        $f[] = "    server_name $ElasticSearchBehindHostname;";
    }


    $ssl_protocols="TLSv1 TLSv1.1 TLSv1.2";
    $ssl_ciphers="ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:ECDHE-RSA-RC4-SHA:ECDHE-ECDSA-RC4-SHA:RC4-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!3DES:!MD5:!PSK";


    $nginx_certificate=new nginx_certificate($ElasticSearchBehindCertificate,null);

    $f[]="#";
    $f[]="#\tSSL ---------------------------------- [".__LINE__."]";
    $f[]="#\tsslcertificate = <$ElasticSearchBehindCertificate>";


    if(!is_file("/etc/nginx/certificates/dhparam.pem")){

        $unix=new unix();
        $openssl=$unix->find_program("openssl");
        system("$openssl dhparam -outform PEM -out /etc/nginx/certificates/dhparam.pem 2048");

    }
    if(!is_file("/etc/nginx/certificates/dhparam.pem")){
        $f[]="\tssl_dhparam /etc/nginx/certificates/dhparam.pem;";
    }
    $f[]=$nginx_certificate->GetConf();
    $f[]="\tssl_session_cache shared:SSL:50m;";
    $f[]="\tssl_session_timeout  5m;";
    $f[]="\tssl_protocols  $ssl_protocols;";
    $f[]="\tssl_ciphers '$ssl_ciphers';";
    $f[]="\tssl_buffer_size on;";
    $f[]="";
    $f[]="    location / {";
    if($ElasticsearchAuthenticate==1) {
        $f[] = "      auth_basic \"Restricted Access\";";
        $f[] = "      auth_basic_user_file /etc/nginx/ElasticSearch/htpasswd.users;";
    }
    $f[]="      proxy_pass http://127.0.0.1:9200;";
    $f[]="      proxy_redirect off;";
    $f[]="      proxy_buffering off;";
    $f[]="      proxy_http_version 1.1;";
    $f[]="      proxy_set_header Connection \"Keep-Alive\";";
    $f[]="      proxy_set_header Proxy-Connection \"Keep-Alive\";";
    $f[]="    }";
    $f[]="";
    $f[]="  }";
    $f[]="";

    @file_put_contents("/etc/nginx/ElasticSearch/ElasticSearch.conf",@implode("\n",$f));
    return true;
}

function export_single_site_progress($prc,$text,$ID){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"nginx.export.$ID.progress");
}

function export_single_site($ID=0){
    $unix=new unix();
    echo "Exporting Site $ID\n";
    $target_file=PROGRESS_DIR."/www-$ID.tar.gz";
    export_single_site_progress(15,"{exporting} #$ID",$ID);
    $f[]="/etc/nginx/reverse.d/0-$ID.conf";
    $f[]="/etc/nginx/modsecurities/nginx-$ID.conf";
    $f[]="/etc/nginx/modsecurities/locations-$ID.conf";
    $f[]="/etc/nginx/modsecurities/whiterules-$ID.conf";
    $t=array();
    foreach ($f as $path){
        if(!is_file($path)){
            continue;
        }
        echo "Exporting $path\n";
        $t[]=$path;


    }

    if(count($t)==0){
        echo "Nothing to export\n";
        export_single_site_progress(110,"{exporting} #$ID {failed}",$ID);
        return false;
    }

    $tar=$unix->find_program("tar");
    if(is_file($target_file)){@unlink($target_file);}
    export_single_site_progress(80,"{exporting} #$ID...",$ID);
    system("$tar czvf $target_file ".@implode(" ",$t));
    export_single_site_progress(100,"{exporting} #$ID {success}",$ID);
    return true;
}
function create_server_certificate_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"nginx.servercert.progress");

}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return $ligne["servicename"];
}
function create_server_certificate($SERVICEID){
    $unix=new unix();
    $SERVICEID=intval($SERVICEID);
    if($SERVICEID==0) {
        $SERVICEID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CREATE_CERT_CLIENT_ID"));

    }
    if($SERVICEID==0) {
        echo "ServiceID: $SERVICEID is null!\n";
        create_server_certificate_progress(110,"#$SERVICEID {failed}");
        return false;
    }
    $socknginx=new socksngix($SERVICEID);
    $openssl=$unix->find_program("openssl");
    $path="/etc/nginx/CERT_TEMP/$SERVICEID";
    if(!is_dir($path)){@mkdir($path,0755,true);}
    $ca_crt="$path/ca.crt";
    $ca_key="$path/ca.key";
    $rm=$unix->find_program("rm");
    $deleteall="$rm -rf $path";
    $servicename=get_servicename($SERVICEID);

    $ssl_certificate=$socknginx->GET_INFO("ssl_certificate");

    if($ssl_certificate==null){
        create_server_certificate_progress(110,"No master Certificate #$SERVICEID {failed}");
        shell_exec($deleteall);
        return false;
    }




    $CLIENT_CERT_SERVER_TEMP=unserialize($socknginx->GET_INFO("CLIENT_CERT_SERVER_TEMP"));
    if(!isset($CLIENT_CERT_SERVER_TEMP["CertificateName"])){$CLIENT_CERT_SERVER_TEMP["CertificateName"]=$servicename;}
    if(!isset($CLIENT_CERT_SERVER_TEMP["levelenc"])){$CLIENT_CERT_SERVER_TEMP["levelenc"]=4096;}
    if(!isset($CLIENT_CERT_SERVER_TEMP["CertificateMaxDays"])){$CLIENT_CERT_SERVER_TEMP["CertificateMaxDays"]=3650;}

    $CertificateMaxDays=$CLIENT_CERT_SERVER_TEMP["CertificateMaxDays"];
    $CertificateName=$CLIENT_CERT_SERVER_TEMP["CertificateName"];
    $LevelEnc=$CLIENT_CERT_SERVER_TEMP["levelenc"];
    $subjectAltName=$CLIENT_CERT_SERVER_TEMP["subjectAltName"];


    create_server_certificate_progress(20,"Create a CA Certificate #$SERVICEID");
    $cmd="$openssl genrsa -des3 -passout pass:pass -out $ca_key $LevelEnc 2>&1";
    echo "STEP1: $cmd\n";
    $results=array();
    exec("$cmd",$results);
    foreach ($results as $line){echo "STEP1: $line\n";}

    if(!is_file($ca_key)){
        create_server_certificate_progress(110,"Create a CA Certificate #$SERVICEID {failed}");
        shell_exec($deleteall);
        return false;
    }
    $subjectAltNames=parse_subjectAltNames($subjectAltName);
    $subj=build_subject($ssl_certificate,$CertificateName);
    $addext2 = " -addext \"extendedKeyUsage=serverAuth,clientAuth\"";
    if($subjectAltNames<>null){
        $addext = " -addext \"subjectAltName=$subjectAltNames\"";
    }


    create_server_certificate_progress(50,"Create a CA Certificate #$SERVICEID");
    $cmd="$openssl req -new -x509 -batch -days $CertificateMaxDays -passin pass:pass $subj$addext$addext2 -key $ca_key -out $ca_crt 2>&1";
    echo "STEP2: $cmd\n";
    $results=array();
    exec("$cmd",$results);
    foreach ($results as $line){echo "STEP2: $line\n";}
    if(!is_file($ca_crt)){
        echo "Missing $ca_crt\n";
        create_server_certificate_progress(110,"Create a CA Certificate #$SERVICEID {failed}");
        shell_exec($deleteall);
        return false;
    }

    create_server_certificate_progress(90,"Saving a CA Certificate #$SERVICEID");
    $q= new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $sql="CREATE TABLE IF NOT EXISTS `nginx_servers_certs` (
	`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
	`CertificateName` text,
	`levelenc` INTEGER NOT NULL DEFAULT 4096,
	`ca_key` text,
	`ca_crt` text  )";
    $q->QUERY_SQL($sql);

    if(!$q->FIELD_EXISTS("nginx_servers_certs","levelenc")){
        $q->QUERY_SQL("ALTER TABLE nginx_servers_certs ADD `levelenc` INTEGER NOT NULL DEFAULT 4096");
    }

    $ca_data=base64_encode(@file_get_contents($ca_key));
    $crt_data=base64_encode(@file_get_contents($ca_crt));
    $q->QUERY_SQL("INSERT INTO nginx_servers_certs (CertificateName,ca_key,ca_crt,levelenc)
    VALUES ('$CertificateName','$ca_data','$crt_data','$LevelEnc')");
    if(!$q->ok){
        echo $q->mysql_error."\n";
        create_server_certificate_progress(110,"Create a CA Certificate #$SERVICEID {failed} SQL Error");
        shell_exec($deleteall);
        return false;
    }
    create_server_certificate_progress(100,"Create a CA Certificate #$SERVICEID {success}");
    shell_exec($deleteall);


    $ligne=$q->mysqli_fetch_array("SELECT ID FROM nginx_servers_certs ORDER BY ID DESC LIMIT 1");
    $socknginx->SET_INFO("ssl_client_certificate",$ligne["ID"]);
    return true;


}
function create_client_certificate_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"nginx.clientcert.progress");

}
function create_client_certificate($cert_id){
    $unix   = new unix();
    $q      = new lib_sqlite("/home/artica/SQLITE/nginx.db");

    if(!$q->FIELD_EXISTS("nginx_servers_certs","levelenc")){ $q->QUERY_SQL("ALTER TABLE nginx_servers_certs ADD `levelenc` INTEGER NOT NULL DEFAULT 4096"); }

    $ligne  = $q->mysqli_fetch_array("SELECT CertificateName,ca_crt,ca_key,levelenc FROM nginx_servers_certs WHERE ID='$cert_id'");
    $ca_crt_data = base64_decode($ligne["ca_crt"]);
    $ca_key_data = base64_decode($ligne["ca_key"]);
    $CLIENT_CERT_CLIENT_TEMP=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CLIENT_CERT_CLIENT_TEMP"));
    $array=openssl_x509_parse($ca_crt_data);
    $openssl_x509_read=openssl_x509_read($ca_crt_data);
    if(!isset($CLIENT_CERT_CLIENT_TEMP["CertificateMaxDays"])){$CLIENT_CERT_CLIENT_TEMP["CertificateMaxDays"]=4096;}
    $CertificateName=$ligne["CertificateName"];
    $CertificateMaxDays=$CLIENT_CERT_CLIENT_TEMP["CertificateMaxDays"];
    $password=$CLIENT_CERT_CLIENT_TEMP["password"];
    $subjectAltName=$array["extensions"]["subjectAltName"];
    foreach ($array as $key=>$val){
        if(is_array($val)){continue;}
        echo "Server Certificate: [$key]: <$val>\n";
    }
    $levelenc=intval($ligne["levelenc"]);
    if($levelenc==0){$levelenc=4096;}
    $set_serial=time();

    if(empty($openssl_x509_read)) {
        create_client_certificate_progress(110,"$CertificateName unable to read");
        echo "#$cert_id $CertificateName unable to read!\n";
        return false;
    }

    foreach ($array["subject"] as $key=>$val){$ST[$key]=$val;}
    $username=$CLIENT_CERT_CLIENT_TEMP["username"];
    $ST["CN"]= $username;
    $SUBJ=array();
    foreach ($ST as $key=>$val){
        $SUBJ[]="$key=$val";
    }
    $subj="-subj \"/".@implode("/",$SUBJ)."\"";


    $addext2 = " -addext \"extendedKeyUsage=clientAuth\"";
    if(strlen($subjectAltName)>3){
        $addext = " -addext \"subjectAltName=$subjectAltName\"";
    }

    $openssl=$unix->find_program("openssl");
    $path="/etc/nginx/CERT_TEMP/$cert_id";
    if(!is_dir($path)){@mkdir($path,0755,true);}
    $user_key="$path/user.key";
    $user_csr="$path/user.csr";
    $user_crt="$path/user.crt";
    $ca_crt="$path/ca.crt";
    $ca_key="$path/ca.key";
    $user_pfx="$path/user.pfx";
    $rm=$unix->find_program("rm");
    $deleteall="$rm -rf $path";

    @file_put_contents($ca_crt,$ca_crt_data);
    @file_put_contents($ca_key,$ca_key_data);

    create_client_certificate_progress(50,"Create a CA Certificate #$username");
    $cmd="$openssl genrsa -des3 -passout pass:pass -out $user_key $levelenc 2>&1";
    echo "$cmd\n";
    $results=array();
    exec("$cmd",$results);
    foreach ($results as $line){echo "STEP1: $line\n";}

    if(!is_file($user_key)){
        echo "$user_key, no such file\n";
        create_client_certificate_progress(110,"Create a CA Certificate #$username {failed}");
        shell_exec($deleteall);
        return false;
    }

    create_client_certificate_progress(60,"Create a CSR Certificate #$username");

    $cmd="$openssl req -new -batch -passin pass:pass $subj$addext$addext2 -key $user_key -out $user_csr 2>&1";
    echo "$cmd\n";
    $results=array();
    exec("$cmd",$results);
    foreach ($results as $line){echo "STEP2: $line\n";}

    if(!is_file($user_csr)){
        echo "$user_csr, no such file\n";
        create_client_certificate_progress(110,"Create a CSR Certificate #$username {failed}");
        shell_exec($deleteall);
        return false;
    }

    create_client_certificate_progress(70,"Create Certificate #$username");
    $cmd="$openssl x509 -req -days $CertificateMaxDays -passin pass:pass -in $user_csr -CA $ca_crt -CAkey $ca_key -set_serial $set_serial -out $user_crt 2>&1";
    echo "$cmd\n";
    $results=array();
    exec("$cmd",$results);
    foreach ($results as $line){echo "STEP3: $line\n";}

    if(!is_file($user_crt)){
        echo "$user_crt, no such file\n";
        create_client_certificate_progress(110,"Create Certificate #$username {failed}");
        shell_exec($deleteall);
        return false;
    }
    $pkcs12Pass=$unix->shellEscapeChars($password);
    create_client_certificate_progress(75,"Creating a PKCS #12 (PFX) #$username");
    $cmd="$openssl pkcs12 -export -password pass:$pkcs12Pass -passin pass:pass -out $user_pfx -inkey $user_key -in $user_crt -certfile $ca_crt -nodes 2>&1";
    $results=array();
    exec("$cmd",$results);
    foreach ($results as $line){echo "STEP4: $line\n";}

    if(!is_file($user_pfx)){
        echo "$user_pfx, no such file\n";
        create_client_certificate_progress(110,"Creating a PKCS #12 (PFX) #$username {failed}");
        shell_exec($deleteall);
        return false;
    }

    $sql="CREATE TABLE IF NOT EXISTS `nginx_clients_certs` (
	`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
	`certid` INTEGER,
	`ClientName` text,
	`levelenc` INTEGER NOT NULL DEFAULT 4096,
	`user_key` text,
	`user_crt` text,
	`user_pfx` text
	 )";
    $q->QUERY_SQL($sql);

    if(!$q->FIELD_EXISTS("nginx_clients_certs","certid")){ $q->QUERY_SQL("ALTER TABLE nginx_clients_certs ADD `certid` INTEGER NOT NULL DEFAULT 0"); }


    $user_key_data=base64_encode(@file_get_contents($user_key));
    $user_crt_data=base64_encode(@file_get_contents($user_crt));
    $user_pfx_data=base64_encode(@file_get_contents($user_pfx));

    create_client_certificate_progress(80,"Saving data #$username");

    $q->QUERY_SQL("INSERT INTO nginx_clients_certs (certid,ClientName,levelenc,user_key,user_crt,user_pfx)
    VALUES ('$cert_id','$username','$levelenc','$user_key_data','$user_crt_data','$user_pfx_data')");

    if(!$q->ok){
        echo $q->mysql_error."\n";
        create_client_certificate_progress(110,"Saving data {failed} SQL Error #$username");
        shell_exec($deleteall);
        return false;
    }
    create_client_certificate_progress(100,"#$username {success}");
    shell_exec($deleteall);
    return true;

}

function parse_subjectAltNames($AltNames):string{
    $fina=array();
    $MAIN=array();
    $AltNames=trim(strtolower($AltNames));
    if($AltNames==null){return "";}
    $tb=multiexplode(array(","," ","|",":",";"),$AltNames);
    if(count($tb)>0){
        foreach ($tb as $a) {
            $a = trim($a);
            if ($a == null) {continue;}
            $MAIN[$a]=true;
        }
    }else{
        $MAIN[$AltNames]=true;
    }
    foreach ($MAIN as $dom=>$none){
        $fina[]="DNS:$dom";
    }

    return @implode(",",$fina);

}
function multiexplode ($delimiters,$string) {
    $ready = str_replace($delimiters, $delimiters[0], $string);
    $launch = explode($delimiters[0], $ready);
    return  $launch;
}


function build_subject($ssl_certificate,$certname){
    $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
    $sql="SELECT * FROM sslcertificates WHERE CommonName='$ssl_certificate'";
    $SUBJ=array();
    $ligne=$q->mysqli_fetch_array($sql);
    if($ligne["CountryName"]<>null){$SUBJ[]="C={$ligne["CountryName"]}";}
    if($ligne["stateOrProvinceName"]<>null){$SUBJ[]="ST={$ligne["stateOrProvinceName"]}";}
    if($ligne["localityName"]<>null){$SUBJ[]="L={$ligne["localityName"]}";}
    if($ligne["OrganizationName"]<>null){$SUBJ[]="O={$ligne["OrganizationName"]}";}
    if($ligne["OrganizationalUnit"]<>null){$SUBJ[]="OU={$ligne["OrganizationalUnit"]}";}
    $SUBJ[]="CN=$certname";
    return "-subj \"/".@implode("/",$SUBJ)."\"";


}

