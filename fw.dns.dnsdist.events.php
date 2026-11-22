<?php
// stamp 17/11/2026
// Journal du service DNS Firewall (dnsdist) : /var/log/dnsfw.backends.log lu par
// articarest (logfile_search "dnsfw-backends"), etat courant des serveurs DNS lu
// sur /dnsfw/struct, noms des regles sur /dns/dnsdist/rules. Les messages du
// moteur sont traduits en phrases claires, les repetitions consecutives sont
// regroupees. Prefixe de page : dnev_.
// awc_*() : include INCONDITIONNEL (cf. CLAUDE.md, awc-tables).
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){dnev_search();exit;}
if(isset($_GET["build-page"])){dnev_build();exit;}
page();


function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{APP_DNSDIST}&nbsp;&raquo;&nbsp;{events}",ico_eye,
        "{dnev_page_explain}","$page?build-page=yes","dnsfw-service-events","progress-dnsdist-restart",
        false,"table-loader");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

// --- Page : bandeau, hote de l'etat courant, recherche, TINY -----------------------

function dnev_build():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!$tpl->DnsReader()){
        echo $tpl->div_error("{failed}: {ERROR_NO_PRIVS}");
        return false;
    }
    $sb=$tpl->search_block($page,"","","","");
    $sbCss="";
    if(preg_match_all('#<style[^>]*>.*?</style>#is',$sb,$re)){
        $sbCss=implode("",$re[0]);
        $sb=preg_replace('#<style[^>]*>.*?</style>#is',"",$sb);
    }
    $html=array();
    $html[]="<div id='dnev-root'>";
    $html[]=dnev_banner();
    $html[]="<div id='dnev-now'></div>";
    $html[]="<div class='dnev-search'>$sb</div>";
    $html[]="</div>";

    $topbuttons=array();
    $topbuttons[]=array("if(typeof DnevRefresh==='function'){DnevRefresh();}",ico_refresh,"{refresh}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{APP_DNSDIST}&nbsp;&raquo;&nbsp;{events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{dnev_page_explain}";
    $TINY_ARRAY["URL"]="dnsfw-service-events";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo dnev_style().$sbCss.$tpl->_ENGINE_parse_body(implode("\n",$html))."<script>$tiny</script>";
    return true;
}

function dnev_banner():string{
    $chips=array(
        array("fa-solid fa-file-lines","{dnev_chip_source}"),
        array("fa-solid fa-arrow-down-wide-short","{dnev_chip_newest}"),
        array("fa-solid fa-layer-group","{dnev_chip_grouped}"),
        array("fa-solid fa-eye","{dnev_chip_readonly}"),
    );
    $c=array();
    foreach($chips as $x){
        $c[]="<span class='dnev-chip'><i class='{$x[0]}' aria-hidden='true'></i>{$x[1]}</span>";
    }
    return "<div class='dnev-banner'>
<div class='dnev-bico'><i class='fa-solid fa-clipboard-list' aria-hidden='true'></i></div>
<div class='dnev-btxt'><div class='dnev-btitle'>{dnev_banner_title}</div>
<div class='dnev-bdesc'>{dnev_banner_text}</div>
<div class='dnev-chips'>".implode("",$c)."</div></div></div>";
}

// --- Repondeur de la recherche : etat courant + filtres + journal ------------------

function dnev_search():bool{
    $tpl=new template_admin();
    if(!$tpl->DnsReader()){
        echo $tpl->div_error("{failed}: {ERROR_NO_PRIVS}");
        return false;
    }
    $fn=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $div=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["div-id"] ?? ""));
    $m=dnev_model((string)$_GET["search"]);

    $html=array();
    $html[]="<div id='dnev-now-src' class='dnev-now'>".dnev_now($m)."</div>";
    $html[]=dnev_toolbar($m,$fn);
    $html[]=dnev_timeline($m);
    echo dnev_style().$tpl->_ENGINE_parse_body(implode("\n",$html)).dnev_js($fn,$div);
    return true;
}

// --- Modele : lu une seule fois -----------------------------------------------------

