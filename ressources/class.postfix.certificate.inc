<?php
class postfix_certificate{
	private $CommonName=null;
	private $ssl_path="/etc/ssl/certs/postfix";
	public $crt_content=null;
	private $srca_content=null;
	public $privkey_content=null;
	private $SquidCert=null;
	private $Squidkey=null;
	private $clientkey=null;
	private $clientcert=null;
    private $bundle=null;
	private $ssl_client_certificate=0;
	private $RootCa=null;
	private $PrefixOutput;
	private $AS_ROOT=false;

    public function __construct($CommonName=null){
        if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
        if($CommonName<>null){$this->CommonName=$CommonName;}
        $this->PrefixOutput="Starting......: ".date("H:i:s")." [INIT]: Postfix \"CERT\"";
        if($GLOBALS["posix_getuid"]==0){$this->AS_ROOT=true;}
    }

	public function build():bool{
		if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] $this->CommonName\n";}
		if($this->CommonName==null){return $this->build_default();}
		$this->load_certificate();
        $certificate_subdir=str_replace("*", "_ALL_", $this->CommonName);
        if(preg_match("#^SUB:([0-9]+)#",$this->CommonName,$re)){
            $SUBID=$re[1];
            $certificate_subdir="subcert-id-$SUBID";
        }


		
		$Directory="$this->ssl_path/$certificate_subdir";
		@mkdir($Directory,0755,true);
		$PRIVATE_KEY=$this->privkey_content;
		$CERTIFICATE=$this->crt_content;
		$ROOT=$this->RootCa;
		
		if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] $Directory/server.crt ".strlen($CERTIFICATE)." bytes\n";}
        if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] $Directory/server.key ".strlen($PRIVATE_KEY)." bytes\n";}

        @file_put_contents("$Directory/server.crt", "$CERTIFICATE");
		@file_put_contents("$Directory/server.key", "$PRIVATE_KEY");

        $cmd["smtpd_tls_key_file"]="$Directory/server.key";
        $cmd["smtpd_tls_cert_file"]="$Directory/server.crt";

        if(strlen($this->RootCa)>50) {
            @file_put_contents("$Directory/ca.csr", "$ROOT");
            $cmd["smtpd_tls_CAfile"]="$Directory/ca.csr";
        }else{
            $cmd["smtpd_tls_CAfile"]="";
        }
        $cmd["smtpd_tls_exclude_ciphers"]= "EXP, MEDIUM, LOW, DES, 3DES, SSLv2";


        foreach ($cmd as $num=>$ligne){
            $this->postconf($num,$ligne);
				
		}
        return true;
		
	}
	
	public function load_certificate():bool{

        if(preg_match("#^SUB:([0-9]+)#",$this->CommonName,$re)){
            $SUBID=$re[1];
            list($certificate,$privkey,$ca)=$this->GetMainSubCertificate($SUBID);
        }else{
            list($certificate,$privkey,$ca)=$this->GetMainCertificate($this->CommonName);
        }

        $this->privkey_content=$privkey;
        $this->crt_content=$certificate;
        $this->RootCa=$ca;

        if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] Private key: ".strlen($this->privkey_content)." bytes\n";}
		if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] Certificate: ".strlen($this->crt_content)." bytes\n";}
        if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] CA.........: ".strlen($this->RootCa)." bytes\n";}
        return true;
		
	}

    private function GetMainSubCertificate($SUBID):array{
        $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
        $sql="SELECT * FROM subcertificates WHERE ID=$SUBID";


        $ligne=$q->mysqli_fetch_array($sql);
        if(!isset($ligne["bundle"])){$ligne["bundle"]="";}
        $crt_content=str_replace("\\n","\n",base64_decode($ligne["crt"]));
        $privkey_content=str_replace("\\n","\n",base64_decode($ligne["srca"]));
        $certid=intval($ligne["certid"]);
        list($srca_content,$MainKey,$CA)=$this->GetMainCertificate("",$certid);
        return array($crt_content,$privkey_content,$srca_content);

    }

    private function GetMainCertificate($CommonName=null,$certid=0):array{
        $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
        $sql="SELECT `UsePrivKeyCrt`,`crt`,`csr`,`srca`,`clientkey`,`clientcert`,`privkey`,`SquidCert`,`Squidkey`,`bundle`
		FROM sslcertificates WHERE CommonName='$CommonName'";
        if($certid>0){
            $sql="SELECT `UsePrivKeyCrt`,`crt`,`srca`,`clientkey`,`clientcert`,`privkey`,`SquidCert`,`Squidkey`,`bundle`
		FROM sslcertificates WHERE ID=$certid";
        }

        $ligne=$q->mysqli_fetch_array($sql);
        $srca_content="";
        $bundle="";

        if(!isset($ligne["bundle"])){$ligne["bundle"]="";}
        $UsePrivKeyCrt=intval($ligne["UsePrivKeyCrt"]);
        $crt_content=str_replace("\\n","\n",$ligne["crt"]);

        if (strlen($ligne["srca"])>50){
            $srca_content=str_replace("\\n","\n",$ligne["srca"]);
        }
        if(strlen($ligne["bundle"])>50){
            $bundle=str_replace("\\n","\n",$ligne["bundle"]);
        }

        $privkey_content=str_replace("\\n","\n",$ligne["privkey"]);

        if($UsePrivKeyCrt==0){
            if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] Self-Signed certificate\n";}
            $bundle="";
            $srca_content="";
            $privkey_content=str_replace("\\n","\n",$ligne["Squidkey"]);
            $crt_content=str_replace("\\n","\n",$ligne["SquidCert"]);
        }

        if(strlen($bundle)>50){
            $crt_content=$crt_content."\n$bundle";
        }

        return array($crt_content,$privkey_content,$srca_content);



    }
	
	
	private function build_default():bool{
		if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] Build default certificate\n";}
		if(!is_file("$this->ssl_path/ca.key")){
			@chmod("/usr/share/artica-postfix/bin/artica-install", 0755);
			shell_exec("/usr/share/artica-postfix/exec.postfix.default-certificate.php");
		}
		
		$cmd["smtpd_tls_key_file"]="/etc/ssl/certs/postfix/ca.key";
		$cmd["smtpd_tls_cert_file"]="/etc/ssl/certs/postfix/ca.crt";
		//$cmd["smtpd_tls_CAfile"]="/etc/ssl/certs/postfix/ca.csr";
        foreach ($cmd as $num=>$ligne){
			$this->postconf($num,$ligne);
			
		}
		return true;
	}

    function postconf($key,$value=null):bool{
        $unix=new unix();
        $unix->POSTCONF_SET($key,$value,$GLOBALS["POSTFIX_INSTANCE_ID"]);
        return true;
    }
	
	
}

