<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["params-js"])){params_js();exit;}
if(isset($_POST["restore"])){exit;}
if(isset($_GET["restore-js"])){restore_js();exit;}
if(isset($_GET["restore-popup"])){restore_popup();exit;}
if(isset($_GET["params"])){params();exit;}
if(isset($_POST["OpenDLAPBackupMaxContainers"])){params_save();exit;}

if(isset($_GET["remove"])){remove_js();exit;}
if(isset($_POST["remove"])){remove_perform();exit;}
if(isset($_GET["download"])){download();exit;}
page();

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{containers}"]="$page?table-start=yes";
    $array["{schedule}"]="fw.system.tasks.php?sub-main=yes&ForceTaskType=84";
    echo $tpl->tabs_default($array);

}
function remove_js(){
    $tpl=new template_admin();
    $filename=$_GET["remove"];
    $md=$_GET["md"];
    $tpl->js_confirm_delete($filename,"remove",$filename,"$('#$md').remove()");

}
function restore_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=$_GET["restore-js"];

    if($filename<>null){
        if(!isset($_GET["confirm"])) {
            $jsAfter = "Loadjs('$page?restore-js=$filename&confirm=yes');";
            $tpl->js_dialog_confirm_action("{restore} $filename", "restore", "$filename", $jsAfter);
            return;
        }
    }

    $tpl->js_dialog1("{restore} {container} $filename","$page?restore-popup=$filename",550);

}
function restore_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=$_GET["restore-popup"];
    $html[]="<div id='restore-container-upload'></div>";

    if($filename==null){
        $html[]=$tpl->div_explain("{restore_ldap_container_explain}");
        $html[]="<div class='center' style='margin:30px'>";
        $html[]=$tpl->form_button_upload("{upload}",$page,null,"AsSystemAdministrator");
        $html[]="</center>";
    }else{
        $fname=urlencode($filename);
        $jsrestart= $tpl->framework_buildjs(
            "/openldap/backup/import/$fname","openldap.backup.progress","openldap.backup.progress.txt",
            "restore-container-upload","LoadAjax('ldap-containers','$page?table=yes');dialogInstance1.close();");
        $html[]="<script>$jsrestart;</script>";
    }

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function file_uploaded():bool{
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $file=urlencode($_GET["file-uploaded"]);
    $tpl=new template_admin();

    echo $tpl->framework_buildjs(
        "/openldap/backup/import/$file","openldap.backup.progress","openldap.backup.progress.txt",
        "restore-container-upload","LoadAjax('ldap-containers','$page?table=yes');dialogInstance1.close();"
    );
    return true;
}


function params_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{backup_ldap} {parameters}","$page?params=yes",800);
}
function params_save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
}

function params(){
    $tpl=new template_admin();
    $OpenDLAPBackupMaxContainers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenDLAPBackupMaxContainers"));
    if($OpenDLAPBackupMaxContainers==0){$OpenDLAPBackupMaxContainers=30;}


    $OpenLDAPBackUpload=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPBackUpload"));
    $OpenLDAPBackUploadFTPserv=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPBackUploadFTPserv"));
    $OpenLDAPBackUploadFTPusr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPBackUploadFTPusr"));
    $OpenLDAPBackUploadFTPpass=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPBackUploadFTPpass"));
    $OpenLDAPBackUploadFTPDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPBackUploadFTPDir"));
    $OpenLDAPBackUploadFTPPassive=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPBackUploadFTPPassive"));
    $OpenLDAPBackUploadFTPTLS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPBackUploadFTPTLS"));


    $array["FTP_SERVER"]["KEY"]="OpenLDAPBackUploadFTPserv";
    $array["FTP_SERVER"]["VALUE"]="$OpenLDAPBackUploadFTPserv";
    $array["FTP_PASSIVE"]["KEY"]="OpenLDAPBackUploadFTPPassive";
    $array["FTP_PASSIVE"]["VALUE"]="$OpenLDAPBackUploadFTPPassive";
    $array["TLS"]["KEY"]="OpenLDAPBackUploadFTPTLS";
    $array["TLS"]["VALUE"]="$OpenLDAPBackUploadFTPTLS";
    $array["TARGET_DIR"]["KEY"]="OpenLDAPBackUploadFTPDir";
    $array["TARGET_DIR"]["VALUE"]="$OpenLDAPBackUploadFTPDir";
    $array["USERNAME"]["KEY"]="OpenLDAPBackUploadFTPusr";
    $array["USERNAME"]["VALUE"]="$OpenLDAPBackUploadFTPusr";
    $array["PASSWORD"]["KEY"]="OpenLDAPBackUploadFTPpass";
    $array["PASSWORD"]["VALUE"]="$OpenLDAPBackUploadFTPpass";

    $form[]=$tpl->field_numeric("OpenDLAPBackupMaxContainers","{max_containers}",$OpenDLAPBackupMaxContainers);
    $form[]=$tpl->field_checkbox("OpenLDAPBackUpload","{enable_FTP_backup}",$OpenLDAPBackUpload);
    $form[]=$tpl->field_ftp_params($array);

    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}","blur()","AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html) );



}

