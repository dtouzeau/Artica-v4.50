<?php
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');


if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--reconfigure"){reconfigure();exit;}
if($argv[1]=="--proxy"){echo proxy_settings()."\n";exit;}
if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--restart"){restart();exit;}
if($argv[1]=="--remove"){remove_service();exit;}

function build_progress($pourc,$text){
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $cachefile=PROGRESS_DIR."/dwagent.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}

function reconfigure():bool{
    $unix=new unix();
    $installer  = "/usr/local/sbin/dwagent";
    $logpath    = "/var/log/dwagent/dwagent.log";
    $outse      = " >/var/log/dwagent/setup.log 2>&1";
    $DWAgentKey = null;

    if(is_file($installer)) {
        $binsize = filesize($installer);
        _out("Installer $binsize bytes");
        if ($binsize < 50) {
            @unlink($installer);
        }
    }

    if(!is_file($installer)){
        build_progress(10,"{reconfigure} {downloading}...");
        if(!download_dwagent()){
            build_progress(110,"{reconfigure} {failed}...");
            return false;
        }
    }

    if(is_file($installer)) {
        $binsize = filesize($installer);
        if ($binsize < 50) {
            @unlink($installer);
            build_progress(110,"{reconfigure} {failed} Corrupted installer ({$binsize}bytes)...");
            return false;
        }
    }

    if(is_file("/etc/artica-postfix/DWSERVICE_SETUP_KEY")){
        $DWAgentKey=trim(@file_get_contents("/etc/artica-postfix/DWSERVICE_SETUP_KEY"));
        if(!preg_match("#^[0-9]+-[0-9]+-[0-9]+$#",$DWAgentKey)){
            @unlink("/etc/artica-postfix/DWSERVICE_SETUP_KEY");
            return false;
        }

        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DWAgentKey",$DWAgentKey);
        @unlink("/etc/artica-postfix/DWSERVICE_SETUP_KEY");
    }

    build_progress(20,"{reconfigure} {uninstalling}...");
    if(is_file("/usr/share/dwagent/native/uninstall")) {
        shell_exec("/usr/share/dwagent/native/uninstall -silent");
    }



    if($DWAgentKey==null) {
        $DWAgentKey = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DWAgentKey"));
    }

    $proxy          = proxy_settings();
    build_progress(30,"{reconfigure} with key = $DWAgentKey");
    @chmod("/usr/local/sbin/dwagent",0750);
    $cmdline="/usr/local/sbin/dwagent -silent key=$DWAgentKey logpath=$logpath{$proxy} $outse";
    $sh=$unix->sh_command($cmdline);
    $unix->go_exec($sh);
    $tail=$unix->find_program("tail");
    $pp=30;
    for($i=0;$i<120;$i++){
        $pid=$unix->PIDOF_PATTERN("dwagent -silent key=");
        if(!$unix->process_exists($pid)){break;}
        $results=array();
        exec("$tail -n 1 /var/log/dwagent/setup.log 2>&1",$results);
        $pp++;
        if($pp>80){$pp=80;}
        build_progress($pp,$results[0]);
        sleep(1);
    }


    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DWAgentEnable",1);
    if($unix->process_exists(DWAGENT_PID())){
        build_progress(100,"{reconfigure} {success}");
        return true;
    }
    build_progress(80,"{starting}");
    if(!start()){
        echo $cmdline."\n";
        build_progress(110,"{reconfigure} {failed}");
        return false;
    }

    build_progress(100,"{reconfigure} {success}");
    return true;
}

function restart():bool{
    $unix=new unix();
    build_progress(20,"{restarting} {stopping}...");
    $unix->shell_command("/etc/init.d/dwagent stop");

    system("/etc/init.d/dwagent stop");
    if($unix->process_exists(DWAGENT_PID())){
        build_progress(30,"{restarting} {stopping}...");
        system("/etc/init.d/dwagent stop");
    }
    if($unix->process_exists(DWAGENT_PID())){
        build_progress(110,"{restarting} {stopping} {failed}...");
        return false;
    }

    build_progress(50,"{restarting} {starting}...");
    if(!start()){
        build_progress(110,"{restarting} {starting} {failed}...");
        return false;
    }
    build_progress(100,"{restarting} {starting} {success}...");
    return true;
}

function download_dwagent():bool{
    $logpath    = "/var/log/dwagent/dwagent.log";
    $curl       = new ccurl("https://www.dwservice.net/download/dwagent_x86.sh");
    build_progress(12,"{downloading} dwagent_x86.sh...");
    $curl->NoLocalProxy();
    if(!$curl->GetFile("/usr/local/sbin/dwagent")){
        build_progress(12,"{downloading} {failed}");
        echo $curl->error."\n";
        foreach ($GLOBALS["CURLDEBUG"] as $line){
            _out($line);
        }

        return false;

    }


    build_progress(12,"{downloading} {done}...");
    $KEY=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DWAgentKey"));
    $proxy_settings=proxy_settings();
    if($KEY==null){$KEY="xxxxxx";}
    @chmod("/usr/local/sbin/dwagent",0750);
    if(!is_dir("/var/log/dwagent")){@mkdir("/var/log/dwagent",true);}

    if(!test_install()) {
        build_progress(15, "{installing} key = $KEY");
        shell_exec("/usr/local/sbin/dwagent -silent key=$KEY logpath=$logpath{$proxy_settings} >/var/log/dwagent/setup.log 2>&1");
        build_progress(20, "{installing} {done}");
        if(!outsetup()){
            return false;
        }
    }

    return true;

}




function _out($text):bool{
    $unix=new unix();
    $unix->ToSyslog("[START] $text",false,"dwagent");
    $date=date("H:i:s");
    echo "Starting......: $date [INIT]: DWAgent service: $text\n";
    return true;
}

function outsetup(){
    $f=explode("\n",@file_get_contents("/var/log/dwagent/setup.log"));
    foreach ($f as $line){
        $line=trim($line);
        if(preg_match("#Text file busy#",$line)){
            echo "$line\n";
            echo " * * * * An Already installation still exists * * * *\n";
            return false;
        }
        if($line==null){continue;}
        _out($line);
    }
    return true;
}
function outserver(){
    $f=explode("\n",@file_get_contents("/usr/share/dwagent/native/service.log"));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        _out($line);
    }
}

