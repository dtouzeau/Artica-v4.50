<?php
# SP206
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}

if(isset($_GET["create-pfx"])){create_pfx();exit;}
if(isset($_GET["certificate-flat"])){certificate_flat();exit;}
if(isset($_GET["FormSearch"])){form_search();exit;}
if(isset($_POST["ChangeCert"])){change_cert_save();exit;}
if(isset($_POST["NEW_SERVER_CERTIFICATE"])){subcertificates_new_server_save();exit;}
if(isset($_POST["NEW_CLIENT_CERTIFICATE"])){subcertificates_new_client_save();exit;}

if(isset($_POST["SETTINGS"])){certificate_settings_save();exit;}
if(isset($_GET["certificate-locked-not-uploaded"])){certificate_settings_locked_empty();exit;}
if(isset($_GET["csr-js"])){certificate_csr_js();exit;}
if(isset($_GET["crt-js"])){certificate_crt_js();exit;}
if(isset($_GET["autorenew-js"])){autorenew_js();exit;}
if(isset($_GET["autorenew-popup"])){autorenew_popup();exit;}
if(isset($_POST["LetsEncryptRenewAfterDays"])){autorenew_save();exit;}

if(isset($_GET["srca-js"])){certificate_srca_js();exit;}
if(isset($_GET["privkey-js"])){certificate_privkey_js();exit;}
if(isset($_GET["bundle-js"])){certificate_bundle_js();exit;}
if(isset($_GET["CABundleProvider-js"])){certificate_provider_bundle_js();exit;}
if(isset($_GET["CABundleProvider"])){certificate_provider_bundle_popup();exit;}
if(isset($_POST["SAVE_CABUNDLE"])){certificate_provider_bundle_save();exit;}



if(isset($_GET["certificate-flat2"])){certificate_flat2();exit;}
if(isset($_GET["new-certificate-js"])){certificate_new_js();exit;}
if(isset($_GET["new-certificate-popup"])){certificate_new_popup();exit;}
if(isset($_GET["repair-database"])){repair_database();exit;}
if(isset($_GET["patch-database"])){patch_database();exit;}



if(isset($_POST["GENCSR"])){certificate_new_save();exit;}
if(isset($_GET["display-csr-js"])){display_csr_js();exit;}
if(isset($_GET["display-csr-popup"])){display_csr_popup();exit;}
if(isset($_GET["certificate-js"])){certificate_js();exit;}
if(isset($_GET["certificate-popup"])){certificate_tab();exit;}
if(isset($_GET["certificate-settings"])){certificate_settings();exit;}
if(isset($_GET["certificate-csr-verify"])){certificate_csr_verify();exit;}
if(isset($_GET["certificate-csr"])){certificate_csr();exit;}
if(isset($_GET["certificate-crt"])){certificate_crt();exit;}
if(isset($_GET["certificate-crt-verify"])){certificate_crt_verify();exit;}
if(isset($_GET["certificate-srca"])){certificate_srca();exit;}
if(isset($_GET["certificate-srca-verify"])){certificate_srca_verify();exit;}
if(isset($_GET["certificate-privkey2"])){certificate_privkey2();exit;}
if(isset($_GET["certificate-privkey"])){certificate_privkey();exit;}
if(isset($_GET["certificate-privkey-verify"])){certificate_privkey_verify();exit;}
if(isset($_GET["certificate-info"])){certificate_info_crt_popup();exit;}
if(isset($_GET["certificate-bundle"])){certificate_bundle();exit;}
if(isset($_GET["certificate-bundle-verify"])){certificate_bundle_verify();exit;}
if(isset($_POST["SAVE_CONTENT"])){SAVE_CONTENT();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["getdb"])){getdb();exit;}
if(isset($_GET["analyze-js"])){analyze_js();exit;}
if(isset($_GET["letsencrypt-dns-js"])){letsencrypt_dns_js();exit;}
if(isset($_GET["letsencrypt-dnskey-js"])){letsencrypt_dns_key_js();exit;}
if(isset($_GET["letsencrypt-dnskey-popup"])){letsencrypt_dns_key_popup();exit;}
if(isset($_GET["download"])){export_single_certificate();exit;}
if(isset($_GET["download-cert"])){download_certificate();exit;}
if(isset($_GET["download-pfx"])){download_pfx();exit;}

if(isset($_GET["subcertificates-js"])){subcertificates_only_js();exit;}
if(isset($_GET["subcertificates"])){subcertificates_start();exit;}
if(isset($_GET["subcertificates-table"])){subcertificates_table();exit;}
if(isset($_GET["new-server-certificate"])){subcertificates_new_server_js();exit;}
if(isset($_GET["new-server-certificate-popup"])){subcertificates_new_server_popup();exit;}
if(isset($_GET["del-server-certificate"])){subcertificates_delete_js();exit;}
if(isset($_POST["del-server-certificate"])){subcertificates_delete();exit;}
if(isset($_GET["show-server-certificate"])){subcertificates_js();exit;}
if(isset($_GET["subcertificates-settings"])){subcertificates_settings();exit;}
if(isset($_GET["subcertificates-tab"])){subcertificates_tabs();exit;}
if(isset($_GET["subcertificates-crt"])){subcertificates_crt();exit;}
if(isset($_GET["subcertificates-privkey"])){subcertificates_privkey();exit;}
if(isset($_GET["subcertificates-pks12"])){subcertificates_pks12();exit;}
if(isset($_GET["subcertificates-verify"])){subcertificates_verify();exit;}
if(isset($_GET["subcertificates-info"])){subcertificates_info();exit;}
if(isset($_GET["subcertificates-top-buttons"])){subcertificates_top_buttons();exit;}

if(isset($_GET["new-client-certificate"])){subcertificates_new_client_js();exit;}
if(isset($_GET["new-client-certificate-popup"])){subcertificates_new_client_popup();exit;}
if(isset($_GET["dashboard"])){from_dashboard();exit;}
if(isset($_GET["change-cert-js"])){change_cert_js();exit;}
if(isset($_GET["change-cert-popup"])){change_cert_popup();exit;}





page();

function patch_database():bool{
    $function=$_GET["function"];
    $sock=new sockets();
    $tpl        = new template_admin();
    $gpid=0;
    if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);
    }
    $data=$sock->REST_API("/certificate/patchdb/$gpid");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        return $tpl->js_error("Decoding: ".strlen($data)." bytes<hr>".json_last_error_msg());

    }
    if(!$json->Status){
        return $tpl->js_error($json->Error);
    }
    echo "$function();";
    return true;
}

function create_pfx():bool{
    $tpl        = new template_admin();
    $function=$_GET["function"];
    $gpid=0;
    if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);
    }
    $CommonName=$_GET["create-pfx"];
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/certificate/pfx/create/$CommonName/$gpid"));
    if(!$json->Status){
        return $tpl->js_error($json->Error);
    }
    echo "$function();";
    return true;

}

function repair_database():bool{
    $function=$_GET["function"];
    $sock=new sockets();
    $tpl        = new template_admin();
    $gpid=0;
    if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);
    }
    $data=$sock->REST_API("/certificate/rebuilddb/$gpid");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        return $tpl->js_error("Decoding: ".strlen($data)." bytes<hr>".json_last_error_msg());

    }
    if(!$json->Status){
        return $tpl->js_error($json->Error);
    }
    echo "$function();";
    return true;
}

function change_cert_js(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $q          = new lib_sqlite(GetDatabase());
    $ID         = intval($_GET["change-cert-js"]);
    $sql        = "SELECT CommonName FROM sslcertificates WHERE ID=$ID";
    $ligne      = $q->mysqli_fetch_array($sql);
    $CommonName = $ligne["CommonName"];
    $tpl->js_dialog2("$CommonName","$page?change-cert-popup=$ID");

}
function change_cert_popup(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $q          = new lib_sqlite(GetDatabase());
    $ID         = intval($_GET["change-cert-popup"]);
    $sql        = "SELECT CommonName FROM sslcertificates WHERE ID=$ID";
    $ligne      = $q->mysqli_fetch_array($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    $CommonName = $ligne["CommonName"];
    $jsafter="dialogInstance1.close();dialogInstance2.close();LoadAjax('table-loader-sslcerts-service','$page?table=yes');";
    $tpl->field_hidden("ChangeCert",$ID);
    $form[]=$tpl->field_text("CommonName","{commonName}",$CommonName);
    $html=$tpl->form_outside($CommonName, @implode("\n", $form),null,"{edit}",$jsafter,"AsCertifsManager");
    echo $html;
}
function change_cert_save(){
    $tpl        = new template_admin();
    $q          = new lib_sqlite(GetDatabase());
    $ID         = intval($_POST["ChangeCert"]);
    $CommonName = trim($_POST["CommonName"]);
    if($CommonName==null){echo "jserror:" . $tpl->javascript_parse_text("{your_query_is_empty}");return false;}
    $CommonName = str_replace("'","_",$CommonName);
    $CommonName = replace_accents($CommonName);
    $q->QUERY_SQL("UPDATE sslcertificates SET CommonName='$CommonName' WHERE ID=$ID");
    if(!$q->ok){echo "jserror:" . $tpl->javascript_parse_text($q->mysql_error);return false;}
    return true;
}
//dialogInstance$number.close();

function subcertificates_pks12():bool{
    $ID=intval($_GET["subcertificates-pks12"]);
    $q=new lib_sqlite(GetDatabase());
    $return=null;
    $sql="SELECT commonName,pks12 FROM subcertificates WHERE ID=$ID";
    $ligne=$q->mysqli_fetch_array($sql);
    $final_content=base64_decode($ligne["pks12"]);
    if($GLOBALS["VERBOSE"]){
        echo "PKS12: $ID: {$ligne["commonName"]} = {$ligne["pks12"]}<br>";
        echo "$final_content";
        return true;
    }

    $CommonName=$ligne["commonName"];
    $final_content=base64_decode($ligne["pks12"]);
    $fsize=strlen($final_content);
    $content_type=" application/x-pkcs12";
    header('Content-type: '.$content_type);
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$CommonName.pfx\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    echo $final_content;
    return true;
}

function subcertificates_crt(){
    $tpl = new template_admin();
    $ID = intval($_GET["subcertificates-crt"]);
    $q=new lib_sqlite(GetDatabase());
    $sql="SELECT certid,srca,crt FROM subcertificates WHERE ID=$ID";
    $ligne=$q->mysqli_fetch_array($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    $certificateData=base64_decode($ligne["crt"]);
    $commonName=$ligne["commonName"];
    $form[]=$tpl->field_textarea("csr", null,$certificateData,"664px");
    echo $tpl->form_outside($commonName, @implode("\n", $form),"{public_key_ssl_explain}",null,null);
}
function subcertificates_privkey(){
    $tpl = new template_admin();
    $ID = intval($_GET["subcertificates-privkey"]);
    $q=new lib_sqlite(GetDatabase());
    $sql="SELECT certid,srca,crt FROM subcertificates WHERE ID=$ID";
    $ligne=$q->mysqli_fetch_array($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    $certid=intval($ligne["certid"]);
    $certificateData=base64_decode($ligne["srca"]);

    $commonName=$ligne["commonName"];
    $form[]=$tpl->field_textarea("csr", null,$certificateData,"664px");
    echo $tpl->form_outside($commonName, @implode("\n", $form),"{public_key_ssl_explain}",null,null);

}

function subcertificates_verify(){
    $tpl = new template_admin();
    $ID=intval($_GET["subcertificates-verify"]);
    $q=new lib_sqlite(GetDatabase());
    $sql="SELECT certid,srca,crt FROM subcertificates WHERE ID=$ID";
    $ligne=$q->mysqli_fetch_array($sql);
    $certid=$ligne["certid"];
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}

    $CertificatePath=PROGRESS_DIR."/certificate.pem";
    $KeyPath=PROGRESS_DIR."/cakey.pem";
    $crtdata=base64_decode($ligne["crt"]);
    @file_put_contents($CertificatePath,$crtdata);

    $sql="SELECT crt FROM sslcertificates WHERE ID='$certid'";
    $ligne=$q->mysqli_fetch_array($sql);
    @file_put_contents($KeyPath,$ligne["crt"]);

    $cmd="/usr/bin/openssl verify -purpose sslserver -CAfile $KeyPath -verbose  $CertificatePath 2>&1";

    exec("$cmd",$resultsCMD);
    foreach ($resultsCMD as $line){
        $line=str_replace("/usr/share/artica-postfix/ressources/logs/web/","",$line);
        $line=str_replace("OK","<span class='label label-primary'>OK</span>",$line);
        echo "<div>$line</div>";
    }


}
function autorenew_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $LetsEncryptRenewAfterDays=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LetsEncryptRenewAfterDays"));
    $html[]=$tpl->div_explain("{autorenew}||{autorenew_letsencrypt}");
    if($LetsEncryptRenewAfterDays==0){$LetsEncryptRenewAfterDays=15;}
    $form[]=$tpl->field_text("LetsEncryptRenewAfterDays","{MaxDays}",$LetsEncryptRenewAfterDays);
    $html[]=$tpl->form_outside(null,  $form,null,"{apply}","dialogInstance5.close();","AsCertifsManager");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function autorenew_save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    return admin_tracks_post("Saving Certificate Center autorenew parameter");
}

function subcertificates_masterInfo($Masterid){
    $tpl=new template_admin();
    $q = new lib_sqlite(GetDatabase());
    $sql = "SELECT CommonName,ID FROM sslcertificates WHERE ID='$Masterid'";
    $ligne = $q->mysqli_fetch_array($sql);
    return $ligne;
}

function subcertificates_settings(){
    $tpl = new template_admin();
    $page = CurrentPageName();
    $q = new lib_sqlite(GetDatabase());
    $ID = intval($_GET["subcertificates-settings"]);
    $sql = "SELECT * FROM subcertificates WHERE ID='$ID'";
    $ligne = $q->mysqli_fetch_array($sql);
    $Masterid=$ligne["certid"];
    $html[]="<div class='ibox-content'><table style='width:100%' class='table'>";
    $MasterInfo=subcertificates_masterInfo($Masterid);
    $html[]="<tr>";
    $html[]="<td style='width:1%' nowrap><strong>{certificate} ({master2}):</strong></td>";
    if(!isset($MasterInfo["ID"])){
        $html[]="<td style='width:1%' nowrap><strong class='text-danger'>{missing_certificate}</strong></td>";
    }else {
        $html[] = "<td>$Masterid) {$MasterInfo["CommonName"]}</td>";
    }
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:1%' nowrap><strong>{CommonName}:</strong></td>";
    $html[]="<td>{$ligne["commonName"]}</td>";
    $html[]="</tr>";
    if($ligne["subjectAltName"]<>null){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{subjectAltName}:</strong></td>";
        $html[]="<td>{$ligne["subjectAltName"]}</td>";
        $html[]="</tr>";

    }
    if($ligne["subjectAltName1"]<>null){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{subjectAltName}:</strong></td>";
        $html[]="<td>{$ligne["subjectAltName1"]}</td>";
        $html[]="</tr>";

    }
    if($ligne["subjectAltName2"]<>null){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{subjectAltName}:</strong></td>";
        $html[]="<td>{$ligne["subjectAltName2"]}</td>";
        $html[]="</tr>";

    }



    if($ligne["CountryName"]<>null){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{countryName}:</strong></td>";
        $html[]="<td>{$ligne["CountryName"]}</td>";
        $html[]="</tr>";

    }
    if($ligne["stateOrProvinceName"]<>null){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{stateOrProvinceName}:</strong></td>";
        $html[]="<td>{$ligne["stateOrProvinceName"]}</td>";
        $html[]="</tr>";

    }
    if($ligne["localityName"]<>null){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{localityName}:</strong></td>";
        $html[]="<td>{$ligne["localityName"]}</td>";
        $html[]="</tr>";

    }
    if($ligne["OrganizationName"]<>null){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{organizationName}:</strong></td>";
        $html[]="<td>{$ligne["OrganizationName"]}</td>";
        $html[]="</tr>";

    }
    if($ligne["OrganizationalUnit"]<>null){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{organizationalUnitName}:</strong></td>";
        $html[]="<td>{$ligne["OrganizationalUnit"]}</td>";
        $html[]="</tr>";

    }
    if($ligne["emailAddress"]<>null){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{emailAddress}:</strong></td>";
        $html[]="<td>{$ligne["emailAddress"]}</td>";
        $html[]="</tr>";

    }
    if($ligne["levelenc"]<>null){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{level_encryption}:</strong></td>";
        $html[]="<td>{$ligne["levelenc"]}</td>";
        $html[]="</tr>";

    }
    if($ligne["CertificateMaxDays"]<>null){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{CertificateMaxDays}:</strong></td>";
        $html[]="<td>{$ligne["CertificateMaxDays"]} ({days}</td>";
        $html[]="</tr>";

    }
    $html[]="<tr>";
    $html[]="<td style='width:1%' nowrap>&nbsp;</td>";
    $html[]="<td><div id='verify-div$ID'></div></td>";
    $html[]="</tr>";
    $html[]="</table></div>

        
        <script>LoadAjax('verify-div$ID','$page?subcertificates-verify=$ID')</script>

        ";
    echo $tpl->_ENGINE_parse_body($html);
    return true;


}

function from_dashboard(){
    $tpl=new template_admin();
    $html="	<div class='row' style='margin-top:15px'>
	<div id='progress-certificate-center-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-sslcerts-service'></div>

	</div>
	</div>	<script>
	
	LoadAjax('table-loader-sslcerts-service','fw.certificates-center.php?table=yes');

	</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $Addon=null;
    if(isset($_GET["HarmpID"])){
        $_SESSION["HARMPID"]=intval($_GET["HarmpID"]);
        $Addon="&HarmpID={$_GET["HarmpID"]}";
    }else{
        if(isset($_SESSION["HARMPID"])){
            $Addon="&HarmpID={$_SESSION["HarmpID"]}";
        }
    }


    $html=$tpl->page_header("{certificates_center}",ico_certificate,
        "{ssl_certificates_center_text}","fw.certificates-center.php?FormSearch=yes$Addon",
        "certificate-center","progress-certificate-center-restart",false,"table-loader-sslcerts-service");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{certificates_center}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
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
    VERBOSE("Database: $db",__LINE__);
    return $db;
}
function subcertificates_only_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $OnlyClient="";
    $NoPass="";
    if(isset($_GET["OnlyClient"])){
        $OnlyClient="&OnlyClient=yes";
    }
    if(isset($_GET["NoPass"])){
        $NoPass="&NoPass=yes";
    }
    $CertID=intval($_GET["subcertificates-js"]);
    return $tpl->js_dialog5("{certificates}","$page?subcertificates=$CertID$OnlyClient$NoPass");

}
function subcertificates_start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $OnlyClient="";$NoPass="";
    if(isset($_GET["OnlyClient"])){
        $OnlyClient="&OnlyClient=yes";
    }
    if(isset($_GET["NoPass"])){
        $NoPass="&NoPass=yes";
    }

    $ID=intval($_GET["subcertificates"]);
    $html[]="<div style='padding:0;padding-top:10px;margin-bottom: 10px' id='subcertificate-buttons-$ID'></div>";
    $html[]=$tpl->search_block($page,null,null,null,"&subcertificates-table=$ID$OnlyClient$NoPass");
    echo @implode("\n",$html);
    return true;
}
function subcertificates_delete_js(){
    $tpl = new template_admin();
    $ID=intval($_GET["del-server-certificate"]);
    $md=$_GET["md"];
    $q = new lib_sqlite(GetDatabase());
    $ligne=$q->mysqli_fetch_array("SELECT commonName,organizationName,emailAddress FROM subcertificates WHERE ID=$ID");
    if(!$q->ok){
        $tpl->js_mysql_alert($q->mysql_error);
        return;
    }
    $commonName=$ligne["commonName"];
    $organizationName=$ligne["organizationName"];
    $emailAddress=$ligne["emailAddress"];
    $tpl->js_confirm_delete("$commonName, $organizationName $emailAddress ($ID)","del-server-certificate",$ID,"$('#$md').remove()");

}
function subcertificates_delete():bool{
    $ID=intval($_POST["del-server-certificate"]);
    $q = new lib_sqlite(GetDatabase());
    $q->QUERY_SQL("DELETE FROM subcertificates WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error."\n";return false;}
    return true;
}
function subcertificates_new_server_js():bool{
    $certid = intval($_GET["new-server-certificate"]);
    $function = $_GET["function"];
    $tpl = new template_admin();
    $page = CurrentPageName();
   return $tpl->js_dialog2("{new_server_certificate}", "$page?new-server-certificate-popup=yes&certid=$certid&function=$function");
}
function subcertificates_new_client_js():bool{
    $certid = intval($_GET["new-client-certificate"]);
    $function = $_GET["function"];
    $tpl = new template_admin();
    $page = CurrentPageName();
    $NoPass="";
    if(isset($_GET["NoPass"])){
        $NoPass="&NoPass=yes";
    }

   return $tpl->js_dialog2("{new_client_certificate}", "$page?new-client-certificate-popup=yes&certid=$certid&function=$function$NoPass");
}
function subcertificates_js(){
    $certid = intval($_GET["show-server-certificate"]);
    $function = $_GET["function"];
    $tpl = new template_admin();
    $page = CurrentPageName();
    $tpl->js_dialog3("{certificate} $certid", "$page?subcertificates-tab=$certid&function=$function");
}
function subcertificates_new_server_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $certid=intval($_POST["NEW_SERVER_CERTIFICATE"]);
    $_POST["certid"]=$certid;
    $gpid=0;
    if(isset($_SESSION["HARMPID"])){$gpid=intval($_SESSION["HARMPID"]);}
    $_POST["NodeID"]=$gpid;
    $sock=new sockets();
    $data=$sock->REST_API_POST("/certificate/server/create",$_POST);
    $json=json_decode($data);
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    return admin_tracks_post("Create a new Server Certificate");

}
function subcertificates_new_client_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $certid=intval($_POST["NEW_CLIENT_CERTIFICATE"]);
    $_POST["certid"]=$certid;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CLIENT_CERTIFICATE_$certid",serialize($_POST));
}
function  subcertificates_top_buttons():bool{
    $users=new usersMenus();
    $certid  = intval($_GET["subcertificates-top-buttons"]);
    $function=$_GET["function"];
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $sslclient=0;
    $sslserver=0;
    $OnlyClient=false;
    $topbuttons=array();

    if(isset($_GET["OnlyClient"])){
        $OnlyClient=true;
    }
    $NoPass="";
    if(isset($_GET["NoPass"])){
        $NoPass="&NoPass=yes";
    }


    $parse_certificate=parse_certificate_artica($certid);

    if(isset($parse_certificate["purposes"])) {
        $purposes = $parse_certificate["purposes"];
        if(is_array($purposes)) {
            foreach ($purposes as $index => $sub) {
                if (trim($sub[2]) == null) {
                    continue;
                }
                if (trim(strtolower($sub[2])) == "sslclient") {
                    $sslclient = 1;
                }
                if (trim(strtolower($sub[2])) == "sslserver") {
                    $sslserver = 1;
                }
            }
        }
    }

    if($users->AsCertifsManager){
        if($OnlyClient){
            $sslserver=0;
        }
        if($sslserver==1) {

            $topbuttons[] = array("Loadjs('$page?new-server-certificate=$certid&function=$function')", ico_plus, "{new_server_certificate}");
        }



        if($sslclient==1) {
            $topbuttons[] = array("Loadjs('$page?new-client-certificate=$certid&function=$function$NoPass')", ico_plus, "{new_client_certificate}");
        }
    }

    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}
