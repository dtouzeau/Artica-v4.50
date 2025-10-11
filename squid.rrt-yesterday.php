<?php

if($argv[1]=="--verbose"){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["verbose"])){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["AS_ROOT"]=false;
if(function_exists("posix_getuid")){
	if(posix_getuid()==0){
		$GLOBALS["AS_ROOT"]=true;
		include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
		include_once(dirname(__FILE__)."/framework/frame.class.inc");
		include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
		include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
		include_once(dirname(__FILE__)."/framework/class.settings.inc");
	}}

	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.html.pages.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.highcharts.inc');
	include_once('ressources/class.rrd.inc');
	
	$users=new usersMenus();
	if(!$GLOBALS["AS_ROOT"]){if(!$users->AsSquidAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}}
	if(isset($_GET["rtt-hour"])){rtt_hour();exit;}
	if(isset($_GET["rwt-hour"])){rtw_hour();exit;}
	if(isset($_GET["rwi-hour"])){rti_hour();exit;}
	if(isset($_GET["rwu-hour"])){rtu_hour();exit;}
	
	if(isset($_GET["popup"])){start();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}

	
	start();

function start(){
	$page=CurrentPageName();
	$time=time();
	$f1[]="<div style='width:1000px;heigh:300px' id='$time-2'></div><hr>";
	$f1[]="<div style='width:1000px;heigh:500px' id='$time-3'></div><hr>";
	$f1[]="<div style='width:1000px;heigh:500px' id='$time-4'></div><hr>";
	$f1[]="<div style='width:1000px;heigh:500px' id='$time-5'></div><hr>";
	$f2[]="function FDeux$time(){
		LoadjsSilent('$page?rtt-hour=yes&container=$time-2&container2=$time-3&container3=$time-4&container4=$time-5',false);
		LoadjsSilent('$page?rwt-hour=yes&container=$time-3',false);
		LoadjsSilent('$page?rwi-hour=yes&container=$time-4',false);
		LoadjsSilent('$page?rwu-hour=yes&container=$time-5',false);
	}
	setTimeout(\"FDeux$time()\",500);";
	
	$html=@implode("\n", $f1)."<script>".@implode("\n", $f2)."</script>";
	echo $html;
	
	
}

function rtt_hour(){

$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$xtime=$q->HIER_TIME();
	$table="RTTD_".date("Ymd",$q->HIER_TIME());
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT zHour as MIN ,SUM(`size`) as `SIZE` FROM `$table` GROUP BY MIN ORDER BY MIN");
	$CountRow=mysqli_num_rows($results);

	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$size=intval($ligne["SIZE"]);
		$min=$ligne["MIN"];
		if(strlen($min)==1){$min="0{$min}";}
		$min="{$min}:00";
		$xdata[]=$min;
		$size=$size/1024;
		$size=$size/1024;
		$ydata[]=round($size,2);
		if($GLOBALS["VERBOSE"]){echo "<li>$min = {$ligne["SIZE"]}Bytes ,{$size}MB</li>";}
	}

	$t=time();
	$title="{realtime_flow}/{hour} (MB) ".$q->time_to_date($xtime);
	$timetext="{minutes}";
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->_ENGINE_parse_body("{hour} ");
	$highcharts->LegendSuffix="MB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{size}"=>$ydata);
	$highcharts->SetRefreshCallBack="Refresh$t";
	$highcharts->RemoveLock=true;
	$charts=$highcharts->BuildChart();
	
	$script="

	


$charts
";
	echo $script;
}

function rtw_hour(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$table="RTTD_".date("Ymd",$q->HIER_TIME());
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT SUM(size) AS SIZE,sitename FROM $table GROUP BY sitename ORDER BY SIZE DESC LIMIT 0,10");
	$CountRow=mysqli_num_rows($results);
	

	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$ligne["SIZE"]=$ligne["SIZE"]/1024;
		$ligne["SIZE"]=$ligne["SIZE"]/1024;
		
		$PieData[$ligne["sitename"]]=$ligne["SIZE"];
		
	}


$page=CurrentPageName();

$tpl=new templates();
$highcharts=new highcharts();
$highcharts->TitleFontSize="14px";
$highcharts->AxisFontsize="12px";
$highcharts->container=$_GET["container"];
$highcharts->PieDatas=$PieData;
$highcharts->ChartType="pie";
$highcharts->PiePlotTitle="{size} MB";
$highcharts->Title=$tpl->_ENGINE_parse_body("{top_websites} MB");
$highcharts->RemoveLock=true;
echo $highcharts->BuildChart();
}
function rti_hour(){

	$page=CurrentPageName();
	$date=date("YmdH");
	$table="RTTH_{$date}";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT SUM(size) AS SIZE,ipaddr FROM $table GROUP BY ipaddr 
			ORDER BY SIZE DESC LIMIT 0,10");
	$CountRow=mysqli_num_rows($results);


	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$ligne["SIZE"]=$ligne["SIZE"]/1024;
		$ligne["SIZE"]=$ligne["SIZE"]/1024;

		$PieData[$ligne["ipaddr"]]=$ligne["SIZE"];

	}


	$page=CurrentPageName();

	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{size} MB";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_ipaddr} MB");
	$highcharts->RemoveLock=true;
	echo $highcharts->BuildChart();
}

function rtu_hour(){

	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$table="RTTD_".date("Ymd",$q->HIER_TIME());
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT SUM(size) AS SIZE,uid FROM $table GROUP BY uid HAVING LENGTH(uid)>0 ORDER BY SIZE DESC LIMIT 0,10");
	$CountRow=mysqli_num_rows($results);

	if(mysqli_num_rows($results)<2){
		$date=date("YmdH",time()-60);
		$table="RTTH_{$date}";
		$results=$q->QUERY_SQL("SELECT SUM(size) AS SIZE,uid FROM $table GROUP BY uid HAVING LENGTH(uid)>0 ORDER BY SIZE DESC LIMIT 0,10");

	}

	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$ligne["SIZE"]=$ligne["SIZE"]/1024;
		$ligne["SIZE"]=$ligne["SIZE"]/1024;
		$PieData[$ligne["uid"]]=$ligne["SIZE"];

	}


	$page=CurrentPageName();

	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{size} MB";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_members} MB");
	$highcharts->RemoveLock=true;
	echo $highcharts->BuildChart();
}




