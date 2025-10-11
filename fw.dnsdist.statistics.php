<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["domain-search"])){domains_search();exit;}
if(isset($_GET["restart-js"])){restart_js();exit;}
if(isset($_GET["today"])){today();exit;}
if(isset($_GET["week"])){week_stats();exit;}
if(isset($_GET["hourly"])){hourly_stats();exit;}
if(isset($_GET["month"])){month_stats();exit;}
if(isset($_GET["yesterday"])){yesterday_stats();exit;}
if(isset($_GET["year"])){year_stats();exit;}
if(isset($_GET["top-categories"])){top_categories();exit;}
if(isset($_GET["restart-perform"])){restart_perform();exit;}
if(isset($_GET["domains-start"])){domains_start();exit;}
if(isset($_GET["search-category"])){filter_category();exit;}
page();


function page(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $array["{yesterday}"]="$page?yesterday=yes";
    $array["{hourly}"]="$page?hourly=yes";
    $array["{today}"]="$page?today=yes";
    $array["{this_week}"]="$page?week=yes";
    $array["{this_month}"]="$page?month=yes";
    $array["{this_year}"]="$page?year=yes";
    $array["{domains}"]="$page?domains-start=yes";
    echo "<div style='margin-top:10px'>";
    echo $tpl->tabs_default($array);
    echo "</div>";
}
function filter_category(){
    $_SESSION["NGINXSEARCH"]["CATEGORY"]=$_GET["search-category"];
    $function=$_GET["function"];
    header("content-type: application/x-javascript");
    echo "$function();";
}

function domains_start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'>";
    $q=new postgres_sql();
    $sql="SELECT SUM(hits) as hits,categoryid FROM dnsfw_stats GROUP BY categoryid ORDER BY hits DESC";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);return false;}
    $options["DROPDOWN"]["CONTENT"][$tpl->_ENGINE_parse_body("{all}")]="Loadjs('$page?search-category=&function=%s')";
    $catz=new mysql_catz();
    while ($ligne = pg_fetch_assoc($results)) {
        $cid=$ligne["categoryid"];
        $category=$catz->CategoryIntToStr($cid);
        $options["DROPDOWN"]["CONTENT"][$category]="Loadjs('$page?search-category=$cid&function=%s')";
    }

    $options["DROPDOWN"]["TITLE"]=$tpl->_ENGINE_parse_body("{categories}");
    echo $tpl->search_block($page,null,null,null,"&domain-search=yes",$options);
    echo "</div>";
    return true;
}

