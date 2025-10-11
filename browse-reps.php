<?php
session_start();
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.sockets.inc');
	include_once('ressources/class.samba.inc');
	include_once('ressources/class.nfs.inc');
	include_once('ressources/class.lvm.org.inc');
	include_once('ressources/class.user.inc');	
	include_once('ressources/class.crypt.php');	
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.auditd.inc');	


	 if(!IsRights()){
			$tpl=new templates();
			$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
			echo "alert('$error')";
			die("DIE " .__FILE__." Line: ".__LINE__);
		}
	
	if(isset($_POST["DeleteRoot"])){DeleteRoot();exit;}	
	if(isset($_POST["DeleteSingleFile"])){DeleteSingleFile();exit;}	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["events-table"])){table_list();exit;}

js();


function js(){
	if($_GET["root"]==null){$_GET["root"]=base64_encode("/");}
	$root=base64_decode($_GET["root"]);
	$title=$root;
	$page=CurrentPageName();
	echo "YahooLogWatcher('750','$page?popup=yes&root={$_GET["root"]}','$root');";
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$owner=$tpl->_ENGINE_parse_body("{owner}");
	$size=$tpl->_ENGINE_parse_body("{size}");	
	$file=$tpl->_ENGINE_parse_body("{file}");	
	$empty=$tpl->_ENGINE_parse_body("{remove_this_folder}");	
	$modified=$tpl->javascript_parse_text("{modified}");	
	$remove=$tpl->javascript_parse_text("{remove}");
	
	$TB_HEIGHT=450;
	$TB_WIDTH=550;
	$TB2_WIDTH=400;
	if(isset($_GET["full-size"])){
		$TB_WIDTH=872;
		$TB2_WIDTH=610;
	}
	
	if(isset($_GET["full-medium"])){
		$TB_WIDTH=845;
		$TB_HEIGHT=400;
		$TB2_WIDTH=580;
		
	}
	
	$t=time();
	$decryptedFolder=base64_decode($_GET["root"]);
	$buttons="
	buttons : [
	{name: '$empty', bclass: 'Delz', onpress : RemoveFolder},
	
		],	";
	

	
	
	$html="
	<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#events-table-$t').flexigrid({
	url: '$page?events-table=yes&root={$_GET["root"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none', width :31, sortable : true, align: 'center'},
		{display: '$file', name : 'file', width :357, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width : 55, sortable : false, align: 'left'},
		{display: '$owner', name : 'size', width : 55, sortable : false, align: 'left'},
		{display: '$modified', name : 'size', width : 114, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'size', width : 31, sortable : false, align: 'center'},
		
	],
	$buttons

	searchitems : [
		{display: '$file', name : 'text'},
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '$decryptedFolder',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 736,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,300]
	
	});   
});

	var x_RemoveFolder= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#events-table-$t').flexReload();
	}

function RemoveFolder(){
	if(confirm('$remove: $decryptedFolder ?')){
		var XHR = new XHRConnection();
		XHR.appendData('DeleteRoot','{$_GET["root"]}');
		XHR.sendAndLoad('$page', 'POST',x_RemoveFolder);	
		}
	}

	var x_DeleteSingleFile= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#row'+mem$t).remove();
		$('#events-table-$t').flexReload();
		//$('#grid_list').flexOptions({url: 'newurl/'}).flexReload(); 
		// $('#fgAllPatients').flexOptions({ query: 'blah=qweqweqwe' }).flexReload();
		
	}

function DeleteSingleFile(file,id){
	if(confirm('$remove: '+file+' ?')){
		mem$t=id;
		var XHR = new XHRConnection();
		XHR.appendData('DeleteSingleFile',file);
		XHR.appendData('root','{$_GET["root"]}');
		XHR.sendAndLoad('$page', 'POST',x_DeleteSingleFile);	
	
	}
}


</script>";

	echo $html;
	
	
}

function DeleteSingleFile(){
	$sock=new sockets();
	$fullpath=base64_decode($_POST["root"])."/".$_POST["DeleteSingleFile"];
	$fullpath=str_replace("//", "/", $fullpath);
	$sock->getFrameWork("cmd.php?file-remove=".base64_encode($fullpath));
	
}

