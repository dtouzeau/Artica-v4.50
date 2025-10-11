<?php
include_once(dirname(__FILE__)."/class.mount.inc");



class squid_ssl{
    private $sslproxy_version;
    private $sslcrtd_program;
    private $acls;
    private $ok;
    private $EVENTS=array();
    private $SquidVersion=null;
    public $BuildSquidCertificateCRTD_LOGS=array();
    private $SquidUrgency=0;
    private $CountOfSSLBump=0;
    private $IS_SQUID4=false;
    private $isSquid5=false;
    private $sslStepBump="ssl_step2";
    public function __construct(){
        $this->sslcrtd_program=$this->sslcrtd_program_path();
        $ini=new Bs_IniHandler();
        $this->sslproxy_version=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sslproxy_version"));

        if(!isset($GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"])){
            $ini->loadString($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaSquidParameters"));
            if(isset($ini->_params)){$GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"]=$ini->_params;}
        }else{
            $ini->_params=$GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"];
        }

        $this->SquidVersion=$this->GET_SQUID_VERSION();
        if(preg_match("#^(5|6|7)\.#",$this->SquidVersion)){$this->isSquid5=true;}

    }
    public function build_ssl_path(){


    }

    Public function BuildSSlBumpRulesForArticaRest(){
        $this->acls=new squid_acls();
        $this->acls->Build_Acls();
        @file_put_contents("/etc/squid3/acls_center.conf", @implode("\n",$this->acls->acls_array));
        $ProxyDefaultUncryptSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyDefaultUncryptSSL"));
        $ssl_bump_rules=$this->ssl_bump_rules();
        $f[]=@implode("\n",$this->EVENTS);
        $CountOfSSLSplice=count($ssl_bump_rules);
        $Date=date("Y-m-d H:i:s");
        $f[]="# builded on $Date";
        $f[]="# Rules (spliced) added by admins....";
        $f[]="# $CountOfSSLSplice rules...";
        $f[]="# ProxyDefaultUncryptSSL: $ProxyDefaultUncryptSSL...";
        $f[]="# -------------- Personal rules -----------------";
        $f[]=@implode("\n",$ssl_bump_rules);

        $f[]="# DEFAULT_RULE:$ProxyDefaultUncryptSSL";
        if($ProxyDefaultUncryptSSL==0){
            $f[]="ssl_bump splice all";
            if($this->CountOfSSLBump==0){$f[]="ssl_bump stare all";}
        }else{
            if($this->CountOfSSLBump==0){$f[]="ssl_bump stare all";}
            $f[]="ssl_bump bump all";
        }
        $sslproxy_cert_error=$this->sslproxy_cert_error();
        if(count($sslproxy_cert_error)>0){
            $f[]="";
            $f[]=@implode("\n",$sslproxy_cert_error);
            $f[]="sslproxy_cert_error deny all";
        }else{
            $f[]="sslproxy_cert_error allow all";
        }
        $f[]="";
        file_put_contents("/etc/squid3/ssl.bump.conf",@implode("\n",$f));
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


    private function sslcrtd_program_path(){
        if($this->SquidUrgency==1){return null;}
        if(isset($GLOBALS["sslcrtd_program"])){return $GLOBALS["sslcrtd_program"];}
        if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
        $ssl_crtd = $unix->squid_locate_generic_bin("ssl_crtd");
        $this->BuildSquidCertificateCRTD_LOGS[]="# sslcrtd_program_path: ssl_crtd = '$ssl_crtd'";
        if($ssl_crtd==null){
            $ssl_crtd = $unix->squid_locate_generic_bin("security_file_certgen");
            $this->BuildSquidCertificateCRTD_LOGS[]="# sslcrtd_program_path: security_file_certgen = '$ssl_crtd'";
        }
        $GLOBALS["sslcrtd_program"]=$ssl_crtd;
        return  $GLOBALS["sslcrtd_program"];
    }


    public function build(){
        if(!is_file("/etc/artica-postfix/settings/Daemons/AllowSquidWhitelistsSSL")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("AllowSquidWhitelistsSSL", 1);}
        $SquidSSLUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSSLUrgency"));
        if($SquidSSLUrgency==1){
            $f[]="# SSL Emergency mode !!! [".__LINE__."]";
            @file_put_contents("/etc/squid3/ssl.conf", @implode("\n",$f)."\n");
            return;

        }

        $this->build_ssl_path();
        if($this->isSquid5){
            $f[]="# Squid 5.x branch";
        }

        $SSLPORT=array();
        $AllowSquidWhitelistsSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowSquidWhitelistsSSL"));


        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $sql="SELECT * FROM proxy_ports WHERE enabled=1 AND UseSSL=1";
        $results = $q->QUERY_SQL($sql);
        if(!$q->ok){

            if(function_exists("debug_backtrace")){
                $trace=debug_backtrace();
                foreach ($trace as $line){$file=basename($line["file"]);$function=$line["function"];$line=$line["line"];$EXPLAINZ[]="From $file $function() Line $line";}
            }
            squid_admin_mysql(1, "Configuration failed on SSL with MySQL Error [action=abort]", $q->mysql_error."\n",@implode("\n", $EXPLAINZ));
            return;
        }


        foreach ($results as $index=>$ligne){
            $ipaddr=$ligne["ipaddr"];
            $port=intval($ligne["port"]);
            $eth=$ligne["nic"];
            $ID=$ligne["ID"];
            $f[]="# SSL used for port ID $ID, $ipaddr:$port on $eth";
            $SSLPORT[]=$ID;
        }

        $sql="SELECT * FROM transparent_ports WHERE enabled=1";
        $results=$q->QUERY_SQL($sql);
        foreach ($results as $index=>$ligne){
            $ID=$ligne["ID"];
            if($ligne["sslcertificate"]<>null){
                $f[]="# SSL used for Transparent port ID $ID";
                $SSLPORT[]=$ID;
            }
        }

        $HaClusterClient                = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){
            $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
            if(intval($HaClusterGBConfig["HaClusterDecryptSSL"])==1){
                $f[]="# HaCluster use SSL";
                $SSLPORT[]="HacCluster";
            }
        }
        $SquidMikrotikEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMikrotikEnabled"));
        $f[] = "# Patch 2020 - 08 - 03 SquidMikrotikEnabled = $SquidMikrotikEnabled";
        if($SquidMikrotikEnabled==0) {
            if (count($SSLPORT) == 0) {
                $f[] = "# No port defined for use SSL";
                @file_put_contents("/etc/squid3/ssl.conf", @implode("\n", $f) . "\n");
                return;
            }
        }

        $unix=new unix();
        $SQUID_VERSION=$unix->squid_version();
        $this->acls=new squid_acls();
        $this->acls->Build_Acls();
        $sslproxy_foreign_intermediate_certs=false;

        if(!is_numeric($this->sslproxy_version)){$sslproxy_version=1;}
        if($this->sslproxy_version==0){$sslproxy_version=1;}
        $f[]="# SSL Proxy options  Proxy version:$SQUID_VERSION [".__LINE__."]";

        if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)#", $SQUID_VERSION,$re)){
            $MAJOR=intval($re[1]);
            $MINOR=intval($re[2]);
            $REV=intval($re[3]);
            $f[]="# SSL Proxy options  Proxy Major: $MAJOR, Minor: $MINOR revision: $REV [".__LINE__."]";

            if($MAJOR>3){
                $this->IS_SQUID4=true;}

            if($MAJOR>2){
                if($MINOR>4){
                    if($REV>19){
                        $sslproxy_foreign_intermediate_certs=true;
                    }
                }
            }

        }

        if($sslproxy_foreign_intermediate_certs){
            $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
            $count=$q->COUNT_ROWS("publiccerts");
            if($count<2){
                $squid_ssl=new squid_ssl();
                $squid_ssl->DEFAULT_CERTIFICATES();
            }
            $fZ=array();
            $results=$q->QUERY_SQL("SELECT `content` FROM publiccerts WHERE enabled=1");
            foreach($results as $index=>$ligne) {$fZ[]=$ligne["content"];}
            @file_put_contents("/etc/squid3/intermediate_ca.pem", @implode("\n", $fZ));
            $fZ=array();
            $f[]="sslproxy_foreign_intermediate_certs /etc/squid3/intermediate_ca.pem";
            $f[]="sslproxy_cafile /etc/squid3/intermediate_ca.pem";

        }
        $sslcrtd_program_dbsize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sslcrtd_program_dbsize"));
        if($sslcrtd_program_dbsize==0){$sslcrtd_program_dbsize=8;}


        echo "Starting......: ".date("H:i:s")." Initalize database\n";
        shell_exec("/usr/sbin/artica-phpfpm-service -sslcrtd");
        $sslcrtd_disable_cache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sslcrtd_disable_cache"));
        if(intval($sslcrtd_disable_cache)==1){
            $sslcrtd_program_cmdline="$this->sslcrtd_program";
        }
        else{
            $ssl_crtd_path=$unix->SQUID_SSL_CRTD_PATH();
            $sslcrtd_program_cmdline="$this->sslcrtd_program -s $ssl_crtd_path/ssl_db -M {$sslcrtd_program_dbsize}MB";
        }

        $SslCrtdChildrens=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SslCrtdChildrens"));
        $SslCrtdQueueSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SslCrtdQueueSize"));
        if($SslCrtdChildrens==0){$SslCrtdChildrens=32;}
        if($SslCrtdQueueSize==0){$SslCrtdQueueSize=$SslCrtdChildrens*2;}
        $f[]="sslcrtd_program $sslcrtd_program_cmdline";
        $f[]="sslcrtd_children $SslCrtdChildrens startup=5 idle=1 queue-size=$SslCrtdQueueSize";
        $annotate_transaction=null;
        if($this->isSquid5){
            $f[]="acl AnnotateSSLGBW2 annotate_transaction whitelistssl=yes";
            $annotate_transaction = " AnnotateSSLGBW2";
        }


        $f[]="#The AppStore application in IOS (iPhone, iPad, MacOS) uses SSL Certificate Pinning,";
        $f[]="#it means the application knows what certificate to expect when accessing AppStore.";
        $f[]="#When you enable SSL Bump of HTTPS connections Squid replaces the default certificate with a ‘mimicked’ one;";
        $f[]="#the application detects that and refuses to function.";
        $f[]="#";
        $f[]="acl FakeCert ssl::server_name .apple.com";
        $f[]="acl FakeCert ssl::server_name .icloud.com";
        $f[]="acl FakeCert ssl::server_name .mzstatic.com";

        $f[]="acl FakeCert ssl::server_name .bnpparisbas";
        $f[]="acl SSLInternalNets dst 10.0.0.0/8";
        $f[]="acl SSLInternalNets dst 172.16.0.0/12";
        $f[]="acl SSLInternalNets dst 192.168.0.0/16";


        $f[]="acl ssl_step1 at_step SslBump1";
        $f[]="acl ssl_step2 at_step SslBump2";
        $f[]="acl ssl_step3 at_step SslBump3";
        if(@file_exists("/etc/squid3/ssl_whitelist.conf")){
            $f[]="include /etc/squid3/ssl_whitelist.conf";
        }
        $f[]="ssl_bump peek ssl_step1";

        if($AllowSquidWhitelistsSSL==1){
            $dstdomain_file = "/etc/squid3/acls_whitelist.dstdomain.conf";
            $dstdom_regex   = "/etc/squid3/acls_whitelist.dstdom_regex.conf";
            $dst_file       ="/etc/squid3/acls_whitelist.dst.conf";
            if(!is_file($dst_file)){@touch($dst_file);}
            if(!is_file($dstdom_regex)){@touch($dstdom_regex);}
            if(!is_file($dstdomain_file)){@touch($dstdomain_file);}
            @chown($dst_file,"squid");
            @chown($dstdom_regex,"squid");
            @chown($dstdomain_file,"squid");
            $f[]="ssl_bump splice GlobalWhitelistDomains$annotate_transaction";
            $f[]="ssl_bump splice GlobalWhitelistDSTNet$annotate_transaction";
        }
        $f[]="ssl_bump splice SSLInternalNets$annotate_transaction";
        $f[]="ssl_bump splice FakeCert$annotate_transaction";


        if(!is_file("/etc/squid3/ssl.conf")){
            @file_put_contents("/etc/squid3/ssl.conf", @implode("\n",$f)."\nssl_bump splice all\n");
        }

        $SniGroups=$this->SniGroups();
        if(count($SniGroups)>0){
            $f[]="";
            $f[]=@implode("\n",$SniGroups);
        }
        $ssl_bump_rules=$this->ssl_bump_rules();
        $f[]=@implode("\n",$this->EVENTS);
        $CountOfSSLSplice=count($ssl_bump_rules);
        if(!$this->ok){return;}

        $f[]="";
        $f[]="# Rules (spliced) added by admins....";
        $f[]="# $CountOfSSLSplice rules...";
        $f[]="# -------------- Personal rules -----------------";
        $f[]=@implode("\n",$ssl_bump_rules);

        $ProxyDefaultUncryptSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyDefaultUncryptSSL"));
        if($ProxyDefaultUncryptSSL==0){
            $f[]="ssl_bump splice all";
            if($this->CountOfSSLBump==0){$f[]="ssl_bump stare all";}
        }else{
            if($this->CountOfSSLBump==0){$f[]="ssl_bump stare all";}
            $f[]="ssl_bump bump all";
        }

        $f[]="";

        if(!$this->IS_SQUID4){
            $f[]="tls_outgoing_options options=NO_SSLv3,NO_TICKET cipher=ALL:!SSLv2:!SSLv3:!ADH:!DSS:!MD5:!EXP:!DES:!PSK:!SRP:!RC4:!IDEA:!SEED:!aNULL:!eNULL";
        }


        $sslproxy_cert_error=$this->sslproxy_cert_error();
        if(count($sslproxy_cert_error)>0){
            $f[]="";
            $f[]=@implode("\n",$sslproxy_cert_error);
            $f[]="sslproxy_cert_error deny all";
        }else{
            $f[]="sslproxy_cert_error allow all";
        }

        $on_unsupported_protocol=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("on_unsupported_protocol"));

        if($on_unsupported_protocol==1) {
            $f[]="on_unsupported_protocol tunnel all";

        }


        @file_put_contents("/etc/squid3/ssl.conf", @implode("\n",$f)."\n");

    }




