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
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["SquidBubbleMode"])){SquidBubbleMode();exit;}

js();


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$YahooWin=2;
	if(isset($_GET["YahooWin"])){$YahooWin=$_GET["YahooWin"];$YahooWinUri="&YahooWin={$_GET["YahooWin"]}";}
	$title=$tpl->_ENGINE_parse_body("{HotSpot}");
	$html="
	var YahooWinx=$YahooWin;
	if(YahooWinx==2){
		YahooWin2Hide();
		YahooWin6Hide();
	}	
	YahooWin$YahooWin('700','$page?popup=yes$YahooWinUri','$title')";
	echo $html;
}

function popup(){
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$t=time();
	$EnableRemoteStatisticsAppliance=0;
	$SquidBubbleMode=$sock->GET_INFO('SquidBubbleMode');
	if(!is_numeric($SquidBubbleMode)){$SquidBubbleMode=0;}
	
	$html="
	
	<div id='$t-animate'></div>
	<div id='$t' class=explain style='font-size:14px'>{bubble_mode_explain}</div>
	<div id='$t' class=explain style='font-size:14px'>{advanced_settings_in_miniadm}</div>
	<table style='width:99%' class=form>
	<tr>
	<td colspan=2>". Paragraphe_switch_img("{activate_bubble_mode}","{activate_bubble_explain}",
		"SquidBubbleMode",$SquidBubbleMode,null,"450")."
			</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveHotSpot$t()","16px")."</td>
	</tr>
	</table>
	<script>
	var x_SaveHotSpot$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		document.getElementById('$t-animate').innerHTML='';
		Loadjs('squid.compile.progress.php');
		RefreshTab('squid_main_svc');
	
	}
	
	
		function SaveHotSpot$t(){
			var lock=$EnableRemoteStatisticsAppliance;
			if(lock==1){Loadjs('squid.newbee.php?error-remote-appliance=yes');return;}
			var XHR = new XHRConnection();
			XHR.appendData('SquidBubbleMode',document.getElementById('SquidBubbleMode').value);
			AnimateDiv('$t-animate');
			XHR.sendAndLoad('$page', 'POST',x_SaveHotSpot$t);
	}
	</script>
";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function SquidBubbleMode(){
	$sock=new sockets();
	$sock->SET_INFO("SquidBubbleMode", $_POST["SquidBubbleMode"]);
	
}