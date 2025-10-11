<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}	
	
	if(isset($_GET["tables-list"])){databases_list_json();exit;}
	if(isset($_GET["mysql-check"])){mysql_check();exit;}
	if(isset($_POST["mysql-empty"])){mysql_empty();exit;}
	
	
	
database_table_list();	
	function database_table_list(){
		$page=CurrentPageName();
		$users=new usersMenus();
		$tpl=new templates();
		$database=$tpl->_ENGINE_parse_body("{database}");
		$tables_number=$tpl->_ENGINE_parse_body("{tables_number}");
		$database_size=$tpl->_ENGINE_parse_body("{database_size}");
		$perfrom_mysqlcheck=$tpl->javascript_parse_text("{perform_mysql_check}");
		$table=$tpl->_ENGINE_parse_body("{table}");
		$table_size=$tpl->_ENGINE_parse_body("{table_size}");
		$rows_number=$tpl->_ENGINE_parse_body("{rows_number}");
		$empty=$tpl->_ENGINE_parse_body("{empty}");
		$perfrom_empty=$tpl->javascript_parse_text("{perform_empty_ask}");
		$tables=$tpl->javascript_parse_text("{tables}");
		$rescan=$tpl->_ENGINE_parse_body("{rescan}");
		$privileges=$tpl->_ENGINE_parse_body("{privileges}");
		$restore=$tpl->_ENGINE_parse_body("{restore}");
		$delete=$tpl->_ENGINE_parse_body("{empty}");
		$t=time();
		
		
		$title=$tpl->_ENGINE_parse_body("{browse_mysql_server_text}");
	
		$buttons="
		buttons : [
		{name: '<b>$rescan</b>', bclass: 'Reload', onpress : Rescan$t},
		{name: '<b>$privileges</b>', bclass: 'Group', onpress : Privileges$t},
		{name: '<b>$restore</b>', bclass: 'Restore', onpress : DB{$_GET["instance-id"]}Restore },
		],";
		$buttons=null;
		$html="
		<div id='anim-$t'></div>
		<table class='mysql-table-$t' style='display: none' id='mysql-table-$t' style='width:100%;margin:-10px'></table>
		<script>
		memedb='';
		$(document).ready(function(){
		$('#mysql-table-$t').flexigrid({
		url: '$page?tables-list=yes&t=$t&databasename={$_GET["database"]}&instance-id={$_GET["instance-id"]}&t=$t',
		dataType: 'json',
	
		colModel : [
		{display: '$table', name : 'TABLE_NAME', width : 408, sortable : true, align: 'left'},
		{display: '$table_size', name : 'DATA_LENGTH', width :160, sortable : true, align: 'right'},
		{display: '$rows_number', name : 'TABLE_ROWS', width :171, sortable : true, align: 'right'},
		{display: 'Mysqlcheck', name : 'none1', width : 106, sortable : false, align: 'center'},
		{display: '$empty', name : 'none2', width : 106, sortable : false, align: 'center'},
		
	
	
	
		],
	
		$buttons
	
		searchitems : [
		{display: '$table', name : 'TABLE_NAME'},
	
		],
		sortname: 'TABLE_ROWS',
		sortorder: 'desc',
		usepager: true,
		title: '$title',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 450,
		singleSelect: true
	
	});
	});
	
	var x_MysqlCheck= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	}
	
	var x_Rescan$t= function (obj) {
	document.getElementById('anim-$t').innerHTML='';
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	$('#mysql-table-$t').flexReload();
	}
	
	
	function DB{$_GET["instance-id"]}Restore(){
	Loadjs('mysql.restoredb.php?instance-id={$_GET["instance-id"]}&database={$_GET["database"]}');
		}
	
		function Rescan$t(){
		var XHR = new XHRConnection();
		XHR.appendData('mysql-scan','{$_GET["database"]}');
			XHR.appendData('instance-id','{$_GET["instance-id"]}');
			AnimateDiv('anim-$t');
				XHR.sendAndLoad('$page', 'GET',x_Rescan$t);
	
	}
	
	
	function MysqlCheck(table){
		if(confirm('$perfrom_mysqlcheck\\n'+table)){
		var XHR = new XHRConnection();
		XHR.appendData('mysql-check',table);
		XHR.sendAndLoad('$page', 'GET',x_MysqlCheck);
		}
	}
	
	
	
	var x_TableEmpty= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		$('#mysql-table-$t').flexReload();
	}
	
	
	function TableEmpty$t(table){
		if(confirm('$perfrom_empty\\n'+table)){
			var XHR = new XHRConnection();
			XHR.appendData('mysql-empty',table);
			XHR.sendAndLoad('$page', 'POST',x_TableEmpty);
			}
		}
</script>";
echo $html;
}

function mysql_empty(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("TRUNCATE TABLE `".$_POST["mysql-empty"]."`");
	
}

function databases_list_json(){
	$search=$_GET["search"];
	$MyPage=CurrentPageName();
	$page=1;
	$users=new usersMenus();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$t=$_GET["t"];
	$table="(SELECT * FROM information_schema.tables WHERE table_schema = 'squidlogs') as t";
	
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}

	if (isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){json_error_show("$q->mysql_error $sql");}
	$total = $ligne["TCOUNT"];

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);



	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysqli_num_rows($results)==0){json_error_show("Query return empty array, $sql, ($q->mysql_error)");}
	$ldap=new clladp();
	while ($ligne = mysqli_fetch_assoc($results)) {


		$TABLE_NAME=$ligne["TABLE_NAME"];
		$DATA_LENGTH=FormatBytes($ligne["DATA_LENGTH"]/1024);
		$TABLE_ROWS=FormatNumber($ligne["TABLE_ROWS"]);

		$md5S=md5($ligne["databasename"]);
		$js="LoadMysqlTables('{$ligne["databasename"]}');";
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='text-decoration:underline'>";

		$spanStyle1="<span style='font-size:16px;font-weight:bold;color:#5F5656;'>";
		$dbsize=FormatBytes($dbsize/1024);
		$mysqlcheck=imgsimple("database-check-32.png",null,"MysqlCheck('{$ligne["TABLE_NAME"]}')");
		$delete=imgsimple("dustbin-32.png",null,"TableEmpty$t('$TABLE_NAME')");
		
		$data['rows'][] = array(
				'id' => $md5S,
				'cell' => array(
						"$spanStyle1$TABLE_NAME</a></strong>",
						"$spanStyle1$DATA_LENGTH</span>",
						"$spanStyle1$TABLE_ROWS</span>",
						$mysqlcheck,
						$delete
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
function mysql_check(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?mysql-check=yes&database=squidlogs&table={$_GET["mysql-check"]}&instance-id={$_GET["instance-id"]}");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{operation_launched_in_background}");

}

