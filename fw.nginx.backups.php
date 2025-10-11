<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");

if(isset($_POST["NginxBackupMaxSize"])){parameters_save();exit;}
if(isset($_GET["remove-all"])){remove_all_js();exit;}
if(isset($_POST["remove-all"])){remove_all_perform();exit;}
if(isset($_GET["upload-backup"])){file_uploaded_js();exit;}
if(isset($_GET["template-import"])){template_import_js();exit;}
if(isset($_GET["template-import-popup"])){template_import_popup();exit;}
if(isset($_GET["file-uploaded"])){template_import_uploaded();exit;}
if(isset($_GET["parameters-js"])){parameters_js();exit;}
if(isset($_GET["parameters-popup"])){parameters_popup();exit;}
if(isset($_GET["InstantBackup-js"])){InstantBackup_js();exit;}
if(isset($_GET["InstantBackup-popup"])){InstantBackup_popup();exit;}

if(isset($_POST["restore"])){restore_save();exit;}
if(isset($_POST["restore-db"])){restore_db_save();exit;}
if(isset($_GET["restore-js"])){restore_js();exit;}
if(isset($_GET["restore-db-js"])){restore_db_js();exit;}
if(isset($_GET["restore-db-popup"])){restore_db_popup();exit;}

if(isset($_GET["restore-popup"])){restore_popup();exit;}
if(isset($_GET["restore-saved"])){restore_saved();exit;}

if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}

if(isset($_GET["enable-rule-js"])){enable_feature();}
if(isset($_GET["top-buttons"])){top_buttons();exit;}
if(isset($_GET["disableall"])){rule_disable_all();exit;}
if(isset($_GET["enableall"])){rule_enable_all();exit;}
if(isset($_GET["OnlyActive"])){OnlyActive();exit;}
if(isset($_GET["download"])){download();exit;}
if(isset($_GET["remove"])){remove_js();exit;}
if(isset($_POST["remove"])){remove_perform();exit;}
if(isset($_GET["upload-js"])){upload_js();exit;}
if(isset($_GET["upload-popup"])){upload_popup();exit;}
if(isset($_GET["InstantBackup-restore"])){InstantBackup_restore_js();exit;}
if(isset($_POST["InstantBackup-restore"])){InstantBackup_restore_perform();exit;}
service_js();

function upload_js():bool{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_dialog5("{upload}: {backup}","$page?upload-popup=yes&function=$function",550);
}
function remove_all_js():bool{
    $tpl        = new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_confirm_delete("{delete_all_backups_ask}","remove-all","yes","$function()");
}
function remove_all_perform():bool{
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/remove-all_backups");
    return admin_tracks("Removing all reverse-proxy backup containers");
}
function parameters_js():bool{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_dialog7("{parameters}","$page?parameters-popup=yes&function=$function");
}
function InstantBackup_js():bool{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $function=$_GET["function"];
    $function0=$_GET["function0"];
    return $tpl->js_dialog7("InstantBackup","$page?InstantBackup-popup=yes&function=$function&function0=$function0");
}
function InstantBackup_restore_js():bool{
    $tpl        = new template_admin();
    $function=$_GET["function"];
    $function0=$_GET["function0"];
    $f=array();
    if(strlen($function)>0){
        $f[]=$function."()";
    }
    if(strlen($function0)>0){
        $f[]=$function0."()";
    }
    return $tpl->js_confirm_execute("{restore_database}","InstantBackup-restore","yes",@implode(";",$f));
}
function InstantBackup_restore_perform():bool{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/instantbackup/restore"));
    if(!$json->Status){
        echo $json->Error;
        return false;
    }
    return admin_tracks("InstantBackup for Reverse-Proxy restored database");
}
//
function InstantBackup_popup():bool{
    $page=currentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $function0=$_GET["function0"];
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/instantbackup/status"));
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }
    $BackupTime=$json->BackupTime;
    $Distance=distanceOfTimeInWords($BackupTime,time());
    $tpl->table_form_field_text("{created}",$tpl->time_to_date($BackupTime).
        " <small>({since} $Distance)</small>",ico_file_zip);

    if($BackupTime>0) {
        $tpl->table_form_button("{restore_database}","Loadjs('$page?InstantBackup-restore=yes&function=$function&function0=$function0')","AsSystemAdministrator",ico_retweet);
    }

    foreach ($json->OpenFiles as $OpenFiles) {
        $pid=$OpenFiles->pid;
        $command=$OpenFiles->command;
        $FD=$OpenFiles->FD;
        $tpl->table_form_field_text("{locked}","$command FD:$FD Pid:$pid",ico_engine_warning);

    }
    echo $tpl->table_form_compile();

