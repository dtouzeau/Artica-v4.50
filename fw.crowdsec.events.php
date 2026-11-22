<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();
if(!$users->AsFirewallManager){
    $tpl=new template_admin();
    echo $tpl->div_error("Privileges error");
    exit();
}

if(isset($_GET["form"])){start_form();exit;}
if(isset($_GET["cslog-list"])){cslog_list();exit;}
page();


function page(){
    $page               = CurrentPageName();
    $tpl                = new template_admin();
    $IPTABLES_VERSION   = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_CROWDSEC_VERSION");
    $html=$tpl->page_header("{APP_CROWDSEC} v$IPTABLES_VERSION {events}",
        ico_eye,"{APP_CROWDSEC_EXPLAIN}","$page?form=yes","crowdsec-events",
        "progress-crowdsec-restart",false,"crowdsec-events");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_CROWDSEC}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

// Onglet "Service events" de fw.crowdsec.php : bandeau + barre de recherche + hote rechargé.
// Pas de TINY ici : la page mère possède l'en-tête (un TINY d'onglet l'écrasait).
function start_form():bool{
    $tpl=new template_admin();
    $h=array();
    $h[]="<div class='cslog'>";
    $h[]=cslog_banner();
    $h[]=cslog_toolbar();
    $h[]="<div id='cslog-host' aria-live='polite'><div class='cslog-loading'><i class='fas fa-spinner fa-spin' aria-hidden='true'></i></div></div>";
    $h[]="</div>";
    echo cslog_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).cslog_js();
    return true;
}

// ---------- données ----------

// Fonction et non const : un const de premier niveau placé après l'aiguillage n'est jamais déclaré.
function cslog_filters():array{
    return array(
        "errors"=>"level=(error|fatal)",
        "warnings"=>"level=warning",
        "bans"=>"ban on",
    );
}

function cslog_params():array{
    $q=isset($_GET["q"])?(string)$_GET["q"]:"";
    // Le motif part dans un segment d'URL puis dans rg/grep -E : on retire les
    // métacaractères qui casseraient l'expression ; "*" reste un joker.
    $q=preg_replace('/[\x00-\x1f"\'`\\\\()\[\]{}|^$+?<>]/u',"",$q);
    $q=trim(mb_substr($q,0,120));
    $f=isset($_GET["f"])?(string)$_GET["f"]:"all";
    $fl=cslog_filters();
    if(!isset($fl[$f])){$f="all";}
    $max=intval($_GET["max"] ?? 250);
    if(!in_array($max,array(100,250,500,1000),true)){$max=250;}
    return array("q"=>$q,"f"=>$f,"max"=>$max);
}

function cslog_parse_line(string $line):array{
    $r=array("raw"=>true,"ts"=>0,"tsraw"=>"","level"=>"","msg"=>$line,"extra"=>"","ban"=>false);
    if(!preg_match('#^time="([^"]+)"\s+level=([a-z]+)\s+msg="((?:[^"\\\\]|\\\\.)*)"(.*)$#i',$line,$re)){
        return $r;
    }
    $r["raw"]=false;
    $r["tsraw"]=$re[1];
    try{ $d=new DateTime($re[1]); $r["ts"]=$d->getTimestamp(); }catch(Exception $e){ $r["ts"]=0; }
    $r["level"]=strtolower($re[2]);
    $r["msg"]=str_replace(array('\\"','\\\\'),array('"','\\'),$re[3]);
    $r["extra"]=trim($re[4]);
    $r["ban"]=preg_match('/\bban on\b/i',$r["msg"])===1;
    return $r;
}

