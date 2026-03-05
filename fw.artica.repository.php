<?php
/**
 * Artica Repository Management Console
 * Manages local repository synchronization and push updates to remote agents
 */

include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
include_once dirname(__FILE__) . '/ressources/class.sockets.inc';

$page = CurrentPageName();
if(isset($_POST["repo_path"])){RepoPath_save();exit;}
if(isset($_GET["RepoPath-js"])){RepoPath_js();exit;}
if(isset($_GET["RepoPath-popup"])){RepoPath_popup();exit;}
if(isset($_GET["RepoPath-status"])){RepoPath_status();exit;}
if(isset($_GET["operation-details"])){operation_details();exit;}
if(isset($_GET["operation-details-popup"])){operation_details_popup();exit;}
if(isset($_GET["debians-js"])){debian_js();exit;}
if(isset($_POST["debian_version"])){debian_save();exit;}
if(isset($_GET["hotfix-add-js"])){hotfix_add_js();exit;}
if(isset($_GET["hotfix-add-step1"])){hotfix_add_step1();exit;}
if(isset($_GET["hotfix-add-step2"])){hotfix_add_step2();exit;}
if(isset($_GET["hotfix-add-step3"])){hotfix_add_step3();exit;}
if(isset($_POST["hotfix_main"])){hotfix_add_step2_save();exit;}
if(isset($_GET["upload-hotfix"])){hotfix_uploaded_js();exit;}
if(isset($_GET["delete-hotfix-js"])){hotfix_delete_js();exit;}
if(isset($_POST["delete-hotfix"])){hotfix_delete_perform();exit;}
if(isset($_GET["behavior-js"])){behavior_js();exit;}
if(isset($_GET["behavior-popup"])){behavior_popup();exit;}
if(isset($_POST["ArticaReposSoftwareLatestOnly"])){behavior_save();exit;}

if(isset($_GET["sp-list-start"])){service_packs_start();exit;}
if(isset($_GET["sp-list"])){service_packs_list();exit;}


if(isset($_GET["softwares"])){softwares();exit;}
if(isset($_GET["softwares-debian"])){softwares_debian();exit;}
if (isset($_GET["main-tabs"])) { main_tabs(); exit; }
if (isset($_GET["repository-status-start"])) { repository_status_start(); exit; }
if (isset($_GET["repository-status"])) { repository_status(); exit; }
if (isset($_GET["repository-stats"])) { repository_stats(); exit; }
if (isset($_GET["agents-list"])) { agents_list(); exit; }
if (isset($_GET["files-list"])) { files_list(); exit; }

if (isset($_GET["hotfix-list-start"])) { hotfix_list_start(); exit; }
if (isset($_GET["hotfix-list"])) { hotfix_list(); exit; }

if(isset($_GET["files-tab"])){files_tabs();exit;}
if (isset($_GET["agent-info-js"])) { agent_info_js(); exit; }
if (isset($_GET["agent-info-popup"])) { agent_info_popup(); exit; }
if (isset($_GET["push-update-js"])) { push_update_js(); exit; }
if (isset($_GET["push-update-tabs"])) { push_update_tabs(); exit; }

if(isset($_GET["push-update-groups"])){push_update_groups();exit;}
if(isset($_POST["push-update-groups-post"])){push_update_groups_post();exit;}

if (isset($_GET["push-update-popup"])) { push_update_popup(); exit; }
if(isset($_GET["push-results"])){push_update_table();exit;}

if (isset($_GET["start-sync"])) { start_sync(); exit; }
if (isset($_POST["push-update"])) { push_update(); exit; }
if(isset($_GET["debian_js"])){debian_js();exit;}
if(isset($_GET["debian-popup"])){debian_popup();exit;}

