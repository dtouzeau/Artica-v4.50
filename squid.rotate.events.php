<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die("DIE " .__FILE__." Line: ".__LINE__);	
}


if(isset($_GET["cachelogs-events-list"])){events_search();exit;}

page();

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$events=$tpl->_ENGINE_parse_body("{events}");
	$zdate=$tpl->_ENGINE_parse_body("{zDate}");

	$title=$tpl->_ENGINE_parse_body("{rotate_events}");
	
	$t=time();
	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	
<script>
function flexRTStart$t(){
$('#flexRT$t').flexigrid({
	url: '$page?cachelogs-events-list=yes',
	dataType: 'json',
	colModel : [
		{display: '<strong style=font-size:18px>$zdate</strong>', name : 'zDate', width :238, sortable : true, align: 'left'},
		{display: '<strong style=font-size:18px>$events</strong>', name : 'events', width : 1184, sortable : false, align: 'left'},
		],
	
	searchitems : [
		{display: '$events', name : 'events'}
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});  
	$('table-1-selected').remove();
	$('flex1').remove();	 
}

function SelectGrid2(com, grid) {
	var items = $('.trSelected',grid);
	var id=items[0].id;
	id = id.substring(id.lastIndexOf('row')+3);
	if (com == 'Select') {
			LoadAjax('table-1-selected','$page?familysite-show='+id);
		}
	}
	 
setTimeout('flexRTStart$t()',800);		 

</script>
	
	
	";
	
	echo $html;
	
}

function events_search(){
$page=CurrentPageName();
$tpl=new templates();
$sock=new sockets();
$sorted=false;
	$export=PROGRESS_DIR."/rotate.events";
	//$_POST["rp"]=intval($_POST["rp"])+10;
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}	
	if(isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}

	if($_POST["query"]<>null){
		$search=base64_encode($_POST["query"]);
		$sock->getFrameWork("squid.php?rotateevents=$search&rp=$rp");
		if($GLOBALS["VERBOSE"]){echo "Reading $export<br>\n";}
		$datas=explode("\n",@file_get_contents($export));
		$total=count($datas);
		
	}else{
		$sock->getFrameWork("squid.php?rotateevents=&rp=$rp");
		if($GLOBALS["VERBOSE"]){echo "Reading $export<br>\n";}
		$datas=explode("\n",@file_get_contents($export));
		$total=count($datas);
	}
	
		
	$pageStart = ($page-1)*$rp;
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){
		if($_POST["sortname"]=="zDate"){
			if($_POST["sortorder"]=="desc"){
				$sorted=true;
				krsort($datas);
			}
		}
	$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(!$sorted){krsort($datas);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(count($datas)==0){
		json_error_show("no data",1);
	}
	$c=0;
	$tpl=new templates();
	$current=date("Y-m-d");
	foreach ($datas as $key=>$line){
		
		$date="&nbsp;";
		
		if(preg_match("#^(.+?)\s+\[#",$line,$re)){
			$line=str_replace($re[1],"",$line);
			$date=strtotime($re[1]);
			$datetext=$tpl->time_to_date($date,true);
		
			
		}
		if(preg_match("#You should probably remove#i", $line)){continue;}
		if(preg_match("#is ignored to keep splay#i", $line)){continue;}
		if(preg_match("#is a subnetwork of#i", $line)){continue;}
		if(preg_match("#violates HTTP#i", $line)){continue;}
		if(preg_match("#empty ACL#i", $line)){continue;}
		if(preg_match("#WARNING#i", $line)){$line="<span style='color:#f59c44'>$line</line>";}
		if(preg_match("#FATAL#i", $line)){$line="<span style='color:#680000'>$line</line>";}
		if(preg_match("#abnormally#i", $line)){$line="<span style='color:#680000'>$line</line>";}
		if(preg_match("#Reconfiguring#i", $line)){$line="<span style='color:#003D0D;font-weight:bold'>$line</line>";}
		if(preg_match("#Accepting HTTP#i", $line)){$line="<span style='color:#003D0D;font-weight:bold'>$line</line>";}
		if(preg_match("#Ready to serve requests#i", $line)){$line="<span style='color:#003D0D;font-weight:bold'>$line</line>";}
		$c++;
		$data['rows'][] = array(
			'id' => md5($line),
			'cell' => array(
					"<span style='font-size:16px'>$datetext</span>", "<span style='font-size:16px'>$line</span>")
		);
	}
	
	$data['total']=$c;
	echo json_encode($data);	
}


