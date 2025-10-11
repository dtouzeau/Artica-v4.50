<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.mysql-meta.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');


$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die("DIE " .__FILE__." Line: ".__LINE__);

}



if(isset($_GET["popup"])){page();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["flow-day"])){flow_day();exit;}
if(isset($_GET["flow-month-graph1"])){flow_month_graph1();exit;}
if(isset($_GET["flow-month-graph2"])){flow_month_graph2();exit;}
if(isset($_GET["websites"])){websites_table();exit;}
if(isset($_GET["search-websites"])){websites_search();exit;}

if(isset($_GET["members"])){members_table();exit;}
if(isset($_GET["search-members"])){members_search();exit;}

if(isset($_GET["categories"])){categories_table();exit;}
if(isset($_GET["search-categories"])){categories_search();exit;}

if(isset($_GET["days"])){days_table();exit;}
if(isset($_GET["search-days"])){days_search();exit;}





js();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$suffix=suffix();
	$t=time();
	$title=$tpl->_ENGINE_parse_body("{this_month}::{$_GET["field"]} {$_GET["value"]}");
	$page=CurrentPageName();
	$html="
	function Start$t(){
	YahooWin2('1019','$page?tabs=yes$suffix','$title')
}

Start$t();";

	echo $html;


}

function suffix(){
	if(!isset($_GET["time"])){$_GET["time"]=time();}
	return "&field={$_GET["field"]}&value=".urlencode($_GET["value"])."&time={$_GET["time"]}";
}

function tabs(){
	$sock=new sockets();
	$fontsize=16;
	$tpl=new templates();
	$page=CurrentPageName();

	$md5=md5($_GET["field"].$_GET["value"]);

	$date=date("Ym");
	$table="{$date}_maccess";


	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($table)){
		echo FATAL_ERROR_SHOW_128("{no_table_see_support}");
		return;
	}

	$suffix=suffix();
	$array["flow-day"]='{flow_by_day}';
	$array["members"]='{members}';
	//$array["categories"]='{categories}';
	$array["days"]='{day_tracking}';
	
	




	foreach ($array as $num=>$ligne){

		if($num=="parameters"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.accesslogs.params.php?parameters=yes\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;

		}

		if($num=="schedule"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.databases.schedules.php?TaskType=54\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;

		}

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes$suffix\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
	}

	echo build_artica_tabs($html, "squid_users_stats_$md5",990)."";

}

function flow_day(){
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$date=date("Ym",$_GET["time"]);
	$sql="SELECT SUM(hits) as hits,SUM(size) as size,{$_GET["field"]},DAY(zDate) as tday 
	FROM {$date}_maccess GROUP BY {$_GET["field"]},tday HAVING {$_GET["field"]}='{$_GET["value"]}' ORDER BY tday";
	$time=time();
	
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	if(!$q->ok){
		echo FATAL_ERROR_SHOW_128($q->mysql_error_html());
		return;
		
	}
	
	
	if(mysqli_num_rows($results)<2){
		echo FATAL_ERROR_SHOW_128("{request_is_less_2}");
		return;
		
	}
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$value1[$ligne["tday"]]=round($ligne["size"]/1024);
		$value2[$ligne["tday"]]=$ligne["hits"];
	}
	
	$value1_enc=urlencode(base64_encode(serialize($value1)));
	$value2_enc=urlencode(base64_encode(serialize($value2)));
	
	$f1[]="<div style='width:955px;height:340px' id='$time-2'></div>";
	$f1[]="<div style='width:955px;height:340px' id='$time-3'></div>";
	
	
	$f2[]="function FDeux$time(){
		LoadjsSilent('$page?container=$time-2&flow-month-graph1=yes&serialize=$value1_enc',false);
	}
	setTimeout(\"FDeux$time()\",500);";
	

	$f2[]="function F3$time(){
	LoadjsSilent('$page?container=$time-3&flow-month-graph2=yes&serialize=$value2_enc',false);
	}
	setTimeout(\"F3$time()\",500);";
	
	
	echo @implode("\n", $f1);
	echo "<script>".@implode("\n", $f2)."</script>";
}
function flow_month_graph1(){


	$data=unserialize(base64_decode($_GET["serialize"]));
	
	
	
	while (list ($day, $size) = each ($data) ){
		
		$size=$size/1024;
		$xdata[]=$day;
		$ydata[]=$size;
		
	}


	$title="{downloaded_flow_this_month} (MB)";
	$timetext="{days}";
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	//$highcharts->subtitle="<a href=\"javascript:blur();\" OnClick=\"Loadjs('squid.rtt.php')\" style='font-size:16px;text-decoration:underline'>{realtime_flow}</a>";
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=true;
	//$highcharts->LegendPrefix=date("H")."h";
	$highcharts->LegendSuffix="MB";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();
}

