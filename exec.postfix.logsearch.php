<?php
$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.familysites.inc");
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
$GLOBALS["FORCE"]=false;
$GLOBALS["EXECUTED_AS_ROOT"]=true;
$GLOBALS["RUN_AS_DAEMON"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["DISABLE_WATCHDOG"]=false;
if(preg_match("#--nowachdog#",$GLOBALS["COMMANDLINE"])){$GLOBALS["DISABLE_WATCHDOG"]=true;}
if(preg_match("#--force#",$GLOBALS["COMMANDLINE"])){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",$GLOBALS["COMMANDLINE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if($argv[1]=="--xsearch"){xsearch();exit;}
if($argv[1]=="--old"){search_in_old($argv[2],$argv[3],$argv[4]);exit;}



xrun($argv[1],$argv[2]);


function build_progress($text,$pourc){
	$echotext=$text;
	if(!is_dir("/usr/share/artica-postfix/ressources/logs")){@mkdir("/usr/share/artica-postfix/ressources/logs",0755,true);}
	if(is_numeric($text)){$old=$pourc;$pourc=$text;$text=$old;}
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." [INIT]: {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/postfix.events.{$GLOBALS["md5search"]}.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	file_put_contents($cachefile, serialize($array));
	if(!is_file($cachefile)){echo "$cachefile no such file!\n";}
	if($GLOBALS["VERBOSE"]){echo "$cachefile -> $pourc ok\n";}
	chmod($cachefile,0755);
}

function xrun($pattern,$md5){
    $unix=new unix();
    $GLOBALS["md5search"]=$md5;
	build_progress("{scanning}....",10);
    $tail=$unix->find_program("tail");
    $grep=$unix->find_program("grep");
    $array=unserialize(base64_decode($pattern));
    if(!is_array($array)){$array=array();}
    $finalfile="/usr/share/artica-postfix/ressources/logs/postfix.events.$md5.results";

    $DAYS_LEFT=intval($array["DAYS_LEFT"]);
    $MAX_LINES=intval($array["MAX_LINES"]);
    $search=trim($array["search"]);
    if($MAX_LINES==0){$MAX_LINES=250;}
    echo "DAYS_LEFT: $DAYS_LEFT\n";
    echo "MAX_LINES: $MAX_LINES\n";


    if($search<>null){
        if(preg_match("#^regex\s+(.+)#",$search,$re)){
            $search=$re[1];
        }else {
            $search = "*$search*";
            $search = str_replace("**", "*", $search);
            $search = StringToRegex($search);
        }

    }else{
        $DAYS_LEFT=0;
    }
    echo "search...: $search\n";

    if($DAYS_LEFT==0){
        if($search==null){
            $cmd="$tail -n $MAX_LINES /var/log/mail.log >$finalfile 2>&1";
            echo $cmd."\n";
            shell_exec($cmd);
            build_progress("{done}",100);
            return;
        }
        $cmd="$grep -E \"$search\" /var/log/mail.log|$tail -n $MAX_LINES >$finalfile 2>&1";
        echo $cmd."\n";
        shell_exec($cmd);
        build_progress("{done}",100);
        return;

    }



    $tempfile=$unix->FILE_TEMP();
    search_in_old($DAYS_LEFT,$search,$tempfile);
    build_progress("{searching} mail.log",80);
    $cmd="$grep -E \"$search\" /var/log/mail.log >>$tempfile 2>&1";
    echo $cmd."\n";
    shell_exec($cmd);
    build_progress("{searching} $MAX_LINES {lines}",90);
    $cmd="$tail -n $MAX_LINES $tempfile >$finalfile 2>&1";
    echo $cmd."\n";
    shell_exec($cmd);
    @unlink($tempfile);
    build_progress("{done}",100);
    return;



	
}

function search_in_old($dayleft,$stringTosearch,$tempfile){

    $unix=new unix();
    $zgrep=$unix->find_program("zgrep");
    $BackupMaxDaysDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir"));
    if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
    $FinalDir=$BackupMaxDaysDir."/mail";
    echo "BackupMaxDaysDir.....: $BackupMaxDaysDir\n";
    echo "FinalDir.............: $FinalDir\n";
    echo "Day left.............: $dayleft\n";
    echo "String...............: $stringTosearch\n";

    for($i=1;$i<$dayleft+1;$i++){

        $xtime=strtotime("-$i days");
        $DayToScan=date("Y",$xtime)."/".date("m",$xtime)."/".date("d",$xtime);
        $DirToscan=$FinalDir."/$DayToScan";
        $DIRS[$xtime]=$DirToscan;
    }

    build_progress("{searching}",20);
    ksort($DIRS);
    $i=5;
    foreach ($DIRS as $time=>$dir){
        $i=$i+5;
        $prc=20+$i;
        if($prc>80){$prc=80;}

        echo "Find File in $dir\n";
        $files=$unix->DirFiles($dir,"mail-[0-9\-]+\.gz$");
        foreach ($files as $filename){
            build_progress("{searching} $filename",$prc);
            echo "\t$dir/$filename\n";
            $cmdline="$zgrep -e \"$stringTosearch\" $dir/$filename >>$tempfile";
            echo "\t$cmdline";
            shell_exec($cmdline);
        }


    }


}


function StringToRegex($pattern){
    $pattern=str_replace("/", "\/", $pattern);
    $pattern=str_replace(".", "\.", $pattern);
    $pattern=str_replace("(", "\(", $pattern);
    $pattern=str_replace(")", "\)", $pattern);
    $pattern=str_replace("+", "\+", $pattern);
    $pattern=str_replace("?", "\?", $pattern);
    $pattern=str_replace("[", "\[", $pattern);
    $pattern=str_replace("]", "\]", $pattern);
    $pattern=str_replace("*", ".*", $pattern);
    return $pattern;

}

	
function xsearch($ID,$timeSearch,$PostfixHistorySearch,$c){	
	$unix=new unix();
	$BackupMaxDaysDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir"));
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	
	$SearchDir="$BackupMaxDaysDir/mail";
	if(!is_dir("/home/artica/postfix/history")){
		@mkdir("/home/artica/postfix/history",0755,true);
	}
	
	
	
	
	if(!preg_match("#regex\s+(.+)#", $PostfixHistorySearch,$re)){
		$PostfixHistorySearch=str_replace(".", "\.", $PostfixHistorySearch);
		$PostfixHistorySearch=str_replace("*", ".*?", $PostfixHistorySearch);
		$PostfixHistorySearch=str_replace("[", "\[", $PostfixHistorySearch);
		$PostfixHistorySearch=str_replace("]", "\]", $PostfixHistorySearch);
		$PostfixHistorySearch=str_replace("(", "\(", $PostfixHistorySearch);
		$PostfixHistorySearch=str_replace(")", "\)", $PostfixHistorySearch);
		$PostfixHistorySearch=str_replace("/", "\/", $PostfixHistorySearch);
	}else{
		$PostfixHistorySearch=$re[1];
	}

	$grep=$unix->find_program("grep");
	$find=$unix->find_program("find");
	$ListOfFiles=array();
	$FINAL_FILES[time()]="/var/log/mail.log";

	build_progress("{scanning} $SearchDir....",$c++);
	exec("$find $SearchDir|$grep -E \"\.gz\"",$ListOfFiles);
	$FINAL_FILES=array();
	foreach ($ListOfFiles as $ligne){
		$filepath=$ligne;
		$basename=basename($filepath);
		$dirname=dirname($filepath);
		$dirname=str_replace("$SearchDir/", "", $dirname);
		$dirname=str_replace("/", "-", $dirname)." 00:00:00";
		$dirtime=strtotime($dirname);
		if($timeSearch>$dirtime){continue;}
		echo "Will search in $filepath $dirtime ==> (".date("Y-m-d H:i:s",$dirtime).")\n";
		$FINAL_FILES[$dirtime]=$filepath;
		
	}
	
	build_progress("{scanning} ".count($FINAL_FILES)." files ",$c++);
	
	ksort($FINAL_FILES);
	
	$unix=new unix();
	$zcat=$unix->find_program("zcat");
	
	foreach ($FINAL_FILES as $notime=>$path){
		$c++;
		if($c>90){$c=90;}
		build_progress("{scanning} ".basename($path)."....",$c++);
	
		$cmd="$zcat $path | $grep --binary-files=text -Ei \"$PostfixHistorySearch\" >>/home/artica/postfix/history/$ID.tmp 2>&1";
		echo $cmd."\n";
		shell_exec($cmd);
	}
	
	$countlines=$unix->COUNT_LINES_OF_FILE("/home/artica/postfix/history/$ID.tmp");
	echo "Final === $countlines\n";
	return $c;
	
}