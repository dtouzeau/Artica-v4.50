<?php
/**
 * Nginx Reverse Proxy - Realtime Client Metrics
 * Displays real-time client connections per service from ConnecsMonitor
 *
 * API Endpoint Used:
 * - GET /metrics/realtime/clients/{serviceid} via REST_API_NGINX()
 */
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
if(isset($_GET["LoadAsnInfos"])){LoadAsnInfos();exit;}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["charts-tab"])){charts_tab();exit;}
if(isset($_GET["chart-data-js"])){chart_data_js();exit;}
if(isset($_GET["top-widgets"])){top_widgets();exit;}
if(isset($_GET["clients-table"])){clients_table();exit;}
if(isset($_GET["asn-tab"])){asn_tab();exit;}
if(isset($_GET["asn-chart-js"])){asn_chart_data_js();exit;}
if(isset($_GET["asn-table"])){asn_table();exit;}
if(isset($_GET["fill-asn"])){fill_asn();exit;}
js();

function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["serviceid"] ?? 0);

    $servicename="Unknown";
    if($serviceid>0){
        $sock=new socksngix($serviceid);
        $servicename=$sock->GetServiceName() ?: "Unknown";
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/metrics/realtime/clients/$serviceid"));

    $totalReqs=0;
    $uniqueClients=0;
    if($json && isset($json->status) && $json->status && is_array($json->clients)){
        foreach($json->clients as $c){
            $totalReqs+=intval($c->total ?? 0);
        }
        $uniqueClients=count($json->clients);
    }

    $totalReqs=$tpl->FormatNumber($totalReqs);
    $title="{active_requests}: $servicename - $totalReqs {requests}, $uniqueClients {members}";
    return $tpl->js_dialog9($title,"$page?start=yes&serviceid=$serviceid",1100);
}

function start():bool{
    $page=CurrentPageName();
    $serviceid=intval($_GET["serviceid"] ?? 0);
    echo "<div id='nginx-ar-progress'></div>";
    echo "<script>LoadAjax('nginx-ar-progress','$page?tabs=yes&serviceid=$serviceid');</script>";
    return true;
}

function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["serviceid"] ?? 0);
    $group["{charts}"]="$page?charts-tab=yes&serviceid=$serviceid";
    $group["{members}"]="$page?clients-table=yes&serviceid=$serviceid";
    $group["ASN"]="$page?asn-tab=yes&serviceid=$serviceid";
    echo $tpl->tabs_default($group);
    return true;
}

function top_widgets():bool{
    $tpl=new template_admin();
    $serviceid=intval($_GET["serviceid"] ?? 0);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/metrics/realtime/clients/$serviceid"));

    $totalReqs=0;
    $uniqueClients=0;
    $totalRps=0;
    $elapsedSec=0;
    $windowSec=0;

    if($json && isset($json->status) && $json->status){
        $elapsedSec=intval($json->elapsed_sec ?? 0);
        $windowSec=intval($json->window_duration_sec ?? 600);
        if(is_array($json->clients)){
            $uniqueClients=count($json->clients);
            foreach($json->clients as $c){
                $totalReqs+=intval($c->total ?? 0);
                $totalRps+=floatval($c->req_per_sec ?? 0);
            }
        }
    }

    $elapsedMin=round($elapsedSec/60,1);
    $windowMin=round($windowSec/60);
    $windowText="{$elapsedMin}m / {$windowMin}m";

    $widget_requests=base64_encode($tpl->_ENGINE_parse_body(
        $tpl->widget_style1("navy-bg","fas fa-exchange-alt","{requests}",$tpl->FormatNumber($totalReqs))
    ));
    $widget_clients=base64_encode($tpl->_ENGINE_parse_body(
        $tpl->widget_style1("lazur-bg","fas fa-users","{members}",$tpl->FormatNumber($uniqueClients))
    ));
    $widget_rps=base64_encode($tpl->_ENGINE_parse_body(
        $tpl->widget_style1("blue-bg","fas fa-tachometer-alt","Req/sec",number_format($totalRps,1))
    ));
    $widget_window=base64_encode($tpl->_ENGINE_parse_body(
        $tpl->widget_style1("yellow-bg","fas fa-clock","{window}",$windowText)
    ));

    $html[]="if(document.getElementById('widget-requests')){";
    $html[]="  \$('#widget-requests').html(base64_decode('$widget_requests'));";
    $html[]="  \$('#widget-clients').html(base64_decode('$widget_clients'));";
    $html[]="  \$('#widget-rps').html(base64_decode('$widget_rps'));";
    $html[]="}";

    header("content-type: application/x-javascript");
    echo implode("\n",$html);
    return true;
}