function flow_month_graph2(){
	$data=unserialize(base64_decode($_GET["serialize"]));
	
	
	
	while (list ($day, $size) = each ($data) ){
	
		
		$xdata[]=$day;
		$ydata[]=$size;
	
	}
	
	
	$title="{requests_number_per_day}";
	$timetext="{days}";
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	//$highcharts->subtitle="<a href=\"javascript:blur();\" OnClick=\"Loadjs('squid.rtt.php')\" style='font-size:16px;text-decoration:underline'>{realtime_flow}</a>";
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=true;
	//$highcharts->LegendPrefix=date("H")."h";
	$highcharts->LegendSuffix="{requests}";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{requests}"=>$ydata);
	echo $highcharts->BuildChart();
}
function websites_table(){

	
	$suffix=suffix();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$familysite=$tpl->javascript_parse_text("{familysite}");
	$MAC=$tpl->javascript_parse_text("{MAC}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$load=$tpl->javascript_parse_text("{load}");
	$version=$tpl->javascript_parse_text("{version}");
	$filename=$tpl->javascript_parse_text("{filename}");
	$status=$tpl->javascript_parse_text("{status}");
	$events=$tpl->javascript_parse_text("{events}");
	$global_whitelist=$tpl->javascript_parse_text("{whitelist} (Meta)");
	$policies=$tpl->javascript_parse_text("{policies}");
	$orders=$tpl->javascript_parse_text("{orders}");
	$type=$tpl->javascript_parse_text("{type}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$size=$tpl->javascript_parse_text("{size}");
	$link_all_hosts_ask=$tpl->javascript_parse_text("{link_all_hosts_ask}");
	$policies=$tpl->javascript_parse_text("{policies}");
	$tag=$tpl->javascript_parse_text("{tag}");
	$synchronize=$tpl->javascript_parse_text("{synchronize}");
	$synchronize_policies_explain=$tpl->javascript_parse_text("{synchronize_policies_explain}");
	$t=time();
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	$categorysize=387;
	$date=$tpl->javascript_parse_text("{date}");
	$title=$tpl->javascript_parse_text("{this_month}:: {websites}:: {$_GET["field"]}:: &laquo;{$_GET["value"]}&raquo;");
	$suffixMD=md5($suffix);
	$buttons="
	buttons : [
	{name: '$link_host', bclass: 'add', onpress : LinkHosts$t},
	{name: '$synchronize', bclass: 'ScanNet', onpress : Orders$t},
	],";
	$buttons=null;

	//	$q=new mysql();
	//$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT SUM(size) as size FROM backuped_logs","artica_events"));

	$html="
	<table class='SQUID_USERS_PROFILE_TABLE$suffixMD' style='display: none' id='SQUID_USERS_PROFILE_TABLE$suffixMD' style='width:1200px'></table>
	<script>
	$(document).ready(function(){
	$('#SQUID_USERS_PROFILE_TABLE$suffixMD').flexigrid({
	url: '$page?search-websites=yes&$suffix',
	dataType: 'json',
	colModel : [
	{display: '$familysite', name : 'familysite', width : 641, sortable : true, align: 'left'},
	{display: '$size', name : 'size', width : 124, sortable : true, align: 'right'},
	{display: '$hits', name : 'hits', width : 124, sortable : true, align: 'right'},
	],
	$buttons
	searchitems : [
	{display: '$familysite', name : 'familysite'},
	],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<strong style=font-size:22px>$title</strong>',
	useRp: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:50,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true

});
});
</script>";
echo $html;
}

function websites_search(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$date=date("Ym",$_GET["time"]);
	
	
	
	$table="(SELECT SUM(hits) as hits,SUM(size) as size,familysite,{$_GET["field"]} 
	FROM {$date}_maccess GROUP BY familysite,{$_GET["field"]} HAVING {$_GET["field"]}='{$_GET["value"]}') as t";
	$searchstring=string_to_flexquery();
	$page=1;


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){ $ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}"; }}
	if (isset($_POST['page'])) {$page = $_POST['page'];}


	$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
	$total = $ligne["tcount"];


	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}


	if(mysqli_num_rows($results)==0){json_error_show("no data",1);}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$fontsize="18";
	$style=" style='font-size:{$fontsize}px'";
	$styleHref=" style='font-size:{$fontsize}px;text-decoration:underline'";
	$free_text=$tpl->javascript_parse_text("{free}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$overloaded_text=$tpl->javascript_parse_text("{overloaded}");
	$orders_text=$tpl->javascript_parse_text("{orders}");
	$directories_monitor=$tpl->javascript_parse_text("{directories_monitor}");


	while ($ligne = mysqli_fetch_assoc($results)) {
	$LOGSWHY=array();

	$uid=$ligne["uid"];
	$MAC=$ligne["MAC"];
	$ipaddr=$ligne["ipaddr"];
	$size=FormatBytes($ligne["size"]/1024);
	$hits=FormatNumber($ligne["hits"]);
	$familysite=$ligne["familysite"];
		
		
		$MAC_FILTER="Loadjs('squid.users-stats.currentmonth.php?field=MAC&value=".urlencode($MAC)."')";
		$UID_FILTER="Loadjs('squid.users-stats.currentmonth.php?field=uid&value=".urlencode($uid)."')";
		$IPADDR_FILTER="Loadjs('squid.users-stats.currentmonth.php?field=ipaddr&value=".urlencode($ipaddr)."')";
	

		$MAC_FILTERlink="<a href=\"javascript:blur();\"
		OnClick=\"javascript:$MAC_FILTER\" $styleHref>";


		$UID_FILTERlink="<a href=\"javascript:blur();\"
		OnClick=\"javascript:$UID_FILTER\" $styleHref>";

		$IPADDR_FILTERlink="<a href=\"javascript:blur();\"
		OnClick=\"javascript:$IPADDR_FILTER\" $styleHref>";


				$cell=array();
				$cell[]="<span $style>$familysite</a></span>";
				$cell[]="<span $style>$size</a></span>";
				$cell[]="<span $style>$hits</a></span>";


				$data['rows'][] = array(
						'id' => $ligne['zmd5'],
				'cell' => $cell
				);
	}


	echo json_encode($data);
}
function categories_table(){


	$suffix=suffix();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$categories=$tpl->javascript_parse_text("{categories}");
	$MAC=$tpl->javascript_parse_text("{MAC}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$load=$tpl->javascript_parse_text("{load}");
	$version=$tpl->javascript_parse_text("{version}");
	$filename=$tpl->javascript_parse_text("{filename}");
	$status=$tpl->javascript_parse_text("{status}");
	$events=$tpl->javascript_parse_text("{events}");
	$global_whitelist=$tpl->javascript_parse_text("{whitelist} (Meta)");
	$policies=$tpl->javascript_parse_text("{policies}");
	$orders=$tpl->javascript_parse_text("{orders}");
	$type=$tpl->javascript_parse_text("{type}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$size=$tpl->javascript_parse_text("{size}");
	$link_all_hosts_ask=$tpl->javascript_parse_text("{link_all_hosts_ask}");
	$policies=$tpl->javascript_parse_text("{policies}");
	$tag=$tpl->javascript_parse_text("{tag}");
	$synchronize=$tpl->javascript_parse_text("{synchronize}");
	$synchronize_policies_explain=$tpl->javascript_parse_text("{synchronize_policies_explain}");
	$t=time();
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	$categorysize=387;
	$date=$tpl->javascript_parse_text("{date}");
	$title=$tpl->javascript_parse_text("{this_month}:: {categories}:: {$_GET["field"]}:: &laquo;{$_GET["value"]}&raquo;");
	$suffixMD=md5(serialize($_GET));
	$buttons="
	buttons : [
	{name: '$link_host', bclass: 'add', onpress : LinkHosts$t},
	{name: '$synchronize', bclass: 'ScanNet', onpress : Orders$t},
	],";
	$buttons=null;

	//	$q=new mysql();
	//$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT SUM(size) as size FROM backuped_logs","artica_events"));

	$html="
	<table class='SQUID_USERS_PROFILE_TABLE$suffixMD' style='display: none' id='SQUID_USERS_PROFILE_TABLE$suffixMD' style='width:1200px'></table>
	<script>
	$(document).ready(function(){
	$('#SQUID_USERS_PROFILE_TABLE$suffixMD').flexigrid({
	url: '$page?search-categories=yes&$suffix',
	dataType: 'json',
	colModel : [
	{display: '$categories', name : 'category', width : 641, sortable : true, align: 'left'},
	{display: '$size', name : 'size', width : 124, sortable : true, align: 'right'},
	{display: '$hits', name : 'hits', width : 124, sortable : true, align: 'right'},
	],
	$buttons
	searchitems : [
	{display: '$categories', name : 'category'},
	],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<strong style=font-size:22px>$title</strong>',
	useRp: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:50,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true

});
});
</script>";
	echo $html;
}

function categories_search(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$date=date("Ym",$_GET["time"]);



	$table="(SELECT SUM(hits) as hits,SUM(size) as size,category,{$_GET["field"]}
	FROM {$date}_maccess GROUP BY category,{$_GET["field"]} HAVING {$_GET["field"]}='{$_GET["value"]}') as t";
	$searchstring=string_to_flexquery();
	$page=1;


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){ $ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}"; }}
	if (isset($_POST['page'])) {$page = $_POST['page'];}


	$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
	$total = $ligne["tcount"];


	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}

	$suffix=suffix();
	if(mysqli_num_rows($results)==0){json_error_show("no data",1);}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$fontsize="18";
	$style=" style='font-size:{$fontsize}px'";
	$styleHref=" style='font-size:{$fontsize}px;text-decoration:underline'";
	$free_text=$tpl->javascript_parse_text("{free}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$overloaded_text=$tpl->javascript_parse_text("{overloaded}");
	$orders_text=$tpl->javascript_parse_text("{orders}");
	$directories_monitor=$tpl->javascript_parse_text("{directories_monitor}");


	while ($ligne = mysqli_fetch_assoc($results)) {
	$LOGSWHY=array();

	$uid=$ligne["uid"];
	$MAC=$ligne["MAC"];
	$ipaddr=$ligne["ipaddr"];
	$size=FormatBytes($ligne["size"]/1024);
	$hits=FormatNumber($ligne["hits"]);
	$category=$ligne["category"];

	$categoryenc=urlencode($category);
	$FILTER="Loadjs('squid.users-stats.currentmonth.category.php?category=$categoryenc&$suffix')";


	$FILTERlink="<a href=\"javascript:blur();\"
	OnClick=\"javascript:$FILTER\" $styleHref>";


	
	if($category==null){$category="Unknown";}

	$cell=array();
	$cell[]="<span $style>$FILTERlink$category</a></span>";
	$cell[]="<span $style>$size</a></span>";
	$cell[]="<span $style>$hits</a></span>";


	$data['rows'][] = array(
	'id' => $ligne['zmd5'],
	'cell' => $cell
	);
	}


	echo json_encode($data);
	}
	
