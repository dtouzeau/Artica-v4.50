<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["site-selector"])){site_selector();exit;}
if(isset($_GET["site-content"])){site_content();exit;}
if(isset($_GET["site-widgets"])){site_widgets();exit;}
if(isset($_GET["site-pie-js"])){site_pie_js();exit;}
if(isset($_GET["site-trends-js"])){site_trends_js();exit;}
if(isset($_GET["site-table"])){site_table();exit;}
if(isset($_GET["select-site-js"])){select_sites_js();exit;}
if(isset($_GET["select-site-start"])){select_sites_start();exit;}
if(isset($_GET["select-site-search"])){select_sites_search();exit;}
if(isset($_GET["site-selected-js"])){select_sites_selected();exit;}
page();

function select_sites_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $siteid=intval($_GET["select-site-js"]);
    return $tpl->js_dialog10("{websites}","$page?select-site-start=$siteid",550);
}
function select_sites_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $siteid=intval($_GET["select-site-start"]);
    echo $tpl->search_block($page,"","","","&select-site-search=$siteid");
    return true;
}
function select_sites_selected():bool{
    $page=CurrentPageName();
    $serviceid=intval($_GET["site-selected-js"]);
    $_SESSION["select-site-selected"]=$serviceid;
    $function=$_GET["function"];
    echo "LoadAjax('site-content-area','$page?site-content=yes&serviceid=$serviceid');\n$function();";
    return true;
}
function select_sites_search():bool{
    $page=CurrentPageName();
    $function=$_GET["function"];
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $TRCLASS="";
    $t=time();
    $exclude=intval($_GET["select-site-search"]);
    $html[]="<table id='table-$t' class='table table-stripped' data-page-size='50'>";
    $html[]="<thead><tr>";
    $html[]="<th data-sortable=false data-type='text' style='width:1%'>&nbsp;</th>";
    $html[]="<th data-sortable=true data-type='text'>{websites}</th>";
    $html[]="<th data-sortable=false data-type='text' style='width:1%'>&nbsp;</th>";
    $html[]="</tr></thead><tbody>";
    $get_site_names=get_site_names();
    foreach($get_site_names as $serviceid=>$name){

        if(isset($_SESSION["select-site-selected"])){
            if($serviceid==$_SESSION["select-site-selected"]){
                continue;
            }
        }else {
            if ($serviceid == $exclude) {
                continue;
            }
        }
        if(strlen($_GET["search"])>2){
            if(!preg_match("#{$_GET["search"]}#",$name)){
                continue;
            }
        }
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        //$js="LoadAjax('site-content-area','$page?site-content=yes&serviceid=$serviceid');$function();";
        $js="Loadjs('$page?site-selected-js=$serviceid&function=$function');";
        $but=$tpl->button_autnonome("{select}",$js,ico_check,"AsWebStatisticsAdministrator",0,"btn-primary");
        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td style='width:1%'><i class='".ico_earth."'></td>";
        $html[]="<td style='width:99%'><strong style='font-size:16px'>$name</strong></td>";
        $html[]="<td style='width:1%'>$but</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="<tfoot><tr><td colspan='3'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $html[]="</table>";
    $html[]="<script>NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $title="{APP_NGINX} {statistics} - {websites}";
    $html=$tpl->page_header($title,"fa-duotone fa-solid fa-chart-mixed",
        "{statistics_nginx_site_explain}","$page?tabs=yes",
        "nginx-statistics-sites",
        "progress-nginx-statistics-sites",false,
        "table-nginx-statistics-sites"
    );
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: $title",$html);
        echo $tpl->build_firewall();
        return true;
    }
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $array["{websites}"]="$page?site-selector=yes";
    echo $tpl->tabs_default($array);
}

function get_site_names():array{
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/sites/ids");
    $json=json_decode($data);
    if(!$json || !isset($json->Status) || !$json->Status || !property_exists($json,"sites")){return [];}
    return (array)$json->sites;
}

