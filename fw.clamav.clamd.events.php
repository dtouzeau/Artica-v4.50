<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
page();

// ---- design (maquette stitch_clamav_events, 2026-11-15) ---------------------------------------
// Tail of /var/log/clamav/clamd.log (GET /clamd/events/{max}/{search}), newest first.
// One model per request, one function per widget, CSS and JS concatenated OUTSIDE the parser.
// Every count is computed over the lines displayed: the daemon exposes nothing else.

function clamev_max_choices():array{ return array(50,100,250,500,1000); }

// Search text and line count, from the request then the session. The old free-text syntax
// ("50 events") is still understood, so a session saved before this design keeps working.
function clamev_params():array{
    $term=null;$max=0;
    if(isset($_GET["search"])){$term=(string)$_GET["search"];}
    if(isset($_GET["max"])){$max=intval($_GET["max"]);}
    if($term===null){ $term=isset($_SESSION["CLAMD_SEARCH"])?(string)$_SESSION["CLAMD_SEARCH"]:""; }
    if($max==0 && isset($_SESSION["CLAMD_SEARCH_MAX"])){$max=intval($_SESSION["CLAMD_SEARCH_MAX"]);}
    if(preg_match("#(^|\s)([0-9]+)\s+events(\s|$)#i",$term,$re)){
        if($max==0){$max=intval($re[2]);}
        $term=str_replace($re[0]," ",$term);
    }
    $term=trim(preg_replace('/\s+/',' ',$term));
    // rg receives the text as a regular expression in a URL segment: keep plain characters only.
    $term=str_replace("/","*",$term);
    $term=preg_replace('/[^A-Za-z0-9 _.:@*=,\-]/',"",$term);
    if(strlen($term)>128){$term=substr($term,0,128);}
    if(!in_array($max,clamev_max_choices())){$max=50;}
    return array("term"=>$term,"max"=>$max);
}

function clamev_tr(string $token):string{
    $tpl=new template_admin();
    $t=$tpl->_ENGINE_parse_body("{".$token."}");
    $args=func_get_args(); array_shift($args);
    foreach($args as $a){ $p=strpos($t,"%s"); if($p===false){break;} $t=substr_replace($t,(string)$a,$p,2); }
    return $t;
}

// Log text is external data: escaped, and braces/brackets neutralised so the template engine
// never reads a {token} or a [br] inside it.
function clamev_h($s):string{
    return str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),req_html((string)$s));
}

function clamev_types():array{
    return array(
        "detection"=>array("label"=>"{clamev_t_detection}","plural"=>"{clamev_k_detections}","ico"=>"fas fa-virus"),
        "error"    =>array("label"=>"{clamev_t_error}","plural"=>"{clamev_k_errors}","ico"=>"fas fa-circle-xmark"),
        "warning"  =>array("label"=>"{clamev_t_warning}","plural"=>"{clamev_k_warnings}","ico"=>"fas fa-triangle-exclamation"),
        "reload"   =>array("label"=>"{clamev_t_reload}","plural"=>"{clamev_k_reloads}","ico"=>"fas fa-database"),
        "startstop"=>array("label"=>"{clamev_t_startstop}","plural"=>"{clamev_k_startstop}","ico"=>"fas fa-power-off"),
        "info"     =>array("label"=>"{clamev_t_info}","plural"=>"","ico"=>"fas fa-circle-info"),
    );
}

function clamev_classify(string $msg):string{
    if(preg_match('#\sFOUND\s*$#',$msg)){return "detection";}
    if(preg_match("#ERROR:|Can't connect|failed:|errno=[0-9]+#i",$msg)){return "error";}
    if(preg_match('#WARNING:|Warning:|Not loading|Disabling#',$msg)){return "warning";}
    if(preg_match('#SelfCheck:|Database (correctly )?reloaded|Database modification detected|Reading databases|Activating the newly loaded database|Database status OK#i',$msg)){return "reload";}
    if(preg_match('#(^|\s)(\+\+\+|---)\s*(Started|Stopped) at#',$msg)){return "startstop";}
    return "info";
}

