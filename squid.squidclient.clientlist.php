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

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["items"])){items();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{display_current_sessions}");
	echo "YahooWin('750','$page?popup=yes','$title')";
	
	
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=450;
	$TB_WIDTH=720;
	
	
	$t=time();
	$new_entry=$tpl->_ENGINE_parse_body("{new_rule}");
	$client=$tpl->_ENGINE_parse_body("{client}");
	$uri=$tpl->_ENGINE_parse_body("{urls}");
	$title=null;
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$date=$tpl->javascript_parse_text("{date}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewGItem$t},
	{name: '$compile_rules', bclass: 'Reconf', onpress : AmavisCompileRules},
	{name: '$online_help', bclass: 'Help', onpress : ItemHelp$t},
	],	";
	
	$buttons=null;
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t',
	dataType: 'json',
	colModel : [	
		{display: '$client', name : 'client', width :83, sortable : true, align: 'left'},
		{display: '$uri', name : 'uri', width :418, sortable : false, align: 'left'},
		{display: '$date', name : 'action', width :163, sortable : false, align: 'left'},

	],
	$buttons

	searchitems : [
		{display: '$client', name : 'client'},
		{display: '$uri', name : 'uri'},

	],
	sortname: 'mailfrom',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

</script>";
	
	echo $html;
}

function items(){
	
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?squid-sessions=yes")));
	if(count($datas)==0){json_error_show("No data");}
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();	
			$c=0;
	while (list($num,$val)=each($datas)){
		
		if($val["USER"]==null){$val["USER"]=$val["CLIENT"];}else{
			$val["USER"]=$val["USER"]."<br><i style='font-size:11px'>{$val["CLIENT"]}</i>";
		}
		$c++;
	$data['rows'][] = array(

		'id' => "$num",
		'cell' => array(
			"<span style='font-size:12px;color:$color'>{$val["USER"]}</a></span>",
			"<span style='font-size:12px;color:$color'>{$val["URI"]}</a></span>",
			"<span style='font-size:12px;color:$color'>{$val["SINCE"]}</span>",
			)
		);
		
		
	}
	$data['total'] = $c;
	echo json_encode($data);	
	
}
	