function site_selector():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sites=get_site_names();
    if(isset($_SESSION["select-site-selected"])){
        if($_SESSION["select-site-selected"]==0){
            unset($_SESSION["select-site-selected"]);
        }
    }

    if(count($sites)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_data_available}"));
        return false;
    }

    ksort($sites);
    $firstId=null;
    if(!isset($_SESSION["select-site-selected"])) {
        foreach ($sites as $id => $name) {
            if ($firstId === null) $firstId = $id;
            break;
        }
    }else{
        $firstId=$_SESSION["select-site-selected"];
    }



    $html[]="<div id='site-content-area' style='margin:10px'></div>";
    $html[]="<script>";
    if($firstId!==null){
        $html[]="LoadAjax('site-content-area','$page?site-content=yes&serviceid=$firstId');";
    }
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function site_content():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["serviceid"] ?? 0);
    if($serviceid<1){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{select_website}"));
        return false;
    }

    $array["{last_10_minutes}"]="$page?site-widgets=yes&serviceid=$serviceid&range=10min";
    $array["{today}"]="$page?site-widgets=yes&serviceid=$serviceid&range=today";
    $array["{yesterday}"]="$page?site-widgets=yes&serviceid=$serviceid&range=yesterday";
    $array["{this_week}"]="$page?site-widgets=yes&serviceid=$serviceid&range=week";
    $array["{this_month}"]="$page?site-widgets=yes&serviceid=$serviceid&range=month";
    $array["{this_year}"]="$page?site-widgets=yes&serviceid=$serviceid&range=year";

    echo $tpl->tabs_default($array);
    return true;
}

function build_time_filter(string $range, int $serviceid):array{
    $where="service_id=$serviceid";
    $table="rvproxy_monitor";
    $groupBy="zdate";

    switch($range){
        case "10min":
            $where.=" AND zdate=(SELECT MAX(zdate) FROM rvproxy_monitor WHERE service_id=$serviceid)";
            break;
        case "today":
            $where.=" AND zdate::date=CURRENT_DATE";
            break;
        case "yesterday":
            $where.=" AND zdate::date=(CURRENT_DATE - INTERVAL '1 day')";
            break;
        case "week":
            $where.=" AND zdate >= date_trunc('week',CURRENT_DATE)";
            break;
        case "month":
            $where.=" AND zdate >= date_trunc('month',CURRENT_DATE)";
            $table="rvproxy_monitor_hourly";
            break;
        case "year":
            $where.=" AND zdate >= date_trunc('year',CURRENT_DATE)";
            $table="rvproxy_monitor_hourly";
            break;
        default:
            $where.=" AND zdate=(SELECT MAX(zdate) FROM rvproxy_monitor WHERE service_id=$serviceid)";
    }

    return ["table"=>$table,"where"=>$where,"groupBy"=>$groupBy];
}

