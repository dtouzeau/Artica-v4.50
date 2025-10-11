<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.nmap.inc');
	include_once(dirname(__FILE__).'/ressources/class.computers.inc');
	
	$user=new usersMenus();
	if(!$user->AsWebStatisticsAdministrator){echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die("DIE " .__FILE__." Line: ".__LINE__);}
	if(isset($_GET["items-mac"])){items_mac();exit;}
	if(isset($_GET["items-ip"])){items_ip();exit;}
	if(isset($_GET["delete-mac-js"])){delete_mac_js();exit;}
	if(isset($_POST["delete-mac"])){delete_mac();exit;}
	if(isset($_GET["group-popup"])){group_popup();exit;}
	if(isset($_GET["delete-ipaddr-js"])){delete_ipaddr_js();exit;}
	if(isset($_GET["group-js"])){group_js();exit;}
	if(isset($_POST["delete-ipaddr"])){delete_ipaddr();exit;}
	if(isset($_POST["item"])){Save();exit;}
	if(isset($_GET["export-js"])){Export_js();exit;}
	if(isset($_GET["export-popup"])){Export_popup();exit;}
	
	if(isset($_GET["import-js"])){Import_js();exit;}
	if(isset($_GET["import-popup"])){Import_popup();exit;}
	if(isset($_POST["popup_import_list"])){Import_Save();exit;}
table();



function group_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();	
	$MAC_encode=urlencode($_GET["MAC"]);
	$ipencode=urlencode($_GET["IP"]);
	$title=$tpl->javascript_parse_text("{group}&nbsp;{$_GET["MAC"]}&nbsp;{$_GET["IP"]}");
	echo "YahooWin(890,'$page?group-popup=yes&table={$_GET["table"]}&MAC=$MAC_encode&IP=$ipencode','$title')";
	
	
}
function Import_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{import}");
	echo "YahooWin(890,'$page?import-popup=yes','$title')";

}
function export_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{export}");
	echo "YahooWin(890,'$page?export-popup=yes','$title')";
	
}
function Export_popup(){
	//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$f=array();
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM webfilters_nodes WHERE LENGTH(uid)>1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysqli_fetch_assoc($results)) {
		if($ligne["MAC"]=="00:00:00:00:00:00"){continue;}
		$f[]="{$ligne["MAC"]},{$ligne["uid"]},{$ligne["hostname"]},{$ligne["group"]}";


	}
	$sql="SELECT * FROM webfilters_ipaddr WHERE LENGTH(uid)>1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysqli_fetch_assoc($results)) {
		$f[]="{$ligne["ipaddr"]},{$ligne["uid"]},{$ligne["hostname"]},{$ligne["group"]}";


	}

	echo "<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:99%;height:546px;border:5px solid #8E8E8E;
	overflow:auto;font-size:14px !important' id='popup_import_list-$t'>".@implode("\n", $f)."</textarea>";

}

function Import_popup(){
	$page=CurrentPageName();
	$t=time();
	$html="
	<div id='popup_import_$t' class=form style='width:98%'>
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:99%;height:546px;border:5px solid #8E8E8E;
	overflow:auto;font-size:14px !important' id='popup_import_list-$t'></textarea>
	
	<div style='text-align:right'>
		<hr>
			". button("{import}","Save$t()",28)."
	</div>
</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	YahooWinHide();
	$('#PROXY_ALIASES_TABLE').flexReload();
}
	
function Save$t(){
	var XHR = new XHRConnection();
	var pp=encodeURIComponent(document.getElementById('popup_import_list-$t').value);
	XHR.appendData('popup_import_list',pp);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
	
}


