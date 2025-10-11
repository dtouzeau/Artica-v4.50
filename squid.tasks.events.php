<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.mysql.squid.builder.php');
	$user=new usersMenus();
	if(!$user->AsSquidAdministrator){$tpl=new templates();echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";exit;}
	
	if(isset($_GET["search"])){search();exit;}
	if(isset($_GET["select-tasks"])){select_tasks();exit;}
	if(isset($_GET["select-script"])){select_script();exit;}
	if(isset($_GET["select-function"])){select_function();exit;}
	
	
table();

	

function table(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$purge_catagories_database_explain=$tpl->javascript_parse_text("{purge_catagories_database_explain}");
	$purge_catagories_table_explain=$tpl->javascript_parse_text("{purge_catagories_table_explain}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{add} {category}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$new_schedule=$tpl->_ENGINE_parse_body("{new_schedule}");
	$DisableSquidDefaultSchedule=$sock->GET_INFO("DisableSquidDefaultSchedule");
	if(!is_numeric($DisableSquidDefaultSchedule)){$DisableSquidDefaultSchedule=0;}
	$explain=$tpl->_ENGINE_parse_body("{explain_squid_tasks}");
	$run=$tpl->_ENGINE_parse_body("{run}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$run_this_task_now=$tpl->javascript_parse_text("{run_this_task_now} ?");
	$zDate=$tpl->_ENGINE_parse_body("{zDate}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$file=$tpl->_ENGINE_parse_body("{file}");
	$function=$tpl->_ENGINE_parse_body("{function}");
	
	$t=time();
	$html="
	<div class=explain>$explain</div>


	<div style='margin-left:-15px'>
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>
	</div>
<script>
var rowSquidTask='';
$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?search=yes',
	dataType: 'json',
	colModel : [
		{display: '$zDate', name : 'zDate', width : 132, sortable : true, align: 'left'},
		{display: '$task', name : 'TASKID', width : 121, sortable : false, align: 'left'},
		{display: '$description', name : 'description', width : 587, sortable : false, align: 'left'},

	],
buttons : [
	{name: '$task', bclass: 'Search', onpress : SelectTasks},
	{name: '$file', bclass: 'Search', onpress : SelectScript},
	{name: '$function', bclass: 'Search', onpress : SelectFunction},
	
		],	
	searchitems : [
		{display: '$description', name : 'TimeDescription'},
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 897,
	height: 350,
	singleSelect: true
	
	});   
});	

	function AddNewSchedule(category){
			Loadjs('$page?AddNewSchedule-js=yes&ID=0');
	}
	
	function SquidCrontaskUpdateTable(){
		$('#$t').flexReload();
	 }
	
	var x_SquidTaskEnable=function (obj) {
		var ID='{$_GET["ID"]}';
		var results=obj.responseText;
		if(results.length>0){alert(results);}		
	}

	var x_DisableSquidDefaultScheduleCheck=function (obj) {
		var ID='{$_GET["ID"]}';
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		$('#$t').flexReload();		
	}		


	function SelectTasks(){
		YahooWin4('500','$page?select-tasks=yes&t=$t','$task?');
	}
	
	function SelectFunction(){
		YahooWin4('550','$page?select-function=yes&t=$t','$task?');
	}
	
	function SelectScript(){
		YahooWin4('550','$page?select-script=yes&t=$t','$file?');
	}	

	function DisableSquidDefaultScheduleCheck(){
	  	var XHR = new XHRConnection();
	  	if(document.getElementById('DisableSquidDefaultSchedule').checked){XHR.appendData('DisableSquidDefaultSchedule',1);}
	  	else{XHR.appendData('DisableSquidDefaultSchedule',0);}
	  	XHR.sendAndLoad('$page', 'POST',x_DisableSquidDefaultScheduleCheck);	
	}
	
	
	function SquidTaskRun(ID,explain){
		if(confirm('$run_this_task_now `'+explain+'`')){
			var XHR = new XHRConnection();
			XHR.appendData('ID',ID);
	  		XHR.appendData('schedule-run','yes');
	  		XHR.sendAndLoad('$page', 'POST',x_SquidTaskEnable);		
		}
	
	}
	
	
	var x_SquidTaskDelete=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		$('#rowSquidTask'+rowSquidTask).remove();
	}	
	
	function SquidTaskDelete(ID){
		rowSquidTask=ID;
	  	var XHR = new XHRConnection();
		XHR.appendData('ID',ID);
	  	XHR.appendData('schedule-delete','yes');
	  	XHR.sendAndLoad('$page', 'POST',x_SquidTaskDelete);	
	}
	
	
	
</script>";
	
	echo $html;
}

