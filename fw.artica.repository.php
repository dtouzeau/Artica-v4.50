<?php
/**
 * Artica Repository Management Console
 * Manages local repository synchronization and push updates to remote agents
 */

include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
include_once dirname(__FILE__) . '/ressources/class.sockets.inc';
include_once dirname(__FILE__) . '/ressources/class.netagent.label.inc';
include_once dirname(__FILE__) . '/ressources/class.artica.push.ui.inc';

$page = CurrentPageName();
if(isset($_POST["repo_path"])){RepoPath_save();exit;}
// Pentest 2026-11-04 : filename/push-update-js/delete-hotfix-js/type/softwares-debian/operation-details etaient recopies
// sans encodage dans la page (XSS). Ce sont des noms de paquet, de type de depot, de version Debian et d'operation.
foreach(array("filename","push-update-js","delete-hotfix-js","softwares-debian") as $k){
    if(isset($_GET[$k])){$_GET[$k]=preg_replace('/[^A-Za-z0-9_.+~-]/','',(string)$_GET[$k]);}
}
foreach(array("type","operation-details","operation-details-popup","main","sp") as $k){
    if(isset($_GET[$k])){$_GET[$k]=preg_replace('/[^A-Za-z0-9_.-]/','',(string)$_GET[$k]);}
}
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
// Droit verifie cote serveur : le menu ne montre la page qu'a AsArticaMetaAdmin,
// mais les handlers d'ecriture repondaient a toute session ouverte.
// artrepo_can_write() : ressources/class.users.menus.inc (predicat partage avec
// fw.artica.repository.hotfixes.php et .ServicePacks.php ; faux pour un compte AsMetaAdmin seul).
function behavior_save():bool{
    $tpl=new template_admin();
    if(!artrepo_can_write()){echo $tpl->post_error("{ERROR_NO_PRIVS}");return false;}
    $ArticaReposSoftwareLatestOnly=intval($_POST["ArticaReposSoftwareLatestOnly"])==1?1:0;
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
    $html[]=$tpl->form_outside("",$form,"","{apply}","dialogInstance3.close();if(typeof ArtRepoRefresh==='function'){ArtRepoRefresh();}","AsArticaMetaAdmin");
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
    if(!artrepo_can_write()){echo $tpl->post_error("{ERROR_NO_PRIVS}");return false;}
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
        echo $tpl->post_error("{error} ".$json->validation->dest_path."(".$json->validation->dest_available_human.") ".$json->validation->error);
        return false;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/articarepos/config/repo-path",array("path"=>$path)));
    return admin_tracks("Change the Artica Meta repository path to $path");
}

