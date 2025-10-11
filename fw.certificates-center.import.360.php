<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_POST["pfx-password"])){$tpl=new template_admin();$tpl->CLEAN_POST();$_SESSION["pfx-password"]=$_POST["pfx-password"];exit;}
if(isset($_GET["password-posted"])){PasswordPosted();exit;}
if(isset($_GET["password-form"])){PasswordForm();exit;}
if(isset($_GET["array"])){parse_array();exit;}
js();



function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$users=new usersMenus();
    $certid=0;
    $CommonName=$_GET["CommonName"];
    $CommonNameEnc=urlencode($CommonName);
    $function=$_GET["function"];
    if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);
        if ($gpid > 0) {
            $Addon="&HarmpID=$gpid";
            $users->AsCertifsManager=true;
        }
    }
    if(isset($_GET["certid"])){
        $certid=intval($_GET["certid"]);
    }

    if(!$users->AsCertifsManager){$tpl->js_no_privileges();return; }
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("openssl.php?patch-sql=yes$Addon");
	$tpl->js_dialog6("$CommonName:{import_certificates_from_cyberdef360}", "$page?popup=yes&certid=$certid&function=$function&CommonName=$CommonNameEnc","700");
	
}


function popup():bool{
    $certid=0;
	$tpl=new template_admin();
	$page=CurrentPageName();
    if(isset($_GET["certid"])){
        $certid=intval($_GET["certid"]);
    }
    $CommonName=$_GET["CommonName"];
    $CommonNameEnc=urlencode($CommonName);

    $function=$_GET["function"];
	$bt_upload=$tpl->button_upload("$CommonName - {import_certifcate_file}",$page,null,"&certid=$certid&function=$function&CommonName=$CommonNameEnc")."&nbsp;&nbsp;";
	$explain=$tpl->div_explain("{import_certificates_from_cyberdef360_explain}");
	$html="<div id='ca-form-import'>
        <div class='center'>$bt_upload</div></div>
	    <div id='progress-certificates-center-import' style='margin-top:20px'>$explain</div>";
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function PasswordPosted(){
    $file=$_GET["filename"];
    if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);
        if ($gpid > 0) {
            $Addon="&HarmpID=$gpid";
        }
    }

    $password=base64_encode($_SESSION["pfx-password"]);
    header("content-type: application/x-javascript");
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/certificates.center.import.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/certificates.center.import.progress.log";
    $ARRAY["CMD"]="openssl.php?import-pfx=yes$Addon&filename=".urlencode($file)."&password=$password";
    $ARRAY["TITLE"]="{importing} $file";
    $ARRAY["AFTER"]="LoadAjax('table-loader-sslcerts-service','fw.certificates-center.php?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-certificates-center-import')";
    echo "document.getElementById('pfx-form-import').innerHTML='';\n";
    echo $jsrestart;

}

function file_uploaded():bool{
    $tpl=new template_admin();
    $file=$_GET["file-uploaded"];
    $fullpath="/usr/share/artica-postfix/ressources/conf/upload/$file";
    $certid=intval($_GET["certid"]);
    $function=$_GET["function"];
    $CommonName=$_GET["CommonName"];
    $CommonNameEnc=urlencode($CommonName);
    if(!preg_match("#\.pem$#",$file)){
        return $tpl->js_error("Error: $file not a PEM file");
    }
    if($certid==0){
        return $tpl->js_error("Error: Please generate a CSR before");
    }

    $array["path"]=$fullpath;
    $gpid=0;
    if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);
    }
    $array["HarmpID"]=$gpid;

    $sock=new sockets();
    $array["certid"]=$certid;
    $data=$sock->REST_API_POST("/certificate/pem/import",$array);
    $json=json_decode($data);
    if(!$json->Status){
        return $tpl->js_mysql_alert($json->Error);
    }


    header("content-type: application/x-javascript");
    echo "dialogInstance6.close();\n";
    if(strlen($function)>2) {
        echo "$function();\n";
    }
    echo "dialogInstance6.close();\n";
    echo "Loadjs('fw.certificates-center.php?certificate-js=$CommonNameEnc&ID=$certid&function=$function');\n";
    return true;
}
function GetDatabase(){
    $db="/home/artica/SQLITE/certificates.db";
    if(isset($_SESSION["HARMPID"])){
        $gpid=intval($_SESSION["HARMPID"]);
        if($gpid>0){
            $db="/home/artica/SQLITE/certificates.$gpid.db";
        }
    }
    return $db;
}

