<?php
$GLOBALS["BYPASS"]=true;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.gluster.samba.php');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");

$cmdlines=@implode(" ", $argv);
writelogs("Executed `$cmdlines`","MAIN",__FILE__,__LINE__);
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}

if($GLOBALS["VERBOSE"]){echo "Debug mode TRUE for {$argv[1]}\n";}


// probe toutes les clients sans uuid.et vérifie ceux non insérés dans la base.
if($argv[1]=='--probes'){probes();exit();}
// récupère les répertoires à clusteriser.
if($argv[1]=='--sources'){MASTERS_GET_SOURCES();exit();}

// monte les répertoires vers les serveurs clusters.
if($argv[1]=='--mount'){CLIENT_MOUNT_FOLDERS();exit();}

//Démonte les répertoires et remonte ceux encore disponibles...
if($argv[1]=='--client-dismount'){CLIENT_DISMOUNT_FOLDERS();exit();}

// Démonte tous les répertoires, synchronise depuis les maîtres et remonte les répertoires.
if($argv[1]=='--remount'){CLIENT_REMOUNT_FOLDERS();exit();}

//liste les répertoires montés.
if($argv[1]=='--mounted-client'){CLIENT_LIST_MOUNTED_FOLDERS();exit();}

if($argv[1]=='--master'){NotifyMaster();exit;}

// écrit la config du serveur.
if($argv[1]=='--conf'){BuildLocalConf();exit;}

// notifie les nouveaux clients
if($argv[1]=='--notify-all-clients'){NotifyAllClients();exit();}

// notifie les clients pour qu'ils soient supprimés.
if($argv[1]=='--delete-clients'){DeleteAllClients();exit();}

//notifie les anciens clients du changement, les force à démonter et remonter...
if($argv[1]=='--update-all-clients'){UpdateAllClients();exit();}

//notifie les maitres du statut des montages 
if($argv[1]=='--notify-server'){NotifyAllServersStatus();exit();}


if($argv[1]=='--help'){help();exit();}
if($argv[1]=='-h'){help();exit();}



if(isset($_POST["notify"])){ReceiveParams();exit;}
if(isset($_POST["events"])){ReceiveClientEvents();exit;}
if(isset($_POST["update-mounts"])){ReceiveServerUpdateMount();exit;}
if(isset($_POST["delete-server"])){ReceiveServerDelete();exit;}




if(isset($_POST["bricks"])){export_bricks();exit;}
if(isset($_POST["NTFY_STATUS"])){server_receive_status();exit;}
if(isset($_POST["CLIENT_NTFY_SRV_INFO"])){server_request_client_info();exit();}
if(isset($_POST["STATUSDIRSIZE"])){server_receive_diresizes();exit;}
if(isset($_POST["PDNS_REPLIC"])){PDNS_REPLIC();exit;}
if(isset($_POST["RDCUBE-REPLIC"])){ROUNDCUBE_REPLIC();exit;}

if(isset($_POST)){
	foreach ($_POST as $num=>$ligne){
		writelogs("unable to understand $num= $ligne",__FUNCTION__,__FILE__,__LINE__);
	}
	exit();
}
if(isset($_GET)){
    foreach ($_GET as $num=>$ligne){
		writelogs("unable to understand $num= $ligne",__FUNCTION__,__FILE__,__LINE__);
	}
	exit();
}


writelogs("no posts notify clients by default...",__FUNCTION__,__FILE__,__LINE__);
NotifyClients();
exit();


function NotifyAllClients(){
	$sql="SELECT * FROM glusters_clients WHERE client_notified=0 ORDER BY ID DESC";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		NotifyClient("{$ligne["client_ip"]}:{$ligne["client_port"]}",$ligne["ID"]);
	}
}


function DeleteAllClients(){
	$q=new mysql();
	$sql="DELETE FROM glusters_clients WHERE client_notified=0 AND NotifToDelete=1";
	$q->QUERY_SQL($sql,"artica_backup");
	
	
	$sql="SELECT * FROM glusters_clients WHERE client_notified=1 AND NotifToDelete=1";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		NotifyDeleteClient("{$ligne["client_ip"]}:{$ligne["client_port"]}",$ligne["ID"]);
	}	
	
}

function UpdateAllClients(){
	$sql="SELECT * FROM glusters_clients WHERE client_notified=1 ORDER BY ID DESC";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		NotifyUpdateClient("{$ligne["client_ip"]}:{$ligne["client_port"]}",$ligne["ID"]);
	}	
	
}


function NotifyEvents($text,$ID){
	$q=new mysql();
	$sql="SELECT parameters FROM glusters_clients WHERE ID=$ID";
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	
	$array=unserialize(base64_decode($ligne["parameters"]));
	if(count($array["LOGS"])>300){unset($array["LOGS"]);}
	$array["LOGS"][time()]=date("m-d H:i:s")." $text";
	$sql="UPDATE glusters_clients SET parameters='".base64_encode(serialize($array))."' WHERE ID=$ID";
	$q->QUERY_SQL($sql,"artica_backup");
}

