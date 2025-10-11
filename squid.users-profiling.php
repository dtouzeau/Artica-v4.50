<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.mysql-meta.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');


$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die("DIE " .__FILE__." Line: ".__LINE__);

}


if(isset($_GET["popup"])){page();exit;}
if(isset($_GET["search"])){search();exit;}

tabs();

function tabs(){
	$sock=new sockets();
	$fontsize=16;
	$tpl=new templates();
	$page=CurrentPageName();
	
	
	$date=date("Ym");
	$table="{$date}_maccess";
	
	
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($table)){
		echo FATAL_ERROR_SHOW_128("{no_table_see_support}");
		return;
	}
	
	
	$array["popup"]='{members_this_month}';
	
	
	
	
	foreach ($array as $num=>$ligne){
	
		if($num=="parameters"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.accesslogs.params.php?parameters=yes\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}

		if($num=="schedule"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.databases.schedules.php?TaskType=54\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "squid_users_profiling",1100)."<script>LeftDesign('user-stats-256.png');</script>";
	
}

function page(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$member=$tpl->javascript_parse_text("{member}");
	$MAC=$tpl->javascript_parse_text("{MAC}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$load=$tpl->javascript_parse_text("{load}");
	$version=$tpl->javascript_parse_text("{version}");
	$filename=$tpl->javascript_parse_text("{filename}");
	$status=$tpl->javascript_parse_text("{status}");
	$events=$tpl->javascript_parse_text("{events}");
	$global_whitelist=$tpl->javascript_parse_text("{whitelist} (Meta)");
	$policies=$tpl->javascript_parse_text("{policies}");
	$orders=$tpl->javascript_parse_text("{orders}");
	$type=$tpl->javascript_parse_text("{type}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$size=$tpl->javascript_parse_text("{size}");
	$link_all_hosts_ask=$tpl->javascript_parse_text("{link_all_hosts_ask}");
	$policies=$tpl->javascript_parse_text("{policies}");
	$tag=$tpl->javascript_parse_text("{tag}");
	$synchronize=$tpl->javascript_parse_text("{synchronize}");
	$synchronize_policies_explain=$tpl->javascript_parse_text("{synchronize_policies_explain}");
	$t=time();
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	$categorysize=387;
	$date=$tpl->javascript_parse_text("{date}");
	$title=$tpl->javascript_parse_text("{members}:: {web_statistics}:: {this_month}");
	
	$buttons="
	buttons : [
	{name: '$link_host', bclass: 'add', onpress : LinkHosts$t},
	{name: '$synchronize', bclass: 'ScanNet', onpress : Orders$t},
	],";
	$buttons=null;
	
//	$q=new mysql();
	//$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT SUM(size) as size FROM backuped_logs","artica_events"));
	
$html="
<table class='SQUID_USERS_PROFILE_TABLE' style='display: none' id='SQUID_USERS_PROFILE_TABLE' style='width:1200px'></table>
<script>
$(document).ready(function(){
	$('#SQUID_USERS_PROFILE_TABLE').flexigrid({
			url: '$page?search=yes',
			dataType: 'json',
			colModel : [
			{display: '$member', name : 'uid', width : 437, sortable : true, align: 'left'},
			{display: '$MAC', name : 'MAC', width : 151, sortable : true, align: 'left'},
			{display: '$ipaddr', name : 'ipaddr', width : 151, sortable : true, align: 'right'},
			{display: '$size', name : 'size', width : 124, sortable : true, align: 'right'},
			{display: '$hits', name : 'hits', width : 124, sortable : true, align: 'right'},
		],
			$buttons
			searchitems : [
			{display: '$member', name : 'zDate'},
			{display: '$MAC', name : 'path'},
			{display: '$ipaddr', name : 'path'},
			
	
	
			],
			sortname: 'size',
			sortorder: 'desc',
			usepager: true,
			title: '<strong style=font-size:22px>$title</strong>',
			useRp: true,
			rpOptions: [10, 20, 30, 50,100,200],
			rp:50,
			showTableToggleBtn: false,
			width: '99%',
			height: 400,
			singleSelect: true
	
	});
	});
	
	function LinkHosts$t(){
	Loadjs('artica-meta.policies.php?function=LinkEdHosts$t');
	}
	
	var xLinkEdHosts$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){ alert(res); return; }
	$('#ARTICA_SOURCE_LOGS_TABLE').flexReload();
	
	}
	
	
	function LinkEdHosts$t(policyid){
	var XHR = new XHRConnection();
	XHR.appendData('link-policy',policyid);
	XHR.appendData('gpid','{$_GET["ID"]}');
	XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
	}
	
	function LinkHostsAll$t(){
	if(!confirm('$link_all_hosts_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('link-all','{$_GET["ID"]}');
			XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
	}
	
			function Orders$t(){
			if(!confirm('$synchronize_policies_explain')){return;}
			var XHR = new XHRConnection();
			XHR.appendData('synchronize-group','{$_GET["ID"]}');
			XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
	}
	
	</script>";
	echo $html;
}	
	
function search(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	
	$date=date("Ym");
	$table="(SELECT SUM(hits) as hits,SUM(size) as size,uid,ipaddr,MAC FROM {$date}_maccess GROUP BY uid,ipaddr,MAC) as t";
	$searchstring=string_to_flexquery();
	$page=1;
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){ $ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}"; }}
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
	$total = $ligne["tcount"];
	
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}
	
	
	if(mysqli_num_rows($results)==0){json_error_show("no data",1);}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	$fontsize="18";
	$style=" style='font-size:{$fontsize}px'";
	$styleHref=" style='font-size:{$fontsize}px;text-decoration:underline'";
	$free_text=$tpl->javascript_parse_text("{free}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$overloaded_text=$tpl->javascript_parse_text("{overloaded}");
	$orders_text=$tpl->javascript_parse_text("{orders}");
	$directories_monitor=$tpl->javascript_parse_text("{directories_monitor}");
	
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$LOGSWHY=array();
		
		$uid=$ligne["uid"];
		$MAC=$ligne["MAC"];
		$ipaddr=$ligne["ipaddr"];
		$size=FormatBytes($ligne["size"]/1024);
		$hits=FormatNumber($ligne["hits"]);
		
		$MAC_FILTER="Loadjs('squid.users-stats.currentmonth.php?field=MAC&value=".urlencode($MAC)."')";
		$UID_FILTER="Loadjs('squid.users-stats.currentmonth.php?field=uid&value=".urlencode($uid)."')";
		$IPADDR_FILTER="Loadjs('squid.users-stats.currentmonth.php?field=ipaddr&value=".urlencode($ipaddr)."')";
		
		
		$MAC_FILTERlink="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:$MAC_FILTER\" $styleHref>";
		

		$UID_FILTERlink="<a href=\"javascript:blur();\"
		OnClick=\"javascript:$UID_FILTER\" $styleHref>";

		$IPADDR_FILTERlink="<a href=\"javascript:blur();\"
		OnClick=\"javascript:$IPADDR_FILTER\" $styleHref>";		
		
		
		$cell=array();
		$cell[]="<span $style>$UID_FILTERlink$uid</a></span>";
		$cell[]="<span $style>$MAC_FILTERlink$MAC</a></span>";
		$cell[]="<span $style>$IPADDR_FILTERlink$ipaddr</a></span>";
		$cell[]="<span $style>$size</a></span>";
		$cell[]="<span $style>$hits</a></span>";
		
	
		$data['rows'][] = array(
				'id' => $ligne['zmd5'],
				'cell' => $cell
		);
	}
	
	
	echo json_encode($data);
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}

