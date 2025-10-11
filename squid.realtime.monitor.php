<?php
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/charts.php');
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.os.system.tools.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');

if(isset($_GET["RTTTCOURBE4"])){RTTTCOURBE4();exit;}
if(isset($_GET["RTTTCOURBE3"])){RTTTCOURBE3();exit;}
if(isset($_GET["RTTTCOURBE2"])){RTTTCOURBE2();exit;}
if(isset($_GET["RTTTCOURBE"])){RTTTCOURBE();exit;}
if(isset($_GET["popup"])){popup();exit;}
js();
function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{realtime}");
	$html="YahooWin5('1400','$page?popup=yes','$title')";
	echo $html;

}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	$html="
	<input type='hidden' id='RTTTCOURBESTAMP' value='0'>
	<input type='hidden' id='RTTTCOURBESWITCH' value='0'>
	<div id='RTTTCOURBE' style='width:1350px;height:220px'></div>		
	<div id='RTTTCOURBE2' style='width:1350px;height:220px'></div>	
	<table style='width:100%'>
	<tr>
		<td><div id='RTTTCOURBE3' style='width:675px;height:550px'></div></td>
		<td><div id='RTTTCOURBE4' style='width:675px;height:550px'></div></td>
	</tr>
	</table>	
			
			
			
	<script>
		Loadjs('$page?RTTTCOURBE=yes');
		
		function RTTTCOURBECOUNT(){
			if(!YahooWin5Open()){ return ;}
			var timestamp=parseInt(document.getElementById('RTTTCOURBESTAMP').value);
			if(timestamp==0){
				document.getElementById('RTTTCOURBESTAMP').value=1;
				setTimeout('RTTTCOURBECOUNT()',1000);
			  	return;
			}
			timestamp=timestamp+1;
			
			if(timestamp<8){
				document.getElementById('RTTTCOURBESTAMP').value=timestamp;
				setTimeout('RTTTCOURBECOUNT()',1000);
			  	return;
			}
			
			document.getElementById('RTTTCOURBESTAMP').value=1;
			 
			
			if( parseInt(document.getElementById('RTTTCOURBESWITCH').value)==0){
				document.getElementById('RTTTCOURBESWITCH').value=1;
			}else{
				document.getElementById('RTTTCOURBESWITCH').value=0;
			}
			
			
			Loadjs('$page?RTTTCOURBE=yes');
		
		}
		
	</script>
	";
	
	echo $html;
	
}

function RTTTCOURBE(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new postgres_sql();
	$curtime=date("YmdH");
	$tablename="access_$curtime";
	$q=new postgres_sql();
	
	$results=$q->QUERY_SQL("SELECT SUM(size) AS size, EXTRACT(MINUTE FROM zdate) as zdate FROM $tablename GROUP BY EXTRACT(MINUTE FROM zdate) ORDER BY EXTRACT(MINUTE FROM zdate)");
	
	while($ligne=@pg_fetch_array($results)){
		$xdata[]=$ligne["zdate"];
		$ligne["size"]=$ligne["size"]/1024;
		$ydata[]=round($ligne["size"]/1024,2);
	}
	
	
	$title="{downloaded_flow} (MB) {this_hour}";
	$timetext="{minutes}";
	$highcharts=new highcharts();
	$highcharts->container="RTTTCOURBE";
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{minutes}: ');
	$highcharts->LegendSuffix="MB";;
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();
	echo "Loadjs('$page?RTTTCOURBE2=yes');";
	
}
function RTTTCOURBE2(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new postgres_sql();
	$curtime=date("YmdH");
	$tablename="access_$curtime";
	$q=new postgres_sql();

	$results=$q->QUERY_SQL("SELECT SUM(rqs) AS rqs, EXTRACT(MINUTE FROM zdate) as zdate FROM $tablename GROUP BY EXTRACT(MINUTE FROM zdate) ORDER BY EXTRACT(MINUTE FROM zdate)");

	while($ligne=@pg_fetch_array($results)){
		$xdata[]=$ligne["zdate"];
		$ydata[]=$ligne["rqs"];
	}

	$title="{requests} {this_hour}";
	$timetext="{minutes}";
	$highcharts=new highcharts();
	$highcharts->container="RTTTCOURBE2";
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{minutes}: ');
	$highcharts->LegendSuffix="RQS";;
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("RQS"=>$ydata);
	echo $highcharts->BuildChart();
	echo "Loadjs('$page?RTTTCOURBE3=yes&switch='+document.getElementById('RTTTCOURBESWITCH').value);";

}

