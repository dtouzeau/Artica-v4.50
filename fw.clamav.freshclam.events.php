<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
page();

// ---- design (maquette stitch_clamav_update_events, 2026-11-15) --------------------------------
// "Update events" tab of fw.clamav.status.php: tail of /var/log/clamav/freshclam.log
// (GET /freshclam/events/{base64 query}/{max}), newest first. Sibling of the "Service events"
// tab (fw.clamav.clamd.events.php): same search card, clickable counters, Time / Type / Message.
// The daemon returns raw lines only: the outcome banner, the counters and the database strip are
// computed over the lines shown, and say so.
// Left out of the mock-up because invented: "Cycle ID #4928", read time "in 14ms", the version
// footer and its links, the "Simulate table view" buttons, the per-counter causes
// ("mirror unreachable", "DNS retry / fallback").

function fcev_max_choices():array{ return array(50,100,250,500,1000); }
function fcev_logfile():string{ return "/var/log/clamav/freshclam.log"; }

// Search text and line count, from the request then the session. The old free-text syntax
// ("250 events") is still understood, so a session saved before this design keeps working.
function fcev_params():array{
    $term=null;$max=0;
    if(isset($_GET["search"])){$term=(string)$_GET["search"];}
    if(isset($_GET["max"])){$max=intval($_GET["max"]);}
    if($term===null){ $term=isset($_SESSION["FRESHCLAM_SEARCH"])?(string)$_SESSION["FRESHCLAM_SEARCH"]:""; }
    if($max==0 && isset($_SESSION["FRESHCLAM_SEARCH_MAX"])){$max=intval($_SESSION["FRESHCLAM_SEARCH_MAX"]);}
    if(preg_match("#(^|\s)([0-9]+)\s+events(\s|$)#i",$term,$re)){
        if($max==0){$max=intval($re[2]);}
        $term=str_replace($re[0]," ",$term);
    }
    $term=trim(preg_replace('/\s+/',' ',$term));
    // The daemon greps with the text: plain characters only.
    $term=preg_replace('/[^A-Za-z0-9 _.:@*=,\/\-]/',"",$term);
    if(strlen($term)>128){$term=substr($term,0,128);}
    if(!in_array($max,fcev_max_choices())){$max=250;}
    return array("term"=>$term,"max"=>$max);
}

// Token translated FIRST, then each %s replaced in order (the raw "{token}" has no %s).
function fcev_tr(string $token):string{
    $tpl=new template_admin();
    $t=$tpl->_ENGINE_parse_body("{".$token."}");
    $args=func_get_args(); array_shift($args);
    foreach($args as $a){ $p=strpos($t,"%s"); if($p===false){break;} $t=substr_replace($t,(string)$a,$p,2); }
    return $t;
}

// Log text is external data: escaped, braces/brackets neutralised so the template engine never
// reads a {token} or a [br] inside it.
function fcev_h($s):string{
    return str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),req_html((string)$s));
}

function fcev_types():array{
    return array(
        "update"  =>array("label"=>"{fcev_t_update}","ico"=>"fas fa-download"),
        "uptodate"=>array("label"=>"{fcev_t_uptodate}","ico"=>"fas fa-database"),
        "error"   =>array("label"=>"{fcev_t_error}","ico"=>"fas fa-circle-xmark"),
        "warning" =>array("label"=>"{fcev_t_warning}","ico"=>"fas fa-triangle-exclamation"),
        "cycle"   =>array("label"=>"{fcev_t_cycle}","ico"=>"fas fa-rotate"),
        "artica"  =>array("label"=>"{fcev_t_artica}","ico"=>"fas fa-shield-halved"),
        "info"    =>array("label"=>"{fcev_t_info}","ico"=>"fas fa-circle-info"),
    );
}

// Type of a freshclam message. Order matters: "Clamd was NOT notified" is a warning, not a cycle.
function fcev_classify(string $msg):string{
    // freshclam's own prefix decides first: "WARNING: Clamd was NOT notified: Can't connect…" is a warning.
    if(preg_match('#ERROR:#',$msg)){return "error";}
    if(preg_match('#^WARNING:|^Warning:#',$msg)){return "warning";}
    if(preg_match("#Can't connect|Can't download|Download failed|failed|Unable to|errno=[0-9]+|Missing entreprise license|is truncated#i",$msg)){return "error";}
    if(preg_match('#WARNING:|Not loading|Disabling|NOT notified|Trying again|retrying|skipping#i',$msg)){return "warning";}
    if(preg_match('#is up[ -]to[ -]date#i',$msg)){return "uptodate";}
    if(preg_match('#\.c[lv]d updated|Database updated#i',$msg)){return "update";}
    if(fcev_is_cycle_start($msg) || preg_match('#successfully notified about the update#i',$msg)){return "cycle";}
    return "info";
}

// The line that opens an update cycle: freshclam's own, or Artica's manual run through freshexec.
function fcev_is_cycle_start(string $msg):bool{
    return preg_match('#update process started at|Running manual update using freshexec#i',$msg)===1;
}

