<?php
class nginx_certificate{
    private $ssl_path="/etc/nginx/certificates";
    private $certificate=null;
    private $UsePrivKeyCrt=0;
    private $UseGodaddy=0;
    public $letsencript=0;
    private $crt_content=null;
    private $csr_content=null;
    private $srca_content=null;
    private $privkey_content=null;
    private $bundle=null;
    private $SquidCert=null;
    private $Squidkey=null;
    private $clientkey;
    private $clientcert=null;
    private $ssl_client_certificate;
    public $ssl_certificate_path=null;
    public $ssl_certificate_key=null;
    private $CertPassword=null;
    private $RootCa=null;
    private $LOGS=array();


    function __construct($certificate=null,$ssl_client_certificate=0){
        $this->ssl_client_certificate=intval($ssl_client_certificate);
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
        $config="/etc/nginx/certificates/openssl.conf";

        $cmd="$openssl genrsa -des3 -passout pass:$password -out $this->ssl_path/default/privkey.pem 2048";
        if($GLOBALS["AS_ROOT"]){echo $cmd."\n";}
        system($cmd);

        $cmd="$openssl req -new -x509 -nodes -passin pass:$password -key $this->ssl_path/default/privkey.pem -batch -config $config -out $this->ssl_path/default/cacert.pem -days $CertificateMaxDays";
        if($GLOBALS["AS_ROOT"]){echo $cmd."\n";}
        system($cmd);
        system("/bin/cp $this->ssl_path/default/privkey.pem $this->ssl_path/default/privkey.cp.pem");
        system("$openssl rsa -passin pass:$password -in $this->ssl_path/default/privkey.cp.pem -out $this->ssl_path/default/privkey.pem");
    }
    public function client_certificate():string{
        $q= new lib_sqlite("/home/artica/SQLITE/nginx.db");
        $ligne=$q->mysqli_fetch_array("SELECT CertificateName,ca_crt FROM nginx_servers_certs WHERE ID='$this->certificate'");
        $CertificateName=$ligne["CertificateName"];
        $ssl_client_certificate[]="# Use ssl_client_certificate with <$this->certificate> / $CertificateName [".__CLASS__."/".__LINE__."]";
        if(intval($this->certificate)==0){
            $ssl_client_certificate[]="# Bad ID! [".__CLASS__."/".__LINE__."]";
            return @implode("\n",$ssl_client_certificate);
        }

        $CA=base64_decode($ligne["ca_crt"]);
        if(strlen($CA)<20){
            $ssl_client_certificate[]="# Bad length! [".__CLASS__."/".__LINE__."]";
            return @implode("\n",$ssl_client_certificate);
        }
        $certificate_subdir=str_replace("*", "_ALL_", $CertificateName);
        $Directory="$this->ssl_path/$certificate_subdir";
        @mkdir($Directory,0755,true);
        @file_put_contents("$Directory/ca.crt", $CA);
        $ssl_client_certificate[]="\tssl_client_certificate \"$Directory/ca.crt\";";
        $ssl_client_certificate[]="\tssl_verify_client\ton;";
        $ssl_client_certificate[]="\tssl_verify_depth 1;";
        return @implode("\n",$ssl_client_certificate);
    }
    private function load_certificate():bool{
        $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
        $SUBID=0;
        $sql="SELECT * FROM sslcertificates WHERE CommonName='$this->certificate'";

        if(preg_match("#SUB:([0-9]+)#",$this->certificate,$re)){
            $SUBID=$re[1];
            $sql="SELECT * FROM subcertificates WHERE ID=$SUBID";
        }
        if($GLOBALS["VERBOSE"]){
            echo "Using SQL: \"$sql\"\n";
        }
        $ligne=$q->mysqli_fetch_array($sql);

        $this->UsePrivKeyCrt=$ligne["UsePrivKeyCrt"];
        $this->crt_content=str_replace("\\n","\n",$ligne["crt"]);
        $this->csr_content=str_replace("\\n","\n",$ligne["csr"]);
        $this->srca_content=str_replace("\\n","\n",$ligne["srca"]);
        $this->bundle=str_replace("\\n","\n",$ligne["bundle"]);
        $this->RootCa=str_replace("\\n","\n",$ligne["srca"]);
        if(isset($ligne["privkey"])) {
            $this->privkey_content = str_replace("\\n", "\n", $ligne["privkey"]);
        }
        if(isset($ligne["SquidCert"])) {
            $this->SquidCert = str_replace("\\n", "\n", $ligne["SquidCert"]);
        }
        if(isset($ligne["Squidkey"])) {
            $this->Squidkey = str_replace("\\n", "\n", $ligne["Squidkey"]);
        }
        if($SUBID>0){
            $this->crt_content=base64_decode($this->crt_content);
            $this->crt_content=str_replace("\\n","\n",$this->crt_content);
            $this->srca_content=base64_decode($this->srca_content);
            $this->srca_content=str_replace("\\n","\n",$this->srca_content);
            $this->RootCa=$this->srca_content;
            $this->SquidCert=$this->crt_content;
            $this->Squidkey=$this->srca_content;
            $this->privkey_content=$this->srca_content;

        }
        if(isset($ligne["clientkey"])) {
            $this->clientkey = $ligne["clientkey"];
        }
        if(isset($ligne["clientcert"])) {
            $this->clientcert = $ligne["clientcert"];
        }
        if(isset($ligne["bundle"])) {
            $this->bundle = str_replace("\\n", "\n", $ligne["bundle"]);
        }

        if(isset($ligne["UseGodaddy"])) {
            $this->UseGodaddy = intval($ligne["UseGodaddy"]);
        }
        if(isset($ligne["CertPassword"])) {
            $this->CertPassword = trim($ligne["CertPassword"]);
        }
        $this->LOGS[]="# srca_content       = ".strlen($this->srca_content);
        $this->LOGS[]="# privkey_content    = ".strlen($this->privkey_content);
        $this->LOGS[]="# bundle             = ".strlen($this->bundle);
        //ssl_client_certificate
        return true;
    }
    private function is_default():bool{
        if(!is_file("$this->ssl_path/default/cacert.pem")){return false;}
        if(!is_file("$this->ssl_path/default/privkey.pem")){return false;}
        return true;
    }

