<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.squid.inc');

$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}

if(isset($_GET["categories"])){page();exit;}
if(isset($_GET["search"])){search();exit;}
js();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text(utf8_encode("{categories}"));
	$t=time();

$html="YahooWinBrowse('850','$page?categories=yes&callback={$_GET["callback"]}','$title');";
echo $html;
}


function page(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$websites=$tpl->_ENGINE_parse_body("{websites}");
	$add_website=$tpl->_ENGINE_parse_body("{add_website}");
	
	$select=$tpl->javascript_parse_text("{select}");
	$aliases=$tpl->javascript_parse_text("{aliases}");
	$about2=$tpl->_ENGINE_parse_body("{about2}");
	$new_report=$tpl->javascript_parse_text("{new_report}");
	$report=$tpl->javascript_parse_text("{report}");
	$title=$tpl->javascript_parse_text("{statistics}:: {websites}");
	$progress=$tpl->javascript_parse_text("{progress}");
	$run=$tpl->javascript_parse_text("{run}");
	$size=$tpl->javascript_parse_text("{size}");
	$hits=$tpl->javascript_parse_text("{hits}");
	
	$q=new mysql_squid_builder();
	
	
	$build=$tpl->javascript_parse_text("{rebuild_items}");
	$buttons="
	buttons : [
	{name: '<strong style=font-size:16px >$build</strong>', bclass: 'Reconf', onpress : BuildCache$t},
	],";
	
	
	$html="
	<table class='SQUID_FAMZ' style='display: none' id='SQUID_FAMZ' style='width:100%'></table>
	<script>
	$(document).ready(function(){
	$('#SQUID_FAMZ').flexigrid({
	url: '$page?search=yes&callback={$_GET["callback"]}',
	dataType: 'json',
		colModel : [
		{display: '$websites', name : 'familysite', width : 390, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width : 124, sortable : true, align: 'right'},
		{display: '$hits', name : 'hits', width : 124, sortable : true, align: 'right'},
		{display: '$select;', name : 'explain', width : 70, sortable : false, align: 'center'},
		],
		$buttons
	searchitems : [
	{display: '$websites', name : 'familysite'},
	
	],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: '350',
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});
	
	
function NewReport$t(){
	Loadjs('squid.browse-familysites.php?callback=Addcategory$t');
}

function BuildCache$t(){
	Loadjs('squid.browse-familysites.progress.php');
}

var xAddcategory$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#SQUID_MAIN_REPORTS').flexReload();
	$('#SQUID_FAMZ').flexReload();
}
	
function Addcategory$t(categorykey){
	var XHR = new XHRConnection();
	XHR.appendData('ID','{$_GET["ID"]}');
	XHR.appendData('categorykey',categorykey);
	XHR.sendAndLoad('$page', 'POST',xAddcategory$t);
}
</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$table="main_websites";
	$q=new mysql_squid_builder();
	$FORCE=1;
	$t=$_GET["t"];


	$total=0;
	if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("no data [".__LINE__."]",0);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];

	}else{
		if(strlen($FORCE)>2){
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE";
			$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_events"));
			$total = $ligne["TCOUNT"];
		}else{
			$total = $q->COUNT_ROWS($table, "artica_events");
		}
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=100;}


	$pageStart = ($page-1)*$rp;
	if($pageStart<0){$pageStart=0;}
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM `$table` WHERE $FORCE $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",0);}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$CurrentPage=CurrentPageName();

	if(mysqli_num_rows($results)==0){json_error_show("no data");}
	$searchstring=string_to_flexquery();


	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	$q1=new mysql();
	$t=time();

	$fontsize=22;

	$report=$tpl->javascript_parse_text("{report}");
	$category=$tpl->javascript_parse_text("{category}");
	$from_the_last_time=$tpl->javascript_parse_text("{from_the_last_time}");
	$report_not_categorized_text=$tpl->javascript_parse_text("{report_not_categorized}");
	$error_engine_categorization=$tpl->javascript_parse_text("{error_engine_categorization}");

	$span="<span style='font-size:{$fontsize}px'>";



	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$zmd5=$ligne["zmd5"];
		$familysite=$ligne["familysite"];
		
		$size=FormatBytes($ligne["size"]/1024);
		$hits=FormatNumber($ligne["hits"]);
		$select=imgsimple("arrow-right-32.png",null,"{$_GET["callback"]}('$familysite')");

		$data['rows'][] = array(
				'id' => $zmd5,
				'cell' => array(
						"$span$familysite</a></span>",
						"$span$size</a></span>",
						"$span$hits</a></span>",
						"$select",

				)
		);

	}
	echo json_encode($data);

}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}

