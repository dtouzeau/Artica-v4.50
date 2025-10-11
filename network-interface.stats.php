<?php
ini_set('memory_limit','1000M');
header("Pragma: no-cache");
header("Expires: 0");
$GLOBALS["DUMP"]=false;
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.system.nics.inc');
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
if(isset($_GET["dump"])){$GLOBALS["DUMP"]=true;}

	$user=new usersMenus();
	if(!$user->AsWebStatisticsAdministrator){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		exit;
	}
	
	if(isset($_GET["main"])){main_page();exit;}
	if(isset($_GET["stats-requeteur"])){stats_requeteur();exit;}
	if(isset($_GET["requeteur-popup"])){requeteur_popup();exit;}
	if(isset($_GET["requeteur-js"])){requeteur_js();exit;}
	if(isset($_GET["remove-cache-js"])){remove_cache_js();exit;}
	if(isset($_GET["remove-cache"])){remove_cache_button();exit;}
	if(isset($_POST["remove-cache"])){remove_cache();exit;}
	if(isset($_GET["query-js"])){build_query_js();exit;}
	if(isset($_GET["from-md5-js"])){build_query_from_md5_js();exit;}
	if(isset($_GET["graph1"])){graph1();exit;}
	if(isset($_GET["graphP"])){graphP();exit;}
	
	if(isset($_GET["table1"])){table1();exit;}
	
	if(isset($_GET["graph2"])){graph2();exit;}
	if(isset($_GET["table2"])){table2();exit;}
	
	if(isset($_GET["table3"])){table3();exit;}
	if(isset($_GET["graph3"])){graph3();exit;}	

	if(isset($_GET["table4"])){table4();exit;}
	if(isset($_GET["graph4"])){graph4();exit;}
	

	
page();

function stats_requeteur(){
	$tpl=new templates();
	$page=CurrentPageName();

	$ahref_sys="<a href=\"javascript:blur();\"
	OnClick=\"Loadjs('$page?requeteur-js=yes&t={$_GET["t"]}')\">";
	echo $tpl->_ENGINE_parse_body("$ahref_sys{build_the_query}</a>");
}
function requeteur_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$build_the_query=$tpl->javascript_parse_text("{build_the_query}::{chronology}");
	echo "YahooWin('670','$page?requeteur-popup=yes&t={$_GET["t"]}','$build_the_query');";
}


function build_query_from_md5_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$oldMd5=$_GET["from-md5-js"];
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT ID,params FROM reports_cache WHERE `zmd5`='$oldMd5'"));
	if($ligne["ID"]==0){die("DIE " .__FILE__." Line: ".__LINE__);}
	$params=unserialize($ligne["params"]);
	$from=$params["FROM"];
	$to=$params["TO"];
	$interval=$params["INTERVAL"];
	$interface=$params["INTERFACE"];

	
	if(isset($_GET["interface"])){$interface=$_GET["interface"];}
	$array["FROM"]=$from;
	$array["TO"]=$to;
	$array["INTERVAL"]=$interval;
	$array["INTERFACE"]=$interface;
	
	
	$timetext1=$tpl->time_to_date($from,true);
	$timetext2=$tpl->time_to_date($to,true);
	
	
	$md5=md5("ETHSTATS:$from$to$interval$interface");
	$serialize=mysql_escape_string2(serialize($array));
	$nextFunction="LoadAjaxRound('interfaces-stats','$page?zmd5=$md5')";
	$nextFunction_encoded=urlencode(base64_encode($nextFunction));
	$title="Network Interface: $interval $timetext1 - {to} $timetext2 $interface";
	$sql="INSERT IGNORE INTO `reports_cache` (`zmd5`,`title`,`report_type`,`zDate`,`params`) VALUES
	('$md5','$title','ETHSTATS',NOW(),'$serialize')";
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "alert('". $tpl->javascript_parse_text($q->mysql_errror)."')";return;}
	echo "Loadjs('squid.statistics.progress.php?zmd5=$md5&NextFunction=$nextFunction_encoded')";
	
	
}

