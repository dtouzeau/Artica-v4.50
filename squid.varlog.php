<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die("DIE " .__FILE__." Line: ".__LINE__);	
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["location"])){save();exit;}

js();


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$log_location=$tpl->javascript_parse_text("{log_location}");
	echo "YahooWin2('990','$page?popup=yes','$log_location')";
	
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$varlog=base64_decode($sock->getFrameWork("squid.php?varlog-location=yes"));
	$t=time();
	$html="
	<div style='font-size:32px;margin-bottom:25px'>{log_location}</div>
	<div class=explain style='font-size:22px;margin-bottom:25px'>{squid_log_location_explain}</div>
	<div style='width:98%' class=form>		
	<table style='width:100%'>
	<tr>
		<td valign='top' class=legend style='font-size:28px;vertical-align:middle'>{edit_location}:</td>
		<td>". Field_text("location-$t",$varlog,"font-size:28px;width:99%")."</td>
		<td style='font-size:28px;vertical-align:middle'>". button_browse("location-$t")."</td>
	</tr>
	<tr><td colspan=3 align='right'><p>&nbsp;</p><hr>". button("{apply}","Save$t()",38)."</td></tr>
	</table>		
	</div>
<script>

	var xSave$t= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		Loadjs('squid.varlog.progress.php');
	}

function Save$t(){
	var pp=encodeURIComponent(document.getElementById('location-$t').value);
	var XHR = new XHRConnection();
	XHR.appendData('location',pp);
	XHR.sendAndLoad('$page', 'POST',xSave$t); 
}
</script>
";
echo $tpl->_ENGINE_parse_body($html);	
	
	
}
function save(){
	$sock=new sockets();
	$_POST["location"]=url_decode_special_tool($_POST["location"]);
	$sock->SET_INFO("VarLogSquidLocation", $_POST["location"]);
}
