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
	if(isset($_GET["show_backuped_folders"])){FOLDERS_BACKUPED();exit;}
	if(isset($_GET["ExecBackupTemporaryPath"])){BACKUP_OPTIONS_SAVE();exit;}
	if(isset($_GET["BACKUP_TASKS_LISTS"])){tasks_list();exit;}
	if(isset($_GET["BACKUP_TASKS_ROWS"])){tasks_rows();exit;}
	if(isset($_GET["show_tasks"])){show_tasks();exit;}
	if(isset($_GET["BACKUP_TASK_RUN"])){BACKUP_TASK_RUN();exit;}
	if(isset($_GET["backup-sources"])){BACKUP_SOURCES();exit;}
	if(isset($_GET["DeleteBackupSource"])){BACKUP_SOURCES_DELETE();exit;}
	if(isset($_GET["DeleteBackupTask"])){BACKUP_TASK_DELETE();exit;}
	if(isset($_GET["adv-options"])){BACKUP_OPTIONS();exit;}
	if(isset($_GET["backup-tests"])){BACKUP_TASK_TEST();exit;}
	if(isset($_GET["backup_stop_imap"])){BACKUP_SOURCES_SAVE_OPTIONS();exit;}
	if(isset($_GET["FOLDER_BACKUP"])){FOLDER_BACKUP_JS();exit;}
	if(isset($_GET["FOLDER_BACKUP_POPUP"])){FOLDER_BACKUP_POPUP();exit;}
	if(isset($_GET["BACKUP_FOLDER_ENABLE"])){BACKUP_FOLDER_ENABLE();exit;}
	if(isset($_GET["FOLDER_BACKUP_DELETE"])){FOLDERS_BACKUPED_DELETE();exit;}
	if(isset($_GET["TASK_EVENTS_DETAILS"])){TASK_EVENTS_DETAILS();exit;}
	if(isset($_GET["TASK_EVENTS_DETAILS_INFOS"])){TASK_EVENTS_DETAILS_INFOS();exit;}
	if(isset($_GET["BACKUP_TASK_MODIFY_RESSOURCES"])){BACKUP_TASK_MODIFY_RESSOURCES();exit;}
	if(isset($_POST["BACKUP_TASK_MODIFY_RESSOURCES_APPLY"])){BACKUP_TASK_MODIFY_RESSOURCES_SAVE();exit;}
	if(isset($_POST["DeleteAllBackTaskEvents"])){DeleteAllBackTaskEvents();exit;}
	if(isset($_GET["BACKUP_TASK_SHOW_CONTAINERS"])){BACKUP_TASK_SHOW_CONTAINERS();exit;}
	
	if(isset($_GET["events"])){BACKUP_EVENTS();exit;}
js();



function FOLDER_BACKUP_JS(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{backup_a_folder}");
	$folder=$_GET["FOLDER_BACKUP"];
	
	$start="YahooWin(600,'$page?FOLDER_BACKUP_POPUP=yes&folder=$folder','$title');";

	
	$html="
		function FOLDER_BACKUP_START(){
			$start
		
		}

	var x_BACKUP_FOLDER_ENABLE= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
	 }			
		
	function BACKUP_FOLDER_ENABLE(taskid){
		var XHR = new XHRConnection();
		XHR.appendData('BACKUP_FOLDER_ENABLE',taskid);
		if(document.getElementById('task_folder_'+taskid).checked){
			XHR.appendData('ENABLE',1);
			}else{
			XHR.appendData('ENABLE',0);
			}
		if(document.getElementById('recursive_folder_'+taskid).checked){
			XHR.appendData('RECURSIVE',1);
			}else{
			XHR.appendData('RECURSIVE',0);
			}			
			
		XHR.appendData('folder','$folder');
		XHR.sendAndLoad('$page', 'GET',x_BACKUP_FOLDER_ENABLE);
		
	}
	
	FOLDER_BACKUP_START();
	";
	echo $html;
}

function BACKUP_FOLDER_ENABLE(){
	$taskid=$_GET["BACKUP_FOLDER_ENABLE"];
	$folder=$_GET["folder"];
	$RECURSIVE=$_GET["RECURSIVE"];
	
	$sql="SELECT ID FROM backup_folders WHERE path='$folder' AND taskid='$taskid'";
	$q=new mysql();
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$ID=$ligne["ID"];
	
	
	if($ID==0){
		if($_GET["ENABLE"]==0){return;}
		if($_GET["ENABLE"]==1){
			$sql="INSERT INTO backup_folders (path,taskid,recursive) VALUES('$folder',$taskid,$RECURSIVE)";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo $q->mysql_error;}
			return;
		}
	}
	
	if($_GET["ENABLE"]==0){
		$sql="DELETE FROM backup_folders WHERE ID=$ID";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;}
		return;
	}
	
	$sql="UPDATE backup_folders SET recursive=$RECURSIVE WHERE ID=$ID";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}	
}

