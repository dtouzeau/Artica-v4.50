<?php
include_once(dirname(__FILE__).'/class.dansguardian.inc');
include_once(dirname(__FILE__).'/class.sockets.inc');
include_once(dirname(__FILE__)."/class.c-icap-filter.inc");




class icap{
    var $ASROOT=false;
    var $IS_50=false;
    var $IS_30=false;
    var $IS_31=false;
    var $IS_32=false;
    var $IS_33=false;

    var $ICAP_SERVICES_COUNT=0;
    var $enable_kavproxy=0;
    var $enable_cicap=0;
    var $enable_squidguard=0;
    var $EnableClamavInCiCap=0;
    var $icap_final_string;
    var $EnableSquidGuardInCiCAP=0;
    var $EnableKavICAPRemote=0;
    var $KavICAPRemoteAddr=null;
    var $KavICAPRemotePort=0;
    var $enable_metascanner=0;
    var $RemoteApplianceServer=null;
    var $CicapEnabled=0;
    var $IcapServerAddress="127.0.0.1";
    public $icap_lines=array();
    public $CountDeServices=0;
    private $AntivirusEnabledCentral=1;
    private $SquidDisableAllFilters=0;
    private $SquidUrgency=0;
    private $SQUID_VERSION=0;
    private $EnableSecurityAppliance=0;
    private $SERVICE_AV=array();

    function __construct(){
        $users=new usersMenus();
        $ini=new Bs_IniHandler();
        $sock=new sockets();
        $SQUID_ICAP_ENABLED=$users->SQUID_ICAP_ENABLED;

        $ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
        $ini->loadString($ArticaSquidParameters);
        $this->enable_cicap=$sock->GET_INFO('CicapEnabled');
        if(!is_numeric($this->enable_cicap)){$this->enable_cicap=0;}

        $this->enable_squidguard=$sock->GET_INFO('squidGuardEnabled');

        $this->AntivirusEnabledCentral=$sock->GET_INFO("AntivirusEnabledCentral");

        $this->SquidDisableAllFilters=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableAllFilters"));
        $this->SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
        $this->EnableSecurityAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSecurityAppliance"));

        $this->EnableClamavInCiCap=$sock->GET_INFO("EnableClamavInCiCap");
        $this->EnableSquidGuardInCiCAP=$sock->GET_INFO("EnableSquidGuardInCiCAP");
        $this->EnableKavICAPRemote=$sock->GET_INFO("EnableKavICAPRemote");
        $this->KavICAPRemoteAddr=$sock->GET_INFO("KavICAPRemoteAddr");
        $this->KavICAPRemotePort=$sock->GET_INFO("KavICAPRemotePort");
        $this->enable_metascanner=$sock->GET_INFO("KavMetascannerEnable");

        if($this->KavICAPRemoteAddr==null){$this->EnableKavICAPRemote=0;}
        if(!is_numeric($this->AntivirusEnabledCentral)){$this->AntivirusEnabledCentral=1;}
        if(!is_numeric($this->KavICAPRemotePort)){$this->EnableKavICAPRemote=0;}
        if(!is_numeric($this->enable_kavproxy)){$this->enable_kavproxy=0;}
        if(!is_numeric($this->EnableKavICAPRemote)){$this->EnableKavICAPRemote=0;}

        if(!is_numeric($this->enable_metascanner)){$this->enable_metascanner=0;}
        if(!is_numeric($this->EnableSquidGuardInCiCAP)){
            $this->EnableSquidGuardInCiCAP=0;
            $sock->SET_INFO("EnableSquidGuardInCiCAP","0");
        }




        if(posix_getuid()==0){$this->ASROOT=true;}
        if(isset($GLOBALS["AS_ROOT"])){if($GLOBALS["AS_ROOT"]){$this->ASROOT=true;}}

        $this->SQUID_VERSION=$users->SQUID_VERSION;
        if(preg_match('#^([0-9]+)\.([0-9]+)#',$this->SQUID_VERSION,$re)){
            if($re[1]>=3){
                $this->IS_30=true;
                if($re[2]==1){$this->IS_31=true;}
                if($re[2]>=2){$this->IS_32=true;$this->IS_31=true;}
                if($re[2]>=3){$this->IS_33=true;$this->IS_32=true;$this->IS_31=true;}
                if($re[2]>=4){$this->IS_40=true;$this->IS_33=true;$this->IS_32=true;$this->IS_31=true;}
            }
            if($re[1]>=5){
                $this->IS_40=true;
                $this->IS_50=true;
            }
        }


        if(!$users->C_ICAP_INSTALLED){$this->enable_cicap=0;}
        if(!$users->SQUIDGUARD_INSTALLED){$this->EnableSquidGuardInCiCAP=0;}
        $this->icap_lines=array();



    }

