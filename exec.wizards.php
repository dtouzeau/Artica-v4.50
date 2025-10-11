<?php
$GLOBALS["SQUID_PR"]=false;
$GLOBALS["PERC"]=0;
$GLOBALS["NOPROGRESS"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.nics.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
if(preg_match("#--squidpr#",implode(" ",$argv))){$GLOBALS["SQUID_PR"]=true;}
if(preg_match("#--unprogress#",implode(" ",$argv))){$GLOBALS["NOPROGRESS"]=true;}
if($argv[1]=="--calc"){calc($argv[2]);exit;}
if($argv[1]=="--create-waf"){create_waf($argv[2]);exit;}
if($argv[1]=="--create-cache"){create_cache($argv[2]);exit;}
if($argv[1]=="--create-dns-filter"){create_dns_filter($argv[2]);exit;}
if($argv[1]=="--create-ipsec"){create_ipsec($argv[2]);exit;}
if($argv[1]=="--create-certificate"){create_default_certificate($argv[2]);exit;}
if($argv[1]=="--create-webfiltering-page"){create_web_filtering_page();exit;}
if($argv[1]=="--create-gateway"){create_gateway();exit;}
if($argv[1]=="--create-simple-proxy"){create_simple_proxy();exit;}
if($argv[1]=="--uninstall-all"){uninstall_all();exit;}



function build_progress($text,$pourc){
    if($GLOBALS["NOPROGRESS"]){return false;}
    $GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/wizard.progress";
    if($GLOBALS["PERC"]>0){
        $array["POURC"]=$GLOBALS["PERC"];
        $array["TEXT"]="({$pourc}%) $text";

    }else{
        $array["POURC"]=$pourc;
        $array["TEXT"]="$text";
    }

    if($GLOBALS["VERBOSE"]){echo "{$pourc}% $text\n";}
    @file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
    @chmod($GLOBALS["PROGRESS_FILE"],0755);

    if($GLOBALS["SQUID_PR"]) {
        $cachefile = PROGRESS_DIR ."/squid.access.center.progress";
        $array["POURC"] = 11;
        $array["TEXT"] = "{$pourc}% $text";
        if ($GLOBALS["VERBOSE"]) {
            echo "{$pourc}% $text\n";
        }
        @file_put_contents($cachefile, serialize($array));
        @chmod($GLOBALS["PROGRESS_FILE"], 0755);
    }


}

function create_ipsec($hostname){
    build_progress("{installing}....",5);
    $unix=new unix();
    $ini=new Bs_IniHandler();
    $sock=new sockets();
    $php5=$unix->LOCATE_PHP5_BIN();
    $hostname_g=$unix->hostname_g();
    if(!file_exists("/usr/sbin/ipsec")){
        build_progress("{not_installed} {APP_STRONGSWAN} {failed}",110);
        return; 
    }
    build_progress("{installing} {APP_STRONGSWAN}",40);
    system("$php5 /usr/share/artica-postfix/exec.strongswan.enable.php");
    if(!file_exists("/etc/artica-postfix/settings/Daemons/ArticastrongSwanSettings")){
        $strongSwanInitFile="
        [GLOBAL]
        ENABLE_SERVER=1
        strongSwanCharonstart=1
        strongSwanCachecrls=0
        strongSwanCharondebug=4
        strongSwanStrictcrlpolicy=0
        strongSwanUniqueids=1
        StrongswanListenInterface=eth0
        StrongswanEnableDNSWINS=0
        strongSwanEnableDHCP=0";
        $sock->SaveConfigFile($strongSwanInitFile->toString(), "ArticastrongSwanSettings");
    }

    build_progress("{configuring} {APP_STRONGSWAN}",60);
    $tmpname="certificate_".time();
    $ip=$unix->InterfaceToIPv4("eth0");
    system("$php5 /usr/share/artica-postfix/exec.strongswan.php --build-cert {$tmpname} {$ip} 0");
    build_progress("{configuring} {APP_STRONGSWAN}",80);
    system("$php5 /usr/share/artica-postfix/exec.strongswan.php --reconfigure");
    build_progress("{configuring} {APP_STRONGSWAN} {success}",100);



}

function create_dns_filter($hostname){
    build_progress("{installing}....",5);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $hostname_g=$unix->hostname_g();


    $SERVICES["/etc/init.d/proxy-pac"]="/usr/sbin/artica-phpfpm-service -uninstall-proxypac";
    $SERVICES["/etc/init.d/ufdb"]="/usr/sbin/artica-phpfpm-service -uninstall-ufdb";
    $SERVICES["/etc/init.d/squid"]="/usr/sbin/artica-phpfpm-service -uninstall-proxy";

    if(is_file("/etc/init.d/squid")){
        squid_admin_mysql(0, "Removing proxy service and all associated service!",
        null, __FILE__, __LINE__);
    }

    foreach ($SERVICES as $initd=>$script){
        if(is_file($initd)){
            build_progress("{uninstalling}....$initd",10);
            shell_exec("$php5 /usr/share/artica-postfix/$script");
        }
    }


    if(!create_default_certificate()){
        build_progress("{installing} {APP_DNSFILTERD} {failed}",110);
        return;
    }

    build_progress("{installing} {APP_NGINX}",40);
    if(!is_file("/etc/init.d/nginx")) {
        system("/usr/sbin/artica-phpfpm-service -nginx-install");
    }

    build_progress("{configuring} {APP_NGINX}",50);
    if(!create_web_filtering_page()){return;}


    if(!is_file("/etc/init.d/dnsfilterd")){
        build_progress("{configuring} {APP_DNSFILTERD}",60);
        system("$php5 /usr/share/artica-postfix/exec.dnsfilterd.php --install");
    }

    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $q->QUERY_SQL("DELETE FROM webfilter_blks WHERE webfilter_id=0");

    $blks="14,5,7,42,45,46,57,64,92,105,109,111,140,143,149,167,169,181,183,184,187,192,195,200,205,209,226";
    $tb=explode(",",$blks);
    foreach ($tb as $cat) {
        $sql = "INSERT OR IGNORE INTO webfilter_blks (webfilter_id,category,modeblk) VALUES ('0','{$cat}','0')";
        $q->QUERY_SQL($sql);
    }
    $q->QUERY_SQL("INSERT OR IGNORE INTO webfilter_blks (webfilter_id,category,modeblk) VALUES ('0','89','1')");

    $DefaultIpRedirection=null;
    $ip=new IP();
    $unix=new unix();
    if(!$ip->isValid($hostname)){
        $DefaultIpRedirection=$unix->InterfaceToIPv4("eth0");
        if($DefaultIpRedirection=="0.0.0.0"){$DefaultIpRedirection=null;}
        if($DefaultIpRedirection==null){$DefaultIpRedirection=$unix->InterfaceToIPv4("eth1");}
        if($DefaultIpRedirection==null){$DefaultIpRedirection="127.0.0.1";}
    }else{
        $DefaultIpRedirection=$hostname;
    }

    $sock=new sockets();
    $sock->SET_INFO("DefaultIpRedirection",$DefaultIpRedirection);
    system("$php5 /usr/share/artica-postfix/exec.dnsfilterd.php --reload");

    $nohup=$unix->find_program("nohup");
    system("$nohup $php5 /usr/share/artica-postfix/exec.squid.blacklists.php --force >/dev/null 2>&1 &");


    build_progress("{configuring} {APP_DNSFILTERD} {success}",100);

}



function create_default_certificate($gbprc=0){
    if($gbprc>0){$GLOBALS["PERC"]=$gbprc;}
    $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
    $unix=new unix();
    $hostname=$unix->hostname_g();
    $php5=$unix->LOCATE_PHP5_BIN();

    $ligne=$q->mysqli_fetch_array("SELECT `csr`,`crt`,`UsePrivKeyCrt`,`SquidCert` `CommonName` FROM `sslcertificates` WHERE `CommonName`='$hostname'");
    if(!isset($ligne["CommonName"])){$ligne["CommonName"]=null;}

    if($ligne["CommonName"]==null) {
        $sql = "INSERT OR IGNORE INTO `sslcertificates` (CountryName,stateOrProvinceName,CertificateMaxDays,OrganizationName,OrganizationalUnit,emailAddress,localityName,levelenc,password,CommonName) VALUES ('UNITED STATES_US','New York','730','MyCompany Ltd','IT service','postmaster@localhost.localdomain','Brooklyn','2048','','$hostname')";
        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo "Creating certificate failed\n";
            return false;
        }

    }
    $ligne=$q->mysqli_fetch_array("SELECT `csr`,`crt`,`UsePrivKeyCrt`,`SquidCert` `CommonName` FROM `sslcertificates` WHERE `CommonName`='$hostname'");


    $field="crt";
    if($ligne["UsePrivKeyCrt"]==0){$field="SquidCert";}
    if(strlen($ligne["csr"])<20){
        $cmd=trim("$php5 /usr/share/artica-postfix/exec.openssl.php --csr \"$hostname\" --output");
        system($cmd);
    }
    if(!isset($ligne[$field])){$ligne[$field]="";}

    if(strlen($ligne[$field])<20){
        $cmd = trim("$php5 /usr/share/artica-postfix/exec.openssl.php --easyrsa $hostname --output");
        system($cmd);
    }

    return true;
}

