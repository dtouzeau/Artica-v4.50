#!/usr/bin/php -q
<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["OUTPUT"]=true;
$GLOBALS["TITLENAME"]="DNS RBL Categories";


if($argv[1]=="--stop"){stop();exit();}
if($argv[1]=="--start"){start();exit();}
if($argv[1]=="--restart"){restart();exit();}
if($argv[1]=="--install"){install();exit();}
if($argv[1]=="--reload"){reload();exit();}
if($argv[1]=="--uninstall"){uninstall();exit();}
if($argv[1]=="--replic"){replic();exit();}
if($argv[1]=="--templates"){templates();exit;}


function logging($text){
    if(!function_exists("openlog")){return;}
    if(!function_exists("syslog")){return;}
    $LOG_SEV=LOG_INFO;
    if(function_exists("openlog")){openlog("categories-rbl", LOG_PID , LOG_SYSLOG);}
    if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
    if(function_exists("closelog")){closelog();}
}

function reload(){

    $pid=PID_NUM();

    echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} PID $pid\n";
    if(is_running($pid)){
        echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Check changes\n";
        getdnsfiles();
        echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $pid\n";
        KILL_PROCESS($pid,"HUP");
        return;
    }

    echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} try to start\n";
    if(!start(true)){
        return;
    }


}

function replic(){

    $changes=getdnsfiles();
    logging("Changes: ".count($changes)." modified");
    if(count($changes)==0){
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Nothing to new\n";
        $pid=PID_NUM();
        if(!is_running($pid)){
            logging("Not running !!! [{action}={start}]");
            start();
        }
        return;
    }
    logging("Reloading....");
    reload();

}

function restart(){

    if(!stop(true)){
        return;
    }


    if(!start(true)){
        return;
    }

}

function PROCESS_PPID($pid){
    $pid=trim($pid);
    $pid=str_replace("\r", "", $pid);
    $pid=str_replace("\n", "", $pid);
    if(!is_file("/proc/$pid/status")){return 0;}
    $f=@explode("\n",@file_get_contents("/proc/$pid/status"));

    $pattern="#^PPid:\s+([0-9]+)#";
    foreach ($f as $num=>$ligne){
        if(preg_match($pattern,$ligne,$re)){
            if(trim($re[1])<50){return $pid;}
            return trim($re[1]);
        }
    }
    return $pid;
}


function PIDOF($binpath,$noppid=false){

    $cmd="/bin/pidof -s $binpath";

    $re=proc_exec($cmd);
    if(is_array($re)){
        foreach ($re as $ligne){
            if(!preg_match("#[0-9]+#",$ligne)){continue;}
            $pid=trim($ligne);
            if($noppid){
                if($GLOBALS["VERBOSE"]){echo "PIDOF -> $pid\n";}
                return $pid;
            }
            return PROCESS_PPID($pid);

        }
    }

}
function proc_exec($cmd){
    $ret        = 0;
    $BUF_SIZE   = 1024;
    $FD_WRITE   = 0 ;      # stdin
    $FD_READ    = 1;
    $FD_ERR     = 2;        # stderr
    $errbuf     = null;
    $first_exitcode=null;
    $array=array();

    $descriptorspec = array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
        2 => array("pipe", "w")
    );

    $ptr = proc_open($cmd, $descriptorspec, $pipes, NULL, $_ENV);
    if (!is_resource($ptr)){return array();}

    while (($buffer = fgets($pipes[$FD_READ], $BUF_SIZE)) != NULL || ($errbuf = fgets($pipes[$FD_ERR], $BUF_SIZE)) != NULL) {
        if (!isset($flag)) {
            $pstatus = proc_get_status($ptr);
            $first_exitcode = $pstatus["exitcode"];
            $flag = true;
        }
        if (strlen($buffer)){
            $buffer=str_replace("\n", "", $buffer);
            $buffer=str_replace("\r", "", $buffer);
            $array[]=$buffer;
        }

    }

    foreach ($pipes as $pipe){fclose($pipe);}
    $pstatus = proc_get_status($ptr);

    if (!strlen($pstatus["exitcode"]) || $pstatus["running"]) {

        if ($pstatus["running"])
            proc_terminate($ptr);
        $ret = proc_close($ptr);
    } else {
        if ((($first_exitcode + 256) % 256) == 255
            && (($pstatus["exitcode"] + 256) % 256) != 255)
            $ret = $pstatus["exitcode"];
        elseif (!strlen($first_exitcode))
            $ret = $pstatus["exitcode"];
        elseif ((($first_exitcode + 256) % 256) != 255)
            $ret = $first_exitcode;
        else
            $ret = 0;
        proc_close($ptr);
    }

    return $array;
}

function is_running($pid){
    if($pid==0){return false;}
    if(is_file("/proc/$pid/status")){return true;}
}

