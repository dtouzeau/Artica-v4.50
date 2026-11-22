<?php
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_POST["pfx-password"])){import_pfx_save();exit;}
if(isset($_GET["password-form"])){PasswordForm();exit;}
js();



function js():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$users=new usersMenus();

    if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);
        if ($gpid > 0) {
            $users->AsCertifsManager = true;
        }
    }

	if(!$users->AsCertifsManager){$tpl->js_no_privileges();return false; }
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$tpl->js_no_license();return false;}
	return $tpl->js_dialog6("{certificates} >> {import} PFX", "$page?popup=yes","700");
	
}


function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$bt_upload=$tpl->button_upload("{import_pfx_file}",$page)."&nbsp;&nbsp;";
	$html="<div id='pfx-form-import'><div class='center'>$bt_upload</div></div>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

// The form posts the password with the file name; the daemon imports it at once.
function import_pfx_save():bool{
    $tpl=new template_admin();
    if(!pfx_import_allowed()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    // The file name travels base64-encoded (field_hidden does not escape quotes or <).
    // form_outside double-encodes its fields; rawurldecode is exact and keeps a literal '+'.
    $file=base64_decode(rawurldecode($_POST["pfx-filename"] ?? ""));
    $password=rawurldecode($_POST["pfx-password"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/certificate/import/pfx",
        array("filename"=>$file,"password"=>$password)));
    if(!is_object($json)){echo "articarest: no answer";return false;}
    if(!$json->Status){echo $json->Error;return false;}
    admin_tracks("Imported the PFX certificate $json->CommonName from $file");
    return true;
}

function pfx_import_allowed():bool{
    if(intval($_SESSION["HARMPID"] ?? 0)>0){return true;}
    $users=new usersMenus();
    return (bool)$users->AsCertifsManager;
}


function file_uploaded(){

    $file=urlencode($_GET["file-uploaded"]);
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    echo "LoadAjax('pfx-form-import','$page?password-form=yes&filename=$file')";
}
function PasswordForm(){
    $tpl=new template_admin();
    $form[]=$tpl->field_hidden("pfx-filename",base64_encode($_GET["filename"] ?? ""));
    $form[]=$tpl->field_password("pfx-password","{password}",null);
    $jsafter="dialogInstance6.close();LoadAjax('table-loader-sslcerts-service','fw.certificates-center.php?table=yes');";
    echo $tpl->form_outside("{import_pfx_file}",$form,null,"{apply}",$jsafter);
}