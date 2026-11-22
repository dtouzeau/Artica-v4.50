<?php
// fw.databases.errors.php — SQLite requests the web console could not run.
//
// Stitch redesign 2026-11-22 (stitch_artica_sqlite_error_console). REAL DATA ONLY:
//   /home/artica/SQLITE/system_events.db, table sql_errors (ID, zdate, subject, error b64,
//   sql b64, database, debug b64 = serialized PHP trace). Written by lib_sqlite::SQLITE_ERROR()
//   as a JSON drop file in ressources/logs/sql_errors/, inserted later with bound parameters by
//   articarest (notifs.checkSQLErrors, via ScanDir), purged after SystemEventsRetentionTime days
//   (default 7, minimum 2 — notifs.CleanTables()). Every counter is computed over the whole
//   table; only the list is capped (250 newest).
// One model (dberr_model) feeds every widget; one widget = one function.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["build"])){dberr_build();exit;}
if(isset($_GET["dberr-host"])){dberr_host();exit;}
if(isset($_GET["zoomid"])){zoom_js();exit;}
if(isset($_GET["zoom-popup"])){zoom_popup();exit;}
if(isset($_GET["delete-js"])){dberr_delete_js();exit;}
if(isset($_POST["dberr-delete"])){dberr_delete();exit;}
page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{sql_errors}","fad fa-bug","{dberr_header_explain}","$page?build=yes",
        "sql-errors","progress-firehol-restart",false,"dberr-build");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{sql_errors}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

function dberr_db(){
    return new lib_sqlite("/home/artica/SQLITE/system_events.db");
}

function dberr_admin():bool{
    $users=new usersMenus();
    return (bool)$users->AsSystemAdministrator;
}

function dberr_h($s):string{
    return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
}

// Token resolved first, then its placeholders replaced.
function dberr_tr(string $tok,array $repl=array()):string{
    $tpl=new template_admin();
    $t=$tpl->_ENGINE_parse_body("{".$tok."}");
    foreach($repl as $k=>$v){$t=str_replace($k,(string)$v,$t);}
    return $t;
}

function dberr_ago(int $ts):string{
    if($ts<=0){return "&mdash;";}
    $d=max(1,time()-$ts);
    if($d<60){return "{dberr_ago_now}";}
    if($d<3600){return dberr_tr("dberr_ago_min",array("%s"=>intval($d/60)));}
    if($d<86400){return dberr_tr("dberr_ago_hour",array("%s"=>intval($d/3600)));}
    return dberr_tr("dberr_ago_day",array("%s"=>intval($d/86400)));
}

// "/home/artica/SQLITE/acls.db" -> "acls"
function dberr_dbname(string $path):string{
    $b=basename(trim($path));
    $b=preg_replace('/\.(db|sqlite3?)$/i','',$b);
    return $b!=""?$b:"?";
}

function dberr_retention():int{
    $d=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SystemEventsRetentionTime"));
    if($d==0){$d=7;}
    if($d<2){$d=2;}
    return $d;
}

// ── Model ─────────────────────────────────────────────────────────────────