function subcertificates_new_client_popup(){
    $tpl=new template_admin();
    $certid=$_GET["certid"];
    $function=$_GET["function"];
    $page=CurrentPageName();
    $array_country_codes=array();
    $array_country_codes2=array();
    $NoPass=false;
    if(isset($_GET["NoPass"])){
        $NoPass=true;
    }

    $myhostname=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname");
    $db=file_get_contents(dirname(__FILE__) . '/ressources/databases/ISO-3166-Codes-Countries.txt');
    $tbl=explode("\n",$db);
    foreach ($tbl as $line){
        if(preg_match('#(.+?);\s+([A-Z]{1,2})#',$line,$regs)){
            $regs[2]=trim($regs[2]);
            $regs[1]=trim($regs[1]);
            $array_country_codes["{$regs[1]}_{$regs[2]}"]=$regs[1];
            $array_country_codes2[$regs[2]]="{$regs[1]}_{$regs[2]}";
        }
    }
    $tbl=array();
    $ENC[2048]=2048;
    $ENC[4096]=4096;

    $ligne=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CLIENT_CERTIFICATE_$certid"));
    if(!is_array($ligne)){$ligne=array();}
    $countOfLIne=count($ligne);
    VERBOSE("Count of lines: $countOfLIne",__LINE__);
    if($countOfLIne<5) {
        $q = new lib_sqlite(GetDatabase());
        if (!$q->FIELD_EXISTS("sslcertificates", "subjectAltName")) {
            $q->QUERY_SQL("ALTER TABLE sslcertificates ADD subjectAltName TEXT");
            $q->QUERY_SQL("ALTER TABLE sslcertificates ADD subjectAltName1 TEXT");
            $q->QUERY_SQL("ALTER TABLE sslcertificates ADD subjectAltName2 TEXT");
        }
        VERBOSE("Get CommonName,CountryName, localityName,OrganizationName,OrganizationalUnit From $certid",__LINE__);
        $ligne = $q->mysqli_fetch_array("SELECT * FROM sslcertificates WHERE ID=$certid");

        $field="crt";
        if($ligne["UsePrivKeyCrt"]==0){$field="SquidCert";}
        $crtdata=$ligne[$field];
        $openssl_x509_parse=openssl_x509_parse($crtdata);
        if(isset($openssl_x509_parse["subject"]["C"])){
            $ligne["CountryName"]=$openssl_x509_parse["subject"]["C"];
        }
        if(isset($openssl_x509_parse["subject"]["ST"])){
            $ligne["stateOrProvinceName"]=$openssl_x509_parse["subject"]["ST"];
        }
        if(isset($openssl_x509_parse["subject"]["L"])){
            $ligne["localityName"]=$openssl_x509_parse["subject"]["L"];
        }
        if(isset($openssl_x509_parse["subject"]["O"])){
            $ligne["OrganizationName"]=$openssl_x509_parse["subject"]["O"];
        }
        if(isset($openssl_x509_parse["subject"]["OU"])){
            $ligne["OrganizationalUnit"]=$openssl_x509_parse["subject"]["OU"];
        }
        if(isset($openssl_x509_parse["subject"]["emailAddress"])){
            $ligne["emailAddress"]=$openssl_x509_parse["subject"]["emailAddress"];
        }

        if (!$q->ok) {
            VERBOSE($q->mysql_error,__LINE__);
            echo $tpl->div_error($q->mysql_error);
        }
    }

    $t=time();
    if(!isset($ligne["emailAddress"])){$ligne["emailAddress"]=null;}
    if($ligne["subjectAltName"]==null){$ligne["subjectAltName"]=$myhostname;}
    if($ligne["subjectAltName1"]==null){$ligne["subjectAltName1"]=$_SERVER["SERVER_ADDR"];}
    if($ligne["subjectAltName2"]==null){$ligne["subjectAltName2"]=$_SERVER["SERVER_NAME"];}

    if(!is_numeric($ligne["CertificateMaxDays"])){$ligne["CertificateMaxDays"]=3650;}
    if(!intval($ligne["levelenc"])==0){$ligne["levelenc"]=4096;}

    $form[]=$tpl->field_hidden("NEW_CLIENT_CERTIFICATE",$certid);
    $form[]=$tpl->field_text("CommonName", "{username}", "User1",true,null);
    $form[]=$tpl->field_text("emailAddress", "{emailAddress}", $ligne["emailAddress"]);
    if(!$NoPass) {
        $form[] = $tpl->field_password("password", "{password} ({optional})");
    }

    $tpl->field_hidden("subjectAltName",$ligne["subjectAltName"]);
    $tpl->field_hidden("subjectAltName1",$ligne["subjectAltName1"]);
    $tpl->field_hidden("subjectAltName2",$ligne["subjectAltName2"]);

    if($ligne["CountryName"]<>null){
        $tpl->field_hidden("CountryName",$ligne["CountryName"]);
        $tbl[]="<tr>";
        $tbl[]="<td style='width:1%;text-align: right' nowrap>{countryName}</td>";
        $tbl[]="<td style='width:1%;text-align: left'><strong>{$array_country_codes[$ligne["CountryName"]]} ({$ligne["CountryName"]})</strong></td>";
        $tbl[]="</tr>";
    }else{
        if($ligne["CountryName"]==null){$ligne["CountryName"]="US";}
        $form[]=$tpl->field_array_hash($array_country_codes,"CountryName", "nonull:{countryName}", $ligne["CountryName"]);
    }
    if($ligne["stateOrProvinceName"]<>null){
        if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
        $tpl->field_hidden("stateOrProvinceName",$ligne["stateOrProvinceName"]);
        $tbl[]="<tr>";
        $tbl[]="<td style='width:1%;text-align: right' nowrap>{stateOrProvinceName}</td>";
        $tbl[]="<td style='width:1%;text-align: left'><strong>{$ligne["stateOrProvinceName"]}</strong></td>";
        $tbl[]="</tr>";
    }else{
        $form[]=$tpl->field_text("stateOrProvinceName", "{stateOrProvinceName}", $ligne["stateOrProvinceName"]);
    }
    if($ligne["localityName"]<>null){
        if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
        $tpl->field_hidden("localityName",$ligne["localityName"]);
        $tbl[]="<tr>";
        $tbl[]="<td style='width:1%;text-align: right' nowrap>{localityName}</td>";
        $tbl[]="<td style='width:1%;text-align: left'><strong>{$ligne["localityName"]}</strong></td>";
        $tbl[]="</tr>";
    }else{
        $form[]=$tpl->field_text("localityName", "{localityName}", $ligne["localityName"]);
    }
    if($ligne["OrganizationName"]<>null){
        if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
        $tpl->field_hidden("OrganizationName",$ligne["OrganizationName"]);
        $tbl[]="<tr>";
        $tbl[]="<td style='width:1%;text-align: right' nowrap>{organizationName}</td>";
        $tbl[]="<td style='width:1%;text-align: left'><strong>{$ligne["OrganizationName"]}</strong></td>";
        $tbl[]="</tr>";
    }else{
        $form[]=$tpl->field_text("OrganizationName", "{organizationName}", $ligne["OrganizationName"]);
    }
    if($ligne["OrganizationalUnit"]<>null){
        if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}
        $tpl->field_hidden("OrganizationalUnit",$ligne["OrganizationalUnit"]);
        $tbl[]="<tr>";
        $tbl[]="<td style='width:1%;text-align: right' nowrap>{organizationalUnitName}</td>";
        $tbl[]="<td style='width:1%;text-align: left'><strong>{$ligne["OrganizationalUnit"]}</strong></td>";
        $tbl[]="</tr>";
    }else{
        $form[]=$tpl->field_text("OrganizationalUnit", "{organizationalUnitName}", $ligne["OrganizationalUnit"]);
    }
    if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
    $form[]=$tpl->field_array_hash($ENC, "levelenc", "{level_encryption}", $ligne["levelenc"]);
    $form[]=$tpl->field_numeric("CertificateMaxDays","{CertificateMaxDays} ({days})",$ligne["CertificateMaxDays"]);
    if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);
        if ($gpid > 0) {
            $Addon="&HarmpID=$gpid";
        }
    }

    $js[]="dialogInstance2.close()";
    $js[]="LoadAjax('table-loader-sslcerts-service','fw.certificates-center.php?table=yes')";
    $js[]="$function()";
    $js[]="if(document.getElementById('flat-parameters-cluster-master')){";
    $js[]="LoadAjax('flat-parameters-cluster-master','fw.system.cluster.master.php?flat-parameters-cluster-master=yes');}";

    $jsrestart=$tpl->framework_buildjs(
        "/certificate/client/selfsigned/$certid",
        "selfsign.progress",
        "selfsign.log",
        "progress-$t",
        @implode(";",$js)
    );


    $html[]="<div id='progress-$t'></div>";
    $html[]=$tpl->form_outside("{new_client_certificate}", @implode("\n", $form),null,"{create}",$jsrestart,"AsCertifsManager");
    $html[]="<table style='margin-top: 10px;margin-bottom: 10px' class='table'>";
    $html[]=@implode("",$tbl);
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);




}
function subcertificates_new_server_popup(){
    $tpl=new template_admin();
    $certid=$_GET["certid"];
    $function=$_GET["function"];
    $page=CurrentPageName();
    $myhostname=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname");
    $db=file_get_contents(dirname(__FILE__) . '/ressources/databases/ISO-3166-Codes-Countries.txt');
    $tbl=explode("\n",$db);
    foreach ($tbl as $line){
        if(preg_match('#(.+?);\s+([A-Z]{1,2})#',$line,$regs)){
            $regs[2]=trim($regs[2]);
            $regs[1]=trim($regs[1]);
            $array_country_codes["{$regs[1]}_{$regs[2]}"]=$regs[1];
            $array_country_codes2[$regs[2]]="{$regs[1]}_{$regs[2]}";
        }
    }


    $ENC[1024]=1024;
    $ENC[2048]=2048;
    $ENC[4096]=4096;

    $ligne=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SERVER_CERTIFICATE_$certid"));
    if(!is_array($ligne)){$ligne=array();}

    if(count($ligne)<5) {
        $q = new lib_sqlite(GetDatabase());

        if (!$q->FIELD_EXISTS("sslcertificates", "subjectAltName")) {
            $q->QUERY_SQL("ALTER TABLE sslcertificates ADD subjectAltName TEXT");
            $q->QUERY_SQL("ALTER TABLE sslcertificates ADD subjectAltName1 TEXT");
            $q->QUERY_SQL("ALTER TABLE sslcertificates ADD subjectAltName2 TEXT");
        }

        $ligne = $q->mysqli_fetch_array("SELECT CommonName,CountryName,
    localityName,OrganizationName,OrganizationalUnit,stateOrProvinceName,subjectAltName,subjectAltName1,subjectAltName2,CertificateMaxDays,levelenc FROM sslcertificates WHERE ID=$certid");

        if (!$q->ok) {
            echo $tpl->div_error($q->mysql_error);
        }
    }

    $t=time();
    if($ligne["CountryName"]==null){$ligne["CountryName"]="US";}
    if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
    if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
    if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
    if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
    if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}

    if($ligne["subjectAltName"]==null){$ligne["subjectAltName"]=$myhostname;}
    if($ligne["subjectAltName1"]==null){$ligne["subjectAltName1"]=$_SERVER["SERVER_ADDR"];}
    if($ligne["subjectAltName2"]==null){$ligne["subjectAltName2"]=$_SERVER["SERVER_NAME"];}

    if(!is_numeric($ligne["CertificateMaxDays"])){$ligne["CertificateMaxDays"]=3650;}
    if(!intval($ligne["levelenc"])==0){$ligne["levelenc"]=4096;}

    $form[]=$tpl->field_hidden("NEW_SERVER_CERTIFICATE",$certid);
    $form[]=$tpl->field_text("CommonName", "{CommonName}", $myhostname,true,null);
    $form[]=$tpl->field_text("subjectAltName", "{subjectAltName}", $ligne["subjectAltName"],true,null);
    $form[]=$tpl->field_text("subjectAltName1", "{subjectAltName} (1)", $ligne["subjectAltName1"],true,null);
    $form[]=$tpl->field_text("subjectAltName2", "{subjectAltName} (2)", $ligne["subjectAltName2"],false,null);
    $form[]=$tpl->field_array_hash($array_country_codes,"CountryName", "nonull:{countryName}", $ligne["CountryName"]);
    $form[]=$tpl->field_text("stateOrProvinceName", "{stateOrProvinceName}", $ligne["stateOrProvinceName"]);
    $form[]=$tpl->field_text("localityName", "{localityName}", $ligne["localityName"]);
    $form[]=$tpl->field_text("OrganizationName", "{organizationName}", $ligne["OrganizationName"]);
    $form[]=$tpl->field_text("OrganizationalUnit", "{organizationalUnitName}", $ligne["OrganizationalUnit"]);
    $form[]=$tpl->field_text("emailAddress", "{emailAddress}", $ligne["emailAddress"]);
    $form[]=$tpl->field_numeric("CertificateMaxDays","{CertificateMaxDays} ({days})",$ligne["CertificateMaxDays"]);
    if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);
        if ($gpid > 0) {
            $Addon="&HarmpID=$gpid";
        }
    }

   $after="dialogInstance2.close();LoadAjax('table-loader-sslcerts-service','fw.certificates-center.php?table=yes');$function()";


    $html[]="<div id='progress-$t'></div>";
    $html[]=$tpl->form_outside("{new_server_certificate}", @implode("\n", $form),null,"{create}",$after,"AsCertifsManager");
    echo $tpl->_ENGINE_parse_body($html);

}
function subcertificates_table():bool{
    $OnlyClient="";$NoPass="";
    if(isset($_GET["OnlyClient"])){
        $OnlyClient="&OnlyClient=yes";
    }
    if(isset($_GET["NoPass"])){
        $NoPass="&NoPass=yes";
    }

    $certid=intval($_GET["subcertificates-table"]);
    $function=$_GET["function"];
    $q=new lib_sqlite(GetDatabase());
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $gpid=0;
    if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);
        if ($gpid > 0) {
            $Addon="&HarmpID=$gpid";
        }
    }




    $btns="LoadAjaxSilent('subcertificate-buttons-$certid','$page?subcertificates-top-buttons=$certid&function=$function$OnlyClient$NoPass')";



    $html[]="</div>";
    $html[]="<div class='ibox-content' style='padding:0px;margin-top:5px;'>";
    $html[]="<table class='table table-over'>";
    $html[]="<tr style='background-color: #CCCCCC'>";
    $html[]="<th>{type}</th>";
    $html[]="<th>{expire}</th>";
    $html[]="<th>{CommonName}</th>";
    $html[]="<th>OU</th>";
    $html[]="<th>{emailAddress}</th>";
    $html[]="<th>DOWN</th>";
    $html[]="<th>DEL</th>";
    $html[]="</tr>";

    $results=$q->QUERY_SQL("SELECT * FROM subcertificates WHERE certid='$certid' ORDER BY Certype,commonName ASC");

    foreach ($results as $index=>$ligne) {
        $md=md5(serialize($ligne));

        $Certype=$ligne["Certype"];
        $ID=$ligne["ID"];
        $commonName=$ligne["commonName"];
        $organizationName=$ligne["organizationName"];
        $emailAddress=$ligne["emailAddress"];
        $type="<span class='label label-info'>{client_certificate}</span>";
        $DateTo=$ligne["DateTo"];
        $err="";
        $commonName=$tpl->td_href($commonName,null,"Loadjs('$page?show-server-certificate=$ID&md=$md')");
        if($Certype==1){
            $type="<span class='label label-primary'>{server_certificate}</span>";
            $data=$sock->REST_API("/certificate/server/$ID/$gpid");
            $json=json_decode($data);
            if(!$json->Status){
                $type="<span class='label label-danger'>{server_certificate}</span>";
                $err="<br><small class=text-danger>$json->Error</small>";
            }
        }
        $del=$tpl->icon_delete("Loadjs('$page?del-server-certificate=$ID&md=$md')","AsCertifsManager");
        $download=$tpl->icon_nothing();
        $DateToT=$tpl->time_to_date($DateTo);
        if( strlen($ligne["pks12"])>50) {
            $download = $tpl->icon_download("document.location.href='$page?subcertificates-pks12=$ID'", "AsCertifsManager");
        }
        $html[]="<tr id='$md'>";
        $html[]="<td style='width:1%' nowrap>$type</td>";
        $html[]="<td style='width:1%' nowrap>$DateToT</td>";
        $html[]="<td>$commonName$err</td>";
        $html[]="<td>$organizationName</td>";
        $html[]="<td>$emailAddress</td>";
        $html[]="<td style='width:1%' nowrap>$download</td>";
        $html[]="<td style='width:1%' nowrap>$del</td>";
        $html[]="</tr>";
    }

    $html[]="</table>";
    $html[]="</div>";
    $html[]="<script>$btns</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function certificate_new_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $add=null;
    $function=$_GET["function"];
    if(isset($_GET["OnlyCSR"])){$add="&OnlyCSR=yes";}
    return $tpl->js_dialog1("{new_certificate}", "$page?new-certificate-popup=yes&function=$function$add");
}



