<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["member-stats"])){echo member_stats_js();exit;}
if(isset($_GET["member-stats-tabs"])){echo member_stats_tabs();exit;}
if(isset($_GET["member-stats-pane-hours"])){echo member_stats_pane_hours();exit;}
if(isset($_GET["member-stats-pane-hours-graph"])){echo member_stats_pane_hours_graph();exit;}
if(isset($_GET["member-stats-pane-hours-graph2"])){echo member_stats_pane_hours_graph2();exit;}
if(isset($_GET["member-stats-pane-sites"])){echo member_stats_pane_sites();exit;}

if(isset($_GET["member-stats-pane-sites-graph"])){echo member_stats_pane_sites_graph();exit;}
if(isset($_GET["member-stats-pane-sites-graph2"])){echo member_stats_pane_sites_graph2();exit;}
if(isset($_GET["member-stats-pane-sites-graph3"])){echo member_stats_pane_sites_graph3();exit;}
if(isset($_GET["member-stats-pane-sites-graph4"])){echo member_stats_pane_sites_graph4();exit;}
if(isset($_GET["member-stats-notcategorized"])){echo member_stats_notcategorized();exit;}
if(isset($_GET["member-stats-websites"])){echo member_stats_websites();exit;}


function member_stats_pane_sites(){
    $page=CurrentPageName();
    $srctype=$_GET["srctype"];
    $username=$_GET["data"];
    $usernameenc=urlencode($username);
    $Date1=$_GET["date-from"];
    $Date2=$_GET["date-to"];
    $id=md5(serialize($_GET));

    echo "<table style='width:100%'>
    <tr>
    <td style='width:50%'><div id='$id'></div></td>
    <td style='width:50%'><div id='rq-$id'></div></td>
    </tr>
    <tr>
    <td style='width:50%'><div id='cats-$id'></div></td>
    <td style='width:50%'><div id='catr-$id'></div></td>
    </tr>    
    </table>
    
    <script>
        Loadjs('$page?member-stats-pane-sites-graph=yes&date-from=$Date1&date-to=$Date2&srctype=$srctype&data=$usernameenc&id=$id');
         Loadjs('$page?member-stats-pane-sites-graph2=yes&date-from=$Date1&date-to=$Date2&srctype=$srctype&data=$usernameenc&id=rq-$id');   
         Loadjs('$page?member-stats-pane-sites-graph3=yes&date-from=$Date1&date-to=$Date2&srctype=$srctype&data=$usernameenc&id=cats-$id');
         Loadjs('$page?member-stats-pane-sites-graph4=yes&date-from=$Date1&date-to=$Date2&srctype=$srctype&data=$usernameenc&id=catr-$id');    
    </script>";

}

function member_stats_pane_hours(){
    $page=CurrentPageName();
    $srctype=$_GET["srctype"];
    $username=$_GET["data"];
    $usernameenc=urlencode($username);
    $Date1=$_GET["date-from"];
    $Date2=$_GET["date-to"];
    $id=md5(serialize($_GET));

    echo "<div id='$id'></div>
    <div id='rq-$id' style='margin-top:50px'></div>
    <script>
        Loadjs('$page?member-stats-pane-hours-graph=yes&date-from=$Date1&date-to=$Date2&srctype=$srctype&data=$usernameenc&id=$id');
         Loadjs('$page?member-stats-pane-hours-graph2=yes&date-from=$Date1&date-to=$Date2&srctype=$srctype&data=$usernameenc&id=rq-$id');   
    
    </script>";
}

function member_stats_pane_sites_graph(){
    $tpl=new template_admin();
    $srctype=$_GET["srctype"];
    $username=$_GET["data"];
    $Date1=$_GET["date-from"];
    $Date2=$_GET["date-to"];
    $strtime=date("Y-m-d H:i:s",$Date1);
    $strtoTime=date("Y-m-d H:i:s",$Date2);

    $q=new postgres_sql();
    $sql="SELECT SUM(statscom_days.size) as size,statscom_websites.familysite,statscom_days.siteid FROM statscom_days,statscom_websites WHERE statscom_websites.siteid=statscom_days.siteid AND zdate >'$strtime' and zdate < '$strtoTime' and $srctype='$username' GROUP by familysite,statscom_days.siteid ORDER BY size DESC LIMIT 20";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);
        $PieData2[$ligne["familysite"]]=array($size,$ligne["siteid"]);
        $PieData[$ligne["familysite"]]=$size;
    }

    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{top_websites_by_size} $username (MB)";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();

}

