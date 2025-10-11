<?php
#SP206
$GLOBALS["OUTPUT"]=false;
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.openvpn.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
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

    if(preg_match("#--HarmpID=([0-9]+)#",implode(" ",$argv),$re)){
        $GLOBALS["HarmpID"]=$re[1];
    }


}
if($GLOBALS["OUTPUT"]){echo "Debug mode TRUE for {$argv[1]} {$argv[2]}\n";}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if($argv[1]=="--pkcs12"){build_pkcs12($argv[2]);exit;}
if($argv[1]=="--pass"){passphrase($argv[2]);exit;}
if($argv[1]=="--buildkey"){buildkey($argv[2]);exit;}
if($argv[1]=="--parse"){parse_certificate($argv[2]);exit;}

if($argv[1]=="--x509"){x509($argv[2]);exit;}
if($argv[1]=="--mysql"){exit();}
if($argv[1]=="--squid-auto"){squid_autosigned($argv[2]);exit;}
if($argv[1]=="--squid-validate"){squid_validate($argv[2]);exit;}
if($argv[1]=="--BuildCSR"){$GLOBALS["OUTPUT"]=true;easyrsa_vars($argv[2]);exit;}
if($argv[1]=="--client-server"){autosigned_certificate_server_client($argv[2]);exit;}
if($argv[1]=="--client-nginx"){build_client_side_certificate($argv[2]);exit;}
if($argv[1]=="--pvk"){pvk_convert($argv[2]);exit;}
if($argv[1]=="--pfx-convert"){pfx_convert($argv[2]);exit;}
if($argv[1]=="--cert-infos"){UpdateCertificateInfos($argv[2]);exit;}
if($argv[1]=="--pfx"){build_pfx($argv[2]);exit;}
if($argv[1]=="--der"){build_der($argv[2]);exit;}
if($argv[1]=="--csr"){easyrsa_csr($argv[2]);exit;}
if($argv[1]=="--squid27"){squid27_certif($argv[2]);exit;}
if($argv[1]=="--backup"){backup();exit;}
if($argv[1]=="--import-backup"){import_backup($argv[2]);exit;}
if($argv[1]=="--import-backup2"){import_backup2($argv[2]);exit;}
if($argv[1]=="--restore"){restore($argv[2]);exit;}
//exec.openssl.php --easyrsa xxx
if($argv[1]=="--easyrsa"){easyrsa_vars($argv[2]);exit;}
if($argv[1]=="--easyclient"){easyrsa_client($argv[2],$argv[3]);exit;}
if($argv[1]=="--import-pfx"){import_pfx($argv[2],$argv[3]);exit;}
if($argv[1]=="--import-p7r"){import_p7r($argv[2]);exit;}
if($argv[1]=="--selfsign"){SelfRootCA($argv[2]);exit;}
if($argv[1]=="--selfsign-server"){selfServer($argv[2]);exit;}
if($argv[1]=="--selfsign-client"){selfClient($argv[2]);exit;}


if($argv[1]=="--client-cert"){exit;}
if($argv[1]=="--rootca"){SelfRootCA($argv[2]);exit;}
if($argv[1]=="--sql"){patchSQL();exit;}
if($argv[1]=="--squid-wizard"){squid_wizard();exit;}
if($argv[1]=="--read"){read_certificate($argv[2]);}


echo "Cannot understand your commandline {$argv[1]}\n";

function BuildCSR($CommonName){
    $CommonName=str_replace("_ALL_", "*", $CommonName);
    buildkey($CommonName,true);
    squid_autosigned($CommonName);

}
function GetDatabase():string{
    $db="/home/artica/SQLITE/certificates.db";
    if(isset($GLOBALS["HarmpID"])){
        if(intval($GLOBALS["HarmpID"])>0){
            $db="/home/artica/SQLITE/certificates.{$GLOBALS["HarmpID"]}.db";
        }
    }
    return $db;
}
function parse_certificate($path){
    $f=@file_get_contents($path);
    print_r(openssl_x509_parse($f));
}
function build_progress_x509($text,$pourc):bool{
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"openssl.x509.progress");
    return true;
}
function build_progress_import($text,$pourc){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/certificates.center.import.progress";
    echo "[{$pourc}%] $text\n";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["CACHEFILE"],0755);
    if($GLOBALS["OUTPUT"]){sleep(1);}
}


function import_backup2($filename){
    import_backup(base64_encode($filename));
}

function import_pfx($filename,$password):bool{
    $unix=new unix();
    $filename=base64_decode($filename);
    $fullpath="/usr/share/artica-postfix/ressources/conf/upload/$filename";
    $SourceFile=$unix->TEMP_DIR()."/import.pfx";


    if(!is_file($fullpath)){
        echo "$fullpath no such file\n";
        build_progress_import("$filename no such file",110);
        @unlink($fullpath);
        return false;
    }

    if(!preg_match("#\.pfx$#",$filename)){
        build_progress_import("$filename not a pfx file",110);
        @unlink($fullpath);
        return false;
    }

    if(is_file($SourceFile)){@unlink($SourceFile);}
    @copy($fullpath,$SourceFile);
    @unlink($fullpath);
    $passfile=$unix->FILE_TEMP();
    @file_put_contents($passfile,base64_decode($password));

    build_progress_import("{extract} {certificate}",20);
    $addon=null;
    $addon2=null;
    if(isset($GLOBALS["HarmpID"])){
        if (intval($GLOBALS["HarmpID"])>0){
            $addon=" -groupid {$GLOBALS["HarmpID"]}";
            $addon2=" --HarmpID={$GLOBALS["HarmpID"]}";
        }
    }

    $cmd="/usr/share/artica-postfix/bin/articarest -pfx $SourceFile -password $passfile$addon 2>&1";

    exec($cmd,$results);
    foreach ($results as $line){
        $line=trim($line);
        if(preg_match("#^ERROR:#",$line)){
            build_progress_import("$line",110);
            @unlink($passfile);
            @unlink($SourceFile);
            return false;
        }
        echo "$line\n";

    }
    @unlink($passfile);
    @unlink($SourceFile);
    @unlink($fullpath);
    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.certificates.center.php$addon2");
    build_progress_import(" {certificate} {success}",100);
    return true;

}
function import_p7r($filename){
    $unix=new unix();
    $filename=base64_decode($filename);
    $fullpath="/usr/share/artica-postfix/ressources/conf/upload/$filename";
    $SourceFile=$unix->TEMP_DIR()."/import.p7r";

    if(!is_file($fullpath)){
        echo "$fullpath no such file\n";
        build_progress_import("$filename no such file",110);
        @unlink($fullpath);
        return false;
    }

    if(!preg_match("#\.p7(r|b|c)$#",$filename)){
        build_progress_import("$filename not a pkcs7 file",110);
        @unlink($fullpath);
        return false;
    }
    //

    if(is_file($SourceFile)){@unlink($SourceFile);}
    @copy($fullpath,$SourceFile);
    @unlink($fullpath);
    $openssl=$unix->find_program("openssl");
    $certificate_file=$unix->TEMP_DIR()."/certificate.p7b";
    if(is_file($certificate_file)){@unlink($certificate_file);}
    $cmd="$openssl pkcs7 -inform der -in $SourceFile -out $certificate_file";
    echo "$cmd\n";
    shell_exec($cmd);
    @unlink($SourceFile);

    if(!is_file($certificate_file)){
        build_progress_import("{exporting} {certificate} {failed}",110);
        @unlink($SourceFile);
        return false;
    }


    build_progress_import("{checking} {certificate}",20);
    $f=explode("\n",@file_get_contents($certificate_file));
    $SCANNED=false;
    foreach ($f as $line){
        if(preg_match("#BEGIN PKCS7#",$line)){
            $SCANNED=true;
            break;
        }
    }
    if(!$SCANNED){
        build_progress_import("{checking} {certificate} PKCS7 {failed} ",110);
        @unlink($certificate_file);
        return false;
    }
    build_progress_import("{checking} {certificate}",50);
    $certificate_final=$unix->TEMP_DIR()."/certificate.crt";
    $cmd="$openssl pkcs7 -print_certs -in $certificate_file -out $certificate_final";
    echo "$cmd\n";
    shell_exec($cmd);

    @unlink($certificate_file);


    $fp = fopen($certificate_final, "r");
    $cert = fread($fp, 16192);
    fclose($fp);
    $array=openssl_x509_parse($cert);

    $CommonName=trim(strtolower($array["subject"]["CN"]));
    if($CommonName==null){
        if(isset($array["extensions"]["subjectAltName"])){
            $subjectAltName=$array["extensions"]["subjectAltName"];
            if(preg_match("#DNS:(.+?)[\s|,|$]#",$subjectAltName,$re)){
                $CommonName=$re[1];
            }
        }
    }

    $AsRoot=0;
    $q=new lib_sqlite(GetDatabase());
    $DateFrom=date("Y-m-d H:i:s",$array["validFrom_time_t"]);
    $DateTo=date("Y-m-d H:i:s",$array["validTo_time_t"]);
    $OrganizationName=$q->sqlite_escape_string2($array["subject"]["O"]);
    $OrganizationalUnit=$q->sqlite_escape_string2($array["subject"]["OU"]);

    if(isset($array["extensions"]["basicConstraints"])){
        if($array["extensions"]["basicConstraints"]=="CA:TRUE"){$AsRoot=1;}
    }


    $sql="SELECT  ID  FROM sslcertificates WHERE CommonName='$CommonName'";

    $certificate_data=$q->sqlite_escape_string2(@file_get_contents($certificate_final));
    $ligne=$q->mysqli_fetch_array($sql);


    if(intval($ligne["ID"])==0){
        $sql="INSERT INTO `sslcertificates` (CommonName,`privkey`,`crt`,`UsePrivKeyCrt`,`DateFrom`,`DateTo`,`OrganizationName`,`OrganizationalUnit`,`AsRoot`,`Generated`)
        VALUES ('$CommonName','','$certificate_data',1,'$DateFrom','$DateTo','$OrganizationName','$OrganizationalUnit','$AsRoot','1')";
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            echo $q->mysql_error;
            @unlink($certificate_final);
            build_progress_import("$filename SQL Error",110);
            return false;

        }
    }else{
        build_progress_import("$CommonName: {alreadyexists}",110);
        $ID=intval($ligne["ID"]);
        $sql="UPDATE `sslcertificates` SET 
                             privkey='',
                             crt='$certificate_data',
                             DateFrom='$DateFrom',
                             DateTo='$DateTo',
                             OrganizationName='$OrganizationName',
                             OrganizationalUnit='$OrganizationalUnit',
                             AsRoot='$AsRoot',
                             Generated=1,
                             UsePrivKeyCrt=1 WHERE ID=$ID";
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            echo $q->mysql_error;
            @unlink($certificate_final);
            build_progress_import("$filename SQL Error",110);
            return false;

        }

    }
    @unlink($certificate_final);
    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.certificates.center.php");
    build_progress_import(" {certificate} $CommonName {success}",100);
    return true;

}
function read_certificate($certificate_file):bool{
    $fp = fopen($certificate_file, "r");
    $cert = fread($fp, 16192);
    fclose($fp);
    $array=openssl_x509_parse($cert);
    print_r($array);
    return true;

}
function import_backup($filename){

    $filename=base64_decode($filename);
    $fullpath="/usr/share/artica-postfix/ressources/conf/upload/$filename";

    if(!is_file($fullpath)){
        echo "$fullpath no such file\n";
        build_progress_import("$filename no such file",110);
        return;
    }





    if(preg_match("#\.ccc$#",$filename)){
        $q=new lib_sqlite(GetDatabase());
        $data=unserialize(base64_decode(@file_get_contents($fullpath)));

        foreach ($data as $key=>$content){
            if($key=="ID"){continue;}
            $fields[]="`$key`";
            $DATAS[]="'".$q->sqlite_escape_string2($content)."'";

        }
        build_progress_import("{importing}",50);
        patchSQL();
        @unlink($fullpath);
        $sql="INSERT INTO sslcertificates (".@implode(",",$fields).") VALUES (".@implode(",",$DATAS).")";
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            build_progress_import($q->mysql_error,110);
            return;
        }
        build_progress_import("{success}",100);
        return;

    }

    $q=new lib_sqlite($fullpath);

    if(!$q->TABLE_EXISTS("sslcertificates")){
        build_progress_import("$filename no such database or missing sslcertificates table",110);
        @unlink($fullpath);
        return;
    }



    @unlink(GetDatabase());
    @copy($fullpath, GetDatabase());
    @unlink($fullpath);
    @chmod(GetDatabase(),0777);
    build_progress_import("$filename {success}",100);


}


function easyrsa_client($CommonName,$ClientName){
    $unix=new unix();
    $openssl=$unix->find_program("openssl");
    $rm=$unix->find_program("rm");
    $q=new lib_sqlite(GetDatabase());
    $MasterCommonName=$CommonName;
    $CommonNamePath=str_replace("*", "_ALL_", $CommonName);
    $RSA_ROOT="/usr/share/artica-postfix/bin/EasyRSA-3.0.0";
    $sql="SELECT *  FROM sslcertificates WHERE CommonName='$CommonName'";
    echo "$sql\n";
    $RSA_ROOT="/usr/share/artica-postfix/bin/EasyRSA-3.0.0";

    $ligne=$q->mysqli_fetch_array($sql);
    if($ligne["CommonName"]==null){$ligne["CommonName"]="*";}

    echo "CommonName..........: $CommonName\n";
    echo "CountryName.........: {$ligne["CountryName"]}\n";
    echo "stateOrProvinceName.: {$ligne["stateOrProvinceName"]}\n";
    echo "OrganizationName....: {$ligne["OrganizationName"]}\n";
    $CommonName_commandline=$CommonName;
    $AsProxyCertificate=intval($ligne["AsProxyCertificate"]);
    //if($AsProxyCertificate==1){$CommonName_commandline=null;}
    build_progress_x509("$CommonName",20);

    if($ligne["CountryName"]==null){$ligne["CountryName"]="UNITED STATES_US";}
    if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
    if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
    if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
    if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
    if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}
    if($ligne["levelenc"]<1024){$ligne["levelenc"]=4096;}
    if(preg_match("#^.*?_(.+)#", $ligne["CountryName"],$re)){$C=$re[1];}else{$C=$ligne["CountryName"];}
    $ST=$ligne["stateOrProvinceName"];
    $L=$ligne["localityName"];
    $O=$ligne["OrganizationName"];
    $OU=$ligne["OrganizationalUnit"];

    if(strpos(" $CommonName", ".")>0){
        $T=explode(".",$CommonName);
        unset($T[0]);
        $MasterCommonName=@implode(".", $T);
    }


    $TARGET_DIR="/etc/ssl/certificates/tmp/$CommonNamePath";
    if(is_dir($TARGET_DIR)){system("$rm -rf $TARGET_DIR");}
    @mkdir($TARGET_DIR,0755,true);
    build_progress_x509("$CommonName",25);

    $f[]="set_var EASYRSA	\"$RSA_ROOT\"";
    $f[]="set_var EASYRSA_OPENSSL	\"$openssl\"";
    $f[]="set_var EASYRSA_PKI		\"$TARGET_DIR/pki\"";
    $f[]="# Choices are:";
    $f[]="#   cn_only  - use just a CN value";
    $f[]="#   org      - use the \"traditional\" Country/Province/City/Org/OU/email/CN format";
    $f[]="set_var EASYRSA_DN	\"org\"";
    $f[]="set_var EASYRSA_REQ_COUNTRY	\"$C\"";
    $f[]="set_var EASYRSA_REQ_PROVINCE	\"{$ligne["stateOrProvinceName"]}\"";
    $f[]="set_var EASYRSA_REQ_CITY	\"{$ligne["localityName"]}\"";
    $f[]="set_var EASYRSA_REQ_ORG	\"{$ligne["OrganizationName"]}\"";
    $f[]="set_var EASYRSA_REQ_EMAIL	\"{$ligne["emailAddress"]}\"";
    $f[]="set_var EASYRSA_REQ_OU		\"{$ligne["OrganizationalUnit"]}\"";
    $f[]="set_var EASYRSA_KEY_SIZE	{$ligne["levelenc"]}";
    $f[]="set_var EASYRSA_ALGO		sha1";
    $f[]="set_var EASYRSA_CURVE		secp384r1";
    $f[]="set_var EASYRSA_CA_EXPIRE	3650";
    $f[]="set_var EASYRSA_CERT_EXPIRE	3650";
    $f[]="set_var EASYRSA_CRL_DAYS	180";
    $f[]="set_var EASYRSA_NS_SUPPORT	\"no\"";
    $f[]="set_var EASYRSA_NS_COMMENT	\"Artica Generated Certificate\"";
    $f[]="set_var EASYRSA_TEMP_FILE	\"\$EASYRSA_PKI/extensions.temp\"";
    $f[]="#alias awk=\"/alt/bin/awk\"";
    $f[]="#alias cat=\"/alt/bin/cat\"";
    $f[]="set_var EASYRSA_EXT_DIR	\"\$EASYRSA/x509-types\"";
    $f[]="set_var EASYRSA_SSL_CONF	\"\$EASYRSA/openssl-1.0.cnf\"";
    $f[]="set_var EASYRSA_REQ_CN		\"$MasterCommonName\"";
    $f[]="set_var EASYRSA_DIGEST		\"sha256\"";
    $f[]="set_var EASYRSA_SUBJ		\"/C=$C/ST=$ST/L=$L/O=$O/OU=$OU\"";
    $f[]="";
    @file_put_contents("/usr/share/artica-postfix/bin/EasyRSA-3.0.0/vars", @implode("\n", $f));

    echo
    @mkdir("$TARGET_DIR/pki",0755,true);
    @mkdir("$TARGET_DIR/pki/issued",0755,true);
    @mkdir("$TARGET_DIR/pki/private",0755,true);
    @mkdir("$TARGET_DIR/pki/reqs",0755,true);
    @mkdir("$TARGET_DIR/pki/certs_by_serial",0755,true);

    $T=explode(".",$CommonName);
    unset($T[0]);
    $MasterCommonName=@implode(".", $T);


    @file_put_contents("$TARGET_DIR/pki/ca.crt", $ligne["srca"]);
    @file_put_contents("$TARGET_DIR/pki/index.txt", "\n");
    @file_put_contents("$TARGET_DIR/pki/index.txt.attr", "unique_subject = yes\n");
    @file_put_contents("$TARGET_DIR/pki/serial", "01\n");
    @copy("/etc/squid3/ssl/dhparam.pem", "$TARGET_DIR/pki/dh.pem");
    @file_put_contents("$TARGET_DIR/pki/reqs/$MasterCommonName.req", $ligne["csr"]);
    @file_put_contents("$TARGET_DIR/pki/private/$MasterCommonName.key", $ligne["privkey"]);
    @file_put_contents("$TARGET_DIR/pki/private/ca.key", $ligne["privkey"]);
    @file_put_contents("$TARGET_DIR/pki/issued/$MasterCommonName.crt", $ligne["crt"]);


}
function restore($filename=null){

    $unix=new unix();
    $nice=$unix->EXEC_NICE();
    $gunzip=$unix->find_program("gunzip");
    $mysql=$unix->find_program("mysql");
    $q=new lib_sqlite(GetDatabase());
    $SourceFile=dirname(__FILE__)."/ressources/conf/upload/$filename";
    if(!is_file($SourceFile)){
        echo "$SourceFile no such file\n";
        build_progress_x509("{restore} {failed}",110);
        return;

    }

    build_progress_x509("{restore}...",80);
    $pattern_cmdline=$q->MYSQL_CMDLINES;
    $prefix="$mysql $pattern_cmdline artica_backup";
    $cmd=trim("$nice $gunzip -c $SourceFile |$prefix");
    system("$cmd");
    @unlink($SourceFile);
    build_progress_x509("{restore} {success}",100);


}

