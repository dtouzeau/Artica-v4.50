<?php
include_once(dirname(__FILE__)."/class.cyrus.inc");
include_once(dirname(__FILE__)."/class.roundcube.inc");

class roundcube_freewebs{
	var $groupware;
	var $ou;
	var $www_dir;
	var $ServerPort;	
	var $servername;
	var $database;
	var $uid;
	var $rebuildb=false;
	var $mysql_password=null;	
	var $mysql_username=null;
	var $mysql_instance_id=0;
	var $Params=array();
	private $PrefixOutput=null;
	function __construct($servername){
		if($servername<>null){
			
			$this->servername=$servername;
			$this->PrefixOutput="Starting......: ".date("H:i:s")." [INIT]: Apache \"$servername\"";
		
		}
		$this->load();
	}
	
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
			if($this->www_dir==null){$this->www_dir="/var/www/$this->servername";}
			if(strlen($ligne["Params"])>5){$this->Params=@unserialize(base64_decode($ligne["Params"]));}
			$this->OutputEvents("mysql user=$this->mysql_username",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$this->mysql_instance_id=$ligne["mysql_instance_id"];
			$roundcube=new roundcube();
			$this->database=$roundcube->database;
			if($ligne["mysql_database"]<>null){$this->database=$ligne["mysql_database"];}else{
				$sql="UPDATE freeweb SET mysql_database='{$this->database}' WHERE servername='$this->servername'";
				$q->QUERY_SQL($sql,"artica_backup");
			}
			$this->uid=$ligne["uid"];
			
	}
	
	private function OutputEvents($text,$function=null,$file=null,$line=null){
		echo $this->PrefixOutput." [RCBE/$line] $text\n";
		writelogs("[Roundcube:$this->servername] $text",$function,$file,$line);
	}
	
