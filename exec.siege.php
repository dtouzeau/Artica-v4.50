<?php
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}


if($argv[1]=="--clean-urls"){clean_urls();exit;}
if($argv[1]=="--import"){import_urls();exit;}
if($argv[1]=="--analyze-access"){analyze_access($argv[2]);exit;}


xrun();

function analyze_access($fname){
    $bin="/usr/share/artica-postfix/bin/accesslog.py";
    @chmod($bin,0755);
    $unix=new unix();
    $fname=$unix->shellEscapeChars($fname);
    shell_exec("$bin $fname >/usr/share/artica-postfix/ressources/logs/web/access.log.parser.debug 2>&1");

}

function xrun(){
$unix=new unix();	
$siege="/usr/share/artica-postfix/bin/siege";
$ARRAY=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSiegeConfig"));
if(!is_numeric($ARRAY["SESSIONS"])){$ARRAY["SESSIONS"]=150;}
if(!is_numeric($ARRAY["MAX_TIME"])){$ARRAY["MAX_TIME"]=30;}
if(!isset($ARRAY["CONNECTION"])){$ARRAY["CONNECTION"]="keep-alive";}

build_progress_disconnect("{starting}",5);

if(!is_file($siege)){
    $siege=$unix->find_program("siege");
    if(!is_file($siege)) {
        build_progress_disconnect("{please_wait} {installing} SIEGE", 50);
        $unix->DEBIAN_INSTALL_PACKAGE("siege");
        $siege=$unix->find_program("siege");
        if (!is_file($siege)) {
            build_progress_disconnect("{installing} SIEGE {failed}", 110);
        }
    }
}
$f[]="failures = 50064";
$f[]="internet = true";
$f[]="delay = 0.0";
$addr=$ARRAY["REMOTE_PROXY"];
$port=intval($ARRAY["REMOTE_PROXY_PORT"]);
$target="$addr:$port";


if($ARRAY["SESSIONS"]==0){
	build_progress_disconnect("{failed} {simulate} 0 sessions",110);
	return false;
}

$SIEGE_URLS=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SIEGE_URLS");
if($SIEGE_URLS==null){$SIEGE_URLS=@file_get_contents("/usr/share/artica-postfix/bin/install/squid/urls.txt");}
@file_put_contents("/etc/siege/urls.txt",$SIEGE_URLS);

if($addr<>null && $port>0) {
    $f[] = "proxy-host =$addr";
    $f[] = "proxy-port = $port";
}
$reps=3;

    $URLS_NUMBER=$unix->COUNT_LINES_OF_FILE("/etc/siege/urls.txt");
    $urls_count=$URLS_NUMBER;

//$f[]="connection = {$ARRAY["CONNECTION"]}";
$f[]="user-agent = Mozilla/5.0 (compatible; IE 11.0; Win32; Trident/7.0)";
$f[]="file = /etc/siege/urls.txt";
$f[]="concurrent = {$ARRAY["SESSIONS"]}";
$f[]="time = {$ARRAY["MAX_TIME"]}S";
$f[]="timeout = 5";
$f[]="logfile = /var/log/siege.log";
if(trim($ARRAY["USERNAME"])<>null){
	$f[]="username = {$ARRAY["USERNAME"]}";
	$f[]="password = {$ARRAY["PASSWORD"]}";
}
@file_put_contents("/root/.siegerc", @implode("\n", $f));
$filetemp=$unix->FILE_TEMP();
$nohup=$unix->find_program("nohup");

if(!is_file("/root/.siegerc")){
    echo "Error writting /root/.siegerc\n";
    build_progress_disconnect("{failed}",110);
}


$start_time=date("Y-m-d H:i:s");
    build_progress_disconnect("{analyze} urls",40);
    clean_urls();
$ss[]="$nohup $siege --concurrent={$ARRAY["SESSIONS"]}";
$ss[]="--internet --file=/etc/siege/urls.txt --time={$ARRAY["MAX_TIME"]}S"; 
$ss[]="--benchmark --delay=0.1 --quiet --json-output --rc=/root/.siegerc >$filetemp 2>&1 &";
$cmd=@implode(" ", $ss);
echo "$cmd\n";
build_progress_disconnect("{executing}",50);

system($cmd);	
sleep(2);
$pid=$unix->PIDOF($siege);
while ($unix->process_exists($pid)){
	$array_mem=getSystemMemInfo();
	$MemFree=$array_mem["MemFree"];
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	echo "Memory Free: ".round($MemFree/1024)." MB\n";
	echo "Load: $internal_load\n";
	build_progress_disconnect("{please_wait} Load:$internal_load",50);
	sleep(2);
	$pid=$unix->PIDOF($siege);
}

build_progress_disconnect("{please_wait} {analyze}...",90);
$content=@file_get_contents($filetemp);
$zend=date("Y-m-d H:i:s");
build_progress_disconnect("{done}...",99);
sleep(5);

$php=$unix->LOCATE_PHP5_BIN();
shell_exec("$php /usr/share/artica-postfix/exec.convert-to-sqlite.php");
$q=new lib_sqlite("/home/artica/SQLITE/siege.db");

    $sql="CREATE TABLE IF NOT EXISTS reports ( 
    `ID` INTEGER PRIMARY KEY AUTOINCREMENT, 
     users INTEGER,
     target TEXT,
    `zdate` TEXT, 
    `zend` TEXT,                                   
    `subject` TEXT,
     report TEXT                               
    )";

    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $q->mysql_error."\n";
        build_progress_disconnect("{failed} while creating database",110);
        return false;
    }