function backup(){
    build_progress_x509("Dump sslcertificates",110);
    die("function not available");

}

function easyrsa_csr($CommonName){
    $unix=new unix();
    $openssl=$unix->find_program("openssl");
    $rm=$unix->find_program("rm");
    $q=new lib_sqlite(GetDatabase());
    $MasterCommonName=$CommonName;

    $CommonNamePath=str_replace("*", "_ALL_", $CommonName);
    build_progress_x509("**** [$CommonName]**** (easyrsa_csr)",15);
    $openssl=$unix->find_program("openssl");
    $sql="SELECT *  FROM sslcertificates WHERE CommonName='$CommonName'";
    echo "$sql\n";
    $RSA_ROOT="/usr/share/artica-postfix/bin/EasyRSA-3.0.0";
    if(is_dir("$RSA_ROOT/pki")){shell_exec("$rm -rf $RSA_ROOT/pki");}

    build_progress_x509("$CommonName",15);
    $ligne=$q->mysqli_fetch_array($sql);


    echo "CommonName..........: $CommonName\n";
    build_progress_x509("$CommonName",20);

    $TARGET_DIR="/etc/ssl/certificates/tmp/$CommonNamePath";
    echo "CommonNamePath......: {$CommonNamePath}\n";
    echo "TARGET_DIR..........: {$TARGET_DIR}\n";
    if(is_dir($TARGET_DIR)){system("$rm -rf $TARGET_DIR");}
    @mkdir($TARGET_DIR,0755,true);
    build_progress_x509("$CommonName",25);

    $f=easyrsa_buildvars($ligne,$MasterCommonName,$RSA_ROOT,$openssl,$TARGET_DIR);
    @file_put_contents("/usr/share/artica-postfix/bin/EasyRSA-3.0.0/vars", @implode("\n", $f));



    build_progress_x509("$CommonName",30);

    if(!is_dir("$TARGET_DIR/reqs")){
        build_progress_x509("$CommonName",35);
        @chmod("$RSA_ROOT/easyrsa",0755);
        chdir($RSA_ROOT);
        echo "easyrsa init-pki --batch\n";
        system("./easyrsa init-pki --batch");

    }

    build_progress_x509("$CommonName",40);
    if(!is_dir("$TARGET_DIR/pki/reqs")){
        echo "init PKI failed ($TARGET_DIR/pki/reqs) no such dir.\n";
        build_progress_x509("init PKI failed.",110);
        return;
    }

    build_progress_x509("Generating Certificates",75);
    @chmod("$RSA_ROOT/easyrsa",0755);
    chdir($RSA_ROOT);
    echo "easyrsa gen-req \"$MasterCommonName\" nopass batch\n";
    system("./easyrsa gen-req \"$MasterCommonName\" nopass batch");

    if(!is_file("$TARGET_DIR/pki/reqs/$MasterCommonName.req")){
        echo "init CSR failed ($TARGET_DIR/reqs/$MasterCommonName.req) no such file.\n";
        build_progress_x509("init CSr failed.",110);
        return;

    }
    if(!is_file("$TARGET_DIR/pki/private/$MasterCommonName.key")){
        echo "init Private key failed ($TARGET_DIR/private/$MasterCommonName.key) no such file.\n";
        build_progress_x509("init CSr failed.",110);
        return;
    }

    $csr=sqlite_escape_string2(@file_get_contents("$TARGET_DIR/pki/reqs/$MasterCommonName.req"));
    $keydata=sqlite_escape_string2(@file_get_contents("$TARGET_DIR/pki/private/$MasterCommonName.key"));

    $sql="UPDATE sslcertificates SET `csr`='$csr',`privkey`='$keydata' WHERE CommonName='$CommonName'";
    $q=new lib_sqlite(GetDatabase());
    $q->QUERY_SQL($sql);

    if(!$q->ok){
        echo $q->mysql_error;
        build_progress_x509("MySQL PKI failed.",110);
        return;
    }

    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.certificates.center.php");
    build_progress_x509("{CSR} Success",100);



}

function easyrsa_cnf(){
    $f[]="# For use with Easy-RSA 3.0 and OpenSSL 1.0.*";
    $f[]="";
    $f[]="RANDFILE		= \$ENV::EASYRSA_PKI/.rnd";
    $f[]="####################################################################";
    $f[]="[ ca ]";
    $f[]="default_ca	= CA_default		# The default ca section";
    $f[]="";
    $f[]="####################################################################";
    $f[]="[ CA_default ]";
    $f[]="";
    $f[]="dir		= \$ENV::EASYRSA_PKI	# Where everything is kept";
    $f[]="certs		= \$dir			# Where the issued certs are kept";
    $f[]="crl_dir		= \$dir			# Where the issued crl are kept";
    $f[]="database	= \$dir/index.txt	# database index file.";
    $f[]="new_certs_dir	= \$dir/certs_by_serial	# default place for new certs.";
    $f[]="";
    $f[]="certificate	= \$dir/ca.crt	 	# The CA certificate";
    $f[]="serial		= \$dir/serial 		# The current serial number";
    $f[]="crl		= \$dir/crl.pem 		# The current CRL";
    $f[]="private_key	= \$dir/private/ca.key	# The private key";
    $f[]="RANDFILE	= \$dir/.rand		# private random number file";
    $f[]="";
    $f[]="x509_extensions	= basic_exts		# The extentions to add to the cert";
    $f[]="";
    $f[]="# This allows a V2 CRL. Ancient browsers don't like it, but anything Easy-RSA";
    $f[]="# is designed for will. In return, we get the Issuer attached to CRLs.";
    $f[]="crl_extensions	= crl_ext";
    $f[]="";
    $f[]="default_days	= \$ENV::EASYRSA_CERT_EXPIRE	# how long to certify for";
    $f[]="default_crl_days= \$ENV::EASYRSA_CRL_DAYS	# how long before next CRL";
    $f[]="default_md	= \$ENV::EASYRSA_DIGEST		# use public key default MD";
    $f[]="preserve	= no			# keep passed DN ordering";
    $f[]="";
    $f[]="# A few difference way of specifying how similar the request should look";
    $f[]="# For type CA, the listed attributes must be the same, and the optional";
    $f[]="# and supplied fields are just that :-)";
    $f[]="policy		= policy_anything";
    $f[]="";
    $f[]="# For the 'anything' policy, which defines allowed DN fields";
    $f[]="[ policy_anything ]";
    $f[]="countryName		= optional";
    $f[]="stateOrProvinceName	= optional";
    $f[]="localityName		= optional";
    $f[]="organizationName	= optional";
    $f[]="organizationalUnitName	= optional";
    $f[]="commonName		= supplied";
    $f[]="name			= optional";
    $f[]="emailAddress		= optional";
    $f[]="";
    $f[]="####################################################################";
    $f[]="# Easy-RSA request handling";
    $f[]="# We key off \$DN_MODE to determine how to format the DN";
    $f[]="[ req ]";
    $f[]="default_bits		= \$ENV::EASYRSA_KEY_SIZE";
    $f[]="default_keyfile 	= privkey.pem";
    $f[]="default_md		= \$ENV::EASYRSA_DIGEST";
    $f[]="distinguished_name	= \$ENV::EASYRSA_DN";
    $f[]="x509_extensions		= easyrsa_ca	# The extentions to add to the self signed cert";
    $f[]="basicConstraints = CA:FALSE";
    $f[]="keyUsage = nonRepudiation, digitalSignature, keyEncipherment";
    $f[]="";
    $f[]="# A placeholder to handle the \$EXTRA_EXTS feature:";
    $f[]="#%EXTRA_EXTS%	# Do NOT remove or change this line as \$EXTRA_EXTS support requires it";
    $f[]="";
    $f[]="####################################################################";
    $f[]="# Easy-RSA DN (Subject) handling";
    $f[]="";
    $f[]="# Easy-RSA DN for cn_only support:";
    $f[]="[ cn_only ]";
    $f[]="commonName		= Common Name (eg: your user, host, or server name)";
    $f[]="commonName_max		= 64";
    $f[]="commonName_default	= \$ENV::EASYRSA_REQ_CN";
    $f[]="";
    $f[]="# Easy-RSA DN for org support:";
    $f[]="[ org ]";
    $f[]="countryName			= Country Name (2 letter code)";
    $f[]="countryName_default		= \$ENV::EASYRSA_REQ_COUNTRY";
    $f[]="countryName_min			= 2";
    $f[]="countryName_max			= 2";
    $f[]="";
    $f[]="stateOrProvinceName		= State or Province Name (full name)";
    $f[]="stateOrProvinceName_default	= \$ENV::EASYRSA_REQ_PROVINCE";
    $f[]="";
    $f[]="localityName			= Locality Name (eg, city)";
    $f[]="localityName_default		= \$ENV::EASYRSA_REQ_CITY";
    $f[]="";
    $f[]="0.organizationName		= Organization Name (eg, company)";
    $f[]="0.organizationName_default	= \$ENV::EASYRSA_REQ_ORG";
    $f[]="";
    $f[]="organizationalUnitName		= Organizational Unit Name (eg, section)";
    $f[]="organizationalUnitName_default	= \$ENV::EASYRSA_REQ_OU";
    $f[]="";
    $f[]="commonName			= Common Name (eg: your user, host, or server name)";
    $f[]="commonName_max			= 64";
    $f[]="commonName_default		= \$ENV::EASYRSA_REQ_CN";
    $f[]="";
    $f[]="emailAddress			= Email Address";
    $f[]="emailAddress_default		= \$ENV::EASYRSA_REQ_EMAIL";
    $f[]="emailAddress_max		= 64";
    $f[]="";
    $f[]="####################################################################";
    $f[]="# Easy-RSA cert extension handling";
    $f[]="";
    $f[]="# This section is effectively unused as the main script sets extensions";
    $f[]="# dynamically. This core section is left to support the odd usecase where";
    $f[]="# a user calls openssl directly.";
    $f[]="[ basic_exts ]";
    $f[]="subjectKeyIdentifier	= hash";
    $f[]="authorityKeyIdentifier	= keyid,issuer:always";
    $f[]="";
    $f[]="# The Easy-RSA CA extensions";
    $f[]="[ easyrsa_ca ]";
    $f[]="subjectKeyIdentifier=hash";
    $f[]="authorityKeyIdentifier=keyid:always,issuer:always";
    $f[]="keyUsage = cRLSign, keyCertSign";
    $f[]="basicConstraints = CA:true";
    $f[]="";
    $f[]="# nsCertType omitted by default. Let's try to let the deprecated stuff die.";
    $f[]="# nsCertType = sslCA";
    $f[]="";
    $f[]="# CRL extensions.";
    $f[]="[ crl_ext ]";
    $f[]="";
    $f[]="# Only issuerAltName and authorityKeyIdentifier make any sense in a CRL.";
    $f[]="";
    $f[]="# issuerAltName=issuer:copy";
    $f[]="authorityKeyIdentifier=keyid:always,issuer:always";
    $f[]="";
    @file_put_contents("/usr/share/artica-postfix/bin/EasyRSA-3.0.0/openssl-1.0.cnf", @implode("\n", $f));
}

function easyrsa_buildvars($ligne,$MasterCommonName,$RSA_ROOT,$openssl,$TARGET_DIR){

    if($ligne["CountryName"]==null){$ligne["CountryName"]="UNITED STATES_US";}
    if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
    if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
    if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
    if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
    if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}
    if($ligne["levelenc"]<1024){$ligne["levelenc"]=4096;}
    if(preg_match("#^.*?_(.+)#", $ligne["CountryName"],$re)){$C=$re[1];}else{$C=$ligne["CountryName"];}
    $ST=$ligne["stateOrProvinceName"];
    $L=$ligne["localityName"];
    $O=$ligne["OrganizationName"];
    $OU=$ligne["OrganizationalUnit"];
    $MasterCommonName=str_replace("_ALL_", "*", $MasterCommonName);
    echo "EASYRSA_PKI.........: $TARGET_DIR/pki\n";
    echo "CountryName.........: {$ligne["CountryName"]}\n";
    echo "stateOrProvinceName.: {$ligne["stateOrProvinceName"]}\n";
    echo "OrganizationName....: {$ligne["OrganizationName"]}\n";
    echo "Commoon Name (build vars).: $MasterCommonName\n";

    $f[]="set_var EASYRSA	\"$RSA_ROOT\"";
    $f[]="set_var EASYRSA_OPENSSL	\"$openssl\"";
    $f[]="set_var EASYRSA_PKI		\"$TARGET_DIR/pki\"";
    $f[]="# Choices are:";
    $f[]="#   cn_only  - use just a CN value";
    $f[]="#   org      - use the \"traditional\" Country/Province/City/Org/OU/email/CN format";
    $f[]="set_var EASYRSA_KEY_SIZE {$ligne["levelenc"]}";
    $f[]="set_var EASYRSA_DN	\"org\"";
    $f[]="set_var EASYRSA_REQ_COUNTRY	\"$C\"";
    $f[]="set_var EASYRSA_REQ_PROVINCE	\"{$ligne["stateOrProvinceName"]}\"";
    $f[]="set_var EASYRSA_REQ_CITY	\"{$ligne["localityName"]}\"";
    $f[]="set_var EASYRSA_REQ_ORG	\"{$ligne["OrganizationName"]}\"";
    $f[]="set_var EASYRSA_REQ_EMAIL	\"{$ligne["emailAddress"]}\"";
    $f[]="set_var EASYRSA_REQ_OU		\"{$ligne["OrganizationalUnit"]}\"";
    $f[]="set_var EASYRSA_KEY_SIZE	{$ligne["levelenc"]}";
    $f[]="set_var EASYRSA_ALGO		rsa";
    $f[]="set_var EASYRSA_CURVE		secp384r1";
    $f[]="set_var EASYRSA_CA_EXPIRE	3650";
    $f[]="set_var EASYRSA_CERT_EXPIRE	3650";
    $f[]="set_var EASYRSA_CRL_DAYS	180";
    $f[]="set_var EASYRSA_NS_SUPPORT	\"no\"";
    $f[]="set_var EASYRSA_NS_COMMENT	\"Artica Generated Certificate\"";
    $f[]="set_var EASYRSA_TEMP_FILE	\"\$EASYRSA_PKI/extensions.temp\"";
    $f[]="#alias awk=\"/alt/bin/awk\"";
    $f[]="#alias cat=\"/alt/bin/cat\"";
    $f[]="set_var EASYRSA_EXT_DIR	\"\$EASYRSA/x509-types\"";
    $f[]="set_var EASYRSA_SSL_CONF	\"\$EASYRSA/openssl-1.0.cnf\"";
    $f[]="set_var EASYRSA_REQ_CN		\"$MasterCommonName\"";
    $f[]="set_var EASYRSA_DIGEST		\"sha256\"";
    $f[]="set_var EASYRSA_SUBJ		\"/C=$C/ST=$ST/L=$L/O=$O/OU=$OU\"";
    $f[]="";
    return $f;
}