function remove_perform(){
    $filename=$_POST["remove"];
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("openldap.php?remove-backup=$filename");

}

function page(){

    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{backup_ldap}","fad fa-file-archive","{backup_ldap_explain}",
        "$page?tabs=yes","ldap-backup","progress-openldapbackup-restart",false,"table-loader-openldap-backup");



	if(isset($_GET["main-page"])){
        $tpl=new template_admin("{backup_ldap}",$html);
		echo $tpl->build_firewall();
		return;
	}
	echo $tpl->_ENGINE_parse_body($html);

}

function table_start(){
    $page=CurrentPageName();
    echo "<div id='ldap-containers' style='margin-top:20px'></div><script>LoadAjax('ldap-containers','$page?table=yes')</script>";
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $TRCLASS=null;
    $t=time();

    $jsrestart=$tpl->framework_buildjs("/openldap/backup/now","openldap.backup.progress",
        "openldap.backup.progress.txt","progress-openldapbackup-restart","LoadAjax('ldap-containers','$page?table=yes');");

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{containers}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{createdate}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{restore}</center></th>";
    $html[]= "<th data-sortable=true class='text-capitalize' data-type='text' style=\"text-align: center;\">{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $target_directory="/home/artica/ldap_backup";
    $dir_handle = opendir($target_directory);
    $MAIN=array();
    if($dir_handle) {
        while ($file = readdir($dir_handle)) {
            if ($file == '.') {
                continue;
            }
            if ($file == '..') {
                continue;
            }
            if (!is_file("$target_directory/$file")) {
                continue;
            }
            if (!preg_match("#^([0-9]+)\.gz$#", $file, $re)) {
                continue;
            }
            $stime = $re[1];
            $MAIN[$stime] = $file;
        }
    }

    krsort($MAIN);

    foreach ($MAIN as $stime=>$file){

                if ($TRCLASS == "footable-odd") {
                    $TRCLASS = null;
                } else {
                    $TRCLASS = "footable-odd";
                }
                $md = md5(serialize($file));
                $item = $file;
                $date=$tpl->time_to_date($stime,true);
                $remove = $tpl->icon_delete("Loadjs('$page?remove=$file&md=$md')","AsSystemAdministrator");
                $size=@filesize("$target_directory/$file");
                $size=FormatBytes($size/1024);
                $ico="<i class='fad fa-file-archive'></i>&nbsp;&nbsp;";
                $item=$tpl->td_href($item,"{download}","s_PopUp('$page?download=$item',0,0);");
                $restore=$tpl->icon_restore("Loadjs('$page?restore-js=$file');","AsSystemAdministrator");

            $html[] = "<tr class='$TRCLASS' id='$md'>";
            $html[] = "<td><strong>{$ico}$item</strong></td>";
            $html[] = "<td width=1% nowrap><strong>$date</strong></td>";
            $html[] = "<td width=1%><strong>$size</strong></td>";
            $html[] = "<td style='vertical-align:middle' width=1%>$restore</td>";
            $html[] = "<td style='vertical-align:middle' width=1%>$remove</td>";
            $html[] = "</tr>";
        }



    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='5'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";


    $topbuttons[] = array("Loadjs('$page?params-js=yes');", ico_params, "{parameters}");
    $topbuttons[] = array($jsrestart, ico_save, "{backup_now}");
    $topbuttons[] = array("Loadjs('$page?restore-js=')", ico_import, "{import}:{backup}");
    $added="";
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openldap/backup/instant"));

    if ($json->Status) {
        $size=$json->filesize;
        $xTime=$json->filetime;
        $distance=distanceOfTimeInWords($xTime,time());
        $added="<br><i class='font-bold'>InstantLDAPBackup: ".FormatBytes($size/1024)." {since} $distance</i>";
    }

    $TINY_ARRAY["TITLE"]="{backup_ldap}";
    $TINY_ARRAY["ICO"]="fad fa-file-archive";
    $TINY_ARRAY["EXPL"]="{backup_ldap_explain}$added";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="
<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } )});
	$headsjs
</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function download(){
    $target_directory="/home/artica/ldap_backup";
    $filename=$_GET["download"];

    $filepath="$target_directory/$filename";
    $fsize=@filesize($filepath);


    if(!$GLOBALS["VERBOSE"]){
        header('Content-type: application/x-tar');
        header('Content-Transfer-Encoding: binary');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
        header("Content-Length: ".$fsize);
        ob_clean();
        flush();
    }
    if(is_file($filepath)){
        readfile($filepath);
    }
}

function save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();	
	unset($_POST["suffix"]);
	$tpl->SAVE_POSTs();
	
}

