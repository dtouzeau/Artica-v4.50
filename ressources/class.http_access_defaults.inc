<?php
include_once(dirname(__FILE__)."/class.squid.acls.useragents.inc");
include_once(dirname(__FILE__)."/class.squid.ntlm.inc");

class http_access_defaults{
    private $SquidDisableAllFilters=0;
    private $SquidUrgency=0;
    private $EnableKerbAuth=0;
    private $LockActiveDirectoryToKerberos=0;
    private $UseNativeKerberosAuth=0;
    private $WindowsActiveDirectoryKerberos=0;
    private $smartphones_port=0;
    private $SquidLdapAuthEnableGroups=0;
    private $LDAP_AUTH=0;
    private $SquidEnableIdentdServiceOnly=0;
    private $ProxyDedicateMicrosoftRules=0;
    private $BasicAuthenticatorEmergency=0;
    private $SquidAllowSmartPhones=0;
    private $EnableFakeAuth=0;
    private $AllowWindowsUpdates=0;
    private $SquidVersion=null;
    private $LOGS=array();
    private $ActiveDirectory_error=null;
    private $isSquid5=false;


    function __construct(){
        if(!is_file("/etc/artica-postfix/settings/Daemons/AllowWindowsUpdates")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("AllowWindowsUpdates", 1);}
        if(!is_file("/etc/artica-postfix/settings/Daemons/AllowAllNetworksInSquid")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("AllowAllNetworksInSquid", 1);}
        if(!is_file("/etc/artica-postfix/settings/Daemons/AllowSquidCompatibility")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("AllowSquidCompatibility", 1);}
        $this->smartphones_port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("smartphones_port"));
        $this->SquidDisableAllFilters=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableAllFilters"));
        $this->SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
        $this->EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
        $this->LDAP_AUTH=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidStandardLDAPAuth"));
        $this->LOGS[]="# SquidStandardLDAPAuth = $this->LDAP_AUTH";
        $EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
        $this->LOGS[]="# EnableOpenLDAP = $EnableOpenLDAP";
        $this->SquidLdapAuthEnableGroups=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLdapAuthEnableGroups"));
        $this->SquidEnableIdentdServiceOnly=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidEnableIdentdServiceOnly"));
        $this->ProxyDedicateMicrosoftRules=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyDedicateMicrosoftRules"));
        $this->BasicAuthenticatorEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BasicAuthenticatorEmergency"));
        $this->SquidAllowSmartPhones=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAllowSmartPhones"));
        $this->EnableFakeAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFakeAuth"));
        $this->LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){$this->LockActiveDirectoryToKerberos=1;}


        $this->UseNativeKerberosAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseNativeKerberosAuth"));
        $this->WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){$this->LockActiveDirectoryToKerberos=1;}

        $this->SquidVersion=$this->GET_SQUID_VERSION();
        if(preg_match("#^(5|6|7)\.#",$this->SquidVersion)){$this->isSquid5=true;}

        if($this->LockActiveDirectoryToKerberos==1) {
            if (!is_file("/etc/squid3/krb5.keytab")) {
                $this->LockActiveDirectoryToKerberos = 0;
                $this->ActiveDirectory_error="# /etc/squid3/krb5.keytab";
            }
            if(is_file("/etc/squid3/krb5.keytab")){
                $this->LockActiveDirectoryToKerberos = 1;
                $this->ActiveDirectory_error=null;
            }

        }

        if($this->LockActiveDirectoryToKerberos==1){$this->EnableKerbAuth=1;}
        $SquidRadiusAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRadiusAuth"));
        $this->LOGS[]="# SquidRadiusAuth = $SquidRadiusAuth";

        if($EnableOpenLDAP==0){$this->LDAP_AUTH=0;$this->LOGS[]="# LDAP_AUTH = 0 caused by EnableOpenLDAP";}
        if($SquidRadiusAuth==1){$this->LDAP_AUTH=1;}
        if($this->EnableKerbAuth==1
            or $this->LockActiveDirectoryToKerberos==1
            or $this->WindowsActiveDirectoryKerberos==1
            or $this->UseNativeKerberosAuth==1
        ){$this->LDAP_AUTH=0;}
    }

    private function GET_SQUID_VERSION():string{
        if(isset($GLOBALS["GET_SQUID_VERSION"])){return $GLOBALS["GET_SQUID_VERSION"];}
        exec("/usr/sbin/squid -v 2>&1",$results);
        foreach ($results as $line){
            if(preg_match("#Squid Cache: Version\s+([0-9\.]+)#",$line,$re)){
                $GLOBALS["GET_SQUID_VERSION"]=strval($re[1]);
                return $GLOBALS["GET_SQUID_VERSION"];
            }
        }
        $GLOBALS["GET_SQUID_VERSION"]=strval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion"));
        return $GLOBALS["GET_SQUID_VERSION"];
    }


    private function auth_schemes(){
        if(!$this->isSquid5){return "# auth_schemes(); Squid Proxy v$this->SquidVersion is not 5.x or above";}
        if($this->SquidUrgency==1){return "# auth_schemes(); Emergency = SquidUrgency";}
        $squid_acls_groups=new squid_acls_groups();
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $results=$q->QUERY_SQL("SELECT * FROM squid_auth_schemes_acls WHERE enabled=1 ORDER BY zorder");
        $aclsarray=array();
        foreach ($results as $index=>$ligne) {
            $aclport = $ligne["aclport"];
            $PortGroup = null;
            $ztype = $ligne["ztype"];
            $rulename = $ligne["rulename"];
            if ($aclport > 0) {$PortGroup = " MyPort{$aclport}";}
            $aclid = $ligne["ID"];
            $acls_array = $squid_acls_groups->buildacls_bytype_items($aclid, false, "squid_auth_schemes_link");
            $aclsarray[] = "#\t$aclid] $rulename " . count($acls_array) . " items";
            if (count($acls_array) == 0) {continue;}
            $annotate_transaction="AnnotateAuthRule$aclid";
            $aclsarray[] = "acl $annotate_transaction annotate_transaction authmec=$aclid";
            $aclsarray[]="auth_schemes $ztype {$PortGroup} ".@implode(" ", $acls_array) ." $annotate_transaction";
            $aclsarray[]="";
        }
        if(count($aclsarray)>0) {
            $aclsarray[] = "auth_schemes ALL all";
            file_put_contents("/etc/squid3/http_access.auth.schemes.conf",@implode("\n", $aclsarray));
        }
        file_put_contents("/etc/squid3/http_access.auth.schemes.conf","#");

    }
    public function CommandLineForArticaRest(){
        $this->auth_schemes();
        $f=new useragents();
        $f->ACLS_ALL();
    }

    function build():string{
        $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
        $this->EnableKerbAuth=$EnableKerbAuth;
        $conf[]="# $this->ActiveDirectory_error";
        $conf[]="# Class http_access_default L.".__LINE__;
        if(!is_file("/etc/squid3/http_access.header.conf")){
            @file_put_contents("/etc/squid3/http_access.header.conf","#");
        }
        $conf[]="include /etc/squid3/http_access.header.conf";
        $conf[]="";
        $sql="SELECT ID,port FROM proxy_ports WHERE enabled=1 AND Parent=1";
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $results = $q->QUERY_SQL($sql);
        foreach ($results as $index=>$ligne){
            $conf[]="http_access allow ParentPort{$ligne["ID"]}";
        }
        $this->auth_schemes();
        $f=new useragents();
        $f->ACLS_ALL();
        $this->http_access_deny_final();
        return @implode("\n", $conf);
    }
    public function http_access_deny_final(){
        shell_exec("/usr/sbin/artica-phpfpm-service -proxy-http-access-final");
    }
    private function WHITELISTED_AUTH(){
        $ipaddr_dest=array();
        $destdomains=array();
        $ipaddr=array();
        $macs=array();
        $annotate_transaction_white=null;
        $SquidWhitelistAuthFrom=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWhitelistAuthFrom"));
        $SquidWhitelistAuthTo=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWhitelistAuthTo"));
        $IP=new IP();

        $f[]="acl SquidWhitelistAuthFromNet src \"/etc/squid3/SquidWhitelistAuthFromNet.db\"";
        $f[]="acl SquidWhitelistAuthToNet dst \"/etc/squid3/SquidWhitelistAuthToNet.db\"";
        $f[]="acl SquidWhitelistAuthToDoms dstdom_regex -i \"/etc/squid3/SquidWhitelistAuthToDoms.db\"";
        $f[]="acl SquidWhitelistAuthFromMAC src \"/etc/squid3/SquidWhitelistAuthFromMAC.db\"";
        $f[] = "acl HotSpotDest dstdomain dummy.site";
        $f[] = "acl HotSpotNets src 10.0.0.1";
        $f[] = "acl MyMobilesPort localport 17";

        @file_put_contents("/etc/squid3/SquidWhitelistAuthFromNet.db", "");
        @file_put_contents("/etc/squid3/SquidWhitelistAuthToNet.db", "");
        @file_put_contents("/etc/squid3/SquidWhitelistAuthToDoms.db", "");
        @file_put_contents("/etc/squid3/SquidWhitelistAuthFromMAC.db", "");



        $acls_merge[]="SquidWhitelistAuthFromNet";
        $acls_merge[]="SquidWhitelistAuthToNet";
        $acls_merge[]="SquidWhitelistAuthToDoms";
        $acls_merge[]="SquidWhitelistAuthFromMAC";
        $acls_merge[]="HotSpotNets";
        $acls_merge[]="HotSpotDest";



        if(is_array($SquidWhitelistAuthFrom)){
            if(count($SquidWhitelistAuthFrom)>0){
                foreach ($SquidWhitelistAuthFrom as $index=>$ligne){
                    if($IP->isIPAddressOrRange($index)){$ipaddr[]=$index;continue;}
                    if($IP->IsvalidMAC($index)){$macs[]=$index;continue;}
                }
            }
        }
        if(is_array($SquidWhitelistAuthTo)){
            if(count($SquidWhitelistAuthTo)>0){
                foreach ($SquidWhitelistAuthTo as $index=>$ligne){
                    if($IP->isIPAddressOrRange($index)){$ipaddr_dest[]=$index;continue;}
                    $destdomains[]=$index;
                }
            }
        }

        // NTLM WhiteList



        if(count($ipaddr)>0){
            $f[]="# WHITELISTED_AUTH: ".count($ipaddr)." <FROM> Network Elements";
            @file_put_contents("/etc/squid3/SquidWhitelistAuthFromNet.db", @implode("\n", $ipaddr));
        }
        if(count($ipaddr_dest)>0) {
            $f[] = "# WHITELISTED_AUTH: " . count($ipaddr_dest) . " <TO> Network Elements";
            @file_put_contents("/etc/squid3/SquidWhitelistAuthToNet.db", @implode("\n", $ipaddr_dest));
        }

        if(count($destdomains)>0){
            $f[]="# WHITELISTED_AUTH: ".count($destdomains)." domains";

            foreach ($destdomains as $index=>$www){
                $destdomains[$index]=$this->StringToRegex($www);
            }

            @file_put_contents("/etc/squid3/SquidWhitelistAuthToDoms.db", @implode("\n", $destdomains));

        }
        if(count($macs)>0){
            $f[]="# WHITELISTED_AUTH: ".count($ipaddr)." MAC Elements";
            @file_put_contents("/etc/squid3/SquidWhitelistAuthFromMAC.db", @implode("\n", $macs));
        }

        $f[]="acl GlobalNoAuth any-of ".@implode(" ",$acls_merge);

        return @implode("\n", $f);
    }
    private function StringToRegex($pattern){
        if(substr($pattern,0,1)=="^"){$pattern=trim(substr($pattern,1,strlen($pattern)));}
        if(preg_match("#^regex:(.+)#", trim($pattern),$re)){return $re[1];}
        $pattern=str_replace(".", "\.", $pattern);
        $pattern=str_replace("(", "\(", $pattern);
        $pattern=str_replace(")", "\)", $pattern);
        $pattern=str_replace("+", "\+", $pattern);
        $pattern=str_replace("|", "\|", $pattern);
        $pattern=str_replace("{", "\{", $pattern);
        $pattern=str_replace("}", "\}", $pattern);
        $pattern=str_replace("?", "\?", $pattern);

        $pattern=str_replace("http://", "^http://", $pattern);
        $pattern=str_replace("https://", "^https://", $pattern);
        $pattern=str_replace("ftp://", "^ftp://", $pattern);
        $pattern=str_replace("ftps://", "^ftps://", $pattern);
        if(substr($pattern,0,2)=="\."){
            $pattern=substr($pattern,2,strlen($pattern));
            $pattern="(^|\.)$pattern";
        }

        if(preg_match("#^\*-(.+)#",$pattern,$re)){
            $pattern="-$re[1]";
        }
        if(preg_match("#^(.+?)\*$#",$pattern,$re)){
            $pattern=$re[1];
        }

        if(preg_match("#^\*\\.(.+)#",$pattern,$re)){
            $pattern="(^|\.){$re[1]}";
        }

        if(preg_match("#^\\*(.+)#",$pattern,$re)){
            $pattern="(^|\.){$re[1]}";
        }
        $pattern=str_replace("(^|\.)\.", "(^|\.)", $pattern);

        return $pattern;
    }




}