function build_query_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$from=strtotime("{$_GET["date1"]} {$_GET["time1"]}");
	$to=strtotime("{$_GET["date2"]} {$_GET["time2"]}");
	$interval=$_GET["interval"];
	$t=$_GET["t"];
	$interface=$_GET["interface"];
	
	$_SESSION["SQUID_STATS_DATE1"]=$_GET["date1"];
	$_SESSION["SQUID_STATS_TIME1"]=$_GET["time1"];
	
	$_SESSION["SQUID_STATS_DATE2"]=$_GET["date2"];
	$_SESSION["SQUID_STATS_TIME2"]=$_GET["time2"];
	
	
	$timetext1=$tpl->time_to_date(strtotime("{$_GET["date1"]} {$_GET["time1"]}"),true);
	$timetext2=$tpl->time_to_date(strtotime("{$_GET["date2"]} {$_GET["time2"]}"),true);
	
	
	
	$md5=md5("ETHSTATS:$from$to$interval$interface");
	$interval=$_GET["interval"];
	
	$nextFunction="LoadAjaxRound('interfaces-stats','$page?zmd5=$md5')";
	$nextFunction_encoded=urlencode(base64_encode($nextFunction));
	$q=new mysql_squid_builder();
	$q->CheckReportTable();
	
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT ID,builded FROM reports_cache WHERE `zmd5`='$md5'"));
	if(intval($ligne["ID"])==0){
		$array["FROM"]=$from;
		$array["TO"]=$to;
		$array["INTERVAL"]=$interval;
		$array["INTERFACE"]=$interface;
		
		$nic=new system_nic($eth);
		$serialize=mysql_escape_string2(serialize($array));
		$title="Network Interface: $interface $nic->IPADDR $interval $timetext1 - {to} $timetext2";
		$sql="INSERT IGNORE INTO `reports_cache` (`zmd5`,`title`,`report_type`,`zDate`,`params`) VALUES 
		('$md5','$title','ETHSTATS',NOW(),'$serialize')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "alert('". $tpl->javascript_parse_text($q->mysql_errror)."')";return;}
		echo "Loadjs('squid.statistics.progress.php?zmd5=$md5&NextFunction=$nextFunction_encoded')";
		return;
	}
	
	if(intval($ligne["builded"]==0)){
echo "
function Start$t(){
	Loadjs('squid.statistics.progress.php?zmd5=$md5&NextFunction=$nextFunction_encoded&t=$t');
}

if(document.getElementById('graph-$t')){
	document.getElementById('graph-$t').innerHTML='<center><img src=img/loader-big.gif></center>';
}
LockPage();	
setTimeout('Start$t()',800);
";



return;
}
	
	echo $nextFunction;
	
}
function remove_cache_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$zmd5=$_GET["zmd5"];
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT ID,`title` FROM `reports_cache` WHERE `zmd5`='$zmd5'"));
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."')";return;}
	$title=$tpl->javascript_parse_text("{delete} id {$ligne["ID"]} \"{$ligne["title"]}\" ($zmd5)");
	$page=CurrentPageName();
	
	
	$t=time();