function dnev_model(string $search):array{
    $tpl=new template_admin();
    $search=trim($search);
    $MAIN=$tpl->format_search_protocol($search);
    // format_search_protocol() impose 50 lignes sans "max N" : la page en promet 200.
    if(!preg_match('#(^|\s)(max|limit)(\s+|=)[0-9]+#i',$search)){ $MAIN["MAX"]=200; }
    $max=intval($MAIN["MAX"] ?? 0);
    if($max<=0){ $max=200; }
    if($max>1500){ $max=1500; }
    $err="";
    $lines=$tpl->logfile_search("dnsfw-backends",$MAIN,true,200,$err);
    if(!is_array($lines)){ $lines=array(); }

    $m=array("search"=>$search,"term"=>trim((string)($MAIN["TERM"] ?? "")),"max"=>$max,"error"=>$err,
        "rules"=>array(),"servers"=>array(),"svc_ok"=>false,"uptime"=>null,"version"=>"",
        "events"=>array(),"groups"=>array(),"lines"=>0,"first"=>null,"last"=>null,
        "counts"=>array("all"=>0,"outages"=>0,"lifecycle"=>0,"warnings"=>0,"noanswer"=>0,"technical"=>0));

    $rules=acls_api_get("/dns/dnsdist/rules");
    if(is_array($rules)){
        foreach($rules as $r){
            if(is_array($r) && isset($r["id"])){ $m["rules"][intval($r["id"])]=(string)($r["ruleName"] ?? ""); }
        }
    }
    $st=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/struct"));
    if(is_object($st) && !empty($st->Status) && isset($st->Info) && is_object($st->Info)){
        $m["svc_ok"]=true;
        if(isset($st->Info->statistics->uptime) && is_numeric($st->Info->statistics->uptime)){
            $m["uptime"]=intval($st->Info->statistics->uptime);
        }
        $m["version"]=isset($st->Info->version)?(string)$st->Info->version:"";
        if(isset($st->Info->servers) && is_array($st->Info->servers)){
            foreach($st->Info->servers as $s){
                if(!is_object($s)){ continue; }
                $pools=array();
                if(isset($s->pools) && is_array($s->pools)){ foreach($s->pools as $p){ $pools[]=(string)$p; } }
                $m["servers"][]=array(
                    "name"=>(string)($s->name ?? ""),
                    "address"=>(string)($s->address ?? ""),
                    "state"=>(string)($s->state ?? ""),
                    "pools"=>$pools,
                    "failures"=>isset($s->healthCheckFailures)?intval($s->healthCheckFailures):null,
                    "latency"=>isset($s->latency)&&is_numeric($s->latency)?floatval($s->latency):null,
                    "queries"=>isset($s->queries)&&is_numeric($s->queries)?floatval($s->queries):null,
                );
            }
        }
    }

    foreach($lines as $line){
        $ev=dnev_parse((string)$line);
        if($ev===null){ continue; }
        $m["events"][]=$ev;
        $m["lines"]++;
        $m["counts"]["all"]++;
        $m["counts"][$ev["cat"]]++;
        if($ev["ts"]!==null){
            if($m["last"]===null || $ev["ts"]>$m["last"]){ $m["last"]=$ev["ts"]; }
            if($m["first"]===null || $ev["ts"]<$m["first"]){ $m["first"]=$ev["ts"]; }
        }
    }
    // Regroupement des messages consecutifs identiques (meme cle), ordre du plus recent au plus ancien.
    $groups=array();
    foreach($m["events"] as $ev){
        $n=count($groups);
        // Meme message, a moins de 5 min du precedent : sinon ce sont deux evenements distincts.
        $prev=$n>0?$groups[$n-1]["items"][count($groups[$n-1]["items"])-1]:null;
        $close=$prev!==null && ($prev["ts"]===null || $ev["ts"]===null || abs($prev["ts"]-$ev["ts"])<=300);
        if($n>0 && $groups[$n-1]["key"]===$ev["key"] && $close){
            $groups[$n-1]["items"][]=$ev;
            continue;
        }
        $groups[]=array("key"=>$ev["key"],"items"=>array($ev));
    }
    $m["groups"]=$groups;
    return $m;
}

// Une ligne syslog -> evenement classe. Les deux formats d'horodatage de rsyslog sont acceptes.
function dnev_parse(string $line):?array{
    $line=trim($line);
    if($line===""){ return null; }
    $ts=null; $time=""; $pid=""; $msg=$line;
    if(preg_match('#^([A-Z][a-z]{2})\s+(\d{1,2})\s+(\d{2}:\d{2}:\d{2})\s+\S+\s+[^\s\[]+\[(\d+)\]:\s*(.*)$#',$line,$re)){
        $time=$re[3]; $pid=$re[4]; $msg=$re[5];
        $year=intval(date("Y"));
        $t=strtotime("{$re[1]} {$re[2]} $year {$re[3]}");
        if($t!==false && $t>time()+86400){ $t=strtotime("{$re[1]} {$re[2]} ".($year-1)." {$re[3]}"); }
        if($t!==false){ $ts=$t; }
    }elseif(preg_match('#^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})\S*\s+\S+\s+[^\s\[]+\[(\d+)\]:\s*(.*)$#',$line,$re)){
        $time=$re[2]; $pid=$re[3]; $msg=$re[4];
        $t=strtotime("{$re[1]} {$re[2]}");
        if($t!==false){ $ts=$t; }
    }
    $c=dnev_classify($msg);
    $key=$c["kind"]."|".$c["srv"]."|".($c["kind"]=="timeout"?"":preg_replace('#\d+#',"N",$msg));
    // Les details techniques d'un demarrage alternent (ecoute, tampons, serveurs) : un seul groupe.
    if($c["cat"]=="technical" && $c["kind"]!="other"){ $key="technical"; }
    return array("ts"=>$ts,"time"=>$time,"pid"=>$pid,"msg"=>$msg,"kind"=>$c["kind"],"cat"=>$c["cat"],
        "srv"=>$c["srv"],"addr"=>$c["addr"],"arg"=>$c["arg"],"key"=>$key);
}

