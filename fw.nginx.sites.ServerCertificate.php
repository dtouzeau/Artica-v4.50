<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["www-tabs"])){www_tabs();exit;}
if(isset($_GET["parameters-popup"])){parameters_popup();exit;}
if(isset($_GET["parameters-popup2"])){parameters_popup2();exit;}
if(isset($_POST["ServerCertificate"])){SaveParameters();exit;}

if(isset($_POST["client_create_server"])) {client_certificate_server_save();exit;}
if(isset($_GET["client-certificate-server-generate"])){client_certificate_server_js();exit;}
if(isset($_GET["client-certificate-server-popup"])){client_certificate_server_popup();exit;}

if(isset($_POST["client-certificate-delete-client"])){client_certificate_client_delete_perform();exit;}
if(isset($_GET["client-certificate-delete-js"])){client_certificate_client_delete_js();exit;}
if(isset($_POST["client_create_client"])){client_certificate_client_save();exit;}
if(isset($_GET["client-certificates-list"])){client_certificate_list();exit;}
if(isset($_GET["client-certificates-list2"])){client_certificate_list2();exit;}
if(isset($_GET["client-certificate-create-js"])){client_certificate_create_js();exit;}
if(isset($_GET["client-certificate-create-popup"])){client_certificate_create_popup();exit;}
if(isset($_GET["pfx"])){client_certificate_download_pfx();exit;}

www_js();
function client_certificate_client_delete_js(){
    $ID=intval($_GET["client-certificate-delete-js"]);
    $q                          = new lib_sqlite(NginxGetDB());
    $md=$_GET["md"];
    $ligne = $q->mysqli_fetch_array("SELECT ClientName FROM nginx_clients_certs WHERE ID='$ID'");
    $CertificateName = $ligne["ClientName"];
    $tpl=new template_admin();
    $tpl->js_confirm_delete("#$ID: $CertificateName","client-certificate-delete-client",$ID,"$('#$md').remove();");

}
function client_certificate_client_delete_perform():bool{
    $ID=intval($_POST["client-certificate-delete-client"]);
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne = $q->mysqli_fetch_array("SELECT ClientName,certid FROM nginx_clients_certs WHERE ID='$ID'");
    $CertificateName = $ligne["ClientName"];
    $certid             = $ligne["certid"];
    $ligneCert          = $q->mysqli_fetch_array("SELECT CertificateName FROM nginx_servers_certs WHERE ID='$certid'");
    $certificate_server = $ligneCert["CertificateName"];
    $q->QUERY_SQL("DELETE FROM nginx_clients_certs WHERE ID='$ID'");
    if(!$q->ok){echo $q->mysql_error."\n";return false;}
    admin_tracks("Remove Client Certificate $CertificateName from $certificate_server");
    return true;
}
function client_certificate_create_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["client-certificate-create-js"]);
    if(!isset($_GET["function"])){$_GET["function"]=null;}
    $function=$_GET["function"];
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne = $q->mysqli_fetch_array("SELECT CertificateName FROM nginx_servers_certs WHERE ID='$ID'");
    $CertificateName = $ligne["CertificateName"];
    return $tpl->js_dialog_modal("$CertificateName: {create_client_certificate}","$page?client-certificate-create-popup=$ID&function=$function");
}
function client_certificate_server_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $ID=intval($_GET["client-certificate-server-generate"]);
    return $tpl->js_dialog_modal("{create_ca_certificate}","$page?client-certificate-server-popup=$ID&function=$function");
}

function client_certificate_client_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["client_create_client"]);
    $q      = new lib_sqlite(NginxGetDB());
    $ligne  = $q->mysqli_fetch_array("SELECT CertificateName FROM nginx_servers_certs WHERE ID='$ID'");
    $CertificateName=$ligne["CertificateName"];
    $username=$_POST["username"];
    $CertificateMaxDays=$_POST["CertificateMaxDays"];
    $HarmpID=HarmpID();
    if(strlen($_POST["password"])<3){
        $_POST["password"]="NONE";
    }
    $password=base64_encode($_POST["password"]);

    $sock=new sockets();

    $content=$sock->REST_API("/certificate/clientcert/$ID/$username/$CertificateMaxDays/$HarmpID/$password");
    $json=json_decode($content);
    if (json_last_error()> JSON_ERROR_NONE) {
        $csr_error="Decoding: ".strlen($content)." bytes ".json_last_error_msg();
        echo $tpl->post_error($csr_error);
        return false;
    }

    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }

    admin_tracks("Create a new client certificate $username for $CertificateName");
    return true;
}

