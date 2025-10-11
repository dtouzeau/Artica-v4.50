<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.cron.inc');
	include_once('ressources/class.backup.inc');

$user=new usersMenus();
	if($user->AsSystemAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["popup-list"])){popup_list();exit;}
	if(isset($_POST["freeweb"])){popup_save();exit;}
js();


function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sources=$tpl->_ENGINE_parse_body("{sources}&nbsp;&raquo;{websites}");	
	$ID=$_GET["taskid"];
	$task=$tpl->_ENGINE_parse_body("{task}");
	$html="YahooWin4('550','$page?popup=yes&taskid=$ID','$task $ID&raquo;$sources');";
	echo $html;
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$source=$tpl->_ENGINE_parse_body("{source}");
	$advanced_options=$tpl->_ENGINE_parse_body("{advanced_options}");
	$mysql_instance=$tpl->_ENGINE_parse_body("{mysql_instance}");
	$select=$tpl->_ENGINE_parse_body("{select}");
	$ID=$_GET["taskid"];
	$websites=$tpl->_ENGINE_parse_body("{websites}");
	$sitename=$tpl->_ENGINE_parse_body("{sitename}");	
	$html="
		
		$mysqlerror
	<table class='backup-sources-www-table-list' style='display: none' id='backup-sources-www-table-list'' style='width:99%'></table>
	
<script>
$(document).ready(function(){
$('#backup-sources-www-table-list').flexigrid({
	url: '$page?popup-list=yes&taskid=$ID',
	dataType: 'json',
	colModel : [
		{display: '$sitename', name : 'servername', width : 460, sortable : true, align: 'left'},
		{display: '$select', name : 'delete', width : 31, sortable : false, align: 'center'},
	],
	searchitems : [
		{display: '$sitename', name : 'servername'},
		],	

	sortname: 'servername',
	sortorder: 'asc',
	usepager: true,
	title: '$websites',
	useRp: true,
	rp: 15,
	showTableToggleBtn: true,
	width: 535,
	height: 300,
	singleSelect: true
	
	});   
});

	var x_BackWWWWChoose= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);return;};
			$('#backup-sources-table-list').flexReload();
			$('#table-backup-tasks').flexReload();
			
		 }	

function BackWWWWChoose(sitename){
		var XHR = new XHRConnection();
		XHR.appendData('taskid',$ID);
		XHR.appendData('freeweb',sitename);
		XHR.sendAndLoad('$page', 'POST',x_BackWWWWChoose);

}


</script>
	
	
	";
	echo $html;
}

function popup_save(){
	$sql="SELECT datasbackup FROM backup_schedules WHERE ID='{$_POST["taskid"]}'";
	$q=new mysql();
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$ressources=unserialize(base64_decode($ligne["datasbackup"]));	
	$ressources[]="FREEWEB:{$_POST["freeweb"]}";
	$new_ressources=base64_encode(serialize($ressources));
	$sql="UPDATE backup_schedules SET datasbackup='$new_ressources' WHERE ID='{$_POST["taskid"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}

function popup_list(){
	include_once(dirname(__FILE__).'/ressources/class.apache.inc');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
		$vhosts=new vhosts();
	
	$search='%';
	$table="freeweb";
	$page=1;
	$FORCE_FILTER="";
	
	if($q->COUNT_ROWS($table,'artica_backup')==0){
		writelogs("$table, no row",__FILE__,__FUNCTION__,__FILE__,__LINE__);
		$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();
		echo json_encode($data);
		return ;
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,'artica_backup');
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	
	
	//if(mysqli_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	$sock=new sockets();
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$id=$ligne["ID"];
		$groupware=null;
		if($ligne["groupware"]<>null){$groupware=div_groupware("({{$vhosts->TEXT_ARRAY[$ligne["groupware"]]["TITLE"]}})");}
		$choose=imgsimple("arrow-right-24.png",null,"BackWWWWChoose('{$ligne["servername"]}')");
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array("<psan style='font-size:16px;'>{$ligne["servername"]}$groupware</span>",

		$choose )
		);
	}
	
	
echo json_encode($data);		


}
function div_groupware($text){
	if(!isset($GLOBALS["CLASS_TPL"])){$GLOBALS["CLASS_TPL"]=new templates();}
	return $GLOBALS["CLASS_TPL"]->_ENGINE_parse_body("<div style=\"font-size:11px;font-weight:bold;font-style:italic;color:#B64B13;margin:0px;padding:0px\">$text</div>");
}	
	
	
