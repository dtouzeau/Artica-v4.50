<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["doughnut-processes-mem"])){doughnut_ps_mem();exit;}
if(isset($_GET["zoom-js"])){zoom_js();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["time"])){table();exit;}
if(isset($_POST["overcommit_memory"])){Save();exit;}
if(isset($_GET["memory-graph"])){memory_graph();exit;}
if(isset($_GET["memory-graph2"])){memory_graph2();exit;}
if(isset($_GET["zoom-popup"])){zoom_popup();exit;}
if(isset($_GET["zoom-today"])){zoom_popup_today();exit;}
if(isset($_GET["zoom-hour"])){zoom_popup_hour();exit;}
if(isset($_GET["zoom-yesterday"])){zoom_popup_yesterday();exit;}
if(isset($_GET["zoom-week"])){zoom_popup_week();exit;}
if(isset($_GET["zoom-month"])){zoom_popup_month();exit;}
if(isset($_GET["zoom-lastweek"])){zoom_popup_lastweek();exit;}



start();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header(
        "{memory_info}","fad fa-memory",
        "{memory_info_text}","$page?tabs=yes","system-memory","progress-system-memory",false,"table-system-memory");



    if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return;}
    echo $tpl->_ENGINE_parse_body($html);

}

function zoom_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $proc=$_GET["zoom-js"];
    $intervall=$_GET["interval"];
    $procEnco=urlencode($proc);
    return $tpl->js_dialog2("{{$proc}} {{$intervall}}","$page?zoom-popup=$procEnco&interval=$intervall",1024);
}
function zoom_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $proc=urlencode($_GET["zoom-popup"]);
    $intervall=$_GET["interval"];

    $html[]="<div id='zoom-popup'></div>";

    if($intervall=="yesterday") {
        $html[] = "<script>Loadjs('$page?zoom-yesterday=$proc');</script>')";
    }
    if($intervall=="today") {
        $html[] = "<script>Loadjs('$page?zoom-today=$proc');</script>')";
    }
    if($intervall=="hour") {
        $html[] = "<script>Loadjs('$page?zoom-hour=$proc');</script>')";
    }
    if($intervall=="week") {
        $html[] = "<script>Loadjs('$page?zoom-week=$proc');</script>')";
    }
    if($intervall=="month") {
        $html[] = "<script>Loadjs('$page?zoom-month=$proc');</script>')";
    }
    if($intervall=="lastweek") {
        $html[] = "<script>Loadjs('$page?zoom-lastweek=$proc');</script>')";
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function zoom_popup_lastweek():bool{
    $tpl=new template_admin();
    $q=new postgres_sql();
    $startLastWeek = date('Y-m-d 00:00:00', strtotime('monday last week'));
    $startThisWeek = date('Y-m-d 00:00:00', strtotime('monday this week'));
    $proc=$_GET["zoom-lastweek"];
    $sql="SELECT date_trunc('day', zdate) AS tdate,
            AVG(mem) AS mem,date_trunc('day', zdate) as zdate
            FROM ps_mem
            WHERE date_trunc('day', zdate) >= '$startLastWeek'
            AND date_trunc('day', zdate) < '$startThisWeek'
            AND proc='$proc'
        GROUP BY date_trunc('day', zdate)
        ORDER BY date_trunc('day', zdate) ASC";
    $results=$q->QUERY_SQL($sql);

    while ($ligne = pg_fetch_assoc($results)) {
        $date=strtotime($ligne["zdate"]);
        $xdata[]=$tpl->_ENGINE_parse_body(date("{l} d",$date));
        $ydata[]=round($ligne["mem"]/1024,2);
    }


    $highcharts=new highcharts();
    $highcharts->container="zoom-popup";
    $highcharts->xAxis=$xdata;
    $highcharts->Title="{{$proc}}";
    $highcharts->TitleFontSize="22px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="MB";
    $highcharts->xAxis_labels=false;
    $highcharts->LegendPrefix=$tpl->javascript_parse_text("{time}: ");
    $highcharts->LegendSuffix="MB";
    $highcharts->xAxisTtitle="{time}";
    $highcharts->datas=array("{memory_usage}"=>$ydata);
    echo $highcharts->BuildChart();
    return true;
}
function zoom_popup_month(){
    $tpl=new template_admin();
    $q=new postgres_sql();
    $thisMonth = date("Y-m-01 00:00:00");
    $proc=$_GET["zoom-month"];
    $sql="SELECT AVG(mem) as mem,date_trunc('day', zdate) as zdate FROM ps_mem
    WHERE date_trunc('day', zdate) >= '$thisMonth'
    AND date_trunc('day', zdate) < ('$thisMonth'::timestamp + interval '1 month')
    AND proc='$proc'
    GROUP BY date_trunc('day', zdate) ORDER BY date_trunc('day', zdate) ASC ";
    $results=$q->QUERY_SQL($sql);

    while ($ligne = pg_fetch_assoc($results)) {
        $date=strtotime($ligne["zdate"]);
        $xdata[]=$tpl->_ENGINE_parse_body(date("{l} d",$date));
        $ydata[]=round($ligne["mem"]/1024,2);
    }


    $highcharts=new highcharts();
    $highcharts->container="zoom-popup";
    $highcharts->xAxis=$xdata;
    $highcharts->Title="{{$proc}}";
    $highcharts->TitleFontSize="22px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="MB";
    $highcharts->xAxis_labels=false;
    $highcharts->LegendPrefix=$tpl->javascript_parse_text("{time}: ");
    $highcharts->LegendSuffix="MB";
    $highcharts->xAxisTtitle="{time}";
    $highcharts->datas=array("{memory_usage}"=>$ydata);
    echo $highcharts->BuildChart();
}
function zoom_popup_week(){
    $tpl=new template_admin();
    $q=new postgres_sql();
    $thisWeek = date("Y-m-d 00:00:00", strtotime("monday this week"));
    $proc=$_GET["zoom-week"];
    $sql="SELECT AVG(mem) AS mem,date_trunc('day',zdate) as zdate FROM ps_mem
            WHERE date_trunc('day', zdate) >= '$thisWeek'
            AND date_trunc('day', zdate) < ('$thisWeek'::timestamp + interval '7 days')
            AND proc='$proc'
            GROUP BY date_trunc('day',zdate) ORDER BY date_trunc('day',zdate) ASC";
    $results=$q->QUERY_SQL($sql);

    while ($ligne = pg_fetch_assoc($results)) {
        $date=strtotime($ligne["zdate"]);
        $xdata[]=$tpl->_ENGINE_parse_body(date("{l}",$date));
        $ydata[]=round($ligne["mem"]/1024,2);
    }


    $highcharts=new highcharts();
    $highcharts->container="zoom-popup";
    $highcharts->xAxis=$xdata;
    $highcharts->Title="{{$proc}}";
    $highcharts->TitleFontSize="22px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="MB";
    $highcharts->xAxis_labels=false;
    $highcharts->LegendPrefix=$tpl->javascript_parse_text("{time}: ");
    $highcharts->LegendSuffix="MB";
    $highcharts->xAxisTtitle="{time}";
    $highcharts->datas=array("{memory_usage}"=>$ydata);
    echo $highcharts->BuildChart();
}
function zoom_popup_yesterday(){
    $tpl=new template_admin();
    $q=new postgres_sql();
    $yesterday = date("Y-m-d 00:00:00", strtotime("-1 day"));
    $proc=$_GET["zoom-yesterday"];
    $sql="SELECT zdate,mem FROM ps_mem WHERE date_trunc('day',zdate)='$yesterday' AND proc='$proc' ORDER BY zdate";
    $results=$q->QUERY_SQL($sql);

    while ($ligne = pg_fetch_assoc($results)) {
        $date=strtotime($ligne["zdate"]);
        $xdata[]=date("H:i",$date);
        $ydata[]=round($ligne["mem"]/1024,2);
    }


    $highcharts=new highcharts();
    $highcharts->container="zoom-popup";
    $highcharts->xAxis=$xdata;
    $highcharts->Title="{{$proc}}";
    $highcharts->TitleFontSize="22px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="MB";
    $highcharts->xAxis_labels=false;
    $highcharts->LegendPrefix=$tpl->javascript_parse_text("{time}: ");
    $highcharts->LegendSuffix="MB";
    $highcharts->xAxisTtitle="{time}";
    $highcharts->datas=array("{memory_usage}"=>$ydata);
    echo $highcharts->BuildChart();

}
function zoom_popup_today(){
    $tpl=new template_admin();
    $q=new postgres_sql();
    $today = date("Y-m-d 00:00:00");
    $proc=$_GET["zoom-today"];
    $sql="SELECT zdate,mem FROM ps_mem WHERE date_trunc('day',zdate)='$today' AND proc='$proc' ORDER BY zdate";
    $results=$q->QUERY_SQL($sql);

    while ($ligne = pg_fetch_assoc($results)) {
        $date=strtotime($ligne["zdate"]);
        $xdata[]=date("H:i",$date);
        $ydata[]=round($ligne["mem"]/1024,2);
    }


    $highcharts=new highcharts();
    $highcharts->container="zoom-popup";
    $highcharts->xAxis=$xdata;
    $highcharts->Title="{{$proc}}";
    $highcharts->TitleFontSize="22px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="MB";
    $highcharts->xAxis_labels=false;
    $highcharts->LegendPrefix=$tpl->javascript_parse_text("{time}: ");
    $highcharts->LegendSuffix="MB";
    $highcharts->xAxisTtitle="{time}";
    $highcharts->datas=array("{memory_usage}"=>$ydata);
    echo $highcharts->BuildChart();

}
function zoom_popup_hour(){
    $tpl=new template_admin();
    $q=new postgres_sql();
    $currentHour = date("Y-m-d H:00:00");
    $proc=$_GET["zoom-hour"];
    $sql="SELECT zdate,mem FROM ps_mem WHERE date_trunc('hour', zdate)='$currentHour' AND proc='$proc' ORDER BY zdate";
    $results=$q->QUERY_SQL($sql);

    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
        return true;
    }

    while ($ligne = pg_fetch_assoc($results)) {
        $date=strtotime($ligne["zdate"]);
        $xdata[]=date("i",$date)."mn";
        $ydata[]=round($ligne["mem"]/1024,2);
    }


    $highcharts=new highcharts();
    $highcharts->container="zoom-popup";
    $highcharts->xAxis=$xdata;
    $highcharts->Title="{{$proc}}";
    $highcharts->TitleFontSize="22px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="MB";
    $highcharts->xAxis_labels=false;
    $highcharts->LegendPrefix=$tpl->javascript_parse_text("{time}: ");
    $highcharts->LegendSuffix="MB";
    $highcharts->xAxisTtitle="{time}";
    $highcharts->datas=array("{memory_usage}"=>$ydata);
    echo $highcharts->BuildChart();
}
function start(){

    if(!isset($_GET["time"])){$_GET["time"]="today";}
    $page=CurrentPageName();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:240px;vertical-align: top;padding:5px'><canvas id=\"doughnut-processes-mem\" width=250px height=250px style=\"margin: 0 auto 0\"></canvas>";

    $html[]="</td>";
    $html[]="<td style='width:99%'><div id='system-memory-processes-container'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";

    $html[]="<script type='text/javascript'>";
    $html[]="LoadAjax('system-memory-processes-container','$page?time={$_GET["time"]}');";
    $html[]="</script>";
    echo implode("",$html);
}