// "Nov 15 03:00:01 host freshclam[123]: msg" (syslog file written by rsyslog) or
// "Sat Nov 15 03:00:01 2026 -> msg" (freshclam's own log). freshclam's LogTime prefix inside a
// syslog message is stripped too. A line matching neither is kept as Info with no time (it used
// to be printed in red ABOVE the table).
function fcev_parse(string $line):array{
    $line=trim($line);
    $ts=0;$msg=$line;$pid="";
    if(preg_match('#^([A-Z][a-z]{2}\s+[0-9]+\s+[0-9:]+)\s+\S+\s+\S+?\[([0-9]+)\]:\s*(.*)$#',$line,$re)){
        $t=strtotime($re[1]); if($t!==false){$ts=$t;}
        $pid=$re[2]; $msg=$re[3];
    }elseif(preg_match('#^(.+?)\s+->\s*(.*)$#',$line,$re)){
        $t=strtotime(trim($re[1])); if($t!==false){$ts=$t;}
        $msg=$re[2];
    }
    if(preg_match('#^[A-Z][a-z]{2} [A-Z][a-z]{2}\s+[0-9]+ [0-9:]+ [0-9]{4} ->\s*(.*)$#',$msg,$re)){ $msg=$re[1]; }
    $msg=trim($msg);
    // Messages written by Artica: "[Artica]: <caller>> text" or "[Artica]: text".
    $artica=false;
    if(preg_match('#^\[Artica\]:\s*(.*)$#',$msg,$re)){
        $artica=true; $msg=trim($re[1]);
        if(preg_match('#^[A-Za-z0-9_./-]+(\.[A-Za-z0-9_]+)?:[0-9]+>\s*(.+)$#',$msg,$re2)){ $msg=trim($re2[2]); }
    }
    $type=fcev_classify($msg);
    return array("ts"=>$ts,"pid"=>$pid,"msg"=>$msg,"raw"=>$line,"type"=>$type,"artica"=>$artica,
        "start"=>fcev_is_cycle_start($msg));
}

// One model per request: rows (newest first), counts, cycles, databases, outcome.
function fcev_model():array{
    $P=fcev_params();
    $M=array("term"=>$P["term"],"max"=>$P["max"],"error"=>"","rows"=>array(),
        "counts"=>array("update"=>0,"uptodate"=>0,"error"=>0,"warning"=>0,"cycle"=>0,"info"=>0,"artica"=>0),
        "oldest"=>0,"newest"=>0,"last_update"=>0,"last_check"=>0,"dbs"=>array(),"outcome"=>array());
    $q=strlen($P["term"])<3?"NONE":$P["term"];
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/freshclam/events/".urlencode(base64_encode($q))."/".$P["max"]);
    if(trim((string)$data)==""){ $M["error"]="{fcev_no_answer}"; return $M; }
    $json=json_decode($data);
    if(!is_object($json)){
        $M["error"]=json_last_error()>JSON_ERROR_NONE?json_last_error_msg():"{fcev_no_answer}";
        return $M;
    }
    if(property_exists($json,"Status") && !$json->Status){
        $M["error"]=isset($json->Error)&&$json->Error!=""?(string)$json->Error:"{fcev_no_answer}";
        return $M;
    }
    $logs=(isset($json->Logs)&&is_array($json->Logs))?$json->Logs:array();
    foreach($logs as $line){
        $line=trim((string)$line);
        if($line=="" || preg_match('#^[\-]+$#',$line)){continue;}
        // A missing freshclam.log comes back as the reader's own error text: "nothing written yet".
        if(preg_match('#^(tail|rg|grep): .*(cannot open|No such file)#i',$line)){continue;}
        $r=fcev_parse($line);
        if($r["msg"]=="" || preg_match('#^[\-]+$#',$r["msg"])){continue;}
        $M["rows"][]=$r;
        $M["counts"][$r["type"]]++;
        if($r["artica"]){$M["counts"]["artica"]++;}
        if($r["ts"]>0){
            if($M["newest"]==0 || $r["ts"]>$M["newest"]){$M["newest"]=$r["ts"];}
            if($M["oldest"]==0 || $r["ts"]<$M["oldest"]){$M["oldest"]=$r["ts"];}
            if($r["type"]=="update" && $r["ts"]>$M["last_update"]){$M["last_update"]=$r["ts"];}
            if(($r["type"]=="update"||$r["type"]=="uptodate") && $r["ts"]>$M["last_check"]){$M["last_check"]=$r["ts"];}
        }
        // Databases: the newest line about each one wins (rows arrive newest first).
        if(preg_match('#^([A-Za-z0-9_-]+)\.(c[lv]d)\s+(database is up[ -]to[ -]date|updated)\s*\(version:\s*([0-9]+),\s*sigs:\s*([0-9]+)#i',$r["msg"],$re)){
            $db=strtolower($re[1]);
            if(!isset($M["dbs"][$db])){
                $M["dbs"][$db]=array("file"=>$re[1].".".$re[2],"version"=>$re[4],"sigs"=>intval($re[5]),"ts"=>$r["ts"],
                    "state"=>stripos($re[3],"updated")!==false?"update":"uptodate");
            }
        }
    }
    $M["groups"]=fcev_groups($M["rows"]);
    $M["outcome"]=fcev_outcome($M);
    // Remembered only when the request was explicit.
    if(isset($_GET["search"])){
        $_SESSION["FRESHCLAM_SEARCH"]=$P["term"];
        $_SESSION["FRESHCLAM_SEARCH_MAX"]=$P["max"];
    }
    return $M;
}

// Rows grouped by update cycle. Newest first, a cycle ENDS (in display order) with its start
// line: everything from the top down to the first start line is the latest cycle.
// Returns, per row index, the group number, and per group its start row (or -1 when the buffer
// cut it off).
function fcev_groups(array $rows):array{
    $g=0;$of=array();$start=array(0=>-1);
    foreach($rows as $i=>$r){
        $of[$i]=$g;
        if($r["start"]){ $start[$g]=$i; $g++; $start[$g]=-1; }
    }
    return array("of"=>$of,"start"=>$start);
}

/**
 * Outcome of the latest cycle, from its lines only: red = errors and no database checked,
 * amber = checked but with errors or warnings, or warnings only, green = checked cleanly,
 * grey = nothing to judge. Not computed while a search filters the lines: a filtered buffer
 * would make any verdict a lie.
 */
