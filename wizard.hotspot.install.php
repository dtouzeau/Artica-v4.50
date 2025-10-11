<?php
	if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PROCESS"]=true;$GLOBALS["VERBOSE_SYSLOG"]=true;}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.langages.inc');
	include_once('ressources/class.sockets.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.privileges.inc');
	include_once('ressources/class.browser.detection.inc');
	include_once('ressources/class.resolv.conf.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.squid.inc');
	
	// back = LoadAjax('setup-content','$page?setup-proxy-type=yes&savedsettings='+results)
	if(isset($_GET["setup-2"])){setup_2();exit;}
	if(isset($_GET["setup-3"])){setup_3();exit;}
	
	if(isset($_POST["ArticaHotSpotInterface"])){ArticaHotSpotInterface();exit;}
	if(isset($_POST["ArticaHotSpotInterface2"])){ArticaHotSpotInterface2();exit;}
	if(isset($_POST["ArticaHotSpotProvideDHCPDNS"])){ArticaHotSpotProvideDHCPDNS();exit;}
	if(isset($_POST["SaveHD"])){SaveHD();exit;}
	setup_1();	
	
	
function ArticaHotSpotInterface(){
	$sock=new sockets();
	$sock->SET_INFO("ArticaHotSpotInterface", $_POST["ArticaHotSpotInterface"]);
}
function ArticaHotSpotInterface2(){
	$sock=new sockets();
	$sock->SET_INFO("ArticaHotSpotInterface2", $_POST["ArticaHotSpotInterface2"]);
}
function ArticaHotSpotProvideDHCPDNS(){
	$sock=new sockets();
	$sock->SET_INFO("ArticaHotSpotProvideDHCPDNS", 1);	
}
	