function dnev_classify(string $msg):array{
    $r=array("kind"=>"other","cat"=>"technical","srv"=>"","addr"=>"","arg"=>"");
    if(preg_match("#Marking downstream (\S+) \(([^)]*)\) as '(up|down)'#i",$msg,$re)){
        $r["kind"]=strtolower($re[3])=="up"?"up":"down"; $r["cat"]="outages"; $r["srv"]=$re[1]; $r["addr"]=$re[2]; return $r;
    }
    if(preg_match("#Timeout while waiting for the health check response.*?from backend (\S+) \(([^)]*)\)#i",$msg,$re)){
        $r["kind"]="timeout"; $r["cat"]="noanswer"; $r["srv"]=$re[1]; $r["addr"]=$re[2]; return $r;
    }
    if(preg_match("#Starting DNS Firewall service#i",$msg)){ $r["kind"]="start"; $r["cat"]="lifecycle"; return $r; }
    if(preg_match("#Exiting on user request#i",$msg)){ $r["kind"]="stop"; $r["cat"]="lifecycle"; return $r; }
    if(preg_match("#Configuration '.*?' OK#i",$msg)){ $r["kind"]="config"; $r["cat"]="lifecycle"; return $r; }
    if(preg_match("#Security Update.*?see (https://(?:dnsdist\.org|docs\.powerdns\.com)/[A-Za-z0-9_./-]+)#i",$msg,$re)){
        $r["kind"]="secupdate"; $r["cat"]="warnings"; $r["arg"]=$re[1]; return $r;
    }
    if(preg_match("#Security Update#i",$msg)){ $r["kind"]="secupdate"; $r["cat"]="warnings"; return $r; }
    if(preg_match("#(Failed to retrieve security status|Error while retrieving the security update)#i",$msg)){
        $r["kind"]="secfail"; $r["cat"]="warnings"; return $r;
    }
    if(preg_match("#^Listening on (\S+)#i",$msg,$re)){ $r["kind"]="listen"; $r["arg"]=$re[1]; return $r; }
    if(preg_match("#^Added downstream server (\S+)#i",$msg,$re)){ $r["kind"]="added"; $r["arg"]=$re[1]; return $r; }
    if(preg_match("#^Raised (send|receive) buffer to \d+ for local address '([^']*)'#i",$msg,$re)){ $r["kind"]="buffer"; $r["arg"]=$re[2]; return $r; }
    if(preg_match("#(Got|Closed) control connection#i",$msg)){ $r["kind"]="control"; return $r; }
    if(preg_match("#plain-text password#i",$msg)){ $r["kind"]="plainpass"; return $r; }
    if(preg_match("#^dnsdist (\S+) comes with ABSOLUTELY NO WARRANTY#i",$msg,$re)){ $r["kind"]="engine"; $r["arg"]=$re[1]; return $r; }
    if(preg_match("#^Webserver launched on (\S+)#i",$msg,$re)){ $r["kind"]="webserver"; $r["arg"]=$re[1]; return $r; }
    if(preg_match("#^ACL allowing queries from: (.+)$#i",$msg,$re)){ $r["kind"]="acl"; $r["arg"]=$re[1]; return $r; }
    if(preg_match("#^(Console ACL allowing|Accepting control connections)#i",$msg)){ $r["kind"]="console"; return $r; }
    if(preg_match("#\b(unable|failed|error|fatal)\b#i",$msg)){ $r["kind"]="error"; $r["cat"]="warnings"; return $r; }
    return $r;
}

// --- Petits outils -------------------------------------------------------------------

// Traduit d'abord, remplace ensuite chaque %s dans l'ordre (les valeurs sont deja encodees).
function dnev_t(string $token,array $args=array()):string{
    $tpl=new template_admin();
    $s=(string)$tpl->_ENGINE_parse_body($token);
    foreach($args as $a){
        $p=strpos($s,"%s");
        if($p===false){ break; }
        $s=substr($s,0,$p).$a.substr($s,$p+2);
    }
    return $s;
}

function dnev_num($v):string{
    if($v===null){ return "&mdash;"; }
    return number_format($v,0,".",",");
}

function dnev_ago(?int $ts):string{
    if($ts===null){ return ""; }
    $d=time()-$ts;
    if($d<60){ return dnev_t("{dnev_ago_now}"); }
    if($d<3600){ return dnev_t("{dnev_ago_min}",array(intval($d/60))); }
    if($d<86400){ return dnev_t("{dnev_ago_hour}",array(intval($d/3600))); }
    return dnev_t("{dnev_ago_day}",array(intval($d/86400)));
}

function dnev_duration(int $s):string{
    $d=intval($s/86400); $h=intval(($s%86400)/3600); $mn=intval(($s%3600)/60);
    if($d>0){ return "{$d} d {$h} h"; }
    if($h>0){ return "{$h} h {$mn} min"; }
    return max(1,$mn)." min";
}

// Nom interne d'un serveur DNS -> libelle lisible. DNS<objet>-<regle>-<index>[-def] (dnsdist/acls/AclsServers.go).
function dnev_srv_label(string $name,array $m):string{
    if(preg_match('#^DNS(\d+)-(\d+)(?:-(\d+))?(-def)?$#',$name,$re)){
        $rid=intval($re[2]);
        $rn=isset($m["rules"][$rid]) && $m["rules"][$rid]!==""?$m["rules"][$rid]:"#$rid";
        $rn=req_html($rn);
        if(!isset($re[3]) || $re[3]===""){ return dnev_t("{dnev_srv_rule}",array($rn)); }
        $idx=intval($re[3])+1;
        if(!empty($re[4])){ return dnev_t("{dnev_srv_rule_def}",array($rn,$idx)); }
        return dnev_t("{dnev_srv_rule_n}",array($rn,$idx));
    }
    return req_html($name);
}

function dnev_srv_html(array $ev,array $m):string{
    $label=dnev_srv_label($ev["srv"],$m);
    $tip=req_html(trim($ev["srv"]." ".$ev["addr"]));
    return "<b class='dnev-srv' title='$tip'>$label</b>";
}

// --- Etat courant ---------------------------------------------------------------------

function dnev_now(array $m):string{
    if(!$m["svc_ok"]){
        return "<div class='dnev-card dnev-card-warn'><div class='dnev-nhead'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i>{dnev_svc_title}</div>
<div class='dnev-nbig'>{dnev_svc_noanswer}</div><div class='dnev-nsub'>{dnev_svc_noanswer_help}</div></div>";
    }
    $h=array(dnev_now_service($m));
    foreach($m["servers"] as $s){ $h[]=dnev_now_server($m,$s); }
    return implode("",$h);
}

