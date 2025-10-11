<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["remove-video"])){video_remove();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["restart-auth"])){restart_auth();exit;}
if(isset($_GET["LOGS-SEARCH"])){searchInSyslog();exit;}
if(isset($_GET["RDPPROXY-SEARCH"])){searchInRDPPROXY();exit;}
if(isset($_GET["videos"])){videos_scan();exit;}
if(isset($_GET["move-logo"])){movelogo();exit;}
if(isset($_GET["upgrade"])){upgrade();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);

function upgrade(){
    $unix=new unix();
    $unix->framework_execute("exec.rdpproxy.php --upgrade","squid.rdpproxy.upgrade","squid.rdpproxy.upgrade.log");
}

function movelogo(){
    $file = "/usr/share/artica-postfix/ressources/conf/upload/{$_GET["move-logo"]}";
    $destfile="/usr/share/artica-postfix/img/rdpproxy/{$_GET["move-logo"]}";
    if(!is_file($destfile)){
        writelogs_framework("{$_GET["move-logo"]} remove $destfile",__FILE__,__LINE__);
        @unlink($destfile);
    }
    writelogs_framework("{$_GET["move-logo"]} copy $file -> $destfile",__FILE__,__LINE__);
    @copy($file,"/usr/share/artica-postfix/img/rdpproxy/{$_GET["move-logo"]}");
    @chmod($destfile,0755);
}

function restart(){
	$unix=new unix();
	$nohup=null;
	shell_exec("$nohup /etc/init.d/rdpproxy restart >/dev/null 2>&1 &");
}


function videos_scan(){
    $GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/squid.rdpproxy-videos.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/squid.rdpproxy-videos.progress.txt";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";
    @file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.rdpproxy.videos.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function video_remove(){
    $ID=intval($_GET["remove-video"]);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd="$php5 /usr/share/artica-postfix/exec.rdpproxy.videos.php --remove $ID >/dev/null 2>&1";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function status(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd="$php5 /usr/share/artica-postfix/exec.status.php --rdpproxy >/usr/share/artica-postfix/ressources/logs/web/rdpproxy.status 2>&1";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}




function searchInSyslog(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["LOGS-SEARCH"]));
    $filepath=$_GET["file"];
    $search=$MAIN["TERM"];
    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -iE '$search' $filepath |tail -n $max >".PROGRESS_DIR."/rdpproxy.syslog 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}



function searchInRDPPROXY(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["RDPPROXY-SEARCH"]));
    $filepath=$_GET["file"];
    $search=$MAIN["TERM"];
    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep -iE '$search' $filepath |tail -n $max >/usr/share/artica-postfix/ressources/logs/web/rdpproxy.daemon.log  2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

