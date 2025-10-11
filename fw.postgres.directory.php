<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.openvpn.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["ChangePostGresSQLDir"])){ChangePostGresSQLDir();exit;}
if(isset($_GET["newdir-js"])){popup_newdir_js();exit;}
if(isset($_GET["newdir"])){popup_newdir();exit;}
popup_js();


function popup_newdir_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsDatabaseAdministrator){$tpl->js_no_privileges();exit();}
	$title=$tpl->javascript_parse_text("{database_storage_path}");
	$tpl->js_dialog($title, "$page?newdir=yes");	
	
}
function popup_newdir():bool{
    $tpl=new template_admin();
    $jsupdate=$tpl->framework_buildjs("/postgresql/movedb",
    "postgres.changedir.progress","postgres.changedir.log","progress-ChangeDir","BootstrapDialog1.close();LoadAjax('table-postgresqlstatus','fw.postgresql.status.php?table=yes');");

	
	echo "<div id='progress-ChangeDir'></div>
			<script>$jsupdate</script>";
	
	return true;
}

function popup_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsDatabaseAdministrator){$tpl->js_no_privileges();exit();}
	$title=$tpl->javascript_parse_text("{database_storage_path}");
	$tpl->js_dialog($title, "$page?popup=yes");
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/postgresql/realpath"));
    $PostGresSQLDatabaseDirectory=$json->Info;

	$form[]=$tpl->field_browse_directory("ChangePostGresSQLDir", "{directory}", $PostGresSQLDatabaseDirectory);
	$html[]=$tpl->form_outside("{database_storage_path}", @implode("\n", $form),null,"{apply}","Loadjs('$page?newdir-js=yes')","AsDatabaseAdministrator");
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function ChangePostGresSQLDir(){
$sock=new sockets();
$_POST["ChangePostGresSQLDir"]=url_decode_special_tool($_POST["ChangePostGresSQLDir"]);

    if(strpos($_POST["ChangePostGresSQLDir"], "/PostgreSQL")===false){
        $_POST["ChangePostGresSQLDir"]="{$_POST["ChangePostGresSQLDir"]}/PostgreSQL";
    }



$sock->SET_INFO("ChangePostGresSQLDir", $_POST["ChangePostGresSQLDir"]);

}