function FOLDERS_BACKUPED(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql();
	$cron=new cron_macros();
	
	$sql="SELECT * FROM backup_schedules ORDER BY ID DESC";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$backup=new backup_protocols();	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$typeBck=$backup->backuptypes[$ligne["resource_type"]];
		if($typeBck==null){$typeBck=$ligne["resource_type"];}
		$TASK_EX[$ligne["ID"]]=$typeBck."&nbsp;(".$cron->cron_human($ligne["schedule"]).")";
	}
	
	
	
	$html=$html."
	<div id='FOLDER_BACKUPED_DIV'>
	<table style='width:99%'>
	<th>{task}</th>
	<th>&nbsp;</th>
	<th>{path}</th>
	<th>{recursive}</th>
	<th>&nbsp;</th>
	</tr>";	
	
	$sql="SELECT * FROM backup_folders ORDER BY ID DESC";
	$results=$q->QUERY_SQL($sql,"artica_backup");	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($ligne["recursive"]==1){$ligne["recursive"]="{enabled}";}else{$ligne["recursive"]="{disabled}";}
		
		$html=$html.
		"<tr ". CellRollOver().">
		<td width=1% align='center'><strong style='font-size:12px'>{$ligne["taskid"]}</strong></td>
		<td style='font-size:12px' nowrap>". $TASK_EX[$ligne["taskid"]]."</td>
		<td style='font-size:12px' width=98%><code>". base64_decode($ligne["path"])."</code></td>
		<td width=1% style='font-size:12px' align='left'>{$ligne["recursive"]}</td>
		<td width=1% style='font-size:12px' align='left'>". imgtootltip("ed_delete.gif","{delete}","FOLDER_BACKUP_DELETE({$ligne["ID"]})")."</td>
		</tr>";
		
	}
$html=$html."</table></div>

<script>
var x_FOLDER_BACKUP_DELETE= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		
	 }			
		
	function FOLDER_BACKUP_DELETE(ID){
		var XHR = new XHRConnection();
		XHR.appendData('FOLDER_BACKUP_DELETE',ID);
		document.getElementById('FOLDER_BACKUPED_DIV').innerHTML='<center><img src=img/wait_verybig.gif></center>';
		XHR.sendAndLoad('$page', 'GET',x_FOLDER_BACKUP_DELETE);
		RefreshTab('main_config_backup_tasks');
	}
	
</script>

";
echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function FOLDERS_BACKUPED_DELETE(){
	$ID=$_GET["FOLDER_BACKUP_DELETE"];
	$q=new mysql();
	$sql="DELETE FROM backup_folders WHERE ID='$ID'";
	$q->QUERY_SQL($sql,"artica_backup");
	writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
	if(!$q->ok){echo $q->mysql_error;}
}


function FOLDER_BACKUP_POPUP(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$backup_decoded=base64_decode($_GET["folder"]);
	$cron=new cron_macros();
	$html="<H3>{backup_this_directory}: $backup_decoded</H3>
	<p style='font-size:13px'>{backup_this_directory_explain}</p>
	";
	
	$sql="SELECT taskid,recursive FROM backup_folders WHERE path='{$_GET["folder"]}'";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$tasks[$ligne["taskid"]]=1;
		$recursive[$ligne["taskid"]]=$ligne["recursive"];
	}
	
	$sql="SELECT * FROM backup_schedules ORDER BY ID DESC";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$backup=new backup_protocols();
	
	
	$html=$html."<table style='width:99%'>
	<th>{task}</th>
	<th>{STORAGE_TYPE}</th>
	<th>{schedule}</th>
	<th>{enabled}</th>
	<th>{recursive}</th>
	</tr>";
		
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){	
		
		$enable=Field_checkbox("task_folder_{$ligne["ID"]}",1,$tasks[$ligne["ID"]],"BACKUP_FOLDER_ENABLE({$ligne["ID"]})");
		$recursive=Field_checkbox("recursive_folder_{$ligne["ID"]}",1,$recursive[$ligne["ID"]],"BACKUP_FOLDER_ENABLE({$ligne["ID"]})");
		$STORAGE_TYPE=$backup->backuptypes[trim($ligne["resource_type"])];
		if($STORAGE_TYPE==null){$STORAGE_TYPE=$ligne["resource_type"];}
		
		
		$html=$html.
		"<tr ". CellRollOver().">
		<td width=1% align='center'><strong style='font-size:12px'>{$ligne["ID"]}</strong></td>
		<td style='font-size:12px'>{$backup->backuptypes[$ligne["resource_type"]]}</td>
		<td style='font-size:12px'>". $cron->cron_human($ligne["schedule"])."</td>
		<td style='font-size:12px' align='center'>$enable</td>
		<td style='font-size:12px' align='center'>$recursive</td>
		</tr>";
		
		
	}	
	
	$html=$html."</table>";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}




