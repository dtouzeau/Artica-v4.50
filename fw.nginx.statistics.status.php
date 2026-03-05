<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();


if(isset($_POST["ConnectMonitorPrometheusPort"])){Prometheus_save();exit;}
if(isset($_POST["ConnectMonitorPrometheus"])){Prometheus_save();exit;}
if(isset($_GET["worldmap"])){worldmap();exit;}
if(isset($_GET["map-data"])){worldmap_data();exit;}
if(isset($_GET["map-table"])){worldmap_table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["top-status"])){top_status();exit;}
if(isset($_GET["top-usersagents-1"])){UsersAgentsWidgets();exit;}
if(isset($_GET["top-htons-1"])){HtonsWidgets();exit;}
if(isset($_GET["parameters-js"])){parameters_js();exit;}
if(isset($_GET["parameters-popup"])){parameters_popup();exit;}
if(isset($_GET["Prometheus-js"])){Prometheus_js();exit;}
if(isset($_GET["Prometheus-popup"])){Prometheus_popup();exit;}


if(isset($_POST["UseAbusesIPReputationKey"])){parameters_save();exit;}
if(isset($_GET["charts-tab"])){charts_tab();exit;}
if(isset($_GET["pie-data-js"])){pie_data_js();exit;}
if(isset($_GET["trends-data-js"])){trends_data_js();exit;}
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
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    if(isset($_POST["ConnectMonitorPrometheusPort"])){
        if(intval($_POST["ConnectMonitorPrometheusPort"])==0){
            $_POST["ConnectMonitorPrometheusPort"]=9925;
        }
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

function charts_tab():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]="<div style='margin:10px'>";

    $html[]="<table style='width:100%'><tr>";
    $html[]="<td style='width:25%;vertical-align:top;padding:5px'>";
    $html[]="<div class='ibox'><div class='ibox-title'><h5>{requests}</h5></div>";
    $html[]="<div class='ibox-content' style='height:280px'><canvas id='pie-requests'></canvas></div></div>";
    $html[]="</td>";
    $html[]="<td style='width:25%;vertical-align:top;padding:5px'>";
    $html[]="<div class='ibox'><div class='ibox-title'><h5>{legitimate_flow}</h5></div>";
    $html[]="<div class='ibox-content' style='height:280px'><canvas id='pie-legit'></canvas></div></div>";
    $html[]="</td>";
    $html[]="<td style='width:25%;vertical-align:top;padding:5px'>";
    $html[]="<div class='ibox'><div class='ibox-title'><h5>{illegitimate_flow}</h5></div>";
    $html[]="<div class='ibox-content' style='height:280px'><canvas id='pie-illegit'></canvas></div></div>";
    $html[]="</td>";
    $html[]="<td style='width:25%;vertical-align:top;padding:5px'>";
    $html[]="<div class='ibox'><div class='ibox-title'><h5>{distribution_errors}</h5></div>";
    $html[]="<div class='ibox-content' style='height:280px'><canvas id='pie-errors'></canvas></div></div>";
    $html[]="</td>";
    $html[]="</tr></table>";

    $html[]="<table style='width:100%;margin-top:10px'><tr>";
    $html[]="<td style='width:50%;vertical-align:top;padding:5px'>";
    $html[]="<div class='ibox'><div class='ibox-title'><h5>{requests_and_clients_trend}</h5></div>";
    $html[]="<div class='ibox-content'><canvas id='line-requests' height='120'></canvas></div></div>";
    $html[]="</td>";
    $html[]="<td style='width:50%;vertical-align:top;padding:5px'>";
    $html[]="<div class='ibox'><div class='ibox-title'><h5>{traffic_flow_trend}</h5></div>";
    $html[]="<div class='ibox-content'><canvas id='line-flows' height='120'></canvas></div></div>";
    $html[]="</td>";
    $html[]="</tr></table>";
    $html[]="</div>";
    $html[]="<script>";
    $html[]="Loadjs('$page?pie-data-js=yes');";
    $html[]="Loadjs('$page?trends-data-js=yes');";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function pie_data_js():bool{
    header("content-type: application/x-javascript");
    $sites=get_site_names();

    $q1=new postgres_sql();
    $sql="SELECT json_agg(sub ORDER BY total_requests DESC) as rows FROM (
        SELECT service_id,
            SUM(total_requests) as total_requests,
            SUM(count_legit) as count_legit,
            SUM(count_illegit) as count_illegit,
            SUM(count_server_err) as count_server_err
        FROM rvproxy_monitor
        WHERE zdate=(SELECT MAX(zdate) FROM rvproxy_monitor)
        GROUP BY service_id
    ) sub";

    $agg=$q1->mysqli_fetch_array($sql);
    $rows=[];
    if($agg && isset($agg['rows'])){$rows=json_decode($agg['rows'],true) ?? [];}

    $labels_rqs=[];$data_rqs=[];
    $labels_legit=[];$data_legit=[];
    $labels_illegit=[];$data_illegit=[];

    foreach($rows as $row){
        $sid=$row['service_id'];
        $name=isset($sites[$sid]) ? $sites[$sid] : "Service #$sid";
        if(strlen($name)>30) $name=substr($name,0,27)."...";

        $total=intval($row['total_requests']);
        $legit=intval($row['count_legit']);
        $illegit=intval($row['count_illegit']);

        if($total>0){$labels_rqs[]=$name;$data_rqs[]=$total;}
        if($legit>0){$labels_legit[]=$name;$data_legit[]=$legit;}
        if($illegit>0){$labels_illegit[]=$name;$data_illegit[]=$illegit;}
    }

    $q2=new postgres_sql();
    $sql2="SELECT
        SUM(count_400) as c400,SUM(count_401) as c401,SUM(count_403) as c403,
        SUM(count_404) as c404,SUM(count_405) as c405,SUM(count_429) as c429,
        SUM(count_444) as c444,SUM(count_499) as c499,SUM(count_408) as c408,
        SUM(count_500) as c500,SUM(count_502) as c502,SUM(count_503) as c503,
        SUM(count_504) as c504
    FROM rvproxy_monitor
    WHERE zdate=(SELECT MAX(zdate) FROM rvproxy_monitor)";

    $err_row=$q2->mysqli_fetch_array($sql2);
    $error_labels=[];$error_data=[];
    if(is_array($err_row)){
        $err_map=[
            '400 Bad Request'=>intval($err_row['c400'] ?? 0),
            '401 Unauthorized'=>intval($err_row['c401'] ?? 0),
            '403 Forbidden'=>intval($err_row['c403'] ?? 0),
            '404 Not Found'=>intval($err_row['c404'] ?? 0),
            '405 Not Allowed'=>intval($err_row['c405'] ?? 0),
            '408 Timeout'=>intval($err_row['c408'] ?? 0),
            '429 Rate Limit'=>intval($err_row['c429'] ?? 0),
            '444 No Response'=>intval($err_row['c444'] ?? 0),
            '499 Client Closed'=>intval($err_row['c499'] ?? 0),
            '500 Internal'=>intval($err_row['c500'] ?? 0),
            '502 Bad Gateway'=>intval($err_row['c502'] ?? 0),
            '503 Unavailable'=>intval($err_row['c503'] ?? 0),
            '504 Gateway Timeout'=>intval($err_row['c504'] ?? 0),
        ];
        foreach($err_map as $label=>$val){
            if($val>0){$error_labels[]=$label;$error_data[]=$val;}
        }
    }

    $colors=json_encode(['#1ab394','#23c6c8','#1c84c6','#f8ac59','#ed5565','#ab7df6','#676a6c','#2f4050','#0d6efd','#20c997','#ffc107','#6610f2','#dc3545','#17a2b8','#28a745']);
    $errColors=json_encode(['#f8ac59','#ed5565','#ab7df6','#1c84c6','#676a6c','#23c6c8','#e74c3c','#c0392b','#e67e22','#2ecc71','#3498db','#9b59b6','#34495e']);
    $piOpts=json_encode([
        'responsive'=>true,'maintainAspectRatio'=>false,
        'plugins'=>['legend'=>['position'=>'right','labels'=>['boxWidth'=>10,'font'=>['size'=>10]]]]
    ]);

    $js=[];
    $js[]="(function(){";
    $js[]="var colors=$colors;";
    $js[]="var errColors=$errColors;";
    $js[]="var piOpts=$piOpts;";

    $js[]="try{ var el=document.getElementById('pie-requests');";
    $js[]="if(el){new Chart(el,{type:'doughnut',data:{labels:".json_encode($labels_rqs).",datasets:[{data:".json_encode($data_rqs).",backgroundColor:colors}]},options:piOpts});";
    $js[]="}}catch(e){console.error('pie-requests',e);}";

    $js[]="try{ var el=document.getElementById('pie-legit');";
    $js[]="if(el){new Chart(el,{type:'doughnut',data:{labels:".json_encode($labels_legit).",datasets:[{data:".json_encode($data_legit).",backgroundColor:colors}]},options:piOpts});";
    $js[]="}}catch(e){console.error('pie-legit',e);}";

    $js[]="try{ var el=document.getElementById('pie-illegit');";
    $js[]="if(el){new Chart(el,{type:'doughnut',data:{labels:".json_encode($labels_illegit).",datasets:[{data:".json_encode($data_illegit).",backgroundColor:colors}]},options:piOpts});";
    $js[]="}}catch(e){console.error('pie-illegit',e);}";

    $js[]="try{ var el=document.getElementById('pie-errors');";
    $js[]="if(el){new Chart(el,{type:'doughnut',data:{labels:".json_encode($error_labels).",datasets:[{data:".json_encode($error_data).",backgroundColor:errColors}]},options:piOpts});";
    $js[]="}}catch(e){console.error('pie-errors',e);}";

    $js[]="})();";
    echo implode("\n",$js);
    return true;
}