function cslog_model(array $p):array{
    $m=array("ok"=>true,"error"=>"","lines"=>array(),"errors"=>0,"warnings"=>0,"bans"=>0,
        "min"=>0,"max"=>0,"p"=>$p,"searched"=>($p["f"]!=="all" || strlen($p["q"])>=3));
    $term="NONE";
    $fl=cslog_filters();
    if($p["f"]!=="all"){$term=$fl[$p["f"]];}
    elseif(strlen($p["q"])>=3){$term=$p["q"];}
    // "/" décodé par le routeur casserait la route : "." (n'importe quel caractère) le remplace.
    $term=str_replace("/",".",$term);
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/events/{$p["max"]}/".rawurlencode($term));
    $json=json_decode($data);
    if(!is_object($json)){
        $m["ok"]=false; $m["error"]=json_last_error_msg(); return $m;
    }
    if(isset($json->Status) && !$json->Status){
        $m["ok"]=false; $m["error"]=isset($json->Error)?(string)$json->Error:""; return $m;
    }
    $logs=(isset($json->Logs) && is_array($json->Logs))?$json->Logs:array();
    // Filtre rapide + texte : le démon ne prend qu'un motif, le texte filtre ensuite les lignes reçues.
    $post=($p["f"]!=="all" && strlen($p["q"])>=3)?str_replace("*","",$p["q"]):"";
    foreach($logs as $line){
        $line=trim((string)$line);
        if($line===""){continue;}
        if($post!=="" && stripos($line,$post)===false){continue;}
        $r=cslog_parse_line($line);
        if($r["level"]==="error" || $r["level"]==="fatal"){$m["errors"]++;}
        if($r["level"]==="warning"){$m["warnings"]++;}
        if($r["ban"]){$m["bans"]++;}
        if($r["ts"]>0){
            if($m["min"]==0 || $r["ts"]<$m["min"]){$m["min"]=$r["ts"];}
            if($r["ts"]>$m["max"]){$m["max"]=$r["ts"];}
        }
        $m["lines"][]=$r;
    }
    return $m;
}

// ---------- aides ----------

function cslog_h($s):string{
    // Les accolades sont encodées : le texte du journal traverse _ENGINE_parse_body().
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8"));
}

function cslog_t(string $key,...$args):string{
    static $cache=array();
    if(!isset($cache[$key])){
        $tpl=new template_admin();
        $cache[$key]=html_entity_decode($tpl->_ENGINE_parse_body("{".$key."}"),ENT_QUOTES,"UTF-8");
    }
    // Texte brut : un retour [br] (ou le <br> qu'en tire le moteur) devient une espace.
    $s=preg_replace('#\[br\]|<br\s*/?>#i'," ",$cache[$key]);
    if(count($args)>0){$s=vsprintf($s,$args);}
    return $s;
}

function cslog_clock(int $ts):string{
    if(date("Ymd",$ts)===date("Ymd")){return date("H:i:s",$ts);}
    return date("M j, H:i:s",$ts);
}

function cslog_ago(int $ts):string{
    $d=time()-$ts;
    if($d<60){return cslog_t("cslog_ago_now");}
    if($d<3600){return cslog_t("cslog_ago_min",intval($d/60));}
    if($d<86400){return cslog_t("cslog_ago_hr",intval($d/3600));}
    return cslog_t("cslog_ago_day",intval($d/86400));
}

// ---------- widgets ----------

function cslog_banner():string{
    $chips=array(
        array("fas fa-arrow-down-wide-short","{cslog_chip_newest}"),
        array("fas fa-magnifying-glass","{cslog_chip_search}"),
        array("fas fa-eye","{cslog_chip_readonly}"),
    );
    $c=array();
    foreach($chips as $x){$c[]="<span class='cslog-chip'><i class='{$x[0]}' aria-hidden='true'></i> {$x[1]}</span>";}
    return "<section class='cslog-banner'><div class='cslog-banner-ico' aria-hidden='true'><i class='fas fa-book-open'></i></div>"
        ."<div><h2 class='cslog-banner-t'>{cslog_banner_title}</h2><p class='cslog-banner-p'>{cslog_banner_text}</p>"
        ."<div class='cslog-chips'>".implode("",$c)."</div></div></section>";
}