function dberr_model(string $search,string $db):array{
    $MAX=250;
    $m=array("rows"=>array(),"max"=>$MAX,"search"=>$search,"db"=>"","error"=>"","total"=>0,"matching"=>0,
        "per_db"=>array(),"per_subject"=>array(),"days"=>array(),"retention"=>dberr_retention(),"admin"=>dberr_admin());
    $q=dberr_db();
    // Search applies to every figure; the database filter only to the list, so the chips keep
    // showing every database to switch to.
    $where="";$binds=array();
    if($search!=""){
        $where=" WHERE subject LIKE ?";
        $binds[]="%".str_replace(array("%","_"),array("",""),$search)."%";
    }
    // Counters over the WHOLE table, not over the listed rows.
    $res=$q->QUERY_SQL_PARAMS("SELECT database, COUNT(*) AS n FROM sql_errors$where GROUP BY database ORDER BY n DESC",$binds);
    if(!$q->ok){$m["error"]=(string)$q->mysql_error;return $m;}
    foreach((is_array($res)?$res:array()) as $l){
        $path=(string)($l["database"] ?? "");
        $name=dberr_dbname($path);
        // Two paths may share a file name: keep them apart by path, show the short name.
        $m["per_db"][$path]=array("name"=>$name,"n"=>intval($l["n"] ?? 0));
        $m["total"]+=intval($l["n"] ?? 0);
    }
    $res=$q->QUERY_SQL_PARAMS("SELECT lower(subject) AS s, COUNT(*) AS n FROM sql_errors$where GROUP BY lower(subject)",$binds);
    foreach((is_array($res)?$res:array()) as $l){$m["per_subject"][(string)($l["s"] ?? "")]=intval($l["n"] ?? 0);}
    $today=strtotime("today");
    for($i=6;$i>=0;$i--){$m["days"][date("Y-m-d",$today-$i*86400)]=0;}
    $since=$today-6*86400;
    $res=$q->QUERY_SQL_PARAMS("SELECT strftime('%Y-%m-%d',zdate,'unixepoch','localtime') AS d, COUNT(*) AS n FROM sql_errors WHERE zdate>=?"
        .($search!=""?" AND subject LIKE ?":"")." GROUP BY d",array_merge(array($since),$binds));
    foreach((is_array($res)?$res:array()) as $l){
        $d=(string)($l["d"] ?? "");
        if(isset($m["days"][$d])){$m["days"][$d]=intval($l["n"] ?? 0);}
    }
    // The list: newest first, at most $MAX rows, narrowed to one database when asked.
    if($db!="" && isset($m["per_db"][$db])){$m["db"]=$db;}
    $lw=$where;$lb=$binds;
    if($m["db"]!=""){$lw.=($lw==""?" WHERE":" AND")." database=?";$lb[]=$m["db"];}
    $m["matching"]=$m["db"]!=""?$m["per_db"][$m["db"]]["n"]:$m["total"];
    $res=$q->QUERY_SQL_PARAMS("SELECT ID,zdate,subject,database FROM sql_errors$lw ORDER BY zdate DESC LIMIT $MAX",$lb);
    if(!$q->ok){$m["error"]=(string)$q->mysql_error;}
    foreach((is_array($res)?$res:array()) as $l){
        if(!is_array($l)){continue;}
        $r=array("id"=>intval($l["ID"] ?? 0),"ts"=>intval($l["zdate"] ?? 0),"subject"=>trim((string)($l["subject"] ?? "")),
            "path"=>(string)($l["database"] ?? ""));
        $r["db"]=dberr_dbname($r["path"]);
        $m["rows"][]=$r;
    }
    return $m;
}

// "SQLite Error" is what lib_sqlite stores when the error is a list: say so instead.
function dberr_is_generic(string $subject):bool{
    return $subject=="" || strcasecmp($subject,"SQLite Error")==0;
}

// ── Hosts ─────────────────────────────────────────────────────────────────

function dberr_build(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html="<div id='dberr-host' class='dberr' aria-live='polite'></div>";
    $btns=array();
    $btns[]=array("DberrReload();",ico_refresh,"{refresh}");
    $TINY=array();
    $TINY["TITLE"]="{sql_errors}";
    $TINY["ICO"]="fad fa-bug";
    $TINY["EXPL"]="{dberr_header_explain}";
    $TINY["URL"]="sql-errors";
    $TINY["BUTTONS"]=$tpl->table_buttons($btns);
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
    echo dberr_style().$tpl->_ENGINE_parse_body($html).dberr_js($page)
        ."<script>window.DberrState={db:'',q:''};LoadAjax('dberr-host','$page?dberr-host=yes');$tiny</script>";
}

function dberr_host(){
    $tpl=new template_admin();
    $search=trim((string)($_GET["q"] ?? ""));
    $search=substr(preg_replace('/[\x00-\x1F]/','',$search),0,120);
    // The database filter is a path; dberr_model() keeps it only if it is one of the table's.
    $m=dberr_model($search,substr((string)($_GET["db"] ?? ""),0,512));
    $h=array();
    $h[]=dberr_banner($m);
    if($m["error"]!=""){
        $h[]="<div class='dberr-alert' role='alert'><i class='fas fa-circle-exclamation' aria-hidden='true'></i><span><b>{dberr_unreadable}</b> ".dberr_h($m["error"])."</span></div>";
    }
    if($m["total"]==0 && $search=="" && $m["error"]==""){
        $h[]=dberr_empty($m);
    }else{
        if($search==""){
            $h[]="<div class='dberr-kpis'>".dberr_kpi_count($m).dberr_kpi_db($m).dberr_kpi_latest($m)."</div>";
        }
        if($m["total"]>0){$h[]=dberr_filters($m);}
        $h[]=dberr_table($m);
    }
    echo $tpl->_ENGINE_parse_body(implode("\n",$h))
        ."<script>if(window.jQuery&&jQuery.fn.peity){jQuery('#dberr-host .dberr-spark').peity('bar',{fill:['#005447'],height:32,width:96,padding:0.2});}</script>";
}

