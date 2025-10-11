<?php

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
include_once(dirname(__FILE__).'/ressources/class.ActiveDirectory.inc');

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=='--build'){build();exit;}




function build(){
	if(!is_dir("/usr/share/phpldapadmin/config")){
		echo "slapd: [INFO] phpldapadmin not detected\n";
	}
	writelogs("Starting building phpldapadmin",__FUNCTION__,__FILE__,__LINE__);
	$ldap=new clladp();	
	$sock=new sockets();
	$EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
	$EnableParamsInPhpldapAdmin=$sock->GET_INFO("EnableParamsInPhpldapAdmin");
	if(!is_numeric($EnableParamsInPhpldapAdmin)){$EnableParamsInPhpldapAdmin=0;}
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}
	$suffix=trim(@file_get_contents("/etc/artica-postfix/ldap_settings/suffix"));
	$EnableOpenLdapProxy=$sock->GET_INFO("EnableOpenLdapProxy");
	$OpenLdapProxySuffix=$sock->GET_INFO("OpenLdapProxySuffix");
	if($OpenLdapProxySuffix==null){$OpenLdapProxySuffix="dc=meta";}
	if(!is_numeric($EnableOpenLdapProxy)){$EnableOpenLdapProxy=0;}		

$f[]="<?php";
$f[]="\$session[\"blowfish\"]=\"5ebe2294ecd0e0f08eab7690d2a6ee69\";";
$f[]="\$config->custom->appearance[\"tree\"] = \"AJAXTree\";";
$f[]="\$config->custom->appearance[\"friendly_attrs\"] = array(";
$f[]="	\"facsimileTelephoneNumber\" => \"Fax\",";
$f[]="	\"gid\"                      => \"Group\",";
$f[]="	\"mail\"                     => \"Email\",";
$f[]="	\"telephoneNumber\"          => \"Telephone\",";
$f[]="	\"uid\"                      => \"User Name\",";
$f[]="	\"userPassword\"             => \"Password\"";
$f[]=");";
$f[]="";
$f[]="";
$f[]="\$servers = new Datastore();";
$f[]="\$servers->newServer(\"ldap_pla\");";
$f[]="\$servers->setValue(\"server\",\"name\",\"Local LDAP Server\");";
$f[]="\$servers->setValue(\"server\",\"host\",\"$ldap->ldap_host\");";
$f[]="\$servers->setValue(\"server\",\"port\",$ldap->ldap_port);";
$f[]="\$servers->setValue(\"server\",\"base\",array(\"$suffix\"));";
$f[]="\$servers->setValue(\"login\",\"auth_type\",\"session\");";
$f[]="\$servers->setValue(\"login\",\"bind_id\",\"cn=$ldap->ldap_admin,$suffix\");";
$f[]="\$servers->setValue(\"login\",\"bind_pass\",\"\");";
$f[]="\$servers->setValue(\"server\",\"tls\",false);";
$f[]="";

if($EnableOpenLdapProxy==1){
	echo "slapd: [INFO] phpldapadmin adding LDAP-META Server settings\n";
$f[]="\$servers->newServer(\"ldap_pla\");";
$f[]="\$servers->setValue(\"server\",\"name\",\"Local LDAP-META Server\");";
$f[]="\$servers->setValue(\"server\",\"host\",\"$ldap->ldap_host\");";
$f[]="\$servers->setValue(\"server\",\"port\",$ldap->ldap_port);";
$f[]="\$servers->setValue(\"server\",\"base\",array(\"$OpenLdapProxySuffix\"));";
$f[]="\$servers->setValue(\"login\",\"auth_type\",\"session\");";
$f[]="\$servers->setValue(\"login\",\"bind_id\",\"cn=$ldap->ldap_admin,$OpenLdapProxySuffix\");";
$f[]="\$servers->setValue(\"login\",\"bind_pass\",\"\");";
$f[]="\$servers->setValue(\"server\",\"tls\",false);";
$f[]="";	
	
}

if($sock->SQUID_IS_EXTERNAL_LDAP()){

	$EXTERNAL_LDAP_AUTH_PARAMS=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidExternalAuth")));
	$ldap_server=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"];
	$ldap_port=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_port"];
	$userdn=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_user"];
	$ldap_password=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_password"];
	$ldap_suffix=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_suffix"];
	
	echo "slapd: [INFO] phpldapadmin adding LDAP Server for proy settings\n";
	$f[]="\$servers->newServer(\"ldap_pla\");";
	$f[]="\$servers->setValue(\"server\",\"name\",\"Remote $ldap_server\");";
	$f[]="\$servers->setValue(\"server\",\"host\",\"$ldap_server\");";
	$f[]="\$servers->setValue(\"server\",\"port\",$ldap_port);";
	$f[]="\$servers->setValue(\"server\",\"base\",array(\"$ldap_suffix\"));";
	$f[]="\$servers->setValue(\"login\",\"auth_type\",\"session\");";
	$f[]="\$servers->setValue(\"login\",\"bind_id\",\"$userdn\");";
	$f[]="\$servers->setValue(\"login\",\"bind_pass\",\"\");";
	$f[]="\$servers->setValue(\"server\",\"tls\",false);";
	$f[]="";
	
	
}



