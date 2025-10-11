<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["local-ldap"])){local_ldap();exit;}
if(isset($_GET["remote-ldap"])){remote_ldap();exit;}
if(isset($_GET["radius-config"])){radius_config();exit;}
if(isset($_POST["SquidStandardLDAPAuth"])){Save();exit;}
if(isset($_POST["radiusserver"])){SaveRadius();exit;}
if(isset($_POST["SquidExternLDAPAUTH"])){SaveRemoteLDAP();exit;}
if(isset($_GET["remote-ldap-status"])){remote_ldap_status();exit;}
if(isset($_GET["local-ldap-status"])){local_ldap_status();exit;}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["table"])){table();exit;}
page();
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{your_proxy} {authentication}",
        ico_groups_finders,"{your_proxy_authentication_text}","$page?start=yes",
        "proxy-auth-types","progress-squidauth-restart",false,"table-loader-squid-auth");



	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return true;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<table style='width:100%;margin-top:15px'>";
    $html[]="<tr>";
    $html[]="<td style='width:1%;vertical-align:top' nowrap>";
    $html[]="<div id='local-ldap-status'></div>";
    $html[]="<div id='remote-ldap-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:90%;vertical-align:top'>";
    $html[]="<div id='auth-table'></div>";
    $html[]="</td>";
    $html[]="</TR>";
    $html[]="</table>";
    $html[]="<script>";

    $jsrestart=$tpl->framework_buildjs("/proxy/ntlm/reconfigure","onlyntlm.progress","onlyntlm.progress.log","progress-squidauth-restart");

    $topbuttons[] = array($jsrestart,ico_retweet,"{reconfigure}");
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["TITLE"]="{your_proxy} {authentication}";
    $TINY_ARRAY["ICO"]=ico_groups_finders;
    $TINY_ARRAY["URL"]="proxy-auth-types";
    $TINY_ARRAY["EXPL"]="{your_proxy_authentication_text}";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $js1=$tpl->RefreshInterval_js("local-ldap-status","fw.proxy.members.php","local-ldap-status=yes");
    $js2=$tpl->RefreshInterval_js("remote-ldap-status","fw.proxy.members.php","remote-ldap-status=yes");

    $html[]=$js1;
    $html[]=$js2;
    $html[]=$jstiny;
    $html[]="LoadAjax('auth-table','$page?table=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SquidRadiusAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRadiusAuth"));
    $SquidStandardLDAPAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidStandardLDAPAuth"));
    $SquidExternLDAPAUTH=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidExternLDAPAUTH"));
    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    $UseNativeKerberosAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseNativeKerberosAuth"));
    $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
    $sHowAuth=0;
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    if($EnableKerbAuth==1 OR $UseNativeKerberosAuth==1  OR $LockActiveDirectoryToKerberos==1){
        $sHowAuth=1;
    }
    if($EnableActiveDirectoryFeature==0){$sHowAuth=0;}

    $tpl->table_form_field_js("blur()");
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
            $tpl->table_form_field_text("{kerberos_authentication}","{license_error}",ico_microsoft);
    }else{
        if($sHowAuth==1){
            $tpl->table_form_field_js("Loadjs('fw.proxy.kerberos.php?js=yes')");
            $tpl->table_form_field_bool("{kerberos_authentication}",$sHowAuth,ico_microsoft);
            $SquidClientParams=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));

            if(!isset($SquidClientParams["auth_param_ntlm_children"])){
                $SquidClientParams["auth_param_ntlm_children"]=20;
            }
            if(!isset($SquidClientParams["auth_param_ntlm_startup"])){
                $SquidClientParams["auth_param_ntlm_startup"]=0;
            }
            if(!isset($SquidClientParams["auth_param_ntlm_idle"])){
                $SquidClientParams["auth_param_ntlm_idle"]=1;
            }


            if(!is_numeric($SquidClientParams["auth_param_ntlm_children"])){$SquidClientParams["auth_param_ntlm_children"]=20;}
            if(!is_numeric($SquidClientParams["auth_param_ntlm_startup"])){$SquidClientParams["auth_param_ntlm_startup"]=0;}
            if(!is_numeric($SquidClientParams["auth_param_ntlm_idle"])){$SquidClientParams["auth_param_ntlm_idle"]=1;}
            if($SquidClientParams["auth_param_ntlm_children"]<5){$SquidClientParams["auth_param_ntlm_children"]=5;}
            if($SquidClientParams["auth_param_ntlm_startup"]<5){$SquidClientParams["auth_param_ntlm_startup"]=5;}
            if($SquidClientParams["auth_param_ntlm_idle"]<1){$SquidClientParams["auth_param_ntlm_idle"]=1;}

            $CHILDREN_MAX=$SquidClientParams["auth_param_ntlm_children"];
            $CHILDREN_STARTUP=$SquidClientParams["auth_param_ntlm_children"];
            $CHILDREN_IDLE=$SquidClientParams["auth_param_ntlm_idle"];
            $tpl->table_form_field_text("{CHILDREN_MAX}", "$CHILDREN_MAX {processes}, {CHILDREN_STARTUP} $CHILDREN_STARTUP {processes}, {CHILDREN_IDLE} $CHILDREN_IDLE {processes}", ico_microsoft);
        }else {
            $tpl->table_form_field_bool("{kerberos_authentication}", $sHowAuth, ico_microsoft);
        }
    }


    $tpl->table_form_field_js("Loadjs('fw.proxy.members.php?local-ldap-js=yes')");
    $tpl->table_form_field_bool("{authenticate_users_local_db}",$SquidStandardLDAPAuth,ico_users);

    if($SquidStandardLDAPAuth==1){
       $tpl->table_form_field_js("Loadjs('fw.proxy.general.php?basic-authentication-js=yes&restart-id=progress-squidauth-restart')");
       $tpl->table_form_field_text("{CHILDREN_MAX}", BasicAuthText(), ico_timeout);
    }



    $tpl->table_form_field_js("Loadjs('fw.proxy.members.php?remote-ldap-js=yes')");
    if($SquidExternLDAPAUTH==1){
        $EXTERNAL_LDAP_AUTH_PARAMS=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidExternalAuth"));
        $ldap_server=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"];
        $ldap_port=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_port"];
        $userdn=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_user"];
        $tpl->table_form_field_text("{authenticate_users_remote_db}","$userdn@$ldap_server:$ldap_port",ico_users);

        $tpl->table_form_field_js("Loadjs('fw.proxy.general.php?basic-authentication-js=yes&restart-id=progress-squidauth-restart')");
        $tpl->table_form_field_text("{CHILDREN_MAX}", BasicAuthText(), ico_timeout);

    }else{
        $tpl->table_form_field_bool("{authenticate_users_remote_db}",$SquidExternLDAPAUTH,ico_users);
    }
    $tpl->table_form_field_js("Loadjs('fw.proxy.members.php?radius-config-js=yes')");
    if($SquidRadiusAuth==1){
        $radiusserver=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("radiusserver");
        $radiusidentifier=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("radiusidentifier");
        $radiusport=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("radiusport"));
        $tpl->table_form_field_text("{authenticate_users_radius}","$radiusidentifier@$radiusserver:$radiusport",ico_users);

    }else{
        $tpl->table_form_field_bool("{authenticate_users_radius}",$SquidRadiusAuth,ico_users);
    }
    $html[]=$tpl->table_form_compile();

    $jsrestart=$tpl->framework_buildjs("/proxy/ntlm/reconfigure","onlyntlm.progress","onlyntlm.progress.log","progress-squidauth-restart");



    $topbuttons[] = array($jsrestart,ico_retweet,"{reconfigure}");
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["TITLE"]="{your_proxy} {authentication}";
    $TINY_ARRAY["ICO"]=ico_groups_finders;
    $TINY_ARRAY["EXPL"]="{your_proxy_authentication_text}";

    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>$headsjs</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function BasicAuthText():string{

    $SquidClientParams=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));

    if(!isset($SquidClientParams["auth_param_basic_children"])){
        $SquidClientParams["auth_param_basic_children"]=3;
    }
    if(!isset($SquidClientParams["auth_param_basic_startup"])){
        $SquidClientParams["auth_param_basic_startup"]=2;
    }
    if(!isset($SquidClientParams["auth_param_basic_idle"])){
        $SquidClientParams["auth_param_basic_idle"]=1;
    }

    if(!is_numeric($SquidClientParams["auth_param_basic_children"])){$SquidClientParams["auth_param_basic_children"]=3;}
    if(!is_numeric($SquidClientParams["auth_param_basic_startup"])){$SquidClientParams["auth_param_basic_startup"]=2;}
    if(!is_numeric($SquidClientParams["auth_param_basic_idle"])){$SquidClientParams["auth_param_basic_idle"]=1;}
    if(intval($SquidClientParams["auth_param_basic_children"])==0){$SquidClientParams["auth_param_basic_children"]=3;}
    if(intval($SquidClientParams["auth_param_basic_startup"])==0){$SquidClientParams["auth_param_basic_startup"]=2;}
    if(intval($SquidClientParams["auth_param_basic_idle"])==0){$SquidClientParams["auth_param_basic_idle"]=1;}

    $CHILDREN_MAX=$SquidClientParams["auth_param_basic_children"];
    $CHILDREN_STARTUP=$SquidClientParams["auth_param_basic_startup"];
    $CHILDREN_IDLE=$SquidClientParams["auth_param_basic_idle"];

    return "$CHILDREN_MAX {processes}, {CHILDREN_STARTUP} $CHILDREN_STARTUP {processes}, {CHILDREN_IDLE} $CHILDREN_IDLE {processes}";


}
