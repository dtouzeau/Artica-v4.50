<?php

include_once(dirname(__FILE__)."/class.ActiveDirectory.inc");
include_once(dirname(__FILE__)."/class.tcpip.inc");
class squid_acls{
    public $acls_array = array();
    public $availableGroups = array();
    public $http_access = array();
    private $LDAP_EXTERNAL_AUTH = 0;
    public $RequestedGroupeType = null;
    public $UseDynamicGroupsAcls = 1;
    public $EnableKerbAuth = 0;
    public $AUTH = 0;
    public $instancetime = null;
    public $ASROOT = false;
    private $hasProxyTransparent = 0;
    private $LockActiveDirectoryToKerberos=0;
    private $EnableTransparent27 = 0;
    private $SquidUrgency = 0;
    private $DynamicACLUrgency = 0;
    public $buildacls_bytype_logs=array();
    public $BuildSquidCertificateCRTD_LOGS=array();
    private $isSquid5=false;

    public function __construct()
    {

        if(function_exists("posix_getuid")) {
            if (posix_getuid() == 0) {
                $this->ASROOT = true;
            }
        }else{
            $this->ASROOT = true;
        }
        if (!isset($GLOBALS["CLASS_USERS"])) {
            $GLOBALS["CLASS_USERS"] = new usersMenus();
        }

        $sock = new sockets();
        $this->hasProxyTransparent = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("hasProxyTransparent"));
        $this->EnableTransparent27 = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTransparent27"));
        $this->UseDynamicGroupsAcls = 1;
        $this->SquidUrgency = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));

        $this->DynamicACLUrgency = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DynamicACLUrgency"));


        $this->EnableKerbAuth = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
        $this->LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){$this->LockActiveDirectoryToKerberos=1;}

        if( $this->LockActiveDirectoryToKerberos==1) {
            if (!is_file("/etc/squid3/krb5.keytab")) {
                $this->LockActiveDirectoryToKerberos = 0;
            }
            if(is_file("/etc/squid3/krb5.keytab")){
                $this->LockActiveDirectoryToKerberos = 1;
            }
        }

        if($this->LockActiveDirectoryToKerberos==1){$this->EnableKerbAuth=1;}

        $this->LDAP_EXTERNAL_AUTH = $this->LDAP_EXTERNAL_AUTH_PARSE();
        $SquidStandardLDAPAuth = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidStandardLDAPAuth");
        $EnableOpenLDAP = intval($sock->GET_INFO("EnableOpenLDAP"));
        $UseNativeKerberosAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseNativeKerberosAuth"));
        $WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));

        if($this->EnableKerbAuth==1
            or $UseNativeKerberosAuth==1
            or $WindowsActiveDirectoryKerberos==1
            or $this->LockActiveDirectoryToKerberos==1
        ) {
            $this->LDAP_EXTERNAL_AUTH=0;
        }


        if ($EnableOpenLDAP == 0) {
            $SquidStandardLDAPAuth = 0;
        }

        if ($SquidStandardLDAPAuth == 1) {
            $this->AUTH = 1;
        }


        if (class_exists("squidbee")) {
            if(!isset($GLOBALS["SQUIDBEE"])){$GLOBALS["SQUIDBEE"]=new squidbee();}
            $squid = $GLOBALS["SQUIDBEE"];

            if ($squid->LDAP_EXTERNAL_AUTH == 1) {
                $this->AUTH = 1;
            }
            if ($squid->EnableKerbAuth == 1) {
                $this->AUTH = 1;
            }

        }
        $this->instancetime = time();
        if (!isset($GLOBALS["COMPILE_SQUID_TOKENS"])) {
            $this->compilation_params();
        }
        if (isset($GLOBALS["COMPILE_SQUID_TOKENS"])) {
            if (count($GLOBALS["COMPILE_SQUID_TOKENS"]) < 3) {
                $this->compilation_params();
            }
        }

        $SquidVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion");
        if(preg_match("#^(5|6|7)\.#",$SquidVersion)){$this->isSquid5=true;}

    }


    private function LDAP_EXTERNAL_AUTH_PARSE(){
        return intval(@file_get_contents('/etc/artica-postfix/settings/Daemons/SquidExternLDAPAUTH'));
    }

    public function search_inacls($search):array{
        $q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $tpl        = new template_admin();
        $search     = $q->SearchAntiXSS($search);
        $main       = array();
        $search2    = null;
        $COM        = "LIKE";

        if ($search == null) {
            return array();
        }

        if (strpos($search, "*") > 0) {
            $search = str_replace("*", "%", $search);
            $search2 = str_replace(".", "%", $search2);
        } else {
            $search = "*$search*";
            $search = str_replace("*", "%", $search);
            $search2 = str_replace(".", "%", $search2);
        }

        $ANDS1[] = "( GroupName $COM '$search' )";
        if ($search2 <> null) {
            $ANDS1[] = "( GroupName LIKE '$search2' )";
        }
        $ANDS1[] = "( description $COM '$search' )";
        if ($search2 <> null) {
            $ANDS1[] = "( description LIKE '$search2' )";
        }

        $ANDS[] = "( description $COM '$search' )";
        if ($search2 <> null) {
            $ANDS[] = "( description $COM '$search2' )";
        }
        $ANDS[] = "(pattern $COM '$search' )";
        if ($search2 <> null) {
            $ANDS[] = "( pattern $COM '$search2' )";
        }


        $suffix = " (" . @implode(" OR ", $ANDS1) . ") LIMIT 20";
        $sql = "SELECT ID,GroupName,description FROM webfilters_sqgroups WHERE $suffix";

        //webfilters_sqitems.pattern,webfilters_sqitems.description as itemdesc
        //FROM webfilters_sqgroups,webfilters_sqitems
        //WHERE webfilters_sqitems.gpid=webfilters_sqgroups.ID";
        $results = $q->QUERY_SQL($sql);
        VERBOSE($sql." ==== ".count($results), __LINE__);

        if (!$q->ok) {
            echo $tpl->div_error($q->mysql_error . "<br>$sql");
        }
        if (count($results) > 0) {
            foreach ($results as $index => $ligne) {
                $main[$ligne["ID"]] = $index;

            }
        }

        $suffix=" (". @implode(" OR ",$ANDS).") LIMIT 20";
        $sql="SELECT gpid FROM webfilters_sqitems WHERE $suffix";
        $results = $q->QUERY_SQL($sql);

        if (!$q->ok) {
            echo $tpl->div_error($q->mysql_error . "<br>$sql");
        }

        VERBOSE($sql." ==== ".count($results), __LINE__);

        if (count($results) > 0) {
            foreach ($results as $index => $ligne) {
                $main[$ligne["gpid"]] = $index;
            }
        }

        //webfilters_sqitems.pattern,webfilters_sqitems.description as itemdesc
        //FROM webfilters_sqgroups,webfilters_sqitems
        //WHERE webfilters_sqitems.gpid=webfilters_sqgroups.ID";
        //
        if(!is_array($main)){return array();}
        VERBOSE("RETURN ==== ".count($main), __LINE__);
        return $main;

    }


    private function AllowSmartPhones(){
        $OK = false;
        $SquidAllowSmartPhones = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAllowSmartPhones"));
        $smartphones_port = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("smartphones_port"));
        if ($SquidAllowSmartPhones == 1) {
            $OK = true;
        }
        if ($smartphones_port == 1) {
            $OK = true;
        }

        if (!$OK) {
            $this->acls_array[] = "# acl MobilePhones no one use SquidAllowSmartPhones/smartphones_port";
            return;
        }

        $this->acls_array[] = "# Mobile Phones SquidAllowSmartPhones ==1";
        $this->acls_array[] = "acl MobilePhones browser -i (CFNetwork|iPhone|iPad|Mobile|Android|BlackBerry|PalmSource|SymbianOS|IEMobile|Opera Mobi|Apple-iPhone|Apple-iPad|Apple-iPod)\n";
    }


    public function Build_Acls($force = false, $AsMeta = false){

        $annotate_transaction_white=null;
        $SquidDisableAllFilters = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableAllFilters"));
        $smartphones_port = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("smartphones_port"));
        $SquidAllowRemotePorts = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAllowRemotePorts"));
        $prefix     = "/etc/squid3";

        $defaults_files[]="whitelisted-computers-by-mac.acl";
        $defaults_files[]="acls_whitelist.conf";
        $defaults_files[]="squid-block.acl";
        $defaults_files[]="DomainsBlackLists.acl";
        $defaults_files[]="FromBlackLists.acl";
        $defaults_files[]="NetworksBlackLists.acl";
        $defaults_files[]="logging.conf";
        $defaults_files[]="acls_center_meta.conf";
        $defaults_files[]="acls_center.conf";
        $defaults_files[]="acls_safeports.conf";
        $defaults_files[]="protocols.conf";
        foreach ($defaults_files as $fname) {
            $tpath="$prefix/$fname";
            if (!is_file($tpath)) {
                @touch($tpath);
            }
        }
        $this->acls_array[] ="# ".date("Y-m-d H:i:s");
        $this->acls_array[] ="include /etc/squid3/protocols.conf";

        $this->Build_Acls_Groups($force, $AsMeta);
        // GO convert here.
        $this->Build_Acls_time();
        if (!$AsMeta) {
            $this->AllowSmartPhones();
        }
        if ($this->SquidUrgency == 0) {
            $this->ACL_identd_server();



            if ($SquidDisableAllFilters == 0) {
                $this->acls_array[] = "acl blockedsites url_regex \"/etc/squid3/squid-block.acl\"";
                $this->acls_array[] = "acl DomainsBlackLists dstdomain \"/etc/squid3/DomainsBlackLists.acl\"";
                $this->acls_array[] = "acl NetworksBlackLists dst \"/etc/squid3/NetworksBlackLists.acl\"";
                $this->acls_array[] = "acl NetInternalBlacklists src \"/etc/squid3/FromBlackLists.acl\"";

            }

            if ($smartphones_port > 0) {
                $this->acls_array[] = "acl MyMobilesPort localport $smartphones_port";
            }
        }


    }

    private function ACL_identd_server(){
        $SquidEnableIdentdService = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidEnableIdentdService"));
        $EnableKerbAuth = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
        $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}

        if( $LockActiveDirectoryToKerberos==1) {
            if (!is_file("/etc/squid3/krb5.keytab")) {
                $LockActiveDirectoryToKerberos = 0;
                $this->acls_array[] = "# Fatal ! /etc/squid3/krb5.keytab missing  (LockActiveDirectoryToKerberos = 0 )";
            }
            if(is_file("/etc/squid3/krb5.keytab")){
                $this->acls_array[] = "# [OK] /etc/squid3/krb5.keytab  (LockActiveDirectoryToKerberos = 1 )";
                $LockActiveDirectoryToKerberos = 1;
            }
        }

        if($LockActiveDirectoryToKerberos==1){$EnableKerbAuth=1;}

        if ($EnableKerbAuth == 1) {
            $SquidEnableIdentdService = 0;
        }

        if ($EnableKerbAuth == 1) {
            $this->acls_array[] = "# Active Directory is enabled (EnableKerbAuth), ACL ident disabled";
            return null;
        }

        if ($SquidEnableIdentdService == 0) {
            $this->acls_array[] = "# Identd is disabled (SquidEnableIdentdService)";
            return null;
        }

        $this->acls_array[] = "acl identhosts ident REQUIRED";
        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $sql = "SELECT * FROM ident_networks";
        $results = $q->QUERY_SQL($sql);
        $network_items = array();
        $ip = new IP();
        foreach ($results as $index => $ligne) {
            $network_item = trim($ligne["network_item"]);
            if ($network_item == null) {
                continue;
            }
            if (!$ip->isIPAddressOrRange($network_item)) {
                continue;
            }
            $network_items[] = $network_item;
        }

        if (count($network_items) == 0) {
            $this->acls_array[] = "ident_lookup_access allow all";
        } else {
            @file_put_contents("/etc/squid3/ident_aware_hosts.db", @implode("\n", $network_items) . "\n");
            @chown("/etc/squid3/ident_aware_hosts.db", "squid");
            @chgrp("/etc/squid3/ident_aware_hosts.db", "squid");
            $this->acls_array[] = "acl ident_aware_hosts src \"/etc/squid3/ident_aware_hosts.db\"";
            $this->acls_array[] = "ident_lookup_access allow ident_aware_hosts";
            $this->acls_array[] = "ident_lookup_access deny all";
        }
        $SquidEnableIdentTimeout = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidEnableIdentTimeout"));
        if ($SquidEnableIdentTimeout == 0) {
            $SquidEnableIdentTimeout = 3;
        }
        $this->acls_array[] = "ident_timeout $SquidEnableIdentTimeout seconds";


    }

    private function isDynamicAcls()
    {
        if ($this->DynamicACLUrgency == 1) {
            return false;
        }
        if ($this->SquidUrgency == 1) {
            return false;
        }

        if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
            return false;
        }


        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $sql = "SELECT COUNT( * ) AS TCOUNT FROM (
				SELECT webfilters_sqaclaccess.aclid, webfilters_sqacllinks.gpid, webfilters_sqgroups.ID
				FROM webfilters_sqaclaccess, webfilters_sqacllinks, webfilters_sqgroups
				WHERE webfilters_sqaclaccess.aclid = webfilters_sqacllinks.aclid
				AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
				AND webfilters_sqgroups.GroupType = 'dynamic_acls'
				AND webfilters_sqgroups.enabled = 1
		) AS t";
        $ligne = $q->mysqli_fetch_array($sql);

        if ($ligne["TCOUNT"] == 0) {
            return false;
        }
        return true;

    }

    public function IsObjectAuth()
    {
        if ($this->isRADIUSAuth()) {
            return true;
        }
        if ($this->isADAuth()) {
            return true;
        }
        if ($this->isOpenLDAPAuth()) {
            return true;
        }
        return false;
    }



    public function isRADIUSAuth()
    {
        if (isset($GLOBALS["squid_acl_isRADIUSAuth"])) {
            return $GLOBALS["squid_acl_isRADIUSAuth"];
        }
        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $sql = "SELECT COUNT( * ) AS TCOUNT FROM (
				SELECT webfilters_sqaclaccess.aclid, webfilters_sqacllinks.gpid, webfilters_sqgroups.ID,webfilters_sqgroups.params
				FROM webfilters_sqaclaccess, webfilters_sqacllinks, webfilters_sqgroups
				WHERE webfilters_sqaclaccess.aclid = webfilters_sqacllinks.aclid
				AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
				AND webfilters_sqgroups.GroupType = 'radius_auth'
				AND webfilters_sqgroups.enabled = 1
		) AS t";
        $ligne = $q->mysqli_fetch_array($sql);

        if ($ligne["TCOUNT"] == 0) {
            $GLOBALS["squid_acl_isRADIUSAuth"] = false;
            return $GLOBALS["squid_acl_isRADIUSAuth"];
        }
        $GLOBALS["squid_acl_isRADIUSAuth"] = true;
        return true;

    }

    public function IsAuthenticationEnabled():bool{
        $SquidRadiusAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRadiusAuth"));
        if($SquidRadiusAuth==1){return true;}

        $EnableAdLDAPAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAdLDAPAuth"));
        if($EnableAdLDAPAuth==1){return true;}

        $SquidExternLDAPAUTH=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidExternLDAPAUTH"));
        if($SquidExternLDAPAUTH==1){return true;}

        $SquidStandardLDAPAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidStandardLDAPAuth"));
        if($SquidStandardLDAPAuth==1){return true;}

        $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
        if($LockActiveDirectoryToKerberos==1){return true;}
        $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
        if($EnableKerbAuth==1){return true;}
        $EnableKerbNTLM=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbNTLM"));
        if($EnableKerbNTLM==1){return true;}
        $EnableFakeAuth=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFakeAuth");
        if($EnableFakeAuth==1){return true;}
        return false;

    }

    public function isADAuth()
    {
        if (isset($GLOBALS["squid_acl_" . __FUNCTION__])) {
            return $GLOBALS["squid_acl_" . __FUNCTION__];
        }
        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $sql = "SELECT COUNT( * ) AS TCOUNT FROM (
				SELECT webfilters_sqaclaccess.aclid, webfilters_sqacllinks.gpid, webfilters_sqgroups.ID,webfilters_sqgroups.params
				FROM webfilters_sqaclaccess, webfilters_sqacllinks, webfilters_sqgroups
				WHERE webfilters_sqaclaccess.aclid = webfilters_sqacllinks.aclid
				AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
				AND (webfilters_sqgroups.GroupType = 'ad_auth' OR  webfilters_sqgroups.GroupType = 'proxy_auth')
				AND webfilters_sqgroups.enabled = 1
		) AS t";
        $ligne = $q->mysqli_fetch_array($sql);

        if ($ligne["TCOUNT"] == 0) {
            $GLOBALS["squid_acl_" . __FUNCTION__] = false;
            return $GLOBALS["squid_acl_" . __FUNCTION__];
        }
        $GLOBALS["squid_acl_" . __FUNCTION__] = true;
        return true;

    }

    public function isOpenLDAPAuth()
    {
        if (isset($GLOBALS["squid_acl_" . __FUNCTION__])) {
            return $GLOBALS["squid_acl_" . __FUNCTION__];
        }
        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $sql = "SELECT COUNT( * ) AS TCOUNT FROM (
				SELECT webfilters_sqaclaccess.aclid, webfilters_sqacllinks.gpid, webfilters_sqgroups.ID,webfilters_sqgroups.params
				FROM webfilters_sqaclaccess, webfilters_sqacllinks, webfilters_sqgroups
				WHERE webfilters_sqaclaccess.aclid = webfilters_sqacllinks.aclid
				AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
				AND webfilters_sqgroups.GroupType = 'ldap_auth'
				AND webfilters_sqgroups.enabled = 1
		) AS t";
        $ligne = $q->mysqli_fetch_array($sql);

        if ($ligne["TCOUNT"] == 0) {
            $GLOBALS["squid_acl_" . __FUNCTION__] = false;
            return $GLOBALS["squid_acl_" . __FUNCTION__];
        }
        $GLOBALS["squid_acl_" . __FUNCTION__] = true;
        return true;

    }



    public function GetRulesFromADGroup($GroupName)
    {
        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $RULES = array();
        $GroupName = mysql_escape_string2($GroupName);
        $sql = "SELECT ID FROM webfilters_sqgroups WHERE (GroupType='proxy_auth_ads' OR GroupType='proxy_auth_statad') AND (GroupName='$GroupName') AND enabled=1";


        $results = $q->QUERY_SQL($sql);
        if ($GLOBALS["VERBOSE"]) {
            echo "<span style='color:blue'>GetRulesFromADGroup(" . count($results) . "):: $sql</span><br>\n";
        }


        foreach ($results as $index => $ligne) {
            $sql = "SELECT webfilters_sqacls.aclname,webfilters_sqacls.ID FROM webfilters_sqacls,webfilters_sqacllinks WHERE webfilters_sqacllinks.aclid = webfilters_sqacls.ID AND webfilters_sqacls.enabled =1 AND webfilters_sqacllinks.gpid ={$ligne["ID"]}";

            $results2 = $q->QUERY_SQL($sql);
            if ($GLOBALS["VERBOSE"]) {
                echo "<span style='color:blue'>GetRulesFromADGroup(" . mysqli_num_rows($results2) . "):: $sql</span><br>\n";
            }
            while ($ligne2 = mysqli_fetch_assoc($results2)) {
                if ($GLOBALS["VERBOSE"]) {
                    echo "<span style='color:blue;font-size:22px'>GetRulesFromADGroup:: {$ligne2["aclname"]}!</span><br>\n";
                }
                $RULES[$ligne2["ID"]] = $ligne2["aclname"];

            }
        }


        $sql = "SELECT webfilters_sqgroups.ID FROM webfilters_sqitems,webfilters_sqgroups WHERE webfilters_sqitems.pattern='$GroupName' AND webfilters_sqitems.gpid=webfilters_sqgroups.ID AND webfilters_sqgroups.GroupType='proxy_auth_multiad'";
        $results = $q->QUERY_SQL($sql);
        if ($GLOBALS["VERBOSE"]) {
            echo "<span style='color:blue'>GetRulesFromADGroup(" . mysqli_num_rows($results) . "):: $sql</span><br>\n";
        }
        foreach ($results as $index => $ligne) {
            $sql = "SELECT webfilters_sqacls.aclname,webfilters_sqacls.ID FROM webfilters_sqacls,webfilters_sqacllinks WHERE webfilters_sqacllinks.aclid = webfilters_sqacls.ID AND webfilters_sqacls.enabled =1 AND webfilters_sqacllinks.gpid ={$ligne["ID"]}";
            $results2 = $q->QUERY_SQL($sql);
            if ($GLOBALS["VERBOSE"]) {
                echo "<span style='color:blue'>GetRulesFromADGroup(" . mysqli_num_rows($results2) . "):: $sql</span><br>\n";
            }
            while ($ligne2 = mysqli_fetch_assoc($results2)) {
                if ($GLOBALS["VERBOSE"]) {
                    echo "<span style='color:blue;font-size:22px'>GetRulesFromADGroup:: {$ligne2["aclname"]}!</span><br>\n";
                }
                $RULES[$ligne2["ID"]] = $ligne2["aclname"];

            }
        }
        if ($GLOBALS["VERBOSE"]) {
            echo "<span style='color:red;font-size:22px'>GetRulesFromADGroup:: " . count($RULES) . " RULES</span><br>\n";
        }
        return $RULES;

    }


    public function GetWebfilteringRulesFromADGroup($GroupName)
    {
        $RULES=array();
        $q = new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $sql = "SELECT webfilter_group.ID FROM webfilter_group WHERE localldap=2 AND groupname='$GroupName'";
        $results = $q->QUERY_SQL($sql);
        if ($GLOBALS["VERBOSE"]) {
            echo "<span style='color:blue'>GetWebfilteringRulesFromADGroup(" . mysqli_num_rows($results) . "):: $sql</span><br>\n";
        }
        foreach ($results as $index => $ligne) {

            $id = $ligne["ID"];
            $sql = "SELECT webfilter_rules.groupname,webfilter_rules.ID
					FROM webfilter_assoc_groups,webfilter_rules
					WHERE webfilter_assoc_groups.group_id=$id
					AND webfilter_assoc_groups.webfilter_id=webfilter_rules.ID";

            $results2 = $q->QUERY_SQL($sql);
            if ($GLOBALS["VERBOSE"]) {
                echo "<span style='color:blue'>GetWebfilteringRulesFromADGroup(" . mysqli_num_rows($results2) . "):: $sql</span><br>\n";
            }
            foreach ($results2 as $index2 => $ligne2) {
                if ($GLOBALS["VERBOSE"]) {
                    echo "<span style='color:blue;font-size:22px'>GetWebfilteringRulesFromADGroup:: {$ligne2["aclname"]}!</span><br>\n";
                }
                $RULES[$ligne2["ID"]] = $ligne2["groupname"];

            }
        }

        if ($GLOBALS["VERBOSE"]) {
            echo "<span style='color:red;font-size:22px'>GetWebfilteringRulesFromADGroup:: " . count($RULES) . " RULES</span><br>\n";
        }
        return $RULES;

    }


    private function IsAclsSSL()
    {
        $q = new lib_sqlite("/home/artica/SQLITE/proxy.db");
        if (isset($GLOBALS["IsAclsSSL"])) {
            return $GLOBALS["IsAclsSSL"];
        }
        $sql = "SELECT COUNT(*) as tcount FROM proxy_ports WHERE UseSSL=1 AND enabled=1";
        $ligne = $q->mysqli_fetch_array($sql);
        if (!$q->ok) {
            echo $q->mysql_error . "\n";
            return false;
        }
        if (intval($ligne["tcount"]) > 0) {
            $GLOBALS["IsAclsSSL"] = true;
            return true;
        }
        $GLOBALS["IsAclsSSL"] = false;
        return false;


    }

    public function delete_group($gpid)
    {

        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $ligne=$q->mysqli_fetch_array("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$gpid'");
        $GroupName=$ligne["GroupName"];

        $f[] = "webfilters_sqacllinks";
        $f[] = "parents_sqacllinks";
        $f[] = "logs_sqacllinks";
        $f[] = "outgoingaddr_sqacllinks";
        $f[] = "privoxy_sqacllinks";
        $f[] = "squid_http_headers_link";
        $f[] = "tcp_outgoing_mark_links";
        $f[] = "squid_http_bandwidth_link";
        $f[] = "squid_url_rewrite_link";
        $f[] = "sslrules_sqacllinks";
        $f[] = "sslproxy_cert_error_sqacllinks";
        $f[] = "dnsdist_sqacllinks";
        $f[] = "dnsfw_acls_link";
        $f[] = "squid_http_headers_link";
        $f[] = "squid_auth_schemes_link";
        $f[] = "wpad_sources_link";
        $f[] = "wpad_white_link";

        foreach ($f as $linktable) {
            $q->QUERY_SQL("DELETE FROM $linktable WHERE gpid=$gpid");

        }

        if (!$q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid='$gpid'")) {
            echo $q->mysql_error;
            return false;
        }
        if (!$q->QUERY_SQL("DELETE FROM webfilters_sqgroups WHERE ID='$gpid'")) {
            echo $q->mysql_error;
            return false;
        }

        if($q->TABLE_EXISTS("webfilters_gpslink")){
            $q->QUERY_SQL("DELETE FROM webfilters_gpslink WHERE groupid='$gpid'");
            $q->QUERY_SQL("DELETE FROM webfilters_gpslink WHERE gpid='$gpid'");
        }

        if(function_exists("admin_tracks")){admin_tracks("Remove ACL Group $GroupName");}
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
        $lib=new lib_memcached();
        $lib->Delkey("DNSFWOBJS");
        return true;

    }


    private function LoadGroupsInMemoryResults($acl_table,$link_table){
        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");

        $sqlarr[]="SELECT ";
        if($q->FIELD_EXISTS($acl_table,"rulename")) {
            $sqlarr[] = " $acl_table.rulename,";
        }
        $sqlarr[] = "$acl_table.enabled,";
        $sqlarr[] = "$acl_table.ID,";
        $sqlarr[] = "$link_table.gpid
				FROM $link_table,$acl_table
				WHERE $link_table.aclid=$acl_table.ID
        AND $acl_table.enabled=1";
        $sql=@implode("",$sqlarr);
        $results = $q->QUERY_SQL($sql);

        if (!$q->ok) {
            writelogs("Fatal $q->mysql_error [$sql]",__FUNCTION__,__FILE__,__LINE__);
            return false;
        }
        if(!is_array($results)){
            $results=array();
        }
        foreach ($results as $index => $ligne) {
            $aclname="";
            $aclid=0;
            if(isset($ligne["rulename"])){
                $aclname = $ligne["rulename"];
            }
            if(isset($ligne["ID"])){
                $aclid = $ligne["ID"];
            }
            $GLOBALS["LoadRulesInMemory"][$ligne["gpid"]][] = array($aclname, $aclid,$acl_table);
            $GLOBALS["LoadGroupsInMemory"][$ligne["gpid"]] = true;
        }
        return true;
    }

    public function LoadGroupsInMemory($AlsoDisabled=false)
    {
        if (isset($GLOBALS["LoadGroupsInMemory"])) {
            return $GLOBALS["LoadGroupsInMemory"];
        }
        $webfilters_sqacls_enabled="AND webfilters_sqacls.enabled=1";
        $squid_parents_acls_enabled="AND squid_parents_acls.enabled=1";
        $squid_logs_acls_enabled="AND squid_logs_acls.enabled=1";
        $squid_outgoingaddr_acls_enabled="AND squid_outgoingaddr_acls.enabled=1";

        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $GLOBALS["LoadGroupsInMemory"] = array();
        $GLOBALS["LoadRulesInMemory"] = array();
        $SQUIDACLsEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDACLsEnabled"));
        $EnableParentProxy = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableParentProxy"));
        if($AlsoDisabled){
            $webfilters_sqacls_enabled=null;
            $squid_parents_acls_enabled=null;
            $squid_logs_acls_enabled=null;
            $squid_outgoingaddr_acls_enabled=null;
        }

        if ($SQUIDACLsEnabled == 1) {
            $results = $q->QUERY_SQL("SELECT 
                webfilters_sqacls.aclname,
				webfilters_sqacls.enabled,
				webfilters_sqacls.ID as aclid,
				webfilters_sqacllinks.gpid 
				FROM webfilters_sqacllinks,webfilters_sqacls 
				WHERE webfilters_sqacllinks.aclid=webfilters_sqacls.ID 
				$webfilters_sqacls_enabled");
            if (!$q->ok) {
                $this->acls_array[] = "# !!!!! Line:" . __LINE__ . " $q->mysql_error";
                return false;
            }
            foreach ($results as $index => $ligne) {
                $aclname = $ligne["aclname"];
                $aclid = $ligne["aclid"];
                $GLOBALS["LoadRulesInMemory"][$ligne["gpid"]][] = array($aclname, $aclid,"webfilters_sqacls");
                $GLOBALS["LoadGroupsInMemory"][$ligne["gpid"]] = true;
            }


        } else {
            $this->acls_array[] = "# SQUIDACLsEnabled == 0, aborting webfilters_sqacls";
        }


        if ($EnableParentProxy == 1) {
            $results = $q->QUERY_SQL("SELECT
            squid_parents_acls.rulename,
			squid_parents_acls.enabled,
			squid_parents_acls.aclid,
			parents_sqacllinks.gpid
			FROM parents_sqacllinks,squid_parents_acls
			WHERE parents_sqacllinks.aclid=squid_parents_acls.aclid
			$squid_parents_acls_enabled");
            foreach ($results as $index => $ligne) {
                $aclname = $ligne["rulename"];
                $aclid = $ligne["aclid"];
                $GLOBALS["LoadRulesInMemory"][$ligne["gpid"]][] = array($aclname, $aclid,"squid_parents_acls");
                $GLOBALS["LoadGroupsInMemory"][$ligne["gpid"]] = true;
            }

            $results = $q->QUERY_SQL("SELECT
            squid_parents_acls.rulename,
			squid_parents_acls.enabled,
			squid_parents_acls.aclid,
			parents_white_sqacllinks.gpid
			FROM parents_white_sqacllinks,squid_parents_acls
			WHERE parents_white_sqacllinks.aclid=squid_parents_acls.aclid
			$squid_parents_acls_enabled");
            foreach ($results as $index => $ligne) {
                $aclname = $ligne["rulename"];
                $aclid = $ligne["aclid"];
                $GLOBALS["LoadRulesInMemory"][$ligne["gpid"]][] = array($aclname, $aclid,"squid_parents_acls");
                $GLOBALS["LoadGroupsInMemory"][$ligne["gpid"]] = true;
            }
        }

        $results = $q->QUERY_SQL("SELECT
                squid_logs_acls.rulename,
				squid_logs_acls.enabled,
				squid_logs_acls.aclid,
				logs_sqacllinks.gpid
				FROM logs_sqacllinks,squid_logs_acls
				WHERE logs_sqacllinks.aclid=squid_logs_acls.aclid
				$squid_logs_acls_enabled");

        if (!$q->ok) {
            echo "# # # # $q->mysql_error # # #\n";
        }

        foreach ($results as $index => $ligne) {
            $aclname = $ligne["rulename"];
            $aclid = $ligne["aclid"];
            $GLOBALS["LoadRulesInMemory"][$ligne["gpid"]][] = array($aclname, $aclid,"squid_logs_acls");

            $GLOBALS["LoadGroupsInMemory"][$ligne["gpid"]] = true;
        }

        $sql="SELECT *  FROM `global_whitelist` WHERE enabled=1 and deny_ufdb=1";
        $results = $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error."\n";}


        foreach ($results as $index=>$ligne){
            $GLOBALS["LoadGroupsInMemory"][$ligne["groupid"]] = true;
        }

        $results = $q->QUERY_SQL("SELECT
                squid_outgoingaddr_acls.rulename,
				squid_outgoingaddr_acls.enabled,
				squid_outgoingaddr_acls.aclid,
				outgoingaddr_sqacllinks.gpid
				FROM outgoingaddr_sqacllinks,squid_outgoingaddr_acls
				WHERE outgoingaddr_sqacllinks.aclid=squid_outgoingaddr_acls.aclid
				$squid_outgoingaddr_acls_enabled");

        if (!$q->ok) {
            echo "# # # # $q->mysql_error # # #\n";
        }

        foreach ($results as $index => $ligne) {
            $aclname = $ligne["rulename"];
            $aclid = $ligne["aclid"];
            $GLOBALS["LoadRulesInMemory"][$ligne["gpid"]][] = array($aclname, $aclid,"squid_outgoingaddr_acls");
            $GLOBALS["LoadGroupsInMemory"][$ligne["gpid"]] = true;
        }

        $results = $q->QUERY_SQL("SELECT
                squid_privoxy_acls.rulename,
				squid_privoxy_acls.enabled,
				squid_privoxy_acls.aclid,
				privoxy_sqacllinks.gpid
				FROM privoxy_sqacllinks,squid_privoxy_acls
				WHERE privoxy_sqacllinks.aclid=squid_privoxy_acls.aclid
				AND squid_privoxy_acls.enabled=1");

        if (!$q->ok) {echo "# # # # $q->mysql_error # # #\n";}

        foreach ($results as $index => $ligne) {
            $aclname = $ligne["rulename"];
            $aclid = $ligne["aclid"];
            $GLOBALS["LoadRulesInMemory"][$ligne["gpid"]][] = array($aclname, $aclid,"squid_privoxy_acls");
            $GLOBALS["LoadGroupsInMemory"][$ligne["gpid"]] = true;
        }



       $sql="SELECT webfilters_gpslink.*,webfilters_sqgroups.GroupType,
       webfilters_sqgroups.GroupName
       FROM webfilters_gpslink,webfilters_sqgroups
       WHERE webfilters_gpslink.gpid=webfilters_sqgroups.ID
       AND webfilters_gpslink.enabled=1
       AND webfilters_sqgroups.enabled=1";

        $results = $q->QUERY_SQL($sql);
        if (!$q->ok) {echo "# # # # $q->mysql_error # # #\n";}

        foreach ($results as $index => $ligne) {
            $GLOBALS["LoadGroupsInMemory"][$ligne["gpid"]] = true;
        }
//--------------------------------------------------------------------------------------------------

        if(!$q->TABLE_EXISTS("squid_icap_acls")){
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("system.php?force-databases=yes");
        }


        $this->LoadGroupsInMemoryResults("ssl_rules","sslrules_sqacllinks");
        $this->LoadGroupsInMemoryResults("sslproxy_cert_error","sslproxy_cert_error_sqacllinks");


        $this->LoadGroupsInMemoryResults("tcp_outgoing_mark","tcp_outgoing_mark_links");
        $this->LoadGroupsInMemoryResults("squid_http_headers_acls","squid_http_headers_link");
        $this->LoadGroupsInMemoryResults("http_reply_access","http_reply_access_links");
        $this->LoadGroupsInMemoryResults("squid_http_bandwidth_acls","squid_http_bandwidth_link");
        $this->LoadGroupsInMemoryResults("squid_auth_schemes_acls","squid_auth_schemes_link");
        $this->LoadGroupsInMemoryResults("squid_icap_acls","squid_icap_acls_link");




        $results = $q->QUERY_SQL("SELECT
                squid_url_rewrite_acls.rulename,
				squid_url_rewrite_acls.enabled,
				squid_url_rewrite_acls.ID,
				squid_url_rewrite_link.gpid
				FROM squid_url_rewrite_link,squid_url_rewrite_acls
				WHERE squid_url_rewrite_link.aclid=squid_url_rewrite_acls.ID
				AND squid_url_rewrite_acls.enabled=1");

        if (!$q->ok) {
            echo "# # # # $q->mysql_error # # #\n";
        }

        foreach ($results as $index => $ligne) {
            $aclname = $ligne["rulename"];
            $aclid = $ligne["ID"];
            $GLOBALS["LoadRulesInMemory"][$ligne["gpid"]][] = array($aclname, $aclid,"squid_url_rewrite_acls");
            $GLOBALS["LoadGroupsInMemory"][$ligne["gpid"]] = true;
        }


        if ($this->IsAclsSSL()) {
            $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
            $results = $q->QUERY_SQL("SELECT ssl_rules.description,ssl_rules.ID, sslrules_sqacllinks.gpid FROM sslrules_sqacllinks,ssl_rules WHERE sslrules_sqacllinks.aclid=ssl_rules.ID AND ssl_rules.enabled=1");
            foreach ($results as $index => $ligne) {
                $aclname = "{ssl_rules}:" . $ligne["description"];
                $aclid = $ligne["ID"];
                $GLOBALS["LoadRulesInMemory"][$ligne["gpid"]][] = array($aclname, $aclid,"ssl_rules");
                $GLOBALS["LoadGroupsInMemory"][$ligne["gpid"]] = true;
            }
            $results = $q->QUERY_SQL("SELECT sslproxy_cert_error.description,sslproxy_cert_error.ID, sslproxy_cert_error_sqacllinks.gpid FROM sslproxy_cert_error_sqacllinks,sslproxy_cert_error WHERE sslproxy_cert_error_sqacllinks.aclid=sslproxy_cert_error.ID AND sslproxy_cert_error.enabled=1");
            foreach ($results as $index => $ligne) {
                $aclname = "{ssl_rules}:" . $ligne["description"];
                $aclid = $ligne["ID"];
                $GLOBALS["LoadRulesInMemory"][$ligne["gpid"]][] = array($aclname, $aclid,"ssl_rules");
                $GLOBALS["LoadGroupsInMemory"][$ligne["gpid"]] = true;
            }
            



        }

        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $results = $q->QUERY_SQL("SELECT ID FROM global_whitelist WHERE enabled=1");

        foreach ($results as $index => $ligne) {
            $GLOBALS["LoadGroupsInMemory"][$ligne["ID"]] = true;
        }

        $this->acls_array[] = "# LoadGroupsInMemory = " . count($GLOBALS["LoadGroupsInMemory"]);
        foreach ($GLOBALS["LoadGroupsInMemory"] as $a=>$b){
            $this->acls_array[] = "# Group $a is used";
        }


        reset($GLOBALS["LoadGroupsInMemory"]);
        return $GLOBALS["LoadGroupsInMemory"];
    }

    public function NotUsedGroup($gpid)
    {

        $LoadGroupsInMemory = $this->LoadGroupsInMemory();
        if (isset($LoadGroupsInMemory[$gpid])) {
            $this->acls_array[] = "# Group $gpid is used by  ACLs rules " . count($LoadGroupsInMemory) . " items";
            return false;
        }
        $this->acls_array[] = "# Group $gpid is * NOT * used by  ACLs rules";
        return true;

    }


    private function Build_Acls_Groups($force = false, $asMeta = false){

        if (!$force) {
            if (isset($GLOBALS["BUILD_ACLS_EXECUTED"])) {
                $this->acls_array = $GLOBALS["BUILD_ACLS_EXECUTED"]["acls_array"];
                $this->availableGroups = $GLOBALS["BUILD_ACLS_EXECUTED"]["availableGroups"];
                return true;
            }
        }
        $logs_ouput="Starting......: " . date("H:i:s") . " [ACLS]: ACL Engine Group";
        $sock = new sockets();
        $this->SquidUrgency = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
        $EnableSplashScreen = $sock->GET_INFO("EnableSplashScreen");
        $EnableSplashScreenAsObject = $sock->GET_INFO("EnableSplashScreenAsObject");
        if (!is_numeric($EnableSplashScreen)) {
            $EnableSplashScreen = 0;
        }
        if (!is_numeric($EnableSplashScreenAsObject)) {
            $EnableSplashScreenAsObject = 0;
        }
        $SplashScreenURI = $sock->GET_INFO("SplashScreenURI");
        if (trim($SplashScreenURI) == null) {
            $EnableSplashScreen = 0;
        }



        $URLAR = parse_url($SplashScreenURI);
        if (isset($URLAR["host"])) {
            $SplashScreenURI = "http://$SplashScreenURI";
        }
        if (!preg_match("#^http.*#", $SplashScreenURI)) {
            $SplashScreenURI = "http://$SplashScreenURI";
        }



        $EnableKerbAuth = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
        $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        $kerberosActiveDirectoryHost=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("kerberosActiveDirectoryHost");
        if($HaClusterClient==1){
            if(strlen($kerberosActiveDirectoryHost)>3){
                $EnableKerbAuth=1;
            }
        }

        if (!is_file("/etc/artica-postfix/settings/Daemons/EnableQuotaSize")) {
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableQuotaSize", 0);
        }
        if (!is_file("/etc/artica-postfix/settings/Daemons/SizeQuotasCheckerEmergency")) {
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SizeQuotasCheckerEmergency", 0);
        }

        $SquidStandardLDAPAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidStandardLDAPAuth"));


        if ($this->SquidUrgency == 1) {
            $EnableKerbAuth = 0;
        }



        $isDynamicAcls = $this->isDynamicAcls();


        $ARRAY_AUTH["radius_auth"] = true;
        $ARRAY_AUTH["ad_auth"] = true;
        $ARRAY_AUTH["ldap_auth"] = true;

        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $database = "squidlogs";
        $DirectorySuffix = "acls";
        $PrefixGroup = "Group";


        if ($asMeta) {
            $q = new mysql();
            $DirectorySuffix = "acls-meta";
            $PrefixGroup = "MetGroup";
            if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
                return false;
            }
            $database = "artica_backup";
        }
        @mkdir("/etc/squid3/$DirectorySuffix", 0755, true);
        if (!$q->FIELD_EXISTS("webfilters_sqgroups", "PortDirection", $database)) {
            $q->QUERY_SQL("ALTER TABLE `webfilters_sqgroups` ADD `PortDirection` smallint(1) NOT NULL DEFAULT '0',ADD INDEX(`PortDirection`)");
        }
        $sql = "SELECT ID,GroupName,GroupType,PortDirection,params FROM webfilters_sqgroups WHERE enabled=1";
        $results = $q->QUERY_SQL($sql, "metaclient");
        $ReturnFirstValue = false;
        $MAIN_ACLS_GROUPSOFGROUPS=array();
        foreach ($results as $index => $ligne) {
            $GroupName = $ligne["GroupName"];
            $ItemsPrefix = null;
            $ItemsSuffix = null;
            $textMeta = " Used locally";
            $acls_name = "{$PrefixGroup}{$ligne["ID"]}";
            $portname = null;
            $this->acls_array[] = "";
            $this->acls_array[] = "";
            $this->acls_array[] = "# ----- $GroupName ( $acls_name ) {$ligne["ID"]} type:{$ligne["GroupType"]} -------------------------";

            if ($asMeta) {
                $textMeta = " Used By Artica Meta";
            }
            if (!$asMeta) {
                if ($this->NotUsedGroup($ligne["ID"])) {
                    $this->acls_array[] = "# $GroupName -> $acls_name type {$ligne["GroupType"]} not used by any rule. $textMeta (" . __FUNCTION__ . "/" . __LINE__ . ")";
                    $this->acls_array[] = "";
                    $this->acls_array[] = "# --------------------------------------------------------------------";
                    continue;
                }
            }

            $this->acls_array[] = "# $acls_name type:{$ligne["GroupType"]} EnableKerbAuth:$EnableKerbAuth, $textMeta (" . __FUNCTION__ . "/" . __LINE__ . ")";
            if ($ligne["GroupType"] == "proxy_auth_tagad") {
                if ($EnableKerbAuth == 0) {
                    $this->availableGroups[$ligne["ID"]] = false;
                    $this->acls_array[] = "# Authentication Trough Active Directory disabled.";
                    $this->acls_array[] = "";
                    $this->acls_array[] = "";
                    $this->acls_array[] = "# --------------------------------------------------------------------";
                    continue;
                }
            }

            if($ligne["GroupType"]=="AclsGroup"){
                $this->acls_array[] = "# Group of object {$ligne["ID"]} (To the end)";
                $MAIN_ACLS_GROUPSOFGROUPS[$ligne["ID"]]=$GroupName;
            }

            if ($ligne["GroupType"] == "time") {
                $this->acls_array[] = "# ParseTimeAclsGroup({$ligne["ID"]})";
                $AclsValue = $this->ParseTimeAclsGroup($ligne["ID"]);

                if ($AclsValue == null) {
                    $this->acls_array[] = "#! Error, no value returned";
                    $this->acls_array[] = "";
                    $this->acls_array[] = "";
                    $this->acls_array[] = "# --------------------------------------------------------------------";
                    continue;
                }
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "acl $acls_name {$ligne["GroupType"]} $AclsValue";
                $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                if ($TEMPLATE <> null) {
                    $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                }
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }


            if ($GLOBALS["VERBOSE"]) {
                echo "#\n#\n";
            }
            echo "$logs_ouput:[$this->instancetime] $acls_name `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]}\n";

            if ($ligne["GroupType"] == "maxconn") {
                $AclsValue = $this->buildItems($ligne["ID"], true);

                $this->availableGroups[$ligne["ID"]] = true;
                echo "$logs_ouput: $acls_name `{$ligne["GroupName"]}` maxconn $AclsValue max connection(s)\n";
                $this->acls_array[] = "acl $acls_name {$ligne["GroupType"]} $AclsValue";
                $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                if ($TEMPLATE <> null) {
                    $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                }
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }
            if ($ligne["GroupType"] == "the_shields") {
                $KSRNAsACls      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNAsACls"));
                if ($KSRNAsACls == 0) {
                    $this->availableGroups[$ligne["ID"]] = false;
                    $this->acls_array[] = "# The Shields is disabled in ACLs.";
                    $this->acls_array[] = "# --------------------------------------------------------";
                    continue;
                }
            }


            if ($ligne["GroupType"] == "proxy_auth_adou") {
                if ($EnableKerbAuth == 0) {
                    $this->availableGroups[$ligne["ID"]] = false;
                    $this->acls_array[] = "# Authentication Trough Active Directory disabled.";
                    $this->acls_array[] = "# --------------------------------------------------------";
                }

                if ($this->SquidUrgency == 1) {
                    $this->availableGroups[$ligne["ID"]] = false;
                    $this->acls_array[] = "# Proxy is on Global Emergency.";
                    $this->acls_array[] = "# --------------------------------------------------------";
                    continue;
                }

                $this->acls_array[] = "# ORGANIZATION: Authentication Trough Active Directory enabled.";
                $this->availableGroups[$ligne["ID"]] = true;
                $gpname = utf8_encode($ligne["GroupName"]);
                $gpname = str_replace(" ", "%20", $gpname);
                $this->acls_array[] = "acl $acls_name external ads_group OU:$gpname";
                $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                if ($TEMPLATE <> null) {
                    $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                }
                $this->acls_array[] = "# --------------------------------------------------------";
                continue;
            }
            if ($ligne["GroupType"] == "ldap_group") {

                if($SquidStandardLDAPAuth==0){
                    $this->availableGroups[$ligne["ID"]] = false;
                    $this->acls_array[] = "# Authentication Trough LOCAL LDAP disabled.";
                    $this->acls_array[] = "# --------------------------------------------------------";
                    continue;
                }
                $this->acls_array[] = "# Authentication LOCAL LDAP enabled.";
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "acl $acls_name external ldap_group {$ligne["params"]}";
                $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                if ($TEMPLATE <> null) {
                    $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                }
                $this->acls_array[] = "# --------------------------------------------------------";
            }


            if ( ($ligne["GroupType"] == "proxy_auth_ads" ) OR    ($ligne["GroupType"] == "proxy_auth_authenticated") ) {
                if ($EnableKerbAuth == 0) {
                    $this->availableGroups[$ligne["ID"]] = false;
                    $this->acls_array[] = "# Authentication Trough Active Directory disabled.";
                    $this->acls_array[] = "# --------------------------------------------------------";
                    continue;
                }

                if ($this->SquidUrgency == 1) {
                    $this->availableGroups[$ligne["ID"]] = false;
                    $this->acls_array[] = "# Proxy is on Global Emergency.";
                    $this->acls_array[] = "# --------------------------------------------------------";
                    continue;
                }

                $this->acls_array[] = "# Authentication Trough Active Directory enabled.";
                $this->availableGroups[$ligne["ID"]] = true;
                $ligne["GroupName"]=str_replace("$","\\$",$ligne["GroupName"]);
                $this->acls_array[] = "acl $acls_name external ads_group " . utf8_encode($ligne["GroupName"]);
                $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                if ($TEMPLATE <> null) {
                    $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                }
                $this->acls_array[] = "# --------------------------------------------------------";
                continue;
            }


            if ($ligne["GroupType"] == "proxy_auth_extad") {
               //4depreciated
                continue;
            }


            if ($ligne["GroupType"] == "proxy_auth_tagad") {
                if ($EnableKerbAuth == 0) {
                    $this->availableGroups[$ligne["ID"]] = false;
                    $this->acls_array[] = "# Authentication Trough Active Directory disabled.";
                    $this->acls_array[] = "# --------------------------------------------------------";
                    continue;
                }



                echo "$logs_ouput:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:proxy_auth_ads $acls_name is available\n";
                $this->acls_array[] = "# Active Directory connected Yes";
                $this->availableGroups[$ligne["ID"]] = true;
                if ($ligne["aclport"] > 0) {
                    if ($ligne["aclport"] == 500) {
                        $portname = null;
                    } else {
                        $portname = " MyPort{$ligne["aclport"]}";
                    }
                }
                $this->acls_array[] = "acl tag_$acls_name external ads_group TAG:" . utf8_encode($ligne["GroupName"]);
                $this->acls_array[] = "http_access deny{$portname} !tag_{$acls_name}";
                $this->acls_array[] = "acl $acls_name tag " . strtolower(utf8_encode($ligne["GroupName"]));
                $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                if ($TEMPLATE <> null) {
                    $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                }
                $this->acls_array[] = "";
                $this->acls_array[] = "# -------------------------------------------------------------";
                continue;
            }


            if ($ligne["GroupType"] == "clt_conn_tag") {
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "acl $acls_name note clt_conn_tag " . utf8_encode($ligne["GroupName"]);
                $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                if ($TEMPLATE <> null) {
                    $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                }
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }
            if ($ligne["GroupType"] == "proxy_auth_multiad") {
                if ($EnableKerbAuth == 0) {
                    $this->availableGroups[$ligne["ID"]] = false;
                    $this->acls_array[] = "# Authentication Trough Active Directory disabled.";
                    $this->acls_array[] = "# --------------------------------------------------------";
                    continue;
                }


                $AclsItemsCount = $this->buildItems($ligne["ID"]);

                if ($AclsItemsCount == 0) {
                    $this->availableGroups[$ligne["ID"]] = false;
                    $this->acls_array[] = "# AclsItemsCount == 0";
                    $this->acls_array[] = "";
                    $this->acls_array[] = "# --------------------------------------------------------";
                    continue;
                }

                echo "$logs_ouput:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:proxy_auth_ads $acls_name is available\n";
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "acl $acls_name external ads_group {$ligne["ID"]}";
                $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                if ($TEMPLATE <> null) {
                    $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                }
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }
            if ($ligne["GroupType"] == "proxy_auth_ldap") {
                if ($this->LDAP_EXTERNAL_AUTH == 0) {
                    echo "$logs_ouput:[$this->instancetime] `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} -> SKIP EnableKerbAuth = 0\n";
                    continue;
                }
                if ($this->SquidUrgency == 1) {
                    echo "$logs_ouput:[$this->instancetime] `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} -> SKIP Emergency mdode !\n";
                    continue;
                }
                echo "$logs_ouput:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:proxy_auth_ldap $acls_name is available\n";
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "# LDAP_EXTERNAL_AUTH=$this->LDAP_EXTERNAL_AUTH [" . __LINE__ . "]";
                $this->acls_array[] = "acl $acls_name external ldap_group {$ligne["GroupName"]}";
                $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                if ($TEMPLATE <> null) {
                    $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                }
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }
            if ($ligne["GroupType"] == "ssl_sni") {
                $this->buildItems($ligne["ID"]);
                if (!isset($GLOBALS["external_acl_type"]["SNIGroup{$ligne["ID"]}"])) {
                    continue;
                }
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }
            if ($ligne["GroupType"] == "ssl_sni_regex") {
                $this->buildItems($ligne["ID"]);
                if (!isset($GLOBALS["external_acl_type"]["SNIGroup{$ligne["ID"]}"])) {
                    continue;
                }
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }
            if ($ligne["GroupType"] == "proxy_auth_statad") {
                if ($EnableKerbAuth == 0) {
                    $this->availableGroups[$ligne["ID"]] = false;
                    $this->acls_array[] = "# Authentication Trough Active Directory disabled.";
                    $this->acls_array[] = "";
                    $this->acls_array[] = "# --------------------------------------------------------------------";
                    continue;
                }

                $AclsItemsCount = $this->buildItems($ligne["ID"]);
                if ($AclsItemsCount == 0) {
                    continue;
                }
                echo "$logs_ouput:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:proxy_auth_ads $acls_name is available\n";
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "acl $acls_name proxy_auth -i \"/etc/squid3/acls/container_{$ligne["ID"]}.txt\"";
                $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                if ($TEMPLATE <> null) {
                    $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                }
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }
            if (preg_match("#time_quota:([0-9]+)#", $ligne["GroupType"], $re)) {
                $this->buildItems_time_quota($ligne["ID"], $re[1]);
                echo "$logs_ouput:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}: $acls_name is available " . __CLASS__ . "/" . __FUNCTION__ . "/" . __LINE__ . "\n";
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "acl $acls_name external ExTimeQuota{$re[1]}";
                $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                if ($TEMPLATE <> null) {
                    $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                }
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }
            if (preg_match("#time_session:([A-Z]+):([0-9]+)#", $ligne["GroupType"], $re)) {
                echo "$logs_ouput:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}: $acls_name is available " . __CLASS__ . "/" . __FUNCTION__ . "/" . __LINE__ . "\n";
                $this->availableGroups[$ligne["ID"]] = true;
                $SUFFIX = $re[1];
                if ($SUFFIX == "ACTIVE") {
                    $SUFFIX = null;
                }
                $this->acls_array[] = "acl $acls_name external SessionTrack{$re[2]} $SUFFIX";
                $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                if ($TEMPLATE <> null) {
                    $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                }
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }
            if ($ligne["GroupType"] == "maxconn") {
                $this->availableGroups[$ligne["ID"]] = false;
                $AclsItem = $this->buildItems($ligne["ID"], true);
                echo "$logs_ouput:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:proxy_auth_ads $acls_name is available\n";
                if ($AclsItem <> null) {
                    $this->availableGroups[$ligne["ID"]] = true;
                    $this->acls_array[] = "acl $acls_name maxconn $AclsItem";
                    $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                    if ($TEMPLATE <> null) {
                        $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                    }
                }
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }
            if ($ligne["GroupType"] == "max_user_ip") {
                $this->availableGroups[$ligne["ID"]] = false;
                $GLOBALS["authenticate_ip_ttl"] = true;
                $AclsItem = $this->buildItems($ligne["ID"], true);
                echo "$logs_ouput:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:proxy_auth_ads $acls_name is available\n";
                if ($AclsItem <> null) {
                    $this->availableGroups[$ligne["ID"]] = true;
                    $this->acls_array[] = "acl $acls_name max_user_ip $AclsItem";
                    $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                    if ($TEMPLATE <> null) {
                        $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                    }
                }
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }
            if($ligne["GroupType"] == "proxy_auth_authenticated"){
                $this->availableGroups[$ligne["ID"]] = true;
                continue;
            }
            if($ligne["GroupType"] == "url_db") {
                if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
                    $this->availableGroups[$ligne["ID"]] = false;
                    $this->acls_array[] = "# url_db, no such license";
                    continue;
                }
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "# URL Database from group id";
                $this->acls_array[] = "acl $acls_name external UrlsDatabases {$ligne["ID"]}";
                $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                if ($TEMPLATE <> null) {
                    $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                }
                $this->acls_array[] = "";
                $this->acls_array[] = "#";
            }
            if ($ligne["GroupType"] == "dynamic_acls") {
                if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
                    $this->availableGroups[$ligne["ID"]] = false;
                    $this->acls_array[] = "";
                    $this->acls_array[] = "# --------------------------------------------------------------------";
                    continue;
                }

                if (!$isDynamicAcls) {
                    $this->availableGroups[$ligne["ID"]] = false;
                    $this->acls_array[] = "";
                    $this->acls_array[] = "# --------------------------------------------------------------------";
                    continue;
                }
                echo "$logs_ouput:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:dynamic_acls $acls_name is available\n";
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "acl $acls_name external DynamicAcls {$ligne["ID"]}";
                $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                if ($TEMPLATE <> null) {
                    $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                }
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;

            }
            if ($ligne["GroupType"] == "weekrange") {
                $MAINS = array();
                $this->acls_array[] = "# Params " . strlen($ligne["params"]) . " bytes";
                $params = unserialize(base64_decode($ligne["params"]));
                $datas = $params["TIME"];
                $this->acls_array[] = "# Data " . count($datas) . " Items";
                if (count($datas) > 0) {
                    $this->availableGroups[$ligne["ID"]] = true;
                    $ayDays = array("S", "M", "T", "W", "H", "F", "A");
                    foreach ($datas as $szPeriod=>$none){
                        if (!preg_match("#^([0-9]+)_(.+)#", $szPeriod, $re)) {
                            $this->acls_array[] = "#$szPeriod no match";
                            continue;
                        }
                        $Day = $re[1];
                        $Hour = str_replace("-", ".", $re[2]);
                        $szStart = date("G:i", $Hour * 3600);
                        $szEnd = date("G:i", ($Hour + 0.5) * 3600);
                        if ($szEnd == "0:00") {$szEnd = "23:59";}
                        $MAINS["$szStart-$szEnd"]["DAYS"][$ayDays[$Day]] = true;

                    }
                    foreach ($MAINS as $time=>$DAYS){
                        $dd = array();
                        foreach ($DAYS["DAYS"] as $bday=>$none){
                            $dd[] = $bday;
                        }
                        $this->acls_array[] = "acl $acls_name time " . @implode("", $dd) . " $time";
                    }

                    $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                    if ($TEMPLATE <> null) {
                        $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                    }


                }
                $this->acls_array[] = "";
                $this->acls_array[] = "# ----------------------------------------------------------";
                continue;
            }
            //------------------------------------------------------------------------
            if (isset($ARRAY_AUTH[$ligne["GroupType"]])) {
                $this->availableGroups[$ligne["ID"]] = false;
                if ($this->SquidUrgency == 1) {
                    echo "$logs_ouput:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:AUTH: {$ligne["GroupType"]} EMERGENCY ENABLED\n";
                    continue;
                }
                echo "$logs_ouput:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:AUTH: {$ligne["GroupType"]} $acls_name is available\n";
                if ($this->IsObjectAuth()) {
                    $this->availableGroups[$ligne["ID"]] = true;
                    $this->acls_array[] = "acl $acls_name external BasicFakeAuth ID{$ligne["ID"]}";
                    $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                    if ($TEMPLATE <> null) {
                        $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                    }
                    @file_put_contents("/etc/squid3/AuthParams{$ligne["ID"]}.conf", $ligne["params"]);
                }
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;

            }
        //-----------------------------------------------------------------------------------

            if ($ligne["GroupType"] == "accessrule") {
                $this->buildItems($ligne["ID"]);
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "acl $acls_name note simplerule \"/etc/squid3/acls/container_{$ligne["ID"]}.txt\"";
                $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                if ($TEMPLATE <> null) {
                    $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                }
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }

            if ($ligne["GroupType"] == "categories") {
                if ($this->SquidUrgency == 1) {
                    $this->acls_array[] = "# Group: (categories) [$this->instancetime] Emergency Mode [" . __LINE__ . "]";
                    continue;
                }

                $AclsItemsCount = $this->buildItems($ligne["ID"]);
                if ($AclsItemsCount == 0) {
                    $this->acls_array[] = "# Group: (categories) [$this->instancetime] No item [" . __LINE__ . "]";
                    continue;
                }

                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "acl $acls_name note category \"/etc/squid3/acls/container_{$ligne["ID"]}.txt\"";

                $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                if ($TEMPLATE <> null) {
                    $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                }
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }
            //-----------------------------------------------------------------------------------
            if ($ligne["GroupType"] == "Smartphones") {
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "acl $acls_name browser -i (CFNetwork|iPhone|iPad|Mobile|Android|BlackBerry|PalmSource|SymbianOS|IEMobile|Opera Mobi|Apple-iPhone|Apple-iPad|Apple-iPod)";
                $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                if ($TEMPLATE <> null) {
                    $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                }
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }
            //-----------------------------------------------------------------------------------
/*
            if ($ligne["GroupType"] == "quota_time") {
                if (!$GLOBALS["ArtcExtrnQuotaTimeScanner"]) {
                    echo "$logs_ouput:[$this->instancetime] `{$ligne["GroupName"]}` - ArtcExtrnQuotaTimeScanner is disabled !!! Why ???\n";
                    $this->acls_array[] = "";
                    $this->acls_array[] = "# --------------------------------------------------------------------";
                    continue;
                }

                if (!isset($GLOBALS["LoadingExtrnQuotaTime"][$ligne["ID"]])) {
                    echo "$logs_ouput:[$this->instancetime] `{$ligne["GroupName"]}` NOT AVAILABLE\n";
                    $this->acls_array[] = "";
                    $this->acls_array[] = "# --------------------------------------------------------------------";
                    continue;
                }

                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "acl $acls_name external ArtTime{$ligne["ID"]}";
                $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                if ($TEMPLATE <> null) {
                    $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                }
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }
 */           //---------------------------------------------------------------------------------------
/*
            if ($ligne["GroupType"] == "quota_size") {
                $this->availableGroups[$ligne["ID"]] = false;
                if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
                    $this->acls_array[] = "# quota_size $acls_name skip, License Error";
                    continue;
                }
                if ($SizeQuotasCheckerEmergency == 1) {
                    $this->acls_array[] = "# quota_size $acls_name skip, Emergency (helper)";
                    continue;
                }
                if ($EnableQuotaSize == 0) {
                    $this->acls_array[] = "# quota_size $acls_name skip, Disabled";
                    continue;
                }
                if ($this->SquidUrgency == 1) {
                    $this->acls_array[] = "# quota_size $acls_name skip, Emergency (global)";
                    continue;
                }

                $params = unserialize(base64_decode($ligne["params"]));
                $VOLUME_DETECT = $params["VOLUME_DETECT"];
                $VOLUME_SCHE = $params["VOLUME_SCHE"];
                $VOLUME = $params["VOLUME"];
                $this->availableGroups[$ligne["ID"]] = true;
                $acls_name1 = "Intern{$ligne["ID"]}";
                $this->acls_array[] = "# Check: $VOLUME_DETECT, for $VOLUME_SCHE and {$VOLUME}Mo";
                $this->acls_array[] = "acl $acls_name1 external ArtQuotaSize {$ligne["ID"]}:$VOLUME_DETECT:$VOLUME_SCHE:$VOLUME";
                $this->acls_array[] = "http_access deny !$acls_name1";
                $this->acls_array[] = "acl $acls_name tag SIZE:{$ligne["ID"]}";
                $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                if ($TEMPLATE <> null) {
                    $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                }
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }


            if ($ligne["GroupType"] == "NudityScan") {
                continue;
            }
*/
            if ($ligne["GroupType"] == "teamviewer") {
                $t = new products_ip_ranges();
                $fname="/etc/squid3/acls/teamviewer-nets.acl";
                $t->teamviewer_networks($ligne["ID"]);
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "acl $acls_name dst \"$fname\"";
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;

            }

            if ($ligne["GroupType"] == "facebook") {
                $t = new products_ip_ranges();
                $t->facebook_networks($ligne["ID"]);
                $this->availableGroups[$ligne["ID"]] = true;
                $fname="/etc/squid3/acls/facebook-nets.acl";
                $this->acls_array[] = "acl $acls_name dst \"$fname\"";

                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;

            }

            if ($ligne["GroupType"] == "whatsapp") {
                $t = new products_ip_ranges();
                $t->whatsapp_networks($ligne["ID"]);
                $this->availableGroups[$ligne["ID"]] = true;
                $fname="/etc/squid3/acls/whatsapp-nets.acl";
                $this->acls_array[] = "acl $acls_name dst \"$fname\"";
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;

            }

            if ($ligne["GroupType"] == "office365") {
                $t = new products_ip_ranges();
                $t->office365_networks();
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "acl $acls_name dst \"/etc/squid3/acls/office365-nets.acl\"";
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;

            }


            if ($ligne["GroupType"] == "FTP") {
                $this->acls_array[] = "acl $acls_name proto FTP";
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }

            if ($ligne["GroupType"] == "AntiTrack") {
                $t = new products_ip_ranges();
                $t->AntiTrack();
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "acl $acls_name url_regex -i \"/etc/squid3/acls/AntiTrack.acl\"";
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }


            if ($ligne["GroupType"] == "skype") {
                $t = new products_ip_ranges();
                $t->skype_networks();
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "acl $acls_name dst \"/etc/squid3/acls/skype-nets.acl\"";
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }

            if ($ligne["GroupType"] == "youtube") {
                $t = new products_ip_ranges();
                $t->youtube_networks();
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "acl $acls_name dst \"/etc/squid3/acls/youtube-nets.acl\"";
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }

            if ($ligne["GroupType"] == "google") {
                $t = new products_ip_ranges();
                $t->google_networks($ligne["ID"]);
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "acl $acls_name dst \"/etc/squid3/acls/google-nets.acl\"";
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }

            if ($ligne["GroupType"] == "dropbox") {
                $t = new products_ip_ranges();
                $t->dropbox_networks();
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "acl $acls_name dst \"/etc/squid3/acls/dropbox-nets.acl\"";
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }

            if ($ligne["GroupType"] == "google_ssl") {
                $t = new products_ip_ranges();
                $t->google_networks($ligne["ID"]);
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "acl $acls_name dst \"/etc/squid3/acls/google-ssl.acl\"";
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }


            if ($ligne["GroupType"] == "hotspot_auth") {
                if ($EnableSplashScreen == 0) {
                    echo "$logs_ouput:[$this->instancetime] `{$ligne["GroupName"]}` - hotspot_auth is disabled !!! EnableSplashScreen =0 ???\n";
                    $this->acls_array[] = "";
                    $this->acls_array[] = "# --------------------------------------------------------------------";
                    continue;
                }

                if ($EnableSplashScreenAsObject == 0) {
                    echo "$logs_ouput:[$this->instancetime] `{$ligne["GroupName"]}` - hotspot_auth is disabled !!! EnableSplashScreenAsObject =0 ???\n";
                    $this->acls_array[] = "";
                    $this->acls_array[] = "# --------------------------------------------------------------------";
                    continue;
                }

                echo "$logs_ouput:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:NudityScan $acls_name is available\n";
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "acl $acls_name external SplashScreenAuthDef";
                $this->acls_array[] = "deny_info 303:$SplashScreenURI?request=%o $acls_name";
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }


            if ($ligne["GroupType"] == "time") {
                echo "$logs_ouput:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:Time \"$acls_name\" is available\n";
                $time = $this->ParseTimeAclsGroup($ligne["ID"]);
                if ($time <> null) {
                    $this->availableGroups[$ligne["ID"]] = true;
                    if ($GLOBALS["VERBOSE"]) {
                        echo "{$ligne["ID"]} \"acl $acls_name time $time\"";
                    }
                    $this->acls_array[] = "acl $acls_name time $time";
                    $TEMPLATE = $this->acl_tpl($ligne["ID"]);
                    if ($TEMPLATE <> null) {
                        $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
                    }
                } else {
                    echo "$logs_ouput:[$this->instancetime] `{$ligne["GroupName"]}` SKIP with error\n";
                }
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }

            if ($ligne["GroupType"] == "proxy_auth") {
                $AUTH=false;
                if($this->isADAuth()){
                    $AUTH=true;
                }
                if($this->isOpenLDAPAuth()){
                    $AUTH=true;
                }
                if($this->isRADIUSAuth()){
                    $AUTH=true;
                }

                if (!$AUTH) {
                    $this->acls_array[] = "# AUTH response failed " . __LINE__;
                    continue;
                }
                $ligne["GroupType"] = "proxy_auth -i";
                if ($this->AUTH == 0) {
                    $this->acls_array[] = "";
                    $this->acls_array[] = "# --------------------------------------------------------------------";
                    continue;
                }


            }
            if ($ligne["GroupType"] == "browser") {
                $ligne["GroupType"] = "browser -i";
            }

            if ($ligne["GroupType"] == "req_mime_type") {
                $ligne["GroupType"] = "req_mime_type -i";
            }
            if ($ligne["GroupType"] == "rep_mime_type") {
                $ligne["GroupType"] = "rep_mime_type -i";
            }


            if ($ligne["GroupType"] == "url_regex") {
                $ligne["GroupType"] = "url_regex -i";
            }
            if ($ligne["GroupType"] == "referer_regex") {
                $ligne["GroupType"] = "referer_regex -i";
            }

            if ($ligne["GroupType"] == "url_regex_extensions") {
                $ligne["GroupType"] = "url_regex -i";
            }

            if ($ligne["GroupType"] == "rep_header_filename") {
                $ligne["GroupType"] = "rep_header Content-Disposition -i";
                $ItemsPrefix = "attachment; filename=.*?";
                $ItemsSuffix = "(\"|$)";
            }

            if ($ligne["GroupType"] == "all" OR $ligne["GroupType"] == "the_shields") {
                $this->availableGroups[$ligne["ID"]] = true;
                $this->acls_array[] = "";
                $this->acls_array[] = "# --------------------------------------------------------------------";
                continue;
            }


            $AclsItemsCount = $this->buildItems($ligne["ID"], $ReturnFirstValue, $asMeta, $ItemsPrefix, $ItemsSuffix);
            echo "$logs_ouput: $acls_name `{$ligne["GroupName"]}` `$AclsItemsCount` item(s)\n";


            if ($AclsItemsCount == 0) {
                $this->acls_array[] = "# Will be not available (no item)";
                $this->acls_array[] = "#----------------------------------------------------------------";
                continue;
            }
            $this->availableGroups[$ligne["ID"]] = true;
            echo "$logs_ouput: $acls_name is available\n";
            $this->acls_array[] = "acl $acls_name {$ligne["GroupType"]} \"/etc/squid3/$DirectorySuffix/container_{$ligne["ID"]}.txt\" ";
            $TEMPLATE = $this->acl_tpl($ligne["ID"]);
            if ($TEMPLATE <> null) {
                $this->acls_array[] = "deny_info $TEMPLATE $acls_name";
            }
            $GLOBALS["BUILD_ACLS_EXECUTED"]["acls_array"] = $this->acls_array;
            $GLOBALS["BUILD_ACLS_EXECUTED"]["availableGroups"] = $this->availableGroups;
            $this->acls_array[] = "";
            $this->acls_array[] = "# ------------------------------------------------------------------";
        }


        if(count($MAIN_ACLS_GROUPSOFGROUPS)==0){
            $this->acls_array[] = "# No Meta group defined";
            return true;
        }
        // --------------------- META GROUPS ANY OF ---------------------
        foreach ($MAIN_ACLS_GROUPSOFGROUPS as $GroupID=>$GroupName){
            $acls_name = "{$PrefixGroup}{$GroupID}";
            $sql="SELECT webfilters_gpslink.*,webfilters_sqgroups.GroupType,
                webfilters_sqgroups.GroupName
                FROM webfilters_gpslink,webfilters_sqgroups
                WHERE webfilters_gpslink.gpid=webfilters_sqgroups.ID
                AND webfilters_gpslink.groupid=$GroupID";

            $results=$q->QUERY_SQL($sql);
            $ListOfGroups=array();
            foreach ($results as $index => $ligne) {
                $gpid=intval($ligne["gpid"]);
                if(!$this->availableGroups[$gpid]){
                    $this->acls_array[] = "# Sub-group {$PrefixGroup}{$gpid} unavailable";
                    continue;
                }
                $ListOfGroups[]="{$PrefixGroup}{$gpid}";
            }
            if(count($ListOfGroups)==0){
                $this->acls_array[] = "# Meta group {$PrefixGroup}$GroupID < $GroupName > is unavailable ( no items )";
                continue;
            }
            $this->availableGroups[$GroupID] = true;
            $this->acls_array[] = "# Meta group of $GroupID $GroupName is OK!";
            $this->acls_array[] = "acl $acls_name any-of ".@implode(" ",$ListOfGroups);
            $TEMPLATE = $this->acl_tpl($GroupID);
            if ($TEMPLATE <> null) { $this->acls_array[] = "deny_info $TEMPLATE $acls_name"; }

        }
       return true;
    }
    private function acl_tpl($aclid){

        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        if(!$q->FIELD_EXISTS("webfilters_sqgroups", "tplreset")){
            $q->QUERY_SQL("ALTER TABLE `webfilters_sqgroups` ADD `tplreset` smallint(1) ,ADD INDEX ( `tplreset` ) ");
        }



        if($GLOBALS["VERBOSE"]){echo "Group {$aclid} Checks acltpl...\n";}
        $sql="SELECT acltpl,tplreset FROM webfilters_sqgroups WHERE ID='$aclid'";
        $ligne=$q->mysqli_fetch_array($sql);

        if(intval($ligne["tplreset"])==1){return "TCP_RESET";}
        if($ligne["acltpl"]==null){if($GLOBALS["VERBOSE"]){echo "Group {$aclid} Checks acltpl == NULL...\n";}return null;}




        if($ligne["acltpl"]=="ARTICA_SLASH_SCREEN"){
            $sock=new sockets();
            $EnableSplashScreen=intval($sock->GET_INFO("EnableSplashScreen"));
            $EnableSplashScreenAsObject=$sock->GET_INFO("EnableSplashScreenAsObject");
            if(!is_numeric($EnableSplashScreenAsObject)){$EnableSplashScreenAsObject=0;}
            $SplashScreenURI=$sock->GET_INFO("SplashScreenURI");
            if(trim($SplashScreenURI)==null){$EnableSplashScreen=0;}
            $URLAR=parse_url($SplashScreenURI);
            if(isset($URLAR["host"])){$SplashScreenURI="http://$SplashScreenURI";}
            if(!preg_match("#^http.*#", $SplashScreenURI)){$SplashScreenURI="http://$SplashScreenURI";}
            return "303:$SplashScreenURI";
        }


        $acltpl=$ligne["acltpl"];
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $sql="SELECT template_name,lang,template_link,template_uri,emptytpl FROM squidtpls WHERE `zmd5`='$acltpl'";
        $ligne=$q->mysqli_fetch_array($sql);
        if(!is_array($ligne)){
            $ligne=array();
        }
        if(!isset($ligne["template_name"])){
            $ligne["template_name"]="";
        }
        $tplname=$ligne["template_name"];
        if($GLOBALS["VERBOSE"]){echo "Group {$aclid} Checks acltpl == $tplname...\n";}
        if(isset($ligne["lang"])) {
            if (is_numeric($ligne["lang"])) {
                $ligne["lang"] = "en";
            }
            if (!preg_match("#^ERR_.+#", $ligne["template_name"])) {
                $ligne["template_name"] = "ERR_" . $ligne["template_name"];
            }
            $template_path = "/usr/share/squid-langpack/templates/{$ligne["lang"]}/{$ligne["template_name"]}";
            if ($GLOBALS["VERBOSE"]) {
                echo "Group {$aclid} Checks acltpl == $template_path...\n";
            }

            if ($ligne["emptytpl"] == 1) {
                if (!is_file($template_path)) {
                    return null;
                }
                if ($GLOBALS["VERBOSE"]) {
                    echo "Group {$aclid} Checks acltpl == 200:{$ligne["template_name"]}..\n";
                }
                return "200:{$ligne["template_name"]}";
            }


            if ($ligne["template_link"] == 1) {
                $freeWebsLink = "?a=%a&e=%e&ee=%E&www=%H&url=%R&x=%x&T=$acltpl&M=%M";
                $ligne["template_uri"] = str_replace("%FREEWEBS%", $freeWebsLink, $ligne["template_uri"]);
                return $ligne["template_uri"];

            }
        }

        return "";

    }

    private function ParseTimeAclsGroup($ID){

        $sql="SELECT pattern,other FROM webfilters_sqitems WHERE gpid='$ID' ORDER BY other DESC";
        $this->acls_array[]="#$sql";
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

        $ligne=$q->mysqli_fetch_array($sql);
        $pattern_data=trim($ligne["pattern"]);
        $pattern=trim(base64_decode($ligne["other"]));
        if($GLOBALS["VERBOSE"]){echo "ParseTimeAclsGroup(): Group{$ID} Time: pattern = $pattern Alternate: $pattern_data\n";}
        if($pattern==null){
            if($pattern_data<>null){
                return $pattern_data;
            }
        }
        $TimeSpace=unserialize(base64_decode($ligne["other"]));
        if(!is_array($TimeSpace)){return null;}
        $fromtime=$TimeSpace["H1"];
        $tottime=$TimeSpace["H2"];
        $this->acls_array[]="# From: $fromtime, To: $tottime";
        if($fromtime<>null && $tottime<>null){
            $acltime=" $fromtime-$tottime";
        }
        if($fromtime=="00:00" && $tottime=="00:00"){
            $acltime=null;
        }

        $days=array("0"=>"M","1"=>"T","2"=>"W","3"=>"H","4"=>"F","5"=>"A","6"=>"S");
        $dd=array();
        foreach ($TimeSpace as $key=>$ligne){
            if(preg_match("#^day_([0-9]+)#", $key,$re)){
                $dayT=$re[1];
                if($ligne<>1){continue;}
                $dd[]=$days[$dayT];
            }
        }
        if(count($dd)>0){$days_text=@implode("", $dd);}

        if($acltime==null && $days_text==null){
            return "";
        }
        return "$days_text$acltime";

    }

    private function buildItems_time_quota($gpid,$timeid){
        $filename2="/etc/squid3/acls/container_$gpid.txt";
        $filename="/etc/squid3/acls/time_{$timeid}.txt";
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $sql="SELECT * FROM webfilters_sqitems WHERE enabled=1 AND gpid=$gpid";
        $results = $q->QUERY_SQL($sql);
        $DATA2=array();
        @unlink($filename2);

        $suffix="TIME:$gpid:$timeid ";

        if(!$q->ok){
            return 0;
        }

        foreach ($results as $index=>$ligne) {
            $ligne["pattern"]=trim($ligne["pattern"]);
            if($ligne["pattern"]==null){continue;}
            if($GLOBALS["VERBOSE"]){echo "Group $gpid {$ligne["pattern"]}\n";}
            if(!preg_match("#^(.*?)\s+(.+)#", $ligne["pattern"],$re)){
                continue;
            }
            $identifier=trim($re[1]);
            $budget=trim($re[2]);
            if(strpos($budget,"/")==0){
                continue;
            }

            if($GLOBALS["VERBOSE"]){echo "$suffix: identifier: $identifier\n";}

            $EXPLODED=explode("/",$budget);
            $EXPLODED[0]=trim($EXPLODED[0]);
            $EXPLODED[1]=trim($EXPLODED[1]);

            if(preg_match("#AD:(.+)#", $identifier,$re)){
                $ad=new ActiveDirectory();
                if($GLOBALS["VERBOSE"]){echo "$suffix: Get Members from: {$re[1]}\n";}
                $entries=$ad->search_users_from_groupName($re[1]);

                if(count($entries)==0){
                    if($GLOBALS["VERBOSE"]){echo "$suffix: Active directory {$re[1]} no user found\n";}
                    $DATA2[]="# Active directory {$re[1]} no user found";
                    continue;
                }
                foreach ($entries as $num=>$uid){
                    if($GLOBALS["VERBOSE"]){echo "$suffix:$uid\n";}
                    $DATA2[]="$uid {$EXPLODED[0]}/{$EXPLODED[1]}";
                    $GLOBALS["buildItems_time_quota"][$timeid][$identifier]="{$EXPLODED[0]}/{$EXPLODED[1]}";

                }
                continue;
            }

            $DATA2[]="$identifier {$EXPLODED[0]}/{$EXPLODED[1]}";
            $GLOBALS["buildItems_time_quota"][$timeid][$identifier]="{$EXPLODED[0]}/{$EXPLODED[1]}";
        }

        if(count($GLOBALS["buildItems_time_quota"][$timeid])==0){
            @unlink($filename);
            @touch($filename);
            return 0;
        }
        @unlink($filename);
        reset($GLOBALS["buildItems_time_quota"][$timeid]);
        $TT=array();
        foreach ($GLOBALS["buildItems_time_quota"][$timeid] as $num=>$val){
            $TT[]="$num $val";
        }

        @file_put_contents($filename, @implode("\n", $TT)."\n");
        @file_put_contents($filename2, @implode("\n", $DATA2)."\n");
        return count($TT);
    }


    private function buildItems($gpid,$returnFirstValue=false,$asMeta=false,$ItemsPrefix=null,$ItemsSuffix=null){
        $filename="/etc/squid3/acls/container_$gpid.txt";
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $ligne=$q->mysqli_fetch_array("SELECT bulkimport FROM webfilters_sqgroups WHERE ID='$gpid'");
        if(!isset($ligne["bulkimport"])){$ligne["bulkimport"]=null;}
        $strlen=0;
        if(!is_null($ligne["bulkimport"])) {
            $strlen = strlen($ligne["bulkimport"]);
        }
        if($strlen>6){
            if(!is_file($filename)){@touch($filename);}
            return 5000;
        }


        if($asMeta){$filename="/etc/squid3/acls-meta/container_$gpid.txt";}

        @unlink($filename);
        $f=$this->GetItems($gpid,null,$asMeta,$ItemsPrefix,$ItemsSuffix);
        if($returnFirstValue){
            return $f[0];
        }
        $c=count($f);
        if($c>0){
            @file_put_contents($filename, @implode("\n", $f)."\n");}
        return $c;
    }

    private function Build_Acls_time(){
        $days=array("0"=>"M","1"=>"T","2"=>"W","3"=>"H","4"=>"F","5"=>"A","6"=>"S");
        $sql="SELECT * FROM webfilters_sqtimes_rules WHERE enabled=1";
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $results = $q->QUERY_SQL($sql);
        foreach ($results as $index=>$ligne) {
            $TimeSpace=unserialize($ligne["TimeCode"]);
            if(strlen($TimeSpace["BEGINH"])==1){$TimeSpace["BEGINH"]="0{$TimeSpace["BEGINH"]}";}
            if(strlen($TimeSpace["BEGINM"])==1){$TimeSpace["BEGINM"]="0{$TimeSpace["BEGINM"]}";}
            if(strlen($TimeSpace["ENDH"])==1){$TimeSpace["ENDH"]="0{$TimeSpace["ENDH"]}";}
            if(strlen($TimeSpace["ENDM"])==1){$TimeSpace["ENDM"]="0{$TimeSpace["ENDM"]}";}
            $timeHour="{$TimeSpace["BEGINH"]}:{$TimeSpace["BEGINM"]}-{$TimeSpace["ENDH"]}:{$TimeSpace["ENDM"]}";

            $f=array();
            foreach ($TimeSpace["DAYS"] as $num=>$val){
                if($num==array()){continue;}
                if(!isset($days[$num])){continue;}
                if($days[$num]==array()){continue;}
                if($val<>1){continue;}
                $f[]= $days[$num];
            }
            $days_text=trim(@implode("", $f));
            if($days_text==null){$days_text="MTWHFAS";}
            $acls_name="Time{$ligne["ID"]}";
            $this->acls_array[]="acl $acls_name time $days_text $timeHour";

        }
    }


    public function build_http_access(){
        $this->build_http_access_time();
        return $this->http_access;

    }


    private function string_to_regex($pattern){
        if(preg_match("#^regex:(.+)#", trim($pattern),$re)){return $re[1];}
        if(preg_match("#^\*(.+)#",$pattern,$re)){
            $pattern=$re[1];
        }

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

        if(preg_match("#^\*-(.+)#",$pattern,$re)){
            $pattern="-$re[1]";
        }
        if(preg_match("#^(.+?)\*$#",$pattern,$re)){
            $pattern=$re[1];
        }

        if(preg_match("#^\*\\.(.+)#",$pattern,$re)){
            $pattern="(^|\.){$re[1]}";
        }

        if(preg_match("#^\*(.+)#",$pattern,$re)){
            $pattern="(^|\.){$re[1]}";
        }
        $pattern=str_replace("(^|\.)\.", "(^|\.)", $pattern);
        return $pattern;
    }

    public function dstdomain_parse($website){
        include_once(dirname(__FILE__).'/class.tcpip.inc');
        $website=trim(strtolower($website));
        if($website==null){return null;}
        $ip=new IP();
        if($ip->isIPAddress($website)){return null;}
        if($GLOBALS["VERBOSE"]){echo "dstdomain_parse:: $website\n";}


        if(preg_match("#(.*?)\.http$#", $website,$re)){
            if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website -> {$re[1]}\n";}
            $website=$re[1];
        }
        if(preg_match("#^http:\/\/(.*)#", $website,$re)){
            if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website -> {$re[1]}\n";}
            $website=$re[1];
        }
        if(preg_match("#^https:\/\/(.*)#", $website,$re)){
            if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website\n";}
            $website=$re[1];
        }
        if(preg_match("#^ftp:\/\/(.*)#", $website,$re)){
            if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website\n";}
            $website=$re[1];
        }
        if(preg_match("#^ftps:\/\/(.*)#", $website,$re)){
            if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website\n";}
            $website=$re[1];
        }
        if(preg_match("#^www\.(.*)#", $website,$re)){
            if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website\n";}
            $website=$re[1];
        }
        if(preg_match("#^(.+?)\/$#", $website,$re)){
            if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website -> {$re[1]}\n";}
            $website=$re[1];
        }
        if(preg_match("#^(.+?):([0-9]+)#", $website,$re)){$website=$re[1];}
        if(preg_match("#^http.*?:(.+)#", $website,$re)){
            if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website -> {$re[1]}\n";}
            $website=$re[1];
        }
        if(preg_match("#^\.(.+)#", $website,$re)){
            if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website -> {$re[1]}\n";}
            $website=$re[1];
        }

        if(preg_match("#(.+?):(.+)#", $website,$re)){
            if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website\n";}
            $website=$re[1];
        }
        if(preg_match("#^(.+?)\.(com|io)[0-9]+$#", trim($website),$re)){
            $website=$re[1].".{$re[2]}";
        }

        if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website\n";}
        $website=str_replace('.*.', '.', $website);
        $website=str_replace('*', '.', $website);
        $website=str_replace('$', '', $website);
        $website=str_replace('(', '', $website);
        $website=str_replace(')', '', $website);
        $website=str_replace(';', '.', $website);
        $website=str_replace(',', '.', $website);
        $website=str_replace('%', '', $website);
        $website=str_replace('+', '', $website);
        $website=str_replace('/', '', $website);
        $website=str_replace('?', '', $website);
        $website=str_replace('!', '', $website);
        $website=str_replace(':', '', $website);
        $website=str_replace('{', '', $website);
        $website=str_replace('}', '', $website);
        $website=str_replace('^', '', $website);
        $website=str_replace('\\', '', $website);
        $website=str_replace('\'', '', $website);
        $website=str_replace('"', '', $website);
        $website=str_replace('&', '', $website);
        $website=str_replace('<', '', $website);
        $website=str_replace('>', '', $website);
        $website=str_replace('[', '', $website);
        $website=str_replace(']', '', $website);

        if(strpos($website, ".")==0){return null;}

        if($GLOBALS["VERBOSE"]){echo "dstdomain_parse:: $website\n";}
        return $website;

    }


    public function clean_dstdomains($array=array()){
        if(!is_array($array)){return array();}
        if(count($array)==0){return array();}
        $q=new mysql_squid_builder();
        $f=array();
        $MAIN_MASTER=array();

        foreach ($array as $index=>$domain){
            $domain=strtolower($domain);
            $CLEANEDDOMS[$domain]=$domain;
        }
        ksort($CLEANEDDOMS);

        //search family
        foreach ($CLEANEDDOMS as $domain=>$nothing){
            $chapeau=0;
            $DOMAIN_SRC=$domain;
            $domain=trim(strtolower($domain));
            if(substr($domain, 0,1)=="^"){$domain=substr($domain,1,strlen($domain));$chapeau=1;}
            $domain=$this->dstdomain_parse($domain);
            if($domain==null){continue;}
            $MASTERDOMAIN=$q->GetFamilySites($domain);
            if($domain<>$MASTERDOMAIN){continue;}
            if(isset($MAIN_MASTER[".$MASTERDOMAIN"])){continue;}
            unset($CLEANEDDOMS[$DOMAIN_SRC]);
            if($chapeau){$MAIN_MASTER["$MASTERDOMAIN"]=true;}
            else{$MAIN_MASTER["$MASTERDOMAIN"]=false;}
        }

        reset($CLEANEDDOMS);
        //searchSubFamily
        foreach ($CLEANEDDOMS as $domain=>$nothing){
            $chapeau=0;
            $DOMAIN_SRC=$domain;
            $domain=trim(strtolower($domain));
            if(substr($domain, 0,1)=="^"){$domain=substr($domain,1,strlen($domain));$chapeau=1;}
            $domain=$this->dstdomain_parse($domain);
            $MASTERDOMAIN=$q->GetFamilySites($domain);
            if($domain==null){continue;}
             if(isset($MAIN_MASTER["$domain"])){continue;}
            $replaced=str_ireplace(".$MASTERDOMAIN", "", $domain);
            if(stripos("   $replaced", ".")>0){continue;}
            unset($CLEANEDDOMS[$DOMAIN_SRC]);
            if($chapeau){$MAIN_MASTER["$domain"]=true;}
            else{$MAIN_MASTER["$domain"]=false;}
        }

        reset($CLEANEDDOMS);
        krsort($CLEANEDDOMS);
        foreach ($CLEANEDDOMS as $domain=>$nothing){
            $chapeau=0;
            $domain=trim(strtolower($domain));
            if(substr($domain, 0,1)=="^"){$domain=substr($domain,1,strlen($domain));$chapeau=1;}

            $domain=$this->dstdomain_parse($domain);
            if($domain==null){continue;}


            $MASTERDOMAIN=$q->GetFamilySites($domain);
            if(isset($MAIN_MASTER["$MASTERDOMAIN"])){continue;}



            if($domain==$MASTERDOMAIN){
                if($chapeau){$MAIN_MASTER["$MASTERDOMAIN"]=true;}else{$MAIN_MASTER["$MASTERDOMAIN"]=false;}
                continue;
            }

            $MASTERDOMAINX=str_replace(".", "\.", $MASTERDOMAIN);

            if(preg_match("#^(.+?)\.(.+?)\.$MASTERDOMAINX$#", $domain,$re)){
                if(isset($MAIN_MASTER["{$re[2]}.$MASTERDOMAIN"])){continue;}
                if($chapeau){$MAIN_MASTER["$domain"]=true;}else{$MAIN_MASTER["$domain"]=false;}
            }

            if(preg_match("#^(.+?)\.$MASTERDOMAINX$#", $domain)){
                if($chapeau){$MAIN_MASTER["$domain"]=true;}else{$MAIN_MASTER["$domain"]=false;}
            }


        }

        foreach ($MAIN_MASTER as $domain=>$none){
            if($none){$f[]=$domain;}else{$f[]=".$domain";}

        }

        return $f;
    }


    private function GetItems_dstdomain($gpid){
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $array=array();
        $sql="SELECT *,length(pattern) FROM webfilters_sqitems WHERE enabled=1 AND gpid=$gpid ORDER BY length(pattern)";
        $results = $q->QUERY_SQL($sql);

        if(!$q->ok){$this->buildacls_bytype_logs[]="# Group $gpid, SQLite Error L.".__LINE__;}
        foreach ($results as $index=>$ligne) {
            $ligne["pattern"]=trim(strtolower($ligne["pattern"]));
            $array[]=$ligne["pattern"];

        }
        $this->buildacls_bytype_logs[]="# Group $gpid: ".count($array)." items L.".__LINE__;
        return $this->clean_dstdomains($array);
    }


    public function GetItems($gpid,$required=null,$asMeta=false,$ItemsPrefix=null,$ItemsSuffix=null){
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $Ipclass=new IP();
        if($asMeta){
            $this->buildacls_bytype_logs[]="# As meta = Yes L.".__LINE__;
            $q=new mysql();
        }
        $sql="SELECT GroupName,GroupType,enabled FROM webfilters_sqgroups WHERE ID='$gpid'";
        $ligne=$q->mysqli_fetch_array($sql);

        if(!$q->ok){
            $this->buildacls_bytype_logs[]="# SQlite Error L.".__LINE__;
        }
        $GroupeType=$ligne["GroupType"];


        if($GroupeType=="proxy_auth_statad"){
            include_once(dirname(__FILE__)."/class.external.ad.inc");
            $GroupName=$ligne["GroupName"];
            $ad=new external_ad_search();
            return $ad->MembersFromGroupName($GroupName);

        }

        $this->RequestedGroupeType=$GroupeType;
        if($ligne["enabled"]==0){return array();}
        if($required<>null){if($GroupeType<>$required){return array();}}

        $sql="SELECT * FROM webfilters_sqitems  WHERE enabled=1 AND gpid=$gpid";
        $c=0;
        $f=array();
        $Ipclass=new IP();

        if($GroupeType=="dstdomain"){
            $this->buildacls_bytype_logs[]="# $GroupeType GetItems_dstdomain($gpid) L.".__LINE__;
            return $this->GetItems_dstdomain($gpid);
        }

        $already=array();
        $results = $q->QUERY_SQL($sql);
        foreach ($results as $index=>$ligne) {
            $ligne["pattern"]=trim($ligne["pattern"]);
            if($ligne["pattern"]==null){continue;}
            if($GLOBALS["VERBOSE"]){echo "Group $gpid {$ligne["pattern"]} Type: $GroupeType\n";}
            if(trim($ligne["pattern"])==null){continue;}


            if($GroupeType=="port"){
                if(preg_match("#[a-z]+#", $ligne["pattern"])){
                    if($GLOBALS["VERBOSE"]){echo "! ! [$GroupeType] \"{$ligne["pattern"]}\"\n";}
                    continue;
                }
                if(!preg_match("#[0-9\-]+#", $ligne["pattern"])){
                    if($GLOBALS["VERBOSE"]){echo "! ! [$GroupeType] \"{$ligne["pattern"]}\"\n";}
                    continue;
                }

            }

            if($GroupeType=="arp"){
                $ligne["pattern"]=strtolower($ligne["pattern"]);
                $ligne["pattern"]=str_replace("-", ":", $ligne["pattern"]);
                if(!$Ipclass->IsvalidMAC($ligne["pattern"])){
                    if($GLOBALS["VERBOSE"]){echo "! ! [$GroupeType] \"{$ligne["pattern"]}\"\n";}
                    continue;
                }

            }




            if($GroupeType=="url_regex_extensions"){
                $ligne["pattern"]=str_replace(".", "", $ligne["pattern"]);
                $ligne["pattern"]=$this->string_to_regex($ligne["pattern"]);
                $ligne["pattern"]="\.{$ligne["pattern"]}$";
            }



            if($GroupeType=="arp"){
                $ligne["pattern"]=str_replace("-", ":", $ligne["pattern"]);
            }
            if($GroupeType=="src"){

                $ligne["pattern"]=str_replace("...",".",$ligne["pattern"]);
                $ligne["pattern"]=str_replace("..",".",$ligne["pattern"]);
                $ligne["pattern"]=str_replace("/32", "", $ligne["pattern"]);
                if(trim($ligne["pattern"])=="0.0.0.0/0"){$ligne["pattern"]="all";}
                if(trim($ligne["pattern"])=="0.0.0.0"){$ligne["pattern"]="all";}
                if(!$Ipclass->isIPAddressOrRange($ligne["pattern"])){
                    continue;
                }
            }

            if($GroupeType=="req_mime_type"){
                if(preg_match("#^regex:(.+)#", $ligne["pattern"],$re)){$ligne["pattern"]=$re[1];}else{
                    $ligne["pattern"]="^{$ligne["pattern"]}$";
                }
            }


            if($GroupeType=="url_regex"){
                $ligne["pattern"]=$this->SanitizeRegex($ligne["pattern"]);

                if(!$this->isaregex($ligne["pattern"])){
                    $ligne["pattern"]=$this->string_to_regex($ligne["pattern"]);
                }
            }

            if($GroupeType=="dstdom_regex"){
                if(!$this->isaregex($ligne["pattern"])){
                    $ligne["pattern"]=$this->string_to_regex($ligne["pattern"]);}
            }

            if($GroupeType=="urlpath_regex"){
                if(!$this->isaregex($ligne["pattern"])){
                    $ligne["pattern"]=$this->string_to_regex($ligne["pattern"]);}
            }

            if($GroupeType=="referer_regex"){
                if(!$this->isaregex($ligne["pattern"])){
                    $ligne["pattern"]=$this->string_to_regex($ligne["pattern"]);}
            }

            if($GroupeType=="dst"){
                $ligne["pattern"]=str_replace("...",".",$ligne["pattern"]);
                $ligne["pattern"]=str_replace("..",".",$ligne["pattern"]);
                $ligne["pattern"]=str_replace("/32", "", $ligne["pattern"]);
                if(!$Ipclass->isIPAddressOrRange($ligne["pattern"])){
                    if($GLOBALS["VERBOSE"]){echo "! ! [$GroupeType] \"{$ligne["pattern"]}\"\n";}
                    continue;}

            }
            if($GroupeType=="src"){
                $ligne["pattern"]=str_replace("...",".",$ligne["pattern"]);
                $ligne["pattern"]=str_replace("..",".",$ligne["pattern"]);
                if(!$Ipclass->isIPAddressOrRange($ligne["pattern"])){
                    if($GLOBALS["VERBOSE"]){echo "! ! [$GroupeType] \"{$ligne["pattern"]}\"\n";}
                    // It must not pass here !!
                    continue;
                }

            }


            if(isset($already[$ligne["pattern"]])){
                continue;
            }
            $already[$ligne["pattern"]]=true;

            if(preg_match("#AD:(.*?):(.+)#", $ligne["pattern"],$re)){
                $entries=$this->members_from_activedirectory($ligne["pattern"]);
                if($entries==null){continue;}
                $ligne["pattern"]=$entries;
            }
            if($GLOBALS["VERBOSE"]){echo "* * [$GroupeType] \"{$ligne["pattern"]}\"\n";}
            $f[]="$ItemsPrefix{$ligne["pattern"]}$ItemsSuffix";
            $c++;
        }
        return $f;
    }
    private function isaregex($pattern){
        $pattern=" $pattern";
        if(strpos($pattern, ".*")>0){return true;}
        if(strpos($pattern, ".+")>0){return true;}
        if(strpos($pattern, "[")>0){return true;}
        if(strpos($pattern, "]")>0){return true;}
        if(strpos($pattern, "(")>0){return true;}
        if(strpos($pattern, ")")>0){return true;}
        if(strpos($pattern, "?")>0){return true;}
        if(strpos($pattern, "\\")>0){return true;}


    }

    private function SanitizeRegex($pattern):string{

        if(preg_match("#^\?(.+)#",$pattern,$re)){
            $pattern=$re[1];
        }

        if(preg_match("#^\.\*\?(.+)#",$pattern,$re)){
            $pattern="(^|\.){$re[1]}";
        }
        if(preg_match("#^\*\.(.+)#",$pattern,$re)){
            $pattern="(^|\.){$re[1]}";
        }
        if(preg_match("#^\*(.+)#",$pattern,$re)){
            $pattern="(^|\.){$re[1]}";
        }


        return $pattern;
    }

    private function members_from_activedirectory($dn){
        if(trim($dn)==null){return;}
        $f=array();
        if(preg_match("#AD:(.*?):(.+)#", $dn,$re)){
            $dnEnc=$re[2];
            $LDAPID=$re[1];
            $dn=base64_decode($dnEnc);
            if($GLOBALS["VERBOSE"]){writelogs("LDAPID:$LDAPID DN=`$dn`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
            $ad=new ActiveDirectory($LDAPID);
            $USERS=$ad->search_users_from_group($dn,0);
        }else{
            squid_admin_mysql(1, "Failed to lookup $dn", __FUNCTION__, __FILE__, __LINE__, "activedirectory");
            return null;
        }

        if($GLOBALS["VERBOSE"]){writelogs("Array(USERS) = ".count($USERS)." items",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}

        if(count($USERS)==0){
            squid_admin_mysql(1, "Failed to lookup $dn no such user", __FUNCTION__, __FILE__, __LINE__, "activedirectory");
            return null;
        }

        foreach ($USERS as $dn=>$Props){
            $type=$Props["TYPE"];
            if($GLOBALS["VERBOSE"]){writelogs("DN=$dn TYPE=`$type`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}



            if($type=="group"){
                if($ad->LDAP_RECURSIVE==1){
                    writelogs("Group -> ParseLDAPSubUsersGroups($LDAPID,$dn)",__FUNCTION__,__FILE__,__LINE__);
                    $newrow=$this->ParseLDAPSubUsersGroups($LDAPID,$dn);
                    if(count($newrow>0)){
                        foreach ($newrow as $a=>$b){$f[]=$b;}
                    }
                }
                continue;
            }

            if(trim($Props["uid"]==null)){continue;}
            $Props["uid"]=trim(strtolower($Props["uid"]));

            $f[]=$Props["uid"];

        }

        return @implode("\n", $f)."\n";
    }

    private function ParseLDAPSubUsersGroups($LDAPID,$dn){
        $f=array();
        if($GLOBALS["VERBOSE"]){writelogs("LDAPID: $LDAPID, Parsing DN=$dn ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
        $ad=new ActiveDirectory($LDAPID);
        $Array=$ad->search_users_from_group($dn);
        if($GLOBALS["VERBOSE"]){writelogs("DN=$dn items=`". count($Array)."`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
        if($ad->ldap_last_error<>null){writelogs("FAILED to lookup dn $dn",__FUNCTION__,__FILE__,__LINE__);}

        if(count($Array)==0){
            writelogs("Lookup dn $dn return no user !!!",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            return array();
        }


        if($GLOBALS["VERBOSE"]){writelogs("DN=$dn START LOOP",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}

        foreach ($Array as $dn=>$GPARR){
            $type=$GPARR["TYPE"];
            if($GLOBALS["VERBOSE"]){writelogs("DN=$dn type=`$type`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}

            if($type=="group"){
                if($ad->LDAP_RECURSIVE==1){
                    writelogs("Group -> ParseUsersGroups($LDAPID,$dn)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
                    $newrow=$this->ParseLDAPSubUsersGroups($LDAPID,$dn);
                    if(is_array($newrow)){
                        if(count($newrow>0)){
                            foreach ($newrow as $a=>$b){$f[]=$b;}
                        }
                    }
                }
                continue;
            }
            if($GLOBALS["VERBOSE"]){writelogs("DN=$dn ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
            $cn=trim($GPARR["uid"]);
            if(trim($cn)==null){continue;}
            $cn=str_replace(" ", "%20", $cn);
            $f[]=$cn;
        }


        return $f;
    }



    private function build_http_access_time(){
        $sql="SELECT webfilters_sqtimes_assoc . * , `webfilters_sqtimes_rules`.`Allow`
		FROM `webfilters_sqtimes_assoc` , `webfilters_sqgroups` , `webfilters_sqtimes_rules`
		WHERE 
			webfilters_sqtimes_assoc.gpid = webfilters_sqgroups.ID
			AND webfilters_sqtimes_rules.ID = webfilters_sqtimes_assoc.TimeRuleID
			AND webfilters_sqgroups.enabled =1
			AND webfilters_sqtimes_rules.enabled =1
			
		ORDER BY webfilters_sqtimes_rules.`Allow` DESC";
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

        $results = $q->QUERY_SQL($sql);
        $alreadyTpl=array();
        if(!$q->ok){echo "\n---------------------\n$q->mysql_error\n$sql\n\nLine: ".__LINE__."\nClass:".__CLASS__."\nFunction: ".__FUNCTION__."\nFile:".__FILE__."\n---------------------\n";}
        foreach ($results as $index=>$ligne) {
            $Access="deny";
            if($ligne["Allow"]==1){$Access="allow";}
            if(!isset($this->availableGroups[$ligne["gpid"]])){continue;}
            $this->http_access[]="http_access $Access Group{$ligne["gpid"]} Time{$ligne["TimeRuleID"]}";
            if($ligne["Allow"]==0){
                if(!isset($alreadyTpl[$ligne["TimeRuleID"]])){
                    $this->deny_info_time($ligne["TimeRuleID"]);
                    $this->http_access[]="deny_info ERROR_TIMERULE_{$ligne["TimeRuleID"]} Time{$ligne["TimeRuleID"]}";
                    $alreadyTpl[$ligne["TimeRuleID"]]=true;
                }
            }

        }


    }

    private function deny_info_time($ID){
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $ligne=$q->mysqli_fetch_array("SELECT TemplateError,`Allow` FROM webfilters_sqtimes_rules WHERE ID='$ID'");
        $filename="/usr/share/squid-langpack/en/ERROR_TIMERULE_$ID";
        $filename2="/usr/share/squid-langpack/templates/ERROR_TIMERULE_$ID";
        @unlink($filename);
        if($ligne["Allow"]==1){return;}

        $array=unserialize(base64_decode($ligne["TemplateError"]));
        if(!isset($array["TITLE"])){$array["TITLE"]="Access denied for this time";}
        if(!isset($array["ERROR"])){$array["ERROR"]="The requested access could not be allowed";}
        if(!isset($array["EXPLAIN"])){$array["EXPLAIN"]="Your are not allowed to acces to internet at this time";}
        if(!isset($array["REASON"])){$array["REASON"]="Surfing to internet banned";}

        @mkdir("/usr/share/squid-langpack/en",0755,true);
        if(!is_dir("/usr/share/squid-langpack/en/templates")){@mkdir("/usr/share/squid-langpack/en/templates",0755,true);}
        if(!is_dir("/usr/share/squid-langpack/templates")){@mkdir("/usr/share/squid-langpack/templates",0755,true);}

        $content="<table class=\"w100 h100\">
		<tr>
		<td class=\"c m\">
		<table style=\"margin:0 auto;border:solid 1px #560000\">
		<tr>
		<td class=\"l\" style=\"padding:1px\">
		<div style=\"width:346px;background:#E33630\">
			<div style=\"padding:3px\">
				<div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\">
					<div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\">
						<h1>ERROR: {$array["TITLE"]}</h1>
					</div>
					<div class=\"c\" style=\"font:bold 13px arial;text-transform:uppercase;color:#FFF;padding:8px 0\">Proxy Error</div>
					<div style=\"background:#F7F7F7;padding:20px 28px 36px\"> 
						<div id=\"titles\"> 
							<h1>ERROR</h1> <h2>{$array["ERROR"]}</h2> 
						</div> <hr>  
							<div id=\"content\"> <p>{$array["EXPLAIN"]}</p>  
							<blockquote id=\"error\"> <p><b>{$array["REASON"]}</b></p> </blockquote>  
							<p>Access control configuration prevents your request from being allowed at this time. Please contact your service provider if you feel this is incorrect.</p>  <p>Your cache administrator is <a href=\"mailto:%w%W\">%w</a>.</p> <br> </div>  <hr> <div id=\"footer\"> <p>Generated %T by %h (%s)</p> <!-- %c --> </div> </div></div>
								</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>";
        $header=@file_get_contents(dirname(__FILE__)."/databases/squid.default.header.db");
        $newheader=str_replace("{TITLE}", $array["TITLE"], $header);
        $templateDatas="$newheader$content</body></html>";
        @file_put_contents($filename, $templateDatas);
        @file_put_contents($filename2, $templateDatas);


    }





    private function compilation_params():array{

        if(isset($GLOBALS["COMPILE_SQUID_TOKENS"])){return $GLOBALS["COMPILE_SQUID_TOKENS"];}


        $SQUID_ARRAY=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("COMPILE_SQUID_TOKENS"));
        if(is_array($SQUID_ARRAY)){
            if(count($SQUID_ARRAY)>3){
                return $SQUID_ARRAY;
            }
        }

        if(!class_exists("unix")){
            include_once("/usr/share/artica-postfix/framework/class.unix.inc");
            include_once("/usr/share/artica-postfix/framework/frame.class.inc");
        }
        $unix=new unix();
        $squidbin=$unix->find_program("squid");
        if($squidbin==null){$squidbin=$unix->find_program("squid3");}
        exec("$squidbin -v 2>&1",$results);
        $text=@implode("\n", $results);
        $GLOBALS["COMPILE_SQUID_TOKENS"]=array();
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

        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("COMPILE_SQUID_TOKENS",serialize($GLOBALS["COMPILE_SQUID_TOKENS"]));
        return $GLOBALS["COMPILE_SQUID_TOKENS"];
    }
}