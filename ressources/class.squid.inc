<?php


if(!isset($GLOBALS["AS_ROOT"])){$GLOBALS["AS_ROOT"]=false;}
include_once(dirname(__FILE__).'/class.sqlite.inc');
include_once(dirname(__FILE__).'/class.dansguardian.inc');
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__).'/class.sockets.inc');
include_once(dirname(__FILE__).'/class.samba.inc');
include_once(dirname(__FILE__)."/class.c-icap-filter.inc");
include_once(dirname(__FILE__)."/class.icap.inc");
include_once(dirname(__FILE__)."/class.squid.bandwith.inc");
include_once(dirname(__FILE__)."/class.computers.inc");
include_once(dirname(__FILE__)."/class.squid.acls.inc");
include_once(dirname(__FILE__)."/class.ecap.inc");
include_once(dirname(__FILE__)."/class.system.network.inc");
include_once(dirname(__FILE__)."/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/class.squid.acls.useragents.inc");
include_once(dirname(__FILE__)."/class.squid.booster.inc");
include_once(dirname(__FILE__)."/class.squidworkers.inc");
include_once(dirname(__FILE__)."/class.system.nics.inc");
include_once(dirname(__FILE__)."/class.squid.caches.rules.inc");
include_once(dirname(__FILE__)."/class.products-ip-ranges.inc");
include_once(dirname(__FILE__)."/class.tcpip.inc");
include_once(dirname(__FILE__)."/class.compile.ufdbguard.inc");
include_once(dirname(__FILE__)."/class.squid.access.manager.inc");
include_once(dirname(__FILE__)."/class.squid.external.ldap.inc");
include_once(dirname(__FILE__)."/class.squid.ssl.inc");
include_once(dirname(__FILE__)."/class.squid.hypercache.inc");
include_once(dirname(__FILE__)."/class.squid.checks.inc");
include_once(dirname(__FILE__)."/class.squid.refresh_patterns.inc");
include_once(dirname(__FILE__)."/class.squid.ntlm.inc");
include_once(dirname(__FILE__)."/class.squid.externals.acls.childrens.inc");
include_once(dirname(__FILE__)."/class.squid.ports.inc");
include_once(dirname(__FILE__)."/class.squid.url_rewrite.inc");
include_once(dirname(__FILE__)."/class.squid.parents.inc");
include_once(dirname(__FILE__)."/class.squid.ftp.inc");
include_once(dirname(__FILE__)."/class.squid.common.inc");


class squid{


}
class squidbee{
    public $CURRENT_PROGRESS=0;
    private $CORP_LICENSE=false;
    public $MAX_PROGRESS=0;
    private $IS_VERS_LOGS=null;
    public $dn;
    public $network_array=array();
    public $dns_array=array();
    public $squid_conf;
    public $ArticaSquidParameters;
    private $SquidReverseDefaultWebSite=null;
    private $EnableSquidSSLCRTD=0;
    public $ldap_error;
    public $listen_port;
    public $second_listen_port=0;
    public $smartphones_port=0;
    public $visible_hostname;
    public $enable_kavproxy;
    public $enable_cicap;
    public $enable_dansguardian;
    public $enable_squidguard;
    public $enable_UfdbGuard;
    public $LDAP_AUTH=0;
    public $NTLM_AUTH=0;
    public $SSL_BUMP=0;
    public $EnableClamavInCiCap=0;
    public $alt_listen_port;
    public $kav_accept=false;
    private $AdminAsSeenNTLMPerfs=0;
    public $kav_accept_why='';
    public $SquidBlockSites='';
    public $array_block_istes=array();
    public $global_conf_array=array();
    public $cache_list=array();
    public $acl_times=array();
    public $hasProxyTransparent;
    private $SquidUsersNumber=0;
    public $EnableTransparent27=0;
    public $ASROOT=false;

    public $sslbump_token_debug=array();
    public $SQUID_ICAP_ENABLED=false;
    public $SQUID_VERSION="";

    public $LDAP_EXTERNAL_AUTH=0;
    public $IS_27=false;
    public $IS_30=false;
    public $IS_31=false;
    public $IS_32=false;
    public $IS_33=false;
    public $IS_34=false;
    public $IS_35=false;
    public $IS_40=false;
    public $IS_50=false;
    public $intvalVersion=false;
    public $ICAP_SERVICES_COUNT=0;
    public $FTP_PARAMS=array();
    public $wccp2_enabled=0;
    public $SquidWCCPL3Enabled=0;
    public $wccp2_router=null;
    public $wccp2_forwarding_method=1;
    public $wccp2_return_method=1;
    public $wccp2_assignment_method="hash";

    public $EnableUserAgentBanAll=0;
    public $enable_adzapper=0;
    public $enable_squidclamav=0;
    public $enable_metascanner=0;
    public $enable_streamcache=0;
    public $EnableChangeRequestSize=0;
    public $EnableKerbAuth=0;
    public $EnableKavICAPRemote=0;
    public $KavICAPRemoteAddr=null;
    public $KavICAPRemotePort=0;
    public $SquidDisableAllFilters=0;



    public $enable_ecapav=0;
    public $CPU_NUMBER;
    public $uuid;
    public $EnableICPPort=0;
    public $UseTProxyMode=0;
    public $store_dir_minsize=null;
    public $SquidBoosterOnly=0;
    public $ssl_port=0;
    public $certificate_center=null;
    public $second_listen_portForTransparent=0;
    public $EnableKerberosAuthentication=0;
    public $EnableSquidCSV=0;
    public $SquidActHasReverse=0;
    public $EnableSplashScreen=0;
    public $AsSquidLoadBalancer=0;
    Public $LogsWarninStop=0;


    public $snmp_community=null;
    public $snmp_enable=0;
    public $snmp_port=3401;
    public $snmp_access_ip=null;
    public $SquidLdapAuthEnableGroups=0;
    public $EnableRemoteStatisticsAppliance=0;
    private $TRANSPARENT_PORTS_LIST=array();

    private $ProxyDedicateMicrosoftRules=0;
    private $AntivirusEnabledCentral=1;
    private $UfdbEnabledCentral=1;

    private $EnableMacAddressFilterCentral=1;
    private $EnableMacAddressFilter=1;
    private $SquidBubbleMode=0;
    private $MyPortsArray=array();
    private $MyStandPort=null;
    private $SquidClientParams=array();
    private $ForceWindowsUpdateCaching=0;
    private $MGR_LISTEN_PORT=0;
    private $ChilliEnabled=false;
    private $DisableAnyCache=0;
    private $SquidCachesProxyEnabled=0;
    private $DisableSSLStandardPort=0;
    public $SquidBinIpaddr=null;
    private $WCCPListenPort=0;
    private $SquidUrgency=0;
    private $BasicAuthenticatorEmergency=0;
    private $CacheManagement2=1;
    private $ArticaHotSpotPort=0;
    private $ArticaSSLHotSpotPort=0;
    private $UtDNSEnable=0;
    private $SquidEnableIdentdService=0;
    private $SquidEnableIdentdServiceOnly=0;
    private $SquidSimpleConfig=0;
    private $KerbAuthInfos=array();
    private $isSquid5=false;



    private $SQUID_REVERSE_APPLIANCE;
    private $SquidExternalAclQuota=0;
    private $CnTLMPORT=0;
    private $EnableCNTLM=0;
    private $second_port_transparent=null;
    private $transparent=null;
    private $DisableWinbindd;
    private $WCCPHash;
    private $SQUID_VERSION_CALC;
    private $SQUID_VERSIONS_LOGS=array();
    private $LockActiveDirectoryToKerberos=0;

    public $nodes_names=array();

