<?php
putenv('LDAPTLS_REQCERT=never');
$_ENV['LDAPTLS_REQCERT'] = 'never';

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.ActiveDirectoryRootDSE.inc");
include_once(dirname(__FILE__)."/ressources/class.activedirectory.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/PowerShellKTPass.inc.php");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["ActiveDirectoryReportInterface-js"])){ActiveDirectoryReportInterface_js();exit;}
if(isset($_GET["ActiveDirectoryReportInterface-popup"])){ActiveDirectoryReportInterface_popup();exit;}
if(isset($_GET["auth-js"])){auth_js();exit;}
if(isset($_GET["auth-popup"])){auth_popup();exit;}
if(isset($_POST["WINDOWS_SERVER_ADMIN_MOD"])){auth_save();exit;}
if(isset($_POST["ktpass"])){ktpass_save();exit;}
if(isset($_GET["ticket-audit-js"])){ticket_audit_js();exit;}
if(isset($_GET["table"])){features();exit;}
if(isset($_POST["fullhosname"])){Save();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["configuration-file"])){configuration_file_js();exit;}
if(isset($_GET["configuration-popup"])){configuration_file();exit;}
if(isset($_POST["conf"])){configuration_file_save();exit;}
if(isset($_GET["CheckUPSettings"])){CheckUPSettings();exit;}
if(isset($_GET["ktpass-js"])){ktpass_js();exit;}
if(isset($_GET["ktpass-popup"])){ktpass_popup();exit;}
if(isset($_GET["ktpass2"])){ktpass_popup2();exit;}
if(isset($_GET["ktpass3"])){ktpass_popup3();exit;}
if(isset($_GET["ktpass4"])){ktpass_popup4();exit;}
if(isset($_GET["ticket-audit"])){ticket_audit();exit;}
if(isset($_GET["ticket-audit-popup"])){ticket_audit_popup();exit;}
if(isset($_GET["renew-keytab-js"])){renew_key_tab_js();exit;}
if(isset($_GET["download-ps1"])){DownloadKeyTab();exit;}
page();

function renew_key_tab_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/mktutils/keytab/renew"));
    if(!$json->Status){
        return $tpl->js_error($json->Error);
    }
    return $tpl->js_ok("");
}

function ktpass_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{manual_install}","$page?ktpass-popup=yes",550);
}
function auth_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{authentication}","$page?auth-popup=yes",550);
}
function ActiveDirectoryReportInterface_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{authentication} {last_events}","$page?ActiveDirectoryReportInterface-popup=yes");
}
function ActiveDirectoryReportInterface_popup():bool{
    $ActiveDirectoryReportInterface=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryReportInterface"));
    $html=array();
    $tb=explode("\n",$ActiveDirectoryReportInterface);
    foreach ($tb as $line) {
        $class="";
        if(strpos($line," Error:")>0){
            $class="text-danger";
        }
        if(strpos($line,"kinit -V")>0){
            continue;
        }

        if(preg_match("#(failed|Error|Invalid)#i",$line)){
            $class="text-danger";
        }


        $html[]="<div class='$class'>".$line."</div>";
    }

    echo @implode("\n",$html);
    return true;
}
function configuration_file_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{view_config}","$page?configuration-popup=yes",900);
}
function ktpass_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<div id='ktpass-wizard'>";

    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!is_array($array)){$array=array();}
    if(!isset($array["WINDOWS_DNS_SUFFIX"])){$array["WINDOWS_DNS_SUFFIX"]="";}

    $WINDOWS_DNS_SUFFIX=strval($array["WINDOWS_DNS_SUFFIX"]);
    if(strlen($WINDOWS_DNS_SUFFIX)<3){
        $myhostname=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname");
        $df=explode(".",$myhostname);
        unset($df[0]);
        $WINDOWS_DNS_SUFFIX=implode(".",$df);
    }

    $form[]=$tpl->field_hidden("ktpass","yes");
    $form[]=$tpl->field_text("WINDOWS_DNS_SUFFIX","{WINDOWS_DNS_SUFFIX}",$WINDOWS_DNS_SUFFIX);
    $form[] = $tpl->field_text("ADDITIONAL_SPNs", "{additional_spns}", "", false,"{additional_spns_explain}");

    $html[]= $tpl->form_outside("",$form,"{wizard_kerberos}","{next}","LoadAjax('ktpass-wizard','$page?ktpass2=yes')");
    $html[]="</div>";

    $sock=new sockets();
    $data=json_decode($sock->REST_API("/mktutils/klist/dump"));
    if(property_exists($data->Info,"DEFAULT_PRINCPAL")){
        $html[]="<script>";
        $html[]="LoadAjax('ktpass-wizard','$page?ktpass4=yes');";
        $html[]="</script>";
    }


    echo $tpl->_ENGINE_parse_body($html);
}
function ktpass_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!is_array($array)){$array=array();}
    if(!isset($array["WINDOWS_SERVER_NETBIOSNAME"])) {
        $array["WINDOWS_SERVER_NETBIOSNAME"] = "";
    }
    if(!isset($array["fullhosname"])) {
        $array["fullhosname"] = "";
    }
    if(isset($_POST["fullhosname"])){
        $tb=explode(".",$_POST["fullhosname"]);
        $_POST["WINDOWS_SERVER_NETBIOSNAME"]=$tb[0];

    }
    $array["WINDOWS_SERVER_TYPE"] = "WIN_2019";

    $_POST["ADDITIONAL_SPNs"]=str_replace(";",",",$_POST["ADDITIONAL_SPNs"]);
    $_POST["ADDITIONAL_SPNs"]=str_replace(" ",",",$_POST["ADDITIONAL_SPNs"]);
    $_POST["ADDITIONAL_SPNs"]=str_replace(" ","",$_POST["ADDITIONAL_SPNs"]);
    foreach ($_POST as $key => $value) {
        $array[$key]=$value;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerbAuthInfos",base64_encode(serialize($array)));
}
function ktpass_popup2(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sock=new sockets();
    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!is_array($array)){$array=array();}
    $WINDOWS_DNS_SUFFIX=$array["WINDOWS_DNS_SUFFIX"];
    if(!isset($array["WINDOWS_SERVER_NETBIOSNAME"])) {
        $array["WINDOWS_SERVER_NETBIOSNAME"] = "";
    }
    if(!isset($array["fullhosname"])) {
        $array["fullhosname"] = "";
    }


    if (strlen($array["fullhosname"])<3){
        $json=json_decode($sock->REST_API("/mktutils/kdc/resolve/$WINDOWS_DNS_SUFFIX"));
        if(isset($json->Info[0])) {
            if (!$json->Status) {
                echo $tpl->div_warning($json->Error);
                return true;
            }
            $array["fullhosname"] = $json->Info[0]->Target;
            $array["WINDOWS_SERVER_NETBIOSNAME"] = $json->HostInfo->NetBiosName;
        }
    }
    $form[]=$tpl->field_hidden("ktpass","yes");
    $form[]=$tpl->field_hidden("WINDOWS_SERVER_NETBIOSNAME",$array["WINDOWS_SERVER_NETBIOSNAME"]);
    $form[]=$tpl->field_text("fullhosname","{ad_full_hostname}",$array["fullhosname"]);
    $html[]= $tpl->form_outside("$WINDOWS_DNS_SUFFIX",$form,"","{next}","LoadAjax('ktpass-wizard','$page?ktpass3=yes')");
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function ktpass_popup3():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<div id='kinit-progress'></div>";
    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!is_array($array)){$array=array();}

    if(!isset($array["WINDOWS_SERVER_ADMIN"])) {
        $array["WINDOWS_SERVER_ADMIN"] = "";
    }
    if(!isset($array["WINDOWS_SERVER_PASS"])) {
        $array["WINDOWS_SERVER_PASS"] = "";
    }

    $Link="s_PopUpFull('https://wiki.articatech.com/proxy-service/SPNRights','1024','900');";

    $js=$tpl->framework_buildjs("/mktutils/kinit",
        "kinit.progress","kinit.log","kinit-progress","LoadAjax('ktpass-wizard','$page?ktpass4=yes')");

    $form[]=$tpl->field_hidden("ktpass","yes");
    $form[] = $tpl->field_text("WINDOWS_SERVER_ADMIN", "{administrator}", $array["WINDOWS_SERVER_ADMIN"], true);
    $form[] = $tpl->field_password("WINDOWS_SERVER_PASS", "{password}", $array["WINDOWS_SERVER_PASS"], true);
    $html[]= $tpl->form_outside("<a href=\"$Link\">{spauser}</a>",$form,"{spauser_explain}","{connect}",$js);
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function DownloadKeyTab():bool{
    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    $myhostname=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname");
    $tt=explode(".",$myhostname);
    unset($tt[0]);
    $DEFAULT_DOMAIN=@implode(".",$tt);
    $DEFAULT_DOMAIN_UPPER=strtoupper($DEFAULT_DOMAIN);
    $kerberosRealm=$DEFAULT_DOMAIN_UPPER;
    $KerberosUsername=$array["WINDOWS_SERVER_ADMIN"];


    $data=BuildPowerShellKTPass($kerberosRealm,$myhostname,$KerberosUsername);
    $timestamp =time();
    $tsstring = gmdate('D, d M Y H:i:s ') . 'GMT';
    header("Content-Length: ".strlen($data));
    header('Content-type: application/x-powershell');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$myhostname-keytab.ps1\"");
    header("Cache-Control: no-cache, must-revalidate");
    header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', $timestamp + (60 * 60)));
    header("Last-Modified: $tsstring");
    ob_clean();
    flush();
    echo $data;
    return true;
}

