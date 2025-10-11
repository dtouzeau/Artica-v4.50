<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.tcpip.inc');
	
	
	
	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){exit();}
	if(isset($_GET["arpquery"])){arp_query();exit;}
	if(isset($_POST["ArpDeleteSingle"])){arp_delete();exit;}
	if(isset($_GET["delete-arp-js"])){delete_arp_js();exit;}
	if(isset($_GET["add-arp-js"])){add_arp_js();exit;}
	if(isset($_GET["arp-edit"])){arp_form();exit;}
	if(isset($_POST["ArpEdit"])){apr_edit();exit;}
	if(isset($_GET["EnableArpDaemon"])){EnableArpDaemonSave();exit;}
	if(isset($_GET["arpd-options-js"])){ARPD_OPTIONS_JS();exit;}
	if(isset($_GET["arpd-options-popup"])){ARPD_OPTIONS_POPUP();exit;}
	if(isset($_POST["ArpdKernelLevel"])){ARPD_OPTIONS_SAVE();exit;}
	if(isset($_GET["arp-form"])){arp_options();exit;}
page();


function delete_arp_js(){
	header("content-type: application/x-javascript");
	$q=new mysql();
	$ID=$_GET["delete-arp-js"];
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM arpcache WHERE ID=$ID","artica_backup"));
	$page=CurrentPageName();
	$tpl=new templates();	
	if($ligne['mac']==null){$ligne['mac']=$tpl->javascript_parse_text("{corrupted}");}
	$delete=$tpl->javascript_parse_text("{delete}");
	
$html="
	var x_ArpDeleteSingle= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#flex2Arpd').remove();
		RefreshTab('tabs_listnics2');
		
	}

function ArpDeleteSingle(){
	if(confirm('$delete IP:{$ligne["ipaddr"]} MAC:{$ligne['mac']}')){
		var XHR = new XHRConnection();
		XHR.appendData('ArpDeleteSingle','$ID');
		AnimateDiv('ARPD_EXPLAIN');
		XHR.sendAndLoad('$page', 'POST',x_ArpDeleteSingle);	
	
	}
}
ArpDeleteSingle();
";

echo $html;
}

function add_arp_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{arp_table} {apply}");
	if($_GET["add-arp-js"]<1){
		$title=$tpl->_ENGINE_parse_body("{arp_table} {add}");
	}
	echo "YahooWin4('450','$page?arp-edit={$_GET["add-arp-js"]}','$title')";
}

