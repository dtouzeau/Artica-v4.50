<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',1);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');

	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	include_once('ressources/class.mysql-server.inc');
	include_once('ressources/class.mysql-multi.inc');
	
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsSystemAdministrator){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["list"])){items_list();exit;}
	if(isset($_GET["item-js"])){itemjs();exit;}
	if(isset($_GET["item"])){item_popup();exit;}
	if(isset($_GET["item-form"])){item_popup_form();exit;}
	if(isset($_POST["ID"])){item_save();exit;}
	if(isset($_POST["enable-item"])){item_enable();exit;}
	if(isset($_POST["delete-item"])){item_delete();exit;}
	if(isset($_POST["ReloadWebConsole"])){ReloadWebConsole();exit;}
	if(isset($_GET["phpmyadmin-options"])){popup_options();exit;}
	if(isset($_POST["phpmyadminAllowNoPassword"])){phpmyadminAllowNoPassword();exit;}
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{protect_phpmyadmin}");
	$html="YahooWin4('650','$page?popup=yes','$title')";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$pattern=$tpl->_ENGINE_parse_body("{pattern}");
	$description=$tpl->_ENGINE_parse_body("{description}");	
	$new_item=$tpl->_ENGINE_parse_body("{new_item}");	
	$type=$tpl->_ENGINE_parse_body("{xtype}");
	$restart_web_console=$tpl->_ENGINE_parse_body("{restart_web_console}");
	$options=$tpl->javascript_parse_text("{options}");
	$TB_WIDTH=631;
	$item=$tpl->_ENGINE_parse_body("{item}");
	$explain=$tpl->_ENGINE_parse_body("{protect_phpmyadmin_text}");
	//AllowNoPassword
	$t=time();
	$html="
	<div class=explain>$explain</div>
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var selectedMysqlid='';
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$pattern', name : 'pattern', width : 378, sortable : false, align: 'left'},
		{display: '$type', name : 'type', width : 112, sortable : true, align: 'left'},
		{display: '', name : 'none2', width : 30, sortable : false, align: 'left'},
		{display: '', name : 'none3', width : 50, sortable : false, align: 'left'},
		
	],
buttons : [
	{name: '$new_item', bclass: 'add', onpress : AddNewItem},
	{name: '$restart_web_console', bclass: 'Reload', onpress : ReloadWebConsole},
	{name: '$options', bclass: 'Settings', onpress : PhpLdapAdminOptions},
	
		],	
	searchitems : [
		{display: '$pattern', name : 'pattern'},
		],
	sortname: 'pattern',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: 250,
	singleSelect: true
	
	});   
});

function AddNewItem(ID){
	Loadjs('$page?item-js=yes&table=$t&ID='+ID);

}

function PhpLdapAdminOptions(){
	YahooWin5(550,'$page?phpmyadmin-options=yes','$options');
}

	var x_MysqlEnableItem= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		
	}	
	
	var x_DeletedMysqlItemDen=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		$('#row'+selectedMysqlid).remove();
	}	
	

function MysqlEnableItem(md5,id){
	var XHR = new XHRConnection();
	XHR.appendData('enable-item',id);
	if(document.getElementById(md5).checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
	XHR.sendAndLoad('$page', 'POST',x_MysqlEnableItem);
}

function ReloadWebConsole(){
	var XHR = new XHRConnection();
	XHR.appendData('ReloadWebConsole','yes');
	XHR.sendAndLoad('$page', 'POST',x_MysqlEnableItem);	
	}
function DeletedMysqlItemDen(ID,md){
	selectedMysqlid=md;
	var XHR = new XHRConnection();
	XHR.appendData('delete-item',ID);
	XHR.sendAndLoad('$page', 'POST',x_DeletedMysqlItemDen);	
}

	
</script>";
	
	echo $html;
}

function popup_options(){
	$sock=new sockets();
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$phpmyadminAllowNoPassword=$sock->GET_INFO("phpmyadminAllowNoPassword");
	if(!is_numeric($phpmyadminAllowNoPassword)){$phpmyadminAllowNoPassword=0;}
	
	$html="
	<div id='div$t'></div>
	<table style='width:100%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{AllowNoPassword}</td>
		<td>". Field_checkbox("phpmyadminAllowNoPassword", 1,$phpmyadminAllowNoPassword)."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>". button("{apply}","SavePat$t()",18)."</td>
	</tr>
	</table>
	<script>
	
	var x_SavePat$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		YahooWin5Hide();
	}		
	
	function SavePat$t(){
	
		var XHR = new XHRConnection();
		if(document.getElementById('phpmyadminAllowNoPassword').checked){
			XHR.appendData('phpmyadminAllowNoPassword',1);
		}else{
			XHR.appendData('phpmyadminAllowNoPassword',0);
		}
		AnimateDiv('div$t');
		XHR.sendAndLoad('$page', 'POST',x_SavePat$t);
	}

</script>	
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function phpmyadminAllowNoPassword(){
	$sock=new sockets();
	$sock->SET_INFO("phpmyadminAllowNoPassword", $_POST["phpmyadminAllowNoPassword"]);
	
}

