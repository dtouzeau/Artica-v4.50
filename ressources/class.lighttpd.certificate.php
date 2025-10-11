<?php

class lighttpd_certificate{
	private $CommonName=null;
	private $ssl_path="/etc/lighttpd/certificates";
	private $UsePrivKeyCrt=0;
	private $crt_content=null;
	private $csr_content=null;
	private $srca_content=null;
	private $privkey_content=null;
	private $SquidCert=null;
	private $Squidkey=null;
	private $clientkey=null;
	private $clientcert=null;
	private $ssl_client_certificate=0;
	private $RootCa=null;
	
	function  __construct($CommonName=null){
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		if($CommonName<>null){$this->CommonName=$CommonName;}
		
	}
	
	
	public function build(){
		if($this->CommonName==null){return $this->build_default();}
		$this->load_certificate();
		$certificate_subdir=str_replace("*", "_ALL_", $this->CommonName);
		
		$Directory="$this->ssl_path/$certificate_subdir";
		@mkdir($Directory,0755,true);
		$PRIVATE_KEY=$this->srca_content;
		$CERTIFICATE=$this->crt_content;
		@file_put_contents("$Directory/lighttpd.pem", "$PRIVATE_KEY\n$CERTIFICATE");
		return "\tssl.pemfile\t\t= \"$Directory/lighttpd.pem\"";
			
		
		
	}
	
	private function load_certificate(){
		$q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
		$sql="SELECT `UsePrivKeyCrt`,`crt`,`csr`,`srca`,`clientkey`,`clientcert`,`privkey`,`SquidCert`,`Squidkey`,`bundle`
		FROM sslcertificates WHERE CommonName='$this->CommonName'";
		$ligne=$q->mysqli_fetch_array($sql);
		$this->UsePrivKeyCrt=$ligne["UsePrivKeyCrt"];
		$this->crt_content=str_replace("\\n","\n",$ligne["crt"]);
		$this->csr_content=str_replace("\\n","\n",$ligne["csr"]);
		$this->srca_content=str_replace("\\n","\n",$ligne["srca"]);
		$this->privkey_content=str_replace("\\n","\n",$ligne["privkey"]);
		$this->SquidCert=str_replace("\\n","\n",$ligne["SquidCert"]);
		$this->Squidkey=str_replace("\\n","\n",$ligne["Squidkey"]);
		$this->clientkey=$ligne["clientkey"];
		$this->clientcert=$ligne["clientkey"];
		$this->RootCa=str_replace("\\n","\n",$ligne["srca"]);
		
		if($this->UsePrivKeyCrt==0){
			$this->srca_content=$this->Squidkey;
			$this->crt_content=$this->SquidCert;
		}else{
			$this->srca_content=$ligne["privkey"];
			$this->crt_content=str_replace("\\n","\n",$ligne["crt"]);
		}
		
	}
	
	
	private function build_default(){
		if(!is_file("/opt/artica/ssl/certs/lighttpd.pem")){
			@chmod("/usr/share/artica-postfix/bin/artica-install", 0755);
			shell_exec("/usr/share/artica-postfix/bin/artica-install -lighttpd-cert");
		
		}
		return "\tssl.pemfile\t\t= \"/opt/artica/ssl/certs/lighttpd.pem\"";
		
	}
	
	
}

