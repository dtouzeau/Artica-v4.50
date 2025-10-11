<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		$content=str_replace("{SCRIPT}", "<script>alert('$onlycorpavailable');document.location.href='miniadm.webstats.php';</script>", $content);
		echo $content;	
		return;
	}

	if(isset($_GET["sites-tables"])){sites_tables();exit;}
	if(isset($_GET["sites-tables-rows"])){sites_tables_row();exit;}
	if(isset($_GET["members-week"])){members_table();exit;}
	if(isset($_GET["members-week-rows"])){members_table_row();exit;}
	
	
	
page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$week=$_GET["week"];
	$year=$_GET["year"];
	$familysite=$_GET["familysite"];
	$t=time();
	$tablename="$year{$week}_week";
	$sql="SELECT `day`,SUM(hits) as hits,SUM(size) as size FROM $tablename GROUP BY `day`,familysite HAVING familysite='$familysite'";
	
	$q=new mysql_squid_builder();
	$timeZ=$q->WEEK_TOTIMEHASH_FROM_TABLENAME($tablename);

	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$xdata[]=$tpl->_ENGINE_parse_body(date("{l}",$timeZ[$ligne["day"]]));
		$ydata[]=$ligne["hits"];
		
		
		$ydata2[]=round(($ligne["size"]/1024)/1000);		
		
	}	
	
	
	
	
	$targetedfile="ressources/logs/".md5(basename(__FILE__).".".__FUNCTION__.".". $sql).".6.png";
	$gp=new artica_graphs();
	$gp->width=938;
	$gp->height=250;
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=null;
	$gp->x_title=$tpl->_ENGINE_parse_body("{days}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";
	$t=time();
	$gp->line_green();
	if(is_file($targetedfile)){$image="<center>
	<p style='font-size:18px'>$familysite: {requests_during_this_week}</p>
	<img src='$targetedfile'></center>";}

	reset($xdata);
	$targetedfile="ressources/logs/".md5(basename(__FILE__).".".__FUNCTION__.".". $sql).".7.png";
	$gp=new artica_graphs();
	$gp->width=938;
	$gp->height=250;
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata2;
	$gp->y_title=null;
	$gp->x_title=$tpl->_ENGINE_parse_body("{days}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";
	$t=time();
	$gp->line_green();
	if(is_file($targetedfile)){$image2="<center>
	<p style='font-size:18px'>$familysite: {size_during_this_week} (MB)</p>
	<img src='$targetedfile'></center>";}	
	
	
	$html="
	$image
	$image2
	<hr>
	<div id='$t'></div>
	<script>
		LoadAjax('$t','$page?sites-tables=yes&familysite=$familysite&week={$_GET["week"]}&year={$_GET["year"]}');
	</script>
	";
	
	echo $html;
}

function sites_tables(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sitename=$tpl->_ENGINE_parse_body("{sitename}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");	
	$title=$tpl->_ENGINE_parse_body("{$_GET["familysite"]}: {visited_subsites_during_this_week}");
	
	$t=time();

	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?sites-tables-rows=yes&week={$_GET["week"]}&year={$_GET["year"]}&familysite={$_GET["familysite"]}',
	dataType: 'json',
	colModel : [
		{display: '$sitename', name : 'sitename', width :676, sortable : true, align: 'left'},
		{display: '$hits', name : 'thits', width :97, sortable : true, align: 'center'},
		{display: '$size', name : 'tsize', width :108, sortable : true, align: 'center'},
		
	],
	

	searchitems : [
		{display: '$sitename', name : 'sitename'},
		],
	sortname: 'thits',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 938,
	height: 350,
	singleSelect: true
	
	});   
});
	
