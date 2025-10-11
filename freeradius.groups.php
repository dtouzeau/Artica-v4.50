<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.freeradius.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}
	// freeradius_db
	
	
	if(isset($_GET["group-id-js"])){group_id_js();exit;}
	if(isset($_GET["group-form-id"])){group_id();exit;}
	if(isset($_GET["connection-form"])){connection_form();exit;}
	if(isset($_POST["groupname"])){group_save();exit;}
	if(isset($_GET["query"])){connection_list();exit;}
	if(isset($_POST["EnableLocalLDAPServer"])){EnableLocalLDAPServer();exit;}
	if(isset($_POST["group-delete"])){group_delete();exit;}
	if(isset($_POST["EnableDisable"])){connection_enable();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	js();
	
	
function js(){
		$tpl=new templates();
		$page=CurrentPageName();
		header("content-type: application/x-javascript");
	
		$title=$tpl->javascript_parse_text("{groups2}");
		$t=$_GET["t"];
		echo "YahooWin3('700','$page?popup=yes','$title')";
	
	}	
	
	
function group_id_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$id=urlencode($_GET["group-id-js"]);
	$title=$tpl->javascript_parse_text("{new_group}");
	$t=$_GET["t"];
	
	if($id>0){
		$q=new mysql();
		$ligne=mysqli_fetch_array(
				$q->QUERY_SQL("SELECT groupname FROM radgroupcheck WHERE id='$id'","artica_backup"));
				$title=utf8_decode($tpl->javascript_parse_text($ligne["username"]));
	}
	
	echo "YahooWin4('650','$page?group-form-id=$id&t=$t','$title')";
	
}


function group_id(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$btname="{add}";
	$q=new mysql();
	$id=$_GET["group-form-id"];
	if($id>0){
		$btname="{apply}";
		
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM radgroupcheck WHERE id='$id'","artica_backup"));
		if(!is_numeric($gpid)){$gpid=0;}		
	}
	
	$html="<div id='anim-$t'></div>
	<div style='width:98%' class=form>	
	<div style='font-size:18px;margin-bottom:20px'><i>administrators, ProxyAdms, ProxySecurity, ProxyMonitor, WebStatsAdm,VPNAdmins</i></div>
	
	
	<table style='width:99%'>
	<tr>
		<td class=legend style='font-size:26px'>{name}:</td>
		<td>". Field_text("groupname-$t",$ligne["groupname"],"font-size:26px;width:480px")."</td>		
	</tr>					
	<tr>
		<td colspan=2 align=right><hr>".button("$btname","Save$t()",42)."</td>
	</tr>	
	</table>	
	</div>
			
	
	<script>
	var x_Save$t= function (obj) {
	var connection_id='$id';
	var results=obj.responseText;
	if(results.length>3){alert(results);document.getElementById('$t').innerHTML='';return;}
	document.getElementById('$t').innerHTML='';
	if(connection_id.length==0){YahooWin4Hide();}
	$('#$t').flexReload();
}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('groupname', encodeURIComponent(document.getElementById('groupname-$t').value));
	XHR.appendData('id', '$id');
	AnimateDiv('anim-$t');
	XHR.sendAndLoad('$page', 'POST',x_Save$t);
}	
 </script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function group_save(){
	$q=new mysql();
	$_POST["groupname"]=url_decode_special_tool($_POST["groupname"]);
	
	$free=new freeradius();
	$free->GroupSave($_POST["groupname"],$_POST["id"]);
	

	
}
	
function popup(){
	
		$page=CurrentPageName();
		$tpl=new templates();
		$q=new mysql();
		$sock=new sockets();
		$shortname=$tpl->javascript_parse_text("{groups2}");
		$nastype=$tpl->javascript_parse_text("{type}");
		$delete=$tpl->javascript_parse_text("{delete}");
		$connection=$tpl->javascript_parse_text("{connection}");
		$add=$tpl->javascript_parse_text("{new_group}");
		$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
		$title=$tpl->javascript_parse_text("{groups2}");
		$tablewidht=680;
		$t=time();
	
		$buttons="buttons : [
		{name: '<strong style=font-size:18px>$add</strong>', bclass: 'Add', onpress : AddConnection$t},
		],	";
	

	