function letsencrypt_dns_key_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["letsencrypt-dnskey-js"]);

    $q=new lib_sqlite(GetDatabase());
    $sql="SELECT * FROM sslcertificates WHERE ID='$ID'";
    $ligne=$q->mysqli_fetch_array($sql);
    $CommonName=$ligne["CommonName"];
    $tpl->js_dialog1("$CommonName: {LETSENCRYPT_CERTIFICATE}", "$page?letsencrypt-dnskey-popup=$ID");
}

function letsencrypt_dns_key_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["letsencrypt-dnskey-popup"]);

    $q=new lib_sqlite(GetDatabase());
    $sql="SELECT * FROM sslcertificates WHERE ID='$ID'";
    $ligne=$q->mysqli_fetch_array($sql);
    $CommonName=$ligne["CommonName"];
    $letsencrypt_dns_key=$ligne["letsencrypt_dns_key"];
    $letsencrypt_dns_key_text=$tpl->_ENGINE_parse_body("{letsencrypt_dns_key}");
    $letsencrypt_dns_key_text=str_replace("%d","_acme-challenge.$CommonName",$letsencrypt_dns_key_text);
    $letsencrypt_dns_key_text=str_replace("%s","$letsencrypt_dns_key",$letsencrypt_dns_key_text);

    if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);
        if ($gpid > 0) {
            $Addon="&HarmpID=$gpid";
        }
    }


    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/letsencrypt.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/letsencrypt.log";
    $ARRAY["CMD"]="openssl.php?letsencrypt-dns=$ID$Addon";
    $ARRAY["TITLE"]="Let’s Encrypt {$ligne["CommonName"]}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-sslcerts-service','$page?table=yes');dialogInstance1.close();";

    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=letsencrypt-$ID')";

    $html[]="<div id='letsencrypt-$ID'></div>";

    $form[]=$tpl->field_info("CommonName", "{CommonName}", $ligne["CommonName"],true,null);
    $form[]=$tpl->field_info("emailAddress", "{emailAddress}", $ligne["emailAddress"],true);
    $form[]=$tpl->field_info("API", "{API_KEY}", $ligne["letsencrypt_dns_key"],true);
    $form[]=$tpl->field_hidden("UseLetsEncrypt", 1);
    $form[]=$tpl->field_hidden("ID", $ID);
    $html[]=$tpl->form_outside("{$ligne["CommonName"]} - Let’s Encrypt", $form,$letsencrypt_dns_key_text,"{create_certificate}",$jsrestart,"AsCertifsManager");
    echo $tpl->_ENGINE_parse_body($html);

}

function letsencrypt_dns_js(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["letsencrypt-dns-js"]);
    $q=new lib_sqlite(GetDatabase());
    $sql="SELECT * FROM sslcertificates WHERE ID='$ID'";
    $ligne=$q->mysqli_fetch_array($sql);
    if(!$q->FIELD_EXISTS("sslcertificates","letsencrypt_dns_key")){
        $q->QUERY_SQL("ALTER TABLE sslcertificates ADD letsencrypt_dns_key TEXT");
    }
    if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);
        if ($gpid > 0) {
            $Addon="&HarmpID=$gpid";
        }
    }

    $letsencrypt_dns_key=$ligne["letsencrypt_dns_key"];
    if($letsencrypt_dns_key==null){
        $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/letsencrypt.progress";
        $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/letsencrypt.log";
        $ARRAY["CMD"]="openssl.php?letsencrypt-dns-key=$ID$Addon";
        $ARRAY["TITLE"]="Let’s Encrypt {$ligne["CommonName"]} DNS...";
        $ARRAY["AFTER"]="Loadjs('$page?letsencrypt-dnskey-js=$ID')";
        $prgress=base64_encode(serialize($ARRAY));
        $jsrestart="\nLoadjs('fw.progress.php?content=$prgress&mainid=letsencrypt-$ID');\n";
        echo $jsrestart;
        return;
    }

    echo "Loadjs('$page?letsencrypt-dnskey-js=$ID');";

}






function delete_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $Certificate=$_GET["delete-js"];
    $js="$('#{$_GET["id"]}').remove();";
    $tpl->js_confirm_delete($Certificate, "delete", $Certificate,$js);

}
function delete(){
    $q=new lib_sqlite(GetDatabase());
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM sslcertificates WHERE CommonName='{$_POST["delete"]}'");
    $certid=$ligne["ID"];
    $q->QUERY_SQL("DELETE FROM subcertificates WHERE certid=$certid");
    $q->QUERY_SQL("DELETE FROM sslcertificates WHERE CommonName='{$_POST["delete"]}'");
}

function display_csr_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_dialog1("{CSR}", "$page?display-csr-popup=yes&function=$function");
}

function display_csr_popup():bool{
    $tpl=new template_admin();
    $form[]=$tpl->field_textarea("csr", null, base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeneratedCSR")),"664px");
   echo $tpl->form_outside("{CSR}", @implode("\n", $form),"{csr_ssl_explain}",null,null,null,null,false,true);
   return true;
}


function certificate_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function="";
    $CertificateGet=$_GET["certificate-js"];
    $certificate=urlencode($CertificateGet);
    if(isset($_GET["function"])){$function=$_GET["function"];}
    return $tpl->js_dialog1("{certificate} $CertificateGet", "$page?certificate-popup=$certificate&function=$function&certificate=$certificate");
}
function subcertificates_tabs(){
    $ID=intval($_GET["subcertificates-tab"]);
    $tpl=new template_admin();
    $page=CurrentPageName();

    $q = new lib_sqlite(GetDatabase());
    $ligne=$q->mysqli_fetch_array("SELECT commonName,organizationName,emailAddress FROM subcertificates WHERE ID=$ID");
    if(!$q->ok){
        $tpl->js_mysql_alert($q->mysql_error);
        return;
    }
    $commonName=$ligne["commonName"];


    $array[$commonName]="$page?subcertificates-settings=$ID";
    $array["{certificate}"]="$page?subcertificates-crt=$ID";
    $array["{privkey}"]="$page?subcertificates-privkey=$ID";
    $array["Info."]="$page?subcertificates-info=$ID";
    echo $tpl->tabs_default($array);

}

function subcertificates_info(){
    $ID=$_GET["subcertificates-info"];
    $q=new lib_sqlite(GetDatabase());

    $ligne=$q->mysqli_fetch_array("SELECT crt FROM subcertificates WHERE ID=$ID");

    $crt=base64_decode($ligne["crt"]);


    $passin=null;
    if($ligne["CertPassword"]<>null){
        $passin=" -passin pass:{$ligne["CertPassword"]}";
    }

    $crt=str_replace("\\n", "\n", $crt);
    @mkdir("/usr/share/artica-postfix/ressources/conf/upload",0755,true);
    $filepath=dirname(__FILE__)."/ressources/conf/upload/Cert.pem";
    @file_put_contents($filepath,$crt);
    exec("/usr/bin/openssl x509 -text$passin -in $filepath 2>&1",$results);

    foreach ($results as $num=>$ligne){
        $ligne=trim($ligne);
        $tt[]=$ligne;
    }

    echo "<textarea
		style='margin-top:5px;font-family:Courier New,serif;
		font-weight:bold;border:5px solid #8E8E8E;
		overflow:auto;font-size:12px !important;width:99%;height:390px'>".@implode("\n", $tt)."</textarea>";

}
function certificate_tab(){
    $function=$_GET["function"];
    $q=new lib_sqlite(GetDatabase());
    $sql="SELECT `crt`,`csr`,`Generated`,`SquidCert`,`UsePrivKeyCrt`,`UseGodaddy`,`UseLetsEncrypt`,`AsRoot`,ID,srca  FROM sslcertificates WHERE CommonName='{$_GET["certificate-popup"]}'";
    $ligne=$q->mysqli_fetch_array($sql);

    $UseLetsEncrypt=intval($ligne["UseLetsEncrypt"]);
    $UsePrivKeyCrt=intval($ligne["UsePrivKeyCrt"]);
    $AsRoot=intval($ligne["AsRoot"]);
    $ID=intval($ligne["ID"]);

    $page=CurrentPageName();
    $tpl=new template_admin();
    $sslclient=0;
    $certificate=urlencode($_GET["certificate-popup"]);
    $array["{settings}"]="$page?certificate-settings=$certificate&function=$function";
    $array["{certificates}"]="$page?certificate-flat=$ID&function=$function";
    $parse_certificate=parse_certificate_artica($_GET["certificate-popup"]);
    $purposes=$parse_certificate["purposes"];
    foreach ($purposes as $index=>$sub){
        if(trim($sub[2])==null){continue;}
        if(trim(strtolower($sub[2]))=="sslclient"){
            $sslclient=1;
        }
    }

    if($AsRoot==1 or $sslclient==1){
        $array["{secondary_certificates}"]="$page?subcertificates=$ID";

    }

    $array["{info}"]="$page?certificate-info=$certificate&function=$function";

    echo $tpl->tabs_default($array);
}

