<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["top-status"])){top_status();exit;}
if(isset($_GET["hour-status"])){hour_status();exit;}
if(isset($_GET["today-status"])){today_status();exit;}
if(isset($_GET["top-destination"])){graph_top_destinations();exit;}
if(isset($_GET["top-sources"])){graph_top_sources();exit;}
if(isset($_GET["top-services"])){graph_top_services();exit;}


if(isset($_GET["top-destination-table"])){graph_top_destinations_table();exit;}
if(isset($_GET["top-sources-table"])){graph_top_sources_table();exit;}
if(isset($_GET["top-services-table"])){graph_top_services_table();exit;}

page();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("FortiGate {today}",ico_firewall,"{APP_FORTIGATE_EXPLAIN}",
        "$page?tabs=yes","fortigate-today","progress-fortigate",false,"table-loader-fortigatetofay-service");

    $tpl=new template_admin("FortiGate",$html);

    if(isset($_GET["main-page"])){
        echo $tpl->build_firewall();
        return;
    }

   echo $tpl->_ENGINE_parse_body($html);

}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{TOP}"]="$page?top-status=yes";
    $array["{this_hour}"]="$page?hour-status=yes";
    $array["{today}"]="$page?today-status=yes";
    echo $tpl->tabs_default($array);

}
function hour_status(){
    $tpl=new template_admin();
    $html[]="<div class='center' style='margin-top:40px'>";
    $html[]="<img src='img/squid/fortiflowout-hourly.flat.png'>";
    $html[]="<img src='img/squid/fortiflowin-hourly.flat.png'>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
}
function today_status(){
    $tpl=new template_admin();
    $html[]="<div class='center' style='margin-top:40px'>";
    $html[]="<img src='img/squid/fortiflowout-day.flat.png'>";
    $html[]="<img src='img/squid/fortiflowin-day.flat.png'>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
}

function top_status(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:50%'>";
    $html[]="<div class='center' style='margin-top:0px;height:558px' id='top-destination'>";
    $html[]="</td>";
    $html[]="<td style='vertical-align:top;width:50%;padding-left:10px'>";
    $html[]="<div class='center' style='margin-top:0px' id='top-destination-table'>";
    $html[]="</td>";
    $html[]="</tr>";

    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:50%'>";
    $html[]="<div class='center' style='margin-top:0px;height:558px' id='top-sources'>";
    $html[]="</td>";
    $html[]="<td style='vertical-align:top;width:50%;padding-left:10px'>";
    $html[]="<div class='center' style='margin-top:0px' id='top-sources-table'>";
    $html[]="</td>";
    $html[]="</tr>";

    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:50%'>";
    $html[]="<div class='center' style='margin-top:0px;height:558px' id='top-services'>";
    $html[]="</td>";
    $html[]="<td style='vertical-align:top;width:50%;padding-left:10px'>";
    $html[]="<div class='center' style='margin-top:0px' id='top-services-table'>";
    $html[]="</td>";
    $html[]="</tr>";


    $html[]="</table>";
    $html[]="<script>";
    $html[]="Loadjs('$page?top-destination=yes&container=top-destination')";
    $html[]="Loadjs('$page?top-sources=yes&container=top-sources')";
    $html[]="Loadjs('$page?top-services=yes&container=top-services')";
    $html[]="LoadAjax('top-destination-table','$page?top-destination-table=yes')";
    $html[]="LoadAjax('top-sources-table','$page?top-sources-table=yes')";
    $html[]="LoadAjax('top-services-table','$page?top-services-table=yes')";

    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}
function graph_top_sources_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new postgres_sql();
    $sql="SELECT SUM(rcvbytes) as tsize,srcip FROM fortigate_access GROUP BY srcip ORDER BY tsize DESC LIMIT 15";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){return $tpl->div_error($q->mysql_error);}


    $html[]="<table style='width:100%' class='table table-stripped'>";
    $html[]="<tr>";
    $html[]="<th>{ipaddr}</th>";
    $html[]="<th>{hostname}</th>";
    $html[]="<th style='text-align:right'>{bandwidth}</th>";
    $html[]="</tr>";

    while($ligne=@pg_fetch_array($results)){

        $size=$ligne["tsize"];
        $size=$size/1024;
        $size=FormatBytes($size);
        $dstip=$ligne["srcip"];
        $hostname=gethostbyaddr($dstip);

        $dstip=$tpl->td_href("$dstip","","Loadjs('fw.fortinet.zoom.sources.php?record=$dstip&time=today')");

        $html[]="<tr>";
        $html[]="<td style='width:1%;text-align:left' nowrap>$dstip</td>";
        $html[]="<td style='width:1%;text-align:left' nowrap>$hostname</td>";
        $html[]="<td style='width:1%;text-align:right' nowrap>$size</td>";
        $html[]="</tr>";
    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}
