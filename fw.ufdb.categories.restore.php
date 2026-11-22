<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.categories.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["file-defined"])){file_defined_js();exit;}
if(isset($_POST["filepath"])){file_defined();exit;}
js();


function js(){
	$tpl=new template_admin();
	$users=new usersMenus();
	$page=CurrentPageName();
	if(!$users->AsDansGuardianAdministrator){$tpl->js_no_privileges();return;}
	$title="{restore_backup}";
	$tpl->js_dialog2($title, "$page?popup=yes");
	
	
}
// Restoring categories replaces the current lists: web filtering administrators only, checked
// on every entry point (popup, path, start), not only when the dialog opens.
function ufdbrestore_allowed():bool{
	$users=new usersMenus();
	return (bool)($users->AsDansGuardianAdministrator || $users->AsSystemAdministrator);
}

function file_defined_js(){
	header("content-type: application/x-javascript");
	if(!ufdbrestore_allowed()){$tpl=new template_admin();$tpl->js_no_privileges();return;}
	if(!isset($_SESSION["categories_backup_path"])){return;}
	if($_SESSION["categories_backup_path"]==null){return;}
	$file=$_SESSION["categories_backup_path"];
    $fileEn=urlencode($file);

    $tpl=new template_admin();
    $js=$tpl->framework_buildjs("/category/backup/restore/$fileEn",
    "backup_categories.progress",
    "backup_categories.progress.log",
    "backup-categories-progress",
    "LoadAjax('table-categories-list','fw.artica.backup.categories.php?table=yes')");

	echo $js."\n";
	
}

function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	if(!ufdbrestore_allowed()){echo $tpl->div_error("{ERROR_NO_PRIVS2}");return;}

	$jsafter="Loadjs('$page?file-defined=yes')";
	$form[]=$tpl->form_button_upload("{upload_a_file} (*.tar.gz)",$page,$_SESSION["categories_backup_path"],"AsDansGuardianAdministrator");
	$form[]=$tpl->field_text("filepath", "{local_file}", null,true,null);

	$html=$tpl->form_outside("{your_categories} &raquo;&raquo; {import}", @implode("\n", $form),
			"{bkcat_import_explain}","{import}",$jsafter,"AsDansGuardianAdministrator");
	// Said before the import, not after: the restore replaces the lists, and only full .tar.gz
	// containers are accepted (articarest CategoriesBackup restores .tar.gz only).
	$warn=$tpl->div_warning("{bkcat_import_warn_title}||{bkcat_import_warn_text}");
	echo
	"<div style='margin-top:10px' id='backup-categories-progress'></div>".$tpl->_ENGINE_parse_body($warn.$html);
	
	
}


function file_defined(){
	$tpl=new template_admin();
	if(!ufdbrestore_allowed()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return;}
	$tpl->CLEAN_POST();
	
	$_SESSION["categories_backup_path"]=$_POST["filepath"];
}


function file_uploaded(){
    if(!fw_progress_page_priv("AsDansGuardianAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
	header("content-type: application/x-javascript");
    $tpl=new template_admin();
	$file=$_GET["file-uploaded"];
    $fileEn=urlencode($file);

    $jsrestart=$tpl->framework_buildjs("/category/backup/restore/$fileEn",
        "backup_categories.progress",
        "backup_categories.progress.log",
        "backup-categories-progress",
        "LoadAjax('table-perso-category-loader','fw.ufdb.categories.php?table=yes');");

	echo $jsrestart;
}