<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.openvpn.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){stats_day();exit;}
if(isset($_GET["chart-line-time"])){chart_line_time();exit;}
if(isset($_GET["top-users"])){top_users();exit;}
if(isset($_GET["top-rqs"])){top_rqs();exit;}
if(isset($_GET["top-sites-size"])){top_sites_size();exit;}
if(isset($_GET["top-sites-rqs"])){top_sites_hits();exit;}
if(isset($_GET["top-cats-size"])){top_cats_size();exit;}
if(isset($_GET["top-cats-hits"])){top_cats_hits();exit;}
if(isset($_GET["day-picker-js"])){day_picker_js();exit;}
if(isset($_GET["day-picker-popup"])){day_picker_popup();exit;}
if(isset($_POST["day-picker"])){$_SESSION["statscom-date"]=$_POST["day-picker"];exit;}

stats_day_tabs();

function day_picker_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{choose_date}","$page?day-picker-popup=yes",500);

}

function day_picker_popup(){
   $tpl=new template_admin();
   $page=CurrentPageName();
   if(!isset($_SESSION["statscom-date"])){$_SESSION["statscom-date"]="";}
   $form[]=$tpl->field_date("day-picker","{date}",$_SESSION["statscom-date"]);
   echo $tpl->form_outside("{choose_date}",$form,null,"{apply}","LoadAjax('statscom-choose-date','$page?dayPick=yes')");
}

function stats_day_tabs(){
    $q=new postgres_sql();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    $daypick=false;
    $today=date("Y-m-d");
    $lastDay = date("Y-m-d", strtotime(date("Y-m-d", strtotime("-7 day"))));
    $results=$q->QUERY_SQL("SELECT date(zdate) as zdate FROM statscom WHERE date(zdate) < '$today' AND date(zdate) > '$lastDay' group by date(zdate) ORDER by date(zdate) DESC");

    if(!$q->ok){echo $tpl->div_error($q->mysql_error);return;}

    if(isset($_GET['dayPick'])){
        $daypick=true;
        $name=$tpl->time_to_date(strtotime($_SESSION["statscom-date"]));
        $array[$name]="$page?popup={$_SESSION["statscom-date"]}&t=$t";
    }

    $array["{today}"]="$page?popup=$today&t=$t";

    while ($ligne = pg_fetch_assoc($results)) {
        $zdate=strtotime($ligne["zdate"]);
        $name=$tpl->time_to_date($zdate);
        $array[$name]="$page?popup={$ligne["zdate"]}&t=$t";
    }
    $array["{other}"]="javascript:Loadjs('$page?day-picker-js=yes')";

if(!$daypick){$html[]="<div style='margin-top:10px' id='statscom-choose-date'>";}
$html[]= $tpl->tabs_default($array);
if(!$daypick){$html[]="</div>";}
echo $tpl->_ENGINE_parse_body($html);
}