function create_web_filtering_page(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $hostname_g=$unix->hostname_g();
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM nginx_services WHERE type=6 and enabled=1");
    $SERVICE_ID=intval($ligne["ID"]);

    if($SERVICE_ID>0){
        $q->QUERY_SQL("DELETE FROM nginx_services WHERE ID='$SERVICE_ID'");
        $q->QUERY_SQL("DELETE FROM stream_ports WHERE serviceid='$SERVICE_ID'");
        $q->QUERY_SQL("DELETE FROM service_parameters WHERE serviceid='$SERVICE_ID'");
    }


    $sql = "INSERT INTO nginx_services (`type`,servicename,hosts,enabled ) VALUES(6,'Web filtering error page','*',1)";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        build_progress("{configuring} {APP_NGINX} {failed}",110);
        echo $q->mysql_error;
        return;
    }

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM nginx_services WHERE type=6 and enabled=1 AND servicename='Web filtering error page'");



    $SERVICE_ID=intval($ligne["ID"]);
    if($SERVICE_ID==0) {
        build_progress("{configuring} {APP_NGINX} {failed}",110);
        echo "Unknown error..\n";
        return;
    }

    $sql = "INSERT INTO stream_ports (serviceid,interface,port,options,zmd5) 
    VALUES($SERVICE_ID,'',80,'YTo4OntzOjI6IklEIjtzOjE6IjAiO3M6MzoibWQ1IjtzOjA6IiI7czo5OiJzZXJ2aWNlaWQiO3M6MToiMSI7czo5OiJpbnRlcmZhY2UiO3M6MDoiIjtzOjQ6InBvcnQiO3M6MjoiODAiO3M6Mzoic3NsIjtzOjE6IjAiO3M6NDoic3BkeSI7czoxOiIwIjtzOjE0OiJwcm94eV9wcm90b2NvbCI7czoxOiIwIjt9','f033ab37c30201f73f142449d037028d');";
    $q->QUERY_SQL($sql);


    $sql = "INSERT INTO stream_ports (serviceid,interface,port,options,zmd5) 
    VALUES($SERVICE_ID,'',443,'YTo4OntzOjI6IklEIjtzOjE6IjAiO3M6MzoibWQ1IjtzOjA6IiI7czo5OiJzZXJ2aWNlaWQiO3M6MToiMSI7czo5OiJpbnRlcmZhY2UiO3M6MDoiIjtzOjQ6InBvcnQiO3M6MzoiNDQzIjtzOjM6InNzbCI7czoxOiIxIjtzOjQ6InNwZHkiO3M6MToiMCI7czoxNDoicHJveHlfcHJvdG9jb2wiO3M6MToiMCI7fQ==','13f3cf8c531952d72e5847c4183e6910');";
    $q->QUERY_SQL($sql);


    $sql="INSERT INTO service_parameters (`serviceid`,`zkey`,zvalue`) VALUES ($SERVICE_ID,'ssl_protocols','TLSv1 TLSv1.1 TLSv1.2');";
    $q->QUERY_SQL($sql);
    $sql="INSERT INTO service_parameters (`serviceid`,`zkey`,zvalue`) VALUES ($SERVICE_ID,'ssl_ciphers','ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:ECDHE-RSA-RC4-SHA:ECDHE-ECDSA-RC4-SHA:RC4-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!3DES:!MD5:!PSK');";
    $q->QUERY_SQL($sql);

    $sql="INSERT INTO service_parameters (`serviceid`,`zkey`,zvalue`) VALUES($SERVICE_ID,'ssl_prefer_server_ciphers','0');";
    $q->QUERY_SQL($sql);

    $sql="INSERT INTO service_parameters (`serviceid`,`zkey`,zvalue`) VALUES($SERVICE_ID,'ssl_buffer_size','16');";
    $q->QUERY_SQL($sql);

    $sql="INSERT INTO service_parameters (`serviceid`,`zkey`,zvalue`) VALUES($SERVICE_ID,'ssl_certificate','$hostname_g');";
    $q->QUERY_SQL($sql);


    system("$php5 /usr/share/artica-postfix/exec.nginx.single.php $SERVICE_ID");
    return true;
}


