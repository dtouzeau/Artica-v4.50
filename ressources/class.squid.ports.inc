<?php

include_once(dirname(__FILE__).'/class.squid.certificates.inc');

class squid_ports{
    private $ASROOT=true;
    private $EnableTransparent27=0;
    private $MGR_LISTEN_PORT=0;
    private $SquidUrgency=0;
    private $EnableSSLOnStandardPort=0;
    private $EnableSquidSSLCRTD=1;
    public $TRANSPARENT_PORTS_LIST=array();
    private $SquidBubbleMode=0;


    public function __construct(){
        $this->EnableTransparent27=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTransparent27"));
        $this->MGR_LISTEN_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
        $this->SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
        $this->EnableSSLOnStandardPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSSLOnStandardPort"));
        $this->SquidBubbleMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidBubbleMode"));
        $this->NETWORK_ALL_INTERFACES();
        if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"])){$unix=new unix();$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES();}
        if(!isset($GLOBALS["NETWORK_ALL_NICS"])){$unix=new unix();$GLOBALS["NETWORK_ALL_NICS"]=$unix->NETWORK_ALL_INTERFACES();}
    }
    private function sslcrtd_program_path(){
        if($this->SquidUrgency==1){return null;}
        if(isset($GLOBALS["sslcrtd_program"])){return $GLOBALS["sslcrtd_program"];}
        if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
        $GLOBALS["sslcrtd_program"] = $unix->squid_locate_generic_bin("ssl_crtd");
        return  $GLOBALS["sslcrtd_program"];
    }
    private function ssl_bump_port(){}
    private function myport_token(){return "localport";}
    private function ssl_bump_token(){return "ssl-bump";}
    private function transparent_token(){return" intercept";}


