<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();


if(isset($_POST["ConnectMonitorPrometheusPort"])){Prometheus_save();exit;}
if(isset($_POST["ConnectMonitorPrometheus"])){Prometheus_save();exit;}
if(isset($_GET["worldmap"])){worldmap();exit;}
if(isset($_GET["map-data"])){worldmap_data();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["top-status"])){top_status();exit;}
if(isset($_GET["parameters-js"])){parameters_js();exit;}
if(isset($_GET["parameters-popup"])){parameters_popup();exit;}
if(isset($_GET["Prometheus-js"])){Prometheus_js();exit;}
if(isset($_GET["Prometheus-popup"])){Prometheus_popup();exit;}


if(isset($_POST["UseAbusesIPReputationKey"])){parameters_save();exit;}
if(isset($_GET["charts-tab"])){charts_tab();exit;}
page();
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $title="{APP_NGINX} {statistics}";
    $html=$tpl->page_header($title,ico_dashboard,"{statistics_nginx_explain}","$page?tabs=yes",
        "nginx-statistics",
        "progress-nginx-statistics-restart",false,
        "table-nginx-statistics"
    );

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: {APP_NGINX} {statistics}",$html);
        echo $tpl->build_firewall();
        return true;
    }


    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function parameters_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{parameters}","$page?parameters-popup=yes");
}
function Prometheus_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("Prometheus","$page?Prometheus-popup=yes");
}
function Prometheus_popup():bool{
    $page=CurrentPageName();
    $ConnectMonitorPrometheus=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ConnectMonitorPrometheus"));
    $ConnectMonitorPrometheusPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ConnectMonitorPrometheusPort"));
    if($ConnectMonitorPrometheusPort==0){
        $ConnectMonitorPrometheusPort=9925;
    }
    $tpl=new template_admin();
    $html[]=$tpl->BigCircleCheckbox("ConnectMonitorPrometheus",
        "{enable_prometheus_exporter}","{enable_prometheus_exporter_reverse}",$ConnectMonitorPrometheus);
    $html[]=$tpl->BigIntegerField("ConnectMonitorPrometheusPort","{listen_port}","",$ConnectMonitorPrometheusPort);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function Prometheus_save():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        admin_tracks("Refused to save the Prometheus exporter settings: insufficient privileges");
        echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
        return false;
    }
    $tpl->CLEAN_POST();
    // Deux champs Big* (un POST par champ) : seules ces cles s'enregistrent, et le
    // port est corrige AVANT l'ecriture (il etait corrige apres, 0 restait enregistre).
    if(isset($_POST["ConnectMonitorPrometheus"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ConnectMonitorPrometheus",intval($_POST["ConnectMonitorPrometheus"])==1?1:0);
    }
    if(isset($_POST["ConnectMonitorPrometheusPort"])){
        $port=intval($_POST["ConnectMonitorPrometheusPort"]);
        if($port<1 || $port>65535){$port=9925;}
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ConnectMonitorPrometheusPort",$port);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/prometheus/restart");
    return admin_tracks_post("Save Prometheus service option for Artica Stats");
}

function parameters_popup():bool{
    $tpl=new template_admin();
    $UseAbusesIPReputationKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseAbusesIPReputationKey"));

    $form[]= $tpl->field_text("UseAbusesIPReputationKey","AbuseIP {API_KEY}",$UseAbusesIPReputationKey);
    $html[]= $tpl->form_outside("",$form,null,"{apply}", "dialogInstance2.close();", "AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function parameters_save():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        admin_tracks("Refused to save the reverse-proxy statistics settings: insufficient privileges");
        echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
        return false;
    }
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reputation/abuseipdb");
    return admin_tracks_post("Save Reverse-Proxy statistics global settings");
}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $array["{status}"]="$page?table=yes";
    echo $tpl->tabs_default($array);
}

function get_site_names():array{
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/sites/ids");
    $json=json_decode($data);
    if(!$json || !isset($json->Status) || !$json->Status || !property_exists($json,"sites")){return [];}
    return (array)$json->sites;
}