function member_stats_pane_sites_graph3(){
    $tpl=new template_admin();
    $srctype=$_GET["srctype"];
    $username=$_GET["data"];
    $Date1=$_GET["date-from"];
    $Date2=$_GET["date-to"];
    $strtime=date("Y-m-d H:i:s",$Date1);
    $strtoTime=date("Y-m-d H:i:s",$Date2);

    $q=new postgres_sql();
    $catz=new mysql_catz();
    $sql="SELECT SUM(statscom_days.size) as size,statscom_websites.category FROM statscom_days,statscom_websites WHERE statscom_websites.siteid=statscom_days.siteid AND zdate >'$strtime' and zdate < '$strtoTime' and $srctype='$username' GROUP by category ORDER BY size DESC LIMIT 15";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);
        $category=$catz->CategoryIntToStr($ligne["category"]);
        $PieData2[$category]=array($size,$ligne["siteid"]);
        $PieData[$category]=$size;
    }

    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{top_categories_by_size} $username (MB)";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();

}
function member_stats_pane_sites_graph4(){
    $tpl=new template_admin();
    $srctype=$_GET["srctype"];
    $username=$_GET["data"];
    $Date1=$_GET["date-from"];
    $Date2=$_GET["date-to"];
    $strtime=date("Y-m-d H:i:s",$Date1);
    $strtoTime=date("Y-m-d H:i:s",$Date2);

    $q=new postgres_sql(); $catz=new mysql_catz();
    $sql="SELECT SUM(statscom_days.hits) as size,statscom_websites.category FROM statscom_days,statscom_websites WHERE statscom_websites.siteid=statscom_days.siteid AND zdate >'$strtime' and zdate < '$strtoTime' and $srctype='$username' GROUP by category ORDER BY size DESC LIMIT 15";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $category=$catz->CategoryIntToStr($ligne["category"]);
        $PieData2[$category]=array($size,$ligne["siteid"]);
        $PieData[$category]=$size;
    }

    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{top_categories_by_hits} $username";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();

}
function member_stats_pane_sites_graph2(){
    $tpl=new template_admin();
    $srctype=$_GET["srctype"];
    $username=$_GET["data"];
    $Date1=$_GET["date-from"];
    $Date2=$_GET["date-to"];
    $strtime=date("Y-m-d H:i:s",$Date1);
    $strtoTime=date("Y-m-d H:i:s",$Date2);

    $q=new postgres_sql();
    $sql="SELECT SUM(statscom_days.hits) as size,statscom_websites.familysite,statscom_days.siteid FROM statscom_days,statscom_websites WHERE statscom_websites.siteid=statscom_days.siteid AND zdate >'$strtime' and zdate < '$strtoTime' and $srctype='$username' GROUP by familysite,statscom_days.siteid ORDER BY size DESC LIMIT 20";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $PieData2[$ligne["familysite"]]=array($size,$ligne["siteid"]);
        $PieData[$ligne["familysite"]]=$size;
    }

    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{top_websites_by_hits} $username";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();


}

function member_stats_pane_hours_graph(){
    $tpl=new template_admin();
    $srctype=$_GET["srctype"];
    $username=$_GET["data"];
    $Date1=$_GET["date-from"];
    $Date2=$_GET["date-to"];
    $strtime=date("Y-m-d H:i:s",$Date1);
    $strtoTime=date("Y-m-d H:i:s",$Date2);


    $q=new postgres_sql();
    $sql="SELECT SUM(size) as size,zdate FROM statscom_days WHERE ( zdate >'$strtime' and zdate < '$strtoTime' and $srctype='$username') GROUP BY zdate ORDER BY zdate";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = round($size);
        $stime=strtotime($ligne["zdate"]);
        $DateText=date("l d",$stime);

        $xdata[]=$DateText;
        $ydata[]=$size;
    }
    $UNIT="KB";

    $stimeFrom=$tpl->time_to_date($Date1);
    $stimeTo=$tpl->time_to_date($Date2);
    $title="{downloaded_flow} (MB) $stimeFrom - $stimeTo {$UNIT} $srctype $username";
    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle=$UNIT;
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix=$UNIT;
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{size}"=>$ydata);
    echo $highcharts->BuildChart();
}

