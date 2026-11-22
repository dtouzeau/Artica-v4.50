<?php
// awc_*() / helpers partages : l'include DOIT rester INCONDITIONNEL (place dans
// if(!class_exists("sockets")){...} il n'etait jamais evalue -> fatal).
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.artica-samba.inc");
include_once(dirname(__FILE__)."/ressources/class.samba2.ui.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$user=new usersMenus();
if(!$user->AsSambaAdministrator){die();}

if(isset($_GET["start"])){smbev_start();exit;}
if(isset($_GET["tiles"])){smbev_tiles_inner();exit;}
if(isset($_GET["tab-journals"])){smbev_tab_journals();exit;}
if(isset($_GET["journal"])){smbev_journal_panel_out();exit;}
if(isset($_GET["tab-sessions"])){smbev_tab_sessions();exit;}
if(isset($_GET["sessions"])){smbev_sessions_inner();exit;}
if(isset($_GET["tab-status"])){smbev_tab_status();exit;}

page();


function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $iid=smbev_iid();
    $instance_name=samba2_instance_suffix($iid);
    $html=$tpl->page_header("$instance_name &raquo;&raquo; {events}",
        ico_eye,
        "{samba_events_explain}",
        "$page?start=yes&instance-id=$iid","samba-logs-$iid","progress-samba-logs",
        false,"table-samba-logs");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("$instance_name - {events}",$html);
        echo $tpl->build_firewall();
        return;
    }
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
}

// ── Helpers ──────────────────────────────────────────────────

function smbev_iid():string{
    return preg_replace("#[^a-zA-Z0-9]#","",(string)($_GET["instance-id"] ?? ""));
}

function smbev_h($v):string{
    if(is_array($v)){return "";}
    return htmlspecialchars((string)$v,ENT_QUOTES,"ISO-8859-1");
}

// Translated token as plain text (sprintf patterns), [br] markers removed.
function smbev_t(string $key):string{
    $tpl=new template_admin();
    $s=$tpl->_ENGINE_parse_body("{".$key."}");
    return trim(str_replace(array("[br]","[BR]"),"",$s));
}

function smbev_lines():int{
    $l=intval($_GET["lines"] ?? 500);
    if(!in_array($l,array(200,500,2000),true)){$l=500;}
    return $l;
}

function smbev_level_param():string{
    $l=(string)($_GET["level"] ?? "all");
    if(!in_array($l,array("all","err","note"),true)){$l="all";}
    return $l;
}

// Relative age: "just now", "5 min ago", "3 h ago", else the date.
function smbev_ago(int $ts):string{
    if($ts<=0){return "&mdash;";}
    $d=time()-$ts;
    if($d<60){return "{smbev_just_now}";}
    if($d<3600){return intval($d/60)." {smbev_min_ago}";}
    if($d<86400){return intval($d/3600)." {smbev_h_ago}";}
    return date("Y-m-d H:i",$ts);
}

function smbev_size(int $bytes):string{
    if($bytes<1024){return "$bytes B";}
    return FormatBytes($bytes/1024);
}

// Friendly name of a Samba journal: array(title, technical subtitle).
function smbev_file_label(string $name):array{
    $map=array(
        "log.smbd"=>array("{smbev_f_smbd}","<span class='sev-mono'>log.smbd</span>"),
        "log.nmbd"=>array("{smbev_f_nmbd}","<span class='sev-mono'>log.nmbd</span> &mdash; {smbev_f_nmbd_sub}"),
        "log.wsdd"=>array("{smbev_f_wsdd}","<span class='sev-mono'>log.wsdd</span> &mdash; {smbev_f_wsdd_sub}"),
        "log.winbindd"=>array("{smbev_f_winbindd}","<span class='sev-mono'>log.winbindd</span> &mdash; {smbev_f_winbindd_sub}"),
    );
    if(isset($map[$name])){return $map[$name];}
    return array(smbev_h($name),"");
}

function smbev_file_title_text(string $name):string{
    $l=smbev_file_label($name);
    if($l[1]===""){return $name;}
    $tpl=new template_admin();
    return $tpl->_ENGINE_parse_body($l[0]);
}

// ── Model ────────────────────────────────────────────────────

function smbev_health():array{
    static $cache=null;
    if($cache!==null){return $cache;}
    $samba=new ArticaSamba();
    try{
        $h=$samba->health();
        $st=(string)($h["status"] ?? "");
        $cache=array("ok"=>in_array($st,array("ok","healthy"),true),"reached"=>true,"data"=>$h,"error"=>"");
    }catch(\RuntimeException $e){
        $cache=array("ok"=>false,"reached"=>false,"data"=>array(),"error"=>$e->getMessage());
    }
    return $cache;
}

// People connected now. The daemon answers {sessions:[...],total}; an older
// daemon returned every active row of 30 days (one per poll), so only the rows
// of the most recent poll are kept, which is what the fixed daemon returns.
function smbev_sessions(string $iid):array{
    static $cache=array();
    if(isset($cache[$iid])){return $cache[$iid];}
    $out=array("ok"=>false,"rows"=>array(),"last"=>0,"error"=>"");
    $samba=new ArticaSamba();
    try{
        $r=$samba->getSessions($iid);
    }catch(\RuntimeException $e){
        $out["error"]=$e->getMessage();
        $cache[$iid]=$out;
        return $out;
    }
    $list=(is_array($r) && isset($r["sessions"]) && is_array($r["sessions"]))?$r["sessions"]:array();
    $latest="";
    foreach($list as $s){
        if(!is_array($s)){continue;}
        $ts=(string)($s["timestamp"] ?? "");
        if($ts>$latest){$latest=$ts;}
    }
    $rows=array();
    $seen=array();
    foreach($list as $s){
        if(!is_array($s)){continue;}
        if((string)($s["timestamp"] ?? "")!==$latest){continue;}
        $u=(string)($s["user"] ?? "");
        $ip=(string)($s["client_ip"] ?? "");
        $k=strtolower($u)."|".$ip;
        if(isset($seen[$k])){continue;}
        $seen[$k]=true;
        $rows[]=array("user"=>$u,"ip"=>$ip);
    }
    usort($rows,function($a,$b){
        $c=strnatcasecmp($a["user"],$b["user"]);
        if($c!=0){return $c;}
        return strnatcmp($a["ip"],$b["ip"]);
    });
    $out["ok"]=true;
    $out["rows"]=$rows;
    $out["last"]=smbev_ts($latest);
    $cache[$iid]=$out;
    return $out;
}

function smbev_ts(string $iso):int{
    if($iso===""){return 0;}
    try{
        $d=new DateTime($iso);
        return intval($d->getTimestamp());
    }catch(\Exception $e){
        return 0;
    }
}

