<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(isset($_POST["none"])){exit;}
if(isset($_GET["page"])){page();exit;}
if(isset($_GET["chart1"])){chart1();exit;}
if(isset($_GET["chart2"])){chart2();exit;}
if(isset($_GET["chart3"])){chart3();exit;}
if(isset($_GET["chart-line-time"])){chart_line_time();exit;}
page();


function GET_DATAS(){
    $tpl=new template_admin();
    try {
        $redis = new Redis();
        $redis->connect("127.0.0.1", 4322, 2);
    } catch (Exception $e) {

        echo $tpl->div_error("{APP_STATS_REDIS}||".$e->getMessage());
        exit;
    }

    $datas= unserialize(base64_decode($redis->get("DASBOARD.ALLSTATS")));
    $redis->close();
    return $datas;

}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $STATS_DASHBOARD=GET_DATAS();
    $hourly_download=intval($STATS_DASHBOARD["TOTAL"]["HOURLY"]["DOWN"]);
    $week_download=$STATS_DASHBOARD["TOTAL"]["WEEKLY"]["DOWN"];
    $daily_download=$STATS_DASHBOARD["TOTAL"]["DAILY"]["DOWN"];
    $monthly_download=intval($STATS_DASHBOARD["TOTAL"]["MONTHLY"]["DOWN"]);
$tpl->CLEAN_POST();

    $currday=strtotime(date("Y-m-d 00:00:00"));
    $ArticaBackGroundColor=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaBackGroundColor"));
    if($ArticaBackGroundColor==null){$ArticaBackGroundColor="#ffffff";}

    $html[]="<div class='gray-bg' style='background-color:$ArticaBackGroundColor;width:100%;min-height:250px;margin-top:15px;padding-top:15px'>";

    $html[] = "<table style='width:100%'>";
    $html[] = "<tr>";
    if($hourly_download>0) {

        $hourly_download=FormatBytes($hourly_download/1024);


        $html[] = "<td style='width:25%'>
                        <div class=\"ibox\">
                            <div class=\"ibox-content\">
                                <h1 class=\"m-b-md\">{this_hour}</h1>
                                <h2 class=\"text-navy\">
                                     <i class=\"fas fa-download\"></i> $hourly_download&nbsp;
                                     
                                     
                                </h2>
                                 <small>{download} $hourly_download</small>
                            </div>
                        </div>
                    </td>";
    }


    if($daily_download>0){
        //<i class="fas fa-download"></i><i class="fas fa-upload"></i>
        $daily_download=FormatBytes($daily_download/1024);


        $html[] = "<td style='width:25%'>
                        <div class=\"ibox\">
                            <div class=\"ibox-content\">
                                <h1 class=\"m-b-md\">{today}</h1>
                                <h2 class=\"text-navy\">
                                    <i class=\"fas fa-download\"></i> $daily_download&nbsp;
                                    
                                    
                                    
                                </h2>
                                <small>{download} $daily_download</small>
                            </div>
                        </div>
                    </div>
                    </td>";




    }

    if($week_download>0){

        $week_download=FormatBytes($week_download/1024);


        $html[] = "<td style='width:25%'>
                        <div class=\"ibox\">
                            <div class=\"ibox-content\">
                                <h1 class=\"m-b-md\">{this_week}</h1>
                                <h2 class=\"text-navy\">
                                    <i class=\"fas fa-download\"></i> $week_download&nbsp;
                                    
                                    
                                    
                                </h2>
                                <small>{download} $week_download</small>
                            </div>
                        </div>
                    </div>
                    </td>";


    }

    if($monthly_download>0){
        $monthly_download=FormatBytes($monthly_download/1024);


        $html[] = "<td style='width:25%'>
                        <div class=\"ibox\">
                            <div class=\"ibox-content\">
                                <h1 class=\"m-b-md\">{this_month}</h1>
                                <h2 class=\"text-navy\">
                                    <i class=\"fas fa-download\"></i> $monthly_download&nbsp;
                                    
                                    
                                    
                                </h2>
                                <small>{download} $monthly_download</small>
                            </div>
                        </div>
                    </div>
                   </td>";
    }

//<i class="fas fa-chart-area"></i><i class="fas fa-user-clock"></i> <i class="fas fa-analytics"></i>

    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<div class=\"ibox float-e-margins\">";
    $html[]=" <div class=\"ibox-title\">
                        <h5 id='morris-proxy-area-chart-title'></h5>
                        <div class=\"ibox-tools\">
                           
                            <a class=\"dropdown-toggle\" data-toggle=\"dropdown\" href=\"#\">
                                <i class=\"fa fa-wrench\"></i>
                            </a>
                            <ul class=\"dropdown-menu dropdown-user\">";

    foreach ($STATS_DASHBOARD["RQ"]["WEEKLY"] as $xtime=>$none){
        $zdate=$tpl->time_to_date($xtime);
        $html[]="<li><a href=\"javascript:Loadjs('$page?chart-line-time=$xtime');\">$zdate</a></li>";
                                }
        $html[]="
                                </li>
                            </ul>
                           
                        </div>
                    </div>
                    <div class=\"ibox-content\" style=\"position: relative\">
                        <div id=\"morris-proxy-area-chart\" style='width:100%'></div>
                    </div>
                </div>";


    $html[]="<table style='width:100%'><tr>";

    $html[]="<td valign='top' width='50%'>";
    $html[]="<div class=\"ibox-title\">
                    <h5 id='morris-proxy-bar-chart-title'></h5>

                    </div>
                    <div class=\"ibox-content\" style=\"position: relative\">
                        <div id=\"morris-proxy-bar-chart\"></div>
                    </div>";
    $html[]="</td>";
    $html[]="<td valign='top' width='50%'>";
    $html[]="
                    <div class=\"ibox-title\">
                        <h5 id='morris-proxy-users-title'></h5>
                        
                    </div>
                    <div class=\"ibox-content\" style=\"position: relative\">
                        <div id=\"morris-proxy-users-chart\"></div>
                    </div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="</div>