function easyrsa_vars($CommonName){

    $unix=new unix();
    $openssl=$unix->find_program("openssl");
    $rm=$unix->find_program("rm");
    $q=new lib_sqlite(GetDatabase());
    $MasterCommonName=$CommonName;


    $xtempfile=base64_decode("L29wdC9xbHByb3h5L2V0Yy9zeXN0ZW0uanNvbg==");
    $xtempconf=base64_decode("ewoJInNxdWlkX3VzZXIiOiAic3F1aWQiLAoJInNxdWlkX2V4ZV9wYXRoIjoiL3Vzci9zYmluL3NxdWlkIiwKCSJzcXVpZF9jb25mX3BhdGgiOiIvZXRjL3NxdWlkMy9zcXVpZC5jb25mIiwKCSJzc2xjcnRkX2V4ZV9wYXRoIjoiL2xpYi9zcXVpZDMvc3NsX2NydGQiLAoJInNxdWlkX2RhZW1vbl9uYW1lIjoic3F1aWQiLAoJInNzbGNydGRfZGlyX3BhdGgiOiIvdmFyL2xpYi9zcXVpZC9zZXNzaW9uL3NzbC9zc2xfZGIiLAoJIm9wZW5zc2xfZXhlX3BhdGgiOiIvdXNyL2Jpbi9vcGVuc3NsIiwKCSJ1c2VfcGVla19uX3NwbGljZSIgOiB0cnVlCn0K");
    $xtempdir=dirname($xtempfile);
    easyrsa_cnf();




    build_progress_x509("$CommonName",15);
    $openssl=$unix->find_program("openssl");
    $CommonName_sql=str_replace("_ALL_", "*", $CommonName);
    $sql="SELECT *  FROM sslcertificates WHERE CommonName='$CommonName_sql'";
    $CommonName=str_replace("*", "_ALL_", $CommonName);
    $RSA_ROOT="/usr/share/artica-postfix/bin/EasyRSA-3.0.0";

    $ligne=$q->mysqli_fetch_array($sql);

    if(!$q->ok){
        build_progress_x509("$CommonName MySQL Error",110);
        echo $q->mysql_error."\n";
    }

    if($ligne["CommonName"]==null){$ligne["CommonName"]="*";}
    $CommonNamePath=str_replace("*", "_ALL_", $CommonName);
    echo $xtempconf."\n";
    @mkdir($xtempdir,0755,true);
    @file_put_contents($xtempfile, $xtempconf);
    if(is_dir("$RSA_ROOT/pki")){shell_exec("$rm -rf $RSA_ROOT/pki");}
    if(preg_match("#^.*?_(.+)#", $ligne["CountryName"],$re)){$C=$re[1];}else{$C=$ligne["CountryName"];}
    echo "CommonName..........: $CommonName\n";
    echo "CountryName.........: {$ligne["CountryName"]} ($C)\n";
    echo "stateOrProvinceName.: {$ligne["stateOrProvinceName"]}\n";
    echo "localityName........: {$ligne["localityName"]}\n";
    echo "OrganizationName....: {$ligne["OrganizationName"]}\n";
    echo "OrganizationalUnit..: {$ligne["OrganizationalUnit"]}\n";
    echo "CommonNamePath......: {$CommonNamePath}\n";
    echo "eMail...............: {$ligne["emailAddress"]}\n";
    $CountryName=$ligne["CountryName"];
    $stateOrProvinceName=$ligne["stateOrProvinceName"];
    $localityName=$ligne["localityName"];
    $OrganizationName=$ligne["OrganizationName"];
    $OrganizationalUnit=$ligne["OrganizationalUnit"];
    $eMail=$ligne["emailAddress"];


    $CommonName_commandline=$CommonName;
    $AsProxyCertificate=intval($ligne["AsProxyCertificate"]);
    //if($AsProxyCertificate==1){$CommonName_commandline=null;}
    build_progress_x509("$CommonName",20);


    if(strpos(" $CommonName", ".")>0){
        $T=explode(".",$CommonName);
        unset($T[0]);
        $MasterCommonName=@implode(".", $T);
    }


    $TARGET_DIR="/etc/ssl/certificates/tmp/$CommonNamePath";
    $TARGET_DIR=str_replace("*", "_ALL_", $TARGET_DIR);
    if(is_dir($TARGET_DIR)){system("$rm -rf $TARGET_DIR");}
    echo "Target Directory....: {$TARGET_DIR}\n";
    @mkdir($TARGET_DIR,0755,true);
    build_progress_x509("$CommonName",25);
    echo "Path $TARGET_DIR\n";

    $f=easyrsa_buildvars($ligne,$MasterCommonName,$RSA_ROOT,$openssl,$TARGET_DIR);
    @file_put_contents("/usr/share/artica-postfix/bin/EasyRSA-3.0.0/vars", @implode("\n", $f));

    build_progress_x509("$TARGET_DIR/reqs",30);
    echo "- easyrsa init-pki --batch - \n";
    if(!is_dir("$TARGET_DIR/reqs")){
        build_progress_x509("$CommonName",35);
        @chmod("$RSA_ROOT/easyrsa",0755);
        chdir($RSA_ROOT);
        echo "easyrsa init-pki --batch\n";
        system("./easyrsa init-pki --batch");

    }

    build_progress_x509("$TARGET_DIR/pki/reqs",40);
    if(!is_dir("$TARGET_DIR/pki/reqs")){
        echo "init PKI failed. -$TARGET_DIR/pki/reqs - no such file\n";
        build_progress_x509("init PKI failed.",110);
        return;
    }

    build_progress_x509("$TARGET_DIR/ca.crt",45);
    if(!is_file("$TARGET_DIR/ca.crt")){
        build_progress_x509("$CommonName",50);
        echo "Building CA certificate file\n";
        @chmod("$RSA_ROOT/easyrsa",0755);
        chdir($RSA_ROOT);
        system("./easyrsa build-ca nopass");
    }

    if(!is_file("$TARGET_DIR/pki/ca.crt")){
        echo "$TARGET_DIR/pki/ca.crt no such file\n";
        echo "CA certificate file failed.\n";
        build_progress_x509("CA certificate file failed.",110);
        return;

    }

    build_progress_x509("$TARGET_DIR/pki/ca.crt",60);
    $CAFile="$TARGET_DIR/pki/ca.crt";

    if(!is_file($CAFile)){
        echo "$CAFile no such file\n";
        build_progress_x509("Generating Certificate Authority failed.",110);
        return;
    }

    $srca=sqlite_escape_string2(@file_get_contents($CAFile));

    build_progress_x509("{backup} Certificate Authority",62);
    $CommonName_sql=str_replace("_ALL_", "*", $CommonName);
    echo "UPDATE sslcertificates SET `srca`='.../...' WHERE CommonName='$CommonName_sql'\n";
    $sql="UPDATE sslcertificates SET `srca`='$srca' WHERE CommonName='$CommonName_sql'";

    if($GLOBALS["OUTPUT"]){echo $sql."\n";}

    $q->QUERY_SQL($sql);
    if(!$q->ok){
        build_progress_x509("MYSQL Error `srca`",110);
        echo $q->mysql_error."\n$sql\n";
        return;
    }



    if(is_file("/etc/squid3/ssl/dhparam.pem")){
        echo "Copy /etc/squid3/ssl/dhparam.pem -> $TARGET_DIR/pki/dh.pem\n";
        @copy("/etc/squid3/ssl/dhparam.pem", "$TARGET_DIR/pki/dh.pem");
    }else{
        echo "/etc/squid3/ssl/dhparam.pem no such file ( first SSL install ???)\n";
    }

    if(!is_file("$TARGET_DIR/pki/dh.pem")){
        build_progress_x509("{generating_dh_parameters}",65);
        echo "Generating DH parameters\n";
        @chmod("$RSA_ROOT/easyrsa",0755);
        chdir($RSA_ROOT);
        system("./easyrsa gen-dh");

    }




    if(!is_file("$TARGET_DIR/pki/dh.pem")){
        echo "$TARGET_DIR/pki/dh.pem no such file\n";
        echo "Generating DH parameters failed\n";
        build_progress_x509("{generating_dh_parameters} {failed}.",110);
        return;
    }

    build_progress_x509("Generating DH parameters",70);
    if(!is_file("/etc/squid3/ssl/dhparam.pem")){
        @mkdir("/etc/squid3/ssl",0755,true);
        echo "Copy $TARGET_DIR/pki/dh.pem -> /etc/squid3/ssl/dhparam.pem\n";
        @copy("$TARGET_DIR/pki/dh.pem", "/etc/squid3/ssl/dhparam.pem");
    }

    build_progress_x509("Generating Certificates",75);
    @chmod("$RSA_ROOT/easyrsa",0755);
    chdir($RSA_ROOT);
    echo "L.".__LINE__."\n";
    echo "- easyrsa build-server-full \"$CommonName_sql\" nopass -\n";
    system("./easyrsa build-server-full \"$CommonName_sql\" nopass");


    $filetemp=$unix->FILE_TEMP();
    $CMDZ[]="/usr/share/artica-postfix/bin/certificate-center";
    if(is_file($filetemp)){@unlink($filetemp);}

    echo "filetemp:$filetemp L.".__LINE__."\n";


    $CMDZ[]="-action=create-root-certificate";
    $CMDZ[]="-common-name=\"$CommonName_sql\"";
    $CMDZ[]="-country=\"$C\"";
    $CMDZ[]="-e-mail=\"$eMail\"";
    $CMDZ[]="-organization=\"{$OrganizationName}\"";
    $CMDZ[]="-organizational-unit=\"{$OrganizationalUnit}\"";
    $CMDZ[]="-province=\"{$stateOrProvinceName}\"";
    $CMDZ[]="-city=\"{$localityName}\"";
    $CMDZ[]="-output=\"$filetemp\"";

    $cmddline=@implode(" ", $CMDZ);
    system($cmddline);
    echo $cmddline."\n";
    shell_exec("$rm -rf $xtempdir");



    if(!is_file($filetemp)){
        echo "$filetemp, No such file N".__LINE__."\n";
        build_progress_x509("Generating Root Certificate failed.",110);
        return;
    }

    $DER_PATH="$TARGET_DIR/pki/private/$MasterCommonName.der";
    echo "DER_PATH = $DER_PATH\n";
    build_progress_x509("Generating Certificate in DER format.",76);
    echo "$openssl x509 -in $filetemp -outform DER -out $DER_PATH\n";
    shell_exec("$openssl x509 -in $filetemp -outform DER -out $DER_PATH");

    if(!is_file($filetemp)){
        @unlink($filetemp);
        echo "$DER_PATH no such file\n";
        build_progress_x509("Generating DER Certificate failed.",110);
        return;
    }

    $tempdata=@file_get_contents($filetemp);
    echo "tempdata len =".strlen($tempdata)."\n";

    $SquidCertPath="$TARGET_DIR/pki/issued/proxy.$MasterCommonName.crt";
    $SquidkeyPath="$TARGET_DIR/pki/private/proxy.$MasterCommonName.key";

    if(!preg_match("#-----BEGIN PRIVATE KEY-----(.*?)-----END PRIVATE KEY-----.*?-----BEGIN CERTIFICATE-----(.*?)-----END CERTIFICATE-----#is",$tempdata,$re)){
        build_progress_x509("Parsing Root Certificate failed.",110);
        return;
    }

    echo "Proxy Certificate Content....: ".strlen($re[1])." bytes\n";
    echo "Proxy Private Key Content....: ".strlen($re[2])." bytes\n";

    @file_put_contents($SquidkeyPath,"-----BEGIN PRIVATE KEY-----{$re[1]}-----END PRIVATE KEY-----\n");
    @file_put_contents($SquidCertPath,"-----BEGIN CERTIFICATE-----{$re[2]}-----END CERTIFICATE-----\n");
    @unlink($filetemp);


    $PrivateKey="$TARGET_DIR/pki/private/$CommonName_sql.key";

    $Certificate="$TARGET_DIR/pki/issued/$CommonName_sql.crt";

    $CSR="$TARGET_DIR/pki/reqs/$CommonName_sql.req";


    if(!is_file($PrivateKey)){
        echo "$PrivateKey no such file\n";
        echo "Private Key failed\n";
        build_progress_x509("Private Key failed.",110);
        return;

    }
    if(!is_file($Certificate)){
        echo "$Certificate no such file\n";
        echo "Certificate failed\n";
        build_progress_x509("Certificate failed",110);
        return;
    }
    if(!is_file($CSR)){
        echo "$CSR no such file\n";
        echo "Certificate Request failed\n";
        build_progress_x509("Certificate Request failed",110);
        return;
    }


    build_progress_x509("{compressing} {all}",77);
    $tempfile=$unix->FILE_TEMP();
    system("cd $TARGET_DIR");
    @chdir($TARGET_DIR);
    $tar=$unix->find_program("tar");
    system("$tar -czf $tempfile.tar.gz *");
    if(!is_file("$tempfile.tar.gz")){
        echo "Compressing $TARGET_DIR to $tempfile.tar.gz failed.\n";
        build_progress_x509("{compressing} failed",110);
        return;
    }
    $easyrsabackup=sqlite_escape_string2(base64_encode(@file_get_contents("$tempfile.tar.gz")));
    @unlink("$tempfile.tar.gz");

    build_progress_x509("{backup} {all}",78);
    $sql="UPDATE sslcertificates SET `easyrsabackup`='$easyrsabackup' WHERE CommonName='$CommonName_sql'";

    $q->QUERY_SQL($sql);
    if(!$q->ok){
        build_progress_x509("MYSQL Error On easyrsabackup",110);
        echo $q->mysql_error."\n$sql\n";
        return;
    }


    $csr=sqlite_escape_string2(@file_get_contents($CSR));
    $privkey=sqlite_escape_string2(@file_get_contents($PrivateKey));
    $crt=sqlite_escape_string2(@file_get_contents($Certificate));


    $SquidCert=sqlite_escape_string2(@file_get_contents($SquidCertPath));
    $Squidkey=sqlite_escape_string2(@file_get_contents($SquidkeyPath));
    $DerFile=sqlite_escape_string2(base64_encode(@file_get_contents($DER_PATH)));

    echo "Proxy Certificate MySQL......: ".strlen($SquidCert)." bytes\n";
    echo "Proxy Private Key MySQL......: ".strlen($Squidkey)." bytes\n";



    if($GLOBALS["OUTPUT"]){echo "Save privkey and csr for $CommonName_sql\n";}

    build_progress_x509("{backup} {certificate}",79);
    $sql="UPDATE sslcertificates SET `easyrsa`=1, `DerContent`='$DerFile' WHERE CommonName='$CommonName_sql'";
    if($GLOBALS["OUTPUT"]){echo $sql."\n";}
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        build_progress_x509("MYSQL Error 1",110);
        echo $q->mysql_error."\n$sql\n";
        return;
    }


    build_progress_x509("{backup} {certificate}",80);
    $sql="UPDATE sslcertificates SET `easyrsa`=1, `privkey`='$privkey' WHERE CommonName='$CommonName_sql'";
    if($GLOBALS["OUTPUT"]){echo $sql."\n";}
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        build_progress_x509("MYSQL Error 1",110);
        echo $q->mysql_error."\n$sql\n";
        return;
    }
    build_progress_x509("{backup} {certificate}",81);
    $sql="UPDATE sslcertificates SET `csr`='$csr' WHERE CommonName='$CommonName_sql'";
    if($GLOBALS["OUTPUT"]){echo $sql."\n";}
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        build_progress_x509("MYSQL Error 2",110);
        echo $q->mysql_error."\n$sql\n";
        return;
    }



    build_progress_x509("{backup} {certificate}",82);
    $sql="UPDATE sslcertificates SET `crt`='$crt' WHERE CommonName='$CommonName_sql'";
    if($GLOBALS["OUTPUT"]){echo $sql."\n";}
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        build_progress_x509("MYSQL Error 3",110);
        echo $q->mysql_error."\n$sql\n";
        return;
    }



    build_progress_x509("{backup} {certificate}",84);
    $sql="UPDATE sslcertificates SET `SquidCert`='$SquidCert' WHERE CommonName='$CommonName_sql'";
    if($GLOBALS["OUTPUT"]){echo $sql."\n";}
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        build_progress_x509("MYSQL Error 4",110);
        echo $q->mysql_error."\n$sql\n";
        return;
    }
    build_progress_x509("{backup} {certificate}",85);
    $sql="UPDATE sslcertificates SET `Squidkey`='$Squidkey' WHERE CommonName='$CommonName_sql'";
    if($GLOBALS["OUTPUT"]){echo $sql."\n";}
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        build_progress_x509("MYSQL Error 4",110);
        echo $q->mysql_error."\n$sql\n";
        return;
    }

    build_progress_x509("{building} PFX {certificate}",85);
    build_pfx($CommonName_sql);
    UpdateCertificateInfos($CommonName_sql);
    build_progress_x509("$CommonName_sql {success}",100);


}


function buildkey($CommonName,$notparsing=false){
    $unix=new unix();
    $openssl=$unix->find_program("openssl");
    $CommonName=str_replace("_ALL_", "*", $CommonName);





    $directory="/etc/openssl/certificate_center/".md5($CommonName);
    if(!is_file($openssl)){
        echo "openssl.......: No such binary, aborting...\n";
    }
    $q=new lib_sqlite(GetDatabase());
    $sql="SELECT *  FROM sslcertificates WHERE CommonName='$CommonName'";

    if($GLOBALS["OUTPUT"]){echo $sql."\n";}

    $ligne=$q->mysqli_fetch_array($sql);
    if($ligne["CommonName"]==null){$ligne["CommonName"]="*";}

    echo "CommonName..........: $CommonName\n";
    echo "CountryName.........: {$ligne["CountryName"]}\n";
    echo "stateOrProvinceName.: {$ligne["stateOrProvinceName"]}\n";
    echo "OrganizationName....: {$ligne["OrganizationName"]}\n";



    if($ligne["CountryName"]==null){$ligne["CountryName"]="UNITED STATES_US";}
    if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
    if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
    if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
    if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
    if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}



    if($ligne["levelenc"]<1024){$ligne["levelenc"]=4096;}



    $ST=$ligne["stateOrProvinceName"];
    $L=$ligne["localityName"];
    $O=$ligne["OrganizationName"];
    $OU=$ligne["OrganizationalUnit"];
    if(preg_match("#^.*?_(.+)#", $ligne["CountryName"],$re)){$C=$re[1];}

    $CommonName_commandline=$CommonName;
    $AsProxyCertificate=intval($ligne["AsProxyCertificate"]);
    if($AsProxyCertificate==1){$CommonName_commandline=null;}


    $CommonName_commandline=$CommonName;
    $AsProxyCertificate=intval($ligne["AsProxyCertificate"]);
    if($AsProxyCertificate==1){$CommonName_commandline=null;}

    if($GLOBALS["OUTPUT"]){echo "As proxy certificate: $AsProxyCertificate [".__LINE__."]";}

    @mkdir($directory,0755,true);
    $cmd="$openssl req -nodes -newkey rsa:{$ligne["levelenc"]} -nodes -keyout $directory/myserver.key -out $directory/server.csr -subj \"/C=$C/ST=$ST/L=$L/O=$O/OU=$OU/CN=$CommonName_commandline\" 2>&1";
    if($GLOBALS["OUTPUT"]){echo $cmd."\n";}
    exec($cmd,$results);
    if($GLOBALS["OUTPUT"]){echo @implode("\n", $results)."\n";}

    $csr=sqlite_escape_string2(@file_get_contents("$directory/server.csr"));
    $privkey=sqlite_escape_string2(@file_get_contents("$directory/myserver.key"));

    if($GLOBALS["OUTPUT"]){echo "Save privkey and csr for $CommonName\n";}

    $sql="UPDATE sslcertificates SET `privkey`='$privkey',`Squidkey`='$privkey',`csr`='$csr' WHERE CommonName='$CommonName'";
    if($GLOBALS["OUTPUT"]){echo $sql."\n";}
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n";}

    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --pass >/dev/null 2>&1 &");
    if(!$notparsing){UpdateCertificateInfos($CommonName,1);}


}

function passphrase(){
    $unix=new unix();
    $ldap=new clladp();
    $q=new lib_sqlite(GetDatabase());
    $sql="SELECT servername,sslcertificate  FROM freeweb WHERE LENGTH(sslcertificate)>0";
    @mkdir("/etc/apache2/ssl-tools",0755,true);

    $data[]="#!/bin/sh";
    $data[]="STR=$1";
    $data[]="STR2=`expr match \"\$STR\" '\(.*\?\):'`";

    $results=$q->QUERY_SQL($sql,'artica_backup');
    while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
        $servername=$ligne["servername"];
        $CommonName=$ligne["sslcertificate"];
        $sql="SELECT password from sslcertificates WHERE CommonName='$CommonName'";
        $ligneZ=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
        if($ligneZ["password"]==null){$ligneZ["password"]=$ldap->ldap_password;}
        $data[]="[ \"$servername\" = \$STR2 ] && echo \"{$ligneZ["password"]}\"";
    }
    $data[]="";


    @file_put_contents("/etc/apache2/ssl-tools/sslpass.sh", @implode("\n", $data));
    @chmod("/etc/apache2/ssl-tools/sslpass.sh", 0755);
}