function graph_top_services_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new postgres_sql();
    $sql="SELECT SUM(rcvbytes) as tsize,service FROM fortigate_access GROUP BY service ORDER BY tsize DESC LIMIT 15";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}


    $html[]="<table style='width:100%' class='table table-stripped'>";
    $html[]="<tr>";
    $html[]="<th>{service}</th>";
    $html[]="<th style='text-align:right'>{bandwidth}</th>";
    $html[]="</tr>";

    while($ligne=@pg_fetch_array($results)){

        $size=$ligne["tsize"];
        $size=$size/1024;
        $size=FormatBytes($size);
        $dstip=$ligne["service"];
        $dstip=$tpl->td_href("$dstip","","Loadjs('fw.fortinet.zoom.services.php?record=$dstip&time=today')");

        $html[]="<tr>";
        $html[]="<td style='width:1%;text-align:left' nowrap>$dstip</td>";
        $html[]="<td style='width:1%;text-align:right' nowrap>$size</td>";
        $html[]="</tr>";
    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}
function graph_top_destinations_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new postgres_sql();
    $sql="SELECT SUM(rcvbytes) as tsize,dstip FROM fortigate_access GROUP BY dstip ORDER BY tsize DESC LIMIT 15";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){return $tpl->div_error($q->mysql_error);}


    $html[]="<table style='width:100%' class='table table-stripped'>";
    $html[]="<tr>";
    $html[]="<th>{ipaddr}</th>";
    $html[]="<th>{hostname}</th>";
    $html[]="<th style='text-align:right'>{bandwidth}</th>";
    $html[]="</tr>";

    while($ligne=@pg_fetch_array($results)){

        $size=$ligne["tsize"];
        $size=$size/1024;
        $size=FormatBytes($size);
        $dstip=$ligne["dstip"];
        $hostname=gethostbyaddr($dstip);

        $dstip=$tpl->td_href("$dstip","","Loadjs('fw.fortinet.zoom.destination.php?record=$dstip&time=today')");

        $html[]="<tr>";
        $html[]="<td style='width:1%;text-align:left' nowrap>$dstip</td>";
        $html[]="<td style='width:1%;text-align:left' nowrap>$hostname</td>";
        $html[]="<td style='width:1%;text-align:right' nowrap>$size</td>";
        $html[]="</tr>";
    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}
function graph_top_services(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new postgres_sql();
    $title="{top_services} (MB)";
    $sql="SELECT SUM(rcvbytes) as tsize,service FROM fortigate_access GROUP BY service ORDER BY tsize DESC LIMIT 15";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){return $tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}


    while($ligne=@pg_fetch_array($results)){
        $size=$ligne["tsize"];
        $size=$size/1024;
        $size=$size/1024;
        $size=round($size,3);
        $PieData[$ligne["service"]]=$size;
    }


    $tpl=new templates();
    $highcharts=new highcharts();
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->container=$_GET["container"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle="{size} MB";
    $highcharts->Title=$tpl->_ENGINE_parse_body($title);
    echo $highcharts->BuildChart();
}
function graph_top_sources(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new postgres_sql();
    $title="{top_sources} (MB)";
    $sql="SELECT SUM(rcvbytes) as tsize,srcip FROM fortigate_access GROUP BY srcip ORDER BY tsize DESC LIMIT 15";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){return $tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}


    while($ligne=@pg_fetch_array($results)){
        $size=$ligne["tsize"];
        $size=$size/1024;
        $size=$size/1024;
        $size=round($size,3);
        $PieData[$ligne["srcip"]]=$size;
    }


    $tpl=new templates();
    $highcharts=new highcharts();
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->container=$_GET["container"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle="{size} MB";
    $highcharts->Title=$tpl->_ENGINE_parse_body($title);
    echo $highcharts->BuildChart();
}
function graph_top_destinations(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new postgres_sql();
    $title="{top_destinations} (MB)";
    $sql="SELECT SUM(rcvbytes) as tsize,dstip FROM fortigate_access GROUP BY dstip ORDER BY tsize DESC LIMIT 15";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){return $tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}


    while($ligne=@pg_fetch_array($results)){
        $size=$ligne["tsize"];
        $size=$size/1024;
        $size=$size/1024;
        $size=round($size,3);
        $PieData[$ligne["dstip"]]=$size;
    }


    $tpl=new templates();
    $highcharts=new highcharts();
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->container=$_GET["container"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle="{size} MB";
    $highcharts->Title=$tpl->_ENGINE_parse_body($title);
    echo $highcharts->BuildChart();
}