function smbev_files(string $iid):array{
    static $cache=array();
    if(isset($cache[$iid])){return $cache[$iid];}
    $samba=new ArticaSamba();
    try{
        $files=$samba->listLogFiles($iid);
        $cache[$iid]=array("ok"=>true,"files"=>is_array($files)?$files:array(),"error"=>"");
    }catch(\RuntimeException $e){
        $cache[$iid]=array("ok"=>false,"files"=>array(),"error"=>$e->getMessage());
    }
    return $cache[$iid];
}

// File asked for, kept only when the daemon lists it; else log.smbd, else the newest.
function smbev_pick_file(array $files,string $want):string{
    $names=array();
    foreach($files as $f){
        if(is_array($f) && isset($f["name"])){$names[]=(string)$f["name"];}
    }
    if(count($names)==0){return "";}
    if($want!=="" && in_array($want,$names,true)){return $want;}
    if(in_array("log.smbd",$names,true)){return "log.smbd";}
    return $names[0];
}

// Samba log: "[2026/11/17 14:06:48.950882,  0] ../../source3/lib/serverid.c:48(fn)"
// then the message on the following lines. Levels are classified by smbev_classify().
// Other formats (wsdd) fall back to one entry per line with a keyword level.
function smbev_parse(string $content):array{
    $lines=preg_split("/\r?\n/",$content);
    $entries=array();
    $cur=null;
    $samba=false;
    foreach($lines as $ln){
        if(preg_match('#^\[(\d{4})/(\d\d)/(\d\d) (\d\d:\d\d:\d\d)(?:\.\d+)?,\s*(\d+)[^\]]*\]\s*(.*)$#',$ln,$m)){
            $samba=true;
            if($cur!==null){$entries[]=$cur;}
            $lvl=intval($m[5]);
            $cur=array("date"=>"$m[1]-$m[2]-$m[3]","time"=>$m[4],"lvl"=>$lvl,
                "src"=>smbev_src(trim($m[6])),"msg"=>"");
            continue;
        }
        if($cur!==null){
            $t=trim($ln);
            if($t===""){continue;}
            $cur["msg"].=($cur["msg"]===""?"":"\n").$t;
        }
    }
    if($cur!==null){$entries[]=$cur;}
    if($samba){
        foreach($entries as $i=>$e){
            if($e["msg"]===""){$entries[$i]["msg"]=$e["src"];$entries[$i]["src"]="";}
            $entries[$i]["lvl"]=smbev_classify(intval($e["lvl"]),$entries[$i]["msg"]);
        }
        return $entries;
    }
    $entries=array();
    foreach($lines as $ln){
        $t=trim($ln);
        if($t===""){continue;}
        $date="";$time="";
        if(preg_match('#^(\d{4}-\d\d-\d\d)[ T](\d\d:\d\d:\d\d)\S*\s*(.*)$#',$t,$m)){
            $date=$m[1];$time=$m[2];$t=$m[3];
        }
        $lvl="det";
        if(preg_match('#\b(ERROR|CRITICAL|FATAL)\b#i',$t)){$lvl="err";}
        elseif(preg_match('#\bWARN(ING)?\b#i',$t)){$lvl="note";}
        $entries[]=array("date"=>$date,"time"=>$time,"lvl"=>$lvl,"src"=>"","msg"=>$t);
    }
    return $entries;
}

// With Samba's default "log level = 0" nearly every written line is level 0,
// informational ones included (workgroup dumps, start banner). Level 0/1 is an
// Error only when the message reports a failure; otherwise it is a Notice.
function smbev_classify(int $lvl,string $msg):string{
    if($lvl>=2){return "det";}
    if(preg_match('#fail|denied|cannot|can\'t|unable|refus|error|panic|invalid|timed? ?out|not found|could not|no such|corrupt|abort#i',$msg)){
        return "err";
    }
    return "note";
}

// "../../source3/lib/serverid.c:48(serverid_exists_local)" -> "serverid.c:48 serverid_exists_local"
function smbev_src(string $s):string{
    if(preg_match('#^(\S+?)\(([^)]*)\)\s*$#',$s,$m)){
        return basename($m[1])." ".$m[2];
    }
    return $s;
}

// Consecutive identical messages become one group; newest group first.
function smbev_group(array $entries):array{
    $groups=array();
    $last=-1;
    foreach($entries as $e){
        if($last>=0){
            $g=$groups[$last];
            if($g["msg"]===$e["msg"] && $g["src"]===$e["src"] && $g["lvl"]===$e["lvl"]){
                $groups[$last]["count"]++;
                $groups[$last]["last_time"]=$e["time"];
                $groups[$last]["last_date"]=$e["date"];
                continue;
            }
        }
        $e["count"]=1;
        $e["last_time"]=$e["time"];
        $e["last_date"]=$e["date"];
        $groups[]=$e;
        $last=count($groups)-1;
    }
    return array_reverse($groups);
}

function smbev_journal(string $iid,string $file,int $lines):array{
    static $cache=array();
    $k="$iid|$file|$lines";
    if(isset($cache[$k])){return $cache[$k];}
    $out=array("ok"=>false,"file"=>$file,"lines"=>$lines,"groups"=>array(),"entries"=>0,"err"=>0,"warn"=>0,"error"=>"");
    if($file===""){$cache[$k]=$out;return $out;}
    $samba=new ArticaSamba();
    try{
        $content=$samba->readLogFile($iid,$file,$lines);
    }catch(\RuntimeException $e){
        $out["error"]=$e->getMessage();
        $cache[$k]=$out;
        return $out;
    }
    $entries=smbev_parse((string)$content);
    $err=0;$warn=0;
    foreach($entries as $e){
        if($e["lvl"]=="err"){$err++;}
        if($e["lvl"]=="note"){$warn++;}
    }
    $out["ok"]=true;
    $out["groups"]=smbev_group($entries);
    $out["entries"]=count($entries);
    $out["err"]=$err;
    $out["warn"]=$warn;
    $cache[$k]=$out;
    return $out;
}

// Everything the summary tiles need, computed once.
function smbev_model(string $iid):array{
    $files=smbev_files($iid);
    $file=smbev_pick_file($files["files"],"");
    return array(
        "iid"=>$iid,
        "health"=>smbev_health(),
        "sessions"=>smbev_sessions($iid),
        "files"=>$files,
        "file"=>$file,
        "journal"=>smbev_journal($iid,$file,500),
    );
}

// ── Page content ─────────────────────────────────────────────

