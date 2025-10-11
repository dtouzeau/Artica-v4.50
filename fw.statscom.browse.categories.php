<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["template"])){template();exit;}
if(isset($_GET["vertical-timeline"])){vertical_timeline();exit;}
if(isset($_GET["top-sites"])){top_sites();exit;}
if(isset($_GET["top-users"])){top_users();exit;}
if(isset($_GET["sites-table"])){top_sites_table();exit;}
if(isset($_GET["users-table"])){top_users_table();exit;}
if(isset($_GET["top-categories"])){top_categories();exit;}
if(isset($_GET["categories-table"])){top_categories_table();exit;}
if(isset($_GET["members"])){members_table();exit;}
if(isset($_GET["sites"])){sites_table();exit;}
js();


function js(){
    $tpl=new template_admin();
    $field=$_GET["field"];
    $value=$_GET["value"];

    $fieldenc=urlencode($field);
    $valueenc=urlencode($value);
    $category=$_GET["category"];
    $catz=new mysql_catz();
    $categoryname=$catz->CategoryIntToStr($category);
    if(!isset($_GET["engine"])){$_GET["engine"]="proxy";}

    $page=CurrentPageName();
    $tpl->js_dialog3("$field/$value/{category}: $categoryname","$page?tabs=yes&field=$fieldenc&value=$valueenc&category=$category&engine={$_GET["engine"]}",1040);


}

function tabs(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $field=$_GET["field"];
    $value=$_GET["value"];
    $category=$_GET["category"];

    $fieldenc=urlencode($field);
    $valueenc=urlencode($value);

    $catz=new mysql_catz();
    $categoryname=$catz->CategoryIntToStr($category);

    $array[$categoryname]="$page?template=yes&field=$fieldenc&value=$valueenc&category=$category&engine={$_GET["engine"]}";
    $array["{members}"]="$page?members=yes&field=$fieldenc&value=$valueenc&category=$category&engine={$_GET["engine"]}";
    $array["{websites}"]="$page?sites=yes&field=$fieldenc&value=$valueenc&category=$category&engine={$_GET["engine"]}";
    echo $tpl->tabs_default($array);


}

