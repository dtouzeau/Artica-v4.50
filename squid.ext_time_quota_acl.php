<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsDansGuardianAdministrator){
		$tpl=new templates();
		$alert=$tpl->javascript_parse_text('{ERROR_NO_PRIVS}');
		echo "alert('$alert')";
		die("DIE " .__FILE__." Line: ".__LINE__);	
	}
	if(isset($_GET["browser-quota-js"])){browse_quota_js();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["sessions-rules-list"])){rules_list();exit;}
	if(isset($_GET["session-rule-js"])){session_rule_js();exit;}
	if(isset($_GET["session-rule"])){session_rule_tab();exit;}
	if(isset($_GET["session-rule-settings"])){session_rule_settings();exit;}
	if(isset($_POST["QuotaName"])){session_rule_settings_save();exit;}
	if(isset($_POST["acl-rule-delete"])){session_rule_delete();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	if(!is_numeric($_GET["t"])){$_GET["t"]=time();}
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{session_manager}");
	echo "YahooWin2('810','$page?popup=yes&t={$_GET["t"]}','$title');";
	
	
}
function browse_quota_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	if(!is_numeric($_GET["t"])){$_GET["t"]=time();}
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{browse}...{session_manager}");
	echo "YahooWinBrowse('810','$page?popup=yes&t={$_GET["t"]}&browse=yes&checkbowid={$_GET["checkbowid"]}&textid={$_GET["textid"]}&idnum={$_GET["idnum"]}','$title');";


}
function session_rule_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	if(!is_numeric($_GET["t"])){$_GET["t"]=time();}
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{session_manager}::{$_GET["ID"]}");
	
	if($_GET["ID"]>0){
		$q=new mysql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM ext_time_quota_acl WHERE ID={$_GET["ID"]}","artica_backup"));
		$title=$title."::".$tpl->javascript_parse_text($ligne["QuotaName"]);
	}
	
	echo "YahooWin3('650','$page?session-rule=yes&t={$_GET["t"]}&ID={$_GET["ID"]}','$title');";


}

function session_rule_tab(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	
	
	$array["session-rule-settings"]='{settings}';
	if($ID>0){
		$array["rules"]='{rules}';
	}
	
	

	foreach ($array as $num=>$ligne){
		if($num=="rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'>
					<a href=\"squid.ext_time_quota_acl_rules.php?mainrule=$ID&t=$t&tOrg={$_GET["tOrg"]}\"><span>$ligne</span></a></li>\n");
			continue;
		}
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"$page?$num=yes&ID=$ID&t=$t&tOrg={$_GET["tOrg"]}\"><span>$ligne</span></a></li>\n");
	
	}

	
	echo "
	<div id=main_session_rule_zoom style='width:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_session_rule_zoom').tabs();
			
			
			});
		</script>";	
}

