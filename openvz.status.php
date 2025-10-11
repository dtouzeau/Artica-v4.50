<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
session_start();
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.cyrus.inc');
include_once('ressources/class.main_cf.inc');
include_once('ressources/charts.php');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');

//ini_set('display_errors', 1);
//ini_set('error_reporting', E_ALL);



$users=new usersMenus();
if(!$users->AsSystemAdministrator){exit;}

if(isset($_GET["items"])){items();exit;}

page();



function page(){
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();	
	$GLOBALS["ICON_FAMILY"]="COMPUTERS";
	$sock=new sockets();
	$users=new usersMenus();
	$scan_your_network=$tpl->_ENGINE_parse_body("{scan_your_network}");
	$edit_networks=$tpl->_ENGINE_parse_body("{edit_networks}");
	$ADD_COMPUTER=$tpl->_ENGINE_parse_body("{ADD_COMPUTER}");
	$periodic_scan=$tpl->_ENGINE_parse_body("{periodic_scan}");
	$findcomputer="{name: '$scan_your_network', bclass: 'ScanNet', onpress : ScanNet},";

	$networs="{name: '$edit_networks', bclass: 'Net', onpress : ViewNetwork},";
	$addComp="{name: '$ADD_COMPUTER', bclass: 'Add', onpress : AddCompz},";

	
	
	$add_computer_js="javascript:YahooUser(1051,'domains.edit.user.php?userid=newcomputer$&ajaxmode=yes','New computer');";

	
	
	$t=time();
	$title=$tpl->_ENGINE_parse_body('{browse_computers}')."::";
	$delete_database_ask=$tpl->_ENGINE_parse_body("{delete_database_ask}");
	$database=$tpl->_ENGINE_parse_body("{database}");
	$tables_number=$tpl->_ENGINE_parse_body("{tables_number}");
	$database_size=$tpl->_ENGINE_parse_body("{database_size}");	
	$perfrom_mysqlcheck=$tpl->javascript_parse_text("{perform_mysql_check}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");	
	$hostname=$tpl->javascript_parse_text("{hostname}");
	$OS=$tpl->javascript_parse_text("{OS}");
	$new_database="New database";
	$bt_default_www="{name: '$add_default_www', bclass: 'add', onpress : FreeWebAddDefaultVirtualHost},";
	$bt_webdav="{name: '$WebDavPerUser', bclass: 'add', onpress : FreeWebWebDavPerUsers},";
	//$bt_rebuild="{name: '$rebuild_items', bclass: 'Reconf', onpress : RebuildFreeweb},";
	$bt_config=",{name: '$config_file', bclass: 'Search', onpress : config_file}";	
	$tables_size=$tpl->_ENGINE_parse_body("{tables_size}");
	
	$TB_WIDTH=721;
	$hostname_width=243;
	$OS_width=232;
	
	
	

	$buttons="
	buttons : [
		
		$addComp$networs$findcomputer$ScanComputersNet
	
		],";
	$buttons=null;
	$html="
	<div style='margin-left:-10px'>
		<table class='beancounters' style='display: none' id='beancounters' style='width:100%;margin:-10px'></table>
	</div>
<script>

                                                                               
memedb='';
$(document).ready(function(){
$('#beancounters').flexigrid({
	url: '$page?items=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: 'resource', name : 'resource', width : 112, sortable : false, align: 'left'},
		{display: 'held', name : 'held', width :90, sortable : false, align: 'left'},
		{display: 'maxheld', name : 'maxheld', width :90, sortable : false, align: 'left'},
		{display: 'barrier', name : 'barrier', width : 151, sortable : false, align: 'left'},
		{display: 'limit', name : 'limit', width : 131, sortable : false, align: 'left'},
		{display: 'failcnt', name : 'failcnt', width : 51, sortable : false, align: 'center'},
	],
	
	$buttons

	searchitems : [
		{display: 'resource', name : 'resource'},
		
		],
	sortname: 'resource',
	sortorder: 'asc',
	usepager: true,
	title: 'beancounters',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: 423,
	singleSelect: true
	
	});   
});

</script>

";
echo $html;		

	
	
	
	
	
}


function items(){
	
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("services.php?beancounters=yes")));	
	
	
$res_bytes = "dgramrcvbuf|kmemsize|othersockbuf|tcprcvbuf|tcpsndbuf|";

$bytes["dgramrcvbuf"]=true;
$bytes["kmemsize"]=true;
$bytes["othersockbuf"]=true;
$bytes["tcprcvbuf"]=true;
$bytes["tcpsndbuf"]=true;

$pagesB["privvmpages"]=true;
$pagesB["vmguarpages"]=true;
$pagesB["shmpages"]=true;
if($_POST["query"]<>null){
	$search=string_to_regex($_POST["query"]);
}

	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();
	$c=0;
	foreach ($datas as $num=>$ligne){
		$ligne=str_replace("\r", "", $ligne);
		$ligne=str_replace("\n", "", $ligne);
		if(!preg_match("#.*?\s+([a-z]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)#", $ligne,$re)){continue;}
		$resource=$re[1];
		$held=$re[2];
		$maxheld=$re[3];
		$barrier=$re[4];
		$limit=$re[5];
		$failcnt=$re[6];
		if($search<>null){if(!preg_match("#$search#", $resource)){continue;}}
		
		if(isset($bytes[$resource])){
			$held=FormatBytes($held/1024);
			$maxheld=FormatBytes($maxheld/1024);
			$barrier=FormatBytes($barrier/1024);
			$limit=FormatBytes($limit/1024);
		}
		if(isset($pagesB[$resource])){
			$held=$held*4096;
			$maxheld=$maxheld*4096;
			$barrier=$barrier*4096;
			$limit=$limit*4096;
			$held=FormatBytes($held/1024);
			$maxheld=FormatBytes($maxheld/1024);
			$barrier=FormatBytes($barrier/1024);
			$limit=FormatBytes($limit/1024);			
			
		}
		$failcnt_color="black";
		if($failcnt>0){
			$failcnt_color="#D31B1B";
		}
		$c++;
				$data['rows'][] = array(
					'id' => md5($ligne),
					'cell' => array(
					 
					 "<span style='font-size:14px'>$resource</span>",
					 "<span style='font-size:14px'>$held</span>",
					 "<span style='font-size:14px'>$maxheld</span>",
					"<span style='font-size:14px'>$barrier</span>",
					"<span style='font-size:14px'>$limit</span>",
					"<span style='font-size:14px;color:$failcnt_color'>$failcnt</span>",
					
					 )
					);			
		
		
		
		
	}
	$data['total'] =$c;
echo json_encode($data);	
	
}