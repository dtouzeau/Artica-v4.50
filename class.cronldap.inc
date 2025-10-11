<?php
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.mail.inc');
include_once(dirname(__FILE__) . '/ressources/smtp/class.smtp.loader.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__) . '/ressources/class.system-msmtp.inc');
class cronldap{
	
	var $ldap_admin;
	var $ldap_password;
	var $suffix;
	var $ldap_host;
	var $ldap_port;
	var $cyrus_ldap_admin;
	var $cyrus_ldap_admin_password;
	var $cyrus_imapd_installed;
	var $ldap_connection;
	var $ldap_connected=false;
	
	
	public function __construct(){
			include(dirname(__FILE__) . "/ressources/settings.inc");
			$this->ldap_admin=$_GLOBAL["ldap_admin"];
			$this->ldap_password=$_GLOBAL["ldap_password"];
			$this->suffix=$_GLOBAL["ldap_root_database"];
			$this->ldap_host=$_GLOBAL["ldap_host"];
			$this->ldap_port=$_GLOBAL["ldap_port"];
			$this->cyrus_ldap_admin_password=$_GLOBAL["cyrus_ldap_admin_password"];
			$this->cyrus_imapd_installed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cyrus_imapd_installed"));
			
			if($this->ldap_host==null){$this->ldap_host="127.0.0.1";}
			if($this->ldap_port==null){$this->ldap_port="389";}
			$this->Connect();
		}

		
		function Connect(){
            $error="";
		$this->ldap_connection=ldap_connect($this->ldap_host, $this->ldap_port ) ;
		if(!$this->ldap_connection){ echo("Unable to connect to ldap server {$this->ldap_host}");exit;}

		ldap_set_option($this->ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3); // on passe le LDAP en version 3, necessaire pour travailler avec le AD
		ldap_set_option($this->ldap_connection, LDAP_OPT_REFERRALS, 0); 				
		
		$bind=ldap_bind($this->ldap_connection, "cn=$this->ldap_admin,$this->suffix", $this->ldap_password);
		if(!$bind){
			$errornumber= ldap_errno($this->ldap_connection);
				
				switch ($errornumber) {
						case 0x31:
							$error=$error . "\nBad username or password. Please try again.";
							break;
						case 0x32:
							$error=$error . "\nInsufficient access rights.";
							break;
						case 81:
							$error=$error . "\nUnable to connect to the LDAP server\n
							$this->ldap_host:$this->ldap_port\nplease,\nverify if ldap daemon is running\n or the ldap server address";
							break;						
						case -1:
							
							break;
						default:
							$error=$error . "\nCould not bind to the $ldap_host LDAP server." ."\n". ldap_err2str(ldap_errno($this->ldap_connection));
	 				}				
				
				echo "ERROR number : $errornumber\nWarning\n unable to connect with following\n cn=$this->ldap_admin,$this->suffix)\n$error\n";
				die($error);
			}
		$this->ldap_connected=true;	
		return true;
		}
		
	function ExistsDN($dn){
		$attrs=array();
		$search =@ldap_read($this->ldap_connection,$dn,'(objectClass=*)',$attrs);
		if (! $search){
			return false;}else{return true;}
	
		}

   	function ldap_add($dn,$update_array){
   		$r=@ldap_add($this->ldap_connection,$this->dn_escape($dn),$update_array);
   		if(!$r){
   			$error=ldap_errno($this->ldap_connection);
   			$msg=ldap_err2str(ldap_errno($this->ldap_connection));
   			if($error==20){return true;}
   			if($error==68){return true;}
   			echo "\n****\nError number $error\nAction:LDAP add\ndn:$dn\n$msg****\n";
   			return false;
   			}
   			
   		return true;}	
   		
	function ldap_add_mod($cn,$update_array){
		$res=@ldap_mod_add($this->ldap_connection,$this->dn_escape($cn),$update_array);
		
  		if(!$r){
			$nolog=false;
			if(ldap_errno($this->ldap_connection)=="0"){return true;}
   			if(ldap_errno($this->ldap_connection)<>0){
   				switch (ldap_errno($this->ldap_connection)) {
   					case 20:$nolog=true;break;
   					case 21:$nolog=true;return true;break;
   					case 0:$nolog=true;return true;break;
   					default:$nolog=false;break;
   				}
   			}
   			if($nolog==false){return false;}
   			
   		return true;
  		
  		}
			return true;
   		}   		

	function dn_escape($dn) {	
		$olddn = $dn;
		# Check if the RDN has a comma and escape it.
		while (preg_match('/([^\\\\]),(\s*[^=]*\s*),/',$dn))
			$dn = preg_replace('/([^\\\\]),(\s*[^=]*\s*),/','$1\\\\2C$2,',$dn);
			$dn = preg_replace('/([^\\\\]),(\s*[^=]*\s*)([^,])$/','$1\\\\2C$2$3',$dn);
		return $dn;
		}  

   	function Ldap_modify($dn,$update_array){
   		if(!is_array($update_array)){
   			//$this->ldap_last_error="No datas in this command ! on line " . __LINE__ . " in file " .__FILE__;
   			echo "No datas in this command !".__CLASS__ . "/" . __FUNCTION__.' '.__FILE__."\n";
   			return false;
   		}
   		$r=@ldap_modify($this->ldap_connection,$this->dn_escape($dn),$update_array);	
   		if(!$r){
   			
   			
   			$nolog=false;
   			if(ldap_errno($this->ldap_connection)<>0){
   				switch (ldap_errno($this->ldap_connection)) {
   					case 20:$nolog=true;break;
   					case 0:$nolog=true;break;
   					case '0':$nolog=true;break;
   					default:$nolog=false;break;
   				}
   			}
   			if($nolog==false){
                $errnum= ldap_errno($this->ldap_connection);
   				$this->ldap_last_error='Error number ' . $errnum . "\nAction:LDAP Modify\ndn:$dn\n" . ldap_err2str($errnum);
   				echo "ERROR Number $errnum Ldap_modify  \"dn=$dn\" :  " . ldap_err2str($errnum)." ".__CLASS__
   				. "/" . __FUNCTION__." ".__FILE__."\n";
                   foreach ($update_array as $num=>$ligne){
	   					if(is_array($ligne)){
	   						foreach ($ligne as $a=>$b){
	   							echo "ERROR $num($a)=$b",__CLASS__ . "/" . __FUNCTION__." ".__FILE__."\n";
	   						}
	   					}else{
	   						echo "ERROR $num=$ligne",__CLASS__ . "/" . __FUNCTION__." ".__FILE__."\n";
	   					}
	   					
	   				
	   			}
	   			return false;}
   			
   			return true;}
   		
   		return true;}		

}


class filesClasses{
	
		function FindFileInFolder($dir,$extension){
		$f=new filesClass();
		$array=$f->DirListTable($dir,true);
        foreach ($array as $num=>$val){
			if($f->Get_extension($val)==$extension){return $val;}
		}
	}
	
	function Get_extension($filename){
		   	$parts = explode('.',$filename);
   			$last = count($parts) - 1;
   			$ext = $parts[$last];
   			return $ext;
		
		
		}
		
			
	function DirListTable($dir,$onlyfiles=false){
		$Files = array();
		$It=  @opendir($dir);
		if (! $It){return null;}
		while ($Filename = readdir($It)){
			if ($Filename == '.' || $Filename == '..')
			continue;
			
			if($onlyfiles==true){
				$ext=$this->Get_extension($Filename);
				if($ext<>null)
					$Files[]=$Filename;
				}
			else{$Files[]=$Filename;}
		}

		return $Files;
	}
	
}




function write_syslogs($text,$file){
	echo date("Y-m-d h:i:s")." : $text\n";
	$file=basename($file);
	if(!function_exists('syslog')){return null;}
	openlog($file, LOG_PID | LOG_PERROR, LOG_LOCAL0);
	syslog(LOG_INFO, $text);
	closelog();	
}

function BuildPid($file){
$pid=getmypid();
$file=basename($file);
$pidfile="/etc/artica-postfix/croned.1/$file.pid";
if(file_exists($pidfile)){
	$currentpid=trim(@file_get_contents($pidfile));
	write_syslog("NewPID PID: $pid");
	write_syslog("Current PID: $currentpid");
	if($currentpid<>$pid){
		if(is_dir('/proc/'.$currentpid)){
			write_syslog("Already instance executed");
			return false;
	}else{
		write_syslog("$currentpid is not executed continue...");
		return true;
	}
		
	}
	
}	

return true;
}

?>