function member_stats_pane_hours_graph2(){

    $tpl=new template_admin();
    $srctype=$_GET["srctype"];
    $username=$_GET["data"];
    $Date1=$_GET["date-from"];
    $Date2=$_GET["date-to"];
    $strtime=date("Y-m-d H:i:s",$Date1);
    $strtoTime=date("Y-m-d H:i:s",$Date2);


    $q=new postgres_sql();
    $sql="SELECT SUM(hits) as hits,zdate FROM statscom_days WHERE ( zdate >'$strtime' and zdate < '$strtoTime' and $srctype='$username') GROUP BY zdate ORDER BY zdate";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["hits"];
        $stime=strtotime($ligne["zdate"]);
        $DateText=date("l d",$stime);

        $xdata[]=$DateText;
        $ydata[]=$size;
    }
    $UNIT="KB";

    $stimeFrom=$tpl->time_to_date($Date1);
    $stimeTo=$tpl->time_to_date($Date2);
    $title="{requests} $stimeFrom - $stimeTo {$UNIT} $srctype $username";
    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="{requests}";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="{requests}";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{requests}"=>$ydata);
    echo $highcharts->BuildChart();

}

function member_stats_websites(){
    $tpl=new template_admin();
    $srctype=$_GET["srctype"];
    $username=$_GET["data"];
    $usernameenc=urlencode($username);
    $Date1=$_GET["date-from"];
    $Date2=$_GET["date-to"];
    $strtime=date("Y-m-d H:i:s",$Date1);
    $strtoTime=date("Y-m-d H:i:s",$Date2);

    $sql="SELECT SUM(statscom_days.size) as size,SUM(statscom_days.hits) as hits,
    statscom_websites.sitename,
    statscom_websites.category,
    statscom_websites.siteid
    FROM statscom_days,statscom_websites WHERE statscom_websites.siteid=statscom_days.siteid 
    AND zdate >'$strtime' 
    AND zdate < '$strtoTime' 
    AND statscom_days.$srctype='$username'
    GROUP by sitename,statscom_websites.siteid,statscom_websites.category ORDER BY size DESC";


    $id=md5(serialize($_GET));
    $html[]="<table style='width:100%' class='table' id='$id' class=\"footable table table-stripped\" 
    data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{domains}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{category}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='numeric'>{requests}</th>";
    $html[]="</tr>";

    $q=new postgres_sql();
    $catz=new mysql_catz();
    $q->QUERY_SQL($sql);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $sizeM=$ligne["size"];
        $sizeR=$ligne["hits"];
        $siteid=$ligne["siteid"];
        $site=$ligne["sitename"];

        $category=$catz->CategoryIntToStr($ligne["category"]);



        $link=$tpl->td_href("$site",null,"Loadjs('fw.statscom_days.browse.website.php?siteid=$siteid&field=$srctype&value=$usernameenc&date-from=$Date1&date-to=$Date2')");
        $html[]="<tr>";
        $html[]="<td><strong>$link</strong></td>";
        $html[]="<td>$category</td>";
        $html[]="<td>".FormatBytes($sizeM/1024)."</td>";
        $html[]="<td>".$tpl->FormatNumber($sizeR)."</td>";
        $html[]="</tr>";

    }
    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
<script> 
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#$id').footable({ \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) }); 
</script>";

    echo $tpl->_ENGINE_parse_body($html);
}

