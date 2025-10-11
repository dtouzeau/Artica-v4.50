<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.squid.accesslogs.inc');
	include_once('ressources/class.tcpip.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128('{ERROR_NO_PRIVS}');
	die("DIE " .__FILE__." Line: ".__LINE__);	
}

if(isset($_POST["BackupSquidLogsUseNas"])){Save();exit;}

page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$BackupSquidLogsUseNas=$sock->GET_INFO("BackupSquidLogsUseNas");
	$BackupSquidLogsNASIpaddr=$sock->GET_INFO("BackupSquidLogsNASIpaddr");
	$BackupSquidLogsNASFolder=$sock->GET_INFO("BackupSquidLogsNASFolder");
	$BackupSquidLogsNASUser=$sock->GET_INFO("BackupSquidLogsNASUser");
	$BackupSquidLogsNASPassword=$sock->GET_INFO("BackupSquidLogsNASPassword");
	$BackupSquidLogsNASFolder2=$sock->GET_INFO("BackupSquidLogsNASFolder2");
	if($BackupSquidLogsNASFolder2==null){$BackupSquidLogsNASFolder2="artica-backup-syslog";}
	if(!is_numeric($BackupSquidLogsUseNas)){$BackupSquidLogsUseNas=0;}	
	$t=time();
	
	if(isset($_GET["ViaStats"])){
		$prefix_title="<a href=\"javascript:GoToStatsOptions();\" style=';font-size:36px;text-decoration:underline'>{statistics_engine}</a>&nbsp;|&nbsp;";
	}
	
$html="<div style='width:100%;font-size:36px'>$prefix_title{NAS_storage}</div>
		
	<div style='font-size:30px;margin-bottom:30px'>{log_retention_nas_text}</div>	
	<div style='width:95%'  class=form>
	<table style='width:100%'>
	<tr><td colspan=3>". Paragraphe_switch_img("{use_remote_nas}", "{BackupSquidLogsUseNas_explain}",
			"BackupSquidLogsUseNas",$BackupSquidLogsUseNas,null,1100,"SaveBackupSquidNasCheck$t()")."
	<tr>
		<td class=legend style='font-size:26px'>{hostname}:</td>
		<td>". Field_text("BackupSquidLogsNASIpaddr",$BackupSquidLogsNASIpaddr,"font-size:26px;width:520px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:26px'>{shared_folder}:</td>
		<td>". Field_text("BackupSquidLogsNASFolder",$BackupSquidLogsNASFolder,"font-size:26px;width:520px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:26px'>{storage_directory}:</td>
		<td>". Field_text("BackupSquidLogsNASFolder2",$BackupSquidLogsNASFolder2,"font-size:26px;width:520px")."</td>
		<td>&nbsp;</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:26px'>{username}:</td>
		<td>". Field_text("BackupSquidLogsNASUser",$BackupSquidLogsNASUser,"font-size:26px;width:520px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:26px'>{password}:</td>
		<td>". Field_password("BackupSquidLogsNASPassword",$BackupSquidLogsNASPassword,"font-size:26px;width:300px")."</td>
		<td>&nbsp;</td>
	</tr>
	
	<tr>
		<td colspan=3 align=right style='font-size:26px;padding-top:50px'>
				<hr>".button("{test_connection}", "SaveBackupSquidNas$t();Loadjs('squid.nas.storage.progress.php')",38)."&nbsp;|&nbsp;". button("{apply}", "SaveBackupSquidNas$t()",38)."</td>
	</tr>

	
	</table>
</div>
<script>
function SaveBackupSquidNasCheck$t(){
	document.getElementById('BackupSquidLogsNASIpaddr').disabled=true;
	document.getElementById('BackupSquidLogsNASFolder').disabled=true;
	document.getElementById('BackupSquidLogsNASUser').disabled=true;
	document.getElementById('BackupSquidLogsNASPassword').disabled=true;
	document.getElementById('BackupSquidLogsNASFolder2').disabled=true;
	
	if(document.getElementById('BackupSquidLogsUseNas').value==1){
		document.getElementById('BackupSquidLogsNASIpaddr').disabled=false;
		document.getElementById('BackupSquidLogsNASFolder').disabled=false;
		document.getElementById('BackupSquidLogsNASUser').disabled=false;
		document.getElementById('BackupSquidLogsNASPassword').disabled=false;
		document.getElementById('BackupSquidLogsNASFolder2').disabled=false;
	}
}

var x_SaveSettsLogRotate$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	RefreshTab('main_squid_logs_sources');
}
	
	
function SaveBackupSquidNas$t(){
	var XHR = new XHRConnection();
	XHR.appendData('BackupSquidLogsUseNas',document.getElementById('BackupSquidLogsUseNas').value);
	XHR.appendData('BackupSquidLogsNASIpaddr',document.getElementById('BackupSquidLogsNASIpaddr').value);
	XHR.appendData('BackupSquidLogsNASFolder',encodeURIComponent(document.getElementById('BackupSquidLogsNASFolder').value));
	XHR.appendData('BackupSquidLogsNASUser',encodeURIComponent(document.getElementById('BackupSquidLogsNASUser').value));
	XHR.appendData('BackupSquidLogsNASPassword',encodeURIComponent(document.getElementById('BackupSquidLogsNASPassword').value));
	XHR.appendData('BackupSquidLogsNASFolder2',encodeURIComponent(document.getElementById('BackupSquidLogsNASFolder2').value));
	XHR.sendAndLoad('$page', 'POST',x_SaveSettsLogRotate$t);
}
SaveBackupSquidNasCheck$t();	
</script>

				
";	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	$sock=new sockets();

	if(isset($_POST["SystemLogsPath"])){$_POST["SystemLogsPath"]=url_decode_special_tool($_POST["SystemLogsPath"]);}
	if(isset($_POST["BackupMaxDaysDir"])){$_POST["BackupMaxDaysDir"]=url_decode_special_tool($_POST["BackupMaxDaysDir"]);}
	if(isset($_POST["BackupSquidLogsNASFolder"])){$_POST["BackupSquidLogsNASFolder"]=url_decode_special_tool($_POST["BackupSquidLogsNASFolder"]);}
	if(isset($_POST["SystemLogsPath"])){$_POST["SystemLogsPath"]=url_decode_special_tool($_POST["SystemLogsPath"]);}
	if(isset($_POST["BackupSquidLogsNASPassword"])){$_POST["BackupSquidLogsNASPassword"]=url_decode_special_tool($_POST["BackupSquidLogsNASPassword"]);}
	if(isset($_POST["BackupSquidLogsNASFolder2"])){$_POST["BackupSquidLogsNASFolder2"]=url_decode_special_tool($_POST["BackupSquidLogsNASFolder2"]);}

	foreach ($_POST as $key=>$value){
		$value=url_decode_special_tool($value);
		$sock->SET_INFO($key, $value);
	}

}