function ktpass_popup4(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $myhostname=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname");
    $tt=explode(".",$myhostname);
    $Netb=$tt[0];
    unset($tt[0]);



    $bt_upload=$tpl->button_upload("krb5.keytab",$page)."&nbsp;&nbsp;";
    $makesureadcomp=$tpl->_ENGINE_parse_body("{makesureadcomp}");
    $makesureadcomp=str_replace("%s",$Netb,$makesureadcomp);

    $js="document.location.href='/$page?download-ps1=yes'";
    $button = $tpl->button_autnonome("{download_powershell_script}",$js,
        ico_download, "AsProxyMonitor");

    $html[]=$tpl->div_explain("{download_powershell_script}||{download_powershell_script_explain}<br>{download_powershell_script_exp}<br>
<strong>$makesureadcomp</strong><br>
<p style=\"font-family:'Courier New';color:black;background-color:#EEF2FE;border:1px solid #c0c0c0; font-weight:bold;padding: 9px;border-radius:5px;margin:5px;font-size: initial\">Unblock-File \"$myhostname.int-keytab.ps1\"</p>
    <div style='text-align:right;margin-top:20px'>$button</div>");




    $html[]="<div style='margin-top:20px;text-align:right'>$bt_upload</div>";
    echo $tpl->_ENGINE_parse_body($html);
}
function configuration_file(){
    $tpl=new template_admin();
    $form[]=$tpl->field_textareacode("conf",null,@file_get_contents("/etc/squid3/authenticate.conf"));
    $html[] = $tpl->form_outside("{view_config}", @implode("\n", $form), "", "{apply}", "blur()", "AsSquidAdministrator", true);
    echo $tpl->_ENGINE_parse_body($html);
}
function configuration_file_save():bool{
    $tpl=new template_admin();
    $errfile="/usr/share/artica-postfix/ressources/logs/authenticate.error";
    $tpl->CLEAN_POST();
    @file_put_contents("/usr/share/artica-postfix/ressources/conf/upload/authenticate.conf",$_POST["conf"]);
    $sock=new sockets();
    $sock->getFrameWork("squid2.php?kerberos-manual-conf=yes");

    if(is_file($errfile)){
        $data=@file_get_contents($errfile);
        if(preg_match("#^[0-9]+.*?\.conf(.+)#",$data,$re)){
            $data="Line $re[1]";
        }
        admin_tracks("Fatal error $data while modify directly proxy kerberos configuration file");
        echo "jserror:".$tpl->javascript_parse_text($data);
        return true;
    }
    admin_tracks("Success modify directly proxy kerberos configuration file");
    return true;
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $kerberos_authentication=$tpl->_ENGINE_parse_body("{kerberos_authentication}");
    $WindowsActiveDirectoryKerberos_explain=$tpl->_ENGINE_parse_body("{WindowsActiveDirectoryKerberos_explain}");
    $Myhostname=php_uname("n");
    $WindowsActiveDirectoryKerberos_explain=str_replace("%hostname%",$Myhostname,$WindowsActiveDirectoryKerberos_explain);

    $html=$tpl->page_header("Active Directory &raquo;&raquo $kerberos_authentication","fab fa-windows",$WindowsActiveDirectoryKerberos_explain,
        "$page?table=yes","single-kerberos","kerberos-single-restart",false,"table-kerberos-single");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Active Directory/$kerberos_authentication",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function Kerberos_connect():string{
    $page=CurrentPageName();
    $tpl=new template_admin();

    return $tpl->framework_buildjs("/mktutils/connect",
        "msktutils.progress","msktutils.log",
        "kerberos-single-restart",
        "LoadAjax('table-kerberos-single','$page?table=yes');LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');");
}

function features(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $NtpdateAD = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NtpdateAD"));
    $AdNotResolvDC = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AdNotResolvDC"));

    $LockActiveDirectoryToKerberos = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $LockActiveDirectoryToKerberosBasic = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberosBasic"));
    $HaClusterClient = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if ($HaClusterClient == 1) {
        $LockActiveDirectoryToKerberos = 1;
    }
    $hostname_full = php_uname("n");
    $hostname = $hostname_full;
    if (strpos($hostname, ".") > 0) {
        $tre = explode(".", $hostname);
        unset($tre[0]);

    }


    $array = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if (!is_array($array)) {
        $array = array();
    }

    if (!isset($array["COMPUTER_BRANCH"])) {
        $array["COMPUTER_BRANCH"] = "CN=Computers";
    }
    if (!isset($array["WINDOWS_SERVER_TYPE"])) {
        $array["WINDOWS_SERVER_TYPE"] = "WIN_2019";
    }
    if (!isset($array["LDAP_TLS"])) {
        $array["LDAP_TLS"] = 0;
    }

    $severtype["WIN_2003"] = "Windows 2000/2003";
    $severtype["WIN_2008AES"] = "Windows 2008/2012";
    $severtype["WIN_2016"] = "Windows 2016";
    $severtype["WIN_2019"] = "Windows 2019";

    $html[] = "<div id='CheckUPSettings'></div>";

    if ($LockActiveDirectoryToKerberos == 0) {
        $form[] = $tpl->field_text("fullhosname", "{ad_full_hostname}", $array["fullhosname"], true);
        $form[] = $tpl->field_text("COMPUTER_BRANCH", "{ad_computers_branch}", $array["COMPUTER_BRANCH"], true);
        $form[] = $tpl->field_text("ADDITIONAL_SPNs", "{additional_spns}", $array["ADDITIONAL_SPNs"], false,"{additional_spns_explain}");

        $form[] = $tpl->field_array_hash($severtype, "WINDOWS_SERVER_TYPE", "{WINDOWS_SERVER_TYPE}", $array["WINDOWS_SERVER_TYPE"]);
        $form[] = $tpl->field_checkbox("AdNotResolvDC", "{AdNotResolvDC}", $AdNotResolvDC, false,
            "url:https://wiki.articatech.com/active-directory/kdc-resolve-false;");
        $form[] = $tpl->field_checkbox("LDAP_TLS", "{useTLS}", $array["LDAP_TLS"], false, null);
        $form[] = $tpl->field_checkbox("LDAP_SSL", "{enable_ssl} (port 636)", $array["LDAP_SSL"], false, null);
        $form[] = $tpl->field_email("WINDOWS_SERVER_ADMIN", "{administrator}", $array["WINDOWS_SERVER_ADMIN"], true);
        $form[] = $tpl->field_password("WINDOWS_SERVER_PASS", "{password}", $array["WINDOWS_SERVER_PASS"], true);
        $form[] = $tpl->field_checkbox("LockActiveDirectoryToKerberosBasic", "{LockActiveDirectoryToKerberosBasic}", $LockActiveDirectoryToKerberosBasic, false, null);
        $form[] = $tpl->field_checkbox("NtpdateAD", "url:https://wiki.articatech.com/en/active-directory/Time-synchronization-with-Active-Directory;{synchronize_time_with_ad}", $NtpdateAD, false, "{synchronize_time_with_ad_explain}");

        $html[] = $tpl->form_outside("{join_activedirectory_domain} / {kerberos_authentication}", @implode("\n", $form), "", "{apply}", Kerberos_connect(), "AsSquidAdministrator", true);
    }

    $yesno[0] = "<span class='label label'>{no}</span>";
    $yesno[1] = "<span class='label label-primary'>{yes}</span>";

    if ($LockActiveDirectoryToKerberos == 1) {
        $html[]=flat_config();

    }

    $kerberos_authentication=$tpl->_ENGINE_parse_body("{kerberos_authentication}");
    $WindowsActiveDirectoryKerberos_explain=$tpl->_ENGINE_parse_body("{WindowsActiveDirectoryKerberos_explain}");
    $Myhostname=php_uname("n");
    $WindowsActiveDirectoryKerberos_explain=str_replace("%hostname%",$Myhostname,$WindowsActiveDirectoryKerberos_explain);



    $TINY_ARRAY["TITLE"]="Active Directory &raquo;&raquo $kerberos_authentication";
    $TINY_ARRAY["ICO"]="fab fa-windows";
    $TINY_ARRAY["EXPL"]="$WindowsActiveDirectoryKerberos_explain";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons(TopButtons());
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[] = "<script>";
    $html[]=$headsjs;
    $html[] = "LoadAjax('CheckUPSettings','$page?CheckUPSettings=yes');";
    $html[] = "</script>";

    echo $tpl->_ENGINE_parse_body($html);
}
function auth_popup():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    $array = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));

    $form[] = $tpl->field_email("WINDOWS_SERVER_ADMIN_MOD", "{administrator}", $array["WINDOWS_SERVER_ADMIN"], true);
    $form[] = $tpl->field_password("WINDOWS_SERVER_PASS", "{password}", $array["WINDOWS_SERVER_PASS"], true);

    $html[] = $tpl->form_outside("", @implode("\n", $form), "", "{apply}", "LoadAjax('table-kerberos-single','$page?table=yes');", "AsSquidAdministrator", true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function auth_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $array = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    $array["WINDOWS_SERVER_ADMIN"]=$_POST["WINDOWS_SERVER_ADMIN_MOD"];
    $array["WINDOWS_SERVER_PASS"]=$_POST["WINDOWS_SERVER_PASS"];

    $NewKerb=base64_encode(serialize($array));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerbAuthInfos", $NewKerb);
    return admin_tracks("Changed kerberos credentials to {$array["WINDOWS_SERVER_ADMIN"]} with password");
}

