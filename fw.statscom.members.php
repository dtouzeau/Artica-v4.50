<?php
// SP 127
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.ip2host.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["build-full-query"])){build_full_query();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["page-de-guarde"])){page_de_guarde();exit;}
if(isset($_GET["query"])){query();exit;}
if(isset($_GET["query-js"])){query_js();exit;}
if(isset($_GET["query-popup"])){query_popup();exit;}
if(isset($_POST["FROM"])){query_save();exit;}
if(isset($_GET["vertical-timeline"])){echo vertical_time_line();exit;}
if(isset($_GET["top-sites"])){echo top_sites();exit;}
if(isset($_GET["top-users"])){echo top_users();exit;}
if(isset($_GET["top-categories"])){echo top_categories();exit;}
if(isset($_GET["template"])){template();exit;}
if(isset($_GET["sites-table"])){sites_table();exit;}
if(isset($_GET["users-table"])){echo users_table();exit;}
if(isset($_GET["categories-table"])){echo categories_table();exit;}
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



page();

function query_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $srctype=$_GET["srctype"];
    $username=$_GET["data"];
    $usernameenc=urlencode($username);
    $tpl->js_dialog1("{build_query}: {{$srctype}} $username","$page?query-popup=yes&srctype=$srctype&data=$usernameenc",650);

}

function member_stats_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $srctype=$_GET["srctype"];
    $username=$_GET["data"];
    $usernameenc=urlencode($username);
    $Date1=$_GET["date-from"];
    $Date2=$_GET["date-to"];
    $tpl->js_dialog10("{statistics}: {{$srctype}} $username","$page?member-stats-tabs=yes&date-from=$Date1&date-to=$Date2&srctype=$srctype&data=$usernameenc",2000);

}

function member_stats_tabs(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $srctype=$_GET["srctype"];
    $username=$_GET["data"];
    $usernameenc=urlencode($username);
    $Date1=$_GET["date-from"];
    $Date2=$_GET["date-to"];
    $Diff=$tpl->time_diff_min_interval_hours($Date1,$Date2);
    $suffix="&date-from=$Date1&date-to=$Date2&srctype=$srctype&data=$usernameenc";

    if($Diff>96){
        $days=intval($Diff/24);
        $array["{last} $days {days}"]="fw.statscom.members.cold.php?member-stats-pane-hours=yes$suffix";
        $array["{top_websites}"]="fw.statscom.members.cold.php?member-stats-pane-sites=yes$suffix";
        $array["{websites}"]="fw.statscom.members.cold.php?member-stats-websites=yes$suffix";
        $array["{websites_not_categorized}"]="fw.statscom.members.cold.php?member-stats-notcategorized=yes$suffix";

    }else{
        $array["{last} $Diff {hours}"]="$page?member-stats-pane-hours=yes$suffix";
        $array["{top_websites}"]="$page?member-stats-pane-sites=yes$suffix";
        $array["{websites}"]="$page?member-stats-websites=yes$suffix";
        $array["{websites_not_categorized}"]="$page?member-stats-notcategorized=yes$suffix";

    }

    echo $tpl->tabs_default($array);
}

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
    $sql="SELECT SUM(statscom.size) as size,statscom_websites.familysite,statscom.siteid FROM statscom,statscom_websites WHERE statscom_websites.siteid=statscom.siteid AND zdate >'$strtime' and zdate < '$strtoTime' and $srctype='$username' GROUP by familysite,statscom.siteid ORDER BY size DESC LIMIT 20";
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
    $sql="SELECT SUM(statscom.size) as size,statscom_websites.category FROM statscom,statscom_websites WHERE statscom_websites.siteid=statscom.siteid AND zdate >'$strtime' and zdate < '$strtoTime' and $srctype='$username' GROUP by category ORDER BY size DESC LIMIT 15";
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
    $sql="SELECT SUM(statscom.hits) as size,statscom_websites.category FROM statscom,statscom_websites WHERE statscom_websites.siteid=statscom.siteid AND zdate >'$strtime' and zdate < '$strtoTime' and $srctype='$username' GROUP by category ORDER BY size DESC LIMIT 15";
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
    $sql="SELECT SUM(statscom.hits) as size,statscom_websites.familysite,statscom.siteid FROM statscom,statscom_websites WHERE statscom_websites.siteid=statscom.siteid AND zdate >'$strtime' and zdate < '$strtoTime' and $srctype='$username' GROUP by familysite,statscom.siteid ORDER BY size DESC LIMIT 20";
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
    echo "// $srctype=$username $Date1 -> $Date2\n";

    $q=new postgres_sql();
    $sql="SELECT SUM(size) as size,zdate FROM statscom WHERE ( zdate >'$strtime' and zdate < '$strtoTime' and $srctype='$username') GROUP BY zdate ORDER BY zdate";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo "// $q->mysql_error\n";
        $tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = round($size);
        $stime=strtotime($ligne["zdate"]);
        $DateText=date("l H:i",$stime);
        echo "//{$ligne["zdate"]} = {$ligne["size"]}\n";
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
    $sql="SELECT SUM(hits) as hits,zdate FROM statscom WHERE ( zdate >'$strtime' and zdate < '$strtoTime' and $srctype='$username') GROUP BY zdate ORDER BY zdate";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["hits"];
        $stime=strtotime($ligne["zdate"]);
        $DateText=date("l H:i",$stime);

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