// ── Widgets ───────────────────────────────────────────────────────────────

function dberr_banner(array $m):string{
    $chips=array(array("fas fa-eye","{dberr_chip_readonly}"),
        array("fas fa-clock",dberr_tr("dberr_chip_kept",array("%s"=>$m["retention"]))),
        array("fas fa-life-ring","{dberr_chip_support}"));
    $c="";
    foreach($chips as $ch){$c.="<span class='dberr-chip'><i class='{$ch[0]}' aria-hidden='true'></i>{$ch[1]}</span>";}
    return "<section class='dberr-banner'><div class='dberr-banner-ico' aria-hidden='true'><i class='fas fa-database'></i></div>"
        ."<div class='dberr-banner-txt'><h2 class='dberr-banner-t'>{dberr_banner_title}</h2><p class='dberr-banner-p'>{dberr_banner_text}</p>"
        ."<div class='dberr-chips'>$c</div></div></section>";
}

function dberr_empty(array $m):string{
    return "<section class='dberr-card dberr-empty'><div class='dberr-empty-ico' aria-hidden='true'><i class='fas fa-circle-check'></i></div>"
        ."<h3 class='dberr-card-t'>{dberr_empty_title}</h3><p class='dberr-muted'>".dberr_tr("dberr_empty_text",array("%s"=>$m["retention"]))."</p></section>";
}

function dberr_kpi(string $icon,string $tone,string $label,string $right,string $body,string $foot):string{
    return "<div class='dberr-card dberr-kpi'><div class='dberr-kpi-head'><span class='dberr-kpi-l'><i class='$icon dberr-tone-$tone' aria-hidden='true'></i>$label</span>$right</div>"
        ."<div class='dberr-kpi-body'>$body</div><div class='dberr-kpi-foot'>$foot</div></div>";
}

function dberr_kpi_count(array $m):string{
    $n=$m["total"];
    $spark="<span class='dberr-spark-wrap'><span class='dberr-help'>{dberr_last7}</span><span class='dberr-spark'>".implode(",",array_values($m["days"]))."</span></span>";
    $cap=$n>$m["max"]?"<b>".dberr_tr("dberr_capped",array("%s"=>$m["max"]))."</b>":"";
    $foot="<span>".dberr_tr("dberr_kept_days",array("%s"=>$m["retention"]))."</span>$cap";
    return dberr_kpi("fas fa-triangle-exclamation","warn","{dberr_kpi_count}",$spark,"<span class='dberr-big'>$n</span>",$foot);
}

function dberr_kpi_db(array $m):string{
    $n=$m["total"];
    $path=(string)key($m["per_db"]);
    $top=current($m["per_db"]);
    $body="<span class='dberr-big dberr-big-name'>".dberr_h($top["name"])."</span> <span class='dberr-pill dberr-pill-bad'>"
        .dberr_tr("dberr_of_errors",array("%a"=>$top["n"],"%b"=>$n))."</span><span class='dberr-path'>".dberr_h($path)."</span>";
    $foot="<span>".dberr_tr("dberr_db_count",array("%s"=>count($m["per_db"])))."</span>"
        ."<button type='button' class='dberr-link' data-db='".dberr_h($path)."' onclick=\"DberrFilter(this.getAttribute('data-db'))\">{dberr_show_only}<i class='fas fa-arrow-right' aria-hidden='true'></i></button>";
    return dberr_kpi("fas fa-database","accent","{dberr_kpi_db}","",$body,$foot);
}

