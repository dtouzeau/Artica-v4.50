<?php
// fw.artica.backup.categories.php — "Categories" tab of fw.artica.backup.php: backups of the
// web-filtering categories (lists of websites the proxy allows or blocks).
//
// Stitch redesign 2026-11-22 (stitch_artica_backup_tab_redesign). REAL DATA ONLY:
//   /home/artica/SQLITE/categoriesbackup.db, table backup (ID, sourcepath, created, filesize,
//   events = base64 log of the run), written by articarest CategoriesBackup.BackupCategories():
//   pg_dump of proxydb -> /home/artica/backup-categories/<unix>.tar.gz, run every night at 01:45
//   (EachDay1h45, skipped while another categories operation holds the lock) and on demand.
//   After each successful backup only the BKCAT_MAX most recent are kept (articarest
//   CategoriesBackup.MaxArchives): the older ones are deleted, file and row.
//   GET /categories/backup                 back up now (progress backup_categories.progress)
//   GET /category/backup/restoreid/{id}    pg_restore --clean: REPLACES the current lists
//   GET /category/backup/delete/{id}       removes the file and the row
// The host id (table-categories-list) and ?table=yes are kept: fw.ufdb.categories.restore.php
// reloads them after an import. One model (bkcat_model) feeds every widget.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// Mirrors articarest CategoriesBackup.MaxArchives: change both together. Defined BEFORE the
// dispatch below: a ?table=yes request exits there and would never reach a later define().
define("BKCAT_MAX",5);
$users=new usersMenus();
if(!$users->AsDansGuardianAdministrator && !$users->AsSquidAdministrator && !$users->AsSystemAdministrator){exit();}
if(isset($_GET["download"])){download();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["backup-js"])){bkcat_backup_js();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["bkcat-delete"])){bkcat_delete();exit;}
if(isset($_GET["restore-js"])){restore_js();exit;}
if(isset($_POST["bkcat-restore"])){bkcat_restore_save();exit;}
table_start();

function table_start(){
    $page=CurrentPageName();
    echo bkcat_style()."<div id='table-categories-list' class='bkcat' style='margin-top:10px' aria-live='polite'></div>"
        .bkcat_js($page)."<script>LoadAjax('table-categories-list','$page?table=yes');</script>";
}

// Changing categories is web filtering's job: the import dialog already asks for this flag.
function bkcat_writer():bool{
    $users=new usersMenus();
    return (bool)($users->AsDansGuardianAdministrator || $users->AsSystemAdministrator);
}

function bkcat_db(){
    return new lib_sqlite("/home/artica/SQLITE/categoriesbackup.db");
}

function bkcat_h($s):string{
    return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
}

function bkcat_tr(string $tok,array $repl=array()):string{
    $tpl=new template_admin();
    $t=$tpl->_ENGINE_parse_body("{".$tok."}");
    foreach($repl as $k=>$v){$t=str_replace($k,(string)$v,$t);}
    return $t;
}

function bkcat_ago(int $ts):string{
    if($ts<=0){return "&mdash;";}
    $d=max(1,time()-$ts);
    if($d<3600){return bkcat_tr("bkcat_ago_min",array("%s"=>max(1,intval($d/60))));}
    if($d<86400){return bkcat_tr("bkcat_ago_hour",array("%s"=>intval($d/3600)));}
    return bkcat_tr("bkcat_ago_day",array("%s"=>intval($d/86400)));
}

function bkcat_size(float $bytes):string{
    if($bytes<=0){return "&mdash;";}
    return preg_replace('/(\d)\s*([KMGTP]?B)$/','$1 $2',trim(FormatBytes($bytes/1024,true)));
}

// Dates without a decimal point: js_dialog_confirm_action() turns every "." into a line break.
function bkcat_date(int $ts):string{
    return $ts>0?date("Y-m-d H:i",$ts):"&mdash;";
}

// ── Model ─────────────────────────────────────────────────────────────────