function days_table(){
	
	
		$suffix=suffix();
		$page=CurrentPageName();
		$tpl=new templates();
		$t=time();
		$days=$tpl->javascript_parse_text("{days}");
		$MAC=$tpl->javascript_parse_text("{MAC}");
		$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
		$load=$tpl->javascript_parse_text("{load}");
		$version=$tpl->javascript_parse_text("{version}");
		$filename=$tpl->javascript_parse_text("{filename}");
		$status=$tpl->javascript_parse_text("{status}");
		$events=$tpl->javascript_parse_text("{events}");
		$global_whitelist=$tpl->javascript_parse_text("{whitelist} (Meta)");
		$policies=$tpl->javascript_parse_text("{policies}");
		$orders=$tpl->javascript_parse_text("{orders}");
		$type=$tpl->javascript_parse_text("{type}");
		$hits=$tpl->javascript_parse_text("{hits}");
		$size=$tpl->javascript_parse_text("{size}");
		$link_all_hosts_ask=$tpl->javascript_parse_text("{link_all_hosts_ask}");
		$policies=$tpl->javascript_parse_text("{policies}");
		$tag=$tpl->javascript_parse_text("{tag}");
		$synchronize=$tpl->javascript_parse_text("{synchronize}");
		$synchronize_policies_explain=$tpl->javascript_parse_text("{synchronize_policies_explain}");
		$t=time();
		$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
		$categorysize=387;
		$date=$tpl->javascript_parse_text("{date}");
		$title=$tpl->javascript_parse_text("{this_month}:: {days}:: {$_GET["field"]}:: &laquo;{$_GET["value"]}&raquo;");
		$suffixMD=md5(serialize($_GET));
		$buttons="
		buttons : [
		{name: '$link_host', bclass: 'add', onpress : LinkHosts$t},
		{name: '$synchronize', bclass: 'ScanNet', onpress : Orders$t},
		],";
		$buttons=null;
	
		//	$q=new mysql();
		//$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT SUM(size) as size FROM backuped_logs","artica_events"));
	
		$html="
		<table class='SQUID_USERS_PROFILE_TABLE$suffixMD' style='display: none' id='SQUID_USERS_PROFILE_TABLE$suffixMD' style='width:1200px'></table>
		<script>
		$(document).ready(function(){
		$('#SQUID_USERS_PROFILE_TABLE$suffixMD').flexigrid({
		url: '$page?search-days=yes&$suffix',
		dataType: 'json',
		colModel : [
		{display: '$days', name : 'zDate', width : 641, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width : 124, sortable : true, align: 'right'},
		{display: '$hits', name : 'hits', width : 124, sortable : true, align: 'right'},
		],
		$buttons
		searchitems : [
		{display: '$days', name : 'zDate'},
		],
		sortname: 'zDate',
		sortorder: 'desc',
		usepager: true,
		title: '<strong style=font-size:22px>$title</strong>',
		useRp: true,
		rpOptions: [10, 20, 30, 50,100,200],
		rp:50,
		showTableToggleBtn: false,
		width: '99%',
		height: 400,
		singleSelect: true
	
	});
	});
	</script>";
		echo $html;
	}	

	