function Import_Save(){
	$datas=url_decode_special_tool($_POST["popup_import_list"]);
	$tr=explode("\n",$datas);
	$IPclass=new IP();
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("webfilters_ipaddr", "ip")){$q->QUERY_SQL("ALTER TABLE `webfilters_ipaddr` ADD `ip` int(10) unsigned NOT NULL default '0',ADD INDEX ( `ip` )");}
	if(!$q->FIELD_EXISTS("webfilters_nodes", "group")){$q->QUERY_SQL("ALTER TABLE `webfilters_nodes` ADD `group` VARCHAR( 128 ),ADD INDEX ( `group` ) ");}
    foreach ($tr as $num=>$ligne){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		$tbl=explode(",",$ligne);
		$alias=trim($tbl[1]);
		$hostname=trim($tbl[2]);
		$grp=trim($tbl[3]);
		if($alias==null){continue;}
		if($IPclass->IsvalidMAC($tbl[0])){
			$sql="INSERT OR IGNORE INTO webfilters_nodes (MAC,uid,hostname,nmapreport,nmap,`group`) VALUES ('{$tbl[0]}','{$alias}','$hostname','',0,'{$grp}')";
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error;return;}
			
			$sql="UPDATE webfilters_nodes SET uid='{$alias}' WHERE MAC='{$tbl[0]}'";
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error;return;}

			continue;
		}

		if($IPclass->isIPAddress($tbl[0])){	
			$ip2Long2=ip2Long2($tbl[0]);
			$sql="INSERT OR IGNORE INTO webfilters_ipaddr (ipaddr,uid,ip,hostname,`group`) VALUES ('{$tbl[0]}','{$alias}','$ip2Long2','$hostname','$grp')"; 
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo "Fatal:".$q->mysql_error;return;}
			$sql="UPDATE webfilters_ipaddr SET uid='{$alias}' WHERE ipaddr='{$tbl[0]}'";
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error;return;}
				
		}
		//d8:9e:3f:34:2d:8d,iPhoneAlex,,childs
		
		
	}
}


function  delete_ipaddr_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$MAC=$_GET["delete-ipaddr-js"];
	$delete=$tpl->javascript_parse_text("{delete} $MAC?" );
	
	echo "
	var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#PROXY_ALIASES_TABLE').flexReload();
	}
	
	function Save$t(){
	if(!confirm('$delete')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-ipaddr',  '$MAC');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	
	
	Save$t();";
	}

function delete_mac_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$MAC=$_GET["delete-mac-js"];
$delete=$tpl->javascript_parse_text("{delete} $MAC?" );

echo "
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#PROXY_ALIASES_TABLE').flexReload();
}		

function Save$t(){
	if(!confirm('$delete')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-mac',  '$MAC');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
		
		
Save$t();";
}
function delete_mac(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilters_nodes WHERE MAC='{$_POST["delete-mac"]}'");
	if(!$q->ok){echo $q->mysql_error;}
	
}
function delete_ipaddr(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilters_ipaddr WHERE ipaddr='{$_POST["delete-ipaddr"]}'");
	if(!$q->ok){echo $q->mysql_error;}

}

function group_popup() {
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$table=$_GET["table"];
	$IP=trim($_GET["IP"]);
	$MAC=trim($_GET["MAC"]);
	$t=time();
	$GROUPS[null]="{select}";
	$IPclass=new IP();
	
	
	if($MAC<>null){
		if($IPclass->IsvalidMAC($MAC)){
			$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_nodes WHERE MAC='{$MAC}'"));
			$member=$ligne["uid"];
			$group=$ligne["group"];
			$sql="SELECT `group` FROM webfilters_nodes GROUP BY `group` ORDER BY `group`";
			$results = $q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo $q->mysql_error_html();}
			while ($ligne2 = mysqli_fetch_assoc($results)) {if(trim($ligne2["group"])==null){continue;}$GROUPS[$ligne2["group"]]=$ligne2["group"];}
			
			$field="
			<tr>
				<td class=legend style='font-size:26px'>{MAC}:</td>
				<td>". Field_text("$t-ITEM",$MAC,"font-size:26px;width:260px",$MAC,null,null,false,"LinkUserStatsDBcHeck$t(event)")."</td>
			</tr>
			";
			
		}
	}
	
	
	
	if($IP<>null){
		
		if($member==null){
			if($IPclass->isValid($_GET["ipaddr"])){
				$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_ipaddr WHERE ipaddr='{$IP}'"));
				$member=$ligne["uid"];
				$group=$ligne["group"];
				$sql="SELECT `group` FROM webfilters_ipaddr GROUP BY `group` ORDER BY `group`";
				$results = $q->QUERY_SQL($sql,"artica_backup");
				if(!$q->ok){echo $q->mysql_error_html();}
				
				while ($ligne2 = mysqli_fetch_assoc($results)) {if(trim($ligne2["group"])==null){continue;}$GROUPS[$ligne2["group"]]=$ligne2["group"];}
				
				
			}
		
			
		
		}
		
		$field="<tr>
				<td class=legend style='font-size:26px'>{ipaddr}:</td>
				<td>". field_ipv4("$t-ITEM",$IP,"font-size:26px;width:250px",null,null,null,false,"LinkUserStatsDBcHeck$t(event)")."</td>
			</tr>";
	}	
	
$html="<div id='div-$t' style='width:98%' class=form>
<div style='font-size:30px;margin-bottom:20px'>{proxy_alias}: {$_GET["MAC"]} / {$_GET["ipaddr"]}</div>
	<table style='width:100%'>
		$field
		<tr>
			<td class=legend style='font-size:26px'>{alias}:</td>
			<td>". Field_text("$t-uid",$member,"font-size:26px;width:550px",null,null,null,false,"LinkUserStatsDBcHeck$t(event)")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:26px'>{group}:</td>
			<td>". Field_array_Hash($GROUPS,"$t-group",$group,"style:font-size:26px;",null,null,null,false,"LinkUserStatsDBcHeck$t(event)")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:26px'>{group} ({new}):</td>
			<td>". Field_text("$t-group2",null,"font-size:26px;width:550px",null,null,null,false,"LinkUserStatsDBcHeck$t(event)")."</td>
		</tr>										
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","LinkUserStatsDB$t()",32)."</td>
		</tr>
	</table>
</div>
<script>
var x_LinkUserStatsDB$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	YahooWinHide();
	if(document.getElementById('main_node_infos_tab')){RefreshTab('main_node_infos_tab');}
		
	if(document.getElementById('OCS_SEARCH_TABLE')){
		var id=document.getElementById('OCS_SEARCH_TABLE').value;
		$('#'+id).flexReload();
	}
	if(document.getElementById('PROXY_ALIASES_TABLE')){
		$('#PROXY_ALIASES_TABLE').flexReload();
	}
		
	if(IsFunctionExists('RefreshNodesSquidTbl')){ RefreshNodesSquidTbl();}
	Loadjs('squid.macToUid.progress.php');
}
		
