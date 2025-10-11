<?php
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}
$GLOBALS["WAIT"]=false;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.artica.inc");
include_once(dirname(__FILE__)."/ressources/class.pure-ftpd.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/charts.php");
include_once(dirname(__FILE__)."/ressources/class.mimedefang.inc");
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.ini.inc");

if($argv[1]=="--create"){create_user($argv[2]);exit();}
if($argv[1]=="--progress"){create_user_from_mysql();exit();}


function create_user_from_mysql(){
	$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
	$GLOBALS["WAIT"]=true;
	
	build_progress("{start}",10);
	$results=$q->QUERY_SQL("SELECT * FROM CreateUserQueue");
	if(!$q->ok){
		echo $q->mysql_error;
		build_progress("MySQL error",110);
		return;
	}
	if(!is_array($results)){
        $results=array();
    }
	@mkdir("/usr/share/artica-postfix/ressources/logs/web/create-users",0755,true);
	echo count($results)." member(s) to create...\n";
	
	foreach ($results as $index=>$ligne){
		$zMD5=$ligne["zMD5"];
		$content=$ligne["content"];
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/create-users/$zMD5", $content);
		if(create_user($zMD5)){
			build_progress("{removing_order}: $index",95);
			$q->QUERY_SQL("DELETE FROM `CreateUserQueue` WHERE `zMD5`='$zMD5'");
		}else{
			$q->QUERY_SQL("DELETE FROM `CreateUserQueue` WHERE `zMD5`='$zMD5'");
			build_progress("{failed}",110);
			return;
		}
	}
	
	build_progress("{done}",100);
}

function build_progress($text,$pourc):bool{
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/create-user.progress", serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);
	if($GLOBALS["WAIT"]){usleep(800);}
    return true;
}


function create_user($filename){
	$tpl=new templates();
	$unix=new unix();
	$nohup=null;
	$path="/usr/share/artica-postfix/ressources/logs/web/create-users/$filename";
	echo "Path:$path\n";
	build_progress("Open $filename",10);
	
	if(!is_file($path)){
		echo "$path no such file...\n";
		return false;	
	}
	
	$MAIN=unserialize(base64_decode(@file_get_contents($path)));

	
	build_progress("Create new member {$MAIN["login"]}",15);
	
	$users=new user($MAIN["login"]);
	if($users->password<>null){
		echo "User already exists {$MAIN["login"]}\n";
		build_progress("{account_already_exists}",110);
		@unlink($path);
		return true;
	}
	$ou=$MAIN["ou"];
	$password=url_decode_special_tool($MAIN["password"]);
	$MAIN["firstname"]=url_decode_special_tool($MAIN["firstname"]);
	$MAIN["lastname"]=url_decode_special_tool($MAIN["lastname"]);
	
	build_progress("{$MAIN["firstname"]} {$MAIN["lastname"]}",20);
	 
	if(strpos($MAIN["email"], "@")){
		$tt=explode("@",$MAIN["email"]);
		$MAIN["email"]=$tt[0];
		$MAIN["internet_domain"]=$tt[1];
	}
	
	build_progress("{$MAIN["email"]}@{$MAIN["internet_domain"]}",21);
	 
	if(trim($MAIN["internet_domain"])==null){$MAIN["internet_domain"]="localhost.localdomain";}
	echo "Add new user {$MAIN["login"]} {$MAIN["ou"]} {$MAIN["gpid"]}\n";
	$users->ou=$MAIN["ou"];
	$users->password=url_decode_special_tool($MAIN["password"]);
	$users->mail="{$MAIN["email"]}@{$MAIN["internet_domain"]}";
	$users->DisplayName="{$MAIN["firstname"]} {$MAIN["lastname"]}";
	$users->givenName=$MAIN["firstname"];
    $users->sn=$MAIN["lastname"];
    $users->group_id=$MAIN["gpid"];
	$users->homeDirectory="/home/{$MAIN["login"]}";
	      
   
	      
	      
if(is_numeric($MAIN["gpid"])){
	$gp=new groups($MAIN["gpid"]);
	echo "privileges: {$MAIN["gpid"]} -> AsComplexPassword = \"{$gp->Privileges_array["AsComplexPassword"]}\"\n";
	if($gp->Privileges_array["AsComplexPassword"]=="yes"){
		$ldap=new clladp();
		$hash=$ldap->OUDatas($ou);
		$privs=$ldap->_ParsePrivieleges($hash["ArticaGroupPrivileges"],array(),true);
		$policiespwd=unserialize(base64_decode($privs["PasswdPolicy"]));
		if(is_array($policiespwd)){
			$priv=new privileges();
		    	if(!$priv->PolicyPassword($password,$policiespwd)){
		    	build_progress("Need complex password",110);
		     	echo "Need complex password";@unlink($path);return true;
		     }
		  }
	 }
}

build_progress("{$MAIN["firstname"]} {$MAIN["lastname"]} {save}",25);

if(!$users->add_user()){
	echo $users->error."\n".$users->ldap_error;
	build_progress("{failed}",110);
	@unlink($path);
	return false;
}

@mkdir("$users->homeDirectory");
@chown("$users->homeDirectory",$users->uid);



echo "Remove $path\n";
@unlink($path);
return true;
}


