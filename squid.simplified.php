<?php


if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.ldap.inc');
include_once('ressources/class.tcpip.inc');
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.squid.inc');

if(isset($_GET["page"])){page();exit;}
if(isset($_POST["SquidSimpleConfig"])){SquidSimpleConfig();exit;}
js();


function js(){
	header("content-type: application/javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{simplified_parameters}");
	echo "YahooWin6('850','$page?page=yes','$title')";
	
	
	
	
}

function page(){
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$button_reconfigure=button("{reconfigure}","Loadjs('squid.compile.progress.php');",32);
	$button_reload=button("{reload}","Loadjs('squid.reload.php');",32);
	$button_restart=button("{restart}","Loadjs('squid.restart.php');",32);
	$button_purge=button("DNS: {purge}","Loadjs('squid.dns.status.php?purge-js');",32);
	$sock=new sockets();
	$SquidSimpleConfig=$sock->GET_INFO("SquidSimpleConfig");
	if(!is_numeric($SquidSimpleConfig)){$SquidSimpleConfig=1;}
	
	$p=Paragraphe_switch_img("{simplified_parameters}", "{SquidSimpleConfig_explain}","SquidSimpleConfig-$t",$SquidSimpleConfig,null,800);
	$html="
	<div style='width:98%' class=form>
	$p
	<div style='margin-top:20px;text-align:right'>".button("{apply}","Save$t()",36)."</div>
	</div>		
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	Loadjs('squid.compile.progress.php');
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SquidSimpleConfig',document.getElementById('SquidSimpleConfig-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>			
";
echo $tpl->_ENGINE_parse_body($html);
	
}

function SquidSimpleConfig(){
	$sock=new sockets();
	$sock->SET_INFO("SquidSimpleConfig", $_POST["SquidSimpleConfig"]);
	
}
