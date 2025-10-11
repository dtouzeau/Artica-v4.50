<?php
	//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.tcpip.inc');
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsArticaAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}
	
	if(isset($_GET["role-js"])){role_js();exit;}
	if(isset($_GET["role-popup"])){role_popup();exit;}
	if(isset($_POST["step2"])){role_save();exit;}
	if(isset($_GET["step2"])){role_step2();exit;}
	if(isset($_GET["items"])){items();exit;}
	if(isset($_GET["delete-item-js"])){delete_item_js();exit;}
	if(isset($_POST["delete-item"])){delete_item();exit;}
	
	table();
	
function role_js(){
		header("content-type: application/x-javascript");
		$tpl=new templates();
		$page=CurrentPageName();
		$zmd5=$_GET["zmd5"];
		$t=$_GET["t"];
		$eth=$_GET["eth"];
		if($zmd5==null){$title=$tpl->javascript_parse_text("{new_role}");}
		if($zmd5<>null){
			$q=new mysql();
			$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM nics_roles WHERE zmd5='$zmd5'","artica_backup"));
			$ip=new system_nic($eth);
			$title=$tpl->javascript_parse_text("{interface}: $ip->NICNAME - ".$ligne["role"]);
		}
	
		echo "YahooWin3('650','$page?role-popup=yes&zmd5=$zmd5&t=$t&eth=$eth','$title')";
}
function delete_item_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$q=new mysql_shorewall();
	$remove=$tpl->javascript_parse_text("{delete_role}");
	$t=time();

echo "
var xRemove$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#flexRT{$_GET["t"]}').flexReload();
	$('#flexRT{$_GET["tt"]}').flexReload();
	$('#flexRT{$_GET["t-rule"]}').flexReload();
	ExecuteByClassName('SearchFunction');
}
function Remove$t(){
	if(!confirm('$remove ?') ){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-item',  '{$_GET["zmd5"]}');
	XHR.sendAndLoad('$page', 'POST',xRemove$t);
}

Remove$t();";

}	

function delete_item(){
	$zmd5=$_POST["delete-item"];
	$q=new mysql();
	
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM nics_roles WHERE zmd5='$zmd5'","artica_backup"));
	$role=$ligne["role"];
	$eth=$ligne["nic"];
	
	$q->QUERY_SQL("DELETE FROM nics_roles WHERE zmd5='$zmd5'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	if($role=="DHCP"){
		$sock=new sockets();
		$sock->getFrameWork("dnmasq.php?delete-dhcp-role=yes&eth=$eth");
	}
	
	
}

function role_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$zmd5=$_GET["zmd5"];
	$t=time();
	$eth=$_GET["eth"];
	$q->BuildTables();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM nics_roles WHERE zmd5='$zmd5'","artica_backup"));
	$users=new usersMenus();
	

	
	$html="
	<div id='STEP_{$_GET["t"]}'>
	<div style='width:98%' class=form>
		<div style='font-size:16px' class=explain>{create_role_explain}</div>
		<table style='width:100%;margin-top:20px'>	
			<tr>
				<td class=legend style='font-size:16px'>{role}:</td>
				<td>". Field_array_Hash($users->NicRoleArray, "role-$t",null,null,'',0,"font-size:16px")."</td>
			</tr>
		</table>
	</div>
		<table style='width:100%'>	
			<tr>
				<td style='font-size:16px' width=50% align='left'>". button("{cancel}","YahooWin3Hide();",14)."</td>
				<td style='font-size:16px' width=50% align='right'>". button("{next}","Save$t();",14)."</td>
			</tr>
		</table>				
	
