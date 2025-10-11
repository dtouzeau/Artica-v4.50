#!/usr/bin/php
<?php
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.openssl.aes.inc');
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}

if(isset($argv[1])) {
    if ($argv[1] == "--client") {
        create_client_certificate($argv[2], $argv[3]);
        exit;
    }
}
nginx_generate_server_certificate();

function nginx_generate_server_certificate(){
    $unix=new unix();
    $WizardSavedSettings = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));
    $LicenseInfos = unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));

    if (!isset($WizardSavedSettings["company_name"])) {
        if (!isset($LicenseInfos["COMPANY"])) {$LicenseInfos["COMPANY"] = time();}
        $LicenseInfos['COMPANY'] = $WizardSavedSettings["company_name"];
    }
    $Company    = $WizardSavedSettings["company_name"];
    $path       = "/etc/nginx/CERT_TEMP/WebConsole";
    if(!is_dir($path)){@mkdir($path,0755,true);}
    $ca_crt     = "$path/ca.crt";
    $ca_key     = "$path/ca.key";
    $rm         = $unix->find_program("rm");
    $openssl    = $unix->find_program("openssl");
    $deleteall  = "$rm -rf $path";
    $addext2    = " -addext \"extendedKeyUsage=serverAuth,clientAuth\"";
    $addext3    = " -addext \"nsComment='Certificate Usage For Artica Web Console'\"";
    $CertificateMaxDays=3650;
    $subj       =  "-subj \"/O=$Company/OU=Artica/CN=Artica WebConsole\"";
    $LevelEnc=4096;
    create_client_certificate_progress(20,"{new_server_certificate} $ca_key");
    $cmd="$openssl genrsa -des3 -passout pass:pass -out $ca_key $LevelEnc 2>&1";
    echo $cmd."\n";
    shell_exec($cmd);
    if(!is_file($ca_key)){
        shell_exec($deleteall);
        create_client_certificate_progress(110,"{new_server_certificate} $ca_key {failed}");
        return false;
    }
    $cmd="$openssl req -new -x509 -batch -days $CertificateMaxDays -passin pass:pass $subj$addext2$addext3 -key $ca_key -out $ca_crt 2>&1";
    echo $cmd."\n";
    create_client_certificate_progress(50,"{new_server_certificate} $ca_crt");
    shell_exec($cmd);
    if(!is_file($ca_crt)){
        create_client_certificate_progress(110,"{new_server_certificate} $ca_crt {failed}");
        echo "Missing $ca_crt\n";
        shell_exec($deleteall);
        return false;
    }
    $ca_data=base64_encode(@file_get_contents($ca_key));
    $crt_data=base64_encode(@file_get_contents($ca_crt));
    $MAIN["ca_data"]=$ca_data;
    $MAIN["crt_data"]=$crt_data;
    $LighttpdServerCertificate=base64_encode(serialize($MAIN));
    create_client_certificate_progress(100,"{new_server_certificate} {success}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdServerCertificate",$LighttpdServerCertificate);
    squid_admin_mysql(1,"Generating Web console Server certificate done...",null,__FILE__,__LINE__);
    return true;


}
function create_client_certificate_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"manager-certificate.progress");

}


