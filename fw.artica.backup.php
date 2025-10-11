<?php
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["schedule-js"])){schedule_js();exit;}
if(isset($_GET["schedule-popup"])){schedule_popup();exit;}
if(isset($_POST["BackupArticaSnaps"])){schedule_save();exit;}
if(isset($_GET["parameters-dirs"])){parameters_dir_js();exit;}
if(isset($_GET["parameters-dirs-popup"])){parameters_dir_popup();exit;}

if(isset($_GET["nas-js"])){nas_js();exit;}
if(isset($_GET["nas-popup"])){nas_popup();exit;}

if(isset($_POST["SnapShotStorageMax"])){parameters_save();exit;}
if(isset($_POST["BackupArticaBackUseNas"])){parameters_save();exit;}
if(isset($_POST["InfluxAdminRetentionTime"])){parameters_save();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["test-nas-js"])){test_nas_js();exit;}
if(isset($_GET["tests-nas-results"])){test_nas_results();exit;}
if(isset($_GET["download-js"])){download_js();exit;}
if(isset($_GET["download-popup"])){download_popup();exit;}
if(isset($_GET["realdownload-js"])){realdownload_js();exit;}
if(isset($_GET["realdownload-popup"])){realdownload_popup();exit;}
if(isset($_GET["upload-js"])){upload_js();exit;}
if(isset($_GET["support-tool-js"])){support_tool_js();exit;}
if(isset($_GET["upload-support-tool-popup"])){support_tool_popup();exit;}
if(isset($_GET["support-tool-uploaded"])){support_tool_uploaded();exit;}
if(isset($_GET["support-tool-progress-popup"])){support_tool_progress_popup();exit;}


if(isset($_GET["restore-js"])){restore_js();exit;}
if(isset($_GET["restore-confirm-js"])){restore_confirm_js();exit;}
if(isset($_GET["upload-progress-js"])){upload_progress_js();exit;}
if(isset($_GET["upload-popup"])){upload_popup();exit;}
if(isset($_GET["upload-password"])){upload_password();exit;}
if(isset($_GET["upload-progress-popup"])){upload_progress_popup();exit;}
if(isset($_GET["restore-confirm-popup"])){restore_confirm_popup();exit;}
if(isset($_GET["parameters-tiny"])){parameters_buttons();exit;}
if(isset($_GET["parameters2"])){parameters2();exit;}

if(isset($_GET["nas-enable"])){nas_enable();exit;}
if(isset($_GET["backup-js"])){backup_js();exit;}
if(isset($_GET["backup-popup"])){backup_popup();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete_perform();exit;}
if(isset($_POST["BackupArticaBackNASFolder"])){parameters_save();exit;}
if(isset($_GET["test-nas-popup"])){test_nas_popup();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded_js();exit;}
if(isset($_POST["snapshot_password"])){file_uploaded_password();exit;}
if(isset($_POST["file-restore"])){exit();}
if(isset($_GET["statistics"])){statistics();exit;}

if(isset($_GET["clean-now"])){clean_now_js();exit;}
if(isset($_GET["clean-now-popup"])){clean_now_popup();exit;}
if(isset($_GET["clean-after-save"])){clean_after_save();exit;}
if(isset($_POST["CleanNow"])){$_SESSION["CleanNow"]=$_POST["CleanNow"];exit;}
if(isset($_GET["js-desc"])){snapshot_description_js();exit;}
if(isset($_GET["desc-popup"])){snapshot_description_popup();exit;}
if(isset($_POST["desc-save"])){snapshot_description_save();exit;}
if(isset($_GET["js-content"])){snapshot_content_js();exit;}
if(isset($_GET["content-popup"])){snapshot_content_popup();exit;}


