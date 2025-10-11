<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["top-computers"])){top_ip();exit;}
if(isset($_GET["top-countries"])){top_countries();exit;}
if(isset($_GET["top-threats"])){top_reputations();exit;}
if(isset($_GET["top-usersagents-1"])){UsersAgentsWidgets();exit;}
if(isset($_GET["top-htons-1"])){HtonsWidgets();exit;}
if(isset($_GET["serviceid-js"])){field_serviceid();exit;}
if(isset($_GET["top-table"])){top_table();exit;}
if(isset($_GET["byip-js"])){byip_js();exit;}
if(isset($_GET["byip-popup"])){byip_popup();exit;}
if(isset($_GET["byip-popup-requests"])){byip_requests();exit;}
if(isset($_GET["byip-popup-requests-graph"])){byip_requests_graph();exit;}
if(isset($_GET["byip-popup-websites"])){byip_websites_graph();exit;}
page();

function field_serviceid():bool{
    $_SESSION[base64_encode(__FILE__)]["serviceid"]=$_GET["serviceid-js"];
    echo RefreshAllJS();
    return true;
}
function RefreshAllJS():string{
    $page=CurrentPageName();
    $addon="";
    if(isset($_GET["week"])){
        $addon="&week=yes";
    }
    if(isset($_GET["month"])){
        $addon="&month=yes";
    }

    $f[]= "Loadjs('$page?top-computers=yes$addon');";
    $f[]= "Loadjs('$page?top-countries=yes$addon');";
    $f[]= "Loadjs('$page?top-threats=yes$addon');";
    $f[]= "LoadAjax('top-table','$page?top-table=yes$addon');";


    return @implode("\n",$f);
}
function byip_js():bool{
    $hton=$_GET["byip-js"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ipaddr=long2ip($hton);
    return $tpl->js_dialog2($ipaddr,"$page?byip-popup=$hton",1024);
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $title="{APP_NGINX} {client_source_ip_address}";
    $html=$tpl->page_header($title,ico_dashboard,"{statistics_nginx_explain}","$page?tabs=yes",
        "nginx-statistics-hosts",
        "progress-nginx-statistics-hosts-restart",false,
        "table-nginx-statistics-hosts"
    );

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: {APP_NGINX} {client_source_ip_address}",$html);
        echo $tpl->build_firewall();
        return;
    }


    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $array["{today}"]="$page?table=yes";
    $array["{this_week}"]="$page?table=yes&week=yes";
    $array["{this_month}"]="$page?table=yes&month=yes";
    echo $tpl->tabs_default($array);
}
function byip_popup():bool{
    $page=CurrentPageName();
    $hton=$_GET["byip-popup"];
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $array["{today}"]="$page?byip-popup-requests=$hton";

    echo $tpl->tabs_default($array);
    return true;
}
function byip_requests():bool{
    $page=CurrentPageName();
    $hton=$_GET["byip-popup-requests"];
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body("<div id='byip-popup-requests' style='margin-top:10px;min-height:450px'></div>");
    echo "<table style='width:100%'>";
    echo "<tr>";
    echo "<td style='vertical-align:top;padding:5px;'><div style='margin-top:10px;min-height:450px' id='byip-popup-sites'></div></td>";
    echo "</tr>";
    echo "</table>";
    echo "<script>\nLoadjs('$page?byip-popup-requests-graph=$hton');\n";
    echo "Loadjs('$page?byip-popup-websites=$hton');\n";
    echo "</script>";
    return true;
}
function byip_websites_graph():bool{
    $timetext="{by_day}";
    $today=date("Y-m-d");
    $hton=$_GET["byip-popup-websites"];
    $sql="SELECT SUM(hits) as hits,serviceid FROM hotstinfos_realtime WHERE hton=$hton GROUP BY serviceid ORDER BY hits DESC";
    $q=new postgres_sql();

    $hash=hashServices();
    $results=$q->QUERY_SQL($sql);
    while($ligne=@pg_fetch_assoc($results)){
        $instance=get_servicename($ligne['serviceid']);
        $tcount=$ligne["hits"];
        $MAIN[$instance]=$tcount;

    }


    $highcharts=new highcharts();
    $highcharts->container="byip-popup-sites";
    $highcharts->PieDatas=$MAIN;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{visited_websites}";
    $highcharts->Title=$highcharts->PiePlotTitle;
    $highcharts->height=450;
    echo $highcharts->ApexPie();
    return true;
}
function hashServices():array{
    $zids=array();
    $q=new lib_sqlite(NginxGetDB());
    $sql="SELECT ID,servicename FROM nginx_services WHERE enabled=1 ORDER BY servicename";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        $servicename=$ligne["servicename"];
        $ID=$ligne["ID"];
        $zids[$ID]=$servicename;


    }
    return $zids;
}
function byip_requests_graph():bool{
    $timetext="{today}";
    $today=date("Y-m-d");
    $hton=$_GET["byip-popup-requests-graph"];
    $sql="SELECT SUM(hits) as hits,zdate FROM hotstinfos_realtime WHERE hton=$hton GROUP BY zdate ORDER BY zdate";
    $q=new postgres_sql();
    $results=$q->QUERY_SQL($sql);
    while($ligne=@pg_fetch_assoc($results)){
        $xdata[]=str_replace($today,"",$ligne["zdate"]);
        $ydata[]=$ligne["hits"];
    }
    if(count($xdata)<2){
        header("content-type: application/x-javascript");
        echo "// $sql;";
        echo "// Count = ".count($xdata)." <2";
        return true;
    }

    $title="{requests} $timetext";

    $highcharts=new highcharts();
    $highcharts->container="byip-popup-requests";
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="{requests}";
    $highcharts->xAxis_labels=true;
    $highcharts->height=300;
    $highcharts->LegendSuffix="{time}";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{requests}"=>$ydata);
    echo $highcharts->ApexChart();
    return true;
}
function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $serviceid=null;
    if(isset($_SESSION[base64_encode(__FILE__)]["serviceid"])){
        $serviceid=$_SESSION[base64_encode(__FILE__)]["serviceid"];
    }
    $sql="SELECT serviceid FROM hotstinfos_realtime GROUP BY serviceid";

    if(isset($_GET["week"])){
        $tz = new DateTimeZone(date_default_timezone_get());
        $start = (new DateTimeImmutable('today', $tz))->modify('monday this week')->setTime(0, 0, 0);
        $FilterTime=$start->format('Y-m-d H:i:s');
        $sql="SELECT serviceid FROM hotstinfos_days WHERE zdate >'$FilterTime' GROUP BY serviceid";
    }
    VERBOSE($sql,__LINE__);
    $results=$q->QUERY_SQL($sql);
    $Hash[null]="{all}";

    while($ligne=@pg_fetch_assoc($results)){
        $Hash[$ligne["serviceid"]]=get_servicename($ligne["serviceid"]);
    }

   $field=$tpl->DropDown($Hash, $serviceid, "{sitename}", "$page?serviceid-js=%s");

    $js="";
    $html[]="<div style='margin-top:10px'>";
    $html[]=$field;
    $html[]="</div>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;'><div id='top-computers' style='width:450px'></div></td>";
    $html[]="<td style='vertical-align:top;'><div id='top-countries' style='width:450px'></div></td>";
    $html[]="<td style='vertical-align:top;'><div id='top-threats' style='width:450px'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";

    $html[]="<div id='top-table'></div>";

    $html[]="<script>";
    $html[]=RefreshAllJS();
    $html[]="$js";
    $html[]="</script>";
    echo implode("",$html);

}

