<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="ANTISPAM";
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.postgres.inc');

	$user=new usersMenus();
	if(isset($_GET["items"])){items();exit;}
	popup();
	





function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=300;
	$TB_WIDTH=880;
	$q=new postgres_sql();
	$q->ipaudit_table();
	
	
	
	$t=time();
	$new_entry=$tpl->_ENGINE_parse_body("{new_rule}");
	$from=$tpl->_ENGINE_parse_body("{source}");
	$to=$tpl->_ENGINE_parse_body("{destination}");
	$title=$tpl->_ENGINE_parse_body("&laquo;{APP_IPAUDIT}&raquo;");
	$protocol=$tpl->_ENGINE_parse_body("{protocol}");
	$ask_delete_rule=$tpl->javascript_parse_text("{delete_this_rule}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$zdate=$tpl->javascript_parse_text("{date}");
	$size=$tpl->javascript_parse_text("{size}");
	
	$sql="CREATE TABLE IF NOT EXISTS ipaudit (
		ip1 inet,
		ip2 inet,
		protocol BIGINT DEFAULT '0' NOT NULL,
		ip1port INT DEFAULT '0' NOT NULL,
		ip2port INT DEFAULT '0' NOT NULL,
		ip1bytes BIGINT DEFAULT '0' NOT NULL,
		ip2bytes BIGINT DEFAULT '0' NOT NULL,
		ip1pkts BIGINT DEFAULT '0' NOT NULL,
		ip2pkts BIGINT DEFAULT '0' NOT NULL,
		eth1 varchar(12) DEFAULT '' NOT NULL,
		eth2 varchar(12) DEFAULT '' NOT NULL,
		constartdate timestamp NOT NULL,
		constopdate timestamp NOT NULL,
		constartmsec BIGINT DEFAULT '0' NOT NULL,
		constopmsec BIGINT DEFAULT '0' NOT NULL,
		probename_g varchar(128) DEFAULT '' NOT NULL
		);";
	
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_entry</strong>', bclass: 'Add', onpress : NewGItem$t},
	
	
	],	";
	$buttons=null;
	
	$html="
	<table class='IPAUDIT_EVENTS_TABLE' style='display: none' id='IPAUDIT_EVENTS_TABLE' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#IPAUDIT_EVENTS_TABLE').flexigrid({
	url: '$page?items=yes&t=$t',
	dataType: 'json',
	colModel : [	
	
		{display: '<span style=font-size:18px>$zdate</span>', name : 'constartdate', width :158, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$from</span>', name : 'ip1', width :224, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$size</span>', name : 'ip1bytes', width :128, sortable : true, align: 'right'},
		{display: '<span style=font-size:18px>$to</span>', name : 'ip2', width :224, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$size</span>', name : 'ip2bytes', width :128, sortable : true, align: 'right'},
		{display: '<span style=font-size:18px>$protocol</span>', name : 'protocol', width :96, sortable : true, align: 'right'},
		
		


	],
	$buttons

	searchitems : [
		{display: '$from', name : 'ip1'},

	],
	sortname: 'constartdate',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function ItemHelp$t(){
	s_PopUpFull('http://www.mail-appliance.org/index.php?cID=305','1024','900');
}


var x_NewGItem$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
    $('#flexRT$t').flexReload();
}

function NewGItem$t(){
	YahooWin('650','$page?rulemd5=&t=$t','$new_entry');
	
}
function GItem$t(zmd5,ttile){
	YahooWin('650','$page?rulemd5='+zmd5+'&t=$t',ttile);
	
}

var x_DeleteAutCompress$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#rowC'+mem$t).remove();
}

function GroupAmavisExtEnable(id){
	var value=0;
	if(document.getElementById('gp'+id).checked){value=1;}
 	var XHR = new XHRConnection();
    XHR.appendData('enable-gp',id);
    XHR.appendData('value',value);
    XHR.sendAndLoad('$page', 'POST',x_NewGItem$t);		
}


function DeleteAutCompress$t(md5){
	if(confirm('$ask_delete_rule')){
		mem$t=md5;
 		var XHR = new XHRConnection();
      	XHR.appendData('del-zmd5',md5);
      	XHR.sendAndLoad('$page', 'POST',x_DeleteAutCompress$t);		
	
	}

}

</script>";
	
	echo $html;
}

function items(){
	//1.4.010916
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new postgres_sql();
	$users=new usersMenus();	
	
	$search='%';
	$table="ipaudit";
	$page=1;
	$FORCE_FILTER="";

	
	
	$protocols[1]="ICMP";
	$protocols[6]="TCP";
	$protocols[17]="UDP";


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexPostGresquery();
	$sql="SELECT COUNT(*) as tcount FROM $table WHERE $searchstring";
	$ligne=$q->mysqli_fetch_array($sql);
	$total = $ligne["tcount"];
		
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $rp OFFSET $pageStart";
	
	$sql="SELECT *  FROM $table WHERE $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$add=$tpl->javascript_parse_text("{add}");
	if(!$q->ok){json_error_show($q->mysql_error);}	
	if(pg_num_rows($results)==0){json_error_show("no rule");}

	
	
	while ($ligne = pg_fetch_assoc($results)) {
		$color="#000000";
		$zmd5=md5(serialize($ligne));
	
		$ip1bytes=$ligne["ip1bytes"];
		$ip2bytes=$ligne["ip2bytes"];
		$Unit1="bytes";
		if($ip1bytes>1024){
			$ip1bytes=FormatBytes($ip1bytes/1024);
			$Unit1=null;
		}
		$Unit2="bytes";
		if($ip2bytes>1024){
			$ip2bytes=FormatBytes($ip2bytes/1024);
			$Unit2=null;
		}	

	
	$data['rows'][] = array(
		'id' => "C$zmd5",
		'cell' => array(
			"<span style='font-size:14px;color:$color'>{$ligne["constartdate"]}</a></span>",
			"<span style='font-size:14px;color:$color'>{$ligne["ip1"]}:{$ligne["ip1port"]}</a></span>",
			"<span style='font-size:14px;color:$color'>{$ip1bytes} {$Unit1}</a></span>",
			"<span style='font-size:14px;color:$color'>{$ligne["ip2"]}:{$ligne["ip2port"]}</a></span>",
			"<span style='font-size:14px;color:$color'>{$ip2bytes} {$Unit2}</a></span>",
			"<span style='font-size:14px;color:$color'>{$protocols[$ligne["protocol"]]}</a></span>",
			
			
			)
		);
	}
	
	
echo json_encode($data);	
	
}