function site_widgets():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["serviceid"] ?? 0);
    $range=trim($_GET["range"] ?? "10min");
    if($serviceid<1){return false;}

    $sites=get_site_names();
    $siteName=isset($sites[$serviceid]) ? $sites[$serviceid] : "Service #$serviceid";

    $tf=build_time_filter($range,$serviceid);
    $q=new postgres_sql();

    $sql="SELECT
        SUM(total_requests) as total_requests,
        SUM(unique_clients) as unique_clients,
        SUM(count_legit) as count_legit,
        SUM(count_illegit) as count_illegit,
        SUM(count_server_err) as count_server_err
    FROM {$tf['table']}
    WHERE {$tf['where']}";

    $row=$q->mysqli_fetch_array($sql);
    $totalRqs=intval($row['total_requests'] ?? 0);
    $totalClients=intval($row['unique_clients'] ?? 0);
    $totalLegit=intval($row['count_legit'] ?? 0);
    $totalIllegit=intval($row['count_illegit'] ?? 0);
    $totalSrvErr=intval($row['count_server_err'] ?? 0);

    $w_rqs=$tpl->widget_style1("navy-bg","fa-duotone fa-solid fa-raindrops","{requests}",$tpl->FormatNumber($totalRqs));
    $w_clients=$tpl->widget_style1("lazur-bg",ico_computer,"{clients}",$tpl->FormatNumber($totalClients));
    $w_legit=$tpl->widget_style1("green-bg","fa-solid fa-check-circle","{legitimate}",$tpl->FormatNumber($totalLegit));
    $w_illegit=$tpl->widget_style1("yellow-bg","fa-solid fa-shield-exclamation","{illegitimate}",$tpl->FormatNumber($totalIllegit));
    $w_srverr=$tpl->widget_style1("red-bg","fa-solid fa-server","{server_errors}",$tpl->FormatNumber($totalSrvErr));

    if($totalRqs==0) $w_rqs=$tpl->widget_style1("gray-bg","fa-duotone fa-solid fa-raindrops","{requests}",0);
    if($totalClients==0) $w_clients=$tpl->widget_style1("gray-bg",ico_computer,"{clients}",0);
    if($totalLegit==0) $w_legit=$tpl->widget_style1("gray-bg","fa-solid fa-check-circle","{legitimate}",0);
    if($totalIllegit==0) $w_illegit=$tpl->widget_style1("gray-bg","fa-solid fa-shield-exclamation","{illegitimate}",0);
    if($totalSrvErr==0) $w_srverr=$tpl->widget_style1("gray-bg","fa-solid fa-server","{server_errors}",0);

    $html[]="<div style='margin:5px'>";
    $html[]="<table style='width:100%'><tr>";
    $html[]="<td style='width:20%'>$w_rqs</td>";
    $html[]="<td style='width:20%;padding-left:5px'>$w_clients</td>";
    $html[]="<td style='width:20%;padding-left:5px'>$w_legit</td>";
    $html[]="<td style='width:20%;padding-left:5px'>$w_illegit</td>";
    $html[]="<td style='width:20%;padding-left:5px'>$w_srverr</td>";
    $html[]="</tr></table>";

    if($range!="10min"){
        $html[]="<table style='width:100%;margin-top:10px'><tr>";
        $html[]="<td style='width:50%;vertical-align:top;padding:5px'>";
        $html[]="<div class='ibox'><div class='ibox-title'><h5>{requests} & {clients}</h5></div>";
        $html[]="<div class='ibox-content'><canvas id='site-line-rqs-$serviceid' height='120'></canvas></div></div>";
        $html[]="</td>";
        $html[]="<td style='width:50%;vertical-align:top;padding:5px'>";
        $html[]="<div class='ibox'><div class='ibox-title'><h5>{traffic} {flow}</h5></div>";
        $html[]="<div class='ibox-content'><canvas id='site-line-flows-$serviceid' height='120'></canvas></div></div>";
        $html[]="</td>";
        $html[]="</tr></table>";
    }

    $html[]="<table style='width:100%;margin-top:10px'><tr>";
    $html[]="<td style='width:50%;vertical-align:top;padding:5px'>";
    $html[]="<div class='ibox'><div class='ibox-title'><h5>{requests} {distribution}</h5></div>";
    $html[]="<div class='ibox-content' style='height:280px'><canvas id='site-pie-rqs-$serviceid'></canvas></div></div>";
    $html[]="</td>";
    $html[]="<td style='width:50%;vertical-align:top;padding:5px'>";
    $html[]="<div class='ibox'><div class='ibox-title'><h5>{errors} {distribution}</h5></div>";
    $html[]="<div class='ibox-content' style='height:280px'><canvas id='site-pie-err-$serviceid'></canvas></div></div>";
    $html[]="</td>";
    $html[]="</tr></table>";

    $html[]="<div style='margin-top:10px'>";
    $html[]="<div class='ibox'><div class='ibox-title'><h5>HTTP {status} {details}</h5></div>";
    $html[]="<div class='ibox-content'><div id='site-table-area-$serviceid'></div></div></div>";
    $html[]="</div>";

    $html[]="</div>";

    $html[]="<script>";
    $html[]="Loadjs('$page?site-pie-js=yes&serviceid=$serviceid&range=$range');";
    if($range!="10min"){
        $html[]="Loadjs('$page?site-trends-js=yes&serviceid=$serviceid&range=$range');";
    }
    $html[]="LoadAjax('site-table-area-$serviceid','$page?site-table=yes&serviceid=$serviceid&range=$range');";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function site_pie_js():bool{
    header("content-type: application/x-javascript");
    $serviceid=intval($_GET["serviceid"] ?? 0);
    $range=trim($_GET["range"] ?? "10min");
    if($serviceid<1){echo "console.error('no serviceid');";return false;}

    $tf=build_time_filter($range,$serviceid);
    $q=new postgres_sql();

    $sql="SELECT
        SUM(count_legit) as count_legit,
        SUM(count_illegit) as count_illegit,
        SUM(count_server_err) as count_server_err,
        SUM(count_200) as c200,SUM(count_201) as c201,SUM(count_202) as c202,SUM(count_204) as c204,
        SUM(count_301) as c301,SUM(count_302) as c302,SUM(count_304) as c304,SUM(count_307) as c307,SUM(count_308) as c308,
        SUM(count_400) as c400,SUM(count_401) as c401,SUM(count_403) as c403,SUM(count_404) as c404,
        SUM(count_405) as c405,SUM(count_429) as c429,SUM(count_444) as c444,SUM(count_499) as c499,SUM(count_408) as c408,
        SUM(count_500) as c500,SUM(count_502) as c502,SUM(count_503) as c503,SUM(count_504) as c504
    FROM {$tf['table']}
    WHERE {$tf['where']}";

    $row=$q->mysqli_fetch_array($sql);
    if(!$row){echo "console.error('no data');";return false;}

    $legit=intval($row['count_legit'] ?? 0);
    $illegit=intval($row['count_illegit'] ?? 0);
    $srverr=intval($row['count_server_err'] ?? 0);

    $rqs_labels=[];$rqs_data=[];
    if($legit>0){$rqs_labels[]="Legitimate";$rqs_data[]=$legit;}
    if($illegit>0){$rqs_labels[]="Illegitimate";$rqs_data[]=$illegit;}
    if($srverr>0){$rqs_labels[]="Server Errors";$rqs_data[]=$srverr;}

    $err_map=[
        '400'=>intval($row['c400'] ?? 0),'401'=>intval($row['c401'] ?? 0),
        '403'=>intval($row['c403'] ?? 0),'404'=>intval($row['c404'] ?? 0),
        '405'=>intval($row['c405'] ?? 0),'408'=>intval($row['c408'] ?? 0),
        '429'=>intval($row['c429'] ?? 0),'444'=>intval($row['c444'] ?? 0),
        '499'=>intval($row['c499'] ?? 0),
        '500'=>intval($row['c500'] ?? 0),'502'=>intval($row['c502'] ?? 0),
        '503'=>intval($row['c503'] ?? 0),'504'=>intval($row['c504'] ?? 0),
    ];
    $err_labels=[];$err_data=[];
    foreach($err_map as $code=>$val){
        if($val>0){$err_labels[]="HTTP $code";$err_data[]=$val;}
    }

    $piOpts=json_encode([
        'responsive'=>true,'maintainAspectRatio'=>false,
        'plugins'=>['legend'=>['position'=>'right','labels'=>['boxWidth'=>12,'font'=>['size'=>11]]]]
    ]);
    $errColors=json_encode(['#f8ac59','#ed5565','#ab7df6','#1c84c6','#676a6c','#23c6c8','#e74c3c','#c0392b','#e67e22','#2ecc71','#3498db','#9b59b6','#34495e']);

    $js=[];
    $js[]="(function(){";
    $js[]="var piOpts=$piOpts;";
    $js[]="var errColors=$errColors;";

    $js[]="try{ var el=document.getElementById('site-pie-rqs-$serviceid');";
    if(count($rqs_data)==0){
        $js[]="if(el){new Chart(el,{type:'doughnut',data:{labels:['No Data'],datasets:[{data:[1],backgroundColor:['#e0e0e0']}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{enabled:false},title:{display:true,text:'No Data',font:{size:14},color:'#676a6c'}}}})}";
    }else{
        $js[]="if(el){new Chart(el,{type:'doughnut',data:{labels:".json_encode($rqs_labels).",datasets:[{data:".json_encode($rqs_data).",backgroundColor:['#1ab394','#f8ac59','#ed5565']}]},options:piOpts})}";
    }
    $js[]="}catch(e){console.error('site-pie-rqs',e);}";

    $js[]="try{ var el=document.getElementById('site-pie-err-$serviceid');";
    if(count($err_data)==0){
        $js[]="if(el){new Chart(el,{type:'doughnut',data:{labels:['No Errors'],datasets:[{data:[1],backgroundColor:['#1ab394']}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{enabled:false},title:{display:true,text:'No Errors',font:{size:14},color:'#1ab394'}}}})}";
    }else{
        $js[]="if(el){new Chart(el,{type:'doughnut',data:{labels:".json_encode($err_labels).",datasets:[{data:".json_encode($err_data).",backgroundColor:errColors}]},options:piOpts})}";
    }
    $js[]="}catch(e){console.error('site-pie-err',e);}";

    $js[]="})();";
    echo implode("\n",$js);
    return true;
}

