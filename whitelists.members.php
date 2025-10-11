<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.spamassassin.inc');


session_start();
$ldap=new clladp();


	
	if(!GetRightsEmail()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup-global-white"])){table();exit; }
	if(isset($_GET["popup-global-black"])){table(1);exit; }
	if(isset($_GET["search"])){search();exit;}
	if(isset($_GET["newitem-js"])){new_itemjs();exit;}
	if(isset($_POST["delete"])){delete();exit;}
	if(isset($_POST["black"])){save();exit;}
js();	

	
function GetRightsEmail(){
	$uid=$_REQUEST["uid"];
	if($_SESSION["uid"]==$uid){return true;}
	$user=new usersMenus();
	if($user->AsPostfixAdministrator){return true;}
	
	
}
function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{blackandwhite_list}");
	$uid=urlencode($_GET["uid"]);
	echo "YahooWin5(750,'$page?tabs=yes&uid=$uid','{$_GET["uid"]}:$title');";
	
}

function new_itemjs(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$explain=$tpl->javascript_parse_text("{wbl_howto}",1);
	$t=time();
	$page=CurrentPageName();
	echo"
var xStart$t=function(obj){
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		$('#table-{$_GET["t"]}').flexReload();
	}
	
function Start$t(){
		email=prompt('$explain');
		if(!email){return;}
		var XHR = new XHRConnection();
		XHR.appendData('uid','{$_GET["uid"]}');
		XHR.appendData('black','{$_GET["black"]}');
		XHR.appendData('email',email);
		XHR.sendAndLoad('$page', 'POST',xStart$t);
	}
	
Start$t();
	";
	
}

function save(){
	$ct=new user($_POST["uid"]);
	
	if(!preg_match("#@.+#", $_POST["email"])){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{wbl_howto}",1);
		return;
	}
	
	if($_POST["black"]==1){
		if(!$ct->add_blacklist($_POST["email"])){echo $ct->ldap_error;}
		return;
	}
	
	
	if(!$ct->add_whitelist($_POST["email"])){echo $ct->ldap_error;}
	
	
}


function tabs(){
	
	$uid=urlencode($_GET["uid"]);
	$array["popup-global-white"]="{white list}";
	$array["popup-global-black"]="{black list}";
	$tpl=new templates();
	$page=CurrentPageName();
	foreach ($array as $num=>$ligne){

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&uid=$uid\" style='font-size:18px'><span>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "main_config_persowbl");
}


function table($black=0){
	$ldap=new clladp();
	$page=CurrentPageName();
	$tpl=new templates();
	if(!is_numeric($black)){$black=0;}
	$t=time();
	$new_item=$tpl->javascript_parse_text("{new_item}");
	$from=$tpl->javascript_parse_text("{sender}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$uidenc=urlencode($_GET["uid"]);
	$subtitle=$tpl->javascript_parse_text("{black list}");
	if($black==0){$subtitle=$tpl->javascript_parse_text("{white list}");}
	
	$html="
<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
	var mem_$t='';
	var selected_id=0;
	$(document).ready(function(){
	$('#table-$t').flexigrid({
	url: '$page?search=yes&uid=$uidenc&black=$black&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$from', name : 'type', width : 518, sortable : false, align: 'left'},
	{display: '$delete', name : 'from', width : 90, sortable : true, align: 'center'},
	
	
	
	],
	buttons : [
	{name: '$new_item', bclass: 'add', onpress : newitem$t},
	
	],
	searchitems : [
	{display: '$from', name : 'from'},
	],
	sortname: 'category',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:16px>{$_GET["uid"]} $subtitle</span>',
	useRp: false,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true
	
	});
});
	
	
	
	function newitem$t(){
		Loadjs('$page?newitem-js=yes&uid=$uidenc&black=$black&t=$t');
	
	}

	
	var x_AddwlCallback$t=function(obj){
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		$('#row'+mem_$t).remove();
	}

	
	
	function DeleteBlackList$t(from,md){
		var XHR = new XHRConnection();
		mem_$t=md;
		wbl=0;
		XHR.appendData('delete',from);
		XHR.appendData('type',$black);
		XHR.appendData('uid','{$_GET["uid"]}');
		XHR.sendAndLoad('$page', 'POST',x_AddwlCallback$t);
	}

	</script>
	";
	
	echo $html;
}

function delete(){
	$ct=new user($_POST["uid"]);
	if($_POST["type"]==1){
		$ct->del_blacklist($_POST["delete"]);
		return;
	}
	
	$ct->del_whitelist($_POST["delete"]);
}

function search(){
	$uid=$_GET["uid"];
	$t=$_GET["t"];
	$ct=new user($uid);
	if($_GET["black"]==1){
		$array=$ct->amavisBlacklistSender;
	}else{
		$array=$ct->amavisWhitelistSender;
	}
	
	$number=count($array);
	if($number==0){json_error_show("no item");}
	
	if(!is_numeric($_POST["page"])){$_POST["page"]=1;}
	
	$data = array();
	$data['page'] = $_POST["page"];
	$data['total'] = $number;
	$data['rows'] = array();
	$color="black";
	$start=0;
	if($_POST["page"]>1){
		$start=$_POST["page"]*$_POST["rp"];
	}
	$a=0;
	$tofind=$_POST["query"];
	$tofind=str_replace(".", "\.", $tofind);
	$tofind=str_replace("*", ".*?", $tofind);
	
	
	$c=0;
	while (list ($email, $none) = each ($array)){
		$a++;
		if($start>0){if($a<$start){continue;}}
		if($c>$_POST["rp"]){break;}
		if($tofind<>null){if(!preg_match("#$tofind#",$email)){continue;}}
		$id=md5($email);
		$delete=imgsimple("delete-32.png",null,"DeleteBlackList$t('{$email}','$id')");
		$c++;
		
		$data['rows'][] = array(
				'id' => $id,
				'cell' => array(
						"<span style='font-size:22px;color:$color'>{$email}</span>",
						"<span style='font-size:22px;color:$color'>$delete</span>",
				)
		);		
		
	}
	

	echo json_encode($data);
	
	
	
	
}

