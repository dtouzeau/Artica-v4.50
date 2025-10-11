<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.blackboxes.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsAnAdministratorGeneric){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die("DIE " .__FILE__." Line: ".__LINE__);	
}


if(isset($_GET["rows-table"])){rows_table();exit;}
table();


function table(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$netmask=$tpl->_ENGINE_parse_body("{netmask}");
	$gateway=$tpl->_ENGINE_parse_body("{gateway}");
	$macaddr=$tpl->_ENGINE_parse_body("{ComputerMacAddress}");
	$TB_HEIGHT=450;
	$TABLE_WIDTH=836;
	$TB2_WIDTH=400;
	$ROW1_WIDTH=629;
	$ROW2_WIDTH=163;
	
	
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
	url: '$page?rows-table=yes&nodeid={$_GET["nodeid"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'ipaddr1', width :35, sortable : false, align: 'left'},
		{display: '$ipaddr', name : 'ipaddr', width :120, sortable : true, align: 'left'},
		{display: '$netmask', name : 'netmask', width :120, sortable : true, align: 'left'},
		{display: '$gateway', name : 'gateway', width :120, sortable : true, align: 'left'},
		{display: '$macaddr', name : 'mac', width :160, sortable : true, align: 'left'},
		
		
		
	],
	
	searchitems : [
		{display: '$ipaddr', name : 'ipaddr'},
		],	
	
	sortname: '	ipaddr',
	sortorder: 'asc',
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
	
	$search='%';
	$table="nics";
	$page=1;
	$ORDER="ORDER BY nics ASC";
	
	$total=0;
	if($q->COUNT_ROWS($table)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND ((`{$_POST["qtype"]}` LIKE '$search' AND nodeid=$nodeid) OR (`mac` LIKE '$search' AND nodeid=$nodeid))";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE nodeid=$nodeid";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE nodeid=$nodeid $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){
		
	}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	
	
	//if(mysqli_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		
		
		
		
	$data['rows'][] = array(
		'id' => $ligne['mac'],
		'cell' => array(
			"<img src='img/folder-network-24.png'>",
			"<span style='font-size:16px'>{$ligne["ipaddr"]}</span>",
			"<span style='font-size:16px'>{$ligne["netmask"]}</span>",
			"<span style='font-size:16px'>{$ligne["gateway"]}</span>",
			"<span style='font-size:16px'>{$ligne["mac"]}</span>",			 
	
		)
		);
	}
	
	
echo json_encode($data);		

}