function PID_NUM(){
    $pid=trim(@file_get_contents("/var/run/rbldnsd.pid"));
    if(is_running($pid)){return $pid;}
    return PIDOF("/usr/sbin/rbldnsd");
}

function blacklisted(){
    $blacklisted["167"]=true;
    $blacklisted["199"]=true; // Educational White list ({free_edition})
    $blacklisted["89"]=true; // Global White List
    $blacklisted["166"]=true; //advertising ({free_edition})
    $blacklisted["167"]=true; //porn ({free_edition})
    $blacklisted["168"]=true; //agressive ({free_edition})
    $blacklisted["169"]=true; //arjel ({free_edition})
    $blacklisted["170"]=true; //religious associations ({free_edition})
    $blacklisted["171"]=true; //astrology ({free_edition})
    $blacklisted["172"]=true; //audio-video ({free_edition})
    $blacklisted["173"]=true; //finance/banking ({free_edition})
    $blacklisted["174"]=true; //bitcoin ({free_edition})
    $blacklisted["175"]=true; //blogs ({free_edition})
    $blacklisted["176"]=true; //celebrities ({free_edition})
    $blacklisted["177"]=true; //chat ({free_edition})
    $blacklisted["178"]=true; //children ({free_edition})
    $blacklisted["179"]=true; //cleaning ({free_edition})
    $blacklisted["180"]=true; //hobby/cooking ({free_edition})
    $blacklisted["182"]=true; //dangerous materials ({free_edition})
    $blacklisted["183"]=true; //Dating ({free_edition})
    $blacklisted["184"]=true; //D-DOS ({free_edition})
    $blacklisted["185"]=true; //Dialers ({free_edition})
    $blacklisted["186"]=true; //Downloads ({free_edition})
    $blacklisted["187"]=true; //Drugs ({free_edition})
    $blacklisted["188"]=true; //Educational Games ({free_edition})
    $blacklisted["189"]=true; //filehosting ({free_edition})
    $blacklisted["190"]=true; //Financial ({free_edition})
    $blacklisted["191"]=true; //Forums ({free_edition})
    $blacklisted["192"]=true; //Gambling ({free_edition})
    $blacklisted["193"]=true; //Games ({free_edition})
    //$f[]="$blacklisted["194"]=true; //Proxies ({free_edition})
    $blacklisted["195"]=true; //Hacking ({free_edition})
    $blacklisted["196"]=true; //Jobsearch ({free_edition})
    $blacklisted["197"]=true; //Women`s Lingerie ({free_edition})
    $blacklisted["198"]=true; //White list ({free_edition})
    $blacklisted["199"]=true; //Educational White list ({free_edition})
    $blacklisted["200"]=true; //Malwares ({free_edition})
    $blacklisted["201"]=true; //Manga ({free_edition})
    $blacklisted["202"]=true; //Marketingware ({free_edition})
    $blacklisted["203"]=true; //Mixed/adult ({free_edition})
    $blacklisted["204"]=true; //Smartphones ({free_edition})
    $blacklisted["205"]=true; //Phishing ({free_edition})
    $blacklisted["207"]=true; //Press ({free_edition})
    $blacklisted["208"]=true; //Redirector ({free_edition})
    //$f[]="$blacklisted["209"]=true; //Advertising ({free_edition})
    $blacklisted["210"]=true; //Radio ({free_edition})
    $blacklisted["211"]=true; //Reaffected ({free_edition})
    //$f[]="$blacklisted["212"]=true; //Redirector ({free_edition})
    $blacklisted["213"]=true; //Remote-control ({free_edition})
    $blacklisted["214"]=true; //Sect ({free_edition})
    $blacklisted["215"]=true; //Sexual Education ({free_edition})
    $blacklisted["216"]=true; //Shopping ({free_edition})
    $blacklisted["217"]=true; //Shorteners ({free_edition})
    $blacklisted["218"]=true; //Social Networks ({free_edition})
    $blacklisted["219"]=true; //Special ({free_edition})
    $blacklisted["220"]=true; //Recretation/Sports ({free_edition})
    $blacklisted["221"]=true; //Strict redirector ({free_edition})
    $blacklisted["222"]=true; //Strong redirector ({free_edition})
    $blacklisted["223"]=true; //Translation ({free_edition})
    $blacklisted["224"]=true; //Cheater ({free_edition})
    $blacklisted["225"]=true; //Update ({free_edition})
    $blacklisted["226"]=true; //Warez ({free_edition})
    $blacklisted["227"]=true; //Webmail ({free_edition})
    return $blacklisted;

}

