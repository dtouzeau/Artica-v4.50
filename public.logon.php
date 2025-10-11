<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.browser.detection.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	
	
	if(isset($_GET["login"])){login();exit;}
	
js();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$widownsize=995;
	$t=$_GET["t"];
	$title=$tpl->_ENGINE_parse_body("{logon}");
	$html="YahooWinBrowse('750','$page?login=yes','$title');";
	echo $html;
	
}


function login(){
	$page=CurrentPageName();
	$tpl=new templates();
	$failed=$tpl->javascript_parse_text("{failed}");
	$t=time();
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	". Field_text_table("username-$t","{username}",null,26,"script:SaveCheck$t(event)",400).
	   Field_password_table("password-$t","{password}",null,26,"script:SaveCheck$t(event)",400).
	Field_button_table_autonome("{submit}", "Save$t",32)."
			
			
	</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length==0){alert('FATAL ERROR');return;};
	if(results=='FALSE'){
		alert('$failed');
		return;
	}
	AjaxTopMenu('template-top-menus','admin.top.menus.php');
	YahooWinBrowseHide();
}

function SaveCheck$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}

function Save$t(){
	var XHR = new XHRConnection();
	var pp=encodeURIComponent(document.getElementById('password-$t').value);
	XHR.appendData('artica_password',pp);
	XHR.appendData('artica_username',document.getElementById('username-$t').value);
	XHR.appendData('VIA_API','1');
	XHR.sendAndLoad('logon.php', 'POST',xSave$t);
}
</script>
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