function sites_table(){
    $category=$_GET["category"];
    $q=new postgres_sql();
    $tpl=new template_admin();
    $DIFF_MIN=$_SESSION["STATSCOM_DAY"]["DIFF_MIN"];
    $time1=strtotime("{$_SESSION["STATSCOM_DAY"]["FROM"]} {$_SESSION["STATSCOM_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["STATSCOM_DAY"]["TO"]} {$_SESSION["STATSCOM_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);
    $field=$_GET["field"];
    $value=$_GET["value"];

    $fieldenc=urlencode($field);
    $valueenc=urlencode($value);

    $category=$_GET["category"];
    $catz=new mysql_catz();
    $categoryname=$catz->CategoryIntToStr($category);
    $field_table="{size}";


    $sql="SELECT SUM(statscom.size) as tsize,statscom_websites.familysite as familysite
    FROM statscom,statscom_websites  WHERE 
    statscom_websites.siteid=statscom.siteid AND
    statscom_websites.category=$category AND 
    statscom.size >100 AND zdate >'$strtime' and zdate < '$strtoTime' GROUP by familysite ORDER BY tsize";

    if($_GET["engine"]=="dnsfw"){
        $sql="SELECT SUM(statscom_dnsfw.hits) as tsize,statscom_websites.familysite as familysite
    FROM statscom_dnsfw,statscom_websites  WHERE 
    statscom_websites.siteid=statscom_dnsfw.siteid AND
    statscom_websites.category=$category AND 
    zdate >'$strtime' and zdate < '$strtoTime' GROUP by familysite ORDER BY tsize DESC";
        $field_table="{queries}";
    }


    $results=$q->QUERY_SQL($sql);

    if(!$q->ok){echo $tpl->div_error($q->mysql_error_html());}

    $t=time();
    $html[]="
    <H2>{familysite}: {category} <strong>$categoryname</strong> {from} ".$tpl->time_to_date($time1,true)." {to} ". $tpl->time_to_date($time2,true)."</H2>
<table id='table-$t' class=\"footable table table-stripped\" 
    data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{familysite}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$field_table</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $td1=$tpl->table_td1prc_Left();
    $TRCLASS=null;

    if(!$q->ok){echo $tpl->div_error($q->mysql_error_html());}

    while ($ligne = pg_fetch_assoc($results)) {
        if ($TRCLASS == "footable-odd ") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd ";
        }

        $familysite = $ligne["familysite"];
        $size = FormatBytes($ligne["tsize"] / 1024);

        if($_GET["engine"]=="dnsfw"){
            $size = $tpl->FormatNumber($ligne["tsize"]);

        }


        $html[] = "<tr style='vertical-align:middle' class='$TRCLASS'>";
        $html[] = "<td $td1><strong>$familysite</strong></td>";
        $html[] = "<td $td1 data-sort-value='{$ligne["tsize"]}'>$size</td>";
        $html[] = "</tr>";
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

function members_table(){
    $category=$_GET["category"];
    $q=new postgres_sql();
    $tpl=new template_admin();
    $hideMembers=0;
    $DIFF_MIN=$_SESSION["STATSCOM_DAY"]["DIFF_MIN"];
    $time1=strtotime("{$_SESSION["STATSCOM_DAY"]["FROM"]} {$_SESSION["STATSCOM_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["STATSCOM_DAY"]["TO"]} {$_SESSION["STATSCOM_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);
    $field=$_GET["field"];
    $value=$_GET["value"];
    $ColSpan=4;

    $fieldenc=urlencode($field);
    $valueenc=urlencode($value);
    $field_table="{size}";
    $category=$_GET["category"];
    $catz=new mysql_catz();
    $categoryname=$catz->CategoryIntToStr($category);
    $hideMacs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('statscomHideMacs'));
    $hideUnkownMembers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('statscomHideUnkownMembers'));
    $sql="SELECT SUM(statscom.size) as tsize,username,ipaddr,MAC FROM statscom,statscom_websites  WHERE 
    statscom_websites.siteid=statscom.siteid AND
    statscom_websites.category=$category AND 
    statscom.size >100 AND zdate >'$strtime' and zdate < '$strtoTime' GROUP by username,ipaddr,MAC ORDER BY tsize";

    if($_GET["engine"]=="dnsfw"){
        $sql="SELECT SUM(statscom_dnsfw.hits) as tsize,statscom_websites.familysite as familysite
    FROM statscom_dnsfw,statscom_websites  WHERE 
    statscom_websites.siteid=statscom_dnsfw.siteid AND
    statscom_websites.category=$category AND 
    statscom_dnsfw.hits >100 AND zdate >'$strtime' and zdate < '$strtoTime' GROUP by familysite ORDER BY tsize";
        $field_table="{queries}";
        $hideMacs=1;
        $hideMembers=1;
        $sql="SELECT SUM(statscom_dnsfw.hits) as tsize, ipaddr FROM statscom_dnsfw,statscom_websites  WHERE 
    statscom_websites.siteid=statscom_dnsfw.siteid AND
    statscom_websites.category=$category AND zdate >'$strtime' and zdate < '$strtoTime' GROUP by ipaddr ORDER BY tsize DESC";

    }


    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        VERBOSE($q->mysql_error,__LINE__);
        echo $tpl->div_error($q->mysql_error_html());}

    VERBOSE($sql." = ".count($results),__LINE__);



    $t=time();
    $html[]="
    <H2>{members}: {category} <strong>$categoryname</strong> {from} ".$tpl->time_to_date($time1,true)." {to} ". $tpl->time_to_date($time2,true)."</H2>
<table id='table-$t' class=\"footable table table-stripped\" 
    data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    if($hideMembers==0) {
        $ColSpan=$ColSpan-1;
        $html[] = "<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{member}</th>";
    }
    if($hideMacs==0) {
        $ColSpan=$ColSpan-1;
        $html[] = "<th data-sortable=true class='text-capitalize' data-type='text'>{MAC}</th>";
    }
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$field_table</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $td1=$tpl->table_td1prc_Left();
    $TRCLASS=null;
    while ($ligne = pg_fetch_assoc($results)) {
        if ($TRCLASS == "footable-odd ") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd ";
        }
        $username=null;
        $mac=null;
        $macenc=null;

        $ipaddr = $ligne["ipaddr"];
        if($hideMembers==0){$username = $ligne["username"];}
        if($hideMacs==0) {
            $mac = $ligne["mac"];
            $macenc = urlencode($mac);
            $mac = $tpl->td_href($mac, "{statistics}", "Loadjs('fw.statscom.browse.users.php?field=mac&value=$macenc')");
        }

        $size = FormatBytes($ligne["tsize"] / 1024);
        if($_GET["engine"]=="dnsfw"){
            $size=$tpl->FormatNumber($ligne["tsize"]);
        }

        $ipaddr = $tpl->td_href($ipaddr, "{statistics}", "Loadjs('fw.statscom.browse.users.php?field=ipaddr&value=$ipaddr')");
        if($hideMembers==0) {
            if ($username == null) {
                if ($hideUnkownMembers == 1) {
                    $username = $ipaddr;
                } else {
                    $username = "{unknown}";
                }

            } else {
                $username = $tpl->td_href($username, "{statistics}", "Loadjs('fw.statscom.browse.users.php?field=username&value=$username')");

            }
        }


        $html[] = "<tr style='vertical-align:middle' class='$TRCLASS'>";
        if($hideMembers==0) {
            $html[] = "<td $td1>$username</td>";
        }
        if($hideMacs==0) {
            $html[] = "<td $td1>$mac</td>";
        }
        $html[] = "<td $td1>$ipaddr</td>";
        $html[] = "<td $td1 data-sort-value='{$ligne["tsize"]}'>$size</td>";
        $html[] = "</tr>";
    }

        $html[]="</tbody>";
        $html[]="<tfoot>";

        $html[]="<tr>";
        $html[]="<td colspan='$ColSpan'>";
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


function template(){
    $field=$_GET["field"];
    $value=$_GET["value"];
    $fieldenc=urlencode($field);
    $valueenc=urlencode($value);
    $category=$_GET["category"];
    $imgwait="<img src='/img/Eclipse-0.9s-120px.gif'>";
    $t=time();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top'><div id='vertical-timeline-$t'>$imgwait</div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top'><div id='websites-graph-$t'>$imgwait</div></td>";
    $html[]="<td style='vertical-align:top'><div id='websites-table-$t'>$imgwait</div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td colspan=2><hr></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:744px'><div id='users-graph-$t' style='width:744px'>$imgwait</div></td>";
    $html[]="<td style='vertical-align:top;width:99%'><div id='users-table-$t'>$imgwait</div></td>";
    $html[]="</tr>";

    $html[]="</table>";
    $html[]="<script>";
    $html[]="Loadjs('$page?vertical-timeline=yes&t=$t&field=$fieldenc&value=$valueenc&category=$category&engine={$_GET["engine"]}');";
 //   $html[]="Loadjs('$page?top-sites=yes&id=top-sites-$t&suffix=$t');";
 //   $html[]="Loadjs('$page?top-users=yes&id=top-users-$t&suffix=$t');";
 //   $html[]="Loadjs('$page?top-categories=yes&id=top-categories-$t&suffix=$t');";
 //   $html[]="</script>";
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
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);
    $UNIT="{each_10minutes}";
    $SQL_DATE="zdate";
    $field=$_GET["field"];
    $value=$_GET["value"];

    $fieldenc=urlencode($field);
    $valueenc=urlencode($value);

    $category=$_GET["category"];
    $catz=new mysql_catz();
    $categoryname=$catz->CategoryIntToStr($category);

    if($DIFF_HOURS>6){
        $UNIT="{hourly}";
        $SQL_DATE="date_part('hour', zdate)";
    }

    if($DIFF_HOURS>48) {
        $UNIT = "{daily}";
        $SQL_DATE = "date_part('day', zdate)";
    }
    $AND=null;
    if($field<>null){$AND=" AND $field='$value'";}

    $sql="SELECT SUM(statscom.size) as size,$SQL_DATE as zdate FROM statscom,statscom_websites WHERE 
    statscom_websites.siteid=statscom.siteid
    AND statscom_websites.category=$category{$AND}
    AND zdate >'$strtime' and zdate < '$strtoTime' GROUP by $SQL_DATE ORDER BY $SQL_DATE";

    if($_GET["engine"]=="dnsfw"){
        $sql="SELECT SUM(statscom_dnsfw.hits) as size,$SQL_DATE as zdate FROM statscom_dnsfw,statscom_websites WHERE 
    statscom_websites.siteid=statscom_dnsfw.siteid
    AND statscom_websites.category=$category{$AND}
    AND zdate >'$strtime' and zdate < '$strtoTime' GROUP by $SQL_DATE ORDER BY $SQL_DATE";

    }


    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);

        if($_GET["engine"]=="dnsfw"){
            $size = $ligne["size"];

        }

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
    $title="{downloaded_flow} (MB) $categoryname $field $value $stimeFrom - $stimeTo {$UNIT}";
    if($_GET["engine"]=="dnsfw"){
        $title="{dns_queries} $categoryname $field $value $stimeFrom - $stimeTo {$UNIT}";
    }

    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->container="vertical-timeline-$t";
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
    $page=CurrentPageName();
    echo "Loadjs('$page?top-sites=yes&t=$t&field=$fieldenc&value=$valueenc&category=$category&engine={$_GET["engine"]}');\n";
    echo "Loadjs('$page?top-users=yes&t=$t&field=$fieldenc&value=$valueenc&category=$category&engine={$_GET["engine"]}');\n";
}