function session_rule_settings(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();	
	$EnableMacAddressFilter=$sock->GET_INFO("EnableMacAddressFilter");
	if(!is_numeric($EnableMacAddressFilter)){$EnableMacAddressFilter=0;}
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$tOrg=$_GET["tOrg"];
	$btname="{add}";
	if($ID>0){
		$btname="{apply}";
		$q=new mysql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM ext_time_quota_acl WHERE ID=$ID","artica_backup"));
		
	}
	
	if($ligne["QuotaName"]==null){$ligne["QuotaName"]="New Quota rule";}
	if($ligne["details"]==null){$ligne["details"]="Created on ". date("l F d Y");}
	if($ligne["QuotaType"]==null){$ligne["QuotaType"]="src";}
	
	if($ligne["TTL"]==null){$ligne["TTL"]="60";}
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	
	$QuotaType["src"]="{ip_address}";
	
	if($EnableMacAddressFilter==0){
		$QuotaType["MAC"]="{mac_address}";
	}
	
	$QuotaType["ADMBR"]="{activedirectoryldap_members}";
	$QuotaType["EXT_USER"]="{external_acl_member}";
	//$QuotaType["EXT_TAG"]="{external_acl_tag}";	
	
	$html="
	<div id='$t'></div>		
	<table style='width:99%' class=form>
		<tr>
			<td class=legend style='font-size:16px'>{rule_name}:</td>		
			<td>". Field_text("QuotaName-$t",$ligne["QuotaName"],"font-size:16px;width:210px",null,null,null,false,"SaveQ$t(event)")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{description}:</td>		
			<td>". Field_text("details-$t",$ligne["details"],"font-size:16px;width:210px",null,null,null,false,"SaveQ$t()")."</td>
		</tr>					
		<tr>
			<td class=legend style='font-size:16px'>{member_type}:</td>		
			<td>". Field_array_Hash($QuotaType,"QuotaType-$t",$ligne["QuotaType"],null,'',0,"font-size:16px")."</td>
		</tr>					
		<tr>
			<td class=legend style='font-size:16px'>{TTL}:</td>		
			<td style='font-size:16px'>". Field_text("TTL-$t",$ligne["TTL"],"font-size:16px;width:90px",null,null,null,false,"SaveQ$t(event)")."&nbsp;{seconds}</td>
			
		</tr>	
		<tr>
			<td class=legend style='font-size:16px'>{enabled}:</td>		
			<td>". Field_checkbox("enabled-$t",1,$ligne["enabled"],"Check$t()")."</td>
		</tr>
		<tr>
			<td colspan=2 align='right'><hr>". button($btname,"Save$t()","18px")."</td>
		</tr>
		</table>
		<script>
		var x_Save$t= function (obj) {
			var ID=$ID;
			document.getElementById('$t').innerHTML='';	
			var res=obj.responseText;
			if(res.length>3){alert(res);return;}
			$('#table-$tOrg').flexReload();
			$('#table-$t').flexReload();			
			if(ID<1){YahooWin3Hide();return;}
			RefreshTab('main_session_rule_zoom');

			
		}
		
		function SaveQ$t(e){
			if(checkEnter(e)){ Save$t();}
		}
	
		function Save$t(){
			var XHR = new XHRConnection();
			var QuotaName=encodeURIComponent(document.getElementById('QuotaName-$t').value);
			var details=encodeURIComponent(document.getElementById('details-$t').value);
			var enabled=0;
			XHR.appendData('QuotaName', QuotaName);
			XHR.appendData('ID', '$ID');
			XHR.appendData('details', details);
			XHR.appendData('QuotaType', document.getElementById('QuotaType-$t').value);
			if(document.getElementById('enabled-$t').checked){enabled=1;}
			XHR.appendData('enabled', enabled);
			XHR.sendAndLoad('$page', 'POST',x_Save$t);  	
			AnimateDiv('$t');
			}
					
		</script>
		";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function session_rule_settings_save(){
	$q=new mysql();
	$_POST["QuotaName"]=url_decode_special_tool($_POST["QuotaName"]);
	$_POST["details"]=url_decode_special_tool($_POST["details"]);
	$ID=$_POST["ID"];
	if($ID>0){
		$sql="UPDATE ext_time_quota_acl SET 
				QuotaName='{$_POST["QuotaName"]}',
				details='{$_POST["details"]}',
				enabled='{$_POST["enabled"]}' WHERE ID=$ID
				";
		
		
		
	}else{
		$sql="INSERT IGNORE INTO ext_time_quota_acl (QuotaName,details,enabled,QuotaType)
				VALUES('{$_POST["QuotaName"]}','{$_POST["details"]}','{$_POST["enabled"]}','{$_POST["QuotaType"]}')";
	}
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}

	
function popup(){	
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$q->CheckTables();
	$tOrg=$_GET["t"];
	$description=$tpl->_ENGINE_parse_body("{description}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$groups=$tpl->_ENGINE_parse_body("{proxy_objects}");
	$delete_rule_ask=$tpl->javascript_parse_text("{delete_rule_ask}");
	$apply_params=$tpl->_ENGINE_parse_body("{apply}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$t=time();

	if(isset($_GET["browse"])){
		$addon="&browse=yes&textid={$_GET["textid"]}&idnum={$_GET["idnum"]}";
		$delete=$tpl->javascript_parse_text("{select}");
		
	}
	
	
	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var DeleteSquidAclGroupTemp=0;
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?sessions-rules-list=yes&t=$t&tOrg=$tOrg$addon',
	dataType: 'json',
	colModel : [
		{display: '$rule', name : 'QuotaName', width : 181, sortable : true, align: 'left'},
		{display: '$description', name : 'items', width : 258, sortable : false, align: 'left'},
		{display: '$type', name : 'QuotaType', width : 100, sortable : true, align: 'left'},
		{display: 'TTL', name : 'TTL', width : 100, sortable : true, align: 'left'},
		{display: '$enabled', name : 'enabled', width : 31, sortable : true, align: 'center'},
		{display: '$delete', name : 'del', width : 31, sortable : false, align: 'center'},
	],
buttons : [
	{name: '$new_rule', bclass: 'add', onpress : AddSessionRule$t},
		],	
	searchitems : [
		{display: '$rule', name : 'QuotaName'},
		],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 790,
	height: 450,
	singleSelect: true
	
	});   
});
function AddSessionRule$t() {
	Loadjs('$page?session-rule-js=yes&ID=-1&t=$t&tOrg=$tOrg');
	
}	



	var x_DeleteSquidAclGroup= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		$('#rowtime'+TimeRuleIDTemp).remove();
	}
	
	
	function SelectSessionRule(ID,name){
		document.getElementById('{$_GET["textid"]}').innerHTML=name;
		document.getElementById('{$_GET["idnum"]}').value=ID;
		if(document.getElementById('{$_GET["checkbowid"]}')){
			document.getElementById('{$_GET["checkbowid"]}').checked=true;
		}
		
		
		YahooWinBrowseHide();
	}
	

	
	var x_SquidBuildNow= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#table-$t').flexReload();
	}	
	
	
	function SquidBuildNow$t(){
		Loadjs('squid.compile.php');
	}

	var x_DeleteSquidAclRule$t= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#rowacl'+DeleteSquidAclGroupTemp).remove();
	}	
	
	
	function DeleteSquidAclRule$t(ID){
		DeleteSquidAclGroupTemp=ID;
		if(confirm('$delete_rule_ask :'+ID)){
			var XHR = new XHRConnection();
			XHR.appendData('acl-rule-delete', ID);
			XHR.sendAndLoad('$page', 'POST',x_DeleteSquidAclRule$t);
		}  		
	}


	
	function EnableDisableAclRule(ID){
		var XHR = new XHRConnection();
		XHR.appendData('acl-rule-enable', ID);
		if(document.getElementById('aclid_'+ID).checked){XHR.appendData('enable', '1');}else{XHR.appendData('enable', '0');}
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableAclRule);  		
	}		
	
	

	