function dnev_now_service(array $m):string{
    $up=0; $total=count($m["servers"]);
    foreach($m["servers"] as $s){ if(strtolower($s["state"])=="up"){ $up++; } }
    $run=$m["uptime"]!==null?dnev_t("{dnev_svc_running}",array(dnev_duration($m["uptime"]))):"{dnev_svc_active}";
    $ver=$m["version"]!==""?"<div class='dnev-nsub'>".dnev_t("{dnev_svc_version}",array("<span class='dnev-mono'>".req_html($m["version"])."</span>"))."</div>":"";
    if($total==0){
        $foot="<span class='dnev-muted'>{dnev_svc_noservers}</span>";
    }elseif($up==$total){
        $foot="<i class='fa-solid fa-circle-check dnev-ok' aria-hidden='true'></i>".dnev_t("{dnev_svc_allup}",array($total));
    }else{
        $foot="<i class='fa-solid fa-circle-exclamation dnev-bad' aria-hidden='true'></i>".dnev_t("{dnev_svc_someup}",array($total-$up,$total));
    }
    return "<div class='dnev-card'><div class='dnev-nhead'><span class='dnev-pill dnev-pill-ok'><i class='fa-solid fa-circle-check' aria-hidden='true'></i>{dnev_running}</span>{dnev_svc_title}</div>
<div class='dnev-nbig'>$run</div>$ver<div class='dnev-nfoot'>$foot</div></div>";
}

function dnev_now_server(array $m,array $s):string{
    $state=$s["state"];
    $forced=($state==="UP" || $state==="DOWN");
    $isUp=strtolower($state)=="up";
    if($forced){
        $pill="<span class='dnev-pill dnev-pill-forced'><i class='fa-solid fa-hand' aria-hidden='true'></i>".($isUp?"{dnev_forced_on}":"{dnev_forced_off}")."</span>";
    }elseif($isUp){
        $pill="<span class='dnev-pill dnev-pill-ok'><i class='fa-solid fa-circle-check' aria-hidden='true'></i>{dnev_answering}</span>";
    }else{
        $pill="<span class='dnev-pill dnev-pill-bad'><i class='fa-solid fa-circle-xmark' aria-hidden='true'></i>{dnev_not_answering}</span>";
    }
    $title=dnev_srv_label($s["name"],$m);
    $sub=req_html($s["name"])." &middot; <span class='dnev-nowrap'>".req_html($s["address"])."</span>";
    $line="";
    if(!$isUp){
        if($s["failures"]!==null && $s["failures"]>0){
            $line="<div class='dnev-nline dnev-bad'><i class='fa-solid fa-hourglass-half' aria-hidden='true'></i>".dnev_t("{dnev_srv_failures}",array(dnev_num($s["failures"])))."</div>";
        }
    }else{
        $parts=array();
        if($s["latency"]!==null && $s["queries"]!==null && $s["queries"]>0){
            $parts[]=dnev_t("{dnev_srv_latency}",array(number_format($s["latency"],1,".",",")));
            $parts[]=dnev_t("{dnev_srv_queries}",array(dnev_num($s["queries"])));
        }else{
            $parts[]="{dnev_srv_noquery}";
        }
        if($s["failures"]!==null && $s["failures"]>0){ $parts[]=dnev_t("{dnev_srv_failures}",array(dnev_num($s["failures"]))); }
        $line="<div class='dnev-nline'><i class='fa-solid fa-gauge' aria-hidden='true'></i>".implode(" &middot; ",$parts)."</div>";
    }
    $pool=implode(", ",array_map("req_html",$s["pools"]));
    $foot="";
    if(!$isUp){
        $others=0;
        foreach($m["servers"] as $o){
            if($o["name"]===$s["name"] || strtolower($o["state"])!="up"){ continue; }
            if(count(array_intersect($o["pools"],$s["pools"]))>0){ $others++; }
        }
        $foot=$others>0?dnev_t("{dnev_srv_aside}",array($others)):"{dnev_srv_aside_none}";
        $foot="<div class='dnev-nnote dnev-nnote-bad'>$foot</div>";
    }elseif($pool!==""){
        $foot="<div class='dnev-nnote'><span class='dnev-dot' aria-hidden='true'></span>".dnev_t("{dnev_srv_pool}",array($pool))."</div>";
    }
    $cls=$isUp?"":" dnev-card-down";
    return "<div class='dnev-card$cls'><div class='dnev-shead'><div class='dnev-stitle'><div class='dnev-sname'>$title</div></div>$pill</div><div class='dnev-ssub dnev-mono'>$sub</div>$line$foot</div>";
}

// --- Barre d'outils : aide de recherche, lignes brutes, filtres ----------------------

function dnev_toolbar(array $m,string $fn):string{
    $cats=array("all"=>"{dnev_f_all}","outages"=>"{dnev_f_outages}","lifecycle"=>"{dnev_f_lifecycle}",
        "warnings"=>"{dnev_f_warnings}","noanswer"=>"{dnev_f_noanswer}","technical"=>"{dnev_f_technical}");
    $chips=array();
    foreach($cats as $k=>$label){
        $n=dnev_num($m["counts"][$k]);
        $chips[]="<button type='button' class='dnev-filter' data-f='$k' aria-pressed='".($k=="all"?"true":"false")."' OnClick=\"DnevFilter('$k');\">$label<span class='dnev-fn'>$n</span></button>";
    }
    return "<div class='dnev-card dnev-tools'>
<div class='dnev-trow'><div class='dnev-hint'>{dnev_search_hint}</div>
<div class='dnev-raw-sw'><button type='button' role='switch' aria-checked='false' id='dnev-raw-switch' class='dnev-switch' OnClick=\"DnevRaw();\" aria-labelledby='dnev-raw-lbl'><span></span></button>
<div><div id='dnev-raw-lbl' class='dnev-swlbl'>{dnev_raw}</div><div class='dnev-swhelp'>{dnev_raw_help}</div></div></div></div>
<div class='dnev-filters' role='group'>".implode("",$chips)."</div>
<div class='dnev-muted dnev-small'>".dnev_t("{dnev_counts_note}",array(dnev_num($m["lines"])))."</div></div>";
}

// --- Journal ---------------------------------------------------------------------------

