<?php
// Onglet "Events" de l'agent Zabbix (fw.system.zabbix.php) : journal /var/log/zabbix/zabbix_agentd.log.
// Maquette stitch_zabbix_agent_log_viewer. Prefixe de page : zbxev_.
// Source unique : logfile_search("zabbix-agent") -> /system/logfile/search (lignes, la plus recente d'abord).
// La severite n'existe pas dans ce journal : elle est DEDUITE du message (zbxev_severity()).
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// Meme droit que la garde centrale des pages fw.system.* (class.privs.guard.inc).
$users=new usersMenus();if(!$users->AsSystemAdministrator){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
page();

// ---------------------------------------------------------------- helpers

function zbxev_t(string $k,array $args=array()):string{
    $tpl=new template_admin();
    $s=trim(preg_replace('/\s+/',' ',str_replace(array("[br]","<br>")," ",$tpl->_ENGINE_parse_body("{".$k."}"))));
    foreach($args as $a){
        $p=strpos($s,"%s");
        if($p===false){break;}
        $s=substr($s,0,$p).$a.substr($s,$p+2);
    }
    return $s;
}

function zbxev_lines_allowed():array{
    return array(50,250,1000,1500);
}

function zbxev_lines($v):int{
    $v=intval($v);
    if(!in_array($v,zbxev_lines_allowed(),true)){return 250;}
    return $v;
}

function zbxev_term($v):string{
    $v=trim((string)$v);
    $v=preg_replace('/[^A-Za-z0-9 _.:*@#\[\]\/=-]/',"",$v);
    return substr($v,0,120);
}

// Severite deduite du message, premiere regle qui correspond.
function zbxev_severity(string $msg):string{
    if(preg_match('/failed:|ERROR:|errno=[0-9]+|started to fail|\bcannot\b|\bfailed\b|rejected/i',$msg)){return "error";}
    if(preg_match('/\s+no\s+|\bstopped\b|Disabling|Not loading|connection closed|\bwarning\b|Got signal/i',$msg)){return "warning";}
    if(preg_match('/\bstarted\b|Starting Zabbix|Database updated|is working again/i',$msg)){return "started";}
    return "info";
}

// Message echappe, segments [..] et adresses ip[:port] en monospace (une seule passe, sans chevauchement).
function zbxev_message_html(string $msg):string{
    $h=req_html($msg);
    return preg_replace_callback('/(\[[^\]]{1,160}\])|(\b[0-9]{1,3}(?:\.[0-9]{1,3}){3}(?::[0-9]{1,5})?\b)/',function($m){
        $cls=($m[1]!=="")?"zbxev-tok zbxev-tok-b":"zbxev-tok";
        return "<span class='$cls'>".$m[0]."</span>";
    },$h);
}

// ---------------------------------------------------------------- model

function zbxev_model(string $term,int $lines):array{
    $tpl=new template_admin();
    $m=array("term"=>$term,"lines"=>$lines,"rows"=>array(),"error"=>"",
        "counts"=>array("all"=>0,"error"=>0,"warning"=>0,"started"=>0,"info"=>0));
    $MAIN=$tpl->format_search_protocol($term);
    if(!is_array($MAIN)){$MAIN=array();}
    $MAIN["MAX"]=$lines;
    $error="";
    // zabbix_agentd.log ne commence pas par une date syslog : pas de filtre de date.
    $data=$tpl->logfile_search("zabbix-agent",$MAIN,false,$lines,$error);
    $m["error"]=(string)$error;
    foreach($data as $line){
        $line=rtrim((string)$line);
        if(trim($line)==""){continue;}
        $r=array("pid"=>"","ts"=>0,"day"=>"","time"=>"","msg"=>trim($line));
        if(preg_match('/^\s*([0-9]+):([0-9]{4})([0-9]{2})([0-9]{2}):([0-9]{2})([0-9]{2})([0-9]{2})(?:\.[0-9]+)?\s+(.*)$/',$line,$re)){
            $r["pid"]=$re[1];
            $r["day"]="$re[2]-$re[3]-$re[4]";
            $r["time"]="$re[5]:$re[6]:$re[7]";
            $ts=strtotime("$re[2]-$re[3]-$re[4] $re[5]:$re[6]:$re[7]");
            $r["ts"]=($ts===false)?0:$ts;
            $r["msg"]=trim($re[8]);
        }
        $r["sev"]=zbxev_severity($r["msg"]);
        $r["restart"]=preg_match('/Starting Zabbix Agent/i',$r["msg"])?true:false;
        $m["counts"]["all"]++;
        $m["counts"][$r["sev"]]++;
        $m["rows"][]=$r;
    }
    return $m;
}

// Etat du demon : INI de /zabbix/status (section APP_ZABBIX_AGENT), "" = agent non installe.
function zbxev_agent_model():array{
    $a=array("state"=>"unknown","pid"=>"");
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/zabbix/status"));
    if(!is_object($json) || !isset($json->Status) || !$json->Status){return $a;}
    $info=trim((string)($json->Info ?? ""));
    if($info==""){$a["state"]="absent";return $a;}
    $ini=new Bs_IniHandler();
    $ini->loadString($info);
    $sec=isset($ini->_params["APP_ZABBIX_AGENT"])?$ini->_params["APP_ZABBIX_AGENT"]:array();
    if(!is_array($sec) || count($sec)==0){return $a;}
    if(intval($sec["running"] ?? 0)==1){
        $a["state"]="running";
        $a["pid"]=preg_replace('/[^0-9]/',"",(string)($sec["master_pid"] ?? ""));
        return $a;
    }
    $a["state"]="stopped";
    return $a;
}

// ---------------------------------------------------------------- page (toolbar + host)

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    $term=zbxev_term($_SESSION["ZBXEV_TERM"] ?? "");
    $lines=zbxev_lines($_SESSION["ZBXEV_LINES"] ?? 250);
    $host="zbxev-host-$t";
    echo zbxev_style();
    echo zbxev_toolbar($t,$term,$lines);
    echo "<div id='$host' class='zbxev' aria-live='polite'>".zbxev_skeleton()."</div>";
    echo zbxev_js($page,$t,$host);
}

