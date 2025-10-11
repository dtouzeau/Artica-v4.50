<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["graph"])){graph();exit;}
if(isset($_GET["table"])){graph_table();exit;}
if(isset($_GET["linear"])){rqs();exit;}
js();


function js(){
    $record=$_GET["record"];
    $time=$_GET["time"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{services}: $record","$page?popup=yes&time=$time&record=$record",1250);
}

function popup(){
    $record=$_GET["record"];
    $time=$_GET["time"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();
    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td colspan=2><div id='linear-$t'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:50%'>";
    $html[]="<div class='center' style='margin-top:0px;height:558px' id='top-sources-$t'>";
    $html[]="</td>";
    $html[]="<td style='vertical-align:top;width:50%;padding-left:10px'>";
    $html[]="<div class='center' style='margin-top:0px' id='top-sources-table-$t'>";
    $html[]="</td>";
    $html[]="</tr>";

    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:50%'>";
    $html[]="<div class='center' style='margin-top:0px;height:558px' id='top-destinations-$t'>";
    $html[]="</td>";
    $html[]="<td style='vertical-align:top;width:50%;padding-left:10px'>";
    $html[]="<div class='center' style='margin-top:0px' id='top-destinations-table-$t'>";
    $html[]="</td>";
    $html[]="</tr>";


    $html[]="</table>";
    $html[]="<script>";
    $html[]="Loadjs('$page?linear=yes&container=linear-$t&time=$time&record=$record&field=service')";
    $html[]="Loadjs('$page?graph=yes&container=top-sources-$t&time=$time&record=$record&field=srcip')";
    $html[]="Loadjs('$page?graph=yes&container=top-destinations-$t&time=$time&record=$record&field=dstip')";
    $html[]="LoadAjax('top-sources-table-$t','$page?table=yes&time=$time&record=$record&field=srcip&hostname=yes')";
    $html[]="LoadAjax('top-destinations-table-$t','$page?table=yes&time=$time&record=$record&field=dstip')";

    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);

}

function graph_top_sources_query(){
    $record=$_GET["record"];
    $time=$_GET["time"];
    $field=$_GET["field"];
    if($time=="today") {
        return "SELECT SUM(rcvbytes) as tsize,$field as zfield FROM fortigate_access WHERE service='$record' GROUP BY $field ORDER BY tsize DESC LIMIT 15";
    }
    if($time=="this_month") {
        return "SELECT SUM(rcvbytes) as tsize,$field as zfield FROM fortigate_days WHERE service='$record' AND zdate >= date_trunc('month', current_date) GROUP BY $field ORDER BY tsize DESC LIMIT 15";
    }


}
function graph(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new postgres_sql();
    $time=$_GET["time"];
    $field=$_GET["field"];
    $title="{{$field}} {{$time}} (MB)";
    $sql = graph_top_sources_query();

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){return $tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}


    while($ligne=@pg_fetch_array($results)){
        $size=$ligne["tsize"];
        $size=$size/1024;
        $size=$size/1024;
        $size=round($size,3);
        $PieData[$ligne["zfield"]]=$size;
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
function graph_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new postgres_sql();
    $sql = graph_top_sources_query();
    $results=$q->QUERY_SQL($sql);
    $time=$_GET["time"];
    if(!$q->ok){return $tpl->div_error($q->mysql_error);}
    $hostname=false;
    if(isset($_GET["hostname"])){
        $hostname=true;
    }
    $field=$_GET["field"];
    $html[]="<table style='width:100%' class='table table-stripped'>";
    $html[]="<tr>";
    $html[]="<th>{{$field}}</th>";
    if($hostname) {
        $html[] = "<th>{hostname}</th>";
    }
    $html[]="<th style='text-align:right'>{bandwidth}</th>";
    $html[]="</tr>";

    while($ligne=@pg_fetch_array($results)){

        $size=$ligne["tsize"];
        $size=$size/1024;
        $size=FormatBytes($size);
        $dstip=$ligne["zfield"];

        if($hostname) {
            $hostname = gethostbyaddr($dstip);

        }

        if($field=="srcip") {
            $dstip = $tpl->td_href($dstip,"", "Loadjs('fw.fortinet.zoom.sources.php?record=$dstip&time=$time')");
        }
        if($field=="dstip") {
            $dstip = $tpl->td_href($dstip, "","Loadjs('fw.fortinet.zoom.destination.php?record=$dstip&time=$time')");
        }
        if($field=="service") {
            $dstip = $tpl->td_href($dstip,"", "Loadjs('fw.fortinet.zoom.services.php?record=$dstip&time=$time')");
        }


        $html[]="<tr>";
        $html[]="<td style='width:1%;text-align:left' nowrap>$dstip</td>";
        $html[]="<td style='width:1%;text-align:left' nowrap>$hostname</td>";
        $html[]="<td style='width:1%;text-align:right' nowrap>$size</td>";
        $html[]="</tr>";
    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}

function linear_query(){
    $record=$_GET["record"];
    $time=$_GET["time"];
    $field=$_GET["field"];
    if($time=="today") {
        return "SELECT SUM(rcvbytes) as tsize,zdate FROM fortigate_access WHERE $field='$record' GROUP BY zdate ORDER by zdate";
    }
    if($time=="this_month") {
        return "SELECT SUM(rcvbytes) as tsize,zdate FROM fortigate_days WHERE $field='$record' AND zdate >= date_trunc('month', current_date) GROUP BY zdate ORDER by zdate";
    }
}
function rqs(){
    $MAIN=unserialize(@file_get_contents(PROGRESS_DIR."/hour.requests.data"));
    $tpl=new templates();
    $page=CurrentPageName();
    $record=$_GET["record"];
    $q=new postgres_sql();
    $time=$_GET["time"];
    $field=$_GET["field"];
    $title="$record {{$time}} (MB)";
    $sql = linear_query();

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){return $tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}

    while($ligne=@pg_fetch_array($results)){
        $size=$ligne["tsize"];
        $size=$size/1024;
        $size=$size/1024;
        $size=round($size,3);
        $MAIN["xdata"][]=$ligne["zdate"];
        $MAIN["ydata"][]=$size;
    }


    $timetext="{time}";
    $highcharts=new highcharts();
    $highcharts->container=$_GET["container"];
    $highcharts->xAxis=$MAIN["xdata"];
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="22px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="RQS";
    $highcharts->xAxis_labels=false;
    $highcharts->LegendPrefix=$tpl->javascript_parse_text('{time}:');
    $highcharts->LegendSuffix="{bandwidth}";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("| {bandwidth}"=>$MAIN["ydata"]);
    echo $highcharts->BuildChart();
}