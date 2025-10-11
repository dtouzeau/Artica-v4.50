<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.postgres.inc');
include_once(dirname(__FILE__)."/ressources/class.influx.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");


if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["search"])){search();exit;}

js();


function js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{this_hour}: {$_GET["domain"]}");
	$domain=urlencode($_GET["domain"]);
	echo "YahooWin5('720','$page?popup=yes&domain=$domain','$title')";

}

function popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$members=$tpl->_ENGINE_parse_body("{members}");
	$add_member=$tpl->_ENGINE_parse_body("{add_member}");
	
	$delete=$tpl->javascript_parse_text("{delete}");
	$aliases=$tpl->javascript_parse_text("{aliases}");
	$about2=$tpl->_ENGINE_parse_body("{about2}");
	$new_report=$tpl->javascript_parse_text("{new_report}");
	$report=$tpl->javascript_parse_text("{report}");
	
	$progress=$tpl->javascript_parse_text("{progress}");
	$size=$tpl->javascript_parse_text("{size}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$uid=$tpl->javascript_parse_text("{uid}");
	$q=new mysql_squid_builder();
	$mac=$tpl->javascript_parse_text("{MAC}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$websites=$tpl->javascript_parse_text("{websites}");
	$categories=$tpl->javascript_parse_text("{categories}");
	$TIMES_SLOT["day"]="{this_day}";
	$TIMES_SLOT["hour"]="{this_hour}";
	$TIMES_SLOT["week"]="{this_week}";
	$TIMES_SLOT["month"]="{this_month}";
	$title=$tpl->javascript_parse_text("{this_hour}: {$_GET["domain"]}");
	$domain=urlencode($_GET["domain"]);
	
	$t=time();
	$buttons="
	buttons : [
	{name: '<strong style=font-size:16px>$uid</strong>', bclass: 'link', onpress : GoToUID$t},
	{name: '<strong style=font-size:16px>$mac</strong>', bclass: 'link', onpress : GotoMAC$t},
	{name: '<strong style=font-size:16px>$ipaddr</strong>', bclass: 'link', onpress : GotoIPADDR$t},
	],";

	$buttons=null;
	
	$html="
	<table class='RTT$t' style='display: none' id='RTT$t' style='width:100%'></table>
	<script>
$(document).ready(function(){
	$('#RTT$t').flexigrid({
	url: '$page?search=yes&domain=$domain',
	dataType: 'json',
	colModel : [
	{display: '<strong style=font-size:18px>$uid</strong>', name : 'userid', width : 150, sortable : true, align: 'left'},
	{display: '<strong style=font-size:18px>$mac</strong>', name : 'mac', width : 150, sortable : true, align: 'left'},
	{display: '<strong style=font-size:18px>$ipaddr</strong>', name : 'ipaddr', width : 150, sortable : true, align: 'left'},
	{display: '<strong style=font-size:18px>$size</strong>', name : 'size', width : 150, sortable : true, align: 'right'},
	],
	$buttons
	searchitems : [
	{display: '$uid', name : 'userid'},
	{display: '$mac', name : 'mac'},
	{display: '$ipaddr', name : 'ipaddr'},
	],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:26px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: '500',
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
});

function GoToUID$t(){
$('#RTT$t').flexOptions({url: '$page?search=yes&timeslot={$_GET["timeslot"]}'}).flexReload();

}
function GotoMAC$t(){
$('#RTT$t').flexOptions({url: '$page?search=yes&timeslot={$_GET["timeslot"]}&SUBDIR=mac'}).flexReload();
}
function GotoIPADDR$t(){
$('#RTT$t').flexOptions({url: '$page?search=yes&timeslot={$_GET["timeslot"]}&SUBDIR=ipaddr'}).flexReload();
}
function GotoWEBS$t(){
$('#RTT$t').flexOptions({url: '$page?search=yes&timeslot={$_GET["timeslot"]}&SUBDIR=WEBS'}).flexReload();
}
function GotoCATS$t(){
$('#RTT$t').flexOptions({url: '$page?search=yes&timeslot={$_GET["timeslot"]}&SUBDIR=CATS'}).flexReload();
}

</script>
";	
	
	echo $html;
}

function search(){
	$base="/home/squid/rttsize";
	$YEAR=date("Y");
	$MONTH=intval(date("m"));
	$DAY=intval(date("d"));
	$HOUR=intval(date("H"));
	$q=new postgres_sql();

	
	$curtime=date("YmdH");
	$tablename="access_$curtime";
	
	
	
	$total=0;
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	$table="(SELECT SUM(size) as size,SUM(rqs) as rqs,userid,mac,ipaddr FROM $tablename WHERE familysite='{$_GET["domain"]}' GROUP BY userid,mac,ipaddr) as t";
	$searchstring=string_to_flexPostGresquery();
	$sql="SELECT COUNT(*) as tcount FROM $table WHERE $searchstring";
	$ligne=pg_fetch_assoc($q->QUERY_SQL($sql));
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",0);}
	$total = $ligne["tcount"];
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=100;}
	
	
	$pageStart = ($page-1)*$rp;
	if($pageStart<0){$pageStart=0;}
	$limitSql = "LIMIT $rp OFFSET $pageStart";
	
	$sql="SELECT *  FROM $table WHERE $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",0);}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	$CurrentPage=CurrentPageName();
	
	if(pg_num_rows($results)==0){json_error_show("no data");}
	$ClassQ=new mysql_squid_builder();
	$fontsize="16px";
	$span="<span style='font-size:{$fontsize}'>";
	$IPTCP=new IP();
	while ($ligne = pg_fetch_assoc($results)) {
		$userid=$ligne["userid"];
		$mac=$ligne["mac"];
		$ipaddr=$ligne["ipaddr"];
		$size=FormatBytes($ligne["size"]/1024);
		if($userid==null){$userid="Unknown";}
		
			
		
		$mac_encoded=urlencode($mac);
		
		if($userid==null){
			$userid=$ClassQ->MacToUid($mac);
			if($userid<>null){$member_assoc="&nbsp; ($userid)";}
		}
		$ahrefMAC="<a href=\"javascript:blur();\"
			OnClick=\"Loadjs('squid.nodes.php?node-infos-js=yes&MAC=$mac_encoded');\"
			style='font-size:$fontsize;text-decoration:underline'>";
	

		$data['rows'][] = array(
				'id' => serialize($ligne),
				'cell' => array(
						"$span$ahref$userid</a></span>",
						"$span$ahrefMAC$mac</a>$member_assoc</span>",
						"$span$ahref$ipaddr</a></span>",
						"$span$size</a></span>",
	
				)
		);
	
	}

	echo json_encode($data);	
	
	
	
}

