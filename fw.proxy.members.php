<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["local-ldap"])){local_ldap();exit;}
if(isset($_GET["remote-ldap"])){remote_ldap();exit;}

if(isset($_GET["radius-config-js"])){radius_config_js();exit;}
if(isset($_GET["radius-config-popup"])){radius_config_popup();exit;}
if(isset($_POST["SquidStandardLDAPAuth"])){Save();exit;}
if(isset($_POST["radiusserver"])){SaveRadius();exit;}
if(isset($_POST["SquidExternLDAPAUTH"])){SaveRemoteLDAP();exit;}

if(isset($_GET["remote-ldap-js"])){remote_ldap_js();exit;}
if(isset($_GET["remote-ldap-popup"])){remote_ldap_popup();exit;}
if(isset($_GET["remote-ldap-status"])){remote_ldap_status();exit;}

if(isset($_GET["local-ldap-status"])){local_ldap_status();exit;}
if(isset($_GET["local-ldap-js"])){local_ldap_js();exit;}
if(isset($_GET["local-ldap-popup"])){local_ldap_popup();exit;}
page();
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{your_proxy} {authentication}",
        ico_user_lock,"{your_proxy_authentication_text}","$page?tabs=yes",
        "proxy-auth","progress-squidauth-restart",false,"table-loader-squid-auth");



	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return true;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function tabs():bool{
	$tpl=new template_admin();
    $array["{authentication}"]="fw.proxy.members.engines.php?start=yes";
    $array["{auth_mec_pref}"]="fw.proxy.auth_schemes.php?via-tabs=yes";

	echo $tpl->tabs_default($array);
    return true;
}

function local_ldap_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{local_ldap}","$page?local-ldap-popup=yes");
    return true;
}

function local_ldap_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$SquidStandardLDAPAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidStandardLDAPAuth"));
	$SquidLdapAuthBanner=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLdapAuthBanner"));
    $EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
	if($SquidLdapAuthBanner==null){$SquidLdapAuthBanner="Basic credentials, Please logon...";}
	$security="AsSquidAdministrator";

    $jsrestart=RestartJS();

    $form[]=$tpl->field_checkbox("SquidStandardLDAPAuth","{authenticate_users_local_db}",$SquidStandardLDAPAuth,"SquidLdapAuthBanner","{authenticate_users_explain}");
    $form[]=$tpl->field_text("SquidLdapAuthBanner","{banner}",$SquidLdapAuthBanner);
    $EXPLAIn=NULL;

    if($EnableOpenLDAP==0){
        $EXPLAIn="<STRONG>{please_enable_openldap_feature}</STRONG>";
        $security="disabled";
    }


    $html[]=$tpl->form_outside(null, $form,$EXPLAIn,"{apply}",
        "dialogInstance2.close();LoadAjax('auth-table','fw.proxy.members.engines.php?table=yes');$jsrestart",$security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function radius_config_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{RADIUSAuthentication}","$page?radius-config-popup=yes");
    return true;
}

function RestartJS(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $after[]="LoadAjax('auth-table','fw.proxy.members.engines.php?table=yes');";
    $after[]="LoadAjaxSilent('top-barr','fw-top-bar.php');";


    return $tpl->framework_buildjs("/proxy/ntlm/reconfigure","onlyntlm.progress","onlyntlm.progress.log","progress-squidauth-restart",@implode(";",$after));

}

function radius_config_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$security="AsSquidAdministrator";

    $jsrestart=RestartJS();


	
	$SquidRadiusAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRadiusAuth"));
	$radiusserver=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("radiusserver");
	$radiuspassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("radiuspassword");
	$radiusidentifier=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("radiusidentifier");
	$radiusport=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("radiusport"));
	$SquidLdapAuthBanner=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLdapAuthBanner"));
	if($SquidLdapAuthBanner==null){$SquidLdapAuthBanner="Basic credentials, Please logon...";}
	
	if($radiusport==0){$radiusport=1812;}
	
	$form[]=$tpl->field_checkbox("SquidRadiusAuth","{authenticate_users_radius}",$SquidRadiusAuth,true,"{authenticate_users_radius}");
	$form[]=$tpl->field_text("SquidLdapAuthBanner","{banner}",$SquidLdapAuthBanner);
	$form[]=$tpl->field_text("radiusserver","{radius_server}", $radiusserver,true);
	$form[]=$tpl->field_numeric("radiusport","{radius_server_port}", $radiusport);
	$form[]=$tpl->field_text("radiusidentifier","{radiusidentifier}",$radiusidentifier,false,"{radiusidentifier_explain}");
	$form[]=$tpl->field_password2("radiuspassword", "{radiuspassword}", $radiuspassword);
	$html[]=$tpl->form_outside(null, $form,null,"{apply}",
        "dialogInstance2.close();LoadAjax('auth-table','fw.proxy.members.engines.php?table=yes');$jsrestart",$security);
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}