function LinkUserStatsDBcHeck$t(e){
	if(checkEnter(e)){LinkUserStatsDB$t();}
}
		
function LinkUserStatsDB$t(){
	var XHR = new XHRConnection();
	XHR.appendData('uid',document.getElementById('$t-uid').value);
	XHR.appendData('group',document.getElementById('$t-group').value);
	XHR.appendData('group2',document.getElementById('$t-group2').value);
	XHR.appendData('item',document.getElementById('$t-ITEM').value);
	XHR.appendData('table','$table');
	XHR.sendAndLoad('$page', 'POST',x_LinkUserStatsDB$t);
}
</script>
		
		";
		echo $tpl->_ENGINE_parse_body($html);	
}

function Save(){
	$q=new mysql_squid_builder();
	
	foreach ($_POST as $num=>$ligne){
		$_POST[$num]=mysql_escape_string2($ligne);
	}
	
	$group=$_POST["group"];
	if($_POST["group2"]<>null){$group=$_POST["group2"];}
	
	if($_POST["table"]=="webfilters_ipaddr"){
		
		$sql="UPDATE webfilters_ipaddr SET 
			`uid`='{$_POST["uid"]}',
			`ipaddr`='{$_POST["item"]}',
			`group`='$group' WHERE `ipaddr`='{$_POST["item"]}'";
		
	}
	if($_POST["table"]=="webfilters_nodes"){
	
		$sql="UPDATE webfilters_nodes SET
		`uid`='{$_POST["uid"]}',
		`MAC`='{$_POST["item"]}',
		`group`='$group' WHERE `MAC`='{$_POST["item"]}'";
	
		}	
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
}


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$groups=$tpl->javascript_parse_text("{groups}");
		$from=$tpl->_ENGINE_parse_body("{from}");
		$to=$tpl->javascript_parse_text("{to}");
		$rule=$tpl->javascript_parse_text("{rule}");
		$delete=$tpl->javascript_parse_text("{delete} {zone} ?");
		$rewrite_rules_fdb_explain=$tpl->javascript_parse_text("{rewrite_rules_fdb_explain}");
		$new_text=$tpl->javascript_parse_text("{new_proxy_alias}");
		$network=$tpl->javascript_parse_text("{network2}");
		$rules=$tpl->javascript_parse_text("{rules}");
		$item=$tpl->javascript_parse_text("{item}");
		$member=$tpl->javascript_parse_text("{member}");
		$delete=$tpl->javascript_parse_text("{delete}");
		$MAC=$tpl->javascript_parse_text("{MAC}");
		$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
		$title=$tpl->_ENGINE_parse_body("{my_proxy_aliases}");
		$proxy_alias=$tpl->javascript_parse_text("{proxy_alias}");
		$about2=$tpl->javascript_parse_text("{about2}");
		$my_proxy_aliases_text=$tpl->javascript_parse_text("{my_proxy_aliases_text}",0);
		$apply=$tpl->javascript_parse_text("{apply}");
		$computers=$tpl->javascript_parse_text("{computers}");
		$by_mac=$tpl->javascript_parse_text("{by_mac}");
		$by_ip=$tpl->javascript_parse_text("{by_ip}");
		$current_members=$tpl->javascript_parse_text("{current_members}");
		$VirtualGroup=$tpl->javascript_parse_text("{virtual_group}");
		$export=$tpl->javascript_parse_text("{export}");
		$import=$tpl->javascript_parse_text("{import}");
		$tt=time();
		$buttons="
		buttons : [
		{name: '<strong style=font-size:18px>$new_text</strong>', bclass: 'add', onpress : NewRule$tt},
		{name: '<strong style=font-size:18px>$by_mac</strong>', bclass: 'Search', onpress : ByMAC$tt},
		{name: '<strong style=font-size:18px>$by_ip</strong>', bclass: 'Search', onpress : ByIP$tt},
		{name: '<strong style=font-size:18px>$export</strong>', bclass: 'export', onpress : Export$tt},
		{name: '<strong style=font-size:18px>$import</strong>', bclass: 'import', onpress : Import$tt},
		{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'Reload', onpress : Apply$tt},
		{separator: true},
		{name: '<strong style=font-size:18px>$about2</strong>', bclass: 'Help', onpress : About2$tt},
		{name: '<strong style=font-size:18px>$computers</strong>', bclass: 'link', onpress : GotoNetworkBrowseComputers$tt},
		{name: '<strong style=font-size:18px>$current_members</strong>', bclass: 'link', onpress : GotoProxyCurrentMembers$tt},
		
		
	
		],";
	
