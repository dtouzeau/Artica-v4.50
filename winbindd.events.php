<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	
	

if(!WinbindGetPrivs()){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die("DIE " .__FILE__." Line: ".__LINE__);	
}

if(isset($_GET["rows-table"])){rows_table();exit;}
if(isset($_GET["table"])){events_table();exit;}


js();



function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_SAMBA_WINBIND}");
	echo "YahooWin5('890','$page?table=yes','$title')";
	
}

function events_table(){

	$page=CurrentPageName();
	$tpl=new templates();

	$description=$tpl->_ENGINE_parse_body("{description}");
	$zDate=$tpl->_ENGINE_parse_body("{zDate}");
	$TB_HEIGHT=450;
	$TABLE_WIDTH=876;
	$TB2_WIDTH=400;
	$ROW1_WIDTH=157;
	$ROW2_WIDTH=607;


	$t=time();

	$buttons="
	buttons : [
	{name: '$empty', bclass: 'Delz', onpress : EmptyEvents},

	],	";
	$html="
	<table class='node-table-$t' style='display: none' id='node-table-$t' style='width:99%'></table>
	<script>

	$(document).ready(function(){
	$('#node-table-$t').flexigrid({
	url: '$page?rows-table=yes&nodeid={$_GET["nodeid"]}',
	dataType: 'json',
	colModel : [
	{display: '$zDate', name : 'zDate', width :118, sortable : true, align: 'left'},
	{display: '$description', name : 'line', width :712, sortable : true, align: 'left'},
	],

	searchitems : [
	{display: '$description', name : 'line'},
	],

	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TABLE_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true

});
});



</script>";

	echo $html;

}

function rows_table(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	$search=string_to_flexregex();

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}


	$content=unserialize(base64_decode($sock->getFrameWork("samba.php?winbindd-logs=yes&rp=$rp")));

	$c=0;

	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();
	krsort($content);
	while (list ($num, $ligne) = each ($content) ){
		$color="black";

		
		
		if(preg_match("#\[([0-9\/]+)\s+([0-9:\.]+),.*?\]\s+(.+)#", $ligne,$re)){
			$date=$re[1]." ".$re[2];
			$ligne=$re[3];
		}
		$ligne=str_replace("\n", "<br>", $ligne);

		if($search<>null){if(!preg_match("#$search#i", $ligne)){continue;}}
		$c++;
		$data['rows'][] = array(
				'id' => md5($ligne),
				'cell' => array(
						"<span style='font-size:12px;color:$color'>$date</span>",
						"<span style='font-size:12px;color:$color'>$ligne</span>",
							

				)
		);
	}

	$data['total'] =$c;
	echo json_encode($data);

}



function WinbindGetPrivs(){
	$usersmenus=new usersMenus();
	if($usersmenus->AsSquidAdministrator){return true;}
	if($usersmenus->AsSambaAdministrator){return true;}
	return false;
	
}