function SaveRadius():bool{
	$tpl=new template_admin();
	
	if($_POST["SquidRadiusAuth"]==1){
        admin_tracks("Activate Proxy Radius Authentication");
		$_POST["SquidStandardLDAPAuth"]=0;
		$_POST["SquidExternLDAPAUTH"]=0;
	}else{
        admin_tracks("Saving Proxy Radius Authentication");
    }
	
	
	$tpl->SAVE_POSTs();
	return true;
}

function Save(){
	$tpl=new template_admin();
	if($_POST["SquidStandardLDAPAuth"]==1){
		$_POST["SquidExternLDAPAUTH"]=0;
		$_POST["SquidRadiusAuth"]=0;
	}
	admin_tracks("Save Proxy Authentication method..");
	$tpl->SAVE_POSTs();
}

function remote_ldap_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{use_remote_ldap}","$page?remote-ldap-popup=yes");
    return true;
}

function SquidExternalAuth():array{
    $EXTERNAL_LDAP_AUTH_PARAMS=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidExternalAuth"));
    if(!isset($EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"])){$EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"]=null;}
    if(!isset($EXTERNAL_LDAP_AUTH_PARAMS["ldap_port"])){$EXTERNAL_LDAP_AUTH_PARAMS["ldap_port"]=null;}
    if(!isset($EXTERNAL_LDAP_AUTH_PARAMS["ldap_user"])){$EXTERNAL_LDAP_AUTH_PARAMS["ldap_user"]=null;}
    if(!isset($EXTERNAL_LDAP_AUTH_PARAMS["ldap_password"])){$EXTERNAL_LDAP_AUTH_PARAMS["ldap_password"]=null;}
    if(!isset($EXTERNAL_LDAP_AUTH_PARAMS["ldap_suffix"])){$EXTERNAL_LDAP_AUTH_PARAMS["ldap_suffix"]=null;}
    if(!isset($EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_users"])){$EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_users"]=null;}
    if(!isset($EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group"])){$EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group"]=null;}

    if(!isset($EXTERNAL_LDAP_AUTH_PARAMS["auth_banner"])){$EXTERNAL_LDAP_AUTH_PARAMS["auth_banner"]=null;}
    if(!isset($EXTERNAL_LDAP_AUTH_PARAMS["ldap_user_attribute"])){$EXTERNAL_LDAP_AUTH_PARAMS["ldap_user_attribute"]=null;}
    if(!isset($EXTERNAL_LDAP_AUTH_PARAMS["ldap_group_attribute"])){$EXTERNAL_LDAP_AUTH_PARAMS["ldap_group_attribute"]=null;}
    if(!isset($EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_search_group"])){$EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_search_group"]=null;}
    if(!isset($EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group_attribute"])){$EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group_attribute"]=null;}
    if(!isset($EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group"])){$EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group"]=null;}
    return $EXTERNAL_LDAP_AUTH_PARAMS;
}