function NotifyUpdateClient($server,$ID){
	$curl=new ccurl("https://$server/exec.gluster.php");
	$curl->parms["update-mounts"]="yes";
	if(!$curl->get()){
		NotifyEvents("Failed to update status",$ID);
		return;	
	}
	
	if(!preg_match("#<ANSWER>OK</ANSWER>#s",$curl->data)){
		NotifyEvents("Protocol error",$ID);
		return ;
	}
	
	NotifyEvents("Update settings success...",$ID);
	
	
}

function NotifyDeleteClient($server,$ID){
	$curl=new ccurl("https://$server/exec.gluster.php");
	$curl->parms["delete-server"]="yes";
	if(!$curl->get()){
		NotifyEvents("Failed to send delete order",$ID);
		return;	
	}	
	
	if(!preg_match("#<ANSWER>OK</ANSWER>#s",$curl->data)){
		NotifyEvents("Protocol error",$ID);
		return ;
	}	
	

	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM glusters_clients WHERE ID=$ID","artica_backup");
	$q->QUERY_SQL("DELETE FROM glusters_clientssize WHERE hostname='$server'","artica_backup");
	$q->QUERY_SQL("DELETE FROM glusters_clientssize WHERE client_ip='$server'","artica_backup");	
	
	
}


function NotifyClient($server,$ID){
	writelogs("Notify $server ($ID)",__FUNCTION__,__FILE__,__LINE__);
	$unix=new unix();
	$localport=$unix->LIGHTTPD_PORT();
	$notifed=false;
	if(!function_exists("curl_init")){
		writelogs("curl_init not detected",__FUNCTION__,__FILE__,__LINE__);
		NotifyEvents("{CURLPHP_NOT_INSTALLED}",$ligne["ID"]);
		return null;
	}
	
	$array["notify"]="yes";
	$array["localport"]=$localport;
	
	while (list ($num, $ligne) = each ($array)){
		$curlPost .='&'.$num.'=' . urlencode($ligne);
	}
	
	writelogs("https://$server/exec.gluster.php -> $curlPost",__FUNCTION__,__FILE__,__LINE__);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://$server/exec.gluster.php");
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
	
	$data = curl_exec($ch);
	$error=curl_errno($ch);
	if($error>0){
		NotifyEvents("$error",$ID);
		writelogs("Connect to $server error $error",__FUNCTION__,__FILE__,__LINE__); 
	}

switch ($error) {
	case 6:
		NotifyEvents("{error_curl_resolve}",$ID);
		curl_close($ch);
		return null;
		break;
	
	default:break;
}

	if(curl_errno($ch)==false){
		if(preg_match("#404 Not Found#is",$data)){
			writelogs("Connect to $server error 404 Not Found",__FUNCTION__,__FILE__,__LINE__);
			NotifyEvents("404 Not Found: {error_wrong_artica_version}",$ID);
			curl_close($ch);
			return null;
			}
		
		if(preg_match("#GLUSTER_NOT_INSTALLED#is",$data)){
				writelogs("Connect to $server error GLUSTER_NOT_INSTALLED",__FUNCTION__,__FILE__,__LINE__);
				NotifyEvents("{error_gluster_not_installed}",$ID);
				curl_close($ch);
				return null;	
			}
			
		if(preg_match("#CURL_NOT_INSTALLED#is",$data)){
				writelogs("Connect to $server error CURL_NOT_INSTALLED",__FUNCTION__,__FILE__,__LINE__);
				NotifyEvents("{error_php_curl}",$ID);
				curl_close($ch);
				return null;	
			}
		
		if(preg_match("#GLUSTER_MYSQL_ERROR#is",$data)){
				preg_match("#<ERR>(.+?)</ERR>#is",$data,$re);
				writelogs("Connect to $server error GLUSTER_MYSQL_ERROR",__FUNCTION__,__FILE__,__LINE__);
				NotifyEvents("mysql error:{$re[1]}",$ID);
				curl_close($ch);
				return null;	
			}		
	}

if(preg_match("#GLUSTER_OK#is",$data)){
		writelogs("Connect to $server success",__FUNCTION__,__FILE__,__LINE__);
		NotifyEvents("{success}",$ID);
		writelogs("Set this server has notified",__FUNCTION__,__FILE__,__LINE__);
		curl_close($ch);
		$notifed=true;	
}	
if($notifed){
	$q=new mysql();
	$sql="UPDATE glusters_clients SET client_notified='1' WHERE ID=$ID";
	$q->QUERY_SQL($sql,"artica_backup");
}else{
	NotifyEvents("unknown error",$ID);
	writelogs("$server Not notified, unknown error\n$data\n",__FUNCTION__,__FILE__,__LINE__);
}

	
}

