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
	$taskid=$_GET["ID"];
	$sources=$tpl->_ENGINE_parse_body("{events}&nbsp;&raquo;{task}:$taskid");	
	$html="YahooWin4('845','$page?popup=yes&taskid=$taskid','$sources');";
	echo $html;
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$source=$tpl->_ENGINE_parse_body("{source}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$resource=$tpl->_ENGINE_parse_body("{resource}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$ID=$_GET["taskid"];
	$delete_all_items=$tpl->javascript_parse_text("{delete_all_items}");
	
	$buttons="
	buttons : [
	{name: '<b>$delete_all_items</b>', bclass: 'Delz', onpress : DeleteAllBackTaskEvents},
	
		],";		
	
	$html="
	<table class='backup-sources-events-table-list' style='display: none' id='backup-sources-events-table-list' style='width:99%'></table>
	
<script>
$(document).ready(function(){
$('#backup-sources-events-table-list').flexigrid({
	url: '$page?popup-list=yes&taskid=$ID',
	dataType: 'json',
	colModel : [
		{display: '$status', name : 'status', width : 31, sortable : false, align: 'center'},
		{display: '$date', name : 'zdate', width : 119, sortable : true, align: 'left'},
		{display: '$resource', name : 'backup_source', width : 91, sortable : true, align: 'left'},
		{display: '$events', name : 'event', width : 511, sortable : false, align: 'left'},
	],
	searchitems : [
		{display: '$events', name : 'event'},
		],	
$buttons
	sortname: 'zdate',
	sortorder: 'desc',
	usepager: true,
	title: '$events',
	useRp: true,
	rp: 15,
	showTableToggleBtn: true,
	width: 820,
	height: 400,
	singleSelect: true
	
	});   
});
		function TASK_EVENTS_DETAILS_INFOS(ID){
			YahooWin5('700','backup.tasks.php?TASK_EVENTS_DETAILS_INFOS='+ID,ID+'::$events');
		}

	var x_DeleteAllBackTaskEvents= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		$('#backup-sources-events-table-list').flexReload();
	 }	
	
	function DeleteAllBackTaskEvents(){
		if(confirm('$delete_all_items ?')){
			var XHR = new XHRConnection();
			XHR.appendData('DeleteAllBackTaskEvents',$ID);
			XHR.sendAndLoad('backup.tasks.php', 'POST',x_DeleteAllBackTaskEvents);
		}
		
	}

</script>
	
	
	";
	echo $html;
}

function popup_list(){
	$ID=$_GET["taskid"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
		
	
	$search='%';
	$table="(SELECT *,DATE_FORMAT(zdate,'%W') as explainday,DATE_FORMAT(zdate,'%p') as tmorn,DATE_FORMAT(zdate,'%Hh -%i:%s') as ttime  FROM `backup_events` WHERE `task_id`='$ID') as t";
	$page=1;
	$FORCE_FILTER="";
	
	if($q->COUNT_ROWS("backup_events",'artica_events')==0){json_error_show("No data");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,'artica_events'));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,'artica_events');
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	
	//if(mysqli_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	$sock=new sockets();
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$id=md5(@implode(" ", $ligne));
		$img="info-18.png";
		$status=null;
		$ligne["event"]=$tpl->_ENGINE_parse_body($ligne["event"]);
		if(strlen($ligne["event"])>90){
			
			$ligne["event"]=wordwrap($ligne["event"], 90, "<br />\n");
		}
		
		if(preg_match("#^([A-Z]+).*?,#",$ligne["event"],$re)){
			$status=$re[1];
			$ligne["event"]=str_replace($re[1].',','',$ligne["event"]);
		}
		$ligne["explainday"]=strtolower($ligne["explainday"]);
		$date=$tpl->_ENGINE_parse_body("{{$ligne["explainday"]}} {$ligne["ttime"]}");
		
		
		switch ($status) {
			case "INFO":$img="info-18.png";break;
			case "ERROR":$img="status_warning.png";break;
			default:
				;
			break;
		}
		
			$display="TASK_EVENTS_DETAILS_INFOS({$ligne["ID"]})";
			$disblayUri="<a href=\"javascript:blur();\" OnClick=\"javascript:$display;\" style='font-size:12px;text-decoration:underline'>";
	
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
			"<img src='img/$img'>",
			"<span style='font-size:12px;'>$disblayUri$date</a></span>",
			"<span style='font-size:12px;'>{$ligne["backup_source"]}</a></span>",
			"<span style='font-size:12px;'>{$ligne["event"]}</a></span>",
			)
		);
	}
	
	
echo json_encode($data);		


}
function div_groupware($text){
	if(!isset($GLOBALS["CLASS_TPL"])){$GLOBALS["CLASS_TPL"]=new templates();}
	return $GLOBALS["CLASS_TPL"]->_ENGINE_parse_body("<div style=\"font-size:11px;font-weight:bold;font-style:italic;color:#B64B13;margin:0px;padding:0px\">$text</div>");
}	
	
	