function hotfix_delete_js():bool{
    if(!artrepo_can_write()){$tpl=new template_admin();$tpl->js_error("{ERROR_NO_PRIVS}");return false;}
    $fname=(string)$_GET["delete-hotfix-js"];
    $md=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["md"] ?? ""));
    $tpl=new template_admin();
    $p=hfx_parse($fname);
    $what=htmlspecialchars($fname);
    if($p["ts"]>0){$what=date("Y-m-d H:i",$p["ts"])." (SP ".$p["sp"].")";}
    $text="{delete_this_item}:<br><strong style=\"font-size:16px\">$what</strong><br><span style=\"font-family:monospace\">".htmlspecialchars($fname)."</span><br><br>{hfx_delete_warn}";
    $tpl->js_confirm_delete($text,"delete-hotfix",$fname,"if(typeof HfxRemoved==='function'){HfxRemoved('$md');}",true);
    return true;
}
function hotfix_delete_perform():bool{
    $tpl=new template_admin();
    if(!artrepo_can_write()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");return false;}
    $tpl->CLEAN_POST();
    $raw=(string)$_POST["delete-hotfix"];
    $fname=urlencode($raw);
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/hotfix/delete/$fname");
    $json=json_decode($data);
    if(!is_object($json) || empty($json->Status)){
        $err=is_object($json)&&isset($json->Error)?(string)$json->Error:trim((string)$data);
        // Le contenu s'affiche dans un $.alert HTML : texte echappe, pas de prefixe jserror:.
        echo htmlspecialchars($err==""?"Artica REST API: no answer":$err);
        return false;
    }
    return admin_tracks("Delete Artica Meta repository hotfix $raw");
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
    if(!artrepo_can_write()){$tpl=new template_admin();echo $tpl->post_error("{ERROR_NO_PRIVS}");return false;}
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
    if(!artrepo_can_write()){$tpl=new template_admin();$tpl->js_error("{ERROR_NO_PRIVS}");return false;}
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
        // Pentest 2026-11-04 : file-uploaded etait recopie sans encodage dans la page (XSS).
        return $tpl->js_error(req_html($filename).": wrong filename");
    }
    $main=$_GET["main"];
    $sp=$_GET["sp"];
    $ver=str_replace(".tgz","",$filename);
    $sock=new sockets();
    $json=json_decode($sock->REST_API_POST("/articarepos/upload/hotfix/$main/$sp/$ver",array("filename"=>$fullpath)));
    @unlink($fullpath);
    if(!$json->Status){
        return $tpl->js_error(req_html("$filename:$json->Error"));
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
// Hote du tableau de bord (onglet "Repository status", maquette stitch_artica_meta_repo)
// L'hote est recharge toutes les 5 s par un timer unique (window.ArtRepoTimer) ; le
// rechargement saute quand une modale ou une SweetAlert est ouverte.
function repository_status_start():bool{
    $page=CurrentPageName();
    echo "<div id='artrepo-host' style='margin-top:10px'></div>
<script>
window.ArtRepoTinySig='';
LoadAjaxSilent('artrepo-host','$page?repository-status=yes');
if(window.ArtRepoTimer){clearInterval(window.ArtRepoTimer);}
window.ArtRepoTimer=setInterval(function(){
    if(!document.getElementById('artrepo-host')){clearInterval(window.ArtRepoTimer);window.ArtRepoTimer=null;return;}
    if(document.hidden){return;}
    if(jQuery('.modal.in,.modal.show').length>0){return;}
    if(jQuery('.sweet-alert.visible,.sweet-alert.showSweetAlert').length>0){return;}
    LoadAjaxSilent('artrepo-host','$page?repository-status=yes');
},5000);
</script>";
    return true;
}

function repository_status():bool {
    $tpl=new template_admin();
    $m=artrepo_model();
    $html=array();
    $html[]="<div class='artrepo ak'>";
    $html[]=artrepo_banner($m);
    $html[]="<div class='ak-grid-4 artrepo-kpis'>";
    $html[]=artrepo_kpi_files($m);
    $html[]=artrepo_kpi_size($m);
    $html[]=artrepo_kpi_index($m);
    $html[]=artrepo_kpi_breakdown($m);
    $html[]="</div>";
    $html[]="<div class='ak-grid-2 artrepo-cols'>";
    $html[]=artrepo_card_storage($m);
    $html[]=artrepo_card_mirror($m);
    $html[]="</div>";
    $html[]="</div>";
    echo artrepo_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).artrepo_js($m).artrepo_tiny();
    return true;
}

// Un seul passage sur le demon par rafraichissement : quatre routes + trois reglages.
function artrepo_model():array{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $sync=json_decode($sock->REST_API("/articarepos/sync/status"));
    $st=json_decode($sock->REST_API("/articarepos/status"));
    $idx=json_decode($sock->REST_API("/articarepos/index/status"));
    $mv=json_decode($sock->REST_API("/articarepos/move/status"));
    if(!is_object($sync)){$sync=new stdClass();}
    if(!is_object($st)){$st=new stdClass();}
    if(!is_object($idx)){$idx=new stdClass();}
    if(!is_object($mv)){$mv=new stdClass();}

    $m=array();
    $m["running"]=!empty($sync->running) || !empty($st->sync_running);
    $m["step"]=isset($sync->current_step)?(string)$sync->current_step:"";
    $m["progress"]=isset($sync->progress)?intval($sync->progress):0;
    $m["started"]=artrepo_ts(isset($sync->started_at)?$sync->started_at:"");
    $m["files_dl"]=isset($sync->files_downloaded)?intval($sync->files_downloaded):0;
    $m["bytes_dl"]=isset($sync->bytes_downloaded)?floatval($sync->bytes_downloaded):0;
    $m["last_error"]=isset($sync->last_error)?trim((string)$sync->last_error):"";

    $part=isset($sync->repo_part)&&is_object($sync->repo_part)?$sync->repo_part:new stdClass();
    $m["part_total"]=isset($part->total_bytes)?floatval($part->total_bytes):0;
    $m["part_used"]=isset($part->used_bytes)?floatval($part->used_bytes):0;
    $m["part_avail"]=isset($part->available_bytes)?floatval($part->available_bytes):0;
    $m["part_pct"]=isset($part->usage_percent)?floatval($part->usage_percent):0;
    $m["repo_bytes"]=isset($sync->repo_size_bytes)?floatval($sync->repo_size_bytes):0;
    $m["repo_pct"]=isset($sync->repo_usage)?floatval($sync->repo_usage):0;

    $m["repo_path"]=isset($st->repo_path)&&$st->repo_path!=""?(string)$st->repo_path:"/home/artica/artica-repository";
    $m["total_files"]=isset($st->total_files)?intval($st->total_files):0;
    $m["total_size"]=isset($st->total_size_bytes)?floatval($st->total_size_bytes):0;
    $m["generated"]=artrepo_ts(isset($st->generated_at)?$st->generated_at:"");

    $m["rebuilding"]=!empty($idx->rebuilding) || !empty($st->index_rebuilding);
    $m["stats"]=null;
    if(isset($idx->stats) && is_object($idx->stats)){
        $s=$idx->stats;
        $m["stats"]=array(
            "official"=>isset($s->official_count)?intval($s->official_count):0,
            "sp"=>isset($s->service_pack_count)?intval($s->service_pack_count):0,
            "hotfix"=>isset($s->hotfix_count)?intval($s->hotfix_count):0,
            "software"=>isset($s->software_count)?intval($s->software_count):0,
        );
    }

    $m["move"]=!empty($mv->in_progress);
    $op=isset($mv->operation)&&is_object($mv->operation)?$mv->operation:new stdClass();
    $m["mv_status"]=isset($op->status)?(string)$op->status:"";
    $m["mv_progress"]=isset($op->progress)?floatval($op->progress):0;
    $m["mv_files"]=isset($op->copied_files)?intval($op->copied_files):0;
    $m["mv_files_total"]=isset($op->total_files)?intval($op->total_files):0;
    $m["mv_bytes"]=isset($op->copied_bytes)?floatval($op->copied_bytes):0;
    $m["mv_bytes_total"]=isset($op->total_bytes)?floatval($op->total_bytes):0;
    $m["mv_src"]=isset($op->source_path)?(string)$op->source_path:"";
    $m["mv_dst"]=isset($op->dest_path)?(string)$op->dest_path:"";
    $m["mv_file"]=isset($op->current_file)?(string)$op->current_file:"";
    $m["mv_error"]=isset($op->error)?trim((string)$op->error):"";

    // Ecrit par le demon a la fin de chaque synchronisation reussie (persiste aux redemarrages).
    $m["last_sync"]=intval($sock->GET_INFO("ArticaRepoLastSync"));
    $m["meta"]=intval($sock->GET_INFO("EnableArticaMeta"))==1;
    $deb=trim((string)$sock->GET_INFO("ArticaReposDebianVersions"));
    if(strlen($deb)<2){$deb="10,12,13";}
    $m["debian"]=array();
    foreach(explode(",",$deb) as $v){ $v=intval($v); if($v>0){$m["debian"][$v]=true;} }
    $m["latest_only"]=intval($sock->GET_INFO("ArticaReposSoftwareLatestOnly"))==1;
    return $m;
}

// Horodatage Go (RFC3339, "Z") -> epoch ; 0 pour une date absente ou le temps zero de Go.
function artrepo_ts($iso):int{
    $iso=trim((string)$iso);
    if($iso=="" || strpos($iso,"0001-01-01")===0){return 0;}
    try{ $d=new DateTime($iso); return $d->getTimestamp(); }catch(Exception $e){ return 0; }
}

function artrepo_bytes($b):string{
    $b=floatval($b);
    if($b<=0){return "0 B";}
    return FormatBytes($b/1024,true);
}

// "3 hours ago" : le token est traduit d'abord, puis le nombre est substitue (ordre des mots par langue).
function artrepo_ago($ts):string{
    $tpl=new template_admin();
    $d=time()-intval($ts);
    if($d<60){return $tpl->_ENGINE_parse_body("{artrepo_ago_now}");}
    if($d<3600){$n=intval($d/60);$k="artrepo_ago_min";}
    elseif($d<172800){$n=intval($d/3600);$k="artrepo_ago_hours";}
    else{$n=intval($d/86400);$k="artrepo_ago_days";}
    return str_replace("%s",$n,$tpl->_ENGINE_parse_body("{".$k."}"));
}

function artrepo_date($ts):string{
    $tpl=new template_admin();
    return htmlspecialchars($tpl->time_to_date(intval($ts),true));
}

// Etat du bandeau, par priorite : un seul affiche.
function artrepo_state(array $m):string{
    if($m["move"]){return "move";}
    if($m["running"]){return "running";}
    if($m["rebuilding"]){return "rebuilding";}
    if($m["last_error"]!=""){return "failed";}
    if($m["total_files"]==0){return "empty";}
    if($m["part_total"]>0 && $m["part_avail"]<5*1073741824){return "lowspace";}
    // Deux cycles automatiques (2 x 5 h) manques : le miroir n'est plus a jour.
    if($m["last_sync"]>0 && (time()-$m["last_sync"])>36000){return "stale";}
    return "ok";
}

function artrepo_banner(array $m):string{
    $state=artrepo_state($m);
    if($state=="running"){return artrepo_banner_running($m);}
    if($state=="move"){return artrepo_banner_move($m);}
    $page=CurrentPageName();
    $launch="<button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('$page?start-sync=yes');\"><i class='fas fa-arrows-rotate'></i> {launch_updates}</button>";
    $ico="fas fa-circle-check";$title="{artrepo_banner_ok_title}";$sub="";$right="";
    if($state=="ok" || $state=="stale" || $state=="lowspace"){
        $when="{artrepo_unknown}";
        if($m["last_sync"]>0){
            $when="<strong>".artrepo_ago($m["last_sync"])."</strong> <span class='artrepo-sep'>&bull;</span> <span class='ak-tag'>".artrepo_date($m["last_sync"])."</span>";
        }
        $sub="{artrepo_last_sync}: $when";
        $sched=$m["meta"]?"<i class='fas fa-arrows-rotate'></i> {artrepo_every_5h}":"<i class='fas fa-circle-pause'></i> {artrepo_auto_off}";
        $right="<div class='ak-strip-side'><span>{artrepo_auto_sync}</span><span class='artrepo-fact-v'>$sched</span></div>";
    }
    if($state=="stale"){
        $ico="fas fa-clock-rotate-left";$title="{artrepo_stale_title}";
        $sub.="<br>{artrepo_stale_sub}";
        $right.=$launch;
    }
    if($state=="lowspace"){
        $ico="fas fa-triangle-exclamation";$title="{artrepo_lowspace_title}";
        $sub.="<br>{artrepo_lowspace_sub}";
    }
    if($state=="rebuilding"){
        $ico="fas fa-gears";$title="{artrepo_rebuild_title}";$sub="{artrepo_rebuild_sub}";
    }
    if($state=="failed"){
        $ico="fas fa-triangle-exclamation";$title="{artrepo_failed_title}";
        $sub="<span class='artrepo-err'>".htmlspecialchars($m["last_error"])."</span>";
        $right=$launch;
    }
    if($state=="empty"){
        $ico="fas fa-cloud-arrow-down";$title="{artrepo_empty_title}";$sub="{artrepo_empty_sub}";
        $right=$launch;
    }
    $tones=array("ok"=>"ak-ok","failed"=>"ak-err","rebuilding"=>"ak-warn","stale"=>"ak-warn","lowspace"=>"ak-warn","empty"=>"ak-off");
    $tone=isset($tones[$state])?$tones[$state]:"ak-off";
    return "<section class='artrepo-banner $tone' role='status'>
<div class='artrepo-banner-l'><span class='ak-ico artrepo-banner-ico'><i class='$ico'></i></span>
<div><div class='artrepo-banner-t'>$title</div><div class='artrepo-banner-s'>$sub</div></div></div>
<div class='artrepo-banner-r'>$right</div></section>";
}

// Synchronisation en cours : les six etapes reelles du demon (20/40/60/80/90/100 %).
function artrepo_banner_running(array $m):string{
    $p=max(0,min(100,$m["progress"]));
    $steps=array(20=>"{artrepo_step_index}",40=>"{artrepo_step_sp}",60=>"{artrepo_step_soft}",
        80=>"{artrepo_step_dns}",90=>"{artrepo_step_repoindex}",100=>"{artrepo_step_done}");
    $li=array();
    foreach($steps as $at=>$label){
        // Le demon annonce l'etape a son DEBUT : l'etape courante est la derniere atteinte.
        $cls="artrepo-step";$ic="<i class='far fa-circle'></i>";
        if($p>$at || ($p==100 && $at==100)){$cls.=" artrepo-step-done";$ic="<i class='fas fa-check'></i>";}
        elseif($p==$at){$cls.=" artrepo-step-cur";$ic="<i class='fas fa-circle-dot'></i>";}
        $li[]="<li class='$cls'>$ic $label</li>";
    }
    $started=$m["started"]>0?date("H:i",$m["started"]):"-";
    $step=htmlspecialchars($m["step"]);
    $tpl=new template_admin();
    return "<section class='artrepo-banner artrepo-banner-p ak-info' role='status'>
<div class='artrepo-banner-row'>
<div class='artrepo-banner-l'><span class='ak-ico artrepo-banner-ico'><i class='fas fa-arrows-rotate ak-spin'></i></span>
<div><div class='artrepo-banner-t'>{artrepo_running_title}</div><div class='artrepo-banner-s'>$step</div></div></div>
<div class='artrepo-banner-r artrepo-facts'>
<span>{artrepo_started}: <strong>$started</strong></span>
<span>{artrepo_files_downloaded}: <strong>".$tpl->FormatNumber($m["files_dl"],0,",")."</strong></span>
<span>{artrepo_downloaded}: <strong>".artrepo_bytes($m["bytes_dl"])."</strong></span></div></div>
<div class='ak-bar artrepo-bar' role='progressbar' aria-valuemin='0' aria-valuemax='100' aria-valuenow='$p'><span style='width:$p%'></span></div>
<ol class='artrepo-steps'>".@implode("",$li)."</ol></section>";
}

// Deplacement du depot vers un autre repertoire (validating -> copying -> verifying -> cleaning).
function artrepo_banner_move(array $m):string{
    $tpl=new template_admin();
    $p=intval(round(max(0,min(100,$m["mv_progress"]))));
    $order=array("validating","copying","verifying","cleaning");
    $pos=array_search($m["mv_status"],$order);
    $li=array();
    foreach($order as $i=>$k){
        $cls="artrepo-step";$ic="<i class='far fa-circle'></i>";
        if($pos!==false && $i<$pos){$cls.=" artrepo-step-done";$ic="<i class='fas fa-check'></i>";}
        if($pos!==false && $i==$pos){$cls.=" artrepo-step-cur";$ic="<i class='fas fa-circle-dot'></i>";}
        $li[]="<li class='$cls'>$ic {artrepo_mv_$k}</li>";
    }
    $files=$tpl->FormatNumber($m["mv_files"],0,",")." / ".$tpl->FormatNumber($m["mv_files_total"],0,",")." {files}";
    $bytes=artrepo_bytes($m["mv_bytes"])." / ".artrepo_bytes($m["mv_bytes_total"]);
    $cur="";
    if($m["mv_file"]!=""){$cur="<div class='ak-mono artrepo-ellipsis' title='".htmlspecialchars($m["mv_file"],ENT_QUOTES)."'>".htmlspecialchars($m["mv_file"])."</div>";}
    return "<section class='artrepo-banner artrepo-banner-p ak-info' role='status'>
<div class='artrepo-banner-row'>
<div class='artrepo-banner-l'><span class='ak-ico artrepo-banner-ico'><i class='fas fa-truck-moving'></i></span>
<div><div class='artrepo-banner-t'>{artrepo_move_title}</div>
<div class='artrepo-banner-s'>{artrepo_move_from} <span class='ak-tag'>".htmlspecialchars($m["mv_src"])."</span> {artrepo_move_to} <span class='ak-tag'>".htmlspecialchars($m["mv_dst"])."</span></div></div></div>
<div class='artrepo-banner-r artrepo-facts'><span><strong>$p%</strong></span><span>$files</span><span>$bytes</span></div></div>
<div class='ak-bar artrepo-bar' role='progressbar' aria-valuemin='0' aria-valuemax='100' aria-valuenow='$p'><span style='width:$p%'></span></div>
<ol class='artrepo-steps'>".@implode("",$li)."</ol>$cur</section>";
}

function artrepo_kpi_files(array $m):string{
    $tpl=new template_admin();
    $v=$m["total_files"]>0?$tpl->FormatNumber($m["total_files"],0,","):"0";
    return "<div class='ak-fig artrepo-kpi'><div class='artrepo-kpi-h'><span class='ak-fig-l'>{total_files}</span><span class='ak-ico ak-ico-sm'><i class='fas fa-file'></i></span></div>
<div class='ak-fig-v artrepo-kpi-v'>$v</div></div>";
}

function artrepo_kpi_size(array $m):string{
    // FormatBytes() colle l'unite au nombre ("38.6GB") : on la separe pour la mettre en petit.
    $v=artrepo_bytes($m["total_size"]);
    $num=$v;$unit="";
    if(preg_match('/^([0-9][0-9.,]*)\s*([A-Za-z]+)$/',$v,$re)){$num=$re[1];$unit="<span class='artrepo-kpi-u'>".$re[2]."</span>";}
    return "<div class='ak-fig artrepo-kpi'><div class='artrepo-kpi-h'><span class='ak-fig-l'>{total_size}</span><span class='ak-ico ak-ico-sm'><i class='fas fa-weight-hanging'></i></span></div>
<div class='ak-fig-v artrepo-kpi-v'>$num $unit</div></div>";
}

function artrepo_kpi_index(array $m):string{
    $v="<div class='ak-fig-v artrepo-kpi-v artrepo-kpi-v2'>-</div>";
    if($m["rebuilding"]){
        $v="<div class='ak-fig-v artrepo-kpi-v artrepo-kpi-v2'><i class='fas fa-gears'></i> {artrepo_rebuilding}</div>";
    }elseif($m["generated"]>0){
        $v="<div class='ak-fig-v artrepo-kpi-v artrepo-kpi-v2'>".artrepo_ago($m["generated"])."</div><div class='ak-fig-s'>".artrepo_date($m["generated"])."</div>";
    }
    return "<div class='ak-fig artrepo-kpi'><div class='artrepo-kpi-h'><span class='ak-fig-l'>{artrepo_index_generated}</span><span class='ak-ico ak-ico-sm'><i class='fas fa-list-check'></i></span></div>$v</div>";
}

// Repartition du contenu de l'index : barre empilee proportionnelle + legende chiffree.
function artrepo_kpi_breakdown(array $m):string{
    $tpl=new template_admin();
    $head="<div class='artrepo-kpi-h'><span class='ak-fig-l'>{artrepo_breakdown}</span><span class='ak-ico ak-ico-sm'><i class='fas fa-layer-group'></i></span></div>";
    if(!is_array($m["stats"])){
        return "<div class='ak-fig artrepo-kpi'>$head<div class='ak-fig-s'>{artrepo_no_stats}</div></div>";
    }
    $s=$m["stats"];
    $parts=array(
        array("official","{artrepo_official}",$s["official"]),
        array("sp","{artrepo_step_sp}",$s["sp"]),
        array("hotfix","{artrepo_hotfixes}",$s["hotfix"]),
        array("software","{artrepo_step_soft}",$s["software"]),
    );
    $sum=$s["official"]+$s["sp"]+$s["hotfix"]+$s["software"];
    $bar=array();$leg=array();
    foreach($parts as $p){
        $n=intval($p[2]);
        if($sum>0 && $n>0){
            // 2 % minimum : un segment de 6 fichiers sur 1 300 reste visible.
            $w=max(2,round($n*100/$sum,1));
            $bar[]="<span class='artrepo-seg artrepo-seg-{$p[0]}' style='flex-grow:$w' title='".$tpl->FormatNumber($n,0,",")."'></span>";
        }
        $leg[]="<span class='artrepo-leg'><span class='ak-dot artrepo-seg-{$p[0]}'></span>{$p[1]} <strong>".$tpl->FormatNumber($n,0,",")."</strong></span>";
    }
    if(count($bar)==0){$bar[]="<span class='artrepo-seg artrepo-seg-none' style='flex-grow:1'></span>";}
    return "<div class='ak-fig artrepo-kpi'>$head<div class='artrepo-stack'>".@implode("",$bar)."</div><div class='artrepo-legend'>".@implode("",$leg)."</div></div>";
}

function artrepo_card_storage(array $m):string{
    $page=CurrentPageName();
    $path=htmlspecialchars($m["repo_path"]);
    $change="<button type='button' class='ak-link' OnClick=\"Loadjs('$page?RepoPath-js=yes');\" aria-label='{artrepo_change}'><i class='fas fa-pen'></i> {artrepo_change}</button>";
    if($m["move"]){$change="<span class='ak-muted ak-nowrap'><i class='fas fa-truck-moving'></i> {artrepo_move_title}</span>";}
    $h=array();
    $h[]="<article class='ak-card artrepo-card'><div class='artrepo-card-h'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='fas fa-hard-drive'></i></span><h3>{artrepo_storage}</h3></div>";
    $h[]="<div class='artrepo-path'><div><div class='artrepo-lbl'>{main_storage_directory}</div><div class='ak-mono artrepo-mono'>$path</div></div>$change</div>";
    if(!$m["move"] && $m["mv_status"]=="failed" && $m["mv_error"]!=""){
        $h[]="<div class='ak-banner ak-err'><i class='fas fa-triangle-exclamation'></i> {artrepo_move_failed}: ".htmlspecialchars($m["mv_error"])."</div>";
    }
    if($m["part_total"]<=0){
        $h[]="<div class='ak-banner ak-off'><i class='fas fa-circle-info'></i> {artrepo_no_partition}</div>";
    }else{
        $repo=min($m["repo_bytes"],$m["part_used"]);
        $other=max(0,$m["part_used"]-$repo);
        $pct=intval(round($m["part_pct"]));
        $repoPct=round($m["repo_pct"],1);
        // peity donut : depot / autres fichiers / libre, mesures contre la taille reelle de la partition.
        $data=sprintf("%.0f,%.0f,%.0f",$repo/1048576,$other/1048576,max(0,$m["part_avail"])/1048576);
        $h[]="<div class='artrepo-disk'>";
        $h[]="<div class='artrepo-donut'><span class='artrepo-pie' data-peity='$data'>$data</span><div class='artrepo-donut-c'><strong>$pct%</strong><span>{artrepo_used_lbl}</span></div></div>";
        $h[]="<dl class='artrepo-dl'>";
        $h[]="<dt>{artrepo_partition_size}</dt><dd>".artrepo_bytes($m["part_total"])."</dd>";
        $h[]="<dt><span class='ak-dot artrepo-seg-other'></span>{artrepo_used_other}</dt><dd>".artrepo_bytes($other)."</dd>";
        $h[]="<dt><span class='ak-dot artrepo-seg-repo'></span>{artrepo_used_repo}</dt><dd class='artrepo-accent'>".artrepo_bytes($repo)." ($repoPct%)</dd>";
        $h[]="<dt><span class='ak-dot artrepo-seg-free'></span>{artrepo_available}</dt><dd><span class='ak-tag ak-ok'>".artrepo_bytes($m["part_avail"])."</span></dd>";
        $h[]="</dl></div>";
        $cls="ak-banner ak-off";$ic="fas fa-circle-info";
        if($m["part_avail"]<5*1073741824){$cls="ak-banner ak-warn";$ic="fas fa-triangle-exclamation";}
        if($m["part_avail"]<1073741824){$cls="ak-banner ak-err";$ic="fas fa-triangle-exclamation";}
        $h[]="<div class='$cls'><i class='$ic'></i> {artrepo_min_free}</div>";
    }
    $h[]="</article>";
    return @implode("\n",$h);
}

function artrepo_card_mirror(array $m):string{
    $page=CurrentPageName();
    $names=array(10=>"Buster",12=>"Bookworm",13=>"Trixie");
    $chips=array();
    foreach($names as $v=>$code){
        if(isset($m["debian"][$v])){
            $chips[]="<span class='ak-pill ak-ok'><i class='fas fa-check'></i> Debian $v <span class='artrepo-chip-sub'>($code)</span></span>";
        }else{
            $chips[]="<span class='ak-pill ak-off artrepo-chip-off' title='{artrepo_not_mirrored}'><i class='fas fa-ban'></i> Debian $v <span class='artrepo-chip-sub'>($code)</span><span class='sr-only'> {artrepo_not_mirrored}</span></span>";
        }
    }
    $latest=$m["latest_only"];
    $segL="ak-seg-b".($latest?" on":"");
    $segA="ak-seg-b".(!$latest?" on":"");
    $note=$latest?"{artrepo_latest_note}":"{artrepo_all_note}";
    $h=array();
    $h[]="<article class='ak-card artrepo-card'><div class='artrepo-card-h'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='fas fa-sliders'></i></span><h3>{artrepo_mirrored}</h3></div>";
    $h[]="<div class='artrepo-sec'><div class='artrepo-sec-h'><div><div class='artrepo-lbl'>{artrepo_debian_versions}</div><div class='ak-help'>{artrepo_debian_explain}</div></div>";
    $h[]="<button type='button' class='ak-link' OnClick=\"Loadjs('$page?debians-js=yes');\" aria-label='{artrepo_edit} {artrepo_debian_versions}'><i class='fas fa-pen'></i> {artrepo_edit}</button></div>";
    $h[]="<div class='ak-chips'>".@implode("",$chips)."</div></div>";
    $h[]="<div class='artrepo-sec'><div class='artrepo-sec-h'><div><div class='artrepo-lbl'>{artrepo_behavior}</div></div></div>";
    $h[]="<div class='ak-seg artrepo-seg-ctl' role='radiogroup' aria-label='{artrepo_behavior}'>";
    $h[]="<button type='button' role='radio' aria-checked='".($latest?"true":"false")."' class='$segL' OnClick=\"ArtRepoLatest(1);\"><i class='fas fa-bolt'></i> {download_only_latest_versions}</button>";
    $h[]="<button type='button' role='radio' aria-checked='".(!$latest?"true":"false")."' class='$segA' OnClick=\"ArtRepoLatest(0);\"><i class='fas fa-boxes-stacked'></i> {download_all_versions}</button>";
    $h[]="</div><div class='ak-banner ak-off'><i class='fas fa-circle-info'></i> <span>$note</span></div></div>";
    $h[]="</article>";
    return @implode("\n",$h);
}

// Concatene HORS de _ENGINE_parse_body : les blocs {..} du JS seraient lus comme des tokens.
function artrepo_js(array $m):string{
    $page=CurrentPageName();
    $cur=$m["latest_only"]?1:0;
    return "<script>
function ArtRepoRefresh(){ if(document.getElementById('artrepo-host')){LoadAjaxSilent('artrepo-host','$page?repository-status=yes');} }
function ArtRepoLatest(v){
    if(v==$cur){return;}
    jQuery.post('$page',{ArticaReposSoftwareLatestOnly:v},function(resp){
        var t=String(resp||'').trim();
        if(t.length>3){ var x=t.indexOf('jserror:')===0?t.substring(8):t; swal({title:'Oops...',text:'<strong>'+x+'</strong>',html:true,type:'error'}); }
        ArtRepoRefresh();
    });
}
(function(){
    function run(){ jQuery('#artrepo-host .artrepo-pie').peity('donut',{radius:62,innerRadius:46,fill:['".artrepo_accent()."','#94a3b8','#e2e8f0']}); }
    if(jQuery.fn.peity){run();}else{ var sc=document.createElement('script'); sc.src='js/jquery.peity.min.js'; sc.onload=run; document.head.appendChild(sc); }
})();
</script>";
}

// TINY rejoue seulement quand ses boutons changent (le timer recharge l'hote toutes les 5 s).
function artrepo_tiny():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $topbuttons=array();
    if(artrepo_can_write()){   // gestion du depot : infogerant ; un tenant n'y fait qu'envoyer
        $topbuttons[]=array("Loadjs('$page?start-sync=yes')",ico_refresh,"{launch_updates}");
        $topbuttons[]=array("Loadjs('fw.artica.repository.hotfixes.php?js=yes')",ico_upload,"InstantHotfix");
        $topbuttons[]=array("Loadjs('fw.artica.repository.ServicePacks.php?js=yes')",ico_upload,"InstantServicePack");
    }
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{artica_repositories}";
    $TINY_ARRAY["ICO"]=ico_database;
    $TINY_ARRAY["EXPL"]="{artica_repositories_explain}";
    $TINY_ARRAY["URL"]="artica-repos";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $q=urlencode(base64_encode(serialize($TINY_ARRAY)));
    $sig=md5($q);
    return "<script>if(window.ArtRepoTinySig!=='$sig'){window.ArtRepoTinySig='$sig';Loadjs('fw.progress.php?tiny-page=$q');}</script>";
}