function create_cache(){

    build_progress("{installing}....",5);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();

    $SERVICES["/etc/init.d/dnsfilterd"]="exec.dnsfilterd.php --uninstall";
    $SERVICES["/etc/init.d/proxy-pac"]="exec.squid.autoconfig.php --uninstall";
    $SERVICES["/etc/init.d/ufdb"]="/usr/sbin/artica-phpfpm-service -install-ufdb";
    $SERVICES["/etc/init.d/nginx"]="/usr/sbin/artica-phpfpm-service -nginx-uninstall";

    foreach ($SERVICES as $initd=>$script){
        if(is_file($initd)){
            build_progress("{uninstalling}....$initd",10);
            shell_exec("$php5 /usr/share/artica-postfix/$script");
        }
    }

    if(is_file("/etc/init.d/squid")) {
        build_progress("{uninstalling}....",20);
        system("$php5 /usr/share/artica-postfix/exec.squid.global.access.php --disable-acls --percent-pr=20");
    }
    if(!is_file("/etc/init.d/squid")) {
        build_progress("{installing}....",30);
        system("/usr/sbin/artica-phpfpm-service -install-proxy");
    }

    build_progress("{installing}....",40);
    system("$php5 /usr/share/artica-postfix/exec.squid.global.access.php --install-cache --percent-pr=40");

    build_progress("{installing}....",50);
    system("$php5 /usr/share/artica-postfix/exec.squid.global.access.php --enable-cache --percent-pr=50");

    $q=new lib_sqlite("/home/artica/SQLITE/caches.db");
    $COUNT_ROWS=intval($q->COUNT_ROWS("squid_caches_center"));
    $unix=new unix();
    $array=$unix->DIRPART_INFO("/home");
    $AIV=round($array["AIV"]/1024)/2;


    if($COUNT_ROWS==0) {

        @mkdir("/home/CachesDisk/disk",0755,true);
        $sql = "INSERT INTO squid_caches_center (cachename,cpu,cache_dir,cache_type,cache_size,cache_dir_level1,cache_dir_level2,enabled,percentcache,usedcache,zOrder,min_size,max_size,RemoveSize) VALUES('CachesDisk',1,'/home/CachesDisk/disk','aufs','$AIV','16','256',1,0,0,1,0,3145728,0)";
        $q->QUERY_SQL($sql);

    }


    system("$php5 /usr/share/artica-postfix/exec.squid.verify.caches.php --percent-pr=60");


    build_progress("{installing}....{success}",100);


}

