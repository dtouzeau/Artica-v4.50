<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["syslog"])){searchInSyslog();exit;}
if(isset($_GET["client-status"])){client_status();exit;}
if(isset($_GET["action-trusted"])){action_trusted();exit;}
if(isset($_GET["service-events"])){service_events();exit;}
if(isset($_GET["decisions-list"])){decisions_list();exit;}
if(isset($_GET["decision-add"])){decisions_add();exit;}

if(isset($_GET["collection-remove"])){collection_remove();exit;}
if(isset($_GET["ipset-status"])){ipset_status();exit;}
if(isset($_GET["ipset-list"])){ipset_list();exit;}
if(isset($_GET["restart-custom-bouncer"])){restart_customer_bounder();exit;}


$f=array();
foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);

function collection_remove():bool{
    $name=$_GET["collection-remove"];
    exec("/usr/local/sbin/cscli collections remove  $name --force 2>&1",$results);
    $unix=new unix();
    return $unix->framework_exec("exec.crowdsec.php --tasks-collections");
}



function ipset_status():bool{
    $tfile=PROGRESS_DIR."/crowdsec-blacklists.status";
    exec("/usr/sbin/ipset list crowdsec-blacklists -terse 2>&1",$results);
    @file_put_contents($tfile,@implode("\n",$results));
    @chmod($tfile,0755);
    return true;
}
function ipset_list():bool{
    $tfile=PROGRESS_DIR."/crowdsec-blacklists.list";
    $cmd="/usr/sbin/ipset list crowdsec-blacklists >$tfile 2>&1";
    shell_exec($cmd);
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    @chmod($tfile,0755);
    return true;
}

function status():bool{
	$unix=new unix();
    return $unix->framework_exec("exec.status.php --crowdsec");
}

function restart_customer_bounder(){
    $unix=new unix();
    return $unix->framework_execute("exec.crowdsec.php --restart-customer-bouncer");
}

function client_status(){
	$unix=new unix();
	$fail2banclient=$unix->find_program("fail2ban-client");
	writelogs_framework("$fail2banclient -s /var/run/fail2ban/fail2ban.sock status >/usr/share/artica-postfix/ressources/logs/fail2ban.client.status" ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$fail2banclient -s /var/run/fail2ban/fail2ban.sock status >/usr/share/artica-postfix/ressources/logs/fail2ban.client.status");
}
function restart(){
	$migration=null;


	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/fail2ban.restart.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/fail2ban.restart.progress.txt";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.fail2ban.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function uninstall():bool{
	$unix=new unix();
    return $unix->framework_execute("exec.crowdsec.php --uninstall","crowdsec.progress","crowdsec.progress.log");

}
function install():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.crowdsec.php --install","crowdsec.progress","crowdsec.progress.log");

}

function searchInSyslog(){
	$unix=new unix();
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$MAIN=unserialize(base64_decode($_GET["syslog"]));
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

	if($PID<>null){$PID_P=".*?\[$PID\].*?";}
	if($IN<>null){$IN_P="\s+.*?$IN.*?";}
	if($SRC<>null){$IN_P="\s+.*?$SRC.*?";}
	if($DST<>null){$IN_P="\s+.*?$DST.*?";}
	if($MAIN["C"]==0){$TERM_P=$TERM;}


	$mainline="{$PID_P}{$TERM_P}{$IN_P}";
	if($TERM<>null){
		if($MAIN["C"]>0){
			$mainline="($mainline|$TERM)";
		}
	}



	$search="$date.*?$mainline";
	$search=str_replace(".*?.*?",".*?",$search);
	$cmd="$grep --binary-files=text -i -E '$search' /var/log/fail2ban.log |tail -n $max >/usr/share/artica-postfix/ressources/logs/web/fail2ban.syslog 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/fail2ban.syslog.pattern", $search);
	shell_exec($cmd);

}
function decisions_add():bool{
    $unix=new unix();
    $ipaddr=$_GET["decision-add"];
    $time=intval($_GET["time"]);
    $desc=base64_decode($_GET["desc"]);
    $type="--ip";
    if(strpos("/",$ipaddr)>0){
        $type="--range";
    }
    $cmdline="/usr/local/sbin/cscli decisions add $type $ipaddr --duration {$time}m --reason \"$desc\"";
    writelogs_framework($cmdline);
    shell_exec($cmdline);
    return $unix->framework_exec("exec.crowdsec.php --tasks-decisions");
}

function service_events():bool{
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["service-events"]));
    $filename=PROGRESS_DIR."/crowdsec.syslog";
    $TERM=null;
    foreach ($MAIN as $val=>$key){
        writelogs_framework("$val --- > $key",__FUNCTION__,__FILE__,__LINE__);
        $MAIN[$key]=str_replace(".", "\.", $MAIN[$key]);
        $MAIN[$key]=str_replace("*", ".*?", $MAIN[$key]);

    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    if($MAIN["TERM"]<>null){$search=".*?{$MAIN["TERM"]}";}
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/crowdsec/crowdsec.log |tail -n $max >$filename 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    return true;
}

