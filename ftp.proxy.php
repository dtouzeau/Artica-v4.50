<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.groups.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.ActiveDirectory.inc');


$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die("DIE " .__FILE__." Line: ".__LINE__);
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["EnableFTPProxy"])){EnableFTPProxy();exit;}

js();


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{APP_FTP_PROXY}");
	echo "YahooWin3('700','$page?popup=yes','$title')";
	
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$EnableFTPProxy=$sock->GET_INFO('EnableFTPProxy');
	if(!is_numeric($EnableFTPProxy)){$EnableFTPProxy=0;}
	$t=time();
	$p=Paragraphe_switch_img("{enable_ftp_proxy_service}", 
			"{enable_ftp_proxy_service_text}",
			"EnableFTPProxy",$EnableFTPProxy,null,550);
	
	
	$html="
	<div id='$t'></div>
	<div style='width:98%' class=form>
			$p
	<div style='width:100%;text-align:right'>".button("{apply}","Save$t();",18)."</div>
	<script>
		var x_Save$t= function (obj) {
			document.getElementById('$t').innerHTML='';
			var results=obj.responseText;
			if(results.length>5){alert(results);}
			RefreshTab('squid_main_svc');
		}
	
	
		function Save$t(){
			 var XHR = new XHRConnection();
			 XHR.appendData('EnableFTPProxy', document.getElementById('EnableFTPProxy').value);
			 AnimateDiv('$t');
			 XHR.sendAndLoad('$page', 'POST',x_Save$t);	
		}
			
			
	</script>		
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function EnableFTPProxy(){
	$sock=new sockets();
	$sock->SET_INFO("EnableFTPProxy", $_POST["EnableFTPProxy"]);
	$sock->getFrameWork("ftpproxy.php?init=yes");
	$sock->getFrameWork("ftpproxy.php?restart=yes");
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
}



