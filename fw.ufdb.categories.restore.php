<?php
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
function file_defined_js(){
	header("content-type: application/x-javascript");
	if(!isset($_SESSION["categories_backup_path"])){return;}
	if($_SESSION["categories_backup_path"]==null){return;}
	$file=$_SESSION["categories_backup_path"];
	$basename=basename($file);
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup_categories.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup_categories.log";
	$ARRAY["CMD"]="ufdbguard.php?restore-categories=".urlencode($file);
	$ARRAY["TITLE"]="{restore_backup} $basename";
	$ARRAY["AFTER"]="LoadAjax('table-categories-list','fw.artica.backup.categories.php?table=yes')";
	$prgress=base64_encode(serialize($ARRAY));
	echo "Loadjs('fw.progress.php?content=$prgress&mainid=backup-categories-progress')\n";
	
}

function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();

	$jsafter="Loadjs('$page?file-defined=yes')";
	$form[]=$tpl->form_button_upload("{upload_a_file} (*.gz {or} *.tar.gz)",$page,$_SESSION["categories_backup_path"],"AsDansGuardianAdministrator");
	$form[]=$tpl->field_text("filepath", "{local_file}", null,true,null);

	$html=$tpl->form_outside("{your_categories} &raquo;&raquo; {import}", @implode("\n", $form),
			"{your_categories_import_explain}","{import}",$jsafter,"AsDansGuardianAdministrator");
	echo 
	"<div style='margin-top:10px' id='backup-categories-progress'></div>".$tpl->_ENGINE_parse_body($html);
	
	
}


function file_defined(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	
	$_SESSION["categories_backup_path"]=$_POST["filepath"];
}


function file_uploaded(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$file=$_GET["file-uploaded"];
	$basename=basename($file);
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup_categories.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup_categories.log";
	$ARRAY["CMD"]="ufdbguard.php?restore-categories=".urlencode($file)."&uploaded=yes";
	$ARRAY["TITLE"]="{restore_backup} $basename";
	$ARRAY["AFTER"]="LoadAjax('table-perso-category-loader','fw.ufdb.categories.php?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=backup-categories-progress')";
	echo $jsrestart;
}