function dberr_kpi_latest(array $m):string{
    $r=$m["rows"][0];
    $tpl=new template_admin();
    $age="<span class='dberr-pill' title='".dberr_h($tpl->time_to_date($r["ts"],true))."'>".dberr_ago($r["ts"])."</span>";
    $subj=dberr_is_generic($r["subject"])?"<i class='dberr-generic'>{dberr_generic}</i>":"<b class='dberr-ellipsis' title='".dberr_h($r["subject"])."'>".dberr_h($r["subject"])."</b>";
    $body="$subj<span class='dberr-path'>".dberr_h($r["path"])."</span>";
    $foot="<span></span><button type='button' class='dberr-link' onclick=\"Loadjs('".CurrentPageName()."?zoomid={$r["id"]}')\">{dberr_view_detail}<i class='fas fa-arrow-right' aria-hidden='true'></i></button>";
    return dberr_kpi("fas fa-clock","accent","{dberr_kpi_latest}",$age,$body,$foot);
}

// Counts are over the whole table; clicking reloads the list for that database (server side).
function dberr_filters(array $m):string{
    $all=$m["db"]==""?"true":"false";
    $b="<button type='button' role='radio' aria-checked='$all' data-db='' onclick=\"DberrFilter('')\">{dberr_all} <b>{$m["total"]}</b></button>";
    foreach($m["per_db"] as $path=>$x){
        $on=$m["db"]===(string)$path?"true":"false";
        $e=dberr_h($path);
        $b.="<button type='button' role='radio' aria-checked='$on' data-db='$e' title='$e' onclick=\"DberrFilter(this.getAttribute('data-db'))\">".dberr_h($x["name"])." <b>{$x["n"]}</b></button>";
    }
    return "<section class='dberr-card dberr-filters'><span class='dberr-filters-l'>{dberr_filter}</span>"
        ."<div class='dberr-seg' role='radiogroup' aria-label='{dberr_filter}'>$b</div>"
        ."<span class='dberr-help'><i class='fas fa-lightbulb' aria-hidden='true'></i> {dberr_filter_help}</span></section>";
}

function dberr_table(array $m):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $rows="";
    foreach($m["rows"] as $r){
        $rep=$m["per_subject"][strtolower($r["subject"])] ?? 1;
        $subj=dberr_is_generic($r["subject"])
            ?"<i class='dberr-generic'>{dberr_generic}</i>"
            :"<span class='dberr-ellipsis' title='".dberr_h($r["subject"])."'>".dberr_h($r["subject"])."</span>";
        $exact=dberr_h($tpl->time_to_date($r["ts"],true));
        $rows.="<tr tabindex='0' onclick=\"Loadjs('$page?zoomid={$r["id"]}')\" onkeydown=\"if(event.keyCode==13){Loadjs('$page?zoomid={$r["id"]}');}\">"
            ."<td class='dberr-when'><span title='$exact'>".dberr_ago($r["ts"])."</span><small>".date("Y-m-d H:i:s",$r["ts"])."</small></td>"
            ."<td class='dberr-err'>$subj</td>"
            ."<td><span class='dberr-dbchip' title='".dberr_h($r["path"])."'><i class='fas fa-database' aria-hidden='true'></i>".dberr_h($r["db"])."</span></td>"
            ."<td class='dberr-c'><span class='dberr-rep'>&times;$rep</span></td>"
            ."<td class='dberr-c dberr-chev'><i class='fas fa-chevron-right' aria-hidden='true'></i></td></tr>";
    }
    $search=dberr_h($m["search"]);
    $nomatch=$m["search"]!=""&&count($m["rows"])==0
        ?"<div class='dberr-nomatch'>".dberr_tr("dberr_nomatch",array("%s"=>$search))." <button type='button' class='dberr-link' onclick=\"DberrSearch('')\">{dberr_clear_search}</button></div>":"";
    return "<section class='dberr-card dberr-list'><div class='dberr-list-head'><div class='dberr-list-t'><h3 class='dberr-card-t'>{dberr_errors}</h3>"
        ."<span class='dberr-pill' id='dberr-count'>".(count($m["rows"])<$m["matching"]
            ?dberr_tr("dberr_showing_of",array("%a"=>count($m["rows"]),"%b"=>$m["matching"]))
            :dberr_tr("dberr_showing",array("%s"=>count($m["rows"]))))."</span></div>"
        ."<label class='dberr-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i><span class='sr-only'>{dberr_search}</span>"
        ."<input type='search' id='dberr-q' value='$search' placeholder=\"{dberr_search_ph}\" onkeydown=\"if(event.keyCode==13){DberrSearch(this.value);}\"></label></div>"
        .($rows!=""?"<div class='dberr-scroll'><table class='dberr-tbl'><thead><tr><th>{dberr_when}</th><th>{dberr_error}</th><th>{dberr_database}</th><th class='dberr-c'>{dberr_repeats}</th><th></th></tr></thead><tbody id='dberr-rows'>$rows</tbody></table></div>":"")
        .$nomatch
        ."<div class='dberr-foot'><i class='fas fa-circle-info' aria-hidden='true'></i> {dberr_foot}</div></section>";
}