function remote_ldap_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$security="AsSquidAdministrator";
	$SquidLdapAuthBanner=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLdapAuthBanner");
	if($SquidLdapAuthBanner==null){$SquidLdapAuthBanner="Basic credentials, Please logon...";}
	$SquidExternLDAPAUTH=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidExternLDAPAUTH"));

    $EXTERNAL_LDAP_AUTH_PARAMS=SquidExternalAuth();
	$ldap_server=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"];
	$ldap_port=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_port"];
	$userdn=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_user"];
	$ldap_password=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_password"];
	$ldap_suffix=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_suffix"];
	$ldap_filter_users=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_users"];
	$ldap_filter_group=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group"];

	$auth_banner=$EXTERNAL_LDAP_AUTH_PARAMS["auth_banner"];
	$ldap_user_attribute=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_user_attribute"];
	$ldap_group_attribute=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_group_attribute"];
	$ldap_filter_search_group=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_search_group"];
	$ldap_filter_group_attribute=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group_attribute"];
	
	if($auth_banner==null){$auth_banner=$SquidLdapAuthBanner;}
	if($ldap_filter_users==null){$ldap_filter_users="sAMAccountName=%s";}
	if($ldap_filter_group==null){$ldap_filter_group="(&(objectclass=person)(sAMAccountName=%u)(memberof=*))";}
	if($ldap_port==null){$ldap_port=389;}
	
	if($ldap_filter_users==null){$ldap_filter_users="sAMAccountName=%s";}
	if($ldap_user_attribute==null){$ldap_user_attribute="sAMAccountName";}
	if($ldap_filter_group==null){$ldap_filter_group="(&(objectclass=person)(sAMAccountName=%u)(memberof=*))";}
	if($ldap_filter_search_group==null){$ldap_filter_search_group="(&(objectclass=group)(sAMAccountName=%s))";}
	if($ldap_group_attribute==null){$ldap_group_attribute="sAMAccountName";}
	if($ldap_filter_group_attribute==null){$ldap_filter_group_attribute="memberof";}


    $jsrestart=RestartJS();

	$form[]=$tpl->field_checkbox("SquidExternLDAPAUTH","{authenticate_users_remote_db}",$SquidExternLDAPAUTH,false,"{SQUID_LDAP_AUTH_EXT}");
	$form[]=$tpl->field_text("auth_banner","{auth_banner}",$auth_banner);
	
	$ldap_server_id=md5("ldap_server$tpl->suffixid");
	$ldap_port_id=md5("ldap_port$tpl->suffixid");

	$form[]=$tpl->field_text("ldap_server","{openldap_server}",$ldap_server);
	$form[]=$tpl->field_numeric("ldap_port","{listen_port}", $ldap_port);
	$form[]=$tpl->field_text("ldap_user","{userdn}",$userdn,true,"");
	$form[]=$tpl->field_password2("ldap_password", "{ldap_password}", $ldap_password);
	$form[]=$tpl->field_browse_suffix("ldap_suffix", "{ldap_suffix}", $ldap_suffix,null,$ldap_server_id,$ldap_port_id);
	
	$form[]=$tpl->field_section("{members}");
	$form[]=$tpl->field_text("ldap_filter_users","{ldap_filter_users}",$ldap_filter_users,true,"");
	$form[]=$tpl->field_text("ldap_user_attribute","{ldap_user_attribute}",$ldap_user_attribute,true,"");
	$form[]=$tpl->field_section("{groups2}");
	$form[]=$tpl->field_text("ldap_filter_group","{search_users_in_groups}",$ldap_filter_group,true,"");
	$form[]=$tpl->field_text("ldap_filter_group_attribute","{attribute}",$ldap_filter_group_attribute,true,"");
	$form[]=$tpl->field_text("ldap_filter_search_group","{ldap_filter_search_groups}",$ldap_filter_search_group,true,"");
	$form[]=$tpl->field_text("ldap_group_attribute","{ldap_group_attribute}",$ldap_group_attribute,true,"");
	
	$html[]=$tpl->form_outside(null,  $form,null,"{apply}",
        "dialogInstance2.close();LoadAjax('auth-table','fw.proxy.members.engines.php?table=yes');$jsrestart",$security);
	echo $tpl->_ENGINE_parse_body($html);
    return true;
	
}

function local_ldap_status():bool{
    $tpl=new template_admin();
    $SquidStandardLDAPAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidStandardLDAPAuth"));
    if($SquidStandardLDAPAuth==0){
        return false;
    }

    $fsock = @fsockopen("127.0.0.1", 389, $errno, $errstr, 2);
    if ( ! $fsock ){
        echo $tpl->widget_rouge("127.0.0.1:389 {failed}","{error} $errno<br>$errstr");
        return false;
    }
    $ldap_server=str_replace(".","\.","127.0.0.1");
    $f=explode("\n",@file_get_contents("/etc/squid3/authenticate.conf"));

    $found=false;
    foreach ($f as $line){

        if(preg_match("#basic_ldap_auth.*?-h $ldap_server -p 389#",$line)){
            $found=true;
            break;
        }

    }
    if(!$found) {
        echo $tpl->widget_jaune("{status}","{disconnected} ($ldap_server)");
        return false;
    }

    $f=explode("\n",@file_get_contents("/etc/squid3/http_access_final.conf"));
    foreach ($f as $line){
        if(preg_match("#http_access deny.*?ldapauth#",$line)){
            basic_authenticators_status();
            return true;
        }
    }

    echo $tpl->widget_jaune("{status}","{disconnected} (2)");
    return true;

}

