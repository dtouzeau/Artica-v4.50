<?php
include_once(dirname(__FILE__)."/class.mysql.inc");;
include_once(dirname(__FILE__)."/class.user.inc");


class owncloud_www{

	private $servername;
	private $database;
	private $uid;
	private $rebuildb=false;
	private $mysql_password=null;	
	private $mysql_username=null;
	private $mysql_instance_id=0;
	private $useSSL=0;
	private $www_dir=null;
	private $groupware_version=null;
	
	private function Load(){
			$sql="SELECT * from freeweb WHERE servername='$this->servername'";
			$q=new mysql();
			$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			$this->groupware=$ligne["groupware"];
			$this->servername=$ligne["servername"];
			$this->ou=$ligne["ou"];
			$this->www_dir=$ligne["www_dir"];
			$this->ServerPort=$ligne["ServerPort"];
			$this->mysql_password=$ligne["mysql_password"];
			$this->mysql_username=$ligne["mysql_username"];	
			$this->mysql_instance_id=$ligne["mysql_instance_id"];
				
			$this->useSSL=$ligne["useSSL"];	
			if($this->www_dir==null){$this->www_dir="/var/www/$this->servername";}
			writelogs("[$this->servername] Piwigo storage:$this->www_dir",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			
			$this->database="owncloud".time();
			if($ligne["mysql_database"]<>null){$this->database=$ligne["mysql_database"];}else{
				$sql="UPDATE freeweb SET mysql_database='{$this->database}' WHERE servername='$this->servername'";
				$q->QUERY_SQL($sql,"artica_backup");
			}
			
			if($this->mysql_username==null){$this->mysql_username=$q->mysql_admin;}
			if($this->mysql_password==null){$this->mysql_password=$q->mysql_password;}
			$this->groupware_version=$ligne["groupware_version"];
			$this->uid=$ligne["uid"];
		}
	
	function owncloud_www($servername){
		$this->servername=$servername;
		$this->load();
	}
	
	private function update_version(){
		$q=new mysql();
		$q->QUERY_SQL("UPDATE freeweb SET `groupware_version`='$this->groupware_version' WHERE servername='$this->servername'","artica_backup");
		
	}
	
	public function verifinstall(){
		$servername=$this->servername;
		$sources="/usr/local/share/artica/owncloud_src";
		$sourcesql="$sources/install/piwigo_structure-mysql.sql";
		$appli_password=null;
		$appli_user=null;
		if(!is_dir($sources)){writelogs("[$servername] $sources no such directory",__FUNCTION__,__FILE__,__LINE__);return;}
		$q=new mysql();
		$unix=new unix();
		$ldap=new clladp();
		if(!$q->DATABASE_EXISTS($this->database)){$q->CREATE_DATABASE($this->database);}
			
		if(!$this->checkfiles()){
			$cp=$unix->find_program("cp");
			$cmd="$cp -rf $sources/* $this->www_dir/";
			writelogs("[$servername] $cmd",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			exec($cmd,$results);
			if($GLOBALS["VERBOSE"]){while (list ($none, $line) = each ($results) ){writelogs("[$servername] $line",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}}
		}
		
		$unix=new unix();
		$apache_user=$unix->APACHE_SRC_ACCOUNT();
		$apache_group=$unix->APACHE_SRC_GROUP();
		writelogs("[$servername] Apply permissions $apache_user:$apache_group",__FUNCTION__,__FILE__,__LINE__);
		$unix->chmod_func(0755, "$this->www_dir");
		$unix->chown_func($apache_user, $apache_group,"$this->www_dir/*");
		writelogs("[$servername] CheckMYSQL",__FUNCTION__,__FILE__,__LINE__);
		$this->CheckMySQL();
		
	}
	
	private function checkfiles(){
		$f[]="cron.php";
		$f[]="index.php";
		$f[]="public.php";
		$f[]="remote.php";
		$f[]="status.php";


		$q=new mysql();
		while (list ($none, $file) = each ($f) ){
			if(!file_exists("$this->www_dir/$file")){
				writelogs("Fatal $this->www_dir/$file no such file",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
			
		}
		return true;		
		
	}	
	

	
	private function GenerateConfig(){
			$q=new mysql();
		if(is_file("$this->www_dir/config/config.php")){
			require "$this->www_dir/config/config.php";
			
		}
		$passwordsalt=null;
		$instanceid=null;
		
		if(!isset($CONFIG["passwordsalt"])){$CONFIG["passwordsalt"]=null;}
		if(!isset($CONFIG["instanceid"])){$CONFIG["instanceid"]=null;}
		
		
		
		if($CONFIG["passwordsalt"]<>null){$passwordsalt=$CONFIG["passwordsalt"];}
		if($CONFIG["instanceid"]<>null){$instanceid=$CONFIG["passwordsalt"];}
		
		if($passwordsalt==null){
			$passwordsalt=mt_rand(1000,9000).
				mt_rand(1000,9000).mt_rand(1000,9000).mt_rand(1000,9000).
				mt_rand(1000,9000).mt_rand(1000,9000).mt_rand(1000,9000).mt_rand(1000,9000);
		}
		if($instanceid==null){$instanceid=uniqid();}
		$homeDirectory="/home/owncloud/$this->servername";
		if($this->uid<>null){
			$ct=new user($this->uid);
			$homeDirectory=$ct->homeDirectory;
			if($homeDirectory==null){$homeDirectory="/home/$this->uid";}
		}
		
		
		$datadirectory="$homeDirectory/owncloud_data";
		$apps_paths="$homeDirectory/apps";
		@mkdir($datadirectory,0755,true);
		$f[]="<?php";
		$f[]="define(\"DEBUG\", true);";
		$f[]="\$CONFIG = array(";
		$f[]="\"installed\" => false,";
		$f[]="\"dbtype\" => \"mysql\",";
		$f[]="\"dbname\" => \"$this->database\",";
		$f[]="\"dbuser\" => \"$q->mysql_admin\",";
		$f[]="\"dbpassword\" => \"$q->mysql_password\",";
		$f[]="\"dbhost\" => \"$q->mysql_server\",";
		$f[]="\"dbtableprefix\" => \"oc_\",";
		$f[]="\"passwordsalt\" => \"$passwordsalt\",";
		$f[]="\"instanceid\" => \"$instanceid\",";
		$f[]="\"forcessl\" => false,";
		$f[]="\"theme\" => \"\",";
		$f[]="\"3rdpartyroot\" => \"\",";
		$f[]="\"3rdpartyurl\" => \"\",";
		$f[]="\"defaultapp\" => \"files\",";
		$f[]="\"knowledgebaseenabled\" => true,";
		$f[]="\"knowledgebaseurl\" => \"http://api.apps.owncloud.com/v1\",";
		$f[]="\"appstoreenabled\" => true,";
		$f[]="\"appstoreurl\" => \"http://api.apps.owncloud.com/v1\",";
		$f[]="\"mail_smtpmode\" => \"sendmail\",";
		$f[]="\"mail_smtphost\" => \"127.0.0.1\",";
		$f[]="\"mail_smtpauth\" => false,";
		$f[]="\"mail_smtpname\" => \"\",";
		$f[]="\"mail_smtppassword\" => \"\",";
		$f[]="\"appcodechecker\" => \"\",";
		$f[]="\"updatechecker\" => true,";
		$f[]="\"log_type\" => \"syslog\",";
		$f[]="\"logfile\" => \"$this->www_dir/log.txt\",";
		$f[]="/* Loglevel to start logging at. 0=DEBUG, 1=INFO, 2=WARN, 3=ERROR (default is WARN) */";
		$f[]="\"loglevel\" => \"0\",";
		$f[]="\"datadirectory\" => \"$this->www_dir/storage/data\",";
		$f[]="";
		$f[]="\"apps_paths\" => array(";
		$f[]="	array(";
		$f[]="		'path'=> '$this->www_dir/apps',";
		$f[]="		'url' => '/apps',";
		$f[]="		'writable' => true,";
		$f[]="  ),";
		$f[]=" ),";
		$f[]=");";
		$f[]="?>";		

		@file_put_contents("$this->www_dir/config/config.php", @implode("\n", $f));
		$unix=new unix();
		$mount=$unix->find_program("mount");
		$umount=$unix->find_program("umount");
		$apache_user=$unix->APACHE_SRC_ACCOUNT();
		$apache_group=$unix->APACHE_SRC_GROUP();
		writelogs("[$this->servername] Apply permissions $apache_user:$apache_group ln:`$mount`",__FUNCTION__,__FILE__,__LINE__);
		$unix->chmod_func(0755, "$this->www_dir");
		$unix->chown_func($apache_user, $apache_group,"$this->www_dir/config/config.php");
		writelogs("[$this->servername] Apply permissions `$apps_paths`",__FUNCTION__,__FILE__,__LINE__);
		@mkdir("$apps_paths",0755);
		$unix->chmod_func(0770, "$apps_paths");
		$unix->chown_func($apache_user, $apache_group,"$apps_paths");	

		writelogs("[$this->servername] Apply permissions `$datadirectory`",__FUNCTION__,__FILE__,__LINE__);
		@mkdir("$datadirectory",0755);
		$unix->chmod_func(0755, "$datadirectory");
		$unix->chown_func($apache_user, $apache_group,"$datadirectory");		
		
		writelogs("[$this->servername] Apply permissions `$this->www_dir/storage`",__FUNCTION__,__FILE__,__LINE__);
		@mkdir("$this->www_dir/storage",0755,true);
		$unix->chmod_func(0755, "$this->www_dir/storage");
		$unix->chown_func($apache_user, $apache_group,"$this->www_dir/storage");	
		
		writelogs("[$this->servername] Sym `$apps_paths`",__FUNCTION__,__FILE__,__LINE__);
		shell_exec("$umount -l $this->www_dir/storage/apps >/dev/null 2>&1");
		@unlink("$this->www_dir/storage/apps");
		@mkdir("$this->www_dir/storage/apps",0770,true);
		$unix->chmod_func(0770, "$apps_paths");
		$unix->chown_func($apache_user, $apache_group,"$this->www_dir/storage/apps");	
		shell_exec("$mount --bind -o user=$apache_user,group=$apache_user,rw $apps_paths $this->www_dir/storage/apps");
		
		writelogs("[$this->servername] Sym `$datadirectory`",__FUNCTION__,__FILE__,__LINE__);
		shell_exec("$umount -l $this->www_dir/storage/data >/dev/null 2>&1");
		@unlink("$this->www_dir/storage/data");
		@mkdir("$this->www_dir/storage/data",0770,true);		
		@unlink("$this->www_dir/storage/data");
		@mkdir("$this->www_dir/storage/data",0770,true);
		$unix->chmod_func(0770, "$this->www_dir/data");
		$unix->chmod_func(0770, "$datadirectory");
		$unix->chown_func($apache_user, $apache_group,"$this->www_dir/storage/data");	
		shell_exec("$mount --bind -o user=$apache_user,group=$apache_user,rw $datadirectory $this->www_dir/storage/data");
		writelogs("[$this->servername] Apply permissions done",__FUNCTION__,__FILE__,__LINE__);
		$this->createHtaccess();
	}
	
	private function createHtaccess() {
		$content = "ErrorDocument 403 /core/templates/403.php\n";//custom 403 error page
		$content.= "ErrorDocument 404 /core/templates/404.php\n";//custom 404 error page
		$content.= "<IfModule mod_php5.c>\n";
		$content.= "php_value upload_max_filesize 512M\n";//upload limit
		$content.= "php_value post_max_size 512M\n";
		$content.= "php_value memory_limit 512M\n";
		$content.= "<IfModule env_module>\n";
		$content.= "  SetEnv htaccessWorking true\n";
		$content.= "</IfModule>\n";
		$content.= "</IfModule>\n";
		$content.= "<IfModule mod_rewrite.c>\n";
		$content.= "RewriteEngine on\n";
		$content.= "RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\n";
		$content.= "RewriteRule ^.well-known/host-meta /public.php?service=host-meta [QSA,L]\n";
		$content.= "RewriteRule ^.well-known/carddav /remote.php/carddav/ [R]\n";
		$content.= "RewriteRule ^.well-known/caldav /remote.php/caldav/ [R]\n";
		$content.= "RewriteRule ^apps/([^/]*)/(.*\.(css|php))$ index.php?app=$1&getfile=$2 [QSA,L]\n";
		$content.= "RewriteRule ^remote/(.*) remote.php [QSA,L]\n";
		$content.= "</IfModule>\n";
		$content.= "Options -Indexes\n";
		@file_put_contents("$this->www_dir/.htaccess", $content); //supress errors in case we don't have permissions for it

		$content = "deny from all\n";
		$content.= "IndexIgnore *";
		file_put_contents("$this->www_dir/storage/data/.htaccess", $content);
		file_put_contents("$this->www_dir/storage/data/index.html", '');
	}	
	
	private function CreateTables(){
		/*require_once("$this->www_dir/lib/base.php");
		OC_DB::createDbFromStructure("$this->www_dir/db_structure.xml");
		
		if(is_file("$this->www_dir/appinfo/database.xml")) {
			OC_DB::createDbFromStructure("$this->www_dir/appinfo/database.xml");
		}
		*/
	
		
	
		
		$unix=new unix();
		$q=new mysql();
		$mysql=$unix->find_program("mysql");
		$sourcesql="/usr/share/artica-postfix/bin/install/owncloud.sql";
		$password=$q->mysql_password;
		if(strlen($password)>0){$password=" -p$password";}
		writelogs("[$this->servername] Installing table & database",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		$cmd="$mysql -u $q->mysql_admin$password --batch -h $q->mysql_server -P $q->mysql_port -D $this->database < $sourcesql 2>&1";
		if($GLOBALS["VERBOSE"]){writelogs("[$this->servername] $cmd",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		exec($cmd,$results);
		if($GLOBALS["VERBOSE"]){while (list ($none, $line) = each ($results) ){writelogs("[$this->servername] $line",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}}

	}
	
	
	private function CheckMySQL(){
		$q=new mysql();
		$ldap=new clladp();
		$this->GenerateConfig();
		if(!$this->checkTables()){$this->CreateTables();}
		if(!$this->checkTables()){
			writelogs("[$this->servername] Failed creating tables....",__FUNCTION__,__FILE__,__LINE__);	
			return;
		}
		
		chdir($this->www_dir."/lib");
		$_SERVER["SCRIPT_FILENAME"]="index.php";
		$_SERVER["SCRIPT_NAME"]= "$this->www_dir/index.php";
		ini_set('include_path',ini_get('include_path').":$this->www_dir/3rdparty:$this->www_dir/lib:"); 
		
		
		writelogs("[$this->servername] Include $this->www_dir/lib/base.php",__FUNCTION__,__FILE__,__LINE__);
		
		$_SERVER["SCRIPT_FILENAME"]="$this->www_dir/index.php";
		$_SERVER["SCRIPT_NAME"]= "$this->www_dir/index.php";		
		require_once("$this->www_dir/lib/base.php");
		$version=OC_Config::getValue( "version", "" );
		$this->groupware_version=$version;
		$this->update_version();
		writelogs("[$this->servername] GetVersion $version...",__FUNCTION__,__FILE__,__LINE__);
		$passwordsalt=OC_Config::getValue( "passwordsalt", "" );
		writelogs("[$this->servername] getValue(passwordsalt) = $passwordsalt...",__FUNCTION__,__FILE__,__LINE__);
		
		if($version==null){
			writelogs("[$this->servername] set Version...",__FUNCTION__,__FILE__,__LINE__);
			writelogs("Create MySQL configuration ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			OC_Config::setValue('version',implode('.',OC_Util::getVersion()));
		}

		if(!$this->checkTables()){
			OC_DB::createDbFromStructure("$this->www_dir/db_structure.xml");
		}
		
		if(!$this->checkTables()){
			writelogs("Check tables failed",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return;
		}
		$installdat=OC_Appconfig::getValue('core', 'installedat','');
		writelogs("installedat `$installdat`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		
		if($installdat==null){OC_Appconfig::setValue('core', 'installedat',microtime(true));}
		$installdat=OC_Appconfig::getValue('core', 'lastupdatedat','');
		if($installdat==null){OC_Appconfig::setValue('core', 'lastupdatedat',microtime(true));}
		if(!OC_Group::groupExists('admin')){
			if(!OC_Group::createGroup('admin')){
				writelogs("Failed to create admin group",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return;
			}
		}
		
		if(!OC_Group::groupExists('Users')){
			if(!OC_Group::createGroup('Users')){
				writelogs("Failed to create Users group",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return;
			}
		}
		
		if($this->uid<>null){
			$ct=new user($this->uid);
			writelogs("Create $this->uid user",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			OC_User::createUser($this->uid,$ct->password);
			OC_Group::addToGroup($this->uid, 'Users');
		}
						
		
		if(!OC_User::userExists($ldap->ldap_admin)){
			writelogs("Create $ldap->ldap_admin user",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			if (!OC_User::createUser($ldap->ldap_admin,$ldap->ldap_password)){
				writelogs("Creating $ldap->ldap_admin entry failed",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return;
			}
		}else{
			writelogs("$ldap->ldap_admin Already exists in $this->database...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			OC_User::setPassword($ldap->ldap_admin,$ldap->ldap_password);
		}
		
		
		
		OC_Group::addToGroup($ldap->ldap_admin, 'admin');
		OC_Config::setValue('installed', true);
		
		
		/*\OCP\Config::setAppValue("user_ldap", 'ldap_uuid_attribute', 'auto');
		\OCP\Config::setAppValue("user_ldap", 'ldap_host', $ldap->ldap_host);
		\OCP\Config::setAppValue("user_ldap", 'ldap_port', $ldap->ldap_port);
		\OCP\Config::setAppValue("user_ldap", 'ldap_dn', "cn=$ldap->ldap_admin,$ldap->suffix");
		\OCP\Config::setAppValue("user_ldap", 'ldap_agent_password', base64_encode($ldap->ldap_password));
		\OCP\Config::setAppValue("user_ldap", 'ldap_base', $ldap->suffix);
		\OCP\Config::setAppValue("user_ldap", 'ldap_display_name', "displayName");
		\OCP\Config::setAppValue("user_ldap", 'ldap_userlist_filter', "objectClass=posixAccount");
		\OCP\Config::setAppValue("user_ldap", 'ldap_login_filter', "(uid=%uid)");
		\OCP\Config::setAppValue("user_ldap", 'ldap_group_filter', "(objectClass=posixGroup)");
		\OCP\Config::setAppValue("user_ldap", 'ldap_group_display_name', "cn");
		\OCP\Config::setAppValue("user_ldap", 'ldap_group_member_assoc_attribute', "memberUid");
		\OCP\Config::setAppValue("user_ldap", 'ldap_email_attr', "mail");
		\OCP\Config::setAppValue("user_ldap", 'home_folder_naming_rule', "homeDirectory");
		*/
		writelogs("Finish instance id: `$getInstanceId`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	}
	
	private function checkTables(){
		$f["oc_appconfig"]=true;
		$f["oc_calendar_calendars"]=true;
		$f["oc_calendar_objects"]=true;
		$f["oc_calendar_repeat"]=true;
		$f["oc_calendar_share_calendar"]=true;
		$f["oc_calendar_share_event"]=true;
		$f["oc_contacts_addressbooks"]=true;
		$f["oc_contacts_cards"]=true;
		$f["oc_fscache"]=true;
		$f["oc_gallery_sharing"]=true;
		$f["oc_groups"]=true;
		$f["oc_group_admin"]=true;
		$f["oc_group_user"]=true;
		$f["oc_locks"]=true;
		$f["oc_media_albums"]=true;
		$f["oc_media_artists"]=true;
		$f["oc_media_sessions"]=true;
		$f["oc_media_songs"]=true;
		$f["oc_media_users"]=true;
		$f["oc_pictures_images_cache"]=true;
		$f["oc_preferences"]=true;
		$f["oc_properties"]=true;
		$f["oc_queuedtasks"]=true;
		$f["oc_share"]=true;
		$f["oc_users"]=true;
		
		$q=new mysql();
		while (list ($table, $file) = each ($f) ){
			
			if(!$q->TABLE_EXISTS($table, $this->database)){
				writelogs("No such table `$table` in $this->database",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
		}
		return true;
		
	}
	
	
	
}