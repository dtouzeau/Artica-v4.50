<?php
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
include_once('ressources/class.tcpip.inc');

if(isset($_GET["list"])){table_list();exit;}
if(isset($_GET["js-popup"])){js_popup();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["id"])){save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
page();

function js_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$ruleid=intval($_GET["ruleid"]);
	if($ruleid==0){$title=$tpl->javascript_parse_text("{new_rule}");}
	if($ruleid>0){
		$q=new mysql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT interface,checkip FROM lsm_rules WHERE id='$ruleid'",'artica_backup'));
		$title=$ligne["interface"].":: {$ligne["checkip"]}";
	}
	echo "YahooWin(990,'$page?popup=yes&ruleid=$ruleid','$title')";

}

function delete_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$ruleid=intval($_GET["delete-js"]);
	$t=time();
	
echo "var xSave$t=function (obj) {
		var tempvalue=obj.responseText;
		if (tempvalue.length>5){alert(tempvalue);return;}
		if(document.getElementById('LSM_TABLE_LIST')){ $('#LSM_TABLE_LIST').flexReload(); }
	}
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('delete','$ruleid');
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	 Save$t();";
}

function delete(){
	
	$q=new mysql();
	
	$sqlad="DELETE FROM lsm_rules where id='{$_POST["delete"]}'";
	$q->QUERY_SQL($sqlad,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ruleid=intval($_GET["ruleid"]);
	$btname="{add}";
	if($ruleid==0){$title=$tpl->_ENGINE_parse_body("{new_rule}");}
	if($ruleid>0){
		$q=new mysql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM lsm_rules WHERE id='$ruleid'",'artica_backup'));
		$title=$ligne["interface"].":: {$ligne["checkip"]}";
		$btname="{apply}";
	}
	
	$ip=new networking();
	$btname=$tpl->_ENGINE_parse_body($btname);
	
	$interfaces=$ip->Local_interfaces();
	unset($interfaces["lo"]);
	$t=time();
	$array[null]="{all}";
	$array2[null]="{all}";
	foreach ($interfaces as $eth){
		if(preg_match("#^(gre|dummy)#", $eth)){continue;}
		$nic=new system_nic($eth);
		$arrayNIC[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
		$arrayNIC2[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
	
	}

	$forward_interface=$tpl->_ENGINE_parse_body("{forward_interface}");	
	if(intval($ligne["max_packet_loss"])==0){$ligne["max_packet_loss"]=15;}
	if(intval($ligne["max_successive_pkts_lost"])==0){$ligne["max_successive_pkts_lost"]=7;}
	if(intval($ligne["min_packet_loss"])==0){$ligne["min_packet_loss"]=5;}
	if(intval($ligne["min_successive_pkts_rcvd"])==0){$ligne["min_successive_pkts_rcvd"]=10;}
	if(intval($ligne["interval_ms"])==0){$ligne["interval_ms"]=1000;}
	if(intval($ligne["timeout_ms"])==0){$ligne["timeout_ms"]=1000;}
	if(intval($ligne["ttl"])==0){$ligne["ttl"]=64;}
	if(intval($ligne["action"])==0){$ligne["action"]=1;}
	if(!isset($ligne["enabled"])){$ligne["enabled"]=1;}
	
	$actionconfig=unserialize(base64_decode($ligne["actionconfig"]));
	
	$action_array=action_array();
	
	$html="<div style='width:98%' class=form>
	<div style='font-size:30px;margin-bottom:20px'>$title</div>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:22px;'>{enabled}:</td>
			<td style='font-size:20px'>". Field_checkbox_design("enabled-$t",1,$ligne["enabled"])."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px;'>{target}:</td>
			<td style='font-size:20px'>". field_ipv4("checkip-$t",$ligne["checkip"],"font-size:22px")."</td>
		</tr>						
		<tr>
			<td class=legend style='font-size:22px;'>{listen_interface}:</td>
			<td style='font-size:20px'>". Field_array_Hash($arrayNIC, "interface-$t",$ligne["interface"],"style:font-size:22px;")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px;'>{max_packet_loss}:</td>
			<td style='font-size:22px'>". field_text("max_packet_loss-$t", $ligne["max_packet_loss"],"font-size:22px;width:90px;")."</td>
		</tr>					
		<tr>
			<td class=legend style='font-size:22px;'>{interval}:</td>
			<td style='font-size:22px'>". field_text("interval_ms-$t", $ligne["interval_ms"],
					"font-size:22px;width:90px;")." {milliseconds}</td>
		</tr>	
		</tr>					
		<tr>
			<td class=legend style='font-size:22px;'>{timeout2}:</td>
			<td style='font-size:22px'>". field_text("timeout_ms-$t", $ligne["timeout_ms"],
					"font-size:22px;width:90px;")." {milliseconds}</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px;'>{ttl}:</td>
			<td style='font-size:22px'>". field_text("ttl-$t", $ligne["ttl"],
					"font-size:22px;width:90px;")."</td>
		</tr>																
		<tr>
			<td class=legend style='font-size:22px;'>{max_successive_pkts_lost}:</td>
			<td style='font-size:22px'>". field_text("max_successive_pkts_lost-$t", $ligne["max_successive_pkts_lost"],"font-size:22px;width:90px;")."</td>
		</tr>			
		<tr>
			<td class=legend style='font-size:22px;'>{min_packet_loss}:</td>
			<td style='font-size:22px'>". field_text("min_packet_loss-$t", $ligne["min_packet_loss"],"font-size:22px;width:90px;")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:22px;'>{min_successive_pkts_rcvd}:</td>
			<td style='font-size:22px'>". field_text("min_successive_pkts_rcvd-$t", $ligne["min_successive_pkts_rcvd"],"font-size:22px;width:90px;")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px;'>{action}:</td>
			<td style='font-size:20px'>". Field_array_Hash($action_array, "action-$t",$ligne["action"],"ChangeActionConfig$t()",null,0,"font-size:22px;")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px;'>Proxy: $forward_interface:</td>
			<td style='font-size:20px'>". Field_array_Hash($arrayNIC, "forward_interface-$t",$actionconfig["forward_interface"],"style:font-size:22px;")."</td>
		</tr>					
		<tr>
			<td colspan=2 align='right'><hr>". button($btname,"Save$t()",32)."</td>
		</tr>						
	</table>																		
	</div>
					
<script>

function ChangeActionConfig$t(){
	document.getElementById('forward_interface-$t').disabled=true;
	
	if(document.getElementById('action-$t').value=='2'){
		document.getElementById('forward_interface-$t').disabled=false;
	}
}

	var xSave$t=function (obj) {
		var NextID=0;
		var tempvalue=obj.responseText;
		if (tempvalue.length>5){alert(tempvalue);return;}
		var ID=$ruleid;
		if(document.getElementById('LSM_TABLE_LIST')){ $('#LSM_TABLE_LIST').flexReload(); }
		if(ID==0){ YahooWinHide();}
	}
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('id','$ruleid');
		XHR.appendData('interface',document.getElementById('interface-$t').value);
		XHR.appendData('max_packet_loss',document.getElementById('max_packet_loss-$t').value);
		XHR.appendData('interval_ms',encodeURIComponent(document.getElementById('interval_ms-$t').value));
		XHR.appendData('timeout_ms',document.getElementById('timeout_ms-$t').value);
		XHR.appendData('ttl',document.getElementById('ttl-$t').value);
		XHR.appendData('checkip',document.getElementById('checkip-$t').value);
		
		
		XHR.appendData('max_successive_pkts_lost',document.getElementById('max_successive_pkts_lost-$t').value);
		XHR.appendData('min_packet_loss',document.getElementById('min_packet_loss-$t').value);
		XHR.appendData('min_successive_pkts_rcvd',document.getElementById('min_successive_pkts_rcvd-$t').value);
		XHR.appendData('action',document.getElementById('action-$t').value);
		XHR.appendData('forward_interface',document.getElementById('forward_interface-$t').value);
		if(document.getElementById('enabled-$t').checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
		XHR.sendAndLoad('$page', 'POST',xSave$t);	
	}										
ChangeActionConfig$t();					
</script>					
";
	echo $tpl->_ENGINE_parse_body($html);
}

