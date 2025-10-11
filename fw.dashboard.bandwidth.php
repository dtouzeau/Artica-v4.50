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

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/kernel/info"));
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error(json_last_error_msg());
    }else{
        if($json->Status) {
            if (!$json->XnDPI) {
                $kernel = $json->Version;
                if(strlen($kernel)>3) {
                    echo $tpl->div_error("Your server kernel ($kernel) is no longer tuned to the bandwidth flow module.<br>Check whether there is a new version tuned to your kernel");
                }
            }
        }
    }



    $NDPI_DASHBOARD=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NDPI_DASHBOARD"));
    $hourly_download=intval($NDPI_DASHBOARD["TOTAL"]["HOURLY"]["DOWN"]);
    $hourly_upload=intval($NDPI_DASHBOARD["TOTAL"]["HOURLY"]["UP"]);
    $week_download=$NDPI_DASHBOARD["TOTAL"]["WEEKLY"]["DOWN"];
    $week_upload=$NDPI_DASHBOARD["TOTAL"]["WEEKLY"]["UP"];
    $daily_upload=intval($NDPI_DASHBOARD["TOTAL"]["DAILY"]["UP"]);
    $daily_download=$NDPI_DASHBOARD["TOTAL"]["DAILY"]["DOWN"];


    $monthly_download=intval($NDPI_DASHBOARD["TOTAL"]["MONTHLY"]["DOWN"]);
    $monthly_upload=intval($NDPI_DASHBOARD["TOTAL"]["MONTHLY"]["UP"]);

    $currday=strtotime(date("Y-m-d 00:00:00"));
    $ArticaBackGroundColor=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaBackGroundColor"));
    if($ArticaBackGroundColor==null){$ArticaBackGroundColor="#ffffff";}

    $html[]="<div class='gray-bg' style='background-color:$ArticaBackGroundColor;width:100%;min-height:250px;margin-top:15px;padding-top:15px'>";

    if($hourly_download>0) {

        $hourly_download=FormatBytes($hourly_download/1024);
        $hourly_upload=FormatBytes($hourly_upload/1024);

        $html[] = "<div class=\"col-lg-3\">
                        <div class=\"ibox\">
                            <div class=\"ibox-content\">
                                <h5 class=\"m-b-md\">{bandwidth} {this_hour}</h5>
                                <h2 class=\"text-navy\">
                                     <i class=\"fas fa-download\"></i> $hourly_download&nbsp;
                                     <i class=\"fas fa-upload\"></i> $hourly_upload
                                     
                                </h2><div class=\"text-navy\">
                                <i class=\"fas fa-user-clock\"></i> <a href='#' OnClick=\"Loadjs('fw.ndpi.table.hour.php');\">{search}:{time}</a></div>
                            </div>
                        </div>
                    </div>";
    }


    if($daily_download>0){
        //<i class="fas fa-download"></i><i class="fas fa-upload"></i>
        $daily_download=FormatBytes($daily_download/1024);
        $daily_upload=FormatBytes($daily_upload/1024);

        $html[] = "<div class=\"col-lg-3\">
                        <div class=\"ibox\">
                            <div class=\"ibox-content\">
                                <h5 class=\"m-b-md\">{bandwidth} {today}</h5>
                                <h2 class=\"text-navy\">
                                    <i class=\"fas fa-download\"></i> $daily_download&nbsp;
                                    <i class=\"fas fa-upload\"></i> $daily_upload
                                    
                                    
                                </h2>
                                <small>{download} $daily_download / {upload} $daily_upload</small>
                            </div>
                        </div>
                    </div>";




    }

    if($week_download>0){

        $week_download=FormatBytes($week_download/1024);
        $week_upload=FormatBytes($week_upload/1024);

        $html[] = "<div class=\"col-lg-3\">
                        <div class=\"ibox\">
                            <div class=\"ibox-content\">
                                <h5 class=\"m-b-md\">{bandwidth} {this_week}</h5>
                                <h2 class=\"text-navy\">
                                    <i class=\"fas fa-download\"></i> $week_download&nbsp;
                                    <i class=\"fas fa-upload\"></i> $week_upload
                                    
                                    
                                </h2>
                                <small>{download} $week_download / {upload} $week_upload</small>
                            </div>
                        </div>
                    </div>";


    }

    if($monthly_download>0){
        $monthly_download=FormatBytes($monthly_download/1024);
        $monthly_upload=FormatBytes($monthly_upload/1024);

        $html[] = "<div class=\"col-lg-3\">
                        <div class=\"ibox\">
                            <div class=\"ibox-content\">
                                <h5 class=\"m-b-md\">{bandwidth} {this_month}</h5>
                                <h2 class=\"text-navy\">
                                    <i class=\"fas fa-download\"></i> $monthly_download&nbsp;
                                    <i class=\"fas fa-upload\"></i> $monthly_upload
                                    
                                    
                                </h2>
                                <small>{download} $monthly_download / {upload} $monthly_upload</small>
                            </div>
                        </div>
                    </div>";
    }