    private function SniGroups(){
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $f=array();
        $sql="SELECT webfilters_sqgroups.*
		FROM webfilters_sqgroups, ssl_rules, sslrules_sqacllinks
		WHERE sslrules_sqacllinks.aclid = ssl_rules.ID
		AND sslrules_sqacllinks.gpid = webfilters_sqgroups.ID
		AND webfilters_sqgroups.GroupType LIKE 'ssl_sni%'
		AND webfilters_sqgroups.enabled =1
		AND ssl_rules.enabled =1";
        $results=$q->QUERY_SQL($sql);
        if(!$q->ok){
            $f[]="# !!!! MySQL Error !!!!";
            return $f;
        }

        foreach($results as $index=>$ligne) {
            $GroupName=$ligne["GroupName"];
            $GroupType=$ligne["GroupType"];
            $ID=$ligne["ID"];
            $f[]="# SNI Group $GroupName/$GroupType";
            $f[]=$this->SniGroupsItems($ID,$GroupType);
        }



        return $f;

    }

    private function isaregex($pattern){
        $pattern=" $pattern";
        if(strpos($pattern, "*")>0){return true;}
        if(strpos($pattern, ".*")>0){return true;}
        if(strpos($pattern, "[")>0){return true;}
        if(strpos($pattern, "]")>0){return true;}
        if(strpos($pattern, "(")>0){return true;}
        if(strpos($pattern, ")")>0){return true;}
        if(strpos($pattern, "?")>0){return true;}
        return false;
    }