function zbxev_toolbar(int $t,string $term,int $lines):string{
    $segs=array();
    foreach(zbxev_lines_allowed() as $n){
        $on=($n==$lines);
        $segs[]="<button type='button' class='zbxev-seg".($on?" on":"")."' data-lines='$n' aria-pressed='".($on?"true":"false")."' OnClick=\"ZbxEvLines$t(this);\">$n</button>";
    }
    $v=req_html($term);
    $h=array();
    $h[]="<div class='zbxev'>";
    $h[]="<div class='zbxev-bar'>";
    $h[]="<div class='zbxev-field'><i class='fas fa-magnifying-glass zbxev-field-ico' aria-hidden='true'></i>";
    $h[]="<label for='zbxev-q-$t' class='zbxev-sr'>".zbxev_t("zbxev_search")."</label>";
    $h[]="<input type='text' id='zbxev-q-$t' value=\"$v\" placeholder=\"".zbxev_t("zbxev_search")."\" OnKeyPress=\"ZbxEvKey$t(event);\" OnInput=\"ZbxEvClearVis$t();\" autocomplete='off'>";
    $h[]="<button type='button' id='zbxev-x-$t' class='zbxev-x'".($term==""?" hidden":"")." aria-label=\"".zbxev_t("zbxev_clear")."\" title=\"".zbxev_t("zbxev_clear")."\" OnClick=\"ZbxEvClear$t();\"><i class='fas fa-xmark' aria-hidden='true'></i></button></div>";
    $h[]="<div class='zbxev-segs' role='group' aria-label=\"".zbxev_t("zbxev_lines")."\"><span class='zbxev-segs-l'>".zbxev_t("zbxev_lines")."</span>".implode("",$segs)."</div>";
    $h[]="<button type='button' class='zbxev-btn' OnClick=\"ZbxEvRun$t();\"><i class='fas fa-magnifying-glass' aria-hidden='true'></i> ".zbxev_t("zbxev_search")."</button>";
    $h[]="<button type='button' class='zbxev-ibtn' aria-label=\"".zbxev_t("zbxev_refresh")."\" title=\"".zbxev_t("zbxev_refresh")."\" OnClick=\"ZbxEvRun$t();\"><i class='fas fa-arrows-rotate' aria-hidden='true'></i></button>";
    $h[]="</div>";
    $h[]="<div class='zbxev-hint'>".zbxev_t("zbxev_hint",array("<span class='zbxev-kbd'>*</span>"))."</div>";
    $h[]="</div>";
    return implode("",$h);
}