function certificate_flat():bool{
    $page=CurrentPageName();
    $function=$_GET["function"];
    $ID=intval($_GET["certificate-flat"]);
    echo "<div id='certificate-flat-$ID'></div><script>LoadAjax('certificate-flat-$ID','$page?certificate-flat2=$ID&function=$function')</script>";
    return true;
}
function certificate_flat2():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["certificate-flat2"]);
    $function=$_GET["function"];
    $gpid=intval($_SESSION["HARMPID"]);
    $sock=new sockets();
    $q=new lib_sqlite(GetDatabase());
    $refreshEncoded=base64_encode("LoadAjax('certificate-flat-$ID','$page?certificate-flat2=$ID&function=$function')");

    if (!$q->FIELD_EXISTS("sslcertificates", "ServerCert")) {
        $q->QUERY_SQL("ALTER TABLE sslcertificates ADD ServerCert INTEGER NOT NULL DEFAULT 0");
    }


    $sql="SELECT `crt`,`csr`,`Generated`,bundle,`SquidCert`,`UsePrivKeyCrt`,`UseGodaddy`,`UseLetsEncrypt`,`AsRoot`,Squidkey,privkey,ID,srca,ServerCert,CABundleProvider  FROM sslcertificates WHERE ID='$ID'";
    $ligne=$q->mysqli_fetch_array($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error."<br>Please restart the REST API service..");
    }

    $TextType="{official_certificate}";
    $UsePrivKeyCrt=intval($ligne["UsePrivKeyCrt"]);
    $ServerCert=intval($ligne["ServerCert"]);
    $UseLetsEncrypt=intval($ligne["UseLetsEncrypt"]);
    $CABundleProvider=base64_decode($ligne["CABundleProvider"]);
    $TypeAddon="";
    if($UseLetsEncrypt==1){
        $TextType=array();
        $js="Loadjs('fw.certificates-center.letsencrypt.php?ID=$ID&function=$function&function2=$refreshEncoded')";
        $TextType["BUTTON"]["VALUE"] = "{LETSENCRYPT_CERTIFICATE}";
        $TextType["BUTTON"]["LABEL"] = "{renew}";
        $TextType["BUTTON"]["JS"] = $js;
        $ligne["csr"]="";
    }

    if($UsePrivKeyCrt==1){
       if($ServerCert==1){
           $TextType="$TextType&nbsp;/&nbsp;{server_certificate}";
       }


        $tpl->table_form_field_text("{type}",$TextType,ico_certificate);
    }else{
        if($ServerCert==1){
            $TypeAddon="&nbsp;/&nbsp;{server_certificate}";
        }
        $tpl->table_form_field_text("{type}","{SelfSignedCert}$TypeAddon",ico_certificate);
    }

    $csr=$ligne["csr"];

    if($UsePrivKeyCrt==1) {
        if (strlen($csr) > 50) {

            $content = $sock->REST_API("/certificate/csrcheck/$ID/$gpid");
            $csr_text = "[OK] " . strlen($csr) . " bytes";
            $csr_error = "";
            $Error = false;
            $json = json_decode($content);
            if (json_last_error() > JSON_ERROR_NONE) {
                $csr_error = "Decoding: " . strlen($content) . " bytes " . json_last_error_msg();

            }
            if (strlen($csr_error) == 0) {
                if (!$json->Status) {
                    $csr_error = $json->Error;
                }
            }
            if (strlen($csr_error) > 1) {
                $csr_text = $csr_error;
                $Error = true;
            }
            $tpl->table_form_field_js("Loadjs('$page?csr-js=$ID')", "AsCertifsManager");
            $tpl->table_form_field_text("{CERTIFICATE_REQUEST}", $csr_text, ico_certificate, $Error);
        } else {
            if($UseLetsEncrypt==0) {
                $tpl->table_form_field_text("{CERTIFICATE_REQUEST}", "{none}", ico_disabled);
            }
        }
    }

    $crt=$ligne["SquidCert"];
    $privkey=$ligne["Squidkey"];
    $bundle=$ligne["bundle"];
    $srca=$ligne["srca"];

    if($UsePrivKeyCrt==1){
        $crt=$ligne["crt"];
        $privkey=$ligne["privkey"];

    }

    VERBOSE("CRT: ".strlen($crt) ." BYTES",__LINE__);
    VERBOSE("PRIVKEY: ".strlen($privkey) ." BYTES",__LINE__);

    if( (strlen($crt)>50) AND (strlen($privkey)>50) ){

        $content=$sock->REST_API("/certificate/crtcheck/$ID/$gpid");
        $csr_text="[OK] ".strlen($crt)." bytes";
        $key_text="[OK] ".strlen($privkey)." bytes";
        $csr_error="";
        $Error=false;
        $json=json_decode($content);
        if (json_last_error()> JSON_ERROR_NONE) {
            $csr_error="Decoding: ".strlen($content)." bytes ".json_last_error_msg();
            $key_text=$csr_error;
        }
        if(strlen($csr_error)==0) {
            if (!$json->Status) {
                $csr_error = $json->Error;
                $key_text=$csr_error;
            }
        }
        if(strlen($csr_error)>1){
            $csr_text=$csr_error;
            $key_text=$csr_error;
            $Error=true;
        }
        $tpl->table_form_field_js("Loadjs('$page?crt-js=$ID&function=$function')","AsCertifsManager");
        $tpl->table_form_field_text("{certificate}",$csr_text,ico_certificate,$Error);
        $tpl->table_form_field_js("Loadjs('$page?privkey-js=$ID&function=$function')","AsCertifsManager");
        $tpl->table_form_field_text("{privkey}",$key_text,ico_certificate,$Error);

    }else{
        if (strlen($crt)<50) {
            $tpl->table_form_field_js("Loadjs('$page?crt-js=$ID&function=$function')","AsCertifsManager");
            $tpl->table_form_field_text("{certificate}", "{missing}", ico_certificate, true);
        }else{
            $StrlenOfCrt=strlen($crt);
            $tpl->table_form_field_js("Loadjs('$page?crt-js=$ID&function=$function')","AsCertifsManager");
            $tpl->table_form_field_text("{certificate}", "[OK] $StrlenOfCrt bytes", ico_certificate);
        }

        if (strlen($privkey)<50) {
            if($ServerCert==0) {
                $tpl->table_form_field_js("Loadjs('$page?privkey-js=$ID&function=$function')", "AsCertifsManager");
                $tpl->table_form_field_text("{privkey}", "{missing}", ico_certificate, true);
            }
        }

    }
    if($UsePrivKeyCrt==1) {
        if (strlen($srca) > 50) {
            $content = $sock->REST_API("/certificate/cacheck/$ID/$gpid");
            $srca_text = "[OK] " . strlen($srca) . " bytes";
            $csr_error = "";
            $Error = false;
            $json = json_decode($content);
            if (json_last_error() > JSON_ERROR_NONE) {
                $csr_error = "Decoding: " . strlen($content) . " bytes " . json_last_error_msg();

            }
            if (strlen($csr_error) == 0) {
                if (!$json->Status) {
                    $csr_error = $json->Error;
                }
            }
            if (strlen($csr_error) > 1) {
                $srca_text = $csr_error;
                $Error = true;
            }
            $tpl->table_form_field_js("Loadjs('$page?srca-js=$ID&function=$function')", "AsCertifsManager");
            $tpl->table_form_field_text("{ROOT_CERT}", $srca_text, ico_certificate, $Error);

        } else {
            if($UseLetsEncrypt==0) {
                $tpl->table_form_field_js("Loadjs('$page?srca-js=$ID&function=$function')", "AsCertifsManager");
                $tpl->table_form_field_text("{ROOT_CERT}", "{none}", ico_certificate, false);
            }
        }
        if (strlen($bundle) > 50) {
            $csr_text = "[OK] " . strlen($bundle) . " bytes";
            $tpl->table_form_field_js("Loadjs('$page?bundle-js=$ID&function=$function')", "AsCertifsManager");
            $tpl->table_form_field_text("{certificate_chain}", $csr_text, ico_certificate);
        } else {
            $tpl->table_form_field_js("Loadjs('$page?bundle-js=$ID&function=$function')", "AsCertifsManager");
            $tpl->table_form_field_text("{certificate_chain}", "{none}", ico_certificate);
        }




    }
    if($UsePrivKeyCrt==1) {
        $tpl->table_form_field_js("Loadjs('$page?CABundleProvider-js=$ID&function=$function')", "AsCertifsManager");
        if (strlen($CABundleProvider) > 50) {
            $csr_text = "[OK] " . strlen($CABundleProvider) . " bytes";
            $tpl->table_form_field_text("{CABundleProvider}", $csr_text, ico_certificate);
        }else{
            $tpl->table_form_field_text("{CABundleProvider}", "{none}", ico_certificate);
        }
    }


    echo $tpl->_ENGINE_parse_body($tpl->table_form_compile());
    return true;
}

function certificate_crt(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite(GetDatabase());
    $function=$_GET["function"];
    $tt=time();
    $return=null;
    $CommonName=$_GET["certificate-crt"];
    $ID=intval($_GET["ID"]);
    $CommonNameURL=urlencode($CommonName);
    $button_save="{apply}";
    $sql="SELECT `crt`,`SquidCert`,`UsePrivKeyCrt`,`UseGodaddy`  FROM sslcertificates WHERE CommonName='$CommonName'";
    if($ID>0){
        $sql="SELECT `crt`,`SquidCert`,`UsePrivKeyCrt`,`UseGodaddy`  FROM sslcertificates WHERE ID=$ID";
    }


    $ligne=$q->mysqli_fetch_array($sql);

    $field="crt";
    if($ligne["UsePrivKeyCrt"]==0){
        $field="SquidCert";
        $button_upload=null;
        $button_save=null;

    }
    $ligne[$field]=str_replace("\\n", "\n", $ligne[$field]);
    $form[]=$tpl->field_hidden("SAVE_CONTENT", $CommonName);
    $form[]=$tpl->field_hidden("ID", $ID);
    $form[]=$tpl->field_hidden("field", $field);
    $form[]=$tpl->field_textarea("content", "TextareaCert:{value}", $ligne[$field],"100%");
    echo
    "<div id='$tt-verify' style='margin-top:20px'></div>";
    if(strlen($function)>2){$jsafter="$function();";}
    $jsafter="$jsafter;LoadAjax('certificate-flat-$ID','$page?certificate-flat2=$ID&function=$function')";
    echo $tpl->form_outside("", @implode("\n", $form),"{public_key_ssl_explain}",$button_save,$jsafter,"AsCertifsManager");
    if( $ligne[$field]==null){$return="return;";}

    echo "<script>
	function verify$tt(){
	{$return}LoadAjax('$tt-verify','$page?certificate-crt-verify=$CommonNameURL',true);
	}
	setTimeout('verify$tt()',1000);
	</script>";


}

function certificate_crt_verify(){


    echo "<script>if ( $('#spinner').children('.ibox-content').hasClass('sk-loading') ){
		$('#spinner').children('.ibox-content').toggleClass('sk-loading');
	}</script>";


    $CommonName=$_GET["certificate-crt-verify"];
    $q=new lib_sqlite(GetDatabase());
    $sql="SELECT `crt`,`SquidCert`,`UsePrivKeyCrt`,`UseGodaddy`  FROM sslcertificates WHERE CommonName='$CommonName'";
    $ligne=$q->mysqli_fetch_array($sql);

    $field="crt";
    if($ligne["UsePrivKeyCrt"]==0){$field="SquidCert";}
    if($ligne["UseGodaddy"]==1){$field="crt";}

    @mkdir("/usr/share/artica-postfix/ressources/conf/upload",0755,true);
    $filepath=dirname(__FILE__)."/ressources/conf/upload/Cert.pem";


    $ligne[$field]=str_replace("\\n", "\n",  $ligne[$field]);

    $fulltext=$ligne[$field];

    if(preg_match_all("#-----BEGIN CERTIFICATE-----(.+?)-----END CERTIFICATE-----#s",$fulltext,$re)){
        if(count($re[1])>1){
            VERBOSE("This is a chain certificate",__LINE__);
            $fulltext=$re[0][0];

        }

    }


    @file_put_contents($filepath, $fulltext);

    if($GLOBALS["VERBOSE"]){VERBOSE("/usr/bin/openssl verify -verbose $filepath 2>&1");}

    exec("/usr/bin/openssl x509 -in $filepath -text -noout 2>&1",$results);

    $class="alert alert-success";

    foreach ($results as $num=>$ligne){

        if($GLOBALS["VERBOSE"]){VERBOSE("Line $num [$ligne]");}

        if(preg_match("#[0-9]+:error:[0-9A-Z]+:PEM routines:#",$ligne)){$class="alert alert-success";}
        if(preg_match("#unable to load#",$ligne)){$class="alert alert-danger";}
        if(preg_match("#unable to get local issuer certificate#",$ligne)){continue;}
        if(preg_match("#Expecting:#",$ligne)){$class="alert alert-danger";}

        if(!preg_match("#(Subject|Not Before|Not After\s+|Issuer|RSA Public-Key):#",$ligne)){continue;}

        $ligne=str_replace($filepath, "Info", $ligne);
        $ligne=htmlentities($ligne);
        $f[]="$ligne";

    }

    echo "<div class='$class' style='margin-top:10px'>".@implode("<br>", $f)."</div>";

}

function certificate_srca():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $q=new lib_sqlite(GetDatabase());
    $tt=time();
    $return=null;
    $CommonName=$_GET["certificate-srca"];
    $CommonNameURL=urlencode($CommonName);
    $function=$_GET["function"];
    $button_save="{apply}";
    $sql="SELECT `srca` FROM sslcertificates WHERE CommonName='$CommonName'";
    $ID=intval($_GET["ID"]);

    if($ID>0){
        $sql="SELECT `srca`  FROM sslcertificates WHERE ID='$ID'";
    }

    $ligne=$q->mysqli_fetch_array($sql);
    $field="srca";
    if(!$q->ok){echo $q->mysql_error_html(true);}
    if($ligne["UsePrivKeyCrt"]==0){$button_save=null;}

    $Expose_content=$ligne[$field];
    $Expose_content=str_replace("\\n", "\n", $Expose_content);

    $form[]=$tpl->field_hidden("SAVE_CONTENT", $CommonName);
    $form[]=$tpl->field_hidden("ID", $ID);
    $form[]=$tpl->field_hidden("field", "srca");
    $form[]=$tpl->field_textarea("content", "TextareaCert:{value}", $Expose_content,"100%");
    echo "<div id='$tt-verify' style='margin-top:20px'></div>";


    if($ligne[$field]==null){$return="return;";}
    if(strlen($function)>2){$jsafter="$function();";}
    $jsafter="$jsafter;LoadAjax('certificate-flat-$ID','$page?certificate-flat2=$ID&function=$function')";
    echo $tpl->form_outside($CommonName, @implode("\n", $form),"{ROOT_CERT}",$button_save,$jsafter,"AsCertifsManager");


    echo  "<script>
	function verify$tt(){
	$return
	LoadAjax('$tt-verify','$page?certificate-srca-verify=$CommonNameURL',true);
	}
	setTimeout('verify$tt()',1000);
	</script>";
    return true;

}

function SAVE_CONTENT(){
    $q=new lib_sqlite(GetDatabase());
    $CommonName=$_POST["SAVE_CONTENT"];
    $ID=intval($_POST["ID"]);
    $field=$_POST["field"];
    $content=url_decode_special_tool($_POST["content"]);
    $content=$q->sqlite_escape_string2($content);
    $sql="UPDATE sslcertificates SET `$field`='$content' WHERE CommonName='$CommonName'";
    if($ID>0){
        $sql="UPDATE sslcertificates SET `$field`='$content' WHERE ID='$ID'";
    }

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error_html(true);return;}
    certificate_extract_crt($CommonName);
}

function certificate_privkey():bool{
    $page=CurrentPageName();
    $CommonName=$_GET["certificate-privkey"];
    $q=new lib_sqlite(GetDatabase());
    $ID=intval($_GET["ID"]);
    if($ID==0) {
        $sql = "SELECT ID FROM sslcertificates WHERE CommonName='$CommonName'";
        $ligne = $q->mysqli_fetch_array($sql);
        $ID = $ligne["ID"];
    }
    echo "<div id='DIVKEY{$ID}'></div><script>LoadAjax('DIVKEY{$ID}','$page?certificate-privkey2=$ID');</script>";
    return true;
}

function certificate_privkey2():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $q=new lib_sqlite(GetDatabase());
    $tt=time();
    $return=null;
    $ID=$_GET["certificate-privkey2"];
    $button_save="{apply}";
    $sql="SELECT ID,`CommonName`,`privkey`,`Squidkey`,`UsePrivKeyCrt`,`UseGodaddy`  FROM sslcertificates WHERE ID='$ID'";
    VERBOSE($sql,__LINE__);
    $ligne=$q->mysqli_fetch_array($sql);
    $CommonName=$ligne["CommonName"];
    $ID=$ligne["ID"];
    $field="privkey";
    if(!$q->ok){echo $q->mysql_error_html(true);}
    if($ligne["UsePrivKeyCrt"]==0){
        $button_save=null;
        $field="Squidkey";
        if(strlen($ligne[$field])<10){
            if(strlen($ligne["privkey"])>10){$field="privkey";}
        }
    }
    VERBOSE("$field == ". strlen($ligne[$field]). " Bytes",__LINE__);
    $ligne[$field]=str_replace("\\n", "\n", $ligne[$field]);
    $CommonNameURL=urlencode($CommonName);
    $form[]=$tpl->field_hidden("ID", $ID);
    $form[]=$tpl->field_hidden("SAVE_CONTENT", $CommonName);
    $form[]=$tpl->field_hidden("field", $field);
    $form[]=$tpl->field_textarea("content", "TextareaCert:{value}", $ligne[$field],"100%");
    echo
    "<div id='$tt-verify' style='margin-top:20px'></div>";

    if(strlen($function)>2){$jsafter="$function();";}
    $jsafter="$jsafter;LoadAjax('certificate-flat-$ID','$page?certificate-flat2=$ID&function=$function')";
    $tpl->form_add_button("{upload}","Loadjs('fw.certificates-center.import.key.php?ID=$ID')");
    echo $tpl->form_outside($CommonName, @implode("\n", $form),"{privkey_ssl_explain}",$button_save,$jsafter,"AsCertifsManager");
    if($ligne[$field]==null){$return="return;";}

    
