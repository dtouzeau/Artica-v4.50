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
	$title=$tpl->_ENGINE_parse_body("DNS::{browse}...");
	echo "YahooUser('550','$page?popup=yes&domain={$_GET["domain"]}&field={$_GET["field"]}&t={$_GET["t"]}','$title')";
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
				url: '$page?items=yes&tt=$tt&domain={$_GET["domain"]}&field={$_GET["field"]}&t={$_GET["t"]}',
				dataType: 'json',
				colModel : [
					{display: '$hostname', name : 'name', width :315, sortable : true, align: 'left'},
					{display: '$ipaddr', name : 'content', width :129, sortable : true, align: 'left'},
					{display: '&nbsp;', arrow : 'name', width :31, sortable : true, align: 'center'},
				],
			$buttons

			searchitems : [
				{display: '$hostname', name : 'name'},
				{display: '$ipaddr', name : 'content'},
			],
			
			sortname: 'name',
			sortorder: 'asc',
			usepager: true,
			title: '',
			useRp: true,
			rp: 50,
			showTableToggleBtn: false,
			width: 535,
			height: $TB_HEIGHT,
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
	$table="records";
	$tablesrc="records";
	$database='powerdns';
	$page=1;
	$FORCE_FILTER=" AND `type` = 'A'";




	if(!$users->AsSystemAdministrator){
		if(!$users->AsDnsAdministrator){
			$ldap=new clladp();
			$domains=$ldap->hash_get_domains_ou($_SESSION["ou"]);
			while (list ($num, $ligne) = each ($domains) ){
				$tt[]="(domains.id = records.domain_id AND domains.name = '$num')";
			}
				
			$table="(SELECT records.* FROM records, domains WHERE ".@implode(" OR ", $tt).") as t";
		}
	}

	if($_GET["domain"]<>null){
		$table="(SELECT records.* FROM records, domains WHERE (domains.id = records.domain_id AND domains.name = '{$_GET["domain"]}') ) as t";

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

	$sock=new sockets();
	$aliases=$tpl->_ENGINE_parse_body("{aliases}");
	while ($ligne = mysqli_fetch_assoc($results)) {
		$id=$ligne["id"];
		$articasrv=null;
		$aliases_text=null;
		$delete=imgsimple("delete-24.png",null,"PdnsRecordDelete$t('$id')");
		if($ligne["articasrv"]<>null){$articasrv="<div><i style='font-size:11px'>serv:{$ligne["articasrv"]}</i></div>";}
		$ligne2=mysqli_fetch_array($q->QUERY_SQL("SELECT COUNT(id) as tcount FROM records WHERE `content`='{$ligne["name"]}' AND `type` = 'CNAME'","powerdns"));
		$aliases_count=$ligne2["tcount"];
		if($aliases_count>0){
			$aliases_text="<div><i style='font-size:11px;font-weight:bold'>$aliases_count $aliases</i></div>";
		}

		$arrow=imgsimple("arrow-right-24.png",null,"SelectDNSEntry$tt('{$ligne["name"]}')");
		
		
		$data['rows'][] = array(
				'id' => $id,
				'cell' => array(
						"<a href=\"javascript:blur();\" OnClick=\"javascript:SelectDNSEntry$tt('{$ligne["name"]}');\" 
						style='font-size:12px;text-decoration:underline'>{$ligne["name"]}</a>$articasrv$aliases_text",
						"<span style='font-size:12px;'>{$ligne["content"]}</span>",$arrow
				)
		);
	}


	echo json_encode($data);

}