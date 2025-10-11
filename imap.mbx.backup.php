<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.cron.inc');
	
	$users=new usersMenus();
	if(!$users->AsMailBoxAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die("DIE " .__FILE__." Line: ".__LINE__);
	}	
	if(isset($_POST["OfflineImapBackupDir"])){Save();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	js();
	
	
function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{mailboxes_backups}");
	$html="YahooWin6('550','$page?popup=yes','$title')";
	echo $html;
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$OfflineImapBackupTool=$sock->GET_INFO("OfflineImapBackupTool");
	$OfflineImapBackupDir=$sock->GET_INFO("OfflineImapBackupDir");
	$OfflineImapWKDir=$sock->GET_INFO("OfflineImapWKDir");
	if(!is_numeric($OfflineImapBackupTool)){$OfflineImapBackupTool=0;}
	if($OfflineImapBackupDir==null){$OfflineImapBackupDir="%HOME%/mailbackups";}
	if($OfflineImapWKDir==null){$OfflineImapWKDir="/home/artica/mailbackups";}
	$t=time();
	
	$html="
	<div style='font-size:14px' class=explain id='$t'>{mailboxes_backups_text_admin}</div>
	<table style='width:99%' class=form>
	<tr>
	<td colspan=2>
	". Paragraphe_switch_img("{enable_backup_mailboxes_features}", "{enable_backup_mailboxes_features_explain}"
	,"OfflineImapBackupTool",$OfflineImapBackupTool,null,350).
	"</td>
	</tr>
	<tR>
		<td class=legend style='font-size:16px'>{working_directory}:</td>
		<td>". Field_text("OfflineImapWKDir",$OfflineImapWKDir,"width:220px;font-size:16px")."</td>
	</tr>	
	<tR>
		<td class=legend style='font-size:16px'>{storage_directory}:</td>
		<td>". Field_text("OfflineImapBackupDir",$OfflineImapBackupDir,"width:220px;font-size:16px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()","18px")."</td>
	</tr>
	</table>
	<script>
	var x_Save$t=function(obj){
      var tempvalue=obj.responseText;
      if(tempvalue.length>5){alert(tempvalue);}
      document.getElementById('$t').innerHTML='';
      YahooWin6Hide();
      }	
		
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('OfflineImapBackupDir',document.getElementById('OfflineImapBackupDir').value);
		XHR.appendData('OfflineImapBackupTool',document.getElementById('OfflineImapBackupTool').value);
		XHR.appendData('OfflineImapWKDir',document.getElementById('OfflineImapWKDir').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_Save$t);
		}	
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function Save(){
	$sock=new sockets();
	$sock->SET_INFO("OfflineImapBackupTool", $_POST["OfflineImapBackupTool"]);
	$sock->SET_INFO("OfflineImapBackupDir", $_POST["OfflineImapBackupDir"]);
	$sock->SET_INFO("OfflineImapWKDir", $_POST["OfflineImapWKDir"]);
	
}