function dnev_timeline(array $m):string{
    $h=array();
    $h[]="<div class='dnev-card dnev-tl' id='dnev-tl'>";
    $h[]="<div class='dnev-tlhead'><span>{dnev_journal}</span><span class='dnev-muted'><i class='fa-solid fa-layer-group' aria-hidden='true'></i> {dnev_chip_grouped}</span></div>";
    if($m["error"]!==""){
        $h[]="<div class='dnev-empty dnev-empty-bad'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i><div class='dnev-etitle'>{dnev_read_error}</div><div class='dnev-mono dnev-small'>".req_html($m["error"])."</div></div>";
    }elseif($m["lines"]==0){
        if($m["term"]!==""){
            $h[]="<div class='dnev-empty'><i class='fa-solid fa-magnifying-glass' aria-hidden='true'></i><div class='dnev-etitle'>".dnev_t("{dnev_nomatch}",array("&laquo;&nbsp;".req_html($m["search"])."&nbsp;&raquo;"))."</div><button type='button' class='dnev-btn' OnClick=\"DnevClear();\">{dnev_clear_search}</button></div>";
        }else{
            $h[]="<div class='dnev-empty'><i class='fa-solid fa-inbox' aria-hidden='true'></i><div class='dnev-etitle'>{dnev_empty_title}</div><div class='dnev-muted'>{dnev_empty_text}</div></div>";
        }
    }else{
        $gi=0;
        foreach($m["groups"] as $g){
            $gi++;
            $h[]=dnev_group($m,$g,$gi);
        }
        $h[]=dnev_footer($m);
    }
    $h[]="</div>";
    return implode("\n",$h);
}

function dnev_kind_icon(string $kind):array{
    $map=array(
        "down"=>array("fa-solid fa-circle-xmark","bad"),
        "up"=>array("fa-solid fa-circle-check","ok"),
        "timeout"=>array("fa-solid fa-hourglass-half","neutral"),
        "start"=>array("fa-solid fa-power-off","info"),
        "stop"=>array("fa-solid fa-circle-stop","neutral"),
        "config"=>array("fa-solid fa-clipboard-check","ok"),
        "secupdate"=>array("fa-solid fa-shield-halved","warn"),
        "secfail"=>array("fa-solid fa-triangle-exclamation","warn"),
        "error"=>array("fa-solid fa-triangle-exclamation","bad"),
    );
    return isset($map[$kind])?$map[$kind]:array("fa-solid fa-gear","light");
}

function dnev_sentence(array $ev,array $m):string{
    $a=req_html($ev["arg"]);
    switch($ev["kind"]){
        case "down": return dnev_t("{dnev_ev_down}",array(dnev_srv_html($ev,$m)));
        case "up": return dnev_t("{dnev_ev_up}",array(dnev_srv_html($ev,$m)));
        case "timeout": return dnev_t("{dnev_ev_timeout}",array(dnev_srv_html($ev,$m)));
        case "start": return "{dnev_ev_start}";
        case "stop": return "{dnev_ev_stop}";
        case "config": return "{dnev_ev_config}";
        case "secupdate": return "{dnev_ev_secupdate}";
        case "secfail": return "{dnev_ev_secfail}";
        case "listen": return dnev_t("{dnev_ev_listen}",array("<span class='dnev-mono'>$a</span>"));
        case "added": return dnev_t("{dnev_ev_added}",array("<span class='dnev-mono'>$a</span>"));
        case "buffer": return dnev_t("{dnev_ev_buffer}",array("<span class='dnev-mono'>$a</span>"));
        case "control": return "{dnev_ev_control}";
        case "plainpass": return "{dnev_ev_plainpass}";
        case "engine": return dnev_t("{dnev_ev_engine}",array("<span class='dnev-mono'>$a</span>"));
        case "webserver": return dnev_t("{dnev_ev_webserver}",array("<span class='dnev-mono'>$a</span>"));
        case "acl": return dnev_t("{dnev_ev_acl}",array("<span class='dnev-mono'>$a</span>"));
        case "console": return "{dnev_ev_console}";
        case "error": return "{dnev_ev_error}";
    }
    return "{dnev_ev_other}";
}

function dnev_time_cell(array $ev):string{
    if($ev["ts"]===null){ return "<div class='dnev-time'><b>&mdash;</b></div>"; }
    $full=date("Y-m-d H:i:s",$ev["ts"]);
    return "<div class='dnev-time' title='$full'><b class='dnev-mono'>".req_html($ev["time"])."</b><span>".dnev_ago($ev["ts"])."</span></div>";
}

