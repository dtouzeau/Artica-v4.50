<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once("ressources/class.os.system.inc");
	include_once("ressources/class.lvm.org.inc");
	include_once("ressources/class.autofs.inc");
	
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){echo "alert('no privileges');";die("DIE " .__FILE__." Line: ".__LINE__);}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["uuid"])){subdisk();exit;}
	if(isset($_GET["getlist"])){popup_list();exit;}
	if(isset($_GET["show-devices-js"])){show_devices_js();exit;}
	tabs();
	
	
function show_devices_js(){
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{devices}");
	$uuid=$_GET["show-devices-js"];
	
	$disks=unserialize(base64_decode($sock->getFrameWork("btrfs.php?btrfs-scan=yes")));
	echo "YahooWin2('440','$page?popup=yes&uuid=$uuid','{$disks[$uuid]["LABEL"]}:$title')";
}


function popup(){
	$sock=new sockets();
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$label=$tpl->_ENGINE_parse_body("{label}");
	$dev=$tpl->_ENGINE_parse_body("{source}");
	$used=$tpl->_ENGINE_parse_body("{used}");
	$mounted=$tpl->_ENGINE_parse_body("{mounted}");
	$devices=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$uuid=$_GET["uuid"];
	$disks=unserialize(base64_decode($sock->getFrameWork("btrfs.php?btrfs-scan=yes")));
	$title=$tpl->javascript_parse_text("{devices}");
	//$array[$UUID]["DEVICES"]

	$TABLE_WIDTH=705;
	
	
	
$buttons="
		buttons : [
		{name: '$add_a_shared_folder', bclass: 'add', onpress : AddShared$t},
		{name: '$default_settings', bclass: 'Reconf', onpress : Defsets$t},
		
		],";	
$buttons=null;	
$html="

<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%;'></table>
<script>
var IDTMP=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?getlist=yes&uuid=$uuid',
	dataType: 'json',
	colModel : [
		{display: 'ID', name : 'label', width :67, sortable : false, align: 'left'},
		{display: '$size', name : 'label', width :54, sortable : false, align: 'left'},
		{display: '$dev', name : 'dev', width :128, sortable : true, align: 'left'},
		{display: '$used', name : 'used', width : 85, sortable : false, align: 'left'},
		],
	$buttons

	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: 'BtrFS {$disks[$uuid]["LABEL"]}:$title',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function btrfsSubdisk(uuid){
	LoadAjax('BRTFS_TABLE2','$page?uuid='+uuid);
}
function Defsets$t(){
	Loadjs('samba.default.settings.php');
}



Loadjs('js/samba.js');
</script>	
";
	
	echo $html;
	
}

function popup_list(){
	
	$tpl=new templates();
	$sock=new sockets();
	$disks=unserialize(base64_decode($sock->getFrameWork("btrfs.php?btrfs-scan=yes")));
	$MyPage=CurrentPageName();
	
	
	if($_POST["query"]<>null){
		$_POST["query"]=str_replace(".", "\.", $_POST["query"]);
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", ".*?", $_POST["query"]);
		$search=$_POST["query"];

	}
	$MAIN=$disks[$_GET["uuid"]]["DEVICES"];
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($MAIN);
	$data['rows'] = array();	
	
	$c=0;
	while (list ($index, $array) = each ($MAIN) ){	
		$md=md5(serialize($array));
		$SIZE=$array["SIZE"];
		$DEV=$array["DEV"];
		$USED=$array["USED"];
		$MOUNTED=$array["MOUNTED"];
		$DEVICES=count($array["DEVICES"]);
		$href="<a href=\"javascript:blur()\" style='font-size:14px;text-decoration:underline'>";
		$hrefdevices="<a href=\"javascript:blur()\" OnClick=\"Loadjs('$MyPage?show-devices-js=$uuid');\" style='font-size:18px;text-decoration:underline;font-weight:bold'>";

		$c++;
		$data['rows'][] = array(
		'id' => $md,
		'cell' => array(
		 "$href$index</a>",
		 "$href$SIZE</a>",
		 "$href$DEV</a>",
		 "$href$USED</a>",
		 "$hrefdevices$DEVICES</a>",	
		)
		);			
		
	}
	
	
	$data['total'] = $c;
	echo json_encode($data);		
	
}