// Main page
page();

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{artica_repositories}",
        ico_database,"{artica_repositories_explain}","$page?main-tabs=yes",
        "artica-repos","progress-repos",false,"artica-repo-main");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{artica_repositories}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function RepoPath_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog3("{main_storage_directory}", "$page?RepoPath-popup=yes",600);
}
function behavior_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog3("{behavior}", "$page?behavior-popup=yes",600);
}
function behavior_popup():bool{
    $tpl=new template_admin();
    $ArticaReposSoftwareLatestOnly=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaReposSoftwareLatestOnly"));

    $html[]=$tpl->BigCircleCheckbox("ArticaReposSoftwareLatestOnly",
        "{download_only_latest_versions}","{download_only_latest_versions_explain}",
        $ArticaReposSoftwareLatestOnly,"dialogInstance3.close();");

    echo $tpl->_ENGINE_parse_body(implode("", $html));
    return true;
}
function behavior_save():bool{
    $ArticaReposSoftwareLatestOnly=intval($_POST["ArticaReposSoftwareLatestOnly"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/articarepos/config/latest-only",array("enabled"=>$ArticaReposSoftwareLatestOnly));
    return admin_tracks("Set download behavior to download latest only as $ArticaReposSoftwareLatestOnly for Artica meta");
}

function RepoPath_status():bool{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/move/status"));
    if(!property_exists($json,"in_progress")){
        return false;
    }
    if(!$json->in_progress){
        return false;
    }
    $operation=$json->operation;
    $progress=intval($operation->progress);
    $status=$operation->status;
    $copied_files=$operation->copied_files;
    $tpl=new template_admin();
    echo $tpl->GetProgressBarr($progress,"$status {files} $copied_files");
    /*
    "operation": {
        "status": "copying",
    "source_path": "/home/artica/artica-repository",
    "dest_path": "/data/artica-repository",
    "started_at": "2026-01-03T10:30:00Z",
    "total_files": 1500,
    "copied_files": 750,
    "total_bytes": 5368709120,
    "copied_bytes": 2684354560,
    "progress": 50.0,
    */
    return true;

}
function RepoPath_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/config/repo-path"));
    $repo_path=$json->repo_path;
    $html[]="<div id='repo-status-dir'></div>";
    $form[]=$tpl->field_browse_directory("repo_path","{main_storage_directory}",$repo_path);
    $html[]=$tpl->form_outside("",$form,"","{apply}",);
    $html[]="<script>";
    $html[]=$tpl->RefreshInterval_js("repo-status-dir",$page,"RepoPath-status=yes");
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
/*
    {
        "Status": true,
  "repo_path": "/home/artica/artica-repository",
  "repo_size_bytes": 5368709120,
  "repo_size_human": "5.00 GB",
  "partition": {
        "path": "/home/artica/artica-repository",
    "total_bytes": 107374182400,
    "used_bytes": 53687091200,
    "available_bytes": 53687091200,
    "usage_percent": 50.0,
    "total_human": "100.00 GB",
    "used_human": "50.00 GB",
    "available_human": "50.00 GB"
  }
*/
    return true;
}
function RepoPath_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $path=$_POST["repo_path"];
    $path_encoded=urlencode($path);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/move/validate?path=$path_encoded"));

    ///articarepos/move/validate?path=%2Fmedia%2FData
    //0:36:3636 sockets/REST_API[535]: /articarepos/move/validate?path=%2Fmedia%2FData= [{ "Status": false,
    // "validation": { "valid": false, "source_path": "/home/artica/artica-repository",
    // "dest_path": "/media/Data", "source_size_bytes": 0, "source_size_human": "0 B",
    // "dest_available_bytes": 8394752000, "dest_available_human": "7.82 GB",
    // "dest_partition": { "path": "/media", "total_bytes": 15791431680,
    // "used_bytes": 6572187648, "available_bytes": 8394752000,
    // "usage_percent": 41.61869411956953, "total_human": "14.71 GB", "used_human": "6.12 GB",
    // "available_human": "7.82 GB" }, "same_partition": true, "enough_space": true,
    // "dest_exists": true, "dest_empty": false, "error": "destination directory exists and is not empty" } }] (fw.artica.repository.php)
    //object(stdClass)#5 (2) { ["Status"]=> bool(false) ["validation"]=> object(stdClass)#6 (13) { ["valid"]=> bool(false)
    // ["source_path"]=> string(30) "/home/artica/artica-repository"
    // ["dest_path"]=> string(11) "/media/Data" ["source_size_bytes"]=> int(0)
    // ["source_size_human"]=> string(3) "0 B" ["dest_available_bytes"]=> int(8394752000) ["dest_available_human"]=> string(7) "7.82 GB" ["dest_partition"]=> object(stdClass)#7 (8) { ["path"]=> string(6) "/media" ["total_bytes"]=> int(15791431680) ["used_bytes"]=> int(6572187648) ["available_bytes"]=> int(8394752000) ["usage_percent"]=> float(41.61869411956953257458735606633126735687255859375) ["total_human"]=> string(8) "14.71 GB" ["used_human"]=> string(7) "6.12 GB" ["available_human"]=> string(7) "7.82 GB" } ["same_partition"]=> bool(true) ["enough_space"]=> bool(true) ["dest_exists"]=> bool(true) ["dest_empty"]=> bool(false) ["error"]=> string(45) "destination directory exists and is not empty" } } jserror:Error:():

    if(!property_exists($json,"Status")){
        echo $tpl->post_error("Invalid protocol");
        return false;
    }

    if(!$json->Status){
        var_dump($json);
        echo $tpl->post_error("{error} ".$json->validation->dest_path."(".$json->validation->dest_available_human.") ".$json->validation->error);
        return false;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/articarepos/config/repo-path",array("path"=>$path)));
    return admin_tracks("Change the Artica Meta repository path to $path");
}

function hotfix_delete_js():bool{
    $fname=$_GET["delete-hotfix-js"];
    $tpl = new template_admin();
    $md=$_GET["md"];
    $tpl->js_confirm_delete($fname,"delete-hotfix",$fname,"$('#$md').remove();");
    return true;
}
function hotfix_delete_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $fname=urlencode($_POST["delete-hotfix"]);
    writelogs("/articarepos/hotfix/delete/$fname",__FUNCTION__,__FILE__,__LINE__);
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/hotfix/delete/$fname");
    $json=json_decode($data);
    if(!$json->Status){
        writelogs("Error:$json->Error ($data)",__FUNCTION__,__FILE__,__LINE__);
        echo $json->Error;
        return false;
    }
    return true;
}
function hotfix_add_js():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    return $tpl->js_dialog2("{add_hotfix}", "$page?hotfix-add-step1=yes", 600);
}
function hotfix_add_step1():bool{
    $page = CurrentPageName();
    echo "<div id='hotfix_steps'></div><script>LoadAjax('hotfix_steps','$page?hotfix-add-step2=yes')</script>";
    return true;
}
function hotfix_add_step2():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $versions["4.50.000000"]="4.50.000000";
    $versions["4.60.000000"]="4.60.000000";
    $version=@file_get_contents("VERSION");
    for($i=0;$i<250;$i++){
        $SP[$i]=$i;
    }
    $CUP=@file_get_contents("/usr/share/artica-postfix/SP/$version");
    $form[]=$tpl->field_array_hash($versions,"hotfix_main","Artica",$version);
    $form[]=$tpl->field_array_hash($SP,"hotfix_sp","{service_pack}",$CUP);
    echo $tpl->form_outside("",$form,null,"{next}","LoadAjax('hotfix_steps','$page?hotfix-add-step3=yes')");

   
    return true;

}
function hotfix_add_step2_save():bool{
    $_SESSION["hotfix_add_step2"]=$_POST;
    return true;
}
function hotfix_add_step3():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $hotfix_main=$_SESSION["hotfix_add_step2"]["hotfix_main"];
    $hotfix_sp=$_SESSION["hotfix_add_step2"]["hotfix_sp"];
    $btn=$tpl->button_upload("{upload_a_file} $hotfix_main SP$hotfix_sp (12345678-90.tgz)",$page,"","&upload-hotfix=yes&main=$hotfix_main&sp=$hotfix_sp","");
    $html="<div class='center' style='margin:50px'>
    $btn</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function hotfix_uploaded_js():bool{
    $tpl=new template_admin();
    $filename=trim($_GET["file-uploaded"]);
    if(strlen($filename)<2){
        return $tpl->js_error("No file given");
    }
    $fullpath="/usr/share/artica-postfix/ressources/conf/upload/$filename";

    if(preg_match("#^([0-9]+)-([0-9]+)\s+\(.*?\.tgz#",$filename,$re)){
        $fullpath="/usr/share/artica-postfix/ressources/conf/upload/$re[1]-$re[2].tgz";
        $badPath="/usr/share/artica-postfix/ressources/conf/upload/$filename";
        @unlink($fullpath);
        @copy($badPath,$fullpath);
        @unlink($badPath);
        $filename="$re[1]-$re[2].tgz";
    }

    if(!preg_match("#^[0-9]+-[0-9]+\.tgz#",$filename)){
        @unlink($fullpath);
        return $tpl->js_error("$filename: wrong filename");
    }
    $main=$_GET["main"];
    $sp=$_GET["sp"];
    $ver=str_replace(".tgz","",$filename);
    $sock=new sockets();
    $json=json_decode($sock->REST_API_POST("/articarepos/upload/hotfix/$main/$sp/$ver",array("filename"=>$fullpath)));
    @unlink($fullpath);
    if(!$json->Status){
        return $tpl->js_error("$filename:$json->Error");
    }
    $tpl->js_ok("{add_package_later}","dialogInstance2.close();");
    return admin_tracks("Uploaded {$_GET["file-uploaded"]} new hotfix for $main $sp");
}

