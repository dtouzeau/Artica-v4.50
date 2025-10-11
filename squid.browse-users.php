<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.squid.inc');

$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}

if(isset($_GET["categories"])){page();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["select-js"])){select_js();exit;}
if(isset($_GET["select"])){select();exit;}
js();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text(utf8_encode("{browse}:{members}"));
	$t=time();

$html="YahooWinBrowse('850','$page?categories=yes&callback={$_GET["callback"]}','$title');";
echo $html;
}

function select_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text(utf8_encode("{select}"));
	$t=time();
	
	$html="LoadWinORG('750','$page?select=yes&callback={$_GET["callback"]}&md5={$_GET["md5"]}','$title');";
	echo $html;	
	
}

function select(){
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM `UsersToTal` WHERE zMD5='{$_GET["md5"]}'"));
	
	$hostname=$ligne["hostname"];
	$client=$ligne["client"];
	$MAC=$ligne["MAC"];
	$uid=$ligne["uid"];
	$callback=$_GET["callback"];
	
	$html[]="
	<div style='width:98%' class=form>		
	<table style='width:100%'>
	";
	
	if($hostname<>null){
	$html[]="
	<tr>
		<td class=legend style='font-size:16px'>{hostname}:</td>
		<td style='font-size:16px'>$hostname</td>
		<td style='font-size:16px'>". button("{select}", "WinORGHide();$callback('hostname','$hostname')",16)."</td>
	</tr>
	<tr><td coslpan=3>&nbsp;</tr>	
	";
	}
	
	if($client<>null){
	$html[]="
	<tr>
		<td class=legend style='font-size:16px'>{ipaddr}:</td>
		<td style='font-size:16px'>$client</td>
		<td style='font-size:16px'>". button("{select}", "WinORGHide();$callback('client','$client')",16)."</td>
	</tr><tr><td coslpan=3>&nbsp;</tr>	
	";
	}
	
	if($MAC<>null){
	$html[]="
	<tr>
		<td class=legend style='font-size:16px'>{MAC}:</td>
		<td style='font-size:16px'>$MAC</td>
		<td style='font-size:16px'>". button("{select}", "WinORGHide();$callback('MAC','$MAC')",16)."</td>
	</tr><tr><td coslpan=3></tr>
	";
	}
	
	if($uid<>null){
	$html[]="
	<tr>
		<td class=legend style='font-size:16px'>{uid}:</td>
		<td style='font-size:16px'>$uid</td>
		<td style='font-size:16px'>". button("{select}", "WinORGHide();$callback('uid','$uid')",16)."</td>
	</tr><tr><td coslpan=3>&nbsp;</tr>	
	";
	}
	$html[]="
	</table>
	</div>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}



function page(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$websites=$tpl->_ENGINE_parse_body("{websites}");
	$add_website=$tpl->_ENGINE_parse_body("{add_website}");
	
	$select=$tpl->javascript_parse_text("{select}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$uid=$tpl->javascript_parse_text("{uid}");
	$MAC=$tpl->javascript_parse_text("{MAC}");
	$title=$tpl->javascript_parse_text("{reports_center}:: {members}");
	$progress=$tpl->javascript_parse_text("{progress}");
	$run=$tpl->javascript_parse_text("{run}");
	$size=$tpl->javascript_parse_text("{size}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$q=new mysql_squid_builder();

	
	$build=$tpl->javascript_parse_text("{rebuild_items}");
	$buttons="
	buttons : [
	{name: '<strong style=font-size:16px >$build</strong>', bclass: 'Reconf', onpress : BuildCache$t},
	],";
	
	
	$html="
	<table class='SQUID_USERZ' style='display: none' id='SQUID_USERZ' style='width:100%'></table>
	<script>
	$(document).ready(function(){
	$('#SQUID_USERZ').flexigrid({
	url: '$page?search=yes&callback={$_GET["callback"]}',
	dataType: 'json',
		colModel : [
		{display: '$hostname', name : 'hostname', width : 124, sortable : true, align: 'left'},
		{display: '$ipaddr', name : 'client', width : 79, sortable : true, align: 'left'},
		{display: '$MAC', name : 'MAC', width : 135, sortable : true, align: 'left'},
		{display: '$uid', name : 'uid', width : 124, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width : 79, sortable : true, align: 'right'},
		{display: '$hits', name : 'hits', width : 89, sortable : true, align: 'right'},
		{display: '$select;', name : 'explain', width : 70, sortable : false, align: 'center'},
		],
		$buttons
	searchitems : [
	{display: '$hostname', name : 'hostname'},
	{display: '$ipaddr', name : 'client'},
	{display: '$MAC', name : 'MAC'},
	{display: '$uid', name : 'uid'},
	
	],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: '350',
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});
	
	
	function BuildCache$t(){
	Loadjs('squid.browse-users.progress.php');
	}
	
	var xAddcategory$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#SQUID_MAIN_REPORTS').flexReload();
	$('#SQUID_USERZ').flexReload();
	}
	
	function Addcategory$t(categorykey){
	var XHR = new XHRConnection();
	XHR.appendData('ID','{$_GET["ID"]}');
	XHR.appendData('categorykey',categorykey);
	XHR.sendAndLoad('$page', 'POST',xAddcategory$t);
	}
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$table="UsersToTal";
	$q=new mysql_squid_builder();
	$FORCE=1;
	$t=$_GET["t"];


	$total=0;
	if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("no data [".__LINE__."]",0);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];

	}else{
		if(strlen($FORCE)>2){
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE";
			$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_events"));
			$total = $ligne["TCOUNT"];
		}else{
			$total = $q->COUNT_ROWS($table, "artica_events");
		}
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=100;}


	$pageStart = ($page-1)*$rp;
	if($pageStart<0){$pageStart=0;}
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM `$table` WHERE $FORCE $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",0);}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$CurrentPage=CurrentPageName();

	if(mysqli_num_rows($results)==0){json_error_show("no data");}
	$searchstring=string_to_flexquery();


	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	$q1=new mysql();
	$t=time();

	$fontsize=16;

	$report=$tpl->javascript_parse_text("{report}");
	$category=$tpl->javascript_parse_text("{category}");
	$from_the_last_time=$tpl->javascript_parse_text("{from_the_last_time}");
	$report_not_categorized_text=$tpl->javascript_parse_text("{report_not_categorized}");
	$error_engine_categorization=$tpl->javascript_parse_text("{error_engine_categorization}");

	$span="<span style='font-size:{$fontsize}px'>";



	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$zmd5=$ligne["zMD5"];
		$hostname=$ligne["hostname"];
		$client=$ligne["client"];
		$MAC=$ligne["MAC"];
		$uid=$ligne["uid"];
		
		$size=FormatBytes($ligne["size"]/1024);
		$hits=FormatNumber($ligne["hits"]);
		$select=imgsimple("arrow-right-32.png",null,"Loadjs('$MyPage?select-js=yes&callback={$_GET["callback"]}&md5=$zmd5')");
	
		

		$data['rows'][] = array(
				'id' => $zmd5,
				'cell' => array(
						"$span$hostname</a></span>",
						"$span$client</a></span>",
						"$span$MAC</a></span>",
						"$span$uid</a></span>",
						
						"$span$size</a></span>",
						"$span$hits</a></span>",
						"$select",

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

