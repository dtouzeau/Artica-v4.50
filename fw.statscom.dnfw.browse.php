<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.ip2host.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


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
page();

function query_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{build_query}","$page?query-popup=yes",650);

}

function query_popup(){

    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new postgres_sql();
    if(!$q->CREATE_STATSCOM()) {
        echo $tpl->div_error($q->mysql_error);
    }
    $results=$q->QUERY_SQL("SELECT zdate FROM statscom_days GROUP BY zdate ORDER BY zdate DESC");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}

    $DATES[date("Y-m-d")]=$tpl->time_to_date(time());
    $DATES[date("Y-m-d",strtotime( '-1 days' ))]=$tpl->time_to_date(strtotime( '-1 days' ));

    while ($ligne = pg_fetch_assoc($results)) {
        $time=strtotime($ligne["zdate"]);
        $DATES[$ligne["zdate"]]=$tpl->time_to_date($time);
    }

    $results=$q->QUERY_SQL("SELECT username,ipaddr,mac FROM statscom_users GROUP BY username,ipaddr,mac ORDER BY username,ipaddr,mac");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}

    while ($ligne = pg_fetch_assoc($results)) {
        $username=$ligne["username"];
        $ipaddr=$ligne["ipaddr"];
        $mac=$ligne["mac"];

        if($mac<>"00:00:00:00:00:00"){
            $ARRAY_MAC[$mac]=$mac;
        }
        if($username<>null){
            $ARRAY_USER[$username]=$username;
        }
        if($ipaddr<>null){
            $ARRAY_IP[$ipaddr]=$ipaddr;
        }
    }



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
    $form[]=$tpl->field_section("{from_date}");
    $form[]=$tpl->field_array_hash($DATES,"FROM","nonull:{from_date}",$_SESSION["STATSCOM_DAY"]["FROM"]);

    $tb=explode(":",$_SESSION["STATSCOM_DAY"]["FROMH"]);
    $form[]=$tpl->field_array_hash($TIMES,"FROM_HOUR","nonull:{from_time}",$tb[0]);
    $form[]=$tpl->field_array_hash($MINS,"FROM_MIN","nonull:{minutes}",$tb[1]);

    $form[]=$tpl->field_section("{to_date}");
    $tb=explode(":",$_SESSION["STATSCOM_DAY"]["TOH"]);
    $form[]=$tpl->field_array_hash($DATES,"TO","nonull:{to_date}",$_SESSION["STATSCOM_DAY"]["TO"]);
    $form[]=$tpl->field_array_hash($TIMES,"TO_HOUR","nonull:{from_time}",$tb[0]);
    $form[]=$tpl->field_array_hash($MINS,"TO_MIN","nonull:{minutes}",$tb[1]);


    echo $tpl->form_outside("{build_query}",$form,null,"{launch}","LoadAjax('stats-com-data','$page?template=yes');","AsWebStatisticsAdministrator",true);

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
    $_SESSION["STATSCOM_DAY"]["USER"]=$_POST["USER"];
    $_SESSION["STATSCOM_DAY"]["IP"]=$_POST["IP"];
    $_SESSION["STATSCOM_DAY"]["MAC"]=$_POST["MAC"];
    $_SESSION["STATSCOM_DAY"]["SIZE"]=$_POST["SIZE"];
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

