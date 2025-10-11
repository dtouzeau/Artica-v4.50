<?php
class cherrypy_certificate{
    private $ssl_path="/etc/cherrypy/certificates";
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
    public $ssl_certificate_path=null;
    private $CertPassword=null;
    private $RootCa=null;

    function __construct($certificate=null,$ApplicationName=null){
        if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
        if($certificate<>null){$this->certificate=$certificate;}
        if($ApplicationName<>null){$this->ssl_path="$this->ssl_path/$ApplicationName";}
        if(!is_dir($this->ssl_path)){@mkdir($this->ssl_path,0755,true);}
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
        $this->clientkey=$ligne["clientkey"];
        $this->clientcert=$ligne["clientkey"];
        $this->bundle=str_replace("\\n","\n",$ligne["bundle"]);
        $this->RootCa=str_replace("\\n","\n",$ligne["srca"]);
        $this->UseGodaddy=intval($ligne["UseGodaddy"]);
        $this->CertPassword=trim($ligne["CertPassword"]);

    }

    private function _out($text):bool{
        if(!function_exists("_out")){return false;}
        _out($text);
        return true;
    }

    public function build():bool{
        $f=array();
        if($this->certificate==null){return false;}
        $this->load_certificate();
        $this->_out("[$this->certificate]: Use Private Key: $this->UsePrivKeyCrt");

        if(is_file("$this->ssl_path/certificate.csr")){@unlink("$this->ssl_path/certificate.csr");}
        if(is_file("$this->ssl_path/CAkey.pem")){@unlink("$this->ssl_path/CAkey.pem");}
        if(is_file("$this->ssl_path/certificate.pem")){@unlink("$this->ssl_path/certificate.pem");}
        if(is_file("$this->ssl_path/private_key.key")){@unlink("$this->ssl_path/private_key.key");}

        @file_put_contents("$this->ssl_path/certificate.csr", $this->csr_content);
        @file_put_contents("$this->ssl_path/CAkey.pem", $this->srca_content);



        if($this->letsencript==1){
            if(is_file("/etc/letsencrypt/live/$this->certificate/fullchain.pem")){
                $this->_out("[$this->certificate]: Use letsencrypt...");
                @copy("/etc/letsencrypt/live/$this->certificate/fullchain.pem","$this->ssl_path/certificate.pem");
                @copy("/etc/letsencrypt/live/$this->certificate/privkey.pem","$this->ssl_path/private_key.key");
                return true;
            }
            return false;
        }

        if($this->UsePrivKeyCrt==1){
            $this->_out("[$this->certificate]: Use crt_content");
            $this->_out("[$this->certificate]: Use privkey_content");
            if(strlen($this->bundle)>20){
                $this->crt_content="$this->crt_content\n$this->bundle";
            }

            if(strlen($this->privkey_content)<20){
                $f[]="# Private key field is empty, using the server one.[".__CLASS__."/".__LINE__."]";
                $this->privkey_content=@file_get_contents("/etc/openssl/private-key/privkey.key");
            }
            $this->crt_content=str_replace("\\n", "\n", $this->crt_content);
            $this->privkey_content=str_replace("\\n", "\n", $this->privkey_content);
            @file_put_contents("$this->ssl_path/certificate.pem", $this->crt_content);
            @file_put_contents("$this->ssl_path/private_key.key", $this->privkey_content);
            return true;
        }

        $this->_out("[$this->certificate]: Use SquidCert");
        $this->_out("[$this->certificate]: Use Squidkey");

        $this->SquidCert=str_replace("\\n", "\n", $this->SquidCert);
        $this->Squidkey=str_replace("\\n", "\n", $this->Squidkey);
        $this->csr_content=str_replace("\\n", "\n", $this->csr_content);

        $this->_out("[$this->certificate]: Use $this->ssl_path directory");

        @file_put_contents("$this->ssl_path/certificate.pem",$this->SquidCert);
        @file_put_contents("$this->ssl_path/private_key.key", $this->Squidkey);
        @file_put_contents("$this->ssl_path/certificate.csr", $this->csr_content);
        return true;


    }

}
