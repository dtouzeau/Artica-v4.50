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
	
js();


function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sources=$tpl->_ENGINE_parse_body("{sources}");	
	$ID=$_GET["ID"];
	$task=$tpl->_ENGINE_parse_body("{task}");
	$html="YahooWin3('666','$page?popup=yes&ID=$ID','$task $ID&raquo;$sources');";
	echo $html;
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$source=$tpl->_ENGINE_parse_body("{source}");
	$advanced_options=$tpl->_ENGINE_parse_body("{advanced_options}");
	$mysql_instance=$tpl->_ENGINE_parse_body("{mysql_instance}");
	$WebSites=$tpl->_ENGINE_parse_body("{websites}");
	$BACKUP_TASK_CONFIRM_DELETE_SOURCE=$tpl->javascript_parse_text("{BACKUP_TASK_CONFIRM_DELETE_SOURCE}");
	$ID=$_GET["ID"];	
	$t=time();
	$html="
		
		$mysqlerror
	<table class='backup-sources-table-list' style='display: none' id='backup-sources-table-list' style='width:99%'></table>
	
<script>
var BackupSRCMem=0;
$(document).ready(function(){
$('#backup-sources-table-list').flexigrid({
	url: '$page?popup-list=yes&ID=$ID&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none', width : 31, sortable : false, align: 'center'},
		{display: '$source', name : 'source', width :529, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
	],
buttons : [
		{name: '$mysql_instance', bclass: 'add', onpress : add_mysql_instance},
		{separator: true},
		{name: '$WebSites', bclass: 'add', onpress : BackupWebSitesAdd},
		{name: 'WebGet', bclass: 'add', onpress : BackupRemoteWebSitesAdd},
		
		],	

	sortname: 'ip_address',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: false,
	rp: 15,
	showTableToggleBtn: true,
	width: 650,
	height: 300,
	singleSelect: true
	
	});   
});
	function add_mysql_instance() {
		Loadjs('backup.sources.mysqlinst.php?taskid=$ID');
		
	}
	
	function BackupWebSitesAdd(){
		Loadjs('backup.sources.www.php?taskid=$ID');
	}
	
	function BackupRemoteWebSitesAdd(){
		Loadjs('backup.sources.WebGet.php?taskid=$ID&index=-1');
	}

	var x_DELETE_BACKUP_SOURCES$t = function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){
				alert(tempvalue);
				return;
			}
			$('#table-backup-tasks').flexReload();
			if(document.getElementById('row'+BackupSRCMem)){
				$('#row'+BackupSRCMem).remove();
			}else{
				$('#backup-sources-table-list').flexReload();
			}
	}

	function DELETE_BACKUP_SOURCES$t(ID,INDEX){
		BackupSRCMem=INDEX;
		if(confirm('$BACKUP_TASK_CONFIRM_DELETE_SOURCE')){
			var XHR = new XHRConnection();
			XHR.appendData('DeleteBackupSource','yes');
			XHR.appendData('ID',ID);
			XHR.appendData('INDEX',INDEX);
			XHR.sendAndLoad('backup.tasks.php', 'GET',x_DELETE_BACKUP_SOURCES$t);
		}
	}

</script>
	
	
	";
	echo $html;
}

function popup_list(){
	include_once('ressources/class.freeweb.inc');
	$MyPage=CurrentPageName();
	$tpl=new templates();		
	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();
	$t=$_GET["t"];	
	
	$sql="SELECT datasbackup FROM backup_schedules WHERE ID='{$_GET["ID"]}'";
	$q=new mysql();
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$ressources=unserialize(base64_decode($ligne["datasbackup"]));
	$c=0;
	
	if(is_array($ressources)){
		while (list ($num, $val) = each ($ressources) ){
			if(is_array($val)){continue;}
			
			$val=str_replace("all","{BACKUP_ALL_MEANS}",$val);
			
		if(preg_match("#MYSQLINSTANCE:([0-9]+)#",$val, $re)){
				$sql="SELECT servername FROM mysqlmulti WHERE ID={$re[1]}";
				$ligne2=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
				$MysqlInstanceName=$ligne2["servername"];
				$val="{mysql_instance_databases} &laquo;$MysqlInstanceName&raquo;";
				
			}
		if(preg_match("#FREEWEB:(.+)#",$val, $re)){
			$free=new freeweb($re[1]);
			$val="{website} &laquo;{$re[1]}&raquo;";
		}	

		if(preg_match("#WEBGET:(.+)#",$val, $re)){
			$arr=unserialize(base64_decode($re[1]));
			if($arr["AutoRestore"]==1){
				$AutorestoreText=" {and} {auto-restore} {to} {$arr["AutoRestoreSiteName"]}";
			}
			
			$val="<a href=\"javascript:blur();\"
			OnClick=\"Loadjs('backup.sources.WebGet.php?taskid={$_GET["ID"]}&index=$num');\"
			style='text-decoration:underline;font-weight:bold'>WebGET {remote_artica_server} &laquo;{$arr["RemoteArticaSite"]}&raquo;</a><br>{from}:  &laquo;{$arr["RemoteArticaServer"]}&raquo;$AutorestoreText";
		}			
		
		
			
			$val=$tpl->_ENGINE_parse_body($val);
			$c++;
			$data['rows'][] = array(
					'id' => $num,
					'cell' => array(
					"<STRONG style='font-size:14px'>$num</STRONG>",
					"<code style='font-size:14px;font-weight:bold'>$val</code>",
					imgsimple("delete-24.png","{delete}","DELETE_BACKUP_SOURCES$t({$_GET["ID"]},$num)")
					)
					);			
			
			
			
			
		}
	}

	$folder=$tpl->_ENGINE_parse_body("{folder}");
	$sql="SELECT * FROM backup_folders WHERE taskid={$_GET["ID"]}";
	$results=$q->QUERY_SQL($sql,"artica_backup");	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$c++;
		if($ligne["recursive"]==1){$ligne["recursive"]="{enabled}";}else{$ligne["recursive"]="{disabled}";}
		$ligne["recursive"]=$tpl->_ENGINE_parse_body($ligne["recursive"]);
		$id=md5(base64_decode($ligne["path"]));
		$data['rows'][] = array(
					'id' => $id,
					'cell' => array(
					"<STRONG style='font-size:14px'>$folder ({$ligne["recursive"]})</STRONG>",
					"<code style='font-size:14px;font-weight:bold'><code>". base64_decode($ligne["path"])."</code></code>",
					"&nbsp;"
					)
					);				
	
		
	}	
	
	$data['total'] = $c;
	echo json_encode($data);	
	
}
	
	
	