function top_sites(){
    $page=CurrentPageName();
    $t=$_GET["t"];
    $tpl=new template_admin();
    $q=new postgres_sql();
    $field=$_GET["field"];
    $value=$_GET["value"];
    $fieldenc=urlencode($field);
    $valueenc=urlencode($value);
    $DIFF_MIN=$_SESSION["STATSCOM_DAY"]["DIFF_MIN"];
    $DIFF_HOURS=$DIFF_MIN/60;
    echo "// DIFF: $DIFF_MIN hours";
    $time1=strtotime("{$_SESSION["STATSCOM_DAY"]["FROM"]} {$_SESSION["STATSCOM_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["STATSCOM_DAY"]["TO"]} {$_SESSION["STATSCOM_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);

    $category=$_GET["category"];
    $catz=new mysql_catz();
    $categoryname=$catz->CategoryIntToStr($category);
    $AND=null;
    if($field<>null){
        $AND=" AND statscom.$field='$value'";
    }

    $sql="SELECT SUM(statscom.size) as size,statscom_websites.familysite FROM statscom,statscom_websites WHERE statscom_websites.siteid=statscom.siteid AND statscom_websites.category=$category{$AND} AND zdate >'$strtime' and zdate < '$strtoTime' GROUP by familysite ORDER BY size DESC LIMIT 10";

    if($_GET["engine"]=="dnsfw"){
            $sql="SELECT SUM(statscom_dnsfw.hits) as size,statscom_websites.familysite FROM statscom_dnsfw,statscom_websites WHERE statscom_websites.siteid=statscom_dnsfw.siteid AND statscom_websites.category=$category{$AND} AND zdate >'$strtime' and zdate < '$strtoTime' GROUP by familysite ORDER BY size DESC LIMIT 10";
    }

    $q->QUERY_SQL($sql);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);
        if($_GET["engine"]=="dnsfw"){
            $size = $ligne["size"];
        }


        $PieData[$ligne["familysite"]]=$size;
        $PieData2[$ligne["familysite"]]=$ligne["size"];
    }

    $encoded=base64_encode(serialize($PieData2));

    $highcharts=new highcharts();
    $highcharts->container="websites-graph-$t";
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "$categoryname $field $value {top_websites_by_size} (MB)";
    if($_GET["engine"]=="dnsfw"){
        $highcharts->PiePlotTitle = "$categoryname $field $value {top_websites_by_hits}";
    }

    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();

    echo "LoadAjax('websites-table-$t','$page?sites-table=$encoded&engine={$_GET["engine"]}');\n";


}