function client_restart_notify(){
	$master=@file_get_contents("/etc/artica-cluster/master");
	
	
	$gl=new gluster();
	$myname=@file_get_contents("/etc/artica-cluster/local.name");
	
	writelogs("MASTER=\"$master\"; me=\"$myname\"",__FUNCTION__,__FILE__,__LINE__);
	
	if($myname<>$master){
	if(is_array($gl->clients)){
		while (list ($num, $ligne) = each ($gl->clients) ){
			writelogs("Deleting clusters-$num",__FUNCTION__,__FILE__,__LINE__);
			@unlink("/etc/artica-cluster/clusters-$num");
		}
		}
	}
	writelogs("Notify master",__FUNCTION__,__FILE__,__LINE__);
	NotifyStatus();	
	BuildLocalConf();
	writelogs("Notify master...",__FUNCTION__,__FILE__,__LINE__);
	NotifyStatus();	
}





function ReceiveParams(){
	$localport=$_POST["localport"];
	$master=$_SERVER['REMOTE_ADDR'];
	writelogs("$master:$localport",__FUNCTION__,__FILE__,__LINE__);
	
	
	echo "RECIEVE OK\n\n";
	$users=new usersMenus();
	if(!$users->GLUSTER_INSTALLED){
		echo "GLUSTER_NOT_INSTALLED\n\n";
		exit();
	}
	
	if(!function_exists("curl_init")){
		echo "CURL_NOT_INSTALLED\n\n";
		exit();
	}
	
	;
	
	$sql="INSERT INTO glusters_servers (server_ip,server_port) VALUES('$master','$localport')";
	squid_admin_mysql(2, "Success receive order from master server {$_SERVER['REMOTE_ADDR']}" , __FUNCTION__, __FILE__, __LINE__, "cluster");
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		squid_admin_mysql(2, "failed $q->mysql_error" , __FUNCTION__, __FILE__, __LINE__, "cluster");
		echo "GLUSTER_MYSQL_ERROR\n\n<ERR>$q->mysql_error</ERR>";
		exit();	
	}
	
	
	echo "GLUSTER_OK";
	MASTERS_GET_SOURCES();
}

function ReceiveServerUpdateMount(){
	echo "\n\n<ANSWER>OK</ANSWER>\n\n";
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?gluster-remounts=yes");
}

function ReceiveServerDelete(){
	$localport=$_POST["localport"];
	$master=$_SERVER['REMOTE_ADDR'];
	writelogs("$master:Receive notification from $master to remove it",__FUNCTION__,__FILE__,__LINE__);
	
	$sql="DELETE FROM glusters_servers WHERE server_ip='$master'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		writelogs("$master:$q->mysql_error");
		return ;
	}
	writelogs("$master:Success",__FUNCTION__,__FILE__,__LINE__);
	$sock=new sockets();
	writelogs("$master:Notfy framework to remount",__FUNCTION__,__FILE__,__LINE__);
	$sock->getFrameWork("gluster.php?client-dismount=yes");	
	echo "\n\n<ANSWER>OK</ANSWER>\n\n";
	
}

function BuildLocalConf(){
	if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
	$GLOBALS["EXECUTED_AS_ROOT"]=true;
	
	@mkdir("/etc/artica-cluster",null,true);
	$configfile="/etc/artica-cluster/glusterfs-server.vol";
	@unlink($configfile);
	$gluser=new gluster_samba();
	$conf=$gluser->build();
	if(trim($conf)==null){
		echo "Starting......: ".date("H:i:s")." Gluster Daemon glusterfs-server.vol no settings...\n";
		return;
	}
	squid_admin_mysql(2, "Gluster Daemon glusterfs-server.vol rebuilded..." , __FUNCTION__, __FILE__, __LINE__, "cluster");
	@file_put_contents($configfile,$conf);
	if(is_file($configfile)){
		echo "Starting......: ".date("H:i:s")." Gluster Daemon `glusterfs-server.vol` done\n";
	}else{
		echo "Starting......: ".date("H:i:s")." Gluster Daemon `glusterfs-server.vol` failed\n";
	}
	

	
}


function MASTERS_GET_SOURCES(){
	
	$sql="SELECT * FROM glusters_servers";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$directories=0;
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
			writelogs("{$ligne["server_ip"]}:{$ligne["server_port"]}:: get sources from...");
			$directories=$directories.MASTER_GET_SOURCE_CONNECT("{$ligne["server_ip"]}:{$ligne["server_port"]}",$ligne["ID"]);
	}
	if($directories>0){
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?gluster-mounts=yes");
	}
	
}
/*
 * ce connect au host afin de recevoir la liste des dossiers à clusteriser.
 */
