<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.assp-multi.inc');
include_once(dirname(__FILE__) . '/ressources/class.maincf.multi.inc');

$GLOBALS["NOSTART"]=false;
$_GET["LOGFILE"]=PROGRESS_DIR."/interface-postfix.log";
if(preg_match("#--nostart#",implode(" ",$argv))){$GLOBALS["NOSTART"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}

$unix=new unix();
$GLOBALS["postmulti"]=$unix->find_program("postmulti");
$GLOBALS["postconf"]=$unix->find_program("postconf");
$GLOBALS["postmap"]=$unix->find_program("postmap");
$GLOBALS["postalias"]=$unix->find_program("postalias");
$GLOBALS["postfix"]=$unix->find_program("postfix");

if($argv[1]=="--all-syslog"){build_syslog_all();exit;}
if($argv[1]=='--syslog'){build_syslog($argv[2]);exit;}
if($argv[1]=='--status'){instance_status($argv[2]);exit;}
if($argv[1]=='--install'){PostfixMultiEnable();exit();}
if($argv[1]=='--removes'){PostfixMultiDisable();exit();}
if($argv[1]=='--uninstall'){PostfixMultiDisable();exit();}
if($argv[1]=='--reconfigure-all'){reconfigure();exit();}
if($argv[1]=='--restart-all'){restart_all_instances();exit();}
if($argv[1]=='--instance-memory'){reconfigure_instance_tmpfs($argv[2],$argv[3]);exit();}
if($argv[1]=='--instance-memory-kill'){reconfigure_instance_tmpfs_umount($argv[2]);exit();}
if($argv[1]=='--destroy'){DestroyInstance($argv[2]);exit();}
if($argv[1]=='--instance-start'){start_instance($argv[2]);exit();}
if($argv[1]=='--instances-enable'){reconfigure_instances_enable();exit;}
if($argv[1]=='--instance-stop'){stop_instance($argv[2]);exit;}
if($argv[1]=='--instance-status'){echo status_instance($argv[2]);exit;}
if($argv[1]=='--instance-restart'){restart_instance($argv[2]);exit;}
if($argv[1]=='--instance-install'){instance_install($argv[2]);exit;}
if($argv[1]=='--instance-uninstall'){instance_uninstall($argv[2]);exit;}
if($argv[1]=='--instances-list'){print_r(InstancesList_production());exit;}
if($argv[1]=="--instance-reinstall"){instance_reinstall($argv[2]);exit;}
if($argv[1]=='--interface-change'){interface_change($argv[2],$argv[3]);exit;}
if($argv[1]=="--reconfigure-instance"){reconfigure_instance($argv[2]);exit;}
if($argv[1]=="--sync-cron"){synchronize_cron();exit;}

$sock=new sockets();
$GLOBALS["EnablePostfixMultiInstance"]=$sock->GET_INFO("EnablePostfixMultiInstance");
if($GLOBALS["EnablePostfixMultiInstance"]<>1){
		echo "Starting......: ".date("H:i:s")." Multi-instances is not enabled ({$GLOBALS["EnablePostfixMultiInstance"]})\n";
		PostfixMultiDisable();
		exit();
}
$unix=new unix();

	echo "Starting......: ".date("H:i:s")." Enable Postfix multi-instances\n";
	
	$pidfile="/etc/artica-postfix/".basename(__FILE__)." ". md5(implode("",$argv)).".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		echo "Starting......: ".date("H:i:s")." multi-instances configurator already executed PID $pid\n";
		exit();
	}

	$pid=getmypid();
	echo "Starting......: ".date("H:i:s")." Postfix multi-instances configurator running $pid\n";
	file_put_contents($pidfile,$pid);	


writelogs("receive ". implode(",",$argv),"MAIN",__FILE__,__LINE__);



if($argv[1]=='--instance-reconfigure'){exit();}
if($argv[1]=='--clean'){remove_old_instances();exit();}
if($argv[1]=='--from-main-maincf'){exit();}
if($argv[1]=='--instance-start'){start_instance($argv[2]);exit();}
if($argv[1]=='--reload-all'){CheckInstances();exit();}

reconfigure();

function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/HEADER_CHECK";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_syslog_all(){
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $results=$q->QUERY_SQL("SELECT ID FROM postfix_instances ORDER BY instancename");
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        build_syslog($ID);
    }
}

function build_syslog($ID){
    $unix = new unix();
    $conf = "/etc/rsyslog.d/postfix-instance$ID.conf";
    $md5_start = null;
    if (is_file($conf)) {
        $md5_start = md5_file($conf);
    }
    $h[] = "if  (\$programname =='postfix-instance$ID') then {";
    $h[] = BuildRemoteSyslogs("postfix");
    $h[] = BuildRemoteSyslogs("postfix-instance$ID");
    $h[] = buildlocalsyslogfile("/var/log/postfix-instance$ID.log");
    $h[] = buildlocalsyslogfile("/var/log/mail.log");
    $h[] = "& stop";
    $h[] = "}";
    @file_put_contents($conf, @implode("\n", $h));
    $md5_end = md5_file($conf);
    if ($md5_end <> $md5_start) {
        $unix->RESTART_SYSLOG(true);
    }

}

function restart_all_instances_progress($prc,$text):bool{
    $unix=new unix();
    $unix->framework_progress($prc,$text,"postfix.restart.all.progress");
    return true;
}

function restart_all_instances():bool{
	$unix=new unix();
	$postfix=$unix->find_program("postfix");
	$GLOBALS["postmulti"]=$unix->find_program("postmulti");

    $c=1;
    restart_all_instances_progress($c++,"{stopping}");
    $InstancesList_production=InstancesList_production();
    _out("Stopping instances...". count($InstancesList_production));
    foreach ($InstancesList_production as $instance_id=>$instance_path){
        restart_all_instances_progress($c++,"{stopping} $instance_path");
        stop_instance($instance_id);

    }
    if($c>80){$c=80;}

	echo "Starting......: ".date("H:i:s")." Stopping master instance\n";
	system("$postfix stop");
    restart_all_instances_progress($c++,"Sync CRON");
    synchronize_cron();
    restart_all_instances_progress($c++,"Sync Monit");
    synchronize_monit();

    $pids=$unix->PIDOF_PATTERN_ALL("/usr/lib/postfix/master");
    foreach ($pids as $pid){
        restart_all_instances_progress($c++,"{stopping} /lib/postfix/master pid:$pid");
        if($c>80){$c=80;}
        $unix->KILL_PROCESS($pid,9);
    }

    $cmds_kill[]="qmgr -l -t";
    $cmds_kill[]="pickup -l -t";
    $cmds_kill[]="showq -t";
    foreach ($cmds_kill as $cmd){
        if($c>80){$c=80;}
        restart_all_instances_progress($c++,"{stopping} {search} $cmd");
        $pids=$unix->PIDOF_PATTERN_ALL($cmd);
        foreach ($pids as $pid){
            if($c>80){$c=80;}
            restart_all_instances_progress($c++,"{stopping}$cmd :$pid");
            $unix->KILL_PROCESS($pid,9);
        }
    }

    if($c>80){$c=80;}
    restart_all_instances_progress($c++,"{stopping} checking first instance security");
	echo "Starting......: ".date("H:i:s")." checking first instance security\n";
    system("$postfix -c /etc/postfix set-permissions >/dev/null 2>&1");
    system("$postfix start");

    foreach ($InstancesList_production as $instance_id=>$instance_path) {
        if($c>90){$c=90;}
        restart_all_instances_progress($c++,"{starting} instance $instance_id");
        system("$postfix -c $instance_path set-permissions >/dev/null 2>&1");
        start_instance($instance_id);
    }
    
    restart_all_instances_progress(100,"{starting} SMTP service success");
	return true;
}

