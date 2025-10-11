<?php
class squid_ftp{
	private $FTP_PARAMS=array();
	
	function __construct(){
		$this->FTP_PARAMS=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidFTPParams")));
		
	}
	
	
	public function build(){
		$conf[]="";
		$conf[]="#--------- FTP specific parameters";

		
		if(!isset($this->FTP_PARAMS["ftp_sanitycheck"])){$this->FTP_PARAMS["ftp_sanitycheck"]='yes';}
		if(!isset($this->FTP_PARAMS["ftp_epsv"])){$this->FTP_PARAMS["ftp_epsv"]='yes';}
		if(!isset($this->FTP_PARAMS["ftp_epsv_all"])){$this->FTP_PARAMS["ftp_epsv_all"]='no';}
		if(!isset($this->FTP_PARAMS["ftp_telnet_protocol"])){$this->FTP_PARAMS["ftp_telnet_protocol"]='yes';}
		if(!isset($this->FTP_PARAMS["ftp_user"])){$this->FTP_PARAMS["ftp_user"]=null;}
		if(!isset($this->FTP_PARAMS["ftp_passive"])){$this->FTP_PARAMS["ftp_passive"]=1;}
	
	
		
		if($this->FTP_PARAMS["ftp_sanitycheck"]==null){$this->FTP_PARAMS["ftp_sanitycheck"]='yes';}
		if($this->FTP_PARAMS["ftp_epsv"]==null){$this->FTP_PARAMS["ftp_epsv"]='yes';}
		if($this->FTP_PARAMS["ftp_epsv_all"]==null){$this->FTP_PARAMS["ftp_epsv_all"]='no';}
		if($this->FTP_PARAMS["ftp_telnet_protocol"]==null){$this->FTP_PARAMS["ftp_telnet_protocol"]='yes';}
		if(!isset($this->FTP_PARAMS["ftp_user"])){$this->FTP_PARAMS["ftp_user"]=null;}
		$ftp_passive=$this->FTP_PARAMS["ftp_passive"];
		$ftp_sanitycheck=$this->FTP_PARAMS["ftp_sanitycheck"];
		$ftp_epsv=$this->FTP_PARAMS["ftp_sanitycheck"];
		$ftp_epsv_all=$this->FTP_PARAMS["ftp_epsv_all"];
		$ftp_telnet_protocol=$this->FTP_PARAMS["ftp_telnet_protocol"];
	
		if($ftp_passive==null){$ftp_passive=1;}
		if($ftp_passive==1){$ftp_passive="on";}else{$ftp_passive="off";}
	
		if($ftp_sanitycheck==null){$ftp_sanitycheck=1;}
		if($ftp_sanitycheck==1){$ftp_sanitycheck="on";}else{$ftp_sanitycheck="off";}
	
		if($ftp_epsv==null){$ftp_epsv=1;}
		if($ftp_epsv==1){$ftp_epsv="allow all";}else{$ftp_epsv="deny all";}
	
		if($ftp_epsv_all==null){$ftp_epsv_all=0;}
		if($ftp_epsv_all==1){$ftp_epsv_all="on";}else{$ftp_epsv_all="off";}
	
		if($ftp_telnet_protocol==null){$ftp_telnet_protocol=0;}
		if($ftp_telnet_protocol==1){$ftp_telnet_protocol="on";}else{$ftp_telnet_protocol="off";}
	

	    if(!is_null($this->FTP_PARAMS["ftp_user"])) {
            if (preg_match("#(.+?)@#", $this->FTP_PARAMS["ftp_user"])) {
                $conf[] = "ftp_user {$this->FTP_PARAMS["ftp_user"]}";
            }
        }
	
	
		$conf[]="ftp_passive $ftp_passive";
		$conf[]="ftp_sanitycheck $ftp_sanitycheck";
		$conf[]="ftp_epsv $ftp_epsv";
		$conf[]="ftp_epsv_all $ftp_epsv_all";
		$conf[]="ftp_telnet_protocol $ftp_telnet_protocol";
		$conf[]="";
		$conf[]="";
		@file_put_contents("/etc/squid3/FTP.conf", @implode("\n", $conf));
		
	}
	
}