function setup_1(){
		$page=CurrentPageName();
		$users=new usersMenus();
		$tpl=new templates();
		$net=new networking();
		$sock=new sockets();
		$q=new mysql();
		$interfaces=$net->Local_interfaces();
		$t=$_GET["t"];
		$savedsettings=$_GET["savedsettings"];
		$savedsettings_enc=urlencode($savedsettings);
	
		unset($interfaces["lo"]);
		$ArticaHotSpotInterface=$sock->GET_INFO("ArticaHotSpotInterface");
	
		foreach ($interfaces as $eth){
			$nic=new system_nic($eth);
			if($nic->enabled==0){continue;}
			$array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
	
		}
	
		if(count($array)<2){
			echo FATAL_ERROR_SHOW_128("{error_hotspot_2nics}<center>".button("{cancel}", "LoadAjax('setup-content','wizard.install.php?setup-proxy-type=yes&savedsettings=$savedsettings_enc')",40)."</center>");
			return;
		}
	
	
		$html="
	<div style='font-size:45px;margin-bottom:30px'>{hotspot_network}...</div>
	<div style='font-size:22px;margin-bottom:30px' class=explain>{hotspot_interface_explain}</div>
	
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:26px'>{network_interface}:</td>
		<td>". Field_array_Hash($array, "ArticaHotSpotInterface-$t",$ArticaHotSpotInterface,"style:font-size:26px")."</td>
	</tr>
	<tr>
		<td align='left' style='padding-top:50px'>". button("{cancel}","LoadAjax('setup-content','wizard.install.php?setup-proxy-type=yes&savedsettings=$savedsettings_enc')",26)."</td>
		<td align='right' style='padding-top:50px'>". button("{next}","SaveF$t()",26)."</td>
	</tr>
	</table>
	</div>
	<script>
	var xSaveF$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		LoadAjax('setup-content','$page?setup-2=yes&t=$t&savedsettings=$savedsettings_enc');
	}
	
	function SaveF$t(){
	var XHR = new XHRConnection();
	XHR.appendData('savedsettings','$savedsettings');
	XHR.appendData('ArticaHotSpotInterface',document.getElementById('ArticaHotSpotInterface-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSaveF$t);
	}
	</script>
	";
		echo $tpl->_ENGINE_parse_body($html);
}
function setup_2(){
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$net=new networking();
	$sock=new sockets();
	$q=new mysql();
	$interfaces=$net->Local_interfaces();
	$t=$_GET["t"];
	$savedsettings=$_GET["savedsettings"];
	$savedsettings_enc=urlencode($savedsettings);


	unset($interfaces["lo"]);
	$ArticaHotSpotInterface=$sock->GET_INFO("ArticaHotSpotInterface");
	$ArticaHotSpotInterface2=$sock->GET_INFO("ArticaHotSpotInterface2");

	foreach ($interfaces as $eth){
		if($eth==$ArticaHotSpotInterface){continue;}
		$nic=new system_nic($eth);
		if($nic->enabled==0){continue;}
		$array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";

	}




	$html="
<div style='font-size:45px;margin-bottom:30px'>{hotspot_wan_network}...</div>
<div style='font-size:22px;margin-bottom:30px' class=explain>{hotspot_wan_network_explain}</div>

<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:26px'>{network_interface}:</td>
		<td>". Field_array_Hash($array, "ArticaHotSpotInterface2-$t",$ArticaHotSpotInterface2,"style:font-size:26px")."</td>
	</tr>
	<tr>
		<td align='left' style='padding-top:50px'>". button("{cancel}","LoadAjax('setup-content','wizard.install.php?setup-proxy-type=yes&savedsettings=$savedsettings_enc')",26)."</td>
		<td align='right' style='padding-top:50px'>". button("{next}","SaveF$t()",26)."</td>
	</tr>
	</table>
</div>
<script>
	var xSaveF$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	LoadAjax('setup-content','$page?setup-3=yes&t=$t&savedsettings=$savedsettings_enc');
}

function SaveF$t(){
var XHR = new XHRConnection();
XHR.appendData('ArticaHotSpotInterface2',document.getElementById('ArticaHotSpotInterface2-$t').value);
XHR.sendAndLoad('$page', 'POST',xSaveF$t);
}
</script>
";
	echo $tpl->_ENGINE_parse_body($html);


}
function setup_3(){
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$net=new networking();
	$sock=new sockets();
	$q=new mysql();
	
	$t=$_GET["t"];
	$savedsettings=$_GET["savedsettings"];
	$savedsettings_enc=urlencode($savedsettings);
	

	unset($interfaces["lo"]);
	$ArticaHotSpotInterface=$sock->GET_INFO("ArticaHotSpotInterface");
	$ArticaHotSpotInterface2=$sock->GET_INFO("ArticaHotSpotInterface2");





	$html="
<div style='font-size:45px;margin-bottom:30px'>{hotspot_dhcp_dns}...</div>
<div style='font-size:22px;margin-bottom:30px' class=explain>{hotspot_dhcp_dns_explain}</div>

<div style='width:98%' class=form>
	<center style='margin:20px'>". button("{yes_want_dhcp_dns}", "SaveYes$t()",30)."</center>
	<center style='margin:20px'>". button("{no_want_dhcp_dns}", "SaveNo$t()",30)."</center>		
	<tr>
		<td align='left' style='padding-top:50px'>". button("{cancel}","LoadAjax('setup-content','wizard.install.php?setup-proxy-type=yes&savedsettings=$savedsettings_enc')",26)."</td>
		<td align='right' style='padding-top:50px'>&nbsp;</td>
	</tr>
	</table>
</div>
<script>
	var xSaveF$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		LoadAjax('setup-content','wizard.install.php?setup-features=yes&savedsettings=$savedsettings_enc');
}

function SaveYes$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ArticaHotSpotProvideDHCPDNS','yes');
	XHR.sendAndLoad('$page', 'POST',xSaveF$t);
}
function SaveNo$t(){
	LoadAjax('setup-content','wizard.install.php?setup-features=yes&savedsettings=$savedsettings_enc');
}
</script>
";
	echo $tpl->_ENGINE_parse_body($html);


}