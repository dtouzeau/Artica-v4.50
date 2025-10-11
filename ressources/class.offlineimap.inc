<?php


class offlineimap{
	
	
	
	var $uid=null;

	var $local_ssl=0;
	var $remote_username=null;
	var $local_username=null;
	var $local_cert_fingerprint=null;
	
	var $RemoteType=null;
	var $remote_password=null;
	var $remote_imap=null;
	var $remote_ssl=0;
	var $remote_cert_fingerprint=null;
	
	var $maxage=0;
	var $maxsize=0;
	var $createfolders=null;
	var $readonly=null;
	var $LocalType=null;
	
	var $maildir_path=null;
	var $maildir_final=null;
	
	var $MD5_account=null;
	
	
	function __construct(){
		
	}
	
	
	function buildconf(){
		$this->MD5_account=md5($this->remote_username.$this->remote_imap);
		$offlineImapConf[]="[general]";
		$offlineImapConf[]="accounts = $this->remote_username";
		$offlineImapConf[]="maxsyncaccounts = 1";
		$offlineImapConf[]="ui =Noninteractive.Basic, Noninteractive.Quiet";
		$offlineImapConf[]="ignore-readonly = no";
		$offlineImapConf[]="socktimeout = 60";
		$offlineImapConf[]="fsync = true";
		$offlineImapConf[]="";
		$offlineImapConf[]="";
		$offlineImapConf[]="[ui.Curses.Blinkenlights]";
		$offlineImapConf[]="statuschar = .";
		$offlineImapConf[]="";
		$offlineImapConf[]="[Account $this->remote_username]";
		$offlineImapConf[]="localrepository = Local$this->MD5_account";
		$offlineImapConf[]="remoterepository = Remote$this->MD5_account";
		$offlineImapConf[]="status_backend = sqlite";
		if($this->maxsize>0){$this->maxsize=($this->maxsize*1024)*1000;$offlineImapConf[]="maxsize = $this->maxsize";}
		if($this->maxage>0){$offlineImapConf[]="maxage = $this->maxage";}	
		
		$local=$this->Local();
		$remote=$this->Remote();
		if($local==null){$this->buildlogs("Fatal error while creating local config", __FUNCTION__, __LINE__);return;}
		if($remote==null){$this->buildlogs("Fatal error while creating remote config", __FUNCTION__, __LINE__);return;}
		$offlineImapConf[]=$local;
		$offlineImapConf[]="";
		$offlineImapConf[]=$remote;
		return @implode("\n", $offlineImapConf);
		
		
		
	}
	
	private function Local(){
		$offlineImapConf[]="[Repository Local$this->MD5_account]";
		
		if($this->LocalType=="maildir"){
			$this->maildir_final=$this->local_maildir();
			if($this->maildir_final==null){$this->buildlogs("Fatal error while retreive maildir path", __FUNCTION__, __LINE__);return;}
			if(!is_dir($this->maildir_final)){@mkdir($this->maildir_final,0755,true);}
			
			$offlineImapConf[]="type = Maildir";
			$offlineImapConf[]="localfolders = $this->maildir_final";
			return @implode("\n", $offlineImapConf);
		}
		
	}
	
	private function Remote(){
		
		$offlineImapConf[]="[Repository Remote$this->MD5_account]";
		
		if($this->RemoteType=="imap"){
			if($this->remote_username==null){
				$this->buildlogs("No remote account defined", __FUNCTION__, __LINE__);
				return;
			}
			
			if($this->remote_password==null){
				$this->buildlogs("No remote password defined", __FUNCTION__, __LINE__);
				return;
			}			
			
			$offlineImapConf[]="type = IMAP";
			$offlineImapConf[]="maxconnections = 3";
			$offlineImapConf[]="remotehost = $this->remote_imap";
			if($this->remote_ssl==1){
				$offlineImapConf[]="ssl = 1";
				$offlineImapConf[]="remoteport = 993";}
			else{
				$offlineImapConf[]="ssl = 0";
				$offlineImapConf[]="remoteport = 143";
			}
			if(strlen($this->remote_cert_fingerprint)>5){$offlineImapConf[]="cert_fingerprint = $this->remote_cert_fingerprint";}
			$offlineImapConf[]="remoteuser = $this->remote_username";
			$offlineImapConf[]="remotepass = $this->remote_password";				
			$offlineImapConf[]="holdconnectionopen = no";
			$offlineImapConf[]="expunge = no";
			$offlineImapConf[]="subscribedonly = no";
			$offlineImapConf[]="readonly = True";	
			return @implode("\n", $offlineImapConf)		;
			
		}
		
		
		$this->buildlogs("No RemoteType defined", __FUNCTION__, __LINE__);
		
	}
	
	
	
	private function local_maildir(){
		$this->maildir_path=$this->maildir_path."/$this->remote_imap/$this->remote_username";
		$this->maildir_path=$this->maildir_parse($this->maildir_path,$this->uid);
		return $this->maildir_path;
		
	}
	
	public function maildir_parse($dir,$uid){
		if($uid==null){return $dir;}
		include_once(dirname(__FILE__)."/class.user.inc");
		$u=new user($uid);
		if(!$u->homeDirectory==null){$u->homeDirectory="/home/$uid";}
		$dir=str_replace("%HOME%", $u->homeDirectory, $dir);
		return $dir;
	}
	
	
private function buildlogs($text,$function,$line){
	echo "$function:: $text in line $line\n";
	$GLOBALS["EV"][]="$function:: $text in line $line";
	
}	
	
	
	
	
	
	
}