page();
function nas_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("modal:{NAS_storage}","$page?nas-popup=yes");

}
function parameters_dir_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("modal:{directories}","$page?parameters-dirs-popup=yes");
}
function schedule_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("modal:{schedule}","$page?schedule-popup=yes");
    return true;
}
function schedule_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $BackupArticaSnaps=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaSnaps"));
    $Array     = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaSnapsSched"));
    if(!isset($Array["DAY"])){$Array["DAY"]=2;}

    for($i=1;$i<30;$i++){
        $term="{days}";
        if($i<2){$term="{day}";}
        $DAYS[$i]="$i $term";
    }
    for($i=1;$i<24;$i++){
        $t="$i";
        if($i<10){$t="0$i";}
        $zH[$i]=$t;
    }
    for($i=1;$i<60;$i++){
        $t="$i";
        if($i<10){$t="0$i";}
        $zM[$i]=$t;
    }
    if(!isset($Array["HOUR"])){$Array["HOUR"]="1";}
    if(!isset($Array["MIN"])){$Array["MIN"]="30";}
    $form[]=$tpl->field_checkbox("BackupArticaSnaps","{enable}",$BackupArticaSnaps);
    $form[]=$tpl->field_array_hash($DAYS,"DAY","{backup}:{each}",$Array["DAY"]);
    $form[]=$tpl->field_array_hash($zH,"HOUR","{hour}",$Array["HOUR"]);
    $form[]=$tpl->field_array_hash($zM,"MIN","{minutes}",$Array["MIN"]);
    echo $tpl->form_outside("{backup_artica_settings}",$form,"{backup_artica_settings_explain}","{apply}","dialogInstance1.close();Loadjs('$page?parameters-tiny=yes');");
    return true;
}
function schedule_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("BackupArticaSnaps",$_POST["BackupArticaSnaps"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("BackupArticaSnapsSched",serialize($_POST));
    admin_tracks_post("Saving Schedule settings of Artica backup task");
    $GLOBALS["CLASS_SOCKETS"]->getGoFramework("exec.backup.artica.php --set-schedule");
    return true;
}
function snapshot_description_js():bool{
    $ID=intval($_GET["js-desc"]);
    $q=new lib_sqlite("/home/artica/SQLITE/snapshots.db");
    $ligne=$q->mysqli_fetch_array("SELECT name FROM snapshots WHERE ID='$ID'");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $name=$ligne["name"];
    $tpl->js_dialog6("{snapshot}:$name", "$page?desc-popup=$ID",850);
    return true;
}
function snapshot_content_js():bool{
    $ID=intval($_GET["js-content"]);
    $q=new lib_sqlite("/home/artica/SQLITE/snapshots.db");
    $ligne=$q->mysqli_fetch_array("SELECT name FROM snapshots WHERE ID='$ID'");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $name=$ligne["name"];
    $tpl->js_dialog6("{snapshot}:$name", "$page?content-popup=$ID",850);
    return true;
}
function snapshot_content_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["content-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/snapshots.db");
    $ligne=$q->mysqli_fetch_array("SELECT content FROM snapshots WHERE ID='$ID'");
    $content=unserialize($ligne["content"]);

    $TRCLASS="";
    $td1="style='width:1%' nowrap";
    $t=time();
    $TH="data-sortable=true class='text-capitalize' data-type='text'";

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th $TH>{filename}</th>";
    $html[]="<th $TH>{size}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    foreach ($content as $filename=>$size) {
        $MD5 = null;
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }

        if($size>1024) {
            $size = FormatBytes($size / 1024);
        }else{
            $size="{$size}Bytes";
        }
        $html[]="<tr class='$TRCLASS' id=''>";
        $html[]="<td>$filename</td>";
        $html[]="<td $td1>$size</td>";
        $html[]="</tr>";

    }


    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='2'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
		<script>
			NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
			$(document).ready(function() { $('#table-$t').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
		</script>";
    echo $tpl->_ENGINE_parse_body($html);

return true;
}
function snapshot_description_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["desc-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/snapshots.db");
    $ligne=$q->mysqli_fetch_array("SELECT name FROM snapshots WHERE ID='$ID'");

    $tpl->field_hidden("desc-save",$ID);
    $form[]=$tpl->field_text("snapdesc","{description}",$ligne["name"],true);
    $security="AsSystemAdministrator";
    echo $tpl->form_outside("{description}", @implode("\n", $form),null,"{apply}","LoadAjax('table-snapshots-list','$page?table=yes');dialogInstance6.close();",$security);
    return true;
}
function snapshot_description_save(){
    $ID=intval($_POST["desc-save"]);
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $desc=$_POST["snapdesc"];

    $q=new lib_sqlite("/home/artica/SQLITE/snapshots.db");
    $q->QUERY_SQL("UPDATE snapshots SET name='$desc' WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error;return false;}
    admin_tracks("Saving snapshot ID $ID description to $desc");
    return true;
}

function backup_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog6("modal:{create_a_snapshot}", "$page?backup-popup=yes",850);
}

