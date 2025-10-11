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
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["sessions-rules-list"])){rules_list();exit;}
	if(isset($_GET["session-rule-js"])){session_rule_js();exit;}
	if(isset($_GET["session-rule"])){session_rule_tab();exit;}
	if(isset($_GET["session-rule-settings"])){session_rule_settings();exit;}
	if(isset($_POST["rulename"])){session_rule_settings_save();exit;}
	if(isset($_POST["acl-rule-delete"])){session_rule_delete();exit;}
	if(isset($_POST["acl-rule-enable"])){session_rule_enable();exit;}
	
	popup();
	
	
	function popup(){
		$page=CurrentPageName();
		$sock=new sockets();
		$tpl=new templates();
		$q=new mysql_squid_builder();
		$q->CheckTables();
		$tOrg=$_GET["t"];
		$t=$_GET["tt"];
		$mainrule=$_GET["mainrule"];
		
		$rule=$tpl->_ENGINE_parse_body("{rule}");
		$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
		$groups=$tpl->_ENGINE_parse_body("{proxy_objects}");
		$delete_rule_ask=$tpl->javascript_parse_text("{delete_rule_ask}");
		$period=$tpl->javascript_parse_text("{period}");
		$budget=$tpl->_ENGINE_parse_body("{budget}");
		$enabled=$tpl->javascript_parse_text("{enabled}");
		$delete=$tpl->javascript_parse_text("{delete}");
		$tt=time();
	
	
	
		$html="
		<table class='table-$tt' style='display: none' id='table-$tt' style='width:99%'></table>
		<script>
		var DeleteSquidAclGroupTemp$tt=0;
		$(document).ready(function(){
		$('#table-$tt').flexigrid({
		url: '$page?sessions-rules-list=yes&t=$t&tOrg=$tOrg&mainrule=$mainrule&tt=$tt',
		dataType: 'json',
		colModel : [
		{display: '$rule', name : 'rulename', width : 300, sortable : true, align: 'left'},
		{display: '$budget', name : 'budget', width : 76, sortable : true, align: 'left'},
		{display: '$period', name : 'period', width : 76, sortable : true, align: 'left'},
		{display: '$enabled', name : 'enabled', width : 31, sortable : true, align: 'center'},
		{display: '$delete', name : 'del', width : 31, sortable : false, align: 'center'},
		],
		buttons : [
		{name: '$new_rule', bclass: 'add', onpress : AddSessionRule$tt},
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
		width: 600,
		height: 450,
		singleSelect: true
	
	});
	});
	function AddSessionRule$tt() {
		Loadjs('$page?session-rule-js=yes&ID=-1&t=$t&tOrg=$tOrg&tt=$tt&mainrule=$mainrule');
	
	}
	
	var x_DeleteSquidAclRule$tt= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#rowacl'+DeleteSquidAclGroupTemp$tt).remove();
		$('#table-$tOrg').flexReload();
		$('#table-$t').flexReload();		
	}
	var x_EnableDisableAclRule$tt= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#table-$tOrg').flexReload();
		$('#table-$t').flexReload();
		$('#table-$tt').flexReload();
	}	
	

	function DeleteSessionRule$tt(ID){
		DeleteSquidAclGroupTemp$tt
		if(confirm('$delete_rule_ask :'+ID)){
			var XHR = new XHRConnection();
			XHR.appendData('acl-rule-delete', ID);
			XHR.sendAndLoad('$page', 'POST',x_DeleteSquidAclRule$tt);	
			
			}
	
	}
	
	function EnableDisableSessionRule$tt(ID){
		var XHR = new XHRConnection();
		XHR.appendData('acl-rule-enable', ID);
		if(document.getElementById('sessionid_'+ID).checked){XHR.appendData('enable', '1');}else{XHR.appendData('enable', '0');}
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableAclRule$tt);
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
	$tt=$_GET["tt"];
	$tOrg=$_GET["tOrg"];
	$mainrule=$_GET["mainrule"];
	$search='%';
	$table="ext_time_quota_acl_rules";
	$page=1;
	$data = array();
	$data['rows'] = array();
	$FORCE_FILTER="AND ruleid='$mainrule'";

	
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
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error");}



	$data['page'] = $page;
	$data['total'] = $total;
	
	
	$budgets["s"]="{seconds}";
	$budgets["m"]="{minutes}";
	$budgets["h"]="{hours}";
	$budgets["d"]="{days}";
	$budgets["w"]="{weeks}";
	


	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$val=0;
		$color="black";
		$disable=Field_checkbox("sessionid_{$ligne['ID']}", 1,$ligne["enabled"],"EnableDisableSessionRule$tt('{$ligne['ID']}')");
		$ligne['rulename']=utf8_encode($ligne['rulename']);
		
		$delete=imgsimple("delete-24.png",null,"DeleteSessionRule$tt('{$ligne['ID']}')");
		if($ligne["enabled"]==0){$color="#8a8a8a";}

		
		
		if(preg_match("#([0-9]+)([a-z]+)#", $ligne["budget"],$re)){
			$budget=$re[1];
			$budget2=$re[2];
		
		}
		
		if(preg_match("#([0-9]+)([a-z]+)#", $ligne["period"],$re)){
			$period=$re[1];
			$period2=$re[2];
		
		}

		$period=$tpl->javascript_parse_text("$period {$budgets[$period2]}");
		$budget=$tpl->javascript_parse_text("$budget {$budgets[$budget2]}");
		$gplist=GetGroupsList($ligne['ID']);

		$data['rows'][] = array(
				'id' => "acl{$ligne['ID']}",
				'cell' => array("<a href=\"javascript:blur();\" OnClick=\"Loadjs('$MyPage?session-rule-js=yes&ID={$ligne['ID']}&t=$t&tOrg=$tOrg&mainrule=$mainrule&tt=$tt');\" style=\"font-size:16px;text-decoration:underline;color:$color\">{$ligne['rulename']}</a>$gplist",
						"<span style=\"font-size:14px;color:$color\">$budget</span>",
						"<span style=\"font-size:14px;color:$color\">$period</span>",
						$disable,
						$delete
		)
		);
}