function x509($CommonName){
    $CommonName=str_replace("_ALL_", "*", $CommonName);
    $unix=new unix();
    $ldap=new clladp();
    $directory="/etc/openssl/certificate_center/".md5($CommonName);

    build_progress_x509("Certificate for $CommonName",5);
    if($GLOBALS["OUTPUT"]){echo "[".__LINE__."] Direcory: $directory\n";}

    @mkdir($directory,0644,true);
    $openssl=$unix->find_program("openssl");
    $cp=$unix->find_program("cp");
    if(!is_file($openssl)){echo "[".__LINE__."] openssl.......: No such binary, aborting...\n";
        build_progress_x509("No such binary, aborting",110);
        return;
    }

    $q=new lib_sqlite(GetDatabase());
    $q->BuildTables();

    $sql="SELECT *  FROM sslcertificates WHERE 3='$CommonName'";
    if($GLOBALS["OUTPUT"]){echo "[".__LINE__."] $sql\n";}
    $ligne=$q->mysqli_fetch_array($sql);
    if($GLOBALS["OUTPUT"]){echo "Loading MySQL parameters\n";}
    if(!$q->ok){
        if($GLOBALS["OUTPUT"]){echo "[".__LINE__."] $q->mysql_error\n";}
        build_progress_x509("MySQL error",110);
    }


    if($ligne["CommonName"]==null){
        build_progress_x509("MySQL return a null CommonName",110);
        echo "[".__LINE__."] MySQL return a null CommonName For `$CommonName`, aborting...\n";return;}

    $csr="$directory/server.csr";
    $privkey="$directory/myserver.key";
    if(!is_file($csr)){if(strlen($ligne["csr"])>10){@file_put_contents($csr, $ligne["csr"]);}}
    if(!is_file($privkey)){if(strlen($ligne["privkey"])>10){@file_put_contents($privkey, $ligne["privkey"]);}}
    if(!is_file($privkey)){buildkey($CommonName);}

    if(!is_file($privkey)){
        echo "$privkey no such file\n";
        return;
    }

    if(!is_file($csr)){echo "$csr no such file\n";return;}
    $CertificateMaxDays=intval($ligne["CertificateMaxDays"]);
    if($CertificateMaxDays<5){$CertificateMaxDays=730;}
    if($ligne["CountryName"]==null){$ligne["CountryName"]="UNITED STATES_US";}
    if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
    if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
    if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
    if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
    if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}
    if(trim($ligne["password"])==null){$ligne["password"]=$ldap->ldap_password;}
    if(preg_match("#^.*?_(.+)#", $ligne["CountryName"],$re)){$C=$re[1];}
    $ST=$ligne["stateOrProvinceName"];
    $L=$ligne["localityName"];
    $O=$ligne["OrganizationName"];
    $OU=$ligne["OrganizationalUnit"];
    @unlink("$directory/.rnd");
    @unlink("$directory/serial.old");
    @unlink("$directory/index.txt.attr");
    @unlink("$directory/index.txt.old");
    @unlink("$directory/rnd");

    @file_put_contents("$directory/rand", "0");
    @file_put_contents("$directory/serial.txt", "01");
    @file_put_contents("$directory/serial", "01");
    shell_exec("$cp /dev/null $directory/index.txt");
    putenv("HOME=/root");
    putenv("RANDFILE=$directory/rnd");
    system("env");



    build_progress_x509("Creating openssl settings",10);
    $f[]="HOME			= $directory";
    $f[]="RANDFILE		= $directory/rnd";
    $f[]="oid_section		= new_oids";
    $f[]="";
    $f[]="[ new_oids ]";
    $f[]="";
    $f[]="[ ca ]";
    $f[]="default_ca	= CA_default		# The default ca section";
    $f[]="[ CA_default ]";
    $f[]="dir		= $directory		# Where everything is kept";
    $f[]="certs		= $directory		# Where the issued certs are kept";
    $f[]="crl_dir		= $directory		# Where the issued crl are kept";
    $f[]="database	= $directory/index.txt	# database index file.";
    $f[]="new_certs_dir	= $directory		# default place for new certs.";
    $f[]="certificate	= $directory/server.crt 	# The CA certificate";
    $f[]="serial		= $directory/serial 		# The current serial number";
    $f[]="crlnumber	= $directory/crlnumber	# the current crl number";
    $f[]="crl		= $directory/crl.pem 		# The current CRL";
    $f[]="private_key	= $directory/myserver.key";

    $f[]="x509_extensions	= usr_cert		# The extentions to add to the cert";
    $f[]="name_opt 	= ca_default		# Subject Name options";
    $f[]="cert_opt 	= ca_default		# Certificate field options";
    $f[]="default_days	= $CertificateMaxDays";
    $f[]="default_crl_days= 30			# how long before next CRL";
    $f[]="default_md	= sha1			# which md to use.";
    $f[]="preserve	= no			# keep passed DN ordering";
    $f[]="policy		= policy_match";
    $f[]="";
    $f[]="[SAN]";
    $f[]="subjectAltName=DNS:$CommonName";
    $f[]="";
    $f[]="[ policy_match ]";
    $f[]="countryName			= optional";
    $f[]="stateOrProvinceName	= optional";
    $f[]="organizationName		= optional";
    $f[]="organizationalUnitName	= optional";
    $f[]="commonName			= supplied";
    $f[]="emailAddress			= optional";
    $f[]="";
    $f[]="[ policy_anything ]";
    $f[]="countryName			= optional";
    $f[]="stateOrProvinceName	= optional";
    $f[]="localityName			= optional";
    $f[]="organizationName		= optional";
    $f[]="organizationalUnitName	= optional";
    $f[]="commonName			= supplied";
    $f[]="emailAddress			= optional";
    $f[]="";
    $f[]="[ req ]";
    $f[]="default_bits		= 1024";
    $f[]="default_keyfile 	= privkey.pem";
    $f[]="distinguished_name	= req_distinguished_name";
    $f[]="attributes		= req_attributes";
    $f[]="x509_extensions	= v3_ca	# The extentions to add to the self signed cert";
    $f[]="input_password = {$ligne["password"]}";
    $f[]="output_password = {$ligne["password"]}";
    $f[]="string_mask = nombstr";
    $f[]="subjectAltName=DNS:$CommonName";
    $f[]="";
    $f[]="[ req_distinguished_name ]";
    $f[]="countryName				= $C";
    $f[]="countryName_default		= $C";
    $f[]="countryName_min			= 2";
    $f[]="countryName_max			= 2";
    $f[]="stateOrProvinceName		= {$ligne["stateOrProvinceName"]}";
    $f[]="localityName				= {$ligne["localityName"]}";
    $f[]="0.organizationName		= {$ligne["OrganizationName"]}";
    $f[]="0.organizationName_default= {$ligne["OrganizationName"]}";
    $f[]="organizationalUnitName	= {$ligne["OrganizationalUnit"]}";
    $f[]="commonName				= $CommonName";
    $f[]="commonName_max			= 64";
    $f[]="emailAddress				= {$ligne["emailAddress"]}";

    echo "[".__LINE__."] emailAddress = {$ligne["emailAddress"]}\n";

    $f[]="emailAddress_max		= ".strlen($ligne["emailAddress"]);
    $f[]="";
    $f[]="[ req_attributes ]";
    $f[]="challengePassword		= A challenge password";
    $f[]="challengePassword_min		= 4";
    $f[]="challengePassword_max		= 20";
    $f[]="unstructuredName		= An optional company name";
    $f[]="";
    $f[]="[ usr_cert ]";
    $f[]="basicConstraints=CA:FALSE";
    $f[]="nsComment			= \"OpenSSL Generated Certificate\"";
    $f[]="subjectKeyIdentifier=hash";
    $f[]="authorityKeyIdentifier=keyid,issuer";
    $f[]="[ v3_req ]";
    $f[]="basicConstraints = CA:FALSE";
    $f[]="keyUsage = nonRepudiation, digitalSignature, keyEncipherment";
    $f[]="";
    $f[]="[ v3_ca ]";
    $f[]="keyUsage = cRLSign, keyCertSign";

    $f[]="[ crl_ext ]";
    $f[]="authorityKeyIdentifier=keyid:always,issuer:always";
    $f[]="";
    $f[]="[ proxy_cert_ext ]";
    $f[]="basicConstraints=CA:FALSE";
    $f[]="nsComment			= \"OpenSSL Generated Certificate\"";
    $f[]="subjectKeyIdentifier=hash";
    $f[]="authorityKeyIdentifier=keyid,issuer:always";
    $f[]="proxyCertInfo=critical,language:id-ppl-anyLanguage,pathlen:3,policy:foo";
    echo "[".__LINE__."] Writing $directory/openssl.cf\n";
    @file_put_contents("$directory/openssl.cf", @implode("\n",$f));

    @chdir($directory);
    $server_cert="$directory/server.crt";
    $DefaultSubject="-subj \"/C=$C/ST=$ST/L=$L/O=$O/OU=$OU/CN=$CommonName\"";
    echo "\n";
    echo "[".__LINE__."] ************************************************************************\n";
    echo "[".__LINE__."] DefaultSubject = $DefaultSubject\n";
    $cmd="$openssl x509 -req -CAcreateserial -days $CertificateMaxDays -in $csr -signkey $privkey -out $server_cert -sha1 2>&1";
    echo "[".__LINE__."] $cmd\n";
    echo "\n";
    echo "[".__LINE__."] ************************************************************************\n";
    echo "[".__LINE__."] $cmd\n";
    build_progress_x509("Creating certificate",15);
    exec($cmd,$results0);

    foreach ($results0 as $num=>$ligneLine){
        if(preg_match("#unable#i", $ligneLine)){
            echo "[".__LINE__."] ************************** ERROR DETECTED !!! **************************\n";
            echo $ligneLine."\n";
            echo "[".__LINE__."] ************************************************************************\n\n";
            build_progress_x509("ERROR DETECTED !!!",110);
            return;
        }
        echo "[".__LINE__."] $ligneLine\n";

    }

    if(!is_file($server_cert)){
        echo "[".__LINE__."] ************************** ERROR DETECTED !!! **************************\n";
        echo "[".__LINE__."] $directory/server.crt No such file !\n";
        echo "[".__LINE__."] ************************************************************************\n\n";
        build_progress_x509("server.crt No such file !",110);
        return;
    }

    $ligne["password"]=escapeshellcmd($ligne["password"]);
    echo "[".__LINE__."] ************************************************************************\n\n";
    if(is_file("$directory/rnd")){
        echo "Removing $directory/rnd\n";
        @unlink("$directory/rnd");
        @touch("$directory/rnd");
        @chmod("$directory/rnd",0644);
    }

    if(is_file("$directory/cakey.pem")){@unlink("$directory/cakey.pem");}
    build_progress_x509("Generating private key ",20);
    $cmd="$openssl genrsa -des3 -rand $directory/rand -passout pass:{$ligne["password"]} -out $directory/cakey.pem 4096 2>&1";
    echo "\n";
    echo "[".__LINE__."] ************************************************************************\n$cmd\n";
    exec($cmd,$results1);

    foreach ($results1 as $num=>$ligneLine){
        if(preg_match("#unable#i", $ligneLine)){
            echo "[".__LINE__."] ************************** ERROR DETECTED !!! **************************\n";
            echo "[".__LINE__."] $ligneLine\n";
            echo "[".__LINE__."] ************************************************************************\n\n";
            build_progress_x509("ERROR DETECTED !!!",110);
            return;
        }
        echo "[".__LINE__."] $ligneLine\n";

    }
    echo "[".__LINE__."] ************************************************************************\n\n";

    $cakeySize=@filesize("$directory/cakey.pem");
    echo "[".__LINE__."] cakey.pem: $cakeySize bytes";
    if($cakeySize==0){
        echo "[".__LINE__."] ************************** ERROR DETECTED !!! **************************\n";
        echo "[".__LINE__."] $directory/cakey.pem O bytes!!!\n";
        echo "[".__LINE__."] ************************************************************************\n\n";
        build_progress_x509("cakey.pem O bytes!!!",110);
        return;
    }

    build_progress_x509("Signing the key ",30);
    $cmdS=array();
    $cmdS[]="$openssl req -new -sha1 -config $directory/openssl.cf";
    $cmdS[]=$DefaultSubject;
    $cmdS[]="-reqexts SAN";
    $cmdS[]="-key $directory/cakey.pem -out $directory/ca.csr";
    $cmdS[]=" 2>&1";
    $cmd=@implode(" ", $cmdS);
    echo "\n************************************************************************\n$cmd\n************************************************************************\n";
    exec($cmd,$results2);

    $ERRR=false;
    echo "[".__LINE__."] Procedure #".__LINE__."\n";
    foreach ($results2 as $num=>$ligneLine){
        if(preg_match("#(unable|error)#i", $ligneLine)){
            echo "[".__LINE__."] ************************** ERROR DETECTED !!! **************************\n";
            echo "[".__LINE__."] $ligneLine\n";
            echo "[".__LINE__."] ************************************************************************\n\n";
            $ERRR=true;
            break;
        }
        echo "[".__LINE__."] $ligneLine\n";
    }

    if($ERRR){
        echo "Content of openssl.cf\n";
        echo @file_get_contents("$directory/openssl.cf");
        sleep(3);
        build_progress_x509("ERROR DETECTED !!!",110);
    }

    echo "[".__LINE__."] ************************************************************************\n\n";


// #####################################################################################################################

    $cmd="$openssl req -new -newkey rsa:1024 $DefaultSubject -days $CertificateMaxDays -nodes -x509 -keyout $directory/DynamicCert.pem -out $directory/DynamicCert.pem 2>&1";
    echo "[".__LINE__."] ************************************************************************\n";
    echo "$cmd\n";

    $results1=array();

    build_progress_x509("Signing the key ",40);
    exec($cmd,$results1);
    foreach ($results1 as $num=>$ligneLine){
        if(preg_match("#(unable|error)#i", $ligneLine)){
            echo "[".__LINE__."] ************************** ERROR DETECTED !!! **************************\n";
            echo "[".__LINE__."] $ligneLine\n";
            echo "[".__LINE__."] ************************************************************************\n\n";
            build_progress_x509("ERROR DETECTED !!!",110);
            return;
        }
        echo "[".__LINE__."] $ligneLine\n";

    }

    echo "[".__LINE__."] ************************************************************************\n\n";





// #####################################################################################################################

    build_progress_x509("Signing the key ",50);
    echo "[".__LINE__."] ************************************************************************\n";
    $cmd="$openssl x509 -in $directory/DynamicCert.pem -outform DER -out $directory/DynamicCert.der 2>&1";
    echo "$cmd\n";
    $results1=array();
    build_progress_x509("Signing the key ",50);
    exec($cmd,$results1);
    foreach ($results1 as $num=>$ligneLine){
        if(preg_match("#unable#i", $ligneLine)){
            echo "[".__LINE__."] ************************** ERROR DETECTED !!! **************************\n";
            echo "[".__LINE__."] $ligneLine\n";
            echo "[".__LINE__."] ************************************************************************\n\n";
            build_progress_x509("ERROR DETECTED !!!",51);

        }
        echo "[".__LINE__."] $ligneLine\n";

    }
// #####################################################################################################################



    build_progress_x509("Signing the key ",51);
    $cmdS=array();
    $cmdS[]="$openssl ca -batch -extensions v3_ca $DefaultSubject -days $CertificateMaxDays -out $directory/cacert-itermediate.pem";
    $cmdS[]="-in $directory/ca.csr -config $directory/openssl.cf";
    $cmdS[]="-cert $directory/server.crt";
    $cmd=@implode(" ", $cmdS);
    echo "[".__LINE__."] ************************************************************************\n";
    echo "$cmd\n";
    $results1=array();
    exec($cmd,$results1);
    foreach ($results1 as $num=>$ligneLine){
        if(preg_match("#unable#i", $ligneLine)){
            echo "[".__LINE__."] ************************** ERROR DETECTED !!! **************************\n";
            echo "[".__LINE__."] $ligneLine\n";
            echo "[".__LINE__."] ************************************************************************\n\n";
            return;
        }
        echo "[".__LINE__."] $ligneLine\n";

    }

    echo "[".__LINE__."] ************************************************************************\n\n";
// #####################################################################################################################
    $server_cert_content=@file_get_contents($server_cert);
    $intermediate_content=@file_get_contents("$directory/cacert-itermediate.pem");
    @file_put_contents("$directory/chain.crt", "$intermediate_content\n$server_cert_content");

//chain.crt = SSLCertificateChainFile

# make sure you are in the Intermediate CA folder and not in the Root CA one<br />
#cd /var/ca/ca2008/<br />
# create the private key<br />
#openssl genrsa -des3 -out toto.key 4096<br />
# generate a certificate sign request<br />
#openssl req -new -key toto.key -out toto.csr<br />
# sign the request with the Intermediate CA<br />
#openssl ca -config openssl.cnf -policy policy_anything -out toto.crt -infiles toto.csr<br />
# and store the server files in the certs/ directory<br />
#mkdir certs/{server_name}<br />
#mv {server_name}.key {server_name}.csr {server_name}.crt certs/<br />
    @unlink("$directory/.rnd");
    @unlink("$directory/serial.old");
    @unlink("$directory/index.txt.attr");
    @unlink("$directory/index.txt.old");
    @unlink("$directory/rnd");

    @file_put_contents("$directory/serial.txt", "01");
    @file_put_contents("$directory/serial", "01");
    shell_exec("$cp /dev/null $directory/index.txt");
    build_progress_x509("Signing the key ",52);
    $cmdS=array();
    $cmdS[]="$openssl ca -batch -config $directory/openssl.cf -passin pass:{$ligne["password"]}";
    $cmdS[]="-keyfile $directory/cakey.pem";
    $cmdS[]="-cert $directory/cacert-itermediate.pem -policy policy_anything -out $directory/MAIN.crt";
    $cmdS[]="-infiles $directory/ca.csr";
    $cmd=@implode(" ", $cmdS);
    echo "[".__LINE__."] ************************************************************************\n";
    echo "$cmd\n";
    $results1=array();
    exec($cmd,$results1);
    foreach ($results1 as $num=>$ligneLine){
        if(preg_match("#unable#i", $ligneLine)){
            echo "[".__LINE__."] ************************** ERROR DETECTED !!! **************************\n";
            echo "[".__LINE__."] $ligneLine\n";
            echo "[".__LINE__."] ************************************************************************\n\n";
            return;
        }
        echo "[".__LINE__."] $ligneLine\n";

    }

    echo "[".__LINE__."] ************************************************************************\n\n";
// #####################################################################################################################


    build_progress_x509("Saving certificates",55);
    $content=sqlite_escape_string2(@file_get_contents("$directory/MAIN.crt"));
    $bundle=sqlite_escape_string2(@file_get_contents("$directory/chain.crt"));
    $sql="UPDATE sslcertificates SET `crt`='$content',`bundle`='$bundle' WHERE CommonName='$CommonName'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n";}
    squid_autosigned($CommonName);
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    chdir("/root");
    shell_exec("$nohup $php5 ".__FILE__." --pass >/dev/null 2>&1 &");
    UpdateCertificateInfos($CommonName);
    build_progress_x509("{done}",100);

}

