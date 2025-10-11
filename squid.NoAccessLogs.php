<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["SquidNoAccessLogs"])){save();exit;}
	js();
	
	
function js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$html="YahooWin2('890','$page?popup=yes','{access_log}');";
	echo $html;	
	
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$SquidNoAccessLogs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNoAccessLogs"));
	$t=time();
	
	if($SquidNoAccessLogs==0){$enabled=1;}else{$enabled=0;}
	
	
	$html="
	<div id='$t' style='width:98%' class=form>
	<table style='width:99%'>
	<tr>
		<td colspan=2>". Paragraphe_switch_img("{activate} {access_log}", "{squid_access_log_text}",
				"ENABLE-$t",$enabled,null,700,"blur()")."</td>
		
	</tr>
		
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveSyslogSquid$t()",28)."</td>
	</tr>
	</table>
	
	<script>
		var x_SaveSyslogSquid$t= function (obj) {
			Loadjs('squid.compile.progress.php');
			YahooWin2Hide();
			if(document.getElementById('squid-status')){
				LoadAjax('squid-status','squid.main.quicklinks.php?status=yes');
			}
		}
	
	
	function SaveSyslogSquid$t(){
		var XHR = new XHRConnection();
		
		XHR.appendData('SquidNoAccessLogs',document.getElementById('ENABLE-$t').value);
		XHR.sendAndLoad('$page', 'POST',x_SaveSyslogSquid$t);
	}


</script>
	
	";
	
echo $tpl->_ENGINE_parse_body($html);	
	
	
	
}

function save(){
	$squid=new squidbee();
	$sock=new sockets();
	if($_POST["SquidNoAccessLogs"]==1){$_POST["SquidNoAccessLogs"]=0;}else{$_POST["SquidNoAccessLogs"]=1;}
	$sock->SET_INFO("SquidNoAccessLogs", $_POST["SquidNoAccessLogs"]);
	
	
}