function query_popup(){

    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new postgres_sql();
    if(!$q->CREATE_STATSCOM()) {
        echo $tpl->div_error($q->mysql_error);
    }

    if(!isset($_SESSION["STATSCOM_DAYS_CACHE"])) {
        $results = $q->QUERY_SQL("SELECT zdate FROM statscom_days GROUP BY zdate ORDER BY zdate DESC");
        if (!$q->ok) {
            echo $tpl->div_error($q->mysql_error);
        }

        $_SESSION["STATSCOM_DAYS_CACHE"][date("Y-m-d")] = $tpl->time_to_date(time());
        $_SESSION["STATSCOM_DAYS_CACHE"][date("Y-m-d", strtotime('-1 days'))] = $tpl->time_to_date(strtotime('-1 days'));

        while ($ligne = pg_fetch_assoc($results)) {
            $time = strtotime($ligne["zdate"]);
            $_SESSION["STATSCOM_DAYS_CACHE"][$ligne["zdate"]] = $tpl->time_to_date($time);
        }
    }

    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}

    for($i=0;$i<24;$i++){
        $h=$i;
        if($i<10){$h="0{$i}";}
        $h="$h";
        $TIMES[$h]=$h;
    }
    for($i=0;$i<60;$i++){
        $h=$i;
        if($i<10){$h="0{$i}";}
        $h="$h";
        $MINS[$h]=$h;
    }

    $tpl->field_hidden("STATSCOM_SRC",$_GET["srctype"]);
    $tpl->field_hidden("STATSCOM_SRC_DATA",$_GET["data"]);

    $form[]=$tpl->field_section("{from_date}");
    $form[]=$tpl->field_array_hash($_SESSION["STATSCOM_DAYS_CACHE"],"FROM","nonull:{from_date}",$_SESSION["STATSCOM_DAY"]["FROM"]);

    $tb=explode(":",$_SESSION["STATSCOM_DAY"]["FROMH"]);
    $form[]=$tpl->field_array_hash($TIMES,"FROM_HOUR","nonull:{from_time}",$tb[0]);
    $form[]=$tpl->field_array_hash($MINS,"FROM_MIN","nonull:{minutes}",$tb[1]);

    $form[]=$tpl->field_section("{to_date}");
    $tb=explode(":",$_SESSION["STATSCOM_DAY"]["TOH"]);
    $form[]=$tpl->field_array_hash($_SESSION["STATSCOM_DAYS_CACHE"],"TO","nonull:{to_date}",$_SESSION["STATSCOM_DAY"]["TO"]);
    $form[]=$tpl->field_array_hash($TIMES,"TO_HOUR","nonull:{from_time}",$tb[0]);
    $form[]=$tpl->field_array_hash($MINS,"TO_MIN","nonull:{minutes}",$tb[1]);


    echo $tpl->form_outside("{build_query}",$form,null,"{launch}","Loadjs('$page?build-full-query=yes');","AsWebStatisticsAdministrator",true);

}