// ── Detail ────────────────────────────────────────────────────────────────

function zoom_js(){
    $id=intval($_GET["zoomid"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{dberr_detail_title}","$page?zoom-popup=$id",900);
}

// Decodes one stored entry; never trusts the serialized blobs (no object is ever built).
function dberr_entry(int $id):array{
    $q=dberr_db();
    $l=$q->mysqli_fetch_array("SELECT * FROM sql_errors WHERE ID=$id");
    if(!is_array($l) || !isset($l["ID"])){return array();}
    $e=array("id"=>$id,"ts"=>intval($l["zdate"] ?? 0),"subject"=>trim((string)($l["subject"] ?? "")),"path"=>(string)($l["database"] ?? ""),
        "sql"=>(string)base64_decode((string)($l["sql"] ?? "")),"errors"=>array(),"trace"=>array());
    $err=(string)base64_decode((string)($l["error"] ?? ""));
    $arr=@unserialize($err,array("allowed_classes"=>false));
    if(is_array($arr)){
        foreach($arr as $k=>$v){$e["errors"][]=(is_scalar($v)?(string)$v:json_encode($v));}
    }elseif($err!=""){
        $e["errors"][]=$err;
    }
    $tr=@unserialize((string)base64_decode((string)($l["debug"] ?? "")),array("allowed_classes"=>false));
    if(is_array($tr)){
        foreach($tr as $row){
            if(!is_array($row)){continue;}
            $e["trace"][]=array("file"=>str_replace("/usr/share/artica-postfix/","",(string)($row["file"] ?? "")),
                "line"=>intval($row["line"] ?? 0),"function"=>(string)($row["function"] ?? ""),"class"=>(string)($row["class"] ?? ""));
        }
    }
    return $e;
}

// Plain text for the clipboard: what support asks for, in one block.
function dberr_support_text(array $e):string{
    $t=array();
    $t[]="Subject: ".$e["subject"];
    $t[]="Database: ".$e["path"];
    $t[]="Date: ".date("Y-m-d H:i:s",$e["ts"]);
    $t[]="Error:";
    foreach($e["errors"] as $x){$t[]="  ".$x;}
    $t[]="SQL:";
    $t[]=$e["sql"];
    $t[]="Trace:";
    $i=0;
    foreach($e["trace"] as $r){$i++;$t[]="  $i. ".($r["class"]!=""?$r["class"]."::":"").$r["function"]."() ".$r["file"].":".$r["line"];}
    return implode("\n",$t);
}

function zoom_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $e=dberr_entry(intval($_GET["zoom-popup"]));
    if(count($e)==0){
        echo dberr_style().$tpl->_ENGINE_parse_body("<div class='dberr'><div class='dberr-alert'><i class='fas fa-circle-info' aria-hidden='true'></i><span>{dberr_gone}</span></div></div>");
        return;
    }
    $title=dberr_is_generic($e["subject"])?"{dberr_generic_title}":dberr_h($e["subject"]);
    $errs="";
    foreach($e["errors"] as $x){$errs.="<div>".dberr_h($x)."</div>";}
    if($errs==""){$errs="<div>&mdash;</div>";}
    $trace="";
    $i=0;
    foreach($e["trace"] as $r){
        $i++;
        $cls=$r["class"]!=""?"<span class='dberr-muted-i'>".dberr_h($r["class"])."::</span>":"";
        $trace.="<li><span class='dberr-n'>$i</span><span class='dberr-mono'>$cls".dberr_h($r["function"])."()</span><span class='dberr-muted-i'>".dberr_h($r["file"])
            .($r["line"]>0?", {dberr_line} ".$r["line"]:"")."</span></li>";
    }
    if($trace==""){$trace="<li class='dberr-muted-i'>&mdash;</li>";}
    $del="";
    if(dberr_admin()){
        $del="<button type='button' class='dberr-del' onclick=\"Loadjs('$page?delete-js={$e["id"]}')\"><i class='fas fa-trash' aria-hidden='true'></i>{dberr_delete}</button>";
    }
    $support=dberr_h(dberr_support_text($e));
    $h="<div class='dberr dberr-detail'>"
        ."<div class='dberr-detail-head'><h3>$title</h3><p class='dberr-muted'><span class='dberr-dbchip'><i class='fas fa-database' aria-hidden='true'></i>".dberr_h(dberr_dbname($e["path"]))."</span> "
        ."<span class='dberr-mono'>".dberr_h($e["path"])."</span> &middot; ".dberr_h($tpl->time_to_date($e["ts"],true))."</p></div>"
        ."<section class='dberr-card dberr-sec'><h4>{dberr_what_happened}</h4><div class='dberr-errbox'>$errs</div></section>"
        ."<section class='dberr-card dberr-sec'><h4>{dberr_request}</h4><p class='dberr-help'>{dberr_request_help}</p><pre class='dberr-code'>".dberr_h($e["sql"])."</pre></section>"
        ."<section class='dberr-card dberr-sec'><h4>{dberr_where}</h4><p class='dberr-help'>{dberr_where_help}</p><ol class='dberr-trace'>$trace</ol></section>"
        ."<textarea id='dberr-support-$e[id]' class='dberr-hidden' readonly aria-hidden='true'>$support</textarea>"
        ."<div class='dberr-detail-foot'><button type='button' class='dberr-btn' onclick=\"DberrCopy(this,'dberr-support-$e[id]')\"><i class='fas fa-copy' aria-hidden='true'></i><span>{dberr_copy}</span></button>$del</div>"
        ."</div>";
    echo dberr_style().$tpl->_ENGINE_parse_body($h).dberr_copy_js();
}

// ── Delete ────────────────────────────────────────────────────────────────

function dberr_delete_js(){
    $tpl=new template_admin();
    $id=intval($_GET["delete-js"]);
    header("content-type: application/x-javascript");
    if(!dberr_admin()){$tpl->js_error("{ERROR_NO_PRIVS2}");return;}
    if($id<1){return;}
    $tpl->js_confirm_delete("{dberr_delete_confirm}","dberr-delete",strval($id),"dialogInstance1.close();DberrReload();",true);
}

// Echoes nothing on success, the error otherwise.
function dberr_delete(){
    $tpl=new template_admin();
    if(!dberr_admin()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return;}
    $id=intval($_POST["dberr-delete"]);
    if($id<1){return;}
    $q=dberr_db();
    $q->QUERY_SQL_PARAMS("DELETE FROM sql_errors WHERE ID=?",array($id));
    if(!$q->ok){echo $q->mysql_error;return;}
    admin_tracks("Delete the SQL error log entry #$id");
}

// ── JS / CSS ──────────────────────────────────────────────────────────────

function dberr_js(string $page):string{
    $tpl=new template_admin();
    return "<script src='js/jquery.peity.min.js'></script>
<script>
window.DberrState=window.DberrState||{db:'',q:''};
function DberrReload(){
    LoadAjaxSilent('dberr-host','$page?dberr-host=yes&q='+encodeURIComponent(DberrState.q||'')+'&db='+encodeURIComponent(DberrState.db||''));
}
function DberrSearch(v){ DberrState.q=String(v||'').trim(); DberrReload(); }
function DberrFilter(db){ DberrState.db=String(db||''); DberrReload(); }
</script>";
}

// The copy helper is emitted by the detail popup itself: it must work whatever loaded first.
function dberr_copy_js():string{
    $tpl=new template_admin();
    $copied=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{dberr_copied}"),ENT_QUOTES,"UTF-8"));
    return "<script>
window.DberrCopy=function(btn,id){
    var ta=document.getElementById(id); if(!ta){return;}
    var t=ta.value;
    var done=function(){ var s=btn.querySelector('span'); var o=s?s.textContent:''; if(s){s.textContent=$copied;} btn.classList.add('dberr-copied');
        setTimeout(function(){ if(s){s.textContent=o;} btn.classList.remove('dberr-copied'); },2000); };
    var fallback=function(){ var x=document.createElement('textarea'); x.value=t; x.setAttribute('readonly',''); x.style.position='absolute'; x.style.left='-9999px';
        document.body.appendChild(x); x.select(); try{document.execCommand('copy');done();}catch(e){} document.body.removeChild(x); };
    if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(t).then(done,fallback); } else { fallback(); }
};
</script>";
}

