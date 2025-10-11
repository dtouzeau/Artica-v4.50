<?php
include_once (dirname(__FILE__)."/class.squid.manager.inc");
class icontop{
    public $current_filedesc=0;
    public $Current=0;
    public $Available=0;
    public $from_cache=false;
    public $filedesc_prc=0;
    public $current_in_conf;


    private function ShieldConnector():string{
        $SQUIDACLsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDACLsEnabled"));
        $GoShieldServerHide=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldServerHide"));
        if ($SQUIDACLsEnabled==0) {return "";}
        $MacToUidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MacToUidUrgency"));
        if($MacToUidUrgency==1){return "{proxy_in_MacToUid_emergency_mode}||js:Loadjs('fw.proxy.emergency.MacToUid.php');";}
        $GoShieldConnectorDisableACL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldConnectorDisableACL"));
        if ($GoShieldConnectorDisableACL==1) {
            return "";
        }
        if($GoShieldServerHide==1){
            return "";
        }

        $Go_Shield_Connector_Enable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Enable"));
        if ($Go_Shield_Connector_Enable==1) {
            return "";
        }

        return "{filtering_connector_disabled}||js:Loadjs('fw.go.shield.connector.php?link-connector=yes');";

    }

    function MsftncsiReport():string{

        $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
        $DisableMsftncsi=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMsftncsi"));
        if($SQUIDEnable==0) {return "";}
        if($DisableMsftncsi==1) {return "";}
        $MsftncsiReport=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MsftncsiReport");
        if(strlen($MsftncsiReport)<5){
            return "";
        }
        $json=json_decode($MsftncsiReport);


        if(!property_exists($json,"success")) {
            return "";
        }
        if($json->success){
            return "";
        }

        return  "{error_internet_proxy}||||DANGER||js:Loadjs('fw.proxy.status.php?MsftncsiReport-js=yes');||";

    }

