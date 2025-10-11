<?php


function AskPasswordAuth($title){
	
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body($title);
	
	if (!isset($_SERVER['PHP_AUTH_USER'])) {
		header('WWW-Authenticate: Basic realm="'.$title.'"');
		header('HTTP/1.0 401 Unauthorized');
		die("DIE " .__FILE__." Line: ".__LINE__);
		if(!CheckPassword($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW'])){
			header('WWW-Authenticate: Basic realm="'.$title.'"');
			header('HTTP/1.0 401 Unauthorized');
			die("DIE " .__FILE__." Line: ".__LINE__);
		}
	}
	
	
	if($_SESSION["uid"]==null){
		header('WWW-Authenticate: Basic realm="'.$title.'"');
		header('HTTP/1.0 401 Unauthorized');
		if(!CheckPassword($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW'])){header('HTTP/1.0 401 Unauthorized');die("DIE " .__FILE__." Line: ".__LINE__);}
	}	
	
}


function CheckPassword($username,$password){

	include(dirname(__FILE__)."/settings.inc");
	include_once(dirname(__FILE__)."/class.user.inc");
	$FixedLanguage=null;
	$users=new usersMenus();
	if($password==null){ return false; }

	writelogs("$username<>{$_GLOBAL["ldap_admin"]} $password<>{$_GLOBAL["ldap_password"]}",__FUNCTION__,__FILE__,__LINE__);
	
	if(trim(strtolower($username))==trim(strtolower($_GLOBAL["ldap_admin"]))){
		if(trim($password)<>trim($_GLOBAL["ldap_password"])){return false;}
		$users=new usersMenus();
		$_SESSION["uid"]='-100';
		$_SESSION["groupid"]='-100';
		
		
		
		$_SESSION["detected_lang"]=$_POST["lang"];
		$_SESSION["CORP"]=$users->CORP_LICENSE;
		$_SESSION["privileges"]["ArticaGroupPrivileges"]='
			[AllowAddGroup]="yes"
			[AllowAddUsers]="yes"
			[AllowChangeKav]="yes"
			[AllowChangeKas]="yes"
			[AllowChangeUserPassword]="yes"
			[AllowEditAliases]="yes"
			[AllowEditAsWbl]="yes"
			[AsSystemAdministrator]="yes"
			[AsPostfixAdministrator]="yes"
			[AsArticaAdministrator]="yes"
			';
		return true;
	}


	writelogs("$username:: Continue, processing....",__FUNCTION__,__FILE__,__LINE__);
	$ldap=new clladp();
	$IsKerbAuth=$ldap->IsKerbAuth();
	writelogs("$username:: Is AD -> $IsKerbAuth",__FUNCTION__,__FILE__,__LINE__);

	if($ldap->IsKerbAuth()){
		$external_ad_search=new external_ad_search();
		if($external_ad_search->CheckUserAuth($username,$password)){
			$users=new usersMenus();
			$privs=new privileges($_POST["username-logon"]);
			$privileges_array=$privs->privs;
			$_SESSION["InterfaceType"]="{ARTICA_MINIADM}";
			$_SESSION["VirtAclUser"]=false;
			setcookie("mem-logon-user", $_POST["username-logon"], time()+172800);
			$_SESSION["privileges_array"]=$privs->privs;
			$_SESSION["uid"]=$_POST["username-logon"];
			$_SESSION["passwd"]=$_POST["username-logon"];
			$_SESSION["privileges"]["ArticaGroupPrivileges"]=$privs->content;
			BuildSession($username);
			return true;
		}
	}


	writelogs("$username:: Continue, processing....",__FUNCTION__,__FILE__,__LINE__);

	$q=new mysql();
	$sql="SELECT `username`,`value`,id FROM radcheck WHERE `username`='$username' AND `attribute`='Cleartext-Password' LIMIT 0,1";
	writelogs("$username:: Is a RADIUS users \"$sql\"",__FUNCTION__,__FILE__,__LINE__);
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!is_numeric($ligne["id"])){$ligne["id"]=0;}
	if(!$q->ok){writelogs("$username:: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
	writelogs("$username:: $password <> ".md5($ligne["value"]),__FUNCTION__,__FILE__,__LINE__);
	if($ligne["id"]>0){
		$checkRadiusPass=false;
		if(md5($ligne["value"])==$password){
			writelogs("$username:: RADIUS Password true for no MD5",__FUNCTION__,__FILE__,__LINE__);
			$checkRadiusPass=true;
		}
		if(md5($ligne["value"])==$passwordMD){
			writelogs("$username:: RADIUS Password true for yes MD5",__FUNCTION__,__FILE__,__LINE__);
			$checkRadiusPass=true;
		}


		if($checkRadiusPass){
			writelogs("$username:: Authenticated as a RADIUS users id={$ligne["id"]}",__FUNCTION__,__FILE__,__LINE__);
			$privs=new privileges($_POST["username-logon"],null,$ligne["id"]);
			$privileges_array=$privs->privs;
			$_SESSION["CORP"]=$users->CORP_LICENSE;
			$_SESSION["InterfaceType"]="{ARTICA_MINIADM}";
			setcookie("mem-logon-user", $username, time()+172800);
			$_SESSION["privileges_array"]=$privs->privs;
			while (list ($key, $val) = each ($_SESSION["privileges_array"]) ){if(!isset($_SESSION[$key])){$_SESSION[$key]=$val;}}
			reset($_SESSION["privileges_array"]);
			$_SESSION["uid"]=$username;
			$_SESSION["RADIUS_ID"]=$ligne["id"];
			BuildSession($username);
			return true;
		}
	}



	writelogs("$username::Finally Is LOCAL LDAP ? -> $IsKerbAuth",__FUNCTION__,__FILE__,__LINE__);
	$u=new user($username);
	$userPassword=$u->password;


	if(trim($u->uidNumber)==null){return false;}
	if( trim($password)<>trim($userPassword)){return false;}

	$ldap=new clladp();
	$users=new usersMenus();
	$_SESSION["CORP"]=$users->CORP_LICENSE;
		
	$privs=new privileges($u->uid);
	$privs->SearchPrivileges();
	$privileges_array=$privs->privs;
	$_SESSION["VirtAclUser"]=false;
	$_SESSION["privileges_array"]=$privs->privs;
	$_SESSION["privs"]=$privileges_array;
	if(isset($privileges_array["ForceLanguageUsers"])){$_SESSION["OU_LANG"]=$privileges_array["ForceLanguageUsers"];}
	$_SESSION["uid"]=$username;
	$_SESSION["privileges"]["ArticaGroupPrivileges"]=$privs->content;
	$_SESSION["groupid"]=$ldap->UserGetGroups($_POST["username"],1);
	$_SESSION["DotClearUserEnabled"]=$u->DotClearUserEnabled;
	$_SESSION["MailboxActive"]=$u->MailboxActive;

	$_SESSION["ou"]=$u->ou;
	$_SESSION["UsersInterfaceDatas"]=trim($u->UsersInterfaceDatas);
	include_once(dirname(__FILE__)."/ressources/class.translate.rights.inc");
	$cr=new TranslateRights(null, null);
	$r=$cr->GetPrivsArray();
	while (list ($key, $val) = each ($r) ){

		if($users->$key){$_SESSION[$key]=$users->$key;}}

		if(is_array($_SESSION["privs"])){
			$r=$_SESSION["privs"];
			while (list ($key, $val) = each ($r) ){
				$t[$key]=$val;
				$_SESSION[$key]=$val;
			}
		}



		if(!isset($_SESSION["OU_LANG"])){$_SESSION["OU_LANG"]=null;}
		if(!isset($_SESSION["ASDCHPAdmin"])){$_SESSION["ASDCHPAdmin"]=false;}


		if(trim($_SESSION["OU_LANG"])<>null){
			$_SESSION["detected_lang"]=$_SESSION["OU_LANG"];
		}else{
			include_once(dirname(__FILE__)."/ressources/class.langages.inc");
			$lang=new articaLang();
			$_SESSION["detected_lang"]=$lang->get_languages();
		}
		if(trim($FixedLanguage)<>null){$_SESSION["detected_lang"]=$FixedLanguage;}
		return true;


}
?>