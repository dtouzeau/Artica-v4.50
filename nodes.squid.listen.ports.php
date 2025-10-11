<?php
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}

	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.blackboxes.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsAnAdministratorGeneric){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die("DIE " .__FILE__." Line: ".__LINE__);	
}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["listenport"])){save();exit;}

js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{listen_port}");
	
		echo "
		YahooWin6(350,'$page?popup=yes&nodeid={$_GET["nodeid"]}','$title');
		
		var x_listenport= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
			YahooWin6Hide();
			if(document.getElementById('main_squid_quicklinks_tabs{$_GET["nodeid"]}')){RefreshTab('main_squid_quicklinks_tabs{$_GET["nodeid"]}');}
		}
		
		function listenport(){
			var XHR = new XHRConnection();
			XHR.appendData('listenport',document.getElementById('listen_port').value);
			XHR.appendData('second_listen_port',document.getElementById('second_listen_port').value);
			XHR.appendData('nodeid',{$_GET["nodeid"]});			
			XHR.sendAndLoad('$page', 'POST',x_listenport);	
		}		
		";	
}

function popup(){
	
	$squid=new squidnodes($_GET["nodeid"]);
	if(!is_numeric($squid->second_listen_port)){$squid->second_listen_port=0;}
	
$form="
		
		<table style='width:99%' class=form>
			<tr>
				<td class=legend nowrap style='font-size:16px;'>{listen_port}:</td>
				<td>" . Field_text('listen_port',$squid->listen_port,'width:95px;font-size:16px;padding:5px')."</td>
				<td width=1%>&nbsp;</td>
			</tr>
			<tr>
				<td class=legend nowrap style='font-size:16px;'>{second_port}:</td>
				<td>" . Field_text('second_listen_port',$squid->second_listen_port,'width:95px;font-size:16px;padding:5px')."</td>
				<td width=1%>". help_icon("{squid_second_port_explain}")."</td>
			</tr>			
			
			
			<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","listenport()",16)."</td>
			</tr>
		</table>			
		
		";

		
	$html="
			<div class=explain style='font-size:14px;'>{listen_port_text}</div>
				$form
			<br>
			
		";
		
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body($html,'squid.index.php');			
}


function save(){
	$squid=new squidnodes($_POST["nodeid"]);
	$squid->SET("listen_port",$_POST["listenport"]);
	$squid->SET("second_listen_port",$_POST["second_listen_port"]);
	$squid->SaveToLdap();
	
	
}