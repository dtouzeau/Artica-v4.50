<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

// Evenements du serveur FTP (ProFTPD), maquette stitch_artica_ftp_server_events.
// Source unique : GET /proftpd/events/{motif}/{max} (articarest, rg sur
// /var/log/proftpd.log, lignes les plus recentes d'abord). Le motif est construit
// ici (prefixe de date ancre + mot echappe) ; tout compteur porte sur les seules
// lignes recues, et la page le dit.

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$users=new usersMenus();
if(!$users->AsSystemAdministrator){exit();}
if(isset($_GET["build"])){ftpe_build();exit;}
if(isset($_GET["results"])){ftpe_results();exit;}
page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{ftpe_title}","fas fa-list-check","{ftpe_explain}","$page?build=yes","ftp-events","progress-proftpd-events",false,"ftpe-main");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{ftpe_title}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

// ── Helpers ─────────────────────────────────────────────────────────────────

function ftpe_t($token,$a="",$b="",$c=""):string{
    if(!isset($GLOBALS["FTPE_T"])){$GLOBALS["FTPE_T"]=array();}
    if(!isset($GLOBALS["FTPE_T"][$token])){
        $tpl=new template_admin();
        $GLOBALS["FTPE_T"][$token]=$tpl->_ENGINE_parse_body("{"."$token}");
    }
    return str_replace(array("%1","%2","%3"),array($a,$b,$c),$GLOBALS["FTPE_T"][$token]);
}

function ftpe_h($s):string{
    return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
}

function ftpe_periods():array{
    return array("hour"=>"{ftpe_p_hour}","today"=>"{ftpe_p_today}","yesterday"=>"{ftpe_p_yesterday}","all"=>"{ftpe_p_all}");
}
function ftpe_line_counts():array{
    return array(50,200,500,1000);
}

// Parametres de la recherche : $_GET, sinon la derniere recherche de la session.
function ftpe_params():array{
    $p=isset($_SESSION["FTPE_PARAMS"]) && is_array($_SESSION["FTPE_PARAMS"])?$_SESSION["FTPE_PARAMS"]:array("q"=>"","period"=>"today","lines"=>200);
    if(isset($_GET["period"])){$p["period"]=(string)$_GET["period"];}
    if(isset($_GET["lines"])){$p["lines"]=intval($_GET["lines"]);}
    if(isset($_GET["q"])){$p["q"]=(string)$_GET["q"];}
    if(!isset(ftpe_periods()[$p["period"]])){$p["period"]="today";}
    if(!in_array(intval($p["lines"]),ftpe_line_counts(),true)){$p["lines"]=200;}
    // Mot libre : imprimable, 64 caracteres au plus, sans '/' (segment d'URL).
    $q=preg_replace('#[\x00-\x1f\x7f/\\\\]#u',' ',$p["q"]);
    $p["q"]=trim(mb_substr((string)$q,0,64));
    $_SESSION["FTPE_PARAMS"]=$p;
    return $p;
}

// Motif rg : prefixe de date ancre (lignes "YYYY-MM-DD HH:MM:SS,ms …"), puis le
// mot echappe. "NONE" = pas de filtre (GrepTail lit alors la fin du journal).
// Fonction pure (harnais).
function ftpe_pattern($period,$q,$now):string{
    $prefix="";
    if($period=="hour"){$prefix="^".date("Y-m-d H:",$now);}
    if($period=="today"){$prefix="^".date("Y-m-d",$now);}
    if($period=="yesterday"){$prefix="^".date("Y-m-d",strtotime("-1 day",$now));}
    $word="";
    if($q!==""){
        // Echappement compatible rg (regex Rust) ; '*' et '?' restent litteraux.
        $word=preg_replace('#([.\\\\+*?\[\]^$(){}|\-])#','\\\\$1',$q);
        $word=str_replace(" ","\\s+",$word);
    }
    if($prefix==="" && $word===""){return "NONE";}
    if($word===""){return $prefix;}
    if($prefix===""){return $word;}
    return $prefix.".*".$word;
}

