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
if(isset($_POST["squid_transparent"])){save();exit;}

js();

function js(){
		$page=CurrentPageName();
		$tpl=new templates();
		$transparent_mode=$tpl->_ENGINE_parse_body("{transparent_mode}");
		echo "YahooWin6(450,'$page?popup=yes&nodeid={$_GET["nodeid"]}','$transparent_mode');";
}

function save(){
	$squid=new squidnodes($_POST["nodeid"]);
	$squid->SET("hasProxyTransparent", $_POST["squid_transparent"]);
	$squid->SaveToLdap();
}



function popup(){
	
	$page=CurrentPageName();
	$squid=new squidnodes($_GET["nodeid"]);
	$t=time();
	$field=Paragraphe_switch_img('{transparent_mode}','{transparent_mode_text}',"squid_transparent$t",$squid->GET("hasProxyTransparent"),null,350);
	$html="
	
	<div id='squid_transparentdiv$t'>
		<div style='float:right'>". help_icon("{transparent_mode_limitations}")."</div><div class=explain>{transparent_mode_explain}</div>
		<table style='width:99%' class=form>
			<tr>
				<td colspan=2>$field</td>
			</tr>
			<td colspan=2 align='right'><hr>". button("{apply}","SaveTransparentProxy$t();",18)."</tD>
		</table>
	</div>
	
	<script>
	
	var x_SaveTransparentProxy= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		YahooWin6Hide();
		if(document.getElementById('main_squid_quicklinks_tabs{$_GET["nodeid"]}')){RefreshTab('main_squid_quicklinks_tabs{$_GET["nodeid"]}');}
	}	
	
	function SaveTransparentProxy$t(){
		var XHR = new XHRConnection();
		XHR.appendData('nodeid',{$_GET["nodeid"]});
		XHR.appendData('squid_transparent',document.getElementById('squid_transparent$t').value);
		AnimateDiv('squid_transparentdiv$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveTransparentProxy);		
		}	
	
	
	</script>
	";
	
$tpl=new templates();
		echo $tpl->_ENGINE_parse_body($html,'squid.index.php');	
	
}