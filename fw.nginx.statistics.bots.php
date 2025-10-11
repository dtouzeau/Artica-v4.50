<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["data-find"])){data_find();exit;}
if(isset($_GET["worldmap"])){worldmap();exit;}
if(isset($_GET["map-data"])){worldmap_data();exit;}
if(isset($_GET["trustit"])){trustit();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["top-status-botnets"])){top_status();exit;}
if(isset($_GET["chart-top-botnets"])){chart_top_botnets();exit;}
if(isset($_GET["chart-top-sites"])){chart_top_sites();exit;}
if(isset($_GET["cnx-botnets-today"])){graphs_botnets_today();exit;}
if(isset($_GET["top-htons-1"])){HtonsWidgets();exit;}
if(isset($_GET["parameters-js"])){parameters_js();exit;}
if(isset($_GET["parameters-popup"])){parameters_popup();exit;}
if(isset($_POST["UseAbusesIPReputationKey"])){parameters_save();exit;}
if(isset($_GET["data-search"])){data_search();exit;}

page();
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $title="{APP_NGINX} Botnets";
    $html=$tpl->page_header($title,"fa-regular fa-user-robot","{statistics_nginx_botnets_explain}","$page?tabs=yes",
        "nginx-botnets",
        "progress-botnets-statistics-restart",false,
        "table-botnets-statistics"
    );

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: {APP_NGINX} {statistics}",$html);
        echo $tpl->build_firewall();
        return true;
    }


    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function parameters_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{parameters}","$page?parameters-popup=yes");
}
function parameters_popup():bool{
    $tpl=new template_admin();
    $UseAbusesIPReputationKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseAbusesIPReputationKey"));

    $form[]= $tpl->field_text("UseAbusesIPReputationKey","AbuseIP {API_KEY}",$UseAbusesIPReputationKey);
    $html[]= $tpl->form_outside("",$form,null,"{apply}", "dialogInstance2.close();", "AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function parameters_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reputation/abuseipdb");
    return admin_tracks_post("Save Reverse-Proxy statistics global settings");
}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
   // $array["{worldmap}"]="$page?worldmap=yes";
    $array["{status}"]="$page?table=yes";
    $array["{data}"]="$page?data-search=yes";
    $array["{Suspicious}"]="$page?data-search=yes&suspicious=yes";
    echo $tpl->tabs_default($array);
}
function top_status():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new postgres_sql();

    $ligne=$q->mysqli_fetch_array("SELECT zdate FROM botnets ORDER BY zdate ASC LIMIT 1");
    $StartDate=strtotime($ligne["zdate"]);

    $ligne=$q->mysqli_fetch_array("SELECT SUM(counter) as tcount FROM botnets");
    $Counter=intval($ligne["tcount"]);

    $results=$q->QUERY_SQL("SELECT category FROM botnets GROUP BY category");
    $c=0;
    while($ligne=@pg_fetch_assoc($results)) {
       if(strlen($ligne["category"])>0) {
           $c++;
       }
    }
    $results=$q->QUERY_SQL("SELECT category FROM botnets  WHERE trusted=0 GROUP by category");
    $d=0;
    while($ligne=@pg_fetch_assoc($results)) {
        if(strlen($ligne["category"])>0) {
            $d++;
        }
    }

    if($Counter==0) {
        $widget_rqs = $tpl->widget_style1("gray-bg", "fa-duotone fa-solid fa-raindrops", "{requests}", 0);

        $widget_Agents=$tpl->widget_style1("gray-bg","fa-regular fa-user-robot","{agents}",0);

        $widget_untrusted=$tpl->widget_style1("gray-bg","fas fa-question-circle","{Suspicious}",0);


    }else{
        $widget_rqs = $tpl->widget_style1("green-bg", "fa-duotone fa-solid fa-raindrops", "{requests} {since} ".$tpl->time_to_date($StartDate,true), $tpl->FormatNumber($Counter));

        $widget_Agents=$tpl->widget_style1("green-bg","fa-regular fa-user-robot","{agents}",$tpl->FormatNumber($c));

        $widget_untrusted=$tpl->widget_style1("yellow-bg","fas fa-question-circle","{Suspicious}",$tpl->FormatNumber($d));
    }
    $title="{APP_NGINX} Botnets";
    $topbuttons[]=array("Loadjs('$page?parameters-js=yes');",ico_params,"{parameters}");
    $TINY_ARRAY["TITLE"]=$title;
    $TINY_ARRAY["ICO"]="fa-regular fa-user-robot";
    $TINY_ARRAY["EXPL"]="{statistics_nginx_botnets_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["BUTTONS"]="";
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$widget_rqs</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$widget_Agents</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$widget_untrusted</td>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=$headsjs;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();


    $js=$tpl->RefreshInterval_js("top-status-botnets",$page,"top-status-botnets=yes");
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;'><div id='top-status-botnets'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;'><div id='cnx-botnets-today'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;'>";
    $html[]="    <table style='width:100%'>";
    $html[]="        <tr>";
    $html[]="            <td style='width:50%'><div id='chart-top-botnets'></div></td>";
    $html[]="            <td style='width:50%'><div id='chart-top-sites'></div></td>";
    $html[]="        </tr>";
    $html[]="    </table>";

    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="$js";
    $html[]="Loadjs('$page?cnx-botnets-today=yes');";
    $html[]="Loadjs('$page?chart-top-botnets=yes');";
    $html[]="Loadjs('$page?chart-top-sites=yes');";

    $html[]="</script>";
    echo implode("",$html);

}
function worldmap(){
$tpl=new template_admin();
$html[]="<table style='width:100%;margin-top:30px'>";
$html[]="<tr>";
$html[]="<td style='vertical-align:top;'><div id='world-map-container' style='width:1024px;height:400px'></div></td>";
$html[]="</tr>";
$html[]="<tr>";
$html[]="<td style='vertical-align:top;'><div id='world-map-data'></div></td>";
$html[]="</tr>";
$html[]="</table>";
$html[]=renderWorldMap();
echo $tpl->_ENGINE_parse_body($html);
}
function graphs_botnets_today():bool{

    $curday=date("Y-m-d 00:00:00");
    $q=new postgres_sql();
    $sql="SELECT SUM(counter) AS event_count,zdate FROM botnets WHERE zdate >'$curday' GROUP BY zdate ORDER BY zdate;";
    $ydata=array();
    $xdata=array();
    $results=$q->QUERY_SQL($sql);
    while($ligne=@pg_fetch_assoc($results)){
        $zdate=strtotime($ligne['zdate']);
        $xdata[]=date("H:i",$zdate);
        $ydata[]=$ligne["event_count"];
    }
    if(count($xdata)<2){
        header("content-type: application/x-javascript");
        echo "// $sql;";
        echo "// Count = ".count($xdata)." <2";
        return true;
    }

    $title="botnets/{connections} {today}";
    $highcharts=new highcharts();
    $highcharts->container="cnx-botnets-today";
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="{requests}";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="{days}";
    $highcharts->xAxisTtitle="{time}";
    $highcharts->datas=array("{requests}"=>$ydata);
    echo $highcharts->ApexChart();
    return true;


}