function dnev_group(array $m,array $g,int $gi):string{
    $ev=$g["items"][0];
    $n=count($g["items"]);
    $oldest=$g["items"][$n-1];
    $raw=req_html($ev["msg"]);
    $suffix="";
    if($n>1){
        $suffix=" <span class='dnev-grp'>".dnev_t("{dnev_grp}",array(dnev_num($n),req_html($oldest["time"]),req_html($ev["time"])))."</span>";
    }
    // Un demarrage du service = separateur pleine largeur.
    if($ev["kind"]=="start"){
        return "<div class='dnev-divider' data-cat='lifecycle'><span class='dnev-dico'><i class='fa-solid fa-power-off' aria-hidden='true'></i></span>
<div><div class='dnev-dtitle'>{dnev_ev_start} &mdash; <span class='dnev-mono'>".req_html($ev["time"])."</span>$suffix</div><div class='dnev-small dnev-muted'>".dnev_ago($ev["ts"])."</div><div class='dnev-raw'>$raw</div></div></div>";
    }
    list($ico,$tone)=dnev_kind_icon($ev["kind"]);
    $sent=dnev_sentence($ev,$m);
    $mixed=false;
    if($n>1 && $g["key"]=="technical"){
        foreach($g["items"] as $it){ if($it["kind"]!==$ev["kind"]){ $mixed=true; break; } }
    }
    if($mixed){
        $sent=dnev_t("{dnev_ev_techgroup}",array(dnev_num($n)));
        $suffix=" <span class='dnev-grp'>".dnev_t("{dnev_between}",array(req_html($oldest["time"]),req_html($ev["time"])))."</span>";
    }
    // Message technique ou inconnu : le texte d'origine reste visible, la phrase seule ne dit pas tout.
    $detail="";
    if(in_array($ev["kind"],array("other","error"))){ $detail="<div class='dnev-detail'>$raw</div>"; }
    $side=array();
    if($ev["kind"]=="secupdate" && $ev["arg"]!==""){
        $url=req_html($ev["arg"]);
        $side[]="<a class='dnev-btn' href=\"$url\" target='_blank' rel='noopener noreferrer'>{dnev_advisory} <i class='fa-solid fa-arrow-up-right-from-square' aria-hidden='true'></i></a>";
    }
    $sub="";
    if($n>1){
        $side[]="<button type='button' class='dnev-link' aria-expanded='false' aria-controls='dnev-sub-$gi' OnClick=\"DnevToggle($gi,this);\">".dnev_t("{dnev_show_all}",array(dnev_num($n)))." <i class='fa-solid fa-chevron-down' aria-hidden='true'></i></button>";
        $rows=array();
        $cap=300;
        foreach($g["items"] as $i=>$it){
            if($i>=$cap){ break; }
            $rows[]="<div class='dnev-subrow'><span class='dnev-mono'>".req_html($it["time"])."</span><span>".($mixed?dnev_sentence($it,$m):req_html($it["msg"]))."</span></div>";
        }
        if($n>$cap){ $rows[]="<div class='dnev-subrow dnev-muted'>".dnev_t("{dnev_more_hidden}",array(dnev_num($n-$cap)))."</div>"; }
        $sub="<div class='dnev-sub' id='dnev-sub-$gi' hidden>".implode("",$rows)."</div>";
    }
    $sideH=count($side)>0?"<div class='dnev-side'>".implode("",$side)."</div>":"";
    return "<div class='dnev-ev dnev-t-$tone' data-cat='{$ev["cat"]}'>".dnev_time_cell($ev)."
<span class='dnev-ico'><i class='$ico' aria-hidden='true'></i></span>
<div class='dnev-body'><div class='dnev-sent'>$sent$suffix</div>$detail<div class='dnev-raw'>$raw</div>$sub</div>$sideH</div>";
}

function dnev_footer(array $m):string{
    $fmt="H:i:s";
    if($m["first"]!==null && $m["last"]!==null && date("Ymd",$m["first"])!==date("Ymd",$m["last"])){ $fmt="Y-m-d H:i:s"; }
    $from=$m["first"]!==null?"<span class='dnev-mono'>".date($fmt,$m["first"])."</span>":"&mdash;";
    $to=$m["last"]!==null?"<span class='dnev-mono'>".date($fmt,$m["last"])."</span>":"&mdash;";
    $more="";
    if($m["lines"]>=$m["max"] && $m["max"]<1500){
        $next=$m["max"]<500?500:1500;
        $more="<button type='button' class='dnev-btn' OnClick=\"DnevMore($next);\"><i class='fa-solid fa-arrow-down' aria-hidden='true'></i> ".dnev_t("{dnev_load_more}",array(dnev_num($next)))."</button>";
    }
    return "<div class='dnev-foot'><span>".dnev_t("{dnev_showing}",array("<b>".dnev_num($m["lines"])."</b>",$from,$to))."</span>$more</div>";
}

// --- JavaScript ----------------------------------------------------------------------

function dnev_js(string $fn,string $div):string{
    return "<script>
(function(){
  var src=document.getElementById('dnev-now-src'), host=document.getElementById('dnev-now');
  if(src && host){ host.innerHTML=''; host.className='dnev-now'; while(src.firstChild){ host.appendChild(src.firstChild); } src.parentNode.removeChild(src); }
  window.DnevRefresh=function(){ if(typeof window['$fn']==='function'){ window['$fn'](); } };
  function input(){ var d=document.getElementById('$div'); if(!d){return null;} var b=d.closest('.sb-stitch'); return b?b.querySelector('input.form-control'):null; }
  window.DnevMore=function(n){ var i=input(); if(!i){return;} i.value=(i.value.replace(/(^|\\s)(max|limit)(\\s+|=)[0-9]+/ig,'')+' max '+n).trim(); window.DnevRefresh(); };
  window.DnevClear=function(){ var i=input(); if(i){ i.value=''; } window.DnevRefresh(); };
  window.DnevFilter=function(f){
    window.DnevCat=f;
    var bs=document.querySelectorAll('#dnev-root .dnev-filter');
    for(var i=0;i<bs.length;i++){ bs[i].setAttribute('aria-pressed', bs[i].getAttribute('data-f')===f?'true':'false'); }
    var ev=document.querySelectorAll('#dnev-tl [data-cat]');
    for(var j=0;j<ev.length;j++){ ev[j].style.display=(f==='all'||ev[j].getAttribute('data-cat')===f)?'':'none'; }
  };
  window.DnevRaw=function(force){
    var tl=document.getElementById('dnev-tl'), sw=document.getElementById('dnev-raw-switch'); if(!tl||!sw){return;}
    var on=(typeof force==='boolean')?force:sw.getAttribute('aria-checked')!=='true';
    sw.setAttribute('aria-checked',on?'true':'false'); tl.classList.toggle('dnev-show-raw',on);
    try{ localStorage.setItem('dnevRaw',on?'1':'0'); }catch(e){}
  };
  window.DnevToggle=function(id,btn){
    var s=document.getElementById('dnev-sub-'+id); if(!s){return;}
    var open=s.hasAttribute('hidden'); if(open){ s.removeAttribute('hidden'); }else{ s.setAttribute('hidden',''); }
    btn.setAttribute('aria-expanded',open?'true':'false');
  };
  var raw=false; try{ raw=localStorage.getItem('dnevRaw')==='1'; }catch(e){}
  window.DnevRaw(raw);
  window.DnevFilter(window.DnevCat||'all');
})();
</script>";
}

