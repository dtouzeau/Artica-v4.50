<?php
// stamp 01/11/2026

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["debian-mirror"])){debian_mirror_install();exit;}
if(isset($_GET["debian-mirror-uninstall"])){debian_mirror_uninstall();}
if(isset($_GET["apt-mirror-ini-status"])){APT_MIRROR_STATUS();exit;}
if(isset($_GET["apt-mirror-stop"])){APT_MIRROR_STOP();exit;}
if(isset($_GET["apt-mirror-start"])){APT_MIRROR_START();exit;}
if(isset($_GET["apt-mirror-save"])){APT_MIRROR_CONFIG();exit;}
if(isset($_GET["debian-mirror-remove"])){APT_MIRROR_REMOVE();exit;}
if(isset($_GET["debian-mirror-ismove"])){APT_MIRROR_MOVE_RUNING();exit;}
if(isset($_GET["apt-mirror-move"])){APT_MIRROR_MOVE_START();exit;}
if(isset($_GET["config-client"])){APT_CLIENT_CONF();exit;}
if(isset($_GET["syslog-mirror"])){SearchInSyslog();exit;}

writelogs_framework("Unable to understand the query ".@implode(" ",$_GET),__FUNCTION__,__FILE__,__LINE__);


function APT_CLIENT_CONF(){
    system("/usr/sbin/artica-phpfpm-service -sources-list");
}




function debian_mirror_install(){
    $unix=new unix();
    $unix->framework_execute("exec.apt-mirror.php --install",
        "debian-mirror.progress",
        "debian-mirror.log");
}
function debian_mirror_uninstall(){
    $unix=new unix();
    $unix->framework_execute("exec.apt-mirror.php --uninstall",
        "debian-mirror.progress",
        "debian-mirror.log");
}
function APT_MIRROR_REMOVE(){
    $unix=new unix();
    $unix->framework_execute("exec.apt-mirror.php --rmdir",
        "debian-mirror.progress",
        "debian-mirror.log");
}

function APT_MIRROR_STATUS(){
    shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --apt-mirror --nowachdog");
}
function APT_MIRROR_STOP(){
    $unix=new unix();
    $pid=$unix->PIDOF_PATTERN("perl /bin/apt-mirror");
    writelogs("apt-mirror, pid=$pid",__FUNCTION__,__FILE__,__LINE__);
    if(!$unix->process_exists($pid)){return true;}
    writelogs("apt-mirror, kill=$pid",__FUNCTION__,__FILE__,__LINE__);
    $unix->KILL_PROCESS($pid,9);
    $unix->framework_exec("exec.apt-mirror.php --stop2");
    return true;
}
function APT_MIRROR_START(){
    $unix=new unix();
    $unix->framework_exec("exec.apt-mirror.php --perform");
}
function APT_MIRROR_MOVE_RUNING(){
    $unix=new unix();
    $pids=$unix->PIDOF_PATTERN("rsync -arctuxzv --remove-source-files");
    writelogs_framework("rsync --> $pids....",__FUNCTION__,__FILE__,__LINE__);
    $FILE=PROGRESS_DIR."/APT_MIRROR_MOVE_RUNING";
    if($unix->process_exists($pids)){
        @file_put_contents($FILE,1);
        return true;
    }
    @file_put_contents($FILE,0);
    return true;
}
function APT_MIRROR_MOVE_START(){
    $unix=new unix();
    $unix->framework_exec("exec.apt-mirror.php --move-folder");
}


function APT_MIRROR_CONFIG(){
    $unix=new unix();
    $unix->framework_exec("exec.apt-mirror.php --build");
    $unix->framework_exec("exec.apt-mirror.php --restart");
}

function SearchInSyslog(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["syslog-mirror"]));
    $targetfile="/var/log/apt-mirror.log";
    $RFile=PROGRESS_DIR."/apt-mirror.syslog";
    $PFile=PROGRESS_DIR."/apt-mirror.syslog.pattern";

    foreach ($MAIN as $val=>$key){
        $MAIN[$val]=str_replace(".", "\.", $MAIN[$val]);
        $MAIN[$val]=str_replace("/", "\/", $MAIN[$val]);
        $MAIN[$val]=str_replace("*", ".*?", $MAIN[$val]);
    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    if($max==0){$max=100;}
    $date=$MAIN["DATE"];
    $search=$MAIN["TERM"];
    $search="$date.*?$search";
    $search=str_replace(".*?.*?",".*?",$search);


    $cmd="$grep --binary-files=text -i -E '$search' $targetfile |$tail -n $max >$RFile 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents($PFile, $search);
    shell_exec($cmd);

}