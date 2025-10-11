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
    $function=$_GET["function"];
	if(!$users->AsDnsAdministrator){$tpl->js_no_privileges();return; }
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$tpl->js_no_license();return;}
	$tpl->js_dialog6("{certificates} >> {import} {database}", "$page?popup=yes&function=$function","600");
	
}


function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $function=$_GET["function"];
	$bt_upload=$tpl->button_upload("{upload_backup}",$page,null,"&function=$function")."&nbsp;&nbsp;";
	$html="<center>$bt_upload</center>
	".$tpl->div_error("{warning_all_tables_will_be_removed_and_restored}<br>{if_ccc_then_import_added}")."
	<div id='progress-certificates-center-import'></div>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function GetDatabase(){
    $db="/home/artica/SQLITE/certificates.db";
    if(isset($_SESSION["HARMPID"])){
        $gpid=intval($_SESSION["HARMPID"]);
        if($gpid>0){
            $db="/home/artica/SQLITE/certificates.$gpid.db";
        }
    }
    return $db;
}


function file_uploaded():bool{
    $function=$_GET["function"];
    if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);
        if ($gpid > 0) {
            $Addon="&HarmpID=$gpid";
        }
    }
	
	header("content-type: application/x-javascript");
	$file=$_GET["file-uploaded"];

    $ARRAY["AFTER"]="dialogInstance6.close();";
    if(strlen($function)>1){
        $ARRAY["AFTER"]="$function()dialogInstance6.close();";
    }

	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/certificates.center.import.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/certificates.center.import.progress.log";
	$ARRAY["CMD"]="openssl.php?import-backup=yes$Addon&filename=".urlencode($file);
	$ARRAY["TITLE"]="{importing} $file";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-certificates-center-import')";
	echo $jsrestart;
    return true;
}