<?php
$GLOBALS["ICON_FAMILY"]="COMPUTERS";
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');

if(posix_getuid()<>0){
	$users=new usersMenus();
	if((!$users->AsSambaAdministrator) OR (!$users->AsSystemAdministrator)){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["EnableScanComputersNetSchedule"])){EnableScanComputersNetScheduleSave();exit;}


js();


function js(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$title=$tpl->_ENGINE_parse_body("{periodic_scan}");
	$html="YahooWin3('375','$page?popup=yes','$title');";
	echo $html;
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$sock=new sockets();
	$EnableScanComputersNet=$sock->GET_INFO("EnableScanComputersNet");
	if(!is_numeric($EnableScanComputersNet)){$EnableScanComputersNet=0;}
	$EnableScanComputersNetSchedule=$sock->GET_INFO("EnableScanComputersNetSchedule");
	if(!is_numeric($EnableScanComputersNetSchedule)){$EnableScanComputersNetSchedule=15;}
	$id=time();
	$html="
	<div id='$id'>
	<div class=explain style='font-size:13px'>{periodic_scan_net_explain}</div>
	
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:16px'>{enable}:</td>
		<td>". Field_checkbox("EnableScanComputersNet",1,$EnableScanComputersNet,"EnableScanComputersNetCheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{schedule}:</td>
		<td style='font-size:16px'>". Field_text("EnableScanComputersNetSchedule",$EnableScanComputersNetSchedule,"width:90px;font-size:16px")."&nbsp;{minutes}</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>". button("{apply}", "SaveScanNetComputers()")."</td>
	</tr>
	</tbody>
	</table>
	</div>
	<script>
	
	var x_SaveScanNetComputers= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		if(document.getElementById('ocs-search-toolbox')){LoadAjax('ocs-search-toolbox','computer-browse.php?MenusRight=yes');}
		if(document.getElementById('ocs-search-div')){SearchComputers();}
		YahooWin3Hide();
	}	
	
	
		function EnableScanComputersNetCheck(){
			document.getElementById('EnableScanComputersNetSchedule').disabled=true;
			if(document.getElementById('EnableScanComputersNet').checked){
				document.getElementById('EnableScanComputersNetSchedule').disabled=false;	
			}
		}
	
	function SaveScanNetComputers(){
		var XHR = new XHRConnection();
		XHR.appendData('EnableScanComputersNetSchedule',document.getElementById('EnableScanComputersNetSchedule').value);
		if(document.getElementById('EnableScanComputersNet').checked){XHR.appendData('EnableScanComputersNet',1);}else{XHR.appendData('EnableScanComputersNet',0);}
		AnimateDiv('$id');
		XHR.sendAndLoad('$page', 'POST',x_SaveScanNetComputers);		
	
	}
	EnableScanComputersNetCheck();
	</script>";
	echo $tpl->_ENGINE_parse_body($html);
}

function EnableScanComputersNetScheduleSave(){
	$sock=new sockets();
	$sock->SET_INFO("EnableScanComputersNet", $_POST["EnableScanComputersNet"]);
	$sock->SET_INFO("EnableScanComputersNetSchedule", $_POST["EnableScanComputersNetSchedule"]);
	
}