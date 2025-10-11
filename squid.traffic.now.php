<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["search"])){search();exit;}
	js();
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$NameTitle=base64_decode($_GET["NameTitle"]);
	$title="$NameTitle::{$_GET["field"]}::{$_GET["value"]}";
	$html="YahooWin('806','$page?popup=yes&field={$_GET["field"]}&value={$_GET["value"]}','$title')";
	echo $html;
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$webservers=$tpl->_ENGINE_parse_body("{webservers}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$time=$tpl->_ENGINE_parse_body("{time}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$country=$tpl->_ENGINE_parse_body("{country}");
	$url=$tpl->_ENGINE_parse_body("{url}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	
	//javascript:
	
	if($_GET["field"]=="MAC"){
		$buttons="
		buttons : [
		{name: '$computer_infos', bclass: 'add', onpress : ComputerInfos'},
		],";
			
	}
	
	
	$title=$tpl->_ENGINE_parse_body("{$_GET["field"]}::{$_GET["value"]}::{today}: {requests} {since} ".date("H")."h");
	
	$t=time();
	$html="
	<div style='margin-top:-10px;margin-left:-15px'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</div>
	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?search=yes&field={$_GET["field"]}&value={$_GET["value"]}',
	dataType: 'json',
	colModel : [
		{display: '$time', name : 'zDate', width :53, sortable : true, align: 'left'},
		{display: '$country', name : 'country', width : 92, sortable : false, align: 'left'},
		{display: '$webservers', name : 'sitename', width : 135, sortable : false, align: 'left'},
		{display: '$url', name : 'uri', width : 386, sortable : false, align: 'left'},
		{display: '$size', name : 'Querysize', width : 60, sortable : true, align: 'left'}

		],
	
	searchitems : [
		{display: '$webservers', name : 'sitename'}
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 807,
	height: 420,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function ComputerInfos(){
	Loadjs('squid.nodes.php?node-infos-js=yes&MAC={$_GET["value"]}',true);
}

</script>
	
	
	";
	
	echo $html;
	

}

function search(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$search=$_GET["search"];
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table="squidhour_".date("YmdH");
	$page=1;
	$ORDER="ORDER BY ID DESC";	
	$FORCE_FILTER=" AND `{$_GET["field"]}`='{$_GET["value"]}'";
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}	
	if(isset($_POST['rp'])) {$rp = $_POST['rp'];}

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE (`uri` LIKE '$search') $FORCE_FILTER";
		$QUERY="AND (`uri` LIKE '$search')";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
		
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";	
	
		
	
	
	if($q->COUNT_ROWS($table)==0){return;}
	
	$sql="SELECT *,DATE_FORMAT(zDate,'%H:%i:%s') as ttime  FROM `$table` WHERE 1 $QUERY $FORCE_FILTER $ORDER $limitSql";
	$results=$q->QUERY_SQL($sql);
	
//&nbsp;|&nbsp;{$ligne["CLIENT"]}&nbsp;|&nbsp;{$ligne["uid"]}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql);
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	while ($ligne = mysqli_fetch_assoc($results)) {
		
		
		if($ligne["QuerySize"]>1024){
			$ligne["QuerySize"]=FormatBytes($ligne["QuerySize"]/1024);
		}else{
			$ligne["QuerySize"]="{$ligne["QuerySize"]} Bytes";
		}
	
		$c++;
		$today=date("Y-m-d");
		$familysite=$q->GetFamilySites($ligne["sitename"]);
		$js="squid.traffic.statistics.days.php?today-zoom=yes&type=req&familysite=$familysite&day=$today";
		
		
		
		
		$data['rows'][] = array(
			'id' => "{$ligne["ID"]}",
			'cell' => array($ligne["ttime"], $ligne["country"], 
		
		
		
		"<a href=\"#\" style='text-decoration:underline' OnClick=\"Loadjs('$js');\">{$ligne["sitename"]}</a>",
		$ligne["uri"],$ligne["QuerySize"],"add")
		);
	}
	echo json_encode($data);	
}