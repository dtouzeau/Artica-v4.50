<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["none"])){die();}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["postgresqlPort"])){Save();exit;}
if(isset($_GET["ufdbconf-popup"])){ufdbconf_popup();exit;}
if(isset($_GET["ufdbdebug-popup"])){ufdbdebug_popup();exit;}
if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["remove-database-ask"])){remove_database_ask();exit;}
if(isset($_GET["statscom-super-top-status"])){status_top();exit;}

status();

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{status}"]="$page?table=yes";
    $array["{entities}"]="fw.statscom.entities.php";
	$array["{days}"]="fw.statscom.days.php";
    $array["{data}"]="fw.statscom.data.php";
    $array["{pdf_reports}"]="fw.statscom.reports.php";
    $array["{settings}"]="fw.statscom.settings.php";
    $array["{service_events}"]="fw.statscom.debug.php";
    echo $tpl->tabs_default($array);

}

function remove_database_ask(){
    $page=CurrentPageName();
    $tpl=new template_admin();


    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/postgres.remove.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/postgres.remove.progress.txt";
    $ARRAY["CMD"]="postgres.php?remove-database=yes";
    $ARRAY["TITLE"]="{REMOVE_DATABASE}";
    $ARRAY["AFTER"]="LoadAjax('table-postgresqlstatus','$page?tabs=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=postgress-statscom-restart');";
    $tpl->js_confirm_delete("{REMOVE_DATABASE}<hr>{rebuild_database_warn}","none",$tpl->javascript_parse_text("{database}"),$jsrestart);
}

function status(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    //

    $html=$tpl->page_header("{APP_STATS_COMMUNICATOR}","fa fa-chart-area","{APP_STATS_COMMUNICATOR_EXPLAIN}","$page?tabs=yes","statscom","postgress-statscom-restart",false,"table-statscom-status");


	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}
	
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body($html);

}
function table_status_logsink(){
    $tpl=new template_admin();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/syslog/logsink/stats/status"));
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg",ico_bug,"Decoding ".json_last_error_msg(),"{error}"));
        return "";
    }
    if(!property_exists($json,"Info")){
        echo $tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg",ico_bug,"Missing property","{error}"));
        return "";
    }
    if(!$json->Info->featureActive){
        echo $tpl->_ENGINE_parse_body($tpl->widget_style1("gray-bg",ico_sensor,"{status}","{inactive2}"));
        return "";
    }
    if(!$json->Info->configured){
        echo $tpl->_ENGINE_parse_body($tpl->widget_style1("yellow-bg",ico_sensor,"{status}","{unconfigured}"));
        return "";
    }
    if(!$json->Info->running){
        echo $tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg",ico_sensor,"{stopped}","{status}"));
        return "";
    }
    if($json->Info->messages==0){
        $sensor=$tpl->_ENGINE_parse_body($tpl->widget_style1("gray-bg",ico_sensor,"{received}",0));
    }else {
        $Num = $tpl->FormatNumber($json->Info->messages);
        $sensor=$tpl->_ENGINE_parse_body($tpl->widget_style1("navy-bg",ico_sensor,"{received}",$Num));
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/statistics/status"));

    if (json_last_error()> JSON_ERROR_NONE) {
        $stats=$tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg",ico_statistics,json_last_error_msg(),"{error}"));
    }else{
        if(!$json->Status){
            $stats=$tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg",ico_statistics,$json->Error,"{error}"));
        }else{
            if($json->Received==0 && $json->Processed==0) {
                $stats=$tpl->_ENGINE_parse_body($tpl->widget_style1("gray-bg",ico_statistics,"{received}",0));
            }else{
                $Received=$tpl->FormatNumber($json->Received);
                $Processed=$tpl->FormatNumber($json->Processed);
                $stats=$tpl->_ENGINE_parse_body($tpl->widget_style1("navy-bg",ico_statistics,"{received}/{processed}","$Received&nbsp;/&nbsp;$Processed"));
            }

        }
    }

    $Queue=$tpl->_ENGINE_parse_body($tpl->widget_style1("gray-bg",ico_timeout,"{queued}",0));

    if(property_exists($json,"Queue")){
        if($json->Queue>0){
            $Queue=$tpl->_ENGINE_parse_body($tpl->widget_style1("navy-bg",ico_timeout,"{queued}",$tpl->FormatNumber($json->Queue)));

        }
    }
    $tpl=new template_admin();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[] = "<td style='padding:2px;width:33%'>$sensor</td>";
    $html[] = "<td style='padding:2px;width:33%'>$stats</td>";
    $html[] = "<td style='padding:2px;width:33%'>$Queue</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return "";
}