function main_tabs():bool {
    $tpl = new template_admin();
    $page = CurrentPageName();

    $tabs = array();
    $tabs["{repository_status}"] = "$page?repository-status-start=yes";
    $tabs["{repository}"] = "$page?files-tab=yes";
    echo $tpl->tabs_default($tabs);
    return true;
}
function repository_status_path($tpl,$repoPath){
    $page=CurrentPageName();
    $jsonMove=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/move/status"));
    if(!property_exists($jsonMove,"in_progress")) {
        $tpl->table_form_field_js("Loadjs('$page?RepoPath-js=yes')");
        $tpl->table_form_field_text("{path}", "<span style='text-transform: none'>$repoPath</span>", ico_directory);
        $tpl->table_form_field_js("");
        return $tpl;
    }

    if (!$jsonMove->in_progress) {
        $tpl->table_form_field_js("Loadjs('$page?RepoPath-js=yes')");
        $tpl->table_form_field_text("{path}", "<span style='text-transform: none'>$repoPath</span>", ico_directory);
        $tpl->table_form_field_js("");
        return $tpl;
    }
    $operation = $jsonMove->operation;
    $progress = intval($operation->progress);
    $status = $operation->status;
    $copied_files = $operation->copied_files;
    $tpl->table_form_field_text("{path}", "$progress% - $status {files}:$copied_files", ico_directory);
    return $tpl;

}
function repository_status_start():bool{

    $tpl = new template_admin();
    $page = CurrentPageName();
    $refresh=$tpl->RefreshInterval_js("artica-repo-main-status","$page","repository-status=yes",5);
    echo "<div id='artica-repo-main-status' style='margin-top:10px'></div>
    <script>
    $refresh
    </script>
";
return true;
}
function repository_status():bool {
    $tpl = new template_admin();
    $page = CurrentPageName();
    $sock = new sockets();


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/index/status"));
    if(property_exists($json,"rebuilding")) {
        if($json->rebuilding) {
            $tpl->table_form_field_text("{index}","{synchronization}",ico_refresh_animate);
            $tpl->table_form_field_js("");
        }
    }

    

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/sync/status"));



    $step = isset($json->current_step) ? $json->current_step : "-";
    $progress = isset($json->progress) ? $json->progress : 0;
    $filesDownloaded = isset($json->files_downloaded) ? $json->files_downloaded : 0;
    $bytesDownloaded = isset($json->bytes_downloaded) ? formatBytes($json->bytes_downloaded/1024) : "0 B";
    $lastError = isset($json->last_error) && !empty($json->last_error) ? $json->last_error : "";
    $partUsagePct = isset($json->repo_part->usage_percent) ? round($json->repo_part->usage_percent, 2) : 0;
    $partTotalHuman = isset($json->repo_part->total_human) ? $json->repo_part->total_human : '0 B';
    $partUsedHuman = isset($json->repo_part->used_human) ? $json->repo_part->used_human : '0 B';
    $partAvailHuman = isset($json->repo_part->available_human) ? $json->repo_part->available_human : '0 B';
    $repoSizeHuman = isset($json->repo_size_human) ? $json->repo_size_human : '0 B';

    $progressClass = $json->running ? "progress-bar-striped active" : "";
    if ($progress >= 100) {
        $progressClass = "progress-bar-success";
    }
    if($progress>0 && $progress<100) {
        $f[] = "<div class='progress'>";
        $f[] = "<div class='progress-bar $progressClass' style='width: $progress%'>$progress% $step $filesDownloaded $bytesDownloaded</div>";
        $f[] = "</div>";
    }



    $json = json_decode($sock->REST_API("/articarepos/status"));

    if (isset($json->error)) {
        $f[] = "<div class='alert alert-warning'>{status}: $json->error</div>";
    }
    $repoPath = isset($json->repo_path) ? $json->repo_path : "/home/artica/artica-repository";
    $totalFiles = isset($json->total_files) ? $json->total_files : 0;
    $totalSize = isset($json->total_size_bytes) ? formatBytes($json->total_size_bytes/1024) : "0 B";
    $syncRunning = isset($json->sync_running) && $json->sync_running ? "{yes}" : "{no}";
    $repoUsage = isset($json->repo_usage) ? round($json->repo_usage, 2) : 0;






    $tpl=repository_status_path($tpl,$repoPath);
    $ico=ico_retweet;
    if($json->sync_running){
        $ico=ico_refresh_animate;
    }
    $tpl->table_form_field_text("{synchronisation}",$syncRunning,$ico);
    $tpl->table_form_field_js("");


    if($totalFiles==0) {
        $tpl->table_form_field_bool("{total_files}",0,ico_file);
        $tpl->table_form_field_bool("{total_size}",0,ico_weight);
    }else{
        $tpl->table_form_field_text("{total_files}",$tpl->FormatNumber($totalFiles),ico_file);
        $tpl->table_form_field_text("{total_size}",$totalSize,ico_weight);
        $tpl->table_form_field_text("{partition}", "$partTotalHuman ($repoUsage%)", "fas fa-chart-pie");
        $tpl->table_form_field_text("{used}", "$partUsedHuman ($partUsagePct%)", ico_weight);
        $tpl->table_form_field_text("{available}", $partAvailHuman, "fas fa-arrow-alt-circle-down");
    }
    $tpl->table_form_field_js("Loadjs('$page?debians-js=yes')");
    $ArticaReposDebianVersions=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaReposDebianVersions");
    if(strlen($ArticaReposDebianVersions)<2){
        $ArticaReposDebianVersions="10,12,13";
    }

    $tpl->table_form_field_text("Debian {versions}",$ArticaReposDebianVersions,ico_linux);

    $ArticaReposSoftwareLatestOnly=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaReposSoftwareLatestOnly"));
    $tpl->table_form_field_js("Loadjs('$page?behavior-js=yes')");
    if($ArticaReposSoftwareLatestOnly==0){
        $tpl->table_form_field_text("{behavior}","{download_all_versions}",ico_download);
    }else{
        $tpl->table_form_field_text("{behavior}","{download_only_latest_versions}",ico_download);
    }

    $f[]=$tpl->table_form_compile();


    $topbuttons[] = array("Loadjs('$page?start-sync=yes')", ico_refresh, "{launch_updates}");
    $topbuttons[] = array("Loadjs('fw.artica.repository.hotfixes.php?js=yes')", ico_upload, "InstantHotfix");
    $topbuttons[] = array("Loadjs('fw.artica.repository.ServicePacks.php?js=yes')", ico_upload, "InstantServicePack");

    $TINY_ARRAY["TITLE"]="{artica_repositories}";
    $TINY_ARRAY["ICO"]=ico_database;
    $TINY_ARRAY["EXPL"]="{artica_repositories_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    // JavaScript
    $f[] = "<script>";
    $f[]=$jstiny;

    $f[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
    return true;
}
function softwares():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $tabs = array();
    $ArticaReposDebianVersions = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaReposDebianVersions");
    if (strlen($ArticaReposDebianVersions) < 2) {
        $ArticaReposDebianVersions = "10,12,13";
    }
    $tt = explode(",", $ArticaReposDebianVersions);
    foreach ($tt as $t) {
        $tabs["Debian $t"] = "$page?softwares-debian=$t";
    }
    echo "<div style='margin-top:10px'>".$tpl->tabs_default($tabs)."</div>";
    return true;
}
function softwares_debian():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $DebVer=$_GET["softwares-debian"];

    $TRCLASS=null;
    $html[]="<table id='ttdeb$DebVer' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{softwares}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='text-align:right'>{version}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{size}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text' nowrap></center></th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text' nowrap></center></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $TRCLASS="";
    $uri = "/articarepos/files/software/$DebVer";
    $ArticaLocalRepoPath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLocalRepoPath");
    if(strlen($ArticaLocalRepoPath)<4){
        $ArticaLocalRepoPath="/home/artica/artica-repository";
    }
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($uri));
    $ss1="style='width:1%' nowrap";
    foreach ($json->files as $file) {
        if($TRCLASS=="footable-odd"){$TRCLASS="";}else{$TRCLASS="footable-odd";}
        $num=md5(json_encode($file));
        $name=$file->name;
        $size=FormatBytes($file->size/1024);
        $filename=$file->filename;
        $local_path=$file->local_path;
        $local_path=str_replace($ArticaLocalRepoPath,"",$local_path);
        $filenameEnc=urlencode($local_path);

        $choose=$tpl->icon_upload("Loadjs('fw.artica.repository.softwares.deploy.php?js=$name&filename=$filenameEnc')");
        $version=str_replace(".tar.gz","",$filename);
        $html[] = "<tr class='$TRCLASS' id='$num'>";
        $html[] = "<td $ss1><strong>{{$name}}</strong></td>";
        $html[] = "<td $ss1>$version</span></td>";
        $html[] = "<td $ss1><strong>$size</td>";
        $html[] = "<td $ss1 class=center>$choose</center></span></td>";
        $html[] = "<td style='width:99%'></td>";
        $html[] = "</tr>";


    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";

    $topbuttons=array();
    //$topbuttons[] = array("Loadjs('$page?start-sync=yes')", ico_refresh, "{launch_updates}");
    $TINY_ARRAY["TITLE"]="{artica_repositories} {softwares} Debian $DebVer";
    $TINY_ARRAY["ICO"]=ico_database;
    $TINY_ARRAY["EXPL"]="{artica_repositories_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $js="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>";
    $html[]="NoSpinner();\n".$js;

    $html[]="$(document).ready(function() { $('#ttdeb$DebVer').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));




    return true;
}
function files_tabs():bool{
    echo "<div style='margin-top: 10px'>";
    $tpl = new template_admin();
    $page = CurrentPageName();

    $tabs = array();
    $tabs["{hotfixes}"] = "$page?hotfix-list-start=yes";
    $tabs["{service_packs}"] = "$page?sp-list-start=yes&type=servicepack";
    $tabs["{softwares}"] = "$page?softwares=yes";
    //$tabs["{dns_firewall}"] = "$page?files-list=yes&type=dnsfirewall";


    echo $tpl->tabs_default($tabs)."</div>";
    return true;
}
function hotfix_list_start():bool{
        $page = CurrentPageName();
        echo "<div id='hotfix-list'></div><script>LoadAjax('hotfix-list','$page?hotfix-list=yes');</script>";
        return true;
}
function service_packs_start():bool{
    $page = CurrentPageName();
    echo "<div id='servicepack-list'></div><script>LoadAjax('servicepack-list','$page?sp-list=yes');</script>";
    return true;
}
function hostfixCut($filename):array{

    if(preg_match("#hotfix-.+?-sp([0-9]+)\-([0-9]+)\.#",$filename,$re)) {
        $sp = intval($re[1]);
        $hf = formatHotfix($re[2]);
        return array("SP"=>$sp,"hf"=>$hf);
    }
    if(preg_match("#hotfix-.+?-sp([0-9]+)\-([0-9]+)-([0-9]+)\.#",$filename,$re)) {
        $sp = intval($re[1]);
        $hf = formatHotfix($re[2].$re[3]);
        return array("SP"=>$sp,"hf"=>$hf);
    }

    return array("SP"=>"-","hf"=>"-");
}
function hotfix_list():bool {
    $tpl = new template_admin();
    $page = CurrentPageName();
    $sock = new sockets();

    $type = isset($_GET["type"]) ? $_GET["type"] : "hotfix";
    $uri = "/articarepos/files/$type";

    $json = json_decode($sock->REST_API($uri));

    $f = array();


    // Files table
    $f[] = "<table class='table table-striped table-hover'>";
    $f[] = "<thead><tr>";
    $f[] = "<th>Artica</th>";
    $f[] = "<th>SP</th>";
    $f[] = "<th>{hotfix}</th>";
    $f[] = "<th>{size}</th>";
    $f[] = "<th>MD5</th>";
    $f[] = "<th>{updated}</th>";
    $f[] = "<th>{actions}</th>";
    $f[] = "<th></th>";
    $f[] = "<th></th>";
    $f[] = "</tr></thead>";
    $f[] = "<tbody>";

    if (isset($json->files) && is_array($json->files)) {
        foreach ($json->files as $file) {
            $filename = $file->filename;
            $size = formatBytes($file->size/1024);
            $md5 = substr($file->md5, 0, 12) . "...";
            $hff=hostfixCut($filename);
            $sp=$hff["SP"];
            $hf=$hff["hf"];
            $synced_at="-";
            if(isset($file->synced_at)){
                $synced_at=$tpl->time_to_date($tpl->GoToTimestamp($file->synced_at),true);
            }
            $artica_version=$file->artica_version;
            $fencode=urlencode($filename);
            $fid=md5(json_encode($file));
            $f[] = "<tr id='$fid'>";
            $f[] = "<td style='width:1%'><strong>$artica_version</strong></td>";
            $f[] = "<td style='width:1%'><strong>$sp</strong></td>";
            $f[] = "<td style='width:1%'><code>$hf</code></td>";
            $f[] = "<td style='width:1%'>$size</td>";
            $f[] = "<td style='width:1%'><small>$md5</small></td>";
            $f[] = "<td style='width:1%' nowrap>$synced_at</td>";
            $f[] = "<td>";
            $f[] = "<button class='btn btn-xs btn-primary' onclick=\"Loadjs('$page?push-update-js=$fencode&type=$type');\">";
            $f[] = "<i class='fas fa-upload'></i> {update2}";
            $f[] = "</button>";
            $f[] = "</td>";
            $f[] = "<td style='width:1%'>".$tpl->icon_delete("Loadjs('$page?delete-hotfix-js=$fencode&md=$fid')")."</td>";
            $f[] = "<td style='width:99%'>&nbsp;</td>";
            $f[] = "</tr>";
        }
    } else {
        $f[] = "<tr><td colspan='5' class='text-center'>{no_files}</td></tr>";
    }

    $f[] = "</tbody></table>";


    $topbuttons[] = array("Loadjs('$page?hotfix-add-js=yes')", ico_plus, "{add_hotfix}");
    $TINY_ARRAY["TITLE"]="{artica_repositories} {hotfixes}";
    $TINY_ARRAY["ICO"]=ico_cd;
    $TINY_ARRAY["EXPL"]="{artica_repositories_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $f[] = "<script>$headsjs</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
    return true;
}
function formatHotfix(string $s): string {
    // Expect exactly YYYYMMDDHH (10 digits)
    if (!preg_match('/^\d{10}$/', $s)) {
        return "";
    }

    return substr($s, 0, 8) . '-' . substr($s, 8, 2);
}
function files_list() {
    $tpl = new template_admin();
    $page = CurrentPageName();
    $sock = new sockets();

    $type = isset($_GET["type"]) ? $_GET["type"] : "official";
    $uri = "/articarepos/files/$type";

    $json = json_decode($sock->REST_API($uri));

    $f = array();


    // Files table
    $f[] = "<table class='table table-striped table-hover'>";
    $f[] = "<thead><tr>";
    $f[] = "<th>{filename}</th>";
    $f[] = "<th>{size}</th>";
    $f[] = "<th>MD5</th>";
    $f[] = "<th>{synced_at}</th>";
    $f[] = "<th>{actions}</th>";
    $f[] = "</tr></thead>";
    $f[] = "<tbody>";

    if (isset($json->files) && is_array($json->files)) {
        foreach ($json->files as $file) {
            $filename = $file->filename;
            $size = formatBytes($file->size/1024);
            $md5 = substr($file->md5, 0, 12) . "...";
            $synced_at="-";
            if(isset($file->synced_at)){
                $synced_at=$tpl->time_to_date($tpl->GoToTimestamp($file->synced_at),true);
            }
            var_dump($file);
            $f[] = "<tr>";
            $f[] = "<td><code>$filename</code></td>";
            $f[] = "<td>$size</td>";
            $f[] = "<td><small>$md5</small></td>";
            $f[] = "<td>$synced_at</td>";
            $f[] = "<td>";
            $f[] = "<button class='btn btn-xs btn-primary' onclick=\"Loadjs('$page?push-update-js=" . urlencode($filename) . "&type=$type');\">";
            $f[] = "<i class='fas fa-upload'></i> {update2}";
            $f[] = "</button>";
            $f[] = "</td>";
            $f[] = "</tr>";
        }
    } else {
        $f[] = "<tr><td colspan='5' class='text-center'>{no_files}</td></tr>";
    }

    $f[] = "</tbody></table>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
}
function service_packs_list() {
    $tpl = new template_admin();
    $page = CurrentPageName();
    $sock = new sockets();
    $uri = "/articarepos/files/servicepack";
    $json = json_decode($sock->REST_API($uri));

    $f = array();


    // Files table
    $f[] = "<table class='table table-striped table-hover'>";
    $f[] = "<thead><tr>";
    $f[] = "<th>Artica</th>";
    $f[] = "<th>{filename}</th>";
    $f[] = "<th>{size}</th>";
    $f[] = "<th>MD5</th>";
    $f[] = "<th>{updated}</th>";
    $f[] = "<th>{actions}</th>";
    $f[] = "</tr></thead>";
    $f[] = "<tbody>";

    if (isset($json->files) && is_array($json->files)) {
        foreach ($json->files as $file) {
            $filename = $file->filename;
            $size = formatBytes($file->size/1024);
            $md5 = substr($file->md5, 0, 12) . "...";
            $synced_at="-";
            if(isset($file->synced_at)){
                $synced_at=$tpl->time_to_date($tpl->GoToTimestamp($file->synced_at),true);
            }
            $spNUm=0;

            $artica_version=$file->artica_version;
            if(preg_match("#^ArticaP([0-9]+)-.+#", $filename, $m)) {
                $spNUm=$m[1];
            }

            $f[] = "<tr>";
            $f[] = "<td style='font-weight:bold;width:1%'>$artica_version</td>";
            $f[] = "<td  style='width:1%' nowrap>{service_pack} <strong style='font-size:16px'>$spNUm</strong></td>";
            $f[] = "<td  style='width:1%' nowrap>$size</td>";
            $f[] = "<td  style='width:1%' nowrap><small>$md5</small></td>";
            $f[] = "<td  style='width:1%' nowrap>$synced_at</td>";
            $f[] = "<td  style='width:1%' nowrap>";
            $f[] = "<button class='btn btn-xs btn-primary' onclick=\"Loadjs('$page?push-update-js=" . urlencode($filename) . "&type=servicepack');\">";
            $f[] = "<i class='fas fa-upload'></i> {update2}";
            $f[] = "</button>";
            $f[] = "</td>";
            $f[] = "</tr>";
        }
    } else {
        $f[] = "<tr><td colspan='5' class='text-center'>{no_files}</td></tr>";
    }

    $f[] = "</tbody></table>";
    $topbuttons=array();
    //$topbuttons[] = array("Loadjs('$page?hotfix-add-js=yes')", ico_plus, "{add_hotfix}");
    $TINY_ARRAY["TITLE"]="{artica_repositories} {service_packs}";
    $TINY_ARRAY["ICO"]=ico_cd;
    $TINY_ARRAY["EXPL"]="{artica_repositories_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $f[] = "<script>$headsjs</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
}