    private function SniGroupsItems($ID,$GroupType="ssl_sni"){
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $f=array();
        $ForceRegex=false;
        if($GroupType=="ssl_sni_regex"){$ForceRegex=true;}
        $f[]="# id:$ID Type: $GroupType";
        $results=$q->QUERY_SQL("SELECT * FROM webfilters_sqitems WHERE gpid=$ID AND enabled=1");
        $CountOf=0;
        foreach($results as $index=>$ligne) {

            if($ForceRegex){
                $f[]="acl SNIGroup$ID ssl::server_name_regex -i {$ligne["pattern"]}";
                $CountOf++;
                continue;
            }

            if($this->isaregex($ligne["pattern"])){
                $f[]="acl SNIGroup$ID ssl::server_name_regex -i {$ligne["pattern"]}";
                $CountOf++;
                continue;
            }
            $ligne["pattern"]=str_replace(".", "\." ,$ligne["pattern"]);
            $CountOf++;
            $f[]="acl SNIGroup$ID ssl::server_name_regex -i {$ligne["pattern"]}";

        }

        if($CountOf==0){
            $f[]="acl SNIGroup$ID ssl::server_name nothing.net";
        }
        return @implode("\n",$f);
    }


    private function sslproxy_cert_error(){
        $this->ok=true;
        $squid_acls_groups=new squid_acls_groups();
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $f=array();
        $Types[1]="deny";
        $Types[2]="allow";
        $isSquid5=$this->isSquid5;


        $sql="SELECT * FROM sslproxy_cert_error WHERE enabled=1 ORDER BY zOrder";
        $results=$q->QUERY_SQL($sql);
        if(!$q->ok){$this->ok=false;}
        foreach($results as $index=>$ligne) {
            $aclid=$ligne["ID"];
            $annotate_transaction="";
            $ruletype=$ligne["ruletype"];
            if(!isset($Types[$ruletype])) {
                  continue;
            }
            $groups=$squid_acls_groups->buildacls_bytype_items($aclid,0,"sslproxy_cert_error_sqacllinks");
            $FINAL=@implode(" ", $groups);
            $FINAL=str_replace("!!", "!", $FINAL);
            if(count($groups)==0){continue;}
            if($isSquid5) {
                $annotate_transaction = " AnnotateSSLR$aclid";
                $f[]="acl AnnotateSSLR$aclid annotate_transaction certerr=$aclid";
            }
            $f[]="#\tSSLPROXY_CERT_ID:$aclid";
            $f[]="sslproxy_cert_error $Types[$ruletype] $FINAL$annotate_transaction";

        }



        $sql="SELECT * FROM ssl_rules WHERE enabled=1 AND trust=1 ORDER BY zOrder";
        $results=$q->QUERY_SQL($sql);
        if(!$q->ok){$this->ok=false;}

        foreach($results as $index=>$ligne) {
            $aclid=$ligne["ID"];
            $groups=$squid_acls_groups->buildacls_bytype_items($aclid,0,"sslrules_sqacllinks");
            $FINAL=@implode(" ", $groups);
            $FINAL=str_replace("!!", "!", $FINAL);
            if(count($groups)==0){continue;}
            $f[]="sslproxy_cert_error allow $FINAL";
        }

        if(count($squid_acls_groups->ErrorsArray)>0){
            $f[]=@implode("\n", $squid_acls_groups->ErrorsArray);
        }

        return $f;
    }

