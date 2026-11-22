<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
// awc_table_style(): feuille de style partagee des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["js"])){js();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["graph"])){graph();exit;}
if(isset($_GET["chart-data-js"])){chart_data_js();exit;}
if(isset($_GET["realtime-data"])){realtime_data();exit;}

function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["js"]);
    $sock=new socksngix($ID);
    $servicename=$sock->GetServiceName();
    return $tpl->js_dialog2("#$ID - $servicename {latency}", "$page?tabs=$ID");
}

function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["tabs"]);
    $array["{realtime}"]="$page?graph=yes&time=realtime&serviceid=$ID";
    $array["{today}"]="$page?graph=yes&time=today&serviceid=$ID";
    $array["{last_48_h}"]="$page?graph=yes&time=48h&serviceid=$ID";
    $array["{this_week}"]="$page?graph=yes&time=week&serviceid=$ID";
    echo $tpl->tabs_default($array);
    return true;
}

function graph():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["serviceid"] ?? 0);
    $time=trim($_GET["time"] ?? "realtime");
    if($serviceid<1){return false;}

    if($time=="realtime"){
        $html[]="<div style='margin:10px'>";
        $html[]="<div id='realtime-area-$serviceid'></div>";
        $html[]="</div>";
        $html[]="<script>";
        $html[]="LoadAjax('realtime-area-$serviceid','$page?realtime-data=yes&serviceid=$serviceid');";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    // today, 48h, week: show widgets + line chart + error chart
    $endpoint=build_metrics_endpoint($time,$serviceid);
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX($endpoint);
    $json=json_decode($data);

    if(!$json || !isset($json->Status) || !$json->Status || !isset($json->Data)){
        $err=isset($json->Error) ? $json->Error : "{protocol_error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_warning($err));
        return false;
    }

    $points=$json->Data;
    if(!is_array($points) || count($points)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data_available}"));
        return false;
    }

    // compute summary from points
    $totalCount=0;$totalErrors=0;$sumAvg=0;$globalMax=0;$globalMin=PHP_FLOAT_MAX;
    foreach($points as $p){
        $totalCount+=intval($p->count ?? 0);
        $totalErrors+=intval($p->errors ?? 0);
        $sumAvg+=floatval($p->avg_ms ?? 0);
        $mx=floatval($p->max_ms ?? 0);
        $mn=floatval($p->min_ms ?? 0);
        if($mx>$globalMax) $globalMax=$mx;
        if($mn<$globalMin && $mn>0) $globalMin=$mn;
    }
    $avgMs=count($points)>0 ? round($sumAvg/count($points),1) : 0;
    $globalMax=round($globalMax,1);
    if($globalMin==PHP_FLOAT_MAX) $globalMin=0;
    $globalMin=round($globalMin,1);

    $w_avg=$tpl->widget_style1($avgMs>500?"yellow-bg":"green-bg","fa-solid fa-gauge-high","{latency}","$avgMs ms");
    $w_max=$tpl->widget_style1($globalMax>1000?"red-bg":"navy-bg","fa-solid fa-arrow-up","{maximum}","$globalMax ms");
    $w_min=$tpl->widget_style1("lazur-bg","fa-solid fa-arrow-down","{minimum}","$globalMin ms");
    // ------------------------------------------------------------------
    // RECONSTRUCTION. Les fonctions build_metrics_endpoint(), chart_data_js()
    // et realtime_data(), ainsi que la fin de graph(), ont ete detruites par une
    // erreur d'edition de ma part et n'existaient dans aucune sauvegarde ni dans
    // git (fichier non suivi). Elles sont reecrites d'apres le contrat reel du
    // daemon reverse-proxy, module BackendsScanner:
    //   GET /backends-scanner/run/{id}            -> []LatencyEntry
    //        {timestamp, latency_ms, backend, status, error}
    //   GET /backends-scanner/metrics/today/{id}  -> []LatencyMetricPoint
    //   GET /backends-scanner/metrics/48h/{id}       {timestamp, avg_ms, max_ms,
    //   GET /backends-scanner/metrics/week/{id}       min_ms, count, errors}
    // Enveloppe commune: {Status:true, Data:[...]} ou {Status:false, Error:"..."}.
    // Ce n'est PAS une restauration: la mise en page d'origine des graphiques
    // n'est pas connue.
    // ------------------------------------------------------------------
    $html[]="<div style='margin:10px'>";
    $html[]="<div style='display:flex;flex-wrap:wrap;gap:5px;align-items:stretch'>";
    $html[]="<div style='flex:1 1 240px;min-width:0'>$w_avg</div>";
    $html[]="<div style='flex:1 1 240px;min-width:0'>$w_max</div>";
    $html[]="<div style='flex:1 1 240px;min-width:0'>$w_min</div>";
    $html[]="</div>";

    $suffix=$serviceid."-".preg_replace('/[^a-z0-9]/','',strtolower($time));
    $html[]=latencies_chart_box("{latency} (ms)","lat-avg-$suffix");
    $html[]=latencies_chart_box("{errors}","lat-err-$suffix");
    $html[]="</div>";

    // Chart.js est fourni globalement par la coquille de page; les donnees sont
    // chargees a part pour ne pas faire passer le script par _ENGINE_parse_body.
    $script="<script>Loadjs('$page?chart-data-js=yes&time=".urlencode($time)
        ."&serviceid=$serviceid');</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n",$html)).$script;
    return true;
}