function getQuery($time){


     if($time=="lastweek") {
             $startLastWeek = date('Y-m-d 00:00:00', strtotime('monday last week'));
             $startThisWeek = date('Y-m-d 00:00:00', strtotime('monday this week'));
             return "SELECT date_trunc('day', zdate) AS tdate,
                 AVG(mem) AS mem,
                 proc
            FROM ps_mem
           WHERE date_trunc('day', zdate) >= '$startLastWeek'
             AND date_trunc('day', zdate) < '$startThisWeek'
        GROUP BY proc, date_trunc('day', zdate)
        ORDER BY AVG(mem) DESC";
     }

    if($time=="hour") {
        $currentHour = date("Y-m-d H:00:00");
        return  "SELECT AVG(mem) AS mem, proc FROM ps_mem WHERE date_trunc('hour', zdate) = '$currentHour' GROUP BY proc ORDER BY AVG(mem) DESC";
    }

    if($time=="today") {
        $today = date("Y-m-d 00:00:00");
        return "SELECT AVG(mem) as mem, proc FROM ps_mem WHERE date_trunc('day',zdate)='$today' GROUP by proc ORDER BY AVG(mem) DESC";
    }
    if($time=="yesterday") {
        $yesterday = date("Y-m-d 00:00:00", strtotime("-1 day"));
        return "SELECT AVG(mem) as mem, proc FROM ps_mem WHERE date_trunc('day',zdate)='$yesterday' GROUP by proc ORDER BY AVG(mem) DESC";
    }

    if($time=="week") {
        $thisWeek = date("Y-m-d 00:00:00", strtotime("monday this week"));
        return "SELECT AVG(mem) AS mem,proc FROM ps_mem
            WHERE date_trunc('day', zdate) >= '$thisWeek'
            AND date_trunc('day', zdate) < ('$thisWeek'::timestamp + interval '7 days')
            GROUP BY proc ORDER BY AVG(mem) DESC";

    }
    if($time=="month") {
        $thisMonth = date("Y-m-01 00:00:00");

// 2) Build the query to include only days >= $thisMonth and < $thisMonth + 1 month
        return "SELECT AVG(mem) AS mem,proc FROM ps_mem
    WHERE date_trunc('day', zdate) >= '$thisMonth'
      AND date_trunc('day', zdate) < ('$thisMonth'::timestamp + interval '1 month')
    GROUP BY proc ORDER BY AVG(mem) DESC
";
    }

    return "";

}

