<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.squid.builder.php');


$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die("DIE " .__FILE__." Line: ".__LINE__);
}


if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["list"])){showlist();exit;}
if(isset($_GET["refresh"])){refresh();exit;}

popup();

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();

	$zdate=$tpl->javascript_parse_text("{time}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$requests=$tpl->javascript_parse_text("{requests}");
	$connections=$tpl->javascript_parse_text("{connections}");
	$uid=$tpl->javascript_parse_text("{uid}");
	$errors=$tpl->javascript_parse_text("{errors}");
	$refresh=$tpl->javascript_parse_text("{refresh}");
	$TCP_HIT=$tpl->javascript_parse_text("{cached}");
	$TCP_MISS=$tpl->javascript_parse_text("{not_cached}");
	$TCP_REDIRECT=$tpl->javascript_parse_text("{REDIRECT}");
	$TCP_TUNNEL=$tpl->javascript_parse_text("{ssl}");
	// ipaddr        | familysite            | servername                                | uid               | MAC               | size
	$t=time();
	$ActiveRequestsR=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/active_requests.inc"));
	$ActiveRequestsNumber=count($ActiveRequestsR["CON"]);
	$title=$tpl->javascript_parse_text("{active_clients}");
	$html="
	<table class='CLIENT_LIST_TABLE' style='display:none' id='CLIENT_LIST_TABLE'></table>
	<script>
	function StartLogsSquidTable$t(){

	$('#CLIENT_LIST_TABLE').flexigrid({
	url: '$page?list=yes',
	dataType: 'json',
	colModel : [
	{display: '<strong style=font-size:18px>$uid</strong>', name : 'uid', width : 211, sortable : false, align: 'left'},
	{display: '<strong style=font-size:18px>$ipaddr</strong>', name : 'ipaddr', width :139, sortable : false, align: 'left'},
	{display: '<strong style=font-size:18px>$connections</strong>', name : 'CUR_CNX', width : 139, sortable : false, align: 'right'},
	{display: '<strong style=font-size:18px>$requests</strong>', name : 'RQS', width : 139, sortable : false, align: 'right'},
	{display: '<strong style=font-size:18px>$TCP_HIT</strong>', name : 'TCP_HIT', width : 139, sortable : false, align: 'right'},
	{display: '<strong style=font-size:18px>$TCP_MISS</strong>', name : 'TCP_MISS', width : 139, sortable : false, align: 'right'},
	{display: '<strong style=font-size:18px>$TCP_REDIRECT</strong>', name : 'TCP_REDIRECT', width : 139, sortable : false, align: 'right'},
	{display: '<strong style=font-size:18px>$TCP_TUNNEL</strong>', name : 'TCP_TUNNEL', width : 139, sortable : false, align: 'right'},
	{display: '<strong style=font-size:18px>$errors</strong>', name : 'TAG_NONE', width : 139, sortable : false, align: 'right'},
	],

	searchitems : [
	{display: '$uid', name : 'uid'},
	{display: '$ipaddr', name : 'ipaddr'},
	

	],

	buttons : [
	{name: '<strong style=font-size:18px>$refresh</strong>', bclass: 'Reload', onpress : refresh$t},
	{separator: true},
	{name: 'Excel', bclass : 'excel', onpress : exportTo},
	{separator: true},
	{name: 'CSV', bclass : 'csv', onpress : exportTo},
	],


	sortname: 'CUR_CNX',
	sortorder: 'desc',
	usepager: true,
	title: '<span id=title-$t style=font-size:30px>$title</span>',
	useRp: true,
	rp: 500,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500,1000,1500]

});

}

function refresh$t(){
$('#CLIENT_LIST_TABLE').flexReload();

}

StartLogsSquidTable$t();
</script>
";
	echo $html;
}
function showlist(){

	$q=new mysql_squid_builder();
	$table="mgr_client_list";
	if(!$q->TABLE_EXISTS($table)){$q->CheckReportTable();}
	$tpl=new templates();
	$page=1;
	$FORCE_FILTER=null;
	$total=0;

	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}

	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error");}


	if(mysqli_num_rows($results)==0){
		json_error_show("No data, $sql");
	}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	while ($ligne = mysqli_fetch_assoc($results)) {
		$md5=md5(serialize($line));
		$CUR_CNX=numberFormat($ligne["CUR_CNX"],0,""," ");
		$RQS=numberFormat($ligne["RQS"],0,""," ");
		$TCP_HIT=numberFormat($ligne["TCP_HIT"],0,""," ");
		$TCP_MISS=numberFormat($ligne["TCP_MISS"],0,""," ");
		$TCP_REDIRECT=numberFormat($ligne["TCP_REDIRECT"],0,""," ");
		$TCP_TUNNEL=numberFormat($ligne["TCP_TUNNEL"],0,""," ");
		$TAG_NONE=numberFormat($ligne["TAG_NONE"],0,""," ");
	
		
		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(
						"<span style='font-size:16px'>{$ligne["uid"]}</a></span>",
						"<span style='font-size:16px'>{$ligne["ipaddr"]}</a></span>",
						"<span style='font-size:16px'>$CUR_CNX</span>",
						"<span style='font-size:16px'>$RQS</span>",
						"<span style='font-size:16px'>$TCP_HIT</span>",
						"<span style='font-size:16px'>$TCP_MISS</span>",
						"<span style='font-size:16px'>$TCP_REDIRECT</span>",
						"<span style='font-size:16px'>$TCP_TUNNEL</span>",
						"<span style='font-size:16px'>$TAG_NONE</span>",

							
				)
		);
	}


	echo json_encode($data);
}
?>