function trends_data_js():bool{
    header("content-type: application/x-javascript");
    $q=new postgres_sql();

    $sql="SELECT json_agg(sub ORDER BY zdate ASC) as rows FROM (
        SELECT zdate,
            SUM(total_requests) as total_requests,
            SUM(unique_clients) as unique_clients,
            SUM(count_legit) as count_legit,
            SUM(count_illegit) as count_illegit,
            SUM(count_server_err) as count_server_err
        FROM rvproxy_monitor
        GROUP BY zdate
    ) sub";

    $agg=$q->mysqli_fetch_array($sql);
    $rows=[];
    if($agg && isset($agg['rows'])){$rows=json_decode($agg['rows'],true) ?? [];}

    $labels=[];$rqs=[];$clients=[];$legit=[];$illegit=[];$errors=[];
    foreach($rows as $row){
        $labels[]=date("m-d H:i",strtotime($row['zdate']));
        $rqs[]=intval($row['total_requests']);
        $clients[]=intval($row['unique_clients']);
        $legit[]=intval($row['count_legit']);
        $illegit[]=intval($row['count_illegit']);
        $errors[]=intval($row['count_server_err']);
    }

    $labelsJson=json_encode($labels);
    $js=[];
    $js[]="(function(){";

    $js[]="try{ var el=document.getElementById('line-requests');";
    $js[]="if(el){new Chart(el,{type:'line',data:{";
    $js[]="labels:$labelsJson,";
    $js[]="datasets:[";
    $js[]="{label:'Requests',data:".json_encode($rqs).",borderColor:'#1ab394',backgroundColor:'rgba(26,179,148,0.1)',fill:true,tension:0.3,pointRadius:2},";
    $js[]="{label:'Clients',data:".json_encode($clients).",borderColor:'#1c84c6',backgroundColor:'rgba(28,132,198,0.1)',fill:true,tension:0.3,pointRadius:2,yAxisID:'y1'}";
    $js[]="]},options:{responsive:true,interaction:{mode:'index',intersect:false},";
    $js[]="scales:{y:{type:'linear',position:'left',title:{display:true,text:'Requests'},beginAtZero:true},";
    $js[]="y1:{type:'linear',position:'right',grid:{drawOnChartArea:false},title:{display:true,text:'Clients'},beginAtZero:true}},";
    $js[]="plugins:{legend:{position:'top'}}}});";
    $js[]="}}catch(e){console.error('line-requests',e);}";

    $js[]="try{ var el=document.getElementById('line-flows');";
    $js[]="if(el){new Chart(el,{type:'line',data:{";
    $js[]="labels:$labelsJson,";
    $js[]="datasets:[";
    $js[]="{label:'Legitimate',data:".json_encode($legit).",borderColor:'#1ab394',backgroundColor:'rgba(26,179,148,0.15)',fill:true,tension:0.3,pointRadius:2},";
    $js[]="{label:'Illegitimate',data:".json_encode($illegit).",borderColor:'#f8ac59',backgroundColor:'rgba(248,172,89,0.15)',fill:true,tension:0.3,pointRadius:2},";
    $js[]="{label:'Server Errors',data:".json_encode($errors).",borderColor:'#ed5565',backgroundColor:'rgba(237,85,101,0.15)',fill:true,tension:0.3,pointRadius:2}";
    $js[]="]},options:{responsive:true,interaction:{mode:'index',intersect:false},";
    $js[]="scales:{y:{beginAtZero:true}},";
    $js[]="plugins:{legend:{position:'top'}}}});";
    $js[]="}}catch(e){console.error('line-flows',e);}";

    $js[]="})();";
    echo implode("\n",$js);
    return true;
}