function _out($text):bool{
    $text=trim($text);
    if($text==null){return true;}
    echo "Starting......: ".date("H:i:s")." $text\n";
    if(!function_exists("openlog")){return false;}

    if(!isset($GLOBALS["INSTANCE_ID"])){
        $trace=@debug_backtrace();
        $logs=parse_traceback($trace,"No Instance ID!");
        openlog("postfix-instance-unknown/postfix", LOG_PID , LOG_SYSLOG);
        echo "Starting......: ".date("H:i:s")." $text\n";
        syslog(LOG_INFO, $text);
        foreach ($logs as $text){
            echo "Starting......: ".date("H:i:s")." $text\n";
            syslog(LOG_INFO, $text);
        }
        return true;
    }

    openlog("postfix-instance{$GLOBALS["INSTANCE_ID"]}/postfix", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}

function parse_traceback($traces,$text_principal):array{
    $f=array();
        foreach ($traces as $traceback){
            $method="";
            if(is_array($traceback)){
                foreach ($traceback as $a=>$b){
                    try {
                        if(is_object($a) OR is_object($b)){continue;}
                        if(is_array($b)){
                            foreach ($b as $d=>$e){
                                $method = $method . " $a = > $d => $e";
                            }
                            continue;
                        }

                        $method = $method . " $a = > $b";
                    } catch (Exception $e) {
                        continue;
                    }
                }
            }
            $filename=$traceback["file"];
            $function=$traceback["function"];
            $line=$traceback["line"];
            $f[]="[$text_principal]: Called by $filename $function line  $line - $method";
        }

        return $f;
}

function reconfigure(){
    _out("Initialize...");
    PostfixMultiProgress("{reconfiguring}",10);
    shlib_directory();
    PostfixMultiProgress("{reconfiguring}",20);
    exec("{$GLOBALS["postmulti"]} -e init 2>&1",$results);
    foreach ($results as $line){
        _out("Initialize: $line");
    }
    PostfixMultiProgress("{reconfiguring}",30);
	remove_old_instances();
    PostfixMultiProgress("{reconfiguring}",40);
    reconfigure_instances_enable();
    PostfixMultiProgress("{reconfiguring}",50);
	CheckInstances();
    PostfixMultiProgress("{reconfiguring} {success}",100);
	
}
function clean_instance_maincf($path):bool{
    $newline=array();
    echo "Starting......: ".date("H:i:s")." Cleaning:[$path]\n";
    $f=explode("\n",@file_get_contents($path));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^\##",$line)){continue;}
        if(preg_match("#^PATH=#",$line)){continue;}
        if(preg_match("#^ddd\s+#",$line)){continue;}
        $newline[]=$line;
    }
    $newline[]="";
    @file_put_contents($path,@implode("\n",$newline));
    return true;
}

function InstancesList_production():array{
    $INSTANCES=array();
    $multi_instance_directories=null;
    $f=explode("\n",@file_get_contents("/etc/postfix/main.cf"));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#multi_instance_directories.*?=(.+)#",$line,$re)){continue;}
        if($GLOBALS["VERBOSE"]){echo "Found $re[1]\n";}
        $multi_instance_directories=trim($re[1]);

    }
    if($multi_instance_directories==null){
        if($GLOBALS["VERBOSE"]){echo "multi_instance_directories Not found or empty\n";}
        return array();}
    $tt=explode(" ",$multi_instance_directories);
    foreach ($tt as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#postfix-instance([0-9]+)#",$line,$re)){continue;}
        if($GLOBALS["VERBOSE"]){echo "multi_instance_directories: Found Instance $line\n";}
        $INSTANCES[$re[1]]=$line;
    }
    return $INSTANCES;
}

function InstancesList():array{
	$unix=new unix();
    $MAIN=array();
	if($GLOBALS["postmulti"]==null){
		$GLOBALS["postmulti"]=$unix->find_program("postmulti");
	}
	if(is_dir("/etc/postfix-hub")){
		if(!is_file("/etc/postfix-hub/dynamicmaps.cf")){@file_put_contents("/etc/postfix-hub/dynamicmaps.cf","#");}
	}
    if(isset($GLOBALS["INSTANCE"])){unset($GLOBALS["INSTANCE"]);}
    $sen["n"]=0;
    $sen["y"]=1;
    if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["postmulti"]} -l -a\n";}
	exec("{$GLOBALS["postmulti"]} -l -a 2>&1",$results);
	foreach ($results as $ligne){
        $ligne=trim($ligne);
        if($ligne==null){continue;}
        if(preg_match("#warning:\s+\/#",$ligne)){continue;}

        if(preg_match("#fatal:\s+(.+?)\/main\.cf,.*?after attribute name#",$ligne,$re)){
            clean_instance_maincf("$re[1]/main.cf");
        }
        if(preg_match("#fatal.*?open\s+.*?main\.cf.*?No such file or directory#",$ligne)){
            multi_instance_directories();
            continue;
        }

        echo "Starting......: ".date("H:i:s")." [$ligne]\n";

		if(preg_match("#^(.+?)\s+(.+?)\s+(y|n)\s+#",$ligne,$re)){
			$re[1]=trim($re[1]);
			if($re[1]=='-'){continue;}
            $enabled=$sen[trim($re[3])];
			echo "Starting......: ".date("H:i:s")." Detecting instance [{$re[1]}]={$re[3]}/$enabled\n";
			$MAIN[$re[1]]=$enabled;
			
			
		}
	}

	//$tmpstr=$unix->FILE_TEMP();
	//shell_exec("{$GLOBALS["postmulti"]} -p status >$tmpstr 2>&1");
	//echo @file_get_contents($tmpstr);
    return $MAIN;
}


function shlib_directory():bool{
    $unix=new unix();
    $cp=$unix->find_program("cp");
    if(!is_dir("/usr/local/lib/postfix")){
        @mkdir("/usr/local/lib/postfix",0755,true);
    }
    shell_exec("$cp -rf /usr/lib/postfix/libpostfix-* /usr/local/lib/postfix/");
    shell_exec("$cp -rf /usr/lib/postfix/postfix-*.so /usr/local/lib/postfix");
    $unix->POSTCONF_SET("shlib_directory","/usr/local/lib/postfix");
    return true;
}