function agent_info_js():bool {
    $page = CurrentPageName();
    $tpl = new template_admin();
    $id = intval($_GET["agent-info-js"]);
    return $tpl->js_dialog2("{agent_info}", "$page?agent-info-popup=$id", 600);
}
function agent_info_popup():bool {
    $tpl = new template_admin();
    $sock = new sockets();
    $id = intval($_GET["agent-info-popup"]);

    $json = json_decode($sock->REST_API("/articarepos/agent/$id/info"));

    $f = array();

    if (isset($json->Error)) {
        $f[] = "<div class='alert alert-danger'>$json->Error</div>";
        echo $tpl->_ENGINE_parse_body(implode("\n", $f));
        return false;
    }

    $debianVersion = isset($json->debian_version) ? $json->debian_version : "-";
    $debianName = isset($json->debian_version_name) ? $json->debian_version_name : "-";
    $articaVersion = isset($json->artica_version) ? $json->artica_version : "-";
    $servicePack = isset($json->artica_service_pack) ? $json->artica_service_pack : "-";
    $hotfix = isset($json->artica_hotfix_version) ? $json->artica_hotfix_version : "-";

    $f[] = "<table class='table table-bordered'>";
    $f[] = "<tr><td><strong>Debian {version}</strong></td><td>$debianVersion ($debianName)</td></tr>";
    $f[] = "<tr><td><strong>Artica {version}</strong></td><td>$articaVersion</td></tr>";
    $f[] = "<tr><td><strong>{service_pack}</strong></td><td>$servicePack</td></tr>";
    $f[] = "<tr><td><strong>Hotfix</strong></td><td>$hotfix</td></tr>";
    $f[] = "</table>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
    return true;
}
function debian_js():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    return $tpl->js_dialog2("Debian {versions}", "$page?debian-popup=yes", 600);
}
function debian_popup():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $ArticaReposDebianVersions=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaReposDebianVersions");
    if(strlen($ArticaReposDebianVersions)<2){
        $ArticaReposDebianVersions="10,12,13";
    }
    $tt=explode(",",$ArticaReposDebianVersions);
    foreach($tt as $v){
        $Sel[$v]=true;
    }
    $f=array(10,12,13);
    foreach ($f as $v){
        $val=0;
        if(isset($Sel[$v])){
            $val=1;
        }
        $form[]=$tpl->field_checkbox("debian_$v","Debian $v",$val);
    }
    $form[]=$tpl->field_hidden("debian_version","yes");
    echo $tpl->form_outside("",$form,"","{apply}","dialogInstance2.close();");
    return true;
}
function debian_save():bool{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $tt=array();
    foreach ($_POST as $k=>$v){
        if(preg_match("#^debian_([0-9]+)$#",$k,$m)){
            if($v==0){
                continue;
            }
            $tt[]=$m[1];
        }
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaReposDebianVersions",@implode(",",$tt));
    return admin_tracks("Save fetch repositories for Debian: ".@implode(",",$tt));
}
function push_update_js():bool {
    $page = CurrentPageName();
    $tpl = new template_admin();
    $filename = isset($_GET["push-update-js"]) ? urlencode($_GET["push-update-js"]) : "";
    $type = isset($_GET["type"]) ? $_GET["type"] : "";
    $agentId = isset($_GET["agent_id"]) ? intval($_GET["agent_id"]) : 0;
    return $tpl->js_dialog2("{push_update} $filename", "$page?push-update-tabs=yes&filename=$filename&type=$type&agent_id=$agentId", 700);
}
function push_update_tabs():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $filename = isset($_GET["filename"]) ? urlencode($_GET["filename"]) : "";
    $type = isset($_GET["type"]) ? $_GET["type"] : "";
    $preselectedAgent = isset($_GET["agent_id"]) ? intval($_GET["agent_id"]) : 0;
    $array["{nodes}"]="$page?push-update-popup=yes&filename=$filename&type=$type&agent_id=$preselectedAgent";
    $array["{groups}"]="$page?push-update-groups=yes&filename=$filename&type=$type&agent_id=$preselectedAgent";
    echo $tpl->tabs_default($array);
    return true;
}

