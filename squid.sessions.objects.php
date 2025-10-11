<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.groups.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.system.network.inc');

$user=new usersMenus();
if($user->AsSquidAdministrator==false){echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die("DIE " .__FILE__." Line: ".__LINE__);}

if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["object-js"])){object_js();exit;}
if(isset($_GET["object-popup"])){object_popup();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_POST["ID"])){Save();exit;}
if(isset($_POST["delete"])){Delete();exit;}
table();


function object_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	header("content-type: application/x-javascript");
	if($ID==0){
		$TITLE=$tpl->javascript_parse_text("{new_object}");
	}else{
		$q=new mysql_squid_builder();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT objectname FROM sessions_objects WHERE ID='$ID'"));
		$TITLE=utf8_encode($ligne["objectname"]);
	}

	echo "YahooWin2('750','$page?object-popup=yes&ID=$ID&t=$t','$TITLE',true);";

}
function delete_js(){
	$t=time();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$confirm=$tpl->javascript_parse_text("{session_object_delete_confirm}");
$html="
var xDel$t= function (obj) {
	var res=obj.responseText;
	if (res.length>0){alert(res);return;}
	$('#TABLE_SESSION_OBJECT').flexReload();
}

function Del$t(){
	if(!confirm('$confirm')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','{$_GET["ID"]}');
	XHR.sendAndLoad('$page', 'POST',xDel$t);
	
}

Del$t();";

echo $html;
}

function Delete(){
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilters_sqgroups WHERE GroupType='time_session:ACTIVE:{$_POST["delete"]}'");
	$q->QUERY_SQL("DELETE FROM webfilters_sqgroups WHERE GroupType='time_session:LOGIN:{$_POST["delete"]}'");
	$q->QUERY_SQL("DELETE FROM webfilters_sqgroups WHERE GroupType='time_session:LOGOUT:{$_POST["delete"]}'");
	if(!$q->ok){echo $q->mysql_error;}
	$q->QUERY_SQL("DELETE FROM sessions_objects WHERE ID='{$_POST["delete"]}'");
	if(!$q->ok){echo $q->mysql_error;}
	
}


function object_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$btname="{add}";
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	$ligne["ttl"]="3600";
	$ligne["sleep"]=300;
	if($ID>0){
		$btname="{apply}";
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM sessions_objects WHERE ID='$ID'"));

	}
	
	
	
	$indentifier["LOGIN"]="{username}";
	$indentifier["SRC"]="{ipaddr}";
	$indentifier["SRCEUI48"]="{MAC}";
	$indentifier["EXT_TAG"]="{statistics_virtual_group}";

	$html="
<div style='width:98%' class=form>
	<table style='width:99%'>
	<tr>
		<td class=legend style='font-size:22px'>{object_name}:</td>
		<td>". Field_text("objectname-$t",$ligne["objectname"],"font-size:22px;width:400px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{indentifier}:</td>
		<td>". Field_array_Hash($indentifier, "identifier-$t",$ligne["identifier"],"style:font-size:22px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{ttl} ({seconds})","{squid_ttl_session_explain}").":</td>
		<td>". Field_text("ttl-$t",$ligne["ttl"],"font-size:22px;width:220px")."</td>
	</tr>				
	<tr>
		<td colspan=2 align=right><hr>".button("$btname","Save$t()",30)."</td>
	</tr>
	</table>
</div>
<script>
var x_Save$t= function (obj) {
	var ID='$ID';
	var results=obj.responseText;
	if(results.length>3){alert(results);document.getElementById('$t').innerHTML='';return;}
	if(ID==0){YahooWin2Hide();}
	$('#TABLE_SESSION_OBJECT').flexReload();
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ttl', document.getElementById('ttl-$t').value);
	XHR.appendData('identifier', document.getElementById('identifier-$t').value);
	XHR.appendData('ID', '$ID');
	XHR.appendData('objectname', encodeURIComponent(document.getElementById('objectname-$t').value));
	XHR.sendAndLoad('$page', 'POST',x_Save$t);
}
</script>
";

	echo $tpl->_ENGINE_parse_body($html);
}

