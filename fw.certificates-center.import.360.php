<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
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