function charts_tab():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $serviceid=intval($_GET["serviceid"] ?? 0);

    // Widgets row
    $html[]="<div id='nginx-ar-widgets'>";
    $html[]="<table style='width:100%;margin-top:-5px'><tbody>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'><span id='widget-requests'></span></td>";
    $html[]="<td style='width:33%;padding-left:5px'><span id='widget-clients'></span></td>";
    $html[]="<td style='width:33%;padding-left:5px'><span id='widget-rps'></span></td>";
    $html[]="</tr>";
    $html[]="</tbody></table>";
    $html[]="</div>";

    // Charts row
    $html[]="<div class='row' style='margin-top:20px'>";
    $html[]="<div class='col-md-12'>";
    $html[]="<div class='ibox'>";
    $html[]="<div class='ibox-title'>";
    $html[]="<h5><i class='fas fa-chart-pie'></i> {statistics}</h5>";
    $html[]="</div>";
    $html[]="<div class='ibox-content'>";

    // Two doughnut charts side by side
    $html[]="<div class='row'>";

    // Doughnut: Top Clients by Requests
    $html[]="<div class='col-md-6'>";
    $html[]="<h4 style='text-align:center'><i class='fas fa-users'></i> {top} {members} ({requests})</h4>";
    $html[]="<div style='height:350px;position:relative'>";
    $html[]="<canvas id='chart-clients-doughnut'></canvas>";
    $html[]="</div>";
    $html[]="</div>";

    // Doughnut: Status Code Distribution
    $html[]="<div class='col-md-6'>";
    $html[]="<h4 style='text-align:center'><i class='fas fa-chart-pie'></i> HTTP {status}</h4>";
    $html[]="<div style='height:350px;position:relative'>";
    $html[]="<canvas id='chart-status-doughnut'></canvas>";
    $html[]="</div>";
    $html[]="</div>";

    $html[]="</div>"; // row

    // Horizontal bar chart
    $html[]="<div class='row' style='margin-top:30px'>";
    $html[]="<div class='col-md-12'>";
    $html[]="<h4 style='text-align:center'><i class='fas fa-chart-bar'></i> {top} {members} (Req/sec)</h4>";
    $html[]="<div style='height:300px;position:relative'>";
    $html[]="<canvas id='chart-rps-bar'></canvas>";
    $html[]="</div>";
    $html[]="</div>";
    $html[]="</div>"; // row

    $html[]="</div>"; // ibox-content
    $html[]="</div>"; // ibox
    $html[]="</div>"; // col
    $html[]="</div>"; // row

    // Widget auto-refresh and chart loading
    $jsRefresh=$tpl->RefreshInterval_Loadjs("nginx-ar-widgets",$page,"top-widgets=yes&serviceid=$serviceid",10);

    $html[]="<script>";
    $html[]="$jsRefresh";
    $html[]="var clientsDoughnut=null;";
    $html[]="var statusDoughnut=null;";
    $html[]="var rpsBarChart=null;";
    $html[]="Loadjs('$page?chart-data-js=yes&serviceid=$serviceid');";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

