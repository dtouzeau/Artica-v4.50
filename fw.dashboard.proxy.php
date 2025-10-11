<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.sqstats.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["bandwidth-graph-today"])){bandwidth_graph_today();exit;}
if(isset($_GET["bandwidth-hits-today"])){bandwidth_hits_today();exit;}
if(isset($_GET["bandwidth-users-today"])){bandwidth_users_today();exit;}



page();

function page(){

    $page=CurrentPageName();
    $t=md5(time());

    echo "<div id='$t'></div>
    <script>LoadAjax('$t','$page?table=yes&t=$t')</script>
    ";




}


function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td valign='top'>";
    $html[]="    <table style='width:100%;'>";
    $html[]="    <tr>";


    $xkey = $tpl->time_key_10mn();
    $KeyTotalHits = "WebStats:$xkey:TotalHits";
    $KeyTotalSize = "WebStats:$xkey:TotalSize";
    $KeyUsersList = "WebStats:$xkey:CurrentUsers";
    $KeyDomainsList = "WebStats:$xkey:CurrentDomains";

    $redis = new Redis();
    try {
        $redis->connect('/var/run/redis/redis.sock');
    } catch (Exception $e) {
        echo "<div class='alert alert-danger' style='margin-top:30px'>" . $e->getMessage() . "</div>";
        exit;
    }
    try {
        $TotalHits = $redis->get($KeyTotalHits);
        $TotalSize = $redis->get($KeyTotalSize);
    }catch (Exception $e) {
            echo "<div class='alert alert-danger' style='margin-top:30px'>" . $e->getMessage() . "</div>";
            exit;
        }

    $squidstat=new squidstat();
    if(!$squidstat->connect()){
        $html[]="<td valign='top' style='padding:15px'>";
        $html[]=$tpl->widget_h("yellow","fas fa-users","??","{proxy_members}");
        $html[]="</td>";
    }else {
        $data = $squidstat->makeQuery();
        $squidstat->ReturnOnlyTitle=true;
        $Members = $squidstat->makeHtmlReport($data,false,$hoss,"host");
        $html[]="<td valign='top' style='padding:15px'>";
        $html[]=$tpl->widget_h("green","fas fa-users",$Members,"{proxy_members}");
        $html[]="</td>";
    }


    $Domains = count($redis->sMembers($KeyDomainsList));



    $html[]="<td valign='top' style='padding:15px'>";
    $html[]=$tpl->widget_h("green","far fa-poo-storm",FormatNumber($TotalHits)."/".FormatNumber($Domains),"{requests}/{domains}");
    $html[]="</td>";

    $html[]="<td valign='top' style='padding:15px'>";
    $html[]=$tpl->widget_h("green","fab fa-mixcloud",FormatBytes($TotalSize/1024),"{bandwidth}");
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td colspan='3'><div id='bandwidth-graph-today'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td colspan='3'><div id='bandwidth-hits-today'></div></td>";
    $html[]="</tr>";

    $html[]="<tr>";
    $html[]="<td colspan='3'><div id='bandwidth-users-today'></div></td>";
    $html[]="</tr>";

    $html[]="</table>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";

    $html[]="<script>";
    $html[]="Loadjs('$page?bandwidth-graph-today=yes&container=bandwidth-graph-today');";
    $html[]="Loadjs('$page?bandwidth-hits-today=yes&container=bandwidth-hits-today');";
    $html[]="Loadjs('$page?bandwidth-users-today=yes&container=bandwidth-users-today');";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);

}


function bandwidth_graph_today(){


    $q=new postgres_sql();
    $zmd5=$_GET["zmd5"];
    $page=CurrentPageName();
    $time=time();
    $today=date("Y-m-d 00:00:00");
    $results=$q->QUERY_SQL("SELECT size, zdate FROM \"bandwidth_table\" WHERE zdate>'$today' order by zdate ASC ");




    while($ligne=@pg_fetch_assoc($results)){
        $size=$ligne["size"];
        $size=$size/1024;
        $size=round($size/1024);
        $time=strtotime($ligne["zdate"]);
        $xdata[]=date("H:i",$time);
        $ydata[]=$size;
    }


    $title="{downloaded_flow} (MB) {today}";
    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->container=$_GET["container"];
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

function bandwidth_hits_today(){
    $q=new postgres_sql();
    $zmd5=$_GET["zmd5"];
    $page=CurrentPageName();
    $time=time();
    $today=date("Y-m-d 00:00:00");
    $results=$q->QUERY_SQL("SELECT hits, zdate FROM \"bandwidth_table\" WHERE zdate>'$today' order by zdate ASC ");




    while($ligne=@pg_fetch_assoc($results)){
        $size=$ligne["hits"];
        $time=strtotime($ligne["zdate"]);
        $xdata[]=date("H:i",$time);
        $ydata[]=$size;
    }


    $title="{hits} {today}";
    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->AreaColor="#3B5998";
    $highcharts->container=$_GET["container"];
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="{hits}";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="{hits}";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{hits}"=>$ydata);
    echo $highcharts->BuildChart();

}
function bandwidth_users_today(){
    $q=new postgres_sql();
    $zmd5=$_GET["zmd5"];
    $page=CurrentPageName();
    $time=time();
    $today=date("Y-m-d 00:00:00");
    $results=$q->QUERY_SQL("SELECT count(userid) as users, zdate FROM \"access_users\" WHERE zdate>'$today' GROUP BY zdate order by zdate ASC ");




    while($ligne=@pg_fetch_assoc($results)){
        $size=$ligne["users"];
        $time=strtotime($ligne["zdate"]);
        $xdata[]=date("H:i",$time);
        $ydata[]=$size;

    }


    $title="{members} {today}";
    $highcharts=new highcharts();
    $highcharts->container=$_GET["container"];
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="{members}";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="{members}";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{members}"=>$ydata);
    echo $highcharts->BuildChart();

}




function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}


