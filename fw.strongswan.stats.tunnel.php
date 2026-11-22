<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// Include INCONDITIONNEL de premier niveau (CLAUDE.md, awc-* regle 1):
// glisse dans le bootstrap if(!class_exists("sockets")) il ne s'executerait
// jamais et la page mourrait sur awc_table_style() indefinie. // Service Pack 9
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["template"])){template();exit;}
if(isset($_GET["vertical-timeline"])){vertical_timeline();exit;}
if(isset($_GET["top-vips"])){top_vips();exit;}
if(isset($_GET["top-users"])){top_users();exit;}
if(isset($_GET["vips-table"])){top_vips_table();exit;}
if(isset($_GET["users-table"])){top_users_table();exit;}
if(isset($_GET["members"])){members_table();exit;}
if(isset($_GET["remote-hosts-table"])){top_remote_hosts_table();exit;}
if(isset($_GET["top-remote-hosts"])){top_remote_hosts();exit;}
js();


function js(){
    $tpl=new template_admin();
    $field=$_GET["field"];
    $value=$_GET["value"];

    $fieldenc=urlencode($field);
    $valueenc=urlencode($value);
    $tunnel=$_GET["tunnel"];

    $page=CurrentPageName();
    $tpl->js_dialog3("$field/$value/{tunnel}: $tunnel","$page?tabs=yes&field=$fieldenc&value=$valueenc&tunnel=$tunnel",1040);


}

function tabs(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $field=$_GET["field"];
    $value=$_GET["value"];
    $tunnel=$_GET["tunnel"];

    $fieldenc=urlencode($field);
    $valueenc=urlencode($value);



    $array[$tunnel]="$page?template=yes&field=$fieldenc&value=$valueenc&tunnel=$tunnel";
    $array["{members}"]="$page?members=yes&field=$fieldenc&value=$valueenc&tunnel=$tunnel";
    echo $tpl->tabs_default($array);


}