	function getTables(){
		$path="$this->www_dir/SQL/mysql.initial.sql";
		if(!is_file($path)){
			$this->OutputEvents("$path no such file",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return array();
		}
		$datas=@file_get_contents($path);
		$tables=array();
		$tbl=explode("\n",$datas);
		while (list ($num, $line) = each ($tbl)){
		if(preg_match('#CREATE TABLE (.+?)\s+#i',$line,$re)){
			$re[1]=str_replace('`','',$re[1]);
			$tables[$re[1]]=$re[1];
			}
		}

		return $tables;
		
	}
	
	
	function mysqldb(){
		$tables=$this->getTables();
		$unix=new unix();
		$password=null;
		$init="$this->www_dir/SQL/mysql.initial.sql";
		$updatesrc="$this->www_dir/SQL/mysql.update.sql";
		if(count($tables)==0){return false;}
		$q=new roundcube($this->database);
		$Update=false;
		
		if($q->DATABASE_EXISTS($this->database)){
			while (list ($table, $line) = each ($tables)){
				if(!$q->TABLE_EXISTS($table, $this->database)){
					$this->OutputEvents("$this->database/$table no such table",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$Update=true;break;
				}
			}
		}else{
			$Update=true;
			$q->CREATE_DATABASE($this->database);
			$php5=$unix->LOCATE_PHP5_BIN();
			$unix->THREAD_COMMAND_SET("$php5 /usr/share/artica-postfix/exec.roundcube.php");
		}
		
		
		if(!$Update){return true;}
		$mysql=$unix->find_program("mysql");
		if($q->mysql_password<>null){$password=" --password=$q->mysql_password ";}
		$this->OutputEvents("creating tables...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		$cmd="$mysql ". $q->MYSQL_CMDLINES." --skip-column-names --database=$this->database --silent --xml <$init";
		$this->OutputEvents("$cmd",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);		
		
		if(is_file($updatesrc)){
			$cmd="$mysql ". $q->MYSQL_CMDLINES." --skip-column-names --database=$this->database --silent --xml < $updatesrc";
			$this->OutputEvents("$cmd",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			shell_exec($cmd);			
		}
		
		reset($tables);
		while (list ($table, $line) = each ($tables)){
			if(!$q->TABLE_EXISTS($table, $this->database,true)){
				$this->OutputEvents("$this->database/$table no such table",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;}
		}
		
		return true;
		
	}
	
	
	public function build(){
		if(!is_dir($this->www_dir)){@mkdir($this->www_dir,0755,true);}
		
		if(!is_dir("/usr/share/roundcube")){
			$this->OutputEvents("/usr/share/roundcube no such directory",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
		if(!is_file("$this->www_dir/program/localization/fr_FR/messages.inc")){
			$this->OutputEvents("copy sources To `$this->www_dir`...",__FUNCTION__,__FILE__,__LINE__);
			shell_exec("/bin/cp -rfP /usr/share/roundcube/* $this->www_dir/");
		}		
		
		
		if($this->mysqldb()){
			$this->OutputEvents("Verify main config file",__FUNCTION__,__FILE__,__LINE__);
			$this->build_main_inc();
			$this->OutputEvents("Verify DB config file",__FUNCTION__,__FILE__,__LINE__);
			$this->build_db_inc();
			$this->OutputEvents("Verify managesieve",__FUNCTION__,__FILE__,__LINE__);
			$this->managesieve();
		}
		
		$unix=new unix();
		$apacheusername=$unix->APACHE_SRC_ACCOUNT();
		$apachegroup=$unix->APACHE_SRC_GROUP();
		$chown=$unix->find_program("chown");	
		shell_exec("$chown -R $apacheusername:$apachegroup $this->www_dir &");		
		
	}
	
	private function fix_identities($mail_domain){
		$mail_domain=$free->Params["ROUNDCUBE"]["mail_domain"];
		$database=$this->database;
		$instance_id=$this->mysql_instance_id;
		$q=new mysql();
		if($instance_id>0){$q=new mysql_multi($instance_id);}
		$sql="SELECT identity_id,email FROM identities";
		$results = $q->QUERY_SQL($sql,$database);
		while ($ligne = mysqli_fetch_assoc($results)) {
			if(preg_match("#(.+?)@#", $ligne["email"],$re)){
				$email="{$re[1]}@$mail_domain";
				$q->QUERY_SQL("UPDATE identities SET email='$email' WHERE identity_id={$ligne["identity_id"]}",$database);
			}
		}
		
	}
	
	
	private function build_main_inc(){
		$free=new freeweb($this->servername);
		if(!isset($free->Params["ROUNDCUBE"])){$free->Params["ROUNDCUBE"]=array();}
		$users=new usersMenus();
		$SMTP_SERVER=$free->Params["ROUNDCUBE"]["SMTP_SERVER"];
		if($SMTP_SERVER==null){$SMTP_SERVER="127.0.0.1";}	
		$SMTP_SERVER_PORT=25;
		if(preg_match("#(.+?):([0-9]+)#", $SMTP_SERVER,$re)){
			$SMTP_SERVER=$re[1];
			$SMTP_SERVER_PORT=$re[2];
		}

		$imapserver_default_port=143;
		$cyrus=new cyrus_conf();
		$imapserver="127.0.0.1";
		if(isset($cyrus->main_array["CYRUS"]["ListenAddress"])){
			if($cyrus->main_array["CYRUS"]["ListenAddress"]<>null){
				$imapserver=$cyrus->main_array["CYRUS"]["ListenAddress"];
			}
		}
		
		
		
		
		if(isset($free->Params["ROUNDCUBE"]["IMAP_SERVER"])){
			$free->Params["ROUNDCUBE"]["IMAP_SERVER"]=trim($free->Params["ROUNDCUBE"]["IMAP_SERVER"]);
			if($free->Params["ROUNDCUBE"]["IMAP_SERVER"]<>null){
				$imapserver=$free->Params["ROUNDCUBE"]["IMAP_SERVER"];
				
			}
		}
		
			
		
		
		if(preg_match("#(.+?):([0-9]+)#", $imapserver,$re)){
			$imapserver=$re[1];
			$imapserver_default_port=$re[2];
		}
		$username_domain=$free->Params["ROUNDCUBE"]["username_domain"];
		$mail_domain=$free->Params["ROUNDCUBE"]["mail_domain"];
		$product_name=$free->Params["ROUNDCUBE"]["product_name"];
		if($product_name==null){$product_name="$this->servername Webmail";}
		
		$free->Params["ROUNDCUBE"]["product_name"]=$_POST["product_name"];
		$ENABLE_NAB=$free->Params["ROUNDCUBE"]["ENABLE_NAB"];
		$ENABLE_NAB_OU=$free->Params["ROUNDCUBE"]["NAB-OU"];
		$WRITE_NAB=$free->Params["ROUNDCUBE"]["WRITE_NAB"];
		if(!is_numeric($imapserver_default_port)){$imapserver_default_port=143;}
		

		
		
		$this->OutputEvents("IMAP..........: $imapserver:$imapserver_default_port",__FUNCTION__,__FILE__,__LINE__);
		$this->OutputEvents("SMTP..........: $SMTP_SERVER",__FUNCTION__,__FILE__,__LINE__);
		
		$this->OutputEvents("LOGIN domain..: $username_domain",__FUNCTION__,__FILE__,__LINE__);
		$this->OutputEvents("Default domain: $mail_domain",__FUNCTION__,__FILE__,__LINE__);
		$this->OutputEvents("product_name..: $product_name",__FUNCTION__,__FILE__,__LINE__);
		$this->OutputEvents("NAB...........: $ENABLE_NAB ( $ENABLE_NAB_OU ) writeable:$WRITE_NAB",__FUNCTION__,__FILE__,__LINE__);
		
		if(isset($_POST["product_name"])){
			$free->Params["ROUNDCUBE"]["product_name"]=$_POST["product_name"];
			$free->Params["ROUNDCUBE"]["ENABLE_NAB"]=$_POST["ENABLE_NAB"];
			$free->Params["ROUNDCUBE"]["NAB-OU"]=$_POST["NAB-OU"];	
		}	
		
		
		if($mail_domain<>null){$this->fix_identities($mail_domain);}
		
		$conf[]="<?php";
		$conf[]="";
		$conf[]="/*";
		$conf[]=" +-----------------------------------------------------------------------+";
		$conf[]=" | Main configuration file ". date("Y-m-d H:i:s")."                                        |";
		$conf[]=" |                                                                       |";
		$conf[]=" | This file is part of the Roundcube Webmail client                     |";
		$conf[]=" | Copyright (C) 2005-2010, The Roundcube Dev Team                       |";
		$conf[]=" | Licensed under the GNU GPL                                            |";
		$conf[]=" |                                                                       |";
		$conf[]=" +-----------------------------------------------------------------------+";
		$conf[]="";
		$conf[]="*/";
		$conf[]="";
		$conf[]="\$rcmail_config = array();";
		$conf[]="";
		$conf[]="// ----------------------------------";
		$conf[]="// LOGGING/DEBUGGING";
		$conf[]="// ----------------------------------";
		$conf[]="";
		$conf[]="// system error reporting: 1 = log; 2 = report (not implemented yet), 4 = show, 8 = trace";
		$conf[]="\$rcmail_config['debug_level'] = 1;";
		$conf[]="";
		$conf[]="// log driver:  'syslog' or 'file'.";
		$conf[]="\$rcmail_config['log_driver'] = 'file';";
		$conf[]="";
		$conf[]="// date format for log entries";
		$conf[]="// (read http://php.net/manual/en/function.date.php for all format characters)  ";
		$conf[]="\$rcmail_config['log_date_format'] = 'd-M-Y H:i:s O';";
		$conf[]="";
		$conf[]="// Syslog ident string to use, if using the 'syslog' log driver.";
		$conf[]="\$rcmail_config['syslog_id'] = 'roundcube-$this->servername';";
		$conf[]="";
		$conf[]="// Syslog facility to use, if using the 'syslog' log driver.";
		$conf[]="// For possible values see installer or http://php.net/manual/en/function.openlog.php";
		$conf[]="\$rcmail_config['syslog_facility'] = LOG_USER;";
		$conf[]="";
		$conf[]="// Log sent messages to <log_dir>/sendmail or to syslog";
		$conf[]="\$rcmail_config['smtp_log'] = true;";
		$conf[]="";
		$conf[]="// Log successful logins to <log_dir>/userlogins or to syslog";
		$conf[]="\$rcmail_config['log_logins'] = false;";
		$conf[]="";
		$conf[]="// Log session authentication errors to <log_dir>/session or to syslog";
		$conf[]="\$rcmail_config['log_session'] = false;";
		$conf[]="";
		$conf[]="// Log SQL queries to <log_dir>/sql or to syslog";
		$conf[]="\$rcmail_config['sql_debug'] = false;";
		$conf[]="";
		$conf[]="// Log IMAP conversation to <log_dir>/imap or to syslog";
		$conf[]="\$rcmail_config['imap_debug'] = false;";
		$conf[]="";
		$conf[]="// Log LDAP conversation to <log_dir>/ldap or to syslog";
		$conf[]="\$rcmail_config['ldap_debug'] = false;";
		$conf[]="";
		$conf[]="// Log SMTP conversation to <log_dir>/smtp or to syslog";
		$conf[]="\$rcmail_config['smtp_debug'] = false;";
		$conf[]="";
		$conf[]="// ----------------------------------";
		$conf[]="// IMAP";
		$conf[]="// ----------------------------------";
		$conf[]="";
		$conf[]="// the mail host chosen to perform the log-in";
		$conf[]="// leave blank to show a textbox at login, give a list of hosts";
		$conf[]="// to display a pulldown menu or set one host as string.";
		$conf[]="// To use SSL/TLS connection, enter hostname with prefix ssl:// or tls://";
		$conf[]="// Supported replacement variables:";
		$conf[]="// %n - http hostname (\$_SERVER['SERVER_NAME'])";
		$conf[]="// %d - domain (http hostname without the first part)";
		$conf[]="// %s - domain name after the '@' from e-mail address provided at login screen";
		$conf[]="// For example %n = mail.domain.tld, %d = domain.tld";
		$conf[]="\$rcmail_config['default_host'] = '$imapserver';";
		$conf[]="";
		$conf[]="// TCP port used for IMAP connections";
		$conf[]="\$rcmail_config['default_port'] = $imapserver_default_port;";
		$conf[]="";
		$conf[]="// IMAP AUTH type (DIGEST-MD5, CRAM-MD5, LOGIN, PLAIN or empty to use";
		$conf[]="// best server supported one)";
		$conf[]="\$rcmail_config['imap_auth_type'] = null;";
		$conf[]="";
		$conf[]="// If you know your imap's folder delimiter, you can specify it here.";
		$conf[]="// Otherwise it will be determined automatically";
		$conf[]="\$rcmail_config['imap_delimiter'] = null;";
		$conf[]="";
		$conf[]="// If IMAP server doesn't support NAMESPACE extension, but you're";
		$conf[]="// using shared folders or personal root folder is non-empty, you'll need to";
		$conf[]="// set these options. All can be strings or arrays of strings.";
		$conf[]="// Folders need to be ended with directory separator, e.g. \"INBOX.\"";
		$conf[]="// (special directory \"~\" is an exception to this rule)";
		$conf[]="// These can be used also to overwrite server's namespaces";
		$conf[]="\$rcmail_config['imap_ns_personal'] = null;";
		$conf[]="\$rcmail_config['imap_ns_other']    = null;";
		$conf[]="\$rcmail_config['imap_ns_shared']   = null;";
		$conf[]="";
		$conf[]="// By default IMAP capabilities are readed after connection to IMAP server";
		$conf[]="// In some cases, e.g. when using IMAP proxy, there's a need to refresh the list";
		$conf[]="// after login. Set to True if you've got this case.";
		$conf[]="\$rcmail_config['imap_force_caps'] = false;";
		$conf[]="";
		$conf[]="// By default list of subscribed folders is determined using LIST-EXTENDED";
		$conf[]="// extension if available. Some servers (dovecot 1.x) returns wrong results";
		$conf[]="// for shared namespaces in this case. http://trac.roundcube.net/ticket/1486225";
		$conf[]="// Enable this option to force LSUB command usage instead.";
		$conf[]="\$rcmail_config['imap_force_lsub'] = false;";
		$conf[]="";
		$conf[]="// IMAP connection timeout, in seconds. Default: 0 (no limit)";
		$conf[]="\$rcmail_config['imap_timeout'] = 0;";
		$conf[]="";
		$conf[]="// Optional IMAP authentication identifier to be used as authorization proxy";
		$conf[]="\$rcmail_config['imap_auth_cid'] = null;";
		$conf[]="";
		$conf[]="// Optional IMAP authentication password to be used for imap_auth_cid";
		$conf[]="\$rcmail_config['imap_auth_pw'] = null;";
		$conf[]="";
		$conf[]="// Type of IMAP indexes cache. Supported values: 'db', 'apc' and 'memcache'.";
		$conf[]="\$rcmail_config['imap_cache'] = null;";
		$conf[]="";
		$conf[]="// Enables messages cache. Only 'db' cache is supported.";
		$conf[]="\$rcmail_config['messages_cache'] = false;";
		$conf[]="";
		$conf[]="";
		$conf[]="// ----------------------------------";
		$conf[]="// SMTP";
		$conf[]="// ----------------------------------";
		$conf[]="";
		$conf[]="// SMTP server host (for sending mails).";
		$conf[]="// To use SSL/TLS connection, enter hostname with prefix ssl:// or tls://";
		$conf[]="// If left blank, the PHP mail() function is used";
		$conf[]="// Supported replacement variables:";
		$conf[]="// %h - user's IMAP hostname";
		$conf[]="// %n - http hostname (\$_SERVER['SERVER_NAME'])";
		$conf[]="// %d - domain (http hostname without the first part)";
		$conf[]="// %z - IMAP domain (IMAP hostname without the first part)";
		$conf[]="// For example %n = mail.domain.tld, %d = domain.tld";
		$conf[]="\$rcmail_config['smtp_server'] = '$SMTP_SERVER';";
		$conf[]="";
		$conf[]="// SMTP port (default is 25; 465 for SSL)";
		$conf[]="\$rcmail_config['smtp_port'] = $SMTP_SERVER_PORT;";
		$conf[]="";
		$conf[]="// SMTP username (if required) if you use %u as the username Roundcube";
		$conf[]="// will use the current username for login";
		$conf[]="\$rcmail_config['smtp_user'] = '';";
		$conf[]="";
		$conf[]="// SMTP password (if required) if you use %p as the password Roundcube";
		$conf[]="// will use the current user's password for login";
		$conf[]="\$rcmail_config['smtp_pass'] = '';";
		$conf[]="";
		$conf[]="// SMTP AUTH type (DIGEST-MD5, CRAM-MD5, LOGIN, PLAIN or empty to use";
		$conf[]="// best server supported one)";
		$conf[]="\$rcmail_config['smtp_auth_type'] = '';";
		$conf[]="";
		$conf[]="// Optional SMTP authentication identifier to be used as authorization proxy";
		$conf[]="\$rcmail_config['smtp_auth_cid'] = null;";
		$conf[]="";
		$conf[]="// Optional SMTP authentication password to be used for smtp_auth_cid";
		$conf[]="\$rcmail_config['smtp_auth_pw'] = null;";
		$conf[]="";
		$conf[]="// SMTP HELO host ";
		$conf[]="// Hostname to give to the remote server for SMTP 'HELO' or 'EHLO' messages ";
		$conf[]="// Leave this blank and you will get the server variable 'server_name' or ";
		$conf[]="// localhost if that isn't defined. ";
		$conf[]="\$rcmail_config['smtp_helo_host'] = '$users->hostname';";
		$conf[]="";
		$conf[]="// SMTP connection timeout, in seconds. Default: 0 (no limit)";
		$conf[]="\$rcmail_config['smtp_timeout'] = 0;";
		$conf[]="";
		$conf[]="// ----------------------------------";
		$conf[]="// SYSTEM";
		$conf[]="// ----------------------------------";
		$conf[]="";
		$conf[]="// THIS OPTION WILL ALLOW THE INSTALLER TO RUN AND CAN EXPOSE SENSITIVE CONFIG DATA.";
		$conf[]="// ONLY ENABLE IT IF YOU'RE REALLY SURE WHAT YOU'RE DOING!";
		$conf[]="\$rcmail_config['enable_installer'] = false;";
		$conf[]="";
		$conf[]="// use this folder to store log files (must be writeable for apache user)";
		$conf[]="// This is used by the 'file' log driver.";
		$conf[]="\$rcmail_config['log_dir'] = 'logs/';";
		$conf[]="";
		$conf[]="// use this folder to store temp files (must be writeable for apache user)";
		$conf[]="\$rcmail_config['temp_dir'] = 'temp/';";
		$conf[]="";
		$conf[]="// lifetime of message cache";
		$conf[]="// possible units: s, m, h, d, w";
		$conf[]="\$rcmail_config['message_cache_lifetime'] = '10d';";
		$conf[]="";
		$conf[]="// enforce connections over https";
		$conf[]="// with this option enabled, all non-secure connections will be redirected.";
		$conf[]="// set the port for the ssl connection as value of this option if it differs from the default 443";
		$conf[]="\$rcmail_config['force_https'] = false;";
		$conf[]="";
		$conf[]="// tell PHP that it should work as under secure connection";
		$conf[]="// even if it doesn't recognize it as secure (\$_SERVER['HTTPS'] is not set)";
		$conf[]="// e.g. when you're running Roundcube behind a https proxy";
		$conf[]="\$rcmail_config['use_https'] = false;";
		$conf[]="";
		$conf[]="// Allow browser-autocompletion on login form.";
		$conf[]="// 0 - disabled, 1 - username and host only, 2 - username, host, password";
		$conf[]="\$rcmail_config['login_autocomplete'] = 0;";
		$conf[]="";
		$conf[]="// If users authentication is not case sensitive this must be enabled.";
		$conf[]="// You can also use it to force conversion of logins to lower case.";
		$conf[]="// After enabling it all user records need to be updated, e.g. with query:";
		$conf[]="// UPDATE users SET username = LOWER(username);";
		$conf[]="\$rcmail_config['login_lc'] = false;";
		$conf[]="";
		$conf[]="// automatically create a new Roundcube user when log-in the first time.";
		$conf[]="// a new user will be created once the IMAP login succeeds.";
		$conf[]="// set to false if only registered users can use this service";
		$conf[]="\$rcmail_config['auto_create_user'] = true;";
		$conf[]="";
		$conf[]="// replace Roundcube logo with this image";
		$conf[]="// specify an URL relative to the document root of this Roundcube installation";
		$conf[]="\$rcmail_config['skin_logo'] = null;";
		$conf[]="";
		$conf[]="// Includes should be interpreted as PHP files";
		$conf[]="\$rcmail_config['skin_include_php'] = false;";
		$conf[]="";
		$conf[]="// Session lifetime in minutes";
		$conf[]="// must be greater than 'keep_alive'/60";
		$conf[]="\$rcmail_config['session_lifetime'] = 10;";
		$conf[]="";
		$conf[]="// session domain: .example.org";
		$conf[]="\$rcmail_config['session_domain'] = '';";
		$conf[]="";
		$conf[]="// Backend to use for session storage. Can either be 'db' (default) or 'memcache'";
		$conf[]="// If set to memcache, a list of servers need to be specified in 'memcache_hosts'";
		$conf[]="// Make sure the Memcache extension (http://pecl.php.net/package/memcache) version >= 2.0.0 is installed";
		$conf[]="\$rcmail_config['session_storage'] = 'db';";
		$conf[]="";
		$conf[]="// Use these hosts for accessing memcached";
		$conf[]="// Define any number of hosts in the form hostname:port";
		$conf[]="\$rcmail_config['memcache_hosts'] = null; // e.g. array( 'localhost:11211', '192.168.1.12:11211' );";
		$conf[]="";
		$conf[]="// check client IP in session athorization";
		$conf[]="\$rcmail_config['ip_check'] = false;";
		$conf[]="";
		$conf[]="// check referer of incoming requests";
		$conf[]="\$rcmail_config['referer_check'] = false;";
		$conf[]="";
		$conf[]="// X-Frame-Options HTTP header value sent to prevent from Clickjacking.";
		$conf[]="// Possible values: sameorigin|deny. Set to false in order to disable sending them";
		$conf[]="\$rcmail_config['x_frame_options'] = 'sameorigin';";
		$conf[]="";
		$conf[]="// this key is used to encrypt the users imap password which is stored";
		$conf[]="// in the session record (and the client cookie if remember password is enabled).";
		$conf[]="// please provide a string of exactly 24 chars.";
		$conf[]="\$rcmail_config['des_key'] = 'rcmail-!24ByteDESkey*Str';";
		$conf[]="";
		$conf[]="// Automatically add this domain to user names for login";
		$conf[]="// Only for IMAP servers that require full e-mail addresses for login";
		$conf[]="// Specify an array with 'host' => 'domain' values to support multiple hosts";
		$conf[]="// Supported replacement variables:";
		$conf[]="// %h - user's IMAP hostname";
		$conf[]="// %n - http hostname (\$_SERVER['SERVER_NAME'])";
		$conf[]="// %d - domain (http hostname without the first part)";
		$conf[]="// %z - IMAP domain (IMAP hostname without the first part)";
		$conf[]="// For example %n = mail.domain.tld, %d = domain.tld";
		$conf[]="\$rcmail_config['username_domain'] = '$username_domain';";
		$conf[]="";
		$conf[]="// This domain will be used to form e-mail addresses of new users";
		$conf[]="// Specify an array with 'host' => 'domain' values to support multiple hosts";
		$conf[]="// Supported replacement variables:";
		$conf[]="// %h - user's IMAP hostname";
		$conf[]="// %n - http hostname (\$_SERVER['SERVER_NAME'])";
		$conf[]="// %d - domain (http hostname without the first part)";
		$conf[]="// %z - IMAP domain (IMAP hostname without the first part)";
		$conf[]="// For example %n = mail.domain.tld, %d = domain.tld";
		$conf[]="\$rcmail_config['mail_domain'] = '$mail_domain';";
		$conf[]="";
		$conf[]="// Password charset.";
		$conf[]="// Use it if your authentication backend doesn't support UTF-8.";
		$conf[]="// Defaults to ISO-8859-1 for backward compatibility";
		$conf[]="\$rcmail_config['password_charset'] = 'ISO-8859-1';";
		$conf[]="";
		$conf[]="// How many seconds must pass between emails sent by a user";
		$conf[]="\$rcmail_config['sendmail_delay'] = 0;";
		$conf[]="";
		$conf[]="// Maximum number of recipients per message. Default: 0 (no limit)";
		$conf[]="\$rcmail_config['max_recipients'] = 0; ";
		$conf[]="";
		$conf[]="// Maximum allowednumber of members of an address group. Default: 0 (no limit)";
		$conf[]="// If 'max_recipients' is set this value should be less or equal";
		$conf[]="\$rcmail_config['max_group_members'] = 0; ";
		$conf[]="";
		$conf[]="// add this user-agent to message headers when sending";
		$conf[]="\$rcmail_config['useragent'] = 'Roundcube Webmail/'.RCMAIL_VERSION;";
		$conf[]="";
		$conf[]="// use this name to compose page titles";
		$conf[]="\$rcmail_config['product_name'] = '$this->servername Webmail';";
		$conf[]="";
		$conf[]="// try to load host-specific configuration";
		$conf[]="// see http://trac.roundcube.net/wiki/Howto_Config for more details";
		$conf[]="\$rcmail_config['include_host_config'] = false;";
		$conf[]="";
		$conf[]="// path to a text file which will be added to each sent message";
		$conf[]="// paths are relative to the Roundcube root folder";
		$conf[]="\$rcmail_config['generic_message_footer'] = '';";
		$conf[]="";
		$conf[]="// path to a text file which will be added to each sent HTML message";
		$conf[]="// paths are relative to the Roundcube root folder";
		$conf[]="\$rcmail_config['generic_message_footer_html'] = '';";
		$conf[]="";
		$conf[]="// add a received header to outgoing mails containing the creators IP and hostname";
		$conf[]="\$rcmail_config['http_received_header'] = false;";
		$conf[]="";
		$conf[]="// Whether or not to encrypt the IP address and the host name";
		$conf[]="// these could, in some circles, be considered as sensitive information;";
		$conf[]="// however, for the administrator, these could be invaluable help";
		$conf[]="// when tracking down issues.";
		$conf[]="\$rcmail_config['http_received_header_encrypt'] = false;";
		$conf[]="";
		$conf[]="// This string is used as a delimiter for message headers when sending";
		$conf[]="// a message via mail() function. Leave empty for auto-detection";
		$conf[]="\$rcmail_config['mail_header_delimiter'] = NULL;";
		$conf[]="";
		$conf[]="// number of chars allowed for line when wrapping text.";
		$conf[]="// text wrapping is done when composing/sending messages";
		$conf[]="\$rcmail_config['line_length'] = 72;";
		$conf[]="";
		$conf[]="// send plaintext messages as format=flowed";
		$conf[]="\$rcmail_config['send_format_flowed'] = true;";
		$conf[]="";
		$conf[]="// don't allow these settings to be overriden by the user";
		$conf[]="\$rcmail_config['dont_override'] = array();";
		$conf[]="";
		$conf[]="// Set identities access level:";
		$conf[]="// 0 - many identities with possibility to edit all params";
		$conf[]="// 1 - many identities with possibility to edit all params but not email address";
		$conf[]="// 2 - one identity with possibility to edit all params";
		$conf[]="// 3 - one identity with possibility to edit all params but not email address";
		$conf[]="\$rcmail_config['identities_level'] = 0;";
		$conf[]="";
		$conf[]="// Mimetypes supported by the browser.";
		$conf[]="// attachments of these types will open in a preview window";
		$conf[]="// either a comma-separated list or an array: 'text/plain,text/html,text/xml,image/jpeg,image/gif,image/png,application/pdf'";
		$conf[]="\$rcmail_config['client_mimetypes'] = null;  # null == default";
		$conf[]="";
		$conf[]="// mime magic database";
		$conf[]="\$rcmail_config['mime_magic'] = '/usr/share/misc/magic';";
		$conf[]="";
		$conf[]="// path to imagemagick identify binary";
		$conf[]="\$rcmail_config['im_identify_path'] = null;";
		$conf[]="";
		$conf[]="// path to imagemagick convert binary";
		$conf[]="\$rcmail_config['im_convert_path'] = null;";
		$conf[]="";
		$conf[]="// maximum size of uploaded contact photos in pixel";
		$conf[]="\$rcmail_config['contact_photo_size'] = 160;";
		$conf[]="";
		$conf[]="// Enable DNS checking for e-mail address validation";
		$conf[]="\$rcmail_config['email_dns_check'] = false;";
		$conf[]="";
		$conf[]="// ----------------------------------";
		$conf[]="// PLUGINS";
		$conf[]="// ----------------------------------";
		$conf[]="";
		
		$ARRAY_PLUGINS=array();
		if(!is_array($this->Params["ROUNDCUBE"])){$this->Params["ROUNDCUBE"]=array();}
		while (list ($key, $value) = each ($this->Params["ROUNDCUBE"])){
			if(preg_match("#plugin_(.+)#", $key,$re)){
				if($value==1){
					$ARRAY_PLUGINS[$re[1]]=true;
					$plugin[]="'{$re[1]}'";
					$this->OutputEvents("adding `{$re[1]}` plugin",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				}
			}
			
		}
		
		if(!isset($ARRAY_PLUGINS["managesieve"])){
			$this->OutputEvents("adding defaults plugins managesieve",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			if(is_dir("$this->www_dir/plugins/managesieve")){
				$this->OutputEvents("adding `managesieve` plugin",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$plugin[]="'managesieve'";
			}else{
				$this->OutputEvents("$this->www_dir/plugins/managesieve no such directory",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			}
			
			
		}
		
		
		
		$conf[]="// List of active plugins (in plugins/ directory)";
		$conf[]="\$rcmail_config['plugins'] = array(". @implode(",", $plugin).");";
		$conf[]="";
		$conf[]="// ----------------------------------";
		$conf[]="// USER INTERFACE";
		$conf[]="// ----------------------------------";
		$conf[]="";
		$conf[]="// default messages sort column. Use empty value for default server's sorting, ";
		$conf[]="// or 'arrival', 'date', 'subject', 'from', 'to', 'size', 'cc'";
		$conf[]="\$rcmail_config['message_sort_col'] = '';";
		$conf[]="";
		$conf[]="// default messages sort order";
		$conf[]="\$rcmail_config['message_sort_order'] = 'DESC';";
		$conf[]="";
		$conf[]="// These cols are shown in the message list. Available cols are:";
		$conf[]="// subject, from, to, cc, replyto, date, size, status, flag, attachment";
		$conf[]="\$rcmail_config['list_cols'] = array('subject', 'status', 'from', 'date', 'size', 'flag', 'attachment');";
		$conf[]="";
		$conf[]="// the default locale setting (leave empty for auto-detection)";
		$conf[]="// RFC1766 formatted language name like en_US, de_DE, de_CH, fr_FR, pt_BR";
		$conf[]="\$rcmail_config['language'] = null;";
		$conf[]="";
		$conf[]="// use this format for short date display (date or strftime format)";
		$conf[]="\$rcmail_config['date_short'] = 'D H:i';";
		$conf[]="";
		$conf[]="// use this format for detailed date/time formatting (date or strftime format)";
		$conf[]="\$rcmail_config['date_long'] = 'd.m.Y H:i';";
		$conf[]="";
		$conf[]="// use this format for today's date display (date or strftime format)";
		$conf[]="// Note: \$ character will be replaced with 'Today' label";
		$conf[]="\$rcmail_config['date_today'] = 'H:i';";
		$conf[]="";
		$conf[]="// use this format for date display without time (date or strftime format)";
		$conf[]="\$rcmail_config['date_format'] = 'Y-m-d';";
		$conf[]="";
		$conf[]="// store draft message is this mailbox";
		$conf[]="// leave blank if draft messages should not be stored";
		$conf[]="// NOTE: Use folder names with namespace prefix (INBOX. on Courier-IMAP)";
		$conf[]="\$rcmail_config['drafts_mbox'] = 'Drafts';";
		$conf[]="";
		$conf[]="// store spam messages in this mailbox";
		$conf[]="// NOTE: Use folder names with namespace prefix (INBOX. on Courier-IMAP)";
		$conf[]="\$rcmail_config['junk_mbox'] = 'Junk';";
		$conf[]="";
		$conf[]="// store sent message is this mailbox";
		$conf[]="// leave blank if sent messages should not be stored";
		$conf[]="// NOTE: Use folder names with namespace prefix (INBOX. on Courier-IMAP)";
		$conf[]="\$rcmail_config['sent_mbox'] = 'Sent';";
		$conf[]="";
		$conf[]="// move messages to this folder when deleting them";
		$conf[]="// leave blank if they should be deleted directly";
		$conf[]="// NOTE: Use folder names with namespace prefix (INBOX. on Courier-IMAP)";
		$conf[]="\$rcmail_config['trash_mbox'] = 'Trash';";
		$conf[]="";
		$conf[]="// display these folders separately in the mailbox list.";
		$conf[]="// these folders will also be displayed with localized names";
		$conf[]="// NOTE: Use folder names with namespace prefix (INBOX. on Courier-IMAP)";
		$conf[]="\$rcmail_config['default_imap_folders'] = array('INBOX', 'Drafts', 'Sent', 'Junk', 'Trash');";
		$conf[]="";
		$conf[]="// automatically create the above listed default folders on login";
		$conf[]="\$rcmail_config['create_default_folders'] = false;";
		$conf[]="\$rcmail_config['protect_default_folders'] = true;";
		$conf[]="\$rcmail_config['quota_zero_as_unlimited'] = false;";
		$conf[]="\$rcmail_config['enable_spellcheck'] = true;";
		$conf[]="\$rcmail_config['spellcheck_engine'] = 'googie';";
		$conf[]="\$rcmail_config['spellcheck_uri'] = '';";
		$conf[]="\$rcmail_config['max_pagesize'] = 200;";
		$conf[]="\$rcmail_config['min_keep_alive'] = 60;";
		$conf[]="\$rcmail_config['upload_progress'] = false;";
		$conf[]="\$rcmail_config['undo_timeout'] = 0;";
		$conf[]="";
		$conf[]="// ----------------------------------";
		$conf[]="// ADDRESSBOOK SETTINGS";
		$conf[]="// ----------------------------------";

		if(($ENABLE_NAB==1) && $ENABLE_NAB_OU<>null){
			$conf[]="\$rcmail_config['address_book_type'] = 'ldap';";
			$writable="false";
			if($WRITE_NAB==1){$writable="true";}
			$ldap=new clladp();
		$conf[]="\$rcmail_config['ldap_public']['$ENABLE_NAB_OU'] = array(";
		$conf[]="  'name'          => '$ENABLE_NAB_OU',";
		$conf[]="  'hosts'         => array('$ldap->ldap_host'),";
		$conf[]="  'port'          => $ldap->ldap_port,";
		$conf[]="  'use_tls'	      => false,";
		$conf[]="  'ldap_version'  => 3,";
		$conf[]="  'user_specific' => true,";
		$conf[]="  'base_dn'       => '$ldap->suffix',";
		$conf[]="  'bind_dn'       => 'cn=$ldap->ldap_admin,$ldap->suffix',";
		$conf[]="  'bind_pass'     => '$ldap->ldap_password',";
		$conf[]="  'search_base_dn' => 'ou=$ENABLE_NAB_OU,dc=organizations,$ldap->suffix',";
		$conf[]="  'search_filter'  => '(&(objectClass=posixAccount)(uid=%u))',";
		$conf[]="  'auth_cid'       => '',";
		$conf[]="  'auth_method'    => '',";
		$conf[]="  'hidden'        => false,";
		$conf[]="  'writable'       => $writable,";
		$conf[]="  'LDAP_Object_Classes' => array('top', 'inetOrgPerson'),";
		$conf[]="  'LDAP_rdn'       => 'mail',";
		$conf[]="  'required_fields' => array('cn', 'sn', 'mail'),";
		$conf[]="  'search_fields'   => array('mail', 'cn','displayName','uid','givenname'),";
		$conf[]="  'fieldmap' => array(";
		$conf[]="    'name'        => 'cn',";
		$conf[]="    'surname'     => 'sn',";
		$conf[]="    'firstname'   => 'givenName',";
		$conf[]="    'email'       => 'mail',";
		$conf[]="    'phone:home'  => 'homePhone',";
		$conf[]="    'phone:work'  => 'telephoneNumber',";
		$conf[]="    'phone:mobile' => 'mobile',";
		$conf[]="    'street'      => 'street',";
		$conf[]="    'zipcode'     => 'postalCode',";
		$conf[]="    'locality'    => 'l',";
		$conf[]="    'country'     => 'c',";
		$conf[]="    'organization' => 'o',";
		$conf[]="  ),";
		$conf[]="  'sort'          => 'cn',    // The field to sort the listing by.";
		$conf[]="  'scope'         => 'sub',   // search mode: sub|base|list";
		$conf[]="  'filter'        => '(objectClass=userAccount)',      // used for basic listing (if not empty) and will be &'d with search queries. example: status=act";
		$conf[]="  'fuzzy_search'  => true,    // server allows wildcard search";
		$conf[]="  'vlv'           => false,   // Enable Virtual List View to more efficiently fetch paginated data (if server supports it)";
		$conf[]="  'numsub_filter' => '(objectClass=organizationalUnit)',   // with VLV, we also use numSubOrdinates to query the total number of records. Set this filter to get all numSubOrdinates attributes for counting";
		$conf[]="  'sizelimit'     => '0',     // Enables you to limit the count of entries fetched. Setting this to 0 means no limit.";
		$conf[]="  'timelimit'     => '0',     // Sets the number of seconds how long is spend on the search. Setting this to 0 means no limit.";
		$conf[]="  'groups'        => array(";
		$conf[]="    'base_dn'     => '',";
		$conf[]="    'filter'      => '(objectClass=groupOfNames)',";
		$conf[]="    'object_classes' => array(\"top\", \"groupOfNames\"),";
		$conf[]="    // name of the member attribute, e.g. uniqueMember";
		$conf[]="    'member_attr'    => 'member',";
		$conf[]="  ),";
		$conf[]=");";
		$conf[]="";	
		$conf[]="\$rcmail_config['ldap_public']['private'] = array(";
    	$conf[]="\t'name'              => 'Private Addressbook',";
    	$conf[]="\t'hosts'              => array('$ldap->ldap_host'),";
   	 	$conf[]="\t'port'                => $ldap->ldap_port,";
   	 	$conf[]="\t'writable'       => true,";
    	$conf[]="\t'user_specific' => true,";
    	$conf[]="\t'base_dn'         => 'ou=%u,ou=People,dc=$ENABLE_NAB_OU,dc=NAB,$ldap->suffix',";
    	$conf[]="\t'bind_dn'          => 'cn=$ldap->ldap_admin,$ldap->suffix',";
   		$conf[]="\t'bind_pass'      => '$ldap->ldap_password',";
   		$conf[]="\t'filter'              => '(objectClass=inetOrgPerson)',";
		$conf[]="\t'LDAP_Object_Classes' => array('top', 'inetOrgPerson'),";
		$conf[]="\t'LDAP_rdn'       => 'mail',";
		$conf[]="\t'required_fields' => array('cn', 'sn', 'mail'),";
		$conf[]="\t'search_fields'   => array('mail', 'cn','displayName','uid','givenname'),";
		$conf[]="\t'fieldmap' => array(";
		$conf[]="\t\t'name'        => 'cn',";
		$conf[]="\t\t'surname'     => 'sn',";
		$conf[]="\t\t'firstname'   => 'givenName',";
		$conf[]="\t\t'email'       => 'mail',";
		$conf[]="\t\t'phone:home'  => 'homePhone',";
		$conf[]="\t\t'phone:work'  => 'telephoneNumber',";
		$conf[]="\t\t'phone:mobile' => 'mobile',";
		$conf[]="\t\t'street'      => 'street',";
		$conf[]="\t\t'zipcode'     => 'postalCode',";
		$conf[]="\t\t'locality'    => 'l',";
		$conf[]="\t\t'country'     => 'c',";
		$conf[]="\t\t'organization' => 'o',";
		$conf[]="\t),";   		
    	$conf[]="\t'groups'           => array(";
    	$conf[]="\t\t'base_dn'           => '',";
   		$conf[]="\t\t'filter'                 => '(objectClass=groupOfNames)',";
  		$conf[]="\t\t'object_classes' => array('top', 'groupOfNames'),";
 		$conf[]="\t),";
		$conf[]=");";		

		$conf[]="\$rcmail_config['autocomplete_addressbooks'] = array('sql','$ENABLE_NAB_OU','private');";
		}else{
			$conf[]="\$rcmail_config['address_book_type'] = 'sql';";
			$conf[]="\$rcmail_config['autocomplete_addressbooks'] = array('sql');";
			
		}
		$conf[]="";
		$conf[]="// The minimum number of characters required to be typed in an autocomplete field";
		$conf[]="// before address books will be searched. Most useful for LDAP directories that";
		$conf[]="// may need to do lengthy results building given overly-broad searches";
		$conf[]="\$rcmail_config['autocomplete_min_length'] = 1;";
		$conf[]="";
		$conf[]="// Number of parallel autocomplete requests.";
		$conf[]="// If there's more than one address book, n parallel (async) requests will be created,";
		$conf[]="// where each request will search in one address book. By default (0), all address";
		$conf[]="// books are searched in one request.";
		$conf[]="\$rcmail_config['autocomplete_threads'] = 0;";
		$conf[]="";
		$conf[]="// Max. numer of entries in autocomplete popup. Default: 15.";
		$conf[]="\$rcmail_config['autocomplete_max'] = 15;";
		$conf[]="";
		$conf[]="// show address fields in this order";
		$conf[]="// available placeholders: {street}, {locality}, {zipcode}, {country}, {region}";
		$conf[]="\$rcmail_config['address_template'] = '{street}<br/>{locality} {zipcode}<br/>{country} {region}';";
		$conf[]="";
		$conf[]="// ----------------------------------";
		$conf[]="// USER PREFERENCES";
		$conf[]="// ----------------------------------";
		$conf[]="";
		$conf[]="// Use this charset as fallback for message decoding";
		$conf[]="\$rcmail_config['default_charset'] = 'ISO-8859-1';";
		$conf[]="";
		$conf[]="// skin name: folder from skins/";
		$conf[]="\$rcmail_config['skin'] = 'default';";
		$conf[]="";
		$conf[]="// show up to X items in list view";
		$conf[]="\$rcmail_config['pagesize'] = 40;";
		$conf[]="";
		$conf[]="// use this timezone to display date/time";
		$conf[]="\$rcmail_config['timezone'] = 'auto';";
		$conf[]="";
		$conf[]="// is daylight saving On?";
		$conf[]="\$rcmail_config['dst_active'] = (bool)date('I');";
		$conf[]="";
		$conf[]="// prefer displaying HTML messages";
		$conf[]="\$rcmail_config['prefer_html'] = true;";
		$conf[]="";
		$conf[]="// display remote inline images";
		$conf[]="// 0 - Never, always ask";
		$conf[]="// 1 - Ask if sender is not in address book";
		$conf[]="// 2 - Always show inline images";
		$conf[]="\$rcmail_config['show_images'] = 0;";
		$conf[]="";
		$conf[]="// compose html formatted messages by default";
		$conf[]="// 0 - never, 1 - always, 2 - on reply to HTML message only ";
		$conf[]="\$rcmail_config['htmleditor'] = 0;";
		$conf[]="";
		$conf[]="// show pretty dates as standard";
		$conf[]="\$rcmail_config['prettydate'] = true;";
		$conf[]="";
		$conf[]="// save compose message every 300 seconds (5min)";
		$conf[]="\$rcmail_config['draft_autosave'] = 300;";
		$conf[]="";
		$conf[]="// default setting if preview pane is enabled";
		$conf[]="\$rcmail_config['preview_pane'] = false;";
		$conf[]="";
		$conf[]="// Mark as read when viewed in preview pane (delay in seconds)";
		$conf[]="// Set to -1 if messages in preview pane should not be marked as read";
		$conf[]="\$rcmail_config['preview_pane_mark_read'] = 0;";
		$conf[]="";
		$conf[]="// Clear Trash on logout";
		$conf[]="\$rcmail_config['logout_purge'] = false;";
		$conf[]="";
		$conf[]="// Compact INBOX on logout";
		$conf[]="\$rcmail_config['logout_expunge'] = false;";
		$conf[]="";
		$conf[]="// Display attached images below the message body ";
		$conf[]="\$rcmail_config['inline_images'] = true;";
		$conf[]="";
		$conf[]="// Encoding of long/non-ascii attachment names:";
		$conf[]="// 0 - Full RFC 2231 compatible";
		$conf[]="// 1 - RFC 2047 for 'name' and RFC 2231 for 'filename' parameter (Thunderbird's default)";
		$conf[]="// 2 - Full 2047 compatible";
		$conf[]="\$rcmail_config['mime_param_folding'] = 1;";
		$conf[]="";
		$conf[]="// Set true if deleted messages should not be displayed";
		$conf[]="// This will make the application run slower";
		$conf[]="\$rcmail_config['skip_deleted'] = false;";
		$conf[]="";
		$conf[]="// Set true to Mark deleted messages as read as well as deleted";
		$conf[]="// False means that a message's read status is not affected by marking it as deleted";
		$conf[]="\$rcmail_config['read_when_deleted'] = true;";
		$conf[]="";
		$conf[]="// Set to true to never delete messages immediately";
		$conf[]="// Use 'Purge' to remove messages marked as deleted";
		$conf[]="\$rcmail_config['flag_for_deletion'] = false;";
		$conf[]="";
		$conf[]="// Default interval for keep-alive/check-recent requests (in seconds)";
		$conf[]="// Must be greater than or equal to 'min_keep_alive' and less than 'session_lifetime'";
		$conf[]="\$rcmail_config['keep_alive'] = 60;";
		$conf[]="";
		$conf[]="// If true all folders will be checked for recent messages";
		$conf[]="\$rcmail_config['check_all_folders'] = false;";
		$conf[]="";
		$conf[]="// If true, after message delete/move, the next message will be displayed";
		$conf[]="\$rcmail_config['display_next'] = false;";
		$conf[]="";
		$conf[]="// 0 - Do not expand threads ";
		$conf[]="// 1 - Expand all threads automatically ";
		$conf[]="// 2 - Expand only threads with unread messages ";
		$conf[]="\$rcmail_config['autoexpand_threads'] = 0;";
		$conf[]="";
		$conf[]="// When replying place cursor above original message (top posting)";
		$conf[]="\$rcmail_config['top_posting'] = false;";
		$conf[]="";
		$conf[]="// When replying strip original signature from message";
		$conf[]="\$rcmail_config['strip_existing_sig'] = true;";
		$conf[]="";
		$conf[]="// Show signature:";
		$conf[]="// 0 - Never";
		$conf[]="// 1 - Always";
		$conf[]="// 2 - New messages only";
		$conf[]="// 3 - Forwards and Replies only";
		$conf[]="\$rcmail_config['show_sig'] = 1;";
		$conf[]="";
		$conf[]="// When replying or forwarding place sender's signature above existing message";
		$conf[]="\$rcmail_config['sig_above'] = false;";
		$conf[]="";
		$conf[]="// Use MIME encoding (quoted-printable) for 8bit characters in message body";
		$conf[]="\$rcmail_config['force_7bit'] = false;";
		$conf[]="";
		$conf[]="// Defaults of the search field configuration.";
		$conf[]="// The array can contain a per-folder list of header fields which should be considered when searching";
		$conf[]="// The entry with key '*' stands for all folders which do not have a specific list set.";
		$conf[]="// Please note that folder names should to be in sync with \$rcmail_config['default_imap_folders']";
		$conf[]="\$rcmail_config['search_mods'] = null;  // Example: array('*' => array('subject'=>1, 'from'=>1), 'Sent' => array('subject'=>1, 'to'=>1));";
		$conf[]="";
		$conf[]="// Defaults of the addressbook search field configuration.";
		$conf[]="\$rcmail_config['addressbook_search_mods'] = null;  // Example: array('name'=>1, 'firstname'=>1, 'surname'=>1, 'email'=>1, '*'=>1);";
		$conf[]="";
		$conf[]="// 'Delete always'";
		$conf[]="// This setting reflects if mail should be always deleted";
		$conf[]="// when moving to Trash fails. This is necessary in some setups";
		$conf[]="// when user is over quota and Trash is included in the quota.";
		$conf[]="\$rcmail_config['delete_always'] = false;";
		$conf[]="";
		$conf[]="// Behavior if a received message requests a message delivery notification (read receipt)";
		$conf[]="// 0 = ask the user, 1 = send automatically, 2 = ignore (never send or ask)";
		$conf[]="// 3 = send automatically if sender is in addressbook, otherwise ask the user";
		$conf[]="// 4 = send automatically if sender is in addressbook, otherwise ignore";
		$conf[]="\$rcmail_config['mdn_requests'] = 0;";
		$conf[]="";
		$conf[]="// Return receipt checkbox default state";
		$conf[]="\$rcmail_config['mdn_default'] = 0;";
		$conf[]="";
		$conf[]="// Delivery Status Notification checkbox default state";
		$conf[]="\$rcmail_config['dsn_default'] = 0;";
		$conf[]="";
		$conf[]="// Place replies in the folder of the message being replied to";
		$conf[]="\$rcmail_config['reply_same_folder'] = false;";
		$conf[]="";
		$conf[]="// Sets default mode of Forward feature to \"forward as attachment\"";
		$conf[]="\$rcmail_config['forward_attachment'] = false;";
		$conf[]="";
		$conf[]="// Defines address book (internal index) to which new contacts will be added";
		$conf[]="// By default it is the first writeable addressbook.";
		$conf[]="// Note: Use '0' for built-in address book.";
		$conf[]="\$rcmail_config['default_addressbook'] = null;";
		$conf[]="";
		$conf[]="// Enables spell checking before sending a message.";
		$conf[]="\$rcmail_config['spellcheck_before_send'] = false;";
		$conf[]="";
		$conf[]="// end of config file";		
		@file_put_contents($this->www_dir."/config/main.inc.php", @implode("\n", $conf));
		$this->OutputEvents("$this->www_dir/config/main.inc.php done",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	}
	
	
	private function build_db_inc(){
			//$instance_id=$this-
			$this->OutputEvents("MySQL database is: `$this->database` Server:$round->mysql_server/$round->SocketPath",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$round=new roundcube($this->database);
			$data=$round->db_inc_php(true);	
			
			@file_put_contents($this->www_dir."/config/db.inc.php",$data);
			$this->OutputEvents("$this->www_dir/config/db.inc.php done",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	}
	
	private function managesieve(){

		
		if(!is_dir("$this->www_dir/plugins/managesieve")){
			$this->OutputEvents("$this->www_dir/plugins/managesieve no such directory",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return;}
			
		$free=new freeweb($this->servername);
		if(!isset($free->Params["ROUNDCUBE"])){$free->Params["ROUNDCUBE"]=array();}	
		$sock=new sockets();
		$cyrus=new cyrus_conf();
		$SieveListenIp="127.0.0.1";
		if(isset($cyrus->main_array["CYRUS"]["ListenAddress"])){if($cyrus->main_array["CYRUS"]["ListenAddress"]<>null){$SieveListenIp=$cyrus->main_array["CYRUS"]["ListenAddress"];}}
		
		if(isset($free->Params["ROUNDCUBE"]["IMAP_SERVER"])){
			$free->Params["ROUNDCUBE"]["IMAP_SERVER"]=trim($free->Params["ROUNDCUBE"]["IMAP_SERVER"]);
			if($free->Params["ROUNDCUBE"]["IMAP_SERVER"]<>null){
				$SieveListenIp=$free->Params["ROUNDCUBE"]["IMAP_SERVER"];
				
			}
		}
		if(preg_match("#(.+?):([0-9]+)#", $SieveListenIp,$re)){
			$SieveListenIp=$re[1];
		}		
		
		if(trim($SieveListenIp)=="127.0.0.1"){
			$SieveListenIp=$sock->GET_INFO("SieveListenIp");
			if(trim($SieveListenIp)==null){$SieveListenIp="127.0.0.1";}
		}
		
		$managesieve_port=4190;
		
		$f=file("/etc/services");
		foreach ($f as $num=>$ligne){
			if(preg_match("#sieve\s+([0-9]+)\/tcp#", $ligne,$re)){
				$managesieve_port=$re[1];
				break;
			}
		}

		if(isset($free->Params["ROUNDCUBE"]["SIEVE_SERVER"])){
			$SIEVE_SERVER=null;
			$SIEVE_SERVER_PORT=0;
			if($free->Params["ROUNDCUBE"]["SIEVE_SERVER"]<>null){
				$this->OutputEvents("SIEVE_SERVER = ".$free->Params["ROUNDCUBE"]["SIEVE_SERVER"],__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$SIEVE_SERVER=$free->Params["ROUNDCUBE"]["SIEVE_SERVER"];
				if(is_numeric($SIEVE_SERVER)){
					$SIEVE_SERVER_PORT=$SIEVE_SERVER;
					$SIEVE_SERVER="127.0.0.1";
				}
				
				if(preg_match("#(.+?):([0-9]+)#", $SIEVE_SERVER,$re)){
					$SIEVE_SERVER_PORT=$re[2];
					$SIEVE_SERVER=$re[1];
				}

			}
			if($SIEVE_SERVER_PORT>0){
				$managesieve_port=$SIEVE_SERVER_PORT;
			}
			if($SIEVE_SERVER<>null){
				$SieveListenIp=$SIEVE_SERVER;
			}
			
		}
		
		
			
		
			$this->OutputEvents("Sieve: $SieveListenIp:$managesieve_port",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			
			$conf[]="<?php";
			$conf[]="";
			$conf[]="// managesieve server port";
			$conf[]="\$rcmail_config['managesieve_port'] = $managesieve_port;";
			$conf[]="";
			$conf[]="// managesieve server address, default is localhost.";
			$conf[]="// Replacement variables supported in host name:";
			$conf[]="// %h - user's IMAP hostname";
			$conf[]="// %n - http hostname (\$_SERVER['SERVER_NAME'])";
			$conf[]="// %d - domain (http hostname without the first part)";
			$conf[]="// For example %n = mail.domain.tld, %d = domain.tld";
			$conf[]="\$rcmail_config['managesieve_host'] = '$SieveListenIp';";
			$conf[]="";
			$conf[]="// authentication method. Can be CRAM-MD5, DIGEST-MD5, PLAIN, LOGIN, EXTERNAL";
			$conf[]="// or none. Optional, defaults to best method supported by server.";
			$conf[]="\$rcmail_config['managesieve_auth_type'] = null;";
			$conf[]="";
			$conf[]="// Optional managesieve authentication identifier to be used as authorization proxy.";
			$conf[]="// Authenticate as a different user but act on behalf of the logged in user.";
			$conf[]="// Works with PLAIN and DIGEST-MD5 auth.";
			$conf[]="\$rcmail_config['managesieve_auth_cid'] = null;";
			$conf[]="";
			$conf[]="// Optional managesieve authentication password to be used for imap_auth_cid";
			$conf[]="\$rcmail_config['managesieve_auth_pw'] = null;";
			$conf[]="";
			$conf[]="// use or not TLS for managesieve server connection";
			$conf[]="// it's because I've problems with TLS and dovecot's managesieve plugin";
			$conf[]="// and it's not needed on localhost";
			$conf[]="\$rcmail_config['managesieve_usetls'] = false;";
			$conf[]="";
			$conf[]="// default contents of filters script (eg. default spam filter)";
			$conf[]="\$rcmail_config['managesieve_default'] = '/etc/dovecot/sieve/global';";
			$conf[]="";
			$conf[]="// The name of the script which will be used when there's no user script";
			$conf[]="\$rcmail_config['managesieve_script_name'] = 'managesieve';";
			$conf[]="";
			$conf[]="// Sieve RFC says that we should use UTF-8 endcoding for mailbox names,";
			$conf[]="// but some implementations does not covert UTF-8 to modified UTF-7.";
			$conf[]="// Defaults to UTF7-IMAP";
			$conf[]="\$rcmail_config['managesieve_mbox_encoding'] = 'UTF-8';";
			$conf[]="";
			$conf[]="// I need this because my dovecot (with listescape plugin) uses";
			$conf[]="// ':' delimiter, but creates folders with dot delimiter";
			$conf[]="\$rcmail_config['managesieve_replace_delimiter'] = '';";
			$conf[]="";
			$conf[]="// disabled sieve extensions (body, copy, date, editheader, encoded-character,";
			$conf[]="// envelope, environment, ereject, fileinto, ihave, imap4flags, index,";
			$conf[]="// mailbox, mboxmetadata, regex, reject, relational, servermetadata,";
			$conf[]="// spamtest, spamtestplus, subaddress, vacation, variables, virustest, etc.";
			$conf[]="// Note: not all extensions are implemented";
			$conf[]="\$rcmail_config['managesieve_disabled_extensions'] = array();";
			$conf[]="";
			$conf[]="// Enables debugging of conversation with sieve server. Logs it into <log_dir>/sieve";
			$conf[]="\$rcmail_config['managesieve_debug'] = false;";
			$conf[]="";
			$conf[]="// Enables features described in http://wiki.kolab.org/KEP:14";
			$conf[]="\$rcmail_config['managesieve_kolab_master'] = false;";
			$conf[]="";
			$conf[]="// Script name extension used for scripts including. Dovecot uses '.sieve',";
			$conf[]="// Cyrus uses '.siv'. Doesn't matter if you have managesieve_kolab_master disabled.";
			$conf[]="\$rcmail_config['managesieve_filename_extension'] = '.siv';";
			$conf[]="";
			$conf[]="// List of reserved script names (without extension).";
			$conf[]="// Scripts listed here will be not presented to the user.";
			$conf[]="\$rcmail_config['managesieve_filename_exceptions'] = array();";
			$conf[]="";
			$conf[]="?>";	
			$this->OutputEvents("$this->www_dir/plugins/managesieve/config.inc.php done",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			@file_put_contents("$this->www_dir/plugins/managesieve/config.inc.php", @implode("\n", $conf));
			@chmod("$this->www_dir/plugins/managesieve/config.inc.php",0755);	
		
	}
	
	
}