function push_update_groups():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $sock = new sockets();

    $filename = isset($_GET["filename"]) ? urldecode($_GET["filename"]) : "";
    $type = isset($_GET["type"]) ? $_GET["type"] : "";
    $groupsJson = json_decode($sock->REST_API("/netagents/groups"));
    $groups = is_array($groupsJson) ? $groupsJson : [];

    $f = [];
    $f[] = "<div class='form-group' style='margin-top:10px'>";
    $f[] = "<label>{select_groups} &raquo;&raquo; " . htmlspecialchars($filename) . "</label>";
    $f[] = "<div style='margin-bottom:6px'>";
    $f[] = "<button type='button' class='btn btn-xs btn-default' onclick=\"$('.group-checkbox').prop('checked',true);\">";
    $f[] = "<i class='fas fa-check-square'></i> {select_all}";
    $f[] = "</button>";
    $f[] = " <button type='button' class='btn btn-xs btn-default' onclick=\"$('.group-checkbox').prop('checked',false);\">";
    $f[] = "<i class='fas fa-square'></i> {unselect_all}";
    $f[] = "</button>";
    $f[] = "</div>";
    $f[] = "<div style='max-height:200px;overflow-y:auto;border:1px solid #ddd;padding:10px;'>";

    if (!empty($groups)) {
        foreach ($groups as $grp) {
            $gid   = intval($grp->id ?? 0);
            $gname = htmlspecialchars($grp->name ?? "Group #$gid");
            $cnt   = intval($grp->agent_count ?? 0);
            $badge = "<span class='badge' style='background:#1c84c6;color:#fff;margin-left:6px'>$cnt</span>";
            $f[] = "<div class='checkbox'>";
            $f[] = "<label>";
            $f[] = "<input type='checkbox' class='group-checkbox' value='$gid'>";
            $f[] = " $gname$badge";
            $f[] = "</label>";
            $f[] = "</div>";
        }
    } else {
        $f[] = "<p class='text-muted'>{no_data}</p>";
    }

    $f[] = "</div>";
    $f[] = "</div>";
    $f[] = "<div style='width:100%;padding-right:10px;text-align:right;'>";
    $f[] = "<button class='btn btn-primary btn-lg' onclick=\"DoPushUpdateGroups();\">";
    $f[] = "<i class='fas fa-upload'></i> {push_update}";
    $f[] = "</button>";
    $f[] = "</div>";
    $f[] = "<div id='push-result-groups' style='margin-top:40px;border-top:1px solid #cccccc;padding-top:5px'></div>";

    $filenameVal = addslashes($filename);
    $typeVal     = addslashes($type);

    $f[] = "<script>";
    $f[] = "function DoPushUpdateGroups(){";
    $f[] = "  var gids=[];";
    $f[] = "  \$('.group-checkbox:checked').each(function(){ gids.push(\$(this).val()); });";
    $f[] = "  if(gids.length==0){ alert('{select_at_least_one}'); return; }";
    $f[] = "  \$('#push-result-groups').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {push_update}...</div>');";
    $f[] = "  \$.post('$page',{";
    $f[] = "    'push-update-groups-post':1,";
    $f[] = "    'filename':'$filenameVal',";
    $f[] = "    'type':'$typeVal',";
    $f[] = "    'group_ids':gids.join(',')";
    $f[] = "  },function(data){ \$('#push-result-groups').html(data); });";
    $f[] = "}";
    $f[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
    return true;
}