echo "
var xLinkEdHosts$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){ alert(res); return; }
	
	
	if( document.getElementById('BROWSE_STATISTICS_CACHES2') ){
		$('#BROWSE_STATISTICS_CACHES2').flexReload();
	}	
	if( document.getElementById('BROWSE_STATISTICS_CACHES') ){
		$('#BROWSE_STATISTICS_CACHES').flexReload();
	}
	
	
}
	
	
function LinkEdHosts$t(){
	if(!confirm('$title ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('remove-cache','$zmd5');
	XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
}
LinkEdHosts$t();
" ;
}
function remove_cache(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM reports_cache WHERE `zmd5`='{$_POST["remove-cache"]}'");
	$tpl=new templates();
	
}

function requeteur_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	squid_stats_default_values();
	$t=$_GET["t"];
	$per["10m"]="10 {minutes}";
	$per["1h"]="{hour}";
	$per["1d"]="{day}";
	
	
	$members["MAC"]="{MAC}";
	$members["USERID"]="{uid}";
	$members["IPADDR"]="{ipaddr}";
	
	
	$q=new postgres_sql();
	$Selectore=$q->fieldSelectore();

	$ip=new networking();
	$MAIN_INTERFACES=$ip->NETWORK_ALL_INTERFACES();
	unset($MAIN_INTERFACES["lo"]);
	while (list ($eth, $none) = each ($MAIN_INTERFACES) ){
		$nic=new system_nic($eth);
		if($nic->enabled==0){continue;}
		$arrayIP[$eth]="$eth: $nic->IPADDR";
	}
	
	$stylelegend="style='vertical-align:top;font-size:18px;padding-top:5px' nowrap";
	$html="<div style='width:98%;margin-bottom:20px' class=form>
	<table style='width:100%'>
	<tr>
		<td $stylelegend class=legend>{from_date}:</td>
		<td style='vertical-align:top;font-size:18px'>". field_date("from-date-$t",$_SESSION["SQUID_STATS_DATE1"],";font-size:18px;width:160px",$Selectore)."
		&nbsp;".Field_text("from-time-$t",$_SESSION["SQUID_STATS_TIME1"],";font-size:18px;width:82px")."</td>
		
	</tr>
	<tr>
		<td $stylelegend class=legend>{to_date}:</td>
		<td style='vertical-align:top;font-size:18px'>". field_date("to-date-$t",$_SESSION["SQUID_STATS_DATE2"],";font-size:18px;width:160px",$Selectore)."
		&nbsp;". Field_text("to-time-$t",$_SESSION["SQUID_STATS_TIME2"],";font-size:18px;width:82px")."</td>
	</tr>
	<tr>
		<td $stylelegend class=legend>{interval}:</td>
		<td style='vertical-align:top;font-size:18px'>". Field_array_Hash($per,"interval-$t","1h","style:font-size:18px;")."</td>
	</tr>	
	<tr>
		<td $stylelegend class=legend>{interface}:</td>
		<td style='vertical-align:top;font-size:18px'>". Field_array_Hash($arrayIP,"interface-$t",null,"style:font-size:18px;")."</td>
	</tr>																	
	<tr style='height:50px'>	
		<td style='vertical-align:top;font-size:18px;' colspan=2 align='right'>". button("{generate_statistics}","Run$t()",22)."</td>
	</tr>
	</table>
	</div>
<script>
function Run$t(){
	var date1=document.getElementById('from-date-$t').value;
	var time1=document.getElementById('from-time-$t').value;
	var date2=document.getElementById('to-date-$t').value
	var time2=document.getElementById('to-time-$t').value;
	var interface=document.getElementById('interface-$t').value;
	var interval=document.getElementById('interval-$t').value;
	Loadjs('$page?query-js=yes&t=$t&container=graph-$t&interface='+interface+'&date1='+date1+'&time1='+time1+'&date2='+date2+'&time2='+time2+'&interval='+interval);

}
</script>
";	
	
echo $tpl->_ENGINE_parse_body($html);
	
	
}



function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$title=null;
	
	echo "<div style='float:right;margin:5px;margin-top:5px'>".button($tpl->_ENGINE_parse_body("{build_the_query}"), "Loadjs('$page?requeteur-js=yes&t=$t')",16)."</div>";
	$content="<center style='margin:50px' id='websites-button-area'>". button("{build_the_query}","Loadjs('$page?requeteur-js=yes&t=$t')",42)."</center>";
	
	
	if($_GET["zmd5"]==null){
		$q=new mysql_squid_builder();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT title,zmd5 FROM reports_cache WHERE report_type='ETHSTATS' ORDER BY zDate DESC LIMIT 0,1"));
	}else{
		$q=new mysql_squid_builder();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT title,zmd5 FROM reports_cache WHERE zmd5='{$_GET["zmd5"]}'"));
		
	}
	
	
	if($ligne["zmd5"]<>null){
		$nextFunction="LoadAjax('ETHSTATS_STATS_MAIN_GRAPH','$page?main=yes&zmd5={$ligne["zmd5"]}&t=$t');";
		$content=null;
		$title="<div style='font-size:30px;margin-bottom:20px'>".
				texttooltip($tpl->javascript_parse_text($ligne["title"]),"{edit}",
				"Loadjs('squid.statistics.edit.report.php?zmd5={$ligne["zmd5"]}&t=$t')")."</div>";
	}

	$html="
	<div id='ETHSTATS_STATS_MAIN_GRAPH'>$content</div>
	<script>
		LoadAjaxTiny('stats-requeteur','$page?stats-requeteur=yes&t=$t');
		$nextFunction
	</script>";
	
	
	
