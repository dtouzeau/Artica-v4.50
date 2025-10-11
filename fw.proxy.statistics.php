<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["hour"])){page_hour();exit;}
if(isset($_GET["day"])){page_day();exit;}
if(isset($_GET["bandwidth-hour"])){hour_bandwidth();exit;}
if(isset($_GET["camembert"])){camembert();exit;}
if(isset($_GET["rqs-hour"])){hour_rqs();exit;}
if(isset($_GET["rqs-day"])){day_rqs();exit;}

if(isset($_GET["bandwidth-day"])){day_bandwidth();exit;}

xgen();



function xgen(){
$OPENVPN=false;	
$users=new usersMenus();
$page=CurrentPageName();
$t=time();
$html="
<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{SQUID_STATS1}</h1></div>
</div>
	<div class='ibox-content'>
		<div id='table-status-squid-stats'></div>
	</div>


	<script>
	$.address.state('/');
	$.address.value('proxy-statistics');
	$.address.title('Artica: Proxy Statistics');
	LoadAjax('table-status-squid-stats','$page?tabs=yes');

	</script>


";

if(isset($_GET["main-page"])){
	$tpl=new template_admin(null,$html);
	echo $tpl->build_firewall();
	return;
}

$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{this_hour}"]="$page?hour=yes";
	$array["{this_day}"]="$page?day=yes";
	$array["{this_week}"]="fw.proxy.statistics.week.php";
	$array["{this_month}"]="fw.proxy.statistics.month.php";
	echo $tpl->tabs_default($array);
}