function basic_authenticators_status():bool{
    $tpl        = new template_admin();
    $manager    = new cache_manager();
    $requests   = 0;
    $processes  = 0;
    $data       = explode("\n",$manager->makeQuery("basicauthenticator"));

    foreach ($data as $line){
        $line=trim($line);
        if(preg_match("#number active:\s+([0-9]+) of ([0-9]+)#",$line,$re)){
            $processes="{$re[1]}/{$re[2]}";
        }
        if(preg_match("#requests sent:\s+([0-9]+)#",$line,$re)){
            $requests=intval($re[1]);

        }
    }



    $requests=$tpl->FormatNumber($requests);
    echo $tpl->widget_vert("{processes} $processes<br> LDAP {requests}:","$requests");
    return true;

}


function remote_ldap_status(){
    $tpl=new template_admin();


    $SquidExternLDAPAUTH=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidExternLDAPAUTH"));
    $EXTERNAL_LDAP_AUTH_PARAMS=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidExternalAuth"));
    $ldap_server=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"];
    $ldap_port=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_port"];

    if($SquidExternLDAPAUTH==0){
        return;
    }

    $fsock = @fsockopen($ldap_server, $ldap_port, $errno, $errstr, 2);
    if ( ! $fsock ){
        echo $tpl->widget_rouge("$ldap_server:$ldap_port {failed}","{error} $errno<br>$errstr");
        return;
    }

    $ldap_server=str_replace(".","\.",$ldap_server);
    $f=explode("\n",@file_get_contents("/etc/squid3/authenticate.conf"));

    $found=false;
    foreach ($f as $line){

        if(preg_match("#basic_ldap_auth.*?-h $ldap_server -p $ldap_port#",$line)){
            $found=true;
            break;
        }

    }
    if(!$found) {
        echo $tpl->widget_jaune("{status}","{disconnected} (1)");
        return false;
    }

    $f=explode("\n",@file_get_contents("/etc/squid3/http_access_final.conf"));
    foreach ($f as $line){
        if(preg_match("#http_access deny.*?ldapauth#",$line)){
            basic_authenticators_status();
            return true;
        }
    }

    echo $tpl->widget_jaune("{status}","{disconnected} (2)");




}

function SaveRemoteLDAP(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidExternLDAPAUTH",$_POST["SquidExternLDAPAUTH"]);
	$EXTERNAL_LDAP_AUTH_PARAMS=base64_encode(serialize($_POST));
	$GLOBALS["CLASS_SOCKETS"]->SaveConfigFile($EXTERNAL_LDAP_AUTH_PARAMS, "SquidExternalAuth");
	
	if($_POST["SquidExternLDAPAUTH"]==1){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidStandardLDAPAuth", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidRadiusAuth", 0);
	}
	
	$EXTERNAL_LDAP_AUTH_PARAMS=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidExternalAuth"));
	$ldap_server=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"];
	$ldap_port=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_port"];

	$CONNECTION=@ldap_connect($ldap_server,$ldap_port);
	
	if(!$CONNECTION){
		echo $tpl->_ENGINE_parse_body("jserror:{failed_connect_ldap} $ldap_server:$ldap_port");
		return;
	}
	@ldap_set_option($CONNECTION, LDAP_OPT_PROTOCOL_VERSION, 3);
	@ldap_set_option($CONNECTION, LDAP_OPT_REFERRALS, 0);
	@ldap_set_option($CONNECTION, LDAP_OPT_PROTOCOL_VERSION, 3); // on passe le LDAP en version 3, necessaire pour travailler avec le AD
	@ldap_set_option($CONNECTION, LDAP_OPT_REFERRALS, 0);
	
	$userdn=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_user"];
	$ldap_password=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_password"];
	$BIND=@ldap_bind($CONNECTION, $userdn, $ldap_password);
	
		if(!$BIND){
		$error=@ldap_err2str(@ldap_errno($CONNECTION));
		if (@ldap_get_option($CONNECTION, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {$error=$error." $extended_error";}
		@ldap_close($CONNECTION);
		echo $tpl->_ENGINE_parse_body("jserror:$error");
		@ldap_close($CONNECTION);
		return;
	}
	
	@ldap_close($CONNECTION);
	
}