function js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{BACKUP_TASKS}");
	$sources=$tpl->_ENGINE_parse_body("{sources}");	
	$BACKUP_TASK_CONFIRM_DELETE=$tpl->javascript_parse_text("{BACKUP_TASK_CONFIRM_DELETE}");
	$BACKUP_TASK_CONFIRM_DELETE_SOURCE=$tpl->javascript_parse_text("{BACKUP_TASK_CONFIRM_DELETE_SOURCE}");
	$tests=$tpl->javascript_parse_text("{test}");
	$backupTaskRunAsk=$tpl->javascript_parse_text("{backupTaskRunAsk}");
	$apply_upgrade_help=$tpl->javascript_parse_text("{apply_upgrade_help}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$resources=$tpl->_ENGINE_parse_body("{resources}");
	$containers=$tpl->_ENGINE_parse_body("{containers}");
	$start="BACKUP_TASKS_LOAD();";
	$startcode="YahooWin2('785','$page?popup=yes','$title');";
	if(isset($_GET["in-front-ajax"])){
		$start="BACKUP_TASKS_LOAD2();";
	}
	
	
	if(isset($_GET["in-tab"])){
		$startcode="LoadAjax('{$_GET["in-tab"]}','$page?popup=yes')";
	}
	
	$html="
	mem_taskid='';	
	
		function BACKUP_TASKS_LOAD(){
			$startcode
		}
		
		function BACKUP_TASKS_LOAD2(){
			$('#BodyContent').load('$page?popup=yes');
		}		
	
		function BACKUP_TASKS_LISTS(){
		if(document.getElementById('table-backup-tasks')){
			$('#table-backup-tasks').flexReload();
			return;
			}
			LoadAjax('taskslists','$page?BACKUP_TASKS_LISTS=yes');
		}
		
		function BACKUP_TASKS_SOURCE(ID){
			Loadjs('backup.sources.tasks.php?ID='+ID);
			//YahooWin3('500','$page?backup-sources=yes&ID='+ID,'$sources');
		}
		
		function TASK_EVENTS_DETAILS(ID){
			Loadjs('backup.tasks.events.php?ID='+ID);
			//YahooWin3('700','$page?TASK_EVENTS_DETAILS='+ID,ID+'::$events');
		}
		
		function TASK_EVENTS_DETAILS_INFOS(ID){
			YahooWin5('700','$page?TASK_EVENTS_DETAILS_INFOS='+ID,ID+'::$events');
		}
		
		function BACKUP_TASK_MODIFY_RESSOURCES(ID){
			YahooWin3('500','$page?BACKUP_TASK_MODIFY_RESSOURCES='+ID,ID+'::$resources');
		}
		
		function BACKUP_TASK_SHOW_CONTAINERS(ID){
			YahooWin4('700','$page?BACKUP_TASK_SHOW_CONTAINERS='+ID,ID+'::$containers');
		}
		
		
var x_DeleteBackupTask= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		BACKUP_TASKS_LISTS();
		if(document.getElementById('wizard-backup-intro')){
			WizardBackupLoad();
		}
	 }	

var x_DELETE_BACKUP_SOURCES= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		BACKUP_TASKS_LISTS();
		YahooWin3Hide();
	 }		 
		
		function DeleteBackupTask(ID){
			if(confirm('$BACKUP_TASK_CONFIRM_DELETE')){
				var XHR = new XHRConnection();
				XHR.appendData('DeleteBackupTask',ID);
				XHR.sendAndLoad('$page', 'GET',x_DeleteBackupTask);
			}
		}
		

		
		
	var x_BACKUP_SOURCES_SAVE_OPTIONS= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			BACKUP_TASKS_SOURCE(mem_taskid);
			
		 }		
		
		
	function BACKUP_SOURCES_SAVE_OPTIONS(taskid){
		mem_taskid=taskid;
		var XHR = new XHRConnection();
		if(document.getElementById('backup_stop_imap').checked){
		XHR.appendData('backup_stop_imap',1);}else{
		XHR.appendData('backup_stop_imap',0);}
		XHR.appendData('taskid',taskid);
		document.getElementById('BACKUP_SOURCES_OPTIONS').innerHTML='<center><img src=img/wait_verybig.gif></center>';	
		XHR.sendAndLoad('$page', 'GET',x_BACKUP_SOURCES_SAVE_OPTIONS);
		}	

	function BACKUP_TASK_TEST(ID){
			YahooWin3('790','$page?backup-tests=yes&ID='+ID,'$tests');
		}
		
	var x_BACKUP_TASK_RUN= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			alert('$apply_upgrade_help');
			BACKUP_TASKS_LISTS();
		 }		
		
		
		
		function BACKUP_TASK_RUN(ID){
			if(confirm('$backupTaskRunAsk')){
				var XHR = new XHRConnection();
				XHR.appendData('BACKUP_TASK_RUN',ID);
				XHR.sendAndLoad('$page', 'GET',x_BACKUP_TASK_RUN);
			}
		}
		
	
	$start";
	
	
	echo $html;
}


