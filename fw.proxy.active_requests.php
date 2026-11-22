<?php
/**
 * Squid Active Requests Monitor
 * Displays real-time active connections (KPI tiles + ranked lists, no chart library)
 *
 * API Endpoints Used:
 * - GET /squid/active-requests/current - Current stats summary
 * - GET /squid/active-requests/by-client - Stats grouped by client
 * - GET /squid/active-requests/by-hostname - Stats grouped by hostname
 * - GET /squid/active-requests/stats - Collector state and retention
 * - POST /squid/active-requests/run - Trigger data collection
 */
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.sqstats.inc");
include_once(dirname(__FILE__)."/ressources/class.netagents.tenants.inc");
// Artica Meta : avec ?id=<noeud>, toute la page lit /netagents/realtimedb/* (et declenche une
// collecte) pour ce noeud. Reserve aux comptes Meta ; l'usage local (sans id) est inchange.
natn_node_id_gate(array("id"));

// Action handlers
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["charts-tab"])){charts_tab();exit;}
if(isset($_GET["stats-host"])){stats_host();exit;}
if(isset($_GET["refresh-run"])){refresh_run();exit;}
if(isset($_GET["group-by-host"])){group_by_hosts();exit;}
if(isset($_GET["group-by-host-table"])){group_by_hosts_table();exit;}
if(isset($_GET["group-by-username"])){group_by_usernames();exit;}
if(isset($_GET["group-by-username-table"])){group_by_usernames_table();exit;}
if(isset($_GET["url-js"])){url_js();exit;}
if(isset($_GET["url-popup"])){url_popup();exit;}
if(isset($_GET["ip-js"])){ip_js();exit;}
if(isset($_GET["ip-popup"])){ip_popup();exit;}
if(isset($_GET["table"])){table();exit;}
js();


function MetaID():array{
    if(!isset($_GET["id"])){
        return array(0,"");
    }
    $id=intval($_GET["id"]);
    if($id>0){
        return array($id,"&id=$id");
    }
    return array(0,"");
}

function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    list($id,$addon)=MetaID();
    // Les totaux vivent dans l'onglet Statistics (rafraichi) : un titre fige a l'ouverture
    // affichait des chiffres qui contredisaient les tuiles quelques minutes plus tard.
    return $tpl->js_dialog9("{active_requests}","$page?start=yes$addon",1100);
}

function url_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $url=$_GET["url-js"];
    $urlencoded=urlencode($url);
    list($id,$addon)=MetaID();
    return $tpl->js_dialog10("{active_requests}: $url","$page?url-popup=$urlencoded$addon",750);
}

function ip_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $url=$_GET["ip-js"];
    $urlencoded=urlencode($url);
    list($id,$addon)=MetaID();
    return $tpl->js_dialog11("{active_requests}: $url","$page?ip-popup=$urlencoded$addon",990);
}

function start():bool{

    list($id,$addon)=MetaID();
    $page=CurrentPageName();
    echo "<div id='active-requests-progress'></div>";
    echo "<script>LoadAjax('active-requests-progress','$page?tabs=yes$addon');</script>";
    return true;
}

function tabs():bool{
    $page=CurrentPageName();
    list($id,$addon)=MetaID();
    $tpl=new template_admin();
    $group["{statistics}"]="$page?charts-tab=yes$addon";
    $group["{websites}"]="$page?group-by-host=yes$addon";
    $group["{members}"]="$page?group-by-username=yes$addon";
    echo $tpl->tabs_default($group);
    return true;
}

// ---------------------------------------------------------------------------
// Onglet Statistics (maquette Stitch stitch_proxy_active_requests_dashboard).
// Un seul modele (ar_model) lit les routes UNE fois ; chaque widget l'affiche.
// Le collecteur prend une photo toutes les 3 minutes (Each3Minutes) ou sur
// Refresh : l'hote est recharge toutes les 30 s, l'age de la photo avance en JS.
// ---------------------------------------------------------------------------

function charts_tab():bool{
    $page=CurrentPageName();
    list($id,$addon)=MetaID();
    $html=array();
    $html[]="<div id='ar-stats-host' class='ak ar' style='margin-top:12px'></div>";
    $html[]="<script>";
    $html[]="window.ArStatsReload=function(){ if(!document.getElementById('ar-stats-host')){return;} LoadAjaxSilent('ar-stats-host','$page?stats-host=yes$addon'); };";
    $html[]="window.ArStatsRefresh=function(btn){ if(btn){ btn.disabled=true; btn.setAttribute('aria-busy','true'); var i=btn.querySelector('svg,i'); if(i){i.classList.add('ak-spin');} } Loadjs('$page?refresh-run=yes$addon'); };";
    $html[]="window.ArStatsTick=function(){";
    $html[]="  if(window.ArStatsTimer){clearTimeout(window.ArStatsTimer);}";
    $html[]="  if(!document.getElementById('ar-stats-host')){return;}";
    $html[]="  window.ArStatsTimer=setTimeout(function(){";
    $html[]="    if(!document.getElementById('ar-stats-host')){return;}";
    // Une modale ouverte PAR-DESSUS celle-ci (detail d'un site ou d'un poste) ou une SweetAlert : on attend.
    $html[]="    var busy=$('.modal:visible').length>1 || $('.sweet-alert:visible').length>0;";
    $html[]="    if(!busy){ArStatsReload();} ArStatsTick();";
    $html[]="  },30000);";
    $html[]="};";
    $html[]="ArStatsReload(); ArStatsTick();";
    $html[]="</script>";
    echo @implode("\n",$html);
    return true;
}

function ar_model(int $id):array{
    $m=array("ok"=>false,"conns"=>0,"clients"=>0,"hosts"=>0,"size"=>0,"time"=>0.0,"ts"=>0,
        "running"=>null,"retention"=>"","by_host"=>array(),"by_client"=>array());

    if($id==0){
        $cur=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/active-requests/current"));
        $data=(is_object($cur) && !empty($cur->success) && isset($cur->data))?$cur->data:null;
        $h=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/active-requests/by-hostname?limit=10"));
        $hosts=(is_object($h) && !empty($h->success) && is_array($h->data ?? null))?$h->data:array();
        $c=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/active-requests/by-client?limit=10"));
        $clients=(is_object($c) && !empty($c->success) && is_array($c->data ?? null))?$c->data:array();
        $st=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/active-requests/stats"));
        if(is_object($st) && !empty($st->success) && isset($st->data)){
            $m["running"]=!empty($st->data->running);
            $m["retention"]=ar_retention((string)($st->data->data_retention ?? ""));
        }
    }else{
        $raw=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/realtimedb/current/$id"));
        $data=(is_object($raw) && isset($raw->total_connections))?$raw:null;
        $h=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/realtimedb/by-hostname/$id?limit=10"));
        $hosts=(is_object($h) && is_array($h->hosts ?? null))?$h->hosts:array();
        $c=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/realtimedb/by-client/$id?limit=10"));
        $clients=(is_object($c) && is_array($c->clients ?? null))?$c->clients:array();
    }

    if($data!==null){
        $m["ok"]=true;
        $m["conns"]=intval($data->total_connections ?? 0);
        $m["clients"]=intval($data->unique_clients ?? 0);
        $m["hosts"]=intval($data->unique_hostnames ?? 0);
        $m["size"]=floatval($data->total_size ?? 0);
        $m["time"]=floatval($data->total_time ?? 0);
        $m["ts"]=intval($data->timestamp ?? 0);
    }
    foreach($hosts as $r){
        $m["by_host"][]=array("name"=>(string)($r->hostname ?? ""),"conns"=>intval($r->connections ?? 0),
            "size"=>floatval($r->total_size ?? 0),"time"=>floatval($r->total_time ?? 0),
            "peers"=>is_array($r->clients ?? null)?$r->clients:array());
    }
    foreach($clients as $r){
        $m["by_client"][]=array("name"=>(string)($r->client ?? ""),"conns"=>intval($r->connections ?? 0),
            "size"=>floatval($r->total_size ?? 0),"time"=>floatval($r->total_time ?? 0),
            "peers"=>is_array($r->hostnames ?? null)?$r->hostnames:array());
    }
    return $m;
}