function instance_install_progress($prc,$text,$ID):bool{
    $instancename = "postfix-instance$ID";
    $unix=new unix();
    _out("$instancename: $text");
    $unix->framework_progress($prc,$text,"postfix-multi.$ID.install.progress");
    return true;
}
function multi_instance_directories():bool{
    $unix=new unix();
    $sql="SELECT * FROM postfix_instances";
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $results=$q->QUERY_SQL($sql);
    $multi_instance_directories=array();

    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $path="/etc/postfix-instance$ID";
        if(!is_dir($path)){
            echo "$path no such directory, skip it...\n";
            continue;}
        $multi_instance_directories[]=$path;
    }

    $postconf=$unix->find_program("postconf");
    $final=@implode(" ",$multi_instance_directories);
    shell_exec("$postconf -e \"multi_instance_directories=$final\" 2>&1");
    return true;
}
function interface_change_progress($prc,$text,$ID){
    $instancename = "postfix-instance$ID";
    $unix=new unix();
    _out("$instancename: $text");
    $unix->framework_progress($prc,$text,"postfix-multi.$ID.interface.progress");
}
function interface_change($ID,$Interface):bool{
    if(intval($ID)==0){
        echo "Interface ID is null ($ID)!\n";
        interface_change_progress(110,"{interface} {failed}",$ID);
        return false;
    }
    $GLOBALS["INSTANCE_ID"]=intval($ID);
    $unix=new unix();
    $InterfaceIP=$unix->InterfaceToIPv4($Interface);
    if($InterfaceIP==null){
        interface_change_progress(110,"{interface} {failed}",$ID);
        return false;
    }
    if($InterfaceIP=="0.0.0.0"){
        interface_change_progress(110,"{interface} {failed}",$ID);
        return false;
    }

    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $q->QUERY_SQL("UPDATE postfix_instances SET interface='$Interface' WHERE ID=$ID");
    if(!$q->ok){
        interface_change_progress(110,"{interface} {failed}",$ID);
        return false;
    }
    interface_change_progress(20,"{interface} $Interface",$ID);

    $unix->POSTCONF_SET("inet_interfaces",$InterfaceIP,$ID);
    interface_change_progress(50,"{restarting}...",$ID);
    restart_instance($ID);
    interface_change_progress(100,"{restarting} {success}...",$ID);
    return true;

}
function instance_reinstall_progress($prc,$text,$ID):bool{
    $instancename = "postfix-instance$ID";
    $unix=new unix();
    _out("$instancename: $text");
    $unix->framework_progress($prc,$text,"postfix-multi.$ID.reinstall.progress");
    return true;
}
function _out_results($results):bool{
    foreach ($results as $line){
        if(preg_match("#warning:#",$line)){continue;}
        _out("$line");
    }
    return true;
}
function instance_reinstall($ID):bool{
    if(intval($ID)==0){
        instance_reinstall_progress(110,"{uninstalling} {failed}",$ID);
        return false;
    }
    $unix=new unix();
    $GLOBALS["INSTANCE_ID"]=intval($ID);
    instance_reinstall_progress(20,"{uninstalling}...",$ID);
    $instancename = "postfix-instance$ID";
    $spooldir="/var/spool/postfix-instance$ID";
    exec("{$GLOBALS["postmulti"]} -i $instancename -p stop 2>&1",$results);
    _out_results($results);$results=array();
    instance_reinstall_progress(30,"{uninstalling}...",$ID);
    exec("{$GLOBALS["postmulti"]} -i $instancename -e disable 2>&1",$results);
    _out_results($results);$results=array();
    instance_reinstall_progress(40,"{uninstalling}...",$ID);
    $php=$unix->LOCATE_PHP5_BIN();
    if(is_dir($spooldir)){
        $subdirs[]="active";
        $subdirs[]="bounce";
        $subdirs[]="corrupt";
        $subdirs[]="defer";
        $subdirs[]="deferred";
        $subdirs[]="flush";
        $subdirs[]="hold";
        $subdirs[]="incoming";
        $subdirs[]="maildrop";
        $subdirs[]="pid";
        $subdirs[]="private";
        $subdirs[]="public";
        $subdirs[]="saved";
        $subdirs[]="trace";
        foreach ($subdirs as $directory) {
            $TDir="$spooldir/$directory";
            if(!is_dir($TDir)){continue;}
            instance_reinstall_progress(50, "{uninstalling} $directory...", $ID);
            $rm = $unix->find_program("rm");
            shell_exec("$rm -rf $TDir/*");
        }
    }
    exec("{$GLOBALS["postmulti"]} -i $instancename -e destroy 2>&1",$results);
    _out_results($results);$results=array();
    instance_reinstall_progress(60,"{installing}...",$ID);
    exec("{$GLOBALS["postmulti"]} -I $instancename -e create 2>&1",$results);
    _out_results($results);$results=array();
    instance_reinstall_progress(70,"{installing}...",$ID);
    CleanMainCF($ID);
    instance_reinstall_progress(75,"{installing}...",$ID);
    reconfigure_instance_inet_interfaces($ID);
    instance_reinstall_progress(70,"{installing}...",$ID);
    shell_exec("{$GLOBALS["postmulti"]} -I $instancename -e enable >/dev/null 2>&1");
    _out_results($results);$results=array();
    instance_reinstall_progress(75,"{reconfiguring} (global)...",$ID);
    shell_exec("$php /usr/share/artica-postfix/exec.postfix.maincf.php --instance-id=$ID");
    instance_reinstall_progress(76,"{reconfiguring} (transport)...",$ID);
    shell_exec("$php /usr/share/artica-postfix/exec.postfix.transport.php --instance-id=$ID");
    instance_reinstall_progress(80,"{starting}...",$ID);
    exec("{$GLOBALS["postmulti"]} -i $instancename -p start 2>&1",$results);
    instance_reinstall_progress(85,"{reloading}...",$ID);
    exec("{$GLOBALS["postmulti"]} -i $instancename -p reload 2>&1",$results);
    _out_results($results);$results=array();
    instance_reinstall_progress(100,"{success}...",$ID);
    return true;
}

function instance_uninstall($ID):bool{
    if(intval($ID)==0){
        instance_install_progress(110,"{uninstalling} {failed}",$ID);
        return false;
    }
    $GLOBALS["INSTANCE_ID"]=intval($ID);
    $unix=new unix();
    _out("Uninstalling Instance $ID");
    instance_install_progress(15,"{uninstalling}",$ID);
    $instancename = "postfix-instance$ID";
    $instancekey="instance$ID";
    DestroyInstance($instancename);
    $instance_path="/etc/postfix-instance$ID";
    if(is_dir($instance_path)){
        $rm=$unix->find_program("rm");
        shell_exec("$rm -rf $instance_path");
    }
    instance_install_progress(80,"{cleaning}...",$ID);
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $q->QUERY_SQL("DELETE FROM postfix_instances WHERE id='$ID'");
    $q->QUERY_SQL("DELETE FROM postfix_params WHERE uuid='$instancekey'");
    instance_install_progress(100,"{success}...",$ID);
    multi_instance_directories();
    return true;
}

