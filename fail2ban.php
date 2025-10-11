<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.iptables-chains.inc');
	include_once('ressources/class.resolv.conf.inc');


	if(isset($_GET["parameters"])){parameters();exit;}

function popup(){
	$users=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$EnablePostfixAutoBlockWhiteListed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixAutoBlockWhiteListed"));


	if(!$users->AsArticaAdministrator){
		echo FATAL_ERROR_SHOW_128($tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}"));
		die("DIE " .__FILE__." Line: ".__LINE__);
	}

	$array["parameters"]='{settings}';
	$array["tab-iptables-events"]='{events}';

	$fontsize=22;
	foreach ($array as $num=>$ligne){
		if($num=="tab-iptables-whlhosts"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"whitelists.admin.php?popup-hosts=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}

		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
	}


	echo build_artica_tabs($html, "fail2ban_tabs",1390);



}


function parameters(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	
	$EnableFail2Ban=$sock->GET_INFO("EnableFail2Ban");

	$form1=Paragraphe_switch_img("{enable_feature}",
			"{APP_FAIL2BAN_EXPLAIN}",'EnableFail2Ban',$EnableFail2Ban,
			"{enable_disable}",1060);
	
	
	$form="
	<div id='EnablePostfixAutoBlockDiv' class=form style='width:98%'>
		
		
	<table style='width:100%' >
	<tr>
	<td colspan=2>$form1</td>
	</tr>
	<tr>
	<td class=legend style=font-size:22px>{log_all_events}:</td>
	<td>". Field_checkbox_design("InstantIptablesEventAll",1,$InstantIptablesEventAll,"InstantIptablesEventAllSave()")."</td>
		</tr>
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","EnablePostfixAutoBlockDeny()",40)."</td>
			</tr>
		</table>
	</div>";
	
	
}