<script>
    document.getElementById('morris-proxy-bar-chart').innerHTML='';
    document.getElementById('morris-proxy-users-chart').innerHTML='';
    document.getElementById('morris-proxy-area-chart').innerHTML='';
    Loadjs('$page?chart-line-time=$currday');
</script>

";

echo $tpl->_ENGINE_parse_body($html);



}

function chart_line_time(){
    $xtime=$_GET["chart-line-time"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $download_text=$tpl->javascript_parse_text("{download}");
    $upload_text=$tpl->javascript_parse_text("{upload}");
    $STATS_DASHBOARD=GET_DATAS();
    $HOURLY=$STATS_DASHBOARD["RQ"]["WEEKLY"][$xtime];



    foreach ($HOURLY as $time=>$array){
        $DOWN = $array["DOW"];
        $DOWN = $DOWN / 1024;
        $DOWN = $DOWN / 1024;
        $DOWN = round($DOWN);
        $stime=strtotime($time);
        $xdata[]=date("H:i",$stime);
        $ydata[]=$DOWN;
    }


    $stime=$tpl->time_to_date($xtime);
    $title="{downloaded_flow} (MB) $stime";
    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->container="morris-proxy-area-chart";
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
    $f[]="Loadjs('$page?chart2=$xtime');";



    echo @implode("\n",$f);
}



function chart2(){
    $catz=new mysql_catz();
    $xtime=$_GET["chart2"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $download_text=$tpl->javascript_parse_text("{download}");
    $upload_text=$tpl->javascript_parse_text("{upload}");
    $STATS_DASHBOARD=GET_DATAS();

    $DAILY=$STATS_DASHBOARD["RQ"]["CATEGORY"][$xtime];


    $f[]="document.getElementById('morris-proxy-bar-chart').innerHTML='';";
    $f[]="Morris.Bar({
        element: 'morris-proxy-bar-chart',
        data: [";

    foreach ($DAILY as $apps=>$array) {


        $apps = $catz->CategoryIntToStr($apps);
        $DOWN = $array["DOW"];
        $DOWN = $DOWN / 1024;
        $DOWN = $DOWN / 1024;
        $DOWN = round($DOWN);



        $f[] = "{ APPS: '$apps', down: $DOWN },";
    }
    $f[]="],
        xkey: 'APPS',
        ykeys: ['down', 'up'],
        labels: ['$download_text (MB)'],
        hideHover: 'auto',
        resize: true,
        barColors: ['#1ab394', '#cacaca'],
    });
    ";
    $stime=$tpl->time_to_date($xtime);
    $title=$tpl->_ENGINE_parse_body("$stime: {categories}");
    $title=str_replace("'","`",$title);
    $f[]="document.getElementById('morris-proxy-bar-chart-title').innerHTML='$title';";
    $f[]="Loadjs('$page?chart3=$xtime');";
    header("content-type: application/x-javascript");
    echo @implode("\n",$f);

}

function chart3(){
    $xtime=$_GET["chart3"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $download_text=$tpl->javascript_parse_text("{download}");
    $upload_text=$tpl->javascript_parse_text("{upload}");
    $STATS_DASHBOARD=GET_DATAS();

    $DAILY=$STATS_DASHBOARD["RQ"]["SRC"][$xtime];


    $f[]="document.getElementById('morris-proxy-users-chart').innerHTML='';";
    $f[]="Morris.Bar({
        element: 'morris-proxy-users-chart',
        data: [";

    foreach ($DAILY as $apps=>$array) {
        $DOWN = $array["DOW"];
        $UP = $array["UP"];
        $DOWN = $DOWN / 1024;
        $DOWN = $DOWN / 1024;
        $DOWN = round($DOWN);

        $UP = $UP / 1024;
        $UP = $UP / 1024;
        $UP = round($UP);


        $f[] = "{ APPS: '$apps', down: $DOWN, up: $UP },";
    }
    $f[]="],
        xkey: 'APPS',
        ykeys: ['down', 'up'],
        labels: ['$download_text (MB)', '$upload_text (MB)'],
        hideHover: 'auto',
        resize: true,
        barColors: ['#1ab394', '#cacaca'],
    });
    ";
    $stime=$tpl->time_to_date($xtime);
    $title=$tpl->_ENGINE_parse_body("$stime: {top_members}");
    $title=str_replace("'","`",$title);
    $f[]="document.getElementById('morris-proxy-users-title').innerHTML='$title';";

    header("content-type: application/x-javascript");
    echo @implode("\n",$f);

}