echo json_encode($data);
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
		$arrayF=$acl->FlexArray($ligne["groupid"],1,12);
		$f[]="<div style='font-size:10px'>{$arrayF["ROW"]} ({$arrayF["ITEMS"]} ".$tpl->_ENGINE_parse_body('{items}').")</div>";
	}
	return @implode("\n", $f);
}


function session_rule_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	if(!is_numeric($_GET["t"])){$_GET["t"]=time();}
	$tt=$_GET["tt"];
	$tOrg=$_GET["tOrg"];
	$mainrule=$_GET["mainrule"];
	
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{session_manager}::{$_GET["ID"]}");

	if($mainrule>0){
		$q=new mysql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT QuotaName FROM ext_time_quota_acl WHERE ID=$mainrule","artica_backup"));
		$title=$title."::".$tpl->javascript_parse_text($ligne["QuotaName"]);
	}

	echo "YahooWin4('650','$page?session-rule=yes&t={$_GET["t"]}&ID={$_GET["ID"]}&tt=$tt&tOrg=$tOrg&mainrule=$mainrule','$title');";
}

function session_rule_tab(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$tOrg=$_GET["tOrg"];
	$mainrule=$_GET["mainrule"];

	$array["session-rule-settings"]='{settings}';
	if($ID>0){
		$array["items"]='{groups2}';
	}



	foreach ($array as $num=>$ligne){
		if($num=="rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'>
					<a href=\"squid.ext_time_quota_acl_rules.php?ID=$ID&t=$t&tOrg={$_GET["tOrg"]}&tt=$tt&tOrg=$tOrg&mainrule=$mainrule\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="items"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'>
					<a href=\"squid.ext_time_quota_acl_groups.php?aclid=$ID&t=$t&tOrg={$_GET["tOrg"]}&tt=$tt&tOrg=$tOrg&mainrule=$mainrule\"><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"$page?$num=yes&ID=$ID&t=$t&tOrg={$_GET["tOrg"]}&tt=$tt&tOrg=$tOrg&mainrule=$mainrule\"><span>$ligne</span></a></li>\n");

	}


	echo "
	<div id=main_session_rule2_zoom style='width:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_session_rule2_zoom').tabs();
		
		
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
	$tt=$_GET["tt"];
	$mainrule=$_GET["mainrule"];
	$btname="{add}";
	if($ID>0){
		$btname="{apply}";
		$q=new mysql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM ext_time_quota_acl_rules WHERE ID=$ID","artica_backup"));

	}

	if($ligne["budget"]==null){$ligne["budget"]="3h";}
	if($ligne["period"]==null){$ligne["period"]="1d";}
	if($ligne["rulename"]==null){$ligne["rulename"]="New budget rule";}
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	
	$budgets["s"]="{seconds}";
	$budgets["m"]="{minutes}";
	$budgets["h"]="{hours}";
	$budgets["d"]="{days}";
	$budgets["w"]="{weeks}";
	
	if(preg_match("#([0-9]+)([a-z]+)#", $ligne["budget"],$re)){
		$budget=$re[1];
		$budget2=$re[2];
		
	}
	
	if(preg_match("#([0-9]+)([a-z]+)#", $ligne["period"],$re)){
		$period=$re[1];
		$period2=$re[2];
	
	}	

	$html="
	<div id='$tt'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{rule_name}:</td>
		<td colspan=2>". Field_text("rulename-$tt",$ligne["rulename"],"font-size:16px;width:210px",null,null,null,false,"SaveQ$t(event)")."</td>
		
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{budget}:</td>
		<td colspan=2>
			<table width=5%>
				<tr>
				<td>". Field_text("budget-$tt",$budget,"font-size:16px;width:90px",null,null,null,false,"SaveQ$t(event)")."</td>
				<td width=1%>". Field_array_Hash($budgets,"budget2-$tt",$budget2,null,'',0,"font-size:16px")."</td>
				</tr>
			</table>
		</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:16px'>{period}:</td>
		<td colspan=2>
				<table width=5%>
				<tr>		
				<td>". Field_text("period-$tt",$period,"font-size:16px;width:90px",null,null,null,false,"SaveQ$t(event)")."</td>
				<td width=1%>". Field_array_Hash($budgets,"period2-$tt",$period2,null,'',0,"font-size:16px")."</td>
				</tr>
				</table>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{enabled}:</td>
		<td colspan=2>". Field_checkbox("enabled-$tt",1,$ligne["enabled"],"Check$tt()")."</td>
		
	</tr>
	<tr>
	<td colspan=3 align='right'><hr>". button($btname,"Save$tt()","18px")."</td>
	</tr>
			</table>
			<script>
			var x_Save$tt= function (obj) {
				var ID=$ID;
				document.getElementById('$tt').innerHTML='';
				var res=obj.responseText;
				if(res.length>3){alert(res);return;}
				$('#table-$tOrg').flexReload();
				$('#table-$t').flexReload();
				$('#table-$tt').flexReload();
				if(ID<1){YahooWin4Hide();return;}
				RefreshTab('main_session_rule2_zoom');
			}

	function SaveQ$tt(e){
		if(checkEnter(e)){ Save$tt();}
	}

	function Save$tt(){
				var XHR = new XHRConnection();
				var QuotaName=encodeURIComponent(document.getElementById('rulename-$tt').value);
				var enabled=0;
				XHR.appendData('rulename', QuotaName);
				XHR.appendData('ID', '$ID');
				XHR.appendData('mainrule', '$mainrule');
				XHR.appendData('budget', document.getElementById('budget-$tt').value+document.getElementById('budget2-$tt').value);
				XHR.appendData('period', document.getElementById('period-$tt').value+document.getElementById('period2-$tt').value);
				if(document.getElementById('enabled-$tt').checked){enabled=1;}
				XHR.appendData('enabled', enabled);
				XHR.sendAndLoad('$page', 'POST',x_Save$tt);
				AnimateDiv('$t');
		}
	
