<?php
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
$GLOBALS["NOCHECK"]=false;
$GLOBALS["RELOAD"]=false;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["CLUSTER"]=false;
$GLOBALS["squid-verif"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);

if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--reload#",implode(" ",$argv),$re)){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--nochek#",implode(" ",$argv))){$GLOBALS["NOCHECK"]=true;}
if(preg_match("#--cluster#",implode(" ",$argv))){$GLOBALS["CLUSTER"]=true;}
if(preg_match("#--verif#",implode(" ",$argv))){$GLOBALS["squid-verif"]=true;}

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.remote-stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.checks.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.access.manager.inc');
include_once(dirname(__FILE__).'/ressources/class.products-ip-ranges.inc');

if(isset($argv[1])){
    if($argv[1]=="--import"){import_blacklist_file($argv[2]);exit;}

}

function build_progress_import($text,$pourc):bool{
    $unix=new unix();
    return $unix->framework_progress($pourc,$text,"squid.wb.import.progress");
}


function import_blacklist_file($filepath):bool{
    $unix=new unix();

    $filepath="/usr/share/artica-postfix/ressources/conf/upload/$filepath";
    if(!is_file($filepath)){
        echo "$filepath No such file\n";
        return build_progress_import("{failed}",110);
    }

    $Max=$unix->COUNT_LINES_OF_FILE($filepath);
    echo "$Max lines to parse...\n";
    build_progress_import("$Max lines to parse...",10);
    $fp = @fopen($filepath, "r");
    if(!$fp){
        echo "$filepath BAD FD\n";
        @unlink($filepath);
        return build_progress_import("{failed}",110);
    }
    if($Max==0){
        echo "$filepath division by 0\n";
        @unlink($filepath);
        return build_progress_import("division by 0",110);
    }

    $n=array();
    $c=0;
    $prc_mem=0;
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $q->QUERY_SQL("DELETE FROM deny_websites");

    while(!feof($fp)) {
        $line = trim(fgets($fp));
        $line = trim($line);
        $c++;
        if(strpos(" $line","#")>0){continue;}
        if(strpos(" $line",";")>0){continue;}
        if(strpos(" $line",",")>0){continue;}
        if(strpos(" $line","'")>0){continue;}
        if(strpos(" $line","\"")>0){continue;}
        if(strpos(" $line","^")>0){continue;}
        if(substr($line,0,1)=="."){
            $line=substr($line,1,strlen($line));
        }
        if($c>0) {

            $prc1 = $c / $Max;
            $prc = round($prc1) * 100;
            if ($prc > 98) {$prc = 98;}
            if ($prc > 10) {
                if ( $prc > $prc_mem) {
                    build_progress_import($line, $prc);
                    $prc_mem = $prc;
                }
            }
        }

        $n[]="('$line')";
        if(count($n)>4000){
            $q->QUERY_SQL("INSERT OR IGNORE INTO `deny_websites` (`items`) VALUES ".@implode(",", $n));
            if(!$q->ok){
                echo $q->mysql_error."\n";
                return build_progress_import("MySQL error",110);
            }
            $n=array();
        }
    }
    fclose($fp);
    @unlink($filepath);
    if(count($n)>0){
        $q->QUERY_SQL("INSERT OR IGNORE INTO `deny_websites` (`items`) VALUES ".@implode(",", $n));
        if(!$q->ok){
            echo $q->mysql_error."\n";
            return build_progress_import("MySQL error",110);
        }
    }


    return build_progress_import("{success}",100);
}

function squidprivs($path){
	@chmod($path, 0755);
	@chown($path,"squid");
	@chgrp($path, "squid");	
	
}

function build_IsInSquid():bool{
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	foreach ($f as $val){
		if(preg_match("#include.*?acls_whitelist\.conf#i", $val)){return true;}

	}
    return false;
}


function string_to_regex_3($pattern):string{
    if(is_null($pattern)){return "";}
	if(preg_match("#^regex:(.+)#", trim($pattern),$re)){return $re[1];}


	$pattern=str_replace(".", "\.", $pattern);
	$pattern=str_replace("(", "\(", $pattern);
	$pattern=str_replace(")", "\)", $pattern);
	$pattern=str_replace("+", "\+", $pattern);
	$pattern=str_replace("|", "\|", $pattern);
	$pattern=str_replace("{", "\{", $pattern);
	$pattern=str_replace("}", "\}", $pattern);
	$pattern=str_replace("?", "\?", $pattern);
	$pattern=str_replace("http://", "^http://", $pattern);
	$pattern=str_replace("https://", "^https://", $pattern);
	$pattern=str_replace("ftp://", "^ftp://", $pattern);

    if(preg_match("#^\*-(.+)#",$pattern,$re)){
        $pattern="-$re[1]";
    }
    if(preg_match("#^(.+?)\*$#",$pattern,$re)){
        $pattern=$re[1];
    }

    if(preg_match("#^\*\\.(.+)#",$pattern,$re)){
        $pattern="(^|\.)$re[1]";
    }

    if(preg_match("#^\*(.+)#",$pattern,$re)){
        $pattern="(^|\.)$re[1]";
    }
    $pattern=str_replace("(^|\.)\.", "(^|\.)", $pattern);
    return str_replace("ftps://", "^ftps://", $pattern);

}