/** Le point d'entree REST correspondant a une periode. */
function build_metrics_endpoint($time,$serviceid):string{
    $serviceid=intval($serviceid);
    switch(trim((string)$time)){
        case "week": return "/backends-scanner/metrics/week/$serviceid";
        case "48h":  return "/backends-scanner/metrics/48h/$serviceid";
        default:     return "/backends-scanner/metrics/today/$serviceid";
    }
}

/** Un cadre de graphique vide, rempli ensuite par chart_data_js(). */
function latencies_chart_box($title,$canvasId):string{
    return "<div class='ibox' style='margin-top:10px'>"
        ."<div class='ibox-title'><h5>$title</h5></div>"
        ."<div class='ibox-content'><div style='height:240px'>"
        ."<canvas id='".htmlspecialchars($canvasId)."'></canvas>"
        ."</div></div></div>";
}

/**
 * Les donnees des deux graphiques d'une periode.
 * Echelle de categories et non de temps: la coquille de page ne charge AUCUN
 * adaptateur de dates, et une echelle type:'time' echouerait.
 */
function chart_data_js(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $serviceid=intval($_GET["serviceid"] ?? 0);
    $time=trim((string)($_GET["time"] ?? "today"));
    if($serviceid<1){return;}

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX(build_metrics_endpoint($time,$serviceid)));
    if(!is_object($json) || !isset($json->Status) || !$json->Status || !isset($json->Data)){return;}
    $points=$json->Data;
    if(!is_array($points) || count($points)==0){return;}

    $labels=array(); $avg=array(); $max=array(); $errors=array();
    foreach($points as $p){
        if(!is_object($p)){continue;}
        $labels[]=latencies_window_label((string)($p->timestamp ?? ""));
        // sprintf et non round(): serialize_precision=-1 ferait sortir un
        // flottant a cinquante chiffres de json_encode().
        $avg[]=sprintf('%.1f',floatval($p->avg_ms ?? 0));
        $max[]=sprintf('%.1f',floatval($p->max_ms ?? 0));
        $errors[]=intval($p->errors ?? 0);
    }
    if(count($labels)==0){return;}

    $suffix=$serviceid."-".preg_replace('/[^a-z0-9]/','',strtolower($time));
    $lJson=json_encode($labels);
    $avgJson="[".@implode(",",$avg)."]";
    $maxJson="[".@implode(",",$max)."]";
    $errJson="[".@implode(",",array_map("intval",$errors))."]";
    $avgLabel=addslashes($tpl->javascript_parse_text("{average}"));
    $maxLabel=addslashes($tpl->javascript_parse_text("{maximum}"));
    $errLabel=addslashes($tpl->javascript_parse_text("{errors}"));

    echo "(function(){
  var a=document.getElementById('lat-avg-$suffix');
  if(a){ new Chart(a,{type:'line',data:{labels:$lJson,datasets:[
      {label:'$avgLabel',data:$avgJson,borderColor:'#006a62',backgroundColor:'rgba(0,106,98,0.15)',fill:true,tension:0.3,pointRadius:0},
      {label:'$maxLabel',data:$maxJson,borderColor:'#865300',backgroundColor:'transparent',fill:false,tension:0.3,pointRadius:0}]},
    options:{responsive:true,maintainAspectRatio:false,
      plugins:{legend:{display:true},datalabels:{display:false}},
      scales:{y:{beginAtZero:true}}}}); }
  var e=document.getElementById('lat-err-$suffix');
  if(e){ new Chart(e,{type:'bar',data:{labels:$lJson,datasets:[
      {label:'$errLabel',data:$errJson,backgroundColor:'#ba1a1a'}]},
    options:{responsive:true,maintainAspectRatio:false,
      plugins:{legend:{display:false},datalabels:{display:false}},
      scales:{y:{beginAtZero:true,ticks:{precision:0}}}}}); }
})();";
}

/**
 * Abrege l'horodatage d'une fenetre pour en faire une etiquette de graphique.
 * Le daemon serialise en UTC avec un suffixe Z: DateTime le comprend, un
 * decoupage manuel non.
 */
function latencies_window_label($ts):string{
    $ts=trim((string)$ts);
    if($ts===""){return "-";}
    try{
        $d=new DateTime($ts);
        return $d->format("d/m H:i");
    }catch(Exception $e){
        return htmlspecialchars(substr($ts,0,16));
    }
}

/**
 * Le releve temps reel: une sonde par backend, a l'instant present.
 */
