<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.system.network.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSystemAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}		
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["EnableDNSLinker"])){EnableDNSLinker();exit;}
	
	
	
js();


function js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{DNS_LINKER}");
	echo "YahooWin3('600','$page?popup=yes','$title')";
	
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$tcp=new networking();
	$EnableDNSLinker=$sock->GET_INFO("EnableDNSLinker");
	$EnableDNSLinkerCreds=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSLinkerCreds")));
	if(preg_match("#^(.+?):#", $EnableDNSLinkerCreds["CREDS"],$re)){$SuperAdmin=$re[1];}
	
	
	$hostname=$EnableDNSLinkerCreds["hostname"];
	$listen_port=$EnableDNSLinkerCreds["listen_port"];
	$listen_ip=$EnableDNSLinkerCreds["listen_addr"];
	$send_listen_ip=$EnableDNSLinkerCreds["send_listen_ip"];
	if(!is_numeric($EnableDNSLinker)){$EnableDNSLinker=0;}
	if(!is_numeric($listen_port)){$listen_port=9000;}
	$t=time();
	$p=Paragraphe_switch_img("{activate_dns_linker}", "{activate_dns_linker_text}","EnableDNSLinker",$EnableDNSLinker,null,500);
	$ips=$tcp->ALL_IPS_GET_ARRAY();
	
	$html="<table style='width:100%' class=form>
	<tr>
		<td colspan=2>$p</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{hostname}:</td>
		<td>". Field_text("hostname-$t",$hostname,"font-size:16px;width:99%")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{listen_port}:</td>
		<td>". Field_text("listen_port-$t",$listen_port,"font-size:16px;width:90px")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:16px'>{SuperAdmin}:</td>
		<td>". Field_text("SuperAdmin",$SuperAdmin,"font-size:16px;width:99%")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{password}:</td>
		<td>". Field_password("SuperAdminPass",null,"font-size:16px;width:70%")."</td>
	</tr>
		<tr>
			<td class=legend style='font-size:16px'>{listen_ip}:</td>
			<td>". Field_array_Hash($ips,"listen_addr-$t",$listen_ip,"style:font-size:16px;padding:3px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{send_listen_ip}:</td>
			<td>". Field_array_Hash($ips,"send_listen_ip-$t",$send_listen_ip,"style:font-size:16px;padding:3px")."</td>
		</tr>											
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()","18")."</td>
	</tr>								
	</table>		
	<script>
	
	var x_Save$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		YahooWin3Hide();
	}	
		
		function Save$t(){
			var SuperAdminPass=document.getElementById('SuperAdminPass').value;
			if(SuperAdminPass.length==0){
				alert('Please, define the password...\\n');
				return;
			}
			var pp=encodeURIComponent(document.getElementById('SuperAdminPass').value);
			var XHR = new XHRConnection();
			XHR.appendData('EnableDNSLinker',document.getElementById('EnableDNSLinker').value);
			XHR.appendData('SuperAdmin',document.getElementById('SuperAdmin').value);
			XHR.appendData('hostname',document.getElementById('hostname-$t').value);
			XHR.appendData('listen_port',document.getElementById('listen_port-$t').value);
			XHR.appendData('listen_addr',document.getElementById('listen_addr-$t').value);
			XHR.appendData('send_listen_ip',document.getElementById('send_listen_ip-$t').value);
			XHR.appendData('SuperAdminPass',pp);
			AnimateDiv('EnableDNSLinker_img');
			XHR.sendAndLoad('$page', 'POST',x_Save$t);			
		
		}
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function EnableDNSLinker(){
	$sock=new sockets();
	$_POST["SuperAdminPass"]=url_decode_special_tool($_POST["SuperAdminPass"]);
	$sock->SET_INFO("EnableDNSLinker", $_POST["EnableDNSLinker"]);
	
	$EnableDNSLinkerCreds["CREDS"]=$_POST["SuperAdmin"].":".md5($_POST["SuperAdminPass"]);
	$EnableDNSLinkerCreds["hostname"]=$_POST["hostname"];
	$EnableDNSLinkerCreds["listen_port"]=$_POST["listen_port"];
	$EnableDNSLinkerCreds["listen_addr"]=$_POST["listen_addr"];
	$EnableDNSLinkerCreds["send_listen_ip"]=$_POST["send_listen_ip"];
	
	
	
	$sock->SaveConfigFile(base64_encode(serialize($EnableDNSLinkerCreds)), "EnableDNSLinkerCreds");
	$sock->getFrameWork("system.php?dns-linker=yes");	
}