if($EnableKerbAuth==1){
	$ad=new ActiveDirectory();
	$f[]="\$servers->newServer(\"ldap_pla\");";
	$f[]="\$servers->setValue(\"server\",\"name\",\"ActiveDirectory {$ad->ldap_host}\");";
	$f[]="\$servers->setValue(\"server\",\"host\",\"{$ad->ldap_host}\");";
	$f[]="\$servers->setValue(\"server\",\"port\",$ad->ldap_port);";
	$f[]="\$servers->setValue(\"server\",\"base\",array(\"{$ad->suffix}\"));";
	$f[]="\$servers->setValue(\"login\",\"auth_type\",\"session\");";
	$f[]="\$servers->setValue(\"login\",\"bind_id\",\"{$ad->ldap_dn_user}\");";
	$f[]="\$servers->setValue(\"login\",\"bind_pass\",\"\");";
	$f[]="\$servers->setValue(\"server\",\"tls\",false);";
	$f[]="";	
}
	




if($EnableSambaActiveDirectory==1){
	$array=unserialize(base64_decode($sock->getFrameWork("cmd.php?net-ads-info=yes")));
	$ActiveDirectoryCredentials["suffix"]=$array["Bind Path"];
	$ActiveDirectoryCredentials["host"]=$array["LDAP server"];	
	if($ActiveDirectoryCredentials["host"]<>null){
		if($EnableParamsInPhpldapAdmin==1){
			$bind_id="{$ActiveDirectoryCredentials["bind_dn"]},{$ActiveDirectoryCredentials["suffix"]}";
				$f[]="\$servers->newServer(\"ldap_pla\");";
				$f[]="\$servers->setValue(\"server\",\"name\",\"ActiveDirectory {$ActiveDirectoryCredentials["host"]}\");";
				$f[]="\$servers->setValue(\"server\",\"host\",\"{$ActiveDirectoryCredentials["host"]}\");";
				$f[]="\$servers->setValue(\"server\",\"port\",389);";
				$f[]="\$servers->setValue(\"server\",\"base\",array(\"{$ActiveDirectoryCredentials["suffix"]}\"));";
				$f[]="\$servers->setValue(\"login\",\"auth_type\",\"session\");";
				$f[]="\$servers->setValue(\"login\",\"bind_id\",\"$bind_id\");";
				$f[]="\$servers->setValue(\"login\",\"bind_pass\",\"\");";
				$f[]="\$servers->setValue(\"server\",\"tls\",false);";
				$f[]="";
		}
	}
}
$pattern="(objectClass=AdLinker)";
$sr =@ldap_search($ldap->ldap_connection,$ldap->suffix,$pattern,array("dn"));
$hash=ldap_get_entries($ldap->ldap_connection,$sr);
	if($hash["count"]>0){
		include_once(dirname(__FILE__).'/ressources/class.activedirectory.inc');
		for($i=0;$i<$hash["count"];$i++){
			if(preg_match("#cn=adlinker,ou=(.+?),dc=organizations,#",$hash[$i]["dn"],$re)){
				echo "Starting lighttpd............: Build connexion for Active Directory Linker on \"{$re[1]}\" OU\n";
				$wad=new wad($re[1]);
				$f[]="\$servers->newServer(\"ldap_pla\");";
				$f[]="\$servers->setValue(\"server\",\"name\",\"ActiveDirectory {$wad->ldap_host}\");";
				$f[]="\$servers->setValue(\"server\",\"host\",\"{$wad->ldap_host}\");";
				$f[]="\$servers->setValue(\"server\",\"port\",389);";
				$f[]="\$servers->setValue(\"server\",\"base\",array(\"{$wad->suffix}\"));";
				$f[]="\$servers->setValue(\"login\",\"auth_type\",\"session\");";
				$f[]="\$servers->setValue(\"login\",\"bind_id\",\"\");";
				$f[]="\$servers->setValue(\"login\",\"bind_pass\",\"\");";
				$f[]="\$servers->setValue(\"server\",\"tls\",false);";
				$f[]="";				
				
			} 
		}
	}




$f[]="?>";
echo "slapd: [INFO] phpldapadmin success\n";
@file_put_contents("/usr/share/phpldapadmin/config/config.php",@implode("\n",$f));	
@chmod("/usr/share/phpldapadmin/config/config.php",0666);
}
