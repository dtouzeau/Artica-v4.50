<?php

class squid_certificate{
    public $BuildSquidCertificateCRTD_LOGS=array();
    public $key_out;
    private $EnableSquidSSLCRTD=1;
    private $sslcrtd_program=null;
    private $ASROOT=true;
    private $SquidUrgency=0;
    private $https_port=false;
    private $subcertificate=0;
    private $AuthSSL=false;
    public $LOGS=array();

    function __construct(){
        if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
        $this->SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
    }

    public function SetSSLPort($subcertificate=0){
        $this->https_port=true;
        $this->subcertificate=$subcertificate;
    }

    public function SetSSLClients(){
        $this->AuthSSL=true;
    }

    private function sslcrtd_program_path(){
        if($this->SquidUrgency==1){return null;}
        if(isset($GLOBALS["sslcrtd_program"])){return $GLOBALS["sslcrtd_program"];}
        if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
        $ssl_crtd = $unix->squid_locate_generic_bin("security_file_certgen");
        $this->BuildSquidCertificateCRTD_LOGS[]="# sslcrtd_program_path: security_file_certgen = '$ssl_crtd'";
        $GLOBALS["sslcrtd_program"]=$ssl_crtd;
        return  $GLOBALS["sslcrtd_program"];
    }

    private function build_secure_proxy(){
        $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
        $sql="SELECT certid,srca,crt FROM subcertificates WHERE ID=$this->subcertificate";
        @mkdir("/etc/squid3",0755,true);
        @mkdir("/etc/squid3/ssl",0755,true);
        @mkdir("/etc/squid3/ssl/secure",0755,true);

        $ligne=$q->mysqli_fetch_array($sql);
        $certid=intval($ligne["certid"]);
        $certificateData=base64_decode($ligne["crt"]);
        $KeyData=base64_decode($ligne["srca"]);



        $certificatepath="/etc/squid3/ssl/secure/servercert{$this->subcertificate}.pem";
        $keypath="/etc/squid3/ssl/secure/serverkey{$this->subcertificate}.pem";
        $CaPath="/etc/squid3/ssl/secure/serverkey/ca{$certid}.pem";
        @file_put_contents($certificatepath,$certificateData);
        @file_put_contents($keypath,$KeyData);

        //$certid


        $sql="SELECT privkey,crt FROM sslcertificates WHERE ID='$certid'";
        $ligne=$q->mysqli_fetch_array($sql);
        @file_put_contents($CaPath,$ligne["crt"]);

        $cmds[]="cert=$certificatepath";
        $cmds[]="key=$keypath";
        if($this->AuthSSL) {
            $cmds[] = "clientca=$CaPath";
            $cmds[] = "sslcontext=id";
        }
        return @implode(" ",$cmds);


    }

    private function IfCertisCA($certdata){
        $array=openssl_x509_parse($certdata);
        if(!isset($array["extensions"])){return false;}
        if(!isset($array["extensions"]["basicConstraints"])){return false;}

        if(preg_match("#CA:TRUE#i",$array["extensions"]["basicConstraints"])){
            writelogs("{$array["extensions"]["basicConstraints"]} Matches CA:TRUE",__FUNCTION__,__FILE__,__LINE__);
            return true;
        }
        return false;
    }

    public function BuildSquidCertificate($commname=null){
        $this->key_out=null;
        if($this->https_port){
            $commname=null;
            $GLOBALS["BuildSquidCertificate"][]="Building Secure Proxy [".__LINE__."]";
            return $this->build_secure_proxy();
        }

        $this->sslcrtd_program=$this->sslcrtd_program_path();
        $GLOBALS["BuildSquidCertificate"]=array();
        @mkdir("/etc/squid3",0755,true);
        @mkdir("/etc/squid3/ssl",0755,true);
        @mkdir("/usr/share/artica-postfix/ressources/squid",0755,true);
        if($commname==null){
            $unix=new unix();
            $commname=$unix->hostname_g();
        }

        $GLOBALS["BuildSquidCertificate"][]="`$commname` [".__LINE__."]";

        if(strlen($this->sslcrtd_program)==0){$this->EnableSquidSSLCRTD=0;}

        $this->BuildSquidCertificateCRTD_LOGS[]="# EnableSquidSSLCRTD = $this->EnableSquidSSLCRTD [".__LINE__."]";
        if($this->EnableSquidSSLCRTD==1){
            $GLOBALS["BuildSquidCertificate"][]="CommonName: `$commname` -> BuildSquidCertificateCRTD($commname)";
            $line= $this->BuildSquidCertificateCRTD($commname);
            $GLOBALS["BuildSquidCertificate"][]="Return: $line [".__LINE__."]";
            $this->BuildSquidCertificateCRTD_LOGS[]="# Return: $line  [".__LINE__."]";
            return $line;
        }


        $this->BuildSquidCertificateCRTD_LOGS[]="# Passed -> `$commname` -> SaveCertificate($commname) [".__LINE__."]";
        $GLOBALS["BuildSquidCertificate"][]="CommonName: `$commname` -> SaveCertificate($commname)";
        return $this->SaveCertificate($commname);

    }



