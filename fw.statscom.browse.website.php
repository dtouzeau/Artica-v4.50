<?php
// Service Pack 9
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// Pentest 2026-11-04 : engine, field, siteid, t, date-from et date-to etaient recopies sans encodage dans la page (XSS).
foreach (array("engine","field") as $xk){if(isset($_GET[$xk])){$_GET[$xk]=preg_replace('#[^A-Za-z0-9_]#','',(string)$_GET[$xk]);}}
foreach (array("siteid","t","date-from","date-to") as $xk){if(isset($_GET[$xk])){$_GET[$xk]=intval($_GET[$xk]);}}
if(isset($_GET["template"])){template();exit;}
if(isset($_GET["vertical-timeline"])){vertical_timeline();exit;}
if(isset($_GET["top-sites"])){top_sites();exit;}
if(isset($_GET["top-users"])){top_users();exit;}
if(isset($_GET["sites-table"])){top_sites_table();exit;}
if(isset($_GET["users-table"])){top_users_table();exit;}
if(isset($_GET["top-categories"])){top_categories();exit;}
if(isset($_GET["categories-table"])){top_categories_table();exit;}
js();


function js(){
    $tpl=new template_admin();
    $field=$_GET["field"];
    $value=$_GET["value"];
    $siteid=$_GET["siteid"];
    $catz=new mysql_catz();
    $_GET["t"]=time();
    $q= new postgres_sql();
    // Pentest 2026-11-04 : siteid etait concatene dans la requete.
    $siteidSQL=intval($siteid);
    $ligne=$q->mysqli_fetch_array("SELECT sitename,category FROM statscom_websites WHERE siteid=$siteidSQL");
    $sitename=$ligne["sitename"];
    $category=$catz->CategoryIntToStr($ligne["category"]);
    $page=CurrentPageName();
    $queryadd=queryadd();
    $title=array();
    if($field<>null){$title[]=$field;}
    if($value<>null){$title[]=$value;}
    if($sitename<>null){$title[]=$sitename;}
    if($category<>null){$title[]=$category;}

    $final_title=@implode("/",$title);

    if(!isset($_GET["engine"])){$_GET["engine"]="proxy";}

    $tpl->js_dialog4($final_title,"$page?template=yes$queryadd&engine={$_GET["engine"]}",1040);


}

function queryadd(){
    $total=null;
    $field=$_GET["field"];
    $value=$_GET["value"];
    $fieldenc=urlencode($field);
    $valueenc=urlencode($value);
    $siteid=$_GET["siteid"];
    // Pentest 2026-11-04 : sitename etait recopie sans encodage dans la page (XSS).
    $sitename=req_url($_GET["sitename"] ?? "");
    if(!isset($_GET["engine"])){$_GET["engine"]="proxy";}
    if(isset($_GET["total"])){
        $total="&total=yes";
    }

    if(isset($_GET["date-from"])){
        $addate="&date-from={$_GET["date-from"]}&date-to={$_GET["date-to"]}";
    }

    if(isset($_GET["t"])){$t="&t={$_GET["t"]}";}
    return "&engine={$_GET["engine"]}&field=$fieldenc&value=$valueenc&siteid=$siteid&sitename=$sitename$t$addate$total";
}

function template(){
    $field=$_GET["field"];
    $value=$_GET["value"];
    if(!isset($_GET["engine"])){$_GET["engine"]="proxy";}
    $fieldenc=urlencode($field);
    $valueenc=urlencode($value);
    $siteid=$_GET["siteid"];
    $t=$_GET["t"];
    $imgwait="<img src='/img/Eclipse-0.9s-120px.gif'>";
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top'><div id='vertical-timeline-$t'>$imgwait</div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:744px'><div id='users-graph-$t' style='width:744px'></div></td>";
    $html[]="<td style='vertical-align:top;width:99%'><div id='users-table-$t'></div></td>";
    $html[]="</tr>";

    $html[]="</table>";
    $html[]="<script>";
    $queryadd=queryadd();
    $html[]="Loadjs('$page?vertical-timeline=yes$queryadd');";
    echo $tpl->_ENGINE_parse_body($html);
}

