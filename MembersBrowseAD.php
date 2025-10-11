<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.samba.inc');


	
	
	$user=new usersMenus();
	if($user->AsSambaAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["groups-list"])){group_list();exit;}
	
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	if(!isset($_GET["OnlyUsers"])){$_GET["OnlyUsers"]=0;}
	if(!isset($_GET["OnlyGroups"])){$_GET["OnlyGroups"]=0;}
	if(!isset($_GET["OnlyGUID"])){$_GET["OnlyGUID"]=0;}
	if(!isset($_GET["NOComputers"])){$_GET["NOComputers"]=0;}
	if(!isset($_GET["Zarafa"])){$_GET["Zarafa"]=0;}
	if(!isset($_GET["OnlyAD"])){$_GET["OnlyAD"]=0;}
	$title=$tpl->_ENGINE_parse_body("{browse}::{groups}::ActiveDirectory");
	echo "LoadWinORG('543','$page?popup=yes&field-user={$_GET["field-user"]}&NOComputers={$_GET["NOComputers"]}&prepend={$_GET["prepend"]}&prepend-guid={$_GET["prepend-guid"]}&OnlyUsers={$_GET["OnlyUsers"]}&organization={$_GET["organization"]}&OnlyGroups={$_GET["OnlyGroups"]}&OnlyGUID={$_GET["OnlyGUID"]}&callback={$_GET["callback"]}&Zarafa={$_GET["Zarafa"]}&OnlyAD={$_GET["OnlyAD"]}','$title');";	
	
	
	
}



function popup(){
	$page=CurrentPageName();
	$tpl=new templates();		
	if($_GET["prepend"]==null){$_GET["prepend"]=0;}
	if($_GET["prepend-guid"]==null){$_GET["prepend-guid"]=0;}
	$OnlyGUID=$_GET["OnlyGUID"];
	$OnlyAD=$_GET["OnlyAD"];
	if(!is_numeric($OnlyGUID)){$OnlyGUID=0;}
	if(!is_numeric($OnlyAD)){$OnlyAD=0;}
	if($_GET["callback"]<>null){$callback="{$_GET["callback"]}(id,prependText,guid);WinORGHide();return;";}
	$GroupName=$tpl->_ENGINE_parse_body("{groupname}");
	$Members=$tpl->_ENGINE_parse_body("{members}");
	$html="
		<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?groups-list=yes&t=$t&field-user={$_GET["field-user"]}&NOComputers={$_GET["NOComputers"]}&prepend={$_GET["prepend"]}&prepend-guid={$_GET["prepend-guid"]}&OnlyUsers={$_GET["OnlyUsers"]}&organization={$_GET["organization"]}&OnlyGroups={$_GET["OnlyGroups"]}&OnlyGUID={$_GET["OnlyGUID"]}&callback={$_GET["callback"]}&Zarafa={$_GET["Zarafa"]}&OnlyAD={$_GET["OnlyAD"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'select', width : 42, sortable : false, align: 'center'},
		{display: '$GroupName', name : 'groupname', width : 372, sortable : true, align: 'left'},
		{display: '$Members', name : 'none', width : 60, sortable : false, align: 'center'},
		
		
	],

	searchitems : [
		{display: '$GroupName', name : 'groupname'},
		],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 530,
	height: 250,
	singleSelect: true
	
	});   
});
	function BrowseFindUserGroupClick(e){
		if(checkEnter(e)){BrowseFindUserGroup();}
	}
	
	var x_BrowseFindUserGroup=function (obj) {
		tempvalue=obj.responseText;
		document.getElementById('finduserandgroupsidBrwse').innerHTML=tempvalue;
	}


	function BrowseFindUserGroup(){
		LoadAjax('finduserandgroupsidBrwse','$page?query='+escape(document.getElementById('BrowseUserQuery').value)+'&prepend={$_GET["prepend"]}&field-user={$_GET["field-user"]}&prepend-guid={$_GET["prepend-guid"]}&OnlyUsers={$_GET["OnlyUsers"]}&OnlyGUID={$_GET["OnlyGUID"]}&organization={$_GET["organization"]}&OnlyGroups={$_GET["OnlyGroups"]}&callback={$_GET["callback"]}&NOComputers={$_GET["NOComputers"]}&Zarafa={$_GET["Zarafa"]}&OnlyAD=$OnlyAD');
	
	}	
	
	
	function BrowseSelect$t(id,prependText,guid){
			$callback
			var prepend={$_GET["prepend"]};
			var prepend_gid={$_GET["prepend-guid"]};
			var OnlyGUID=$OnlyGUID;
			if(document.getElementById('{$_GET["field-user"]}')){
				var selected=id;
				if(OnlyGUID==1){
					document.getElementById('{$_GET["field-user"]}').value=guid;
					WinORGHide();
					return;
				}
				
				if(prepend==1){selected=prependText+id;}
				if(prepend_gid==1){
					if(guid>1){
						selected=prependText+id+':'+guid;
					}
				}
				document.getElementById('{$_GET["field-user"]}').value=selected;
				WinORGHide();
			}
		}


	
</script>	
	
	";
	
echo $html;
}

function group_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$search='%';
	$table="adgroups";
	$database="artica_backup";
	$page=1;
	$t=$_GET["t"];
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
	if(!$q->ok){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($q->mysql_error,"", "",""));json_encode($data);return;}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysqli_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));json_encode($data);return;}
	
	
	
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$val=0;
		//$disable=Field_checkbox("groupid_{$ligne['gpid']}", 1,$ligne["enabled"],"EnableDisableGroup('{$ligne['ID']}')");
		$ligne['groupname']=utf8_encode($ligne['groupname']);
		$jsSelect="BrowseSelect$t('','','{$ligne["gpid"]}')";
		
		$ligne2=mysqli_fetch_array($q->QUERY_SQL("SELECT COUNT(gpid) as tcount FROM adusers WHERE gpid='{$ligne['gpid']}'",$database));
	$data['rows'][] = array(
		'id' => "group{$ligne['gpid']}",
		'cell' => array(
		"<a href=\"javascript:blur();\" OnClick=\"javascript:$jsSelect\"><img src='img/arrow-right-24.png' style='border:0px'></a>",
		"<a href=\"javascript:blur();\" 
		OnClick=\"javascript:$jsSelect\" 
		style='font-size:16px;text-decoration:underline'>{$ligne['groupname']}</span>",
		"<span style='font-size:16px;'>{$ligne2['tcount']}</span>",
		)
		);
	}
	
	
	echo json_encode($data);	
}