function stats_day(){
    $t=intval($_GET["t"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $today=$_GET["popup"];

    $html[]="<div class=\"ibox float-e-margins\" style='margin-top:10px'>";
    $html[]=" <div class=\"ibox-title\">
                        <h5 id='$t-area-chart-title'></h5>
                        <div class=\"ibox-tools\">
                           
                            <a class=\"dropdown-toggle\" data-toggle=\"dropdown\" href=\"#\">
                                <i class=\"fa fa-wrench\"></i>
                            </a>
                            <ul class=\"dropdown-menu dropdown-user\">";
    $results=$q->QUERY_SQL("SELECT date(zdate) as zdate FROM statscom group by date(zdate) ORDER by date(zdate)");

    while ($ligne = pg_fetch_assoc($results)) {
        $zdate=strtotime($ligne["zdate"]);
        $name=$tpl->time_to_date($zdate);
        $html[]="<li><a href=\"javascript:Loadjs('$page?chart-line-time={$ligne["zdate"]}&t=$t');\">$name</a></li>";
    }
    $html[]="
                                </li>
                            </ul>
                           
                        </div>
                    </div>
                    <div class=\"ibox-content\" style=\"position: relative\">
                        <div id=\"$t-area-chart\" style='width:100%'></div>
                    </div>
                </div>";
$html[]="<table style='width:100%'>";
$html[]="<tr>";
$html[]="<td valign='top' width='50%'>";
$html[]="       <div class=\"ibox-content\" style=\"position: relative\">
                        <div id=\"$t-top-users\"></div>
                    </div>";
$html[]="</td>";
$html[]="<td valign='top' width='50%'>";
$html[]="
                    <div class=\"ibox-content\" style=\"position: relative\">
                        <div id=\"$t-rqs-chart\"></div>
                    </div>";
$html[]="</td>";
$html[]="</tr>";

    $html[]="<tr>";
    $html[]="<td valign='top' width='50%'>";
    $html[]="       <div class=\"ibox-content\" style=\"position: relative\">
                        <div id=\"$t-top-sites-size\"></div>
                    </div>";
    $html[]="</td>";
    $html[]="<td valign='top' width='50%'>";
    $html[]="
                    <div class=\"ibox-content\" style=\"position: relative\">
                        <div id=\"$t-top-sites-rqs\"></div>
                    </div>";
    $html[]="</td>";
    $html[]="</tr>";

    $html[]="<tr>";
    $html[]="<td valign='top' width='50%'>";
    $html[]="       <div class=\"ibox-content\" style=\"position: relative\">
                        <div id=\"$t-top-cats-size\"></div>
                    </div>";
    $html[]="</td>";
    $html[]="<td valign='top' width='50%'>";
    $html[]="
                    <div class=\"ibox-content\" style=\"position: relative\">
                        <div id=\"$t-top-cats-rqs\"></div>
                    </div>";
    $html[]="</td>";
    $html[]="</tr>";

$html[]="</table>";
$html[]="</div>
<script>
    document.getElementById('$t-top-users').innerHTML='';
    document.getElementById('$t-area-chart').innerHTML='';
    document.getElementById('$t-rqs-chart').innerHTML='';
    Loadjs('$page?chart-line-time=$today&t=$t');
</script>";

echo $tpl->_ENGINE_parse_body($html);
}
function chart_line_time(){
    $requested_date=$_GET["chart-line-time"];
    $t=$_GET["t"];
    $xtime=strtotime($requested_date);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $bandwidth_text=$tpl->javascript_parse_text("{bandwidth}");
    $q=new postgres_sql();

    $results=$q->QUERY_SQL("SELECT zdate, SUM(size) as size FROM statscom 
        WHERE date(zdate)='$requested_date' group by zdate ORDER by zdate");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}


    while ($ligne = pg_fetch_assoc($results)) {
        $DOWN = $ligne["size"];
        $DOWN = $DOWN / 1024;
        $DOWN = $DOWN / 1024;
        $DOWN = round($DOWN);
        $stime=strtotime($ligne["zdate"]);
        $xdata[]=date("H:i",$stime);
        $ydata[]=$DOWN;
    }


    $stime=$tpl->time_to_date($xtime);
    $title="$bandwidth_text (MB) $stime";
    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->container="$t-area-chart";
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
    $f[]="Loadjs('$page?top-users=$requested_date&t=$t');";
    echo @implode("\n",$f);
}

function top_users(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $requested_date=$_GET["top-users"];
    $t=$_GET["t"];
    $q=new postgres_sql();
    $xtime=strtotime($requested_date);

    $results=$q->QUERY_SQL("SELECT SUM(size) as size,username,ipaddr,mac FROM statscom 
        WHERE date(zdate)='$requested_date' group by username,ipaddr,mac ORDER BY size DESC LIMIT 15");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}


    while ($ligne = pg_fetch_assoc($results)) {
        $DOWN = $ligne["size"];
        $DOWN = $DOWN / 1024;
        $DOWN = $DOWN / 1024;
        $DOWN = round($DOWN);
        $username=$ligne["username"];
        $ipaddr=$ligne["ipaddr"];
        $mac=$ligne["mac"];
        $key=null;
        if($username<>null){$key=$username;}
        if($key==null){
            if($mac<>null){$key=$mac;}
            if($key=="00:00:00:00:00:00"){$key=null;}
        }
        if($key==null){
            if($ipaddr==null){
                $key=$ipaddr;
            }
        }
        if($key==null){continue;}
        $PieData[$key]=$DOWN;
    }



    $highcharts=new highcharts();
    $highcharts->container="$t-top-users";
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle="{top_members_by_size}";
    $highcharts->Title=$tpl->_ENGINE_parse_body("{top_members_by_size} ".$tpl->time_to_date($xtime));
    echo $highcharts->BuildChart();
    echo "\nLoadjs('$page?top-rqs=$requested_date&t=$t');\n";

}
function top_rqs(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $requested_date=$_GET["top-rqs"];
    $t=$_GET["t"];
    $q=new postgres_sql();
    $xtime=strtotime($requested_date);

    $results=$q->QUERY_SQL("SELECT SUM(hits) as size,username,ipaddr,mac FROM statscom 
        WHERE date(zdate)='$requested_date' group by username,ipaddr,mac ORDER BY size DESC LIMIT 15");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}


    while ($ligne = pg_fetch_assoc($results)) {
        $DOWN = $ligne["size"];
        $username=$ligne["username"];
        $ipaddr=$ligne["ipaddr"];
        $mac=$ligne["mac"];
        $key=null;
        if($username<>null){$key=$username;}
        if($key==null){
            if($mac<>null){$key=$mac;}
            if($key=="00:00:00:00:00:00"){$key=null;}
        }
        if($key==null){
            if($ipaddr==null){
                $key=$ipaddr;
            }
        }
        if($key==null){continue;}
        $PieData[$key]=$DOWN;
    }



    $highcharts=new highcharts();
    $highcharts->container="$t-rqs-chart";
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle="{top_members_by_hits}";
    $highcharts->Title=$tpl->_ENGINE_parse_body("{top_members_by_hits} ".$tpl->time_to_date($xtime));
    echo $highcharts->BuildChart();
   echo "\nLoadjs('$page?top-sites-size=$requested_date&t=$t');\n";
}

function top_sites_size()
{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $requested_date = $_GET["top-sites-size"];
    $t = $_GET["t"];
    $q = new postgres_sql();


    $results = $q->QUERY_SQL("SELECT SUM(size) as size,siteid FROM statscom 
        WHERE date(zdate)='$requested_date' group by siteid ORDER BY size DESC LIMIT 15");
    if (!$q->ok) {
        echo $tpl->js_mysql_alert($q->mysql_error);
        return;
    }

    while ($ligne = pg_fetch_assoc($results)) {
        $DOWN = $ligne["size"];
        $DOWN = $DOWN / 1024;
        $DOWN = $DOWN / 1024;
        $DOWN = round($DOWN);
        $sitename = SiteIntegerToString($ligne["siteid"]);
        $PieData[$sitename] = $DOWN;
    }


    $highcharts = new highcharts();
    $highcharts->container = "$t-top-sites-size";
    $highcharts->PieDatas = $PieData;
    $highcharts->ChartType = "pie";
    $highcharts->PiePlotTitle = "{top_websites_by_size} (MB)";
    $highcharts->Title = $tpl->_ENGINE_parse_body("{top_websites_by_size}");
    echo $highcharts->BuildChart();
    echo "\nLoadjs('$page?top-sites-rqs=$requested_date&t=$t');\n";

}

function top_sites_hits(){
    $tpl = new template_admin();
    $page = CurrentPageName();
    $requested_date = $_GET["top-sites-rqs"];
    $t = $_GET["t"];
    $q = new postgres_sql();


    $results = $q->QUERY_SQL("SELECT SUM(hits) as hits,siteid FROM statscom 
        WHERE date(zdate)='$requested_date' group by siteid ORDER BY hits DESC LIMIT 15");
    if (!$q->ok) {
        echo $tpl->js_mysql_alert($q->mysql_error);
        return;
    }

    while ($ligne = pg_fetch_assoc($results)) {
        $DOWN = $ligne["hits"];
        $DOWN = round($DOWN);
        $sitename = SiteIntegerToString($ligne["siteid"]);
        $PieData[$sitename] = $DOWN;
    }


    $highcharts = new highcharts();
    $highcharts->container = "$t-top-sites-rqs";
    $highcharts->PieDatas = $PieData;
    $highcharts->ChartType = "pie";
    $highcharts->PiePlotTitle = "{top_websites_by_hits}";
    $highcharts->Title = $tpl->_ENGINE_parse_body("{top_websites_by_hits}");
    echo $highcharts->BuildChart();
    echo "\nLoadjs('$page?top-cats-size=$requested_date&t=$t');\n";


}

function top_cats_size(){
    $tpl = new template_admin();
    $page = CurrentPageName();
    $requested_date = $_GET["top-cats-size"];
    $t = $_GET["t"];
    $q = new postgres_sql();
    $catz=new mysql_catz();

    $results = $q->QUERY_SQL("SELECT date(statscom.zdate) as date,SUM(statscom.size) as size,statscom_websites.category FROM statscom,statscom_websites WHERE statscom_websites.siteid=statscom.siteid AND  date(statscom.zdate)='$requested_date' GROUP BY category,date ORDER BY size DESC LIMIT 15");

    if (!$q->ok) {
        echo $tpl->js_mysql_alert($q->mysql_error);
        return;
    }

    while ($ligne = pg_fetch_assoc($results)) {
        $DOWN = $ligne["size"];
        $DOWN = $DOWN / 1024;
        $DOWN = $DOWN / 1024;
        $DOWN = round($DOWN);
        $category = $catz->CategoryIntToStr($ligne["category"]);
        $PieData[$category] = $DOWN;
    }


    $highcharts = new highcharts();
    $highcharts->container = "$t-top-cats-size";
    $highcharts->PieDatas = $PieData;
    $highcharts->ChartType = "pie";
    $highcharts->PiePlotTitle = "{top_categories_by_size} (MB)";
    $highcharts->Title = $tpl->_ENGINE_parse_body("{top_categories_by_size}");
    echo $highcharts->BuildChart();
   echo "\nLoadjs('$page?top-cats-hits=$requested_date&t=$t');\n";

}

function top_cats_hits(){
    $tpl = new template_admin();
    $page = CurrentPageName();
    $requested_date = $_GET["top-cats-hits"];
    $t = $_GET["t"];
    $q = new postgres_sql();
    $catz=new mysql_catz();

    $results = $q->QUERY_SQL("SELECT date(statscom.zdate) as date,SUM(statscom.hits) as hits,statscom_websites.category FROM statscom,statscom_websites WHERE statscom_websites.siteid=statscom.siteid AND  date(statscom.zdate)='$requested_date' GROUP BY category,date ORDER BY hits DESC LIMIT 15");

    if (!$q->ok) {
        echo $tpl->js_mysql_alert($q->mysql_error);
        return;
    }

    while ($ligne = pg_fetch_assoc($results)) {
        $DOWN = $ligne["hits"];
        $category = $catz->CategoryIntToStr($ligne["category"]);
        $PieData[$category] = $DOWN;
    }


    $highcharts = new highcharts();
    $highcharts->container = "$t-top-cats-rqs";
    $highcharts->PieDatas = $PieData;
    $highcharts->ChartType = "pie";
    $highcharts->PiePlotTitle = "{top_categories_by_hits}";
    $highcharts->Title = $tpl->_ENGINE_parse_body("{top_categories_by_hits}");
    echo $highcharts->BuildChart();
   // echo "\nLoadjs('$page?top-cats-hits=$requested_date&t=$t');\n";


}


function SiteIntegerToString($siteid){

    if(isset($GLOBALS[$siteid])){return $GLOBALS[$siteid];}
    $q = new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT sitename FROM statscom_websites WHERE siteid=$siteid");
    $GLOBALS[$siteid]=$ligne["sitename"];
    return $GLOBALS[$siteid];

}