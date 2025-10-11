<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["none"])){exit;}
if(isset($_POST["PostgreSQLFTPEnable"])){backup_ftp_save();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["delete-js"])){delete_table_js();exit;}
if(isset($_POST["delete"])){delete_table();exit;}
if(isset($_GET["table"])){page();exit;}
if(isset($_GET["start-backup"])){backup_js();exit;}
if(isset($_GET["down"])){download();exit;}
if(isset($_GET["backup-perform-js"])){backup_perform_js();exit;}
if(isset($_GET["backup-perform-popup"])){backup_perform_popup();exit;}

if(isset($_GET["backup-import-js"])){backup_import_js();exit;}
if(isset($_GET["backup-import-popup"])){backup_import_popup();exit;}

if(isset($_GET["backup-parameters-js"])){backup_parameters_js();exit;}
if(isset($_GET["backup-params-start"])){backup_parameters_start();exit;}
if(isset($_GET["backup-params-popup"])){backup_parameters_popup();exit;}

if(isset($_GET["storage-dir-js"])){storage_dir_js();exit;}
if(isset($_GET["storage-dir-popup"])){storage_dir_popup();exit;}
if(isset($_POST["InFluxBackupDatabaseDir"])){storage_dir_save();exit;}


if(isset($_GET["restore-backup-js"])){backup_restore_js();exit;}
if(isset($_GET["restore-confirm-js"])){backup_restore_confirm_js();exit;}
if(isset($_GET["restore-confirm-popup"])){backup_restore_confirm_popup();exit;}

if(isset($_GET["backup-ftp-js"])){backup_ftp_js();exit;}
if(isset($_GET["backup-ftp-popup"])){backup_ftp_popup();exit;}

start();