function save(){
	$q=new mysql();
	$id=$_POST["id"];
	unset($_POST["id"]);
	$forward_interface=$_POST["forward_interface"];
	$actionconfigR["forward_interface"]=$_POST["forward_interface"];
	
	unset($_POST["forward_interface"]);
	
	$actionconfig=base64_encode(serialize($actionconfigR));
	$tr1[]="`actionconfig`";
	$tr2[]="'$actionconfig'";
	$tre[]="`actionconfig`='$actionconfig'";
	foreach ($_POST as $key=>$value){
		$tr1[]="`$key`";
		$tr2[]="'$value'";
		$tre[]="`$key`='$value'";
	}
	
	$sqled="UPDATE lsm_rules SET ".@implode( ",",$tre)." WHERE id='$id'";
	$sqlad="INSERT IGNORE INTO lsm_rules (".@implode(",", $tr1).") VALUES (".@implode(",", $tr2).")";
	if($id==0){ $q->QUERY_SQL($sqlad,"artica_backup");}else{$q->QUERY_SQL($sqled,"artica_backup");}
	if(!$q->ok){echo $q->mysql_error;}
	
}

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS2}");
		return;
	}
	
	if(!$q->TABLE_EXISTS("lsm_rules", "artica_backup")){
		echo FATAL_ERROR_SHOW_128("Missing MySQL Table artica_backup.lsm_rules");
		return;
	}
	
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$organization=$tpl->_ENGINE_parse_body("{organization}");
	$context=$tpl->_ENGINE_parse_body("{context}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");
	$sock=new sockets();
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}
	$EnableipV6=$sock->GET_INFO("EnableipV6");
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}

	$nic=$tpl->_ENGINE_parse_body("{nic}");
	$netmask=$tpl->_ENGINE_parse_body("{netmask}");
	$tcp_address=$tpl->_ENGINE_parse_body("{tcp_address}");
	$broadcast_has_ipaddr=$tpl->_ENGINE_parse_body("{broadcast_has_ipaddr}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$add_default_www=$tpl->_ENGINE_parse_body("{add_default_www}");
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_freeweb_text}");
	$delete_freeweb_dnstext=$tpl->javascript_parse_text("{delete_freeweb_dnstext}");
	$NoGatewayForVirtualNetWork=$tpl->_ENGINE_parse_body("{NoGatewayForVirtualNetWork}");
	$apply_network_configuration=$tpl->_ENGINE_parse_body("{apply}");
	$action=$tpl->_ENGINE_parse_body("{action}");
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$apply_network_configuration_warn=$tpl->javascript_parse_text("{apply_network_configuration_warn}");
	$virtual_interfaces=$tpl->_ENGINE_parse_body("{virtual_interfaces}");
	$reboot_network=$tpl->javascript_parse_text("{reboot_network}");
	$users=new usersMenus();
	$title=$tpl->javascript_parse_text("{LinkStatusMonitor} {rules}");

	$tablewidth=874;
	$servername_size=412;

	$t=time();

	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_rule</strong>', bclass: 'add', onpress : Add$t},
	{name: '<strong style=font-size:18px>$apply_network_configuration</strong>', bclass: 'Reconf', onpress : BuildNetConf$t},

	],";
	$html="
	<table class='LSM_TABLE_LIST' style='display: none' id='LSM_TABLE_LIST' style='width:100%'></table>
