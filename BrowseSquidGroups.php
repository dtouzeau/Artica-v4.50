<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	
	$GLOBALS["GroupType"]["src"]="{addr}";
	$GLOBALS["GroupType"]["arp"]="{ComputerMacAddress}";
	$GLOBALS["GroupType"]["dstdomain"]="{dstdomain}";
	$GLOBALS["GroupType"]["proxy_auth"]="{members}";	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "<H2>$alert</H2>";
	die("DIE " .__FILE__." Line: ".__LINE__);	
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["groups-list"])){group_list();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title="{browse}:{APP_SQUID}:{groups}";
	$title=$tpl->_ENGINE_parse_body($title);
	$html="YahooWinBrowse(550,'$page?popup=yes&CallBack={$_GET["CallBack"]}','$title');";
	echo $html;
	
	
}

function popup(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$type=$tpl->_ENGINE_parse_body("{type}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$t=time();		
	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?groups-list=yes&CallBack={$_GET["CallBack"]}',
	dataType: 'json',
	colModel : [
		{display: '$description', name : 'GroupName', width : 250, sortable : true, align: 'left'},
		{display: '$time', name : 'GroupType', width : 157, sortable : true, align: 'left'},
		{display: '$items', name : 'items', width : 37, sortable : false, align: 'center'},
		{display: '', name : 'none2', width : 35, sortable : false, align: 'left'},		
	],
	searchitems : [
		{display: '$description', name : 'GroupName'},
		],
	sortname: 'GroupName',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 530,
	height: 250,
	singleSelect: true
	
	});   
});

function RefreshSquidGroupTable(){
	$('#table-$t').flexReload();
}


	var x_DeleteSquidAclGroup= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		$('#rowtime'+TimeRuleIDTemp).remove();
	}
	
	var x_EnableDisableGroup= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		
		
	}	
	
	function DeleteSquidAclGroup(ID){
		DeleteSquidAclGroupTemp=ID;
		if(confirm('$delete_group_ask :'+ID)){
			var XHR = new XHRConnection();
			XHR.appendData('DeleteGroup', 'yes');
			XHR.appendData('ID', ID);
			XHR.sendAndLoad('$page', 'POST',x_DeleteSquidAclGroup);
		}  		
	}

	var x_DeleteSquidAclGroup= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#rowgroup'+DeleteSquidAclGroupTemp).remove();
	}
	
	function EnableDisableGroup(ID){
		var XHR = new XHRConnection();
		XHR.appendData('EnableGroup', 'yes');
		XHR.appendData('ID', ID);
		if(document.getElementById('groupid_'+ID).checked){XHR.appendData('enable', '1');}else{XHR.appendData('enable', '0');}
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableGroup);  		
	}		
	
	

	
</script>
	
	";
	
	echo $html;
}

function group_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table="webfilters_sqgroups";
	$page=1;

	if($q->COUNT_ROWS($table)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE enabled=1 $FORCE_FILTER $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE enabled=1 $FORCE_FILTER";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE enabled=1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($q->mysql_error,"", "",""));json_encode($data);return;}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysqli_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));son_encode($data);return;}
	
	$GroupType["src"]="{addr}";
	$GroupType["arp"]="{ComputerMacAddress}";
	$GroupType["dstdomain"]="{dstdomain}";
	$GroupType["proxy_auth"]="{members}";
	
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$val=0;
		
		$ligne['GroupName']=utf8_encode($ligne['GroupName']);
		$GroupTypeText=$tpl->_ENGINE_parse_body($GroupType[$ligne["GroupType"]]);
		
		$select=imgtootltip("arrow-right-32.png","{select} {$ligne['GroupName']}","{$_GET["CallBack"]}({$ligne['ID']})");
		
		
		$ligne2=mysqli_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='{$ligne['ID']}'"));
	$data['rows'][] = array(
		'id' => "group{$ligne['ID']}",
		'cell' => array(
		"<span style='font-size:16px;'>{$ligne['GroupName']}</span>",
		"<span style='font-size:16px;'>$GroupTypeText</span>",
		"<span style='font-size:16px;'>{$ligne2['tcount']}</span>",
		$select)
		);
	}
	
	
	echo json_encode($data);	
}