    function __construct(){

        if(function_exists("posix_getuid")) { if (posix_getuid() == 0) { $this->ASROOT = true; } }else{ $this->ASROOT = true; }
        $GLOBALS["VERBOSED"]=false;
        if(!$this->ASROOT){if($GLOBALS["VERBOSE"]){$GLOBALS["VERBOSED"]=true;}}
        $this->VerifyLdapBranch();
        if($GLOBALS["VERBOSED"]){echo __LINE__.":: ->ParseConfig()<br>\n";}
        $this->ParseConfig();

        $sock=new sockets();
        $this->uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
        if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
        $users=$GLOBALS["CLASS_USERS"];
        $this->CPU_NUMBER=$users->CPU_NUMBER;

        if($this->ASROOT){
            $this->SQUID_VERSION=$this->root_squid_version_nocache();
            $this->SQUID_VERSION_CALC="As root";
        }else{$this->SQUID_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion");}
        $this->SQUID_ICAP_ENABLED=$users->SQUID_ICAP_ENABLED;


        $this->CORP_LICENSE=$users->CORP_LICENSE;
        $this->enable_UfdbGuard=$this->EnableUfdbGuard();
        $this->enable_adzapper=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAdZapper");
        $this->enable_squidclamav=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidClamav");
        $this->EnableKerbAuth=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth");
        $this->LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){$this->LockActiveDirectoryToKerberos=1;}
        if($this->LockActiveDirectoryToKerberos==1){$this->EnableKerbAuth=1;}
        $this->EnableKavICAPRemote=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKavICAPRemote");
        $this->KavICAPRemoteAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KavICAPRemoteAddr");
        $this->KavICAPRemotePort=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KavICAPRemotePort");
        $this->enable_metascanner=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KavMetascannerEnable");
        $this->enable_streamcache=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidEnableStreamCache");
        $this->SquidDisableAllFilters=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableAllFilters");
        $this->enable_ecapav=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquideCapAVEnabled");
        $this->UseTProxyMode=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseTProxyMode");
        $this->EnableSquidCSV=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidCSV");
        $this->EnableKerberosAuthentication=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerberosAuthentication");
        $this->EnableSplashScreen=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSplashScreen");
        $this->SquidActHasReverse=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidActHasReverse");
        $this->AsSquidLoadBalancer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("AsSquidLoadBalancer");
        $this->UfdbEnabledCentral=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbEnabledCentral");
        $this->AntivirusEnabledCentral=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("AntivirusEnabledCentral");

        $this->EnableMacAddressFilter=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMacAddressFilter");
        $this->EnableMacAddressFilterCentral=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMacAddressFilterCentral");
        $this->SquidBubbleMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidBubbleMode"));
        $this->LogsWarninStop=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsWarninStop"));
        $this->AdminAsSeenNTLMPerfs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AdminAsSeenNTLMPerfs"));
        $this->SquidClientParams=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));
        $this->ForceWindowsUpdateCaching=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ForceWindowsUpdateCaching");
        $this->SquidReverseDefaultWebSite=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidReverseDefaultWebSite");
        $this->SquidExternalAclQuota=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidExternalAclQuota");
        $this->DisableSSLStandardPort=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableSSLStandardPort");

        $this->smartphones_port=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("smartphones_port");
        $this->EnableSquidSSLCRTD=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidSSLCRTD");

        $this->DisableWinbindd=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableWinbindd");
        $this->SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
        $this->BasicAuthenticatorEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BasicAuthenticatorEmergency"));
        $this->ProxyDedicateMicrosoftRules=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyDedicateMicrosoftRules");
        $this->WCCPListenPort=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCPListenPort");
        $this->CacheManagement2=1;
        $this->UtDNSEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UtDNSEnable"));
        $this->SquidEnableIdentdService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidEnableIdentdService"));
        $this->SquidEnableIdentdServiceOnly=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidEnableIdentdServiceOnly"));
        $this->SquidSimpleConfig=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSimpleConfig");
        $this->SquidUsersNumber=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUsersNumber"));
        if(!is_numeric($this->SquidSimpleConfig)){$this->SquidSimpleConfig=1;}
        $this->KerbAuthInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));


        if(!is_numeric($this->ProxyDedicateMicrosoftRules)){$this->ProxyDedicateMicrosoftRules=0;}
        if(!is_numeric($this->DisableWinbindd)){$this->DisableWinbindd=0;}
        if($this->KavICAPRemoteAddr==null){$this->EnableKavICAPRemote=0;}
        if(!is_numeric($this->SquidExternalAclQuota)){$this->SquidExternalAclQuota=0;}
        if(!is_numeric($this->DisableSSLStandardPort)){$this->DisableSSLStandardPort=1;}
        if(!is_numeric($this->KavICAPRemotePort)){$this->EnableKavICAPRemote=0;}
        if(!is_numeric($this->AsSquidLoadBalancer)){$this->AsSquidLoadBalancer=0;}
        if(!is_numeric($this->SquidActHasReverse)){$this->SquidActHasReverse=0;}
        if(!is_numeric($this->EnableKavICAPRemote)){$this->EnableKavICAPRemote=0;}
        if(!is_numeric($this->EnableKerbAuth)){$this->EnableKerbAuth=0;}
        if(!is_numeric($this->SquidBubbleMode)){$this->SquidBubbleMode=0;}
        if(!is_numeric($this->enable_metascanner)){$this->enable_metascanner=0;}
        if(!is_numeric($this->SquidDisableAllFilters)){$this->SquidDisableAllFilters=0;}
        if(!is_numeric($this->UseTProxyMode)){$this->UseTProxyMode=0;}
        if(!is_numeric($this->EnableSquidCSV)){$this->EnableSquidCSV=0;}
        if(!is_numeric($this->UfdbEnabledCentral)){$this->UfdbEnabledCentral=1;}
        if(!is_numeric($this->AntivirusEnabledCentral)){$this->AntivirusEnabledCentral=1;}
        if(!is_numeric($this->EnableKerberosAuthentication)){$this->EnableKerberosAuthentication=0;}
        if(!is_numeric($this->EnableSplashScreen)){$this->EnableSplashScreen=0;}
        if(!is_numeric($this->EnableMacAddressFilter)){$this->EnableMacAddressFilter=1;}
        if(!is_numeric($this->EnableMacAddressFilterCentral)){$this->EnableMacAddressFilterCentral=1;}

        if(!is_numeric($this->smartphones_port)){$this->smartphones_port=0;}
        if(!is_numeric($this->EnableSquidSSLCRTD)){$this->EnableSquidSSLCRTD=1;}
        if(!is_numeric($this->CacheManagement2)){$this->CacheManagement2=1;}
        if(!is_numeric($this->UtDNSEnable)){$this->UtDNSEnable=0;}



        $SquidVersion=$this->GET_SQUID_VERSION();
        $this->SQUID_VERSION=$SquidVersion;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidVersion",$SquidVersion);
        if(preg_match("#^(5|6|7|8|9)\.#",$SquidVersion)){$this->isSquid5=true;}
        if($this->EnableKerberosAuthentication==1){$this->EnableKerbAuth=0;}

        if(!is_numeric($this->SquidUrgency)){$this->SquidUrgency=0;}
        if(!isset($this->KerbAuthInfos["LDAP_NONTLM_DOMAIN"])){$this->KerbAuthInfos["LDAP_NONTLM_DOMAIN"]=null;}
        if(!isset($this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"])){$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]=null;}

        if(!isset($this->SquidClientParams["auth_param_basic_children"])){$this->SquidClientParams["auth_param_basic_children"]=3;}
        if(!isset($this->SquidClientParams["auth_param_basic_startup"])){$this->SquidClientParams["auth_param_basic_startup"]=1;}
        if(!isset($this->SquidClientParams["auth_param_basic_idle"])){$this->SquidClientParams["auth_param_basic_idle"]=1;}

        if(!isset($this->SquidClientParams["external_acl_children"])){$this->SquidClientParams["external_acl_children"]=5;}
        if(!isset($this->SquidClientParams["external_acl_startup"])){$this->SquidClientParams["external_acl_startup"]=1;}
        if(!isset($this->SquidClientParams["external_acl_idle"])){$this->SquidClientParams["external_acl_idle"]=1;}


        if(!is_numeric($this->SquidClientParams["auth_param_basic_children"])){$this->SquidClientParams["auth_param_basic_children"]=3;}
        if(!is_numeric($this->SquidClientParams["auth_param_basic_startup"])){$this->SquidClientParams["auth_param_basic_startup"]=2;}
        if(!is_numeric($this->SquidClientParams["auth_param_basic_idle"])){$this->SquidClientParams["auth_param_basic_idle"]=1;}



        if(!is_numeric($this->SquidClientParams["external_acl_children"])){$this->SquidClientParams["external_acl_children"]=5;}
        if(!is_numeric($this->SquidClientParams["external_acl_startup"])){$this->SquidClientParams["external_acl_startup"]=1;}
        if(!is_numeric($this->SquidClientParams["external_acl_idle"])){$this->SquidClientParams["external_acl_idle"]=1;}
        if($this->SquidClientParams["external_acl_startup"]<1){$this->SquidClientParams["external_acl_startup"]=2;}

        //Disable StreamCache Patch 2012-11-20
        $this->enable_streamcache=0;
        $this->WCCPHash=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCPHash"));

        if( (!is_numeric($this->WCCPListenPort)) OR ($this->WCCPListenPort<1024) ) {
            $this->WCCPListenPort=rand(18999, 65550);
            $sock->SET_INFO("WCCPListenPort", $this->WCCPListenPort);
        }


        $users=new usersMenus();
        if($users->SQUID_REVERSE_APPLIANCE){
            $this->SquidActHasReverse=1;
            $this->ssl_port=443;
            $this->SSL_BUMP=1;
        }

        $wks=new squidworkers();


        $this->CheckVersion();
        $this->hasProxyTransparent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("hasProxyTransparent"));
        $this->EnableTransparent27=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTransparent27"));


        $this->SquidBoosterOnly=$wks->zcheck_squidbooster_value();
        if($this->SquidActHasReverse==1){$this->enable_UfdbGuard=0;}



        if(!$this->IS_33){
            if($this->hasProxyTransparent==1){$this->EnableSquidSSLCRTD=0;}
        }
        if($this->SSL_BUMP==1){
            if($this->ssl_port==0){
                $this->ssl_port=$this->get_ssl_port();
            }
        }


        // Urgency ********************* always at end.
        if($this->SquidUrgency==1){
            $this->SquidEnableIdentdService=0;
            $this->enable_UfdbGuard=0;
            $this->DisableAnyCache=1;
            $this->SquidBubbleMode=0;
            $this->SquidDisableAllFilters=1;
        }
        // Urgency ********************* always at end.
        if($this->SquidEnableIdentdService==0){$this->SquidEnableIdentdServiceOnly=0;}


    }
    private function EnableUfdbGuard(){
        $sock=new sockets();
        $LogsWarninStop=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsWarninStop");
        if($LogsWarninStop==1){return 0;}
        if($this->EnableTransparent27==1){return 0;}
        return $sock->EnableUfdbGuard();

    }
    private function compilation_params(){
        if(!$this->ASROOT){return null;}
        if(isset($GLOBALS["COMPILE_SQUID_TOKENS"])){return $GLOBALS["COMPILE_SQUID_TOKENS"];}
        if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
        $squidbin=$unix->find_program("squid");
        if($squidbin==null){$squidbin=$unix->find_program("squid3");}
        exec("$squidbin -v 2>&1",$results);
        $text=@implode("\n", $results);
        if(preg_match("#configure options:\s+(.+)#is", $text,$re)){$text=$re[1];}
        if(preg_match_all("#'(.+?)'#is", $text, $re)){
            foreach ($re[1] as $index=>$line){
                if(preg_match("#(.+?)=(.*)#", $line,$ri)){
                    $key=$ri[1];
                    $value=$ri[2];
                    $key=str_replace("--", "", $key);
                    if(trim($value)==null){$value=true;}
                    $GLOBALS["COMPILE_SQUID_TOKENS"][$key]=$value;
                    continue;
                }
                $key=$line;
                $value=1;
                $key=str_replace("--", "", $key);
                if($GLOBALS["VERBOSE"]){echo "squid -v [$key] = `$value`\n";}
                $GLOBALS["COMPILE_SQUID_TOKENS"][$key]=$value;
            }

        }
        return $GLOBALS["COMPILE_SQUID_TOKENS"];
    }
    private function log_access_acls(){

        $f[]="#\t LOGS CENTER";
        $f[]="#\t LogsWarninStop =  $this->LogsWarninStop; SquidUrgency=$this->SquidUrgency";
        if($this->LogsWarninStop==1){return @implode("\n", $f);}
        if($this->SquidUrgency==1){return @implode("\n", $f);}
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $results=$q->QUERY_SQL("SELECT * FROM squid_logs_acls  WHERE enabled=1 ORDER BY zorder");
        if(!$q->ok){$f[]="#$q->mysql_error";}
        $squid_acls_groups=new squid_acls_groups();

        foreach ($results as $index=>$ligne){
            $rulename=$ligne["rulename"];
            $aclid=$ligne["aclid"];
            $logtype=$ligne["logtype"];
            $logconfig=unserialize(base64_decode($ligne["logconfig"]));
            if(!isset($logconfig["LOGFILENAME"])){$logconfig["LOGFILENAME"]=null;}
            if(!isset($logconfig["SYSLOG_FACILITY"])){$logconfig["SYSLOG_FACILITY"]=null;}
            $LOGFILENAME=$logconfig["LOGFILENAME"];
            if($LOGFILENAME==null){$LOGFILENAME="access{$aclid}.log";}
            if($logconfig["SYSLOG_FACILITY"]==null){$logconfig["SYSLOG_FACILITY"]="local6";}

            $f[]="# ($aclid): $rulename LOG TYPE:$logtype";

            $acls_array=$squid_acls_groups->buildacls_bytype_items($aclid,false,"logs_sqacllinks");
            $f[]=@implode("\n", $squid_acls_groups->buildacls_bytype_logs);
            if(count($acls_array)==0){continue;}

            if($logtype==1){
                $FINAL=@implode(" ", $acls_array);
                $FINAL=str_replace("!!", "!", $FINAL);
                $f[]="access_log none $FINAL";
                continue;

            }

            if($logtype==0){
                $f[]="access_log stdio:/var/log/squid/$LOGFILENAME logformat=squid buffer-size=64KB on-error=drop ".@implode(" ", $acls_array);
                continue;
            }

            if($logtype==3){
                $f[]="access_log syslog:{$logconfig["SYSLOG_FACILITY"]}.info logformat=squid buffer-size=64KB on-error=drop ".@implode(" ", $acls_array);
                continue;
            }

        }

        return @implode("\n", $f);



    }
    private function log_access_acls_csv(){
        if($this->LogsWarninStop==1){return null;}
        if($this->SquidUrgency==1){return null;}
        $f[]="#\t CVS log rules";
        $MACFILER="%>eui";
        $CLIENTSOURCE="%<a";
        if($this->EnableMacAddressFilter==0){$MACFILER='00:00:00:00:00:00';}
        if($this->SquidBubbleMode==1){$prefixacls=" MyStandPort ";}
        if(($this->IS_31) && (!$this->IS_32)){$MACFILER='00:00:00:00:00:00';$CLIENTSOURCE="0.0.0.0";}



        $f[]="logformat csv_acls \"%{%Y-%m-%d}tl\";\"%{%H:%M:%S}tl\";\"%>a\";\"%>A\";\"$MACFILER\";\"$CLIENTSOURCE\";\"%<A\";\"%[un\";\"%rm\";\"%ru\";\"%rv\";\"%>Hs\";\"%<st\";\"%Ss:%Sh\";\"%{User-Agent}>h\";\"%{X-Forwarded-For}>h\"";


        $acls=new squid_acls_groups();
        $log_access=$acls->buildacls_bytype("log_access");

        if(count($log_access)>0){
            foreach ($log_access as $line){
                $f[]="access_log $line $prefixacls";
            }
        }

        return @implode("\n", $f)."\n\n";
    }
     private function request_header_add(){
        if($this->SquidUrgency==1){return "#\t Add Headers:\n#\t Urgency enable\n";}
        $f[]="#\t Add Headers:";
        if($this->SquidBubbleMode==1){$prefixacls=" MyStandPort ";}
        $acls=new squid_acls_groups();
        $request_header_add=$acls->buildacls_bytype("request_header_add");
        if(count($request_header_add)>0){
            echo "Starting......: ".date("H:i:s")." [ACLS]: Engine request_header_add ".count($request_header_add)." rules..\n";
            foreach ($request_header_add as $index=>$line){
                $f[]="request_header_add $line";
            }
        }else{
            echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine request_header_add No rules..\n";
        }
        return @implode("\n", $f)."\n\n";

    }
     private function root_squid_version_nocache(){
        if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
        if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
        $unix=new unix();

        $squidbin=$unix->LOCATE_SQUID_BIN();
        if($squidbin==null){$squidbin=trim($unix->find_program("squid3"));}
        if(!is_file($squidbin)){return 0;}
        exec("$squidbin -v 2>&1",$results);

        $this->SQUID_VERSIONS_LOGS[]="# Binary $squidbin";


        foreach ($results as $num=>$val){
            if(preg_match("#Squid Cache: Version.*?([0-9\.]+)#", $val,$re)){
                $this->SQUID_VERSIONS_LOGS[]="# Version {$re[1]}";
                if($re[1]=="2.7."){$re[1]="2.7.0";}
                if($re[1]=="3.0."){$re[1]="3.0.0";}
                if($re[1]=="3.1."){$re[1]="3.1.0";}
                if($re[1]=="3.2."){$re[1]="3.2.0";}
                if($re[1]=="3.3."){$re[1]="3.3.0";}
                if($re[1]=="3.4."){$re[1]="3.4.0";}
                if($re[1]=="3.5."){$re[1]="3.5.0";}

                if(!isset($GLOBALS["NOSQUIDOUTPUT"])){
                    echo "Starting......: ".date("H:i:s")." Squid : Version (as root) '{$re[1]}'\n";
                }
                $GLOBALS[__FUNCTION__]=$re[1];
                return $re[1];
            }
        }
        return 0;
    }
    private function root_squid_version(){return $this->root_squid_version_nocache();	}
    private function CheckVersion(){
        if(!isset($GLOBALS["intvalVersion"])){$GLOBALS["intvalVersion"]=null;}
        if((isset($GLOBALS["SQUID_IS_VERSION"])) && (trim($GLOBALS["intvalVersion"])<>null)   ){
            $this->IS_30=$GLOBALS["SQUID_IS_VERSION"][30];
            $this->IS_31=$GLOBALS["SQUID_IS_VERSION"][31];
            $this->IS_32=$GLOBALS["SQUID_IS_VERSION"][32];
            $this->IS_35=$GLOBALS["SQUID_IS_VERSION"][35];
            $this->IS_40=$GLOBALS["SQUID_IS_VERSION"][40];
            $this->intvalVersion=$GLOBALS["intvalVersion"];
            return;
        }
        $this->intvalVersion=0;
        if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)#',$this->SQUID_VERSION,$re)){
            $this->intvalVersion=intval($re[1]).intval($re[2]).intval($re[3]);
        }
        if($this->intvalVersion==0){
            if(preg_match('#^([0-9]+)\.([0-9]+)#',$this->SQUID_VERSION,$re)){
                $this->intvalVersion=intval($re[1]).intval($re[2]).'0';
            }

        }

        $VERLOGS[]="intvalVersion:$this->intvalVersion";

        if($this->intvalVersion>0){
            $VERLOGS[]="FIRST:{$re[1]};SECOND:{$re[2]}";

            if($re[1]==3){
                if($re[2]==0){
                    $this->IS_30=true;
                    $this->IS_31=false;
                    $this->IS_32=false;
                    $this->IS_33=false;
                    $this->IS_34=false;
                    $GLOBALS["SQUID_IS_VERSION"][30]=true;
                    $GLOBALS["SQUID_IS_VERSION"][31]=false;
                    $GLOBALS["SQUID_IS_VERSION"][32]=false;
                    $GLOBALS["SQUID_IS_VERSION"][33]=false;
                    $GLOBALS["SQUID_IS_VERSION"][34]=false;
                    $GLOBALS["SQUID_IS_VERSION"][35]=false;
                    return;
                }


                if($re[2]==1){
                    $this->IS_30=false;
                    $this->IS_31=true;
                    $this->IS_32=false;
                    $this->IS_33=false;
                    $this->IS_34=false;
                    $GLOBALS["SQUID_IS_VERSION"][30]=false;
                    $GLOBALS["SQUID_IS_VERSION"][31]=true;
                    $GLOBALS["SQUID_IS_VERSION"][32]=false;
                    $GLOBALS["SQUID_IS_VERSION"][33]=false;
                    $GLOBALS["SQUID_IS_VERSION"][34]=false;
                    $GLOBALS["SQUID_IS_VERSION"][35]=false;
                    return;
                }
                if($re[2]==2){
                    $this->IS_30=false;
                    $this->IS_31=true;
                    $this->IS_32=true;
                    $this->IS_33=false;
                    $this->IS_34=false;
                    $GLOBALS["SQUID_IS_VERSION"][30]=false;
                    $GLOBALS["SQUID_IS_VERSION"][31]=true;
                    $GLOBALS["SQUID_IS_VERSION"][32]=true;
                    $GLOBALS["SQUID_IS_VERSION"][33]=false;
                    $GLOBALS["SQUID_IS_VERSION"][34]=false;
                    return;
                }
                if($re[2]==3){
                    $this->IS_30=false;
                    $this->IS_31=false;
                    $this->IS_32=true;
                    $this->IS_33=true;
                    $this->IS_34=false;
                    $GLOBALS["SQUID_IS_VERSION"][30]=false;
                    $GLOBALS["SQUID_IS_VERSION"][31]=false;
                    $GLOBALS["SQUID_IS_VERSION"][32]=true;
                    $GLOBALS["SQUID_IS_VERSION"][33]=true;
                    $GLOBALS["SQUID_IS_VERSION"][34]=false;
                    return;
                }

                if($re[2]==4){
                    $this->IS_30=false;
                    $this->IS_31=false;
                    $this->IS_32=true;
                    $this->IS_33=true;
                    $this->IS_34=true;
                    $GLOBALS["SQUID_IS_VERSION"][30]=false;
                    $GLOBALS["SQUID_IS_VERSION"][31]=false;
                    $GLOBALS["SQUID_IS_VERSION"][32]=true;
                    $GLOBALS["SQUID_IS_VERSION"][33]=true;
                    $GLOBALS["SQUID_IS_VERSION"][34]=true;
                    return;
                }
                if($re[2]>=5){
                    $VERLOGS[]="->3.5x";
                    $this->IS_30=false;
                    $this->IS_31=false;
                    $this->IS_32=true;
                    $this->IS_33=true;
                    $this->IS_34=true;
                    $this->IS_35=true;
                    $GLOBALS["SQUID_IS_VERSION"][30]=false;
                    $GLOBALS["SQUID_IS_VERSION"][31]=false;
                    $GLOBALS["SQUID_IS_VERSION"][32]=true;
                    $GLOBALS["SQUID_IS_VERSION"][33]=true;
                    $GLOBALS["SQUID_IS_VERSION"][34]=true;
                    $GLOBALS["SQUID_IS_VERSION"][35]=true;
                    return;
                }

            }

            if($re[1]==4){
                $GLOBALS["SQUID_IS_VERSION"][30]=false;
                $GLOBALS["SQUID_IS_VERSION"][31]=false;
                $GLOBALS["SQUID_IS_VERSION"][32]=true;
                $GLOBALS["SQUID_IS_VERSION"][33]=true;
                $GLOBALS["SQUID_IS_VERSION"][34]=true;
                $GLOBALS["SQUID_IS_VERSION"][35]=true;
                $GLOBALS["SQUID_IS_VERSION"][40]=true;
                $GLOBALS["SQUID_IS_VERSION"][50]=false;

            }

            if($re[1]==5){
                $this->IS_30=false;
                $this->IS_31=false;
                $this->IS_32=true;
                $this->IS_33=true;
                $this->IS_34=true;
                $this->IS_35=true;
                $this->IS_40=true;
                $this->IS_50=true;
                $GLOBALS["SQUID_IS_VERSION"][30]=false;
                $GLOBALS["SQUID_IS_VERSION"][31]=false;
                $GLOBALS["SQUID_IS_VERSION"][32]=true;
                $GLOBALS["SQUID_IS_VERSION"][33]=true;
                $GLOBALS["SQUID_IS_VERSION"][34]=true;
                $GLOBALS["SQUID_IS_VERSION"][35]=true;
                $GLOBALS["SQUID_IS_VERSION"][40]=true;
                $GLOBALS["SQUID_IS_VERSION"][50]=true;

            }

            if($re[1]==2){
                $this->IS_30=false;
                $this->IS_31=false;
                $this->IS_32=false;
                $this->IS_33=false;
                $this->IS_34=false;
                if($re[2]>=7){
                    $this->IS_27=true;
                }
            }


            $this->IS_VERS_LOGS=@implode(" ", $VERLOGS);

            if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)#',$this->SQUID_VERSION,$re)){
                $this->intvalVersion=intval($re[1]).intval($re[2]).intval($re[3]);
                $GLOBALS["intvalVersion"]=$this->intvalVersion;
            }else{

                if(preg_match('#^([0-9]+)\.([0-9]+)\.STABLE#',$this->SQUID_VERSION,$re)){
                    $this->intvalVersion=intval($re[1]).intval($re[2]).'0';
                    $GLOBALS["intvalVersion"]=$this->intvalVersion;
                }

            }

        }
        $GLOBALS["OUTPUTED_VER"]=true;

    }
    function VerifyLdapBranch(){
        if(isset($GLOBALS["SQUID_MEMORYCONF"])){
            if(isset($GLOBALS["SQUID_MEMORYCONF"]["GlobalSquidConf"])){
                $this->squid_conf=$GLOBALS["SQUID_MEMORYCONF"]["GlobalSquidConf"];
            }

            if(isset($GLOBALS["SQUID_MEMORYCONF"]["ArticaSquidParameters"])){
                $this->ArticaSquidParameters=$GLOBALS["SQUID_MEMORYCONF"]["ArticaSquidParameters"];
            }

            if(isset($GLOBALS["SQUID_MEMORYCONF"]["SquidBlockSites"])){
                $this->SquidBlockSites=$GLOBALS["SQUID_MEMORYCONF"]["SquidBlockSites"];
            }
            return;
        }

        $sock=new sockets();
        $this->squid_conf=$sock->GET_INFO('GlobalSquidConf');
        if($this->squid_conf==null){$this->squid_conf=$this->LOAD_CONF_ROOT();}

        $this->ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
        $this->SquidBlockSites=$sock->GET_INFO('SquidBlockSites');

        $GLOBALS["SQUID_MEMORYCONF"]["GlobalSquidConf"]=$this->squid_conf;
        $GLOBALS["SQUID_MEMORYCONF"]["ArticaSquidParameters"]=$this->ArticaSquidParameters;
        $GLOBALS["SQUID_MEMORYCONF"]["SquidBlockSites"]=$this->SquidBlockSites;


    }
    private function LOAD_CONF_ROOT(){
       return "/etc/squid3/squid.conf";
    }
    function ParseConfig(){


        $ini=new Bs_IniHandler();
        $sock=new sockets();
        if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
        $users=$GLOBALS["CLASS_USERS"];

        $this->EnableRemoteStatisticsAppliance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteStatisticsAppliance");
        if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}

        if($this->ASROOT){
            if(!isset($GLOBALS["NOSQUIDOUTPUT"])){
                if(!isset($GLOBALS["OUTPUTED_ARP"])){echo "Starting......: ".date("H:i:s")." [MAC]: ARP OK, acls with Mac addresses enabled\n";}}
        }else{
            if($this->ASROOT){
                if(!isset($GLOBALS["OUTPUTED_ARP"])){echo "Starting......: ".date("H:i:s")." [MAC]: ARP Not enabled, acls with Mac addresses disabled\n";}
            }
        }



        $network=null;
        $dns=null;
        $GLOBALS["OUTPUTED_ARP"]=true;
        if($this->ArticaSquidParameters==null){$this->ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');}

        if($GLOBALS["VERBOSED"]){echo __LINE__." ArticaSquidParameters ->". strlen($this->ArticaSquidParameters)." bytes<br>\n";}

        if(!isset($GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"])){
            $ini->loadString($this->ArticaSquidParameters);
            if(isset($ini->_params)){
                $GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"]=$ini->_params;
            }
        }else{
            $ini->_params=$GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"];
        }

        $this->LDAP_AUTH=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidStandardLDAPAuth"));
        $this->SquidLdapAuthEnableGroups=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLdapAuthEnableGroups");
        $network="";
        $EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
        if($EnableOpenLDAP==0){$this->LDAP_AUTH=0;$this->SquidLdapAuthEnableGroups=0;}

        if(isset($ini->_params)){

            if(!isset($ini->_params["NETWORK"]["NODES_NAME"])){$ini->_params["NETWORK"]["NODES_NAME"]=null;}
            if(!isset($ini->_params["NETWORK"]["cdir"])){$ini->_params["NETWORK"]["cdir"]=null;}
            if(!isset($ini->_params["NETWORK"]["LISTEN_PORT"])){$ini->_params["NETWORK"]["LISTEN_PORT"]=3128;}
            if(!isset($ini->_params["NETWORK"]["SECOND_PORT"])){$ini->_params["NETWORK"]["SECOND_PORT"]=0;}
            if(!isset($ini->_params["NETWORK"]["visible_hostname"])){$ini->_params["NETWORK"]["visible_hostname"]=null;}
            if(!isset($ini->_params["NETWORK"]["NTLM_AUTH"])){$ini->_params["NETWORK"]["NTLM_AUTH"]=0;}
            if(!isset($ini->_params["NETWORK"]["snmp_community"])){$ini->_params["NETWORK"]["snmp_community"]="";}
            if(!isset($ini->_params["NETWORK"]["snmp_enable"])){$ini->_params["NETWORK"]["snmp_enable"]=0;}
            if(!isset($ini->_params["NETWORK"]["snmp_port"])){$ini->_params["NETWORK"]["snmp_port"]=null;}
            if(!isset($ini->_params["NETWORK"]["snmp_access_ip"])){$ini->_params["NETWORK"]["snmp_access_ip"]=null;}
            if(!isset($ini->_params["NETWORK"]["certificate_center"])){$ini->_params["NETWORK"]["certificate_center"]=null;}
            if(!isset($ini->_params["NETWORK"]["EnableUserAgentBanAll"])){
$ini->_params["NETWORK"]["EnableUserAgentBanAll"]=0;}
            if(!isset($ini->_params["NETWORK"]["EnableChangeRequestSize"])){
                $ini->_params["NETWORK"]["EnableChangeRequestSize"]=0;
            }
            if(!isset($ini->_params["NETWORK"]["EnableICPPort"])){
                $ini->_params["NETWORK"]["EnableICPPort"]=0;
            }

            if(!is_null($ini->_params["NETWORK"]["cdir"])) {
                $network = explode(';', $ini->_params["NETWORK"]["cdir"]);
            }

            $this->listen_port=$ini->_params["NETWORK"]["LISTEN_PORT"];
            $this->second_listen_port=$ini->_params["NETWORK"]["SECOND_PORT"];

            if($this->visible_hostname==null){
                $this->visible_hostname=$ini->_params["NETWORK"]["visible_hostname"];
            }

            if(!is_null( $this->visible_hostname)) {
                $this->visible_hostname = str_replace("..", ".", $this->visible_hostname);
            }


            if($this->ASROOT){
                include_once("/usr/share/artica-postfix/framework/class.unix.inc");
                if(!class_exists("unix")){
                    include_once("/usr/share/artica-postfix/framework/class.unix.inc");
                }
                $unix=new unix();
                $nginx=$unix->find_program("nginx");
                if(is_file($nginx)){
                    $sock=new sockets();
                    $DisableNginxProxy=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNginxProxy");
                    if(!is_numeric($DisableNginxProxy)){$DisableNginxProxy=0;}
                    if($DisableNginxProxy==0){
                        if($this->listen_port==80){$this->listen_port=8080;}
                        if($this->ssl_port==443){$this->ssl_port=4443;}
                    }
                }
            }

            if($this->ASROOT){
                if(!isset($GLOBALS["NOSQUIDOUTPUT"])){
                    echo "Starting......: ".date("H:i:s")." [AUTH]: LDAP_AUTH = $this->LDAP_AUTH\n";
                }
            }
            if(!isset($ini->_params["NETWORK"]["wccp2_forwarding_method"])){
                $ini->_params["NETWORK"]["wccp2_forwarding_method"]="";
            }
            if(!isset($ini->_params["NETWORK"]["wccp2_return_method"])){
                $ini->_params["NETWORK"]["wccp2_return_method"]="";
            }
            if(!isset($ini->_params["NETWORK"]["wccp2_assignment_method"])){
                $ini->_params["NETWORK"]["wccp2_assignment_method"]="";
            }
            if(!isset($ini->_params["NETWORK"]["ALT_PORT"])){
                $ini->_params["NETWORK"]["ALT_PORT"]="";
            }
            if(!isset($ini->_params["NETWORK"]["SSL_PORT"])){
                $ini->_params["NETWORK"]["SSL_PORT"]="";
            }
            if(!isset($ini->_params["NETWORK"]["SSL_BUMP"])){
                $ini->_params["NETWORK"]["SSL_BUMP"]="";
            }
            if(!isset($ini->_params["NETWORK"]["wccp2_router"])){
                $ini->_params["NETWORK"]["wccp2_router"]="";
            }

            $this->NTLM_AUTH=$ini->_params["NETWORK"]["NTLM_AUTH"];

            $this->LDAP_EXTERNAL_AUTH=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidExternLDAPAUTH"));
            $this->snmp_community=$ini->_params["NETWORK"]["snmp_community"];
            $this->snmp_enable=$ini->_params["NETWORK"]["snmp_enable"];
            $this->snmp_port=$ini->_params["NETWORK"]["snmp_port"];
            $this->snmp_access_ip=$ini->_params["NETWORK"]["snmp_access_ip"];

            $this->certificate_center=$ini->_params["NETWORK"]["certificate_center"];
            $this->ssl_port=$ini->_params["NETWORK"]["SSL_PORT"];
            $this->alt_listen_port=$ini->_params["NETWORK"]["ALT_PORT"];
            $this->SSL_BUMP=$ini->_params["NETWORK"]["SSL_BUMP"];
            $this->wccp2_enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWCCPEnabled");
            $this->wccp2_router=$ini->_params["NETWORK"]["wccp2_router"];



            $this->wccp2_forwarding_method=$ini->_params["NETWORK"]["wccp2_forwarding_method"];
            $this->wccp2_return_method=$ini->_params["NETWORK"]["wccp2_return_method"];
            $this->wccp2_assignment_method=$ini->_params["NETWORK"]["wccp2_assignment_method"];

            $this->EnableUserAgentBanAll=$ini->_params["NETWORK"]["EnableUserAgentBanAll"];
            $this->EnableChangeRequestSize=$ini->_params["NETWORK"]["EnableChangeRequestSize"];
            $this->EnableICPPort=$ini->_params["NETWORK"]["EnableICPPort"];
            if(!is_numeric($this->SquidLdapAuthEnableGroups)){$this->SquidLdapAuthEnableGroups=0;}
            $this->enable_kavproxy=0;
            if(!is_numeric($this->second_listen_port)){$this->second_listen_port=0;}
            if(!is_numeric($this->snmp_enable)){$this->snmp_enable=0;}
            if(!is_numeric($this->snmp_port)){$this->snmp_port=3401;}
            if(!isset($ini->_params["NODES_NAME"])){$ini->_params["NODES_NAME"]=null;}

            $this->nodes_names=$ini->_params["NODES_NAME"];

            if(is_array($ini->_params)){
                reset($ini->_params);

                foreach ($ini->_params as $num=>$val){
                    if(preg_match('#cache:(.+)#',$num,$re)){
                        if($GLOBALS["VERBOSE"]){echo "Found cache: {$re[1]}<br>\n";}
                        $re[1]=str_replace(" ", "_", $re[1]);

                        $this->cache_list[$re[1]]=array(
                            "cache_type"=>$ini->_params[$num]["cache_type"],
                            "cache_dir_level1"=>$ini->_params[$num]["cache_dir_level1"],
                            "cache_dir_level2"=>$ini->_params[$num]["cache_dir_level2"],
                            "cache_size"=>$ini->_params[$num]["cache_size"],
                            "cache_maxsize"=>$ini->_params[$num]["cache_maxsize"],
                        );
                    }

                    if(preg_match('#time:([0-9]+):(.+)#',$num,$re)){
                        foreach ($ini->_params[$num] as $a=>$b){
                            $this->acl_times[$num][$a]=$b;
                        }
                    }
                }
            }

        }


        if($this->wccp2_enabled==null){$this->wccp2_enabled="0";}
        if($this->wccp2_forwarding_method==null){$this->wccp2_forwarding_method="1";}
        if($this->wccp2_return_method==null){$this->wccp2_return_method="1";}
        if($this->wccp2_assignment_method==null){$this->wccp2_assignment_method="hash";}

        if($this->EnableChangeRequestSize==null){$this->EnableChangeRequestSize="0";}
        if($this->enable_cicap==null){$this->enable_cicap=0;}
        if($this->listen_port==null){$this->listen_port=3128;}
        if($this->visible_hostname=="proxyweb"){$this->visible_hostname=null;}

        if($this->visible_hostname==null){
            if(class_exists("unix")) {
                $unix = new unix();
                $this->visible_hostname = $unix->hostname_g();
            }else{
                include_once("/usr/share/artica-postfix/framework/class.unix.inc");
                $unix = new unix();
                $this->visible_hostname = $unix->hostname_g();

            }
        }
        if($this->visible_hostname==null){$this->visible_hostname=$users->hostname;}
        if(!is_numeric($this->LDAP_AUTH)){$this->LDAP_AUTH=0;}
        if(!is_numeric($this->NTLM_AUTH)){$this->NTLM_AUTH=0;}
        if($this->enable_kavproxy==null){$this->enable_kavproxy=0;}
        $this->enable_dansguardian=0;


        if($this->SSL_BUMP==null){$this->SSL_BUMP=0;}

        if(preg_match("#([0-9]+)\.([0-9]+)#",$users->SQUID_VERSION,$re)){
            if($re[1]<3){$this->SSL_BUMP=0;}else{if($re[2]<1){$this->SSL_BUMP=0;}}
        }



        if(is_array($network)){
            foreach ($network as $index=>$cidr){
                if(trim($cidr)==null){continue;}
                $this->network_array[]=$cidr;
            }
        }

        if($this->squid_conf==null){$this->squid_conf=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("GlobalSquidConf");}


        $this->global_conf_array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGlobalConfArray"));
        if(!is_array($this->global_conf_array)){
            $tb=explode("\n",$this->squid_conf);
            writelogs("global_conf_array is not an array, parsing ". count($tb).
                " lines.. ". strlen($this->squid_conf)." bytes",__CLASS__.'/'.__FUNCTION__,__FILE__);

            foreach ($tb as $val){
                if(preg_match('#^([a-z\_]+)\s+(.+)#',$val,$re)){
                    if($re[1]=="acl"){continue;}
                    if($re[1]=="http_access"){continue;}
                    if($re[1]=="auth_param"){continue;}
                    $this->global_conf_array[$re[1]]=$re[2];
                }
            }
        }



        if(!isset($this->global_conf_array["reply_header_max_size"])){$this->global_conf_array["reply_header_max_size"]="64 KB";}



        $fqdncache_size=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("fqdncache_size"));
        if($fqdncache_size==0){$fqdncache_size=1024;}



        $CacheMemCentral=0;

        $this->EnableRemoteStatisticsAppliance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteStatisticsAppliance");
        if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}




        $this->enable_cicap=$sock->GET_INFO('CicapEnabled');
        $this->enable_squidguard=$sock->GET_INFO('squidGuardEnabled');
        $this->UfdbEnabledCentral=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbEnabledCentral");
        $this->AntivirusEnabledCentral=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("AntivirusEnabledCentral");
        $this->SquidWCCPL3Enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWCCPL3Enabled"));

        if(!is_numeric($this->enable_kavproxy)){$this->enable_kavproxy=0;}
        if(!is_numeric($this->enable_cicap)){$this->enable_cicap=0;}
        if(!is_numeric($this->enable_squidguard)){$this->enable_squidguard=0;}
        if(!is_numeric($this->UfdbEnabledCentral)){$this->UfdbEnabledCentral=1;}
        if(!is_numeric($this->AntivirusEnabledCentral)){$this->AntivirusEnabledCentral=1;}

        if($this->EnableRemoteStatisticsAppliance==1){
            $this->enable_UfdbGuard=$this->UfdbEnabledCentral;
            $this->EnableKavICAPRemote=$this->AntivirusEnabledCentral;
            $this->enable_cicap=$this->AntivirusEnabledCentral;
            $this->enable_kavproxy=$this->AntivirusEnabledCentral;
            $this->enable_squidclamav=0;
        }
        if($this->DisableWinbindd==1){$this->EnableKerbAuth=0;$this->NTLM_AUTH=0;}
        if($this->SquidWCCPL3Enabled==1){$this->wccp2_enabled=0;}

        $SquidWCCPL3SSLEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWCCPL3SSLEnabled"));
        if($SquidWCCPL3SSLEnabled==1){
            $this->SSL_BUMP=1;
            $this->EnableSquidSSLCRTD=1;
        }




        if($this->SquidUrgency==1){
            $this->enable_cicap=0;
            $this->enable_kavproxy=0;
            $this->enable_squidclamav=0;
            $this->NTLM_AUTH=0;
        }

        if($this->SSL_BUMP==0){

            $this->EnableSquidSSLCRTD=0;
        }


    }
    private function append_domain():string{
        $SquidAppendDomain=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAppendDomain"));
        $SquidAppendDomainDisabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAppendDomainDisabled"));
        if($SquidAppendDomainDisabled==1){return "";}

        $f[]="# Append domain: SquidAppendDomain = $SquidAppendDomain [".__LINE__."]";

        if($SquidAppendDomain==null){
            $MainArray=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolvConf"));
            $SquidAppendDomain=trim($MainArray["DOMAINS1"]);
            $f[]="# Append domain: resolvConf = $SquidAppendDomain [".__LINE__."]";
            if($SquidAppendDomain=="localhost.local"){$SquidAppendDomain=null;}
        }

        if($SquidAppendDomain==null){
            $unix=new unix();
            $hostname=trim($unix->hostname_g());
            $hostname2=explode('.',$hostname);
            unset($hostname2[0]);
            $SquidAppendDomain=trim(@implode(".", $hostname2));
            $f[]="# Append domain: $hostname = $SquidAppendDomain [".__LINE__."]";

        }

        $f[]="# Append domain: [$SquidAppendDomain] [".__LINE__."]";
        if($SquidAppendDomain==null){return @implode("\n", $f);}

        $f[]="append_domain .$SquidAppendDomain";
        return @implode("\n", $f);
    }

    private function request_header_access(){
        if($this->SquidUrgency==1){return null;}
        if($this->IS_27){return "#request_header_access/request_header_replace not supported in 2.7x\n";}

        $results=$this->_compile_parmz();
        $enabled=false;
        foreach ($results as $num=>$val){if(preg_match("#enable-http-violations#", $val)){$enabled=true;}}
        if(!$enabled){return "# --------- enable-http-violations no such compilation option in ".count($results)." rows";}
        $q=new mysql();
        $tr=array();
        $ALLOWED=array();
        $BANNED=array();
        $CHANGE=array();
        $final=null;
        $t=array();
        $sql="SELECT * FROM squid_header_access WHERE active=1";
        $results = $q->QUERY_SQL($sql,"artica_backup");
        if(!$q->ok){return "# --------- MySql error detected\n";}
        if(mysqli_num_rows($results)==0){return "# --------- 0 active entry\n";}
        while ($ligne = mysqli_fetch_assoc($results)) {

            if($ligne["allow"]==1){
                if($ligne["header"]=="All"){$final="request_header_access {$ligne["header"]} allow all";continue;}
                $ALLOWED[]="request_header_access {$ligne["header"]} allow all";

            }
            if($ligne["allow"]==0){
                if($ligne["header"]=="All"){$final="request_header_access {$ligne["header"]} deny all";continue;}
                $BANNED[]="request_header_access {$ligne["header"]} deny all";
            }

            if(strlen(trim($ligne["replacewith"]))>2){
                $ligne["replacewith"]=trim($ligne["replacewith"]);
                $CHANGE[]="request_header_replace {$ligne["header"]} {$ligne["replacewith"]}";
            }

        }

        $t[]="# --------- allow: ".count($ALLOWED)." deny:".count($BANNED)." modify: ".count($CHANGE);

        if(count($ALLOWED)>0){
            $t[]=@implode("\n", $ALLOWED);
        }
        if(count($BANNED)>0){
            $t[]=@implode("\n", $BANNED);
        }
        if($final<>null){
            $t[]=$final;
        }
        if(count($CHANGE)>0){
            $t[]=@implode("\n", $CHANGE);
        }
        if(count($t)>0){return @implode("\n", $t);}

    }


    private function _compile_parmz(){
        if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}

        if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
        $squidbin=$unix->find_program("squid3");
        if(strlen($squidbin)<5){$squidbin=$unix->find_program("squid");}
        exec("$squidbin -v 2>&1",$results);
        if($GLOBALS["VERBOSE"]){echo "[DEBUG]: $squidbin -v -> ".count($results). "rows\n";}
        $GLOBALS[__FUNCTION__]=$results;
        return $results;
    }

    function SaveToLdap($norestart=false){
        if(!$this->kav_accept){
            writelogs("SQUID:: kavaccept = FALSE",__CLASS__.'/'.__FUNCTION__,__FILE__);
            $this->enable_kavproxy=0;
        }

        $sock=new sockets();
        if(!is_numeric($this->snmp_port)){$this->snmp_port=3401;}
        $ini=null;
        $ini=$ini."[NETWORK]\n";
        $ini=$ini."cdir=".implode(";",$this->network_array)."\n";
        $ini=$ini."dns_servers=".implode(";",$this->dns_array)."\n";
        $ini=$ini."LISTEN_PORT=$this->listen_port\n";
        $ini=$ini."SECOND_PORT=$this->second_listen_port\n";
        $ini=$ini."SSL_PORT=$this->ssl_port\n";
        $ini=$ini."certificate_center=$this->certificate_center\n";
        $ini=$ini."snmp_community=$this->snmp_community\n";
        $ini=$ini."snmp_enable=$this->snmp_enable\n";
        $ini=$ini."snmp_port=$this->snmp_port\n";
        $ini=$ini."snmp_access_ip=$this->snmp_access_ip\n";


        if($this->enable_dansguardian==1){
            $this->alt_listen_port=$sock->RandomPort();
            $dansguardian=new dansguardian();
            $dansguardian->SaveSettings();
        }
        $ini=$ini."wccp2_enabled=$this->wccp2_enabled\n";
        $ini=$ini."wccp2_router=$this->wccp2_router\n";
        $ini=$ini."wccp2_forwarding_method=$this->wccp2_forwarding_method\n";
        $ini=$ini."wccp2_return_method=$this->wccp2_return_method\n";
        $ini=$ini."wccp2_assignment_method=$this->wccp2_assignment_method\n";

        $ini=$ini."EnableUserAgentBanAll=$this->EnableUserAgentBanAll\n";
        $ini=$ini."EnableChangeRequestSize=$this->EnableChangeRequestSize\n";


        $ini=$ini."EnableICPPort=$this->EnableICPPort\n";


        if($this->IS_30){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [VER]: is 3.x OK\n";}}
        if(!$this->IS_32){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [VER]: is NOT 3.2.x OK\n";}}
        if($this->IS_32){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [VER]: is 3.2.x OK\n";}}






        $ini=$ini."ALT_PORT=$this->alt_listen_port\n";
        $ini=$ini."visible_hostname=$this->visible_hostname\n";



        writelogs("Save LDAP_AUTH=$this->LDAP_AUTH",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        $sock->SET_INFO("SquidStandardLDAPAuth", $this->LDAP_AUTH);
        $sock->SET_INFO("SquidLdapAuthEnableGroups", $this->SquidLdapAuthEnableGroups);
        $sock->SET_INFO("SquidExternLDAPAUTH", $this->LDAP_EXTERNAL_AUTH);
        $ini=$ini."LDAP_AUTH=$this->LDAP_AUTH\n";
        $ini=$ini."NTLM_AUTH=$this->NTLM_AUTH\n";
        $ini=$ini."SSL_BUMP=$this->SSL_BUMP\n";


        $this->CACHE_PATH=str_replace(" ", "_", $this->CACHE_PATH);
        $ini=$ini."[KAV]\n";
        $ini=$ini."enabled=$this->enable_kavproxy\n";
        $ini=$ini."[DANSGUARDIAN]\n";
        $ini=$ini."enable_dansguardian=$this->enable_dansguardian\n";
        $ini=$ini."[CACHE]\n";
        $ini=$ini."CACHE_SIZE=$this->CACHE_SIZE\n";
        $ini=$ini."CACHE_PATH=$this->CACHE_PATH\n";
        $ini=$ini."CACHE_TYPE=$this->CACHE_TYPE\n";

        if(is_array($this->nodes_names)){
            reset($this->nodes_names);
            $ini=$ini."[NODES_NAME]\n";
            foreach ($this->nodes_names as $num=>$val){
                $ini=$ini."$num=$val\n";
            }
        }



        if(is_array($this->cache_list)){
            reset($this->cache_list);
            $num=null;
            $val=null;
            foreach ($this->cache_list as $num=>$val){
                $num=str_replace(" ", "_", $num);
                $ini=$ini."[cache:$num]\n";
                $ini=$ini."cache_type={$val["cache_type"]}\n";
                $ini=$ini."cache_dir_level1={$val["cache_dir_level1"]}\n";
                $ini=$ini."cache_dir_level2={$val["cache_dir_level2"]}\n";
                $ini=$ini."cache_size={$val["cache_size"]}\n";
                $ini=$ini."cache_maxsize={$val["cache_maxsize"]}\n";
            }
        }else{
            writelogs("cache_list, not an array...",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
        }

        if(is_array($this->acl_times)){
            reset($this->acl_times);
            foreach ($this->acl_times as $num=>$array){
                $ini=$ini."[$num]\n";
                foreach ($array as $a=>$b){
                    $ini=$ini."$a=$b\n";
                }

            }

        }

        writelogs("Save SquidGlobalConfArray ".count($this->global_conf_array)." parameters",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        $sock->SaveConfigFile(base64_encode(serialize($this->global_conf_array)),"SquidGlobalConfArray");
        $sock->SET_INFO("DansGuardianEnabled",$this->enable_dansguardian);

        $sock->SET_INFO("hasProxyTransparent",$this->hasProxyTransparent);
        $sock->SaveConfigFile($ini,"ArticaSquidParameters");
        //$sock->SaveConfigFile($this->BuildSquidConf(),"GlobalSquidConf");
        $sock->SET_INFO("CicapEnabled",$this->enable_cicap);
        $sock->SET_INFO("squidGuardEnabled",$this->enable_squidguard);
        $sock->SET_INFO("EnableAdZapper",$this->enable_adzapper);
        $sock->SET_INFO("EnableSquidClamav",$this->enable_squidclamav);
        $sock->SET_INFO("SquidEnableStreamCache",$this->enable_streamcache);
        $sock->SET_INFO("SquideCapAVEnabled",$this->enable_ecapav);

        $EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
        if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}

        if(class_exists("usersMenus")){
            $users=new usersMenus();
            if($users->STATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}
        }

        if($this->enable_dansguardian==1){
            writelogs("",__FUNCTION__,__FILE__);
            writelogs("Save Dansguardian configuration...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            $dans=new dansguardian();
            $dans->SaveSettings();
        }


        if($EnableWebProxyStatsAppliance==1){
            $sock=new sockets();
            $sock->getFrameWork("squid.php?notify-remote-proxy=yes");
            return true;
        }

        if(!$norestart){
            $sock->getFrameWork("squid.php?build-smooth=yes");
        }
        return true;

    }
    function SaveToServer($norestart=false){
        $sock=new sockets();
        $EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
        if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}

        if(class_exists("usersMenus")){
            $users=new usersMenus();
            if($users->STATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}
        }

        if($EnableWebProxyStatsAppliance==1){
            $sock=new sockets();
            $sock->getFrameWork("squid.php?notify-remote-proxy=yes");
            return;
        }

        if(!$norestart){
            $sock=new sockets();
            $sock->getFrameWork("cmd.php?squidnewbee=yes");
        }
    }
    public function SquidGuardDatabasesStatus($all=0){
        $datas=explode("\n",@file_get_contents("/etc/squid/squidGuard.conf"));
        foreach ($datas as $a=>$b){
            if(preg_match("#domainlist.+?(.+)#",$b,$re)){
                $file="/var/lib/squidguard/{$re[1]}";
                if(filesize($file)==0){continue;}
                $f[]=$file;
                continue;
            }

            if(preg_match("#urllist.+?(.+)#",$b,$re)){
                $file="/var/lib/squidguard/{$re[1]}";
                if(filesize($file)==0){continue;}
                $f[]=$file;
                continue;
            }


        }

        if($all==0){foreach ($f as $a=>$b){
            if(!is_file("$b.db")){
                $array[]="$b.db";
            }

        }}else{
            foreach ($f as $a=>$b){
                $array[]="$b.db";
            }
        }


        return $array;

    }
    public function is_auth(){
        if($this->SquidUrgency==1){return false;}
        if(isset($GLOBALS["squid_is_auth"])){return $GLOBALS["squid_is_auth"];}
        $GLOBALS["squid_is_auth"]=$this->_is_auth();

    }

    private function _is_auth(){
        if($this->SquidUrgency==1){return false;}
        if($this->LDAP_AUTH==1){return true;}
        if($this->LDAP_EXTERNAL_AUTH==1){return true;}
        if($this->EnableKerbAuth==1){return true;}

        $UseNativeKerberosAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseNativeKerberosAuth"));
        $WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));

        if($UseNativeKerberosAuth==1){return true;}
        if($WindowsActiveDirectoryKerberos==1){return true;}
        $acl=new squid_acls();
        if($acl->IsObjectAuth()){return true;}
        return false;
    }
    private function build_progress($text){

        if(!isset($GLOBALS["PRC"])){$GLOBALS["PRC"]=$this->CURRENT_PROGRESS;}


        ///--wccp-build

        $GLOBALS["PRC"]++;
        echo "Starting......: ".date("H:i:s")." $text {$GLOBALS["PRC"]}%\n";
        if(!function_exists("build_progress")){return;}
        if($this->CURRENT_PROGRESS==0){return;}
        if($GLOBALS["PRC"]>$this->MAX_PROGRESS){$GLOBALS["PRC"]=$this->MAX_PROGRESS;}
        build_progress("{reconfigure} $text",$GLOBALS["PRC"]);
    }
    private function WCCP(){
        $ipClass=new IP();


        if($this->wccp2_enabled<>1){return "# --------- Cisco's Web Cache Coordination Protocol is not enabled\n";}
        $conf[]="";


        $wccp2_router=array();
        $wccp2_routers=$this->WCCPHash["wccp2_router"];
        if(strpos($wccp2_routers, ",")>0){
            $wccp2_router=explode(",",$wccp2_routers);
        }else{
            $wccp2_router[]=$wccp2_routers;
        }

        $conf[]="# --------- Cisco's Web Cache Coordination Protocol";
        foreach ($wccp2_router as $none=>$ipaddr){
            if($ipClass->isValid($ipaddr)){
                $conf[]="wccp2_router $ipaddr";
            }

        }

        $conf[]="wccp2_address {$this->WCCPHash["listen_address"]}";
        $conf[]="wccp2_forwarding_method {$this->WCCPHash["wccp2_forwarding_method"]}";
        $conf[]="wccp2_return_method {$this->WCCPHash["wccp2_return_method"]}";
        $conf[]="wccp2_assignment_method {$this->WCCPHash["wccp2_assignment_method"]}";
        $conf[]="";
        return implode("\n",$conf);
    }
    private function myport_token(){return "localport";}
    private function memory_pools(){
        $SquidMemoryPools=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMemoryPools"));
        if($SquidMemoryPools==0){return "memory_pools off";}

        $memory_pools_limit_suffix=null;
        $SquidMemoryPoolsLimit=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMemoryPoolsLimit"));
        if($SquidMemoryPoolsLimit>0){$memory_pools_limit_suffix=" MB";}
        $f[]="memory_pools on";
        if($SquidMemoryPoolsLimit==0){
            $f[]="memory_pools_limit none";
        }else{
            $f[]="memory_pools_limit $SquidMemoryPoolsLimit$memory_pools_limit_suffix";
        }
        return @implode("\n", $f);
    }
    private function ACL_TEMPLATES_URIS(){
        if($this->SquidUrgency==1){return null;}

        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $sql="SELECT template_uri FROM squidtpls WHERE template_link=1";
        $results = $q->QUERY_SQL($sql);
        $acls=array();
        $ALREADY=array();
        foreach ($results as $index=>$ligne){
            if(trim($ligne["template_uri"])==null){return;}
            $uri=$this->ACL_TEMPLATES_URIS_TO_DSTDOMAIN($ligne["template_uri"]);
            if(isset($ALREADY[$uri])){continue;}$ALREADY[$uri]=true;
            $acls[]=$uri;
        }

        $GetUfdBguardRedirect=$this->GetUfdBguardRedirect();

        foreach ($GetUfdBguardRedirect as $tpl=>$line){
            $uri=$this->ACL_TEMPLATES_URIS_TO_DSTDOMAIN($tpl);
            if(isset($ALREADY[$uri])){continue;}$ALREADY[$uri]=true;
            $acls[]=$uri;
        }


        @file_put_contents("/etc/squid3/acls/templatesacls.txt", @implode("\n", $acls)."\n");
        $GLOBALS["HTTP_ACCESS_ALLOW_TEMPLATES"]="http_access allow {$this->MyStandPort}AllowedUrisTemplates";
        return null;

    }
    private function ACL_TEMPLATES_URIS_TO_DSTDOMAIN($uri){
        $uri=str_replace("%FREEWEBS%", "", $uri);
        if(strpos("  $uri", "http")==0){$uri="http://$uri";}
        if(preg_match("#^([0-9]+):(.+)#", $uri,$re)){$uri=$re[2];}
        $URLAR=parse_url($uri);
        $scheme=$URLAR["scheme"];
        $sitename=$URLAR["host"];
        if($sitename==null){return;}
        if(preg_match("#^(.+?):[0-9]+$#", $sitename)){return $re[1];}
        return $sitename;

    }
    private function GetUfdBguardRedirect(){
        if(!is_file("/etc/squid3/ufdbGuard.conf")){return array();}
        $array=array();
        $f=explode("\n",@file_get_contents("/etc/squid3/ufdbGuard.conf"));
        foreach ($f as $index=>$line){
            if(preg_match("#redirect\s+(.+)#", $line,$re)){
                $uri=trim($re[1]);if(preg_match("#^[0-9]+:(.+)#", $uri,$re)){$uri=$re[1];}
                $array[$uri]=$uri;
                continue;
            }

        }

        return $array;
    }
    private function string_to_regex($pattern){
        if(trim($pattern)==null){return null;}
        $pattern=str_replace(".", "\.", $pattern);
        $pattern=str_replace("[", "\[", $pattern);
        $pattern=str_replace("]", "\]", $pattern);
        $pattern=str_replace("(", "\(", $pattern);
        $pattern=str_replace(")", "\)", $pattern);
        $pattern=str_replace("$", "\$", $pattern);
        $pattern=str_replace("?", "\?", $pattern);
        $pattern=str_replace("#", "\#", $pattern);
        $pattern=str_replace("{", "\{", $pattern);
        $pattern=str_replace("}", "\}", $pattern);
        $pattern=str_replace("!", "\!", $pattern);

        return $pattern;

    }
    private function BaseNameWebSite($www){
        if(trim($www)==null){return;}
        if(is_numeric(trim($www))){return;}

        if(strpos(" $www", "//")>0){
            $arrayURI=parse_url($www);
            $www=$arrayURI["host"];

        }
        if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
        if(preg_match("#(.+):[0-9]+#", $www,$re)){$www=$re[1];}
        if(substr($www,0,1)<>"."){$www=".$www";}
        if(substr($www,strlen($www)-1,1)=="/"){$www=substr($www, 0,strlen($www)-1);}
        $exploded=explode(".", $www);
        $sitebase=$exploded[count($exploded)-2].".".$exploded[count($exploded)-1];
        if(substr($sitebase, 0,1)=="."){$sitebase=substr($sitebase, 1,strlen($sitebase));}
        $sitebase=str_replace('.*.', '.', $sitebase);
        $sitebase=str_replace('*', '.', $sitebase);
        $sitebase=str_replace('$', '', $sitebase);
        $sitebase=str_replace('(', '', $sitebase);
        $sitebase=str_replace(')', '', $sitebase);
        $sitebase=str_replace(';', '.', $sitebase);
        $sitebase=str_replace(',', '.', $sitebase);
        $sitebase=str_replace('%', '', $sitebase);
        $sitebase=str_replace('+', '', $sitebase);
        $sitebase=str_replace('/', '', $sitebase);
        $sitebase=str_replace('?', '', $sitebase);
        $sitebase=str_replace('!', '', $sitebase);
        $sitebase=str_replace(':', '', $sitebase);
        $sitebase=str_replace('{', '', $sitebase);
        $sitebase=str_replace('}', '', $sitebase);
        $sitebase=str_replace('^', '', $sitebase);
        $sitebase=str_replace('\\', '', $sitebase);
        $sitebase=str_replace('\'', '', $sitebase);
        $sitebase=str_replace('"', '', $sitebase);
        $sitebase=str_replace('&', '', $sitebase);
        $sitebase=str_replace('<', '', $sitebase);
        $sitebase=str_replace('>', '', $sitebase);
        $sitebase=str_replace('[', '', $sitebase);
        $sitebase=str_replace(']', '', $sitebase);
        return $sitebase;
    }
    private function rsyslogd_version(){
        $unix=new unix();
        $rsyslogd=$unix->find_program("rsyslogd");
        exec("$rsyslogd -v 2>&1",$results);
        foreach ($results as $line) {
            if(!preg_match("#syslogd\s+([0-9\.]+)#", $line,$re)){continue;}
            return $re[1];
        }
        return "0";
    }
    private function CleanSquidConf():bool{
        $CLEANED=false;
        $newf=array();
        $f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
        foreach ($f as $line){
            if(preg_match("#^debug_options#",trim($line))){
                $CLEANED=true;
                continue;
            }
            $newf[]=$line;
        }

        if(!$CLEANED){return false;}
        @file_put_contents("/etc/squid3/squid.conf",@implode("\n",$newf));
        return true;
    }

    public function access_logs():bool{
        $acls=new squid_acls_groups();

        $denylogs=$acls->buildacls_bytype("deny_log");

        if(count($denylogs)>0){
            foreach ($denylogs as $line){
                $conf[]="access_log none $line\n";
            }
        }
        $csvlog=$this->access_log_csv();
        if($csvlog<>null){
            $conf[]="# --------- CSVLOG line:".__LINE__;
            $conf[]="$csvlog";
        }
        $log_access_acls_csv=$this->log_access_acls_csv();
        $log_access_acls=$this->log_access_acls();
        if($log_access_acls<>null){$conf[]=$log_access_acls;}
        if($log_access_acls_csv<>null){$conf[]=$log_access_acls_csv;}



        @file_put_contents("/etc/squid3/accesslog.acls.conf", @implode("\n", $conf));
        return true;
    }

    function BuildSquidConf(){
        system("/usr/sbin/artica-phpfpm-service -reconfigure-proxy");
    }

    public function CPU_NUMBER(){
        if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
        $cat=$unix->find_program("cat");
        $grep=$unix->find_program("grep");
        $cut=$unix->find_program("cut");
        $wc=$unix->find_program("wc");
        $cmd="$cat /proc/cpuinfo |$grep \"model name\" |$cut -d: -f2|$wc -l 2>&1";
        $CPUNUM=exec($cmd);
        return $CPUNUM;
    }

    public function isNGnx(){
        if(isset($GLOBALS["isNGnx"])){return $GLOBALS["isNGnx"];}
        $users=new usersMenus();
        if(!$users->NGINX_INSTALLED){$GLOBALS["isNGnx"]=false;return $GLOBALS["isNGnx"];}
        $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
        if($EnableNginx==1){
            $GLOBALS["isNGnx"]=true;
            return $GLOBALS["isNGnx"];
        }

        $GLOBALS["isNGnx"]=false;
        return $GLOBALS["isNGnx"];

    }
    private function access_log_csv(){
        if($this->SquidUrgency==1){return;}
        if($this->LogsWarninStop==1){return;}
        if($this->EnableSquidCSV==0){return;}


        $SquidCsvParams=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCsvParams"));

        $CSV["a1"]="\"%>a\"";
        $CSV["a2"]="\"%>A\"";
        $CSV["a3"]="\"%>eui\"";
        $CSV["a4"]="\"%<a\"";
        $CSV["a5"]="\"%<A\"";
        $CSV["a6"]="\"%[un\"";
        $CSV["a7"]="\"%rm\"";
        $CSV["a8"]="\"%ru\"";
        $CSV["a9"]="\"%rv\"";
        $CSV["a10"]="\"%>Hs\"";
        $CSV["a11"]="\"%<st\"";
        $CSV["a12"]="\"%Ss:%Sh\"";
        $CSV["a13"]="\"%{User-Agent}>h\"";
        $CSV["a14"]="\"%{X-Forwarded-For}>h\"";

        if(($this->IS_31) && (!$this->IS_32)){
            unset($CSV["a4"]);
            unset($CSV["a3"]);

        }


        if($this->EnableMacAddressFilter==0){unset($CSV["a3"]);}


        if( (count($SquidCsvParams)==0) OR !is_array($SquidCsvParams)){
            foreach ($CSV as $code=>$explain){
                $SquidCsvParams[$code]=1;
            }
        }

        $access[]="\"%{%Y-%m-%d}tl\"";
        $access[]="\"%{%H:%M:%S}tl\"";

        foreach ($CSV as $code=>$value){
            if($SquidCsvParams[$code]==1){
                $ll[]=$code;
                $access[]=$value;
            }
        }
        //$conf[]="logformat csv \"%tl\",\"%>eui\",\"%>a\",\"%>A\",\"%[ui\",\"%[un\",\"[%tl]\",\"%rm %ru HTTP/%rv\" %>Hs %<st %Ss:%Sh UserAgent:\"%{User-Agent}>h\" Forwarded:\"%{X-Forwarded-For}>h\"\n";

        $conf[]="logformat csv ".@implode(",", $access);
        $acls=new squid_acls_groups();
        $denylogsTXT=null;
        $denylogs=$acls->buildacls_bytype("deny_log");
        if(count($denylogs)>0){$denylogsTXT=trim(@implode(" ", $denylogs));}
        if(strlen($denylogsTXT)>2){$denylogsTXT=" $denylogsTXT";}

        $conf[]="access_log stdio:/var/log/squid/access.csv csv !MgRPort$denylogsTXT\n";
        return @implode("\n", $conf);
    }
    private function ACL_MANAGER(){
        if( ($this->IS_31) OR ($this->IS_30) OR ($this->IS_27)){
            if(!$this->IS_32){
                if(!$this->IS_33){
                    return "acl manager proto cache_object";
                }
            }
        }
    }
    private function NoAuthPorts(){
        $HaClusterTransParentMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode"));
        $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));

        if($HaClusterClient==1){
            if($HaClusterTransParentMode==1){
                $GLOBALS["NoAuthPorts_event"]="# HaCluster + Transparent -> not auth set..";
                return "NoAuthPort";
            }
        }

        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");

        $sql="SELECT COUNT(*) as TCOUNT FROM proxy_ports WHERE enabled=1 and NoAuth=1";
        $ligne=$q->mysqli_fetch_array($sql);
        if(intval($ligne["TCOUNT"])==0){return null;}
        $GLOBALS["NoAuthPorts_event"]="# {$ligne["TCOUNT"]} ports whith not auth set..";
        return "NoAuthPort";
    }
    private function http_access(){


        $conf[]="#";
        $conf[]="#";
        $conf[]="# # # # # # # # # # # # # # # # # # # ## # # # # # [".__LINE__."]";
        $conf[]="# # # # # # # # # # MAIN RULES # # # # # # # # # # [".__LINE__."]";
        $conf[]="# # # # # # # # # # # # # # # # # # # ## # # # # # [".__LINE__."]";
        $conf[]="#\t Dedicated rule that allow Access to Artica repositories";
        $conf[]="#\t in any cases for updates [".__LINE__."]";
        $conf[]="#";
        $conf[]="#";
        if($this->IS_35){
            if($this->SquidUrgency==0){
                $conf[]="#\t HTTP 301/302 ACL";
                $conf[]="acl http302 http_status 302";
                $conf[]="acl http301 http_status 301";
                $conf[]="";
                $conf[]="#\t Apple playlists ( no cache index files )";
                $conf[]="acl mimenocache rep_mime_type -i application/x-apple-plist";
                $conf[]="acl mimenocache rep_mime_type -i application/vnd.apple.mpegurl";
                $conf[]="acl mimenocache rep_mime_type -i video/abst";
                $conf[]="";
                $conf[]="store_miss deny http302";
                $conf[]="store_miss deny http301";
                $conf[]="send_hit deny http302";
                $conf[]="send_hit deny http301";
                $conf[]="";
                $conf[]="store_miss deny mimenocache";
                $conf[]="send_hit deny mimenocache";
                $conf[]="";


            }
        }



        $conf[]="#";
        $annotate_transaction=null;
        if($this->isSquid5) {
            $annotate_transaction = " AnnotateAllowMynet";
            $conf[] = "acl AnnotateAllowMynet annotate_transaction accessrule=allowmynet";
        }
        $conf[]="http_access allow MyLocalIpsDest$annotate_transaction";

        $annotate_transaction=null;
        if($this->isSquid5) {
            $annotate_transaction = " AnnotateFTP";
            $conf[] = "acl AnnotateFTP annotate_transaction accessrule=allow_ftp";
        }

        $conf[]="http_access allow FTP$annotate_transaction";


        if(!is_file("/etc/squid3/GlobalAccessManager_deny_cache.conf")){@touch("/etc/squid3/GlobalAccessManager_deny_cache.conf");}
        $conf[]="include /etc/squid3/GlobalAccessManager_deny_cache.conf";

        if($this->SquidUrgency==0){
            $conf[]="#\t Global Access Manager for Deny Global";
            $conf[]="include /etc/squid3/GlobalAccessManager_deny.conf";
        }

        if($this->SquidUrgency==1){
            $annotate_transaction=null;
            if($this->isSquid5) {
                $annotate_transaction = " EmergencyAllow";
                $conf[] = "acl EmergencyAllow annotate_transaction accessrule=emergency";
            }
            $conf[]="#\t URGENCY ENABLED !!! F:".__FUNCTION__." line:".__LINE__;
            $conf[]="http_access allow$annotate_transaction all\n";
            $GLOBALS[__FUNCTION__]=@implode("\n", $conf);
            return $GLOBALS[__FUNCTION__];
        }

        $ACL_MANAGER=$this->ACL_MANAGER();

        $conf[]="#\t ACL_MANAGER F:".__FUNCTION__." line:".__LINE__;
        if($ACL_MANAGER<>null){$conf[]=$ACL_MANAGER;}

        if(isset($GLOBALS["HTTP_ACCESS_ALLOW_TEMPLATES"])){
            if($GLOBALS["HTTP_ACCESS_ALLOW_TEMPLATES"]<>null){
                $conf[]="#\t Allow remote error pages in all ways...F:".__FUNCTION__." line:".__LINE__;
                $conf[]=$GLOBALS["HTTP_ACCESS_ALLOW_TEMPLATES"];
            }
        }

        if(isset($GLOBALS["HTTP_ACCESS_ALLOW_SPLASHS"])){
            if($GLOBALS["HTTP_ACCESS_ALLOW_SPLASHS"]<>null){
                $conf[]="#\t Allow Splash authentications websites for HotSpot...F:".__FUNCTION__." line:".__LINE__;
                $conf[]=$GLOBALS["HTTP_ACCESS_ALLOW_SPLASHS"];
            }
        }


        if(isset($GLOBALS["HTTP_ACCESS"]["ArticaRestrictAccess"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["ArticaRestrictAccess"];}
        if(isset($GLOBALS["HTTP_ACCESS"]["ExtrnQuota"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["ExtrnQuota"];}
        if(isset($GLOBALS["HTTP_ACCESS"]["MacToUid"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["MacToUid"];}





        if(isset($GLOBALS["PROXY_NTLM_SWITCH"])){
            $conf[]="include /etc/squid3/non_ntlm.access";
        }

        $conf[]="http_access allow squidclient manager";
        $conf[]="http_access allow squidclient MgrInfoUri";

        $q=new mysql_squid_builder();

        if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"])){$unix=new unix();$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES();}

        if(isset($GLOBALS["HTTP_ACCESS"]["QUOTAS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["QUOTAS"];}
        if(isset($GLOBALS["HTTP_ACCESS"]["SPLASH"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["SPLASH"];}
        if(!isset($GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"])){$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"]=array();}


        $conf[]="http_access allow to_localhost";




        if(count($GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"])>0){
            $conf[]=implode("\n",$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"]);
        }





        if(isset($GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_MAC"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_MAC"];}
        if(isset($GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS"];}
        if(isset($GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS_MAC"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS_MAC"];}
        if(isset($GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS"];}



        $conf[]="http_access deny purge";
        if($this->EnableUserAgentBanAll==1){$conf[]="http_access deny !AllowedBrowsers";}
        if(isset($GLOBALS["HTTP_ACCESS"]["BLOCKEDSITES"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["BLOCKEDSITES"];}
        if(isset($GLOBALS["HTTP_ACCESS"]["MALWARE_PATROL"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["MALWARE_PATROL"];}
        if(isset($GLOBALS["HTTP_ACCESS"]["ItChart"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["ItChart"];}


        $conf[]="#";
        $conf[]="#";
        $conf[]="# # # # # # # # # # # # # # # # # # RULES ADDED BY ADMINISTRATORS # # # # # # # # # # # # # # # # # # F:".__FUNCTION__." line:".__LINE__;
        $conf[]="#";
        if($this->SquidUrgency==0){
            $conf[]="#\t Global Access Manager for NON-NTLM";
            $conf[]="include /etc/squid3/GlobalAccessManager_auth.conf";
        }
        $conf[]="include /etc/squid3/http_access.conf";
        $conf[]="include /etc/squid3/http_reply_access.conf";
        $conf[]="include /etc/squid3/tcp_outgoing_address.conf";
        $conf[]="include /etc/squid3/tcp_outgoing_mark.conf";

        if(!is_file("/etc/squid3/http_access.conf")){@touch("/etc/squid3/http_access.conf");}
        if(!is_file("/etc/squid3/tcp_outgoing_mark.conf")){@touch("/etc/squid3/tcp_outgoing_mark.conf");}
        if(!is_file("/etc/squid3/tcp_outgoing_address.conf")){@touch("/etc/squid3/tcp_outgoing_address.conf");}
        if(!is_file("/etc/squid3/http_reply_access.conf")){@touch("/etc/squid3/http_reply_access.conf");}
        $conf[]="#\tOthers mandatories ACLS";



        if(isset($GLOBALS["HTTP_ACCESS"]["WHITELISTED_BROWSERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["WHITELISTED_BROWSERS"];}
        if(isset($GLOBALS["HTTP_ACCESS_SPLASH_SCREEN"])){
            $conf[]="#\tHotSpot allowed accesses";
            $conf[]=$GLOBALS["HTTP_ACCESS_SPLASH_SCREEN"];
        }

        $conf[]="#\tIs Transparent ? ". count($this->TRANSPARENT_PORTS_LIST)." items";
        if(count($this->TRANSPARENT_PORTS_LIST)>0){
            if(isset($GLOBALS["HTTP_ACCESS"]["OFFICE_NETWORK"])){
                $conf[]="http_access allow office_network MyTransparentPort";
            }else{
                $conf[]="http_access allow MyTransparentPort";
            }

        }
    $conf[]="#\t Some ports Without auth ?";
        $NoAuthPorts=$this->NoAuthPorts();
        if($NoAuthPorts<>null){
            $conf[]=$GLOBALS["NoAuthPorts_event"];
            $conf[]="http_access allow $NoAuthPorts all";
        }



        if(isset($GLOBALS["HTTP_ACCESS"]["EXTERNAL_ARTICA_HELPER"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["EXTERNAL_ARTICA_HELPER"];}

        if(isset($GLOBALS["HTTP_ACCESS"]["LDAP_GROUP"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["LDAP_GROUP"];}
        if(isset($GLOBALS["HTTP_ACCESS"]["IDENT_SERVICE"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["IDENT_SERVICE"];}
        if(isset($GLOBALS["HTTP_ACCESS"]["ISP_NETWORK"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["ISP_NETWORK"];}
        if(isset($GLOBALS["HTTP_ACCESS"]["OFFICE_NETWORK"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["OFFICE_NETWORK"];}
        if(isset($GLOBALS["HTTP_ACCESS_REVERSE"])){$conf[]=$GLOBALS["HTTP_ACCESS_REVERSE"];}

        foreach ($conf as $val){
            if(trim($val)==null){continue;}
            $f[]=$val;
        }


        $final="\n".@implode("\n",$f)."\n";
        $GLOBALS[__FUNCTION__]=$final;
        return $final;
    }
    private function NETWORK_ALL_INTERFACES(){
        if(isset($GLOBALS["NETWORK_ALL_INTERFACES"])){return $GLOBALS["NETWORK_ALL_INTERFACES"];}

        if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
        $GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES(true);
        $GLOBALS["NETWORK_ALL_INTERFACES"]["0.0.0.0"]=true;
    }
    private function GET_SQUID_VERSION():string{
        exec("/usr/sbin/squid -v 2>&1",$results);
        foreach ($results as $line){
            if(preg_match("#Squid Cache: Version\s+([0-9\.]+)#",$line,$re)){
                return strval($re[1]);
            }
        }
        return strval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion"));
    }
    public function get_ssl_port(){

        if($this->ssl_port>0){
            if($this->isNGnx()){if($this->ssl_port==443){$this->ssl_port=10443;}}
            return $this->ssl_port;
        }

        if($this->second_listen_portForTransparent>0){
            $this->ssl_port=$this->second_listen_portForTransparent+1;
            $this->SaveToLdap();
            return $this->ssl_port;
        }

        if($this->ssl_port==0){
            $this->ssl_port=$this->listen_port+2;
            return $this->ssl_port;
        }
    }
    function acls_multimedias(){
        return null;
        $conf=null;
        $conf=$conf."acl MULTIMEDIA rep_mime_type -i ^(audio\/x-mpegurl|audio\/mpeg|video\/flv|video\/x-flv|application\/x-shockwave-flash|audio\/ogg|video\/ogg|application\/ogg)$\n";
        $conf=$conf."acl multimedia_rep rep_mime_type -i ^video/x-ms-asf$\n";
        $conf=$conf."acl multimedia_rep rep_mime_type -i ^application/vnd.ms.wms-hdr.asfv1$\n";
        $conf=$conf."acl multimedia_rep rep_mime_type -i ^application/x-mms-framed$\n";
        $conf=$conf."acl multimedia_rep rep_mime_type -i ^image/\n";
        $conf=$conf."acl multimedia_rep rep_mime_type -i ^video\n";
        $conf=$conf."acl multimedia_rep rep_mime_type -i ^audio\n";
        $conf=$conf."acl multimedia_rep rep_mime_type -i ^application/x-dvi$\n";
        $conf=$conf."acl multimedia_rep rep_mime_type -i ^application/x-isoview\n";
        $conf=$conf."acl multimedia_browsers browser -i ^.*player\n";
        $conf=$conf."acl bigfiles_types urlpath_regex -i \.(deb|rpm|iso|tar\.gz|gz|bz|tar|cue|nrg|crf|bwi|bwt|lcd|ccd|mdf|mds|vcd|cif|vdi|img)((\?|&).*)?$";

        return $conf;
    }
}

class cicap{
    public $main_array=array();
    public $EnableClamavInCiCap=0;
    public $EnableCicapToSyslog=0;
    public $EnableSquidGuardInCiCAP=1;
    public $EnableUfdbGuard=0;
    public $enable_metascanner=0;
    private $EnableRemoteStatisticsAppliance=0;
    private $AntivirusEnabledCentral=1;
    private $UfdbEnabledCentral=1;
    private $EnableSecurityAppliance=0;
    private $EnableCICAPDNSBL=0;
    private $EnableCICAPRansomware=0;

    function __construct(){

        $ini=new Bs_IniHandler();
        $sock=new sockets();
        $ini->loadString($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapInternalConfiguration"));
        $this->main_array=$ini->_params;
        $this->BuildDefaults();
        $this->EnableClamavInCiCap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavInCiCap"));
        $this->EnableCicapToSyslog=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCicapToSyslog");
        $this->EnableSquidGuardInCiCAP=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidGuardInCiCAP");
        $this->EnableUfdbGuard=$sock->EnableUfdbGuard();
        $this->enable_metascanner=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KavMetascannerEnable");
        $this->EnableRemoteStatisticsAppliance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteStatisticsAppliance");
        $this->UfdbEnabledCentral=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbEnabledCentral");
        $this->AntivirusEnabledCentral=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("AntivirusEnabledCentral");
        $this->EnableSecurityAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSecurityAppliance"));
        $this->EnableCICAPDNSBL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCICAPDNSBL"));
        $this->EnableCICAPRansomware=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCICAPRansomware"));
        if($this->EnableCICAPDNSBL==0){$this->EnableCICAPDNSBL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHTTPSURBL"));}
        if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}
        if(!is_numeric($this->UfdbEnabledCentral)){$this->UfdbEnabledCentral=1;}
        if(!is_numeric($this->AntivirusEnabledCentral)){$this->AntivirusEnabledCentral=1;}
        if(!is_numeric($this->EnableSquidGuardInCiCAP)){$this->EnableSquidGuardInCiCAP=1;}
        if(!is_numeric($this->EnableCicapToSyslog)){$this->EnableCicapToSyslog=1;}

        if($this->EnableRemoteStatisticsAppliance==1){
            $this->EnableUfdbGuard=$this->UfdbEnabledCentral;
        }

        if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
        $users=$GLOBALS["CLASS_USERS"];
        if(!$users->SQUIDGUARD_INSTALLED){$this->EnableSquidGuardInCiCAP=0;}
        if($users->APP_UFDBGUARD_INSTALLED){
            if($this->EnableUfdbGuard==1){
                if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." c-icap disabling squidGuard databases, UfdbGuard is enabled\n";}
                $this->EnableSquidGuardInCiCAP=0;
            }
        }

        if(!$users->APP_KHSE_INSTALLED){$this->enable_metascanner=0;}



    }
    function BuildDefaults(){
        if($this->main_array["CONF"]["Timeout"]==null){$this->main_array["CONF"]["Timeout"]=300;}
        if($this->main_array["CONF"]["KeepAlive"]==null){$this->main_array["CONF"]["KeepAlive"]="on";}
        if($this->main_array["CONF"]["MaxKeepAliveRequests"]==null){$this->main_array["CONF"]["MaxKeepAliveRequests"]="100";}
        if($this->main_array["CONF"]["KeepAliveTimeout"]==null){$this->main_array["CONF"]["KeepAliveTimeout"]="600";}
        if($this->main_array["CONF"]["StartServers"]==null){$this->main_array["CONF"]["StartServers"]="3";}
        if($this->main_array["CONF"]["MaxServers"]==null){$this->main_array["CONF"]["MaxServers"]="30";}
        if($this->main_array["CONF"]["MinSpareThreads"]==null){$this->main_array["CONF"]["MinSpareThreads"]="10";}
        if($this->main_array["CONF"]["MaxSpareThreads"]==null){$this->main_array["CONF"]["MaxSpareThreads"]="20";}
        if($this->main_array["CONF"]["ThreadsPerChild"]==null){$this->main_array["CONF"]["ThreadsPerChild"]="0";}
        if($this->main_array["CONF"]["MaxRequestsPerChild"]==null){$this->main_array["CONF"]["MaxRequestsPerChild"]="0";}
        if($this->main_array["CONF"]["srv_clamav.SendPercentData"]==null){$this->main_array["CONF"]["srv_clamav.SendPercentData"]="5";}
        if($this->main_array["CONF"]["srv_clamav.StartSendPercentDataAfter"]==null){$this->main_array["CONF"]["srv_clamav.StartSendPercentDataAfter"]="2";}
        if($this->main_array["CONF"]["srv_clamav.MaxObjectSize"]==null){$this->main_array["CONF"]["srv_clamav.MaxObjectSize"]="5";}
        if($this->main_array["CONF"]["srv_clamav.ClamAvMaxFilesInArchive"]==null){$this->main_array["CONF"]["srv_clamav.ClamAvMaxFilesInArchive"]="10";}
        if($this->main_array["CONF"]["srv_clamav.ClamAvMaxFileSizeInArchive"]==null){$this->main_array["CONF"]["srv_clamav.ClamAvMaxFileSizeInArchive"]="100";}
        if($this->main_array["CONF"]["srv_clamav.ClamAvMaxRecLevel"]==null){$this->main_array["CONF"]["srv_clamav.ClamAvMaxRecLevel"]="5";}
        if($this->main_array["CONF"]["ThreadsPerChild"]==0){$this->main_array["CONF"]["ThreadsPerChild"]=10;}
        $this->main_array["CONF"]["VirSaveDir"]="/home/c-icap/VirSaveDir";
        if($this->main_array["CONF"]["VirHTTPServer"]==null){$this->main_array["CONF"]["VirHTTPServer"]="https:///exec.cicap.php?usename=%f&remove=1&file=";}
        if($this->main_array["CONF"]["DebugLevel"]==null){$this->main_array["CONF"]["DebugLevel"]="1";}
        if($this->main_array["CONF"]["ViralatorMode"]==null){$this->main_array["CONF"]["ViralatorMode"]="0";}




    }

    function Save(){
        $ini=new Bs_IniHandler();
        $ini->_params=$this->main_array;
        $confArtica=$ini->toString();
        $sock=new sockets();
        $sock->SaveConfigFile($confArtica,'CicapInternalConfiguration');
        $sock->getFrameWork("cmd.php?cicap-reconfigure=yes");
        $sock->getFrameWork("cmd.php?cicap-reload=yes");
    }

}