<?php
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die("DIE " .__FILE__." Line: ".__LINE__);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.awstats.inc');
	
	

	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["parameters"])){parameters();exit;}
	if(isset($_POST["TomcatEnable"])){parameters_save();exit;}
page();


function page(){
	
	$time=time();
	$page=CurrentPageName();
	$html="<div id='tomcat-$time'></div>
	<script>
		LoadAjax('tomcat-$time','$page?tabs=yes');
	</script>
	";
	
	echo $html;
	
}


function parameters(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$TomcatListenPort=$sock->GET_INFO("TomcatListenPort");
	$TomcatEnable=$sock->GET_INFO("TomcatEnable");
	$TomcatAdminName=$sock->GET_INFO("TomcatAdminName");
	$TomcatAdminPass=$sock->GET_INFO("TomcatAdminPass");
	if(!is_numeric($TomcatListenPort)){$TomcatListenPort=8080;}
	if(!is_numeric($TomcatEnable)){$TomcatEnable=1;}
	
	$users=new usersMenus();
	if($users->OPENEMM_INSTALLED){
		$OpenEMMEnable=$sock->GET_INFO("OpenEMMEnable");
		if(!is_numeric($OpenEMMEnable)){$OpenEMMEnable=1;}
		if($OpenEMMEnable==1){
			echo $tpl->_ENGINE_parse_body("<div class=explain>{OPENEMM_INSTALLED_ENABLED_FEATURE_DISABLED}</div>");
			return;
			
		}
	}
	
	
	$ldap=new clladp();
	$ueim="http://{$_SERVER["SERVER_ADDR"]}:$TomcatListenPort/manager/html/";
	if(($TomcatAdminName==null) OR ($TomcatAdminPass==null)) {$admin_text=$ldap->ldap_admin;}else{$admin_text=$TomcatAdminName;}
	
	$html="
	<div id='tomcatid'>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend>{enable}</td>
		<td>". Field_checkbox("TomcatEnable", 1,$TomcatEnable)."</td>
	</tr>
	<tr>
		<td class=legend>{listen_port}:</td>
		<td>". Field_text("TomcatListenPort",$TomcatListenPort,"font-size:14px;padding:3px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend>{admin}:</td>
		<td>". Field_text("TomcatAdminName",$TomcatAdminName,"font-size:14px;padding:3px;width:110px")."</td>
	</tr>
	<tr>
		<td class=legend>{password}:</td>
		<td>". Field_password("TomcatAdminPass",$TomcatAdminPass,"font-size:14px;padding:3px;width:110px")."</td>
	</tr>		
	<tr>
		<td class=legend>{tomcat_admin_interface}:</td>
		<td style='font-size:14px'><a href=\"$ueim\" target=_new>$ueim</a> ({use}:$ldap->ldap_admin)</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveTomCatParams()")."</td>
	</tr>
	</table>
	
	<script>
	
	var x_SaveTomCatParams=function (obj) {
			var results=obj.responseText;
			RefreshTab('main_config_tomcat');
		}	
		
		function SaveTomCatParams(){
			var XHR = new XHRConnection();
			if(document.getElementById('TomcatEnable').checked){XHR.appendData('TomcatEnable',1);}else{XHR.appendData('TomcatEnable',0);}
    		XHR.appendData('TomcatListenPort',document.getElementById('TomcatListenPort').value);
    		XHR.appendData('TomcatAdminName',document.getElementById('TomcatAdminName').value);
    		XHR.appendData('TomcatAdminPass',document.getElementById('TomcatAdminPass').value);
    		AnimateDiv('tomcatid');
    		XHR.sendAndLoad('$page', 'POST',x_SaveTomCatParams);
			
		}		
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function parameters_save(){
	$sock=new sockets();
	$sock->SET_INFO("TomcatListenPort", $_POST["TomcatListenPort"]);
	$sock->SET_INFO("TomcatEnable", $_POST["TomcatEnable"]);
	$sock->SET_INFO("TomcatAdminName", $_POST["TomcatAdminName"]);
	$sock->SET_INFO("TomcatAdminPass", $_POST["TomcatAdminPass"]);
	
	$sock->getFrameWork("services.php?restart-tomcat=yes");
}


function tabs(){
	$tpl=new templates();	
	$page=CurrentPageName();
	$users=new usersMenus();
	$array["parameters"]='{parameters}';
	
	foreach ($array as $num=>$ligne){
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_tomcat style='width:100%;height:600px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
		  $(document).ready(function() {
			$(\"#main_config_tomcat\").tabs();});
		</script>";		
	
}