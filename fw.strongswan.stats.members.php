<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["template"])){template();exit;}
if(isset($_GET["vertical-timeline"])){vertical_timeline();exit;}
if(isset($_GET["top-tunnels"])){top_tunnels();exit;}
if(isset($_GET["tunnels-table"])){top_tunnels_table();exit;}
js();


function js(){
    $tpl=new template_admin();
    $field=$_GET["field"];
    $value=$_GET["value"];

    $fieldenc=urlencode($field);
    $valueenc=urlencode($value);

    $page=CurrentPageName();
    $tpl->js_dialog2("$field/$value","$page?template=yes&field=$fieldenc&value=$valueenc",1040);


}
function template(){
    $field=$_GET["field"];
    $value=$_GET["value"];
    $fieldenc=urlencode($field);
    $valueenc=urlencode($value);
    $t=time();
    $imgwait="<img src='/img/Eclipse-0.9s-120px.gif'>";
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top'><div id='vertical-timeline-bytes-in-out$t'>$imgwait</div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top'><div id='vertical-timeline-packets-in-out-$t'>$imgwait</div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<table style='width:100%'>";
    $html[]="<td colspan=2><hr></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top'><div id='tunnels-graph-$t'>$imgwait</div></td>";
    $html[]="<td style='vertical-align:top'><div id='tunnels-table-$t'>$imgwait</div></td>";
    $html[]="</tr>";


    $html[]="</table>";
    $html[]="<script>";
    $html[]="Loadjs('$page?vertical-timeline=yes&t=$t&field=$fieldenc&value=$valueenc');";

    echo $tpl->_ENGINE_parse_body($html);
}

