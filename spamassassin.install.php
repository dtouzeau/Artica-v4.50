<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.clamav.inc');

	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	
	
js();
	
function js(){
	$usersmenus=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();		
	if($usersmenus->spamassassin_installed){return;}
	$title=$tpl->_ENGINE_parse_body("{APP_SPAMASSASSIN}");
	echo "YahooWin3('550','$page?popup=yes','$title')";
	
}	
	



function popup(){
	$usersmenus=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();			
		$html="
		<center>
		<table style='width:80%' class=form>
		<tr>
			<td valign='top' width=1%><img src='img/software-remove-128.png'></td>
			<td valign='top' width=99%>
			<p style='font-size:14px'>{APP_SPAMASSASSIN_TEXT}
			</p>
			<div style='text-align:right'><hr>". button("{install}","InstallSpamassassin()",18)."</div>
		</td>
		</tr>
		</table>
		</center>
			<script>
				function InstallSpamassassin(){
					Loadjs('setup.index.progress.php?product=APP_SPAMASSASSIN&start-install=yes');
					YahooWin3Hide();
				}
			</script>";
		echo $tpl->_ENGINE_parse_body($html);
}