</script>
	
	";
	
	echo $html;
	
}	


function rules_list(){
		//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
		$tpl=new templates();
		$MyPage=CurrentPageName();
		$q=new mysql();
		$database="artica_backup";
		$t=$_GET["t"];
		$tOrg=$_GET["tOrg"];
		$search='%';
		$table="ext_time_quota_acl";
		$page=1;
		$data = array();
		$data['rows'] = array();
		$FORCE_FILTER=null;
		
		if(!$q->TABLE_EXISTS($table, $database)){$q->BuildTables();}
	
		if(isset($_POST["sortname"])){
			if($_POST["sortname"]<>null){
				$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
			}
		}
	
		if (isset($_POST['page'])) {$page = $_POST['page'];}
		$searchstring=string_to_flexquery();
	
		if($searchstring<>null){
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
			$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,$database));
			$total = $ligne["TCOUNT"];
	
		}else{
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
			$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,$database));
			$total = $ligne["TCOUNT"];
			
		}
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
		
		$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		$results = $q->QUERY_SQL($sql,$database);
		if(!$q->ok){json_error_show("$q->mysql_error");}
	
		// %EXT_TAG , %LOGIN , %IDENT , %EXT_USER , %SRC , %SRCEUI48
	
		$data['page'] = $page;
		$data['total'] = $total;
	
		$QuotaType["src"]="{ip_address}";
		$QuotaType["ADMBR"]="{activedirectoryldap_members}";
		$QuotaType["EXT_USER"]="{external_acl_member}";
		$QuotaType["EXT_TAG"]="{external_acl_tag}";
		$QuotaType["MAC"]="{mac_address}";
		$seconds=$tpl->_ENGINE_parse_body("{seconds}");
	
		$order=$tpl->_ENGINE_parse_body("{order}:");
		while ($ligne = mysqli_fetch_assoc($results)) {
			$val=0;
			$color="black";
			$disable=Field_checkbox("sessionid_{$ligne['ID']}", 1,$ligne["enabled"],"EnableDisableSessionRule('{$ligne['ID']}')");
			$ligne['QuotaName']=utf8_encode($ligne['QuotaName']);
			$ligne['details']=utf8_encode($ligne['details']);
			$delete=imgsimple("delete-24.png",null,"DeleteSquidAclRule$t('{$ligne['ID']}')");
			if($ligne["enabled"]==0){$color="#8a8a8a";}
	


			$QuotaGroup=QuotaGroup($ligne['ID']);
			
			if($_GET["browse"]=="yes"){
				$delete=imgsimple("arrow-right-24.png",null,"SelectSessionRule('{$ligne['ID']}','{$ligne['QuotaName']}')");
				
			}
	
			$data['rows'][] = array(
					'id' => "acl{$ligne['ID']}",
					'cell' => array("<a href=\"javascript:blur();\"  
					OnClick=\"Loadjs('$MyPage?session-rule-js=yes&ID={$ligne['ID']}&t=$t&tOrg=$tOrg');\"
					style='font-size:16px;text-decoration:underline;color:$color'>{$ligne['QuotaName']}</span>
					</A>
					",
					"<span style='font-size:13px;color:$color'>{$ligne['details']}$QuotaGroup</span>",
					"<span style='font-size:12px;color:$color'>".$tpl->_ENGINE_parse_body("{$QuotaType[$ligne['QuotaType']]}")."</span>",
					"<span style='font-size:12px;color:$color'>{$ligne['TTL']}&nbsp;$seconds</span>",
					$disable,
					$delete
				)
			);
	}
	
	
		echo json_encode($data);
}