function Save(){
	$q=new mysql_squid_builder();
	$objectname=mysql_escape_string2(url_decode_special_tool($_POST["objectname"]));
	$ID=intval($_POST["ID"]);
	$identifier=$_POST["identifier"];
	$ttl=$_POST["ttl"];
	$sleep=intval($_POST["sleep"]);
	if($ID==0){
		
		
		$q->QUERY_SQL("INSERT IGNORE INTO `sessions_objects` (objectname,identifier,ttl,sleep) 
				VALUES ('$objectname','$identifier','$ttl','$sleep')");
		
	}else{
		
		$q->QUERY_SQL("UPDATE `sessions_objects` 
				SET objectname='$objectname',
				identifier='$identifier',sleep='$sleep',
				ttl='$ttl' WHERE ID=$ID");		
		
	}
	
	if(!$q->ok){echo $q->mysql_error;}
	
	
	
	
	
}

function table(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$shortname=$tpl->javascript_parse_text("{member}");
	$ttl=$tpl->javascript_parse_text("{ttl}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$identifier=$tpl->javascript_parse_text("{identifier}");
	$add=$tpl->javascript_parse_text("{new_member}");
	$object_name=$tpl->javascript_parse_text("{object_name}");
	$new_quota_object=$tpl->_ENGINE_parse_body("{new_object}");
	$title=$tpl->javascript_parse_text("{sessions_tracking_objects}");
	$apply_params=$tpl->_ENGINE_parse_body("{apply}");
	$pauselen=$tpl->_ENGINE_parse_body("{pauselen}");
	$tablewidht=883;
	$t=time();
	
	$q=new mysql_squid_builder();
	$sql="CREATE TABLE IF NOT EXISTS `sessions_objects` (
	 ID INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`objectname` varchar(90) NOT NULL,
	`ttl` smallint(10) NOT NULL,
	`sleep` smallint(10) NOT NULL,
	`identifier` varchar(50) NOT NULL DEFAULT 'SRC',
	KEY `objectname` (`objectname`),
	KEY `ttl` (`ttl`),
	KEY `identifier` (`identifier`)
	) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html();}
	
	$apply_paramsbt="{separator: true},{name: '<strong style=font-size:18px>$apply_params</strong>', bclass: 'apply', onpress : SquidBuildNow$t},";
	
$buttons="buttons : [
		{name: '<strong style=font-size:18px>$new_quota_object</strong>', bclass: 'Add', onpress : AddQuota$t},
],	";
echo "
	
<table class='$t' style='display: none' id='TABLE_SESSION_OBJECT' style='width:99%;text-align:left'></table>
<script>
$(document).ready(function(){
$('#TABLE_SESSION_OBJECT').flexigrid({
	url: '$page?search=yes&t=$t',
	dataType: 'json',
		colModel : [
		{display: '<span style=font-size:22px>$object_name</span>', name : 'objectname', width : 450, sortable : false, align: 'left'},
		{display: '<span style=font-size:22px>$identifier</span>', name : 'identifier', width : 250, sortable : false, align: 'left'},
		{display: '<span style=font-size:22px>$ttl</span>', name : 'ttl', width : 240, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'none3', width : 60, sortable : false, align: 'center'},
		],
		$buttons
		searchitems : [
		{display: '$object_name', name : 'objectname'},
	
	
		],
		sortname: 'objectname',
		sortorder: 'asc',
		usepager: true,
		title: '<span style=font-size:26px>$title</span>',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 450,
		singleSelect: true
	});
});
	
function SquidBuildNow$t(){
	Loadjs('squid.compile.php');
}
function AddQuota$t(){
	Loadjs('$page?object-js=yes&t=$t&ID=0');
}
	
</script>
	";
}

function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$search='%';
	$table="sessions_objects";
	$page=1;
	$data = array();
	$data['rows'] = array();
	$FORCE_FILTER=1;

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}

	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error",0);}
		$total = $ligne["TCOUNT"];
			
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}

	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM `$table` WHERE $FORCE_FILTER $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error,$sql",1);}



	$data['page'] = $page;
	$data['total'] = $total;

	if(mysqli_num_rows($results)==0){json_error_show("no data",1);}

	$indentifierZ["LOGIN"]="{username}";
	$indentifierZ["SRC"]="{ipaddr}";
	$indentifierZ["SRCEUI48"]="{MAC}";
	$indentifierZ["EXT_TAG"]="{statistics_virtual_group}";
	$seconds=$tpl->javascript_parse_text("{seconds}");
	while ($ligne = mysqli_fetch_assoc($results)) {
		$val=0;
		$color="black";
		$download="&nbsp;";
		$delete=imgsimple("delete-48.png",null,"Loadjs('$MyPage?delete-js=yes&ID={$ligne["ID"]}')");

		$href="<a href=\"javascript:blur();\"
		OnClick=\"Loadjs('$MyPage?object-js=yesID={$ligne["ID"]}');\"
		style=\"font-size:22px;text-decoration:underline;color:$color\">";
		
		$identifier=$tpl->javascript_parse_text($indentifierZ[$ligne["identifier"]]);
		$objectname=utf8_encode($ligne["objectname"]);

		$data['rows'][] = array(
				'id' => "ACC{$ligne['ID']}",
				'cell' => array(
						"$href{$objectname}</a>",
						"$href{$identifier}</a>",
						"$href{$ligne['ttl']} $seconds</a>",
						"<center>$delete</center>"
		)
		);
	}
	echo json_encode($data);
}