//<i class="fas fa-chart-area"></i><i class="fas fa-user-clock"></i> <i class="fas fa-analytics"></i>

if(!isset($NDPI_DASHBOARD["RQ"]["WEEKLY"])){
    $NDPI_DASHBOARD["RQ"]["WEEKLY"]=array();
}
if(!is_array($NDPI_DASHBOARD["RQ"]["WEEKLY"])){
    $NDPI_DASHBOARD["RQ"]["WEEKLY"]=array();
}

    $html[]="<div class=\"ibox float-e-margins\">";
    $html[]=" <div class=\"ibox-title\">
                        <h5 id='morris-area-chart-title'></h5>
                        <div class=\"ibox-tools\">
                           
                            <a class=\"dropdown-toggle\" data-toggle=\"dropdown\" href=\"#\">
                                <i class=\"fa fa-wrench\"></i>
                            </a>
                            <ul class=\"dropdown-menu dropdown-user\">";

    foreach ($NDPI_DASHBOARD["RQ"]["WEEKLY"] as $xtime=>$none){
        $zdate=$tpl->time_to_date($xtime);
        $html[]="<li><a href=\"javascript:Loadjs('$page?chart-line-time=$xtime');\">$zdate</a></li>";
                                }
        $html[]="
                                </li>
                            </ul>
                           
                        </div>
                    </div>
                    <div class=\"ibox-content\" style=\"position: relative\">
                        <div id=\"morris-area-chart\" style='width:100%'></div>
                    </div>
                </div>";


    $html[]="<table style='width:100%'><tr>";

    $html[]="<td valign='top' width='50%'>";
    $html[]="<div class=\"ibox-title\">
                    <h5 id='morris-bar-chart-title'></h5>

                    </div>
                    <div class=\"ibox-content\" style=\"position: relative\">
                        <div id=\"morris-bar-chart\"></div>
                    </div>";
    $html[]="</td>";
    $html[]="<td valign='top' width='50%'>";
    $html[]="
                    <div class=\"ibox-title\">
                        <h5 id='morris-users-title'></h5>
                        
                    </div>
                    <div class=\"ibox-content\" style=\"position: relative\">
                        <div id=\"morris-users-chart\"></div>
                    </div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="</div>
<script>
    document.getElementById('morris-bar-chart').innerHTML='';
    document.getElementById('morris-users-chart').innerHTML='';
    document.getElementById('morris-area-chart').innerHTML='';
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
    $NDPI_DASHBOARD=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NDPI_DASHBOARD"));
    $HOURLY=$NDPI_DASHBOARD["RQ"]["WEEKLY"][$xtime];



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
    $highcharts->container="morris-area-chart";
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
    $xtime=$_GET["chart2"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $download_text=$tpl->javascript_parse_text("{download}");
    $upload_text=$tpl->javascript_parse_text("{upload}");
    $NDPI_DASHBOARD=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NDPI_DASHBOARD"));

    $DAILY=$NDPI_DASHBOARD["RQ"]["CATEGORY"][$xtime];


    $f[]="document.getElementById('morris-bar-chart').innerHTML='';";
    $f[]="Morris.Bar({
        element: 'morris-bar-chart',
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
    $title=$tpl->_ENGINE_parse_body("$stime: {TOP_APPLICATIONS}");
    $title=str_replace("'","`",$title);
    $f[]="document.getElementById('morris-bar-chart-title').innerHTML='$title';";
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
    $NDPI_DASHBOARD=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NDPI_DASHBOARD"));

    if(!isset($NDPI_DASHBOARD["RQ"]["SRC"][$xtime])){
        $NDPI_DASHBOARD["RQ"]["SRC"][$xtime]=array();
    }
    if(!is_array($NDPI_DASHBOARD["RQ"]["SRC"][$xtime])){
        $NDPI_DASHBOARD["RQ"]["SRC"][$xtime]=array();
    }

    $DAILY=$NDPI_DASHBOARD["RQ"]["SRC"][$xtime];


    $f[]="document.getElementById('morris-users-chart').innerHTML='';";
    $f[]="Morris.Bar({
        element: 'morris-users-chart',
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
    $f[]="document.getElementById('morris-users-title').innerHTML='$title';";

    header("content-type: application/x-javascript");
    echo @implode("\n",$f);

}
