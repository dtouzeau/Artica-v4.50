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

	
	$users=new usersMenus();
	if(!$GLOBALS["AS_ROOT"]){if(!$users->AsSquidAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}}
	if(isset($_GET["rtt-hour"])){rtt_hour();exit;}
	if(isset($_GET["rwt-hour"])){rtw_hour();exit;}
	if(isset($_GET["rwi-hour"])){rti_hour();exit;}
	if(isset($_GET["rwu-hour"])){rtu_hour();exit;}
	
	if(isset($_GET["popup"])){start();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
js();





	function tabs(){
		$t=time();
		$page=CurrentPageName();
		$users=new usersMenus();
		$tpl=new templates();
		$array["popup"]='{panel}';
		$array["members"]='{members}';
		
		$q=new mysql_squid_builder();
		$table="RTTD_".date("Ymd",$q->HIER_TIME());
		if($q->TABLE_EXISTS($table)){
			$array["yesterday"]='{yesterday}';
		}
	
		$page=CurrentPageName();
	
		foreach ($array as $num=>$ligne){
	
			if($num=="members"){
				$html[]= "<li style='font-size:18px'><a href=\"squid.rrt-members.php\"><span>$ligne</span></a></li>\n";
				continue;
			}
	
			if($num=="yesterday"){
				$html[]= "<li style='font-size:18px'><a href=\"squid.rrt-yesterday.php\"><span>$ligne</span></a></li>\n";
				continue;
			}			
			
	
			$html[]= "<li style='font-size:18px'><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n";
		}
	
	
		echo build_artica_tabs($html, "squid_rrtt_tabs",1100)."
			<script>LeftDesign('statistics-white-256-opac20.png');</script>";
	
	}





function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{realtime_flow}");
	echo "YahooWin6(1150,'$page?tabs=yes','$title')";
	
	
}


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
	$date=date("YmdH");
	$table="RTTH_{$date}";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT DATE_FORMAT(FROM_UNIXTIME(xtime),'%i') as MIN ,SUM(`size`) as `SIZE` FROM `$table` GROUP BY MIN ORDER BY MIN");
	$CountRow=mysqli_num_rows($results);

	if(mysqli_num_rows($results)<2){
		$date=date("YmdH",time()-60);
		$table="RTTH_{$date}";
		$results=$q->QUERY_SQL("SELECT DATE_FORMAT(FROM_UNIXTIME(xtime),'%i') as MIN ,SUM(`size`) as `SIZE` FROM `$table` GROUP BY MIN ORDER BY MIN");

	}

	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$size=intval($ligne["SIZE"]);
		$min=$ligne["MIN"];
		$xdata[]=$min;
		$ydata[]=round($size/1024,2);
		if($GLOBALS["VERBOSE"]){echo "<li>$min = {$ligne["SIZE"]}Bytes ,{$size}MB</li>";}
	}

	$t=time();
	$title="{realtime_flow}/{minute} (KB)";
	$timetext="{minutes}";
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=date("H")."h";
	$highcharts->LegendSuffix="KB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{size}"=>$ydata);
	$highcharts->SetRefreshCallBack="Refresh$t";
	$highcharts->RemoveLock=true;
	$charts=$highcharts->BuildChart();
	
	$script="

	
function Refresh$t(series){
	setTimeout(\"Refresh2$t()\",9000);
	
}

function Refresh2$t(){	
	if(! YahooWin6Open() ){return;}
	LoadjsSilent('$page?rtt-hour=yes&container={$_GET["container"]}&container2={$_GET["container2"]}&container3={$_GET["container3"]}&container4={$_GET["container4"]}',false);
	LoadjsSilent('$page?rwt-hour=yes&container={$_GET["container2"]}',false);
	LoadjsSilent('$page?rwi-hour=yes&container={$_GET["container3"]}',false);
	LoadjsSilent('$page?rwu-hour=yes&container={$_GET["container4"]}',false);		
}
$charts
";
	echo $script;
}

function rtw_hour(){
	
	$page=CurrentPageName();
	$date=date("YmdH");
	$table="RTTH_{$date}";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT SUM(size) AS SIZE,sitename FROM $table GROUP BY sitename ORDER BY SIZE DESC LIMIT 0,10");
	$CountRow=mysqli_num_rows($results);
	
	if(mysqli_num_rows($results)<2){
		$date=date("YmdH",time()-60);
		$table="RTTH_{$date}";
		$results=$q->QUERY_SQL("SELECT SUM(size) AS SIZE,sitename FROM $table GROUP BY sitename ORDER BY SIZE DESC LIMIT 0,10");
	
	}
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
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
$highcharts->PiePlotTitle="{size} KB";
$highcharts->Title=$tpl->_ENGINE_parse_body("{top_websites} KB");
$highcharts->RemoveLock=true;
echo $highcharts->BuildChart();
}
function rti_hour(){

	$page=CurrentPageName();
	$date=date("YmdH");
	$table="RTTH_{$date}";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT SUM(size) AS SIZE,ipaddr FROM $table GROUP BY ipaddr ORDER BY SIZE DESC LIMIT 0,10");
	$CountRow=mysqli_num_rows($results);

	if(mysqli_num_rows($results)<2){
		$date=date("YmdH",time()-60);
		$table="RTTH_{$date}";
		$results=$q->QUERY_SQL("SELECT SUM(size) AS SIZE,ipaddr FROM $table GROUP BY ipaddr ORDER BY SIZE DESC LIMIT 0,10");

	}

	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
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
	$highcharts->PiePlotTitle="{size} KB";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_ipaddr} KB");
	$highcharts->RemoveLock=true;
	echo $highcharts->BuildChart();
}

function rtu_hour(){

	$page=CurrentPageName();
	$date=date("YmdH");
	$table="RTTH_{$date}";
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
	$highcharts->PiePlotTitle="{size} KB";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_members} KB");
	$highcharts->RemoveLock=true;
	echo $highcharts->BuildChart();
}