function clean_now_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog6("{clean_databases}", "$page?clean-now-popup=yes",750);
}
function clean_now_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $InfluxAdminRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxAdminRetentionTime"));
    if($InfluxAdminRetentionTime==0){$InfluxAdminRetentionTime=365;}
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$InfluxAdminRetentionTime=5;}

    $form[]=$tpl->field_numeric("CleanNow","{retention_days}",$InfluxAdminRetentionTime);

    $html[]="<div id='clean-database-progress'></div>";
    $html[]=$tpl->form_outside("{clean_databases}",$form,"{clean_databases_explain}","{apply}","Loadjs('$page?clean-after-save=yes')","AsSystemAdministrator",true);

    echo $tpl->_ENGINE_parse_body($html);

}

function clean_after_save(){
    $tpl=new template_admin();
    $CleanNow=intval($_SESSION["CleanNow"]);
    if($CleanNow==0){
        $tpl->js_error("{retention_days} == 0 !!!");
        return;
    }

    admin_tracks("Clean statistics database tasks for a retention of $CleanNow days");

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.statistics.purge.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.statistics.purge.progress.txt";
    $ARRAY["CMD"]="postgres.php?purge=$CleanNow";
    $ARRAY["TITLE"]="{clean_databases}";
    $ARRAY["AFTER"]="if(typeof dialogInstance6 == 'object'){ dialogInstance6.close();}";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=clean-database-progress')";

    header("content-type: application/x-javascript");
    echo "$jsrestart\n";

}

function upload_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	return $tpl->js_dialog6("{upload_snapshot}", "$page?upload-popup=yes",550);
}
function support_tool_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog6("{attach_support_tool}", "$page?upload-support-tool-popup=yes",550);
}


function restore_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$filename=$_GET["restore-js"];
	$fileencode=urlencode($filename);
	$tpl->js_confirm_execute("{restore} {snapshot}<br>$filename", "file-restore", $filename,"Loadjs('$page?restore-confirm-js=$fileencode')");
	
}
function restore_confirm_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$filename=$_GET["restore-confirm-js"];
	$fileencode=urlencode($filename);
	$tpl->js_dialog6("{restore} {progress}", "$page?restore-confirm-popup=$fileencode",650);
	
}

function upload_progress_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$filename=$_GET["upload-progress-js"];
	$fileencode=urlencode($filename);	
	$tpl->js_dialog6("{upload_snapshot} {progress}", "$page?upload-progress-popup=$fileencode",650);
}
function file_uploaded_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$filename=$_GET["file-uploaded"];
	$fileencode=urlencode($filename);
	if(preg_match("#\.aes$#", $filename)){
		$tpl->js_dialog6("{upload_snapshot} {password}", "$page?upload-password=$fileencode",890);
		return;
	}
	return $tpl->js_dialog6("{upload_snapshot} {progress}", "$page?upload-progress-popup=$fileencode",890);
}

function support_tool_uploaded():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=$_GET["file-uploaded"];
    $fileencode=urlencode($filename);
    return $tpl->js_dialog6("{attach_support_tool} {progress}", "$page?support-tool-progress-popup=$fileencode",890);
}

function upload_password(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$filename=$_GET["upload-password"];
	$fileencode=urlencode($filename);
	$password=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapshotUploadedPassword"));
	$form[]=$tpl->field_password2("snapshot_password", "{password}", $password);
	echo $tpl->form_outside($filename, $form,null,"{submit}","Loadjs('$page?upload-progress-js=$fileencode')",null,true);
	
}
function file_uploaded_password(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$tpl->CLEAN_POST();
	$sock->SET_INFO("SnapshotUploadedPassword", $_POST["snapshot_password"]);
}