function ImportSubCertificates($data,$privkey=null,$csrdata=null){
    $tpl                    = new template_admin();
    $array                  = openssl_x509_parse($data);
    $CountryName            = null;
    $OrganizationName       = null;
    $OrganizationalUnit     = null;
    $stateOrProvinceName    = null;
    $localityName           = null;
    $CommonName             = null;
    $emailAddress           = null;
    $subjectKeyIdentifier   = null;
    $certid                 = 0;
    $subjectAltName         = null;
    $subjectAltName1        = null;
    $WHERE                  = array();
    $csrData                = $csrdata;

    foreach ($array as $key=>$val){
        writelogs("openssl_x509_parse: $key = [$val]",__FUNCTION__,__FILE__,__LINE__);
        if(is_array($val)){
            foreach ($val as $skey=>$valz){
                writelogs("openssl_x509_parse: $key [ $skey ] = [$valz]",__FUNCTION__,__FILE__,__LINE__);
            }
        }
    }


    if(strlen($csrdata)>0){
        writelogs("$CommonName PARSING CSR ", __FUNCTION__, __FILE__, __LINE__);
        $CSRarray=openssl_csr_get_subject($csrData,false);
        if(isset($CSRarray["stateOrProvinceName"])){$array["subject"]["ST"]=$CSRarray["stateOrProvinceName"];}

        if(isset($CSRarray["organizationalUnitName"])){
            $array["issuer"]["OU"]=$CSRarray["organizationalUnitName"];
            $array["subject"]["OU"]=$CSRarray["organizationalUnitName"];
        }
        if(isset($CSRarray["organizationName"])){
            $array["subject"]["O"]=$CSRarray["organizationName"];
            $array["issuer"]["O"]=$CSRarray["organizationName"];
        }
        if(isset($CSRarray["commonName"])){
            $array["issuer"]["CN"]=$CSRarray["commonName"];
            $array["subject"]["CN"]=$CSRarray["commonName"];
        }
        if(isset($CSRarray["emailAddress"])){
            $array["issuer"]["emailAddress"]=$CSRarray["emailAddress"];
            $array["subject"]["emailAddress"]=$CSRarray["emailAddress"];
        }
        if(isset($CSRarray["localityName"])){
            $array["subject"]["L"]=$CSRarray["localityName"];

        }
        if(isset($CSRarray["countryName"])){
            $array["subject"]["C"]=$CSRarray["countryName"];
        }
    }


    if(!isset($array["subject"])){
        $tpl->js_error("subject not found!");
        return false;
    }
    $q=new lib_sqlite(GetDatabase());
    if(isset($array["issuer"]["O"])){
        writelogs("$CommonName organizationName = {$array["issuer"]["O"]}", __FUNCTION__, __FILE__, __LINE__);
        $WHERE[]="organizationName='". $q->sqlite_escape_string2($array["issuer"]["O"])."'";
    }
    if(isset($array["issuer"]["OU"])){
        writelogs("$CommonName OrganizationalUnit = {$array["issuer"]["OU"]}", __FUNCTION__, __FILE__, __LINE__);
        $WHERE[]="OrganizationalUnit='". $q->sqlite_escape_string2($array["issuer"]["OU"])."'";
    }
    if(isset($array["issuer"]["CN"])){
        $WHERE[]="commonName='". $q->sqlite_escape_string2($array["issuer"]["CN"])."'";
    }
    if(isset($array["issuer"]["emailAddress"])){
        $WHERE[]="emailAddress='". $q->sqlite_escape_string2($array["issuer"]["emailAddress"])."'";
    }




    if(!$q->FIELD_EXISTS("sslcertificates","subjectKeyIdentifier")){
        $q->QUERY_SQL("ALTER TABLE sslcertificates ADD subjectKeyIdentifier TEXT NULL");
    }

    if(isset($array["subject"]["C"])){$CountryName=$array["subject"]["C"];}
    if(isset($array["subject"]["O"])){$OrganizationName=$array["subject"]["O"];}
    if(isset($array["subject"]["OU"])){$OrganizationalUnit=$array["subject"]["OU"];}
    if(isset($array["subject"]["ST"])){$stateOrProvinceName=$array["subject"]["ST"];}
    if(isset($array["subject"]["CN"])){$CommonName=$array["subject"]["CN"];}
    if(isset($array["subject"]["L"])){$localityName=$array["subject"]["L"];}
    if(isset($array["subject"]["emailAddress"])){$emailAddress=$array["subject"]["emailAddress"];}

    if(isset($array["extensions"]["subjectAltName"])){
        $TF=explode(",",$array["extensions"]["subjectAltName"]);
        foreach ($TF as $index=>$line){
            if(preg_match("#DNS:(.*)#i",$line,$re)){$TF[$index]=$re[1];}
        }
        if(isset($TF[0])){$subjectAltName=$TF[0];}
        if(isset($TF[1])){$subjectAltName1=$TF[1];}
        if(isset($TF[2])){$subjectAltName2=$TF[2];}
    }

    $validFrom_time_t=$array["validFrom_time_t"];
    $validTo_time_t=$array["validTo_time_t"];
    $validFrom_time=date("Y-m-d H:i:s",$validFrom_time_t);
    $validTo_time=date("Y-m-d H:i:s",$validTo_time_t);



    if(count($WHERE)>0) {
        $sqla = "SELECT ID FROM sslcertificates WHERE " . @implode(" AND ", $WHERE);
        $ligne = $q->mysqli_fetch_array($sqla);
        writelogs("$CommonName Search $sqla = '{$ligne["ID"]}'", __FUNCTION__, __FILE__, __LINE__);
        $certid = intval($ligne["ID"]);
        if(!$q->ok){
            writelogs("$q->mysql_error", __FUNCTION__, __FILE__, __LINE__);
        }
    }

    $OrganizationName       = $q->sqlite_escape_string2($OrganizationName);
    $localityName           = $q->sqlite_escape_string2($localityName);
    $stateOrProvinceName    = $q->sqlite_escape_string2($stateOrProvinceName);
    $CountryName            = $q->sqlite_escape_string2($CountryName);
    $OrganizationalUnit     = $q->sqlite_escape_string2($OrganizationalUnit);
    $CommonName             = $q->sqlite_escape_string2($CommonName);
    $subjectAltName         = $q->sqlite_escape_string2($subjectAltName);
    if($CommonName==null){$CommonName="unknown.certificate.".time();}



    if($certid>0){
        writelogs("$CommonName privkey = ".strlen($privkey)." bytes", __FUNCTION__, __FILE__, __LINE__);
        $UsePrivKeyCrt          = 1;
        $levelenc               = 2048;
        $p12Data                = '';

        $key_data               = '';
        $certificate_data       = base64_encode($data);
        $CertificateCenterCSRMD = null;
        $sql="INSERT INTO subcertificates (certid,UsePrivKeyCrt,Certype,levelenc,countryName,
    stateOrProvinceName,localityName,organizationName,organizationalUnitName,commonName,emailAddress,
    pks12,csr,srca,crt,subjectAltName,subjectAltName1,subjectAltName2,CertificateCenterCSR,DateTo,DateFrom,privkey,Squidkey)
    VALUES ($certid,$UsePrivKeyCrt,1,$levelenc,'$CountryName','$stateOrProvinceName','$localityName','$OrganizationName',
    '$OrganizationalUnit','$CommonName','$emailAddress','$p12Data','$csrData','$key_data','$certificate_data','$subjectAltName', 
    '$subjectAltName1','$subjectAltName2','$CertificateCenterCSRMD',$validTo_time_t,$validFrom_time_t,'$privkey','$privkey')";
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            $tpl->js_error($q->mysql_error);
            return false;
        }

        header("content-type: application/x-javascript");
        echo "dialogInstance6.close();\n";
        echo "LoadAjax('table-loader-sslcerts-service','fw.certificates-center.php?table=yes');\n";
        return true;
    }

    $certificate_data=$q->sqlite_escape_string2($data);

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM sslcertificates WHERE CommonName='$CommonName'");
    if(intval($ligne["ID"])>0){
        $CommonName="{$CommonName}_".time();
    }

    $sql="INSERT INTO sslcertificates (CommonName,UsePrivKeyCrt,bundle,crt,SquidCert,Squidkey,stateOrProvinceName,localityName,OrganizationName,OrganizationalUnit,AsRoot,DateFrom,DateTo,emailAddress,CountryName,subjectKeyIdentifier,Generated,privkey,Squidkey,csr)";

    if(!$q->FIELD_EXISTS("sslcertificates",'Generated')){
        $q->QUERY_SQL("ALTER TABLE sslcertificates ADD Generated INTEGER NOT NULL DEFAULT 0");
    }
    writelogs("$CommonName OrganizationalUnit   = $OrganizationalUnit", __FUNCTION__, __FILE__, __LINE__);
    writelogs("$CommonName OrganizationName     = $OrganizationName", __FUNCTION__, __FILE__, __LINE__);
    $CommonNameEnc=urlencode($CommonName);
    $f[]="'$CommonName'";
    $f[]="'1'";
    $f[]="''";
    $f[]="'$certificate_data'";
    $f[]="'$certificate_data'";
    $f[]="''";
    $f[]="'$stateOrProvinceName'";
    $f[]="'$localityName'";
    $f[]="'$OrganizationName'";
    $f[]="'$OrganizationalUnit'";
    $f[]="'0'";
    $f[]="'$validFrom_time'";
    $f[]="'$validTo_time'";
    $f[]="'$emailAddress'";
    $f[]="'$CountryName'";
    $f[]="''";
    $f[]="'1'";
    writelogs("$CommonName privkey = ".strlen($privkey)." bytes", __FUNCTION__, __FILE__, __LINE__);
    $f[]="'$privkey'";
    $f[]="'$privkey'";
    $f[]="'$csrData'";


    $sqlcmd=$sql." VALUES (".@implode(",",$f).")";
    $q->QUERY_SQL($sqlcmd);
    writelogs(PROGRESS_DIR."/certificate.sql --> sql data", __FUNCTION__, __FILE__, __LINE__);
    @file_put_contents(PROGRESS_DIR."/certificate.sql",$sqlcmd);
    if(!$q->ok){$tpl->js_error($q->mysql_error);return false;}

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM sslcertificates WHERE CommonName='$CommonName'");
    $ID=intval($ligne["ID"]);

    writelogs("$CommonNameEnc ID=$ID", __FUNCTION__, __FILE__, __LINE__);
    $js="Loadjs('fw.certificates-center.php?certificate-js=$CommonNameEnc&ID=$ID')";

    header("content-type: application/x-javascript");
    echo "dialogInstance6.close();\n$js\n";
    echo "LoadAjax('table-loader-sslcerts-service','fw.certificates-center.php?table=yes');";
    return true;


}

