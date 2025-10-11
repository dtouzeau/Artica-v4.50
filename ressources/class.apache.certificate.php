<?php

class apache_certificate{
	private $CommonName=null;
	private $ssl_path="/etc/ssl/certs/apache";
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
	private $PrefixOutput;
	private $bundle;
	private $AS_ROOT=false;
	private $LOGS=array();
	public $SSLCertificateFile=null;
	public $SSLCertificateKeyFile=null;
	public $SSLCertificateChainFile=null;
	
	function __construct($CommonName=null){
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		if($CommonName<>null){$this->CommonName=$CommonName;}
		$this->PrefixOutput="Starting......: ".date("H:i:s")." [INIT]: Apache \"CERT\"";
		if($GLOBALS["posix_getuid"]==0){$this->AS_ROOT=true;}
	}
	
	
	public function build(){
		
		if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] $this->CommonName\n";}
		if($this->CommonName==null){return $this->build_default();}
		$this->load_certificate();
		if($this->UsePrivKeyCrt==1){return $this->BuildOfficial();}
		$certificate_subdir=str_replace("*", "_ALL_", $this->CommonName);
		
		$Directory="$this->ssl_path/$certificate_subdir";
		@mkdir($Directory,0755,true);
		$PRIVATE_KEY=$this->srca_content;
		$CERTIFICATE=$this->crt_content;
		
		if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] $Directory/server.crt ".strlen($CERTIFICATE)." bytes\n";}
		@file_put_contents("$Directory/server.crt", "$CERTIFICATE");
		@file_put_contents("$Directory/server.key", "$PRIVATE_KEY");
		if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] $Directory/server.key ".strlen($PRIVATE_KEY)." bytes\n";}
		
		if(!is_file("$Directory/server.crt")){return $this->build_default();}
		$f[]="# Use SSL key [".__CLASS__."/".__LINE__."]";
		$f[]=@implode("\n", $this->LOGS);
		$f[]="\tSSLCertificateFile \"$Directory/server.crt\"";
		$f[]="\tSSLCertificateKeyFile \"$Directory/server.key\"";
		
		$this->SSLCertificateFile="$Directory/server.crt";
		$this->SSLCertificateKeyFile="$Directory/server.key";
		
		return @implode("\n",$f);
	}
	
	
	private function BuildOfficial(){
			$certificate_subdir=str_replace("*", "_ALL_", $this->CommonName);
			$Directory="$this->ssl_path/$certificate_subdir";
			@mkdir($Directory,0755,true);
			

			
			$PRIVATE_KEY=$this->srca_content;
			$CERTIFICATE=$this->crt_content;
			$CHAIN=$this->bundle;
		
			@file_put_contents("$Directory/server.crt", "$CERTIFICATE");
			@file_put_contents("$Directory/server.key", "$PRIVATE_KEY");
			
			$f[]="# Use Official key [".__CLASS__."/".__LINE__."]";
			$f[]="\tSSLCertificateFile \"$Directory/server.crt\"";
			$f[]="\tSSLCertificateKeyFile \"$Directory/server.key\"";
			
			$this->SSLCertificateFile="$Directory/server.crt";
			$this->SSLCertificateKeyFile="$Directory/server.key";
			
			
			if(strlen($CHAIN)>20){
				$f[]="# Use CHAIN certificate [".__CLASS__."/".__LINE__."]";
				$this->SSLCertificateChainFile="$Directory/chain.pem";
				@file_put_contents("$Directory/chain.pem", "$CHAIN");
				$f[]="\tSSLCertificateChainFile \"$Directory/chain.pem\"";
			}
			
			return @implode("\n", $f);
	}
	
	
	private function load_certificate(){
		$q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
		if(!$q->FIELD_EXISTS("sslcertificates","DynamicCert","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `DynamicCert` TEXT NOT NULL";$q->QUERY_SQL($sql,'artica_backup');}
		$sql="SELECT `UsePrivKeyCrt`,`crt`,`csr`,`srca`,`clientkey`,`clientcert`,`DynamicCert`,`privkey`,`SquidCert`,`Squidkey`,`bundle`
		FROM sslcertificates WHERE CommonName='$this->CommonName'";
		$ligne=$q->mysqli_fetch_array($sql);
		if(!$q->ok){
			$this->LOGS[]="#".str_replace("\n", " ", $q->mysql_error);
		}
		$this->LOGS[]="# $this->CommonName UsePrivKeyCrt={$ligne["UsePrivKeyCrt"]}";
		
		$this->UsePrivKeyCrt=intval($ligne["UsePrivKeyCrt"]);
		$this->crt_content=str_replace("\\n","\n",$ligne["crt"]);
		$this->csr_content=str_replace("\\n","\n",$ligne["csr"]);
		$this->srca_content=str_replace("\\n","\n",$ligne["srca"]);
		
		
		$this->privkey_content=str_replace("\\n","\n",$ligne["privkey"]);
		$this->SquidCert=str_replace("\\n","\n",$ligne["SquidCert"]);
		$this->Squidkey=str_replace("\\n","\n",$ligne["Squidkey"]);
		$this->clientkey=$ligne["clientkey"];
		$this->clientcert=$ligne["clientkey"];
		$this->RootCa=str_replace("\\n","\n",$ligne["srca"]);
		$this->bundle=str_replace("\\n","\n",$ligne["bundle"]);
		
		if($this->UsePrivKeyCrt==0){
			$this->srca_content=$this->Squidkey;
			$this->crt_content=$this->SquidCert;
		}else{
			$this->srca_content=$ligne["privkey"];
			$this->crt_content=str_replace("\\n","\n",$ligne["crt"]);
		}
			

		if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] Private key: ".strlen($this->srca_content)." bytes\n";}
		if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] Certificate: ".strlen($this->crt_content)." bytes\n";}
			
		
	}
	
	
	private function build_default(){
		if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] Build default certificate\n";}
		if(!is_file("/etc/ssl/certs/apache/server.crt")){
			@chmod("/usr/share/artica-postfix/bin/artica-install", 0755);
			shell_exec("/usr/share/artica-postfix/bin/artica-install --apache-ssl-cert");
		}
		
		$this->SSLCertificateFile="/etc/ssl/certs/apache/server.crt";
		$this->SSLCertificateKeyFile="/etc/ssl/certs/apache/server.key";
		
		$f[]="SSLCertificateFile \"/etc/ssl/certs/apache/server.crt\"";
		$f[]="SSLCertificateKeyFile \"/etc/ssl/certs/apache/server.key\"";
		return @implode("\n",$f);
		
	}
	
	
}