return true;
}
function certificate_provider_bundle_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite(GetDatabase());
    $tt=time();
    $return="";
    $CommonName=$_GET["CABundleProvider"];
    $function=$_GET["function"];
    $button_save="{apply}";
    $sql="SELECT `CABundleProvider`  FROM sslcertificates WHERE CommonName='$CommonName'";
    $ID=intval($_GET["ID"]);
    if($ID>0){
        $sql="SELECT `CABundleProvider` FROM sslcertificates WHERE ID='$ID'";
    }
    $ligne=$q->mysqli_fetch_array($sql);
    if(!$q->ok){echo $q->mysql_error_html(true);}

    $CABundleProvider=str_replace("\\n", "\n", base64_decode($ligne["CABundleProvider"]));
    $form[]=$tpl->field_hidden("SAVE_CABUNDLE", $CommonName);
    $form[]=$tpl->field_hidden("ID", $ID);
    $form[]=$tpl->field_hidden("field", "CABundleProvider");
    $form[]=$tpl->field_textarea("content", "TextareaCert:{value}", $CABundleProvider,"100%");

    if(strlen($function)>2){$jsafter="$function();";}
    $jsafter="$jsafter;LoadAjax('certificate-flat-$ID','$page?certificate-flat2=$ID&function=$function');dialogInstance5.close();";
    echo $tpl->form_outside("", @implode("\n", $form),"",$button_save,$jsafter,"AsCertifsManager");

    echo "<script>
	</script>";
}
function certificate_provider_bundle_save():bool{
    $q=new lib_sqlite(GetDatabase());
    $CommonName=$_POST["SAVE_CABUNDLE"];
    $ID=intval($_POST["ID"]);
    $content=url_decode_special_tool($_POST["content"]);
    $content=base64_encode($content);
    $sql="UPDATE sslcertificates SET `CABundleProvider`='$content' WHERE CommonName='$CommonName'";
    if($ID>0){
        $sql="UPDATE sslcertificates SET `CABundleProvider`='$content' WHERE ID='$ID'";
    }
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $q->mysql_error_html(true);
        return false;
    }
    return admin_tracks("Save Certificate CA Bundle Provider for $CommonName/$ID");
}
function certificate_bundle():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite(GetDatabase());
    $tt=time();
    $return="";
    $CommonName=$_GET["certificate-bundle"];
    $CommonNameURL=urlencode($CommonName);
    $function=$_GET["function"];
    $button_save="{apply}";
    $sql="SELECT `bundle`  FROM sslcertificates WHERE CommonName='$CommonName'";
    $ID=intval($_GET["ID"]);
    if($ID>0){
        $sql="SELECT `bundle` FROM sslcertificates WHERE ID='$ID'";
    }
    $ligne=$q->mysqli_fetch_array($sql);
    if(!$q->ok){echo $q->mysql_error_html(true);}

    $ligne["bundle"]=str_replace("\\n", "\n", $ligne["bundle"]);
    $form[]=$tpl->field_hidden("SAVE_CONTENT", $CommonName);
    $form[]=$tpl->field_hidden("ID", $ID);
    $form[]=$tpl->field_hidden("field", "bundle");
    $form[]=$tpl->field_textarea("content", "TextareaCert:{value}", $ligne["bundle"],"100%");
    echo
    "<div id='$tt-verify' style='margin-top:20px'></div>";

    if(strlen($function)>2){$jsafter="$function();";}
    $jsafter="$jsafter;LoadAjax('certificate-flat-$ID','$page?certificate-flat2=$ID&function=$function')";
    echo $tpl->form_outside($CommonName, @implode("\n", $form),"{certificate_chain_explain}",$button_save,$jsafter,"AsCertifsManager");
    if(strlen($ligne["bundle"])<50){$return="return;";}

    echo "<script>
	function verify$tt(){
	$return
	LoadAjax('$tt-verify','$page?certificate-bundle-verify=$CommonNameURL',true);
	}
	setTimeout('verify$tt()',1000);
	</script>";
    return true;

}
function certificate_bundle_verify(){


    echo "<script>if ( $('#spinner').children('.ibox-content').hasClass('sk-loading') ){
		$('#spinner').children('.ibox-content').toggleClass('sk-loading');
	}</script>";


    $CommonName=$_GET["certificate-bundle-verify"];
    $q=new lib_sqlite(GetDatabase());
    if(!$q->FIELD_EXISTS("sslcertificates","CertPassword","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `CertPassword` VARCHAR(40)";$q->QUERY_SQL($sql,'artica_backup');}
    $sql="SELECT `crt`,`srca`,`CertPassword`,`UsePrivKeyCrt`,`privkey`,`SquidCert`,`Squidkey`,`bundle`  FROM sslcertificates WHERE CommonName='$CommonName'";
    $ligne=$q->mysqli_fetch_array($sql);

    $certificate_path=dirname(__FILE__)."/ressources/conf/upload/Cert.pem";
    $main_path=dirname(__FILE__)."/ressources/conf/upload";
    $CAFile=dirname(__FILE__)."/ressources/conf/upload/CaFile.pem";

    $ligne["srca"]=str_replace("\\n", "\n", $ligne["srca"]);
    $ligne["bundle"]=str_replace("\\n", "\n", $ligne["bundle"]);
    $ligne["crt"]=str_replace("\\n", "\n", $ligne["crt"]);

    @file_put_contents($CAFile, $ligne["srca"]."\n".$ligne["bundle"]);
    @file_put_contents($certificate_path, $ligne["crt"]);

    $CMD[]="/usr/bin/openssl verify -verbose ";
    $CMD[]="-CAfile $CAFile";
    $CMD[]="-purpose any $certificate_path 2>&1";


    $cmdline=@implode(" ", $CMD);
    if($GLOBALS["VERBOSE"]){echo "<hr>".$cmdline."<br>";}
    $f[]=$cmdline;
    exec($cmdline,$results);
    $INFO=array();
    $class="text-info";

    foreach ($results as $num=>$ligne){
        if($GLOBALS["VERBOSE"]){echo "<li style='font-size:12px>$ligne</li>\n";}

        $ligne=str_replace($main_path."/", "", $ligne);
        if(preg_match("#(CN|OU).*?=#i",$ligne)){$INFO[]=$ligne;}
        if(preg_match("#[0-9]+:error:[0-9A-Z]+:#",$ligne)){$class="alert alert-success";}
        if(preg_match("#unable to load#",$ligne)){$class="alert alert-success";}
        if(preg_match("#Subject:(.*)#",$ligne)){$INFO[]=$ligne;}
        if(preg_match("#server\.pem#",$ligne)){$INFO[]=$ligne;}
        if(preg_match("#verify OK#i",$ligne)){$INFO[]=$ligne;}
        if(preg_match("#OK#",$ligne)){$INFO[]=$ligne;}
        $ligne=htmlentities($ligne);
        $f[]="$ligne";
    }
    if($class=="alert alert-success"){
        echo "<div class='alert alert-danger'>".@implode("<br>", $f)."</div>";
    }else{
        echo "<div class='alert alert-success'>".@implode("<br>", $INFO)."</div>";
    }
    //@unlink($CAFile);
    //@unlink($certificate_path);

}

function certificate_privkey_verify(){
    $CommonName=$_GET["certificate-privkey-verify"];
    $q=new lib_sqlite(GetDatabase());
    if(!$q->FIELD_EXISTS("sslcertificates","CertPassword","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `CertPassword` TEXT NULL";$q->QUERY_SQL($sql,'artica_backup');}
    $sql="SELECT `crt`,`CertPassword`,`UsePrivKeyCrt`,`privkey`,`SquidCert`,`Squidkey`  FROM sslcertificates WHERE CommonName='$CommonName'";
    $ligne=$q->mysqli_fetch_array($sql);



    echo "<script>if ( $('#spinner').children('.ibox-content').hasClass('sk-loading') ){
		$('#spinner').children('.ibox-content').toggleClass('sk-loading');
	}</script>";

    $certField="crt";
    $keyfield="privkey";

    if($ligne["UsePrivKeyCrt"]==0){
        $certField="SquidCert";
        $keyfield="Squidkey";
    }



    if($ligne[$certField]==null){
        $tpl=new templates();
        echo "<div class='alert alert-danger'>". $tpl->_ENGINE_parse_body("{failed}.- $certField -..{no_certificate}")."</div>";
        return;
    }


    @mkdir("/usr/share/artica-postfix/ressources/conf/upload",0755,true);

    $ligne[$certField]=str_replace("\\n", "\n", $ligne[$certField]);
    $ligne[$keyfield]=str_replace("\\n", "\n", $ligne[$keyfield]);


    $filepath=dirname(__FILE__)."/ressources/conf/upload/Cert.pem";
    @file_put_contents($filepath, $ligne[$certField]);
    if(!is_file($filepath)){
        echo "<div class='alert alert-danger'>$filepath permission denied</div>";
        return;
    }

    $passin=null;
    if($ligne["CertPassword"]<>null){$passin=" -passin pass:{$ligne["CertPassword"]}";}

    $md5=trim(exec("/usr/bin/openssl x509 -noout$passin -modulus -in $filepath | /usr/bin/openssl md5 2>&1"));
    $filepath=dirname(__FILE__)."/ressources/conf/upload/Cert.key";
    @file_put_contents($filepath, $ligne[$keyfield]);
    $md52=trim(exec("/usr/bin/openssl rsa -noout$passin -modulus -in $filepath | /usr/bin/openssl md5 2>&1"));

    if($md5<>$md52){
        echo "<div class='alert alert-danger'>ID: $CommonName<br>Private Key failed &laquo;$md5&raquo; / &laquo;$md52&raquo;</div>";
    }else{
        echo "<div class='alert alert-success'>ID: $CommonName<br>Private Key Success</div>";
    }

}

function certificate_srca_verify(){


    echo "<script>if ( $('#spinner').children('.ibox-content').hasClass('sk-loading') ){
		$('#spinner').children('.ibox-content').toggleClass('sk-loading');
	}</script>";

    $CommonName=$_GET["certificate-srca-verify"];
    $q=new lib_sqlite(GetDatabase());
    $sql="SELECT `srca`,`SquidCert`,`bundle`,`UsePrivKeyCrt`,`crt` FROM sslcertificates WHERE CommonName='$CommonName'";
    $t=$_GET["t"];
    $ligne=$q->mysqli_fetch_array($sql);
    if(!$q->ok){echo $q->mysql_error_html(true);return;}
    $tt=time();

    if($ligne["UsePrivKeyCrt"]==0){echo "<div class='alert alert-success'>Self-Signed certificate</div>";return;}

    if(strlen($ligne["srca"])<50){
        $sock=new sockets();
        echo "<div class='alert alert-danger'>No content</div>";
        return;
    }

    $certificate=$ligne["SquidCert"];
    if($ligne["UsePrivKeyCrt"]==1){
        $certificate=$ligne["crt"];

    }


    $certificate=str_replace("\\n", "\n", $certificate);
    $ligne["srca"]=str_replace("\\n", "\n", $ligne["srca"]);
    $ligne["bundle"]=str_replace("\\n", "\n", $ligne["bundle"]);

    $main_path=dirname(__FILE__)."/ressources/conf/upload";
    $certificate_path="$main_path/server.pem";
    $root_certificate="$main_path/ca.pem";
    @file_put_contents($certificate_path, $certificate);
    @file_put_contents($root_certificate, $ligne["srca"]."\n".$ligne["bundle"]);

    $CMD[]="/usr/bin/openssl verify -verbose ";
    $CMD[]="-CAfile $root_certificate";
    $CMD[]="-purpose any $certificate_path 2>&1";


    $cmdline=@implode(" ", $CMD);
    if($GLOBALS["VERBOSE"]){echo "<hr>".$cmdline."<br>";}
    $f[]=$cmdline;
    exec($cmdline,$results);
    $INFO=array();
    $class="text-info";

    foreach ($results as $num=>$ligne){
        if($GLOBALS["VERBOSE"]){echo "<li style='font-size:12px>$ligne</li>\n";}

        $ligne=str_replace($main_path."/", "", $ligne);

        if(preg_match("#[0-9]+:error:[0-9A-Z]+:#",$ligne)){$class="alert alert-success";}
        if(preg_match("#unable to load#",$ligne)){$class="alert alert-success";}
        if(preg_match("#Subject:(.*)#",$ligne)){$INFO[]=$ligne;}
        if(preg_match("#server\.pem#",$ligne)){$INFO[]=$ligne;}
        if(preg_match("#verify OK#i",$ligne)){$INFO[]=$ligne;}
        if(preg_match("#lookup#i",$ligne)){$INFO[]=$ligne;}
        if(preg_match("#OK#",$ligne)){$INFO[]=$ligne;}
        $ligne=htmlentities($ligne);
        $f[]="$ligne";
    }
    if($class=="alert alert-success"){
        echo "<div class='alert alert-danger'>".@implode("<br>", $f)."</div>";
    }else{
        echo "<div class='alert alert-success'>".@implode("<br>", $INFO)."</div>";
    }
    @unlink($root_certificate);
    @unlink($certificate_path);



}
function certificate_privkey_js():bool{
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $ID     = intval($_GET["privkey-js"]);
    $q      = new lib_sqlite(GetDatabase());
    $sql    ="SELECT * FROM sslcertificates WHERE ID='$ID'";
    $ligne  = $q->mysqli_fetch_array($sql);
    $CommonName=urlencode($ligne["CommonName"]);
    $function=$_GET["function"];
    return $tpl->js_dialog5("{$ligne["CommonName"]} {certificate}","$page?certificate-privkey=$CommonName&ID=$ID&function=$function");
}
function certificate_crt_js():bool{
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $ID     = intval($_GET["crt-js"]);
    $q      = new lib_sqlite(GetDatabase());
    $sql    ="SELECT * FROM sslcertificates WHERE ID='$ID'";
    $ligne  = $q->mysqli_fetch_array($sql);
    $CommonName=urlencode($ligne["CommonName"]);
    $function=$_GET["function"];
    return $tpl->js_dialog5("{$ligne["CommonName"]} {certificate}","$page?certificate-crt=$CommonName&ID=$ID&function=$function");
}
function certificate_srca_js():bool{
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $ID     = intval($_GET["srca-js"]);
    $function=$_GET["function"];
    $q      = new lib_sqlite(GetDatabase());
    $sql    ="SELECT * FROM sslcertificates WHERE ID='$ID'";
    $ligne  = $q->mysqli_fetch_array($sql);
    $CommonName=urlencode($ligne["CommonName"]);
    return $tpl->js_dialog5("{$ligne["CommonName"]} {certificate}","$page?certificate-srca=$CommonName&ID=$ID&function=$function");
}
function autorenew_js():bool{
    $tpl    = new template_admin();
    $page   = CurrentPageName();

    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));

    if($EnableNginx==0){
        return $tpl->js_error($tpl->_ENGINE_parse_body("{error_lesencrypt_nginx_not_installed}"));
    }

    return $tpl->js_dialog5("{autorenew}","$page?autorenew-popup=yes");
}
function certificate_csr_js():bool{
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $ID     = intval($_GET["csr-js"]);
    $function=$_GET["function"];
    $q      = new lib_sqlite(GetDatabase());
    $sql    ="SELECT * FROM sslcertificates WHERE ID='$ID'";
    $ligne  = $q->mysqli_fetch_array($sql);
    $CommonName=urlencode($ligne["CommonName"]);
    return $tpl->js_dialog5("{$ligne["CommonName"]} {CSR}","$page?certificate-csr=$CommonName&ID=$ID&function=$function");

}
function certificate_bundle_js():bool{
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $function=$_GET["function"];
    $ID     = intval($_GET["bundle-js"]);
    $q      = new lib_sqlite(GetDatabase());
    $sql    ="SELECT * FROM sslcertificates WHERE ID='$ID'";
    $ligne  = $q->mysqli_fetch_array($sql);
    $CommonName=urlencode($ligne["CommonName"]);
    return $tpl->js_dialog5("{$ligne["CommonName"]} {certificate_chain}","$page?certificate-bundle=$CommonName&ID=$ID&function=$function");

}
function certificate_provider_bundle_js():bool{
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $function=$_GET["function"];
    $ID     = intval($_GET["CABundleProvider-js"]);
    $q      = new lib_sqlite(GetDatabase());
    $sql    ="SELECT * FROM sslcertificates WHERE ID='$ID'";
    $ligne  = $q->mysqli_fetch_array($sql);
    $CommonName=urlencode($ligne["CommonName"]);
    return $tpl->js_dialog5("{$ligne["CommonName"]} {CABundleProvider}","$page?CABundleProvider=$CommonName&ID=$ID&function=$function");

}


function certificate_csr():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite(GetDatabase());
    $tt=time();
    $return=null;
    $commonName=$_GET["certificate-csr"];
    $CommonNameURL=urlencode($commonName);
    $sql="SELECT `csr`,`UsePrivKeyCrt`,`UseGodaddy`,domains FROM sslcertificates WHERE CommonName='$commonName'";
    if(isset($_GET["ID"])){
        $sql="SELECT * FROM sslcertificates WHERE ID='{$_GET["ID"]}'";
    }





    $ligne=$q->mysqli_fetch_array($sql);
    if(!$q->ok){echo $q->mysql_error_html(true);}
    $domains=$ligne["domains"];

    $html[]="<div id='$tt-verify' style='margin-top:20px'></div>";
    $html[]=$tpl->div_explain("{CSR}||{csr_ssl_explain}");
    $html[]="<textarea style='width: 817px;height: 406px;font-size: 15px;padding-left:80px;padding-top:15px'>{$ligne["csr"]}</textarea>";
    $report=certifcate_csr_parse($ligne["csr"],$ligne["privkey"]);
    echo $report;




    $html[]="<script>
	function verify$tt(){
		$return
		LoadAjax('$tt-verify','$page?certificate-csr-verify=$CommonNameURL',true);
	}
	setTimeout('verify$tt()',1000);
	</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function certifcate_csr_parse($csr,$privkey):string{
    $tpl=new template_admin();
    $subject = openssl_csr_get_subject($csr);
    if (!$subject) {
        return $tpl->div_error("Failed to parse the CSR subject.");

    }
    return "";
}

function certificate_csr_verify(){
    $CommonName=$_GET["certificate-csr-verify"];
    $q=new lib_sqlite(GetDatabase());
    $sql="SELECT `csr` ,`UsePrivKeyCrt`,`UseGodaddy` FROM sslcertificates WHERE CommonName='$CommonName'";
    $ligne=$q->mysqli_fetch_array($sql);


    $NOT_BUILD=false;
    if($ligne["UsePrivKeyCrt"]==1){$NOT_BUILD=true;}
    if($ligne["UseGodaddy"]==1){$NOT_BUILD=true;}

    $ligne=$q->mysqli_fetch_array($sql);
    if(!$q->ok){senderror($q->mysql_error);return;}
    $tt=time();

    if(!$NOT_BUILD){
        if(strlen($ligne["csr"])<50){
            $sock=new sockets();
            $CommonName=urlencode($CommonName);
            echo base64_decode($sock->getFrameWork("system.php?BuildCSR=$CommonName"));
        }
    }


    @mkdir(dirname(__FILE__)."/ressources/conf/upload",0755,true);
    $filepath=dirname(__FILE__)."/ressources/conf/upload/Cert.csr";
    @file_put_contents($filepath, $ligne["csr"]);
    $cmdline="/usr/bin/openssl req -text -noout -verify -verbose -in $filepath 2>&1";

    exec($cmdline,$results);
    $INFO=array();
    $class="text-info";
    if($GLOBALS["VERBOSE"]){echo $cmdline." = ". count($results)." Lines\n";}
    $f[]="$CommonName: $filepath ".strlen($ligne["csr"])." bytes";
    foreach ($results as $num=>$ligne){
        if($GLOBALS["VERBOSE"]){echo "L.$num $ligne<br>\n";}
        if(preg_match("#[0-9]+:error:[0-9A-Z]+:PEM routines:#",$ligne)){$class="alert alert-success";}
        if(preg_match("#unable to load#",$ligne)){$class="alert alert-success";}
        if(preg_match("#Subject:(.*)#",$ligne)){$INFO[]=$ligne;}
        if(preg_match("#verify OK#i",$ligne)){$INFO[]=$ligne;}
        $ligne=str_replace($filepath, "Info", $ligne);
        $ligne=htmlentities($ligne);
        $f[]="$ligne";
    }
    if($class=="alert alert-success"){
        echo "<div class='alert alert-danger'>".@implode("<br>", $f)."</div>";
    }else{
        echo "<div class='alert alert-success'>".@implode("<br>", $INFO)."</div>";
    }

    echo "<script>if ( $('#spinner').children('.ibox-content').hasClass('sk-loading') ){
		$('#spinner').children('.ibox-content').toggleClass('sk-loading');
	}</script>";
}