function popup(){
	
	$tpl=new templates();
	$array["show_tasks"]='{tasks}';
	$array["show_backuped_folders"]='{backuped_folders}';
	$array["adv-options"]='{advanced_options}';
	$array["events"]='{events}';
	$array["arkeia"]="Arkeia";
	
	
	
	
	$page=CurrentPageName();

	
	foreach ($array as $num=>$ligne){
		if($num=="arkeia"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"arkeia.php\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_backup_tasks style='width:100%;height:730px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_backup_tasks').tabs();
			
			
			});
		</script>";	
	
	
}

function show_tasks(){
	$html="
	
	<div id='taskslists'></div>
	
	<script>
	BACKUP_TASKS_LISTS();
	</script>
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function FOLDER_BACKUPED_NUMBER($taskid){
	$q=new mysql();
	$sql="SELECT COUNT(ID) AS tcount FROM backup_folders WHERE taskid=$taskid";
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["tcount"]==null){$ligne["tcount"]=0;}
	return $ligne["tcount"];
}

function tasks_list(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$task=$tpl->_ENGINE_parse_body("{task}");
	$test=$tpl->_ENGINE_parse_body("{test}");
	$STORAGE_TYPE=$tpl->_ENGINE_parse_body("{STORAGE_TYPE}");
	$resource=$tpl->_ENGINE_parse_body("{resource}");	
	$schedule=$tpl->_ENGINE_parse_body("{schedule}");
	$sources=$tpl->_ENGINE_parse_body("{sources}");
	$add_task=$tpl->_ENGINE_parse_body("{add_task}");
	$t=time();
	$backup_tasks=$tpl->_ENGINE_parse_body("{backup_tasks}");
	$TB_WIDTH=829;

	// BACKUP_TASKS_LISTS() !!!  $('#table-backup-tasks').flexReload();
	$html="
		
		$mysqlerror
	<table class='table-backup-tasks' style='display: none' id='table-backup-tasks' style='width:99%'></table>
	
<script>
$(document).ready(function(){
$('#table-backup-tasks').flexigrid({
	url: '$page?BACKUP_TASKS_ROWS=yes',
	dataType: 'json',
	colModel : [
		{display: '$task', name : 'ID', width :31, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'none1', width : 31, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'none2', width : 31, sortable : false, align: 'left'},
		{display: '$test', name : 'none3', width : 31, sortable : false, align: 'left'},
		{display: '$STORAGE_TYPE', name : 'resource_type', width : 131, sortable : true, align: 'left'},
		{display: '$resource', name : 'pattern', width : 131, sortable : true, align: 'left'},
		{display: '$schedule', name : 'schedule', width : 131, sortable : true, align: 'left'},
		{display: '$sources', name : 'none4', width : 131, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'none2', width : 31, sortable : false, align: 'left'},
	],
buttons : [
		{name: '$add_task', bclass: 'add', onpress : add_task$t},
		{separator: true},
		
		],	
	searchitems : [
		{display: '$STORAGE_TYPE', name : 'resource_type'},
		{display: '$resource', name : 'pattern'},
		
		],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '$backup_tasks',
	useRp: true,
	rp: 15,
	showTableToggleBtn: true,
	width: $TB_WIDTH,
	height: 460,
	singleSelect: true
	
	});   
});

function add_task$t(){
	Loadjs('wizard.backup-all.php');
}

</script>

";

echo $html;
	
	
	
}



function tasks_rows(){
	$sql=new mysql();
	$sock=new sockets();
	$tpl=new templates();
	$backup=new backup_protocols();
	$cron=new cron_macros();
	$storages["usb"]="{usb_external_drive}";
	$storages["smb"]="{remote_smb_server}";
	$storages["rsync"]="{remote_rsync_server}";
	$storages["automount"]="{automount_ressource}";
	$storages["local"]="{local_directory}";		
	$storages["ssh"]="{remote_ssh_service}";
	
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	
	$search='%';
	$table="backup_schedules";
	$page=1;
	
	if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("No task...");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];
		
	}else{
		$total =$q->COUNT_ROWS($table,"artica_backup");
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql,"artica_backup");	
	if(!$q->ok){json_error_show($q->mysql_error);}
	$fontsize=14;
		
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$ressources=unserialize(base64_decode($ligne["datasbackup"]));
		$sources=(count($ressources)-1)+FOLDER_BACKUPED_NUMBER($ligne["ID"])." {sources}";
		$sources="<a href=\"javascript:Loadjs('backup.sources.tasks.php?ID={$ligne["ID"]}')\" style='text-decoration:underline;font-size:{$fontsize}px'>$sources</a>";
		
		
		
		
		$run=imgsimple("run-24.png","{run_task}","BACKUP_TASK_RUN({$ligne["ID"]})");
		if($ligne["pid"]>5){
			$array_pid=unserialize(base64_decode($sock->getFrameWork("cmd.php?procstat={$ligne["pid"]}")));
			if($array_pid["since"]<>null){
				$run=imgsimple("ajax-menus-loader.gif","{running}: {since} {$array_pid["since"]}","");
			}
		}
		
		
		$sql="SELECT SUM(size) as tsize FROM backup_storages WHERE taskid={$ligne["ID"]}";
		$ligneSize=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$sizeStorages=FormatBytes($ligneSize["tsize"]);
		$divStart="<span style='font-size:{$fontsize}px'>";
		$divStop="</span>";
		
				$data['rows'][] = array(
					'id' => $ligne["ID"],
					'cell' => array(
					 
					 "$divStart{$ligne["ID"]}$divStop",
					 imgsimple("events-24.png","{events}","TASK_EVENTS_DETAILS({$ligne["ID"]})"),
					 $run,
					 imgsimple("eclaire-24.png","{BACKUP_TASK_TEST}","BACKUP_TASK_TEST({$ligne["ID"]})"),
					 "<a href=\"javascript:BACKUP_TASK_MODIFY_RESSOURCES('{$ligne["ID"]}')\" style='text-decoration:underline;font-size:{$fontsize}px'>". $tpl->_ENGINE_parse_body($storages[$ligne["resource_type"]])."</a>",
					 "<a href=\"javascript:BACKUP_TASK_SHOW_CONTAINERS('{$ligne["ID"]}')\" style='text-decoration:underline;font-size:{$fontsize}px'>". $backup->extractFirsRessource($ligne["pattern"])."</a> ($sizeStorages)",
					  "$divStart".$tpl->_ENGINE_parse_body($cron->cron_human($ligne["schedule"])). "$divStop",
					 "$divStart". $tpl->_ENGINE_parse_body($sources). "$divStop",
					 imgsimple("delete-24.png","{delete}","DeleteBackupTask({$ligne["ID"]})")
					 )
					);		
			}
