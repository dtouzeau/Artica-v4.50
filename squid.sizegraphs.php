<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');

	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["members"])){members();exit;}
	if(isset($_GET["graph1"])){graph1();exit;}
	if(isset($_GET["graph2"])){graph2();exit;}
	if(isset($_GET["graph3"])){graph3();exit;}
	if(isset($_GET["graph4"])){graph4();exit;}
	if(isset($_GET["graph5"])){graph5();exit;}
	if(isset($_GET["graph6"])){graph6();exit;}
	
	
	if(isset($_GET["websites"])){websites();exit;}
	
	if(isset($_GET["timejs"])){timejs();exit;}
js();

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["popup"]='{flow}';
	$array["websites"]='{websites}';
	$array["members"]='{members}';

	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	
	$t=time();
	foreach ($array as $num=>$ligne){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=ye&t=$t\" style='font-size:16px'><span>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "squid_size_graphs");
	
	
}


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$useragent_database=$tpl->_ENGINE_parse_body("{downloaded_size_this_hour}");
	$html="YahooWin5('1090','$page?tabs=yes','$useragent_database');";
	echo $html;
}

function websites(){
	$page=CurrentPageName();
	$t=time();
	$CurTime=date("YmdH");
	$CurDay=date("Ymd");
	$q=new mysql_squid_builder();
	$hour_table="quotahours_{$CurTime}";
	$day_table="quotaday_{$CurDay}";
	
	if($q->TABLE_EXISTS($hour_table)){
		$hour_div="<div id='graph3-$t' style='width:1000px'></div>";
		$hour_js="setTimeout(\"FHour$t()\",600);";
	}
	
	if($q->TABLE_EXISTS($day_table)){
		$day_div="<div id='graph4-$t' style='width:1000px'></div>";
		$day_js="setTimeout(\"FDay$t()\",600);";
	}
		
	
	$html="<div style='float:right'>". imgtootltip("refresh-24.png","{refresh}","RefreshTab('squid_size_graphs')")."</div>
$hour_div
$day_div
<span id='log-$t'></span>
<script>
	function FHour$t(){
		AnimateDiv('graph3-$t');
		Loadjs('$page?graph3=yes&container=graph3-$t&t=$t',true);
	}
	function FDay$t(){
		AnimateDiv('graph4-$t');
		Loadjs('$page?graph4=yes&container=graph4-$t&t=$t',true);
	}
	
$hour_js
$day_js
</script>
	";
	echo $html;	
	
}

function members(){
	$page=CurrentPageName();
	$t=time();
	$CurTime=date("YmdH");
	$CurDay=date("Ymd");
	$q=new mysql_squid_builder();
	$hour_table="quotahours_{$CurTime}";
	$day_table="quotaday_{$CurDay}";
	
	if($q->TABLE_EXISTS($hour_table)){
		$hour_div="<div id='graph3-$t' style='width:1000px'></div>";
		$hour_js="setTimeout(\"FHour$t()\",600);";
	}
	
	if($q->TABLE_EXISTS($day_table)){
		$day_div="<div id='graph4-$t' style='width:1000px'></div>";
		$day_js="setTimeout(\"FDay$t()\",600);";
	}
	
	
	$html="<div style='float:right'>". imgtootltip("refresh-24.png","{refresh}","RefreshTab('squid_size_graphs')")."</div>
	$hour_div
	$day_div
	<span id='log-$t'></span>
	<script>
	function FHour$t(){
	AnimateDiv('graph3-$t');
	Loadjs('$page?graph5=yes&container=graph3-$t&t=$t',true);
	}
	function FDay$t(){
	AnimateDiv('graph4-$t');
	Loadjs('$page?graph6=yes&container=graph4-$t&t=$t',true);
	}
	
	$hour_js
	$day_js
	</script>
	";
	echo $html;
		
	
}