    public function GetConf():string{
        $ssl_client_certificate=array();
        $f=array();
        if($this->certificate==null){
            $f[]="# certificate == null -> Use default";
            if(!$this->is_default()){$this->certificate_default();}
            $f[]="\tssl_certificate \"$this->ssl_path/default/cacert.pem\";";
            $f[]="\tssl_certificate_key \"$this->ssl_path/default/privkey.pem\";";
            $this->ssl_certificate_path="$this->ssl_path/default/cacert.pem";
            $this->ssl_certificate_key="$this->ssl_path/default/privkey.pem";
            return @implode("\n", $f);
        }

        $f[]="# certificate $this->certificate";
        $this->load_certificate();
        $certificate_subdir=str_replace("*", "_ALL_", $this->certificate);
        $certificate_subdir=str_replace(":", "_",$certificate_subdir);

        $Directory="$this->ssl_path/$certificate_subdir";
        @mkdir($Directory,0755,true);

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [$this->certificate]: Use Private Key: $this->UsePrivKeyCrt\n";}
        @file_put_contents("$Directory/certificate.csr", $this->csr_content);
        @file_put_contents("$Directory/CAkey.pem", $this->srca_content);

        if($this->ssl_client_certificate==1){
            $ssl_client_certificate[]="# Use ssl_client_certificate TRUE [".__CLASS__."/".__LINE__."]";
            if($this->RootCa<>null){
                @file_put_contents("$Directory/ca.crt", $this->RootCa);
                $ssl_client_certificate[]="\tssl_client_certificate $Directory/ca.crt;";
                $ssl_client_certificate[]="\tssl_verify_client\toptional_no_ca;";
                //$ssl_client_certificate[]="\tssl_verify_depth 3;";
            }else{
                $ssl_client_certificate[]="# Root certificate no saved [".__CLASS__."/".__LINE__."]";
            }

        }else{
            $ssl_client_certificate[]="# Use ssl_client_certificate FALSE [".__CLASS__."/".__LINE__."]";
        }

        if($this->letsencript==1){
            if(is_file("/etc/letsencrypt/live/$this->certificate/fullchain.pem")){
                $f[]="# Use /etc/letsencrypt/live/$this->certificate";
                $f[]="\tssl_certificate /etc/letsencrypt/live/$this->certificate/fullchain.pem;";
                $f[]="\tssl_certificate_key /etc/letsencrypt/live/$this->certificate/privkey.pem;";

                $this->ssl_certificate_path="/etc/letsencrypt/live/$this->certificate/fullchain.pem";
                $this->ssl_certificate_key="/etc/letsencrypt/live/$this->certificate/privkey.pem";
                return @implode("\n", $f);
            }

        }

        $f[]="# Use Godaddy: $this->UseGodaddy";
        $f[]="# Bundle length: ".strlen($this->bundle);

        if($this->UseGodaddy==1){
            $Directory="$Directory/Godaddy";
            @mkdir($Directory,0755,true);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [$this->certificate]: Use Godaddy Certificate\n";}
            $chain=$this->crt_content."\n".$this->bundle;
            $unix=new unix();
            $openssl=$unix->find_program("openssl");
            if(!is_file("/etc/openssl/private-key/privkey.key")){
                @mkdir("/etc/openssl/private-key",0755,true);
                shell_exec("$openssl genrsa -out /etc/openssl/private-key/privkey.key 2048");
            }

            @copy("/etc/openssl/private-key/privkey.key","$Directory/private_key.key");
            @file_put_contents("$Directory/certificate.pem", $chain);
            $f[]="# Use Godaddy key [".__CLASS__."/".__LINE__."]";
            $this->ssl_certificate_path="$Directory/certificate.pem";
            $this->ssl_certificate_key="$Directory/private_key.key";
            $f[]="\tssl_certificate \"$Directory/certificate.pem\";";
            $f[]="\tssl_certificate_key \"$Directory/private_key.key\";";
            return @implode("\n", $f);
        }

        if(strlen($this->bundle)>500){
            $f[]="# Add Bundle Inside CRT";
            $this->crt_content="$this->crt_content\n$this->bundle";
            $this->SquidCert="$this->SquidCert\n$this->bundle";
        }

        if($this->UsePrivKeyCrt==1){
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [$this->certificate]: Use crt_content\n";}
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [$this->certificate]: Use privkey_content\n";}


            if(strlen($this->privkey_content)<20){
                $f[]="# Private key field is empty, using the server one.[".__CLASS__."/".__LINE__."]";
                $this->privkey_content=@file_get_contents("/etc/openssl/private-key/privkey.key");
            }
            $this->crt_content=str_replace("\\n", "\n", $this->crt_content);
            $this->privkey_content=str_replace("\\n", "\n", $this->privkey_content);
            @file_put_contents("$Directory/certificate.pem", $this->crt_content);
            @file_put_contents("$Directory/private_key.key", $this->privkey_content);
            $f[]="# Use Private key [".__CLASS__."/".__LINE__."]";
            if(strlen($this->CertPassword)>2){
                $f[]="\tssl_password_file \"$Directory/certificate.pass\";";
                @file_put_contents("$Directory/certificate.pass", $this->CertPassword);
            }
            $this->ssl_certificate_path="$Directory/certificate.pem";
            $this->ssl_certificate_key="$Directory/private_key.key";

            $f[]="\tssl_certificate \"$Directory/certificate.pem\";";
            $f[]="\tssl_certificate_key \"$Directory/private_key.key\";";
            return @implode("\n", $f);
        }

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [$this->certificate]: Use SquidCert\n";}
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [$this->certificate]: Use Squidkey\n";}

        $this->SquidCert=str_replace("\\n", "\n", $this->SquidCert);
        $this->Squidkey=str_replace("\\n", "\n", $this->Squidkey);
        $this->csr_content=str_replace("\\n", "\n", $this->csr_content);
        @file_put_contents("$Directory/certificate.pem",$this->SquidCert);
        @file_put_contents("$Directory/private_key.key", $this->Squidkey);
        @file_put_contents("$Directory/certificate.csr", $this->csr_content);

        if($GLOBALS["VERBOSE"]){
            echo "Saving Certificate: \"$Directory/certificate.csr\"\n";
            echo "Saving Private key: \"$Directory/private_key.key\"\n";
        }


        $f[]="# Use certificate center key [".__CLASS__."/".__LINE__."]";
        $f[]="\tssl_certificate $Directory/certificate.pem;";
        $this->ssl_certificate_path="$Directory/certificate.pem";
        $this->ssl_certificate_key="$Directory/private_key.key";
        $f[]="\tssl_certificate_key $Directory/private_key.key;";
        $f[]="# ssl_client_certificate = ". count($ssl_client_certificate)." lines [".__LINE__."]";
        $f[]=@implode("\n", $ssl_client_certificate);
        return @implode("\n", $f);


    }
    private function certificate_conf(){
        include_once(dirname(__FILE__).'/class.ssl.certificate.inc');
        $ssl=new ssl_certificate();
        $array=$ssl->array_ssl;
        if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
        $users=$GLOBALS["CLASS_USERS"];
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
        @mkdir("/etc/nginx/certificates",0666,true);
        file_put_contents("/etc/nginx/certificates/openssl.conf",@implode("\n",$conf));
    }


}