function chart_top_botnets(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $curday=date("Y-m-d 00:00:00");
    $query = "SELECT SUM(counter) as hits,category
    FROM botnets
    WHERE zdate >'$curday' GROUP by category ORDER by hits DESC LIMIT 10";;
    $q=new postgres_sql();
    $MAIN=array();
    $results=$q->QUERY_SQL($query);
    if(!$q->ok){
        header("content-type: application/x-javascript");
        echo "// $q->mysql_error;";
        return true;
    }
    while($ligne=@pg_fetch_assoc($results)){
       // $instance=get_servicename($ligne['serviceid']);
        $tcount=$ligne["hits"];
        $MAIN[$ligne["category"]]=$tcount;

    }
    if(count($MAIN)<2){
        header("content-type: application/x-javascript");
        echo "// $query;";
        echo "// Count = ".count($MAIN)." <2";
        return true;
    }
    $highcharts=new highcharts();
    $highcharts->container="chart-top-botnets";
    $highcharts->PieDatas=$MAIN;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{top} 10 botnets {today}";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->ApexPie();
    return true;

}
function chart_top_sites(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $curday=date("Y-m-d 00:00:00");
    $query = "SELECT SUM(counter) as hits,serviceid
    FROM botnets WHERE zdate >'$curday' GROUP by serviceid ORDER by hits DESC LIMIT 10";;
    $q=new postgres_sql();
    $MAIN=array();
    $results=$q->QUERY_SQL($query);
    if(!$q->ok){
        header("content-type: application/x-javascript");
        echo "// $q->mysql_error;";
        return true;
    }
    while($ligne=@pg_fetch_assoc($results)){
        $instance=get_servicename($ligne['serviceid']);
        $tcount=$ligne["hits"];
        $MAIN[$instance]=$tcount;

    }
    if(count($MAIN)<2){
        header("content-type: application/x-javascript");
        echo "// $query;";
        echo "// Count = ".count($MAIN)." <2";
        return true;
    }
    $highcharts=new highcharts();
    $highcharts->container="chart-top-sites";
    $highcharts->PieDatas=$MAIN;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{top_impacted_websites_agnt} {today}";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->ApexPie();
    return true;
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
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
function data_search():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $suspicious="";
    if(isset($_GET["suspicious"])){
        $suspicious="&suspicious=1";
    }
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,"","","","&data-find=yes$suspicious");
    echo "</div>";
    return true;
}
function trustit(){
    $srcip=$_GET["trustit"];
    $category=$_GET["category"];
    $function=$_GET["function"];
    $q=new postgres_sql();
    $q->QUERY_SQL("UPDATE botnets SET trusted=1 WHERE category='$category' AND srcip='$srcip'");
    echo "$function();";

}
function data_find():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $html[]="<table id='table-fireqos-interfaces' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{family}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>{useragent}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>{trusted}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>{queries}</th>";
    if(isset($_GET["suspicious"])){
        $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    }
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $suspicious1="";
    if(isset($_GET["suspicious"])){
        $suspicious1="WHERE trusted=0";

    }
    $query = "SELECT SUM(counter) as hits,category,ua,srcip,trusted,hostname
    FROM botnets $suspicious1 GROUP by category,ua,srcip,trusted,hostname ORDER by hits DESC LIMIT 250";


    $search=$_GET["search"];
    if(strlen($search)>0){
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $query = "SELECT SUM(counter) as hits,category,ua,srcip,trusted,hostname
    FROM botnets WHERE ( (ua LIKE '$search') OR (category LIKE '$search') OR (hostname LIKE '$search')) GROUP by category,ua,srcip,trusted,hostname ORDER by hits DESC LIMIT 250";
        if(isset($_GET["suspicious"])){
            $query = "SELECT SUM(counter) as hits,category,ua,srcip,trusted
    FROM botnets WHERE trusted=0 AND ( (ua LIKE '$search') OR (category LIKE '$search') OR (hostname LIKE '$search')) GROUP by category,ua,srcip,trusted,hostname ORDER by hits DESC LIMIT 250";

        }
    }

    $q=new postgres_sql();
    if(!$q->FIELD_EXISTS("botnets","hostname")){
        $q->QUERY_SQL("ALTER TABLE `botnets` ADD `hostname` varchar(255)");
        if(!$q->ok){
            echo $tpl->div_error($q->mysql_error);
        }
    }
    $results=$q->QUERY_SQL($query);
    if(!$q->ok){
        header("content-type: application/x-javascript");
        echo "// $q->mysql_error;";
        return true;
    }
    $TRCLASS="";
    while($ligne=@pg_fetch_assoc($results)){

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5($ligne);
        $category=$ligne["category"];
        $srcip=$ligne["srcip"];
        $trusted=$ligne["trusted"];
        $ua=$ligne["ua"];
        $ipaddr=long2ip($srcip);
        $ico_check="";
        $hits=$ligne["hits"];
        if($trusted==1){
            $ico_check="<i class=\"".ico_check."\"></i>";
        }
        $hostname=$ligne["hostname"];
        $hostc="<i class='".ico_computer."'></i>&nbsp;";
        if(strlen($hostname)<3) {
            $hostc="";
            $hostname = gethostbyaddr($ipaddr);
            $q->QUERY_SQL("UPDATE botnets SET hostname='$hostname' WHERE srcip='$srcip'");
            if(!$q->ok){
                echo $tpl->div_error($q->mysql_error);
            }
        }

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap><i class='fa-regular fa-user-robot'></i>&nbsp;<strong>$category</strong></td>";
        $html[]="<td style='width:1%' nowrap>$ipaddr<br><small>$hostc$hostname</small></td>";
        $html[]="<td style='width:99%'>$ua</td>";
        $html[]="<td style='width:1%' class='center' nowrap><strong>$ico_check</strong></td>";
        $html[]="<td style='width:1%' class='center' nowrap><strong>$hits</strong></td>";
        if(isset($_GET["suspicious"])){
            $js="Loadjs('$page?trustit=$srcip&category=$category&function=$function')";
            $hostkey="<button class='btn btn-default btn-xs' OnClick=\"$js\">{trusted}</button>";
            $html[]="<td style='width:1%' class='center' nowrap>$hostkey</td>";
        }

        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}