function cslog_toolbar():string{
    $f=array(
        array("all","","{cslog_f_all}"),
        array("errors","fas fa-circle-xmark","{cslog_f_errors}"),
        array("warnings","fas fa-triangle-exclamation","{cslog_f_warnings}"),
        array("bans","fas fa-ban","{cslog_f_bans}"),
    );
    $b=array();
    foreach($f as $x){
        $on=$x[0]==="all"?"true":"false";
        $ico=$x[1]!==""?"<i class='{$x[1]}' aria-hidden='true'></i> ":"";
        $b[]="<button type='button' role='radio' aria-checked='$on' class='cslog-qf' data-f='{$x[0]}' OnClick=\"CsLogFilter('{$x[0]}')\">$ico{$x[2]}</button>";
    }
    $opts=array();
    foreach(array(100,250,500,1000) as $n){
        $sel=$n==250?" selected":"";
        $opts[]="<option value='$n'$sel>".cslog_t("cslog_lines_n",$n)."</option>";
    }
    return "<section class='cslog-bar' aria-label=\"{cslog_bar_label}\">"
        ."<div class='cslog-search'><label for='cslog-q' class='cslog-sr'>{cslog_search_label}</label>"
        ."<div class='cslog-input'><span class='cslog-input-ico' aria-hidden='true'><i class='fas fa-magnifying-glass'></i></span>"
        ."<input id='cslog-q' type='search' autocomplete='off' placeholder=\"{cslog_search_ph}\" OnInput='CsLogInput()' OnKeyDown='CsLogKey(event)'></div>"
        ."<div class='cslog-help'>{cslog_search_help}</div></div>"
        ."<div class='cslog-qfs' role='radiogroup' aria-label=\"{cslog_qf_label}\">".implode("",$b)."</div>"
        ."<div class='cslog-right'><label for='cslog-max' class='cslog-lbl'>{cslog_show}</label>"
        ."<select id='cslog-max' class='cslog-select' OnChange='CsLogLoad()' title=\"{cslog_show_help}\">".implode("",$opts)."</select>"
        ."<button type='button' class='cslog-btn-ico' OnClick='CsLogLoad()' aria-label=\"{refresh}\" title=\"{refresh}\"><i class='fas fa-rotate' aria-hidden='true'></i></button></div>"
        ."</section>";
}

function cslog_summary(array $m):string{
    $n=count($m["lines"]);
    $h=array();
    $h[]="<span class='cslog-sum-n'>".cslog_h($n==1?cslog_t("cslog_shown_one"):cslog_t("cslog_shown",$n))."</span>";
    $pills=array(
        array("errors","cslog-pill-err","fas fa-circle-xmark",$m["errors"],"cslog_n_errors","cslog_n_error"),
        array("warnings","cslog-pill-warn","fas fa-triangle-exclamation",$m["warnings"],"cslog_n_warnings","cslog_n_warning"),
        array("bans","cslog-pill-ban","fas fa-ban",$m["bans"],"cslog_n_bans","cslog_n_ban"),
    );
    foreach($pills as $x){
        $txt=$x[3]==1?cslog_t($x[5]):cslog_t($x[4],$x[3]);
        $cls=$x[3]>0?$x[1]:"cslog-pill-zero";
        $h[]="<button type='button' class='cslog-pill $cls' OnClick=\"CsLogFilter('{$x[0]}')\"><i class='{$x[2]}' aria-hidden='true'></i> ".cslog_h($txt)."</button>";
    }
    if($m["min"]>0){
        $h[]="<span class='cslog-range'>".cslog_h(cslog_t("cslog_range",cslog_clock($m["min"]),cslog_clock($m["max"])))."</span>";
    }
    return "<div class='cslog-sum'><div class='cslog-sum-l'>".implode("",$h)."</div><div class='cslog-sum-note'>{cslog_sum_note}</div></div>";
}

function cslog_level_badge(array $r):string{
    if($r["raw"]){return "<span class='cslog-lv cslog-lv-raw'><i class='fas fa-align-left' aria-hidden='true'></i> {cslog_lv_raw}</span>";}
    $map=array(
        "info"=>array("cslog-lv-info","fas fa-circle-info","{cslog_lv_info}"),
        "warning"=>array("cslog-lv-warn","fas fa-triangle-exclamation","{cslog_lv_warning}"),
        "error"=>array("cslog-lv-err","fas fa-circle-xmark","{cslog_lv_error}"),
        "fatal"=>array("cslog-lv-err cslog-lv-fatal","fas fa-circle-xmark","{cslog_lv_fatal}"),
        "debug"=>array("cslog-lv-dbg","fas fa-bug","{cslog_lv_debug}"),
        "trace"=>array("cslog-lv-dbg","fas fa-bug","{cslog_lv_trace}"),
    );
    if(!isset($map[$r["level"]])){
        return "<span class='cslog-lv cslog-lv-info'>".cslog_h($r["level"])."</span>";
    }
    $x=$map[$r["level"]];
    return "<span class='cslog-lv {$x[0]}'><i class='{$x[1]}' aria-hidden='true'></i> {$x[2]}</span>";
}