function instance_install($ID):bool{
    $GLOBALS["INSTANCE_ID"]=$ID;
    $unix=new unix();
    _out("Installing Instance $ID");
    instance_install_progress(15,"{installing}",$ID);
    $instancename = "postfix-instance$ID";
    $instance_path="/etc/postfix-instance$ID";

    $postmulti=$unix->POSTCONF_MULTI_BIN();
    $unix->framework_exec("exec.postfix.maincf.php --clean-main");
    echo "$postmulti -I $instancename -e create\n";
    exec("$postmulti -I $instancename -e create 2>&1",$results);
    foreach ($results as $line){
        instance_install_progress(30,$line,$ID);
    }
    $CurInstances=InstancesList();
    if(!isset($CurInstances[$instancename])){
        instance_install_progress(110,"{failed}",$ID);
        return false;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $ligne=$q->mysqli_fetch_array("SELECT instancename,enabled from postfix_instances WHERE id='$ID'");

    $instancename=$ligne["instancename"];
    $enabled=intval($ligne["enabled"]);
    instance_install_progress(50,$line,$ID);
    instance_cron($ID);
    if($enabled==1) {
        instance_install_progress(60,"$instancename: {enable}",$ID);
        shell_exec("$postmulti -i $instancename -e enable");
    }
    $unix->framework_exec("exec.postfix.maincf.php --clean-main --instance-id=$ID");
    multi_instance_directories();
    clean_instance_maincf("$instance_path/main.cf");
    instance_install_progress(100,"{success}",$ID);
    return true;
}

function CheckInstances(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	if($unix->process_exists(@file_get_contents($pidfile))){
		_out("Check Instances function already executed PID ". @file_get_contents($pidfile));
		exit();
	}
        $c=50;
        $postmulti=$unix->find_program("postmulti");
		$pid=getmypid();
        _out("Check Instances configurator running $pid");
		file_put_contents($pidfile,$pid);		
	    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $results=$q->QUERY_SQL("SELECT * FROM postfix_instances WHERE enabled=1");

        PostfixMultiProgress("{reconfiguring}",$c++);
        $CurInstances=InstancesList();
        foreach ($CurInstances as $instance_name=>$enabled){
            _out("Current instance \"$instance_name\" enabled=$enabled");
        }

		foreach ($results as $index=>$ligne){
            $ID=$ligne["ID"];
            if($c>70){$c=70;}
            $instancetext=$ligne["instancename"];
            PostfixMultiProgress("{reconfiguring} $instancetext",$c++);
            _out("$instancetext: Checking instance $instancetext ($ID)");
            $instancename="postfix-instance{$ID}";

            if(!isset($CurInstances[$instancename])){
                _out("$instancetext:  Creating a new instance id:$ID $instancetext - $instancename");
                if(!instance_install($ID)){continue;}

                $CurInstances=InstancesList();

            }
            if(!isset($CurInstances[$instancename])){
                _out("$instancetext: Failed to find instance $instancename");
                continue;
            }
            _out("$instancetext: reconfiguring...");
           ConfigureMainCF($ID);
		}
    PostfixMultiProgress("{reconfiguring}",$c++);
    reconfigure_instances_enable();
    PostfixMultiProgress("{reconfiguring} $instancetext",$c++);
	@unlink($pidfile);
}

function reconfigure_instances_enable(){
    $unix=new unix();
    $postmulti=$unix->find_program("postmulti");
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $results=$q->QUERY_SQL("SELECT * FROM postfix_instances");
    $CurrentInstances=InstancesList();
    foreach ($results as $index=>$ligne) {
        $ID = $ligne["ID"];
        $enabled=$ligne["enabled"];
        $instanceText = $ligne["instancename"];
        _out("Checking if instance $instanceText ($ID) is enabled");
        $instancename = "postfix-instance{$ID}";
        if(!isset($CurrentInstances[$instancename])){continue;}
        if($enabled==1){
            if($CurrentInstances[$instancename]==0){
                _out("Enabling instance $instanceText ($ID)...");
                shell_exec("$postmulti -i $instancename -e enable");
            }
            continue;
        }
        if($enabled==0){
            if($CurrentInstances[$instancename]==1){
                out("Disabling instance $instanceText ($ID)...");
                shell_exec("$postmulti -i $instancename -e disable");
            }
            continue;
        }


    }


}






function reconfigure_instance_tmpfs($hostname,$mem){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".".$hostname.".pid";
	if($unix->process_exists(@file_get_contents($pidfile))){
		echo "Starting......: ".date("H:i:s")." multi-instances configurator already executed PID ". @file_get_contents($pidfile)."\n";
		exit();
	}

	$pid=getmypid();
	echo "Starting......: ".date("H:i:s")." Postfix multi-instances configurator running $pid\n";
	file_put_contents($pidfile,$pid);		
	
	if(!is_numeric($mem)){
		echo "Starting......: ".date("H:i:s")." Postfix multi-instances Memory set \"$mem\" is not an integer\n";
		return;
	}
	if($mem<5){return null;}
	$directory="/var/spool/postfix-$hostname";
	if($hostname=="master"){$directory="/var/spool/postfix";}
		
	$MOUNTED_TMPFS_MEM=$unix->MOUNTED_TMPFS_MEM($directory);
	if($MOUNTED_TMPFS_MEM>0){
		echo "Starting......: ".date("H:i:s")." Postfix \"$hostname\" mounted memory $mem/{$MOUNTED_TMPFS_MEM}MB\n";
		if($mem>$MOUNTED_TMPFS_MEM){$diff=$mem-$MOUNTED_TMPFS_MEM;}
		if($mem<$MOUNTED_TMPFS_MEM){$diff=$MOUNTED_TMPFS_MEM-$mem;}
		if($diff>20){
			echo "Starting......: ".date("H:i:s")." Postfix \"$hostname\" diff={$diff}M\"\n"; 
			reconfigure_instance_tmpfs_umount($hostname);
			reconfigure_instance_tmpfs_mount($hostname,$mem);
		}
		
	}else{
		echo "Starting......: ".date("H:i:s")." Postfix \"$hostname\" directory is not mounted has tmpfs\n";
		reconfigure_instance_tmpfs_mount($hostname,$mem);
		
	}
	
	@unlink($pidfile);

}

function reconfigure_instance_tmpfs_mount($hostname,$mem){
		$unix=new unix();
		$directory="/var/spool/postfix-$hostname";
		if($hostname=="master"){$directory="/var/spool/postfix";}
		
		
		$MOUNTED_TMPFS_MEM=$unix->MOUNTED_TMPFS_MEM($directory);
		if($MOUNTED_TMPFS_MEM>0){
			echo "Starting......: ".date("H:i:s")." Postfix \"$hostname\" Already mounted\n";
			return;
		}
		
		
		$mount=$unix->find_program("mount");
		@mkdir("/var/spool/backup/postfix-$hostname",0755,true);
		echo "Starting......: ".date("H:i:s")." Postfix \"$hostname\" backup $directory\n";
		shell_exec("/bin/cp -pr $directory/* /var/spool/backup/postfix-$hostname/");
		shell_exec("/bin/rm -rf $directory/*");
		echo "Starting......: ".date("H:i:s")." Postfix \"$hostname\" mounting $directory\n";
		$cmd="$mount -t tmpfs -o size={$mem}M tmpfs \"$directory\"";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		exec("$cmd");
		$MOUNTED_TMPFS_MEM=$unix->MOUNTED_TMPFS_MEM($directory);
		if($MOUNTED_TMPFS_MEM>0){
			echo "Starting......: ".date("H:i:s")." Postfix \"$hostname\" mounted memory $mem/{$MOUNTED_TMPFS_MEM}MB\n";	
		}else{
			echo "Starting......: ".date("H:i:s")." Postfix \"$hostname\" mounted memory FAILED\n";
				
		}	
		
	shell_exec("/bin/cp -pr /var/spool/backup/postfix-$hostname/* $directory/");
	shell_exec("/bin/rm -rf /var/spool/backup/postfix-$hostname");					
	
}

function reconfigure_instance_tmpfs_umount($hostname){
		$directory="/var/spool/postfix-$hostname";
		if($hostname=="master"){$directory="/var/spool/postfix";}
		$results=array();
		$unix=new unix();
		$umount=$unix->find_program("umount");
		if($GLOBALS["UMOUNT_COUNT"]==0){
			@mkdir("/var/spool/backup/postfix-$hostname",0755,true);
			echo "Starting......: ".date("H:i:s")." Postfix \"$hostname\" backup files and directories.\n";
			shell_exec("/bin/cp -pr $directory/* /var/spool/backup/postfix-$hostname/ >/dev/null 2>&1");
			shell_exec("/bin/rm -rf $directory/*");
		}
		
		echo "Starting......: ".date("H:i:s")." Postfix \"$hostname\" stopping postfix\n";
		$cmd="{$GLOBALS["postmulti"]} -i postfix-$hostname -p stop >/dev/null 2>&1";
		if($hostname=="master"){$cmd="{$GLOBALS["postmulti"]} -i postfix-$hostname -p stop >/dev/null 2>&1";}
		
		shell_exec("{$GLOBALS["postmulti"]} -i postfix-$hostname -p stop >/dev/null 2>&1");
		
		$pids=trim(@implode(" ",$unix->LSOF_PIDS($directory)));
		if(strlen($pids)>2){
			echo "Starting......: ".date("H:i:s")." Postfix \"$hostname\" kill processes $pids\n";
			shell_exec("/bin/kill -9 $pids >/dev/null 2>&1");
		}
		
		
		$cmd="$umount -l \"$directory\"";
		
		
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		exec("$cmd 2>&1",$results);
		foreach ($results as $num=>$ligne){
			echo "Starting......: ".date("H:i:s")." Postfix \"$hostname\" $umount: $ligne\n"; 
		}
		
		$MOUNTED_TMPFS_MEM=$unix->MOUNTED_TMPFS_MEM($directory);
		if($MOUNTED_TMPFS_MEM==0){
			echo "Starting......: ".date("H:i:s")." Postfix \"$hostname\" umounted memory {$MOUNTED_TMPFS_MEM}MB\n";	
			
		}else{
			echo "Starting......: ".date("H:i:s")." Postfix \"$hostname\" failed to umount {$GLOBALS["UMOUNT_COUNT"]}/10\n";
			$GLOBALS["UMOUNT_COUNT"]=$GLOBALS["UMOUNT_COUNT"]+1;
			if($GLOBALS["UMOUNT_COUNT"]<20){
				reconfigure_instance_tmpfs_umount($hostname);
				return;
			}else{
				echo "Starting......: ".date("H:i:s")." Postfix \"$hostname\" timeout\n";
				shell_exec("/bin/cp -pr /var/spool/backup/postfix-$hostname/* $directory/ >/dev/null 2>&1");
				shell_exec("/bin/rm -rf /var/spool/backup/postfix-$hostname");
				return;	
			}
		}
}



function CleanMainCF($instanceid):bool{
    if($instanceid==0){return true;}
    $hostname="instance{$instanceid}";
    $maincf_path="/etc/postfix-$hostname/main.cf";
    $f=explode("\n",@file_get_contents($maincf_path));
    $newmain=array();
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^\##",$line)){continue;}
        if(preg_match("#^ddd\s+#",$line)){continue;}
        if(preg_match("#^PATH=#",$line)){continue;}
        $newmain[]=$line;
    }
    $newmain[]="# END CONFIG FILE\n\n";
    @file_put_contents($maincf_path,@implode("\n",$newmain));
    _out("Cleaning $maincf_path done");
    return true;

}
function ConfigureMainCF($instanceid,$nostart=false):bool{
	if($instanceid==0){return true;}

    $hostname="instance{$instanceid}";
    $instance_path="/etc/postfix-$hostname";
	if(!is_dir($instance_path)){@mkdir("$instance_path",0755,true);}
	if(!is_file("$instance_path/main.cf")){@file_put_contents("$instance_path/main.cf", "\n");}
	
	if(!is_file("$instance_path/dynamicmaps.cf")){
		echo "Starting......: ".date("H:i:s")." Postfix $hostname creating dynamicmaps.cf\n";
		@file_put_contents("$instance_path/dynamicmaps.cf","#");
	}
	
	reconfigure_instance_inet_interfaces($instanceid);
	echo "Starting......: ".date("H:i:s")." Postfix $hostname enable it into the Postfix main system\n";
	shell_exec("{$GLOBALS["postmulti"]} -i postfix-$hostname -e enable >/dev/null 2>&1");
	if(!$nostart){start_instance($instanceid);}
    return true;
}

