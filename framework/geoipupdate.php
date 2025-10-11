<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["databases"])){databases();exit;}
if(isset($_GET["update"])){update();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["reload"])){reload();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["systemusers"])){systemusers();exit;}
if(isset($_GET["restart-progress"])){restart_progress();exit;}
if(isset($_GET["install-tgz"])){install_tgz();exit;}
if(isset($_GET["chowndirs"])){chowndirs();exit;}
if(isset($_GET["ufdb-real"])){searchInSyslog();exit;}
if(isset($_GET["syslog"])){searchInSyslog();exit;}
if(isset($_GET["requests"])){searchInRequests();exit;}
if(isset($_GET["run"])){Runas();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);


function Runas(){
    $unix=new unix();
    $unix->framework_execute("exec.geoipupdate.php --run","GeoipUpdate.progress","GeoipUpdate.log");
}

function databases(){
    $unix=new unix();
    $Files=$unix->DirFiles("/usr/local/share/GeoIP");

    foreach ($Files as $filename=>$none){
        $ffilepath="/usr/local/share/GeoIP/$filename";
        $time=filemtime($ffilepath);
        $size=@filesize($ffilepath);
        $ARRAY[$filename]["TIME"]=$time;
        $ARRAY[$filename]["SIZE"]=$size;
    }

    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/GeoIP.db",serialize($ARRAY));

}

function restart(){
	$migration=null;
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/DNSCryptProxy.restart.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/DNSCryptProxy.restart.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.DnsCryptProxy.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function update(){
    $migration=null;
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/DNSCryptProxy.update.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/DNSCryptProxy.update.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.DnsCryptProxy.php --update >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}





function install(){
	$migration=null;
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/GeoipUpdate.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/GeoipUpdate.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.geoipupdate.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function uninstall(){
    $migration=null;
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/GeoipUpdate.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/GeoipUpdate.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.geoipupdate.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function restart_progress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/DNSCryptProxy.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/DNSCryptProxy.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["CACHEFILE"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.DnsCryptProxy.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
		
	
}
function reload(){
	restart_progress();


}
function searchInRequests(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["requests"]));
    $TERM=null;$TERM_P=null;

    foreach ($MAIN as $val=>$key){
        writelogs_framework("$val -> $key",__FUNCTION__,__FILE__,__LINE__);
        $MAIN[$key]=str_replace(".", "\.", $MAIN[$key]);
        $MAIN[$key]=str_replace("*", ".*?", $MAIN[$key]);
    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}
    if($MAIN["C"]==0){$TERM_P=$TERM;}
    $search=str_replace(".*?.*?",".*?",$TERM_P);
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/dnscrypt-proxy/query.log |$tail -n $max >/usr/share/artica-postfix/ressources/logs/web/dnscrypt-proxy.queries 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function searchInSyslog(){
	$unix=new unix();
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$MAIN=unserialize(base64_decode($_GET["syslog"]));
    $TERM=null;$TERM_P=null;

	foreach ($MAIN as $val=>$key){
        $MAIN[$key]=str_replace(".", "\.", $MAIN[$key]);
		$MAIN[$key]=str_replace("*", ".*?", $MAIN[$key]);
	}

	$max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
	if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}
    if($MAIN["C"]==0){$TERM_P=$TERM;}

    $search=str_replace(".*?.*?",".*?",$TERM_P);
	$cmd="$grep --binary-files=text -i -E '\s+dnscrypt-proxy\[.*?$search' /var/log/syslog |$tail -n $max >/usr/share/artica-postfix/ressources/logs/web/dnscrypt-proxy.search 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/dnscrypt-proxy.pattern", "\s+dnscrypt-proxy\[.*?$search");
	shell_exec($cmd);

}