function zbxev_skeleton():string{
    $h=array();
    $h[]="<div class='zbxev-card zbxev-skel' aria-hidden='true'>";
    for($i=0;$i<6;$i++){$h[]="<div class='zbxev-skel-b'></div>";}
    $h[]="</div>";
    return implode("",$h);
}

// ---------------------------------------------------------------- responder (?search=)

function search(){
    $term=zbxev_term($_GET["search"] ?? "");
    $lines=zbxev_lines($_GET["lines"] ?? 250);
    $t=preg_replace('/[^0-9]/',"",(string)($_GET["t"] ?? ""));
    $_SESSION["ZBXEV_TERM"]=$term;
    $_SESSION["ZBXEV_LINES"]=$lines;
    $m=zbxev_model($term,$lines);
    $h=array();
    if($m["error"]!=""){$h[]=zbxev_banner_error($m["error"]);}
    // Journal illisible : aucun compteur (0 serait faux), le bandeau seul.
    if($m["error"]==""){
        $h[]=zbxev_kpis($m,$t);
        if($m["counts"]["all"]>0){$h[]=zbxev_scope($m);}else{$h[]="<div class='zbxev-gap'></div>";}
    }
    $h[]="<div class='zbxev-card zbxev-tcard'>";
    if($m["counts"]["all"]==0){
        $h[]=($m["error"]!="")?"":(($term!="")?zbxev_empty_search($t):zbxev_empty_log());
    }else{
        $h[]=zbxev_table($m);
        $h[]="<div class='zbxev-none' hidden><i class='fas fa-filter' aria-hidden='true'></i> ".zbxev_t("zbxev_filter_none")."</div>";
    }
    $h[]=zbxev_footer(zbxev_agent_model());
    $h[]="</div>";
    echo implode("\n",$h);
}

function zbxev_banner_error(string $err):string{
    return "<div class='zbxev-banner' role='alert'><i class='fas fa-circle-exclamation' aria-hidden='true'></i><span><strong>"
        .zbxev_t("zbxev_read_error")."</strong> ".req_html($err)."</span></div>";
}

function zbxev_kpi(string $sev,string $label,string $ico,int $n,string $t):string{
    $dim=($n==0 && $sev!="all")?" dim":"";
    return "<button type='button' class='zbxev-kpi zbxev-kpi-$sev$dim' data-sev='$sev' aria-pressed='false' OnClick=\"ZbxEvFilter$t(this);\">"
        ."<span class='zbxev-kpi-t'><span class='zbxev-kpi-l'>$label</span><span class='zbxev-kpi-n'>$n</span></span>"
        ."<span class='zbxev-kpi-i'><i class='fas $ico' aria-hidden='true'></i></span></button>";
}