function bkcat_model():array{
    $m=array("rows"=>array(),"error"=>"","total_size"=>0.0,"latest"=>0,"oldest"=>0,"writer"=>bkcat_writer());
    $q=bkcat_db();
    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `backup` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`sourcepath` TEXT UNIQUE,`created` INTEGER NOT NULL DEFAULT 0,`filesize` INTEGER NOT NULL DEFAULT 0,`events` TEXT )");
    $res=$q->QUERY_SQL("SELECT ID,sourcepath,created,filesize,events FROM backup ORDER BY created DESC, ID DESC");
    if(!$q->ok){$m["error"]=(string)$q->mysql_error;return $m;}
    foreach((is_array($res)?$res:array()) as $l){
        if(!is_array($l)){continue;}
        $events=trim((string)base64_decode((string)($l["events"] ?? "")));
        $r=array("id"=>intval($l["ID"] ?? 0),"path"=>(string)($l["sourcepath"] ?? ""),"ts"=>intval($l["created"] ?? 0),
            "size"=>floatval($l["filesize"] ?? 0),"events"=>$events);
        $m["rows"][]=$r;
        $m["total_size"]+=$r["size"];
        if($r["ts"]>$m["latest"]){$m["latest"]=$r["ts"];}
        if($r["ts"]>0 && ($m["oldest"]==0 || $r["ts"]<$m["oldest"])){$m["oldest"]=$r["ts"];}
    }
    return $m;
}

// The nightly task runs at 01:45: the next run is today's 01:45 if not passed yet, else tomorrow's.
function bkcat_next_run():int{
    $t=strtotime("today 01:45");
    return $t>time()?$t:strtotime("tomorrow 01:45");
}

// ── Host ──────────────────────────────────────────────────────────────────

function table(){
    $tpl=new template_admin();
    $m=bkcat_model();
    $h=array();
    $h[]=bkcat_banner($m);
    if($m["error"]!=""){
        $h[]="<div class='bkcat-alert' role='alert'><i class='fas fa-circle-exclamation' aria-hidden='true'></i><span><b>{bkcat_unreadable}</b> ".bkcat_h($m["error"])."</span></div>";
    }elseif(count($m["rows"])==0){
        $h[]=bkcat_empty($m);
    }else{
        $h[]="<div class='bkcat-kpis'>".bkcat_kpi_latest($m).bkcat_kpi_count($m).bkcat_kpi_space($m)."</div>";
        $h[]=bkcat_list($m);
    }
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{backup_categories}";
    $TINY_ARRAY["ICO"]="fa fa-archive";
    $TINY_ARRAY["EXPL"]="{bkcat_header_explain}";
    $TINY_ARRAY["URL"]="snapshots";
    $TINY_ARRAY["BUTTONS"]="";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h))."<script>NoSpinner();$jstiny if(window.BkcatInit){BkcatInit();}</script>";
}

// ── Widgets ───────────────────────────────────────────────────────────────

function bkcat_backup_button(array $m):string{
    if(!$m["writer"]){return "";}
    $page=CurrentPageName();
    $help="{bkcat_backup_now_help}";
    // The consequence before the action: at the limit, a new backup deletes the oldest one.
    $n=count($m["rows"]);
    if($n>=BKCAT_MAX){
        $help.="<br><b>".bkcat_tr("bkcat_replaces_oldest",array("%s"=>bkcat_date($m["oldest"])))."</b>";
    }
    return "<div class='bkcat-cta'><button type='button' class='bkcat-btn bkcat-btn-lg' onclick=\"Loadjs('$page?backup-js=yes')\"><i class='fas fa-floppy-disk' aria-hidden='true'></i>{bkcat_backup_now}</button>"
        ."<span class='bkcat-help'>$help</span></div>";
}

function bkcat_banner(array $m):string{
    $chips=array(array("fas fa-moon","{bkcat_chip_nightly}"),array("fas fa-layer-group",bkcat_tr("bkcat_chip_keep",array("%s"=>BKCAT_MAX))),array("fas fa-download","{bkcat_chip_download}"));
    $c="";
    foreach($chips as $ch){$c.="<span class='bkcat-chip'><i class='{$ch[0]}' aria-hidden='true'></i>{$ch[1]}</span>";}
    return "<section class='bkcat-banner'><div class='bkcat-banner-ico' aria-hidden='true'><i class='fas fa-box-archive'></i></div>"
        ."<div class='bkcat-banner-txt'><h2 class='bkcat-banner-t'>{bkcat_banner_title}</h2><p class='bkcat-banner-p'>{bkcat_banner_text}</p>"
        ."<div class='bkcat-chips'>$c</div><p class='bkcat-tip'><i class='fas fa-lightbulb' aria-hidden='true'></i> {bkcat_tip}</p></div>"
        .bkcat_backup_button($m)."</section>";
}