    private function ssl_bump_rules(){
        $this->ok=true;
        $isSquid5=$this->isSquid5;
        $squid_acls_groups=new squid_acls_groups();
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $f=array();
        $sql="SELECT * FROM ssl_rules WHERE enabled=1 ORDER BY zOrder";
        $results=$q->QUERY_SQL($sql);
        if(!$q->ok){$this->ok=false;}

        $c=0;
        $Annotation_splice[0]="splicerule";
        $Annotation_splice[1]="bumprule";

        $ACLS[0]="splice";
        $ACLS[1]="bump";

        foreach($results as $index=>$ligne) {
            $aclname=trim($ligne["description"]);
            $zOrder=intval($ligne["zOrder"]);
            $crypt=intval($ligne["crypt"]);
            $aclid=$ligne["ID"];
            $annotate_transaction=null;
            $splicerule=$Annotation_splice[$crypt];
            $splice_action=$ACLS[$crypt];
            $groups=$squid_acls_groups->buildacls_bytype_items($aclid,0,"sslrules_sqacllinks");
            if(count($groups)==0){
                foreach ($squid_acls_groups->buildacls_bytype_logs as $line){
                    $this->EVENTS[]="# $line";
                }
                $this->EVENTS[]="# Error, rule $aclid, no groups.";
                continue;
            }
            $FINAL=@implode(" ", $groups);
            $FINAL=str_replace("!!", "!", $FINAL);
            $f[]="";
            $f[]="# id:$aclid";
            $f[]="# $aclname order:$zOrder";
            if($crypt==1){
                $this->CountOfSSLBump++;
            }

            if($isSquid5) {
                $annotate_transaction = " AnnotateSSLW$aclid";
                $f[]="acl AnnotateSSLW$aclid annotate_transaction $splicerule=$aclid";
            }

            $this->EVENTS[]="# IMPRIM_RULE:$aclid";

            $f[]="ssl_bump $splice_action $FINAL$annotate_transaction";
            $c++;
        }

        if(count($squid_acls_groups->ErrorsArray)>0){
            $f[]=@implode("\n", $squid_acls_groups->ErrorsArray);
        }



        return $f;
    }