function chart_data_js():bool{
    header("content-type: application/x-javascript");
    $serviceid=intval($_GET["serviceid"] ?? 0);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/metrics/realtime/clients/$serviceid"));

    $clients=[];
    if($json && isset($json->status) && $json->status && is_array($json->clients)){
        $clients=$json->clients;
    }

    $js=[];

    // Color palette
    $js[]="var chartColors=[";
    $js[]="    '#36A2EB','#FF6384','#4BC0C0','#FF9F40','#9966FF',";
    $js[]="    '#FFCD56','#C9CBCF','#7BC225','#E74C3C','#3498DB'";
    $js[]="];";
    $js[]="";

    // Destroy existing charts
    $js[]="if(clientsDoughnut) clientsDoughnut.destroy();";
    $js[]="if(statusDoughnut) statusDoughnut.destroy();";
    $js[]="if(rpsBarChart) rpsBarChart.destroy();";
    $js[]="";

    // Sort clients by total descending, take top 10
    usort($clients,function($a,$b){
        return intval($b->total ?? 0)-intval($a->total ?? 0);
    });
    $top10=array_slice($clients,0,10);

    // Prepare top clients by requests
    $clientLabels=[];
    $clientTotals=[];
    foreach($top10 as $c){
        $clientLabels[]=$c->ip ?? 'unknown';
        $clientTotals[]=intval($c->total ?? 0);
    }

    // Aggregate status codes across all clients
    $statusAgg=[];
    foreach($clients as $c){
        if(isset($c->status_codes) && is_object($c->status_codes)){
            foreach($c->status_codes as $code=>$count){
                $bucket=intval($code);
                if($bucket>=200 && $bucket<300) $key="2xx";
                elseif($bucket>=300 && $bucket<400) $key="3xx";
                elseif($bucket>=400 && $bucket<500) $key="4xx";
                elseif($bucket>=500 && $bucket<600) $key="5xx";
                else $key="other";
                $statusAgg[$key]=($statusAgg[$key] ?? 0)+intval($count);
            }
        }
    }

    $statusLabels=[];
    $statusValues=[];
    $statusColors=[];
    $colorMap=["2xx"=>"#1ab394","3xx"=>"#23c6c8","4xx"=>"#f8ac59","5xx"=>"#ed5565","other"=>"#C9CBCF"];
    foreach(["2xx","3xx","4xx","5xx","other"] as $k){
        if(isset($statusAgg[$k]) && $statusAgg[$k]>0){
            $statusLabels[]=$k;
            $statusValues[]=$statusAgg[$k];
            $statusColors[]=$colorMap[$k];
        }
    }

    // Sort clients by req_per_sec for bar chart, take top 10
    usort($clients,function($a,$b){
        return floatval($b->req_per_sec ?? 0)<=>floatval($a->req_per_sec ?? 0);
    });
    $top10rps=array_slice($clients,0,10);

    $rpsLabels=[];
    $rpsValues=[];
    foreach($top10rps as $c){
        $rpsLabels[]=$c->ip ?? 'unknown';
        $rpsValues[]=round(floatval($c->req_per_sec ?? 0),2);
    }

    $clientLabelsJson=json_encode($clientLabels);
    $clientTotalsJson=json_encode($clientTotals);
    $statusLabelsJson=json_encode($statusLabels);
    $statusValuesJson=json_encode($statusValues);
    $statusColorsJson=json_encode($statusColors);
    $rpsLabelsJson=json_encode($rpsLabels);
    $rpsValuesJson=json_encode($rpsValues);

    // Clients doughnut chart
    $js[]="var clientLabels=$clientLabelsJson;";
    $js[]="var clientTotals=$clientTotalsJson;";
    $js[]="";
    $js[]="if(clientLabels.length>0){";
    $js[]="    var ctx1=document.getElementById('chart-clients-doughnut').getContext('2d');";
    $js[]="    clientsDoughnut=new Chart(ctx1,{";
    $js[]="        type:'doughnut',";
    $js[]="        data:{";
    $js[]="            labels:clientLabels,";
    $js[]="            datasets:[{";
    $js[]="                data:clientTotals,";
    $js[]="                backgroundColor:chartColors";
    $js[]="            }]";
    $js[]="        },";
    $js[]="        options:{";
    $js[]="            responsive:true,";
    $js[]="            maintainAspectRatio:false,";
    $js[]="            plugins:{";
    $js[]="                legend:{position:'right',labels:{boxWidth:12,font:{size:11}}},";
    $js[]="                tooltip:{";
    $js[]="                    callbacks:{";
    $js[]="                        label:function(ctx){";
    $js[]="                            return ctx.label+': '+ctx.raw.toLocaleString()+' requests';";
    $js[]="                        }";
    $js[]="                    }";
    $js[]="                }";
    $js[]="            }";
    $js[]="        }";
    $js[]="    });";
    $js[]="} else {";
    $js[]="    document.getElementById('chart-clients-doughnut').parentNode.innerHTML='<div class=\"alert alert-info\" style=\"margin-top:100px;text-align:center\">No data available</div>';";
    $js[]="}";
    $js[]="";

    // Status codes doughnut chart
    $js[]="var statusLabels=$statusLabelsJson;";
    $js[]="var statusValues=$statusValuesJson;";
    $js[]="var statusColors=$statusColorsJson;";
    $js[]="";
    $js[]="if(statusLabels.length>0){";
    $js[]="    var ctx2=document.getElementById('chart-status-doughnut').getContext('2d');";
    $js[]="    statusDoughnut=new Chart(ctx2,{";
    $js[]="        type:'doughnut',";
    $js[]="        data:{";
    $js[]="            labels:statusLabels,";
    $js[]="            datasets:[{";
    $js[]="                data:statusValues,";
    $js[]="                backgroundColor:statusColors";
    $js[]="            }]";
    $js[]="        },";
    $js[]="        options:{";
    $js[]="            responsive:true,";
    $js[]="            maintainAspectRatio:false,";
    $js[]="            plugins:{";
    $js[]="                legend:{position:'right',labels:{boxWidth:12,font:{size:11}}},";
    $js[]="                tooltip:{";
    $js[]="                    callbacks:{";
    $js[]="                        label:function(ctx){";
    $js[]="                            return 'HTTP '+ctx.label+': '+ctx.raw.toLocaleString();";
    $js[]="                        }";
    $js[]="                    }";
    $js[]="                }";
    $js[]="            }";
    $js[]="        }";
    $js[]="    });";
    $js[]="} else {";
    $js[]="    document.getElementById('chart-status-doughnut').parentNode.innerHTML='<div class=\"alert alert-info\" style=\"margin-top:100px;text-align:center\">No data available</div>';";
    $js[]="}";
    $js[]="";

    // Req/sec horizontal bar chart
    $js[]="var rpsLabels=$rpsLabelsJson;";
    $js[]="var rpsValues=$rpsValuesJson;";
    $js[]="";
    $js[]="if(rpsLabels.length>0){";
    $js[]="    var ctx3=document.getElementById('chart-rps-bar').getContext('2d');";
    $js[]="    rpsBarChart=new Chart(ctx3,{";
    $js[]="        type:'bar',";
    $js[]="        data:{";
    $js[]="            labels:rpsLabels,";
    $js[]="            datasets:[{";
    $js[]="                label:'Req/sec',";
    $js[]="                data:rpsValues,";
    $js[]="                backgroundColor:'#36A2EB',";
    $js[]="                borderColor:'#2980B9',";
    $js[]="                borderWidth:1";
    $js[]="            }]";
    $js[]="        },";
    $js[]="        options:{";
    $js[]="            indexAxis:'y',";
    $js[]="            responsive:true,";
    $js[]="            maintainAspectRatio:false,";
    $js[]="            plugins:{";
    $js[]="                legend:{display:false}";
    $js[]="            },";
    $js[]="            scales:{";
    $js[]="                x:{beginAtZero:true}";
    $js[]="            }";
    $js[]="        }";
    $js[]="    });";
    $js[]="} else {";
    $js[]="    document.getElementById('chart-rps-bar').parentNode.innerHTML='<div class=\"alert alert-info\" style=\"margin-top:80px;text-align:center\">No data available</div>';";
    $js[]="}";

    echo implode("\n",$js);
    return true;
}