function download_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$filename=$_GET["download-js"];
	$filenameenc=urlencode($filename);
	$tpl->js_dialog6("{download} $filename", "$page?download-popup=$filenameenc",850);
}
function realdownload_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$filename=$_GET["realdownload-js"];
	$filenameenc=urlencode($filename);
	$targetfilename=dirname(__FILE__)."/ressources/web/logs/$filename";
	if(!is_file($targetfilename)){
		$tpl->js_error_stop($targetfilename." no such file");
		exit();
	}
	
	$tpl->js_dialog6("{download} $filename", "$page?realdownload-popup=$filenameenc",850);
}
function test_nas_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog6("{test_connection}", "$page?test-nas-popup=yes",500);
}
function realdownload_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$filename=$_GET["realdownload-popup"];
    admin_tracks("manually Downloaded a snapshot $filename");
	$filesize=FormatBytes(@filesize("ressources/web/logs/$filename")/1024);
	echo "<center><a href=\"ressources/web/logs/$filename\"><H1>$filename ($filesize)</a></h1></center>";
}
function upload_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$btn=$tpl->button_upload("{upload_a_file}",$page);
	$html="<div class='alert alert-success'>{SNAPSHOT_UPLOAD_EXPLAIN}</div>
			<div class='center'>$btn</div>
			";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function support_tool_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $btn=$tpl->button_upload("{upload_a_file} (*.tar.gz)",$page,null,"&support-tool-uploaded=yes");
    $html="<div class='center'>$btn</div>";
    echo $tpl->_ENGINE_parse_body($html);
}
function upload_progress_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$filename=urlencode($_GET["upload-progress-popup"]);

    $jsrestart=$tpl->framework_buildjs("/system/snapshots/uploaded/$filename",
    "backup.upload.progress","backup.upload.progress.txt",
        "progress-snapshot-upload",
        "if(typeof dialogInstance6 == 'object'){ dialogInstance6.close();}LoadAjax('table-snapshots-list','$page?table=yes&admintrack-snapshot-uploaded=$filename')"
    );

	echo "<div class='row'><div id='progress-snapshot-upload'></div><script>$jsrestart</script>";
    return true;
}
function support_tool_progress_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=urlencode($_GET["support-tool-progress-popup"]);

    $jsrestart=$tpl->framework_buildjs("/system/supporttool/uploaded/$filename",
        "backup.upload.progress","backup.upload.progress.txt",
        "support-tool-progress-popup",
        "if(typeof dialogInstance6 == 'object'){ dialogInstance6.close();}LoadAjax('table-snapshots-list','$page?table=yes&admintrack-snapshot-uploaded=$filename')"
    );
    echo "<div class='row'><div id='support-tool-progress-popup'></div><script>$jsrestart</script>";
    return true;

}

function restore_confirm_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$filename=$_GET["restore-confirm-popup"];

    $jsrestart=$tpl->framework_buildjs("/system/snapshots/nohupprestore/$filename",
        "backup.artica.progress","backup.artica.progress.txt",
        "progress-snapshot-restore",
        "if(typeof dialogInstance6 == 'object'){ dialogInstance6.close();}LoadAjax('table-snapshots-list','$page?table=yes&admin-track-restore=$filename')"
    );

	echo "<div class='row'><div id='progress-snapshot-restore'></div><script>$jsrestart</script>";
    return true;
}

function backup_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $jsrestart=$tpl->framework_buildjs("/system/snapshots/execute",
    "backup.artica.progress","backup.artica.progress.txt",
        "progress-snapshot-backup","if(typeof dialogInstance6 == 'object'){ dialogInstance6.close();}LoadAjax('table-snapshots-list','$page?table=yes')");

    admin_tracks("Create a new artica backup snapshot");
	echo "<div class='row'><div id='progress-snapshot-backup'></div><script>$jsrestart</script>";
    return true;
}
function download_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$filename=$_GET["download-popup"];
	$filenameenc=urlencode($filename);
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/backup.artica.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/backup.artica.progress.txt";
	$ARRAY["CMD"]="artica.php?prepare-download=$filenameenc";
	$ARRAY["TITLE"]="$filename";
	$ARRAY["AFTER"]="if(typeof dialogInstance6 == 'object'){ dialogInstance6.close();}Loadjs('$page?realdownload-js=$filenameenc')";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-snapshot-backup')";
	echo "<div class='row'><div id='progress-snapshot-backup'></div><script>$jsrestart</script>";
}

function test_nas_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	echo "<div class='row'><div id='progress-snapshot-backup'></div><script>LoadAjax('progress-snapshot-backup','$page?tests-nas-results=success&class=text-success')</script>";
}
function test_nas_results():bool{
    $tpl=new template_admin();
    $sock=new sockets();
    $sock->REST_API_TIMEOUT=20;
    $data=$sock->REST_API("/system/snapshots/testnas");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error} 376||".json_last_error_msg());
        return true;
    }
    if(!$json->Status){
        echo $tpl->div_error("{error} 380||$json->Error");
        return true;
    }

    echo $tpl->widget_vert("N.A.S","{success}","",ico_networks,567);
    return true;
}

function table_start(){$page=CurrentPageName();echo "<div id='table-snapshots-list'></div><script>LoadAjax('table-snapshots-list','$page?table=yes');</script>";}

function delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$filename=$_GET["delete-js"];
	$filenameec=urlencode($_GET["delete-js"]);
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/backup.artica.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/backup.artica.progress.txt";
	$ARRAY["CMD"]="artica.php?snapshot-remove=$filenameec";
	$ARRAY["TITLE"]="{delete} $filename";
	$ARRAY["AFTER"]="LoadAjax('table-snapshots-list','$page?table=yes')";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-snapshot-restart')";
	$tpl->js_confirm_delete($filename, "delete", $filename,"$jsrestart");
}
function delete_perform(){
    admin_tracks("Delete a snapshot {$_POST["delete"]}");

}

