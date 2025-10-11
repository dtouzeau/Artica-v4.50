<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");

if(isset($_GET["cnx-today"])){cnx_today();exit;}
if(isset($_GET["cpu-usage"])){cpu_usage();exit;}
if(isset($_GET["cpu-requests"])){cpu_requests();exit;}
page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    $SquidSMPConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPConfig"));
    if($CPU_NUMBER==0){
        $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->getFrameWork("services.php?CPU-NUMBER=yes"));
    }
    $kid=0;
    for($i=1;$i<$CPU_NUMBER+1;$i++){
        if(!isset($SquidSMPConfig[$i])){$CPUNumber=0;}else{$CPUNumber=intval($SquidSMPConfig[$i]);}
        if($i==1){if($CPUNumber==0){$CPUNumber=1;}}
        if($CPUNumber==1){$kid=$kid+1;}
    }


    $js=array();
    $html[]="<div id='proxy-connections-day'></div>";
    $html[]="<table style='width:100%'>";

    for($i=1;$i<$kid+1;$i++){
        $html[]="<tr>";
        $html[]="<td width='50%'><div id='cpu-$i-usage-day'></div></td>";
        $html[]="<td width='50%'><div id='cpu-$i-requests-day'></div></td>";
        $html[]="</tr>";
        $html[]="<tr><td colspan='2'><hr></td></tr>";
        $js[]="Loadjs('$page?cpu-usage=$i');";
        $js[]="Loadjs('$page?cpu-requests=$i');";
    }
    $html[]="</table>";

    $TINY_ARRAY["TITLE"]="{statistics}: {performance}";
    $TINY_ARRAY["ICO"]="fa-solid fa-jet-fighter";
    $TINY_ARRAY["EXPL"]="{proxy_service_about}";
    $TINY_ARRAY["URL"]="proxy-status";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>Loadjs('$page?cnx-today=yes');";
    $html[]=@implode("\n",$js);
    $html[]="$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);



}

function cnx_today(){
    $tpl=new template_admin();
    $today=date("Y-m-d 00:00:00");
    $q=new postgres_sql();
    $results=$q->QUERY_SQL("SELECT zdate,cnx FROM workers_cnx WHERE zdate>'$today' ORDER BY zdate");

    while ($ligne = pg_fetch_assoc($results)) {
        $date=strtotime($ligne["zdate"]);
        $xdata[]=date("H:i",$date);
        $ydata[]=$ligne["cnx"];
    }

    $title="{graph_number_of_connections}";
    $highcharts=new highcharts();
    $highcharts->container="proxy-connections-day";
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="22px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle=$_GET["legend"];
    $highcharts->xAxis_labels=false;
    $highcharts->LegendPrefix=$tpl->javascript_parse_text("{time}: ");
    $highcharts->LegendSuffix=$_GET["legend"];
    $highcharts->xAxisTtitle="{time}";
    $highcharts->datas=array("{connections}"=>$ydata);
    echo $highcharts->BuildChart();

}

function cpu_usage():bool{
    $tpl=new template_admin();
    $today=date("Y-m-d 00:00:00");
    $q=new postgres_sql();
    $cpu=intval($_GET["cpu-usage"]);
    $results=$q->QUERY_SQL("SELECT zdate,cpu_usage FROM workers_stats WHERE zdate>'$today' AND cpu='$cpu' ORDER BY zdate");

    if(!$results){
        echo $tpl->div_error($q->mysql_error);
        return false;
    }

    while ($ligne = pg_fetch_assoc($results)) {
        $date=strtotime($ligne["zdate"]);
        $xdata[]=date("H:i",$date);
        $ydata[]=$ligne["cpu_usage"];
    }

    $title="{percentage} {cpu_stat} {processor}:$cpu";
    $highcharts=new highcharts();
    $highcharts->container="cpu-$cpu-usage-day";
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="22px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle=$_GET["legend"];
    $highcharts->xAxis_labels=false;
    $highcharts->LegendPrefix=$tpl->javascript_parse_text("{time}: ");
    $highcharts->LegendSuffix=$_GET["legend"];
    $highcharts->xAxisTtitle="{time}";
    $highcharts->datas=array("{percentage} (%)"=>$ydata);
    echo $highcharts->BuildChart();
    return true;
}

function cpu_requests():bool{
    $tpl=new template_admin();
    $today=date("Y-m-d 00:00:00");
    $q=new postgres_sql();
    $cpu=intval($_GET["cpu-requests"]);
    $results=$q->QUERY_SQL("SELECT zdate,requests FROM workers_stats WHERE zdate>'$today' AND cpu='$cpu' ORDER BY zdate");

    if(!$results){
        echo $tpl->div_error($q->mysql_error);
        return false;
    }

    while ($ligne = pg_fetch_assoc($results)) {
        $date=strtotime($ligne["zdate"]);
        $xdata[]=date("H:i",$date);
        $ydata[]=$ligne["requests"];
        if($GLOBALS["VERBOSE"]){echo "$date => {$ligne["requests"]}<br>\n";}
    }

    $title="{graph_number_of_requests_seconds} {processor}:$cpu";
    $highcharts=new highcharts();
    $highcharts->container="cpu-$cpu-requests-day";
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="22px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle=$_GET["legend"];
    $highcharts->xAxis_labels=false;
    $highcharts->LegendPrefix=$tpl->javascript_parse_text("{time}: ");
    $highcharts->LegendSuffix=$_GET["legend"];
    $highcharts->xAxisTtitle="{time}";
    $highcharts->datas=array("{requests}"=>$ydata);
    echo $highcharts->BuildChart();
    return true;
}