    public function NOTIFS_SQUID($UPDATES_ARRAY=array(),$nooutjs=false):array{
    if($this->from_cache==true){
        $NOTIFS_SQUID=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NOTIFS_SQUID"));
        $this->filedesc_prc=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("squid_current_filedesc");
        if(!is_array($NOTIFS_SQUID)){return array();}
        return $NOTIFS_SQUID;
    }
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $ERR        = array();
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
	if($SQUIDEnable==0){return array();}
    $SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
    $SquidUFDBUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUFDBUrgency"));
    $UfdbGuardDisabledRedirectors=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardDisabledRedirectors"));
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    $DynamicACLUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DynamicACLUrgency"));
    $SquidSSLUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSSLUrgency"));
    $SizeQuotasCheckerEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SizeQuotasCheckerEmergency"));
    $LogsWarninStop=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsWarninStop"));
    $StoreIDUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StoreIDUrgency"));
    $ActiveDirectoryEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryEmergency"));
    $SquidUrgencyCaches=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgencyCaches"));
    $SquidMimeEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMimeEmergency"));
    $BasicAuthenticatorEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BasicAuthenticatorEmergency"));
    $DisableAnyCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableAnyCache"));
    $SquidHotSpotUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHotSpotUrgency"));
    $SquidDebug5=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDebug5"));
    $realsquidversion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion");
    $MalformedSquidPIDfileOther=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MalformedSquidPIDfileOther");

    preg_match("#^([0-9])#",$realsquidversion,$re);
    $realFver=$re[1];
    if(!is_array($UPDATES_ARRAY)){$UPDATES_ARRAY=array();}
    $IsActiveDirectory=$this->IsActiveDirectory();
    $ShieldConnector=$this->ShieldConnector();
    if(strlen($ShieldConnector)>5){
        $ERR[] =$ShieldConnector;
    }
    $ERR[]=$this->MsftncsiReport();
    if($MalformedSquidPIDfileOther==1){
        $ERR[] = "{MalformedSquidPIDfileOther}||{suggest_to_restart_proxy_service}||WARN||js:Loadjs('fw.proxy.apply.ports.php');||";

    }
    $EnableeCapClamav=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("eCAPClamavEmergency"));
    $eCAPClamavEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("eCAPClamavEmergency"));




    $SQUID_ACLS_REMOVED_ITEMS_C=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_ACLS_REMOVED_ITEMS");
    if(strlen($SQUID_ACLS_REMOVED_ITEMS_C)>2) {
        $SQUID_ACLS_REMOVED_ITEMS=unserialize($SQUID_ACLS_REMOVED_ITEMS_C);
        if (count($SQUID_ACLS_REMOVED_ITEMS) > 0) {
            $SQUID_ACLS_REMOVED_TITLE = $tpl->_ENGINE_parse_body("{SQUID_ACLS_REMOVED_ITEMS}");
            $SQUID_ACLS_REMOVED_TITLE = str_replace("%s", count($SQUID_ACLS_REMOVED_ITEMS), $SQUID_ACLS_REMOVED_TITLE);
            $ERR[] = "$SQUID_ACLS_REMOVED_TITLE||{SQUID_ACLS_REMOVED_ITEMS_TEXT}||WARN||js:Loadjs('fw.proxy.acls.bugs.php');||";
        }
    }


    $firstVer=0;
    if(count($UPDATES_ARRAY)>0) {
        $HideNewSquidVer = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideNewSquidVer"));
        if ($HideNewSquidVer == 0) {
            $AVAILABLE_SQUID = $tpl->SQUID_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY);
            if ($AVAILABLE_SQUID > 0) {
                preg_match("#^([0-9])#",$AVAILABLE_SQUID,$re);
                $firstVer=$re[1];
                if($realFver==$firstVer) {
                    VERBOSE("AVAILABLE_SQUID = $AVAILABLE_SQUID ($firstVer)", __LINE__);
                    $NewVer = $UPDATES_ARRAY["APP_SQUID"][$AVAILABLE_SQUID]["VERSION"];
                    $ERR[] = "{SQUID_NEWVERSION} $NewVer||{SQUID_NEWVERSION_TEXT}||WARN||js:Loadjs('fw.system.upgrade-software.php?product=APP_SQUID');||js:Loadjs('$page?SetToken=HideNewSquidVer');";
                }
            }
        }
    }

    if(method_exists($tpl,"SQUID6_LATEST_AVAILABLE_VERSION")) {
        if (isset($UPDATES_ARRAY["APP_SQUID6"])) {
            $HideNewSquidVer6Upg = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideNewSquidVer6Upg"));
            if ($HideNewSquidVer6Upg == 0) {
                if ($realFver < 6) {
                    $AVAILABLE_SQUID = $tpl->SQUID6_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY);
                    if ($AVAILABLE_SQUID > 0) {
                        $NewVer = $UPDATES_ARRAY["APP_SQUID6"][$AVAILABLE_SQUID]["VERSION"];
                        $ERR[] = "{SQUID_NEWVERSION} $NewVer||{SQUID_NEWVERSION_TEXT}||WARN||js:Loadjs('fw.system.upgrade-software.php?product=APP_SQUID6');||js:Loadjs('$page?SetToken=HideNewSquidVer6Upg');";
                    }
                }
            }
            $HideNewSquidVer6 = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideNewSquidVer6"));
            if ($HideNewSquidVer6 == 0) {
                if ($realFver == 6) {
                    $AVAILABLE_SQUID = $tpl->SQUID6_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY);
                    if ($AVAILABLE_SQUID > 0) {
                        $NewVer = $UPDATES_ARRAY["APP_SQUID6"][$AVAILABLE_SQUID]["VERSION"];
                        $ERR[] = "{SQUID_NEWVERSION} $NewVer||{SQUID_NEWVERSION_TEXT}||WARN||js:Loadjs('fw.system.upgrade-software.php?product=APP_SQUID6');||js:Loadjs('$page?SetToken=HideNewSquidVer6');";
                    }
                }
            }
        }
    }



    $SQUID_ACLS_BUGS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_ACLS_BUGS"));

    $CVE20242056=$this->CVE20242056();
    if(strlen($CVE20242056)>3){
        $ERR[]=$CVE20242056;
    }

    if($SQUID_ACLS_BUGS>0){
        $error=$tpl->_ENGINE_parse_body("{acls_check_errors}");
        $error=str_replace("%s",$SQUID_ACLS_BUGS,$error);
        $ERR[]="$error||js:Loadjs('fw.proxy.acls.bugs.php');";
    }

    if($SquidDebug5==1){
        $ERR[] = "{proxy_is_debug_mode}||||DANGER||";
    }
    $SquidConfReadOnly=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidConfReadOnly"));
    if($SquidConfReadOnly==1){
        $ERR[]="{proxy_in_readonly}||js:Loadjs('fw.proxy.actions.php');";
    }

    if($SquidUrgency==1){ $ERR[]="{proxy_in_emergency_mode}||js:Loadjs('fw.proxy.emergency.remove.php');";}
    if($SquidUrgencyCaches==1){$ERR[]="{your_proxy_is_in_caches_emergency}||js:Loadjs('fw.proxy.emergency.remove.php');";}
    if($LogsWarninStop==1){$ERR[]="{squid_logs_urgency_section}||js:Loadjs('fw.proxy.emergency.remove.php');";}

        if($EnableeCapClamav==1){
            if($eCAPClamavEmergency==1){
                //eCAPClamav_emergency_mode_explain
                $ERR[]="{eCAPClamav_emergency_mode}||js:Loadjs('fw.proxy.emergency.remove.php');";
            }
        }



        $KSRNRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNRemote"));
    if($KSRNRemote==1){
        $KSRNRemoteAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNRemoteAddr"));
        $KSRNRemotePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNRemotePort"));
        $fp = fsockopen($KSRNRemoteAddr,$KSRNRemotePort,$errno,$errstr,1);
        if(!$fp){
             $ERR[]="{theshields_connection_error}||$errstr";
        }
    }

    if($BasicAuthenticatorEmergency==1){$ERR[]="{authentication_emergency_mode}||js:Loadjs('fw.proxy.emergency.remove.php');";}
    if($SquidSSLUrgency==1){$ERR[]="{proxy_in_ssl_emergency_mode}||js:Loadjs('fw.proxy.ssl-emergency.remove.php');";}
    if($SquidUFDBUrgency==1){$ERR[]="{proxy_in_webfiltering_emergency_mode}||js:Loadjs('fw.ufdb.emergency.remove.php');";}
    if($UfdbGuardDisabledRedirectors==1){ $ERR[]="{proxy_in_webfiltering_redirectors_mode}"; }

    if($DynamicACLUrgency==1){$ERR[]="{proxy_in_dynamic_emergency_mode}||js:Loadjs('fw.proxy.emergency.remove.php');";}
    if($StoreIDUrgency==1){$ERR[]="{hypercache_in_emergency_mode}||js:Loadjs('fw.proxy.emergency.StoreID.php');";}
    if($SizeQuotasCheckerEmergency==1){$ERR[]="{proxy_in_quota_emergency_mode}||js:Loadjs('fw.proxy.emergency.remove.php');";}
    if($SquidMimeEmergency==1){$ERR[]="{your_proxy_is_in_mime_emergency}||js:Loadjs('fw.proxy.emergency.remove.php');";}
    if($SquidHotSpotUrgency==1){$ERR[]="{your_proxy_is_in_hotspot_emergency}||js:Loadjs('fw.proxy.emergency.remove.php');";}
    if($DisableAnyCache==1){$ERR[]="{all_cache_method_are_globally_disabled}||js:Loadjs('fw.proxy.disable.php')";}

    if($IsActiveDirectory) {

        if ($ActiveDirectoryEmergency == 1) {
            $LockActiveDirectoryToKerberos = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
            $HaClusterClient = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
            if ($HaClusterClient == 1) {
                $LockActiveDirectoryToKerberos = 1;
            }
            if ($LockActiveDirectoryToKerberos == 0) {
                $isAdNonNTLM = $this->isAdNonNTLM();
                if ($isAdNonNTLM) {
                    $ERR[] = "{activedirectory_emergency_mode}||js:Loadjs('fw.proxy.emergency.ntlm.remove.php?line=" . __LINE__ . "');";
                } else {
                    $ERR[] = "{activedirectory_emergency_mode}||js:Loadjs('fw.proxy.emergency.remove.php?line=" . __LINE__ . "');";
                }
            } else {
                $ERR[] = "{activedirectory_emergency_mode}||js:Loadjs('fw.proxy.emergency.kerberos.remove.php?line=" . __LINE__ . "');";
            }

        } else {
            $isAdNonNTLM = $this->isAdNonNTLM();
            if ($isAdNonNTLM) {
                $ERR[] = "{activedirectory_emergency_mode}||js:Loadjs('fw.proxy.emergency.ntlm.remove.php?line=" . __LINE__ . "');";
            }
        }

        if ($this->proxy_is_not_configured_ad()) {
            $ERR[] = "{proxy_is_not_configured_ad}||js:Loadjs('fw.proxy.reconfigure.activedirectory.php');";
        }
        $KERBEROS_AUTH_ERR = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("KERBEROS_AUTH_ERR");
        VERBOSE("KERBEROS_AUTH_ERR ==[$KERBEROS_AUTH_ERR]", __LINE__);
        if (strlen($KERBEROS_AUTH_ERR) > 0) {
            $js = "document.location.href='/ad-state';";
            $KERBEROS_AUTH_ERR = str_replace("'", "`", $KERBEROS_AUTH_ERR);
            $ERR[] = "{active_directory_connection_issue}||$KERBEROS_AUTH_ERR||DANGER||js:$js";
        }


        $json1 = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/kerberos/resolvad"));
        if (property_exists($json1, "Status")) {
            if (!$json1->Status) {
                if (!property_exists($json1, "JsonFailed")) {
                    $sbr = explode("|", $json1->Error);
                    $ERR[] = "$sbr[0]||$sbr[1]||";
                }
            }
        }
    }


    VERBOSE("proxy_is_not_configured_ldap()",__LINE__);
    if($this->proxy_is_not_configured_ldap()){
        VERBOSE("proxy_is_not_configured_ldap() RETURN TRUE SO NOTIF IT",__LINE__);
        $ERR[]="{proxy_is_not_configured_ldap}||js:Loadjs('fw.proxy.members.ldapfix.php');";
    }


    $users=new usersMenus();
    if($users->AsProxyMonitor){
        $RESTART_INSTALL_SQUID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RESTART_INSTALL_SQUID"));
        if($RESTART_INSTALL_SQUID==1) {
            $ERR[] = "{NEED_RESTART_SQUID}||js:Loadjs('fw.proxy.need.restart.php');";
        }
    }
    $ERR=$this->proxy_checks_ports($ERR);

    if(count($ERR)>0){
        $text=$ERR[0];
        if(strpos($text,"||WARN||")==0) {
            $ss = base64_encode($ERR[0]);
            if(!$nooutjs) {
                echo "<script>LoadAjaxSilent('widget-hostname','fw.index.php?widget-hostname=yes&ERRJS=$ss');</script>";
            }
        }
    }else{
        if(!$nooutjs){echo "<script>LoadAjaxSilent('widget-hostname','fw.index.php?widget-hostname=yes');</script>";}
    }


    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NOTIFS_SQUID",serialize($ERR));
    $ERR=$this->check_ufdbguard($ERR);



    return $ERR;

}