return true;

}
function service_js():bool{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $function=$_GET["function"];
    $NginxBackupCurSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxBackupCurSize"))*1024;
    $NginxBackupCurFiles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxBackupCurFiles"));
    return $tpl->js_dialog4("{backups} ".FormatBytes($NginxBackupCurSize)." $NginxBackupCurFiles {files}","$page?popup-main=yes&function=$function");
}
function restore_js():bool{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $filename=$_GET["restore-js"];
    $function=$_GET["function"];
    $function0=$_GET["function0"];
    $filename2=urlencode($filename);
    return $tpl->js_dialog7("{restore} $filename","$page?restore-popup=$filename2&function=$function&function0=$function0");
}
function restore_db_js():bool{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $filename=$_GET["restore-db-js"];
    $function=$_GET["function"];
    $function0=$_GET["function0"];
    $filename2=urlencode($filename);
    return $tpl->js_dialog7("{restore} DB $filename","$page?restore-db-popup=$filename2&function=$function&function0=$function0");
}
function upload_popup():bool{
    $function=$_GET["function"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $btn=$tpl->button_upload("{upload_a_file} (*.tgz)",$page,"","","&upload-backup=yes&function=$function&uploadedfname");
    $html="<div class='center' style='margin:50px'>$btn</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function parameters_popup():bool{
    $function=$_GET["function"];
    $page=CurrentPageName();
    $tpl=new template_admin();

    $NginxBackupMaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxBackupMaxSize"));
    if($NginxBackupMaxSize==0){
        $NginxBackupMaxSize=5000;
    }
    $form[]=$tpl->field_numeric("NginxBackupMaxSize","{max_directory_size} (MB)",$NginxBackupMaxSize);
    echo $tpl->form_outside("",$form,"","{apply}",$function);
    return true;
}
function parameters_save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    return admin_tracks("Saving Reverse-Proxy backup parameters");
}
function file_uploaded_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=$_GET["uploadedfname"];
    if(strlen($filename)<2){
        return $tpl->js_error("No file given");
    }
    $function=$_GET["function"];
    $filename=base64_encode($filename);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/import/backup/$filename");
    echo "dialogInstance5.close();$function();";
    return admin_tracks("Uploaded {$_GET["file-uploaded"]} reverse-proxy backup source");
}
function template_js():bool{
    $ID  = intval($_GET["template-js"]);
    $function=$_GET["function"];
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $title      = "{template}: #$ID";
    if($ID==0){$title="{new_template}";}
    return $tpl->js_dialog5($title,"$page?template-popup=$ID&function=$function");

}
function remove_js():bool{
    $tpl        = new template_admin();
    $md=$_GET["md"];
    $filename=$_GET["remove"];
    return $tpl->js_confirm_delete($filename,"remove",$filename,"$('#$md').remove();");
}
function remove_perform():bool{
    $filename=$_POST["remove"];
    $sock=new sockets();
    $sock->REST_API_NGINX("/reverse-proxy/delete/backup/$filename");
    return admin_tracks("Deleted reverse-proxy backup container $filename");

}
function template_remove_perform():bool{
    $tplid=intval($_POST["template-remove"]);
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT tpname FROM nginx_templates WHERE ID=$tplid");
    $tpname=$ligne["tpname"];
    $q->QUERY_SQL("DELETE FROM nginx_templates WHERE ID=$tplid");
    return admin_tracks("Removed reverse-proxy template $tpname configuration");
}
function template_import_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $bt_upload=$tpl->button_upload("{upload_template}",$page,null,"&function=$function")."&nbsp;&nbsp;";
    $explain=$tpl->div_explain("{upload_template}||{upload_template_explain}<p>");
    $html="<div id='ca-form-import'>
        <div class='center'>$bt_upload</div></div>
	    <div style='margin-top:20px'>$explain</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function template_import_uploaded():bool{
    $tpl=new template_admin();
    $file=$_GET["file-uploaded"];
    $fullpath="/usr/share/artica-postfix/ressources/conf/upload/$file";
    $function=$_GET["function"];
    $ligne=unserialize(base64_decode(file_get_contents($fullpath)));
    @unlink($fullpath);

    if(!is_array($ligne)){
        return $tpl->js_error("{corrupted}",__FUNCTION__,__FILE__,__LINE__);
    }
    $q                          = new lib_sqlite(NginxGetDB());

    $Keys = array();
    $vals = array();
    unset($ligne["ID"]);
    foreach ($ligne as $key => $value) {
        $Keys[] = $key;
        $vals[] = sprintf("'%s'", $value);
    }
    $sql = sprintf("INSERT INTO nginx_templates (%s) VALUES (%s)", implode(",", $Keys), implode(",", $vals));
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        return $tpl->js_error($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
    }
    header("content-type: application/x-javascript");
    echo "dialogInstance5.close();\n";
    echo "$function();\n";
    return true;
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return strval($ligne["servicename"]);
}
function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}
function restore_db_popup():bool{
    $page=CurrentPageName();
    $function=$_GET["function"];
    $function0=$_GET["function0"];
    $filename     = $_GET["restore-db-popup"];
    $tpl=new template_admin();
    $t=time();
    $form[]=$tpl->field_hidden("restore-db",$filename);
    $refresh=$tpl->framework_buildjs("nginx:/reverse-proxy/restore/database/$filename","nginx.backup.restore.progress","nginx.backup.restore.progress.log","Restore$t","dialogInstance5.close();$function();$function0()");
    $html[]="<div id='Restore$t'></div>";
    $html[]=$tpl->div_warning("{restore} DB $filename||{restore_database_explain_reverse_backup}");
    $html[]=$tpl->form_outside(null,$form,"","{restore} DB",$refresh,"AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function restore_db_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $filename=$_POST["restore-db"];
    return admin_tracks("Restored database only from backup container $filename for reverse-proxy service");
}
function restore_popup():bool{
    $page=CurrentPageName();
    $function=$_GET["function"];
    $function0=$_GET["function0"];
    $filename     = $_GET["restore-popup"];
    $tpl=new template_admin();
    $t=time();
    $DisableBuildNginxConfig=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableBuildNginxConfig"));

    $form[]=$tpl->field_hidden("restore",$filename);
    $form[]=$tpl->field_checkbox("DisableBuildNginxConfig","{DenyHaproxyConf}",$DisableBuildNginxConfig);

    $refresh=$tpl->framework_buildjs("nginx:/reverse-proxy/restore/backup/$filename","nginx.backup.restore.progress","nginx.backup.restore.progress.log","Restore$t","dialogInstance5.close();$function();$function0()");
    $html[]="<div id='Restore$t'></div>";
    $html[]=$tpl->div_warning("{restore} $filename||{restore_explain_reverse_backup}");
    $html[]=$tpl->form_outside(null,$form,"","{restore}",$refresh,"AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function restore_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $filename=$_POST["restore"];
    $sock=new sockets();
    $sock->SET_INFO("DisableBuildNginxConfig",$_POST["DisableBuildNginxConfig"]);
    return admin_tracks("Restored backup container $filename for reverse-proxy service");
}
function popup_main():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $function=$_GET["function"];
    $html[]="<div id='backup-nginx-btns' style='margin-bottom:10px'></div>";
    $options["UPLOAD"]="Loadjs('$page?upload-js=yes&function=%s')";
    $html[]=$tpl->search_block($page,null,null,null,"&function0=$function",$options);
    echo @implode("\n",$html);
    return true;
}
function top_buttons():bool{
    $function=$_GET["function"];
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $function0=$_GET["function0"];

    $topbuttons[] = array("Loadjs('$page?InstantBackup-js=yes&function=$function&function0=$function0')", ico_file_zip, "InstantBackup");

    $topbuttons[] = array("Loadjs('$page?parameters-js=yes&function=$function&function0=$function0')", ico_params, "{main_parameters}");
    $topbuttons[] = array("Loadjs('$page?remove-all=yes&function=$function&function0=$function0')", ico_trash, "{remove_all_items}");

    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}
function rule_disable_all(){
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];
    $sock       = new socksngix($serviceid);
    $data=unserialize(base64_decode($sock->GET_INFO("FUris")));
    foreach ($data as $UserAgent=>$none){
        $data[$UserAgent]=0;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("FUris",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    return admin_tracks("Disable all user-Agent Deny For reverse-proxy $get_servicename");
}
function search():bool{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $function =$_GET["function"];
    $function0=$_GET["function0"];
    $tableid    = time();
    $search=trim($_GET["search"]);
    $NginxBackuprestored=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxBackuprestored");

$html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
        	<th nowrap>{backup}</th>
        	<th nowrap>{created}</th>
        	<th nowrap>{download}</th>
        	<th nowrap>{restore} DB</th>
        	<th nowrap>{restore}</th>
        	<th nowrap>{delete}</small></th>
        </tr>
	<tbody>
";
    $sock=new sockets();
    $data=$sock->REST_API_NGINX("/reverse-proxy/backups/list");
    $json=json_decode($data);

    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<br>".json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->widget_rouge("{error}","Framework return false!<br>$json->Error");
        return false;
    }


    foreach ($json->list as $index=>$ligne){
        $md=md5(serialize($ligne));
        $filename=$ligne->filename;

        if($search<>null){
            if(!preg_match("#$search#",$filename)){
                continue;
            }
        }

        $strong=null;
        if($NginxBackuprestored==$filename){
            $strong="<strong>";
        }
        $filetime=$ligne->filetime;
        $filesize=$ligne->fileSize;
        $bytes=FormatBytes($filesize/1024);
        $filenameEnc=urlencode($filename);
        $delete=$tpl->icon_delete("Loadjs('$page?remove=$filenameEnc&md=$md')","AsWebMaster");
        $down=$tpl->icon_download("document.location.href='$page?download=$filenameEnc'","AsWebMaster");
        $restore=$tpl->icon_restore("Loadjs('$page?restore-js=$filenameEnc&function=$function&function0=$function0')","AsWebMaster");

        $restoreDB=$tpl->icon_restore("Loadjs('$page?restore-db-js=$filenameEnc&function=$function&function0=$function0')","AsWebMaster");

        $time=$tpl->time_to_date($filetime,true);
        $html[]="<tr id='$md'>
				<td style='width:1%' nowrap>$strong<i class='fas fa-file-alt'></i>&nbsp;$filename ($bytes)</strong></td>
				<td style='width:99%'>$time</td>
				<td style='width:1%' class='center' nowrap>$down</td>
				<td style='width:1%' class='center' nowrap>$restoreDB</td>
				<td style='width:1%' class='center' nowrap>$restore</td>
				<td style='width:1%' class='center' nowrap>$delete</td>
				</tr>";

    }

        $html[]="</tbody>";
        $html[]="</table>";
        $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
        $html[]="LoadAjax('backup-nginx-btns','$page?top-buttons=yes&function=$function&function0=$function0');";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
}
function download():bool{
    $sock=new sockets();
    $filename=$_GET["download"];
    $data=$sock->REST_API_NGINX("/reverse-proxy/download/backup/$filename");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        die();
    }

    $tfile=PROGRESS_DIR."/$filename";

    if(!$json->Status){
        header('Content-type: '."text/plain");
        header('Content-Transfer-Encoding: binary');
        header("Content-Disposition: attachment; filename=\"$filename.txt\"");
        header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
        header("Content-Length: ".strlen($json->Error));
        ob_clean();
        flush();
        echo $json->Error;
        return false;
    }


    header('Content-type: '."application/x-gzip");
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".filesize($tfile));
    ob_clean();
    flush();
   readfile($tfile);
    @unlink($tfile);
    return true;
}