function MASTER_GET_SOURCE_CONNECT($host,$ID){
	$DirectoryNumber=0;
	$file="/etc/artica-postfix/croned.1/".md5($host).".vol";
	$timefile=file_time_sec($file);
	if(file_time_sec($file)<30){
		writelogs("MASTER::$host, $file $timefile seconds, need 30s aborting",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	@unlink($file);
	@file_put_contents($file,"#");
	
	$curl=new ccurl("https://$host/exec.gluster.php");
	$curl->parms["bricks"]="yes";
	if(!$curl->get()){return null;}
	
	if(!preg_match("#<SOURCES>(.+?)</SOURCES>#s",$curl->data,$re)){
		writelogs("MASTER::$host, unable to preg_match",__FUNCTION__,__FILE__,__LINE__);
		squid_admin_mysql(2, "failed $host, unable to preg_match - Error parsing sources" , __FUNCTION__, __FILE__, __LINE__, "cluster");
		MASTER_SEND_LOGS($host,"Error parsing sources");
		return null;
	}
	writelogs($re[1],__FUNCTION__,__FILE__,__LINE__);
	$paths=unserialize(base64_decode($re[1]));
	writelogs("MASTER::$host, receive ". count($paths)." directories",__FUNCTION__,__FILE__,__LINE__);
	squid_admin_mysql(2, "receive ". count($paths)." directories from $host" , __FUNCTION__, __FILE__, __LINE__, "cluster");
	$DirectoryNumber=count($paths);
	$array["PATHS"]=$paths;
	$based=base64_encode(serialize($array));
	$sql="UPDATE glusters_servers SET parameters='$based' WHERE ID=$ID";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		squid_admin_mysql(2, "Error $q->mysql_error from $host" , __FUNCTION__, __FILE__, __LINE__, "cluster");
		writelogs("MASTER::$host, $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		MASTER_SEND_LOGS($host,"Mysql Error $q->mysql_error");
		return;
	}
	if($DirectoryNumber>0){
		MASTER_SEND_LOGS($host,"Success receive `$DirectoryNumber` clustered directories",__FUNCTION__,__FILE__,__LINE__);
		squid_admin_mysql(2, "Success update clustered $DirectoryNumber directorie(s) from $host" , __FUNCTION__, __FILE__, __LINE__, "cluster");
	}
	
	return $DirectoryNumber;
}

function MASTER_SEND_LOGS($host,$text,$function=null,$file=null,$line=null){
	if(trim($text)==null){return;}
	$file=basename($file);
	if($function==null){
		if(function_exists("debug_backtrace")){
			$trace=@debug_backtrace();
			if(isset($trace[1])){
				$file=basename($trace[1]["file"]);
				$function=$trace[1]["function"];
				$line=$trace[1]["line"];
			}
		}
	}
	
	$text=$text. "\nFunction: $function() in $file line $line";
	$curl=new ccurl("https://$host/exec.gluster.php");
	$curl->parms["events"]=base64_encode($text);
	if(!$curl->get()){return null;}
}
function MASTER_SEND_STATUS($host){
	
	$unix=new unix();
	$df=$unix->find_program("df");
	
	exec("$df -hP 2>&1",$results);
	
	foreach ($results as $num=>$ligne){
		if(preg_match("#\/etc\/.*?\.vol\s+(.*?)\s+(.*?)\s+(.*?)\s+([0-9]+)%\s+(.+)#", $ligne,$re)){
			$ARRAY[$re[5]]=array(
				"SIZE"=>$re[1],
				"USED"=>$re[2],
				"AVAILABLE"=>$re[3],
				"POURC"=>$re[4]
			);
			
		}
		
	}
	$text=serialize($ARRAY);
	$curl=new ccurl("https://$host/exec.gluster.php");
	$curl->parms["HOSTNAME"]=$unix->hostname_g();
	$curl->parms["STATUSDIRSIZE"]=base64_encode($text);
	if(!$curl->get()){return null;}
	if(preg_match("#<ERROR>(.*?)</ERROR>#is", $curl->data,$re)){
		echo "ERROR -> ".$re[1]."\n";
	}
}

function server_receive_diresizes(){
	$hostname=$_POST["HOSTNAME"];
	$conn=$_SERVER['REMOTE_ADDR'];
	$f=array();
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM glusters_clientssize WHERE hostname='$hostname'","artica_backup");
	$q->QUERY_SQL("DELETE FROM glusters_clientssize WHERE client_ip='$conn'","artica_backup");
	$ARRAY=unserialize(base64_decode($_POST["STATUSDIRSIZE"]));
	
	$prefix="INSERT IGNORE INTO glusters_clientssize (`client_ip`,`hostname`,`SIZE`,`USED`,`AVAILABLE`,`POURC`,`DIRNAME`) VALUES ";
	
	while (list ($directory, $ligne) = each ($ARRAY) ){
		$f[]="('$conn','$hostname','{$ligne["SIZE"]}','{$ligne["USED"]}','{$ligne["AVAILABLE"]}','{$ligne["POURC"]}','$directory')";
		
	}
	
	
	if(count($f)>0){
		$sql=$prefix.@implode(",", $f);
		$q->QUERY_SQL($sql,"artica_backup");
		
		if(!$q->ok){
			echo "<ERROR>$q->mysql_error</ERROR>";
			writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
		}
	}
	
	
	
}



function ReceiveClientEvents(){
	$conn=$_SERVER['REMOTE_ADDR'];
	$sql="SELECT ID FROM glusters_clients WHERE client_ip='$conn'";
	$q=new mysql();
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	NotifyEvents(base64_decode($_POST["events"]),$ligne["ID"]);
}


function getStatus(){
	$glfs=new gluster_client();
	$mounts=$glfs->get_mounted();
	if(!is_array($mounts)){return "No paths set or mounted";}
	
	$f[]="Client status:";
	$c=0;
	while (list ($num, $vol) = each ($mounts) ){
		$path=$glfs->volToPath($vol);
		$f[]="$path is mounted OK";
		$c++;
	}
	if($c==0){return null;}
	return @implode("\n",$f);
	
}


function NotifyAllServersStatus(){
	$q=new mysql();
	if($q->COUNT_ROWS("glusters_servers", "artica_backup")==0){return;}
	$status=getStatus();
	if($status==null){return;}
	$sql="SELECT * FROM glusters_servers";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		MASTER_SEND_LOGS("{$ligne["server_ip"]}:{$ligne["server_port"]}",$status);
		MASTER_GET_SOURCE_CONNECT("{$ligne["server_ip"]}:{$ligne["server_port"]}",$ligne["ID"]);
		MASTER_SEND_STATUS("{$ligne["server_ip"]}:{$ligne["server_port"]}");
	}	
	
}





function server_receive_status(){
	writelogs("Receive infos from {$_POST["NTFY_STATUS"]}",__FUNCTION__,__FILE__,__LINE__);
	
	$gl=new gluster();
	
	if($gl->clients[$_POST["NTFY_STATUS"]]==null){
		writelogs("Depreciated server, send order to delete",__FUNCTION__,__FILE__,__LINE__);
		echo "DELETE_YOU";
		exit;
		}
	
	
	$ini=new Bs_IniHandler();
	while (list ($num, $ligne) = each ($_POST)){
		writelogs("Receive infos $num = $ligne from {$_POST["NTFY_STATUS"]}",__FUNCTION__,__FILE__,__LINE__);
		$ini->_params["CLUSTER"][$num]=$ligne;
	}
	
	$sock=new sockets();
	$sock->SaveClusterConfigFile($ini->toString(),"clusters-".$_POST["NTFY_STATUS"]);
	$cyrus_id=$sock->getFrameWork("cmd.php?idofUser=cyrus");
	echo "CYRUS-ID=$cyrus_id;\n";
	
	
	$gl=new gluster();
	if(is_array($gl->clients)){
		while (list ($num, $name) = each ($gl->clients) ){
			$cl[]=$name;
		}
	}
	
	$datas=implode(";",$cl);
	writelogs("Sending servers list ". strlen($datas)." bytes",__FUNCTION__,__FILE__,__LINE__);
	echo $datas;

}


function export_bricks(){
	$conn=$_SERVER['REMOTE_ADDR'];
	$sql="SELECT * FROM gluster_clients_brick";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		writelogs("$conn:: {$ligne["brickname"]}={$ligne["source"]}",__FUNCTION__,__FILE__,__LINE__);
		$array[$ligne["brickname"]]=$ligne["source"];	
	}
	
	$serial=serialize($array);
	writelogs("$conn:: send $serial",__FUNCTION__,__FILE__,__LINE__);
	
	echo "<SOURCES>".base64_encode(serialize($array))."</SOURCES>";
}