function DeleteRoot(){
	$sock=new sockets();
	$datas=$sock->getFrameWork("cmd.php?folder-remove={$_POST["DeleteRoot"]}");
	if($datas<>null){
		$tpl=new templates();
		echo $tpl->javascript_parse_text($datas);
	}
}

function table_list(){
	//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	$page=1;
	$search=null;
	$tpl=new template_admin();
	$sock=new sockets();
	if($_POST["query"]<>null){$search=string_to_regex($_POST["query"]);}
	$datas=unserialize(base64_decode($sock->getFrameWork("cmd.php?Dir-Files={$_GET["root"]}&queryregex=".base64_encode($search))));
	
	ksort($datas);
	
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$pageStart = ($page-1)*$rp;
	$pageStop=$pageStart+$rp;
	$data = array();
	$data['page'] = $page;
	$data['total'] = count($datas);
	$data['rows'] = array();
	$dir=str_replace("../","",base64_decode($_GET["root"]));
	$dir=str_replace("//","/",$dir);
	$today=$tpl->_ENGINE_parse_body("{today}");
	
	$c=0;
    foreach ($datas as $num=>$val){
			if($pageStart>0){
				if($c<$pageStart){
					$c++;
					continue;
				}
			}
			
			$full_path=$tpl->utf8_encode($dir."/$num");
			$array=unserialize(base64_decode($sock->getFrameWork("cmd.php?filestat=". base64_encode($full_path))));
            if(!is_array($array)){$array=array();}
			$owner=$array["owner"]["owner"]["name"];
			if(date('Y',$array["time"]["mtime"])==date('Y')){$modified=date('M D d H:i:s',$array["time"]["mtime"]);}else{$modified=date('Y-m-d H:i',$array["time"]["mtime"]);}
			if(date('Y-m-d',$array["time"]["mtime"])==date('Y-m-d')){$modified="$today ".date('H:i:s',$array["time"]["mtime"]);}			
			
			$size=$array["size"]["size"];
			$ext=Get_extension($num);
			$img="img/ext/def_small.gif";
			if($ext<>null){if(isset($GLOBALS[$ext])){$img="img/ext/{$ext}_small.gif";}else{if(is_file("img/ext/{$ext}_small.gif")){$img="img/ext/{$ext}_small.gif";$GLOBALS[$ext]=true;}}}
				
			$size_new=FormatBytes($size/1024);
			$text_file=$num;
			if(is_dir($full_path)){$img="img/folder.gif";}
			if($size_new==0){$size_new=$size." bytes";}
			$id=md5("$num");	
			$delete=imgsimple("delete-24.png",null,"DeleteSingleFile('$num','$id')");
				
			if($_SESSION["uid"]<>-100){
				if($owner<>$_SESSION["uid"]){
					$delete="&nbsp;"; 
				}
			}
			

			$c++;	
			if($c>=$pageStop){break;}
			
			
		$data['rows'][] = array(
		'id' => $id,
		'cell' => array("<img src='$img'>",$text_file,$size_new,$owner,$modified,$delete)
		);
		
			
	}
	
	
	echo json_encode($data);
}

		
		
function IsRights(){
		if(!is_object($GLOBALS["USERMENUS"])){$users=new usersMenus();$GLOBALS["USERMENUS"]=$users;}else{$users=$GLOBALS["USERMENUS"];}
		$users=new usersMenus();
		if($users->AsArticaAdministrator){return true;}
		if($users->AsSambaAdministrator){return true;}
		if($users->AsSystemAdministrator){return true;}
		return true;
}

function isAnUser(){
		if(!is_object($GLOBALS["USERMENUS"])){$users=new usersMenus();$GLOBALS["USERMENUS"]=$users;}else{$users=$GLOBALS["USERMENUS"];}
		if($users->AsArticaAdministrator){return false;}
		if($users->AsSambaAdministrator){return false;}
		if($users->AsSystemAdministrator){return false;}
		return true;	
}		