function parse_certificate_artica($commonName):array{
    $q = new lib_sqlite(GetDatabase());
    if(!is_numeric($commonName)) {
        $sql = "SELECT UsePrivKeyCrt,crt,SquidCert FROM sslcertificates WHERE CommonName='$commonName'";
    }else{
        $sql = "SELECT UsePrivKeyCrt,crt,SquidCert FROM sslcertificates WHERE ID='$commonName'";
    }
    $ligne = $q->mysqli_fetch_array($sql);
    if(!$q->ok){
        writelogs($q->mysql_error." [$commonName]",__FUNCTION__,__FILE__,__LINE__);
    }


    VERBOSE("Parse Certificate $commonName",__LINE__);

    if(!isset($ligne["UsePrivKeyCrt"])){
        $ligne["UsePrivKeyCrt"]=0;
    }
    $UsePrivKeyCrt=intval($ligne["UsePrivKeyCrt"]);

    $field="crt";
    if($UsePrivKeyCrt==0){
        $field="SquidCert";
    }
    if(!isset($ligne[$field])){
        $ligne[$field]="";
    }
    VERBOSE("$field = ".strlen($ligne[$field])." bytes",__LINE__);

    $ligne[$field]=str_replace("\\n", "\n", $ligne[$field]);
    $array=openssl_x509_parse($ligne[$field]);
    if(!is_array($array)){return array();}
    return $array;
}

function certificate_settings_locked():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $sock = new sockets();
    $q = new lib_sqlite(GetDatabase());
    $btname = null;
    $commonName = $_GET["certificate-settings"];
    $commonNameEnc = urlencode($commonName);
    $sql = "SELECT * FROM sslcertificates WHERE CommonName='$commonName'";
    $ligne = $q->mysqli_fetch_array($sql);
    $ID=$ligne["ID"];

    $UsePrivKeyCrt=intval($ligne["UsePrivKeyCrt"]);
    $CaContent=$ligne["privkey"];

    $field="crt";
    if($UsePrivKeyCrt==0){
        $CaContent=$ligne["Squidkey"];
        $field="SquidCert";
    }
    $ligne[$field]=str_replace("\\n", "\n", $ligne[$field]);
    $array=openssl_x509_parse($ligne[$field]);
    $openssl_x509_read=openssl_x509_read($ligne[$field]."\n".$CaContent);
    if(empty($openssl_x509_read)) {
        echo $tpl->div_error('x509 cert could not be read');
    }
    $capth="/usr/share/artica-postfix/ressources/logs/web/ca.key";
    $cer="/usr/share/artica-postfix/ressources/logs/web/cert.der";
    @file_put_contents($capth,$CaContent);
    @file_put_contents($cer,$ligne[$field]);

    $validTo_time_t = $array["validTo_time_t"];
    $purposes=$array["purposes"];
    $basicConstraints=$array["extensions"]["basicConstraints"];
    $keyUsage=$array["extensions"]["keyUsage"];
    $extendedKeyUsage=$array["extensions"]["extendedKeyUsage"];
    $subjectAltName=$array["extensions"]["subjectAltName"];
    $issuer_CN=$array["issuer"]["CN"];
    $LetsEncrypt=false;
    if($issuer_CN=="Let's Encrypt Authority X3"){
        $LetsEncrypt=True;
    }

    $table2[]="<table style='width:100%' class='table'>";

    $table2[]="<tr>";
    $table2[]="<td style='width:1%' nowrap><strong>{expire}:</strong></td>";
    $table2[]="<td><span class='label label-primary'>".$tpl->time_to_date($validTo_time_t)."</span></td>";
    $table2[]="</tr>";
    $table2[]="<tr>";
    $table2[]="<td style='width:1%' nowrap><strong>{CA_CERTIFICATE}:</strong></td>";

    if(preg_match("#CA:TRUE#",$basicConstraints)){
        $table2[]="<td><span class='label label-primary'>{yes}</span></td>";
    }else{
        $table2[]="<td><span class='label label'>{no}</span></td>";
    }
    $table2[]="</tr>";

    $purposesTR["sslclient"]="SSL client";
    $purposesTR["sslserver"]="SSL server";
    $purposesTR["nssslserver"]="Netscape SSL server";
    $purposesTR["smimesign"]="S/MIME signing";
    $purposesTR["smimeencrypt"]="S/MIME encryption";
    $purposesTR["crlsign"]="CRL signing";
    $purposesTR["any"]="Any Purpose";
    $purposesTR["ocsphelper"]="OCSP helper";
    $purposesTR["timestampsign"]="Time Stamp signing";

    foreach ($purposes as $index=>$sub){
        if(trim($sub[2])==null){continue;}
        $text=$sub[2];
        if(isset($purposesTR[$sub[2]])){$text=$purposesTR[$sub[2]];}
        $table2[]="<tr>";
        $table2[]="<td style='width:1%' nowrap><strong>$text:</strong></td>";


        if (intval($sub[0]) === 1) {
            $table2[]="<td><span class='label label-primary'>{yes} </span></td>";
        } else {
            $table2[]="<td><span class='label label'>{no}</span></td>";
        }
        $table2[]="</tr>";
    }


    $keyUsageAr=explode(",",$keyUsage);
    $extendedKeyUsageAr=explode(",",$extendedKeyUsage);
    $table2[]="<tr>";
    $table2[]="<td style='width:1%;vertical-align:top !important' nowrap ><strong>{KeyUsage}:</strong></td>";

    foreach ($keyUsageAr as $word){
        $word=trim($word);
        $keyUsageBr[]="<div style='margin-top:3px'><span class='label label-primary'>$word</span></div>";

    }
    foreach ($extendedKeyUsageAr as $word){
        $word=trim($word);
        $keyUsageBr[]="<div style='margin-top:3px'><span class='label label-warning'>$word</span></div>";

    }
    $table2[]="<td>".@implode(" ",$keyUsageBr)."</td>";
    $table2[]="</tr>";

    $subjectAltNameAr=explode(",",$subjectAltName);
    foreach ($subjectAltNameAr as $word){
        $word=trim($word);
        $subjectAltNameBr[]="<div style='margin-top:3px'>$word</div>";

    }
    $table2[]="<tr>";
    $table2[]="<td style='width:1%;vertical-align:top !important' nowrap ><strong>{subjectAltName}:</strong></td>";
    $table2[]="<td>".@implode(" ",$subjectAltNameBr)."</td>";
    $table2[]="</tr>";

    $table2[]="</table>";

    $html[]="<div class='ibox-content'>
    <table style='width:100%'>
    <tr>
    <td style='vertical-align: top;width:50%'>
    <table style='width:100%' class='table'>";

    if($ligne["CommonName"]==null){$ligne["CommonName"]=$array["CN"];}
    $html[]="<tr style='background: #d1dade'>";
    $html[]="<td style='width:1%' nowrap colspan='2'><strong>{issuer}</strong></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:1%' nowrap><strong>{CommonName}:</strong></td>";
    $html[]="<td>$issuer_CN</td>";
    $html[]="</tr>";

    if($array["issuer"]["C"]<>null){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{countryName}:</strong></td>";
        $html[]="<td>{$array["issuer"]["C"]}</td>";
        $html[]="</tr>";

    }
    if($array["issuer"]["O"]<>null){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{organizationName}:</strong></td>";
        $html[]="<td>{$array["issuer"]["O"]}</td>";
        $html[]="</tr>";

    }
    $html[]="<tr style='background: #d1dade'>";
    $html[]="<td style='width:1%' nowrap colspan='2'><strong>{certificate}</strong></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:1%' nowrap><strong>{CommonName}:</strong></td>";
    $html[]="<td nowrap>{$ligne["CommonName"]} &nbsp;
    <a class='btn btn-white btn-bitbucket' 
        OnClick=\"Loadjs('$page?change-cert-js=$ID');\">
            <i class='fa fa-wrench'></i></a>
    </td>";
    $html[]="</tr>";
    if($ligne["CountryName"]<>null){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{countryName}:</strong></td>";
        $html[]="<td>{$ligne["CountryName"]}</td>";
        $html[]="</tr>";

    }
    if($ligne["stateOrProvinceName"]<>null){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{stateOrProvinceName}:</strong></td>";
        $html[]="<td>{$ligne["stateOrProvinceName"]}</td>";
        $html[]="</tr>";

    }
    if($ligne["localityName"]<>null){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{localityName}:</strong></td>";
        $html[]="<td>{$ligne["localityName"]}</td>";
        $html[]="</tr>";

    }
    if($ligne["OrganizationName"]<>null){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{organizationName}:</strong></td>";
        $html[]="<td>{$ligne["OrganizationName"]}</td>";
        $html[]="</tr>";

    }
    if($ligne["OrganizationalUnit"]<>null){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{organizationalUnitName}:</strong></td>";
        $html[]="<td>{$ligne["OrganizationalUnit"]}</td>";
        $html[]="</tr>";

    }
    if($ligne["emailAddress"]<>null){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{emailAddress}:</strong></td>";
        $html[]="<td>{$ligne["emailAddress"]}</td>";
        $html[]="</tr>";

    }
    if($ligne["levelenc"]<>null){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{level_encryption}:</strong></td>";
        $html[]="<td>{$ligne["levelenc"]}</td>";
        $html[]="</tr>";

    }
    if($ligne["CertificateMaxDays"]<>null){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{CertificateMaxDays}:</strong></td>";
        $html[]="<td>{$ligne["CertificateMaxDays"]} ({days}</td>";
        $html[]="</tr>";

    }

    if(!IfCertisCA($ligne[$field])){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{type}:</strong></td>";
        $html[]="<td><strong>{server_certificate}</strong></td>";
        $html[]="</tr>";
    }


    $html[]="</table>
    </td><td style='vertical-align: top;width:50%;padding-left: 15px'>".@implode("\n",$table2)."</td>
    </tr>
    </table>";


    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function certificate_settings_locked_empty():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $q=new lib_sqlite(GetDatabase());
    $id=intval($_GET["certificate-locked-not-uploaded"]);
    $sql="SELECT * FROM sslcertificates WHERE ID='$id'";
    $ligne=$q->mysqli_fetch_array($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }
    $function=$_GET["function"];

    $db=file_get_contents(dirname(__FILE__) . '/ressources/databases/ISO-3166-Codes-Countries.txt');
    $tbl=explode("\n",$db);
    foreach ($tbl as $line){
        if(preg_match('#(.+?);\s+([A-Z]{1,2})#',$line,$regs)){
            $regs[2]=trim($regs[2]);
            $regs[1]=trim($regs[1]);
            $array_country_codes["{$regs[1]}_{$regs[2]}"]=$regs[1];
            $array_country_codes2[$regs[2]]="{$regs[1]}_{$regs[2]}";
        }
    }

    if(strlen($ligne["csr"])>50) {
        $tpl->table_form_field_js("Loadjs('$page?csr-js=$id&function=$function')","AsCertifsManager");
        $tpl->table_form_field_button("{CSR}","{view} {CSR}",ico_certificate);

    }
    if(strlen($ligne["crt"])<50){
        $tpl->table_form_field_js("s_PopUpFull('https://cyberdef360.com/store/ssl-certificates','1024','900');");
        $tpl->table_form_field_button("class:btn-blue:{generate_certificate}","{obtain_an_official_certificate}",ico_link);
        $tpl->table_form_field_js("Loadjs('fw.certificates-center.import.cert.php?certid=$id&function=$function')","AsCertifsManager");
        $tpl->table_form_field_button("{import}","{import_set_certificates} (*.zip)",ico_file_zip);
        $CommonName=urlencode($ligne["CommonName"]);
        $tpl->table_form_field_js("Loadjs('fw.certificates-center.import.360.php?CommonName=$CommonName&certid=$id&function=$function')","AsCertifsManager");
        $tpl->table_form_field_button("{import}","{import_certificates_from_cyberdef360} (*.pem)",ico_certificate);

    }


    $tpl->table_form_field_js("");
    $tpl->table_form_field_text("{CommonName}", $ligne["CommonName"],ico_earth);
    $tpl->table_form_field_text("{countryName}", $ligne["CountryName"],ico_flag);
    $tpl->table_form_field_text("{stateOrProvinceName}", $ligne["stateOrProvinceName"],ico_location);
    $tpl->table_form_field_text("{localityName}", $ligne["localityName"],ico_city);
    $tpl->table_form_field_text("{organizationName}", $ligne["OrganizationName"],ico_sitemap);
    $tpl->table_form_field_text("{organizationalUnitName}", $ligne["OrganizationalUnit"],ico_group);
    $tpl->table_form_field_text("{emailAddress}", $ligne["emailAddress"],ico_email);
    $tpl->table_form_field_text("{level_encryption}", $ligne["levelenc"],ico_certificate);
    $tpl->table_form_field_text("{CertificateMaxDays}", $ligne["CertificateMaxDays"]." {days}",ico_timeout);
    echo $tpl->table_form_compile();



return true;
}
function certificate_settings(){
    $function=$_GET["function"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $q=new lib_sqlite(GetDatabase());
    $btname=null;
    $commonName=$_GET["certificate-settings"];
    $commonNameEnc=urlencode($commonName);
    $sql="SELECT * FROM sslcertificates WHERE CommonName='$commonName'";

    $ligne=$q->mysqli_fetch_array($sql);
    $ID=intval($ligne["ID"]);
    $Generated=intval($ligne["Generated"]);
    $UsePrivKeyCrt=intval($ligne["UsePrivKeyCrt"]);

    if($UsePrivKeyCrt==1){
        VERBOSE("Certificate is an official certificate",__LINE__);
        if(strlen($ligne["csr"])>50){
            VERBOSE("Certificate as a Certificate Request",__LINE__);
            if(strlen($ligne["crt"])<50){
                VERBOSE("No certificate Uploaded",__LINE__);
                echo "<div id='certificate-settings-locked'></div>
                        <script>
                            LoadAjax('certificate-settings-locked','$page?certificate-locked-not-uploaded=$ID&function=$function')
                        </script>";
                return true;
            }
        }

    }

    $field="crt";
    if($UsePrivKeyCrt==0){
        $field="SquidCert";
    }
    if($ligne[$field]==null){$Generated=0;}

    $CertificateData=$ligne[$field];
    $openssl_x509_parse=openssl_x509_parse($CertificateData);
    $issuer=$openssl_x509_parse["issuer"]["CN"];
    if($issuer=="Let's Encrypt Authority X3"){
        return certificate_settings_locked();
    }



    if($Generated==1){
        return certificate_settings_locked();
    }

    $UsePrivKeyCrt=intval($ligne["UsePrivKeyCrt"]);
    $UseLetsEncrypt=intval($ligne["UseLetsEncrypt"]);
    if(!is_numeric($ligne["CertificateMaxDays"])){$ligne["CertificateMaxDays"]=730;}


    $db=file_get_contents(dirname(__FILE__) . '/ressources/databases/ISO-3166-Codes-Countries.txt');
    $tbl=explode("\n",$db);
    foreach ($tbl as $line){
        if(preg_match('#(.+?);\s+([A-Z]{1,2})#',$line,$regs)){
            $regs[2]=trim($regs[2]);
            $regs[1]=trim($regs[1]);
            $array_country_codes["{$regs[1]}_{$regs[2]}"]=$regs[1];
            $array_country_codes2[$regs[2]]="{$regs[1]}_{$regs[2]}";
        }
    }



    $ENC[1024]=1024;
    $ENC[2048]=2048;
    $ENC[4096]=4096;

    if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);
        if ($gpid > 0) {
            $Addon="&HarmpID=$gpid";
        }
    }

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.log";
    $ARRAY["CMD"]="openssl.php?generate-x509=$commonNameEnc$Addon";
    $ARRAY["TITLE"]="{certificate} {$ligne["CommonName"]}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-sslcerts-service','$page?table=yes');";

    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-generate-cert-restart')";

    $html="<div id='progress-generate-cert-restart'></div>";
    $form[]=$tpl->field_hidden("SETTINGS", "True");

    if($UsePrivKeyCrt==1){$SelfSigned=0;}else{$SelfSigned=1;}

    if(strlen($ligne["csr"])>50) {
        $form[] = $tpl->field_info("csr-none", "{CSR}",
            array("VALUE" => null,
                "BUTTON" => true,
                "BUTTON_CAPTION" => "{view} {CSR}",
                "BUTTON_JS" => "Loadjs('$page?csr-js=$ID')"

            ));
    }


    $form[]=$tpl->field_checkbox("SelfSigned", "{SelfSignedCert}", $SelfSigned,true,"{SelfSignedCertText}");
    $form[]=$tpl->field_checkbox("AsRoot", "{ROOT_CERT}", $ligne["AsRoot"],true);



    $form[]=$tpl->field_text("CommonName", "{CommonName}", $ligne["CommonName"],true,null);
    $form[]=$tpl->field_array_hash($array_country_codes,"CountryName", "nonull:{countryName}", $ligne["CountryName"]);
    $form[]=$tpl->field_text("stateOrProvinceName", "{stateOrProvinceName}", $ligne["stateOrProvinceName"]);
    $form[]=$tpl->field_text("localityName", "{localityName}", $ligne["localityName"]);
    $form[]=$tpl->field_text("OrganizationName", "{organizationName}", $ligne["OrganizationName"]);
    $form[]=$tpl->field_text("OrganizationalUnit", "{organizationalUnitName}", $ligne["OrganizationalUnit"]);
    $form[]=$tpl->field_text("emailAddress", "{emailAddress}", $ligne["emailAddress"]);
    $form[]=$tpl->field_array_hash($ENC, "levelenc", "{level_encryption}", $ligne["levelenc"]);
    $form[]=$tpl->field_numeric("CertificateMaxDays","{CertificateMaxDays} ({days})",$ligne["CertificateMaxDays"]);


    if($SelfSigned==1){
        $tpl->form_add_button("{generate_certificate}", "$jsrestart");
        $btname="{apply}";

    }




    if($UseLetsEncrypt==1){
        $btname=null;

    }
    $jsafter="";
    if(strlen($function)>2){$jsafter="$function();";}
    $html=$html. $tpl->form_outside($commonName, @implode("\n", $form),null,$btname,$jsafter,"AsCertifsManager");
    echo $html;
}