echo json_encode($data);	
	}
	
function BACKUP_SOURCES(){
	$sql="SELECT datasbackup FROM backup_schedules WHERE ID='{$_GET["ID"]}'";
	$q=new mysql();
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$ressources=unserialize(base64_decode($ligne["datasbackup"]));
	$html="
	<div style='width:100%;height:150px;overflow:auto;'>
	<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<th colspan=2>{source}</th>
	<th>&nbsp;</th>
</thead>
<tbody class='tbody'>";
			
	
	
	if(is_array($ressources)){
		while (list ($num, $val) = each ($ressources) ){
			if(is_array($val)){continue;}
			$val=str_replace("all","{BACKUP_ALL_MEANS}",$val);
			
			
			
			$html=$html.
			
			"
			
			<tr class=$classtr>
				<td widh=1% valign='top' style='font-size:14px'><STRONG>$num</STRONG></td>
				<td width=99%><code style='font-size:14px;font-weight:bold'>$val</td>
				<td widh=1% valign='top'>". imgtootltip("delete-32.png","{delete}","DELETE_BACKUP_SOURCES({$_GET["ID"]},$num)")."</td>
			</tr>
			";
			
		}
	}
	
	$sql="SELECT * FROM backup_folders WHERE taskid={$_GET["ID"]}";
	$results=$q->QUERY_SQL($sql,"artica_backup");	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($ligne["recursive"]==1){$ligne["recursive"]="{enabled}";}else{$ligne["recursive"]="{disabled}";}
		
		$html=$html.
		"<tr><td colspan=4 style='border-top:1px solid #005447'>&nbsp;</td></tr>
		<tr ". CellRollOver().">
		<td widh=1% valign='top'><img src='img/fw_bold.gif'></td>
		<td style='font-size:12px' nowrap><strong>{folder}</strong></td>
		<td style='font-size:12px;font-weight:bold' width=98%><code>". base64_decode($ligne["path"])."</code></td>
		<td width=1% style='font-size:12px' align='left'>&nbsp;</td>
		</tr>";
		
	}	
	
	
	$html=$html."
	</table>
	</div>
	<BR>
	<div id='BACKUP_SOURCES_OPTIONS'>
	<div style='font-size:16px'>{options}</div>
	<table style='width:99%' class=form>
	<tr>
		<td valign='top' class=legend>{backup_stop_imap}:</td>
		<td valign='top'>". Field_checkbox("backup_stop_imap",1,$ressources["OPTIONS"]["STOP_IMAP"],"BACKUP_SOURCES_SAVE_OPTIONS({$_GET["ID"]})")."</td>
	</tr>
	</table>
	</div>";
	






$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);		
	
}

function BACKUP_TASK_RUN(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?backup-task-run={$_GET["BACKUP_TASK_RUN"]}");
	
}

function BACKUP_SOURCES_DELETE(){
	$sql="SELECT datasbackup FROM backup_schedules WHERE ID='{$_GET["ID"]}'";
	$q=new mysql();
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$ressources=unserialize(base64_decode($ligne["datasbackup"]));
	unset($ressources[$_GET["INDEX"]]);
	$new_ressources=base64_encode(serialize($ressources));
	$sql="UPDATE backup_schedules SET datasbackup='$new_ressources' WHERE ID='{$_GET["ID"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?backup-build-cron=yes");	
	
}