function vertical_timeline(){
    $t=$_GET["t"];
    $tpl=new template_admin();
    $q=new postgres_sql();



    $DIFF_MIN=$_SESSION["STATSCOM_DAY"]["DIFF_MIN"];
    $DIFF_HOURS=$DIFF_MIN/60;
    echo "// DIFF: $DIFF_MIN hours";
    $time1=strtotime("{$_SESSION["STATSCOM_DAY"]["FROM"]} {$_SESSION["STATSCOM_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["STATSCOM_DAY"]["TO"]} {$_SESSION["STATSCOM_DAY"]["TOH"]}:00");

    if(isset($_GET["date-from"])){
        $time1=$_GET["date-from"];
        $time2=$_GET["date-to"];
    }

    if(isset($_GET["total"])){
        $time1=strtotime ( '-30 day');
        $time2=time();
        $DIFF_MIN=round(($time2-$time1)/60);
        $DIFF_HOURS=$DIFF_MIN/60;
    }

    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);


    $UNIT="{each_10minutes}";
    $SQL_DATE="zdate";
    $field=$_GET["field"];
    $value=$_GET["value"];
    $siteid=$_GET["siteid"];

    if($DIFF_HOURS>6){
        $UNIT="{hourly}";
        $SQL_DATE="date_part('hour', zdate)";
    }

    if($DIFF_HOURS>96) {
        $UNIT = "{daily}";
        $SQL_DATE = "date_part('day', zdate)";
    }
    // Pentest 2026-11-04 : field/value/siteid etaient concatenes dans la requete.
    $siteid=intval($siteid);
    $params=array();
    $AND=null;
    if($field<>null){
        if(!in_array(strtolower($field),array("username","ipaddr","mac"),true)){$tpl->js_mysql_alert("Invalid field");return false;}
        $AND=" AND $field=$1";
        $params[]=$value;
    }

    $sql="SELECT SUM(statscom.size) as size,$SQL_DATE as zdate FROM statscom,statscom_websites WHERE 
    statscom_websites.siteid=statscom.siteid
    AND statscom_websites.siteid=$siteid{$AND}
    AND zdate >'$strtime' and zdate < '$strtoTime' GROUP by $SQL_DATE ORDER BY $SQL_DATE";

    if($_GET["engine"]=="dnsfw"){
        $sql="SELECT SUM(statscom_dnsfw.hits) as size,$SQL_DATE as zdate FROM statscom_dnsfw,statscom_websites WHERE 
    statscom_websites.siteid=statscom_dnsfw.siteid
    AND statscom_websites.siteid=$siteid{$AND}
    AND zdate >'$strtime' and zdate < '$strtoTime' GROUP by $SQL_DATE ORDER BY $SQL_DATE";
    }


    $results=$q->QUERY_SQL_PARAMS($sql,$params);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = round($size);
        if($_GET["engine"]=="dnsfw"){
            $size = $ligne["size"];
        }
        echo "// $UNIT {$ligne["zdate"]} {$ligne["size"]} bytes = $size KB\n";
        $stime=strtotime($ligne["zdate"]);
        $datetext=date("H:i",$stime);
        if($DIFF_HOURS>6){
            $datetext="{$ligne["zdate"]}h";
        }
        if($DIFF_HOURS>96){
            $datetext=$tpl->_ENGINE_parse_body("{day}")." {$ligne["zdate"]}";
        }
        $xdata[]=$datetext;
        $ydata[]=$size;
    }


    $stimeFrom=$tpl->time_to_date($time1);
    $stimeTo=$tpl->time_to_date($time2);
    // Pentest 2026-11-04 : sitename, value et interval etaient recopies sans encodage dans la page (XSS).
    $sitenameT=req_js(req_html($_GET["sitename"] ?? ""));
    $valueT=req_js(req_html($value));
    $title="{downloaded_flow} (KB) $sitenameT $field $valueT $stimeFrom - $stimeTo {$UNIT}";
    if($_GET["engine"]=="dnsfw"){
        $title="DNS {queries} $sitenameT $field $valueT $stimeFrom - $stimeTo {$UNIT}";
    }
    $timetext=req_js(req_html($_GET["interval"] ?? ""));
    $highcharts=new highcharts();
    $highcharts->container="vertical-timeline-$t";
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="KB";
    if($_GET["engine"]=="dnsfw"){
        $highcharts->yAxisTtitle="{queries}";
    }
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="KB";
    if($_GET["engine"]=="dnsfw"){
        $highcharts->LegendSuffix=null;
    }
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{size}"=>$ydata);

    if($_GET["engine"]=="dnsfw"){
        $highcharts->datas=array("{queries}"=>$ydata);
    }

    echo $highcharts->BuildChart();
    $page=CurrentPageName();
    $queryadd=queryadd();

    if($field=="username"){return true;}
    if($field=="ipaddr"){return true;}
    if($field=="mac"){return true;}

    echo "Loadjs('$page?top-users=yes$queryadd');\n";
}