function realtime_data():bool{
    $tpl=new template_admin();
    $serviceid=intval($_GET["serviceid"] ?? 0);
    if($serviceid<1){return false;}

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/backends-scanner/run/$serviceid"));
    if(!is_object($json) || !isset($json->Status) || !$json->Status){
        $err=(is_object($json) && isset($json->Error))?htmlspecialchars($json->Error):"{protocol_error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_warning($err));
        return false;
    }
    $entries=(isset($json->Data) && is_array($json->Data))?$json->Data:array();

    $currentBackends=0; $currentErrors=0; $currentMaxMs=0; $newest="";
    foreach($entries as $e){
        if(!is_object($e)){continue;}
        $currentBackends++;
        $ms=floatval($e->latency_ms ?? 0);
        if($ms>$currentMaxMs){ $currentMaxMs=$ms; }
        if(trim((string)($e->error ?? ""))!=="" || intval($e->status ?? 0)>=500 || intval($e->status ?? 0)==0){
            $currentErrors++;
        }
        $ts=(string)($e->timestamp ?? "");
        if($ts>$newest){ $newest=$ts; }
    }
    $newestFmt=$newest!==""?latencies_window_label($newest):"-";
    $currentMaxMs=round($currentMaxMs,1);
    $w_avg=$tpl->widget_style1($currentMaxMs>500?"yellow-bg":"green-bg","fa-solid fa-gauge-high","{maximum}","$currentMaxMs ms");
    $w_backends=$tpl->widget_style1($currentErrors>0?"yellow-bg":"green-bg","fa-solid fa-server","{backends}","$currentBackends");

    $html=array();
    $html[]="<div style='display:flex;flex-wrap:wrap;gap:5px;align-items:stretch'>";
    $html[]="<div style='flex:1 1 260px;min-width:0'>$w_avg</div>";
    $html[]="<div style='flex:1 1 260px;min-width:0'>$w_backends</div>";
    $html[]="</div>";

    if(count($entries)>0){
        $html[]="<div class='ibox' style='margin-top:10px'><div class='ibox-title'><h5>{backends} {latency} ({realtime}) ($newestFmt)</h5></div>";
        $html[]="<div class='ibox-content'>";
        $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'>";
        $html[]="<table class='awc-tbl'><thead><tr>";
        $html[]="<th>{backend}</th>";
        $html[]="<th class='center'>{latency} (ms)</th>";
        $html[]="<th class='center'>HTTP {status}</th>";
        $html[]="<th class='center'>{error}</th>";
        $html[]="<th style='width:30%'>&nbsp;</th>";
        $html[]="</tr></thead><tbody>";

        $maxLatency=1;
        foreach($entries as $e){
            $ms=floatval($e->latency_ms ?? 0);
            if($ms>$maxLatency){ $maxLatency=$ms; }
        }
        foreach($entries as $e){
            if(!is_object($e)){continue;}
            $html[]=latencies_backend_row($e,$maxLatency);
        }
        $html[]="</tbody></table></div></div></div>";
        $html[]="</div></div>";
    }else{
        $html[]="<div style='margin-top:10px'>".$tpl->div_info("{no_data_available}")."</div>";
    }

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>NoSpinner();</script>";
    return true;
}

/**
 * Une mesure de latence par backend.
 * Les quatre branches de coloration d'origine produisaient TROIS fois la meme
 * pastille verte pour trois conditions distinctes; seules la couleur de la barre
 * et le seuil changeaient reellement. Le statut 0 -- pas de reponse -- etait par
 * ailleurs traite APRES coup, en ecrasant la pastille deja calculee.
 */
function latencies_backend_row($e,$maxLatency):string{
    $backend=htmlspecialchars((string)($e->backend ?? ""));
    $ms=round(floatval($e->latency_ms ?? 0),1);
    $status=intval($e->status ?? 0);
    $error=(string)($e->error ?? "");

    if($error!=="" || $status>=500 || $status==0){ $barColor="#ba1a1a"; $badgeColor="#ba1a1a"; }
    elseif($status>=400){ $barColor="#865300"; $badgeColor="#865300"; }
    elseif($ms>500){ $barColor="#865300"; $badgeColor="#26a69a"; }
    else{ $barColor="#26a69a"; $badgeColor="#26a69a"; }

    $badge="<span class='awc-badge' style='background:$badgeColor'>".($status==0?"-":$status)."</span>";
    $pct=$maxLatency>0 ? round(($ms/$maxLatency)*100) : 0;
    $bar="<div style='background:#f4f8f9;border-radius:3px;height:18px;width:100%'>"
        ."<div style='background:$barColor;height:18px;border-radius:3px;width:{$pct}%;min-width:2px'></div></div>";
    $errorCell=$error!=="" ? "<span style='color:#ba1a1a'>".htmlspecialchars($error)."</span>"
                           : "<span style='color:#26a69a'>OK</span>";

    $h ="<tr>";
    $h.="<td class='awc-nowrap'><span style='font-family:monospace'>$backend</span></td>";
    $h.="<td class='center awc-nowrap'><strong>$ms</strong></td>";
    $h.="<td class='center awc-nowrap'>$badge</td>";
    $h.="<td class='center awc-nowrap'>$errorCell</td>";
    $h.="<td>$bar</td>";
    $h.="</tr>";
    return $h;
}