function page_hour(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ProxyGraphsTOPNumber=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyGraphsTOPNumber"));
	if($ProxyGraphsTOPNumber<2){$ProxyGraphsTOPNumber=10;}
	
	$html[]="<table style='width:100%'>";
	$q=new postgres_sql();
	$now=date("Y-m-d H:00:00",time());
	
	
	$sql="SELECT to_char(zdate, 'HH:MI') as thour,SUM(SIZE) as size FROM proxy_traffic WHERE zdate>'$now' GROUP BY thour ORDER BY thour";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
	$MAIN=array();
	while($ligne=pg_fetch_array($results)){
		
		$size=$ligne["size"]/1024;
		$MAIN["xdata"][]=$ligne["thour"];
		$MAIN["ydata"][]=$size;
	}
	$html[]="<!-- ".__LINE__.": $sql  -->";
	$html[]="<!-- ".__LINE__.": From $now, ".count($MAIN["xdata"])." elements -->";
	if(count($MAIN["xdata"])>1){
		$data=serialize($MAIN);
		@file_put_contents(PROGRESS_DIR."/hour.bandwidth.data", $data);
		$html[]="<tr><td valign='top' colspan=2><div id='hour-bandwidth' style='with:1450;height:350px'></div></td></tr>";
		$js[]="Loadjs('$page?bandwidth-hour=yes');";
	}
	
	$sql="SELECT to_char(zdate, 'HH:MI') as thour,SUM(rqs) as size FROM proxy_traffic WHERE zdate>'$now' GROUP BY thour ORDER BY thour";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
	$MAIN=array();
	while($ligne=pg_fetch_array($results)){
		$MAIN["xdata"][]=$ligne["thour"];
		$MAIN["ydata"][]=$ligne["size"];
	}

	$html[]="<!-- ".__LINE__.": $sql  -->";
	$html[]="<!-- ".__LINE__.": From $now, ".count($MAIN["xdata"])." elements -->";
	if(count($MAIN["xdata"])>1){
		$data=serialize($MAIN);
		@file_put_contents(PROGRESS_DIR."/hour.requests.data", $data);
		$html[]="<tr><td valign='top' colspan=2><div id='hour-rqs' style='with:1450;height:350px'></div></td></tr>";
		$js[]="Loadjs('$page?rqs-hour=yes');";
	}
	
	//-------------------------------------------------------------------------------------------------------------
	
	$sql="SELECT familysite,SUM(SIZE) as size,SUM(rqs) as rqs FROM proxy_traffic WHERE zdate>'$now' GROUP BY familysite ORDER BY size desc LIMIT $ProxyGraphsTOPNumber";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
	$MAIN=array();
	$MAIN=array();
	$PieData=array();
	$table=array();
	$table[]="<table class='table table-striped'>";
	$table[]="<thead>";
	$table[]="<tr>";
	$table[]="<th>{familysite}</th>";
	$table[]="<th>{requests}</th>";
	$table[]="<th>{size}</th>";
	$table[]="</tr>";
	$table[]="</thead>";
	while($ligne=pg_fetch_array($results)){
		$familysite=$ligne["familysite"];
		$size=$ligne["size"]/1024;
		$rqs=FormatNumber($ligne["rqs"]);
		$PieData[$familysite]=$size;
		
		$table[]="<tr>";
		$table[]="<td nowrap>$familysite</td>";
		$table[]="<td width=1% nowrap>$rqs</td>";
		$table[]="<td width=1% nowrap>". FormatBytes($size)."</td>";
		$table[]="</tr>";
		
		
	}
	$table[]="</tr>";
	$table[]="</table>";
	
	$html[]="<!-- ".__LINE__.": $sql  -->";
	$html[]="<!-- ".__LINE__.": From $now, ".count($PieData)." elements -->";
	
	if(count($PieData)>1){
		$data=serialize($PieData);
		@file_put_contents(PROGRESS_DIR."/hour.familysite.data", $data);
		$html[]="<tr>
				<td valign='top'><div id='hour-familysite' style='with:600px;height:500px'></div></td>
				<td valign='top' style='width:1%' nowrap>".@implode("\n", $table)."</td>
				</tr>";
		$js[]="Loadjs('$page?camembert=yes&container=hour-familysite&data-file=hour.familysite.data&title=top_visited_websites');";
	}
//---------------------------------------------------------------------------------------------------------------------------------
	//-------------------------------------------------------------------------------------------------------------
	$table=array();
	$sql="SELECT category,SUM(SIZE) as size,SUM(rqs) as rqs FROM proxy_traffic WHERE zdate>'$now' GROUP BY category ORDER BY size desc LIMIT $ProxyGraphsTOPNumber";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
	$MAIN=array();
	$MAIN=array();
	$PieData=array();
	$table=array();
	$table[]="<table class='table table-striped'>";
	$table[]="<thead>";
	$table[]="<tr>";
	$table[]="<th>{category}</th>";
	$table[]="<th>{requests}</th>";
	$table[]="<th>{size}</th>";
	$table[]="</tr>";
	$table[]="</thead>";
	while($ligne=pg_fetch_array($results)){
		$familysite=$tpl->CategoryidToName($ligne["category"]);
		$size=$ligne["size"]/1024;
		$rqs=FormatNumber($ligne["rqs"]);
		$PieData[$familysite]=$size;
	
		$table[]="<tr>";
		$table[]="<td nowrap>$familysite</td>";
		$table[]="<td width=1% nowrap>$rqs</td>";
		$table[]="<td width=1% nowrap>". FormatBytes($size)."</td>";
		$table[]="</tr>";
	
	
	}
	$table[]="</tr>";
	$table[]="</table>";
	
	$html[]="<!-- ".__LINE__.": $sql  -->";
	$html[]="<!-- ".__LINE__.": From $now, ".count($PieData)." elements -->";
	
	if(count($PieData)>1){
		$data=serialize($PieData);
		@file_put_contents(PROGRESS_DIR."/hour.category.data", $data);
		$html[]="<tr>
				<td valign='top'><div id='hour-category' style='with:600px;height:500px'></div></td>
				<td valign='top' style='width:1%' nowrap>".@implode("\n", $table)."</td>
				</tr>";
		$js[]="Loadjs('$page?camembert=yes&container=hour-category&data-file=hour.category.data&title=top_visited_categories');";
	}
	//---------------------------------------------------------------------------------------------------------------------------------
	
	//-------------------------------------------------------------------------------------------------------------
	$table=array();$PieData=array();
	$sql="SELECT member,SUM(SIZE) as size,SUM(rqs) as rqs FROM proxy_traffic WHERE zdate>'$now' GROUP BY member ORDER BY size desc LIMIT $ProxyGraphsTOPNumber";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
	$MAIN=array();
	$MAIN=array();
	$PieData=array();
	$table=array();
	
	$table[]="<table class='table table-striped'>";
	$table[]="<thead>";
	$table[]="<tr>";
	$table[]="<th>{members}</th>";
	$table[]="<th>{requests}</th>";
	$table[]="<th>{size}</th>";
	$table[]="</tr>";
	$table[]="</thead>";
	while($ligne=pg_fetch_array($results)){
		$familysite=$ligne["member"];
		$size=$ligne["size"]/1024;
		$rqs=FormatNumber($ligne["rqs"]);
		$PieData[$familysite]=$size;
	
		$table[]="<tr>";
		$table[]="<td nowrap>$familysite</td>";
		$table[]="<td width=1% nowrap>$rqs</td>";
		$table[]="<td width=1% nowrap>". FormatBytes($size)."</td>";
		$table[]="</tr>";
	
	
	}
	$table[]="</tr>";
	$table[]="</table>";
	
	$html[]="<!-- ".__LINE__.": $sql  -->";
	$html[]="<!-- ".__LINE__.": From $now, ".count($PieData)." elements -->";
	
	if(count($PieData)>1){
		$data=serialize($PieData);
		@file_put_contents(PROGRESS_DIR."/hour.member.data", $data);
		$html[]="<tr>
				<td valign='top'><div id='hour-member' style='with:600px;height:500px'></div></td>
				<td valign='top' style='width:1%' nowrap>".@implode("\n", $table)."</td>
				</tr>";
		$js[]="Loadjs('$page?camembert=yes&container=hour-member&data-file=hour.member.data&title=top_members');";
	}
	//---------------------------------------------------------------------------------------------------------------------------------
		
	
	$html[]="</table>";
	$html[]="<script>".@implode("\n", $js)."</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
			
	
	
}

