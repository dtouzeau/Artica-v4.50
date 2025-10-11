<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.tcpip.inc');
	include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
	include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
	include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
	include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
	include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
	include_once(dirname(__FILE__)."/ressources/class.influx.inc");


	$users=new usersMenus();
	if(!$users->AsProxyMonitor){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
	if(isset($_GET["query"])){graph();exit;}
	if(isset($_GET["memstats"])){memstats();exit;}

	
page();


function page(){
	$q=new mysql_squid_builder();
	$timekey=date('Ymd');
	$timekeyMonth=date("Ym");
	$time=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$tpl=new templates();
	
	$t=time();
	$per["1m"]="{minute}";
	$per["5m"]="5 {minutes}";
	$per["10m"]="10 {minutes}";
	$per["1h"]="{hour}";
	$per["1d"]="{day}";
	
	
	$members["MAC"]="{MAC}";
	$members["USERID"]="{uid}";
	$members["IPADDR"]="{ipaddr}";
	
	
	
	if(!isset($_SESSION["SQUID_STATS_MEM_DATE1"])){$_SESSION["SQUID_STATS_MEM_DATE1"]=date("Y-m-d");}
	if(!isset($_SESSION["SQUID_STATS_MEM_TIME1"])){$_SESSION["SQUID_STATS_MEM_TIME1"]="00:00";}
	
	if(!isset($_SESSION["SQUID_STATS_MEM_DATE2"])){$_SESSION["SQUID_STATS_MEM_DATE2"]=date("Y-m-d");}
	if(!isset($_SESSION["SQUID_STATS_MEM_TIME2"])){$_SESSION["SQUID_STATS_MEM_TIME2"]="23:00";}
	
	
	$html="
	<div style='width:98%;margin-bottom:20px' class=form>
	<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;font-size:18px' class=legend>{interval}:</td>
		<td style='vertical-align:top;font-size:18px;'>". Field_array_Hash($per,"interval-$t","10m","blur()",null,0,"font-size:18px;")."</td>
		<td style='vertical-align:top;font-size:18px' class=legend nowrap>{from_date}:</td>
		<td style='vertical-align:top;font-size:18px'>". field_date("from-date-$t",$_SESSION["SQUID_STATS_MEM_DATE1"],";font-size:18px;width:160px")."</td>
		<td style='vertical-align:top;font-size:18px'>". Field_text("from-time-$t",$_SESSION["SQUID_STATS_MEM_TIME1"],";font-size:18px;width:82px")."</td>
		<td style='vertical-align:top;font-size:18px' class=legend nowrap>{to_date}:</td>
		<td style='vertical-align:top;font-size:18px'>". field_date("to-date-$t",$_SESSION["SQUID_STATS_MEM_DATE2"],";font-size:18px;width:160px")."</td>
		<td style='vertical-align:top;font-size:18px'>". Field_text("to-time-$t",$_SESSION["SQUID_STATS_MEM_TIME2"],";font-size:18px;width:82px")."</td>
		<td style='vertical-align:top;font-size:18px;;width:400px'>". button("Go","Run$t()",18)."</td>
		</tr>
		</table>
	</div>
	<div style='font-size:32px'>{proxy_memory_service_status}</div>
	<div style='width:1280px;height:550px;margin-bottom:10px' id='graph-$t'></div>
	<div style='font-size:32px'>{server_memory_consumption}</div>
	<div style='width:1280px;height:550px;margin-bottom:10px' id='memstats-$t'></div>
	
	
	
	
	
	
<script>

function Run$t(){
	var date1=document.getElementById('from-date-$t').value;
	var time1=document.getElementById('from-time-$t').value;
	var date2=document.getElementById('to-date-$t').value
	var time2=document.getElementById('to-time-$t').value;
	var interval=document.getElementById('interval-$t').value;
	Loadjs('$page?query=yes&container=graph-$t&date1='+date1+'&time1='+time1+'&date2='+date2+'&time2='+time2+'&interval='+interval);
	Loadjs('$page?memstats=yes&container=memstats-$t&date1='+date1+'&time1='+time1+'&date2='+date2+'&time2='+time2+'&interval='+interval);

}
Run$t();
</script>	
";	
	
	echo $tpl->_ENGINE_parse_body($html);

}

function graph(){
	$time=time();
	$page=CurrentPageName();
	$influx=new influx();
	$_SESSION["SQUID_STATS_MEM_DATE1"]=$_GET["date1"];
	$_SESSION["SQUID_STATS_MEM_TIME1"]=$_GET["time1"];
	
	$_SESSION["SQUID_STATS_MEM_DATE2"]=$_GET["date2"];
	$_SESSION["SQUID_STATS_MEM_TIME2"]=$_GET["time2"];
	
	
	
	$from=strtotime("{$_GET["date1"]} {$_GET["time1"]}");
	$to=strtotime("{$_GET["date2"]} {$_GET["time2"]}");
	
	$sql="select MEAN(memory) as memory from squidmem group by time({$_GET["interval"]}) where time > {$from}s and time < {$to}s";
	$main=$influx->QUERY_SQL($sql);
	echo "// $sql";
	$per["1m"]="H:i";
	$per["5m"]="H:i";
	$per["10m"]="H:i";
	$per["15m"]="H:i";
	$per["30m"]="H:i";
	$per["1h"]="m-d H:00";
	$per["1d"]="m-d";
	
	
	foreach ($main as $row) {
		$time=$row->time;
		$min=date($per[$_GET["interval"]],$time);
		$xdata[]=$min;
		$ydata[]=$row->memory;
	}
	
	
	$page=CurrentPageName();
	$time=time();
	
	krsort($xdata);
	krsort($ydata);
	
	
	$title="{proxy_memory_service_status} (MB)";
	$timetext=$_GET["interval"];
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	//$highcharts->subtitle="<a href=\"javascript:blur();\" OnClick=\"Loadjs('squid.rtt.php')\" style='font-size:16px;text-decoration:underline'>{realtime_flow}</a>";
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=true;
	
	$highcharts->LegendSuffix="MB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();
	
}

function memstats(){
	$time=time();
	$page=CurrentPageName();
	$influx=new influx();
	$q=new mysql();
	
	$_SESSION["SQUID_STATS_MEM_DATE1"]=$_GET["date1"];
	$_SESSION["SQUID_STATS_MEM_TIME1"]=$_GET["time1"];
	
	$_SESSION["SQUID_STATS_MEM_DATE2"]=$_GET["date2"];
	$_SESSION["SQUID_STATS_MEM_TIME2"]=$_GET["time2"];
	
	
	
	$from=strtotime("{$_GET["date1"]} {$_GET["time1"]}");
	$to=strtotime("{$_GET["date2"]} {$_GET["time2"]}");
	
	$md5=md5("{$_GET["interval"]}$from$to");
	
	$sql="SELECT MEM_STATS FROM SYSTEM  WHERE time > {$from}s and time < {$to}s GROUP BY time({$_GET["interval"]})";
	$main=$influx->QUERY_SQL($sql);
	echo "// $sql";
	$per["1m"]="Y-m-d H:i:00";
	$per["5m"]="Y-m-d H:i:00";
	$per["10m"]="Y-m-d H:i:00";
	$per["15m"]="Y-m-d H:i:00";
	$per["30m"]="Y-m-d H:i:00";
	$per["1h"]="Y-m-d H:00";
	$per["1d"]="Y-m-d";
	
	
	foreach ($main as $row) {
		$time=$row->time;
		$min=date($per[$_GET["interval"]],$time);
		$f[]="('$min','$row->MEM_STATS')";
		
		
	}
	
	$temptable="tmp_$md5";
	
	$sql="CREATE TABLE IF NOT EXISTS `$temptable` (
	`zDate` DATETIME PRIMARY KEY,
	`size` INT UNSIGNED NOT NULL DEFAULT 1,
	KEY `size`(`size`)
	)  ENGINE = MYISAM;";
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "//$q->mysql_error\n";}
	
	
	
	$q->QUERY_SQL("INSERT IGNORE INTO `$temptable` (`zDate`,`size`) VALUES ".@implode(",", $f),"artica_backup");
	$results=$q->QUERY_SQL("SELECT AVG(size) as MEM_STATS,zDate FROM $temptable GROUP BY zDate ORDER BY zDate","artica_backup");

	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$time=strtotime($ligne["zDate"]);		
		$xdata[]=date("H:i",$time);
		$ydata[]=$ligne["MEM_STATS"]/1024;

	}
			
	$q->QUERY_SQL("DROP TABLE $temptable","artica_backup");

	
	
	$page=CurrentPageName();
	$time=time();
	

	
	
	$title="{server_memory_consumption} (GB)";
	$timetext=$_GET["interval"];
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	//$highcharts->subtitle="<a href=\"javascript:blur();\" OnClick=\"Loadjs('squid.rtt.php')\" style='font-size:16px;text-decoration:underline'>{realtime_flow}</a>";
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="GB";
	$highcharts->xAxis_labels=true;
	
	$highcharts->LegendSuffix="GB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();
	
	
	
	
}

function graph_current_month_day(){

	$timekey=date('Ym');
	$time=time();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$table="squidmemoryM_$timekey";
	
	$sql="SELECT `day` zhour,memoryuse FROM `$table` ORDER BY `day`";
	$results=$q->QUERY_SQL($sql);
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		if(strlen($ligne["zhour"])==1){$ligne["zhour"]="0{$ligne["zhour"]}";}
		$ttime="{$ligne["zhour"]}";
		$xdata[]=$ttime;
		$ydata[]=$ligne["memoryuse"];
	
	}
	
	$title="{this_month} (MB)";
	$timetext="{day}";
	
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{size} (MB)";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=date("m")."/".date("Y")." ";
	$highcharts->LegendSuffix="MB";
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();	
	
	
}


