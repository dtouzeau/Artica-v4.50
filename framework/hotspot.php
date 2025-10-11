<?php
$GLOBALS["CACHE_FILE"]="/etc/artica-postfix/iptables-hostspot.conf";
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["ArticaHotSpotInterface"])){ArticaHotSpotInterface();exit;}
if(isset($_GET["buildconf"])){buildconf();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["events"])){searchInSyslog();exit;}
if(isset($_GET["download-events"])){download_events();exit;}
if(isset($_GET["empty-events"])){empty_events();exit;}
foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);



function empty_events():bool{
    $unix=new unix();
    $echo=$unix->find_program("echo");
    shell_exec("$echo \"\" > /var/log/squid/hotspot.log");
    return true;
}
function download_events():bool{
    $unix = new unix();
    return $unix->compress("/var/log/squid/hotspot.log", PROGRESS_DIR . "/hotspot.log.gz");
}



function searchInSyslog(){
    $unix=new unix();
    $tail=$unix->find_program("tail");
    $targetfile="/usr/share/artica-postfix/ressources/logs/hotspot.log.tmp";
    $sourceLog="/var/log/squid/hotspot.log";
    $grep=$unix->find_program("grep");
    $rp=intval($_GET["rp"]);
    $query=$_GET["query"];
    $cmd="$tail -n $rp $sourceLog >$targetfile 2>&1";
    $pattern=null;
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
    shell_exec($cmd);
    @chmod("$targetfile",0755);

}

function ArticaHotSpotInterface(){
	
	$ArticaHotSpotInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHotSpotInterface");
	$ArticaSplashHotSpotPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaSplashHotSpotPort"));
	$ArticaSplashHotSpotPortSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaSplashHotSpotPortSSL"));
	if($ArticaSplashHotSpotPort==0){$ArticaSplashHotSpotPort=16080;}
	if($ArticaSplashHotSpotPortSSL==0){$ArticaSplashHotSpotPortSSL=16443;}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaSplashHotSpotPort", $ArticaSplashHotSpotPort);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaSplashHotSpotPortSSL", $ArticaSplashHotSpotPortSSL);
	
	
	$unix=new unix();
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();


    foreach ($NETWORK_ALL_INTERFACES as $interface=>$line){
		$IP2=$line["IPADDR"];
		if($interface=="lo"){continue;}
		if($IP2==null){continue;}
		if($IP2=="0.0.0.0"){continue;}
		$AVAIINT[]=$interface;
	}
	
	if($ArticaHotSpotInterface==null){$ArticaHotSpotInterface=$AVAIINT[0];}
	
	
	$ipaddr=trim($NETWORK_ALL_INTERFACES[$ArticaHotSpotInterface]["IPADDR"]);
	
	writelogs_framework("ArticaHotSpotInterface = $ArticaHotSpotInterface IPADDR:$ipaddr",__FUNCTION__,__FILE__,__LINE__);
	
	if( ($ipaddr=="0.0.0.0") OR ($ipaddr==null)){
		$ArticaHotSpotInterface=$AVAIINT[0];
		writelogs_framework("NEw ArticaHotSpotInterface = {$AVAIINT[0]}",__FUNCTION__,__FILE__,__LINE__);
		$ipaddr=$NETWORK_ALL_INTERFACES[$ArticaHotSpotInterface]["IPADDR"];
		
	}
	
	writelogs_framework("http://$ipaddr:$ArticaSplashHotSpotPort/hotspot.php",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>http://$ipaddr:$ArticaSplashHotSpotPort/hotspot.php</articadatascgi>";
	
}



function services_status(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.status.php --hotspot --nowachdog 2>&1";
	writelogs_framework($cmd,__FILE__,__FUNCTION__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";
	
}
function status(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $cmd="$php /usr/share/artica-postfix/exec.status.php --hotspot-web --nowachdog 2>&1";
    writelogs_framework($cmd,__FILE__,__FUNCTION__,__LINE__);
    shell_exec($cmd);
}
function restart(){
    $unix=new unix();
    $unix->framework_exec("exec.hotspot-service.php --restart",
     "hotspot.progress","hotspot.log");
}



