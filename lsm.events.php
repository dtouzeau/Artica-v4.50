<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.ldap.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.openssh.inc');
include_once('ressources/class.user.inc');

$user=new usersMenus();
if($user->AsSystemAdministrator==false){
	$tpl=new templates();
	echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die("DIE " .__FILE__." Line: ".__LINE__);exit();
}

if(isset($_GET["rows-table"])){events_table();exit;}
events();
function events(){
	$tpl=new templates();
	$page=CurrentPageName();
	$events=$tpl->javascript_parse_text("{events}");
	$t=time();
	$html="
	<table class='node-table-$t' style='display: none' id='node-table-$t' style='width:99%'></table>
	<script>
	$(document).ready(function(){
	$('#node-table-$t').flexigrid({
	url: '$page?rows-table=yes&nodeid={$_GET["nodeid"]}',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'zdate', width :115, sortable : false, align: 'left'},
	{display: '<span style=font-size:22px>$events</span>', name : 'event', width :1290, sortable : true, align: 'left'},




	],

	sortname: '	ipaddr',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:30px>$events</strong>',
	useRp: false,
	rp: 50,
	showTableToggleBtn: true,
	width: '99%',
	height: 550,
	singleSelect: true

});
});
</script>";
echo $html;

}


function events_table(){
	$sock=new sockets();
	$targetfile="/usr/share/artica-postfix/ressources/logs/lsm.log.tmp";
	$sock->getFrameWork("lsm.php?events=yes&rp={$_POST["rp"]}&query=".urlencode($_POST["query"]));
	$rows=explode("\n",@file_get_contents($targetfile));
	@krsort($rows);
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($rows);
	$data['rows'] = array();
	$c=0;
	while (list ($num, $line) = each ($rows)){
		$line=trim($line);
		$color="black";
		$edit=null;
		$line=str_replace("#012", "", $line);
		$c++;
		if(preg_match("#No Profile configured#", $line)){continue;}
		
		
		if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.+?:(.+)#" , $line,$re)){
			$date="{$re[1]} {$re[2]} {$re[3]}";
			$line=trim($re[4]);
		}
		if(substr($line, 0,1)==":"){$line=substr($line, 1,strlen($line));}
		$md5=md5("$date$line");
		$line=htmlentities($line);
		if(preg_match("#(still down|state to down|Unreachable)#i", $line)){$color="#CC0A0A";}
		if(preg_match("#(state to up)#i", $line)){$color="#069900";}
		if(preg_match("#rule([0-9]+)#", $line,$re)){
			$edit="<a href=\"javascript:blur();\" OnClick=\"Loadjs('lsm.rules.php?js-popup=yes&ruleid={$re[1]}');\" style='color:$color;text-decoration:underline'>";
			$line=str_replace("rule{$re[1]}", "{$edit}rule id:{$re[1]}</a>", $line);
		}
		
		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(
						"<span style='font-size:13.5px;color:$color'>$date</span>",
						"<span style='font-size:13.5px;color:$color'>$line</span>",

				)
		);
	}

	if($c==0){json_error_show("no data");}
	$data['total'] =$c;
	echo json_encode($data);
}