function page_day(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ProxyGraphsTOPNumber=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyGraphsTOPNumber"));
	if($ProxyGraphsTOPNumber<2){$ProxyGraphsTOPNumber=10;}
	
	$q=new postgres_sql();
	$now=date("Y-m-d 00:00:00",time());
	$sql="SELECT to_char(zdate, 'HH') as thour,SUM(SIZE) as size FROM proxy_traffic WHERE zdate>'$now' GROUP BY thour ORDER BY thour";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
	$final=array();
		$MAIN=array();
	$MAIN=array();
	$PieData=array();
	$table=array();
	
	$html[]="<table style='width:100%'>";
	
	while($ligne=pg_fetch_array($results)){
	
		$size=$ligne["size"]/1024;
		$size=$size/1024;
		$MAIN["xdata"][]=$ligne["thour"];
		$MAIN["ydata"][]=$size;
	}
	
	$html[]="<!-- ".__LINE__.": $sql  -->";
	$html[]="<!-- ".__LINE__.": From $now, ".count($MAIN["xdata"])." elements -->";
	if(count($MAIN["xdata"])>1){
		$data=serialize($MAIN);
		@file_put_contents(PROGRESS_DIR."/day.bandwidth.data", $data);
		$html[]="<tr><td valign='top' colspan=2><div id='day-bandwidth' style='with:1450;height:350px'></div></td></tr>";
		$js[]="Loadjs('$page?bandwidth-day=yes');";
	}
	
	$sql="SELECT to_char(zdate, 'HH') as thour,SUM(rqs) as size FROM proxy_traffic WHERE zdate>'$now' GROUP BY thour ORDER BY thour";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
	$MAIN=array();
	while($ligne=pg_fetch_array($results)){
		$MAIN["xdata"][]=$ligne["thour"];
		$MAIN["ydata"][]=$ligne["size"];
	}
	
	$html[]="<!-- ".__LINE__.": $sql  -->";
	$html[]="<!-- ".__LINE__.": From $now, ".count($MAIN["xdata"])." elements -->";
	if(count($MAIN["xdata"])>1){
		$data=serialize($MAIN);
		@file_put_contents(PROGRESS_DIR."/day.requests.data", $data);
		$html[]="<tr><td valign='top' colspan=2><div id='day-rqs' style='with:1450;height:350px'></div></td></tr>";
		$js[]="Loadjs('$page?rqs-day=yes');";
	}	
	
	
	
	//-------------------------------------------------------------------------------------------------------------
	$table=array();$PieData=array();
	$sql="SELECT familysite,SUM(SIZE) as size,SUM(rqs) as rqs FROM proxy_traffic WHERE zdate>'$now' GROUP BY familysite ORDER BY size desc LIMIT $ProxyGraphsTOPNumber";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
		$MAIN=array();
	$MAIN=array();
	$PieData=array();
	$table=array();
	
	$table[]="<table class='table table-striped'>";
	$table[]="<thead>";
	$table[]="<tr>";
	$table[]="<th>{familysite}</th>";
	$table[]="<th>{requests}</th>";
	$table[]="<th>{size}</th>";
	$table[]="</tr>";
	$table[]="</thead>";
	while($ligne=pg_fetch_array($results)){
		$familysite=$ligne["familysite"];
		$size=$ligne["size"]/1024;
		$rqs=FormatNumber($ligne["rqs"]);
		$PieData[$familysite]=$size;
	
		$table[]="<tr>";
		$table[]="<td nowrap>$familysite</td>";
		$table[]="<td width=1% nowrap>$rqs</td>";
		$table[]="<td width=1% nowrap>". FormatBytes($size)."</td>";
		$table[]="</tr>";
	
	
	}
	$table[]="</tr>";
	$table[]="</table>";
	
	$html[]="<!-- ".__LINE__.": $sql  -->";
	$html[]="<!-- ".__LINE__.": From $now, ".count($PieData)." elements -->";
	
	if(count($PieData)>1){
		$data=serialize($PieData);
		@file_put_contents(PROGRESS_DIR."/day.familysite.data", $data);
		$html[]="<tr>
				<td valign='top'><div id='day-familysite' style='with:600px;height:500px'></div></td>
				<td valign='top' style='width:1%' nowrap>".@implode("\n", $table)."</td>
				</tr>";
		$js[]="Loadjs('$page?camembert=yes&container=day-familysite&data-file=day.familysite.data&title=top_visited_websites');";
	}
	//---------------------------------------------------------------------------------------------------------------------------------	
	

	//-------------------------------------------------------------------------------------------------------------
	$table=array();$PieData=array();
	$sql="SELECT category,SUM(SIZE) as size,SUM(rqs) as rqs FROM proxy_traffic WHERE zdate>'$now' GROUP BY category ORDER BY size desc LIMIT $ProxyGraphsTOPNumber";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
		$MAIN=array();
	$MAIN=array();
	$PieData=array();
	$table=array();
	
	$table[]="<table class='table table-striped'>";
	$table[]="<thead>";
	$table[]="<tr>";
	$table[]="<th>{category}</th>";
	$table[]="<th>{requests}</th>";
	$table[]="<th>{size}</th>";
	$table[]="</tr>";
	$table[]="</thead>";
	while($ligne=pg_fetch_array($results)){
		$familysite=$tpl->CategoryidToName($ligne["category"]);
		$size=$ligne["size"]/1024;
		$rqs=FormatNumber($ligne["rqs"]);
		$PieData[$familysite]=$size;
	
		$table[]="<tr>";
		$table[]="<td nowrap>$familysite</td>";
		$table[]="<td width=1% nowrap>$rqs</td>";
		$table[]="<td width=1% nowrap>". FormatBytes($size)."</td>";
		$table[]="</tr>";
	
	
	}
	$table[]="</tr>";
	$table[]="</table>";
	
	$html[]="<!-- ".__LINE__.": $sql  -->";
	$html[]="<!-- ".__LINE__.": From $now, ".count($PieData)." elements -->";
	
	if(count($PieData)>1){
		$data=serialize($PieData);
		@file_put_contents(PROGRESS_DIR."/day.category.data", $data);
		$html[]="<tr>
				<td valign='top'><div id='day-category' style='with:600px;height:500px'></div></td>
				<td valign='top' style='width:1%' nowrap>".@implode("\n", $table)."</td>
				</tr>";
		$js[]="Loadjs('$page?camembert=yes&container=day-category&data-file=day.category.data&title=top_visited_categories');";
	}
	//---------------------------------------------------------------------------------------------------------------------------------
		
	
	//-------------------------------------------------------------------------------------------------------------
	$table=array();$PieData=array();
	$sql="SELECT member,SUM(SIZE) as size,SUM(rqs) as rqs FROM proxy_traffic WHERE zdate>'$now' GROUP BY member ORDER BY size desc LIMIT $ProxyGraphsTOPNumber";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
		$MAIN=array();
	$MAIN=array();
	$PieData=array();
	$table=array();
	
	$table[]="<table class='table table-striped'>";
	$table[]="<thead>";
	$table[]="<tr>";
	$table[]="<th>{members}</th>";
	$table[]="<th>{requests}</th>";
	$table[]="<th>{size}</th>";
	$table[]="</tr>";
	$table[]="</thead>";
	while($ligne=pg_fetch_array($results)){
		$familysite=$ligne["member"];
		$size=$ligne["size"]/1024;
		$rqs=FormatNumber($ligne["rqs"]);
		$PieData[$familysite]=$size;
	
		$table[]="<tr>";
		$table[]="<td nowrap>$familysite</td>";
		$table[]="<td width=1% nowrap>$rqs</td>";
		$table[]="<td width=1% nowrap>". FormatBytes($size)."</td>";
		$table[]="</tr>";
	
	
	}
	$table[]="</tr>";
	$table[]="</table>";
	
	$html[]="<!-- ".__LINE__.": $sql  -->";
	$html[]="<!-- ".__LINE__.": From $now, ".count($PieData)." elements -->";
	
	if(count($PieData)>1){
		$data=serialize($PieData);
		@file_put_contents(PROGRESS_DIR."/day.member.data", $data);
		$html[]="<tr>
				<td valign='top'><div id='day-member' style='with:600px;height:500px'></div></td>
				<td valign='top' style='width:1%' nowrap>".@implode("\n", $table)."</td>
				</tr>";
		$js[]="Loadjs('$page?camembert=yes&container=day-member&data-file=day.member.data&title=top_members');";
	}
	//---------------------------------------------------------------------------------------------------------------------------------
		
	
	
	$html[]="</table>";
	$html[]="<script>".@implode("\n", $js)."</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
}

