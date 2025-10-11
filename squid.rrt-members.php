<?php

if($argv[1]=="--verbose"){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["verbose"])){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["AS_ROOT"]=false;
if(function_exists("posix_getuid")){
	if(posix_getuid()==0){
		$GLOBALS["AS_ROOT"]=true;
		include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
		include_once(dirname(__FILE__)."/framework/frame.class.inc");
		include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
		include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
		include_once(dirname(__FILE__)."/framework/class.settings.inc");
	}}

	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.html.pages.inc');
	include_once('ressources/class.mysql.inc');
	if(isset($_GET["list"])){main_items();exit;}
	
	main_table();
function main_table(){
$page=CurrentPageName();
$tpl=new templates();
$q=new mysql_squid_builder();
$q->CheckTables();
$type=$tpl->_ENGINE_parse_body("{type}");
$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");

$sitename=$tpl->_ENGINE_parse_body("{websites}");
$uid=$tpl->_ENGINE_parse_body("{member}");
$MAC=$tpl->javascript_parse_text("{MAC}");
$size=$tpl->javascript_parse_text("{size}");
$title=$tpl->javascript_parse_text("{realtime_flow} {members} {today} {time}:".date("H")."h");
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
		{display: '$sitename', name : 'sitename', width : 230, sortable : true, align: 'left'},
		{display: '$ipaddr', name : 'ipaddr', width : 120, sortable : true, align: 'left'},
		{display: '$uid', name : 'uid', width : 230, sortable : true, align: 'left'},
		{display: '$MAC', name : 'MAC', width : 230, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width : 150, sortable : true, align: 'right'},


		],

		searchitems : [
		{display: '$ipaddr', name : 'ipaddr'},
		{display: '$uid', name : 'uid'},
		{display: '$MAC', name : 'MAC'},
		],
		sortname: 'size',
		sortorder: 'desc',
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

function main_items(){
	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$date=date("YmdH");
	$table="RTTH_{$date}";
	if($q->COUNT_ROWS($table)==0){json_error_show("No data");}
	$catz=new mysql_catz();
	$search='%';
	$table="(SELECT SUM(size) as size,ipaddr,uid,MAC,sitename FROM $table GROUP BY ipaddr,uid,MAC,sitename) as t";
	$page=1;
	
	
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show("$q->mysql_error");}
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show("$q->mysql_error");}
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysqli_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));json_encode($data);return;}
	
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$category=null;
	
	$ligne["size"]=FormatBytes($ligne["size"]/1024);
	$category=$catz->GetMemoryCache($ligne["sitename"],true);
	if($category<>null){$category="<br><strong style='color:#D2904A;font-size:12px'>($category)</strong>";}
	
	$data['rows'][] = array(
		'id' => md5($ligne["pattern"]),
				'cell' => array(
				"<span style='font-size:14px;'>{$ligne["sitename"]}$category</span>",
				"<span style='font-size:14px;'>{$ligne["ipaddr"]}</span>",
				"<span style='font-size:14px;'>{$ligne["uid"]}</span>",
				"<span style='font-size:14px;'>{$ligne["MAC"]}</span>",
				"<span style='font-size:14px;'>{$ligne["size"]}</span>",
				
		)
		);
	}
	
	
	echo json_encode($data);
}