function certificate_settings_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite(GetDatabase());
    foreach ($_POST as $num=>$vl){
        $vl=url_decode_special_tool($vl);
        $_POST[$num]=$q->sqlite_escape_string2($vl);
    }

    $CommonName=$_POST["CommonName"];
    if($_POST["SelfSigned"]==0){$_POST["UsePrivKeyCrt"]=1;}else{$_POST["UsePrivKeyCrt"]=0;}


    $sql="UPDATE `sslcertificates` SET
	CountryName='{$_POST["CountryName"]}',
	stateOrProvinceName='{$_POST["stateOrProvinceName"]}',
	CertificateMaxDays='{$_POST["CertificateMaxDays"]}',
	OrganizationName='{$_POST["OrganizationName"]}',
	OrganizationalUnit='{$_POST["OrganizationalUnit"]}',
	emailAddress='{$_POST["emailAddress"]}',
	localityName='{$_POST["localityName"]}',
	levelenc='{$_POST["levelenc"]}',
	UsePrivKeyCrt='{$_POST["UsePrivKeyCrt"]}',
	CertificateMaxDays='{$_POST["CertificateMaxDays"]}',
	password='{$_POST["password"]}'
	WHERE CommonName='$CommonName'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error_html(true);}
    return true;

}


function certificate_new_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $OnlyCSR=false;
    if(isset($_GET["OnlyCSR"])){$OnlyCSR=true;}
    $function=$_GET["function"];

    $ligne=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CertificateCenterCSR"));

    $t=time();
    if($ligne["CountryName"]==null){$ligne["CountryName"]="US";}
    if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
    if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
    if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
    if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
    if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}
    if(!is_numeric($ligne["CertificateMaxDays"])){$ligne["CertificateMaxDays"]=730;}
    if(!is_numeric($ligne["levelenc"])){$ligne["levelenc"]=4096;}

    if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);
        if ($gpid > 0) {
            $Addon="&HarmpID=$gpid";
        }
    }

    $sock->getFrameWork("openssl.php?copy-csr=yes$Addon");
    $data=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/Myprivkey.csr");
    @unlink("/usr/share/artica-postfix/ressources/logs/web/Myprivkey.csr");

    if($ligne["CommonName"]==null){
        $ligne["CommonName"]=$sock->getFrameWork("system.php?hostname-g=yes");
    }
    $array_country_codes=array();
    $db=file_get_contents(dirname(__FILE__) . '/ressources/databases/ISO-3166-Codes-Countries.txt');
    $tbl=explode("\n",$db);
    foreach ($tbl as $line){
        if(preg_match('#(.+?);\s+([A-Z]{1,2})#',$line,$regs)){
            $regs[2]=trim($regs[2]);
            $regs[1]=trim($regs[1]);
            $array_country_codes["{$regs[1]}_{$regs[2]}"]=$regs[1];
            $array_country_codes2[$regs[2]]="{$regs[1]}_{$regs[2]}";
        }
    }

    $ENC[1024]=1024;
    $ENC[2048]=2048;
    $ENC[4096]=4096;
    $jsafter="dialogInstance1.close();$function()";

    $html="<div id='progress-generate-csr-restart'></div>";
    $form[]=$tpl->field_hidden("GENCSR", "True");
    if($OnlyCSR){
        $form[]=$tpl->field_hidden("OnlyCSR", "True");
        $form[]=$tpl->field_certificate("OnlyCSRID","{based_on_certificate}",null);
    }
    $form[]=$tpl->field_text("CommonName", "{CommonName}", $ligne["CommonName"],true,null);
    $form[]=$tpl->field_array_hash($array_country_codes,"CountryName", "nonull:{countryName}", $ligne["CountryName"]);
    $form[]=$tpl->field_text("stateOrProvinceName", "{stateOrProvinceName}", $ligne["stateOrProvinceName"]);
    $form[]=$tpl->field_text("localityName", "{localityName}", $ligne["localityName"]);
    $form[]=$tpl->field_text("OrganizationName", "{organizationName}", $ligne["OrganizationName"]);
    $form[]=$tpl->field_text("OrganizationalUnit", "{organizationalUnitName}", $ligne["OrganizationalUnit"]);
    $form[]=$tpl->field_text("emailAddress", "{emailAddress}", $ligne["emailAddress"]);
    $form[]=$tpl->field_array_hash($ENC, "levelenc", "{level_encryption}", $ligne["levelenc"]);
    $form[]=$tpl->field_numeric("CertificateMaxDays","{CertificateMaxDays} ({days})",$ligne["CertificateMaxDays"]);
    $form[]=$tpl->field_tags("domains","{domains}",$ligne["domains"]);

    $title_form="{new_certificate}";
    $button_name="{generate certificate}";
    $EXPLAIN="{new_certificate_explain}";
    if($OnlyCSR){
        $title_form="{CSR}";
        $EXPLAIN="{csr_ssl_explain}";
        $button_name="{generate_csr}";
    }
    $html=$html. $tpl->form_outside($title_form, $form,$EXPLAIN,$button_name,$jsafter,"AsCertifsManager");
    echo $html;
    return true;
}

function form_search():bool{
    $Addon=null;
    if(isset($_GET["HarmpID"])){
        $_SESSION["HARMPID"]=intval($_GET["HarmpID"]);
        $Addon="&HarmpID={$_GET["HarmpID"]}";
    }else{
        if(isset($_SESSION["HARMPID"])){
            $Addon="&HarmpID={$_SESSION["HarmpID"]}";
        }
    }
    $tpl=new template_admin();
    $page=CurrentPageName();
    $options["WIDTH"]=460;
    echo $tpl->search_block($page,null,null,null,"&table=yes$Addon",$options);
    return true;
}

function PatchTables():bool{
    $q=new lib_sqlite(GetDatabase());

    $sql="CREATE TABLE IF NOT EXISTS `sslcertificates` (
    `ID` INTEGER PRIMARY KEY AUTOINCREMENT,    `CommonName` TEXT UNIQUE,    `DateFrom` text,    `DateTo` text,    `CountryName` TEXT,    `stateOrProvinceName` TEXT,    `localityName` TEXT,    `OrganizationName` TEXT,    `OrganizationalUnit` TEXT,    `CompanyName`  TEXT,    `emailAddress` TEXT,    `levelenc` INTEGER NOT NULL DEFAULT '4096',    `CertificateMaxDays` INTEGER,    `IsClientCert` INTEGER,    `AsProxyCertificate` INTEGER,    `UsePrivKeyCrt` INTEGER,    `UseGodaddy` INTEGER,    `UseLetsEncrypt` INTEGER,    `easyrsa` INTEGER ,    `DynamicCert` TEXT,    `DynamicDer` TEXT,    `DerContent` TEXT,    `csr` TEXT,    `srca` TEXT,    `der` TEXT,    `privkey` TEXT,    `pks12` TEXT,    `keyPassword` TEXT,    `crt` TEXT,    `Squidkey` TEXT,    `SquidCert` TEXT,    `bundle` TEXT,    `clientkey` TEXT,    `clientcert` TEXT,    `easyrsabackup` BLOB,    `CertPassword` TEXT,    `password` TEXT
  )";
    $q->QUERY_SQL($sql);


    if (!$q->FIELD_EXISTS("sslcertificates", "AsRoot")) {
        $q->QUERY_SQL("ALTER TABLE sslcertificates ADD AsRoot INTEGER NOT NULL DEFAULT 0");
    }
    if (!$q->FIELD_EXISTS("sslcertificates", "Generated")) {
        $q->QUERY_SQL("ALTER TABLE sslcertificates ADD Generated INTEGER NOT NULL DEFAULT 0");
    }

    $TextesPaths=array("subjectAltName","subjectAltName1","subjectAltName2","CertificateCenterCSR");
    foreach ($TextesPaths as $field) {
        if (!$q->FIELD_EXISTS("sslcertificates", $field)) {
            $q->QUERY_SQL("ALTER TABLE sslcertificates ADD $field TEXT NOT NULL DEFAULT ''");
        }
    }
    return true;
}



function table():bool{
    $tpl=new template_admin();
    $function="";
    if(isset($_GET["HarmpID"])){}
    $function=$_GET["function"];
    $page=CurrentPageName();
    PatchTables();
    $q=new lib_sqlite(GetDatabase());


    $Addon="";
    if(isset($_GET["HarmpID"])){
        $Addon="&HarmpID={$_GET["HarmpID"]}";
    }
    $users=new usersMenus();
    if(isset($_SESSION["HARMPID"])){
        if($_SESSION["HARMPID"]>0){
            $users->AsCertifsManager=true;
        }
    }
    if(isset($_GET["t"])) {
        $t = $_GET["t"];
    }else{
        $t=time();
    }
    if(!is_numeric($t)){$t=time();}

    $expire=$tpl->_ENGINE_parse_body("{expire}");
    $CommonName=$tpl->_ENGINE_parse_body("{CommonName}");
    $Organization=$tpl->_ENGINE_parse_body("{organizationName}");
    $organizationalUnitName=$tpl->_ENGINE_parse_body("{organizationalUnitName}");
    $emailAddress=$tpl->javascript_parse_text("{emailAddress}");

    $bts[]="<div class=\"btn-group\" data-toggle=\"buttons\">";

    if($users->AsCertifsManager){
        $bts[]="<label class=\"btn btn btn-blue\" OnClick=\"Loadjs('fw.selfsigned.php?function=$function')\"><i class='fa fa-plus'></i> {SELF_ROOT_CERT} </label>";

        $add="Loadjs('fw.certificates-center.php?new-certificate-js=yes&OnlyCSR=yes&function=$function');";
        $bts[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_certificate} (CSR)</label>";


        $addLetsEncrypt="Loadjs('fw.certificates-center.letsencrypt.php?function=$function')";
        $bts[]="<label class=\"btn btn-info\" OnClick=\"$addLetsEncrypt\"><i class='fa fa-plus'></i> {LETSENCRYPT_CERTIFICATE} </label>";



    $filters["{import} PFX"]="far fa-cloud-download||Loadjs('fw.certificates-center.import.pfx.php?function=$function');";
    $filters["{import} p7r - p7b"]="far fa-cloud-download||Loadjs('fw.certificates-center.import.p7r.php?function=$function');";
    $filters["{import_set_certificates} (zip)"]="far fa-cloud-download||Loadjs('fw.certificates-center.import.cert.php?function=$function');";
     $filters["{import} {server_certificate}"]=ico_import."||Loadjs('fw.certificates-center.import.servercert.php?function=$function');";
    $filters["{generate_csr}"]="fas fa-file-certificate||Loadjs('fw.certificates-center.php?new-certificate-js=yes&OnlyCSR=yes&function=$function');";
    $filters["{autorenew} Let`s Encrypt"]=ico_refresh."||Loadjs('$page?autorenew-js=yes&function=$function');";

    }

    $filters["SPACER"]=true;
    $filters["{import} {database}"]="far fa-cloud-download||Loadjs('fw.certificates-center.import.php?function=$function&function=$function');";
    $filters["{export} {database}"]="far fa-cloud-upload||document.location.href='$page?getdb=yes&function=$function'";

    $bts[]=$tpl->button_dropdown_table("{actions}",$filters,"AsCertifsManager");
    $bts[]="</div>";
    $html[]="<table id='table-firewall-rules' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true style='width:1%'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$CommonName</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$expire</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$Organization</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$emailAddress</th>";
    $html[]="<th data-sortable=false>PFX</th>";
    $html[]="<th data-sortable=false>{download2}</th>";
    $html[]="<th data-sortable=false>{export}</th>";
    $html[]="<th data-sortable=false>Del.</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $sock=new sockets();
    $jsAfter="LoadAjax('table-loader-proxy-outgoingaddr','$page?table=yes');";
    $GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);




    $sql="SELECT *  FROM sslcertificates ORDER BY CommonName LIMIT 250";
    $search="";
    if(isset($_GET["search"])){$search=trim($tpl->CLEAN_BAD_XSS($_GET["search"]));}
    if(strlen($search)>0){
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);

        $fields=array("CountryName","stateOrProvinceName","OrganizationName","OrganizationalUnit","emailAddress","localityName");
        foreach ($fields as $ff){
            $tt[]=" OR ($ff LIKE '$search')";
        }
        $sql="SELECT *  FROM sslcertificates WHERE ( (CommonName LIKE '$search')".@implode($tt)." )  ORDER BY CommonName LIMIT 250";
    }

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){

        if(preg_match("#file is not a database#",$q->mysql_error)){
            $ico=ico_support;
            $js="Loadjs('$page?repair-database=yes&function=$function');";
            $button=$tpl->button_autnonome("{repair_database}",$js, $ico, "AsCertifsManager",300,"btn-danger");
            echo $tpl->div_error($q->mysql_error."<br>$sql<div style='text-align:right'>$button</div>");
            return true;
        }

        $ico=ico_support;
        $js="Loadjs('$page?patch-database=yes&function=$function');";
        $button=$tpl->button_autnonome("{repair_database}",$js, $ico, "AsCertifsManager",300,"btn-danger");
        echo $tpl->div_error($q->mysql_error."<br>$sql<div style='text-align:right'>$button</div>");
        return true;
    }

    VERBOSE("RESULTS = ".count($results)." entries...",__LINE__);
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $square_class="text-navy";
        $pproxy=array();
        $ID=intval($ligne["ID"]);
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $CommonName=$ligne["CommonName"];
        $UsePrivKeyCrt=intval($ligne["UsePrivKeyCrt"]);
        $UseLetsEncrypt=intval($ligne["UseLetsEncrypt"]);
        if(!isset($ligne["ServerCert"])){$ligne["ServerCert"]=0;}
        $CommonNameEnc=urlencode($CommonName);
        $aclid=md5($CommonName);
        $pks12=strlen($ligne["pks12"]);
        $DateToText=null;
        $treste=null;
        $AsRoot=intval($ligne["AsRoot"]);
        $DateFrom=$ligne["DateFrom"];
        $DateTo=$ligne["DateTo"];
        if($DateTo=="0000-00-00"){$DateTo=null;}
        $Generated=$ligne["Generated"];
        $crt=$ligne["crt"];
        $SquidCert=$ligne["SquidCert"];
        $privkey=$ligne["privkey"];
        $Squidkey=$ligne["Squidkey"];
        $ServerCert=intval($ligne["ServerCert"]);
        $csr=$ligne["csr"];

        if($UsePrivKeyCrt==1){$SquidCert=$crt;$Squidkey=$privkey;}
        $icon2=null;$icon3=null;
        $icon="<span class='label label-success'>{SelfSignedCert}</span>";
        if($UsePrivKeyCrt==1){$icon="<span class='label label-primary'>{official_certificate}</span>";}

        if($Generated==0){
            if(IfCertisCA($SquidCert)){
                $q->QUERY_SQL("UPDATE sslcertificates SET Generated=1,AsRoot=1 WHERE ID=$ID");
                $AsRoot=1;
            }else{
                $q->QUERY_SQL("UPDATE sslcertificates SET Generated=1,AsRoot=0 WHERE ID=$ID");
            }
        }

        if($AsRoot==1){
            $icon2="&nbsp;<span class='label label-info'>{ROOT_CERT}</span>";
            if($ServerCert==1){
                $icon2="&nbsp;<span class='label label-info'>{server_certificate}</span>";
            }
        }
        if(strlen($Squidkey)<50){
            $icon3="&nbsp;<span class='label label-warning'>{missing_private_key}</span>";
            if($ServerCert==1){
                $icon3="";
            }
        }

        if(isset($_SESSION["HARMPID"])) {
            $gpid = intval($_SESSION["HARMPID"]);
            if ($gpid > 0) {
                $Addon="&HarmpID=$gpid";
            }
        }

        if($DateTo==null){
            $sock->getFrameWork("openssl.php?extract-infos=$ID$Addon");
        }

        VERBOSE("DateTo:$DateTo",__LINE__);
        $iconStatus="";
        if($DateTo<>null){
            $t1=time();
            $t2=strtotime($DateTo);
            if($t1>$t2){
                $iconStatus="&nbsp;<span class='label label-danger'>{expired}</span>";
            }
            if($t2>$t1) {
                $treste = "(" . $tpl->javascript_parse_text(distanceOfTimeInWords($t1, $t2)) . ")";
                $treste_mins = distanceMinStrings($DateTo);
                if ($treste_mins < 4320) {
                    $iconStatus = "&nbsp;<span class='label label-danger'>{expire_soon}</span>";

                }
            }
        }else{
            $DateTo=$tpl->icon_nothing();
        }
        $ID=$ligne["ID"];
        $js="Loadjs('$page?certificate-js=$CommonNameEnc&ID=$ID&function=$function')";


        if($UseLetsEncrypt==1){
            $icon="<span class='label label-info'>{LETSENCRYPT_CERTIFICATE}</span>";
            if(strlen($ligne["crt"])<50){
                $js="Loadjs('fw.certificates-center.letsencrypt.php?ID=$ID&function=$function')";
                $icon="<span class='label label-default'>{LETSENCRYPT_CERTIFICATE}</span>";
            }
        }

        $field="crt";
        if($UsePrivKeyCrt==0){$field="SquidCert";}
        $CertficateData=$ligne[$field];

        if(strlen($ligne[$field])<10){
            if($UseLetsEncrypt==0) {
                $icon = "<span class='label'>{not_generated}</span>";
            }
        }
        $href=$tpl->td_href($CommonName, "{click_to_edit}",$js);
        $idrow=md5($CommonNameEnc);

        VERBOSE("CSR: ".strlen($csr),__LINE__);
        VERBOSE("CRT: ".strlen($CertficateData),__LINE__);
        VERBOSE("KEY: ".strlen($Squidkey),__LINE__);

        if(strlen($csr)>50){
            if( strlen($CertficateData)<50 ){
                $icon2=null;$icon3="<span class='label label-warning'>{waiting_registration}</span>";
                $icon = "<span class='label'>{CSR}</span>";

            }
        }

        $ous=array();
        if(strlen($ligne["OrganizationName"])>2){
            $ous[]= $ligne["OrganizationName"];
        }
        if(strlen($ligne["OrganizationalUnit"])>2){
            $ous[]= $ligne["OrganizationalUnit"];
        }
        $CommonNameDiv=md5($CommonName);
        if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="<i class='fa fa-minus'></i>";}
        if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="<i class='fa fa-minus'></i>";}
        if($ligne["emailAddress"]==null){$ligne["emailAddress"]="<i class='fa fa-minus'></i>";}
        if($DateTo==null){$DateTo="<i class='fa fa-minus'></i>";}
        $html[]="<tr class='$TRCLASS' id='$idrow'>";
        $html[]="<td style='width:1%' nowrap>$icon</td>";
        $html[]="<td>$href{$icon2}{$icon3}</td>";
        $html[]="<td>$DateTo$iconStatus&nbsp;<small>$treste</small></td>";
        $html[]="<td>". @implode("&nbsp;|&nbsp;",$ous)."</td>";
        $html[]="<td><span id='certdiv-$CommonNameDiv'>". td_sitesbyCert($CommonName)."</span></td>";
        $html[]="<td style='width:1%' nowrap >{$ligne["emailAddress"]}</td>";

        if($pks12>50) {
            $html[] = "<td class='center' style='width:1%' nowrap>" . $tpl->icon_archive("document.location.href='$page?download-pfx=$ID'", "AsCertifsManager") . "</td>";
        }else{
            $html[] = "<td class='center' style='width:1%' nowrap>" . $tpl->icon_run("Loadjs('$page?create-pfx=$CommonNameEnc&function=$function')") . "</td>";
        }

        $html[]="<td class='center' style='width:1%' nowrap>". $tpl->icon_download("document.location.href='$page?download-cert=$ID'","AsCertifsManager") ."</td>";

        $html[]="<td class='center' style='width:1%' nowrap>". $tpl->icon_export("document.location.href='$page?download=$CommonNameEnc'","AsCertifsManager") ."</td>";


        $jsDel="Loadjs('$page?delete-js=$CommonNameEnc&id=$idrow')";
        if($PowerDNSEnableClusterSlave==1){
            $jsDel="";
        }

        $html[]="<td class='center' style='width:1%' nowrap>". $tpl->icon_delete($jsDel,"AsCertifsManager") ."</td>";
        $html[]="</tr>";

    }
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    if($PowerDNSEnableClusterSlave==1){
        $bts=array();
    }

    $TINY_ARRAY["TITLE"]="{certificates_center}";
    $TINY_ARRAY["ICO"]=ico_certificate;
    $TINY_ARRAY["EXPL"]="{ssl_certificates_center_text}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$bts);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='8'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$jstiny