function top_users(){

    $page=CurrentPageName();
    $t=$_GET["t"];
    $tpl=new template_admin();
    $q=new postgres_sql();
    $field=$_GET["field"];
    $value=$_GET["value"];
    $fieldenc=urlencode($field);
    $valueenc=urlencode($value);
    $category=$_GET["category"];
    $DIFF_MIN=$_SESSION["STATSCOM_DAY"]["DIFF_MIN"];
    $DIFF_HOURS=$DIFF_MIN/60;
    echo "// DIFF: $DIFF_MIN hours";
    $time1=strtotime("{$_SESSION["STATSCOM_DAY"]["FROM"]} {$_SESSION["STATSCOM_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["STATSCOM_DAY"]["TO"]} {$_SESSION["STATSCOM_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);

    $AND=null;
    if($field<>null){
        $AND=" AND statscom.$field='$value'";
    }

    $sql="SELECT SUM(statscom.size) as size,username,ipaddr,MAC FROM statscom,statscom_websites  WHERE 
    statscom_websites.siteid=statscom.siteid AND
    statscom_websites.category=$category{$AND} AND 
    zdate >'$strtime' and zdate < '$strtoTime' GROUP by username,ipaddr,MAC ORDER BY size DESC LIMIT 15";

    if($_GET["engine"]=="dnsfw"){
        $sql="SELECT SUM(statscom_dnsfw.hits) as size,ipaddr FROM statscom_dnsfw,statscom_websites  WHERE 
    statscom_websites.siteid=statscom_dnsfw.siteid AND
    statscom_websites.category=$category{$AND} AND 
    zdate >'$strtime' and zdate < '$strtoTime' GROUP by ipaddr ORDER BY size DESC LIMIT 15";
    }



    $q->QUERY_SQL($sql);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);
        if($_GET["engine"]=="dnsfw"){
            $size = $ligne["size"];
        }
        $username=$ligne["username"];
        $ipaddr=$ligne["ipaddr"];
        $MAC=$ligne["MAC"];
        $issu=array();
        $issu[]=$username;
        $issu[]=$MAC;
        $issu[]=$ipaddr;
        $PieData2[@implode("|",$issu)]=$ligne["size"];
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
    $highcharts->PiePlotTitle = "{top_members} (MB)";
    if($_GET["engine"]=="dnsfw"){
        $highcharts->PiePlotTitle = "{top_members}";
    }
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

        if($sizeM>1024){
            $sizeM2=FormatBytes($sizeM/1024);
        }else{
            $sizeM2="{$sizeM} Bytes";
        }

        if($_GET["engine"]=="dnsfw"){
            $sizeM2=$tpl->FormatNumber($sizeM);
        }


        $html[]="<tr>";
        $html[]="<td><strong>".@implode("&nbsp;|&nbsp;",$t)."</strong></td>";
        $html[]="<td>$sizeM2</td>";
        $html[]="</tr>";

    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}

function top_sites_table(){
    $fname="{size}";
    if($_GET["engine"]=="dnsfw"){
        $fname="{queries}";
    }
    $tpl=new template_admin();
    $html[]="<table style='width:100%' class='table'>";
    $html[]="<tr>";
    $html[]="<th>{domains}</th>";
    $html[]="<th>$fname</th>";
    $html[]="</tr>";
    $table=unserialize(base64_decode($_GET["sites-table"]));
    foreach ( $table as $site=>$sizeM) {

        if($sizeM>1024){
            $sizeM2=FormatBytes($sizeM/1024);
        }else{
            $sizeM2="{$sizeM} Bytes";
        }
        if($_GET["engine"]=="dnsfw"){
            $sizeM2=$tpl->FormatNumber($sizeM);
        }

        $html[]="<tr>";
        $html[]="<td><strong>$site</strong></td>";
        $html[]="<td>$sizeM2</td>";
        $html[]="</tr>";

    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}