function days_search(){
		$MyPage=CurrentPageName();
		$page=CurrentPageName();
		$tpl=new templates();
		$sock=new sockets();
		$q=new mysql_squid_builder();
		$date=date("Ym",$_GET["time"]);
	
	
	
		$table="(SELECT SUM(hits) as hits,SUM(size) as size,zDate,{$_GET["field"]}
		FROM {$date}_maccess GROUP BY zDate,{$_GET["field"]} HAVING {$_GET["field"]}='{$_GET["value"]}') as t";
		$searchstring=string_to_flexquery();
		$page=1;
	
	
		if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){ $ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}"; }}
		if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
		$total = $ligne["tcount"];
	
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
		if(!is_numeric($rp)){$rp=50;}
	
	
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
		$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
		$results = $q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}
	
		$suffix=suffix();
		if(mysqli_num_rows($results)==0){json_error_show("no data",1);}
	
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
	
		$fontsize="18";
		$style=" style='font-size:{$fontsize}px'";
		$styleHref=" style='font-size:{$fontsize}px;text-decoration:underline'";
		$free_text=$tpl->javascript_parse_text("{free}");
		$computers=$tpl->javascript_parse_text("{computers}");
		$overloaded_text=$tpl->javascript_parse_text("{overloaded}");
		$orders_text=$tpl->javascript_parse_text("{orders}");
		$directories_monitor=$tpl->javascript_parse_text("{directories_monitor}");
	
	
		while ($ligne = mysqli_fetch_assoc($results)) {
			$LOGSWHY=array();
	
			$uid=$ligne["uid"];
			$MAC=$ligne["MAC"];
			$ipaddr=$ligne["ipaddr"];
			$size=FormatBytes($ligne["size"]/1024);
			$hits=FormatNumber($ligne["hits"]);
			$zDate=$ligne["zDate"];
	
			$zDateEnc=urlencode($zDate);
			$FILTER="Loadjs('squid.users-stats.currentmonth.day.php?zdate=$zDateEnc&$suffix')";
	
	
			$FILTERlink="<a href=\"javascript:blur();\"
			OnClick=\"javascript:$FILTER\" $styleHref>";
	
			$time=strtotime($zDate);
			$time_text=$tpl->_ENGINE_parse_body($q->time_to_date($time));
	
			
	
			$cell=array();
			$cell[]="<span $style>$FILTERlink$zDate&nbsp;-&nbsp;$time_text</a></span>";
			$cell[]="<span $style>$size</a></span>";
			$cell[]="<span $style>$hits</a></span>";
	
	
			$data['rows'][] = array(
					'id' => $ligne['zmd5'],
					'cell' => $cell
			);
		}
	
	
		echo json_encode($data);
	}
	
	
	
