<?php
$mem=round(((memory_get_usage()/1024)/1000),2);events("START WITH {$mem}MB ","MAIN",__LINE__);
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$mem=round(((memory_get_usage()/1024)/1000),2);events("{$mem}MB before class.users.menus.inc","MAIN",__LINE__);
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.ad-agent.logs.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){
    $GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir");
    if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; }
}


if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(!Build_pid_func(__FILE__,"MAIN")){
    events(basename(__FILE__)." Already executed.. aborting the process");
    exit();
}

$pid=getmypid();
$pidfile="/var/run/adagent-tail.pid";
$unix=new unix();
$GLOBALS["SQUID_INSTALLED"]=false;
$GLOBALS["RSYNC_RECEIVE"]=array();
$GLOBALS["LOCATE_PHP5_BIN"]=$unix->LOCATE_PHP5_BIN();
$GLOBALS["PS_BIN"]=$unix->find_program("ps");
$GLOBALS["SID"]="";
$GLOBALS["SQUIDBIN"]=$unix->LOCATE_SQUID_BIN();
$GLOBALS["nohup"]=$unix->find_program("nohup");
$GLOBALS["sysctl"]=$unix->find_program("sysctl");
$GLOBALS["CHMOD_BIN"]=$unix->find_program("chmod");
$GLOBALS["CHOWN_BIN"]=$unix->find_program("chown");
$GLOBALS["SMARTCTL_BIN"]=$unix->find_program("smartctl");
$GLOBALS["NICE"]=$unix->EXEC_NICE();
$GLOBALS["REBOOT_BIN"]=$unix->find_program("reboot");
$GLOBALS["SYNC_BIN"]=$unix->find_program("sync");
$GLOBALS["DF_BIN"]=$unix->find_program("df");
$GLOBALS["COUNT-LINES"]=0;
$GLOBALS["COUNT-LINES-TIME"]=0;
$GLOBALS["PGREP_BIN"]=$unix->find_program("pgrep");
$GLOBALS["SHUTDOWN_BIN"]=$unix->find_program("shutdown");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_UNIX"]=new unix();
$GLOBALS["CLEANCMD"]="{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.clean.logs.php --urgency >/dev/null 2>&1 &";

$sock=null;
$unix=null;

$mem=round(((memory_get_usage()/1024)/1000),2);events("{$mem}MB before forking","MAIN",__LINE__);

@file_put_contents($pidfile, getmypid());

$pipe = fopen("php://stdin", "r");
while(!feof($pipe)){
    $buffer .= fgets($pipe, 4096);
    try{ Parseline($buffer);}catch (Exception $e) {events("fatal error:".  $e->getMessage());}

    $buffer=null;
}



fclose($pipe);
events("Shutdown...");
@unlink($pidfile);
exit();


function Parseline($buffer){


    if(strpos($buffer, '"CONNECT')>1){return;}
    if(strpos($buffer, '"POST')>1){return;}
    if(strpos($buffer, '"GET')>1){return;}

    if(preg_match("#haproxy\[.*?Server\s+(.+?)\s+is going\s+([A-Z]+)\s+#", $buffer,$re)){squid_admin_mysql(1, "Load-balancing {$re[1]} going on state {$re[2]} [action=notify]",$buffer,__FILE__,__LINE__);return;}
    if(preg_match("#haproxy\[.*?Server\s+(.+?)\s+is\s+(DOWN|UP).*?reason: (.+?), check duration#", $buffer,$re)){squid_admin_mysql(0, "Load-balancing {$re[1]} is on state {$re[2]} ! reason:{$re[3]} [action=notify]",$buffer,__FILE__,__LINE__);return;}
    if(preg_match("#haproxy\[.*?Server\s+(.+?)\s+is\s+([A-Z\/]+)\s+#", $buffer,$re)){squid_admin_mysql(1, "Load-balancing {$re[1]} going on state {$re[2]} [action=notify]",$buffer,__FILE__,__LINE__);return;}

}

function events($text,$zline=0){
    $common="{$GLOBALS["ARTICALOGDIR"]}/adagent.debug";
    $size=@filesize($common);
    $pid=getmypid();
    $date=date("Y-m-d H:i:s");
    $h = @fopen($common, 'a');
    $sline="[$pid] $text";
    $line="$date [$pid] $text (line $zline)\n";
    @fwrite($h,$line);

    @fclose($h);

}