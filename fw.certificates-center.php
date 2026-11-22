<?php
// Service Pack 9 2026-10-10
# SP206
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// cert_view_*() : vue en trois onglets d'un certificat, partagee avec fw.nginx.AppClientMTLS.php
include_once(dirname(__FILE__)."/ressources/class.certificates.view.inc");
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
if(isset($_GET["certificate-view-overview"])){certcenter_view_overview();exit;}
if(isset($_GET["certificate-view-key"])){certcenter_view_key();exit;}
if(isset($_GET["certificate-view-raw"])){certcenter_view_raw();exit;}
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



if(isset($_GET["download"])){export_single_certificate();exit;}
if(isset($_GET["download-cert"])){download_certificate();exit;}
if(isset($_GET["download-pfx"])){download_pfx();exit;}
if(isset($_GET["generate-x509-js"])){certificate_generate_js();exit;}

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
        return $tpl->js_error(crt_meta_error($json->Error));
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
        return $tpl->js_error(crt_meta_error($json->Error));
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
        return $tpl->js_error(crt_meta_error($json->Error));
    }
    echo "$function();";
    return true;
}

function change_cert_js(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb        = new CertificatesDB();
    $ID         = intval($_GET["change-cert-js"]);
    $row        = $cdb->getById($ID);
    $CommonName = is_object($row) ? $row->CommonName : "";
    $tpl->js_dialog2("$CommonName","$page?change-cert-popup=$ID");

}
function change_cert_popup(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb        = new CertificatesDB();
    $ID         = intval($_GET["change-cert-popup"]);
    $row        = $cdb->getById($ID);
    if($cdb->lastError !== ""){echo $tpl->div_error($cdb->lastError);}
    $CommonName = is_object($row) ? $row->CommonName : "";
    $jsafter="dialogInstance1.close();dialogInstance2.close();LoadAjax('table-loader-sslcerts-service','$page?table=yes');";
    $tpl->field_hidden("ChangeCert",$ID);
    $form[]=$tpl->field_text("CommonName","{commonName}",$CommonName);
    $html=$tpl->form_outside($CommonName, @implode("\n", $form),null,"{edit}",$jsafter,"AsCertifsManager");
    echo $html;
}
function change_cert_save(){
    $tpl        = new template_admin();
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb        = new CertificatesDB();
    $ID         = intval($_POST["ChangeCert"]);
    $CommonName = trim($_POST["CommonName"]);
    if($CommonName==null){echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{your_query_is_empty}"));return false;}
    $CommonName = str_replace("'","_",$CommonName);
    $CommonName = replace_accents($CommonName);
    if(!$cdb->update($ID, ["CommonName"=>$CommonName])){
        echo (new template_admin())->post_error(crt_meta_error($cdb->lastError));
        return false;
    }
    return true;
}
//dialogInstance$number.close();

function subcertificates_pks12():bool{
    $ID=intval($_GET["subcertificates-pks12"]);
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb=new CertificatesDB();
    $return=null;
    $row=$cdb->subGetById($ID);
    $ligne=is_object($row) ? (array)$row : array("commonName"=>"","pks12"=>"");
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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb=new CertificatesDB();
    $row=$cdb->subGetById($ID);
    if($cdb->lastError !== ""){echo $tpl->div_error($cdb->lastError);}
    $ligne=is_object($row) ? (array)$row : array("crt"=>"","commonName"=>"");
    $certificateData=base64_decode($ligne["crt"]);
    $commonName=$ligne["commonName"];
    $form[]=$tpl->field_textarea("csr", null,$certificateData,"664px");
    echo $tpl->form_outside($commonName, @implode("\n", $form),"{public_key_ssl_explain}",null,null);
}
function subcertificates_privkey(){
    $tpl = new template_admin();
    $ID = intval($_GET["subcertificates-privkey"]);
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb=new CertificatesDB();
    $row=$cdb->subGetById($ID);
    if($cdb->lastError !== ""){echo $tpl->div_error($cdb->lastError);}
    $ligne=is_object($row) ? (array)$row : array("certid"=>0,"srca"=>"","commonName"=>"");
    $certid=intval($ligne["certid"]);
    $certificateData=base64_decode($ligne["srca"]);

    $commonName=$ligne["commonName"];
    $form[]=$tpl->field_textarea("csr", null,$certificateData,"664px");
    echo $tpl->form_outside($commonName, @implode("\n", $form),"{public_key_ssl_explain}",null,null);

}

function subcertificates_verify(){
    $tpl = new template_admin();
    $ID=intval($_GET["subcertificates-verify"]);
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb=new CertificatesDB();
    $row=$cdb->subGetById($ID);
    if($cdb->lastError !== ""){echo $tpl->div_error($cdb->lastError);}
    $ligne=is_object($row) ? (array)$row : array("certid"=>0,"crt"=>"","srca"=>"");
    $certid=$ligne["certid"];

    $CertificatePath=PROGRESS_DIR."/certificate.pem";
    $KeyPath=PROGRESS_DIR."/cakey.pem";
    $crtdata=base64_decode($ligne["crt"]);
    @file_put_contents($CertificatePath,$crtdata);

    $rowMaster=$cdb->getById(intval($certid));
    $ligne=is_object($rowMaster) ? (array)$rowMaster : array("crt"=>"");
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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    $row = $cdb->getById(intval($Masterid));
    if(!is_object($row)){return array();}
    return (array)$row;
}

function subcertificates_settings(){
    $tpl = new template_admin();
    $page = CurrentPageName();
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    $ID = intval($_GET["subcertificates-settings"]);
    $row = $cdb->subGetById($ID);
    $ligne = is_object($row) ? (array)$row : array();
    $Masterid=$ligne["certid"] ?? 0;
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
        // Pentest 2026-11-04 : HarmpID etait recopie sans encodage dans la page (XSS).
        $Addon="&HarmpID=".intval($_GET["HarmpID"]);
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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    $row = $cdb->subGetById($ID);
    if($cdb->lastError !== ""){
        $tpl->js_mysql_alert($cdb->lastError);
        return;
    }
    $ligne = is_object($row) ? (array)$row : array("commonName"=>"","organizationName"=>"","emailAddress"=>"");
    $commonName=$ligne["commonName"];
    $organizationName=$ligne["organizationName"];
    $emailAddress=$ligne["emailAddress"];
    $tpl->js_confirm_delete("$commonName, $organizationName $emailAddress ($ID)","del-server-certificate",$ID,"$('#$md').remove()");

}
function subcertificates_delete():bool{
    $ID=intval($_POST["del-server-certificate"]);
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    if(!$cdb->subDelete($ID)){echo $cdb->lastError."\n";return false;}
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
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
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

    if(count($topbuttons)==0){return true;}
    echo $tpl->table_buttons($topbuttons);   // déjà traduit
    return true;
}
function subcertificates_new_client_popup(){
    if(!fw_progress_page_priv("AsCertifsManager")){return false;}   // task link signed for this session: built only for whom may run it
    $tpl=new template_admin();
    $certid=fw_progress_seg($_GET["certid"] ?? "");
    if($certid===""){return fw_progress_bad_value("certid");}
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
        include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
        $cdb = new CertificatesDB();
        VERBOSE("Get CommonName,CountryName, localityName,OrganizationName,OrganizationalUnit From $certid",__LINE__);
        $row = $cdb->getById(intval($certid));
        $ligne = is_object($row) ? (array)$row : array();

        $field="crt";
        if(($ligne["UsePrivKeyCrt"] ?? 0)==0){$field="SquidCert";}
        $crtdata=$ligne[$field] ?? "";
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

        if ($cdb->lastError !== "") {
            VERBOSE($cdb->lastError,__LINE__);
            echo $tpl->div_error($cdb->lastError);
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
        $form[]=$tpl->field_array_hash($array_country_codes,"CountryName", "nonull:{countryName}", $array_country_codes2[$ligne["CountryName"]] ?? $ligne["CountryName"]);
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
    // Pentest 2026-11-04 : certid etait recopie sans encodage dans la page (XSS).
    $certid=intval($_GET["certid"]);
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
        include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
        $cdb = new CertificatesDB();
        $row = $cdb->getById(intval($certid));
        $ligne = is_object($row) ? (array)$row : array();
        if ($cdb->lastError !== "") {
            echo $tpl->div_error($cdb->lastError);
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
    $form[]=$tpl->field_array_hash($array_country_codes,"CountryName", "nonull:{countryName}", $array_country_codes2[$ligne["CountryName"]] ?? $ligne["CountryName"]);
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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb=new CertificatesDB();
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
    $rawResults=$cdb->subList(intval($certid));
    $results=array();
    foreach($rawResults as $r){ $results[]=(array)$r; }
    usort($results,function($a,$b){
        $ca=intval($a["Certype"] ?? 0); $cb=intval($b["Certype"] ?? 0);
        if($ca!==$cb){return $ca<=>$cb;}
        return strcmp((string)($a["commonName"] ?? ""),(string)($b["commonName"] ?? ""));
    });

    $rows=array();
    foreach ($results as $index=>$ligne) {
        $md=md5(serialize($ligne));

        $Certype=$ligne["Certype"];
        $ID=$ligne["ID"];
        $commonName=$ligne["commonName"];
        $organizationName=$ligne["organizationName"];
        $emailAddress=$ligne["emailAddress"];
        $badge="<span class='awc-badge awc-badge-off'>{client_certificate}</span>";
        $DateTo=$ligne["DateTo"];
        $err="";
        $failed=false;
        $commonName=$tpl->td_href($commonName,null,"Loadjs('$page?show-server-certificate=$ID&md=$md')");
        if($Certype==1){
            $badge="<span class='awc-badge awc-badge-on'>{server_certificate}</span>";
            $data=$sock->REST_API("/certificate/server/$ID/$gpid");
            $json=json_decode($data);
            if(!is_object($json) OR !$json->Status){
                $failed=true;
                $badge="<span class='awc-badge' style='background:#ba1a1a'>{server_certificate}</span>";
                $errMsg=is_object($json)?strval($json->Error):"{error}";
                $err="<span style='display:block;font-size:12px;color:#ba1a1a;margin-top:2px'>"
                    .htmlspecialchars($errMsg)."</span>";
            }
        }
        $del=crt_tip($tpl->icon_delete("Loadjs('$page?del-server-certificate=$ID&md=$md')","AsCertifsManager"),"{crt_tip_delete_server}");
        $download="";
        $DateToT=$tpl->time_to_date($DateTo);
        if( strlen(strval($ligne["pks12"]))>50) {
            $download = crt_tip($tpl->icon_download("document.location.href='$page?subcertificates-pks12=$ID'", "AsCertifsManager"),"{crt_tip_pfx_download}");
        }

        $meta=array();
        if(strlen(trim(strval($organizationName)))>0){$meta[]=htmlspecialchars($organizationName);}
        if(strlen(trim(strval($emailAddress)))>0){$meta[]=htmlspecialchars($emailAddress);}

        $h ="<tr>";
        $h.="<td>".crt_ident(ico_certificate,$commonName,$meta,$failed).$err."</td>";
        $h.="<td class='center'>$badge</td>";
        $h.="<td class='awc-mono' nowrap>$DateToT</td>";
        $h.="<td>".crt_acts(array($download,$del))."</td>";
        $h.="</tr>";
        $rows[]=$h;
    }

    $html[]=crt_table_open(array(
        "<th>{CommonName}</th>",
        "<th class='center'>{type}</th>",
        "<th>{expire}</th>",
        "<th style='text-align:right'>{actions}</th>"),"crt-rows-sub");
    if(count($rows)==0){
        $html[]=crt_table_empty(4);
    }else{
        $html[]=@implode("\n",$rows);
    }
    $totalSub=count($rows);
    $html[]=crt_table_close($totalSub,$totalSub,"{certificates}","crt-pager-sub");

    $html[]="</div>";
    $html[]="<script>$btns</script>";
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body($html)
        .awc_pager_js(25,"crt-rows-sub","crt-pager-sub");
    return true;
}

/* Socle des tableaux d'entites: le cadre, la carte et le pager viennent de
 * awc_table_style() / awc_pager_js() (class.statistics_appliance.inc, partages). */

/** En-tete + ouverture du tableau. */
function crt_table_open($columns,$rowsId):string{
    $h=array();
    $h[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $h[]="<table class='awc-tbl'><thead><tr>";
    foreach($columns as $col){ $h[]=$col; }
    $h[]="</tr></thead><tbody id='$rowsId'>";
    return @implode("\n",$h);
}

/** Pied du tableau: compteur a gauche, pager a droite. */
function crt_table_close($shown,$total,$unit,$pagerId):string{
    $h=array();
    $h[]="</tbody></table></div>";
    $h[]="<div class='awc-foot'>";
    $h[]="<span>{showing} $shown / $total $unit</span>";
    $h[]="<span class='awc-pg' id='$pagerId'></span>";
    $h[]="</div></div></div>";
    return @implode("\n",$h);
}

/** Ligne vide normalisee (jamais un <tbody> vide). */
function crt_table_empty($colspan):string{
    return "<tr><td colspan='$colspan'><div class='awc-empty'>{no_data}</div></td></tr>";
}

/** Bloc d'identite: pastille d'icone, nom (deja rendu, il porte son lien), metadonnees. */
function crt_ident($ico,$name,$meta=array(),$off=false):string{
    $icocls=$off?"awc-ico awc-ico-off":"awc-ico";
    $namecls=$off?"awc-name awc-name-off":"awc-name";
    $h ="<div class='awc-ident'>";
    $h.="<span class='$icocls'><i class='$ico'></i></span>";
    $h.="<span><span class='$namecls'>$name</span>";
    $out=array();
    foreach($meta as $m){
        if(is_null($m)){continue;}
        if(strlen(trim(strip_tags(strval($m))))==0){continue;}
        $out[]=$m;
    }
    if(count($out)>0){
        $h.="<div class='awc-meta'>".@implode(" &middot; ",$out)."</div>";
    }
    $h.="</span></div>";
    return $h;
}

/** Cellule d'actions alignee a droite. */
function crt_acts($items):string{
    if(!is_array($items)){$items=array($items);}
    $out=array();
    foreach($items as $i){
        if(is_null($i)){continue;}
        if(strlen(trim($i))==0){continue;}
        $out[]=$i;
    }
    return "<div class='awc-acts'>".@implode("",$out)."</div>";
}

// Wraps a row action with a hover tooltip explaining what it does.
// The $token is resolved later by _ENGINE_parse_body(); double quotes keep
// translations containing an apostrophe from cutting the attribute.
function crt_tip($button,$token):string{
    if(is_null($button)){return "";}
    if(strlen(trim($button))==0){return "";}
    return "<span class='crt-tip' title=\"$token\">$button</span>";
}

function certificate_new_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $add=null;
    $function=$_GET["function"];
    if(isset($_GET["OnlyCSR"])){$add="&OnlyCSR=yes";}
    return $tpl->js_dialog1("{new_certificate}", "$page?new-certificate-popup=yes&function=$function$add");
}














function delete_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    // Pentest 2026-11-04 : delete-js et id etaient recopies sans encodage dans la page (XSS).
    $Certificate=(string)$_GET["delete-js"];
    $domid=preg_replace('#[^A-Za-z0-9_-]#','',(string)($_GET["id"] ?? ""));
    $js="$('#$domid').remove();";
    $tpl->js_confirm_delete(req_html($Certificate), "delete", $Certificate,$js);

}
function delete(){
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    [$exists,$certid] = $cdb->exists($_POST["delete"]);
    if(!$exists){return;}
    if(!$cdb->delete(intval($certid))){
        echo (new template_admin())->post_error(crt_meta_error($cdb->lastError));
    }
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
    // Pentest 2026-11-04 : certificate-js etait recopie sans encodage dans la page (XSS).
    $CertificateT=req_html(req_html($CertificateGet));
    return $tpl->js_dialog1("{certificate} $CertificateT", "$page?certificate-popup=$certificate&function=$function&certificate=$certificate",950);
}
function subcertificates_tabs(){
    $ID=intval($_GET["subcertificates-tab"]);
    $tpl=new template_admin();
    $page=CurrentPageName();

    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    $row = $cdb->subGetById($ID);
    if(!is_object($row)){
        $tpl->js_mysql_alert($cdb->lastError);
        return;
    }
    $ligne = (array)$row;
    $commonName=$ligne["commonName"];


    $array[$commonName]="$page?subcertificates-settings=$ID";
    $array["{certificate}"]="$page?subcertificates-crt=$ID";
    $array["{privkey}"]="$page?subcertificates-privkey=$ID";
    $array["Info."]="$page?subcertificates-info=$ID";
    echo $tpl->tabs_default($array);

}

function subcertificates_info(){
    $ID=intval($_GET["subcertificates-info"]);
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    $row = $cdb->subGetById($ID);
    $ligne = is_object($row) ? (array)$row : array();

    $crt=base64_decode($ligne["crt"] ?? "");


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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    $row = $cdb->getByCommonName($_GET["certificate-popup"]);
    $ligne = is_object($row) ? (array)$row : array();

    $UseLetsEncrypt=intval($ligne["UseLetsEncrypt"] ?? 0);
    $UsePrivKeyCrt=intval($ligne["UsePrivKeyCrt"] ?? 0);
    $AsRoot=intval($ligne["AsRoot"] ?? 0);
    $ID=intval($ligne["ID"] ?? 0);

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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    // Pentest 2026-11-04 : la page Let's Encrypt executait le JavaScript recu en
    // base64 dans function2 ; il est maintenant signe pour la session.
    $refreshEncoded=signed_html_token("LoadAjax('certificate-flat-$ID','$page?certificate-flat2=$ID&function=$function')");

    $row = $cdb->getById($ID);
    if(!is_object($row)){
        echo $tpl->div_error($cdb->lastError."<br>Please restart the REST API service..");
    }
    $ligne = is_object($row) ? (array)$row : array();
    // Held by Artica Meta: the stored PEM is shown, never edited here (the daemon answers 423).
    $metaHeld=crt_meta_held($ligne);
    if($metaHeld){
        $tpl->table_form_field_text("{ccmeta_badge}","{ccmeta_held_short}",ico_lock);
    }

    $TextType="{official_certificate}";
    $UsePrivKeyCrt=intval($ligne["UsePrivKeyCrt"]);
    $ServerCert=intval($ligne["ServerCert"]);
    $UseLetsEncrypt=intval($ligne["UseLetsEncrypt"]);
    $CABundleProvider=base64_decode($ligne["CABundleProvider"]);
    $TypeAddon="";
    if($UseLetsEncrypt==1 && !$metaHeld){
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
            crt_meta_field_js($tpl,$metaHeld,"Loadjs('$page?csr-js=$ID')", "AsCertifsManager");
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
        crt_meta_field_js($tpl,$metaHeld,"Loadjs('$page?crt-js=$ID&function=$function')","AsCertifsManager");
        $tpl->table_form_field_text("{certificate}",$csr_text,ico_certificate,$Error);
        crt_meta_field_js($tpl,$metaHeld,"Loadjs('$page?privkey-js=$ID&function=$function')","AsCertifsManager");
        $tpl->table_form_field_text("{privkey}",$key_text,ico_certificate,$Error);

    }else{
        if (strlen($crt)<50) {
            crt_meta_field_js($tpl,$metaHeld,"Loadjs('$page?crt-js=$ID&function=$function')","AsCertifsManager");
            $tpl->table_form_field_text("{certificate}", "{missing}", ico_certificate, true);
        }else{
            $StrlenOfCrt=strlen($crt);
            crt_meta_field_js($tpl,$metaHeld,"Loadjs('$page?crt-js=$ID&function=$function')","AsCertifsManager");
            $tpl->table_form_field_text("{certificate}", "[OK] $StrlenOfCrt bytes", ico_certificate);
        }

        if (strlen($privkey)<50) {
            if($ServerCert==0) {
                crt_meta_field_js($tpl,$metaHeld,"Loadjs('$page?privkey-js=$ID&function=$function')", "AsCertifsManager");
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
            crt_meta_field_js($tpl,$metaHeld,"Loadjs('$page?srca-js=$ID&function=$function')", "AsCertifsManager");
            $tpl->table_form_field_text("{ROOT_CERT}", $srca_text, ico_certificate, $Error);

        } else {
            if($UseLetsEncrypt==0) {
                crt_meta_field_js($tpl,$metaHeld,"Loadjs('$page?srca-js=$ID&function=$function')", "AsCertifsManager");
                $tpl->table_form_field_text("{ROOT_CERT}", "{none}", ico_certificate, false);
            }
        }
        if (strlen($bundle) > 50) {
            $csr_text = "[OK] " . strlen($bundle) . " bytes";
            crt_meta_field_js($tpl,$metaHeld,"Loadjs('$page?bundle-js=$ID&function=$function')", "AsCertifsManager");
            $tpl->table_form_field_text("{certificate_chain}", $csr_text, ico_certificate);
        } else {
            crt_meta_field_js($tpl,$metaHeld,"Loadjs('$page?bundle-js=$ID&function=$function')", "AsCertifsManager");
            $tpl->table_form_field_text("{certificate_chain}", "{none}", ico_certificate);
        }




    }
    if($UsePrivKeyCrt==1) {
        crt_meta_field_js($tpl,$metaHeld,"Loadjs('$page?CABundleProvider-js=$ID&function=$function')", "AsCertifsManager");
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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    $function=$_GET["function"];
    $tt=time();
    $return=null;
    $CommonName=$_GET["certificate-crt"];
    $ID=intval($_GET["ID"]);
    $CommonNameURL=urlencode($CommonName);
    $button_save="{apply}";
    if($ID>0){
        $row = $cdb->getById($ID);
    }else{
        $row = $cdb->getByCommonName($CommonName);
    }
    $ligne = is_object($row) ? (array)$row : array();

    $field="crt";
    if(($ligne["UsePrivKeyCrt"] ?? 0)==0){
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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    $row = $cdb->getByCommonName($CommonName);
    $ligne = is_object($row) ? (array)$row : array();

    $field="crt";
    if(($ligne["UsePrivKeyCrt"] ?? 0)==0){$field="SquidCert";}
    if(($ligne["UseGodaddy"] ?? 0)==1){$field="crt";}

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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    $tt=time();
    $return=null;
    $CommonName=$_GET["certificate-srca"];
    $CommonNameURL=urlencode($CommonName);
    $function=$_GET["function"];
    $button_save="{apply}";
    $ID=intval($_GET["ID"]);

    if($ID>0){
        $row = $cdb->getById($ID);
    }else{
        $row = $cdb->getByCommonName($CommonName);
    }
    $ligne = is_object($row) ? (array)$row : array();
    $field="srca";
    if(!is_object($row) && $cdb->lastError!==""){echo $tpl->div_error(htmlspecialchars($cdb->lastError));}
    if(($ligne["UsePrivKeyCrt"] ?? 0)==0){$button_save=null;}

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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    $tpl=new template_admin();
    $CommonName=$_POST["SAVE_CONTENT"];
    $ID=intval($_POST["ID"]);
    $field=$_POST["field"];
    $content=url_decode_special_tool($_POST["content"]);
    if($ID<=0){
        [$exists,$existsId] = $cdb->exists($CommonName);
        if(!$exists){echo $tpl->div_error("certificate $CommonName not found");return;}
        $ID = $existsId;
    }
    if(!$cdb->update(intval($ID), array($field=>$content))){
        echo $tpl->div_error(crt_meta_error_html($cdb->lastError));
        return;
    }
    certificate_extract_crt($CommonName);
}

function certificate_privkey():bool{
    $page=CurrentPageName();
    $CommonName=$_GET["certificate-privkey"];
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    $ID=intval($_GET["ID"]);
    if($ID==0) {
        [$exists,$existsId] = $cdb->exists($CommonName);
        $ID = intval($existsId);
    }
    echo "<div id='DIVKEY{$ID}'></div><script>LoadAjax('DIVKEY{$ID}','$page?certificate-privkey2=$ID');</script>";
    return true;
}

function certificate_privkey2():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    $tt=time();
    $return=null;
    $ID=intval($_GET["certificate-privkey2"]);
    $button_save="{apply}";
    VERBOSE("getById($ID)",__LINE__);
    $row = $cdb->getById($ID);
    $ligne = is_object($row) ? (array)$row : array();
    $CommonName=$ligne["CommonName"] ?? "";
    $ID=intval($ligne["ID"] ?? $ID);
    $field="privkey";
    if(!is_object($row) && $cdb->lastError!==""){echo $tpl->div_error(htmlspecialchars($cdb->lastError));}
    if(($ligne["UsePrivKeyCrt"] ?? 0)==0){
        $button_save=null;
        $field="Squidkey";
        if(strlen($ligne[$field] ?? "")<10){
            if(strlen($ligne["privkey"] ?? "")>10){$field="privkey";}
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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    $tt=time();
    $return="";
    $CommonName=$_GET["CABundleProvider"];
    $function=$_GET["function"];
    $button_save="{apply}";
    $ID=intval($_GET["ID"]);
    if($ID>0){
        $row = $cdb->getById($ID);
    }else{
        $row = $cdb->getByCommonName($CommonName);
    }
    $ligne = is_object($row) ? (array)$row : array();
    if(!is_object($row) && $cdb->lastError!==""){echo $tpl->div_error(htmlspecialchars($cdb->lastError));}

    $CABundleProvider=str_replace("\\n", "\n", base64_decode($ligne["CABundleProvider"] ?? ""));
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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    $tpl=new template_admin();
    $CommonName=$_POST["SAVE_CABUNDLE"];
    $ID=intval($_POST["ID"]);
    $content=url_decode_special_tool($_POST["content"]);
    $content=base64_encode($content);
    if($ID<=0){
        [$exists,$existsId] = $cdb->exists($CommonName);
        if(!$exists){echo $tpl->div_error("certificate $CommonName not found");return false;}
        $ID = intval($existsId);
    }
    if(!$cdb->update($ID, array("CABundleProvider"=>$content))){
        echo $tpl->div_error(crt_meta_error_html($cdb->lastError));
        return false;
    }
    return admin_tracks("Save Certificate CA Bundle Provider for $CommonName/$ID");
}
function certificate_bundle():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    $tt=time();
    $return="";
    $CommonName=$_GET["certificate-bundle"];
    $CommonNameURL=urlencode($CommonName);
    $function=$_GET["function"];
    $button_save="{apply}";
    $ID=intval($_GET["ID"]);
    if($ID>0){
        $row = $cdb->getById($ID);
    }else{
        $row = $cdb->getByCommonName($CommonName);
    }
    $ligne = is_object($row) ? (array)$row : array();
    if(!is_object($row) && $cdb->lastError!==""){echo $tpl->div_error(htmlspecialchars($cdb->lastError));}

    $ligne["bundle"]=str_replace("\\n", "\n", $ligne["bundle"] ?? "");
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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    $row = $cdb->getByCommonName($CommonName);
    $ligne = is_object($row) ? (array)$row : array();

    $certificate_path=dirname(__FILE__)."/ressources/conf/upload/Cert.pem";
    $main_path=dirname(__FILE__)."/ressources/conf/upload";
    $CAFile=dirname(__FILE__)."/ressources/conf/upload/CaFile.pem";

    $ligne["srca"]=str_replace("\\n", "\n", $ligne["srca"] ?? "");
    $ligne["bundle"]=str_replace("\\n", "\n", $ligne["bundle"] ?? "");
    $ligne["crt"]=str_replace("\\n", "\n", $ligne["crt"] ?? "");

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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    $row = $cdb->getByCommonName($CommonName);
    $ligne = is_object($row) ? (array)$row : array();



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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    $t=$_GET["t"];
    $row = $cdb->getByCommonName($CommonName);
    if(!is_object($row)){
        $tpl=new template_admin();
        echo $tpl->div_error(htmlspecialchars($cdb->lastError));
        return;
    }
    $ligne = (array)$row;
    $tt=time();

    if(($ligne["UsePrivKeyCrt"] ?? 0)==0){echo "<div class='alert alert-success'>Self-Signed certificate</div>";return;}

    if(strlen($ligne["srca"] ?? "")<50){
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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb    = new CertificatesDB();
    $row    = $cdb->getById($ID);
    $ligne  = is_object($row) ? (array)$row : array();
    $CommonName=urlencode($ligne["CommonName"] ?? "");
    $function=$_GET["function"];
    return $tpl->js_dialog5(($ligne["CommonName"] ?? "")." {certificate}","$page?certificate-privkey=$CommonName&ID=$ID&function=$function");
}
function certificate_crt_js():bool{
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $ID     = intval($_GET["crt-js"]);
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb    = new CertificatesDB();
    $row    = $cdb->getById($ID);
    $ligne  = is_object($row) ? (array)$row : array();
    $CommonName=urlencode($ligne["CommonName"] ?? "");
    $function=$_GET["function"];
    return $tpl->js_dialog5(($ligne["CommonName"] ?? "")." {certificate}","$page?certificate-crt=$CommonName&ID=$ID&function=$function");
}
function certificate_srca_js():bool{
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $ID     = intval($_GET["srca-js"]);
    $function=$_GET["function"];
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb    = new CertificatesDB();
    $row    = $cdb->getById($ID);
    $ligne  = is_object($row) ? (array)$row : array();
    $CommonName=urlencode($ligne["CommonName"] ?? "");
    return $tpl->js_dialog5(($ligne["CommonName"] ?? "")." {certificate}","$page?certificate-srca=$CommonName&ID=$ID&function=$function");
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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb    = new CertificatesDB();
    $row    = $cdb->getById($ID);
    $ligne  = is_object($row) ? (array)$row : array();
    $CommonName=urlencode($ligne["CommonName"] ?? "");
    return $tpl->js_dialog5(($ligne["CommonName"] ?? "")." {CSR}","$page?certificate-csr=$CommonName&ID=$ID&function=$function");

}
function certificate_bundle_js():bool{
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $function=$_GET["function"];
    $ID     = intval($_GET["bundle-js"]);
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb    = new CertificatesDB();
    $row    = $cdb->getById($ID);
    $ligne  = is_object($row) ? (array)$row : array();
    $CommonName=urlencode($ligne["CommonName"] ?? "");
    return $tpl->js_dialog5(($ligne["CommonName"] ?? "")." {certificate_chain}","$page?certificate-bundle=$CommonName&ID=$ID&function=$function");

}
function certificate_provider_bundle_js():bool{
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $function=$_GET["function"];
    $ID     = intval($_GET["CABundleProvider-js"]);
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb    = new CertificatesDB();
    $row    = $cdb->getById($ID);
    $ligne  = is_object($row) ? (array)$row : array();
    $CommonName=urlencode($ligne["CommonName"] ?? "");
    return $tpl->js_dialog5(($ligne["CommonName"] ?? "")." {CABundleProvider}","$page?CABundleProvider=$CommonName&ID=$ID&function=$function");

}


function certificate_csr():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb=new CertificatesDB();
    $tt=time();
    $return=null;
    $commonName=$_GET["certificate-csr"];
    $CommonNameURL=urlencode($commonName);
    if(isset($_GET["ID"])){
        $row = $cdb->getById(intval($_GET["ID"]));
    }else{
        $row = $cdb->getByCommonName($commonName);
    }
    $ligne = is_object($row) ? (array)$row : array();
    if(!is_object($row) && $cdb->lastError!==""){echo $tpl->div_error(htmlspecialchars($cdb->lastError));}
    $domains=$ligne["domains"] ?? "";

    $html[]="<div id='$tt-verify' style='margin-top:20px'></div>";
    $html[]=$tpl->div_explain("{CSR}||{csr_ssl_explain}");
    $html[]="<textarea style='width: 817px;height: 406px;font-size: 15px;padding-left:80px;padding-top:15px'>".($ligne["csr"] ?? "")."</textarea>";
    $report=certifcate_csr_parse($ligne["csr"] ?? "",$ligne["privkey"] ?? "");
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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb=new CertificatesDB();
    $row=$cdb->getByCommonName($CommonName);
    $ligne=is_object($row) ? (array)$row : array();


    $NOT_BUILD=false;
    if(($ligne["UsePrivKeyCrt"] ?? 0)==1){$NOT_BUILD=true;}
    if(($ligne["UseGodaddy"] ?? 0)==1){$NOT_BUILD=true;}

    if(!is_object($row)){senderror($cdb->lastError);return;}
    $tt=time();

    if(!$NOT_BUILD){
        if(strlen($ligne["csr"] ?? "")<50){
            $tpl=new template_admin();
            $cleanCN=preg_replace('/[^a-zA-Z0-9.*_\-]/','',$CommonName);
            $buildJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
                "/certificate/build-csr",
                array("common_name"=>$cleanCN)
            ));
            if(is_object($buildJson) && !empty($buildJson->success)){
                echo "<p class='text-success'><i class='fas fa-check-circle'></i> CSR generated for ".htmlspecialchars($cleanCN)."</p>";
                $row=$cdb->getByCommonName($CommonName);
                $ligne=is_object($row) ? (array)$row : array();
            } else {
                $err=is_object($buildJson)?htmlspecialchars($buildJson->error ?? 'unknown error'):'connection error';
                echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
            }
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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    if(!is_numeric($commonName)) {
        $row = $cdb->getByCommonName($commonName);
    }else{
        $row = $cdb->getById(intval($commonName));
    }
    if(!is_object($row)){
        writelogs($cdb->lastError." [$commonName]",__FUNCTION__,__FILE__,__LINE__);
    }
    $ligne = is_object($row) ? (array)$row : array();


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

// Certificat genere (ou Let's Encrypt) : lecture seule, vue en trois onglets
// partagee (ressources/class.certificates.view.inc).
//
// L'ancienne version ecrivait, a chaque affichage, la cle privee dans
// ressources/logs/web/ca.key et le certificat dans cert.der -- un repertoire
// servi par le serveur web, et deux fichiers que rien ne relisait.
function certificate_settings_locked():bool{
    $page=CurrentPageName();
    $commonName=urlencode((string)$_GET["certificate-settings"]);
    $banner="";
    if(isset($GLOBALS["CRT_META_HELD"][(string)$_GET["certificate-settings"]])){
        $tpl=new template_admin();
        // crt_css() outside the token parser: the dialog may open without the table's styles.
        $banner=crt_css().$tpl->_ENGINE_parse_body(crt_meta_banner());
    }
    echo $banner.cert_view_tabs(array(
        "overview"=>"$page?certificate-view-overview=$commonName",
        "key"=>"$page?certificate-view-key=$commonName",
        "raw"=>"$page?certificate-view-raw=$commonName",
    ));
    return true;
}

// PEM public d'un certificat du centre ; $ligne recoit sa ligne en base.
// UsePrivKeyCrt=1 : certificat officiel dans « crt », sinon auto-signe dans
// « SquidCert » -- colonnes en PEM brut, avec parfois des « \n » litteraux.
function certcenter_view_load($commonName,&$ligne):string{
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb=new CertificatesDB();
    $row=$cdb->getByCommonName((string)$commonName);
    $ligne=is_object($row)?(array)$row:array();
    $field="SquidCert";
    if(intval(isset($ligne["UsePrivKeyCrt"])?$ligne["UsePrivKeyCrt"]:0)==1){$field="crt";}
    $pem=isset($ligne[$field])?(string)$ligne[$field]:"";
    return str_replace("\\n","\n",$pem);
}

function certcenter_view_uid($commonName):string{
    return "cc-".substr(md5((string)$commonName),0,12);
}

function certcenter_view_overview():bool{
    $commonName=(string)$_GET["certificate-view-overview"];
    $ligne=array();
    $pem=certcenter_view_load($commonName,$ligne);
    $error="";
    $x509=cert_view_parse_pem($pem,$error);
    if($error<>""){echo $error;return false;}
    echo cert_view_overview_html($pem,$x509,certcenter_view_uid($commonName),certcenter_view_rows($ligne,$x509));
    return true;
}

function certcenter_view_key():bool{
    $commonName=(string)$_GET["certificate-view-key"];
    $ligne=array();
    $pem=certcenter_view_load($commonName,$ligne);
    $error="";
    $x509=cert_view_parse_pem($pem,$error);
    if($error<>""){echo $error;return false;}
    echo cert_view_key_html($pem,$x509,certcenter_view_uid($commonName));
    return true;
}

function certcenter_view_raw():bool{
    $commonName=(string)$_GET["certificate-view-raw"];
    $ligne=array();
    $pem=certcenter_view_load($commonName,$ligne);
    $error="";
    $x509=cert_view_parse_pem($pem,$error);
    if($error<>""){echo $error;return false;}
    echo cert_view_raw_html($pem,$x509,certcenter_view_uid($commonName));
    return true;
}

// Ce que le centre sait du certificat et que le PEM ignore : sa nature, comme
// l'affichait l'ancienne vue, et les parametres enregistres a la generation.
function certcenter_view_rows($ligne,$x509):array{
    return array(
        array("{type}",certcenter_view_type($x509)),
        array("{certificates_stored_settings}",certcenter_view_stored($ligne)),
    );
}

function certcenter_view_type($x509):string{
    $bc="";
    if(isset($x509["extensions"]["basicConstraints"])){$bc=(string)$x509["extensions"]["basicConstraints"];}
    if(preg_match("#CA:TRUE#i",$bc)){
        return "<span class='certv-pill is-accent'><i class='fa-solid fa-building-shield'></i>{CA_CERTIFICATE}</span>";
    }
    return "<span class='certv-pill'><i class='fa-solid fa-server'></i>{server_certificate}</span>";
}

// Nom commun enregistre (renommable, change-cert-js), niveau de chiffrement et
// duree demandes a la generation.
function certcenter_view_stored($ligne):string{
    $page=CurrentPageName();
    $ID=intval(isset($ligne["ID"])?$ligne["ID"]:0);
    $cn=cert_view_h(isset($ligne["CommonName"])?$ligne["CommonName"]:"");
    $users=new usersMenus();
    if($ID>0 && $users->AsCertifsManager && !crt_meta_held($ligne)){
        $cn.="<a href=\"javascript:blur();\" class='certv-act' title=\"{modify}\" OnClick=\"Loadjs('$page?change-cert-js=$ID');\"><i class='fa-regular fa-pen'></i></a>";
    }
    $facts=cert_view_fact("{CommonName}",$cn);
    $levelenc=intval(isset($ligne["levelenc"])?$ligne["levelenc"]:0);
    if($levelenc>0){
        $facts.=cert_view_fact("{level_encryption}","$levelenc bits");
    }
    $days=intval(isset($ligne["CertificateMaxDays"])?$ligne["CertificateMaxDays"]:0);
    if($days>0){
        $facts.=cert_view_fact("{CertificateMaxDays}","$days {days}");
    }
    return "<div class='certv-facts'>$facts</div>";
}

function certificate_settings_locked_empty():bool{
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    include_once(dirname(__FILE__)."/ressources/class.certificates.view.inc");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $cdb=new CertificatesDB();
    $id=intval($_GET["certificate-locked-not-uploaded"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $row=$cdb->getById($id);
    $ligne = is_object($row) ? (array)$row : array();
    if($cdb->lastError!==""){
        echo $tpl->div_error($cdb->lastError);
        return false;
    }

    $actions=array();
    $csr=isset($ligne["csr"])?(string)$ligne["csr"]:"";
    $crt=isset($ligne["crt"])?(string)$ligne["crt"]:"";

    if(strlen($csr)>50){
        $actions[]=array(
            "TITLE"=>"{cert_pending_view_csr}",
            "SUB"=>"{cert_pending_view_csr_sub}",
            "JS"=>"Loadjs('$page?csr-js=$id&function=$function')",
            "ICO"=>"fa-solid fa-code",
            "VARIANT"=>"teal",
        );
    }
    if(strlen($crt)<50){
        $actions[]=array(
            "TITLE"=>"{cert_pending_buy_ssl}",
            "SUB"=>"{cert_pending_buy_ssl_sub}",
            "JS"=>"s_PopUpFull('https://cyberdef360.com/store/ssl-certificates','1024','900');",
            "ICO"=>"fa-solid fa-arrow-trend-up",
            "VARIANT"=>"emerald",
            "BADGE"=>"{cert_pending_ca_badge}",
        );
        $actions[]=array(
            "TITLE"=>"{cert_pending_import_zip}",
            "SUB"=>"{cert_pending_import_zip_sub}",
            "JS"=>"Loadjs('fw.certificates-center.import.cert.php?certid=$id&function=$function')",
            "ICO"=>"fa-solid fa-file-arrow-up",
            "VARIANT"=>"blue",
        );
        $CommonName=urlencode(isset($ligne["CommonName"])?(string)$ligne["CommonName"]:"");
        $actions[]=array(
            "TITLE"=>"{cert_pending_import_360}",
            "SUB"=>"{cert_pending_import_360_sub}",
            "JS"=>"Loadjs('fw.certificates-center.import.360.php?CommonName=$CommonName&certid=$id&function=$function')",
            "ICO"=>"fa-solid fa-file-lines",
            "VARIANT"=>"indigo",
            "BADGE"=>"{cert_pending_sync_pem}",
        );
    }

    echo cert_pending_html($ligne,$actions);
    return true;
}
function certificate_settings(){
    if(!fw_progress_page_priv("AsCertifsManager")){return false;}   // task link signed for this session: built only for whom may run it
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $function=$_GET["function"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $cdb=new CertificatesDB();
    $btname=null;
    $commonName=$_GET["certificate-settings"];

    $row=$cdb->getByCommonName($commonName);
    $ligne = is_object($row) ? (array)$row : array();
    // Held by the Artica Meta "certificates" policy: read-only view, whatever its state.
    if(crt_meta_held($ligne)){return certificate_settings_locked();}
    $ID=intval($ligne["ID"] ?? 0);
    $Generated=intval($ligne["Generated"] ?? 0);
    $UsePrivKeyCrt=intval($ligne["UsePrivKeyCrt"] ?? 0);

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

    $jsrestart="Loadjs('$page?generate-x509-js=$ID&function=".urlencode($function)."')";

    $html="";
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
    $form[]=$tpl->field_array_hash($array_country_codes,"CountryName", "nonull:{countryName}", $array_country_codes2[$ligne["CountryName"]] ?? $ligne["CountryName"]);
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

// (Re)generates a self-signed certificate from its stored settings: CA, server
// certificate, CSR and PFX, by the daemon (classcerts.BuildCSR).
function certificate_generate_js():bool{
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsCertifsManager){return $tpl->js_no_privileges();}
    $ID=intval($_GET["generate-x509-js"]);
    $function=$_GET["function"] ?? "";
    $cdb=new CertificatesDB();
    $row=$cdb->getById($ID);
    if(!is_object($row)){return $tpl->js_error("$ID: not found $cdb->lastError");}
    $CommonName=$row->CommonName;
    $GLOBALS["CLASS_SOCKETS"]->REST_API_TIMEOUT=120;
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/certificate/build-csr",
        array("common_name"=>$CommonName)));
    if(!is_object($json)){return $tpl->js_error("articarest: no answer");}
    if(empty($json->success)){return $tpl->js_error(crt_meta_error($json->error ?? "unknown error"));}
    admin_tracks("Generated the self-signed certificate $CommonName");
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-loader-sslcerts-service','fw.certificates-center.php?table=yes');\n";
    if(preg_match('#^[A-Za-z0-9_]+$#',$function)){echo "$function();\n";}
    return true;
}

function certificate_settings_save():bool{
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $cdb=new CertificatesDB();
    foreach ($_POST as $num=>$vl){
        $_POST[$num]=url_decode_special_tool($vl);
    }

    $CommonName=$_POST["CommonName"];
    if($_POST["SelfSigned"]==0){$_POST["UsePrivKeyCrt"]=1;}else{$_POST["UsePrivKeyCrt"]=0;}

    [$exists,$existsId]=$cdb->exists($CommonName);
    if(!$exists || $existsId<=0){
        echo $tpl->div_error("$CommonName: not found");
        return true;
    }

    $fields=[
        "CountryName"         => $_POST["CountryName"] ?? "",
        "stateOrProvinceName" => $_POST["stateOrProvinceName"] ?? "",
        "CertificateMaxDays"  => $_POST["CertificateMaxDays"] ?? "",
        "OrganizationName"    => $_POST["OrganizationName"] ?? "",
        "OrganizationalUnit"  => $_POST["OrganizationalUnit"] ?? "",
        "emailAddress"        => $_POST["emailAddress"] ?? "",
        "localityName"        => $_POST["localityName"] ?? "",
        "levelenc"            => $_POST["levelenc"] ?? "",
        "UsePrivKeyCrt"       => $_POST["UsePrivKeyCrt"],
        "password"            => $_POST["password"] ?? "",
    ];
    if(!$cdb->update($existsId,$fields)){
        echo $tpl->div_error(crt_meta_error_html($cdb->lastError));
    }
    return true;

}


function certificate_new_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
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

    if($ligne["CommonName"]==null){
        $ligne["CommonName"]=php_uname('n');
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
    // Nom de la ligne dans le centre, distinct du CN : vide = le CN. Permet un second
    // CSR (nouvelle clé) pour le même nom d'hôte que le certificat en service.
    $form[]=$tpl->field_text("CertificateName", "{certificate_name}", "",false,"{certificate_name_csr_explain}");
    $form[]=$tpl->field_array_hash($array_country_codes,"CountryName", "nonull:{countryName}", $array_country_codes2[$ligne["CountryName"]] ?? $ligne["CountryName"]);
    $form[]=$tpl->field_text("stateOrProvinceName", "{stateOrProvinceName}", $ligne["stateOrProvinceName"]);
    $form[]=$tpl->field_text("localityName", "{localityName}", $ligne["localityName"]);
    $form[]=$tpl->field_text("OrganizationName", "{organizationName}", $ligne["OrganizationName"]);
    $form[]=$tpl->field_text("OrganizationalUnit", "{organizationalUnitName}", $ligne["OrganizationalUnit"]);
    $form[]=$tpl->field_text("emailAddress", "{emailAddress}", $ligne["emailAddress"]);
    $form[]=$tpl->field_array_hash($ENC, "levelenc", "{level_encryption}", $ligne["levelenc"]);
    $form[]=$tpl->field_numeric("CertificateMaxDays","{CertificateMaxDays} ({days})",$ligne["CertificateMaxDays"]);
    $form[]=crt_domains_field($tpl,$ligne["domains"] ?? "");

    $title_form="{new_certificate}";
    $button_name="{generate certificate}";
    $EXPLAIN="{new_certificate_explain}";
    if($OnlyCSR){
        $title_form="{CSR}";
        $EXPLAIN="{csr_ssl_explain}";
        $button_name="{generate_csr}";
    }
    $html=$html. $tpl->form_outside($title_form, $form,$EXPLAIN,$button_name,$jsafter,"AsCertifsManager");
    echo crt_domains_style().$html;
    return true;
}

function form_search():bool{
    $Addon=null;
    $tpl=new template_admin();
    $page=CurrentPageName();
    $options["WIDTH"]=460;
    echo $tpl->search_block($page,null,null,null,"&table=yes$Addon",$options);
    return true;
}

function PatchTables():bool{
    // Schema is auto-patched by the Go daemon (csqlite.PatchSSLTable) on every connect.
    return true;
}



function table():bool{
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $page=CurrentPageName();
    PatchTables();
    $cdb=new CertificatesDB();


    $Addon="";
    $users=new usersMenus();
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

    // Boutons de l'en-tête : méthode commune table_buttons() (même rendu que les autres pages),
    // le menu {actions} passe par SUB. Sans le droit, les boutons sont affichés verrouillés.
    $topbuttons=array();
    $topbuttons[]=array("Loadjs('fw.selfsigned.php?function=$function')",ico_plus,"{SELF_ROOT_CERT}","AsCertifsManager");
    $topbuttons[]=array("Loadjs('fw.certificates-center.php?new-certificate-js=yes&OnlyCSR=yes&function=$function')",ico_plus,"{new_certificate} (CSR)","AsCertifsManager");
    $topbuttons[]=array("Loadjs('fw.certificates-center.letsencrypt.php?function=$function&wizard=1')",ico_plus,"{LETSENCRYPT_CERTIFICATE}","AsCertifsManager");
    $filters=array();
    if($users->AsCertifsManager){
        $filters["{import} PFX"]="far fa-cloud-download||Loadjs('fw.certificates-center.import.pfx.php?function=$function');";
        $filters["{import} p7r - p7b"]="far fa-cloud-download||Loadjs('fw.certificates-center.import.p7r.php?function=$function');";
        $filters["{import_set_certificates} (zip)"]="far fa-cloud-download||Loadjs('fw.certificates-center.import.cert.php?function=$function');";
        $filters["{import} {server_certificate}"]=ico_import."||Loadjs('fw.certificates-center.import.servercert.php?function=$function');";
        $filters["{generate_csr}"]="fas fa-file-certificate||Loadjs('fw.certificates-center.php?new-certificate-js=yes&OnlyCSR=yes&function=$function');";
        $filters["{autorenew} Let`s Encrypt"]=ico_refresh."||Loadjs('$page?autorenew-js=yes&function=$function');";
        $filters["{trusted_certificates}"]="fas fa-shield-check||Loadjs('fw.certificates-center.truststore.php?js=yes');";
        $filters["SPACER"]=true;
    }
    $filters["{import} {database}"]="far fa-cloud-download||Loadjs('fw.certificates-center.import.php?function=$function');";
    $filters["{export} {database}"]="far fa-cloud-upload||document.location.href='$page?getdb=yes&function=$function'";
    $topbuttons["SUB"]=$filters;
    $topbuttons["SUB_TITLE"]="{actions}";
    $bts=array($tpl->table_buttons($topbuttons,"AsCertifsManager"));
    $sock=new sockets();
    $jsAfter="LoadAjax('table-loader-proxy-outgoingaddr','$page?table=yes');";
    $GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);




    $search="";
    if(isset($_GET["search"])){$search=trim($tpl->CLEAN_BAD_XSS($_GET["search"]));}

    $rowsLean=$cdb->listAll($search);
    if($cdb->lastError!==""){
        $ico=ico_support;
        $js="Loadjs('$page?patch-database=yes&function=$function');";
        $button=$tpl->button_autnonome("{repair_database}",$js, $ico, "AsCertifsManager",300,"btn-danger");
        echo $tpl->div_error($cdb->lastError."<div style='text-align:right'>$button</div>");
        return true;
    }

    // Hydrate with full blobs (PEM data) for the per-row computations below.
    $results=array();
    foreach($rowsLean as $r){
        $idForFetch=intval(is_object($r)?($r->ID ?? 0):($r["ID"] ?? 0));
        if($idForFetch<=0){continue;}
        $full=$cdb->getById($idForFetch);
        if(is_object($full)){
            $results[]=(array)$full;
        }
    }

    VERBOSE("RESULTS = ".count($results)." entries...",__LINE__);
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $rows=array();
    $K=array("total"=>0,"expired"=>0,"soon"=>0,"roots"=>0,"roots_self"=>0,"le"=>0,"linked"=>0);
    foreach ($results as $index=>$ligne){
        $ID=intval($ligne["ID"]);
        $CommonName=$ligne["CommonName"];
        $UsePrivKeyCrt=intval($ligne["UsePrivKeyCrt"] ?? 0);
        $UseLetsEncrypt=intval($ligne["UseLetsEncrypt"] ?? 0);
        if(!isset($ligne["ServerCert"])){$ligne["ServerCert"]=0;}
        $CommonNameEnc=urlencode($CommonName);
        $pks12=strlen($ligne["pks12"] ?? "");
        $treste=null;
        $AsRoot=intval($ligne["AsRoot"] ?? 0);
        $DateTo=$ligne["DateTo"] ?? "";
        if($DateTo=="0000-00-00"){$DateTo="";}
        $Generated=$ligne["Generated"] ?? 0;
        $crt=$ligne["crt"] ?? "";
        $SquidCert=$ligne["SquidCert"] ?? "";
        $privkey=$ligne["privkey"] ?? "";
        $Squidkey=$ligne["Squidkey"] ?? "";
        $ServerCert=intval($ligne["ServerCert"]);
        $csr=$ligne["csr"] ?? "";

        if($UsePrivKeyCrt==1){$SquidCert=$crt;$Squidkey=$privkey;}

        $metaHeld=crt_meta_held($ligne);
        if($Generated==0 && !$metaHeld){
            if(IfCertisCA($SquidCert)){
                $cdb->update($ID,["Generated"=>1,"AsRoot"=>1]);
                $AsRoot=1;
            }else{
                $cdb->update($ID,["Generated"=>1,"AsRoot"=>0]);
            }
        }

        $CertficateData=$UsePrivKeyCrt==0 ? $SquidCert : $crt;
        $js="Loadjs('$page?certificate-js=$CommonNameEnc&ID=$ID&function=$function')";

        // Type badges and the data-type keys the TINY "type" filter matches on.
        $badges=array();$types=array();
        if($UseLetsEncrypt==1){
            $types[]="letsencrypt";
            $badges[]=crt_badge("{LETSENCRYPT_CERTIFICATE}",strlen($crt)<50?"gray":"cyan");
            if(strlen($crt)<50){$js="Loadjs('fw.certificates-center.letsencrypt.php?ID=$ID&function=$function')";}
        }elseif(strlen($csr)>50 && strlen($CertficateData)<50){
            $types[]="csr";
            $badges[]=crt_badge("{CSR}","gray");
            $badges[]=crt_badge("{waiting_registration}","amber");
        }elseif(strlen($CertficateData)<10){
            $badges[]=crt_badge("{not_generated}","gray");
        }elseif($UsePrivKeyCrt==1){
            $types[]="official";
            $badges[]=crt_badge("{official_certificate}","green");
        }else{
            $types[]="selfsigned";
            $badges[]=crt_badge("{SelfSignedCert}","blue");
        }
        if($AsRoot==1){
            if($ServerCert==1){
                $badges[]=crt_badge("{server_certificate}","indigo");
            }else{
                $types[]="root";
                $badges[]=crt_badge("{ROOT_CERT}","indigo");
                $K["roots"]++;
                if(in_array("selfsigned",$types)){$K["roots_self"]++;}
            }
        }
        if(strlen($Squidkey)<50 && $ServerCert==0 && !in_array("csr",$types)){
            $badges[]=crt_badge("{missing_private_key}","amber");
        }
        if($UseLetsEncrypt==1){$K["le"]++;}
        if($metaHeld){
            $types[]="meta";
            array_unshift($badges,crt_meta_badge());
        }

        // Expiration state. "soon" = within crt_soon_days(): with the former 3-day
        // threshold a certificate 7 days from expiry counted as "valid" and the
        // Expired/Urgent tile said "Nothing to renew" (RWE, 2026-11-16).
        $state="none";
        if($DateTo<>""){
            $t1=time();
            $t2=strtotime($DateTo);
            if($t2!==false && $t1>$t2){$state="expired";$K["expired"]++;}
            if($t2!==false && $t2>$t1){
                $state="valid";
                $treste=$tpl->javascript_parse_text(distanceOfTimeInWords($t1,$t2));
                if(($t2-$t1)<crt_soon_days()*86400){$state="soon";$K["soon"]++;}
            }
        }

        $href=$tpl->td_href(crt_e($CommonName),"{click_to_edit}",$js);
        $idrow=md5($CommonNameEnc);

        $ous=array();
        if(strlen($ligne["OrganizationName"])>2){$ous[]=crt_e($ligne["OrganizationName"]);}
        if(strlen($ligne["OrganizationalUnit"])>2){$ous[]=crt_e($ligne["OrganizationalUnit"]);}
        $meta=array();
        // Nom de ligne distinct du CN (renouvellement avec nouvelle clé) : afficher le CN.
        $SubjectCN=trim(strval($ligne["SubjectCN"] ?? ""));
        if($SubjectCN<>"" && strcasecmp($SubjectCN,$CommonName)<>0){$meta[]="<span class='awc-mono'>CN: ".crt_e($SubjectCN)."</span>";}
        if(count($ous)>0){$meta[]=@implode(" &middot; ",$ous);}
        if(strlen(trim(strval($ligne["emailAddress"])))>0){$meta[]="<span class='awc-mono'>".crt_e($ligne["emailAddress"])."</span>";}

        $sitesFor=td_sitesbyCert($CommonName,$ID);
        if($sitesFor<>""){$K["linked"]++;}
        $CommonNameDiv=md5($CommonName);

        if($pks12>50) {
            $pfx=crt_tip($tpl->icon_archive("document.location.href='$page?download-pfx=$ID'", "AsCertifsManager"),"{crt_tip_pfx_download}");
        }elseif($metaHeld){
            $pfx=crt_meta_locked_act("fa-solid fa-gears");
        }else{
            $pfx=crt_tip($tpl->icon_run("Loadjs('$page?create-pfx=$CommonNameEnc&function=$function')"),"{crt_tip_pfx_create}");
        }
        $view="<a href='javascript:blur();' class='ak-act' title=\"{crt_tip_view}\" OnClick=\"$js\"><i class='fa-regular fa-eye'></i></a>";
        $renew="";
        if($UseLetsEncrypt==1 && $metaHeld){
            $renew=crt_meta_locked_act("fa-solid fa-arrows-rotate");
        }elseif($UseLetsEncrypt==1 && $users->AsCertifsManager){
            $renew="<a href='javascript:blur();' class='ak-act crt-act-renew' title=\"{crt_tip_renew}\" OnClick=\"Loadjs('fw.certificates-center.letsencrypt.php?ID=$ID&function=$function')\"><i class='fa-solid fa-arrows-rotate'></i></a>";
        }
        $dl=crt_tip($tpl->icon_download("document.location.href='$page?download-cert=$ID'","AsCertifsManager"),"{crt_tip_pem}");
        $ex=crt_tip($tpl->icon_export("document.location.href='$page?download=$CommonNameEnc'","AsCertifsManager"),"{crt_tip_export}");
        $jsDel="Loadjs('$page?delete-js=$CommonNameEnc&id=$idrow')";
        if($PowerDNSEnableClusterSlave==1){$jsDel="";}
        $del=crt_tip($tpl->icon_delete($jsDel,"AsCertifsManager"),"{crt_tip_delete}");
        if($metaHeld){$del=crt_meta_locked_act("fa-solid fa-trash-can");}

        $dtype=@implode(" ",$types);
        $h ="<tr data-state='$state' data-type='$dtype'>";
        $h.="<td>".crt_ident("fa-solid fa-fingerprint",$href,$meta)."<div class='crt-badges'>".@implode("",$badges)."</div></td>";
        $h.="<td nowrap>".crt_status_cell($state,$DateTo,$treste)."</td>";
        $h.="<td><span id='certdiv-$CommonNameDiv'>".crt_services_cell($sitesFor)."</span></td>";
        $h.="<td nowrap>".crt_keytype($CertficateData)."</td>";
        $h.="<td>".crt_acts(array($view,$renew,$pfx,$dl,$ex,$del))."</td>";
        $h.="</tr>";
        $rows[]=$h;
    }
    $K["total"]=count($rows);
    if($PowerDNSEnableClusterSlave==1){
        $bts=array();
    }

    $TINY_ARRAY["TITLE"]="{certificates_center}";
    $TINY_ARRAY["ICO"]=ico_certificate;
    $TINY_ARRAY["EXPL"]="{ssl_certificates_center_text}";
    $TINY_ARRAY["BUTTONS"]=siem_tiny_filters(crt_tiny_filters($results,$K),@implode("",$bts));
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<div class='crt'>";
    $html[]=crt_kpis($K,$users->AsCertifsManager);
    $html[]=crt_table_open(array(
        "<th>{crt_col_host}</th>",
        "<th>{crt_col_status}</th>",
        "<th>{crt_col_services}</th>",
        "<th>{key_type}</th>",
        "<th style='text-align:right'>{actions}</th>"),"crt-rows-main");
    if(count($rows)==0){
        $html[]=crt_table_empty(5);
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]=crt_table_close($K["total"],$K["total"],"{certificates}","crt-pager-main");
    $html[]=crt_expired_banner($K["expired"]);
    $html[]="</div>";

    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$jstiny
</script>";

    echo awc_table_style().siem_tiny_filters_style().crt_css()
        .$tpl->_ENGINE_parse_body($html)
        .awc_pager_js(50,"crt-rows-main","crt-pager-main")
        .crt_filter_js();
    return true;
}

// A function, not a define(): top-level statements below the dispatcher never run first.
function crt_soon_days():int{return 30;}

// Artica Meta "certificates" policy (spec netagents 2026-11-19 6): a row whose metaid column is
// filled is held by Meta. The daemon refuses delete, renew, replace and rename (423 managed by
// Artica Meta); attaching it to a service stays allowed. Held names are remembered for the
// read-only view of the same request.
function crt_meta_held($ligne):bool{
    if(!is_array($ligne)){return false;}
    $held=trim(strval($ligne["metaid"] ?? ""))<>"";
    if($held && isset($ligne["CommonName"])){$GLOBALS["CRT_META_HELD"][(string)$ligne["CommonName"]]=true;}
    return $held;
}
function crt_meta_badge():string{
    return "<span class='ak-tag crt-meta' title=\"{ccmeta_held_tip}\"><i class='fa-solid fa-diagram-project'></i>{ccmeta_badge}</span>";
}
// A row action the Meta lock forbids: shown greyed out, with the reason as tooltip.
function crt_meta_locked_act($ico):string{
    return "<span class='crt-tip' title=\"{ccmeta_held_tip}\"><a href='javascript:blur();' class='ak-act crt-act-off' aria-disabled='true'><i class='$ico'></i></a></span>";
}
function crt_meta_banner():string{
    return "<div class='crt-meta-banner'><i class='fa-solid fa-diagram-project'></i><div>{ccmeta_cert_banner}</div></div>";
}
// Edit link of a stored PEM field: none on a held row.
function crt_meta_field_js($tpl,$held,$js,$priv=null){
    if($held){return;}
    $tpl->table_form_field_js($js,$priv);
}
// Daemon refusal of a held row ("managed by Artica Meta", HTTP 423) -> translated token;
// any other error unchanged. For post_error()/js_error(), which resolve tokens.
function crt_meta_error($msg):string{
    if(strcasecmp(trim(strval($msg)),"managed by Artica Meta")==0){return "{ccmeta_cert_refused}";}
    return strval($msg);
}
// Same, as HTML for div_error(): the token translated, anything else escaped.
function crt_meta_error_html($msg):string{
    $e=crt_meta_error($msg);
    if($e=="{ccmeta_cert_refused}"){
        $tpl=new template_admin();
        return $tpl->_ENGINE_parse_body($e);
    }
    return htmlspecialchars($e);
}

function crt_e($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8"));
}

function crt_badge($label,$tone):string{
    $map=array("blue"=>"ak-info","green"=>"ak-ok","amber"=>"ak-warn","gray"=>"ak-off","cyan"=>"crt-cyan","indigo"=>"crt-indigo");
    return "<span class='ak-tag ".($map[$tone] ?? "ak-off")."'>$label</span>";
}

function crt_status_cell($state,$DateTo,$treste):string{
    if($state=="none"){return "<span class='crt-muted'>-</span>";}
    $map=array("expired"=>array("ak-err","{expired}"),"soon"=>array("ak-warn","{expire_soon}"),"valid"=>array("ak-ok","{valid}"));
    $h="<span class='ak-pill {$map[$state][0]}'><span class='ak-dot'></span>{$map[$state][1]}</span>";
    $h.="<div class='crt-date'>".crt_e($DateTo)."</div>";
    if(strlen(trim(strval($treste)))>0){$h.="<div class='crt-muted'>".crt_e($treste)."</div>";}
    return $h;
}

function crt_services_cell($sitesFor):string{
    if($sitesFor==""){
        // null = the daemon inventory could not be read: "not used" would be a guess.
        if(crt_usage_map()===null){return "<span class='crt-muted'>{crt_usage_unknown}</span>";}
        return "<span class='crt-muted'>{crt_no_service_any}</span>";
    }
    return "<span class='crt-svc'><i class='fa-solid fa-globe'></i><span>$sitesFor</span></span>";
}

// "RSA 2048 / SHA256" from the certificate itself: key algorithm and size, signature hash.
function crt_keytype($pem):string{
    $pem=str_replace("\\n","\n",(string)$pem);
    if(strlen($pem)<50){return "<span class='crt-muted'>-</span>";}
    $parts=array();
    $k=@openssl_pkey_get_public($pem);
    if($k){
        $d=@openssl_pkey_get_details($k);
        if(is_array($d)){
            $names=array(OPENSSL_KEYTYPE_RSA=>"RSA",OPENSSL_KEYTYPE_DSA=>"DSA",OPENSSL_KEYTYPE_EC=>"EC");
            $t=isset($d["type"]) && isset($names[$d["type"]]) ? $names[$d["type"]] : "";
            $bits=intval($d["bits"] ?? 0);
            $parts[]=trim("$t ".($bits>0?$bits:""));
        }
    }
    $x=@openssl_x509_parse($pem);
    if(is_array($x) && isset($x["signatureTypeSN"]) && preg_match('/(SHA[0-9]+|MD5|ED25519|ED448)/i',$x["signatureTypeSN"],$re)){
        $parts[]=strtoupper($re[1]);
    }
    $parts=array_filter($parts,"strlen");
    if(count($parts)==0){return "<span class='crt-muted'>-</span>";}
    return "<span class='crt-key'>".crt_e(@implode(" / ",$parts))."</span>";
}

// TINY filters: state and type, with their counts (client-side, crt_filter_js()).
function crt_tiny_filters($results,$K):array{
    $valid=$K["total"]-$K["expired"]-$K["soon"];
    $states=array(""=>"{all_states} ({$K["total"]})","expired"=>"{expired} ({$K["expired"]})",
        "soon"=>"{expire_soon} ({$K["soon"]})","valid"=>"{valid} ($valid)");
    $types=array(""=>"{all_types}","root"=>"{ROOT_CERT}","selfsigned"=>"{SelfSignedCert}",
        "official"=>"{official_certificate}","letsencrypt"=>"{LETSENCRYPT_CERTIFICATE}","csr"=>"{CSR}","meta"=>"{ccmeta_badge}");
    $f=array();
    $f[]=array("TYPE"=>"select","ID"=>"crt-f-state","LABEL"=>"{state}","VALUE"=>"","OPTIONS"=>$states,"JS"=>"CrtFilter");
    $f[]=array("TYPE"=>"select","ID"=>"crt-f-type","LABEL"=>"{type}","VALUE"=>"","OPTIONS"=>$types,"JS"=>"CrtFilter");
    return $f;
}

function crt_filter_js():string{
    return "<script>
window.CrtFilter=function(){
 var g=function(id){var e=document.getElementById(id);return e?e.value:'';};
 var s=g('crt-f-state'),t=g('crt-f-type');
 var tb=document.getElementById('crt-rows-main'); if(!tb){return;}
 var box=document.getElementById('crt-pager-main'), rows=tb.rows;
 if(s==''&&t==''){
   if(box){box.style.display='';}
   if(typeof tb.__awcRedraw=='function'){tb.__awcRedraw();}else{for(var i=0;i<rows.length;i++){rows[i].style.display='';}}
   return;
 }
 if(box){box.style.display='none';}
 for(var j=0;j<rows.length;j++){
   var r=rows[j], ok=(s==''||r.getAttribute('data-state')==s)&&(t==''||(' '+(r.getAttribute('data-type')||'')+' ').indexOf(' '+t+' ')>=0);
   r.style.display=ok?'':'none';
 }
};
</script>";
}

function crt_kpi_tile($label,$value,$sub,$ico,$tone,$js=""):string{
    $click=$js<>"" ? " crt-kpi-click' OnClick=\"$js\"" : "'";
    $map=array("err"=>"ak-err crt-kpi-err","ok"=>"ak-ok","green"=>"ak-accent","indigo"=>"crt-indigo");
    $t=$map[$tone] ?? "";
    return "<div class='ak-fig crt-kpi $t$click><div class='ak-fig-txt'><div class='ak-fig-l'>$label</div><div class='ak-fig-v'>$value</div>"
        ."<div class='ak-fig-s ak-mono'>$sub</div></div><span class='ak-ico ak-ico-sq'><i class='$ico'></i></span></div>";
}
function crt_kpi_total($K):string{
    return crt_kpi_tile("{certificates}",$K["total"],"{$K["linked"]} {crt_kpi_linked_any}","fa-solid fa-lock","neutral");
}
function crt_kpi_urgent($K):string{
    $n=$K["expired"]+$K["soon"];
    $tone=$n>0 ? "err" : "ok";
    $sub=$n>0 ? "{crt_kpi_urgent_sub}" : "{crt_kpi_urgent_none}";
    return crt_kpi_tile("{crt_kpi_urgent}",$n,$sub,"fa-solid fa-triangle-exclamation",$tone);
}
function crt_kpi_roots($K):string{
    return crt_kpi_tile("{crt_kpi_roots}",$K["roots"],"{$K["roots_self"]} {crt_kpi_selfsigned}","fa-solid fa-shield-halved","indigo");
}
function crt_kpi_letsencrypt($K,$admin):string{
    $days=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LetsEncryptRenewAfterDays"));
    if($days==0){$days=15;}
    $page=CurrentPageName();
    $js=$admin ? "Loadjs('$page?autorenew-js=yes')" : "";
    return crt_kpi_tile("Let's Encrypt",$K["le"],"{autorenew}: $days {days}","fa-solid fa-arrows-rotate","green",$js);
}
function crt_kpis($K,$admin):string{
    return "<div class='ak ak-grid-4 crt-kpis'>".crt_kpi_total($K).crt_kpi_urgent($K).crt_kpi_roots($K).crt_kpi_letsencrypt($K,$admin)."</div>";
}

function crt_expired_banner($expired):string{
    if($expired==0){return "";}
    return "<div class='ak-banner ak-warn crt-warn'><i class='fa-solid fa-circle-info'></i><div><div class='crt-warn-t'>{crt_warn_title} ($expired)</div>"
        ."<div>{crt_warn_text}</div></div></div>";
}

function crt_css():string{
    if(isset($GLOBALS["CRT_CSS"])){return "";}
    $GLOBALS["CRT_CSS"]=true;
    // Seules les regles propres a la page : le reste vient du kit ak- (angular.css.php).
    return "<style>
.crt-kpis{margin:10px 0 18px}
.crt-kpi{align-items:flex-start}
.crt-kpi-click{cursor:pointer}.crt-kpi-click:hover{border-color:#cbd5e1}
.crt-kpi-err{border-color:#fecaca}.crt-kpi-err .ak-fig-l,.crt-kpi-err .ak-fig-v{color:#dc2626}
.crt-indigo{--ak-tfg:#005447;--ak-tbg:#e6eeed}.crt-cyan{--ak-tfg:#0e7490;--ak-tbg:#ecfeff}
.crt-badges{display:flex;flex-wrap:wrap;gap:6px;margin:6px 0 0 54px}
.crt-date{font-family:'JetBrains Mono',Consolas,monospace;font-size:13px;color:#334155;margin-top:6px}
.crt-muted{font-size:13px;color:#94a3b8}
.crt-svc{display:inline-flex;gap:8px;align-items:baseline;font-size:13px}.crt-svc i{color:#94a3b8}
.crt-key{font-family:'JetBrains Mono',Consolas,monospace;font-size:13px;color:#334155}
.crt-tip{display:inline-flex;cursor:help}
.ak-act.crt-act-renew{color:#059669}
.crt-meta{--ak-tfg:#005447;--ak-tbg:#f2f6f6;display:inline-flex;align-items:center;gap:5px}
.ak-act.crt-act-off{color:#cbd5e1;cursor:not-allowed}.ak-act.crt-act-off:hover{background:none;color:#cbd5e1}
.crt-meta-banner{display:flex;gap:12px;align-items:flex-start;background:#f2f6f6;border:1px solid #bfd4d1;color:#005447;border-radius:12px;padding:12px 16px;margin:0 0 14px;font-size:13px;line-height:1.6}
.crt-meta-banner>i{color:#005447;font-size:16px;margin-top:3px}
.crt-warn{align-items:flex-start;gap:14px;margin-top:18px;padding:16px 20px;font-size:13px;line-height:1.6}
.crt-warn>i,.crt-warn>svg{color:#d97706;font-size:18px;margin-top:2px}
.crt-warn-t{font-weight:700;font-size:14px}
</style>";
}

// Services that reference each certificate (Web console, SMTP, proxy ports,
// HaCluster...), from the daemon inventory /certificates/api/usage, once per
// request: [ID => [label,...]]. null = inventory unavailable (older daemon).
// Reverse-proxy sites are left to the nginx query below, which links them.
function crt_usage_map(){
    if(array_key_exists("CRT_USAGE_MAP",$GLOBALS)){return $GLOBALS["CRT_USAGE_MAP"];}
    $GLOBALS["CRT_USAGE_MAP"]=null;
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/certificates/api/usage"),true);
    if(!is_array($json) || empty($json["success"]) || !isset($json["data"]["certificates"]) || !is_array($json["data"]["certificates"])){
        return null;
    }
    $map=array();
    foreach($json["data"]["certificates"] as $c){
        $id=intval($c["id"] ?? 0);
        if($id<=0 || !isset($c["consumers"]) || !is_array($c["consumers"])){continue;}
        foreach($c["consumers"] as $cons){
            if(($cons["service"] ?? "")=="reverse-proxy" && strlen($cons["target"] ?? "")>0){continue;}
            // known=false: a setting that merely holds the same text (myhostname...).
            if(empty($cons["known"])){continue;}
            if(array_key_exists("enabled",$cons) && $cons["enabled"]===false){continue;}
            $label=trim(strval($cons["label"] ?? ""));
            if($label==""){continue;}
            $map[$id][$label]=true;
        }
    }
    $GLOBALS["CRT_USAGE_MAP"]=$map;
    return $map;
}

function td_sitesbyCert($CommonName,$ID=0):string{
    $tpl=new template_admin();
    $function=$_GET["function"];
    // Same query for every row: fetched once per request.
    if(!isset($GLOBALS["CRT_SSL_PARAMS"])){
        $GLOBALS["CRT_SSL_PARAMS"]=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/service_parameters/zkey/ssl_certificate"),true);
    }
    $data=$GLOBALS["CRT_SSL_PARAMS"];
    $rows=array();
    if(is_array($data) && !empty($data["Status"]) && isset($data["rows"]) && is_array($data["rows"])){
        $rows=$data["rows"];
    }

    $CONF=array();
    foreach ($rows as $ligne){
        if(!isset($ligne["zvalue"])){continue;}
        if(strval($ligne["zvalue"])!==strval($CommonName)){continue;}
        $serviceid=isset($ligne["serviceid"])?intval($ligne["serviceid"]):0;
        if($serviceid<=0){continue;}
        $host=get_servicename($serviceid);
        if(strlen($host)<3){continue;}
        $CONF[]=$tpl->td_href($host,"","Loadjs('fw.nginx.sites.php?www-parameters-ssl-js=$serviceid&CertCenter=$function')");
    }

    $usage=crt_usage_map();
    $ID=intval($ID);
    if(is_array($usage) && $ID>0 && isset($usage[$ID])){
        foreach(array_keys($usage[$ID]) as $label){
            $CONF[]=crt_e($label);
        }
    }

    return @implode(", ",$CONF);
}
function isReverse():bool{
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    if($EnableNginx==1){
        return true;
    }
    $EnablePulseReverse=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePulseReverse"));
    if($EnablePulseReverse==1){
        return true;
    }
    $ActiveDirectoryRestLetsEncrypt    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestLetsEncrypt"));
    if($ActiveDirectoryRestLetsEncrypt==1){
        return true;
    }
    return false;
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
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


/**
 * certificate_new_csr_fields() — build the row to INSERT when a CSR is generated
 * "based on" an existing certificate.
 *
 * The point of that mode is to keep the SAME PRIVATE KEY under a new Common Name,
 * so the source row is read only for its key: everything else comes from the form
 * the operator just filled in.
 *
 * The key is always written to `privkey` with UsePrivKeyCrt=1, whichever column it
 * came from, because that is the pair LoadCertificateFromDB() reads in the daemon
 * (openssl.go): UsePrivKeyCrt==1 -> privkey, else -> Squidkey. Writing the key
 * without that flag would make the daemon read the empty Squidkey and silently
 * generate a brand-new key instead of reusing the one the operator selected.
 *
 * Escaped "\n" sequences are restored: the daemon does the same on read, but a CSR
 * built from a half-escaped PEM fails later and much less clearly.
 *
 * Pure function — no DB, no $_POST, no globals — so it can be exercised directly.
 */
/**
 * certificate_new_row_name() — [nom de la ligne, SubjectCN] d'un nouveau CSR.
 *
 * sslcertificates.CommonName est UNIQUE et sert de nom au certificat dans tout le
 * produit (services, liste, daemon). Le CN du sujet n'en est qu'une valeur par défaut :
 * un « Certificate name » saisi devient le nom de la ligne et le CN part dans
 * SubjectCN, lu par OpenSSLCreateCSR(). SubjectCN vide quand le nom égale le CN.
 *
 * Fonction pure.
 */
function certificate_new_row_name(array $post): array {
    $cn = strtolower(trim((string)($post["CommonName"] ?? "")));
    $name = strtolower(trim((string)($post["CertificateName"] ?? "")));
    $name = trim(preg_replace('#[^a-z0-9._*-]+#', '-', $name), "-");
    if ($name === "" || $name === $cn) {
        return [$cn, ""];
    }
    return [$name, $cn];
}

/**
 * crt_domains_parse() — liste des noms saisis dans la zone « Domains » du CSR.
 * Séparateurs acceptés : retour à la ligne, virgule, point-virgule, espace.
 * Rend [noms valides en minuscules, sans doublon ; entrées refusées]. Fonction pure.
 */
function crt_domains_parse(string $raw): array {
    $ok = array(); $bad = array();
    foreach (preg_split('#[\s,;]+#', $raw) as $d) {
        $d = strtolower(trim($d));
        if ($d === "") { continue; }
        $valid = filter_var($d, FILTER_VALIDATE_IP) !== false
            || preg_match('#^(\*\.)?([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9-]{2,63}$#', $d);
        if (!$valid) { $bad[] = $d; continue; }
        if (!in_array($d, $ok, true)) { $ok[] = $d; }
    }
    return array($ok, $bad);
}

/**
 * crt_domains_field() — zone de texte « Domains » du formulaire CSR, un nom par ligne,
 * avec son aide. field_textarea_normal() enregistre le champ dans le formulaire ; seul
 * son balisage est adapté (placeholder, police, hauteur).
 */
function crt_domains_field($tpl, string $value): string {
    list($names, ) = crt_domains_parse($value);
    $h = $tpl->field_textarea_normal("domains", "{domains}", implode("\n", $names), "100%", "110px");
    $h = preg_replace("#style='width:100%;min-height: 110px;font-size: 12px;' class='form-control'#",
        "class='form-control crt-dom-ta' rows='4' spellcheck='false' autocomplete='off' "
        ."placeholder='www.example.com&#10;mail.example.com&#10;*.example.com&#10;192.0.2.10'", $h, 1);
    return $h."<tr class='afrm-row afrm-full'><td colspan='2' style='padding-left:19px'>"
        ."<div class='crt-dom-help'><i class='fa-solid fa-circle-info'></i><div>{csr_domains_help}</div></div></td></tr>";
}

/** Feuille de style de crt_domains_field(), émise hors _ENGINE_parse_body. */
function crt_domains_style(): string {
    return "<style>.crt-dom-ta{width:100%;min-height:110px;resize:vertical;padding:10px 12px;"
        ."font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,'Liberation Mono',monospace;font-size:13px;"
        ."line-height:1.6;color:#0f172a;background:#fff;border:1px solid #cbd5e1;border-radius:8px;box-shadow:none}"
        .".crt-dom-ta:focus{border-color:#10b981;box-shadow:0 0 0 3px rgba(16,185,129,.12);outline:0}"
        .".crt-dom-ta::placeholder{color:#94a3b8}"
        .".crt-dom-help{display:flex;gap:10px;align-items:flex-start;margin:2px 0 10px 0;padding:10px 12px;"
        ."border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;color:#475569;font-size:13px;line-height:1.5}"
        .".crt-dom-help>svg,.crt-dom-help>i{color:#64748b;margin-top:3px;flex:0 0 auto}</style>";
}

function certificate_new_csr_fields(array $post, array $srcRow): array {
    $fields = [
        "CountryName"         => $post["CountryName"] ?? "",
        "stateOrProvinceName" => $post["stateOrProvinceName"] ?? "",
        "CertificateMaxDays"  => intval($post["CertificateMaxDays"] ?? 0) > 0 ? $post["CertificateMaxDays"] : 730,
        "OrganizationName"    => $post["OrganizationName"] ?? "",
        "OrganizationalUnit"  => $post["OrganizationalUnit"] ?? "",
        "emailAddress"        => $post["emailAddress"] ?? "",
        "localityName"        => $post["localityName"] ?? "",
        "levelenc"            => $post["levelenc"] ?? "",
        "password"            => $post["password"] ?? "",
        // Normalisé comme certificate_new_save() le fait avant d'appeler
        // /certificate/csr/{cn} : sslcertificates.CommonName n'a pas de COLLATE
        // NOCASE, donc insérer la forme brute rendrait la ligne introuvable au
        // daemon dès que l'opérateur saisit une majuscule.
        "CommonName"          => certificate_new_row_name($post)[0],
        "SubjectCN"           => certificate_new_row_name($post)[1],
        "domains"             => implode(",", crt_domains_parse((string)($post["domains"] ?? ""))[0]),
    ];

    // Where the source keeps its key: privkey when UsePrivKeyCrt=1, Squidkey
    // otherwise — with a fallback, because rows written by older code paths do not
    // always agree with their own flag.
    $field = "privkey";
    if (intval($srcRow["UsePrivKeyCrt"] ?? 0) == 0) {
        $field = "Squidkey";
        if (strlen($srcRow[$field] ?? "") < 10 && strlen($srcRow["privkey"] ?? "") > 10) {
            $field = "privkey";
        }
    }
    $key = str_replace("\\n", "\n", $srcRow[$field] ?? "");
    if (strlen($key) > 10) {
        $fields["privkey"]       = $key;
        $fields["UsePrivKeyCrt"] = 1;
    }
    return $fields;
}
function certificate_new_save():bool{
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb=new CertificatesDB();
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $SubjectCN=strtolower(trim($_POST["CommonName"]));
    // $CommonName = nom de la ligne (clé du centre) ; le CN du sujet peut différer.
    $CommonName=certificate_new_row_name($_POST)[0];
    if($CommonName==""){echo $tpl->post_error("{CommonName}: {your_query_is_empty}");return false;}
    $sock=new sockets();
    $gpid=0;
    $existsMsg="$CommonName: {alreadyexists}<br>{certificate_name_exists_hint}";
    list(,$badDomains)=crt_domains_parse((string)($_POST["domains"] ?? ""));
    if(count($badDomains)>0){
        echo $tpl->post_error("{csr_domain_invalid}<br><code>".implode(" ",array_slice($badDomains,0,5))."</code>");
        return false;
    }

    $sock->SET_INFO("CSRCommonName",$SubjectCN);


    if(isset($_POST["OnlyCSR"])){
        if(isset($_POST["OnlyCSRID"])){
            $OnlyCSRID=trim($_POST["OnlyCSRID"]);
            if(strlen($OnlyCSRID)>3){
                $rowSrc=$cdb->getByCommonName($OnlyCSRID);
                $ligne = is_object($rowSrc) ? (array)$rowSrc : array();
                $ID=intval($ligne["ID"] ?? 0);
                if($ID==0){echo $tpl->post_error("$OnlyCSRID: {source_package_not_found}");return false;}

                $insertFields=certificate_new_csr_fields($_POST,$ligne);
                if(!isset($insertFields["privkey"])){
                    echo $tpl->post_error("$OnlyCSRID: {no_data}");
                    return false;
                }

                // The daemon's /certificate/csr/{cn} loads the row BY COMMON NAME
                // (LoadCertificateFromDB) — it never creates it. Without this insert
                // it answers "Could not find configuration for <cn>", which is what
                // this whole branch used to do: it read the source row, validated its
                // private key, then threw it away and called the CSR endpoint for a
                // Common Name that existed nowhere.
                [$exists,$existsId]=$cdb->exists($CommonName);
                if($exists){
                    echo $tpl->post_error($existsMsg);
                    return false;
                }
                $newId=$cdb->insert($insertFields);
                if($newId<=0){echo $tpl->post_error($cdb->lastError);return false;}

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


    [$exists,$existsId]=$cdb->exists($CommonName);
    if($exists){
        echo $tpl->post_error($existsMsg);
        return false;
    }

    // Aucune ligne source : pas de clé à reprendre, le daemon en génère une.
    $insertFields=certificate_new_csr_fields($_POST,[]);
    $newId=$cdb->insert($insertFields);
    if($newId<=0){echo $tpl->post_error($cdb->lastError);return false;}



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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $CommonName=$_GET["certificate-info"];
    $cdb=new CertificatesDB();

    $row=$cdb->getByCommonName($CommonName);
    $ligne = is_object($row) ? (array)$row : array();

    $field="crt";
    if(intval($ligne["UsePrivKeyCrt"] ?? 0)==0){$field="SquidCert";}
    if(intval($ligne["UseGodaddy"] ?? 0)==1){$field="crt";}


    $passin=null;
    if(($ligne["CertPassword"] ?? null)<>null){
        $passin=" -passin pass:{$ligne["CertPassword"]}";
    }

    $ligne[$field]=str_replace("\\n", "\n", $ligne[$field] ?? "");
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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb=new CertificatesDB();

    $row=$cdb->getByCommonName($CommonName);
    $ligne = is_object($row) ? (array)$row : array();
    $UsePrivKeyCrt=intval($ligne["UsePrivKeyCrt"] ?? 0);
    $ID=intval($ligne["ID"] ?? 0);

    $Certfield="crt";
    if($UsePrivKeyCrt==0){return;}
    if($ID<=0){return;}



    $sock=new sockets();
    $tpl=new templates();

    $updateFields=array();

    $ligne[$Certfield]=str_replace("\\n", "\n", $ligne[$Certfield] ?? "");

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

            $updateFields["DateFrom"]=$DateFrom;
            continue;

        }
        if(preg_match("#Not After.*?:(.+)#",$ligne,$re)){
            $Date1=strtotime($re[1]);
            $DateTo=date("Y-m-d",$Date1);
            $updateFields["DateTo"]=$DateTo;
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
        $updateFields["CountryName"]=$C;
    }
    if($ST<>null){
        $updateFields["stateOrProvinceName"]=$ST;
    }
    if($L<>null){
        $updateFields["localityName"]=$L;
    }
    if($O<>null){
        $updateFields["OrganizationName"]=$O;
    }
    if($OU<>null){
        $updateFields["OrganizationalUnit"]=$OU;
    }

    if($levelenc>0){
        $updateFields["levelenc"]=$levelenc;
    }



    if(count($updateFields)>0){

        VERBOSE("UPDATE sslcertificates SET ".count($updateFields)." fields WHERE CommonName=$CommonName", __LINE__);
        if(!$cdb->update($ID,$updateFields)){
            if($return){return $cdb->lastError;}
            echo $cdb->lastError;return;}

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
    // Stream the certificates DB binary via /certificate/db/export. Direct
    // file access is gone — the daemon owns the file.
    header('Content-type: application/x-sqlite3');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"certificates-center.db\"");
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    ob_clean();
    flush();

    $ch=curl_init();
    curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, "/usr/share/artica-postfix/bin/run/articarest.sock");
    curl_setopt($ch, CURLOPT_URL, "http://localhost/certificate/db/export");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_exec($ch);
    curl_close($ch);
}

function download_pfx(){
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $ID=intval($_GET["download-pfx"]);
    $cdb=new CertificatesDB();
    $return=null;
    $row=$cdb->getById($ID);
    $ligne = is_object($row) ? (array)$row : array();
    $CommonName=$ligne["CommonName"] ?? "";
    $final_content=base64_decode($ligne["pks12"] ?? "");

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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $ID=intval($_GET["download-cert"]);
    $cdb=new CertificatesDB();
    $return=null;


    $row=$cdb->getById($ID);
    $ligne = is_object($row) ? (array)$row : array();

    $CommonName=$ligne["CommonName"] ?? "";
    $field="privkey";
    $field_cert="crt";
    if($cdb->lastError!==""){echo htmlspecialchars($cdb->lastError);}
    if(intval($ligne["UsePrivKeyCrt"] ?? 0)==0){
        $field="Squidkey";
        $field_cert="SquidCert";

        if(strlen($ligne[$field] ?? "")<10){
            if(strlen($ligne["privkey"] ?? "")>10){$field="privkey";}
        }

    }

    $ligne[$field]=str_replace("\\n", "\n", $ligne[$field] ?? "");
    $private_key_content=trim($ligne[$field]);
    $ligne[$field_cert]=str_replace("\\n", "\n", $ligne[$field_cert] ?? "");
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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $CommonName=$_GET["download"];
    $cdb=new CertificatesDB();
    $row=$cdb->getByCommonName($CommonName);
    $ligne = is_object($row) ? (array)$row : array();
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