// "Sun Nov 15 10:00:01 2026 -> msg" (LogTime true) or the syslog form "Nov 15 10:00:01 host clamd[12]: msg".
// A line that matches neither is kept as an Info row with no time, never printed above the table.
function clamev_parse(string $line):array{
    $line=trim($line);
    $ts=0;$msg=$line;
    if(preg_match('#^(.+?)\s+->\s+(.*)$#',$line,$re)){
        $t=strtotime(trim($re[1])); if($t!==false){$ts=$t;}
        $msg=$re[2];
    }elseif(preg_match('#^([A-Z][a-z]{2}\s+[0-9]+\s+[0-9:]+)\s+\S+\s+\S+?\[[0-9]+\]:\s+(.*)$#',$line,$re)){
        $t=strtotime($re[1]); if($t!==false){$ts=$t;}
        $msg=$re[2];
    }
    return array("ts"=>$ts,"msg"=>trim($msg),"raw"=>$line,"type"=>clamev_classify(trim($msg)));
}

function clamev_model():array{
    $P=clamev_params();
    $M=array("term"=>$P["term"],"max"=>$P["max"],"error"=>"","rows"=>array(),
        "counts"=>array("detection"=>0,"error"=>0,"warning"=>0,"reload"=>0,"startstop"=>0,"info"=>0),
        "oldest"=>0,"newest"=>0,"last_detection"=>0);
    $search=strlen($P["term"])<3?"NONE":rawurlencode($P["term"]);
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/clamd/events/{$P["max"]}/$search");
    $json=json_decode($data);
    if(trim((string)$data)==""){ $M["error"]="{clamev_no_answer}"; return $M; }
    if(!is_object($json)){
        $M["error"]=json_last_error()>JSON_ERROR_NONE?json_last_error_msg():"{clamev_no_answer}";
        return $M;
    }
    if(property_exists($json,"Status") && !$json->Status){
        $M["error"]=isset($json->Error)&&$json->Error!=""?(string)$json->Error:"{clamev_no_answer}";
        return $M;
    }
    $logs=(isset($json->Logs)&&is_array($json->Logs))?$json->Logs:array();
    foreach($logs as $line){
        $line=trim((string)$line);
        if($line=="" || preg_match('#^[\-]+$#',$line)){continue;}
        // A missing clamd.log comes back as the reader's own error text ("tail: cannot open …"),
        // not as an error: it means "nothing written yet", never an Info event.
        if(preg_match('#^(tail|rg|grep): .*(cannot open|No such file)#i',$line)){continue;}
        $r=clamev_parse($line);
        if($r["msg"]=="" || preg_match('#^[\-]+$#',$r["msg"])){continue;}
        $M["rows"][]=$r;
        $M["counts"][$r["type"]]++;
        if($r["ts"]>0){
            if($M["newest"]==0 || $r["ts"]>$M["newest"]){$M["newest"]=$r["ts"];}
            if($M["oldest"]==0 || $r["ts"]<$M["oldest"]){$M["oldest"]=$r["ts"];}
            if($r["type"]=="detection" && $r["ts"]>$M["last_detection"]){$M["last_detection"]=$r["ts"];}
        }
    }
    // Remembered only when the request was explicit, as before.
    if(isset($_GET["search"])){
        $_SESSION["CLAMD_SEARCH"]=$P["term"];
        $_SESSION["CLAMD_SEARCH_MAX"]=$P["max"];
    }
    return $M;
}

function clamev_age(int $ts):string{
    if($ts<=0){return "";}
    $d=time()-$ts;
    if($d<0){$d=0;}
    if($d<60){return clamev_tr("clamev_age_now");}
    if($d<3600){return clamev_tr("clamev_age_min",intval($d/60));}
    if($d<86400){return clamev_tr("clamev_age_hours",intval($d/3600));}
    return clamev_tr("clamev_age_days",intval($d/86400));
}

function clamev_short_date(int $ts):string{
    if($ts<=0){return "—";}
    return date("Y",$ts)==date("Y")?date("M j",$ts):date("M j Y",$ts);
}

// ---- page shell ------------------------------------------------------------------------------

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $P=clamev_params();
    $html=array();
    $html[]="<div class='clamev' id='clamev-root'>";
    $html[]=clamev_intro();
    $html[]=clamev_search_card($P);
    $html[]="<div id='clamev-results'></div>";
    $html[]="</div>";
    $q="&max=".$P["max"]."&search=".urlencode($P["term"]);
    echo clamev_style().$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>".clamev_js()."ClamEvLoad('".$q."');</script>";
}