function TopEmergency():array{
    $tpl=new template_admin();
    $page=currentPageName();
    $enable_emergency_kerb=$tpl->framework_buildjs("/proxy/emergency/activedirectory/on",
        "ad.emergency.progress",
        "ad.emergency.log",
        "kerberos-single-restart",
        "LoadAjax('table-kerberos-single','$page?table=yes');");

    $disable_emergency_kerb=$tpl->framework_buildjs("/proxy/emergency/activedirectory/off",
        "ad.emergency.progress",
        "ad.emergency.log",
        "kerberos-single-restart",
        "LoadAjax('table-kerberos-single','$page?table=yes');");

    $UseNativeKerberosAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseNativeKerberosAuth"));
    $ActiveDirectoryEmergency       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryEmergency"));
    $LockActiveDirectoryToKerberos  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    if($UseNativeKerberosAuth == 1){$LockActiveDirectoryToKerberos=1;}


    if ($ActiveDirectoryEmergency == 0) {
        if ($LockActiveDirectoryToKerberos == 0) {
            return array("$enable_emergency_kerb","fas fa-exclamation-circle","{enable_emergency_mode}");
        }
        return array("$enable_emergency_kerb","fas fa-exclamation-circle","{enable_emergency_mode}");
    }

    if ($LockActiveDirectoryToKerberos == 0) {
        return array("$disable_emergency_kerb","fas fa-exclamation-circle","{disable_emergency_mode}");
    }
    return array("$disable_emergency_kerb","fas fa-exclamation-circle","{disable_emergency_mode}");
}