function bkcat_empty(array $m):string{
    $btn=$m["writer"]?"<button type='button' class='bkcat-btn' onclick=\"Loadjs('".CurrentPageName()."?backup-js=yes')\"><i class='fas fa-floppy-disk' aria-hidden='true'></i>{bkcat_backup_now}</button>":"";
    $imp=$m["writer"]?"<button type='button' class='bkcat-btn-q' onclick=\"Loadjs('fw.ufdb.categories.restore.php')\"><i class='fas fa-file-import' aria-hidden='true'></i>{bkcat_import}</button>":"";
    return "<section class='bkcat-card bkcat-empty'><div class='bkcat-empty-ico' aria-hidden='true'><i class='fas fa-box-archive'></i></div>"
        ."<h3 class='bkcat-card-t'>{bkcat_empty_title}</h3><p class='bkcat-muted'>{bkcat_empty_text}</p><div class='bkcat-empty-act'>$btn$imp</div></section>";
}

function bkcat_kpi(string $icon,string $label,string $right,string $body,string $foot,string $extra=""):string{
    return "<div class='bkcat-card bkcat-kpi$extra'><div class='bkcat-kpi-head'><span class='bkcat-kpi-l'><i class='$icon' aria-hidden='true'></i>$label</span>$right</div>"
        ."<div class='bkcat-kpi-body'>$body</div><div class='bkcat-kpi-foot'>$foot</div></div>";
}

function bkcat_kpi_latest(array $m):string{
    $tpl=new template_admin();
    $old=(time()-$m["latest"])>2*86400;
    $pill=$old?"<span class='bkcat-pill bkcat-pill-warn'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i>{bkcat_outdated}</span>"
        :"<span class='bkcat-pill bkcat-pill-ok'><i class='fas fa-circle-check' aria-hidden='true'></i>{bkcat_uptodate}</span>";
    $body="<span class='bkcat-big' title='".bkcat_h($tpl->time_to_date($m["latest"],true))."'>".bkcat_ago($m["latest"])."</span><span class='bkcat-sub bkcat-mono'>".bkcat_date($m["latest"])."</span>";
    if($old){$body.="<p class='bkcat-warn-t'>".bkcat_tr("bkcat_outdated_text",array("%s"=>intval((time()-$m["latest"])/86400)))."</p>";}
    $foot="<span><i class='fas fa-calendar-check' aria-hidden='true'></i> ".bkcat_tr("bkcat_next_run",array("%s"=>date("Y-m-d H:i",bkcat_next_run())))."</span>";
    return bkcat_kpi("fas fa-clock-rotate-left","{bkcat_kpi_latest}",$pill,$body,$foot,$old?" bkcat-kpi-warn":"");
}

function bkcat_kpi_count(array $m):string{
    $n=count($m["rows"]);
    $body="<span class='bkcat-big'>$n</span><span class='bkcat-sub'>".bkcat_tr("bkcat_of_max",array("%s"=>BKCAT_MAX))."</span>";
    $foot="<span><i class='fas fa-clock' aria-hidden='true'></i> ".bkcat_tr("bkcat_oldest",array("%s"=>bkcat_date($m["oldest"])))."</span>";
    return bkcat_kpi("fas fa-layer-group","{bkcat_kpi_count}","",$body,$foot);
}

function bkcat_kpi_space(array $m):string{
    $body="<span class='bkcat-big'>".bkcat_size($m["total_size"])."</span><span class='bkcat-sub'>{bkcat_on_disk}</span>";
    $foot="<span class='bkcat-help'>".bkcat_tr("bkcat_rotation_help",array("%s"=>BKCAT_MAX))."</span>";
    return bkcat_kpi("fas fa-hard-drive","{bkcat_kpi_space}","",$body,$foot);
}