</div>	
	<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length==0){return;}
	$('#flexRT{$_GET["t"]}').flexReload();
	LoadAjax('STEP_{$_GET["t"]}','$page?step2=yes&content='+results);
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('step2','yes');
	XHR.appendData('eth','$eth');
	XHR.appendData('t','{$_GET["t"]}');
	XHR.appendData('role',document.getElementById('role-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}		
</script>			
	";
	
echo $tpl->_ENGINE_parse_body($html);
	
	
}

function role_step2(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$ARRAY=unserialize(base64_decode($_GET["content"]));
	$zmd5=$ARRAY["zmd5"];
	$t=$ARRAY["t"];
	$tt=time();
	$eth=$ARRAY["eth"];
	$role=$ARRAY["role"];
	$users=new usersMenus();	
	$zmd5=md5("$eth$role");
	$q=new mysql();
	
	$sql="INSERT INTO nics_roles (zmd5,role,nic,status) 
			VALUES ('$zmd5','$role','$eth','{installed}')";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128($q->mysql_error));
		return;
	}
	
	if($role=="DHCP"){
		$sock=new sockets();
		$sock->getFrameWork("dnmasq.php?save-dhcp-role=yes&eth=$eth");
	}
	
	
	$roletitle=$users->NicRoleArray[$role];
	
	$html="
	<div style='font-size:18px;margin:15px'>$eth - $roletitle</div>
	<center id='animthis-$tt'></center>
			
	<script>
		function ff$tt(){
			$('#flexRT$t').flexReload();
			YahooWin3Hide();
		}
		
		AnimateDiv('animthis-$tt');
		setTimeout('ff$tt()',800);
		
	</script>
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function role_save(){
	echo urlencode(base64_encode(serialize($_POST)));
}


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	$type=$tpl->javascript_parse_text("{type}");
	$zone=$tpl->_ENGINE_parse_body("{zone}");
	$new_text=$tpl->javascript_parse_text("{new_role}");
	$role=$tpl->javascript_parse_text("{role}");
	$delete=$tpl->javascript_parse_text("{delete} {interface} ?");
	$rewrite_rules_fdb_explain=$tpl->javascript_parse_text("{rewrite_rules_fdb_explain}");
	$rebuild_tables=$tpl->javascript_parse_text("{rebuild_tables}");
	$comment=$tpl->javascript_parse_text("{comment}");
	
	
	$maintitle=$tpl->javascript_parse_text("{$_GET["eth"]}: {roles}");
	
	
	$apply=$tpl->javascript_parse_text("{apply}");
	
	
	
	$buttons="
		buttons : [
		{name: '$new_text', bclass: 'add', onpress : NewRule$tt},
		],";
	
	$html="
<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
<script>
	function Start$tt(){
		$('#flexRT$tt').flexigrid({
		url: '$page?items=yes&t=$tt&tt=$tt&eth={$_GET["eth"]}',
		dataType: 'json',
		colModel : [
		{display: '$role', name : 'role', width :387, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'status', width : 204, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
		],
		$buttons
		searchitems : [
		{display: '$role', name : 'role'},
	
		],
		sortname: 'role',
		sortorder: 'asc',
		usepager: true,
		title: '$maintitle',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 450,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
	
	});
	}
	
	var xNewRule$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
	}
	
	
	function NewRule$tt(){
	Loadjs('$page?role-js=yes&zmd5=&eth={$_GET["eth"]}&t=$tt','$new_text');
	}
	function Delete$tt(zmd5){
	if(confirm('$delete')){
	var XHR = new XHRConnection();
	XHR.appendData('interface-delete', zmd5);
	XHR.sendAndLoad('$page', 'POST',xNewRule$tt);
	}
	}
	
	function Apply$tt(){
	Loadjs('shorewall.php?apply-js=yes',true);
	}
	
	
	var xRuleEnable$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
	}
	
	
	function RuleEnable$tt(ID,md5){
	var XHR = new XHRConnection();
	XHR.appendData('rule-enable', ID);
	if(document.getElementById(md5).checked){XHR.appendData('enable', 1);}else{XHR.appendData('enable', 0);}
	XHR.sendAndLoad('$page', 'POST',xRuleEnable$tt);
	}
	var x_LinkAclRuleGpid$tt= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#table-$t').flexReload();
	$('#flexRT$tt').flexReload();
	ExecuteByClassName('SearchFunction');
	}
	function FlexReloadRulesRewrite(){
	$('#flexRT$t').flexReload();
	}
	
	function MoveRuleDestination$tt(mkey,direction){
	var XHR = new XHRConnection();
	XHR.appendData('rules-destination-move', mkey);
	XHR.appendData('direction', direction);
	XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);
	}
	
	function MoveRuleDestinationAsk$tt(mkey,def){
	var zorder=prompt('Order',def);
	if(!zorder){return;}
	var XHR = new XHRConnection();
	XHR.appendData('rules-destination-move', mkey);
	XHR.appendData('rules-destination-zorder', zorder);
	XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);
	}
	Start$tt();
	
	</script>
	";
	echo $html;
	
	}	
function items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	$t=$_GET["t"];
	$search='%';
	$table="nics_roles";
	
	
	$page=1;
	$FORCE_FILTER=null;
	$groupid=$_GET["groupid"];
	$total=0;
	$FORCE_FILTER="AND `nic`='{$_GET["eth"]}'";
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}else{
		$total = $q->COUNT_ROWS("nics_roles","artica_backup");
		}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	$no_rule=$tpl->_ENGINE_parse_body("{no_rule}");
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	if(mysqli_num_rows($results)==0){json_error_show("no data $sql");}
	$users=new usersMenus();
	
		$fontsize="16";
	
		while ($ligne = mysqli_fetch_assoc($results)) {
			$color="black";
			$NICNAME=null;
			$md5=$ligne["zmd5"];
			$delete=imgsimple("delete-24.png",null,"Loadjs('$MyPage?delete-item-js=yes&zmd5={$ligne["zmd5"]}&t={$_GET["t"]}')");
			
			$role=$tpl->_ENGINE_parse_body($users->NicRoleArray[$ligne["role"]]);
			$status=$tpl->_ENGINE_parse_body($ligne["status"]);
			
			if($ligne["role"]=="NAT"){
				$js="<a href=\"javascript:blur();\" 
				style='font-size:{$fontsize}px;text-decoration:underline'
				OnClick=\"Loadjs('shorewall.masq.php?eth={$_GET["eth"]}&md5=$md5&tsource={$_GET["t"]}');\">";
				
			}
			
			
			$data['rows'][] = array(
					'id' => $ligne['ID'],
					'cell' => array(
					"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$js$role</a></span>",
					"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$status</span>",
					"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>",)
			);
		}
	
	
		echo json_encode($data);
	
	}