    private function ssl_bump_bump(){
        $isSquid5=$this->isSquid5;


        $this->ok=true;
        $squid_acls_groups=new squid_acls_groups();
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $f=array();
        $sql="SELECT * FROM ssl_rules WHERE enabled=1 AND crypt=1 ORDER BY zOrder";
        $results=$q->QUERY_SQL($sql);
        if(!$q->ok){$this->ok=false;}


        foreach($results as $index=>$ligne) {
            $aclid=$ligne["ID"];
            $annotate_transaction=null;
            $groups=$squid_acls_groups->buildacls_bytype_items($aclid,0,"sslrules_sqacllinks");
            if(count($groups)==0){
                $f[]="# Rule $aclid, no groups";
                continue;
            }
            $FINAL=@implode(" ", $groups);
            $FINAL=str_replace("!!", "!", $FINAL);
            $this->EVENTS[]="# IMPRIM_RULE:$aclid";

            if($isSquid5) {
                $annotate_transaction = " AnnotateSSL$aclid";
                $f[]="acl AnnotateSSL$aclid annotate_transaction bumprule=$aclid";
            }
            $f[]="#id:$aclid";
            $f[]="ssl_bump bump $this->sslStepBump $FINAL$annotate_transaction";
        }
        if(count($squid_acls_groups->ErrorsArray)>0){
            $f[]=@implode("\n", $squid_acls_groups->ErrorsArray);
        }
        return $f;
    }

