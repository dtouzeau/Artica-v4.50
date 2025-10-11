<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.artica.graphs.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die("DIE " .__FILE__." Line: ".__LINE__);	
}
if(isset($_POST["ArticaDBPath"])){ArticaDBPathSave();exit;}
if(isset($_GET["popup"])){popup();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$ask=$tpl->javascript_parse_text("{change_directory}");
	echo "YahooWin5('600','$page?popup=yes','$ask',true);";

}

function popup(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$ArticaDBPath=$sock->GET_INFO("ArticaDBPath");
	if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
	$t=time();
	$html="
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{directory}:</td>
		<td>". Field_text("ArticaDBPath",$ArticaDBPath,"font-size:16px;width:220px")."</td>
		<td width=1%>". button_browse("ArticaDBPath")."</td>
	</tr>
	<tr>
		<td colspan=3 align='right'>". button("{apply}","Save$t()",18)."</td>
	</tr>
	</table>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
}	
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ArticaDBPath',document.getElementById('ArticaDBPath').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}
</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}
function ArticaDBPathSave(){
	$sock=new sockets();
	$ArticaDBPath=$sock->GET_INFO("ArticaDBPath");
	if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}	
	if($ArticaDBPath==$_POST["ArticaDBPath"]){return;}
	$newPath=urlencode(base64_encode($_POST["ArticaDBPath"]));
	$sock->getFrameWork("squid.php?catzdb-changedir=$newPath");
	sleep(10);
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{task_has_been_scheduled_in_background_mode}",1);
	
}