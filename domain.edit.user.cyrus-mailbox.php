<?php
$GLOBALS["VERBOSE"]=false;
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
session_start ();
include_once ('ressources/class.templates.inc');
include_once ('ressources/class.ldap.inc');
include_once ('ressources/class.users.menus.inc');
include_once ('ressources/class.artica.inc');
include_once ('ressources/class.pure-ftpd.inc');
include_once ('ressources/class.user.inc');
include_once ('ressources/charts.php');
include_once ('ressources/class.mimedefang.inc');
include_once ('ressources/class.computers.inc');
include_once ('ressources/class.ini.inc');
include_once ('ressources/class.ocs.inc');
include_once (dirname ( __FILE__ ) . "/ressources/class.cyrus.inc");

if ((!isset ($_GET["uid"] )) && (isset($_POST["uid"]))){$_GET["uid"]=$_POST["uid"];}
if ((isset ($_GET["uid"] )) && (! isset ($_GET["userid"] ))) {$_GET["userid"] = $_GET["uid"];}


$usersprivs = new usersMenus ( );
$change_aliases = GetRights_aliases();
$modify_user = 1;
if ($_SESSION ["uid"] != $_GET["userid"]) {$modify_user = 0;}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["apply-js"])){apply_js();exit;}
if(isset($_POST["MailBoxMaxSize"])){Save();exit;}



page();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{mailbox}:{$_GET["uid"]}");
	echo "YahooWin6Hide();YahooWin6('995','$page?popup=yes&uid={$_GET["uid"]}','$title')";

}


function apply_js(){
	
	
	$uidenc=urlencode($_GET["uid"]);
	// Loadjs('domains.edit.user.create.mbx.php?uid=$uid')
	
	
}