// "24h0m0s" (time.Duration Go) -> "24h"
function ar_retention(string $d):string{
    $d=preg_replace('/(?<=[hm])0m0s$|(?<=h)0m$|(?<=[hm])0s$/','',$d);
    return preg_replace('/[^0-9hms]/','',$d);
}

function ar_bytes(float $b):string{
    if($b<=0){return "0 B";}
    $u=array("B","KB","MB","GB","TB"); $i=0;
    while($b>=1024 && $i<4){$b=$b/1024;$i++;}
    if($i==0){return intval($b)." B";}
    return ($b>=100?round($b):round($b,1))." ".$u[$i];
}

function ar_dur(float $s):string{
    $s=intval(round($s));
    if($s<60){return "{$s}s";}
    if($s<3600){return intval($s/60)."m ".sprintf("%02d",$s%60)."s";}
    return intval($s/3600)."h ".sprintf("%02d",intval(($s%3600)/60))."m";
}

// Barre = accent de la marque ; texte = sa variante foncee (le vert clair seul tombe sous AA).
function ar_accent():array{
    $acc=array("#10b981","#047857");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$acc[0]=$a["accent"];}
        if(!empty($a["accent_dark"])){$acc[1]=$a["accent_dark"];}
    }
    return $acc;
}

function stats_host():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    list($id,$addon)=MetaID();
    $m=ar_model($id);
    $html=array();
    $html[]=ar_strip($m);
    $html[]=ar_kpis($m);
    $html[]="<div class='ak-grid-2' style='margin-top:16px'>";
    $html[]=ar_rank($m["by_host"],"hosts",$page,$addon,$m["ok"]);
    $html[]=ar_rank($m["by_client"],"clients",$page,$addon,$m["ok"]);
    $html[]="</div>";
    $html[]=ar_footer($m);
    $html[]="<script>".ar_js()."</script>";
    echo ar_style().$tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

function ar_strip(array $m):string{
    $h=array();
    $h[]="<div class='ak-card ar-strip'>";
    $h[]="<div class='ar-strip-l'>";
    if(!$m["ok"]){
        $h[]="<span class='ak-pill ak-off'><i class='fas fa-circle-question'></i> {ar_no_snapshot}</span>";
    }else{
        if($m["running"]===false){
            $h[]="<span class='ak-pill ak-warn'><i class='fas fa-circle-pause'></i> {ar_collector_stopped}</span>";
        }else{
            $h[]="<span class='ak-dot ak-ok' aria-hidden='true'></span>";
        }
        if($m["ts"]>0){
            $h[]="<span>{ar_snapshot_taken_at} <strong class='ar-clock'>".date("H:i:s",$m["ts"])."</strong></span>";
            $h[]="<span class='ak-muted' id='ar-age' data-ts='{$m["ts"]}' data-now='".time()."' data-fmt=\"{ar_ago}\"></span>";
        }
    }
    $h[]="</div>";
    $h[]="<button type='button' class='ak-btn ak-btn-primary' id='ar-refresh-btn' aria-busy='false' OnClick=\"ArStatsRefresh(this)\"><i class='fas fa-arrows-rotate'></i> {refresh}</button>";
    $h[]="</div>";
    return @implode("",$h);
}

function ar_kpis(array $m):string{
    $dash="&mdash;";
    $tiles=array(
        array("ak-ok","fas fa-arrow-right-arrow-left","{connections}",$m["ok"]?number_format($m["conns"],0,"."," "):$dash,""),
        array("ak-info","fas fa-users","{ar_members}",$m["ok"]?number_format($m["clients"],0,"."," "):$dash,""),
        array("ak-violet","fas fa-globe","{websites}",$m["ok"]?number_format($m["hosts"],0,"."," "):$dash,""),
        array("ak-warn","fas fa-download","{ar_size}",$m["ok"]?ar_bytes($m["size"]):$dash,
            $m["ok"]?"{ar_cumulated_time} ".ar_dur($m["time"]):""),
    );
    $h=array("<div class='ak-grid-4' style='margin-top:16px'>");
    foreach($tiles as $t){
        $h[]="<div class='ak-fig ar-fig {$t[0]}'>";
        $h[]="<span class='ak-ico ak-ico-sm ak-ico-sq'><i class='{$t[1]}'></i></span>";
        $h[]="<div class='ak-fig-txt ak-grow'><span class='ak-fig-l'>{$t[2]}</span><span class='ak-fig-v'>{$t[3]}</span>";
        if($t[4]!=""){$h[]="<span class='ak-fig-s ar-fig-s'>{$t[4]}</span>";}
        $h[]="</div></div>";
    }
    $h[]="</div>";
    return @implode("",$h);
}

