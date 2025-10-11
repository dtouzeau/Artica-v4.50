<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
install($argv[1]);exit;



function RESTevents($text,$line=0):bool{
    if($line>0){$text="$text [$line]";}
    $LOG_SEV=LOG_INFO;
    if(!function_exists("openlog")){return false;}
    openlog("REST_API", LOG_PID , LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    $unix=new unix();
    $unix->ToSyslog($text." [$line]",false,"artica-update");
    return true;
}

function install($filename):bool{
    $ARTICA_WORK                = "/etc/artica-postfix";
    $ARTICA_BASE                = "/usr/share/artica-postfix";
    $basename                   = basename(__FILE__);
    $func                       = __FUNCTION__;
    $unix                       = new unix();
    $RESS_BASE                  = "$ARTICA_BASE/ressources";
    $LOGS_BASE                  = "$RESS_BASE/logs";
    $GLOBALS["PROGRESS_FILE"]   = "$LOGS_BASE/artica.install.progress";
	$GLOBALS["LOG_FILE"]        = "$LOGS_BASE/web/artica.install.progress.txt";
	$pidfile                    = "$ARTICA_WORK/pids/$basename.$func.pid";
    $tarballs_file              = "$RESS_BASE/conf/upload/$filename";
    $tar                        = $unix->find_program("tar");
	

	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
	    $TimeExec=$unix->PROCCESS_TIME_MIN($pid);
	    if($TimeExec<30) {
            RESTevents("[BINARY]: Already PID $pid running", __LINE__);
            build_progress("Already task running PID: $pid since {$TimeExec}Mn", 110);
            if (is_file($tarballs_file)) {
                @unlink($tarballs_file);
            }
            return false;
        }
        echo "Killing old process PID: $pid running since {$TimeExec}Mn\n";
	    $unix->KILL_PROCESS($pid,9);
    }
    if(is_file($pidfile)){@unlink($pidfile);}
    @file_put_contents($pidfile,getmypid());

	$LINUX_CODE_NAME            = $unix->LINUX_CODE_NAME();
	$LINUX_DISTRIBUTION         = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("LinuxDistributionFullName");
	$LINUX_VERS                 = $unix->LINUX_VERS();
	$LINUX_ARCHITECTURE         = $unix->LINUX_ARCHITECTURE();
	$APACHEUSER                 = $unix->APACHE_SRC_ACCOUNT();
	$TMP_DIR                    = $unix->TEMP_DIR();
	$ORGV                       = trim(@file_get_contents("$ARTICA_BASE/VERSION"));
	$PATCH_VER                  = null;
    $ASPATCH                    = false;
    $patched                    = "";

	if(!is_file($tarballs_file)){
        RESTevents("[BINARY]:install [$tarballs_file] unable to stat",__LINE__);
        build_progress("$tarballs_file no such file.",110);
        return false;
    }


	$size=filesize($tarballs_file);
    echo "Package $tarballs_file $size bytes\n";

	if(preg_match("#ArticaP[0-9]+\.tgz#i",$filename)){
        $tarballs_file_encoded=base64_encode($tarballs_file);
        $php=$unix->LOCATE_PHP5_BIN();
        echo "Use patching method..\n";
        $unix->ToSyslog("Use patching method \"$tarballs_file_encoded\"",false,"artica-update");
        system("$php $ARTICA_BASE/exec.nightly.php --patch $tarballs_file_encoded");
        exit;
    }

	if(preg_match("#^dwagent\.tar\.gz$#",$filename)){
	    $md5=md5_file($tarballs_file);
	    echo "md5: $md5\n";
	    if($md5<>"a005ce016b2caee4619e7eda9f03d3e2"){
            build_progress("DWAgent {failed}",110);
            echo "Expected a005ce016b2caee4619e7eda9f03d3e2\n";
            @unlink($tarballs_file);
            return false;
        }


        build_progress("$tarballs_file extract DWAgent",20);
        $tempdir    = $unix->TEMP_DIR();
        $destfile   = "$tempdir/dwagent.sh";
        if(!is_dir("/usr/local/sbin")){@mkdir("/usr/local/sbin",0755,true);}
        if(is_file("$destfile")){@unlink($destfile);}
        system("$tar xf $tarballs_file -C $tempdir/");
        @unlink($tarballs_file);
        if(!is_file($destfile)){
            build_progress("$tarballs_file extract DWAgent {failed}",110);
            return false;
        }
        build_progress("$tarballs_file extract DWAgent {success}",50);
        if(is_file("/usr/local/sbin/dwagent")){@unlink("/usr/local/sbin/dwagent");}
        if(!@copy($destfile,"/usr/local/sbin/dwagent")){
            build_progress("$destfile unable to install",110);
            return false;
        }

        @chmod("/usr/local/sbin/dwagent",0755);
        build_progress("DWAgent {success}",100);
        return true;

    }

    $ASHOTFIX=false;
    $HOTFIXVER="";
	if(preg_match("#([0-9]+)-([0-9]+)\.tgz$#",$filename,$r)){
        $ASHOTFIX=true;
        $HOTFIXVER="$r[1]-$r[2]";
    }

	
	if (preg_match('#([0-9\.]+)_([0-9\.]+)-([0-9]+).tgz$#i',$filename,$r)){
		$CUR_BRANCH=@file_get_contents("$ARTICA_BASE/MAIN_RELEASE");
		$CUR_BRANCH=trim($CUR_BRANCH);
		
		echo "Patch....................: {$r[3]}\n";
		echo "From.....................: {$r[1]}\n";
		echo "To.......................: {$r[2]}\n";
		echo "Current Branch..........: $CUR_BRANCH\n";
		if($CUR_BRANCH<>$r[1]){
			echo "$CUR_BRANCH != {$r[1]}\n";
			build_progress("{not_for_current_branch} {requested} {$r[1]}",110);
			return false;
		}
		$PATCH_VER=$r[2]." :";
		$ASPATCH=true;
	}
	
	echo "Size....................: ".FormatBytes($size/1024)."\n";
	echo "Current version.........: $ORGV\n";
		
	build_progress("{analyze}...",10);
		
	echo "Current system..........: $LINUX_CODE_NAME $LINUX_DISTRIBUTION {$LINUX_VERS[0]}/{$LINUX_VERS[1]} $LINUX_ARCHITECTURE\n";
	echo "Package.................: $filename\n";
	echo "Temp dir................: $TMP_DIR\n";
	echo "Apache User.............: $APACHEUSER\n";
	
	
	
	if(!is_file($tarballs_file)){
		echo "$tarballs_file no such file...\n";
		build_progress("No such file...",110);
		return false;
	}
	echo "Checking $tarballs_file...\n";

	if(strpos($tarballs_file,")")>0){
        echo "Checking $tarballs_file remove parentheses...\n";
	    $SrcTar=$tarballs_file;
        $tarballs_file=str_replace("(","",$tarballs_file);
        $tarballs_file=str_replace(")","",$tarballs_file);
        $tarballs_file=str_replace(" ","",$tarballs_file);
        if(is_file($tarballs_file)){@unlink($tarballs_file);}
        @copy($SrcTar,$tarballs_file);
        @unlink($SrcTar);
    }


    $tarballs_file=$unix->shellEscapeChars($tarballs_file);
	
	$chown=$unix->find_program("chown");
	$chmod=$unix->find_program("chmod");

	$rm=$unix->find_program("rm");
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();

    build_progress("{analyze} ".basename($tarballs_file),50);

    if(!$unix->TARGZ_TEST_CONTAINER($tarballs_file,false,true)){
        echo "Starting......: ".date("H:i:s")." Testing Package ".basename($tarballs_file)." failed\n";
        @unlink($tarballs_file);
        build_progress("Corrupted!",110);
        exit;
    }
    echo "Starting......: ".date("H:i:s")." Purge directories\n";
    build_progress("Purge directories",51);

    if(is_dir("$RESS_BASE/conf/meta/hosts/uploaded")){
        system("$rm -f $RESS_BASE/conf/meta/hosts/uploaded/*");
    }
    build_progress("{extracting}...{please_wait}",54);
    exec("$tar xpf $tarballs_file -C /usr/share/ 2>&1",$results);

	echo "Removing $tarballs_file...\n";
	@unlink($tarballs_file);
	shell_exec("$rm -rf $RESS_BASE/conf/upload/*");
	build_progress("{apply_permissions}...",55);

    if($ASHOTFIX) {
        squid_admin_mysql(1, "New Hotfix $HOTFIXVER updated to main core program", null, __FILE__, __LINE__);
    }
    Update_history();
	echo "$APACHEUSER -> $ARTICA_BASE\n";
	shell_exec("$chown -R $APACHEUSER $ARTICA_BASE");
	echo "0755 -> $ARTICA_BASE\n";
	shell_exec("$chmod -R 0755 $ARTICA_BASE");
	$ORGD=@file_get_contents("$ARTICA_BASE/VERSION");
    shell_exec("$nohup /usr/sbin/artica-phpfpm-service -permission-watch >/dev/null 2>&1 &");

    echo "Old version.............: $ORGV\n";
	if($ASPATCH){$patched=" (patched)";}
	echo "Current version.........: $ORGD$patched\n";
	sleep(2);
	if($ORGV==$ORGD){
		build_progress("{same_version} $PATCH_VER$filename...",100);
		return false;
	}
	
	build_progress("{restarting} Artica...",62);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/register/server");
	build_progress("{restarting} Artica...",65);
	build_progress("{building_init_scripts}...",70);
	system("$php $ARTICA_BASE/exec.initslapd.php");
	build_progress("{updating_network}...",75);
	system("$php $ARTICA_BASE/exec.virtuals-ip.php");
	system("$php $ARTICA_BASE/exec.monit.php --build");
	echo "Starting......: ".date("H:i:s")." Purge and clean....\n";
	build_progress("{restarting} Artica...",80);
	if(is_file("/etc/init.d/nginx")){shell_exec("$nohup /etc/init.d/nginx reload >/dev/null 2>&1 &");}
	
	echo "Starting......: ".date("H:i:s")." Restarting Artica....\n";
	build_progress("{restarting} Artica...",81);


	build_progress("{restarting} Artica...",83);
	echo "Starting......: ".date("H:i:s")." Restarting Process1....\n";
	shell_exec("$nohup /usr/bin/php $ARTICA_BASE/exec.status.php --process1 --force --verbose ".time()."");
	build_progress("{restarting} Artica...",85);
	
	echo "Starting......: ".date("H:i:s")." Restarting Monit....\n";
	shell_exec("$nohup /etc/init.d/monit restart >/dev/null 2>&1 &");
	build_progress("{restarting} {APP_MONIT}...",86);
	
	echo "Starting......: ".date("H:i:s")." Restarting Artica status....\n";
	shell_exec("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
	build_progress("{restarting} {artica_status}...",87);
    shell_exec("/etc/init.d/artica-ad-rest restart >/dev/null 2>&1 &");
	
	echo "Starting......: ".date("H:i:s")." Restarting Scheduler....\n";
	if(is_file("/etc/init.d/squid")) {
        shell_exec("$nohup $php $ARTICA_BASE/exec.squid.php --build-schedules >/dev/null 2>&1 &");
    }
	build_progress("{restarting} Artica...",88);
	shell_exec("$nohup $php $ARTICA_BASE/exec.schedules.php --defaults >/dev/null 2>&1 &");
	build_progress("{restarting} Artica...",90);
    shell_exec("$nohup $php $ARTICA_BASE/exec.verif.packages.php >/dev/null 2>&1 &");
	build_progress("{restarting} Artica...",100);
	echo "Starting......: ".date("H:i:s")." Done you can close the screen....\n";

    if(is_file($pidfile)){@unlink($pidfile);}
	die();
	
}
function Update_history():bool{
    $PVER=0;
    $CURVER=trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
    if(is_file("/usr/share/artica-postfix/SP/$CURVER")) {
        $PVER = intval(@file_get_contents("/usr/share/artica-postfix/SP/$CURVER"));
    }

    $CurrentHotFix=CurrentHotFix();
    if(strlen($CurrentHotFix)>2){
        if($PVER>0) {
            $PVER = "Service Pack $PVER and HotFix $CurrentHotFix";
        }else{
            $PVER="HotFix $CurrentHotFix";
        }
    }

    $q=new lib_sqlite("/home/artica/SQLITE/nightly.db");
    $sql="CREATE TABLE IF NOT EXISTS `history` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`version` text UNIQUE, `updated` INTEGER,`asseen` integer ,`xline` integer)";
    $q->QUERY_SQL($sql);
    $time=time();
    $line=__LINE__;
    $q->QUERY_SQL("INSERT OR IGNORE INTO history (version,updated,asseen,xline) VALUES ('$CURVER $PVER','$time',0,$line)");
    return true;
}

function CurrentHotFix():string{

    $f=explode("\n",@file_get_contents("/usr/share/artica-postfix/fw.updates.php"));
    foreach ($f as $line){
        if(preg_match("#GLOBALS\[.*?HOTFIX.*?\].*?([0-9\-]+)#",$line,$re)){
            return $re[1];
        }
    }
    return "";
}


function build_progress($text,$pourc):bool{
	$unix=new unix();
    $unix->framework_progress($pourc,$text,"artica.install.progress");
    return true;
}