function page(){

$uid=$_GET["uid"];
$uidenc=urlencode($uid);
$users = new usersMenus ( );
$page = CurrentPageName ();
$RealMailBox = false;
$tpl = new templates ( );
$page = CurrentPageName ();
$user = new user ( $uid );
$t=time();

$cyr = new cyrus ( );
if($GLOBALS["VERBOSE"]){echo "<H1> cyrus ( ) -> IfMailBoxExists($uid)</H1>\n";}
$RealMailBox=$cyr->IfMailBoxExists($uid);

if (! $RealMailBox) {
	
	if(preg_match("#Authentication failed#i", $cyr->cyrus_infos)){
		echo USER_MAILBOX_AUTHENTICATION_FAILED ( $uid,nl2br($cyr->cyrus_infos));
		return;
	}
	
	echo USER_MAILBOX_NONEXISTENT ( $uid,nl2br($cyr->cyrus_infos));
	$no_mailbox = "<p class=caption style='color:#d32d2d'>{user_no_mailbox} !!</p>";
	return;
}

if ($user->MailboxActive == 'TRUE') {
	$MailboxActive=1;
	$cyrus = new cyrus ( );
	$res = $cyrus->get_quota_array ( $uid );
	$size = $cyrus->MailboxInfosSize ( $uid );
	$orgfree = $cyrus->USER_STORAGE_LIMIT - $cyrus->USER_STORAGE_USAGE;
	$free = FormatBytes ( $orgfree );

	if ($cyrus->MailBoxExists ( $uid )) {
		$graph1 = InsertChart ( 'js/charts.swf', "js/charts_library", "listener.graphs.php?USER_STORAGE_USAGE=$cyrus->USER_STORAGE_USAGE&STORAGE_LIMIT=$cyrus->USER_STORAGE_LIMIT&FREE=$orgfree", 200, 167, "", true, $users->ChartLicence );
	} else {
		$graph1 = "<H3>{no_mailbox_user}</H3>";
	}
	$mailboxInfos = "<div>
			<i>" . FormatBytes ( $cyrus->USER_STORAGE_USAGE ) . "/" . FormatBytes ( $cyrus->USER_STORAGE_LIMIT ) . "<br>
			($free {free})</i><br><strong>" . FormatBytes ( $size ) . " used</strong>
			 </div>";

}


$export_mailbox = $tpl->_ENGINE_parse_body ( '{export_mailbox}' );
$import_mailbox = $tpl->_ENGINE_parse_body ( '{import_mailbox}' );
if (strlen ( $import_mailbox ) > strlen ( $export_mailbox )) {
	$import_mailbox = substr ( $import_mailbox, 0, strlen ( $export_mailbox ) - 3 ) . "...";
}

//sudo -u cyrusimap /usr/bin/cyrus/bin/reconstruct -r -f user/shortname

$tr[]=button("{repair_mailbox}","Loadjs('domains.edit.user.php?script=repair_mailbox&uid=$uidenc');",18,286);
$tr[]=button("{export_mailbox}","Loadjs('domains.edit.user.php?script=export_script&uid=$uidenc');",18,286);
$tr[]=button("{empty_this_mailbox}","Loadjs('domains.edit.user.empty.mailbox.php?&userid=$uidenc');",18,286);
$tr[]=button("{delete_this_mailbox}","Loadjs('domains.edit.user.php?script=delete_mailbox&uid=$uidenc');",18,286);

while (list ($key, $line) = each ($tr) ){
	$buttons=$buttons."
	<tr>
		<td><center style='margin-bottom:15px'>$line</center></td>
	</tr>		
			
	";
	
}


if (! $RealMailBox) {$buttons = null;}

$priv = new usersMenus();
$ini = new Bs_IniHandler();
$ini->loadString ($user->MailboxSecurityParameters);

$button = "
      	<tr>
      		<td colspan=2 align='right'>
      		<hr>
      		" . button ( "{apply}", "Save$t()",26 ) . "
      		</td>
      	</tr>
      	";
if ($priv->AllowAddUsers == false) {
	$button = null;
	$img_left_mbx = "<img src='img/folder-mailbox-96.png'>";
}
$subtitle = "{user_quota}";
$main_graph = "<div style='border:1px solid #005447;padding:5px;margin:3px'><span id='mailbox_graph'>$graph1</span></div>";

if ($user->MailBoxMaxSize == 0) {
	$subtitle = "<i>{user_has_no_quota}</i>";
	$graph1 = null;
	$mailboxInfos = "<strong>" . FormatBytes ( $size ) . " used</strong>";
	$mailboxInfos = null;
	$main_graph = null;
}

if ($ldap->ldap_last_error != null) {
	return nl2br ( $ldap->ldap_last_error );
}


$ADDisable=0;
if($priv->EnableManageUsersTroughActiveDirectory){
	$ADDisable=1;
	$button=null;
}

if($subtitle<>null){$subtitle="<p class=explain style='font-size:16px'>$subtitle</p>";}



$html = "
<div id='usermailboxformdiv'>
<table style='width:100%'>
<form name='FFUserMailBox'>
<input type='hidden' name='UserMailBoxEdit' value='$uid'>
<table style='width:100%'>
<tr>	
	<td valign='top' style='width:288px'><table style='width:100%'>$buttons</table></td>
	<td valign='top'>
<table style='width:100%'>
	<tr>
		<td colspan=3>
			<div style='font-size:30px;margin-bottom:20px'>{mailbox} {mailbox account}: $uid</div>$mailboxInfo
		</td>
	</tr>
		<td class=legend style='font-size:18px' class=legend>{MailboxActive}</td>
		<td>" . Field_checkbox_design("MailboxActive-$t", 1, $MailboxActive) . "</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td  align='right' nowrap class=legend valign='middle' 
			style='vertical-align:middle;font-size:18px'>{mailbox quota}:</td>
		<td style='font-size:18px'>" . Field_text ( 'MailBoxMaxSize', $user->MailBoxMaxSize, 'width:95px;font-size:18px' ) . "&nbsp;MB</td>
		<td align='left'>" . help_icon ( $mailboxInfos, true ) . "</td>
	</tr>
	<tr>
	<td colspan=3>$subtitle</td>
	</tr>
	<tr>
	<td colspan=3><div style='font-size:30px;margin-bottom:20px'>{mailbox_priv}</div></td>
	</tr>
	<tr>
		<td class=legend style='text-align:rigth;font-size:18px'>{mplt}:</td>
		<td>" . Field_checkbox_design( "mp_l-$t", 1, $ini->_params["mailbox"] ["l"], null, '{mpl}' ) . "</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend nowrap style='text-align:rigth;font-size:18px'>{mprt}:</td>
		<td>" . Field_checkbox_design ( "mp_r-$t", 1, $ini->_params["mailbox"] ["r"], null, '{mpr}' ) . "</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend nowrap style='text-align:rigth;font-size:18px'>{mpst}:</td>
		<td>" . Field_checkbox_design ( "mp_s-$t", 1, $ini->_params["mailbox"] ["s"], null, '{mps}' ) . "</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend nowrap style='text-align:rigth;font-size:18px'>{mpwt}:</td>
		<td>" . Field_checkbox_design ( "mp_w-$t", 1, $ini->_params["mailbox"] ["w"], null, '{mpw}' ) . "</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend nowrap style='text-align:rigth;font-size:18px'>{mpit}:</td>
		<td>" . Field_checkbox_design ( "mp_i-$t", 1, $ini->_params["mailbox"] ["i"], null, '{mpi}' ) . "</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend nowrap style='text-align:rigth;font-size:18px'>{mppt}:</td>
		<td>" . Field_checkbox_design ( "mp_p-$t", 1, $ini->_params["mailbox"] ["p"], null, '{mpp}' ) . "</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend nowrap style='text-align:rigth;font-size:18px'>{mpct}:</td>
		<td>" . Field_checkbox_design ( "mp_c-$t", 1, $ini->_params["mailbox"] ["c"], null, '{mpc}' ) . "</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend nowrap style='text-align:rigth;font-size:18px'>{mpdt}:</td>
		<td>" . Field_checkbox_design ( "mp_d-$t", 1, $ini->_params["mailbox"] ["d"], null, '{mpd}' ) . "</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
	<td class=legend nowrap style='text-align:rigth;font-size:18px'><strong>{mpat}</strong>:</td>
	<td>" . Field_checkbox_design ( "mp_a-$t", 1, $ini->_params["mailbox"] ["a"], null, '{mpa}' ) . "</td>
	<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=3 align='right'>$button</td>
	</tr>
	</table>
</td>
</tr>
</table>
<p>&nbsp;</p>

$main_graph

<script>
	var xSave$t= function (obj) {
		var MailBoxMaxSize='0';
		var tempvalue=obj.responseText;
		if(document.getElementById('MailBoxMaxSize')){
			var MailBoxMaxSize=document.getElementById('MailBoxMaxSize').value;
		}
		if(tempvalue.length>3){
			
			return;
		}
		Loadjs('domains.edit.user.create.mbx.php?uid=$uidenc&MailBoxMaxSize='+MailBoxMaxSize);
	}		

	function Save$t(){
		
		var mp_l=1;
		var mp_r=1;
		var mp_s=1;
		var mp_w=1;
		var mp_i=1;
		var mp_p=1;
		var mp_c=1;
		var mp_d=1;
		var mp_a=1;
		var XHR = new XHRConnection();
		XHR.appendData('Save','$uid');
		if(document.getElementById('MailboxActive-$t').checked){XHR.appendData('MailboxActive','TRUE');}else{XHR.appendData('MailboxActive','FALSE');}
		XHR.appendData('MailBoxMaxSize',document.getElementById('MailBoxMaxSize').value);
		if(document.getElementById('mp_l-$t').checked){mp_l=1;}else{mp_l=0;}
		if(document.getElementById('mp_r-$t').checked){mp_r=1;}else{mp_r=0;}
		if(document.getElementById('mp_s-$t').checked){mp_s=1;}else{mp_s=0;}
		if(document.getElementById('mp_w-$t').checked){mp_w=1;}else{mp_w=0;}
		if(document.getElementById('mp_i-$t').checked){mp_i=1;}else{mp_i=0;}
		if(document.getElementById('mp_p-$t').checked){mp_p=1;}else{mp_p=0;}
		if(document.getElementById('mp_c-$t').checked){mp_c=1;}else{mp_c=0;}
		if(document.getElementById('mp_d-$t').checked){mp_d=1;}else{mp_d=0;}
		if(document.getElementById('mp_a-$t').checked){mp_a=1;}else{mp_a=0;}	
		
		XHR.appendData('mp_l',mp_l);
		XHR.appendData('mp_r',mp_r);
		XHR.appendData('mp_s',mp_s);
		XHR.appendData('mp_w',mp_w);
		XHR.appendData('mp_i',mp_i);
		XHR.appendData('mp_p',mp_p);
		XHR.appendData('mp_c',mp_c);
		XHR.appendData('mp_d',mp_d);
		XHR.appendData('mp_a',mp_a);
		
		
		XHR.sendAndLoad('$page', 'POST',xSave$t);	
	}
							
</script>
";

echo $tpl->_ENGINE_parse_body ( $html );
						
}					
						