function bkcat_list(array $m):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $rows="";
    $first=true;
    foreach($m["rows"] as $r){
        $name=bkcat_h(basename($r["path"]));
        $latest=$first?"<span class='bkcat-pill bkcat-pill-accent'><i class='fas fa-star' aria-hidden='true'></i>{bkcat_latest}</span>":"";
        $first=false;
        $details="";
        if($r["events"]!=""){
            $details="<button type='button' class='bkcat-link' aria-expanded='false' onclick='BkcatDetails(this)'>{bkcat_details}<i class='fas fa-chevron-down' aria-hidden='true'></i></button>"
                ."<pre class='bkcat-log' hidden>".bkcat_h($r["events"])."</pre>";
        }
        $acts="<button type='button' class='bkcat-ico' aria-label=\"{bkcat_download}\" title=\"{bkcat_download}\" onclick=\"s_PopUp('$page?download={$r["id"]}',550,250)\"><i class='fas fa-download' aria-hidden='true'></i></button>";
        if($m["writer"]){
            $acts.="<button type='button' class='bkcat-ico' aria-label=\"{bkcat_restore}\" title=\"{bkcat_restore}\" onclick=\"Loadjs('$page?restore-js={$r["id"]}')\"><i class='fas fa-rotate-left' aria-hidden='true'></i></button>"
                ."<button type='button' class='bkcat-ico bkcat-ico-del' aria-label=\"{bkcat_delete}\" title=\"{bkcat_delete}\" onclick=\"Loadjs('$page?delete-js={$r["id"]}')\"><i class='fas fa-trash' aria-hidden='true'></i></button>";
        }
        $rows.="<tr><td class='bkcat-when'><b>".bkcat_date($r["ts"])."</b><small title='".bkcat_h($tpl->time_to_date($r["ts"],true))."'><i class='fas fa-clock' aria-hidden='true'></i> ".bkcat_ago($r["ts"])."</small></td>"
            ."<td class='bkcat-arch'><div class='bkcat-arch-l'><span class='bkcat-file bkcat-mono' title='".bkcat_h($r["path"])."'>$name</span>$latest</div>$details</td>"
            ."<td class='bkcat-size'>".bkcat_size($r["size"])."</td><td class='bkcat-acts'>$acts</td></tr>";
    }
    $imp=$m["writer"]?"<button type='button' class='bkcat-btn-q' onclick=\"Loadjs('fw.ufdb.categories.restore.php')\" title=\"{bkcat_import_help}\"><i class='fas fa-file-import' aria-hidden='true'></i>{bkcat_import}</button>":"";
    $n=count($m["rows"]);
    return "<section class='bkcat-card bkcat-list'><div class='bkcat-list-head'><div class='bkcat-list-t'><h3 class='bkcat-card-t'>{bkcat_backups}</h3>"
        ."<span class='bkcat-pill bkcat-pill-accent'>".bkcat_tr("bkcat_archives",array("%s"=>$n))."</span><span class='bkcat-help'>{bkcat_list_help}</span></div>$imp</div>"
        ."<div class='bkcat-scroll'><table class='bkcat-tbl'><thead><tr><th>{bkcat_created}</th><th>{bkcat_archive}</th><th>{bkcat_size_h}</th><th class='bkcat-r'>{bkcat_actions}</th></tr></thead>"
        ."<tbody id='bkcat-rows'>$rows</tbody></table></div>"
        ."<div class='bkcat-foot'><span id='bkcat-showing'></span><nav class='bkcat-pg' id='bkcat-pager' aria-label='{bkcat_pages}'></nav></div></section>";
}

// ── Actions ───────────────────────────────────────────────────────────────

function bkcat_entry(int $id):array{
    $q=bkcat_db();
    $l=$q->mysqli_fetch_array("SELECT ID,sourcepath,created,filesize FROM backup WHERE ID=$id");
    if(!is_array($l) || !isset($l["ID"])){return array();}
    return array("id"=>$id,"path"=>(string)$l["sourcepath"],"ts"=>intval($l["created"]),"size"=>floatval($l["filesize"]));
}

function bkcat_after_js():string{
    return "LoadAjaxSilent('table-categories-list','".CurrentPageName()."?table=yes');";
}

function bkcat_backup_js(){
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    if(!bkcat_writer()){$tpl->js_error("{ERROR_NO_PRIVS2}");return;}
    echo $tpl->framework_buildjs("/categories/backup","backup_categories.progress","backup_categories.log",
        "progress-snapshot-restart",bkcat_after_js());
}

// Restore replaces the current lists: the consequence is said before, with the archive's date.
function restore_js(){
    $tpl=new template_admin();
    $id=intval($_GET["restore-js"]);
    if(!bkcat_writer()){header("content-type: application/x-javascript");$tpl->js_error("{ERROR_NO_PRIVS2}");return;}
    $e=bkcat_entry($id);
    if(count($e)==0){header("content-type: application/x-javascript");$tpl->js_error("{bkcat_gone}");return;}
    $js=$tpl->framework_buildjs("/category/backup/restoreid/$id","backup_categories.progress","backup_categories.progress.log",
        "progress-snapshot-restart",bkcat_after_js());
    $text=bkcat_tr("bkcat_restore_confirm",array("%s"=>bkcat_date($e["ts"])));
    $tpl->js_dialog_confirm_action($text,"bkcat-restore",strval($id),$js);
}

