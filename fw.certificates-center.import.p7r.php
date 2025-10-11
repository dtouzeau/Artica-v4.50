<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}

js();



function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$users=new usersMenus();
	if(!$users->AsDnsAdministrator){$tpl->js_no_privileges();return; }
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$tpl->js_no_license();return;}
	$tpl->js_dialog6("{certificates} >> {import} p7r/p7b", "$page?popup=yes","700");
	
}


function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$bt_upload=$tpl->button_upload("{import}",$page)."&nbsp;&nbsp;";
	$html="<div id='p7r-form-import'><div class='center'>$bt_upload</div></div>
	<div id='progress-certificates-center-import'></div>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function PasswordPosted(){
    $file=$_GET["filename"];
    $password=base64_encode($_SESSION["pfx-password"]);


}


function file_uploaded(){

    $file=$_GET["filename"];
    if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);
        if ($gpid > 0) {
            $Addon="&HarmpID=$gpid";
        }
    }

    $file=urlencode($_GET["file-uploaded"]);
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/certificates.center.import.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/certificates.center.import.progress.log";
    $ARRAY["CMD"]="openssl.php?import-p7r=yes$Addon&filename=".urlencode($file);
    $ARRAY["TITLE"]="{importing} $file";
    $ARRAY["AFTER"]="LoadAjax('table-loader-sslcerts-service','fw.certificates-center.php?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-certificates-center-import')";
    echo "document.getElementById('p7r-form-import').innerHTML='';\n";
    echo $jsrestart;
}
