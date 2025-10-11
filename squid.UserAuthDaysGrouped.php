<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}
	if(isset($_GET["search"])){tableau();exit;}
	if(isset($_GET["member-list"])){liste();exit;}

	js();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$size=850;
	$_GET["search-js"]=url_decode_special_tool($_GET["search-js"]);
	$search=urlencode($_GET["search-js"]);

	$title=$tpl->_ENGINE_parse_body("{search}");
	$html="YahooWinBrowse('$size','$page?search=yes&word=$search','$title')";
	echo $html;
}

function tableau(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$hostname=$tpl->javascript_parse_text("{hostname}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$size=$tpl->javascript_parse_text("{size}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$member=$tpl->javascript_parse_text("{member}");
	$title=$tpl->javascript_parse_text("{search}::{members}");
	$_GET["word"]=urlencode($_GET["word"]);
	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	<script>
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?member-list=yes&default={$_GET["word"]}',
	dataType: 'json',
	colModel : [
	{display: '$member', name : 'uid', width :109, sortable : true, true: 'left'},
	{display: 'MAC', name : 'MAC', width : 124, sortable : false, true: 'left'},
	{display: '$hostname', name : 'hostname', width : 229, sortable : true, align: 'left'},
	{display: '$ipaddr', name : 'ipaddr', width : 100, sortable : true, align: 'left'},
	{display: '$size', name : 'size', width : 83, sortable : true, align: 'left'},
	{display: '$hits', name : 'hits', width : 90, sortable : true, align: 'left'},
	
	
	],
	
	searchitems : [
		{display: '$member', name : 'uid'},
		{display: 'MAC', name : 'MAC'},
		{display: '$hostname', name : 'hostname'},
		{display: '$ipaddr', name : 'ipaddr'},
		
		],	
		sortname: 'hits',
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
	</script>
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function liste(){
	$Mypage=CurrentPageName();
	$table="UserAuthDaysGrouped";
	$page=1;
	$data = array();
	$data['rows'] = array();

	
	
	$q=new mysql_squid_builder();
	
	if(!$q->TABLE_EXISTS($table)){
		json_error_show("$table No such table...");
	}
	
	if($q->COUNT_ROWS($table)==0){
		json_error_show("$table No data..");
	}
	
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	$table="(SELECT uid,MAC,hostname,ipaddr,SUM(hits) as hits, SUM(QuerySize) as size FROM $table GROUP BY  uid,mac,hostname,ipaddr ) as t";
	
	$searchstring=string_to_flexquery();
	if($searchstring==null){
		$searchstring=$_GET["default"];
		if($searchstring<>null){
			$searchstring=str_replace("*", "%", $searchstring);
			$OP=" = ";
			if(strpos(" $searchstring", "%")>0){$OP="LIKE";}
			$searchstring="AND ( (`uid` $OP '$searchstring') OR (`MAC` $OP '$searchstring') OR (`ipaddr` $OP '$searchstring') OR (`hostname` $OP '$searchstring'))";
		}
	}

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
	
	if(mysqli_num_rows($results)==0){
		json_error_show("Query No data.. $sql");
	}	
	
	$data['page'] = $page;
	$data['total'] = $total;

	$style="style='font-size:15px'";
	$stylehReF="style='font-size:15px;text-decoration:underline'";
	while ($ligne = mysqli_fetch_assoc($results)) {
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		$ligne["hits"]=FormatNumber($ligne["hits"]);
		
		
		$uriUid="Loadjs('squid.UserAuthDays.php?field=uid&value=".urlencode($ligne["uid"])."')";
		$uriMAC="Loadjs('squid.UserAuthDays.php?field=MAC&value=".urlencode($ligne["MAC"])."')";
		$urihostname="Loadjs('squid.UserAuthDays.php?field=hostname&value=".urlencode($ligne["hostname"])."')";
		$uriipaddr="Loadjs('squid.UserAuthDays.php?field=ipaddr&value=".urlencode($ligne["ipaddr"])."')";
		
		
		
	$data['rows'][] = array(
	'id' => md5(serialize($ligne)),
	'cell' => array(
			"<span $style><a href=\"javascript:blur();\" OnClick=\"javascript:$uriUid\" $stylehReF>{$ligne["uid"]}</a></span>",
			"<span $style><a href=\"javascript:blur();\" OnClick=\"javascript:$uriMAC\" $stylehReF>{$ligne["MAC"]}</a></span>",
			"<span $style><a href=\"javascript:blur();\" OnClick=\"javascript:$urihostname\" $stylehReF>{$ligne["hostname"]}</a></span>",
			"<span $style><a href=\"javascript:blur();\" OnClick=\"javascript:$uriipaddr\" $stylehReF>{$ligne["ipaddr"]}</a></span>",
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

