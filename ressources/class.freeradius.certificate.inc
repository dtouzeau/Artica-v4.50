<?php



class freeradius_certificate{
	private $ssl_path="/etc/ssl/certs/freeradius";
	private $certifcate_conf="/etc/ssl/certs/freeradius/openssl.conf";
	private $certificate=null;
	private $UsePrivKeyCrt=0;
	private $UseGodaddy=0;
	private $crt_content=null;
	private $csr_content=null;
	private $srca_content=null;
	private $privkey_content=null;
	private $bundle=null;
	private $SquidCert=null;
	private $Squidkey=null;
	private $clientkey=null;
	private $clientcert=null;
	private $ssl_client_certificate=0;
	private $CertPassword=null;
	private $RootCa=null;
	
	function __construct($certificate=null){
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		if($certificate<>null){$this->certificate=$certificate;}
	}
	
	
	private function certificate_default(){
		
		$ldap=new clladp();
		$sock=new sockets();
		$unix=new unix();
		@mkdir($this->ssl_path."/default",0755,true);
		
		if($GLOBALS["AS_ROOT"]){echo "*************** BUILDING DEFAULT CERTIFICATE ***************\n";}
		@unlink("$this->ssl_path/default/privkey.cp.pem");
		@unlink("$this->ssl_path/default/cacert.pem");
		@unlink("$this->ssl_path/default/privkey.pem");
	
		$this->certificate_conf();

		$CertificateMaxDays=$sock->GET_INFO('CertificateMaxDays');
		if($CertificateMaxDays==null){$CertificateMaxDays='730';}
		
		$password=$unix->shellEscapeChars($ldap->ldap_password);
		if($password==null){$password="artica";}
		$openssl=$unix->find_program("openssl");
		$config=$this->certifcate_conf;
		
		//privkey.pem
		//cacert.pem
		
		$cmd="$openssl genrsa -des3 -passout pass:$password -out $this->ssl_path/default/privkey.pem 2048 1024";
		if($GLOBALS["AS_ROOT"]){echo $cmd."\n";}
		system($cmd);
		
		$cmd="$openssl req -new -x509 -nodes -passin pass:$password -key $this->ssl_path/default/privkey.pem -batch -config $config -out $this->ssl_path/default/cacert.pem -days $CertificateMaxDays";
		if($GLOBALS["AS_ROOT"]){echo $cmd."\n";}
		system($cmd);
		system("/bin/cp $this->ssl_path/default/privkey.pem $this->ssl_path/default/privkey.cp.pem");
		system("$openssl rsa -passin pass:$password -in $this->ssl_path/default/privkey.cp.pem -out $this->ssl_path/default/privkey.pem");
	}
	

	private function load_certificate(){
		$q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
		
		$sql="SELECT `UsePrivKeyCrt`,`CertPassword`,`crt`,`csr`,`srca`,`clientkey`,`clientcert`,`privkey`,`SquidCert`,`Squidkey`,
		`bundle`,`UseGodaddy`
		FROM sslcertificates WHERE CommonName='$this->certificate'";
		$ligne=$q->mysqli_fetch_array($sql);
		$this->UsePrivKeyCrt=$ligne["UsePrivKeyCrt"];
		$this->crt_content=str_replace("\\n","\n",$ligne["crt"]);
		$this->csr_content=str_replace("\\n","\n",$ligne["csr"]);
		$this->srca_content=str_replace("\\n","\n",$ligne["srca"]);
		$this->privkey_content=str_replace("\\n","\n",$ligne["privkey"]);
		$this->SquidCert=str_replace("\\n","\n",$ligne["SquidCert"]);
		$this->Squidkey=str_replace("\\n","\n",$ligne["Squidkey"]);
		$this->bundle=str_replace("\\n","\n",$ligne["bundle"]);
		$this->RootCa=str_replace("\\n","\n",$ligne["srca"]);
		$this->UseGodaddy=intval($ligne["UseGodaddy"]);
		$this->CertPassword=trim($ligne["CertPassword"]);

		//ssl_client_certificate
		
	}
	
	
	private function is_default(){
		
		if(!is_file("$this->ssl_path/default/cacert.pem")){return false;}
		if(!is_file("$this->ssl_path/default/privkey.pem")){return false;}
		return true;
	}
	