function artrepo_accent():string{
    $accent="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_dark"])){$accent=$a["accent_dark"];}
    }
    return $accent;
}

function artrepo_style():string{
    if(isset($GLOBALS["ARTREPO_CSS"])){return "";}
    $GLOBALS["ARTREPO_CSS"]=true;
    $accent=artrepo_accent();
    // Communs (carte, tuiles, pastilles, bandeaux, jauge, segmente) : kit ak- (angular.css.php).
    return "<style>
.artrepo{max-width:1320px}
.artrepo-banner{background:#fff;border:1px solid #e2e8f0;border-left:4px solid var(--ak-tsolid,#64748b);border-radius:12px;padding:20px 24px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.artrepo-banner-p{display:block}
.artrepo-banner-row{display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap}
.artrepo-banner-l{display:flex;align-items:center;gap:16px;min-width:0;flex:1 1 420px}
.artrepo-banner-ico{flex-basis:44px;width:44px;height:44px;font-size:20px}
.artrepo-banner-t{font-size:18px;font-weight:600;line-height:24px}
.artrepo-banner-s{font-size:13px;color:#475569;margin-top:4px;line-height:20px}
.artrepo-banner-r{display:flex;align-items:center;gap:20px;flex-wrap:wrap}
.artrepo-sep{color:#94a3b8;margin:0 4px}
.artrepo-err{display:inline-block;margin-top:4px;background:#ffdad6;color:#ba1a1a;border-radius:6px;padding:4px 10px;font-size:13px;word-break:break-word}
.artrepo-fact-v{font-size:14px;font-weight:600;color:#0f172a}
.artrepo-fact-v svg,.artrepo-fact-v i{color:$accent}
.artrepo-facts{font-size:13px;color:#475569}
.artrepo-bar{height:8px;margin:16px 0 10px}
.artrepo-steps{list-style:none;margin:0;padding:0;display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;font-size:13px;color:#64748b}
.artrepo-step-done{color:#15803d}.artrepo-step-cur{color:#1d4ed8;font-weight:700}
.artrepo-kpis{gap:24px;margin-bottom:24px}
.artrepo-kpi{display:block;min-height:112px}
.artrepo-kpi-h{display:flex;justify-content:space-between;align-items:center}
.artrepo-kpi-v{line-height:32px;margin-top:10px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.artrepo-kpi .ak-fig-s{margin-top:4px}
.artrepo-kpi-v2{font-size:22px}
.artrepo-kpi-u{font-size:15px;font-weight:600;color:#64748b}
.artrepo-stack{display:flex;height:10px;border-radius:6px;overflow:hidden;margin-top:14px;gap:2px;background:#f1f5f9}
.artrepo-seg{display:block;height:100%;flex-basis:0}
.artrepo-seg-official{background:#2563eb}.artrepo-seg-sp{background:#14b8a6}.artrepo-seg-hotfix{background:#f59e0b}.artrepo-seg-software{background:#334155}.artrepo-seg-none{background:#e2e8f0}
.artrepo-seg-repo{background:$accent}.artrepo-seg-other{background:#94a3b8}.artrepo-seg-free{background:#e2e8f0;border:1px solid #cbd5e1}
.artrepo-legend{display:grid;grid-template-columns:1fr 1fr;gap:4px 12px;margin-top:10px;font-size:13px;color:#475569}
.artrepo-leg{min-width:0;line-height:18px}
.artrepo-leg strong{color:#0f172a}
.artrepo .ak-dot{margin-right:6px;vertical-align:middle}
.artrepo-cols{gap:24px;align-items:start}
.artrepo-card{padding:20px 24px}
.artrepo-card-h{display:flex;align-items:center;gap:12px;padding-bottom:14px;border-bottom:1px solid #e2e8f0;margin-bottom:16px}
.artrepo-card-h h3{margin:0;font-size:14px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#0f172a}
.artrepo-path{display:flex;justify-content:space-between;align-items:center;gap:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 16px}
.artrepo-path>div{min-width:0}
.artrepo-lbl{font-size:13px;font-weight:600;color:#334155;letter-spacing:.02em;text-transform:uppercase}
.artrepo .ak-help{margin-top:2px}
.artrepo-mono{font-size:13px;margin-top:4px;word-break:break-all}
.artrepo-ellipsis{font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:8px;color:#475569}
.artrepo-disk{display:flex;align-items:center;gap:32px;padding:20px 8px}
.artrepo-donut{position:relative;width:124px;height:124px;flex:0 0 auto}
.artrepo-donut-c{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none}
.artrepo-donut-c strong{font-size:22px;color:#0f172a}
.artrepo-donut-c span{font-size:12px;color:#64748b;text-transform:uppercase}
.artrepo-dl{display:grid;grid-template-columns:auto auto;gap:10px 24px;margin:0;font-size:13px;flex:1 1 auto;align-items:center}
.artrepo-dl dt{font-weight:400;color:#475569}
.artrepo-dl dd{margin:0;text-align:right;font-weight:600;color:#0f172a;white-space:nowrap}
.artrepo-accent{color:#00504a!important}
.artrepo .ak-banner{align-items:flex-start;margin-top:12px;font-size:13px;line-height:19px}
.artrepo-sec{padding:4px 0 18px}
.artrepo-sec+.artrepo-sec{border-top:1px solid #e2e8f0;padding-top:18px}
.artrepo-sec-h{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:12px}
.artrepo-chip-off{font-weight:400}
.artrepo-chip-sub{font-weight:400;opacity:.85}
.artrepo-seg-ctl{display:flex}.artrepo-seg-ctl .ak-seg-b{flex:1 1 0;padding:10px 12px}
@media (max-width:1200px){.artrepo-cols{grid-template-columns:1fr}}
</style>";
}
// Fin du tableau de bord

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
// Logiciels d'une version Debian (Repository > Softwares > Debian N, maquette stitch_artica_meta_softs)
// Une ligne par logiciel (et non par fichier), versions repliees dessous ; filtre et pages cote navigateur.
function softwares_debian():bool{
    $tpl=new template_admin();
    $deb=intval($_GET["softwares-debian"]);
    $m=sft_model($deb);
    $html=array();
    $html[]="<div class='sft ak' id='sft-$deb'>";
    if($m["error"]!=""){
        $html[]="<div class='ak-banner ak-err'><i class='fas fa-triangle-exclamation'></i> ".htmlspecialchars($m["error"])."</div>";
    }elseif(count($m["apps"])==0 && count($m["missing"])==0){
        $html[]=sft_empty($deb);
    }else{
        $html[]=sft_summary($m);
        $html[]=sft_toolbar($m);
        $html[]=sft_table($m);
    }
    $html[]="</div>";
    echo hfx_style().sft_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).sft_js($m).sft_tiny($deb);
    return true;
}

function sft_model(int $deb):array{
    $m=array("deb"=>$deb,"apps"=>array(),"missing"=>array(),"error"=>"","files"=>0,"total"=>0,"newer"=>0,
        "catalog"=>null,"latest_only"=>intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaReposSoftwareLatestOnly"))==1);
    if($deb<1){$m["error"]="Invalid Debian version";return $m;}
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/files/software/$deb"));
    if(!is_object($json)){$m["error"]="Artica REST API: no answer";return $m;}
    if(isset($json->Status) && !$json->Status && isset($json->Error)){$m["error"]=(string)$json->Error;return $m;}
    $m["catalog"]=sft_catalog($deb);
    if(isset($json->files) && is_array($json->files)){
        foreach($json->files as $f){
            $code=isset($f->name)?(string)$f->name:"";
            $fn=isset($f->filename)?(string)$f->filename:"";
            if($code=="" || $fn==""){continue;}
            $ver=isset($f->version)&&$f->version!=""?(string)$f->version:preg_replace('#\.(tar\.gz|tgz)$#','',$fn);
            $lp=isset($f->local_path)?(string)$f->local_path:"";
            $pos=strpos($lp,"/softwares/debian");
            $row=array("ver"=>$ver,"bin"=>isset($f->version_bin)?intval($f->version_bin):0,"filename"=>$fn,
                "rel"=>$pos===false?"":substr($lp,$pos),
                "size"=>isset($f->size)?floatval($f->size):0,"md5"=>isset($f->md5)?strtolower((string)$f->md5):"",
                "mtime"=>artrepo_ts(isset($f->synced_at)?$f->synced_at:""));
            if(!isset($m["apps"][$code])){$m["apps"][$code]=array("code"=>$code,"label"=>sft_label($code),"files"=>array(),"size"=>0,"mtime"=>0,"published"=>"","newer"=>false);}
            $m["apps"][$code]["files"][]=$row;
            $m["apps"][$code]["size"]+=$row["size"];
            if($row["mtime"]>$m["apps"][$code]["mtime"]){$m["apps"][$code]["mtime"]=$row["mtime"];}
            $m["files"]++;
            $m["total"]+=$row["size"];
        }
    }
    foreach($m["apps"] as $code=>$a){
        usort($a["files"],"sft_cmp_files");
        $newest=$a["files"][0];
        if(is_array($m["catalog"]) && isset($m["catalog"][$code])){
            $pub=$m["catalog"][$code];
            $a["published"]=$pub["ver"];
            if(sft_cmp_ver($pub["bin"],$pub["ver"],$newest["bin"],$newest["ver"])>0){$a["newer"]=true;$m["newer"]++;}
        }
        $m["apps"][$code]=$a;
    }
    if(is_array($m["catalog"])){
        foreach($m["catalog"] as $code=>$pub){
            if(isset($m["apps"][$code])){continue;}
            $m["missing"][$code]=array("code"=>$code,"label"=>sft_label($code),"published"=>$pub["ver"]);
        }
    }
    uasort($m["apps"],"sft_cmp_label");
    uasort($m["missing"],"sft_cmp_label");
    return $m;
}

function sft_cmp_label($a,$b){ return strcasecmp($a["label"],$b["label"]); }
// Comparaison de versions. version_bin concatene les chiffres (26.8.1.2041 -> 26812041,
// 26.8.34.13 -> 2683413) : il ne sert qu'en dernier recours, quand une version texte manque.
function sft_cmp_ver($binA,$verA,$binB,$verB):int{
    if($verA!=="" && $verB!=="" && $verA!==$verB){return version_compare($verA,$verB);}
    if($binA!=$binB){return $binA>$binB?1:-1;}
    return 0;
}
function sft_cmp_files($a,$b){ return sft_cmp_ver($b["bin"],$b["ver"],$a["bin"],$a["ver"]); }

// Nom affiche : le code APP_xxx est une cle de traduction ; a defaut, le code lui-meme.
function sft_label($code):string{
    $t=trim(hfx_t($code));
    if($t=="" || $t[0]=="{"){return $code;}
    return html_entity_decode(strip_tags($t),ENT_QUOTES,"UTF-8");
}

// Catalogue publie par Artica pour cette Debian, enregistre par la synchronisation.
// Contenu venu d'Internet : unserialize sans aucune classe, taille plafonnee.
function sft_catalog(int $deb){
    $st=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/status"));
    $repo=is_object($st)&&isset($st->repo_path)?(string)$st->repo_path:"";
    if($repo=="" || $repo[0]!="/" || strpos($repo,"..")!==false){return null;}
    $path=rtrim($repo,"/")."/softwares/debian$deb/index.dat";
    if(!@is_file($path) || !@is_readable($path)){return null;}
    $raw=@file_get_contents($path,false,null,0,8388608);
    if($raw===false || strlen($raw)<10){return null;}
    $data=@unserialize($raw,array("allowed_classes"=>false));
    if(!is_array($data)){return null;}
    $cat=array();
    foreach($data as $code=>$versions){
        if(!is_array($versions)){continue;}
        $best=null;
        foreach($versions as $bin=>$d){
            if(!is_array($d) || empty($d["URI"])){continue;}
            $v=array("bin"=>intval($bin),"ver"=>isset($d["VERSION"])?(string)$d["VERSION"]:(string)$bin);
            if($best===null || sft_cmp_ver($v["bin"],$v["ver"],$best["bin"],$best["ver"])>0){$best=$v;}
        }
        if($best!==null){$cat[(string)$code]=$best;}
    }
    return $cat;
}

function sft_summary(array $m):string{
    $tpl=new template_admin();
    $ret=$m["latest_only"]?"{download_only_latest_versions}":"{download_all_versions}";
    $cat="<div class='sft-cat'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='fas fa-cloud'></i></span><div><div class='hfx-fact-k'>{sft_catalog_status}</div><div class='sft-cat-v'>{spk_cat_unknown}</div></div></div>";
    if(is_array($m["catalog"])){
        $miss=count($m["missing"]);
        $missTxt=$miss>0?" &middot; <span class='sft-warn'>".str_replace("%s",$miss,hfx_t("sft_not_mirrored_n"))."</span>":"";
        $cat="<div class='sft-cat'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='fas fa-cloud'></i></span><div><div class='hfx-fact-k'>{sft_catalog_status}</div><div class='sft-cat-v'>{sft_published_by} <strong>".count($m["catalog"])."</strong>$missTxt</div></div></div>";
    }
    $size=hfx_size($m["total"]);
    $num=$size;$unit="";
    if(preg_match('/^(\S+) (\S+)$/',$size,$re)){$num=$re[1];$unit="<span class='hfx-unit'>".$re[2]."</span>";}
    return "<section class='hfx-sum sft-sum'>
<div class='hfx-fact'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='fas fa-cubes'></i></span><div><div class='hfx-fact-k'>{sft_software}</div><div class='hfx-fact-v'>".$tpl->FormatNumber(count($m["apps"]),0,",")."</div></div></div>
<div class='hfx-fact'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='fas fa-file-zipper'></i></span><div><div class='hfx-fact-k'>{sft_files}</div><div class='hfx-fact-v'>".$tpl->FormatNumber($m["files"],0,",")."</div></div></div>
<div class='hfx-fact'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='fas fa-weight-hanging'></i></span><div><div class='hfx-fact-k'>{total_size}</div><div class='hfx-fact-v'>$num $unit</div></div></div>
<div class='hfx-fact'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='fas fa-clock-rotate-left'></i></span><div><div class='hfx-fact-k'>{sft_retention}</div><div class='hfx-fact-v hfx-fact-v2'>$ret</div></div></div>
$cat</section>";
}

function sft_toolbar(array $m):string{
    $deb=$m["deb"];
    $h="<section class='sft-tools'><label class='ak-search hfx-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i><input type='search' id='sft-q-$deb' placeholder=\"{sft_search_ph}\" aria-label=\"{sft_search_ph}\" oninput=\"SftApply($deb,1)\"></label>";
    $h.="<div class='ak-seg sft-seg' role='radiogroup' aria-label=\"{sft_filter}\">";
    $h.="<button type='button' role='radio' aria-checked='true' class='ak-seg-b sft-segbtn on' data-f='mirrored' onclick=\"SftFilter($deb,'mirrored')\">{sft_f_mirrored} (".count($m["apps"]).")</button>";
    if(is_array($m["catalog"])){
        $h.="<button type='button' role='radio' aria-checked='false' class='ak-seg-b sft-segbtn' data-f='newer' onclick=\"SftFilter($deb,'newer')\">{sft_f_newer} ({$m["newer"]})</button>";
        $h.="<button type='button' role='radio' aria-checked='false' class='ak-seg-b sft-segbtn' data-f='missing' onclick=\"SftFilter($deb,'missing')\">{sft_f_missing} (".count($m["missing"]).")</button>";
    }
    $h.="</div></section>";
    return $h;
}

function sft_table(array $m):string{
    $deb=$m["deb"];
    $h=array();
    $h[]="<section class='ak-card'><div class='hfx-scroll'><table class='ak-tbl hfx-tbl sft-tbl'>";
    $h[]="<thead><tr><th scope='col'>{sft_software}</th><th scope='col'>{sft_newest_version}</th><th scope='col'>{sft_versions}</th><th scope='col' class='ak-end'>{hfx_size}</th><th scope='col'>{updated}</th><th scope='col' class='ak-end'>{hfx_actions}</th></tr></thead>";
    $h[]="<tbody id='sft-rows-$deb'>";
    foreach($m["apps"] as $a){ $h[]=sft_row($a,$deb); $h[]=sft_versions_row($a,$deb); }
    foreach($m["missing"] as $a){ $h[]=sft_missing_row($a); }
    $h[]="<tr class='sft-nomatch' style='display:none'><td colspan='6'><div class='hfx-empty-s'><i class='fas fa-filter-circle-xmark'></i> {sft_nomatch} <button type='button' class='ak-link' onclick=\"SftClear($deb)\">{hfx_clear}</button></div></td></tr>";
    $h[]="</tbody></table></div>";
    $h[]="<div class='ak-card-f'><span class='sft-count'></span><span class='ak-pg sft-pager'></span></div></section>";
    return @implode("\n",$h);
}

function sft_deploy_js(array $f,string $code):string{
    if($f["rel"]==""){return "";}
    return "Loadjs('fw.artica.repository.softwares.deploy.php?js=".urlencode($code)."&filename=".urlencode($f["rel"])."');";
}

function sft_q(array $a):string{
    $q=$a["label"]." ".$a["code"];
    if(isset($a["files"])){foreach($a["files"] as $f){$q.=" ".$f["ver"];}}
    if(!empty($a["published"])){$q.=" ".$a["published"];}
    return htmlspecialchars(strtolower($q),ENT_QUOTES);
}

function sft_badge(array $a,string $ver):string{
    if($a["newer"]){return "<span class='ak-tag ak-warn'><i class='fas fa-arrow-up'></i> ".htmlspecialchars($a["published"])." {sft_published}</span>";}
    if($a["published"]!=""){return "<span class='ak-tag ak-ok hfx-latest'><i class='fas fa-star'></i> {hfx_latest}</span>";}
    return "";
}

function sft_row(array $a,int $deb):string{
    $n=$a["files"][0];
    $rid="sft-v-$deb-".md5($a["code"]);
    $count=count($a["files"])==1?hfx_t("sft_one_version"):str_replace("%s",count($a["files"]),hfx_t("sft_n_versions"));
    $upd="-";
    if($a["mtime"]>0){$upd="<div class='hfx-upd'>".artrepo_ago($a["mtime"])."</div><div class='ak-code'>".date("Y-m-d",$a["mtime"])."</div>";}
    $deploy=sft_deploy_js($n,$a["code"]);
    $btn=$deploy==""?"":"<button type='button' class='ak-btn ak-btn-primary' onclick=\"$deploy\"><i class='fas fa-paper-plane'></i> {sft_deploy}</button>";
    $f=($a["newer"]?" sft-is-newer":"");
    return "<tr class='sft-app$f' data-kind='app' data-q='".sft_q($a)."' data-rid='$rid'>
<td><div class='hfx-ident'><span class='ak-ico ak-ico-sq'><i class='fas fa-cube'></i></span><div><div class='hfx-name'>".htmlspecialchars($a["label"])."</div><div class='ak-code'>".htmlspecialchars($a["code"])."</div></div></div></td>
<td><span class='sft-ver'>".htmlspecialchars($n["ver"])."</span> ".sft_badge($a,$n["ver"])."</td>
<td><span class='ak-pill ak-off'>$count</span></td>
<td class='ak-num ak-nowrap'>".hfx_size($a["size"])."</td>
<td>$upd</td>
<td class='ak-end'><div class='ak-acts hfx-acts'>$btn<button type='button' class='sft-tog' aria-expanded='false' aria-controls='$rid' aria-label=\"{sft_show_versions}\" title=\"{sft_show_versions}\" onclick=\"SftToggle(this,'$rid')\"><i class='fas fa-chevron-down'></i></button></div></td></tr>";
}

function sft_versions_row(array $a,int $deb):string{
    $rid="sft-v-$deb-".md5($a["code"]);
    $rows=array();
    foreach($a["files"] as $i=>$f){
        $latest=($i==0 && !$a["newer"] && $a["published"]!="")?" <span class='ak-tag ak-ok hfx-latest'>{hfx_latest}</span>":"";
        $md5=$f["md5"];
        $md5h=strlen($md5)==32?"<span class='hfx-md5'><span class='ak-mono' title='$md5'>".substr($md5,0,8)."&hellip;".substr($md5,-4)."</span><button type='button' class='ak-act' aria-label=\"{hfx_copy_md5}\" title=\"{hfx_copy_md5}\" onclick=\"SftCopy(this,'$md5')\"><i class='far fa-copy'></i></button></span>":"-";
        $deploy=sft_deploy_js($f,$a["code"]);
        $btn=$deploy==""?"":"<button type='button' class='ak-btn' onclick=\"$deploy\">{sft_deploy}</button>";
        $rows[]="<tr><td><span class='ak-mono sft-subver'>".htmlspecialchars($f["ver"])."</span>$latest</td><td class='ak-nowrap ak-mono'>".hfx_size($f["size"])."</td><td>$md5h</td><td class='ak-mono'>".($f["mtime"]>0?date("Y-m-d",$f["mtime"]):"-")."</td><td class='ak-end'>$btn</td></tr>";
    }
    return "<tr class='sft-sub' id='$rid' data-kind='sub' style='display:none'><td colspan='6'><div class='sft-subwrap'><table class='ak-tbl sft-subtbl'>
<thead><tr><th>{sft_version}</th><th>{hfx_size}</th><th>MD5</th><th>{updated}</th><th class='ak-end'>{hfx_actions}</th></tr></thead><tbody>".@implode("",$rows)."</tbody></table></div></td></tr>";
}

function sft_missing_row(array $a):string{
    return "<tr class='sft-app sft-missing' data-kind='missing' data-q='".sft_q($a)."' style='display:none'>
<td><div class='hfx-ident'><span class='ak-ico ak-ico-sq ak-off'><i class='fas fa-cube'></i></span><div><div class='hfx-name ak-muted'>".htmlspecialchars($a["label"])."</div><div class='ak-code'>".htmlspecialchars($a["code"])."</div></div></div></td>
<td><span class='ak-mono ak-muted'>&mdash; ".htmlspecialchars($a["published"])." {sft_published}</span></td>
<td>&mdash;</td><td class='ak-end'>&mdash;</td><td>&mdash;</td>
<td class='ak-end'><span class='ak-muted'><i class='fas fa-circle-minus'></i> {sft_not_in_mirror}</span></td></tr>";
}

function sft_empty(int $deb):string{
    return "<section class='ak-card hfx-empty'><span class='ak-ico ak-ico-lg'><i class='fas fa-cubes'></i></span>
<div class='hfx-empty-t'>".str_replace("%s",$deb,hfx_t("sft_empty_title"))."</div><div class='hfx-empty-s'>{sft_empty_sub}</div></section>";
}

// Concatene HORS de _ENGINE_parse_body. Etat par onglet Debian (plusieurs onglets coexistent).
function sft_js(array $m):string{
    $L=array();
    foreach(array("showing"=>"sft_showing","prev"=>"hfx_prev","next"=>"hfx_next","copied"=>"hfx_copied") as $k=>$t){
        $L[$k]=trim(str_replace(array("[br]","<br>"),"",hfx_t($t)));
    }
    $deb=intval($m["deb"]);
    return "<script>
window.SftL=".json_encode($L).";
window.SftState=window.SftState||{};
window.SftState[$deb]={f:'mirrored',page:1,size:25};
if(typeof window.SftApply!=='function'){
window.SftApply=function(deb,resetPage){
    var st=window.SftState[deb], tb=document.getElementById('sft-rows-'+deb); if(!st||!tb){return;}
    if(resetPage){st.page=1;}
    var qi=document.getElementById('sft-q-'+deb), q=qi?qi.value.toLowerCase().trim():'';
    var rows=tb.rows, match=[], i, r, kind;
    for(i=0;i<rows.length;i++){
        r=rows[i]; kind=r.getAttribute('data-kind'); if(kind!=='app'&&kind!=='missing'){continue;}
        r.style.display='none';
        var ok=(st.f==='missing')?(kind==='missing'):(kind==='app'&&(st.f==='mirrored'||r.className.indexOf('sft-is-newer')>=0));
        if(ok&&q!==''&&r.getAttribute('data-q').indexOf(q)<0){ok=false;}
        if(ok){match.push(r);}
    }
    for(i=0;i<rows.length;i++){ if(rows[i].getAttribute('data-kind')==='sub'){rows[i].style.display='none';} }
    var pages=Math.max(1,Math.ceil(match.length/st.size)); if(st.page>pages){st.page=pages;}
    var a=(st.page-1)*st.size, b=Math.min(match.length,st.page*st.size);
    for(i=a;i<b;i++){ match[i].style.display=''; var rid=match[i].getAttribute('data-rid'); var t=match[i].querySelector('.sft-tog');
        if(rid&&t&&t.getAttribute('aria-expanded')==='true'){ var s=document.getElementById(rid); if(s){s.style.display='';} } }
    var root=document.getElementById('sft-'+deb);
    var nm=root.querySelector('.sft-nomatch'); if(nm){nm.style.display=match.length?'none':'';}
    var c=root.querySelector('.sft-count'); if(c){ c.innerHTML=SftL.showing.replace('%s','<strong>'+(b-a)+'</strong>').replace('%s','<strong>'+match.length+'</strong>'); }
    var box=root.querySelector('.sft-pager'); if(!box){return;}
    if(pages<=1){box.innerHTML='';return;}
    var h='<a href=\"#\" data-p=\"'+(st.page-1)+'\" class=\"'+(st.page==1?'off':'')+'\">&lsaquo; '+SftL.prev+'</a>';
    for(var p=1;p<=pages;p++){ h+='<a href=\"#\" data-p=\"'+p+'\" class=\"'+(p==st.page?'cur':'')+'\"'+(p==st.page?' aria-current=\"page\"':'')+'>'+p+'</a>'; }
    h+='<a href=\"#\" data-p=\"'+(st.page+1)+'\" class=\"'+(st.page==pages?'off':'')+'\">'+SftL.next+' &rsaquo;</a>';
    box.innerHTML=h;
    var as=box.getElementsByTagName('a');
    for(i=0;i<as.length;i++){ as[i].onclick=function(){ var p=parseInt(this.getAttribute('data-p'),10); if(p>=1&&p<=pages&&p!=st.page){st.page=p;SftApply(deb,0);} return false; }; }
};
window.SftFilter=function(deb,f){
    var st=window.SftState[deb]; if(!st){return;} st.f=f;
    jQuery('#sft-'+deb+' .sft-segbtn').each(function(){ var on=this.getAttribute('data-f')===f; this.setAttribute('aria-checked',on?'true':'false'); jQuery(this).toggleClass('on',on); });
    SftApply(deb,1);
};
window.SftClear=function(deb){ var q=document.getElementById('sft-q-'+deb); if(q){q.value='';} SftFilter(deb,'mirrored'); };
window.SftToggle=function(btn,rid){
    var s=document.getElementById(rid); if(!s){return;}
    var open=btn.getAttribute('aria-expanded')!=='true';
    btn.setAttribute('aria-expanded',open?'true':'false'); s.style.display=open?'':'none';
    btn.innerHTML=open?'<i class=\"fas fa-chevron-up\"></i>':'<i class=\"fas fa-chevron-down\"></i>';
};
window.SftCopy=function(btn,txt){
    var done=function(){ var old=btn.innerHTML; btn.innerHTML='<i class=\"fas fa-check\"></i>'; btn.setAttribute('title',SftL.copied); setTimeout(function(){btn.innerHTML=old;},2000); };
    var fallback=function(){ var t=document.createElement('textarea'); t.value=txt; t.style.position='fixed'; t.style.opacity='0'; document.body.appendChild(t); t.select(); try{document.execCommand('copy');done();}catch(e){} document.body.removeChild(t); };
    if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(txt).then(done,fallback); }else{ fallback(); }
};
}
SftApply($deb,1);
</script>";
}

function sft_tiny(int $deb):string{
    $tpl=new template_admin();
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{artica_repositories} {softwares} Debian $deb";
    $TINY_ARRAY["ICO"]=ico_database;
    $TINY_ARRAY["EXPL"]="{artica_repositories_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons(array());
    return "<script>NoSpinner();Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');</script>";
}

function sft_style():string{
    if(isset($GLOBALS["SFT_CSS"])){return "";}
    $GLOBALS["SFT_CSS"]=true;
    // Les regles communes viennent de hfx_style() et du kit ak- (angular.css.php).
    return "<style>
.sft{max-width:1320px;margin-top:10px}
.sft-sum{grid-template-columns:.8fr .8fr 1fr 1.4fr 1.6fr}
.sft-cat{display:flex;align-items:center;gap:12px;padding-left:20px;justify-content:flex-end;text-align:right}
.sft-cat-v{font-size:14px;font-weight:600;color:#111d23;line-height:20px}
.sft-warn{color:#865300}
.sft-tools{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:16px;flex-wrap:wrap}
.sft-tog:focus-visible{outline:2px solid var(--ak-fill);outline-offset:2px}
.sft-ver{font-family:monospace;font-size:15px;font-weight:600;margin-right:6px}
.sft-tog{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border:1px solid #e2e8f0;background:#fff;border-radius:8px;color:#3d4947;cursor:pointer}
.sft-tog:hover{background:#e3f0f8}
.sft-sub>td{background:#f8fafc;padding:12px 20px 16px 76px!important}
.sft-subwrap{background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden}
.sft-subtbl th{padding:10px 16px}
.sft-subtbl td{padding:8px 16px;font-size:13px}
.sft-subver{font-weight:600;margin-right:6px}
@media (max-width:1200px){.sft-sum{grid-template-columns:repeat(4,minmax(0,1fr))}.sft-cat{grid-column:1/-1;justify-content:flex-start;text-align:left;padding:12px 0 0}}
</style>";
}
// Fin des logiciels Debian

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
        echo "<div id='hotfix-list'></div><script>window.HfxState=null;LoadAjax('hotfix-list','$page?hotfix-list=yes');</script>";
        return true;
}
function service_packs_start():bool{
    $page = CurrentPageName();
    echo "<div id='servicepack-list'></div><script>LoadAjax('servicepack-list','$page?sp-list=yes');</script>";
    return true;
}
// Liste des hotfixes (sous-onglet Repository > Hotfixes, maquette stitch_artica_meta_hotfixes)
// Filtre, recherche et pagination cote navigateur : 67 lignes sur meta, bien sous la limite
// de quelques centaines de lignes que supporte un tableau rendu en entier.
function hotfix_list():bool {
    $tpl=new template_admin();
    $m=hfx_model();
    $html=array();
    $html[]="<div class='hfx ak'>";
    if($m["error"]!=""){
        $html[]="<div class='ak-banner ak-err hfx-alert'><i class='fas fa-triangle-exclamation'></i> ".htmlspecialchars($m["error"])."</div>";
    }
    if($m["error"]!=""){
        // Index illisible : on ne pretend pas que le depot est vide.
    }elseif(count($m["files"])==0){
        $html[]=hfx_empty();
    }else{
        $html[]=hfx_summary($m);
        $html[]=hfx_toolbar($m);
        $html[]=hfx_table($m);
    }
    $html[]="</div>";
    echo hfx_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).hfx_js($m).hfx_tiny();
    return true;
}

// Nom de fichier -> version Artica, SP et numero de construction YYYYMMDDHH.
// Deux formes reelles : hotfix-4.50.000000-sp10-2026102317.tgz et hotfix-4.50.000000-sp10-20261102-03.tgz
function hfx_parse($filename):array{
    $r=array("ver"=>"","sp"=>-1,"build"=>"","ts"=>0);
    if(!preg_match('#^hotfix-(.+?)-sp([0-9]+)-([0-9]{8})-?([0-9]{2})\.#',$filename,$re)){return $r;}
    $r["ver"]=$re[1];
    $r["sp"]=intval($re[2]);
    $r["build"]=$re[3].$re[4];
    $ts=mktime(intval($re[4]),0,0,intval(substr($re[3],4,2)),intval(substr($re[3],6,2)),intval(substr($re[3],0,4)));
    $r["ts"]=$ts===false?0:$ts;
    return $r;
}

function hfx_model():array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/files/hotfix"));
    $m=array("files"=>array(),"error"=>"","total"=>0,"groups"=>array(),"versions"=>array(),"newest"=>null);
    if(!is_object($json)){
        $m["error"]="Artica REST API: no answer";
        return $m;
    }
    if(isset($json->Status) && !$json->Status && isset($json->Error)){
        $m["error"]=(string)$json->Error;
        return $m;
    }
    if(!isset($json->files) || !is_array($json->files)){return $m;}
    foreach($json->files as $f){
        $fn=isset($f->filename)?(string)$f->filename:"";
        if($fn==""){continue;}
        $p=hfx_parse($fn);
        $ver=$p["ver"]!=""?$p["ver"]:(isset($f->artica_version)?(string)$f->artica_version:"");
        $m["files"][]=array(
            "filename"=>$fn,"ver"=>$ver,"sp"=>$p["sp"],"build"=>$p["build"],"ts"=>$p["ts"],
            "size"=>isset($f->size)?floatval($f->size):0,
            "md5"=>isset($f->md5)?strtolower((string)$f->md5):"",
            "mtime"=>artrepo_ts(isset($f->synced_at)?$f->synced_at:""),
            "latest"=>false,
        );
    }
    // Le demon trie sur version_bin, toujours 0 pour un hotfix : on trie ici, SP puis construction.
    usort($m["files"],function($a,$b){
        if($a["ver"]!=$b["ver"]){return version_compare($b["ver"],$a["ver"]);}
        if($a["sp"]!=$b["sp"]){return $b["sp"]-$a["sp"];}
        return strcmp($b["build"],$a["build"]);
    });
    foreach($m["files"] as $i=>$f){
        $m["total"]+=$f["size"];
        $m["versions"][$f["ver"]]=true;
        $k=$f["ver"]."|".$f["sp"];
        if(!isset($m["groups"][$k])){
            $m["groups"][$k]=array("ver"=>$f["ver"],"sp"=>$f["sp"],"count"=>0,"size"=>0);
            $m["files"][$i]["latest"]=true;   // premier de son groupe = le plus recent
        }
        $m["groups"][$k]["count"]++;
        $m["groups"][$k]["size"]+=$f["size"];
        if($m["newest"]===null || strcmp($f["build"],$m["newest"]["build"])>0){$m["newest"]=$f;}
    }
    return $m;
}

// Token traduit AVANT substitution d'un %s (l'ordre des mots change selon la langue).
function hfx_t($k):string{
    $tpl=new template_admin();
    return $tpl->_ENGINE_parse_body("{".$k."}");
}

function hfx_size($bytes):string{
    $v=artrepo_bytes($bytes);
    if(preg_match('/^([0-9][0-9.,]*)\s*([A-Za-z]+)$/',$v,$re)){return $re[1]." ".$re[2];}
    return $v;
}

function hfx_build_label(array $f):string{
    if($f["ts"]>0){return date("Y-m-d H:i",$f["ts"]);}
    return htmlspecialchars($f["filename"]);
}

function hfx_build_id(array $f):string{
    if(strlen($f["build"])==10){return substr($f["build"],0,8)."-".substr($f["build"],8,2);}
    return "-";
}

// Liste compacte des SP presents : 0, 1, 4–10
function hfx_sp_ranges(array $sps):string{
    sort($sps);
    $out=array();$start=null;$prev=null;
    foreach($sps as $s){
        if($start===null){$start=$s;$prev=$s;continue;}
        if($s==$prev+1){$prev=$s;continue;}
        $out[]=$start==$prev?"$start":"$start&ndash;$prev";
        $start=$s;$prev=$s;
    }
    if($start!==null){$out[]=$start==$prev?"$start":"$start&ndash;$prev";}
    return @implode(", ",$out);
}

function hfx_sp_counts(array $m):array{
    $sp=array();
    foreach($m["files"] as $f){ if(!isset($sp[$f["sp"]])){$sp[$f["sp"]]=0;} $sp[$f["sp"]]++; }
    return $sp;
}

function hfx_summary(array $m):string{
    $sp=hfx_sp_counts($m);
    ksort($sp);
    $tpl=new template_admin();
    $size=hfx_size($m["total"]);
    $num=$size;$unit="";
    if(preg_match('/^(\S+) (\S+)$/',$size,$re)){$num=$re[1];$unit="<span class='hfx-unit'>".$re[2]."</span>";}
    $newest="-";
    if(is_array($m["newest"])){
        $newest="<span class='ak-mono'>".hfx_build_label($m["newest"])."</span> <span class='ak-tag ak-ok'>SP ".$m["newest"]["sp"]."</span>";
    }
    $spark=array();$alt=array();
    foreach($sp as $k=>$n){$spark[]=$n;$alt[]="SP $k: $n";}
    $h=array();
    $h[]="<section class='hfx-sum'>";
    $h[]="<div class='hfx-fact'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='fas fa-bandage'></i></span><div><div class='hfx-fact-k'>{hotfixes}</div><div class='hfx-fact-v'>".$tpl->FormatNumber(count($m["files"]),0,",")."</div></div></div>";
    $h[]="<div class='hfx-fact'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='fas fa-weight-hanging'></i></span><div><div class='hfx-fact-k'>{total_size}</div><div class='hfx-fact-v'>$num $unit</div></div></div>";
    $h[]="<div class='hfx-fact'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='fas fa-layer-group'></i></span><div><div class='hfx-fact-k'>{hfx_service_packs}</div><div class='hfx-fact-v'>".count($sp)." <span class='hfx-unit'>(".hfx_sp_ranges(array_keys($sp)).")</span></div></div></div>";
    $h[]="<div class='hfx-fact'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='fas fa-clock'></i></span><div><div class='hfx-fact-k'>{hfx_newest}</div><div class='hfx-fact-v hfx-fact-v2'>$newest</div></div></div>";
    $h[]="<div class='hfx-spark'><div class='hfx-spark-k'>{hfx_distribution}<br><span class='ak-mono'>SP ".min(array_keys($sp))." &rarr; SP ".max(array_keys($sp))."</span></div>";
    $h[]="<span class='hfx-bar' aria-hidden='true'>".@implode(",",$spark)."</span><span class='sr-only'>".@implode(", ",$alt)."</span></div>";
    $h[]="</section>";
    return @implode("\n",$h);
}

function hfx_toolbar(array $m):string{
    $sp=hfx_sp_counts($m);
    krsort($sp);
    $h=array();
    $h[]="<section class='hfx-tools'>";
    $h[]="<div class='hfx-tools-l'>";
    $h[]="<label class='ak-search hfx-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i><input type='search' id='hfx-q' placeholder=\"{hfx_search_ph}\" aria-label=\"{hfx_search_ph}\" oninput='HfxFilter()'></label>";
    $vers=array_keys($m["versions"]);
    if(count($vers)>1){
        rsort($vers);
        $o=array("<option value=''>{hfx_all_versions}</option>");
        foreach($vers as $v){$o[]="<option value='".htmlspecialchars($v,ENT_QUOTES)."'>Artica ".htmlspecialchars($v)."</option>";}
        $h[]="<label class='hfx-ver'><span>{hfx_version}</span><select id='hfx-ver' onchange='HfxFilter()'>".@implode("",$o)."</select></label>";
    }else{
        $h[]="<span class='hfx-ver'><span>{hfx_version}</span><span class='ak-mono'>Artica ".htmlspecialchars($vers[0])."</span></span>";
    }
    $h[]="</div>";
    $h[]="<div class='ak-seg hfx-chips' role='radiogroup' aria-label=\"{hfx_filter_sp}\">";
    $h[]="<button type='button' role='radio' aria-checked='true' class='ak-seg-b hfx-chip on' data-sp='all' onclick=\"HfxSp('all')\">{hfx_all} (".count($m["files"]).")</button>";
    foreach($sp as $k=>$n){
        $h[]="<button type='button' role='radio' aria-checked='false' class='ak-seg-b hfx-chip' data-sp='$k' onclick=\"HfxSp('$k')\">SP $k &middot; $n</button>";
    }
    $h[]="</div></section>";
    return @implode("\n",$h);
}

function hfx_table(array $m):string{
    $h=array();
    $h[]="<section class='ak-card'><div class='hfx-scroll'><table class='ak-tbl hfx-tbl'>";
    $h[]="<thead><tr><th scope='col'>{hotfix}</th><th scope='col'>Artica</th><th scope='col' class='ak-end'>{hfx_size}</th><th scope='col'>MD5</th><th scope='col'>{updated}</th><th scope='col' class='ak-end'>{hfx_actions}</th></tr></thead>";
    $h[]="<tbody id='hfx-rows'>";
    $cur="";
    foreach($m["files"] as $f){
        $k=$f["ver"]."|".$f["sp"];
        if($k!=$cur){
            $cur=$k;
            $h[]=hfx_group_row($m["groups"][$k],count($m["versions"])>1);
        }
        $h[]=hfx_row($f);
    }
    $h[]="<tr id='hfx-nomatch' style='display:none'><td colspan='6'><div class='hfx-empty-s'><i class='fas fa-filter-circle-xmark'></i> {hfx_nomatch} <button type='button' class='ak-link' onclick='HfxClear()'>{hfx_clear}</button></div></td></tr>";
    $h[]="</tbody></table></div>";
    $h[]="<div class='ak-card-f'><span id='hfx-count'></span><span class='ak-pg' id='hfx-pager'></span></div>";
    $h[]="</section>";
    return @implode("\n",$h);
}

function hfx_group_row(array $g,bool $showVer):string{
    $gid=md5($g["ver"]."|".$g["sp"]);
    $ver=$showVer?" <span class='ak-mono ak-muted'>Artica ".htmlspecialchars($g["ver"])."</span>":"";
    $count=$g["count"]==1?hfx_t("hfx_group_one"):str_replace("%s",$g["count"],hfx_t("hfx_group_count"));
    return "<tr class='hfx-grp' data-gid='$gid' data-sp='{$g["sp"]}' data-ver='".htmlspecialchars($g["ver"],ENT_QUOTES)."'><th colspan='6' scope='rowgroup'><span class='ak-dot ak-ok hfx-dot'></span><span class='hfx-grp-t'>{hfx_service_pack} {$g["sp"]}</span>$ver <span class='ak-muted'>&middot; $count &middot; ".hfx_size($g["size"])."</span></th></tr>";
}

function hfx_row(array $f):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gid=md5($f["ver"]."|".$f["sp"]);
    $rid="hfx-".md5($f["filename"]);
    $fn=htmlspecialchars($f["filename"],ENT_QUOTES);
    $fenc=urlencode($f["filename"]);
    $id=hfx_build_id($f);
    $label=hfx_build_label($f);
    $q=strtolower($f["filename"]." ".$id." ".$f["build"]." ".$label." sp".$f["sp"]." sp ".$f["sp"]." ".$f["md5"]);
    $latest=$f["latest"]?" <span class='ak-tag ak-ok hfx-latest'><i class='fas fa-star'></i> {hfx_latest}</span>":"";
    $md5=$f["md5"];
    $md5s=strlen($md5)==32?substr($md5,0,8)."&hellip;".substr($md5,-4):htmlspecialchars($md5);
    $upd="-";
    if($f["mtime"]>0){$upd="<div class='hfx-upd'>".artrepo_ago($f["mtime"])."</div><div class='ak-code'>".date("Y-m-d H:i",$f["mtime"])."</div>";}
    $h="<tr class='hfx-row' id='$rid' data-gid='$gid' data-sp='{$f["sp"]}' data-ver='".htmlspecialchars($f["ver"],ENT_QUOTES)."' data-q='".htmlspecialchars($q,ENT_QUOTES)."'>";
    $h.="<td><div class='hfx-ident'><span class='ak-ico ak-ico-sq'><i class='fas fa-bandage'></i></span><div><div class='hfx-name'>$label$latest</div><div class='ak-code' title='$fn'>id: $id</div></div></div></td>";
    $h.="<td class='ak-mono'>".htmlspecialchars($f["ver"])."</td>";
    $h.="<td class='ak-num ak-nowrap'>".hfx_size($f["size"])."</td>";
    $h.="<td><span class='hfx-md5'><span class='ak-mono' title='".htmlspecialchars($md5,ENT_QUOTES)."'>$md5s</span>";
    if(strlen($md5)==32){
        $h.="<button type='button' class='ak-act' aria-label=\"{hfx_copy_md5}\" title=\"{hfx_copy_md5}\" onclick=\"HfxCopy(this,'$md5')\"><i class='far fa-copy'></i></button>";
    }
    $h.="</span></td>";
    $h.="<td>$upd</td>";
    $h.="<td class='ak-end'><div class='ak-acts hfx-acts'>";
    $h.="<button type='button' class='ak-btn ak-btn-primary' onclick=\"Loadjs('$page?push-update-js=$fenc&type=hotfix');\"><i class='fas fa-paper-plane'></i> {push_update}</button>";
    $h.="<button type='button' class='ak-act ak-danger hfx-act-danger' aria-label=\"{hfx_delete}\" title=\"{hfx_delete}\" onclick=\"Loadjs('$page?delete-hotfix-js=$fenc&md=$rid');\"><i class='fas fa-trash-can'></i></button>";
    $h.="</div></td></tr>";
    return $h;
}

function hfx_empty():string{
    $page=CurrentPageName();
    return "<section class='ak-card hfx-empty'><span class='ak-ico ak-ico-lg'><i class='fas fa-bandage'></i></span>
<div class='hfx-empty-t'>{hfx_empty_title}</div><div class='hfx-empty-s'>{hfx_empty_sub}</div>
<button type='button' class='ak-btn ak-btn-primary' onclick=\"Loadjs('$page?hotfix-add-js=yes');\"><i class='fas fa-plus'></i> {add_hotfix}</button></section>";
}

// Concatene HORS de _ENGINE_parse_body. Les libelles du pager sont traduits en PHP puis passes en JSON.
function hfx_js(array $m):string{
    $tpl=new template_admin();
    $L=array();
    foreach(array("showing"=>"{hfx_showing}","prev"=>"{hfx_prev}","next"=>"{hfx_next}","copied"=>"{hfx_copied}") as $k=>$t){
        $L[$k]=trim(str_replace(array("[br]","<br>"),"",hfx_t(trim($t,"{}"))));
    }
    $accent=artrepo_accent();
    return "<script>
var HfxL=".json_encode($L).";
window.HfxState=window.HfxState||{q:'',sp:'all',ver:'',page:1};
function HfxRows(){ var tb=document.getElementById('hfx-rows'); return tb?tb.rows:[]; }
function HfxApply(){
    var st=window.HfxState, rows=HfxRows(), size=25, match=[], i;
    var q=(st.q||'').toLowerCase().trim();
    for(i=0;i<rows.length;i++){
        var r=rows[i]; if(r.className.indexOf('hfx-row')<0){continue;}
        var ok=true;
        if(st.sp!=='all' && r.getAttribute('data-sp')!==st.sp){ok=false;}
        if(ok && st.ver!=='' && r.getAttribute('data-ver')!==st.ver){ok=false;}
        if(ok && q!=='' && r.getAttribute('data-q').indexOf(q)<0){ok=false;}
        r.style.display='none';
        if(ok){match.push(r);}
    }
    var pages=Math.max(1,Math.ceil(match.length/size));
    if(st.page>pages){st.page=pages;} if(st.page<1){st.page=1;}
    var shown={}, a=(st.page-1)*size, b=Math.min(match.length,st.page*size);
    for(i=a;i<b;i++){ match[i].style.display=''; shown[match[i].getAttribute('data-gid')]=true; }
    for(i=0;i<rows.length;i++){ if(rows[i].className.indexOf('hfx-grp')>=0){ rows[i].style.display=shown[rows[i].getAttribute('data-gid')]?'':'none'; } }
    var nm=document.getElementById('hfx-nomatch'); if(nm){nm.style.display=match.length?'none':'';}
    var c=document.getElementById('hfx-count');
    if(c){ c.innerHTML=HfxL.showing.replace('%s','<strong>'+(b-a)+'</strong>').replace('%s','<strong>'+match.length+'</strong>'); }
    var box=document.getElementById('hfx-pager'); if(!box){return;}
    if(pages<=1){box.innerHTML='';return;}
    var h='<a href=\"#\" data-p=\"'+(st.page-1)+'\" class=\"'+(st.page==1?'off':'')+'\">&lsaquo; '+HfxL.prev+'</a>';
    for(var p=1;p<=pages;p++){ h+='<a href=\"#\" data-p=\"'+p+'\" class=\"'+(p==st.page?'cur':'')+'\"'+(p==st.page?' aria-current=\"page\"':'')+'>'+p+'</a>'; }
    h+='<a href=\"#\" data-p=\"'+(st.page+1)+'\" class=\"'+(st.page==pages?'off':'')+'\">'+HfxL.next+' &rsaquo;</a>';
    box.innerHTML=h;
    var as=box.getElementsByTagName('a');
    for(i=0;i<as.length;i++){ as[i].onclick=function(){ var p=parseInt(this.getAttribute('data-p'),10); if(p>=1&&p<=pages&&p!=st.page){st.page=p;HfxApply();} return false; }; }
}
function HfxFilter(){
    var q=document.getElementById('hfx-q'), v=document.getElementById('hfx-ver');
    window.HfxState.q=q?q.value:''; window.HfxState.ver=(v&&v.tagName==='SELECT')?v.value:''; window.HfxState.page=1; HfxApply();
}
function HfxSp(sp){
    window.HfxState.sp=sp; window.HfxState.page=1;
    jQuery('.hfx-chip').each(function(){ var on=this.getAttribute('data-sp')===sp; this.setAttribute('aria-checked',on?'true':'false'); jQuery(this).toggleClass('on',on); });
    HfxApply();
}
function HfxClear(){ var q=document.getElementById('hfx-q'); if(q){q.value='';} var v=document.getElementById('hfx-ver'); if(v&&v.tagName==='SELECT'){v.value='';} window.HfxState.q='';window.HfxState.ver=''; HfxSp('all'); }
function HfxCopy(btn,txt){
    var done=function(){ var old=btn.innerHTML; btn.innerHTML='<i class=\"fas fa-check\"></i>'; btn.setAttribute('title',HfxL.copied); setTimeout(function(){btn.innerHTML=old;},2000); };
    var fallback=function(){ var t=document.createElement('textarea'); t.value=txt; t.style.position='fixed'; t.style.opacity='0'; document.body.appendChild(t); t.select(); try{document.execCommand('copy');done();}catch(e){} document.body.removeChild(t); };
    if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(txt).then(done,fallback); }else{ fallback(); }
}
function HfxRemoved(rid){
    var r=document.getElementById(rid); if(!r){return;}
    var gid=r.getAttribute('data-gid'), badge=r.querySelector('.hfx-latest'); r.parentNode.removeChild(r);
    var left=0, rows=HfxRows(), i; for(i=0;i<rows.length;i++){ if(rows[i].className.indexOf('hfx-row')>=0 && rows[i].getAttribute('data-gid')===gid){left++;} }
    if(badge && left>0){ for(i=0;i<rows.length;i++){ if(rows[i].className.indexOf('hfx-row')>=0 && rows[i].getAttribute('data-gid')===gid){ var nm=rows[i].querySelector('.hfx-name'); if(nm){nm.appendChild(document.createTextNode(' '));nm.appendChild(badge);} break; } } }
    if(left===0){ for(i=0;i<rows.length;i++){ if(rows[i].className.indexOf('hfx-grp')>=0 && rows[i].getAttribute('data-gid')===gid){rows[i].parentNode.removeChild(rows[i]);break;} } }
    HfxApply();
}
(function(){
    var st=window.HfxState, q=document.getElementById('hfx-q'), v=document.getElementById('hfx-ver');
    if(q){q.value=st.q||'';} if(v&&v.tagName==='SELECT'){v.value=st.ver||'';}
    if(st.sp!=='all' && !document.querySelector('.hfx-chip[data-sp=\"'+st.sp+'\"]')){st.sp='all';}
    HfxSp(st.sp);
    function run(){ jQuery('.hfx .hfx-bar').peity('bar',{height:32,width:120,padding:0.2,fill:['$accent']}); }
    if(jQuery.fn.peity){run();}else{ var sc=document.createElement('script'); sc.src='js/jquery.peity.min.js'; sc.onload=run; document.head.appendChild(sc); }
})();
</script>";
}

function hfx_tiny():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $topbuttons=array();
    if(artrepo_can_write()){$topbuttons[]=array("Loadjs('$page?hotfix-add-js=yes')",ico_plus,"{add_hotfix}");}
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{artica_repositories} {hotfixes}";
    $TINY_ARRAY["ICO"]=ico_cd;
    $TINY_ARRAY["EXPL"]="{artica_repositories_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');</script>";
}

function hfx_style():string{
    if(isset($GLOBALS["HFX_CSS"])){return "";}
    $GLOBALS["HFX_CSS"]=true;
    // Carte, table, pastilles, recherche, segmente, pagination : kit ak- (angular.css.php).
    return "<style>
.hfx{max-width:1320px;margin-top:10px}
.hfx-alert{margin-bottom:16px}
.hfx-sum{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 24px;display:grid;grid-template-columns:1fr 1fr 1.2fr 1.5fr auto;align-items:center;margin-bottom:20px}
.hfx-fact{display:flex;align-items:center;gap:12px;min-width:0;padding:4px 20px;border-right:1px solid #e2e8f0}
.hfx-fact:first-child{padding-left:0}
.hfx-fact-k{font-size:13px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#3d4947}
.hfx-fact-v{font-size:22px;font-weight:700;line-height:28px;color:#111d23;white-space:nowrap}
.hfx-fact-v2{font-size:15px;line-height:24px}
.hfx-unit{font-size:13px;font-weight:600;color:#6d7a77}
.hfx-spark{display:flex;align-items:center;gap:12px;padding-left:20px}
.hfx-spark-k{font-size:13px;color:#3d4947;text-align:right;line-height:16px}
.hfx-tools{display:flex;flex-direction:column;gap:12px;margin-bottom:16px}
.hfx-tools-l{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.hfx-search{width:320px;height:40px}
.hfx-ver{display:inline-flex;align-items:center;gap:8px;height:40px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:0 12px;font-size:13px;color:#3d4947;margin:0;font-weight:400}
.hfx-ver select{border:0;font-size:13px;background:transparent}
.hfx-chips{flex-wrap:wrap;align-self:flex-start}
.hfx-tbl thead th{white-space:nowrap}
.hfx-tbl td{padding-top:12px;padding-bottom:12px}
.hfx-row:hover{background:#fbfeff}
.hfx-grp th{padding:10px 20px;font-size:14px;font-weight:600;letter-spacing:0;text-transform:none;color:var(--ak-text);border-top:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0}
.hfx-grp .ak-muted{font-size:13px;font-weight:400}
.hfx-dot{margin-right:8px;vertical-align:middle}
.hfx-ident{display:flex;align-items:center;gap:14px}
.hfx-name{font-size:16px;font-weight:700;line-height:22px;white-space:nowrap}
.hfx-ident .ak-code{margin-top:2px}
.hfx-latest{margin-left:8px;vertical-align:2px}
.hfx-md5{display:inline-flex;align-items:center;gap:6px;font-size:13px;white-space:nowrap}
.hfx-upd{white-space:nowrap}
.hfx-acts{gap:8px}
.hfx-act-danger{opacity:.45}
.hfx-row:hover .hfx-act-danger,.hfx-act-danger:focus-visible{opacity:1}
.hfx-empty{text-align:center;padding:48px 24px}
.hfx-empty-t{font-size:18px;font-weight:600;margin-top:14px}
.hfx-empty-s{font-size:14px;color:#6d7a77;margin:6px 0 18px;text-align:center;padding:16px}
.hfx-empty .hfx-empty-s{padding:0}
@media (max-width:1200px){.hfx-sum{grid-template-columns:repeat(4,minmax(0,1fr))}.hfx-spark{grid-column:1/-1;padding:12px 0 0;justify-content:flex-start}.hfx-fact:nth-child(4){border-right:0}}
</style>";
}
// Fin de la liste des hotfixes

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
// Service packs (sous-onglet Repository > Service Packs, maquette stitch_artica_meta_sp)
// Un seul SP par version Artica : la synchronisation supprime l'ancien avant de telecharger le nouveau.
function service_packs_list():bool{
    $tpl=new template_admin();
    $m=spk_model();
    $html=array();
    $html[]="<div class='spk ak'>";
    if($m["error"]!=""){
        $html[]="<div class='ak-banner ak-err'><i class='fas fa-triangle-exclamation'></i> ".htmlspecialchars($m["error"])."</div>";
    }elseif(count($m["files"])==0){
        $html[]=spk_empty();
    }else{
        $html[]=spk_strip($m);
        $html[]=spk_featured($m["files"][0],$m);
        if(count($m["files"])>1){$html[]=spk_others($m);}
    }
    $html[]="</div>";
    echo spk_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).spk_js().spk_tiny();
    return true;
}

function spk_model():array{
    $m=array("files"=>array(),"error"=>"","total"=>0,"catalog"=>null,"match"=>0);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/files/servicepack"));
    if(!is_object($json)){$m["error"]="Artica REST API: no answer";return $m;}
    if(isset($json->Status) && !$json->Status && isset($json->Error)){$m["error"]=(string)$json->Error;return $m;}
    $m["catalog"]=spk_catalog();
    if(isset($json->files) && is_array($json->files)){
        foreach($json->files as $f){
            $fn=isset($f->filename)?(string)$f->filename:"";
            if($fn==""){continue;}
            $sp=-1;$ver=isset($f->artica_version)?(string)$f->artica_version:"";
            if(preg_match('#^ArticaP([0-9]+)-(.+)\.tgz$#',$fn,$re)){$sp=intval($re[1]);if($ver==""){$ver=$re[2];}}
            $md5=isset($f->md5)?strtolower((string)$f->md5):"";
            $row=array("filename"=>$fn,"ver"=>$ver,"sp"=>$sp,"md5"=>$md5,
                "size"=>isset($f->size)?floatval($f->size):0,
                "mtime"=>artrepo_ts(isset($f->synced_at)?$f->synced_at:""),
                "cat"=>spk_catalog_state($m["catalog"],$ver,$sp,$md5));
            if($row["cat"]=="match"){$m["match"]++;}
            $m["total"]+=$row["size"];
            $m["files"][]=$row;
        }
    }
    // L'index est une map Go : ordre aleatoire a chaque appel. Version la plus recente d'abord.
    usort($m["files"],function($a,$b){ return version_compare($b["ver"],$a["ver"]); });
    return $m;
}

// Catalogue publie par Artica, enregistre par la synchronisation dans le depot.
// Contenu venu d'Internet : unserialize sans aucune classe. null = catalogue illisible.
function spk_catalog(){
    $st=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/status"));
    $repo=is_object($st)&&isset($st->repo_path)?(string)$st->repo_path:"";
    if($repo=="" || $repo[0]!="/" || strpos($repo,"..")!==false){return null;}
    $path=rtrim($repo,"/")."/servicepack-index.dat";
    if(!@is_file($path) || !@is_readable($path)){return null;}
    $raw=@file_get_contents($path,false,null,0,1048576);
    if($raw===false || strlen($raw)<10){return null;}
    $data=@unserialize($raw,array("allowed_classes"=>false));
    if(!is_array($data)){return null;}
    $cat=array();
    foreach($data as $ver=>$d){
        if(!is_array($d)){continue;}
        $cat[(string)$ver]=array("sp"=>isset($d["VERSION"])?intval($d["VERSION"]):-1,"md5"=>isset($d["MD5"])?strtolower(trim((string)$d["MD5"])):"");
    }
    return $cat;
}

function spk_catalog_state($cat,$ver,$sp,$md5):string{
    if(!is_array($cat)){return "unknown";}
    if(!isset($cat[$ver])){return "absent";}
    if($cat[$ver]["sp"]==$sp && $cat[$ver]["md5"]!="" && $cat[$ver]["md5"]==$md5){return "match";}
    return "differs";
}

function spk_badge($state,$short):string{
    if($state=="match"){return "<span class='ak-tag ak-ok'><i class='fas fa-circle-check'></i> ".($short?"{spk_cat_match_s}":"{spk_cat_match}")."</span>";}
    if($state=="differs"){return "<span class='ak-tag ak-warn'><i class='fas fa-triangle-exclamation'></i> ".($short?"{spk_cat_differs_s}":"{spk_cat_differs}")."</span>";}
    if($state=="absent"){return "<span class='ak-tag ak-off'><i class='fas fa-circle-question'></i> ".($short?"{spk_cat_absent_s}":"{spk_cat_absent}")."</span>";}
    return "<span class='ak-tag ak-off'><i class='fas fa-circle-question'></i> {spk_cat_unknown}</span>";
}

function spk_md5(array $f):string{
    $md5=$f["md5"];
    if(strlen($md5)!=32){return "<span class='ak-mono'>".htmlspecialchars($md5==""?"-":$md5)."</span>";}
    return "<span class='spk-md5'><span class='ak-chip' title='$md5'>".substr($md5,0,8)."&hellip;".substr($md5,-4)."</span>"
        ."<button type='button' class='ak-act' aria-label=\"{hfx_copy_md5}\" title=\"{hfx_copy_md5}\" onclick=\"SpkCopy(this,'$md5')\"><i class='far fa-copy'></i></button></span>";
}

function spk_updated(array $f):string{
    if($f["mtime"]<=0){return "-";}
    return "<div class='spk-upd'>".artrepo_ago($f["mtime"])."</div><div class='ak-code ak-nowrap'>".date("Y-m-d H:i",$f["mtime"])."</div>";
}

function spk_push_js(array $f):string{
    $page=CurrentPageName();
    return "Loadjs('$page?push-update-js=".urlencode($f["filename"])."&type=servicepack');";
}

function spk_strip(array $m):string{
    $n=count($m["files"]);
    if(!is_array($m["catalog"])){
        $cat="<span class='spk-fact-v ak-muted'><i class='fas fa-circle-question'></i> {spk_cat_unknown}</span>";
    }elseif($m["match"]==$n){
        $cat="<span class='spk-fact-v spk-ok'><i class='fas fa-circle-check'></i> {$m["match"]} / $n {spk_match_word}</span>";
    }else{
        $cat="<span class='spk-fact-v spk-warn'><i class='fas fa-triangle-exclamation'></i> {$m["match"]} / $n {spk_match_word}</span>";
    }
    return "<section class='spk-strip'><div class='spk-note'><i class='fas fa-circle-info'></i><span>{spk_rule}</span></div>
<div class='spk-facts'><div class='spk-fact'><span class='spk-fact-k'>{spk_artica_versions}</span><span class='spk-fact-v'>$n</span></div>
<div class='spk-fact'><span class='spk-fact-k'>{total_size}</span><span class='spk-fact-v'>".hfx_size($m["total"])."</span></div>
<div class='spk-fact'><span class='spk-fact-k'>{spk_catalog_check}</span>$cat</div></div></section>";
}

function spk_featured(array $f,array $m):string{
    $sp=$f["sp"]>=0?$f["sp"]:"?";
    return "<section class='spk-feat' role='region' aria-label=\"{spk_newest}\">
<div class='spk-feat-l'><div class='spk-eyebrow'>{spk_newest}</div><div class='ak-mono spk-feat-ver'>Artica ".htmlspecialchars($f["ver"])."</div><div class='spk-feat-sp'>{hfx_service_pack} $sp</div></div>
<div class='spk-feat-m'>
<div class='spk-metric'><div class='spk-metric-k'>{hfx_size}</div><div class='spk-metric-v ak-nowrap'>".hfx_size($f["size"])."</div></div>
<div class='spk-metric'><div class='spk-metric-k'>{updated}</div>".spk_updated($f)."</div>
<div class='spk-metric'><div class='spk-metric-k'>MD5</div>".spk_md5($f)."</div>
</div>
<div class='spk-feat-r'>".spk_badge($f["cat"],false)."<button type='button' class='ak-btn ak-btn-primary ak-btn-lg' onclick=\"".spk_push_js($f)."\"><i class='fas fa-paper-plane'></i> {push_update}</button></div>
</section>";
}

function spk_others(array $m):string{
    $rows=array();
    foreach(array_slice($m["files"],1) as $f){
        $rows[]=spk_row($f);
    }
    return "<section class='spk-others'><h3 class='spk-h'>{spk_other_versions}</h3>
<div class='ak-card'><div class='spk-scroll'><table class='ak-tbl spk-tbl'>
<thead><tr><th scope='col'>Artica</th><th scope='col'>{hfx_service_pack}</th><th scope='col' class='ak-end'>{hfx_size}</th><th scope='col'>MD5</th><th scope='col'>{updated}</th><th scope='col'>{spk_catalog}</th><th scope='col' class='ak-end'>{hfx_actions}</th></tr></thead>
<tbody>".@implode("\n",$rows)."</tbody></table></div></div></section>";
}

function spk_row(array $f):string{
    $sp=$f["sp"]>=0?"SP ".$f["sp"]:"-";
    return "<tr><td><div class='spk-ident'><span class='ak-ico ak-ico-sq'><i class='fas fa-box-archive'></i></span><span class='ak-mono spk-ver' title='".htmlspecialchars($f["filename"],ENT_QUOTES)."'>".htmlspecialchars($f["ver"])."</span></div></td>
<td class='spk-spn'>$sp</td><td class='ak-num ak-nowrap'>".hfx_size($f["size"])."</td><td>".spk_md5($f)."</td><td>".spk_updated($f)."</td><td>".spk_badge($f["cat"],true)."</td>
<td class='ak-end'><button type='button' class='ak-btn' onclick=\"".spk_push_js($f)."\"><i class='fas fa-paper-plane'></i> {push_update}</button></td></tr>";
}

function spk_empty():string{
    return "<section class='ak-card spk-empty'><span class='ak-ico ak-ico-lg'><i class='fas fa-box-archive'></i></span>
<div class='spk-empty-t'>{spk_empty_title}</div><div class='spk-empty-s'>{spk_empty_sub}</div></section>";
}

function spk_js():string{
    $L=array("copied"=>trim(str_replace(array("[br]","<br>"),"",hfx_t("hfx_copied"))));
    return "<script>
var SpkL=".json_encode($L).";
function SpkCopy(btn,txt){
    var done=function(){ var old=btn.innerHTML; btn.innerHTML='<i class=\"fas fa-check\"></i>'; btn.setAttribute('title',SpkL.copied); setTimeout(function(){btn.innerHTML=old;},2000); };
    var fallback=function(){ var t=document.createElement('textarea'); t.value=txt; t.style.position='fixed'; t.style.opacity='0'; document.body.appendChild(t); t.select(); try{document.execCommand('copy');done();}catch(e){} document.body.removeChild(t); };
    if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(txt).then(done,fallback); }else{ fallback(); }
}
</script>";
}

function spk_tiny():string{
    $tpl=new template_admin();
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{artica_repositories} {service_packs}";
    $TINY_ARRAY["ICO"]=ico_cd;
    $TINY_ARRAY["EXPL"]="{artica_repositories_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons(array());
    return "<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');</script>";
}

function spk_style():string{
    if(isset($GLOBALS["SPK_CSS"])){return "";}
    $GLOBALS["SPK_CSS"]=true;
    $accent=artrepo_accent();
    $line="rgba(0,106,98,.35)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    // Carte, table, etiquettes, boutons : kit ak- (angular.css.php).
    return "<style>
.spk{max-width:1320px;margin-top:10px}
.spk-strip{display:flex;align-items:center;justify-content:space-between;gap:24px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px 24px;margin-bottom:24px;flex-wrap:wrap}
.spk-note{display:flex;align-items:flex-start;gap:10px;font-size:14px;color:#3d4947;flex:1 1 420px;line-height:20px}
.spk-note svg,.spk-note i{color:$accent;margin-top:2px;font-size:16px}
.spk-facts{display:flex;align-items:center}
.spk-fact{display:flex;align-items:center;gap:10px;padding:0 20px;border-left:1px solid #e2e8f0;white-space:nowrap}
.spk-fact:first-child{border-left:0}
.spk-fact-k{font-size:13px;color:#6d7a77}
.spk-fact-v{font-size:16px;font-weight:700;color:#111d23}
.spk-ok{color:#00504a}.spk-warn{color:#865300}
.spk-feat{display:grid;grid-template-columns:minmax(260px,1fr) 2fr auto;gap:24px;align-items:center;background:#fff;border:1px solid $line;border-top:4px solid $accent;border-radius:12px;padding:24px 28px;margin-bottom:32px}
.spk-eyebrow{font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#00504a}
.spk-feat-ver{font-size:15px;margin-top:6px;color:#111d23}
.spk-feat-sp{font-size:28px;font-weight:700;line-height:34px;margin-top:4px}
.spk-feat-m{display:flex;gap:0;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0}
.spk-metric{flex:1 1 0;padding:0 20px;min-width:0}
.spk-metric+.spk-metric{border-left:1px solid #e2e8f0}
.spk-metric-k{font-size:13px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#6d7a77;margin-bottom:6px}
.spk-metric-v{font-size:16px;font-weight:700}
.spk-feat-r{display:flex;flex-direction:column;align-items:flex-end;gap:12px}
.spk-md5{display:inline-flex;align-items:center;gap:6px;font-size:13px;white-space:nowrap}
.spk-upd{white-space:nowrap}
.spk .ak-code{margin-top:2px}
.spk-h{font-size:14px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#3d4947;margin:0 0 12px}
.spk-tbl tbody tr:hover{background:#fbfeff}
.spk-tbl thead th{white-space:nowrap}
.spk-ident{display:flex;align-items:center;gap:14px}
.spk-ver{font-size:15px;font-weight:600}
.spk-spn{font-size:16px;font-weight:700;white-space:nowrap}
.spk-empty{text-align:center;padding:48px 24px}
.spk-empty-t{font-size:18px;font-weight:600;margin-top:14px}
.spk-empty-s{font-size:14px;color:#6d7a77;margin-top:6px}
@media (max-width:1200px){.spk-feat{grid-template-columns:1fr auto}.spk-feat-m{grid-column:1/-1;grid-row:2;border:0;border-top:1px solid #e2e8f0;padding-top:16px}.spk-metric:first-child{padding-left:0}}
</style>";
}
// Fin des service packs

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
    echo $tpl->form_outside("",$form,"","{apply}","dialogInstance2.close();if(typeof ArtRepoRefresh==='function'){ArtRepoRefresh();}","AsArticaMetaAdmin");
    return true;
}
function debian_save():bool{
    $tpl = new template_admin();
    if(!artrepo_can_write()){echo $tpl->post_error("{ERROR_NO_PRIVS}");return false;}
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
// ---------------------------------------------------------------------------------------------
// Push update (maquette stitch_artica_update_push_modal) : fiche du paquet, onglets Nodes/Groups,
// table des cibles avec applicabilite calculee, barre d'action avec confirmation, activite des envois.
// Donnees : index du depot (/articarepos/files/{type}), /netagents/alist, /netagents/groups,
// /articarepos/push/status (operations gardees 30 min par le demon, 2 envois simultanes).
// ---------------------------------------------------------------------------------------------
function pshu_types():array{
    return array("hotfix"=>"{pshu_type_hotfix}","servicepack"=>"{pshu_type_servicepack}",
        "official"=>"{pshu_type_official}","software"=>"{pshu_type_software}","dnsfirewall"=>"{pshu_type_dnsfirewall}");
}

function pshu_get_file():string{
    return preg_replace('/[^A-Za-z0-9_.+~-]/','',(string)($_GET["filename"] ?? ""));
}
function pshu_get_type():string{
    $t=(string)($_GET["type"] ?? "");
    $types=pshu_types();
    return isset($types[$t])?$t:"";
}

// Le paquet : version, SP et build lus dans le nom, taille et md5 dans l'index du depot.
function pshu_file(string $fn,string $type):array{
    $f=array("filename"=>$fn,"type"=>$type,"ver"=>"","sp"=>-1,"build"=>"","ts"=>0,"size"=>-1,"md5"=>"","indexed"=>null);
    if(preg_match('#^hotfix-#',$fn)){
        $p=hfx_parse($fn);
        $f["ver"]=$p["ver"];$f["sp"]=$p["sp"];$f["build"]=$p["build"];$f["ts"]=$p["ts"];
        if($type==""){$f["type"]="hotfix";}
    }elseif(preg_match('#^ArticaP([0-9]+)-(.+)\.tgz$#',$fn,$re)){
        $f["sp"]=intval($re[1]);$f["ver"]=$re[2];
        if($type==""){$f["type"]="servicepack";}
    }
    if($f["type"]==""){return $f;}
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/files/".$f["type"]));
    if(!is_object($json) || !isset($json->files) || !is_array($json->files)){return $f;}
    $f["indexed"]=false;
    foreach($json->files as $row){
        if(!isset($row->filename) || (string)$row->filename!==$fn){continue;}
        $f["indexed"]=true;
        if(isset($row->size)){$f["size"]=floatval($row->size);}
        if(isset($row->md5)){$f["md5"]=strtolower((string)$row->md5);}
        if($f["ver"]=="" && !empty($row->artica_version)){$f["ver"]=(string)$row->artica_version;}
        break;
    }
    return $f;
}

// Hotfix publie par le noeud ($GLOBALS["HOTFIX"] de fw.updates.php, "20261113-01") : 10 chiffres.
function pshu_hf_digits(string $hf):string{
    $d=preg_replace('/[^0-9]/','',$hf);
    return strlen($d)==10?$d:"";
}

// applicable | installed | newer | other | unknown | blocked | na
function pshu_state(array $f,array $a):string{
    if($a["blacklisted"]){return "blocked";}
    if($f["type"]!="hotfix" && $f["type"]!="servicepack"){return "na";}
    if($f["ver"]=="" || $f["sp"]<0){return "na";}
    if($a["ver"]==""){return "unknown";}
    if($a["ver"]!==$f["ver"]){return "other";}
    if($f["type"]=="servicepack"){
        if($a["sp"]==$f["sp"]){return "installed";}
        if($a["sp"]>$f["sp"]){return "newer";}
        return "applicable";
    }
    if($a["sp"]!=$f["sp"]){return "other";}
    $cur=pshu_hf_digits($a["hf"]);
    if($cur!="" && strlen($f["build"])==10){
        $c=strcmp($cur,$f["build"]);
        if($c==0){return "installed";}
        if($c>0){return "newer";}
    }
    return "applicable";
}

// Evaluation d'un membre de groupe pour pshu_group_targets() : null = pas d'applicabilite.
function pshu_hotfix_eval($f){
    if(!is_array($f)){return null;}
    return function($ag)use($f){
        return pshu_state($f,array("ver"=>trim((string)($ag->artica_version ?? "")),"sp"=>intval($ag->artica_service_pack ?? 0),
            "hf"=>trim((string)($ag->artica_hotfix ?? "")),"blacklisted"=>false));
    };
}

function pshu_nodes_model(array $f,int $pre):array{
    $m=array("error"=>"","agents"=>array(),"applicable"=>0,"evaluable"=>($f["type"]=="hotfix"||$f["type"]=="servicepack"));
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/alist?status=online&enabled=1"));
    if(!is_object($json)){$m["error"]="Artica REST API: no answer";return $m;}
    if(isset($json->Status) && !$json->Status){$m["error"]=(string)($json->Error ?? "{error}");return $m;}
    if(!isset($json->agents) || !is_array($json->agents)){return $m;}
    foreach($json->agents as $ag){
        if(($ag->status ?? "")!="online"){continue;}
        $a=array("id"=>intval($ag->id ?? 0),"host"=>(string)($ag->hostname ?? ""),"ip"=>(string)($ag->ip_address ?? ""),
            "ver"=>trim((string)($ag->artica_version ?? "")),"sp"=>intval($ag->artica_service_pack ?? 0),
            "hf"=>trim((string)($ag->artica_hotfix ?? "")),"blacklisted"=>!empty($ag->blacklisted));
        if($a["id"]<=0){continue;}
        $a["state"]=pshu_state($f,$a);
        $a["checked"]=($pre==$a["id"] && $a["state"]!="blocked" && $a["state"]!="installed");
        if($a["state"]=="applicable"){$m["applicable"]++;}
        $m["agents"][]=$a;
    }
    usort($m["agents"],function($x,$y){ return strcasecmp($x["host"],$y["host"]); });
    return $m;
}

// Groupes : chaque groupe est lu en detail pour connaitre ses noeuds reels. Seuls les noeuds
// en ligne, Artica et non bloques sont des cibles ; les autres sont comptes et ignores a l'envoi
// (pshu_group_targets, partage avec push_update_groups_post).
function pshu_groups_model(array $f):array{
    $m=array("error"=>"","groups"=>array());
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups"));
    if($json===null){$m["error"]="Artica REST API: no answer";return $m;}
    if(is_object($json) && isset($json->Status) && !$json->Status){$m["error"]=(string)($json->Error ?? "{error}");return $m;}
    if(!is_array($json)){return $m;}
    foreach($json as $g){
        $gid=intval($g->id ?? 0);
        if($gid<=0){continue;}
        $row=array("id"=>$gid,"name"=>(string)($g->name ?? "#$gid"),"desc"=>trim((string)($g->description ?? "")),
            "denied"=>!empty($g->blacklisted),"read"=>false);
        $row=array_merge($row,pshu_group_targets($gid,pshu_hotfix_eval($f)));
        $m["groups"][]=$row;
    }
    usort($m["groups"],function($x,$y){ return strcasecmp($x["name"],$y["name"]); });
    return $m;
}

// Noeuds d'un groupe, classes : on (cible), off (hors ligne), bl (bloque), na (sans Artica).
// $f nul : pas d'evaluation d'applicabilite (envoi). "read" faux = groupe illisible.

// Token traduit, puis %s remplaces dans l'ordre.

function push_update_js():bool {
    $page = CurrentPageName();
    $tpl = new template_admin();
    $filename = isset($_GET["push-update-js"]) ? urlencode($_GET["push-update-js"]) : "";
    $type = pshu_get_type();
    $agentId = isset($_GET["agent_id"]) ? intval($_GET["agent_id"]) : 0;
    return $tpl->js_dialog2("{push_update}", "$page?push-update-tabs=yes&filename=$filename&type=$type&agent_id=$agentId", 980);
}

function push_update_tabs():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $fn = pshu_get_file();
    $type = pshu_get_type();
    $pre = isset($_GET["agent_id"]) ? intval($_GET["agent_id"]) : 0;
    $q="filename=".urlencode($fn)."&type=$type&agent_id=$pre";
    $array=array();
    $array["{pshu_tab_nodes}"]="$page?push-update-popup=yes&$q";
    $array["{pshu_tab_groups}"]="$page?push-update-groups=yes&$q";
    $f=pshu_file($fn,$type);
    echo pshu_style().$tpl->_ENGINE_parse_body(pshu_package($f)).$tpl->tabs_default($array);
    return true;
}

// 1. Fiche du paquet
function pshu_package(array $f):string{
    $types=pshu_types();
    $ico=$f["type"]=="hotfix"?"fa-bandage":($f["type"]=="servicepack"?"fa-box-archive":"fa-box");
    $chips=array();
    if(isset($types[$f["type"]])){$chips[]="<span class='ak-chip ak-accent pshu-chip'>".$types[$f["type"]]."</span>";}
    if($f["ver"]!=""){$chips[]="<span class='ak-chip pshu-chip'>".pshu_t("pshu_version",array(htmlspecialchars($f["ver"])))."</span>";}
    if($f["sp"]>=0){$chips[]="<span class='ak-chip pshu-chip'>".pshu_t("pshu_sp",array($f["sp"]))."</span>";}
    if($f["ts"]>0){$chips[]="<span class='ak-chip pshu-chip'>".pshu_t("pshu_build",array(date("Y-m-d H",$f["ts"])."h"))."</span>";}
    if($f["size"]>=0){$chips[]="<span class='ak-chip pshu-chip'>".hfx_size($f["size"])."</span>";}
    if(strlen($f["md5"])==32){
        $md5=$f["md5"];
        $chips[]="<span class='ak-chip pshu-chip' title='$md5'>MD5 ".substr($md5,0,8)."&hellip;".substr($md5,-4)
            ."<button type='button' class='ak-act pshu-copy' aria-label=\"{hfx_copy_md5}\" title=\"{hfx_copy_md5}\" onclick=\"PshuCopy(this,'$md5')\"><i class='far fa-copy'></i></button></span>";
    }
    $warn="";
    if($f["indexed"]===false){
        $warn="<div class='ak-banner ak-warn pshu-warn'><i class='fas fa-triangle-exclamation'></i><span class='ak-banner-m'>{pshu_not_in_repo}</span></div>";
    }
    return "<section class='ak pshu-pkg'><div class='ak-strip'><span class='ak-ico ak-ico-sq ak-info'><i class='fas $ico'></i></span>
<div class='ak-strip-main'><div class='ak-strip-t ak-mono pshu-fn'>".htmlspecialchars($f["filename"])."</div><div class='ak-chips pshu-chips'>".implode("",$chips)."</div></div></div>$warn</section>";
}


// ---------- Onglet Nodes ----------
function push_update_popup():bool {
    $tpl = new template_admin();
    $fn = pshu_get_file();
    $type = pshu_get_type();
    $pre = isset($_GET["agent_id"]) ? intval($_GET["agent_id"]) : 0;
    $f=pshu_file($fn,$type);
    $m=pshu_nodes_model($f,$pre);
    $id=pshu_host_id();
    $html=array();
    $html[]="<div class='ak pshu' id='$id' data-kind='nodes' data-file='".htmlspecialchars($fn,ENT_QUOTES)."' data-type='".$f["type"]."'>";
    if($m["error"]!=""){
        $html[]="<div class='ak-banner ak-err'><i class='fas fa-circle-exclamation'></i><span class='ak-banner-m'>".htmlspecialchars($m["error"])."</span></div>";
    }elseif(count($m["agents"])==0){
        $html[]=pshu_empty("fa-plug-circle-xmark","{pshu_empty_nodes}","{pshu_empty_nodes_s}");
    }else{
        $html[]=pshu_toolbar($id,"nodes",$m["evaluable"],count($m["agents"]));
        $html[]=pshu_nodes_table($id,$m);
        $html[]=pshu_confirm($id,"nodes");
        $html[]=pshu_actionbar($id,"nodes");
    }
    $html[]=pshu_activity_host($id,$fn);
    $html[]="</div>";
    echo pshu_style().$tpl->_ENGINE_parse_body(implode("\n",$html)).pshu_js($id);
    return true;
}

// 3. Barre de selection

// 4. Table des noeuds
function pshu_nodes_table(string $id,array $m):string{
    $rows=array();
    foreach($m["agents"] as $a){$rows[]=pshu_node_row($id,$a);}
    return "<div class='ak-card pshu-tblwrap'><div class='pshu-scroll' tabindex='0' role='region' aria-label=\"{pshu_tab_nodes}\"><table class='ak-tbl pshu-tbl'>
<thead><tr><th class='pshu-cbcol'><input type='checkbox' id='$id-all' aria-label=\"{pshu_sel_all}\" onclick=\"PshuSel('$id',this.checked?'all':'none')\"></th>
<th>{pshu_th_host}</th><th>{pshu_th_ver}</th><th>{pshu_th_sp}</th><th>{pshu_th_hf}</th><th>{pshu_th_app}</th></tr></thead>
<tbody id='$id-rows'>".implode("",$rows)."</tbody></table>
<div class='pshu-nomatch' id='$id-nomatch' hidden>{pshu_nomatch}</div></div></div>";
}

function pshu_node_row(string $id,array $a):string{
    $st=$a["state"];
    $locked=($st=="blocked"||$st=="installed");
    $host=netagent_display_name($a["id"],$a["host"]);
    $ip=netagent_address_label($a["ip"]);
    $q=htmlspecialchars(strtolower($a["host"]." ".$a["ip"]),ENT_QUOTES);
    $lid="$id-l".$a["id"];
    $other=($st=="other");
    $ver=$a["ver"]==""?"<span class='ak-muted'>&mdash;</span>":"<span class='ak-mono".($other?" pshu-diff":"")."'>".htmlspecialchars($a["ver"])."</span>";
    $sp=$a["ver"]==""?"<span class='ak-muted'>&mdash;</span>":"<span class='ak-mono".($other?" pshu-diff":"")."'>SP".$a["sp"]."</span>";
    $hf=$a["hf"]==""?"<span class='ak-muted'>&mdash;</span>":"<span class='ak-mono'>".htmlspecialchars($a["hf"])."</span>";
    $cls="pshu-row pshu-st-$st".($a["checked"]?" on":"");
    $dis=$locked?" disabled":"";
    $chk=$a["checked"]?" checked":"";
    $h="<tr class='$cls' data-q='$q' data-st='$st' data-label='$host'>";
    $h.="<td class='pshu-cbcol'><input type='checkbox' class='pshu-cb' value='{$a["id"]}' aria-labelledby='$lid'$chk$dis onchange=\"PshuSync('$id')\"></td>";
    $h.="<td><span class='ak-name' id='$lid'>$host</span><span class='ak-code'>$ip</span></td>";
    $h.="<td>$ver</td><td>$sp</td><td>$hf</td>";
    $h.="<td>".($st=="na"?"<span class='ak-muted' title=\"{pshu_tip_na}\">&mdash;</span>":pshu_state_pill($st))."</td>";
    $h.="</tr>";
    return $h;
}

function pshu_state_pill(string $st):string{
    $map=array(
        "applicable"=>array("ak-ok","fa-circle-check","{pshu_st_applicable}",""),
        "installed"=>array("ak-off","fa-equals","{pshu_st_installed}","{pshu_tip_installed}"),
        "newer"=>array("ak-off","fa-arrow-up","{pshu_st_newer}","{pshu_tip_newer}"),
        "other"=>array("ak-warn","fa-triangle-exclamation","{pshu_st_other}","{pshu_tip_other}"),
        "unknown"=>array("ak-off","fa-circle-question","{pshu_st_unknown}","{pshu_tip_unknown}"),
        "blocked"=>array("ak-err","fa-ban","{pshu_st_blocked}","{pshu_tip_blocked}"),
    );
    if(!isset($map[$st])){return "";}
    $e=$map[$st];
    $tip=$e[3]!=""?" title=\"".$e[3]."\"":"";
    return "<span class='ak-pill {$e[0]} pshu-pill'$tip><i class='fas {$e[1]}'></i> {$e[2]}</span>";
}


// 5. Confirmation (dans le volet, pas d'alerte navigateur)

// Barre d'action collee en bas

// 6. Activite : hote recharge seul

// ---------- Onglet Groups ----------
function push_update_groups():bool{
    $tpl = new template_admin();
    $fn = pshu_get_file();
    $type = pshu_get_type();
    $f=pshu_file($fn,$type);
    $m=pshu_groups_model($f);
    $id=pshu_host_id();
    $html=array();
    $html[]="<div class='ak pshu' id='$id' data-kind='groups' data-file='".htmlspecialchars($fn,ENT_QUOTES)."' data-type='".$f["type"]."'>";
    if($m["error"]!=""){
        $html[]="<div class='ak-banner ak-err'><i class='fas fa-circle-exclamation'></i><span class='ak-banner-m'>".htmlspecialchars($m["error"])."</span></div>";
    }elseif(count($m["groups"])==0){
        $html[]=pshu_empty("fa-layer-group","{pshu_empty_groups}","");
    }else{
        $html[]=pshu_toolbar($id,"groups",false,count($m["groups"]));
        $html[]=pshu_groups_table($id,$m,$f);
        $html[]=pshu_confirm($id,"groups");
        $html[]=pshu_actionbar($id,"groups");
    }
    $html[]=pshu_activity_host($id,$fn);
    $html[]="</div>";
    echo pshu_style().$tpl->_ENGINE_parse_body(implode("\n",$html)).pshu_js($id);
    return true;
}

function pshu_groups_table(string $id,array $m,array $f):string{
    $rows=array();
    $evaluable=($f["type"]=="hotfix"||$f["type"]=="servicepack") && $f["ver"]!="" && $f["sp"]>=0;
    foreach($m["groups"] as $g){$rows[]=pshu_group_row($id,$g,$evaluable);}
    return "<div class='ak-card pshu-tblwrap'><div class='pshu-scroll' tabindex='0' role='region' aria-label=\"{pshu_tab_groups}\"><table class='ak-tbl pshu-tbl pshu-gtbl'>
<thead><tr><th class='pshu-cbcol'><input type='checkbox' id='$id-all' aria-label=\"{pshu_sel_all}\" onclick=\"PshuSel('$id',this.checked?'all':'none')\"></th>
<th>{pshu_th_group}</th><th>{pshu_th_online}</th><th>{pshu_th_app}</th><th class='ak-num'>{pshu_th_offline}</th></tr></thead>
<tbody id='$id-rows'>".implode("",$rows)."</tbody></table>
<div class='pshu-nomatch' id='$id-nomatch' hidden>{pshu_nomatch}</div></div></div>";
}



// ---------- Envois (POST) : n'ecrivent que l'erreur ----------
// Envois (push_update, push_update_groups_post) : meta_can_push() -- infogerant, ou tenant vers SES
// noeuds et groupes (le daemon filtre, spec 2026-11-13 5.10.5). La GESTION du depot reste artrepo_can_write().
function push_update():bool{
    $tpl = new template_admin();
    if(!meta_can_push()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");return false;}
    $filename = preg_replace('/[^A-Za-z0-9_.+~-]/','',trim((string)($_POST["filename"] ?? "")));
    $type = preg_replace('/[^a-z]/','',trim((string)($_POST["type"] ?? "")));
    $ids=array();
    foreach(explode(",",(string)($_POST["agent_ids"] ?? "")) as $raw){
        $n=intval(trim($raw));
        if($n>0){$ids[$n]=true;}
    }
    if($filename=="" || count($ids)==0){echo $tpl->_ENGINE_parse_body("{pshu_nothing}");return false;}
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/articarepos/push", array(
        "filename" => $filename,"type" => $type,"agent_ids" => implode(",",array_keys($ids)))));
    if(!is_object($json)){echo "Artica REST API: no answer";return false;}
    if(isset($json->Status) && !$json->Status){echo (string)($json->Error ?? "error");return false;}
    return admin_tracks("Push update $filename to ".count($ids)." Meta nodes (".implode(",",array_keys($ids)).")");
}

function push_update_groups_post():bool{
    $tpl = new template_admin();
    if(!meta_can_push()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");return false;}
    $filename = preg_replace('/[^A-Za-z0-9_.+~-]/','',trim((string)($_POST["filename"] ?? "")));
    $type = preg_replace('/[^a-z]/','',trim((string)($_POST["type"] ?? "")));
    $gids=array();
    foreach(explode(",",(string)($_POST["group_ids"] ?? "")) as $raw){
        $n=intval(trim($raw));
        if($n>0){$gids[$n]=true;}
    }
    if($filename=="" || count($gids)==0){echo $tpl->_ENGINE_parse_body("{pshu_nothing}");return false;}
    // Chaque groupe est deplie en ses noeuds cibles (en ligne, Artica, non bloques) : un noeud de
    // deux groupes n'est envoye qu'une fois. Un groupe "Deny from updates" est refuse.
    $glist=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups"));
    $denied=array();
    if(is_array($glist)){ foreach($glist as $g){ if(!empty($g->blacklisted)){$denied[intval($g->id ?? 0)]=true;} } }
    // Un envoi par groupe, nomme d'apres lui (group_id) : la file d'activite dit quel groupe a
    // recu quoi. Un noeud present dans plusieurs groupes est attribue au premier seulement.
    $ids=array();$plan=array();
    foreach(array_keys($gids) as $gid){
        if(isset($denied[$gid])){continue;}
        $t=pshu_group_targets($gid);
        $list=array();
        foreach($t["on"] as $aid){
            if(isset($ids[$aid])){continue;}
            $ids[$aid]=true;$list[]=$aid;
        }
        if(count($list)>0){$plan[$gid]=$list;}
    }
    if(count($ids)==0){echo $tpl->_ENGINE_parse_body("{pshu_no_target}");return false;}
    $done=array();
    foreach($plan as $gid=>$list){
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/articarepos/push", array(
            "filename"=>$filename,"type"=>$type,"agent_ids"=>implode(",",$list),"group_id"=>$gid)));
        $err="";
        if(!is_object($json)){$err="Artica REST API: no answer";}
        elseif(isset($json->Status) && !$json->Status){$err=(string)($json->Error ?? "error");}
        if($err!=""){
            if(count($done)>0){admin_tracks("Push update $filename started for groups ".implode(",",$done)." then failed on group $gid: $err");}
            echo $err;return false;
        }
        $done[]=$gid;
    }
    return admin_tracks("Push update $filename to ".count($ids)." Meta nodes via groups ".implode(",",$done));
}

// ---------- Activite des envois ----------

function push_update_table():bool{
    return pshu_activity("/articarepos/push/status",30);
}





// ---------- JS (hors moteur de traduction) ----------

function start_sync():bool {
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    if(!artrepo_can_write()){$tpl->js_error("{ERROR_NO_PRIVS}");return false;}
    $cur=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/sync/status"));
    if(is_object($cur) && !empty($cur->running)){
        $tpl->js_error("{artrepo_sync_already}");
        return false;
    }
    $ArticaReposDebianVersions=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaReposDebianVersions");
    if(strlen($ArticaReposDebianVersions)<2){
        $ArticaReposDebianVersions="10,12,13";
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/articarepos/sync", array(
        "debian_versions" => $ArticaReposDebianVersions
    )),true);
    if(!is_array($json) || !isset($json["status"]) || $json["status"]!="started"){
        $err="{artrepo_sync_failed}";
        if(is_array($json) && !empty($json["error"])){$err=(string)$json["error"];}
        $tpl->js_error($err);
        return false;
    }
    $tpl->js_ok("{artrepo_sync_started}","if(typeof ArtRepoRefresh==='function'){ArtRepoRefresh();}");
    return admin_tracks("Start manually Meta repository sync for Debian $ArticaReposDebianVersions");
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


function operation_details():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=$_GET["operation-details"];
    return $tpl->js_dialog10("{operation_details}","$page?operation-details-popup=$id",800);
}

// Rapport d'une diffusion de paquet Artica : fenetre commune opd_* (class.artica.push.ui.inc).
// Le demon garde ces rapports 30 min apres la fin (PushOperationTTL).
function operation_details_popup():bool{
    return opd_popup($_GET["operation-details-popup"],"/articarepos/push/status",30);
}