function cslog_when(array $r):string{
    if($r["ts"]<=0){return "<span class='cslog-none' aria-label=\"{cslog_no_time}\">&mdash;</span>";}
    return "<div class='cslog-ago'>".cslog_h(cslog_ago($r["ts"]))."</div>"
        ."<div class='cslog-clock' title=\"".cslog_h($r["tsraw"])."\">".cslog_h(cslog_clock($r["ts"]))."</div>";
}

function cslog_message_html(array $r,string $hl):string{
    $txt=cslog_h($r["msg"]);
    // Adresses IPv4, puis IPv6 avec "::" (pas d'horodatage pris pour une IPv6).
    $txt=preg_replace('/\b(?:\d{1,3}\.){3}\d{1,3}\b/','<span class="cslog-ip">$0</span>',$txt);
    $txt=preg_replace('/(?<![\w:])(?:[0-9a-f]{1,4}:){1,7}:(?:[0-9a-f]{1,4}(?::[0-9a-f]{1,4}){0,6})?(?![\w:])/i','<span class="cslog-ip">$0</span>',$txt);
    if($hl!==""){
        $needle=cslog_h($hl);
        $parts=preg_split('/(<[^>]+>)/',$txt,-1,PREG_SPLIT_DELIM_CAPTURE);
        $out=array();
        foreach($parts as $pt){
            if($pt!=="" && $pt[0]==="<"){$out[]=$pt;continue;}
            $out[]=preg_replace('/'.preg_quote($needle,'/').'/i','<mark class="cslog-hl">$0</mark>',$pt);
        }
        $txt=implode("",$out);
    }
    if($r["extra"]!==""){$txt.=" <span class='cslog-extra'>".cslog_h($r["extra"])."</span>";}
    $tag=$r["ban"]?"<span class='cslog-tag'><i class='fas fa-ban' aria-hidden='true'></i> {cslog_blocked}</span>":"";
    $long=mb_strlen($r["msg"].$r["extra"])>220;
    $cls=$long?"cslog-msg cslog-clamp":"cslog-msg";
    $more=$long?"<button type='button' class='cslog-more' aria-expanded='false' data-more=\"{cslog_show_more}\" data-less=\"{cslog_show_less}\" OnClick='CsLogMore(this)'>{cslog_show_more}</button>":"";
    return "<div class='$cls'>$tag$txt</div>$more";
}

function cslog_row(array $r,string $hl):string{
    $cls="";
    if($r["ban"]){$cls="cslog-r-ban";}
    if($r["level"]==="error" || $r["level"]==="fatal"){$cls="cslog-r-err";}
    return "<tr class='$cls'><td class='cslog-c-when'>".cslog_when($r)."</td><td class='cslog-c-lv'>".cslog_level_badge($r)."</td>"
        ."<td>".cslog_message_html($r,$hl)."</td></tr>";
}

function cslog_table(array $m):string{
    $hl=str_replace("*","",$m["p"]["q"]);
    if(strlen($hl)<3){$hl="";}
    $rows=array();
    foreach($m["lines"] as $r){$rows[]=cslog_row($r,$hl);}
    return "<div class='cslog-card'><table class='cslog-tbl'><thead><tr><th scope='col' class='cslog-c-when'>{cslog_col_when}</th>"
        ."<th scope='col' class='cslog-c-lv'>{cslog_col_level}</th><th scope='col'>{cslog_col_msg}</th></tr></thead>"
        ."<tbody>".implode("",$rows)."</tbody></table></div>";
}