function zbxev_kpis(array $m,string $t):string{
    $c=$m["counts"];
    $h=array();
    $h[]="<div class='zbxev-kpis'>";
    $h[]=zbxev_kpi("all",zbxev_t("zbxev_total"),"fa-list",$c["all"],$t);
    $h[]=zbxev_kpi("error",zbxev_t("zbxev_errors"),"fa-circle-xmark",$c["error"],$t);
    $h[]=zbxev_kpi("warning",zbxev_t("zbxev_warnings"),"fa-triangle-exclamation",$c["warning"],$t);
    $h[]=zbxev_kpi("started",zbxev_t("zbxev_starts"),"fa-circle-play",$c["started"],$t);
    $h[]="</div>";
    return implode("",$h);
}

function zbxev_scope(array $m):string{
    $n="<strong>".$m["counts"]["all"]."</strong>";
    if($m["term"]!=""){
        $s=zbxev_t("zbxev_scope_q",array($n,"<strong>&laquo;&nbsp;".req_html($m["term"])."&nbsp;&raquo;</strong>"));
    }else{
        $s=zbxev_t("zbxev_scope",array($n));
    }
    $right=($m["counts"]["all"]>0)?"<span class='zbxev-scope-r'>".zbxev_t("zbxev_filter_hint")."</span>":"";
    return "<div class='zbxev-scope'><span>$s</span>$right</div>";
}

function zbxev_sev_cell(string $sev):string{
    $map=array(
        "error"=>array("fa-circle-xmark","zbxev_sev_error"),
        "warning"=>array("fa-triangle-exclamation","zbxev_sev_warning"),
        "started"=>array("fa-circle-play","zbxev_sev_started"),
        "info"=>array("fa-circle-info","zbxev_sev_info"),
    );
    $x=$map[$sev];
    $l=zbxev_t($x[1]);
    return "<td class='zbxev-c-sev'><span class='zbxev-sev zbxev-sev-$sev' title=\"$l\"><i class='fas {$x[0]}' aria-hidden='true'></i></span><span class='zbxev-sr'>$l</span></td>";
}

function zbxev_day_row(array $r):string{
    $tpl=new template_admin();
    $label=($r["ts"]>0)?$tpl->time_to_date($r["ts"]):"&mdash;";
    // time_to_date() n'ecrit l'annee que pour une autre annee : on l'ajoute pour l'annee courante.
    if($r["ts"]>0 && date("Y",$r["ts"])==date("Y")){$label.=" ".date("Y",$r["ts"]);}
    return "<tr class='zbxev-day'><td colspan='4'><i class='far fa-calendar' aria-hidden='true'></i> $label</td></tr>";
}

function zbxev_row(array $r):string{
    $time=($r["time"]!="")?$r["time"]:"&mdash;";
    $pid=($r["pid"]!="")?req_html($r["pid"]):"&mdash;";
    $pill=$r["restart"]?" <span class='zbxev-pill'><i class='fas fa-power-off' aria-hidden='true'></i> ".zbxev_t("zbxev_restart")."</span>":"";
    return "<tr class='zbxev-r zbxev-r-{$r["sev"]}' data-sev='{$r["sev"]}'>".zbxev_sev_cell($r["sev"])
        ."<td class='zbxev-c-time'>$time</td><td class='zbxev-c-pid'>$pid</td>"
        ."<td class='zbxev-c-msg'>".zbxev_message_html($r["msg"])."$pill</td></tr>";
}

function zbxev_table(array $m):string{
    $h=array();
    $h[]="<table class='zbxev-t'><caption class='zbxev-sr'>".zbxev_t("zbxev_caption")."</caption>";
    $h[]="<thead><tr><th scope='col' class='zbxev-c-sev'><span class='zbxev-sr'>".zbxev_t("zbxev_th_sev")."</span></th>"
        ."<th scope='col' class='zbxev-c-time'>".zbxev_t("zbxev_th_time")."</th>"
        ."<th scope='col' class='zbxev-c-pid'>".zbxev_t("zbxev_th_pid")."</th>"
        ."<th scope='col'>".zbxev_t("zbxev_th_event")."</th></tr></thead><tbody>";
    $day=null;
    foreach($m["rows"] as $r){
        if($r["day"]!==$day && $r["day"]!=""){
            $h[]=zbxev_day_row($r);
            $day=$r["day"];
        }
        $h[]=zbxev_row($r);
    }
    $h[]="</tbody></table>";
    return implode("\n",$h);
}

