<?php
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.users.menus.inc');

if(!creds()){die("DIE " .__FILE__." Line: ".__LINE__);}
if(isset($_GET["hello"])){echo "<ANSWER>HELLO</ANSWER>";die("DIE " .__FILE__." Line: ".__LINE__);}
if(isset($_GET["prepare-snapshot"])){prepare_snapshot();exit;}
if(isset($_GET["status-snapshot"])){status_snapshot();exit;}
if(isset($_GET["clean-snapshot"])){clean_snapshot();exit;}
if(isset($_GET["cluster-snapshot"])){cluster_snapshot();exit;}
if(isset($_GET["cluster-tables"])){cluster_tables();exit;}



function creds(){
	
	$users=new usersMenus();
	$ldap=new clladp();
	$creds=unserialize(base64_decode($_GET["creds"]));
	if(strtolower(trim($creds["ADM"]))<>strtolower(trim($ldap->ldap_admin))){
		echo "<ERROR>{authentication_failed}: With username: ".strtolower(trim($creds["ADM"]))."</ERROR>\n";
		return false;
	}
	
	$currentpass=md5(trim($ldap->ldap_password));
	if($creds["PASS"]<>$currentpass){
		echo "<ERROR>{authentication_failed}: With username: ".strtolower(trim($creds["ADM"]))."</ERROR>\n";
		return false;}
	
	return true;
	
}

function prepare_snapshot(){
	$medir=dirname(__FILE__);
	$cachefile=PROGRESS_DIR."/backup.artica.progress";
	if(is_file("$medir/ressources/logs/web/snapshot.tar.gz")){
		$timexe=file_time_min_Web("$medir/ressources/logs/web/snapshot.tar.gz");
		if($timexe<5){
			$array=array("POURC"=>100,"TEXT"=>"Cache file {$timexe}mn");
			@file_put_contents(PROGRESS_DIR."/backup.artica.progress", serialize($array));
			@chmod(PROGRESS_DIR."/backup.artica.progress",0755);
			return;
		}
		@unlink("$medir/ressources/logs/web/snapshot.tar.gz");
		
	}
	
	$sock=new sockets();
	$sock->getFrameWork("artica.php?snapshot-nomysql=yes");
}

function status_snapshot(){
	$cachefile=PROGRESS_DIR."/backup.artica.progress";
	echo "<ANSWER>".base64_encode(@file_get_contents($cachefile))."</ANSWER>\n<DETAILS>".@file_get_contents(PROGRESS_DIR."/backup.artica.progress.txt")."</DETAILS>";
}
function clean_snapshot(){
	@unlink(PROGRESS_DIR."/snapshot.tar.gz");
	echo "<ANSWER>OK</ANSWER>";
}

function cluster_tables(){
	$sock=new sockets();
	
	writelogs("Remote IP = {$_GET["myip"]}",__FUNCTION__,__FILE__,__LINE__);
	
	if(isset($_GET["myip"])){if($_GET["myip"]<>null){$SnapShotRemote["REMOTE_SERVER"]=$_GET["myip"];}}
	if($SnapShotRemote["REMOTE_SERVER"]==null){$SnapShotRemote["REMOTE_SERVER"]=GetRemoteIP();}
	
	$MAIN=unserialize(base64_decode($_GET["cluster-snapshot"]));
	if(!isset($MAIN["PASS"])){
		echo "* * * * ERROR {$SnapShotRemote["REMOTE_SERVER"]} no password set * * * * *\n";
	
	}
	$SnapShotRemote["REMOTE_ADMIN"]=$MAIN["ADM"];
	$SnapShotRemote["REMOTE_PASSWORD"]=$MAIN["PASS"];
	if(intval($SnapShotRemote["LISTEN_PORT"])==0){$SnapShotRemote["LISTEN_PORT"]=9000;}
	
	
	writelogs("Saving: Master server: {$SnapShotRemote["REMOTE_SERVER"]}:{$SnapShotRemote["LISTEN_PORT"]}",__FUNCTION__,__FILE__,__LINE__);
	writelogs("Saving: Master server: {$SnapShotRemote["REMOTE_ADMIN"]}",__FUNCTION__,__FILE__,__LINE__);
	$data=base64_encode(serialize($SnapShotRemote));
	writelogs("Saving: SnapShotRemote ".strlen($data)." bytes",__FUNCTION__,__FILE__,__LINE__);
	if($SnapShotRemote["REMOTE_SERVER"]<>null){
		$sock->SaveConfigFile($data, "SnapShotRemote");
		$sock->REST_API("/cluster/client/download");
	}
	echo "<ANSWER>OK</ANSWER>\n<DETAILS>\ncmdline:{$SnapShotRemote["CLUSTER_COMMAND"]}\ncluster_tables()\nREMOTE_ADDR={$_SERVER["REMOTE_ADDR"]}\nHTTP_X_REAL_IP={$_SERVER["HTTP_X_REAL_IP"]}\nHTTP_X_FORWARDED_FOR={$_SERVER["HTTP_X_FORWARDED_FOR"]}\n{$SnapShotRemote["REMOTE_SERVER"]}:{$SnapShotRemote["LISTEN_PORT"]}</DETAILS>";	
	
}

function cluster_snapshot(){
	$sock=new sockets();
	if(isset($_GET["myip"])){if($_GET["myip"]<>null){$SnapShotRemote["REMOTE_SERVER"]=$_GET["myip"];}}
	if($SnapShotRemote["REMOTE_SERVER"]==null){$SnapShotRemote["REMOTE_SERVER"]=GetRemoteIP();}
	
	$MAIN=unserialize(base64_decode($_GET["cluster-snapshot"]));
	if(!isset($MAIN["PASS"])){
		echo "* * * * ERROR {$SnapShotRemote["REMOTE_SERVER"]} no password set * * * * *\n";
		
	}
	$SnapShotRemote["REMOTE_ADMIN"]=$MAIN["ADM"];
	$SnapShotRemote["REMOTE_PASSWORD"]=$MAIN["PASS"];
	if(isset($_GET["cmdline"])){$SnapShotRemote["CLUSTER_COMMAND"]=base64_decode($_GET["cmdline"]);}
	
	
	if(intval($SnapShotRemote["LISTEN_PORT"])==0){$SnapShotRemote["LISTEN_PORT"]=9000;}
	$sock->SaveConfigFile(base64_encode(serialize($SnapShotRemote)), "SnapShotRemote");
	$cmd="artica.php?snapshot-retreive=yes&nodelete=yes";
	$sock->getFrameWork($cmd);
	echo "<ANSWER>OK</ANSWER>\n<DETAILS>\ncmdline:{$SnapShotRemote["CLUSTER_COMMAND"]}\nREMOTE_ADDR={$_SERVER["REMOTE_ADDR"]}\nHTTP_X_REAL_IP={$_SERVER["HTTP_X_REAL_IP"]}\nHTTP_X_FORWARDED_FOR={$_SERVER["HTTP_X_FORWARDED_FOR"]}\n{$SnapShotRemote["REMOTE_SERVER"]}:{$SnapShotRemote["LISTEN_PORT"]}</DETAILS>";
}

function GetRemoteIP(){
	if(isset($_SERVER["REMOTE_ADDR"])){$IPADDR=$_SERVER["REMOTE_ADDR"];}
	if(isset($_SERVER["HTTP_X_REAL_IP"])){$IPADDR=$_SERVER["HTTP_X_REAL_IP"];}
	if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
	return $IPADDR;
}