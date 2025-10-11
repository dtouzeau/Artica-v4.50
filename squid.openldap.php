<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	
	$user=new usersMenus();

		
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}	
	
	if(isset($_POST["ldap_auth"])){Save();exit;}

page();

function page(){

$squid=new squidbee();
$users=new usersMenus();
$sock=new sockets();
$tpl=new templates();
$page=CurrentPageName();
$SquidLdapAuthEnableGroups=$sock->GET_INFO("SquidLdapAuthEnableGroups");
$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
$SquidLdapAuthBanner=$sock->GET_INFO("SquidLdapAuthBanner");
if($SquidLdapAuthBanner==null){$SquidLdapAuthBanner="Basic credentials, Please logon...";}
if($EnableKerbAuth==1){$error=FATAL_ERROR_SHOW_128("{ldap_with_ad_explain}");}

if(trim($users->SQUID_LDAP_AUTH)==null){
	echo FATAL_ERROR_SHOW_128("{authenticate_users_no_binaries}");return;
}
	$EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
	$please_choose_only_one_method=$tpl->javascript_parse_text("{please_choose_only_one_method}");
	$ldap_server=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"];
	$ldap_port=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_port"];
	$userdn=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_user"];
	$ldap_password=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_password"];
	$ldap_suffix=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_suffix"];
	$ldap_filter_users=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_users"];
	$ldap_filter_group=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group"];
	
	$auth_banner=$squid->EXTERNAL_LDAP_AUTH_PARAMS["auth_banner"];
	$ldap_user_attribute=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_user_attribute"];
	$ldap_group_attribute=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_group_attribute"];
	$ldap_filter_search_group=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_search_group"];
	$ldap_filter_group_attribute=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group_attribute"];
	$SquidLdapAuthBanner=$sock->GET_INFO("SquidLdapAuthBanner");
	if($SquidLdapAuthBanner==null){$SquidLdapAuthBanner="Basic credentials, Please logon...";}
	$EnableSquidExternalLDAP=$squid->LDAP_EXTERNAL_AUTH;
	if($auth_banner==null){$auth_banner=$SquidLdapAuthBanner;}
	if($ldap_filter_users==null){$ldap_filter_users="sAMAccountName=%s";}
	if($ldap_filter_group==null){$ldap_filter_group="(&(objectclass=person)(sAMAccountName=%u)(memberof=*))";}
	if($ldap_port==null){$ldap_port=389;}
	$t=time();
	
	if($ldap_filter_users==null){$ldap_filter_users="sAMAccountName=%s";}
	if($ldap_user_attribute==null){$ldap_user_attribute="sAMAccountName";}
	if($ldap_filter_group==null){$ldap_filter_group="(&(objectclass=person)(sAMAccountName=%u)(memberof=*))";}
	if($ldap_filter_search_group==null){$ldap_filter_search_group="(&(objectclass=group)(sAMAccountName=%s))";}
	if($ldap_group_attribute==null){$ldap_group_attribute="sAMAccountName";}
	if($ldap_filter_group_attribute==null){$ldap_filter_group_attribute="memberof";}
	
	if(!is_numeric($squid->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_children"])){$squid->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_children"]=10;}
	if(!is_numeric($squid->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_startup"])){$squid->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_startup"]=3;}
	if(!is_numeric($squid->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_idle"])){$squid->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_idle"]=1;}
	if(!is_numeric($squid->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_cache"])){$squid->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_cache"]=360;}
	
	if($EnableSquidExternalLDAP==1){$squid->LDAP_AUTH=0;}
	$bt_SaveExternalLDAPSYS=button("{apply}","SaveExternalLDAPSYS()",44);
	$txt_SaveExternalLDAPSYS=null;
	
	if($EnableOpenLDAP==0){
		$squid->LDAP_AUTH=0;
		$bt_SaveExternalLDAPSYS=null;
		$txt_SaveExternalLDAPSYS="<p class=text-error style='font-size:18px'>{local_openldap_service_disabled}</p>";
	}
	
	

$html="
<div style='font-size:30px;margin-bottom:30px'>{local_authentication}</div>
$error
<div style='width:98%' class=form>
$txt_SaveExternalLDAPSYS
<table style='width:99%' class=TableRemove>
<tr>
	<td colspan=2 >" . Paragraphe_switch_img(
					"{authenticate_users_local_db}","{authenticate_users_explain}",
					"ldap_auth-$t",$squid->LDAP_AUTH,'{enable_disable}',1450,"LocalDBCheck$t()")."
	</td>
</tr>
<tr>
	<td style='font-size:30px' class=legend>{banner}:</td>
	<td>". Field_text("SquidLdapAuthBanner-$t", $SquidLdapAuthBanner,"font-size:30px;width:1060px")."</td>
</td>
</tr>
	<tr>
		<td colspan=2 align='right'>
			<hr>
				<p>&nbsp;</p>$bt_SaveExternalLDAPSYS
		</td>
	</tr>
</table>
</div>

	
	<script>
	
	
function LocalDBCheck$t(){

	document.getElementById('SquidLdapAuthBanner-$t').disabled=true;

	if(document.getElementById('ldap_auth-$t').value==1){
		document.getElementById('SquidLdapAuthBanner-$t').disabled=false;
	}
}
	
	var x_SaveExternalLDAPSYS= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		Loadjs('squid.global.wl.center.progress.php');
	}				
			
		function SaveExternalLDAPSYS(){
			var XHR = new XHRConnection();
			var ldap_auth=document.getElementById('ldap_auth-$t').value;
			XHR.appendData('ldap_auth', ldap_auth);
			XHR.appendData('SquidLdapAuthBanner',encodeURIComponent(document.getElementById('SquidLdapAuthBanner-$t').value));
			XHR.sendAndLoad('$page', 'POST',x_SaveExternalLDAPSYS);		
			}
			
	
		LocalDBCheck$t();
	</script>
";


echo $tpl->_ENGINE_parse_body($html);

}

function Save(){
	$squid=new squidbee();
	$sock=new sockets();
		
	foreach ($_POST as $num=>$ligne){
		$_POST[$num]=url_decode_special_tool($ligne);
	
	}
	
	$_POST["SquidLdapAuthBanner"]=url_decode_special_tool($_POST["SquidLdapAuthBanner"]);

	$sock->SaveConfigFile($_POST["SquidLdapAuthBanner"], "SquidLdapAuthBanner");
	
	
	if($_POST["ldap_auth"]==1){
		$squid->LDAP_AUTH=1;
		$squid->LDAP_EXTERNAL_AUTH=0;
	}else{
		$squid->LDAP_AUTH=0;
	}
	
	
	


	$squid->SaveToLdap();
	
}

