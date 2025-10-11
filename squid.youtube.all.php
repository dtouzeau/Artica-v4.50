<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["title_array"]["size"]="{downloaded_flow}";
	$GLOBALS["title_array"]["req"]="{requests}";	
	
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.highcharts.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die("no rights");}	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["graph"])){graph();exit;}
	
	
js();



function js(){
	
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{videos_number}");
	echo "YahooWin2('880','$page?popup=yes','$title')";
	
}

function popup(){
	$t=time();
	$page=CurrentPageName();
	echo "
	<div id='container-$t' style='width:850;height:450px'></div>
	<script>
		Loadjs('$page?graph=yes&container=container-$t');
	</script>		
			
	";
}

function graph(){
	
	
	$q=new mysql_squid_builder();
	$sql="SELECT SUM(hits) as hits,zDate FROM youtube_dayz GROUP BY zDate ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);
	
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$ligne["zDate"]=str_replace(date("Y")."-", "", $ligne["zDate"]);
		$xdata[]=$ligne["zDate"];
		$ydata[]=$ligne["hits"];
		
		if($GLOBALS["VERBOSE"]){echo "\"{$ligne["zDate"]}\" = {$ligne["hits"]}<br>\n";}
	}
	
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{downloaded_videos}/{day}";
	$highcharts->yAxisTtitle="{videos}";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{videos}"=>$ydata);
	echo $highcharts->BuildChart();
	
}
