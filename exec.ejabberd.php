<?php
	if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
	include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
	include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	include_once(dirname(__FILE__).'/ressources/class.ejabberd.inc');
	if(preg_match("#--verbose#",implode(" ",$argv))){
		echo "Running into verbose mode...\n";
		$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/framework/frame.class.inc');
	include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
	if($argv[1]=="--reconfigure"){build(true);exit();}
	if($argv[1]=="--zarafa"){ZarafaPlugin(true);exit();}
	
	
build();



function build($reconfiguremode=false){
	
	echo "Starting......: ".date("H:i:s")." ejabberd daemon creating /etc/ejabberd/ejabberd.cfg\n";
	$ejb=new ejabberd();
	$conf=$ejb->BuildMasterConf();
	@file_put_contents("/etc/ejabberd/ejabberd.cfg", $conf);
	$unix=new unix();
	$ejabberdctl=$unix->find_program("ejabberdctl");
	if(!is_file($ejabberdctl)){return;}
	ZarafaPlugin();
	if(!$reconfiguremode){
		$cmd="$ejabberdctl load_config /etc/ejabberd/ejabberd.cfg";
		if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
		shell_exec($cmd);
	}
	echo "Starting......: ".date("H:i:s")." ejabberd daemon configure success\n";
}

function ZarafaPlugin($silent=false){
	$unix=new unix();
	$stampfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($unix->file_time_min($stampfile)==0){return;}
	@unlink($stampfile);
	@file_put_contents($stampfile, time());
	
	$sock=new sockets();
	$ejabberdInsideZarafa=$sock->GET_INFO("ejabberdInsideZarafa");
	if(!is_numeric($ejabberdInsideZarafa)){$ejabberdInsideZarafa=0;}
	if($ejabberdInsideZarafa==0){
		if(!$silent){echo "Starting......: ".date("H:i:s")." ejabberd Integration in Zarafa WebAPP is not enabled\n";}
		return;
	}
	$q=new mysql();
	$sql="SELECT servername FROM freeweb WHERE groupware='WEBAPP'";
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){
		if(!$silent){echo "Starting......: ".date("H:i:s")." ejabberd Fatal: $q->mysql_error\n";}
		return;
	}
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$zarafa_urls[]="\"http://{$ligne["servername"]}\"";
		
	}
	if(count($zarafa_urls)==0){
		if(!$silent){echo "Starting......: ".date("H:i:s")." ejabberd no Zarafa WebAPP is set under FreeWebs...\n";}
		return;
	}
	

	
	$zarafa_urls_final=@implode(",", $zarafa_urls);
	
	$f[]="#!/usr/bin/php";
	$f[]="<?php";
	$f[]="include_once(dirname(__FILE__).\"/ressources/class.user.inc\");";
	$f[]="";
	$f[]="/*";
	$f[]="Patched and Based on work by";
	$f[]="Copyright (c) <2005> LISSY Alexandre, \"lissyx\" <alexandrelissy@free.fr>";
	$f[]="*/";
	$f[]="";
	$f[]="error_reporting(0);";
	$f[]="\$auth = new JabberAuth();";
	$f[]="\$auth->zarafa_urls = array($zarafa_urls_final);";
	$f[]="";
	$f[]="\$auth->play(); // We simply start process !";
	$f[]="";
	$f[]="class JabberAuth {";
	$f[]="	var \$zarafapath; ";
	$f[]="";
	$f[]="	var \$debug 		= false; 				      /* Debug mode */";
	$f[]="	var \$debugfile 	= \"/var/log/pipe-debug.log\";  /* Debug output */";
	$f[]="	var \$logging 	= false; 				      /* Do we log requests ? (syslog) */";
	$f[]="	/*";
	$f[]="	 * For both debug and logging, ejabberd have to be able to write.";
	$f[]="	 */";
	$f[]="	";
	$f[]="	var \$jabber_user;   /* This is the jabber user passed to the script. filled by \$this->command() */";
	$f[]="	var \$jabber_pass;   /* This is the jabber user password passed to the script. filled by \$this->command() */";
	$f[]="	var \$jabber_server; /* This is the jabber server passed to the script. filled by \$this->command(). Useful for VirtualHosts */";
	$f[]="	var \$data;          /* This is what SM component send to us. */";
	$f[]="	";
	$f[]="	var \$command; /* This is the command sent ... */";
	$f[]="	var \$stdin;   /* stdin file pointer */";
	$f[]="	var \$stdout;  /* stdout file pointer */";
	$f[]="";
	$f[]="	function JabberAuth()";
	$f[]="	{";
	$f[]="		@define_syslog_variables();";
	$f[]="		@openlog(\"pipe-auth\", LOG_NDELAY, LOG_SYSLOG);";
	$f[]="		";
	$f[]="		if(\$this->debug) {";
	$f[]="			@error_reporting(E_ALL);";
	$f[]="			@ini_set(\"log_errors\", \"1\");";
	$f[]="			@ini_set(\"error_log\", \$this->debugfile);";
	$f[]="		}";
	$f[]="		\$this->logg(\"Starting pipe-auth ...\"); // We notice that it's starting ...";
	$f[]="		\$this->openstd();";
	$f[]="	}";
	$f[]="	";
	$f[]="	function stop()";
	$f[]="	{";
	$f[]="		\$this->logg(\"Shutting down ...\"); // Sorry, have to go ...";
	$f[]="		closelog();";
	$f[]="		\$this->closestd(); // Simply close files";
	$f[]="		exit(0); // and exit cleanly";
	$f[]="	}";
	$f[]="	";
	$f[]="	function openstd()";
	$f[]="	{";
	$f[]="		\$this->stdout = @fopen(\"php://stdout\", \"w\"); // We open STDOUT so we can read";
	$f[]="		\$this->stdin  = @fopen(\"php://stdin\", \"r\"); // and STDIN so we can talk !";
	$f[]="	}";
	$f[]="	";
	$f[]="	function readstdin()";
	$f[]="	{";
	$f[]="		\$l      = @fgets(\$this->stdin, 3); // We take the length of string";
	$f[]="		\$length = @unpack(\"n\", \$l); // ejabberd give us something to play with ...";
	$f[]="		\$len    = \$length[\"1\"]; // and we now know how long to read.";
	$f[]="		if(\$len > 0) { // if not, we'll fill logfile ... and disk full is just funny once";
	$f[]="			\$this->logg(\"Reading \$len bytes ... \"); // We notice ...";
	$f[]="			\$data   = @fgets(\$this->stdin, \$len+1);";
	$f[]="			// \$data = iconv(\"UTF-8\", \"ISO-8859-15\", \$data); // To be tested, not sure if still needed.";
	$f[]="			\$this->data = \$data; // We set what we got.";
	$f[]="			\$this->logg(\"IN: \".\$data);";
	$f[]="		}";
	$f[]="	}";
	$f[]="	";
	$f[]="	function closestd()";
	$f[]="	{";
	$f[]="		@fclose(\$this->stdin); // We close everything ...";
	$f[]="		@fclose(\$this->stdout);";
	$f[]="	}";
	$f[]="	";
	$f[]="	function out(\$message)";
	$f[]="	{";
	$f[]="		@fwrite(\$this->stdout, \$message); // We reply ...";
	$f[]="		@fflush(\$this->stdout);";
	$f[]="		\$dump = @unpack(\"nn\", \$message);";
	$f[]="		\$dump = \$dump[\"n\"];";
	$f[]="		\$this->logg(\"OUT: \". \$dump);";
	$f[]="	}";
	$f[]="	";
	$f[]="	function play()";
	$f[]="	{";
	$f[]="		do {";
	$f[]="			\$this->readstdin(); // get data";
	$f[]="			\$length = strlen(\$this->data); // compute data length";
	$f[]="			if(\$length > 0 ) { // for debug mainly ...";
	$f[]="				\$this->logg(\"GO: \".\$this->data);";
	$f[]="				\$this->logg(\"data length is : \".\$length);";
	$f[]="			}";
	$f[]="			\$ret = \$this->command(); // play with data !";
	$f[]="			\$this->logg(\"RE: \" . \$ret); // this is what WE send.";
	$f[]="			\$this->out(\$ret); // send what we reply.";
	$f[]="			\$this->data = NULL; // more clean. ...";
	$f[]="		} while (true);";
	$f[]="	}";
	$f[]="	";
	$f[]="	function command()";
	$f[]="	{";
	$f[]="		\$data = \$this->splitcomm(); // This is an array, where each node is part of what SM sent to us :";
	$f[]="		// 0 => the command,";
	$f[]="		// and the others are arguments .. e.g. : user, server, password ...";
	$f[]="		";
	$f[]="		if(strlen(\$data[0]) > 0 ) {";
	$f[]="			\$this->logg(\"Command was : \".\$data[0]);";
	$f[]="		}";
	$f[]="		switch(\$data[0]) {";
	$f[]="			case \"isuser\": // this is the \"isuser\" command, used to check for user existance";
	$f[]="					\$this->jabber_user = \$data[1];";
	$f[]="					\$parms = \$data[1];  // only for logging purpose";
	$f[]="					\$return = \$this->checkuser();";
	$f[]="				break;";
	$f[]="				";
	$f[]="			case \"auth\": // check login, password";
	$f[]="					\$this->jabber_user = \$data[1];";
	$f[]="					\$this->jabber_pass = \$data[3];";
	$f[]="					\$parms = \$data[1].\":\".\$data[2].\":\".md5(\$data[3]); // only for logging purpose";
	$f[]="					\$return = \$this->checkpass();";
	$f[]="				break;";
	$f[]="				";
	$f[]="			case \"setpass\":";
	$f[]="					\$return = false; // We do not want jabber to be able to change password";
	$f[]="				break;";
	$f[]="				";
	$f[]="			default:";
	$f[]="					\$this->stop(); // if it's not something known, we have to leave.";
	$f[]="					// never had a problem with this using ejabberd, but might lead to problem ?";
	$f[]="				break;";
	$f[]="		}";
	$f[]="		";
	$f[]="		\$return = (\$return) ? 1 : 0;";
	$f[]="		";
	$f[]="		if(strlen(\$data[0]) > 0 && strlen(\$parms) > 0) {";
	$f[]="			\$this->logg(\"Command : \".\$data[0].\":\".\$parms.\" ==> \".\$return.\" \");";
	$f[]="		}";
	$f[]="		return @pack(\"nn\", 2, \$return);";
	$f[]="	}";
	$f[]="	";
	$f[]="	function checkpass()";
	$f[]="	{";
	$f[]="		foreach(\$this->zarafa_urls as \$url) {";
	$f[]="		    if(\$this->checkpassWA(\$url))";
	$f[]="		        return true;";
	$f[]="		}";
	$f[]="	        ";
	$f[]="        return false;";
	$f[]="    }";
	$f[]="            ";
	$f[]="    function checkpassWA(\$url)";
	$f[]="    {";
	$f[]="		\$this->logg(\"checkpassWA \" . \$this->jabber_pass);";
	$f[]="		\$pass = \$this->jabber_pass;";
	$f[]="		\$user = \$this->jabber_user;";
	$f[]="		\$users=new user(\$user);";
	$f[]="		\$this->logg(\"checkpassWA: Try LDAP...\");";	
	$f[]="		if(\$users->password==\$pass){\$this->logg(\"got true from LDAP server...\");return true;}";
	$f[]="		\$this->logg(\"checkpassWA Got failed from LDAP server\");";	
	$f[]="		\$this->logg(\"checkpassWA \" . \$url);";	
	$f[]="		";
	$f[]="		// Only accept alnum cookies";
	$f[]="		if(!preg_match(\"/[a-zA-Z0-9]+/\", \$pass)) {";
	$f[]="		    \$this->logg(\"bad pass\");";
	$f[]="			return false;";
	$f[]="        }";
	$f[]="";
	$f[]="		\$ctx = stream_context_create(array(\"http\" => ";
	$f[]="										array(\"method\" => \"GET\",";
	$f[]="											  \"header\" => \"Cookie: ZARAFA_WEBAPP=\$pass\r\n\" ) ) );";
	$f[]="";
	$f[]="		\$fp = fopen(\$url . \"/index.php?verify=\$user\", \"rt\", false, \$ctx);";
	$f[]="		\$ok = fgets(\$fp);";
	$f[]="		";
	$f[]="		\$this->logg(\"got \$ok\");";
	$f[]="		";
	$f[]="		if(\$ok === \"1\")";
	$f[]="			return true;";
	$f[]="		else{";
	$f[]="			\$this->logg(\"checkpassWA fopen::pass`\$pass`\". \$url . \"/index.php?verify=\$user\");";
	$f[]="	        \$fp = fopen(\$url . \"/index.php?verify=\$user\", \"rt\", false, \$ctx);";
	$f[]="	        \$ok = fgets(\$fp);";
	$f[]="          \$this->logg(\"got `\$ok`\");";
	$f[]=" 			if(\$ok === \"1\"){  \$this->logg(\"SUCCESS from \$url\");return true;}";
	$f[]="		}";
	$f[]="			";
	$f[]="        return false;";
	$f[]="	}";
	$f[]="	";
	$f[]="	function checkuser()";
	$f[]="	{";
	$f[]="		// I guess you should send 'false' if the user auth'd and then was deleted";
	$f[]="		return true;";
	$f[]="	}";
	$f[]="	";
	$f[]="	function splitcomm() // simply split command and arugments into an array.";
	$f[]="	{";
	$f[]="		return explode(\":\", \$this->data);";
	$f[]="	}";
	$f[]="	";
	$f[]="	function logg(\$message) // pretty simple, using syslog.";
	$f[]="	{";
	$f[]="		if(\$this->logging) {";
	$f[]="			@syslog(LOG_INFO, \$message);";
	$f[]="		}";
	$f[]="	}";
	$f[]="}";
	$f[]="";
	$f[]="?>";	
	@file_put_contents("/usr/share/artica-postfix/jabberauth.php", @implode("\n", $f));
	@chmod("/usr/share/artica-postfix/jabberauth.php",0755);
	if(!$silent){echo "Starting......: ".date("H:i:s")." ejabberd /usr/share/artica-postfix/jabberauth.php configured...\n";}
}