// Classement : rang, nom (lien vers la popup de detail) et nombre de pairs, avec sous le nom
// la barre des connexions mesuree contre le premier de la liste ; puis connexions / taille / duree.
function ar_rank(array $rows,string $kind,string $page,string $addon,bool $known):string{
    $isHost=$kind=="hosts";
    $tone=$isHost?"ar-blue":"ar-teal";
    $h=array();
    $h[]="<div class='ak-card ak-card-pad ar-rank $tone'>";
    $h[]="<div class='ar-rank-h'><span class='ar-rank-t'><i class='fas ".($isHost?"fa-globe":"fa-users")."'></i> ".($isHost?"{ar_top_websites}":"{ar_top_members}")."</span>";
    if(count($rows)>0){$h[]="<span class='ak-pill ak-off'>{ar_sorted_by_connections}</span>";}
    $h[]="</div>";
    if(count($rows)==0){
        // Sans photo lisible on ne sait rien : ne pas affirmer qu'aucune requete n'est en cours.
        if(!$known){
            $h[]="<div class='ak-empty-c'><i class='fas fa-circle-question'></i><div>{ar_no_snapshot}</div></div>";
        }else{
            $h[]="<div class='ak-empty-c'><i class='fas fa-circle-check'></i><div>{ar_empty}</div><div class='ak-muted'>{ar_empty_hint}</div></div>";
        }
        $h[]="</div>";
        return @implode("",$h);
    }
    $max=1; $total=0.0;
    foreach($rows as $r){ if($r["conns"]>$max){$max=$r["conns"];} $total+=$r["size"]; }
    $h[]="<table class='ar-tbl'><thead><tr>";
    $h[]="<th class='ar-c-rank'>#</th><th>".($isHost?"{ar_col_hostname}":"{ar_col_client}")."</th>";
    $h[]="<th class='ak-num'>{connections}</th><th class='ak-num'>{ar_size}</th><th class='ak-num'>{ar_time}</th>";
    $h[]="</tr></thead><tbody>";
    $rank=0;
    foreach($rows as $r){
        $rank++;
        $name=$r["name"];
        $param=$isHost?"url-js":"ip-js";
        $js="Loadjs('$page?$param=".urlencode($name)."$addon');";
        $label=htmlspecialchars("$name, {$r["conns"]} ",ENT_QUOTES)."{connections}, ".ar_bytes($r["size"]).", ".ar_dur($r["time"]);
        $n=count($r["peers"]);
        $peerWord=$isHost?($n==1?"{ar_client}":"{ar_clients}"):($n==1?"{ar_site}":"{ar_sites}");
        $peerList=htmlspecialchars(implode(", ",array_slice($r["peers"],0,30)),ENT_QUOTES);
        $pct=round($r["conns"]*100/$max,1);
        $h[]="<tr>";
        $h[]="<td class='ar-c-rank'>$rank</td>";
        $h[]="<td class='ar-c-name'><div class='ar-name'>";
        $h[]="<a href=\"javascript:blur();\" class='ar-link".($isHost?"":" ak-mono")."' title=\"".htmlspecialchars($name,ENT_QUOTES)."\" aria-label=\"$label\" OnClick=\"$js\">".htmlspecialchars($name)."</a>";
        $h[]="</div><div class='ar-sub'><span class='ar-bar' aria-hidden='true'><span style='width:$pct%'></span></span>";
        $h[]="<span class='ak-count ar-peers' title=\"$peerList\">$n $peerWord</span></div></td>";
        $h[]="<td class='ak-num ar-strong'>".number_format($r["conns"],0,"."," ")."</td>";
        $h[]="<td class='ak-num".($r["size"]<=0?" ak-muted":"")."'>".ar_bytes($r["size"])."</td>";
        $h[]="<td class='ak-num ak-muted'>".ar_dur($r["time"])."</td>";
        $h[]="</tr>";
    }
    $h[]="</tbody></table>";

    // Part de la taille transferee : un segment par ligne, mesure contre le total du top 10.
    $h[]="<div class='ar-share'>";
    $h[]="<div class='ar-share-h'><span>{ar_share_size}</span><span class='ak-mono'>{ar_total}: ".ar_bytes($total)."</span></div>";
    $h[]="<div class='ar-share-bar' aria-hidden='true'>";
    if($total>0){
        $i=0; $cnt=count($rows);
        foreach($rows as $r){
            if($r["size"]<=0){$i++;continue;}
            $w=round($r["size"]*100/$total,2);
            $op=$cnt>1?round(1-($i*0.7/($cnt-1)),2):1;
            $h[]="<span style='width:$w%;opacity:$op' title=\"".htmlspecialchars($r["name"],ENT_QUOTES)." ".ar_bytes($r["size"])."\"></span>";
            $i++;
        }
    }
    $h[]="</div></div>";
    $h[]="</div>";
    return @implode("",$h);
}

function ar_footer(array $m):string{
    $txt="{ar_inflight_only}";
    if($m["retention"]!=""){$txt.=" &middot; {ar_history_kept}: ".htmlspecialchars($m["retention"]);}
    return "<div class='ak-note'>$txt</div>";
}

function ar_js():string{
    return "(function(){var e=document.getElementById('ar-age'); if(!e){return;}
 var ts=parseInt(e.getAttribute('data-ts'),10), skew=parseInt(e.getAttribute('data-now'),10)-Math.floor(Date.now()/1000), fmt=e.getAttribute('data-fmt')||'%s';
 function d(s){ if(s<60){return s+' s';} if(s<3600){return Math.floor(s/60)+' min '+(s%60)+' s';} return Math.floor(s/3600)+' h '+Math.floor((s%3600)/60)+' min'; }
 function upd(){ if(!document.body.contains(e)){clearInterval(window.ArAgeTimer);return;} var s=Math.max(0,Math.floor(Date.now()/1000)+skew-ts); e.textContent='('+fmt.replace('%s',d(s))+')'; }
 if(window.ArAgeTimer){clearInterval(window.ArAgeTimer);} upd(); window.ArAgeTimer=setInterval(upd,1000);})();";
}