function synchronize_cron():bool{
    $PRODUCTION_FILES=array();
    $files=scandir("/etc/cron.d");
    foreach ($files as $filename){
        if(!preg_match("#postfix-instance-status-([0-9]+)#",$filename,$re)){
            continue;
        }
        $PRODUCTION_FILES[$re[1]]="/etc/cron.d/$filename";

    }

    if(count($PRODUCTION_FILES)==0){return true;}
    $InstancesList_production=InstancesList_production();
    $RELOAD_MONIT=false;
    foreach ($PRODUCTION_FILES as $instance_id=>$cronfile){
        if(isset($InstancesList_production[$instance_id])){continue;}
        _out("Removing old cron task of instance ID $instance_id ($cronfile)");
        @unlink($cronfile);
        if(is_file("/etc/cron.d/postfix-postqueue-$instance_id")){
            _out("Removing old cron task (queue) of instance ID $instance_id ($cronfile)");
            @unlink("/etc/cron.d/postfix-postqueue-$instance_id");
        }
        $RELOAD_MONIT=true;

    }
    if(!$RELOAD_MONIT){return true;}
    _out("Reloading cron daemon...");
    $unix=new unix();
    $unix->go_exec("/etc/init.d/cron restart");
    return true;
}

function synchronize_monit():bool{
    $PRODUCTION_FILES=array();
    $files=scandir("/etc/monit/conf.d");
    foreach ($files as $filename){
        if(!preg_match("#postfix-instance([0-9]+).monitrc#",$filename,$re)){
            continue;
        }
        $PRODUCTION_FILES[$re[1]]="/etc/monit/conf.d/postfix-instance$re[1].monitrc";

    }
    if(count($PRODUCTION_FILES)==0){return true;}
    $InstancesList_production=InstancesList_production();
    $RELOAD_MONIT=false;
    foreach ($PRODUCTION_FILES as $instance_id=>$monitfile){
        if(isset($InstancesList_production[$instance_id])){continue;}
        _out("Removing old monitor of instance ID $instance_id ($monitfile)");
        @unlink($monitfile);
        $RELOAD_MONIT=true;

    }
    if(!$RELOAD_MONIT){return true;}
    _out("Reloading monitor daemon...");
    $unix=new unix();
    $unix->go_exec("/etc/init.d/monit restart");
    return true;
}

function instance_name($ID):string{
    $GLOBALS["INSTANCE_ID"]=$ID;
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $ligne=$q->mysqli_fetch_array("SELECT instancename from postfix_instances WHERE id='$ID'");
    return trim($ligne["instancename"]);
}
function reconfigure_instance_progress($prc,$text):bool{
    $unix=new unix();
    $unix->framework_progress($prc, $text, "postfix-multi.{$GLOBALS["INSTANCE_ID"]}.reconfigure.progress");
    return true;
}
function reconfigure_instance($ID):bool{
    $GLOBALS["INSTANCE_ID"]=$ID;
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    reconfigure_instance_progress(10,"{configuring}");
    shell_exec("$php /usr/share/artica-postfix/exec.postfix.maincf.php --reconfigure --instance-id=$ID");
    reconfigure_instance_progress(10,"{configuring}");
    shell_exec("$php /usr/share/artica-postfix/exec.postfix.transport.php --instance-id=$ID");
    reconfigure_instance_progress(100,"{configuring} {success}");
    return true;
}

function reconfigure_instance_inet_interfaces($ID):bool{
    $unix=new unix();
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $ligne=$q->mysqli_fetch_array("SELECT * from postfix_instances WHERE id='$ID'");
    $interface=$ligne["interface"];
    $ipaddr=$unix->InterfaceToIPv4($interface);
    $instancename=$ligne["instancename"];
    $EnableipV6=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6"));
    _out("$instancename: Bind IP address $ipaddr");
    $unix->POSTCONF_MULTI_SET("instance$ID","inet_interfaces","$ipaddr");
    $unix->POSTCONF_MULTI_SET("instance$ID","inet_protocols","ipv4");
    $unix->POSTCONF_MULTI_SET("instance$ID","smtp_bind_address6","");
    $unix->POSTCONF_MULTI_SET("instance$ID","master_service_disable","");
    $unix->POSTCONF_MULTI_SET("instance$ID","authorized_submit_users","static:anyone");
    $unix->POSTCONF_MULTI_SET("instance$ID","debugger_command","");

    $smtp_bind_address6=null; // A voir si piv6
    if($EnableipV6==1){
        if(trim($smtp_bind_address6)<>null){
            $unix->POSTCONF_MULTI_SET("instance$ID","inet_protocols","all");
            $unix->POSTCONF_MULTI_SET("instance$ID","smtp_bind_address6",$smtp_bind_address6);
        }
    }
    return true;
}

