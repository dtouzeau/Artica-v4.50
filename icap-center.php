<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.groups.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.ActiveDirectory.inc');
include_once('ressources/class.external.ldap.inc');

$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die("DIE " .__FILE__." Line: ".__LINE__);
}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["icap-search"])){search();exit;}
if(isset($_GET["service-js"])){service_js();exit;}

if(isset($_GET["service-delete-js"])){service_delete_js();exit;}
if(isset($_POST["delete-item"])){service_delete();exit;}

if(isset($_GET["service-popup"])){service_popup();exit;}
if(isset($_POST["service_name"])){service_save();exit;}

if(isset($_GET["reset-js"])){reset_js();exit;}
if(isset($_POST["reset"])){reset_perform();exit;}

if(isset($_GET["move-item-js"])){move_items_js();exit;}
if(isset($_POST["move-item"])){move_items();exit;}

tabs();

function service_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_service}");
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	if($ID>0){
		$q=new mysql_squid_builder();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT service_name FROM c_icap_services WHERE ID=$ID"));
		$title=$ligne["service_name"];
	}
	
	
	$YahooWin="YahooWin";
	echo "$YahooWin('850','$page?service-popup=yes&t=$t&ID=$ID','$title');";
	
}
function move_items_js(){
	$page=CurrentPageName();
	$tpl=new templates();

	$t=time();
	header("content-type: application/x-javascript");
	$html="

var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
	$('#flexRT{$_GET["t"]}').flexReload();
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('move-item','{$_GET["ID"]}');
	XHR.appendData('t','{$_GET["t"]}');
	XHR.appendData('dir','{$_GET["dir"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

Save$t();
";

	echo $html;

}

function service_delete_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT service_name FROM c_icap_services WHERE ID='$ID'"));
	$t=time();
	header("content-type: application/x-javascript");
	echo "
	
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
	$('#flexRT{$_GET["t"]}').flexReload();
}
function Save$t(){
	if(!confirm('".$tpl->javascript_parse_text("{delete} {$ligne["service_name"]}")."?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-item','{$_GET["ID"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	
Save$t();
";
	
}

function service_delete(){
	$q=new mysql_squid_builder();
	$ID=$_POST["delete-item"];
	if(!is_numeric($ID)){echo "Not an ID\n";return;}
	$q->QUERY_SQL("DELETE FROM c_icap_services WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;}
}

function move_items(){
	$q=new mysql_squid_builder();
	$ID=$_POST["move-item"];
	$t=$_POST["t"];
	$dir=$_POST["dir"];
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT zOrder FROM c_icap_services WHERE ID='$ID'"));
	if(!$q->ok){echo $q->mysql_error;}

	$cpu=$ligne["cpu"];
	$CurrentOrder=$ligne["zOrder"];

	if($dir==0){
		$NextOrder=$CurrentOrder-1;
	}else{
		$NextOrder=$CurrentOrder+1;
	}

	$sql="UPDATE c_icap_services SET zOrder=$CurrentOrder WHERE zOrder='$NextOrder'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}


	$sql="UPDATE c_icap_services SET zOrder=$NextOrder WHERE ID='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}

	$results=$q->QUERY_SQL("SELECT ID FROM c_icap_services ORDER by zOrder");
	if(!$q->ok){echo $q->mysql_error;}
	$c=1;
	while ($ligne = mysqli_fetch_assoc($results)) {
		$ID=$ligne["ID"];

		$sql="UPDATE c_icap_services SET zOrder=$c WHERE ID='$ID'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
		$c++;
	}

	$sock=new sockets();
	$sock->getFrameWork("squid.php?icap-clients=yes");
}

function reset_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();	
	$reset_ask=$tpl->javascript_parse_text("{reset_ask}");
	$t=time();
echo "
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>5){alert(results);return;}
	$('#flexRT{$_GET["t"]}').flexReload();
}
function Save$t(){
	if(!confirm('$reset_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('reset','yes');
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}

Save$t();";
	
	
}

function reset_perform(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DROP TABLE c_icap_services");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}


function tabs(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$fontsize=22;
	
	$array["table"]="{icap_center}";
	$array["exclude"]='AV {exclude}:Mime';
	$array["exclude-www"]='AV {exclude}:{websites}';
	

	$t=time();
	foreach ($array as $num=>$ligne){
		
		if($num=="exclude"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.hosts.blks.php?popup=yes&blk=6\" style='font-size:{$fontsize}'>
					<span>$ligne</span></a></li>\n");
					continue;
		}
		
		if($num=="exclude-www"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"c-icap.wwwex.php\" style='font-size:{$fontsize}'>
							<span style='font-size:{$fontsize}'>$ligne</span></a></li>\n");
							continue;
		}
	
		if($num=="rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"dansguardian2.mainrules.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
	
		}

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
	}
	
	
	
	$html=build_artica_tabs($html,'main_icap_center_tabs')."<script>LeftDesign('icap-center-256-opac20.png');</script>";
	
	echo $html;	
	
	
}

function service_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$btname="{add}";
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$BlockAddr=0;
	$BlockParams=0;
	if($ID>0){
		$btname="{apply}";
		$q=new mysql_squid_builder();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM c_icap_services WHERE ID=$ID"));
	}
	
	if($ligne["service_name"]==null){$ligne["service_name"]=$ligne["service_name"]=$tpl->javascript_parse_text("{new_service}");}
	
	if(!is_numeric($ligne["routing"])){$ligne["routing"]=1;}
	if(!is_numeric($ligne["bypass"])){$ligne["bypass"]=1;}
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	if(!is_numeric($ligne["maxconn"])){$ligne["maxconn"]=100;}
	if(!is_numeric($ligne["zOrder"])){$ligne["zOrder"]=1;}
	
	if($ligne["overload"]==null){$ligne["overload"]="bypass";}
	
	$respmod["reqmod_precache"]="REQMOD";
	$respmod["respmod_precache"]="RESPMOD";
	
	$overload["block"]="{block}";
	$overload["bypass"]="{bypass}";
	$overload["wait"]="{wait}";
	$overload["force"]="{force}";
	
	if($ID==1){$BlockAddr=1;$BlockParams=1;}
	if($ID==2){$BlockAddr=1;$BlockParams=1;}
	if($ID==3){$BlockParams=1;}
	if($ID==4){$BlockParams=1;}
	if($ID==5){$BlockAddr=1;$BlockParams=1;}
	if($ID==6){$BlockAddr=1;$BlockParams=1;}	
	if($ID==7){$BlockParams=1;}
	if($ID==8){$BlockParams=1;}
	if($ID==9){$BlockParams=1;}
	if($ID==10){$BlockParams=1;}
	if($ID==11){$BlockParams=1;}
	
	
	
	
	$html="
		<div style='font-size:30px;margin-bottom:20px'>{$ligne["service_name"]}</div>
		<div style='width:98%' class=form>
		<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:22px'>{service_name}:</td>
			<td>". Field_text("service_name-$t",$ligne["service_name"],"font-size:22px;width:500px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{enabled}:</td>
			<td>". Field_checkbox_design("enabled-$t",1,$ligne["enabled"])."</td>
		</tr>					
					
		<tr>
			<td class=legend style='font-size:22px'>{order}:</td>
			<td>". Field_text("zOrder-$t",$ligne["zOrder"],"font-size:22px;width:110px")."</td>
		</tr>					
		<tr>
			<td class=legend style='font-size:22px'>{address}:</td>
			<td>". Field_text("address-$t",$ligne["ipaddr"],"font-size:22px;width:95%")."</td>
		</tr>					
		<tr>
			<td class=legend style='font-size:22px'>{listen_port}:</td>
			<td>". Field_text("listenport-$t",$ligne["listenport"],"font-size:22px;width:120px")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:22px'>{icap_service_name}:</td>
			<td>". Field_text("icap_server-$t",$ligne["icap_server"],"font-size:22px;width:80%")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{type}:</td>
			<td>". Field_array_Hash($respmod,"respmod-$t",$ligne["respmod"],"style:font-size:22px")."</td>
		</tr>											
		<tr>
			<td class=legend style='font-size:22px'>{if_overloaded}:</td>
			<td>". Field_array_Hash($overload,"overload-$t",$ligne["overload"],"style:font-size:22px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>X-Next-Services:</td>
			<td>". Field_checkbox("routing-$t",1,$ligne["routing"])."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{bypass}:</td>
			<td>". Field_checkbox_design("bypass-$t",1,$ligne["bypass"])."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{max_connections}:</td>
			<td>". Field_text("maxconn-$t",$ligne["maxconn"],"font-size:22px;width:110px")."</td>
		</tr>										
		<tr>
			<td colspan=2 align='right'><hr>". button($btname,"Save$t()",32)."</td>
		</tr>
		</table>
		</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	var ID='$ID';
	if(results.length>5){alert(results);return;}
	if(ID==0){YahooWinHide();}
	$('#flexRT{$_GET["t"]}').flexReload();
}

function BlockAddr$t(){
	var block='$BlockAddr';
	var blockParams='$BlockParams';
	if(block==1){
		document.getElementById('address-$t').disabled=true;
		document.getElementById('listenport-$t').disabled=true;
	}
	if(blockParams==1){
		document.getElementById('icap_server-$t').disabled=true;
		document.getElementById('respmod-$t').disabled=true;
	}
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ID','$ID');
	XHR.appendData('service_name',encodeURIComponent(document.getElementById('service_name-$t').value));
	XHR.appendData('zOrder',document.getElementById('zOrder-$t').value);
	XHR.appendData('address',document.getElementById('address-$t').value);
	XHR.appendData('listenport',document.getElementById('listenport-$t').value);
	XHR.appendData('icap_server',document.getElementById('icap_server-$t').value);
	XHR.appendData('respmod',document.getElementById('respmod-$t').value);
	XHR.appendData('overload',document.getElementById('overload-$t').value);
	XHR.appendData('maxconn',document.getElementById('maxconn-$t').value);
	XHR.appendData('service_key','{$ligne["service_key"]}');
	
	
	
	if(document.getElementById('bypass-$t').checked){
		XHR.appendData('bypass',1);
	}else{
		XHR.appendData('bypass',0);
	}
	
	if(document.getElementById('routing-$t').checked){
		XHR.appendData('routing',1);
	}else{
		XHR.appendData('routing',0);
	}	
	if(document.getElementById('enabled-$t').checked){
		XHR.appendData('enabled',1);
	}else{
		XHR.appendData('enabled',0);
	}		
	
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}

 BlockAddr$t();
</script>
		
	";
	
echo $tpl->_ENGINE_parse_body($html);
	
}

function service_save(){
	include_once(dirname(__FILE__)."/class.html.tools.inc");
	$html=new htmltools_inc();
	$ID=$_POST["ID"];
	$service_name=url_decode_special_tool($_POST["service_name"]);
	if($_POST["service_key"]==null){
		$_POST["service_key"]=md5("{$_POST["address"]}{$_POST["listenport"]}{$_POST["respmod"]}{$_POST["icap_server"]}");
	}
		

	if($ID==0){
		$sql="INSERT INTO c_icap_services (service_name,service_key,respmod,routing,bypass,enabled,zOrder,ipaddr,listenport,icap_server,maxconn,overload)
			VALUES('$service_name','{$_POST["service_key"]}','{$_POST["respmod"]}',
			{$_POST["routing"]},{$_POST["bypass"]},{$_POST["enabled"]},
			{$_POST["zOrder"]},'{$_POST["address"]}','{$_POST["listenport"]}','{$_POST["icap_server"]}','{$_POST["maxconn"]}','{$_POST["overload"]}')";
	}
	

	if($ID>0){
		$sql="UPDATE c_icap_services SET
		`service_name`='$service_name',
		`service_key`='{$_POST["service_key"]}',
		`listenport`='{$_POST["listenport"]}',
		`icap_server`='{$_POST["icap_server"]}',
		`respmod`='{$_POST["respmod"]}',
		`routing`='{$_POST["routing"]}',
		`bypass`='{$_POST["bypass"]}',
		`enabled`='{$_POST["enabled"]}',
		`zOrder`='{$_POST["zOrder"]}',
		`maxconn`='{$_POST["maxconn"]}',
		`ipaddr`='{$_POST["address"]}',
		`overload`='{$_POST["overload"]}' WHERE ID=$ID";
	}	
	
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?icap-clients=yes");
	
}


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$explain_section=$tpl->_ENGINE_parse_body("{icap_center_explain}");
	$t=time();
	$service_name=$tpl->_ENGINE_parse_body("{service_name}");
	$respmod=$tpl->_ENGINE_parse_body("{mode}");
	$bypass=$tpl->_ENGINE_parse_body("{bypass}");
	$new_service=$tpl->javascript_parse_text("{new_service}");
	$address=$tpl->javascript_parse_text("{address}");
	$order=$tpl->javascript_parse_text("{order}");
	$title=$tpl->javascript_parse_text("{icap_center}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$reset=$tpl->javascript_parse_text("{reset}");
	$status=$tpl->javascript_parse_text("{status}");
	$OnlyActive=$tpl->javascript_parse_text("{OnlyActive}");
	$All=$tpl->javascript_parse_text("{all}");
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_service</strong>', bclass: 'add', onpress : Add$t},
	{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'apply', onpress : Apply$t},
	{name: '<strong style=font-size:18px>$reset</strong>', bclass: 'reload', onpress : reset$t},
	{name: '<strong style=font-size:18px>$OnlyActive</strong>', bclass: 'Search', onpress : OnlyActive$t},
	{name: '<strong style=font-size:18px>$All</strong>', bclass: 'Search', onpress : OnlyAll$t},
	
	
	
	],";
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	
	<script>
	var rowid=0;
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?icap-search=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:22px>$order</span>', name : 'zOrder', width : 105, sortable : true, align: 'center'},
	{display: '<span style=font-size:22px>$service_name</span>', name : 'service_name', width : 528, sortable : true, align: 'left'},
	{display: '<span style=font-size:22px>$address</span>', name : 'ipaddr', width : 163, sortable : true, align: 'left'},
	{display: '<span style=font-size:22px>$respmod</span>', name : 'respmod', width :191, sortable : false, align: 'left'},
	{display: '<span style=font-size:22px>$bypass</span>', name : 'bypass', width : 93, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'up', width : 65, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'down', width : 65, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'del', width : 65, sortable : false, align: 'center'},
	{display: '<span style=font-size:22px>$status</span>', name : 'status', width : 65, sortable : true, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$service_name', name : 'service_name'},
	{display: '$address', name : 'ipaddr'},
	],
	sortname: 'zOrder',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:30px>$title</strong>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});

	
