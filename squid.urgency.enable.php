<?php
if(isset($_GET["verbose"])){
	$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
	$GLOBALS["debug"]=true;ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.system.network.inc');




if(isset($_GET["popup"])){popup();exit;}
$users=new usersMenus();
if(!$users->AsSquidAdministrator){
	$tpl=new templates();
	header("content-type: application/x-javascript");
	echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die("DIE " .__FILE__." Line: ".__LINE__);
}

if(isset($_POST["SquidUrgency"])){Save();exit;}



js();

function js(){
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{global_urgency_mode}");
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "YahooWin3('700','$page?popup=yes','$title');";

}


function popup(){
	$user=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();

	
	
	
	
	$html="
	<div style='font-size:40px;margin-bottom:40px'>{global_urgency_mode}</div>	
	<div class=explain style='font-size:26px'>{squid_urgency_explain}</div>	
	<div style='width:98%' class=form>
			<center style='margin:30px'>". button("{turn_into_emergency}","Save$t()",40)."</center>
	</div>
<script>
var xSave$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}	
	YahooWin3Hide();
	Loadjs('squid.compile.progress.php');
}		
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SquidUrgency',1);	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
	
function Save(){
	$sock=new sockets();
	foreach ($_POST as $key=>$value){
		$sock->SET_INFO($key, $value);
		
	}
	
}	
	