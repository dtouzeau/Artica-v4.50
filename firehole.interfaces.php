<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.firehol.inc');

	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
	
	if(isset($_GET["interfaces"])){search();exit;}
	
popup();	
	

function popup(){
	unset($_SESSION["postfix_firewall_rules"]);
	$users=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	
	$t=time();
	$interfaces=$tpl->_ENGINE_parse_body("{interfaces}");
	$netzone=$tpl->_ENGINE_parse_body("{netzone}");
	$local_services=$tpl->_ENGINE_parse_body("{local_services}");
	$log=$tpl->_ENGINE_parse_body("{LOG}");
	$saved_date=$tpl->_ENGINE_parse_body("{zDate}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$name=$tpl->_ENGINE_parse_body("{name}");
	$allow_rules=$tpl->_ENGINE_parse_body("{allow_rules}");
	$banned_rules=$tpl->_ENGINE_parse_body("{banned_rules}");
	$empty_all_firewall_rules=$tpl->javascript_parse_text("{empty_all_firewall_rules}");
	$services=$tpl->_ENGINE_parse_body("{services}");
	$current_rules=$tpl->_ENGINE_parse_body("{current_rules}");
	$options=$tpl->_ENGINE_parse_body("{options}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$ERROR_IPSET_NOT_INSTALLED=$tpl->javascript_parse_text("{ERROR_IPSET_NOT_INSTALLED}");
	$IPSET_INSTALLED=0;
	if($users->IPSET_INSTALLED){$IPSET_INSTALLED=1;}

	$TB_HEIGHT=450;
	$TABLE_WIDTH=920;
	$TB2_WIDTH=400;
	$ROW1_WIDTH=629;
	$ROW2_WIDTH=163;

	$t=time();

	$buttons="
	buttons : [
	{name: '$empty', bclass: 'Delz', onpress : EmptyRules},
	{name: '$new_rule', bclass: 'Add', onpress : NewIptableRule},
	{name: '$allow_rules', bclass: 'Search', onpress : AllowRules},
	{name: '$banned_rules', bclass: 'Search', onpress : BannedRules},
	{name: '$block_countries', bclass: 'Catz', onpress : block_countries},
	{name: '$current_rules', bclass: 'Search', onpress : current_rules},
	{name: '$options', bclass: 'Settings', onpress : options$t},


	],	";
	$html="
	<table class='FIREHOLE_INTERFACES_TABLES' style='display: none' id='FIREHOLE_INTERFACES_TABLES' style='width:99%'></table>
	<script>
	var IptableRow='';
	$(document).ready(function(){
	$('#FIREHOLE_INTERFACES_TABLES').flexigrid({
	url: '$page?interfaces=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'none', width :40, sortable : false, align: 'center'},
	{display: '$interfaces', name : 'Interface', width :110, sortable : false, align: 'center'},
	{display: '$name', name : 'NICNAME', width :222, sortable : false, align: 'left'},
	{display: '$netzone', name : 'netzone', width :222, sortable : false, align: 'left'},
	{display: '$local_services', name : 'none2', width :131, sortable : true, align: 'center'},
	{display: '$services', name : 'none2', width :97, sortable : true, align: 'center'},

	],
	$buttons

	searchitems : [
	{display: '$interfaces', name : 'Interface'},
	{display: '$name', name : 'NICNAME'},
	{display: '$netzone', name : 'netzone'},
	],

	sortname: 'Interface',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: $TB_HEIGHT,
	singleSelect: true

});
});

function block_countries(){
var IPSET_INSTALLED=$IPSET_INSTALLED;
if(IPSET_INSTALLED==0){alert('$ERROR_IPSET_NOT_INSTALLED');return;}
Loadjs('system.ipblock.php')
}

function current_rules(){
Loadjs('system.iptables.save.php');
}

var x_EmptyRules= function (obj) {
var tempvalue=obj.responseText;
if(tempvalue.length>3){alert(tempvalue);return;}
IpTablesInboundRuleResfresh();
}

function EmptyRules(){
if(confirm('$empty_all_firewall_rules ?')){
var XHR = new XHRConnection();
XHR.appendData('EmptyAll','yes');
XHR.sendAndLoad('$page', 'POST',x_EmptyRules);
}
}

function NewIptableRule(){
iptables_edit_rules('');
}

function IpTablesInboundRuleResfresh(){
$('#table-$t').flexReload();
}

function AllowRules(){
$('#table-$t').flexOptions({ url: '$page?iptables_rules=yes&t=$t&allow=1' }).flexReload();
}
function BannedRules(){
$('#table-$t').flexOptions({ url: '$page?iptables_rules=yes&t=$t&allow=0' }).flexReload();
}

var x_IptableDelete= function (obj) {
var tempvalue=obj.responseText;
if(tempvalue.length>3){alert(tempvalue);return;}
$('#row'+IptableRow).remove();

}

function options$t(){
Loadjs('$page?options=yes&table=table-$t',true);
}

function IptableDelete(key){
IptableRow=key;
var XHR = new XHRConnection();
XHR.appendData('DeleteIptableRule',key);
XHR.sendAndLoad('$page', 'POST',x_IptableDelete);
}

var x_FirewallDisableRUle= function (obj) {
var tempvalue=obj.responseText;
if(tempvalue.length>3){alert(tempvalue);}
}

function iptables_edit_rules(num){
YahooWin5('800','$page?edit_rule=yes&t=$t&rulemd5='+num,'$rule');

}


function FirewallDisableRUle(ID){
var XHR = new XHRConnection();
XHR.appendData('ID',ID);
if(document.getElementById('enabled_'+ID).checked){XHR.appendData('EnableFwRule',0);}else{XHR.appendData('EnableFwRule',1);}
XHR.sendAndLoad('$page', 'POST',x_FirewallDisableRUle);
}

function EnableLog(ID){
var XHR = new XHRConnection();
XHR.appendData('ID',ID);
if(document.getElementById('enabled_'+ID).checked){XHR.appendData('EnableLog',1);}else{XHR.appendData('EnableLog',0);}
XHR.sendAndLoad('$page', 'POST',x_FirewallDisableRUle);

}

</script>";

	echo $html;
}

function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";
	$search='%';
	$table="nics";
	$page=1;
	$ORDER=null;
	$allow=null;

	$total=0;
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("no data");;}

	
	$searchstring=string_to_flexquery();
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$table="(SELECT * FROM nics WHERE isFW=1) as t";
	$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,$database));
	$total = $ligne["TCOUNT"];


	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}

	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT * FROM $table $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error_html(),1);}
	if(mysqli_num_rows($results)==0){json_error_show("no data $sql");}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error);}
	$fontsize=18;
	$firehole=new firehol();
	while ($ligne = mysqli_fetch_assoc($results)) {
		$mouse="OnMouseOver=\"this.style.cursor='pointer'\" OnMouseOut=\"this.style.cursor='default'\"";
		$linkstyle="style='text-decoration:underline'";
		$Interface=$ligne["Interface"];
		$NICNAME=$ligne["NICNAME"];
		$netzone=$ligne["netzone"];
		$CountDelocal=$firehole->interface_count_local_services($Interface);
		$CountServices=$firehole->interface_count_allowed_services($Interface);
		$js="Loadjs('system.nic.edit.php?nic=$Interface')";

		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:{$fontsize}px;text-decoration:underline'>";

		$data['rows'][] = array(
				'id' => $ligne["Interface"],
				'cell' => array(
						"<img src='img/folder-network-32.png' valign='middle'>",
						"<span style='font-size:18px'>$link$Interface</a></span>",
						"<span style='font-size:18px'>$link$NICNAME</a></span>",
						"<span style='font-size:18px'>$netzone</span>",
						"<span style='font-size:18px'>$CountDelocal</span>",
						"<span style='font-size:18px'>$CountServices</span>",
						
							

				)
		);
	}


	echo json_encode($data);

}