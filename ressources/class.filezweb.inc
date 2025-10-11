<?php
include_once(dirname(__FILE__)."/class.mysql.inc");;
include_once(dirname(__FILE__)."/class.user.inc");


class filez_www{

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
			
			$this->database="filez".time();
			if($ligne["mysql_database"]<>null){$this->database=$ligne["mysql_database"];}else{
				$sql="UPDATE freeweb SET mysql_database='{$this->database}' WHERE servername='$this->servername'";
				$q->QUERY_SQL($sql,"artica_backup");
			}
			
			if($this->mysql_username==null){$this->mysql_username=$q->mysql_admin;}
			if($this->mysql_password==null){$this->mysql_password=$q->mysql_password;}
			$this->groupware_version=$ligne["groupware_version"];
			$this->uid=$ligne["uid"];
		}
	
	function __construct($servername){
		$this->servername=$servername;
		$this->load();
	}
	
	private function update_version(){
		$q=new mysql();
		$q->QUERY_SQL("UPDATE freeweb SET `groupware_version`='$this->groupware_version' WHERE servername='$this->servername'","artica_backup");
		
	}
	
	public function verifinstall(){
		$servername=$this->servername;
		$sources="/usr/share/filez";
		$sourcesql="$sources/config/db/schema.sql";
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
		$this->SetConfig();
		$this->SetDefault();
		$this->SetCss();
		$unix=new unix();
		$apache_user=$unix->APACHE_SRC_ACCOUNT();
		$apache_group=$unix->APACHE_SRC_GROUP();
		writelogs("[$servername] Apply permissions $apache_user:$apache_group",__FUNCTION__,__FILE__,__LINE__);
		$unix->chmod_func(0755, "$this->www_dir");
		@mkdir("/home/fz_uploads/$servername",0755,true);
		$unix->chown_func($apache_user, $apache_group,"$this->www_dir/*");
		$unix->chown_func($apache_user, $apache_group,"/home/fz_uploads/$servername/*");
		writelogs("[$servername] CheckMYSQL",__FUNCTION__,__FILE__,__LINE__);
		$this->CheckMySQL();
		
	}
	
	private function checkfiles(){
		
		$f[]="favicon.ico";
		$f[]="index.php";
		
		$q=new mysql();
		while (list ($none, $file) = each ($f) ){
			if(!file_exists("$this->www_dir/$file")){
				writelogs("Fatal $this->www_dir/$file no such file",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
			
		}
		$f=array();
		$f[]="app";
		$f[]="config";
		$f[]="doc";
		$f[]="i18n";
		$f[]="lib";
		$f[]="resources";
		$f[]="scripts";
		while (list ($none, $file) = each ($f) ){
			if(!is_dir("$this->www_dir/$file")){
				writelogs("Fatal $this->www_dir/$file no such directory",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
				
		}		
		
		return true;		
		
	}	

	
	
	
	private function CreateTables(){
		$q=new mysql();
		$sql="CREATE TABLE IF NOT EXISTS `fz_file` (
		  `id`              BIGINT UNSIGNED NOT NULL,
		  `del_notif_sent`  BOOLEAN         DEFAULT 0,
		  `file_name`       varchar(100)    NOT NULL,
		  `file_size`       INTEGER         DEFAULT 0,
		  `available_from`  DATE            NOT NULL,
		  `available_until` DATE            NOT NULL,
		  `comment`         varchar(200),
		  `download_count`  INTEGER         DEFAULT 0,
		  `notify_uploader` BOOLEAN         DEFAULT 0,
		  `created_by`      INTEGER         NOT NULL,
		  `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  `extends_count`   INTEGER         DEFAULT '0',
		  `password`        varchar(40)     DEFAULT NULL,
		  INDEX       (`created_by`),
		  FOREIGN KEY (`created_by`) REFERENCES fz_user(id),
		  UNIQUE KEY `id` (`id`)
		) DEFAULT CHARSET=utf8;
		";
		
		if(!$q->QUERY_SQL($sql,$this->database)){return false;}
		$sql="CREATE TABLE  `fz_info` ( `key`   VARCHAR( 30 ) NOT NULL , `value` VARCHAR( 50 ) NOT NULL , PRIMARY KEY (  `key` ) ) DEFAULT CHARSET=utf8;";
		if(!$q->QUERY_SQL($sql,$this->database)){return false;}

		$sql="CREATE TABLE `fz_user` (
		`id`          SERIAL      NOT NULL,
		`username`    VARCHAR(40) NOT NULL,
		`password`    VARCHAR(40) NOT NULL,
		`salt`        VARCHAR(40),
		`firstname`   VARCHAR(50) NOT NULL,
		`lastname`    VARCHAR(50) NOT NULL,
		`email`       VARCHAR(50) NOT NULL,
		`is_admin`    BOOLEAN     DEFAULT 0,
		`created_at`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP
		) ENGINE = MYISAM ;	";
		if(!$q->QUERY_SQL($sql,$this->database)){return false;}
		if(!$q->QUERY_SQL("INSERT IGNORE INTO `fz_info` (`key`, `value`) VALUES ('cron_freq', NOW());",$this->database)){return false;}
		if(!$q->QUERY_SQL("INSERT IGNORE INTO `fz_info` (`key`, `value`) VALUES ('db_version', '3.0-alpha') ON DUPLICATE KEY UPDATE value = '3.0-alpha';",$this->database)){return false;}
		
		$ldap=new clladp();
		
		if(!$q->QUERY_SQL("INSERT IGNORE INTO `fz_user` (`username`, `password`, `firstname`, `lastname`, `email`, `is_admin`)
		VALUES ('admin', SHA1('filez'), 'FileZ', 'admin', 'foo@bar.com', 1);",$this->database)){return false;}		
		
		if(!$q->QUERY_SQL("INSERT IGNORE INTO `fz_user` (`username`, `password`, `firstname`, `lastname`, `email`, `is_admin`)
		VALUES ('$ldap->ldap_admin', SHA1('$ldap->ldap_password'), 'FileZ', 'admin', 'foo@bar.com', 1);",$this->database)){return false;}		
		return true;
	}
	
	
	private function CheckMySQL(){
		$q=new mysql();
		$ldap=new clladp();
		
		if(!$this->checkTables()){$this->CreateTables();}
		if(!$this->checkTables()){
			writelogs("[$this->servername] Failed creating tables....",__FUNCTION__,__FILE__,__LINE__);	
			return;
		}
		
		
	}
	
	private function checkTables(){
		$f["fz_file"]=true;
		$f["fz_info"]=true;
		$f["fz_user"]=true;
	
		$q=new mysql();
		while (list ($table, $file) = each ($f) ){
			
			if(!$q->TABLE_EXISTS($table, $this->database)){
				writelogs("No such table `$table` in $this->database",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
		}
		return true;
		
	}
	
	private function SetDefault(){
		$f[]=";";
		$f[]=";                 NEVER EDIT THIS FILE";
		$f[]=";";
		$f[]="; To customize your installation, edit \"filez.ini\". If you make";
		$f[]="; changes here, they will be lost on next upgrade of FileZ!";
		$f[]=";";
		$f[]="; Documentation is in the source of filez.ini.example and on:";
		$f[]="; https://github.com/UAPV/FileZ/wiki";
		$f[]=";";
		$f[]="[app]";
		$f[]="debug = false";
		$f[]="send_us_a_file_feature = false";
		$f[]="use_url_rewriting = true";
		$f[]="log_dir = /var/log/filez";
		$f[]="upload_dir = /var/filez/uploads";
		$f[]="filez1_compat = false";
		$f[]="max_file_lifetime     = 20";
		$f[]="default_file_lifetime = 10";
		$f[]="max_extend_count = 7";
		$f[]="min_hash_size = 4";
		$f[]="max_hash_size = 8";
		$f[]="default_locale = fr";
		$f[]="auth_handler_class = Fz_Controller_Security_Internal";
		$f[]="user_factory_class = Fz_User_Factory_Database";
		$f[]="user_quota = 2G";
		$f[]="https = off";
		$f[]="progress_monitor = Fz_UploadMonitor_Apc";
		$f[]="sharing_destinations[] = email";
		$f[]="admin_email = postmaster@example.com";
		$f[]="antivirus = false";
		$f[]="; force_fqdn = filez.domaine.fr; used by getDownloadUrl() in app/models/File.php See https://github.com/FileZ/FileZ/issues/24";
		$f[]="; cache_dir = tmp; Shared hosting often needs to specify a writeable dir as temp dir. (fix Zend error: \"specify a cache_dir\")";
		$f[]="";
		$f[]="[db]";
		$f[]="dsn  = \"mysql:host=localhost;dbname=filez\"";
		$f[]="user = filez";
		$f[]="; db_dialect = \"Sqlite\"";
		$f[]="";
		$f[]="[cron]";
		$f[]="days_before_expiration_mail = 2";
		$f[]="frequency = +1 day";
		$f[]="";
		$f[]="[email]";
		$f[]="from_name=Filez";
		$f[]="from_email = filez@example.com";
		$f[]="host=smtp.example.com";
		$f[]="name = filez";
		$f[]="";
		$f[]="[user_factory_options]";
		//$f[]="db_use_global_conf = true";
		//$f[]="db_table = fz_user";
		//$f[]="db_username_field = username";
		//$f[]="db_password_field = password";
		//$f[]="db_password_algorithm = SHA1";
		$f[]="; You have the possibility to block certain users with:";
		$f[]="; accountFilterFormat   = \"(&(objectClass=posixAccount)(uid=%s)(mailenable=true))\" See https://github.com/FileZ/FileZ/issues/12";
		$f[]="";
		$f[]="[user_attributes_translation]";
		$f[]="firstname = firstname";
		$f[]="lastname  = lastname";
		$f[]="email     = email";
		$f[]="id        = username";
		$f[]="";
		$f[]="[looknfeel]";
		//$f[]="show_credit = true";
		//$f[]="help_url = help\n";
		@file_put_contents("$this->www_dir/config/filez.default.ini", @implode("\n", $f));
		@copy("/usr/share/artica-postfix/img/usb-share-128.png","$this->www_dir/resources/images/usb-share-128.png");
		
	}
	
	
	private function SetConfig(){
		$params=$free->Params["FILEZ"];
		$ldap=new clladp();
		
		if($params["SMTP_SERVER"]==null){$params["SMTP_SERVER"]="127.0.0.1";}
		if($params["MAILFROM"]==null){$params["MAILFROM"]="root@mydomain.tld";}
		if($params["FROMNAME"]==null){$params["FROMNAME"]="The Postmaster";}
		if($params["LDAP_SERVER"]==null){$params["LDAP_SERVER"]="127.0.0.1";}
		if($params["LDAP_SUFFIX"]==null){$params["LDAP_SUFFIX"]="dc=organizations,$ldap->suffix";}
		if($params["LDAP_DN"]==null){$params["LDAP_DN"]="cn=$ldap->ldap_admin,$ldap->suffix";}
		if($params["LDAP_PASSWORD"]==null){$params["LDAP_PASSWORD"]="$ldap->ldap_password";}
		if($params["LDAP_FILTER"]==null){$params["LDAP_FILTER"]="(&(objectclass=*)(uid=%s))";}
		
		
		
		if(!is_numeric($params["post_max_size"])){$params["post_max_size"]=750;}
		if(!is_numeric($params["upload_max_filesize"])){$params["upload_max_filesize"]=750;}
		if(!is_numeric($params["max_file_lifetime"])){$params["max_file_lifetime"]=20;}
		if(!is_numeric($params["default_file_lifetime"])){$params["default_file_lifetime"]=10;}
		if(!is_numeric($params["max_extend_count"])){$params["max_extend_count"]=7;}
		if(!is_numeric($params["user_quota"])){$params["user_quota"]=2;}
		
		
		
		$f[]=";;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;";
		$f[]="; General configuration";
		$f[]=";;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;";
		$f[]="[app]";
		$f[]="use_url_rewriting = true";
		$f[]="";
		$f[]="; Must be writtable by the web server";
		$f[]="upload_dir = /home/fz_uploads/$this->servername";
		$f[]="";
		$f[]="; Must be writtable by the web server";
		$f[]="log_dir = $this->www_dir";
		$f[]="";
		$f[]="; Allow downloading file sent with filez 1.x";
		$f[]="filez1_compat = true";
		$f[]="";
		$f[]="; Max and default lifetime of a file on the server";
		$f[]="max_file_lifetime     = {$params["max_file_lifetime"]} ";
		$f[]="default_file_lifetime = {$params["default_file_lifetime"]}";
		$f[]="";
		$f[]="; Maximum number of days the lifetime of a file can be extended";
		$f[]="max_extend_count = {$params["max_extend_count"]}";
		$f[]="";
		$f[]="; Min/Max size of files hash codes";
		$f[]="min_hash_size = 4";
		$f[]="max_hash_size = 6";
		$f[]="";
		$f[]="; Default langage when no translation exists for the user locale";
		$f[]="default_locale = en";
		$f[]="";
		$f[]="; Name of the class used to authenticate the user. Built-in handler are :";
		$f[]=";  * Fz_Controller_Security_Cas       Log the user against a CAS server";
		$f[]=";  * Fz_Controller_Security_Internal  Log the user with a built-in Controller.";
		$f[]=";                                     This controller will check user/pass with";
		$f[]=";                                     'login' method in the user factory.";
		$f[]="auth_handler_class = Fz_Controller_Security_Internal";
		$f[]="";
		$f[]="; Name of the class used to identify the user. Built-in factories are :";
		$f[]=";  * Fz_User_Factory_Ldap";
		$f[]=";  * Fz_User_Factory_Database";
		$f[]="user_factory_class = Fz_User_Factory_Ldap";
		$f[]="";
		$f[]="; Max disk spaced per user. Support shorthand format : M (Mega), G (Giga)";
		$f[]="user_quota = {$params["user_quota"]}G";
		$f[]="";
		$f[]="; File extension allowed to be uploaded. Empty means no restriction";
		$f[]=";allowed_extensions = png,jpg,jpeg,gif";
		$f[]="";
		$f[]=";;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;";
		$f[]="; Cron Job";
		$f[]=";;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;";
		$f[]="[cron]";
		$f[]="; Number of days before before sending the notification mail for deletion";
		$f[]="days_before_expiration_mail = 2";
		$f[]="";
		$f[]=";;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;";
		$f[]="; Look'n feel";
		$f[]=";;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;";
		$f[]="[looknfeel]";
		$f[]="; Logo image";
		$f[]="your_logo               = \"resources/images/usb-share-128.png\"";
		$f[]="";
		$f[]="; Custom CSS";
		$f[]="; custom_css              = \"resources/css/custom.css\"";
		$f[]="";
		$f[]="; show filez credits at the bottom of the pages";
		$f[]="show_credit = false";
		$f[]="";
		$f[]="; Url where the user will find Filez documentation";
		//$f[]="help_url = help ; Relative or absolute";
		$f[]="";
		$f[]="; Where does the user reports bug ?";
		//$f[]="bug_report_href = mailto:some-one@somewhere.com ; or http://your-bug-tracker.com";
		$f[]="";
		$f[]=";;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;";
		$f[]="; Database configuration";
		$f[]=";;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;";
		$q=new mysql();
		$f[]="[db]";
		$f[]="dsn      = \"mysql:host=$q->mysql_server;dbname=$this->database\"";
		$f[]="user     = $this->mysql_username";
		$f[]="password = $this->mysql_password";
		$f[]="";
		$f[]="";
		$f[]=";;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;";
		$f[]="; Mail configuration";
		$f[]=";;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;";
		

		
		$f[]="[email]";
		$f[]="from_email={$params["MAILFROM"]}";
		$f[]="from_name=\"{$params["FROMNAME"]}\"";
		$f[]="host={$params["SMTP_SERVER"]}";
		$f[]="; auth=login ; possible values = crammd5, login, plain";
		$f[]="; port=25";
		$f[]="; username=user";
		$f[]="; password=pwd";
		$f[]="";
		$f[]="";
		$f[]=";;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;";
		$f[]="; Authentication configuration";
		$f[]=";;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;";
		$f[]="[auth_options]";
		$f[]="; Parameters for the CAS authentication handler.";
		$f[]="cas_server_host = cas.univ-avignon.fr";
		$f[]="";
		$f[]="";
		$f[]=";;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;";
		$f[]="; Identification configuration";
		$f[]=";;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;";
		$f[]="[user_factory_options]";
		$f[]="; Parameters passed to the Ldap user factory.";
		$f[]="; Available parameters : http://framework.zend.com/manual/en/zend.ldap.api.html";
		
		$baseDn=$params["LDAP_SUFFIX"];
		$f[]="host = {$params["LDAP_SERVER"]}";
		$f[]="useSsl = false";
		$f[]="baseDn = \"$baseDn\"";
		$f[]="bindRequiresDn = true";
		$f[]="username = \"{$params["LDAP_DN"]}\"";
		$f[]="password = \"{$params["LDAP_PASSWORD"]}\"";
		$f[]="accountFilterFormat = \"{$params["LDAP_FILTER"]}\"";
		
		
		
		$f[]="";
		$f[]="; Parameters for the Database user factory";
		$f[]="; db_use_global_conf    = false";
		$f[]="; db_server_dsn         = \"mysql:host=localhost;dbname=filez\"";
		$f[]="; db_server_user        = filez";
		$f[]="; db_server_password    = filez";
		$f[]="; db_table              = user";
		$f[]="; db_password_field     = password";
		$f[]="; db_username_field     = username";
		$f[]="; db_password_algorithm = SHA1";
		$f[]="; Possible algorithm are :";
		$f[]=";   - MD5         (unsecure)";
		$f[]=";   - SHA1        (unsecure)";
		$f[]=";   - PHP Function name ex: \"methodName\"";
		$f[]=";   - PHP Static method ex: \"ClassName::Method\"";
		$f[]=";   - Plain SQL ex: \"SHA1(CONCAT(salt, :password))\"";
		$f[]=";";
		$f[]="; You have the possibility to block certain users with: (see also https://github.com/FileZ/FileZ/issues/12)";
		$f[]="; accountFilterFormat   = \"(&(objectClass=posixAccount)(uid=%s)(mailenable=true))\"";
		$f[]="";
		$f[]="[user_attributes_translation]";
		$f[]="; In order to make the application schema agnostic with differents user storage";
		$f[]="; facilities, each user attributes is translated from its original name to the";
		$f[]="; application name. The syntax is as follow : application_name = original_name.";
		$f[]="; This attributes are required by filez :";
		$f[]=";  * firstname";
		$f[]=";  * lastname";
		$f[]=";  * email";
		$f[]=";  * id";
		$f[]="; Exemple for an Ldap repository :";
		$f[]="firstname = givenname";
		$f[]="lastname  = sn";
		$f[]="email     = mail";
		$f[]="id        = uid";
		$f[]="";

		@file_put_contents("$this->www_dir/config/filez.ini", @implode("\n", $f));
		
	}
	
	private function SetCss(){
		$f[]="/* General */";
		$f[]="html {";
		$f[]="  background: #376EBF;";
		$f[]="}";
		$f[]="";
		$f[]="body {";
		$f[]="  font-size:100%;";
		$f[]="  font-family: Arial, sans-serif;";
		$f[]="  line-height:1.125em;";
		
		$f[]="}";
		$f[]="p {";
		$f[]="  font-size:0.875em;";
		$f[]="}";
		$f[]="";
		$f[]="header, article, footer {";
		$f[]="  margin-left: auto;";
		$f[]="  margin-right: auto;";
		$f[]="  width: 60%;";
		$f[]="  max-width: 650px;";
		$f[]="  background-color: #FFFFFF;";
		$f[]="  padding-top: 10px;";
		$f[]="  padding-bottom: 10px;";
		$f[]="  margin-top: 10px;";
		$f[]="  border-radius: 5px 5px 5px 5px;";
		$f[]="  border: 1px solid #1E52A1;";
		$f[]="}";
		
		$f[]=".article{";
		$f[]="	padding:5px;";
		$f[]="}";
		$f[]="h1 {";
		$f[]="}";
		$f[]="a {";
		$f[]="  color: black;";
		$f[]="  text-decoration: none;";
		$f[]="}";
		$f[]="a:hover {";
		$f[]="  color: #E70707;";
		$f[]="}";
		$f[]=".clearboth {";
		$f[]="	clear: both;";
		$f[]="	height: 0px;";
		$f[]="	display: block;";
		$f[]="}";
		$f[]="";
		$f[]="/* ";
		$f[]=" * Section new-file & uploaded-files";
		$f[]=" */";
		$f[]="section {";
		$f[]="  background-color: #FFF;";
		$f[]="  -moz-border-radius:    5px;";
		$f[]="  -webkit-border-radius: 5px;";
		$f[]="  border-radius:         5px;";
		$f[]="  border-width: 1px;";
		$f[]="  border-style: solid;";
		$f[]="  border-color: #D4D0C9;";
		$f[]="  margin-top:     0.4em;";
		$f[]="  margin-bottom: 1.5em;";
		$f[]="}";
		$f[]=".qtip section {";
		$f[]="  -moz-border-radius:    0;";
		$f[]="  -webkit-border-radius: 0;";
		$f[]="  border: none;";
		$f[]="  margin: 0;";
		$f[]="}";
		$f[]="h2 {";
		$f[]="  /* letter-spacing: -0.05em; */";
		$f[]="  font-weight: normal;";
		$f[]="  font-size: 1.1em;";
		$f[]="}";
		$f[]="h2 span.filename {";
		$f[]="  font-size: 0.8em;";
		$f[]="}";
		$f[]="";
		$f[]="/*";
		$f[]=" * Header";
		$f[]=" */";
		$f[]="header {";
		$f[]="  padding: 3em 0 1.4em 0;";
		$f[]="  position: relative;";
		$f[]="}";
		$f[]="header h1 {";
		$f[]="}";
		$f[]="header #filez-logo {";
		$f[]="  display: block;";
		$f[]="  margin: auto 0;";
		$f[]="}";
		$f[]="header #filez-header {";
		$f[]="  font-weight: bold;";
		$f[]="  font-size: 1em;";
		$f[]="  color: black;";
		$f[]="  display: block;";
		$f[]="}";
		$f[]="header #your-logo {";
		$f[]="  display: block;";
		$f[]="  float: left;";
		$f[]="  margin-top: -1em;";
		$f[]="  margin-right: 1em;";
		$f[]="  margin-left: 1em;";
		$f[]="  padding-right: 1em;";
		$f[]="  border-right: 1px solid #D9D4CF;";
		$f[]="}";
		$f[]="#auth-box {";
		$f[]="  position: absolute;";
		$f[]="  top: 6px;";
		$f[]="  right: 4px;";
		$f[]="  -moz-border-radius-bottomleft:      6px;";
		$f[]="  -webkit-border-bottom-left-radius:  6px;";
		$f[]="  -moz-border-radius-bottomright:     6px;";
		$f[]="  -webkit-border-bottom-right-radius: 6px;";
		$f[]="  border-radius: 6px 6px 6px 6px;";
		$f[]="	border: 1px solid #B90000;";
		$f[]="  padding: 0.3em 0.7em;";
		$f[]="  color:#B90000;";
		$f[]="  font-size: 0.8em;";
		$f[]="}";
		$f[]="header #auth-box a#logout {";
		$f[]="  background: transparent url(../images/icons/system-log-out.png) no-repeat right center;";
		$f[]="  padding: 8px;";
		$f[]="  text-indent: -2000px;";
		$f[]="  overflow: hidden;";
		$f[]="}";
		$f[]="";
		$f[]="/*";
		$f[]=" * Notifications";
		$f[]=" */";
		$f[]=".notif {";
		$f[]="    -moz-border-radius:		 10px;";
		$f[]="    -webkit-border-radius: 10px;";
		$f[]="    padding: 1em;";
		$f[]="    margin-top: 2em;";
		$f[]="    background: #fffec2; /* notif ok */";
		$f[]="    border: 1px solid #E5E4AD; /* notif ok */";
		$f[]="}";
		$f[]=".notif.error {";
		$f[]="    background: #f2e1dc;";
		$f[]="    border: 1px solid #f3c5c1;";
		$f[]="}";
		$f[]=".notif.error h1 {";
		$f[]="    color: #AC342B;";
		$f[]="}";
		$f[]=".notif .notif-close {";
		$f[]="    float: right;";
		$f[]="    height: 16px;";
		$f[]="    width: 16px;";
		$f[]="    text-indent: -3000em;";
		$f[]="    display: block;";
		$f[]="    background: transparent url(../images/icons/dialog-close.png) no-repeat center center;";
		$f[]="    opacity: 0.4;";
		$f[]="    filter:alpha(opacity=40);";
		$f[]="    -moz-border-radius:    3px;";
		$f[]="    -webkit-border-radius: 3px;";
		$f[]="}";
		$f[]=".notif .notif-close:hover ,";
		$f[]=".notif:hover .notif-close {";
		$f[]="    background-color: #f3c5c1;";
		$f[]="    opacity: 1;";
		$f[]="    filter:alpha(opacity=100);";
		$f[]="}";
		$f[]="";
		$f[]="/*";
		$f[]=" * Section new-file";
		$f[]=" */";
		$f[]="section.new-file {";
		$f[]="  -moz-box-shadow:    inset 0 20px 20px rgba(0,0,0,0.04);";
		$f[]="  -webkit-box-shadow: inset 0 20px 20px rgba(0,0,0,0.04);";
		$f[]="}";
		$f[]=".qtip section.new-file {";
		$f[]="  -moz-box-shadow:    none;";
		$f[]="  -webkit-box-shadow: none;";
		$f[]="}";
		$f[]="section.new-file form {";
		$f[]="  position: relative;";
		$f[]="}";
		$f[]="section.new-file div#start-from {";
		$f[]="  width: 20%;";
		$f[]="  position: absolute; top: 0;";
		$f[]="}";
		$f[]="section.new-file div#file {";
		$f[]="  position: absolute; top: 0;";
		$f[]="  width: 40%;";
		$f[]="}";
		$f[]="section.new-file div#file       ,";
		$f[]="section.new-file div#lifetime   ,";
		$f[]="section.new-file div#start-from {";
		$f[]="";
		$f[]="}";
		$f[]="section.new-file div#file       input ,";
		$f[]="section.new-file div#lifetime   select ,";
		$f[]="section.new-file div#comment    input ,";
		$f[]="section.new-file div#start-from input {";
		$f[]="  box-sizing:         border-box;";
		$f[]="  -moz-box-sizing:    border-box;";
		$f[]="  -webkit-box-sizing: border-box;";
		$f[]="  width: 100%;";
		$f[]="}";
		$f[]="section.new-file div#file       { left:  1.3em; }";
		$f[]="section.new-file div#start-from { right: 1.3em; }";
		$f[]="section.new-file div#file input {";
		$f[]="}";
		$f[]="section.new-file div#lifetime {";
		$f[]="  width: 18%;";
		$f[]="  margin: 0 28% 0 auto;";
		$f[]="}";
		$f[]="section.new-file div#start-from input {";
		$f[]="  background: transparent url(../images/icons/office-calendar.png) no-repeat 6px center;";
		$f[]="  padding-left: 28px;";
		$f[]="}";
		$f[]="section.new-file div#comment ,";
		$f[]="section.new-file ul#options {";
		$f[]="  padding: 0 1.3em;";
		$f[]="}";
		$f[]="section.new-file label {";
		$f[]="  padding-top: 0.8em;";
		$f[]="  display: block;";
		$f[]="}";
		$f[]="section.new-file div#upload {";
		$f[]="  background: #F6F8FB;";
		$f[]="  -moz-border-radius-bottomright:     5px;";
		$f[]="  -webkit-border-radius-bottomright:  5px;";
		$f[]="  border-radius-bottomright:          5px;";
		$f[]="  -moz-border-radius-bottomleft:      5px;";
		$f[]="  -webkit-border-radius-bottomleft:   5px;";
		$f[]="  border-radius-bottomleft:           5px;";
		$f[]="  -moz-box-shadow:    inset 0 20px 20px rgba(63,109,177,0.05);";
		$f[]="  -webkit-box-shadow: inset 0 20px 20px rgba(63,109,177,0.05);";
		$f[]="  padding: 0.7em;";
		$f[]="  margin-top: 1em;";
		$f[]="  text-align: center;";
		$f[]="  border-width: 1px 0 0 0;";
		$f[]="  border-style: solid;";
		$f[]="  border-color: #EDF2F7;";
		$f[]="}";
		$f[]="section.new-file div#upload * {";
		$f[]="}";
		$f[]="section.new-file div#upload-loading {";
		$f[]="  width: 220px; height: 19px;";
		$f[]="  background: transparent url('../images/ajax-loader.gif') no-repeat center center;";
		$f[]="  margin: 0 auto;";
		$f[]="}";
		$f[]="section.new-file ul#options {";
		$f[]="		margin-top: 0.8em;";
		$f[]="}";
		$f[]="section.new-file ul#options li + li {";
		$f[]="  padding-left: 0.4em;";
		$f[]="	margin-left: 0.4em;";
		$f[]="  border-left: 1px solid #DDD;";
		$f[]="}";
		$f[]="section.new-file div#password *,";
		$f[]="section.new-file ul#options li ,";
		$f[]="section.new-file ul#options input ,";
		$f[]="section.new-file ul#options li label {";
		$f[]="  display: inline;";
		$f[]="}";
		$f[]="";
		$f[]="h2.new-file a.awesome {";
		$f[]="  background-image: url(../images/icons/upload.png);";
		$f[]="  background-position: 10px center;";
		$f[]="  background-repeat: no-repeat;";
		$f[]="  font-size: 0.85em;";
		$f[]="	font-weight: normal;";
		$f[]="	padding-left: 40px;";
		$f[]="	float: right;";
		$f[]="	margin-bottom: .7em;";
		$f[]="	color: #EEE;";
		$f[]="}";
		$f[]="h2.new-file a.awesome:hover {";
		$f[]="	color: #FFF;";
		$f[]="}";
		$f[]=".ui-dialog section.new-file {";
		$f[]="    margin: 0;";
		$f[]="}";
		$f[]="";
		$f[]=" ";
		$f[]="/*";
		$f[]=" * form";
		$f[]=" */";
		$f[]="select ,";
		$f[]="textarea ,";
		$f[]="input {";
		$f[]="  padding: 0.4em;";
		$f[]="  border-width: 1px;";
		$f[]="  border-style: solid;";
		$f[]="  border-color: #EDEBE6;";
		$f[]="  background: #FBFAF8;";
		$f[]="  -moz-border-radius:    4px;";
		$f[]="  -webkit-border-radius: 4px;";
		$f[]="  border-radius:         4px;";
		$f[]="  border: 1px solid rgba(0,0,0,0.3);";
		$f[]="  -moz-box-shadow:    inset 0 4px 4px rgba(0,0,0,0.05);";
		$f[]="  -webkit-box-shadow: inset 0 4px 4px rgba(0,0,0,0.05);";
		$f[]="}";
		$f[]="label {";
		$f[]="  font-size: 0.8em;";
		$f[]="  color: #7C746C;";
		$f[]="}";
		$f[]="";
		$f[]="/*";
		$f[]=" * Section uploaded-files";
		$f[]=" */";
		$f[]="";
		$f[]="h2#uploaded-files-title {";
		$f[]="	padding-top: .3em;";
		$f[]="	margin-left: 10px;";
		$f[]="	font-size: 130%;";
		$f[]="}";
		$f[]="section#uploaded-files {";
		$f[]="	clear: both;";
		$f[]="	padding: 1px;";
		$f[]="	margin: 5px;";
		$f[]="}";
		$f[]="section#uploaded-files ul#files {";
		$f[]="  list-style: none;";
		$f[]="}";
		$f[]="section#uploaded-files ul li.file {";
		$f[]="  position: relative;";
		$f[]="  padding-top: 0.7em;";
		$f[]="}";
		$f[]="section#uploaded-files ul li.file.even {";
		$f[]="  background: #F5F4F2;";
		$f[]="  border-width: 1px 0 1px 0;";
		$f[]="  border-style: solid;";
		$f[]="  border-color: #EDF2F7;";
		$f[]="}";
		$f[]="section#uploaded-files ul li.file:first-child {";
		$f[]="  -moz-border-radius-topright:     5px;";
		$f[]="  -webkit-border-top-left-radius:  5px;";
		$f[]="  border-radius-topright:          5px;";
		$f[]="  -moz-border-radius-topleft:      5px;";
		$f[]="  -webkit-border-top-right-radius: 5px;";
		$f[]="  border-radius-topleft:           5px;";
		$f[]="}";
		$f[]="section#uploaded-files ul li.file:last-child {";
		$f[]="  -moz-border-radius-bottomright:     5px;";
		$f[]="  -webkit-border-bottom-left-radius:  5px;";
		$f[]="  border-radius-bottomright:          5px;";
		$f[]="  -moz-border-radius-bottomleft:      5px;";
		$f[]="  -webkit-border-bottom-left-radius:  5px;";
		$f[]="  border-radius-bottomleft:           5px;";
		$f[]="}";
		$f[]="section#uploaded-files ul li.file:hover {";
		$f[]="  background: #FFFDE9;";
		$f[]="}";
		$f[]="";
		$f[]="/*";
		$f[]=" * File description";
		$f[]=" */";
		$f[]="section#uploaded-files ul li .file-attributes ,";
		$f[]="section#uploaded-files ul li .file-description {";
		$f[]="  padding: 0 0 0.2em 64px;";
		$f[]="}";
		$f[]="section#uploaded-files ul li .file-description p {";
		$f[]="	display: inline;";
		$f[]="}";
		$f[]="section#uploaded-files ul li p.filename {";
		$f[]="}";
		$f[]="section#uploaded-files ul li p.filename a {";
		$f[]="  font-weight: bold;";
		$f[]="  font-size: 1.3em;";
		$f[]="  letter-spacing: -0.05em;";
		$f[]="}";
		$f[]="section#uploaded-files ul li img.mimetype {";
		$f[]="	position: absolute;";
		$f[]="	top:  0.9em;";
		$f[]="	left: 1.0em;";
		$f[]="}";
		$f[]="";
		$f[]="";
		$f[]="section#uploaded-files ul li p a.copy2clipboard {";
		$f[]="	opacity: 0;";
		$f[]="}";
		$f[]="section#uploaded-files ul li:hover p a.copy2clipboard {";
		$f[]="	text-indent: -5000px;";
		$f[]="	background: transparent url(../images/icons/copy.png) no-repeat center bottom; ";
		$f[]="	width: 16px;";
		$f[]="	padding: 0 5px;";
		$f[]="	display: inline-block;";
		$f[]="	opacity: 1;";
		$f[]="}";
		$f[]="section#uploaded-files ul li p.comment {";
		$f[]="	display: none;";
		$f[]="	/* TODO */";
		$f[]="}";
		$f[]="section#uploaded-files ul li .filesize {";
		$f[]="	color: #B0ABA6;";
		$f[]="  font-size: 0.82em;";
		$f[]="}";
		$f[]="section#uploaded-files ul li p.share {";
		$f[]="	padding-left: 1em;";
		$f[]="}";
		$f[]="section#uploaded-files ul li p.share a {";
		$f[]="	background-image: url(../images/icons/share-this-icon.png); ";
		$f[]="  background-position: 7px center;";
		$f[]="  background-repeat: no-repeat;";
		$f[]="	padding-left: 26px;";
		$f[]="	padding-top:    4px;";
		$f[]="	padding-bottom: 4px;";
		$f[]="	position: absolute;";
		$f[]="	top:   20px;";
		$f[]="	right: 20px;";
		$f[]="}";
		$f[]="/*";
		$f[]=" * File attributes";
		$f[]=" */";
		$f[]="section#uploaded-files ul li .file-attributes p {";
		$f[]="	display: inline;";
		$f[]="}";
		$f[]="section#uploaded-files ul li .file-attributes p + p {";
		$f[]="	border-left: 1px solid #DDD;";
		$f[]="	padding-left: 0.5em;";
		$f[]="	margin-left:  0.5em;";
		$f[]="}";
		$f[]="section#uploaded-files ul li .file-attributes {";
		$f[]="  color: #7C746C;";
		$f[]="  font-size: 0.82em;";
		$f[]="	padding-bottom: 0.7em;";
		$f[]="}";
		$f[]="section#uploaded-files ul li .file-attributes .availability b { color: #433C34; }";
		$f[]="";
		$f[]="section#uploaded-files ul li a.extend {";
		$f[]="  padding-left: 20px;";
		$f[]="  line-height: 16px;";
		$f[]="  height: 16px;";
		$f[]="  display: inline-block;";
		$f[]="  background-position: left top;";
		$f[]="  background-repeat: no-repeat;";
		$f[]="  background-color: transparent;";
		$f[]="}";
		$f[]="section#uploaded-files ul li a.extend { background-image: url(../images/icons/add.png); }";
		$f[]="section#uploaded-files ul li a.send-by-email { background-image: url(../images/icons/mail-message-new.png); }";
		$f[]="section#uploaded-files ul li a.extend { text-indent: -5000px; width: 16px; padding: 0; }";
		$f[]="section#uploaded-files ul li a.delete {";
		$f[]="	background: transparent url(../images/icons/remove.png) no-repeat left center;";
		$f[]="	text-indent: -5000px;";
		$f[]="  line-height: 16px;";
		$f[]="  height: 16px;";
		$f[]="  width: 16px;";
		$f[]="	display: inline-block;";
		$f[]="}";
		$f[]="";
		$f[]="/*";
		$f[]=" * send-email-form";
		$f[]=" */";
		$f[]="";
		$f[]=".send-email-form label {";
		$f[]="  display: block;";
		$f[]="}";
		$f[]=".send-email-form textarea ,";
		$f[]=".send-email-form input.to {";
		$f[]="  width: 97%;";
		$f[]="}";
		$f[]=".send-email-form textarea {";
		$f[]="  height: 4em;";
		$f[]="}";
		$f[]="";
		$f[]="#email-modal form p {";
		$f[]="  padding: 1em 1.5em 0 1.5em;";
		$f[]="}";
		$f[]="#email-modal form p.submit {";
		$f[]=" background: #F6F8FB;";
		$f[]="  -moz-box-shadow:    inset 0 20px 20px rgba(63,109,177,0.05);";
		$f[]="  -webkit-box-shadow: inset 0 20px 20px rgba(63,109,177,0.05);";
		$f[]="  padding: 0.7em;";
		$f[]="  margin-top: 1em;";
		$f[]="  text-align: center;";
		$f[]="  border-width: 1px 0 0 0;";
		$f[]="  border-style: solid;";
		$f[]="  border-color: #EDF2F7;";
		$f[]="}";
		$f[]="form a.open-email-client {";
		$f[]="  background: transparent url(../images/icons/internet-mail-22x22.png) no-repeat right center;";
		$f[]="  padding: 4px 28px 4px 0;";
		$f[]="  line-height: 22px;";
		$f[]="  text-align: right;";
		$f[]="  display: block;";
		$f[]="}";
		$f[]="";
		$f[]="/* Section #preview-file */";
		$f[]="h2.preview img {";
		$f[]="	vertical-align: middle;";
		$f[]="}";
		$f[]="h2.filename {";
		$f[]="  font-size: 1.3em;";
		$f[]="  font-weight: bold;";
		$f[]="  padding-top: 2em;";
		$f[]="}";
		$f[]="section#preview-file {";
		$f[]="  color: #7C746C;";
		$f[]="  -moz-box-shadow:    inset 0 20px 20px rgba(0,0,0,0.05);";
		$f[]="  -webkit-box-shadow: inset 0 20px 20px rgba(0,0,0,0.05);";
		$f[]="  margin-top: 1em;";
		$f[]="	padding: 1.1em;";
		$f[]="}";
		$f[]="section#preview-file form,";
		$f[]="section#preview-file p {";
		$f[]="  padding: 0.5em 0;";
		$f[]="}";
		$f[]="section#preview-file b {";
		$f[]="	color: #433C34;";
		$f[]="}";
		$f[]="section#preview-file #preview-image {";
		$f[]="  text-align: center;";
		$f[]="}";
		$f[]="section#preview-file #availability {";
		$f[]="	float: left;";
		$f[]="}";
		$f[]="section#preview-file #owner {";
		$f[]="	float: right;";
		$f[]="}";
		$f[]="section#preview-file #download,";
		$f[]="section#preview-file #comment {";
		$f[]="	clear: both;";
		$f[]="}";
		$f[]="section#preview-file #download {";
		$f[]="	padding: 1.2em 0;";
		$f[]="	text-align: center;";
		$f[]="}";
		$f[]="section#preview-file #download.image {";
		$f[]="	text-align: right;";
		$f[]="}";
		$f[]="";
		$f[]="/*";
		$f[]=" * Share modal";
		$f[]=" */";
		$f[]=" ";
		$f[]="#share-modal {";
		$f[]="	padding: 1em;";
		$f[]="}";
		$f[]="#share-modal #share-link a {";
		$f[]="	font-size: 1.8em;";
		$f[]="	line-height: 2em;";
		$f[]="  color: #1E52A1;";
		$f[]="}";
		$f[]="#share-modal #share-destinations {";
		$f[]="	padding-top: 1em;";
		$f[]="}";
		$f[]="#share-modal #share-destinations li {";
		$f[]="	display: block;";
		$f[]="	float: left;";
		$f[]="}";
		$f[]="#share-modal #share-destinations li a {";
		$f[]="	display: block;";
		$f[]="	background-position: center top;";
		$f[]="	background-repeat: no-repeat;";
		$f[]="	width: 80px;";
		$f[]="	padding-top : 50px;";
		$f[]="	text-align: center;";
		$f[]="	font-size: .75em;";
		$f[]="}";
		$f[]="#share-modal #share-destinations li a:hover {";
		$f[]="	background-position: center 2px;";
		$f[]="}";
		$f[]="#share-modal #share-destinations li.email		 a { background-image: url(../images/icons/share-email.png); }";
		$f[]="#share-modal #share-destinations li.facebook a { background-image: url(../images/icons/share-facebook.png); }";
		$f[]="#share-modal #share-destinations li.twitter  a { background-image: url(../images/icons/share-twitter.png); }";
		$f[]="";
		$f[]="";
		$f[]="/*";
		$f[]=" * Login form";
		$f[]=" */";
		$f[]="";
		$f[]="form#login-form {";
		$f[]="  width: 230px;";
		$f[]="  margin: 3em auto;";
		$f[]="  padding: 15px;";
		$f[]="  background: #494846;";
		$f[]="  -moz-border-radius:     7px;";
		$f[]="  -webkit-border-radius:  7px;";
		$f[]="  border-radius:          7px;";
		$f[]="}";
		$f[]="form#login-form p {";
		$f[]="    margin: 0 0 10px 0;";
		$f[]="}";
		$f[]="form#login-form label {";
		$f[]="  display: block;";
		$f[]="  color: #BBB;";
		$f[]="  text-shadow: 1px 1px 3px #222;";
		$f[]="}";
		$f[]="form#login-form input {";
		$f[]="  width: 202px;";
		$f[]="  padding: 5px;";
		$f[]="  font-size: 1.2em;";
		$f[]="}";
		$f[]="form#login-form p#submit-login {";
		$f[]="    padding: 10px 0 5px 0;";
		$f[]="    text-align: center;";
		$f[]="  font-size: 0.8em;";
		$f[]="}";
		$f[]="input.password ,";
		$f[]="input.username ,";
		$f[]="form#login-form input.username ,";
		$f[]="form#login-form input.password {";
		$f[]="    padding-left: 21px;";
		$f[]="    background-repeat: no-repeat;";
		$f[]="    background-position: 3px center;";
		$f[]="}";
		$f[]="input.password {";
		$f[]="    background-image: url(../images/icons/keys.png);";
		$f[]="}";
		$f[]="input.username {";
		$f[]="    background-image: url(../images/icons/user.png);";
		$f[]="}";
		$f[]="";
		$f[]="";
		$f[]="/*";
		$f[]=" * Footer";
		$f[]=" */";
		$f[]="footer {";
		$f[]="  text-align: center;";
		$f[]="  padding: 4em 0;";
		$f[]="}";
		$f[]="footer a {";
		$f[]="  color: #8F837A;";
		$f[]="  font-size: 0.7em;";
		$f[]="  text-decoration: underline;";
		$f[]="}";
		$f[]="footer #disk-usage {";
		$f[]="  font-size: 0.8em;";
		$f[]="}";
		$f[]="";
		$f[]="div.lim-debug ,";
		$f[]="footer div.debug {";
		$f[]="    margin: 0 0 1em 0;";
		$f[]="    background: #242423;";
		$f[]="    color: red;";
		$f[]="    text-align: left;";
		$f[]="    padding: 0.5em;";
		$f[]="    font-size: 0.9em;";
		$f[]="}";
		$f[]="div.lim-debug dl ,";
		$f[]="footer div.debug pre {";
		$f[]="    white-space: pre-wrap;";
		$f[]="    border-top: 1px solid #651918;";
		$f[]="    padding-top: 0.2em;";
		$f[]="    margin-top: 0.2em;";
		$f[]="    color: #BCBCBC;";
		$f[]="}";
		$f[]="div.lim-debug hr {display: none;}";
		$f[]="";
		$f[]="/*";
		$f[]=" * Support";
		$f[]=" */";
		$f[]="#support {";
		$f[]="  padding: 0.7em 0;";
		$f[]="}";
		$f[]="#support a {";
		$f[]="  padding: 0 .8em;";
		$f[]="}";
		$f[]="#support a + a {";
		$f[]="  border-left: 1px solid #D4D0C9;";
		$f[]="}";
		$f[]="#support a.bug ,";
		$f[]="#support a.help {";
		$f[]="  padding-left: 2.7em;";
		$f[]="  background-position: 0.8em center;";
		$f[]="  background-repeat: no-repeat;";
		$f[]="  display: inline-block;";
		$f[]="  height: 16px;";
		$f[]="}";
		$f[]="#support a.bug  {background-image: url(../images/icons/bug.png); }";
		$f[]="#support a.help {background-image: url(../images/icons/help.png);}";
		$f[]="";
		$f[]="/*";
		$f[]=" * Modal";
		$f[]=" */";
		$f[]="";
		$f[]="#modal-background {";
		$f[]="    position: fixed;";
		$f[]="    top: 0;";
		$f[]="    left: 0;";
		$f[]="    height: 200%;";
		$f[]="    width: 100%;";
		$f[]="    opacity: 0.7;";
		$f[]="    filter:alpha(opacity=70);";
		$f[]="    background-color: black;";
		$f[]="    z-index: 5000;";
		$f[]="    display: none;";
		$f[]="}";
		$f[]="";
		$f[]="/* Awesome button from http://www.zurb.com/blog_uploads/0000/0485/buttons-02.html */";
		$f[]=".awesome,";
		$f[]=".awesome:visited {";
		$f[]="  background: #222;";
		$f[]="  display: inline-block; ";
		$f[]="  padding: 5px 10px 6px; ";
		$f[]="  color: #fff; ";
		$f[]="  text-decoration: none;";
		$f[]="  -moz-border-radius: 5px; ";
		$f[]="  -webkit-border-radius: 5px;";
		$f[]="  text-shadow: -1px -1px 0px rgba(0,0,0,0.3);";
		$f[]="  border-bottom: 1px solid rgba(0,0,0,0.25);";
		$f[]="  position: relative;";
		$f[]="  cursor: pointer;";
		$f[]="  font-size: 13px;";
		$f[]="  font-weight: bold;";
		$f[]="  line-height: 1;";
		$f[]="  -moz-box-shadow:    inset 0 20px 20px rgba(255,255,255,0.3);";
		$f[]="  -webkit-box-shadow: inset 0 20px 20px rgba(255,255,255,0.3);";
		$f[]="}";
		$f[]=".awesome:hover { background-color: #111; color: #fff;text-shadow: 0 -1px 1px rgba(0,0,0,0.4);}";
		$f[]=".awesome:active { top: 1px; }";
		$f[]="";
		$f[]=".blue.awesome, .blue.awesome:visited { background-color: #1E52A1; }";
		$f[]=".blue.awesome:hover                  { background-color: #376EBF; }";
		$f[]="";
		$f[]=".green.awesome, .green.awesome:visited { background-color: #019652; }";
		$f[]=".green.awesome:hover                   { background-color: #008653; }";
		$f[]="";
		$f[]=".awesome.large {";
		$f[]="  padding: 6px 20px 6px 20px;";
		$f[]="}";
		$f[]="";
		$f[]="";
		$f[]="/* jQuery UI css extend";
		$f[]="----------------------------------*/";
		$f[]="";
		$f[]="/* Datepicker";
		$f[]="----------------------------------*/";
		$f[]=".ui-datepicker {";
		$f[]="	font-size:80%;";
		$f[]="        z-index: 10000;";
		$f[]="}";
		$f[]="";
		$f[]="/* Dialog";
		$f[]="----------------------------------*/";
		$f[]=".ui-widget-overlay {";
		$f[]="    opacity: 0.5;";
		$f[]="    filter:alpha(opacity=50);";
		$f[]="}";
		$f[]=".ui-dialog { ";
		$f[]="    position: relative; padding: 0; width: 300px; background: #FFF;";
		$f[]="  -moz-box-shadow:    0 5px 20px rgba(0,0,0,0.2);";
		$f[]="  -webkit-box-shadow: 0 5px 20px rgba(0,0,0,0.2);";
		$f[]="  border: 0;";
		$f[]="  font-family: Arial, sans-serif;";
		$f[]="  font-size: 100%;";
		$f[]="}";
		$f[]=".ui-dialog .ui-dialog-titlebar { padding: .5em .3em .5em 1em; position: relative; border: none;";
		$f[]="    -moz-border-radius-bottomleft: 0; -webkit-border-bottom-left-radius: 0;";
		$f[]="    -moz-border-radius-bottomright: 0; -webkit-border-bottom-right-radius: 0;";
		$f[]="    -moz-box-shadow:    inset 0 10px 10px rgba(255,255,255,0.06);";
		$f[]="    -webkit-box-shadow: inset 0 10px 10px rgba(255,255,255,0.06);";
		$f[]="    background: #333;";
		$f[]="    color: #999;";
		$f[]="    font-size:0.9em;";
		$f[]="    text-shadow: 0 0 4px #000;";
		$f[]="}";
		$f[]=".ui-dialog .ui-dialog-title { float: left; margin: 0; }";
		$f[]=".ui-dialog .ui-dialog-titlebar-close { position: absolute; right: .3em; top: 50%; width: 19px; margin: -10px 0 0 0; padding: 1px; height: 18px; }";
		$f[]=".ui-dialog .ui-dialog-titlebar-close span { display: block; margin: 1px; }";
		$f[]=".ui-dialog .ui-dialog-titlebar-close:hover, .ui-dialog .ui-dialog-titlebar-close:focus { padding: 0; }";
		$f[]=".ui-dialog .ui-dialog-content { border: 0; padding: 0; background: none; overflow: auto; zoom: 1; }";
		$f[]=".ui-dialog .ui-dialog-buttonpane { text-align: left; border-width: 1px 0 0 0; background-image: none; margin: .5em 0 0 0; padding: .3em 1em .5em .4em; }";
		$f[]=".ui-dialog .ui-dialog-buttonpane button { float: right; margin: .5em .4em .5em 0; cursor: pointer; padding: .2em .6em .3em .6em; line-height: 1.4em; width:auto; overflow:visible; }";
		$f[]=".ui-dialog .ui-resizable-se { width: 14px; height: 14px; right: 3px; bottom: 3px; }";
		$f[]=".ui-draggable .ui-dialog-titlebar { cursor: move; }";
		$f[]="";
		$f[]=".ui-widget input, .ui-widget select, .ui-widget textarea, .ui-widget button {";
		$f[]="  font-size: 0.8em;";
		$f[]="}";
		$f[]="";
		$f[]="/**";
		$f[]=" * qtip";
		$f[]=" */";
		$f[]="";
		$f[]=".ui-tooltip-dark .ui-tooltip-titlebar,";
		$f[]=".ui-tooltip-dark .ui-tooltip-content{";
		$f[]="	border: 0;";
		$f[]="}";
		$f[]=".ui-tooltip-dark .ui-tooltip-content{";
		$f[]="	background-color: #505050;";
		$f[]="	background-color: rgba(0,0,0,.8);";
		$f[]="}";
		$f[]="";
		$f[]="";
		$f[]="";
		$f[]="/*";
		$f[]=" * Install";
		$f[]=" */";
		$f[]="";
		$f[]="#install fieldset {";
		$f[]="    margin: 1em 0;";
		$f[]="    padding: 0 1em 1em 1em;";
		$f[]="    background: rgba(255,255,255,0.5);";
		$f[]="    border: 1px solid rgba(0,0,0,0.15);";
		$f[]="    -moz-border-radius: 5px;";
		$f[]="    -webkit-border-radius: 5px;";
		$f[]="    -moz-box-shadow:    0 3px 5px rgba(0,0,0,0.1);";
		$f[]="    -webkit-box-shadow: 0 3px 5px rgba(0,0,0,0.1);";
		$f[]="}";
		$f[]="#install fieldset legend {";
		$f[]="    padding: 0 0.3em;";
		$f[]="    font-weight: bold;";
		$f[]="}";
		$f[]="#install label ,";
		$f[]="#install input {";
		$f[]="    display: block;";
		$f[]="    width: 80%;";
		$f[]="}";
		$f[]="#install label ,";
		$f[]="#install fieldset table {";
		$f[]="    width: 100%;";
		$f[]="    box-sizing: border-box;";
		$f[]="}";
		$f[]="#install input[type='checkbox'] {";
		$f[]="    display: inline;";
		$f[]="    width: 1em;";
		$f[]="    height: 2em;";
		$f[]="}";
		$f[]="#install label {";
		$f[]="    margin-top: 1em;";
		$f[]="}";
		$f[]="#install label.error {";
		$f[]="    color: red;";
		$f[]="    margin-top: 0;";
		$f[]="}";
		$f[]="#install p.submit {";
		$f[]="    text-align: center;";
		$f[]="}";
		$f[]="#install p.submit input {";
		$f[]="    display: inline;";
		$f[]="    width: 50%;";
		$f[]="    padding: 1em;";
		$f[]="}";
		$f[]="#install .help {";
		$f[]="    margin: 1em 0.5em;";
		$f[]="    padding: 1em;";
		$f[]="    background: #F3EFCE;";
		$f[]="    border: 1px solid rgba(0,0,0,0.1);";
		$f[]="    -moz-border-radius: 5px;";
		$f[]="    -webkit-border-radius: 5px;";
		$f[]="    -moz-box-shadow:    0 1px 3px rgba(0,0,0,0.1);";
		$f[]="    -webkit-box-shadow: 0 1px 3px rgba(0,0,0,0.1);";
		$f[]="}";
		$f[]="#install .help ,";
		$f[]="#install .help li ,";
		$f[]="#install .help p {";
		$f[]="    font-size: 13px;";
		$f[]="}";
		$f[]="#install div.help ul {";
		$f[]="    padding-left: 2em;";
		$f[]="}";
		$f[]="#install h2 {";
		$f[]="    padding-top: 1em;";
		$f[]="    font-size: 95%;";
		$f[]="    font-weight: bold;";
		$f[]="}";
		$f[]="#install h3 {";
		$f[]="    padding-top: 1em;";
		$f[]="    font-size: 70%;";
		$f[]="    opacity: 0.8;";
		$f[]="    font-weight: bold;";
		$f[]="}";
		$f[]="";
		$f[]="#install .required {";
		$f[]="    color: red;";
		$f[]="    font-size: 80%;";
		$f[]="    opacity: 0.7;";
		$f[]="    padding-left: 1em;";
		$f[]="}";
		$f[]="#install .check-item .required {";
		$f[]="    font-weight: bold;";
		$f[]="    font-size: 110%;";
		$f[]="    text-transform: uppercase;";
		$f[]="}";
		$f[]="#install-notifs ,";
		$f[]="#install-errors {";
		$f[]="    -moz-border-radius:    10px;";
		$f[]="    -webkit-border-radius: 10px;";
		$f[]="    background: #D2E0CC;";
		$f[]="    border: 1px solid #BDCAB8;";
		$f[]="    padding: 0.5em 1em;";
		$f[]="    margin: 1em 0;";
		$f[]="}";
		$f[]="#install-errors {";
		$f[]="    background: #f2e1dc;";
		$f[]="    border: 1px solid #f3c5c1;";
		$f[]="}";
		$f[]="#install-notifs p ,";
		$f[]="#install-notifs ul ,";
		$f[]="#install-errors p ,";
		$f[]="#install-errors ul {";
		$f[]="    padding: 0.6em 0;";
		$f[]="}";
		$f[]="#install-notifs ul ,";
		$f[]="#install-errors ul {";
		$f[]="    padding-left: 2em;";
		$f[]="}";
		$f[]="#install-errors input {";
		$f[]="    background: none;";
		$f[]="    border: none;";
		$f[]="    display: inline;";
		$f[]="    padding:0;margin:0;";
		$f[]="    width: auto;";
		$f[]="    color: red;";
		$f[]="    text-shadow: none;";
		$f[]="}";
		$f[]="#install-notifs ul li ,";
		$f[]="#install-errors ul li {";
		$f[]="    padding: 0.4em 0;";
		$f[]="}";
		$f[]="#install .check-item {";
		$f[]="    padding: 1em 0;";
		$f[]="    border-bottom: 1px solid rgba(0,0,0,0.1);";
		$f[]="}";
		$f[]="#install .check-item p {";
		$f[]="    padding: 0.3em 0;";
		$f[]="}";
		$f[]="#install .help pre {";
		$f[]="    background: rgba(0,0,0,0.7);";
		$f[]="    color: #BBB;";
		$f[]="    padding: 0.5em;";
		$f[]="    margin: 0.3em 0;";
		$f[]="}";
		$f[]="#install .help .warning {";
		$f[]="    background: rgba(0,0,0,0.02);";
		$f[]="    color: red;";
		$f[]="    padding: 1em;";
		$f[]="    font-weight: bold;";
		$f[]="}";
		$f[]="";
		$f[]="/*";
		$f[]=" * documentation";
		$f[]=" */";
		$f[]="article.doc section {";
		$f[]="  padding: 1em;";
		$f[]="}";
		$f[]="article.doc section li ,";
		$f[]="article.doc section h1 ,";
		$f[]="article.doc section h2 ,";
		$f[]="article.doc section p {";
		$f[]="  padding: 0.5em 0;";
		$f[]="  font-size: 0.9em;";
		$f[]="}";
		$f[]="article.doc section li {";
		$f[]="  margin-left: 2em;";
		$f[]="}";
		$f[]="article.doc section h1 ,";
		$f[]="article.doc section h2 {";
		$f[]="  font-weight: bold;";
		$f[]="}";
		$f[]="article.doc section h1 {";
		$f[]="  padding-top: 1.2em;";
		$f[]="  font-size: 110%;";
		$f[]="}";
		$f[]="article.doc section h2 {";
		$f[]="  padding-top: 1em;";
		$f[]="  font-size: 82%;";
		$f[]="}";
		$f[]="article.doc .img-block img {";
		$f[]="  -moz-box-shadow:    0 3px 10px #404040;";
		$f[]="  -webkit-box-shadow: 0 3px 10px #404040;";
		$f[]="}";
		$f[]="article.doc .img-block {";
		$f[]="  text-align: center;";
		$f[]="	margin: 1em 0;";
		$f[]="}";	
		@file_put_contents("$this->www_dir/resources/css/main.css", @implode("\n", $f));
		
	}
	
	
	
}