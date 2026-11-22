<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Pentest 2026-11-04 : restore-backup-js/restore-confirm-js/restore-confirm-popup/down/delete-js/id etaient recopies sans encodage dans la page (XSS).
foreach(array("restore-backup-js","restore-confirm-js","restore-confirm-popup","down","delete-js") as $xssk){if(isset($_GET[$xssk])){$_GET[$xssk]=intval($_GET[$xssk]);}}
if(isset($_GET["id"])){$_GET["id"]=preg_replace('/[^A-Za-z0-9_-]/','',(string)$_GET["id"]);}
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
    if(!fw_progress_page_priv("AsDatabaseAdministrator|AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
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
    if(!fw_progress_page_priv("AsDatabaseAdministrator|AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
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

function delete_table():bool{
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/postgresql/delete-backup/".intval($_POST["delete"]));
    return true;
}

function start(){
    $page=CurrentPageName();
    echo "<div id='backup-postgresql-db'></div><script>LoadAjax('backup-postgresql-db','$page?table=yes')</script>";

}
/**
 * Une sauvegarde: identite a gauche (nom du fichier, identifiant en sous-titre),
 * puis la date de production, le volume, et les trois actions.
 *
 * La cle de tri du <tr> est l'horodatage brut: la date affichee est localisee et
 * ne se compare pas -- "12 fevrier" contre "3 mars" trie a l'envers en lexical.
 */
function pgb_row($tpl,$ligne):string{
	$page=CurrentPageName();
	$ID=intval($ligne["ID"]);
	$filename=basename($ligne["filename"]);
	$md=md5(serialize($ligne));
	$zdate=intval($ligne["zdate"]);
	$total_bytes=FormatBytes($ligne["filesize"]/1024);
	$date=$tpl->time_to_date($zdate,true);

	// Les icon_*() rendraient des boutons Bootstrap au milieu des actions plates du
	// tableau awc; on conserve leur controle de privilege (IsSecurity) et on rend
	// les liens au dessin de la table.
	$acts=array();
	$acts[]="<a class='awc-act awc-act-go' title='{restore}' href=\"javascript:blur();\" "
		."onclick=\"Loadjs('$page?restore-backup-js=$ID')\"><i class='fas fa-file-export'></i></a>";
	$acts[]="<a class='awc-act' title='{download}' href=\"javascript:blur();\" "
		."onclick=\"document.location.href='$page?down=$ID'\"><i class='fas fa-download'></i></a>";
	if($tpl->IsSecurity("AsSystemAdministrator")){
		$acts[]="<a class='awc-act awc-act-danger' title='{delete}' href=\"javascript:blur();\" "
			."onclick=\"Loadjs('$page?delete-js=$ID&id=$md')\"><i class='fas fa-trash-can'></i></a>";
	}

	$h ="<tr id='$md' data-sort='$zdate'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico'><i class='fas fa-file-zipper'></i></span>";
	$h.="<span><span class='awc-name'>".htmlspecialchars($filename)."</span>";
	$h.="<div class='awc-meta'>ID: $ID</div>";
	$h.="</span></div></td>";
	$h.="<td class='awc-mono' style='width:1%' nowrap>".htmlspecialchars($date)."</td>";
	$h.="<td style='width:1%' nowrap><span class='awc-seen'>$total_bytes</span></td>";
	$h.="<td style='width:1%'><div class='awc-acts'>".@implode("",$acts)."</div></td>";
	$h.="</tr>";
	return $h;
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

	$sql="SELECT * FROM backup ORDER BY zdate DESC";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $tpl->_ENGINE_parse_body($tpl->FATAL_ERROR_SHOW_128($q->mysql_error));
		exit();
	}

	$rows=array();
	$totalbytes=0;
	foreach ($results as $index=>$ligne){
		$totalbytes=$totalbytes+floatval($ligne["filesize"]);
		$rows[]=pgb_row($tpl,$ligne);
	}
	$total=count($rows);

	$html=array();
	// FooTable fournissait sa propre boite de filtre; le tableau awc ne l'embarque
	// pas, la barre la remplace (meme dessin que search_block, filtrage navigateur).
	$html[]=awc_search_bar("pgb-search");
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{filename}</th>";
	$html[]="<th style='width:1%' nowrap id='pgb-th-date'>{date} <span id='pgb-th-date-arrow'></span></th>";
	$html[]="<th style='width:1%' nowrap>{size}</th>";
	$html[]="<th style='width:1%' nowrap>{actions}</th>";
	$html[]="</tr></thead><tbody id='pgb-rows'>";
	if($total==0){
		$html[]="<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $total / $total {backup} &mdash; ".FormatBytes($totalbytes/1024)."</span>";
	$html[]="<span class='awc-pg' id='pgb-pager'></span>";
	$html[]="</div></div></div>";

    $topbuttons=array();
    $topbuttons[] = array("Loadjs('$page?start-backup=yes');", ico_save, "{backup_database}");
    $topbuttons[] = array("Loadjs('$page?backup-import-js=yes');", ico_save, "{import_backup}");
    $topbuttons[] = array("Loadjs('$page?backup-parameters-js=yes');", ico_params, "{parameters}");
    $topbuttons[] = array("Loadjs('$page?backup-ftp-js=yes');", ico_save, "{ftp_backup}");

    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICA_POSTGRESQL_VERSION");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="PostgreSQL $version &raquo;&raquo; {backup}";
    $TINY_ARRAY["ICO"]=ico_database;
    $TINY_ARRAY["EXPL"]="{PostgreSQL_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	// Feuille et scripts concatenes AUTOUR du parsing: la regex #{(.+?)}# de
	// _ENGINE_parse_body lit chaque corps de regle CSS et chaque bloc JS comme un jeton.
	// Ordre impose: pager d'abord (il publie tb.__awcRedraw), puis tri, puis filtre.
	$foot="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$jstiny</script>";
	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.awc_pager_js(50,"pgb-rows","pgb-pager")
		.awc_sort_js("pgb-th-date","pgb-rows","desc")
		.awc_filter_js("pgb-search","pgb-rows","pgb-pager")
		.$foot;

}