function clients_table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    $serviceid=intval($_GET["serviceid"] ?? 0);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/metrics/realtime/clients/$serviceid"));

    $clients=[];
    if($json && isset($json->status) && $json->status && is_array($json->clients)){
        $clients=$json->clients;
    }

    // Sort by total descending
    usort($clients,function($a,$b){
        return intval($b->total ?? 0)-intval($a->total ?? 0);
    });

    $html[]="<table id='table-$t' class='footable table table-stripped' data-page-size='100'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true data-type='text'>{ipaddr}</th>";
    $html[]="<th data-sortable=true data-type='number' style='text-align:right'>{requests}</th>";
    $html[]="<th data-sortable=true data-type='number' style='text-align:right'>Req/sec</th>";
    $html[]="<th data-sortable=true data-type='number' style='text-align:right'>HTTP 2xx</th>";
    $html[]="<th data-sortable=true data-type='number' style='text-align:right'>HTTP 3xx</th>";
    $html[]="<th data-sortable=true data-type='number' style='text-align:right'>HTTP 4xx</th>";
    $html[]="<th data-sortable=true data-type='number' style='text-align:right'>HTTP 5xx</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    $d=0;
    foreach($clients as $c){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ip=htmlspecialchars($c->ip ?? 'unknown',ENT_QUOTES,'UTF-8');
        $total=$tpl->FormatNumber(intval($c->total ?? 0));
        $rps=number_format(floatval($c->req_per_sec ?? 0),1);

        // Aggregate status codes into buckets
        $s2xx=0;$s3xx=0;$s4xx=0;$s5xx=0;
        if(isset($c->status_codes) && is_object($c->status_codes)){
            foreach($c->status_codes as $code=>$count){
                $bucket=intval($code);
                if($bucket>=200 && $bucket<300) $s2xx+=intval($count);
                elseif($bucket>=300 && $bucket<400) $s3xx+=intval($count);
                elseif($bucket>=400 && $bucket<500) $s4xx+=intval($count);
                elseif($bucket>=500 && $bucket<600) $s5xx+=intval($count);
            }
        }

        $badge2xx=$s2xx>0?"<span class='badge' style='background:#1ab394;color:#fff'>".$tpl->FormatNumber($s2xx)."</span>":"0";
        $badge3xx=$s3xx>0?"<span class='badge' style='background:#23c6c8;color:#fff'>".$tpl->FormatNumber($s3xx)."</span>":"0";
        $badge4xx=$s4xx>0?"<span class='badge' style='background:#f8ac59;color:#fff'>".$tpl->FormatNumber($s4xx)."</span>":"0";
        $badge5xx=$s5xx>0?"<span class='badge' style='background:#ed5565;color:#fff'>".$tpl->FormatNumber($s5xx)."</span>":"0";

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td style='width:1%'><i class='".ico_member."'></i></td>";
        $html[]="<td><strong>$ip</strong></td>";
        $html[]="<td style='text-align:right'>$total</td>";
        $html[]="<td style='text-align:right'>$rps</td>";
        $html[]="<td style='text-align:right'>$badge2xx</td>";
        $html[]="<td style='text-align:right'>$badge3xx</td>";
        $html[]="<td style='text-align:right'>$badge4xx</td>";
        $html[]="<td style='text-align:right'>$badge5xx</td>";
        $html[]="</tr>";
        $d++;
        if($d>500){
            break;
        }
    }

    $html[]="</tbody>";
    $html[]="<tfoot><tr><td colspan='8'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $html[]="</table>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function(){\$('#table-$t').footable({";
    $html[]="    \"filtering\":{\"enabled\":true},";
    $html[]="    \"sorting\":{\"enabled\":true},";
    $html[]="    \"paging\":{\"size\":{$GLOBALS["FOOTABLE_PSIZE"]}}";
    $html[]="});});</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

// ==================== ASN Tab ====================

function asn_lookup_info(string $asn):array{

    $LookupASNInfosCaches=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LookupASNInfosCaches"));
    if(!$LookupASNInfosCaches){
        $LookupASNInfosCaches=array();
    }
    if(isset($LookupASNInfosCaches[$asn])){
        return $LookupASNInfosCaches[$asn];
    }


    $result=array("asn"=>$asn,"name"=>"","country"=>"");
    if($asn==""){return $result;}
    $records=@dns_get_record("AS$asn.asn.cymru.com",DNS_TXT);
    if(!is_array($records) || count($records)==0){return $result;}
    $parts=explode("|",$records[0]["txt"] ?? "");
    if(count($parts)>=5){
        $result["country"]=trim($parts[1]);
        $result["name"]=trim($parts[4]);
    }elseif(count($parts)>=2){
        $result["country"]=trim($parts[1]);
    }
    $LookupASNInfosCaches[$asn]=$result;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LookupASNInfosCaches",base64_encode(serialize($LookupASNInfosCaches)));
    return $result;
}

function asn_tab():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $serviceid=intval($_GET["serviceid"] ?? 0);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/metrics/realtime/asn/$serviceid"));

    $asnCount=0;
    $totalReqs=0;
    $totalIPs=0;
    if($json && isset($json->status) && $json->status && is_array($json->asns)){
        $asnCount=count($json->asns);
        foreach($json->asns as $a){
            $totalReqs+=intval($a->total_requests ?? 0);
            $totalIPs+=intval($a->unique_ips ?? 0);
        }
    }
    $elapsedSec=intval($json->elapsed_sec ?? 0);
    $elapsedMin=round($elapsedSec/60,1);

    // Widgets
    $html=array();
    $html[]="<table style='width:100%;margin-top:-5px'><tbody><tr>";
    $html[]="<td style='width:25%'>".$tpl->_ENGINE_parse_body(
        $tpl->widget_style1("navy-bg","fas fa-network-wired","ASNs",$tpl->FormatNumber($asnCount))
    )."</td>";
    $html[]="<td style='width:25%;padding-left:5px'>".$tpl->_ENGINE_parse_body(
        $tpl->widget_style1("lazur-bg","fas fa-exchange-alt","{requests}",$tpl->FormatNumber($totalReqs))
    )."</td>";
    $html[]="<td style='width:25%;padding-left:5px'>".$tpl->_ENGINE_parse_body(
        $tpl->widget_style1("blue-bg","fas fa-users","IPs",$tpl->FormatNumber($totalIPs))
    )."</td>";
    $html[]="<td style='width:25%;padding-left:5px'>".$tpl->_ENGINE_parse_body(
        $tpl->widget_style1("yellow-bg","fas fa-clock","{window}","{$elapsedMin} min")
    )."</td>";
    $html[]="</tr></tbody></table>";

    // Charts row
    $html[]="<div class='row' style='margin-top:20px'>";
    $html[]="<div class='col-md-12'>";
    $html[]="<div class='ibox'>";
    $html[]="<div class='ibox-title'><h5><i class='fas fa-chart-pie'></i> ASN {statistics}</h5></div>";
    $html[]="<div class='ibox-content'>";

    $html[]="<div class='row'>";

    // Doughnut: Top ASNs by Requests
    $html[]="<div class='col-md-6'>";
    $html[]="<h4 style='text-align:center'><i class='fas fa-network-wired'></i> {top} ASN ({requests})</h4>";
    $html[]="<div style='height:350px;position:relative'>";
    $html[]="<canvas id='chart-asn-doughnut'></canvas>";
    $html[]="</div></div>";

    // Stacked bar: Errors by ASN (4xx/5xx)
    $html[]="<div class='col-md-6'>";
    $html[]="<h4 style='text-align:center'><i class='fas fa-exclamation-triangle'></i> ASN {errors} (4xx/5xx)</h4>";
    $html[]="<div style='height:350px;position:relative'>";
    $html[]="<canvas id='chart-asn-errors-bar'></canvas>";
    $html[]="</div></div>";

    $html[]="</div>"; // row

    // Horizontal bar: Unique IPs per ASN
    $html[]="<div class='row' style='margin-top:30px'>";
    $html[]="<div class='col-md-12'>";
    $html[]="<h4 style='text-align:center'><i class='fas fa-users'></i> {top} ASN (IPs)</h4>";
    $html[]="<div style='height:300px;position:relative'>";
    $html[]="<canvas id='chart-asn-ips-bar'></canvas>";
    $html[]="</div></div></div>"; // col, row

    $html[]="</div></div></div></div>"; // ibox-content, ibox, col, row

    // ASN detail table
    $html[]="<div id='asn-table-container' style='margin-top:20px'></div>";

    $html[]="<script>";
    $html[]="var asnDoughnut=null;";
    $html[]="var asnErrorsBar=null;";
    $html[]="var asnIpsBar=null;";
    $html[]="Loadjs('$page?asn-chart-js=yes&serviceid=$serviceid');";
    $html[]="LoadAjax('asn-table-container','$page?asn-table=yes&serviceid=$serviceid');";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

function asn_chart_data_js():bool{
    header("content-type: application/x-javascript");
    $serviceid=intval($_GET["serviceid"] ?? 0);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/metrics/realtime/asn/$serviceid"));

    $asns=[];
    if($json && isset($json->status) && $json->status && is_array($json->asns)){
        $asns=$json->asns;
    }

    $js=[];
    $js[]="var chartColors=[";
    $js[]="    '#36A2EB','#FF6384','#4BC0C0','#FF9F40','#9966FF',";
    $js[]="    '#FFCD56','#C9CBCF','#7BC225','#E74C3C','#3498DB',";
    $js[]="    '#8E44AD','#2ECC71','#F39C12','#1ABC9C','#D35400'";
    $js[]="];";

    $js[]="if(asnDoughnut) asnDoughnut.destroy();";
    $js[]="if(asnErrorsBar) asnErrorsBar.destroy();";
    $js[]="if(asnIpsBar) asnIpsBar.destroy();";

    // Top 15 ASNs by requests (already sorted desc from API)
    $top=array_slice($asns,0,15);

    $doughnutLabels=[];
    $doughnutValues=[];
    foreach($top as $a){
        $asn=$a->asn ?? "";
        $info=asn_lookup_info($asn);
        $label="AS$asn";
        if($info["name"]!=""){$label="AS$asn (".mb_substr($info["name"],0,25).")";}
        $doughnutLabels[]=$label;
        $doughnutValues[]=intval($a->total_requests ?? 0);
    }

    // Errors: 4xx+5xx per ASN, sorted by error count
    $errorData=[];
    foreach($asns as $a){
        $asn=$a->asn ?? "";
        $e4xx=0;$e5xx=0;
        if(isset($a->statuses) && is_object($a->statuses)){
            foreach($a->statuses as $code=>$detail){
                $c=intval($code);
                if($c>=400 && $c<500) $e4xx+=intval($detail->requests ?? 0);
                elseif($c>=500 && $c<600) $e5xx+=intval($detail->requests ?? 0);
            }
        }
        if($e4xx+$e5xx>0){
            $errorData[]=["asn"=>$asn,"e4xx"=>$e4xx,"e5xx"=>$e5xx,"total"=>$e4xx+$e5xx];
        }
    }
    usort($errorData,function($a,$b){return $b["total"]-$a["total"];});
    $topErrors=array_slice($errorData,0,10);

    $errLabels=[];$err4xx=[];$err5xx=[];
    foreach($topErrors as $e){
        $info=asn_lookup_info($e["asn"]);
        $label="AS".$e["asn"];
        if($info["name"]!=""){$label.=" (".mb_substr($info["name"],0,20).")";}
        $errLabels[]=$label;
        $err4xx[]=$e["e4xx"];
        $err5xx[]=$e["e5xx"];
    }

    // Unique IPs per ASN, sorted by IP count
    $ipsSorted=$asns;
    usort($ipsSorted,function($a,$b){return intval($b->unique_ips ?? 0)-intval($a->unique_ips ?? 0);});
    $topIps=array_slice($ipsSorted,0,10);

    $ipsLabels=[];$ipsValues=[];
    foreach($topIps as $a){
        $asn=$a->asn ?? "";
        $info=asn_lookup_info($asn);
        $label="AS$asn";
        if($info["name"]!=""){$label.=" (".mb_substr($info["name"],0,20).")";}
        $ipsLabels[]=$label;
        $ipsValues[]=intval($a->unique_ips ?? 0);
    }

    $dLabelsJson=json_encode($doughnutLabels);
    $dValuesJson=json_encode($doughnutValues);
    $errLabelsJson=json_encode($errLabels);
    $err4xxJson=json_encode($err4xx);
    $err5xxJson=json_encode($err5xx);
    $ipsLabelsJson=json_encode($ipsLabels);
    $ipsValuesJson=json_encode($ipsValues);

    // === Doughnut: Top ASNs by requests ===
    $js[]="var dLabels=$dLabelsJson;";
    $js[]="var dValues=$dValuesJson;";
    $js[]="if(dLabels.length>0){";
    $js[]="    var ctx1=document.getElementById('chart-asn-doughnut').getContext('2d');";
    $js[]="    asnDoughnut=new Chart(ctx1,{";
    $js[]="        type:'doughnut',";
    $js[]="        data:{labels:dLabels,datasets:[{data:dValues,backgroundColor:chartColors}]},";
    $js[]="        options:{";
    $js[]="            responsive:true,maintainAspectRatio:false,";
    $js[]="            plugins:{";
    $js[]="                legend:{position:'right',labels:{boxWidth:12,font:{size:10}}},";
    $js[]="                tooltip:{callbacks:{label:function(ctx){return ctx.label+': '+ctx.raw.toLocaleString()+' requests';}}}";
    $js[]="            }";
    $js[]="        }";
    $js[]="    });";
    $js[]="} else {";
    $js[]="    document.getElementById('chart-asn-doughnut').parentNode.innerHTML='<div class=\"alert alert-info\" style=\"margin-top:100px;text-align:center\">No data available</div>';";
    $js[]="}";

    // === Stacked bar: Errors by ASN ===
    $js[]="var errLabels=$errLabelsJson;";
    $js[]="var err4xx=$err4xxJson;";
    $js[]="var err5xx=$err5xxJson;";
    $js[]="if(errLabels.length>0){";
    $js[]="    var ctx2=document.getElementById('chart-asn-errors-bar').getContext('2d');";
    $js[]="    asnErrorsBar=new Chart(ctx2,{";
    $js[]="        type:'bar',";
    $js[]="        data:{labels:errLabels,datasets:[";
    $js[]="            {label:'4xx',data:err4xx,backgroundColor:'#f8ac59',borderColor:'#e09b4b',borderWidth:1},";
    $js[]="            {label:'5xx',data:err5xx,backgroundColor:'#ed5565',borderColor:'#d44455',borderWidth:1}";
    $js[]="        ]},";
    $js[]="        options:{";
    $js[]="            indexAxis:'y',responsive:true,maintainAspectRatio:false,";
    $js[]="            plugins:{legend:{position:'top',labels:{boxWidth:12}}},";
    $js[]="            scales:{x:{stacked:true,beginAtZero:true},y:{stacked:true}}";
    $js[]="        }";
    $js[]="    });";
    $js[]="} else {";
    $js[]="    document.getElementById('chart-asn-errors-bar').parentNode.innerHTML='<div class=\"alert alert-info\" style=\"margin-top:100px;text-align:center\">No error data</div>';";
    $js[]="}";

    // === Horizontal bar: Unique IPs per ASN ===
    $js[]="var ipsLabels=$ipsLabelsJson;";
    $js[]="var ipsValues=$ipsValuesJson;";
    $js[]="if(ipsLabels.length>0){";
    $js[]="    var ctx3=document.getElementById('chart-asn-ips-bar').getContext('2d');";
    $js[]="    asnIpsBar=new Chart(ctx3,{";
    $js[]="        type:'bar',";
    $js[]="        data:{labels:ipsLabels,datasets:[{";
    $js[]="            label:'Unique IPs',data:ipsValues,backgroundColor:'#23c6c8',borderColor:'#1ba5a7',borderWidth:1";
    $js[]="        }]},";
    $js[]="        options:{";
    $js[]="            indexAxis:'y',responsive:true,maintainAspectRatio:false,";
    $js[]="            plugins:{legend:{display:false}},";
    $js[]="            scales:{x:{beginAtZero:true}}";
    $js[]="        }";
    $js[]="    });";
    $js[]="} else {";
    $js[]="    document.getElementById('chart-asn-ips-bar').parentNode.innerHTML='<div class=\"alert alert-info\" style=\"margin-top:80px;text-align:center\">No data available</div>';";
    $js[]="}";

    echo implode("\n",$js);
    return true;
}

function asn_table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    $serviceid=intval($_GET["serviceid"] ?? 0);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/metrics/realtime/asn/$serviceid"));

    $asns=[];
    if($json && isset($json->status) && $json->status && is_array($json->asns)){
        $asns=$json->asns;
    }

    $html=array();
    $html[]="<div class='ibox'>";
    $html[]="<div class='ibox-title'><h5><i class='fas fa-table'></i> ASN {details}</h5></div>";
    $html[]="<div class='ibox-content'>";

    $html[]="<table id='table-asn-$t' class='footable table table-stripped' data-page-size='100'>";
    $html[]="<thead><tr>";
    $html[]="<th data-sortable=true data-type='text'>ASN</th>";
    $html[]="<th data-sortable=true data-type='text'>{description}</th>";
    $html[]="<th data-sortable=true data-type='text'>{country}</th>";
    $html[]="<th data-sortable=true data-type='number' style='text-align:right'>{requests}</th>";
    $html[]="<th data-sortable=true data-type='number' style='text-align:right'>IPs</th>";
    $html[]="<th data-sortable=true data-type='number' style='text-align:right'>2xx</th>";
    $html[]="<th data-sortable=true data-type='number' style='text-align:right'>3xx</th>";
    $html[]="<th data-sortable=true data-type='number' style='text-align:right'>4xx</th>";
    $html[]="<th data-sortable=true data-type='number' style='text-align:right'>5xx</th>";
    $html[]="</tr></thead>";
    $html[]="<tbody>";

    $TRCLASS=null;
    $d=0;$z=0;
    foreach($asns as $a){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $asn=$a->asn ?? "";
        $total=intval($a->total_requests ?? 0);
        $ips=intval($a->unique_ips ?? 0);

        $z++;

        $s2xx=0;$s3xx=0;$s4xx=0;$s5xx=0;
        if(isset($a->statuses) && is_object($a->statuses)){
            foreach($a->statuses as $code=>$detail){
                $c=intval($code);
                $cnt=intval($detail->requests ?? 0);
                if($c>=200 && $c<300) $s2xx+=$cnt;
                elseif($c>=300 && $c<400) $s3xx+=$cnt;
                elseif($c>=400 && $c<500) $s4xx+=$cnt;
                elseif($c>=500 && $c<600) $s5xx+=$cnt;
            }
        }

        $badge2xx=$s2xx>0?"<span class='badge' style='background:#1ab394;color:#fff'>".$tpl->FormatNumber($s2xx)."</span>":"0";
        $badge3xx=$s3xx>0?"<span class='badge' style='background:#23c6c8;color:#fff'>".$tpl->FormatNumber($s3xx)."</span>":"0";
        $badge4xx=$s4xx>0?"<span class='badge' style='background:#f8ac59;color:#fff'>".$tpl->FormatNumber($s4xx)."</span>":"0";
        $badge5xx=$s5xx>0?"<span class='badge' style='background:#ed5565;color:#fff'>".$tpl->FormatNumber($s5xx)."</span>":"0";
        $ico_refres=ico_refresh_animate;
        $name="<span id='asname-$asn'><i class='$ico_refres'></i></span>";
        $country="<span id='ascountry-$asn'><i class='$ico_refres'></i></span>";
        if($z<20){
            $info=asn_lookup_info($asn);
            $name=htmlspecialchars($info["name"]);
            $country=htmlspecialchars($info["country"]);
        }


        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td nowrap><strong>AS$asn</strong></td>";
        $html[]="<td>$name</td>";
        $html[]="<td nowrap>$country</td>";
        $html[]="<td style='text-align:right'>".$tpl->FormatNumber($total)."</td>";
        $html[]="<td style='text-align:right'>".$tpl->FormatNumber($ips)."</td>";
        $html[]="<td style='text-align:right'>$badge2xx</td>";
        $html[]="<td style='text-align:right'>$badge3xx</td>";
        $html[]="<td style='text-align:right'>$badge4xx</td>";
        $html[]="<td style='text-align:right'>$badge5xx</td>";
        $html[]="</tr>";
        $d++;
        if($d>250){break;}
    }

    $html[]="</tbody>";
    $html[]="<tfoot><tr><td colspan='9'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $html[]="</table>";

    $html[]="</div></div>"; // ibox-content, ibox

    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function(){\$('#table-asn-$t').footable({";
    $html[]="    \"filtering\":{\"enabled\":true},";
    $html[]="    \"sorting\":{\"enabled\":true},";
    $html[]="    \"paging\":{\"size\":{$GLOBALS["FOOTABLE_PSIZE"]}}";
    $html[]="});});
    Loadjs('$page?LoadAsnInfos=yes');
    </script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}
function fill_asn():bool{
    $asnq=explode(",",$_GET["fill-asn"]);
    $f=array();
    foreach($asnq as $asn){
        $info=asn_lookup_info($asn);
        $name=base64_encode(htmlspecialchars($info["name"]));
        $country=base64_encode(htmlspecialchars($info["country"]));
        header("content-type: application/x-javascript");
        $f[]="if(document.getElementById('asname-$asn')){";
        $f[]="document.getElementById('asname-$asn').innerHTML=base64_decode('$name');";
        $f[]="}";
        $f[]="if(document.getElementById('ascountry-$asn')){";
        $f[]="document.getElementById('ascountry-$asn').innerHTML=base64_decode('$country');";
        $f[]="}";
    }
    echo implode("\n",$f);
    return true;
}
function LoadAsnInfos():bool{
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    echo "
function FillASNBatch(list) {
    // list = array of ints
    Loadjs('$page?fill-asn=' + list.join(','));
}

function CollectASNs() {
    const seen = Object.create(null);
    const out = [];

    $(\"span[id^='asname-']\").each(function () {
        const m = this.id.match(/^asname-(\d+)$/);
        if (!m) return;

        const asn = parseInt(m[1], 10);
        if (!Number.isFinite(asn)) return;

        if (!seen[asn]) {
            seen[asn] = true;
            out.push(asn);
        }
    });

    return out;
}

function ChunkArray(arr, size) {
    const chunks = [];
    for (let i = 0; i < arr.length; i += size) {
        chunks.push(arr.slice(i, i + size));
    }
    return chunks;
}

// Sequential queue using a small delay between requests
function ProcessASNQueue(chunks, delayMs) {
    let i = 0;

    function next() {
        if (i >= chunks.length) return;

        FillASNBatch(chunks[i]);
        i++;

        setTimeout(next, delayMs);
    }

    next();
}

$(document).ready(function () {
    const asns = CollectASNs();          // deduped list
    const chunks = ChunkArray(asns, 50); // 50 per request (tweak)
    ProcessASNQueue(chunks, 150);        // 150ms between requests (tweak)
});
";
    return true;


}
