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
    $function=$_GET["function"];
	if(!$users->AsCertifsManager){$tpl->js_no_privileges();return; }
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$tpl->js_no_license();return;}
	$tpl->js_dialog6("{certificates} >> {import} {database}", "$page?popup=yes&function=$function","600");
	
}


function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $function=$_GET["function"];
	$bt_upload=$tpl->button_upload("{upload_backup}",$page,null,"&function=$function")."&nbsp;&nbsp;";
	$html="<center>$bt_upload</center>
	".$tpl->div_error("{warning_all_tables_will_be_removed_and_restored}<br>{if_ccc_then_import_added}");
	echo $tpl->_ENGINE_parse_body($html);
	
}

function file_uploaded():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsCertifsManager){return $tpl->js_no_privileges();}
    $function=$_GET["function"] ?? "";
    $file=$_GET["file-uploaded"];
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/certificate/import/backup",
        array("filename"=>$file)));
    if(!is_object($json)){return $tpl->js_error("articarest: no answer");}
    if(!$json->Status){return $tpl->js_error($json->Error);}
    if(isset($json->CommonName)){
        admin_tracks("Imported the certificate $json->CommonName from $file");
    }else{
        admin_tracks("Restored the certificates database from $file");
    }
    header("content-type: application/x-javascript");
    echo "dialogInstance6.close();\n";
    if(preg_match('#^[A-Za-z0-9_]+$#',$function)){echo "$function();\n";}
    return true;
}