function clamev_intro():string{
    return "<p class='clamev-intro'>{clamev_intro}</p>";
}

function clamev_search_card(array $P):string{
    $opts=array();
    foreach(clamev_max_choices() as $n){
        $sel=$n==$P["max"]?" selected":"";
        $opts[]="<option value='$n'$sel>".clamev_tr("clamev_n_lines",$n)."</option>";
    }
    $v=clamev_h($P["term"]);
    return "<div class='clamev-card clamev-search'>
  <div class='clamev-search-row'>
    <div class='clamev-field clamev-grow'>
      <label for='clamev-q' class='clamev-label'>{clamev_search}</label>
      <div class='clamev-input'><span class='clamev-input-ico' aria-hidden='true'><i class='fas fa-magnifying-glass'></i></span>
      <input type='text' id='clamev-q' value=\"$v\" maxlength='128' placeholder=\"{clamev_search_ph}\" onkeydown=\"ClamEvKey(event)\"></div>
      <div class='clamev-help'>{clamev_search_help}</div>
    </div>
    <div class='clamev-field clamev-lines'>
      <label for='clamev-max' class='clamev-label'>{clamev_lines}</label>
      <select id='clamev-max' onchange='ClamEvGo()'>".implode("",$opts)."</select>
      <div class='clamev-help'>{clamev_lines_help}</div>
    </div>
    <div class='clamev-field clamev-btns'>
      <span class='clamev-label'>&nbsp;</span>
      <button type='button' class='clamev-btn' onclick='ClamEvGo()'><span aria-hidden='true'><i class='fas fa-magnifying-glass'></i></span> {clamev_search}</button>
    </div>
  </div>
  <div class='clamev-chips' id='clamev-chips'></div>
</div>";
}

// ---- results (?search=) ----------------------------------------------------------------------

function search(){
    $tpl=new template_admin();
    $M=clamev_model();
    $html=array();
    if($M["error"]!=""){
        $html[]=clamev_error($M);
    }else{
        $html[]=clamev_summary($M);
        $html[]=clamev_table($M);
    }
    $html[]=clamev_state($M);
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html))."<script>".clamev_tiny()."ClamEvChips();ClamEvApply();</script>";
}