function top_users(){
    $engine=$_GET["engine"];
    $page=CurrentPageName();
    $t=$_GET["t"];
    $tpl=new template_admin();
    $q=new postgres_sql();
    $field=$_GET["field"];
    $value=$_GET["value"];
    $title="{top_members} (MB)";
    $DIFF_MIN=$_SESSION["STATSCOM_DAY"]["DIFF_MIN"];
    echo "// DIFF: $DIFF_MIN hours";
    $time1=strtotime("{$_SESSION["STATSCOM_DAY"]["FROM"]} {$_SESSION["STATSCOM_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["STATSCOM_DAY"]["TO"]} {$_SESSION["STATSCOM_DAY"]["TOH"]}:00");




    if(isset($_GET["total"])){
        $time1=strtotime ( '-30 day');
        $time2=time();
    }
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);

    // Pentest 2026-11-04 : field/value/siteid etaient concatenes dans la requete.
    $siteid=intval($_GET["siteid"]);
    $params=array();
    $AND=null;
    if($field<>null){
        if(!in_array(strtolower($field),array("username","ipaddr","mac"),true)){$tpl->js_mysql_alert("Invalid field");return;}
        $AND=" AND statscom.$field=$1";
        if($_GET["engine"]=="dnsfw"){
            $AND=" AND statscom_dnsfw.$field=$1";
        }
        $params[]=$value;
    }

    $sql="SELECT SUM(statscom.size) as size,username,ipaddr,MAC FROM statscom,statscom_websites  WHERE 
    statscom_websites.siteid=statscom.siteid AND
    statscom_websites.siteid=$siteid{$AND} AND 
    zdate >'$strtime' and zdate < '$strtoTime' GROUP by username,ipaddr,MAC ORDER BY size DESC LIMIT 15";

    if($_GET["engine"]=="dnsfw"){
        $title="{top_members}";
        $sql="SELECT SUM(statscom_dnsfw.hits) as size,ipaddr FROM statscom_dnsfw,statscom_websites  WHERE 
    statscom_websites.siteid=statscom_dnsfw.siteid AND
    statscom_websites.siteid=$siteid{$AND} AND 
    zdate >'$strtime' and zdate < '$strtoTime' GROUP by ipaddr ORDER BY size DESC LIMIT 15";


    }

    $results=$q->QUERY_SQL_PARAMS($sql,$params);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);
        if($_GET["engine"]=="dnsfw"){
            $size=$ligne["size"];
        }
        $username=$ligne["username"];
        $ipaddr=$ligne["ipaddr"];
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
    $highcharts->container="users-graph-$t";
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = $title;
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();
    echo "LoadAjax('users-table-$t','$page?users-table=$encoded&engine={$_GET["engine"]}');";


}

function top_users_table(){

    $tpl=new template_admin();
    $html[]="<table style='width:100%' class='table'>";
    $html[]="<tr>";
    $html[]="<th>{member}</th>";
    $html[]="</tr>";
    $table=unserialize(base64_decode($_GET["users-table"]),array("allowed_classes"=>false));
    foreach ( $table as $site=>$sizeM) {
        $h=explode("|",$site);
        $t=array();
        // Pentest 2026-11-04 : users-table (donnees serialisees) etait recopie sans encodage dans la page (XSS).
        if($h[0]<>null){
            $t[]=$tpl->td_href(req_html($h[0]),null,"Loadjs('fw.statscom.browse.users.php?field=username&value=".urlencode($h[0])."&engine={$_GET["engine"]}')");
        }
        if($h[1]<>null){
            $t[]=$tpl->td_href(req_html($h[1]),null,"Loadjs('fw.statscom.browse.users.php?field=mac&value=".urlencode($h[1])."&engine={$_GET["engine"]}')");
        }
        if($h[2]<>null){
            $t[]=$tpl->td_href(req_html($h[2]),null,"Loadjs('fw.statscom.browse.users.php?field=ipaddr&value=".urlencode($h[2])."&engine={$_GET["engine"]}')");
        }

        $value=FormatBytes($sizeM*1024);
        if($_GET["engine"]=="dnsfw"){
            $value=$tpl->FormatNumber($sizeM);
        }

        $html[]="<tr>";
        $html[]="<td><strong>".@implode("&nbsp;|&nbsp;",$t)."</strong></td>";
        $html[]="<td>$value</td>";
        $html[]="</tr>";

    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}

