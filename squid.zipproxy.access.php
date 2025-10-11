<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.ldap.inc');
include_once('ressources/class.user.inc');
include_once('ressources/class.langages.inc');
include_once('ressources/class.sockets.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.privileges.inc');
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");


$users=new usersMenus();
if(!$users->AsDansGuardianAdministrator){
	$tpl=new templates();
	echo "<script> alert('". $tpl->javascript_parse_text("`{ERROR_NO_PRIVS}")."'); </script>";
	die("DIE " .__FILE__." Line: ".__LINE__);
}

if(isset($_GET["events-list"])){events_list();exit;}

popup();
function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$events=$tpl->_ENGINE_parse_body("{events}");
	$zdate=$tpl->_ENGINE_parse_body("{zDate}");
	$proto=$tpl->_ENGINE_parse_body("{proto}");
	$uri=$tpl->_ENGINE_parse_body("{url}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	if(function_exists("date_default_timezone_get")){$timezone=" - ".date_default_timezone_get();}
	$title=$tpl->_ENGINE_parse_body("{compressor_requests2}");
	$zoom=$tpl->_ENGINE_parse_body("{zoom}");
	$button1="{name: 'Zoom', bclass: 'Search', onpress : ZoomSquidAccessLogs},";
	$stopRefresh=$tpl->javascript_parse_text("{stop_refresh}");
	$logs_container=$tpl->javascript_parse_text("{logs_container}");
	$refresh=$tpl->javascript_parse_text("{refresh}");

	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{add} {category}");
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$new_schedule=$tpl->_ENGINE_parse_body("{new_rotate}");
	$explain=$tpl->_ENGINE_parse_body("{explain_squid_tasks}");
	$run=$tpl->_ENGINE_parse_body("{run}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$filename=$tpl->_ENGINE_parse_body("{filename}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$askdelete=$tpl->javascript_parse_text("{empty_store} ?");
	$duration=$tpl->_ENGINE_parse_body("{duration}");
	$ext=$tpl->_ENGINE_parse_body("{extension}");
	$back_to_events=$tpl->_ENGINE_parse_body("{back_to_events}");
	$Compressedsize=$tpl->_ENGINE_parse_body("{compressed_size}");
	$realsize=$tpl->_ENGINE_parse_body("{realsize}");
	$delete_file=$tpl->javascript_parse_text("{delete_file}");
	$proto=$tpl->javascript_parse_text("{proto}");
	$event=$tpl->_ENGINE_parse_body("{event}");
	$reload_proxy_service=$tpl->_ENGINE_parse_body("{reload_proxy_service}");
	$compressed=$tpl->_ENGINE_parse_body("{compressed}");
	$ratio=$tpl->_ENGINE_parse_body("{ratio}");
	$table_size=855;
	$url_row=505;
	$member_row=276;
	$table_height=420;
	$distance_width=230;
	$tableprc="100%";
	$margin="-10";
	$margin_left="-15";

	if(isset($_GET["bypopup"])){
		$table_size=1019;
		$url_row=576;
		$member_row=333;
		$distance_width=352;
		$margin=0;
		$margin_left="-5";
		$tableprc="99%";
		$button1="{name: '<strong id=refresh-$t>$stopRefresh</stong>', bclass: 'Reload', onpress : StartStopRefresh$t},";
		$table_height=590;
		$Start="StartRefresh$t()";
	}

	$q=new mysql_squid_builder();
	$countContainers=$q->COUNT_ROWS("squid_storelogs");
	if($countContainers>0){
		$button2="{name: '<strong id=container-log-$t>$logs_container</stong>', bclass: 'SSQL', onpress : StartLogsContainer$t},";
		$button_container="{name: '<strong id=container-log-$t>$back_to_events</stong>', bclass: 'SSQL', onpress : StartLogsSquidTable$t},";
		$button_container_delall="{name: '$empty', bclass: 'Delz', onpress : EmptyStore$t},";
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT SUM(Compressedsize) as tsize FROM squid_storelogs"));
		$title_table_storage="$logs_container $countContainers $files (".FormatBytes($ligne["tsize"]/1024).")";
	}


	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$error=$tpl->javascript_parse_text("{error}");
	$sitename=$tpl->javascript_parse_text("{sitename}");
	$button3="{name: '<strong id=container-log-$t>$rotate_logs</stong>', bclass: 'Reload', onpress : SquidRotate$t},";


	$buttons[]="{name: '<strong>$reload_proxy_service</stong>', bclass: 'Reload', onpress : ReloadProxy$t},";


	$html="
		
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>

	<input type='hidden' id='refresh$t' value='1'>
	<script>
	var mem$t='';
	function StartLogsSquidTable$t(){
	$('#flexRT$t').flexigrid({
	url: '$page?events-list=yes',
	dataType: 'json',
	colModel : [
		
	{display: '$zdate', name : 'zDate', width :139, sortable : true, align: 'left'},
	{display: '$ipaddr', name : 'events', width : 123, sortable : false, align: 'left'},
	{display: '$proto', name : 'proto', width : 75, sortable : false, align: 'left'},
	{display: '$uri', name : 'events', width : 394, sortable : false, align: 'left'},
	{display: '$size', name : 'size', width : 77, sortable : false, align: 'left'},
	{display: '$compressed', name : 'size2', width : 77, sortable : false, align: 'left'},
	{display: '$ratio', name : 'ratio', width : 46, sortable : false, align: 'center'},
	],

	buttons : [

	],


	searchitems : [
	{display: '$event', name : 'event'},
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=\"font-size:16px\">$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '98.5%',
	height: 640,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]

});

}



StartLogsSquidTable$t();
</script>";
	echo $html;
}

function events_list(){
	$sock=new sockets();
	if(!is_numeric($_POST["rp"])){$_POST["rp"]=50;}
	$sock->getFrameWork("squid.php?zipproxy-real=yes&rp={$_POST["rp"]}&query=".urlencode($_POST["query"]));
	$filename="/usr/share/artica-postfix/ressources/logs/zipproxy-access.log.tmp";
	$dataZ=explode("\n",@file_get_contents($filename));
	$tpl=new templates();
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($data);
	$data['rows'] = array();
	$today=date("Y-m-d");
	$tcp=new IP();

	$cachedT=$tpl->_ENGINE_parse_body("{cached}");
	$c=0;

	if(count($dataZ)==0){json_error_show("no data");}
	$logfileD=new logfile_daemon();
	if($GLOBALS["VERBOSE"]){echo "<strong>Receive ".count($dataZ)." events</strong><hr>\n";}

	while (list ($num, $line) = each ($dataZ)){
		
		$TR=preg_split("/[\s,]+/", $line);
		
		if($GLOBALS["VERBOSE"]){print_r($TR);}
		
		if(count($TR)<5){continue;}
		$c++;
		$color="black";
		

		
		
		$date=date("Y-m-d H:i:s",$TR[0]);
		$ip=$TR[2];
		$size=$TR[4];
		$sizeCompressed=$TR[5];
		$PROTO=$TR[6];
		
		$uri=$TR[7];
		$ratio=0;
		if($PROTO=="CONNECT"){$color="#BAB700";}
		if($PROTO<>"CONNECT"){
			$color="#A5A5A5";
			if($sizeCompressed<$size){
				$ratio=($sizeCompressed/$size)*100;
				$ratio=round($ratio,2);
				$color="black";
			}
		}


		if($size>1024){
			$size=FormatBytes($size/1024);}
		else{
			$size="$size Bytes";
		}
		if($sizeCompressed>1024){
			$sizeCompressed=FormatBytes($sizeCompressed/1024);}
		else{$sizeCompressed="$sizeCompressed Bytes";}
		$date=str_replace($today." ", "", $date);
		$data['rows'][] = array(
				'id' => md5($line),
				'cell' => array(
						"<span style='font-size:14px;color:$color'>$date</span>",
						"<span style='font-size:14px;color:$color'>$ip</span>",
						"<span style='font-size:14px;color:$color'>{$PROTO}</span>",
						"<span style='font-size:14px;color:$color'>{$uri}</span>",
						"<span style='font-size:14px;color:$color'>$size</span>",
						"<span style='font-size:14px;color:$color'>$sizeCompressed</span>",
						"<span style='font-size:14px;color:$color'>{$ratio}%</span>",
						"$ip"
				)
		);

	}

	if($c==0){json_error_show("no event",3);}
	$data['total'] = $c;
	echo json_encode($data);

}