<script>
$(document).ready(function(){
	$('#LSM_TABLE_LIST').flexigrid({
			url: '$page?list=yes&t=$t',
			dataType: 'json',
			colModel : [
			{display: '<span style=font-size:22px>$nic</span>', name : 'interface', width :90, sortable : true, align: 'left'},
			{display: '<span style=font-size:22px>$tcp_address</span>', name : 'checkip', width :230, sortable : true, align: 'left'},
			{display: '<span style=font-size:22px>$action</span>', name : 'action', width : 758, sortable : true, align: 'left'},
			{display: '&nbsp;', name : 'none2', width : 60, sortable : false, align: 'center'},

			],
			$buttons

			searchitems : [
			{display: '$nic', name : 'interface'},
			{display: '$tcp_address', name : 'checkip'},
			],
			sortname: 'interface',
			sortorder: 'asc',
			usepager: true,
			title: '<span style=font-size:30px>$title</span>',
			useRp: true,
			rp: 50,
			showTableToggleBtn: false,
			width: '99%',
			height: 550,
			singleSelect: true

	});
});

function Add$t(){
	Loadjs('$page?js-popup=yes&ruleid=0');		
}

function BuildNetConf$t(){
	Loadjs('lsm.restart.php');
}
</script>";

echo $html;




}

