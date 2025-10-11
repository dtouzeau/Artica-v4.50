<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__).'/ressources/class.highcharts.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["stats"])){stats();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["build-stats-cnx"])){build_stats_cnx();exit;}
if(isset($_GET["build-stats-users"])){build_stats_users();exit;}
if(isset($_GET["week"])){stats_week();exit;}
if(isset($_GET["build-week-users"])){build_week_users();exit;}
if(isset($_GET["build-week-cnx"])){build_week_cnx();exit;}


page();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();


    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_PROXY_PAC}</h1>
	<p>{statistics}</p>
	</div>

	</div>



	<div class='row'><div id='progress-proxypac-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-proxypac-stats'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/proxypac-statistics');	
	LoadAjax('table-loader-proxypac-stats','$page?tabs=yes');

	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_PROXY_PAC} {statistics}",$html);
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);

}

function stats(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $day=$_GET["stats"];
    $t=time();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td valign='top'><div id='$t-cnx'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td valign='top'><div id='$t-users'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="Loadjs('$page?build-stats-cnx=$day&container=$t-cnx');";
    $html[]="Loadjs('$page?build-stats-users=$day&container=$t-users');";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);



}
function stats_week(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $t=time();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td valign='top'><div id='$t-cnx'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td valign='top'><div id='$t-users'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="Loadjs('$page?build-week-cnx=yes&container=$t-cnx');";
    $html[]="Loadjs('$page?build-week-users=yes&container=$t-users');";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);



}
function build_week_cnx(){


    $tpl=new template_admin();
    $dates=$tpl->time_to_week(time());

    $q=new postgres_sql();
    $title="{clients}";
    $sql="SELECT AVG(requests) as rqs,date_trunc('day',zdate) as zdate FROM proxypac_stats 
            WHERE date_trunc('day',zdate)>='{$dates[0]}' AND date_trunc('day',zdate)<='{$dates[1]}' GROUP BY date_trunc('day',zdate) ";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo "//$q->mysql_error\n";}
    while($ligne=@pg_fetch_assoc($results)){
        echo "//{$ligne["zdate"]} {$ligne["rqs"]}\n";
        $time=strtotime($ligne["zdate"]);

        $xdata[]=$tpl->_ENGINE_parse_body(date("{l}",$time));
        $ydata[]=$ligne["rqs"];
    }

    $timetext="{days}";
    $highcharts=new highcharts();
    $highcharts->container=$_GET["container"];
    $highcharts->xAxis=$xdata;

    $highcharts->Title=$title." {$dates[0]} - {$dates[1]}";
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="{requests} ";
    $highcharts->xAxis_labels=false;
    $highcharts->LegendPrefix="$day ";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->LegendSuffix=" {requests}";
    $highcharts->datas=array("{clients}"=>$ydata);
    $highcharts->OnErrorEvent=$sql;
    echo $highcharts->BuildChart();

}
function build_week_users(){


    $tpl=new template_admin();
    $dates=$tpl->time_to_week(time());

    $q=new postgres_sql();
    $title="{clients}";
    $sql="SELECT AVG(clients) as rqs,date_trunc('day',zdate) as zdate FROM proxypac_stats 
            WHERE date_trunc('day',zdate)>='{$dates[0]}' AND date_trunc('day',zdate)<='{$dates[1]}' GROUP BY date_trunc('day',zdate) ";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo "//$q->mysql_error\n";}
    while($ligne=@pg_fetch_assoc($results)){
        echo "//{$ligne["zdate"]} {$ligne["rqs"]}\n";
        $time=strtotime($ligne["zdate"]);

        $xdata[]=$tpl->_ENGINE_parse_body(date("{l}",$time));
        $ydata[]=$ligne["rqs"];
    }

    $timetext="{days}";
    $highcharts=new highcharts();
    $highcharts->container=$_GET["container"];
    $highcharts->xAxis=$xdata;

    $highcharts->Title=$title." {$dates[0]} - {$dates[1]}";
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="{members} ";
    $highcharts->xAxis_labels=false;
    $highcharts->LegendPrefix="$day ";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->LegendSuffix=" {members}";
    $highcharts->datas=array("{clients}"=>$ydata);
    $highcharts->OnErrorEvent=$sql;
    echo $highcharts->BuildChart();

}


function build_stats_users(){

    $day=$_GET["build-stats-users"];

    $q=new postgres_sql();
    $title="{clients}";
    $sql="SELECT SUM(clients) as rqs,zdate FROM proxypac_stats WHERE date_trunc('day',zdate)='$day' GROUP BY zdate ";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo "//$q->mysql_error\n";}
    while($ligne=@pg_fetch_assoc($results)){
        echo "//{$ligne["zdate"]} {$ligne["rqs"]}\n";
        $time=strtotime($ligne["zdate"]);

        $xdata[]=date("H:i",$time);
        $ydata[]=$ligne["rqs"];
    }


    $timetext="{minutes}";
    $highcharts=new highcharts();
    $highcharts->container=$_GET["container"];
    $highcharts->xAxis=$xdata;

    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="{members}";
    $highcharts->xAxis_labels=false;
    $highcharts->LegendPrefix="$day ";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->LegendSuffix=" {members}";
    $highcharts->datas=array("{clients}"=>$ydata);
    $highcharts->OnErrorEvent=$sql;
    echo $highcharts->BuildChart();

}

function build_stats_cnx(){

    $day=$_GET["build-stats-cnx"];

    $q=new postgres_sql();
    $title="{requests}";
    $sql="SELECT SUM(requests) as rqs,zdate FROM proxypac_stats WHERE date_trunc('day',zdate)='$day' GROUP BY zdate ";
    $results=$q->QUERY_SQL($sql);
if(!$q->ok){echo "//$q->mysql_error\n";}
    while($ligne=@pg_fetch_assoc($results)){
        echo "//{$ligne["zdate"]} {$ligne["rqs"]}\n";
        $time=strtotime($ligne["zdate"]);

        $xdata[]=date("H:i",$time);
        $ydata[]=$ligne["rqs"];
    }


    $timetext="{minutes}";
    $highcharts=new highcharts();
    $highcharts->container=$_GET["container"];
    $highcharts->xAxis=$xdata;

    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="{requests}";
    $highcharts->xAxis_labels=false;
    $highcharts->LegendPrefix="$day ";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->LegendSuffix=" {requests}";
    $highcharts->datas=array("{requests}"=>$ydata);
    $highcharts->OnErrorEvent=$sql;
    echo $highcharts->BuildChart();


}

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sock=new sockets();
    $today=date("Y-m-d");
    $yesterday=date("Y-m-d",strtotime("yesterday"));
    $array["{today}"]="$page?stats=$today";
    $array["{yesterday}"]="$page?stats=$yesterday";
    $array["{this_week}"]="$page?week=yes";
    echo $tpl->tabs_default($array);

}