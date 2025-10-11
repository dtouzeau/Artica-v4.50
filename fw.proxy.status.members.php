<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__).'/ressources/charts.php');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["users-hour"])){users_hours();exit;}
if(isset($_GET["users-day"])){users_day();exit;}
if(isset($_GET["users-week"])){users_week();exit;}
if(isset($_GET["users-month"])){users_month();exit;}
js();

function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog6("{real_members}", "$page?popup=yes",900);
}

function popup(){
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$tpl=new template_admin();
	$html=array();
	
	$now=date("Y-m-d H:00:00");
	$sql="SELECT avg(users) as users,HOUR(zdate) as thour, MINUTE(zdate) as tmin from (SELECT users,zdate FROM lic_proxy_day WHERE zdate>'$now') as t GROUP BY thour,tmin";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
	
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$MAIN["xdata"][]=$ligne["thour"].":".$ligne["tmin"];
		$MAIN["ydata"][]=$ligne["users"];
		$final[$ligne["thour"].":".$ligne["tmin"]]=intval($ligne["users"]);
		
	}
	$html[]="<!-- users-hour: From $now, ".count($final)." elements -->";
	if(count($final)>1){
		$data=urlencode(serialize($MAIN));
		$html[]="<div id='users-hour' style='with:1450;height:350px'></div>";
		$js[]="Loadjs('$page?users-hour=yes&data=$data');";
	}

	
//-----------------------------------------------------------------------------------------------------	
	
	$now=date("Y-m-d 00:00:00");
	$sql="SELECT AVG(users) as users,HOUR(zdate) as thour FROM (SELECT users,zdate from lic_proxy_day WHERE zdate>'$now') as t GROUP BY thour";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
	$final=array();
	$MAIN=array();
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$MAIN["xdata"][]=$ligne["thour"].":00";
		$MAIN["ydata"][]=$ligne["users"];
		$final[$ligne["thour"].":00"]=intval($ligne["users"]);
	
	}
	
	
	if(count($final)>1){
		$data=urlencode(serialize($MAIN));
		$html[]="<div id='users-day' style='with:1450;height:350px'></div>";
		$js[]="Loadjs('$page?users-day=yes&data=$data');";
	}
//-----------------------------------------------------------------------------------------------------	
	
	$now=date("Y-m-d 00:00:00",strtotime("last week monday"));
	$sql="SELECT AVG(users) as users,DAY(zdate) as tday,HOUR(zdate) as thour FROM (SELECT users,zdate from lic_proxy_day WHERE zdate>'$now') as t GROUP BY tday,thour";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
	$final=array();
	$MAIN=array();
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$html[]="<!-- users-week: {$ligne["tday"]} {$ligne["thour"]}:00 {$ligne["users"]} -->";
		$MAIN["xdata"][]=$ligne["tday"]." ".$ligne["thour"].":00";
		$MAIN["ydata"][]=$ligne["users"];
		$final[$ligne["thour"].":00"]=intval($ligne["users"]);
	
	}
	
	$html[]="<!-- users-week: From $now, ".count($final)." elements -->";
	if(count($final)>1){
		$data=serialize($MAIN);
		@file_put_contents(PROGRESS_DIR."/users-week.data", $data);
		$html[]="<div id='users-week' style='with:1450;height:350px'></div>";
		$js[]="Loadjs('$page?users-week=yes');";
	}	
//-----------------------------------------------------------------------------------------------------	
	$now=date("Y-m-d 00:00:00",strtotime( 'first day of ' . date( 'F Y')));
	$sql="SELECT AVG(users) as users,DAY(zdate) as tday FROM (SELECT users,zdate from lic_proxy_day WHERE zdate>'$now') as t GROUP BY tday";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
	$final=array();
	$MAIN=array();
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$MAIN["xdata"][]=$ligne["tday"];
		$MAIN["ydata"][]=$ligne["users"];
		$final[$ligne["tday"]]=intval($ligne["users"]);
	
	}
	
	$html[]="<!-- users-month: From $now, ".count($final)." elements -->";
	if(count($final)>1){
		$data=serialize($MAIN);
		@file_put_contents(PROGRESS_DIR."/users-month.data", $data);
		$html[]="<div id='users-month' style='with:1450;height:350px'></div>";
		$js[]="Loadjs('$page?users-month=yes');";
	}
	
	echo @implode("\n", $html)."<script>".@implode("\n", $js)."</script>";
	
}

function users_month(){
	$MAIN=unserialize(@file_get_contents(PROGRESS_DIR."/users-month.data"));
	$tpl=new templates();
	$page=CurrentPageName();
	$title="{members}/{this_month}";
	$timetext="{day}";
	$highcharts=new highcharts();
	$highcharts->container="users-month";
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{members}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{day}: ');
	$highcharts->LegendSuffix="";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{members}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();
	
}

function users_week(){

	$MAIN=unserialize(@file_get_contents(PROGRESS_DIR."/users-week.data"));
	$tpl=new templates();
	$page=CurrentPageName();
	$title="{members}/{this_week}";
	$timetext="{day}/{hour}";
	$highcharts=new highcharts();
	$highcharts->container="users-week";
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{members}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{time}: ');
	$highcharts->LegendSuffix="";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{members}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();
}

function users_day(){

	$MAIN=unserialize($_GET["data"]);
	$tpl=new templates();
	$page=CurrentPageName();
	$title="{members}/{this_day}";
	$timetext="{hour}";
	$highcharts=new highcharts();
	$highcharts->container="users-day";
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{members}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{time}: ');
	$highcharts->LegendSuffix="";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{members}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();	
}

function users_hours(){
	
	$MAIN=unserialize($_GET["data"]);
	$tpl=new templates();
	$page=CurrentPageName();
	$title="{members}/{this_hour}";
	$timetext="{minutes}";
	$highcharts=new highcharts();
	$highcharts->container="users-hour";
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{members}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{time}: ');
	$highcharts->LegendSuffix="";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{members}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();
}