</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function td_sitesbyCert($CommonName):string{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $q=new lib_sqlite(NginxGetDB());
    $results=$q->QUERY_SQL("SELECT serviceid FROM `service_parameters` WHERE 
            `zkey`='ssl_certificate' AND zvalue='$CommonName'");

    $CONF=array();
    foreach ($results as $index=>$ligne){
        $serviceid=$ligne["serviceid"];
        $host=get_servicename($serviceid);
        if(strlen($host)<3){continue;}
        $CONF[]=$tpl->td_href($host,"","Loadjs('fw.nginx.sites.php?www-parameters-ssl-js=$serviceid&CertCenter=$function')");
    }

    return @implode(", ",$CONF);
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    if(isset($ligne["servicename"])) {
        return strval($ligne["servicename"]);
    }
    return "";
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}
function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}
function IfCertisCA($certdata){
    $array=openssl_x509_parse($certdata);
    if(!isset($array["extensions"])){return false;}
    if(!isset($array["extensions"]["basicConstraints"])){return false;}

    if(preg_match("#CA:TRUE#i",$array["extensions"]["basicConstraints"])){
        writelogs("{$array["extensions"]["basicConstraints"]} Matches CA:TRUE",__FUNCTION__,__FILE__,__LINE__);
        return true;
    }
    return false;
}


function certificate_new_save():bool{
    $q=new lib_sqlite(GetDatabase());
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $CommonName=strtolower(trim($_POST["CommonName"]));
    $sock=new sockets();
    $gpid=0;
    if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);

    }



    $sock->SET_INFO("CSRCommonName",$CommonName);


    if(isset($_POST["OnlyCSR"])){
        if(isset($_POST["OnlyCSRID"])){
            $OnlyCSRID=trim($_POST["OnlyCSRID"]);
            if(strlen($OnlyCSRID)>3){
               $sql="SELECT ID,`privkey`,`Squidkey`,`UsePrivKeyCrt` FROM sslcertificates WHERE CommonName='$OnlyCSRID'";
                $ligne=$q->mysqli_fetch_array($sql);
                $ID=intval($ligne["ID"]);
                if($ID==0){echo $tpl->post_error("$OnlyCSRID: {source_package_not_found}");return false;}
                $field="privkey";
                if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
                if($ligne["UsePrivKeyCrt"]==0){
                    $field="Squidkey";
                    if(strlen($ligne[$field])<10){
                        if(strlen($ligne["privkey"])>10){$field="privkey";}
                    }

                }
                $ligne[$field]=str_replace("\\n", "\n", $ligne[$field]);
                if(strlen($ligne[$field])<10){
                    echo $tpl->post_error("$field {no_data}");
                    return false;
                }

                $data=$sock->REST_API("/certificate/csr/$CommonName/$gpid");
                $json=json_decode($data);
                if (json_last_error()> JSON_ERROR_NONE) {
                    echo $tpl->post_error("Decoding: ".strlen($data)." bytes<hr>".json_last_error_msg());
                    return false;
                }
                if(!$json->Status){
                    echo $tpl->post_error($json->Error);
                    return false;
                }

                return true;

            }
        }
   }



foreach ($_POST as $num=>$vl){
        $_POST[$num]=$q->sqlite_escape_string2($vl);
    }
    if($_POST["CertificateMaxDays"]==0){$_POST["CertificateMaxDays"]=730;}
    $fields[]="CountryName"	;
    $fields[]="stateOrProvinceName"	;
    $fields[]="CertificateMaxDays"	;
    $fields[]="OrganizationName"	;
    $fields[]="OrganizationalUnit"	;
    $fields[]="emailAddress"	;
    $fields[]="localityName"	;
    $fields[]="levelenc"	;
    $fields[]="password"	;
    $fields[]="CommonName"	;
    $fields[]="domains"	;

    $ligne=$q->mysqli_fetch_array("SELECT CommonName FROM `sslcertificates` WHERE `CommonName`='$CommonName'");
    if(trim($ligne["CommonName"])<>null){
        echo $tpl->post_error("$CommonName: {alreadyexists}");
        return false;
    }

    if(!$q->FIELD_EXISTS("sslcertificates","domains")){
        $q->QUERY_SQL("ALTER TABLE sslcertificates ADD domains TEXT NOT NULL DEFAULT ''");
    }

    if(!isset($_POST["password"])){$_POST["password"]="";}

    $values[]="'{$_POST["CountryName"]}'";
    $values[]="'{$_POST["stateOrProvinceName"]}'";
    $values[]="'{$_POST["CertificateMaxDays"]}'";
    $values[]="'{$_POST["OrganizationName"]}'";
    $values[]="'{$_POST["OrganizationalUnit"]}'";
    $values[]="'{$_POST["emailAddress"]}'";
    $values[]="'{$_POST["localityName"]}'";
    $values[]="'{$_POST["levelenc"]}'";
    $values[]="'{$_POST["password"]}'";
    $values[]="'{$_POST["CommonName"]}'";
    $values[]="'{$_POST["domains"]}'";
    $sql="INSERT INTO `sslcertificates` (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
    $q->QUERY_SQL($sql);

    if(!$q->ok){echo $tpl->javascript_parse_text("jserror:$q->mysql_error\n$sql");return false;}



   $data=$sock->REST_API("/certificate/csr/$CommonName/$gpid");
   $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->post_error("Decoding: ".strlen($data)." bytes<hr>".json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        echo $tpl->post_error($CommonName."<hr>".$json->Error);
        return false;
    }

    return true;

}



function certificate_info_crt_popup($return=false){
    $CommonName=$_GET["certificate-info"];
    $q=new lib_sqlite(GetDatabase());

    $sql="SELECT `crt`,`CertPassword`,`SquidCert`,`UsePrivKeyCrt`,`UseGodaddy`  FROM sslcertificates WHERE CommonName='$CommonName'";
    $ligne=$q->mysqli_fetch_array($sql);

    $field="crt";
    if($ligne["UsePrivKeyCrt"]==0){$field="SquidCert";}
    if($ligne["UseGodaddy"]==1){$field="crt";}


    $passin=null;
    if($ligne["CertPassword"]<>null){
        $passin=" -passin pass:{$ligne["CertPassword"]}";
    }

    $ligne[$field]=str_replace("\\n", "\n", $ligne[$field]);
    @mkdir("/usr/share/artica-postfix/ressources/conf/upload",0755,true);
    $filepath=dirname(__FILE__)."/ressources/conf/upload/Cert.pem";
    @file_put_contents($filepath, $ligne[$field]);
    exec("/usr/bin/openssl x509 -text$passin -in $filepath 2>&1",$results);

    foreach ($results as $num=>$ligne){
        $ligne=trim($ligne);
        $tt[]=$ligne;
    }

    if($return){return $tt;}

    echo "<textarea
		style='margin-top:5px;font-family:Courier New,serif;
		font-weight:bold;border:5px solid #8E8E8E;
		overflow:auto;font-size:12px !important;width:99%;height:390px'>".@implode("\n", $tt)."</textarea>";

}

function certificate_extract_crt($CommonName,$return=false){
    $q=new lib_sqlite(GetDatabase());


    $sql="SELECT *  FROM sslcertificates WHERE CommonName='$CommonName'";
    $ligne=$q->mysqli_fetch_array($sql);
    $UsePrivKeyCrt=intval($ligne["UsePrivKeyCrt"]);

    $Certfield="crt";
    if($UsePrivKeyCrt==0){return;}



    $sock=new sockets();
    $tpl=new templates();

    $ADDF=array();

    $ligne[$Certfield]=str_replace("\\n", "\n", $ligne[$Certfield]);

    $filepath=dirname(__FILE__)."/ressources/conf/upload/Cert.pem";
    @file_put_contents($filepath, $ligne[$Certfield]);
    $cmd="/usr/bin/openssl x509 -text -in $filepath 2>&1";

    exec($cmd,$results);
    $OU=null;
    $CN=null;
    $C=null;
    $ST=null;
    $L=null;
    $O=null;
    $levelenc=0;
    foreach ($results as $ligne){

        if(preg_match("#Subject:\s+(.+)#", $ligne,$re)){
            $XLINE=$re[1];
            $XLINES=explode(",",$XLINE);
            foreach ($XLINES as $b){
                if(preg_match("#(.+?)=(.+)#", $b,$re)){
                    $key=strtoupper(trim($re[1]));
                    $value=trim($re[2]);
                    if($key=="OU"){$OU=$value;}
                    if($key=="CN"){$CN=$value;}
                }
            }
            continue;
        }

        if(preg_match("#Issuer:\s+(.+)#", $ligne,$re)){
            $XLINE=$re[1];
            $XLINES=explode(",",$XLINE);
            foreach ($XLINES as $b){
                if(preg_match("#(.+?)=(.+)#", $b,$re)){
                    $key=strtoupper(trim($re[1]));
                    $value=trim($re[2]);
                    if($key=="C"){$C=$value;}
                    if($key=="ST"){$ST=$value;}
                    if($key=="L"){$L=$value;}
                    if($key=="O"){$O=$value;}
                }
            }
            continue;
        }


        if(preg_match("#Not Before.*?:(.+)#",$ligne,$re)){
            $Date1=strtotime($re[1]);
            $DateFrom=date("Y-m-d",$Date1);

            $ADDF[]="`DateFrom`='".mysql_escape_string2($DateFrom)."'";
            continue;

        }
        if(preg_match("#Not After.*?:(.+)#",$ligne,$re)){
            $Date1=strtotime($re[1]);
            $DateTo=date("Y-m-d",$Date1);
            $ADDF[]="`DateTo`='".mysql_escape_string2($DateTo)."'";
            continue;

        }

        if(preg_match("#DNS:(.+?)(,|$)#i",$ligne,$re)){
            if(preg_match("#Domain Control Validated#i", $OU)){$OU=null;}
            VERBOSE("FOUND {$re[1]} in <code>$ligne</code>", __LINE__);
            VERBOSE("OU is '<code>$OU</code>'", __LINE__);
            if($OU==null){
                $re[1]=str_replace("*.", "", $re[1]);
                $OU=$re[1];
            }
            continue;
        }


        if(preg_match("#Public-Key:.*?([0-9]+)\s+bit#", $ligne,$re)){
            $levelenc=$re[1];
            continue;
        }
    }


    if($C<>null){
        $ADDF[]="`CountryName`='".mysql_escape_string2($C)."'";
    }
    if($ST<>null){
        $ADDF[]="`stateOrProvinceName`='".mysql_escape_string2($ST)."'";
    }
    if($L<>null){
        $ADDF[]="`localityName`='".mysql_escape_string2($L)."'";
    }
    if($O<>null){
        $ADDF[]="`OrganizationName`='".mysql_escape_string2($O)."'";
    }
    if($OU<>null){
        $ADDF[]="`OrganizationalUnit`='".mysql_escape_string2($OU)."'";
    }

    if($levelenc>0){
        $ADDF[]="`levelenc`='".mysql_escape_string2($levelenc)."'";
    }



    if(count($ADDF)>0){

        $sql="UPDATE sslcertificates SET ".@implode(",", $ADDF)." WHERE `CommonName`='$CommonName'";
        VERBOSE($sql, __LINE__);
        $q=new lib_sqlite(GetDatabase());

        $q->QUERY_SQL($sql);
        if(!$q->ok){
            if($return){return $q->mysql_error;}
            echo $q->mysql_error;return;}

    }

}

function analyze_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $return=certificate_extract_crt($_GET["analyze-js"],true);
    if(strlen($return)){
        $tpl->js_error($return);
        return;
    }

    echo "LoadAjax('table-loader-sslcerts-service','$page?table=yes');";
}

function getdb(){

    $content_type="application/x-sqlite3";
    header('Content-type: '.$content_type);
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"certificates-center.db\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    $fsize = filesize(GetDatabase());
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile(GetDatabase());


    //"
}

function download_pfx(){
    $ID=intval($_GET["download-pfx"]);
    $q=new lib_sqlite(GetDatabase());
    $return=null;
    $sql="SELECT CommonName,pks12 FROM sslcertificates WHERE ID='$ID'";
    $ligne=$q->mysqli_fetch_array($sql);
    $CommonName=$ligne["CommonName"];
    $final_content=base64_decode($ligne["pks12"]);

    $fsize=strlen($final_content);

    $content_type=" application/x-pkcs12";
    header('Content-type: '.$content_type);
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$CommonName.pfx\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    echo $final_content;
}

function download_certificate(){

    $ID=intval($_GET["download-cert"]);
    $q=new lib_sqlite(GetDatabase());
    $return=null;


    $sql="SELECT * FROM sslcertificates WHERE ID='$ID'";
    $ligne=$q->mysqli_fetch_array($sql);

    $CommonName=$ligne["CommonName"];
    $field="privkey";
    $field_cert="crt";
    if(!$q->ok){echo $q->mysql_error_html(true);}
    if(intval($ligne["UsePrivKeyCrt"])==0){
        $field="Squidkey";
        $field_cert="SquidCert";

        if(strlen($ligne[$field])<10){
            if(strlen($ligne["privkey"])>10){$field="privkey";}
        }

    }

    $ligne[$field]=str_replace("\\n", "\n", $ligne[$field]);
    $private_key_content=trim($ligne[$field]);
    $ligne[$field_cert]=str_replace("\\n", "\n", $ligne[$field_cert]);
    $certificate_content=trim($ligne[$field_cert]);

    $final_content=$certificate_content."\n$private_key_content\n";

    $fsize=strlen($final_content);

    $content_type="application/x-pem-file";
    header('Content-type: '.$content_type);
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$CommonName.pem\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    echo $final_content;


}


function export_single_certificate(){
    $CommonName=$_GET["download"];
    $q=new lib_sqlite(GetDatabase());
    $sql="SELECT *  FROM sslcertificates WHERE CommonName='$CommonName'";
    $ligne=$q->mysqli_fetch_array($sql);
    $data=base64_encode(serialize($ligne));
    $size=strlen($data);
    $attchname=$CommonName;
    $attchname=str_replace("*","wilcard",$attchname);

    header('Content-type: application/octet-stream');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$attchname.ccc\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$size);
    ob_clean();
    flush();
    echo $data;

}