function BACKUP_SOURCES_SAVE_OPTIONS(){
	$sql="SELECT datasbackup FROM backup_schedules WHERE ID='{$_GET["taskid"]}'";
	$q=new mysql();
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$ressources=unserialize(base64_decode($ligne["datasbackup"]));
	
	$ressources["OPTIONS"]["STOP_IMAP"]=$_GET["backup_stop_imap"];
	
	$new_ressources=base64_encode(serialize($ressources));
	$sql="UPDATE backup_schedules SET datasbackup='$new_ressources' WHERE ID='{$_GET["taskid"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	
	
}


function BACKUP_TASK_DELETE(){
	$sql="DELETE FROM backup_schedules WHERE ID='{$_GET["DeleteBackupTask"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	$sql="DELETE FROM backup_events WHERE task_id='{$_GET["DeleteBackupTask"]}'";
	$q->QUERY_SQL($sql,"artica_events");
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?backup-build-cron=yes");	
	
}

function BACKUP_TASK_TEST(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("cmd.php?backup-sql-test={$_GET["ID"]}")));
	
$html="
<div style='width:100%;height:450px;width:100%;overflow:auto'>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th>&nbsp;</th>
	
	</tr>
</thead>
<tbody class='tbody'>";	
	
	while (list ($num, $val) = each ($datas) ){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		
		$html=$html . "
		<tr  class=$classtr>
			
			<td style='font-size:13px'>$val</td>
		</tr>";				
		
		
	}
	$html=$html . "</tbody></table></div>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}

function BACKUP_OPTIONS_SAVE(){
	$sock=new sockets();
	$sock->SET_INFO("ExecBackupTemporaryPath",$_GET["ExecBackupTemporaryPath"]);
	$sock->SET_INFO("NoBzipForBackupDatabasesDump",$_GET["NoBzipForBackupDatabasesDump"]);
	$sock->SET_INFO("ExecBackupDeadAfterH",$_GET["ExecBackupDeadAfterH"]);
	$sock->SET_INFO("ExecBackupMaxContainers",$_GET["ExecBackupMaxContainers"]);
	
}


function BACKUP_OPTIONS(){
	$sock=new sockets();
	$page=CurrentPageName();
	$temporarySourceDir=$sock->GET_INFO("ExecBackupTemporaryPath");
	$ExecBackupDeadAfterH=$sock->GET_INFO("ExecBackupDeadAfterH");
	$ExecBackupMaxContainers=$sock->GET_INFO("ExecBackupMaxContainers");
	if(!is_numeric($ExecBackupMaxContainers)){$ExecBackupMaxContainers=6;}	
	
	if($temporarySourceDir==null){$temporarySourceDir="/home/mysqlhotcopy";}
	$NoBzipForBackupDatabasesDump=$sock->GET_INFO("NoBzipForBackupDatabasesDump");
	if($NoBzipForBackupDatabasesDump==null){$NoBzipForBackupDatabasesDump=1;}
	if(!is_numeric($ExecBackupDeadAfterH)){$ExecBackupDeadAfterH=2;}
	
	$html="
	<div id='backup-adv-options'>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:13px'>{ExecBackupTemporaryPath}:</td>
		<td>". Field_text("ExecBackupTemporaryPath",$temporarySourceDir,"font-size:13px;width:220px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:13px'>{ExecBackupDeadAfterH}:</td>
		<td style='font-size:13px'>". Field_text("ExecBackupDeadAfterH",$ExecBackupDeadAfterH,"font-size:13px;width:40px")."&nbsp;{hours}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:13px'>{ExecBackupMaxContainers}:</td>
		<td style='font-size:13px'>". Field_text("ExecBackupMaxContainers",$ExecBackupMaxContainers,"font-size:13px;width:40px")."&nbsp;{containers}</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:13px'>{NoBzipForDatabasesDump}:</td>
		<td>". Field_checkbox("NoBzipForBackupDatabasesDump",1,$NoBzipForBackupDatabasesDump)."</td>
	</tr>	
	
	<tr>
		<td colspan=2 align='right'>". button("{apply}","SAVE_BACKUP_OPTIONS()")."</td>
	</tr>
	</table>
	</div>
	<script>
	var x_SAVE_BACKUP_OPTIONS= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		RefreshTab('main_config_backup_tasks');
	 }	
	
		function SAVE_BACKUP_OPTIONS(){
			var XHR = new XHRConnection();
			XHR.appendData('ExecBackupTemporaryPath',document.getElementById('ExecBackupTemporaryPath').value);
			XHR.appendData('ExecBackupDeadAfterH',document.getElementById('ExecBackupDeadAfterH').value);
			XHR.appendData('ExecBackupMaxContainers',document.getElementById('ExecBackupMaxContainers').value);
			
			
			if(document.getElementById('NoBzipForBackupDatabasesDump').checked){
				XHR.appendData('NoBzipForBackupDatabasesDump',1);
			}else{
				XHR.appendData('NoBzipForBackupDatabasesDump',0);
			}
			
			document.getElementById('backup-adv-options').innerHTML='<center><img src=img/wait_verybig.gif></center>';
			XHR.sendAndLoad('$page', 'GET',x_SAVE_BACKUP_OPTIONS);
		
		}
		
	</script>";
	
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function BACKUP_EVENTS(){
	
$html="<table style='width:99%'>
	<th>&nbsp;</th>
	<th>{date}</th>
	<th>{source}</th>
	<th>{resource}</th>
	<th>{status}</th>
	</tr>";
			
	$backup=new backup_protocols();
	$sql="SELECT * FROM `cyrus_backup_events` ORDER BY `cyrus_backup_events`.`ID` DESC LIMIT 0 , 200";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_events");	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$img="status_ok.png";
		if($ligne["success"]==0){$img="status_critical.png";}
		$array=$backup->ParseProto($ligne["remote_ressource"]);
$html=$html.
		"
		<tr ". CellRollOver().">
		<td widh=1% valign='top'><img src='img/fw_bold.gif'></td>
		<td style='font-size:12px' nowrap><strong>{$ligne["zDate"]}</strong></td>
		<td style='font-size:12px;font-weight:bold' width=98%><code>{$ligne["local_ressource"]}</code></td>
		<td style='font-size:12px' align='left' nowrap>{$array["SERVER"]}</td>
		<td width=1% style='font-size:12px' align='left'><img src='img/$img'></td>
		</tr>
		<tr><td colspan=5 style='border-bottom:1px solid #005447' align=right><i style='font-size:11px'>{$ligne["events"]}</i></td></tr>";		
		
		
	}
	
	