function smbev_start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $iid=smbev_iid();
    if($iid===""){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{select_instance}"));
        return;
    }
    $m=smbev_model($iid);
    $nsess=count($m["sessions"]["rows"]);

    $tabs=array();
    $tabs["<i class='fas fa-book-open'></i> {smbev_tab_journals}"]="$page?tab-journals=yes&instance-id=$iid";
    $tabs["<i class='fas fa-users'></i> {smbev_tab_sessions} <span class='sev-tabcount'>$nsess</span>"]="$page?tab-sessions=yes&instance-id=$iid";
    $tabs["<i class='fas fa-circle-info'></i> {smbev_tab_status}"]="$page?tab-status=yes&instance-id=$iid";

    $html=array();
    $html[]="<div class='sev-page' id='smbev-page-$iid'>";
    $html[]=smbev_hero();
    $html[]="<div id='smbev-tiles-$iid'>".smbev_tiles($m)."</div>";
    $html[]="<div class='sev-tabs'>".$tpl->tabs_default($tabs)."</div>";
    $html[]="</div>";

    $instance_name=samba2_instance_suffix($iid);
    $topbuttons=array();
    $topbuttons[]=array("LoadAjax('table-samba-logs','$page?start=yes&instance-id=$iid');",ico_refresh,"{refresh}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="$instance_name &raquo;&raquo; {events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{samba_events_explain}";
    $TINY_ARRAY["URL"]="samba-logs-$iid";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    echo smbev_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).smbev_page_js($iid).
        "<script>NoSpinner();$tiny</script>";
}

function smbev_tiles_inner(){
    $iid=smbev_iid();
    if($iid===""){return;}
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body(smbev_tiles(smbev_model($iid)));
}

function smbev_hero():string{
    $h=array();
    $h[]="<div class='sev-hero'>";
    $h[]="<div class='sev-hero-ico'><i class='fas fa-folder-open'></i></div>";
    $h[]="<div class='sev-hero-body'>";
    $h[]="<div class='sev-hero-title'>{smbev_hero_title}</div>";
    $h[]="<div class='sev-hero-text'>{smbev_hero_text}</div>";
    $h[]="<div class='sev-chips'>";
    $h[]="<span class='sev-chip'><i class='fas fa-eye'></i> {smbev_chip_readonly}</span>";
    $h[]="<span class='sev-chip'><i class='fas fa-clock'></i> {smbev_chip_minute}</span>";
    $h[]="<span class='sev-chip'><i class='fas fa-triangle-exclamation'></i> {smbev_chip_errors}</span>";
    $h[]="</div></div></div>";
    return @implode("\n",$h);
}

function smbev_tiles(array $m):string{
    return "<div class='sev-tiles'>".smbev_tile_connected($m).smbev_tile_errors($m).smbev_tile_status($m)."</div>";
}

function smbev_tile_connected(array $m):string{
    $s=$m["sessions"];
    $h=array();
    $h[]="<div class='sev-tile'>";
    $h[]="<div class='sev-tile-main'><div class='sev-tile-label'>{smbev_tile_connected}</div>";
    if(!$s["ok"]){
        $h[]="<div class='sev-tile-value sev-muted'>&mdash;</div>";
        $h[]="<div class='sev-tile-sub'>{smbev_sessions_unknown}</div>";
    }else{
        $n=count($s["rows"]);
        $people=array();
        foreach($s["rows"] as $r){$people[strtolower($r["user"])]=true;}
        if($n==0){
            $h[]="<div class='sev-tile-value sev-muted'>0</div>";
            $h[]="<div class='sev-tile-sub'>{smbev_nobody}</div>";
        }else{
            $h[]="<div class='sev-tile-value'>$n <span class='sev-tile-unit'>".($n>1?"{smbev_connections}":"{smbev_connection}")."</span></div>";
            $h[]="<div class='sev-tile-sub'>".sprintf(smbev_t("smbev_people_on"),count($people),$n)."</div>";
        }
    }
    $h[]="</div><div class='sev-tile-ico sev-ico-blue'><i class='fas fa-users'></i></div></div>";
    return @implode("",$h);
}

function smbev_tile_errors(array $m):string{
    $j=$m["journal"];
    $iid=$m["iid"];
    $h=array();
    $h[]="<div class='sev-tile'>";
    $h[]="<div class='sev-tile-main'><div class='sev-tile-label'>{smbev_tile_errors}</div>";
    if(!$m["files"]["ok"]){
        $h[]="<div class='sev-tile-value sev-muted'>&mdash;</div>";
        $h[]="<div class='sev-tile-sub'>{smbev_journals_unreachable}</div>";
        $ico="sev-ico-grey";
    }elseif($m["file"]===""){
        $h[]="<div class='sev-tile-value sev-muted'>&mdash;</div>";
        $h[]="<div class='sev-tile-sub'>{smbev_no_journal_yet}</div>";
        $ico="sev-ico-grey";
    }elseif(!$j["ok"]){
        $h[]="<div class='sev-tile-value sev-muted'>&mdash;</div>";
        $h[]="<div class='sev-tile-sub'>{smbev_journal_unreadable}</div>";
        $ico="sev-ico-grey";
    }else{
        $scope=sprintf(smbev_t("smbev_err_scope"),500,smbev_h(smbev_file_title_text($m["file"])));
        if($j["err"]==0){
            $h[]="<div class='sev-tile-value sev-ok'><i class='fas fa-circle-check'></i> 0</div>";
            $h[]="<div class='sev-tile-sub'>".sprintf(smbev_t("smbev_no_err_scope"),500,smbev_h(smbev_file_title_text($m["file"])))."</div>";
            $ico="sev-ico-green";
        }else{
            $h[]="<div class='sev-tile-value sev-warn'>{$j["err"]} <span class='sev-tile-unit'>".($j["err"]>1?"{smbev_errors}":"{smbev_error}")."</span></div>";
            $h[]="<div class='sev-tile-sub'>$scope</div>";
            $h[]="<a href='#' class='sev-link' OnClick=\"SmbEvShowErrors_$iid();return false;\">{smbev_show_only_errors}</a>";
            $ico="sev-ico-amber";
        }
    }
    $h[]="</div><div class='sev-tile-ico $ico'><i class='fas fa-triangle-exclamation'></i></div></div>";
    return @implode("",$h);
}