function progress_instance($prc,$text,$id):bool{
    $unix=new unix();
    $GLOBALS["INSTANCE_ID"]=$id;
    $unix->framework_progress($prc,$text,"postfix-multi.$id.progress");
    return true;
}
function progress_instance_restart($prc,$text,$id):bool{
    $unix=new unix();
    $unix->framework_progress($prc,$text,"postfix-multi.$id.restart.progress");
    return true;

}
function restart_instance($ID):bool{
    $GLOBALS["INSTANCE_ID"]=$ID;
    $instance_name=instance_name($ID);
    _out("Restarting Instance $instance_name");
    progress_instance_restart(50,"{stopping} $instance_name",$ID);
    stop_instance($ID);
    progress_instance_restart(100,"{starting} $instance_name",$ID);
    if(!start_instance($ID)){
        progress_instance_restart(110,"{starting} $instance_name {failed}",$ID);
        return false;
    }
    progress_instance_restart(100,"{starting} $instance_name {success}",$ID);
    return true;
}

function instance_cron($ID):bool{
    $unix=new unix();
    $me=basename(__FILE__);
    $unix->Popuplate_cron_make("postfix-instance-status-$ID","*/5 * * * *","$me --status $ID");

    return true;
}

function build_monit($ID):bool{
    $md51=null;
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $me=__FILE__;
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $ligne=$q->mysqli_fetch_array("SELECT * from postfix_instances WHERE id='$ID'");
    $interface=$ligne["interface"];
    $ipaddr=$unix->InterfaceToIPv4($interface);
    $fname="/etc/monit/conf.d/postfix-instance$ID.monitrc";
    if(is_file($fname)){
        $md51=md5_file($fname);
    }
    $pidfile="/var/spool/postfix-instance$ID/pid/master.pid";
    $f[]="check process postfix-instance$ID with pidfile $pidfile";
    $f[]="start program = \"$php $me --instance-start $ID --monit\"";
    $f[]="stop program  = \"$php $me --instance-start $ID --monit\"";
    $f[]="if cpu > 60% for 2 cycles then alert";
    $f[]="if cpu > 80% for 5 cycles then restart";
    $f[]="if totalmem > 200.0 MB for 5 cycles then restart";
    $f[]="if children > 250 then restart";
    $f[]="if loadavg(5min) greater than 10 for 8 cycles then stop";
    $f[]="if failed host $ipaddr port 25 type tcp protocol smtp with timeout 15 seconds then alert";
    $f[]="if 3 restarts within 5 cycles then timeout";
    @file_put_contents($fname,@implode("\n",$f));
    $md52=md5_file($fname);
    if($md51==$md52){return true;}
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    return true;
}

function master_lock_instance($ID):bool{
    $master_lock="/var/lib/postfix-instance$ID/master.lock";
    if(!is_file($master_lock)){return true;}
    $unix=new unix();
    $instancename=instance_name($ID);
    _out("$instancename checking master.lock");
    $pid=intval(trim(@file_get_contents($master_lock)));
    _out("$instancename checking master.lock PID: $pid");
    if(!$unix->process_exists($pid)){
        _out("$instancename checking master.lock No PID running");
        @unlink($master_lock);
        return true;
    }

    _out("$instancename  master.lock killing PID $pid");
    $unix->KILL_PROCESS($pid,9);

    for ($i=1;$i<6;$i++){
        if(!$unix->process_exists($pid)){break;}
        $pid=intval(trim(@file_get_contents($master_lock)));
        _out("$instancename killing PID $pid");
        sleep(1);
    }
    $pid=intval(trim(@file_get_contents($master_lock)));
    if($unix->process_exists($pid)){
        _out("$instancename checking master.lock PID: $pid FAILED");
        return false;
    }
    @unlink($master_lock);
    return true;

}
function repair_maincf($ID):bool{
    $instancename=instance_name($ID);
    $GLOBALS["INSTANCE_ID"]=$ID;
    $f[]="compatibility_level = 2";
    $f[]="queue_directory = /var/spool/postfix-instance$ID";
    $f[]="daemon_directory = /usr/lib/postfix";
    $f[]="data_directory = /var/lib/postfix-instance$ID";
    $f[]="mail_owner = postfix";
    $f[]="unknown_local_recipient_reject_code = 550";
    $f[]="alias_maps = hash:/etc/aliases";
    $f[]="debug_peer_level = 2";
    $f[]="inet_protocols = ipv4";
    $f[]="shlib_directory = /usr/local/lib/postfix";
    $f[]="multi_instance_name = postfix-instance$ID";
    $f[]="multi_instance_enable = yes";
    $f[]="";
    @file_put_contents("/etc/postfix-instance$ID/main.cf",@implode("\n",$f));
    _out("$instancename: /etc/postfix-instance$ID/main.cf repaired...");
    return true;

}
function repair_mastercf($ID):bool{
    $instancename=instance_name($ID);
    $GLOBALS["INSTANCE_ID"]=$ID;
    $f[]="#";
    $f[]="# Postfix master process configuration file.  For details on the format";
    $f[]="# of the file, see the master(5) manual page (command: \"man 5 master\").";
    $f[]="#";
    $f[]="# ==========================================================================";
    $f[]="# service type  private unpriv  chroot  wakeup  maxproc command + args";
    $f[]="#               (yes)   (yes)   (yes)   (never) (100)";
    $f[]="# ==========================================================================";
    $f[]="smtp	inet	n	-	n	-	-	smtpd ";
    $f[]="submission	inet	n	-	n	-	-	smtpd";
    $f[]=" -o smtpd_etrn_restrictions=reject";
    $f[]="pickup	fifo	n	-	n	60	1	pickup";
    $f[]="cleanup	unix	n	-	n	-	0	cleanup";
    $f[]="pre-cleanup	unix	n	-	n	-	0	cleanup";
    $f[]="qmgr	fifo	n	-	n	300	1	qmgr";
    $f[]="tlsmgr	unix	-	-	n	1000?	1	tlsmgr";
    $f[]="rewrite	unix	-	-	n	-	-	trivial-rewrite";
    $f[]="bounce	unix	-	-	n	-	0	bounce";
    $f[]="defer	unix	-	-	n	-	0	bounce";
    $f[]="trace	unix	-	-	n	-	0	bounce";
    $f[]="verify	unix	-	-	n	-	1	verify";
    $f[]="flush	unix	n	-	n	1000?	0	flush";
    $f[]="proxymap	unix	-	-	n	-	-	proxymap";
    $f[]="proxywrite	unix	-	-	n	-	1	proxymap";
    $f[]="smtp	unix	-	-	n	-	-	smtp";
    $f[]="relay	unix	-	-	n	-	-	smtp -o fallback_relay=";
    $f[]="showq	unix	n	-	n	-	-	showq";
    $f[]="error	unix	-	-	n	-	-	error";
    $f[]="discard	unix	-	-	n	-	-	discard";
    $f[]="local	unix	-	n	n	-	-	local";
    $f[]="virtual	unix	-	n	n	-	-	virtual";
    $f[]="lmtp	unix	-	-	n	-	-	lmtp";
    $f[]="anvil	unix	-	-	n	-	1	anvil";
    $f[]="scache	unix	-	-	n	-	1	scache";
    $f[]="scan	unix	-	-	n		-	10	sm -v";
    $f[]="maildrop	unix	-	n	n	-	-	pipe ";
    $f[]="retry	unix	-	-	n	-	-	error ";
    $f[]="uucp	unix	-	n	n	-	-	pipe flags=Fqhu user=uucp argv=uux -r -n -z -a\$sender - \$nexthop!rmail (\$recipient)";
    $f[]="ifmail	unix	-	n	n	-	-	pipe flags=F user=ftn argv=/usr/lib/ifmail/ifmail -r \$nexthop (\$recipient)";
    $f[]="bsmtp	unix	-	n	n	-	-	pipe flags=Fq. user=bsmtp argv=/usr/lib/bsmtp/bsmtp -t\$nexthop -f\$sender \$recipient";
    $f[]="mailman	unix	-	n	n	-	-	pipe flags=FR user=mail:mail argv=/etc/mailman/postfix-to-mailman.py \${nexthop} \${mailbox}";
    $f[]="artica-whitelist	unix	-	n	n	-	-	pipe flags=F  user=mail argv=/usr/share/artica-postfix/bin/artica-whitelist -a \${nexthop} -s \${sender} --white";
    $f[]="artica-blacklist	unix	-	n	n	-	-	pipe flags=F  user=mail argv=/usr/share/artica-postfix/bin/artica-whitelist -a \${nexthop} -s \${sender} --black";
    $f[]="artica-reportwbl	unix	-	n	n	-	-	pipe flags=F  user=mail argv=/usr/share/artica-postfix/bin/artica-whitelist -a \${nexthop} -s \${sender} --report";
    $f[]="artica-reportquar	unix	-	n	n	-	-	pipe flags=F  user=mail argv=/usr/share/artica-postfix/bin/artica-whitelist -a \${nexthop} -s \${sender} --quarantines";
    $f[]="artica-spam	unix	-	n	n	-	-	pipe flags=F  user=mail argv=/usr/share/artica-postfix/bin/artica-whitelist -a \${nexthop} -s \${sender} --spam";
    $f[]="zarafa	unix	-	n	n	-	-	pipe	user=mail argv=/usr/bin/zarafa-dagent \${user}";
    $f[]="cyrus	unix	-	n	n	-	-	pipe	flags=R user=cyrus argv=/usr/sbin/cyrdeliver -e -m \${extension} \${user}";
    $f[]="";
    @file_put_contents("/etc/postfix-instance$ID/master.cf",@implode("\n",$f));
    _out("$instancename: /etc/postfix-instance$ID/master.cf repaired...");
    return true;
}