$html=$html."
	</table>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}


function BACKUP_TASK_SHOW_CONTAINERS(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["BACKUP_TASK_SHOW_CONTAINERS"];
	$sql="SELECT *  FROM backup_storages WHERE taskid=$ID ORDER BY zDate";
$html="<div style='height:300px;overflow:auto' id='TASK_EVENTS_DETAILS_LIST_DIV'>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th>{date}</th>
	<th>{size}</th>
	<th width=99%>{directory}</th>
	</tr>
</thead>
<tbody>";		

	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
	if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
	$size=FormatBytes($ligne["size"]);
	$cnx_params=unserialize(base64_decode($ligne["cnx_params"]));
	$container=$cnx_params["CONTAINER"];	
	$mount_path_final=$cnx_params["mount_path_final"];	
	$html=$html.
		"
		<tr class=$classtr>
			<td width=1%  style='font-size:14px' nowrap>{$ligne["zDate"]}</td>
			<td  style='font-size:14px' nowrap width=1%><strong>$size</strong></td>
			<td style='font-size:14px' width=99% nowrap colspan=2><strong>$container</strong><br><span style='font-size:10px'>$mount_path_final</span></td>		
		</tr>
		";		
	
	
	}
	
	$html=$html."
		</tbody>
	</table>
	</div>";
	
echo $tpl->_ENGINE_parse_body($html);	
	
}


function TASK_EVENTS_DETAILS_INFOS(){
	$ID=$_GET["TASK_EVENTS_DETAILS_INFOS"];
	$sql="SELECT *  FROM backup_events WHERE ID=$ID";
	$q=new mysql();
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	$html="<div style='font-size:17px'>$ID)&nbsp;{$ligne["zdate"]}::{$ligne["backup_source"]}</div>
	<div style='height:300px;overflow:auto;border:3px solid #CCCCCC;padding:5px;margin:5px'>";
	$events=@explode("\n",$ligne["event"]);
	

	while (list ($num, $line) = each ($events)){

		$html=$html. "<div style='padding:2px'><code>".htmlspecialchars($line)."</code></div>";
	}
	
	$html=$html."</div>";
	
	echo $html;
	
}
function DeleteAllBackTaskEvents(){
	$ID=$_POST["DeleteAllBackTaskEvents"];
	if(!is_numeric($ID)){return;}
	$sql="DELETE  FROM `backup_events` WHERE `task_id`='$ID'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error;}	
	
	
}