function Add$t(){
	Loadjs('$page?service-js=yes&ID=0&t=$t');
}
function OnlyActive$t(){
	$('#flexRT$t').flexOptions({url: '$page?icap-search=yes&t=$t&OnlyActive=1'}).flexReload(); ExecuteByClassName('SearchFunction'); 
}
function OnlyAll$t(){
	$('#flexRT$t').flexOptions({url: '$page?icap-search=yes&t=$t&OnlyActive=0'}).flexReload(); ExecuteByClassName('SearchFunction');
}

function Apply$t(){
	Loadjs('squid.compile.progress.php?ask=yes&OnlySquid=yes&restart=yes');
}	

function reset$t(){
	Loadjs('$page?reset-js=yes&t=$t');
}

var x_DansGuardianDelGroup= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#row'+rowid).remove();
}
	
function DansGuardianDelGroup(ID){
	if(confirm('$do_you_want_to_delete_this_group ?')){
	rowid=ID;
	var XHR = new XHRConnection();
	XHR.appendData('Delete-Group', ID);
	XHR.sendAndLoad('$page', 'POST',x_DansGuardianDelGroup);
	}
}
	
</script>
	";
	
	echo $html;
	
}

function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	

	
	
	$t=$_GET["t"];
	$search='%';
	$table="c_icap_services";
	$page=1;
	$FORCE_FILTER=1;
	$total=0;
	
	if(isset($_GET["OnlyActive"])){
		if($_GET["OnlyActive"]==1){
			$FORCE_FILTER="`enabled`=1";
		}
	}
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	
	
	if($searchstring<>null){
		$search=$_POST["query"];
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	if(!is_numeric($rp)){$rp=50;}
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE $FORCE_FILTER $searchstring  $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);
	writelogs($sql." ==> ". mysqli_num_rows($results)." items",__FUNCTION__,__FILE__,__LINE__);
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total+1;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	
	
	$fontsize=22;
	while ($ligne = mysqli_fetch_assoc($results)) {
		$CountDeMembers=0;
		$color="black";
		if($ligne["enabled"]==0){$color="#8a8a8a";}
		$style="style='font-size:{$fontsize}px;color:$color;'";
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?service-delete-js=yes&ID={$ligne["ID"]}&t=$t');");
		$bypass="&nbsp;";
		$js="<a href=\"javascript:blur();\" OnClick=\"Loadjs('$MyPage?service-js=yes&ID={$ligne["ID"]}&t=$t');\"
		style='font-size:{$fontsize}px;color:$color;text-decoration:underline'>";
		
		if($ligne["ID"]<20){$delete="&nbsp;";}
		
		$up=imgsimple("arrow-up-32.png",null,"Loadjs('$MyPage?move-item-js=yes&ID={$ligne["ID"]}&dir=0&t={$_GET["t"]}')");
		$down=imgsimple("arrow-down-32.png",null,"Loadjs('$MyPage?move-item-js=yes&ID={$ligne["ID"]}&dir=1&t={$_GET["t"]}')");

		
		if(intval($ligne["bypass"])>0){$ligne["bypass"]=1;}
		
		if($ligne["bypass"]==1){
			$bypass="<img src=img/32-green.png>";
			
		}
		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span $style>$js{$ligne["zOrder"]}</a></span>",
						"<span $style>$js{$ligne["service_name"]}</a></span>",
						"<span $style>{$js}{$ligne["ipaddr"]}:{$ligne["listenport"]}</span>",
						"<span $style>$js{$ligne["respmod"]}</span>",
						"<center $style>$js$bypass</center>",
						"<center $style>$up</center>",
						"<center $style>$down</center>",
						"<center $style>$delete</center>",
						"<center $style>". imgsimple($STATUS_ARRAY[$ligne["status"]])."</center>",
						
				)
		);
	}
	
	
		echo json_encode($data);
	
}