function popup(){
	$page=CurrentPageName();
	$t=time();
	$CurDay=date("Ymd");
	$q=new mysql_squid_builder();
	$day_table="{$CurDay}_gsize";
	
	if($q->TABLE_EXISTS($day_table)){
		$day_div="<div id='graph2-$t' style='width:1000px'></div>";
		$js_graph2="setTimeout(\"FDay$t()\",600);";
	}
	
	$html="
	<div style='float:right'>". imgtootltip("refresh-24.png","{refresh}","RefreshTab('squid_size_graphs')")."</div>		
	<div id='graph1-$t' style='width:1000px'></div>
	$day_div
	<span id='log-$t'></span>
	<script>
		function FTrois$t(){
			AnimateDiv('graph1-$t');
			Loadjs('$page?graph1=yes&container=graph1-$t&t=$t',true);
		} 
		function FDay$t(){
			AnimateDiv('graph2-$t');
			Loadjs('$page?graph2=yes&container=graph2-$t&t=$t',true);
		}		
		
		
	setTimeout(\"FTrois$t()\",600);
	$js_graph2
	</script>
	";
	echo $html;
	
}

function graph1(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$title="{downloaded_size_this_hour}";
	$timetext="{minutes}";
	
	$q=new mysql();
	$cacheFile=PROGRESS_DIR."/INTERFACE_LOAD_AVG3.db";
	$ARRAY=unserialize(@file_get_contents($cacheFile));
	
	
	$xdata=$ARRAY[0];
	$ydata=$ARRAY[1];
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{size} (MB)";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();
	
	
}

function graph2(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$CurDay=date("Ymd");
	$q=new mysql_squid_builder();
	$day_table="{$CurDay}_gsize";
	header("content-type: application/x-javascript");
	$title="{downloaded_size_this_day}";
	$timetext="{hour}";
	

	$sql="SELECT SUM(size) as `size`,`hour` FROM $day_table GROUP BY `hour` ORDER BY `hour`";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$size=$ligne["size"];
		$size=$size/1024;
		$size=round($size/1024);
			$ydata[]=$size;
			$xdata[]=$ligne["hour"];
	}
	
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{size} (MB)";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();	
	
}
function graph3(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$CurTime=date("YmdH");
	$CurDay=date("Ymd");
	$q=new mysql_squid_builder();
	$hour_table="quotahours_{$CurTime}";
	$day_table="quotaday_{$CurDay}";

	$title="{top_websites} {downloaded_size_this_hour} (KB)";
	$timetext="{hour}";
	
	
	$sql="SELECT SUM(size) as tsize,familysite FROM $hour_table GROUP BY familysite LIMIT 0,10";
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$size=$ligne["tsize"];
			$size=$size/1024;
			
			$size=round($size,3);
			$PieData[$ligne["familysite"]]=$size;
			$c++;
	}
		
		
	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{size} KB";
	$highcharts->Title=$tpl->_ENGINE_parse_body($title);
	echo $highcharts->BuildChart();	
}
function graph4(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$CurTime=date("Ymdh");
	$CurDay=date("Ymd");
	$q=new mysql_squid_builder();
	$hour_table="quotahours_{$CurTime}";
	$day_table="quotaday_{$CurDay}";

	$title="{top_websites} {downloaded_size_this_day} (MB) $hour_table";
	$timetext="{hour}";


	$sql="SELECT SUM(size) as tsize,familysite FROM $day_table GROUP BY familysite ORDER BY tsize DESC LIMIT 0,10";

	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}

	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$size=$ligne["tsize"];
		$size=$size/1024;
		$size=$size/1024;
		$size=round($size,3);
		$PieData[$ligne["familysite"]]=$size;
		$c++;
	}


	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{size} MB";
	$highcharts->Title=$tpl->_ENGINE_parse_body($title);
	echo $highcharts->BuildChart();
}

function graph5(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$CurTime=date("YmdH");
	$CurDay=date("Ymd");
	$q=new mysql_squid_builder();
	$hour_table="quotahours_{$CurTime}";
	$day_table="quotaday_{$CurDay}";
	
	$title="{top_members} {downloaded_size_this_hour} (KB)";
	$timetext="{hour}";
	
	
	
	$sql="SELECT SUM(size) as tsize,ipaddr,uid,MAC FROM $hour_table GROUP BY ipaddr,uid,MAC LIMIT 0,15";
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}
	
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$ipaddr=$ligne["ipaddr"];
		$uid=$ligne["uid"];
		$MAC=$ligne["MAC"];
		$size=$ligne["tsize"];
		
		if($uid<>null){
			$MEMBERS[$uid]=$MEMBERS[$uid]+$size;
			continue;
		}
		
		if($MAC<>null){
			$MEMBERS[$MAC]=$MEMBERS[$MAC]+$size;
			continue;
		}

		if($ipaddr<>null){
			$MEMBERS[$ipaddr]=$MEMBERS[$ipaddr]+$size;
			continue;
		}		
		
	}
	
	
	while (list ($member, $size) = each ($MEMBERS) ){
		$size=$size/1024;
		$PieData[$member]=$size;
	}
	
	
	
	
	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{size} KB";
	$highcharts->Title=$tpl->_ENGINE_parse_body($title);
	echo $highcharts->BuildChart();
		
	
}
function graph6(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$CurTime=date("YmdH");
	$CurDay=date("Ymd");
	$q=new mysql_squid_builder();
	$hour_table="quotahours_{$CurTime}";
	$day_table="quotaday_{$CurDay}";

	$title="{top_members} {downloaded_size_this_day} (MB)";
	$timetext="{hour}";



	$sql="SELECT SUM(size) as tsize,ipaddr,uid,MAC FROM $day_table GROUP BY ipaddr,uid,MAC LIMIT 0,15";

	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}


	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$ipaddr=$ligne["ipaddr"];
		$uid=$ligne["uid"];
		$MAC=$ligne["MAC"];
		$size=$ligne["tsize"];

		if($uid<>null){
			$MEMBERS[$uid]=$MEMBERS[$uid]+$size;
			continue;
		}

		if($MAC<>null){
			$MEMBERS[$MAC]=$MEMBERS[$MAC]+$size;
			continue;
		}

		if($ipaddr<>null){
			$MEMBERS[$ipaddr]=$MEMBERS[$ipaddr]+$size;
			continue;
		}

	}


	while (list ($member, $size) = each ($MEMBERS) ){
		$size=$size/1024;
		$size=$size/1024;
		$PieData[$member]=$size;
	}




	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{size} MB";
	$highcharts->Title=$tpl->_ENGINE_parse_body($title);
	echo $highcharts->BuildChart();


}

function timejs(){
	header("content-type: application/x-javascript");
	$t=rand(50, 100);
	echo "Graph1Newtime='$t';";
}