	public function GetConf(){
		$CA_file=null;
		if($this->certificate==null){
			if(!$this->is_default()){$this->certificate_default();}
			$certificate_file="$this->ssl_path/default/cacert.pem";
			$private_key_file="$this->ssl_path/default/privkey.pem";
		}else{
			@mkdir("$this->ssl_path/$this->certificate",0755,true);
			$private_key_file="$this->ssl_path/$this->certificate/privkey.pem";
			$certificate_file="$this->ssl_path/$this->certificate/cert.crt";
			@file_put_contents($private_key_file, $this->privkey_content);
			@file_put_contents($certificate_file,$this->crt_content);
			
			
			if($this->RootCa<>null){
				@file_put_contents("$this->ssl_path/$this->certificate/ca.ca", $this->RootCa);
				$CA_file="$this->ssl_path/$this->certificate/ca.ca";
			}
			
		}

		if(!is_file("/etc/squid3/ssl/dhparam.pem")){
			@mkdir("/etc/squid3/ssl",0755,true);
			$unix=new unix();
			$openssl=$unix->find_program("openssl");
			system("$openssl dhparam -outform PEM -out /etc/squid3/ssl/dhparam.pem 2048");
				
		}
		
		$default_eap_type="ttls";
		$ttls_default_eap_type="mschapv2";
		$ttls_copy_request_to_tunnel="yes";
		$ttls_use_tunneled_reply="yes";
		$peap_copy_request_to_tunnel="yes";
		$peap_use_tunneled_reply="yes";
		
		$f[]="		tls {";
		$f[]="			#private_key_password = whatever";
		$f[]="			private_key_file = $private_key_file";
		$f[]="			certificate_file = $certificate_file";
		$f[]="			dh_file = /etc/squid3/ssl/dhparam.pem";
		if($CA_file<>null){
			$f[]="			CA_file = $CA_file";
		}
		
		$f[]="			random_file = /dev/urandom";
		$f[]="			cipher_list = \"DEFAULT\"";
		$f[]="			make_cert_command = \"\${certdir}/bootstrap\"";
		$f[]="			cache {";
		$f[]="			      enable = no";
		$f[]="			      lifetime = 24 # hours";
		$f[]="			      max_entries = 255";
		$f[]="			}";
		$f[]="			verify {";
		$f[]="		#     		tmpdir = /tmp/radiusd";
		$f[]="		#    		client = \"/path/to/openssl verify -CApath \${..ca_path} %{TLS-Client-Cert-Filename}\"";
		$f[]="			}";
		$f[]="		}";
		$f[]="";
		$f[]="";
		$f[]="		ttls {";
		$f[]="			default_eap_type = $ttls_default_eap_type";
		$f[]="			copy_request_to_tunnel = $ttls_copy_request_to_tunnel";
		$f[]="			use_tunneled_reply = $ttls_use_tunneled_reply";
		$f[]="			virtual_server = \"inner-tunnel\"";
		$f[]="		#	include_length = yes";
		$f[]="		}";
		
		return @implode("\n", $f);
	}
	

	

	
	private function certificate_conf(){
		include_once(dirname(__FILE__).'/class.ssl.certificate.inc');
		$ssl=new ssl_certificate();
		$array=$ssl->array_ssl;
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		$sock=new sockets();
		$cc=$array["artica"]["country"]."_".$array["default_ca"]["countryName_value"];
	
		$country_code="US";
		$contryname="Delaware";
		$locality="Wilmington";
		$organizationalUnitName="Artica Web Proxy Unit";
		$organizationName="Artica";
		$emailAddress="root@$users->hostname";
		$commonName=$users->hostname;
	
	
	
		if(preg_match("#(.+?)_(.+?)$#",$cc,$re)){
			$contryname=$re[1];
			$country_code=$re[2];
		}
		if($array["server_policy"]["localityName"]<>null){$locality=$array["server_policy"]["localityName"];}
		if($array["server_policy"]["organizationalUnitName"]<>null){$organizationalUnitName=$array["server_policy"]["organizationalUnitName"];}
		if($array["server_policy"]["emailAddress"]<>null){$emailAddress=$array["server_policy"]["emailAddress"];}
		if($array["server_policy"]["organizationName"]<>null){$organizationName=$array["server_policy"]["organizationName"];}
		if($array["server_policy"]["commonName"]<>null){$commonName=$array["server_policy"]["commonName"];}
	
		@mkdir("/etc/nginx/certificates/new",0666,true);
	
		$conf[]="[ca]";
		$conf[]="default_ca=default_db";
		$conf[]="unique_subject=no";
		$conf[]="";
		$conf[]="[default_db]";
		$conf[]="dir=.";
		$conf[]="certs=.";
		$conf[]="new_certs_dir=/etc/nginx/certificates/new";
		$conf[]="database= /etc/nginx/certificates/ca.index";
		$conf[]="serial = /etc/nginx/certificates/ca.serial";
		$conf[]="RANDFILE=.rnd";
		$conf[]="certificate=/etc/nginx/certificates/key.pem";
		$conf[]="private_key=/etc/nginx/certificates/ca.key";
		$conf[]="default_days= 730";
		$conf[]="default_crl_days=30";
		$conf[]="default_md=md5";
		$conf[]="preserve=no";
		$conf[]="name_opt=ca_default";
		$conf[]="cert_opt=ca_default";
		$conf[]="unique_subject=no";
		$conf[]="policy=policy_match";
		$conf[]="";
		$conf[]="[server_policy]";
		$conf[]="countryName=supplied";
		$conf[]="stateOrProvinceName=supplied";
		$conf[]="localityName=supplied";
		$conf[]="organizationName=supplied";
		$conf[]="organizationalUnitName=supplied";
		$conf[]="commonName=supplied";
		$conf[]="emailAddress=supplied";
		$conf[]="";
		$conf[]="[server_cert]";
		$conf[]="subjectKeyIdentifier=hash";
		$conf[]="authorityKeyIdentifier=keyid:always";
		$conf[]="extendedKeyUsage=serverAuth,clientAuth,msSGC,nsSGC";
		$conf[]="basicConstraints= critical,CA:false";
		$conf[]="";
		$conf[]="[user_policy]";
		$conf[]="commonName=supplied";
		$conf[]="emailAddress=supplied";
		$conf[]="";
		$conf[]="[user_cert]";
		$conf[]="subjectAltName=email:copy";
		$conf[]="basicConstraints= critical,CA:false";
		$conf[]="authorityKeyIdentifier=keyid:always";
		$conf[]="extendedKeyUsage=clientAuth,emailProtection";
		$conf[]="";
		$conf[]="[req]";
		$conf[]="default_bits=1024";
		$conf[]="default_keyfile=ca.key";
		$conf[]="distinguished_name=default_ca";
		$conf[]="x509_extensions=extensions";
		$conf[]="string_mask=nombstr";
		$conf[]="req_extensions=req_extensions";
		$conf[]="input_password=secret";
		$conf[]="output_password=secret";
		$conf[]="";
		$conf[]="[default_ca]";
		$conf[]="countryName=Country Code";
		$conf[]="countryName_value=$country_code";
		$conf[]="countryName_min=2";
		$conf[]="countryName_max=2";
		$conf[]="stateOrProvinceName=State Name";
		$conf[]="stateOrProvinceName_value=$contryname";
		$conf[]="localityName=Locality Name";
		$conf[]="localityName_value=$locality";
		$conf[]="organizationName=Organization Name";
		$conf[]="organizationName_value=$organizationName";
		$conf[]="organizationalUnitName=Organizational Unit Name";
		$conf[]="organizationalUnitName_value=$organizationalUnitName";
		$conf[]="commonName=Common Name";
		$conf[]="commonName_value=$commonName";
		$conf[]="commonName_max=".strlen($commonName);
		$conf[]="emailAddress=Email Address";
		$conf[]="emailAddress_value=$emailAddress";
		$conf[]="emailAddress_max=".strlen($emailAddress);
		$conf[]="unique_subject=no";
		$conf[]="";
		$conf[]="[extensions]";
		$conf[]="subjectKeyIdentifier=hash";
		$conf[]="authorityKeyIdentifier=keyid:always";
		$conf[]="basicConstraints=critical,CA:false";
		$conf[]="";
		$conf[]="[req_extensions]";
		$conf[]="nsCertType=objsign,email,server";
		$conf[]="";
		$conf[]="[CA_default]";
		$conf[]="policy=policy_match";
		$conf[]="";
		$conf[]="[policy_match]";
		$conf[]="countryName=match";
		$conf[]="stateOrProvinceName=match";
		$conf[]="organizationName=match";
		$conf[]="organizationalUnitName=optional";
		$conf[]="commonName=match";
		$conf[]="emailAddress=optional";
		$conf[]="";
		$conf[]="[policy_anything]";
		$conf[]="countryName=optional";
		$conf[]="stateOrProvinceName=optional";
		$conf[]="localityName=optional";
		$conf[]="organizationName=optional";
		$conf[]="organizationalUnitName=optional";
		$conf[]="commonName=optional";
		$conf[]="emailAddress=optional";
		$conf[]="";
		$conf[]="[v3_ca]";
		$conf[]="subjectKeyIdentifier=hash";
		$conf[]="authorityKeyIdentifier=keyid:always,issuer:always";
		$conf[]="basicConstraints=critical,CA:false";
		
		@mkdir(dirname($this->certifcate_conf),0666,true);
		file_put_contents("$this->certifcate_conf",@implode("\n",$conf));
	}	
	
	
}


