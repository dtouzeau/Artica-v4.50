<?php
$GLOBALS["FORCE"]=false;$GLOBALS["REINSTALL"]=false;
$GLOBALS["NO_HTTPD_CONF"]=false;
$GLOBALS["NO_HTTPD_RELOAD"]=false;
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--reinstall#",implode(" ",$argv))){$GLOBALS["REINSTALL"]=true;}
	if(preg_match("#--no-httpd-conf#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_CONF"]=true;}
	if(preg_match("#--noreload#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_RELOAD"]=true;}
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["posix_getuid"]=0;
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.groups.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
$GLOBALS["SSLKEY_PATH"]="/etc/ssl/certs/apache";	
$GLOBALS["CLASS_UNIX"]=new unix();
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}}

$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$pid=@file_get_contents($pidfile);
if($GLOBALS["CLASS_UNIX"]->process_exists($pid,basename(__FILE__))){exit();}
@file_put_contents($pidfile, getmypid());
$GLOBALS["a2enmod"]=$GLOBALS["CLASS_UNIX"]->find_program("a2enmod");
$apache2ctl=$GLOBALS["CLASS_UNIX"]->find_program("apache2ctl");
if(!is_file($apache2ctl)){$apache2ctl=$GLOBALS["CLASS_UNIX"]->find_program("apachectl");}
if(!is_file($apache2ctl)){echo "Starting......: ".date("H:i:s")." Apache apache2ctl no such file\n";}
$GLOBALS["APACHECTL"]=$apache2ctl;


if($GLOBALS["VERBOSE"]){
	echo "Debug mode TRUE for ". @implode(" ",$argv)."\n";
	echo "LOCATE_APACHE_BIN_PATH.....:".$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_BIN_PATH()."\n";
	echo "LOCATE_APACHE_CONF_PATH....:".$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_CONF_PATH()."\n";
	echo "a2enmod....................:{$GLOBALS["a2enmod"]}\n";
	echo "apachectl..................:{$GLOBALS["APACHECTL"]}\n";
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
}
buildUsers();