</script>";
	
	echo $html;	
	
}
function members_table_row(){
$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$year=$_GET["year"];
	$week=$_GET["week"];
	
	
	$search='%';
	$page=1;
	$total=0;
	$rp=50;
	$tablename="$year{$week}_week";
	$familysite=$_GET["familysite"];
	$table="(SELECT `client` as ipaddr,hostname,uid,MAC,familysite,SUM(hits) as thits,SUM(size) as tsize FROM $tablename 
	GROUP BY `ipaddr`,hostname,MAC,uid,familysite HAVING familysite='$familysite') as t";	
	
	if($q->COUNT_ROWS($tablename,"artica_events")==0){json_error_show("$tablename No such table");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT * FROM $table WHERE 1 $searchstring";
		$results=$q->QUERY_SQL($sql,"artica_events");
		$total =mysql_numrows($results);
		
	}else{
		$sql="SELECT COUNT(*) FROM $table";
		$results=$q->QUERY_SQL($sql,"artica_events");
		$total =mysql_numrows($results);
		
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show("$tablename $sql<br>$q->mysql_error");}
	
	$textcss="<span style='font-size:16px'>";
	while ($ligne = mysqli_fetch_assoc($results)) {
	
		$jsweb="
		<a href=\"javascript:blur()\"
		OnClick=\"Loadjs('squid.traffic.statistics.days.php?today-zoom=yes&type=req&familysite={$ligne["familysite"]}&day={$_GET["day"]}')\"
		style='font-size:12px;text-decoration:underline'>";
		
		$jsjscat="Loadjs('squid.categorize.php?www={$ligne["sitename"]}&day={$_GET["day"]}&week=&month=');";
		$jscat="<a href=\"javascript:blur()\"
		OnClick=\"javascript:$jsjscat\"
		style='font-size:12px;text-decoration:underline'>
		";

		

		$ligne["tsize"]=FormatBytes($ligne["tsize"]/1024);
		
		
	$data['rows'][] = array(
		'id' => $ligne['MAC'],
		'cell' => array(
				$textcss.$ligne["uid"]."</a></span>",
				$textcss.$ligne["hostname"]."</a></span>",
				$textcss.$ligne["ipaddr"]."</a></span>",
				$textcss.$ligne["MAC"]."</a></span>",
				$textcss.$ligne["thits"]."</span>",
				$textcss.$ligne["tsize"]."</span>",
			)
		);
	}
	
	
echo json_encode($data);		
	
}

function sites_tables_row(){
$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$year=$_GET["year"];
	$week=$_GET["week"];
	
	
	$search='%';
	$page=1;
	$total=0;
	$rp=50;
	$tablename="$year{$week}_week";
	$familysite=$_GET["familysite"];
	$table="(SELECT `sitename`,SUM(hits) as thits,SUM(size) as tsize FROM $tablename GROUP BY `sitename`,familysite HAVING familysite='$familysite') as t";	
	
	if($q->COUNT_ROWS($tablename,"artica_events")==0){json_error_show("$tablename No such table");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT * FROM $table WHERE 1 $searchstring";
		$results=$q->QUERY_SQL($sql,"artica_events");
		$total =mysql_numrows($results);
		
	}else{
		$sql="SELECT COUNT(*) FROM $table";
		$results=$q->QUERY_SQL($sql,"artica_events");
		$total =mysql_numrows($results);
		
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show("$tablename $sql<br>$q->mysql_error");}
	
	$textcss="<span style='font-size:16px'>";
	while ($ligne = mysqli_fetch_assoc($results)) {
	
		$jsweb="
		<a href=\"javascript:blur()\"
		OnClick=\"Loadjs('squid.traffic.statistics.days.php?today-zoom=yes&type=req&familysite={$ligne["familysite"]}&day={$_GET["day"]}')\"
		style='font-size:12px;text-decoration:underline'>";
		
		$jsjscat="Loadjs('squid.categorize.php?www={$ligne["sitename"]}&day={$_GET["day"]}&week=&month=');";
		$jscat="<a href=\"javascript:blur()\"
		OnClick=\"javascript:$jsjscat\"
		style='font-size:12px;text-decoration:underline'>
		";

		

		$ligne["tsize"]=FormatBytes($ligne["tsize"]/1024);
		
		
	$data['rows'][] = array(
		'id' => $ligne['MAC'],
		'cell' => array(
				$textcss.$ligne["sitename"]."</a></span>",
				$textcss.$ligne["thits"]."</span>",
				$textcss.$ligne["tsize"]."</span>",
			)
		);
	}
	
	
echo json_encode($data);	
}

function members_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sitename=$tpl->_ENGINE_parse_body("{sitename}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");	
	$title=$tpl->_ENGINE_parse_body("{$_GET["familysite"]}: {members}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$ComputerMacAddress=$tpl->_ENGINE_parse_body("{ComputerMacAddress}");	
	$t=time();

	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?members-week-rows=yes&week={$_GET["week"]}&year={$_GET["year"]}&familysite={$_GET["familysite"]}',
	dataType: 'json',
	colModel : [
		{display: '$member', name : 'uid', width :120, sortable : true, align: 'left'},
		{display: '$hostname', name : 'hostname', width :255, sortable : true, align: 'left'},
		{display: '$ipaddr', name : 'ipaddr', width :120, sortable : true, align: 'left'},
		{display: '$ComputerMacAddress', name : 'MAC', width :142, sortable : true, align: 'left'},
		{display: '$hits', name : 'thits', width :97, sortable : true, align: 'center'},
		{display: '$size', name : 'tsize', width :108, sortable : true, align: 'center'},
		
	],
	

	searchitems : [
		{display: '$member', name : 'uid'},
		{display: '$hostname', name : 'hostname'},
		{display: '$ipaddr', name : 'ipaddr'},
		{display: '$ComputerMacAddress', name : 'MAC'},
		],
	sortname: 'thits',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 938,
	height: 350,
	singleSelect: true
	
	});   
});
	
</script>";
	
	echo $html;	
	
}
