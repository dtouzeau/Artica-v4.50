<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
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
    include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td valign='top'>";
    $html[]="    <table style='width:100%;'>";
    $html[]="    <tr>";


    $GRAPH_SENTANDREFUSED_SMTP_TODAY=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GRAPH_SENTANDREFUSED_SMTP_TODAY"));

    if(!isset($GRAPH_SENTANDREFUSED_SMTP_TODAY["SUM"]["SENT"])){
        $GRAPH_SENTANDREFUSED_SMTP_TODAY["SUM"]["SENT"]=0;
    }
    if(!isset($GRAPH_SENTANDREFUSED_SMTP_TODAY["SUM"]["REFUSED"])){
        $GRAPH_SENTANDREFUSED_SMTP_TODAY["SUM"]["REFUSED"]=0;
    }

    $TotalSent =  $GRAPH_SENTANDREFUSED_SMTP_TODAY["SUM"]["SENT"];
    $TotalRefused = $GRAPH_SENTANDREFUSED_SMTP_TODAY["SUM"]["REFUSED"];
    $score = 0;

    if ($TotalRefused != 0) {
        $score = round(100 - (($TotalSent / $TotalRefused) * 100));
    }

    $main=new maincf_multi("master","master");
    $freeze_delivery_queue=intval($main->GET("freeze_delivery_queue"));
    if($freeze_delivery_queue==1) {
        $html[]="<td valign='top' style='padding:15px' width='33%'>";
        $html[]=$tpl->widget_h("red","fas fa-stop-circle","{WARN_QUEUE_FREEZE}","{WARN_QUEUE_FREEZE}");
        $html[]="</td>";

    }else{

        $html[]="<td valign='top' style='padding:15px' width='33%'>";
        $html[]=$tpl->widget_h("green","fa-thumbs-up",FormatNumber($TotalSent),"{sended_messages}");
        $html[]="</td>";

    }



    $html[]="<td valign='top' style='padding:15px' width='33%'>";
    $html[]=$tpl->widget_h("red","fa-thumbs-down",FormatNumber($TotalRefused),"{rejected_messages}");
    $html[]="</td>";

    $html[]="<td valign='top' style='padding:15px' width='33%'>";
    $html[]=$tpl->widget_h("yellow","fas fa-percent","{$score}%","{spam_rate}");
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

    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);

}


function bandwidth_graph_today(){


    $GRAPH_SENTANDREFUSED_SMTP_TODAY=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GRAPH_SENTANDREFUSED_SMTP_TODAY"));

    if(!isset($GRAPH_SENTANDREFUSED_SMTP_TODAY["SENT"]["xdata"])){
        $GRAPH_SENTANDREFUSED_SMTP_TODAY["SENT"]["xdata"]=array();
    }


    if(is_null($GRAPH_SENTANDREFUSED_SMTP_TODAY["SENT"]["ydata"])){
        $GRAPH_SENTANDREFUSED_SMTP_TODAY["SENT"]["ydata"]=array();
    }


    $title="{sended_messages} {today}";
    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->container=$_GET["container"];
    $highcharts->xAxis=$GRAPH_SENTANDREFUSED_SMTP_TODAY["SENT"]["xdata"];
    $highcharts->Title=$title;
    $highcharts->AreaColor="#1ab394";
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="{messages}";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="{messages}";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{messages}"=>$GRAPH_SENTANDREFUSED_SMTP_TODAY["SENT"]["ydata"]);
    echo $highcharts->BuildChart();


}

function bandwidth_hits_today(){

    $GRAPH_SENTANDREFUSED_SMTP_TODAY=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GRAPH_SENTANDREFUSED_SMTP_TODAY"));

    if(!isset($GRAPH_SENTANDREFUSED_SMTP_TODAY["DENY"])){
        $GRAPH_SENTANDREFUSED_SMTP_TODAY["DENY"]["xdata"]=array();
        $GRAPH_SENTANDREFUSED_SMTP_TODAY["DENY"]["ydata"]=array();
    }
    if(!isset($_GET["interval"])){
        $_GET["interval"]="";
    }

    $title="{refusedSMTP} {today}";
    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->AreaColor="#ed5565";
    $highcharts->container=$_GET["container"];
    $highcharts->xAxis=$GRAPH_SENTANDREFUSED_SMTP_TODAY["DENY"]["xdata"];;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="{refused}";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="{refused}";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{rejected_messages}"=>$GRAPH_SENTANDREFUSED_SMTP_TODAY["DENY"]["ydata"]);
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


