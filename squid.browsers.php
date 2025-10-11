<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "<H2>$alert</H2>";
	die("DIE " .__FILE__." Line: ".__LINE__);	
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["list"])){items();exit;}
js();

function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{browsers}");
	$html="YahooWinBrowse('650','$page?popup=yes&ShowOnly={$_GET["ShowOnly"]}','$title')";
	echo $html;
	
}

function popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	
	$sock=new sockets();
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	if($SquidPerformance>1){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{artica_statistics_disabled}"));
		return;
	}
	
	$q=new mysql_squid_builder();	
	$q->CheckTables();
	$type=$tpl->_ENGINE_parse_body("{type}");
	$browsers=$tpl->_ENGINE_parse_body("{browsers}");
	
	$items=$tpl->_ENGINE_parse_body("{items}");
	$add=$tpl->_ENGINE_parse_body("{add}");
	$delete_group_ask=$tpl->javascript_parse_text("{inputbox delete group}");
	$title=$tpl->javascript_parse_text("{browsers}");
	$t=time();		
	$table_width=630;
	$table_height=450;

	$buttons="buttons : [
	{name: '$new_group', bclass: 'add', onpress : AddGroup},
		],	";
	$buttons=null;
	
	$html=$tpl->_ENGINE_parse_body("")."
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?list=yes',
	dataType: 'json',
	colModel : [
		{display: '$browsers', name : 'pattern', width : 904, sortable : true, align: 'left'},
		{display: '$add', name : 'pattern', width : 81, sortable : false, align: 'center'},
		
		
	],

	searchitems : [
		{display: '$browsers', name : 'pattern'},
		],
	sortname: 'pattern',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: $table_height,
	singleSelect: true
	
	});   
});
</script>
	";
	echo $html;	

}
function items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$RULEID=$_GET["RULEID"];
	
	$search='%';
	$table="UserAgents";
	$page=1;

	if($q->COUNT_ROWS($table)==0){json_error_show("No data");}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show("$q->mysql_error");}
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table`";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show("$q->mysql_error");}
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysqli_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));json_encode($data);return;}
	
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$pattern_enc=urlencode($ligne["pattern"]);
		
		
	$data['rows'][] = array(
		'id' => md5($ligne["pattern"]),
		'cell' => array(
		"<span style='font-size:14px;'>{$ligne["pattern"]}</span>",
		imgtootltip("plus-24.png",null,"Loadjs('squid.browsers-rules.php?ruleid-js=0&pattern=$pattern_enc');")."</span>",
		)
		);
	}
	
	
	echo json_encode($data);	
}