// The confirmation posts here first (empty answer = go), then the progress runner starts the
// restore; fw.progress.php logs the launched route as well.
function bkcat_restore_save(){
    $tpl=new template_admin();
    if(!bkcat_writer()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return;}
    $id=intval($_POST["bkcat-restore"]);
    $e=bkcat_entry($id);
    if(count($e)==0){echo $tpl->_ENGINE_parse_body("{bkcat_gone}");return;}
    admin_tracks("Restore the web categories from the backup of ".date("Y-m-d H:i",$e["ts"])." (#$id)");
}

function delete_js(){
    $tpl=new template_admin();
    $id=intval($_GET["delete-js"]);
    header("content-type: application/x-javascript");
    if(!bkcat_writer()){$tpl->js_error("{ERROR_NO_PRIVS2}");return;}
    $e=bkcat_entry($id);
    if(count($e)==0){$tpl->js_error("{bkcat_gone}");return;}
    $text=bkcat_tr("bkcat_delete_confirm",array("%d"=>bkcat_date($e["ts"]),"%s"=>bkcat_size($e["size"])));
    $tpl->js_confirm_delete($text,"bkcat-delete",strval($id),bkcat_after_js(),true);
}

// Echoes nothing on success, the error otherwise.
function bkcat_delete(){
    $tpl=new template_admin();
    if(!bkcat_writer()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return;}
    $id=intval($_POST["bkcat-delete"]);
    if($id<1){return;}
    $e=bkcat_entry($id);
    $j=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/category/backup/delete/$id"),true);
    if(!is_array($j)){echo $tpl->_ENGINE_parse_body("{bkcat_err_unreachable}");return;}
    if(isset($j["Status"]) && !$j["Status"]){echo bkcat_h((string)($j["Error"] ?? "error"));return;}
    admin_tracks("Delete the web categories backup ".(count($e)>0?date("Y-m-d H:i",$e["ts"]):"")." (#$id)");
}

// Only a file inside the backup directory is ever sent, whatever the row says.
function download(){
    $id=intval($_GET["download"]);
    $e=bkcat_entry($id);
    $base=realpath("/home/artica/backup-categories");
    $real=count($e)>0?realpath($e["path"]):false;
    if($base===false || $real===false || strpos($real,$base."/")!==0 || !is_file($real)){
        header("HTTP/1.1 404 Not Found");
        return;
    }
    if(empty($GLOBALS["VERBOSE"])){
        header('Content-type: application/x-tar');
        header('Content-Transfer-Encoding: binary');
        header("Content-Disposition: attachment; filename=\"".basename($real)."\"");
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        header("Content-Length: ".filesize($real));
        if(ob_get_level()>0){ob_clean();}
        flush();
    }
    readfile($real);
    admin_tracks("Download the web categories backup ".basename($real));
}

// ── JS / CSS ──────────────────────────────────────────────────────────────

function bkcat_js(string $page):string{
    $tpl=new template_admin();
    $showing=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{bkcat_showing}"),ENT_QUOTES,"UTF-8"));
    $prev=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{bkcat_prev}"),ENT_QUOTES,"UTF-8"));
    $next=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{bkcat_next}"),ENT_QUOTES,"UTF-8"));
    return "<script>
window.BkcatState={p:1,per:15};
function BkcatDetails(b){
    var pre=b.nextElementSibling; if(!pre){return;}
    var o=b.getAttribute('aria-expanded')==='true';
    b.setAttribute('aria-expanded',o?'false':'true'); if(o){pre.setAttribute('hidden','');}else{pre.removeAttribute('hidden');}
}
function BkcatPage(p){ BkcatState.p=p; BkcatRender(); }
function BkcatRender(){
    var rows=document.querySelectorAll('#bkcat-rows tr'); if(!rows.length){return;}
    var n=rows.length, pages=Math.max(1,Math.ceil(n/BkcatState.per)), i;
    if(BkcatState.p>pages){BkcatState.p=pages;}
    var a=(BkcatState.p-1)*BkcatState.per, b=Math.min(n,a+BkcatState.per);
    for(i=0;i<n;i++){ rows[i].style.display=(i>=a&&i<b)?'':'none'; }
    var s=document.getElementById('bkcat-showing'); if(s){ s.textContent=$showing.replace('%a',b-a).replace('%b',n); }
    var pg=document.getElementById('bkcat-pager'); if(!pg){return;}
    var h=''; if(pages>1){
        h+='<button type=\"button\" '+(BkcatState.p==1?'disabled':'onclick=\"BkcatPage('+(BkcatState.p-1)+')\"')+'>'+$prev+'</button>';
        for(i=1;i<=pages;i++){ if(i==1||i==pages||Math.abs(i-BkcatState.p)<=1){ h+='<button type=\"button\" class=\"'+(i==BkcatState.p?'on':'')+'\" '+(i==BkcatState.p?'aria-current=\"page\"':'')+' onclick=\"BkcatPage('+i+')\">'+i+'</button>'; } else if(Math.abs(i-BkcatState.p)==2){ h+='<span>&hellip;</span>'; } }
        h+='<button type=\"button\" '+(BkcatState.p==pages?'disabled':'onclick=\"BkcatPage('+(BkcatState.p+1)+')\"')+'>'+$next+'</button>';
    }
    pg.innerHTML=h;
}
function BkcatInit(){ BkcatState.p=1; BkcatRender(); }
</script>";
}