function site_trends_js():bool{
    header("content-type: application/x-javascript");
    $serviceid=intval($_GET["serviceid"] ?? 0);
    $range=trim($_GET["range"] ?? "today");
    if($serviceid<1){echo "console.error('no serviceid');";return false;}

    $tf=build_time_filter($range,$serviceid);
    $q=new postgres_sql();

    $dateFmt="m-d H:i";
    if($range=="month" || $range=="year") $dateFmt="Y-m-d H:i";

    $sql="SELECT json_agg(sub ORDER BY zdate ASC) as rows FROM (
        SELECT zdate,total_requests,unique_clients,count_legit,count_illegit,count_server_err
        FROM {$tf['table']}
        WHERE {$tf['where']}
    ) sub";

    $agg=$q->mysqli_fetch_array($sql);
    $rows=[];
    if($agg && isset($agg['rows'])){$rows=json_decode($agg['rows'],true) ?? [];}

    $labels=[];$rqs=[];$clients=[];$legit=[];$illegit=[];$errors=[];
    foreach($rows as $row){
        $labels[]=date($dateFmt,strtotime($row['zdate']));
        $rqs[]=intval($row['total_requests']);
        $clients[]=intval($row['unique_clients']);
        $legit[]=intval($row['count_legit']);
        $illegit[]=intval($row['count_illegit']);
        $errors[]=intval($row['count_server_err']);
    }

    $labelsJson=json_encode($labels);
    $js=[];
    $js[]="(function(){";

    $js[]="try{ var el=document.getElementById('site-line-rqs-$serviceid');";
    $js[]="if(el){new Chart(el,{type:'line',data:{";
    $js[]="labels:$labelsJson,";
    $js[]="datasets:[";
    $js[]="{label:'Requests',data:".json_encode($rqs).",borderColor:'#1ab394',backgroundColor:'rgba(26,179,148,0.1)',fill:true,tension:0.3,pointRadius:2},";
    $js[]="{label:'Clients',data:".json_encode($clients).",borderColor:'#1c84c6',backgroundColor:'rgba(28,132,198,0.1)',fill:true,tension:0.3,pointRadius:2,yAxisID:'y1'}";
    $js[]="]},options:{responsive:true,interaction:{mode:'index',intersect:false},";
    $js[]="scales:{y:{type:'linear',position:'left',title:{display:true,text:'Requests'},beginAtZero:true},";
    $js[]="y1:{type:'linear',position:'right',grid:{drawOnChartArea:false},title:{display:true,text:'Clients'},beginAtZero:true}},";
    $js[]="plugins:{legend:{position:'top'}}}});";
    $js[]="}}catch(e){console.error('site-line-rqs',e);}";

    $js[]="try{ var el=document.getElementById('site-line-flows-$serviceid');";
    $js[]="if(el){new Chart(el,{type:'line',data:{";
    $js[]="labels:$labelsJson,";
    $js[]="datasets:[";
    $js[]="{label:'Legitimate',data:".json_encode($legit).",borderColor:'#1ab394',backgroundColor:'rgba(26,179,148,0.15)',fill:true,tension:0.3,pointRadius:2},";
    $js[]="{label:'Illegitimate',data:".json_encode($illegit).",borderColor:'#f8ac59',backgroundColor:'rgba(248,172,89,0.15)',fill:true,tension:0.3,pointRadius:2},";
    $js[]="{label:'Server Errors',data:".json_encode($errors).",borderColor:'#ed5565',backgroundColor:'rgba(237,85,101,0.15)',fill:true,tension:0.3,pointRadius:2}";
    $js[]="]},options:{responsive:true,interaction:{mode:'index',intersect:false},";
    $js[]="scales:{y:{beginAtZero:true}},";
    $js[]="plugins:{legend:{position:'top'}}}});";
    $js[]="}}catch(e){console.error('site-line-flows',e);}";

    $js[]="})();";
    echo implode("\n",$js);
    return true;
}

