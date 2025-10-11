<?php
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

if(isset($_GET["popup"])){events_table();exit;}
if(isset($_GET["rows-table"])){rows_table();exit;}

js();

function js(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$ID'"));
	$title=$tpl->_ENGINE_parse_body("{proxy_objects}::{$ligne["GroupName"]}::{events}");
	$html="YahooWin3('814','$page?popup=yes&ID=$ID','$title')";
	echo $html;
}

function events_table(){

	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$description=$tpl->_ENGINE_parse_body("{description}");
	$zDate=$tpl->_ENGINE_parse_body("{zDate}");
	$TB_HEIGHT=450;
	$TABLE_WIDTH=800;
	$TB2_WIDTH=400;
	$ROW1_WIDTH=157;
	$ROW2_WIDTH=607;
	$t=time();

	$buttons="
	buttons : [
	{name: '$empty', bclass: 'Delz', onpress : EmptyEvents},

	],	";
	
	$buttons=null;
	
	
	$html="
	<table class='node-table-$t' style='display: none' id='node-table-$t' style='width:99%'></table>
	<script>

	$(document).ready(function(){
	$('#node-table-$t').flexigrid({
	url: '$page?rows-table=yes&ID={$_GET["ID"]}',
	dataType: 'json',
	colModel : [
	{display: '$zDate', name : 'zDate', width :118, sortable : true, align: 'left'},
	{display: 'PID', name : 'zDate', width :42, sortable : true, align: 'center'},
	{display: '$description', name : 'line', width :583, sortable : true, align: 'left'},
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


	$content=unserialize(base64_decode($sock->getFrameWork("squid.php?watchdog-auth=yes&rp=$rp&ID={$_GET["ID"]}")));

	$c=0;

	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();
	krsort($content);
	while (list ($num, $ligne) = each ($content) ){
		$color="black";

		if(preg_match("#^(.+?)\s+(.*?)\s+\[([0-9]+)\](.*?)$#", $ligne,$re)){
			$date=$re[1]." ".$re[2];
			$pid=$re[3];
			$ligne=$re[4];
		}
		$ligne=str_replace("\n", "<br>", $ligne);
		$ligne=$tpl->javascript_parse_text("$ligne");
		if($search<>null){if(!preg_match("#$search#i", $ligne)){continue;}}
		$c++;
		$data['rows'][] = array(
				'id' => md5($ligne),
				'cell' => array(
						"<span style='font-size:12px;color:$color'>$date</span>",
						"<span style='font-size:12px;color:$color'>$pid</span>",
						"<span style='font-size:12px;color:$color'>$ligne</span>",
							

				)
		);
	}

	$data['total'] =$c;
	echo json_encode($data);

}