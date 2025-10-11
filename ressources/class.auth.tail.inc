<?php

class auth_tail{

	
	function __construct(){
		if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
		if(!isset($GLOBALS["nohup"])){$GLOBALS["nohup"]=$GLOBALS["CLASS_UNIX"]->find_program("nohup");}
		//$unix=new unix();
		
	}
	
	
	public function ParseLog($buffer){
		if($buffer==null){
			$this->events("Buffer is null");
			return false;
		}
		if(strpos($buffer,"monit[")>0){return false;}
		if(strpos($buffer,"session opened for user")>0){return true;}
		if(strpos($buffer,"session closed for user")>0){return true;}
		if(strpos($buffer,"subsystem request for sftp")>0){return true;}
		if(strpos($buffer,"error: Bind to port 22 on ")>0){return true;}
		if(strpos($buffer,"fatal: Cannot bind any address")>0){return true;}
		if(strpos($buffer,"server_exit     : master exited")>0){return true;}
		if(strpos($buffer,"PAM unable to dlopen")>0){return true;}
		if(strpos($buffer,"PAM adding faulty module")>0){return true;}
		if(strpos($buffer,"pam_unix(")>0){return true;}
		if(strpos($buffer,"slapcat:")>0){return true;}
		if(strpos($buffer,"Successful su")>0){return true;}
		
		if(preg_match("#pam_ldap:\s+ldap_simple_bind Can.*?t contact LDAP server#", $buffer)){
			$filename=md5(__LINE__);
			if($this->IfFileTime($filename,5)){
				$GLOBALS["CLASS_UNIX"]->send_email_events("Error, system cannot access to LDAP database", "pam_ldap claim $buffer\nArtica will try to reconfigure pam and restart LDAP server", "security");
				$this->WriteFileCache($filename);
				system("/usr/sbin/artica-phpfpm-service -nsswitch");
				
				return true;
			}
		}
		
		
		
		if(preg_match("#sshd.+?Accepted keyboard-interactive\/pam for\s+(.+?)\s+from\s+(.+?)\s+port#",$buffer,$re)){
			$this->events("{$re[2]} [{$re[1]}] connected");
			@mkdir("{$GLOBALS["ARTICALOGDIR"]}/sshd-success",644,true);
			$array[$re[2]]=$re[1];
			$time=time();
			@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/sshd-success/$time",serialize($array));	
			return true;		
		}
		
				
		if(preg_match("#lastlog_filetype: Couldn.+?t stat\s+(.+?): No such file or directory#",$buffer,$re)){
			$this->events("touch {$re[1]}");
			shell_exec("/bin/touch {$re[1]}");
			return true;
		}
		
		if(preg_match("#sshd.+?Accepted password for\s+(.+?)\s+from\s+(.+?)\s+#",$buffer,$re)){
			$this->events("{$re[2]} [{$re[1]}] connected");
			@mkdir("{$GLOBALS["ARTICALOGDIR"]}/sshd-success",644,true);
			$array[$re[2]]=$re[1];
			$time=time();
			@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/sshd-success/$time",serialize($array));	
			return true;		
		}
		
		
		if(preg_match("#sshd.+?Address\s+(.+?)\s+maps to (.+?),.+? POSSIBLE BREAK-IN ATTEMPT#i",$buffer,$re)){
			$this->events("POSSIBLE BREAK-IN ATTEMPT {$re[2]} [{$re[1]}] ");
			$filename=md5($re[1]);
			if($this->IfFileTime($filename,5)){
				@mkdir("/etc/artica-postfix/sshd-fw",644,true);
				//$this->Addrule($re[1],$re[2]);
				$this->WriteFileCache($filename);
				return true;
			}
		}
		
		if(preg_match("#sshd.+?Invalid user\s+(.+?)\s+from\s+(.+?)$#",$buffer,$re)){
			$this->events("invalid user: {$re[2]} [{$re[1]}] ");
			@mkdir("{$GLOBALS["ARTICALOGDIR"]}/sshd-failed",644,true);
			$array[$re[2]]=$re[1];
			$time=time();
			@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/sshd-failed/$time",serialize($array));
			return true;
		}
		
		if(preg_match("#sshd\[.+?Failed none for invalid user\s+(.*?)\s+from\s+(.*?)\s+port.*?ssh2#",$buffer,$re)){
			$this->events("invalid user: {$re[2]} [{$re[1]}] ");

			@mkdir("{$GLOBALS["ARTICALOGDIR"]}/sshd-failed",644,true);
			$array[$re[2]]=$re[1];
			$time=time();
			@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/sshd-failed/$time",serialize($array));
			return true;			
		}
		
		if(preg_match("#sshd.+?error: PAM: Authentication failure for\s+(.+?)\s+from\s+(.+)#",$buffer,$re)){
			$this->events("Authentication failure: {$re[2]} [{$re[1]}] ");

			@mkdir("{$GLOBALS["ARTICALOGDIR"]}/sshd-failed",644,true);
			$array[$re[2]]=$re[1];
			$time=time();
			@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/sshd-failed/$time",serialize($array));
			return true;
		}
		
		if(preg_match("#sshd.+?Accepted password for (.+?)\s+from\s+(.+?)\s+port#i",$buffer,$re)){
			$this->events("{$re[2]} [{$re[1]}] connected");
			@mkdir("{$GLOBALS["ARTICALOGDIR"]}/sshd-success",644,true);
			$array[$re[2]]=$re[1];
			$time=time();
			@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/sshd-success/$time",serialize($array));
		}
		
		if(preg_match("#Failed password for invalid user\s+(.+?)\s+from\s+(.+?)\s+#",$buffer,$re)){

			@mkdir("{$GLOBALS["ARTICALOGDIR"]}/sshd-failed",644,true);
			$array[$re[2]]=$re[1];
			$time=time();
			@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/sshd-failed/$time",serialize($array));
			return true;
			
		}
		
		if(preg_match("#Failed password for\s+(.+?)\s+from\s+(.+?)\s+#",$buffer,$re)){

			@mkdir("{$GLOBALS["ARTICALOGDIR"]}/sshd-failed",644,true);
			$array[$re[2]]=$re[1];
			$time=time();
			@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/sshd-failed/$time",serialize($array));
			return true;
		}
		
		if(preg_match("#pam_ldap: could not open secret file (.+?)\s+\(No such file or directory#",$buffer,$re)){
			include_once(dirname(__FILE__)."/class.ldap.inc");
			$ldap=new clladp();
			@file_put_contents("{$re[1]}",$ldap->ldap_password);
			return true;
		}
		
		if(preg_match("#sshd\[[0-9]+]\]:#",$buffer)){$this->events("Not Filtered : $buffer");return true;}
		
		return false;
	}
	