function ar_style():string{
    if(isset($GLOBALS["AR_STYLE"])){return "";}
    $GLOBALS["AR_STYLE"]=true;
    list($acc,$accDark)=ar_accent();
    return "<style>
.ar .ar-strip{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:10px 16px;min-height:56px}
.ar .ar-strip-l{display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:14px}
.ar .ar-clock{font-variant-numeric:tabular-nums}
.ar .ar-fig{justify-content:flex-start}
.ar .ar-fig-s{color:var(--ak-muted)}
.ar .ar-rank{display:flex;flex-direction:column;min-width:0}
.ar .ar-blue{--ar-c:#2563eb;--ar-t:#1d4ed8}
.ar .ar-teal{--ar-c:$acc;--ar-t:$accDark}
.ar .ar-rank-h{display:flex;align-items:center;justify-content:space-between;gap:12px;padding-bottom:12px;border-bottom:1px solid var(--ak-border)}
.ar .ar-rank-t{font-size:15px;font-weight:700;color:var(--ak-text);display:inline-flex;align-items:center;gap:8px}
.ar .ar-rank-t>svg,.ar .ar-rank-t>i{color:var(--ar-c)}
.ar .ar-tbl{width:100%;border-collapse:collapse;table-layout:fixed;margin-top:4px}
.ar .ar-tbl th{font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--ak-label);padding:10px 6px;text-align:left;white-space:nowrap}
.ar .ar-tbl td{font-size:14px;padding:9px 6px;border-top:1px solid #f1f5f9;vertical-align:middle;font-variant-numeric:tabular-nums}
.ar .ar-tbl tbody tr:hover{background:#f9fafb}
.ar .ar-tbl .ak-num{text-align:right;white-space:nowrap}
.ar .ar-tbl th:nth-child(3){width:108px}.ar .ar-tbl th:nth-child(4){width:72px}.ar .ar-tbl th:nth-child(5){width:62px}
.ar .ar-c-rank{width:30px;text-align:right;color:var(--ak-muted)}
.ar .ar-name{display:flex;align-items:center;gap:8px;min-width:0}
.ar .ar-link{color:var(--ar-t);text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0;font-weight:600}
.ar .ar-link:hover{text-decoration:underline}
.ar .ar-link:focus-visible,.ar #ar-refresh-btn:focus-visible{outline:2px solid #2563eb;outline-offset:2px}
.ar .ar-peers{flex:0 0 auto;white-space:nowrap;cursor:help;font-size:13px}
.ar .ar-strong{font-weight:700;color:var(--ak-text)}
.ar .ar-sub{display:flex;align-items:center;gap:8px;margin-top:5px}
.ar .ar-bar{display:block;flex:1 1 auto;height:5px;border-radius:6px;background:#f3f4f6;overflow:hidden}
.ar .ar-bar>span{display:block;height:100%;border-radius:6px;background:var(--ar-c);transition:width .2s}
.ar .ar-share{margin-top:auto;padding-top:14px;border-top:1px solid var(--ak-border)}
.ar .ar-share-h{display:flex;justify-content:space-between;gap:12px;font-size:13px;color:var(--ak-muted);margin-bottom:8px}
.ar .ar-share-bar{display:flex;height:6px;border-radius:6px;background:#f3f4f6;overflow:hidden;gap:1px}
.ar .ar-share-bar>span{display:block;height:100%;background:var(--ar-c)}
@media (prefers-reduced-motion:reduce){.ar .ar-bar>span{transition:none}}
</style>";
}

// Refresh : lance une collecte puis recharge l'hote. Remplace l'ancien refresh-data, qui
// renvoyait tel quel un JavaScript arbitraire passe en base64 dans l'URL.
function refresh_run():bool{
    $tpl=new template_admin();
    list($id,$addon)=MetaID();
    header("content-type: application/x-javascript");
    if($id==0){
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/squid/active-requests/run", array()));
        $ok=is_object($json) && !empty($json->success);
        $err=is_object($json)?(string)($json->error ?? ""):"";
    }else{
        $raw=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/realtimedb/fetch/$id", array()));
        $ok=is_object($raw) && !empty($raw->Status);
        $err=is_object($raw)?(string)($raw->Error ?? ""):"";
    }
    // Ce qu'il faut recharger apres la collecte : liste fermee, jamais du JS venu de l'URL.
    $page=CurrentPageName();
    $then="if(window.ArStatsReload){ArStatsReload();}";
    if(($_GET["then"] ?? "")=="hosts"){
        $then="if(document.getElementById('squid-active-requests-by-host')){LoadAjaxSilent('squid-active-requests-by-host','$page?group-by-host-table=yes$addon');}";
    }
    if(($_GET["then"] ?? "")=="members"){
        $then="if(document.getElementById('squid-active-requests-by-usernames')){LoadAjaxSilent('squid-active-requests-by-usernames','$page?group-by-username-table=yes$addon');}";
    }
    echo "$then\n";
    if($ok){ return true; }
    return $tpl->js_error($err!=""?$err:"{ar_collect_failed}");
}

function ip_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t = time();
    $ipaddr = $_GET["ip-popup"];
    list($id,$addon)=MetaID();
    // Get client history from API
    if($id==0) {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/active-requests/client/" . urlencode($ipaddr) . "/history?hours=24"));
    }else{
        $raw = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/realtimedb/client/$id/". urlencode($ipaddr) ."/history?hours=24"));
        $json = (is_object($raw) && isset($raw->history))
            ? (object)["success"=>true,"data"=>$raw->history] : null;
    }

    if(!$json || !$json->success){
        // Fallback to old SQLite method
        $dbfile = "/home/artica/SQLITE_TEMP/ProxCons.db";
        $q = new lib_sqlite($dbfile);
        // Pentest 2026-11-04 : ip-popup (ipaddr) etait concatene dans la requete.
        $results=$q->QUERY_SQL_PARAMS("SELECT url,SUM(size) as size,AVG(ztime) as ztime,COUNT(*) as cnx FROM connections WHERE ipaddr=? GROUP BY url ORDER by size desc",array((string)$ipaddr));

        $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
        $html[]="<table class='awc-tbl'><thead><tr>";
        $html[]="<th>{websites}</th>";
        $html[]="<th class='center'>{connexions}</th>";
        $html[]="<th>{size}</th>";
        $html[]="<th>{time}</th>";
        $html[]="</tr></thead><tbody id='par-url-rows'>";
        $n=0;
        foreach ($results as $index=>$ligne){
            $html[]=par_row(md5(serialize($ligne)),"fas fa-globe",getLinkURL($ligne["url"]),"",
                $tpl->FormatNumber($ligne["cnx"]),FormatBytes(intval($ligne["size"])/1024),
                $tpl->FormatSeconds($ligne["ztime"]));
            $n++;
        }
        if($n==0){$html[]="<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>";}
        $html[]="</tbody></table></div>";
        $html[]="<div class='awc-foot'><span>{showing} $n / $n</span>";
        $html[]="<span class='awc-pg' id='par-url-pager'></span></div></div></div>";
        $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
        echo awc_table_style()
            .$tpl->_ENGINE_parse_body($html)
            .awc_pager_js(50,"par-url-rows","par-url-pager");
        return true;
    }

    // Show history from API
    $history = $json->data ?? [];

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{time}</th>";
    $html[]="<th class='center'>{connexions}</th>";
    $html[]="<th>{size}</th>";
    $html[]="<th>{time}</th>";
    $html[]="</tr></thead><tbody id='par-hh-rows'>";
    $n=0;
    foreach ($history as $item){
        $hostnames = is_array($item->hostnames) ? count($item->hostnames) : 0;
        $html[]=par_row(md5(serialize($item)),"fas fa-clock",
            date("Y-m-d H:i:s", $item->timestamp ?? 0),
            $hostnames." {websites}",
            $tpl->FormatNumber($item->connections ?? 0),
            FormatBytes(($item->total_size ?? 0)/1024),
            $tpl->FormatSeconds($item->total_time ?? 0));
        $n++;
    }
    if($n==0){$html[]="<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>";}
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $n / $n</span>";
    $html[]="<span class='awc-pg' id='par-hh-pager'></span></div></div></div>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body($html)
        .awc_pager_js(50,"par-hh-rows","par-hh-pager");
    return true;
}

function url_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t = time();
    list($id,$addon)=MetaID();
    $url = $_GET["url-popup"];

    // Get hostname history from API
    if($id==0) {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/active-requests/hostname/" . urlencode($url) . "/history?hours=24"));
    }else{
        $raw = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/realtimedb/hostname/$id/" . urlencode($url) . "/history?hours=24"));
        $json = (is_object($raw) && isset($raw->history))
            ? (object)["success"=>true,"data"=>$raw->history] : null;
    }

    if(!$json || !$json->success){
        return true;
    }

    // Show history from API
    $history = $json->data ?? [];

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{time}</th>";
    $html[]="<th class='center'>{connexions}</th>";
    $html[]="<th>{size}</th>";
    $html[]="<th>{time}</th>";
    $html[]="</tr></thead><tbody id='par-ch-rows'>";
    $n=0;
    foreach ($history as $item){
        $clients = is_array($item->clients) ? count($item->clients) : 0;
        $html[]=par_row(md5(serialize($item)),"fas fa-clock",
            date("Y-m-d H:i:s", $item->timestamp ?? 0),
            $clients." {members}",
            $tpl->FormatNumber($item->connections ?? 0),
            FormatBytes(($item->total_size ?? 0)/1024),
            $tpl->FormatSeconds($item->total_time ?? 0));
        $n++;
    }
    if($n==0){$html[]="<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>";}
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $n / $n</span>";
    $html[]="<span class='awc-pg' id='par-ch-pager'></span></div></div></div>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body($html)
        .awc_pager_js(50,"par-ch-rows","par-ch-pager");
    return true;
}

function group_by_hosts():bool{
    $page=CurrentPageName();
    list($id,$addon)=MetaID();
    echo "<div id='squid-active-requests-by-host'></div>";
    echo "<script>LoadAjaxSilent('squid-active-requests-by-host','$page?group-by-host-table=yes$addon');</script>";
    return true;
}

// ---------------------------------------------------------------------------
// Onglet Web sites (maquette Stitch stitch_proxy_active_requests_dashboard1).
// arh_model() lit la photo UNE fois ; recherche, tri et pagination se font dans
// le navigateur (250 lignes au plus). L'etat (recherche, tri, page) survit au Refresh.
// ---------------------------------------------------------------------------
function arh_model(int $id):array{
    $m=array("ok"=>false,"rows"=>array(),"ts"=>0,"running"=>null);
    if($id==0){
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/active-requests/by-hostname?limit=250"));
        if(is_object($json) && !empty($json->success)){
            $m["ok"]=true;
            $data=is_array($json->data ?? null)?$json->data:array();
        }
        $st=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/active-requests/stats"));
        if(is_object($st) && !empty($st->success) && isset($st->data)){$m["running"]=!empty($st->data->running);}
        $cur=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/active-requests/current"));
        if(is_object($cur) && !empty($cur->success) && isset($cur->data)){$m["ts"]=intval($cur->data->timestamp ?? 0);}
    }else{
        $raw=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/realtimedb/by-hostname/$id?limit=250"));
        if(is_object($raw) && isset($raw->hosts)){
            $m["ok"]=true;
            $data=is_array($raw->hosts)?$raw->hosts:array();
        }
    }
    if(!$m["ok"]){return $m;}
    foreach($data as $r){
        $name=(string)($r->hostname ?? "");
        $port="";
        if(preg_match('#^(.+):([0-9]{1,5})$#',$name,$re)){$name=$re[1];$port=$re[2];}
        $m["rows"][]=array("full"=>(string)($r->hostname ?? ""),"name"=>$name,"port"=>$port,
            "conns"=>intval($r->connections ?? 0),"size"=>floatval($r->total_size ?? 0),
            "time"=>floatval($r->total_time ?? 0),
            "clients"=>is_array($r->clients ?? null)?array_values($r->clients):array());
        $ts=intval($r->timestamp ?? 0);
        if($ts>$m["ts"]){$m["ts"]=$ts;}
    }
    return $m;
}

function group_by_hosts_table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    list($id,$addon)=MetaID();
    $m=arh_model($id);
    $html=array();
    $html[]="<div class='ak ar arh'>";
    $html[]=arh_toolbar($m);
    $html[]=arh_table($m,$page,$addon);
    $html[]="<div class='ak-note arh-note'><i class='far fa-circle-info'></i> {arh_note}</div>";
    $html[]="</div>";
    $html[]="<script>".ar_js()."</script>";
    echo ar_style().arh_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).arh_js();
    return true;
}