function TopButtons():array{
    $tpl=new template_admin();
    $page=currentPageName();
    $users = new usersMenus();
    if (!$users->AsSystemAdministrator) {
        return array();
    }
    VERBOSE("OK --> /mktutils/klist",__LINE__);

    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/mktutils/kread"));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/mktutils/klist");


    $Disconnect = $tpl->framework_buildjs("/mktutils/disconnect",
        "msktutils.progress", "msktutils.log", "kerberos-single-restart",
        "LoadAjax('table-kerberos-single','$page?table=yes');",
        "LoadAjax('table-kerberos-single','$page?table=yes');");


    $jsreconnect = $tpl->framework_buildjs("/mktutils/reconnect",
        "msktutils.reconnect", "msktutils.log", "kerberos-single-restart",
        "LoadAjax('table-kerberos-single','$page?table=yes');",
        "LoadAjax('table-kerberos-single','$page?table=yes');");

    $jsreconfigure=$tpl->framework_buildjs("/proxy/ntlm/reconfigure",
        "onlyntlm.progress",
        "onlyntlm.progress.log",
        "kerberos-single-restart","LoadAjax('table-kerberos-single','$page?table=yes');");


    $dataKlist=@file_get_contents("/usr/share/artica-postfix/ressources/logs/klist.out");

    $CONNECTED=true;
    if(strpos($dataKlist,"not found while starting keytab")>0){
        $CONNECTED=false;
    }

        if(!$data->Status) {
            $topbuttons[] = array("Loadjs('$page?ktpass-js=yes')", ico_wizard, "{manual_install}");
        }

        $topbuttons[] = array($Disconnect, ico_unlink, "{disconnect}");

        if($CONNECTED) {
            $topbuttons[] = array($jsreconnect, ico_refresh, "{reconnect}");
            $topbuttons[] = array($jsreconfigure, ico_refresh, "{reconfigure}");
            $topbuttons[] = TopEmergency();
        }else{
            $topbuttons[] = array(Kerberos_connect(), ico_plug, "{connect}");
        }


    return $topbuttons;

}