</script>
";

echo $tpl->_ENGINE_parse_body($html);
}
function session_rule_settings_save(){
	$q=new mysql();
	$_POST["rulename"]=url_decode_special_tool($_POST["rulename"]);
	$mainrule=$_POST["mainrule"];
	$ID=$_POST["ID"];
	if($ID>0){
		$sql="UPDATE ext_time_quota_acl_rules SET
		rulename='{$_POST["rulename"]}',
		budget='{$_POST["budget"]}',
		period='{$_POST["period"]}',
		enabled='{$_POST["enabled"]}' WHERE ID=$ID
		";
	}else{
		$sql="INSERT IGNORE INTO ext_time_quota_acl_rules (rulename,budget,period,enabled,ruleid)
		VALUES('{$_POST["rulename"]}','{$_POST["budget"]}','{$_POST["period"]}','{$_POST["enabled"]}','$mainrule')";
	}
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function session_rule_delete(){
	$ID=$_POST["acl-rule-delete"]; //ext_time_quota_acl_rules
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM ext_time_quota_acl_link WHERE ruleid='$ID'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM ext_time_quota_acl_rules WHERE ID='$ID'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}
function session_rule_enable(){
	$ID=$_POST["acl-rule-enable"]; //ext_time_quota_acl_rules
	$value=$_POST["enable"];
	$q=new mysql();
	
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT enabled FROM ext_time_quota_acl_rules WHERE ID=$ID","artica_backup"));
	if($ligne["enabled"]==0){$value=1;}else{$value=0;}
	$q->QUERY_SQL("UPDATE ext_time_quota_acl_rules SET enabled='$value' WHERE ID='$ID'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}

}