function build_full_query(){
    $page=CurrentPageName();
    $Date1=strtotime($_SESSION["STATSCOM_DAY"]["FROM"]." ".$_SESSION["STATSCOM_DAY"]["FROMH"]);
    $Date2=strtotime($_SESSION["STATSCOM_DAY"]["TO"]." ".$_SESSION["STATSCOM_DAY"]["TOH"]);
    $srctype=$_SESSION["STATSCOM_DAY"]["STATSCOM_SRC"];
    $data=urlencode($_SESSION["STATSCOM_DAY"]["STATSCOM_SRC_DATA"]);
    echo "Loadjs('$page?member-stats=yes&date-from=$Date1&date-to=$Date2&srctype=$srctype&data=$data')";
}

function BUILD_DEFAULT_DATA(){

    if(!isset($_SESSION["STATSCOM_DAY"]["FROM"])){
        $_SESSION["STATSCOM_DAY"]["FROM"]=date("Y-m-d");
    }
    if(!isset($_SESSION["STATSCOM_DAY"]["TO"])){
        $_SESSION["STATSCOM_DAY"]["TO"]=date("Y-m-d");
    }

    if(!isset($_SESSION["STATSCOM_DAY"]["FROMH"])){
        $_SESSION["STATSCOM_DAY"]["FROMH"]="00:00";
    }
    if(!isset($_SESSION["STATSCOM_DAY"]["TOH"])){
        $_SESSION["STATSCOM_DAY"]["TOH"]="23:59";
    }
    if(!isset($_SESSION["STATSCOM_DAY"]["LIMIT"])){
        $_SESSION["STATSCOM_DAY"]["LIMIT"]="250";
    }
    if(intval($_SESSION["STATSCOM_DAY"]["LIMIT"])==0){
        $_SESSION["STATSCOM_DAY"]["LIMIT"]=250;
    }

    $time1=strtotime("{$_SESSION["STATSCOM_DAY"]["FROM"]} {$_SESSION["STATSCOM_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["STATSCOM_DAY"]["TO"]} {$_SESSION["STATSCOM_DAY"]["TOH"]}:00");
    $_SESSION["STATSCOM_DAY"]["DIFF_MIN"]=round(($time2-$time1)/60);

}

function query_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $_SESSION["STATSCOM_DAY"]["FROM"]=$_POST["FROM"];
    $_SESSION["STATSCOM_DAY"]["TO"]=$_POST["TO"];
    $_SESSION["STATSCOM_DAY"]["FROMH"]=$_POST["FROM_HOUR"].":".$_POST["FROM_MIN"];
    $_SESSION["STATSCOM_DAY"]["TOH"]=$_POST["TO_HOUR"].":".$_POST["TO_MIN"];
    $_SESSION["STATSCOM_DAY"]["STATSCOM_SRC"]=$_POST["STATSCOM_SRC"];
    $_SESSION["STATSCOM_DAY"]["STATSCOM_SRC_DATA"]=$_POST["STATSCOM_SRC_DATA"];


    BUILD_DEFAULT_DATA();

}

function template(){
    $t=time();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $imgwait="<img src='/img/Eclipse-0.9s-120px.gif'>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top'><div id='vertical-timeline-$t'>$imgwait</div></td>";
    $html[]="</tr>";
    $html[]="</table>";



    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top' width='2%' nowrap><div id='top-sites-table-$t'>$imgwait</div></td>";
    $html[]="<td style='vertical-align:top'><div id='top-sites-$t' style='height:600px;width:1340px'>$imgwait</div></td>";
    $html[]="</tr>";


    $html[]="<tr>";
    $html[]="<td colspan=2><hr></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top' width='2%' nowrap><div id='top-sites-categories-$t'>$imgwait</div></td>";
    $html[]="<td style='vertical-align:top'><div id='top-categories-$t' style='height:600px;width:1340px'>$imgwait</div></td>";
    $html[]="</tr>";



    $html[]="<tr>";
    $html[]="<td colspan=2><hr></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top' width='2%' nowrap><div id='top-users-table-$t'>$imgwait</div></td>";
    $html[]="<td style='vertical-align:top'><div id='top-users-$t' style='height:600px;width:1340px'>$imgwait</div></td>";
    $html[]="</tr>";
    $html[]="</table>";

    $html[]="<script>";
    $html[]="Loadjs('$page?vertical-timeline=yes&id=vertical-timeline-$t');";
    $html[]="Loadjs('$page?top-sites=yes&id=top-sites-$t&suffix=$t');";
    $html[]="Loadjs('$page?top-users=yes&id=top-users-$t&suffix=$t');";
    $html[]="Loadjs('$page?top-categories=yes&id=top-categories-$t&suffix=$t');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);


}