function top_sites(){
    $page=CurrentPageName();
    $t=$_GET["suffix"];
    $tpl=new template_admin();
    $q=new postgres_sql();
    BUILD_DEFAULT_DATA();
    $DIFF_MIN=$_SESSION["STATSCOM_DAY"]["DIFF_MIN"];
    $DIFF_HOURS=$DIFF_MIN/60;
    echo "// DIFF: $DIFF_MIN hours\n// LINE : ".__LINE__."\n";
    $time1=strtotime("{$_SESSION["STATSCOM_DAY"]["FROM"]} {$_SESSION["STATSCOM_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["STATSCOM_DAY"]["TO"]} {$_SESSION["STATSCOM_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);

    $sql="SELECT SUM(statscom_dnsfw.hits) as size,statscom_websites.familysite,statscom_dnsfw.siteid FROM statscom_dnsfw,statscom_websites WHERE statscom_websites.siteid=statscom_dnsfw.siteid AND zdate >'$strtime' and zdate < '$strtoTime' GROUP by familysite,statscom_dnsfw.siteid ORDER BY size DESC LIMIT 15";

    $q->QUERY_SQL($sql);
    $results=$q->QUERY_SQL($sql);

    if(!$q->ok){
        echo "//$q->mysql_error\n";
        $tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $PieData2[$ligne["familysite"]]=array($size,$ligne["siteid"]);
        $PieData[$ligne["familysite"]]=$size;
    }

    $encoded=base64_encode(serialize($PieData2));

    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{top_websites_by_queries}";
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

    $sql="SELECT SUM(statscom_dnsfw.hits) as size,statscom_websites.category FROM statscom_dnsfw,statscom_websites WHERE statscom_websites.siteid=statscom_dnsfw.siteid AND zdate >'$strtime' and zdate < '$strtoTime' GROUP by category ORDER BY size DESC LIMIT 15";

    $q->QUERY_SQL($sql);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
    $catz=new mysql_catz();
    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $category=$catz->CategoryIntToStr($ligne["category"]);
        $PieData[$category]=$size;
        $PieData2[$ligne["category"]]=$ligne["size"];
    }

    $encoded=base64_encode(serialize($PieData2));

    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{top_categories_by_hits}";
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
        $link=$tpl->td_href($category,null,"Loadjs('fw.statscom.browse.categories.php?category=$categoryid&engine=dnsfw')");

        $html[]="<tr>";
        $html[]="<td><strong>$link</strong></td>";
        $html[]="<td><strong>". $tpl->FormatNumber($sizeM)."</strong></td>";
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

    $sql="SELECT SUM(hits) as size,ipaddr FROM statscom_dnsfw  WHERE zdate >'$strtime' and zdate < '$strtoTime' GROUP by ipaddr ORDER BY size DESC LIMIT 15";

    $q->QUERY_SQL($sql);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $username=$ligne["username"];
        $ipaddr=$ligne["ipaddr"];
        $resolveIP2HOST=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolveIP2HOST"));
        if($resolveIP2HOST==1){
            $host= new ip2host($ipaddr);
            $ipaddr=$host->output;
        }

        $issu=array();
        $issu[]=$ipaddr;
        $PieData2[@implode("|",$issu)]=$size;
        if($username<>null){
            $PieData[$username]=$size;
            continue;
        }
        $PieData[$ipaddr]=$size;
    }

    $encoded=base64_encode(serialize($PieData2));

    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{top_members} ({queries})";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();

    echo "LoadAjax('top-users-table-$t','$page?users-table=$encoded');";


}

function sites_table(){
    $tpl=new template_admin();
    $html[]="<table style='width:100%' class='table'>";
    $html[]="<tr>";
    $html[]="<th>{domains}</th>";
    $html[]="<th>{queries}</th>";
    $html[]="</tr>";
    $table=unserialize(base64_decode($_GET["sites-table"]));
    foreach ( $table as $site=>$array) {
        $sizeM=$array[0];
        $siteid=$array[1];

        $link=$tpl->td_href("$site",null,"Loadjs('fw.statscom.browse.website.php?siteid=$siteid&engine=dnsfw')");

        $html[]="<tr>";
        $html[]="<td><strong>$link</strong></td>";
        $html[]="<td>".$tpl->FormatNumber($sizeM)."</td>";
        $html[]="</tr>";

    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}
function users_table(){

    $tpl=new template_admin();
    $html[]="<table style='width:100%' class='table'>";
    $html[]="<tr>";
    $html[]="<th>{members}</th>";
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
        $html[]="<td>".$tpl->FormatNumber($sizeM)."</td>";
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

    $sql="SELECT SUM(hits) as size,$SQL_DATE as zdate FROM statscom_dnsfw WHERE zdate >'$strtime' and zdate < '$strtoTime' GROUP by $SQL_DATE ORDER BY $SQL_DATE";

    if($GLOBALS["VERBOSE"]){echo "\n\n$sql\n";}
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
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
    $title="{queries} $stimeFrom - $stimeTo {$UNIT}";
    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle=" {queries}";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{queries}"=>$ydata);
    echo $highcharts->BuildChart();
    //$f[]="Loadjs('$page?chart2=$xtime');";



    //echo @implode("\n",$f);


}



function page(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<div class='row ibox-content white-bg'>";
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top: 10px'>";
    $html[]=$tpl->button_label_table("{build_query}",
            "Loadjs('$page?query-js=yes')", "fas fa-filter","AsWebStatisticsAdministrator");
    $html[]=$tpl->button_label_table("{refresh}",
        "LoadAjax('stats-com-data','$page?template=yes')", "fas fa-sync-alt","AsWebStatisticsAdministrator");
    $html[]="</div>";

    $html[]="<div id='stats-com-data' ></div>";
    $html[]="</div>";
    $html[]="<script>LoadAjax('stats-com-data','$page?template=yes');</script>";

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
