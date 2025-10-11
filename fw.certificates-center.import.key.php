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



function js():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$users=new usersMenus();
    $Addon=null;
	$_SESSION["CERTIFID"]=$_GET["ID"];

    if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);
        if ($gpid > 0) {
            $Addon="&HarmpID=$gpid";
            $users->AsCertifsManager=true;
        }
    }

	if(!$users->AsCertifsManager){$tpl->js_no_privileges();return false; }
	return $tpl->js_dialog6("{certificates} >> {import} {private_key}", "$page?popup=yes$Addon","650");
	
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

function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$bt_upload=$tpl->button_upload("{private_key}",$page)."&nbsp;&nbsp;";
	$html="
    <div id='ca-form-import'>
        <div class='center'>$bt_upload</div>
     </div>
	<div id='progress-certificates-center-import'></div>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function file_uploaded(){
    $tpl=new template_admin();
    $file=$_GET["file-uploaded"];
    $uploaddir="/usr/share/artica-postfix/ressources/conf/upload";
    $fullpath="$uploaddir/$file";
    $ID=$_SESSION["CERTIFID"];
    $data=@file_get_contents($fullpath);
    @unlink($fullpath);
    $data=str_replace("\r","",$data);
    $rsa=array();
    $ENCRYPTED=false;
    if(preg_match("#BEGIN ENCRYPTED PRIVATE KEY#is",$data)){
        $SrcData=$data;
        $ENCRYPTED=true;
        writelogs("BEGIN ENCRYPTED PRIVATE KEY found in data, convert it",__FUNCTION__,__FILE__,__LINE__);
        $tmpfile="$uploaddir/servenc.key";
        $dstfile="$uploaddir/serv.key";
        @file_put_contents($tmpfile,$data);
        exec("/usr/bin/openssl rsa -in $tmpfile -out $dstfile 2>&1",$rsa);
        writelogs("/usr/bin/openssl rsa -in $tmpfile -out $dstfile",__FUNCTION__,__FILE__,__LINE__);
        $data=@file_get_contents($dstfile);
        @unlink($tmpfile);
        @unlink($dstfile);
        if(strlen($data)<10){
            $ENCRYPTED=true;
            $data=$SrcData;
        }
    }else{
        writelogs("BEGIN ENCRYPTED PRIVATE KEY not found in data",__FUNCTION__,__FILE__,__LINE__);
    }

    if(!$ENCRYPTED) {
        if (!preg_match("#-----BEGIN PRIVATE KEY-----(.+?)-----END PRIVATE KEY-----#is", $data, $re)) {
            $tpl->js_error("Private Key failed (not a private key data)" . @implode("<br>", $rsa));
            return;
        }
        $data = "-----BEGIN PRIVATE KEY-----{$re[1]}-----END PRIVATE KEY-----\n";
    }

    $sql="SELECT `crt`,`SquidCert`,`UsePrivKeyCrt`,`UseGodaddy`  FROM sslcertificates WHERE ID='$ID'";
    $q=new lib_sqlite(GetDatabase());
    $ligne=$q->mysqli_fetch_array($sql);

    $field="crt";
    if($ligne["UsePrivKeyCrt"]==0){$field="SquidCert";}
    $ligne[$field]=str_replace("\\n", "\n", $ligne[$field]);

    if(!$ENCRYPTED) {
        $result = openssl_x509_check_private_key($ligne[$field], $data);
        if (!$result) {
            $tpl->js_error("Private Key failed<br>key length:" . strlen($data) . "<br>$data");
            return;
        }
    }

    $data=$q->sqlite_escape_string2($data);

    $q->QUERY_SQL("UPDATE sslcertificates SET privkey='$data',Squidkey='$data' WHERE ID=$ID");
    header("content-type: application/x-javascript");
    echo "dialogInstance6.close();\n";
    echo "LoadAjax('DIVKEY{$ID}','fw.certificates-center.php?certificate-privkey2=$ID');\n";
    echo "LoadAjax('table-loader-sslcerts-service','fw.certificates-center.php?table=yes');";


}