function arh_toolbar(array $m):string{
    $h=array();
    $h[]="<div class='ak-tools arh-tools'>";
    $h[]="<label class='ak-search arh-search'><i class='fas fa-magnifying-glass'></i><input type='search' id='arh-q' placeholder=\"{arh_search_placeholder}\" aria-label=\"{arh_search_placeholder}\" autocomplete='off'></label>";
    $h[]="<span class='arh-sortl' id='arh-sort-l'>{arh_sort_by}</span>";
    $h[]="<div class='ak-seg' role='radiogroup' aria-labelledby='arh-sort-l'>";
    foreach(array("c"=>"{connections}","s"=>"{ar_size}","t"=>"{ar_time}") as $k=>$lbl){
        $on=$k=="c";
        $h[]="<button type='button' role='radio' class='ak-seg-b".($on?" on":"")."' aria-checked='".($on?"true":"false")."' data-k='$k'>$lbl</button>";
    }
    $h[]="</div>";
    $h[]="<span class='ak-grow'></span>";
    $h[]="<span class='arh-snap'>";
    if(!$m["ok"]){
        $h[]="<span class='ak-pill ak-off'><i class='fas fa-circle-question'></i> {ar_no_snapshot}</span>";
    }else{
        if($m["running"]===false){
            $h[]="<span class='ak-pill ak-warn'><i class='fas fa-circle-pause'></i> {ar_collector_stopped}</span>";
        }else{
            $h[]="<span class='ak-dot ak-ok' aria-hidden='true'></span>";
        }
        if($m["ts"]>0){
            $h[]="<span>{ar_snapshot_taken_at} <strong class='ar-clock'>".date("H:i:s",$m["ts"])."</strong></span>";
            $h[]="<span class='ak-muted' id='ar-age' data-ts='{$m["ts"]}' data-now='".time()."' data-fmt=\"{ar_ago}\"></span>";
        }
    }
    $h[]="</span>";
    $h[]="<button type='button' class='ak-btn ak-btn-primary' id='arh-refresh' aria-busy='false' OnClick=\"ArhRefresh(this)\"><i class='fas fa-arrows-rotate'></i> {refresh}</button>";
    $h[]="</div>";
    return @implode("",$h);
}

function arh_table(array $m,string $page,string $addon):string{
    $h=array();
    $total=count($m["rows"]);
    $h[]="<div class='ak-card arh-card'>";
    $h[]="<table class='arh-tbl'><thead><tr>";
    $h[]="<th>{arh_col_website}</th><th>{arh_col_clients}</th><th class='ak-num arh-th-c'>{connections}</th><th class='ak-num'>{ar_size}</th><th class='ak-num'>{ar_cumulated_time}</th>";
    $h[]="</tr></thead><tbody id='arh-rows'>";
    $max=1;
    foreach($m["rows"] as $r){ if($r["conns"]>$max){$max=$r["conns"];} }
    foreach($m["rows"] as $r){ $h[]=arh_row($r,$max,$page,$addon); }
    $h[]="</tbody></table>";
    if(!$m["ok"]){
        $h[]="<div class='ak-empty-c'><i class='fas fa-circle-question'></i><div>{ar_no_snapshot}</div></div>";
    }elseif($total==0){
        $h[]="<div class='ak-empty-c'><i class='fas fa-circle-check'></i><div>{ar_empty}</div><div class='ak-muted'>{ar_empty_hint}</div></div>";
    }
    $h[]="<div class='ak-empty-c' id='arh-nomatch' hidden><i class='fas fa-magnifying-glass'></i><div>{arh_no_match}</div><button type='button' class='ak-link' OnClick=\"ArhClear()\">{arh_clear_search}</button></div>";
    if($total>0){
        $h[]="<div class='ak-card-f arh-foot'><span>{showing} <strong id='arh-shown'>$total</strong> / <strong>$total</strong> {arh_websites_unit}</span>";
        $h[]="<span class='arh-pgw'><span class='ak-muted'>50 {arh_rows_per_page}</span><nav class='ak-pg' id='arh-pager' aria-label=\"{arh_pages}\" data-prev=\"{arh_prev}\" data-next=\"{arh_next}\"></nav></span></div>";
    }
    $h[]="</div>";
    return @implode("",$h);
}

