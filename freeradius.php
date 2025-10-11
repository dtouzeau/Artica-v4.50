<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.sqlgrey.inc');
	include_once('ressources/class.main_cf.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}
	
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["service-status"])){services_status();exit;}
	if(isset($_GET["service-toolbox"])){services_toolbox();exit;}
	
	if(isset($_POST["EnableFreeRadius"])){EnableFreeRadius();exit;}
	if(isset($_GET["testauth-js"])){testauth_js();exit;}
	if(isset($_GET["testauth"])){testauth();exit;}
	if(isset($_POST["TESTAUTHUSER"])){testauth_perform();exit;}
	
	if(isset($_POST["apply-config"])){apply_config();exit;}

	
tabs();


function testauth_js(){
	$t=$_GET["t"];
	$tpl=new templates();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{test_auth}");	
	echo "YahooWin2('440','$page?testauth=yes&t=$t','$title')";
}


function tabs(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$q=new mysql();
	
		$array["status"]='{status}';
		$array["auth"]='{databases_users}';
		$array["profiles"]='{connections_profiles}';
		$array["users"]='{internal_users}';
		$array["events"]='{events}';
		
		
		
		$fontsize=14;
		if($tpl->language=="fr"){
			if(count($array)>7){
				$fontsize=12;
			}
			
		}
		
	foreach ($array as $num=>$ligne){
		if($num=="auth"){
			$tab[]="<li><a href=\"freeradius.auth.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		if($num=="profiles"){
			$tab[]="<li><a href=\"freeradius.clients.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}

		if($num=="events"){
			$tab[]="<li><a href=\"syslog.php?popup=yes&force-prefix=freeradius&TB_EV=682\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}

		if($num=="users"){
			$tab[]="<li><a href=\"freeradius.users.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}		
		
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
		}
	
	
	

	$html="
		<div id='main_freeradius_tabs' style='background-color:white;margin-top:10px;width:910px'>
		<ul>
		". implode("\n",$tab). "
		</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_freeradius_tabs').tabs();
			

			});
		</script>
	
	";	
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function status(){
	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();	
	$t=time();
	$html="<table style='width:100%'>
	<tr>
		<td valign='top' style='width:1%'>
			<div id='$t-status'></div>
			
			<div style='width:100%;margin-top:10px;text-align:right'>". imgtootltip("20-refresh.png","{refresh}","LoadAjaxTiny('$t-status','$page?service-status=yes');")."</div>
			</td>
		<td valign='top' style='width:1%'><div id='$t-toolbox'></div></td>
	</tr>
	</table>
	<script>
		LoadAjaxTiny('$t-status','$page?service-status=yes');
		LoadAjaxTiny('$t-toolbox','$page?service-toolbox=yes');		
	</script>
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);

}

function testauth_perform(){
	$sock=new sockets();
	$TESTAUTHPASS=urlencode(base64_encode(url_decode_special_tool($_POST["TESTAUTHPASS"])));
	if($TESTAUTHPASS==null){
		echo "Please set a password";
		return;
	}
	$TESTAUTHUSER=urlencode(base64_encode($_POST["TESTAUTHUSER"]));
	echo(base64_decode($sock->getFrameWork("freeradius.php?test-auth=yes&username=$TESTAUTHUSER&password=$TESTAUTHPASS")));
	
	
	
}

function services_status(){
	
	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();	
	$datas=base64_decode($sock->getFrameWork("freeradius.php?status=yes"));
	
	$ini->loadString($datas);	
	$APP_FREERADIUS=DAEMON_STATUS_ROUND("APP_FREERADIUS",$ini,null,0);

	$table_status="<table style='width:99%' class=form>
	<tr>
	<td>$APP_FREERADIUS</td>
	</tr>	
	</table>
	<script>
	";	
	
	echo $tpl->_ENGINE_parse_body($table_status);
}

function services_toolbox(){
	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();	
	$EnableFreeRadius=$sock->GET_INFO("EnableFreeRadius");
	if(!is_numeric($EnableFreeRadius)){$EnableFreeRadius=0;}

	$t=time();
	$p=Paragraphe_switch_img("{activate_freeradius}","{activate_freeradius_explain}","EnableFreeRadius",$EnableFreeRadius,null,550);
	
	
	
	//$tr[]=Paragraphe32("watchdog", "watchdog_klms8_text", "Loadjs('klms8.watchdog.php')", "watchdog-32.png");
	$tr[]=Paragraphe32("connections_settings", "connections_settings_text", "Loadjs('freeradius.network.php')", "folder-network-32.png");
	//$tr[]=Paragraphe32("license_info", "license_info_text", "Loadjs('klms.license.php')", "kl-license-32.png");
	//$tr[]=Paragraphe32("mta_link", "mta_link_text", "Loadjs('klms.mta.php')", "comut-32.png");
	//$tr[]=Paragraphe32("apply_config", "apply_klms_config_text", "ApplyConfigKLMS()", "32-settings-refresh.png");
	$tr[]=Paragraphe32("test_auth", "test_auth_text", "Loadjs('$page?testauth-js=yes')", "32-key.png");
	
	
	
	
	$table=CompileTr2($tr);
	
	$html="
	<div style='width:98%' class=form id='$t'>$p
		<div style='text-align:right'>". button("{apply}","EnableFreeRadius()",16)."</div>
	</div>
	<hr>
	$table
	<script>
		var X_applycf$t= function (obj) {
 			var tempvalue=obj.responseText;
	      	if(tempvalue.length>3){alert(tempvalue);}
	      	RefreshTab('main_freeradius_tabs');
		}
			
		function ApplyConfigKLMS(){
			var XHR = new XHRConnection();
			XHR.appendData('apply-config','yes');
			XHR.sendAndLoad('$page', 'POST',X_applycf);	
		}	

		function EnableFreeRadius(){
			var XHR = new XHRConnection();
			XHR.appendData('EnableFreeRadius',document.getElementById('EnableFreeRadius').value);
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',X_applycf$t);			
		}
	
	</script>
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function testauth(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$t=time();



	$html="
	<div id='test-$t'></div>
	<table style='width:99%' class=form>
	<tr>
	<tr>
	<td class=legend  style='font-size:16px'>{username}:</td>
	<td>". Field_text("TESTAUTHUSER",
			$_SESSION["TESTAUTHUSER"],"font-size:16px;padding:3px;width:190px",
			null,null,null,false,"TestAuthPerformCk$t(event)")."</td>
	</tr>
	<tr>
		<td class=legend  style='font-size:16px'>{password}:</td>
		<td>". Field_password("TESTAUTHPASS",
				$_SESSION["TESTAUTHUSERPASS"],"font-size:16px;padding:3px;width:190px",
			null,null,null,false,"TestAuthPerformCk$t(event)")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>&nbsp;</td>
		</tr>					
	<tr>
		<td colspan=2 align='right'><hr>". button("{submit}","TestAuthPerform$t()",18)."</td>
		</tr>
		</table>

		<script>
var x_TestAuthPerform$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		document.getElementById('test-$t').innerHTML='';
			
}
function TestAuthPerformCk$t(e){
	if(checkEnter(e)){ TestAuthPerform$t();}
}

function TestAuthPerform$t(){
	var pp=encodeURIComponent(document.getElementById('TESTAUTHPASS').value);
	var XHR = new XHRConnection();
	XHR.appendData('TESTAUTHUSER',document.getElementById('TESTAUTHUSER').value);
	XHR.appendData('TESTAUTHPASS',pp);
	AnimateDiv('test-$t');
	XHR.sendAndLoad('$page', 'POST',x_TestAuthPerform$t);
}
</script>

";

echo $tpl->_ENGINE_parse_body($html);
}


function EnableFreeRadius(){
	$sock=new sockets();
	$sock->SET_INFO("EnableFreeRadius", $_POST["EnableFreeRadius"]);
	$sock->getFrameWork("cmd.php?restart-artica-status");
	$sock->getFrameWork("freeradius.php?restart=yes");
	
}

function apply_config(){
	$sock=new sockets();
	$sock->getFrameWork("klms.php?apply-config=yes");	
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{service_reloaded_in_background_mode}",1);
	
}