function status_top():string{
    $tpl=new template_admin();
    $sock=new sockets();
    $EnableSyslogLogSink=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSyslogLogSink"));
    if ($EnableSyslogLogSink==1){
        return table_status_logsink();
    }
    $data=$sock->REST_API("/proxy/statistics/status");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_style1("bg-red",ico_bug,"{error}",json_last_error_msg()));
        return "";

    }
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_style1("bg-red",ico_bug,"{error}",$json->Error));
        return "";
    }

    if($json->Received==0) {
        $received=$tpl->_ENGINE_parse_body($tpl->widget_style1("gray-bg","fas fa-arrow-to-right","{received}",0));
    }else {
        $received=$tpl->_ENGINE_parse_body($tpl->widget_style1("navy-bg","fas fa-arrow-to-right","{received}",$tpl->FormatNumber($json->Received)));

    }

    if($json->Processed==0) {
        $Processed=$tpl->_ENGINE_parse_body($tpl->widget_style1("gray-bg","fas fa-sort-numeric-down","{processed}",0));

    }else {
        $Processed=$tpl->_ENGINE_parse_body($tpl->widget_style1("navy-bg","fas fa-sort-numeric-down","{processed}",$tpl->FormatNumber($json->Processed)));

    }

    if($json->Queue==0){
        $queue=$tpl->_ENGINE_parse_body($tpl->widget_style1("gray-bg","fas fa-fill-drip","{queued}",0));
    }else{
        $queue=$tpl->_ENGINE_parse_body($tpl->widget_style1("navy-bg","fas fa-fill-drip","{queued}",$tpl->FormatNumber($json->Queue)));
    }

    $SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
    if($SQUIDEnable==0){
        $ActAsASyslogServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActAsASyslogServer"));
        if($ActAsASyslogServer==0){
            echo $tpl->div_warning("{missing_feature}||{stats_no_syslog_receiever}");
        }
    }


    $tpl=new template_admin();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[] = "<td style='padding:2px;width:33%'>$received</td>";
    $html[] = "<td style='padding:2px;width:33%'>$Processed</td>";
    $html[] = "<td style='padding:2px;width:33%'>$queue</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return "";
}