    public function icap_sandbox():int{
        $CicapEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled"));
        if($CicapEnabled==0){return 0;}
        $CICAPEnableSandBox=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CICAPEnableSandBox"));
        if($CICAPEnableSandBox==0){return 0;}
        $C_ICAP_RECORD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("C_ICAP_RECORD"));
        if($C_ICAP_RECORD==0){return 0;}
        return 1;
    }

    public function build_services(){
        $respd="respmod_precache";
        $resqd="reqmod_precache";
        $CicapEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled"));
        if($this->EnableSecurityAppliance==1){$CicapEnabled=0;}
        $EnableCICAPDNSBL=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCICAPDNSBL");
        $CICAPEnableSandBox=$this->icap_sandbox();
        if($CicapEnabled==0){$EnableCICAPDNSBL=0;}
        $EnableClamavInCiCap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavInCiCap"));
        $this->icap_lines[]="# CicapEnabled=$CicapEnabled, EnableCICAPDNSBL=$EnableCICAPDNSBL CICAPEnableSandBox=$CICAPEnableSandBox";

        if($this->SquidUrgency==1){
            $this->icap_lines[]="# Emergency mode enabled!!";
            $conf[]=@implode("\n", $this->icap_lines)."\n";
            @file_put_contents("/etc/squid3/icap.conf", @implode("\n", $conf));
            $this->DisableICAPTemporary();
            return;

        }
        if($this->SquidDisableAllFilters==1){
            $this->icap_lines[]="# All filters as been disabled ( SquidDisableAllFilters )";
            $conf[]=@implode("\n", $this->icap_lines)."\n";
            @file_put_contents("/etc/squid3/icap.conf", @implode("\n", $conf));
            $this->DisableICAPTemporary();
            return;

        }

        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        if(!$q->TABLE_EXISTS("c_icap_services")){
            $this->icap_lines[]="# c_icap_services table does not exists";
            $conf[]=@implode("\n", $this->icap_lines)."\n";
            @file_put_contents("/etc/squid3/icap.conf", @implode("\n", $conf));
            $this->DisableICAPTemporary();
            return;
        }

        if($EnableCICAPDNSBL==1){
            $sql="UPDATE c_icap_services SET enabled=1 WHERE ID=12";
            $q->QUERY_SQL($sql);
        }else{
            $sql="UPDATE c_icap_services SET enabled=0 WHERE ID=12";
            $q->QUERY_SQL($sql);

        }



        $this->icap_lines[]="# P108...";
        $sql="SELECT * FROM c_icap_services WHERE enabled=1 ORDER BY zOrder";
        $results=$q->QUERY_SQL($sql);

        if(!$q->ok){
            $q->mysql_error=str_replace("\n", " ", $q->mysql_error);
            $this->icap_lines[]="# MySQL error $q->mysql_error [".__LINE__."]";
            $conf[]=@implode("\n", $this->icap_lines)."\n";
            @file_put_contents("/etc/squid3/icap.conf", @implode("\n", $conf));
            return;
        }



        $CountDeServices=count($results);
        $KWTSEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSEnabled"));
        $KWTSIPAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSIPAddr");
        $this->icap_lines[]="# Kaspersky Web traffic Security = $KWTSEnabled [$KWTSIPAddr]";


        if($KWTSEnabled==1){
            if($KWTSIPAddr<>null) {
                if ($CountDeServices == 0) {$CountDeServices = 1;}
            }else{
                $KWTSEnabled=0;
            }
        }
        if($CicapEnabled==1){
            if($CICAPEnableSandBox==1){
                if ($CountDeServices == 0) {$CountDeServices = 1;}
            }
        }else{
            $CICAPEnableSandBox=0;
        }



        $this->icap_lines[]="#";
        $this->icap_lines[]="# Table c_icap_services report $CountDeServices service(s) [".__LINE__."]";

        if($CountDeServices==0){
            $this->icap_lines[]="# Aborting, no ICAP service as been defined";
            $conf[]=@implode("\n", $this->icap_lines)."\n";
            @file_put_contents("/etc/squid3/icap.conf", @implode("\n", $conf));
            return;
        }
        $overload["block"]="block";
        $overload["bypass"]="bypass";
        $overload["wait"]="wait";
        $overload["force"]="force";

        if($KWTSEnabled==1){
            $bypass="off";
            $KWTSIPAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSIPAddr");
            $KWTSByPass=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSByPass"));
            $KWTSPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSPort"));
            $KWTSReqMode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSReqMode"));
            $KWTSRepMode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSRepMode"));
            $KWTSOverload=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSOverload"));
            $KWTSMaxCon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSMaxCon"));
            if($KWTSPort==0){$KWTSPort=1344;}
            if($KWTSReqMode==null){$KWTSReqMode="av/reqmod";}
            if($KWTSRepMode==null){$KWTSRepMode="av/respmod";}
            if($KWTSOverload==null){$KWTSOverload="wait";}
            if($KWTSMaxCon==null){$KWTSMaxCon=5000;}
            $on_overload=$overload[$KWTSOverload];
            if(intval($KWTSByPass==1)){$bypass="on";}
            $maxconn_text="on-overload=$on_overload max-conn=$KWTSMaxCon ";
            $opts="{$maxconn_text} bypass=$bypass";
            $this->icap_lines[]="icap_service kwts_req $resqd icap://$KWTSIPAddr:$KWTSPort/$KWTSReqMode $opts";
            $this->icap_lines[]="icap_service kwts_res $respd icap://$KWTSIPAddr:$KWTSPort/$KWTSRepMode $opts";
            $this->CountDeServices++;

        }

        $this->icap_lines[]="# C-ICAP Enabled......: $CicapEnabled";

        if($CicapEnabled==1){
            $ligne = $q->mysqli_fetch_array("SELECT * FROM c_icap_services WHERE ID='1'");
            $enabled=intval($ligne["enabled"]);
            $this->icap_lines[]="# C-ICAP Enabled in DB: $enabled";

            $cicap_addr=$this->CICAP_URI();
            if($enabled==1) {
                    $routing        = "off";
                    $bypass         = "off";
                    $on_overload    = $ligne["overload"];
                    $maxconn        = $ligne["maxconn"];
                    if ($ligne["routing"] == 1) {$routing = "on";}
                    if (intval($ligne["bypass"]) > 0) {$bypass = "on";}
                    if($on_overload==null){$on_overload="bypass";}

                    if($maxconn==100){$maxconn=200;}
                    if($maxconn>0){$maxconn_text="on-overload=$on_overload max-conn=$maxconn ";}
                    $opts="{$maxconn_text}routing=$routing bypass=$bypass";
                    $this->icap_lines[]="# Clamav..............: $EnableClamavInCiCap";
                    $this->icap_lines[]="# SandBox.............: $CICAPEnableSandBox";

                    if($EnableClamavInCiCap==1){
                        $this->SERVICE_AV["antivir_rep"]=True;
                        $this->SERVICE_AV["antivir_req"]=True;
                        $this->icap_lines[]="icap_service antivir_rep $respd icap://$cicap_addr/srv_clamav $opts";
                        $this->icap_lines[]="icap_service antivir_req $resqd icap://$cicap_addr/srv_clamav $opts";
                        $this->CountDeServices++;
                        $this->icap_classes["antivir_rep"]=1;
                        $this->icap_classes["antivir_req"]=1;
                        $this->CountDeServices++;
                    }

                    if($CICAPEnableSandBox==1){
                        $this->icap_lines[]="icap_service sandbox_get reqmod_precache icap://$cicap_addr/sandbox $opts";
                        $this->icap_lines[]="icap_service sandbox_post respmod_precache icap://$cicap_addr/sandbox $opts";
                        $this->CountDeServices++;
                    }
            }


        }

        foreach ($results as $index=>$ligne) {
            $ID                 = $ligne["ID"];
            if(($ID==1) OR ($ID==2)){continue;}
            $routing            = "off";
            $bypass             = "off";
            $on_overload        = $ligne["overload"];
            $maxconn_text       = null;
            $icap_server        = $ligne["icap_server"];
            $ipaddr             = $ligne["ipaddr"];
            $port               = $ligne["listenport"];
            $maxconn            = $ligne["maxconn"];
            $status             = intval($ligne["status"]);
            if($ligne["routing"]==1){$routing="on";}
            if(intval($ligne["bypass"])>0){$bypass="on";}
            $this->icap_lines[]="#";
            $this->icap_lines[]="# Service ID $ID {$ligne["service_name"]} $ipaddr:$port [".__LINE__."]";
            if($status==5){
                $this->icap_lines[]="# Service is in emergency status";
                continue;
            }
            if(!isset($overload[$on_overload])){$on_overload="bypass";}

            if($maxconn==100){$maxconn=200;}
            if($maxconn>0){
                $maxconn_text="on-overload=$on_overload max-conn=$maxconn ";
            }
            $opts="{$maxconn_text}routing=$routing bypass=$bypass";
            $uriAddr="$ipaddr:$port";
            if($ID==12){$icap_server="webfilter"; }

            $this->CountDeServices++;
            $ICAP_NAME="{$icap_server}$ID";
            $ICAP_NAME=str_replace("/","_",$ICAP_NAME);
            $this->icap_classes[$ICAP_NAME]=$ID;
            $this->icap_lines[]="icap_service $ICAP_NAME {$ligne["respmod"]} icap://$uriAddr/$icap_server $opts";


        }

        if($this->CountDeServices==0){
            $this->icap_lines[]="# No services after the MySQL loop";
            $conf[]=@implode("\n", $this->icap_lines)."\n";
            @file_put_contents("/etc/squid3/icap.conf", @implode("\n", $conf));
            $this->DisableICAPTemporary();
            return;
        }


        $this->icap_lines[]="";
        $this->icap_lines[]="# ICAP configs [".__LINE__."]";
        $this->icap_lines[]="icap_enable on";
        $this->icap_lines[]="icap_preview_size 1024";
        $this->icap_lines[]="icap_service_failure_limit -1";
        $this->icap_lines[]="icap_preview_enable on";
        $this->icap_lines[]="icap_send_client_ip on";
        $this->icap_lines[]="icap_send_client_username on";
        $this->icap_lines[]="icap_client_username_header X-Authenticated-User";
        $this->icap_lines[]="icap_client_username_encode on";


        $this->ADAPTATIONS();
        $conf[]="# --------- ICAP Services: $this->CountDeServices service(s) [".__LINE__."]\n";
        $conf[]=@implode("\n", $this->icap_lines)."\n";
        @file_put_contents("/etc/squid3/icap.conf", @implode("\n", $conf));
        $this->DisableICAPTemporary();

    }

    private function DisableICAPTemporary(){
        $DisableICAPTemporary=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableICAPTemporary");
        if($DisableICAPTemporary==0){
            if(is_file("/etc/squid3/icap.conf.bak")){@unlink("/etc/squid3/icap.conf.bak");}
            return;
        }
        if($DisableICAPTemporary==1){
            if(is_file("/etc/squid3/icap.conf.bak")){@unlink("/etc/squid3/icap.conf.bak");}
            @copy("/etc/squid3/icap.conf","/etc/squid3/icap.conf.bak");
            @file_put_contents("/etc/squid3/icap.conf","\n");
        }
    }

    private function icap_policies():array{
        $GLOBALS["ICAP_POLICIES"]=array();
        if(count($GLOBALS["ICAP_POLICIES"])>0){return $GLOBALS["ICAP_POLICIES"];}
        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $results = $q->QUERY_SQL("SELECT * FROM squid_icap_acls WHERE enabled=1 ORDER BY zorder");
        $squid_acls_groups = new squid_acls_groups();
        $array_access["url_rewrite_access_deny"] = "deny";
        $array_access["url_rewrite_access_allow"] = "allow";

        $this->icap_lines[]="# ACLs for ICAP <".count($results)."> [".__LINE__."]";
        $isSquid5=$this->IS_50;
        if($isSquid5){
            $this->icap_lines[]="# Squid v5.x [".__LINE__."]";
        }

        foreach ($results as $index => $ligne) {
            $aclport = $ligne["aclport"];
            $PortGroup = null;
            $ztype = $ligne["ztype"];
            $rulename = $ligne["rulename"];
            if ($aclport > 0) {
                $PortGroup = " MyPort1";
            }
            $aclid = $ligne["ID"];
            $annotate_transaction=null;


            if($isSquid5) {
                $annotate_transaction = "AnnotateICAPAcl$aclid";
                $this->icap_lines[]="acl $annotate_transaction annotate_transaction icaprule=$aclid";
            }

            $acls_array = $squid_acls_groups->buildacls_bytype_items($aclid, false, "squid_icap_acls_link");
            $this->icap_lines[]="# ACLs for ICAP <$aclid> <$rulename> items:".count($acls_array);
            if(count($acls_array)==0) {
                $this->icap_lines[]="# ICAP_ACCESSR:$aclid";
                $GLOBALS["ICAP_POLICIES"][] = "{$array_access[$ztype]}{$PortGroup} all $annotate_transaction";
                continue;
            }
            $this->icap_lines[]="# ICAP_ACCESSR:$aclid";
            $acls_array[]=$annotate_transaction;
            $GLOBALS["ICAP_POLICIES"][] = "{$array_access[$ztype]}{$PortGroup} " . trim(@implode(" ", $acls_array));


        }
        return $GLOBALS["ICAP_POLICIES"];
    }





    private function ADAPTATIONS(){
        $unix=new unix();
        $SquidDebugPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDebugPort"));
        $IcapForwardSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IcapForwardSSL"));
        $EnableClamavInCiCap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavInCiCap"));
        $acls=new squid_acls_groups();
        $acls_deny_center=$acls->buildacls_bytype("adaptation_access_deny");

        $KWTSEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSEnabled"));
        $KWTSIPAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSIPAddr");
        $KWTSDisable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSDisable"));
        $CICAPEnableSandBox=$this->icap_sandbox();
        if($KWTSIPAddr==null){$KWTSEnabled=0;}

        if($KWTSEnabled==1){
            $this->icap_classes["kwts_req"]=1;
            $this->icap_classes["kwts_res"]=1;

        }
        if($CICAPEnableSandBox==1){
            $this->icap_classes["sandbox_get"]=1;
            $this->icap_classes["sandbox_post"]=1;
        }
        $icap_policies=$this->icap_policies();
        $isSquid5=$this->IS_50;
        $annot=null;
        $annotOK=null;
        $annotTestAV=null;
        if($isSquid5) {
            $annot = " AnnotateICAPWhite";
            $annotOK = " AnnotateICAPOK";
            $annotTestAV = " AnnotateICAPForceAV";
            $this->icap_lines[]="acl$annot annotate_transaction icapwhite=yes";
            $this->icap_lines[]="acl$annotOK annotate_transaction icapscan=yes";
            $this->icap_lines[]="acl$annotTestAV annotate_transaction icapavtest=yes";
        }

        $this->icap_lines[]="";
        $this->icap_lines[]="# Adaptations classes KWTSEnabled:$KWTSEnabled CICAPEnableSandBox:$CICAPEnableSandBox [".__LINE__."]";
        $this->icap_lines[]="# EnableClamavInCiCap:$EnableClamavInCiCap";

        $this->icap_lines[]="acl CONNECT method CONNECT";
        $this->icap_lines[]="acl ForceICAPDomains dstdomain .articatech.download";
        $this->icap_lines[]="acl DenyICAPDomains dstdomain detectportal.firefox.com";
        $this->icap_lines[]="acl DenyICAPDomains dstdomain .teamviewer.com";
        $this->icap_lines[]="acl DenyICAPDomains dstdomain .vmware.com";
        $this->icap_lines[]="acl DenyICAPDomains dstdomain ".$unix->hostname_g();
        $this->icap_lines[]="acl DenyICAPIPs dst 216.58.208.0/24";
        $ICAPNoTrustWhiteLists=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ICAPNoTrustWhiteLists"));

        $this->icap_lines[]="";

        foreach ($this->icap_classes as $servicekey=>$ID){
            $ACL_AV=false;
            $this->icap_lines[]="# Adaptation for service $servicekey/$ID [".__LINE__."]";
            $this->icap_lines[]="adaptation_service_set class_$servicekey $servicekey";

            foreach ($icap_policies as $rrule){
                $this->icap_lines[]="adaptation_access class_$servicekey $rrule";
            }


            if($SquidDebugPort==1){$this->icap_lines[]="adaptation_access class_$servicekey deny MyTestPort$annot";}
            if($KWTSEnabled==1){
                if($KWTSDisable==1){
                    if($servicekey=="kwts_req"){
                        $this->icap_lines[]="adaptation_access class_$servicekey deny all";
                        continue;
                    }
                    if($servicekey=="kwts_res"){
                        $this->icap_lines[]="adaptation_access class_$servicekey deny all";
                        continue;
                    }
                }
            }

            if($EnableClamavInCiCap==0){
                if($servicekey=="antivir_rep" OR $servicekey=="antivir_req"){
                    $this->icap_lines[]="# CLAM Antivirus is disabled, aborting [".__LINE__."]";
                    continue;
                }
            }



            if(isset($this->SERVICE_AV[$servicekey])){
                if($IcapForwardSSL==0){
                    $this->icap_lines[]="adaptation_access class_{$servicekey} deny CONNECT";
                }
            }
            $this->icap_lines[] = "adaptation_access class_{$servicekey} allow ForceICAPDomains$annotTestAV";
            $this->icap_lines[] = "adaptation_access class_{$servicekey} deny ToArticaWWW$annot";
            $this->icap_lines[] = "adaptation_access class_{$servicekey} deny squidclient$annot";
            $this->icap_lines[] = "adaptation_access class_{$servicekey} deny MgrInfoUri$annot";
            $this->icap_lines[] = "adaptation_access class_{$servicekey} deny MgrInfoUri2$annot";

            if($ICAPNoTrustWhiteLists==0) {
                $this->icap_lines[] = "adaptation_access class_{$servicekey} deny DenyICAPDomains$annot";
                $this->icap_lines[] = "adaptation_access class_{$servicekey} deny WindowsUpdates$annot";
                $this->icap_lines[] = "adaptation_access class_{$servicekey} deny GlobalWhitelistDSTNet$annot";
                $this->icap_lines[] = "adaptation_access class_{$servicekey} deny DenyICAPIPs$annot";
            }
            if(count($acls_deny_center)>0){
                reset($acls_deny_center);
                foreach ($acls_deny_center as $index => $line){
                    $this->icap_lines[]="adaptation_access class_{$servicekey} deny $line$annot";
                }
            }


            if($ID==1){$ACL_AV=true;}
            if($ID==2){$ACL_AV=true;}
            if($ID==3){$ACL_AV=true;}
            if($ID==4){$ACL_AV=true;}
            if($ID==5){$ACL_AV=true;}
            if($ID==6){$ACL_AV=true;}
            if($ID==7){$ACL_AV=true;}
            if($ID==8){$ACL_AV=true;}


            if($ACL_AV) {
                //-------------------------------------------------------------------------------------------
                if ($ICAPNoTrustWhiteLists == 0) {
                    $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
                    $acls = new squid_acls();
                    $acls->Build_Acls();
                    $sql = "SELECT *  FROM `global_whitelist` WHERE enabled=1 and deny_icap=1";
                    $results = $q->QUERY_SQL($sql);
                    foreach ($results as $index => $ligne) {
                        $acls_name = "Group{$ligne["groupid"]}";
                        $this->icap_lines[] = "# Group {$ligne["groupid"]} not used";
                        if (!$acls->availableGroups[$ligne["groupid"]]) {
                            $this->icap_lines[] = "# Group {$ligne["groupid"]} not used";
                            continue;
                        }
                        $this->icap_lines[] = "adaptation_access class_{$servicekey} deny $acls_name$annot";

                    }
                }

                //-------------------------------------------------------------------------------------------


                if ($ICAPNoTrustWhiteLists == 0) {
                    if (isset($GLOBALS["WHITELISTED_MIMETYPE"])) {
                        if (count($GLOBALS["WHITELISTED_MIMETYPE"]) > 0) {
                            $this->icap_lines[] = "adaptation_access class_{$servicekey} deny StreamMimeWhite";
                        }
                    }

                }
            }




            $this->icap_lines[]="adaptation_access class_{$servicekey} allow all$annotOK";

        }


    }

    private function CICAP_ADDR():string{
        $unix=new unix();
        $this->CICAPListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CICAPListenInterface"));
        if($this->CICAPListenInterface==null){$this->CICAPListenInterface="lo";}
        return $unix->InterfaceToIPv4($this->CICAPListenInterface);

    }


    private function CICAP_URI(){
        if(!class_exists("unix")){include_once('/usr/share/artica-postfix/framework/class.unix.inc');}
        if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
        $ListenAddress=$this->CICAP_ADDR();
        if($ListenAddress==null){$ListenAddress="127.0.0.1";}
        $NETWORK_ALL_INTERFACES=$GLOBALS["CLASS_UNIX"]->NETWORK_ALL_INTERFACES(true);
        if(!isset($NETWORK_ALL_INTERFACES[$ListenAddress])){$ListenAddress="127.0.0.1";}
        if(preg_match("#^127\.0\.0#",$ListenAddress)){return "127.0.0.1:1345";}
        return "$ListenAddress:1345";
    }







    private function C_ICAP_30(){
        if($this->enable_cicap<>1){return null;}
        if($this->enable_kavproxy==1){
            $this->enable_cicap=0;
            return null;
        }
        $users=new usersMenus();
        $this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;


        $kav_rules[]="# --------- icap_service C-ICAP (mode 3.0.x)";

        if($this->EnableSquidGuardInCiCAP==1){$kav_rules[]="icap_service\tservice_req reqmod_precache O icap://$this->IcapServerAddress:1345/srv_url_check";}
        if(!$users->KASPERSKY_WEB_APPLIANCE){
            if($this->EnableClamavInCiCap==1){$kav_rules[]="icap_service\tservice_avi respmod_precache 1 icap://$this->IcapServerAddress:1345/srv_clamav";}
        }
        if($this->enable_metascanner==1){$kav_rules[]="icap_service\tservice_khse respmod_precache 1 icap://$this->IcapServerAddress:1345/khse";}


        $kav_rules[]="";
        if($this->EnableClamavInCiCap==1){$kav_rules[]="icap_class\tclass_antivirus service_avi";}
        if($this->EnableSquidGuardInCiCAP==1){$kav_rules[]="icap_class\tclass_antivirus_req service_req";}
        if($this->EnableClamavInCiCap==1){$kav_rules[]="icap_access\tclass_antivirus allow all";}
        if($this->EnableSquidGuardInCiCAP==1){$kav_rules[]="icap_access\tclass_antivirus_req allow all";}

        if($this->enable_metascanner==1){
            $kav_rules[]="icap_class\tclass_khse service_khse";
            $kav_rules[]="icap_access\tclass_khse allow all";
        }

        $kav_rules[]="";
        return implode("\n",$kav_rules);
    }


    private function C_ICAP_31_icap_service(){


            if($this->enable_metascanner==1){
                return;}
            if($this->EnableClamavInCiCap==0){
                return;}
            if(!$this->C_ICAP_31_is_scanner_exists()){
                return;
            }


        return true;

    }


    private function C_ICAP_31_is_scanner_exists(){
        $modules_path=$this->LOCATE_C_ICAP_MODULES_DIR();
        $result=false;
        if(!is_dir($modules_path)){
            return false;
        }
        if(is_file("$modules_path/srv_clamav.so")){$result=true;}
        if(is_file("$modules_path/virus_scan.so")){$result=true;}
        if(!$result){
            if($this->ASROOT){
                $unix=new unix();
                $nohup=$unix->find_program("nohup");
                shell_exec("$nohup /usr/share/artica-postfix/bin/artica-make APP_C_ICAP >/dev/null 2>&1 &");
            }
        }

        return $result;
    }


    private function C_ICAP_31(){
        $users=new usersMenus();
        $class_url_check=FALSE;
        if($this->enable_cicap==0){
            return "# --------- icap_service enable_cicap=0 [".__LINE__."]\n";
        }
        $sock=new sockets();
        $DisableCicapDNBSL=$sock->GET_INFO("DisableCicapDNBSL");
        if(!is_numeric($DisableCicapDNBSL)){$DisableCicapDNBSL=1;}


        $kav_rules[]="# --------- icap_service C-ICAP mode 3.1.x";


        if($this->enable_squidguard==1){
            if($this->EnableSquidGuardInCiCAP==1){
                $this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;
                $class_url_check=true;
                $kav_rules[]="# --------- icap_service C-ICAP + SquidGuard";
                $kav_rules[]="icap_service\tservice_url_check reqmod_precache routing=on bypass=on icap://$this->IcapServerAddress:1345/url_check";
            }
        }


        if(!$class_url_check){
            if($DisableCicapDNBSL==0){
                $this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;
                $kav_rules[]="# --------- icap_service C-ICAP + DNSBL";
                $kav_rules[]="icap_service\tservice_url_check reqmod_precache routing=on bypass=on icap://$this->IcapServerAddress:1345/url_check";
            }
        }

        if($this->C_ICAP_31_icap_service()){
            $this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;
            $kav_rules[]="icap_service  service_antivir respmod_precache routing=on bypass=on icap://$this->IcapServerAddress:1345/srv_clamav";
        }



        if($this->enable_metascanner==1){
            if(is_file("/opt/kaspersky/khse/libexec/libms-icap.so")){
                $this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;
                $kav_rules[]="icap_service  khse respmod_precache routing=on bypass=on icap://$this->IcapServerAddress:1345/khse";
            }
        }
        return implode("\n",$kav_rules);
    }





    public function LOCATE_C_ICAP_MODULES_DIR(){
        $f[]="virus_scan.so";
        $f[]="sys_logger.so";
        $f[]="srv_echo.so";

        $possibleDirs[]="/usr/lib/c_icap";
        $possibleDirs[]="/var/lib/c_icap";

        foreach ($possibleDirs as $dir){
            reset($f);
            foreach ($f as $filename){
                if(is_file("$dir/$filename")){
                    return $dir;
                }
            }
        }


        return "/usr/lib/c_icap";
    }



}