$content=base64_encode($content);
$sql="INSERT INTO reports (users, target, zdate, zend,subject,report)
VALUES ('{$ARRAY["SESSIONS"]}','$target','$start_time','$zend','Report of $start_time $urls_count urls','$content')";
$q->QUERY_SQL($sql);
if(!$q->ok){
    echo $q->mysql_error;
    build_progress_disconnect("{failed}...",110);
    return false;
}
    build_progress_disconnect("{done}...",100);
return true;
}

function build_progress_disconnect($text,$pourc){
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"squid.siege.progress");
}


function import_urls(){



	$handle = @fopen("/var/log/squid/access.log", "r");
	if (!$handle) {echo "Failed to open file\n";return;}
	
	while (!feof($handle)){
		
		$www =trim(fgets($handle, 4096));

        if(preg_match("#CONNECT (.+?):([0-9]+)#",$www,$re)){
            if($re[2]==443){$re[2]=0;}
            if($re[2]>0) {
                $array["https://{$re[1]}:{$re[2]}/"] = true;
                continue;
            }
            $array["https://{$re[1]}/"] = true;
            continue;
        }
        if(preg_match("#POST (.+?)\s+#",$www,$re)){
            $array["https://{$re[1]} POST val=1&val2=2"]=true;
            continue;
        }


		if(!preg_match("#GET http(.+?)\s+#", $www,$re)){
            if($GLOBALS["VERBOSE"]){echo "No matches $www\n";}
            continue;}
		$array["http{$re[1]}"]=true;

		
		
	}
	
	foreach ($array as $num=>$val){
		$f[]=$num;
	}
	$array=array();
	@mkdir("/etc/siege");
	@file_put_contents("/etc/siege/urls.txt", @implode("\n", $f));
	$f=array();
	build_progress_disconnect(count($f)." urls saved",20);

}

function clean_urls(){
    $f=explode("\n",@file_get_contents("/etc/siege/urls.txt"));
    $MAIN=array();
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#^(http|https|ftp|ftps):\/\/#",$line)){continue;}
        $array=parse_url($line);
        if(!isset($array["host"])){continue;}
        if(!isset($array["scheme"])){continue;}
        $MAIN[strtolower($line)]=$line;
    }
    $c=0;
    $fa=array();
    foreach ($MAIN as $urls=>$urls2){
        $c++;

        $fa[]=$urls2;
        if($c>9999){break;}
    }
    echo count($fa). " items...\n";
    @file_put_contents("/etc/siege/urls.txt",@implode("\n",$fa));

}