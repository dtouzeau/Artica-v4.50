<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["ruleid"])){serviceid_js();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_POST["ID"])){Save();exit;}
if(isset($_GET["main"])){popup();exit;}
if(isset($_GET["engine"])){engine();exit;}
if(isset($_GET["graph"])){graph();exit;}
www_js();


function www_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=$_GET["serviceid"];
    $servicename=get_servicename($ID);
    if($ID==0){$servicename="{all}";}
    return $tpl->js_dialog4("#$ID - $servicename - {statistics}", "$page?tabs=$ID",1200);
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return strval($ligne["servicename"]);
}
function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}
function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["tabs"]);


    $array["{requests}"] = "$page?main=$ID&type=requests";
    $array["{bandwidth}"] = "$page?main=$ID&type=bandwidth";
    $array["{requests} {errors} 4xx"] = "$page?main=$ID&type=requestserr";
    $array["{server} {errors} 5xx"] = "$page?main=$ID&type=servererr";
    $array["{cached_requests}"] = "$page?main=$ID&type=cache";

    echo $tpl->tabs_default($array);
    return true;
}
function popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=$_GET["main"];
    $mtitle=$_GET["type"];
    $mtitle_text="{{$mtitle}}";
    $array["hourly"]="{today} {this_hour}";
    $array["day"]="{today}";
    $array["yesterday"]="{yesterday}";
    $array["week"]="{this_week}";
    $array["month"]="{month}";
    $array["year"]="{year}";
    $t=time();
    if($mtitle=="requestserr"){
        $mtitle_text="{requests} {errors} (4xx)";
    }
    if($mtitle=="servererr"){
        $mtitle_text="{server} {errors} (5xx)";
    }
    if($mtitle=="cache"){
        $mtitle_text="{cached_requests}";
    }

    foreach ($array as $suffix=>$title){
        $unit=null;
        if($mtitle=="bandwidth"){
            $unit=" MB";
        }
        $id=md5("$ID-$suffix-$mtitle");
        $title=base64_encode($tpl->_ENGINE_parse_body("$mtitle_text $title$unit"));
        $html[]="<div id='$id'></div>";
        $js[]="Loadjs('$page?graph=$ID&type=$mtitle&period=$suffix&id=$id&title=$title')";
    }
    $html[]="<script>";
    $html[]=@implode("\n",$js);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function graph():bool{
    $ID=intval($_GET["graph"]);
    $tpl=new template_admin();
    $type=$_GET["type"];
    $type_graphs="{{$type}}";
    $q=new postgres_sql();
    $sql="SELECT to_char(date_trunc('hour', zdate), 'MM-DD') AS hour_formatted, COUNT(*) AS event_count FROM nginx_stats GROUP BY 1 ORDER BY 1;";

    $field="requestcounter";
    if($type=="bandwidth"){
        $field="outbytes";
    }
    if($type=="requestserr"){
        $field="fourxx";
        $type_graphs="4xx";
    }
    if($type=="servererr"){
        $field="fivexx";
        $type_graphs="5xx";
    }
    if($type=="servererr"){
        $field="hit";
        $type_graphs=$tpl->_ENGINE_parse_body("{cached_requests}");
    }

    $mainfilter="serviceid=$ID AND";
    if($ID==0){
        $mainfilter="";
    }

    $FrenchTime=false;
    $formatHour=false;
    $formatMins=false;
    if($_GET["period"]=="hourly"){
        $monday=date("Y-m-d H:00:00",strtotime('-1 hours'));
        $sql="SELECT SUM($field) AS event_count, zdate as hour_formatted FROM nginx_stats WHERE $mainfilter zdate>'$monday' GROUP BY zdate ORDER BY zdate";
        $timetext="Time";
        $formatMins=true;
    }
    if($_GET["period"]=="yesterday"){
        $monday_start = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $monday_end = date('Y-m-d 23:59:59', strtotime('-1 day'));
        $field_time="date_trunc('hour', zdate) + INTERVAL '10 minute' * floor(EXTRACT(MINUTE FROM zdate) / 10)";
        $formatHour=true;
        $sql="
        SELECT SUM(events) as event_count,hours as hour_formatted FROM(
        SELECT SUM($field) AS events, $field_time as hours FROM nginx_stats WHERE $mainfilter zdate>'$monday_start' AND zdate < '$monday_end' GROUP BY hours ORDER BY hours) as t GROUP by hour_formatted";
        $timetext="Time";

    }
    if($_GET["period"]=="day"){
        $formatHour=true;
        $monday = date('Y-m-d 00:00:00');
        $field_time="date_trunc('hour', zdate) + INTERVAL '10 minute' * floor(EXTRACT(MINUTE FROM zdate) / 10) as hour_formatted";
        $sql="SELECT SUM($field) AS event_count, $field_time FROM nginx_stats WHERE $mainfilter zdate>'$monday' GROUP BY hour_formatted ORDER BY hour_formatted";
        $timetext="Time";
    }
    if($_GET["period"]=="week"){
        $date = new DateTime();
        $date->modify('this week');
        $monday=$date->format('Y-m-d')." 00:00:00";
        $field_time="to_char(date_trunc('hour', zdate), 'YYYY-MM-DD HH24') as hour_formatted";
        $sql="SELECT SUM($field) AS event_count, $field_time FROM nginx_stats WHERE $mainfilter zdate>'$monday' GROUP BY hour_formatted ORDER BY hour_formatted";
        $timetext="Hour";
        $FrenchTime=true;
    }
    if($_GET["period"]=="month"){
        $monday=date("Y-m-")."01 00:00:00";
        $field_time="to_char(date_trunc('day', zdate), 'DD') as hour_formatted";
        $sql="SELECT SUM($field) AS event_count, $field_time FROM nginx_stats WHERE $mainfilter zdate>'$monday' GROUP BY hour_formatted ORDER BY hour_formatted";
        $timetext="Hour";
    }
    if($_GET["period"]=="year"){
        $monday=date("Y-")."01-01 00:00:00";
        $field_time="to_char(date_trunc('month', zdate), 'MM') as hour_formatted";
        $sql="SELECT SUM($field) AS event_count, $field_time FROM nginx_stats WHERE $mainfilter zdate>'$monday' GROUP BY hour_formatted ORDER BY hour_formatted";
        $timetext=$tpl->_ENGINE_parse_body("{months}");
    }
    $GROUP=array();
    $title=base64_decode($_GET["title"]);
    $ydata=array();
    $xdata=array();
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        $tpl->js_mysql_alert($q->mysql_error."<br>$sql");
        return true;
    }

    while($ligne=@pg_fetch_assoc($results)){

        if($type=="bandwidth"){
            $ligne["event_count"]=$ligne["event_count"]/1024;
            $ligne["event_count"]=$ligne["event_count"]/1024;
        }
        if($formatHour){
            $time=strtotime($ligne["hour_formatted"]);
            $ligne["hour_formatted"]=date("H",$time)."h".date("i",$time);
        }
        if($formatMins){
            $time=strtotime($ligne["hour_formatted"]);
            $ligne["hour_formatted"]=date("H:i:s",$time);
        }

        if($FrenchTime){
            $time=strtotime($ligne["hour_formatted"].":00:00");
            $ligne["hour_formatted"]=date("{l} H",$time)."h";
            $ligne["hour_formatted"]=$tpl->_ENGINE_parse_body($ligne["hour_formatted"]);
        }

        if(!isset($GROUP[$ligne["hour_formatted"]])){
            $GROUP[$ligne["hour_formatted"]]=$ligne["event_count"];
        }else{
            $GROUP[$ligne["hour_formatted"]]=intval($GROUP[$ligne["hour_formatted"]])+intval($ligne["event_count"]);
        }
    }

    foreach ($GROUP as $hour_formatted=>$event_count) {
        $xdata[] = $hour_formatted;
        $ydata[] = $event_count;
    }

    if(count($ydata)<2){
        echo "// $sql is less than 2";
        return false;
    }



    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="$type_graphs";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="{date}";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("$type_graphs"=>$ydata);
    echo $highcharts->ApexChart();
    return true;
}



