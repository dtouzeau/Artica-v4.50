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
	include_once('ressources/class.ccurl.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.resolv.conf.inc');

	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "<H1>". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."</H1>";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	
	if(isset($_GET["nets"])){networks_list();exit;}
	if(isset($_GET["networks-js"])){network_js();exit;}
	if(isset($_GET["delete-item-js"])){network_js_delete();exit;}
	if(isset($_POST["network-add"])){network_add();exit;}
	if(isset($_POST["delete-network"])){network_delete();exit;}
	
table();	

function network_js_delete(){
	header("content-type: application/x-javascript");
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$delete=$tpl->javascript_parse_text("{remove} {item}");
	$time=time();
	$html="
	var xDelete$time = function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#flexRT$t').flexReload();
}

function Delete$time(){
	if( !confirm('$delete {$_GET["delete-item-js"]} ?') ){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-network','{$_GET["delete-item-js"]}');
	XHR.sendAndLoad('$page', 'POST',xDelete$time,true);
}
Delete$time();";
echo $html;
}

function network_js(){
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$tpl=new templates();
	$recordid=$_GET["recordid"];
	$page=CurrentPageName();
	$explain=$tpl->javascript_parse_text("{acl_src_text}");
	$time=time();
	
$html="
var xAdd$time = function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#flexRT$t').flexReload();
	
}

function Add$time(){
	var host=prompt('$explain');
	if(!host){return;}
	var XHR = new XHRConnection();
	XHR.appendData('network-add',host);
	XHR.sendAndLoad('$page', 'POST',xAdd$time,true);
}
Add$time();";
	echo $html;
}

function network_add(){
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("INSERT IGNORE INTO ident_networks (network_item) VALUES ('{$_POST["network-add"]}')");
	if(!$q->ok){echo $q->mysql_error;}
}

function network_delete(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM ident_networks WHERE network_item='{$_POST["delete-network"]}'");
	if(!$q->ok){echo $q->mysql_error;}
	
}


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$dnsmasq_address_text=$tpl->_ENGINE_parse_body("{dnsmasq_address_text}");
	$hosts=$tpl->_ENGINE_parse_body("{domains}");
	$networks=$tpl->_ENGINE_parse_body("{networks}");
	$new_network=$tpl->_ENGINE_parse_body("{new_network}");
	$blacklist=$tpl->_ENGINE_parse_body("{blacklist}");
	$aliases=$tpl->_ENGINE_parse_body("{aliases}");
	$appy=$tpl->_ENGINE_parse_body("{apply}");
	$apply_params=$tpl->_ENGINE_parse_body("{apply}");
	$apply_paramsbt="{separator: true},{name: '$apply_params', bclass: 'apply', onpress : SquidBuildNow$t},";
	$buttons="
	buttons : [
	{name: '$new_network', bclass: 'add', onpress : AddHost$t},
$apply_paramsbt


	],";

	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	<script>
	$(document).ready(function(){
	var md5H='';
	$('#flexRT$t').flexigrid({
	url: '$page?nets=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$networks', name : 'network_item', width : 835, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'delete', width : 80, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$networks', name : 'network_item'},

	],
	sortname: 'network_item',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:18px>$networks</span>',
	useRp: true,
	rp: 150,
	showTableToggleBtn: false,
	width: '99%',
	height: 300,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});


	function SquidBuildNow$t(){
		Loadjs('squid.compile.php');
	}

function AddHost$t(){
	Loadjs('$page?networks-js=yes&t=$t',true);

}
</script>
";

echo $tpl->_ENGINE_parse_body($html);
}

function networks_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();

	$t=$_GET["t"];
	$search='%';
	$table="ident_networks";
	
	if(!$q->TABLE_EXISTS($table)){
		$sql="CREATE TABLE IF NOT EXISTS `ident_networks` (
				network_item VARCHAR(128) NOT NULL PRIMARY KEY
			)  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql);
	}


	$page=1;
	$FORCE_FILTER=null;

	$total=0;


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);

	$no_rule=$tpl->_ENGINE_parse_body("{no data}");

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){
		if(strpos($q->mysql_error, "doesn't exist")>0){$q->CheckTables();$results = $q->QUERY_SQL($sql);}
	}

	if(!$q->ok){	json_error_show($q->mysql_error."<br>$sql");}
	if(mysqli_num_rows($results)==0){json_error_show("no data");}

	$fontsize="24";

	while ($ligne = mysqli_fetch_assoc($results)) {
		$color="black";
		$network_item=$ligne["network_item"];
		$encoded=urlencode($network_item);
		$delete=imgsimple("delete-48.png",null,"Loadjs('$MyPage?delete-item-js=$encoded&t=$t')");

		$editjs="<a href=\"javascript:blur();\"
		OnClick=\"Loadjs('$MyPage?host-js=yes&ID={$ligne["ID"]}&t=$t',true);\"
		style='font-size:{$fontsize}px;font-weight:bold;color:$color;text-decoration:underline'>";
		$editjs=null;

		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs$network_item</a><br><i style='font-size:12px'>&nbsp;$grouptype</i></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>",)
		);
	}

	echo json_encode($data);
}