function page_de_guarde(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page,null,null);
}

function top_sites(){
    $page=CurrentPageName();
    $t=$_GET["suffix"];
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

    $sql="SELECT SUM(statscom.size) as size,statscom_websites.familysite,statscom.siteid FROM statscom,statscom_websites WHERE statscom_websites.siteid=statscom.siteid AND zdate >'$strtime' and zdate < '$strtoTime' GROUP by familysite,statscom.siteid ORDER BY size DESC LIMIT 15";

    $q->QUERY_SQL($sql);
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

    $encoded=base64_encode(serialize($PieData2));

    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{top_websites_by_size} (MB)";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();

    echo "LoadAjax('top-sites-table-$t','$page?sites-table=$encoded');";


}
function top_categories(){
    $page=CurrentPageName();
    $t=$_GET["suffix"];
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

    $sql="SELECT SUM(statscom.size) as size,statscom_websites.category FROM statscom,statscom_websites WHERE statscom_websites.siteid=statscom.siteid AND zdate >'$strtime' and zdate < '$strtoTime' GROUP by category ORDER BY size DESC LIMIT 15";

    $q->QUERY_SQL($sql);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
    $catz=new mysql_catz();
    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);
        $category=$catz->CategoryIntToStr($ligne["category"]);
        $PieData[$category]=$size;
        $PieData2[$ligne["category"]]=$ligne["size"]/1024;
    }

    $encoded=base64_encode(serialize($PieData2));

    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{top_categories_by_size} (MB)";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();

    echo "LoadAjax('top-sites-categories-$t','$page?categories-table=$encoded');";


}

function categories_table(){
    $tpl=new template_admin();
    $html[]="<table style='width:100%' class='table'>";
    $html[]="<tr>";
    $html[]="<th>{category}</th>";
    $html[]="<th>{size}</th>";
    $html[]="</tr>";
    $table=unserialize(base64_decode($_GET["categories-table"]));
    $catz=new mysql_catz();
    foreach ( $table as $categoryid=>$sizeM) {

        $category=$catz->CategoryIntToStr($categoryid);
        $link=$tpl->td_href($category,null,"Loadjs('fw.statscom.browse.categories.php?category=$categoryid')");

        $html[]="<tr>";
        $html[]="<td><strong>$link</strong></td>";
        $html[]="<td><strong>". FormatBytes($sizeM)."</strong></td>";
        $html[]="</tr>";

    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);

}

