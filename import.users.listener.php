<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.user.inc');
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	if(!islogged()){die("DIE " .__FILE__." Line: ".__LINE__);}
	
	
	
	if(isset($_POST["OUS"])){IMPORT_OUS();die("DIE " .__FILE__." Line: ".__LINE__);}
	if(isset($_POST["MEMBERS"])){IMPORT_MEMBERS();die("DIE " .__FILE__." Line: ".__LINE__);}
	while (list ($num, $ligne) = each ($_POST)){
		writelogs("Unable to undertsand: $num = $ligne","MAIN",__FILE__,__LINE__);
		echo "Unable to undertsand: $num = $ligne\n";}

function islogged($nomurder=0,$noecho=0){
	$users=new usersMenus();
	$ldap=new clladp();
	
	if(!isset($_GET["AuThMeth"])){return false;}
	
	$array=unserialize(base64_decode($_GET["AuThMeth"]));
	
	if($array["admin"]<>$ldap->ldap_admin){
		if($noecho==0){
			echo("$users->hostname (Artica v$users->ARTICA_VERSION):: {failed} {NT_STATUS_LOGON_FAILURE}");
			die("DIE " .__FILE__." Line: ".__LINE__);
		}
		return false;
	}
	
	if($array["pass"]<>$ldap->ldap_password){
		if($noecho==0){
			echo("$users->hostname (Artica v$users->ARTICA_VERSION):: {failed} {NT_STATUS_LOGON_FAILURE} ");
			die("DIE " .__FILE__." Line: ".__LINE__);
		}
		return false;
	}
		
	
	
	return true;
}

function IMPORT_MEMBERS(){
	$me=$_SERVER["SERVER_NAME"];
	$error=array();
	
	
	$members=unserialize(base64_decode($_POST["MEMBERS"]));
	
	writelogs("Analyze ".count($members)." members for ou ".$_POST["OU"],__FUNCTION__,__FILE__,__LINE__);
	
	while (list($uid,$array)=each($members)){
		writelogs("Analyze $uid for ou ".$_POST["OU"],__FUNCTION__,__FILE__,__LINE__);
		$user=new user($uid);
		if($user->UserExists){
			$user->password=$array["password"];
			if($user->add_user()){$success[]="$me::IMPORT_MEMBERS:: Success updating $uid in LDAP database";}else{$error[]="Failed updating $uid in LDAP database\n $user->ldap_error";}
			continue;
			
		}
		
		while (list($key,$value)=each($array)){$user->$key=$value;}
		if($user->add_user()){$success[]="$me::IMPORT_MEMBERS:: Success adding $uid in LDAP database";}else{$error[]="Failed adding $uid in LDAP database\n $user->ldap_error";}
	}
	if(count($error)>0){
		echo "<ERROR>".@implode("\n", $error)."</ERROR>";
		
	}
	
	if(count($success)>0){
		echo "<SUCCESS>".@implode("\n", $success)."</SUCCESS>";
	}		
	
	
}

function IMPORT_OUS(){
	$me=$_SERVER["SERVER_NAME"];
	$error=array();
	$ldap=new clladp();
	$ous=unserialize(base64_decode($_POST["OUS"]));
	while (list($num,$org)=each($ous)){
		if(trim($org)==null){continue;}
		if(!$ldap->AddOrganization($org)){
			$error[]="Unable to add $org in LDAP database\n $ldap->ldap_last_error";
			continue;
		}else{
			$success[]="Success adding $org in LDAP database";
		}
		
	}
	
	if(count($error)>0){
		echo "<ERROR>".@implode("\n", $error)."</ERROR>";
		
	}
	
	if(count($success)>0){
		echo "<SUCCESS>".@implode("\n", $success)."</SUCCESS>";
	}	
	
}