function itemjs(){
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	if(!is_numeric($_GET["ID"])){$_GET["ID"]=0;}
	$item=$tpl->_ENGINE_parse_body("{item}");
$html="
function AddNewItem$t(){
	YahooWin6(550,'$page?item=yes&table={$_GET["table"]}&ID={$_GET["ID"]}&t=$t','$item:{$_GET["ID"]}');
}
AddNewItem$t();
";	

echo $html;
	
}

function item_popup(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$html="
	<div id='div$t'></div>
	<script>
		LoadAjax('div$t','$page?item-form=yes&table={$_GET["table"]}&ID={$_GET["ID"]}&t=$t');
	</script>
	
	
	";
	
	echo $html;
	
	
}


function item_popup_form(){
	$t=$_GET["t"];
	$table=$_GET["table"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();	
	if(!is_numeric($_GET["ID"])){$_GET["ID"]=0;}
	$button="{apply}";
	$sql="SELECT * FROM phpmyadminsecu WHERE ID={$_GET["ID"]}";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($_GET["ID"]==0){$button="{add}";}
	$typeA[0]="{ip_address}";
	//$typeA[1]="{hostname}";
	
	
	$html="
	<div class=explain>{protect_phpmyadmin_item_text}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{xtype}:</td>
		<td>". Field_array_Hash($typeA,"type-$t",$ligne["type"],"style:font-size:16px;")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{pattern}:</td>
		<td>". Field_text("pattern-$t",$ligne["pattern"],"font-size:16px;width:250px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>". button("$button","SavePat$t()",18)."</td>
	</tr>
	</table>
	<script>
	
	var x_SavePat$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		$('#table-$table').flexReload();
		YahooWin6Hide();
	}		
	
	function SavePat$t(){
		var XHR = new XHRConnection();
		var pp=encodeURIComponent(document.getElementById('pattern-$t').value);
		XHR.appendData('ID','{$_GET["ID"]}');
		XHR.appendData('type',document.getElementById('type-$t').value);
		XHR.appendData('pattern',pp);
		AnimateDiv('div$t');
		XHR.sendAndLoad('$page', 'POST',x_SavePat$t);
	}

</script>
	
	
	";	

	echo $tpl->_ENGINE_parse_body($html);
	
}

function item_enable(){
	$q=new mysql();
	$sql="UPDATE phpmyadminsecu SET `enabled`='{$_POST["enabled"]}' WHERE ID={$_POST["enable-item"]}";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}	
	
}
function item_delete(){
	$q=new mysql();
	$sql="DELETE FROM phpmyadminsecu  WHERE ID={$_POST["delete-item"]}";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}		
}

function item_save(){
	$_POST["pattern"]=url_decode_special_tool($_POST["pattern"]);
	$_POST["pattern"]=mysql_escape_string2($_POST["pattern"]);
	$q=new mysql();
	if($_POST["ID"]==0){
		$sql="INSERT INTO phpmyadminsecu (`pattern`,`type`,`enabled`)
		VALUES('{$_POST["pattern"]}','{$_POST["type"]}',1)";
		
	}else{
		$sql="UPDATE phpmyadminsecu SET `pattern`='{$_POST["pattern"]}', `type`='{$_POST["type"]}' WHERE ID={$_POST["ID"]}";
		
	}
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	
}

function items_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	if(!$q->TABLE_EXISTS("phpmyadminsecu", "artica_backup")){
		$q->BuildTables();
	}
	$t=$_GET["t"];
	$search='%';
	$table="phpmyadminsecu";
	$database="artica_backup";
	$page=1;
	$ORDER=null;
	$FORCE_FILTER=null;
	
	if($q->COUNT_ROWS($table,$database)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	
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
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$total =$q->COUNT_ROWS($table,$database);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE 1 $FORCE_FILTER $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysqli_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	$typeA[0]="{ip_address}";
	$typeA[1]="{hostname}";	
	
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$color="black";
		$md5=md5($ligne["ID"].$ligne["pattern"]);
		$js="<a href=\"javascript:blur();\" OnClick=\"Loadjs('$MyPage?item-js=yes&table=$t&ID={$ligne["ID"]}');\"
		style='font-size:16px;color:$color;text-decoration:underline'>";
		$delete=imgtootltip("delete-24.png","{delete}","DeletedMysqlItemDen('{$ligne["ID"]}','$md5')");
		$enable=Field_checkbox($md5, 1,$ligne["enabled"],"MysqlEnableItem('$md5','{$ligne["ID"]}')");
		
		
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
		"$js{$ligne["pattern"]}</a>",
		$tpl->_ENGINE_parse_body("<span style='font-size:14px;color:$color'>$js{$typeA[$ligne["type"]]}</a></span>"),
		"<div style='margin-top:5px'>$enable</div>",
		$delete)
		);
	}
	
	
echo json_encode($data);	
	
	
}
function ReloadWebConsole(){
	$sock=new sockets();
	$tpl=new templates();
	
	$sock->getFrameWork("services.php?restart-lighttpd=yes");
	echo $tpl->javascript_parse_text("{operation_launched_in_background}");
}