// --- Style -----------------------------------------------------------------------------

function dnev_style():string{
    if(isset($GLOBALS["DNEV_STYLE"])){ return ""; }
    $GLOBALS["DNEV_STYLE"]=true;
    $soft="rgba(16,185,129,.12)"; $acc="#006a62";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_soft"])){ $soft=$a["accent_soft"]; }
        if(!empty($a["accent_dark"])){ $acc=$a["accent_dark"]; }
    }
    return "<style>
#dnev-root{display:flex;flex-direction:column;gap:16px;margin-top:10px;color:#111d23}
#dnev-root *{box-sizing:border-box}
#dnev-root .dnev-mono{font-family:monospace}
#dnev-root .dnev-muted{color:#6d7a77}
#dnev-root .dnev-small{font-size:13px}
#dnev-root .dnev-ok{color:#15803d}
#dnev-root .dnev-bad{color:#ba1a1a}
#dnev-root .dnev-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 2px rgba(0,0,0,.05);padding:16px 18px;min-width:0}
#dnev-root .dnev-banner{display:flex;gap:16px;align-items:flex-start;background:#fff;border:1px solid #e5e7eb;border-left:4px solid $acc;border-radius:12px;padding:18px 20px;box-shadow:0 1px 2px rgba(0,0,0,.05)}
#dnev-root .dnev-bico{flex:0 0 48px;width:48px;height:48px;border-radius:50%;background:$soft;color:$acc;display:flex;align-items:center;justify-content:center;font-size:20px}
#dnev-root .dnev-btitle{font-size:19px;font-weight:600;margin-bottom:4px}
#dnev-root .dnev-bdesc{font-size:14px;color:#3d4947;line-height:1.5;max-width:900px}
#dnev-root .dnev-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
#dnev-root .dnev-chip{display:inline-flex;align-items:center;gap:6px;font-size:13px;padding:4px 10px;border-radius:999px;background:#e3f0f8;color:#3d4947;border:1px solid #d5e3ec}
#dnev-root .dnev-now{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px}
#dnev-root .dnev-now:empty{display:none}
#dnev-root .dnev-card-down{border-top:4px solid #ba1a1a}
#dnev-root .dnev-card-warn{background:#fff8ef;border-color:#ffddb9;grid-column:1/-1}
#dnev-root .dnev-nhead{display:flex;align-items:center;justify-content:space-between;flex-direction:row-reverse;gap:8px;font-size:13px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#3d4947}
#dnev-root .dnev-card-warn .dnev-nhead{flex-direction:row;justify-content:flex-start;color:#865300}
#dnev-root .dnev-nbig{font-size:18px;font-weight:600;margin-top:10px}
#dnev-root .dnev-nsub{font-size:13px;color:#6d7a77;margin-top:2px}
#dnev-root .dnev-nfoot{display:flex;align-items:center;gap:6px;font-size:13px;margin-top:12px;padding-top:10px;border-top:1px solid #eef2f1}
#dnev-root .dnev-shead{display:flex;justify-content:space-between;align-items:flex-start;gap:8px}
#dnev-root .dnev-stitle{min-width:0}
#dnev-root .dnev-nowrap{white-space:nowrap}
#dnev-root .dnev-sname{font-size:15px;font-weight:600;overflow-wrap:anywhere}
#dnev-root .dnev-ssub{font-size:13px;color:#6d7a77;overflow-wrap:break-word}
#dnev-root .dnev-nline{display:flex;align-items:center;gap:6px;font-size:13px;margin-top:10px;color:#3d4947}
#dnev-root .dnev-nline.dnev-bad{color:#ba1a1a;font-weight:600}
#dnev-root .dnev-nnote{display:flex;align-items:center;gap:6px;font-size:13px;margin-top:10px;padding:6px 10px;border-radius:8px;background:#f6f7f9;color:#3d4947}
#dnev-root .dnev-nnote-bad{background:#ffdad6;color:#93000a}
#dnev-root .dnev-dot{width:8px;height:8px;border-radius:50%;background:#15803d;flex:0 0 8px}
#dnev-root .dnev-pill{display:inline-flex;align-items:center;gap:5px;white-space:nowrap;font-size:13px;font-weight:600;padding:3px 10px;border-radius:999px;letter-spacing:0;text-transform:none}
#dnev-root .dnev-pill-ok{background:$soft;color:#0b6b4f}
#dnev-root .dnev-pill-bad{background:#ffdad6;color:#93000a}
#dnev-root .dnev-pill-forced{background:#e3f0f8;color:#3d4947}
#dnev-root .dnev-search .sb-stitch{margin:0}
#dnev-root .dnev-search .sb-stitch .ibox-content{padding:0;background:transparent;border:0}
#dnev-root .dnev-search .sb-stitch .input-group{margin-left:0;width:100%;max-width:100%}
#dnev-root .dnev-search [id^=results-]{display:flex;flex-direction:column;gap:16px}
#dnev-root .dnev-tools{display:flex;flex-direction:column;gap:12px}
#dnev-root .dnev-trow{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}
#dnev-root .dnev-hint{font-size:13px;color:#3d4947}
#dnev-root .dnev-hint .dnev-k{font-family:monospace;background:#f1f5f4;border:1px solid #e0e6e4;border-radius:4px;padding:0 5px}
#dnev-root .dnev-raw-sw{display:flex;align-items:center;gap:10px}
#dnev-root .dnev-switch{position:relative;width:40px;height:22px;border-radius:999px;border:0;background:#bcc9c6;padding:0;cursor:pointer;flex:0 0 40px}
#dnev-root .dnev-switch span{position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;transition:left .15s}
#dnev-root .dnev-switch[aria-checked=true]{background:$acc}
#dnev-root .dnev-switch[aria-checked=true] span{left:21px}
#dnev-root .dnev-switch:focus-visible,#dnev-root .dnev-filter:focus-visible,#dnev-root .dnev-btn:focus-visible,#dnev-root .dnev-link:focus-visible{outline:2px solid $acc;outline-offset:2px}
#dnev-root .dnev-swlbl{font-size:14px;font-weight:600}
#dnev-root .dnev-swhelp{font-size:13px;color:#6d7a77}
#dnev-root .dnev-filters{display:flex;flex-wrap:wrap;gap:8px}
#dnev-root .dnev-filter{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:600;padding:6px 8px 6px 14px;border-radius:999px;border:1px solid #d5dedc;background:#f6f7f9;color:#3d4947;cursor:pointer}
#dnev-root .dnev-filter[aria-pressed=true]{background:$acc;border-color:$acc;color:#fff}
#dnev-root .dnev-fn{display:inline-block;min-width:26px;text-align:center;padding:1px 7px;border-radius:999px;background:#fff;color:#3d4947;font-variant-numeric:tabular-nums}
#dnev-root .dnev-filter[aria-pressed=true] .dnev-fn{background:rgba(255,255,255,.22);color:#fff}
#dnev-root .dnev-tl{padding:0;overflow:hidden}
#dnev-root .dnev-tlhead{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 18px;background:#f6f7f9;border-bottom:1px solid #e5e7eb;font-size:13px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#3d4947}
#dnev-root .dnev-tlhead .dnev-muted{text-transform:none;letter-spacing:0;font-weight:400}
#dnev-root .dnev-ev{display:grid;grid-template-columns:120px 36px minmax(0,1fr) auto;gap:14px;align-items:start;padding:14px 18px;border-bottom:1px solid #eef2f1}
#dnev-root .dnev-time{display:flex;flex-direction:column;font-size:13px;color:#6d7a77}
#dnev-root .dnev-time b{font-size:14px;color:#111d23}
#dnev-root .dnev-ico{width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:15px;background:#f1f5f4;color:#3d4947}
#dnev-root .dnev-t-bad .dnev-ico{background:#ffdad6;color:#ba1a1a}
#dnev-root .dnev-t-ok .dnev-ico{background:$soft;color:#0b6b4f}
#dnev-root .dnev-t-warn .dnev-ico{background:#ffddb9;color:#865300}
#dnev-root .dnev-t-info .dnev-ico{background:#e3f0f8;color:#1d4ed8}
#dnev-root .dnev-t-light .dnev-ico{background:#f6f7f9;color:#6d7a77}
#dnev-root .dnev-t-bad{background:#fffafa}
#dnev-root .dnev-t-warn{background:#fffcf6}
#dnev-root .dnev-sent{font-size:15px;line-height:1.45;overflow-wrap:anywhere}
#dnev-root .dnev-t-light .dnev-sent{font-size:14px;color:#3d4947}
#dnev-root .dnev-t-bad .dnev-srv{color:#ba1a1a}
#dnev-root .dnev-t-ok .dnev-srv{color:#0b6b4f}
#dnev-root .dnev-grp{color:#6d7a77}
#dnev-root .dnev-detail{font-size:13px;color:#3d4947;font-family:monospace;margin-top:4px;overflow-wrap:anywhere}
#dnev-root .dnev-raw{display:none;font-family:monospace;font-size:13px;color:#6d7a77;margin-top:4px;overflow-wrap:anywhere}
#dnev-root .dnev-show-raw .dnev-raw{display:block}
#dnev-root .dnev-side{display:flex;flex-direction:column;align-items:flex-end;gap:6px}
#dnev-root .dnev-btn{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;padding:6px 12px;border-radius:8px;border:1px solid #bcc9c6;background:#fff;color:$acc;cursor:pointer;white-space:nowrap;text-decoration:none}
#dnev-root .dnev-btn:hover{background:#f6f7f9}
#dnev-root .dnev-link{border:0;background:transparent;color:$acc;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;padding:4px 0}
#dnev-root .dnev-link[aria-expanded=true] i{transform:rotate(180deg)}
#dnev-root .dnev-sub{margin-top:8px;border-left:2px solid #e5e7eb;padding-left:10px;max-height:320px;overflow:auto}
#dnev-root .dnev-subrow{display:flex;gap:12px;font-size:13px;color:#3d4947;padding:2px 0}
#dnev-root .dnev-subrow span:last-child{overflow-wrap:anywhere;font-family:monospace;color:#6d7a77}
#dnev-root .dnev-subrow .dnev-mono{flex:0 0 auto}
#dnev-root .dnev-divider{display:flex;gap:14px;align-items:flex-start;padding:12px 18px;background:#eef5fb;border-bottom:1px solid #d5e3ec}
#dnev-root .dnev-dico{width:34px;height:34px;border-radius:50%;background:#1d4ed8;color:#fff;display:flex;align-items:center;justify-content:center;flex:0 0 34px}
#dnev-root .dnev-dtitle{font-size:15px;font-weight:600;color:#1e3a8a}
#dnev-root .dnev-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 18px;font-size:14px;color:#3d4947;background:#fff}
#dnev-root .dnev-empty{display:flex;flex-direction:column;align-items:center;gap:8px;padding:40px 20px;text-align:center;font-size:14px}
#dnev-root .dnev-empty > i{font-size:28px;color:#6d7a77}
#dnev-root .dnev-empty-bad > i{color:#ba1a1a}
#dnev-root .dnev-etitle{font-size:16px;font-weight:600}
@media (prefers-reduced-motion:reduce){#dnev-root .dnev-switch span{transition:none}}
</style>";
}
