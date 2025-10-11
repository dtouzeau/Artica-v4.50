<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.samba.inc');


	
	
	$user=new usersMenus();
	if($user->AsAnAdministratorGeneric==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["query"])){query();exit;}
	
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	
	
	$title=$tpl->_ENGINE_parse_body("{browse}::{domains}::");
	echo "LoadWinORG('550','$page?popup=yes&callback={$_GET["callback"]}','$title');";	
	
	
	
}



function popup(){
	$page=CurrentPageName();
	$tpl=new templates();		
	
	$page=CurrentPageName();
	$tpl=new templates();
	$domains=$tpl->_ENGINE_parse_body("{domains}");
	$organization=$tpl->_ENGINE_parse_body("{organization}");
	
	$bt_default_www="{name: '$add_default_www', bclass: 'add', onpress : FreeWebAddDefaultVirtualHost},";
	$bt_webdav="{name: '$WebDavPerUser', bclass: 'add', onpress : FreeWebWebDavPerUsers},";
	//$bt_rebuild="{name: '$rebuild_items', bclass: 'Reconf', onpress : RebuildFreeweb},";
	$bt_help="{name: '$help', bclass: 'Help', onpress : HelpSection},";					

	
	
		
	$t=time();
	
	$buttons="
	buttons : [
	{name: '<b>$new_server</b>', bclass: 'add', onpress : $bt_function_add},
	
		],";
	
	$buttons=null;
	
	$html="
	<table class='domains-table-$t' style='display: none' id='domains-table-$t' style='width:100%;margin:-10px'></table>
<script>
FreeWebIDMEM='';
$(document).ready(function(){
$('#domains-table-$t').flexigrid({
	url: '$page?query=yes&t=$t&callback={$_GET["callback"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'domains', width : 50, sortable : false, align: 'center'},
		{display: '$domains', name : 'domains', width : 444, sortable : true, align: 'left'},
		
	],
	$buttons

	searchitems : [
		{display: '$domains', name : 'domains'},
		],
	sortname: 'domains',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: 533,
	height: 300,
	singleSelect: true
	
	});   
});

	
</script>";
	
	echo $html;	
	
	
	
	
	
}

function query(){
	$ou=null;
	$ldap=new clladp();
	$users=new usersMenus();
	if(!$users->AsArticaAdministrator){$ou=$_SESSION["ou"];}
	if($ou==null){$domains=$ldap->hash_get_all_domains();}else{$domains=$ldap->hash_get_domains_ou($ou);}
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = $total;
	$data['rows'] = array();	
	
	
	
	$c=0;
	while (list ($num, $ligne) = each ($domains) ){
		if($num==null){continue;}
		
		
		if($_POST["query"]<>null){
			$qq=$_POST["query"];
			$qq=str_replace(".", "\.", $qq);
			$qq=str_replace("*", ".*?", $qq);
			if(!preg_match("#$qq#", $num)){continue;}
		}
		$c++;
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:{$_GET["callback"]}('$num');WinORGHide();\" style='text-decoration:underline'>";
		
		
		$data['rows'][] = array(
				'id' => $num,
				'cell' => array(
					"<img src='img/domain-32.png'>",
					"<strong style='font-size:16px;style='color:black'>$href$num</a></strong>",
					)
				);			
		
	}
	$data['total'] = $c;
	echo json_encode($data);	
	
}