function members_table(){
    $tunnel=$_GET["tunnel"];
    $q=new postgres_sql();
    $tpl=new template_admin();
    $DIFF_MIN=$_SESSION["IPPSEC_DAY"]["DIFF_MIN"];
    $time1=strtotime("{$_SESSION["IPPSEC_DAY"]["FROM"]} {$_SESSION["IPPSEC_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["IPPSEC_DAY"]["TO"]} {$_SESSION["IPPSEC_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);
    $field=$_GET["field"];
    $value=$_GET["value"];

    $fieldenc=urlencode($field);
    $valueenc=urlencode($value);

    $tunnel=$_GET["tunnel"];


//    $sql="SELECT SUM(bytes_in) as bytes_in, SUM(bytes_out) as bytes_out, SUM(packets_in) as packets_in, SUM(packets_out) as packets_out, username,local_vip,remote_host, date_trunc('hour',zdate) as zdate FROM strongswan_stats  WHERE
//    conn_name='$tunnel' AND zdate > '$strtime' and zdate < '$strtoTime' GROUP by date_trunc('hour',zdate),username,local_vip,remote_host  ORDER BY zdate";
    // Pentest 2026-11-04 : tunnel (GET) etait concatene dans la requete.
    $sql="SELECT * FROM strongswan_stats  WHERE 
    conn_name='".pg_escape_string2($tunnel)."' AND zdate > '$strtime' and zdate < '$strtoTime' ORDER BY zdate";

    $results=$q->QUERY_SQL($sql);

    if(!$q->ok){echo $tpl->div_error($q->mysql_error_html());}

    $t=time();
    $html[]="<H2>{members}: {tunnel} <strong>".htmlspecialchars($tunnel)."</strong> {from} "
        .$tpl->time_to_date($time1,true)." {to} ".$tpl->time_to_date($time2,true)."</H2>";

    $rows=array();
    while($ligne=pg_fetch_assoc($results)){
        $vips=(string)$ligne["local_vip"];
        $username=(string)$ligne["username"];
        $remote_host=(string)$ligne["remote_host"];
        $zdate=(string)$ligne["zdate"];
        $bytes_in=FormatBytes($ligne["bytes_in"]/1024);
        $bytes_out=FormatBytes($ligne["bytes_out"]/1024);

        // urlencode sur les valeurs qui partent dans une URL de Loadjs: une
        // adresse ou un nom d'utilisateur portant un & ou un espace tronquait
        // silencieusement le parametre suivant.
        $lnkHost=$tpl->td_href($remote_host,"{statistics}",
            "Loadjs('fw.strongswan.stats.members.php?field=remote_host&value=".urlencode($remote_host)."')");
        $lnkVips=$tpl->td_href($vips,"{statistics}",
            "Loadjs('fw.strongswan.stats.members.php?field=local_vip&value=".urlencode($vips)."')");
        $lnkUser=$tpl->td_href($username,"{statistics}",
            "Loadjs('fw.strongswan.stats.members.php?field=username&value=".urlencode($username)."')");

        $named=(trim($username)!=="");
        $h ="<tr>";
        $h.="<td><div class='awc-ident'>";
        $h.="<span class='awc-ico".($named?"":" awc-ico-off")."'><i class='fas fa-user'></i></span>";
        $h.="<span><span class='awc-name".($named?"":" awc-name-off")."'>".($named?$lnkUser:"{unknown}")."</span>";
        $h.="<div class='awc-meta'>".htmlspecialchars($zdate)."</div></span></div></td>";
        $h.="<td class='awc-mono'>$lnkVips</td>";
        $h.="<td class='awc-mono'>$lnkHost</td>";
        $h.="<td class='center awc-mono'>$bytes_in</td>";
        $h.="<td class='center awc-mono'>$bytes_out</td>";
        $h.="<td class='center awc-mono'>".intval($ligne["packets_in"])."</td>";
        $h.="<td class='center awc-mono'>".intval($ligne["packets_out"])."</td>";
        $h.="</tr>";
        $rows[]=$h;
    }

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $html[]="<th>{member}</th>";
    $html[]="<th nowrap>{vips}</th>";
    $html[]="<th nowrap>{remote_host}</th>";
    $html[]="<th class='center' nowrap>{mb_in}</th>";
    $html[]="<th class='center' nowrap>{mb_out}</th>";
    $html[]="<th class='center' nowrap>{packets_in}</th>";
    $html[]="<th class='center' nowrap>{packets_out}</th>";
    $html[]="</tr></thead><tbody id='sswst-rows'>";
    if(count($rows)==0){
        $html[]="<tr><td colspan='7'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} ".count($rows)." {items}</span>"
           ."<span class='awc-pg' id='sswst-pager'></span></div>";
    $html[]="</div></div>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>"
        .awc_pager_js(50,"sswst-rows","sswst-pager");
}


function template(){
    $field=$_GET["field"];
    $value=$_GET["value"];
    $fieldenc=urlencode($field);
    $valueenc=urlencode($value);
    $tunnel=$_GET["tunnel"];
    $imgwait="<img src='/img/Eclipse-0.9s-120px.gif'>";
    $t=time();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top'><div id='vertical-timeline-bytes-in-out-$t'>$imgwait</div></td>";
    $html[]="</tr>";
    $html[]="<tr style='width:100%'>";
    $html[]="<td style='vertical-align:top'><div id='vertical-timeline-packets-in-out-$t'>$imgwait</div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top'><div id='vips-graph-$t'>$imgwait</div></td>";
    $html[]="<td style='vertical-align:top'><div id='vips-table-$t'>$imgwait</div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td colspan=2><hr></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:744px'><div id='users-graph-$t' style='width:744px'>$imgwait</div></td>";
    $html[]="<td style='vertical-align:top;width:99%'><div id='users-table-$t'>$imgwait</div></td>";
    $html[]="</tr>";
    $html[]="<td colspan=2><hr></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:744px'><div id='remote-hosts-graph-$t' style='width:744px'>$imgwait</div></td>";
    $html[]="<td style='vertical-align:top;width:99%'><div id='remote-hosts-table-$t'>$imgwait</div></td>";
    $html[]="</tr>";

    $html[]="</table>";
    $html[]="<script>";
    $html[]="Loadjs('$page?vertical-timeline=yes&t=$t&field=$fieldenc&value=$valueenc&tunnel=$tunnel');";
    //   $html[]="Loadjs('$page?top-vips=yes&id=top-vips-$t&suffix=$t');";
    //   $html[]="Loadjs('$page?top-users=yes&id=top-users-$t&suffix=$t');";
    //   $html[]="Loadjs('$page?top-categories=yes&id=top-categories-$t&suffix=$t');";
    //   $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function vertical_timeline(){
    $t=$_GET["t"];
    $tpl=new template_admin();
    $q=new postgres_sql();
    $DIFF_MIN=$_SESSION["IPPSEC_DAY"]["DIFF_MIN"];
    $DIFF_HOURS=$DIFF_MIN/60;
    echo "// DIFF: $DIFF_MIN hours";
    $time1=strtotime("{$_SESSION["IPPSEC_DAY"]["FROM"]} {$_SESSION["IPPSEC_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["IPPSEC_DAY"]["TO"]} {$_SESSION["IPPSEC_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);
    $UNIT="{each_10minutes}";
    $SQL_DATE="zdate";
    $field=$_GET["field"];
    $value=$_GET["value"];

    $fieldenc=urlencode($field);
    $valueenc=urlencode($value);

    $tunnel=$_GET["tunnel"];


    if($DIFF_HOURS>6){
        $UNIT="{hourly}";
        $SQL_DATE="date_part('hour', zdate)";
    }

    if($DIFF_HOURS>24) {
        $UNIT = "{daily}";
        $SQL_DATE = "date_part('day', zdate)";
    }
    $AND=null;
    if($field<>null){$AND=" AND $field='$value'";}

    //BYTES IN / OUT
//    $sql = "SELECT SUM(bytes_out) as bytes_out, SUM(bytes_in) as bytes_in, $SQL_DATE as zdate FROM strongswan_stats WHERE conn_name='$tunnel' AND zdate >'$strtime' and zdate < '$strtoTime' GROUP by $SQL_DATE ORDER BY $SQL_DATE";

    // Pentest 2026-11-04 : tunnel (GET) etait concatene dans la requete.
    $sql="select sum(bytes_in) as bytes_in, sum(bytes_out) as bytes_out, $SQL_DATE as zdate from (SELECT distinct on (spi_in) spi_in, MAX(bytes_in) as bytes_in, MAX(bytes_out) as bytes_out, zdate,conn_name FROM  strongswan_stats where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY spi_in, zdate,conn_name ORDER BY spi_in, zdate,conn_name) x where conn_name='".pg_escape_string2($tunnel)."' AND zdate >'$strtime' and zdate < '$strtoTime' GROUP BY $SQL_DATE ORDER BY $SQL_DATE";

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
    while ($ligne = pg_fetch_assoc($results)) {
        $bytes_in = $ligne["bytes_in"];
        $bytes_in = $bytes_in / 1024;
        $bytes_in = $bytes_in / 1024;
        $bytes_in = round($bytes_in);

        $bytes_out = $ligne["bytes_out"];
        $bytes_out = $bytes_out / 1024;
        $bytes_out = $bytes_out / 1024;
        $bytes_out = round($bytes_out);
        echo "// {$ligne["zdate"]}\n";
        $stime=strtotime($ligne["zdate"]);
        $datetext=date("H:i",$stime);
        if($DIFF_HOURS>6){
            $datetext="{$ligne["zdate"]}h";
        }
        if($DIFF_HOURS>24){
            $datetext=$tpl->_ENGINE_parse_body("{day}")." {$ligne["zdate"]}";
        }
        $xdata[]=$datetext;
        $mb_in[]=$bytes_in;
        $mb_out[]=$bytes_out;
    }


    $stimeFrom=$tpl->time_to_date($time1);
    $stimeTo=$tpl->time_to_date($time2);
    $title="{APP_STRONGSWAN_TRAFFIC_FLOW} (MB) - $tunnel $field $value $stimeFrom - $stimeTo {$UNIT}";
    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->container="vertical-timeline-bytes-in-out-$t";
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle=" MB";
    $highcharts->xAxis_labels=true;
    $highcharts->LegendSuffix=" MB";
    $highcharts->ChartType = "line";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("In "=>$mb_in, "Out "=>$mb_out);
    echo $highcharts->BuildChart();
    //PACKETS IN / OUT
//    $sql = "SELECT SUM(packets_in) as packets_in, SUM(packets_out) as packets_out, $SQL_DATE as zdate FROM strongswan_stats WHERE conn_name='$tunnel' AND zdate >'$strtime' and zdate < '$strtoTime' GROUP by $SQL_DATE ORDER BY $SQL_DATE";
    // Pentest 2026-11-04 : tunnel (GET) etait concatene dans la requete.
    $sql="select SUM(packets_in) as packets_in, SUM(packets_out) as packets_out, $SQL_DATE as zdate from (SELECT distinct on (spi_in) spi_in, MAX(packets_in) as packets_in, MAX(packets_out) as packets_out, zdate,conn_name FROM  strongswan_stats where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY spi_in, zdate,conn_name ORDER BY spi_in, zdate,conn_name) x where conn_name='".pg_escape_string2($tunnel)."' AND zdate >'$strtime' and zdate < '$strtoTime' GROUP BY $SQL_DATE ORDER BY $SQL_DATE";

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
    while ($ligne = pg_fetch_assoc($results)) {
        $packets_in = $ligne["packets_in"];
        $packets_out = $ligne["packets_out"];
        echo "// {$ligne["zdate"]}\n";
        $stime=strtotime($ligne["zdate"]);
        $datetext=date("H:i",$stime);
        if($DIFF_HOURS>6){
            $datetext="{$ligne["zdate"]}h";
        }
        if($DIFF_HOURS>24){
            $datetext=$tpl->_ENGINE_parse_body("{day}")." {$ligne["zdate"]}";
        }
        $xdata[]=$datetext;
        $packets_in_t[]=$packets_in;
        $packets_out_t[]=$packets_out;
    }


    $stimeFrom=$tpl->time_to_date($time1);
    $stimeTo=$tpl->time_to_date($time2);
    $title="{APP_STRONGSWAN_PACKETS_FLOW} - $tunnel $field $value $stimeFrom - $stimeTo {$UNIT}";
    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->container="vertical-timeline-packets-in-out-$t";
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle=" Packets";
    $highcharts->ChartType = "line";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix=" Packets";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("In "=>$packets_in_t,"Out "=>$packets_out_t);
    echo $highcharts->BuildChart();

    $page=CurrentPageName();
    echo "Loadjs('$page?top-vips=yes&t=$t&field=$fieldenc&value=$valueenc&tunnel=$tunnel');\n";
    echo "Loadjs('$page?top-users=yes&t=$t&field=$fieldenc&value=$valueenc&tunnel=$tunnel');\n";
    echo "Loadjs('$page?top-remote-hosts=yes&t=$t&field=$fieldenc&value=$valueenc&tunnel=$tunnel');\n";
}

function top_vips(){
    $page=CurrentPageName();
    $t=$_GET["t"];
    $tpl=new template_admin();
    $q=new postgres_sql();
    $field=$_GET["field"];
    $value=$_GET["value"];
    $fieldenc=urlencode($field);
    $valueenc=urlencode($value);
    $DIFF_MIN=$_SESSION["IPPSEC_DAY"]["DIFF_MIN"];
    $DIFF_HOURS=$DIFF_MIN/60;
    echo "// DIFF: $DIFF_MIN hours";
    $time1=strtotime("{$_SESSION["IPPSEC_DAY"]["FROM"]} {$_SESSION["IPPSEC_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["IPPSEC_DAY"]["TO"]} {$_SESSION["IPPSEC_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);

    $tunnel=$_GET["tunnel"];

    $AND=null;
    if($field<>null){
        $AND=" AND statscom.$field='$value'";
    }
    

//    $sql="SELECT SUM(bytes_in + bytes_out) as size,local_vip FROM strongswan_stats  WHERE
//    conn_name='$tunnel' AND
//    zdate >'$strtime' and zdate < '$strtoTime' GROUP by local_vip ORDER BY size DESC LIMIT 15";
    // Pentest 2026-11-04 : tunnel (GET) etait concatene dans la requete.
    $sql="select SUM(bytes_in + bytes_out) as size,local_vip from (SELECT distinct on (spi_in) spi_in, MAX(bytes_out) as bytes_out, MAX(bytes_in) as bytes_in, local_vip,zdate,conn_name FROM  strongswan_stats where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY spi_in, local_vip,zdate,conn_name ORDER BY spi_in, local_vip) x where  conn_name='".pg_escape_string2($tunnel)."' AND zdate >'$strtime' and zdate < '$strtoTime' GROUP BY local_vip ORDER BY size DESC LIMIT 15 ";

    $q->QUERY_SQL($sql);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);
        $PieData[$ligne["local_vip"]]=$size;
        $PieData2[$ligne["local_vip"]]=$size;
    }

    $encoded=base64_encode(serialize($PieData2));

    $highcharts=new highcharts();
    $highcharts->container="vips-graph-$t";
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "(MB)";
    $highcharts->TitleFontSize = "14px";

    $highcharts->Title="{vips_top} - $tunnel";
    echo $highcharts->BuildChart();

    echo "LoadAjax('vips-table-$t','$page?vips-table=$encoded');\n";


}

function top_remote_hosts(){
    $page=CurrentPageName();
    $t=$_GET["t"];
    $tpl=new template_admin();
    $q=new postgres_sql();
    $field=$_GET["field"];
    $value=$_GET["value"];
    $fieldenc=urlencode($field);
    $valueenc=urlencode($value);
    $DIFF_MIN=$_SESSION["IPPSEC_DAY"]["DIFF_MIN"];
    $DIFF_HOURS=$DIFF_MIN/60;
    echo "// DIFF: $DIFF_MIN hours";
    $time1=strtotime("{$_SESSION["IPPSEC_DAY"]["FROM"]} {$_SESSION["IPPSEC_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["IPPSEC_DAY"]["TO"]} {$_SESSION["IPPSEC_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);

    $tunnel=$_GET["tunnel"];

    $AND=null;
    if($field<>null){
        $AND=" AND statscom.$field='$value'";
    }


//    $sql="SELECT SUM(bytes_in + bytes_out) as size,remote_host FROM strongswan_stats  WHERE
//    conn_name='$tunnel' AND
//    zdate >'$strtime' and zdate < '$strtoTime' GROUP by remote_host ORDER BY size DESC LIMIT 15";
    // Pentest 2026-11-04 : tunnel (GET) etait concatene dans la requete.
    $sql="select SUM(bytes_in + bytes_out) as size,remote_host from (SELECT distinct on (spi_in) spi_in, MAX(bytes_out) as bytes_out, MAX(bytes_in) as bytes_in, remote_host,zdate,conn_name FROM  strongswan_stats where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY spi_in, remote_host,zdate,conn_name ORDER BY spi_in, remote_host) x where  conn_name='".pg_escape_string2($tunnel)."' AND zdate >'$strtime' and zdate < '$strtoTime' GROUP BY remote_host ORDER BY size DESC LIMIT 15 ";
    $q->QUERY_SQL($sql);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);
        $PieData[$ligne["remote_host"]]=$size;
        $PieData2[$ligne["remote_host"]]=$size;
    }

    $encoded=base64_encode(serialize($PieData2));

    $highcharts=new highcharts();
    $highcharts->container="remote-hosts-graph-$t";
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "(MB)";
    $highcharts->TitleFontSize = "14px";

    $highcharts->Title="{remote_hosts_top} - $tunnel";
    echo $highcharts->BuildChart();

    echo "LoadAjax('remote-hosts-table-$t','$page?remote-hosts-table=$encoded');\n";


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
    $tunnel=$_GET["tunnel"];
    $DIFF_MIN=$_SESSION["IPPSEC_DAY"]["DIFF_MIN"];
    $DIFF_HOURS=$DIFF_MIN/60;
    echo "// DIFF: $DIFF_MIN hours";
    $time1=strtotime("{$_SESSION["IPPSEC_DAY"]["FROM"]} {$_SESSION["IPPSEC_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["IPPSEC_DAY"]["TO"]} {$_SESSION["IPPSEC_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);

    $AND=null;
    if($field<>null){
        $AND=" AND statscom.$field='$value'";
    }

//    $sql="SELECT SUM(bytes_in + bytes_out) as size,username FROM strongswan_stats  WHERE
//    conn_name='$tunnel' AND
//    zdate >'$strtime' and zdate < '$strtoTime' GROUP by username ORDER BY size DESC LIMIT 15";
    // Pentest 2026-11-04 : tunnel (GET) etait concatene dans la requete.
    $sql="select SUM(bytes_in + bytes_out) as size,username from (SELECT distinct on (spi_in) spi_in, MAX(bytes_out) as bytes_out, MAX(bytes_in) as bytes_in, username,zdate,conn_name FROM  strongswan_stats where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY spi_in, username,zdate,conn_name ORDER BY spi_in, username) x where  conn_name='".pg_escape_string2($tunnel)."' AND zdate >'$strtime' and zdate < '$strtoTime' GROUP BY username ORDER BY size DESC LIMIT 15 ";

    $q->QUERY_SQL($sql);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);
        $username=$ligne["username"];
        $issu=array();
        $issu[]=$username;
   
        $PieData2[@implode("|",$issu)]=$size;
        if($username<>null){
            $PieData[$username]=$size;
            continue;
        }
        
    }

    $encoded=base64_encode(serialize($PieData2));

    $highcharts=new highcharts();
    $highcharts->container="users-graph-$t";
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "$field(MB)";
    $highcharts->TitleFontSize = "14px";

    $highcharts->Title="{username_top} - $tunnel";
    echo $highcharts->BuildChart();

    echo "LoadAjax('users-table-$t','$page?users-table=$encoded');";


}

function top_users_table(){

    $tpl=new template_admin();
    $html[]="<table style='width:100%' class='table'>";
    $html[]="<tr>";
    $html[]="<th>{username}</th>";
    $html[]="<th>{size}</th>";
    $html[]="</tr>";
    $table=unserialize(base64_decode($_GET["users-table"]),array("allowed_classes"=>false));
    foreach ( $table as $site=>$sizeM) {
        $h=explode("|",$site);
        $t=array();
        if($h[0]<>null){
            $t[]=$tpl->td_href($h[0],null,"Loadjs('fw.strongswan.stats.members.php?field=username&value=".urlencode($h[0])."')");
        }


        $sizeM=FormatBytes($sizeM*1024);

        $html[]="<tr>";
        $html[]="<td><strong>".@implode("&nbsp;|&nbsp;",$t)."</strong></td>";
        $html[]="<td>$sizeM</td>";
        $html[]="</tr>";

    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}

function top_vips_table(){

    $tpl=new template_admin();
    $html[]="<table style='width:100%' class='table'>";
    $html[]="<tr>";
    $html[]="<th>{vips}</th>";
    $html[]="<th>{size}</th>";
    $html[]="</tr>";
    $table=unserialize(base64_decode($_GET["vips-table"]),array("allowed_classes"=>false));
    foreach ( $table as $site=>$sizeM) {

        $sizeM=FormatBytes($sizeM*1024);

        $link=$tpl->td_href($site,null,"Loadjs('fw.strongswan.stats.members.php?field=local_vip&value=".urlencode($site)."')");


        $html[]="<tr>";
        $html[]="<td><strong>$link</strong></td>";
        $html[]="<td>$sizeM</td>";
        $html[]="</tr>";

    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}

function top_remote_hosts_table(){

    $tpl=new template_admin();
    $html[]="<table style='width:100%' class='table'>";
    $html[]="<tr>";
    $html[]="<th>{remote_hosts}</th>";
    $html[]="<th>{size}</th>";
    $html[]="</tr>";
    $table=unserialize(base64_decode($_GET["remote-hosts-table"]),array("allowed_classes"=>false));
    foreach ( $table as $site=>$sizeM) {

        $sizeM=FormatBytes($sizeM*1024);

        $link=$tpl->td_href($site,null,"Loadjs('fw.strongswan.stats.members.php?field=remote_host&value=".urlencode($site)."')");

        $html[]="<tr>";
        $html[]="<td><strong>$link</strong></td>";
        $html[]="<td>$sizeM</td>";
        $html[]="</tr>";

    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}