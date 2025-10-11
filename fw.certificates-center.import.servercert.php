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
	$tpl->js_dialog6("{server_certificate}", "$page?popup=yes&certid=$certid&function=$function","700");

}


function popup():bool{
    $certid=0;
	$tpl=new template_admin();
	$page=CurrentPageName();


    $function=$_GET["function"];
	$bt_upload=$tpl->button_upload("{server_certificate}: {import_certifcate_file}",$page,null,"&function=$function")."&nbsp;&nbsp;";
	$explain=$tpl->div_explain("{import_server_certificate_explain}<p>");
	$html="<div id='ca-form-import'>
        <div class='center'>$bt_upload</div></div>
	    <div id='progress-certificates-center-import' style='margin-top:20px'>$explain</div>";
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}







function file_uploaded():bool{
    $tpl=new template_admin();
    $file=$_GET["file-uploaded"];
    $fullpath="/usr/share/artica-postfix/ressources/conf/upload/$file";
    $certid=intval($_GET["certid"]);
    $function=$_GET["function"];

    if(!preg_match("#\.(txt|crt|pem|der|cer)$#",$file)){
        return $tpl->js_error("Error: $file not a txt or crt or der or pem or cer file");
    }

    $q=new lib_sqlite(GetDatabase());
    if (!$q->FIELD_EXISTS("sslcertificates", "ServerCert")) {
        $q->QUERY_SQL("ALTER TABLE sslcertificates ADD ServerCert INTEGER NOT NULL DEFAULT 0");
    }

    $sock=new sockets();
    $data=$sock->REST_API("/certificate/certserver/$file");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        return $tpl->js_error(json_last_error_msg());
    }
    if (!$json->Status){
        return $tpl->js_error($json->Error);
    }
    header("content-type: application/x-javascript");
    $f[]="dialogInstance6.close();";
    if(strlen($function)>3){
        $f[]="$function();";
    }
    echo @implode("\n",$f);
    return true;
}
function GetDatabase():string{
    $db="/home/artica/SQLITE/certificates.db";
    if(isset($_SESSION["HARMPID"])){
        $gpid=intval($_SESSION["HARMPID"]);
        if($gpid>0){
            $db="/home/artica/SQLITE/certificates.$gpid.db";
        }
    }
    return $db;
}