function HtonsWidgets(){
    $tpl=new template_admin();
    $NginxHtonsToday=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHtonsToday"));
    $NginxHtonsTotal=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHtonsTotal"));

    $usaico=ico_computer;
    $widget_AbuseIP=$tpl->widget_style1("gray-bg",ico_shield_disabled,"{reputation}","{inactive}");

    $widget_today=$tpl->widget_style1("gray-bg",$usaico,"{client_source_ip_address} {today}",0);
    $widget_total=$tpl->widget_style1("gray-bg",$usaico,"{client_source_ip_address} {total}",0);
    if($NginxHtonsToday>0){
        $widget_today=$tpl->widget_style1("navy-bg",$usaico,"{client_source_ip_address} {today}",$tpl->FormatNumber($NginxHtonsToday));
    }
    if($NginxHtonsTotal>0){
        $widget_total=$tpl->widget_style1("navy-bg",$usaico,"{client_source_ip_address} {total}",$tpl->FormatNumber($NginxHtonsTotal));
    }
    $UseAbuseIPReputationKey=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseAbusesIPReputationKey");
    if(strlen($UseAbuseIPReputationKey)>10){
        $widget_AbuseIP=$tpl->widget_style1("navy-bg",ico_shield,"{reputation} ","{active2}");

        $q=new postgres_sql();
        $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM hotstinfos WHERE abuseipdb=1");
        $THreats=intval($ligne["tcount"]);
        if($THreats>0){
            $widget_AbuseIP=$tpl->widget_style1("yellow-bg",ico_shield,"{reputation} ",$tpl->FormatNumber($THreats));
        }
    }

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$widget_today</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$widget_total</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$widget_AbuseIP</td>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}