function table(){
    $tpl=new template_admin();
    $STATSCOM_DISTANCE_TABLE="";
    $DISTANCE_DATE="";
    $STATSCOM_LAST_SCAN_TEXT="";
    $STATSCOM_FDATE_TABLE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("STATSCOM_FDATE_TABLE"));
    $FIRST_DATE_BIN=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("STATSCOM_LAST_SCANNED_DATE"));
    $STATSCOM_COUNT_BYTES=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("STATSCOM_COUNT_BYTES"));
    $IPADRS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("STATSCOM_COUNT_IPADRS"));
    $STATSCOM_COUNT_ENTRIES=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("STATSCOM_COUNT_ENTRIES"));
    if($FIRST_DATE_BIN>0) {
        $LAST_DATE = $tpl->time_to_date($FIRST_DATE_BIN, true);
        $DISTANCE_DATE = distanceOfTimeInWords($FIRST_DATE_BIN, time());
        $STATSCOM_LAST_SCAN_TEXT="&nbsp;<small>({last_scan} {since} $DISTANCE_DATE $STATSCOM_COUNT_ENTRIES {rows})</small>";
    }
    if($STATSCOM_FDATE_TABLE>0) {
        $STATSCOM_DISTANCE_TABLE = distanceOfTimeInWords($STATSCOM_FDATE_TABLE, time());
    }

    $ARTICA_STATS_REQUESTS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICA_STATS_REQUESTS"));
    $STATSCOM_PROXYNAME=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("STATSCOM_PROXYNAME"));
    $STATSCOM_COUNT_UNAME=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("STATSCOM_COUNT_UNAME"));
    $RtMDays=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("STATSCOM_COUNT_UDAYS"));





    $html[]="<div id='procedure-status' style='margin-top:15px'></div>";
	$html[]="<table style='width:100%;'>";
	$html[]="<tr>";
    $html[]="<td style='width:90%;vertical-align:top;padding-left:20px'>";
    $html[]="<div id='statscom-super-top-status'></div>";
    $html[]="<H2>{last_10_minutes}$STATSCOM_LAST_SCAN_TEXT</H2>";

    
    $html[]="<table style='width:100%;margin-top:5px'>";
    $html[]="<tr>";
    $html[] = "<td style='padding:2px;width:33%'>";
    $html[] = $tpl->widget_style1("navy-bg", "fas fa-alarm-clock", "{last_date}", "<span style='font-size:18px'>$LAST_DATE</span>" );
    $html[] = "</td>";
    $html[]="<td style='padding:2px;width:33%'>".$tpl->widget_style1("navy-bg","fas fa-cloud-showers","{requests}",$tpl->FormatNumber($ARTICA_STATS_REQUESTS))."</td>";
    $html[]="<td style='padding:2px;width:33%'>".$tpl->widget_style1("lazur-bg",ico_download,"{total_downloaded}",FormatBytes($STATSCOM_COUNT_BYTES/1024))."</td>";
    $html[]="</tr>";
    $html[] = "<td style='padding:2px;width:33%'>";
    $html[]=$tpl->widget_style1("navy-bg", "fa fa-desktop", "{nodes}", $tpl->FormatNumber($IPADRS));
    $html[]="</td>";
    $html[] = "<td style='padding:2px;width:33%'>";
    $html[]=$tpl->widget_style1("navy-bg", "fa fa-user", "{members}", $tpl->FormatNumber($STATSCOM_COUNT_UNAME));
    $html[]="</td>";
    $html[] = "<td style='padding:2px;width:33%'>" . $tpl->widget_style1("navy-bg", "fas fa-server", "{entities}", $tpl->FormatNumber($STATSCOM_PROXYNAME)) . "</td>";

    $html[]="</tr>";
    $html[]="</table>";




    $InfluxAdminRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxAdminRetentionTime"));
    if($InfluxAdminRetentionTime==0){$InfluxAdminRetentionTime=365;}
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $InfluxAdminRetentionTime=5;

    }
    $q=new lib_sqlite("/home/artica/SQLITE_TEMP/pg_tables.db");
    $tables[]="statscom";
    $tables[]="statscom_days";
    $tables[]="statscom_websites";
    $tables[]="visits";
    $tables[]="statscom_proxies";
    $tables[]="statsblocks";
    $zbytes=0;
    foreach ($tables as $tablename) {
        $ligne = $q->mysqli_fetch_array("SELECT zbytes FROM ztables WHERE tablename='$tablename'");
        $zbytes = $zbytes + intval($ligne["zbytes"]);
    }
    $tables_size_day=0;
    if($RtMDays>0){
        $tables_size_day=round($zbytes/$RtMDays);
        $tables_size_day=FormatBytes($tables_size_day/1024);
    }

    $tables_size=FormatBytes($zbytes/1024);

    $html[]="<H2>{APP_POSTGRES}</H2>";
    $html[]="<table style='width:100%;margin-top:5px'>";
    $html[]="<tr>";
    $html[] = "<td style='padding:2px;width:33%'>" .
        $tpl->widget_style1("navy-bg", "fas fa-alarm-clock", "{retention} ($STATSCOM_DISTANCE_TABLE)", $tpl->FormatNumber($RtMDays)."/$InfluxAdminRetentionTime {days}") .
        "</td>";
    $html[] = "<td style='padding:2px;width:33%'>" .
        $tpl->widget_style1("navy-bg", "fas fa-database", "{data_size}", $tables_size) .
        "</td>";
    $html[] = "<td style='padding:2px;width:33%'>" . $tpl->widget_style1("navy-bg", "fas fa-server", "{daily_volume}", "$tables_size_day/{day}")."</td>";

    $page=CurrentPageName();
    $js=$tpl->RefreshInterval_js("statscom-super-top-status",$page,"statscom-super-top-status=yes");

    $html[]="</tr>";
    $html[]="</table>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="<script>$js</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function Save(){
	$sock=new sockets();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	
	$_POST["zipproxy_MaxSize"]=$_POST["zipproxy_MaxSize"]*1024;
	
	foreach ($_POST as $key=>$value){
		$sock->SET_INFO($key, $value);
	}
	
}



function graph1(){

	$cacheFile=PROGRESS_DIR."/InfluxDB.state";
	$tpl=new templates();
	$ARRAY=unserialize(@file_get_contents($cacheFile));
	$ARRAY["PART"]=$ARRAY["PART"]/1024;

	$PART=intval($ARRAY["PART"])-intval($ARRAY["SIZEKB"]);

	$MAIN["Partition " .FormatBytes($ARRAY["PART"])]=$PART;
	$MAIN["DB ".FormatBytes($ARRAY["SIZEKB"])]=$ARRAY["SIZEKB"];

	$PieData=$MAIN;
	$highcharts=new highcharts();
	$highcharts->container="influx-db-size";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{database_size}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{database_size} ".FormatBytes($ARRAY["SIZEKB"]));
	echo $highcharts->BuildChart();
}