// ---------------------------------------------------------------------------
// Onglet Status (maquette stitch_artica_reverse_proxy_statistics_dashboard).
// Donnees : /10mins/stats (reverse-proxy, direct) et rvproxy_monitor (PostgreSQL,
// une ligne par site et par fenetre de 10 min, gardee 3 h par ConnecsMonitor).
// Ecarte de la maquette : « 12 of 12 routed » (nombre de sites publies non lu ici),
// infobulle « live » sur la derniere fenetre, tri par colonne.
// ---------------------------------------------------------------------------
function nxst_codes():array{
    return array(
        "rej"=>array(404=>"nxst_c404",403=>"nxst_c403",401=>"nxst_c401",429=>"nxst_c429",444=>"nxst_c444",499=>"nxst_c499",400=>"nxst_c400",405=>"nxst_c405"),
        "srv"=>array(502=>"nxst_c502",503=>"nxst_c503",504=>"nxst_c504",500=>"nxst_c500",408=>"nxst_c408"));
}
function nxst_t(string $token,array $args=array()):string{
    $tpl=new template_admin();
    $s=trim(str_replace(array("[br]","<br>"),array(" "," "),$tpl->_ENGINE_parse_body("{".$token."}")));
    foreach($args as $a){$p=strpos($s,"%s");if($p===false){break;}$s=substr_replace($s,(string)$a,$p,2);}
    return $s;
}
function nxst_h($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES));
}
function nxst_n($v):string{return number_format(floatval($v),0,".",",");}
// Une requete SELECT json_agg(...) as rows : tableau de lignes, ou null si PostgreSQL ne repond pas.
function nxst_pg_rows(string $sql){
    $q=new postgres_sql();
    $r=$q->mysqli_fetch_array($sql);
    if(!is_array($r)){return null;}
    if(!isset($r["rows"]) || $r["rows"]===null){return array();}
    $rows=json_decode((string)$r["rows"],true);
    return is_array($rows)?$rows:array();
}
// Derniere fenetre : totaux, codes et une ligne par site.
function nxst_window():array{
    $w=array("ok"=>false,"zdate"=>"","total"=>0,"legit"=>0,"illegit"=>0,"srv"=>0,"codes"=>array(),"sites"=>array());
    $cols=array();
    foreach(nxst_codes() as $grp){foreach($grp as $c=>$tok){$cols[]="SUM(count_$c) as c$c";}}
    $rows=nxst_pg_rows("SELECT json_agg(sub ORDER BY total_requests DESC) as rows FROM (
        SELECT service_id, MAX(zdate) as zdate, SUM(total_requests) as total_requests, SUM(unique_clients) as unique_clients,
        SUM(count_legit) as count_legit, SUM(count_illegit) as count_illegit, SUM(count_server_err) as count_server_err, ".implode(",",$cols)."
        FROM rvproxy_monitor WHERE zdate=(SELECT MAX(zdate) FROM rvproxy_monitor) GROUP BY service_id) sub");
    if($rows===null){return $w;}
    $w["ok"]=true;
    $names=get_site_names();
    foreach($rows as $r){
        $sid=intval($r["service_id"] ?? 0);
        $t=intval($r["total_requests"] ?? 0);
        $w["zdate"]=(string)($r["zdate"] ?? "");
        $w["total"]+=$t;
        $w["legit"]+=intval($r["count_legit"] ?? 0);
        $w["illegit"]+=intval($r["count_illegit"] ?? 0);
        $w["srv"]+=intval($r["count_server_err"] ?? 0);
        foreach(nxst_codes() as $grp){foreach($grp as $c=>$tok){
            if(!isset($w["codes"][$c])){$w["codes"][$c]=0;}
            $w["codes"][$c]+=intval($r["c$c"] ?? 0);
        }}
        $w["sites"][]=array("name"=>isset($names[$sid])?(string)$names[$sid]:"#$sid","total"=>$t,
            "legit"=>intval($r["count_legit"] ?? 0),"illegit"=>intval($r["count_illegit"] ?? 0),
            "srv"=>intval($r["count_server_err"] ?? 0),"clients"=>intval($r["unique_clients"] ?? 0));
    }
    return $w;
}
// Les 3 heures gardees, une valeur par fenetre de 10 min.
function nxst_series():array{
    $s=array("labels"=>array(),"legit"=>array(),"illegit"=>array(),"srv"=>array(),"clients"=>array(),"total"=>array());
    $rows=nxst_pg_rows("SELECT json_agg(sub ORDER BY zdate ASC) as rows FROM (
        SELECT zdate, SUM(total_requests) as total_requests, SUM(unique_clients) as unique_clients,
        SUM(count_legit) as count_legit, SUM(count_illegit) as count_illegit, SUM(count_server_err) as count_server_err
        FROM rvproxy_monitor GROUP BY zdate) sub");
    if(!is_array($rows)){return $s;}
    foreach($rows as $r){
        $ts=strtotime((string)($r["zdate"] ?? ""));
        $s["labels"][]=$ts?date("H:i",$ts):"";
        $s["legit"][]=intval($r["count_legit"] ?? 0);
        $s["illegit"][]=intval($r["count_illegit"] ?? 0);
        $s["srv"][]=intval($r["count_server_err"] ?? 0);
        $s["clients"][]=intval($r["unique_clients"] ?? 0);
        $s["total"][]=intval($r["total_requests"] ?? 0);
    }
    return $s;
}
function nxst_live():array{
    $j=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/10mins/stats"),true);
    if(!is_array($j) || empty($j["Status"]) || !isset($j["Stats"]) || !is_array($j["Stats"])){return array("ok"=>false);}
    $st=$j["Stats"];
    return array("ok"=>true,"req"=>intval($st["totalQuery"] ?? 0),"sites"=>intval($st["numberOfSites"] ?? 0),"clients"=>intval($st["numberOfClient"] ?? 0));
}
function nxst_health(array $w):array{
    if($w["total"]<=0){return array(-1,"","");}
    $p=intval(floor(100*$w["legit"]/$w["total"]));
    if($p>=80){return array($p,"ok","{nxst_good}");}
    if($p>=50){return array($p,"warn","{nxst_watch}");}
    return array($p,"err","{nxst_poor}");
}
function nxst_spark(array $vals):string{
    $n=count($vals);
    if($n<2){return "";}
    $mx=max($vals);if($mx<=0){return "";}
    $w=90;$h=28;$pts=array();
    foreach($vals as $i=>$v){$pts[]=round($i*$w/($n-1),1).",".round($h-2-($v/$mx)*($h-4),1);}
    return "<svg class='nxst-spark' width='$w' height='$h' viewBox='0 0 $w $h' aria-hidden='true'><polyline points='".implode(" ",$pts)."' fill='none' stroke='#005447' stroke-width='2' stroke-linejoin='round'/></svg>";
}
function nxst_donut(int $p,string $tone):string{
    $col=array("ok"=>"#005447","warn"=>"#d97706","err"=>"#dc2626");
    $r=14;$c=round(2*M_PI*$r,2);$d=round($c*max(0,min(100,$p))/100,2);
    return "<svg width='36' height='36' viewBox='0 0 36 36' aria-hidden='true'><circle cx='18' cy='18' r='$r' fill='none' stroke='#e5e7eb' stroke-width='5'/>"
        ."<circle cx='18' cy='18' r='$r' fill='none' stroke='{$col[$tone]}' stroke-width='5' stroke-dasharray='$d $c' transform='rotate(-90 18 18)' stroke-linecap='round'/></svg>";
}
function nxst_banner():string{
    return "<section class='nxst-card nxst-banner'><span class='nxst-bico' aria-hidden='true'><i class='fas fa-chart-line'></i></span><div class='nxst-btxt'>"
        ."<h2>{nxst_banner_title}</h2><p>{nxst_banner_text}</p><div class='nxst-chips'>"
        ."<span class='nxst-pill'><i class='fas fa-clock'></i>{nxst_chip_live}</span>"
        ."<span class='nxst-pill'><i class='fas fa-chart-area'></i>{nxst_chip_history}</span></div></div></section>";
}
function nxst_geoip_warn():string{
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"))==1){return "";}
    return "<div class='nxst-alert warn' role='status'><i class='fas fa-location-dot'></i><span>{nxst_geoip}</span>"
        ."<a class='nxst-link' href='javascript:blur();' OnClick=\"s_PopUpFull('https://wiki.articatech.com/en/maintenance/geolocation','1024','900');\">{nxst_geoip_link} <i class='fas fa-arrow-up-right-from-square'></i></a></div>";
}
function nxst_tile(string $label,string $ico,string $value,string $cap,string $right=""):string{
    return "<div class='nxst-card nxst-tile'><div class='nxst-tl'><span>$label</span>".($right!==""?$right:"<i class='$ico' aria-hidden='true'></i>")."</div>"
        ."<div class='nxst-tv'>$value</div><p class='nxst-cap'>$cap</p></div>";
}
function nxst_kpis(array $live,array $w,array $series):string{
    $dash="<span class='nxst-none'>—</span>";
    $h=array("<div class='nxst-kpis'>");
    $h[]=nxst_tile("{requests}","fas fa-chart-line",$live["ok"]?nxst_n($live["req"]):$dash,"{nxst_in_10m}",nxst_spark($series["total"]));
    $h[]=nxst_tile("{active_websites}","fas fa-server",$live["ok"]?nxst_n($live["sites"]):$dash,"{nxst_sites_cap}");
    $h[]=nxst_tile("{nxst_visitors}","fas fa-users",$live["ok"]?nxst_n($live["clients"]):$dash,"{nxst_visitors_cap}");
    list($p,$tone,$word)=nxst_health($w);
    if($p<0){
        $h[]=nxst_tile("{nxst_healthy}","fas fa-heart-pulse",$dash,"{nxst_healthy_none}");
    }else{
        $h[]=nxst_tile("{nxst_healthy}","",
            "<span>$p&nbsp;%</span>".nxst_donut($p,$tone),"{nxst_healthy_cap}","<span class='nxst-word $tone'>$word</span>");
    }
    $h[]="</div>";
    return implode("",$h);
}
function nxst_srv_alert(array $w):string{
    if($w["total"]<=0 || $w["srv"]<=0 || $w["srv"]*100<$w["total"]){return "";}
    $top=0;$topc=0;
    foreach(nxst_codes()["srv"] as $c=>$tok){if(($w["codes"][$c] ?? 0)>$top){$top=$w["codes"][$c];$topc=$c;}}
    $txt=nxst_h(nxst_t("nxst_srv_alert",array(nxst_n($w["srv"]),$topc." ".nxst_t("nxst_c{$topc}_s"),nxst_n($top))));
    return "<div class='nxst-alert err' role='alert'><i class='fas fa-server'></i><span>$txt</span>"
        ."<a class='nxst-link' href='javascript:blur();' OnClick='NxstShowSrv()'>{nxst_srv_show} <i class='fas fa-arrow-down'></i></a></div>";
}
function nxst_chart(array $s):array{
    $h=array("<section class='nxst-card nxst-chart'><div class='nxst-ch'><div><h3>{nxst_chart_title}</h3><p>{nxst_chart_sub}</p></div>");
    if(count($s["labels"])==0){
        $h[]="</div><div class='nxst-empty'><i class='fas fa-hourglass-half'></i><b>{nxst_empty_title}</b><span>{nxst_empty_text}</span></div></section>";
        return array(implode("",$h),"");
    }
    $series=array(array("legit","{nxst_ok}","#005447"),array("illegit","{nxst_rej}","#d97706"),array("srv","{nxst_srv}","#dc2626"),array("clients","{nxst_visitors_axis}","#6b7280"));
    $h[]="<div class='nxst-toggles' role='group'>";
    foreach($series as $i=>$x){$h[]="<button type='button' class='nxst-tg' aria-pressed='true' data-i='$i' OnClick='NxstToggle(this)'><i style='background:{$x[2]}'></i>{$x[1]}</button>";}
    $h[]="</div></div>";
    $id="nxst-c-".md5(microtime());
    $tot=array_sum($s["total"]);
    $aria=nxst_h(nxst_t("nxst_chart_aria",array(nxst_n($tot),count($s["labels"]))));
    $h[]="<div class='nxst-canvas'><canvas id='$id' role='img' aria-label=\"$aria\"></canvas></div></section>";
    $J=JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP;
    $lab=array(nxst_t("nxst_ok"),nxst_t("nxst_rej"),nxst_t("nxst_srv"),nxst_t("nxst_visitors_axis"));
    $ds=array(
        array("label"=>$lab[0],"data"=>$s["legit"],"borderColor"=>"#005447","backgroundColor"=>"rgba(0,84,71,0.18)","fill"=>"origin","stack"=>"r","yAxisID"=>"y","tension"=>0.3,"pointRadius"=>0,"borderWidth"=>2),
        array("label"=>$lab[1],"data"=>$s["illegit"],"borderColor"=>"#d97706","backgroundColor"=>"rgba(217,119,6,0.18)","fill"=>"-1","stack"=>"r","yAxisID"=>"y","tension"=>0.3,"pointRadius"=>0,"borderWidth"=>2),
        array("label"=>$lab[2],"data"=>$s["srv"],"borderColor"=>"#dc2626","backgroundColor"=>"rgba(220,38,38,0.18)","fill"=>"-1","stack"=>"r","yAxisID"=>"y","tension"=>0.3,"pointRadius"=>0,"borderWidth"=>2),
        array("label"=>$lab[3],"data"=>$s["clients"],"borderColor"=>"#6b7280","borderDash"=>array(5,4),"fill"=>false,"yAxisID"=>"y1","tension"=>0.3,"pointRadius"=>0,"borderWidth"=>2),
    );
    $js="<script>(function(){var L=".json_encode($s["labels"],$J).",D=".json_encode($ds,$J).";"
        ."function draw(){var c=document.getElementById('$id');if(!c||typeof Chart==='undefined'){return;}"
        ."if(window.NxstChart){try{window.NxstChart.destroy();}catch(e){}}"
        ."var rm=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;"
        ."window.NxstChart=new Chart(c,{type:'line',data:{labels:L,datasets:D},options:{responsive:true,maintainAspectRatio:false,animation:rm?false:{duration:250},"
        ."interaction:{mode:'index',intersect:false},scales:{x:{grid:{display:false},ticks:{autoSkip:true,maxRotation:0,font:{size:13}}},"
        ."y:{stacked:true,beginAtZero:true,ticks:{font:{size:13}}},y1:{position:'right',beginAtZero:true,grid:{drawOnChartArea:false},ticks:{font:{size:13}}}},"
        ."plugins:{legend:{display:false},datalabels:{display:false},tooltip:{callbacks:{label:function(x){return x.dataset.label+': '+Number(x.parsed.y).toLocaleString();}}}}}});}"
        ."window.NxstToggle=function(b){var c=window.NxstChart;if(!c){return;}var i=parseInt(b.getAttribute('data-i'),10);var v=c.isDatasetVisible(i);c.setDatasetVisibility(i,!v);c.update();b.setAttribute('aria-pressed',v?'false':'true');};"
        ."if(typeof Chart!=='undefined'){draw();return;}var e=document.createElement('script');e.src='angular/js/plugins/chartJs/Chart.min.4.5.1.js';e.onload=draw;document.head.appendChild(e);})();</script>";
    return array(implode("",$h),$js);
}
function nxst_sites(array $w):string{
    $h=array("<section class='nxst-card nxst-sites' id='nxst-sites'><div class='nxst-ch'><div><h3>{nxst_sites_title}</h3><p>{nxst_sites_sub}</p></div>"
        ."<span class='nxst-tag'>{nxst_sorted}</span></div>");
    if(count($w["sites"])==0){
        $h[]="<div class='nxst-empty'><i class='fas fa-globe'></i><b>{nxst_sites_empty}</b></div></section>";
        return implode("",$h);
    }
    $h[]="<div class='nxst-scroll'><table class='nxst-tbl'><thead><tr><th scope='col'>{nxst_col_site}</th><th scope='col' class='r'>{requests}</th><th scope='col'>{nxst_col_healthy}</th>"
        ."<th scope='col' class='r'>{nxst_col_rej}</th><th scope='col' class='r'>{nxst_col_srv}</th><th scope='col' class='r'>{nxst_visitors}</th></tr></thead><tbody>";
    $max=0;foreach($w["sites"] as $s){$max=max($max,$s["total"]);}
    foreach($w["sites"] as $i=>$s){
        list($p,$tone,$word)=nxst_health(array("total"=>$s["total"],"legit"=>$s["legit"]));
        $bar=$w["total"]>0?round(100*$s["total"]/$w["total"],1):0;
        $hp=$p<0?"<span class='nxst-none'>—</span>":"<span class='nxst-hp $tone'>$p&nbsp;% · $word</span>";
        $hid=$i>=15?" hidden data-more='1'":"";
        $srvc=$s["srv"]>0?" class='r nxst-srv'":" class='r'";
        $h[]="<tr$hid data-srv='".($s["srv"]>0?1:0)."'><td><span class='nxst-dot $tone'></span><b class='nxst-site' title=\"".nxst_h($s["name"])."\">".nxst_h($s["name"])."</b></td>"
            ."<td class='r'><span class='nxst-num'>".nxst_n($s["total"])."</span><span class='nxst-bar'><span style='width:$bar%'></span></span></td>"
            ."<td>$hp</td><td class='r'>".nxst_n($s["illegit"])."</td><td$srvc>".nxst_n($s["srv"])."</td><td class='r'>".nxst_n($s["clients"])."</td></tr>";
    }
    $h[]="</tbody></table></div>";
    $n=count($w["sites"]);
    if($n>15){
        $h[]="<div class='nxst-foot'><span>".nxst_h(nxst_t("nxst_showing",array(15,$n)))."</span><a class='nxst-link' href='javascript:blur();' OnClick='NxstAll(this)'>".nxst_h(nxst_t("nxst_show_all",array($n)))."</a></div>";
    }
    $h[]="</section>";
    return implode("",$h);
}
function nxst_fail(array $w):string{
    $fail=$w["illegit"]+$w["srv"];
    $h=array("<section class='nxst-card nxst-fail'><div class='nxst-ch'><div><h3>{nxst_fail_title}</h3><p>{nxst_fail_sub}</p></div>"
        .($fail>0?"<span class='nxst-tag err'>".nxst_h(nxst_t("nxst_failed_n",array(nxst_n($fail))))."</span>":"")."</div>");
    if($fail<=0){
        $h[]="<div class='nxst-empty'><i class='fas fa-circle-check'></i><b>{nxst_fail_none}</b></div></section>";
        return implode("",$h);
    }
    $groups=array("rej"=>array("{nxst_rej_group}","warn",$w["illegit"]),"srv"=>array("{nxst_srv_group}","err",$w["srv"]));
    $h[]="<div class='nxst-fl'>";
    foreach(nxst_codes() as $g=>$codes){
        $list=array();
        foreach($codes as $c=>$tok){$v=intval($w["codes"][$c] ?? 0);if($v>0){$list[$c]=$v;}}
        if(count($list)==0){continue;}
        arsort($list);
        $h[]="<div class='nxst-fg {$groups[$g][1]}'><div class='nxst-fgt'><span class='nxst-dot {$groups[$g][1]}'></span>{$groups[$g][0]} <b>".nxst_n($groups[$g][2])."</b></div>";
        foreach($list as $c=>$v){
            $pc=round(100*$v/$fail,1);
            $h[]="<div class='nxst-fr'><div class='nxst-frl'><span><b>$c {".$codes[$c]."_s}</b> — {".$codes[$c]."}</span><span class='nxst-frv'>".nxst_n($v)." · $pc&nbsp;%</span></div>"
                ."<span class='nxst-bar wide'><span style='width:".max(1,$pc)."%'></span></span></div>";
        }
        $h[]="</div>";
    }
    $h[]="</div></section>";
    return implode("",$h);
}
function nxst_foot():string{
    return "<p class='nxst-note'><i class='fas fa-circle-info'></i> {nxst_footnote}</p>";
}
function nxst_js():string{
    $page=CurrentPageName();
    return "<script>NoSpinner();"
        ."window.NxstAll=function(a){document.querySelectorAll('#nxst-sites tr[data-more]').forEach(function(r){r.hidden=false;});var f=a.closest('.nxst-foot');if(f){f.parentNode.removeChild(f);}};"
        ."window.NxstShowSrv=function(){var s=document.getElementById('nxst-sites');if(!s){return;}document.querySelectorAll('#nxst-sites tr[data-more]').forEach(function(r){r.hidden=false;});"
        ."document.querySelectorAll('#nxst-sites tbody tr').forEach(function(r){r.classList.toggle('nxst-hl',r.getAttribute('data-srv')=='1');});s.scrollIntoView({behavior:'smooth',block:'start'});};"
        ."if(window.NxstTimer){clearInterval(window.NxstTimer);}"
        ."window.NxstTimer=setInterval(function(){var h=document.getElementById('main-status');if(!h){clearInterval(window.NxstTimer);return;}"
        ."if(document.querySelector('.modal.in,.modal.show,.sweet-alert.visible')){return;}LoadAjaxSilent('main-status','$page?charts-tab=yes');},120000);"
        ."</script>";
}
function nxst_style():string{
    return "<style>
.nxst{display:flex;flex-direction:column;gap:16px;color:#1f2937;margin-top:10px}
.nxst-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 2px rgba(0,0,0,.05);min-width:0}
.nxst-banner{display:flex;gap:18px;align-items:flex-start;padding:20px 24px;border-left:4px solid #005447}
.nxst-bico{flex:0 0 48px;height:48px;border-radius:12px;background:#e6eeed;color:#005447;display:flex;align-items:center;justify-content:center;font-size:20px}
.nxst-btxt{min-width:0}
.nxst-btxt h2{margin:0 0 6px 0;font-size:18px;font-weight:600;color:#111827}
.nxst-btxt p{margin:0;font-size:14px;color:#4b5563;line-height:1.55;max-width:960px}
.nxst-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.nxst-pill{display:inline-flex;align-items:center;gap:6px;padding:3px 12px;border-radius:999px;background:#f2f6f6;border:1px solid #e6eeed;color:#005447;font-size:13px}
.nxst-alert{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:12px;font-size:14px}
.nxst-alert>i,.nxst-alert>svg{flex:0 0 auto;font-size:16px}
.nxst-alert>span{flex:1 1 auto}
.nxst-alert.warn{background:#fffbeb;border:1px solid #fde68a;color:#78350f}
.nxst-alert.warn>i,.nxst-alert.warn>svg{color:#d97706}
.nxst-alert.err{background:#fef2f2;border:1px solid #fecaca;color:#7f1d1d}
.nxst-alert.err>i,.nxst-alert.err>svg{color:#dc2626}
.nxst-link{color:#005447;font-weight:600;font-size:13px;text-decoration:underline;cursor:pointer;white-space:nowrap}
.nxst-link:hover{color:#003d33}
.nxst-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.nxst-tile{padding:16px 18px;display:flex;flex-direction:column;gap:6px;box-sizing:border-box}
.nxst-tl{display:flex;justify-content:space-between;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#4b5563;text-transform:uppercase;letter-spacing:.04em;min-height:28px}
.nxst-tl>i,.nxst-tl>svg{color:#9ca3af}
.nxst-tv{display:flex;align-items:center;gap:12px;font-size:28px;font-weight:600;color:#111827;font-variant-numeric:tabular-nums;line-height:1.2}
.nxst-cap{margin:0;font-size:13px;color:#6b7280}
.nxst-none{color:#9ca3af}
.nxst-word{font-size:13px;font-weight:600;text-transform:none;letter-spacing:0;padding:1px 9px;border-radius:999px}
.nxst-word.ok,.nxst-hp.ok{background:#e6eeed;color:#005447}
.nxst-word.warn,.nxst-hp.warn{background:#fffbeb;color:#92400e}
.nxst-word.err,.nxst-hp.err{background:#fef2f2;color:#b91c1c}
.nxst-chart{padding:18px 22px}
.nxst-ch{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:12px;flex-wrap:wrap}
.nxst-ch h3{margin:0;font-size:15px;font-weight:600;color:#111827}
.nxst-ch p{margin:3px 0 0 0;font-size:13px;color:#6b7280}
.nxst-toggles{display:flex;flex-wrap:wrap;gap:8px}
.nxst-tg{display:inline-flex;align-items:center;gap:8px;padding:4px 12px;border-radius:999px;border:1px solid #d1d5db;background:#fff;font-size:13px;color:#1f2937;cursor:pointer}
.nxst-tg i{width:10px;height:10px;border-radius:50%;display:inline-block}
.nxst-tg[aria-pressed='false']{opacity:.45;text-decoration:line-through}
.nxst-canvas{position:relative;height:320px}
.nxst-empty{display:flex;flex-direction:column;align-items:center;gap:6px;padding:36px 16px;text-align:center;color:#6b7280;font-size:13px}
.nxst-empty>i,.nxst-empty>svg{font-size:28px;color:#9ca3af;margin-bottom:6px}
.nxst-empty b{font-size:15px;color:#111827}
.nxst-grid{display:grid;grid-template-columns:minmax(0,3fr) minmax(0,2fr);gap:16px;align-items:start}
.nxst-sites .nxst-ch,.nxst-fail .nxst-ch{padding:16px 20px 0 20px}
.nxst-tag{font-size:13px;padding:2px 10px;border-radius:999px;background:#f3f4f6;color:#374151;white-space:nowrap}
.nxst-tag.err{background:#fef2f2;color:#b91c1c}
.nxst-scroll{overflow-x:auto}
.nxst-tbl{width:100%;border-collapse:collapse}
.nxst-tbl th{font-size:13px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;padding:10px 14px;border-bottom:1px solid #eef0f3;background:#fafbfb;text-align:left;white-space:nowrap}
.nxst-tbl td{padding:10px 14px;border-bottom:1px solid #f1f3f5;font-size:14px;color:#111827;vertical-align:middle}
.nxst-tbl .r{text-align:right;font-variant-numeric:tabular-nums}
.nxst-tbl tr.nxst-hl td{background:#fef2f2}
.nxst-site{font-weight:600;max-width:240px;display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:middle}
.nxst-dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:8px;vertical-align:middle;background:#9ca3af}
.nxst-dot.ok{background:#005447}.nxst-dot.warn{background:#d97706}.nxst-dot.err{background:#dc2626}
.nxst-num{display:block}
.nxst-bar{display:block;height:4px;border-radius:999px;background:#e5e7eb;overflow:hidden;margin-top:4px;min-width:60px}
.nxst-bar>span{display:block;height:100%;background:#005447}
.nxst-bar.wide{margin-top:6px}
.nxst-hp{font-size:13px;font-weight:600;padding:2px 8px;border-radius:999px;white-space:nowrap}
.nxst-tbl td.nxst-srv{color:#b91c1c;font-weight:700}
#top-status .nxst-alert{margin-top:16px}
.nxst-foot{display:flex;justify-content:space-between;align-items:center;padding:10px 16px;font-size:13px;color:#6b7280;background:#fafbfb;border-radius:0 0 12px 12px}
.nxst-fl{display:flex;flex-direction:column;gap:14px;padding:12px 20px 18px}
.nxst-fgt{font-size:14px;font-weight:600;margin-bottom:6px}
.nxst-fg.warn .nxst-fgt{color:#92400e}.nxst-fg.err .nxst-fgt{color:#b91c1c}
.nxst-fg.warn .nxst-bar>span{background:#d97706}.nxst-fg.err .nxst-bar>span{background:#dc2626}
.nxst-fr{padding:5px 0}
.nxst-frl{display:flex;justify-content:space-between;gap:10px;font-size:13px;color:#374151}
.nxst-frv{white-space:nowrap;color:#4b5563;font-variant-numeric:tabular-nums}
.nxst-note{margin:0;font-size:13px;color:#6b7280;line-height:1.55}
.nxst-note i{margin-right:4px}
.nxst button:focus-visible,.nxst a:focus-visible{outline:2px solid #005447;outline-offset:2px}
@media(max-width:1200px){.nxst-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.nxst-grid{grid-template-columns:minmax(0,1fr)}}
@media(prefers-reduced-motion:no-preference){.nxst-card{animation:nxstIn .15s ease-out}}
@keyframes nxstIn{from{opacity:0}to{opacity:1}}
</style>";
}
function nxst_tiny():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $top=array();
    $top[]=array("Loadjs('$page?Prometheus-js=yes');",ico_monitor,"Prometheus");
    $T=array("TITLE"=>"{nxst_title}","ICO"=>ico_dashboard,"EXPL"=>"{nxst_header_explain}","URL"=>"nginx-statistics","BUTTONS"=>$tpl->table_buttons($top));
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($T)))."');";
}
// Partie rafraichie toutes les 15 s : tuiles et alerte des erreurs serveur.
function top_status():bool{
    $tpl=new template_admin();
    $w=nxst_window();
    $h=nxst_kpis(nxst_live(),$w,nxst_series()).nxst_srv_alert($w);
    echo $tpl->_ENGINE_parse_body($h)."<script>NoSpinner();</script>";
    return true;
}
// Graphique et tableaux de la derniere fenetre (recharges toutes les 2 min, une fenetre dure 10 min).
function charts_tab():bool{
    $tpl=new template_admin();
    $w=nxst_window();
    if(!$w["ok"]){
        echo $tpl->_ENGINE_parse_body("<div class='nxst-alert err' role='alert'><i class='fas fa-database'></i><span>{nxst_pg_down}</span></div>").nxst_js();
        return true;
    }
    list($ch,$cjs)=nxst_chart(nxst_series());
    $h=$ch."<div class='nxst-grid'>".nxst_sites($w).nxst_fail($w)."</div>".nxst_foot();
    echo $tpl->_ENGINE_parse_body($h).$cjs.nxst_js();
    return true;
}
function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $js=$tpl->RefreshInterval_js("top-status",$page,"top-status=yes",15);
    $h="<div class='nxst'>".nxst_banner().nxst_geoip_warn()."<div id='top-status'></div><div id='main-status' class='nxst'></div></div>";
    echo nxst_style().$tpl->_ENGINE_parse_body($h)."<script>$js;LoadAjaxSilent('main-status','$page?charts-tab=yes');".nxst_tiny()."</script>";
}

function worldmap(){
$tpl=new template_admin();
$html[]="<table style='width:100%;margin-top:30px'>";
$html[]="<tr>";
$html[]="<td style='vertical-align:top;'><div id='world-map-container' style='width:1024px;height:400px'></div></td>";
$html[]="</tr>";
$html[]="<tr>";
$html[]="<td style='vertical-align:top;'><div id='world-map-data'></div></td>";
$html[]="</tr>";
$html[]="</table>";
$html[]=renderWorldMap();
echo $tpl->_ENGINE_parse_body($html);

}

function renderWorldMap($divId = 'world-map-container'):string{

    $page=CurrentPageName();

return "<script>
async function loadWorldMap(dataUrl) {
  try {
    // 1) Load jQuery + jVectorMap assets

    await loadCSS('/js/jvectormap/jquery-jvectormap-2.0.5.css');
    await loadJS('/js/jvectormap/jquery-jvectormap-2.0.5.min.js');
    await loadJS('/js/jvectormap/jquery-jvectormap-world-mill.min.js');

    // 2) Prepare inner container
    const container = document.getElementById('$divId');
    container.innerHTML ='<div id=\"$divId-inner\" style=\"width:100%; height:100%;\"></div>';

    // 3) Fetch your map-data payload
    const resp = await fetch(dataUrl);
    if (!resp.ok) throw new Error('Data load failed: ' + resp.statusText);
    const { values, links } = await resp.json();
    console.log('OK HERE');
    // 4) Render the vector map
    $('#$divId-inner').vectorMap({
      map: 'world_mill',
      backgroundColor: 'transparent',
      regionStyle: {
        initial: {
          fill: '#FFFFFF',
          stroke: '#2c2e38',
          'stroke-width': 1
        }
      },
      series: {
        regions: [{
          values: values,
          scale: [ '#6fe7e1','#005447'],  // low→high turquoise→green
          normalizeFunction: 'polynomial'
        }]
      },
      onRegionTipShow: function(e, el, code){
        // show value in tooltip if you like
        const v = values[code] || 0;
        el.html(el.html() + ' (value: ' + v + ')');
      },
      onRegionClick: function(event, code){
        if (links[code]) {
          window.open(links[code], '_blank');
        }
      }
    });

  } catch (err) {
    console.error(err);
  }
}
console.log('--> document.addEventListener');
  loadWorldMap( '$page?map-data=yes');
  LoadAjaxSilent('worldmap-country','$page?worldmap-country=yes');
</script>";

}
function worldmap_data(){
    header('Content-Type: application/json');

    $query = "SELECT SUM(hits) as hits,hotstinfos.country
    FROM hotstinfos_realtime,hotstinfos
    WHERE hotstinfos.ipaddr=COALESCE(hotstinfos_realtime.ipaddr,'0.0.0.0'::inet + hotstinfos_realtime.hton) GROUP by hotstinfos.country ORDER by hits DESC";

    $q = new postgres_sql();
    $results = $q->QUERY_SQL($query);
    if (!$q->ok) {
        writelogs("$query $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }

    $page=CurrentPageName();

    while ($ligne = @pg_fetch_assoc($results)) {
        $hits = $ligne['hits'];
        $country_name = $ligne['country'];
        $values[$country_name] = $hits;
        $links[$country_name] = "Loadjs('$page?worldmap-country=$country_name');";
    }

    echo json_encode(['values'=>$values,'actions'=>$links]);
}