function calc(){

}

function uninstall_all():int{

    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();

    $SERVICES["/etc/init.d/unbound"]="/usr/sbin/artica-phpfpm-service -uninstall-unbound";
    $SERVICES["/etc/init.d/ufdb"]="/usr/sbin/artica-phpfpm-service -uninstall-ufdb";
    $SERVICES["/etc/init.d/squid"]="/usr/sbin/artica-phpfpm-service -uninstall-proxy";
    $SERVICES["/etc/init.d/dnsfilterd"]="exec.dnsfilterd.php --uninstall";
    $SERVICES["/etc/init.d/proxy-pac"]="exec.squid.autoconfig.php --uninstall";
    $SERVICES["/etc/init.d/nginx"]="/usr/sbin/artica-phpfpm-service -nginx-uninstall";
    $SERVICES["/etc/init.d/slapd"]="/usr/sbin/artica-phpfpm-service -uninstall-ldap";
    $SERVICES["/etc/init.d/munin"]="exec.munin.php --uninstall";
    $SERVICES["/etc/init.d/glances"]="exec.glances.php --uninstall";
    $SERVICES["/etc/init.d/vnstat"]="/usr/sbin/artica-phpfpm-service -uninstall-vnstat";
    $SERVICES["/etc/init.d/arpd"]="exec.arpd.php --uninstall";
    $SERVICES["/etc/init.d/frontail-syslog"]="exec.frontail.php --uninstall";
    $SERVICES["/etc/init.d/tailon"]="/usr/sbin/artica-phpfpm-service -uninstall-tailon";
    $SERVICES["/etc/init.d/artica-postgres"]="exec.initslapd.php --remove-postgres";
    $SERVICES["/etc/init.d/samba"]="exec.samba-service.php --uninstall";
    $SERVICES["/etc/init.d/go-shield-server"]="exec.go.shield.server.php --remove";
    $SERVICES["/etc/init.d/dnsdist"]="exec.dnsdist.php --uninstall";



    $ppr=10;
    foreach ($SERVICES as $initd=>$script){
        if(is_file($initd)){
            $ppr++;
            build_progress("{uninstalling}....$initd",$ppr);
            if (strpos($script,"artica-phpfpm-service")>0){
                shell_exec($script);
                continue;
            }
            shell_exec("$php5 /usr/share/artica-postfix/$script");
        }
    }

    return $ppr;

}