function site_table():bool{
    $tpl=new template_admin();
    $serviceid=intval($_GET["serviceid"] ?? 0);
    $range=trim($_GET["range"] ?? "10min");
    if($serviceid<1){return false;}

    $tf=build_time_filter($range,$serviceid);
    $q=new postgres_sql();

    $sql="SELECT
        SUM(count_200) as c200,SUM(count_201) as c201,SUM(count_202) as c202,SUM(count_204) as c204,
        SUM(count_301) as c301,SUM(count_302) as c302,SUM(count_304) as c304,SUM(count_307) as c307,SUM(count_308) as c308,
        SUM(count_400) as c400,SUM(count_401) as c401,SUM(count_403) as c403,SUM(count_404) as c404,
        SUM(count_405) as c405,SUM(count_429) as c429,SUM(count_444) as c444,SUM(count_499) as c499,SUM(count_408) as c408,
        SUM(count_500) as c500,SUM(count_502) as c502,SUM(count_503) as c503,SUM(count_504) as c504
    FROM {$tf['table']}
    WHERE {$tf['where']}";

    $row=$q->mysqli_fetch_array($sql);
    if(!$row){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data_available}"));
        return false;
    }

    $codes=[
        ['200','OK','2xx'],['201','Created','2xx'],['202','Accepted','2xx'],['204','No Content','2xx'],
        ['301','Moved Permanently','3xx'],['302','Found','3xx'],['304','Not Modified','3xx'],['307','Temporary Redirect','3xx'],['308','Permanent Redirect','3xx'],
        ['400','Bad Request','4xx'],['401','Unauthorized','4xx'],['403','Forbidden','4xx'],['404','Not Found','4xx'],
        ['405','Method Not Allowed','4xx'],['408','Request Timeout','4xx'],['429','Too Many Requests','4xx'],['444','No Response','4xx'],['499','Client Closed','4xx'],
        ['500','Internal Server Error','5xx'],['502','Bad Gateway','5xx'],['503','Service Unavailable','5xx'],['504','Gateway Timeout','5xx'],
    ];

    $t=time();
    $html[]="<table id='table-$t' class='footable table table-stripped' data-page-size='50'>";
    $html[]="<thead><tr>";
    $html[]="<th data-sortable=true data-type='text'>HTTP {status}</th>";
    $html[]="<th data-sortable=true data-type='text'>{description}</th>";
    $html[]="<th data-sortable=true data-type='text'>{category}</th>";
    $html[]="<th data-sortable=true data-type='number'>{count}</th>";
    $html[]="<th data-sortable=false style='width:30%'>&nbsp;</th>";
    $html[]="</tr></thead><tbody>";

    $TRCLASS=null;
    $maxVal=1;
    foreach($codes as $c){
        $v=intval($row['c'.$c[0]] ?? 0);
        if($v>$maxVal) $maxVal=$v;
    }

    foreach($codes as $c){
        $code=$c[0];$desc=$c[1];$cat=$c[2];
        $val=intval($row['c'.$code] ?? 0);
        if($val==0) continue;

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $badgeColor="#1ab394";
        if($cat=="3xx") $badgeColor="#1c84c6";
        if($cat=="4xx") $badgeColor="#f8ac59";
        if($cat=="5xx") $badgeColor="#ed5565";

        $pct=round(($val/$maxVal)*100);
        $bar="<div style='background:#f5f5f5;border-radius:3px;height:18px;width:100%'>";
        $bar.="<div style='background:$badgeColor;height:18px;border-radius:3px;width:{$pct}%;min-width:2px'></div></div>";

        $badge="<span class='badge' style='background:$badgeColor;color:#fff'>$cat</span>";
        $valFmt=number_format($val);

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td><strong>$code</strong></td>";
        $html[]="<td>$desc</td>";
        $html[]="<td>$badge</td>";
        $html[]="<td>$valFmt</td>";
        $html[]="<td>$bar</td>";
        $html[]="</tr>";
    }

    $title="{APP_NGINX} {statistics} - {websites}";
    $sitename="{websites}: {select}";
    $sites=get_site_names();
    if(isset($sites[$serviceid])){
        $title="{APP_NGINX} {statistics} - $sites[$serviceid]";
        $sitename=$sites[$serviceid];
    }
    $page=CurrentPageName();
    $TINY_ARRAY["TITLE"]=$title;
    $TINY_ARRAY["ICO"]="fa-duotone fa-solid fa-chart-mixed";
    $TINY_ARRAY["EXPL"]="{statistics_nginx_site_explain}";

    $topbuttons[] = array("Loadjs('$page?select-site-js=$serviceid')", ico_earth, $sitename);

    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="</tbody>";
    $html[]="<tfoot><tr><td colspan='5'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $html[]="</table>";
    $html[]="<script>NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function(){ $('#table-$t').footable({";
    $html[]="\"sorting\":{\"enabled\":true},\"paging\":{\"size\":50}";
    $html[]="});});$headsjs</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