function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{snapshots}",
        "fa fa-archive","{SNAPSHOTS_EXPLAIN}","$page?tabs=yes",
        "snapshots","progress-snapshot-restart",false,"table-snapshot");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall("{snapshots}");
        return true;
    }

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function tabs():bool{
    $page               = CurrentPageName();
	$tpl                = new template_admin();
	$users              = new usersMenus();
    $EnableSynoBackup   =   intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSynoBackup"));
    $EnableURBackup     =   intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableURBackup"));
    $APP_URBACKUP_INSTALLED = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_URBACKUP_INSTALLED"));

        VERBOSE("EnableURBackup = $EnableURBackup",__LINE__);
        VERBOSE("APP_URBACKUP_INSTALLED = $APP_URBACKUP_INSTALLED",__LINE__);
        if($APP_URBACKUP_INSTALLED==0){$EnableURBackup=0;}
        if($EnableURBackup==1){
            $array["{APP_URBACKUP}"]="fw.artica.backup.urbackup.php";
        }

        if($EnableSynoBackup==1) {
            $array["Synology"] = "fw.artica.backup.synology.php";
        }
        $array["{backup_artica}"]="$page?parameters=yes";


	$array["{snapshots}"]="$page?table-start=yes";

    if($users->CORP_LICENSE){
        $array["{events}"]="fw.artica.backup.events.php";

	}
    $array["{categories}"]="fw.artica.backup.categories.php";
    $array["{statistics} {retentions}"]="fw.retention.php?parameters=yes";
    if($users->AsSystemAdministrator) {
        $array["{import} v3x"] = "fw.artica.import3x.php";
    }

    echo $tpl->tabs_default($array);
    return true;
}