function flat_config():string{
    $tpl=new template_admin();
    $page=currentPageName();



    $NtpdateAD = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NtpdateAD"));
    $AdNotResolvDC = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AdNotResolvDC"));
    $LockActiveDirectoryToKerberosBasic = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberosBasic"));

    $hostname_full = php_uname("n");
    $hostname = $hostname_full;
    if (strpos($hostname, ".") > 0) {
        $tre = explode(".", $hostname);
        unset($tre[0]);
        $domain = @implode(".", $tre);
    }


    $array = unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if (!is_array($array)) {
        $array = array();
    }

    $fullhosname = $array["fullhosname"];
    if ($fullhosname == ".") {
        $fullhosname = null;
    }
    if ($fullhosname == null) {
        $fullhosname = "dc01.$domain";
    }
    if (!isset($array["COMPUTER_BRANCH"])) {
        $array["COMPUTER_BRANCH"] = "CN=Computers";
    }
    if (!isset($array["WINDOWS_SERVER_TYPE"])) {
        $array["WINDOWS_SERVER_TYPE"] = "WIN_2008AES";
    }
    $severtype["WIN_2003"] = "Windows 2000/2003";
    $severtype["WIN_2008AES"] = "Windows 2008/2012";
    $severtype["WIN_2016"] = "Windows 2016";
    $severtype["WIN_2019"] = "Windows 2019";


    $tpl->table_form_field_text("{ad_full_hostname}",$fullhosname,ico_microsoft);
    $tpl->table_form_field_text("{ad_computers_branch}",$array["COMPUTER_BRANCH"],ico_directory);
    $tpl->table_form_field_text("{additional_spns}", $array["ADDITIONAL_SPNs"], ico_directory);

    $tpl->table_form_field_text("{suffix}",$array["LDAP_SUFFIX"],ico_earth);
    $tpl->table_form_field_text("{WINDOWS_SERVER_TYPE}", $severtype[$array["WINDOWS_SERVER_TYPE"]],ico_microsoft);

    $tpl->table_form_field_js("Loadjs('$page?auth-js=yes')");
    $tpl->table_form_field_text("{administrator}", $array["WINDOWS_SERVER_ADMIN"],ico_admin);
    $tpl->table_form_field_js("");
    $tpl->table_form_field_bool("{AdNotResolvDC}",$AdNotResolvDC,ico_server);


    $EnableSquidMicroHotSpot = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidMicroHotSpot"));
    if ($EnableSquidMicroHotSpot == 1) {
        $LockActiveDirectoryToKerberosBasic = 0;
    }

    $tpl->table_form_field_bool("{web_portal_authentication} (HotSpot)",$EnableSquidMicroHotSpot,ico_computer);
    $tpl->table_form_field_bool("{LockActiveDirectoryToKerberosBasic}",$LockActiveDirectoryToKerberosBasic,ico_user);
    $tpl->table_form_field_bool("{synchronize_time_with_ad}",$NtpdateAD,ico_clock);

    $tpl->table_form_field_js("Loadjs('$page?configuration-file=yes')");
    $tpl->table_form_field_text("{view_config}","{view_generated_command_lines}",ico_params);
    $html[]="<table style='width:100%;'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top;width:350px'>";
    $html[]="<div id='ticket-audit' style='min-width:350px'></div>";
    $html[]="</td>";
    $html[]="<td style='vertical-align: top;width:99%'>";
    $html[]=$tpl->table_form_compile();
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $WindowsActiveDirectoryKerberos_explain = $tpl->_ENGINE_parse_body("{WindowsActiveDirectoryKerberos_explain}");
    $Myhostname = php_uname("n");
    $WindowsActiveDirectoryKerberos_explain = str_replace("%hostname%", $Myhostname, $WindowsActiveDirectoryKerberos_explain);

    $TINY_ARRAY["TITLE"] = "Active Directory &raquo;&raquo Kerberos {connected}";
    $TINY_ARRAY["ICO"] = "fab fa-windows";
    $TINY_ARRAY["EXPL"] = $WindowsActiveDirectoryKerberos_explain;
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons(TopButtons());
    $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";

    $refresh=$tpl->RefreshInterval_js("ticket-audit",$page,"ticket-audit=yes");

    $html[]="<script>$jstiny
$refresh
</script>";
    return @implode("\n", $html);


}