function backup_restore_js(){
    $ID=$_GET["restore-backup-js"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE_TEMP/pg_tables.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM backup WHERE ID=$ID");
    $filename=basename($ligne["filename"]);
    $fsize=$ligne["filesize"];
    $fsize=FormatBytes($fsize/1024);
    $tpl->js_confirm_execute("{restore}: $filename - $fsize", "none", $ID,"Loadjs('$page?restore-confirm-js=$ID')");

}
function backup_restore_confirm_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=$_GET["restore-confirm-js"];
    return $tpl->js_dialog6("{backup} >> {restore}", "$page?restore-confirm-popup=$ID","600");
}
function backup_parameters_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
   return $tpl->js_dialog6("{backup} >> {parameters}","$page?backup-params-start=yes");

}
function backup_parameters_start():bool{
    $page=CurrentPageName();
    echo "<div id='backup-parameters-start'></div>
        <script>LoadAjax('backup-parameters-start','$page?backup-params-popup=yes');</script>";
    return true;
}
function backup_parameters_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $InFluxBackupDatabaseDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("InFluxBackupDatabaseDir");
    if(strlen($InFluxBackupDatabaseDir)<3){
        $InFluxBackupDatabaseDir="/home/artica/influx/backup";
    }
    $PostGresBackupMaxContainers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostGresBackupMaxContainers"));
    if($PostGresBackupMaxContainers==0){
        $PostGresBackupMaxContainers=3;
    }

    $tpl->table_form_field_js("Loadjs('$page?storage-dir-js=yes')","AsDatabaseAdministrator");
    $tpl->table_form_field_text("{storage_directory}",$InFluxBackupDatabaseDir,ico_directory);
    $tpl->table_form_field_text("{max_containers}","$PostGresBackupMaxContainers {containers}",ico_file_zip);
    echo $tpl->table_form_compile();
    return true;
}
function backup_restore_confirm_popup(){
    $tpl=new template_admin();
    $ID=$_GET["restore-confirm-popup"];
    $q=new lib_sqlite("/home/artica/SQLITE_TEMP/pg_tables.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM backup WHERE ID=$ID");
    $filename=urlencode($ligne["filename"]);

    $jsrestart=$tpl->framework_buildjs("/postgresql/restore/$filename",
        "postgres.backup.progress",
        "postgres.backup.log",
        "progress-backup-postgressql-restore",
        "dialogInstance6.close();"
    );

    $html="<div id='progress-backup-postgressql-restore'></div><script>$jsrestart</script>";
    echo $tpl->_ENGINE_parse_body($html);

}
function backup_import_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog6("{backup} >> {import}", "$page?backup-import-popup=yes","600");
}
function storage_dir_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog7("{parameters} >> {storage_directory}", "$page?storage-dir-popup=yes","600");
}
function storage_dir_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $InFluxBackupDatabaseDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("InFluxBackupDatabaseDir");
    if(strlen($InFluxBackupDatabaseDir)<3){
        $InFluxBackupDatabaseDir="/home/artica/influx/backup";
    }
    $PostGresBackupMaxContainers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostGresBackupMaxContainers"));
    if($PostGresBackupMaxContainers==0){
        $PostGresBackupMaxContainers=3;
    }

    $form[]=$tpl->field_browse_directory("InFluxBackupDatabaseDir","{storage_directory}",$InFluxBackupDatabaseDir);
    $form[]=$tpl->field_numeric("PostGresBackupMaxContainers","{max_containers}",$PostGresBackupMaxContainers);
    $html[]=$tpl->form_outside("", $form,"","{apply}",
        "dialogInstance7.close();LoadAjax('backup-parameters-start','$page?backup-params-popup=yes');","AsDatabaseAdministrator");

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function storage_dir_save():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLEAN_POST();
    $InFluxBackupDatabaseDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("InFluxBackupDatabaseDir");
    if(strlen($InFluxBackupDatabaseDir)<3){
        $InFluxBackupDatabaseDir="/home/artica/influx/backup";
    }
    if($InFluxBackupDatabaseDir<>$_POST["InFluxBackupDatabaseDir"]){
        $oldDir=urlencode($InFluxBackupDatabaseDir);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/postgresql/mvbackup/$oldDir");
    }

    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/postgresql/cleanbackups");
    return admin_tracks("Save PotgreSQL backup storage to {$_POST["InFluxBackupDatabaseDir"]}");
}
function backup_ftp_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog6("{backup} >> {ftp_backup} >> {squid_ftp_user}", "$page?backup-ftp-popup=yes","600");
}
function backup_ftp_popup(){
    $tpl=new template_admin();


    $jsafter=$tpl->framework_buildjs("/postgresql/ftpcheck",
        "postgres.ftp.validator.progress","postgres.ftp.validator.log",
    "ftp-posgres-validator");


    $PostgreSQLFTPEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPEnable"));
    $PostgreSQLFTPServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPServer"));
    $PostgreSQLFTPPassive=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPPassive"));
    $PostgreSQLFTPTLS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPTLS"));
    $PostgreSQLFTPDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPDir"));
    $PostgreSQLFTPUser=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPUser"));
    $PostgreSQLFTPPass=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPPass"));

    $html[]="<div id='ftp-posgres-validator'></div>";

    if($PostgreSQLFTPDir==null){
        $PostgreSQLFTPDir="/backup";
    }

    $form[]=$tpl->field_checkbox("PostgreSQLFTPEnable","{enable_feature}",$PostgreSQLFTPEnable,true);
    $form[]=$tpl->field_text("PostgreSQLFTPServer", "{ftp_server}", $PostgreSQLFTPServer);
    $form[]=$tpl->field_checkbox("PostgreSQLFTPPassive","{enable_passive_mode}",$PostgreSQLFTPPassive,false,"{enable_passive_mode_explain}");
    $form[]=$tpl->field_checkbox("TLS","{useTLS}",$PostgreSQLFTPTLS);
    $form[]=$tpl->field_text("PostgreSQLFTPDir", "{target_directory}", $PostgreSQLFTPDir);
    $form[]=$tpl->field_text("PostgreSQLFTPUser", "{ftp_username}", $PostgreSQLFTPUser);
    $form[]=$tpl->field_password("PostgreSQLFTPPass", "{ftp_password}", $PostgreSQLFTPPass);
    $html[]=$tpl->form_outside("", @implode("\n", $form),null,"{apply}",$jsafter,"AsDatabaseAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
}

function backup_ftp_save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();

}
function backup_import_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $bt_upload=$tpl->button_upload("{import} *.gz",$page)."&nbsp;&nbsp;";
    $html="<div id='ca-form-import'><div class='center'>$bt_upload</div></div>
	<div id='progress-backup-postgressql-import'></div>";
    echo $tpl->_ENGINE_parse_body($html);

}
function file_uploaded():bool{
    $tpl = new template_admin();
    $page=CurrentPageName();
    $file = $_GET["file-uploaded"];
    $fileenc=urlencode($file);

    $jsrestart=$tpl->framework_buildjs("/postgresql/uploaded/$fileenc",
        "postgres.backup.progress",
        "postgres.backup.log",
        "progress-backup-postgressql-import",
        "dialogInstance6.close();LoadAjax('backup-postgresql-db','$page?table=yes')");

    echo "document.getElementById('ca-form-import').innerHTML='';\n";
    echo $jsrestart;
    return admin_tracks("Uploaded a new PostgreSQL backup file $file");
}

