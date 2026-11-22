<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
// fw_progress_page_priv() / fw_progress_bad_value() : deja charges par class.sessions.inc,
// inclus ici pour qu'un correctif de la seule page ne tombe pas en erreur fatale.
include_once(dirname(__FILE__)."/ressources/class.progress.sign.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

if(isset($_GET["schedule-js"])){schedule_js();exit;}
if(isset($_GET["bks-host"])){bks_host();exit;}
if(isset($_GET["bks-tiny"])){bks_tiny();exit;}
if(isset($_POST["bks-create"])){bks_create_confirm();exit;}
if(isset($_GET["backup-progress-js"])){bks_backup_progress_js();exit;}
if(isset($_GET["schedule-popup"])){schedule_popup();exit;}
if(isset($_POST["BackupArticaSnaps"])){schedule_save();exit;}
if(isset($_GET["parameters-dirs"])){parameters_dir_js();exit;}
if(isset($_GET["parameters-dirs-popup"])){parameters_dir_popup();exit;}

if(isset($_GET["nas-js"])){nas_js();exit;}
if(isset($_GET["remote-js"])){remote_js();exit;}
if(isset($_GET["remote-popup"])){remote_popup();exit;}
if(isset($_GET["azure-js"])){azure_js();exit;}
if(isset($_GET["azure-popup"])){azure_popup();exit;}
if(isset($_POST["SnapShotsRemoteTargetUI"])){remote_save();exit;}
if(isset($_POST["SnapShotsAzureSASURL"]) || isset($_POST["SnapShotsAzureCustomEndpoint"])){azure_save();exit;}
if(isset($_GET["azure-test-js"])){azure_test_js();exit;}
if(isset($_GET["azure-test-results"])){azure_test_results();exit;}
if(isset($_GET["nas-popup"])){nas_popup();exit;}
if(isset($_GET["remote-table-js"])){remote_table_js();exit;}
if(isset($_GET["remote-table"])){remote_table();exit;}
if(isset($_GET["restore-remote-js"])){restore_remote_js();exit;}
if(isset($_GET["restore-remote-popup"])){restore_remote_popup();exit;}
if(isset($_POST["restore_remote_name"])){restore_remote_prepare();exit;}
if(isset($_GET["restore-remote-run-popup"])){restore_remote_run_popup();exit;}

if(isset($_POST["SnapShotStorageMax"])){parameters_save();exit;}
if(isset($_POST["BackupArticaBackUseNas"])){parameters_save();exit;}
if(isset($_POST["BackupArticaBackNASIpaddr"])){parameters_save();exit;}
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
    return $tpl->js_dialog1("modal:{backup_parameters}","$page?parameters-dirs-popup=yes");
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
    echo $tpl->form_outside("{backup_artica_settings}",$form,"{backup_artica_settings_explain}","{apply}","dialogInstance1.close();Loadjs('$page?parameters-tiny=yes');if(document.getElementById('artica-backup-parameters-static')){LoadAjax('artica-backup-parameters-static','$page?parameters2=yes');}");
    return true;
}
function schedule_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("BackupArticaSnaps",$_POST["BackupArticaSnaps"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("BackupArticaSnapsSched",serialize($_POST));
    admin_tracks_post("Saving Schedule settings of Artica backup task");
    // Sans cet appel, les cles sont ecrites et /etc/cron.d/backup-snaphosts
    // n'est jamais regenere : la programmation serait affichee et inerte.
    // (getGoFramework passait par go-exec, absent des appliances : jamais execute.)
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/snapshots/schedule/apply"));
    if(!$json || !property_exists($json,"Status") || !$json->Status){
        $err=($json && property_exists($json,"Error"))?$json->Error:"articarest unreachable";
        echo $tpl->post_error("/system/snapshots/schedule/apply: $err");
        return false;
    }
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
/* Socle des tableaux d'entites: le cadre, la carte et le pager viennent de
 * awc_table_style() / awc_pager_js() (class.statistics_appliance.inc, partages). */

/** En-tete + ouverture du tableau. */
function bkp_table_open($columns,$rowsId):string{
    $h=array();
    $h[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $h[]="<table class='awc-tbl'><thead><tr>";
    foreach($columns as $col){ $h[]=$col; }
    $h[]="</tr></thead><tbody id='$rowsId'>";
    return @implode("\n",$h);
}

/** Pied du tableau: compteur a gauche, pager a droite. */
function bkp_table_close($shown,$total,$unit,$pagerId):string{
    $h=array();
    $h[]="</tbody></table></div>";
    $h[]="<div class='awc-foot'>";
    $h[]="<span>{showing} $shown / $total $unit</span>";
    $h[]="<span class='awc-pg' id='$pagerId'></span>";
    $h[]="</div></div></div>";
    return @implode("\n",$h);
}

/** Ligne vide normalisee (jamais un <tbody> vide). */
function bkp_table_empty($colspan):string{
    return "<tr><td colspan='$colspan'><div class='awc-empty'>{no_data}</div></td></tr>";
}

/** Bloc d'identite: pastille d'icone, nom (deja rendu, il porte son lien), metadonnees. */
function bkp_ident($ico,$name,$meta=array()):string{
    $h ="<div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='$ico'></i></span>";
    $h.="<span><span class='awc-name'>$name</span>";
    $out=array();
    foreach($meta as $m){
        if(is_null($m)){continue;}
        if(strlen(trim(strip_tags(strval($m))))==0){continue;}
        $out[]=$m;
    }
    if(count($out)>0){
        $h.="<div class='awc-meta'>".@implode(" &middot; ",$out)."</div>";
    }
    $h.="</span></div>";
    return $h;
}

/** Cellule d'actions alignee a droite. */
function bkp_acts($items):string{
    if(!is_array($items)){$items=array($items);}
    $out=array();
    foreach($items as $i){
        if(is_null($i)){continue;}
        if(strlen(trim($i))==0){continue;}
        $out[]=$i;
    }
    return "<div class='awc-acts'>".@implode("",$out)."</div>";
}

function snapshot_content_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["content-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/snapshots.db");
    $ligne=$q->mysqli_fetch_array("SELECT content FROM snapshots WHERE ID='$ID'");
    // unserialize() renvoie false sur un contenu tronque ou absent: sans garde, le
    // foreach qui suit levait un warning en PHP 7 et un TypeError en PHP 8.
    $content=@unserialize(strval($ligne["content"]));
    if(!is_array($content)){$content=array();}

    $rows=array();
    foreach ($content as $filename=>$size) {
        if($size>1024) {
            $size = FormatBytes($size / 1024,true);
        }else{
            $size=intval($size)." Bytes";
        }
        $h ="<tr>";
        $h.="<td>".bkp_ident(ico_file,htmlspecialchars(strval($filename)))."</td>";
        $h.="<td class='awc-mono' nowrap>".htmlspecialchars($size)."</td>";
        $h.="</tr>";
        $rows[]=$h;
    }

    $html[]=bkp_table_open(array(
        "<th>{filename}</th>",
        "<th>{size}</th>"),"bkp-rows-content");
    if(count($rows)==0){
        $html[]=bkp_table_empty(2);
    }else{
        $html[]=@implode("\n",$rows);
    }
    $totalFiles=count($rows);
    $html[]=bkp_table_close($totalFiles,$totalFiles,"{files}","bkp-pager-content");
    $html[]="
		<script>
			NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		</script>";
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body($html)
        .awc_pager_js(25,"bkp-rows-content","bkp-pager-content");

return true;
}
function snapshot_description_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["desc-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/snapshots.db");
    $ligne=$q->mysqli_fetch_array("SELECT name FROM snapshots WHERE ID='$ID'");

    $form=array();
    $form[]=$tpl->field_hidden("desc-save",$ID);
    $form[]=$tpl->field_text("snapdesc","{description}",$ligne["name"],true);
    $security="AsSystemAdministrator";
    echo $tpl->form_outside("{description}", @implode("\n", $form),null,"{apply}","dialogInstance6.close();if(document.getElementById('bks-host')){LoadAjaxSilent('bks-host','$page?bks-host=yes');}else{LoadAjax('table-snapshots-list','$page?table=yes');}",$security);
    return true;
}
function snapshot_description_save(){
    $ID=intval($_POST["desc-save"]);
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $desc=$_POST["snapdesc"];

    if(!bks_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $q=new lib_sqlite("/home/artica/SQLITE/snapshots.db");
    $q->QUERY_SQL_PARAMS("UPDATE snapshots SET name=? WHERE ID=?",array((string)$desc,$ID));
    if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
    admin_tracks("Saving snapshot ID $ID description to $desc");
    return true;
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


    $jsrestart=$tpl->framework_buildjs(
        "/postgresql/purge/$CleanNow",
        "squid.statistics.purge.progress",
        "squid.statistics.purge.log",
        "clean-database-progress",
        "if(typeof dialogInstance6 == 'object'){ dialogInstance6.close();}"
    );

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
	if(!bks_is_admin()){$tpl->js_no_privileges();return;}
	$tpl->js_confirm_execute("{bks_restore_confirm}<br><span style='font-family:monospace'>".htmlspecialchars($filename)."</span>", "file-restore", $filename,"Loadjs('$page?restore-confirm-js=$fileencode')");
	
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
// Pentest 2026-11-04 : le nom de l'instantane composait un chemin sous ressources/web/logs
// sans controle ("../" -> test d'existence et taille de n'importe quel fichier). Meme regle
// que le daemon (backup.PrepareDownload) : un simple nom, sans "/", "\\" ni "..".
function artica_backup_snapshot_name($name):string{
	$name=(string)$name;
	if($name==="" || $name!==basename($name) || strpos($name,"..")!==false){return "";}
	if(preg_match('#[/\\\\\x00-\x1f]#',$name)){return "";}
	return $name;
}
function realdownload_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$filename=artica_backup_snapshot_name($_GET["realdownload-js"] ?? "");
	if($filename==""){
		$tpl->js_error_stop("Invalid file name");
		exit();
	}
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
	$filename=artica_backup_snapshot_name($_GET["realdownload-popup"] ?? "");
	if($filename==""){echo "Invalid file name";return;}
    admin_tracks("manually Downloaded a snapshot $filename");
	$filesize=FormatBytes(@filesize("ressources/web/logs/$filename")/1024);
	// Pentest 2026-11-04 : realdownload-popup etait recopie sans encodage dans la page (XSS).
	echo "<center><a href=\"ressources/web/logs/".req_url($filename)."\"><H1>".req_html($filename)." ($filesize)</a></h1></center>";
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
    if(!fw_progress_page_priv("AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
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
    if(!fw_progress_page_priv("AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
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
    if(!fw_progress_page_priv("AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
	$page=CurrentPageName();
	$tpl=new template_admin();
	$filename=fw_progress_restseg($_GET["restore-confirm-popup"] ?? "");
	if($filename===""){return fw_progress_bad_value("restore-confirm-popup");}

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

	echo "<div class='row'><div id='progress-snapshot-backup'></div><script>$jsrestart</script>";
    return true;
}
function download_popup(){
    if(!fw_progress_page_priv("AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
	$page=CurrentPageName();
	$filename=$_GET["download-popup"];
	$filenameenc=urlencode($filename);
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/backup.artica.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/backup.artica.progress.txt";
	$ARRAY["CMD"]="/system/snapshots/prepare/$filenameenc";
	// Pentest 2026-11-04 : download-popup etait recopie sans encodage dans la page (XSS).
	$ARRAY["TITLE"]=req_html($filename);
	$ARRAY["AFTER"]="if(typeof dialogInstance6 == 'object'){ dialogInstance6.close();}Loadjs('$page?realdownload-js=$filenameenc')";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-snapshot-backup')";
	echo "<div class='row'><div id='progress-snapshot-backup'></div><script>$jsrestart</script>";
}

function test_nas_popup():bool{
	$page=CurrentPageName();
	echo "<div class='row'><div id='progress-snapshot-backup'></div><script>LoadAjax('progress-snapshot-backup','$page?tests-nas-results=success&class=text-success')</script>";
    return true;
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
    if(!fw_progress_page_priv("AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
	$page=CurrentPageName();
	$tpl=new template_admin();
	$filename=fw_progress_restseg($_GET["delete-js"] ?? "");
	if($filename===""){return fw_progress_bad_value("delete-js");}
	// The daemon deletes the local copy AND the copy on the active target (NAS, Azure).
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/backup.artica.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/backup.artica.progress.txt";
	$ARRAY["CMD"]="/system/snapshots/delete/$filename";
	$ARRAY["TITLE"]="{delete} $filename";
	$ARRAY["AFTER"]="LoadAjax('table-snapshots-list','$page?table=yes')";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-snapshot-restart')";
	$tpl->js_confirm_delete($filename, "delete", $filename,"$jsrestart");
}
function delete_perform(){
    if(!bks_is_admin()){$tpl=new template_admin();echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
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
    $array["{migration_title}"]="fw.migration.php?build-page=yes";
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
    $tpl=new template_admin();
    $users=new usersMenus();
    header("content-type: application/x-javascript");
    if(!$users->AsSystemAdministrator){return $tpl->js_error("{ERROR_NO_PRIVS2}");}
    $val=intval($_GET["nas-enable"]);
    // La cible distante appartient au daemon (SnapShotsRemoteTarget fait foi) :
    // ecrire seulement BackupArticaBackUseNas laissait le NAS actif sous un bouton OFF.
    $e=snapshot_target_from_nas_switch($val);
    if($e!=""){return $tpl->js_error($e);}
    echo "Loadjs('$page?parameters-tiny=yes');\n";
    echo "LoadAjax('artica-backup-parameters-static','$page?parameters2=yes');\n";
    return admin_tracks("Backup Artica using N.A.S = $val");
}
// Interrupteur NAS historique -> POST /system/snapshots/target.
// 1 : kind "nas" (y compris quand la cible actuelle est Azure).
// 0 : kind "" seulement si la cible actuelle est le NAS ; sinon le NAS est deja
// eteint et rien ne change (eteindre le NAS ne doit pas couper Azure).
// Rend "" en cas de succes, sinon le message d'erreur.
function snapshot_target_from_nas_switch($val):string{
    if(intval($val)==1){
        $kind="nas";
    }else{
        if(remote_target_kind()!="nas"){return "";}
        $kind="";
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/system/snapshots/target",array("kind"=>$kind)));
    if(!$json || !$json->Status){
        return ($json && isset($json->Error)) ? strval($json->Error) : "{error}";
    }
    admin_tracks("Snapshot remote storage set to ".($kind==""?"none":$kind)." (N.A.S switch)");
    return "";
}

function remote_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{remote_storage}","$page?remote-popup=yes",650);
}

function azure_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("Azure Blob","$page?azure-popup=yes",700);
}

function remote_target_kind():string{
    $kind=trim(strval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotsRemoteTarget")));
    if($kind==""){
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaBackUseNas"))==1){return "nas";}
        return "none";
    }
    return $kind;
}

function remote_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $hash=array("none"=>"{none}","nas"=>"N.A.S (SMB)","azure"=>"Azure Blob");
    // $defnull=false : pas d'option vide, sinon un POST vide pourrait effacer la cible
    $form[]=$tpl->field_array_hash($hash,"SnapShotsRemoteTargetUI","{remote_storage}",remote_target_kind(),true,null,null,false);
    // l'id du <select> rendu est md5(nom . suffixid), pas le nom du champ
    $selid=md5("SnapShotsRemoteTargetUI".$tpl->suffixid);
    // Lire le choix AVANT close() : la fermeture detruit le <select>, et
    // getElementById rendait null, si bien qu'"Azure Blob" n'ouvrait jamais le formulaire du SAS.
    $jsafter="var k=document.getElementById('$selid');var kind=k?k.value:'';".
        "dialogInstance1.close();".
        "if(kind=='azure'){Loadjs('$page?azure-js=yes');}".
        "else{LoadAjax('artica-backup-parameters-static','$page?parameters2=yes');}";
    echo $tpl->div_explain("{remote_storage}||{remote_storage_explain}");
    echo $tpl->form_outside("",$form,"","{apply}",$jsafter,"AsSystemAdministrator",false);
    return true;
}

// none / nas : le daemon met a jour SnapShotsRemoteTarget ET BackupArticaBackUseNas.
// azure : enregistre par azure_save (le SAS est obligatoire).
function remote_save():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $tpl->CLEAN_POST();
    $kind=$_POST["SnapShotsRemoteTargetUI"];
    if(!in_array($kind,array("none","nas","azure"),true)){
        echo $tpl->post_error("{error}");
        return false;
    }
    if($kind=="azure"){
        // Le SAS est saisi dans le formulaire Azure ; on n'active rien ici.
        return admin_tracks("Selected Azure Blob as snapshot storage (pending SAS)");
    }
    if($kind=="none"){$kind="";}
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/system/snapshots/target",array("kind"=>$kind)));
    if(!$json || !$json->Status){
        echo $tpl->post_error(($json && isset($json->Error)) ? $json->Error : "{error}");
        return false;
    }
    return admin_tracks("Snapshot remote storage set to ".($kind==""?"none":$kind));
}

function azure_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $state=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/snapshots/target"));
    $html=array();
    if($state && isset($state->password_ok) && !$state->password_ok){
        $html[]=$tpl->div_error("{azure_password_required}");
    }
    if(remote_target_kind()!="azure"){
        $html[]=$tpl->div_warning("{azure_not_active_yet}");
    }
    $html[]=$tpl->div_warning("{azure_password_warning}");
    if($state && isset($state->container)){
        $days=intval($state->days_left);
        $html[]=$tpl->div_explain("{container}: <strong>".htmlspecialchars($state->container)."</strong> &mdash; {expires_in} $days {days}");
    }
    if($state && isset($state->sas_error) && strlen($state->sas_error)>0){
        $html[]=$tpl->div_error(htmlspecialchars($state->sas_error));
    }
    $html[]=$tpl->div_explain("Azure Blob||{azure_blob_explain}");
    // Le SAS stocke n'est jamais renvoye dans la page : champ toujours vide,
    // un envoi vide conserve le SAS enregistre (cote daemon).
    $form[]=$tpl->field_password2("SnapShotsAzureSASURL","{azure_sas_url}","",false,"{azure_sas_url_explain}");
    $form[]=$tpl->field_checkbox("SnapShotsAzureCustomEndpoint","{azure_custom_endpoint}",
        intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotsAzureCustomEndpoint")),false,"{azure_custom_endpoint_explain}");
    $jsafter="dialogInstance1.close();LoadAjax('artica-backup-parameters-static','$page?parameters2=yes');";
    $html[]=$tpl->form_outside("",$form,"","{apply}",$jsafter,"AsSystemAdministrator",false);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function azure_save():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    // Le SAS est exclu du nettoyage : field_password2 l'envoie en
    // encodeURIComponent, et url_decode_special_tool decodait %25 avant
    // %2B/%3D, si bien que sig=AbC%2Bd... arrivait en sig=AbC+d... (lu comme un
    // espace par Azure). Une seule couche a defaire : rawurldecode.
    $tpl->CLEAN_POST("SnapShotsAzureSASURL");
    $payload=array(
        "kind"=>"azure",
        "sas_url"=>trim(rawurldecode(strval($_POST["SnapShotsAzureSASURL"] ?? ""))),
        "custom_endpoint"=>intval($_POST["SnapShotsAzureCustomEndpoint"] ?? 0),
    );
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/system/snapshots/target",$payload));
    if(!$json || !$json->Status){
        echo $tpl->post_error(($json && isset($json->Error)) ? $json->Error : "{error}");
        return false;
    }
    return admin_tracks("Snapshot remote storage set to Azure Blob");
}

function azure_test_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog6("{test_connection}","$page?azure-test-results=yes",500);
}

function azure_test_results():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS2}"));return true;}
    $sock=new sockets();
    $sock->REST_API_TIMEOUT=100;
    $json=json_decode($sock->REST_API("/system/snapshots/target/test"));
    if(!$json){echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}||".json_last_error_msg()));return true;}
    if(!$json->Status){
        $err=isset($json->Error)?$json->Error:"{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}||".htmlspecialchars($err)));
        return true;
    }
    echo $tpl->_ENGINE_parse_body($tpl->widget_vert("Azure Blob","{success}","",ico_networks,567));
    return true;
}

function nas_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
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

    // Pas de case BackupArticaBackUseNas ici : NAS / aucun / Azure se choisit dans
    // {remote_storage}. Ce formulaire ne porte que la connexion au NAS.
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

/* ── Mode plakar actif: cette page devient trompeuse ───────────────────────
 * Avec ArticaSnapshotsViaPlakar=1, le bouton « creer un instantane » de cette
 * page pousse dans le depot plakar, son tableau ne liste plus que d'ANCIENS
 * tarballs, et ses reglages NAS / SnapShotStorageMax ne sont plus lus par
 * personne (CleanSnapShots n'est plus appele). Rien ne le disait.
 * Rend "" quand le mode est inactif, donc l'appel est sans effet sur la
 * quasi-totalite des appliances. */
function plakar_mode_warning():string{
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaSnapshotsViaPlakar"))!=1){return "";}
    $tpl=new template_admin();
    // Pentest 2026-11-04 : les bandeaux filtrent OnClick, un lien simple suffit.
    $link="<a href=\"fw.artica.backup.plakar.php\">".
        $tpl->javascript_parse_text("{articabackup_via_plakar}")."</a>";
    return $tpl->div_warning("{articabackup_plakar_mode_active}||{articabackup_plakar_mode_active_explain}<div style='margin-top:10px'>$link</div>");
}

/**
 * Onglet "Sauvegarde Artica" (maquette stitch_artica_backup_dashboard_redesign) :
 * bandeau Plakar, 3 tuiles de protection, carte des instantanes locaux, puis la
 * conservation des donnees (statistiques, journaux). Une fonction par widget ;
 * CSS et JS concatenes HORS du moteur de traduction.
 */
function parameters2(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=array();
    $html[]="<div class='bk-wrap ak'>";
    $html[]=bk_plakar_banner();
    $html[]="<div class='ak-grid-3 bk-tiles'>";
    $html[]=bk_tile_schedule();
    $html[]=bk_tile_remote();
    $html[]=bk_tile_encryption();
    $html[]="</div>";
    $html[]=bk_card_local();
    $html[]=bk_retention_section();
    $html[]="</div>";
    echo bk_style().$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>Loadjs('$page?parameters-tiny=yes');</script>";
}

/** Bouton "Modifier" d'une tuile ou d'une carte. */
function bk_button($js,$label="{modify}",$ico="fas fa-pen"):string{
    return "<button type='button' class='ak-btn' onclick=\"$js\"><span><i class='$ico'></i></span>$label</button>";
}

/** En-tete d'une tuile : titre en capitales et pastille d'icone. */
function bk_tile_head($title,$ico,$warn=false):string{
    return "<div class='bk-tile-head'><span class='ak-fig-l'>$title</span>"
        ."<span class='ak-ico ak-ico-sm ak-ico-sq".($warn?" ak-warn":"")."'><i class='$ico'></i></span></div>";
}

/** Bandeau du mode Plakar : les reglages NAS et du nombre d'instantanes n'y sont plus lus. */
function bk_plakar_banner():string{
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaSnapshotsViaPlakar"))!=1){return "";}
    $h="<div class='ak-banner ak-warn bk-banner' role='status'><span><i class='fas fa-triangle-exclamation'></i></span>";
    $h.="<div><strong>{articabackup_plakar_mode_active}</strong><p>{articabackup_plakar_mode_active_explain}</p>";
    $h.="<a class='bk-link' href='fw.artica.backup.plakar.php'>{articabackup_via_plakar} <span aria-hidden='true'>&rarr;</span></a></div></div>";
    return $h;
}

/** Tuile 1 : planification des instantanes (BackupArticaSnaps + BackupArticaSnapsSched). */
function bk_tile_schedule():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $on=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaSnaps"))==1;
    $sched=@unserialize((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaSnapsSched"));
    if(!is_array($sched)){$sched=array();}
    $day=intval($sched["DAY"] ?? 2); if($day<1){$day=2;}
    $hour=intval($sched["HOUR"] ?? 1);
    $min=intval($sched["MIN"] ?? 30);

    $h="<div class='ak-card bk-tile'>".bk_tile_head("{schedule}","far fa-clock");
    if($on){
        $every=$day==1?"{bk_every_day}":str_replace("%s",$day,$tpl->_ENGINE_parse_body("{bk_every_n_days}"));
        $at=str_replace("%s","<strong>".sprintf("%02d:%02d",$hour,$min)."</strong>",$tpl->_ENGINE_parse_body("{bk_run_at}"));
        $h.="<div class='bk-state'><span class='ak-pill ak-ok'><span class='ak-dot'></span>{enabled}</span><span class='bk-state-txt'>$every</span></div>";
        $h.="<p class='bk-tile-text'>$at</p>";
    }else{
        $h.="<div class='bk-state'><span class='ak-pill ak-off'><span class='ak-dot'></span>{disabled}</span></div>";
        $h.="<p class='bk-tile-text'>{bk_schedule_off}</p>";
    }
    $h.="<div class='bk-tile-foot'>".bk_button("Loadjs('$page?schedule-js=yes')")."</div></div>";
    return $h;
}

/** Tuile 2 : stockage distant (aucun, NAS SMB ou Azure Blob) et test de connexion. */
function bk_tile_remote():string{
    $page=CurrentPageName();
    $kind=remote_target_kind();
    $s=$GLOBALS["CLASS_SOCKETS"];
    $warns=array();
    $body="";
    $test="";
    $edit="Loadjs('$page?remote-js=yes')";

    if($kind=="nas"){
        $ip=trim($s->GET_INFO("BackupArticaBackNASIpaddr"));
        $folder=trim($s->GET_INFO("BackupArticaBackNASFolder"));
        $user=trim($s->GET_INFO("BackupArticaBackNASUser"));
        $title="N.A.S (SMB)";
        $body.="<dl class='bk-dl'>";
        $body.="<dt>{bk_nas_server}</dt><dd>".($ip==""?"—":htmlspecialchars($ip))."</dd>";
        $body.="<dt>{bk_nas_folder}</dt><dd>".($folder==""?"—":htmlspecialchars($folder))."</dd>";
        $body.="<dt>{bk_nas_account}</dt><dd>".($user==""?"—":htmlspecialchars($user))."</dd>";
        $body.="</dl>";
        if(strlen($ip)<3 || strlen($folder)<3){$warns[]="{bk_nas_incomplete}";}
        $edit="Loadjs('$page?nas-js=yes')";
        $test="Loadjs('$page?test-nas-js=yes')";
    }elseif($kind=="azure"){
        $state=json_decode((string)$s->REST_API("/system/snapshots/target"));
        $title="Azure Blob";
        if(is_object($state) && isset($state->container)){
            $days=intval($state->days_left ?? 0);
            $body.="<dl class='bk-dl'><dt>{container}</dt><dd>".htmlspecialchars((string)$state->container)."</dd>";
            $body.="<dt>{expires_in}</dt><dd>$days <span class='bk-lc'>{days}</span></dd></dl>";
            if($days<4){$warns[]="{bk_azure_expiring}";}
            if(empty($state->password_ok)){$warns[]="{bk_azure_password}";}
        }else{
            $warns[]="{not_configured}";
        }
        $edit="Loadjs('$page?azure-js=yes')";
        $test="Loadjs('$page?azure-test-js=yes')";
    }else{
        $title="{bk_remote_none}";
        $body="<p class='bk-tile-text'>{bk_remote_none_explain}</p>";
    }

    $warn=count($warns)>0;
    $h="<div class='ak-card bk-tile".($warn?" bk-tile-warn":"")."'>".bk_tile_head("{remote_storage}","fas fa-cloud",$warn);
    $h.="<div class='bk-state'><span class='bk-state-txt'>$title</span></div>$body";
    foreach($warns as $w){$h.="<div class='bk-alert'><i class='fas fa-triangle-exclamation'></i> <span>$w</span></div>";}
    $h.="<div class='bk-tile-foot'>";
    if($test<>""){$h.=bk_button($test,"{test_connection}","fas fa-plug");}
    $h.=bk_button($edit)."</div></div>";
    return $h;
}

/** Tuile 3 : chiffrement des instantanes (phrase de passe SnapShotsPassword). */
function bk_tile_encryption():string{
    $page=CurrentPageName();
    $on=strlen(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotsPassword")))>2;
    $h="<div class='ak-card bk-tile".($on?"":" bk-tile-warn")."'>".bk_tile_head("{encryption}",$on?"fas fa-lock":"fas fa-lock-open",!$on);
    if($on){
        $h.="<div class='bk-state'><span class='ak-pill ak-ok'><i class='fas fa-check'></i> {bk_encrypted}</span></div>";
        $h.="<p class='bk-tile-text'>{snapshot_password_explain}</p>";
    }else{
        $h.="<div class='bk-state'><span class='ak-pill ak-warn'><i class='fas fa-lock-open'></i> {bk_not_encrypted}</span></div>";
        $h.="<p class='bk-tile-text'>{bk_not_encrypted_explain}</p>";
    }
    $h.="<div class='bk-tile-foot'>".bk_button("Loadjs('$page?parameters-dirs=yes')")."</div></div>";
    return $h;
}

/** Carte : instantanes locaux (repertoire, nombre conserve, categories). */
function bk_card_local():string{
    $page=CurrentPageName();
    $s=$GLOBALS["CLASS_SOCKETS"];
    $dir=trim($s->GET_INFO("SnapShotsStorageDirectory"));
    if($dir==""){$dir="/home/artica/snapshots";}
    $max=intval($s->GET_INFO("SnapShotStorageMax"));
    if($max<1){$max=3;}
    $cat=intval($s->GET_INFO("SnapShotsCategories"))==1;

    $h="<section class='ak-card bk-card'>";
    $h.="<div class='bk-card-head'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='far fa-folder'></i></span>";
    $h.="<div class='bk-card-titles'><h2>{bk_local_title}</h2><p>{bk_local_explain}</p></div>";
    $h.=bk_button("Loadjs('$page?parameters-dirs=yes')")."</div>";
    $h.="<div class='bk-cols'>";
    $h.="<div class='bk-col'><span class='ak-fig-l'>{storage_directory}</span><span class='bk-path'>".htmlspecialchars($dir)."</span></div>";
    $h.="<div class='bk-col'><span class='ak-fig-l'>{bk_kept}</span><span class='bk-big'>$max</span><span class='ak-help'>{bk_kept_explain}</span></div>";
    $h.="<div class='bk-col'><span class='ak-fig-l'>{include_personal_categories}</span>";
    $h.=$cat?"<span class='ak-pill ak-ok'><span class='ak-dot'></span><span class='bk-uc'>{yes}</span></span>":"<span class='ak-pill ak-off'><span class='ak-dot'></span><span class='bk-uc'>{no}</span></span>";
    $h.="</div></div></section>";
    return $h;
}

/** Section "Conservation des donnees" : deux cartes, verrouillees sans licence Corporate. */
function bk_retention_section():string{
    $s=$GLOBALS["CLASS_SOCKETS"];
    $corp=$s->CORP_LICENSE();
    $general=intval($s->GET_INFO("InfluxAdminRetentionTime"));
    $events=intval($s->GET_INFO("SystemEventsRetentionTime"));
    $dns=intval($s->GET_INFO("PDNSStatsRetentionTime"));
    if($general==0){$general=365;}
    if($events==0){$events=15;}
    if($dns==0){$dns=5;}
    if(!$corp){$general=5;$events=7;$dns=5;}
    $size=$s->GET_INFO("LogsRotateDefaultSizeRotation");
    if(!is_numeric($size)){$size=100;}

    $h="<div class='bk-section'><div><h2>{bk_retention_title}</h2><p>{bk_retention_explain}</p></div>";
    if(!$corp){$h.="<span class='ak-pill ak-off'><i class='fas fa-lock'></i> {bk_corp_required}</span>";}
    $h.="</div>";
    if(!$corp){$h.="<p class='bk-lock-note'>{bk_corp_explain}</p>";}
    $h.="<div class='ak-grid-2 bk-grid2'>";
    $h.=bk_card_retention("fas fa-chart-column","{statistics_retention_parameters}",array(
        array("{retention_days} ({general})",$general,"<span class='bk-lc'>{days}</span>"),
        array("{PDNSStatsRetentionTime}",$dns,"<span class='bk-lc'>{days}</span>"),
    ),$corp);
    $h.=bk_card_retention("far fa-file-lines","{logs_retention_parameters}",array(
        array("{SystemEventsRetentionTime}",$events,"<span class='bk-lc'>{days}</span>"),
        array("{remove_if_files_exceed}",intval($size),"MB"),
    ),$corp);
    $h.="</div>";
    return $h;
}

/** Une carte de retention : deux valeurs en grand ; editable seulement avec la licence. */
function bk_card_retention($ico,$title,$items,$editable):string{
    $h="<section class='ak-card bk-card".($editable?"":" bk-card-locked")."'>";
    $h.="<div class='bk-card-head'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='$ico'></i></span>";
    $h.="<div class='bk-card-titles'><h2>$title</h2></div>";
    if($editable){$h.=bk_button("Loadjs('fw.retention.php?js=yes')");}
    $h.="</div><div class='bk-metrics'>";
    foreach($items as $it){
        $h.="<div class='bk-metric'><span class='bk-metric-lbl'>$it[0]</span>";
        $h.="<span class='bk-metric-val'>$it[1]<span class='bk-unit'>$it[2]</span></span></div>";
    }
    $h.="</div></section>";
    return $h;
}

/** CSS du kit de l'onglet (une fois par requete). */
function bk_style():string{
    if(isset($GLOBALS["BK_STYLE_DONE"])){return "";}
    $GLOBALS["BK_STYLE_DONE"]=true;
    // Carte, pastilles, boutons, grilles : kit ak- (angular.css.php).
    return "<style>
.bk-wrap{margin-top:20px}
.bk-banner{align-items:flex-start;gap:16px;padding:16px 20px;margin-bottom:24px}
.bk-banner>span{font-size:18px;margin-top:2px}
.bk-banner strong{font-size:15px;color:#663e00}
.bk-banner p{font-size:14px;color:#3d4947;margin:4px 0 8px 0;line-height:1.5}
.bk-tiles{gap:24px;margin-bottom:24px}
.bk-tile{padding:24px;display:flex;flex-direction:column}
.bk-tile-warn{border-color:#ffddb9;background:#fffaf3}
.bk-tile-head{display:flex;align-items:center;justify-content:space-between;gap:12px}
.bk-state{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:18px}
.bk-state-txt{font-size:20px;font-weight:600;color:#111d23}
.bk-tile-text{font-size:14px;color:#3d4947;line-height:1.55;margin:10px 0 0 0}
.bk-tile-text strong{color:#111d23;font-variant-numeric:tabular-nums}
.bk-tile-foot{display:flex;gap:10px;margin-top:auto;padding-top:16px;border-top:1px solid #f1f5f9}
.bk-tile>*:nth-last-child(2){margin-bottom:18px}
.bk-tile-foot .ak-btn{flex:1 1 auto}
.bk-dl{display:grid;grid-template-columns:auto 1fr;gap:4px 12px;margin:10px 0 0 0;font-size:14px}
.bk-dl dt{color:#3d4947;font-weight:400}
.bk-dl dd{margin:0;color:#111d23;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.bk-alert{margin-top:12px;font-size:13px;font-weight:600;color:#865300;display:flex;gap:8px;align-items:flex-start;line-height:1.4}
.bk-link:focus-visible{outline:2px solid #006a62;outline-offset:2px}
.bk-link{color:#006a62;font-weight:600;text-decoration:none}
.bk-link:hover{color:#00504a;text-decoration:underline}
.bk-card{padding:24px}
.bk-card-head{display:flex;align-items:flex-start;gap:16px}
.bk-card-titles{flex:1 1 auto;min-width:0}
.bk-card-titles h2{font-size:18px;font-weight:600;margin:6px 0 0 0;color:#111d23}
.bk-card-titles p{font-size:14px;color:#3d4947;margin:4px 0 0 0;line-height:1.5}
.bk-cols{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));margin-top:20px;padding-top:20px;border-top:1px solid #f1f5f9}
.bk-col{padding:0 24px;border-left:1px solid #f1f5f9;display:flex;flex-direction:column;align-items:flex-start;gap:8px;min-width:0}
.bk-col:first-child{padding-left:0;border-left:0}
.bk-path{display:block;max-width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;font-size:14px;color:#111d23;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;user-select:all}
.bk-big{font-size:28px;font-weight:700;line-height:1;color:#111d23;font-variant-numeric:tabular-nums}
.bk-section{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin:32px 0 16px 0;padding-bottom:12px;border-bottom:1px solid #e2e8f0}
.bk-section h2{font-size:18px;font-weight:600;margin:0;color:#111d23}
.bk-section p{font-size:14px;color:#3d4947;margin:4px 0 0 0}
.bk-lock-note{font-size:13px;color:#3d4947;margin:-6px 0 16px 0}
.bk-grid2{gap:24px;margin-bottom:24px}
.bk-metrics{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:20px}
.bk-metric{background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px;padding:16px 18px;display:flex;flex-direction:column;gap:8px;min-width:0}
.bk-metric-lbl{font-size:13px;color:#3d4947;line-height:1.4}
.bk-metric-val{font-size:28px;font-weight:700;line-height:1;color:#111d23;font-variant-numeric:tabular-nums}
.bk-unit{font-size:14px;font-weight:400;color:#3d4947;margin-left:6px}
.bk-lc{text-transform:lowercase}
.bk-uc{display:inline-block}
.bk-uc::first-letter,.bk-dl dt::first-letter{text-transform:uppercase}
.bk-card-locked .bk-metric-val{color:#6d7a77}
@media(max-width:1100px){.bk-tiles,.bk-grid2,.bk-cols{grid-template-columns:1fr}.bk-col{padding:12px 0;border-left:0}}
</style>";
}

function parameters_dir_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$SnapShotsStorageDirectory=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotsStorageDirectory"));
	$SnapShotsPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotsPassword"));
    $SnapShotsCategories=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotsCategories"));

	if($SnapShotsStorageDirectory==null){$SnapShotsStorageDirectory="/home/artica/snapshots";}
	$SnapShotStorageMax=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotStorageMax"));
	if($SnapShotStorageMax<1){$SnapShotStorageMax=3;}
	
	$form[]=$tpl->field_browse_directory("SnapShotsStorageDirectory", "{storage_directory}", $SnapShotsStorageDirectory);
	$form[]=$tpl->field_numeric("SnapShotStorageMax","{max_backup_containers}",$SnapShotStorageMax);
    $form[]=$tpl->field_checkbox("SnapShotsCategories","{include_personal_categories}",$SnapShotsCategories);
	$form[]=$tpl->field_password2("SnapShotsPassword", "{passphrase} ({optional})", $SnapShotsPassword,false,"{snapshot_password_explain}");
    echo $tpl->form_outside("{parameters}", $form,null,"{apply}","LoadAjax('artica-backup-parameters-static','$page?parameters2=yes');dialogInstance1.close();","AsSystemAdministrator",true);
    return true;



}
function parameters_buttons():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    // Les etats (planification, stockage distant) sont desormais affiches dans
    // les tuiles de parameters2() : l'en-tete ne porte plus que l'action principale.
    $topbuttons=array();
    $topbuttons[]=array("Loadjs('$page?backup-js=yes')","fad fa-archive","{create_a_snapshot}");
    $btns_html=$tpl->table_buttons($topbuttons);

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
    if(isset($_POST["BackupArticaBackUseNas"])){
        // Ancien interrupteur : il passe par le daemon (meme regle que nas_enable),
        // jamais par SAVE_POSTs qui n'ecrirait que la cle historique.
        $users=new usersMenus();
        if(!$users->AsSystemAdministrator){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
        $e=snapshot_target_from_nas_switch($_POST["BackupArticaBackUseNas"]);
        unset($_POST["BackupArticaBackUseNas"]);
        if($e!=""){echo $tpl->post_error($e);return false;}
    }
    if(isset($_POST["BackupArticaBackNASIpaddr"])){
        if(!ValidateHostnameOrIP($_POST["BackupArticaBackNASIpaddr"])){
            echo $tpl->post_error(strval($_POST["BackupArticaBackNASIpaddr"])." Invalid");
            return false;
        }
    }
    if(isset($_POST["BackupArticaBackNASFolder"])){
        $_POST["BackupArticaBackNASFolder"]=strip_tags($_POST["BackupArticaBackNASFolder"]);
    }
    if(isset($_POST["SnapShotsPassword"]) && remote_target_kind()=="azure" && strlen($_POST["SnapShotsPassword"])<12){
        echo $tpl->post_error("{azure_password_required}");
        return false;
    }
    $tpl->SAVE_POSTs();
    return  admin_tracks_post("Saving Artica Backup parameters");
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

// ---- Snapshots of OTHER appliances (same NAS share / same Azure container) ----

// Same rule as the daemon (backup/names.go safeNameRe, 253 / 200 chars, no "..").
function remote_valid_host($h):bool{
    return is_string($h) && strlen($h)>0 && strlen($h)<=253
        && preg_match('/^[A-Za-z0-9_-][A-Za-z0-9._-]*$/',$h) && strpos($h,"..")===false;
}
function remote_valid_name($n):bool{
    return is_string($n) && strlen($n)>0 && strlen($n)<=200
        && preg_match('/^[A-Za-z0-9_-][A-Za-z0-9._-]*$/',$n) && strpos($n,"..")===false;
}

function remote_table_js():bool{
    $users=new usersMenus();
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    if(!$users->AsSystemAdministrator){return $tpl->js_error("{ERROR_NO_PRIVS2}");}
    $page=CurrentPageName();
    echo "LoadAjax('table-snapshots-list','$page?remote-table=yes');";
    return true;
}

function remote_table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS2}"));return true;}
    $hosts=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/snapshots/hosts"));
    if(!$hosts || !$hosts->Status){
        $err=($hosts && isset($hosts->Error))?htmlspecialchars(strval($hosts->Error)):"";
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}||$err"));return true;
    }
    $local=strval($hosts->local ?? "");
    $current=strval($_GET["host"] ?? $local);
    if(!remote_valid_host($current)){$current=$local;}
    $hash=array();
    foreach((array)($hosts->hosts ?? array()) as $h){
        if(!remote_valid_host(strval($h))){continue;}
        $hash[$h]=$h;
    }
    if($current!=="" && !isset($hash[$current])){$hash[$current]=$current;}
    $onchange="LoadAjax('table-snapshots-list','$page?remote-table=yes&host='+encodeURIComponent(this.value))";
    $html[]="<div style='margin:10px 0'><a href=\"javascript:blur();\" OnClick=\"LoadAjax('table-snapshots-list','$page?table=yes')\"><i class='fas fa-arrow-left'></i> {back}</a></div>";
    $html[]="<div style='margin-bottom:10px'>".$tpl->field_array_hash_simple($hash,"remote-host","{appliance}",$current,false,$onchange)."</div>";
    $list=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/snapshots/remote-list?host=".urlencode($current)));
    if(!$list || !$list->Status){
        $err=($list && isset($list->Error))?htmlspecialchars(strval($list->Error)):"";
        $html[]=$tpl->div_error("{error}||$err");
        echo $tpl->_ENGINE_parse_body($html);return true;
    }
    $html[]="<table class='table table-hover'><thead><tr><th>{date}</th><th>{filename}</th><th>{size}</th><th>&nbsp;</th></tr></thead><tbody>";
    foreach($list->snapshots as $s){
        $rname=strval($s->name);
        if(!remote_valid_name($rname)){continue;}
        $name=htmlspecialchars($rname);
        $hostenc=urlencode($current);$nameenc=urlencode($rname);
        $restore=$tpl->icon_run("Loadjs('$page?restore-remote-js=yes&host=$hostenc&name=$nameenc')","AsSystemAdministrator");
        $html[]="<tr><td>".htmlspecialchars($tpl->time_to_date(intval($s->time),true))."</td><td>$name</td><td>".FormatBytes(intval($s->size)/1024)."</td><td>$restore</td></tr>";
    }
    $html[]="</tbody></table>";
    $html[]="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function restore_remote_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    header("content-type: application/x-javascript");
    if(!$users->AsSystemAdministrator){return $tpl->js_error("{ERROR_NO_PRIVS2}");}
    $host=strval($_GET["host"] ?? "");$name=strval($_GET["name"] ?? "");
    if(!remote_valid_host($host) || !remote_valid_name($name)){return $tpl->js_error("{error}");}
    return $tpl->js_dialog6("{restore} {snapshot}","$page?restore-remote-popup=yes&host=".urlencode($host)."&name=".urlencode($name),600);
}

// form_outside() cannot carry this flow: its xSave treats any body longer than 3
// characters that is not "jserror:" as an error banner and then skips $jsafter, so
// a handler cannot hand the job id back through it. The popup therefore posts by
// itself ($.post, password in the BODY only, never in a URL) and only the job id
// (32 hex, single use, expires in 10 min daemon-side) travels in the run URL.
function restore_remote_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS2}"));return true;}
    $host=strval($_GET["host"] ?? "");$name=strval($_GET["name"] ?? "");
    if(!remote_valid_host($host) || !remote_valid_name($name)){echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));return true;}
    $eh=htmlspecialchars($host);$en=htmlspecialchars($name);
    $html[]=$tpl->div_warning("{restore_remote_warning}||$eh / $en");
    $html[]="<div id='restore-remote-form' style='margin:15px'>";
    $html[]="<label for='restore-remote-password'><strong>{passphrase}</strong></label>";
    $html[]="<input type='password' id='restore-remote-password' class='form-control' autocomplete='new-password' style='margin:6px 0'>";
    $html[]="<p class='text-muted'>{restore_remote_password_explain}</p>";
    $html[]="<div style='text-align:right'><button type='button' id='restore-remote-go' class='btn btn-danger' OnClick=\"RestoreRemoteGo()\">{restore}</button></div>";
    $html[]="</div>";
    $html[]="<div id='restore-remote-progress'></div>";
    $jh=json_encode($host,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
    $jn=json_encode($name,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
    $js="<script>
function RestoreRemoteGo(){
    var bt=document.getElementById('restore-remote-go');
    if(bt){if(bt.disabled){return;}bt.disabled=true;}
    var fail=function(t){NoSpinner();if(bt){bt.disabled=false;}swal({title:'Oops...',text:'<strong>'+t+'</strong>',html:true,type:'error'});};
    $('body').Wload();
    $.post('$page',{restore_remote_host:$jh,restore_remote_name:$jn,restore_remote_password:document.getElementById('restore-remote-password').value},function(resp){
        resp=String(resp);
        if(resp.indexOf('jserror:')===0){fail(resp.substring(8));return;}
        var j=null;try{j=JSON.parse(resp);}catch(e){}
        if(!j||!j.job){fail('Invalid answer');return;}
        NoSpinner();
        document.getElementById('restore-remote-form').style.display='none';
        LoadAjax('restore-remote-progress','$page?restore-remote-run-popup='+encodeURIComponent(j.job));
    }).fail(function(){fail('Request failed');});
}
</script>";
    echo $tpl->_ENGINE_parse_body($html).$js;
    return true;
}

function restore_remote_prepare():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    // No CLEAN_POST(): it would rewrite the password. host/name are validated instead.
    $host=strval($_POST["restore_remote_host"] ?? "");
    $name=strval($_POST["restore_remote_name"] ?? "");
    $password=strval($_POST["restore_remote_password"] ?? "");
    if(!remote_valid_host($host) || !remote_valid_name($name)){echo $tpl->post_error("{error}");return false;}
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/system/snapshots/restore-remote/prepare",array(
        "host"=>$host,"name"=>$name,"password"=>$password)));
    if(!$json || !$json->Status){
        echo $tpl->post_error(($json && isset($json->Error))?strval($json->Error):"{error}");
        return false;
    }
    $job=preg_replace('/[^a-f0-9]/','',strval($json->job ?? ""));
    if(strlen($job)!=32){echo $tpl->post_error("{error}");return false;}
    admin_tracks("Restore of remote snapshot $host/$name requested");
    echo json_encode(array("job"=>$job));
    return true;
}

function restore_remote_run_popup():bool{
    if(!fw_progress_page_priv("AsSystemAdministrator")){return false;}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $job=preg_replace('/[^a-f0-9]/','',strval($_GET["restore-remote-run-popup"] ?? ""));
    if(strlen($job)!=32){return fw_progress_bad_value("restore-remote-run-popup");}
    $js=$tpl->framework_buildjs("/system/snapshots/restore-remote/run/$job",
        "backup.artica.progress","backup.artica.progress.txt","restore-remote-progress-inner",
        "if(typeof dialogInstance6 == 'object'){ dialogInstance6.close();}LoadAjax('table-snapshots-list','$page?table=yes')");
    echo "<div id='restore-remote-progress-inner'></div><script>$js</script>";
    return true;
}
// ===========================================================================
// Onglet "Snapshots" (maquette stitch_artica_backup_dashboard_redesign du
// 2026-11-03) : bandeau Plakar, 4 tuiles de synthese, carte des instantanes
// avec recherche et pagination cote client. Un modele (bks_model) calcule une
// fois, une fonction par widget, CSS/JS concatenes HORS du moteur.
// ===========================================================================
function bks_is_admin():bool{
    $users=new usersMenus();
    return $users->AsSystemAdministrator==true;
}
function bks_model():array{
    $s=$GLOBALS["CLASS_SOCKETS"];
    $m=array();
    $list=@unserialize(strval($s->GET_INFO("SnapShotList")));
    if(!is_array($list)){$list=array();}
    uasort($list,function($a,$b){return intval($b["TIME"] ?? 0)<=>intval($a["TIME"] ?? 0);});
    $m["list"]=$list;
    $m["max"]=intval($s->GET_INFO("SnapShotStorageMax"));
    if($m["max"]<1){$m["max"]=3;}
    $m["sched_on"]=intval($s->GET_INFO("BackupArticaSnaps"))==1;
    $sched=@unserialize((string)$s->GET_INFO("BackupArticaSnapsSched"));
    if(!is_array($sched)){$sched=array();}
    $m["sched_day"]=max(1,intval($sched["DAY"] ?? 2));
    $m["sched_hour"]=intval($sched["HOUR"] ?? 1);
    $m["sched_min"]=intval($sched["MIN"] ?? 30);
    $m["remote"]=remote_target_kind();
    $m["plakar"]=intval($s->GET_INFO("ArticaSnapshotsViaPlakar"))==1;
    $m["admin"]=bks_is_admin();
    // Descriptions et contenus, indexes par MD5 : une requete pour toute la liste.
    $m["meta"]=array();
    $q=new lib_sqlite("/home/artica/SQLITE/snapshots.db");
    $res=$q->QUERY_SQL("SELECT ID,zmd5,name,length(content) AS clen FROM snapshots");
    if(is_array($res)){
        foreach($res as $r){
            $m["meta"][(string)$r["zmd5"]]=array("id"=>intval($r["ID"]),"name"=>trim((string)$r["name"]),"has_content"=>intval($r["clen"])>20);
        }
    }
    return $m;
}
// --------------------------------------------------------------- tuiles
function bks_tile(string $label,string $value,string $sub,string $ico,string $extra="",string $pill=""):string{
    return "<div class='ak-fig bks-tile'><div class='ak-fig-txt'><div class='ak-fig-l bks-tile-lab'><span>$label</span>$pill</div>"
        ."<div class='bks-tile-val'>$value</div><div class='ak-fig-s'>$sub</div></div>"
        .($extra!=""?$extra:"<span class='ak-ico ak-ok'><i class='$ico'></i></span>")."</div>";
}
function bks_pill(bool $on,string $label):string{
    $cls=$on?"ak-ok":"ak-off";
    $ico=$on?"fa-solid fa-circle-check":"fa-solid fa-circle-minus";
    return "<span class='ak-pill $cls'><i class='$ico'></i> $label</span>";
}
function bks_kpi_latest(array $m):string{
    $tpl=new template_admin();
    if(count($m["list"])==0){
        return bks_tile("{bks_latest}","<span class='bks-muted-val'>{bks_none_yet}</span>","&nbsp;","fa-solid fa-clock-rotate-left");
    }
    $first=reset($m["list"]);
    $t=intval($first["TIME"] ?? 0);
    // "%s ago" est un token : l'ordre des mots change selon la langue ("il y a %s").
    $ago=str_replace("%s",htmlspecialchars(distanceOfTimeInWords($t,time())),$tpl->_ENGINE_parse_body("{bks_ago_fmt}"));
    return bks_tile("{bks_latest}",$ago,
        htmlspecialchars($tpl->time_to_date($t,true)),"fa-solid fa-clock-rotate-left");
}
// Le donut se mesure contre le maximum conserve (SnapShotStorageMax).
function bks_kpi_kept(array $m):string{
    $n=count($m["list"]);
    $donut="<span class='bks-donut' data-peity='{\"fill\":[\"".bks_accent()."\",\"#e2e8f0\"],\"innerRadius\":18,\"radius\":26}'>".min($n,$m["max"])."/".$m["max"]."</span>";
    return bks_tile("{bks_kept}","$n / {$m["max"]}","{bks_kept_sub}","",$donut);
}
function bks_kpi_schedule(array $m):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $cfg=$m["admin"]?"<a class='bks-link' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?schedule-js=yes');\">{bks_configure}</a>":"&nbsp;";
    if(!$m["sched_on"]){
        return bks_tile("{bks_schedule}","<span class='bks-muted-val'>{bks_manual_only}</span>",$cfg,"fa-solid fa-calendar-xmark","",bks_pill(false,"{disabled}"));
    }
    $every=$m["sched_day"]==1?"{bk_every_day}":str_replace("%s",$m["sched_day"],$tpl->_ENGINE_parse_body("{bk_every_n_days}"));
    $at=sprintf("%02d:%02d",$m["sched_hour"],$m["sched_min"]);
    return bks_tile("{bks_schedule}","$every <span class='bks-ago'>{bks_at}</span> $at",$cfg,"fa-solid fa-calendar-check","",bks_pill(true,"{enabled}"));
}
function bks_kpi_remote(array $m):string{
    $page=CurrentPageName();
    $map=array("nas"=>array("N.A.S <span class='bks-ago'>(SMB)</span>","nas-js"),"azure"=>array("Azure Blob","azure-js"));
    if(isset($map[$m["remote"]])){
        list($label,$js)=$map[$m["remote"]];
        $pill=bks_pill(true,"{bks_configured}");
    }else{
        $label="<span class='bks-muted-val'>{bks_local_only}</span>";
        $js="remote-js";
        $pill="";
    }
    $cfg=$m["admin"]?"<a class='bks-link' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?$js=yes');\">{bks_configure}</a>":"&nbsp;";
    return bks_tile("{bks_remote_copy}",$label,$cfg,"fa-solid fa-cloud-arrow-up","",$pill);
}
function bks_plakar_banner(array $m):string{
    if(!$m["plakar"]){return "";}
    return "<div class='ak-banner ak-warn' role='status'><i class='fa-solid fa-triangle-exclamation'></i>"
        ."<div class='ak-banner-m'><b>{articabackup_plakar_mode_active}</b> {articabackup_plakar_mode_active_explain}</div>"
        ."<a class='bks-banner-link' href='fw.artica.backup.plakar.php'>{articabackup_via_plakar} <i class='fa-solid fa-arrow-right'></i></a></div>";
}
// --------------------------------------------------------------- tableau
function bks_location(string $loc):string{
    $map=array(
        "nas"=>array("ak-ok","fa-solid fa-server","N.A.S"),
        "azure"=>array("ak-info","fa-solid fa-cloud","Azure Blob"),
        "local"=>array("ak-off","fa-solid fa-hard-drive","{bks_local_only}"),
    );
    $l=$map[$loc] ?? $map["local"];
    return "<span class='ak-tag {$l[0]}'><i class='{$l[1]}'></i> {$l[2]}</span>";
}
function bks_row(array $m,string $filename,array $a,bool $latest):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $enc=urlencode($filename);
    $md5=(string)($a["MD5"] ?? "");
    $meta=$m["meta"][$md5] ?? array("id"=>0,"name"=>"","has_content"=>false);
    $t=intval($a["TIME"] ?? 0);
    $fn=htmlspecialchars($filename);
    $title=$meta["name"]!=""?htmlspecialchars($meta["name"]):$fn;
    $badge=$latest?" <span class='ak-tag ak-ok'>{bks_latest_chip}</span>":"";
    $search=htmlspecialchars(strtolower($filename." ".$meta["name"]),ENT_QUOTES);
    $date=htmlspecialchars($tpl->time_to_date($t,true));
    $size=htmlspecialchars(FormatBytes(intval($a["SIZE"] ?? 0)/1024,true));

    $acts=array();
    if($m["admin"]){
        $acts[]="<button type='button' class='ak-btn bks-btn-restore' OnClick=\"Loadjs('$page?restore-js=$enc');\"><i class='fa-solid fa-rotate-left'></i> {restore}</button>";
        $acts[]="<button type='button' class='ak-act' OnClick=\"Loadjs('$page?download-js=$enc');\" title='{bks_download}' aria-label='{bks_download} $fn'><i class='fa-solid fa-download'></i></button>";
        // Emplacements reserves : les boutons restent alignes d'une ligne a l'autre.
        $gap="<span class='bks-ibtn-gap' aria-hidden='true'></span>";
        if(!$meta["has_content"]){$acts[]=$gap;}
        if($meta["has_content"]){
            $acts[]="<button type='button' class='ak-act' OnClick=\"Loadjs('$page?js-content={$meta["id"]}');\" title='{bks_contents}' aria-label='{bks_contents} $fn'><i class='fa-solid fa-list'></i></button>";
        }
        if($meta["id"]==0){$acts[]=$gap;}
        if($meta["id"]>0){
            $acts[]="<button type='button' class='ak-act' OnClick=\"Loadjs('$page?js-desc={$meta["id"]}');\" title='{bks_edit_desc}' aria-label='{bks_edit_desc} $fn'><i class='fa-solid fa-pen'></i></button>";
        }
        $acts[]="<button type='button' class='ak-act ak-danger' OnClick=\"Loadjs('$page?delete-js=$enc&id=".md5($filename)."');\" title='{bks_delete}' aria-label='{bks_delete} $fn'><i class='fa-solid fa-trash-can'></i></button>";
    }
    return "<tr data-bks='$search'>"
        ."<td><div class='bks-ident'><span class='ak-ico ak-ico-sq'><i class='fa-solid fa-file-zipper'></i></span><div class='bks-ident-bd'>"
        ."<div class='bks-name".($meta["name"]==""?" ak-mono":"")."'>$title$badge</div><div class='bks-meta'>".($meta["name"]!=""?"<span class='ak-mono'>$fn</span> &middot; ":"")."$date</div></div></div></td>"
        ."<td>".bks_location((string)($a["LOCATION"] ?? "local"))."</td>"
        ."<td class='ak-num ak-nowrap bks-size'>$size</td>"
        ."<td class='ak-end'><div class='ak-acts'>".@implode("",$acts)."</div></td></tr>";
}
function bks_rows_card(array $m):string{
    $page=CurrentPageName();
    $n=count($m["list"]);
    $rows=array();
    $first=true;
    foreach($m["list"] as $filename=>$a){
        if(!is_array($a)){continue;}
        $rows[]=bks_row($m,(string)$filename,$a,$first);
        $first=false;
    }
    if(count($rows)==0){
        $btn=$m["admin"]?"<button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('$page?backup-js=yes');\"><i class='fa-solid fa-plus'></i> {create_a_snapshot}</button>":"";
        $rows[]="<tr class='bks-empty-row'><td colspan='4'><div class='ak-empty-c bks-empty'><i class='fa-solid fa-box-archive'></i><div>{bks_none_yet}</div>$btn</div></td></tr>";
    }
    return "<section class='ak-card'><div class='ak-card-h'><div class='bks-card-title'><h3>{snapshots}</h3><span class='ak-count'>$n</span></div>"
        ."<label class='ak-search bks-search'><i class='fa-solid fa-magnifying-glass'></i><input type='search' id='bks-q' placeholder='{bks_search_ph}' aria-label='{bks_search_ph}' OnInput='BksFilter(1);'></label></div>"
        ."<div class='bks-scroll'><table class='ak-tbl'><thead><tr><th>{snapshot}</th><th>{bks_location}</th><th class='ak-end'>{bks_size}</th><th class='ak-end'>{bks_actions}</th></tr></thead>"
        ."<tbody id='bks-rows'>".@implode("",$rows)."</tbody></table></div>"
        ."<div class='ak-card-f'><span id='bks-showing'></span><span class='ak-pg' id='bks-pager'></span></div></section>";
}
function bks_body(array $m):string{
    return bks_plakar_banner($m)
        ."<div class='ak-grid-4 bks-kpis'>".bks_kpi_latest($m).bks_kpi_kept($m).bks_kpi_schedule($m).bks_kpi_remote($m)."</div>"
        .bks_rows_card($m);
}
function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(isset($_GET["admin-track-restore"])){
        admin_tracks("Restored a snapshot ".basename((string)$_GET["admin-track-restore"]));
    }
    if(isset($_GET["admintrack-snapshot-uploaded"])){
        admin_tracks("Uploaded a snapshot ".basename((string)$_GET["admintrack-snapshot-uploaded"]));
    }
    $m=bks_model();
    echo bks_style()
        ."<div id='bks-host' class='ak'>".$tpl->_ENGINE_parse_body(bks_body($m))."</div>"
        .bks_js()
        ."<script>NoSpinner();Loadjs('$page?bks-tiny=yes');</script>";
}
// Rechargement periodique de la vue seule (jamais le TINY ni le timer).
function bks_host(){
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body(bks_body(bks_model()))."<script>if(window.BksFilter){BksFilter(0);}</script>";
}
function bks_tiny(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $b=array();
    if(bks_is_admin()){
        $b[]="<button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('$page?backup-js=yes');\"><i class='fa-solid fa-camera'></i> {create_a_snapshot}</button>";
        $b[]="<button type='button' class='ak-btn' OnClick=\"Loadjs('$page?upload-js=yes');\"><i class='fa-solid fa-upload'></i> {upload_snapshot}</button>";
        if(remote_target_kind()!="none"){
            $b[]="<button type='button' class='ak-btn' OnClick=\"Loadjs('$page?remote-table-js=yes');\"><i class='fa-solid fa-network-wired'></i> {other_appliances}</button>";
        }
        $b[]="<div class='btn-group'><button type='button' class='ak-btn bks-tb-more dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false' aria-label='{bks_more_actions}' title='{bks_more_actions}'><i class='fa-solid fa-ellipsis-vertical'></i></button>"
            ."<ul class='dropdown-menu dropdown-menu-right'><li><a href=\"javascript:blur();\" OnClick=\"Loadjs('$page?support-tool-js=yes');\"><i class='fa-solid fa-life-ring'></i> {attach_support_tool}</a></li></ul></div>";
    }
    $TINY=array();
    $TINY["TITLE"]="{snapshots}";
    $TINY["ICO"]="fa fa-archive";
    $TINY["EXPL"]="{SNAPSHOTS_EXPLAIN}";
    $TINY["URL"]="snapshots";
    $TINY["BUTTONS"]="<div class='bks-tbar'>".@implode("",$b)."</div>";
    header("content-type: application/x-javascript");
    echo "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
}
// Creation : confirmation d'abord (elle part sinon au premier clic), progression ensuite.
function backup_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!bks_is_admin()){$tpl->js_no_privileges();return;}
    $tpl->js_dialog_confirm_action("{bks_create_confirm}","bks-create","none",
        "Loadjs('$page?backup-progress-js=yes');");
}
function bks_create_confirm(){
    if(!bks_is_admin()){$tpl=new template_admin();echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    admin_tracks("Create a new artica backup snapshot");
}
function bks_backup_progress_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog6("modal:{create_a_snapshot}", "$page?backup-popup=yes",850);
}
function bks_accent():string{
    $accent="#00a58e";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
    }
    return $accent;
}
function bks_js():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $showing=addslashes($tpl->javascript_parse_text("{showing}"));
    $of=addslashes($tpl->javascript_parse_text("{bks_of}"));
    $unit=addslashes($tpl->javascript_parse_text("{snapshots}"));
    $none=addslashes($tpl->javascript_parse_text("{bks_no_match}"));
    return "<script src='js/jquery.peity.min.js'></script><script>
window.BksPage=1;
// Recherche et pagination ensemble : le pager ne compte que les lignes retenues.
window.BksFilter=function(reset){
    var tb=document.getElementById('bks-rows'); if(!tb){return;}
    if(reset){window.BksPage=1;}
    var qi=document.getElementById('bks-q'); var q=qi?qi.value.trim().toLowerCase():'';
    var rows=[],all=tb.rows,i;
    for(i=0;i<all.length;i++){ var k=all[i].getAttribute('data-bks'); if(k===null){continue;} all[i].style.display='none'; if(q==='' || k.indexOf(q)>=0){rows.push(all[i]);} }
    var size=50,n=rows.length,pages=Math.max(1,Math.ceil(n/size));
    if(window.BksPage>pages){window.BksPage=pages;}
    var a=(window.BksPage-1)*size,b=Math.min(n,a+size);
    for(i=a;i<b;i++){rows[i].style.display='';}
    var sh=document.getElementById('bks-showing');
    if(sh){ sh.innerHTML=n===0 && all.length>0 && q!==''?'$none':('$showing '+(n?a+1:0)+'&ndash;'+b+' $of '+n+' $unit'); }
    var pg=document.getElementById('bks-pager'); if(!pg){return;}
    if(pages<2){pg.innerHTML='';return;}
    var h='';
    for(var p=1;p<=pages;p++){ h+='<a href=\"javascript:blur();\" class=\"'+(p==window.BksPage?'cur':'')+'\" data-p=\"'+p+'\">'+p+'</a>'; }
    pg.innerHTML=h;
    var as=pg.getElementsByTagName('a');
    for(i=0;i<as.length;i++){ as[i].onclick=function(){ window.BksPage=parseInt(this.getAttribute('data-p'),10); window.BksFilter(0); return false; }; }
};
window.BksDonut=function(){ if(window.jQuery && jQuery.fn.peity){ jQuery('#bks-host .bks-donut').peity('donut'); } };
window.BksFilter(1); window.BksDonut();
if(window.BksTimer){clearInterval(window.BksTimer);}
window.BksTimer=setInterval(function(){
    if(!document.getElementById('bks-host')){clearInterval(window.BksTimer);window.BksTimer=null;return;}
    if(document.querySelector('.modal.in, .modal.show, .sweet-alert.visible')){return;}
    var q=document.getElementById('bks-q'); if(q && (q.value!=='' || document.activeElement===q)){return;}
    LoadAjaxSilent('bks-host','$page?bks-host=yes');
    setTimeout(window.BksDonut,800);
},60000);
</script>";
}
function bks_style():string{
    if(isset($GLOBALS["BKS_STYLE"])){return "";}
    $GLOBALS["BKS_STYLE"]=true;
    // Tuiles, carte, table, pastilles, boutons, recherche, pagination : kit ak- (angular.css.php).
    return "<style>
#bks-host{margin-top:10px}
#bks-host>.ak-banner{margin-bottom:16px}
.bks-banner-link{flex:0 0 auto;font-weight:700;color:#9a3412!important;text-decoration:none!important;white-space:nowrap}
.bks-kpis{margin-bottom:16px}
.bks-tile{align-items:flex-start;min-height:104px}
.bks-tile-lab{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.bks-tile-lab .ak-pill{letter-spacing:0;text-transform:none}
.bks-tile-val{font-size:20px;font-weight:600;line-height:26px;color:#0f172a}
.bks-ago{font-size:14px;font-weight:500;color:#64748b}
.bks-muted-val{color:#64748b;font-size:17px}
.bks-donut{flex:0 0 auto}
.bks-link{font-weight:700;color:var(--ak-fill)!important;text-decoration:none!important}
.bks-link:hover{text-decoration:underline!important}
.bks-card-title{display:flex;align-items:center;gap:10px}
.bks-card-title h3{margin:0;font-size:18px;font-weight:600;color:#0f172a}
.bks-search{width:340px;max-width:100%}
.bks-scroll{overflow-x:auto}
.bks-ident{display:flex;align-items:center;gap:14px;min-width:0}
.bks-ident-bd{min-width:0}
.bks-name{font-size:15px;font-weight:600;color:#0f172a;overflow-wrap:anywhere}
.bks-name .ak-tag{margin-left:6px;vertical-align:2px}
.bks-meta{font-size:13px;color:#64748b;margin-top:3px}
.bks-size{font-weight:600}
.bks-btn-restore{margin-right:6px}
.bks-ibtn-gap{display:inline-block;width:28px;height:30px}
.bks-link:focus-visible,.bks-banner-link:focus-visible{outline:2px solid var(--ak-fill);outline-offset:2px}
.bks-empty{gap:12px;padding:36px 20px;font-size:15px}
.bks-tbar{display:inline-flex;flex-wrap:wrap;gap:8px;align-items:center}
.bks-tb-more{width:34px;padding:0}
</style>";
}
