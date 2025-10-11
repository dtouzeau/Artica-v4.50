<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.reverse.inc');
	include_once('ressources/class.squidguard-msmtp.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	$user=new usersMenus();
	if(!$user->AsDnsAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}").");";
		exit;
		
	}

	
	

	if(isset($_POST["PowerAdminListenPort"])){save();exit;}




popup();



function popup(){
	$page=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();
	$t=time();
	$LICENSE=0;
	if($users->CORP_LICENSE){$LICENSE=1;}

	$powerDNSVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSVersion");
	$PowerAdminListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerAdminListenPort"));
	if($PowerAdminListenPort==0){$PowerAdminListenPort=9393;}
	$PowerAdminCertificateName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerAdminCertificateName"));
	
	$squid_reverse=new squid_reverse();
	$sslcertificates=$squid_reverse->ssl_certificates_list();
	$button= button("{apply}","SaveSquidGuardHTTPService$t()",40);

	$version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerAdminVersion");
	
	
	$html="
	<div style='font-size:30px;'>".texttooltip("{DNS_SERVICE} v$powerDNSVersion","{dashboard}","GotoPowerDNS()")."&nbsp;|&nbsp;{APP_POWERADMIN} v$version</div>
	<div style='font-size:26px;margin-bottom:30px;text-align:right'><a href='https://{$_SERVER["SERVER_ADDR"]}:$PowerAdminListenPort' target=_new style='text-decoration:underline;color:black'>https://{$_SERVER["SERVER_ADDR"]}:$PowerAdminListenPort</a>
	<p>&nbsp;</p>
	<center style='width:100%;margin:30px'>". button("{disable_feature}", "Loadjs('poweradmin.disable.php')",30)."</center>
	
	<div style='width:98%' class=form>
	
	
	
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:24px'>{listen_port} (SSL):</td>
		<td>". Field_text("PowerAdminListenPort",$PowerAdminListenPort,"font-size:24px;padding:3px;width:110px",null,null,null,false,"")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:24px'>{certificate}:</td>
		<td>". Field_array_Hash($sslcertificates,"PowerAdminCertificateName",$PowerAdminCertificateName,
				"style:font-size:24px;padding:3px;width:75%",null,null,null,false,"")."</td>
	</tr>				
			
	<tr>
		<td colspan=2 align='right'><hr>".$button."</td>
	</tr>	
	</table>
	</div>
<script>

		
var x_SaveSquidGuardHTTPService$t=function(obj){
	 	LoadAjaxRound('poweradmin','poweradmin.php');
}

function SaveSquidGuardHTTPService$t(){
     var XHR = new XHRConnection();
     
     XHR.appendData('PowerAdminListenPort',document.getElementById('PowerAdminListenPort').value);
  	 XHR.appendData('PowerAdminCertificateName',document.getElementById('PowerAdminCertificateName').value);
     
     
     XHR.sendAndLoad('$page', 'POST',x_SaveSquidGuardHTTPService$t);     	
}

</script>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function save(){
	
	$sock=new sockets();

	foreach ($_POST as $key=>$value){
		$sock->SET_INFO($key, $value);
	}
	
	$sock->getFrameWork("pdns.php?reload-poweradmin=yes");

	
	
	
}


?>