function smbev_tile_status(array $m):string{
    $hl=$m["health"];
    $d=$hl["data"];
    $h=array();
    $h[]="<div class='sev-tile'>";
    $h[]="<div class='sev-tile-main'><div class='sev-tile-label'>{smbev_tile_status}</div>";
    if($hl["ok"]){
        $h[]="<div class='sev-tile-value sev-ok'><i class='fas fa-circle-check'></i> {smbev_running}</div>";
        $parts=array();
        if(!empty($d["samba_version"])){$parts[]="Samba ".smbev_h($d["samba_version"]);}
        if(!empty($d["version"])){$parts[]="{smbev_artica_service} ".smbev_h($d["version"]);}
        if(isset($d["instances"])){
            $n=intval($d["instances"]);
            $parts[]="$n ".($n>1?"{smbev_instances}":"{smbev_instance}");
        }
        $h[]="<div class='sev-tile-sub'>".@implode(" &middot; ",$parts)."</div>";
        $ico="sev-ico-green";
    }else{
        $h[]="<div class='sev-tile-value sev-bad'><i class='fas fa-circle-xmark'></i> {smbev_not_responding}</div>";
        $h[]="<div class='sev-tile-sub'>{smbev_not_responding_sub}</div>";
        $ico="sev-ico-red";
    }
    $h[]="<div class='sev-tile-note'>{smbev_status_scope}</div>";
    $h[]="</div><div class='sev-tile-ico $ico'><i class='fas fa-server'></i></div></div>";
    return @implode("",$h);
}

// ── Tab: service journals ────────────────────────────────────

function smbev_tab_journals(){
    $tpl=new template_admin();
    $iid=smbev_iid();
    $html=array();
    $html[]="<div class='sev-pane'>";
    $html[]="<div class='sev-explain'>{smbev_journals_explain}</div>";
    $html[]="<div id='smbev-jr-$iid'>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    smbev_journal_panel_out();
    echo "</div></div>";
}

function smbev_journal_panel_out(){
    $tpl=new template_admin();
    $iid=smbev_iid();
    $files=smbev_files($iid);
    $lines=smbev_lines();
    $level=smbev_level_param();
    $q=substr((string)($_GET["q"] ?? ""),0,120);
    if(!$files["ok"]){
        echo $tpl->_ENGINE_parse_body(smbev_callout_error("{smbev_journals_unreachable}",$files["error"]));
        return;
    }
    if(count($files["files"])==0){
        echo $tpl->_ENGINE_parse_body(smbev_empty("fas fa-book","{smbev_no_journal_title}","{smbev_no_journal_text}",""));
        return;
    }
    $file=smbev_pick_file($files["files"],(string)($_GET["file"] ?? ""));
    $j=smbev_journal($iid,$file,$lines);

    $h=array();
    $h[]="<div class='sev-jr'>";
    $h[]=smbev_file_list($iid,$files["files"],$file,$lines);
    $h[]="<div class='sev-jr-main'>";
    $h[]=smbev_toolbar($iid,$file,$lines,$level);
    $h[]=smbev_journal_summary($j);
    $h[]=smbev_entries($iid,$j);
    $h[]="</div></div>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$h)).smbev_journal_js($iid,$file,$lines,$level,$q);
}

function smbev_file_list(string $iid,array $files,string $sel,int $lines):string{
    $h=array();
    $h[]="<div class='sev-files'>";
    foreach($files as $f){
        if(!is_array($f) || !isset($f["name"])){continue;}
        $name=(string)$f["name"];
        $lab=smbev_file_label($name);
        $mt=intval($f["mtime"] ?? 0);
        $cls=($name===$sel)?" sev-file-on":"";
        $js="SmbEvJr_$iid(".smbev_h(json_encode($name)).",$lines);";
        $h[]="<button type='button' class='sev-file$cls' OnClick=\"$js\" aria-pressed='".($name===$sel?"true":"false")."'>";
        $h[]="<span class='sev-file-top'><span class='sev-file-name'><i class='fas fa-file-lines'></i> {$lab[0]}</span>";
        $h[]="<span class='sev-file-size'>".smbev_size(intval($f["size"] ?? 0))."</span></span>";
        if($lab[1]!==""){$h[]="<span class='sev-file-sub'>{$lab[1]}</span>";}
        $h[]="<span class='sev-file-age' title='".($mt>0?date("Y-m-d H:i:s",$mt):"")."'><i class='fas fa-clock'></i> {smbev_updated} ".smbev_ago($mt)."</span>";
        $h[]="</button>";
    }
    $h[]="</div>";
    return @implode("",$h);
}

function smbev_toolbar(string $iid,string $file,int $lines,string $level):string{
    $h=array();
    $h[]="<div class='sev-tools'>";
    $h[]="<div class='sev-seg' role='radiogroup' aria-label='{smbev_level_filter}'>";
    foreach(array("all"=>"{smbev_all}","err"=>"{smbev_only_errors}","note"=>"{smbev_err_and_note}") as $k=>$lab){
        $on=($k===$level);
        $h[]="<button type='button' role='radio' aria-checked='".($on?"true":"false")."' data-lvl='$k' class='sev-seg-b".($on?" sev-seg-on":"")."' OnClick=\"SmbEvLevel_$iid('$k');\">$lab</button>";
    }
    $h[]="</div>";
    $h[]="<div class='sev-search'><i class='fas fa-magnifying-glass'></i><input type='text' id='smbev-q-$iid' placeholder='{smbev_search_ph}' aria-label='{smbev_search_label}' OnKeyUp=\"SmbEvFilter_$iid();\"></div>";
    $h[]="<label class='sev-lines'>{smbev_lines}<select id='smbev-lines-$iid' title='{smbev_lines_help}' OnChange=\"SmbEvJr_$iid(".smbev_h(json_encode($file)).",this.value);\">";
    foreach(array(200,500,2000) as $n){
        $h[]="<option value='$n'".($n==$lines?" selected":"").">$n</option>";
    }
    $h[]="</select></label>";
    $h[]="<button type='button' class='sev-btn' OnClick=\"SmbEvCopy_$iid();\"><i class='fas fa-copy'></i> <span id='smbev-copy-$iid'>{smbev_copy}</span></button>";
    $h[]="</div>";
    return @implode("",$h);
}

function smbev_journal_summary(array $j):string{
    if(!$j["ok"]){return "";}
    $title=smbev_h(smbev_file_title_text($j["file"]));
    $s=sprintf(smbev_t("smbev_showing"),$j["lines"],"<b>$title</b>");
    $s.=" &middot; ".$j["entries"]." {smbev_entries}";
    if($j["err"]>0){$s.=" &middot; <span class='sev-red'>".$j["err"]." ".($j["err"]>1?"{smbev_errors}":"{smbev_error}")."</span>";}
    if($j["warn"]>0){$s.=" &middot; <span class='sev-amber'>".$j["warn"]." ".($j["warn"]>1?"{smbev_notices}":"{smbev_notice}")."</span>";}
    return "<div class='sev-sum'><span>$s</span><span class='sev-sum-r'>{smbev_newest_first}</span></div>";
}