    private function x_forwarded_for(){

        $x                  = 0;
        $HaClusterClient    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));

        if($HaClusterClient==1) {

            $HaClusterIP = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterIP");
            if (preg_match("#http(s|):\/\/(.+?):[0-9]+#", $HaClusterIP, $re)) {
                $f[] = "# HaCluster, Load-balancer is $HaClusterIP";
                $HaClusterIP = $re[2];
            }
            $f[] = "acl FollowXForwardedIP src $HaClusterIP";
            $x++;
        }





        $IpClass        = new IP();
        $q              = new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $sql            = "SELECT *  FROM `squid_balancers` WHERE enabled=1";
        $results        = $q->QUERY_SQL($sql);



        if($q->ok) {
            $f[]="# squid_balancers ".count($results)." item(s)";
            foreach ($results as $index => $ligne) {
                if (!$IpClass->isIPAddressOrRange($ligne["ipsrc"])) {
                    $f[] = "#{$ligne["ipsrc"]} invalid";
                    continue;
                }
                $x++;
                $f[] = "acl FollowXForwardedIP src {$ligne["ipsrc"]}";
            }
        }

        $f[]="# Final $x item(s)";
        if($x==0){return @implode("\n",$f);}
        $f[]="follow_x_forwarded_for allow FollowXForwardedIP";
        $f[]="acl_uses_indirect_client         on";
        $f[]="delay_pool_uses_indirect_client  on";
        $f[]="log_uses_indirect_client         on";

        return @implode("\n",$f);

    }


    public function BuildPorts(){
        $this->NETWORK_ALL_INTERFACES();
        $sock                           = new sockets();
        $unix                           = new unix();
        $conf_outgoing_addr             = array();
        $DenySquidWriteConf             = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DenySquidWriteConf"));
        if($DenySquidWriteConf==1){return "";}
        $php                            = $unix->LOCATE_PHP5_BIN();
        $q                              = new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $squid_certificate              = new squid_certificate();
        $http_port_sslbump              = $this->ssl_bump_port();
        $conf_acls                      = array();
        $HaClusterGBConfig              = array();
        $HaClusterTransParentMode       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode"));
        $HaClusterTproxy                = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTproxy"));
        $HaClusterClient                = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        $EnableActiveDirectoryFeature   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
        $SquidWCCPL3ProxPort            = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWCCPL3ProxPort"));
        $SquidWCCPL3SSLProxPort         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWCCPL3SSLProxPort"));
        $SquidSSLUrgency                = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSSLUrgency"));
        $SquidHotSpotPort               = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHotSpotPort"));
        $SquidHotSpotSSLPort            = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHotSpotSSLPort"));
        $SquidWCCPL3SSServiceID         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWCCPL3SSServiceID"));
        $DisableSSLStandardPort         = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableSSLStandardPort");
        $SquidAllow80Port               = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAllow80Port"));
        $this->MGR_LISTEN_PORT          = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
        $EnableParentProxy              = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableParentProxy"));
        $NETWORK_ALL_INTERFACES         = $unix->NETWORK_ALL_INTERFACES();
        $this->EnableSquidSSLCRTD       = 1;
        $ArticaSplashHotSpotCertificate = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaSplashHotSpotCertificate");
        $myportToKen                    = $this->myport_token();
        $transparent_token              = $this->transparent_token();
        $HaClusterProxyPort             = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProxyPort"));

        if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$EnableActiveDirectoryFeature=0;}

        if(!is_numeric($DisableSSLStandardPort)){$DisableSSLStandardPort=1;}
        if($this->EnableSSLOnStandardPort==1){$this->DisableSSLStandardPort=0;$DisableSSLStandardPort=0;}
        if($DisableSSLStandardPort==1){$this->EnableSSLOnStandardPort=0;}
        if($SquidHotSpotPort==0){$SquidHotSpotPort=rand(40000, 64000);$sock->SET_INFO("SquidHotSpotPort", $SquidHotSpotPort);}
        if($SquidHotSpotSSLPort==0){$SquidHotSpotSSLPort=rand(40500, 64000);$sock->SET_INFO("SquidHotSpotSSLPort", $SquidHotSpotSSLPort);}
        if($SquidWCCPL3ProxPort==0){$SquidWCCPL3ProxPort=rand(40600, 64000);$sock->SET_INFO("SquidWCCPL3ProxPort", $SquidWCCPL3ProxPort);}
        if($SquidWCCPL3SSLProxPort==0){$SquidWCCPL3SSLProxPort=rand(40700, 64000);$sock->SET_INFO("SquidWCCPL3SSLProxPort", $SquidWCCPL3SSLProxPort);}
        if(!$sock->CORP_LICENSE()){$HaClusterClient=0;}

        if($HaClusterClient==1) {
            $ssl_bump_line=null;
            $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
            if(!isset($HaClusterGBConfig["HaClusterDisableProxyProtocol"])){$HaClusterGBConfig["HaClusterDisableProxyProtocol"]=0;}
            $HaClusterDisableProxyProtocol=intval($HaClusterGBConfig["HaClusterDisableProxyProtocol"]);
            if(intval($HaClusterGBConfig["HaClusterDecryptSSL"])==1){
                $conf[]="#  HaCluster Decrypt SSL is Enabled";
                if(!$this->HaClusterReplaceCertif()){
                    $conf[]="#  HaCluster Decrypt SSL HaClusterReplaceCertif() report false";
                    $HaClusterGBConfig["HaClusterDecryptSSL"]=0;
                }
            }
        }


        if(!isset($HaClusterGBConfig["HaClusterDecryptSSL"])){$HaClusterGBConfig["HaClusterDecryptSSL"]=0;}
        if($this->MGR_LISTEN_PORT==0){$this->MGR_LISTEN_PORT=rand(50000,64000);}
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidMgrListenPort", $this->MGR_LISTEN_PORT);
        shell_exec("/usr/sbin/artica-phpfpm-service -sslcrtd");

        $conf[]="# saved on ".date("Y-m-d H:i:s");
        $conf[]="#";



        if($SquidSSLUrgency==1){$conf[]="# # # SSL EMERGENCY MODE TURN OFF SSL # # #";}
        $conf[]="# --------- Manager Port...........: $this->MGR_LISTEN_PORT [".__LINE__."]";
        $conf[]="# --------- NAT compatible.........: $this->EnableTransparent27 [".__LINE__."]";


        $conf_acls[]="";
        $conf_acls[]="";
        $conf_acls[]="# ---------------------------- Allow to following x-forwarded-for";
        $conf_acls[]                    = $this->x_forwarded_for();
        $conf_acls[] = "acl DenyICAPDomains dstdomain .kaspersky.com";
        $conf_acls[] = "acl DenyICAPDomains dstdomain .kaspersky-labs.com";

        if($EnableParentProxy==0) {
            $conf[] = "pinger_enable off";
        }



        $StandardPorts[]="acl NormalPorts myportname MyManagerPort";


        $conf[]="http_port 127.0.0.1:$this->MGR_LISTEN_PORT name=MyManagerPort";
        $conf[]="acl NoCacheMGR myportname MyManagerPort";
        $conf[]="acl ToArticaWWW dstdomain .artica.fr .articatech.net .articatech.com";
        $conf[]="acl ArticaTSTSite dstdomain .artica.me";
        $conf[]="acl ArticaTSTSiteAll src all";
        $conf[]="acl LocalClient src 127.0.0.1";
        $conf[]="http_access deny ArticaTSTSite ArticaTSTSiteAll";
        $conf[]="deny_info ERR_TEST_SITE ArticaTSTSiteAll";
        $conf[]="cache deny NoCacheMGR";
        $conf[]="send_hit deny NoCacheMGR";
        $conf[]="store_miss deny NoCacheMGR";
        $conf[]="http_access allow NoCacheMGR ToArticaWWW";
        $conf[]="http_access allow LocalClient NoCacheMGR";
        $conf_acls[] = "acl NoAuthPort myportname MyManagerPort";
        $conf_acls[] = "acl MyIPSrc src 127.0.0.1";
        $conf_acls[] = "acl StandardPort port 80 443";
        foreach ($NETWORK_ALL_INTERFACES as $nic=>$array){
            if(!isset($array["IPADDR"])){continue;}
            $IPADDR=$array["IPADDR"];
            if($IPADDR==null){continue;}
            if($IPADDR=="0.0.0.0"){continue;}
            if($IPADDR=="127.0.0.1"){continue;}
            $conf_acls[]="# Local $nic -> IP $IPADDR";
            $conf_acls[]="acl MyIPSrc src $IPADDR";
        }
        $conf_acls[]="http_access allow MyIPSrc NoCacheMGR";

        include_once(dirname(__FILE__)."/class.squid.ports.patch.inc");
        Patch_table_squid_ports();



        $StandardPorts=array();
        $TransparentPorts=array();
        $ConfiguredPorts=0;
        $MIKROTIK_FIREWALL=array();
        $TRANSPARENT_FIREWALL=array();
        $WCCP_FIREWALL=array();
        $PORTS_DUPLICATE=array();
        $NAT_FIREWALL=array();
        $PROXY_PROTOCOL_ENABLED=false;
        $DCP=false;
        $NoCacheCount=0;
        $NoFilterCount=0;

        $chown=$unix->find_program("chown");
        $conf[]=$this->mikrotik_ports();
        $sql="SELECT * FROM transparent_ports WHERE enabled=1";
        $results = $q->QUERY_SQL($sql);

        $conf[]="#";
        $conf[]="# ---------------------------- TRANSPARENT PORTS ". count($results)." Intercept ports";
        $conf[]="#";


        $AD_CONFIGURED=false;
        $authenticate=explode("\n",@file_get_contents("/etc/squid3/authenticate.conf"));
        foreach ($authenticate as $line){

            if(preg_match("#auth_param.*?negotiate_kerberos_auth#",$line)){
                $AD_CONFIGURED=true;
                break;
            }
        }

        if($AD_CONFIGURED){
            if($EnableActiveDirectoryFeature==0){
                shell_exec(" /usr/sbin/artica-phpfpm-service -ad-uninstall");
            }
        }




        foreach ($results as $index=>$ligne){
            $ipaddr="0.0.0.0";
            $localport=$ligne["localport"];
            $NoCache=$ligne["NoCache"];
            $NoFilter=$ligne["NoFilter"];
            $sslcertificate=trim($ligne["sslcertificate"]);

            $this->TRANSPARENT_PORTS_LIST[]=$localport;
            $transparent_text=$transparent_token." ";
            $http_port_text="http_port";
            $ssl_bump_line=null;
            $EnableipV6=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6"));


            if($EnableipV6==0){
                echo "Activate ipv6 configuration...\n";
                $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableipV6",1);
                shell_exec("$php /usr/share/artica-postfix/exec.sysctl.php --build");
                shell_exec("$php /usr/share/artica-postfix/exec.sysctl.php --start");
            }


            $AclPortName="MyPortNameT{$ligne["ID"]}";
            $TransparentPorts[]=$AclPortName;

            if($NoCache==1){$conf_acls[]="acl NoCachePort myportname $AclPortName";$NoCacheCount++;}
            if($NoFilter==1){$conf_acls[]="acl NoFilter myportname $AclPortName";$NoFilterCount++;}
            if($ligne["TProxy"]==1){$transparent_text="tproxy";}
            if($sslcertificate<>null) {
                if($SquidSSLUrgency==1){continue;}
                $squid_certificate=new squid_certificate();
                $ssl_bump_line=$this->ssl_bump_token()." ".$squid_certificate->BuildSquidCertificate($ligne["sslcertificate"]);
                $http_port_text = "https_port";
                if(!is_dir("/var/cache/squid")){@mkdir("/var/cache/squid",0755,true);}
                if(!is_dir("/etc/squid3/ssl")){@mkdir("/etc/squid3/ssl",0755,true);}
                system("$chown squid:squid /var/cache/squid");
                system("$chown -R squid:squid /etc/squid3");

                system("$chown -R squid:squid /etc/squid3");
                system("$chown -R squid:squid /var/cache/squid");

            }

            if($ipaddr<>null){$ipaddr="$ipaddr:";}

            $conf[]="{$http_port_text} {$ipaddr}$localport $transparent_text name=$AclPortName {$ssl_bump_line} ";
            $conf_acls[]="acl MyPort{$ligne["ID"]} myportname $AclPortName";
            $ConfiguredPorts++;
        }
        $conf[]="#";
        $conf[]="#";
        $conf[]="#";


        $results = $q->QUERY_SQL("SELECT * FROM proxy_ports WHERE enabled=1");
        $conf[]="# ---------------------------- NORMAL PORTS ". count($results)." ports";

        foreach ($results as $index=>$ligne){
            if(!isset($ligne["WCCP"])){$ligne["WCCP"]=0;}
            if(!isset($ligne["MIKROTIK_PORT"])){$ligne["MIKROTIK_PORT"]=0;}
            if(!isset($ligne["FTP"])){$ligne["FTP"]=0;}
            if(!isset($ligne["WANPROXY"])){$ligne["WANPROXY"]=0;}
            if(!isset($ligne["Parent"])){$ligne["Parent"]=0;}
            if(!isset($ligne["subcertificate"])){$ligne["subcertificate"]=0;}

            if(!isset($ligne["nic"])){$ligne["nic"]=null;}
            if(!isset($ligne["FTP_TRANSPARENT"])){$ligne["FTP_TRANSPARENT"]=null;}
            if(!isset($ligne["NoAuth"])){$ligne["NoAuth"]=0;}
            if(!isset($ligne["ProxyProtocol"])){$ligne["ProxyProtocol"]=0;}
            if(!isset($ligne["SSLPort"])){$ligne["SSLPort"]=0;}
            if(!isset($ligne["AuthSSL"])){$ligne["AuthSSL"]=0;}
            if(!isset($ligne["Params"])){$ligne["Params"]=serialize(array());}
            $AuthSSL=intval($ligne["AuthSSL"]);
            $SSLPort=intval($ligne["SSLPort"]);
            $subcertificate=intval($ligne["subcertificate"]);
            $ProxyProtocol=$ligne["ProxyProtocol"];
            $port=intval($ligne["port"]);
            $eth=$ligne["nic"];
            $http_port_text="http_port";
            $UseSSL=intval($ligne["UseSSL"]);
            $Params=unserialize($ligne["Params"]);
            $tcpkeepalive_line=null;

            if($HaClusterClient==1){
                if($port==$HaClusterProxyPort){
                    $ProxyProtocol=1;
                    if($HaClusterDisableProxyProtocol==1){$ProxyProtocol=0;}
                    $f[]="# HaCluster: Port $port ProxyProtocol = $ProxyProtocol, Disable Proxy Protocol: $HaClusterDisableProxyProtocol";
                    if(intval($HaClusterGBConfig["HaClusterDecryptSSL"])==1){
                        $f[]="# HaCluster: Decrypt SSL with {$HaClusterGBConfig["HaClusterCertif"]}";
                        $ligne["sslcertificate"]=$HaClusterGBConfig["HaClusterCertif"];
                        $UseSSL=1;
                        $ligne["UseSSL"]=1;
                    }
                }
            }


            if($SSLPort==1){
                $UseSSL=1;
                if($subcertificate==0){
                    $conf[]="# --------- ID {$ligne["ID"]} ssl port but no INT sub-certificate defined";
                    continue;
                }
                $http_port_text="https_port";
            }

            $ssl_bump_line=null;
            $transparent_text=null;
            $Parent=$ligne["Parent"];
            $WANPROXY=intval($ligne["WANPROXY"]);

            $FTP_TRANSPARENT=intval($ligne["FTP_TRANSPARENT"]);
            $FTP=intval($ligne["FTP"]);

            $ProxyProtocol_text=null;

            if($port==$this->MGR_LISTEN_PORT){
                $conf[]="# --------- ID {$ligne["ID"]} $port==$this->MGR_LISTEN_PORT ( not allowed )";
                continue;
            }

            $NoAuth=intval($ligne["NoAuth"]);
            $NoCache=intval($ligne["NoCache"]);
            $NoFilter=intval($ligne["NoFilter"]);

            if($FTP==1){continue;}
            $AclPortName="MyPortNameID{$ligne["ID"]}";

            if($ProxyProtocol==1){
                $ProxyProtocol_text=" require-proxy-header";
                $PROXY_PROTOCOL_ENABLED=true;
            }



            $ipaddr="0.0.0.0";
            $conf[]="# --------- ID {$ligne["ID"]} NIC:$eth/$port/Out:{$ligne["outgoing_addr"]} SSLPort:$SSLPort FTP=$FTP,FTP_TRANSPARENT=$FTP_TRANSPARENT,SquidAllow80Port=$SquidAllow80Port (".__LINE__.")";
            if($SquidAllow80Port==0){
                if($port==80){
                    $conf[]="# --------- -> 80 is not allowed -> abort[".__LINE__."]\n";
                    continue;
                }
                if($port==443){
                    $conf[]="# --------- -> 443 is not allowed -> abort[".__LINE__."]\n";
                    continue;
                }

                if($port==21){
                    $conf[]="# --------- -> 21 is not allowed -> abort[".__LINE__."]\n";
                    continue;
                }

            }


            if($WANPROXY==1){
                $conf[]="#";
                $conf[]="#";
                $conf[]="# --------- WANPROXY: {$ligne["WANPROXY_PORT"]} --------- ";
                $conf[]="http_port 127.0.0.1:{$ligne["WANPROXY_PORT"]} name=$AclPortName";
                $conf[]="acl XForward{$ligne["ID"]} myportname $AclPortName";
                $conf[]="follow_x_forwarded_for allow XForward{$ligne["ID"]}";
                $conf[]="#";
                $conf[]="#";
                continue;
            }

            if($eth<>null){
                if($eth=="lo"){$ipaddr="0.0.0.0";}
                if($eth<>"lo"){
                    $ipaddr=$GLOBALS["NETWORK_ALL_NICS"][$eth]["IPADDR"];
                    $conf[]="# --------- table proxy_ports $eth $ipaddr:$port [".__LINE__."]\n";
                    if($ipaddr==null){
                        $conf[]="# --------- table proxy_ports $eth $ipaddr:$port -> Hardware Error [".__LINE__."]\n";
                        $conf[]="# --------- http $ipaddr -> Hardware Error -> 0.0.0.0 [".__LINE__."]\n";
                        $ipaddr="0.0.0.0";
                    }
                }
            }




            if($UseSSL==1){
                if($SquidSSLUrgency==0){
                    $squid_certificate=new squid_certificate();
                    $SquidTransparentSSLCert=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTransparentSSLCert"));
                    if($ligne["sslcertificate"]==null){if($SquidTransparentSSLCert<>null){$ligne["sslcertificate"]=$SquidTransparentSSLCert;}}

                    if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [SSL]: Use certificate \"{$ligne["sslcertificate"]}\" from table ".__LINE__."\n";}
                    $conf[]="# Use certificate \"{$ligne["sslcertificate"]}\" [".__LINE__."]";

                    $ssl_bump_token=$this->ssl_bump_token();
                    if($ligne["SSLPort"]==1){
                        $ssl_bump_token=null;
                        $squid_certificate->SetSSLPort($subcertificate);
                        if($AuthSSL==1){$squid_certificate->SetSSLClients();}
                        $conf[]="# Use Server Certificate ID \"{$subcertificate}\" [".__LINE__."]";
                    }

                    //tls-cert=

                    $ssl_bump_line="$ssl_bump_token ".$squid_certificate->BuildSquidCertificate($ligne["sslcertificate"]);


                    if(count($GLOBALS["BuildSquidCertificate"])>0){
                        foreach ($GLOBALS["BuildSquidCertificate"] as $y){
                            $conf[]="# $y";
                        }
                    }
                }

            }


            if($Parent==0){
                if($NoAuth==0){
                    $this->MyPortsArray[]=$port;
                    $StandardPorts[]="acl NormalPorts myportname $AclPortName";
                }
            }

            if(!isset($Params["tcpkeepalive"]["enabled"])){
                $Params["tcpkeepalive"]["enabled"]=0;
            }

            if($Params["tcpkeepalive"]["enabled"]==1) {
                if (isset($Params["tcpkeepalive"]["idle"])) {
                    $tcpkeepaliveidle = intval($Params["tcpkeepalive"]["idle"]);
                    $tcpkeepaliveinterval = intval($Params["tcpkeepalive"]["interval"]);
                    $tcpkeepalivetimeout = intval($Params["tcpkeepalive"]["timeout"]);
                    if ($tcpkeepaliveidle > 0 and $tcpkeepaliveinterval > 0 and $tcpkeepalivetimeout > 0) {
                        $tcpkeepalive_line = " tcpkeepalive=$tcpkeepaliveidle,$tcpkeepaliveinterval,$tcpkeepalivetimeout";
                    }
                }
            }



            $ConfiguredPorts++;
            $conf[]="$http_port_text $ipaddr:$port $transparent_text$ProxyProtocol_text name=$AclPortName $ssl_bump_line$tcpkeepalive_line";
            // Migration to go Here.
            $conf_acls[]="acl MyPort{$ligne["ID"]} myportname $AclPortName";
            if($HaClusterClient==1){
                if($HaClusterDisableProxyProtocol==1){
                    $conf_acls[]="follow_x_forwarded_for allow MyPort{$ligne["ID"]}";
                }
            }

            if($ProxyProtocol==1){$conf_acls[]="follow_x_forwarded_for allow MyPort{$ligne["ID"]}";}
            if($NoAuth==1){$conf_acls[]="acl NoAuthPort myportname $AclPortName";}
            if($NoCache==1){$conf_acls[]="acl NoCachePort myportname $AclPortName";$NoCacheCount++;}
            if($NoFilter==1){$conf_acls[]="acl NoFilter myportname $AclPortName";$NoFilterCount++;}
        }

