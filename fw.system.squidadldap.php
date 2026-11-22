<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
putenv('LDAPTLS_REQCERT=never');
$_ENV['LDAPTLS_REQCERT'] = 'never';

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.ActiveDirectoryRootDSE.inc");
include_once(dirname(__FILE__)."/ressources/class.ActiveDirectory.inc");

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){features();exit;}
if(isset($_POST["fullhosname"])){Save();exit;}
if(isset($_GET["enable-feature"])){enable_feature();exit;}
if(isset($_GET["disable-feature"])){disable_feature();exit;}

if(isset($_GET["repair"])){reconfigure();exit;}
page();




function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("Active Directory &raquo;&raquo {squid_ldap_auth}","fab fa-windows",'{squid_ldap_auth_activedirectory}',
        "$page?table=yes","adauth-ldap","adauth-ldap-restart",false,"adauth-ldap-table");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Active Directory/{squid_ldap_auth}",$html);
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);

}

function enable_feature():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    // Squid runs /lib/squid3/artica-proxy-auth, installed by articarest from the
    // shipped copy. With neither of them, enabling the method writes an
    // auth_param line to a missing program and the proxy no longer starts.
    if(!is_file("/usr/share/artica-postfix/bin/artica-proxy-auth") && !is_file("/lib/squid3/artica-proxy-auth")){
        $tpl->js_error("{squid_ldap_auth_helper_missing}");
        return false;
    }

    $err=squidadldap_set(true);
    if($err<>""){
        $tpl->js_error($err);
        return false;
    }
    admin_tracks("Enabled the proxy LDAP authentication (EnableAdLDAPAuth=1)");
    echo "LoadAjax('adauth-ldap-table','$page?table=yes');";
    return true;
}

function disable_feature():bool{
    $tpl=new template_admin();
    $err=squidadldap_set(false);
    if($err<>""){
        $tpl->js_error($err);
        return false;
    }
    admin_tracks("Disconnected the proxy LDAP authentication (EnableAdLDAPAuth=0)");
    echo "document.location.href='/ad-state';";
    return true;
}

// articarest PUT /proxy/auth/ldap : enregistre EnableAdLDAPAuth puis reconstruit
// authenticate.conf et RECHARGE Squid (/proxy/nohup/reconfigure ne rechargeait pas).
// Rend "" si accepte, sinon le message d'erreur.
function squidadldap_set($enabled):string{
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){
        return "{ERROR_NO_PRIVS2}";
    }
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/proxy/auth/ldap",
        array("enabled"=>(bool)$enabled)));
    if(!is_object($json)){
        return "articarest: /proxy/auth/ldap unavailable";
    }
    if(!property_exists($json,"Status") || !$json->Status){
        return property_exists($json,"Error")?(string)$json->Error:"error";
    }
    return "";
}

function reconfigure():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    header("content-type: application/x-javascript");

    // The directory connections the LDAP helper binds against are written by
    // the daemon (they used to be a side effect of exec.hotspot.templates.php).
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/activedirectory/connections/hotspot"));
    if(!$json || !property_exists($json,"Status") || !$json->Status){
        $tpl->js_error(($json && property_exists($json,"Error"))?$json->Error:"{error}");
        return false;
    }

    echo "LoadAjax('adauth-ldap-table','$page?table=yes');";
    return true;


}

function widget_on():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ldap=new ActiveDirectory();


    list($ok,$info)=$ldap->TestsGoLDAP();
    if(!$ok){
        $btn["js"] = "Loadjs('$page?disable-feature=yes')";
        $btn["name"] = "{disable}";
        $btn["ico"] = "fas fa-trash";

        $btn2["name"] = "{repair}";
        $btn2["js"] = "Loadjs('$page?repair=yes');";
        return $tpl->widget_h("yellow","fab fab fa-windows",$info,"{active_directory_authentication}",$btn2,$btn);

    }



    $btn["js"] = "Loadjs('$page?disable-feature=yes')";
    $btn["name"] = "{disable}";
    $btn["ico"] = "fas fa-trash";
    $btn["help"]="https://wiki.articatech.com/proxy-service/authentication/ldap";
    return $tpl->widget_h("green","fas fa-users",
        "{enabled}","{squid_ldap_auth}",$btn);


}

function features(){

    $page=CurrentPageName();
    $tpl=new template_admin();


    $authconv=$tpl->widget_grey("{requests}",0);
    $EnableAdLDAPAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAdLDAPAuth"));


    if($EnableAdLDAPAuth==0){
        $btn["js"] = "Loadjs('$page?enable-feature=yes')";
        $btn["name"] = "{activate}";
        $btn["ico"] = ico_cd;
        $btn["help"]="https://wiki.articatech.com/proxy-service/authentication/ldap";
        $feature_enabled=$tpl->widget_h("grey","fa far fa-times-circle",
            "{disabled}","{squid_ldap_auth}",$btn);

    }else{
        $feature_enabled= widget_on();
    }

    if($EnableAdLDAPAuth==1) {
        $AUTH = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("basicauthenticator_current"));
        if ($AUTH > 0) {
            $AUTH = $tpl->FormatNumber($AUTH);
            $authconv = $tpl->widget_vert("{requests}", $AUTH);
        } else {
            $authconv = $tpl->widget_grey("{requests}", 0);
        }
    }


    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td valign='top' style='width:250px'>";
    $html[]=$feature_enabled."<br>".$authconv;
    $html[]="</td>";
    $html[]="<td valign='top' style='width:99%;padding-left:15px'>";
    $html[]="<div id='plugins-config'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('plugins-config','fw.proxy.general.php?basic-auth=yes&restart-id=adauth-ldap-restart');";
    $html[]="</script>";


    echo $tpl->_ENGINE_parse_body($html);


}