function arp_options(){
	$tpl=new templates();
	$page=CurrentPageName();
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$mac=$tpl->_ENGINE_parse_body("{ComputerMacAddress}");
	$nic=$tpl->_ENGINE_parse_body("{nic}");
	$title=$tpl->_ENGINE_parse_body("{arp_table}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$sock=new sockets();
	$EnableArpDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArpDaemon"));
		
	$EnableArpDaemon_form=Paragraphe_switch_img("{EnableArpDaemon}", "{EnableArpDaemon_explain}","EnableArpDaemon",$EnableArpDaemon,null,550);	
	
	$html="	
	<div class=explain id='ARPD_EXPLAIN' style='font-size:16px'>{ARPD_EXPLAIN}</div>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td valign='top' width=1%>$EnableArpDaemon_form
			<table style='width:100%'>
			<tbody>
			<tr>
			<td width=1% nowrap><a href=\"javascript:Loadjs('$page?arpd-options-js');\" 
			style='font-size:13px;text-decoration:underline'>{arpd_options}</a></td>
			<td width=99%><hr><div style='width:100%;text-align:right'>". button("{apply}","SaveARpd()",16)."</td>
			</tr>
			</tbody>
			</table>
	</tr>
	</table>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function page(){
	$tpl=new templates();
	$page=CurrentPageName();
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$mac=$tpl->_ENGINE_parse_body("{ComputerMacAddress}");
	$nic=$tpl->_ENGINE_parse_body("{nic}");
	$title=$tpl->_ENGINE_parse_body("{arp_table}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$sock=new sockets();
	$EnableArpDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArpDaemon"));
	if(!is_numeric($EnableArpDaemon)){$EnableArpDaemon=1;}	
	$settings=$tpl->_ENGINE_parse_body("{parameters}");
	$EnableArpDaemon_form=Paragraphe_switch_img("{EnableArpDaemon}", "{EnableArpDaemon_explain}","EnableArpDaemon",$EnableArpDaemon,null,550);

	$html="
<table class='flex2Arpd' style='display: none' id='flex2Arpd' style='width:99%'></table>
<script>
$(document).ready(function(){
$('#flex2Arpd').flexigrid({
	url: '$page?arpquery=yes',
	dataType: 'json',
	colModel : [
		{display: '$mac', name : 'mac', width :132, sortable : true, align: 'left'},
		{display: '$ipaddr', name : 'ipaddr', width :100, sortable : true, align: 'left'},
		{display: '$hostname', name : 'hostname', width :400, sortable : true, align: 'left'},
		{display: '$nic', name : 'iface', width : 40, sortable : true, align: 'left'},
		{display: '$delete', name : 'delete', width : 56, sortable : false, align: 'center'},


		],
		
	buttons : [
		{name: 'Add', bclass: 'add', onpress : AddArpTable},
		{separator: true},
		{name: '$settings', bclass: 'Search', onpress : ArpTableForm},
		],
		
		
	searchitems : [
		{display: '$ipaddr', name : 'ipaddr'},
		{display: '$mac', name : 'mac'},
		{display: '$hostname', name : 'hostname'},
		{display: '$nic', name : 'iface'}
		],
	sortname: 'ipaddr',
	sortorder: 'asc',
	usepager: true,
	title: '<psan style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: true,
	width: '99%',
	height: 550,
	singleSelect: true
	
	});   
});

function ArpTableForm(){
	YahooWin2('650','$page?arp-form=yes','$settings')
}

function AddArpTable(){
	Loadjs('$page?add-arp-js=-1');
}

function SaveARpd(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableArpDaemon',document.getElementById('EnableArpDaemon').value);
	XHR.sendAndLoad('$page', 'GET'); 
	YahooWin2Hide();
}	




</script>";
	
	echo $html;
}

function arp_form(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$ID=$_GET["arp-edit"];
	$button="{apply}";
	if($ID<1){$button="{add}";}
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM arpcache WHERE ID=$ID","artica_backup"));	
	$html="
	<div id='arpdiv'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:16px'>{ipaddr}:</td>
		<td>". field_ipv4("ARP_IP", $ligne["ipaddr"],"font-size:16px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{ComputerMacAddress}:</td>
		<td>". Field_text("ARP_MAC",$ligne["mac"],"font-size:16px;padding:5px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button($button,"EditArpEntry()")."</td>
	</tr>
	</tbody>
	</table>
	</div>
	<script>
	
	var x_EditArpEntry= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		if(document.getElementById('flex2Arpd')){ $('#flex2Arpd').remove();}
		if(document.getElementById('tabs_listnics2')){RefreshTab('tabs_listnics2');}
		YahooWin4Hide();
	}	
	
function EditArpEntry(){

		var XHR = new XHRConnection();
		XHR.appendData('ArpEdit','$ID');
		XHR.appendData('ARP_IP',document.getElementById('ARP_IP').value);
		XHR.appendData('ARP_MAC',document.getElementById('ARP_MAC').value);
		AnimateDiv('arpdiv');
		XHR.sendAndLoad('$page', 'POST',x_EditArpEntry);	
	
	
}
</script>	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function apr_edit(){
	$tpl=new templates();
	$page=CurrentPageName();	
	if (!IsPhysicalAddress($_POST["ARP_MAC"])) {echo $tpl->javascript_parse_text("{WARNING_MAC_ADDRESS_CORRUPT}");return;}
	$sock=new sockets();
	$datas=urlencode(base64_encode(serialize($_POST)));
	$sock->getFrameWork("network.php?arp-edit=$datas");
}

function arp_query(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	
	$search='%';
	$table="arpcache";
	$page=1;
	$ORDER="ORDER BY ipaddr ASC";
	
	
	if($q->COUNT_ROWS($table,"artica_backup")==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="WHERE (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(ID) as TCOUNT FROM `$table` $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$total = $q->COUNT_ROWS($table,"artica_backup");
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM `$table` $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql,"artica_backup");
	$divstart="<span style='font-size:14px;font-weight:bold'>";
	$divstop="</div>";
	while ($ligne = mysqli_fetch_assoc($results)) {
		
			if(trim($ligne['mac'])==null){$ligne['mac']="<img src='img/status_warning.png'>";}
			$delete=$tpl->_ENGINE_parse_body(imgtootltip("delete-24.png","{delete}","Loadjs('$MyPage?delete-arp-js={$ligne['ID']}')"));
			$edit="<a href=\"javascript:blur();\" OnClick=\"Loadjs('$MyPage?add-arp-js={$ligne['ID']}');\" 
			style='font-size:14px;font-weight:bold;text-decoration:underline'>";
			
		
		$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array("$divstart{$ligne['mac']}$divstop", $divstart.$edit.$ligne['ipaddr']."</a>$divstop", $divstart.$ligne["hostname"].$divstop, $divstart.$ligne['iface'].$divstop,$delete)
		);
	}
	
	
echo json_encode($data);		
}

function arp_delete(){
	$q=new mysql();
	$ID=$_POST["ArpDeleteSingle"];
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM arpcache WHERE ID=$ID","artica_backup"));
	$sock=new sockets();
	$sock->getFrameWork("network.php?arp-delete={$ligne["ipaddr"]}");
	
}

function EnableArpDaemonSave(){
	$sock=new sockets();
	$sock->SET_INFO("EnableArpDaemon",$_GET["EnableArpDaemon"]);
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");	
	$sock->getFrameWork("services.php?restart-arpd=yes");	
}

function ARPD_OPTIONS_JS(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$title=$tpl->_ENGINE_parse_body("{arpd_options}");
	$html="YahooWin4('650','$page?arpd-options-popup=yes','$title')";
	echo $html;
	
}



function ARPD_OPTIONS_POPUP(){
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$ArpdKernelLevel=$sock->GET_INFO("ArpdKernelLevel");
	if(!is_numeric($ArpdKernelLevel)){$ArpdKernelLevel=0;}
	$array[0]="{arpd_no_kernel_replacement}";
	$array[1]="{arpd_enable_kernel_helper}";
	$array[3]="{arpd_replace_kernel}";
	
	$html="
	<div id='ArpdKernelLevelDiv'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px'>{kernel_level}:</td>
		<td>". Field_array_Hash($array, "ArpdKernelLevel",$ArpdKernelLevel,"style:font-size:14px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveArpdKernelLevel()",14)."</td>
	</tr>
	</table>
	</div>
<script>
	var x_SaveArpdKernelLevel= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		YahooWin4Hide();
	}	
	
function SaveArpdKernelLevel(){
		var XHR = new XHRConnection();
		XHR.appendData('ArpdKernelLevel',document.getElementById('ArpdKernelLevel').value);
		AnimateDiv('ArpdKernelLevelDiv');
		XHR.sendAndLoad('$page', 'POST',x_SaveArpdKernelLevel);	
}
</script>	
	";
echo $tpl->_ENGINE_parse_body($html);
}
function ARPD_OPTIONS_SAVE(){
	$sock=new sockets();
	$sock->SET_INFO("ArpdKernelLevel", $_POST["ArpdKernelLevel"]);
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
	$sock->getFrameWork("services.php?restart-arpd=yes");		
}