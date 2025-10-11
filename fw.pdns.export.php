<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["export-section"])){file_uploaded();exit;}
js();



function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$users=new usersMenus();
	if(!$users->AsDnsAdministrator){$tpl->js_no_privileges();return; }
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$tpl->js_no_license();return;}
	$tpl->js_dialog6("{APP_PDNS} >> {export}", "$page?popup=yes","600");
	
}


// 


function popup(){

	$tpl=new template_admin();
	$page=CurrentPageName();
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/pdns.import.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/pdns.import.progress.log";
	$ARRAY["CMD"]="pdns.php?export-backup=yes";
	$ARRAY["TITLE"]="{exporting}";
	$ARRAY["AFTER"]="LoadAjax('export-section','$page?export-section=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-pdns-import')";
	
	
	

	$bt_upload=$tpl->button_autnonome("{export}",$jsrestart,"fa-upload");
	$html="<center>$bt_upload</center>
	<div id='progress-pdns-import'></div>
	<div id='export-section'></div>";
	echo $tpl->_ENGINE_parse_body($html);
	
}


function file_uploaded(){
	$tpl=new template_admin();
	if(!is_file("/usr/share/artica-postfix/ressources/logs/web/dns-backup.tar.gz")){return;}
	$size=filesize("/usr/share/artica-postfix/ressources/logs/web/dns-backup.tar.gz");
	$size=FormatBytes($size/1024);
	
	echo $tpl->_ENGINE_parse_body("<div class=\"widget-head-color-box navy-bg p-lg text-center\">
                                <h1>dns-backup.tar.gz</h1>
                                <div class=\"m-b-sm\">
                                        <a href=\"ressources/logs/web/dns-backup.tar.gz\"><img src=\"img/good-files-64.png\" class=\"img-circle circle-border m-b-md\" alt=\"image\"></a>
                                </div>
                                        <p class=\"font-bold\">$size</p>


                            </div>");
	
	
	
	
	
}