function push_update_groups_post():void{
    $tpl      = new template_admin();
    $sock     = new sockets();
    $filename = trim($_POST["filename"] ?? "");
    $type     = trim($_POST["type"] ?? "");
    $groupIds = trim($_POST["group_ids"] ?? "");

    if (empty($filename) || empty($groupIds)) {
        echo $tpl->div_warning("{missing_parameters}");
        return;
    }

    // Expand each group to its agent IDs
    $agentIds = [];
    foreach (explode(",", $groupIds) as $rawGid) {
        $gid = intval(trim($rawGid));
        if ($gid <= 0) continue;
        $grp = json_decode($sock->REST_API("/netagents/groups/$gid"));
        if (!is_object($grp) || (isset($grp->Status) && !$grp->Status)) continue;
        foreach (($grp->agents ?? []) as $agent) {
            $aid = intval($agent->id ?? 0);
            if ($aid > 0) $agentIds[$aid] = true;
        }
    }

    if (empty($agentIds)) {
        echo $tpl->div_warning("{no_agents_in_selected_groups}");
        return;
    }

    $json = json_decode($sock->REST_API_POST("/articarepos/push", [
        "filename"  => $filename,
        "type"      => $type,
        "agent_ids" => implode(",", array_keys($agentIds)),
    ]));

    if (isset($json->Status) && !$json->Status) {
        echo $tpl->div_error($json->Error ?? "{error}");
        return;
    }

    $count = count($agentIds);
    echo $tpl->div_success("<i class='fas fa-check-circle'></i> {push_update}: $count {agents}");
    admin_tracks("Push update $filename to " . count($agentIds) . " agents via groups $groupIds");
}