private function CVE20242056():string{
    VERBOSE("CVE20242056",__LINE__);
    $sock=new sockets();
    $data=$sock->REST_API("/CVE20242056");
    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        VERBOSE("json_last_error report error",__LINE__);
        return "";
    }
    if (!$json->Status) {
        VERBOSE("Status FALSE",__LINE__);
        return "";
    }
    if(!$json->CveAffected) {
        $js = "s_PopUpFull('https://wiki.articatech.com/maintenance/security/CVE-2024-2056',1024,768,'Wiki');";
        return "{security}: CVE: {APP_PROXY}||CVE-2024-2056||DANGER||js:$js;";
    }
    return "";
}

private function isAdNonNTLM():bool{
    $f=explode("\n",@file_get_contents("/etc/squid3/non_ntlm.acl"));
    foreach ($f as $line){
        $line=trim($line);
        if(preg_match("#^http_access allow all#",$line)){
            return true;
        }
    }
    return false;

}



private function Tips_paragraph($ico,$js,$text,$hidejs=null,$id=null):string{
    $icon_hide=null;
    if($hidejs<>null){
        $icon_hide="<small class='label label-warning' $hidejs>{hide}</small>&nbsp;&nbsp;";
    }

$f[]="<li id='$id' style='margin-top: 10px'>";
$f[]="<table style='width:100%'>";
$f[]="<tr>";
$f[]="<td width=1% nowrap style='vertical-align:top;padding:0' $js>
    <div style='font-size:28px;padding-left:3px;padding-top:0;margin-top:-5px;vertical-align: top;'>$ico</div></td>";
$f[]="<td width=99% style='vertical-align:top'><div style='padding-left:8px;margin-bottom: 8px'><span class=\"m-l-xs\">$text</span></div></td>";
$f[]="</tr>";
$f[]="<tr><td>&nbsp;</td><td style='text-align:right;border-top:1px solid #CCCCCC;padding-top: 3px' >$icon_hide<small class='label label-primary' $js>{gotit}</small></td></tr>";
$f[]="</table>";
$f[]="</li>";
return @implode("\n",$f);

}