function nas_enable():bool{
    $page=CurrentPageName();
    $val=intval($_GET["nas-enable"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("BackupArticaBackUseNas",$val);
    echo "Loadjs('$page?parameters-tiny=yes');\n";
    echo "LoadAjax('artica-backup-parameters-static','$page?parameters2=yes');\n";
    return admin_tracks("Backup Artica using N.A.S = $val");
}
function nas_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $BackupArticaBackUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaBackUseNas"));
    $BackupArticaBackNASIpaddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaBackNASIpaddr"));
    $BackupArticaBackNASFolder=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaBackNASFolder"));

    $BackupArticaBackNASFolder2=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaBackNASFolder2"));

    if(strlen($BackupArticaBackNASFolder2)<3){
        $BackupArticaBackNASFolder2 = "artica-backup-syslog";
    }



    $BackupArticaBackNASUser=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaBackNASUser"));
    $BackupArticaBackNASPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaBackNASPassword"));


    if(!ValidateHostnameOrIP($BackupArticaBackNASIpaddr)){
        $BackupArticaBackNASIpaddr="";
    }
    $BackupArticaBackNASFolder=strip_tags($BackupArticaBackNASFolder);

    $form[]=$tpl->field_checkbox("BackupArticaBackUseNas","{use_remote_nas}",$BackupArticaBackUseNas,true);
    $form[]=$tpl->field_text("BackupArticaBackNASIpaddr", "{hostname}", $BackupArticaBackNASIpaddr);
    $form[]=$tpl->field_text("BackupArticaBackNASFolder", "{shared_folder}", $BackupArticaBackNASFolder);
    $form[]=$tpl->field_text("BackupArticaBackNASFolder2", "{storage_directory}", $BackupArticaBackNASFolder2);


    $form[]=$tpl->field_text("BackupArticaBackNASUser", "{username}", $BackupArticaBackNASUser);
    $form[]=$tpl->field_password2("BackupArticaBackNASPassword", "{password}", $BackupArticaBackNASPassword);
    $jsafter="dialogInstance1.close();Loadjs('$page?parameters-tiny=yes');LoadAjax('artica-backup-parameters-static','$page?parameters2=yes');";

    echo $tpl->form_outside("{backup_your_snapshots}", $form,"{BACKUPARTICA_TYPE_NAS_EXPLAIN}","{apply}",$jsafter,"AsSystemAdministrator",false);
    return true;
}

function ValidateHostnameOrIP($input) {

    if (filter_var($input, FILTER_VALIDATE_IP)) {
        return true;
    }
    $hostnamePattern = '/^(?!:\/\/)([a-zA-Z0-9-_]{1,63}\.?)+(?:[a-zA-Z]{2,})$/';

    if (preg_match($hostnamePattern, $input)) {
        return true;
    }
    return false;
}

function parameters():bool{
    $page=CurrentPageName();
    echo "<div id='artica-backup-parameters-static'></div>";
    echo "<script>LoadAjax('artica-backup-parameters-static','$page?parameters2=yes');</script>";
    return true;
}

function parameters2(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SnapShotsStorageDirectory=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotsStorageDirectory"));
    $SnapShotsPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotsPassword"));
    $SnapShotsCategories=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotsCategories"));
    $CCENCRYPT_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CCENCRYPT_INSTALLED"));

    if($CCENCRYPT_INSTALLED==0){
        //echo $tpl->div_warning("{legend_uninstall}||{CCENCRYPT_NOT_INSTALLED}");
        $SnapShotsPassword="";
    }

    if($SnapShotsStorageDirectory==null){$SnapShotsStorageDirectory="/home/artica/snapshots";}

    $SnapShotStorageMax=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotStorageMax"));
    if($SnapShotStorageMax<1){$SnapShotStorageMax=3;}

    $tpl->table_form_section("{backup_parameters}");
    $tpl->table_form_field_js("Loadjs('$page?parameters-dirs=yes')","AsSystemAdministrator");
    $tpl->table_form_field_text("{storage_directory}","<span style='text-transform:none'>$SnapShotsStorageDirectory</span>",ico_directory);
    $tpl->table_form_field_text("{max_backup_containers}",$SnapShotStorageMax,ico_directory);
    $tpl->table_form_field_bool("{include_personal_categories}", $SnapShotsCategories,ico_database);
    
    if(strlen($SnapShotsPassword)>2) {
        $tpl->table_form_field_bool("{encryption}", 1, ico_lock);
    }else{
        $tpl->table_form_field_bool("{encryption}", 0, ico_lock);
    }

    $BackupArticaBackUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaBackUseNas"));
    $BackupArticaBackNASIpaddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaBackNASIpaddr"));
    $BackupArticaBackNASFolder=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaBackNASFolder"));
    $BackupArticaBackNASUser=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaBackNASUser"));

    $tpl->table_form_field_js("Loadjs('$page?nas-js=yes')","AsSystemAdministrator");
    if($BackupArticaBackUseNas==0){
        $tpl->table_form_field_bool("{use_remote_nas}", 0, ico_backup_remote);
    }else{
        $NasErr=false;
        if(strlen($BackupArticaBackNASIpaddr)<3){
            $NasErr=true;
        }
        if(strlen($BackupArticaBackNASFolder)<3){
            $NasErr=true;
        }
        $tpl->table_form_field_text("{use_remote_nas}","$BackupArticaBackNASUser@$BackupArticaBackNASIpaddr/$BackupArticaBackNASFolder",ico_backup_remote,$NasErr);
    }


    $InfluxAdminRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxAdminRetentionTime"));
    $SystemEventsRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SystemEventsRetentionTime"));
    $PDNSStatsRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSStatsRetentionTime"));
    if($InfluxAdminRetentionTime==0){$InfluxAdminRetentionTime=365;}
    if($SystemEventsRetentionTime==0){$SystemEventsRetentionTime=15;}
    if($PDNSStatsRetentionTime==0){$PDNSStatsRetentionTime=5;}

    $LogsRotateDefaultSizeRotation=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsRotateDefaultSizeRotation");
    if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}

    $tpl->table_form_field_js("Loadjs('fw.retention.php?js=yes')","AsSystemAdministrator");

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $InfluxAdminRetentionTime=5;
        $SystemEventsRetentionTime=7;
        $PDNSStatsRetentionTime=5;
        $tpl->table_form_field_js("");
    }
    $tpl->table_form_section("{statistics_retention_parameters}");
    $tpl->table_form_field_text("{retention_days} ({general})","$InfluxAdminRetentionTime {days}",ico_timeout);
    $tpl->table_form_field_text("{PDNSStatsRetentionTime}","$PDNSStatsRetentionTime {days}",ico_timeout);
    $tpl->table_form_section("{logs_retention_parameters}");
    $tpl->table_form_field_text("{SystemEventsRetentionTime}","$SystemEventsRetentionTime {days}",ico_timeout);
    $tpl->table_form_field_text("{remove_if_files_exceed}","$LogsRotateDefaultSizeRotation"."MB",ico_timeout);

    $html[]=$tpl->table_form_compile();
    $html[]="<script>Loadjs('$page?parameters-tiny=yes');</script>";
    echo $tpl->_ENGINE_parse_body($html);

}