    private function get_objects($ruleid){

        $table="(SELECT sslrules_sqacllinks.gpid,sslrules_sqacllinks.negation,
		sslrules_sqacllinks.zOrder,sslrules_sqacllinks.zmd5 as mkey,
		webfilters_sqgroups.* FROM sslrules_sqacllinks,webfilters_sqgroups
		WHERE sslrules_sqacllinks.gpid=webfilters_sqgroups.ID AND sslrules_sqacllinks.aclid=$ruleid
		ORDER BY sslrules_sqacllinks.zOrder
		) as t";

    }


    public function DEFAULT_CERTIFICATES(){
        $f=unserialize(@file_get_contents("/usr/share/artica-postfix/bin/install/public-certificates.db"));
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $prefix="INSERT INTO publiccerts (zmd5,issuer,zDate,subject,content,enabled) VALUES ";

        while (list ($md5, $array) = each ($f) ){

            $issuer=mysql_escape_string2($array["Issuer"]);
            $zDate=$array["NotAfter"];
            $Subject=mysql_escape_string2($array["Subject"]);
            $content=mysql_escape_string2($array["CONTENT"]);
            $t[]="('$md5','$issuer','$zDate','$Subject','$content',1)";

            if(count($t)>10){
                $q->QUERY_SQL($prefix.@implode(",", $t));
                if(!$q->ok){return;}
                $t=array();
            }

        }
        if(count($t)>0){
            $q->QUERY_SQL($prefix.@implode(",", $t));
            if(!$q->ok){return;}
        }

    }
}