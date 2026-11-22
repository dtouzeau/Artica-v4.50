<?php
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
	if(!$users->AsCertifsManager){$tpl->js_no_privileges();return; }
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$tpl->js_no_license();return;}
	$tpl->js_dialog6("{certificates} >> {import} p7r/p7b", "$page?popup=yes","700");
	
}


function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$bt_upload=$tpl->button_upload("{import}",$page)."&nbsp;&nbsp;";
	$html="<div id='p7r-form-import'><div class='center'>$bt_upload</div></div>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function file_uploaded():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsCertifsManager){return $tpl->js_no_privileges();}
    $file=$_GET["file-uploaded"];
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/certificate/import/p7r",
        array("filename"=>$file)));
    if(!is_object($json)){return $tpl->js_error("articarest: no answer");}
    if(!$json->Status){return $tpl->js_error($json->Error);}
    admin_tracks("Imported the PKCS#7 certificate $json->CommonName from $file");
    header("content-type: application/x-javascript");
    echo "dialogInstance6.close();\n";
    echo "LoadAjax('table-loader-sslcerts-service','fw.certificates-center.php?table=yes');\n";
    return true;
}
