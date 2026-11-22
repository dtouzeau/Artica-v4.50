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
	$tpl->js_dialog6("{import_set_certificates}", "$page?popup=yes&certid=$certid&function=$function","700");
	
}


function popup():bool{
    $certid=0;
	$tpl=new template_admin();
	$page=CurrentPageName();
    if(isset($_GET["certid"])){
        $certid=intval($_GET["certid"]);
    }
    $function=$_GET["function"];
	$bt_upload=$tpl->button_upload("{import_certifcate_file}",$page,null,"&certid=$certid&function=$function")."&nbsp;&nbsp;";
	$explain=$tpl->div_explain("{import_set_certificates_explain}<p><i>{certificat_import_text}</i></p>");
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

    if(!preg_match("#\.zip$#",$file)){
        return $tpl->js_error("Error: $file not a zip file");
    }

    $array["path"]=$fullpath;
    $gpid=0;
    if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);
    }
    $array["HarmpID"]=$gpid;

    $sock=new sockets();
    if($certid>0){
            $array["certid"]=$certid;
            $data=$sock->REST_API_POST("/certificate/zip/import",$array);
            $json=json_decode($data);
            if(!$json->Status){
                echo $tpl->js_mysql_alert($json->Error);
                return false;
            }

            header("content-type: application/x-javascript");
            echo "dialogInstance6.close();\n";
            if(strlen($function)>2) {
                echo "$function();";
            }
            return true;
    }


    $data=$sock->REST_API_POST("/certificate/zip/newimport",$array);
    $json=json_decode($data);
    if(!$json->Status){
        return  $tpl->js_mysql_alert($json->Error);
    }


    header("content-type: application/x-javascript");
    echo "dialogInstance6.close();\n";
    if(strlen($function)>2) {
        echo "$function();";
    }
    return true;
}