function smbev_entries(string $iid,array $j):string{
    if(!$j["ok"]){
        return smbev_callout_error("{smbev_journal_unreadable}",$j["error"]);
    }
    if(count($j["groups"])==0){
        return smbev_empty("fas fa-file-circle-check","{smbev_empty_journal_title}","{smbev_empty_journal_text}","");
    }
    $h=array();
    $h[]="<div class='sev-entries' id='smbev-entries-$iid'>";
    $prevDate="";
    foreach($j["groups"] as $g){
        if($g["last_date"]!=="" && $g["last_date"]!==$prevDate){
            $h[]="<div class='sev-day'>".smbev_h($g["last_date"])."</div>";
            $prevDate=$g["last_date"];
        }
        $h[]=smbev_entry_row($g);
    }
    $h[]="</div>";
    $h[]="<div class='sev-nomatch' id='smbev-nomatch-$iid' style='display:none'><i class='fas fa-filter-circle-xmark'></i> {smbev_no_match} <a href='#' OnClick=\"SmbEvClear_$iid();return false;\">{smbev_clear_filter}</a></div>";
    return @implode("",$h);
}

function smbev_entry_row(array $g):string{
    $badges=array(
        "err"=>"<span class='sev-badge sev-b-err'><i class='fas fa-circle-exclamation'></i> {smbev_lvl_error}</span>",
        "note"=>"<span class='sev-badge sev-b-note'><i class='fas fa-circle-info'></i> {smbev_lvl_notice}</span>",
        "det"=>"<span class='sev-badge sev-b-det'>{smbev_lvl_detail}</span>",
    );
    $time=$g["last_time"];
    $tip=trim($g["last_date"]." ".$g["last_time"]);
    $rep="";
    if($g["count"]>1){
        $tip=trim($g["date"]." ".$g["time"])." &rarr; ".$tip;
        $rep="<span class='sev-rep' title='".smbev_h(sprintf(smbev_t("smbev_repeated"),$g["count"]))."'>&times;{$g["count"]}</span>";
    }
    $text=strtolower($g["msg"]." ".$g["src"]);
    $raw=trim($g["last_date"]." ".$g["last_time"])." [".$g["lvl"]."] ".$g["msg"].($g["count"]>1?" (x{$g["count"]})":"");
    $h="<div class='sev-e sev-e-{$g["lvl"]}' data-lvl='{$g["lvl"]}' data-text='".smbev_h($text)."' data-raw='".smbev_h($raw)."'>";
    $h.="<div class='sev-e-head'>{$badges[$g["lvl"]]}";
    if($time!==""){$h.="<span class='sev-time' title='".smbev_h($tip)."'>".smbev_h($time)."</span>";}
    $h.=$rep;
    if($g["src"]!==""){$h.="<span class='sev-src'>".smbev_h($g["src"])."</span>";}
    $h.="</div>";
    $h.="<div class='sev-msg'>".nl2br(smbev_h($g["msg"]))."</div>";
    $h.="</div>";
    return $h;
}

// ── Tab: connected now ───────────────────────────────────────

function smbev_tab_sessions(){
    $tpl=new template_admin();
    $iid=smbev_iid();
    $h=array();
    $h[]="<div class='sev-pane'>";
    $h[]="<div class='sev-explain'>{smbev_sessions_explain}</div>";
    $h[]="<div id='smbev-sess-$iid'>".smbev_sessions_block($iid)."</div>";
    $h[]="</div>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$h)).smbev_sessions_js($iid);
}

function smbev_sessions_inner(){
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body(smbev_sessions_block(smbev_iid()))."<script>if(typeof SmbEvSessFilter_".smbev_iid()."==='function'){SmbEvSessFilter_".smbev_iid()."();}</script>";
}

function smbev_sessions_block(string $iid):string{
    $s=smbev_sessions($iid);
    if(!$s["ok"]){
        return smbev_callout_error("{smbev_sessions_unknown}",$s["error"]);
    }
    if(count($s["rows"])==0){
        return smbev_empty("fas fa-user-slash","{smbev_nobody_title}","{smbev_nobody_text}",
            "<button type='button' class='sev-btn sev-btn-primary' OnClick=\"SmbEvOpenTab_$iid('tab-journals');\"><i class='fas fa-book-open'></i> {smbev_open_journals}</button>");
    }
    $count=array();
    foreach($s["rows"] as $r){
        $k=strtolower($r["user"]);
        $count[$k]=($count[$k] ?? 0)+1;
    }
    $h=array();
    $h[]="<div class='sev-sess-tools'><div class='sev-search'><i class='fas fa-magnifying-glass'></i><input type='text' id='smbev-sq-$iid' placeholder='{smbev_filter_sessions}' aria-label='{smbev_filter_sessions}' OnKeyUp=\"SmbEvSessFilter_$iid();\"></div></div>";
    $h[]="<div class='sev-card'><table class='sev-tbl'><thead><tr><th>{smbev_col_user}</th><th>{smbev_col_address}</th><th>{smbev_col_seen}</th></tr></thead><tbody id='smbev-srows-$iid'>";
    $prev="";
    foreach($s["rows"] as $r){
        $h[]=smbev_session_row($iid,$r,$count[strtolower($r["user"])],strtolower($r["user"])!==$prev,$s["last"]);
        $prev=strtolower($r["user"]);
    }
    $h[]="</tbody></table>";
    $h[]=smbev_sessions_foot($s,count($count));
    $h[]="</div>";
    return @implode("",$h);
}

function smbev_session_row(string $iid,array $r,int $ncomp,bool $first,int $last):string{
    $u=$r["user"];
    $short=preg_replace('#^.*[\\\\/]#',"",$u);
    $ini=strtoupper(substr(preg_replace('#[^A-Za-z0-9]#',"",$short),0,2));
    if($ini===""){$ini="?";}
    $cls=$first?"sev-sr-first":"sev-sr-more";
    $text=strtolower($u." ".$r["ip"]);
    $h="<tr class='$cls' data-text='".smbev_h($text)."'>";
    $h.="<td><div class='sev-user'><span class='sev-av'>".smbev_h($ini)."</span><span class='sev-uname'>".smbev_h($u)."</span>";
    if($ncomp>1){$h.="<span class='sev-ucomp'>$ncomp {smbev_computers}</span>";}
    $h.="</div></td>";
    $ip=smbev_h($r["ip"]);
    $h.="<td><span class='sev-mono'>$ip</span> <button type='button' class='sev-icobtn' aria-label='{smbev_copy_address}' title='{smbev_copy_address}' OnClick=\"SmbEvCopyText('$ip',this);\"><i class='fas fa-copy'></i></button></td>";
    $h.="<td><span title='".($last>0?date("Y-m-d H:i:s",$last):"")."'>".smbev_ago($last)."</span></td>";
    $h.="</tr>";
    return $h;
}

