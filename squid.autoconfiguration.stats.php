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

popup();


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
	$q=new postgres_sql();

	
	$delete=$tpl->javascript_parse_text("{delete}");
	$aliases=$tpl->javascript_parse_text("{aliases}");
	$about2=$tpl->_ENGINE_parse_body("{about2}");
	$new_report=$tpl->javascript_parse_text("{new_report}");
	$report=$tpl->javascript_parse_text("{report}");
	$analyze=$tpl->javascript_parse_text("{analyze}");
	
	$purge=$tpl->javascript_parse_text("{purge}");
	$size=$tpl->javascript_parse_text("{size}");
	$date=$tpl->javascript_parse_text("{date}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$uid=$tpl->javascript_parse_text("{uid}");
	$q=new mysql_squid_builder();
	$mac=$tpl->javascript_parse_text("{MAC}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$categories=$tpl->javascript_parse_text("{categories}");
	$TIMES_SLOT["day"]="{this_day}";
	$TIMES_SLOT["hour"]="{this_hour}";
	$TIMES_SLOT["week"]="{this_week}";
	$TIMES_SLOT["month"]="{this_month}";
	$title=$tpl->javascript_parse_text("{wpad_service}: {statistics}");

	
	$t=time();
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$purge</strong>', bclass: 'Delz', onpress : Purge$t},
	{separator: true},
	{name: '<strong style=font-size:18px>Excel</strong>', bclass : 'excel', onpress : exportTo},
	{separator: true},
	{name: '<strong style=font-size:18px>CSV</strong>', bclass : 'csv', onpress : exportTo},
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
	{display: '<strong style=font-size:20px>$date</strong>', name : 'zdate', width : 299, sortable : true, align: 'left'},
	{display: '<strong style=font-size:20px>$ipaddr</strong>', name : 'ipaddr', width : 226, sortable : true, align: 'left'},
	{display: '<strong style=font-size:20px>UserAgent</strong>', name : 'useragent', width : 630, sortable : true, align: 'left'},
	{display: '<strong style=font-size:20px>$rule</strong>', name : 'rule', width : 110, sortable : true, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$ipaddr', name : 'ipaddr'},
	{display: 'UserAgent', name : 'useragent'},
	],
	sortname: 'zdate',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: '550',
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
});

function Purge$t(){
	Loadjs('squid.stored.objects.progress.php');

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
	$q=new postgres_sql();
	$total=0;
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$table="proxypac";
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
	$tpl=new templates();
	if(pg_num_rows($results)==0){json_error_show("no data");}
	$ClassQ=new mysql_squid_builder();
	$fontsize="18px";
	$span="<span style='font-size:{$fontsize}'>";
	$IPTCP=new IP();
	while ($ligne = pg_fetch_assoc($results)) {
		$ipaddr=$ligne["ipaddr"];
		$zdate=strtotime($ligne["zdate"]);
		$zdate=$tpl->time_to_date($zdate,true);
		$useragent=$ligne["useragent"];
		$rule=$ligne["rule"];

		$ahref="<a href=\"javascript:blur();\"
			OnClick=\"Loadjs('squid.autoconfiguration.main.php?rule-js=yes&ID=$rule');\"
			style='font-size:$fontsize;text-decoration:underline'>";
	

		$data['rows'][] = array(
				'id' => serialize($ligne),
				'cell' => array(
						"$span$ahref$zdate</a></span>",
						"$span$ahref$ipaddr</a></span>",
						"$span$ahref$useragent</a></span>",
						"$span$ahref$rule</a></span>",
	
				)
		);
	
	}

	echo json_encode($data);	
	
	
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
