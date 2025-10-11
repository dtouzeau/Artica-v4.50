<?php
include_once(dirname(__FILE__)."/class.squid.externals.acls.childrens.inc");
class squid_ntlm{
    private $SquidUrgency;
    private $DynamicGroupsAclsTTL;
    private $KerbAuthMethod;
    private $WindowsActiveDirectoryKerberos;
    private $DisableWinbindd;
    private $WinbinddInstalled;
    private $SQUID_NTLM_AUTH;
    private $KerbAuthInfos;
    private $AdminAsSeenNTLMPerfs;
    private $ActiveDirectorySquidHTTPHostname;
    private $SquidUsersNumber;
    private $SquidNTLMKeepAlive;
    private $BasicAuthenticatorEmergency;
    private $LDAP_AUTH;
    private $NTLM_AUTH;
    private $LDAP_EXTERNAL_AUTH;
    private $EnableKerbAuth;
    private $SquidLdapAuthEnableGroups;
    private $ASROOT;
    private $SquidClientParams;
    private $SquidLdapAuthBanner;
    private $EnableFakeAuth;
    private $EnableAdLDAPAuth=0;
    private $EnableipV6;
    private $UseNativeKerberosAuth;
    private $LockActiveDirectoryToKerberos;
    private $MicrohotSpotError;
    private $isSquid5=false;
    private $CORP_LICENSE=false;
    function __construct(){
        $this->SquidLdapAuthBanner=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLdapAuthBanner"));
        $this->SquidLdapAuthEnableGroups=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLdapAuthEnableGroups"));
        $this->BasicAuthenticatorEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BasicAuthenticatorEmergency"));
        $this->SquidNTLMKeepAlive=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNTLMKeepAlive"));
        $this->SquidUsersNumber=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUsersNumber"));
        $this->AdminAsSeenNTLMPerfs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AdminAsSeenNTLMPerfs"));
        $this->WinbinddInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WinbinddInstalled"));
        $this->DisableWinbindd=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableWinbindd"));
        $this->SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
        $this->DynamicGroupsAclsTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DynamicGroupsAclsTTL"));
        $this->KerbAuthMethod=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthMethod"));
        $this->SquidClientParams=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));
        $this->WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));
        $this->SQUID_NTLM_AUTH=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NtlmAuthPath"));
        $this->KerbAuthInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
        $this->ActiveDirectorySquidHTTPHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectorySquidHTTPHostname"));
        $this->EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
        $this->EnableFakeAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFakeAuth"));
        $this->EnableAdLDAPAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAdLDAPAuth"));
        $this->UseNativeKerberosAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseNativeKerberosAuth"));
        $this->LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){$this->LockActiveDirectoryToKerberos=1;}
        if($this->DynamicGroupsAclsTTL==0){$this->DynamicGroupsAclsTTL=3600;}
        if($this->DynamicGroupsAclsTTL<5){$this->DynamicGroupsAclsTTL=5;}
        $ini=new Bs_IniHandler();
        $ini->loadFile("/etc/artica-postfix/settings/Daemons/ArticaSquidParameters");
        $this->LDAP_AUTH=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidStandardLDAPAuth"));
        $this->LDAP_EXTERNAL_AUTH=intval(@file_get_contents('/etc/artica-postfix/settings/Daemons/SquidExternLDAPAUTH'));
        $this->EnableipV6=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6"));
        $SquidVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion");
        if(preg_match("#^(5|6|7)\.#",$SquidVersion)){$this->isSquid5=true;}

        $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
        $this->CORP_LICENSE=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();
        if(!$this->CORP_LICENSE){$EnableActiveDirectoryFeature=0;}

        if($EnableActiveDirectoryFeature==0){
            $this->EnableKerbAuth=0;
            $this->UseNativeKerberosAuth=0;
            $this->WindowsActiveDirectoryKerberos=0;
            $this->LockActiveDirectoryToKerberos=0;
        }


        if($this->EnableKerbAuth==1
            or $this->UseNativeKerberosAuth==1
            or $this->WindowsActiveDirectoryKerberos==1
            or $this->LockActiveDirectoryToKerberos==1
        ) {
            $this->LDAP_EXTERNAL_AUTH=0;
        }
        if(!isset($this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"])){$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]=null;}
        if(!isset($this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"])){$this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]=null;}

        if($this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]==null){
            if(strpos($this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"],"@")>0){
                $TR=explode("@",$this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]);
                $this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]=$TR[1];
            }
        }
    }

    public function build():bool{

        $UsePHPForProxyAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UsePHPForProxyAuth"));
        if($UsePHPForProxyAuth==0){
            shell_exec("/usr/sbin/artica-phpfpm-service -proxy-reconfigure-auth");
            return true;
        }

        @file_put_contents("/etc/squid3/authenticate.conf", "\n");
        @file_put_contents("/etc/squid3/authenticate.authenticated.conf","\n");
        @file_put_contents("/etc/squid3/acls_adgroup.conf","");
        $conf=array();
        $this->ntlm_params_defaults();
        $LockActiveDirectoryToKerberos= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));

        if($LockActiveDirectoryToKerberos==1){$this->UseNativeKerberosAuth=1;}
        if($HaClusterClient==1){
            $this->EnableAdLDAPAuth=0;
            $this->UseNativeKerberosAuth=1;
        }
        if(!$this->CORP_LICENSE){$this->UseNativeKerberosAuth=0;}


        if(!is_file("/etc/squid3/acls/DenyIPSrc")){@touch("/etc/squid3/acls/DenyIPSrc");}
        $conf[]="# Is Proxy v5 branch ?: $this->isSquid5";
        $conf[]="# HaClusterClient: $HaClusterClient";
        $conf[]=$this->kerberos_conf();
        $conf[]=$this->ntlm_fake_conf();
        $conf[]=$this->ntlm_auth_conf();
        $conf[]="# ------------------- LDAP CONFIGURATION ----------------".__LINE__;
        $conf[]=$this->ldap_auth_conf();
        $conf[]="# ------------------- AUTH IN PARENTS ----------------".__LINE__;
        $conf[]=$this->AuthParentPort();
        $conf[]="# ------------------- Active Directory LDAP  ----------------".__LINE__;
        $conf[]=$this->ldap_auth_ad();

        if($this->UseNativeKerberosAuth==1){
            $conf[]=$this->ads_groups();
        }

        $conf[]=$this->radius_auth_conf();
        $conf[]=$this->auth_param_basic(true);
        $conf[]="# ------------------- Micro HotSpot ----------------".__LINE__;
        $conf[]=$this->MicroHotSpot();
        $conf[]="# --------------------------------------------------";

        $VERSION=trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
        $SP=intval(@file_get_contents("/usr/share/artica-postfix/SP/$VERSION"));
        $finalConf="# Squid Auth builded by artica v$VERSION Service Pack $SP\n".@implode("\n", $conf)."\n";
        @file_put_contents("/etc/squid3/authenticate.conf",$finalConf );
        return true;
    }

    private function ntlm_fake_conf():string{
        $EnableFakeAuth=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFakeAuth");

        if($this->EnableAdLDAPAuth==1){
            $CONF[] = "# ntlm_fake_conf() EnableAdLDAPAuth = 1 - USE LDAP\n";
            $CONF[] = "#";
            return @implode("\n", $CONF) . "\n\n";
        }


        if($this->SquidUrgency==1){return "# ntlm_fake_conf(); Emergency = SquidUrgency";}
        if($EnableFakeAuth==0){return "# ntlm_fake_conf(); EnableFakeAuth = 0";}
        if($this->UseNativeKerberosAuth==1){return "# ntlm_fake_conf(); UseNativeKerberosAuth = 1";}
        if(!is_file("/lib/squid3/ntlm_fake_auth")){return "# ntlm_fake_conf(); /lib/squid3/ntlm_fake_auth = no such binary";}
        $this->ntlm_params_defaults();
        $conf[]= "auth_param ntlm program /lib/squid3/ntlm_fake_auth -S";
        $conf[]= "auth_param ntlm ".$this->ntlm_auth_childs();
        $conf[]= "auth_param ntlm keep_alive ".$this->ntlm_auth_keepalive();
        if($this->isSquid5) {
            $conf[] = "auth_param ntlm casesensitive off";
        }
        $conf[]= "";
        $conf[]= "#Other settings";
        $conf[]= $this->auth_params_basic_options();
        $conf[]= "acl authFailed src all";
        $conf[]= "acl AUTHENTICATED proxy_auth REQUIRED";
        $conf[]="# END FAKE NTLM Parameters --------------------------------\n";
        return @implode("\n", $conf);
    }
    private function ldap_auth_ad():string{
        if($this->EnableAdLDAPAuth==0){
            $CONF[] = "# ldap_auth_ad() EnableAdLDAPAuth = 0 - SKIP\n";
            $CONF[] = "#";
            return @implode("\n", $CONF) . "\n\n";
        }

        $this->ldap_auth_ad_update();

        $conf[]= "auth_param basic program /lib/squid3/artica-proxy-auth";
        $conf[]= "auth_param basic children ".$this->auth_param_basic_children();
        $conf[]= "auth_param basic realm Active Directory Identification";
        $conf[]=$this->auth_params_basic_options();
        $conf[]= "acl AUTHENTICATED proxy_auth REQUIRED";
        return @implode("\n", $conf) . "\n\n";


    }
    private function ldap_auth_syslog($text){
        if(!function_exists("syslog")){return false;}
        openlog("artica-proxy-auth", LOG_PID , LOG_SYSLOG);
        syslog(LOG_INFO, $text);
        closelog();
    }
    public function ldap_auth_ad_update():bool{
        $src="/usr/share/artica-postfix/bin/artica-proxy-auth";
        if(!is_file("/lib/squid3/artica-proxy-auth")){
            @copy($src,"/lib/squid3/artica-proxy-auth");
            @chmod("/lib/squid3/artica-proxy-auth",0755);
            return true;
        }
        $md5In=md5_file($src);
        $md5out=md5_file("/lib/squid3/artica-proxy-auth");
        $this->ldap_auth_syslog("[UPDATE]: Source: $md5In Destination: $md5out");
        if($md5In==$md5out){
            $this->ldap_auth_syslog("[UPDATE]: No change");
            return true;
        }
        $unix=new unix();
        $cp=$unix->find_program("cp");
        $squidbin=$unix->LOCATE_SQUID_BIN();
        shell_exec("/usr/sbin/artica-phpfpm-service -reload-proxy");
        for($i=0;$i<5;$i++){
            $this->ldap_auth_syslog("[UPDATE]: Updating binary...$i/5");
            shell_exec("$cp -f  $src /lib/squid3/artica-proxy-auth");
            $md5out=md5_file("/lib/squid3/artica-proxy-auth");
            if($md5In==$md5out){
                $this->ldap_auth_syslog("[UPDATE]: Updating binary [Success]...$i/5");
                @chmod("/lib/squid3/artica-proxy-auth",0755);
                shell_exec("/usr/sbin/artica-phpfpm-service -reload-proxy");
                return true;
            }
            shell_exec("/usr/sbin/artica-phpfpm-service -reload-proxy");
            usleep(800);
        }
        $this->ldap_auth_syslog("[UPDATE]: Updating binary [FAILED]...$i/5");
        return false;
    }
    private function AuthParentPort():string{

        if($this->EnableAdLDAPAuth==1){
            $CONF[] = "# AuthParentPort() EnableAdLDAPAuth = 1 - USE LDAP\n";
            $CONF[] = "#";
            return @implode("\n", $CONF) . "\n\n";
        }

        $file=basename(__FILE__);
        if($this->SquidUrgency==1){return "# AuthParentPort(); Emergency = SquidUrgency";}
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");

        if(!$q->FIELD_EXISTS("proxy_ports","AuthParentPort")){
            $q->QUERY_SQL("ALTER TABLE proxy_ports ADD AuthParentPort INTEGER NOT NULL DEFAULT '0'");
        }

        $sql="SELECT * FROM proxy_ports WHERE enabled=1 AND AuthParentPort=1";
        $results=$q->QUERY_SQL($sql);
        $conf=array();
        $conf[]= "# AuthParentPort ". count($results)." destination port(s) ($file/".__LINE__.")";

        $c=0;
        foreach ($results as $index=>$ligne){
            $ID=intval($ligne["ID"]);
            if($ID==0){continue;}
            $c++;
            $AclPortName="MyPortNameID{$ligne["ID"]}";
            $conf[]= "# Port acl $AclPortName";
            $conf[]="acl AuthParentPorts myportname $AclPortName";
            $conf[]="follow_x_forwarded_for allow AuthParentPorts";
        }
        if($c==0){
            $conf[]="#No destination port was defined, aborting ($file/".__LINE__.")";
            return @implode("\n",$conf);
        }


        $ntlm_auth_childs=$this->ntlm_auth_childs();
        $conf[]="";
        $conf[]="auth_param basic program /lib/squid3/basic_fake_auth";
        $conf[]="auth_param basic $ntlm_auth_childs";
        $conf[]="auth_param basic credentialsttl 10 minutes";
        if($this->isSquid5) {
            $conf[]="auth_param basic casesensitive off";
        }

        $conf[]="acl AuthParentPortH proxy_auth REQUIRED";
        $conf[]="http_access deny AuthParentPorts !AuthParentPortH all";
        return @implode("\n", $conf);

    }
    public function MicroHotSpotEnabled():bool{
        $SquidHotSpotUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHotSpotUrgency"));
        if($this->SquidUrgency==1){
            $this->MicrohotSpotError="# EnableSquidMicroHotSpot , external_acl_helper_hotspot() -> Emergency mode !! \n";
            return false;
        }
        if($SquidHotSpotUrgency==1){
            $this->MicrohotSpotError="# EnableSquidMicroHotSpot , external_acl_helper_hotspot() -> HotSpot Emergency mode !! ( see SquidHotSpotUrgency)\n";
            return false;
        }

        $EnableSquidMicroHotSpot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidMicroHotSpot"));

        if($EnableSquidMicroHotSpot==0){
            $this->MicrohotSpotError="# MicroHotSpot(".__CLASS__."/".__LINE__.") -> EnableSquidMicroHotSpot Not Enabled\n";
            return false;
        }

        return true;
    }
    function MicroHotSpotUri():string{
        $HotSpotRedirectUI=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotRedirectUI"));
        if(!preg_match("#(http|https):\/#",$HotSpotRedirectUI)){
            $HotSpotRedirectUI="http://$HotSpotRedirectUI";
        }
        $parse=parse_url($HotSpotRedirectUI);
        if(!isset($parse["port"])){$parse["port"]=0;}
        if(!isset($parse["host"])){$parse["host"]=php_uname("n");}
        $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
        $port=$parse["port"];
        $host=$parse["host"];
        if(strpos($host,":")){
            $tb=explode(":",$host);
            $host=$tb[0];
        }
        $zurl[]="http://";
        $zurl[]=$host;

        if($EnableNginx==1){
            if($port>0){
                $zurl[]=":$port";
            }
            return @implode($zurl);
        }
        $HotSpotListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotListenPort"));
        if($HotSpotListenPort<>80) {
            $zurl[] = ":" . $HotSpotListenPort;
        }
        return @implode($zurl);
    }
    private function MicroHotSpot():string{
        if(!$this->MicroHotSpotEnabled()){ return $this->MicrohotSpotError; }
        $WIFI4EU=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotWIFI4EU_ENABLE"));
        $HotSpotRedirectUI=$this->MicroHotSpotUri();
        $f[]="#\tMICRO HOTSPOT CONFIG";
        $f[]="# Check account with the micro hotspot";

        $parse=parse_url($HotSpotRedirectUI);
        $HotSpotHost=$parse["host"];
        if(strpos($HotSpotHost,":")){
            $tb=explode(":",$HotSpotHost);
            $HotSpotHost=$tb[0];
        }
        $f[] = "# HotSpot URL destination $HotSpotHost is allowed to display the Web page.";
        $IpClass=new IP();
        $f[] = "acl HotSpotDestIP dst 127.0.0.1/32";
        $f[] = "acl HotSpotDest dstdomain localhost.localdomain";

        if($IpClass->isValid($HotSpotHost)){
           $f[] = "# HotSpot destination is an IP address";
           $f[] = "acl HotSpotDestIP dst $HotSpotHost";
        }else{
           $f[] = "# HotSpot destination is an hostname";
           $f[] = "acl HotSpotDest dstdomain $HotSpotHost";
        }
        if($WIFI4EU==1){
                $f[] = "acl wifi4eu dstdomain collection.wifi4eu.ec.europa.eu";
                $f[]= "http_access allow wifi4eu";
                $f[] = "acl wifi4euazure dstdomain wifi4eucollectorprod.azurewebsites.net";
                $f[]= "http_access allow wifi4euazure";
                $f[] = "acl wifi4euswal dstdomain cdn.jsdelivr.net";
                $f[]= "http_access allow wifi4euswal";

            }
        $f[]="acl AllHotSpotDest any-of HotSpotDestIP HotSpotDest";
        $f[]="http_access allow AllHotSpotDest";
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $results=$q->QUERY_SQL("SELECT * FROM hotspot_networks");

        if(count($results)>0){
            foreach ($results as $index=>$ligne) {
                $f[] = "acl HotSpotNets src {$ligne["pattern"]}";
            }
        }
        return @implode("\n", $f);

    }
    private function auth_params_basic_options(): string{
        $SquidClientParams=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));

        if(!isset($SquidClientParams["authenticate_cache_garbage_interval"])){$SquidClientParams["authenticate_cache_garbage_interval"]=3600;}
        if(!isset($SquidClientParams["authenticate_ttl"])){$SquidClientParams["authenticate_ttl"]=3600;}
        if(!isset($SquidClientParams["authenticate_ip_ttl"])){$SquidClientParams["authenticate_ip_ttl"]=1;}
        if(!isset($SquidClientParams["credentialsttl"])){$SquidClientParams["credentialsttl"]=7200;}

        if(intval($SquidClientParams["authenticate_cache_garbage_interval"])==0){$SquidClientParams["authenticate_cache_garbage_interval"]=3600;}
        if(intval($SquidClientParams["authenticate_ttl"])==0){$SquidClientParams["authenticate_ttl"]=3600;}
        if(intval($SquidClientParams["authenticate_ip_ttl"])==0){$SquidClientParams["authenticate_ip_ttl"]=1;}

        if($SquidClientParams["authenticate_ttl"]>$SquidClientParams["authenticate_cache_garbage_interval"]){
            $SquidClientParams["authenticate_cache_garbage_interval"]=$SquidClientParams["authenticate_ttl"];
        }
        if(intval($SquidClientParams["credentialsttl"])==0){$SquidClientParams["credentialsttl"]=7200;}
        $conf[]= "auth_param basic credentialsttl {$SquidClientParams["credentialsttl"]} seconds";
        $conf[]= "authenticate_ttl {$SquidClientParams["authenticate_ttl"]} seconds";
        $conf[]= "authenticate_ip_ttl {$SquidClientParams["authenticate_ip_ttl"]} seconds";
        $conf[]= "authenticate_cache_garbage_interval {$SquidClientParams["authenticate_cache_garbage_interval"]} seconds";
        $conf[]= "";
        return @implode("\n",$conf);
    }
    private function auth_param_basic($direct=false){
        if($this->EnableAdLDAPAuth==1){
            $CONF[] = "# auth_param_basic() EnableAdLDAPAuth = 1 - USE LDAP\n";
            $CONF[] = "#";
            return @implode("\n", $CONF) . "\n\n";
        }


        if($this->BasicAuthenticatorEmergency==1){
            return "# auth_param_basic(); BasicAuthenticatorEmergency = 1; Emergency is enabled, Aborting...[".__LINE__."]\n";
        }
        if($this->SquidUrgency==1){return null;}
        $conf=array();
        if(!is_file($this->SQUID_NTLM_AUTH)){
            if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
            $this->SQUID_NTLM_AUTH=$unix->find_program("ntlm_auth");
        }
        echo "Starting......: ".date("H:i:s")." [AUTH]: Basic: get definitions\n";
        $Multiple=false;

        $conf[]="# NTLM_AUTH = $this->NTLM_AUTH / KerbAuthMethod = $this->KerbAuthMethod [".__LINE__."]";
        if($this->NTLM_AUTH==1){
            $conf[]="# NTLM_AUTH = Check Direct: $direct [".__LINE__."]";
            if(!$direct){
                if( ($this->KerbAuthMethod==0) OR ($this->KerbAuthMethod=2) ){
                    if(!isset($GLOBALS["auth_param_basic_sended"])){
                        $conf[]=$this->ldap_auth_conf();
                        $GLOBALS["auth_param_basic_sended"]=true;
                    }
                }else{
                    $conf[]= "#Basic authentication for other browser is disabled KerbAuthMethod=$this->KerbAuthMethod";
                }
            }
        }

        $acl=new squid_acls();
        if($acl->IsObjectAuth()){
            echo "Starting......: ".date("H:i:s")." [AUTH]: Basic: Objects authentication enabled\n";
            $Multiple=true;
        }else{
            echo "Starting......: ".date("H:i:s")." [AUTH]: Basic: Objects authentication is disabled\n";
        }
        if($this->LDAP_AUTH==1){return null;}
        if($this->LDAP_EXTERNAL_AUTH==1){return null;}

        if($Multiple){
            $conf=array();

            echo "Starting......: ".date("H:i:s")." [AUTH]: Basic: multiple authentication methods enabled\n";
            $basic_fake_auth="/lib/squid3/basic_fake_auth";
            if($this->NTLM_AUTH==1){$conf[]= "#Basic authentication for other browsers that did not supports NTLM: L.".__LINE__;}
            if($this->NTLM_AUTH==0){$conf[]= "#Basic authentication for other browsers";}
            $conf[]= "auth_param basic program $basic_fake_auth";
            $conf[]= "auth_param basic children ".$this->auth_param_basic_children();
            $conf[]= "auth_param basic realm Basic Identification";
            $conf[]=$this->auth_params_basic_options();

            $external_acl_children_class=new external_acl_children_class();
            $external_acl_childrens=$external_acl_children_class->build(0,true);
            $conf[]= "external_acl_type BasicFakeAuth $external_acl_childrens %SRC %LOGIN %{Proxy-Authorization} /usr/share/artica-postfix/external_acl_basic_auth.php\n";
        }

        echo "Starting......: ".date("H:i:s")." [AUTH]: Basic: ".count($conf)." rows\n";

        return @implode("\n", $conf)."\n";

    }

    private function ads_groups():string{
        $conf[]= "# Dynamic ACLs groups (ads_groups)";
        $DynamicGroupsAclsTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DynamicGroupsAclsTTL"));
        if($DynamicGroupsAclsTTL==0){$DynamicGroupsAclsTTL=3600;}

        $cmdline="/lib/squid3/go-squid-auth";
        $LDAPGroupKeepPHP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPGroupKeepPHP"));
        $EnableExternalACLADAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableExternalACLADAgent"));
        if ($EnableExternalACLADAgent==1){
            $cmdline="/lib/squid3/external_acls_ad_agent";
        }
        if ($LDAPGroupKeepPHP==1){
            $cmdline="/usr/share/artica-postfix/external_acl_squid_ldap.php";
        }
        if(!isset($this->SquidClientParams["auth_param_ntlmgroup_idle"])){
            $this->SquidClientParams["auth_param_ntlmgroup_idle"]=1;
        }
        if(!isset($this->SquidClientParams["auth_param_ntlmgroup_children"])){
            $this->SquidClientParams["auth_param_ntlmgroup_children"]=15;
        }
        if(!isset($this->SquidClientParams["auth_param_ntlmgroup_startup"])){
            $this->SquidClientParams["auth_param_ntlmgroup_startup"]=1;
        }

        if(!isset($this->SquidClientParams["Go_Squid_Auth_Concurrency"])){
            $this->SquidClientParams["Go_Squid_Auth_Concurrency"]=50;
        }
        $concurrency=$this->SquidClientParams["Go_Squid_Auth_Concurrency"];


        $auth_param_ntlmgroup_idle=intval($this->SquidClientParams["auth_param_ntlmgroup_idle"]);
        $auth_param_ntlmgroup_children=intval($this->SquidClientParams["auth_param_ntlmgroup_children"]);
        $auth_param_ntlmgroup_startup=intval($this->SquidClientParams["auth_param_ntlmgroup_startup"]);

        if($auth_param_ntlmgroup_idle==0){$auth_param_ntlmgroup_idle=2;}
        if($auth_param_ntlmgroup_children==0){$auth_param_ntlmgroup_children=15;}
        if($auth_param_ntlmgroup_startup==0){$auth_param_ntlmgroup_startup=2;}

        $debug = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_External_ACL_Ldap_Debug"));

        $Go_Shield_External_ACL_Ldap_SearchTimelimit=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_External_ACL_Ldap_SearchTimelimit"));
        $Go_Shield_External_ACL_Ldap_ConnTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_External_ACL_Ldap_ConnTimeout"));
        $conf[]= "#Debug=$debug\n#SearchTimelimit=$Go_Shield_External_ACL_Ldap_SearchTimelimit\n#ConnTimeout=$Go_Shield_External_ACL_Ldap_ConnTimeout\nexternal_acl_type ads_group ttl=$DynamicGroupsAclsTTL negative_ttl=1 concurrency=$concurrency children-startup=$auth_param_ntlmgroup_startup children-idle=$auth_param_ntlmgroup_idle children-max=$auth_param_ntlmgroup_children ipv4 %LOGIN $cmdline";
        $GLOBALS["ads_groups_already_sent"]=true;
        @file_put_contents("/etc/squid3/acls_adgroup.conf",@implode("\n",$conf));
        return "# ads groups OK";

    }

    private function ntlm_auth_conf(){
        $SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));

        if($this->EnableAdLDAPAuth==1){
            $CONF[] = "# ntlm_auth_conf() EnableAdLDAPAuth = 1 - USE LDAP\n";
            $CONF[] = "#";
            return @implode("\n", $CONF) . "\n\n";
        }

        if(!$this->CORP_LICENSE){
            $conf[]= "#--------- " .base64_decode("TGljZW5zZSBGQUxTRQ==");
            $this->UseNativeKerberosAuth=0;
            $this->EnableKerbAuth==0;
        }

        if($this->UseNativeKerberosAuth==1){return "# ntlm_auth_conf(); UseNativeKerberosAuth = 1";}

        @file_put_contents("/etc/squid3/acls_adgroup.conf","");
        $conf[]= "# ntlm_auth_conf(); SquidUrgency             = $SquidUrgency";



        if($SquidUrgency==1){
            if($this->EnableKerbAuth==1){
                $conf[]= "# ntlm_auth_conf(); SquidUrgency is enabled, Aborting...[".__LINE__."]";
                $conf[]= "# see /etc/artica-postfix/settings/Daemons/SquidUrgency";
                $conf[]= "http_access allow all";
                return @implode("\n",$conf);
            }
            $conf[]= "# ntlm_auth_conf(); SquidUrgency is enabled, Aborting...[".__LINE__."]";
            $conf[]= "# see /etc/artica-postfix/settings/Daemons/SquidUrgency\n";
            return @implode("\n",$conf);
        }

        $UseDynamicGroupsAcls=1;

        if($this->DisableWinbindd==1){
            $conf[]= "# DisableWinbindd is turned to 1 All will be disabled ! [".__LINE__."]";
            $this->EnableKerbAuth=0;$this->NTLM_AUTH=0;
        }
        $this->ntlm_params_defaults();
        if($this->EnableKerbAuth==1){$UseDynamicGroupsAcls=1;}



        if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): DisableWinbindd=$this->DisableWinbindd\n";}
        if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): UseDynamicGroupsAcls=$UseDynamicGroupsAcls\n";}
        if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): DynamicGroupsAclsTTL=$this->DynamicGroupsAclsTTL\n";}
        if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): KerbAuthMethod=$this->KerbAuthMethod\n";}
        if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): EnableKerbAuth=$this->EnableKerbAuth\n";}


        // voir permissions on /var/lib/samba/winbindd_privileged
        // http://www.cyberciti.biz/faq/squid-ntlm-authentication-configuration-howto/
        $conf[]= "#--------- EnableKerbAuth = $this->EnableKerbAuth Method:$this->KerbAuthMethod [".__LINE__."]";
        $conf[]= "#--------- Use kerberos: $this->WindowsActiveDirectoryKerberos [".__LINE__."]";


        if($this->EnableKerbAuth==1){
            if($this->WinbinddInstalled==0){
                if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): unable to stat winbind\n";}
                $conf[]= "#--------- NTLM AUTH settings unable to stat winbind";
                return @implode("\n", $conf);
            }
        }else{
            if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): NTLM_AUTH=0, no ntlm is set\n";}
            $conf[]= "#--------- EnableKerbAuth=0, no ntlm is set DisableWinbindd=$this->DisableWinbindd [".__LINE__."]";
            return @implode("\n", $conf);

        }

        if(is_file("/usr/bin/ntlm_auth")){
            $this->SQUID_NTLM_AUTH="/usr/bin/ntlm_auth";
        }
        if(!is_file("/etc/squid3/net_ads_group.pl")){
            if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
            $unix=new unix();
            $php=$unix->LOCATE_PHP5_BIN();
            shell_exec("$php /usr/share/artica-postfix/exec.squid.netads.php >/dev/null 2>&1");
        }

        if(trim($this->SQUID_NTLM_AUTH)==null){
            if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): No NTLM auth_param basic program found\n";}
            return "#No NTLM auth_param basic program found !\n";
        }


        $conf[]= "# --------- NTLM AUTH settings (line:".__LINE__.") ---------";
        $conf[]= "# Authentification mode, building using squid compiled for artica";
        $conf[]= "# Kerberos/NTLM authentication: (KerbAuthMethod = $this->KerbAuthMethod)";
        $conf[]= "# WindowsActiveDirectoryKerberos: $this->WindowsActiveDirectoryKerberos";

        $NOT_NTLM=false;

        if($this->KerbAuthMethod==0){
            if($this->WindowsActiveDirectoryKerberos==1){
                $conf[]="#KerbAuthMethod = 0 and WindowsActiveDirectoryKerberos =1";
                $conf[]=$this->kerberos_conf();
                if(isset($GLOBALS["negotiate_kerberos_auth"])){$conf[]=$GLOBALS["negotiate_kerberos_auth"];}
                $NOT_NTLM=true;
            }

        }

        if($this->KerbAuthMethod==3){
            if($this->WindowsActiveDirectoryKerberos==0){
                $this->KerbAuthMethod=0;
            }else{
                $conf[]="# KerbAuthMethod = 3 and WindowsActiveDirectoryKerberos = 0";
                $conf[]="# NOT_NTLM = True";
                $conf[]=$this->kerberos_conf();
                if(isset($GLOBALS["negotiate_kerberos_auth"])){$conf[]=$GLOBALS["negotiate_kerberos_auth"];}
                $NOT_NTLM=true;
            }

        }


        if(($this->KerbAuthMethod==0) OR ($this->KerbAuthMethod==1)){
            if(!$NOT_NTLM){
                $conf[]="#KerbAuthMethod = 0/1 and NOT_NTLM = False";
                $conf[]=$this->ntlm_pure_conf();
                $conf[]= "";
            }
        }

        if($this->KerbAuthMethod==2){
            $conf[]="# Only basic authentication [".__LINE__."]";
            $conf[]=$this->auth_param_basic();

        }

        $conf[]=$this->ads_groups();
        $conf[]= "";


        if(!is_file("/etc/squid3/non_ntlm.acl")){@touch("/etc/squid3/non_ntlm.acl");}
        if(!is_file("/etc/squid3/non_ntlm.access")){@touch("/etc/squid3/non_ntlm.access");}
        @chown("/etc/squid3/non_ntlm.access", "squid");
        @chgrp("/etc/squid3/non_ntlm.access", "squid");
        @chown("/etc/squid3/non_ntlm.acl", "squid");
        @chgrp("/etc/squid3/non_ntlm.acl", "squid");

        $KerbAuthInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));

        if(!isset($KerbAuthInfos["WINDOWS_SERVER_NETBIOSNAME"])){
            $KerbAuthInfos["WINDOWS_SERVER_NETBIOSNAME"]=null;

        }

        $ldapusername=$KerbAuthInfos["WINDOWS_SERVER_ADMIN"];
        $ldappassword=$KerbAuthInfos["WINDOWS_SERVER_PASS"];
        $ADNETIPADDR=$KerbAuthInfos["ADNETIPADDR"];
        $WINDOWS_DNS_SUFFIX=$KerbAuthInfos["WINDOWS_DNS_SUFFIX"];
        $WINDOWS_SERVER_NETBIOSNAME=$KerbAuthInfos["WINDOWS_SERVER_NETBIOSNAME"];

        if($ADNETIPADDR==null){$ADNETIPADDR="$WINDOWS_SERVER_NETBIOSNAME.$WINDOWS_DNS_SUFFIX";}
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WINDOWS_SERVER_ADMIN", $ldapusername);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WINDOWS_SERVER_PASS", $ldappassword);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WINDOWS_DNS_SUFFIX", $WINDOWS_DNS_SUFFIX);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ADNETIPADDR", $ADNETIPADDR);


        $conf[]= "#Other settings";
        $conf[]=$this->auth_params_basic_options();
        $conf[]= "acl authFailed src all";
        $conf[]= "acl AUTHENTICATED proxy_auth REQUIRED";
        $conf[]="# END NTLM Parameters --------------------------------\n";

        $GLOBALS["PROXY_NTLM_SWITCH"]=true;
        @file_put_contents("/etc/squid3/authenticate.authenticated.conf","#--------- ACL AUTHENTICATED for authentication method\nacl authFailed src all\nacl AUTHENTICATED proxy_auth REQUIRED\ninclude /etc/squid3/non_ntlm.acl\n");
        return implode("\n",$conf);

    }

    private function radius_auth_conf(){

        if($this->EnableAdLDAPAuth==1){
            $CONF[] = "# radius_auth_conf() EnableAdLDAPAuth = 1 - USE LDAP\n";
            $CONF[] = "#";
            return @implode("\n", $CONF) . "\n\n";
        }

        if($this->UseNativeKerberosAuth==1){return "# radius_auth_conf(); UseNativeKerberosAuth = 1";}
        if(!is_file("/lib/squid3/basic_radius_auth")){return null;}
        if($this->SquidUrgency==1){return null;}
        $SquidRadiusAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRadiusAuth"));
        if($SquidRadiusAuth==0){return null;}
        $auth_banner="Basic credentials, Please logon...";
        if($this->SquidLdapAuthBanner<>null){$auth_banner=$this->SquidLdapAuthBanner;}

        $radiusserver=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("radiusserver");
        $radiuspassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("radiuspassword");
        $radiusidentifier=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("radiusidentifier");
        $radiusport=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("radiusport"));
        if($radiusport==0){$radiusport=1812;}


        $f[]="server $radiusserver";
        $f[]="port $radiusport";
        $f[]="secret $radiuspassword";
        if($radiusidentifier<>null){$f[]="identifier $radiusidentifier";}
        @file_put_contents("/etc/squid3/radius_config", @implode("\n", $f)."\n");
        @chown("/etc/squid3/radius_config","squid");


        $conf[]= "auth_param basic program /lib/squid3/basic_radius_auth -f /etc/squid3/radius_config";
        $conf[]= "auth_param basic children ".$this->auth_param_basic_children();
        $conf[]= "auth_param basic realm $auth_banner";
        $conf[]=$this->auth_params_basic_options();
        $conf[]= "acl ldapauth proxy_auth REQUIRED";
        $conf[]="";

        return implode("\n",$conf);


    }

    private function keytab_path():string{
        $f[]="/etc/squid3/krb5.keytab";
        $f[]="/etc/squid3/krb5.keytab";
        foreach ($f as $path){
            if(is_file($path)){return $path;}
        }
        return "";
    }

    private function kerberos_conf():string{
        $LockActiveDirectoryToKerberos = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){
            $this->EnableAdLDAPAuth=0;
            $LockActiveDirectoryToKerberos=1;
        }
        if(!$this->CORP_LICENSE){
            $CONF[] = "# License error";
            $LockActiveDirectoryToKerberos=0;
        }


        if($this->EnableAdLDAPAuth==1){
            $CONF[] = "# kerberos_conf() EnableAdLDAPAuth = 1 - USE LDAP\n";
            $CONF[] = "#";
            return @implode("\n", $CONF) . "\n\n";
        }


        if($LockActiveDirectoryToKerberos==0){
            $CONF[] = "# kerberos_conf() LockActiveDirectoryToKerberos = 0\n";
            $CONF[] = "#";
            return @implode("\n", $CONF) . "\n\n";
        }

        $LockActiveDirectoryToKerberosBasic=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberosBasic"));
        $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        $EnableSquidMicroHotSpot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidMicroHotSpot"));
        if($LockActiveDirectoryToKerberos==1){$this->UseNativeKerberosAuth=1;}
        if($HaClusterClient==1){$this->UseNativeKerberosAuth=1;$LockActiveDirectoryToKerberos=0;}

        $f[]="# kerberos_conf(); EnableSquidMicroHotSpot            = $EnableSquidMicroHotSpot";
        $f[]="# kerberos_conf(); LockActiveDirectoryToKerberos      = $LockActiveDirectoryToKerberos";
        $f[]="# kerberos_conf(); LockActiveDirectoryToKerberosBasic = $LockActiveDirectoryToKerberosBasic";
        $f[]="# kerberos_conf(); HaClusterClient                    = $HaClusterClient";
        $f[]="# kerberos_conf(); UseNativeKerberosAuth              = $this->UseNativeKerberosAuth";

        if ($this->UseNativeKerberosAuth == 0) {
            $f[]="# UseNativeKerberosAuth = is disabled";
            return @implode("\n",$f);

        }

        if($LockActiveDirectoryToKerberos==1 OR $this->UseNativeKerberosAuth ==1 ) {
            $keypath = $this->keytab_path();
            if ($keypath == null) {
                $CONF[] = "# FATAL ! keytab /etc/squid3/krb5.keytab missing\n";
                $CONF[] = "#";
                return @implode("\n", $CONF) . "\n\n";
            }
        }



        $KerberosSPN = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerberosSPN");
        $kerberosActiveDirectoryLBEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kerberosActiveDirectoryLBEnable"));
        if($LockActiveDirectoryToKerberos==1){$kerberosActiveDirectoryLBEnable=1;}

        if(is_file($keypath)) {
            @chown($keypath, "squid");
            @chgrp($keypath, "squid");
            @chmod($keypath, 0755);
        }


        if (!is_file("/lib/squid3/negotiate_kerberos_auth")) {
            if ($GLOBALS["VERBOSE"]) {
                echo "!!!! FATAL /lib/squid3/negotiate_kerberos_auth No such file !!!\n";
            }
            $f[]="# kerberos_conf(): FATAL /lib/squid3/negotiate_kerberos_auth No such file";
            return @implode("\n",$f);

        }


        if($LockActiveDirectoryToKerberosBasic==1){
            if(!is_file("/lib/squid3/basic_ldap_auth")){
                $CONF[]="# FATAL ! /lib/squid3/basic_ldap_auth, no such binary! ";
                $LockActiveDirectoryToKerberosBasic=0;
            }
        }


        if($LockActiveDirectoryToKerberos==1){
            $CONF[]="# EnableSquidMicroHotSpot            = $EnableSquidMicroHotSpot (".__LINE__.")";
            $CONF[]="# LockActiveDirectoryToKerberos      = $LockActiveDirectoryToKerberos (".__LINE__.")";
            $CONF[]="# LockActiveDirectoryToKerberosBasic = $LockActiveDirectoryToKerberosBasic (".__LINE__.")\n";


            $CONF[]="auth_param negotiate program ". $this->negotiate_kerberos_auth();
            $CONF[]="auth_param negotiate ".$this->ntlm_auth_childs();
            $CONF[]="auth_param negotiate keep_alive on";
            if($LockActiveDirectoryToKerberosBasic==1){
                if($EnableSquidMicroHotSpot==0) {
                    $bin="/lib/squid3/basic_ldap_auth";
                    $INFO = unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
                    @file_put_contents("/etc/squid3/ldappass.txt", $INFO["WINDOWS_SERVER_PASS"]);
                    $opts[] = "-b \"{$INFO["LDAP_SUFFIX"]}\"";
                    $opts[] = "-D \"{$INFO["WINDOWS_SERVER_ADMIN"]}\"";
                    $opts[] = "-W /etc/squid3/ldappass.txt";
                    $opts[] = "-f sAMAccountName=%s -v 3";
                    // $opts[]="-u cn";
                    $opts[] = "-h {$INFO["ADNETIPADDR"]}";

                    $CONF[] = "\n";
                    $CONF[] = "auth_param basic program $bin -R " . @implode(" ", $opts);
                    $CONF[] = "auth_param basic children " . $this->auth_param_basic_children();
                    $CONF[] = "auth_param basic realm Active Directory {$INFO["WINDOWS_DNS_SUFFIX"]}";
                    $CONF[] = $this->auth_params_basic_options();

                }else{
                    $CONF[] = "# Forward non-active directory to splash screen";
                }
                $CONF[] = "";

            }


            $CONF[]="acl AUTHENTICATED proxy_auth REQUIRED";
            return @implode("\n",$CONF)."\n\n";
        }

        $f=array(); // Important SP 206
        $f[] = "/lib/squid3/negotiate_kerberos_auth";
        if ($kerberosActiveDirectoryLBEnable == 0) {
            $f[] = "-r -s $KerberosSPN";
        }else{
            $f[] = "-r -s GSS_C_NO_NAME";
        }
        $f[]="-k /etc/squid3/krb5.keytab";



        $CONF[]="# LockActiveDirectoryToKerberos = $LockActiveDirectoryToKerberos (".__LINE__.")\n";
        $CONF[]="auth_param negotiate program ".@implode(" ",$f);
        $CONF[]="auth_param negotiate ".$this->ntlm_auth_childs();
        $CONF[]="auth_param negotiate keep_alive on";
        $CONF[]="\n";

        $KerberosUsername=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerberosUsername");
        $KerberosPassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerberosPassword");
        $kerberosActiveDirectoryHost=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("kerberosActiveDirectoryHost");
        $kerberosActiveDirectorySuffix=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kerberosActiveDirectorySuffix"));

        if($kerberosActiveDirectorySuffix==null) {
            include_once(dirname(__FILE__) . "/class.ActiveDirectoryRootDSE.inc");
            $ad_rootdse = new ad_rootdse($kerberosActiveDirectoryHost, 389, $KerberosUsername, $KerberosPassword);
            $kerberosActiveDirectorySuffix = $ad_rootdse->RootDSE();
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kerberosActiveDirectorySuffix",$kerberosActiveDirectorySuffix);
        }

        if(!preg_match("#^(.+?)@(.+)#",$KerberosUsername,$re)) {
            $domain = $kerberosActiveDirectorySuffix;
            $domain = str_ireplace("dc=", "", $domain);
            $domain = str_ireplace(",", ".", $domain);
        }else{
            $domain=$re[2];
        }

        @file_put_contents("/etc/squid3/ldappass.txt",$KerberosPassword);
        $opts[]="-b \"$kerberosActiveDirectorySuffix\"";
        $opts[]="-D \"$KerberosUsername\"";
        $opts[]="-W /etc/squid3/ldappass.txt";
        $opts[]="-f sAMAccountName=%s -v 3";
        // $opts[]="-u cn";
        $opts[]="-h $kerberosActiveDirectoryHost";

        $realm_info="Active Directory $domain";

        if($HaClusterClient==1){$realm_info=$this->hostname_g();}


        $CONF[]="auth_param basic program /lib/squid3/basic_ldap_auth -R ".@implode(" ",$opts);
        $CONF[]="auth_param basic children ".$this->auth_param_basic_children();
        $CONF[]="auth_param basic realm $realm_info";
        $CONF[]=$this->auth_params_basic_options();
        $CONF[]="";




        $CONF[]="acl AUTHENTICATED proxy_auth REQUIRED";

        return @implode("\n",$CONF)."\n\n";


    }

    private function ldap_local_config(){
        $ldap=new clladp();
        if($ldap->ldap_host==null){$ldap->ldap_host="127.0.0.1";}

        $ldap_host          = $ldap->ldap_host;
        $ldap_port          = $ldap->ldap_port;
        $suffix             = $ldap->suffix;
        $ldap_admin         ="cn=$ldap->ldap_admin,$ldap->suffix";
        $ldap_password      =$ldap->ldap_password;
        $user_filter        ="(&(objectClass=userAccount)(uid=%s))";
        $group_filter       ="(&(objectClass=posixGroup)(gidNumber=%a)(memberUid=%v))";
        $auth_banner        ="Basic credentials, Please logon...";
        $ipv4               =" ipv4 ";
        $SQUID_LDAP_AUTH    =$this->squid_ldap_auth_path();
        $squid_ldap_group_path=$this->squid_ldap_group_path();

        if($this->EnableipV6==1){$ipv4=" ";}
        if($this->SquidLdapAuthBanner<>null){$auth_banner=$this->SquidLdapAuthBanner;}

        $conf[]= "# LDAP AUTH settings ( ".__CLASS__."/". __LINE__.")";
        $conf[]= "# Authentification mode, building using squid compiled for $ldap_host:$ldap_port (".__LINE__.")";
        $conf[]= "# BasicAuthenticatorEmergency = $this->BasicAuthenticatorEmergency";
        if($this->BasicAuthenticatorEmergency==1) {
            return @implode("\n",$conf);
        }
        $conf[]= "auth_param basic program $SQUID_LDAP_AUTH -b \"$suffix\" -D \"$ldap_admin\" -w \"$ldap_password\" -f \"$user_filter\" -v 3 -h $ldap_host -p $ldap_port";

        $conf[]= "external_acl_type ldap_group{$ipv4}%LOGIN " . $this->squid_ldap_group_path()." -D \"$ldap_admin\" -w \"$ldap_password\" -b \"$suffix\"  -f \"$group_filter\" -S -v 3 -h $ldap_host -p $ldap_port";
        $conf[]= "auth_param basic children ".$this->auth_param_basic_children();
        $conf[]= "auth_param basic realm $auth_banner";
        $conf[]=$this->auth_params_basic_options();
        $conf[]= "acl ldapauth proxy_auth REQUIRED";
        $conf[]="";
        return implode("\n",$conf);


    }

    private function ldap_auth_conf($NokerbCheck=false){

        if($this->EnableAdLDAPAuth==1){
            $CONF[] = "# ldap_auth_conf() EnableAdLDAPAuth = 1 - USE LDAP\n";
            $CONF[] = "#";
            return @implode("\n", $CONF) . "\n\n";
        }

        if($this->UseNativeKerberosAuth==1){return "# ldap_auth_conf(): UseNativeKerberosAuth = 1 -> SKIP";}
        if($this->BasicAuthenticatorEmergency==1){return "# ldap_auth_conf Emergency !!!!\n";}
        if($this->SquidUrgency==1){
            return "# Proxy is in Emergency Mode! [".__LINE__."]";
        }

        $SquidRadiusAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRadiusAuth"));
        $SquidStandardLDAPAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidStandardLDAPAuth"));
        $EnableSquidMicroHotSpot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidMicroHotSpot"));
        $EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));

        if($SquidRadiusAuth==1){
            $conf[]= "# RADIUS Authentication is enabled, Aborting LDAP authentication [".__LINE__."]";
            return @implode("\n", $conf);
        }

        $conf[]= "# SquidStandardLDAPAuth   : $SquidStandardLDAPAuth        [".__LINE__."]";
        $conf[]= "# LDAP_EXTERNAL_AUTH      : $this->LDAP_EXTERNAL_AUTH     [".__LINE__."]";
        $conf[]= "# EnableSquidMicroHotSpot : $EnableSquidMicroHotSpot      [".__LINE__."]";
        $conf[]= "# EnableOpenLDAP          : $EnableOpenLDAP               [".__LINE__."]";

        if($SquidStandardLDAPAuth==1){
            $conf[]=$this->ldap_local_config();
            return @implode("\n",$conf);
        }
        if($this->LDAP_EXTERNAL_AUTH==1){
            $conf[]= "#--------- External LDAP database = \"$this->LDAP_EXTERNAL_AUTH\"\n";
            $squid_external_ldap_builder=new squid_external_ldap_builder();
            $conf[]=$squid_external_ldap_builder->Build();
            $conf[]="";
            $this->SquidLdapAuthEnableGroups=1;
            return @implode("\n", $conf);
        }

        $EnableKerbAuth=$this->EnableKerbAuth;

        if(!$NokerbCheck){
            if($EnableKerbAuth==1){
                if($EnableSquidMicroHotSpot==1){
                    $conf[]= "# HotSpot authentication for other browser that did not supports NTLM";
                    return @implode("\n", $conf);

                }

                if(is_file($this->SQUID_NTLM_AUTH)){
                    $conf[]= "# Basic authentication for other browser that did not supports NTLM BasicAuthenticatorEmergency=$this->BasicAuthenticatorEmergency";
                    if($this->BasicAuthenticatorEmergency==0){
                        $conf[]= "auth_param basic program $this->SQUID_NTLM_AUTH --helper-protocol=squid-2.5-basic";
                        $conf[]= "auth_param basic children ".$this->auth_param_basic_children();
                        $conf[]= "auth_param basic realm Active Directory Basic Identification";
                        $conf[]= $this->auth_params_basic_options();
                    }
                    $conf[]= "";
                }else{
                    $conf[]= "#Basic authentication SQUID_NTLM_AUTH no such binary";
                }
                return @implode("\n", $conf);


            }
        }

        if($this->LDAP_AUTH==0){
            if($this->LDAP_EXTERNAL_AUTH<>1){
                echo "Starting......: ".date("H:i:s")." [AUTH]: LDAP authentication method is disabled\n";
                return "#--------- LDAP authentication method is disabled\n";
            }
        }


        $ldap=new clladp();
        if($ldap->ldap_host==null){$ldap->ldap_host="127.0.0.1";}

        $ldap_host=$ldap->ldap_host;
        $ldap_port=$ldap->ldap_port;
        $suffix=$ldap->suffix;
        $ldap_admin="cn=$ldap->ldap_admin,$ldap->suffix";
        $ldap_password=$ldap->ldap_password;
        $user_filter="(&(objectClass=userAccount)(uid=%s))";
        $group_filter="(&(objectClass=posixGroup)(gidNumber=%a)(memberUid=%v))";
        $auth_banner="Basic credentials, Please logon...";
        if($this->SquidLdapAuthBanner<>null){$auth_banner=$this->SquidLdapAuthBanner;}
        $ipv4=" ipv4 ";
        if($this->EnableipV6==1){$ipv4=" ";}

        $SQUID_LDAP_AUTH=$this->squid_ldap_auth_path();
        $squid_ldap_group_path=$this->squid_ldap_group_path();
        $conf[]= "#--------- LDAP AUTH settings ( ".__CLASS__."/". __LINE__.")";

        $conf[]= "#Authentification mode, building using squid compiled for $ldap_host:$ldap_port (".__LINE__.")";
        if(trim($SQUID_LDAP_AUTH)<>null){
            $conf[]= "# BasicAuthenticatorEmergency = $this->BasicAuthenticatorEmergency";
            $conf[]= "auth_param basic program $SQUID_LDAP_AUTH -b \"$suffix\" -D \"$ldap_admin\" -w \"$ldap_password\" -f \"$user_filter\" -v 3 -h $ldap_host -p $ldap_port";

            $conf[]= "#--------- GLOBAL";
            $conf[]= "external_acl_type ldap_group{$ipv4}%LOGIN " . $this->squid_ldap_group_path()." -D \"$ldap_admin\" -w \"$ldap_password\" -b \"$suffix\"  -f \"$group_filter\" -S -v 3 -h $ldap_host -p $ldap_port";

            $conf[]= "auth_param basic children ".$this->auth_param_basic_children();
            $conf[]= "auth_param basic realm $auth_banner";
            $conf[]=$this->auth_params_basic_options();
            $conf[]= "acl ldapauth proxy_auth REQUIRED";
            $conf[]="";
        }else{
            $conf[]= "#No LDAP auth_param basic program found !\n";
            $conf[]="";
        }

        return implode("\n",$conf);

    }



    private function negotiate_ntlm_auth(){
        $SQUID_NTLM_AUTH=$this->SQUID_NTLM_AUTH;
        $LDAP_NONTLM_DOMAIN=null;
        $basicNTLMDomain=null;

        $WINDOWS_DNS_SUFFIX=strtoupper($this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]);
        if(isset($this->KerbAuthInfos["LDAP_NONTLM_DOMAIN"])){ $LDAP_NONTLM_DOMAIN=strtoupper(trim($this->KerbAuthInfos["LDAP_NONTLM_DOMAIN"])); }

        if($WINDOWS_DNS_SUFFIX<>null){
            $basicNTLMDomain=" --domain=$WINDOWS_DNS_SUFFIX";
        }
        if($LDAP_NONTLM_DOMAIN=="NONE"){$basicDomain=null;$LDAP_NONTLM_DOMAIN=null;}
        return "{$SQUID_NTLM_AUTH} $basicNTLMDomain --helper-protocol=squid-2.5-ntlmssp";

    }




    private function ntlm_pure_conf(){
        $negotiate_ntlm_auth=$this->negotiate_ntlm_auth();
        $conf[]= "auth_param ntlm program $negotiate_ntlm_auth";
        $conf[]= "auth_param ntlm ".$this->ntlm_auth_childs();
        $conf[]= "auth_param ntlm keep_alive ".$this->ntlm_auth_keepalive();
        $conf[]= "";
        return @implode("\n", $conf);
    }
    private function squid_ldap_group_path():string{
        if($this->SquidUrgency==1){return "";}
        $basic=$this->squid_ldap_auth_path();
        if(strlen($basic)==0){return  "";}
        $basic=dirname($basic);
        if(is_file("$basic/squid_ldap_group")){return "$basic/squid_ldap_group";}
        if(is_file("$basic/ext_ldap_group_acl")){return "$basic/ext_ldap_group_acl";}
        return  "";
    }

    private function squid_ldap_auth_path(){
        if(is_file('/lib/squid3/basic_ldap_auth')){return '/lib/squid3/basic_ldap_auth';}
        if(is_file('/usr/lib/squid3/squid_ldap_auth')){return '/usr/lib/squid3/squid_ldap_auth';}
        if(is_file('/usr/lib64/squid3/squid_ldap_auth')){return '/usr/lib64/squid3/squid_ldap_auth';}
        if(is_file('/lib/squid3/squid_ldap_auth')){return '/lib/squid3/squid_ldap_auth';}
        if(is_file('/lib64/squid3/squid_ldap_auth')){return '/lib64/squid3/squid_ldap_auth';}
        if(is_file('/usr/lib/squid/ldap_auth')){return '/usr/lib/squid/ldap_auth';}
        if(is_file('/usr/lib/squid/squid_ldap_auth')){return '/usr/lib/squid/squid_ldap_auth';}
        if(is_file('/usr/lib64/squid/squid_ldap_auth')){return '/usr/lib64/squid/squid_ldap_auth';}
        if(is_file('/usr/lib64/squid/ldap_auth')){return '/usr/lib64/squid/ldap_auth';}
        if(is_file('/usr/local/lib/squid/ldap_auth')){return '/usr/local/lib/squid/ldap_auth';}
        if(is_file('/usr/local/lib64/squid/ldap_auth')){return '/usr/local/lib64/squid/ldap_auth';}
        if(is_file('/opt/artica/libexec/squid_ldap_auth')){return '/opt/artica/libexec/squid_ldap_auth';}
        return null;
    }









    private function ntlm_auth_childs():string{
        if(isset($GLOBALS["ntlm_auth_childs"])){return $GLOBALS["ntlm_auth_childs"];}




        if(!isset($this->SquidClientParams["auth_param_ntlm_children"])){$this->SquidClientParams["auth_param_ntlm_children"]=20;}
        if(!isset($this->SquidClientParams["auth_param_ntlm_startup"])){$this->SquidClientParams["auth_param_ntlm_startup"]=5;}
        if(!isset($this->SquidClientParams["auth_param_ntlm_idle"])){$this->SquidClientParams["auth_param_ntlm_idle"]=1;}
        if(!isset($this->SquidClientParams["auth_param_ntlmgroup_children"])){$this->SquidClientParams["auth_param_ntlmgroup_children"]=15;}
        if(!isset($this->SquidClientParams["auth_param_ntlmgroup_startup"])){$this->SquidClientParams["auth_param_ntlmgroup_startup"]=1;}
        if(!isset($this->SquidClientParams["auth_param_ntlmgroup_idle"])){$this->SquidClientParams["auth_param_ntlmgroup_idle"]=1;}

        if(!is_numeric($this->SquidClientParams["auth_param_ntlmgroup_children"])){$this->SquidClientParams["auth_param_ntlmgroup_children"]=15;}
        if(!is_numeric($this->SquidClientParams["auth_param_ntlmgroup_startup"])){$this->SquidClientParams["auth_param_ntlmgroup_startup"]=1;}
        if(!is_numeric($this->SquidClientParams["auth_param_ntlmgroup_idle"])){$this->SquidClientParams["auth_param_ntlmgroup_idle"]=1;}

        if($this->SquidClientParams["auth_param_ntlmgroup_startup"]>$this->SquidClientParams["auth_param_ntlmgroup_children"]){
            $this->SquidClientParams["auth_param_ntlmgroup_startup"]=$this->SquidClientParams["auth_param_ntlmgroup_children"];
        }
        if($this->SquidClientParams["auth_param_ntlmgroup_idle"]>$this->SquidClientParams["auth_param_ntlmgroup_children"]){
            $this->SquidClientParams["auth_param_ntlmgroup_idle"]=1;
        }

        $auth_param_ntlm_children=intval($this->SquidClientParams["auth_param_ntlm_children"]);
        $auth_param_ntlm_startup=intval($this->SquidClientParams["auth_param_ntlm_startup"]);
        $auth_param_ntlm_idle=intval($this->SquidClientParams["auth_param_ntlm_idle"]);

        if($auth_param_ntlm_children==0){$auth_param_ntlm_children=20;}
        if($auth_param_ntlm_startup==0){$auth_param_ntlm_startup=5;}
        if($auth_param_ntlm_idle==0){$auth_param_ntlm_idle=1;}
        if($auth_param_ntlm_idle>=$auth_param_ntlm_startup){$auth_param_ntlm_idle=round($auth_param_ntlm_startup/2);}

        $queuesize = $auth_param_ntlm_children * 4;

        $childs[]="children $auth_param_ntlm_children";
        $childs[]="startup=$auth_param_ntlm_startup";
        $childs[]="idle=$auth_param_ntlm_idle";
        $childs[]="concurrency=0";
        $childs[]="queue-size=$queuesize";
        $childs[]="on-persistent-overload=ERR";
        $auth_param_child=@implode(" ",$childs);
        $GLOBALS["ntlm_auth_childs"]= "$auth_param_child";
        return $auth_param_child;
    }

    private function ntlm_auth_keepalive(){

        $keep_alive="off";
        if($this->SquidNTLMKeepAlive==1){$keep_alive="on";}
        return $keep_alive;

    }

    private function negotiate_kerberos_auth(){
        if(!is_file("/etc/squid3/krb5.keytab")){return null;}
        $LockActiveDirectoryToKerberos= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}

        $WINDOWS_DNS_SUFFIX=strtoupper($this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]);
        $ActiveDirectorySquidHTTPHostname=$this->ActiveDirectorySquidHTTPHostname;
        $myFullHostname=$this->hostname_g();


        @chown("/etc/squid3/krb5.keytab","squid");
        @chgrp("/etc/squid3/krb5.keytab","squid");
        @chmod("/etc/squid3/krb5.keytab",0755);



        if(!is_file("/lib/squid3/negotiate_kerberos_auth")){
            if($GLOBALS["VERBOSE"]){echo "!!!! FATAL /lib/squid3/negotiate_kerberos_auth No such file !!!\n";}
            $GLOBALS["negotiate_kerberos_auth"]="# FATAL /lib/squid3/negotiate_kerberos_auth No such file";
            return null;

        }

        if($ActiveDirectorySquidHTTPHostname<>null){$myFullHostname=$ActiveDirectorySquidHTTPHostname;}


        $f[]="/lib/squid3/negotiate_kerberos_auth";
        $f[]="-t none";
        if($LockActiveDirectoryToKerberos==0) {
            $f[] = "-r -s HTTP/$myFullHostname@$WINDOWS_DNS_SUFFIX";
        }else{
            $f[] = "-r -s GSS_C_NO_NAME";
        }
        $f[]="-k /etc/squid3/krb5.keytab";
        return @implode(" ",$f);
    }

    private function hostname_g():string{

        if(is_file("/etc/artica-postfix/settings/Daemons/myhostname")){
            $results=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
            if(strlen($results)>2){return $results;}
        }


        $uname=posix_uname();
        $domainname=trim($uname["domainname"]);
        if($domainname=="(none)"){$domainname="localhost.localdomain";}

        $hostname=trim($uname["nodename"]);
        if(strpos($hostname, ".")==0) {
            if($domainname<>null){$hostname=trim("$hostname.$domainname");}
        }
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("myhostname", $hostname);
        return trim($hostname);
    }


    private function auth_param_basic_children():int{

        if(isset($GLOBALS["auth_param_basic_children"])){
            if(is_numeric($GLOBALS["auth_param_basic_children"])){
                return $GLOBALS["auth_param_basic_children"];
            }
        }

        $SquidClientParams=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));

        if(!isset($SquidClientParams["auth_param_basic_children"])){$SquidClientParams["auth_param_basic_children"]=3;}
        if(!isset($SquidClientParams["auth_param_basic_startup"])){$SquidClientParams["auth_param_basic_startup"]=2;}
        if(!isset($SquidClientParams["auth_param_basic_idle"])){$SquidClientParams["auth_param_basic_idle"]=1;}
        if(intval($SquidClientParams["auth_param_basic_idle"])==0){$SquidClientParams["auth_param_basic_idle"]=1;}


        $auth_param_basic_children=intval($SquidClientParams["auth_param_basic_children"]);
        $auth_param_basic_startup=intval($SquidClientParams["auth_param_basic_startup"]);
        $auth_param_basic_idle=intval($SquidClientParams["auth_param_basic_idle"]);
        if($auth_param_basic_idle==0){$auth_param_basic_idle=1;}
        if($auth_param_basic_startup==0){$auth_param_basic_startup=2;}
        if($auth_param_basic_children==0){$auth_param_basic_children=3;}

        $auth_param_child="$auth_param_basic_children startup=$auth_param_basic_startup idle=$auth_param_basic_idle";
        $GLOBALS["auth_param_basic_children"]=$auth_param_child;
        return intval($auth_param_child);

    }





    private function ntlm_params_defaults(){
        if(!isset($this->SquidClientParams["external_acl_children"])){$this->SquidClientParams["external_acl_children"]=5;}
        if(!isset($this->SquidClientParams["external_acl_startup"])){$this->SquidClientParams["external_acl_startup"]=5;}
        if(!isset($this->SquidClientParams["external_acl_idle"])){$this->SquidClientParams["external_acl_idle"]=1;}
        if(!isset($this->SquidClientParams["url_rewrite_idle"])){$this->SquidClientParams["url_rewrite_idle"]=1;}
        if(!isset($this->SquidClientParams["authenticate_ttl"])){$this->SquidClientParams["authenticate_ttl"]=14400;}
        if(!isset($this->SquidClientParams["authenticate_ip_ttl"])){$this->SquidClientParams["authenticate_ip_ttl"]=14400;}
        if(!isset($this->SquidClientParams["credentialsttl"])){$this->SquidClientParams["credentialsttl"]=14400;}
        if(!isset($this->SquidClientParams["authenticate_cache_garbage_interval"])){$this->SquidClientParams["authenticate_cache_garbage_interval"]=18000;}


        if(!isset($this->SquidClientParams["auth_param_ntlm_children"])){$this->SquidClientParams["auth_param_ntlm_children"]=20;}
        if(!isset($this->SquidClientParams["auth_param_ntlm_startup"])){$this->SquidClientParams["auth_param_ntlm_startup"]=0;}
        if(!isset($this->SquidClientParams["auth_param_ntlm_idle"])){$this->SquidClientParams["auth_param_ntlm_idle"]=1;}

        if(!isset($this->SquidClientParams["auth_param_basic_children"])){$this->SquidClientParams["auth_param_basic_children"]=3;}
        if(!isset($this->SquidClientParams["auth_param_basic_startup"])){$this->SquidClientParams["auth_param_basic_startup"]=2;}
        if(!isset($this->SquidClientParams["auth_param_basic_idle"])){$this->SquidClientParams["auth_param_basic_idle"]=1;}


        if(!is_numeric($this->SquidClientParams["auth_param_ntlm_children"])){$this->SquidClientParams["auth_param_ntlm_children"]=20;}
        if(!is_numeric($this->SquidClientParams["auth_param_ntlm_startup"])){$this->SquidClientParams["auth_param_ntlm_startup"]=0;}
        if(!is_numeric($this->SquidClientParams["auth_param_ntlm_idle"])){$this->SquidClientParams["auth_param_ntlm_idle"]=1;}

        if(!is_numeric($this->SquidClientParams["auth_param_basic_children"])){$this->SquidClientParams["auth_param_basic_children"]=3;}
        if(!is_numeric($this->SquidClientParams["auth_param_basic_startup"])){$this->SquidClientParams["auth_param_basic_startup"]=2;}
        if(!is_numeric($this->SquidClientParams["auth_param_basic_idle"])){$this->SquidClientParams["auth_param_basic_idle"]=1;}
        if(!is_numeric($this->SquidClientParams["external_acl_children"])){$this->SquidClientParams["external_acl_children"]=5;}
        if(!is_numeric($this->SquidClientParams["external_acl_startup"])){$this->SquidClientParams["external_acl_startup"]=5;}
        if(!is_numeric($this->SquidClientParams["external_acl_idle"])){$this->SquidClientParams["external_acl_idle"]=1;}
        if(!is_numeric($this->SquidClientParams["url_rewrite_idle"])){$this->SquidClientParams["url_rewrite_idle"]=1;}

        if(!isset($this->SquidClientParams["external_acl_idle"])){$this->SquidClientParams["external_acl_idle"]=1;}
        if(intval($this->SquidClientParams["authenticate_cache_garbage_interval"])==0){$this->SquidClientParams["authenticate_cache_garbage_interval"]=18000;}
        if(intval($this->SquidClientParams["authenticate_ttl"])==0){$this->SquidClientParams["authenticate_ttl"]=14400;}
        if(intval($this->SquidClientParams["authenticate_ip_ttl"])==0){$this->SquidClientParams["authenticate_ip_ttl"]=$this->SquidClientParams["authenticate_ttl"];}



        if($this->SquidClientParams["authenticate_ttl"]>$this->SquidClientParams["authenticate_cache_garbage_interval"]){
            $this->SquidClientParams["authenticate_cache_garbage_interval"]=$this->SquidClientParams["authenticate_ttl"];
        }
        if(intval($this->SquidClientParams["credentialsttl"])==0){
            $this->SquidClientParams["credentialsttl"]=$this->SquidClientParams["authenticate_ttl"];
        }
    }

}