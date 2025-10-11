<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.rtmm.tools.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.dansguardian.inc');
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
$user=new usersMenus();
if(!$user->AsWebStatisticsAdministrator){
	$tpl=new templates();
	echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}").");";
	exit;

}

if(isset($_GET["popup"])){popup();exit;}


js();

function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{APP_URLSNARF}");
	$html="YahooWin4(650,'$page?popup=yes";
	echo $html;
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$URLSnarfEnabled=$sock->GET_INFO("URLSnarfEnabled");
	if(!is_numeric($URLSnarfEnabled)){$URLSnarfEnabled=0;}
	$t=time();
	$p=Paragraphe_switch_img("{ACTIVARE_URL_SNIFFING}", "{ACTIVARE_URL_SNIFFING_TEXT}","URLSnarfEnabled-$t",$URLSnarfEnabled,null,550);
	
	
	$html="
	<center id='div-$t'></center>
	$p
	<hr>
	<div style='width:100%;text-align:right'>". button("{apply}","Save$t()","16px")."</div>
<script>
	var x_Save$t= function (obj) {
		document.getElementById('div-$t').innerHTML='';
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		YahooWin3Hide();
	}	
		
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('URLSnarfEnabled',document.getElementById('URLSnarfEnabled-$t').value);
		AnimateDiv('div-$t');
		XHR.sendAndLoad('$page', 'POST',x_Save$t);
		}
	</script>						
			
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}