// Analyse d'une ligne du SystemLog. Fonction pure (harnais).
//   2026-11-19 08:36:20,123 host proftpd[1234] host (client.name[203.0.113.7]): USER bob (Login failed): Incorrect password
function ftpe_parse($line):array{
    $r=array("date"=>"","time"=>"","pid"=>"","host"=>"","ip"=>"","msg"=>trim($line),"raw"=>trim($line),"kind"=>"other","account"=>"");
    if(preg_match('#^(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2}:\d{2})(?:[,.]\d+)?\s+\S+\s+proftpd\[(\d+)\]:?\s*(?:\S+\s*)?(?:\(([^\[\)]*)\[([^\]]*)\]\))?:?\s*(.*)$#',$r["raw"],$re)){
        $r["date"]=$re[1];$r["time"]=$re[2];$r["pid"]=$re[3];
        $r["host"]=trim($re[4]);$r["ip"]=trim($re[5]);
        $r["msg"]=trim($re[6])===""?$r["raw"]:trim($re[6]);
    }
    $m=$r["msg"];
    if(preg_match('#SECURITY VIOLATION#i',$m)){$r["kind"]="security";}
    elseif(preg_match('#no such user#i',$m)){$r["kind"]="unknown";}
    elseif(preg_match('#Login failed|Incorrect password#i',$m)){$r["kind"]="wrong";}
    elseif(preg_match('#Login successful#i',$m)){$r["kind"]="signin";}
    elseif(preg_match('#FTP session opened#i',$m)){$r["kind"]="opened";}
    elseif(preg_match('#FTP session closed#i',$m)){$r["kind"]="closed";}
    elseif(preg_match('#Permission denied#i',$m)){$r["kind"]="refused";}
    elseif(preg_match('#STARTUP|standalone mode|daemon started#i',$m)){$r["kind"]="started";}
    elseif(preg_match('#SHUTDOWN|terminating|exiting#i',$m)){$r["kind"]="stopped";}
    if(preg_match('#\bUSER\s+([^\s:()]+)#',$m,$re)){$r["account"]=$re[1];}
    elseif($r["kind"]=="security" && preg_match('#Root login#i',$m)){$r["account"]="root";}
    return $r;
}

// Groupes des filtres rapides.
function ftpe_group($kind):string{
    if($kind=="wrong" || $kind=="unknown"){return "fail";}
    if($kind=="signin"){return "signin";}
    if($kind=="started" || $kind=="stopped"){return "service";}
    return "other";
}

function ftpe_kinds():array{
    return array(
        "signin"=>array("ok","fas fa-right-to-bracket","{ftpe_k_signin}"),
        "wrong"=>array("err","fas fa-key","{ftpe_k_wrong}"),
        "unknown"=>array("err","fas fa-user-xmark","{ftpe_k_unknown}"),
        "opened"=>array("neutral","fas fa-plug","{ftpe_k_opened}"),
        "closed"=>array("neutral","fas fa-plug-circle-xmark","{ftpe_k_closed}"),
        "refused"=>array("warn","fas fa-ban","{ftpe_k_refused}"),
        "security"=>array("err","fas fa-shield-halved","{ftpe_k_security}"),
        "started"=>array("info","fas fa-power-off","{ftpe_k_started}"),
        "stopped"=>array("info","fas fa-power-off","{ftpe_k_stopped}"),
        "other"=>array("neutral","fas fa-circle-info","{ftpe_k_other}"),
    );
}

// ── Modele ──────────────────────────────────────────────────────────────────

function ftpe_model($p):array{
    $m=array("ok"=>false,"error"=>"","missing"=>false,"rows"=>array(),"signin"=>0,"fail"=>0,"service"=>0,
        "ips"=>array(),"accounts"=>array(),"fail_by_ip"=>array(),"fail_by_account"=>array());
    $pattern=ftpe_pattern($p["period"],$p["q"],time());
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/proftpd/events/".rawurlencode($pattern)."/".intval($p["lines"])));
    if(!is_object($json)){$m["error"]="articarest: no answer";return $m;}
    if(property_exists($json,"Status") && !$json->Status){
        $m["error"]=property_exists($json,"Error")?(string)$json->Error:"";
        return $m;
    }
    $m["ok"]=true;
    $err=property_exists($json,"Error")?(string)$json->Error:"";
    if($err!=="" && stripos($err,"does not exist")!==false){$m["missing"]=true;}
    $lines=property_exists($json,"Results") && is_array($json->Results)?$json->Results:array();
    foreach($lines as $l){
        $l=trim((string)$l);
        if($l===""){continue;}
        $r=ftpe_parse($l);
        $r["group"]=ftpe_group($r["kind"]);
        $m["rows"][]=$r;
        if($r["group"]=="signin"){$m["signin"]++;}
        if($r["group"]=="fail"){$m["fail"]++;}
        if($r["group"]=="service"){$m["service"]++;}
        if($r["ip"]!==""){
            $m["ips"][$r["ip"]]=(isset($m["ips"][$r["ip"]])?$m["ips"][$r["ip"]]:0)+1;
            if($r["group"]=="fail"){$m["fail_by_ip"][$r["ip"]]=(isset($m["fail_by_ip"][$r["ip"]])?$m["fail_by_ip"][$r["ip"]]:0)+1;}
        }
        if($r["account"]!==""){
            $m["accounts"][$r["account"]]=true;
            if($r["group"]=="fail"){$m["fail_by_account"][$r["account"]]=(isset($m["fail_by_account"][$r["account"]])?$m["fail_by_account"][$r["account"]]:0)+1;}
        }
    }
    arsort($m["ips"]);
    arsort($m["fail_by_ip"]);
    arsort($m["fail_by_account"]);
    return $m;
}