// Find de la boucle.

        $conf[]="#  HaCluster Transparent Port Enable = $HaClusterClient / $HaClusterTransParentMode  / $HaClusterTproxy";
        if($HaClusterClient==1) {

            if(intval($HaClusterGBConfig["HaClusterDecryptSSL"])==1){
                $ssl_bump_token=$this->ssl_bump_token();
                $squid_certificate=new squid_certificate();
                $ssl_bump_line="$ssl_bump_token ".$squid_certificate->BuildSquidCertificate($ligne["sslcertificate"]);
            }

            if ($HaClusterTransParentMode == 1) {
                if ($HaClusterTproxy > 0) {
                    $conf[] = "http_port 0.0.0.0:$HaClusterTproxy name=HaClusterTransparent {$ssl_bump_line}";
                    $conf[] = "";
                    $conf_acls[] = "acl NoAuthPort myportname HaClusterTransparent";
                    $conf_acls[] = "acl HaClusterT myportname HaClusterTransparent";
                    $conf_acls[] = "follow_x_forwarded_for allow NoAuthPort";
                    $TransparentPorts[]="HaClusterTransparent";
                    $this->TRANSPARENT_PORTS_LIST[]=$HaClusterTproxy;

                    $ConfiguredPorts++;
                }

            }
        }

        $conf[]="# Configured ports = $ConfiguredPorts [".__LINE__."]";
        if($ConfiguredPorts==0){
            $conf[]="# No Configured port assume 3128 and 8080 by default [".__LINE__."]";
            $conf[]="http_port 0.0.0.0:3128 name=Port3128";
            $conf[]="http_port 0.0.0.0:8080 name=Port8080";
            $StandardPorts[]="acl NormalPorts myportname Port3128";
            $StandardPorts[]="acl NormalPorts myportname Port8080";
        }


        $conf[]="#";
        $conf[]="# Communication ports..........................";
        $SquidICPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidICPPort"));
        $SquidHTCPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTCPPort"));
        $udp_incoming_address=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("udp_incoming_address"));
        $udp_outgoing_address=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("udp_outgoing_address"));
        $conf[]="icp_port $SquidICPPort";
        $conf[]="htcp_port $SquidHTCPPort"; // should 4827
        $unix=new unix();
        if($udp_incoming_address==null){$udp_incoming_address="0.0.0.0";}else{$udp_incoming_address=$unix->InterfaceToIPv4($udp_incoming_address);}
        if($udp_outgoing_address==null){$udp_outgoing_address="255.255.255.255";}else{$udp_outgoing_address=$unix->InterfaceToIPv4($udp_outgoing_address);}
        if($udp_incoming_address<>null){$conf[]="udp_incoming_address $udp_incoming_address";}

        if($udp_outgoing_address<>$udp_incoming_address){
            if($udp_incoming_address<>null){$conf[]="# udp_outgoing_address $udp_outgoing_address";}
        }


        $conf[]="#";
        $conf[]="#";


        $conf[]="# Standards ports count:".count($StandardPorts);
        if(count($StandardPorts)==0){
            $StandardPorts[]="acl NormalPorts myportname MyManagerPort";
        }

        $conf[]=@implode("\n", $StandardPorts);

        if(count($TransparentPorts)>0){
            $conf[]="acl MyTransparentPort myportname ".@implode(" ", $TransparentPorts);
        }

        $conf[]=@implode("\n", $conf_acls);
        $conf[]=@implode("\n", $conf_outgoing_addr);
        $SquidTOS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTOS"));

        if($DCP){
            if($SquidTOS==0){
                $ayDscp = array(0 => '0x00',8 => '0x20',10 => '0x28',12 => '0x30',14 => '0x38',16 => '0x40',18 => '0x48',20 => '0x50',22 => '0x58',24 => '0x60',26 => '0x68',28 => '0x70',30 => '0x78',32 => '0x80',34 => '0x88',36 => '0x90',38 => '0x98',40 => '0xA0',46 => '0xB8',48 => '0xC0',56 => '0xE0');
                $SquidMikroTikTOS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMikroTikTOS"));
                if($SquidMikroTikTOS==0){$SquidMikroTikTOS=12;}
                $conf[]="qos_flows tos local-hit={$ayDscp[$SquidMikroTikTOS]}";
            }
        }
        if($SquidTOS>0){
            $ayDscp = array(0 => '0x00',8 => '0x20',10 => '0x28',12 => '0x30',14 => '0x38',16 => '0x40',18 => '0x48',20 => '0x50',22 => '0x58',24 => '0x60',26 => '0x68',28 => '0x70',30 => '0x78',32 => '0x80',34 => '0x88',36 => '0x90',38 => '0x98',40 => '0xA0',46 => '0xB8',48 => '0xC0',56 => '0xE0');
            $conf[]="qos_flows tos local-hit={$ayDscp[$SquidTOS]}";

        }

        if($PROXY_PROTOCOL_ENABLED){
            $conf[]="proxy_protocol_access allow all";

        }


        if($NoCacheCount>0){
            $conf[]="cache deny NoCachePort";
            $conf[]="send_hit deny NoCachePort";
            $conf[]="store_miss deny NoCachePort";
        }

        if($NoFilterCount>0){$conf[]="url_rewrite_access deny NoFilter";}


        $conf[]="#";
        $conf[]="#";
        $conf[]="acl internal_icons urlpath_regex −i /squid-internal-static/";
        $conf[]="acl internal_icons urlpath_regex −i /hotspot\.php";
        $conf[]="http_access allow internal_icons";
        $conf[]="include /etc/squid3/anonymous.conf";
        if(!is_file("/etc/squid3/anonymous.conf")){@touch("/etc/squid3/anonymous.conf");}
        echo "Saving /etc/squid3/listen_ports.conf\n";
        @file_put_contents("/etc/squid3/listen_ports.conf",  @implode("\n",$conf)."\n");
        @file_put_contents("/etc/squid3/MIKROTIK_FIREWALL.array", serialize($MIKROTIK_FIREWALL));
        @file_put_contents("/etc/squid3/TRANSPARENT_FIREWALL.array", serialize($TRANSPARENT_FIREWALL));
        @file_put_contents("/etc/squid3/WCCP_FIREWALL.array", serialize($WCCP_FIREWALL));
        @file_put_contents("/etc/squid3/NAT_FIREWALL.array", serialize($NAT_FIREWALL));
        return "include /etc/squid3/listen_ports.conf\n";


    }

    private function NETWORK_ALL_INTERFACES(){
        if(isset($GLOBALS["NETWORK_ALL_INTERFACES"])){return $GLOBALS["NETWORK_ALL_INTERFACES"];}

        if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
        $GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES(true);
        $GLOBALS["NETWORK_ALL_INTERFACES"]["0.0.0.0"]=true;
    }


    private function HaClusterReplaceCertif(){
        $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
        $Certif=$HaClusterGBConfig["HaClusterCertif"];
        $DataCertif=unserialize(base64_decode($HaClusterGBConfig["CertifData"]));
        $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM sslcertificates WHERE CommonName='$Certif'");
        $ID=intval($ligne["ID"]);

        foreach ($DataCertif as $key => $val) {
            if(is_numeric($key)){continue;}
            if($key=="ID"){continue;}
            $zKeys[] = "$key";
            $zVals[] = "'" . $q->sqlite_escape_string2($val) . "'";
            $zEdit[] = "$key = '". $q->sqlite_escape_string2($val) . "'";
        }

        if($ID==0) {
            $sql="INSERT INTO sslcertificates (".@implode(",",$zKeys).") 
            VALUES (".@implode(",",$zVals).")";
        }else {
            $sql="UPDATE sslcertificates SET ".@implode(",", $zEdit)." WHERE ID=$ID";

        }
        $q->QUERY_SQL($sql);
        if(!$q->ok){return false;}
        return true;
    }

    private function mikrotik_ports(){
        $unix=new unix();
        $SquidMikrotikEnabled       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMikrotikEnabled"));
        $f[]="# ------------------- MIKROTIK -------------------";
        $f[]="# Mikrotik port enabled = $SquidMikrotikEnabled";
        if($SquidMikrotikEnabled==0){return @implode("\n");}
        $HTTP_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MikrotikHTTPPort"));
        $HTTPS_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MikrotikSSLPort"));
        $MikrotikSSLCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MikrotikSSLCertificate"));
        $MikrotikInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MikrotikInterface");
        if($MikrotikInterface==null){$MikrotikInterface="eth0";}

        $this->TransparentPorts[]="MikrotikPortHTTP";
        $this->TRANSPARENT_PORTS_LIST[]=$HTTP_PORT;

        $MikrotikInterfaceIP="0.0.0.0";
        if($unix->is_interface_available($MikrotikInterface)){
            $MikrotikInterfaceIP=$unix->InterfaceToIPv4($MikrotikInterface);
            if($MikrotikInterfaceIP==null){$MikrotikInterfaceIP="0.0.0.0";}
        }

        $f[]="http_port $MikrotikInterfaceIP:$HTTP_PORT tproxy name=MikrotikPortHTTP";

        if($MikrotikSSLCertificate<>null) {
            $this->TRANSPARENT_PORTS_LIST[] = $HTTPS_PORT;
            $this->TransparentPorts[] = "MikrotikPortSSL";
            $squid_certificate=new squid_certificate();
            $sslconf=$squid_certificate->BuildSquidCertificate($MikrotikSSLCertificate);
            $f[]="https_port $MikrotikInterfaceIP:$HTTPS_PORT tproxy ssl-bump $sslconf name=MikrotikPortSSL";
        }

        $f[]="# ------------------------------------------------\n";
        return @implode("\n",$f);
    }

}