function echo_implode($array,$xline){
    foreach ($array as $num=>$line){
        echo "Line: $xline: $line\n";
    }

}


function openssl_failed($line){
    if(preg_match("#problems making Certificate Request#i", $line)){return true;}
    if(preg_match("#end of string encountered while#i", $line)){return true;}
    if(preg_match("#error:[0-9]+:#i", $line)){return true;}
}

function squid_autosigned($CommonName){
    echo "{warning} Depreciated function, please use easyrsa instead....\n";
    build_progress_x509("{failed}",110);
}

function build_der($CommonName){


    if($CommonName==null){
        build_progress_pkcs12("$CommonName No certificate set",110);
        return;
    }


    $unix=new unix();
    build_progress_pkcs12($CommonName,20);
    $q=new lib_sqlite(GetDatabase());
    if(!$q->FIELD_EXISTS("sslcertificates","DynamicCert","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `DynamicCert` TEXT NOT NULL";$q->QUERY_SQL($sql,'artica_backup');}
    $sql="SELECT `UsePrivKeyCrt`,`crt`,`csr`,`srca`,`clientkey`,`clientcert`,`DynamicCert`,`privkey`,`SquidCert`,`Squidkey`,`bundle`
	FROM sslcertificates WHERE CommonName='$CommonName'";
    $ligne=$q->mysqli_fetch_array($sql);

    $Ca=$ligne["SquidCert"];
    @file_put_contents("/tmp/myCA.pem", $Ca);
    $openssl=$unix->find_program("openssl");
    system("$openssl x509 -in /tmp/myCA.pem -outform DER -out /tmp/myCA.der");

}

function build_pfx($CommonName){

    if($CommonName==null){
        build_progress_pkcs12("build_pfx(): $CommonName No certificate set",110);
        return;
    }


    $unix=new unix();
    $q=new lib_sqlite(GetDatabase());
    if(!$q->FIELD_EXISTS("sslcertificates","DynamicCert","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `DynamicCert` TEXT NOT NULL";$q->QUERY_SQL($sql,'artica_backup');}
    $sql="SELECT `UsePrivKeyCrt`,`crt`,`csr`,`srca`,`clientkey`,`clientcert`,`DynamicCert`,`privkey`,`SquidCert`,`Squidkey`,`bundle`
	FROM sslcertificates WHERE CommonName='$CommonName'";
    $ligne=$q->mysqli_fetch_array($sql);

    if(!$q->ok){
        echo $q->mysql_error."\n";
        build_progress_pkcs12("$CommonName MySQL error",110);
        return;
    }

    build_progress_x509("{building} PFX {certificate}",86);
    $UsePrivKeyCrt=$ligne["UsePrivKeyCrt"];
    echo "UsePrivKeyCrt..........: build_pfx():  $UsePrivKeyCrt\n";

    $private_key_data=$ligne["Squidkey"];
    $certificate_content=$ligne["SquidCert"];


    if($UsePrivKeyCrt==1){
        $private_key_data=$ligne["privkey"];
        $certificate_content=$ligne["crt"];
    }

    echo "Private Key............: build_pfx(): ". strlen($private_key_data)."\n";
    echo "$private_key_data\n";
    echo "Certificate............: build_pfx(): ". strlen($certificate_content)."\n";


    if(strlen($private_key_data)<20){
        echo "Use the server pricate key from /etc/openssl/private-key/privkey.key\n";
        $private_key_data=@file_get_contents("/etc/openssl/private-key/privkey.key");
    }
    if(strlen($certificate_content)<20){
        build_progress_x509("{building} PFX {certificate} {failed}",87);
        return;
    }
    $openssl=$unix->find_program("openssl");
    $path=$unix->TEMP_DIR();
    @file_put_contents("$path/server.key", $private_key_data);
    @file_put_contents("$path/server.crt", $certificate_content);

    @unlink("$path/no.pwd.server.key");
    system("$openssl rsa -in $path/server.key -out $path/no.pwd.server.key");
    if(!is_file("$path/no.pwd.server.key")){
        build_progress_pkcs12("RSA KEY {failed}",110);
        return;
    }
    $certificate_content_no_pass=@file_get_contents("$path/no.pwd.server.key");
    build_progress_pkcs12("Merging data",50);

    @file_put_contents("$path/no.pwd.server.pem", $certificate_content_no_pass."\n".$certificate_content);
    $sock=new sockets();
    $PfxPassword=$sock->GET_INFO("PfxPassword");


    $f[]="$openssl pkcs12 -export -in $path/server.crt";
    $f[]="-inkey $path/no.pwd.server.key -certfile $path/no.pwd.server.pem";
    $f[]="-passout pass:$PfxPassword";
    $f[]="-out $path/server.pfx";
    $cmdline=@implode(" ", $f);
    echo $cmdline."\n";
    system($cmdline);
    if(!is_file("$path/server.pfx")){
        build_progress_pkcs12("PFX KEY {failed}",110);
        @unlink("$path/server.key");
        @unlink("$path/server.crt");
        @unlink("$path/server.pfx");
        @unlink("$path/no.pwd.server.key");
        @unlink("$path/no.pwd.server.pem");
        return;
    }
    $pks12=$q->sqlite_escape_string2(base64_encode(@file_get_contents("$path/server.pfx")));
    build_progress_x509("Save pks12 certificate: ".strlen($pks12),80);
    $q->QUERY_SQL("UPDATE sslcertificates SET pks12='$pks12' WHERE CommonName='$CommonName'","artica_backup");

    @unlink("$path/server.key");
    @unlink("$path/server.crt");
    @unlink("$path/server.pfx");
    @unlink("$path/no.pwd.server.key");
    @unlink("$path/no.pwd.server.pem");


    if(!$q->ok){
        build_progress_x509("Save pks12 certificate: ".strlen($pks12) ." {failed}",110);
        echo $q->mysql_error;
        return;
    }
    build_progress_x509("$CommonName PFX  {success}",100);
    return true;

}

function build_pkcs12($CommonName){
    $unix=new unix();

    $q=new lib_sqlite(GetDatabase());
    $sql="SELECT Squidkey,srca,SquidCert  FROM sslcertificates WHERE CommonName='$CommonName'";
    $ligne=$q->mysqli_fetch_array($sql);
    if(!$q->ok){echo "FATAL! $q->mysql_error\n";return;}

    $openssl=$unix->find_program("openssl");
    squid_validate($CommonName);
    $CommonName=str_replace("_ALL_", "*", $CommonName);
    $directory="/etc/openssl/certificate_center/".md5($CommonName);
    @mkdir($directory,0755,true);
    $tmpfile=time();

    if(trim($ligne["Squidkey"])==null){
        $ligne["Squidkey"]=$ligne["srca"];
    }

    @file_put_contents("$directory/$tmpfile.key", $ligne["Squidkey"]);
    @file_put_contents("$directory/$tmpfile.cert", $ligne["SquidCert"]);

    echo "Private key: $directory/$tmpfile.key\n";
    echo "Certificate: $directory/$tmpfile.cert\n";

    build_progress_x509("Build pks12 certificate",70);
    $cmdline="$openssl pkcs12 -keypbe PBE-SHA1-3DES -certpbe PBE-SHA1-3DES -export -in $directory/$tmpfile.cert -inkey $directory/$tmpfile.key -out $directory/$tmpfile.pks12 -password pass:\"\" -name \"$CommonName\"";

    $resultsCMD=array();
    exec($cmdline,$resultsCMD);

    foreach ($resultsCMD as $line){
        if($GLOBALS["OUTPUT"]){echo "[".__LINE__."] $line\n";}
        if(openssl_failed($line)){
            build_progress_x509("{failed}",110);
            return false;
        }

    }
    if(!is_file("$directory/$tmpfile.pks12")){build_progress_x509("Save pks12 failed",70);return false;}
    $pks12=sqlite_escape_string2(base64_encode(@file_get_contents("$directory/$tmpfile.pks12")));
    build_progress_x509("Save pks12 certificate: ".strlen($pks12),80);
    $q->QUERY_SQL("UPDATE sslcertificates SET pks12='$pks12' WHERE CommonName='$CommonName'","artica_backup");


    @unlink("$directory/$tmpfile.pks12");
    @unlink("$directory/$tmpfile.key");
    @unlink("$directory/$tmpfile.cert");


    if(!$q->ok){
        build_progress_x509("Save pks12 certificate: ".strlen($pks12) ." {failed}",110);
        echo $q->mysql_error;
        return false;
    }

    return true;

}

function build_client_side_certificate($CommonName){
    $unix=new unix();
    $ldap=new clladp();
    $q=new lib_sqlite(GetDatabase());
    $sql="SELECT *  FROM sslcertificates WHERE CommonName='$CommonName'";
    $ligne=$q->mysqli_fetch_array($sql);
    if(!$q->ok){echo "FATAL! $q->mysql_error\n";return;}

    $CertificateMaxDays=intval($ligne["CertificateMaxDays"]);
    if($CertificateMaxDays<5){$CertificateMaxDays=730;}
    if($ligne["CountryName"]==null){$ligne["CountryName"]="UNITED STATES_US";}
    if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
    if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
    if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
    if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
    if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}
    if($ligne["password"]==null){$ligne["password"]=$ldap->ldap_password;}
    $ligne["password"]=escapeshellcmd($ligne["password"]);
    if(preg_match("#^.*?_(.+)#", $ligne["CountryName"],$re)){$C=$re[1];}
    $ST=$ligne["stateOrProvinceName"];
    $L=$ligne["localityName"];
    $O=$ligne["OrganizationName"];
    $OU=$ligne["OrganizationalUnit"];

    $openssl=$unix->find_program("openssl");

    $CommonName=str_replace("_ALL_", "*", $CommonName);
    $directory="/etc/openssl/certificate_center/".md5($CommonName);
    @mkdir($directory,0755,true);
    $tmpfile=time();

    $CommonName=str_replace("_ALL_", "*", $CommonName);
    $directory="/etc/openssl/certificate_center/".md5($CommonName);
    @mkdir($directory,0755,true);
    $tmpfile=time();

    if(trim($ligne["Squidkey"])==null){
        $ligne["Squidkey"]=$ligne["srca"];
    }

    @file_put_contents("$directory/$tmpfile.key", $ligne["Squidkey"]);
    @file_put_contents("$directory/$tmpfile.cert", $ligne["SquidCert"]);

    $subj="-subj \"/C=$C/ST=$ST/L=$L/O=$O/OU=$OU/CN=$CommonName\"";

    echo "Private key: $directory/$tmpfile.key\n";
    echo "Certificate: $directory/$tmpfile.cert\n";




    $cmd="$openssl genrsa -des3 -passout pass:{$ligne["password"]} -out $directory/client.key 2048 ";
    echo $cmd."\n";system($cmd);
    $cmd="$openssl req -new -key $directory/client.key -passin pass:{$ligne["password"]} -out $directory/client.csr $subj";
    echo $cmd."\n";system($cmd);

    $cmd="$openssl x509 -req -days $CertificateMaxDays -in $directory/client.csr ";
    $cmd=$cmd."-CA $directory/$tmpfile.cert -CAkey $directory/$tmpfile.key ";
    $cmd=$cmd."-set_serial 01 -out $directory/client.crt";
    echo $cmd."\n";system($cmd);


    echo "curl -v -s -k --key $directory/client.key --cert $directory/client.crt https://example.com\n";

    exit();

    $cmdline="$openssl pkcs12 -keypbe PBE-SHA1-3DES -certpbe PBE-SHA1-3DES -export -in $directory/client.mysite.crt -inkey $directory/client.mysite.key -out $directory/$tmpfile.pks12 -password pass:\"\" -name \"$CommonName\"";
    system($cmdline);
    $pks12=sqlite_escape_string2(base64_encode(@file_get_contents("$directory/$tmpfile.pks12")));

    build_progress_x509("Save pks12 certificate: ".strlen($pks12),70);
    $q->QUERY_SQL("UPDATE sslcertificates SET pks12='$pks12' WHERE CommonName='$CommonName'");
    if(!$q->ok){echo $q->mysql_error;}

    @unlink("$directory/$tmpfile.pks12");
    @unlink("$directory/$tmpfile.key");
    @unlink("$directory/$tmpfile.cert");


}


function squid_validate($CommonName){
    $q=new lib_sqlite(GetDatabase());
    $tt=time();
    $sql="SELECT Squidkey,SquidCert  FROM sslcertificates WHERE CommonName='$CommonName'";
    $ligne=$q->mysqli_fetch_array($sql);
    @mkdir("/etc/ssl/certs",0755,true);
    $CommonName=str_replace("_ALL_", "*", $CommonName);
    $directory="/etc/openssl/certificate_center/".md5($CommonName);


    @file_put_contents("/etc/ssl/certs/$CommonName.key", $ligne["Squidkey"]);
    @file_put_contents("/etc/ssl/certs/$CommonName.cert", $ligne["SquidCert"]);




}


function GetSubj($CommonName){
    $q=new lib_sqlite(GetDatabase());
    $sql="SELECT *  FROM sslcertificates WHERE CommonName='$CommonName'";
    $ligne=$q->mysqli_fetch_array($sql);

    if($ligne["CountryName"]==null){$ligne["CountryName"]="UNITED STATES_US";}
    if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
    if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
    if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
    if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
    if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}

    $ST=$ligne["stateOrProvinceName"];
    $L=$ligne["localityName"];
    $O=$ligne["OrganizationName"];
    $OU=$ligne["OrganizationalUnit"];


    if(preg_match("#^.*?_(.+)#", $ligne["CountryName"],$re)){$C=$re[1];}

    $subj=" -subj \"/C=$C/ST=$ST/L=$L/O=$O/OU=$OU/CN=$CommonName\" ";
    return $subj;
}


function autosigned_certificate_server_client($CommonName){openssl_pkcs12($CommonName);}

function build_progress_pkcs12($text,$pourc){
    build_progress_x509($text,$pourc);
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/openssl.x509.progress";
    echo "[{$pourc}%] $text\n";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["CACHEFILE"],0755);
    if($GLOBALS["OUTPUT"]){sleep(1);}
}

function openssl_pkcs12($CommonName){
    echo "Depreciated function openssl_pkcs12, please use easyrsa instead...\n";
    build_progress_pkcs12("$CommonName...{failed}",110);
}


function UpdateCertificateInfos($CommonName,$UseCSR=0,$ID=0,$table="sslcertificates"){
    $unix=new unix();
    if($GLOBALS["VERBOSE"]){echo "Extract from $CommonName\n";}

    $q=new lib_sqlite(GetDatabase());
    if($ID>0){$FILTER="WHERE ID=$ID";}else{$FILTER="WHERE CommonName='$CommonName'";}
    $sql="SELECT ID,`crt`,`csr`,`SquidCert`,`UsePrivKeyCrt`,privkey,Squidkey,UseLetsEncrypt,CommonName,bundle  FROM $table $FILTER";
    $ligne=$q->mysqli_fetch_array($sql);
    if(!isset($ligne["CommonName"])){
        $ligne["CommonName"]="";
    }
    if(!isset($ligne["UseLetsEncrypt"])){
        $ligne["UseLetsEncrypt"]="0";
    }
    if(!isset($ligne["bundle"])){
        $ligne["bundle"]=null;
    }
    $CommonName=$ligne["CommonName"];
    $UseLetsEncrypt=intval($ligne["UseLetsEncrypt"]);
    echo "$CommonName UseLetsEncrypt=$UseLetsEncrypt\n";
    $UsePrivKeyCrt=$ligne["UsePrivKeyCrt"];
    $privkey=$ligne["privkey"];
    $bundle=$ligne["bundle"];
    $crt=$ligne["crt"];
    $SquidCert=$ligne["SquidCert"];
    $Squidkey=$ligne["Squidkey"];
    $ID=$ligne["ID"];
    $C=null;
    $ST=null;
    $L=null;
    $O=null;
    $OU=null;
    $emailAddress=null;
    $privkey_content=$Squidkey;
    $certificate_content=$SquidCert;
    $data=array();

    if($UsePrivKeyCrt==1){
        $privkey_content=$privkey;
        $certificate_content=$crt;

    }

    if(strlen($certificate_content)==null){
        echo "Certificate content, no data -> SKIP\n";
       return false;
    }


    $data[]=$certificate_content;
    if(!is_null($bundle)) {
        if (strlen($bundle) > 100) {
            $data[] = $bundle;
        }
    }
    $data[]=$privkey_content;

    $tmpfile=$unix->FILE_TEMP().".$CommonName.crt";
    @file_put_contents($tmpfile,@implode("\n",$data));

    $fp = fopen($tmpfile, "r");
    $cert = fread($fp, 16192);
    fclose($fp);
    $array=openssl_x509_parse($cert);
    @unlink($tmpfile);

    echo "$CommonName $tmpfile -------------\n";

    if(isset($array["subject"]["ST"])){$ST=$array["subject"]["ST"];}
    if(isset($array["subject"]["C"])){$C=$array["subject"]["C"];}
    if(isset($array["subject"]["L"])){$L=$array["subject"]["L"];}
    if(isset($array["subject"]["L"])){$C=$array["subject"]["L"];}
    if(isset($array["subject"]["O"])){$O=$array["subject"]["O"];}
    if(isset($array["subject"]["OU"])){$OU=$array["subject"]["OU"];}
    if(isset($array["subject"]["CN"])){$CN=$array["subject"]["CN"];}
    if(isset($array["subject"]["emailAddress"])){$emailAddress=$array["subject"]["emailAddress"];}

    if(isset($array["issuer"]["ST"])){$ST=$array["issuer"]["ST"];}
    if(isset($array["issuer"]["C"])){$C=$array["issuer"]["C"];}
    if(isset($array["issuer"]["L"])){$L=$array["issuer"]["L"];}
    //if(isset($array["issuer"]["L"])){$L=$array["issuer"]["L"];}
    if(isset($array["issuer"]["O"])){$O=$array["issuer"]["O"];}
    if(isset($array["issuer"]["OU"])){$OU=$array["issuer"]["OU"];}
    if(isset($array["issuer"]["CN"])){$CN=$array["issuer"]["CN"];}
    if(isset($array["issuer"]["emailAddress"])){$emailAddress=$array["issuer"]["emailAddress"];}
    $validFrom=null;
    $validTo=null;
    if($array["validFrom_time_t"]<>null) {
        $validFrom = date("Y-m-d H:i:s", $array["validFrom_time_t"]);
    }
    if($array["validTo_time_t"]<>null) {
        $validTo=date("Y-m-d H:i:s",$array["validTo_time_t"]);
    }


    echo "ST...........: $ST\n";
    echo "L............: $L\n";
    echo "O............: $O\n";
    echo "O............: $O\n";
    echo "OU...........: $OU\n";
    echo "Email........: $emailAddress\n";
    $ST=str_replace("'","`",$ST);
    $C=str_replace("'","`",$C);
    $L=str_replace("'","`",$L);
    $O=str_replace("'","`",$O);
    $OU=str_replace("'","`",$OU);
    $emailAddress=str_replace("'","`",$emailAddress);
    $RootCa=IfCertisCA($CommonName);

    $fields=array();
    if($ST<>null){
        $fields[]="stateOrProvinceName='$ST'";
    }
    if($L<>null){
        $fields[]="localityName='$L'";
    }
    if($O<>null){
        $fields[]="OrganizationName='$O'";
    }
    if($OU<>null){
        $fields[]="OrganizationalUnit='$OU'";
    }
    if($emailAddress<>null){
        $fields[]="emailAddress='$emailAddress'";
    }
    if($C<>null){
        $fields[]="CountryName='$C'";
    }
    if($validFrom<>null){
        $fields[]="DateFrom='$validFrom'";
    }
    if($validTo<>null){
        $fields[]="DateTo='$validTo'";
    }
    if($RootCa){
        $fields[]="AsRoot=1";
    }
    if(count($fields)==0){return;}
    $sql="UPDATE sslcertificates SET ".@implode(",",$fields)." WHERE ID=$ID;";
    echo $sql."\n";
    $q->QUERY_SQL($sql);
}
function IfCertisCA($array){

    if(!isset($array["extensions"])){return false;}
    if(!isset($array["extensions"]["basicConstraints"])){return false;}

    if(preg_match("#CA:TRUE#i",$array["extensions"]["basicConstraints"])){
        writelogs("{$array["extensions"]["basicConstraints"]} Matches CA:TRUE",__FUNCTION__,__FILE__,__LINE__);
        return true;
    }
    return false;
}
function pvk_convert($CommonName){
    $q=new lib_sqlite(GetDatabase());
    $CommonName_source=$CommonName;
    $sql="SELECT pvk_content  FROM sslcertificates WHERE CommonName='$CommonName'";
    $ligne=$q->mysqli_fetch_array($sql);
    $pvk_content=$ligne["pvk_content"];
    $unix=new unix();
    $sock=new sockets();
    $openssl=$unix->find_program("openssl");



    $pv_content_size=strlen($pvk_content);
    $tmpsource=$unix->FILE_TEMP().".pvk";
    $pemdest=$unix->FILE_TEMP().".pem";


    $results[]="Common Name.........: $CommonName";
    $results[]="PVK content.........: $pv_content_size Bytes";

    @file_put_contents($tmpsource, $pvk_content);

    $cmd="$openssl rsa -inform pvk -in $tmpsource -outform pem -out $pemdest 2>&1";
    exec($cmd,$results);
    if(!is_file($pemdest)){
        echo " **** **** FAILED **** ****\n$cmd\n$pemdest no such file\n".@implode("\n", $results);
        return;

    }
    $privkey=sqlite_escape_string2(@file_get_contents($pemdest));
    $sql="UPDATE sslcertificates SET privkey='$privkey' WHERE CommonName='$CommonName'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo " **** **** FAILED **** ****\n";
        echo $q->mysql_error;return;
    }
    echo " **** **** SUCCESS **** ****\nPrivate key as imported updated\n";
}