function clamev_tiny():string{
    $tpl=new template_admin();
    $topbuttons=array();
    $topbuttons[]=array("ClamEvGo()",ico_refresh,"{refresh}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{APP_CLAMAV} {service_events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_CLAMAV_TEXT}";
    $TINY_ARRAY["URL"]="clamav";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}

// Hidden carrier read by the JS: current server-side filters, for the chips.
function clamev_state(array $M):string{
    return "<span id='clamev-state' hidden data-term=\"".clamev_h($M["term"])."\" data-max='".intval($M["max"])."'></span>";
}

function clamev_error(array $M):string{
    $err=$M["error"];
    if(strpos($err,"{")!==0){$err=clamev_h($err);}
    return "<div class='clamev-banner' role='alert'><span class='clamev-banner-ico' aria-hidden='true'><i class='fas fa-plug-circle-xmark'></i></span>
<div><strong>{clamev_unreachable}</strong><div class='clamev-help clamev-mono'>$err</div></div></div>";
}

function clamev_summary(array $M):string{
    $n=count($M["rows"]);
    if($n==0){return "";}
    $ctx="";
    if($n>0){
        $ctx=clamev_tr("clamev_in_lines",$n);
        if($M["oldest"]>0){
            $ctx.=" — ".clamev_tr("clamev_from_to",clamev_short_date($M["oldest"])." ".date("H:i",$M["oldest"]),clamev_short_date($M["newest"])." ".date("H:i",$M["newest"]));
        }
    }
    $cards=array();
    foreach(array("detection","error","warning","reload","startstop") as $k){ $cards[]=clamev_kpi($M,$k); }
    $cards[]=clamev_kpi_last($M);
    $head=$ctx==""?"":"<div class='clamev-ctx'>$ctx</div>";
    return "$head<div class='clamev-kpis'>".implode("",$cards)."</div>";
}

function clamev_kpi(array $M,string $k):string{
    $T=clamev_types();
    $c=intval($M["counts"][$k]);
    $tone=$c>0?" clamev-k-$k":" clamev-k-zero";
    $sub="";
    if($k=="detection" && $c==0 && count($M["rows"])>0){$sub="<div class='clamev-kpi-sub'>{clamev_no_detection_short}</div>";}
    $dis=$c==0?" disabled":"";
    return "<button type='button' class='clamev-kpi$tone' data-type='$k' aria-pressed='false' onclick=\"ClamEvType('$k')\"$dis>
<span class='clamev-kpi-ico' aria-hidden='true'><i class='{$T[$k]["ico"]}'></i></span>
<span class='clamev-kpi-txt'><span class='clamev-kpi-n'>$c</span><span class='clamev-kpi-l'>{$T[$k]["plural"]}</span>$sub</span></button>";
}

function clamev_kpi_last(array $M):string{
    $ts=intval($M["last_detection"]);
    if($ts>0){
        $v=clamev_age($ts);
        $sub="<div class='clamev-kpi-sub'>".clamev_short_date($ts)." ".date("H:i:s",$ts)."</div>";
        $tone=" clamev-k-detection";
    }else{
        $v="{clamev_none_here}";
        $sub="";
        $tone=" clamev-k-zero";
    }
    return "<div class='clamev-kpi clamev-kpi-static$tone'>
<span class='clamev-kpi-ico' aria-hidden='true'><i class='fas fa-clock'></i></span>
<span class='clamev-kpi-txt'><span class='clamev-kpi-v'>$v</span><span class='clamev-kpi-l'>{clamev_last_detection}</span>$sub</span></div>";
}

function clamev_table(array $M):string{
    if(count($M["rows"])==0){ return clamev_empty($M); }
    $rows=array();
    $i=0;
    foreach($M["rows"] as $r){ $i++; $rows[]=clamev_row($r,$i); }
    return "<div class='clamev-card clamev-tablecard'>
<table class='clamev-table'>
<thead><tr><th class='clamev-c-time'>{clamev_time}</th><th class='clamev-c-type'>{clamev_type}</th><th>{clamev_message}</th><th class='clamev-c-det'><span class='clamev-sr'>{clamev_detail}</span></th></tr></thead>
<tbody id='clamev-tbody'>".implode("\n",$rows)."</tbody></table>
<div class='clamev-empty' id='clamev-type-empty' style='display:none'>
  <span class='clamev-empty-ico' aria-hidden='true'><i class='fas fa-shield-check'></i></span>
  <h4 id='clamev-type-empty-t'></h4><p id='clamev-type-empty-p'></p>
</div></div>";
}

function clamev_badge(string $type):string{
    $T=clamev_types();
    return "<span class='clamev-badge clamev-b-$type'><span aria-hidden='true'><i class='{$T[$type]["ico"]}'></i></span> {$T[$type]["label"]}</span>";
}

function clamev_message_cell(array $r):string{
    if($r["type"]=="detection" && preg_match('#^(.*):\s+(\S.*?)\s+FOUND\s*$#',$r["msg"],$re)){
        return "<strong class='clamev-sig'>".clamev_h($re[2])."</strong> — ".clamev_h($re[1]);
    }
    return clamev_h($r["msg"]);
}

function clamev_row(array $r,int $i):string{
    $id="clamev-d-$i";
    if($r["ts"]>0){
        $title=clamev_h(date("D M j H:i:s Y",$r["ts"]))." — ".clamev_age($r["ts"]);
        $time="<span class='clamev-hms'>".date("H:i:s",$r["ts"])."</span> <span class='clamev-day'>".clamev_short_date($r["ts"])."</span>";
    }else{
        $title="";
        $time="<span class='clamev-day'>—</span>";
    }
    $raw=clamev_h($r["raw"]);
    $det=$r["type"]=="detection"?" clamev-r-det":"";
    return "<tr class='clamev-row$det' data-type='{$r["type"]}' tabindex='0' role='button' aria-expanded='false' aria-controls='$id' onclick=\"ClamEvToggle('$id',this)\" onkeydown=\"ClamEvRowKey(event,'$id',this)\">
<td class='clamev-c-time' title=\"$title\">$time</td>
<td class='clamev-c-type'>".clamev_badge($r["type"])."</td>
<td class='clamev-msg'><div class='clamev-ell'>".clamev_message_cell($r)."</div></td>
<td class='clamev-c-det'><span class='clamev-chev' aria-label=\"{clamev_show_line}\"><i class='fas fa-chevron-down'></i></span></td>
</tr>
<tr class='clamev-drawer' id='$id' data-type='{$r["type"]}' style='display:none'><td colspan='4'><div class='clamev-raw'><span class='clamev-mono clamev-rawtxt'>$raw</span>
<button type='button' class='clamev-copy' onclick=\"ClamEvCopy(this,event)\"><span aria-hidden='true'><i class='fas fa-copy'></i></span> {clamev_copy}</button></div></td></tr>";
}

function clamev_empty(array $M):string{
    if(strlen($M["term"])>=3){
        return "<div class='clamev-card clamev-empty'>
  <span class='clamev-empty-ico' aria-hidden='true'><i class='fas fa-magnifying-glass'></i></span>
  <h4>".clamev_tr("clamev_no_match",clamev_h($M["term"]))."</h4><p>{clamev_no_match_help}</p>
  <button type='button' class='clamev-btn-sec' onclick='ClamEvClear()'>{clamev_clear}</button></div>";
    }
    return "<div class='clamev-card clamev-empty'>
  <span class='clamev-empty-ico' aria-hidden='true'><i class='fas fa-file-lines'></i></span>
  <h4>{clamev_empty_title}</h4><p>{clamev_empty_help}</p></div>";
}

// ---- assets ----------------------------------------------------------------------------------

function clamev_js():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $L=array();
    foreach(clamev_types() as $k=>$t){ $L[$k]=$t["label"]; }
    $labels=array();
    foreach($L as $k=>$v){ $labels[$k]=trim(strip_tags(html_entity_decode($tpl->_ENGINE_parse_body($v),ENT_QUOTES,"UTF-8"))); }
    $txt=array(
        "type"=>"{clamev_chip_type}","text"=>"{clamev_chip_text}","active"=>"{clamev_active_filters}","clear"=>"{clamev_clear}",
        "remove"=>"{clamev_remove_filter}","copied"=>"{clamev_copied}","nodet"=>"{clamev_no_detection_lines}",
        "notype"=>"{clamev_no_type_lines}","further"=>"{clamev_look_further}");
    foreach($txt as $k=>$v){ $txt[$k]=trim(strip_tags(html_entity_decode($tpl->_ENGINE_parse_body($v),ENT_QUOTES,"UTF-8"))); }
    $J=json_encode(array("labels"=>$labels,"t"=>$txt));
    return "
window.ClamEvI18n=$J; if(typeof window.ClamEvTypeF==='undefined'){window.ClamEvTypeF='';}
function ClamEvQS(){var q=document.getElementById('clamev-q'),m=document.getElementById('clamev-max');
  return '&max='+encodeURIComponent(m?m.value:'50')+'&search='+encodeURIComponent(q?q.value:'');}
function ClamEvLoad(qs){LoadAjax('clamev-results','$page?r=1'+qs);}
function ClamEvGo(){ClamEvLoad(ClamEvQS());}
function ClamEvKey(e){if(e&&(e.keyCode==13)){e.preventDefault();ClamEvGo();}}
function ClamEvClear(){var q=document.getElementById('clamev-q');if(q){q.value='';}window.ClamEvTypeF='';ClamEvGo();}
function ClamEvType(k){window.ClamEvTypeF=(window.ClamEvTypeF===k)?'':k;ClamEvApply();ClamEvChips();}
function ClamEvEsc(s){var d=document.createElement('div');d.textContent=String(s);return d.innerHTML;}
function ClamEvApply(){
  var f=window.ClamEvTypeF||'',tb=document.getElementById('clamev-tbody'),shown=0;
  var ks=document.querySelectorAll('.clamev-kpi[data-type]');
  for(var i=0;i<ks.length;i++){ks[i].setAttribute('aria-pressed',ks[i].getAttribute('data-type')===f?'true':'false');}
  if(!tb){return;}
  for(var j=0;j<tb.rows.length;j++){var r=tb.rows[j],t=r.getAttribute('data-type');
    if(r.className.indexOf('clamev-drawer')>=0){if(f!==''&&t!==f){r.style.display='none';}continue;}
    var ok=(f===''||t===f);r.style.display=ok?'':'none';if(!ok){r.setAttribute('aria-expanded','false');}if(ok){shown++;}}
  var e=document.getElementById('clamev-type-empty');if(!e){return;}
  if(f!==''&&shown===0){var I=window.ClamEvI18n,st=document.getElementById('clamev-state'),n=st?st.getAttribute('data-max'):'';
    document.getElementById('clamev-type-empty-t').textContent=(f==='detection'?I.t.nodet:I.t.notype).replace('%s',n);
    document.getElementById('clamev-type-empty-p').textContent=I.t.further;e.style.display='';}
  else{e.style.display='none';}
}
function ClamEvChips(){
  var c=document.getElementById('clamev-chips');if(!c){return;}
  var I=window.ClamEvI18n,st=document.getElementById('clamev-state'),term=st?st.getAttribute('data-term'):'',h=[];
  var tmp=document.createElement('textarea');tmp.innerHTML=term||'';term=tmp.value;
  if(window.ClamEvTypeF){h.push('<span class=\"clamev-chip\">'+ClamEvEsc(I.t.type)+': '+ClamEvEsc(I.labels[window.ClamEvTypeF]||'')+' <button type=\"button\" aria-label=\"'+ClamEvEsc(I.t.remove)+'\" onclick=\"ClamEvType(window.ClamEvTypeF)\">&times;</button></span>');}
  if(term&&term.length>=3){h.push('<span class=\"clamev-chip\">'+ClamEvEsc(I.t.text)+': '+ClamEvEsc(term)+' <button type=\"button\" aria-label=\"'+ClamEvEsc(I.t.remove)+'\" onclick=\"ClamEvClearText()\">&times;</button></span>');}
  if(h.length===0){c.innerHTML='';c.style.display='none';return;}
  c.style.display='';c.innerHTML='<span class=\"clamev-chips-l\">'+ClamEvEsc(I.t.active)+'</span>'+h.join('')+'<button type=\"button\" class=\"clamev-link\" onclick=\"ClamEvClear()\">'+ClamEvEsc(I.t.clear)+'</button>';
}
function ClamEvClearText(){var q=document.getElementById('clamev-q');if(q){q.value='';}ClamEvGo();}
function ClamEvToggle(id,row){var e=document.getElementById(id);if(!e){return;}var open=(e.style.display=='none');e.style.display=open?'':'none';if(row){row.setAttribute('aria-expanded',open?'true':'false');}}
function ClamEvRowKey(ev,id,row){if(ev&&(ev.keyCode==13||ev.keyCode==32)){ev.preventDefault();ClamEvToggle(id,row);}}
function ClamEvCopy(btn,ev){if(ev){ev.stopPropagation();}
  var s=btn.parentNode.querySelector('.clamev-rawtxt');if(!s){return;}var txt=s.textContent;
  var done=function(){var o=btn.getAttribute('data-o')||btn.innerHTML;btn.setAttribute('data-o',o);btn.textContent=window.ClamEvI18n.t.copied;setTimeout(function(){btn.innerHTML=o;},1500);};
  if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(txt).then(done,function(){ClamEvCopyOld(txt);done();});return;}
  ClamEvCopyOld(txt);done();}