function fcev_outcome(array $M):array{
    $O=array("tone"=>"none","title"=>"","detail"=>"");
    if(count($M["rows"])==0){ return $O; }
    if(strlen($M["term"])>=3){ $O["tone"]="search"; return $O; }
    $err=null;$warn=null;$upd=null;$utd=null;$seen=false;
    foreach($M["rows"] as $i=>$r){
        if($M["groups"]["of"][$i]!=0){break;}
        $seen=true;
        if($r["type"]=="error" && $err===null){$err=$r;}
        if($r["type"]=="warning" && $warn===null){$warn=$r;}
        if($r["type"]=="update" && $upd===null){$upd=$r;}
        if($r["type"]=="uptodate" && $utd===null){$utd=$r;}
    }
    if(!$seen){ return $O; }
    $ok=($upd!==null||$utd!==null);
    if($err!==null && !$ok){ $O["tone"]="err"; $O["row"]=$err; return $O; }
    if($ok && ($err!==null||$warn!==null)){ $O["tone"]="mixed"; $O["row"]=$err!==null?$err:$warn; $O["ok"]=$upd!==null?$upd:$utd; return $O; }
    if($upd!==null){ $O["tone"]="updated"; $O["row"]=$upd; return $O; }
    if($utd!==null){ $O["tone"]="uptodate"; $O["row"]=$utd; return $O; }
    if($warn!==null){ $O["tone"]="warn"; $O["row"]=$warn; return $O; }
    return $O;
}

function fcev_age(int $ts):string{
    if($ts<=0){return "";}
    $d=time()-$ts;
    if($d<0){$d=0;}
    if($d<60){return fcev_tr("fcev_age_now");}
    if($d<3600){return fcev_tr("fcev_age_min",intval($d/60));}
    if($d<86400){return fcev_tr("fcev_age_hours",intval($d/3600));}
    return fcev_tr("fcev_age_days",intval($d/86400));
}

function fcev_short_date(int $ts):string{
    if($ts<=0){return "—";}
    return date("Y",$ts)==date("Y")?date("M j",$ts):date("M j Y",$ts);
}

// ---- page shell ------------------------------------------------------------------------------

function page(){
    $tpl=new template_admin();
    $P=fcev_params();
    $html=array();
    $html[]="<div class='fcev' id='fcev-root'>";
    $html[]="<p class='fcev-intro'>{fcev_intro}</p>";
    $html[]="<div id='fcev-banner'></div>";
    $html[]=fcev_search_card($P);
    $html[]="<div id='fcev-results'></div>";
    $html[]="</div>";
    $q="&max=".$P["max"]."&search=".urlencode($P["term"]);
    echo fcev_style().$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>".fcev_js()."FcEvLoad('".$q."');</script>";
}

function fcev_search_card(array $P):string{
    $opts=array();
    foreach(fcev_max_choices() as $n){
        $sel=$n==$P["max"]?" selected":"";
        $opts[]="<option value='$n'$sel>".fcev_tr("fcev_n_lines",$n)."</option>";
    }
    $v=fcev_h($P["term"]);
    return "<div class='fcev-card fcev-search'>
  <div class='fcev-search-row'>
    <div class='fcev-field fcev-grow'>
      <label for='fcev-q' class='fcev-label'>{fcev_search}</label>
      <div class='fcev-input'><span class='fcev-input-ico' aria-hidden='true'><i class='fas fa-magnifying-glass'></i></span>
      <input type='text' id='fcev-q' value=\"$v\" maxlength='128' placeholder=\"{fcev_search_ph}\" onkeydown=\"FcEvKey(event)\"></div>
      <div class='fcev-help'>{fcev_search_help}</div>
    </div>
    <div class='fcev-field fcev-lines'>
      <label for='fcev-max' class='fcev-label'>{fcev_lines}</label>
      <select id='fcev-max' onchange='FcEvGo()'>".implode("",$opts)."</select>
      <div class='fcev-help'>{fcev_lines_help}</div>
    </div>
    <div class='fcev-field fcev-btns'>
      <span class='fcev-label'>&nbsp;</span>
      <div class='fcev-btnrow'>
      <button type='button' class='fcev-btn' onclick='FcEvGo()'><span aria-hidden='true'><i class='fas fa-magnifying-glass'></i></span> {fcev_search_btn}</button>
      <button type='button' class='fcev-btn-sec' onclick='FcEvClear()'>{fcev_clear}</button>
      </div>
    </div>
  </div>
  <div class='fcev-chips' id='fcev-chips' style='display:none'></div>
</div>";
}

// ---- results (?search=) ----------------------------------------------------------------------

function search(){
    $tpl=new template_admin();
    $M=fcev_model();
    $html=array();
    $html[]="<div id='fcev-banner-src' hidden>".fcev_banner($M)."</div>";
    if($M["error"]!=""){
        $html[]=fcev_error($M);
    }else{
        $html[]=fcev_kpis($M);
        $html[]=fcev_dbs($M);
        $html[]=fcev_table($M);
    }
    $html[]=fcev_state($M);
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>FcEvBanner();".fcev_tiny()."FcEvChips();FcEvApply();</script>";
}