function cslog_empty(array $m):string{
    if(!$m["searched"]){
        return "<div class='cslog-card cslog-empty'><span class='cslog-empty-ico' aria-hidden='true'><i class='fas fa-book-open'></i></span>"
            ."<div class='cslog-empty-t'>{cslog_empty_title}</div><p>{cslog_empty_text}</p></div>";
    }
    $what=$m["p"]["q"];
    if($m["p"]["f"]!=="all"){$what=cslog_t("cslog_f_".$m["p"]["f"]).($what!==""?" + ".$what:"");}
    return "<div class='cslog-card cslog-empty'><span class='cslog-empty-ico' aria-hidden='true'><i class='fas fa-magnifying-glass'></i></span>"
        ."<div class='cslog-empty-t'>".cslog_h(cslog_t("cslog_nomatch_title",$what))."</div>"
        ."<p>".cslog_h(cslog_t("cslog_nomatch_text",$m["p"]["max"]))."</p>"
        ."<button type='button' class='cslog-btn' OnClick='CsLogClear()'><i class='fas fa-xmark' aria-hidden='true'></i> {cslog_clear}</button></div>";
}

function cslog_error(array $m):string{
    $e=$m["error"]!==""?"<p class='cslog-err-d'>".cslog_h($m["error"])."</p>":"";
    return "<div class='cslog-errbox' role='alert'><span class='cslog-errbox-ico' aria-hidden='true'><i class='fas fa-circle-xmark'></i></span>"
        ."<div><div class='cslog-empty-t'>{cslog_err_title}</div>$e</div></div>";
}

function cslog_list():bool{
    $tpl=new template_admin();
    $m=cslog_model(cslog_params());
    if(!$m["ok"]){echo $tpl->_ENGINE_parse_body(cslog_error($m));return false;}
    if(count($m["lines"])==0){echo $tpl->_ENGINE_parse_body(cslog_empty($m));return true;}
    echo $tpl->_ENGINE_parse_body(cslog_summary($m).cslog_table($m));
    return true;
}

// ---------- style et script ----------

function cslog_style():string{
    $accent="#005447";$dark="#003d33";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        $accent=$a["accent"];$dark=$a["accent_dark"];
    }
    return "<style>