function action_array(){
	$users=new usersMenus();
	$tpl=new templates();
	$ACTIONZ[1]="{notify_with_network_report}";
	if($users->SQUID_INSTALLED){
		$SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
		if($SQUIDEnable==1){
			$ACTIONZ[2]="{notify_and_change_proxy_interface}";
			$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
			if($EnableKerbAuth==1){
				$ACTIONZ[3]="{emergency_mode} (Active Directory)";
			}
			
			
		}
	}
	$ACTIONZ[4]=$tpl->javascript_parse_text("{NotifyAndReloadNetworkServices)");
	
	return $ACTIONZ;
}

function table_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";
	$table="lsm_rules";
	$t=$_GET["t"];
	$search='%';
	$sock=new sockets();
	$page=1;
	$FORCE_FILTER=null;

	if($q->COUNT_ROWS($table,$database)==0){
		$q-QUERY_SQL("INSERT IGNORE INTO lsm_rules (interface,checkip,enabled) VALUES ('eth0','8.8.8.8',1)","artica_backup");
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];

	}else{
		$total = $q->COUNT_ROWS($table, $database);
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";


	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	if(mysqli_num_rows($results)==0){json_error_show(__LINE__.' no data',1);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error);return;}

	$net=new networking();
	$ACTIONZ=action_array();


	while ($ligne = mysqli_fetch_assoc($results)) {
		$id=$ligne["id"];
		$checkip=$ligne["checkip"];
		$max_packet_loss=$ligne["max_packet_loss"];
		$max_successive_pkts_lost=$ligne["max_successive_pkts_lost"];
		$min_packet_loss=$ligne["min_packet_loss"];
		$min_successive_pkts_rcvd=$ligne["min_successive_pkts_rcvd"];
		$interval_ms=$ligne["interval_ms"];
		$timeout_ms=$ligne["timeout_ms"];
		$check_arp=$ligne["check_arp"];
		$device=$ligne["interface"];
		$enabled=$ligne["enabled"];

		$color="black";
		if($enabled==0){$color="#8a8a8a";}

		$action=$tpl->_ENGINE_parse_body($ACTIONZ[$ligne["action"]]);
		
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-js=$id')");
		$edit="<a href=\"javascript:blur();\" OnClick=\"Loadjs('$MyPage?js-popup=yes&ruleid=$id');\"
		style='color:$color;text-decoration:underline'>";

		$data['rows'][] = array(
		'id' => "$id",
		'cell' => array(
				"<span style='font-size:24px;color:$color;'>$edit$device</a></span>",
				"<span style='font-size:24px;color:$color;'>$edit{$checkip}</a></span>",
				"<span style='font-size:24px;color:$color;'>$edit{$action}</a></span>",
				"<center>$delete</center>"
				)
				);
	}


	echo json_encode($data);

}