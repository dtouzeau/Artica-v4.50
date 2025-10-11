<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsArticaAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}		
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["NoIPHostname"])){Save();exit;}
js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{APP_NOIP}");
	$html="YahooWin2('550','$page?popup=yes','$title')";
	echo $html;
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$Config=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoipConf")));
	$EnableNoIpService=$sock->GET_INFO("EnableNoIpService");
	if(!is_numeric($EnableNoIpService)){$EnableNoIpService=0;}
	
	$p=Paragraphe_switch_img("{enable_no_ip_service}", "{enable_no_ip_service_text}","EnableNoIpService",$EnableNoIpService,null,390);
	
	$html="
	<div id='NoIPDiv'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td colspan=2>$p</td>
	</tr>
	<tr>
		<td class=legend>{hostname}:</td>
		<td>". Field_text("NoIPHostname",$Config["NoIPHostname"],"font-size:14px;width:220px")."</td>
	</tr>
		<tr>
		<td class=legend>{username}:</td>
		<td>". Field_text("NoIPUsername",$Config["NoIPUsername"],"font-size:14px;width:160px")."</td>
	</tr>
	</tr>
		<tr>
		<td class=legend>{password}:</td>
		<td>". Field_password("NoIPPassword",$Config["NoIPPassword"],"font-size:14px;width:160px")."</td>
	</tr>	
	<tr>
		<td colspan=2 align=right><hr>". button("{apply}","SaveNoIPForm()")."</td>
	</tr>
	<Tbody>
	</table>
	</div>
	<script>
 
		function x_SaveNoIPForm(obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);}
			YahooWin2Hide();
		}
			
		  
		 function SaveNoIPForm(){  
		    var XHR = new XHRConnection();
			XHR.appendData('NoIPHostname',document.getElementById('NoIPHostname').value);
			XHR.appendData('NoIPUsername',document.getElementById('NoIPUsername').value);
			XHR.appendData('NoIPPassword',document.getElementById('NoIPPassword').value);
			XHR.appendData('EnableNoIpService',document.getElementById('EnableNoIpService').value);
			AnimateDiv('NoIPDiv');
			XHR.sendAndLoad('$page', 'POST',x_SaveNoIPForm);
		 
		 }	
</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function Save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableNoIpService", $_POST["EnableNoIpService"]);
	$sock->SaveConfigFile(base64_encode(serialize($_POST)),"NoipConf");
	
	
}