function bkcat_style():string{
    if(isset($GLOBALS["BKCAT_STYLE"])){return "";}
    $GLOBALS["BKCAT_STYLE"]=true;
    $accent="#005447";$soft="#e6eeed";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.bkcat{display:flex;flex-direction:column;gap:20px;color:#0f172a;font-size:14px}
.bkcat *{box-sizing:border-box}
.bkcat h2,.bkcat h3,.bkcat p,.bkcat pre{margin:0}
.bkcat-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.bkcat-card-t{font-size:17px;font-weight:600}
.bkcat-muted{color:#475569;font-size:14px;line-height:1.55;margin-top:6px!important}
.bkcat-help{display:block;color:#64748b;font-size:13px;line-height:1.45}
.bkcat-mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace}
.bkcat-banner{display:flex;gap:18px;align-items:flex-start;background:$soft;border:1px solid #bfd4d1;border-radius:16px;padding:24px}
.bkcat-banner-ico{flex:0 0 48px;height:48px;border-radius:50%;background:$accent;color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px}
.bkcat-banner-txt{flex:1;min-width:0}
.bkcat-banner-t{font-size:20px;font-weight:600;line-height:1.3}
.bkcat-banner-p{font-size:14px;line-height:1.55;color:#334155;margin-top:6px!important;max-width:820px}
.bkcat-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
.bkcat-chip{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#334155;background:#fff;border:1px solid #bfd4d1;border-radius:999px;padding:4px 12px}
.bkcat-chip i,.bkcat-chip svg{color:$accent}
.bkcat-tip{font-size:13px;color:#334155;margin-top:12px!important}
.bkcat-tip i,.bkcat-tip svg{color:#d97706}
.bkcat-cta{flex:0 0 240px;display:flex;flex-direction:column;align-items:flex-end;gap:8px;text-align:right}
.bkcat-btn{display:inline-flex;align-items:center;gap:8px;background:$accent;color:#fff;border:0;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap}
.bkcat-btn:hover{background:#003d33}
.bkcat-btn-lg{padding:12px 22px;font-size:15px}
.bkcat-btn-q{display:inline-flex;align-items:center;gap:8px;background:#fff;color:#0f172a;border:1px solid #cbd5e1;border-radius:8px;padding:8px 14px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap}
.bkcat-btn-q:hover{border-color:$accent;color:$accent}
.bkcat-btn-q i,.bkcat-btn-q svg{color:$accent}
.bkcat-link{display:inline-flex;align-items:center;gap:6px;background:none;border:0;color:$accent;font-weight:600;font-size:13px;cursor:pointer;padding:4px 0;margin-top:6px}
.bkcat-link[aria-expanded=true] svg,.bkcat-link[aria-expanded=true] i{transform:rotate(180deg)}
.bkcat-alert{display:flex;gap:10px;align-items:flex-start;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;font-size:13px;color:#7f1d1d;word-break:break-word}
.bkcat-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}
.bkcat-kpi{padding:18px 20px;display:flex;flex-direction:column;gap:10px}
.bkcat-kpi-warn{border-color:#fde68a;background:#fffdf5}
.bkcat-kpi-head{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
.bkcat-kpi-l{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:500;color:#334155}
.bkcat-kpi-l i,.bkcat-kpi-l svg{color:$accent}
.bkcat-kpi-body{flex:1;display:flex;align-items:baseline;flex-wrap:wrap;gap:4px 10px}
.bkcat-big{font-size:28px;font-weight:600;line-height:34px;font-variant-numeric:tabular-nums}
.bkcat-sub{font-size:13px;color:#64748b}
.bkcat-warn-t{flex-basis:100%;font-size:13px;color:#92400e;line-height:1.5}
.bkcat-kpi-foot{font-size:13px;color:#475569;border-top:1px solid #f1f5f9;padding-top:10px}
.bkcat-kpi-foot i,.bkcat-kpi-foot svg{color:#64748b}
.bkcat-pill{display:inline-flex;align-items:center;gap:5px;font-size:13px;font-weight:500;border-radius:999px;padding:2px 10px;white-space:nowrap;background:#f1f5f9;color:#334155}
.bkcat-pill-ok{background:#dcfce7;color:#166534}
.bkcat-pill-warn{background:#fef3c7;color:#92400e}
.bkcat-pill-accent{background:$soft;color:$accent;border:1px solid #bfd4d1}
.bkcat-list{overflow:hidden}
.bkcat-list-head{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;padding:18px 20px}
.bkcat-list-t{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.bkcat-scroll{overflow-x:auto}
.bkcat-tbl{width:100%;border-collapse:collapse}
.bkcat-tbl th{font-size:13px;font-weight:600;color:#334155;text-align:left;padding:10px 20px;background:#f8fafc;border-top:1px solid #eef2f6;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.bkcat-tbl td{font-size:14px;padding:14px 20px;border-bottom:1px solid #f1f5f9;vertical-align:top}
.bkcat-r{text-align:right!important}
.bkcat-when{white-space:nowrap;width:1%}
.bkcat-when b{display:block;font-weight:600}
.bkcat-when small{display:block;font-size:13px;color:#64748b;margin-top:2px}
.bkcat-arch{width:100%}
.bkcat-arch-l{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.bkcat-file{font-size:13px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:3px 9px}
.bkcat-log{margin-top:8px!important;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px;font-size:13px;color:#334155;white-space:pre-wrap;word-break:break-word;max-height:220px;overflow:auto}
.bkcat-size{white-space:nowrap;font-weight:600;font-variant-numeric:tabular-nums}
.bkcat-acts{white-space:nowrap;text-align:right;width:1%}
.bkcat-ico{width:34px;height:34px;border-radius:8px;border:1px solid transparent;background:none;color:#475569;cursor:pointer;font-size:15px}
.bkcat-ico:hover{background:#f1f5f9;border-color:#e2e8f0;color:$accent}
.bkcat-ico-del{color:#b91c1c}
.bkcat-ico-del:hover{background:#fef2f2;border-color:#fecaca;color:#b91c1c}
.bkcat-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 20px;font-size:13px;color:#64748b}
.bkcat-pg{display:flex;gap:6px;align-items:center}
.bkcat-pg button{min-width:34px;height:34px;border:1px solid #e2e8f0;background:#fff;border-radius:8px;font-size:13px;color:#334155;cursor:pointer;padding:0 10px}
.bkcat-pg button.on{background:$accent;border-color:$accent;color:#fff}
.bkcat-pg button[disabled]{color:#94a3b8;cursor:not-allowed}
.bkcat-empty{text-align:center;padding:40px 24px;display:flex;flex-direction:column;align-items:center;gap:6px}
.bkcat-empty-ico{width:64px;height:64px;border-radius:50%;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:26px;margin-bottom:8px}
.bkcat-empty .bkcat-muted{max-width:560px}
.bkcat-empty-act{display:flex;gap:10px;margin-top:12px;flex-wrap:wrap;justify-content:center}
.bkcat-btn:focus-visible,.bkcat-btn-q:focus-visible,.bkcat-link:focus-visible,.bkcat-ico:focus-visible,.bkcat-pg button:focus-visible{outline:2px solid $accent;outline-offset:2px}
@media(max-width:1200px){.bkcat-banner{flex-wrap:wrap}.bkcat-cta{flex-basis:100%;align-items:flex-start;text-align:left}}
@media (prefers-reduced-motion:no-preference){.bkcat-btn,.bkcat-ico,.bkcat-btn-q{transition:all .15s}}
</style>";
}