function UsersAgentsWidgets():bool{
    $cloudShower="fa-solid fa-cloud-showers";
    $tpl=new template_admin();
    $NginxUserAgentsToday=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxUserAgentsToday"));
    $NginxUserAgentsTotal=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxUserAgentsTotal"));
    $NginxHtonsToday=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHtonsToday"));

    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT SUM(hits) as hits FROM hotstinfos_days");
    $hits=intval($ligne["hits"]);

   $usaico="fa-regular fa-user-robot";
   $widget_rqs_users=$tpl->widget_style1("gray-bg",$cloudShower,"{requests}/{ipaddr}",0);
    if (($hits>0) AND ($NginxHtonsToday>0)){
        $thits=round($hits/$NginxHtonsToday,2);
        if($thits>100) {
            $thits = $tpl->FormatNumber($thits);
        }
        $widget_rqs_users=$tpl->widget_style1("navy-bg",$cloudShower,"{requests}/{ipaddr}","$thits");
    }

    $widget_today=$tpl->widget_style1("gray-bg",$usaico,"UserAgents {today}",0);
   $widget_total=$tpl->widget_style1("gray-bg",$usaico,"UserAgents {total}",0);
   if($NginxUserAgentsToday>0){
       $widget_today=$tpl->widget_style1("navy-bg",$usaico,"UserAgents {today}",$tpl->FormatNumber($NginxUserAgentsToday));
   }
   if($NginxUserAgentsTotal>0){
       $widget_total=$tpl->widget_style1("navy-bg",$usaico,"UserAgents {total}",$tpl->FormatNumber($NginxUserAgentsTotal));
   }

   $html[]="<table style='width:100%'>";
   $html[]="<tr>";
   $html[]="<td style='width:33%'>$widget_today</td>";
   $html[]="<td style='width:33%;padding-left:5px'>$widget_total</td>";
   $html[]="<td style='width:33%;padding-left:5px'>$widget_rqs_users</td>";
   $html[]="</table>";

   $page=CurrentPageName();
   $html[]="<script>";
   $html[]="LoadAjaxSilent('top-htons-1','$page?top-htons-1=yes');";
   $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function top_status_err($error){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $widget_rqs=$tpl->widget_style1("red-bg","fa-duotone fa-solid fa-raindrops","{requests}",$error);
    $widget_Cnxs=$tpl->widget_style1("red-bg","fa-duotone fa-solid fa-circle-nodes","{active_connections}",$error);
    $widget_events=$tpl->widget_style1("red-bg","fa-duotone fa-solid fa-circle-nodes","{events}/{second}",$error);
    $title="{APP_NGINX} {statistics}";

    $topbuttons[]=array("Loadjs('$page?parameters-js=yes');",ico_params,"{parameters}");
    $TINY_ARRAY["TITLE"]=$title;
    $TINY_ARRAY["ICO"]=ico_dashboard;
    $TINY_ARRAY["EXPL"]="{statistics_nginx_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$widget_rqs</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$widget_Cnxs</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$widget_events</td>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjaxSilent('top-usersagents-1','$page?top-usersagents-1=yes');";
    $html[]=$headsjs;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function top_status():bool{
    $tpl=new template_admin();
    $sock=new sockets();
    $page=CurrentPageName();
    $data=$sock->REST_API_NGINX("/10mins/stats");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        top_status_err("{error} #1");
        return false;
    }
    if(!$json->Status){
        top_status_err("{error} #2");
        return false;
    }
    if(!property_exists($json,"Stats")){
        top_status_err("{error} #3");
        return false;
    }
    $jsStats=$json->Stats;
    $widget_rqs=$tpl->widget_style1("gray-bg","fa-duotone fa-solid fa-raindrops","{requests}",0);
    $widget_Cnxs=$tpl->widget_style1("gray-bg",ico_earth,"{active_websites}",0);
    $widget_events=$tpl->widget_style1("gray-bg",ico_computer,"{clients}",0);


    if($jsStats->totalQuery>0){
        $totalQuery=$tpl->FormatNumber($jsStats->totalQuery);
        $widget_rqs=$tpl->widget_style1("green-bg","fa-duotone fa-solid fa-raindrops","{requests} {last_10_minutes}",$totalQuery);

    }

    if($jsStats->numberOfSites>0){
        $totalQuery=$tpl->FormatNumber($jsStats->numberOfSites);
        $widget_Cnxs=$tpl->widget_style1("navy-bg",ico_earth,"{active_websites} {last_10_minutes}",$totalQuery);
    }

    if($jsStats->numberOfClient>0){
        $totalQuery=$tpl->FormatNumber($jsStats->numberOfClient);
        $widget_events=$tpl->widget_style1("navy-bg",ico_computer,"{clients} {last_10_minutes}",$totalQuery);
    }

    $title="{APP_NGINX} {statistics}";

    $EnableGeoipUpdateWarn="";
    $EnableGeoipUpdate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"));
    if($EnableGeoipUpdate==0){
        $btn=$tpl->button_autnonome("wiki.articatech.com",
            "s_PopUpFull('https://wiki.articatech.com/en/maintenance/geolocation','1024','900');",
            "fas fa-file-code","",200,"btn-warning");
        $EnableGeoipUpdateWarn="<div style='margin-top:10px;width:800px;border:1px solid #f8ac59;border-radius: 3px;padding:5px'><span style='text-warning'>{warning_geoip}</span><div style='text-align: right;padding-right:2px;margin-top:-20px;'>$btn</div></div>";
    }

    $topbuttons[]=array("Loadjs('$page?Prometheus-js=yes');",ico_monitor,"Prometheus");
    $TINY_ARRAY["TITLE"]=$title;
    $TINY_ARRAY["ICO"]=ico_dashboard;
    $TINY_ARRAY["EXPL"]="{statistics_nginx_explain}$EnableGeoipUpdateWarn";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$widget_rqs</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$widget_Cnxs</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$widget_events</td>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjaxSilent('main-status','$page?charts-tab=yes');";
    $html[]=$headsjs;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();


    $js=$tpl->RefreshInterval_js("top-status",$page,"top-status=yes",15);
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;'><div id='top-status'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;'><div id='main-status'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="$js";
    $html[]="</script>";
    echo implode("",$html);

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
    WHERE hotstinfos_realtime.hton=hotstinfos.hton GROUP by hotstinfos.country ORDER by hits DESC";

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
