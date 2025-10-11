<?php
$GLOBALS["OUTPUT"]=false;
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.openvpn.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}

if(is_array($argv)){
    if(preg_match("#--verbose#",implode(" ",$argv))){
        $GLOBALS["OUTPUT"]=true;
        $GLOBALS["debug"]=true;
        $GLOBALS["DEBUG"]=true;
        $GLOBALS["VERBOSE"]=true;
        ini_set('html_errors',0);
        ini_set('display_errors', 1);
        ini_set('error_reporting', E_ALL);
    }
}

if($argv[1]=="--create-certificate"){create_certificate($argv[2]);exit;}
if($argv[1]=="--extract"){ExtractInfos();exit;}
if($argv[1]=="--dnskey"){dnskey($argv[2]);exit;}
if($argv[1]=="--dns"){dns_perform($argv[2]);exit;}

echo "Unable to unerstand {$argv[1]}\n";
function _out($text):bool{
    echo "Service.......: ".date("H:i:s")." [INIT]: Let's Encrypt renew $text\n";
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("letsencrypt", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}

function build_progress($pourc,$text){
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $cachefile=PROGRESS_DIR."/letsencrypt.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}
function dns_perform($CertificateID){
    $CertificateID=intval($CertificateID);
    $unix=new unix();
    $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");

    $ligne=$q->mysqli_fetch_array("SELECT emailAddress,CommonName,csr,privkey,Squidkey FROM sslcertificates WHERE ID=$CertificateID");
    $emailAddress=$ligne["emailAddress"];
    $CommonName=$ligne["CommonName"];
    $certbot=$unix->find_program("certbot");

    build_progress(10, "{extract} {CSR}...");

    $CsrLength=strlen($ligne["csr"]);
    $ligne["csr"]=str_replace("\\n", "\n", $ligne["csr"]);
    echo "CSR: {$CsrLength}Bytes\n";
    if($CsrLength<20){
        build_progress(110, "Corrupted CSR... size < 20");
        return;
    }

    $TEMP_CSR=$unix->FILE_TEMP().".DER";
    @file_put_contents($TEMP_CSR, $ligne["csr"]);


    $certificate_path="/etc/letsencrypt/live/$CommonName/cert.pem";
    $privkey_path="/etc/letsencrypt/live/$CommonName/privkey.pem";
    $chain_path="/etc/letsencrypt/live/$CommonName/chain.pem";
    $fullchain_path="/etc/letsencrypt/live/$CommonName/fullchain.pem";
    @mkdir("/etc/letsencrypt/live/$CommonName",0755,true);



    $cmd[]="$certbot certonly";
    $cmd[]="--config-dir /etc/letsencrypt";
    $cmd[]="--work-dir /var/lib/letsencrypt";
    $cmd[]="--cert-path $certificate_path";
    $cmd[]="--key-path $privkey_path";
    $cmd[]="--fullchain-path $fullchain_path";
    $cmd[]="--chain-path $chain_path";
    if(strlen($ligne["csr"])>100) {
        $cmd[] = "--csr $TEMP_CSR";
    }
    $cmd[]="--standalone";
    $cmd[]="--preferred-challenges dns";
    $cmd[]="--noninteractive";
    $cmd[]="--agree-tos";
    $cmd[]="-m $emailAddress";
    $cmd[]="-d $CommonName";
    $Cmdline=@implode(" ", $cmd);

    echo "$Cmdline\n";
    system($Cmdline);

    build_progress(75, "{saving_certificates}...");

    if(!is_file($certificate_path)){
        echo "Unable to stat $certificate_path [L.".__LINE__."]\n";
        $f=explode("\n",@file_get_contents("/var/log/letsencrypt/letsencrypt.log"));
        foreach ($f as $line){echo $line;}
        $q->QUERY_SQL("UPDATE sslcertificates SET UseLetsEncrypt=0 WHERE ID=$CertificateID");
        @unlink($TEMP_CSR);
        build_progress(110, "{failed}");
        $php=$unix->LOCATE_PHP5_BIN();
        shell_exec("$php /usr/share/artica-postfix/exec.certificates.center.php");
        return;
    }

    if(!is_file($fullchain_path)){
        echo "Unable to stat $fullchain_path\n";
        $f=explode("\n",@file_get_contents("/var/log/letsencrypt/letsencrypt.log"));
        foreach ($f as $line){echo $line."\n";}
        build_progress(110, "{failed}");
        $php=$unix->LOCATE_PHP5_BIN();
        shell_exec("$php /usr/share/artica-postfix/exec.certificates.center.php");
        return;
    }
    if(!is_file($chain_path)){
        echo "Unable to stat $chain_path\n";
        $f=explode("\n",@file_get_contents("/var/log/letsencrypt/letsencrypt.log"));
        foreach ($f as $line){echo $line."\n";}
        $q->QUERY_SQL("UPDATE sslcertificates SET UseLetsEncrypt=0 WHERE ID=$CertificateID");
        @unlink($TEMP_CSR);
        build_progress(110, "{failed}");
        $php=$unix->LOCATE_PHP5_BIN();
        shell_exec("$php /usr/share/artica-postfix/exec.certificates.center.php");
        return;
    }

    build_progress(80, "{saving_certificates} {success}...");

    if(!SaveAllCerts($CommonName,$CertificateID)){
        build_progress(110, "{failed}");
        $f=explode("\n",@file_get_contents("/var/log/letsencrypt/letsencrypt.log"));
        foreach ($f as $line){echo $line."\n";}
    }

    @unlink($TEMP_CSR);
    build_progress(100, "{saving_certificates} {success}...");


    @unlink($certificate_path);
    if(is_file($privkey_path)){@unlink($privkey_path);}
    @unlink($chain_path);
    @unlink($fullchain_path);

    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.certificates.center.php");

}

function dnskey($CertificateID){
    $CertificateID=intval($CertificateID);
    $unix=new unix();
    $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");

    build_progress(10,"{checking}...");
    echo "Loading certificate ID $CertificateID\n";

    $ligne=$q->mysqli_fetch_array("SELECT emailAddress,CommonName,csr,privkey,Squidkey FROM sslcertificates WHERE ID=$CertificateID");
    $emailAddress=$ligne["emailAddress"];
    $CommonName=$ligne["CommonName"];

    $tmp_path=$unix->TEMP_DIR()."/$CommonName";
    $certbot=$unix->find_program("certbot");
    @mkdir($tmp_path,0755,true);

    $sh[]="#!/bin/bash";
    $sh[]="echo \$CERTBOT_VALIDATION > $tmp_path/CERTBOT_VALIDATION";
    $sh[]="echo \$CERTBOT_TOKEN > $tmp_path/CERTBOT_TOKEN";
    $sh[]="";


    build_progress(20,"{checking}...");
    @file_put_contents("$tmp_path/valid.sh",@implode("\n",$sh));
    @chmod("$tmp_path/valid.sh",0755);

    $cmds[]="$certbot certonly --manual --preferred-challenges=dns --non-interactive";
    $cmds[]="--dry-run  --manual-auth-hook $tmp_path/valid.sh";
    $cmds[]="--manual-public-ip-logging-ok -d $CommonName";
    $cmdline=@implode(" ",$cmds);
    echo $cmdline."\n";
    build_progress(50,"{checking}...");
    system($cmdline);

    $CERTBOT_VALIDATION=trim(@file_get_contents("$tmp_path/CERTBOT_VALIDATION"));
    $CERTBOT_TOKEN=@file_get_contents("$tmp_path/CERTBOT_TOKEN");
    echo "emailAddress..........: $emailAddress\n";
    echo "CommonName........: $CommonName\n";
    echo "CERTBOT_VALIDATION: $CERTBOT_VALIDATION\n";
    echo "CERTBOT_TOKEN.....: $CERTBOT_TOKEN\n";

    if($CERTBOT_VALIDATION==null){
        build_progress(110,"{failed}...");
        return;
    }
    if(!$q->FIELD_EXISTS("sslcertificates","letsencrypt_dns_key")){
        $q->QUERY_SQL("ALTER TABLE sslcertificates ADD letsencrypt_dns_key TEXT");
    }

    $sql="UPDATE sslcertificates SET letsencrypt_dns_key='$CERTBOT_VALIDATION' WHERE ID=$CertificateID";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $q->mysql_error."\n";
        build_progress(110,"{failed}...");
        return;
    }

    build_progress(100,"{success}...");
}

function create_certificate($CertificateID){
    $CertificateID=intval($CertificateID);
    $unix=new unix();
    $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");

    if(!$q->FIELD_EXISTS("sslcertificates","AdditionalNames")){
        $q->QUERY_SQL("ALTER TABLE sslcertificates ADD AdditionalNames TEXT NULL");
    }


    $php=$unix->LOCATE_PHP5_BIN();
    $ligne=$q->mysqli_fetch_array("SELECT emailAddress,CommonName,csr,crt,privkey,Squidkey,AdditionalNames FROM sslcertificates WHERE UseLetsEncrypt=1 AND ID=$CertificateID");

    $CSR=openssl_csr_get_subject($ligne["csr"]);
    $emailAddress=$ligne["emailAddress"];
    if($emailAddress==null){$emailAddress=$CSR["emailAddress"];}


    $CommonName=strtolower($ligne["CommonName"]);
    $CommonName=str_replace("*.", "", $CommonName);
    $ligne["csr"]=str_replace("\\n", "\n", $ligne["csr"]);

    $SS[$CommonName]=true;
    $f=explode(",",$ligne["AdditionalNames"]);
    foreach ($f as $line){
        $line=trim(strtolower($line));
        if($line==null){continue;}
        $SS[$line]=true;
    }

    $tDomz=array();
    foreach ($SS as $dom=>$none){
        $tDomz[]="-d $dom";
    }


    if($CertificateID==0){
        build_progress(110, "Certificate ID $CertificateID corrupted.....");
        return;
    }

    if($emailAddress==null){
        _out("Unable to find email address FROM ID $CertificateID...");
        build_progress(110, "Unable to find email address FROM ID $CertificateID...");
        return;
    }
    if($CommonName==null){
        _out("Unable to find email CommonName FROM ID $CertificateID...");
        build_progress(110, "Unable to find CommonName FROM ID $CertificateID...");
        return;
    }

    $LETS_ENCRYPT_PATH="/etc/letsencrypt/live/$CommonName";


    build_progress(10, "{extract} {CSR}...");

    $CsrLength=strlen($ligne["csr"]);
    $ligne["csr"]=str_replace("\\n", "\n", $ligne["csr"]);
    echo "CSR: {$CsrLength}Bytes\n";
    if($CsrLength<20){
        build_progress(110, "Corrupted CSR... size < 20");
        $q->QUERY_SQL("UPDATE sslcertificates SET UseLetsEncrypt=0 WHERE ID=$CertificateID");
        return;
    }

    $TEMP_CSR=$unix->FILE_TEMP().".DER";
    @file_put_contents($TEMP_CSR, $ligne["csr"]);

    $certbot=$unix->find_program("certbot");


    $certificate_path="$LETS_ENCRYPT_PATH/cert.pem";
    $privkey_path="$LETS_ENCRYPT_PATH/privkey.pem";
    $chain_path="$LETS_ENCRYPT_PATH/chain.pem";
    $fullchain_path="$LETS_ENCRYPT_PATH/fullchain.pem";

    $rm=$unix->find_program("rm");
    if(is_dir($LETS_ENCRYPT_PATH)) {
        echo "Removing $LETS_ENCRYPT_PATH\n";
        shell_exec("$rm -rf $LETS_ENCRYPT_PATH");
    }
    for($i=1;$i<11;$i++) {
        $zro="00";
        if($i>9){$zro="0";}
        if (is_dir("$LETS_ENCRYPT_PATH-{$zro}$i")) {
            echo "Removing $LETS_ENCRYPT_PATH-{$zro}$i\n";
            shell_exec("$rm -rf $LETS_ENCRYPT_PATH-{$zro}$i");
        }
    }


    @unlink("/var/log/letsencrypt/letsencrypt.log");
    @touch("/var/log/letsencrypt/letsencrypt.log");

    if(strlen($ligne["privkey"])>20){
        $ligne["privkey"]=str_replace("\\n", "\n", $ligne["privkey"]);
        @file_put_contents($privkey_path, $ligne["privkey"]);
    }

    if(is_file($certificate_path)){
        if(is_file($privkey_path)){
            if(is_file($chain_path)){
                if(is_file($fullchain_path)){
                    build_progress(70, "{saving_certificates}...");
                    if(!SaveAllCerts($CommonName,$CertificateID)){build_progress(110, "{failed}");return;}
                }
            }
        }
    }

    if(is_file("/etc/init.d/firehol")){
        build_progress(40, "{stopping} {APP_FIREWALL}...");
        system("/etc/init.d/firehol stop");
    }

    _out("Stopping Web services...");

    if(is_file("/etc/init.d/nginx")){
        build_progress(42, "{stopping} {APP_NGINX}...");
        system("/etc/init.d/nginx stop");
    }

    if(is_file("/etc/init.d/monit")){
        build_progress(44, "{stopping} {APP_MONIT}...");
        system("/etc/init.d/monit stop");
    }

    if(is_file("/etc/init.d/artica-status")){
        build_progress(46, "{stopping} {APP_MONIT}...");
        system("/etc/init.d/artica-status stop");
    }

    $unix->KILL_PROCESSES_BY_PORT(80);
    $unix->KILL_PROCESSES_BY_PORT(80);


    build_progress(50, "{generate_certificate}...");
    $cmd[]="$certbot certonly";
    $cmd[]="--config-dir /etc/letsencrypt";
    $cmd[]="--work-dir /var/lib/letsencrypt";
    $cmd[]="--cert-path $certificate_path";
    $cmd[]="--key-path $privkey_path";
    $cmd[]="--fullchain-path $fullchain_path";
    $cmd[]="--chain-path $chain_path";
    //if(strlen($ligne["csr"])>100) {
    //  $cmd[] = "--csr $TEMP_CSR";
    //}
    $cmd[]="--standalone";
    $cmd[]="--preferred-challenges http";
    $cmd[]="--noninteractive";
    $cmd[]="--agree-tos";
    $cmd[]="-m $emailAddress";
    $cmd[]=@implode(" ",$tDomz);
    $Cmdline=@implode(" ", $cmd);

    $handle = opendir("/etc/letsencrypt/renewal");

    $CommonNameRegex=str_replace(".","\.",$CommonName);
    echo "Found $CommonNameRegex in /etc/letsencrypt/renewal\n";

    if($handle){
        while (false !== ($filename = readdir($handle))) {
            if($filename=="."){continue;}
            if($filename==".."){continue;}
            $targetFile="/etc/letsencrypt/renewal/$filename";
            if(is_dir($targetFile)){
                continue;
            }
            if(preg_match("#$CommonNameRegex#", $filename)){
                echo "Removing $targetFile\n";
                @unlink($targetFile);}
        }


    }
    echo "Found $CommonNameRegex directories in /etc/letsencrypt/archive\n";
    $handle = opendir("/etc/letsencrypt/archive");
    if($handle){
        while (false !== ($filename = readdir($handle))) {
            if($filename=="."){continue;}
            if($filename==".."){continue;}
            $targetFile="/etc/letsencrypt/archive/$filename";
            if(is_file($targetFile)){continue;}
            echo "Checking $CommonNameRegex in $filename\n";
            if(preg_match("#$CommonNameRegex#", $filename)){
                echo "Removing $targetFile\n";
                shell_exec("$rm -rf $targetFile");
            }
        }


    }



    echo "$Cmdline\n";
    system($Cmdline);

    _out("Starting Web services...");

    if(is_file("/etc/init.d/firehol")){
        build_progress(60, "{starting} {APP_FIREWALL}...");
        system("/etc/init.d/firehol start");
    }

    if(is_file("/etc/init.d/nginx")){
        build_progress(70, "{starting} {APP_NGINX}...");
        system("/etc/init.d/nginx start");
    }

    if(is_file("/etc/init.d/monit")){
        build_progress(72, "{starting} {APP_MONIT}...");
        system("/etc/init.d/monit start");
    }

    if(is_file("/etc/init.d/artica-status")){
        build_progress(74, "{starting} {APP_MONIT}...");
        system("/etc/init.d/artica-status start");
    }

    build_progress(75, "{saving_certificates}...");

    if(!is_file($certificate_path)){
        echo "Unable to stat $certificate_path [L.".__LINE__."]\n";
        $f=explode("\n",@file_get_contents("/var/log/letsencrypt/letsencrypt.log"));
        foreach ($f as $line){echo $line;}
        @unlink($TEMP_CSR);
        build_progress(110, "{failed}");
        return;
    }

    if(!is_file($fullchain_path)){
        echo "Unable to stat $fullchain_path\n";
        $f=explode("\n",@file_get_contents("/var/log/letsencrypt/letsencrypt.log"));
        foreach ($f as $line){echo $line."\n";}
        build_progress(110, "{failed}");
        return;
    }
    if(!is_file($chain_path)){
        echo "Unable to stat $chain_path\n";
        $f=explode("\n",@file_get_contents("/var/log/letsencrypt/letsencrypt.log"));
        foreach ($f as $line){echo $line."\n";}
        @unlink($TEMP_CSR);
        build_progress(110, "{failed}");
        return;
    }

    build_progress(80, "{saving_certificates} {success}...");

    if(!SaveAllCerts($CommonName,$CertificateID)){
        build_progress(110, "{failed}");
        $f=explode("\n",@file_get_contents("/var/log/letsencrypt/letsencrypt.log"));
        foreach ($f as $line){echo $line."\n";}
    }

    @unlink($TEMP_CSR);
    build_progress(100, "{saving_certificates} {success}...");


    if(is_file($privkey_path)) { @unlink($privkey_path);}
    if(is_file($certificate_path)) { @unlink($certificate_path);}
    if(is_file($chain_path)) { @unlink($chain_path);}
    if(is_file($fullchain_path)) { @unlink($fullchain_path);}



}

function SaveAllCerts($CommonName,$CertificateID):bool{
    $certificate_path       = "/etc/letsencrypt/live/$CommonName/cert.pem";
    $privkey_path           = "/etc/letsencrypt/live/$CommonName/privkey.pem";
    $chain_path             = "/etc/letsencrypt/live/$CommonName/chain.pem";
    $fullchain_path         = "/etc/letsencrypt/live/$CommonName/fullchain.pem";
    $q                      = new lib_sqlite("/home/artica/SQLITE/certificates.db");
    $privkey_content        = null;

    $certificate_content    = @file_get_contents($certificate_path);
    $certificate_content    = sqlite_escape_string2($certificate_content);
    $fullchain_content      = @file_get_contents($fullchain_path);
    $fullchain_content      = sqlite_escape_string2($fullchain_content);
    $chain_content          = @file_get_contents($chain_path);
    $chain_content          = sqlite_escape_string2($chain_content);

    if(is_file($privkey_path)) {
        $privkey_content = @file_get_contents($privkey_path);
        $privkey_content = sqlite_escape_string2($privkey_content);
    }



    if(strlen($fullchain_content)>100){
        $certificate_content=$fullchain_content;
    }

    $sqls[]="UPDATE sslcertificates SET";
    $sqls[]="`UsePrivKeyCrt`=1,";
    if(strlen($privkey_content)>50){
        $sqls[]="`privkey`='$privkey_content',";
    }
    $sqls[]="`bundle`='$chain_content',";
    $sqls[]="`crt`='$certificate_content'";
    $sqls[]="WHERE `ID`=$CertificateID";

    $sql=@implode(" ",$sqls);
    $q->QUERY_SQL($sql);

    if(!$q->ok){echo $q->mysql_error."\n";return false;}
    ExtractInfos();
    return true;

}


function ExtractInfos(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.certificates.center.php");

}