function ClamEvCopyOld(txt){var t=document.createElement('textarea');t.value=txt;t.style.position='fixed';t.style.left='-9999px';document.body.appendChild(t);t.select();try{document.execCommand('copy');}catch(e){}document.body.removeChild(t);}
";
}

function clamev_style():string{
    $a="#10b981";$ad="#047857";$as="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $b=brand_ui_accents(brand_palette());
        if(!empty($b["accent"])){$a=$b["accent"];}
        if(!empty($b["accent_dark"])){$ad=$b["accent_dark"];}
        if(!empty($b["accent_soft"])){$as=$b["accent_soft"];}
    }
    return "<style>
.clamev{color:#141b2c;font-size:14px;margin-top:12px}
.clamev *{box-sizing:border-box}
.clamev-intro{font-size:14px;color:#475467;margin:0 0 16px;line-height:1.5}
.clamev-card{background:#fff;border:1px solid #e4e7ec;border-radius:10px;box-shadow:0 1px 2px rgba(16,24,40,.05);margin-bottom:16px}
.clamev-search{padding:16px}
.clamev-search-row{display:flex;gap:16px;align-items:flex-start}
.clamev-field{display:flex;flex-direction:column;gap:4px;min-width:0}
.clamev-grow{flex:1}
.clamev-lines{width:200px;flex-shrink:0}
.clamev-btns{flex-shrink:0}
.clamev-label{font-size:12px;font-weight:600;color:#344054;letter-spacing:.02em;margin:0;display:block;line-height:16px}
.clamev-help{font-size:12px;color:#667085;line-height:1.45}
.clamev-input{position:relative}
.clamev-input-ico{position:absolute;left:12px;top:0;height:40px;display:flex;align-items:center;color:#667085;font-size:14px;pointer-events:none}
.clamev-input input,.clamev-lines select{width:100%;height:40px;border:1px solid #d0d5dd;border-radius:8px;font-size:14px;color:#141b2c;background:#fff;outline:none}
.clamev-input input{padding:0 12px 0 36px}
.clamev-lines select{padding:0 10px}
.clamev-input input:focus,.clamev-lines select:focus{border-color:$a;box-shadow:0 0 0 2px $as}
.clamev-btn{height:40px;padding:0 18px;border:0;border-radius:8px;background:$a;color:#fff;font-weight:600;font-size:14px;display:inline-flex;align-items:center;gap:8px;cursor:pointer;white-space:nowrap}
.clamev-btn:hover{background:$ad}
.clamev-btn-sec{height:36px;padding:0 14px;border:1px solid #d0d5dd;border-radius:8px;background:#fff;color:#344054;font-weight:600;font-size:13px;cursor:pointer;margin-top:12px}
.clamev button:focus-visible,.clamev-row:focus-visible{outline:2px solid #1570ef;outline-offset:2px}
.clamev-chips{display:flex;flex-wrap:wrap;align-items:center;gap:6px;margin-top:12px;padding-top:12px;border-top:1px solid #f2f4f7}
.clamev-chips-l{font-size:12px;font-weight:600;color:#525f73;margin-right:4px}
.clamev-chip{display:inline-flex;align-items:center;gap:4px;padding:2px 4px 2px 10px;border-radius:6px;background:#e9edff;font-size:13px;color:#141b2c}
.clamev-chip button{border:0;background:transparent;color:#525f73;font-size:16px;line-height:1;padding:0 6px;cursor:pointer}
.clamev-link{border:0;background:transparent;color:#0058c3;font-size:13px;font-weight:600;cursor:pointer;margin-left:6px}
.clamev-ctx{font-size:13px;color:#667085;margin:0 0 8px 2px}
.clamev-kpis{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin-bottom:16px}
.clamev-kpi{display:flex;align-items:flex-start;gap:12px;min-height:80px;padding:14px;background:#fff;border:1px solid #e4e7ec;border-radius:10px;text-align:left;font:inherit;color:inherit;cursor:pointer;box-shadow:0 1px 2px rgba(16,24,40,.05)}
.clamev-kpi[disabled]{cursor:default}
.clamev-kpi-static{cursor:default}
.clamev-kpi-ico{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;background:#f2f4f7;color:#98a2b3}
.clamev-kpi-txt{display:flex;flex-direction:column;min-width:0}
.clamev-kpi-n{font-size:24px;font-weight:600;line-height:1.1}
.clamev-kpi-v{font-size:16px;font-weight:600;line-height:1.5}
.clamev-kpi-l{font-size:13px;color:#475467}
.clamev-kpi-sub{font-size:12px;color:#667085;margin-top:2px}
.clamev-k-zero .clamev-kpi-n,.clamev-k-zero .clamev-kpi-v{color:#667085}
.clamev-k-detection .clamev-kpi-ico,.clamev-k-error .clamev-kpi-ico{background:#fee4e2;color:#d92d20}
.clamev-k-detection .clamev-kpi-n,.clamev-k-error .clamev-kpi-n,.clamev-k-detection .clamev-kpi-v{color:#b42318}
.clamev-k-warning .clamev-kpi-ico{background:#fef0c7;color:#dc6803}
.clamev-k-warning .clamev-kpi-n{color:#b54708}
.clamev-k-reload .clamev-kpi-ico{background:#d1e9ff;color:#1570ef}
.clamev-k-startstop .clamev-kpi-ico{background:#f2f4f7;color:#475467}
.clamev-kpi[aria-pressed=true]{border:2px solid #1570ef;background:#f5f8ff;padding:13px}
.clamev-k-detection[aria-pressed=true],.clamev-k-error[aria-pressed=true]{border-color:#d92d20;background:#fff6f5}
.clamev-k-warning[aria-pressed=true]{border-color:#dc6803;background:#fffaeb}
.clamev-tablecard{overflow:hidden}
.clamev-table{width:100%;border-collapse:collapse;table-layout:fixed;color:#141b2c}
.clamev-table th{background:#f1f3ff;font-size:12px;font-weight:600;color:#525f73;text-align:left;padding:10px 16px;letter-spacing:.02em}
.clamev-table td{padding:0 16px;height:44px;border-top:1px solid #eef0f6;vertical-align:middle;color:#141b2c}
.clamev-c-time{width:170px}
.clamev-c-type{width:170px}
.clamev-c-det{width:56px;text-align:center}
.clamev-row{cursor:pointer}
.clamev-row:hover td{background:#f9fafb}
.clamev-r-det td:first-child{box-shadow:inset 3px 0 0 #d92d20}
.clamev-hms{font-family:'JetBrains Mono',Consolas,monospace;font-size:13px;font-weight:600}
.clamev-day{font-size:12px;color:#667085}
.clamev-msg{font-family:'JetBrains Mono',Consolas,monospace;font-size:13px;color:#424754}
.clamev-ell{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.clamev-sig{color:#b42318;font-weight:600}
.clamev-badge{display:inline-flex;align-items:center;gap:6px;padding:3px 9px;border-radius:6px;font-size:12px;font-weight:600;white-space:nowrap}
.clamev-b-detection{background:#fee4e2;color:#b42318}
.clamev-b-error{background:#fee4e2;color:#b42318}
.clamev-b-warning{background:#fef0c7;color:#b54708}
.clamev-b-reload{background:#d1e9ff;color:#175cd3}
.clamev-b-startstop{background:#f2f4f7;color:#344054}
.clamev-b-info{background:#f2f4f7;color:#475467}
.clamev-chev{display:inline-flex;width:28px;height:28px;align-items:center;justify-content:center;color:#525f73;transition:transform .15s}
.clamev-row[aria-expanded=true] .clamev-chev{transform:rotate(180deg)}
.clamev-drawer td{height:auto;padding:0 16px 12px;border-top:0;background:#fbfcff}
.clamev-raw{display:flex;gap:12px;align-items:flex-start;background:#f1f3ff;border-radius:8px;padding:10px 12px}
.clamev-mono{font-family:'JetBrains Mono',Consolas,monospace;font-size:13px}
.clamev-rawtxt{flex:1;word-break:break-all;color:#141b2c}
.clamev-copy{flex-shrink:0;border:1px solid #c2c6d7;background:#fff;border-radius:6px;font-size:12px;font-weight:600;color:#344054;padding:3px 10px;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.clamev-empty{text-align:center;padding:40px 24px}
.clamev-empty-ico{display:inline-flex;width:56px;height:56px;border-radius:50%;background:#f1f3ff;color:#525f73;align-items:center;justify-content:center;font-size:24px;margin-bottom:12px}
.clamev-empty h4{font-size:16px;font-weight:600;margin:0 0 6px;color:#141b2c}
.clamev-empty p{font-size:14px;color:#525f73;margin:0 auto;max-width:520px}
.clamev-banner{display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border:1px solid #fecdca;background:#fef3f2;border-radius:10px;margin-bottom:16px;color:#b42318}
.clamev-banner-ico{font-size:22px;line-height:1}
.clamev-banner strong{font-size:15px}
.clamev-sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}
@media (max-width:1200px){.clamev-kpis{grid-template-columns:repeat(3,minmax(0,1fr))}.clamev-search-row{flex-wrap:wrap}}
@media (prefers-reduced-motion:reduce){.clamev-chev{transition:none}}
</style>";
}