function pfx_convert($CommonName){
    $q=new lib_sqlite(GetDatabase());
    $CommonName_source=$CommonName;
    $sql="SELECT pkcs12,pkcs12Pass  FROM sslcertificates WHERE CommonName='$CommonName'";
    $ligne=$q->mysqli_fetch_array($sql);
    $pkcs12=$ligne["pkcs12"];
    $pkcs12Pass=$ligne["pkcs12Pass"];

    $unix=new unix();
    $sock=new sockets();
    $openssl=$unix->find_program("openssl");

    $content_size=strlen($pkcs12);
    $tmpsource=$unix->FILE_TEMP().".pfx";
    $keyEncdest=$unix->FILE_TEMP().".encrypted";
    $keydest=$unix->FILE_TEMP().".key";
    $certdest=$unix->FILE_TEMP().".crt";
    $pkcs12Pass=$unix->shellEscapeChars($pkcs12Pass);

    $results[]="Common Name.........: $CommonName";
    $results[]="PFX content.........: $content_size Bytes";

    @file_put_contents($tmpsource, $pkcs12);

    $cmd="$openssl pkcs12 -in $tmpsource -password pass:$pkcs12Pass -passin pass:$pkcs12Pass -passout pass:$pkcs12Pass -nocerts -out $keyEncdest";
    exec($cmd,$results);
    if(!is_file($keyEncdest)){
        echo " **** **** FAILED **** ****\nCreate the private key file failed\n";
        return;
    }




    $results=array();
    $cmd="$openssl rsa -in $keyEncdest -passin pass:$pkcs12Pass -out $keydest";
    exec($cmd,$results);
    if(!is_file($keydest)){
        echo " **** **** FAILED **** ****\nDecrypt the key file. failed\n";
        return;
    }

    @unlink($keyEncdest);

    $data=@file_get_contents($keydest);
    if(!preg_match("#-----BEGIN RSA PRIVATE KEY-----(.*?)-----END RSA PRIVATE KEY-----#is", $data,$re)){
        echo " **** **** FAILED **** ****\nDecrypt the key file. failed - RSA PRIVATE KEY - expected\n";
        @unlink($tmpsource);
        @unlink($keydest);
        return;
    }
    $re[1]=trim($re[1]);
    $keydata="-----BEGIN RSA PRIVATE KEY-----\n".$re[1]."\n-----END RSA PRIVATE KEY-----\n";

    $results=array();
    $cmd="$openssl  pkcs12 -in $tmpsource -clcerts -passin pass:$pkcs12Pass -nokeys -out $certdest";
    exec($cmd,$results);
    if(!is_file($certdest)){
        echo " **** **** FAILED **** ****\nExporting the certificate file. failed\n";
        return;
    }
    @unlink($tmpsource);
    @unlink($keydest);

    $data=@file_get_contents($certdest);
    if(!preg_match("#-----BEGIN CERTIFICATE-----(.*?)-----END CERTIFICATE-----#is", $data,$re)){
        echo " **** **** FAILED **** ****\nDecrypt the certificate file. failed - BEGIN CERTIFICATE - expected\n";
        @unlink($certdest);
        return;

    }
    $re[1]=trim($re[1]);
    $certdata="-----BEGIN CERTIFICATE-----\n{$re[1]}\n-----END CERTIFICATE-----\n";

    $certdata=sqlite_escape_string2($certdata);
    $keydata=sqlite_escape_string2($keydata);

    $sql="UPDATE sslcertificates SET `crt`='$certdata',`privkey`='$keydata' WHERE CommonName='$CommonName'";

    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo " **** **** FAILED **** ****\n";
        echo $q->mysql_error;return;
    }
    UpdateCertificateInfos($CommonName);

    echo " **** **** SUCCESS **** ****\n";

}


function squid27_cnf($CommonName,$MainDir){

    $CommonName_sql=str_replace("_ALL_", "*", $CommonName);
    $sql="SELECT *  FROM sslcertificates WHERE CommonName='$CommonName_sql'";
    $q=new lib_sqlite(GetDatabase());
    $ligne=$q->mysqli_fetch_array($sql);

    if($ligne["CountryName"]==null){$ligne["CountryName"]="UNITED STATES_US";}
    if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
    if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
    if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
    if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
    if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}

    $ST=$ligne["stateOrProvinceName"];
    $L=$ligne["localityName"];
    $O=$ligne["OrganizationName"];
    $OU=$ligne["OrganizationalUnit"];


    if(preg_match("#^.*?_(.+)#", $ligne["CountryName"],$re)){$C=$re[1];}

    $subj=" -subj \"/C=$C/ST=$ST/L=$L/O=$O/OU=$OU/CN=$CommonName_sql\" ";
    if(strlen($CommonName_sql)>64){$CommonName_sql=substr($CommonName_sql, 0,64);}


    $f[]="";

    $f[]="####################################################################";
    $f[]="[ ca ]";
    $f[]="default_ca	= CA_default		# The default ca section";
    $f[]="";
    $f[]="####################################################################";
    $f[]="[ CA_default ]";
    $f[]="";
    $f[]="dir		= $MainDir	# Where everything is kept";
    $f[]="certs		= \$dir			# Where the issued certs are kept";
    $f[]="crl_dir		= \$dir			# Where the issued crl are kept";
    $f[]="database	= \$dir/index.txt	# database index file.";
    $f[]="new_certs_dir	= \$dir/certs_by_serial	# default place for new certs.";
    $f[]="";
    $f[]="certificate	= \$dir/ca.crt	 	# The CA certificate";
    $f[]="serial		= \$dir/serial 		# The current serial number";
    $f[]="crl		= \$dir/crl.pem 		# The current CRL";
    $f[]="private_key	= \$dir/private/ca.key	# The private key";
    $f[]="RANDFILE	= \$dir/.rand		# private random number file";
    $f[]="";
    $f[]="x509_extensions	= basic_exts		# The extentions to add to the cert";
    $f[]="";
    $f[]="# This allows a V2 CRL. Ancient browsers don't like it, but anything Easy-RSA";
    $f[]="# is designed for will. In return, we get the Issuer attached to CRLs.";
    $f[]="crl_extensions	= crl_ext";
    $f[]="";
    $f[]="default_days	= 735	# how long to certify for";
    $f[]="default_crl_days= 735	# how long before next CRL";
    $f[]="default_md	= sha1		# use public key default MD";
    $f[]="preserve	= no			# keep passed DN ordering";
    $f[]="";
    $f[]="# A few difference way of specifying how similar the request should look";
    $f[]="# For type CA, the listed attributes must be the same, and the optional";
    $f[]="# and supplied fields are just that :-)";
    $f[]="policy		= policy_anything";
    $f[]="";
    $f[]="# For the 'anything' policy, which defines allowed DN fields";
    $f[]="[ policy_anything ]";
    $f[]="countryName		= optional";
    $f[]="stateOrProvinceName	= optional";
    $f[]="localityName		= optional";
    $f[]="organizationName	= optional";
    $f[]="organizationalUnitName	= optional";
    $f[]="commonName		= supplied";
    $f[]="name			= optional";
    $f[]="emailAddress		= optional";
    $f[]="";
    $f[]="####################################################################";
    $f[]="# Easy-RSA request handling";
    $f[]="# We key off \$DN_MODE to determine how to format the DN";
    $f[]="[ req ]";
    $f[]="default_bits		= 2048";
    $f[]="default_keyfile 	= privkey.pem";
    $f[]="default_md		= sha1";
    $f[]="distinguished_name	= cn_only";
    $f[]="x509_extensions		= easyrsa_ca	# The extentions to add to the self signed cert";
    $f[]="";
    $f[]="# A placeholder to handle the \$EXTRA_EXTS feature:";
    $f[]="#%EXTRA_EXTS%	# Do NOT remove or change this line as \$EXTRA_EXTS support requires it";
    $f[]="";
    $f[]="####################################################################";
    $f[]="# Easy-RSA DN (Subject) handling";
    $f[]="";
    $f[]="# Easy-RSA DN for cn_only support:";
    $f[]="[ cn_only ]";
    $f[]="commonName		= Common Name (eg: your user, host, or server name)";
    $f[]="commonName_max		= 64";
    $f[]="commonName_default	= $CommonName_sql";
    $f[]="";
    $f[]="# Easy-RSA DN for org support:";
    $f[]="[ org ]";
    $f[]="countryName			= Country Name (2 letter code)";
    $f[]="countryName_default		= $C";
    $f[]="countryName_min			= 2";
    $f[]="countryName_max			= 2";
    $f[]="";
    $f[]="stateOrProvinceName		= State or Province Name (full name)";
    $f[]="stateOrProvinceName_default	= {$ligne["stateOrProvinceName"]}";
    $f[]="";
    $f[]="localityName			= Locality Name (eg, city)";
    $f[]="localityName_default		= {$ligne["localityName"]}";
    $f[]="";
    $f[]="0.organizationName		= Organization Name (eg, company)";
    $f[]="0.organizationName_default	= {$ligne["OrganizationName"]}";
    $f[]="";
    $f[]="organizationalUnitName		= Organizational Unit Name (eg, section)";
    $f[]="organizationalUnitName_default	= {$ligne["OrganizationalUnit"]}";
    $f[]="";
    $f[]="commonName			= Common Name (eg: your user, host, or server name)";
    $f[]="commonName_max			= 64";
    $f[]="commonName_default		= $CommonName_sql";
    $f[]="";
    $f[]="emailAddress			= Email Address";
    $f[]="emailAddress_default		= {$ligne["emailAddress"]}";
    $f[]="emailAddress_max		= 64";
    $f[]="";
    $f[]="####################################################################";
    $f[]="# Easy-RSA cert extension handling";
    $f[]="";
    $f[]="# This section is effectively unused as the main script sets extensions";
    $f[]="# dynamically. This core section is left to support the odd usecase where";
    $f[]="# a user calls openssl directly.";
    $f[]="[ basic_exts ]";
    $f[]="basicConstraints	= CA:FALSE";
    $f[]="subjectKeyIdentifier	= hash";
    $f[]="authorityKeyIdentifier	= keyid,issuer:always";
    $f[]="";
    $f[]="# The Easy-RSA CA extensions";
    $f[]="[ easyrsa_ca ]";
    $f[]="subjectKeyIdentifier=hash";
    $f[]="authorityKeyIdentifier=keyid:always,issuer:always";
    $f[]="basicConstraints = CA:true";
    $f[]="keyUsage = nonRepudiation, digitalSignature, keyEncipherment";
    $f[]="# nsCertType omitted by default. Let's try to let the deprecated stuff die.";
    $f[]="# nsCertType = sslCA";
    $f[]="";
    $f[]="# CRL extensions.";
    $f[]="[ crl_ext ]";
    $f[]="";
    $f[]="# Only issuerAltName and authorityKeyIdentifier make any sense in a CRL.";
    $f[]="";
    $f[]="# issuerAltName=issuer:copy";
    $f[]="authorityKeyIdentifier=keyid:always,issuer:always";
    $f[]="";
    @file_put_contents("$MainDir/openssl.cnf", @implode("\n", $f));

    return $subj;
}

function squid27_certif($commonName){

    $Dir="/etc/squid27/certs";
    if(is_dir($Dir)){shell_exec("rm -rf $Dir");}
    @mkdir("$Dir",0755,true);
    $subj=squid27_cnf($commonName,$Dir);
    echo "Subject: $subj\n";
    $cmd="openssl genrsa -passout pass:1234 -des3 -out $Dir/privkey.pem 2048";
    echo "$cmd\n";
    system("$cmd");

    $cmd="openssl req -new -passin pass:1234 -config $Dir/openssl.cnf $subj  -x509 -nodes -key $Dir/privkey.pem -out $Dir/cacert.pem -days 3650";
    echo "$cmd\n";
    system("$cmd");


    $cmd="openssl rsa -passin pass:1234 -in $Dir/privkey.pem -out $Dir/privkey_noPwd.pem";
    echo "$cmd\n";
    system("$cmd");

    echo "cert=$Dir/cacert.pem key=$Dir/privkey_noPwd.pem\n";
    $cmdline="openssl pkcs12 -keypbe PBE-SHA1-3DES -certpbe PBE-SHA1-3DES -export -in $Dir/cacert.pem -inkey $Dir/privkey_noPwd.pem -out $Dir/privkey_noPwd.pks12 -password pass:\"\" -name \"$commonName\"";
    echo $cmdline."\n";
}