function push_update_popup():bool {
    $page = CurrentPageName();
    $tpl = new template_admin();
    $sock = new sockets();

    $filename = isset($_GET["filename"]) ? urldecode($_GET["filename"]) : "";
    $type = isset($_GET["type"]) ? $_GET["type"] : "";
    $preselectedAgent = isset($_GET["agent_id"]) ? intval($_GET["agent_id"]) : 0;

    $agentsJson = json_decode($sock->REST_API("/netagents/alist?status=online&enabled=1"));
    $f = array();
    $f[] = "<div class='form-group' style='margin-top:10px'>";
    $f[] = "<label>{select_agents} &raquo;&raquo; $filename</label>";
    $f[] = "<div style='margin-bottom:6px'>";
    $f[] = "<button type='button' class='btn btn-xs btn-default' onclick=\"$('.agent-checkbox').prop('checked',true);\">";
    $f[] = "<i class='fas fa-check-square'></i> {select_all}";
    $f[] = "</button>";
    $f[] = " <button type='button' class='btn btn-xs btn-default' onclick=\"$('.agent-checkbox').prop('checked',false);\">";
    $f[] = "<i class='fas fa-square'></i> {unselect_all}";
    $f[] = "</button>";
    $f[] = "</div>";
    $f[] = "<div style='max-height:200px;overflow-y:auto;border:1px solid #ddd;padding:10px;'>";

    if (isset($agentsJson->agents) && is_array($agentsJson->agents)) {
        foreach ($agentsJson->agents as $agent) {
            if ($agent->status != "online") continue;
            $checked = ($preselectedAgent == $agent->id) ? "checked" : "";
            $f[] = "<div class='checkbox'>";
            $f[] = "<label>";
            $f[] = "<input type='checkbox' class='agent-checkbox' value='{$agent->id}' $checked>";
            $f[] = " $agent->hostname ($agent->ip_address)";
            $f[] = "</label>";
            $f[] = "</div>";
        }
    }

    $f[] = "</div>";
    $f[] = "</div>";
    $f[] = "<div style='width:100%;padding-right:10px;text-align:right;'>";
    $f[] = "<button class='btn btn-primary btn-lg' onclick=\"DoPushUpdate();\">";
    $f[] = "<i class='fas fa-upload'></i> {push_update}";
    $f[] = "</button>";
    $f[] = "</div>";
    $f[] = "<div id='push-result' style='margin-top:40px;border-top:1px solid #cccccc;padding-top:5px'></div>";
    // JavaScript
    $filenameVal = !empty($filename) ? $filename : "'+$('#push-filename').val()+'";
    $typeVal = !empty($type) ? $type : "'+$('#push-filename option:selected').data('type')+'";
    $js=$tpl->RefreshInterval_js("push-result",$page,"push-results=yes");
    $f[] = "<script>";
    $f[] = "function DoPushUpdate() {";
    $f[] = "  var agentIds = [];";
    $f[] = "  $('.agent-checkbox:checked').each(function(){ agentIds.push($(this).val()); });";
    $f[] = "  if (agentIds.length == 0) { alert('Please select at least one agent'); return; }";
    $f[] = "  $.post('$page', {";
    $f[] = "    'push-update': 1,";
    $f[] = "    'filename': '$filenameVal',";
    $f[] = "    'type': '$typeVal',";
    $f[] = "    'agent_ids': agentIds.join(',')";
    $f[] = "  }, function(data) {";
    $f[] = "    $('#push-result').html(data);";
    $f[] = "  });";
    $f[] = "}";
    $f[]=$js;
    $f[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
    return true;
}

function start_sync():bool {
    $sock = new sockets();

    $ArticaReposDebianVersions=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaReposDebianVersions");
    if(strlen($ArticaReposDebianVersions)<2){
        $ArticaReposDebianVersions="10,12,13";
    }

    $json = json_decode($sock->REST_API_POST("/articarepos/sync", array(
        "debian_versions" => $ArticaReposDebianVersions
    )));

    echo json_encode($json,true);

    if(isset($json["status"])){
        $tpl=new template_admin();
        $tpl->js_ok($json["status"]);
        return admin_tracks("Start manually Meta repository sync...");
    }
    return false;

}

function push_update():bool{
    $tpl = new template_admin();
    $sock = new sockets();

    $filename = isset($_POST["filename"]) ? trim($_POST["filename"]) : "";
    $type = isset($_POST["type"]) ? trim($_POST["type"]) : "";
    $agentIds = isset($_POST["agent_ids"]) ? trim($_POST["agent_ids"]) : "";

    if (empty($filename) || empty($agentIds)) {
        echo $tpl->div_warning("{missing_parameters}");
        return false;
    }

    $json = json_decode($sock->REST_API_POST("/articarepos/push", array(
        "filename" => $filename,
        "type" => $type,
        "agent_ids" => $agentIds
    )));

    return true;
}

function repository_stats() {
    $tpl = new template_admin();
    $sock = new sockets();

    $json = json_decode($sock->REST_API("/articarepos/stats"));

    $f = array();
    $f[] = "<div class='row'>";

    $stats = array(
        array("total_files", "{total_files}", "fa-file"),
        array("official_count", "{officials}", "fa-box"),
        array("service_pack_count", "{service_packs}", "fa-puzzle-piece"),
        array("hotfix_count", "{hotfixes}", "fa-wrench"),
        array("software_count", "{softwares}", "fa-cogs")
    );

    foreach ($stats as $s) {
        $key = $s[0];
        $label = $s[1];
        $icon = $s[2];
        $value = isset($json->$key) ? $json->$key : 0;

        $f[] = "<div class='col-md-2'>";
        $f[] = "<div class='panel panel-info'>";
        $f[] = "<div class='panel-body text-center'>";
        $f[] = "<i class='fas $icon fa-2x'></i>";
        $f[] = "<h3>$value</h3>";
        $f[] = "<small>$label</small>";
        $f[] = "</div></div></div>";
    }

    $f[] = "</div>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
}


function push_update_table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();

    $json=json_decode($sock->REST_API("/articarepos/push/status"));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_warning(json_last_error_msg());
        return false;
    }

    if (!isset($json->operations) || !is_array($json->operations) || count($json->operations) == 0) {
        return true;
    }

    $f=array();
    $f[]="<table class='table table-striped table-hover'>";
    $f[]="<thead>";
    $f[]="<tr>";
    $f[]="  <th>{status}</th>";
    $f[]="  <th>{progress}</th>";
    $f[]="  <th>{success}</th>";
    $f[]="  <th>{failed2}</th>";
    $f[]="  <th>{started}</th>";
    $f[]="  <th>{actions}</th>";
    $f[]="</tr>";
    $f[]="</thead>";
    $f[]="<tbody>";

    foreach ($json->operations as $op) {

        $id = isset($op->id) ? $op->id : "";
        $status = isset($op->status) ? $op->status : "unknown";
        $filename = isset($op->filename) ? basename($op->filename) : "-";
        $total = isset($op->total_agents) ? intval($op->total_agents) : 0;
        $completed = isset($op->completed) ? intval($op->completed) : 0;
        $successCount = isset($op->success_count) ? intval($op->success_count) : 0;
        $failCount = isset($op->fail_count) ? intval($op->fail_count) : 0;
        $startedAt = isset($op->started_at) ? $op->started_at : "";
        $groupName = isset($op->group_name) && !empty($op->group_name) ? " ({$op->group_name})" : "";

        // Format started time
        if (!empty($startedAt)) {

            $startedAt =$tpl->time_to_date($tpl->GoToTimestamp($startedAt),true);
        }

        // Calculate progress
        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;

        // Status badge
        $statusBadge = get_status_badge($status);

        // Progress bar
        $progressBar = get_progress_bar($progress, $status);

        $f[]="<tr>";

        $f[]="  <td style='width: 1%' nowrap>$statusBadge</td>";
        $f[]="  <td style='min-width:150px;'>$progressBar</td>";
        $f[]="  <td class='text-success' style='width: 1%' nowrap><strong>$successCount</strong></td>";
        $f[]="  <td class='text-danger' style='width: 1%' nowrap><strong>$failCount</strong></td>";
        $f[]="  <td style='width: 1%' nowrap>$startedAt</td>";
        $f[]="  <td style='width: 1%' nowrap><button class='btn btn-info btn-xs' OnClick=\"Loadjs('$page?operation-details=$id');\"><i class='fas fa-eye'></i> {details}</button></td>";
        $f[]="</tr>";
        $f[]="<tr>";
        $f[]="  <td colspan='6' style='border-bottom:1px solid #cccccc;padding-bottom:10px'>{file}: <strong>$filename</strong>$groupName</td>";
        $f[]="</tr>";
    }

    $f[]="</tbody>";
    $f[]="</table>";
    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
    return true;
}
function get_progress_bar(int $progress, string $status):string {
    $barClass = "progress-bar-info";
    if ($status == "completed") {
        $barClass = "progress-bar-success";
    } elseif ($status == "failed") {
        $barClass = "progress-bar-danger";
    }

    return "<div class='progress' style='margin-bottom:0;'>
        <div class='progress-bar $barClass' role='progressbar' style='width: $progress%;'>$progress%</div>
    </div>";
}
function get_status_badge(string $status):string {
    switch ($status) {
        case "pending":
            return "<span class='label label-default'><i class='fas fa-clock'></i> {pending}</span>";
        case "running":
            return "<span class='label label-info'><i class='fas fa-spinner fa-spin'></i> {running}</span>";
        case "completed":
            return "<span class='label label-success'><i class='fas fa-check'></i> {completed}</span>";
        case "failed":
            return "<span class='label label-danger'><i class='fas fa-times'></i> {failed}</span>";
        default:
            return "<span class='label label-default'>$status</span>";
    }
}
function operation_details():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=$_GET["operation-details"];
    return $tpl->js_dialog10("{operation_details}","$page?operation-details-popup=$id",800);
}

