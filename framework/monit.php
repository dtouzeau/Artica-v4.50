<?php
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["allstatus"])){allstatus();exit;}
if(isset($_GET["status"])){status_info();exit;}
if(isset($_GET["chock-status"])){chock_status();exit;}
if(isset($_GET["monit-status"])){status();exit;}
if(isset($_GET["delete-cache"])){delete_cache();exit;}
if(isset($_GET["sync-freewebs"])){sync_freewebs();exit;}
if(isset($_GET["www-events"])){www_events();exit;}
if(isset($_GET["mysqldb-restart"])){mysqldb_restart();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["monitor"])){monitor();exit;}
if(isset($_GET["restart-app"])){restart_app();exit;}
if(isset($_GET["reload"])){reload();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["htop"])){htop();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);

function status_info(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	exec("$php5 /usr/share/artica-postfix/exec.status.php --monit --nowachdog 2>&1",$results);
	echo "<articadatascgi>".base64_encode(@implode("\n", $results))."</articadatascgi>";

}

function allstatus(){
    $unix           = new unix();
    $monit          = $unix->find_program("monit");
    $cache_file     = PROGRESS_DIR."/monit.all.status";
    shell_exec("$monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid -s /var/run/monit/monit.state status >$cache_file 2>&1");
}

function reload() {
    $unix           = new unix();
    $monit          = $unix->find_program("monit");
    shell_exec("$monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
}

function monitor(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$monit=$unix->find_program("monit");
	$cmd="$monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid -s /var/run/monit/monit.state monitor {$_GET["monitor"]}";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function restart_app(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$monit=$unix->find_program("monit");
	$cmd="$monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid -s /var/run/monit/monit.state restart {$_GET["restart-app"]}";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function htop(){
    $tfile=PROGRESS_DIR."/htop.status";
    $opts="--black --line-fix";
    $cmd="/usr/bin/echo q | /usr/bin/htop -C | /usr/bin/aha  >$tfile 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    @chmod($tfile,0755);
}




function sync_freewebs(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.freeweb.php --sync-squid");
	
}
function status(){
	$unix=new unix();
	$cache_file=PROGRESS_DIR."/monit.status.all";
    if(is_file($cache_file)) {
        $array = unserialize(@file_get_contents($cache_file));
        echo "<articadatascgi>" . base64_encode(serialize($array)) . "</articadatascgi>";
    }
}
function chock_status(){
// depreciated
}


function restart():bool{
	$unix=new unix();
    return $unix->framework_execute("exec.monit.php --restart","exec.monit.progress","exec.monit.progress.txt");
}

function delete_cache(){
	
	$directory=base64_decode($_GET["delete-cache"]);
	if(trim($directory)==null){return;}
	if(!is_dir($directory)){return;}
	$unix=new unix();
	if($unix->IsProtectedDirectory($directory,true)){return;}
	$rm=$unix->find_program("rm");
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $rm -rf \"$directory\" >/dev/null 2>&1 &");
}
function www_events(){
	$servername=$_GET["servername"];
	$port=$_GET["port"];	
	$type=$_GET["type"];
	$filename="/var/log/apache2/$servername/nginx.access.log";
	if($type==2){
		$filename="/var/log/apache2/$servername/nginx.error.log";
	}
	$search=$_GET["search"];
	$unix=new unix();
	$search=$unix->StringToGrep($search);
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$refixcmd="$tail -n 2500 $filename";
	if($search<>null){
		$refixcmd=$refixcmd."|$grep --binary-files=text -i -E '$search'|$tail -n 500";
	}else{
		$refixcmd="$tail -n 500 $filename";
	}
	
	
	exec($refixcmd." 2>&1",$results);
	writelogs_framework($refixcmd." (".count($results).")",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
	
}
function mysqldb_restart(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$php5 /usr/share/artica-postfix/exec.nginx-db.php --init");
	shell_exec("$nohup /etc/init.d/nginx-db restart >/dev/null 2>&1");
}
function events(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["events"]));
    $PROTO_P=null;

    foreach ($MAIN as $val=>$key){
        $MAIN[$key]=str_replace(".", "\.", $MAIN[$key]);
        $MAIN[$key]=str_replace("*", ".*?", $MAIN[$key]);

    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    $date=$MAIN["DATE"];
    $PROTO=$MAIN["PROTO"];
    $SRC=$MAIN["SRC"];
    $DST=$MAIN["DST"];
    $SRCPORT=$MAIN["SRCPORT"];
    $DSTPORT=$MAIN["DSTPORT"];
    $IN=$MAIN["IN"];
    $OUT=$MAIN["OUT"];
    $MAC=$MAIN["MAC"];
    $PID=$MAIN["PID"];
    if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}

    if($PID<>null){$PID_P=".*?sshd\[$PID\].*?";}
    if($IN<>null){$IN_P="(from|to)\s+.*?$IN.*?";}
    if($SRC<>null){$IN_P="(from|to)\s+.*?$SRC.*?";}
    if($DST<>null){$IN_P="(from|to)\s+.*?$DST.*?";}
    if($MAIN["C"]==0){$TERM_P=$TERM;}


    $mainline="{$PID_P}{$TERM_P}{$IN_P}";
    if($TERM<>null){
        if($MAIN["C"]>0){
            $mainline="($mainline|$TERM)";
        }
    }



    $search="$mainline";
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/monit.log |tail -n $max >/usr/share/artica-postfix/ressources/logs/web/monit.syslog 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents(PROGRESS_DIR."/monit.syslog.pattern", $search);
    shell_exec($cmd);

}