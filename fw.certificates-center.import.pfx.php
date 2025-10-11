<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_POST["pfx-password"])){$tpl=new template_admin();$tpl->CLEAN_POST();$_SESSION["pfx-password"]=$_POST["pfx-password"];exit;}
if(isset($_GET["password-posted"])){PasswordPosted();exit;}
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
	$html="<div id='pfx-form-import'><div class='center'>$bt_upload</div></div>
	<div id='progress-certificates-center-import'></div>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function PasswordPosted(){
    $file=$_GET["filename"];
    $Addon=null;
    if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);
        if ($gpid > 0) {
            $Addon="&HarmpID=$gpid";
        }
    }

    $password=base64_encode($_SESSION["pfx-password"]);
    header("content-type: application/x-javascript");
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/certificates.center.import.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/certificates.center.import.progress.log";
    $ARRAY["CMD"]="openssl.php?import-pfx=yes$Addon&filename=".urlencode($file)."&password=$password";
    $ARRAY["TITLE"]="{importing} $file";
    $ARRAY["AFTER"]="LoadAjax('table-loader-sslcerts-service','fw.certificates-center.php?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-certificates-center-import')";
    echo "document.getElementById('pfx-form-import').innerHTML='';\n";
    echo $jsrestart;

}


function file_uploaded(){

    $file=urlencode($_GET["file-uploaded"]);
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    echo "LoadAjax('pfx-form-import','$page?password-form=yes&filename=$file')";
}
function PasswordForm(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=urlencode($_GET["filename"]);
    $form[]=$tpl->field_password("pfx-password","{password}",null);
    echo $tpl->form_outside("{import_pfx_file}",$form,null,"{apply}","Loadjs('$page?password-posted=yes&filename=$filename')");
}