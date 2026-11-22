<?php

class openvpn_certificate{
	private $CommonName=null;
	private $ssl_path="/etc/ssl/certs/openvpn";
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
	private $password=null;
	private $LOGS=array();
	
	public $ca_path;
	public $dh_path;
	public $key_path;
	public $cert_path;
	public $password_path;
	public $config_path;
	
	function __construct($CommonName=null){
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		
		if($CommonName==null){
			$unix=new unix();
			$this->CommonName=$unix->hostname_g();
		}
		
		if($CommonName<>null){$this->CommonName=$CommonName;}
		$this->PrefixOutput="Starting......: ".date("H:i:s")." [INIT]: OpenVPN \"CERT\"";
		if($GLOBALS["posix_getuid"]==0){$this->AS_ROOT=true;}
	}
		
	public function build(){
		$this->load_certificate();

		
		
		@mkdir("/etc/openvpn/certificates/server",0755,true);
		if(!is_file("/etc/openvpn/certificates/server/dhparam.pem")){
			if(is_file("/etc/squid3/ssl/dhparam.pem")){@copy("/etc/squid3/ssl/dhparam.pem","/etc/openvpn/certificates/server/dhparam.pem");}
		}
		
		if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." OpenVPN CA.........: ". strlen($this->srca_content)." bytes\n";}
		if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." OpenVPN Private Key: ". strlen($this->privkey_content)." bytes\n";}
		if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." OpenVPN Certificate: ". strlen($this->crt_content)." bytes\n";}
		
		
		
		@file_put_contents("/etc/openvpn/certificates/server/ca.ca", $this->srca_content);
		@file_put_contents("/etc/openvpn/certificates/server/key.key", $this->privkey_content);
		@file_put_contents("/etc/openvpn/certificates/server/cert.crt", $this->crt_content);
		
		@chmod("/etc/openvpn/certificates/server/key.key", 0600);
		
		
		
		$f[]="--ca /etc/openvpn/certificates/server/ca.ca";
		$f[]="--dh /etc/openvpn/certificates/server/dhparam.pem";
		$f[]="--key /etc/openvpn/certificates/server/key.key";
		$f[]="--cert /etc/openvpn/certificates/server/cert.crt";
		
		
		return @implode(" ", $f);
	}
	
	

	
	
	private function load_certificate(){
		
		if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." OpenVPN loading certificate $this->CommonName\n";}

		include_once(dirname(__FILE__)."/class.certificates-db.inc");
		$cdb=new CertificatesDB();
		$row=$cdb->getByCommonName($this->CommonName);

		if(!is_object($row)){
			if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." OpenVPN Certificate $this->CommonName was not created, create a new one\n";}
			$this->CreateCertificate($this->CommonName);
			$row=$cdb->getByCommonName($this->CommonName);
			if(!is_object($row)){
				if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." OpenVPN loading certificate Failed: $cdb->lastError\n";}
				$this->LOGS[]="#".str_replace("\n", " ", $cdb->lastError);
				return;
			}
		}
		$ligne=(array)$row;
		$this->LOGS[]="# $this->CommonName UsePrivKeyCrt={$ligne["UsePrivKeyCrt"]}";
		
		$this->UsePrivKeyCrt=intval($ligne["UsePrivKeyCrt"]);
		$this->crt_content=str_replace("\\n","\n",$ligne["crt"]);
		$this->csr_content=str_replace("\\n","\n",$ligne["csr"]);
		$this->srca_content=str_replace("\\n","\n",$ligne["srca"]);
		
		
		

		$this->password=$ligne["password"];
		$this->privkey_content=str_replace("\\n","\n",$ligne["privkey"]);
		$this->SquidCert=str_replace("\\n","\n",$ligne["SquidCert"]);
		$this->Squidkey=str_replace("\\n","\n",$ligne["Squidkey"]);
		$this->clientkey=$ligne["clientkey"];
		$this->clientcert=$ligne["clientkey"];
		$this->RootCa=str_replace("\\n","\n",$ligne["srca"]);
		$this->bundle=str_replace("\\n","\n",$ligne["bundle"]);
		if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." OpenVPN certificate use Admin cert = $this->UsePrivKeyCrt\n";}


			
		
	}
	
	private function CreateCertificate($certificatename){
		if($_POST["CertificateMaxDays"]==0){$_POST["CertificateMaxDays"]=730;}
		$fields[]="CountryName"	;
		$fields[]="stateOrProvinceName"	;
		$fields[]="CertificateMaxDays"	;
		$fields[]="OrganizationName"	;
		$fields[]="OrganizationalUnit"	;
		$fields[]="emailAddress"	;
		$fields[]="localityName"	;
		$fields[]="levelenc"	;
		$fields[]="password"	;
		$fields[]="CommonName"	;
		$fields[]="UsePrivKeyCrt"	;
		
		$values[]="'US'";
		$values[]="'New York'";
		$values[]="'730'";
		$values[]="'IT'";
		$values[]="'IT service'";
		$values[]="'root@$certificatename'";
		$values[]="'Brooklin'";
		$values[]="'2048'";
		$values[]="''";
		$values[]="'$certificatename'";
		$values[]="'0'";
		include_once(dirname(__FILE__)."/class.certificates-db.inc");
		$cdb=new CertificatesDB();
		// INSERT OR IGNORE → check exists first, then insert (best-effort; idempotent)
		[$exists,$existsId]=$cdb->exists($certificatename);
		if(!$exists){
			$newId=$cdb->insert([
				"CountryName"=>"US",
				"stateOrProvinceName"=>"New York",
				"CertificateMaxDays"=>730,
				"OrganizationName"=>"IT",
				"OrganizationalUnit"=>"IT service",
				"emailAddress"=>"root@$certificatename",
				"localityName"=>"Brooklin",
				"levelenc"=>2048,
				"password"=>"",
				"CommonName"=>$certificatename,
				"UsePrivKeyCrt"=>0,
			]);
			if($newId<=0){return;}
		}
		$sock=new sockets();
		$sock->REST_API_TIMEOUT=120;
		$sock->REST_API_POST_JSON("/certificate/build-csr",array("common_name"=>$certificatename));
		
	}
	
	
	private function build_default(){}
	
	
	public function buildSubj($ForceCommonName=null){}
	
	
	
	
}