function RTTTCOURBE3(){
	if($_GET["switch"]==1){RTTTCOURBE3_1();exit;}
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new postgres_sql();
	$curtime=date("YmdH");
	$tablename="access_$curtime";
	$q=new postgres_sql();

	$results=$q->QUERY_SQL("SELECT SUM(size) AS size, familysite FROM $tablename GROUP BY familysite ORDER BY size DESC LIMIT 15");

	while($ligne=@pg_fetch_array($results)){
		$size=$ligne["size"]/1024;
		$size=round($size/1024,2);
		$MAIN[$ligne["familysite"]]=$size;
	}


	$highcharts=new highcharts();
	$highcharts->container="RTTTCOURBE3";
	$highcharts->PieDatas=$MAIN;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{top_websites_by_size}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_websites_by_size} (MB)");
	echo $highcharts->BuildChart();
	echo "Loadjs('$page?RTTTCOURBE4=yes&switch='+document.getElementById('RTTTCOURBESWITCH').value);";

}
function RTTTCOURBE3_1(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new postgres_sql();
	$curtime=date("YmdH");
	$tablename="access_$curtime";
	$q=new postgres_sql();

	$results=$q->QUERY_SQL("SELECT SUM(size) AS size, userid FROM $tablename GROUP BY userid ORDER BY size DESC LIMIT 15");

	while($ligne=@pg_fetch_array($results)){
		$size=$ligne["size"]/1024;
		$size=round($size/1024,2);
		$MAIN[$ligne["userid"]]=$size;
	}


	$highcharts=new highcharts();
	$highcharts->container="RTTTCOURBE3";
	$highcharts->PieDatas=$MAIN;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{top_members_by_size}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_members_by_size} (MB)");
	echo $highcharts->BuildChart();
	echo "Loadjs('$page?RTTTCOURBE4=yes&switch='+document.getElementById('RTTTCOURBESWITCH').value);";

}
function RTTTCOURBE4(){
	if($_GET["switch"]==1){RTTTCOURBE4_1();exit;}
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new postgres_sql();
	$curtime=date("YmdH");
	$tablename="access_$curtime";
	$q=new postgres_sql();

	$results=$q->QUERY_SQL("SELECT SUM(rqs) AS rqs, familysite FROM $tablename GROUP BY familysite ORDER BY rqs DESC LIMIT 15");

	while($ligne=@pg_fetch_array($results)){
		$MAIN[$ligne["familysite"]]=$ligne["rqs"];
	}


	$highcharts=new highcharts();
	$highcharts->container="RTTTCOURBE4";
	$highcharts->PieDatas=$MAIN;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{top_websites_by_hits}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_websites_by_hits}");
	echo $highcharts->BuildChart();
	echo "\nsetTimeout('RTTTCOURBECOUNT()',2000);\n";

}
function RTTTCOURBE4_1(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new postgres_sql();
	$curtime=date("YmdH");
	$tablename="access_$curtime";
	$q=new postgres_sql();

	$results=$q->QUERY_SQL("SELECT SUM(size) AS size, ipaddr FROM $tablename GROUP BY ipaddr ORDER BY size DESC LIMIT 15");

	while($ligne=@pg_fetch_array($results)){
		$size=$ligne["size"]/1024;
		$size=round($size/1024,2);
		$MAIN[$ligne["ipaddr"]]=$size;
	}


	$highcharts=new highcharts();
	$highcharts->container="RTTTCOURBE4";
	$highcharts->PieDatas=$MAIN;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{top_members_by_size}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_members_by_size} {ipaddr} (MB)");
	echo $highcharts->BuildChart();
	echo "\nsetTimeout('RTTTCOURBECOUNT()',2000);\n";

}