function USER_MAILBOX_NONEXISTENT($uid,$error) {
$page = CurrentPageName ();
$tpl = new templates ( );
$html = "
<table style='width:100%;margin-top:20px'>
	<tr>
		<td valign='top' style='width:256px'><img src='img/inbox-error-256.png'></td>
		<td valign='top' style='padding-left:30px'>
				<div style='font-size:42px;color:#D32D2D'>{no_mailbox}</div>
				<div style='font-size:22px;color:#D32D2D;margin:15px'>{user_no_mailbox}</div><hr>
				<div style='margin-top:30px;text-align:right'>".
				button("{create_mailbox2}",
						"Loadjs('domains.edit.user.php?create-mailbox-wizard=yes&uid=$uid&MailBoxMaxSize=0')",32)."
		</td>
		
	</tr>
</table>";

return $tpl->_ENGINE_parse_body ( $html );
}

function USER_MAILBOX_AUTHENTICATION_FAILED($uid,$error){
	$page = CurrentPageName ();
	$tpl = new templates ( );
	$html = "
<table style='width:100%;margin-top:20px'>
	<tr>
		<td valign='top' style='width:256px'><img src='img/inbox-error-256.png'></td>
		<td valign='top' style='padding-left:30px'>
			<div style='font-size:42px;color:#D32D2D'>{authentication_failed_cyrus}</div>
			<div style='font-size:22px;color:#D32D2D;margin:15px'>{user_no_mailbox_authfailed}</div><hr>
		</td>
		
	</tr>
</table>";
	
	return $tpl->_ENGINE_parse_body ( $html );	
	
}

function Save(){
	$tpl=new templates();
	$uid=$_POST["Save"];
	$user=new user($uid);
	
	
	$acls="[mailbox]\n";
	
	foreach ($_POST as $num=>$val){
		if(preg_match('#mp_([a-zA-Z])#',$num,$re)){
			writelogs("set acls {$re[1]}=$val on mailbox",__FUNCTION__,__FILE__);
			$acls=$acls."{$re[1]}=$val\n";
		}
	}
	
	$user=new user($uid);
	$user->MailBoxMaxSize=$_POST["MailBoxMaxSize"];
	$user->MailboxActive=strtoupper($_POST["MailboxActive"]);
	$user->MailboxSecurityParameters=$acls;
	
	
	if(!$user->SaveCyrusMailboxesParameters()){echo $user->ldap_error;}
	
	
	if($user->MailboxActive<>"TRUE"){
		echo $tpl->javascript_parse_text("$uid:{mailbox_disabled} ($user->MailboxActive)");
	}	
}
