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
	$tpl->js_dialog6("{APP_PDNS} >> {import}", "$page?popup=yes","600");
	
}


function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$bt_upload=$tpl->button_upload("{upload_backup}",$page)."&nbsp;&nbsp;";
    $warning=$tpl->div_warning("{warning_all_tables_will_be_removed_and_restored}");
	$html="<center style='margin:30px'>$bt_upload</center>
	<div id='progress-pdns-import'>$warning</div>";
	echo $tpl->_ENGINE_parse_body($html);
	
}


function file_uploaded(){
	
	$tpl=new template_admin();
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$file=$_GET["file-uploaded"];

    $jsrestart= $tpl->framework_buildjs("pdns.php?import-backup=yes&filename=".urlencode($file),
    "pdns.import.progress","pdns.import.progress.log","progress-pdns-import","LoadAjax('table-pdns','fw.pdns.status.php?table=yes');"
    );
	echo $jsrestart;
}