private function proxy_checks_ports($ERR):array{

    $f=explode("\n",@file_get_contents("/etc/squid3/listen_ports.conf"));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#http_port\s+(.+?):([0-9]+)\s+#",$line,$re)){continue;}
        $ipaddr=$re[1];
        $port=$re[2];
        if($ipaddr=="0.0.0.0"){$ipaddr="127.0.0.1";}
        $fp=@fsockopen($ipaddr, $port, $errno, $errstr, 1);
        if(!$fp){
            $jsdown="window.location.href='/proxy-ports'";
            $ERR[]= "{local_proxy_port}:{failed}||$ipaddr:$port<br>$errstr||||js:$jsdown;";
        }

    }
    return $ERR;
}

public function dnsdist(){
    $ERR=array();
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    if($EnableDNSDist==0){return array();}
    $APP_DNSDIST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSDIST_VERSION");
    preg_match("#^([0-9]+)\.([0-9]+)#",$APP_DNSDIST_VERSION,$re);
    $major=intval($re[1]);
    if($major==0){return array();}
    $minor=$re[2];
    if($major==1){
        if($minor<6){
            $help_url="https://wiki.articatech.com/dns/load-balancer/upgrading";
            $js_help="s_PopUpFull('$help_url','1024','900');";
            $tpl=new template_admin();
            $upgrade_required_software=$tpl->_ENGINE_parse_body("{upgrade_required_software}");
            $upgrade_required_software=str_replace("%s",$APP_DNSDIST_VERSION,$upgrade_required_software);
            $ERR[] = "{APP_DNSDIST}: {upgrade_required}||$upgrade_required_software||WARN||js:$js_help";
       }
    }
       return $ERR;
}