    private function  BuildSquidCertificateCRTD($commname){
        $ketMEM="BuildSquidCertificateCRTD$commname";
        $this->BuildSquidCertificateCRTD_LOGS=null;
        if(!$this->https_port) {
            if (isset($GLOBALS[$ketMEM])) {
                return $GLOBALS[$ketMEM];
            }
        }

        if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
        $openssl=$unix->find_program("openssl");

        $certificate_filename=md5($commname);
        $keyout="/etc/squid3/ssl/$certificate_filename.dyn";
        $this->key_out=$keyout;

        $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
        $sql="SELECT `UsePrivKeyCrt`,`SquidCert`,`crt`,`privkey`,`Squidkey`,`bundle` FROM sslcertificates WHERE CommonName='$commname'";
        $ligne=$q->mysqli_fetch_array($sql);

        if(!isset($ligne["UsePrivKeyCrt"])){$ligne["UsePrivKeyCrt"]=0;}
        if(!isset($ligne["bundle"])){$ligne["bundle"]=null;}
        if(!isset($ligne["privkey"])){$ligne["privkey"]=null;}
        if(!isset($ligne["crt"])){$ligne["crt"]=null;}


        if(!$q->ok){
            $GLOBALS["BuildSquidCertificate"][]="# CommonName = $commname SQL ERROR (".__LINE__.")";
            $this->BuildSquidCertificateCRTD_LOGS[]="# MySQL ERROR!!! [".__LINE__."]";
            $GLOBALS["BuildSquidCertificate"][]="#". str_replace("\n", " ", $q->mysql_error);
        }
        $this->BuildSquidCertificateCRTD_LOGS[]="# Key out = $keyout  [".__LINE__."]";
        $this->BuildSquidCertificateCRTD_LOGS[]="# CommonName = $commname UsePrivKeyCrt={$ligne["UsePrivKeyCrt"]} [".__LINE__."]";

        $ligne["privkey"]=trim(str_replace("\\n", "\n", $ligne["privkey"]));
        $ligne["crt"]=trim(str_replace("\\n", "\n", $ligne["crt"]));
        $ligne["bundle"]=trim(str_replace("\\n", "\n", $ligne["bundle"]));
        $CertificateData=null;

        if(intval($ligne["UsePrivKeyCrt"])==1){
            echo "Starting......: ".date("H:i:s")." [SSL]: CommonName: `$commname`, UsePrivKeyCrt = 1\n";
            $strlen=strlen($ligne["crt"]);
            $strlen2=strlen($ligne["privkey"]);
            $strlen3=strlen($ligne["bundle"]);
            $this->BuildSquidCertificateCRTD_LOGS[]="# CRT = $strlen Bytes";
            $this->BuildSquidCertificateCRTD_LOGS[]="# privkey = $strlen2 Bytes";
            $this->BuildSquidCertificateCRTD_LOGS[]="# bundle = $strlen3 Bytes";
            if($strlen<20){$ligne["UsePrivKeyCrt"]=0;}
            if($strlen2<20){$ligne["UsePrivKeyCrt"]=0;}
            if($strlen3>20){
                $CertificateData=$ligne["crt"];
                @file_put_contents("$keyout", "{$ligne["crt"]}\n{$ligne["bundle"]}\n{$ligne["privkey"]}");
            }else{
                @file_put_contents("$keyout", "{$ligne["crt"]}\n{$ligne["privkey"]}\n");
            }


        }

        if(intval($ligne["UsePrivKeyCrt"])==0){
            $GLOBALS["BuildSquidCertificate"][]="UsePrivKeyCrt = 0 [".__LINE__."]";
            $sql="SELECT `UsePrivKeyCrt`,`easyrsa`,`SquidCert`,`Squidkey`,`crt`,`privkey`,`srca` FROM sslcertificates WHERE CommonName='$commname'";
            if($GLOBALS["VERBOSE"]){echo  "Starting......: ".date("H:i:s")." [SSL]: $sql\n";}
            $ligne=$q->mysqli_fetch_array($sql);
            if(!$q->ok){
                $GLOBALS["BuildSquidCertificate"][]="# $sql [error]";
                $GLOBALS["BuildSquidCertificate"][]="#". str_replace("\n", " ", $q->mysql_error);
                echo $q->mysql_error."\n";
            }
            $privatekey=str_replace("\\n", "\n", $ligne["srca"]);
            $SquidCert=$ligne["SquidCert"];
            $strlen=strlen($SquidCert);
            $strlen2=strlen($privatekey);
            $strlen3=strlen($ligne["Squidkey"]);
            if($ligne["easyrsa"]==1){
                echo "Starting......: ".date("H:i:s")." [SSL]: CommonName: `$commname`, used V2 for certificates.\n";
                $strlen2=0;
            }

            $GLOBALS["BuildSquidCertificate"][]="# CommonName = $commname";
            $GLOBALS["BuildSquidCertificate"][]="# SquidCert  = $strlen Bytes";
            $GLOBALS["BuildSquidCertificate"][]="# srca       = $strlen2 Bytes";
            $GLOBALS["BuildSquidCertificate"][]="# Squidkey   = $strlen3 Bytes";


            if($strlen2==0){
                if($strlen3>20){
                    echo "Starting......: ".date("H:i:s")." [SSL]: CommonName: `$commname`, Use Squidkey as KEY\n";
                    $privatekey=str_replace("\\n", "\n", $ligne["Squidkey"]);
                }
            }

            if($SquidCert==null){
                $GLOBALS["BuildSquidCertificate"][]="$commname SquidCert IS NULL in `sslcertificates` table !!? [".__LINE__."]";
                echo "Starting......: ".date("H:i:s")." [SSL] :: $commname SquidCert IS NULL in `sslcertificates` table\n";
                echo "Starting......: ".date("H:i:s")." [SSL] :: $commname Build a default one\n";
                $GLOBALS[$ketMEM]= $this->BuildSquidCertificateCRTD_default();
                return $GLOBALS[$ketMEM];
            }

            $SquidCert=str_replace("\\n", "\n",$SquidCert);
            $GLOBALS["BuildSquidCertificate"][]="$commname -> $keyout [".__LINE__."]";
            $this->BuildSquidCertificateCRTD_LOGS[]="# $keyout (".__LINE__.")";
            if($GLOBALS["VERBOSE"]){echo "* * * * writing $keyout (".__LINE__.")";}
            $CertificateData=$SquidCert;
            $final="$SquidCert\n$privatekey\n";
            $final=str_replace("\n\n","\n",$final);
            @file_put_contents("$keyout", $final);
        }

        @file_put_contents("/var/log/squid/certificates.generation.log",@implode("\n",$this->BuildSquidCertificateCRTD_LOGS));
        $cmd="$openssl x509 -in $keyout -outform DER -out /usr/share/artica-postfix/ressources/squid/certificate.der";
        shell_exec($cmd);


        if(@unlink("/usr/share/artica-postfix/ressources/squid/hotspot-$certificate_filename.der")){
            @copy("/usr/share/artica-postfix/ressources/squid/certificate.der", "/usr/share/artica-postfix/ressources/squid/hotspot-$certificate_filename.der");
        }

        if(!is_file("/etc/squid3/ssl/dhparam.pem")){
            system("$openssl dhparam -outform PEM -out /etc/squid3/ssl/dhparam.pem 2048");

        }

        if(!$this->IfCertisCA($CertificateData)){
            $GLOBALS["BuildSquidCertificate"][]="$commname -> Not a Root CA";
            return  "tls-cert=$keyout tls-dh=/etc/squid3/ssl/dhparam.pem";
        }

        if($this->https_port){
            return  "tls-cert=$keyout tls-dh=/etc/squid3/ssl/dhparam.pem";
        }

        $GLOBALS[$ketMEM]=" generate-host-certificates=on dynamic_cert_mem_cache_size=4MB cert=$keyout sslflags=VERIFY_CRL_ALL options=NO_SSLv3,No_Compression tls-dh=/etc/squid3/ssl/dhparam.pem";

        return $GLOBALS[$ketMEM];


    }
    private function BuildSquidCertificateCRTD_default($commname=null){
        if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
        if($commname==null){$commname=$unix->hostname_g();}
        $commname=strtolower($commname);
        $openssl=$unix->find_program("openssl");
        $certificate_filename=md5($commname);

        $keyout="/etc/squid3/ssl/$certificate_filename.dyn";
        if(is_file($keyout)){
            echo "Starting......: ".date("H:i:s")." [SSL]: $keyout OK\n";
            if(!is_file("/usr/share/artica-postfix/ressources/squid/certificate.der")){
                echo "Starting......: ".date("H:i:s")." [SSL]: create certificate.der\n";
                $cmd="$openssl x509 -in $keyout -outform DER -out /usr/share/artica-postfix/ressources/squid/certificate.der";
                echo "Starting......: ".date("H:i:s")." [SSL]: $cmd\n";
                shell_exec($cmd);
                if(!is_file("/usr/share/artica-postfix/ressources/squid/certificate.der")){ echo "Starting......: ".date("H:i:s")." [SSL]: Warning /usr/share/artica-postfix/ressources/squid/certificate.der no such file\n"; }
            }
            if(is_file("/usr/share/artica-postfix/ressources/squid/certificate.der")){
                if(@unlink("/usr/share/artica-postfix/ressources/squid/hotspot-$certificate_filename.der")){
                    @copy("/usr/share/artica-postfix/ressources/squid/certificate.der", "/usr/share/artica-postfix/ressources/squid/hotspot-$certificate_filename.der");
                }
            }

            echo "Starting......: ".date("H:i:s")." [SSL]: Already generated OK\n";
            $this->key_out=$keyout;
            if($this->https_port){
                return  "tls-cert=$keyout tls-dh=/etc/squid3/ssl/dhparam.pem";
            }

            return " generate-host-certificates=on dynamic_cert_mem_cache_size=4MB cert=$keyout sslflags=VERIFY_CRL_ALL options=NO_SSLv2,NO_SSLv3,No_Compression";
        }

        $DefaultSSLParams=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DefaultSSLParams")));
        $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
        $WizardSavedSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));
        if($DefaultSSLParams["countryName"]==null){$DefaultSSLParams["countryName"]="US";}
        if($DefaultSSLParams["stateOrProvinceName"]==null){$DefaultSSLParams["stateOrProvinceName"]=$WizardSavedSettings["city"];}
        if($DefaultSSLParams["localityName"]==null){$DefaultSSLParams["localityName"]=$WizardSavedSettings["city"];}
        if($DefaultSSLParams["organizationName"]==null){$DefaultSSLParams["organizationName"]=$LicenseInfos["COMPANY"];}
        if($DefaultSSLParams["organizationalUnitName"]==null){$DefaultSSLParams["organizationalUnitName"]=$WizardSavedSettings["organization"];}
        if(!is_numeric($DefaultSSLParams["CertificateMaxDays"])){$DefaultSSLParams["CertificateMaxDays"]=730;}

        $openssl=$unix->find_program("openssl");
        $countryName=$DefaultSSLParams["countryName"];
        $stateOrProvinceName=$DefaultSSLParams["stateOrProvinceName"];
        $localityName=$DefaultSSLParams["localityName"];
        $OrganizationName=$DefaultSSLParams["organizationName"];
        $OrganizationalUnit=$DefaultSSLParams["organizationalUnitName"];
        $CertificateMaxDays=$DefaultSSLParams["CertificateMaxDays"];

        $DefaultSubject="-subj \"/C=$countryName/ST=$stateOrProvinceName/L=$localityName/O=$OrganizationName/OU=$OrganizationalUnit/CN=*\"";
        echo "Starting......: ".date("H:i:s")." [SSL]: Building certificate $DefaultSubject\n";

        $cmd="$openssl req -new -newkey rsa:1024 $DefaultSubject -days 365 -nodes -x509 -keyout $keyout -out $keyout";
        shell_exec($cmd);

        echo "Starting......: ".date("H:i:s")." [SSL]: $cmd\n";

        if(!is_file("$keyout")){
            echo "Starting......: ".date("H:i:s")." [SSL]: Warning !!! $keyout no such file\n";
            return null;}

        echo "Starting......: ".date("H:i:s")." [SSL]: create certificate.der\n";
        $cmd="$openssl x509 -in $keyout -outform DER -out /usr/share/artica-postfix/ressources/squid/certificate.der";
        echo "Starting......: ".date("H:i:s")." [SSL]: $cmd\n";
        shell_exec($cmd);
        if(!is_file("/usr/share/artica-postfix/ressources/squid/certificate.der")){
            echo "Starting......: ".date("H:i:s")." [SSL]: Warning /usr/share/artica-postfix/ressources/squid/certificate.der no such file\n";
        }

        if(@unlink("/usr/share/artica-postfix/ressources/squid/hotspot-$certificate_filename.der")){
            @copy("/usr/share/artica-postfix/ressources/squid/certificate.der", "/usr/share/artica-postfix/ressources/squid/hotspot-$certificate_filename.der");
        }

        if(!is_file("/etc/squid3/ssl/dhparam.pem")){
            system("$openssl dhparam -outform PEM -out /etc/squid3/ssl/dhparam.pem 2048");

        }

        return " generate-host-certificates=on dynamic_cert_mem_cache_size=4MB cert=$keyout sslflags=VERIFY_CRL_ALL options=NO_SSLv3,No_Compression tls-dh=/etc/squid3/ssl/dhparam.pem";
    }
    public function SaveCertificate($commname=null,$cache_peer=false,$nginx=false,$coova=false,$squid27=false){

        if($commname==null){
            if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
            $commname=$unix->hostname_g();
        }


        $prefixOut="Starting......: ".date("H:i:s")." [  SSL  ]: $commname";
        if($nginx){
            $prefixOut="Starting......: ".date("H:i:s")." [INIT]: nginx SSL: $commname";
        }


        @mkdir("/etc/squid3/ssl",0755,true);
        $cert_token="cert";
        $key_token="key";
        if($cache_peer){$cert_token="sslcert";$key_token="sslkey";}
        $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
        if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();


        $certificate_filename=md5($commname);
        $this->LOGS[]="# Certificate $commname File name: $certificate_filename";

        $sql="SELECT `UsePrivKeyCrt`,`crt`,`privkey`,`SquidCert`,`Squidkey`,`bundle` FROM sslcertificates WHERE CommonName='$commname'";
        $ligne=$q->mysqli_fetch_array($sql);

        if($ligne["UsePrivKeyCrt"]==1){
            @file_put_contents("/etc/squid3/$certificate_filename.pem", $ligne["crt"]);
            @file_put_contents("/etc/squid3/$certificate_filename.key", $ligne["privkey"]);
        }



        if(is_file("/etc/squid3/$certificate_filename.pem")){
            if($unix->file_time_min("/etc/squid3/$certificate_filename.pem")<5){

                if($coova){
                    return  "HS_SSLCERTFILE=/etc/squid3/$certificate_filename.pem\nHS_SSLKEYFILE=/etc/squid3/$certificate_filename.key";
                }

                if($nginx){
                    echo "Starting......: ".date("H:i:s")." [INIT]: Nginx[".__LINE__."](".basename(__FILE__).") using certificate /etc/squid3/$certificate_filename.pem\n";
                    echo "Starting......: ".date("H:i:s")." [INIT]: Nginx[".__LINE__."](".basename(__FILE__).") using Key /etc/squid3/$certificate_filename.key\n";
                    return  "\tssl_certificate /etc/squid3/$certificate_filename.pem;\n\tssl_certificate_key /etc/squid3/$certificate_filename.key;";
                }
                $KeyToken="key=/etc/squid3/$certificate_filename.key";
                $CertToken="cert=/etc/squid3/$certificate_filename.pem";

                return " $CertToken $KeyToken";
            }
        }




        if(!$q->ok){
            $this->LOGS[]="# MySQL error L.".__LINE__;
            echo "Starting......: ".date("H:i:s")." [SSL] :: $commname = $q->mysql_error\n";}

        if(strlen($ligne["SquidCert"])==0){

            if(strlen($ligne["crt"])>10){
                $ligne["SquidCert"]=$ligne["crt"];
                $ligne["Squidkey"]=$ligne["privkey"];
            }
        }


        echo "$prefixOut certificate: ". strlen($ligne["SquidCert"])." bytes...\n";
        echo "$prefixOut key........: ". strlen($ligne["Squidkey"])." bytes...\n";
        echo "$prefixOut Chain......: ". strlen($ligne["bundle"])." bytes...\n";
        $GLOBALS["BuildSquidCertificate"][]="OK $commname = $certificate_filename [".__LINE__."]";

        if(strlen($ligne["SquidCert"])==0){
            if($cache_peer){return null;}
            $this->LOGS[]="# SquidCert == 0 Bytes Certificate not set for $commname, return default one L.".__LINE__;
            echo "$prefixOut [FATAL]: Certificate not set for $commname, return default one\n";
            $GLOBALS["BuildSquidCertificate"][]="Certificate not set for $commname, return default one [".__LINE__."]";
            return $this->SaveCertificateDefault($nginx,$coova,$squid27);
        }

        if( strlen($ligne["Squidkey"])==0 ){
            echo "$prefixOut [FATAL]: Key does not exists swith to default one\n";
            $GLOBALS["BuildSquidCertificate"][]="Key does not exists swith to default one [".__LINE__."]";
            return $this->SaveCertificateDefault($nginx,$coova,$squid27);
        }

        if( strlen($ligne["bundle"])>10 ){
            echo "$prefixOut Integrate chain certificate inside certificate\n";
            $GLOBALS["BuildSquidCertificate"][]="OK Integrate chain certificate inside certificate [".__LINE__."]";
            $ligne["SquidCert"]=$ligne["SquidCert"].$ligne["Squidkey"];
        }

        $ligne["SquidCert"]=str_replace("\\n", "\n", $ligne["SquidCert"]);
        $ligne["Squidkey"]=str_replace("\\n", "\n", $ligne["Squidkey"]);
        @file_put_contents("/etc/squid3/$certificate_filename.crt", $ligne["SquidCert"]);
        @file_put_contents("/etc/squid3/$certificate_filename.key", $ligne["Squidkey"]);

        if($nginx){return  "\tssl_certificate /etc/squid3/$certificate_filename.crt;\n\tssl_certificate_key /etc/squid3/$certificate_filename.key;";}

        if($coova){
            return  "HS_SSLCERTFILE=/etc/squid3/$certificate_filename.crt\nHS_SSLKEYFILE=/etc/squid3/$certificate_filename.key";
        }

        if($squid27){
            $tt[]="sslproxy_client_certificate /etc/squid3/$certificate_filename.crt";
            $tt[]="sslproxy_client_key  /etc/squid3/$certificate_filename.key";
            $tt[]="sslproxy_version 1";
           // $tt[]="sslproxy_flags DONT_VERIFY_PEER";
            return array(@implode("\n", $tt),array("certificate"=>"/etc/squid3/$certificate_filename.crt","key"=>"/etc/squid3/$certificate_filename.key"));
        }

        $KeyToken2="key=/etc/squid3/$certificate_filename.key";
        $CertToken2="$cert_token=/etc/squid3/$certificate_filename.crt";
        return " $CertToken2 $KeyToken2";
    }

    private function SaveCertificateDefault($nginx=false,$coova=false,$squid27=false){
        $prefixOut="Starting......: ".date("H:i:s")." [  SSL  ]: Default";
        if($nginx){$prefixOut="Starting......: ".date("H:i:s")." [INIT]: nginx SSL: Default";}


        @mkdir("/etc/squid3/ssl",0755,true);
        if(!is_file("/etc/squid3/ssl/cacert.pem")){
            if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
            echo "$prefixOut invoke to create a faked Self-signed certificate\n";
            shell_exec($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.squid.php --certificate");
        }

        $CertToken="cert=/etc/squid3/ssl/cacert.pem";
        $KeyToken="key=/etc/squid3/ssl/privkey.pem";

        $certificates=" $CertToken $KeyToken";
        if($coova){
            return  "HS_SSLCERTFILE=/etc/squid3/ssl/cacert.pem\nHS_SSLKEYFILE=/etc/squid3/ssl/privkey.pem";
        }

        if($nginx){return  "\tssl_certificate /etc/squid3/ssl/cacert.pem;\n\tssl_certificate_key /etc/squid3/ssl/privkey.pem;";}

        if($squid27){
            $tt[]="sslproxy_client_certificate /etc/squid3/ssl/cacert.pem";
            $tt[]="sslproxy_client_key  /etc/squid3/ssl/privkey.pem";
            $tt[]="sslproxy_version 1";
            //$tt[]="sslproxy_flags DONT_VERIFY_PEER";
            echo "$prefixOut return 2.7v\n";
            return array(@implode("\n", $tt),array("certificate"=>"/etc/squid3/ssl/cacert.pem","key"=>"/etc/squid3/ssl/privkey.pem"));
        }
        return $certificates;
    }
}