function zbxev_empty_search(string $t):string{
    return "<div class='zbxev-empty'><span class='zbxev-empty-i'><i class='fas fa-magnifying-glass' aria-hidden='true'></i></span>"
        ."<div class='zbxev-empty-h'>".zbxev_t("zbxev_no_match")."</div><div class='zbxev-empty-s'>".zbxev_t("zbxev_no_match_sub")."</div>"
        ."<button type='button' class='zbxev-lbtn' OnClick=\"ZbxEvClear$t();\"><i class='fas fa-xmark' aria-hidden='true'></i> ".zbxev_t("zbxev_clear")."</button></div>";
}

function zbxev_empty_log():string{
    return "<div class='zbxev-empty'><span class='zbxev-empty-i'><i class='far fa-file-lines' aria-hidden='true'></i></span>"
        ."<div class='zbxev-empty-h'>".zbxev_t("zbxev_empty")."</div><div class='zbxev-empty-s'>".zbxev_t("zbxev_empty_sub")."</div></div>";
}

function zbxev_footer(array $a):string{
    $map=array(
        "running"=>array("ok",zbxev_t("zbxev_running",array(req_html($a["pid"]!=""?$a["pid"]:"?")))),
        "stopped"=>array("err",zbxev_t("zbxev_stopped")),
        "absent"=>array("off",zbxev_t("zbxev_not_installed")),
        "unknown"=>array("off",zbxev_t("zbxev_status_unknown")),
    );
    $x=$map[$a["state"]];
    return "<div class='zbxev-foot'><span class='zbxev-dot zbxev-dot-{$x[0]}' aria-hidden='true'></span><span>{$x[1]}</span>"
        ."<span class='zbxev-grow'></span><span class='zbxev-foot-r'>".zbxev_t("zbxev_log_source")
        ." <span class='zbxev-mono'>/var/log/zabbix/zabbix_agentd.log</span></span></div>";
}

// ---------------------------------------------------------------- JS / CSS

function zbxev_js(string $page,int $t,string $host):string{
    return "<script>
(function(){
var t='$t';
function q(){var e=document.getElementById('zbxev-q-'+t);return e?e.value:'';}
function lines(){var b=document.querySelector('.zbxev-seg.on');return b?b.getAttribute('data-lines'):'250';}
window['ZbxEvRun'+t]=function(){
  LoadAjaxSilent('$host','$page?search='+encodeURIComponent(q())+'&lines='+lines()+'&t='+t);
};
window['ZbxEvKey'+t]=function(e){ if(!checkEnter(e)){return;} window['ZbxEvRun'+t](); };
window['ZbxEvClearVis'+t]=function(){ var x=document.getElementById('zbxev-x-'+t); if(x){x.hidden=(q()=='');} };
window['ZbxEvClear'+t]=function(){ var e=document.getElementById('zbxev-q-'+t); if(e){e.value='';} window['ZbxEvClearVis'+t](); window['ZbxEvRun'+t](); };
window['ZbxEvLines'+t]=function(b){
  var all=document.querySelectorAll('.zbxev-seg');
  for(var i=0;i<all.length;i++){all[i].classList.remove('on');all[i].setAttribute('aria-pressed','false');}
  b.classList.add('on');b.setAttribute('aria-pressed','true');
  window['ZbxEvRun'+t]();
};
window['ZbxEvFilter'+t]=function(b){
  var host=document.getElementById('$host'); if(!host){return;}
  var sev=b.getAttribute('data-sev');
  var was=b.getAttribute('aria-pressed')=='true';
  var k=host.querySelectorAll('.zbxev-kpi');
  for(var i=0;i<k.length;i++){k[i].setAttribute('aria-pressed','false');}
  if(was || sev=='all'){sev='';}else{b.setAttribute('aria-pressed','true');}
  var rows=host.querySelectorAll('.zbxev-t tbody tr'); var day=null,dayVis=false,vis=0;
  for(var j=0;j<rows.length;j++){
    var r=rows[j];
    if(r.classList.contains('zbxev-day')){ if(day){day.hidden=!dayVis;} day=r; dayVis=false; continue; }
    var show=(sev=='' || r.getAttribute('data-sev')==sev);
    r.hidden=!show; if(show){dayVis=true;vis++;}
  }
  if(day){day.hidden=!dayVis;}
  var n=host.querySelector('.zbxev-none'); if(n){n.hidden=(vis>0);}
};
window['ZbxEvRun'+t]();
})();
</script>";
}