function CheckUPSettings(){
    $tpl=new template_admin();
    $array = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!is_array($array)){$array=array();}
    if(!isset($array["WINDOWS_DNS_SUFFIX"])){$array["WINDOWS_DNS_SUFFIX"]="";}
    $WINDOWS_DNS_SUFFIX=$array["WINDOWS_DNS_SUFFIX"];
    $ss="style='font-weight:bold;'";
    $sock=new sockets();
    $kread=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/mktutils/kread"));
    $div_explain=array();
    if(!$kread->Status){
        echo $tpl->div_warning("{mustkinit}");
        return true;
    }else{
        $klistexplain=$tpl->_ENGINE_parse_body("{klistexplain}");
        $klistexplain=str_replace("%s","<strong>{$kread->Info->ValidStarting}</strong>",$klistexplain);
        $klistexplain=str_replace("%v","<strong>{$kread->Info->Expires}</strong>",$klistexplain);
        $klistexplain=str_replace("%d","<strong>{$kread->Info->RenewUntil}</strong>",$klistexplain);
        $div_explain[]="<div>$klistexplain</div>";
    }

    if(strlen($WINDOWS_DNS_SUFFIX)==0) {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/mktutils/kdcs"));
        if (!$json->Status) {
            echo $tpl->div_warning($json->Error);
            return true;
        }
        $hostname = $json->HostInfo->Fqdn;
        $NetbiosName= $json->HostInfo->NetBiosName;
        $domain=$json->Domain;
    }else{
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/mktutils/kdc/resolve/$WINDOWS_DNS_SUFFIX"));
        if(isset($json->Info[0])) {
            if (!$json->Status) {
                echo $tpl->div_warning($json->Error);
                return true;
            }
        }
        $hostname=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname");
        $tb=explode(".",$hostname);
        $NetbiosName=$tb[0];
        unset($tb[0]);
        $domain=implode(".",$tb);

    }
    VERBOSE("hostname_full = $hostname",__LINE__);
    VERBOSE("Domain = $domain, NetbiosName=$NetbiosName",__LINE__);

    $DCS=array();

    if(property_exists($json,"Info")) {
        if(!is_null($json->Info)) {
            foreach ($json->Info as $key => $value) {
                $Target = $value->Target;
                $DCS[] = "<code $ss>$Target</code>";
            }
        }
    }
    if(count($DCS)>0) {
        $DCTEXT = $tpl->_ENGINE_parse_body("{dcs_list_explain}");
        $DCTEXT = str_replace("%v", "<strong>" . @implode(", ", $DCS) . "</strong><br>", $DCTEXT);
    }else{
        $DCTEXT="<div class='text-danger'>{dcs_no_list} $domain</div>";
    }


    if (!isset($array["COMPUTER_BRANCH"])) {$array["COMPUTER_BRANCH"] = "CN=Computers";}
    if (!isset($array["WINDOWS_SERVER_TYPE"])) {$array["WINDOWS_SERVER_TYPE"] = "WIN_2008AES";}
    if (!isset($array["LDAP_TLS"])) {$array["LDAP_TLS"] = 0;}
    if (!isset($array["LDAP_SSL"])) {$array["LDAP_SSL"] = 0;}

    $Admin=$array["WINDOWS_SERVER_ADMIN"];
    $Password=$array["WINDOWS_SERVER_PASS"];
    $fullhosname=$array["fullhosname"];
    $COMPUTER_BRANCH=$array["COMPUTER_BRANCH"];
    $http_ad_service_conflict="";
    if(strlen($Admin)<5) {
        echo $tpl->div_warning("$DCTEXT{missing} {administrator}");
        return true;
    }
    if(strpos($Admin,"@")==0){
        $Admin="$Admin@$domain";
    }


    if(strlen($fullhosname)<4) {
        echo $tpl->div_warning("$DCTEXT{missing} {ad_full_hostname}");
        return true;
    }
    if(strlen($Password)<2) {
        echo $tpl->div_warning("$DCTEXT{missing} {password}");
        return true;
    }

    $ldap_port=389;
    if($array["LDAP_SSL"]==1){
        $ldap_port=636;
    }

    if(!isset($array["LDAP_SUFFIX"])){
        $dse=new ad_rootdse($fullhosname, $ldap_port, $Admin, $Password,$array["LDAP_SSL"],$_POST["LDAP_TLS"]);
        $array["LDAP_SUFFIX"]=$dse->RootDSE();
        $KerbAuthInfos=base64_encode(serialize($array));
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerbAuthInfos",$KerbAuthInfos);
    }

    $Suffix = $array["LDAP_SUFFIX"];
    $ldap = new ActiveDirectory(0);
    $servicePrincipalName = "HTTP/$hostname";
    $NetBiosName=$hostname;
    if(strpos($NetBiosName,".")>0){
        $tb=explode(".",$NetBiosName);
        $NetBiosName=$tb[0];
    }
    $NetBiosName=strtoupper($NetBiosName);
    $UserPrincipalName="";
    $ObjectSid="";
    $userprincipalname="";
    $userprincipalnameText="";
    $DetectedSamAccountnames=array();
    $SPNsText="";
    $page=CurrentPageName();

    VERBOSE("(&(sAMAccountName=$NetBiosName$)(objectclass=computer))",__LINE__);
    $filter = "(&(sAMAccountName=$NetBiosName$)(objectclass=computer))";
    $search = $ldap->Ldap_search($Suffix, $filter, array());
    if (strlen($ldap->ldap_last_error) > 3) {
        echo $tpl->div_error($DCTEXT . $ldap->ldap_last_error);
        return true;
    }
    if ($search["count"] == 0) {
        $ComputerText=$tpl->_ENGINE_parse_body("{no_computer_in_ad}");
        $ComputerText="<div class='text-danger'>{$ComputerText}</div>";
        $ComputerText=str_replace("%v","<strong>$NetBiosName</strong>",$ComputerText);
    }else{
        $ComputerText="<div><strong>$NetBiosName</strong>: {$search[0]["dn"]}</div>";
    }



    if(!isset($_GET["ntlmcheck"])) {
        VERBOSE("(&(servicePrincipalName=$servicePrincipalName)(objectclass=*))",__LINE__);
        $filter = "(&(servicePrincipalName=$servicePrincipalName)(objectclass=*))";
        $search = $ldap->Ldap_search($Suffix, $filter, array());
        if (strlen($ldap->ldap_last_error) > 3) {
            echo $tpl->div_error($DCTEXT . $ldap->ldap_last_error);
            return true;
        }

        if ($search["count"] == 0) {
            $SPNsText=$tpl->_ENGINE_parse_body("{kerberos_no_spn_found}");
            $SPNsText="<div class='text-danger'>{$SPNsText}</div>";
            $SPNsText=str_replace("%s","<strong>$servicePrincipalName</strong>",$SPNsText);
        }

        if ($search["count"] > 0) {
            $max=$search["count"];
            for($i=0;$i<$max;$i++) {
                $DetectedSamAccountname=$search[$i]["samaccountname"][0];
                VERBOSE("DetectedSamAccountname=$DetectedSamAccountname",__LINE__);
                if(strlen($DetectedSamAccountname)>0){
                    $DetectedSamAccountnames[]="<strong>$DetectedSamAccountname</strong>";
                }
            }
            $SPNsText="<div>{KERBSPN} <strong>($servicePrincipalName): </strong>" .@implode(", ",$DetectedSamAccountnames)."</div>";
        }
        $SPNsKVNO="";
        VERBOSE("/proxy/kerberos/keytab/kvno",__LINE__);
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/kerberos/keytab/kvno"));
        if(!$json->Status && $json->kvno>0 ){

            // /mktutils/keytab/renew
            $bt=$tpl->button_autnonome("{renew} KeyTab","Loadjs('$page?renew-keytab-js=yes')",
                ico_refresh,"AsSystemAdministrator",0,"btn-warning");
            $SPNsKVNO="<div class='text-danger'>{kvno}: <strong>$json->kvno</strong> {error}! &nbsp;$bt</div>";
        }
        if($json->Status && $json->kvno>0 ){
            VERBOSE("KVNO: $json->kvno ActiveDirectoryKVNO=$json->ActiveDirectoryKVNO",__LINE__);
            $SPNsKVNO="<div>{kvno}: <strong>$json->kvno</strong></div>";
            if($json->ActiveDirectoryKVNO>0){
                if($json->kvno<>$json->ActiveDirectoryKVNO){
                    $ActiveDirectoryKVNOERR=$tpl->_ENGINE_parse_body("{ActiveDirectoryKVNOERR}");
                    $ActiveDirectoryKVNOERR=str_replace("%kv1",$json->kvno,$ActiveDirectoryKVNOERR);
                    $ActiveDirectoryKVNOERR=str_replace("%kv2",$json->ActiveDirectoryKVNO,$ActiveDirectoryKVNOERR);
                    $SPNsKVNO="<div class='text-danger'>$ActiveDirectoryKVNOERR</div>";
                }
            }
        }



    }else{
        VERBOSE("/ntlm/hostinfos",__LINE__);
        $json=json_decode($sock->REST_API("/ntlm/hostinfos"));
#var_dump($json);
        $DetectedSamAccountname=$json->Info->SAMAccountName;
        VERBOSE("DetectedSamAccountname=$DetectedSamAccountname",__LINE__);
        $UserPrincipalName="&nbsp;-&nbsp;".$json->Info->UserPrincipalName;
        if(property_exists($json->Info,"ObjectSid")) {
            $ObjectSid = $json->ObjectSid;
        }

    }

    $COMPUTER_AND_NAME=trim(strtoupper($NetbiosName."$"));


    if(is_null($ObjectSid)){
        $ObjectSid="";
    }

    if(strlen($ObjectSid)>2){
        $filter="(&(sAMAccountName=$COMPUTER_AND_NAME)(objectclass=computer))";
        $search=$ldap->Ldap_search($Suffix,$filter,array());
        if( strlen($ldap->ldap_last_error)>3){
            echo $tpl->div_error($DCTEXT."<code>$Admin</code><br>".$ldap->ldap_last_error);
            return true;
        }
        if($search["count"]==0){
            VERBOSE("$filter search[count]=0",__LINE__);
            $no_computer_in_ad=$tpl->_ENGINE_parse_body("{no_computer_in_ad}");
            $no_computer_in_ad=str_replace("%v","<code $ss>$COMPUTER_AND_NAME</code>",$no_computer_in_ad);
            $no_computer_in_ad="<p>$no_computer_in_ad</p>";
            echo $tpl->div_error($DCTEXT.$no_computer_in_ad.$http_ad_service_conflict);
            return true;
        }
        if($GLOBALS["VERBOSE"]){
            print_r($search);
        }

        if(isset($search[0]["userprincipalname"])) {
            $userprincipalname = $search[0]["userprincipalname"][0];
        }

        $DN=$search[0]["dn"];
        if(strlen($DN)>3) {
            if (strlen($COMPUTER_BRANCH) > 3) {
                if (stripos($DN, $COMPUTER_BRANCH) == 0) {
                    $userprincipalnameText = "{userprincipalname}: <strong>$userprincipalname</strong>,
                    <br>{error} $DN <strong>$COMPUTER_BRANCH {missing} </strong>";
                    echo $tpl->_ENGINE_parse_body($tpl->div_error($DCTEXT . $http_ad_service_conflict . $userprincipalnameText));
                    return true;
                }
            }

            if (strlen($userprincipalname) < 3) {
                $userprincipalnameText = "{userprincipalname}: <strong>{missing}</strong>,{DN}: <strong>$DN</strong>";
                echo $tpl->_ENGINE_parse_body($tpl->div_error($DCTEXT . $http_ad_service_conflict . $userprincipalnameText));
                return true;
            }

        }
        if(strlen($userprincipalname)>3){
            $userprincipalnameText="{userprincipalname}: <strong>$userprincipalname</strong>, {DN}: <strong>$DN</strong>";
        }

    }

    $div_explain[]=$DCTEXT;
    if(strlen($ComputerText)>2){
        $div_explain[]=$ComputerText;
    }
    $div_explain[]=$http_ad_service_conflict;
    $div_explain[]=$userprincipalnameText;
    $div_explain[]=$SPNsText;
    $div_explain[]=$SPNsKVNO;


    echo $tpl->_ENGINE_parse_body($tpl->div_explain(@implode(" ",$div_explain)));

    return true;

}
function checkPort($host, $port, $timeout = 3):bool {
    $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if (is_resource($connection)) {fclose($connection); return true; } else { return false; }
}
function Save(){
    $tpl=new template_admin();
    ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
    $tpl->CLEAN_POST();
    $IP=new IP();
    $_POST["ADDITIONAL_SPNs"]=str_replace(";",",",$_POST["ADDITIONAL_SPNs"]);
    $_POST["ADDITIONAL_SPNs"]=str_replace(" ",",",$_POST["ADDITIONAL_SPNs"]);
    $_POST["ADDITIONAL_SPNs"]=str_replace(" ","",$_POST["ADDITIONAL_SPNs"]);
    $KerbAuthInfos=base64_encode(serialize($_POST));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerbAuthInfos",$KerbAuthInfos);

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NtpdateAD",$_POST["NtpdateAD"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LockActiveDirectoryToKerberosBasic",$_POST["LockActiveDirectoryToKerberosBasic"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");$GLOBALS["CLASS_SOCKETS"]->SET_INFO("AdNotResolvDC",$_POST["AdNotResolvDC"]);


    $kerberosActiveDirectoryHost=$_POST["fullhosname"];
    $ipaddr=$GLOBALS["CLASS_SOCKETS"]->gethostbyname($kerberosActiveDirectoryHost);
    if(!$IP->isValid($ipaddr)){
        $err=$tpl->_ENGINE_parse_body("{ad_full_hostname_resolve_issue}");
        $err=str_replace("%s",$kerberosActiveDirectoryHost,$err);
        echo "jserror:$err";
        return false;
    }

    $_POST["ADNETIPADDR"]=$ipaddr;
    $_POST["LDAP_SERVER"]=$_POST["fullhosname"];
    $ldap_port=389;
    $ldapserver=$_POST["fullhosname"];
    $KerberosUsername=$_POST["WINDOWS_SERVER_ADMIN"];
    $KerberosPassword=$_POST["WINDOWS_SERVER_PASS"];
    $ldap_prefix="ldap://";
    if($_POST["LDAP_SSL"]==1){$_POST["LDAP_TLS"]=0;$ldap_port=636;$ldap_prefix="ldaps://";}

    $TR=explode("@",$KerberosUsername);
    $_POST["WINDOWS_DNS_SUFFIX"]=$TR[1];
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WINDOWS_SERVER_ADMIN",$KerberosUsername);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WINDOWS_DNS_SUFFIX",$ldap_prefix);

    if(!checkPort($ipaddr,$ldap_port)){
        echo "jserror:$ipaddr:$ldap_port access denied (DC Firewall enabled ?)";
        return false;
    }


    $ldap_connection=@ldap_connect($ldap_prefix.$ldapserver);
    if(!$ldap_connection){
        $DIAG[]="{Connection_Failed_to_connect_to_DC} $ldap_prefix$ldapserver:$ldap_port";
        if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {$DIAG[]="$extended_error";}
        echo "jserror:".$tpl->_ENGINE_parse_body(@implode("<br>", $DIAG));
        return false;
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
            echo "jserror:".$tpl->_ENGINE_parse_body(@implode("<br>", $DIAG));
            return false;
        }
    }

    $bind=ldap_bind($ldap_connection, $KerberosUsername,$KerberosPassword);
    if(!$bind){
        $error_code=ldap_errno($ldap_connection);
        $DIAG[]="{login_Failed_to_connect_to_DC} Error $error_code {with} $KerberosUsername";

        $DIAG[]=ldap_err2str($error_code);
        if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {$DIAG[]="$extended_error";}
        echo "jserror:".$tpl->_ENGINE_parse_body(@implode("<br>", $DIAG));
        return false;
    }


    $dse=new ad_rootdse($_POST["ADNETIPADDR"], $ldap_port, $KerberosUsername, $KerberosPassword,$_POST["LDAP_SSL"],$_POST["LDAP_TLS"]);
    $RootDSE=$dse->RootDSE();
    if(!$dse->ok){
        echo "jserror:".$tpl->_ENGINE_parse_body($dse->mysql_error);
        return false;
    }

    if($RootDSE<>null){
        $_POST["LDAP_SUFFIX"]=$RootDSE;
    }
    $KerbAuthInfos=base64_encode(serialize($_POST));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerbAuthInfos",$KerbAuthInfos);
    return true;
}
function file_uploaded():bool{
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $file=$_GET["file-uploaded"];

    $sock=new sockets();
    $data=json_decode($sock->REST_API("/kerberos/keytab/simple/$file"));

    if(!$data->Status){
        return $tpl->js_error($data->Error);

    }
    echo  "dialogInstance1.close();LoadAjax('table-kerberos-single','$page?table=yes');";
    return true;
}
function ticket_audit_js():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    return $tpl->js_dialog2("{kerberos_ticket}", "$page?ticket-audit-popup=yes");
}
function ticket_audit():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/kerberos/keytab/check"));

    $btn["ico"]=ico_loupe;
    $btn["name"]="{information}";
    $btn["js"]="Loadjs('$page?ticket-audit-js=yes')";

    if(!$json->Status){

        if(!$json->KeyTabExists){
            $html[]=$tpl->_ENGINE_parse_body($tpl->widget_h("grey",ico_timeout,"{not_connected}","{not_connected}",$btn));
            echo $tpl->_engine_parse_body($html);
            return false;
        }
        $strlen=strlen($json->Error);
        if($strlen>1500){
            $json->Error="{kerberos_ticket} {error}";
        }
        $html[]=$tpl->_ENGINE_parse_body($tpl->widget_h("red","fa-thumbs-down","{error}",$json->Error,$btn));

        $ActiveDirectoryReportInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryReportInterface");
        if(strlen($ActiveDirectoryReportInterface)>20){
            $page=CurrentPageName();
            $bt=$tpl->button_autnonome("{see_events}","Loadjs('$page?ActiveDirectoryReportInterface-js=yes')",
                ico_bug,"AsSystemAdministrator",350,"btn-info");
            $html[]="<div style='margin-top:20px' class='center'>$bt</div>";
        }

        $html[]="<script>LoadAjaxSilent('CheckUPSettings','$page?CheckUPSettings=yes');</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    $xtime=strtotime($json->RenewUntil);
    $date=distanceOfTimeInWords(time(),$xtime);
    $html[]=$tpl->_ENGINE_parse_body($tpl->widget_h("minwidth:350:green",ico_certificate,"<span style='font-size:20px'>$date</span>","{renew}",$btn));

    $ActiveDirectoryReportInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryReportInterface");
    if(strlen($ActiveDirectoryReportInterface)>20){
        $page=CurrentPageName();
        $bt=$tpl->button_autnonome("{see_events}","Loadjs('$page?ActiveDirectoryReportInterface-js=yes')",
            ico_bug,"AsSystemAdministrator",350,"btn-info");
        $html[]="<div style='margin-top:20px' class='center'>$bt</div>";
    }


    $html[]="<script>LoadAjaxSilent('CheckUPSettings','$page?CheckUPSettings=yes');</script>";
    echo $tpl->_ENGINE_parse_body($html);

    return true;
}
function ticket_audit_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $kvno=0;
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/kerberos/keytab/kvno"));
    if(!$json->Status){
        if($json->kvno>0){
            $kvno=$json->kvno;
        }
        if(strpos($json->Error,"\n")>0){
            $json->Error=str_replace("\n","<br>\n",$json->Error);
        }

        $html[]=$tpl->div_error($json->Error);
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/kerberos/keytab/check"));


    if(!$json->Status){
        if(strpos($json->Error,"\n")>0){
            $json->Error=str_replace("\n","<br>\n",$json->Error);
        }

        $html[]=$tpl->div_error($json->Error);
    }
    if($json->kvno>0){
        $kvno=$json->kvno;
    }
    if(!is_null($json->SessionKey)) {
        if (strlen($json->SessionKey) > 0) {
            $tpl->table_form_field_text("{SessionKey}", $json->SessionKey, ico_key);
        }
    }
    $tpl->table_form_field_text("{userprincipalname}",$json->Principal,ico_admin);
    $tpl->table_form_field_text("{kvno}",$kvno,ico_params);
    $tpl->table_form_field_text("{ValidStarting}",$json->ValidStarting,ico_clock);
    $tpl->table_form_field_text("{expires}",$json->Expires,ico_clock);
    $tpl->table_form_field_text("{RenewUntil}",$json->RenewUntil,ico_timeout);
    $html[]=$tpl->table_form_compile();
    $html[]="<div style='margin:15px;border:1px solid #cccccc;padding:10px'>";
    if(strlen($json->klistOut)>3){
        $html[]="<div style='font-size:12px;color:black'><hr>$json->klistOut<hr></div>";
    }
        foreach ($json->KvnoReport as $line) {
            $html[]="<div style='font-size:12px;color:black'>$line</div>";
        }
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body(@implode("",$html));

return true;
}