<?php
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.syslogs.inc');

	
$usersmenus=new usersMenus();
if(!$usersmenus->AsWebStatisticsAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die("DIE " .__FILE__." Line: ".__LINE__);	
}

if(isset($_GET["popup"])){tableau();exit;}
if(isset($_GET["search-store"])){search_store();exit;}

js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{source_logs}");
	header("content-type: application/x-javascript");
	$html="YahooWin2('850','$page?popup=yes','$title');";
	echo $html;
}

function tableau(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{add} {category}");
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$new_schedule=$tpl->_ENGINE_parse_body("{new_rotate}");
	$explain=$tpl->_ENGINE_parse_body("{explain_squid_tasks}");
	$run=$tpl->_ENGINE_parse_body("{run}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$sizeT=$tpl->_ENGINE_parse_body("{size}");
	$filename=$tpl->_ENGINE_parse_body("{filename}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$askdelete=$tpl->javascript_parse_text("{empty_store} ?");
	$zdate=$tpl->javascript_parse_text("{date}");
	$action=$tpl->javascript_parse_text("{action}");
	
	$q=new mysql_storelogs();
	$files=$q->COUNT_ROWS("accesslogs");
	$size=$q->TABLE_SIZE("access_store");
	$title=$tpl->_ENGINE_parse_body("MySQL: {storage} {files}:".FormatNumberX($files,0)." (".FormatBytes($size/1024).")");
	$t=time();
	$html="
	
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>
	<script>
	var rowSquidTask='';
	$(document).ready(function(){
	$('#$t').flexigrid({
	url: '$page?search-store=yes&minisize={$_GET["minisize"]}&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$zdate', name : 'filetime', width : 158, sortable : true, align: 'left'},
	{display: '$filename', name : 'filename', width : 378, sortable : true, align: 'left'},
	{display: '$sizeT', name : 'filesize', width : 95, sortable : true, align: 'left'},
	{display: '$task', name : 'taskid', width : 40, sortable : true, align: 'center'},
	{display: '$action', name : 'action', width : 40, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'delete', width : 32, sortable : false, align: 'center'}
	],

	searchitems : [
	{display: '$filename', name : 'filename'},
	{display: '$task', name : 'taskid'},
	],
	sortname: 'filetime',
	sortorder: 'desc',
	usepager: true,
	title: '<strong>$title</strong>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 835,
	height: 400,
	singleSelect: true
	
	});
	});
	
	
	
	function EmptyStorage(){
	if(confirm('$askdelete')){
	var XHR = new XHRConnection();
	XHR.appendData('DELETE-STORE','yes');
	XHR.sendAndLoad('logrotate.php', 'POST',x_EmptyStorage);
	}
	}
	
	function SquidCrontaskUpdateTable(){
	$('#$t').flexReload();
	}
	
	var x_RotateTaskEnable=function (obj) {
	var ID='{$_GET["ID"]}';
			var results=obj.responseText;
			if(results.length>0){alert(results);}
	}
	
	var x_EmptyStorage=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#$t').flexReload();
	}
	
	
	
	function DisableSquidDefaultScheduleCheck(){
	var XHR = new XHRConnection();
	if(document.getElementById('DisableSquidDefaultSchedule').checked){XHR.appendData('DisableSquidDefaultSchedule',1);}
	else{XHR.appendData('DisableSquidDefaultSchedule',0);}
	XHR.sendAndLoad('$page', 'POST',x_DisableSquidDefaultScheduleCheck);
	}
	
	
	
	
	var x_StorageTaskDelete=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#row'+rowSquidTask).remove();
	}
	
	function StorageTaskDelete(filename,md5){
	rowSquidTask=md5;
	var XHR = new XHRConnection();
	XHR.appendData('filename',filename);
	XHR.appendData('storage-delete','yes');
	XHR.sendAndLoad('logrotate.php', 'POST',x_StorageTaskDelete);
	}
	
	
	
	</script>";
	
	echo $html;
	
		
	
}
function search_store(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_storelogs();
	$search='%';
	$table="accesslogs";
	$page=1;
	$ORDER="ORDER BY ID filetime";
	$sock=new sockets();
	$t=$_GET["t"];
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	$database="syslogs";

	$total=0;
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No data...");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	


	
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	$results=$q->QUERY_SQL($sql);

	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$data = array();$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();
	if(!$q->ok){
		json_error_show($q->mysql_error,1);
	}

	


	while ($ligne = mysqli_fetch_assoc($results)) {
		$color="black";
		$events="&nbsp;";
		$md5=md5("RotateTask{$ligne['filename']}");
		$span="<span style='font-size:16px'>";
		$delete=imgtootltip("delete-24.png","{delete} {$ligne['ID']}","StorageTaskDelete('{$ligne['filename']}','$md5')");

		$jsEdit="Loadjs('logrotate.php?Rotate-js=yes&ID={$ligne['taskid']}&t=$t');";
		$jstask="<a href=\"javascript:blur();\" OnClick=\"javascript:$jsEdit\"
		style='font-size:16px;font-weight:bold;color:$color;text-decoration:underline'>";

		$jslloop="Loadjs('logrotate.php?log-js=yes&filename={$ligne['filename']}&t=$t');";
		$view="<a href=\"javascript:blur();\" OnClick=\"javascript:$jslloop\"
		style='font-size:16px;font-weight:bold;color:$color;text-decoration:underline'>";

		$ligne["filesize"]=FormatBytes($ligne["filesize"]/1024);
		if($ligne['taskid']==0){$jstask=null;}

		$action=null;
		if(preg_match("#auth\.log-.*?#", $ligne["filename"])){
				$action=imgsimple("service-restart-32.png",null,"Loadjs('squid.restoreSource.php?filename={$ligne["filename"]}')");
					
		}
		if(preg_match("#^squid-access.*?#", $ligne["filename"])){
			$action=imgsimple("service-restart-32.png",null,"Loadjs('squid.restoreSource.php?filename={$ligne["filename"]}')");
				
		}

		$xtime=strtotime("{$ligne['filetime']}");
		$dateTex=date("Y {l} {F} d",$xtime);
		if($tpl->language=="fr"){
			$dateTex=date("{l} d {F} Y",$xtime);
		}
		$dateTex=$tpl->_ENGINE_parse_body("$dateTex");
		//rowSquidTask
		$data['rows'][] = array(
		'id' => $md5,
		'cell' => array("$span{$ligne['filetime']}</a></span><div style='font-size:11px'><i>$dateTex</i></div>",
		"$span{$ligne["filename"]}</a><br><i>{$ligne['hostname']}</a></span>",
		"$span{$ligne["filesize"]}</a></span>",
		"$span{$ligne["taskid"]}</a></span>",$action,
		"" )
		);
	}


	echo json_encode($data);

}
function FormatNumberX($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}