function CLIENT_DISMOUNT_FOLDERS(){
	$unix=new unix();
	$glfs=new gluster_client();
	$array=$glfs->get_mounted();	
	$umount=$unix->find_program("umount");
	if(is_array($array)){
		while (list ($index, $volfile) = each ($array) ){
			$results=array();
			exec("$umount -l $volfile 2>&1",$results);
			squid_admin_mysql(2, "umount foler $index for \"".$volfile."\"\n".@implode("\n", $results), __FUNCTION__, __FILE__, __LINE__, "cluster");
		}
	}
	CLIENT_MOUNT_FOLDERS();
}


function CLIENT_MOUNT_FOLDERS(){
	$glfs=new gluster_client();
	$glfs->buildconf();
	
	foreach (glob("/etc/artica-cluster/glusterfs-client/*.vol") as $filename) {
		$path=$glfs->volToPath($filename);
		if($path==null){continue;}
		$basename=basename($filename);
		if(preg_match("#^([0-9]+)\.#", $basename,$re)){$volume=$re[1];}
		if($GLOBALS["VERBOSE"]){echo "Found $filename: path=$path ($basename), volume=$volume\n";}
		unset($GLOBALS["GLUSTERS_EV"]);
		if(!$glfs->ismounted($path,$volume)){
			if($glfs->CheckPath($path)){
				squid_admin_mysql(2, "mouting " .basename($filename), __FUNCTION__, __FILE__, __LINE__, "cluster");
				echo "Starting......: ".date("H:i:s")." Gluster clients ".basename($filename)." mount it\n";
				$glfs->mount($path,$filename);
				if($glfs->ismounted($path)){
					NOTIFY_ALL_MASTERS("Success connect $path",__FUNCTION__,__FILE__,__LINE__);
				}else{
					squid_admin_mysql(2, "Unable to mount $path", __FUNCTION__, __FILE__, __LINE__, "cluster");
					NOTIFY_ALL_MASTERS("Unable to mount $path".@implode("\n", $GLOBALS["GLUSTERS_EV"]),__FUNCTION__,__FILE__,__LINE__);
				}
			}else{
				squid_admin_mysql(2, "Unable to mount $path", __FUNCTION__, __FILE__, __LINE__, "cluster");
				NOTIFY_ALL_MASTERS("Unable to mount $path".@implode("\n", $GLOBALS["GLUSTERS_EV"]),__FUNCTION__,__FILE__,__LINE__);
			}
		}else{
			echo "Starting......: ".date("H:i:s")." Gluster clients ".basename($filename)." already mounted\n";
		}
		
	}
}