function start_instance($ID):bool{
    $GLOBALS["INSTANCE_ID"]=$ID;
    $instancename=instance_name($ID);
    $unix=new unix();
    $lockfile="/etc/postfix-instance$ID/artica.lock";
    if(is_file($lockfile)){
        _out("$instancename Already locked, aborting..");
        return false;
    }
    @file_put_contents($lockfile,getmypid());


    progress_instance(50,"{starting}",$ID);
    $pid=instance_pid($ID);

    _out("Starting instance $instancename");
    if($unix->process_exists($pid)){
        progress_instance(100,"{starting} {success}",$ID);
        @unlink($lockfile);
        return true;
    }

    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $ligne=$q->mysqli_fetch_array("SELECT instancename,interface,enabled from postfix_instances WHERE id='$ID'");
    $enabled=intval($ligne["enabled"]);
    $instancename=$ligne["instancename"];
    $interface=$ligne["interface"];
    
    
    if($enabled==0){
        progress_instance(110,"{starting} $instancename {disabled}",$ID);
        @unlink($lockfile);
        return false;
    }

    if(!is_file("/etc/postfix-instance$ID/main.cf")){
        repair_maincf($ID);
    }
    if(!is_file("/etc/postfix-instance$ID/master.cf")){
        repair_mastercf($ID);
    }

    shell_exec("{$GLOBALS["postmulti"]} -i postfix-instance$ID -e enable");
    reconfigure_instance_inet_interfaces($ID);
    build_syslog($ID);
    build_monit($ID);

    $Ipv4=$unix->InterfaceToIPv4($interface);
    _out("$instancename listen $interface ($Ipv4)");
    if($Ipv4==null){
        _out("$instancename No IP Address associated to $interface");
        @unlink($lockfile);
        return false;
    }

    $pids=$unix->netstat_port($interface,25);
    _out("$instancename $interface:25 ".count($pids)." PID(s)");
    if(count($pids)>0){
        foreach ($pids as $pid=>$none){
            _out("$instancename $interface:25 killing PID $pid");
            $unix->KILL_PROCESS($pid,9);
        }
    }

    // Checking master.lock
    master_lock_instance($ID);
    synchronize_monit();
    synchronize_cron();
    $tempfile=$unix->FILE_TEMP();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup {$GLOBALS["postmulti"]} -i postfix-instance$ID -p start >$tempfile 2>&1";
    echo $cmd."\n";
    _out("$instancename Lauching daemon...");
    _out("$cmd");
    shell_exec($cmd);

    $pid=instance_pid($ID);
    if($unix->process_exists($pid)){
        _out("$instancename successfully started PID $pid");
        progress_instance(100,"{starting} $instancename {success}",$ID);
        @unlink($lockfile);
        return true;
    }
    for($i=1;$i<6;$i++){
        $pid=instance_pid($ID);
        if($unix->process_exists($pid)){
            progress_instance(100,"{starting} $instancename {success}",$ID);
            _out("$instancename successfully started PID $pid");
            @unlink($lockfile);
            return true;
        }
        progress_instance(50,"{starting} $instancename {waiting} $i/5",$ID);
        _out("Starting $instancename waiting $i/5...");
        sleep(2);
    }

    $pid=instance_pid($ID);
    if($unix->process_exists($pid)){
        progress_instance(100,"{starting} $instancename {success}",$ID);
        _out("$instancename successfully started PID $pid");
        @unlink($lockfile);
        return true;
    }

    _out("$instancename failed to start...");

    $f=explode("\n",@file_get_contents($tempfile));
    @unlink($tempfile);
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        _out($line);
    }

    progress_instance(110,"{starting} {failed}",$ID);
    @unlink($lockfile);
    return false;
}

function stop_instance($ID){
    $GLOBALS["INSTANCE_ID"]=$ID;
    $unix=new unix();
    progress_instance(50,"{stopping}",$ID);
    $pid=instance_pid($ID);

    _out("Stopping instance $ID");
    if(!$unix->process_exists($pid)){
        progress_instance(100,"{stopping} {success}",$ID);
        return true;
    }
    shell_exec("{$GLOBALS["postmulti"]} -i postfix-instance$ID -p stop >/dev/null 2>&1");
    $pid=instance_pid($ID);
    if(!$unix->process_exists($pid)){
        progress_instance(100,"{stopping} {success}",$ID);
        return true;
    }
    progress_instance(110,"{stopping} {failed}",$ID);
    return true;
}

