<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["query"])){query();exit;}
if(isset($_GET["query-js"])){query_js();exit;}
if(isset($_GET["query-popup"])){query_popup();exit;}
if(isset($_POST["FROM"])){query_save();exit;}
if(isset($_GET["vertical-timeline"])){echo vertical_time_line();exit;}
if(isset($_GET["top-tunnels"])){echo top_tunnels();exit;}
if(isset($_GET["tunnels-table"])){tunnels_table();exit;}
if(isset($_GET["top-members"])){echo top_members();exit;}
if(isset($_GET["members-table"])){echo members_table();exit;}
if(isset($_GET["top-vips"])){echo top_vips();exit;}
if(isset($_GET["vips-table"])){echo vips_table();exit;}
if(isset($_GET["top-remote-hosts"])){echo top_remote_hosts();exit;}
if(isset($_GET["remote-hosts-table"])){echo remote_hosts_table();exit;}
if(isset($_GET["template"])){template();exit;}



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
    if (!$q->TABLE_EXISTS('strongswan_stats')) {

        $sql = "CREATE TABLE  IF NOT EXISTS `strongswan_stats` (
				`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`zate` INTEGER,
				`spi` varchar(60),
				`conn_name` varchar(128) NOT NULL,
                `username` varchar(128) NOT NULL,
				`remote_host` TEXT,
                `local_vip` TEXT,
                `time` INTEGER ,
				`bytes_in` INTEGER,
				`bytes_out` INTEGER,
    			`packets_in` INTEGER,
				`packets_out` INTEGER
		) ";
        $q->QUERY_SQL($sql);
    }
    $results=$q->QUERY_SQL("SELECT date_trunc('day',zdate)::timestamp::date as zdate FROM strongswan_stats GROUP BY 1 ORDER BY 1 DESC; ");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}

    //$DATES[date("Y-m-d")]=$tpl->time_to_date(time());
    //$DATES[date("Y-m-d",strtotime( '-1 days' ))]=$tpl->time_to_date(strtotime( '-1 days' ));

    while ($ligne = pg_fetch_assoc($results)) {
        $time=strtotime($ligne["zdate"]);
        $DATES[$ligne["zdate"]]=$tpl->time_to_date($time);
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
    $form[]=$tpl->field_array_hash($DATES,"FROM","nonull:{from_date}",$_SESSION["IPPSEC_DAY"]["FROM"]);

    $tb=explode(":",$_SESSION["IPPSEC_DAY"]["FROMH"]);
    $form[]=$tpl->field_array_hash($TIMES,"FROM_HOUR","nonull:{from_time}",$tb[0]);
    $form[]=$tpl->field_array_hash($MINS,"FROM_MIN","nonull:{minutes}",$tb[1]);

    $form[]=$tpl->field_section("{to_date}");
    $tb=explode(":",$_SESSION["IPPSEC_DAY"]["TOH"]);
    $form[]=$tpl->field_array_hash($DATES,"TO","nonull:{to_date}",$_SESSION["IPPSEC_DAY"]["TO"]);
    $form[]=$tpl->field_array_hash($TIMES,"TO_HOUR","nonull:{from_time}",$tb[0]);
    $form[]=$tpl->field_array_hash($MINS,"TO_MIN","nonull:{minutes}",$tb[1]);


    echo $tpl->form_outside("{build_query}",$form,null,"{launch}","LoadAjax('ipsec-data','$page?template=yes');","AsWebStatisticsAdministrator",true);

}

function BUILD_DEFAULT_DATA(){

    if(!isset($_SESSION["IPPSEC_DAY"]["FROM"])){
        $_SESSION["IPPSEC_DAY"]["FROM"]=date("Y-m-d");
    }
    if(!isset($_SESSION["IPPSEC_DAY"]["TO"])){
        $_SESSION["IPPSEC_DAY"]["TO"]=date("Y-m-d");
    }

    if(!isset($_SESSION["IPPSEC_DAY"]["FROMH"])){
        $_SESSION["IPPSEC_DAY"]["FROMH"]="00:00";
    }
    if(!isset($_SESSION["IPPSEC_DAY"]["TOH"])){
        $_SESSION["IPPSEC_DAY"]["TOH"]="23:59";
    }
    if(!isset($_SESSION["IPPSEC_DAY"]["LIMIT"])){
        $_SESSION["IPPSEC_DAY"]["LIMIT"]="250";
    }
    if(intval($_SESSION["IPPSEC_DAY"]["LIMIT"])==0){
        $_SESSION["IPPSEC_DAY"]["LIMIT"]=250;
    }

    $time1=strtotime("{$_SESSION["IPPSEC_DAY"]["FROM"]} {$_SESSION["IPPSEC_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["IPPSEC_DAY"]["TO"]} {$_SESSION["IPPSEC_DAY"]["TOH"]}:00");
    $_SESSION["IPPSEC_DAY"]["DIFF_MIN"]=round(($time2-$time1)/60);

}

function query_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $_SESSION["IPPSEC_DAY"]["FROM"]=$_POST["FROM"];
    $_SESSION["IPPSEC_DAY"]["TO"]=$_POST["TO"];
    $_SESSION["IPPSEC_DAY"]["FROMH"]=$_POST["FROM_HOUR"].":".$_POST["FROM_MIN"];
    $_SESSION["IPPSEC_DAY"]["TOH"]=$_POST["TO_HOUR"].":".$_POST["TO_MIN"];
    $_SESSION["IPPSEC_DAY"]["USER"]=$_POST["USER"];
    $_SESSION["IPPSEC_DAY"]["IP"]=$_POST["IP"];
    $_SESSION["IPPSEC_DAY"]["MAC"]=$_POST["MAC"];
    $_SESSION["IPPSEC_DAY"]["SIZE"]=$_POST["SIZE"];
    BUILD_DEFAULT_DATA();

}

function template(){
    $t=time();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $imgwait="<img src='/img/Eclipse-0.9s-120px.gif'>";


    $html[]="<table style='width:100%'>";
        $html[] = "<tr>";
        $html[] = "<td style='width:50%;vertical-align:top'><div id='vertical-timeline-bytes-$t'>$imgwait</div></td>";
        $html[] = "<td style='width:50%;vertical-align:top'><div id='vertical-timeline-packets-$t'>$imgwait</div></td>";
    $html[]="</tr>";

    $html[]="</table>";



    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top' width='2%' nowrap><div id='top-tunnels-table-$t'>$imgwait</div></td>";
    $html[]="<td style='vertical-align:top'><div id='top-tunnels-$t' style='height:600px;width:100%'>$imgwait</div></td>";
    $html[]="</tr>";


    $html[]="<tr>";
    $html[]="<td colspan=2><hr></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top' width='2%' nowrap><div id='top-vips-table-$t'>$imgwait</div></td>";
    $html[]="<td style='vertical-align:top'><div id='top-vips-$t' style='height:600px;width:100%'>$imgwait</div></td>";
    $html[]="</tr>";


    $html[]="<tr>";
    $html[]="<td colspan=2><hr></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top' width='2%' nowrap><div id='top-members-table-$t'>$imgwait</div></td>";
    $html[]="<td style='vertical-align:top'><div id='top-members-$t' style='height:600px;width:100%'>$imgwait</div></td>";
    $html[]="</tr>";




    $html[]="<tr>";
    $html[]="<td colspan=2><hr></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width: 250px;display: inline-block;white-space: nowrap;' width='2%' nowrap><div id='top-remote-hosts-table-$t'>$imgwait</div></td>";
    $html[]="<td style='vertical-align:top'><div id='top-remote-hosts-$t' style='height:600px;width:100%'>$imgwait</div></td>";
    $html[]="</tr>";




    $html[]="</table>";

    $html[]="<script>";
    $html[]="Loadjs('$page?vertical-timeline=yes&id=$t');";
    $html[]="Loadjs('$page?top-tunnels=yes&id=top-tunnels-$t&suffix=$t');";
    $html[]="Loadjs('$page?top-members=yes&id=top-members-$t&suffix=$t');";
    $html[]="Loadjs('$page?top-vips=yes&id=top-vips-$t&suffix=$t');";
    $html[]="Loadjs('$page?top-remote-hosts=yes&id=top-remote-hosts-$t&suffix=$t');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);


}

function top_tunnels(){
    $page=CurrentPageName();
    $t=$_GET["suffix"];
    $tpl=new template_admin();
    $q=new postgres_sql();
    BUILD_DEFAULT_DATA();
    $DIFF_MIN=$_SESSION["IPPSEC_DAY"]["DIFF_MIN"];
    $DIFF_HOURS=$DIFF_MIN/60;
    echo "// DIFF: $DIFF_MIN hours";
    $time1=strtotime("{$_SESSION["IPPSEC_DAY"]["FROM"]} {$_SESSION["IPPSEC_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["IPPSEC_DAY"]["TO"]} {$_SESSION["IPPSEC_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);

    //$sql="SELECT SUM(bytes_in + bytes_out) as size,conn_name FROM strongswan_stats WHERE  zdate >'$strtime' and zdate < '$strtoTime'  GROUP BY conn_name ORDER BY size DESC LIMIT 15";
    $sql="select SUM(bytes_in + bytes_out) as size,conn_name from (SELECT distinct on (spi_in) spi_in, MAX(bytes_in) as bytes_in, MAX(bytes_out) as bytes_out, conn_name,zdate FROM  strongswan_stats where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY spi_in,conn_name,zdate ORDER BY spi_in,conn_name) x where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY conn_name ORDER BY size DESC LIMIT 15";
echo $sql;


    $q->QUERY_SQL($sql);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);
        $PieData2[$ligne["conn_name"]]=array($size,$ligne["conn_name"]);
        $PieData[$ligne["conn_name"]]=$size;
    }

    $encoded=base64_encode(serialize($PieData2));

    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{APP_STRONGSWAN_TOP_TUNNELS_BY_TRAFFIC_FLOW}";
    $highcharts->TitleFontSize = "14px";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();

    echo "LoadAjax('top-tunnels-table-$t','$page?tunnels-table=$encoded');";


}

//PIE CHART MEMBERS
function top_members(){
    $page=CurrentPageName();
    $t=$_GET["suffix"];
    $tpl=new template_admin();
    $q=new postgres_sql();
    BUILD_DEFAULT_DATA();
    $DIFF_MIN=$_SESSION["IPPSEC_DAY"]["DIFF_MIN"];
    $DIFF_HOURS=$DIFF_MIN/60;
    echo "// DIFF: $DIFF_MIN hours";
    $time1=strtotime("{$_SESSION["IPPSEC_DAY"]["FROM"]} {$_SESSION["IPPSEC_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["IPPSEC_DAY"]["TO"]} {$_SESSION["IPPSEC_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);

    //$sql="SELECT SUM(bytes_in + bytes_out) as size,username FROM strongswan_stats WHERE  zdate >'$strtime' and zdate < '$strtoTime'  GROUP BY username ORDER BY size DESC LIMIT 15";
    $sql="select SUM(bytes_in + bytes_out) as size,username from (SELECT distinct on (spi_in) spi_in, MAX(bytes_in) as bytes_in, MAX(bytes_out) as bytes_out, username,zdate FROM  strongswan_stats where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY spi_in,username,zdate ORDER BY spi_in,username) x where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY username ORDER BY size DESC LIMIT 15";

    $q->QUERY_SQL($sql);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);
        $PieData2[$ligne["username"]]=array($size,$ligne["username"]);
        $PieData[$ligne["username"]]=$size;
    }

    $encoded=base64_encode(serialize($PieData2));

    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{username_top}";
    $highcharts->TitleFontSize = "14px";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();

    echo "LoadAjax('top-members-table-$t','$page?members-table=$encoded');";

}
//TABLE MEMBERS
function members_table(){
    $tpl=new template_admin();
    $html[]="<table style='width:100%' class='table'>";
    $html[]="<tr>";
    $html[]="<th>{member}</th>";
    $html[]="<th>{APP_STRONGSWAN_IN_OUT_MB}</th>";
    $html[]="</tr>";
    $table=unserialize(base64_decode($_GET["members-table"]));
    foreach ( $table as $site=>$array) {
        $sizeM=$array[0];

        $link=$tpl->td_href($site,null,"Loadjs('fw.strongswan.stats.members.php?field=username&value=".urlencode($site)."')");

        $html[]="<tr>";
        $html[]="<td><strong>$link</strong></td>";
        $html[]="<td>".FormatBytes($sizeM*1024)."</td>";
        $html[]="</tr>";

    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}

//PIE CHART VIPS
function top_vips(){
    $page=CurrentPageName();
    $t=$_GET["suffix"];
    $tpl=new template_admin();
    $q=new postgres_sql();
    BUILD_DEFAULT_DATA();
    $DIFF_MIN=$_SESSION["IPPSEC_DAY"]["DIFF_MIN"];
    $DIFF_HOURS=$DIFF_MIN/60;
    echo "// DIFF: $DIFF_MIN hours";
    $time1=strtotime("{$_SESSION["IPPSEC_DAY"]["FROM"]} {$_SESSION["IPPSEC_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["IPPSEC_DAY"]["TO"]} {$_SESSION["IPPSEC_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);

    //$sql="SELECT SUM(bytes_in + bytes_out) as size,local_vip FROM strongswan_stats WHERE  zdate >'$strtime' and zdate < '$strtoTime'  GROUP BY local_vip ORDER BY size DESC LIMIT 15";
    $sql="select SUM(bytes_in + bytes_out) as size,local_vip from (SELECT distinct on (spi_in) spi_in, MAX(bytes_in) as bytes_in, MAX(bytes_out) as bytes_out, local_vip,zdate FROM  strongswan_stats where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY spi_in,local_vip,zdate ORDER BY spi_in,local_vip) x where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY local_vip ORDER BY size DESC LIMIT 15";
    $q->QUERY_SQL($sql);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);
        $PieData2[$ligne["local_vip"]]=array($size,$ligne["local_vip"]);
        $PieData[$ligne["local_vip"]]=$size;
    }

    $encoded=base64_encode(serialize($PieData2));

    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{vips_top}";
    $highcharts->TitleFontSize = "14px";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();

    echo "LoadAjax('top-vips-table-$t','$page?vips-table=$encoded');";

}
//TABLE VIPS
function vips_table(){
    $tpl=new template_admin();
    $html[]="<table style='width:100%' class='table'>";
    $html[]="<tr>";
    $html[]="<th>{vips}</th>";
    $html[]="<th>{APP_STRONGSWAN_IN_OUT_MB}</th>";
    $html[]="</tr>";
    $table=unserialize(base64_decode($_GET["vips-table"]));
    foreach ( $table as $site=>$array) {
        $sizeM=$array[0];

        $link=$tpl->td_href($site,null,"Loadjs('fw.strongswan.stats.members.php?field=local_vip&value=".urlencode($site)."')");

        $html[]="<tr>";
        $html[]="<td><strong>$link</strong></td>";
        $html[]="<td>".FormatBytes($sizeM*1024)."</td>";
        $html[]="</tr>";

    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}
//PIE CHART REMOTE HOSTS
function top_remote_hosts(){
    $page=CurrentPageName();
    $t=$_GET["suffix"];
    $tpl=new template_admin();
    $q=new postgres_sql();
    BUILD_DEFAULT_DATA();
    $DIFF_MIN=$_SESSION["IPPSEC_DAY"]["DIFF_MIN"];
    $DIFF_HOURS=$DIFF_MIN/60;
    echo "// DIFF: $DIFF_MIN hours";
    $time1=strtotime("{$_SESSION["IPPSEC_DAY"]["FROM"]} {$_SESSION["IPPSEC_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["IPPSEC_DAY"]["TO"]} {$_SESSION["IPPSEC_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);

    //$sql="SELECT SUM(bytes_in + bytes_out) as size,remote_host FROM strongswan_stats WHERE  zdate >'$strtime' and zdate < '$strtoTime'  GROUP BY remote_host ORDER BY size DESC LIMIT 15";
    $sql="select SUM(bytes_in + bytes_out) as size,remote_host from (SELECT distinct on (spi_in) spi_in, MAX(bytes_in) as bytes_in, MAX(bytes_out) as bytes_out, remote_host,zdate FROM  strongswan_stats where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY spi_in,remote_host,zdate ORDER BY spi_in,remote_host) x where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY remote_host ORDER BY size DESC LIMIT 15";

    $q->QUERY_SQL($sql);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);
        $PieData2[$ligne["remote_host"]]=array($size,$ligne["remote_host"]);
        $PieData[$ligne["remote_host"]]=$size;
    }

    $encoded=base64_encode(serialize($PieData2));

    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{vips_top}";
    $highcharts->TitleFontSize = "14px";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();

    echo "LoadAjax('top-remote-hosts-table-$t','$page?remote-hosts-table=$encoded');";

}
//TABLE VIPS
function remote_hosts_table(){
    $tpl=new template_admin();
    $html[]="<table style='width:100%' class='table'>";
    $html[]="<tr>";
    $html[]="<th>{remote_hosts}</th>";
    $html[]="<th>{APP_STRONGSWAN_IN_OUT_MB}</th>";
    $html[]="</tr>";
    $table=unserialize(base64_decode($_GET["remote-hosts-table"]));
    foreach ( $table as $site=>$array) {
        $sizeM=$array[0];

        $link=$tpl->td_href($site,null,"Loadjs('fw.strongswan.stats.members.php?field=remote_host&value=".urlencode($site)."')");

        $html[]="<tr>";
        $html[]="<td><strong>$link</strong></td>";
        $html[]="<td>".FormatBytes($sizeM*1024)."</td>";
        $html[]="</tr>";

    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}

//TABLE TUNNELS
function tunnels_table(){
    $tpl=new template_admin();
    $html[]="<table style='width:100%' class='table'>";
    $html[]="<tr>";
    $html[]="<th>{tunnel}</th>";
    $html[]="<th>{APP_STRONGSWAN_IN_OUT_MB}</th>";
    $html[]="</tr>";
    $table=unserialize(base64_decode($_GET["tunnels-table"]));
    foreach ( $table as $site=>$array) {
        $sizeM=$array[0];
        $tunnel=$array[1];

        $link=$tpl->td_href("$site",null,"Loadjs('fw.strongswan.stats.tunnel.php?tunnel=$tunnel')");

        $html[]="<tr>";
        $html[]="<td><strong>$link</strong></td>";
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
    $DIFF_MIN=$_SESSION["IPPSEC_DAY"]["DIFF_MIN"];
    $DIFF_HOURS=$DIFF_MIN/60;
    echo "// DIFF: $DIFF_MIN hours";
    $time1=strtotime("{$_SESSION["IPPSEC_DAY"]["FROM"]} {$_SESSION["IPPSEC_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["IPPSEC_DAY"]["TO"]} {$_SESSION["IPPSEC_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);
    $UNIT="{each_10minutes}";
    $SQL_DATE="zdate";



    if($DIFF_HOURS>6){
        $UNIT="{hourly}";
        $SQL_DATE="date_part('hour', zdate)";
    }

    if($DIFF_HOURS>24) {
        $UNIT = "{daily}";
        $SQL_DATE = "date_part('day', zdate)";
    }
        //BUILD BYTES GRAPH
//        $sql = "SELECT SUM(bytes_out) as bytes_out, SUM(bytes_in) as bytes_in,$SQL_DATE as zdate FROM strongswan_stats WHERE zdate >'$strtime' and zdate < '$strtoTime' GROUP by $SQL_DATE ORDER BY $SQL_DATE";
    $sql="select sum(bytes_in) as bytes_in, sum(bytes_out) as bytes_out, $SQL_DATE as zdate from (SELECT distinct on (spi_in) spi_in, MAX(bytes_in) as bytes_in, MAX(bytes_out) as bytes_out, zdate FROM  strongswan_stats where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY spi_in, zdate ORDER BY spi_in, zdate) x where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY $SQL_DATE ORDER BY $SQL_DATE";

        echo $sql."\n";
        $results = $q->QUERY_SQL($sql);
        if (!$q->ok) {
            $tpl->js_mysql_alert($q->mysql_error);
            return;
        }

        while ($ligne = pg_fetch_assoc($results)) {
            $bytes_out = $ligne["bytes_out"];
            $bytes_out = $bytes_out / 1024;
            $bytes_out = $bytes_out / 1024;
            $bytes_out = round($bytes_out);
            $bytes_in = $ligne["bytes_in"];
            $bytes_in = $bytes_in / 1024;
            $bytes_in = $bytes_in / 1024;
            $bytes_in = round($bytes_in);
            echo "// {$ligne["zdate"]}\n";
            $stime = strtotime($ligne["zdate"]);
            $datetext = date("H:i", $stime);
            if ($DIFF_HOURS > 6) {
                $datetext = "{$ligne["zdate"]}h";
            }
            if ($DIFF_HOURS > 24) {
                $datetext = $tpl->_ENGINE_parse_body("{day}") . " {$ligne["zdate"]}";
            }
            $xdata[] = $datetext;
            $mb_in[] = $bytes_in;
            $mb_out[] = $bytes_out;
            //$conn_name = $ligne["conn_name"];
        }

        $stimeFrom = $tpl->time_to_date($time1);
        $stimeTo = $tpl->time_to_date($time2);
        $title = "{APP_STRONGSWAN_ALL_TUNNELS} - {APP_STRONGSWAN_TRAFFIC_FLOW} (MB) - $stimeFrom - $stimeTo {$UNIT}";
        $timetext = $_GET["interval"];
        $highcharts = new highcharts();
        $highcharts->container = 'vertical-timeline-bytes-'.$_GET["id"];
        $highcharts->xAxis = $xdata;
        $highcharts->Title = $title;
        $highcharts->TitleFontSize = "14px";
        $highcharts->AxisFontsize = "12px";
        $highcharts->yAxisTtitle = "MB";
        $highcharts->xAxis_labels = true;
        $highcharts->LegendSuffix = "MB";
        $highcharts->ChartType = "line";
        $highcharts->xAxisTtitle = $timetext;
        $highcharts->datas = array('In ' => $mb_in, 'Out' => $mb_out);
        echo $highcharts->BuildChart();
        //$f[]="Loadjs('$page?chart2=$xtime');";


        //echo @implode("\n", $f);
        //BUILD PACKETD GRAPH
        //$sql = "SELECT SUM(packets_out) as packets_out, SUM(packets_in) as packets_in,$SQL_DATE as zdate FROM strongswan_stats WHERE zdate >'$strtime' and zdate < '$strtoTime' GROUP by $SQL_DATE ORDER BY $SQL_DATE";
    $sql="select sum(packets_out) as packets_out, sum(packets_in) as packets_in, $SQL_DATE as zdate from (SELECT distinct on (spi_in) spi_in, MAX(packets_out) as packets_out, MAX(packets_in) as packets_in, zdate FROM  strongswan_stats where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY spi_in, zdate ORDER BY spi_in, zdate) x where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY $SQL_DATE ORDER BY $SQL_DATE";
        $results = $q->QUERY_SQL($sql);
        if (!$q->ok) {
            $tpl->js_mysql_alert($q->mysql_error);
            return;
        }
        while ($ligne = pg_fetch_assoc($results)) {
            $packets_out = $ligne["packets_out"];
            $packets_in = $ligne["packets_in"];
            echo "// {$ligne["zdate"]}\n";
            $stime = strtotime($ligne["zdate"]);
            $datetext = date("H:i", $stime);
            if ($DIFF_HOURS > 6) {
                $datetext = "{$ligne["zdate"]}h";
            }
            if ($DIFF_HOURS > 24) {
                $datetext = $tpl->_ENGINE_parse_body("{day}") . " {$ligne["zdate"]}";
            }
            $xdata_pk[] = $datetext;
            $pk_in[] = $packets_in;
            $pk_out[] = $packets_out;
            //$conn_name = $ligne["conn_name"];
        }

        $stimeFrom = $tpl->time_to_date($time1);
        $stimeTo = $tpl->time_to_date($time2);
        $title = "{APP_STRONGSWAN_ALL_TUNNELS} - {APP_STRONGSWAN_PACKETS_FLOW} - $stimeFrom - $stimeTo {$UNIT}";
        $timetext = $_GET["interval"];
        $highcharts = new highcharts();
        $highcharts->container = 'vertical-timeline-packets-'.$_GET["id"];
        $highcharts->xAxis = $xdata_pk;
        $highcharts->Title = $title;
        $highcharts->TitleFontSize = "14px";
        $highcharts->AxisFontsize = "12px";
        $highcharts->yAxisTtitle = "Packets";
        $highcharts->xAxis_labels = true;
        $highcharts->LegendSuffix = " Packets";
        $highcharts->ChartType = "line";
        $highcharts->xAxisTtitle = $timetext;
        $highcharts->datas = array('In' => $pk_in,'Out' => $pk_out);
        echo $highcharts->BuildChart();



}



function page(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $btn=$tpl->button_inline("{online_help}","s_PopUp('https://wiki.articatech.com/en/network/vpn/setup-a-vpn-ipsec','1024','800')","fa-solid fa-headset",null,null,"btn-blue");

    $html[]="<div class='row ibox-content white-bg'>";
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top: 10px'>";
    $html[]=$tpl->button_label_table("{build_query}",
        "Loadjs('$page?query-js=yes')", "fas fa-filter","AsWebStatisticsAdministrator");
    $html[]=$tpl->button_label_table("{refresh}",
        "LoadAjax('ipsec-data','$page?template=yes')", "fas fa-sync-alt","AsWebStatisticsAdministrator");
    $html[]="$btn</div>";

    $html[]="<div id='ipsec-data' ></div>";
    $html[]="</div>";
    $html[]="<script>
$.address.state('/');$.address.value('/ipsec-statistics');
LoadAjax('ipsec-data','$page?template=yes');</script>";

    if(isset($_GET["main-page"])){$tpl=new template_admin('Artica: IPSec Statistics',$tpl->_ENGINE_parse_body($html));echo $tpl->build_firewall();return;}
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



    $time1=strtotime( $_SESSION["IPPSEC_DAY"]["FROM"]." ". $_SESSION["IPPSEC_DAY"]["FROMH"]);
    $time2=strtotime($_SESSION["IPPSEC_DAY"]["TO"]." ". $_SESSION["IPPSEC_DAY"]["TOH"]);

    $seconds=$time2-$time1;

    if($seconds<0){
        echo  $tpl->div_error("{please_define_dates_in_correct_order}");
        return;
    }



    if($seconds<259150){
        $TABLE_SELECTED="statscom";
        $date1=$_SESSION["IPPSEC_DAY"]["FROM"]." ". $_SESSION["IPPSEC_DAY"]["FROMH"];
        $date2=$_SESSION["IPPSEC_DAY"]["TO"]." ". $_SESSION["IPPSEC_DAY"]["TOH"];
        $tt2=date("Y-m-d",strtotime($date2));

        $TITLES[]="{from} " .$tpl->time_to_date(strtotime($date1),true);
        if($tt2==date("Y-m-d")) {
            $TITLES[] = "{to} {today} " . $tpl->time_to_date(strtotime($date2), true);
        }else{
            $TITLES[] = "{to} " . $tpl->time_to_date(strtotime($date2), true);
        }
    }else{
        $TABLE_SELECTED="statscom_days";
        $date1=$_SESSION["IPPSEC_DAY"]["FROM"];
        $date2=$_SESSION["IPPSEC_DAY"]["TO"];
        $tt2=date("Y-m-d",strtotime($date2));
        $TITLES[]="{from} " .$tpl->time_to_date(strtotime($date1),false);
        if($tt2==date("Y-m-d")) {
            $TITLES[] = "{to} {today} " . $tpl->time_to_date(strtotime($date2), false);
        }else{
            $TITLES[] = "{to} " . $tpl->time_to_date(strtotime($date2), false);
        }
    }

    $TITLES[]="(".distanceOfTimeInWords(strtotime($date1),strtotime($date2)).")";


    if($_SESSION["IPPSEC_DAY"]["USER"]<>null){
        $TITLES[]="{and} {member} {$_SESSION["IPPSEC_DAY"]["USER"]}";
        $WHEREU=" AND username='{$_SESSION["IPPSEC_DAY"]["USER"]}'";
    }
    if($_SESSION["IPPSEC_DAY"]["IP"]<>null){
        $TITLES[]="{and} {ipaddr} {$_SESSION["IPPSEC_DAY"]["IP"]}";
        $WHEREI=" AND ipaddr='{$_SESSION["IPPSEC_DAY"]["IP"]}'";
    }
    if($_SESSION["IPPSEC_DAY"]["MAC"]<>null){
        $TITLES[]="{and} {MAC} {$_SESSION["IPPSEC_DAY"]["MAC"]}";
        $WHEREM=" AND mac='{$_SESSION["IPPSEC_DAY"]["MAC"]}'";
    }
    if( intval($_SESSION["IPPSEC_DAY"]["SIZE"])>0){
        $size_q=round(intval( $_SESSION["IPPSEC_DAY"]["SIZE"])*1024);
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
    LIMIT {$_SESSION["IPPSEC_DAY"]["LIMIT"]}";

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