	private function Addrule($ip,$hostname){
		if($hostname==null){$hostname=gethostbyaddr($ip);}
		@mkdir("/etc/artica-postfix/sshd-fw",644,true);
		$array[$ip]=$hostname;
		$time=time();
		$this->events("Put $hostname [$ip] into firewall rule");
		@file_put_contents("/etc/artica-postfix/sshd-fw/$time",serialize($array));
	}

	private function events($text){
		$pid=@getmypid();
		$filename=basename(__FILE__);
		$date=@date("H:i:s");
		$logFile="{$GLOBALS["ARTICALOGDIR"]}/auth-tail.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$pid ".basename(__FILE__)." $text\n");
		@fclose($f);	
		$logFile="{$GLOBALS["ARTICALOGDIR"]}/syslogger.debug";
		if(!isset($GLOBALS["CLASS_UNIX"])){
			include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
			$GLOBALS["CLASS_UNIX"]=new unix();
		}
		$GLOBALS["CLASS_UNIX"]->events("$filename $text",$logFile);
		}
		
	private function WriteFileCache($filename){
		@unlink("/etc/artica-postfix/croned.1/sshd.$filename");
		@file_put_contents("/etc/artica-postfix/croned.1/sshd.$filename","#");	
	}	

	function IfFileTime($file,$min=10){
		$time=file_time_min("/etc/artica-postfix/croned.1/sshd.$file");
		$this->events("$file = {$time}Mn Max:$min");
		if($time>$min){return true;}
		return false;
	}	

}
?>