function QuotaGroup($ID){
	$q=new mysql();
	$tpl=new templates();
	$sql="SELECT * FROM ext_time_quota_acl_rules WHERE ruleid='$ID' AND enabled=1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){return $q->mysql_error;}
	$acl=new squid_acls_groups();
	$t=$_GET["t"];
	$tOrg=$_GET["tOrg"];
	$tt=$_GET["tt"];
	$budgets["s"]="{seconds}";
	$budgets["m"]="{minutes}";
	$budgets["h"]="{hours}";
	$budgets["d"]="{days}";
	$budgets["w"]="{weeks}";
	$f=array();
	while ($ligne = mysqli_fetch_assoc($results)) {
		
		$ligne['rulename']=utf8_encode($ligne['rulename']);
		
		if(preg_match("#([0-9]+)([a-z]+)#", $ligne["budget"],$re)){
			$budget=$re[1];
			$budget2=$re[2];
		
		}
		
		if(preg_match("#([0-9]+)([a-z]+)#", $ligne["period"],$re)){
			$period=$re[1];
			$period2=$re[2];
		
		}
		
		$GetGroupsList=GetGroupsList($ligne["ID"]);
		
		$url="<a href=\"javascript:blur();\" OnClick=\"Loadjs('squid.ext_time_quota_acl_rules.php?session-rule-js=yes&ID={$ligne['ID']}&t=$t&tOrg=$tOrg&mainrule=$ID&tt=$tt');\" style=\"font-size:11px;text-decoration:underline;color:$color\">";
		$period=$tpl->javascript_parse_text("$period {$budgets[$period2]}");
		$budget=$tpl->javascript_parse_text("$budget {$budgets[$budget2]}");
		$f[]="<div style='font-size:11px'>&laquo;<strong>$url{$ligne['rulename']}</strong></a>&raquo; $budget/$period$GetGroupsList</div>";
	}
	
	return @implode("", $f);
}
function GetGroupsList($ID){
	$q=new mysql();
	$tpl=new templates();
	$sql="SELECT groupid FROM ext_time_quota_acl_link WHERE ruleid='$ID' AND enabled=1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){return $q->mysql_error;}
	$acl=new squid_acls_groups();
	$f=array();
	while ($ligne = mysqli_fetch_assoc($results)) {
		$arrayF=$acl->FlexArray($ligne["groupid"],1,10);
		$f[]="<div style='font-size:9px;margin-left:15px'>{$arrayF["ROW"]} ({$arrayF["ITEMS"]} ".$tpl->_ENGINE_parse_body('{items}').")</div>";
	}
	return @implode("\n", $f);
}

function session_rule_delete(){
	$q=new mysql();
	$ID=$_POST["acl-rule-delete"];
	$sql="SELECT ID FROM ext_time_quota_acl_rules WHERE ruleid='$ID'";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q=new mysql();
	while ($ligne = mysqli_fetch_assoc($results)) {
		$idrule=$ligne["ID"];
		$q->QUERY_SQL("DELETE FROM ext_time_quota_acl_link WHERE ruleid='$idrule'","artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	$sql="DELETE FROM ext_time_quota_acl_rules WHERE ruleid='$ID'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sql="DELETE FROM ext_time_quota_acl WHERE ID='$ID'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}