function fcev_tiny():string{
    $tpl=new template_admin();
    // The same action as the status tab's "Update ClamAV Databases" button; this tab reloads
    // its own lines when the progress ends.
    $run=$tpl->framework_buildjs("/freshclam/run","clamav.update.progress","clamav.update.progress.txt",
        "progress-clamav-restart","FcEvGo();");
    $topbuttons=array();
    $topbuttons[]=array($run,ico_download,"{udate_clamav_databases}");
    $topbuttons[]=array("FcEvGo()",ico_refresh,"{refresh}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{APP_CLAMAV}: {update_events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{fcev_explain}";
    $TINY_ARRAY["URL"]="clamav";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}

// Hidden carrier read by the JS: current server-side filters, for the chips.
function fcev_state(array $M):string{
    return "<span id='fcev-state' hidden data-term=\"".fcev_h($M["term"])."\" data-max='".intval($M["max"])."'></span>";
}

function fcev_error(array $M):string{
    $err=$M["error"];
    if(strpos($err,"{")!==0){$err=fcev_h($err);}
    return "<div class='fcev-alert' role='alert'><span class='fcev-alert-ico' aria-hidden='true'><i class='fas fa-plug-circle-xmark'></i></span>
<div><strong>{fcev_unreachable}</strong><div class='fcev-help fcev-mono'>$err</div></div></div>";
}

// Outcome banner (moved above the search card by FcEvBanner()).
function fcev_banner(array $M):string{
    if($M["error"]!=""){ return ""; }
    $O=$M["outcome"];
    $n=count($M["rows"]);
    $ctx="";
    if($n>0){
        $ctx=fcev_tr("fcev_in_lines",$n);
        if($M["oldest"]>0){
            $ctx.=" — ".fcev_tr("fcev_from_to",fcev_short_date($M["oldest"])." ".date("H:i",$M["oldest"]),fcev_short_date($M["newest"])." ".date("H:i",$M["newest"]));
        }
    }
    $tone=$O["tone"]; $title=""; $tip="";
    $icons=array("updated"=>"fas fa-circle-check","uptodate"=>"fas fa-circle-check","mixed"=>"fas fa-triangle-exclamation",
        "warn"=>"fas fa-triangle-exclamation","err"=>"fas fa-circle-xmark","none"=>"fas fa-circle-info","search"=>"fas fa-filter");
    $cls=array("updated"=>"ok","uptodate"=>"ok","mixed"=>"warn","warn"=>"warn","err"=>"err","none"=>"neutral","search"=>"neutral");
    $r=isset($O["row"])?$O["row"]:null;
    if($tone=="updated"){
        $db=fcev_db_of($r["msg"]);
        $title=$db===null?fcev_tr("fcev_o_updated",fcev_age($r["ts"])):fcev_tr("fcev_o_updated_db",fcev_age($r["ts"]),fcev_h($db[0]),fcev_h($db[1]));
    }elseif($tone=="uptodate"){
        $title=fcev_tr("fcev_o_uptodate",fcev_age($r["ts"]));
    }elseif($tone=="mixed"){
        $title=fcev_tr("fcev_o_mixed",fcev_age($O["ok"]["ts"]));
        $tip=$r["msg"];
    }elseif($tone=="warn"){
        $title=fcev_tr("fcev_o_warn",fcev_age($r["ts"]));
        $tip=$r["msg"];
    }elseif($tone=="err"){
        $title=fcev_tr("fcev_o_err",fcev_age($r["ts"]));
        $tip=$r["msg"];
    }elseif($tone=="search"){
        $title="{fcev_o_search}";
    }else{
        $title=$n==0?"{fcev_o_empty}":"{fcev_o_none}";
    }
    $detail="";
    if($tip!==""){
        $short=strlen($tip)>140?substr($tip,0,137)."…":$tip;
        $detail="<div class='fcev-ob-why fcev-mono' title=\"".fcev_h($tip)."\">".fcev_h($short)."</div>";
    }
    return "<div class='fcev-ob fcev-ob-{$cls[$tone]}' role='status'>
<span class='fcev-ob-ico' aria-hidden='true'><i class='{$icons[$tone]}'></i></span>
<div class='fcev-ob-b'><div class='fcev-ob-t'>$title</div>$detail".($ctx==""?"":"<div class='fcev-ob-ctx'>$ctx</div>")."</div></div>";
}

// "daily.cld updated (version: 27455, …" -> array("daily.cld","27455"), null otherwise.
function fcev_db_of(string $msg){
    if(preg_match('#^([A-Za-z0-9_-]+\.c[lv]d)\s.*?version:\s*([0-9]+)#i',$msg,$re)){ return array($re[1],$re[2]); }
    return null;
}

// Six cards: five filters (aria-pressed, disabled at 0) and the last successful update.
function fcev_kpis(array $M):string{
    if(count($M["rows"])==0){ return ""; }
    $c=array();
    foreach(array("update","uptodate","error","warning","artica") as $k){ $c[]=fcev_kpi($M,$k); }
    $c[]=fcev_kpi_last($M);
    return "<div class='fcev-kpis'>".implode("",$c)."</div>";
}

function fcev_kpi(array $M,string $k):string{
    $T=fcev_types();
    $n=intval($M["counts"][$k]);
    $labels=array("update"=>"{fcev_k_updates}","uptodate"=>"{fcev_k_uptodate}","error"=>"{fcev_k_errors}",
        "warning"=>"{fcev_k_warnings}","artica"=>"{fcev_k_artica}");
    $subs=array("update"=>"{fcev_ks_updates}","uptodate"=>"{fcev_ks_uptodate}","error"=>"{fcev_ks_errors}",
        "warning"=>"{fcev_ks_warnings}","artica"=>"{fcev_ks_artica}");
    $tone=$n>0?" fcev-k-$k":" fcev-k-zero";
    $dis=$n==0?" disabled title=\"{fcev_k_none}\"":"";
    return "<button type='button' class='fcev-kpi$tone' data-type='$k' aria-pressed='false' onclick=\"FcEvType('$k')\"$dis>
<span class='fcev-kpi-h'><span class='fcev-kpi-ico' aria-hidden='true'><i class='{$T[$k]["ico"]}'></i></span><span class='fcev-kpi-l'>{$labels[$k]}</span></span>
<span class='fcev-kpi-v'><span class='fcev-kpi-n'>$n</span><span class='fcev-kpi-s'>{$subs[$k]}</span></span></button>";
}

function fcev_kpi_last(array $M):string{
    $ts=intval($M["last_update"]);
    if($ts>0){
        $v=fcev_age($ts);
        $sub=fcev_short_date($ts)." ".date("H:i",$ts);
        $tone=" fcev-k-update";
    }else{
        $v="{fcev_none_here}";
        $sub=$M["last_check"]>0?fcev_tr("fcev_last_check",fcev_age($M["last_check"])):"";
        $tone=" fcev-k-zero";
    }
    return "<div class='fcev-kpi fcev-kpi-static$tone'>
<span class='fcev-kpi-h'><span class='fcev-kpi-ico' aria-hidden='true'><i class='fas fa-clock'></i></span><span class='fcev-kpi-l'>{fcev_k_last}</span></span>
<span class='fcev-kpi-v'><span class='fcev-kpi-t'>$v</span><span class='fcev-kpi-s'>$sub</span></span></div>";
}

// Databases named in the lines shown, as the newest line about each one says.
function fcev_dbs(array $M):string{
    if(count($M["dbs"])==0){ return ""; }
    $chips=array();
    foreach($M["dbs"] as $name=>$d){
        $st=$d["state"]=="update"?"{fcev_db_updated}":"{fcev_db_uptodate}";
        $when=$d["ts"]>0?" <span class='fcev-db-when'>".fcev_age($d["ts"])."</span>":"";
        $chips[]="<span class='fcev-db' title=\"".fcev_h($d["file"])."\"><span class='fcev-db-dot' aria-hidden='true'></span>"
            ."<span><strong>".fcev_h($name)."</strong> · v".fcev_h($d["version"])." · ".fcev_tr("fcev_n_sigs",number_format($d["sigs"]))."</span>"
            ."<span class='fcev-db-st'>$st$when</span></span>";
    }
    return "<div class='fcev-card fcev-dbs'><span class='fcev-dbs-l'><span aria-hidden='true'><i class='fas fa-layer-group'></i></span> {fcev_dbs}</span>"
        ."<div class='fcev-dbs-c'>".implode("",$chips)."</div><span class='fcev-help fcev-dbs-n'>{fcev_dbs_help}</span></div>";
}

function fcev_table(array $M):string{
    if(count($M["rows"])==0){ return fcev_empty($M); }
    $rows=array();
    $G=$M["groups"];
    $prev=-1;
    foreach($M["rows"] as $i=>$r){
        $g=$G["of"][$i];
        if($g!==$prev){
            $prev=$g;
            $s=$G["start"][$g];
            if($s>=0){ $rows[]=fcev_divider($M["rows"][$s],$g); }
        }
        $rows[]=fcev_row($r,$g);
    }
    return "<div class='fcev-card fcev-tablecard'>
<div class='fcev-th'><h3>{fcev_stream}</h3><span class='fcev-th-n' id='fcev-shown'></span></div>
<table class='fcev-table'>
<thead><tr><th class='fcev-c-time'>{fcev_time}</th><th class='fcev-c-type'>{fcev_type}</th><th>{fcev_message}</th></tr></thead>
<tbody id='fcev-tbody'>".implode("\n",$rows)."</tbody></table>
<div class='fcev-empty' id='fcev-type-empty' style='display:none'>
  <span class='fcev-empty-ico' aria-hidden='true'><i class='fas fa-filter'></i></span>
  <h4 id='fcev-type-empty-t'></h4><p>{fcev_look_further}</p>
</div>
<div class='fcev-foot'><span>{fcev_source} <span class='fcev-mono'>".fcev_logfile()."</span></span><span>".fcev_tr("fcev_read_n",count($M["rows"]))."</span></div>
</div>";
}

// Divider above the lines of one update cycle.
function fcev_divider(array $start,int $g):string{
    $when=$start["ts"]>0?fcev_short_date($start["ts"])." ".date("H:i",$start["ts"]):"—";
    $pid=$start["pid"]!==""?" <span class='fcev-div-pid'>freshclam[".fcev_h($start["pid"])."]</span>":"";
    return "<tr class='fcev-div' data-g='$g'><td colspan='3'><span aria-hidden='true'><i class='fas fa-rotate'></i></span> ".fcev_tr("fcev_cycle_started",$when).$pid."</td></tr>";
}

function fcev_badge(array $r):string{
    $T=fcev_types();
    $type=$r["type"];
    if($r["artica"] && ($type=="info"||$type=="cycle")){ $type="artica"; }
    return "<span class='fcev-badge fcev-b-$type'><span aria-hidden='true'><i class='{$T[$type]["ico"]}'></i></span> {$T[$type]["label"]}</span>";
}

// Message: escaped, database file and version highlighted, Artica origin as a small pill when
// the badge already carries the severity.
function fcev_message_cell(array $r):string{
    $m=fcev_h($r["msg"]);
    $m=preg_replace('#^([A-Za-z0-9_-]+\.c[lv]d)\b#','<strong>$1</strong>',$m);
    $m=preg_replace('#(version:\s*)([0-9]+)#','$1<span class="fcev-ver">$2</span>',$m);
    $pre="";
    if($r["artica"] && $r["type"]!="info" && $r["type"]!="cycle"){ $pre="<span class='fcev-pill-artica'>{fcev_t_artica}</span> "; }
    return $pre.$m;
}

function fcev_row(array $r,int $g):string{
    if($r["ts"]>0){
        $title=fcev_h(date("D M j H:i:s Y",$r["ts"]));
        $time="<span class='fcev-age'>".fcev_age($r["ts"])."</span><span class='fcev-day'>".fcev_short_date($r["ts"])." ".date("H:i:s",$r["ts"])."</span>";
    }else{
        $title="";
        $time="<span class='fcev-day'>—</span>";
    }
    $types=$r["type"].($r["artica"]?" artica":"");
    $edge=$r["type"]=="error"?" fcev-r-err":($r["type"]=="warning"?" fcev-r-warn":"");
    return "<tr class='fcev-row$edge' data-type='$types' data-g='$g'>
<td class='fcev-c-time' title=\"$title\">$time</td>
<td class='fcev-c-type'>".fcev_badge($r)."</td>
<td class='fcev-msg fcev-m-{$r["type"]}' title=\"".fcev_h($r["raw"])."\">".fcev_message_cell($r)."</td>
</tr>";
}

function fcev_empty(array $M):string{
    if(strlen($M["term"])>=3){
        return "<div class='fcev-card fcev-empty'>
  <span class='fcev-empty-ico' aria-hidden='true'><i class='fas fa-magnifying-glass'></i></span>
  <h4>".fcev_tr("fcev_no_match",fcev_h($M["term"]))."</h4><p>{fcev_no_match_help}</p>
  <button type='button' class='fcev-btn-sec' onclick='FcEvClear()'>{fcev_clear_filter}</button></div>";
    }
    return "<div class='fcev-card fcev-empty'>
  <span class='fcev-empty-ico' aria-hidden='true'><i class='fas fa-file-lines'></i></span>
  <h4>{fcev_empty_title}</h4><p>{fcev_empty_help}</p></div>";
}

// ---- assets ----------------------------------------------------------------------------------

function fcev_plain($tok):string{
    $tpl=new template_admin();
    return trim(strip_tags(html_entity_decode($tpl->_ENGINE_parse_body($tok),ENT_QUOTES,"UTF-8")));
}

function fcev_js():string{
    $page=CurrentPageName();
    $labels=array("update"=>"{fcev_k_updates}","uptodate"=>"{fcev_k_uptodate}","error"=>"{fcev_k_errors}",
        "warning"=>"{fcev_k_warnings}","artica"=>"{fcev_k_artica}");
    foreach($labels as $k=>$v){ $labels[$k]=fcev_plain($v); }
    $txt=array("type"=>"{fcev_chip_type}","text"=>"{fcev_chip_text}","active"=>"{fcev_active_filters}",
        "clear"=>"{fcev_clear_all}","remove"=>"{fcev_remove_filter}","notype"=>"{fcev_no_type_lines}","shown"=>"{fcev_showing}");
    foreach($txt as $k=>$v){ $txt[$k]=fcev_plain($v); }
    $J=json_encode(array("labels"=>$labels,"t"=>$txt));
    return "
window.FcEvI18n=$J; if(typeof window.FcEvTypeF==='undefined'){window.FcEvTypeF='';}
function FcEvQS(){var q=document.getElementById('fcev-q'),m=document.getElementById('fcev-max');
  return '&max='+encodeURIComponent(m?m.value:'250')+'&search='+encodeURIComponent(q?q.value:'');}
function FcEvLoad(qs){LoadAjax('fcev-results','$page?r=1'+qs);}
function FcEvGo(){if(!document.getElementById('fcev-results')){return;}FcEvLoad(FcEvQS());}
function FcEvKey(e){if(e&&(e.keyCode==13)){e.preventDefault();FcEvGo();}}
function FcEvClear(){var q=document.getElementById('fcev-q');if(q){q.value='';}window.FcEvTypeF='';FcEvGo();}
function FcEvClearText(){var q=document.getElementById('fcev-q');if(q){q.value='';}FcEvGo();}
function FcEvType(k){window.FcEvTypeF=(window.FcEvTypeF===k)?'':k;FcEvApply();FcEvChips();}
function FcEvEsc(s){var d=document.createElement('div');d.textContent=String(s);return d.innerHTML;}
function FcEvBanner(){var s=document.getElementById('fcev-banner-src'),d=document.getElementById('fcev-banner');
  if(!d){return;}d.innerHTML=s?s.innerHTML:'';if(s){s.parentNode.removeChild(s);}}
function FcEvApply(){
  var f=window.FcEvTypeF||'',tb=document.getElementById('fcev-tbody'),shown=0,total=0,vis={};
  var ks=document.querySelectorAll('.fcev-kpi[data-type]');
  for(var i=0;i<ks.length;i++){ks[i].setAttribute('aria-pressed',ks[i].getAttribute('data-type')===f?'true':'false');}
  if(!tb){return;}
  for(var j=0;j<tb.rows.length;j++){var r=tb.rows[j];if(r.className.indexOf('fcev-div')>=0){continue;}
    total++;var t=' '+(r.getAttribute('data-type')||'')+' ',ok=(f===''||t.indexOf(' '+f+' ')>=0);
    r.style.display=ok?'':'none';if(ok){shown++;vis[r.getAttribute('data-g')]=1;}}
  for(var k=0;k<tb.rows.length;k++){var d=tb.rows[k];if(d.className.indexOf('fcev-div')<0){continue;}
    d.style.display=vis[d.getAttribute('data-g')]?'':'none';}
  var I=window.FcEvI18n,n=document.getElementById('fcev-shown');
  if(n){n.textContent='· '+I.t.shown.replace('%s',shown).replace('%s',total);}
  var e=document.getElementById('fcev-type-empty');if(!e){return;}
  if(f!==''&&shown===0){document.getElementById('fcev-type-empty-t').textContent=I.t.notype.replace('%s',I.labels[f]||f);e.style.display='';}
  else{e.style.display='none';}
}
function FcEvChips(){
  var c=document.getElementById('fcev-chips');if(!c){return;}
  var I=window.FcEvI18n,st=document.getElementById('fcev-state'),term=st?st.getAttribute('data-term'):'',h=[];
  var tmp=document.createElement('textarea');tmp.innerHTML=term||'';term=tmp.value;
  if(term&&term.length>=3){h.push('<span class=\"fcev-chip\">'+FcEvEsc(I.t.text)+': <strong>'+FcEvEsc(term)+'</strong> <button type=\"button\" aria-label=\"'+FcEvEsc(I.t.remove+' '+term)+'\" onclick=\"FcEvClearText()\">&times;</button></span>');}
  if(window.FcEvTypeF){var L=I.labels[window.FcEvTypeF]||'';h.push('<span class=\"fcev-chip fcev-chip-t\">'+FcEvEsc(I.t.type)+': <strong>'+FcEvEsc(L)+'</strong> <button type=\"button\" aria-label=\"'+FcEvEsc(I.t.remove+' '+L)+'\" onclick=\"FcEvType(window.FcEvTypeF)\">&times;</button></span>');}
  if(h.length===0){c.innerHTML='';c.style.display='none';return;}
  c.style.display='';c.innerHTML='<span class=\"fcev-chips-l\">'+FcEvEsc(I.t.active)+'</span>'+h.join('')+'<button type=\"button\" class=\"fcev-link\" onclick=\"FcEvClear()\">'+FcEvEsc(I.t.clear)+'</button>';
}
";
}

function fcev_style():string{
    $a="#0d9488";$ad="#0f766e";$as="rgba(13,148,136,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $b=brand_ui_accents(brand_palette());
        if(!empty($b["accent"])){$a=$b["accent"];}
        if(!empty($b["accent_dark"])){$ad=$b["accent_dark"];}
        if(!empty($b["accent_soft"])){$as=$b["accent_soft"];}
    }
    return "<style>
.fcev{color:#111d23;font-size:14px;margin-top:12px}
.fcev *{box-sizing:border-box}
.fcev-intro{font-size:14px;color:#3d4947;margin:0 0 16px;line-height:1.5}
.fcev-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:16px}
.fcev-help{font-size:13px;color:#6d7a77;line-height:1.45}
.fcev-mono{font-family:'JetBrains Mono',Consolas,monospace;font-size:13px}
.fcev-ob{display:flex;align-items:center;gap:16px;padding:16px 20px;border-radius:12px;border:1px solid;margin-bottom:16px}
.fcev-ob-ico{width:40px;height:40px;flex:0 0 40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff}
.fcev-ob-b{min-width:0}
.fcev-ob-t{font-size:17px;font-weight:700;line-height:1.3}
.fcev-ob-why{margin-top:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.fcev-ob-ctx{font-size:13px;margin-top:3px;opacity:.9}
.fcev-ob-ok{background:rgba(16,185,129,.12);border-color:rgba(4,120,87,.3);color:#047857}.fcev-ob-ok .fcev-ob-ico{background:#047857}
.fcev-ob-warn{background:#fff4e5;border-color:#ffddb9;color:#865300}.fcev-ob-warn .fcev-ob-ico{background:#865300}
.fcev-ob-err{background:#ffdad6;border-color:rgba(186,26,26,.3);color:#93000a}.fcev-ob-err .fcev-ob-ico{background:#ba1a1a}
.fcev-ob-neutral{background:#e3f0f8;border-color:#c9dde9;color:#3d4947}.fcev-ob-neutral .fcev-ob-ico{background:#6d7a77}
.fcev-search{padding:16px}
.fcev-search-row{display:flex;gap:16px;align-items:flex-start}
.fcev-field{display:flex;flex-direction:column;gap:4px;min-width:0}
.fcev-grow{flex:1}
.fcev-lines{width:220px;flex-shrink:0}
.fcev-btns{flex-shrink:0}
.fcev-btnrow{display:flex;gap:8px}
.fcev-label{font-size:13px;font-weight:600;color:#3d4947;margin:0;display:block;line-height:17px}
.fcev-input{position:relative}
.fcev-input-ico{position:absolute;left:12px;top:0;height:40px;display:flex;align-items:center;color:#6d7a77;font-size:14px;pointer-events:none}
.fcev-input input,.fcev-lines select{width:100%;height:40px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;color:#111d23;background:#fff;outline:none}
.fcev-input input{padding:0 12px 0 36px}
.fcev-lines select{padding:0 10px}
.fcev-input input:focus,.fcev-lines select:focus{border-color:$a;box-shadow:0 0 0 2px $as}
.fcev-btn{height:40px;padding:0 18px;border:0;border-radius:6px;background:$a;color:#fff;font-weight:600;font-size:14px;display:inline-flex;align-items:center;gap:8px;cursor:pointer;white-space:nowrap}
.fcev-btn:hover{background:$ad}
.fcev-btn-sec{height:40px;padding:0 14px;border:1px solid #cbd5e1;border-radius:6px;background:#fff;color:#3d4947;font-weight:600;font-size:14px;cursor:pointer}
.fcev-empty .fcev-btn-sec{margin-top:12px}
.fcev button:focus-visible{outline:2px solid #006a62;outline-offset:2px}
.fcev-chips{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9}
.fcev-chips-l{font-size:13px;font-weight:600;color:#6d7a77;text-transform:uppercase;letter-spacing:.03em;margin-right:4px}
.fcev-chip{display:inline-flex;align-items:center;gap:4px;padding:3px 4px 3px 12px;border-radius:999px;background:#f1f5f9;border:1px solid #e2e8f0;font-size:13px}
.fcev-chip-t{background:#fff4f2;border-color:#ffdad6;color:#93000a}
.fcev-chip button{border:0;background:transparent;color:inherit;font-size:16px;line-height:1;padding:0 6px;cursor:pointer}
.fcev-link{border:0;background:transparent;color:$ad;font-size:13px;font-weight:600;cursor:pointer;margin-left:4px}
.fcev-kpis{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin-bottom:16px}
.fcev-kpi{display:flex;flex-direction:column;gap:8px;min-height:88px;padding:12px 14px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;text-align:left;font:inherit;color:inherit;cursor:pointer}
.fcev-kpi:hover:not([disabled]):not(.fcev-kpi-static){border-color:#cbd5e1;background:#fbfdfe}
.fcev-kpi[disabled]{cursor:default}
.fcev-kpi-static{cursor:default}
.fcev-kpi-h{display:flex;align-items:center;gap:8px}
.fcev-kpi-ico{font-size:14px;color:#94a3b8}
.fcev-kpi-l{font-size:13px;font-weight:600;color:#3d4947}
.fcev-kpi-v{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap}
.fcev-kpi-n{font-size:22px;font-weight:700;line-height:1.1}
.fcev-kpi-t{font-size:18px;font-weight:700;line-height:1.2}
.fcev-kpi-s{font-size:13px;color:#6d7a77}
.fcev-k-zero .fcev-kpi-n,.fcev-k-zero .fcev-kpi-t{color:#94a3b8}
.fcev-k-update .fcev-kpi-ico,.fcev-k-update .fcev-kpi-l,.fcev-k-update .fcev-kpi-n,.fcev-k-update .fcev-kpi-t{color:#047857}
.fcev-k-uptodate .fcev-kpi-ico{color:#3d4947}
.fcev-k-error .fcev-kpi-ico,.fcev-k-error .fcev-kpi-l,.fcev-k-error .fcev-kpi-n{color:#ba1a1a}
.fcev-k-warning .fcev-kpi-ico,.fcev-k-warning .fcev-kpi-l,.fcev-k-warning .fcev-kpi-n{color:#865300}
.fcev-k-artica .fcev-kpi-ico,.fcev-k-artica .fcev-kpi-l{color:$ad}
.fcev-kpi[aria-pressed=true]{border:2px solid $a;background:$as;padding:11px 13px}
.fcev-k-error[aria-pressed=true]{border-color:#ba1a1a;background:#fff4f2}
.fcev-k-warning[aria-pressed=true]{border-color:#865300;background:#fff8ef}
.fcev-dbs{display:flex;align-items:center;gap:16px;padding:12px 16px;flex-wrap:wrap}
.fcev-dbs-l{font-size:13px;font-weight:600;color:#3d4947;text-transform:uppercase;letter-spacing:.03em;white-space:nowrap}
.fcev-dbs-l i,.fcev-dbs-l svg{color:#94a3b8}
.fcev-dbs-c{display:flex;flex-wrap:wrap;gap:8px}
.fcev-db{display:inline-flex;align-items:center;gap:8px;padding:4px 12px;border-radius:999px;background:#f1f5f9;border:1px solid #e2e8f0;font-family:'JetBrains Mono',Consolas,monospace;font-size:13px;white-space:nowrap}
.fcev-db-dot{width:8px;height:8px;border-radius:50%;background:#10b981;display:inline-block}
.fcev-db-st{font-family:inherit;color:#6d7a77;border-left:1px solid #cbd5e1;padding-left:8px}
.fcev-dbs-n{font-style:italic;margin-left:auto}
.fcev-tablecard{overflow:hidden}
.fcev-th{display:flex;align-items:baseline;gap:8px;padding:14px 16px;border-bottom:1px solid #e2e8f0}
.fcev-th h3{margin:0;font-size:16px;font-weight:600;color:#111d23}
.fcev-th-n{font-size:13px;color:#6d7a77}
.fcev-table{width:100%;border-collapse:collapse;table-layout:fixed;color:#111d23}
.fcev-table th{background:#f8fafc;font-size:13px;font-weight:600;color:#3d4947;text-align:left;padding:10px 16px}
.fcev-table td{padding:10px 16px;border-top:1px solid #eef2f6;vertical-align:middle;color:#111d23}
.fcev-c-time{width:150px}
.fcev-c-type{width:150px}
.fcev-age{display:block;font-size:14px;color:#111d23}
.fcev-day{display:block;font-size:13px;color:#6d7a77;white-space:nowrap}
.fcev-row:hover td{background:#f8fafc}
.fcev-r-err td:first-child{box-shadow:inset 3px 0 0 #ba1a1a}
.fcev-r-warn td:first-child{box-shadow:inset 3px 0 0 #d97706}
.fcev-msg{font-family:'JetBrains Mono',Consolas,monospace;font-size:13px;line-height:1.5;color:#3d4947;overflow-wrap:anywhere}
.fcev-msg strong{color:#111d23}
.fcev-m-error{color:#93000a}.fcev-m-warning{color:#653e00}.fcev-m-update{color:#047857}
.fcev-ver{color:$ad;font-weight:600}
.fcev-pill-artica{display:inline-block;padding:0 7px;border-radius:999px;background:$as;color:$ad;font-family:inherit;font-size:13px;font-weight:600}
.fcev-badge{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:13px;font-weight:600;white-space:nowrap;border:1px solid transparent}
.fcev-b-update{background:rgba(16,185,129,.12);color:#047857;border-color:rgba(4,120,87,.2)}
.fcev-b-uptodate{background:#f1f5f9;color:#3d4947;border-color:#e2e8f0}
.fcev-b-error{background:#ffdad6;color:#93000a}
.fcev-b-warning{background:#ffddb9;color:#653e00}
.fcev-b-cycle{background:#e3f0f8;color:#3d4947}
.fcev-b-artica{background:$as;color:$ad}
.fcev-b-info{background:#f1f5f9;color:#3d4947}
.fcev-div td{background:#f1f5f9;font-size:13px;font-weight:600;color:#3d4947;padding:8px 16px}
.fcev-div i,.fcev-div svg{color:$ad;margin-right:4px}
.fcev-div-pid{font-family:'JetBrains Mono',Consolas,monospace;font-weight:400;color:#6d7a77;margin-left:8px}
.fcev-foot{display:flex;justify-content:space-between;gap:12px;padding:12px 16px;border-top:1px solid #e2e8f0;font-size:13px;color:#6d7a77}
.fcev-empty{text-align:center;padding:40px 24px}
.fcev-empty-ico{display:inline-flex;width:56px;height:56px;border-radius:50%;background:#f1f5f9;color:#6d7a77;align-items:center;justify-content:center;font-size:24px;margin-bottom:12px}
.fcev-empty h4{font-size:16px;font-weight:600;margin:0 0 6px;color:#111d23}
.fcev-empty p{font-size:14px;color:#3d4947;margin:0 auto;max-width:520px}
.fcev-alert{display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border:1px solid rgba(186,26,26,.3);background:#ffdad6;border-radius:12px;margin-bottom:16px;color:#93000a}
.fcev-alert-ico{font-size:22px;line-height:1}
.fcev-alert strong{font-size:15px}
@media (max-width:1200px){.fcev-kpis{grid-template-columns:repeat(3,minmax(0,1fr))}.fcev-search-row{flex-wrap:wrap}.fcev-dbs-n{margin-left:0}}
@media (prefers-reduced-motion:reduce){.fcev *{transition:none!important}}
</style>";
}