function members_table(){
	$suffix=suffix();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$familysite=$tpl->javascript_parse_text("{familysite}");
	$MAC=$tpl->javascript_parse_text("{MAC}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$load=$tpl->javascript_parse_text("{load}");
	$version=$tpl->javascript_parse_text("{version}");
		$filename=$tpl->javascript_parse_text("{filename}");
		$status=$tpl->javascript_parse_text("{status}");
		$events=$tpl->javascript_parse_text("{events}");
		$global_whitelist=$tpl->javascript_parse_text("{whitelist} (Meta)");
		$policies=$tpl->javascript_parse_text("{policies}");
		$orders=$tpl->javascript_parse_text("{orders}");
		$type=$tpl->javascript_parse_text("{type}");
		$hits=$tpl->javascript_parse_text("{hits}");
		$size=$tpl->javascript_parse_text("{size}");
		$link_all_hosts_ask=$tpl->javascript_parse_text("{link_all_hosts_ask}");
		$mac=$tpl->javascript_parse_text("{MAC}");
		$uid=$tpl->javascript_parse_text("{uid}");
		$synchronize=$tpl->javascript_parse_text("{synchronize}");
		$synchronize_policies_explain=$tpl->javascript_parse_text("{synchronize_policies_explain}");
		$t=time();
		$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
		$categorysize=387;
		$date=$tpl->javascript_parse_text("{date}");
		$title=$tpl->javascript_parse_text("{this_month}:: {members}:: {$_GET["field"]}:: &laquo;{$_GET["value"]}&raquo;");
		$suffixMD=md5($suffix);
		$buttons="
		buttons : [
		{name: '$link_host', bclass: 'add', onpress : LinkHosts$t},
		{name: '$synchronize', bclass: 'ScanNet', onpress : Orders$t},
		],";
		$buttons=null;
	
		//	$q=new mysql();
		//$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT SUM(size) as size FROM backuped_logs","artica_events"));
	
		$html="
		<table class='SQUID_WEBSITES_TABLE$suffixMD' style='display: none' id='SQUID_WEBSITES_TABLE$suffixMD' style='width:1200px'></table>
		<script>
		$(document).ready(function(){
		$('#SQUID_WEBSITES_TABLE$suffixMD').flexigrid({
		url: '$page?search-members=yes&$suffix',
		dataType: 'json',
		colModel : [
		{display: '$uid', name : 'uid', width : 180, sortable : true, align: 'left'},
		{display: '$mac', name : 'MAC', width : 180, sortable : true, align: 'left'},
		{display: '$ipaddr', name : 'ipaddr', width : 180, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width : 124, sortable : true, align: 'right'},
		{display: '$hits', name : 'hits', width : 124, sortable : true, align: 'right'},
		],
		$buttons
		searchitems : [
		{display: '$uid', name : 'uid'},
		{display: '$mac', name : 'MAC'},
		{display: '$ipaddr', name : 'ipaddr'},
		],
		sortname: 'size',
		sortorder: 'desc',
		usepager: true,
		title: '<strong style=font-size:22px>$title</strong>',
		useRp: true,
		rpOptions: [10, 20, 30, 50,100,200],
		rp:50,
		showTableToggleBtn: false,
		width: '99%',
		height: 400,
		singleSelect: true
	
	});
	});
	</script>";
		echo $html;
	}	
	
function members_search(){
		$MyPage=CurrentPageName();
		$page=CurrentPageName();
		$tpl=new templates();
		$sock=new sockets();
		$q=new mysql_squid_builder();
		$date=date("Ym",$_GET["time"]);
	
	
	
		$table="(SELECT SUM(hits) as hits,SUM(size) as size,uid,ipaddr,MAC,{$_GET["field"]}
		FROM {$date}_maccess GROUP BY uid,ipaddr,MAC,{$_GET["field"]} HAVING {$_GET["field"]}='{$_GET["value"]}') as t";
		$searchstring=string_to_flexquery();
		$page=1;
	
	
		if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){ $ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}"; }}
		if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	
				$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
				$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
			$total = $ligne["tcount"];
	
	
					if (isset($_POST['rp'])) {$rp != $_POST['rp'];}
		if(!is_numeric($rp)){$rp=50;}
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}
	
	$suffix=suffix();
	if(mysqli_num_rows($results)==0){json_error_show("no data",1);}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$fontsize="18";
	$style=" style='font-size:{$fontsize}px'";
	$styleHref=" style='font-size:{$fontsize}px;text-decoration:underline'";
	$free_text=$tpl->javascript_parse_text("{free}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$overloaded_text=$tpl->javascript_parse_text("{overloaded}");
	$orders_text=$tpl->javascript_parse_text("{orders}");
	$directories_monitor=$tpl->javascript_parse_text("{directories_monitor}");
	
	
	while ($ligne = mysqli_fetch_assoc($results)) {
			$LOGSWHY=array();
	
			$uid=$ligne["uid"];
			$MAC=$ligne["MAC"];
			$ipaddr=$ligne["ipaddr"];
			$size=FormatBytes($ligne["size"]/1024);
			$hits=FormatNumber($ligne["hits"]);
			$zDate=$ligne["zDate"];
			$zDateEnc=urlencode($zDate);
			
			$FILTER_uid="<a href=\"javascript:blur();\"
			OnClick=\"Loadjs('squid.users-stats.currentmonth.website.php?zdate=$zDateEnc&field=uid&value=$uid&familysite={$_GET["value"]}')\" 
			$styleHref>";
			
			$FILTER_MAC="<a href=\"javascript:blur();\"
			OnClick=\"Loadjs('squid.users-stats.currentmonth.website.php?zdate=$zDateEnc&field=MAC&value=$MAC&familysite={$_GET["value"]}')\"
			$styleHref>";

			$FILTER_IPADDR="<a href=\"javascript:blur();\"
			OnClick=\"Loadjs('squid.users-stats.currentmonth.website.php?zdate=$zDateEnc&field=ipaddr&value=$ipaddr&familysite={$_GET["value"]}')\"
			$styleHref>";
	
			$time=strtotime($zDate);
			$time_text=$tpl->_ENGINE_parse_body($q->time_to_date($time));
	
						
	
			$cell=array();
			$cell[]="<span $style>$FILTER_uid$uid</a></span>";
			$cell[]="<span $style>$FILTER_MAC$MAC</a></span>";
			$cell[]="<span $style>$FILTER_IPADDR$ipaddr</a></span>";
			$cell[]="<span $style>$size</a></span>";
			$cell[]="<span $style>$hits</a></span>";
	
	
			$data['rows'][] = array(
			'id' => $ligne['zmd5'],
			'cell' => $cell
			);
	}
	
	
			echo json_encode($data);
		}
		
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