function parameters_dir_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$SnapShotsStorageDirectory=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotsStorageDirectory"));
	$SnapShotsPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotsPassword"));
    $CCENCRYPT_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CCENCRYPT_INSTALLED"));
    $SnapShotsCategories=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotsCategories"));

    if($CCENCRYPT_INSTALLED==0){
        echo $tpl->div_warning("{legend_uninstall}||{CCENCRYPT_NOT_INSTALLED}");
    }
	if($SnapShotsStorageDirectory==null){$SnapShotsStorageDirectory="/home/artica/snapshots";}
	$SnapShotStorageMax=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotStorageMax"));
	if($SnapShotStorageMax<1){$SnapShotStorageMax=3;}
	
	$form[]=$tpl->field_browse_directory("SnapShotsStorageDirectory", "{storage_directory}", $SnapShotsStorageDirectory);
	$form[]=$tpl->field_numeric("SnapShotStorageMax","{max_backup_containers}",$SnapShotStorageMax);
    $form[]=$tpl->field_checkbox("SnapShotsCategories","{include_personal_categories}",$SnapShotsCategories);
	$form[]=$tpl->field_password2("SnapShotsPassword", "{passphrase} ({optional})", $SnapShotsPassword);
    echo $tpl->form_outside("{parameters}", $form,null,"{apply}","LoadAjax('artica-backup-parameters-static','$page?parameters2=yes');dialogInstance1.close();","AsSystemAdministrator",true);
    return true;



}
function parameters_buttons():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $BackupArticaBackUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaBackUseNas"));
    $BackupArticaSnaps=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaSnaps"));

    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $btns[]="<label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?backup-js=yes')\"><i class='fad fa-archive'></i> {create_a_snapshot} </label>";
    if($BackupArticaBackUseNas==1){
        $btns[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?nas-enable=0')\"><i class='fa-duotone fa-server'></i> {use_remote_nas} ON</label>";
        $btns[]="
			<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?test-nas-js=yes')\">
				<i class='fas fa-vials'></i> NAS:{test_connection} </label>
			</div>";

    }else{
        $btns[]="<label class=\"btn btn btn-default\" OnClick=\"Loadjs('$page?nas-enable=1')\">
                <i class='fa-duotone fa-server'></i> {use_remote_nas} OFF</label>";
    }
    if($BackupArticaSnaps==1){
        $btns[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?schedule-js=yes')\"><i class='fa fa-clock'></i> {schedule} ON</label>";

    }else{
        $btns[]="<label class=\"btn btn btn-default\" OnClick=\"Loadjs('$page?schedule-js=yes')\"><i class='fa fa-clock'></i> {schedule} OFF</label>";
    }

    $btns[]="</div>";

    $btns_html=$tpl->_ENGINE_parse_body($btns);

    $TINY_ARRAY["TITLE"]="{snapshots}: {parameters}";
    $TINY_ARRAY["ICO"]="fa fa-archive";
    $TINY_ARRAY["EXPL"]="{SNAPSHOTS_EXPLAIN}";
    $TINY_ARRAY["URL"]="snapshots";
    $TINY_ARRAY["BUTTONS"]=$btns_html;

    header("content-type: application/x-javascript");
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo $jstiny."\n";
    return true;
}

function parameters_save():bool{
	$tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(isset($_POST["BackupArticaBackNASIpaddr"])){
        if(!ValidateHostnameOrIP($_POST["BackupArticaBackNASIpaddr"])){
            $tpl->post_error($_POST["BackupArticaBackNASIpaddr"]." Invalid");
        }
    }
    if(isset($_POST["BackupArticaBackNASFolder"])){
        $_POST["BackupArticaBackNASFolder"]=strip_tags($_POST["BackupArticaBackNASFolder"]);
    }
    $tpl->SAVE_POSTs();
    return  admin_tracks_post("Saving Artica Backup parameters");
}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    if(isset($_GET["admin-track-restore"])){
        admin_tracks("Restored a snapshot {$_GET["admin-track-restore"]}");
    }
    if(isset($_GET["admintrack-snapshot-uploaded"])){
        admin_tracks("Uploaded a snapshot {$_GET["admintrack-snapshot-uploaded"]}");
    }
	


    $topbuttons[] = array("Loadjs('$page?backup-js=yes')", ico_save, "{create_a_snapshot}");
    $topbuttons[] = array("Loadjs('$page?upload-js=yes');", ico_upload, "{upload_snapshot}");
    $topbuttons[] = array("Loadjs('$page?support-tool-js=yes');", ico_upload, "{attach_support_tool}");

    $TH="data-sortable=true class='text-capitalize' data-type='text'";
	
	$html[]="<table id='table-snapshots-lists' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th $TH>&nbsp;</th>";
	$html[]="<th $TH>{date}</th>";
	$html[]="<th $TH>{filename}</center></th>";
    $html[]="<th $TH>{description}</center></th>";
	$html[]="<th $TH>{size}</center></th>";
	$html[]="<th $TH><center>{restore}</center></th>";
	$html[]="<th $TH><center>{delete}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
			
	$SnapShotList=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotList"));
    $q=new lib_sqlite("/home/artica/SQLITE/snapshots.db");
	$TRCLASS=null;
    $td1="style='width:1%' nowrap";

	foreach ($SnapShotList as $filename=>$array){
        $MD5=null;
    	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5($filename);
        $explain="&nbsp;";
	    $time=$tpl->time_to_date($array["TIME"],true);
		$size=FormatBytes($array["SIZE"]/1024);
        if(isset($array["MD5"])){$MD5=$array["MD5"];}
        $content="";
        $ico_cont=$tpl->icon_loupe(false,"");
		$filename_enc=urlencode($filename);
        if($MD5<>null) {
            $ligne = $q->mysqli_fetch_array("SELECT * FROM snapshots WHERE zmd5='$MD5'");
            if ($ligne["ID"] > 0) {
                $explain = "<small>" . $tpl->td_href($ligne["name"], null, "Loadjs('$page?js-desc={$ligne["ID"]}')") . "</small>";
                $content = $ligne["content"];
            }
        }

        if(strlen($content)>20){
            $ico_cont=$tpl->icon_loupe(true,"Loadjs('$page?js-content={$ligne["ID"]}')");
        }



		$filename=$tpl->td_href($filename,"{download}","Loadjs('$page?download-js=$filename_enc')");
		$html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $td1>$ico_cont</td>";
		$html[]="<td $td1>$time</td>";
        $html[]="<td>$filename</td>";
        $html[]="<td>$explain</td>";
		$html[]="<td $td1>$size</td>";
		$html[]="<td $td1>".$tpl->icon_run("Loadjs('$page?restore-js=$filename_enc&id=$md')","AsDnsAdministrator")."</center></td>";
		$html[]="<td $td1>".$tpl->icon_delete("Loadjs('$page?delete-js=$filename_enc&id=$md')","AsDnsAdministrator")."</center></td>";
		$html[]="</tr>";
			
    }
		
		
		$html[]="</tbody>";
		$html[]="<tfoot>";
		$html[]="<tr>";
		$html[]="<td colspan='6'>";
		$html[]="<ul class='pagination pull-right'></ul>";
		$html[]="</td>";
		$html[]="</tr>";
		$html[]="</tfoot>";
		$html[]="</table>";


    $TINY_ARRAY["TITLE"]="{snapshots}: {containers}";
    $TINY_ARRAY["ICO"]="fa fa-archive";
    $TINY_ARRAY["EXPL"]="{SNAPSHOTS_EXPLAIN}";
    $TINY_ARRAY["URL"]="snapshots";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);

    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



		$html[]="
		<script>
			NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
			$(document).ready(function() { $('#table-snapshots-lists').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
			$jstiny
		</script>";
		

		echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
}

function statistics(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $InfluxAdminRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxAdminRetentionTime"));
    $SystemEventsRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SystemEventsRetentionTime"));
    if($InfluxAdminRetentionTime==0){$InfluxAdminRetentionTime=365;}
    if($SystemEventsRetentionTime==0){$SystemEventsRetentionTime=15;}
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $InfluxAdminRetentionTime=5;
        $SystemEventsRetentionTime=7;
    }

    $form[]=$tpl->field_numeric("InfluxAdminRetentionTime","{retention_days}",$InfluxAdminRetentionTime);
    $form[]=$tpl->field_numeric("SystemEventsRetentionTime","{SystemEventsRetentionTime} ({days})",$SystemEventsRetentionTime);

    $tpl->form_add_button("{clean_databases}","Loadjs('$page?clean-now=yes')");

    $html=$tpl->form_outside("{statistics_retention_parameters}",$form,null,"{apply}",null,"AsSystemAdministrator",true);


    echo $tpl->_ENGINE_parse_body($html);

}