function vertical_timeline(){
    $t=$_GET["t"];
    $tpl=new template_admin();
    $q=new postgres_sql();
    $DIFF_MIN=$_SESSION["IPPSEC_DAY"]["DIFF_MIN"];
    $DIFF_HOURS=$DIFF_MIN/60;
    echo "// DIFF: $DIFF_MIN hours";
    $time1=strtotime("{$_SESSION["IPPSEC_DAY"]["FROM"]} {$_SESSION["IPPSEC_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["IPPSEC_DAY"]["TO"]} {$_SESSION["IPPSEC_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);
    $UNIT="{each_10minutes}";
    $SQL_DATE="zdate";
    $field=$_GET["field"];
    $value=$_GET["value"];
    $fieldenc=urlencode($field);
    $valueenc=urlencode($value);

    if($DIFF_HOURS>6){
        $UNIT="{hourly}";
        $SQL_DATE="date_part('hour', zdate)";
    }

    if($DIFF_HOURS>24) {
        $UNIT = "{daily}";
        $SQL_DATE = "date_part('day', zdate)";
    }

//    $sql="SELECT SUM(bytes_out) as bytes_out, SUM(bytes_in) as bytes_in, $SQL_DATE as zdate FROM strongswan_stats WHERE $field='$value' AND zdate >'$strtime' and zdate < '$strtoTime' GROUP by $SQL_DATE ORDER BY $SQL_DATE";
    $sql="select sum(bytes_in) as bytes_in, sum(bytes_out) as bytes_out, $SQL_DATE as zdate from (SELECT distinct on (spi_in) spi_in, MAX(bytes_in) as bytes_in, MAX(bytes_out) as bytes_out, zdate,$field FROM  strongswan_stats where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY spi_in, zdate,$field ORDER BY spi_in, zdate,$field) x where $field='$value' AND zdate >'$strtime' and zdate < '$strtoTime' GROUP BY $SQL_DATE ORDER BY $SQL_DATE";


    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $bytes_in = $ligne["bytes_in"];
        $bytes_in = $bytes_in / 1024;
        $bytes_in = $bytes_in / 1024;
        $bytes_in = round($bytes_in);

        $bytes_out = $ligne["bytes_out"];
        $bytes_out = $bytes_out / 1024;
        $bytes_out = $bytes_out / 1024;
        $bytes_out = round($bytes_out);
        echo "// {$ligne["zdate"]}\n";
        $stime=strtotime($ligne["zdate"]);
        $datetext=date("H:i",$stime);
        if($DIFF_HOURS>6){
            $datetext="{$ligne["zdate"]}h";
        }
        if($DIFF_HOURS>24){
            $datetext=$tpl->_ENGINE_parse_body("{day}")." {$ligne["zdate"]}";
        }
        $xdata[]=$datetext;
        $mb_in[]=$bytes_in;
        $mb_out[]=$bytes_out;
    }


    $stimeFrom=$tpl->time_to_date($time1);
    $stimeTo=$tpl->time_to_date($time2);
    $title="{APP_STRONGSWAN_TRAFFIC_FLOW} (MB) - $field $value $stimeFrom - $stimeTo {$UNIT}";
    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->container="vertical-timeline-bytes-in-out$t";
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="MB";
    $highcharts->ChartType = "line";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="MB";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("In "=>$mb_in, "Out "=>$mb_out);
    echo $highcharts->BuildChart();
    //PACKETS IN / OUT
//    $sql = "SELECT SUM(packets_in) as packets_in, SUM(packets_out) as packets_out, $SQL_DATE as zdate FROM strongswan_stats WHERE $field='$value' AND zdate >'$strtime' and zdate < '$strtoTime' GROUP by $SQL_DATE ORDER BY $SQL_DATE";
    $sql="select SUM(packets_in) as packets_in, SUM(packets_out) as packets_out, $SQL_DATE as zdate from (SELECT distinct on (spi_in) spi_in, MAX(packets_in) as packets_in, MAX(packets_out) as packets_out, zdate,$field FROM  strongswan_stats where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY spi_in, zdate,$field ORDER BY spi_in, zdate,$field) x where $field='$value' AND zdate >'$strtime' and zdate < '$strtoTime' GROUP BY $SQL_DATE ORDER BY $SQL_DATE";

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
    while ($ligne = pg_fetch_assoc($results)) {
        $packets_in = $ligne["packets_in"];
        $packets_out = $ligne["packets_out"];
        echo "// {$ligne["zdate"]}\n";
        $stime=strtotime($ligne["zdate"]);
        $datetext=date("H:i",$stime);
        if($DIFF_HOURS>6){
            $datetext="{$ligne["zdate"]}h";
        }
        if($DIFF_HOURS>24){
            $datetext=$tpl->_ENGINE_parse_body("{day}")." {$ligne["zdate"]}";
        }
        $xdata[]=$datetext;
        $packets_in_t[]=$packets_in;
        $packets_out_t[]=$packets_out;
    }


    $stimeFrom=$tpl->time_to_date($time1);
    $stimeTo=$tpl->time_to_date($time2);
    $title="{APP_STRONGSWAN_PACKETS_FLOW} - $field $value $stimeFrom - $stimeTo {$UNIT}";
    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->container="vertical-timeline-packets-in-out-$t";
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle=" Packets";
    $highcharts->ChartType = "line";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix=" Packets";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("In "=>$packets_in_t,"Out "=>$packets_out_t);
    echo $highcharts->BuildChart();
    $page=CurrentPageName();
    echo "Loadjs('$page?top-tunnels=yes&t=$t&field=$fieldenc&value=$valueenc');\n";
}



function top_tunnels(){
    $page=CurrentPageName();
    $t=$_GET["t"];
    $tpl=new template_admin();
    $q=new postgres_sql();
    $field=$_GET["field"];
    $value=$_GET["value"];
    $fieldenc=urlencode($field);
    $valueenc=urlencode($value);
    $DIFF_MIN=$_SESSION["IPPSEC_DAY"]["DIFF_MIN"];
    $DIFF_HOURS=$DIFF_MIN/60;
    echo "// DIFF: $DIFF_MIN hours";
    $time1=strtotime("{$_SESSION["IPPSEC_DAY"]["FROM"]} {$_SESSION["IPPSEC_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["IPPSEC_DAY"]["TO"]} {$_SESSION["IPPSEC_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);

    //$sql="SELECT SUM(bytes_in + bytes_out) as size,conn_name FROM strongswan_stats WHERE $field='$value' AND zdate >'$strtime' and zdate < '$strtoTime' GROUP by conn_name ORDER BY size DESC LIMIT 15";
    $sql="select SUM(bytes_in + bytes_out) as size,conn_name from (SELECT distinct on (spi_in) spi_in, MAX(bytes_out) as bytes_out, MAX(bytes_in) as bytes_in, conn_name,zdate,$field FROM  strongswan_stats where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY spi_in, conn_name,zdate,$field ORDER BY spi_in, conn_name) x where  $field='$value' AND zdate >'$strtime' and zdate < '$strtoTime' GROUP BY conn_name ORDER BY size DESC LIMIT 15 ";


    $q->QUERY_SQL($sql);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);
        $PieData[$ligne["conn_name"]]=$size;
        $PieData2[$ligne["conn_name"]]=$size;
    }

    $encoded=base64_encode(serialize($PieData2));

    $highcharts=new highcharts();
    $highcharts->container="tunnels-graph-$t";
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "(MB)";
    $highcharts->TitleFontSize = "14px";
    $highcharts->Title="{tunnels_top} - $value";
    echo $highcharts->BuildChart();

    echo "LoadAjax('tunnels-table-$t','$page?tunnels-table=$encoded');";


}
function top_tunnels_table(){
    $tpl=new template_admin();
    $html[]="<table style='width:100%' class='table'>";
    $html[]="<tr>";
    $html[]="<th>{tunnel}</th>";
    $html[]="<th>{size}</th>";
    $html[]="</tr>";
    $table=unserialize(base64_decode($_GET["tunnels-table"]));
    foreach ( $table as $tunnel=>$sizeM) {


        $html[]="<tr>";
        $html[]="<td><strong>$tunnel</strong></td>";
        $html[]="<td><strong>". FormatBytes($sizeM*1024)."</strong></td>";
        $html[]="</tr>";

    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);

}

