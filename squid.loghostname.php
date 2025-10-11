<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["EnableLogHostnames"])){save();exit;}
	js();
	
	
function js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{log_hostnames}");
	$html="YahooWin2('890','$page?popup=yes','$title');";
	echo $html;	
	
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$EnableLogHostnames=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLogHostnames"));
	
	$p=Paragraphe_switch_img("{log_hostnames}", 
			"{log_hostnames_explain}","EnableLogHostnames",$EnableLogHostnames,null,850);
	
	$html="
	<div id='$t'></div>
	<table style='width:99%' class=form>
	<tr>
		
		<td colspan=2>$p</td>
	</tr>
	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",32)."</td>
	</tr>
	</table>
	
	<script>
		var x_Save$t= function (obj) {
			document.getElementById('$t').innerHTML='';
			var res=obj.responseText;
			if (res.length>3){alert(res);}			
			YahooWin2Hide();
			Loadjs('squid.compile.progress.php');
		}
	
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('EnableLogHostnames',document.getElementById('EnableLogHostnames').value);
		XHR.sendAndLoad('$page', 'POST',x_Save$t);
	}
</script>
	
	";
	
echo $tpl->_ENGINE_parse_body($html);	
	
	
	
}

function save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableLogHostnames", $_POST["EnableLogHostnames"]);
	
		
}