function CLIENT_LIST_MOUNTED_FOLDERS(){
	$glfs=new gluster_client();
	$array=$glfs->get_mounted();	
	if(is_array($array)){
		while (list ($index, $volfile) = each ($array) ){
			 echo "mounted Gluster client.....: $index] for \"".$volfile."\"\n";
			
		}
	}
}


function CLIENT_REMOUNT_FOLDERS(){
	$unix=new unix();
	$mount=$unix->find_program("mount");
	$glfs=new gluster_client();
	$array=$glfs->get_mounted();	
	if(is_array($array)){
		while (list ($index, $volfile) = each ($array) ){
			 squid_admin_mysql(2, "Remount $volfile", __FUNCTION__, __FILE__, __LINE__, "cluster");
			 echo "Remount Gluster client......: ".$volfile."\n";
			 NOTIFY_ALL_MASTERS("Task: remount $volfile",__FUNCTION__,__FILE__,__LINE__);
			 shell_exec("$mount -o remount $volfile");
		}
	}else{
		echo "Stopping Gluster client......: no mounted path\n";
		
	}
	
	MASTERS_GET_SOURCES();
	CLIENT_MOUNT_FOLDERS();
	
}

function NOTIFY_ALL_MASTERS($text){
	$sql="SELECT * FROM glusters_servers";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
			MASTER_SEND_LOGS("{$ligne["server_ip"]}:{$ligne["server_port"]}",$text);
			MASTER_GET_SOURCE_CONNECT("{$ligne["server_ip"]}:{$ligne["server_port"]}",$ligne["ID"]);
	}	
}


function help(){
	
	
	echo "--sources...........: Get folders to mount from masters\n";
	echo "--mount.............: Get mount folders from masters\n";
	echo "--client-dismount...: umount all folders and remount them\n";
	echo "--remount...........: remount all folders\n";
	echo "--mounted-client....: List all mounted folders\n";
	echo "--notify-server.....: Send status to all masters\n";
	echo "--master............: Ping masters\n";
	echo "--conf..............: Build local configuration file (master mode)\n";
	echo "--notify-all-clients: Notify all clients (master mode)\n";
	echo "--delete-clients....: Notify all clients stamped to deletion (master mode)\n";
	echo "--update-all-clients: Notify all clients From a change in order to force\n";
	echo "                      Them to remount their local folders.(master mode)\n";

	
}


