<?php
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__).'/class.tcpip.inc');
class openssh{
	
	public $AllowGroups=array();
	var $fullconf;
	var $main_array=array();
	var $ListenAddress=array();
	var $HostKey=array();
	
	function __construct(){
		$OpenSSHDArray=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenSSHDArray")));
		$LOAD=false;
		if(!is_array($OpenSSHDArray)){$LOAD=true;}
		if(!isset($OpenSSHDArray["PermitRootLogin"])){$LOAD=true;}
		if($LOAD){
			$this->fullconf=@file_get_contents("/etc/ssh/sshd_config");
			$this->parseconf();
		}else{
			$this->main_array=$OpenSSHDArray;
		}
        $this->AllowGroups=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHAllowGroups"));
        if(!is_array($this->AllowGroups)){$this->AllowGroups=array();}
		$this->defaults();
	}
	
	private function parseconf(){
		$tb=explode("\n",$this->fullconf);
		if($GLOBALS["VERBOSE"]){writelogs("config=".count($tb)." rows" ,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		foreach ($tb as $line){
			if(trim($line)==null){continue;}
			if(preg_match("#^ListenAddress\s+(.+)#",$line,$re)){continue;}
			if(preg_match("#^HostKey\s+(.+)#",$line,$re)){$this->HostKey[]=$re[1];continue;}			
			
			if(preg_match("#^([A-Za-z0-9]+)\s+(.+)#",$line,$re)){		
				if($re[1]=="Banner"){$re[2]=1;}					
				$this->main_array[$re[1]]=trim($re[2]);
			}else{
				if($GLOBALS["VERBOSE"]){writelogs("$line -> NO MATCH" ,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			}
			
		}

        $this->main_array["PermitEmptyPasswords"]="no";
		$SSHDListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDListenPort"));
        if($SSHDListenPort>10){
            $this->main_array["Port"]=$SSHDListenPort;
        }
		
		
	}


	public function sshd_authorizedkeys():int{

        $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
        $ssh_rsa=array();
        $results=$q->QUERY_SQL("SELECT privatekey FROM sshd_privkeys WHERE enabled=1");
        if(!$q->ok){
            echo $q->mysql_error."\n";
        }
        $c=0;
        foreach ($results as $index=>$ligne){
            $ligne["privatekey"]=str_replace('\\n',"\n",$ligne["privatekey"]);
            if($GLOBALS["VERBOSE"]){echo "{$ligne["privatekey"]}\n";}
            if(strlen($ligne["privatekey"])<20){continue;}
            $ssh_rsa[]=trim($ligne["privatekey"]);
            $c++;
        }
        $results=$q->QUERY_SQL("SELECT zkey FROM sshd_authorizedkeys");
        if(!$q->ok){
            echo $q->mysql_error."\n";
        }
        foreach ($results as $index=>$ligne){
            $zkey=trim($ligne["zkey"]);
            if($GLOBALS["VERBOSE"]){echo "{$ligne["zkey"]}\n";}
            if(strlen($zkey)<10){continue;}
            $c++;
            $zkey=str_replace('\\n',"\n",$zkey);
            $ssh_rsa[]=$zkey;
        }
        if($GLOBALS["VERBOSE"]){echo "Countof=".count($ssh_rsa)."\n";}
        $md51=md5_file("/etc/ssh/authorized_keys");
        @file_put_contents("/etc/ssh/authorized_keys", @implode("\n", $ssh_rsa));
        $md52=md5_file("/etc/ssh/authorized_keys");
        if($md51==$md52){
            $this->AllowGroupsUsersTiny();
            return $c;
        }
        if($this->AllowGroupsUsersTiny()) {
            $this->openssh_reload();
        }
        return $c;
    }
    private function AllowUsersCompile():string{
        $tmparray["root"]=1;
        $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
        $results=$q->QUERY_SQL("SELECT username FROM sshd_privkeys WHERE `enabled`=1");
        foreach ($results as $index=>$ligne){
            $pattern=$ligne["username"];
            $tmparray[$pattern]=1;
        }


        $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
        if($q->COUNT_ROWS("sshd_allowusers")){
            $results=$q->QUERY_SQL("SELECT pattern FROM sshd_allowusers");
            foreach ($results as $index=>$ligne){
                $pattern=$ligne["pattern"];
                $tmparray[$pattern]=1;

            }
        }

        foreach ($tmparray as $user=>$none) {
            $sshd_allowusers[] = $user;
        }

        return @implode(" ",$sshd_allowusers);
    }
    private function AllowGroupsCompile():string{
        $SSHAllowGroups=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHAllowGroups");
        if($GLOBALS["VERBOSE"]){
            echo "SSHAllowGroups = $SSHAllowGroups\n";
        }
        $AllowGroups=unserialize($SSHAllowGroups);

        if(!is_array($AllowGroups)){
            $AllowGroups=array();
        }

        $ALR["root"]=1;
        $zAllowGroups=array();

        foreach ($AllowGroups as $gpname){
            if($GLOBALS["VERBOSE"]){echo "SSHAllowGroups ADDEd => $gpname\n";}
            $ALR[$gpname]=1;
        }
        foreach ($ALR as $gpname=>$none){
            $zAllowGroups[]=$gpname;
        }
        return trim(@implode(" ",$zAllowGroups));
    }
    private function _out($text):bool{
        if(!function_exists("openlog")){return true;}
        openlog("sshd", LOG_PID , LOG_SYSLOG);
        syslog(LOG_INFO, $text);
        closelog();
        return true;
    }
    public function AllowGroupsUsersTiny():bool{
        $DisableSSHConfig       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableSSHConfig"));
        if($DisableSSHConfig==1){
            return true;
        }
        $tfile="/etc/ssh/sshd_config";
        $AllowGroups=$this->AllowGroupsCompile();
        $AllowUsers=$this->AllowUsersCompile();

        $md51=md5_file($tfile);
        $f=explode("\n",@file_get_contents($tfile));
        $FOUNDGP=false;
        foreach ($f as $index=>$line){
            if($GLOBALS["VERBOSE"]){echo "$line\n";}
            if(preg_match("#^AllowGroups#",$line)){
                $FOUNDGP=true;
                if($AllowGroups==null){
                    unset($f[$index]);
                    continue;
                }

                $f[$index]="AllowGroups\t$AllowGroups";
                continue;
            }
            if(preg_match("#^AllowUsers#",$line)){

                $f[$index]="AllowUsers\t$AllowUsers";
            }
        }
        if(!$FOUNDGP){
            if($AllowGroups<>null){
                $f[]="AllowGroups\t$AllowGroups";
            }
        }


        @file_put_contents($tfile,@implode("\n",$f));
        $md52=md5_file($tfile);
        if($md51==$md52){return false;}
        return $this->openssh_reload();

    }
    public function openssh_reload():bool{
        $unix=new unix();
        $pid=$this->openssh_pid();
        if($unix->process_exists($pid)){
            $this->_out("Reloading OpenSSH service PID $pid");
            unix_system_HUP($pid);}
        return true;
    }
    private function openssh_pid():int{
        $unix=new unix();
        $sshd=$unix->find_program("sshd");
        $pidfile="/var/run/sshd.pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid)){return $pid;}
        return $unix->PIDOF($sshd);
    }


	private function defaults(){
        if(!isset($this->main_array["X11Forwarding"])){$this->main_array["X11Forwarding"]="no";}
        if(!isset($this->main_array["IgnoreRhosts"])){$this->main_array["IgnoreRhosts"]="yes";}
        if(!isset($this->main_array["HostbasedAuthentication"])){$this->main_array["HostbasedAuthentication"]="no";}

        if(!isset($this->main_array["KexAlgorithms"])){
            {$this->main_array["KexAlgorithms"]="ecdh-sha2-nistp256,ecdh-sha2-nistp384,ecdh-sha2-nistp521,diffie-hellman-group14-sha1,diffie-hellman-group-exchange-sha256";}
        }
        if(!isset($this->main_array["ClientAliveInterval"])){
            $this->main_array["ClientAliveInterval"]=15;
        }
        if(!isset($this->main_array["ClientAliveCountMax"])){
            $this->main_array["ClientAliveCountMax"]=3;
        }
        if(!isset($this->main_array["AllowTcpForwarding"])){
            $this->main_array["AllowTcpForwarding"]="no";
        }
        if(!isset($this->main_array["MaxStartups"])){
            $this->main_array["MaxStartups"]="10:30:60";
        }
        if(!isset($this->main_array["PermitTTY"])){
            $this->main_array["PermitTTY"]="yes";
        }
        if(!isset($this->main_array["PermitRootLogin"])){$this->main_array["PermitRootLogin"]="yes";}
        if(!isset($this->main_array["PermitTunnel"])){$this->main_array["PermitTunnel"]="no";}
        if(!isset($this->main_array["Port"])){$this->main_array["Port"]="22";}
        if(!isset($this->main_array["UseDNS"])){$this->main_array["UseDNS"]="no";}
        if(!isset($this->main_array["PermitEmptyPasswords"])){$this->main_array["PermitEmptyPasswords"]="no";}
        if(!isset($this->main_array["MaxSessions"])){$this->main_array["MaxSessions"]="10";}
        if(!isset($this->main_array["MaxAuthTries"])){$this->main_array["MaxAuthTries"]="6";}
        if(!isset($this->main_array["PermitTTY"])){$this->main_array["PermitTTY"]="yes";}
        if(!isset($this->main_array["StrictModes"])){$this->main_array["StrictModes"]="yes";}
        if(!isset($this->main_array["SyslogFacility"])){$this->main_array["SyslogFacility"]="AUTH";}
        if(!isset($this->main_array["LogLevel"])){$this->main_array["LogLevel"]="INFO";}
        if(!isset($this->main_array["StrictModes"])){$this->main_array["StrictModes"]="yes";}
        if(!isset($this->main_array["PubkeyAuthentication"])){$this->main_array["PubkeyAuthentication"]="yes";}
        if(!isset($this->main_array["IgnoreRhosts"])){$this->main_array["IgnoreRhosts"]="yes";}
        if(!isset($this->main_array["HostbasedAuthentication"])){$this->main_array["HostbasedAuthentication"]="no";}
        if(!isset($this->main_array["ChallengeResponseAuthentication"])){$this->main_array["ChallengeResponseAuthentication"]="no";}
        if(!isset($this->main_array["PermitEmptyPasswords"])){$this->main_array["PermitEmptyPasswords"]="no";}
        if(!isset($this->main_array["X11Forwarding"])){$this->main_array["X11Forwarding"]="no";}
        if(!isset($this->main_array["X11DisplayOffset"])){$this->main_array["X11DisplayOffset"]="10";}
        if(!isset($this->main_array["PrintMotd"])){$this->main_array["PrintMotd"]="no";}
        if(!isset($this->main_array["PrintLastLog"])){$this->main_array["PrintLastLog"]="yes";}
        if(!isset($this->main_array["TCPKeepAlive"])){$this->main_array["TCPKeepAlive"]="yes";}
        if(!isset($this->main_array["AcceptEnv"])){$this->main_array["AcceptEnv"]="LANG LC_*";}
        if(!isset($this->main_array["Subsystem"])){$this->main_array["Subsystem"]="sftp /usr/lib/openssh/sftp-server";}
        if(!isset($this->main_array["LoginGraceTime"])){$this->main_array["LoginGraceTime"]=0;}

        if($this->main_array["LoginGraceTime"]==null){$this->main_array["LoginGraceTime"]=0;}
		if($this->main_array["PermitRootLogin"]==null){$this->main_array["PermitRootLogin"]="yes";}
		if($this->main_array["PermitTunnel"]==null){$this->main_array["PermitTunnel"]="no";}
		if($this->main_array["Port"]==null){$this->main_array["Port"]="22";}
		if($this->main_array["UseDNS"]==null){$this->main_array["UseDNS"]="no";}
		if($this->main_array["PermitEmptyPasswords"]==null){$this->main_array["PermitEmptyPasswords"]="no";}
		if($this->main_array["MaxSessions"]==null){$this->main_array["MaxSessions"]="10";}
		if($this->main_array["MaxAuthTries"]==null){$this->main_array["MaxAuthTries"]="6";}

        if($this->main_array["PermitTTY"]==null){$this->main_array["PermitTTY"]="yes";}

        if(!isset($this->main_array["StrictModes"])){$this->main_array["StrictModes"]="yes";}
	
		if($this->main_array["SyslogFacility"]==null){$this->main_array["SyslogFacility"]="AUTH";}
		if($this->main_array["LogLevel"]==null){$this->main_array["LogLevel"]="INFO";}
		if($this->main_array["StrictModes"]==null){$this->main_array["StrictModes"]="yes";}
		if($this->main_array["PubkeyAuthentication"]==null){$this->main_array["PubkeyAuthentication"]="yes";}
		if($this->main_array["IgnoreRhosts"]==null){$this->main_array["IgnoreRhosts"]="yes";}
		if($this->main_array["HostbasedAuthentication"]==null){$this->main_array["HostbasedAuthentication"]="no";}
		if($this->main_array["ChallengeResponseAuthentication"]==null){$this->main_array["ChallengeResponseAuthentication"]="no";}
		if($this->main_array["PermitEmptyPasswords"]==null){$this->main_array["PermitEmptyPasswords"]="no";}
		if($this->main_array["X11Forwarding"]==null){$this->main_array["X11Forwarding"]="no";}
		if($this->main_array["X11DisplayOffset"]==null){$this->main_array["X11DisplayOffset"]="10";}
		if($this->main_array["PrintMotd"]==null){$this->main_array["PrintMotd"]="no";}
		if($this->main_array["PrintLastLog"]==null){$this->main_array["PrintLastLog"]="yes";}
		if($this->main_array["TCPKeepAlive"]==null){$this->main_array["TCPKeepAlive"]="yes";}
		if($this->main_array["AcceptEnv"]==null){$this->main_array["AcceptEnv"]="LANG LC_*";}
		if($this->main_array["Subsystem"]==null){$this->main_array["Subsystem"]="sftp /usr/lib/openssh/sftp-server";}

        if(!isset($this->main_array["PasswordAuthentication"])){$this->main_array["PasswordAuthentication"]="yes";}
		if(!isset($this->main_array["AuthorizedKeysFile"])){$this->main_array["AuthorizedKeysFile"]="%h/.ssh/authorized_keys";}
        if($this->main_array["AuthorizedKeysFile"]==null){$this->main_array["AuthorizedKeysFile"]="%h/.ssh/authorized_keys";}
		if($this->main_array["PasswordAuthentication"]==null){$this->main_array["PasswordAuthentication"]="yes";}
		if($this->main_array["LogLevel"]==null){$this->main_array["LogLevel"]="INFO";}
		
		
		unset($this->main_array["AllowGroups"]);
		unset($this->main_array["KeyRegenerationInterval"]);
		unset($this->main_array["KeyRegenerationInterval"]);
		unset($this->main_array["ServerKeyBits"]);
		unset($this->main_array["RSAAuthentication"]);
		unset($this->main_array["RhostsRSAAuthentication"]);
        unset($this->main_array["UsePrivilegeSeparation"]);
        unset($this->main_array["Ciphers"]);
        unset($this->main_array["SSHDCiphers"]);
        unset($this->main_array["SSHDCiphersEnable"]);
        unset($this->main_array["MACs"]);
        unset($this->main_array["Protocol"]);



		$this->main_array["AuthorizedKeysFile"]="/etc/ssh/authorized_keys";

		
		
		
		
	}
	
	function BooleanKeys(){
        $f["AllowTcpForwarding"]=true;
		$f["IgnoreRhosts"]=true;
        $f["X11Forwarding"]=true;
        $f["ChallengeResponseAuthentication"]=true;
		$f["HostbasedAuthentication"]=true;
		$f["PasswordAuthentication"]=true;
		$f["PubkeyAuthentication"]=true;
		$f["StrictModes"]=true;
		$f["PermitRootLogin"]=true;
		$f["PermitEmptyPasswords"]=true;
		$f["PermitTunnel"]=true;
		$f["TCPKeepAlive"]=true;
		$f["PrintLastLog"]=true;
		$f["UseDNS"]=true;
		$f["UsePAM"]=true;
        $f["PermitTTY"]=true;

		return $f;
	}
	
	public function BooleanConvert($value):string{
		if($value=="yes"){return "yes";}
		if($value=="no"){return "no";}
		if($value==null){return "no";}
		if(intval($value)==1){return "yes";}
		if(intval($value)==0){return "no";}
        return "no";
	}
	
	function SaveInterface(){
        $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(base64_encode(serialize($this->main_array)),"OpenSSHDArray");
	}

	function pamd(){
        $SSHGoogle2FA           = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHGoogle2FA"));
        if($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PAM_GOOGLE_AUTHENTICATOR_INSTALLED")==0){$SSHGoogle2FA=0;}


        if($SSHGoogle2FA==0){$f[]="@include common-auth";}
        $f[]="account    required     pam_nologin.so";
        $f[]="@include common-account";
        $f[]="session [success=ok ignore=ignore module_unknown=ignore default=bad]        pam_selinux.so close";
        $f[]="session    required     pam_loginuid.so";
        $f[]="session    optional     pam_keyinit.so force revoke";
        $f[]="@include common-session";
        $f[]="session    optional     pam_motd.so  motd=/run/motd.dynamic";
        $f[]="session    optional     pam_motd.so noupdate";
        $f[]="session    optional     pam_mail.so standard noenv";
        $f[]="session    required     pam_limits.so";
        $f[]="session    required     pam_env.so user_readenv=1 envfile=/etc/default/locale";
        $f[]="session [success=ok ignore=ignore module_unknown=ignore default=bad]        pam_selinux.so open";
        $f[]="@include common-password";
        if($SSHGoogle2FA==1){
            $f[]="auth required pam_google_authenticator.so user=root secret=/etc/ssh/pam_google_authenticator.conf";
        }
        $f[]="";
        @file_put_contents("/etc/pam.d/sshd",@implode("\n",$f));
    }

    public function pam_google_authenticator_qrcode():bool{
        $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
        $WizardSavedSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));
        if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
        if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]="Unknown Company";}
        $hostname=php_uname("n");

        shell_exec("/usr/bin/google-authenticator --time-based --allow-reuse --force --rate-limit=5 --rate-time=60 --window-size=17 --issuer=\"{$LicenseInfos["COMPANY"]}\" --label=ssh@$hostname --secret=/etc/ssh/pam_google_authenticator.conf > /etc/ssh/pam_google_authenticator.auth");

        $fdate=explode("\n",@file_get_contents("/etc/ssh/pam_google_authenticator.auth"));

        foreach ($fdate as $line){
            if(preg_match("#\.google\.com\/(.+?)$#",$line,$re)){
                $uri="https://www.google.com/$re[1]";
                break;
            }
        }
        if($uri==null){
            return false;
        }
        include_once(dirname(__FILE__)."/class.ccurl.inc");
        $curl=new ccurl($uri);
        if(!$curl->GetFile(ARTICA_ROOT."/img/pam_google_authenticator_qrcode.png")){
            echo "Unable to download $uri with error code $curl->error_num\n$curl->error\n";
            return false;}
        return true;
    }
	
	function save($returnconf=false){
        $OPENSSH_VER=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OPENSSH_VER");
		if(preg_match("#([0-9]+)\.([0-9]+)#",$OPENSSH_VER,$re)){$opensshver="{$re[1]}{$re[2]}";}
		if($opensshver<50){unset($this->main_array["MaxSessions"]);}
		unset($this->main_array["finduser"]);
		unset($this->main_array["UsePAM"]);

		$ListenPort=intval($this->main_array["Port"]);
		if($ListenPort==0){$ListenPort=22;}

		$SSHDBanner             = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDBanner"));
        $SSHDInterface          = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDInterface"));
		$EnableFail2Ban         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFail2Ban"));
		$FAIL2BAN_INSTALLED     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FAIL2BAN_INSTALLED"));
		$SSHDNotifyConnected    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDNotifyConnected"));
		$SSHDUsePAM             = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDUsePAM"));
        if (!file_exists("/etc/artica-postfix/settings/Daemons/SSHDUsePAM")){
            $SSHDUsePAM=1;
        }
		$SSHDCiphers            = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDCiphers"));
        $SSHDCiphersEnable      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDCiphersEnable"));
        $SSHDProtocol           = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDProtocol"));
        $EnableSSHPortal        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSSHPortal"));
        $sshdportalPort         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sshdportalPort"));
        $SSHGoogle2FA           = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHGoogle2FA"));
        $EnableSSHProxy         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSSHProxy"));
        $PasswordAuthenticationUsers = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PasswordAuthenticationUsers"));

        if($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PAM_GOOGLE_AUTHENTICATOR_INSTALLED")==0){$SSHGoogle2FA=0;}

        if($SSHGoogle2FA==1) {
            $this->main_array["ChallengeResponseAuthentication"] = "yes";
            $SSHDUsePAM=1;
            if(!is_file("/etc/ssh/pam_google_authenticator.conf")){
                $this->pam_google_authenticator_qrcode();
            }

        }
        $this->pamd();

        if($SSHDProtocol==null){$SSHDProtocol=2;}
        if($SSHDCiphers==null){$SSHDCiphers="aes256-ctr,aes128-cbc,3des-cbc,aes192-cbc,aes256-cbc";}




		if($FAIL2BAN_INSTALLED==1){
				if($EnableFail2Ban==1){
					$this->main_array["LogLevel"]="VERBOSE";
				}
			}
		
		
		$sshd_authorizedkeys_Number=$this->sshd_authorizedkeys();
		if($sshd_authorizedkeys_Number>0){
            $this->main_array["PubkeyAuthentication"]="yes";
        }
        if(!isset($this->main_array["Banner"])){
            $this->main_array["Banner"]=0;
        }
		
		if($this->main_array["Banner"]==1){
			if(strlen($SSHDBanner)>5){
				$this->main_array["Banner"]="/etc/artica-postfix/settings/Daemons/SSHDBanner";
			}
		}else{
			unset($this->main_array["Banner"]);
		}
        $IPClass=new IP();
		$ListenAddress=array();
		$BooleanKeys=$this->BooleanKeys();
		$conf[]="# ".date("Y-m-d H:i:s");

		$conf[]="Protocol\t$SSHDProtocol";
        unset($this->main_array["Protocol"]);

		if($SSHDUsePAM==1){
			$conf[]="UsePAM\tyes";
		}else{
			$conf[]="UsePAM\tno";
		}

		unset($this->main_array["UsePAM"]);

        foreach ($this->main_array as $num=>$line){
			if(trim($line)==null){continue;}
			if($num=="SSHGoogle2FA"){continue;}
            if($num=="PasswordAuthenticationUsers"){continue;}
			if(isset($BooleanKeys[$num])){$line=$this->BooleanConvert($line);}
			$conf[]="$num\t$line";
		}
		

		$CountOfListenAddress=count($ListenAddress);
		$conf[]="ListenAddress\t127.0.0.1:884";
		

			if($CountOfListenAddress==0){
				$unix=new unix();
				$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES(true);
				foreach ($NETWORK_ALL_INTERFACES as $ipaddr=>$none){
					if($ipaddr=="127.0.0.1"){continue;}
                    if(!$IPClass->isValid($ipaddr)) {continue;}
					$ListenAddress[]="ListenAddress\t$ipaddr:$ListenPort";
				}

			}

		
		$CountOfListenAddress=count($ListenAddress);
		if($CountOfListenAddress>0){
			$conf[]=@implode("\n", $ListenAddress);}
		    foreach ($this->HostKey as $line){
			$conf[]="HostKey\t$line";
		}




		
        $AllowGroupsCompile=$this->AllowGroupsCompile();
        if($AllowGroupsCompile<>null) {
            $conf[] = "AllowGroups\t" . $this->AllowGroupsCompile();
        }
        $conf[]="AllowUsers\t".$this->AllowUsersCompile();

			
		$conf[]="";
		if($SSHDCiphersEnable==1) {
            $conf[] = "Ciphers $SSHDCiphers";
        }
		$conf[]="MACs hmac-sha1,umac-64@openssh.com";

        if($this->main_array["PasswordAuthentication"]==0) {
            $conf[] = "Match host 127.0.0.1";
            $conf[] = "\tPasswordAuthentication yes";
            $conf[] = "\tAllowUsers\troot";
            $conf[] = "";

            if (strlen($PasswordAuthenticationUsers)>3){
                $ipclass=new IP();
                $PasswordAuthenticationUsers=str_replace(" ",",",$PasswordAuthenticationUsers);
                $PasswordAuthenticationUsers=str_replace(";",",",$PasswordAuthenticationUsers);
                $Ips=explode(",",$PasswordAuthenticationUsers);
                foreach ($Ips as $ipaddr){
                    $ipaddr=trim($ipaddr);
                    if(strlen($ipaddr)<3){continue;}
                    if(!$ipclass->IsACDIROrIsValid($ipaddr)){continue;}
                    $conf[]="Match host $ipaddr";
                    $conf[]="\tPasswordAuthentication yes";
                    $conf[]="";
                }

            }
        $conf[]="Match all";
        }
        $conf[]="";


		
		if($SSHDNotifyConnected==1){
			$unix=new unix();
			$echo=$unix->find_program("echo");
			$cut=$unix->find_program("cut");
			$hostname=$unix->find_program("hostname");
			$logger=$unix->find_program("logger");
			$php=$unix->LOCATE_PHP5_BIN();
			$nohup=$unix->find_program("nohup");
			@mkdir("/etc/ssh",0755,true);
			$bash[]="ip=`$echo \$SSH_CONNECTION | $cut -d \" \" -f 1`";
			$bash[]="hostname=`$hostname`";
			$bash[]="$logger -t sshd[00000] warning notify \$USER login from \$ip";
			$bash[]="$nohup $php /usr/share/artica-postfix/exec.sshd.php --notify \"\$ip;\$USER\" >/dev/null 2>&1 &";
			$bash[]="";
			@file_put_contents("/etc/ssh/sshrc", @implode("\n", $bash));
		}else{
			if(is_file("/etc/ssh/sshrc")){@unlink("/etc/ssh/sshrc");}
		}
		$sock=new sockets();
		$sock->SaveConfigFile(@implode("\n",$conf),"OpenSSHDConfig");
		if($returnconf){return @implode("\n", $conf);}
		
		
		
	}
		
	
	
	
}
?>