function ImportSubCertificates($data){
    $tpl=new template_admin();
    $array=openssl_x509_parse($data);
    $CountryName=null;
    $OrganizationName=null;
    $OrganizationalUnit=null;
    $stateOrProvinceName=null;
    $localityName=null;
    $CommonName=null;
    $emailAddress=null;
    $subjectKeyIdentifier=null;
    $certid=0;
    $WHERE=array();
    if(!isset($array["subject"])){
        $tpl->js_error("subject not found!");
        return;
    }

    if(isset($array["issuer"]["O"])){
        $WHERE[]="organizationName='{$array["issuer"]["O"]}'";
    }
    if(isset($array["issuer"]["OU"])){
        $WHERE[]="OrganizationalUnit='{$array["issuer"]["OU"]}'";
    }
    if(isset($array["issuer"]["CN"])){
        $WHERE[]="commonName='{$array["issuer"]["CN"]}'";
    }
    if(isset($array["issuer"]["emailAddress"])){
        $WHERE[]="emailAddress='{$array["issuer"]["emailAddress"]}'";
    }



    $q=new lib_sqlite(GetDatabase());
    if(!$q->FIELD_EXISTS("sslcertificates","subjectKeyIdentifier")){$q->QUERY_SQL("ALTER TABLE sslcertificates ADD subjectKeyIdentifier TEXT NULL");}

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

    if($certid>0){
        $UsePrivKeyCrt=1;
        $levelenc=2048;
        $p12Data='';
        $csrData='';
        $key_data='';
        $certificate_data=base64_encode($data);
        $CertificateCenterCSRMD=null;
        $sql="INSERT INTO subcertificates (certid,UsePrivKeyCrt,Certype,levelenc,countryName,
    stateOrProvinceName,localityName,organizationName,organizationalUnitName,commonName,emailAddress,
    pks12,csr,srca,crt,subjectAltName,subjectAltName1,subjectAltName2,CertificateCenterCSR,DateTo,DateFrom)
    VALUES ($certid,$UsePrivKeyCrt,1,$levelenc,'$CountryName','$stateOrProvinceName','$localityName','$OrganizationName',
    '$OrganizationalUnit','$CommonName','$emailAddress','$p12Data','$csrData','$key_data','$certificate_data','$subjectAltName', 
    '$subjectAltName1','$subjectAltName2','$CertificateCenterCSRMD',$validTo_time_t,$validFrom_time_t)";
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
        $tpl->js_error("$CommonName {already_exists}");
        return;
    }

    $sql="INSERT INTO sslcertificates (CommonName,UsePrivKeyCrt,privkey,bundle,crt,SquidCert,Squidkey,stateOrProvinceName,localityName,OrganizationName,OrganizationalUnit,AsRoot,Generated,DateFrom,DateTo,emailAddress,CountryName,subjectKeyIdentifier,Generated)";

    $f[]="'$CommonName'";
    $f[]="'1'";
    $f[]="''";
    $f[]="''";
    $f[]="'$certificate_data'";
    $f[]="'$certificate_data'";
    $f[]="''";
    $f[]="'$stateOrProvinceName'";
    $f[]="'$localityName'";
    $f[]="'$OrganizationName'";
    $f[]="'$OrganizationalUnit'";
    $f[]="'0'";
    $f[]="'1'";
    $f[]="'$validFrom_time'";
    $f[]="'$validTo_time'";
    $f[]="'$emailAddress'";
    $f[]="'$CountryName'";
    $f[]="''";
    $f[]="'1'";

    $q->QUERY_SQL($sql." VALUES (".@implode(",",$f).")");
    if(!$q->ok){$tpl->js_error($q->mysql_error);return;}

    header("content-type: application/x-javascript");
    echo "dialogInstance6.close();\n";
    echo "LoadAjax('table-loader-sslcerts-service','fw.certificates-center.php?table=yes');";



}

function ImportCA($data){
    $tpl=new template_admin();
    $array=openssl_x509_parse($data);

    if(!isset($array["issuer"])){
            $tpl->js_error("Issuer not found!");
            return;
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

    $sql="INSERT INTO sslcertificates (CommonName,UsePrivKeyCrt,privkey,bundle,crt,SquidCert,Squidkey,stateOrProvinceName,localityName,OrganizationName,OrganizationalUnit,AsRoot,Generated,DateFrom,DateTo,emailAddress,CountryName,subjectKeyIdentifier)";
    $q=new lib_sqlite(GetDatabase());

    if(!$q->FIELD_EXISTS("sslcertificates","subjectKeyIdentifier")){$q->QUERY_SQL("ALTER TABLE sslcertificates ADD subjectKeyIdentifier TEXT NULL");}


    $certificate_data=$q->sqlite_escape_string2($data);

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM sslcertificates WHERE CommonName='$CommonName'");
    if(intval($ligne["ID"])>0){
        $tpl->js_error("$CommonName {already_exists}");
        return;
    }

    $f[]="'$CommonName'";
    $f[]="'1'";
    $f[]="''";
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

    $q->QUERY_SQL($sql." VALUES (".@implode(",",$f).")");
    if(!$q->ok){$tpl->js_error($q->mysql_error);return;}

    header("content-type: application/x-javascript");
    echo "dialogInstance6.close();\n";
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
    $parse=unserializeb64($_GET["parse"]);
    print_r($parse);
}
function PasswordForm(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=urlencode($_GET["filename"]);
    $form[]=$tpl->field_password("pfx-password","{password}",null);
    echo $tpl->form_outside("{import_pfx_file}",$form,null,"{apply}","Loadjs('$page?password-posted=yes&filename=$filename')");
}