function probes(){
	$array=array();	
	$unix=new unix();
	$gluster=$unix->find_program("gluster");
	
	$q=new mysql();
	$results=$q->QUERY_SQL("SELECT client_ip,hostname FROM glusters_clients WHERE LENGTH(uuid)=0 AND NotifToDelete=0","artica_backup");
	
	
	if($GLOBALS["VERBOSE"]){echo "SELECT client_ip,hostname FROM glusters_clients WHERE LENGTH(uuid)=0 = ".mysqli_num_rows($results)." rows\n";}
	
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($GLOBALS["VERBOSE"]){echo "Probe {$ligne["client_ip"]}\n";}
		if($ligne["client_ip"]<>null){
			$results2=array();
			exec("$gluster peer probe {$ligne["client_ip"]} 2>&1",$results2);
			if(preg_match("#is already part of another cluster#", $results2[0])){
				$glusterClass=new gluster_client($ligne["client_ip"]);
				$glusterClass->state="WARN: is already part of another cluster";
				$glusterClass->edit_client();
			}
			
			squid_admin_mysql(2, "Probe {$ligne["client_ip"]}:\n".@implode("\n", $results2) , __FUNCTION__, __FILE__, __LINE__, "clusters");
			continue;
		}
	}
	
	$q=new mysql();
	$results=$q->QUERY_SQL("SELECT client_ip,hostname FROM glusters_clients WHERE NotifToDelete=1","artica_backup");
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($GLOBALS["VERBOSE"]){echo "detach {$ligne["client_ip"]}/{$ligne["hostname"]}\n";}
		if($ligne["client_ip"]<>null){
			$results2=array();
			exec("$gluster peer detach {$ligne["client_ip"]} 2>&1",$results2);
			if(preg_match("#is not part of cluster#i", $results2[0])){
				$glusterClass=new gluster_client($ligne["client_ip"]);
				$glusterClass->remove();
			}
			if(preg_match("#successful#i", $results2[0])){
				$glusterClass=new gluster_client($ligne["client_ip"]);
				$glusterClass->remove();
			}

			if(preg_match("#exist in cluster#", $results2[0])){
				$glusterClass=new gluster_client($ligne["client_ip"]);
				$glusterClass->state="WARN: {$results2[0]}";
				$glusterClass->edit_client();
				$NOSTATUS[$ligne["client_ip"]]=true;
				continue;
			}
			
			if(preg_match("#is probably down#", $results2[0])){
				$glusterClass=new gluster_client($ligne["client_ip"]);
				$glusterClass->state="WARN: {$results2[0]}";
				$glusterClass->edit_client();
				$NOSTATUS[$ligne["client_ip"]]=true;
				continue;				
				
			}
			
			squid_admin_mysql(2, "detach {$ligne["client_ip"]}:\n".@implode("\n", $results2) , __FUNCTION__, __FILE__, __LINE__, "clusters");
			continue;
		}
	}		
	
	exec("$gluster peer status 2>&1",$results);
	foreach ($results as $index=>$line){
		if(preg_match("#Hostname:\s+(.+)#", $line,$re)){
			
			$hostname=$re[1];
			if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $hostname)){
				$array[$hostname]["client_ip"]=gethostbyname($hostname);
			}
			
			continue;
		}
		if(preg_match("#Uuid:\s+(.+)#i", $line,$re)){$array[$hostname]["UUID"]=$re[1];continue;}
		if(preg_match("#State:\s+(.+)#i", $line,$re)){$array[$hostname]["STATE"]=$re[1];continue;}
		if($GLOBALS["VERBOSE"]){echo "peer status: $line -> SKIP\n";}
	}
	
	
	if($GLOBALS["VERBOSE"]){echo "peer status: ".count($array)." items\n";}
	
	
	
	
	
	
	while (list ($hostname, $line) = each ($array) ){
		$hostnameText=null;
		if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $hostname)){
				if($GLOBALS["VERBOSE"]){echo "$hostname -> is an ip -> resolve it\n";}
				$line["client_ip"]=$hostname;
				$hostnameText=gethostbyaddr($hostname);
		}
		
		if(isset($NOSTATUS[$line["client_ip"]])){continue;}
		
		$gluster=new gluster_client($hostname);
		if($gluster->client_ip==null){
			if($GLOBALS["VERBOSE"]){echo "$hostname -> ADD -> {$line["STATE"]}\n";}	
			if(isset($line["client_ip"])){$gluster->client_ip=$line["client_ip"];}
			$gluster->state=$line["STATE"];
			$gluster->uuid=$line["UUID"];
			if($hostnameText<>null){$gluster->hostname=$hostnameText;}
			$gluster->add_client();
			continue;
		}
		if($GLOBALS["VERBOSE"]){echo "$hostname -> EDIT -> {$line["STATE"]} - $hostnameText\n";}	
		$gluster->state=$line["STATE"];
		$gluster->uuid=$line["UUID"];
		$gluster->edit_client();
		
		
	}
}