function top_users(){

    $page=CurrentPageName();
    $t=$_GET["suffix"];
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

    $sql="SELECT SUM(size) as size,username,ipaddr,MAC FROM statscom  WHERE zdate >'$strtime' and zdate < '$strtoTime' GROUP by username,ipaddr,MAC ORDER BY size DESC LIMIT 15";

    $q->QUERY_SQL($sql);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);
        $username=$ligne["username"];
        $ipaddr=$ligne["ipaddr"];
        $resolveIP2HOST=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolveIP2HOST"));
        if($resolveIP2HOST==1){
            $host= new ip2host($ipaddr);
            $ipaddr=$host->output;
        }
        $MAC=$ligne["MAC"];
        $issu=array();
        $issu[]=$username;
        $issu[]=$MAC;
        $issu[]=$ipaddr;
        $PieData2[@implode("|",$issu)]=$size;
        if($username<>null){
            $PieData[$username]=$size;
            continue;
        }
        if($MAC<>null){
            $PieData[$MAC]=$size;
            continue;
        }
        $PieData[$ipaddr]=$size;
    }

    $encoded=base64_encode(serialize($PieData2));

    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{top_members} (MB)";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();

    echo "LoadAjax('top-users-table-$t','$page?users-table=$encoded');";


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

    $sql="SELECT SUM(statscom.size) as size,SUM(statscom.hits) as hits,
    statscom_websites.sitename,
    statscom_websites.category,
    statscom_websites.siteid
    FROM statscom,statscom_websites WHERE statscom_websites.siteid=statscom.siteid 
    AND zdate >'$strtime' 
    AND zdate < '$strtoTime' 
    AND statscom.$srctype='$username'
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



        $link=$tpl->td_href("$site",null,"Loadjs('fw.statscom.browse.website.php?siteid=$siteid&field=$srctype&value=$usernameenc&date-from=$Date1&date-to=$Date2')");
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

    $sql="SELECT SUM(statscom.size) as size,SUM(statscom.hits) as hits,
    statscom_websites.sitename,
    statscom_websites.siteid
    FROM statscom,statscom_websites WHERE statscom_websites.siteid=statscom.siteid 
    AND zdate >'$strtime' 
    AND zdate < '$strtoTime' 
    AND statscom.$srctype='$username'
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





        $link=$tpl->td_href("$site",null,"Loadjs('fw.statscom.browse.website.php?siteid=$siteid&field=$srctype&value=$usernameenc&date-from=$Date1&date-to=$Date2')");
        $html[]="<tr>";
        $html[]="<td><i class='text-navy fad fa-question'></i>&nbsp;&nbsp;<strong>$link</strong></td>";
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
            $t[]=$tpl->td_href($h[0],null,"Loadjs('fw.statscom.browse.users.php?field=username&value=".urlencode($h[0])."')");
        }
        if($h[1]<>null){
            $t[]=$tpl->td_href($h[1],null,"Loadjs('fw.statscom.browse.users.php?field=mac&value=".urlencode($h[1])."')");
        }
        if($h[2]<>null){
            $t[]=$tpl->td_href($h[2],null,"Loadjs('fw.statscom.browse.users.php?field=ipaddr&value=".urlencode($h[2])."')");
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

    $sql="SELECT SUM(size) as size,$SQL_DATE as zdate FROM statscom WHERE zdate >'$strtime' and zdate < '$strtoTime' GROUP by $SQL_DATE ORDER BY $SQL_DATE";


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



function page(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<div class='row ibox-content white-bg'>";
    $html[]="<div id='stats-com-members' ></div>";
    $html[]="</div>";
    $html[]="<script>LoadAjax('stats-com-members','$page?page-de-guarde=yes');</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function query(){
    $tpl=new template_admin();
    $catz=new mysql_catz();
    $q=new postgres_sql();
    $t=time();
    $TRCLASS=null;$WHEREU=null;$WHEREM=null;$WHEREI=null;$WHERES=null;
    //86340 = 24H
    // 172740  =



    $time1=strtotime( $_SESSION["STATSCOM_DAY"]["FROM"]." ". $_SESSION["STATSCOM_DAY"]["FROMH"]);
    $time2=strtotime($_SESSION["STATSCOM_DAY"]["TO"]." ". $_SESSION["STATSCOM_DAY"]["TOH"]);

    $seconds=$time2-$time1;

    if($seconds<0){
       echo  $tpl->div_error("{please_define_dates_in_correct_order}");
        return;
    }



    if($seconds<259150){
        $TABLE_SELECTED="statscom";
        $date1=$_SESSION["STATSCOM_DAY"]["FROM"]." ". $_SESSION["STATSCOM_DAY"]["FROMH"];
        $date2=$_SESSION["STATSCOM_DAY"]["TO"]." ". $_SESSION["STATSCOM_DAY"]["TOH"];
        $tt2=date("Y-m-d",strtotime($date2));

        $TITLES[]="{from} " .$tpl->time_to_date(strtotime($date1),true);
        if($tt2==date("Y-m-d")) {
            $TITLES[] = "{to} {today} " . $tpl->time_to_date(strtotime($date2), true);
        }else{
            $TITLES[] = "{to} " . $tpl->time_to_date(strtotime($date2), true);
        }
    }else{
        $TABLE_SELECTED="statscom_days";
        $date1=$_SESSION["STATSCOM_DAY"]["FROM"];
        $date2=$_SESSION["STATSCOM_DAY"]["TO"];
        $tt2=date("Y-m-d",strtotime($date2));
        $TITLES[]="{from} " .$tpl->time_to_date(strtotime($date1),false);
        if($tt2==date("Y-m-d")) {
            $TITLES[] = "{to} {today} " . $tpl->time_to_date(strtotime($date2), false);
        }else{
            $TITLES[] = "{to} " . $tpl->time_to_date(strtotime($date2), false);
        }
    }

    $TITLES[]="(".distanceOfTimeInWords(strtotime($date1),strtotime($date2)).")";


    if($_SESSION["STATSCOM_DAY"]["USER"]<>null){
        $TITLES[]="{and} {member} {$_SESSION["STATSCOM_DAY"]["USER"]}";
        $WHEREU=" AND username='{$_SESSION["STATSCOM_DAY"]["USER"]}'";
    }
    if($_SESSION["STATSCOM_DAY"]["IP"]<>null){
        $TITLES[]="{and} {ipaddr} {$_SESSION["STATSCOM_DAY"]["IP"]}";
        $WHEREI=" AND ipaddr='{$_SESSION["STATSCOM_DAY"]["IP"]}'";
    }
    if($_SESSION["STATSCOM_DAY"]["MAC"]<>null){
        $TITLES[]="{and} {MAC} {$_SESSION["STATSCOM_DAY"]["MAC"]}";
        $WHEREM=" AND mac='{$_SESSION["STATSCOM_DAY"]["MAC"]}'";
    }
    if( intval($_SESSION["STATSCOM_DAY"]["SIZE"])>0){
        $size_q=round(intval( $_SESSION["STATSCOM_DAY"]["SIZE"])*1024);
        $WHERES=" $TABLE_SELECTED.size > $size_q AND";
    }

    $html[]="<H2 style='margin-top: 10px'>".@implode(" ",$TITLES)."</H2>";


    $sql="SELECT SUM($TABLE_SELECTED.hits) as hits, 
    SUM($TABLE_SELECTED.size) as size,
    $TABLE_SELECTED.username,
    $TABLE_SELECTED.ipaddr,
    $TABLE_SELECTED.mac,
    $TABLE_SELECTED.zdate,
    statscom_websites.sitename,
    statscom_websites.category   
    FROM $TABLE_SELECTED,statscom_websites WHERE{$WHERES}
    statscom_websites.siteid=$TABLE_SELECTED.siteid AND
    $TABLE_SELECTED.zdate >='$date1' AND  $TABLE_SELECTED.zdate <= '$date2'{$WHEREU}{$WHEREI}{$WHEREM}
    GROUP BY $TABLE_SELECTED.zdate,$TABLE_SELECTED.username,$TABLE_SELECTED.ipaddr,$TABLE_SELECTED.mac,statscom_websites.sitename, statscom_websites.category ORDER BY $TABLE_SELECTED.zdate
    LIMIT {$_SESSION["STATSCOM_DAY"]["LIMIT"]}";

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo  $tpl->_ENGINE_parse_body($html);
        echo  $tpl->div_error("{$seconds}s<br>".$q->mysql_error."<br>$sql");
        return;
    }

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" 
    data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{sitename}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{category}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{member}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{MAC}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hits}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $td1=$tpl->table_td1prc_Left();
    $tdfree=$tpl->table_tdfree();

    while ($ligne = pg_fetch_assoc($results)) {
        if ($TRCLASS == "footable-odd ") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd ";
        }
        $hits=$tpl->FormatNumber($ligne["hits"]);
        $size=FormatBytes($ligne["size"]/1024);
        $date=$ligne["zdate"];
        $sitename=$ligne["sitename"];
        $category_id=$ligne["category"];
        $category=$catz->CategoryIntToStr($category_id);
        $ipaddr=$ligne["ipaddr"];
        $resolveIP2HOST=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolveIP2HOST"));
        if($resolveIP2HOST==1){
            $host= new ip2host($ipaddr);
            $ipaddr=$host->output;
        }
        $username=$ligne["username"];
        $mac=$ligne["mac"];

        $params=array();
        $params[]="username=".urlencode($username);
        $params[]="category=".urlencode($category_id);
        $params[]="sitename=".urlencode($sitename);
        $params[]="mac=".urlencode($mac);
        $params[]="ipaddr=".urlencode($ipaddr);
        $params[]="date1=".urlencode($date1);
        $params[]="date2=".urlencode($date2);

        $parm=@implode("&",$params);

        $category=$tpl->td_href($category,"{statistics}","Loadjs('fw.statscom.category.php?data=yes&$params')");


        $html[]="<tr style='vertical-align:middle' class='$TRCLASS'>";
        $html[]="<td $td1>$date</td>";
        $html[]="<td $tdfree>$sitename</td>";
        $html[]="<td $td1>$category</td>";
        $html[]="<td $td1>$username</td>";
        $html[]="<td $td1>$mac</td>";
        $html[]="<td $td1>$ipaddr</td>";
        $html[]="<td $td1>$size</td>";
        $html[]="<td $td1>$hits</td>";
        $html[]="</tr>";


    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='8'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
