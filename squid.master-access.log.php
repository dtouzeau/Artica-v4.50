<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.ldap.inc');
include_once('ressources/class.user.inc');
include_once('ressources/class.langages.inc');
include_once('ressources/class.sockets.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.privileges.inc');
include_once('ressources/class.ChecksPassword.inc');
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");

session_start();
if($_SESSION["uid"]==null){ AskPasswordAuth("{realtime_requests}"); }


$users=new usersMenus();
if(!$users->AsDansGuardianAdministrator){
	$tpl=new templates();
	echo "<script> alert('". $tpl->javascript_parse_text("`{$_SERVER['PHP_AUTH_USER']}/{$_SERVER['PHP_AUTH_PW']}` {ERROR_NO_PRIVS}")."'); </script>";
	die("DIE " .__FILE__." Line: ".__LINE__);
}
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["events-list"])){events_list();exit;}

page();
function page(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$hostname=trim($sock->GET_INFO("myhostname"));
	$events=$tpl->_ENGINE_parse_body("{realtime_requests}");
	$please_wait=$tpl->_ENGINE_parse_body("{please_wait}");
echo "<!DOCTYPE html>
<html lang=\"en\">
<head>
	<meta http-equiv=\"X-UA-Compatible\" content=\"IE=9; IE=8\">
	<meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-type\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/artica-theme/jquery-ui.custom.css\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.jgrowl.css\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.cluetip.css\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.treeview.css\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/flexigrid.pack.css\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/fonts.css.php\" />
	
	<title>$hostname $events</title>
	<link rel=\"icon\" href=\"/ressources/templates/Squid/favicon.ico\" type=\"image/x-icon\" />
	<link rel=\"shortcut icon\" href=\"/ressources/templates/Squid/favicon.ico\" type=\"image/x-icon\" />
	<script type=\"text/javascript\" language=\"javascript\" src=\"/mouse.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/XHRConnection.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/cookies.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/default.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery-1.8.3.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery-ui-1.8.22.custom.min.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jqueryFileTree.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.simplemodal-1.3.3.min.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.tools.min.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/flexigrid.pack.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/ui.selectmenu.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.cookie.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.blockUI.js\"></script>
</head>

<body style='background-color:white;margin:0px;padding:0px'>
<div id='mainaccess'>
	<center style='font-size:50px'>$please_wait</center>
	</div>
<script>
	LoadAjax('mainaccess','$page?popup=yes',true);
</script>
</body>
</html>";

}



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
	$title=$tpl->_ENGINE_parse_body("{today}: {realtime_requests} ".date("H")."h$timezone");
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
	$MAC=$tpl->_ENGINE_parse_body("{MAC}");
	$reload_proxy_service=$tpl->_ENGINE_parse_body("{reload_proxy_service}");
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
	$search=$tpl->javascript_parse_text("{search}");
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
			{display: 'Proxy/$ipaddr', name : 'ipaddr', width : 203, sortable : false, align: 'left'},
			{display: '&nbsp;', name : 'code', width : 233, sortable : false, align: 'left'},
			{display: '$proto', name : 'proto', width : 75, sortable : false, align: 'left'},
			{display: '$uri', name : 'events', width : 512, sortable : false, align: 'left'},
			{display: '$size', name : 'size', width : 106, sortable : false, align: 'left'},
			{display: '$duration', name : 'duration', width : 106, sortable : false, align: 'left'},
			],
				
			buttons : [
				
			],
				
	
			searchitems : [
			{display: '$search', name : 'sitename'},
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
	$sock->getFrameWork("squid.php?access-real=yes&ViaMaster=yes&rp={$_POST["rp"]}&query=".urlencode($_POST["query"].""));
	$filename="/usr/share/artica-postfix/ressources/logs/ViaMaster.log.tmp";
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
	
	
	while (list ($num, $line) = each ($dataZ)){
		$TR=preg_split("/[\s,]+/", $line);
		if(count($TR)<5){continue;}
		$c++;
		$color="black";
		$date=date("Y-m-d H:i:s",$TR[0]);
		$durationunit="s";
		$duration=$TR[1]/1000;
		if($duration<60){$duration=round($duration,2);}
		if($duration>60){$duration=round($duration/60,2);$durationunit="mn";}
		$ip=$TR[2];
		$zCode=explode("/",$TR[3]);
		$size=$TR[4];
		$PROTO=$TR[5];
		if($logfileD->CACHEDORNOT($zCode[0])){$color="#009223";}
		$codeToString=$logfileD->codeToString($zCode[1]);
		
		if($PROTO=="CONNECT"){$color="#BAB700";}
		if($zCode[1]>399){$color="#D0080A";}
		
		
		
		if($size>1024){$size=FormatBytes($size/1024);}else{$size="$size Bytes";}
		$date=str_replace($today." ", "", $date);
		$data['rows'][] = array(
				'id' => md5($line),
				'cell' => array(
						"<span style='font-size:14px;color:$color'>$date</span>",
						"<span style='font-size:14px;color:$color'>$ip/{$TR[10]}</span>",
						"<span style='font-size:14px;color:$color'>{$zCode[0]} - $codeToString</span>",
						"<span style='font-size:14px;color:$color'>{$PROTO}</span>",
						"<span style='font-size:14px;color:$color'>{$TR[6]}</span>",
						"<span style='font-size:14px;color:$color'>$size</span>",
						"<span style='font-size:14px;color:$color'>{$duration}$durationunit</span>",
						"$ip",""
				)
		);
		
	}
	
	if($c==0){json_error_show("no event");}
	$data['total'] = $c;
	echo json_encode($data);
	
}