echo "

<table class='$t' style='display: none' id='$t' style='width:99%;text-align:left'></table>
<script>
	var MEMM$t='';
	$(document).ready(function(){
		$('#$t').flexigrid({
			url: '$page?query=yes&t=$t',
			dataType: 'json',
			colModel : [
			{display: '&nbsp;', name : 'none2', width : 52, sortable : false, align: 'center'},
			{display: '<span style=font-size:18px>$shortname</span>', name : 'groupname', width : 510, sortable : false, align: 'left'},
			{display: '&nbsp;', name : 'none3', width : 52, sortable : false, align: 'center'},
		],
		$buttons
		searchitems : [
		{display: '$shortname', name : 'shortname'},
		
		],
		sortname: 'groupname',
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
	
	
	
	function RefreshTable$t(){
		$('#$t').flexReload();
	}
	
	function enable_ip_authentication_save$t(){
	var XHR = new XHRConnection();
	if(document.getElementById('LimitByIp').checked){XHR.appendData('LimitByIp',1);}else{XHR.appendData('LimitByIp',0);}
	XHR.appendData('servername','{$_GET["servername"]}');
			XHR.sendAndLoad('$page', 'POST',x_AuthIpAdd$t);
	}
	
	var x_Refresh$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		RefreshTable$t()
	}
	
	var x_ConnectionDelete$t=function (obj) {
		var results=obj.responseText;
		if(results.length>2){alert(results);return;}
		$('#row'+MEMM$t).remove();
	}
	
	function AddConnection$t(){
		Loadjs('$page?group-id-js=&t=$t');
	}
	
	function EnableLocalLDAPServer$t(){
		var XHR = new XHRConnection();
		XHR.appendData('EnableLocalLDAPServer','yes');
		XHR.sendAndLoad('$page', 'POST',x_Refresh$t);	
	}
	
	function EnableDisable$t(ID){
		var XHR = new XHRConnection();
		XHR.appendData('EnableDisable',ID);
		XHR.sendAndLoad('$page', 'POST',x_Refresh$t);	
	}
	
	function ConnectionDelete$t(id){
		MEMM$t=id;
		if(confirm('$delete '+id+' ?')){
			var XHR = new XHRConnection();
			XHR.appendData('group-delete',id);
			XHR.sendAndLoad('$page', 'POST',x_ConnectionDelete$t);
		}
	}
</script>
	";
}	

function connection_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$q=new mysql();
	$database="artica_backup";
	$t=$_GET["t"];
	$search='%';
	$table="radgroupcheck";
	$page=1;
	$data = array();
	$data['rows'] = array();
	$FORCE_FILTER=null;
		
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
	
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	if(mysqli_num_rows($results)==0){
		json_error_show("no data");
	}
	
	
	$data['page'] = $page;
	$data['total'] = $total;
	

	while ($ligne = mysqli_fetch_assoc($results)) {
		$val=0;
		$color="black";
		$delete=imgsimple("delete-48.png",null,"ConnectionDelete$t('{$ligne['id']}')");
		
		

		$data['rows'][] = array(
				'id' => $ligne['id'],
				'cell' => array("
						<img src='img/group-48.png'>",
						"<a href=\"javascript:blur();\" 
						OnClick=\"Loadjs('$MyPage?group-id-js={$ligne['id']}&t=$t');\" 
						style=\"font-size:22px;text-decoration:underline;color:$color\">
						{$ligne['groupname']}</a>",
						$delete
				)
		);
	}
	
	
	echo json_encode($data);	
	
}


function group_delete(){
	$q=new mysql();
	$ID=$_POST["group-delete"];
	$free=new freeradius();
	$free->GroupDelete($ID);
}