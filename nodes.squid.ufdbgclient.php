<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.blackboxes.inc');
	include_once('ressources/class.squid.inc');
	
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){die("NO PRIVS");}
	if(isset($_GET["rows-table"])){rows_table();exit;}
table();


function table(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	
	$description=$tpl->_ENGINE_parse_body("{description}");
	$zDate=$tpl->_ENGINE_parse_body("{zDate}");
	$TB_HEIGHT=450;
	$TABLE_WIDTH=807;
	$TB2_WIDTH=400;
	$ROW1_WIDTH=157;
	$ROW2_WIDTH=607;
	
	
	$t=time();
	
	$buttons="
	buttons : [
	{name: '$empty', bclass: 'Delz', onpress : EmptyEvents},
	
		],	";
	$html="
	<table class='node-table-$t' style='display: none' id='node-table-$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#node-table-$t').flexigrid({
	url: '$page?rows-table=yes&nodeid={$_GET["nodeid"]}&hostid={$_GET["hostid"]}',
	dataType: 'json',
	colModel : [
		{display: '$zDate', name : 'zDate', width :$ROW1_WIDTH, sortable : true, align: 'left'},
		{display: '$description', name : 'line', width :$ROW2_WIDTH, sortable : true, align: 'left'},
	],
	
	searchitems : [
		{display: '$description', name : 'line'},
		],	
	
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TABLE_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true
	
	});   
});

	
	
</script>";
	
	echo $html;	
	
}
function rows_table(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_blackbox();
	$nodeid=$_GET["nodeid"];
	$hostid=$_GET["hostid"];
	$q=new mysql_blackbox();
	
	$sql="SELECT UfdbClientLogs FROM nodes WHERE hostid='$hostid'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){json_error_show("$q->mysql_error",1);}
	
	if(strlen($ligne["UfdbClientLogs"])==0){
		json_error_show("UfdbClientLogs No content...",1);
	}
	
	$base=base64_decode($ligne["UfdbClientLogs"]);
	$array=unserialize($base);
	
	if(!is_array($array)){json_error_show(strlen($ligne["UfdbClientLogs"])." bytes...Not an array...",1);}
	
	$page=1;
	if(count($array)==0){json_error_show("No rows",1);}	
	krsort($array);
	
	
	$total=count($array);
	$search=string_to_regex($_POST["query"]);
	

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$c=0;
	
	while (list ($index, $ligne) = each ($array) ){
		if(trim($ligne)==null){continue;}
		$md5=md5($ligne);
		$color="black";
		if(preg_match("#([0-9\-\s\:]+)\s+\[#", $ligne,$re)){
			$date=$re[1];
			$ligne=str_replace($date, "", $ligne);
		}
		if($search<>null){if(!preg_match("#$search#", $ligne)){continue;}}
		if(preg_match("#(crashing|failed|No such|FATAL|abnormally|WARNING)#", $ligne["line"])){$color="red";}		
		$c++;
		$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
			"<span style='font-size:14px;color:$color'>$date</span>",
			"<span style='font-size:14px;color:$color'>$ligne</span>",
		 
	
		)
		);
	}
	
	$data['total'] = $c;
	echo json_encode($data);		

}