function validateSslOptions($sslCertData,$sslKeyData){
    $tpl = new template_admin();
//    $sslChainFiles = $this->assembleChainFiles($this->chainPaths);

    $certResource = openssl_x509_read($sslCertData);
    if (!$certResource) {
        $tpl->js_error("The provided certificate is either not a valid X509 certificate or could not be read.",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }

    $keyResource = openssl_pkey_get_private($sslKeyData);
    if (!$keyResource) {
        $tpl->js_error("The provided private key is either not a valid RSA private key or could not be read.",
            __FUNCTION__,__FILE__,__LINE__);
        return false;
    }

    $keyMatch = openssl_x509_check_private_key($certResource, $keyResource);
    if (!$keyMatch) {
        $tpl->js_error("The provided certificate does not match the provided private key.",
            __FUNCTION__,__FILE__,__LINE__);
        return false;

    }


    return true;
}

function ImportCA($data,$privkey=null,$csrdata=null){
    $tpl=new template_admin();
    $array=openssl_x509_parse($data);
    writelogs("ImportCA Start...", __FUNCTION__, __FILE__, __LINE__);
    if(!isset($array["issuer"])){
            $tpl->js_error("Issuer not found!");
            return;
    }

    foreach ($array as $key=>$val){
        if(is_array($val)){
            foreach ($val as $b=>$c){
                writelogs("openssl_x509_parse: $key ->  $b = [$c]",__FUNCTION__,__FILE__,__LINE__);
            }
            continue;
        }
        writelogs("openssl_x509_parse: $key = [$val]",__FUNCTION__,__FILE__,__LINE__);
    }

    $CountryName=null;
    $OrganizationName=null;
    $OrganizationalUnit=null;
    $stateOrProvinceName=null;
    $localityName=null;
    $CommonName=null;
    $emailAddress=null;
    $subjectKeyIdentifier=null;

    if(isset($array["issuer"]["C"])){$CountryName=$array["issuer"]["C"];}
    if(isset($array["issuer"]["O"])){$OrganizationName=$array["issuer"]["O"];}
    if(isset($array["issuer"]["OU"])){$OrganizationalUnit=$array["issuer"]["OU"];}
    if(isset($array["issuer"]["ST"])){$stateOrProvinceName=$array["issuer"]["ST"];}
    if(isset($array["issuer"]["CN"])){$CommonName=$array["issuer"]["CN"];}
    if(isset($array["issuer"]["L"])){$localityName=$array["issuer"]["L"];}
    if(isset($array["issuer"]["emailAddress"])){$emailAddress=$array["issuer"]["emailAddress"];}

    if(isset($array["extensions"]["subjectKeyIdentifier"])){
        $subjectKeyIdentifier=$array["extensions"]["subjectKeyIdentifier"];
    }

    $validFrom_time_t=$array["validFrom_time_t"];
    $validTo_time_t=$array["validTo_time_t"];
    $validFrom_time=date("Y-m-d H:i:s",$validFrom_time_t);
    $validTo_time=date("Y-m-d H:i:s",$validTo_time_t);
    writelogs("$CommonName subjectKeyIdentifier='$subjectKeyIdentifier'",__FUNCTION__,__FILE__,__LINE__);

    $sql="INSERT INTO sslcertificates (CommonName,UsePrivKeyCrt,privkey,bundle,crt,SquidCert,Squidkey,stateOrProvinceName,localityName,OrganizationName,OrganizationalUnit,AsRoot,Generated,DateFrom,DateTo,emailAddress,CountryName,subjectKeyIdentifier,csr)";
    $q=new lib_sqlite(GetDatabase());

    if(!$q->FIELD_EXISTS("sslcertificates","subjectKeyIdentifier")){$q->QUERY_SQL("ALTER TABLE sslcertificates ADD subjectKeyIdentifier TEXT NULL");}

    if($CommonName==null){$CommonName="unknown.certificate.".time();}
    $certificate_data=$q->sqlite_escape_string2($data);

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM sslcertificates WHERE CommonName='$CommonName'");
    if(intval($ligne["ID"])>0){
        $CommonName="{$CommonName}_".time();
    }
    $CommonNameEnc=urlencode($CommonName);
    writelogs("Private key = ".strlen($privkey)." bytes",__FUNCTION__,__FILE__,__LINE__);

    $f[]="'$CommonName'";
    $f[]="'1'";
    $f[]="'$privkey'";
    $f[]="''";
    $f[]="'$certificate_data'";
    $f[]="'$certificate_data'";
    $f[]="''";
    $f[]="'$stateOrProvinceName'";
    $f[]="'$localityName'";
    $f[]="'$OrganizationName'";
    $f[]="'$OrganizationalUnit'";
    $f[]="'1'";
    $f[]="'1'";
    $f[]="'$validFrom_time'";
    $f[]="'$validTo_time'";
    $f[]="'$emailAddress'";
    $f[]="'$CountryName'";
    $f[]="'$subjectKeyIdentifier'";
    $f[]="'$csrdata'";

    $q->QUERY_SQL($sql." VALUES (".@implode(",",$f).")");
    if(!$q->ok){$tpl->js_error($q->mysql_error);return;}

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM sslcertificates WHERE CommonName='$CommonName'");
    $ID=intval($ligne["ID"]);
    $js="Loadjs('fw.certificates-center.php?certificate-js=$CommonNameEnc&ID=$ID')";
    header("content-type: application/x-javascript");
    echo "dialogInstance6.close();\n$js\n";
    echo "LoadAjax('table-loader-sslcerts-service','fw.certificates-center.php?table=yes');";

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


function parse_array(){
    $parse=unserialize(base64_decode($_GET["parse"]));
    print_r($parse);
}
function PasswordForm(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=urlencode($_GET["filename"]);
    $form[]=$tpl->field_password("pfx-password","{password}",null);
    echo $tpl->form_outside("{import_pfx_file}",$form,null,"{apply}","Loadjs('$page?password-posted=yes&filename=$filename')");
}