function selfsign_progress($text,$pourc){
    $CACHEFILE=PROGRESS_DIR."/selfsign.progress";
    echo "[{$pourc}%] $text\n";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($CACHEFILE, serialize($array));
    @chmod($CACHEFILE,0755);
}
function SelfOpenSSLCNF($ligne){

    if($ligne["CountryName"]==null){$ligne["CountryName"]="US";}
    if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
    if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
    if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
    if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
    if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}
    $ligne2=array();
    $CommonName=$ligne["CommonName"];
    $ligne2[$CommonName]=true;
    if($ligne["subjectAltName"]<>null){$ligne2[$ligne["subjectAltName"]]=true;}
    if($ligne["subjectAltName1"]<>null){$ligne2[$ligne["subjectAltName1"]]=true;}
    if($ligne["subjectAltName2"]<>null){$ligne2[$ligne["subjectAltName2"]]=true;}

    if(!is_numeric($ligne["CertificateMaxDays"])){$ligne["CertificateMaxDays"]=3650;}
    if(!is_numeric($ligne["levelenc"])){$ligne["levelenc"]=4096;}
    if($ligne["CertificateName"]==null){$ligne["CertificateName"]="RootCA_".time();}


    $CommonName=$ligne["CommonName"];

    $ST=$ligne["stateOrProvinceName"];
    $L=$ligne["localityName"];
    $O=$ligne["OrganizationName"];
    $OU=$ligne["OrganizationalUnit"];
    if(preg_match("#^.*?_(.+)#", $ligne["CountryName"],$re)){$C=$re[1];}
    $subj="/C=$C/ST=$ST/L=$L/O=$O/OU=$OU/CN=$CommonName";
    $ligne["SUBJ"]=$subj;

    $ligne["SUBJCA"]="/C=$C/ST=$ST/L=$L/O=$O/OU=$OU/emailAddress={$ligne["emailAddress"]}";


    $dir="/opt/artica/ssl";
    if(!is_dir($dir)){@mkdir($dir);}

    $dirs[]="certs";
    $dirs[]="csr";
    $dirs[]="crl";
    $dirs[]="newcerts";
    $dirs[]="private";

    foreach ($dirs as $subdir){
        if(!is_dir("$dir/$subdir")){
            @mkdir("$dir/$subdir");
        }
    }

    @chmod("$dir/private",0700);
    @touch("$dir/index.txt");
    @file_put_contents("$dir/serial","1000");
    @file_put_contents("$dir/crlnumber","0000");
    @file_put_contents("$dir/index.txt.attr","unique_subject = no\n");

    if(preg_match("#^(.*?)_(.+)#",$ligne["CountryName"],$re)){
        $ligne["CountryName"]=$re[2];
    }

    $conf[]="[ ca ]";
    $conf[]="# `man ca`";
    $conf[]="default_ca = CA_default";
    $conf[]=" ";
    $conf[]="[ CA_default ]";
    $conf[]="# Directory and file locations.";
    $conf[]="dir               = .";
    $conf[]="certs             = $dir/certs";
    $conf[]="crl_dir           = $dir/crl";
    $conf[]="new_certs_dir     = $dir/newcerts";
    $conf[]="database          = $dir/index.txt";
    $conf[]="serial            = $dir/serial";
    $conf[]="RANDFILE          = $dir/private/.rand";
    $conf[]=" ";
    $conf[]="# The root key and root certificate.";
    $conf[]="private_key       = $dir/private/ca.key";
    $conf[]="certificate       = $dir/certs/ca.pem";
    $conf[]=" ";
    $conf[]="# For certificate revocation lists.";
    $conf[]="crlnumber         = $dir/crlnumber";
    $conf[]="crl               = $dir/crl/ca.crl";
    $conf[]="crl_extensions    = crl_ext";
    $conf[]="default_crl_days  = 30";
    $conf[]="extensions        = server_cert";
    $conf[]=" ";
    $conf[]="# SHA-1 is deprecated, so use SHA-2 instead.";
    $conf[]="default_md        = sha256";
    $conf[]=" ";
    $conf[]="name_opt          = ca_default";
    $conf[]="cert_opt          = ca_default";
    $conf[]="default_days      = 375";
    $conf[]="preserve          = no";
    $conf[]="policy            = policy_loose";
    $conf[]=" ";
    $conf[]="[ req ]";
    $conf[]="# Options for the `req` tool (`man req`).";
    $conf[]="default_bits        = 4096";
    $conf[]="distinguished_name  = req_distinguished_name";
    $conf[]="string_mask         = utf8only";
    $conf[]=" ";
    $conf[]="# SHA-1 is deprecated, so use SHA-2 instead.";
    $conf[]="default_md          = sha256";
    $conf[]=" ";
    $conf[]="# Extension to add when the -x509 option is used.";
    $conf[]="x509_extensions     = v3_ca";
    $conf[]=" ";
    $conf[]=" ";
    $conf[]=" ";
    $conf[]="[ req_distinguished_name ]";
    $conf[]="countryName                     = Country Name (2 letter code)";
    $conf[]="stateOrProvinceName             = State or Province Name";
    $conf[]="localityName                    = Locality Name";
    $conf[]="0.organizationName              = Organization Name";
    $conf[]="organizationalUnitName          = Organizational Unit Name";
    $conf[]="commonName                      = Common Name";
    $conf[]="emailAddress                    = Email Address";
    $conf[]=" ";
    $conf[]="countryName_default             = {$ligne["CountryName"]}";
    $conf[]="stateOrProvinceName_default     = {$ligne["stateOrProvinceName"]}";
    $conf[]="localityName_default            = {$ligne["localityName"]}";
    $conf[]="0.organizationName_default      = {$ligne["OrganizationName"]}";
    $conf[]="commonName_default              = {$ligne["CommonName"]}";
    $conf[]="emailAddress_default            = {$ligne["emailAddress"]}";
    $conf[]="countryName                     = {$ligne["CountryName"]}";
    $conf[]="stateOrProvinceName             = {$ligne["stateOrProvinceName"]}";
    $conf[]="localityName                    = {$ligne["localityName"]}";
    $conf[]="0.organizationName              = {$ligne["OrganizationName"]}";
    $conf[]="commonName                      = {$ligne["CommonName"]}";
    $conf[]="emailAddress                    = {$ligne["emailAddress"]}";
    $conf[]=" ";
    $conf[]="[ v3_ca ]";
    $conf[]="# Extensions for a typical CA (`man x509v3_config`).";
    $conf[]="subjectKeyIdentifier = hash";
    $conf[]="authorityKeyIdentifier = keyid:always,issuer";
    $conf[]="basicConstraints = critical, CA:true";
    $conf[]="keyUsage = critical, digitalSignature, cRLSign, keyCertSign";
    $conf[]=" ";
    $conf[]="[ policy_loose ]";
    $conf[]="# Allow the intermediate CA to sign a more diverse range of certificates.";
    $conf[]="# See the POLICY FORMAT section of the `ca` man page.";
    $conf[]="countryName             = optional";
    $conf[]="stateOrProvinceName     = optional";
    $conf[]="localityName            = optional";
    $conf[]="organizationName        = optional";
    $conf[]="organizationalUnitName  = optional";
    $conf[]="commonName              = supplied";
    $conf[]="emailAddress            = optional";
    $conf[]=" ";

    $conf[]="[ server_cert ]";
    $conf[]="basicConstraints = CA:FALSE";
    $conf[]="nsCertType = server";
    $conf[]="nsComment = \"OpenSSL Generated Server Certificate\"";
    $conf[]="subjectKeyIdentifier = hash";
    $conf[]="keyUsage = critical, digitalSignature, keyEncipherment";
    $conf[]="extendedKeyUsage = serverAuth";
    $cnf1=array();
    $c=0;
    foreach ($ligne2 as $AltName=>$none){$cnf1[]="DNS:$AltName";}
    if(count($cnf1)>0) {
        $conf[]="subjectAltName = ".@implode(",",$cnf1);
        $ligne["SUBALT"]="subjectAltName = ".@implode(",",$cnf1)."\nkeyUsage = critical, digitalSignature, keyEncipherment, keyCertSign, cRLSign";
        }

    $conf[]="[ SAN ] ";
    $conf[]="basicConstraints =  critical, CA:TRUE";
    $conf[]="nsComment = \"OpenSSL Generated Root Certificate\"";
    $conf[]="keyUsage = critical, digitalSignature, keyEncipherment, keyCertSign, cRLSign";
    $conf[]="extendedKeyUsage = serverAuth, clientAuth";
    if(count($cnf1)>0) {
        $conf[]="subjectAltName = ".@implode(",",$cnf1);
    }
    $conf[]=" ";
    $conf[]="[ usr_cert ]";
    $conf[]="# Extensions for client certificates (`man x509v3_config`).";
    $conf[]="basicConstraints = CA:FALSE";
    $conf[]="nsCertType = client, email";
    $conf[]="nsComment = \"OpenSSL Generated Client Certificate\"";
    $conf[]="subjectKeyIdentifier = hash";
    $conf[]="authorityKeyIdentifier = keyid,issuer";
    $conf[]="keyUsage = critical, nonRepudiation, digitalSignature, keyEncipherment";
    $conf[]="extendedKeyUsage = clientAuth, emailProtection";
    $conf[]=" ";
    $conf[]="[ crl_ext ]";
    $conf[]="authorityKeyIdentifier = keyid:always";
    $conf[]="issuerAltName = issuer:copy";

    @file_put_contents("$dir/openssl.cnf",@implode("\n",$conf));
    return $ligne;
}

function SelfRootCA($ID=0):bool{
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $openssl=$unix->find_program("openssl");
    $q=new lib_sqlite(GetDatabase());
    $dir="/opt/artica/ssl";
    $opensslcnf="$dir/openssl.cnf";
    $UsePrivKeyCrt=0;
    patchSQL();

    if($ID==0) {
        $ligne = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CertificateCenterCSR"));
    }else{
        $ligne=$q->mysqli_fetch_array("SELECT CertificateCenterCSR FROM sslcertificates WHERE ID=$ID");
        $ligne = unserialize(base64_decode($ligne["CertificateCenterCSR"]));
    }

    $CNF=SelfOpenSSLCNF($ligne);
    $CERTDAYS=$CNF["CertificateMaxDays"];

    $CertificateCenterCSR=base64_encode(serialize($CNF));
    $levelenc=$CNF["levelenc"];
    $CommonName=$CNF["CommonName"];
    $CertificateName=$CNF["CertificateName"];
    $subj=$CNF["SUBJ"];
    $subjca=$CNF["SUBJCA"];
    if(!patchSQL()){
        selfsign_progress("SQL error",110);
        return false;
    }

    selfsign_progress("$CertificateName...", 15);
    if($ID==0) {
        echo "Create RootCA...\n";

        shell_exec("$openssl genrsa -out $dir/private/ca.key $levelenc");
        echo "Create Root certificate...\n";

        $cmd="$openssl req -x509 -new -config $dir/openssl.cnf -extensions SAN -subj=\"$subjca\" -nodes -key $dir/private/ca.key -sha256 -days $CERTDAYS -out $dir/certs/ca.pem";


        echo $cmd."\n";
        shell_exec($cmd);
        selfsign_progress("$CertificateName...", 20);

        $cmd="$openssl pkcs12 -export -clcerts -in $dir/certs/ca.pem -inkey $dir/private/ca.key -out $dir/private/ca.p12 -passout pass:";
        echo $cmd."\n";
        shell_exec($cmd);
        selfsign_progress("$CertificateName...", 25);


        $q = new lib_sqlite(GetDatabase());
        $key_data = $q->sqlite_escape_string2(@file_get_contents("$dir/private/ca.key"));
        $certificate_data = $q->sqlite_escape_string2(@file_get_contents("$dir/certs/ca.pem"));
        $p12Data = $q->sqlite_escape_string2(base64_encode(@file_get_contents("$dir/private/ca.p12")));

        $q->QUERY_SQL("DELETE FROM sslcertificates WHERE CommonName='$CertificateName'");
        selfsign_progress("$CertificateName...", 30);
        $sql = "INSERT INTO sslcertificates (CommonName,AsRoot,CertificateCenterCSR,UsePrivKeyCrt,privkey,bundle,crt,SquidCert,Squidkey,pks12,Generated)";

        $f[] = "'$CertificateName'";
        $f[] = "1";
        $f[] = "'$CertificateCenterCSR'";
        $f[] = 0;
        $f[] = "'$key_data'";
        $f[] = "''";
        $f[] = "'$certificate_data'";
        $f[] = "'$certificate_data'";
        $f[] = "'$key_data'";
        $f[] = "'$p12Data',1";

        $q->QUERY_SQL("$sql VALUES (" . @implode(",", $f) . ")");
        if (!$q->ok) {
            echo $q->mysql_error . "\n";
            selfsign_progress("$CertificateName... SQL {failed}", 110);
            return false;
        }
        selfsign_progress("$CertificateName...", 35);
        UpdateCertificateInfos($CertificateName);
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM sslcertificates WHERE CommonName='$CertificateName'");
        $ID=$ligne["ID"];
    }else{
        selfsign_progress("$CertificateName...", 40);
        $ligne=$q->mysqli_fetch_array("SELECT * FROM sslcertificates WHERE ID=$ID");
        $UsePrivKeyCrt=$ligne["UsePrivKeyCrt"];
        if($UsePrivKeyCrt==0){
            $CaContent=$ligne["Squidkey"];
            $CertContent=$ligne["SquidCert"];
        }else{
            $CaContent=$ligne["privkey"];
            $CertContent=$ligne["crt"];
        }

        @file_put_contents("$dir/private/ca.key",$CaContent);
        @file_put_contents("$dir/certs/ca.pem",$CertContent);

    }
    selfsign_progress("$CommonName...", 45);
    $rootCA="$dir/certs/ca.pem";
    $root_rsa="$dir/private/ca.key";
    $PrivatekeyPath="$dir/private/server.key";
    $CSRPath="$dir/csr/server.csr";
    $CertificatePath="$dir/certs/server.pem";
    $P12Path="$dir/private/ca-serv.p12";
    $SUBALT=$CNF["SUBALT"];
    $extfile="$dir/private/EXTFILE";

    $FILESTESTS[]=$PrivatekeyPath;
    $FILESTESTS[]=$CSRPath;
    $FILESTESTS[]=$CertificatePath;
    $FILESTESTS[]=$P12Path;

    $cmd="$openssl genrsa -out $PrivatekeyPath $levelenc";
    echo "$cmd\n";
    shell_exec($cmd);
    selfsign_progress("$CommonName...", 50);
    $cmd="$openssl req -new -sha256 -key $PrivatekeyPath -subj '$subj' -reqexts server_cert -config $opensslcnf -out $CSRPath";
    echo "$cmd\n";
    shell_exec($cmd);

    @file_put_contents($extfile,"$SUBALT\n");
    selfsign_progress("$CommonName...", 55);
    $cmd="$openssl x509 -req -extfile $extfile -days $CERTDAYS -in $CSRPath -CA $rootCA -CAkey $root_rsa -CAcreateserial -out $CertificatePath -sha256";
    echo "$cmd\n";
    shell_exec($cmd);

    selfsign_progress("$CommonName...", 60);
    $cmd="$openssl pkcs12 -export -clcerts -in $CertificatePath -inkey $PrivatekeyPath -out $P12Path -passout pass:";
    echo $cmd."\n";
    shell_exec($cmd);

    foreach ($FILESTESTS as $file){
        if(!is_file($file)){
            shell_exec("$rm -rf $dir");
            echo "Required $file doesn't exists...\n";
            selfsign_progress("$CommonName...{failed}", 110);
            return false;
        }
    }


    $key_data = $q->sqlite_escape_string2(base64_encode(@file_get_contents($PrivatekeyPath)));
    $certificate_data = $q->sqlite_escape_string2(base64_encode(@file_get_contents($CertificatePath)));
    $p12Data = $q->sqlite_escape_string2(base64_encode(@file_get_contents($P12Path)));
    $csrData = $q->sqlite_escape_string2(base64_encode(@file_get_contents($CSRPath)));
    $CountryName=$CNF["CountryName"];
    $stateOrProvinceName=$CNF["stateOrProvinceName"];
    $localityName=$CNF["localityName"];
    $emailAddress=$CNF["emailAddress"];
    $OrganizationName=$CNF["OrganizationName"];
    $OrganizationalUnit=$CNF["OrganizationalUnit"];
    $subjectAltName=$CNF["subjectAltName"];
    $subjectAltName1=$CNF["subjectAltName1"];
    $subjectAltName2=$CNF["subjectAltName2"];

    selfsign_progress("$CommonName...", 70);
    $CertificateCenterCSRMD=md5(time().$CertificateCenterCSR);
    $sql="INSERT INTO subcertificates (certid,UsePrivKeyCrt,Certype,levelenc,countryName,
    stateOrProvinceName,localityName,organizationName,organizationalUnitName,commonName,emailAddress,
    pks12,csr,srca,crt,subjectAltName,subjectAltName1,subjectAltName2,CertificateCenterCSR)
    VALUES ($ID,$UsePrivKeyCrt,1,$levelenc,'$CountryName','$stateOrProvinceName','$localityName','$OrganizationName',
    '$OrganizationalUnit','$CommonName','$emailAddress','$p12Data','$csrData','$key_data','$certificate_data','$subjectAltName', 
    '$subjectAltName1','$subjectAltName2','$CertificateCenterCSRMD')";
    shell_exec("$rm -rf $dir");

    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $q->mysql_error."\n";
        selfsign_progress("$CommonName... SQL {failed}", 110);
        echo "*************\n $sql \n******************\n";
        return false;
    }
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM subcertificates WHERE CertificateCenterCSR='$CertificateCenterCSRMD'");
    $NEWID=$ligne["ID"];
    $validTo_time_t=subcertificates_ValidTo($NEWID);
    $sql="UPDATE subcertificates SET CertificateCenterCSR='$CertificateCenterCSR',DateTo='$validTo_time_t' WHERE ID=$NEWID";
    $q->QUERY_SQL($sql);
    selfsign_progress(__FUNCTION__."() $CommonName... {success}", 100);
    return true;


}
function subcertificates_ValidTo($ID):int{
    $q = new lib_sqlite(GetDatabase());
    $ligne=$q->mysqli_fetch_array("SELECT srca,crt FROM subcertificates WHERE ID='$ID'");

    echo "subcertificates_ValidTo: $ID\n";
    $certificate_content=base64_decode($ligne["crt"]);
    $privkey_content=base64_decode($ligne["srca"]);

    echo "Cert: ".strlen($certificate_content)."\n";
    echo "srca: ".strlen($privkey_content)."\n";

    $data[]=$certificate_content;
    $data[]=$privkey_content;


    $array=openssl_x509_parse(@implode("\n",$data));
    if(!$array){
        echo "openssl_x509_parse Error\n";
        print_r(error_get_last());
        echo @implode("\n",$data)."\n";
    }


    return intval($array["validTo_time_t"]);

}
function selfClient($certid=0){
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $openssl=$unix->find_program("openssl");
    $q=new lib_sqlite(GetDatabase());
    $dir="/opt/artica/ssl";
    $opensslcnf="$dir/openssl.cnf";
    $ligne=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CLIENT_CERTIFICATE_$certid"));
    $CNF=SelfOpenSSLCNF($ligne);
    $CommonName=$CNF["CommonName"];
    $CertificateCenterCSR=base64_encode(serialize($CNF));
    $ligne=$q->mysqli_fetch_array("SELECT UsePrivKeyCrt,Squidkey,SquidCert,privkey,crt FROM sslcertificates WHERE ID=$certid");
    $UsePrivKeyCrt=intval($ligne["UsePrivKeyCrt"]);
    if($UsePrivKeyCrt==0){
        $CaContent=$ligne["Squidkey"];
        $CertContent=$ligne["SquidCert"];
    }else{
        $CaContent=$ligne["privkey"];
        $CertContent=$ligne["crt"];
    }
    $root_rsa="$dir/private/ca.key";
    $rootCA="$dir/certs/ca.pem";
    @file_put_contents($root_rsa,$CaContent);
    @file_put_contents($rootCA,$CertContent);

    selfsign_progress("$CommonName...", 45);
    $levelenc=intval($CNF["levelenc"]);
    $CERTDAYS=intval($CNF["CertificateMaxDays"]);
    $subj=$CNF["SUBJ"];
    $SUBALT=$CNF["SUBALT"];

    $PrivatekeyPath="$dir/private/client.key";
    $CSRPath="$dir/csr/client.csr";
    $CertificatePath="$dir/certs/client.pem";
    $P12Path="$dir/private/ca-clie.p12";
    $extfile="$dir/private/EXTFILE";
    $Passfile="$dir/private/password";

    selfsign_progress("$CommonName...", 50);
    $cmd="$openssl genrsa -out $PrivatekeyPath $levelenc";

    //$cmd="$openssl req -new -newkey rsa:$levelenc -nodes -keyout $PrivatekeyPath -out $CSRPath -subj \"$subj\"";
    echo "$cmd\n";
    shell_exec($cmd);
    if(!is_file($PrivatekeyPath)){
        echo "Create private key failed....\n";
        selfsign_progress("$CommonName...", 110);
        return false;
    }
    $subj=str_replace("'","\'",$subj);
    $cmd="openssl req -new -subj \"$subj\" -key $PrivatekeyPath -out $CSRPath";
    echo "$cmd\n";
    shell_exec($cmd);
    if(!is_file($CSRPath)){
        echo "Create CSR key failed....\n";
        selfsign_progress("$CommonName...", 110);
        return false;
    }


    $ext[]="basicConstraints = CA:FALSE";
    $ext[]="nsCertType = client, email";
    $ext[]="nsComment = \"OpenSSL Generated Client Certificate\"";
    $ext[]="subjectKeyIdentifier = hash";
    $ext[]="authorityKeyIdentifier = keyid,issuer";
    $ext[]="keyUsage = critical, nonRepudiation, digitalSignature, keyEncipherment";
    $ext[]="extendedKeyUsage = clientAuth, emailProtection";
    $ext[]="";
    @file_put_contents($extfile,@implode("\n",$ext));

    selfsign_progress("$CommonName...", 55);
    //$cmd="$openssl x509 -req -in $CSRPath -CA $rootCA -CAkey $root_rsa -out $CertificatePath -extfile $extfile -days $CERTDAYS -set_serial 01 2>&1";

    @file_put_contents($Passfile,trim($CNF["password"]));

    $cmds[]="$openssl x509 -req -in $CSRPath";
    if(trim($CNF["password"])<>null){
        $cmds[]="-passin file:$Passfile";
    }
    $cmds[]="-CA $rootCA -CAkey $root_rsa -out";
    $cmds[]="$CertificatePath -CAcreateserial -days $CERTDAYS -sha256 -extfile $extfile";
    $cmd=@implode(" ",$cmds);
    echo $cmd."\n";
    exec($cmd,$results1);


    if(!is_file($CertificatePath)){
        foreach ($results1 as $line){
            echo "Results: $line\n";
        }

        selfsign_progress("$CommonName...{failed}", 110);
        return false;
    }


    selfsign_progress("{building} PKCS #12 (PFX)", 60);
    $cmd="$openssl pkcs12 -export -clcerts -in $CertificatePath -inkey $PrivatekeyPath -out $P12Path -passout pass:";
    shell_exec($cmd);

    $key_data = $q->sqlite_escape_string2(base64_encode(@file_get_contents($PrivatekeyPath)));
    $certificate_data = $q->sqlite_escape_string2(base64_encode(@file_get_contents($CertificatePath)));
    $p12Data = $q->sqlite_escape_string2(base64_encode(@file_get_contents($P12Path)));
    $csrData = $q->sqlite_escape_string2(base64_encode(@file_get_contents($CSRPath)));
    $CountryName=$q->sqlite_escape_string2($CNF["CountryName"]);
    $stateOrProvinceName=$q->sqlite_escape_string2($CNF["stateOrProvinceName"]);
    $localityName=$q->sqlite_escape_string2($CNF["localityName"]);
    $emailAddress=$q->sqlite_escape_string2($CNF["emailAddress"]);
    $OrganizationName=$q->sqlite_escape_string2($CNF["OrganizationName"]);
    $OrganizationalUnit=$q->sqlite_escape_string2($CNF["OrganizationalUnit"]);
    $subjectAltName=$q->sqlite_escape_string2($CNF["subjectAltName"]);
    $subjectAltName1=$CNF["subjectAltName1"];
    $subjectAltName2=$CNF["subjectAltName2"];

    selfsign_progress("$CommonName...", 70);
    $CertificateCenterCSRMD=md5($CertificateCenterCSR.time());

    $sql="INSERT INTO subcertificates (certid,UsePrivKeyCrt,Certype,levelenc,countryName,
    stateOrProvinceName,localityName,organizationName,organizationalUnitName,commonName,emailAddress,
    pks12,csr,srca,crt,subjectAltName,subjectAltName1,subjectAltName2,CertificateCenterCSR)
    VALUES ($certid,$UsePrivKeyCrt,2,$levelenc,'$CountryName','$stateOrProvinceName','$localityName','$OrganizationName',
    '$OrganizationalUnit','$CommonName','$emailAddress','$p12Data','$csrData','$key_data','$certificate_data','$subjectAltName', 
    '$subjectAltName1','$subjectAltName2','$CertificateCenterCSRMD')";
    shell_exec("$rm -rf $dir");

    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $q->mysql_error."\n";
        selfsign_progress("$CommonName... SQL {failed}", 110);
        echo "*************\n $sql \n******************\n";
        return false;
    }

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM subcertificates WHERE CertificateCenterCSR='$CertificateCenterCSRMD'");
    $NEWID=$ligne["ID"];

    if($NEWID==0){
        selfsign_progress("$CommonName... ID=0", 110);
        return false;
    }

    $validTo_time_t=subcertificates_ValidTo($NEWID);
    if($validTo_time_t==0){
        $q->QUERY_SQL("DELETE FROM subcertificates WHERE ID=$NEWID");
        selfsign_progress("$CommonName... {expire} {failed}", 110);
        return false;
    }

    $sql="UPDATE subcertificates SET CertificateCenterCSR='$CertificateCenterCSR',DateTo='$validTo_time_t' WHERE ID=$NEWID";
    $q->QUERY_SQL($sql);

    selfsign_progress("$CommonName... {success}", 100);
    return true;



}