function buildUsers(){
	$users=new usersMenus();
	$unix=new unix();
	$sock=new sockets();
	$q=new mysql();
	$sql="SELECT * FROM webdavusers";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){die($q->mysql_error);}	
	$c=0;
	$APACHE_DIR_SITES_ENABLED=$unix->APACHE_DIR_SITES_ENABLED();
	if($GLOBALS["VERBOSE"]){echo "APACHE_DIR_SITES_ENABLED.....: $APACHE_DIR_SITES_ENABLED\n";}
	foreach (glob("$APACHE_DIR_SITES_ENABLED/webdav.*.apache") as $filename) {
		if($GLOBALS["VERBOSE"]){echo "Unlink: $filename\n";}
		@unlink($filename);
		$c++;
	}

	
	$EnableWebDavPerUser=$sock->GET_INFO("EnableWebDavPerUser");
	$WebDavPerUserSets=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebDavPerUserSets")));
	if(!is_numeric($EnableWebDavPerUser)){$EnableWebDavPerUser=0;}
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	if($EnableFreeWeb==0){if($GLOBALS["VERBOSE"]){echo "FreeWebs is not enabled\n";}return;}
	if($EnableWebDavPerUser==0){if($GLOBALS["VERBOSE"]){echo "EnableWebDavPerUser is not enabled\n";}return;}
	$WebDavSuffix=$WebDavPerUserSets["WebDavSuffix"];
	if($WebDavSuffix==null){if($GLOBALS["VERBOSE"]){echo "WebDavSuffix is not set\n";}return;}
	
	
	$FreeWebListen=$unix->APACHE_ListenDefaultAddress();
	$FreeWebListenPort=$sock->GET_INFO("FreeWebListenPort");
	$FreeWebListenSSLPort=$sock->GET_INFO("FreeWebListenSSLPort");
	$FreeWebsDisableSSLv2=$sock->GET_INFO("FreeWebsDisableSSLv2");
	if($FreeWebListen==null){$FreeWebListen="*";}
	if($FreeWebListen<>"*"){$FreeWebListenApache="$FreeWebListen";}	
	if($FreeWebListenSSLPort==null){$FreeWebListenSSLPort=443;}
	if(!is_numeric($FreeWebListenSSLPort)){$FreeWebListenSSLPort=443;}
	if(!is_numeric($FreeWebListenPort)){$FreeWebListenPort=80;}
	if(!is_numeric($FreeWebsDisableSSLv2)){$FreeWebsDisableSSLv2=0;}	


	
	if($unix->isNGnx()){
		$FreeWebListenPort=82;
		$FreeWebListenPort=447;
		$FreeWebListen="127.0.0.1";
	}	
	
	$port=$FreeWebListen;	
	
	
	$SSL=$WebDavPerUserSets["EnableSSL"];
	if(!is_numeric($SSL)){$SSL=0;}
	echo "Starting......: ".date("H:i:s")." Apache Listen $FreeWebListen:$FreeWebListenPort, SSL enabled=$SSL SSL Port:$FreeWebListenSSLPort SSLv2=$FreeWebsDisableSSLv2\n";
	
	
	
	
	
	
	$ldap=new clladp();
	
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		
		$uid=$ligne["uid"];
		$usr=new user($uid);
		$HomeDirectory=$usr->homeDirectory;
		if(trim($HomeDirectory)==null){if($GLOBALS["VERBOSE"]){echo "$uid: Home Directory is not set !\n";continue;}}
		if(!is_numeric($usr->group_id)){if($GLOBALS["VERBOSE"]){echo "Search group of $uid...\n";}$usr->group_id=getentGroup($uid);}
		if($GLOBALS["VERBOSE"]){echo "servername will be $uid.$WebDavSuffix usergroup = `$usr->group_id`\n";}
		$group=new groups($usr->group_id);
		if($group->groupName==null){if($GLOBALS["VERBOSE"]){echo "Cannot find group name for $uid\n";}continue;}
		
		$servername="$uid.$WebDavSuffix";
		@mkdir("$usr->homeDirectory/.dav",0755,true);
		$f=array();
		
		if($SSL==1){
			$GLOBALS["CLASS_UNIX"]->vhosts_BuildCertificate($servername);
			$port=$FreeWebListenSSLPort;
			$f[]="<VirtualHost $FreeWebListen:$FreeWebListenPort>";
			$f[]="\tRewriteEngine On";
			$f[]="\tRewriteCond %{HTTPS} off";
			$f[]="\tRewriteRule (.*) https://%{HTTP_HOST}:$FreeWebListenSSLPort";
			$f[]="</VirtualHost>";
			$f[]="";
			$FreeWebListenPort=$FreeWebListenSSLPort;
		}		
		
		
		
		$f[]="<VirtualHost $FreeWebListen:$FreeWebListenPort>";
		
		if($SSL==1){
			$f[]="\tSetEnvIf User-Agent \".*MSIE.*\" nokeepalive ssl-unclean-shutdown downgrade-1.0 force-response-1.0";
			$f[]="\tSSLEngine on";
			$f[]="\tSSLCertificateFile {$GLOBALS["SSLKEY_PATH"]}/$servername.crt";
			$f[]="\tSSLCertificateKeyFile {$GLOBALS["SSLKEY_PATH"]}/$servername.key";	
			if($FreeWebsDisableSSLv2==1){
				$f[]="\tSSLProtocol -ALL +SSLv3 +TLSv1";
				$f[]="\tSSLCipherSuite ALL:!aNULL:!ADH:!eNULL:!LOW:!EXP:RC4+RSA:+HIGH:+MEDIUM";
			}			
		}		
		
		$f[]="\tServerName $servername";
		$f[]="\tServerAdmin $usr->mail";
		$f[]="\tDocumentRoot $usr->homeDirectory";
		//$f[]="        ServerAlias hostname.domaine.tld";
		$f[]="\t<IfModule mpm_itk_module>";
		$f[]="\t\tAssignUserId $usr->uid $group->groupName";
		$f[]="\t</IfModule>";
		$f[]="#WEBDAV";
		$f[]="\tDavLockDB \"$usr->homeDirectory/.dav/DavLock\"";
		include_once(dirname(__FILE__)."/ressources/class.freeweb.inc");
		$freeweb=new freeweb();
		$conf[]=$freeweb->WebDavBrowserMatches();
		
		
		$f[]="\t<Directory $usr->homeDirectory>";
		$f[]="\t\tOptions Indexes FollowSymLinks MultiViews";
		$f[]="\t\tAllowOverride None";
		$f[]="\t\tOrder allow,deny";
		$f[]="\t\tallow from all";
		$f[]="\tDAV On";
        $f[]="\tDAVMinTimeout 600";
        $f[]="\tAuthType Basic";
        $f[]="\tAuthBasicProvider ldap";
        $f[]="\tAuthName \"$servername $uid Only\"";
        $f[]="\tAuthLDAPURL ldap://$ldap->ldap_host:$ldap->ldap_port/dc=organizations,$ldap->suffix?uid?sub";
        $f[]="\tAuthLDAPBindDN cn=$ldap->ldap_admin,$ldap->suffix";
        $f[]="\tAuthLDAPBindPassword $ldap->ldap_password";
        $f[]="\tAuthLDAPGroupAttribute memberUid";
        $f[]="\tRequire user $uid";
        $f[]="\tRequire valid-user";		
		$f[]="\t</Directory>";
		$f[]="";
		$f[]="\tLogFormat \"%h %l %u %t \\\"%r\\\" %>s %b \\\"%{Referer}i\\\" \\\"%{User-Agent}i\\\" %V\" combinedv";
		$f[]="\tCustomLog $usr->homeDirectory/webdav_access.log combinedv";
		$f[]="\tErrorLog $usr->homeDirectory/webdav_error.log";
		$f[]="\tLogLevel warn";
		$f[]="";

			
		$f[]="</VirtualHost>";	
		if($GLOBALS["VERBOSE"]){echo "$uid saving $APACHE_DIR_SITES_ENABLED/webdav.$uid.apache\n";}
		@file_put_contents("$APACHE_DIR_SITES_ENABLED/webdav.$uid.apache", @implode("\n", $f));	
		$c++;
	}	
	if($c>0){
		if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["APACHECTL"]} -k restart 2>&1\n";}
		exec("{$GLOBALS["APACHECTL"]} -k restart 2>&1",$results);
	}
	
	
}
function getentGroup($uid){
	$id=$GLOBALS["CLASS_UNIX"]->find_program("id");
	exec("$id $uid 2>&1",$results);
	if(preg_match("#uid=[0-9]+.+?gid=([0-9]+)#", @implode("", $results),$re)){
		return $re[1];
	}
}