function instance_pid($ID):int{
    $unix=new unix();
    $pidfile="/var/spool/postfix-instance$ID/pid/master.pid";
    if(!is_file($pidfile)){return 0;}
    return $unix->get_pid_from_file($pidfile);
}
function instance_status($ID):bool{
    $unix=new unix();
    $instance_name="postfix-instance$ID";
    $CurInstances=InstancesList();
    if(!isset($CurInstances[$instance_name])){
        if($GLOBALS["VERBOSE"]){
            echo "Not found $instance_name in:";
            print_r($CurInstances);
        }
        $ARRAY["STATUS"]=0;
        $ARRAY["TIME"]="";
        $ARRAY["STATUS_TEXT"]="{not_installed}";
        $ARRAY["NOT_INSTALLED"]=true;
        @file_put_contents(PROGRESS_DIR."/postmulti-$ID.status",serialize($ARRAY));
        return true;
    }
    if($CurInstances[$instance_name]==0){
        $ARRAY["STATUS"]=1;
        $ARRAY["STATUS_TEXT"]="{disabled}";
        $ARRAY["DISABLED"]=true;
        $ARRAY["TIME"]="";
        @file_put_contents(PROGRESS_DIR."/postmulti-$ID.status",serialize($ARRAY));
        return true;
    }
    if(!$unix->process_exists(instance_pid($ID))){
        $ARRAY["STATUS"]=0;
        $ARRAY["STATUS_TEXT"]="{stopped}";
        $ARRAY["TIME"]="";
        @file_put_contents(PROGRESS_DIR."/postmulti-$ID.status",serialize($ARRAY));
        return true;
    }
    $ARRAY["STATUS"]=2;
    $ARRAY["STATUS_TEXT"]="{running}";
    $ARRAY["TIME"]=$unix->PROCESS_TTL_TEXT(instance_pid($ID));
    @file_put_contents(PROGRESS_DIR."/postmulti-$ID.status",serialize($ARRAY));
    return true;
}




function DestroyInstance($instance):bool{
        $ID=0;
        $unix=new unix();
        if(preg_match("#-instance([0-9]+)#",$instance,$re)){
            $ID=intval($re[1]);
        }

		_out("Destroy instance \"$instance\"");
		shell_exec("{$GLOBALS["postmulti"]} -i $instance -p stop");
		shell_exec("{$GLOBALS["postmulti"]} -i $instance -e disable");
		shell_exec("{$GLOBALS["postmulti"]} -i $instance -e destroy");

        $cronfs[]="postfix-instance-status-$ID";
        $cronfs[]="postfix-postqueue-$ID";

        $REBOOT=false;
        foreach ($cronfs as $cronfile){
            if(is_file("/etc/cron.d/$cronfile")){
                @unlink("/etc/cron.d/$cronfile");
                $REBOOT=true;
            }

        }

        if($REBOOT){
            shell_exec("/etc/init.d/cron reload");
        }
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $q->QUERY_SQL("DELETE FROM postfix_params WHERE uuid='instance$ID'");
    $q->QUERY_SQL("DELETE FROM transport_maps WHERE instanceid=$ID");
    $q->QUERY_SQL("DELETE FROM smtp_generic_maps WHERE instanceid=$ID");
    $q->QUERY_SQL("DELETE FROM ip_reputations WHERE instanceid=$ID");
    $q->QUERY_SQL("DELETE FROM mynetworks WHERE instance_id=$ID");
    $q->QUERY_SQL("DELETE FROM smtpd_milter_maps WHERE instance_id=$ID");

    $fname="/etc/monit/conf.d/postfix-instance$ID.monitrc";
    $syslogconf = "/etc/rsyslog.d/postfix-instance$ID.conf";
    if(is_file($fname)){
        @unlink($fname);
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    }
    if(is_file($syslogconf)){
        @unlink($syslogconf);
        $unix->RESTART_SYSLOG();

    }

    $q          = new lib_sqlite("/home/artica/SQLITE/syslogrules.db");
    $q->QUERY_SQL("DELETE FROM rules WHERE logtype='postfix-instance$ID'");
return true;
	
}

function PostfixMultiProgress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"postfix-multi.progress");

}

function PostfixMultiEnable(){
    $unix=new unix();

    $php=$unix->LOCATE_PHP5_BIN();

    PostfixMultiProgress(10,"{enable}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnablePostfixMultiInstance",1);
    shell_exec("$php /usr/share/artica-postfix/exec.convert-to-sqlite.php --postfix-upgrade");

    PostfixMultiProgress(50,"{enable}");
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $sql="CREATE TABLE IF NOT EXISTS `postfix_multi` (
        `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
        `key` TEXT NOT NULL,
        `value` TEXT NOT NULL,
        `ou` TEXT NULL,
        `instanceid` INTEGER NOT NULL,
        `ValueTEXT` TEXT NOT NULL,
        `uuid` TEXT NOT NULL );";
    $q->QUERY_SQL($sql);

    $sql="CREATE TABLE IF NOT EXISTS `postfix_instances` (
        `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
        `instancename` TEXT NOT NULL,
         interface TEXT NOT NULL,
        `enabled` INTEGER NOT NULL DEFAULT 1);";
    $q->QUERY_SQL($sql);
    $maincf=new maincf_multi();
    $maincf->PostfixMainCfDefaultInstance();
    reconfigure();
    PostfixMultiProgress(100,"{enable} {success}");
}

function PostfixMultiDisable(){

    PostfixMultiProgress(10,"{disable}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnablePostfixMultiInstance",0);


	$MAIN=InstancesList();

    foreach ($MAIN as $instance=>$ou){
		if($instance==null){continue;}
		if($instance=="-"){continue;}
        PostfixMultiProgress(50,"destroy $instance");
		echo "Starting......: ".date("H:i:s")." Postfix destroy \"$instance\"\n";
        if(preg_match("#instance([0-9]+)#",$instance,$re)){
            $ID=intval($re[1]);
            DestroyInstance($ID);
        }

	}
    PostfixMultiProgress(80,"{disable}");
	$unix=new unix();
	$unix->POSTCONF_SET("multi_instance_enable","no");
	$unix->POSTCONF_SET("inet_interfaces","all");
	$unix->POSTCONF_SET("multi_instance_directories","");
    $unix->POSTCONF_SET("shlib_directory","");
    PostfixMultiProgress(90,"{reconfigure}");
	system(LOCATE_PHP5_BIN2()."/usr/share/artica-postfix/exec.postfix.maincf.php --reconfigure");
    PostfixMultiProgress(100,"{done}");
	
}

function remove_old_instances():bool{
    $q = new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $results = $q->QUERY_SQL("SELECT * FROM postfix_instances");
    foreach ($results as $index => $ligne) {
        $ID = $ligne["ID"];
        $instance = "postfix-instance{$ID}";
        $MAIN[$instance] = true;
    }

    $OLDS=InstancesList();
    foreach ($OLDS as $instance=>$none){
        if(!isset($MAIN[$instance])){
            _out("Destroy Instance $instance");
            DestroyInstance($instance);
        }

    }
return true;
}
function status_instance($ID):string{
    $GLOBALS["INSTANCE_ID"]=$ID;
    $fname=PROGRESS_DIR . "/postfix.$ID.status";
    $GLOBALS["CLASS_UNIX"]=new unix();
    $l[]="[POSTFIX]";
    $l[]="service_name=APP_POSTFIX";
    $l[]="master_version=".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $l[]="service_cmd=/etc/init.d/postfix";
    $l[]="service_disabled=1";
    $l[]="installed=1";
    $l[]="remove_cmd=--postfix-remove";
    $l[]="family=postfix";
    $l[]="watchdog_features=1";
    $l[]="status_file=$fname";


    $master_pid=instance_pid($ID);
    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $ligne=$q->mysqli_fetch_array("SELECT enabled,instancename from postfix_instances WHERE id='$ID'");
        $enabled=$ligne["enabled"];
        $instancename=$ligne["instancename"];
        if($enabled==1) {
            squid_admin_mysql("$instancename SMTP instance is not running {action}={start}", null, __FILE__, __LINE__);
            start_instance($ID);
        }
        $l[]="running=0";
        @file_put_contents($fname,@implode("\n",$l));
        @chmod($fname,0755);
        return @implode("\n",$l);
    }

    $l[]=$GLOBALS["CLASS_UNIX"]->GetMemoriesOfStatus($master_pid,"APP_POSTFIX$ID");
    $l[]="";

    @file_put_contents($fname,@implode("\n",$l));
    @chmod($fname,0755);
    return @implode("\n",$l);

}







?>