function client_certificate_create_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();
    if(!isset($_GET["function"])){$_GET["function"]=null;}
    $function=$_GET["function"];
    $ID=intval($_GET["client-certificate-create-popup"]);
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne = $q->mysqli_fetch_array("SELECT CertificateName,ca_crt FROM nginx_servers_certs WHERE ID='$ID'");
    $ca_crt=base64_decode($ligne["ca_crt"]);
    $CLIENT_CERT_CLIENT_TEMP=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CLIENT_CERT_CLIENT_TEMP"));
    $array=openssl_x509_parse($ca_crt);
    $openssl_x509_read=openssl_x509_read($ca_crt);
    if(!isset($CLIENT_CERT_CLIENT_TEMP["CertificateMaxDays"])){$CLIENT_CERT_CLIENT_TEMP["CertificateMaxDays"]=365;}
    if(!isset($CLIENT_CERT_CLIENT_TEMP["username"])){$CLIENT_CERT_CLIENT_TEMP["username"]="";}

    if(empty($openssl_x509_read)) {
        echo $tpl->div_error('x509 cert could not be read');
    }
    $sttitles=array();
    foreach ($array["subject"] as $key=>$val){
        $sttitles[]=$val;
    }

    $subtitle=@implode(", ",$sttitles);
    $tpl->field_hidden("client_create_client",$ID);
    $form[]=$tpl->field_text("username", "{username}", $CLIENT_CERT_CLIENT_TEMP["username"],true,null);
    $form[]=$tpl->field_numeric("CertificateMaxDays","{CertificateMaxDays} ({days})",$CLIENT_CERT_CLIENT_TEMP["CertificateMaxDays"]);
    $form[]=$tpl->field_password("password","{password}", "");


    $func=null;
    if(strlen($function)>3){$func=$function."();";}

    $jsrestart="DialogModal.close();$func;";

    $tpl->form_add_button("{cancel}","DialogModal.close()");
    $html[]=$tpl->form_outside("$subtitle", $form,null,"{create}",$jsrestart,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function client_certificate_server_popup():bool{
    $ID=intval($_GET["client-certificate-server-popup"]);
    $t=time();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $ENC[1024]=1024;
    $ENC[2048]=2048;
    $ENC[4096]=4096;
    $socknginx=new socksngix($ID);
    $servicename=get_servicename($ID);
    $CLIENT_CERT_SERVER_TEMP=unserialize($socknginx->GET_INFO("CLIENT_CERT_SERVER_TEMP"));
    if(!isset($CLIENT_CERT_SERVER_TEMP["CertificateName"])){$CLIENT_CERT_SERVER_TEMP["CertificateName"]=$servicename;}
    if(!isset($CLIENT_CERT_SERVER_TEMP["levelenc"])){$CLIENT_CERT_SERVER_TEMP["levelenc"]=4096;}
    if(!isset($CLIENT_CERT_SERVER_TEMP["CertificateMaxDays"])){$CLIENT_CERT_SERVER_TEMP["CertificateMaxDays"]=3650;}

    if($ID>0) {
        $tpl->field_hidden("client_create_server", $ID);
    }else{
        $form[]=$tpl->field_choose_websites("client_create_server","{website}");
    }

    $form[]=$tpl->field_text("CertificateName", "{CertificateName}", $CLIENT_CERT_SERVER_TEMP["CertificateName"],true,null);
    $form[]=$tpl->field_numeric("CertificateMaxDays","{CertificateMaxDays} ({days})",$CLIENT_CERT_SERVER_TEMP["CertificateMaxDays"]);
    $sfunction=null;
    if($function<>null){$sfunction="$function();";}
    $jsrestart="DialogModal.close();$sfunction;LoadAjax('client-certificate-popup-$ID','$page?parameters-popup2=$ID')";
    $tpl->form_add_button("{cancel}","DialogModal.close()");

    $html[]=$tpl->form_outside("{create_ca_certificate}", $form,"{CLIENT_ROOT_CERT_EXPLAIN}","{create}",$jsrestart,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function client_certificate_download_pfx():bool{
    $ID=intval($_GET["pfx"]);
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne = $q->mysqli_fetch_array("SELECT ClientName,certid,user_pfx,user_key,user_crt FROM nginx_clients_certs WHERE ID='$ID'");
    $CertificateName = $ligne["ClientName"];
    $certid             = $ligne["certid"];
    $ligneCert          = $q->mysqli_fetch_array("SELECT ca_key,CertificateName FROM nginx_servers_certs WHERE ID='$certid'");
    $certificate_server = $ligneCert["CertificateName"];
    $user_key=base64_decode($ligneCert["ca_key"]);


    $user_crt=base64_decode($ligne["user_crt"]);
    $user_pfx=base64_decode($ligne["user_pfx"]);
   // $user_key=base64_decode($ligne["user_key"]);
    $final_content=$user_pfx;

    $content_type=" application/x-pkcs12";
    $tfilename="$certificate_server-$CertificateName.pfx";

    if(isset($_GET["pem"])){
        $content_type=" application/x-pem-file";
        $tfilename="$certificate_server-$CertificateName.pem";
        $final_content="$user_crt\n$user_key\n";
    }
    if(isset($_GET["txt"])){
        $content_type=" text/plain";
        $tfilename="$certificate_server-$CertificateName.txt";
        $final_content="$user_crt\n$user_key\n";
    }
    $fsize=strlen($final_content);
    $tfilename=str_replace(" ","-",$tfilename);
    if(!isset($_GET["VERBOSE"])) {
        header('Content-type: ' . $content_type);
        header('Content-Transfer-Encoding: binary');
        header("Content-Disposition: attachment; filename=\"$tfilename\"");
        header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
        header("Content-Length: " . $fsize);
        ob_clean();
        flush();
        echo $final_content;

    }

    admin_tracks("Downloaded Client PFX Certificate $CertificateName from $certificate_server");
    return true;
}
function client_certificate_server_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["client_create_server"]);
    if($ID==0){echo $tpl->post_error("{error_must_choose_website}");return false;}
    $CertificateName=$_POST["CertificateName"];
    $CertificateMaxDays=$_POST["CertificateMaxDays"];
    $HarmpID=HarmpID();

    $sock=new sockets();
    $content=$sock->REST_API("/certificate/servercert/$CertificateName/$CertificateMaxDays/$ID/$HarmpID");
    $json=json_decode($content);
    if (json_last_error()> JSON_ERROR_NONE) {
        $csr_error="Decoding: ".strlen($content)." bytes ".json_last_error_msg();
        echo  $tpl->post_error($csr_error);
        return false;
    }

    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }

    $servicename=get_servicename($ID);
    $sockngix                   = new socksngix($ID);
    $sockngix->SET_INFO("EnableClientCertificate",1);
    admin_tracks("Create Server certificate {$_POST["CertificateName"]} for $servicename");

    return true;
}