function table():bool{
    $t=time();
    $TRCLASS="";
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();

    $time=$_GET["time"];

    $results = $q->QUERY_SQL(getQuery($time));
    if(!$q->ok){
       echo $tpl->div_error($q->mysql_error);
       return false;
    }
    $html[]="<div style='margin-top: 15px'>";

    if($time=="hour") {
        $html[] = "<H2>{this_hour}</H2>";
    }
    if($time=="month") {
        $html[] = "<H2>{this_month}</H2>";
    }
    if($time=="today") {
        $html[] = "<H2>{today}</H2>";
    }
    if($time=="week") {
        $html[] = "<H2>{this_week}</H2>";
    }
    if($time=="yesterday") {
        $html[] = "<H2'>{yesterday}</H2>";
    }
    if($time=="lastweek") {
        $html[] = "<H2>{last_week}</H2>";
    }
    $html[]="</div>";
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{process}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{memory}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    while($ligne=@pg_fetch_array($results)){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $proc=$ligne["proc"];
        $procEnc=urlencode($proc);
        $mem=FormatBytes($ligne["mem"]);
        $ico=ico_mem;

        $js="Loadjs('$page?zoom-js=$procEnc&interval=$time');";
        $proc=$tpl->td_href($proc,"",$js);
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap><i class='$ico'></i></td>";
        $html[]="<td style='width:99%' nowrap>$proc</td>";
        $html[]="<td style='width:1%;text-align: right' nowrap>$mem</td>";
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='3'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";

    $topbuttons[] = array("LoadAjax('system-memory-processes-container','$page?time=lastweek');", ico_mem, "{last_week}");
    $topbuttons[] = array("LoadAjax('system-memory-processes-container','$page?time=yesterday');", ico_mem, "{yesterday}");
    $topbuttons[] = array("LoadAjax('system-memory-processes-container','$page?time=hour');", ico_mem, "{this_hour}");
    $topbuttons[] = array("LoadAjax('system-memory-processes-container','$page?time=today');", ico_mem, "{today}");
    $topbuttons[] = array("LoadAjax('system-memory-processes-container','$page?time=week');", ico_mem, "{this_week}");
    $topbuttons[] = array("LoadAjax('system-memory-processes-container','$page?time=month');", ico_mem, "{this_month}");

    $TINY_ARRAY["TITLE"]="{memory_info}";
    $TINY_ARRAY["ICO"]="fad fa-memory";
    $TINY_ARRAY["EXPL"]="{memory_info_text}";
    $TINY_ARRAY["URL"]="system-memory";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": false },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	Loadjs('$page?doughnut-processes-mem=yes&time=$time');
        $jstiny
	</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function doughnut_ps_mem():bool{
    $tpl=new template_admin();
    $id         ="doughnut-processes-mem";
    $TOTAL = 0;

    $time=$_GET["time"];
    $q=new postgres_sql();
    $results = $q->QUERY_SQL(getQuery($time));
    if(!$q->ok){
        return false;
    }

    $c=0;
    while($ligne=@pg_fetch_array($results)) {
        $proc = $ligne["proc"];
        $MAIN[$ligne["mem"]]=$proc;
        $TOTAL=$TOTAL+$ligne["mem"];
        $c++;
        if($c==10){
            break;
        }
    }


    if(!is_array($MAIN)){$MAIN=array();}

    krsort($MAIN);
    $sizeu=0;
    $i=0;
    $colorz[]="#a383d5";
    $colorz[]="#8783d5";
    $colorz[]="#8399d5";
    $colorz[]="#9bc2da";
    $colorz[]="#9bdab5";
    $colorz[]="#bdda9b";
    $colorz[]="#dada9b";
    $colorz[]="#dac59b";
    $colorz[]="#dab09b";
    $colorz[]="#da9b9b";
    $colorz[]="#da9cb1";
    $colorz[]="#da9cc5";
    $colorz[]="#da9cda";
    $colorz[]="#c59cda";
    $colorz[]="#00aa7f";


    foreach ($MAIN as $size=>$proc){
        $sizeu=$sizeu+$size;
        $size=$size/1024;
        $proc=str_replace("artica-phpfpm",$tpl->_ENGINE_parse_body("{APP_FRAMEWORK}"),$proc);
        $proc=str_replace("postgres",$tpl->_ENGINE_parse_body("{APP_POSTGRES}"),$proc);
        $proc=str_replace("nginx",$tpl->_ENGINE_parse_body("{APP_NGINX}"),$proc);
        $proc=str_replace("squid",$tpl->_ENGINE_parse_body("{APP_SQUID}"),$proc);
        $proc=str_replace("rsyslogd",$tpl->_ENGINE_parse_body("{APP_SYSLOG}"),$proc);
        $proc=str_replace("articarest",$tpl->_ENGINE_parse_body("{SQUID_AD_RESTFULL}"),$proc);
        $proc=str_replace("memcached",$tpl->_ENGINE_parse_body("{APP_MEMCACHED}"),$proc);
        $proc=str_replace("php7.3",$tpl->_ENGINE_parse_body("{APP_PHP5}"),$proc);
        $proc=str_replace("php7.4",$tpl->_ENGINE_parse_body("{APP_PHP5}"),$proc);
        $proc=str_replace("php8.2",$tpl->_ENGINE_parse_body("{APP_PHP5}"),$proc);
        $proc=str_replace("slapd",$tpl->_ENGINE_parse_body("{APP_OPENLDAP}"),$proc);
        $proc=str_replace("unbound",$tpl->_ENGINE_parse_body("{APP_UNBOUND}"),$proc);
        $proc=str_replace("crowdsec-firewall-bouncer",$tpl->_ENGINE_parse_body("{APP_IPTABLES_BOUNCER}"),$proc);
        $proc=str_replace("proxy-pac",$tpl->_ENGINE_parse_body("{APP_PROXY_PAC}"),$proc);
        $proc=str_replace("go-shield-server",$tpl->_ENGINE_parse_body("{APP_GO_SHIELD_SERVER}"),$proc);
        $proc=str_replace("go-shield-connector",$tpl->_ENGINE_parse_body("{APP_GO_SHIELD_CONNECTOR}"),$proc);
        $proc=str_replace("artica-webconsole",$tpl->_ENGINE_parse_body("{APP_ARTICAWEBCONSOLE}"),$proc);
        $proc=str_replace("dns-collector",$tpl->_ENGINE_parse_body("{APP_DNS_COLLECTOR}"),$proc);
        $proc=str_replace("artica-smtpd",$tpl->_ENGINE_parse_body("{APP_ARTICA_NOTIFIER}"),$proc);
        $proc=str_replace("crowdsec",$tpl->_ENGINE_parse_body("{APP_CROWDSEC}"),$proc);
        $proc=str_replace("sshd",$tpl->_ENGINE_parse_body("{APP_OPENSSH}"),$proc);
        $proc=str_replace("redis-server",$tpl->_ENGINE_parse_body("Redis Server"),$proc);


        $proc=html_entity_decode($proc);
        $labels[]="\"$proc\"";
        $data[]=round($size);
        $bgcolor[]="\"$colorz[$i]\"";
        $i++;
        if($i>14){break;}
    }

    $labels[]="\"Others\"";
    $size=intval($TOTAL-$sizeu);
    $size=$size/1024;
    $data[]=round($size);
    $bgcolor[]="\"#00aa7f\"";




    $t=time();
    $f[]="var doughnutData$t = {";
    $f[]="labels: [".@implode(",",$labels)."],";
    $f[]="datasets: [{";
    $f[]="data: [".@implode(",",$data)."],";
    $f[]="backgroundColor: [".@implode(",",$bgcolor)."],";
    $f[]=" }]";
    $f[]="};";


    $f[]="var doughnutOptions = {
            layout: {
            padding: {
                left: 0,
                right: 0,
                top: 0,
                bottom: 0
            }
        },
        responsive: false,
        plugins: {
        legend: {
            display: false // Ensure legends are hidden
        },
        datalabels: {
            display: false // Hide the labels (if using the datalabels plugin)
        }
    }
    
    };";

    $f[]="if(!document.getElementById('$id')){alert('$id not found');}";
    $f[]="var ctx4 = document.getElementById('$id').getContext('2d');";
    $f[]="var myChart= new Chart(ctx4, {type: 'doughnut', data: doughnutData$t, options:doughnutOptions});";
    header("content-type: application/x-javascript");
    echo @implode("\n",$f);
    return true;

}