function proxy_settings():string{
    $DWAgentProxy   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DWAgentProxy"));
    $ini            = new Bs_IniHandler();
    $unix           = new unix();


    $ini->loadString($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaProxySettings"));
    $ArticaProxyServerEnabled=$ini->_params["PROXY"]["ArticaProxyServerEnabled"];
    $ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
    $ArticaProxyServerPort=intval($ini->_params["PROXY"]["ArticaProxyServerPort"]);
    if($ArticaProxyServerPort==0){$ArticaProxyServerPort=3128;}
    $ArticaProxyServerUsername=$ini->_params["PROXY"]["ArticaProxyServerUsername"];
    $ArticaProxyServerUserPassword=$ini->_params["PROXY"]["ArticaProxyServerUserPassword"];
    if($ArticaProxyServerEnabled=="yes"){$ArticaProxyServerEnabled=1;}
    if($ArticaProxyServerEnabled=="no"){$ArticaProxyServerEnabled=0;}

    if($DWAgentProxy==0){return "";}
    if($ArticaProxyServerEnabled==0){return "";}

    $cmdline[]="proxyType=HTTP";
    $cmdline[]="proxyHost=$ArticaProxyServerName";
    $cmdline[]="proxyPort=$ArticaProxyServerPort";
    if($ArticaProxyServerUsername<>null) {
        $cmdline[] = "proxyUser=" . $unix->shellEscapeChars($ArticaProxyServerUsername);
        $cmdline[] = "proxyPassword=" . $unix->shellEscapeChars($ArticaProxyServerUserPassword);
    }
    return strval(" ".@implode(" ",$cmdline));
}

function install():bool{
    $logpath    = "/var/log/dwagent/dwagent.log";
    $outse      = " >/var/log/dwagent/setup.log 2>&1";
    $installer  = "/usr/local/sbin/dwagent";
    $unix       = new unix();
    build_progress(10,"{installing}");

    if(is_file($installer)){
        $fsize=filesize("/usr/local/sbin/dwagent");
        _out("Installer $installer exists ($fsize bytes)");
        if(filesize($fsize)<50){@unlink("/usr/local/sbin/dwagent");}
    }

    if(!is_file("/usr/local/sbin/dwagent")){
        build_progress(11,"Downloading dwagent installer....");
        if(!download_dwagent()){
            build_progress(110,"Failed to download agent...");
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DWAgentEnable",0);
            return false;
        }
        build_progress(14,"Downloading dwagent success....");
    }
    $KEY=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DWAgentKey"));
    if($KEY==null){$KEY="xxxxxx";}

    if(!is_dir("/var/log/dwagent")){@mkdir("/var/log/dwagent",0755,true);}
    @chmod("/usr/local/sbin/dwagent",0755);

    if(!test_install()){
        $proxy=proxy_settings();
        build_progress(25,"{installing} using DWAgent installer");
        $cmd="/usr/local/sbin/dwagent -silent key=$KEY logpath=$logpath{$proxy}$outse";
        echo "$cmd\n";
        $unix->shell_command($cmd);
        outsetup();

        for($i=0;$i<4;$i++) {
            build_progress(30+$i, "{installing} Testing if installation is a success...");
            sleep(1);
        }
    }

    build_progress(30,"{installing} Testing if installation is a success...");
    if(!test_install()){
        build_progress(110,"{installing} {failed}");
        if(is_file("/usr/share/dwagent/native/uninstall")){
            shell_exec("/usr/share/dwagent/native/uninstall -silent");
        }
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DWAgentEnable",0);
        return false;
    }
    build_progress(50,"{installing} Checking installation success");

    $f[]="{";
    $f[]="\"path\": \"/usr/share/dwagent\"";
    $f[]="}\n";
    @file_put_contents("/etc/dwagent",@implode("\n",$f));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DWAgentEnable",1);
    build_progress(50,"{installing} {APP_MONIT}");
    install_monit();
    build_progress(80,"{starting}");
    start();
    build_progress(100,"{success}");

    return true;

}

function DWAGENT_PID():int{
    $unix=new unix();
    $pid=$unix->get_pid_from_file("/usr/share/dwagent/dwagent.pid");
    if($unix->process_exists($pid)){return intval($pid);}
    return intval($unix->PIDOF_PATTERN("native/dwagsvc"));

}

function uninstall(){
    $unix=new unix();
    build_progress(25,"{uninstalling}...");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DWAgentEnable",0);
    $GLOBALS["CLASS_SOCKETS"]->GET_INFO("DWAgentKey","xxxxxx");

    if(is_file("/usr/share/dwagent/native/uninstall")) {
        shell_exec("/usr/share/dwagent/native/uninstall -silent");
    }

    if(is_file("/usr/local/sbin/dwagent")){
        @unlink("/usr/local/sbin/dwagent");
    }

    if(is_file("/etc/monit/conf.d/APP_DWAGENT.monitrc")){
        build_progress(50,"{uninstalling}...");
        @unlink("/etc/monit/conf.d/APP_DWAGENT.monitrc");
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    }

    build_progress(80,"{uninstalling}...");
    if(is_file("/etc/init.d/dwagent")){
        $unix->remove_service("/etc/init.d/dwagent");
    }
    build_progress(100,"{uninstalling} {done}");
}

function install_monit(){
    $unix       = new unix();
    $php        = $unix->LOCATE_PHP5_BIN();

    @unlink("/etc/monit/conf.d/APP_DWAGENT.monitrc");
    $f[]="check process APP_DWAGENT with pidfile /usr/share/dwagent/dwagent.pid";
    $f[]="\tstart program = \"$php ".__FILE__." --start\"";
    $f[]="";
    @file_put_contents("/etc/monit/conf.d/APP_DWAGENT.monitrc", @implode("\n", $f));
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
}

function start():bool{
    $unix           = new unix();
    $DWAgentEnable  = intval( $GLOBALS["CLASS_SOCKETS"]->GET_INFO("DWAgentEnable"));



    if($DWAgentEnable==0){
        _out("DWAgent/service not enabled");
        return false;
    }

    $pid=DWAGENT_PID();
    if($unix->process_exists($pid)){
        $ptime=$unix->PROCCESS_TIME_MIN($pid);
        _out("DWAgent service already running PID $pid since {$ptime}mn");
        return true;
    }
    if(!is_file("/etc/init.d/dwagent")){
        _out("DWAgent service, corrupted installation");
        return false;
    }
    _out("Starting service...");
    @unlink("/usr/share/dwagent/native/service.log");


    $unix->shell_command("/etc/init.d/dwagent start",true);

    for($i=1;$i<6;$i++){
        $pid=DWAGENT_PID();
        if($unix->process_exists($pid)){break;}
        _out("Waiting service to start $i/5");

    }

    $pid=DWAGENT_PID();
    if(!$unix->process_exists($pid)){
        _out("Starting service failed...");
        outserver();
        return false;
    }

    _out("Starting service success...");
    return true;

}

function test_install():bool{


    $f[]="/usr/share/dwagent/__pycache__";
    $f[]="/usr/share/dwagent/agent.py";
    $f[]="/usr/share/dwagent/applications.py";
    $f[]="/usr/share/dwagent/cacerts.pem";
    $f[]="/usr/share/dwagent/communication.py";
    $f[]="/usr/share/dwagent/config.json";
    $f[]="/usr/share/dwagent/configure.py";
    $f[]="/usr/share/dwagent/daemon.py";
    $f[]="/usr/share/dwagent/daemon.pyc";
    $f[]="/usr/share/dwagent/database.py";
    $f[]="/usr/share/dwagent/detectinfo.py";
    $f[]="/usr/share/dwagent/dwagent.log";
    $f[]="/usr/share/dwagent/dwagent.pid";
    $f[]="/usr/share/dwagent/fileversions.json";
    $f[]="/usr/share/dwagent/installer.py";
    $f[]="/usr/share/dwagent/ipc.py";
    $f[]="/usr/share/dwagent/listener.py";
    $f[]="/usr/share/dwagent/monitor.py";
    $f[]="/usr/share/dwagent/native.py";
    $f[]="/usr/share/dwagent/resources.py";
    $f[]="/usr/share/dwagent/runtime";
    $f[]="/usr/share/dwagent/sharedmem";
    $f[]="/usr/share/dwagent/utils.py";



    foreach ($f as $path){
        if(is_dir($path)){continue;}
        if(!is_file($path)){
            _out("Installation corrupted, Missing \"$path\"");
            return false;
        }

    }

    return true;

}