function arh_row(array $r,int $max,string $page,string $addon):string{
    $js="Loadjs('$page?url-js=".urlencode($r["full"])."$addon');";
    $n=count($r["clients"]);
    $pct=round($r["conns"]*100/$max,1);
    $search=strtolower($r["full"]." ".implode(" ",$r["clients"]));
    $label=htmlspecialchars("{$r["name"]}, {$r["conns"]} ",ENT_QUOTES)."{connections}, $n ".($n==1?"{ar_client}":"{ar_clients}").", ".ar_bytes($r["size"]);
    $h="<tr class='arh-r' tabindex='0' data-q=\"".htmlspecialchars($search,ENT_QUOTES)."\" data-n=\"".htmlspecialchars(strtolower($r["name"]),ENT_QUOTES)."\" data-c='{$r["conns"]}' data-s='".intval($r["size"])."' data-t='".round($r["time"],3)."' data-url=\"".htmlspecialchars("$page?url-js=".urlencode($r["full"]).$addon,ENT_QUOTES)."\" aria-label=\"$label\">";
    $h.="<td class='arh-c-name'><div class='arh-name'><a href=\"javascript:blur();\" class='ar-link' title=\"".htmlspecialchars($r["full"],ENT_QUOTES)."\" OnClick=\"$js\" tabindex='-1'>".htmlspecialchars($r["name"])."</a>";
    if($r["port"]!=""){$h.="<span class='ak-mono arh-port'>:".htmlspecialchars($r["port"])."</span>";}
    $h.="</div></td>";
    $h.="<td class='arh-c-cli'><div class='arh-chips'>";
    foreach(array_slice($r["clients"],0,2) as $ip){
        $h.="<a href=\"javascript:blur();\" class='ak-chip arh-ip' OnClick=\"Loadjs('$page?ip-js=".urlencode($ip)."$addon');\">".htmlspecialchars($ip)."</a>";
    }
    if($n>2){
        $rest=htmlspecialchars(implode(", ",array_slice($r["clients"],2,60)),ENT_QUOTES);
        $h.="<span class='ak-count arh-more' title=\"$rest\">+".($n-2)." {arh_more}</span>";
    }
    if($n==0){$h.="<span class='ak-muted'>&mdash;</span>";}
    $h.="</div></td>";
    $h.="<td class='ak-num'><span class='arh-cn'><strong>".number_format($r["conns"],0,"."," ")."</strong><span class='ar-bar arh-bar' aria-hidden='true'><span style='width:$pct%'></span></span></span></td>";
    $h.="<td class='ak-num".($r["size"]<=0?" ak-muted":"")."'>".ar_bytes($r["size"])."</td>";
    $h.="<td class='ak-num ak-muted'>".arh_dur($r["time"])."</td>";
    $h.="</tr>";
    return $h;
}

// Duree lisible de la colonne "Cumulated time" (59 s, 2 min 22 s, 3 h 05 min).
function arh_dur(float $s):string{
    $s=intval(round($s));
    if($s<60){return "$s s";}
    if($s<3600){return intval($s/60)." min ".($s%60)." s";}
    return intval($s/3600)." h ".sprintf("%02d",intval(($s%3600)/60))." min";
}