function getdnsfiles($onlyChanges=false){
    $path       = "/home/dtouzeau";
    $Tpath      = "/home/artica/rbldns/dsbl";
    $dir_handle = @opendir($path);
    $array      = array();
    if(!$dir_handle){
        return array();
    }


    $blacklisted=blacklisted();
    foreach($blacklisted as $zfile){
        if(is_file("$Tpath/$zfile")){
            echo "Remove $Tpath/$zfile\n";-

            @unlink("$Tpath/$zfile");}
    }



    while ($file = readdir($dir_handle)) {
        if($file=='.'){continue;}
        if($file=='..'){continue;}
        $md52   = null;
        if(is_dir("$path/$file")){continue;}
        $md5    = md5_file("$path/$file");
        if(isset($blacklisted[$file])){continue;}

        if(is_file("$Tpath/$file")){$md52=md5_file("$Tpath/$file");}

        if($onlyChanges){
            if($md5==$md52){
                echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $file (no changes)\n";
                @chown("$Tpath/$file","rbldns");
                continue;
            }
        }


        if($md5<>$md52){
            echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} replicate $file\n";
            logging("$file as been modified");
            if(is_file("$Tpath/$file")){@unlink("$Tpath/$file");}
            @copy("$path/$file","$Tpath/$file");

        }

        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $file (OK)\n";
        if(is_file("$Tpath/$file")){
            @chown("$Tpath/$file","rbldns");
            $array[]=$file;
        }

        continue;


    }

    @closedir($dir_handle);
    return $array;
}


function start(){

    $pid=PID_NUM();

    if(is_running($pid)){
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid...\n";
        return true;
    }

    $opt[]      = "/usr/bin/nohup";
    $fz         =   getdnsfiles();
    $opt[]      = "/usr/sbin/rbldnsd";
    $opt[]      = "-4 -f -c 1m";
    $opt[]      = "-b  46.105.178.150";
    $opt[]      = "-p /var/run/rbldnsd.pid";
    $opt[]      = "-r /home/artica/rbldns/dsbl";
    $opt[]      = "-l /var/log/dns.log";
    $opt[]      = "-s /var/log/dns.stats";
    $opt[]      = "filter.artica.center:dnset:".@implode(",",$fz);
    $opt[]      = " >/dev/null 2>&1 &";
    $cmd        = @implode(" ",$opt);


    if(!is_file("/var/log/dns.log")){@touch("/var/log/dns.log");}
    if(!is_file("/var/log/dns.stats")){@touch("/var/log/dns.stats");}
    @chown("/var/log/dns.log","rbldns");
    @chmod("/var/log/dns.log",0755);

    @chmod("/var/log/dns.stats",0755);
    @chown("/var/log/dns.stats","rbldns");

    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";
    shell_exec($cmd);


    for($i=1;$i<5;$i++){
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";
        sleep(1);
        $pid=PID_NUM();
        if(is_running($pid)){break;}
    }

    $pid=PID_NUM();
    if(is_running($pid)){
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";

        return true;

    }else{
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";
        return false;
    }

    @unlink($tmpfile);
    return true;
}
function stop(){

    $pid=PID_NUM();


    if(!is_running($pid)){
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";
        return true;
    }
    $pid=PID_NUM();

    echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";
    unix_system_kill_force($pid);
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        if(!is_running($pid)){break;}
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";
        sleep(1);
    }

    $pid=PID_NUM();
    if(!is_running($pid)){
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
        return true;
    }

    echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";
    unix_system_kill_force($pid);
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        if(!is_running($pid)){break;}
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
        sleep(1);
    }

    if(is_running($pid)){
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed pid $pid...\n";
        return false;
    }else{
        return true;
    }

}










function unix_system_kill_force($pid){

    if (!is_numeric($pid)) {
        return;
    }
    if ($pid < 5) {
        return;
    }
    if (!is_dir("/proc/$pid")) {
        return;
    }

    if(!is_running($pid)){return false;}
    KILL_PROCESS($pid, 9);


}
function KILL_PROCESS($pid,$signal=0){
    $pid=intval($pid);
    if($pid<5){return;}
    if($signal=="HUP"){
        $kill   = "/bin/kill";
        shell_exec("$kill -HUP $pid >/dev/null 2>&1");
        return;
    }


    if($signal==null){$signal=15;}
    if($signal==0){$signal=15;}
    $cmdline=null;

    $arg["HUP"]=1;
    $arg["USR1"]=10;
    $arg["USR2"]=12;
    $arg["TERM"]=15;
    $arg["WINCH"]=28;
    $arg["KILL"]=9;

    $argnum[1]="HUP";
    $argnum[10]="USR1";
    $argnum[12]="USR2";
    $argnum[15]="TERM";
    $argnum[28]="WINCH";
    $argnum[9]="KILL";

    if(is_numeric($signal)){
        if(isset($argnum[$signal])){
            posix_kill($pid,$signal);
        }
        return;

    }



    if(isset($arg[$signal])){
        if(is_file("/proc/$pid/cmdline")){$cmdline=@file_get_contents("/proc/$pid/cmdline");}
        posix_kill($pid,$arg[$signal]);
    }

}