function create_simple_proxy(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $ppr=uninstall_all();
    $ppr++;
    build_progress("{installing} {APP_SQUID}...",$ppr);
    system("/usr/sbin/artica-phpfpm-service -install-proxy");
    $ppr++;
    build_progress("{installing} {APP_UFDBGUARD}",$ppr);
    if(!is_file("/etc/init.d/ufdb")) {system("$php5 /usr/share/artica-postfix/exec.ufdb.enable.php");}



}

function create_gateway(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $ppr=uninstall_all();
    $ppr++;
    build_progress("{installing}....",$ppr);
    shell_exec("$php5 ".ARTICA_ROOT."/exec.sysctl.php --restart");
    build_progress("{done}....",100);

}


function create_waf($hostname){
    build_progress("{installing}....",5);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();


    $SERVICES["/etc/init.d/dnsfilterd"]="exec.dnsfilterd.php --uninstall";

    foreach ($SERVICES as $initd=>$script){
        if(is_file($initd)){
            build_progress("{uninstalling}....$initd",10);
            shell_exec("$php5 /usr/share/artica-postfix/$script");
        }
    }


    build_progress("{installing} {APP_SQUID}",10);

    if(!create_default_certificate()){
        build_progress("{installing} Web Firewall {failed}",110);
        return;
    }

    if(!is_file("/etc/init.d/squid")) {
        system("/usr/sbin/artica-phpfpm-service -install-proxy");
    }
    build_progress("{installing} Web Firewall",20);
    system("$php5 /usr/share/artica-postfix/exec.squid.global.access.php --enable-acls");

    build_progress("{installing} {APP_UFDBGUARD}",30);
    if(!is_file("/etc/init.d/ufdb")) {
        system("$php5 /usr/share/artica-postfix/exec.ufdb.enable.php");
    }
    build_progress("{installing} {APP_NGINX}",40);
    if(!is_file("/etc/init.d/nginx")) {
        system("/usr/sbin/artica-phpfpm-service -nginx-install");
    }


    build_progress("{installing} {APP_PROXY_PAC}",50);
    system("$php5 /usr/share/artica-postfix/exec.squid.autoconfig.php --re-install $hostname");

    build_progress("{configuring} {APP_UFDBGUARD}",60);


    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $q->QUERY_SQL("DELETE FROM webfilter_blks WHERE webfilter_id=0");

    $blks="14,5,7,42,45,46,57,64,92,105,109,111,140,143,149,167,169,181,183,184,187,192,195,200,205,209,226";
    $tb=explode(",",$blks);
    foreach ($tb as $cat) {
        $sql = "INSERT OR IGNORE INTO webfilter_blks (webfilter_id,category,modeblk) VALUES ('0','{$cat}','0')";
        $q->QUERY_SQL($sql);
    }
    $q->QUERY_SQL("INSERT OR IGNORE INTO webfilter_blks (webfilter_id,category,modeblk) VALUES ('0','89','1')");




    build_progress("{configuring} {APP_NGINX}",70);
    if(!create_web_filtering_page()){return;}

    $sock=new sockets();
    $sock->SET_INFO("SquidGuardWebUseExternalUri",1);
    $sock->SET_INFO("SquidGuardWebExternalUri","http://$hostname");
    $sock->SET_INFO("SquidGuardWebExternalUri","$hostname");

    build_progress("{configuring} {APP_UFDBGUARD}",80);
    system("$php5 /usr/share/artica-postfix/exec.squidguard.php --build");

    $nohup=$unix->find_program("nohup");
    system("$nohup $php5 /usr/share/artica-postfix/exec.squid.blacklists.php --force >/dev/null 2>&1 &");

    build_progress("{done}",100);




     




}