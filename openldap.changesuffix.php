<?php

$GLOBALS["ICON_FAMILY"]="PARAMETERS";
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');

	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	include_once('ressources/class.samba.inc');
	
	
$usersmenus=new usersMenus();
if(isset($_GET["js"])){js();exit;}
if($usersmenus->AsArticaAdministrator==false){die("DIE " .__FILE__." Line: ".__LINE__);exit;}

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["change-ldap-suffix-events"])){events();exit;}
	if(isset($_POST["ChangeLDAPSuffixTo"])){save();exit;}


js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{change_ldap_suffix}");
	$html="RTMMail('650','$page?popup=yes','$title')";
	echo $html;
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$ChangeLDAPSuffixFrom=base64_decode($sock->GET_INFO("ChangeLDAPSuffixFrom"));
	$ChangeLDAPSuffixTo=base64_decode($sock->GET_INFO("ChangeLDAPSuffixTo"));	
	$ldap=new clladp();
	$ChangeLDAPSuffixFrom=$ldap->suffix;
	$LockLdapConfig=$sock->GET_INFO("LockLdapConfig");
	$OpenLDAPLogLevel=$sock->GET_INFO("OpenLDAPLogLevel");
	if(!is_numeric($OpenLDAPLogLevel)){$OpenLDAPLogLevel=256;}
	if(!is_numeric($LockLdapConfig)){$LockLdapConfig=0;}
	$button=button("{apply}","ChangeLdapSuffixPerform()","18px");
	if($LockLdapConfig==1){
		$button=null;
	}
	$t=time();
	$html="
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{ldap_suffix}:</td>
		<td><strong style='font-size:16px'>$ChangeLDAPSuffixFrom</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{destination}:</td>
		<td>". Field_text("ChangeLDAPSuffixTo-$t","$ChangeLDAPSuffixTo","font-size:16px;width:450px",null,null,null,false,"ChangeLdapSuffixPerformCheck(event)")."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>
			". button("{apply}","ChangeLdapSuffixPerform()","18px")."
			</td>
	</tr>
	</table>
	<div id='$t-div'></div>
	<script>
		function ChangeLdapSuffixPerformCheck(e){
		 if(checkEnter(e)){
		 	ChangeLdapSuffixPerform();
		 }
		}
		
	function CheckChangeLDAPSuffixTo(){
		var LockLdapConfig=$LockLdapConfig;
		if(LockLdapConfig==1){
			document.getElementById('ChangeLDAPSuffixTo').disabled=true;
		}
	}
	
	var x_ChangeLdapSuffixPerform= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		setTimeout('TransactionChldapCheck()',3000);
		
	}		
			
		

	function ChangeLdapSuffixPerform(){
		var suffix=document.getElementById('ChangeLDAPSuffixTo-$t').value;
		if(confirm('{ask_change_suffix}: $ChangeLDAPSuffixFrom -> '+suffix+' ?')){
			var XHR = new XHRConnection();
			XHR.appendData('ChangeLDAPSuffixTo',suffix);
			XHR.appendData('ChangeLDAPSuffixFrom','$ChangeLDAPSuffixFrom');
			AnimateDiv('$t-div');
			XHR.sendAndLoad('$page', 'POST',x_ChangeLdapSuffixPerform);			
		
		}
	
	}
	
	function TransactionChldapCheck(){
		if(!RTMMailOpen()){return;}
		LoadAjax('$t-div','$page?change-ldap-suffix-events=yes&t=$t');
	}
			
		
	
	
	CheckChangeLDAPSuffixTo();
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function save(){
	$sock=new sockets();
	$sock->SaveConfigFile(base64_encode($_POST["ChangeLDAPSuffixTo"]),"ChangeLDAPSuffixTo");
	$sock->SaveConfigFile(base64_encode($_POST["ChangeLDAPSuffixFrom"]),"ChangeLDAPSuffixFrom");
	$sock->getFrameWork("services.php?change-ldap-suffix=yes");
	
	
}

function events(){
$tpl=new templates();
	$page=CurrentPageName();	
	$file=PROGRESS_DIR."/change.ldap.suffix.log";
	if(!is_file($file)){
		echo $tpl->_ENGINE_parse_body("
		<center style='font-size:18px;padding:50px'>{please_wait_search_transaction_history}</center>
		<script>setTimeout('TransactionChldapCheck()',3000);</script>");
		return;
		
	}
	
	$f=explode("\n",@file_get_contents($file));
	if(count($f)<2){
		echo $tpl->_ENGINE_parse_body("
		<center style='font-size:18px;padding:50px'>{please_wait_search_transaction_history}</center>
		<script>setTimeout('TransactionChldapCheck()',3000);</script>");
		return;
				
	}
	krsort($f);
	foreach ($f as $num=>$ligne){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		$ta[]="<div><code style='font-size:12px'>$ligne</code></div>";
	}
	
	echo @implode("", $ta)."<script>setTimeout('TransactionChldapCheck()',5000);</script>";
		
	
	
	
}