function selfServer($certid=0){
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $openssl=$unix->find_program("openssl");
    $q=new lib_sqlite(GetDatabase());
    $dir="/opt/artica/ssl";
    $opensslcnf="$dir/openssl.cnf";
    $ligne=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SERVER_CERTIFICATE_$certid"));

    $CNF=SelfOpenSSLCNF($ligne);
    $CommonName=$CNF["CommonName"];
    $CertificateCenterCSR=base64_encode(serialize($CNF));

    $ligne=$q->mysqli_fetch_array("SELECT UsePrivKeyCrt,Squidkey,SquidCert,privkey,crt FROM sslcertificates WHERE ID=$certid")
    ;
    $UsePrivKeyCrt=intval($ligne["UsePrivKeyCrt"]);

    if($UsePrivKeyCrt==0){
        $CaContent=$ligne["Squidkey"];
        $CertContent=$ligne["SquidCert"];
    }else{
        $CaContent=$ligne["privkey"];
        $CertContent=$ligne["crt"];
    }
    $root_rsa="$dir/private/ca.key";
    $rootCA="$dir/certs/ca.pem";
    @file_put_contents($root_rsa,$CaContent);
    @file_put_contents($rootCA,$CertContent);

    selfsign_progress("$CommonName...", 45);
    $levelenc=intval($CNF["levelenc"]);
    $CERTDAYS=intval($CNF["CertificateMaxDays"]);
    $subj=$CNF["SUBJ"];
    $SUBALT=$CNF["SUBALT"];

    $PrivatekeyPath="$dir/private/server.key";
    $CSRPath="$dir/csr/server.csr";
    $CertificatePath="$dir/certs/server.pem";
    $P12Path="$dir/private/ca-serv.p12";
    $extfile="$dir/private/EXTFILE";

    $FILESTESTS[]=$PrivatekeyPath;
    $FILESTESTS[]=$CSRPath;
    $FILESTESTS[]=$CertificatePath;
    $FILESTESTS[]=$P12Path;

    $cmd="$openssl genrsa -out $PrivatekeyPath $levelenc";
    echo "$cmd\n";
    shell_exec($cmd);
    selfsign_progress("$CommonName...", 50);
    $cmd="$openssl req -new -sha256 -key $PrivatekeyPath -subj '$subj' -reqexts server_cert -config $opensslcnf -out $CSRPath";
    echo "$cmd\n";
    shell_exec($cmd);

    @file_put_contents($extfile,"$SUBALT\n");
    selfsign_progress("$CommonName...", 55);
     $cmd="$openssl x509 -req -extfile $extfile -days $CERTDAYS -in $CSRPath -CA $rootCA -CAkey $root_rsa -CAcreateserial -out $CertificatePath -sha256";
    echo "$cmd\n";
    shell_exec($cmd);

    selfsign_progress("$CommonName...", 60);
    $cmd="$openssl pkcs12 -export -clcerts -in $CertificatePath -inkey $PrivatekeyPath -out $P12Path -passout pass:";
    echo $cmd."\n";
    shell_exec($cmd);

    foreach ($FILESTESTS as $file){
        if(!is_file($file)){
            shell_exec("$rm -rf $dir");
            echo "Required $file doesn't exists...\n";
            selfsign_progress("$CommonName...{failed}", 110);
            return false;
        }
    }


    $key_data = $q->sqlite_escape_string2(base64_encode(@file_get_contents($PrivatekeyPath)));
    $certificate_data = $q->sqlite_escape_string2(base64_encode(@file_get_contents($CertificatePath)));
    $p12Data = $q->sqlite_escape_string2(base64_encode(@file_get_contents($P12Path)));
    $csrData = $q->sqlite_escape_string2(base64_encode(@file_get_contents($CSRPath)));
    $CountryName=$CNF["CountryName"];
    $stateOrProvinceName=$CNF["stateOrProvinceName"];
    $localityName=$CNF["localityName"];
    $emailAddress=$CNF["emailAddress"];
    $OrganizationName=$CNF["OrganizationName"];
    $OrganizationalUnit=$CNF["OrganizationalUnit"];
    $subjectAltName=$CNF["subjectAltName"];
    $subjectAltName1=$CNF["subjectAltName1"];
    $subjectAltName2=$CNF["subjectAltName2"];

    selfsign_progress("$CommonName...", 70);
    $CertificateCenterCSRMD=md5($CertificateCenterCSR.time());

    $sql="INSERT INTO subcertificates (certid,UsePrivKeyCrt,Certype,levelenc,countryName,
    stateOrProvinceName,localityName,organizationName,organizationalUnitName,commonName,emailAddress,
    pks12,csr,srca,crt,subjectAltName,subjectAltName1,subjectAltName2,CertificateCenterCSR)
    VALUES ($certid,'$UsePrivKeyCrt',1,'$levelenc','$CountryName','$stateOrProvinceName','$localityName','$OrganizationName',
    '$OrganizationalUnit','$CommonName','$emailAddress','$p12Data','$csrData','$key_data','$certificate_data','$subjectAltName', 
    '$subjectAltName1','$subjectAltName2','$CertificateCenterCSRMD')";
    shell_exec("$rm -rf $dir");

    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $q->mysql_error."\n";
        selfsign_progress("$CommonName... SQL {failed}", 110);
        echo "*************\n $sql \n******************\n";
        return false;
    }


    $ligne=$q->mysqli_fetch_array("SELECT ID FROM subcertificates WHERE CertificateCenterCSR='$CertificateCenterCSRMD'");
    $NEWID=$ligne["ID"];

    if($NEWID==0){
        selfsign_progress("$CommonName... ID=0", 110);
        return false;
    }

    $validTo_time_t=subcertificates_ValidTo($NEWID);
    if($validTo_time_t==0){
        $q->QUERY_SQL("DELETE FROM subcertificates WHERE ID=$NEWID");
        selfsign_progress("$CommonName... {expire} {failed}", 110);
        return false;
    }

    $sql="UPDATE subcertificates SET CertificateCenterCSR='$CertificateCenterCSR',DateTo='$validTo_time_t' WHERE ID=$NEWID";
    $q->QUERY_SQL($sql);

    selfsign_progress("$CommonName... {success}", 100);
    return true;




}

function patchSQL():bool{
    $q = new lib_sqlite(GetDatabase());
    if(!$q->TABLE_EXISTS("subcertificates")) {
        $sql = "CREATE TABLE subcertificates (
	`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
	certid INTEGER,
	UsePrivKeyCrt INTEGER NOT NULL DEFAULT 0,
	Certype INTEGER NOT NULL DEFAULT 1,
	levelenc INTEGER NOT NULL DEFAULT 4096,
	countryName TEXT,
	stateOrProvinceName TEXT,
	localityName TEXT,
	organizationName TEXT,
	organizationalUnitName TEXT,
	commonName TEXT,
	emailAddress TEXT,
	pks12 TEXT,
	csr TEXT,
	srca  TEXT,
	crt TEXT,
	DateFrom INTEGER,
	DateTo INTEGER,
	subjectAltName TEXT,
	subjectAltName1 TEXT,
	subjectAltName2 TEXT,
	CertificateCenterCSR TEXT,
	password TEXT)";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error."\n";return false;}
    }

    if (!$q->FIELD_EXISTS("sslcertificates", "AsRoot")) {
        $q->QUERY_SQL("ALTER TABLE sslcertificates ADD AsRoot INTEGER NOT NULL DEFAULT 0");
    }
    if (!$q->FIELD_EXISTS("sslcertificates", "CertificateCenterCSR")) {
        $q->QUERY_SQL("ALTER TABLE sslcertificates ADD CertificateCenterCSR TEXT NULL");
    }
    if (!$q->FIELD_EXISTS("sslcertificates", "AdditionalNames")) {
        $q->QUERY_SQL("ALTER TABLE sslcertificates ADD AdditionalNames TEXT NULL");
    }
    if (!$q->FIELD_EXISTS("sslcertificates", "Generated")) {
        $q->QUERY_SQL("ALTER TABLE sslcertificates ADD Generated INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("sslcertificates","subjectKeyIdentifier")){
        $q->QUERY_SQL("ALTER TABLE sslcertificates ADD subjectKeyIdentifier TEXT NULL");
    }
    if (!$q->FIELD_EXISTS("sslcertificates", "ServerCert")) {
        $q->QUERY_SQL("ALTER TABLE sslcertificates ADD ServerCert INTEGER NOT NULL DEFAULT 0");
    }
    return true;

}

function squid_wizard(){
    $unix=new unix();
    $TEMPDIR=$unix->TEMP_DIR();
    $ProxyCertificateWizard=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyCertificateWizard"));
    if($ProxyCertificateWizard["CountryName"]==null){$ProxyCertificateWizard["CountryName"]="US";}
    if($ProxyCertificateWizard["stateOrProvinceName"]==null){$ProxyCertificateWizard["stateOrProvinceName"]="California";}
    if($ProxyCertificateWizard["localityName"]==null){$ProxyCertificateWizard["localityName"]="Los Angeles";}
    if($ProxyCertificateWizard["organizationName"]==null){$ProxyCertificateWizard["organizationName"]="ACME ltd";}
    if($ProxyCertificateWizard["organizationalUnitName"]==null){$ProxyCertificateWizard["organizationalUnitName"]="Proxy Internet Access";}
    $portid=$ProxyCertificateWizard["portid"];

    $C=$ProxyCertificateWizard["CountryName"];
    $ST=$ProxyCertificateWizard["stateOrProvinceName"];
    $L=$ProxyCertificateWizard["localityName"];
    $O=$ProxyCertificateWizard["organizationName"];
    $OU=$ProxyCertificateWizard["organizationalUnitName"];
    $time=time();
    $DefaultSubject="-subj \"/C={$C}/ST=$ST/L=$L/O=$O/OU=$OU/CN=\"";
    $TempFile=$unix->FILE_TEMP();
    $openssl=$unix->find_program("openssl");
    patchSQL();

    if(is_file($TempFile)){@unlink($TempFile);}
    $cmdline="$openssl req -new -newkey rsa:2048 -days 999 -nodes -x509 $DefaultSubject -keyout $TempFile -out $TempFile";

    selfsign_progress("Generating certificate...",20);
    echo $cmdline."\n";
    system($cmdline);

    if(!is_file($TempFile)){
        selfsign_progress("Generating certificate {failed}...",110);
        return false;
    }

    $TempData=@file_get_contents($TempFile);

    if(!preg_match("#-----BEGIN PRIVATE KEY-----(.+?)-----END PRIVATE KEY-----#is",$TempData,$re)){
        echo "Unable to find PRIVATE KEY\n";
        selfsign_progress("Generating certificate {failed}...",110);
        @unlink($TempFile);
        return false;
    }

    $PrivateKeyData="-----BEGIN PRIVATE KEY-----{$re[1]}-----END PRIVATE KEY-----";

    if(!preg_match("#-----BEGIN CERTIFICATE-----(.+?)-----END CERTIFICATE-----#is",$TempData,$re)){
        echo "Unable to find CERTIFICATE\n";
        selfsign_progress("Generating certificate {failed}...",110);
        @unlink($TempFile);
        return false;
    }

    $CertificateData="-----BEGIN CERTIFICATE-----{$re[1]}-----END CERTIFICATE-----";

    @unlink($TempFile);
    $CertificatePath="$TEMPDIR/$time.cer";
    $PrivatekeyPath="$TEMPDIR/$time.key";
    $P12Path="$TEMPDIR/$time.p12";

    @file_put_contents($CertificatePath,$CertificateData);
    @file_put_contents($PrivatekeyPath,$PrivateKeyData);

    selfsign_progress("Generating pkcs12...",50);
    $cmd="$openssl pkcs12 -export -clcerts -in $CertificatePath -inkey $PrivatekeyPath -out $P12Path -passout pass:";
    echo $cmd."\n";
    system($cmd);

    if(!is_file($P12Path)){
        selfsign_progress("Generating pkcs12...{failed}",110);
        @unlink($CertificatePath);
        @unlink($PrivatekeyPath);
        return false;
    }


    $q = new lib_sqlite(GetDatabase());
    $key_data = $q->sqlite_escape_string2($PrivateKeyData);
    $certificate_data = $q->sqlite_escape_string2($CertificateData);
    $p12Data = $q->sqlite_escape_string2(base64_encode(@file_get_contents($P12Path)));
    $CertificateName="proxy.ssl.$time";

    @unlink($CertificatePath);
    @unlink($PrivatekeyPath);
    @unlink($P12Path);

    selfsign_progress("{saving_configuration}...", 60);
    $sql = "INSERT INTO sslcertificates (CommonName,AsRoot,CertificateCenterCSR,UsePrivKeyCrt,privkey,bundle,crt,SquidCert,Squidkey,pks12,Generated)";
    $f[] = "'$CertificateName'";
    $f[] = "1";
    $f[] = "''";
    $f[] = 0;
    $f[] = "'$key_data'";
    $f[] = "''";
    $f[] = "'$certificate_data'";
    $f[] = "'$certificate_data'";
    $f[] = "'$key_data'";
    $f[] = "'$p12Data',1";

    $q->QUERY_SQL("$sql VALUES (" . @implode(",", $f) . ")");
    if (!$q->ok) {
        echo $q->mysql_error . "\n";
        selfsign_progress("{saving_configuration}... SQL {failed}", 110);
        return false;
    }
    selfsign_progress("{analyze}...", 70);
    UpdateCertificateInfos($CertificateName);
    selfsign_progress("{saving_configuration}...", 75);
    if($portid>0) {
        $q = new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $q->QUERY_SQL("UPDATE proxy_ports SET UseSSL='1',sslcertificate='$CertificateName' WHERE ID='$portid'");
        if (!$q->ok) {
            echo $q->mysql_error . "\n";
            selfsign_progress("{saving_configuration} {APP_SQUID}... SQL {failed}", 110);
            $q = new lib_sqlite(GetDatabase());
            $q->QUERY_SQL("DELETE FROM sslcertificates WHERE CommonName='$CertificateName'");
            return false;
        }

        $php5=$unix->LOCATE_PHP5_BIN();
        selfsign_progress("{reconfiguring}...", 80);
        system("$php5 /usr/share/artica-postfix/exec.squid.global.access.php --ports --restart --firehol --force");

    }
    selfsign_progress("{done}...", 100);
    $ProxyCertificateWizard["CommonName"]=$CertificateName;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ProxyCertificateWizard",serialize($ProxyCertificateWizard));
    return true;
}

?>