$html="
<table class='PROXY_ALIASES_TABLE' style='display: none' id='PROXY_ALIASES_TABLE' style='width:100%'></table>
<script>
	function Start$tt(){
		$('#PROXY_ALIASES_TABLE').flexigrid({
		url: '$page?items-mac=yes',
		dataType: 'json',
		colModel : [
	
		{display: '<span style=font-size:20px>$proxy_alias</span>', name : 'uid', width :500, sortable : true, align: 'left'},
		{display: '<span style=font-size:20px>$item</span>', name : 'item', width : 331, sortable : true, align: 'left'},
		{display: '<span style=font-size:20px>$VirtualGroup</span>', name : 'group', width : 500, sortable : true, align: 'left'},
		{display: '<span style=font-size:20px>$delete</span>', name : 'vendor', width : 96, sortable : true, align: 'left'},
		
		],
		$buttons
		searchitems : [
		{display: '$proxy_alias', name : 'uid'},
		{display: '$item', name : 'item'},
		{display: '$VirtualGroup', name : 'group'},
		
		],
		sortname: 'uid',
		sortorder: 'asc',
		usepager: true,
		title: '<span style=font-size:30px>$title</span>',
		useRp: false,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 477,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
	
	});
	}

	
function Apply$tt(){
	Loadjs('squid.macToUid.progress.php');
}

function Export$tt(){
	Loadjs('$page?export-js=yes');
}

function Import$tt(){
	Loadjs('$page?import-js=yes');
}
	
	
function NewRule$tt(){
	javascript:Loadjs('squid.nodes.php?link-user-js=yes&MAC=&ipaddr=',true)
}

function GotoNetworkBrowseComputers$tt(){
	GotoNetworkBrowseComputers();
}
function GotoProxyCurrentMembers$tt(){
	GotoProxyCurrentMembers();
}

function About2$tt(){
	alert('$my_proxy_aliases_text');
}

function ByMAC$tt(){
	$('#PROXY_ALIASES_TABLE').flexOptions({url: '$page?items-mac=yes'}).flexReload(); 
}
function ByIP$tt(){
	$('#PROXY_ALIASES_TABLE').flexOptions({url: '$page?items-ip=yes'}).flexReload();
}
	
Start$tt();
</script>
";
echo $html;
	
}
	
