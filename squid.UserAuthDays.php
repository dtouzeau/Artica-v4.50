<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}
	if(isset($_GET["search"])){tableau();exit;}
	if(isset($_GET["member-list"])){liste();exit;}
	if(isset($_GET["graph"])){graph();exit;}
	js();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$size=850;
	$field=$_GET["field"];
	$value=$_GET["value"];


	$title=$tpl->_ENGINE_parse_body("{member} $field $value");
	$html="YahooSetupControl('$size','$page?search=yes&field=$field&value=".urlencode($value)."','$title')";
	echo $html;
}

function tableau(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$field=$_GET["field"];
	$value=$_GET["value"];
	
	$member=$tpl->javascript_parse_text("{member}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$size=$tpl->javascript_parse_text("{size}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$date=$tpl->javascript_parse_text("{date}");
	$title=$tpl->javascript_parse_text("{days}::{{$field}} $value");
	$_GET["word"]=urlencode($_GET["word"]);
	$html="
	<div id='graph-$t' style='width:828px;heigh:450px'></div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	<script>
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?member-list=yes&field=$field&value=".urlencode($value)."',
	dataType: 'json',
	colModel : [
	{display: '$date', name : 'zDate', width :346, sortable : true, align: 'left'},
	{display: '$size', name : 'size', width : 186, sortable : true, align: 'left'},
	{display: '$hits', name : 'hits', width : 186, sortable : true, align: 'left'},
	
	
	],
	
	searchitems : [
		{display: '$date', name : 'zDate'},
		
		],	
		sortname: 'zDate',
		sortorder: 'desc',
		usepager: true,
		title: '$title',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: 828,
		height: 400,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200,500]	
	});
	});
	
	
	Loadjs('$page?graph=yes&field=$field&value=".urlencode($value)."&container=graph-$t');
	</script>
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function liste(){
	$Mypage=CurrentPageName();
	$table="UserAuthDays";
	$page=1;
	$data = array();
	$data['rows'] = array();
	$field=$_GET["field"];
	$value=$_GET["value"];
	$tpl=new templates();
	
	$q=new mysql_squid_builder();
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	$table="(SELECT $field,zDate,SUM(hits) as hits, SUM(QuerySize) as size FROM $table 
	GROUP BY  $field,zDate HAVING $field='$value' ) as t";
	
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"]+1;	
	}
	$rp=50;
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	
	
	$data['page'] = $page;
	$data['total'] = $total;

	$style="style='font-size:15px'";
	$stylehReF="style='font-size:15px;text-decoration:underline'";
	while ($ligne = mysqli_fetch_assoc($results)) {
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		$ligne["hits"]=FormatNumber($ligne["hits"]);
		
		
		$zdtTime=strtotime($ligne["zDate"]);
		$tableDest=date("Ymd",$zdtTime)."_hour";
		$zdtTimeT=$tpl->_ENGINE_parse_body(date("{l} {F} d",$zdtTime))." [ ". date("Y-m-d",$zdtTime)." ]";
		$uriUid="Loadjs('squid.traffic.statistics.day.user.php?user=$value&field=$field&table=$tableDest');";
		
	$data['rows'][] = array(
	'id' => md5(serialize($ligne)),
	'cell' => array(
			"<span $style><a href=\"javascript:blur();\" OnClick=\"javascript:$uriUid\" $stylehReF>$zdtTimeT</a></span>",
			"<span $style>{$ligne["size"]}</span>",
			"<span $style>{$ligne["hits"]}</span>",
			
	)
	);
	
	
	}
	
	echo json_encode($data);	
	
	
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}

function graph(){
	
	$Mypage=CurrentPageName();
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$table="UserAuthDays";
	$page=1;
	$data = array();
	$data['rows'] = array();
	$field=$_GET["field"];
	$value=$_GET["value"];
	
	$sql="SELECT $field,zDate,SUM(QuerySize) as size FROM $table 
	GROUP BY  $field,zDate HAVING $field='$value'";
	
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysqli_fetch_assoc($results)) {
		$zdtTime=strtotime($ligne["zDate"]);
		$xdata[]=date("Y-m-d",$zdtTime);
		$ydata[]=round(($ligne["size"]/1024)/1000);
		
	}
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="$value: {downloaded_flow}";
	$highcharts->yAxisTtitle="{bandwith} MB";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{bandwith}"=>$ydata);
	
	echo $highcharts->BuildChart();
	
	
}