function arh_style():string{
    if(isset($GLOBALS["ARH_STYLE"])){return "";}
    $GLOBALS["ARH_STYLE"]=true;
    return "<style>
.arh{margin-top:12px}
.arh .arh-tools{gap:12px}
.arh .arh-search{width:280px}
.arh .arh-sortl{font-size:13px;color:var(--ak-label)}
.arh .arh-snap{display:inline-flex;align-items:center;gap:8px;font-size:13px;color:var(--ak-text2)}
.arh .arh-card{--ar-c:#2563eb;--ar-t:#1d4ed8;overflow:hidden}
.arh .arh-tbl{width:100%;border-collapse:collapse;table-layout:fixed}
.arh .arh-tbl th{font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--ak-label);padding:12px 16px;text-align:left;border-bottom:1px solid var(--ak-border);white-space:nowrap}
.arh .arh-tbl th:nth-child(2){width:30%}.arh .arh-tbl th:nth-child(3){width:170px}.arh .arh-tbl th:nth-child(4){width:100px}.arh .arh-tbl th:nth-child(5){width:150px}
.arh .arh-tbl td{padding:10px 16px;font-size:14px;border-top:1px solid #f1f5f9;vertical-align:middle;font-variant-numeric:tabular-nums}
.arh .arh-tbl tbody tr:first-child td{border-top:0}
.arh .arh-tbl .ak-num{text-align:right;white-space:nowrap}
.arh .arh-r{cursor:pointer}
.arh .arh-r:hover{background:#f9fafb}
.arh .arh-r:focus-visible{outline:2px solid #2563eb;outline-offset:-2px}
.arh .arh-name{display:flex;align-items:baseline;gap:6px;min-width:0}
.arh .arh-name .ar-link{font-size:14px}
.arh .arh-port{font-size:13px;color:var(--ak-muted);flex:0 0 auto}
.arh .arh-chips{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
.arh .arh-ip{font-size:13px;text-decoration:none;cursor:pointer}
.arh .arh-ip:hover{text-decoration:underline}
.arh .arh-ip:focus-visible{outline:2px solid #2563eb;outline-offset:2px}
.arh .arh-more{font-size:13px;cursor:help;white-space:nowrap}
.arh .arh-cn{display:inline-flex;align-items:center;justify-content:flex-end;gap:10px}
.arh .arh-bar{width:70px;flex:0 0 70px}
.arh .arh-foot{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 16px;border-top:1px solid var(--ak-border);font-size:13px;color:var(--ak-text2)}
.arh .arh-pgw{display:inline-flex;align-items:center;gap:12px}
.arh .arh-note{display:flex;align-items:flex-start;gap:8px;margin-top:12px}
.arh .arh-note>svg,.arh .arh-note>i{margin-top:3px}
@media (max-width:1200px){.arh .arh-search{width:100%;flex:1 1 100%}}
</style>";
}

// Recherche, tri et pagination cote client. L'etat vit dans window.ArhState pour survivre
// au rechargement du fragment apres un Refresh.
function arh_js(string $p="arh",string $then="hosts"):string{
    $js=<<<'JS'
<script>
(function(){
 var tb=document.getElementById('arh-rows'); if(!tb){return;}
 var S=window.ArhState||(window.ArhState={q:'',k:'c',p:1}), SIZE=50;
 var q=document.getElementById('arh-q'), pager=document.getElementById('arh-pager'), shown=document.getElementById('arh-shown'), nomatch=document.getElementById('arh-nomatch');
 var all=Array.prototype.slice.call(tb.querySelectorAll('tr.arh-r'));
 function num(r,k){return parseFloat(r.getAttribute('data-'+k))||0;}
 function apply(){
   var needle=S.q.toLowerCase().trim();
   var hit=all.filter(function(r){return needle===''||r.getAttribute('data-q').indexOf(needle)>=0;});
   hit.sort(function(a,b){var d=num(b,S.k)-num(a,S.k); if(d!==0){return d;} return a.getAttribute('data-n')<b.getAttribute('data-n')?-1:1;});
   all.forEach(function(r){r.style.display='none';});
   var pages=Math.max(1,Math.ceil(hit.length/SIZE)); if(S.p>pages){S.p=pages;} if(S.p<1){S.p=1;}
   hit.forEach(function(r,i){tb.appendChild(r); r.style.display=(i>=(S.p-1)*SIZE&&i<S.p*SIZE)?'':'none';});
   if(shown){shown.textContent=hit.length;}
   if(nomatch){nomatch.hidden=!(all.length>0&&hit.length===0);}
   if(!pager){return;}
   var h='', end=Math.min(pages,S.p+2), start=Math.max(1,end-4); end=Math.min(pages,start+4);
   h+='<button type="button" class="'+(S.p==1?'off':'')+'" data-p="'+(S.p-1)+'" aria-label="'+(pager.getAttribute('data-prev')||'')+'">&lsaquo;</button>';
   for(var p=start;p<=end;p++){h+='<button type="button" class="'+(p==S.p?'cur':'')+'" data-p="'+p+'"'+(p==S.p?' aria-current="page"':'')+'>'+p+'</button>';}
   h+='<button type="button" class="'+(S.p==pages?'off':'')+'" data-p="'+(S.p+1)+'" aria-label="'+(pager.getAttribute('data-next')||'')+'">&rsaquo;</button>';
   pager.innerHTML=h;
 }
 if(q){ q.value=S.q; q.addEventListener('input',function(){S.q=q.value;S.p=1;apply();}); }
 Array.prototype.forEach.call(document.querySelectorAll('.arh .ak-seg-b'),function(b){
   var on=b.getAttribute('data-k')===S.k; b.classList.toggle('on',on); b.setAttribute('aria-checked',on?'true':'false');
   b.addEventListener('click',function(){S.k=b.getAttribute('data-k');S.p=1;
     Array.prototype.forEach.call(document.querySelectorAll('.arh .ak-seg-b'),function(x){var o=x===b;x.classList.toggle('on',o);x.setAttribute('aria-checked',o?'true':'false');});
     apply();});
 });
 if(pager){pager.addEventListener('click',function(e){var t=e.target.closest('button'); if(!t||t.classList.contains('off')){return;} S.p=parseInt(t.getAttribute('data-p'),10); apply();});}
 tb.addEventListener('click',function(e){ if(e.target.closest('a')){return;} var r=e.target.closest('tr.arh-r'); if(r){Loadjs(r.getAttribute('data-url'));} });
 tb.addEventListener('keydown',function(e){ if(e.key!=='Enter'){return;} var r=e.target.closest('tr.arh-r'); if(r&&e.target===r){Loadjs(r.getAttribute('data-url'));} });
 window.ArhClear=function(){S.q='';if(q){q.value='';q.focus();}S.p=1;apply();};
 window.ArhRefresh=function(btn){ if(btn){btn.disabled=true;btn.setAttribute('aria-busy','true');var i=btn.querySelector('svg,i');if(i){i.classList.add('ak-spin');}} Loadjs('__PAGE__?refresh-run=yes&then=hosts__ADDON__'); };
 apply();
})();
</script>
JS;
    list($id,$addon)=MetaID();
    if($p!="arh"){$js=str_replace(array("arh","Arh","then=hosts"),array($p,ucfirst($p),"then=$then"),$js);}
    return str_replace(array("__PAGE__","__ADDON__"),array(CurrentPageName(),$addon),$js);
}

function getLinkURL($url){
    list($id,$addon)=MetaID();
    $urlText=$url;
    if(preg_match("#(.+?):([0-9]+)#",$urlText,$re)){
        $urlText=$re[1];
    }
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->td_href($urlText,null,"Loadjs('$page?url-js=".urlencode($url)."$addon')");
}

function group_by_usernames():bool{
    $page=CurrentPageName();
    list($id,$addon)=MetaID();
    echo "<div id='squid-active-requests-by-usernames'></div>";
    echo "<script>LoadAjaxSilent('squid-active-requests-by-usernames','$page?group-by-username-table=yes$addon');</script>";
    return true;
}
// ---------------------------------------------------------------------------
// Onglet members (maquette Stitch stitch_proxy_active_requests_dashboard3).
// Un membre est une IP, ou un nom d'utilisateur quand le proxy authentifie.
// Meme mecanique que l'onglet Web sites : arh_js() avec le prefixe "arm".
// ---------------------------------------------------------------------------
function arm_model(int $id):array{
    $m=array("ok"=>false,"rows"=>array(),"ts"=>0,"running"=>null);
    $data=array();
    if($id==0){
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/active-requests/by-client?limit=100"));
        if(is_object($json) && !empty($json->success)){
            $m["ok"]=true;
            $data=is_array($json->data ?? null)?$json->data:array();
        }
        $st=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/active-requests/stats"));
        if(is_object($st) && !empty($st->success) && isset($st->data)){$m["running"]=!empty($st->data->running);}
        $cur=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/active-requests/current"));
        if(is_object($cur) && !empty($cur->success) && isset($cur->data)){$m["ts"]=intval($cur->data->timestamp ?? 0);}
    }else{
        $raw=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/realtimedb/clients/$id?limit=100"));
        if(is_object($raw) && isset($raw->clients)){
            $m["ok"]=true;
            $data=is_array($raw->clients)?$raw->clients:array();
        }
    }
    $resolve=$id==0 && intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolveIP2HOST"))==1;
    $start=microtime(true);
    foreach($data as $r){
        $client=(string)($r->client ?? "");
        $isIP=filter_var($client,FILTER_VALIDATE_IP)!==false;
        $m["rows"][]=array("name"=>$client,"ip"=>$isIP,"host"=>$isIP&&$resolve?arm_resolve($client,$start):"",
            "conns"=>intval($r->connections ?? 0),"size"=>floatval($r->total_size ?? 0),
            "time"=>floatval($r->total_time ?? 0),
            "sites"=>is_array($r->hostnames ?? null)?array_values($r->hostnames):array());
        $ts=intval($r->timestamp ?? 0);
        if($ts>$m["ts"]){$m["ts"]=$ts;}
    }
    return $m;
}

// Nom DNS inverse d'une IP, garde en session. L'ancienne page resolvait chaque ligne
// sans limite (jusqu'a 100 gethostbyaddr synchrones) : au-dela de 1,5 s de resolution
// cumulee on n'en lance plus, les IP restantes seront resolues au prochain Refresh.
function arm_resolve(string $ip,float $start):string{
    if(isset($_SESSION["resolved"][$ip])){
        $h=(string)$_SESSION["resolved"][$ip];
        return $h==$ip?"":$h;
    }
    if(microtime(true)-$start>1.5){return "";}
    $h=@gethostbyaddr($ip);
    if(!is_string($h) || $h===""){$h=$ip;}
    $_SESSION["resolved"][$ip]=$h;
    return $h==$ip?"":$h;
}

function group_by_usernames_table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    list($id,$addon)=MetaID();
    $m=arm_model($id);
    $html=array();
    $html[]="<div class='ak ar arh arm'>";
    $html[]=arm_toolbar($m);
    $html[]=arm_table($m,$page,$addon);
    $html[]="<div class='ak-note arh-note'><i class='far fa-circle-info'></i> {arm_note}</div>";
    $html[]="</div>";
    $html[]="<script>".ar_js()."</script>";
    echo ar_style().arh_style().arm_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).arh_js("arm","members");
    return true;
}

function arm_toolbar(array $m):string{
    // Meme barre que l'onglet Web sites : seuls les identifiants et le texte d'aide changent.
    $h=arh_toolbar($m);
    return str_replace(array("id='arh-q'","{arh_search_placeholder}","OnClick=\"ArhRefresh(this)\"","id='arh-refresh'","id='arh-sort-l'","aria-labelledby='arh-sort-l'"),
        array("id='arm-q'","{arm_search_placeholder}","OnClick=\"ArmRefresh(this)\"","id='arm-refresh'","id='arm-sort-l'","aria-labelledby='arm-sort-l'"),$h);
}

function arm_table(array $m,string $page,string $addon):string{
    $h=array();
    $total=count($m["rows"]);
    $h[]="<div class='ak-card arh-card'>";
    $h[]="<table class='arh-tbl'><thead><tr>";
    $h[]="<th>{arm_col_member}</th><th>{websites}</th><th class='ak-num'>{connections}</th><th class='ak-num'>{ar_size}</th><th class='ak-num'>{ar_cumulated_time}</th>";
    $h[]="</tr></thead><tbody id='arm-rows'>";
    $max=1;
    foreach($m["rows"] as $r){ if($r["conns"]>$max){$max=$r["conns"];} }
    foreach($m["rows"] as $r){ $h[]=arm_row($r,$max,$page,$addon); }
    $h[]="</tbody></table>";
    if(!$m["ok"]){
        $h[]="<div class='ak-empty-c'><i class='fas fa-circle-question'></i><div>{ar_no_snapshot}</div></div>";
    }elseif($total==0){
        $h[]="<div class='ak-empty-c'><i class='fas fa-circle-check'></i><div>{ar_empty}</div><div class='ak-muted'>{ar_empty_hint}</div></div>";
    }
    $h[]="<div class='ak-empty-c' id='arm-nomatch' hidden><i class='fas fa-magnifying-glass'></i><div>{arm_no_match}</div><button type='button' class='ak-link' OnClick=\"ArmClear()\">{arh_clear_search}</button></div>";
    if($total>0){
        $cap=$total>=100?" <span class='ak-muted'>({arm_top100})</span>":"";
        $h[]="<div class='ak-card-f arh-foot'><span>{showing} <strong id='arm-shown'>$total</strong> / <strong>$total</strong> {arm_members_unit}$cap</span>";
        $h[]="<span class='arh-pgw'><span class='ak-muted'>50 {arh_rows_per_page}</span><nav class='ak-pg' id='arm-pager' aria-label=\"{arh_pages}\" data-prev=\"{arh_prev}\" data-next=\"{arh_next}\"></nav></span></div>";
    }
    $h[]="</div>";
    return @implode("",$h);
}

function arm_row(array $r,int $max,string $page,string $addon):string{
    // La popup d'historique accepte une IP comme un nom d'utilisateur : l'ancienne page
    // ouvrait la popup d'un utilisateur authentifie avec une IP vide.
    $url="$page?ip-js=".urlencode($r["name"]).$addon;
    $n=count($r["sites"]);
    $pct=round($r["conns"]*100/$max,1);
    $search=strtolower($r["name"]." ".$r["host"]." ".implode(" ",$r["sites"]));
    $label=htmlspecialchars("{$r["name"]}, {$r["conns"]} ",ENT_QUOTES)."{connections}, $n ".($n==1?"{ar_site}":"{ar_sites}").", ".ar_bytes($r["size"]);
    $h="<tr class='arm-r' tabindex='0' data-q=\"".htmlspecialchars($search,ENT_QUOTES)."\" data-n=\"".htmlspecialchars(strtolower($r["name"]),ENT_QUOTES)."\" data-c='{$r["conns"]}' data-s='".intval($r["size"])."' data-t='".round($r["time"],3)."' data-url=\"".htmlspecialchars($url,ENT_QUOTES)."\" aria-label=\"$label\">";
    $h.="<td><div class='arm-id'>";
    $h.=$r["ip"]?"<span class='arm-ico'><i class='fas fa-desktop'></i></span>":"<span class='arm-ico arm-ico-user'><i class='fas fa-user'></i></span>";
    $h.="<span class='arm-txt'><a href=\"javascript:blur();\" class='ar-link".($r["ip"]?" ak-mono":"")."' title=\"".htmlspecialchars($r["name"],ENT_QUOTES)."\" OnClick=\"Loadjs('".htmlspecialchars($url,ENT_QUOTES)."');\" tabindex='-1'>".htmlspecialchars($r["name"])."</a>";
    if(!$r["ip"]){
        $h.="<span class='arm-sub'>{arm_auth_user}</span>";
    }elseif($r["host"]!=""){
        $h.="<span class='arm-sub' title=\"".htmlspecialchars($r["host"],ENT_QUOTES)."\">".htmlspecialchars($r["host"])."</span>";
    }
    $h.="</span></div></td>";
    $h.="<td><div class='arh-chips'>";
    foreach(array_slice($r["sites"],0,2) as $site){
        $h.="<a href=\"javascript:blur();\" class='ak-chip arh-ip arm-site' title=\"".htmlspecialchars($site,ENT_QUOTES)."\" OnClick=\"Loadjs('$page?url-js=".urlencode($site)."$addon');\">".htmlspecialchars($site)."</a>";
    }
    if($n>2){
        $rest=htmlspecialchars(implode(", ",array_slice($r["sites"],2,60)),ENT_QUOTES);
        $h.="<span class='ak-count arh-more' title=\"$rest\">+".($n-2)." {arh_more}</span>";
    }
    if($n==0){$h.="<span class='ak-muted'>&mdash;</span>";}
    $h.="</div></td>";
    $h.="<td class='ak-num'><span class='arh-cn'><strong>".number_format($r["conns"],0,"."," ")."</strong><span class='ar-bar arh-bar' aria-hidden='true'><span style='width:$pct%'></span></span></span></td>";
    $h.="<td class='ak-num".($r["size"]<=0?" ak-muted":"")."'>".ar_bytes($r["size"])."</td>";
    $h.="<td class='ak-num ak-muted'>".arh_dur($r["time"])."</td>";
    $h.="</tr>";
    return $h;
}

function arm_style():string{
    if(isset($GLOBALS["ARM_STYLE"])){return "";}
    $GLOBALS["ARM_STYLE"]=true;
    list($acc,$accDark)=ar_accent();
    return "<style>
.arm .arh-card{--ar-c:$acc;--ar-t:#1d4ed8}
.arm .arm-r{cursor:pointer}
.arm .arm-r:hover{background:#f9fafb}
.arm .arm-r:focus-visible{outline:2px solid #2563eb;outline-offset:-2px}
.arm .arm-id{display:flex;align-items:center;gap:12px;min-width:0}
.arm .arm-ico{flex:0 0 28px;width:28px;height:28px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;background:#f1f5f9;color:#475569;font-size:13px}
.arm .arm-ico-user{background:rgba(16,185,129,.12);color:$accDark}
.arm .arm-txt{display:flex;flex-direction:column;min-width:0}
.arm .arm-sub{font-size:13px;color:var(--ak-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.arm .arm-site{display:inline-block;vertical-align:middle;font-family:inherit;max-width:190px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
</style>";
}

function par_row($md,$icon,$name,$meta,$count,$size,$ztime):string{
    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='$icon'></i></span>";
    $h.="<span><span class='awc-name'>$name</span>";
    if(trim((string)$meta)!==""){$h.="<div class='awc-meta'>$meta</div>";}
    $h.="</span></div></td>";
    $h.="<td class='center awc-mono'>$count</td>";
    $h.="<td><span class='awc-seen'>$size</span></td>";
    $h.="<td class='awc-mono'>$ztime</td>";
    $h.="</tr>";
    return $h;
}

