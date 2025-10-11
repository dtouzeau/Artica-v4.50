<?php

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.ldap.inc');
include_once('ressources/class.tcpip.inc');
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.squid.inc');


$usersmenus=new usersMenus();
if(!$usersmenus->AsProxyMonitor){
	$tpl=new templates();
	header("content-type: application/javascript");
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die("DIE " .__FILE__." Line: ".__LINE__);
}

if(isset($_GET["logger-status"])){loggers_status();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$sock=new sockets();
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	if($SquidPerformance>1){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{artica_statistics_disabled}"));
		return;
	}
	
	$t=time();
	$html="<div style='font-size:26px;margin-bottom:20px'>{APP_LOGGERS}</div>
	<div style='font-size:16px' class=explain>{APP_LOGGERS_SQUID_EXPLAIN}</div>
	<div id='logger-status'></div>
	
			
	<script>
		function LoggerStatus$t(){
			LoadAjax('logger-status','$page?logger-status=yes',false);
		
		}
		LoggerStatus$t();
	</script>
";
	
echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
	
}


function loggers_status(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$data=unserialize(base64_decode($sock->getFrameWork("squid.php?loggers-status=yes")));
	if(!is_array($data)){return;}
	
	while (list ($PID, $ARRAY) = each ($data) ){
		$timettl=$ARRAY["TTL"];
		$PURGED=$ARRAY["PURGED"];
		$COUNT_RQS=$ARRAY["COUNT_RQS"];
		$PURGED=FormatNumber($PURGED);
		$COUNT_RQS=FormatNumber($COUNT_RQS);
		if($ARRAY["LASTTIME"]>0){
			$Laststatus=distanceOfTimeInWords($ARRAY["LASTTIME"],time(),true);
		}else{
			$Laststatus="-";
		}
		
		$f[]="
		<div style='width:550px;margin-bottom:20px' class=form>
		<table>
		<tr>
			<td style='min-width:68px;vertical-align:top'><img src='img/process-64.png'></td>
			<td style='min-width:482px;vertical-align:top'>		
				<table>
					<tr>
						<td valign='top' style='font-size:18px' class=legend>{PID}:</td>
						<td valign='top' style='font-size:18px'>$PID</td>
					</tr>
					<tr>
						<td valign='top' style='font-size:18px' class=legend>{running_since}:</td>
						<td valign='top' style='font-size:18px'>{$timettl}mn</td>
					</tr>				
					<tr>
						<td valign='top' style='font-size:18px' class=legend>{purged_events}:</td>
						<td valign='top' style='font-size:18px'>$PURGED</td>
					</tr>	
					<tr>
						<td valign='top' style='font-size:18px' class=legend>{received_connections}:</td>
						<td valign='top' style='font-size:18px'>$COUNT_RQS</td>
					</tr>	
					<tr>
						<td valign='top' style='font-size:18px' class=legend>{last_status}:</td>
						<td valign='top' style='font-size:18px'>$Laststatus</td>
					</tr>
				</table>
			</td>
		</tr>
		</table>
		</div>															
		";
		
		
	}
	
	echo $tpl->_ENGINE_parse_body(CompileTr2($f)
			."
			<div style='width:100%;height:160px'>
			 <div style='float:right;margin-left:10px;'>".button("{refresh}","LoadAjax('logger-status','$page?logger-status=yes',true);",26)."</div>
			<div style='float:right'>".button("{reload_proxy}","Loadjs('squid.reload.php');",26)."</div></div>"
			
			);
	
	
	
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}