// ── Contenu ─────────────────────────────────────────────────────────────────

function ftpe_build(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $p=ftpe_params();
    $h=array();
    $h[]="<div class='ftpe'>";
    $h[]=ftpe_hero();
    $h[]=ftpe_searchbar($p);
    $h[]="<div id='ftpe-results'></div>";
    $h[]="</div>";
    $topbuttons=array();
    $topbuttons[]=array("FtpeShow();",ico_refresh,"{refresh}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{ftpe_title}";
    $TINY_ARRAY["ICO"]="fas fa-list-check";
    $TINY_ARRAY["EXPL"]="{ftpe_explain}";
    $TINY_ARRAY["URL"]="ftp-events";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo ftpe_style().$tpl->_ENGINE_parse_body(implode("\n",$h))."<script>".ftpe_js($p)."FtpeShow();$tiny</script>";
}

function ftpe_results(){
    $tpl=new template_admin();
    $p=ftpe_params();
    $m=ftpe_model($p);
    $h=array();
    if(!$m["ok"]){
        $h[]="<div class='ftpe-alert'><i class='fas fa-circle-exclamation'></i><div><strong>{ftpe_unreadable}</strong><br>".ftpe_h($m["error"])."</div></div>";
    }elseif(count($m["rows"])==0){
        $h[]=$m["missing"]?ftpe_empty_missing():ftpe_empty_nomatch($p);
    }else{
        $h[]="<div class='ftpe-caption'>".ftpe_t("ftpe_in_lines",number_format(count($m["rows"])))."</div>";
        $h[]=ftpe_tiles($m);
        $h[]=ftpe_attention($m);
        $h[]=ftpe_table($m);
    }
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Widgets (une fonction chacun) ──────────────────────────────────────────

function ftpe_hero():string{
    return "<div class='ftpe-hero'><div class='ftpe-hero-ico'><i class='fas fa-list-check'></i></div>"
        ."<div class='ftpe-hero-body'><h3>{ftpe_hero_title}</h3><p>{ftpe_hero_text}</p><div class='ftpe-chips'>"
        ."<span class='ftpe-chip'><i class='fas fa-clock'></i> {ftpe_chip_newest}</span>"
        ."<span class='ftpe-chip'><i class='fas fa-eye'></i> {ftpe_chip_readonly}</span>"
        ."<span class='ftpe-chip'><i class='fas fa-lock'></i> {ftpe_chip_nopwd}</span>"
        ."</div></div><div class='ftpe-hero-btns'><a class='ftpe-btn' href='/ftp-users'><i class='fas fa-user-group'></i> {ftpe_accounts}</a>"
        ."<a class='ftpe-btn' href='/ftp-service'><i class='fas fa-sliders'></i> {ftpe_server_settings}</a></div></div>";
}

function ftpe_searchbar($p):string{
    $seg=array();
    foreach(ftpe_periods() as $k=>$label){
        $on=$p["period"]==$k?"true":"false";
        $seg[]="<button type='button' role='radio' aria-checked='$on' data-p='$k' OnClick=\"FtpePeriod('$k');\">$label</button>";
    }
    $opts=array();
    foreach(ftpe_line_counts() as $n){
        $sel=intval($p["lines"])==$n?" selected":"";
        $opts[]="<option value='$n'$sel>".number_format($n)."</option>";
    }
    $ph=ftpe_h(ftpe_t("ftpe_search_ph"));
    $refresh=ftpe_h(ftpe_t("refresh"));
    return "<div class='ftpe-card ftpe-bar'>"
        ."<div class='ftpe-field ftpe-field-q'><label for='ftpe-q'>{ftpe_search}</label>"
        ."<div class='ftpe-search'><i class='fas fa-magnifying-glass'></i><input type='search' id='ftpe-q' value=\"".ftpe_h($p["q"])."\" placeholder=\"$ph\" OnKeyDown=\"if(event.keyCode==13){FtpeShow();}\"></div>"
        ."<div class='ftpe-help'>{ftpe_search_help}</div></div>"
        ."<div class='ftpe-field'><label>{ftpe_period}</label><div class='ftpe-seg' role='radiogroup' aria-label='{ftpe_period}'>".implode("",$seg)."</div></div>"
        ."<div class='ftpe-field'><label for='ftpe-lines'>{ftpe_lines}</label><select id='ftpe-lines' class='ftpe-select' OnChange=\"FtpeShow();\">".implode("",$opts)."</select>"
        ."<div class='ftpe-help'>{ftpe_lines_help}</div></div>"
        ."<div class='ftpe-field ftpe-field-btn'><button type='button' class='ftpe-btn ftpe-btn-primary' OnClick=\"FtpeShow();\"><i class='fas fa-magnifying-glass'></i> {ftpe_show}</button>"
        ."<button type='button' class='ftpe-ibtn' aria-label=\"$refresh\" title=\"$refresh\" OnClick=\"FtpeShow();\"><i class='fas fa-arrows-rotate'></i></button></div>"
        ."</div>";
}

function ftpe_tiles($m):string{
    return "<div class='ftpe-tiles'>".ftpe_tile_signin($m).ftpe_tile_fail($m).ftpe_tile_ips($m).ftpe_tile_accounts($m)."</div>";
}

function ftpe_tile_signin($m):string{
    return "<div class='ftpe-tile'><div><div class='ftpe-tile-l'>{ftpe_t_signin}</div><div class='ftpe-tile-v ftpe-v-ok'>".number_format($m["signin"])."</div></div>"
        ."<div class='ftpe-tile-ico ftpe-ico-ok'><i class='fas fa-right-to-bracket'></i></div></div>";
}

function ftpe_tile_fail($m):string{
    $cls=$m["fail"]>0?" ftpe-v-err":"";
    $click=$m["fail"]>0?" role='button' tabindex='0' OnClick=\"FtpeChip('fail');\"":"";
    return "<div class='ftpe-tile'$click><div><div class='ftpe-tile-l'>{ftpe_t_fail}</div><div class='ftpe-tile-v$cls'>".number_format($m["fail"])."</div>"
        ."<div class='ftpe-tile-s'>{ftpe_t_fail_help}</div></div>"
        ."<div class='ftpe-tile-ico".($m["fail"]>0?" ftpe-ico-err":"")."'><i class='fas fa-triangle-exclamation'></i></div></div>";
}

function ftpe_tile_ips($m):string{
    $n=count($m["ips"]);
    $sub="";
    if($n>0){
        $top=array_keys($m["ips"])[0];
        $sub="<div class='ftpe-tile-s'>".ftpe_t("ftpe_t_ips_top","<span class='ftpe-mono'>".ftpe_h($top)."</span>",number_format($m["ips"][$top]))."</div>";
    }
    return "<div class='ftpe-tile'><div><div class='ftpe-tile-l'>{ftpe_t_ips}</div><div class='ftpe-tile-v'>".number_format($n)." <small>{ftpe_different}</small></div>$sub</div>"
        ."<div class='ftpe-tile-ico'><i class='fas fa-network-wired'></i></div></div>";
}

function ftpe_tile_accounts($m):string{
    $n=count($m["accounts"]);
    $sub="<div class='ftpe-tile-s'>".ftpe_t("ftpe_t_acc_none")."</div>";
    if(count($m["fail_by_account"])>0){
        $top=array_keys($m["fail_by_account"])[0];
        $sub="<div class='ftpe-tile-s'>".ftpe_t("ftpe_t_acc_top","<span class='ftpe-mono'>".ftpe_h($top)."</span>",number_format($m["fail_by_account"][$top]))."</div>";
    }
    return "<div class='ftpe-tile'><div><div class='ftpe-tile-l'>{ftpe_t_accounts}</div><div class='ftpe-tile-v'>".number_format($n)."</div>$sub</div>"
        ."<div class='ftpe-tile-ico'><i class='fas fa-users'></i></div></div>";
}

// Alerte seulement si une adresse cumule au moins 5 echecs dans les lignes recues.
function ftpe_attention($m):string{
    if(count($m["fail_by_ip"])==0){return "";}
    $ip=array_keys($m["fail_by_ip"])[0];
    $n=$m["fail_by_ip"][$ip];
    if($n<5){return "";}
    $ipjs=ftpe_h(json_encode($ip));
    return "<div class='ftpe-attn'><i class='fas fa-triangle-exclamation'></i><div class='ftpe-attn-txt'>"
        .ftpe_t("ftpe_attention","<strong class='ftpe-mono'>".ftpe_h($ip)."</strong>",number_format($n))."</div>"
        ."<button type='button' class='ftpe-btn ftpe-btn-warn' OnClick=\"FtpeOnlyIp($ipjs);\">{ftpe_only_ip}</button></div>";
}

function ftpe_when($r):string{
    if($r["date"]===""){return "<span class='ftpe-muted'>—</span>";}
    $today=date("Y-m-d");
    $yest=date("Y-m-d",strtotime("-1 day"));
    if($r["date"]==$today){$d=ftpe_t("ftpe_today");}
    elseif($r["date"]==$yest){$d=ftpe_t("ftpe_yesterday");}
    else{$d=date("M j",strtotime($r["date"]));}
    return "<div title='".ftpe_h($r["date"]." ".$r["time"])."'><strong>".ftpe_h($r["time"])."</strong><div class='ftpe-sub'>$d</div></div>";
}

function ftpe_row($r,$i):string{
    $k=ftpe_kinds()[$r["kind"]];
    $pill="<span class='ftpe-pill ftpe-pill-{$k[0]}'><i class='{$k[1]}'></i> {$k[2]}</span>";
    $accCls=$r["group"]=="fail" || $r["kind"]=="security"?" ftpe-acc-err":"";
    $acc=$r["account"]!==""?"<span class='ftpe-acc$accCls' title=\"".ftpe_h($r["account"])."\">".ftpe_h($r["account"])."</span>":"<span class='ftpe-muted'>—</span>";
    $from="<span class='ftpe-muted'>—</span>";
    if($r["ip"]!==""){
        $from="<span class='ftpe-ip' title=\"".ftpe_h($r["ip"])."\">".ftpe_h($r["ip"])."</span>";
        if($r["host"]!=="" && $r["host"]!==$r["ip"]){$from.="<div class='ftpe-sub ftpe-host' title=\"".ftpe_h($r["host"])."\">".ftpe_h($r["host"])."</div>";}
    }
    $show=ftpe_h(ftpe_t("ftpe_show_line"));
    return "<tr data-g='{$r["group"]}' data-ip=\"".ftpe_h($r["ip"])."\">"
        ."<td>".ftpe_when($r)."</td><td>$pill</td><td>$acc</td><td>$from</td>"
        ."<td><div class='ftpe-msg'>".ftpe_h($r["msg"])."</div><div class='ftpe-raw' id='ftpe-raw-$i' hidden>".ftpe_h($r["raw"])."</div></td>"
        ."<td class='ftpe-actions'><button type='button' class='ftpe-link' aria-expanded='false' aria-controls='ftpe-raw-$i' OnClick=\"FtpeRaw(this,'ftpe-raw-$i');\">$show</button></td>"
        ."</tr>";
}

function ftpe_table($m):string{
    $rows=array();
    foreach($m["rows"] as $i=>$r){$rows[]=ftpe_row($r,$i);}
    $chips="<div class='ftpe-chipsf' role='radiogroup' aria-label='{ftpe_filter}'>"
        ."<button type='button' role='radio' aria-checked='true' data-c='all' OnClick=\"FtpeChip('all');\">{ftpe_c_all}</button>"
        ."<button type='button' role='radio' aria-checked='false' data-c='fail' OnClick=\"FtpeChip('fail');\">{ftpe_c_fail} <span class='ftpe-cnt ftpe-cnt-err'>".number_format($m["fail"])."</span></button>"
        ."<button type='button' role='radio' aria-checked='false' data-c='signin' OnClick=\"FtpeChip('signin');\">{ftpe_c_signin} <span class='ftpe-cnt ftpe-cnt-ok'>".number_format($m["signin"])."</span></button>"
        ."<button type='button' role='radio' aria-checked='false' data-c='service' OnClick=\"FtpeChip('service');\">{ftpe_c_service} <span class='ftpe-cnt'>".number_format($m["service"])."</span></button>"
        ."</div>";
    return "<div class='ftpe-card'><div class='ftpe-card-h'><h3>{ftpe_events} <span class='ftpe-count'>".number_format(count($m["rows"]))."</span></h3>"
        ."<div class='ftpe-ipfilter' id='ftpe-ipfilter' hidden><i class='fas fa-filter'></i> <span id='ftpe-ipfilter-v' class='ftpe-mono'></span>"
        ."<button type='button' class='ftpe-link' OnClick=\"FtpeOnlyIp('');\">{ftpe_clear}</button></div>$chips</div>"
        ."<div class='ftpe-tablewrap'><table class='ftpe-table' id='ftpe-table'><thead><tr>"
        ."<th>{ftpe_col_when}</th><th>{ftpe_col_what}</th><th>{ftpe_col_account}</th><th>{ftpe_col_from}</th><th>{ftpe_col_details}</th><th></th>"
        ."</tr></thead><tbody>".implode("",$rows)."</tbody></table>"
        ."<div class='ftpe-nomatch' id='ftpe-nomatch' hidden>{ftpe_no_filter_match}</div></div>"
        ."<div class='ftpe-foot'>".ftpe_t("ftpe_footer","<strong>".number_format(count($m["rows"]))."</strong>")."</div></div>";
}

function ftpe_empty_nomatch($p):string{
    return "<div class='ftpe-card ftpe-empty'><div class='ftpe-empty-ico'><i class='fas fa-magnifying-glass'></i></div>"
        ."<h4>{ftpe_nomatch_title}</h4><p>{ftpe_nomatch_text}</p>"
        ."<button type='button' class='ftpe-btn ftpe-btn-primary' OnClick=\"FtpeEverything();\"><i class='fas fa-list'></i> {ftpe_show_everything}</button></div>";
}

function ftpe_empty_missing():string{
    return "<div class='ftpe-card ftpe-empty'><div class='ftpe-empty-ico'><i class='fas fa-file-circle-question'></i></div>"
        ."<h4>{ftpe_missing_title}</h4><p>{ftpe_missing_text}</p>"
        ."<a class='ftpe-btn ftpe-btn-primary' href='/ftp-service'><i class='fas fa-sliders'></i> {ftpe_server_settings}</a></div>";
}

// ── Style et script ─────────────────────────────────────────────────────────

function ftpe_js($p):string{
    $page=CurrentPageName();
    $period=json_encode($p["period"]);
    return "
window.FtpeState={period:$period,chip:'all',ip:''};
function FtpeShow(){
    var q=document.getElementById('ftpe-q'), l=document.getElementById('ftpe-lines');
    var url='$page?results=yes&period='+encodeURIComponent(window.FtpeState.period)
        +'&lines='+encodeURIComponent(l?l.value:'200')+'&q='+encodeURIComponent(q?q.value:'');
    window.FtpeState.chip='all'; window.FtpeState.ip='';
    LoadAjax('ftpe-results',url);
}
function FtpePeriod(p){
    window.FtpeState.period=p;
    var b=document.querySelectorAll('.ftpe-seg button');
    for(var i=0;i<b.length;i++){b[i].setAttribute('aria-checked',b[i].getAttribute('data-p')===p?'true':'false');}
    FtpeShow();
}
function FtpeEverything(){
    var q=document.getElementById('ftpe-q'); if(q){q.value='';}
    FtpePeriod('all');
}
function FtpeChip(c){
    window.FtpeState.chip=c;
    var b=document.querySelectorAll('.ftpe-chipsf button');
    for(var i=0;i<b.length;i++){b[i].setAttribute('aria-checked',b[i].getAttribute('data-c')===c?'true':'false');}
    FtpeApply();
}
function FtpeOnlyIp(ip){
    window.FtpeState.ip=ip;
    var f=document.getElementById('ftpe-ipfilter'), v=document.getElementById('ftpe-ipfilter-v');
    if(f){f.hidden=(ip==='');} if(v){v.textContent=ip;}
    FtpeApply();
}
function FtpeApply(){
    var t=document.getElementById('ftpe-table'); if(!t){return;}
    var rows=t.tBodies[0].rows, n=0, s=window.FtpeState;
    for(var i=0;i<rows.length;i++){
        var r=rows[i], ok=true;
        if(s.chip!=='all' && r.getAttribute('data-g')!==s.chip){ok=false;}
        if(ok && s.ip!=='' && r.getAttribute('data-ip')!==s.ip){ok=false;}
        r.style.display=ok?'':'none'; if(ok){n++;}
    }
    var nm=document.getElementById('ftpe-nomatch'); if(nm){nm.hidden=(n!==0);}
}
function FtpeRaw(btn,id){
    var e=document.getElementById(id); if(!e){return;}
    e.hidden=!e.hidden; btn.setAttribute('aria-expanded',e.hidden?'false':'true');
}
";
}

function ftpe_style():string{
    if(isset($GLOBALS["FTPE_STYLE"])){return "";}
    $GLOBALS["FTPE_STYLE"]=true;
    $accent="#10b981";$soft="rgba(16,185,129,.12)";$line="rgba(16,185,129,.35)";$dark="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    return "<style>
.ftpe{display:flex;flex-direction:column;gap:20px;color:#0f172a;font-size:14px;margin-top:8px}
#ftpe-results{display:flex;flex-direction:column;gap:16px}
#ftpe-results:empty{display:none}
.ftpe-hero{position:relative;display:flex;gap:18px;align-items:flex-start;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px 24px 18px 28px;box-shadow:0 1px 2px rgba(15,23,42,.06);overflow:hidden}
.ftpe-hero:before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:$accent}
.ftpe-hero-ico{flex:0 0 48px;height:48px;border-radius:50%;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:20px}
.ftpe-hero-body{flex:1;min-width:0}
.ftpe-hero h3{margin:0 0 6px 0;font-size:18px;font-weight:600}
.ftpe-hero p{margin:0;font-size:14px;line-height:1.6;color:#475569;max-width:820px}
.ftpe-hero-btns{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.ftpe-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.ftpe-chip{display:inline-flex;align-items:center;gap:6px;border:1px solid $line;background:$soft;border-radius:999px;padding:3px 11px;font-size:12px;color:#334155}
.ftpe-chip i,.ftpe-chip svg{color:$accent}
.ftpe-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06);overflow:hidden}
.ftpe-bar{display:flex;flex-wrap:wrap;align-items:flex-start;gap:20px 28px;padding:18px 22px}
.ftpe-field{display:flex;flex-direction:column;gap:6px;min-width:0}
.ftpe-field label{font-size:13px;font-weight:600;color:#334155;margin:0}
.ftpe-field-q{flex:1 1 360px}
.ftpe-field-btn{flex-direction:row;align-items:center;gap:8px;margin-left:auto;padding-top:24px}
.ftpe-search{display:flex;align-items:center;gap:8px;border:1px solid #cbd5e1;border-radius:8px;padding:0 12px;height:40px;color:#94a3b8;background:#f8fafc}
.ftpe-search input{border:0;outline:0;flex:1;min-width:0;font-size:14px;color:#0f172a;background:transparent;height:38px}
.ftpe-search:focus-within{border-color:$accent;box-shadow:0 0 0 2px $soft;background:#fff}
.ftpe-help{font-size:12px;color:#64748b}
.ftpe-seg,.ftpe-chipsf{display:flex;flex-wrap:wrap;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:3px;gap:2px}
.ftpe-seg button,.ftpe-chipsf button{border:0;background:transparent;border-radius:6px;padding:7px 14px;font-size:13px;color:#475569;cursor:pointer;white-space:nowrap}
.ftpe-seg button[aria-checked=true],.ftpe-chipsf button[aria-checked=true]{background:#fff;color:#0f172a;font-weight:600;box-shadow:0 1px 2px rgba(15,23,42,.1)}
.ftpe-seg button:focus-visible,.ftpe-chipsf button:focus-visible{outline:2px solid $accent}
.ftpe-select{height:40px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px;font-size:14px;min-width:150px;background:#fff;color:#0f172a}
.ftpe-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;background:#fff;border:1px solid #cbd5e1;border-radius:8px;padding:8px 16px;font-size:14px;font-weight:500;color:#334155;cursor:pointer;white-space:nowrap;text-decoration:none}
.ftpe-btn:hover{background:#f8fafc;color:#0f172a;text-decoration:none}
.ftpe-btn:focus-visible,.ftpe-ibtn:focus-visible,.ftpe-link:focus-visible{outline:2px solid $accent;outline-offset:2px}
.ftpe-btn-primary{background:$accent;border-color:$accent;color:#fff;font-weight:600;height:40px}
.ftpe-btn-primary:hover{background:$dark;border-color:$dark;color:#fff}
.ftpe-btn-warn{background:#fef3c7;border-color:#fcd34d;color:#92400e}
.ftpe-btn-warn:hover{background:#fde68a;color:#78350f}
.ftpe-ibtn{width:40px;height:40px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#475569;cursor:pointer}
.ftpe-ibtn:hover{background:#f8fafc}
.ftpe-caption{font-size:14px;color:#475569}
.ftpe-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.ftpe-tile{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 18px;box-shadow:0 1px 2px rgba(15,23,42,.06);min-width:0}
.ftpe-tile>div:first-child{min-width:0}
.ftpe-tile[role=button]{cursor:pointer}
.ftpe-tile[role=button]:focus-visible{outline:2px solid $accent;outline-offset:2px}
.ftpe-tile-l{font-size:14px;color:#475569}
.ftpe-tile-v{margin-top:4px;font-size:28px;font-weight:700;line-height:1.15}
.ftpe-tile-v small{font-size:15px;font-weight:500;color:#64748b}
.ftpe-v-ok{color:#059669}.ftpe-v-err{color:#b91c1c}
.ftpe-tile-s{margin-top:4px;font-size:12px;color:#64748b;overflow-wrap:anywhere}
.ftpe-tile-ico{flex:0 0 42px;height:42px;border-radius:10px;background:#f1f5f9;color:#475569;display:flex;align-items:center;justify-content:center}
.ftpe-ico-ok{background:#ecfdf5;color:#059669}.ftpe-ico-err{background:#fef2f2;color:#b91c1c}
.ftpe-attn{display:flex;align-items:center;gap:14px;background:#fffbeb;border:1px solid #fcd34d;border-radius:12px;padding:14px 18px;color:#92400e}
.ftpe-attn>i,.ftpe-attn>svg{color:#d97706;font-size:18px}
.ftpe-attn-txt{flex:1;min-width:0}
.ftpe-card-h{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;padding:14px 22px;border-bottom:1px solid #e2e8f0}
.ftpe-card-h h3{margin:0;font-size:17px;font-weight:600;display:flex;align-items:center;gap:10px}
.ftpe-count{border:1px solid #e2e8f0;background:#f8fafc;border-radius:999px;padding:1px 10px;font-size:12px;font-weight:600;color:#334155}
.ftpe-cnt{display:inline-block;margin-left:4px;border-radius:999px;padding:0 7px;font-size:11px;font-weight:600;background:#e2e8f0;color:#334155}
.ftpe-cnt-err{background:#fee2e2;color:#b91c1c}.ftpe-cnt-ok{background:#d1fae5;color:#047857}
.ftpe-ipfilter{display:flex;align-items:center;gap:8px;font-size:13px;color:#475569;background:#f1f5f9;border-radius:8px;padding:5px 10px}
.ftpe-ipfilter[hidden]{display:none}
.ftpe-tablewrap{overflow-x:auto}
.ftpe-table{width:100%;border-collapse:collapse;color:#0f172a}
.ftpe-table th{background:#f8fafc;text-align:left;font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#64748b;padding:12px 22px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.ftpe-table td{padding:12px 22px;border-bottom:1px solid #f1f5f9;vertical-align:top;font-size:14px}
.ftpe-table tbody tr:hover td{background:#f8fafc}
.ftpe-table tbody tr:last-child td{border-bottom:0}
.ftpe-sub{font-size:12px;color:#64748b;margin-top:2px}
.ftpe-muted{color:#94a3b8}
.ftpe-host{max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ftpe-mono{font-family:monospace}
.ftpe-pill{display:inline-flex;align-items:center;gap:6px;border-radius:8px;padding:3px 10px;font-size:13px;font-weight:500;border:1px solid transparent;white-space:nowrap}
.ftpe-pill-ok{background:#ecfdf5;color:#047857;border-color:#a7f3d0}
.ftpe-pill-err{background:#fef2f2;color:#b91c1c;border-color:#fecaca}
.ftpe-pill-warn{background:#fffbeb;color:#b45309;border-color:#fcd34d}
.ftpe-pill-info{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}
.ftpe-pill-neutral{background:#f1f5f9;color:#475569;border-color:#e2e8f0}
.ftpe-acc{display:inline-block;max-width:220px;font-family:monospace;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;vertical-align:top}
.ftpe-acc-err{color:#b91c1c}
.ftpe-ip{display:inline-block;max-width:200px;background:#f1f5f9;border-radius:6px;padding:2px 8px;font-family:monospace;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;vertical-align:top}
.ftpe-msg{color:#334155;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;overflow-wrap:anywhere}
.ftpe-raw{margin-top:8px;background:#0f172a;color:#e2e8f0;border-radius:8px;padding:8px 10px;font-family:monospace;font-size:12px;white-space:pre-wrap;overflow-wrap:anywhere}
.ftpe-raw[hidden]{display:none}
.ftpe-actions{text-align:right;white-space:nowrap;width:1%}
.ftpe-link{border:0;background:transparent;color:#64748b;font-size:13px;cursor:pointer;padding:2px 4px}
.ftpe-link:hover{color:$dark;text-decoration:underline}
.ftpe-nomatch{padding:28px;text-align:center;color:#64748b}
.ftpe-nomatch[hidden]{display:none}
.ftpe-foot{padding:12px 22px;border-top:1px solid #e2e8f0;font-size:13px;color:#64748b;background:#f8fafc}
.ftpe-empty{display:flex;flex-direction:column;align-items:center;text-align:center;padding:44px 24px;gap:10px}
.ftpe-empty-ico{width:72px;height:72px;border-radius:50%;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:28px}
.ftpe-empty h4{margin:6px 0 0 0;font-size:17px;font-weight:600}
.ftpe-empty p{margin:0;color:#475569;max-width:520px;line-height:1.6}
.ftpe-alert{display:flex;gap:12px;align-items:flex-start;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:14px 18px;color:#b91c1c}
@media (max-width:1200px){.ftpe-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}.ftpe-field-btn{margin-left:0;padding-top:0}}
@media (prefers-reduced-motion:no-preference){.ftpe-card,.ftpe-tile,.ftpe-hero{animation:ftpeIn .15s ease-out}}
@keyframes ftpeIn{from{opacity:0}to{opacity:1}}
</style>";
}