function hour_bandwidth(){
	$MAIN=unserialize(@file_get_contents(PROGRESS_DIR."/hour.bandwidth.data"));
	$tpl=new templates();
	$page=CurrentPageName();
	$title="{bandwidth}/{this_hour}";
	$timetext="{time}";
	$highcharts=new highcharts();
	$highcharts->container="hour-bandwidth";
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="KB";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{time}: ');
	$highcharts->LegendSuffix="KB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("| {bandwidth}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();
	
}

function camembert(){
	$tpl=new templates();
	$page=CurrentPageName();
	$PieData=unserialize(@file_get_contents(PROGRESS_DIR."/{$_GET["data-file"]}"));
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{{$_GET["title"]}}");
	echo $highcharts->BuildChart();
	
	
}

function hour_rqs(){
	$MAIN=unserialize(@file_get_contents(PROGRESS_DIR."/hour.requests.data"));
	$tpl=new templates();
	$page=CurrentPageName();
	$title="{requests}/{this_hour}";
	$timetext="{time}";
	$highcharts=new highcharts();
	$highcharts->container="hour-rqs";
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="RQS";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{time}:');
	$highcharts->LegendSuffix="{requests}";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("| {requests}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();
}

function day_bandwidth(){
	$MAIN=unserialize(@file_get_contents(PROGRESS_DIR."/day.bandwidth.data"));
	$tpl=new templates();
	$page=CurrentPageName();
	$title="{bandwidth}/{this_day}";
	$timetext="{hour}";
	$highcharts=new highcharts();
	$highcharts->container="day-bandwidth";
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{hour}');
	$highcharts->LegendSuffix="MB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array(":00 | {bandwidth}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();

}
function day_rqs(){
	$MAIN=unserialize(@file_get_contents(PROGRESS_DIR."/day.requests.data"));
	$tpl=new templates();
	$page=CurrentPageName();
	$title="{requests}/{this_day}";
	$timetext="{time}";
	$highcharts=new highcharts();
	$highcharts->container="day-rqs";
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="KB";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{hour}');
	$highcharts->LegendSuffix="{requests}";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array(":00 {hour}| "=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();
}




function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}