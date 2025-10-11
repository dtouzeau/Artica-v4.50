<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',1);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.auto-aliases.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.auto-aliases.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.ejabberd.inc');
	
	if(!VerifyRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["perform"])){perform();exit;}
	
js();

function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$ou=$_GET["ou"];
	$t=$_GET["t"];
	$EnCryptedFunction=$_GET["EnCryptedFunction"];
	$title=$tpl->javascript_parse_text("{add_local_domain}");
	$html="YahooUser('550','$page?popup=yes&t=$t&ou={$_GET["ou"]}&EnCryptedFunction=$EnCryptedFunction','$title::$ou');";
	echo $html;
	
	
	
}

function popup(){
	$ou=$_GET["ou"];
	$t=$_GET["t"];	
	$ldap=new clladp();
	$tpl=new templates();
	$page=CurrentPageName();	
	if(!is_numeric($t)){$t=time();}
	$EnCryptedFunction=$_GET["EnCryptedFunction"];
	if(strlen($EnCryptedFunction)>3){
		$EnCryptedFunction=base64_decode($EnCryptedFunction)."\n";
	}else{$EnCryptedFunction=null;}
	
	if($ou==null){
		if(isAdmin()){
			$OUS=$ldap->hash_get_ou(true);
			$FieldOu=Field_array_Hash($OUS,"ou-$t",null,null,null,0,"font-size:18px");
		}else{
			
			$FieldOu=Field_hidden("ou-$t", $_SESSION["ou"])."<span style='font-size:18px'>{$_SESSION["ou"]}</span>";
		}
	}else{
		$FieldOu=Field_hidden("ou-$t", $ou)."<span style='font-size:18px'>{$_SESSION["ou"]}</span>";
	}
	
	
	$html="
			
	<div id='animate-$t'></div>		
	<table style='width:98%' class=form>
		<tr>
		  <td class=legend style='font-size:18px'>{domain}:</td>
		  <td>". Field_text("domain-$t",null,"font-size:18px;font-weigth:bold",null,null,null,false,"AddDomainCk$t(event)")."</td>
		</tr>
		<td class=legend style='font-size:18px'>{organization}:</td>  		
		 <td>$FieldOu</td>
		</tr>
		<tr>
			<td colspan=2 align='right'>". button("{add}", "AddDomain$t()","20px")."</td>
		</tr>
	</table>
					
<script>
var x_AddDomain$t=function(obj){
	document.getElementById('animate-$t').innerHTML='';
	var text;
	text=obj.responseText;
	if(text.length>3){alert(text);return;}
	$('#flexRT$t').flexReload();
	YahooUserHide();
	$EnCryptedFunction
	
	
}

function AddDomainCk$t(e){
	if(checkEnter(e)){ AddDomain$t();}
}

function AddDomain$t(){
	var XHR = new XHRConnection();
	XHR.appendData('perform','yes');
	var ou=document.getElementById('ou-$t').value;
	if(ou.length<3){alert('Organization:`'+ou+'` not supported');return;}
	XHR.appendData('ou',document.getElementById('ou-$t').value);
	XHR.appendData('domain',document.getElementById('domain-$t').value);
	AnimateDiv('animate-$t');
	XHR.sendAndLoad('$page', 'POST',x_AddDomain$t); 
}
</script>					
";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function perform(){
	$domain=$_POST["domain"];
	$ou=$_POST["ou"];
	$tpl=new templates();
	
	$ldap=new clladp();
	$hashdoms=$ldap->hash_get_all_domains();
	writelogs("hashdoms[$domain]={$hashdoms[$domain]}",__FUNCTION__,__FILE__);
	
	if($hashdoms[$domain]<>null){
		echo $tpl->_ENGINE_parse_body('{error_domain_exists} ->`'.$domain."`");
		return;
	}
	
	
	
	if(!$ldap->AddDomainEntity($ou,$domain)){
		echo $ldap->ldap_last_error;
		return;
	}
	
}

function isAdmin(){
	$users=new usersMenus();
	if($users->AsSystemAdministrator){return true;}
	if($users->AsDnsAdministrator){return true;}
	
}
function VerifyRights(){
	$usersmenus=new usersMenus();
	if($usersmenus->AsMessagingOrg){return true;}
	if(!$usersmenus->AllowChangeDomains){return false;}
}