echo $tpl->_ENGINE_parse_body($html);
		
}

function main_page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$zmd5=$_GET["zmd5"];
	$t=time();
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die("DIE " .__FILE__." Line: ".__LINE__);}
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT `title` FROM reports_cache WHERE `zmd5`='$zmd5'"));
	$title="<div style='font-size:26px;margin-bottom:20px'>".
	texttooltip($tpl->javascript_parse_text($ligne["title"]),"{edit}",
			"Loadjs('squid.statistics.edit.report.php?zmd5=$zmd5&t=$t')")."</div>";
	
	
$html="$title<div style='text-align:left' id='button-$t'></div>
	
	</div>	
	
	
	<table style='width:100%'>
	<tr>
		<td colspan=2>		
			<div style='width:1450px;height:550px' id='graph-$zmd5'></div>
			<div style='width:1450px;height:550px' id='graphP-$zmd5'></div>
		</td>
	</tr>
	<tr>
		<td colspan=2><p>&nbsp;</p></td>
	</tr>
	
	
</table>	
	
	
<script>
	LoadAjaxTiny('stats-requeteur','$page?stats-requeteur=yes&t=$t');
	Loadjs('$page?graph1=yes&zmd5=$zmd5');
</script>";	
	
	echo $html;
}
function graph1(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	
	$q=new postgres_sql();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die("DIE " .__FILE__." Line: ".__LINE__);}
	$table="{$zmd5}report";
	
	
	if(!$q->TABLE_EXISTS($table)){
		echo "alert('NO table $table...');UnlockPage();";
		$q->QUERY_SQL("DELETE FROM reports_cache WHERE `zmd5`='$zmd5'");
		return;
	}
	
	
//	SELECT SUM(ip1bytes) as ip1bytes, SUM(ip2bytes) as ip2bytes,ip1,ip2,ip2port,protocol,constartdate
	
	$results=$q->QUERY_SQL("SELECT SUM(rx) as rx,eth,zdate FROM \"$table\" GROUP BY eth,zdate ORDER BY zdate");
	while($ligne=@pg_fetch_assoc($results)){
		$ip1bytes=$ligne["rx"];
		$ip1bytes=$ip1bytes/1024;
		$ip1bytes=$ip1bytes/1024;
		if($GLOBALS["DUMP"]){echo "{$ligne["zdate"]} {$ligne["ip1bytes"]}\n";}
		$xdata[]=$ligne["zdate"];
		$eth=$ligne["eth"];
		$ydata[]=$ip1bytes;
	}
	
	
	$title=$tpl->_ENGINE_parse_body("$eth {NIC_RX} (MB)");
	$timetext="{time}";
	$highcharts=new highcharts();
	$highcharts->container="graph-$zmd5";
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=null;
	$highcharts->LegendSuffix="MB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();
	$page=CurrentPageName();
	echo "Loadjs('$page?graphP=yes&zmd5={$_GET["zmd5"]}&t={$_GET["t"]}')";
	
	
}

