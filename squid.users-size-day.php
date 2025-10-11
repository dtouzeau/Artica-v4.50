<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.squid.builder.php');
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["list"])){showlist();exit;}
js();



function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$t=time();
	$q=new mysql_squid_builder();
	
	$title=$tpl->_ENGINE_parse_body("{downloaded_size}::" .$q->time_to_date(time()));
	$page=CurrentPageName();
	$html="
	function Start$t(){
		YahooWin5('950','$page?popup=yes','$title')
	}
	
	Start$t();";
	
	echo $html;
	
	
}


function popup(){
$tpl=new templates();	
$page=CurrentPageName();

$zdate=$tpl->javascript_parse_text("{time}");
$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
$mac=$tpl->javascript_parse_text("{MAC}");
$familysite=$tpl->javascript_parse_text("{familysite}");
$uid=$tpl->javascript_parse_text("{uid}");
$size=$tpl->javascript_parse_text("{size}");
// ipaddr        | familysite            | servername                                | uid               | MAC               | size
$t=time();	
$q=new mysql_squid_builder();
$title=$tpl->javascript_parse_text("{downloaded_size}::" .$q->time_to_date(time()));
$html="
<table class='flexRT$t' style='display:none' id='flexRT$t'></table>
<script>
function StartLogsSquidTable$t(){
	
	$('#flexRT$t').flexigrid({
		url: '$page?list=yes',
		dataType: 'json',
		colModel : [
			{display: '$uid', name : 'uid', width : 141, sortable : true, align: 'left'},
			{display: '$ipaddr', name : 'ipaddr', width :95, sortable : true, align: 'left'},
			{display: '$mac', name : 'MAC', width : 122, sortable : true, align: 'left'},
			{display: '$familysite', name : 'familysite', width : 349, sortable : true, align: 'left'},
			{display: '$size', name : 'size', width : 142, sortable : true, align: 'left'},
			],
	
		searchitems : [
			{display: '$ipaddr', name : 'ipaddr'},
			{display: '$familysite', name : 'familysite'},
			{display: '$uid', name : 'uid'},
			{display: '$mac', name : 'mac'},
			],
		sortname: 'size',
		sortorder: 'desc',
		usepager: true,
		title: '$title',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: 935,
		height: 600,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200,500,1000,1500]
		
		});   

}

StartLogsSquidTable$t();
</script>			
";
echo $html;	
}


function showlist(){
	
	$q=new mysql_squid_builder();
	$tablesrc="quotaday_".date("Ymd");
	$table="(SELECT SUM(size) as size,ipaddr,familysite,uid,MAC FROM `$tablesrc` GROUP BY ipaddr,familysite,uid,MAC) as t";
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$q=new mysql_squid_builder();
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
	
		if(!$q->ok){json_error_show($q->mysql_error,1);}
	
		//if(mysqli_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
		while ($ligne = mysqli_fetch_assoc($results)) {
			$ipaddr=$ligne["ipaddr"];
			$mac=$ligne["MAC"];
			$familysite=$ligne["familysite"];
			$uid=$ligne["uid"];
			$size=FormatBytes($ligne["size"]/1024);
		$data['rows'][] = array(
		'id' => md5(serialize($ligne)),
		'cell' => array("<span style='font-size:14px'>$uid</span>",
				"<span style='font-size:14px'>$ipaddr</span>",
				"<span style='font-size:14px'>$mac</span>",
				"<span style='font-size:14px'>$familysite</span>",
				"<span style='font-size:14px'>$size</span>" )
		);
	}
	
	
	echo json_encode($data);	
}
