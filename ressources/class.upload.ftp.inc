<?php
if(!isset($GLOBALS["VERBOSE"])){$GLOBALS["VERBOSE"]=false;}

class ftp_upload_repo{
	private $UfdbCatsUploadFTPserv;
	private $UfdbCatsUploadFTPusr;
	private $UfdbCatsUploadFTPpass;
	private $UfdbCatsUploadFTPDir;
	public $ftp_infos;
	
	public function __construct(){
		
		$this->UfdbCatsUploadFTPserv=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPserv"));
		$this->UfdbCatsUploadFTPusr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPusr"));
		$this->UfdbCatsUploadFTPpass=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPpass"));
		$this->UfdbCatsUploadFTPDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPDir"));
	}
	
	public function ftp_upload($localfile){
		
		
		$ch = curl_init();
		$fp = fopen($localfile, 'r');
		if($this->UfdbCatsUploadFTPusr<>null){
			$auth=rawurlencode($this->UfdbCatsUploadFTPusr).":".rawurlencode($this->UfdbCatsUploadFTPpass);
			$auth=$auth."@";
		
		}
		$basename=basename($localfile);
		$size=filesize($localfile);
		$uri="ftp://{$auth}$this->UfdbCatsUploadFTPserv/$this->UfdbCatsUploadFTPDir/$basename";
		if($GLOBALS["VERBOSE"]){echo "ftp://$this->UfdbCatsUploadFTPusr:***@$this->UfdbCatsUploadFTPserv/$this->UfdbCatsUploadFTPDir/$basename";}
		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_UPLOAD, 1);
		curl_setopt($ch, CURLOPT_INFILE, $fp);
		curl_setopt($ch, CURLOPT_INFILESIZE, $size);
		curl_exec ($ch);
		$this->ftp_infos= curl_getinfo($ch);
		$http_code=$this->ftp_infos["http_code"];
		
		if(curl_errno($ch)){
			$this->curl_error=curl_error($ch);
			squid_admin_mysql(0, "Unable to upload file $localfile to repository With Error $this->curl_error ( task aborted)", null,__FILE__,__LINE__);
			if($GLOBALS["VERBOSE"]){echo "Error:Curl error: $this->curl_error\n";}
			curl_close($ch);
			return;
		}
		curl_close($ch);
		return true;
		
	}
	
	
	
}