function zbxev_style():string{
    $ac=array("accent"=>"#0E7C66","accent_dark"=>"#0b6654","accent_soft"=>"rgba(14,124,102,.12)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $b=brand_ui_accents(brand_palette());
        $ac["accent"]=$b["accent"];$ac["accent_dark"]=$b["accent_dark"];$ac["accent_soft"]=$b["accent_soft"];
    }
    $A=$ac["accent"];$AD=$ac["accent_dark"];$AS=$ac["accent_soft"];
    return "<style>
.zbxev{font-size:13px;color:#111827;margin-top:16px}
.zbxev *{box-sizing:border-box}
.zbxev-sr{position:absolute!important;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
.zbxev-mono,.zbxev-kbd,td.zbxev-c-time,td.zbxev-c-pid,.zbxev-tok{font-family:'JetBrains Mono',SFMono-Regular,Menlo,Consolas,monospace}
.zbxev-card{background:#fff;border:1px solid #E5E7EB;border-radius:8px;box-shadow:0 1px 2px rgba(0,0,0,.05)}
.zbxev-bar{display:flex;align-items:center;gap:10px;background:#fff;border:1px solid #E5E7EB;border-radius:8px;box-shadow:0 1px 2px rgba(0,0,0,.05);padding:8px;min-height:56px}
.zbxev-field{position:relative;flex:1;min-width:240px}
.zbxev-field input{width:100%;height:40px;padding:0 36px 0 38px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:6px;font-size:13px;color:#111827}
.zbxev-field input:focus{background:#fff;outline:2px solid $A;outline-offset:1px}
.zbxev-field-ico{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#9CA3AF;width:14px;height:14px}
.zbxev-x{position:absolute;right:6px;top:50%;transform:translateY(-50%);border:0;background:transparent;color:#9CA3AF;padding:6px;cursor:pointer;border-radius:4px}
.zbxev-x:hover{color:#4B5563}
.zbxev-segs{display:flex;align-items:center;background:#F3F4F6;border:1px solid #E5E7EB;border-radius:6px;padding:3px;white-space:nowrap}
.zbxev-segs-l{font-size:12px;font-weight:600;color:#6B7280;padding:0 8px}
.zbxev-seg{border:0;background:transparent;font-size:12px;font-weight:600;color:#4B5563;padding:5px 10px;border-radius:4px;cursor:pointer}
.zbxev-seg:hover{color:#111827}
.zbxev-seg.on{background:$A;color:#fff;box-shadow:0 1px 2px rgba(0,0,0,.1)}
.zbxev-btn{display:inline-flex;align-items:center;gap:8px;height:40px;padding:0 16px;border:0;border-radius:6px;background:$A;color:#fff;font-weight:600;font-size:13px;cursor:pointer;white-space:nowrap}
.zbxev-btn:hover{background:$AD}
.zbxev-ibtn{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border:1px solid #E5E7EB;border-radius:6px;background:#fff;color:#4B5563;cursor:pointer}
.zbxev-ibtn:hover{background:#F9FAFB;color:#111827}
.zbxev button:focus-visible,.zbxev-kpi:focus-visible{outline:2px solid $A;outline-offset:2px}
.zbxev-hint{margin:6px 4px 0;font-size:12px;color:#6B7280}
.zbxev-kbd{background:#E5E7EB;border-radius:4px;padding:0 5px;color:#374151;font-weight:600}
.zbxev-banner{display:flex;align-items:center;gap:10px;background:#FEF2F2;border-left:4px solid #DC2626;border-radius:0 6px 6px 0;padding:12px 14px;color:#991B1B;margin-bottom:12px}
.zbxev-banner svg,.zbxev-banner i{color:#DC2626}
.zbxev-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.zbxev-kpi{display:flex;align-items:center;justify-content:space-between;text-align:left;padding:14px 16px;border-radius:10px;border:1px solid #E5E7EB;background:#F9FAFB;cursor:pointer;box-shadow:0 1px 2px rgba(0,0,0,.05);transition:background .15s,border-color .15s}
.zbxev-kpi-t{display:flex;flex-direction:column;gap:2px}
.zbxev-kpi-l{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#6B7280}
.zbxev-kpi-n{font-size:28px;font-weight:700;line-height:1.1;color:#111827}
.zbxev-kpi-i{display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:8px;background:#fff;border:1px solid #E5E7EB;color:#4B5563;font-size:18px}
.zbxev-kpi-error{background:#FEF2F2;border-color:#FECACA}.zbxev-kpi-error .zbxev-kpi-l{color:#991B1B}.zbxev-kpi-error .zbxev-kpi-n,.zbxev-kpi-error .zbxev-kpi-i{color:#DC2626}.zbxev-kpi-error .zbxev-kpi-i{border-color:#FECACA}
.zbxev-kpi-warning{background:#FFFBEB;border-color:#FDE68A}.zbxev-kpi-warning .zbxev-kpi-l{color:#92400E}.zbxev-kpi-warning .zbxev-kpi-n,.zbxev-kpi-warning .zbxev-kpi-i{color:#B45309}.zbxev-kpi-warning .zbxev-kpi-i{border-color:#FDE68A}
.zbxev-kpi-started{background:#ECFDF5;border-color:#A7F3D0}.zbxev-kpi-started .zbxev-kpi-l{color:#065F46}.zbxev-kpi-started .zbxev-kpi-n,.zbxev-kpi-started .zbxev-kpi-i{color:#047857}.zbxev-kpi-started .zbxev-kpi-i{border-color:#A7F3D0}
.zbxev-kpi.dim{opacity:.55}
.zbxev-kpi[aria-pressed=true]{box-shadow:0 0 0 2px currentColor}
.zbxev-kpi-all[aria-pressed=true]{color:#374151}.zbxev-kpi-error[aria-pressed=true]{color:#DC2626}.zbxev-kpi-warning[aria-pressed=true]{color:#D97706}.zbxev-kpi-started[aria-pressed=true]{color:#059669}
.zbxev-scope{display:flex;justify-content:space-between;gap:16px;margin:10px 4px 12px;font-size:12px;color:#6B7280}
.zbxev-gap{height:12px}
.zbxev-scope strong{color:#111827}
.zbxev-scope-r{color:#9CA3AF}
.zbxev-tcard{overflow:hidden}
.zbxev-t{width:100%;border-collapse:collapse;color:#111827;table-layout:auto}
.zbxev-t thead th{position:sticky;top:0;z-index:1;background:#F9FAFB;border-bottom:1px solid #E5E7EB;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#6B7280;padding:10px 12px;text-align:left}
.zbxev-t td{padding:8px 12px;border-bottom:1px solid #F1F5F9;vertical-align:top;color:#111827}
.zbxev-c-sev{width:36px;text-align:center;padding-left:14px!important;padding-right:4px!important}
.zbxev-c-time{width:96px;white-space:nowrap;font-size:13px;color:#374151!important}
.zbxev-c-pid{width:76px;white-space:nowrap;font-size:12px;color:#6B7280!important}
.zbxev-c-msg{line-height:1.6;word-break:break-word}
.zbxev-sev{display:inline-flex;width:16px;height:16px;align-items:center;justify-content:center}
.zbxev-sev-error{color:#DC2626}.zbxev-sev-warning{color:#D97706}.zbxev-sev-started{color:#059669}.zbxev-sev-info{color:#9CA3AF}
.zbxev-r:hover td{background:#F9FAFB}
.zbxev-r-error td{background:#FEF2F2}.zbxev-r-error:hover td{background:#FEE2E2}
.zbxev-r-error td:first-child{box-shadow:inset 3px 0 0 #DC2626}
.zbxev-r-error .zbxev-c-time{color:#B91C1C!important;font-weight:500}.zbxev-r-error .zbxev-c-pid{color:#991B1B!important}.zbxev-r-error .zbxev-c-msg{color:#7F1D1D}
.zbxev-r-warning td:first-child{box-shadow:inset 3px 0 0 #D97706}
.zbxev-tok{font-size:12px}
.zbxev-tok-b{background:#F3F4F6;border:1px solid #E5E7EB;border-radius:4px;padding:1px 4px;color:#1F2937}
.zbxev-r-error .zbxev-tok-b{background:#FEE2E2;border-color:#FECACA;color:#991B1B}
.zbxev-pill{display:inline-flex;align-items:center;gap:4px;margin-left:6px;padding:1px 8px;border-radius:999px;background:#F3F4F6;border:1px solid #E5E7EB;font-size:11px;font-weight:600;color:#374151;vertical-align:1px}
.zbxev-day td{background:#F9FAFB;border-top:1px solid #E5E7EB;border-bottom:1px solid #E5E7EB;font-size:12px;font-weight:600;color:#4B5563;padding:6px 16px}
.zbxev-day svg,.zbxev-day i{color:#9CA3AF;margin-right:6px}
.zbxev-none{padding:24px;text-align:center;color:#6B7280}
.zbxev-empty{padding:56px 16px;text-align:center}
.zbxev-empty-i{display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:50%;background:#F3F4F6;color:#9CA3AF;font-size:20px;margin-bottom:12px}
.zbxev-empty-h{font-weight:600;color:#374151}
.zbxev-empty-s{font-size:12px;color:#6B7280;margin-top:4px}
.zbxev-lbtn{margin-top:16px;display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border:0;border-radius:6px;background:$AS;color:$AD;font-size:12px;font-weight:600;cursor:pointer}
.zbxev-foot{display:flex;align-items:center;gap:8px;padding:10px 16px;background:#F9FAFB;border-top:1px solid #E5E7EB;font-size:12px;color:#4B5563}
.zbxev-grow{flex:1}
.zbxev-foot-r{color:#6B7280}
.zbxev-dot{width:8px;height:8px;border-radius:50%;background:#9CA3AF;flex:none}
.zbxev-dot-ok{background:#10B981}.zbxev-dot-err{background:#DC2626}
.zbxev-skel{padding:16px}
.zbxev-skel-b{height:30px;border-radius:4px;background:#F3F4F6;margin-bottom:10px;animation:zbxevPulse 1.4s ease-in-out infinite}
@keyframes zbxevPulse{50%{opacity:.5}}
.zbxev-r{animation:zbxevIn .15s ease-out}
@keyframes zbxevIn{from{opacity:0}to{opacity:1}}
@media (prefers-reduced-motion:reduce){.zbxev-r,.zbxev-skel-b{animation:none}.zbxev-kpi{transition:none}}
@media (max-width:1200px){.zbxev-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.zbxev-bar{flex-wrap:wrap}}
</style>";
}
