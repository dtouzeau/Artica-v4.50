<?php
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");

if(isset($_GET["popup"])){table();exit;}
if(isset($_GET["items"])){items();exit;}

js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("FreeWebs::{browse}...");
	echo "YahooUser('550','$page?popup=yes&groupware={$_GET["groupware"]}&field={$_GET["field"]}&t={$_GET["t"]}&withhttp={$_GET["withhttp"]}','$title')";
}

function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$q=new mysql();
	$TB_HEIGHT=500;
	$TB_WIDTH=350;
	$domains=$tpl->_ENGINE_parse_body("{domains}");
	$new_domain_controller=$tpl->_ENGINE_parse_body("{new_domain_controller}");
	$table="records";
	$database='powerdns';
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$tt=time();


	$new_entry=$tpl->_ENGINE_parse_body("{new_item}");
	$volumes=$tpl->_ENGINE_parse_body("{volumes}");
	$new_volume=$tpl->_ENGINE_parse_body("{new_volume}");
	$ipaddr=$tpl->_ENGINE_parse_body("{addr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$events=$tpl->_ENGINE_parse_body("events");

	$buttons="
		buttons : [
			{name: '$new_entry', bclass: 'Add', onpress : NewPDNSEntry2$t},
			{name: '$new_domain_controller', bclass: 'Add', onpress : NewDomainController$t},
			{name: '$domains', bclass: 'Search', onpress : FilterDomain$t},

			],	";
			//$('#flexRT$t').flexReload();
	$buttons=null;
	$html="
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:99%'></table>
	<script>
		var mem$t='';
		$(document).ready(function(){
			$('#flexRT$tt').flexigrid({
				url: '$page?items=yes&tt=$tt&groupware={$_GET["groupware"]}&field={$_GET["field"]}&t={$_GET["t"]}&withhttp={$_GET["withhttp"]}',
				dataType: 'json',
				colModel : [
					{display: '$hostname', name : 'servername', width :452, sortable : true, align: 'left'},
					{display: '&nbsp;', arrow : 'name', width :31, sortable : true, align: 'center'},
				],
			$buttons

			searchitems : [
				{display: '$hostname', name : 'servername'},
				
			],
			
			sortname: 'servername',
			sortorder: 'asc',
			usepager: true,
			title: '',
			useRp: true,
			rp: 50,
			showTableToggleBtn: false,
			width: 535,
			height: 380,
			singleSelect: true,
			rpOptions: [10, 20, 30, 50,100,200,500]

		});
});

	function SelectDNSEntry$tt(hostname){
		if(!document.getElementById(\"{$_GET["field"]}\")){
			alert('`{$_GET["field"]}` no such id');
			return;
		}
		document.getElementById(\"{$_GET["field"]}\").value=hostname;
		YahooUserHide();
	}

</script>";

echo $html;
}


function items(){

	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$users=new usersMenus();


	$search='%';
	$table="freeweb";
	$tablesrc="freeweb";
	$database='artica_backup';
	$FORCE_FILTER=null;
	$page=1;
	
	if($_GET["groupware"]<>null){
		$FORCE_FILTER=" AND `groupware` = '{$_GET["groupware"]}'";
	}



	if(!$q->TABLE_EXISTS($tablesrc, $database)){json_error_show("$table, No such table...",0);}
	if($q->COUNT_ROWS($tablesrc,$database)==0){json_error_show("No data...",0);}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show($q->mysql_error,1);}

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	//id 	domain_id 	name 	type 	content 	ttl 	prio 	change_date 	ordername 	auth

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysqli_num_rows($results)==0){
		json_error_show("No item");
	}


	while ($ligne = mysqli_fetch_assoc($results)) {
		$id=$ligne["id"];
		$articasrv=null;
		$aliases_text=null;
		
		if($_GET["withhttp"]==1){
			$withhttp="http://";
		}
		$arrow=imgsimple("arrow-right-24.png",null,"SelectDNSEntry$tt('$withhttp{$ligne["servername"]}')");
		$data['rows'][] = array(
				'id' => $id,
				'cell' => array(
						"<a href=\"javascript:blur();\" OnClick=\"javascript:SelectDNSEntry$tt('$withhttp{$ligne["servername"]}');\" 
						style='font-size:14px;text-decoration:underline'>{$ligne["servername"]}</a>",
						$arrow
				)
		);
	}


	echo json_encode($data);

}