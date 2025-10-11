<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["install-tgz"])){install_tgz();exit;}
if(isset($_GET["is-installed"])){is_installed();exit;}
if(isset($_GET["update-now"])){update_now();exit;}
if(isset($_GET["reconfigure-progress"])){reconfigure_progress();exit;}
if(isset($_GET["install-progress"])){install_progress();exit;}
if(isset($_GET["disable-progress"])){disable_progress();exit;}
if(isset($_GET["enable-progress"])){enable_progress();exit;}
if(isset($_GET["accesses"])){access_real();exit;}
if(isset($_GET["daemon-status"])){status();exit;}
if(isset($_GET["disable-sid"])){disable_sid();exit;}
if(isset($_GET["enable-sid"])){enable_sid();exit;}
if(isset($_GET["restart-tail"])){restart_tail();exit;}
if(isset($_GET["firewall-sid"])){firewall_sid();exit;}
if(isset($_GET["firewall-build"])){firewall_build();exit;}

if(isset($_GET["service-events"])){service_events();exit;}
foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);



function service_events():bool{
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["service-events"]));
    $filename=PROGRESS_DIR."/suricata.syslog";
    $TERM=null;
    foreach ($MAIN as $val=>$key){
        writelogs_framework("$val --- > $key",__FUNCTION__,__FILE__,__LINE__);
        $MAIN[$key]=str_replace(".", "\.", $MAIN[$key]);
        $MAIN[$key]=str_replace("*", ".*?", $MAIN[$key]);

    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    if($MAIN["TERM"]<>null){$search=".*?{$MAIN["TERM"]}";}
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/suricata/suricata-service.log |tail -n $max >$filename 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    return true;
}

function install_progress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR . "/firehol.reconfigure.progress";
	$GLOBALS["LOG_FILE"]=PROGRESS_DIR . "/firehol.reconfigure.log";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOG_FILE"],0777);
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.firehol.php --install-progress >{$GLOBALS["LOG_FILE"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
	
}


function restart_tail(){
	$unix=new unix();
	$unix->go_exec("/etc/init.d/suricata-tail restart");
}

function access_real(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$targetfile="/usr/share/artica-postfix/ressources/logs/firehol.log.tmp";
	$query2=null;
	$sourceLog="/var/log/syslog";
	$rp=intval($_GET["rp"]);
	writelogs_framework("access_real -> $rp search {$_GET["query"]}" ,__FUNCTION__,__FILE__,__LINE__);

	$query=$_GET["query"];
	$grep=$unix->find_program("grep");


	$cmd="$grep --binary-files=text -Ei \"FIREHOL:.*?IN=\" $sourceLog|$tail -n $rp >$targetfile 2>&1";

	if($query<>null){
		if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
			$pattern=str_replace(".", "\.", $query);
			$pattern=str_replace("*", ".*?", $pattern);
			$pattern=str_replace("/", "\/", $pattern);
		}
	}
	if($pattern<>null){
		$cmd="$grep --binary-files=text -Ei \"FIREHOL:.*?IN=.*?$pattern\" $sourceLog|$tail -n $rp  >$targetfile 2>&1";
	}



	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	@chmod("$targetfile",0755);
}


function status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 /usr/share/artica-postfix/exec.status.php --suricata --nowachdog >/usr/share/artica-postfix/ressources/logs/web/suricata.status");
	
}



function reconfigure_progress(){
	$unix=new unix();
	$comand="--reconfigure-progress";
    $unix->framework_execute("exec.suricata.php $comand","suricata.progress","suricata.progress.txt");

}



function disable_sid(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$sig=intval($_GET["sig"]);
	if($sig==0){return;}
	shell_exec("$nohup /usr/share/artica-postfix/bin/sidrule -d $sig >/dev/null 2>&1 &");
}
function enable_sid(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$sig=intval($_GET["sig"]);
	if($sig==0){return;}
	shell_exec("$nohup /usr/share/artica-postfix/bin/sidrule -e $sig >/dev/null 2>&1 &");	
}
function firewall_sid(){
	$unix=new unix();
	$sig=intval($_GET["sig"]);
	if($sig==0){
		writelogs_framework("$sig == 0 Aborting !!!!",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
    $unix->framework_exec("exec.suricata.php --firewall $sig");
}
function firewall_build(){
	$unix=new unix();
	$unix->framework_exec("exec.suricata-fw.php --run --force");
}