function operation_details_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $id=$_GET["operation-details-popup"];

    $json=json_decode($sock->REST_API("/articarepos/push/status/$id"));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_warning(json_last_error_msg());
        return false;
    }

    if (!isset($json->operation)) {
        echo $tpl->div_warning("{operation_not_found}");
        return false;
    }

    $op = $json->operation;
    $status = isset($op->status) ? $op->status : "unknown";
    $filename = isset($op->filename) ? $op->filename : "-";
    $total = isset($op->total_agents) ? intval($op->total_agents) : 0;
    $completed = isset($op->completed) ? intval($op->completed) : 0;
    $successCount = isset($op->success_count) ? intval($op->success_count) : 0;
    $failCount = isset($op->fail_count) ? intval($op->fail_count) : 0;
    $startedAt = isset($op->started_at) ? $op->started_at : "";
    $completedAt = isset($op->completed_at) ? $op->completed_at : "";
    $groupName = isset($op->group_name) && !empty($op->group_name) ? $op->group_name : "-";
    $groupId = isset($op->group_id) ? intval($op->group_id) : 0;

    // Format times
    if (!empty($startedAt)) {
        $startedAt = $tpl->time_to_date($tpl->GoToTimestamp($startedAt),true);
    }

    if (!empty($completedAt) && $completedAt != "0001-01-01T00:00:00Z") {
        $completedAt = $tpl->time_to_date($tpl->GoToTimestamp($completedAt),true);
    } else {
        $completedAt = "-";
    }

    $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
    $statusBadge = get_status_badge($status);
    $progressBar = get_progress_bar($progress, $status);

    $f=array();
    $startedAt=$tpl->time_to_date($tpl->GoToTimestamp($startedAt));
    $completedAt=$tpl->time_to_date($tpl->GoToTimestamp($completedAt));

    // Summary section
    $f[]="<div class='row'>";
    $f[]="  <div class='col-lg-12'>";
    $f[]="    <div class='panel panel-default'>";
    $f[]="      <div class='panel-heading'><strong>{operation_summary}</strong></div>";
    $f[]="      <div class='panel-body'>";
    $f[]="        <table class='table table-condensed'>";
    $f[]="          <tr><td><strong>ID:</strong></td><td><code>$id</code></td></tr>";
    $f[]="          <tr><td><strong>{status}:</strong></td><td>$statusBadge</td></tr>";
    $f[]="          <tr><td><strong>{file}:</strong></td><td nowrap>$filename</td></tr>";
    if ($groupId > 0) {
        $f[]="          <tr><td><strong>{group}:</strong></td><td>$groupName (ID: $groupId)</td></tr>";
    }
    $f[]="          <tr><td><strong>{progress}:</strong></td><td style='width:50%;'>$progressBar</td></tr>";
    $f[]="          <tr><td><strong>{agents}:</strong></td><td>$completed / $total</td></tr>";
    $f[]="          <tr><td><strong>{success}:</strong></td><td class='text-success'><strong>$successCount</strong></td></tr>";
    $f[]="          <tr><td><strong>{failed}:</strong></td><td class='text-danger'><strong>$failCount</strong></td></tr>";
    $f[]="          <tr><td><strong>{started}:</strong></td><td>$op->started_at- $startedAt</td></tr>";
    $f[]="          <tr><td><strong>{completed_at}:</strong></td><td>$completedAt</td></tr>";
    $f[]="        </table>";
    $f[]="      </div>";
    $f[]="    </div>";
    $f[]="  </div>";
    $f[]="</div>";

    // Results section
    if (isset($op->results) && is_array($op->results) && count($op->results) > 0) {
        $f[]="<div class='row'>";
        $f[]="  <div class='col-lg-12'>";
        $f[]="    <div class='panel panel-default'>";
        $f[]="      <div class='panel-heading'><strong>{agent_results}</strong></div>";
        $f[]="      <div class='panel-body' style='max-height:400px; overflow-y:auto;'>";
        $f[]="        <table class='table table-striped table-condensed'>";
        $f[]="          <thead><tr><th>{agent_id}</th><th>{hostname}</th><th>{result}</th><th>{message}</th></tr></thead>";
        $f[]="          <tbody>";

        foreach ($op->results as $result) {
            $agentId = isset($result->agent_id) ? intval($result->agent_id) : 0;
            $hostname = isset($result->hostname) ? $result->hostname : "Agent #$agentId";
            $success = isset($result->success) && $result->success;
            $message = isset($result->message) ? $result->message : "-";

            $rowClass = $success ? "" : "danger";
            $resultIcon = $success ? "<i class='fas fa-check text-success'></i>" : "<i class='fas fa-times text-danger'></i>";

            $f[]="          <tr class='$rowClass'>";
            $f[]="            <td>$agentId</td>";
            $f[]="            <td>$hostname</td>";
            $f[]="            <td>$resultIcon</td>";
            $f[]="            <td>$message</td>";
            $f[]="          </tr>";
        }

        $f[]="          </tbody>";
        $f[]="        </table>";
        $f[]="      </div>";
        $f[]="    </div>";
        $f[]="  </div>";
        $f[]="</div>";
    }

    // Auto-refresh if running
    if ($status == "running" || $status == "pending") {
        $f[]="<div id='operation-refresh-$id'></div>";
        $f[]="<script>setTimeout(function(){ LoadAjax('operation-refresh-$id','$page?operation-details-popup=$id'); }, 2000);</script>";
    }

    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
    return true;
}