function download(){

    $ID=$_GET["down"];
    $q=new lib_sqlite("/home/artica/SQLITE_TEMP/pg_tables.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM backup WHERE ID=$ID");
    $filename=basename($ligne["filename"]);
    $fsize=$ligne["filesize"];
    header('Content-type: application/x-gzip');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($ligne["filename"]);

}


function backup_perform_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{backup_database}","$page?backup-perform-popup=yes",650);
}
function backup_perform_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

     $jsrestart=$tpl->framework_buildjs("/postgresql/backup",
         "postgres.backup.progress","postgres.backup.log","progress-backup-restart",
        "dialogInstance1.close();LoadAjax('backup-postgresql-db','$page?table=yes')");

    $html[]="<div id='progress-backup-restart'></div>";
    $html[]="<script>$jsrestart</script>";
    echo @implode("\n",$html);
    return admin_tracks("Perform a backup of the entire postgres database");

}

function backup_js():bool{

    $tpl=new template_admin();
    $page=CurrentPageName();
   return $tpl->js_confirm_execute("{backup_database}","none","Backup Database","Loadjs('$page?backup-perform-js=yes')");

}

function delete_table_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["delete-js"];
    $q=new lib_sqlite("/home/artica/SQLITE_TEMP/pg_tables.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM backup WHERE ID=$ID");
    $filename=basename($ligne["filename"]);
    $fsize=$ligne["filesize"];
    $id=$_GET["id"];
    $fsize=FormatBytes($fsize/1024);
	$tpl->js_confirm_empty("{backup}: $filename - $fsize", "delete", $ID,"$('#$id').remove()");
	
	
}

function delete_table(){
	$sock=new sockets();
	$sock->getFrameWork("postgres.php?backup-delete={$_POST["delete"]}");
}

function start(){
    $page=CurrentPageName();
    echo "<div id='backup-postgresql-db'></div><script>LoadAjax('backup-postgresql-db','$page?table=yes')</script>";

}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE_TEMP/pg_tables.db");
    $sql="CREATE TABLE IF NOT EXISTS `backup` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`filename` TEXT,
		`filesize` integer,
		`zdate` INTEGER
    )";

    $q->QUERY_SQL($sql);

	$html[]="<table id='table-postgrsql-tables' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{filename}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{restore}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{download}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";


    $topbuttons[] = array("Loadjs('$page?start-backup=yes');", ico_save, "{backup_database}");
    $topbuttons[] = array("Loadjs('$page?backup-import-js=yes');", ico_save, "{import_backup}");
    $topbuttons[] = array("Loadjs('$page?backup-parameters-js=yes');", ico_params, "{parameters}");
    $topbuttons[] = array("Loadjs('$page?backup-ftp-js=yes');", ico_save, "{ftp_backup}");

    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICA_POSTGRESQL_VERSION");
    $TINY_ARRAY["TITLE"]="PostgreSQL $version &raquo;&raquo; {backup}";
    $TINY_ARRAY["ICO"]=ico_database;
    $TINY_ARRAY["EXPL"]="{PostgreSQL_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	
	$FORCE_FILTER=null;
	$total=0;
	
	
	
	$sql="SELECT * FROM backup ORDER BY zdate DESC";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $tpl->_ENGINE_parse_body($tpl->FATAL_ERROR_SHOW_128($q->mysql_error));
		exit();
	}
	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		$filename=$ligne["filename"];
		$ID=$ligne["ID"];
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$total_bytes=FormatBytes($ligne["filesize"]/1024);
        $date=$tpl->time_to_date($ligne["zdate"],true);
		$md=md5(serialize($ligne));
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td>$date</td>";
        $html[]="<td>$filename</td>";
        $html[]="<td style='width:1%' nowrap>$total_bytes</td>";

        $html[]="<td style='width:1%' class='center-block' nowrap>
".$tpl->icon_export("Loadjs('$page?restore-backup-js=$ID')"). "</td>";

        $html[]="<td style='width:1%' class='center-block' nowrap>
".$tpl->icon_download("direct:$page?down=$ID"). "</td>";

		$html[]="<td class='center-block' style='width:1%' nowrap>".$tpl->icon_delete("Loadjs('$page?delete-js=$ID&id=$md')","AsSystemAdministrator")."</td>";
		$html[]="</tr>";
	
	
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
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-postgrsql-tables').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	$jstiny
	</script>";	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}