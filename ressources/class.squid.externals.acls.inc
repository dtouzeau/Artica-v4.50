<?php
include_once(dirname(__FILE__)."/class.squid.url_rewrite.inc");
include_once(dirname(__FILE__)."/class.squid.externals.acls.childrens.inc");
include_once(dirname(__FILE__)."/class.memcached.inc");
class external_acls_squid{
    private $ERROR;
    private $ASROOT=false;
    private $SquidClientParams=array();
    private $SQUID_VERSION=null;
    private $DynamicACLUrgency=0;
    private $IS_31=false;
    private $IS_32=false;
    private $IS_40=false;
    private $IS_35=false;
    private $SquidUrgency=0;
    private $EnableKerbAuth=0;
    private $LDAP_AUTH=0;
    private $LDAP_EXTERNAL_AUTH=0;
    private $CORP_LICENSE=false;
    private $SquidUsersNumber=0;
    private $UfdbEnableParanoidMode=0;
    private $MacToUidUrgency=0;
    public $EnforceArticaCategories=false;
    private $LockActiveDirectoryToKerberos=0;
    private $SquidDisableAllFilters=0;
    private $external_acl_childrens;
    private $EnableipV6=0;
    private $activedirectory_error=null;
    private $memcached;
    private $isSquid5=false;

    public function __construct(){
        if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
        $users=$GLOBALS["CLASS_USERS"];
        $this->memcached=new lib_memcached();
        if(function_exists("posix_getuid")) { if (posix_getuid() == 0) { $this->ASROOT = true; } }else{ $this->ASROOT = true; }
        $this->SquidClientParams=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));
        $this->SQUID_VERSION=$this->root_squid_version_nocache();
        $this->SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
        $this->EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
        $this->LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){$this->LockActiveDirectoryToKerberos=1;}

        if( $this->LockActiveDirectoryToKerberos==1) {
            if (!is_file("/etc/squid3/krb5.keytab")) {
                $this->activedirectory_error="# Missing /etc/squid3/krb5.keytab";
                $this->LockActiveDirectoryToKerberos = 0;
            }
            if(is_file("/etc/squid3/krb5.keytab")){
                $this->LockActiveDirectoryToKerberos = 1;
                $this->activedirectory_error=null;
            }
        }

        if($this->LockActiveDirectoryToKerberos==1){$this->EnableKerbAuth=1;}
        $this->UfdbEnableParanoidMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbEnableParanoidMode"));
        $this->MacToUidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MacToUidUrgency"));
        $this->DynamicACLUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DynamicACLUrgency"));
        $this->SquidDisableAllFilters=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableAllFilters"));
        $ArticaSquidParameters=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('ArticaSquidParameters');
        if(preg_match("#^3\.[0|1]\.#",$this->SQUID_VERSION)){$this->IS_31=true;}
        if(preg_match("#^3\.[2|3|4|5|6|7|8]\.#",$this->SQUID_VERSION)){$this->IS_32=true;}
        if(preg_match("#^3\.[5|6|7|8]\.#",$this->SQUID_VERSION)){$this->IS_35=true;}
        if(preg_match("#^4\.[0|1|2|3|4|5]\.#",$this->SQUID_VERSION)){$this->IS_32=true;$this->IS_40=true;}
        $ini=new Bs_IniHandler();
        $ini->loadString($ArticaSquidParameters);
        $this->LDAP_AUTH=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidStandardLDAPAuth"));
        $this->SquidUsersNumber=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUsersNumber"));
        $this->LDAP_EXTERNAL_AUTH=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidExternLDAPAUTH"));
        $this->EnableipV6=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6"));
        $this->CORP_LICENSE=$users->CORP_LICENSE;
        $this->external_acl_childrens=new external_acl_children_class();
        $SquidVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion");
        if(preg_match("#^(5|6|7)\.#",$this->SQUID_VERSION)){$this->isSquid5=true;}
        $this->defaultsvalues();
    }



    public function Build(){


    }

    public function external_acl_helper_hypercache(){
        return;
        $HyperCacheEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheEmergency"));
        if($HyperCacheEmergency==1){return "#\texternal_acl_helper_hypercache HyperCache Emergency !...\n";}

        $EnableHyperCacheProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHyperCacheProxy"));
        if($EnableHyperCacheProxy==0){return "#\texternal_acl_helper_hypercache HyperCache not set...\n";}
        $ScriptFileName="/usr/share/artica-postfix/external_acl_hypercache.py";
        if($this->SquidUrgency==1){return "#\texternal_acl_helper_hypercache:: SquidUrgency is enabled, aborting\n";}
        $L[]="%URI";
        $CMDILES=@implode(" ", $L);
        $ForceConf["concurrency"]=16;
        $external_acl_childrens=$this->external_acl_childrens->build(0,false,array());
        $f[]="external_acl_type HyperCacheExtrn concurrency=1 $external_acl_childrens $CMDILES $ScriptFileName\n";
        $f[]="acl HyperCachePlugin external HyperCacheExtrn";
        $f[]="http_access deny !HyperCachePlugin";
        $f[]="acl HyperCacheTagged note hypercache 1";
        @chmod($ScriptFileName, 0755);
        @chown($ScriptFileName, "squid");
        return @implode("\n", $f);

    }
}