function domains_search():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new postgres_sql();
    $id=time();
    $sql="SELECT SUM(hits) as hits,domain,categoryid FROM dnsfw_stats GROUP by (domain,categoryid) ORDER BY hits DESC LIMIT 150";
    $html[]="<table id='table-$id' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{domains}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{category}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hits}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    if(isset( $_SESSION["NGINXSEARCH"]["CATEGORY"])){
        if(strlen($_SESSION["NGINXSEARCH"]["CATEGORY"])>0) {
            $catz = " AND categoryid={$_SESSION["NGINXSEARCH"]["CATEGORY"]}";
            $catz2=" WHERE categoryid={$_SESSION["NGINXSEARCH"]["CATEGORY"]}";
        }
    }
    if( strlen($_GET["search"])>2 ){
        $search=str_replace("*","%",$_GET["search"]);
        $sql="SELECT SUM(hits) as hits,domain,categoryid FROM dnsfw_stats 
        WHERE domain LIKE '$search'$catz
        GROUP by (domain,categoryid) ORDER BY hits DESC LIMIT 150";
    }else{
        $sql="SELECT SUM(hits) as hits,domain,categoryid FROM dnsfw_stats $catz2 GROUP by (domain,categoryid) ORDER BY hits DESC LIMIT 150";
    }

    $TRCLASS=null;
    $ico=ico_earth;
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);return false;}
    $catz=new mysql_catz();
    while ($ligne = pg_fetch_assoc($results)) {
        $hits = $ligne["hits"];
        $category=$catz->CategoryIntToStr($ligne["categoryid"]);
        $domain=$ligne["domain"];
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $zmd5=md5(serialize($ligne));
        $html[]="<tr class='$TRCLASS' id='$zmd5'>";
        $html[]="<td width=99% nowrap><i class=\"$ico\"></i>&nbsp;&nbsp;$domain</td>";
        $html[]="<td width=1% nowrap>$category</td>";
        $html[]="<td  width=1% >". $tpl->FormatNumber($hits)."</td>";
        $html[]="<td width=1% nowrap></td>";
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
    $html[]="</table><div><small></small></div>";
    $html[]="
<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$id').footable( {\"filtering\": {\"enabled\": false},\"sorting\": {\"enabled\": true} } ); });
	
</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function restart_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=$_GET["id"];
    $html=base64_encode($tpl->_ENGINE_parse_body("<div style='margin:50px;' class='center'><H1>{restarting} {please_wait}....</H1></div>"));

    $f[]="document.getElementById('$id').innerHTML=base64_decode('$html');";
    $f[]="Loadjs('$page?restart-perform=yes&id=$id')";
    header("content-type: application/x-javascript");
    echo @implode("\n",$f);
}
function restart_perform():bool{
    $tpl=new template_admin();
    admin_tracks("Restarting the DNS Firewall service");
    $sock=new sockets();
    $data=$sock->REST_API("/dnsfw/service/restart");
    $json=json_decode($data);
    $id=$_GET["id"];
    if (json_last_error()> JSON_ERROR_NONE) {
        return $tpl->js_error(json_last_error_msg());
    }
    header("content-type: application/x-javascript");
    if(!$json->Status){
        $html=base64_encode($tpl->_ENGINE_parse_body("<div style='margin:50px;' class='center'><H1 style='color:red'>$json->Error</H1></div>"));
        echo "document.getElementById('$id').innerHTML=base64_decode('$html');";
        return false;
    }

    echo "document.getElementById('$id').innerHTML='';";
    return true;

}
function year_stats(){
    $qq="year";
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();
    $html[]="<div id='dnsdist-restart-service' style='margin-top:10px'></div>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td colspan='2' style='vertical-align: top'>";
    $html[]="<div class='center' colspan=2><img src='img/squid/dnsfw-$qq.flat.png?t=$t'></div>";
    $html[]="</td>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-categories'></div></td>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-rules'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-ipaddr'></div></td>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-domain'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=dnsdist_status_tiny();
    $html[]="Loadjs('$page?top-categories=$qq&field=categoryid&id=top-categories');";
    $html[]="Loadjs('$page?top-categories=$qq&field=ruleid&id=top-rules');";
    $html[]="Loadjs('$page?top-categories=$qq&field=ipaddr&id=top-ipaddr');";
    $html[]="Loadjs('$page?top-categories=$qq&field=domain&id=top-domain');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function hourly_stats(){
    $qq="hourly";
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();
    $html[]="<div id='dnsdist-restart-service' style='margin-top:10px'></div>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td colspan='2' style='vertical-align: top'>";
    $html[]="<div class='center' colspan=2><img src='img/squid/dnsfw-$qq.flat.png?t=$t'></div>";
    $html[]="</td>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-categories'></div></td>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-rules'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-ipaddr'></div></td>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-domain'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=dnsdist_status_tiny();
    $html[]="Loadjs('$page?top-categories=$qq&field=categoryid&id=top-categories');";
    $html[]="Loadjs('$page?top-categories=$qq&field=ruleid&id=top-rules');";
    $html[]="Loadjs('$page?top-categories=$qq&field=ipaddr&id=top-ipaddr');";
    $html[]="Loadjs('$page?top-categories=$qq&field=domain&id=top-domain');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function yesterday_stats(){
    $qq="yesterday";
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();
    $html[]="<div id='dnsdist-restart-service' style='margin-top:10px'></div>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td colspan='2' style='vertical-align: top'>";
    $html[]="<div class='center' colspan=2><img src='img/squid/dnsfw-$qq.flat.png?t=$t'></div>";
    $html[]="</td>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-categories'></div></td>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-rules'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-ipaddr'></div></td>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-domain'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=dnsdist_status_tiny();
    $html[]="Loadjs('$page?top-categories=$qq&field=categoryid&id=top-categories');";
    $html[]="Loadjs('$page?top-categories=$qq&field=ruleid&id=top-rules');";
    $html[]="Loadjs('$page?top-categories=$qq&field=ipaddr&id=top-ipaddr');";
    $html[]="Loadjs('$page?top-categories=$qq&field=domain&id=top-domain');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function week_stats(){
    $qq="week";
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();
    $html[]="<div id='dnsdist-restart-service' style='margin-top:10px'></div>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td colspan='2' style='vertical-align: top'>";
    $html[]="<div class='center' colspan=2><img src='img/squid/dnsfw-$qq.flat.png?t=$t'></div>";
    $html[]="</td>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-categories'></div></td>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-rules'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-ipaddr'></div></td>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-domain'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=dnsdist_status_tiny();
    $html[]="Loadjs('$page?top-categories=$qq&field=categoryid&id=top-categories');";
    $html[]="Loadjs('$page?top-categories=$qq&field=ruleid&id=top-rules');";
    $html[]="Loadjs('$page?top-categories=$qq&field=ipaddr&id=top-ipaddr');";
    $html[]="Loadjs('$page?top-categories=$qq&field=domain&id=top-domain');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function month_stats(){
    $qq="month";
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();
    $html[]="<div id='dnsdist-restart-service' style='margin-top:10px'></div>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td colspan='2' style='vertical-align: top'>";
    $html[]="<div class='center' colspan=2><img src='img/squid/dnsfw-$qq.flat.png?t=$t'></div>";
    $html[]="</td>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-categories'></div></td>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-rules'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-ipaddr'></div></td>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-domain'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=dnsdist_status_tiny();
    $html[]="Loadjs('$page?top-categories=$qq&field=categoryid&id=top-categories');";
    $html[]="Loadjs('$page?top-categories=$qq&field=ruleid&id=top-rules');";
    $html[]="Loadjs('$page?top-categories=$qq&field=ipaddr&id=top-ipaddr');";
    $html[]="Loadjs('$page?top-categories=$qq&field=domain&id=top-domain');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function today():bool{
    $qq="day";
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();
    $html[]="<div id='dnsdist-restart-service' style='margin-top:10px'></div>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td colspan='2' style='vertical-align: top'>";
    $html[]="<div class='center' colspan=2><img src='img/squid/dnsfw-$qq.flat.png?t=$t'></div>";
    $html[]="</td>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-categories'></div></td>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-rules'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-ipaddr'></div></td>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-domain'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=dnsdist_status_tiny();
    $html[]="Loadjs('$page?top-categories=$qq&field=categoryid&id=top-categories');";
    $html[]="Loadjs('$page?top-categories=$qq&field=ruleid&id=top-rules');";
    $html[]="Loadjs('$page?top-categories=$qq&field=ipaddr&id=top-ipaddr');";
    $html[]="Loadjs('$page?top-categories=$qq&field=domain&id=top-domain');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function top_categories():bool{
    $page=CurrentPageName();
    $t=$_GET["suffix"];
    $tpl=new template_admin();
    $q=new postgres_sql();

    $qSqlite=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $title="{top_categories_by_hits}";
    $QueryTime=$_GET["top-categories"];
    $field=$_GET["field"];

    if($QueryTime=="hourly"){
        $strtime=date("Y-m-d H:00:00");
        $strtoTime=date("Y-m-d H:59:59");

    }

    if($QueryTime=="day"){
        $strtime=date("Y-m-d 00:00:00");
        $strtoTime=date("Y-m-d 23:59:59");

    }
    if($QueryTime=="week"){
        $now = new DateTime();
        $firstDayOfWeek = $now->modify('Monday this week');
        $strtime=$firstDayOfWeek->format('Y-m-d 00:00:00');
        $lastDayOfWeek = $now->modify('Sunday this week')->setTime(23, 59, 59);
        $strtoTime= $lastDayOfWeek->format('Y-m-d H:i:s');
    }
    if($QueryTime=="month"){
        $now = new DateTime();
        $firstDayOfWeek = $now->modify('first day of this month');
        $strtime=$firstDayOfWeek->format('Y-m-d 00:00:00');
        $lastDayOfWeek = $now->modify('last day of this month')->setTime(23, 59, 59);
        $strtoTime= $lastDayOfWeek->format('Y-m-d H:i:s');
    }
    if($QueryTime=="year"){
        $now = new DateTime();
        $firstDayOfWeek = $now->modify('first day of this year');
        $strtime=$firstDayOfWeek->format('Y-m-d 00:00:00');
        $lastDayOfWeek = $now->modify('last day of this year')->setTime(23, 59, 59);
        $strtoTime= $lastDayOfWeek->format('Y-m-d H:i:s');
    }
    if($QueryTime=="yesterday"){
        $now = new DateTime();
        $firstDayOfWeek = $now->modify('yesterday');
        $strtime=$firstDayOfWeek->format('Y-m-d 00:00:00');
        $strtoTime= $firstDayOfWeek->format('Y-m-d 23:59:59');
    }
    if($field=="ruleid") {
        $title="{top} {rules}";
    }
    if($field=="ipaddr") {
        $title="{top_ipaddr}";
    }
    if($field=="domain") {
        $title="{top_domains}";
    }
    $PieData=array();
    $sql="SELECT SUM(hits) as hits,$field FROM dnsfw_stats WHERE zdate >'$strtime' and zdate < '$strtoTime' GROUP by $field ORDER BY hits DESC LIMIT 15";

    if(!$q->TABLE_EXISTS("dnsfw_stats")){
        return true;
    }

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}
    $catz=new mysql_catz();
    while ($ligne = pg_fetch_assoc($results)) {
        $hits = $ligne["hits"];
        $text=$ligne[$field];
        if($field=="categoryid") {
            $text = $catz->CategoryIntToStr($ligne["categoryid"]);
        }
        if($field=="ruleid") {
            $ligne2=$qSqlite->mysqli_fetch_array("SELECT rulename FROM dnsdist_rules WHERE ID='{$ligne["ruleid"]}'");
            $text=$ligne2["rulename"];
        }



        if(strlen($text)<3){continue;}
        $PieData[$text]=$hits;
    }

    if(count($PieData)==0){
        return true;
    }
   // $encoded=base64_encode(serialize($PieData2));

    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = $title;
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();
    return true;
    //echo "LoadAjax('top-categories','$page?categories-table=$encoded');";


}
function dnsdist_status_tiny():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $users=new usersMenus();
    if($users->AsDnsAdministrator) {
        $topbuttons[] = array("Loadjs('$page?addhost-js=yes&function={$_GET["function"]}');", "fad fa-desktop", "{new_host_resolve}");
    }
    $topbuttons[] = array("LoadAjaxSilent('MainContent','fw.dns.dnsdist.rules.php');", "fab fa-gripfire", "{firewall_rules}");

    if($users->AsDnsAdministrator) {
        $topbuttons[] = array("Loadjs('$page?restart-js=yes&function={$_GET["function"]}&id=dnsdist-restart-service');", ico_refresh, "{restart_service}");
    }

    $APP_DNSDIST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSDIST_VERSION");
    $error=CountOfInterfaces();


    $TINY_ARRAY["TITLE"]="{APP_DNSDIST} v$APP_DNSDIST_VERSION {statistics}";
    $TINY_ARRAY["ICO"]="fab fa-gripfire";
    $TINY_ARRAY["EXPL"]="$error{APP_DNSDIST_EXPLAIN2}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

}

function CountOfInterfaces():string{
    $PowerDNSListenAddr=explode("\n",trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSListenAddr")));
    $Count=0;
    foreach ($PowerDNSListenAddr as $interface){
        $interface=trim($interface);
        if (strlen($interface)<3){
            continue;
        }
        $Count++;
    }
    if($Count>0){return "";}
    return "<p class='text-danger'><strong>{no_listen_interfaces_defined}</strong></p>";
}