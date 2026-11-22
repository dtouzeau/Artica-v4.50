<?php
include_once(dirname(__FILE__)."/settings.exists.inc"); // artica_setting_exists(): settings are root-only, see the file
include_once(dirname(__FILE__)."/class.squid.acls.useragents.inc");
include_once(dirname(__FILE__)."/class.squid.ntlm.inc");
include_once(dirname(__FILE__)."/class.acls.backend.inc");

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
        if(!artica_setting_exists("AllowWindowsUpdates")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("AllowWindowsUpdates", 1);}
        if(!artica_setting_exists("AllowAllNetworksInSquid")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("AllowAllNetworksInSquid", 1);}
        if(!artica_setting_exists("AllowSquidCompatibility")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("AllowSquidCompatibility", 1);}
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
        // acls.db is API-only — list auth-schemes ACL rules via the acls backend (enabled only, zorder).
        $as=acls_api_get("/proxy/auth-schemes/acls");
        $results=array();
        if(is_array($as)){foreach($as as $r){
            if(!is_array($r)){continue;}
            if(intval($r["enabled"] ?? 0)!=1){continue;}
            $results[]=array("ID"=>intval($r["id"] ?? 0),"aclport"=>intval($r["aclPort"] ?? 0),"ztype"=>(string)($r["zType"] ?? ""),"rulename"=>(string)($r["ruleName"] ?? ""));
        }}
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
    // ---------------------------------------------------------------------
    // WHITELISTED_AUTH() et StringToRegex() ont ete SUPPRIMEES (2026-09-14).
    //
    // C'etait du code mort : WHITELISTED_AUTH etait `private` et n'avait aucun
    // appelant -- ni ici (build() delegue au daemon via http_access_deny_final()),
    // ni ailleurs dans WebConsole -- et StringToRegex n'etait appelee que par
    // elle. Mort, mais pas inoffensif : 130 lignes qui dupliquaient la
    // generation du daemon, avec quatre file_put_contents sur les fichiers de
    // PRODUCTION (/etc/squid3/SquidWhitelistAuth*.db). Les rebrancher aurait
    // rouvert l'incident du 2026-09-14, car cette copie :
    //
    //   - produisait des regex INVALIDES que regcomp(3) refuse, donc un
    //     « FATAL: Bungled » et un Squid qui ne demarre plus : « [ » n'etait
    //     jamais echappe (x[.com -> x[\.com) et « * » seul ressortait tel quel ;
    //   - DETRUISAIT les regex ecrites a la main par double echappement
    //     ((track|stats)\.blabla\.(com|de)$ -> \(track\|stats\)\\.blabla…),
    //     ce qui compile mais ne matche plus rien -- en silence ;
    //   - declarait SquidWhitelistAuthFromMAC en « src » la ou le daemon ecrit
    //     « arp », soit un fichier d'adresses MAC lu comme des adresses IP.
    //
    // Le generateur vivant est squid/http_access_final.go, cote daemon, avec sa
    // validation a l'ecriture (squid/regexguard.go). Ne pas restaurer ceci :
    // toute regeneration de ces fichiers passe par le daemon.
    // ---------------------------------------------------------------------





}