function items_mac(){
	$page=1;
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("webfilters_nodes", "group")){$q->QUERY_SQL("ALTER TABLE `webfilters_nodes` ADD `group` VARCHAR( 128 ),ADD INDEX ( `group` ) ");}
	$table="webfilters_nodes";
	$MyPage=CurrentPageName();
	
	if($_POST["qtype"]=="item"){$_POST["qtype"]="MAC";}
	if($_POST["sortname"]=="item"){$_POST["sortname"]="MAC";}
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	
	if(isset($_GET["verbose"])){echo "<hr><code>$sql</code></hr>";}
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	if(!$q->ok){json_error_show($q->mysql_error,1);}

	if(mysqli_num_rows($results)==0){
		json_error_show("$table no data",1);
	}
	
	
	
		$fontsize="26px";
		$data = array();
		$data['page'] = 1;
		$data['total'] = mysqli_num_rows($results);
		$data['rows'] = array();
	
		
		$computer=new computers();
		$tpl=new templates();
		$unknown=$tpl->javascript_parse_text("{unknown}");
		
	while ($ligne = mysqli_fetch_assoc($results)) {
			$mac=$ligne["MAC"];
			$proxy_alias=$ligne["uid"];
			
			$macenc=urlencode($mac);
			$group=utf8_encode($ligne["group"]);
			if($group==null){$group="$unknown";}
			
				$proxy_alias="
				<a href=\"javascript:blur();\"
				style=\"text-decoration:underline\"
				OnClick=\"Loadjs('squid.nodes.php?node-infos-js=yes&MAC=$macenc');\"
				>$proxy_alias</a>";
				
				$delete=imgsimple("delete-42.png",null,"Loadjs('$MyPage?delete-mac-js=$macenc')");
				$MAC_encode=urlencode($mac);
			$data['rows'][] = array(
			'id' => md5(serialize($ligne)),
			'cell' => array("<span style='font-size:26px'>$proxy_alias</span>",
			"<span style='font-size:26px'>$mac</a></span>",
			"<a href=\"javascript:blur()\"
					OnClick=\"Loadjs('$MyPage?group-js=yes&table=webfilters_nodes&MAC=$MAC_encode');\"
					style='text-decoration:underline;font-size:26px'>$group</a>",
			"<center style='font-size:26px'>$delete</center>",

					)
		);
	}
	
	
	echo json_encode($data);	
}

function items_ip(){
	$page=1;
	$q=new mysql_squid_builder();
	$table="webfilters_ipaddr";
	$MyPage=CurrentPageName();
	if(!$q->FIELD_EXISTS("webfilters_ipaddr", "group")){$q->QUERY_SQL("ALTER TABLE `webfilters_ipaddr` ADD `group` VARCHAR( 128 ),ADD INDEX ( `group` ) ");}
	
	if($_POST["qtype"]=="item"){$_POST["qtype"]="ipaddr";}
	if($_POST["sortname"]=="item"){$_POST["sortname"]="ipaddr";}
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	
	if(isset($_GET["verbose"])){echo "<hr><code>$sql</code></hr>";}
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	
	if(mysqli_num_rows($results)==0){
		json_error_show("$table no data",1);
	}
	
	
	
	$fontsize="26px";
	$data = array();
	$data['page'] = 1;
	$data['total'] = mysqli_num_rows($results);
	$data['rows'] = array();
	
	
	$computer=new computers();
	$tpl=new templates();
	$unknown=$tpl->javascript_parse_text("{unknown}");
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$ipaddr=$ligne["ipaddr"];
		$proxy_alias=$ligne["uid"];
			
		$ipaddrenc=urlencode($ipaddr);
			
		$group=utf8_encode($ligne["group"]);
		if($group==null){$group=$unknown;}
		$proxy_alias="
		<a href=\"javascript:blur();\"
		style=\"text-decoration:underline\"
		OnClick=\"Loadjs('squid.nodes.php?node-infos-js=yes&ipaddr=$ipaddrenc');\"
		>$proxy_alias</a>";
		
			
		$delete=imgsimple("delete-42.png",null,"Loadjs('$MyPage?delete-ipaddr-js=$ipaddrenc')");
		$ipaddr_enc=urlencode($ipaddr);
		$data['rows'][] = array(
				'id' => md5(serialize($ligne)),
				'cell' => array("<span style='font-size:26px'>$proxy_alias</span>",
						"<span style='font-size:26px'>$ipaddr</a></span>",
						"<a href=\"javascript:blur()\"
						OnClick=\"Loadjs('$MyPage?group-js=yes&table=webfilters_ipaddr&IP=$ipaddr_enc');\"
						style='text-decoration:underline;font-size:26px'>$group</a>",
						"<center style='font-size:26px'>$delete</center>",
	
				)
		);
	}
	
	
	echo json_encode($data);	
	
}
