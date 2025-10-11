<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.squid.builder.php');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){echo header("content-type: application/x-javascript");"alert('No privs!');";die("DIE " .__FILE__." Line: ".__LINE__);}
	if(isset($_GET["refresh"])){refresh();exit;}
	if(isset($_GET["list"])){showlist();exit;}
popup();




function popup(){
	$tpl=new templates();
	$page=CurrentPageName();

	$zdate=$tpl->javascript_parse_text("{time}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$duration=$tpl->javascript_parse_text("{duration}");
	$category=$tpl->javascript_parse_text("{category}");
	$member=$tpl->javascript_parse_text("{member}");
	$day=$tpl->javascript_parse_text("{day}");
	$scan_date=$tpl->javascript_parse_text("{scan_date}");
	$event=$tpl->javascript_parse_text("{event}");
	$size=$tpl->javascript_parse_text("{size}");
	$t=time();
	$ActiveRequestsR=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/CurrentSizesUsers.db"));
	$ActiveRequestsNumber=count($ActiveRequestsR["categories"]);
	$title=$tpl->javascript_parse_text("{active_requests}::$ActiveRequestsNumber {categories}");
	if(count($ActiveRequestsR["categories"])==0){
		echo FATAL_ERROR_SHOW_128("{active_requests_no_categories}");
		return;
		
	}
	
	
	$html="
<table class='flexRT$t' style='display:none' id='flexRT$t'></table>
<script>
function StartLogsSquidTable$t(){

$('#flexRT$t').flexigrid({
	url: '$page?list=yes',
	dataType: 'json',
	colModel : [
	{display: '$day', name : 'day', width : 110, sortable : false, align: 'right'},
	{display: '$member', name : 'uid', width : 215, sortable : false, align: 'left'},
	{display: '$category', name : 'category', width : 250, sortable : false, align: 'left'},
	{display: '$size', name : 'size', width : 154, sortable : false, align: 'right'},
	{display: '$scan_date', name : 'size2', width : 142, sortable : false, align: 'right'},
	],

	searchitems : [
	{display: '$day', name : 'day'},
	{display: '$category', name : 'category'},
	
	{display: '$member', name : 'uid'},
	],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<span id=title-$t style=font-size:22px>$title</span>',
	useRp: true,
	rp: 200,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500,1000,1500]

});

}

StartLogsSquidTable$t();
</script>
";
echo $html;
}

function refresh(){
	header("content-type: application/x-javascript");
	$t=$_GET["id"];
	$page=CurrentPageName();
	$ActiveRequestsR=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/CurrentSizesUsers.db"));
	$ActiveRequestsNumber=count($ActiveRequestsR["categories"]);
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{active_requests}::$ActiveRequestsNumber");
	
	echo "function RefreshActiveRequests$t(){
				if(!document.getElementById('flexRT$t')){return;}
				if(!YahooWin5Open()){return;}
				document.getElementById('title-$t').innerHTML='$title';
				$('#flexRT$t').flexReload();
				Loadjs('$page?refresh=yes&id=$t');
			}
			
	setTimeout('RefreshActiveRequests$t()',80000);";
	
	
}


function showlist(){
	$page=1;
	
	$ActiveRequestsR=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/CurrentSizesUsers.db"));
	$duration=date("H:i:s",$ActiveRequestsR["TIME_BUILD"]);
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$q=new mysql_squid_builder();
	$searchstring=string_to_flexregex();
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";


	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();

	//if(mysqli_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
$c=0;
	while (list ($category, $mainarray) = each ($ActiveRequestsR["categories"]) ){
		
		if($searchstring<>null){
			if($_POST["qtype"]=="category"){
				if(!preg_match("#$searchstring#i", $category)){
					continue;
				}
			}
		}
		
		while (list ($time, $mainarray2) = each ($mainarray) ){	
			if(!preg_match("#[0-9]+-[0-9]+#",$time)){continue;}
			
			if($searchstring<>null){
				if($_POST["qtype"]=="day"){
					if(!preg_match("#$searchstring#i", $time)){
						continue;
					}
				}
			}

			while (list ($member, $size) = each ($mainarray2) ){
				if($c==$rp){break;}
				
				if($searchstring<>null){
					if($_POST["qtype"]=="uid"){
						if(!preg_match("#$searchstring#i", $member)){
							continue;
						}
					}
				}
				
				$c++;
				$sizeText="{$size} Bytes";
				if($size>1023){
					$sizeText=FormatBytes($size/1024);
				}
				
				$data['rows'][] = array(
						'id' => md5(serialize($mainarray)),
						'cell' => array("<span style='font-size:16px'>{$time}</span>",
								"<span style='font-size:16px'>$member</span>",
								"<span style='font-size:16px'>$category</span>",
								"<span style='font-size:16px'>$sizeText</span>",
								"<span style='font-size:16px'>$duration</span>",
								
								)
				);
			}
	
	}
	}

	$data['total'] = $c;
	echo json_encode($data);
}
function duration ($seconds) {
	$takes_time = array(604800,86400,3600,60,0);
	$suffixes = array("w","d","h","m","s");
	$output = "";
	foreach ($takes_time as $key=>$val) {
		${$suffixes[$key]} = ($val == 0) ? $seconds : floor(($seconds/$val));
		$seconds -= ${$suffixes[$key]} * $val;
		if (${$suffixes[$key]} > 0) {
			$output .=  ${$suffixes[$key]};
			$output .= $suffixes[$key]." ";
		}
	}
	return trim($output);
}