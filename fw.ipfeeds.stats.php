<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["courbe-threats"])){courbe_threats();exit;}
if(isset($_GET["period"])){stats_page();exit;}
if(isset($_GET["pie-categories"])){pie_categories();exit;}
if(isset($_GET["pie-ips"])){pie_ipaddrs();exit;}
if(isset($_GET["pie-categories-list"])){pie_categories_list();exit;}
if(isset($_GET["pie-ips-list"])){pie_ips_list();exit;}

tabs();

function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{today}"]="$page?period=today";
    $array["{yesterday}"]="$page?period=yesterday";
    $array["{this_week}"]="$page?period=week";
    $array["{this_month}"]="$page?period=month";
    echo "<div style='margin-top:10px'>".$tpl->tabs_default($array)."</div>";
    return true;
}
function stats_page():bool{
    $page=CurrentPageName();
    $time=$_GET["period"];
    $tpl=new template_admin();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td colspan='2' style='text-align:center;'>";
    $html[]="<div id='line-courbe'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:50%;vertical-align: top'><div id='pie-categories'></div></td>";
    $html[]="<td style='width:50%;vertical-align: top'><div id='pie-ips'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:50%;vertical-align: top'><div id='pie-categories-list'></div></td>";
    $html[]="<td style='width:50%;vertical-align: top;padding-left:10px'><div id='pie-ips-list'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="Loadjs('$page?courbe-threats=$time');";
    $html[]="Loadjs('$page?pie-categories=$time');";
    $html[]="Loadjs('$page?pie-ips=$time');";
    $html[]="LoadAjax('pie-categories-list','$page?pie-categories-list=$time');";
    $html[]="LoadAjax('pie-ips-list','$page?pie-ips-list=$time');";
    $html[]="</script>";
    echo @implode("\n", $html);
    return true;
}

function courbe_threats(){
    $time=$_GET["courbe-threats"];
    $zFiles["today"]="todayBlackHits.array";
    $zFiles["yesterday"]="YesterdayBlackHits.array";
    $zFiles["week"]="WeeklyBlackHits.array";
    $zFiles["month"]="MonthlyBlackHits.array";



    $FilePath="/usr/share/artica-postfix/ressources/logs/nfqueue/$zFiles[$time]";
    if(!is_file($FilePath)){
        return "";
    }
    $script_tz = date_default_timezone_get();
    $data=unserialize(file_get_contents($FilePath));
    $xdata=array();
    $ydata=array();
    foreach($data as $k=>$v){
        $date = new DateTime($k, new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone($script_tz));
        $k = $date->format('d H:00');

        if($time=="today" or $time=="yesterday"){
            $k = $date->format('H:i');
        }
        $xdata[]=$k;
        $ydata[]=$v;
    }

    $title="{threats} {{$time}}";
    $timetext=$time;
    $highcharts=new highcharts();
    $highcharts->container="line-courbe";
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="{threats}";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="{days}";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{threats}"=>$ydata);
    echo $highcharts->BuildChart();
    return true;
}

function pie_categories():bool{
    $tpl=new template_admin();
    $time=$_GET["pie-categories"];
    $zFiles["today"]="todayCategories.array";
    $zFiles["yesterday"]="yesterdayCategories.array";
    $zFiles["week"]="weeklyCategories.array";
    $zFiles["month"]="monthlyCategories.array";


    $FilePath="/usr/share/artica-postfix/ressources/logs/nfqueue/$zFiles[$time]";
    if(!is_file($FilePath)){
        return false;
    }
    $PieData=unserialize(file_get_contents($FilePath));
    $highcharts = new highcharts();
    $highcharts->container ="pie-categories";
    $highcharts->PieDatas = $PieData;
    $highcharts->ChartType = "pie";
    $highcharts->PiePlotTitle = "";
    $highcharts->Title = $tpl->_ENGINE_parse_body("{top_categories}/{block}");
    echo $highcharts->BuildChart();
    return true;
}
function pie_ipaddrs():bool{
    $tpl=new template_admin();
    $time=$_GET["pie-ips"];
    $zFiles["today"]="todayIps.array";
    $zFiles["yesterday"]="yesterdayIps.array";
    $zFiles["week"]="weeklyIps.array";
    $zFiles["month"]="monthlyIps.array";

    $FilePath="/usr/share/artica-postfix/ressources/logs/nfqueue/$zFiles[$time]";
    if(!is_file($FilePath)){
        return false;
    }
    $PieData=unserialize(file_get_contents($FilePath));
    $highcharts = new highcharts();
    $highcharts->container ="pie-ips";
    $highcharts->PieDatas = $PieData;
    $highcharts->ChartType = "pie";
    $highcharts->PiePlotTitle = "";
    $highcharts->Title = $tpl->_ENGINE_parse_body("{top_sources}/{block}");
    echo $highcharts->BuildChart();
    return true;
}
function pie_categories_list():bool{
    $t=time();
    $tpl=new template_admin();
    $html[]="<table id='table-a$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{category}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hits}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $time=$_GET["pie-categories-list"];
    $zFiles["today"]="todayCategories.array";
    $zFiles["yesterday"]="yesterdayCategories.array";
    $zFiles["week"]="weeklyCategories.array";
    $zFiles["month"]="monthlyCategories.array";
    $FilePath="/usr/share/artica-postfix/ressources/logs/nfqueue/$zFiles[$time]";
    if(!is_file($FilePath)){
        return false;
    }
    $TRCLASS="";
    $PieData=unserialize(file_get_contents($FilePath));
    arsort($PieData);
    foreach ($PieData as $k=>$v){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5("$k$v");
        $ico=ico_books;
        $v=$tpl->FormatNumber($v);
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:99%' nowrap><i class=\"$ico\"></i>&nbsp;<strong>$k</strong></td>";
        $html[]="<td style='width:1%' nowrap>$v</td>";
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
    $html[]="<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-a$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function pie_ips_list():bool{
    $t=time();
    $tpl=new template_admin();
    $html[]="<table id='table-b$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hits}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $time=$_GET["pie-ips-list"];
    $zFiles["today"]="todayIps.array";
    $zFiles["yesterday"]="yesterdayIps.array";
    $zFiles["week"]="weeklyIps.array";
    $zFiles["month"]="monthlyIps.array";

    $FilePath="/usr/share/artica-postfix/ressources/logs/nfqueue/$zFiles[$time]";
    if(!is_file($FilePath)){
        return false;
    }
    $TRCLASS="";
    $PieData=unserialize(file_get_contents($FilePath));
    arsort($PieData);
    foreach ($PieData as $k=>$v){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5("$k$v");
        $ico=ico_computer;
        $v=$tpl->FormatNumber($v);
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:99%' nowrap><i class=\"$ico\"></i>&nbsp;<strong>$k</strong></td>";
        $html[]="<td style='width:1%' nowrap>$v</td>";
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
    $html[]="<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-b$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