function smbev_sessions_foot(array $s,int $people):string{
    $n=count($s["rows"]);
    $t=$n." ".($n>1?"{smbev_connections}":"{smbev_connection}")." &middot; $people ".($people>1?"{smbev_people}":"{smbev_person}");
    if($s["last"]>0){$t.=" &middot; {smbev_last_checked} ".date("H:i:s",$s["last"]);}
    $stale="";
    if($s["last"]>0 && time()-$s["last"]>180){
        $stale="<span class='sev-amber'><i class='fas fa-clock'></i> {smbev_stale}</span>";
    }
    return "<div class='sev-foot'><span>$t</span>$stale</div>";
}

// ── Tab: service status ──────────────────────────────────────

function smbev_tab_status(){
    $tpl=new template_admin();
    $hl=smbev_health();
    $d=$hl["data"];
    $h=array();
    $h[]="<div class='sev-pane'>";
    $h[]="<div class='sev-explain'>{smbev_status_explain}</div>";
    if(!$hl["ok"]){
        $h[]=smbev_callout_error("{smbev_not_responding_long}",$hl["error"]);
    }
    $h[]="<div class='sev-card sev-dl'>";
    $st=$hl["ok"]?"<span class='sev-ok'><i class='fas fa-circle-check'></i> {smbev_running}</span>":"<span class='sev-bad'><i class='fas fa-circle-xmark'></i> {smbev_not_responding}</span>";
    $h[]=smbev_dl_row("fas fa-heart-pulse","{smbev_dl_status}",$st,"{smbev_dl_status_help}");
    $h[]=smbev_dl_row("fas fa-code-branch","{smbev_dl_samba}",empty($d["samba_version"])?"&mdash;":smbev_h($d["samba_version"]),"{smbev_dl_samba_help}");
    $h[]=smbev_dl_row("fas fa-cube","{smbev_dl_artica}",empty($d["version"])?"&mdash;":smbev_h($d["version"]),"{smbev_dl_artica_help}");
    $h[]=smbev_dl_row("fas fa-layer-group","{smbev_dl_instances}",isset($d["instances"])?strval(intval($d["instances"])):"&mdash;","{smbev_dl_instances_help}");
    $h[]="</div></div>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$h));
}

function smbev_dl_row(string $ico,string $label,string $value,string $help):string{
    return "<div class='sev-dl-row'><span class='sev-dl-ico'><i class='$ico'></i></span>"
        ."<div class='sev-dl-l'><div class='sev-dl-label'>$label</div><div class='sev-dl-help'>$help</div></div>"
        ."<div class='sev-dl-v'>$value</div></div>";
}

// ── Shared blocks ────────────────────────────────────────────

function smbev_empty(string $ico,string $title,string $text,string $action):string{
    return "<div class='sev-empty'><div class='sev-empty-ico'><i class='$ico'></i></div>"
        ."<div class='sev-empty-title'>$title</div><div class='sev-empty-text'>$text</div>"
        .($action!==""?"<div class='sev-empty-act'>$action</div>":"")."</div>";
}

function smbev_callout_error(string $title,string $detail):string{
    return "<div class='sev-callout'><i class='fas fa-circle-xmark'></i><div><div class='sev-callout-t'>$title</div>"
        .($detail!==""?"<div class='sev-callout-d'>".smbev_h($detail)."</div>":"")."</div></div>";
}

// ── JavaScript ───────────────────────────────────────────────

function smbev_page_js(string $iid):string{
    $page=CurrentPageName();
    return "<script>
function SmbEvOpenTab_$iid(key){
  var a=document.querySelector('#smbev-page-$iid a[data-url*=\"'+key+'=yes\"]');
  if(a){ a.click(); }
}
function SmbEvShowErrors_$iid(){ window.SmbEvWant_$iid='err'; SmbEvOpenTab_$iid('tab-journals'); }
function SmbEvCopyText(txt,btn){
  var done=function(){ if(!btn){return;} btn.classList.add('sev-copied'); setTimeout(function(){btn.classList.remove('sev-copied');},1200); };
  if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(txt).then(done,function(){}); return; }
  var t=document.createElement('textarea'); t.value=txt; t.style.position='fixed'; t.style.opacity='0';
  document.body.appendChild(t); t.select(); try{ document.execCommand('copy'); done(); }catch(e){} document.body.removeChild(t);
}
if(window.SmbEvTimer){ clearInterval(window.SmbEvTimer); }
window.SmbEvTimer=setInterval(function(){
  if(!document.getElementById('smbev-tiles-$iid')){ clearInterval(window.SmbEvTimer); window.SmbEvTimer=null; return; }
  if(document.querySelector('.modal.in,.modal.show,.sweet-alert.visible')){ return; }
  LoadAjaxSilent('smbev-tiles-$iid','$page?tiles=yes&instance-id=$iid');
  if(document.getElementById('smbev-sess-$iid')){ LoadAjaxSilent('smbev-sess-$iid','$page?sessions=yes&instance-id=$iid'); }
},60000);
</script>";
}