function search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql();	
	$table="ufdbguard_admin_events";
	$search='%';
	$page=1;
	$WHERE=1;
	$default=$tpl->_ENGINE_parse_body("{default}");
	
	
	if($_GET["filename"]<>null){$ADD2=" AND filename='{$_GET["filename"]}'";}
	if($_GET["function"]<>null){$ADD2=" AND function='{$_GET["function"]}'";}
	if(!is_numeric($_GET["taskid"])){$_GET["taskid"]=0;}
	
	if($_GET["taskid"]>0){$ADD2=$ADD2." AND TASKID='{$_GET["taskid"]}'";$WHERE=1;}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*{$_POST["query"]}*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE $WHERE $ADD2$searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["tcount"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $WHERE $ADD2";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$sql="SELECT * FROM $table WHERE $WHERE $ADD2$searchstring $ORDER $limitSql";
	
	$line=$tpl->_ENGINE_parse_body("{line}");
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysqli_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array(date('Y-m-d H:i:s'),$tpl->_ENGINE_parse_body("{no_event}<br>$sql"),"", "",""));}
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$description=$ligne["description"];
		$description=str_replace("\n", "<br>", $description);
		
		
		if($ligne["TASKID"]==0){$taskname=$default;}
		else{
			$taskname="[{$ligne["TASKID"]}]:".GetTaskName($ligne["TASKID"]) ;
		}
		
		
		
		
		
		$description=$tpl->_ENGINE_parse_body($description);
		$description=wordwrap($description,100,"<br>");
		$description=str_replace("<br><br>","<br>",$description);
		$function="<div style='margin-top:-4px;margin-left:-5px'>
			<i style='font-size:11px'>{$ligne["filename"]}:{$ligne["function"]}() $line {$ligne["line"]} ($taskname)</i>
			</div>";
		
	$data['rows'][] = array(
		'id' => $ligne['zDate'],
		'cell' => array(
		"<strong style='font-size:13px'>{$ligne["zDate"]}</strong>",
		"<strong style='font-size:13px'>{$taskname}</strong>",
		"<div style='font-size:13px;font-weight:normal'>$description$function</div>",
		)
		);
	}
	
	
echo json_encode($data);

}

function GetTaskName($taskid){
	$tpl=new templates();
	if(isset($GLOBALS["TASKSNAME"][$taskid])){return $GLOBALS["TASKSNAME"][$taskid];}
	$q=new mysql_squid_builder();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT TaskType FROM webfilters_schedules WHERE ID='$taskid'"));
	$GLOBALS["TASKSNAME"][$taskid]=$tpl->_ENGINE_parse_body($q->tasks_array[$ligne["TaskType"]]);
	return $GLOBALS["TASKSNAME"][$taskid];
}

function select_function(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=$_GET["t"];
	$sql="SELECT filename,function FROM ufdbguard_admin_events GROUP BY filename,function ORDER BY function";
	$results = $q->QUERY_SQL($sql,"artica_events");
	$f[null]="{all}";
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$f[$ligne["function"]]="{$ligne["filename"]}::{$ligne["function"]}";
	}
	
	$html="
	<table style='width:100%' class=form>
	<tr>
		<td class=legend>{function}:</td>
		<td>". Field_array_Hash($f, "function$t",null,"SelectTaskIDPerform$t()",null,0,"font-size:12.5px",false)."</td>
	</tr>
	</table>
	
	<script>
		function SelectTaskIDPerform$t(){
			 var taskid=document.getElementById('function$t').value;
			 $('#$t').flexOptions({ url: '$page?search=yes&function='+taskid }).flexReload();
			 YahooWin4Hide();
		}
	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);		
	
}

function select_script(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=$_GET["t"];
	$sql="SELECT filename FROM ufdbguard_admin_events GROUP BY filename ORDER BY filename";
	$results = $q->QUERY_SQL($sql,"artica_events");
	$f[null]="{all}";
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$f[$ligne["filename"]]=$ligne["filename"];
	}
	
	$html="
	<table style='width:100%' class=form>
	<tr>
		<td class=legend>{file}:</td>
		<td>". Field_array_Hash($f, "filename$t",null,"SelectTaskIDPerform$t()",null,0,"font-size:12.5px",false)."</td>
	</tr>
	</table>
	
	<script>
		function SelectTaskIDPerform$t(){
			 var taskid=document.getElementById('filename$t').value;
			 $('#$t').flexOptions({ url: '$page?search=yes&filename='+taskid }).flexReload();
			 YahooWin4Hide();
		}
	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function select_tasks(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=$_GET["t"];
	$sql="SELECT TASKID FROM ufdbguard_admin_events GROUP BY TASKID ORDER BY TASKID";
	$results = $q->QUERY_SQL($sql,"artica_events");
	$f[null]="{all}";
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$f[$ligne["TASKID"]]=GetTaskName($ligne["TASKID"]);
	}
	
	$html="
	<table style='width:100%' class=form>
	<tr>
		<td class=legend>{task}:</td>
		<td>". Field_array_Hash($f, "TaskSelect$t",null,"SelectTaskIDPerform()",null,0,"font-size:12.5px",false)."</td>
	</tr>
	</table>
	
	<script>
		function SelectTaskIDPerform(){
			 var taskid=document.getElementById('TaskSelect$t').value;
			 $('#$t').flexOptions({ url: '$page?search=yes&taskid='+taskid }).flexReload();
			 YahooWin4Hide();
		}
	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}