function member_stats_notcategorized(){
    $tpl=new template_admin();
    $srctype=$_GET["srctype"];
    $username=$_GET["data"];
    $usernameenc=urlencode($username);
    $Date1=$_GET["date-from"];
    $Date2=$_GET["date-to"];
    $strtime=date("Y-m-d H:i:s",$Date1);
    $strtoTime=date("Y-m-d H:i:s",$Date2);

    $sql="SELECT SUM(statscom_days.size) as size,SUM(statscom_days.hits) as hits,
    statscom_websites.sitename,
    statscom_websites.siteid
    FROM statscom_days,statscom_websites WHERE statscom_websites.siteid=statscom_days.siteid 
    AND zdate >'$strtime' 
    AND zdate < '$strtoTime' 
    AND statscom_days.$srctype='$username'
    AND statscom_websites.category=0
    GROUP by sitename,statscom_websites.siteid ORDER BY size DESC";



    $html[]="<table style='width:100%' class='table'>";
    $html[]="<tr>";
    $html[]="<th>{domains}</th>";
    $html[]="<th>{size}</th>";
    $html[]="<th>{requests}</th>";
    $html[]="</tr>";

    $q=new postgres_sql();
    $q->QUERY_SQL($sql);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $sizeM=$ligne["size"];
        $sizeR=$ligne["hits"];
        $siteid=$ligne["siteid"];
        $site=$ligne["sitename"];





        $link=$tpl->td_href("$site",null,"Loadjs('fw.statscom_days.browse.website.php?siteid=$siteid&field=$srctype&value=$usernameenc&date-from=$Date1&date-to=$Date2')");
        $html[]="<tr>";
        $html[]="<td><strong>$link</strong></td>";
        $html[]="<td>".FormatBytes($sizeM/1024)."</td>";
        $html[]="<td>".$tpl->FormatNumber($sizeR)."</td>";
        $html[]="</tr>";

    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}
function users_table(){

    $tpl=new template_admin();
    $html[]="<table style='width:100%' class='table'>";
    $html[]="<tr>";
    $html[]="<th>{member}</th>";
    $html[]="</tr>";
    $table=unserialize(base64_decode($_GET["users-table"]));
    foreach ( $table as $site=>$sizeM) {
        $h=explode("|",$site);
        $t=array();
        if($h[0]<>null){
            $t[]=$tpl->td_href($h[0],null,"Loadjs('fw.statscom_days.browse.users.php?field=username&value=".urlencode($h[0])."')");
        }
        if($h[1]<>null){
            $t[]=$tpl->td_href($h[1],null,"Loadjs('fw.statscom_days.browse.users.php?field=mac&value=".urlencode($h[1])."')");
        }
        if($h[2]<>null){
            $t[]=$tpl->td_href($h[2],null,"Loadjs('fw.statscom_days.browse.users.php?field=ipaddr&value=".urlencode($h[2])."')");
        }

        $html[]="<tr>";
        $html[]="<td><strong>".@implode("&nbsp;|&nbsp;",$t)."</strong></td>";
        $html[]="<td>".FormatBytes($sizeM*1024)."</td>";
        $html[]="</tr>";

    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}

function vertical_time_line(){
    $tpl=new template_admin();
    $q=new postgres_sql();
    BUILD_DEFAULT_DATA();
    $DIFF_MIN=$_SESSION["STATSCOM_DAY"]["DIFF_MIN"];
    $DIFF_HOURS=$DIFF_MIN/60;
    echo "// DIFF: $DIFF_MIN hours";
    $time1=strtotime("{$_SESSION["STATSCOM_DAY"]["FROM"]} {$_SESSION["STATSCOM_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["STATSCOM_DAY"]["TO"]} {$_SESSION["STATSCOM_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);
    $UNIT="{each_10minutes}";
    $SQL_DATE="zdate";



    if($DIFF_HOURS>6){
        $UNIT="{hourly}";
        $SQL_DATE="date_part('hour', zdate)";
    }

    if($DIFF_HOURS>48) {
        $UNIT = "{daily}";
        $SQL_DATE = "date_part('day', zdate)";
    }

    $sql="SELECT SUM(size) as size,$SQL_DATE as zdate FROM statscom_days WHERE zdate >'$strtime' and zdate < '$strtoTime' GROUP by $SQL_DATE ORDER BY $SQL_DATE";


    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);
        echo "// {$ligne["zdate"]}\n";
        $stime=strtotime($ligne["zdate"]);
        $datetext=date("H:i",$stime);
        if($DIFF_HOURS>6){
            $datetext="{$ligne["zdate"]}h";
        }
        if($DIFF_HOURS>48){
            $datetext=$tpl->_ENGINE_parse_body("{day}")." {$ligne["zdate"]}";
        }
        $xdata[]=$datetext;
        $ydata[]=$size;
    }


    $stimeFrom=$tpl->time_to_date($time1);
    $stimeTo=$tpl->time_to_date($time2);
    $title="{downloaded_flow} (MB) $stimeFrom - $stimeTo {$UNIT}";
    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="MB";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="MB";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{size}"=>$ydata);
    echo $highcharts->BuildChart();


}