function www_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ID=intval($_GET["client-certificate-js"]);
    $servicename=get_servicename($ID);
    $tpl->js_dialog2("#$ID - $servicename {client_side_certificate}", "$page?www-tabs=$ID&function=$function",950);

}
function www_tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["www-tabs"]);
    $function=$_GET["function"];
    $array["{global_parameters}"]="$page?parameters-popup=$ID&function=$function";
    $array["{clients}"]="$page?client-certificates-list=$ID&function=$function";
    echo $tpl->tabs_default($array);
    return true;
}
function parameters_popup():bool{
    $page                       = CurrentPageName();
    $ID                         = intval($_GET["parameters-popup"]);
    echo "<div id='client-certificate-popup-$ID'></div><script>LoadAjax('client-certificate-popup-$ID','$page?parameters-popup2=$ID')</script>";
    return true;
}
function parameters_popup2():bool{
    $page                       = CurrentPageName();
    $tpl                        = new template_admin();
    $ID                         = intval($_GET["parameters-popup2"]);
    $sockngix                   = new socksngix($ID);
    $ssl_client_certificate     = $sockngix->GET_INFO("ssl_client_certificate");
    $EnableClientCertificate    = $sockngix->GET_INFO("EnableClientCertificate");
    $OptionalClientCertificate= $sockngix->GET_INFO("OptionalClientCertificate");
    $db                         = NginxGetDB();
    $q                          = new lib_sqlite($db);

    $servicename=get_servicename($ID);
    $CERTS=array();
    if($q->TABLE_EXISTS("nginx_servers_certs")){
        $results=$q->QUERY_SQL("SELECT * FROM nginx_servers_certs ORDER BY CertificateName");
       if(!$q->ok){
           echo $q->mysql_error;
       }
        foreach ($results as $index=>$ligne){
            $CERTS[$ligne["ID"]]=$ligne["CertificateName"];
        }
    }

    $q=new lib_sqlite(CertificateDatabase());
    $results=$q->QUERY_SQL("SELECT * FROM sslcertificates WHERE ServerCert=1 ORDER BY CommonName");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    foreach ($results as $index=>$ligne){
        $CERTS["CC:".$ligne["ID"]]=$ligne["CommonName"];
    }

    $js[]="Loadjs('fw.nginx.sites.php?success-js=yes');";
    $js[]="LoadAjax('www-parameters-$ID','fw.nginx.sites.php?www-parameters2=$ID');";
    $js[]="dialogInstance2.close();";
    $js[]="NgixSitesReload();";

    $tpl->field_hidden("ServerCertificate",$ID);
    $form[]=$tpl->field_checkbox("EnableClientCertificate","{enable_feature}",$EnableClientCertificate,true);

    $form[]=$tpl->field_checkbox("OptionalClientCertificate","{optional}",$OptionalClientCertificate);

    $form[]=$tpl->field_array_hash($CERTS,"certificate","nonull:{server_certificate}",$ssl_client_certificate);

    if(strlen($ssl_client_certificate)==0 or $EnableClientCertificate==0) {
        $tpl->form_add_button("{wizard}: {create_ca_certificate}", "Loadjs('$page?client-certificate-server-generate=$ID')");
    }


    echo $tpl->form_outside("$servicename {client_side_certificate}", $form,"{authenticate_ssl_client_explain}","{apply}",@implode("",$js),"AsSystemWebMaster");
    return true;
}
function SaveParameters():bool{
    $tpl                        = new template_admin();
    $tpl->CLEAN_POST();
    $ID                         = intval($_POST["ServerCertificate"]);
    $sockngix                   = new socksngix($ID);
    $certificate=$_POST["certificate"];
    $sockngix->SET_INFO("EnableClientCertificate",$_POST["EnableClientCertificate"]);
    $sockngix->SET_INFO("OptionalClientCertificate",$_POST["OptionalClientCertificate"]);
    $sockngix->SET_INFO("ssl_client_certificate",$certificate);


    $servicename=get_servicename($ID);


    $sock=new sockets();
    $data=$sock->REST_API_NGINX("/reverse-proxy/single/$ID");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->post_error("Decoding: ".strlen($data)." bytes<hr>".json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    return admin_tracks("Reverse-Proxy: $servicename Edit Client Certificate enabled={$_POST["EnableClientCertificate"]}, certificate={$_POST["certificate"]}");
}

function CertificateDatabase():string{
    $db="/home/artica/SQLITE/certificates.db";
    if(isset($_SESSION["HARMPID"])){
        $gpid=intval($_SESSION["HARMPID"]);
        if($gpid>0){
            $db="/home/artica/SQLITE/certificates.$gpid.db";
        }
    }
    VERBOSE("Database: $db",__LINE__);
    return $db;
}

function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return $ligne["servicename"];
}
function www_parameters():bool{
    $page=CurrentPageName();
    $ID=intval($_GET["www-parameters"]);
    echo "<div id='optimize-nginx-$ID' style='margin-top:10px'></div>";
    echo "<script>LoadAjax('optimize-nginx-$ID','$page?www-parameters2=$ID');</script>";
    return true;
}

function HarmpID():int{
    if(!isset($_SESSION["HARMPID"])){return 0;}
    if(intval($_SESSION["HARMPID"])==0){
        return 0;
    }
    return intval($_SESSION["HARMPID"]);
}

function isHarmpID():bool{
    $HarmpID=HarmpID();
    if($HarmpID==0){return false;}

    return true;
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}


function client_certificate_list():bool{
    $page                       = CurrentPageName();
    $ID                         = intval($_GET["client-certificates-list"]);
    $sockngix                   = new socksngix($ID);
    $ssl_client_certificate     =  intval($sockngix->GET_INFO("ssl_client_certificate"));
    $tpl                        = new template_admin();

    $html[]="<div style='margin-top:15px'>";
    $html[]=$tpl->search_block("fw.nginx.clients.certificates.php",null,null,null,"&clients-list=$ssl_client_certificate&serviceid=$ID");
    $html[]="</div>";
    echo @implode("\n",$html);
    return true;
}