function smbev_journal_js(string $iid,string $file,int $lines,string $level,string $q):string{
    $page=CurrentPageName();
    $copied=json_encode(smbev_t("smbev_copied"));
    $copy=json_encode(smbev_t("smbev_copy"));
    $qj=json_encode($q);
    return "<script>
window.SmbEvLvl_$iid=".json_encode($level).";
if(window.SmbEvWant_$iid){ window.SmbEvLvl_$iid=window.SmbEvWant_$iid; window.SmbEvWant_$iid=null; }
function SmbEvJr_$iid(file,lines){
  var q=document.getElementById('smbev-q-$iid'); q=q?q.value:'';
  LoadAjaxSilent('smbev-jr-$iid','$page?journal=yes&instance-id=$iid&file='+encodeURIComponent(file)+'&lines='+encodeURIComponent(lines)+'&level='+encodeURIComponent(window.SmbEvLvl_$iid||'all')+'&q='+encodeURIComponent(q));
}
function SmbEvLevel_$iid(l){
  window.SmbEvLvl_$iid=l;
  var b=document.querySelectorAll('#smbev-jr-$iid .sev-seg-b');
  for(var i=0;i<b.length;i++){ var on=b[i].getAttribute('data-lvl')===l; b[i].classList.toggle('sev-seg-on',on); b[i].setAttribute('aria-checked',on?'true':'false'); }
  SmbEvFilter_$iid();
}
function SmbEvFilter_$iid(){
  var box=document.getElementById('smbev-entries-$iid'); if(!box){ return; }
  var l=window.SmbEvLvl_$iid||'all';
  var q=document.getElementById('smbev-q-$iid'); q=q?q.value.toLowerCase().trim():'';
  var rows=box.children, shown=0, lastDay=null, dayHas=false;
  for(var i=0;i<rows.length;i++){
    var r=rows[i];
    if(r.classList.contains('sev-day')){ if(lastDay){ lastDay.style.display=dayHas?'':'none'; } lastDay=r; dayHas=false; continue; }
    var lv=r.getAttribute('data-lvl'), ok=true;
    if(l==='err' && lv!=='err'){ ok=false; }
    if(l==='note' && lv==='det'){ ok=false; }
    if(ok && q!=='' && r.getAttribute('data-text').indexOf(q)<0){ ok=false; }
    r.style.display=ok?'':'none'; if(ok){ shown++; dayHas=true; }
  }
  if(lastDay){ lastDay.style.display=dayHas?'':'none'; }
  var nm=document.getElementById('smbev-nomatch-$iid'); if(nm){ nm.style.display=shown?'none':''; }
}
function SmbEvClear_$iid(){ var q=document.getElementById('smbev-q-$iid'); if(q){ q.value=''; } SmbEvLevel_$iid('all'); }
function SmbEvCopy_$iid(){
  var box=document.getElementById('smbev-entries-$iid'); if(!box){ return; }
  var out=[], rows=box.querySelectorAll('.sev-e');
  for(var i=0;i<rows.length;i++){ if(rows[i].style.display!=='none'){ out.push(rows[i].getAttribute('data-raw')); } }
  SmbEvCopyText(out.join('\\n'),null);
  var s=document.getElementById('smbev-copy-$iid'); if(s){ s.textContent=$copied; setTimeout(function(){ s.textContent=$copy; },1500); }
}
(function(){ var q=document.getElementById('smbev-q-$iid'); if(q){ q.value=$qj; } SmbEvLevel_$iid(window.SmbEvLvl_$iid); NoSpinner(); })();
</script>";
}

function smbev_sessions_js(string $iid):string{
    return "<script>
function SmbEvSessFilter_$iid(){
  var q=document.getElementById('smbev-sq-$iid'); q=q?q.value.toLowerCase().trim():'';
  var rows=document.querySelectorAll('#smbev-srows-$iid tr');
  for(var i=0;i<rows.length;i++){ rows[i].style.display=(q===''||rows[i].getAttribute('data-text').indexOf(q)>=0)?'':'none'; }
}
NoSpinner();
</script>";
}

// ── CSS ──────────────────────────────────────────────────────

function smbev_style():string{
    $acc="#10b981";$soft="rgba(16,185,129,.12)";$dark="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$acc=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
    }
    return "<style>