.cslog{margin-top:16px;font-size:14px;color:#374151}
.cslog *{box-sizing:border-box}
.cslog-sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)}
.cslog-banner{display:flex;gap:16px;background:#fff;border:1px solid #e5e7eb;border-left:4px solid $accent;border-radius:12px;padding:20px;box-shadow:0 1px 2px rgba(0,0,0,.05)}
.cslog-banner-ico{flex:0 0 40px;height:40px;border-radius:50%;background:#e6eeed;color:$accent;display:flex;align-items:center;justify-content:center;font-size:18px}
.cslog-banner-t{margin:0 0 6px;font-size:18px;font-weight:600;color:#111827}
.cslog-banner-p{margin:0;color:#374151;line-height:1.55}
.cslog-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.cslog-chip{display:inline-flex;align-items:center;gap:6px;background:#f3f4f6;color:#374151;border-radius:999px;padding:4px 12px;font-size:13px}
.cslog-bar{display:flex;flex-wrap:wrap;align-items:flex-start;gap:16px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px 16px;margin-top:16px;box-shadow:0 1px 2px rgba(0,0,0,.05)}
.cslog-search{flex:1 1 380px;max-width:520px}
.cslog-input{position:relative}
.cslog-input-ico{position:absolute;left:12px;top:0;height:40px;display:flex;align-items:center;color:#6b7280;font-size:14px;pointer-events:none}
.cslog-input input{width:100%;height:40px;border:1px solid #d1d5db;border-radius:8px;padding:0 12px 0 36px;font-size:14px;color:#111827;background:#fff}
.cslog-input input:focus,.cslog-select:focus,.cslog-qf:focus-visible,.cslog-btn-ico:focus-visible,.cslog-pill:focus-visible,.cslog-more:focus-visible,.cslog-btn:focus-visible{outline:2px solid $accent;outline-offset:1px}
.cslog-help{font-size:13px;color:#6b7280;margin-top:4px}
.cslog-qfs{display:flex;gap:4px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:4px}
.cslog-qf{border:0;background:transparent;border-radius:8px;padding:6px 12px;font-size:14px;color:#374151;cursor:pointer;display:inline-flex;align-items:center;gap:6px;height:32px}
.cslog-qf:hover{background:#eef2f1}
.cslog-qf[data-f=errors] svg,.cslog-qf[data-f=errors] i{color:#b91c1c}.cslog-qf[data-f=warnings] svg,.cslog-qf[data-f=warnings] i{color:#92400e}.cslog-qf[data-f=bans] svg,.cslog-qf[data-f=bans] i{color:$accent}
.cslog-qf[aria-checked=true]{background:$accent;color:#fff}
.cslog-qf[aria-checked=true] i,.cslog-qf[aria-checked=true] svg{color:#fff}
.cslog-right{margin-left:auto;display:flex;align-items:center;gap:8px}
.cslog-lbl{font-size:13px;color:#374151;margin:0;font-weight:400}
.cslog-select{height:40px;border:1px solid #d1d5db;border-radius:8px;padding:0 10px;font-size:14px;background:#fff;color:#111827}
.cslog-btn-ico{width:40px;height:40px;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:$accent;cursor:pointer}
.cslog-btn-ico:hover{background:#e6eeed}
.cslog-loading{padding:48px;text-align:center;color:#6b7280;font-size:20px}
.cslog-sum{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px;margin:16px 4px 10px;font-size:13px}
.cslog-sum-l{display:flex;flex-wrap:wrap;align-items:center;gap:8px}
.cslog-sum-n{color:#111827;font-weight:600;margin-right:4px}
.cslog-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:3px 12px;font-size:13px;border:1px solid transparent;cursor:pointer}
.cslog-pill-err{background:#fef2f2;color:#b91c1c;border-color:#fecaca}
.cslog-pill-warn{background:#fffbeb;color:#92400e;border-color:#fde68a}
.cslog-pill-ban{background:#e6eeed;color:$accent;border-color:#bfd4d1}
.cslog-pill-zero{background:#f3f4f6;color:#4b5563;border-color:#e5e7eb}
.cslog-range{color:#6b7280;margin-left:4px}
.cslog-sum-note{color:#6b7280;font-style:italic}
.cslog-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 2px rgba(0,0,0,.05);overflow:hidden}
.cslog-tbl{width:100%;border-collapse:separate;border-spacing:0;table-layout:fixed;color:#374151}
.cslog-tbl thead th{position:sticky;top:0;background:#f9fafb;color:#6b7280;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;text-align:left;padding:12px 16px;border-bottom:1px solid #e5e7eb}
.cslog-tbl td{padding:10px 16px;border-bottom:1px solid #f1f5f9;vertical-align:top;color:#374151}
.cslog-tbl tbody tr:nth-child(even) td{background:#f9fafb}
.cslog-tbl tbody tr:hover td{background:#f2f6f6}
.cslog-tbl tbody tr:last-child td{border-bottom:0}
.cslog-c-when{width:150px}.cslog-c-lv{width:130px}
.cslog-r-ban td:first-child{box-shadow:inset 3px 0 0 $accent}
.cslog-r-err td:first-child{box-shadow:inset 3px 0 0 #b91c1c}
.cslog-ago{font-size:14px;font-weight:600;color:#111827}
.cslog-clock{font-size:12px;color:#6b7280;font-family:monospace;margin-top:2px}
.cslog-none{color:#9ca3af}
.cslog-lv{display:inline-flex;align-items:center;gap:6px;border-radius:6px;padding:3px 10px;font-size:13px;border:1px solid transparent;white-space:nowrap}
.cslog-lv-info{background:#f3f4f6;color:#374151;border-color:#e5e7eb}
.cslog-lv-warn{background:#fffbeb;color:#92400e;border-color:#fde68a}
.cslog-lv-err{background:#fef2f2;color:#b91c1c;border-color:#fecaca}
.cslog-lv-fatal{font-weight:700}
.cslog-lv-dbg{background:#f9fafb;color:#6b7280;border-color:#e5e7eb}
.cslog-lv-raw{background:#f3f4f6;color:#4b5563;border-color:#e5e7eb}
.cslog-msg{font-family:monospace;font-size:13px;line-height:1.6;color:#1f2937;word-break:break-word;white-space:pre-wrap}
.cslog-r-err .cslog-msg{color:#991b1b}
.cslog-clamp{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.cslog-clamp.cslog-open{display:block;-webkit-line-clamp:unset}
.cslog-more{border:0;background:none;padding:2px 0;color:$accent;font-size:13px;font-weight:600;cursor:pointer}
.cslog-more:hover{color:$dark;text-decoration:underline}
.cslog-ip{background:#e6eeed;color:$dark;border-radius:4px;padding:0 4px}
.cslog-hl{background:#fef3c7;color:inherit;padding:0 1px;border-radius:2px}
.cslog-extra{color:#6b7280}
.cslog-tag{display:inline-flex;align-items:center;gap:5px;background:$accent;color:#fff;border-radius:6px;padding:1px 8px;font-family:'Open Sans',sans-serif;font-size:13px;font-weight:600;margin-right:8px;white-space:nowrap}
.cslog-empty{text-align:center;padding:48px 24px;margin-top:16px}
.cslog-empty-ico{font-size:24px;color:$accent;background:#e6eeed;width:56px;height:56px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center}
.cslog-empty-t{font-size:16px;font-weight:600;color:#111827;margin:12px 0 4px}
.cslog-empty p{color:#6b7280;margin:0 auto 12px;max-width:560px}
.cslog-btn{border:1px solid #bfd4d1;background:#fff;color:$accent;border-radius:8px;padding:8px 14px;font-size:14px;font-weight:600;cursor:pointer}
.cslog-btn:hover{background:#e6eeed}
.cslog-errbox{display:flex;gap:12px;align-items:flex-start;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:12px;padding:16px 20px;margin-top:16px}
.cslog-errbox-ico{font-size:20px;line-height:1;margin-top:2px}
.cslog-errbox .cslog-empty-t{margin-top:0}
.cslog-errbox .cslog-empty-t{color:#991b1b}
.cslog-err-d{margin:0;font-family:monospace;font-size:13px}
@media (max-width:1200px){.cslog-qfs{order:3;flex-basis:100%;justify-content:flex-start;background:transparent;border:0;padding:0}}
@media (prefers-reduced-motion:no-preference){.cslog-qf,.cslog-pill,.cslog-btn-ico,.cslog-tbl tbody tr td{transition:background-color .15s}}
</style>";
}

function cslog_js():string{
    $page=CurrentPageName();
    return "<script>
(function(){
var S={f:'all',t:null};
function val(id){var e=document.getElementById(id);return e?e.value:'';}
window.CsLogLoad=function(){
  if(S.t){clearTimeout(S.t);S.t=null;}
  if(!document.getElementById('cslog-host')){return;}
  var u='$page?cslog-list=yes&f='+encodeURIComponent(S.f)+'&max='+encodeURIComponent(val('cslog-max')||'250')+'&q='+encodeURIComponent(val('cslog-q'));
  LoadAjaxSilent('cslog-host',u);
};
window.CsLogInput=function(){if(S.t){clearTimeout(S.t);}S.t=setTimeout(CsLogLoad,600);};
window.CsLogKey=function(e){if(e&&e.keyCode===13){e.preventDefault();CsLogLoad();}};
window.CsLogFilter=function(f){
  S.f=f;
  var b=document.querySelectorAll('.cslog-qf');
  for(var i=0;i<b.length;i++){b[i].setAttribute('aria-checked',b[i].getAttribute('data-f')===f?'true':'false');}
  CsLogLoad();
};
window.CsLogClear=function(){var q=document.getElementById('cslog-q');if(q){q.value='';}CsLogFilter('all');};
window.CsLogMore=function(btn){
  var m=btn.previousElementSibling;if(!m){return;}
  var open=m.className.indexOf('cslog-open')<0;
  m.className=open?m.className+' cslog-open':m.className.replace(' cslog-open','');
  btn.setAttribute('aria-expanded',open?'true':'false');
  btn.textContent=open?btn.getAttribute('data-less'):btn.getAttribute('data-more');
};
CsLogLoad();
})();
</script>";
}
