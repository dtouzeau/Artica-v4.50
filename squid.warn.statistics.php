<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.cyrus.inc');
include_once('ressources/class.main_cf.inc');
include_once('ressources/charts.php');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');

$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){die("Not autorized");}

if(isset($_POST["SquidWarnContinue"])){SquidWarnContinue();exit;}
if(isset($_POST["SquidStopArticaStats"])){SquidStopArticaStats();exit;}



page();
function page(){
$users=new usersMenus();
$page=CurrentPageName();
$tpl=new templates();	

$users->MEM_TOTAL_INSTALLEE=FormatBytes($users->MEM_TOTAL_INSTALLEE);
$t=time();
$html="	<div style='width:99%' class=form id='mainid-$t'>
	<table style='width:100%'>
	<tr>
		
		<td valign='top'>
			<p style='font-size:15px'>
			<img src='img/report-warning-256.png' style='margin:10px;float:left'>
			<p style='font-size:16px'>{server}: CPU(s): $users->CPU_NUMBER, {memory}:$users->MEM_TOTAL_INSTALLEE</strong></p>
			<p style='font-size:15px'>{WARN_SQUID_STATS_PERFS}</p>
			</p>
			<table style='width:99%'>
			<tr>
				<td colspan=2 style='font-size:16px;font-weight:bold'>{visit_links}:</td>
			</tr>
			<tr>
				<td valign=middle' width=1%><img src='img/arrow-right-24.png'></td>
				<td width=99% align='left'><a href=\"javascript:blur();\" 
					OnClick=\"javascript:s_PopUpFull('http://www.proxy-appliance.org/index.php?cID=233','1024','900');\"
					style='font-size:15px;font-weight:bold;text-decoration:underline'>{thewebproxy_appliance}</a>
				</td>
			</tr>
			<tr>
				<td valign=middle' width=1%><img src='img/arrow-right-24.png'></td>
				<td width=99% align='left'><a href=\"javascript:blur();\" 
					OnClick=\"javascript:s_PopUpFull('http://www.proxy-appliance.org/index.php?cID=203','1024','900');\"
					style='font-size:15px;font-weight:bold;text-decoration:underline'>{howtousesarg}</a>
				</td>
			</tr>				
			<tr>
				<td valign=middle' width=1%><img src='img/arrow-right-24.png'></td>
				<td width=99% align='left'><a href=\"javascript:blur();\" 
					OnClick=\"javascript:s_PopUpFull('http://www.proxy-appliance.org/index.php?cID=245','1024','900');\"
					style='font-size:15px;font-weight:bold;text-decoration:underline'>{howtogenerateproxycsv}</a>
				</td>
			</tr>	
			</table>
			
			<table style='width:99%' class=form>
			<tr style='height:15px'>
				<td align='center' style='font-size:18px;font-weight:bold'>{actions}:</td>
			</tr>
			<tr style='height:15px'>
				<td align='center'>". button("{i_understand_continue}", "SquidWarnContinue()","18px")."</td> 
			</tr>
			<tr style='height:15px'>
				<td align='center'>". button("{stop_articastats}", "SquidStopArticaStats()","18px")."</td> 
			</tr>
			</table>			
	</tr>
	</table>
	</div>
	<script>
		var x_SquidWarnContinue= function (obj) {
			document.getElementById('mainid-$t').innerHTML='';
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			document.location.href='admin.index.php';
		}			
		var x_SquidStopArticaStats= function (obj) {
			document.getElementById('mainid-$t').innerHTML='';
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			CacheOff();
			document.location.href='admin.index.php';
			
		}		
	
		function SquidWarnContinue(){
			var XHR = new XHRConnection();
			XHR.appendData('SquidWarnContinue','yes');
			AnimateDiv('mainid-$t');
			XHR.sendAndLoad('$page', 'POST',x_SquidWarnContinue);		

		}
		
		function SquidStopArticaStats(){
			var XHR = new XHRConnection();
			XHR.appendData('SquidStopArticaStats','yes');
			AnimateDiv('mainid-$t');
			XHR.sendAndLoad('$page', 'POST',x_SquidStopArticaStats);		
		
		}
	
	</script>
	";	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function SquidWarnContinue(){
	$sock=new sockets();
	$sock->SET_INFO("StatsPerfsSquidAnswered", 1);
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{StatsPerfsSquidAnswered_continue}",1);
	
	
}

function SquidStopArticaStats(){
	$sock=new sockets();
	$sock->SET_INFO("DisableArticaProxyStatistics", 1);
	$sock->SET_INFO("CleanArticaSquidDatabases", 1);
	$sock->SET_INFO("StatsPerfsSquidAnswered", 1);
	$sock->getFrameWork("squid.php?clean-mysql-stats=yes");	
	
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{StatsPerfsSquidAnswered_disablestats}",1);	
	
}