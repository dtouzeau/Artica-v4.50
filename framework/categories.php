<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["categorize-export"])){categorize_export();exit;}
if(isset($_GET["categorize-import"])){categorize_import();exit;}
if(isset($_GET["categorize"])){categorize();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["reload"])){reload();exit;}
if(isset($_GET["restart"])){restart_progress();exit;}
if(isset($_GET["systemusers"])){systemusers();exit;}
if(isset($_GET["restart-progress"])){restart_progress();exit;}
if(isset($_GET["install-tgz"])){install_tgz();exit;}
if(isset($_GET["chowndirs"])){chowndirs();exit;}
if(isset($_GET["ufdb-real"])){searchInSyslog();exit;}
if(isset($_GET["categorize-bulk"])){categorize_bulk();exit;}
if(isset($_GET["externals"])){externals();exit;}
if(isset($_GET["search-in-logs"])){SearchEventsInSyslog();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);


function status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --3proxy >/usr/share/artica-postfix/ressources/logs/web/3proxy.status 2>&1";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function externals(){
}
function SearchEventsInSyslog():bool{
    $unix=new unix();
    $unix->framework_search_syslog($_GET["search-in-logs"],
        "/var/log/webfiltering-categories.log",
        "webfiltering-categories.log","webfiltering-categories.pattern");

    return true;
}

function categorize_bulk(){
    $unix=new unix();
    $migration=null;
    $t=$_GET["categorize-bulk"];
    $ft=$t;
    if(is_file("/usr/share/artica-postfix/ressources/conf/upload/$t")){
        $ft=md5($t);
        $t=$unix->shellEscapeChars($t);
    }

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/categorize.$ft.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/categorize.$ft.logs.txt";

    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    @chmod($ARRAY["LOG_FILE"],0777);

    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.categorize.php --bulk $t >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function categorize(){
	$migration=null;
    $t=$_GET["categorize"];
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/categorize.$t.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/categorize.$t.logs.txt";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.categorize.php $t >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}

function categorize_import(){

    $t=$_GET["category_id"];
    $ForceCat=$_GET["ForceCat"];
    $ForceExt=$_GET["ForceExt"];
    $filename=$_GET["categorize-import"];
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/categorize.$t.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/categorize.$t.logs.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.categorize.php --import $t \"$filename\" $ForceCat $ForceExt >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function categorize_export(){
    $t=$_GET["categorize-export"];
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/categorize.$t.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/categorize.$t.logs.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.categorize.php --export $t >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}




function systemusers(){
	
	
	$f=explode("\n",@file_get_contents("/etc/passwd"));
	foreach ($f as $num=>$line){
		if(!preg_match("#(.+?):x:([0-9]+):([0-9]+):#", $line,$re)){continue;}
		$ARRAYU["{$re[2]}:{$re[3]}"]=$re[1];
		
		
	}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SystemUsers", serialize($ARRAYU));
	@chmod("/etc/artica-postfix/settings/Daemons/SystemUsers",0755);
	
}
function searchInSyslog(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	
	$year=date("Y");
	$month=date("m");
	$day=date("d");
	
	$curfile="log.$year.$month.$day";
	
	$targetfile="/usr/share/artica-postfix/ressources/logs/3proxy.log.tmp";
	$sourceLog="/var/log/3proxy/$curfile";
	$grep=$unix->find_program("grep");
	
	$rp=intval($_GET["rp"]);
	$query=$_GET["query"];
	$cmd="$tail -n $rp $sourceLog >$targetfile 2>&1";

	if($query<>null){
		if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
			$pattern=str_replace(".", "\.", $query);
			$pattern=str_replace("*", ".*?", $pattern);
			$pattern=str_replace("/", "\/", $pattern);
		}
	}
	if($pattern<>null){

		$cmd="$grep --binary-files=text -Ei \"$pattern\" $sourceLog| $tail -n $rp  >$targetfile 2>&1";
	}
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/3proxy.log.cmd", $cmd);
	shell_exec($cmd);
	@chmod("$targetfile",0755);

}