function ROUNDCUBE_REPLIC(){
	$GLOBALS["VERBOSE"]=true;
	ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	$auth=unserialize(base64_decode($_POST["AUTH"]));
	if(!is_array($auth)){echo "<ERROR>token auth no such array</ERROR>";exit();	}	
	$ldap=new clladp();
	$q=new mysql();
	if(strtolower($ldap->ldap_admin)<>strtolower($auth["username"])){echo "<ERROR>Authentication failed</ERROR>";exit();}
	if(md5($ldap->ldap_password)<>$auth["password"]){echo "<ERROR>Authentication failed</ERROR>";exit();	}	
	$servername=trim($_POST["RDCUBE-REPLIC"]);
	if($servername==null){echo "<ERROR>Local FreeWebs server name is not set</ERROR>";exit();}
	$free=new freeweb($servername);
	if($free->loading_error){echo "<ERROR>Internal error $free->loading_error_text</ERROR>";exit();}
	$database=$free->mysql_database;
	if($free->groupware<>"ROUNDCUBE"){echo "<ERROR>Local FreeWebs server $servername Mysql Database:$database is not a roundcube server ($free->groupware)</ERROR>";exit();}
	$mysql_server=$free->mysql_instance_id;
	$database=$free->mysql_database;
	
	echo "<INFO>Using instance $mysql_server on database $database</INFO>\n";
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("mysql.php?dumpwebdb=yes&database=$database&instance-id=$mysql_server")));
	if(is_file("ressources/logs/web/$database.gz")){
		echo "
		<INFOS>".@implode("\n", $datas)."</INFOS>
		<FILENAME>ressources/logs/web/$database.gz</FILENAME>\n";
	}else{
		echo "<ERROR>Failed dump $database\n".@implode("\n", $datas)."</ERROR>\n";
		exit();
	}
		
	//$mysqldump --add-drop-table --skip-comments --databases $database
	
}

function PDNS_REPLIC(){
	$auth=unserialize(base64_decode($_POST["PDNS_REPLIC"]));
	if(!is_array($auth)){echo "<ERROR>token auth no such array</ERROR>";exit();	}
	
	
	$ldap=new clladp();
	$q=new mysql();
	if(strtolower($ldap->ldap_admin)<>strtolower($auth["username"])){echo "<ERROR>Authentication failed</ERROR>";exit();}
	if(md5($ldap->ldap_password)<>$auth["password"]){echo "<ERROR>Authentication failed</ERROR>";exit();	}
	
	
	$sql="SELECT * FROM dhcpd_fixed";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$hostname=trim(strtolower($ligne["hostname"]));
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $hostname)){continue;}
		$domain=$ligne["domain"];
		$hostname="$hostname.$domain";
		$ip=$ligne["ipaddr"];	
		$ip=str_replace('$','',$ip);
		if($ip=="0.0.0.0"){continue;}	
		if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $ip)){continue;}
		$array_computers[$ip]=$hostname;
	}
	
	$sql="SELECT * FROM dhcpd_leases";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$hostname=trim(strtolower($ligne["hostname"]));
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $hostname)){continue;}
		$ip=$ligne["ipaddr"];	
		$ip=str_replace('$','',$ip);
		if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $ip)){continue;}
		if(isset($array_computers[$ip])){continue;}	
		$array_computers[$ip]=$hostname;
	}

	

	$filter_search="(&(objectClass=ArticaComputerInfos)(|(cn=*)(ComputerIP=*)(uid=*))(gecos=computer))";
	$ldap=new clladp();
	$attrs=array("uid","ComputerIP","DnsZoneName");
	$dn="$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter_search,$attrs,0);	
	for($i=0;$i<$hash["count"];$i++){
		$realuid=$hash[$i]["uid"][0];
		$realuid=str_replace('$','',$realuid);
		$realuid=trim(strtolower($realuid));
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $realuid)){continue;}
		
		$ip=$hash[$i][strtolower("ComputerIP")][0];
		if($ip=="0.0.0.0"){continue;}
		if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $ip)){continue;}
		$DnsZoneName=$hash[$i][strtolower("DnsZoneName")][0];
		
		if(strpos($realuid, ".")==0){
			if($DnsZoneName<>null){
				$hostname="$realuid.$DnsZoneName";
			}
		}
		$array_computers[$ip]=$hostname;
		
	}
	
	$sql="SELECT networks.*,hardware.* FROM networks,hardware WHERE networks.HARDWARE_ID=hardware.ID";
	$results=$q->QUERY_SQL($sql,"ocsweb");
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$hostname=trim(strtolower($ligne["NAME"]));
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $hostname)){continue;}
		$ip=$ligne["IPADDRESS"];
		$ip=str_replace('$','',$ip);
		if($ip=="0.0.0.0"){continue;}
		if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $ip)){continue;}
		if(isset($array_computers[$ip])){continue;}
		$array_computers[$ip]=$hostname;
		
	}
	
	$sql="SELECT * FROM records";
	$results=$q->QUERY_SQL($sql,"powerdns");
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$hostname=$ligne["name"];
		$ip=$ligne["content"];
		$ip=str_replace('$','',$ip);
		if($ip=="0.0.0.0"){continue;}
		if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $ip)){continue;}
		if(isset($array_computers[$ip])){continue;}
		$array_computers[$ip]=$hostname;
		
	}	

	echo "<REPLIC>".base64_encode(serialize($array_computers))."</REPLIC>";
		
	
}


?>