function graphP(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$q=new postgres_sql();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die("DIE " .__FILE__." Line: ".__LINE__);}
	$table="{$zmd5}report";
	
	
	if(!$q->TABLE_EXISTS($table)){
		echo "alert('NO table $table...');UnlockPage();";
		$q->QUERY_SQL("DELETE FROM reports_cache WHERE `zmd5`='$zmd5'");
		return;
	}
	
	

	
	$results=$q->QUERY_SQL("SELECT SUM(tx) as rx,eth,zdate FROM \"$table\" GROUP BY eth,zdate ORDER BY zdate");
	while($ligne=@pg_fetch_assoc($results)){
		$ip1bytes=$ligne["rx"];
		$eth=$ligne["eth"];
		$ip1bytes=$ip1bytes/1024;
		$ip1bytes=$ip1bytes/1024;
		if($GLOBALS["DUMP"]){echo "{$ligne["zdate"]} {$ligne["rx"]}\n";}
		$xdata[]=$ligne["zdate"];
		$ydata[]=$ip1bytes;
	}
	
	
	$title=$tpl->_ENGINE_parse_body("$eth {NIC_TX} (MB)");
	$timetext="{time}";
	$highcharts=new highcharts();
	$highcharts->container="graphP-$zmd5";
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=null;
	$highcharts->LegendSuffix="{size}";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();
	$page=CurrentPageName();
	
	
	
}

function graph2(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	
	$q=new postgres_sql();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die("DIE " .__FILE__." Line: ".__LINE__);}
	$table="{$zmd5}report";
	//	SELECT SUM(ip1bytes) as ip1bytes, SUM(ip2bytes) as ip2bytes,ip1,ip2,ip2port,protocol,constartdate
	
	if(!$q->TABLE_EXISTS($table)){
		echo "alert('NO table $table...');UnlockPage();";
		$q->QUERY_SQL("DELETE FROM reports_cache WHERE `zmd5`='$zmd5'");
		return;
	}
	$ProxyGraphsTOPNumber=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyGraphsTOPNumber"));
	if($ProxyGraphsTOPNumber==0){$ProxyGraphsTOPNumber=10;}
	
	$results=$q->QUERY_SQL("SELECT SUM(ip1bytes) as ip1bytes,ip1 FROM \"$table\" GROUP BY ip1 ORDER BY ip1bytes DESC LIMIT $ProxyGraphsTOPNumber");
	while($ligne=@pg_fetch_assoc($results)){
		$size=$ligne["ip1bytes"];
		$size=$size/1024;
		$size=round($size/1024);
		$FAMILYSITE=$ligne["ip1"];
		$TOP_WEBSITES_SIZE[$FAMILYSITE]=$size;
	}
	
	$PieData=$TOP_WEBSITES_SIZE;
	$highcharts=new highcharts();
	$highcharts->container="graph2-$zmd5";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{ipaddr}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{flow} {by_source_ip} (MB)");
	echo $highcharts->BuildChart();
	echo "\n";
	echo "if(document.getElementById('websites-button-area')){document.getElementById('websites-button-area').innerHTML='';}\n";
	echo "LoadAjax('table2-$zmd5','$page?table2=yes&zmd5=$zmd5&t={$_GET["t"]}');\n";

}	