function TASK_EVENTS_DETAILS(){
	$ID=$_GET["TASK_EVENTS_DETAILS"];
	$tpl=new templates();
	$page=CurrentPageName();
	$delete_all_items=$tpl->javascript_parse_text("{delete_all_items}");	
$html="<div style='height:300px;overflow:auto' id='TASK_EVENTS_DETAILS_LIST_DIV'>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th>{status}</th>
	<th>{date}</th>
	<th>{resource}</th>
	<th width=99%>{events}</th>
	<th width=1%>". imgtootltip("delete-24.png","{delete_all}","DeleteAllBackTaskEvents()")."</td>
	</tr>
</thead>
<tbody>";	
	
$sql="SELECT *,DATE_FORMAT(zdate,'%W') as explainday,DATE_FORMAT(zdate,'%p') as tmorn,DATE_FORMAT(zdate,'%Hh%i') as ttime  FROM `backup_events` WHERE `task_id`='$ID' ORDER BY `backup_events`.`zdate` DESC LIMIT 0 , 200";
$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_events");	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
	
		$img="info-18.png";
		$status=null;
		if(strlen($ligne["event"])>60){$ligne["event"]=substr($ligne["event"],0,57)."...";}
		
		if(preg_match("#^([A-Z]+).*?,#",$ligne["event"],$re)){
			$status=$re[1];
			$ligne["event"]=str_replace($re[1].',','',$ligne["event"]);
			
		}
		$ligne["explainday"]=strtolower($ligne["explainday"]);
		$date="{{$ligne["explainday"]}} {$ligne["ttime"]}";
		
		
		switch ($status) {
			case "INFO":$img="info-18.png";break;
			case "ERROR":$img="status_warning.png";break;
			default:
				;
			break;
		}
		
		$display="TASK_EVENTS_DETAILS_INFOS({$ligne["ID"]})";
		$disblayUri="<a href=\"javascript:blur();\" OnClick=\"javascript:$display;\" style='font-size:12px;text-decoration:underline'>";
	if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
$html=$html.
		"
		<tr class=$classtr>
			<td width=1%  align='center' valign='middle'><img src='img/$img'></td>
			<td width=1%  style='font-size:12px' nowrap><strong>$disblayUri$date</a></strong></td>
			<td  style='font-size:12px' nowrap width=1%><strong>{$ligne["backup_source"]}</strong></td>
			<td style='font-size:12px' width=99% nowrap colspan=2><strong>{$ligne["event"]}</strong></td>		
		</tr>
		";
		
		
	}
	
$html=$html."
		</tbody>
	</table>
	</div>
<script>
	var x_DeleteAllBackTaskEvents= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		YahooWin3Hide();
	 }	
	
	function DeleteAllBackTaskEvents(){
		if(confirm('$delete_all_items ?')){
			AnimateDiv('TASK_EVENTS_DETAILS_LIST_DIV');
			var XHR = new XHRConnection();
			XHR.appendData('DeleteAllBackTaskEvents',$ID);
			XHR.sendAndLoad('$page', 'POST',x_DeleteAllBackTaskEvents);
		}
		
	}
</script>	
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);		
}


function BACKUP_TASK_MODIFY_RESSOURCES(){
	$backup=new backup_protocols();	
	$page=CurrentPageName();
	$ID=$_GET["BACKUP_TASK_MODIFY_RESSOURCES"];
	$q=new mysql();
	$sql="SELECT resource_type,pattern FROM backup_schedules WHERE ID=$ID";
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	
	$backupfields=Field_array_Hash($backup->backuptypes,"BACKUP_TASK_MODIFY_RESSOURCES_TYPE",$ligne["resource_type"],null,null,0,"font-size:14px;padding:3px");
	
	$html="
	<div id='BACKUP_TASK_MODIFY_RESSOURCES_DIV'>
	<table style='width:99%' class=form>
	<tr>
		<td valign='middle' class=legend style='font-size:14px'>{STORAGE_TYPE}:</td>
		<td valign='top'>$backupfields</td>
	</tr>
	<tr>
	<td colspan=2><input type='text' id='BACKUP_TASK_MODIFY_RESSOURCES_PATTERN' style='width:100%;padding:5px;font-size:14px;border:2px solid #CCCCCC' value='{$ligne["pattern"]}'></td>
	</tr>
	<tr>
	<td colspan=2 align='right'><hr>". button("{apply}","BACKUP_TASK_MODIFY_RESSOURCES_APPLY()",14)."</td>
	</tr>
	
	</table>
	</div>
	<script>
	var x_BACKUP_TASK_MODIFY_RESSOURCES_APPLY= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		RefreshTab('main_config_backup_tasks');
		YahooWin3Hide();
	 }	
	
		function BACKUP_TASK_MODIFY_RESSOURCES_APPLY(){
			var XHR = new XHRConnection();
			XHR.appendData('BACKUP_TASK_MODIFY_RESSOURCES_APPLY',$ID);
			XHR.appendData('BACKUP_TASK_MODIFY_RESSOURCES_TYPE',document.getElementById('BACKUP_TASK_MODIFY_RESSOURCES_TYPE').value);
			XHR.appendData('BACKUP_TASK_MODIFY_RESSOURCES_PATTERN',document.getElementById('BACKUP_TASK_MODIFY_RESSOURCES_PATTERN').value);
			document.getElementById('BACKUP_TASK_MODIFY_RESSOURCES_DIV').innerHTML='<center><img src=img/wait_verybig.gif></center>';
			XHR.sendAndLoad('$page', 'POST',x_BACKUP_TASK_MODIFY_RESSOURCES_APPLY);
		
		}
	
</script>	";
	
		$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}

function BACKUP_TASK_MODIFY_RESSOURCES_SAVE(){
	$sql="UPDATE backup_schedules 
		SET pattern='{$_POST["BACKUP_TASK_MODIFY_RESSOURCES_PATTERN"]}',
		resource_type='{$_POST["BACKUP_TASK_MODIFY_RESSOURCES_TYPE"]}'
		WHERE ID={$_POST["BACKUP_TASK_MODIFY_RESSOURCES_APPLY"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo $q->mysql_error;
	}
}





	
?>