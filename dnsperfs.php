<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.ccurl.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.highcharts.inc');

	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["graph1"])){graph1();exit;}
	if(isset($_GET["graph2"])){graph2();exit;}
	
	if(isset($_GET["history"])){history();exit;}
	if(isset($_GET["history-week"])){history_week();exit;}
	
js();


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{dns_performance}");
	echo "YahooWin6('900','$page?tabs=yes','$title')";

}

function tabs(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$q=new mysql();
	$array["status"]='{status}';
	$array["history"]='{history} {this_hour}';
	$array["history-week"]='{history} {this_week}';
	$fontsize=14;


	foreach ($array as $num=>$ligne){
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";

	}
	echo build_artica_tabs($tab, "dnsperf-tabs");
}

function status(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$minperf=$sock->GET_INFO("DNSPerfsPointer");
	if(!is_numeric($minperf)){$minperf=301450;}
	$minperfFloat=$minperf/10000;
	
	$explain=$tpl->_ENGINE_parse_body("{dnsperf_explain}");
	$explain=str_replace("%s", $minperfFloat, $explain);
	$html="<div style='font-size:14px' class=explain>$explain</div>
	<div style='width:97%' class=form>
	";

	$sql="SELECT * FROM dnsperfs";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_events");
	while ($ligne = mysqli_fetch_assoc($results)) {
		$time=strtotime($ligne["zDate"]);
		$took=distanceOfTimeInWords($time,time());
		$html=$html."<div style='font-size:16px;margin:10px'><strong>{$ligne["dnsserver"]}</strong> {response_time} {$ligne["performance"]}ms/{$minperfFloat}ms ({$ligne["percent"]}% ) $took</div>";
	}
			
	
	$html=$html."</div>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function history(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sql="SELECT * FROM dnsperfs";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_events");
	while ($ligne = mysqli_fetch_assoc($results)) {
		$dnsserver=$ligne["dnsserver"];
		$id=md5($dnsserver);
		$f1[]="<div style='width:95%;height:230px' id='$id-1'></div>";
		$f2[]="function FOne$id(){AnimateDiv('$id-1');Loadjs('$page?graph1=yes&container=$id-1&dnsserver=$dnsserver&graph1=yes',true);} setTimeout(\"FOne$id()\",500);";
	}
	
	echo @implode("\n", $f1)."<script>".@implode("\n", $f2)."</script>";
	
}
function history_week(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sql="SELECT * FROM dnsperfs";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_events");
	while ($ligne = mysqli_fetch_assoc($results)) {
		$dnsserver=$ligne["dnsserver"];
		$id=md5($dnsserver.time());
		$f1[]="<div style='width:95%;height:230px' id='$id-2'></div>";
		$f2[]="function FOne$id(){AnimateDiv('$id-2');Loadjs('$page?graph2=yes&container=$id-2&dnsserver=$dnsserver',true);} setTimeout(\"FOne$id()\",500);";
	}

	echo @implode("\n", $f1)."<script>".@implode("\n", $f2)."</script>";

}

function graph1(){
	if(!class_exists("highcharts")){return ;}
	$tpl=new templates();
	$dnsserver=$_GET["dnsserver"];
	$q=new mysql();

	$sql="SELECT AVG(percent) as value,min,tdate
	FROM (SELECT DATE_FORMAT(zDate,'%Y-%m-%d %H:00:00') as tdate,DATE_FORMAT(zDate,'%i') as `min`, 
	dnsserver,percent FROM `dnsperfs_week` WHERE zDate>DATE_SUB(NOW(),INTERVAL 1 HOUR) AND dnsserver='$dnsserver') as t
	GROUP BY min,tdate ORDER BY tdate,min";

	//echo "<H1>$sql</H1>";
	$title="$dnsserver: {dnsperf_this_hour}";
	$timetext="{minutes}";
	$results=$q->QUERY_SQL($sql,"artica_events");
	
	$count=mysqli_num_rows($results);
	if($count<2){
			$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d %H:00:00') as tdate,DATE_FORMAT(zDate,'%i') as `min`,
			dnsserver, AVG(percent) as value FROM `dnsperfs_week` GROUP BY `min`,dnsserver HAVING 
			tdate=DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 1 HOUR),'%Y-%m-%d %H:00:00') AND dnsserver='$dnsserver' ORDER BY `min`";
		
	}
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$xdata[]=$ligne["min"];
		$ydata[]=$ligne["value"];
		$tdate=date("H",strtotime($ligne["tdate"]));
		
	}
	
	$timetext="{minutes}";
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{percent}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix="{$tdate}h";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->LegendSuffix="%";
	$highcharts->datas=array($dnsserver=>$ydata);
	$highcharts->OnErrorEvent=$sql;
	echo $highcharts->BuildChart();

}
function graph2(){
	if(!class_exists("highcharts")){return ;}
	$tpl=new templates();
	$dnsserver=$_GET["dnsserver"];
	$q=new mysql();
	
	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as tdate,DATE_FORMAT(zDate,'%d') as tday,DATE_FORMAT(zDate,'%H') as `min`,dnsserver,
	AVG(percent) as value FROM `dnsperfs_week` GROUP BY `min`,dnsserver,tday
	HAVING tdate<DATE_FORMAT(NOW(),'%Y-%m-%d') AND dnsserver='$dnsserver' ORDER BY tdate,`min`";
	
	$title="$dnsserver: {this_week}";
	$timetext="{minutes}";
	$results=$q->QUERY_SQL($sql,"artica_events");
	while ($ligne = mysqli_fetch_assoc($results)) {
		$xdata[]=$ligne["tday"]."/".$ligne["min"]."h";
		$ydata[]=round($ligne["value"]);
	
	}
	
	$timetext="{hours}";
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{percent}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix="{day}";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->LegendSuffix="%";
	$highcharts->datas=array($dnsserver=>$ydata);
	echo $highcharts->BuildChart();	
	
}