.sev-page{color:#0f172a;font-size:14px;margin-top:10px}
.sev-page *{box-sizing:border-box}
.sev-hero{display:flex;gap:20px;background:#fff;border:1px solid #e2e8f0;border-left:4px solid $acc;border-radius:12px;padding:22px 24px;box-shadow:0 1px 2px rgba(15,23,42,.06);margin-bottom:20px}
.sev-hero-ico{flex:0 0 48px;height:48px;border-radius:12px;background:$soft;color:$acc;display:flex;align-items:center;justify-content:center;font-size:22px}
.sev-hero-title{font-size:20px;font-weight:600;margin-bottom:6px}
.sev-hero-text{font-size:14px;color:#475569;line-height:1.55;max-width:980px}
.sev-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.sev-chip{display:inline-flex;align-items:center;gap:7px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:999px;padding:5px 12px;font-size:13px;color:#334155}
.sev-chip .fa-triangle-exclamation,.sev-chip svg.fa-triangle-exclamation{color:#d97706}
.sev-tiles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px;margin-bottom:22px}
@media (max-width:1200px){.sev-tiles{grid-template-columns:minmax(0,1fr)}}
.sev-tile{display:flex;justify-content:space-between;gap:14px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px;box-shadow:0 1px 2px rgba(15,23,42,.06);min-height:118px}
.sev-tile-main{min-width:0}
.sev-tile-label{font-size:13px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#64748b;margin-bottom:6px}
.sev-tile-value{font-size:28px;font-weight:700;line-height:1.2;display:flex;align-items:baseline;gap:8px}
.sev-tile-unit{font-size:14px;font-weight:500;color:#475569}
.sev-tile-sub{font-size:13px;color:#475569;margin-top:4px}
.sev-tile-note{font-size:13px;color:#94a3b8;margin-top:4px}
.sev-tile-ico{flex:0 0 46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:19px}
.sev-ico-blue{background:#eff6ff;color:#2563eb}.sev-ico-green{background:#ecfdf5;color:#16a34a}.sev-ico-amber{background:#fffbeb;color:#d97706}
.sev-ico-red{background:#fef2f2;color:#dc2626}.sev-ico-grey{background:#f1f5f9;color:#64748b}
.sev-ok{color:#16a34a}.sev-bad{color:#dc2626}.sev-warn{color:#d97706}.sev-muted{color:#94a3b8}.sev-red{color:#dc2626;font-weight:600}.sev-amber{color:#b45309;font-weight:600}
.sev-link{display:inline-block;margin-top:6px;font-size:13px;font-weight:600;color:#b45309;text-decoration:underline}
.sev-tabs .nav-tabs>li>a{font-size:14px;font-weight:500}
.sev-tabcount{display:inline-block;min-width:22px;padding:1px 7px;margin-left:4px;border-radius:999px;background:#e2e8f0;color:#334155;font-size:13px;text-align:center}
.sev-pane{padding:16px 0 4px}
.sev-explain{font-size:14px;color:#475569;margin-bottom:14px}
.sev-jr{display:grid;grid-template-columns:280px minmax(0,1fr);gap:20px;align-items:start}
@media (max-width:1200px){.sev-jr{grid-template-columns:minmax(0,1fr)}}
.sev-files{display:flex;flex-direction:column;gap:12px}
.sev-file{display:block;width:100%;text-align:left;background:#fff;border:1px solid #e2e8f0;border-left:4px solid transparent;border-radius:12px;padding:14px 16px;cursor:pointer;color:#0f172a}
.sev-file:hover{background:#f8fafc}
.sev-file:focus-visible,.sev-seg-b:focus-visible,.sev-btn:focus-visible,.sev-icobtn:focus-visible{outline:2px solid $acc;outline-offset:2px}
.sev-file-on{border-color:$acc;border-left-color:$acc;background:$soft}
.sev-file-top{display:flex;justify-content:space-between;gap:8px;align-items:baseline}
.sev-file-name{font-weight:600;font-size:14px}
.sev-file-name svg,.sev-file-name i{color:$dark;margin-right:4px}
.sev-file-size{font-size:13px;color:#64748b;white-space:nowrap}
.sev-file-sub{display:block;font-size:13px;color:#64748b;margin-top:4px;line-height:1.45}
.sev-file-age{display:block;font-size:13px;color:#64748b;margin-top:6px}
.sev-jr-main{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06);min-width:0;overflow:hidden}
.sev-tools{display:flex;flex-wrap:wrap;gap:12px;align-items:center;padding:14px 16px;border-bottom:1px solid #e2e8f0}
.sev-seg{display:inline-flex;background:#f1f5f9;border-radius:8px;padding:3px}
.sev-seg-b{border:0;background:transparent;padding:6px 12px;border-radius:6px;font-size:13px;color:#475569;cursor:pointer;white-space:nowrap}
.sev-seg-on{background:#fff;color:#0f172a;font-weight:600;box-shadow:0 1px 2px rgba(15,23,42,.12)}
.sev-search{position:relative;flex:1 1 220px;max-width:340px}
.sev-search svg,.sev-search i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px}
.sev-search input{width:100%;height:36px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px 0 32px;font-size:14px;color:#0f172a}
.sev-search input:focus{outline:2px solid $acc;outline-offset:1px;border-color:$acc}
.sev-lines{display:inline-flex;align-items:center;gap:8px;margin:0 0 0 auto;font-size:13px;font-weight:500;color:#475569}
.sev-lines select{height:36px;border:1px solid #cbd5e1;border-radius:8px;padding:0 8px;font-size:14px;background:#fff}
.sev-btn{display:inline-flex;align-items:center;gap:6px;height:36px;padding:0 14px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;font-size:14px;color:#0f172a;cursor:pointer}
.sev-btn:hover{background:#f8fafc}
.sev-btn-primary{background:$acc;border-color:$acc;color:#fff}
.sev-btn-primary:hover{background:$dark}
.sev-sum{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:10px 16px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:13px;color:#334155}
.sev-sum-r{color:#64748b}
.sev-entries{max-height:640px;overflow-y:auto}
.sev-day{position:sticky;top:0;background:#f1f5f9;color:#475569;font-size:13px;font-weight:600;padding:4px 16px;border-bottom:1px solid #e2e8f0;z-index:1}
.sev-e{padding:12px 16px;border-bottom:1px solid #f1f5f9}
.sev-e-err{background:#fffafa}
.sev-e-head{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.sev-badge{display:inline-flex;align-items:center;gap:5px;padding:2px 9px;border-radius:6px;font-size:13px;font-weight:600}
.sev-b-err{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca}
.sev-b-note{background:#fffbeb;color:#b45309;border:1px solid #fde68a}
.sev-b-det{background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;font-weight:500}
.sev-time{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;color:#334155}
.sev-rep{display:inline-block;padding:1px 8px;border-radius:999px;background:#fef3c7;color:#92400e;font-size:13px;font-weight:700}
.sev-src{margin-left:auto;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;color:#64748b;overflow-wrap:anywhere}
.sev-msg{margin-top:6px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;line-height:1.55;color:#0f172a;white-space:pre-wrap;overflow-wrap:anywhere}
.sev-nomatch{padding:24px 16px;text-align:center;color:#475569;font-size:14px}
.sev-sess-tools{display:flex;margin-bottom:12px}
.sev-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06);overflow:hidden}
.sev-tbl{width:100%;border-collapse:collapse;font-size:14px;color:#0f172a}
.sev-tbl th{text-align:left;font-size:13px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.04em;padding:10px 16px;background:#f8fafc;border-bottom:1px solid #e2e8f0}
.sev-tbl td{padding:10px 16px;height:52px;vertical-align:middle;color:#0f172a}
.sev-sr-first td{border-top:1px solid #e2e8f0}
.sev-sr-more td{border-top:1px dashed #f1f5f9}
.sev-sr-more .sev-av{visibility:hidden}
.sev-user{display:flex;align-items:center;gap:10px}
.sev-av{flex:0 0 32px;height:32px;border-radius:50%;background:$soft;color:$dark;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:center}
.sev-uname{font-weight:600}
.sev-ucomp{font-size:13px;color:#64748b}
.sev-mono{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px}
.sev-icobtn{border:0;background:transparent;color:#94a3b8;cursor:pointer;padding:4px 6px;border-radius:6px;font-size:13px}
.sev-icobtn:hover{color:#334155;background:#f1f5f9}
.sev-copied{color:#16a34a!important}
.sev-foot{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:12px 16px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:13px;color:#475569}
.sev-dl-row{display:flex;align-items:center;gap:14px;padding:14px 18px;border-top:1px solid #f1f5f9}
.sev-dl-row:first-child{border-top:0}
.sev-dl-ico{flex:0 0 34px;height:34px;border-radius:8px;background:#f1f5f9;color:#475569;display:flex;align-items:center;justify-content:center}
.sev-dl-l{flex:1 1 auto;min-width:0}
.sev-dl-label{font-weight:600;font-size:14px}
.sev-dl-help{font-size:13px;color:#64748b;margin-top:2px}
.sev-dl-v{font-size:15px;font-weight:600;white-space:nowrap}
.sev-empty{background:#fff;border:1px dashed #cbd5e1;border-radius:12px;padding:36px 24px;text-align:center}
.sev-empty-ico{width:56px;height:56px;margin:0 auto 12px;border-radius:50%;background:#f1f5f9;color:#64748b;display:flex;align-items:center;justify-content:center;font-size:22px}
.sev-empty-title{font-size:16px;font-weight:600;margin-bottom:6px}
.sev-empty-text{font-size:14px;color:#475569;max-width:620px;margin:0 auto;line-height:1.55}
.sev-empty-act{margin-top:16px}
.sev-callout{display:flex;gap:12px;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:14px 16px;color:#991b1b;margin-bottom:14px}
.sev-callout-t{font-weight:600;font-size:14px}
.sev-callout-d{font-size:13px;margin-top:4px;font-family:ui-monospace,Menlo,Consolas,monospace;overflow-wrap:anywhere}
@media (prefers-reduced-motion:no-preference){.sev-e,.sev-file{transition:background-color .15s ease}}
</style>";
}