function Save(){
    $tpl=new template_admin();
    ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
    $tpl->CLEAN_POST();
    $IP=new IP();
    $KerbAuthInfos=base64_encode(serialize($_POST));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerbAuthInfos",$KerbAuthInfos);

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NtpdateAD",$_POST["NtpdateAD"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LockActiveDirectoryToKerberosBasic",$_POST["LockActiveDirectoryToKerberosBasic"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("AdNotResolvDC",$_POST["AdNotResolvDC"]);


    $kerberosActiveDirectoryHost=$_POST["fullhosname"];
    $ipaddr=$GLOBALS["CLASS_SOCKETS"]->gethostbyname($kerberosActiveDirectoryHost);
    if(!$IP->isValid($ipaddr)){
        $err=$tpl->_ENGINE_parse_body("{ad_full_hostname_resolve_issue}");
        $err=str_replace("%s",$kerberosActiveDirectoryHost,$err);
        echo (new template_admin())->post_error("$err");
        return false;
    }

    $_POST["ADNETIPADDR"]=$ipaddr;
    $_POST["LDAP_SERVER"]=$_POST["fullhosname"];
    $ldap_port=389;
    $ldapserver=$_POST["fullhosname"];
    $KerberosUsername=$_POST["WINDOWS_SERVER_ADMIN"];
    $KerberosPassword=$_POST["WINDOWS_SERVER_PASS"];
    $COMPUTER_BRANCH=$_POST["COMPUTER_BRANCH"];
    $ldap_prefix="ldap://";
    if($_POST["LDAP_SSL"]==1){$_POST["LDAP_TLS"]=0;$ldap_port=636;$ldap_prefix="ldaps://";}

    $TR=explode("@",$KerberosUsername);
    $_POST["WINDOWS_DNS_SUFFIX"]=$TR[1];
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WINDOWS_SERVER_ADMIN",$KerberosUsername);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WINDOWS_DNS_SUFFIX",$ldap_prefix);


    $ldap_connection=@ldap_connect($ldap_prefix.$ldapserver.":".$ldap_port);
    if(!$ldap_connection){
        $DIAG[]="{Connection_Failed_to_connect_to_DC} $ldap_prefix$ldapserver:$ldap_port";
        if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {$DIAG[]="$extended_error";}
        echo (new template_admin())->post_error("".$tpl->_ENGINE_parse_body(@implode("<br>", $DIAG)));
        @ldap_close();
        return false;
    }

    if($_POST["LDAP_SSL"]==1) {
        ldap_set_option($ldap_connection, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
    }

    ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);


    if($_POST["LDAP_TLS"]==1){
        if(!ldap_start_tls($ldap_connection)){
            $error_code=ldap_errno($ldap_connection);
            $DIAG[]="{useTLS} {failed} error code $error_code";
            if($error_code==52){
                $DIAG[]="<hr>";
                $DIAG[]="Your domain controller does not issue a certificate, please follow the right procedure";
                $DIAG[]="<hr>";
            }



            $DIAG[]=ldap_err2str(ldap_errno($ldap_connection));
            if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {$DIAG[]="$extended_error";}
            echo (new template_admin())->post_error("".$tpl->_ENGINE_parse_body(@implode("<br>", $DIAG)));
            return false;
        }
    }

    $bind=ldap_bind($ldap_connection, $KerberosUsername,$KerberosPassword);
    if(!$bind){
        $error_code=ldap_errno($ldap_connection);
        $DIAG[]="{login_Failed_to_connect_to_DC} Error $error_code {with} $KerberosUsername";

        $DIAG[]=ldap_err2str($error_code);
        if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {$DIAG[]="$extended_error";}
        echo (new template_admin())->post_error("".$tpl->_ENGINE_parse_body(@implode("<br>", $DIAG)));
        return false;
    }


    $dse=new ad_rootdse($_POST["ADNETIPADDR"], $ldap_port, $KerberosUsername, $KerberosPassword,$_POST["LDAP_SSL"],$_POST["LDAP_TLS"]);
    $RootDSE=$dse->RootDSE();
    if(!$dse->ok){
        echo (new template_admin())->post_error("".$tpl->_ENGINE_parse_body($dse->mysql_error));
        return false;
    }

    if($RootDSE<>null){
        $_POST["LDAP_SUFFIX"]=$RootDSE;
    }

    $myhostname=explode(".",$GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
    $hostname=strtolower($myhostname[0]);

    $dn="CN=$hostname,$COMPUTER_BRANCH,{$_POST["LDAP_SUFFIX"]}";
    $search=@ldap_read($ldap_connection,$dn,'(objectClass=*)',array());
    if($search){
        $PLEASE_REMOVE_CMP_AD=$tpl->_ENGINE_parse_body("{PLEASE_REMOVE_CMP_AD}");
        $PLEASE_REMOVE_CMP_AD=str_replace("%cmp",$hostname,$PLEASE_REMOVE_CMP_AD);
        echo (new template_admin())->post_error("$PLEASE_REMOVE_CMP_AD");
        return;
    }

    $KerbAuthInfos=base64_encode(serialize($_POST));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerbAuthInfos",$KerbAuthInfos);







}

function file_uploaded(){
    if(!fw_progress_page_priv("AsSquidAdministrator|AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $file=fw_progress_upload_name($_GET["file-uploaded"] ?? "");   // REST segment, see fw_progress_upload_name
    if($file===""){return fw_progress_bad_value("file-uploaded");}

    $jsrestart=$tpl->framework_buildjs(
        "/kerberos/keytab/install/$file",
        "ActiveDirectoryFeature.progress",
        "ActiveDirectoryFeature.log",
        "kerberos-ad-restart",
        "LoadAjax('table-kerberos','$page?table=yes');",
    );


    echo $jsrestart;
}