private function check_ufdbguard($ERR):array{
        if(!is_array($ERR)){$ERR=array();}
        $EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
        if($EnableUfdbGuard==0){return $ERR;}
        $include=false;
        $f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
        foreach ($f as $line){
            $line=trim($line);
            if($line==null){continue;}
            if(preg_match("#^include.*?ufdbgclient\.conf#",$line)){
                $include=true;
                break;
            }
        }
        if(!$include){
            $ERR[] = "{error_webfiltering_disconnected}||{error_webfiltering_disconnected_explain}||DANGER||";
            return $ERR;
        }


        $f=explode("\n",@file_get_contents("/etc/squid3/ufdbgclient.conf"));
        foreach ($f as $line){
            $line=trim($line);
            if($line==null){continue;}
            if(preg_match("#^url_rewrite_program\s+\/#",$line)){
                return $ERR;
            }
        }
        return $ERR;
}

private function proxy_is_not_configured_ldap():bool{
        $sock=new sockets();
        $SquidStandardLDAPAuth=intval($sock->GET_INFO("SquidStandardLDAPAuth"));
        $EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
        $LDAPGroupKeepPHP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPGroupKeepPHP"));
        if($EnableOpenLDAP==0){$SquidStandardLDAPAuth=0;}
        if($SquidStandardLDAPAuth==0){return false;}
        if($LDAPGroupKeepPHP==1){return false;}

        $f=explode("\n", @file_get_contents("/etc/squid3/authenticate.conf"));
        foreach ($f as $line){
            if (preg_match("#^auth_param basic program.*?basic_ldap_auth#",$line)) {
                return false;
            }
        }

        VERBOSE("auth_param basic program basic_ldap_auth not found!",__LINE__);
        return true;

    }

    public function IsActiveDirectory(){
        $UseNativeKerberosAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseNativeKerberosAuth"));
        $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
        $WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos")
        );
        $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
        $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
        if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$EnableActiveDirectoryFeature=0;}
        if($LockActiveDirectoryToKerberos==1){$UseNativeKerberosAuth=1;$EnableActiveDirectoryFeature=1;}
        if($EnableActiveDirectoryFeature==0){return false;}
        if($HaClusterClient==1){
            $HaClusterDoNotUseAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterDoNotUseAD"));
            $kerberosActiveDirectoryHost=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kerberosActiveDirectoryHost"));
            VERBOSE("HACLUSTER CLIENT = YES HaClusterDoNotUseAD=$HaClusterDoNotUseAD",__LINE__);
            if($HaClusterDoNotUseAD==1){return false;}
            VERBOSE("HACLUSTER CLIENT = kerberosActiveDirectoryHost=[$kerberosActiveDirectoryHost]",__LINE__);
            if(strlen($kerberosActiveDirectoryHost)>3){
                return false;
            }
            if($HaClusterDoNotUseAD==0) {
                $UseNativeKerberosAuth = 1;
            }
        }
        if($UseNativeKerberosAuth==1){return true;}
        if($WindowsActiveDirectoryKerberos==1){return true;}
        if($EnableKerbAuth==1){return true;}
        return false;
    }

    private function acls_adgroup():bool{
        $superline=null;
        $children_max=0;
        $f=explode("\n",@file_get_contents("/etc/squid3/acls_adgroup.conf"));
        foreach ($f as $line){

            if(preg_match("#external_acl_type ads_group(.*?)\/(go-squid-auth|external_acl_squid_ldap|external_acls_ad_agent)#",$line,$re)){
                $superline=$re[1];
            }
        }
        if($superline==null){
            return true;
        }
        if(preg_match("#children-max=([0-9]+)#",$superline,$re)){
            $children_max=$re[1];
        }

        if($children_max<15){
            return true;
        }
        return false;
}

    private function proxy_is_not_configured_adldap(){
        $f=explode("\n", @file_get_contents("/etc/squid3/authenticate.conf"));

        foreach ($f as $line){
            $line = trim($line);
            if ($line == null) {continue;}
            if(preg_match("#^\##",$line)){continue;}
            if (preg_match("#^auth_param basic program.*?artica-proxy-auth#",$line)) {
                VERBOSE("OK FOUND, -$line-");
                return  $this->acls_adgroup();
            }
        }
        return true;
    }


    public function proxy_is_not_configured_ad():bool{
        if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){return false;}
        $ActiveDirectoryEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryEmergency"));
        $EnableAdLDAPAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAdLDAPAuth"));
        VERBOSE("EnableAdLDAPAuth == $EnableAdLDAPAuth",__LINE__);
        if($EnableAdLDAPAuth==1){
            return $this->proxy_is_not_configured_adldap();
        }
        if($ActiveDirectoryEmergency==1){return false;}
        $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
        $IS_AUTHENTICATED=false;
        $UseNativeKerberosAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseNativeKerberosAuth"));
        $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));

        if($HaClusterClient==1){
            $HaClusterADContent=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD");
            $HaClusterAD=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($HaClusterADContent);
            if(!isset($HaClusterAD["kerberosActiveDirectoryHost"])){$HaClusterAD["kerberosActiveDirectoryHost"]=null;}
            if($HaClusterAD["kerberosActiveDirectoryHost"]<>null) {
                $LockActiveDirectoryToKerberos = 1;
            }
        }

        if($EnableKerbAuth==1){
            $IS_AUTHENTICATED=true;
        }
        if($UseNativeKerberosAuth==1){
            $IS_AUTHENTICATED=true;
        }
        if($LockActiveDirectoryToKerberos==1){
            $IS_AUTHENTICATED=true;
        }

        if(!$IS_AUTHENTICATED){return false;}

        $f=explode("\n", @file_get_contents("/etc/squid3/authenticate.conf"));

        foreach ($f as $line){
            $line = trim($line);
            if ($line == null) {continue;}
            if(preg_match("#^\##",$line)){continue;}
            if (preg_match("#^auth_param (negotiate|ntlm) program#",$line)) {
                VERBOSE("OK FOUND, -->ACL_AUTHENTICATED()");
                return  $this->ACL_AUTHENTICATED();
            }
        }

        VERBOSE("proxy_is_not_configured_ad: auth_param (negotiate|ntlm) program not found in /etc/squid3/authenticate.conf",__LINE__);
        return true;


    }

    private function ACL_AUTHENTICATED():bool{
        if(!is_file("/etc/squid3/authenticate.conf")){
            VERBOSE("HTTP_ACCESS_AUTHENTICATED  -->/etc/squid3/authenticate.conf not found");
            return false;
        }
        $f=explode("\n",@file_get_contents("/etc/squid3/authenticate.conf"));
        foreach ($f as $zline) {
            $zline = trim($zline);
            if ($zline == null) {continue;}
            if(preg_match("#^\##",$zline)){continue;}
            if (preg_match("#^acl AUTHENTICATED#", $zline)) {
                VERBOSE("OK FOUND, -->HTTP_ACCESS_AUTHENTICATED()");
                return $this->HTTP_ACCESS_AUTHENTICATED();
            }
        }
        VERBOSE("proxy_is_not_configured_ad: ^acl AUTHENTICATED /etc/squid3/authenticate.conf",__LINE__);
        return true;
    }

    private function HTTP_ACCESS_AUTHENTICATED():bool{
        $EnableSquidMicroHotSpot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidMicroHotSpot"));
        if($EnableSquidMicroHotSpot==1){return false;}
        if(!is_file("/etc/squid3/http_access_final.conf")){
            VERBOSE("HTTP_ACCESS_AUTHENTICATED  -->/etc/squid3/http_access_final.conf [BAD]: not found");
            return true;}
        $f=explode("\n", @file_get_contents("/etc/squid3/http_access_final.conf"));
        foreach ($f as $line){
            $line = trim($line);
            if ($line == null) {continue;}
            if(preg_match("#^\##",$line)){continue;}
            if (preg_match("#^http_access deny.*?AUTHENTICATED#",$line)) {
                VERBOSE("proxy_is_not_configured_ad: HTTP_ACCESS_AUTHENTICATED OK IN /etc/squid3/http_access_final.conf -->acls_adgroup()",__LINE__);
                return $this->acls_adgroup();
            }
        }
        VERBOSE("proxy_is_not_configured_ad: http_access deny.*?AUTHENTICATED /etc/squid3/http_access_final.conf",__LINE__);
        return true;
    }

    public function filedescriptors_checking($nocache=false){
        $f = explode("\n", @file_get_contents("/etc/squid3/caches.conf"));
        foreach ($f as $line) {
            if (preg_match("#max_filedescriptors\s+([0-9]+)#", $line, $re)) {
                $this->Current = $re[1];
                $this->current_in_conf=$re[1];
                break;
            }

        }
        VERBOSE("max_filedescriptors:  $this->Current",__LINE__);

        $squidman = new cache_manager();
        if($nocache){
            $GLOBALS["makeQueryForce"]=true;
        }
        $results = explode("\n", $squidman->makeQuery("info"));
        foreach ($results as $line) {
            if (preg_match("#Number of file desc currently in use:.*?([0-9]+)#", $line,$re)) {
                $this->current_filedesc=$re[1];
                continue;
            }

            if (preg_match("#Maximum number of file descriptors.*?([0-9]+)#", $line,$re)) {
                $this->Available = $re[1];

            }


        }


    }

    private function filedescriptors(){
        $this->filedescriptors_checking();
        $tpl=new template_admin();
        $Current = $this->Current;
        $Available = $this->Available;
        VERBOSE("Current =  $this->current_filedesc",__LINE__);
        VERBOSE("$this->Current <> $Available",__LINE__);
        if ($Current == 0) {
            return "";
        }
        if ($Available == 0) {
            return "";
        }

        $prc=$this->current_filedesc/$Available;
        $prc=round($prc*100,2);

        $this->filedesc_prc=$tpl->FormatNumber($this->current_filedesc);
        if($prc>0) {
            $this->filedesc_prc = "{$prc}%";
        }
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("squid_current_filedesc",$this->filedesc_prc);
        VERBOSE("Percentage $this->current_filedesc/$Available == $prc $this->filedesc_prc",__LINE__);
        VERBOSE("(in config) $Current > (In squid) $Available ",__LINE__);
        if(intval($this->current_in_conf) > intval($Available) ){
            VERBOSE("(in config) $Current > (In squid) $Available !!!",__LINE__);
            $text=$tpl->_ENGINE_parse_body("{diff_filedesc}");
            $text=str_replace("%s1",$Available,$text);
            $text=str_replace("%s2",$this->current_in_conf,$text);
            return "$text||js:Loadjs('fw.proxy.filedesc.php?filedesc-form-js=yes');";
        }



        if($prc>95){
            $text=$tpl->_ENGINE_parse_body("{filedesc_warning}");
            $text=str_replace("%s",$prc,$text);
            return "$text||js:Loadjs('fw.proxy.filedesc.php?filedesc-form-js=yes');";
        }

        return "";
    }

    public function ERROR_TO_ARRAY($ERR):array
    {
        $c = 0;
        $ARRAY = array();
        foreach ($ERR as $error) {
            $c++;
            if (strpos($error, "||") == 0) {
                continue;
            }
            $LEVEL=0;
            $icon = "fas fa-exclamation-circle";
            $ARRAY[$c]["ICO"] = $icon;
            $ft = explode("||", $error);
            $error = $ft[0];
            if(isset($ft[2])) {
                $LEVEL = $ft[2];
            }
            $ARRAY[$c]["LEVEL"] = $LEVEL;
            $ARRAY[$c]["ERROR"] = $error;
            if (preg_match("#js:(.+)#", $ft[1], $re)) {
                $ARRAY[$c]["JS"] = $re[1];
            }


        }

        return $ARRAY;
    }

}