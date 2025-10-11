<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.backup.inc');

	
	if(!isset($_GET["params"])){die("<ERROR>Protocol Error</ERROR>");}
	$array=unserialize(base64_decode($_GET["params"]));
	if(!is_array($array)){die("<ERROR>Protocol Error</ERROR>");}
	
	$RemoteArticaServer=$array["RemoteArticaServer"];
	$RemoteArticaPort=$array["RemoteArticaPort"];
	$RemoteArticaUser=$array["RemoteArticaUser"];
	$RemoteArticaPassword=$array["RemoteArticaPassword"];
	$RemoteArticaSite=$array["RemoteArticaSite"];	
	if($RemoteArticaSite==null){die("<ERROR>Protocol Error: missing FreeWeb website</ERROR>");}
	if(!authProto($RemoteArticaUser,$RemoteArticaPassword,$RemoteArticaSite)){die("<ERROR>Authentication failed</ERROR>");}
	
	if(isset($_GET["remove"])){remove_container($array);exit();}
	
	SendBackup($array);
	
	
	
function authProto($RemoteArticaUser,$RemoteArticaPassword){
	
	if($RemoteArticaUser==null){return false;}
	include("ressources/settings.inc");
	$md5Manager=md5(trim($_GLOBAL["ldap_password"]));
	if(trim(strtolower($_GLOBAL["ldap_admin"]))==trim(strtolower($RemoteArticaUser))){
		if($md5Manager<>$RemoteArticaPassword){return false;}
		return true;
	}
	
	$free=new freeweb($RemoteArticaSite);
	if($free->uid==null){return false;}
	if($free->uid<>$RemoteArticaUser){return false;}
	
	$u=new user($RemoteArticaUser);
	$userPassword=$u->password;	
	if($RemoteArticaPassword<>md5(trim($userPassword))){return false;}
	return true;
	
}

function remove_container($array){
	$RemoteArticaServer=$array["RemoteArticaServer"];
	$RemoteArticaPort=$array["RemoteArticaServer"];
	$RemoteArticaUser=$array["RemoteArticaUser"];
	$RemoteArticaPassword=$array["RemoteArticaPassword"];
	$RemoteArticaSite=$array["RemoteArticaSite"];	
	$targetpackage=dirname(__FILE__)."/ressources/logs/web/$RemoteArticaSite.tar.gz";
	if(is_file($targetpackage)){@unlink($targetpackage);}
	
}

function SendBackup($array){
	
	$RemoteArticaServer=$array["RemoteArticaServer"];
	$RemoteArticaPort=$array["RemoteArticaServer"];
	$RemoteArticaUser=$array["RemoteArticaUser"];
	$RemoteArticaPassword=$array["RemoteArticaPassword"];
	$RemoteArticaSite=$array["RemoteArticaSite"];	
	if($RemoteArticaSite==null){die("<ERROR>Protocol Error: missing FreeWeb website line:". __LINE__."</ERROR>");}	
	$sock=new sockets();
	$targetpackage=dirname(__FILE__)."/ressources/logs/web/$RemoteArticaSite.tar.gz";
	
	
	$datas=base64_decode($sock->getFrameWork("freeweb.php?articaget=$RemoteArticaSite"));
	if(!is_file($targetpackage)){
		echo "<LOGS>SITE:`$RemoteArticaSite`\n------------------\n$targetpackage no sich file</LOGS>\n\n<RESULTS>FAILED</RESULTS>";
		exit();
	}
	echo $datas."<LOGS>$datas</LOGS>\n\n<RESULTS>SUCCESS</RESULTS>";
	
}