function graph3(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new postgres_sql();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die("DIE " .__FILE__." Line: ".__LINE__);}
	$table="{$zmd5}report";
	
	
	if(!$q->TABLE_EXISTS($table)){
		echo "alert('NO table $table...');UnlockPage();";
		$q->QUERY_SQL("DELETE FROM reports_cache WHERE `zmd5`='$zmd5'");
		return;
	}
	$ProxyGraphsTOPNumber=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyGraphsTOPNumber"));
	if($ProxyGraphsTOPNumber==0){$ProxyGraphsTOPNumber=10;}
	
	$results=$q->QUERY_SQL("SELECT SUM(ip2bytes) as ip2bytes,ip2 FROM \"$table\" GROUP BY ip2 ORDER BY ip2bytes DESC LIMIT $ProxyGraphsTOPNumber");
	while($ligne=@pg_fetch_assoc($results)){
		$size=$ligne["ip2bytes"];
		$size=$size/1024;
		$size=round($size/1024);
		$FAMILYSITE=$ligne["ip2"];
		$TOP_WEBSITES_SIZE[$FAMILYSITE]=$size;
	}
	$title=$tpl->_ENGINE_parse_body("TOP $ProxyGraphsTOPNumber {flow} {by_destination_ip} (MB)");
	$PieData=$TOP_WEBSITES_SIZE;
	$highcharts=new highcharts();
	$highcharts->container="graph3-$zmd5";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{ipaddr}";
	$highcharts->Title=$title;
	echo $highcharts->BuildChart();
	echo "LoadAjax('table3-$zmd5','$page?table3=yes&zmd5=$zmd5&t={$_GET["t"]}');\n";
	
	
}
function graph4(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new postgres_sql();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die("DIE " .__FILE__." Line: ".__LINE__);}
	$table="{$zmd5}report";


	if(!$q->TABLE_EXISTS($table)){
		echo "alert('NO table $table...');UnlockPage();";
		$q->QUERY_SQL("DELETE FROM reports_cache WHERE `zmd5`='$zmd5'");
		return;
	}
	$ProxyGraphsTOPNumber=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyGraphsTOPNumber"));
	if($ProxyGraphsTOPNumber==0){$ProxyGraphsTOPNumber=10;}

	$protocols[1]="ICMP";
	$protocols[6]="TCP";
	$protocols[17]="UDP";
	
	
	$results=$q->QUERY_SQL("SELECT SUM(ip1bytes) as ip1bytes,ip2port,protocol FROM \"$table\" GROUP BY ip2port,protocol ORDER BY ip1bytes DESC LIMIT $ProxyGraphsTOPNumber");
	while($ligne=@pg_fetch_assoc($results)){
		$size=$ligne["ip1bytes"];
		$size=$size/1024;
		$size=round($size/1024);
		$FAMILYSITE="{$ligne["ip2port"]} - {$protocols[$ligne["protocol"]]}";
		$TOP_WEBSITES_SIZE[$FAMILYSITE]=$size;
	}

	$PieData=$TOP_WEBSITES_SIZE;
	$highcharts=new highcharts();
	$highcharts->container="graph4-$zmd5";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{top_ports_protocols}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_ports_protocols}/{size}");
	echo $highcharts->BuildChart();
	echo "LoadAjax('table4-$zmd5','$page?table4=yes&zmd5=$zmd5&t={$_GET["t"]}');\n";


}

function table4(){
	$page=CurrentPageName();
	$q=new postgres_sql();
	$tpl=new templates();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die("DIE " .__FILE__." Line: ".__LINE__);}
	$table="{$zmd5}report";


	$protocols[1]="ICMP";
	$protocols[6]="TCP";
	$protocols[17]="UDP";
	
	
	$ProxyGraphsTOPNumber=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyGraphsTOPNumber"));
	if($ProxyGraphsTOPNumber==0){$ProxyGraphsTOPNumber=10;}

	$html[]="<table style='width:100%'>";
	$html[]=$tpl->_ENGINE_parse_body("<tr><th style='font-size:18px;padding:8px'>{top_ports_protocols}</td>
			<th style='font-size:18px'>{size}</td></tr>");

	$results=$q->QUERY_SQL("SELECT SUM(ip1bytes) as ip1bytes,ip2port,protocol FROM \"$table\" GROUP BY ip2port,protocol ORDER BY ip1bytes DESC LIMIT $ProxyGraphsTOPNumber");
	while($ligne=@pg_fetch_assoc($results)){
		$site="{$ligne["ip2port"]} - {$protocols[$ligne["protocol"]]}";
		$size=$ligne["ip1bytes"];
		$size=FormatBytes($size/1024);
		$html[]="<tr><td style='font-size:18px;padding:8px'>$site</a></td>
		<td style='font-size:18px'>$size</td></tr>";


	}


	$html[]="</table>";
	echo @implode("", $html);


}


