<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die("DIE " .__FILE__." Line: ".__LINE__);	
}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["settings"])){settings();exit;}
if(isset($_POST["SquidEnableSessionEngine"])){settings_save();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_SQUID_SESSION_MANAGER}");
	$html="YahooWin2('750','$page?tabs=yes','$title')";
	echo $html;
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["settings"]="{settings}";
	
	
	foreach ($array as $num=>$ligne){
	
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n");
		
			
		}
	echo "<div id=main_squid_sessions style='width:100%;height:600px;overflow:auto;background-color:white;'>
				<ul>". implode("\n",$html)."</ul>
		</div>
		<script>
				$(document).ready(function(){
					$('#main_squid_sessions').tabs();
			

			});
		</script>";		
	
	
}

function settings(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();

	
	$SquidEnableSessionEngine=$sock->GET_INFO("SquidEnableSessionEngine");
	$SquidSessionEngineTimeOut=$sock->GET_INFO("SquidSessionEngineTimeOut");
	$SquidSessionEngineExternalUrl=$sock->GET_INFO("SquidSessionEngineExternalUrl");
	if(!is_numeric($SquidEnableSessionEngine)){$SquidEnableSessionEngine=0;}
	if(!is_numeric($SquidSessionEngineTimeOut)){$SquidSessionEngineTimeOut=3600;}
	
	$array[1800]="30Mn";
	$array[3600]="1h";
	$array[5400]="1h30";
	$array[7200]="2h";
	$array[14400]="4h";
	$array[28800]="8h";
	$array[43200]="12h";
	$array[86400]="1 {day}";
	$array[604800]="1 {week}";
	
	$t=time();
	
	$html="$error
	<div id='div-$t'>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{activate_session_engine}:</td>
		<td>". Field_checkbox("SquidEnableSessionEngine", 1,$SquidEnableSessionEngine,"SquidEnableSessionEngineCheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{session_max_time}:</td>
		<td>". Field_array_Hash($array,"SquidSessionEngineTimeOut", $SquidSessionEngineTimeOut,"style:font-size:14px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{external_webpage}:</td>
		<td>". Field_text("SquidSessionEngineExternalUrl",$SquidSessionEngineExternalUrl,"font-size:14px;width:300px")."</td>
	</tr>
	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SquidEnableSessionEngineSave()",16)."</td>
	</tr>
	
	</table>
	</div>
	<script>
		function SquidEnableSessionEngineCheck(){
			document.getElementById('SquidSessionEngineTimeOut').disabled=true;
			document.getElementById('SquidSessionEngineExternalUrl').disabled=true;
			if(document.getElementById('SquidEnableSessionEngine').checked){
				document.getElementById('SquidSessionEngineTimeOut').disabled=false;
				document.getElementById('SquidSessionEngineExternalUrl').disabled=false;			
			}
		}
		
	var x_SquidEnableSessionEngineSave$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}		
		RefreshTab('main_squid_sessions');
	}			
		
		function SquidEnableSessionEngineSave(){
			var XHR = new XHRConnection();
	  		XHR.appendData('SquidSessionEngineTimeOut',document.getElementById('SquidSessionEngineTimeOut').value);
	  		XHR.appendData('SquidSessionEngineExternalUrl',document.getElementById('SquidSessionEngineExternalUrl').value);
			if(document.getElementById('SquidEnableSessionEngine').checked){XHR.appendData('SquidEnableSessionEngine',1);}else{XHR.appendData('SquidEnableSessionEngine',0);}
			AnimateDiv('div-$t');
			XHR.sendAndLoad('$page', 'POST',x_SquidEnableSessionEngineSave$t);
			
		}
		
		
		
		SquidEnableSessionEngineCheck();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function settings_save(){
	$sock=new sockets();
	
	foreach ($_POST as $key=>$value){
		$sock->SET_INFO($key, $value);
	}
	
}


