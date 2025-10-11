<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.cron.inc');
	include_once('ressources/class.backup.inc');

$user=new usersMenus();
	if($user->AsSystemAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["arkeia-status"])){Arkeia_Status();exit;}
	if(isset($_POST["EnableArkeia"])){EnableArkeiaSave();exit;}
tabs();
	

function tabs(){
	$tpl=new templates();
	$array["status"]='{status}';
	$page=CurrentPageName();
	
	foreach ($array as $num=>$ligne){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_arkeia style='width:100%;height:650px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_arkeia').tabs();
			
			
			});
		</script>";	
}

function status(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();
	$t=time();
	if(!$users->APP_ARKEIA_INSTALLED){
		
		$html="<center style='margin:70px'>
			<table style='width:98%' class=form>
			<tr>
				<td width=1% valign='top'><img src='img/error-128.png'></td>
				<td valign='top'><div style='font-size:18px'>{APP_ARKEIA_NOT_INSTALLED}</div></td>
			</tr>
			</table>
		";
		echo $tpl->_ENGINE_parse_body($html);
		return;
		
	}
	
	
	$EnableArkeia=$sock->GET_INFO("EnableArkeia");
	if(!is_numeric($EnableArkeia)){$EnableArkeia=0;}
	
	
	$form=Paragraphe_switch_img("{ACTIVATE_ARKEIA}", "{APP_ARKEIA_TXT}","EnableArkeia",$EnableArkeia,null,520);
	
	
	$t=time();
	$html="
	<div id='$t-an'>
	<table style='width:99%' class=form>
	<tr>
		<td colspan=2 style='font-size:18px;margin-bottom:10px'>{APP_ARKEIA}</td>
	</tr>
	<tr>
		<td width=1% valign='top'>
			<center style='margin:5px'><img src='img/arkeia-128.png'></center>
			<div style='margin'top' id='$t'></div>
			<div style='width:100%;text-align:right'>". imgtootltip("refresh-24.png","{refresh}","RefreshArkeiaStatus()")."</div>
	
		</td>
		<td width=99% style='margin-left:5px' valign='top'>
			$form
			<hr>
			<div style='text-align:right;width:100%'>". button("{apply}","SaveArkeiaEnable()",16)."</div>
			<br>
			<div class=explain style='font-size:13.5px'>{APP_ARKEIA_FREEWEB_HOWTO}</div>	
			<div style='text-align:right;margin-top:5px'><a href=\"javascript:blur();\" OnClick=\"javascript:QuickLinkSystems('section_freeweb');\" 
			style='font-size:16px;font-weight:bold;text-decoration:underline'>FreeWebs</a></div>
		</td>
	</tr>
	</table>
	</div>
	<script>
		function RefreshArkeiaStatus(){
			LoadAjax('$t','$page?arkeia-status=yes');
		}
	var x_SaveArkeiaEnable=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		RefreshTab('main_config_arkeia');
	}		


	function SaveArkeiaEnable(){
	  	var XHR = new XHRConnection();
	  	XHR.appendData('EnableArkeia',document.getElementById('EnableArkeia').value);
	  	AnimateDiv('$t-an');
	  	XHR.sendAndLoad('$page', 'POST',x_SaveArkeiaEnable);
	}
	RefreshArkeiaStatus();
	
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}


function Arkeia_Status(){

	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();	
	$datas=$sock->getFrameWork("services.php?arkeia-ini-status=yes");
	writelogs(strlen($datas)." bytes for apache status",__CLASS__,__FUNCTION__,__FILE__,__LINE__);
	$ini->loadString(base64_decode($datas));
	$serv[]=DAEMON_STATUS_ROUND("APP_ARKEIAD",$ini,null,0);
	$serv[]=DAEMON_STATUS_ROUND("APP_ARKWSD",$ini,null,0);
	echo $tpl->_ENGINE_parse_body(@implode($serv, "<br>"));
	
}

function EnableArkeiaSave(){
	$sock=new sockets();
	$sock->SET_INFO("EnableArkeia", $_POST["EnableArkeia"]);
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
	$sock->getFrameWork("services.php?restart-arkeia=yes");
	
}