function create_client_certificate($uid,$password){
    $unix   = new unix();
    $ldap   = new clladp();

    echo "Generating certificate for <$uid>\n";

    $LighttpdServerCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdServerCertificate");
    if(strlen($LighttpdServerCertificate)<50){
        nginx_generate_server_certificate();
        $LighttpdServerCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdServerCertificate");
    }

    if(strlen($LighttpdServerCertificate)<50){
        create_client_certificate_progress(110,"Issue on Server certificate, Aborting");
        return false;
    }
    $DN=null;

    if($uid=="-generic"){
        $CLEAN_CERTIFICATE_TEMP=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CLEAN_CERTIFICATE_TEMP")));

        $uid=$CLEAN_CERTIFICATE_TEMP["uid"];
        $type=$CLEAN_CERTIFICATE_TEMP["type"];
        $displayname=$CLEAN_CERTIFICATE_TEMP["displayname"];
        $password=$CLEAN_CERTIFICATE_TEMP["password"];
        if(isset($CLEAN_CERTIFICATE_TEMP["dn"])){$DN=$CLEAN_CERTIFICATE_TEMP["dn"];}
    }

    $MAIN=unserialize(base64_decode($LighttpdServerCertificate));
    $ca_key_data = base64_decode($MAIN["ca_data"]);
    $ca_crt_data = base64_decode($MAIN["crt_data"]);
    $array=openssl_x509_parse($ca_crt_data);
    $levelenc=4096;
    $set_serial=time();

    $CertificateMaxDays=1825;
    if($password==null) {

        $password = $ldap->ldap_password;

    }


    if(!isset($array["subject"] )) {
        create_client_certificate_progress(110,"$uid unable to read <$ca_crt_data>");
        echo "#$uid unable to read!\n";
        return false;
    }

    foreach ($array["subject"] as $key=>$val){$ST[$key]=$val;}
    $username=$uid;
    if($username==-100) {
        $ST["UID"] = "-100";
        $ST["CN"] = $ldap->ldap_admin;


    }else{
        $ST["UID"] = "$type:$username";
        $ST["CN"] = $displayname;
    }
    $SUBJ=array();
    foreach ($ST as $key=>$val){
        $SUBJ[]="$key=$val";
    }
    $subj="-subj \"/".@implode("/",$SUBJ)."\"";
    if($DN<>null){
        $DNEXPL=explode(",",$DN);
        krsort($DNEXPL);
        $DNEX=@implode("/",$DNEXPL);
        $DNEX=$DNEX."/UID=$type:$username";
        $subj="-subj \"/$DNEX\"";
    }
    echo "Subj: [$subj]\n";
    $openssl=$unix->find_program("openssl");
    $path="/etc/nginx/CERT_TEMP/".md5($uid);
    if(!is_dir($path)){@mkdir($path,0755,true);}
    $user_key="$path/user.key";
    $user_csr="$path/user.csr";
    $user_crt="$path/user.crt";
    $ca_crt="$path/ca.crt";
    $ca_key="$path/ca.key";
    $user_pfx="$path/user.pfx";
    $rm=$unix->find_program("rm");
    $deleteall="$rm -rf $path";

    @file_put_contents($ca_crt,$ca_crt_data);
    @file_put_contents($ca_key,$ca_key_data);

    create_client_certificate_progress(50,"Create a CA Certificate #$username");
    $cmd="$openssl genrsa -des3 -passout pass:pass -out $user_key $levelenc 2>&1";
    echo "$cmd\n";
    $results=array();
    exec("$cmd",$results);
    foreach ($results as $line){echo "STEP1: $line\n";}

    if(!is_file($user_key)){
        echo "$user_key, no such file\n";
        create_client_certificate_progress(110,"Create a CA Certificate #$username {failed}");
        shell_exec($deleteall);
        return false;
    }

    create_client_certificate_progress(60,"Create a CSR Certificate #$username");

    $cmd="$openssl req -new -batch -passin pass:pass $subj -key $user_key -out $user_csr 2>&1";
    echo "$cmd\n";
    $results=array();
    exec("$cmd",$results);
    foreach ($results as $line){echo "STEP2: $line\n";}

    if(!is_file($user_csr)){
        echo "$user_csr, no such file\n";
        create_client_certificate_progress(110,"Create a CSR Certificate #$username {failed}");
        shell_exec($deleteall);
        return false;
    }

    create_client_certificate_progress(70,"Create Certificate #$username");
    $cmd="$openssl x509 -req -days $CertificateMaxDays -passin pass:pass -in $user_csr -CA $ca_crt -CAkey $ca_key -set_serial $set_serial -out $user_crt 2>&1";
    echo "$cmd\n";
    $results=array();
    exec("$cmd",$results);
    foreach ($results as $line){echo "STEP3: $line\n";}

    if(!is_file($user_crt)){
        echo "$user_crt, no such file\n";
        create_client_certificate_progress(110,"Create Certificate #$username {failed}");
        shell_exec($deleteall);
        return false;
    }
    $pkcs12Pass=$unix->shellEscapeChars($password);
    create_client_certificate_progress(75,"Creating a PKCS #12 (PFX) #$username");
    $cmd="$openssl pkcs12 -export -password pass:$pkcs12Pass -passin pass:pass -out $user_pfx -inkey $user_key -in $user_crt -certfile $ca_crt 2>&1";
    echo "$cmd\n";
    $results=array();
    exec("$cmd",$results);
    foreach ($results as $line){echo "STEP4: $line\n";}

    if(!is_file($user_pfx)){
        echo "$user_pfx, no such file\n";
        create_client_certificate_progress(110,"Creating a PKCS #12 (PFX) #$username {failed}");
        shell_exec($deleteall);
        return false;
    }


    $user_pfx_data=base64_encode(@file_get_contents($user_pfx));
    create_client_certificate_progress(80,"Saving data #$username");
    if($uid=="-100"){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdManagerClientAuth",$user_pfx_data);
        create_client_certificate_progress(100,"#$username {success}");
        shell_exec($deleteall);
        return true;
    }

    $target_dir="/usr/share/artica-postfix/ressources/conf/certs";
    if(!is_dir($target_dir)){ @mkdir($target_dir,0755,true); }
    @copy($user_pfx,"$target_dir/$uid-$type.pfx");
    @chown("/usr/share/artica-postfix/ressources/conf","www-data");
    @chown("$target_dir/$uid-$type.pfx","www-data");
    create_client_certificate_progress(100,"#$displayname {success}");
    shell_exec($deleteall);
    return true;

}