function table3(){
	$page=CurrentPageName();
	$q=new postgres_sql();
	$tpl=new templates();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die("DIE " .__FILE__." Line: ".__LINE__);}
	$table="{$zmd5}report";
	
	
	
	$html[]="<table style='width:100%'>";
	$html[]=$tpl->_ENGINE_parse_body("<tr><th style='font-size:18px;padding:8px'>{ipaddr}</td>
			<th style='font-size:18px'>{size}</td></tr>");
	
	$ProxyGraphsTOPNumber=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyGraphsTOPNumber"));
	if($ProxyGraphsTOPNumber==0){$ProxyGraphsTOPNumber=10;}
	
	$results=$q->QUERY_SQL("SELECT SUM(ip2bytes) as ip2bytes,ip2 FROM \"$table\" GROUP BY ip2 ORDER BY ip2bytes DESC LIMIT $ProxyGraphsTOPNumber");
	while($ligne=@pg_fetch_assoc($results)){
		$site=$ligne["ip2"];
		$sitename=gethostbyaddr($site);
		$size=$ligne["ip2bytes"];
		$size=FormatBytes($size/1024);
		
		$js="<a href=\"javascript:blur()\"
		OnClick=\"Loadjs('$page?from-md5-js=$zmd5&ip2=$site')\"
		style='font-size:18px;text-decoration:underline'>";
		
		
		$html[]="<tr><td style='font-size:18px;padding:8px'>$js$site</a> ($sitename)</a></td>
			<td style='font-size:18px'>$size</td></tr>";
		
		
	}
	
	
	$html[]="</table>";
	$html[]="<script>";
	$html[]="Loadjs('$page?graph4=yes&zmd5={$_GET["zmd5"]}&t={$_GET["t"]}')";
	$html[]="</script>";
	echo @implode("", $html);	
	
	
}

function remove_cache_button(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$button_browse=null;
	$button_empty=null;
	$sql="SELECT COUNT(ID) as tcount,report_type FROM `reports_cache` GROUP BY report_type HAVING `report_type`='FLOW'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error_html();}
	
	if(intval($ligne["tcount"])>0){
		$button_browse=$tpl->_ENGINE_parse_body(button("{browse_cache}",
				"Loadjs('squid.statistics.browse-cache.php?report_type=FLOW')",16));
	}
	
	$button_empty=$tpl->_ENGINE_parse_body(button("{empty_cache}","Loadjs('$page?remove-cache-js=yes&zmd5={$_GET["zmd5"]}')",16));
	echo "<table><tr><td nowrap>$button_browse</td><td>&nbsp;</td><td>$button_empty</td></tr></table>";
}








function table2(){
	$page=CurrentPageName();
	$q=new postgres_sql();
	$tpl=new templates();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die("DIE " .__FILE__." Line: ".__LINE__);}
	$table="{$zmd5}report";
	$ProxyGraphsTOPNumber=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyGraphsTOPNumber"));
	if($ProxyGraphsTOPNumber==0){$ProxyGraphsTOPNumber=10;}
	
	$html[]="<table style='width:100%'>";
	$html[]=$tpl->_ENGINE_parse_body("<tr><th style='font-size:18px;padding:8px'>{ipaddr}</td><th style='font-size:18px'>{size}</td></tr>");
	$ProxyGraphsTOPNumber=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyGraphsTOPNumber"));
	if($ProxyGraphsTOPNumber==0){$ProxyGraphsTOPNumber=10;}
	
	$results=$q->QUERY_SQL("SELECT SUM(ip1bytes) as ip1bytes,ip1 FROM \"$table\" GROUP BY ip1 ORDER BY ip1bytes DESC LIMIT $ProxyGraphsTOPNumber");
	
	
	while($ligne=@pg_fetch_assoc($results)){
		$site=$ligne["ip1"];
		$sitename=gethostbyaddr($site);
		$size=$ligne["ip1bytes"];
		$size=FormatBytes($size/1024);
		
		$js="<a href=\"javascript:blur()\"
				OnClick=\"Loadjs('$page?from-md5-js=$zmd5&ip1=$site')\"
				style='font-size:18px;text-decoration:underline'>";
		
		
		$html[]="<tr><td style='font-size:18px;padding:8px'>$js$site</a> ($sitename)</a></td>
			<td style='font-size:18px'>$size</td></tr>";
		
		
	}
	
	
	$html[]="</table>";
	$html[]="<script>";
	$html[]="Loadjs('$page?graph3=yes&zmd5={$_GET["zmd5"]}&t={$_GET["t"]}')";
	$html[]="</script>";
	echo @implode("", $html);
	
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}