function dberr_style():string{
    if(isset($GLOBALS["DBERR_STYLE"])){return "";}
    $GLOBALS["DBERR_STYLE"]=true;
    $accent="#005447";$soft="#e6eeed";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.dberr{display:flex;flex-direction:column;gap:20px;color:#0f172a;font-size:14px;margin-top:10px}
.dberr *{box-sizing:border-box}
.dberr h2,.dberr h3,.dberr h4,.dberr p,.dberr ol{margin:0}
.dberr-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.dberr-card-t{font-size:17px;font-weight:600}
.dberr-muted{color:#475569;font-size:13px;line-height:1.5;margin-top:4px!important}
.dberr-help{display:block;color:#64748b;font-size:13px;line-height:1.45}
.dberr-mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px}
.dberr-muted-i{color:#64748b;font-size:13px}
.dberr-banner{display:flex;gap:18px;align-items:flex-start;background:$soft;border:1px solid #bfd4d1;border-radius:16px;padding:24px}
.dberr-banner-ico{flex:0 0 48px;height:48px;border-radius:50%;background:$accent;color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px}
.dberr-banner-txt{flex:1;min-width:0}
.dberr-banner-t{font-size:20px;font-weight:600;line-height:1.3}
.dberr-banner-p{font-size:14px;line-height:1.55;color:#334155;margin-top:6px!important;max-width:880px}
.dberr-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
.dberr-chip{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#334155;background:#fff;border:1px solid #bfd4d1;border-radius:999px;padding:4px 12px}
.dberr-chip i,.dberr-chip svg{color:$accent}
.dberr-alert{display:flex;gap:10px;align-items:flex-start;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;font-size:13px;color:#7f1d1d;word-break:break-word}
.dberr-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px}
.dberr-kpi{padding:18px 20px;display:flex;flex-direction:column;gap:10px;min-width:0}
.dberr-kpi-head{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
.dberr-kpi-l{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:500;color:#334155}
.dberr-tone-warn{color:#d97706}.dberr-tone-accent{color:$accent}
.dberr-kpi-body{flex:1;display:flex;flex-direction:column;gap:4px;min-width:0}
.dberr-kpi-body>.dberr-pill{align-self:flex-start}
.dberr-big{font-size:28px;font-weight:600;line-height:34px;font-variant-numeric:tabular-nums}
.dberr-big-name{font-size:22px}
.dberr-path{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;color:#64748b;word-break:break-all}
.dberr-kpi-foot{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;font-size:13px;color:#64748b;border-top:1px solid #f1f5f9;padding-top:10px}
.dberr-kpi-foot b{color:#0f172a;font-weight:600}
.dberr-spark-wrap{display:flex;flex-direction:column;align-items:flex-end;gap:2px}
.dberr-pill{display:inline-flex;align-items:center;font-size:13px;font-weight:500;border-radius:999px;padding:2px 10px;background:#f1f5f9;color:#334155;white-space:nowrap}
.dberr-pill-bad{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca}
.dberr-ellipsis{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dberr-generic{color:#64748b;font-style:italic}
.dberr-link{display:inline-flex;align-items:center;gap:6px;background:none;border:0;color:$accent;font-weight:600;font-size:13px;cursor:pointer;padding:2px 0}
.dberr-link:hover{text-decoration:underline}
.dberr-filters{display:flex;align-items:center;gap:14px;flex-wrap:wrap;padding:14px 20px}
.dberr-filters-l{font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#64748b}
.dberr-seg{display:flex;flex-wrap:wrap;gap:8px}
.dberr-seg button{display:inline-flex;align-items:center;gap:6px;border:1px solid #e2e8f0;background:#f8fafc;border-radius:999px;padding:5px 14px;font-size:13px;font-weight:500;color:#334155;cursor:pointer}
.dberr-seg button b{font-weight:600;color:#64748b}
.dberr-seg button[aria-checked=true]{background:$accent;border-color:$accent;color:#fff}
.dberr-seg button[aria-checked=true] b{color:#fff}
.dberr-filters .dberr-help{margin-left:auto}
.dberr-filters .dberr-help i,.dberr-filters .dberr-help svg{color:#d97706}
.dberr-list{overflow:hidden}
.dberr-list-head{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;padding:18px 20px}
.dberr-list-t{display:flex;align-items:center;gap:10px}
.dberr-search{display:flex;align-items:center;gap:8px;border:1px solid #cbd5e1;border-radius:10px;padding:0 12px;height:38px;width:360px;max-width:100%;margin:0;color:#64748b;font-weight:400}
.dberr-search input{flex:1;min-width:0;border:0;outline:0;font-size:14px;background:transparent;color:#0f172a}
.dberr-search:focus-within{outline:2px solid $accent;outline-offset:1px}
.dberr-scroll{overflow-x:auto}
.dberr-tbl{width:100%;border-collapse:collapse}
.dberr-tbl th{font-size:13px;font-weight:600;color:#334155;text-align:left;padding:10px 20px;background:#f8fafc;border-top:1px solid #eef2f6;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.dberr-tbl td{font-size:14px;padding:12px 20px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.dberr-tbl tbody tr{cursor:pointer}
.dberr-tbl tbody tr:hover,.dberr-tbl tbody tr:focus{background:#f8fafc;outline:0}
.dberr-tbl tbody tr:focus-visible{box-shadow:inset 3px 0 0 $accent}
.dberr-when{white-space:nowrap;width:1%}
.dberr-when small{display:block;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;color:#64748b}
.dberr-err{max-width:0;width:100%;font-weight:500}
.dberr-c{text-align:center;width:1%;white-space:nowrap}
.dberr-dbchip{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:500;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:999px;padding:2px 10px;white-space:nowrap}
.dberr-dbchip i,.dberr-dbchip svg{color:$accent}
.dberr-rep{display:inline-block;min-width:40px;font-size:13px;font-weight:500;background:#f1f5f9;border-radius:999px;padding:1px 8px;color:#334155}
.dberr-chev{color:#94a3b8}
.dberr-nomatch{padding:28px 20px;text-align:center;color:#475569}
.dberr-foot{display:flex;align-items:center;gap:8px;padding:12px 20px;font-size:13px;color:#64748b;background:#fafbfc;border-top:1px solid #f1f5f9}
.dberr-empty{text-align:center;padding:40px 24px;display:flex;flex-direction:column;align-items:center;gap:6px}
.dberr-empty-ico{width:64px;height:64px;border-radius:50%;background:#dcfce7;color:#15803d;display:flex;align-items:center;justify-content:center;font-size:26px;margin-bottom:8px}
.dberr-empty .dberr-muted{max-width:560px}
.dberr-detail{gap:14px;margin-top:0}
.dberr-detail-head h3{font-size:18px;font-weight:600;word-break:break-word}
.dberr-detail-head .dberr-mono{color:#64748b;word-break:break-all}
.dberr-sec{padding:16px 18px;display:flex;flex-direction:column;gap:8px}
.dberr-sec h4{font-size:15px;font-weight:600}
.dberr-errbox{background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 12px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;color:#7f1d1d;word-break:break-word}
.dberr-code{margin:0;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;color:#0f172a;white-space:pre-wrap;word-break:break-word;max-height:260px;overflow:auto}
.dberr-trace{list-style:none;padding:0!important;display:flex;flex-direction:column;gap:6px}
.dberr-trace li{display:flex;align-items:baseline;gap:10px;flex-wrap:wrap;font-size:13px}
.dberr-n{flex:0 0 22px;height:22px;border-radius:50%;background:$soft;color:$accent;font-weight:600;font-size:13px;display:inline-flex;align-items:center;justify-content:center}
.dberr-hidden{position:absolute;left:-9999px;width:1px;height:1px}
.dberr-detail-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;padding-top:4px}
.dberr-btn{display:inline-flex;align-items:center;gap:8px;background:$accent;color:#fff;border:0;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer}
.dberr-btn:hover{background:#003d33}
.dberr-copied{background:#15803d!important}
.dberr-del{display:inline-flex;align-items:center;gap:6px;background:none;border:0;color:#b91c1c;font-size:13px;font-weight:600;cursor:pointer;padding:6px 4px}
.dberr-del:hover{text-decoration:underline}
.dberr-btn:focus-visible,.dberr-del:focus-visible,.dberr-link:focus-visible,.dberr-seg button:focus-visible{outline:2px solid $accent;outline-offset:2px}
.sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}
@media(max-width:1200px){.dberr-filters .dberr-help{margin-left:0;flex-basis:100%}}
</style>";
}
