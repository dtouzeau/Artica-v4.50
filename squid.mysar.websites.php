<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}	
	if(isset($_GET["rows"])){rows();exit;}
	if(isset($_GET["change-day"])){change_day_popup();exit;}
	
	page();
	
	
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$member=$tpl->_ENGINE_parse_body("{member}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$sitename=$tpl->_ENGINE_parse_body("{sitename}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$day=$tpl->_ENGINE_parse_body("{day}");
	$week=$tpl->_ENGINE_parse_body("{week}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$hosts=$tpl->_ENGINE_parse_body("{hosts}");
	$cache=$tpl->_ENGINE_parse_body("{cached}");
	$websites=$tpl->_ENGINE_parse_body("{websites}");
	$TB_WIDTH=550;
	$t=time();
	if(!isset($_GET["day"])){$_GET["day"]=$q->HIER();}
	
	$buttons="
		buttons : [
		{name: '<b>$day</b>', bclass: 'Calendar', onpress : ChangeDay$t},
	
		],";
	
		$html="
		<table class='$t' style='display: none' id='$t' style='width:99%'></table>
<script>

function LoadTable$t(){
		$('#$t').flexigrid({
		url: '$page?rows=yes&day={$_GET["day"]}',
		dataType: 'json',
		colModel : [
		{display: '$websites', name : 'site', width : 490, sortable : true, true: 'left'},
		{display: '$members', name : 'users', width : 73, sortable : true, align: 'left'},
		{display: '$hosts', name : 'hosts', width : 57, sortable : true, align: 'left'},
		{display: '$size', name : 'hosts', width : 90, sortable : true, align: 'left'},
		{display: '$cache', name : 'cachePercent', width : 58, sortable : true, true: 'left'},
		
	
	
		],$buttons
		searchitems : [
		{display: '$websites', name : 'site'},	
		],
		sortname: 'bytes',
		sortorder: 'desc',
		usepager: true,
		title: '$websites&raquo;{$_GET["day"]}',
		useRp: true,
		rp: 15,
		showTableToggleBtn: false,
		width: 842,
		height: 450,
		singleSelect: true
	
	});
}
	
	function RefreshNodesSquidTbl(){
	$('#$t').flexReload();
	}
	
	function ChangeDay$t(){
		YahooWin(400,'$page?change-day=yes&t=$t','$day');
	}
	
	function ChangeWeek$t(){
		YahooWin(624,'$page?change-week=yes&t=$t','$week');
	}
	function ChangeMonth$t(){
		YahooWin(400,'$page?change-month=yes&t=$t','$month');
	}
	
	
	LoadTable$t()
	</script>";
	
	echo $html;
}
function change_day_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$t=$_GET["t"];
	$members=$tpl->_ENGINE_parse_body("{members}&raquo;");

	$day=$tpl->_ENGINE_parse_body("{day}");

	$sql="SELECT DATE_FORMAT(date,'%Y-%m-%d') as tdate FROM trafficSummaries ORDER BY date LIMIT 0,1";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	$mindate=$ligne["tdate"];

	$sql="SELECT DATE_FORMAT(date,'%Y-%m-%d') as tdate FROM trafficSummaries ORDER BY date DESC LIMIT 0,1";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	$maxdate=date('Y-m-d');

	$html="
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend nowrap style='font-size:16px'>{from_date}:</td>
		<td>". field_date("SdateMember-$t",$_GET["day"],"font-size:16px;padding:3px;width:95px","mindate:$mindate;maxdate:$maxdate")."</td>
		<td>". button("{go}","DayMemberChangeDate$t()",16)."</td>
		</tr>
		</tbody>
		</table>

		<script>
		function DayMemberChangeDate$t(){
		var xday=document.getElementById('SdateMember-$t').value;
		$('.ftitle').html('$members&raquo;$day:'+xday);
		$('#$t').flexOptions({url: '$page?rows=yes&day='+xday,title:'$members'+xday}).flexReload();

}

</script>";

echo $tpl->_ENGINE_parse_body($html);

}


function rows(){

	$q=new mysql_squid_builder();
	$search=trim($_GET["search"]);
	$dayfull="{$_GET["day"]} 00:00:00";
	$date=strtotime($dayfull);
	

	$tpl=new templates();

	$search='%';
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	

	$table="(SELECT sites.id AS sitesID, sites.site AS site, COUNT(DISTINCTROW(usersID)) AS users, COUNT(DISTINCTROW(ip)) AS hosts, SUM(trafficSummaries.inCache+trafficSummaries.outCache) AS bytes,
TRUNCATE((SUM(trafficSummaries.inCache)/SUM(trafficSummaries.inCache+trafficSummaries.outCache))*100,0) AS cachePercent FROM trafficSummaries 
JOIN sites ON trafficSummaries.date=sites.date
AND trafficSummaries.sitesID=sites.id WHERE trafficSummaries.date='{$_GET["day"]}' GROUP BY trafficSummaries.sitesID) as t";

	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			if($_POST["sortname"]=="zDate"){$_POST["sortname"]="hour";}
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}

	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}


	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error");}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	while ($ligne = mysqli_fetch_assoc($results)) {
		if($ligne["uid"]==null){$ligne["uid"]="&nbsp;";}
		$md5=md5(@implode(" ", $ligne));
		$ligne["bytes"]=FormatBytes($ligne["bytes"]/1024);


		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(
						"<span style='font-size:14px'>{$ligne["site"]}</span>",
						"<span style='font-size:14px'>{$ligne["users"]}</span>",
						"<span style='font-size:14px'>{$ligne["hosts"]}</span>",
						"<span style='font-size:14px'>{$ligne["bytes"]}</span>",
						"<span style='font-size:14px'>{$ligne["cachePercent"]}%</span>",
				)
		);
	}


	echo json_encode($data);
}