function top_table(){
    $tpl=new template_admin();
    $filters=array();
    $filters_text="";
    $FilterTime="";
    $page=CurrentPageName();
    $tblid="hotstinfos_realtime";
    if(isset($_GET["week"])){
        $tz = new DateTimeZone(date_default_timezone_get());
        $start = (new DateTimeImmutable('today', $tz))->modify('monday this week')->setTime(0, 0, 0);
        $FilterTime=$start->format('Y-m-d H:i:s');
        $filters[] = "hotstinfos_days.zdate > '$FilterTime'";
        $tblid="hotstinfos_days";
    }
    if(isset($_GET["month"])) {
        $tz = new DateTimeZone(date_default_timezone_get());
        $firstDay = (new DateTimeImmutable('today', $tz))
            ->modify('first day of this month')
            ->setTime(0, 0, 0);

        $FilterTime = $firstDay->format('Y-m-d H:i:s');
        $filters[] = "hotstinfos_days.zdate > '$FilterTime'";
        $tblid="hotstinfos_days";
    }

    
    if(isset($_SESSION[base64_encode(__FILE__)]["serviceid"])){
        $serviceid=$_SESSION[base64_encode(__FILE__)]["serviceid"];
    }
    if(is_numeric($serviceid)) {
        $filters[] = "$tblid.serviceid=$serviceid";
    }
    if(count($filters)>0){
        $filters_text=" AND ".@implode(" AND ",$filters);
    }

    $query = "SELECT SUM(hits) as hits,
       hotstinfos_realtime.hton,
       hotstinfos.country_name,
       hotstinfos.hostname,
       hotstinfos.abuseipdb
    FROM hotstinfos_realtime,hotstinfos 
    WHERE hotstinfos_realtime.hton=hotstinfos.hton $filters_text 
    GROUP BY hotstinfos_realtime.hton,hotstinfos.country_name,hotstinfos.hostname,hotstinfos.abuseipdb
    ORDER by hits DESC LIMIT 500";

    if(isset($_GET["week"]) OR isset($_GET["month"])){
        $query = "SELECT SUM(hits) as hits,
       hotstinfos_days.hton,
       hotstinfos.country_name,
       hotstinfos.hostname,
       hotstinfos.abuseipdb
            FROM hotstinfos_days,hotstinfos 
            WHERE hotstinfos_days.hton=hotstinfos.hton $filters_text 
            GROUP BY hotstinfos_days.hton,hotstinfos.country_name,hotstinfos.hostname,hotstinfos.abuseipdb
            ORDER by hits DESC LIMIT 500";
    }
    $query=str_replace("AND AND","AND",$query);
    $q = new postgres_sql();
    VERBOSE($query,__LINE__);
    $results = $q->QUERY_SQL($query);
    if(!$q->ok){
        echo $tpl->div_error("$query<br>$q->mysql_error");
        return false;
    }

    $tableid="table-".time();
    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
        	<th nowrap>{ipaddr}</th>
        	<th nowrap>{hostname}</th>
        	<th nowrap>{country}</small></th>
        	<th nowrap>{requests}</small></th>
        </tr>
  	</thead>
	<tbody>";

    $TRCLASS="";
    $td1prc=$tpl->table_td1prcLeft();

    while ($ligne = @pg_fetch_assoc($results)){
        $iconco=ico_computer;
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $hton=$ligne["hton"];
        $ipaddr=long2ip($ligne["hton"]);
        $hostname=$ligne["hostname"];
        $country_name=$ligne["country_name"];
        $class="";
        $hits=$tpl->FormatNumber($ligne["hits"]);
        $abuseipdb=intval($ligne["abuseipdb"]);
        if($abuseipdb==1){
            $class="class='text-danger'";
        }

        if(strlen($hostname)<3){
            $hostname=$ipaddr;
        }

        if( (strpos($hostname,".googlebot.com")>0) OR (strpos($hostname,".fbsv.net")>0) OR (strpos($hostname,".search.msn.com")>0) or (strpos($hostname,".petalsearch.com")>0)
            OR (strpos($hostname,".google.com")>0)
            OR (strpos($hostname,".qwant.com")>0)
            OR (strpos($hostname,".applebot.apple.com")>0)
            OR (strpos($hostname,".crawl.amazonbot.amazon")>0)
            OR (strpos($hostname,".mj12bot.com")>0)
            OR (strpos($hostname,".spider.yandex.com")>0)
            OR (strpos($hostname,".mojeek.com")>0)

        ){
            $iconco="fa-regular fa-user-robot";
            $class="class='text-success'";
        }

        $hostname=$tpl->td_href($hostname,null,"Loadjs('fw.modsecurity.threats.php?zoom-ip-js=$ipaddr')");
        $ipaddr=$tpl->td_href($ipaddr,null,"Loadjs('$page?byip-js=$hton')");

        $html[]="<tr class='$TRCLASS' id=''>";
        $html[]="<td style='font-weight:bold;width=1%' $class nowrap><i class=\"$iconco\"></i>&nbsp;$ipaddr</td>";
        $html[]="<td $class style='width:99%'>$hostname</td>";
        $html[]="<td $class $td1prc>$country_name</td>";
        $html[]="<td $class $td1prc>$hits</td>";
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
    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() { $('#$tableid').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function top_threats(){

    $filters=array();
    $serviceid=0;
    $filters_text="";
    $tblid="";
    if(isset($_SESSION[base64_encode(__FILE__)]["serviceid"])){
        $serviceid=$_SESSION[base64_encode(__FILE__)]["serviceid"];
    }
    if(is_numeric($serviceid)) {
        $filters[] = "serviceid=$serviceid";
    }
    if(count($filters)>0){
        $filters_text=" AND ".@implode(" AND ",$filters);
    }


    $query = "SELECT COUNT(*) as hits,ruleid
       FROM modsecurity_audit 
       WHERE DATE(created) = CURRENT_DATE $filters_text
       GROUP BY ruleid
       ORDER BY hits DESC";

    $q = new postgres_sql();
    $results = $q->QUERY_SQL($query);
    if (!$q->ok) {
        writelogs("$query $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }


    $Chartjs = new Chartjs(array());
    $Chartjs->container = "top-threats";


    while ($ligne = @pg_fetch_assoc($results)) {
        $hits = $ligne['hits'];
        $rule = $ligne['ruleid'];
        $Chartjs->PieDatas[$rule] = $hits;
    }
    $Chartjs->Title = "{top} {rules}";
    echo $Chartjs->Pie();
    return true;
}

function top_ip(){
    $filters=array();
    $serviceid=0;
    $filters_text="";
    $WHERE="";
    $subtitle="";
    if(isset($_SESSION[base64_encode(__FILE__)]["serviceid"])){
        $serviceid=$_SESSION[base64_encode(__FILE__)]["serviceid"];
        $subtitle=get_servicename($serviceid);
    }

    $tblid="hotstinfos_realtime";
    if(isset($_GET["week"])) {
        $tz = new DateTimeZone(date_default_timezone_get());
        $start = (new DateTimeImmutable('today', $tz))->modify('monday this week')->setTime(0, 0, 0);
        $FilterTime = $start->format('Y-m-d H:i:s');
        $filters[] = "hotstinfos_days.zdate > '$FilterTime'";
        $tblid = "hotstinfos_days";
    }
    if(isset($_GET["month"])) {
        $tz = new DateTimeZone(date_default_timezone_get());
        $firstDay = (new DateTimeImmutable('today', $tz))
            ->modify('first day of this month')
            ->setTime(0, 0, 0);

        $FilterTime = $firstDay->format('Y-m-d H:i:s');
        $filters[] = "hotstinfos_days.zdate > '$FilterTime'";
        $tblid="hotstinfos_days";
    }

    if(is_numeric($serviceid)) {
      $filters[] = "serviceid=$serviceid";
    }
    if(count($filters)>0){
        $WHERE=" WHERE ";
        $filters_text=@implode(" AND ",$filters);
    }

    $query = "SELECT SUM(hits) as hits,hton FROM $tblid$WHERE$filters_text GROUP by hton ORDER by hits DESC LIMIT 10";

    $q = new postgres_sql();
    $results = $q->QUERY_SQL($query);
    if (!$q->ok) {
        writelogs("$query $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }


    $Chartjs = new Chartjs(array());
    $Chartjs->container = "top-computers";


    while ($ligne = @pg_fetch_assoc($results)) {
        $hits = $ligne['hits'];
        $htons = $ligne['hton'];
        $ipAddress = long2ip($htons);
        $Chartjs->PieDatas[$ipAddress] = $hits;
    }
    $Chartjs->Title = "{top_ipaddr}";
    echo $Chartjs->Pie();
    return true;
}
function top_countries(){
    $filters=array();
    $serviceid=0;
    $subtitle="";

    $filters_text="";
    if(isset($_SESSION[base64_encode(__FILE__)]["serviceid"])){
        $serviceid=$_SESSION[base64_encode(__FILE__)]["serviceid"];
        if($serviceid>0) {
            $subtitle = " - " . get_servicename($serviceid);
        }
    }
    $tblid="hotstinfos_realtime";
    if(isset($_GET["week"])) {
        $tz = new DateTimeZone(date_default_timezone_get());
        $start = (new DateTimeImmutable('today', $tz))->modify('monday this week')->setTime(0, 0, 0);
        $FilterTime = $start->format('Y-m-d H:i:s');
        $filters[] = "hotstinfos_days.zdate > '$FilterTime'";
        $tblid = "hotstinfos_days";
        $subtitle=$subtitle." ({this_week})";
    }
    if(isset($_GET["month"])) {
        $tz = new DateTimeZone(date_default_timezone_get());
        $firstDay = (new DateTimeImmutable('today', $tz))
            ->modify('first day of this month')
            ->setTime(0, 0, 0);

        $FilterTime = $firstDay->format('Y-m-d H:i:s');
        $filters[] = "hotstinfos_days.zdate > '$FilterTime'";
        $tblid="hotstinfos_days";
        $subtitle=$subtitle." ({this_month})";
    }


    if(is_numeric($serviceid)) {
        $filters[] = "$tblid.serviceid=$serviceid";
    }
    if(count($filters)>0){
        $filters_text=" AND ".@implode(" AND ",$filters);
    }

    $query = "SELECT SUM(hits) as hits,hotstinfos.country_name 
    FROM $tblid,hotstinfos 
    WHERE $tblid.hton=hotstinfos.hton $filters_text GROUP by hotstinfos.country_name ORDER by hits DESC LIMIT 10";



    $q = new postgres_sql();
    $results = $q->QUERY_SQL($query);
    if (!$q->ok) {
        writelogs("$query $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }


    $Chartjs = new Chartjs(array());
    $Chartjs->container = "top-countries";


    while ($ligne = @pg_fetch_assoc($results)) {
        $hits = $ligne['hits'];
        $country_name = $ligne['country_name'];
        $Chartjs->PieDatas[$country_name] = $hits;
    }
    $Chartjs->Title = "{top} {countries}$subtitle";
    echo $Chartjs->Pie();
    return true;
}

function top_reputations(){
    $filters=array();
    $serviceid=0;
    $filters_text="";
    $subtitle="";
    if(isset($_SESSION[base64_encode(__FILE__)]["serviceid"])){
        $serviceid=$_SESSION[base64_encode(__FILE__)]["serviceid"];
        if($serviceid>0) {
            $subtitle = " - " . get_servicename($serviceid);
        }
    }

    $tblid="hotstinfos_realtime";
    if(isset($_GET["week"])) {
        $tz = new DateTimeZone(date_default_timezone_get());
        $start = (new DateTimeImmutable('today', $tz))->modify('monday this week')->setTime(0, 0, 0);
        $FilterTime = $start->format('Y-m-d H:i:s');
        $filters[] = "hotstinfos_days.zdate > '$FilterTime'";
        $tblid = "hotstinfos_days";
    }

    if(is_numeric($serviceid)) {
        $filters[] = "$tblid.serviceid=$serviceid";
    }
    if(count($filters)>0){
        $filters_text=" AND ".@implode(" AND ",$filters);
    }

    $query = "SELECT SUM(hits) as hits,hotstinfos.hton 
    FROM $tblid,hotstinfos 
    WHERE $tblid.hton=hotstinfos.hton AND hotstinfos.abuseipdb=1 $filters_text GROUP by hotstinfos.hton ORDER by hits DESC LIMIT 10";

    $q = new postgres_sql();
    $results = $q->QUERY_SQL($query);
    if (!$q->ok) {
        writelogs("$query $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }


    $Chartjs = new Chartjs(array());
    $Chartjs->container = "top-threats";


    while ($ligne = @pg_fetch_assoc($results)) {
        $hits = $ligne['hits'];
        $htons=$ligne["hton"];
        $ipAddress = long2ip($htons);

        $Chartjs->PieDatas[$ipAddress] = $hits;
    }
    $Chartjs->Title = "{top} {reputation}$subtitle";
    echo $Chartjs->Pie();
    return true;
}


function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return strval($ligne["servicename"]);
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
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