<script> 
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) }); 
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function search(){
    $stringToSearch=$_GET["search"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $MAIN=$tpl->format_search_protocol($stringToSearch);
    $MAX=$MAIN["MAX"];
    if($MAX<150){$MAX=150;}
    $IP=new IP();
    $t=time();
    $Term=trim($MAIN["TERM"]);
    $Q=null;
    $hideMacs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('statscomHideMacs'));
    $hideUnkownMembers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('statscomHideUnkownMembers'));

    if($IP->isValid($Term)){
        $Q=" WHERE (ipaddr='$Term')";
    }

    if($Q==null) {
        $Term = "*$Term*";
        $Term = str_replace("**", "*", $Term);
        $Term = str_replace("**", "*", $Term);
        $Term = str_replace("*", "%", $Term);

        if($Term<>null){
            $Q=" WHERE (username LIKE '$Term')";

        }

    }

    //$sql="SELECT * FROM statscom_users $Q ORDER BY username,ipaddr,mac LIMIT $MAX";
    $sql="SELECT * FROM statscom_users $Q LIMIT $MAX";
    $q=new postgres_sql();

    $results=$q->QUERY_SQL($sql);

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" 
    data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{member}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{ipaddr}</th>";
    if($hideMacs==0) {
        $html[] = "<th data-sortable=true class='text-capitalize' data-type='text'>{MAC}</th>";
    }
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    while ($ligne = pg_fetch_assoc($results)) {
        if ($TRCLASS == "footable-odd ") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd ";
        }
        $ipaddr = $ligne["ipaddr"];
        $ipaddrtxt=$ligne["ipaddr"];
        $resolveIP2HOST=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolveIP2HOST"));
        if($resolveIP2HOST==1){
            $host= new ip2host($ipaddr);
            $ipaddrtxt=$host->output;
        }
        $username = $ligne["username"];
        $mac = $ligne["mac"];
        $ico_user="<i class='text-muted fad fa-user'></i>&nbsp;&nbsp;";
        $icon_ip="<i class='text-navy fad fa-ethernet'></i>&nbsp;&nbsp;";
        $icon_mac="<i class='text-muted fad fa-ethernet'></i>&nbsp;&nbsp;";






        $ipaddrtxt=$tpl->td_href($ipaddrtxt,null,"Loadjs('$page?query-js=yes&srctype=ipaddr&data=$ipaddr')");
        if($mac<>"00:00:00:00:00:00"){
            $mac=$tpl->td_href($mac,null,"Loadjs('$page?query-js=yes&srctype=mac&data=$mac')");
            $icon_mac="<i class='text-navy fad fa-ethernet'></i>&nbsp;&nbsp;";
        }


        if($username==null){
            if($hideUnkownMembers==1){
                $username=$ipaddrtxt;
                $ico_user="<i class='text-navy fad fa-ethernet'></i>&nbsp;&nbsp;";
            }
            else{
                $username="{unknown}";
            }

        }
        else{
            $username_enc=urlencode($username);
            $username=$tpl->td_href($username,null,"Loadjs('$page?query-js=yes&srctype=username&data=$username_enc')");
            $ico_user="<i class='text-navy fad fa-user'></i>&nbsp;&nbsp;";

        }

        $html[] = "<tr style='vertical-align:middle' class='$TRCLASS'>";
        $html[] = "<td style='widdth:33%'>$ico_user<strong>$username</strong></td>";
        $html[] = "<td style='widdth:33%'>$icon_ip$ipaddrtxt</td>";
        if($hideMacs==0) {
            $html[] = "<td style='widdth:33%'>$icon_mac$mac</td>";
        }
        $html[] = "</tr>";
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
    $html[]="
<script> 
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": false },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) }); 
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
