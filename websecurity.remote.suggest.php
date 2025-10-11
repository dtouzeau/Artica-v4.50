<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.artica.inc');
include_once(dirname(__FILE__).'/ressources/class.rtmm.tools.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.dansguardian.inc');

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["HIDE"])){HIDE();exit;}
js();
function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{suggest_remote_statistics_appliance}");
	$t=time();
	echo "

	function Start$t(){
	RTMMail('800','$page?popup=yes','$title');

}
Start$t();";



}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$html="<div style='font-size:28px;margin-bottom:20px;font-weight:bold'>{suggest_remote_security_appliance}</div>
	<div style='font-size:18px;margin-bottom:20px' class=explain>{suggest_remote_security_appliance_explain}</div>		
			
	<center style='margin:20px'>
	<a href=\"http://artica-proxy.com/?p=2972\" style='font-size:22px;text-decoration:underline;color:black' target=_new>Doc: The Security Appliance</a>
	</center>
			
			
	<center style='margin:20px'>". button("{connect}", "GotoSecurityAppliance();",26)."</center>			
			
	<center style='margin:20px'>
	<a href=\"http://sourceforge.net/projects/artica-squid/files/ISO/antimalware-appliance/\" 
			style='font-size:22px;text-decoration:underline;color:black' target=_new>{download_the_iso}</a>
	<div style='text-align:right;margin-top:60px'>
			". button("{hide_info_def}","Hide$t()",18)."</div>
			
	</center>
<script>
var xHide$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	RTMMailHide();
}
	
function Hide$t(){
	var XHR = new XHRConnection();
	XHR.appendData('HIDE', 1);
	XHR.sendAndLoad('$page', 'POST',xHide$t);
}
</script>	
";
	

	
	echo $tpl->_ENGINE_parse_body($html);
}

function HIDE(){
	$sock=new sockets();
	$sock->SET_INFO("WebsecurityApplianceInfo", 1);
	
}
