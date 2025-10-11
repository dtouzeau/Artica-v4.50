<?php

if(isset($_SESSION["TIMEZONES"])){if(function_exists("date_default_timezone_set")){@date_default_timezone_set($_SESSION["TIMEZONES"]);}}
if(isset($GLOBALS["TIMEZONES"])){if(function_exists("date_default_timezone_set")){@date_default_timezone_set($GLOBALS["TIMEZONES"]);}}
if(!isset($GLOBALS["AS_ROOT"])) {
    if (function_exists("posix_getuid")) {
        if (posix_getuid() == 0) {
            $GLOBALS["AS_ROOT"] = true;
        }
    }
}
if(!isset($GLOBALS["FULL_DEBUG"])){$GLOBALS["FULL_DEBUG"]=false;}
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.users.menus.inc');
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.mysql.inc');
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__)."/class.mysql.blackboxes.inc");
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__)."/class.mysql.catz.inc");
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.simple.image.inc');
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__)."/class.highcharts.inc");
include_once(dirname(__FILE__)."/class.tcpip.inc");
include_once(dirname(__FILE__)."/class.analyze-page.inc");
include_once(dirname(__FILE__)."/class.stats-appliance.inc");
include_once(dirname(__FILE__)."/class.mysql-dump.inc");
include_once(dirname(__FILE__)."/class.postgres.inc");
include_once(dirname(__FILE__)."/class.template-admin.inc");
ini_set("mysql.connect_timeout",60);

class mysql_squid_builder{
    public $finaldomain=null;
    public $ok=false;
    public $mysql_error;
    public $UseMysql=true;
    public $database="squidlogs";
    public $mysql_server="127.0.0.1";
    public $mysql_admin;
    public $mysql_password;
    public $mysql_port;
    public $MysqlFailed=false;
    public $mysqli_connection;
    public $EnableRemoteStatisticsAppliance=0;
    public $EnableRemoteSyslogStatsAppliance=0;
    private $squidEnableRemoteStatistics=0;
    private $sql;
    public $DisableArticaProxyStatistics=0;
    public $EnableSquidRemoteMySQL=0;
    public $ProxyUseArticaDB=0;
    public $UseStandardMysql=false;
    public $EnableSargGenerator=0;
    public $tasks_array=array();
    public $tasks_explain_array=array();
    public $tasks_remote_appliance=array();
    public $CACHE_AGES=array();
    public $CACHES_RULES_TYPES=array();
    public $tasks_processes=array();
    public $tasks_disabled=array();
    public $last_id;
    public $acl_GroupType=array();
    public $acl_GroupType_fast=array();
    public $acl_GroupType_explain=array();
    public $acl_GroupType_WPAD=array();
    public $acl_GroupType_DNSDIST=array();
    public $acl_GroupType_iptables=array();
    public $acl_GroupType_Firewall_in=array();
    public $acl_GroupType_Firewall_out=array();
    public $acl_GroupType_Firewall_port=array();
    public $acl_GroupType_DNSFW=array();
    public $acl_GroupType_SMTP=array();

    public $acl_GroupTypeIcon=array();
    public $CATEGORY_LICENSES=array();

    public $durations=array();
    public $acl_NTLM=array();
    public $acl_ARRAY_NO_ITEM=array();
    public $SquidActHasReverse=0;
    public $AVAILABLE_METHOD=array();
    public $trace="";
    public $acl_GroupTypeDynamic=array();
    public $PROXY_PAC_TYPES=array();
    public $PROXY_PAC_TYPES_EXPLAIN=array();
    public $SocketName="/var/run/mysqld/mysqld.sock";
    public $SocketPath="";
    public $DisableLocalStatisticsTasks=0;
    private $EnableKerbAuth=0;
    private $UseDynamicGroupsAcls=1;
    public $MYSQL_CMDLINES=null;
    public $MYSQL_DATA_DIR="/var/lib/mysql";
    public $mysql_affected_rows;
    public $report_types=array();
    public $MySQLConnectionType=0;
    private $BD_CONNECT_ERROR;
    public $mysql_errornum=0;

    private $PDO_DSN;

    function __construct($local=false){
        $LDAP_EXTERNAL_AUTH=intval(@file_get_contents('/etc/artica-postfix/settings/Daemons/SquidExternLDAPAUTH'));
        if(function_exists("getLocalTimezone")){
            @date_default_timezone_set(getLocalTimezone());
        }

        if(!class_exists("Bs_IniHandler")){include_once(dirname(__FILE__)."/class.ini.inc");}
        if(!class_exists("sockets")){include_once(dirname(__FILE__)."/class.sockets.inc");}



        if(!isset($GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"])){
            $ini=new Bs_IniHandler();
            $ArticaSquidParameters=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaSquidParameters");
            $ini->loadString($ArticaSquidParameters);
            if(isset($ini->_params)){$GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"]=$ini->_params;}
        }else{
            $ini=new Bs_IniHandler();
            $ini->_params=$GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"];
        }
        if(!isset($GLOBALS["DEBUG_SQL"])){$GLOBALS["DEBUG_SQL"]=false;}
        $EnableArticaMetaServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaMetaServer"));
        $EnablenDPI=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablenDPI"));

        if($EnableArticaMetaServer==1){
            $LDAP_EXTERNAL_AUTH=1;
        }
        $this->durations[0]="{unlimited}";
        $this->durations[5]="05 {minutes}";
        $this->durations[10]="10 {minutes}";
        $this->durations[15]="15 {minutes}";
        $this->durations[30]="30 {minutes}";
        $this->durations[60]="1 {hour}";
        $this->durations[120]="2 {hours}";
        $this->durations[240]="4 {hours}";
        $this->durations[480]="8 {hours}";
        $this->durations[720]="12 {hours}";
        $this->durations[960]="16 {hours}";
        $this->durations[1440]="1 {day}";
        $this->durations[2880]="2 {days}";
        $this->durations[5760]="4 {days}";
        $this->durations[10080]="1 {week}";
        $this->durations[20160]="2 {weeks}";
        $this->durations[43200]="1 {month}";


        $this->report_types[1]="{by_categories}";
        $this->report_types[2]="{by_websites}";

        $this->acl_NTLM["src"]="{src_addr}";
        $this->acl_NTLM["arp"]="{ComputerMacAddress}";
        $this->acl_NTLM["dstdomain"]="{dstdomain}";
        $this->acl_NTLM["dst"]="{dst_addr}";
        $this->acl_NTLM["port"]="{destination_port}";



        $this->acl_GroupType["all"]="{all}";
        $this->acl_GroupType["src"]="{src_addr}";
        $this->acl_GroupType["rgexsrc"]="{src_addr} (regex)";
        $this->acl_GroupType["rgexdst"]="{destination_address} (regex)";
        $this->acl_GroupType["srcdomain"]="{srcdomain}";
        $this->acl_GroupType["arp"]="{ComputerMacAddress}";
        $this->acl_GroupType["dstdomain"]="{dstdomain}";
        $this->acl_GroupType["dstdom_regex"]="{dstdomain_regex}";
        $this->acl_GroupType["url_regex_extensions"]="{url_regex_extensions}";
        $this->acl_GroupType["url_db"]="{acl_url_db}";
        $this->acl_GroupType["dst"]="{dst_addr}";
        $this->acl_GroupType["ssl_error"]="{ssl_error}";
        $this->acl_GroupType["maxconn"]="{acl_maxconn}";
        $this->acl_GroupType["max_user_ip"]="{acl_max_user_ip}";
        $this->acl_GroupType["proxy_auth"]="{members}";
        $this->acl_GroupType["proxy_auth_authenticated"]="{is_authenticated}";
        $this->acl_GroupType["proxy_auth_ads"]="{dynamic_activedirectory_group}";
        $this->acl_GroupType["proxy_auth_adou"]="{active_directory_ou}";
        $this->acl_GroupType["proxy_auth_statad"]="{static_activedirectory_group}";
        //$this->acl_GroupType["proxy_auth_extad"]="{external_activedirectory_group}";
        $this->acl_GroupType["proxy_auth_tagad"]="{acl_tag_adgroup}";
        $this->acl_GroupType["proxy_auth_multiad"]="{multiple_active_directory_groups}";
        $this->acl_GroupType["srcproxy"]="{srcproxy}";
        $this->acl_GroupType["the_shields"]="{KSRN}";
       // $this->acl_GroupType["reputation"]="{use_reput_service}";

        $this->acl_GroupTypeIcon["ssl_error"]=ico_ssl;
        $this->acl_GroupTypeIcon["reputation"]=ico_clouds;
        $this->acl_GroupTypeIcon["reputation"]=ico_clouds;
        $this->acl_GroupTypeIcon["netbiosname"]=ico_computer;
        $this->acl_GroupTypeIcon["ptr"]="fa-solid fa-arrow-left-to-line";
        $this->acl_GroupTypeIcon["opendns"]="fad fa-clouds";
        $this->acl_GroupTypeIcon["opendnsf"]="fad fa-clouds";
        $this->acl_GroupTypeIcon["all"]="fa-solid fa-star-of-life";
        $this->acl_GroupTypeIcon["src"]="fa fa-desktop";
        $this->acl_GroupTypeIcon["rgexsrc"]="fa fa-desktop";
        $this->acl_GroupTypeIcon["rgexdst"]="fas fa-random";
        $this->acl_GroupTypeIcon["srcdomain"]="fas fa-chart-network";
        $this->acl_GroupTypeIcon["arp"]="fas fa-desktop-alt";
        $this->acl_GroupTypeIcon["dstdomain"]="fab fa-soundcloud";
        $this->acl_GroupTypeIcon["dstdom_regex"]="fas fa-link";
        $this->acl_GroupTypeIcon["url_regex"]="fas fa-link";
        $this->acl_GroupTypeIcon["urlpath_regex"]="fas fa-link";
        $this->acl_GroupTypeIcon["url_regex_extensions"]="fas fa-link";
        $this->acl_GroupTypeIcon["url_db"]="fas fa-link";
        $this->acl_GroupTypeIcon["dst"]="fas fa-random";
        $this->acl_GroupTypeIcon["maxconn"]="fas fa-bolt";
        $this->acl_GroupTypeIcon["max_user_ip"]="fas fa-bolt";
        $this->acl_GroupTypeIcon["proxy_auth"]="fas fa-user";
        $this->acl_GroupTypeIcon["ext_user"]="fas fa-user";
        $this->acl_GroupTypeIcon["the_shields"]="fas fa-shield-virus";
        $this->acl_GroupTypeIcon["dnsquerytype"]="fas fa-bookmark";
        $this->acl_GroupTypeIcon["AclsGroup"]="fad fa-layer-group";

        $this->acl_GroupTypeIcon["proxy_auth_authenticated"]="fas fa-user";
        $this->acl_GroupTypeIcon["proxy_auth_ads"]=ico_microsoft;
        $this->acl_GroupTypeIcon["proxy_auth_adou"]=ico_microsoft;
        $this->acl_GroupTypeIcon["proxy_auth_statad"]=ico_microsoft;
        //$this->acl_GroupTypeIcon["proxy_auth_extad"]=ico_microsoft;
        $this->acl_GroupTypeIcon["proxy_auth_tagad"]="fa-pencil";
        $this->acl_GroupTypeIcon["proxy_auth_multiad"]=ico_microsoft;
        $this->acl_GroupTypeIcon["Smartphones"]="fa-mobile";
        $this->acl_GroupTypeIcon["browser"]=ico_eye;
        $this->acl_GroupTypeIcon["referer_regex"]=ico_eye;

        $this->acl_GroupTypeIcon["ssl_sni"]="fa fa-certificate";
        $this->acl_GroupTypeIcon["server_cert_fingerprint"]="fa fa-certificate";
        $this->acl_GroupTypeIcon["ssl_sni_regex"]="fa fa-certificate";
        $this->acl_GroupTypeIcon["webfilter"]="fas fa-shield";
        $this->acl_GroupTypeIcon["articablackreputation"]="fas fa-shield";





        $this->acl_GroupTypeIcon["time"]="far fa-clock";
        //$this->acl_GroupTypeIcon["quota_time"]="fad fa-user-clock";
        $this->acl_GroupTypeIcon["weekrange"]="fas fa-calendar-alt";
        $this->acl_GroupTypeIcon["srcproxy"]="fas fa-server";

        $this->acl_GroupTypeIcon["method"]="far fa-compress";
        $this->acl_GroupTypeIcon["FTP"]="fas fa-file-alt";
        $this->acl_GroupTypeIcon["dynamic_acls"]="fa-bars";
        $this->acl_GroupTypeIcon["req_mime_type"]="fas fa-file-video";
        $this->acl_GroupTypeIcon["rep_mime_type"]="fas fa-file-video";
        $this->acl_GroupTypeIcon["rep_header_filename"]="fas fa-file-video";
        $this->acl_GroupTypeIcon["AntiTrack"]="fas fa-thumbtack";
        $this->acl_GroupTypeIcon["localnet"]="far fa-network-wired";

        $this->acl_GroupTypeIcon["clt_conn_tag"]="fas fa-users";
        $this->acl_GroupTypeIcon["radius_auth"]="fas fa-users";
        $this->acl_GroupTypeIcon["ad_auth"]="fas fa-users";
        $this->acl_GroupTypeIcon["ldap_group"]="fas fa-users";
        $this->acl_GroupTypeIcon["ldap_auth"]="fas fa-users";
        $this->acl_GroupTypeIcon["port"]="fas fa-arrow-right";
        $this->acl_GroupTypeIcon["myportname"]="fab fa-arrow-right";
        $this->acl_GroupTypeIcon["categories"]="fa fa-tags";
        $this->acl_GroupTypeIcon["teamviewer"]="fab fa-cloud";
        $this->acl_GroupTypeIcon["facebook"]="fab fa-facebook";
        $this->acl_GroupTypeIcon["whatsapp"]="fab fa-weixin";
        $this->acl_GroupTypeIcon["skype"]="fab fa-skype";
        $this->acl_GroupTypeIcon["youtube"]="fab fa-youtube";
        $this->acl_GroupTypeIcon["office365"]=ico_microsoft;
        $this->acl_GroupTypeIcon["adfrom"]=ico_microsoft;
        $this->acl_GroupTypeIcon["adto"]=ico_microsoft;


        $this->acl_GroupTypeIcon["google_ssl"]="fab fa-google";
        $this->acl_GroupTypeIcon["google"]="fab fa-google";
        $this->acl_GroupTypeIcon["dropbox"]="fab fa-dropbox";
        $this->acl_GroupTypeIcon["geoipdest"]="fas fa-location";
        $this->acl_GroupTypeIcon["geoipsrc"]="fas fa-location";
        $this->acl_GroupTypeIcon["geoip"]="fas fa-location";
        $this->acl_GroupTypeIcon["doh"]="fas fa-globe";
        $this->acl_GroupTypeIcon["envto"]=ico_message;
        $this->acl_GroupTypeIcon["envfrom"]=ico_message;
        $this->acl_GroupTypeIcon["header"]=ico_list;
        $this->acl_GroupTypeIcon["attachs"]=ico_file_zip;
        $this->acl_GroupTypeIcon["senderad"]=ico_user;
        $this->acl_GroupTypeIcon["spf"]="fa-solid fa-shield-virus";
        $this->acl_GroupTypeIcon["dmarc"]="fa-solid fa-envelope-circle-check";
        $this->acl_GroupTypeIcon["spamc"]="fa-solid fa-ban-bug";
        $this->acl_GroupTypeIcon["accessrule"]=ico_list;


        $SquidStandardLDAPAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidStandardLDAPAuth"));
        if($SquidStandardLDAPAuth==1){
            $this->acl_GroupType["ldap_group"]="{dynamic_ldap_group} (local)";

        }


        if($LDAP_EXTERNAL_AUTH==1){
            $this->acl_GroupType["proxy_auth_ldap"]="{dynamic_ldap_group}";
        }


        $this->acl_GroupType_fast["all"]="{all}";
        $this->acl_GroupType_fast["src"]="{src_addr}";
        $this->acl_GroupType_fast["categories"]="{artica_categories}";
        $this->acl_GroupType_fast["dstdomain"]="{dstdomain}";
        $this->acl_GroupType_fast["dstdom_regex"]="{dstdom_regex}";
        $this->acl_GroupType_fast["url_regex_extensions"]="{url_regex_extensions}";
        $this->acl_GroupType_fast["url_regex"]="{url_regex_acl2}";
        $this->acl_GroupType_fast["urlpath_regex"]="{url_regex_acl3}";
        $this->acl_GroupType_fast["referer_regex"]="{referer_regex}";
        $this->acl_GroupType_fast["arp"]="{ComputerMacAddress}";
        $this->acl_GroupType_fast["time"]="{DateTime}";
        $this->acl_GroupType_fast["port"]="{destination_port}";
        $this->acl_GroupType_fast["myportname"]="{local_proxy_port}";
        $this->acl_GroupType_fast["FTP"]="FTP {protocol}";
        $this->acl_GroupType_fast["method"]="{connection_method}";
        $this->acl_GroupType_fast["browser"]="{browser}";
        $this->acl_GroupType_fast["Smartphones"]="{smartphones}";
        $this->acl_GroupType_fast["maxconn"]="{acl_maxconn}";
        $this->acl_GroupType_fast["max_user_ip"]="{acl_max_user_ip}";
        $this->acl_GroupType_fast["req_mime_type"]="{req_mime_type}";
        $this->acl_GroupType_fast["rep_mime_type"]="{rep_mime_type}";
        $this->acl_GroupType_fast["proxy_auth"]="{members}";
        $this->acl_GroupType_fast["proxy_auth_tagad"]="{acl_tag_adgroup}";
        $this->acl_GroupType_fast["proxy_auth_statad"]="{static_activedirectory_group}";
        $this->acl_GroupType_fast["server_cert_fingerprint"]="{server_cert_fingerprint}";

        $this->acl_GroupType["Smartphones"]="{smartphones}";
        $this->acl_GroupType["browser"]="{browser}";
        $this->acl_GroupType["ssl_sni"]="{acl_ssl_sni}";
        $this->acl_GroupType["ssl_sni_regex"]="{acl_ssl_sni} (regex)";
        $this->acl_GroupType["server_cert_fingerprint"]="{server_cert_fingerprint}";

        $this->acl_GroupType["time"]="{DateTime}";
        $this->acl_GroupType["weekrange"]="{weekranges}";
        $this->acl_GroupType["ext_user"]="{ext_user}";
        $this->acl_GroupType["method"]="{connection_method}";
        $this->acl_GroupType["FTP"]="FTP {protocol}";
        $this->acl_GroupType["dynamic_acls"]="{dynamic_acls}";
        $this->acl_GroupType["req_mime_type"]="{req_mime_type}";
        $this->acl_GroupType["rep_mime_type"]="{rep_mime_type}";
        $this->acl_GroupType["rep_header_filename"]="{rep_header_filename}";
        $this->acl_GroupType["url_regex"]="{url_regex_acl2}";
        $this->acl_GroupType["urlpath_regex"]="{url_regex_acl3}";
        $this->acl_GroupType["referer_regex"]="{referer_regex}";
        $this->acl_GroupType["radius_auth"]="{radius_auth}";
        $this->acl_GroupType["ad_auth"]="{basic_ad_auth}";
        $this->acl_GroupType["ldap_auth"]="{basic_ldap_auth}";
        $this->acl_GroupType["port"]="{destination_port}";
        $this->acl_GroupType["myportname"]="{local_proxy_port}";
        $this->acl_GroupType["clt_conn_tag"]="{statistics_virtual_group}";
        $this->acl_GroupType["categories"]="{artica_categories}";
        $this->acl_GroupType["teamviewer"]="{macro}: TeamViewer";
        $this->acl_GroupType["facebook"]="{macro}: FaceBook";
        $this->acl_GroupType["whatsapp"]="{macro}: whatsapp";
        $this->acl_GroupType["skype"]="{macro}: Skype";
        $this->acl_GroupType["youtube"]="{macro}: Youtube";
        $this->acl_GroupType["office365"]="{macro}: Office 365";
        //$this->acl_GroupType["quota_time"]="{quota_time}";
       // if($EnableQuotaSize==1){
        //    $this->acl_GroupType["quota_size"]="{quota_size}";
       // }
        $this->acl_GroupType["google"]="{macro}: Google {websites}";
        $this->acl_GroupType["google_ssl"]="{macro}: Google SSL {websites}";
        $this->acl_GroupType["AntiTrack"]="{macro}: {ads_and_trackers}";
        $this->acl_GroupType["dropbox"]="{macro}: DropBox networks";
        $this->acl_GroupType["localnet"]="{local_network}";
        $this->acl_GroupType["accessrule"]="{access_rule} (tag)";

        $this->acl_ARRAY_NO_ITEM["clt_conn_tag"]=true;
        $this->acl_ARRAY_NO_ITEM["FTP"]=true;
        $this->acl_ARRAY_NO_ITEM["proxy_auth_ads"]=true;
        $this->acl_ARRAY_NO_ITEM["proxy_auth_tagad"]=true;
        $this->acl_ARRAY_NO_ITEM["weekrange"]=true;
        $this->acl_ARRAY_NO_ITEM["articablackreputation"]=true;
        $this->acl_ARRAY_NO_ITEM["reputation"]=true;

        $this->acl_ARRAY_NO_ITEM["ldap_group"]=true;
        $this->acl_ARRAY_NO_ITEM["proxy_auth_authenticated"]=true;

        $this->acl_ARRAY_NO_ITEM["proxy_auth_adou"]=true;
        $this->acl_ARRAY_NO_ITEM["proxy_auth_statad"]=true;
        $this->acl_ARRAY_NO_ITEM["all"]=true;
        $this->acl_ARRAY_NO_ITEM["dynamic_acls"]=true;
        $this->acl_ARRAY_NO_ITEM["radius_auth"]=true;
        $this->acl_ARRAY_NO_ITEM["ad_auth"]=true;
        $this->acl_ARRAY_NO_ITEM["ldap_auth"]=true;
        $this->acl_ARRAY_NO_ITEM["AntiTrack"]=true;
        $this->acl_ARRAY_NO_ITEM["facebook"]=true;
        $this->acl_ARRAY_NO_ITEM["teamviewer"]=true;
        $this->acl_ARRAY_NO_ITEM["whatsapp"]=true;
        $this->acl_ARRAY_NO_ITEM["office365"]=true;
        $this->acl_ARRAY_NO_ITEM["skype"]=true;
        $this->acl_ARRAY_NO_ITEM["youtube"]=true;
        $this->acl_ARRAY_NO_ITEM["google"]=true;
        $this->acl_ARRAY_NO_ITEM["dropbox"]=true;
        $this->acl_ARRAY_NO_ITEM["google_ssl"]=true;
        $this->acl_ARRAY_NO_ITEM["proxy_auth_ldap"]=true;
        $this->acl_ARRAY_NO_ITEM["Smartphones"]=true;
        $this->acl_ARRAY_NO_ITEM["quota_size"]=true;
        //$this->acl_ARRAY_NO_ITEM["proxy_auth_extad"]=true;
        $this->acl_ARRAY_NO_ITEM["weekperiod"]=true;
        $this->acl_ARRAY_NO_ITEM["localnet"]=true;
        $this->acl_ARRAY_NO_ITEM["url_db"]=true;
        $this->acl_ARRAY_NO_ITEM["webfilter"]=true;
        $this->acl_ARRAY_NO_ITEM["the_shields"]=true;
        $this->acl_ARRAY_NO_ITEM["opendns"]=true;
        $this->acl_ARRAY_NO_ITEM["opendnsf"]=true;
        $this->acl_ARRAY_NO_ITEM["netbiosname"]=true;
        $this->acl_ARRAY_NO_ITEM["senderad"]=true;



        $this->acl_GroupType_WPAD["all"]="{all}";
        $this->acl_GroupType_WPAD["src"]="{ipsrc}";
        $this->acl_GroupType_WPAD["rgexsrc"]="{ipsrc} (regex)";
        $this->acl_GroupType_WPAD["rgexdst"]="{dst} (regex)";
        $this->acl_GroupType_WPAD["srcproxy"]="{srcproxy}";
        $this->acl_GroupType_WPAD["srcdomain"]="{srcdomain}";
        $this->acl_GroupType_WPAD["dstdomain"]="{dstdomain}";
        $this->acl_GroupType_WPAD["dst"]="{dst}";
        $this->acl_GroupType_WPAD["browser"]="{browser}";
        $this->acl_GroupType_WPAD["time"]="{DateTime}";
        $this->acl_GroupType_WPAD["port"]="{destination_port}";


        $this->acl_GroupType_DNSDIST["all"]="{all}";
        $this->acl_GroupType_DNSDIST["src"]="{ipsrc}";
        $this->acl_GroupType_DNSDIST["dstdomain"]="{dstdomain}";
        $this->acl_GroupType_DNSDIST["dstdom_regex"]="{dstdomain_regex}";
        $this->acl_GroupType_DNSDIST["netbiosname"]="{acl_netbiosname}";
        $this->acl_GroupType_DNSDIST["ptr"]="{ptr_dst}";
        $this->acl_GroupType_DNSDIST["dst"]="{dns_servers}";
        $this->acl_GroupType_DNSDIST["doh"]="{APP_DOH_BACKEND}";
        $this->acl_GroupType_DNSDIST["categories"]="{artica_categories}";
        $this->acl_GroupType_DNSDIST["webfilter"]="{web_filtering}";
        $this->acl_GroupType_DNSDIST["dnsquerytype"]="{dnsquerytype}";
        $this->acl_GroupType_DNSDIST["the_shields"]="{SRN}";
        $this->acl_GroupType_DNSDIST["geoipsrc"]="{geoipsrc}";
        $this->acl_GroupType_DNSDIST["opendns"]="OpenDNS";
        $this->acl_GroupType_DNSDIST["opendnsf"]="OpenDNS Family";
        $this->acl_GroupType_DNSDIST["reputation"]="{use_reput_service}";


        $this->acl_GroupType_DNSFW["all"]="{all}";
        $this->acl_GroupType_DNSFW["src"]="{ipsrc}";
        $this->acl_GroupType_DNSFW["dstdomain"]="{dstdomain}";
        $this->acl_GroupType_DNSFW["dstdom_regex"]="{dstdomain_regex}";
        $this->acl_GroupType_DNSFW["dst"]="{dst}";
        $this->acl_GroupType_DNSFW["ptr"]="{ptr_dst}";
        $this->acl_GroupType_DNSFW["dnsquerytype"]="{dnsquerytype}";
        $this->acl_GroupType_DNSFW["weekrange"]="{weekranges}";
        $this->acl_GroupType_DNSFW["geoipdest"]="{geoipdest}";
        $this->acl_GroupType_DNSFW["geoipsrc"]="{geoipsrc}";
        $this->acl_GroupType_DNSFW["the_shields"]="{KSRN}";
        $this->acl_GroupType_DNSFW["categories"]="{artica_categories}";
        $this->acl_GroupType_DNSFW["reputation"]="{use_reput_service}";


        $this->acl_GroupType_SMTP["all"]="{all}";
        $this->acl_GroupType_SMTP["src"]="{ipsrc}";
        $this->acl_GroupType_SMTP["envto"]="{recipient_address}";
        $this->acl_GroupType_SMTP["envfrom"]="{sender_address}";
        $this->acl_GroupType_SMTP["header"]="{header}";
        $this->acl_GroupType_SMTP["senderad"]="{sender} {activedirectory_user}";
        $this->acl_GroupType_SMTP["adfrom"]="{active_directory_group} {sender}";
        $this->acl_GroupType_SMTP["adto"]="{active_directory_group} {recipients}";
        $this->acl_GroupType_SMTP["attachs"]="{attachments}";
        $this->acl_GroupType_SMTP["url_regex"]="{url_regex_acl2}";
        $this->acl_GroupType_SMTP["articablackreputation"]="{articablackreputation}";
        $this->acl_GroupType_SMTP["articablackreputation"]="{articablackreputation}";
        $this->acl_GroupType_SMTP["dmarc"]="{check} DMARC";
        //$this->acl_GroupType_SMTP["dkimverify"]="{verify} DKIM";
        $this->acl_GroupType_SMTP["spf"]="{check} SPF";
        $this->acl_GroupType_SMTP["spamc"]="{check} spamc";
        $this->acl_GroupType_iptables["src"]="{ipsrc}";
        $this->acl_GroupType_iptables["dst"]="{dst}";
        $this->acl_GroupType_iptables["arp"]="{ComputerMacAddress}";
        $this->acl_GroupType_iptables["port"]="{destination_port}";
        $this->acl_GroupType_iptables["fwgeo"]="{countries}";
        $this->acl_GroupType_iptables["localnet"]="{local_network}";


        if($EnablenDPI==1){
            $this->acl_GroupType_iptables["ndpi"]="{APP_NDPI}";

        }




        $this->acl_GroupType_iptables["facebook"]="{macro}: facebook {networks}";
        $this->acl_GroupType_iptables["teamviewer"]="{macro}: TeamViewer {networks}";
        $this->acl_GroupType_iptables["whatsapp"]="{macro}: whatsapp {networks}";
        $this->acl_GroupType_iptables["office365"]="{macro}: Office365 {networks}";
        $this->acl_GroupType_iptables["skype"]="{macro}: Skype {networks}";
        $this->acl_GroupType_iptables["youtube"]="{macro}: YouTube {networks}";
        $this->acl_GroupType_iptables["dropbox"]="{macro}: DropBox {networks}";

        $this->acl_GroupType_Firewall_in["src"]="{addr}";
        $this->acl_GroupType_Firewall_in["arp"]="{ComputerMacAddress}";

        $XTGeoIPInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XTGeoIPInstalled"));

        if($XTGeoIPInstalled==1) {
            $this->acl_GroupType_Firewall_in["geoip"] = "{geoip_location}";
        }
        $this->acl_GroupType_Firewall_in["fwgeo"]="{countries}";
        $this->acl_GroupType_Firewall_in["localnet"]="{local_network}";


        $this->acl_GroupType_Firewall_out["dst"]="{dst}";
        $this->acl_GroupType_Firewall_out["fwgeo"]="{countries}";
        $this->acl_GroupType_Firewall_out["facebook"]="FaceBook - {macro}";
        $this->acl_GroupType_Firewall_out["teamviewer"]="TeamViewer - {macro}";
        $this->acl_GroupType_Firewall_out["whatsapp"]="whatsapp - {macro}";
        $this->acl_GroupType_Firewall_out["office365"]="office 365 - {macro}";
        $this->acl_GroupType_Firewall_out["skype"]="Skype - {macro}";
        $this->acl_GroupType_Firewall_out["youtube"]="YouTube - {macro}";
        $this->acl_GroupType_Firewall_out["google"]="Google - {macro}";
        $this->acl_GroupType_Firewall_out["localnet"]="{local_network}";


        $this->acl_GroupType_Firewall_port["port"]="{destination_port}";

        $this->acl_GroupTypeDynamic[0]="{mac}";
        $this->acl_GroupTypeDynamic[1]="{ipaddr}";
        $this->acl_GroupTypeDynamic[3]="{hostname}";
        $this->acl_GroupTypeDynamic[2]="{member}";
        $this->acl_GroupTypeDynamic[4]="{webserver}";


        $this->AVAILABLE_METHOD["GET"]=true;
        $this->AVAILABLE_METHOD["POST"]=true;
        $this->AVAILABLE_METHOD["HEAD"]=true;
        $this->AVAILABLE_METHOD["CONNECT"]=true;
        $this->AVAILABLE_METHOD["TRACE"]=true;
        $this->AVAILABLE_METHOD["DELETE"]=true;
        $this->AVAILABLE_METHOD["BPROFIND"]=true;
        $this->AVAILABLE_METHOD["PUT"]=true;
        $this->AVAILABLE_METHOD["REPORT"]=true;
        $this->AVAILABLE_METHOD["OPTIONS"]=true;
        $this->AVAILABLE_METHOD["TUNNEL"]=true;
        $this->AVAILABLE_METHOD["PROPFIND"]=true;
        $this->AVAILABLE_METHOD["PROPPATCH"]=true;
        $this->AVAILABLE_METHOD["MKCOL"]=true;
        $this->AVAILABLE_METHOD["COPY"]=true;
        $this->AVAILABLE_METHOD["MOVE"]=true;
        $this->AVAILABLE_METHOD["LOCK"]=true;
        $this->AVAILABLE_METHOD["UNLOCK"]=true;
        $this->AVAILABLE_METHOD["MKDIR"]=true;
        $this->AVAILABLE_METHOD["INDEX"]=true;
        $this->AVAILABLE_METHOD["RMDIR"]=true;
        $this->AVAILABLE_METHOD["LINK"]=true;
        $this->AVAILABLE_METHOD["UNLINK"]=true;
        $this->AVAILABLE_METHOD["PATCH"]=true;
        $this->AVAILABLE_METHOD["BCOPY"]=true;
        $this->AVAILABLE_METHOD["BDELETE"]=true;
        $this->AVAILABLE_METHOD["BMOVE"]=true;
        $this->AVAILABLE_METHOD["BPROPPATCH"]=true;
        $this->AVAILABLE_METHOD["MKCO"]=true;
        $this->AVAILABLE_METHOD["POLL"]=true;
        $this->AVAILABLE_METHOD["SEARCH"]=true;
        $this->AVAILABLE_METHOD["SUBSCRIBE"]=true;

        $this->PROXY_PAC_TYPES[null]="{select}";
        $this->PROXY_PAC_TYPES["shExpMatch"]="{shExpMatch2}";
        $this->PROXY_PAC_TYPES["shExpMatchRegex"]="{shExpMatchRegex}";
        $this->PROXY_PAC_TYPES["isInNetMyIP"]="{isInNetMyIP}";
        $this->PROXY_PAC_TYPES["isInNet"]="{isInNet2}";

        $this->PROXY_PAC_TYPES_EXPLAIN["shExpMatch"]="{shExpMatch2_explain}";
        $this->PROXY_PAC_TYPES_EXPLAIN["shExpMatchRegex"]="{shExpMatchRegex_explain}";
        $this->PROXY_PAC_TYPES_EXPLAIN["isInNetMyIP"]="{isInNetMyIP_explain}";
        $this->PROXY_PAC_TYPES_EXPLAIN["isInNet"]="{isInNet2_explain}";

        $this->CACHE_AGES[0]="{default}";
        $this->CACHE_AGES[30]="30 {minutes}";
        $this->CACHE_AGES[60]="1 {hour}";
        $this->CACHE_AGES[120]="2 {hours}";
        $this->CACHE_AGES[360]="6 {hours}";
        $this->CACHE_AGES[720]="12 {hours}";
        $this->CACHE_AGES[1440]="1 {day}";
        $this->CACHE_AGES[2880]="2 {days}";
        $this->CACHE_AGES[4320]="3 {days}";
        $this->CACHE_AGES[10080]="1 {week}";
        $this->CACHE_AGES[20160]="2 {weeks}";
        $this->CACHE_AGES[43200]="1 {month}";

        $this->CACHE_AGES[129600]="3 {months}";
        $this->CACHE_AGES[259200]="6 {months}";
        $this->CACHE_AGES[525600]="1 {year}";


        $this->CACHES_RULES_TYPES[1]="{domains}";
        $this->CACHES_RULES_TYPES[2]="{extensions}";
        $this->CACHES_RULES_TYPES[3]="{shExpMatchRegex}";

        $this->acl_GroupType_explain["ssl_error"]="{acl_ssl_error}";
        $this->acl_GroupType_explain["reputation"]="{acl_reputation}";
        $this->acl_GroupType_explain["netbiosname"]="{acl_netbiosname}";
        $this->acl_GroupType_explain["ptr"]="{ptr_dst_explain}";
        $this->acl_GroupType_explain["src"]="{acl_src_text}";
        $this->acl_GroupType_explain["rgexsrc"]="{acl_rgexsrc_text}";
        $this->acl_GroupType_explain["rgexdst"]="{acl_rgexdst_text}";
        $this->acl_GroupType_explain["dnsquerytype"]="{dnsquerytype_text}";
        $this->acl_GroupType_explain["arp"]="{ComputerMacAddress}";
        $this->acl_GroupType_explain["dstdomain"]="{proxy_acls_dstdomain_explain}<br>{squid_ask_domain}";
        $this->acl_GroupType_explain["port"]="{acl_squid_remote_ports_explain}";
        $this->acl_GroupType_explain["dst"]="{acl_squid_dst_explain}";
        $this->acl_GroupType_explain["url_regex"]="{acl_squid_url_regex_explain}";
        $this->acl_GroupType_explain["referer_regex"]="{acl_squid_referer_regex_explain}";
        $this->acl_GroupType_explain["urlpath_regex"]="{acl_squid_url_regex_explain}";
        $this->acl_GroupType_explain["rep_header_filename"]="{rep_header_filename_explain}";
        $this->acl_GroupType_explain["all"]="{acl_all_explain}";
        $this->acl_GroupType_explain["srcdomain"]="{acl_srcdomain_explain}";
        $this->acl_GroupType_explain["maxconn"]="{acl_maxconn_explain}";
        $this->acl_GroupType_explain["proxy_auth_ads"]="{proxy_auth_ads_explain}";
        $this->acl_GroupType_explain["dstdom_regex"]="{dstdom_regex_explain}";
        $this->acl_GroupType_explain["categories"]="{categories_acls_explain}";
        $this->acl_GroupType_explain["proxy_auth_statad"]="{proxy_auth_statad_explain}";
        $this->acl_GroupType_explain["browser"]="{proxy_acls_browser_explain}";
        $this->acl_GroupType_explain["proxy_auth_adou"]="{group_explain_proxy_acls_type_5}";
        $this->acl_GroupType_explain["srcproxy"]="{srcproxy_txt}";
        $this->acl_GroupType_explain["proxy_auth_authenticated"]="{is_authenticated_explain}";
        $this->acl_GroupType_explain["url_db"]="{acl_url_db_explain}";
        $this->acl_GroupType_explain["the_shields"]="{KSRN_EXPLAIN}";
        $this->acl_GroupType_explain["doh"]="{acls_doh_uri_explain}";
        $this->acl_GroupType_explain["webfilter"]="{acls_webfilter_explain}";
        $this->acl_GroupType_explain["opendns"]="{opendns_about}";
        $this->acl_GroupType_explain["opendnsf"]="{opendns_about}";
        $this->acl_GroupType_explain["accessrule"]="{accessrule_simple_about}";

        $this->acl_GroupType_explain["senderad"]="{activedirectory_checking} {sender}";
        $this->acl_GroupType_explain["envto"]="{acl_envto_explain}";
        $this->acl_GroupType_explain["envfrom"]="{acl_envfrom_explain}";
        $this->acl_GroupType_explain["header"]="{acl_smtp_header_explain}";
        $this->acl_GroupType_explain["adfrom"]="{proxy_auth_ads_explain}";
        $this->acl_GroupType_explain["adto"]="{proxy_auth_ads_explain}";
        $this->acl_GroupType_explain["attachs"]="{alcs_attachs_explains}";
        $this->acl_GroupType_explain["articablackreputation"]="{articablackreputation_explain}";



        $this->CATEGORY_LICENSES["apple"]=true;
        $this->CATEGORY_LICENSES["microsoft"]=true;
        $this->CATEGORY_LICENSES["youtube"]=true;
        $this->CATEGORY_LICENSES["facebook"]=true;
        $this->CATEGORY_LICENSES["finance/banking"]=true;
        $this->CATEGORY_LICENSES["finance/insurance"]=true;
        $this->CATEGORY_LICENSES["finance/moneylending"]=true;
        $this->CATEGORY_LICENSES["finance/other"]=true;
        $this->CATEGORY_LICENSES["finance/realestate"]=true;
        $this->CATEGORY_LICENSES["financial"]=true;
        $this->CATEGORY_LICENSES["electricalapps"]=true;
        $this->CATEGORY_LICENSES["electronichouse"]=true;
        $this->CATEGORY_LICENSES["green"]=true;
        $this->CATEGORY_LICENSES["hobby/arts"]=true;
        $this->CATEGORY_LICENSES["hobby/cooking"]=true;
        $this->CATEGORY_LICENSES["hobby/fishing"]=true;
        $this->CATEGORY_LICENSES["hobby/other"]=true;
        $this->CATEGORY_LICENSES["hobby/pets"]=true;
        $this->CATEGORY_LICENSES["horses"]=true;
        $this->CATEGORY_LICENSES["housing/accessories"]=true;
        $this->CATEGORY_LICENSES["housing/builders"]=true;
        $this->CATEGORY_LICENSES["housing/doityourself"]=true;
        $this->CATEGORY_LICENSES["isp"]=true;
        $this->CATEGORY_LICENSES["justice"]=true;
        $this->CATEGORY_LICENSES["luxury"]=true;
        $this->CATEGORY_LICENSES["mailing"]=true;
        $this->CATEGORY_LICENSES["maps"]=true;
        $this->CATEGORY_LICENSES["industry"]=true;
        $this->CATEGORY_LICENSES["health"]=true;
        $this->CATEGORY_LICENSES["amazonaws"]=true;
        $this->CATEGORY_LICENSES["alcohol"]=true;
        $this->CATEGORY_LICENSES["akamai"]=true;
        $this->CATEGORY_LICENSES["animals"]=true;
        $this->CATEGORY_LICENSES["suspicious"]=true;
        $this->CATEGORY_LICENSES["webapps"]=true;
        $this->CATEGORY_LICENSES["automobile/bikes"]=true;
        $this->CATEGORY_LICENSES["automobile/cars"]=true;
        $this->CATEGORY_LICENSES["automobile/boats"]=true;
        $this->CATEGORY_LICENSES["automobile/planes"]=true;
        $this->CATEGORY_LICENSES["bicycle"]=true;
        $this->CATEGORY_LICENSES["citrix"]=true;
        $this->CATEGORY_LICENSES["browsersplugins"]=true;
        $this->CATEGORY_LICENSES["clothing"]=true;
        $this->CATEGORY_LICENSES["dictionaries"]=true;
        $this->CATEGORY_LICENSES["google"]=true;
        $this->CATEGORY_LICENSES["automobile/carpool"]=true;
        $this->CATEGORY_LICENSES["hospitals"]=true;
        $this->CATEGORY_LICENSES["tattooing"]=true;
        $this->CATEGORY_LICENSES["yahoo"]=true;
        $this->fill_task_array();
        $this->fill_tasks_disabled();


    }

    public function time_to_date($xtime,$time=false){
        if(!class_exists("templates")){return "";}
        $tpl=new templates();
        $dateT=date("{l} {F} d",$xtime);
        if($time){$dateT=date("{l} {F} d H:i:s",$xtime);}
        if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$xtime);if($time){$dateT=date("{l} d {F} H:i:s",$xtime);}}
        return $tpl->_ENGINE_parse_body($dateT);

    }

    public function mysql_error_html($asP=true){
        $trace=@debug_backtrace();
        if(isset($trace[1])){
            $called="in ". basename($trace[1]["file"])." function {$trace[1]["function"]}() line {$trace[1]["line"]}";
        }
        if($asP){return "<p class=text-error>$this->mysql_error<br>$this->sql<br><i>$called</i></p>";}
        return "<div class=\"alert alert-danger\">$this->mysql_error<br>$this->sql<br><i>$called</i></div>";

    }

    public function dnsdist_acls_errors(){
        $errorgpty=array();
        if(!$GLOBALS["CLASS_SOCKETS"]->DNSDIST_WEBFILTER_ENABLED()){
            $errorgpty["webfilter"]="{dnsdist_server_not_enabled}";
            $errorgpty["the_shields"]="{dnsdist_server_not_enabled}";
            $errorgpty["categories"]="{dnsdist_server_not_enabled}";
            $errorgpty["geoipsrc"]="{dnsdist_server_not_enabled}";
        }

        if($GLOBALS["CLASS_SOCKETS"]->EnableUfdbGuard()==0){
            if(isset($errorgpty["webfilter"])){
                $errorgpty["webfilter"]="{$errorgpty["webfilter"]}<br>{dnsdist_webfilter_not_enabled}";
            }else{
                $errorgpty["webfilter"]="{dnsdist_webfilter_not_enabled}";
            }
        }


        $KSRNEnable=$GLOBALS["CLASS_SOCKETS"]->TheShieldEnabled();
        if(!$KSRNEnable){
            if(isset($errorgpty["the_shields"])){
                $errorgpty["the_shields"]="{$errorgpty["the_shields"]}<br>{dnsdist_theshields_not_enabled}";
            }else{
                $errorgpty["the_shields"]="{dnsdist_theshields_not_enabled}";
            }

        }


        $EnableGeoipUpdate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"));

        if( $EnableGeoipUpdate==0){
            if(isset($errorgpty["geoipsrc"])) {
                $errorgpty["geoipsrc"] = "{$errorgpty["geoipsrc"]}<br>{APP_GEOIPUPDATE_NOT_INSTALLED}";
            }else{
                $errorgpty["geoipsrc"]="{APP_GEOIPUPDATE_NOT_INSTALLED}";
            }
        }
        return $errorgpty;

    }



    public function mysql_error_jsdiv($id){
        $html=$this->mysql_error_html();
        $html=str_replace("'", "\'", $html);
        $html=str_replace("\n", "<br>", $html);
        header("content-type: application/x-javascript");
        echo "document.getElementById('$id').innerHTML='$html';";

    }

    function cluster_table($tablename){
        $database="squidlogs";
        if(!$this->TABLE_EXISTS($tablename,$database)){return;}
        $unix=new unix();
        $mysqldump=$unix->find_program("mysqldump");
        $gzip=$unix->find_program("gzip");
        $MYSQL_CMDLINES=$this->MYSQL_CMDLINES;
        $Rows=$this->COUNT_ROWS($tablename, $database);
        @mkdir("/usr/share/artica-postfix/ressources/logs/clusters",0755,true);
        $targetgz="/usr/share/artica-postfix/ressources/logs/clusters/$database.$tablename.gz";
        $cmdline="$mysqldump --skip-comments $MYSQL_CMDLINES $database $tablename | $gzip >$targetgz";
        @chmod(0755,$targetgz);
        echo "$cmdline\n";
        shell_exec("$cmdline");
        $MAIN["MD5"]=md5_file($targetgz);
        $MAIN["ROWS"]=$Rows;
        @file_put_contents("/usr/share/artica-postfix/ressources/logs/clusters/$database.$tablename.array", serialize($MAIN));
    }

    public function GroupTypeToString($GroupTypeName){
        $GroupType=$this->acl_GroupType;
        $GroupType["src"]="{addr}";
        $GroupType["arp"]="{ComputerMacAddress}";
        $GroupType["dstdomain"]="{dstdomain}";
        $GroupType["proxy_auth"]="{members}";
        $GroupType["port"]="{remote_ports}";
        $GroupType["maxconn"]="{max_connections}";
        $GroupType["ndpi"]="Deep packet inspection";
        $GroupType["the_shields"]="The Shields";
        $GroupType["AclsGroup"]="{group_of_objects}";
        return $GroupType[$GroupTypeName];
    }

    public function GRANT_PRIVS($hostname,$username,$password){
        $this->BD_CONNECT();
        $ok=@mysqli_select_db($this->mysqli_connection,"mysql");
        if (!$ok){
            if($GLOBALS["VERBOSE"]){echo "mysql_select_db -> ERROR\n";}
            $errnum=@mysqli_errno($this->mysqli_connection);
            $des=@mysqli_error($this->mysqli_connection);
            writelogs("$this->SocketPath:$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            $this->mysql_errornum=$errnum;
            $this->mysql_error="Error Number ($errnum) ($des)";
            $this->ok=false;
            return false;
        }


        $sql="SELECT User FROM user WHERE Host='$hostname' AND User='$username'";
        $ligne=@mysqli_fetch_array(@mysqli_query($this->mysqli_connection,$sql));
        if(trim($ligne["User"])==null){
            if(!$this->EXECUTE_SQL("CREATE USER '$username'@'$hostname' IDENTIFIED BY '$password';")){
                return false;
            }


            if(!$this->EXECUTE_SQL("GRANT ALL PRIVILEGES ON * . * TO '$username'@'$hostname' IDENTIFIED BY '$password' WITH GRANT OPTION MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0")){
                return false;
            }
            $this->EXECUTE_SQL("FLUSH PRIVILEGES;");
            return true;
        }

        if(!$this->EXECUTE_SQL("GRANT ALL PRIVILEGES ON * . * TO '$username'@'$hostname' IDENTIFIED BY '$password' WITH GRANT OPTION MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0")){return false;}
        $this->EXECUTE_SQL("FLUSH PRIVILEGES;");
        return true;
    }







    private function VERBOSE($text,$line=0){
        if(!function_exists("VERBOSE")){return null;}
        VERBOSE($text,$line);
        return true;
    }

    private function fill_tasks_disabled(){
        $users=new usersMenus();
        $DisableArticaProxyStatistics=$this->DisableArticaProxyStatistics;
        if($this->EnableRemoteSyslogStatsAppliance==1){$this->DisableLocalStatisticsTasks=1;}
        if($this->DisableLocalStatisticsTasks==1){$DisableArticaProxyStatistics=1;}
        $sock=new sockets();
        $DisableCategoriesDatabasesUpdates=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableCategoriesDatabasesUpdates"));
        $SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
        if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
        $SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));


        $this->tasks_disabled[7]=true;
        $this->tasks_disabled[8]=true;
        $this->tasks_disabled[14]=true;
        $this->tasks_disabled[37]=true;
        $this->tasks_disabled[38]=true;
        $this->tasks_disabled[41]=true;
        $this->tasks_disabled[42]=true;
        $this->tasks_disabled[47]=true;
        $this->tasks_disabled[53]=true;
        $this->tasks_disabled[21]=true;

        if($GLOBALS["VERBOSE"]){
            if(isset($this->acl_GroupType["categories"])){
                $this->VERBOSE("acl_GroupType[categories] = TRUE",__LINE__);
            }
        }

        if($SquidPerformance>1){$this->DisableArticaProxyStatistics=1;}

        if($SquidPerformance>2){
            $this->tasks_disabled[41]=true;
            $this->tasks_disabled[37]=true;
            $this->tasks_disabled[46]=true;
        }

        if($SquidPerformance>1){
            $this->tasks_disabled[17]=true;
        }

        $MEMORY=$users->MEM_TOTAL_INSTALLEE;

        if($MEMORY<624288){
            $users->PROXYTINY_APPLIANCE=true;
            $this->DisableArticaProxyStatistics=1;
            $DisableArticaProxyStatistics=1;
            $this->tasks_disabled[31]=true;
            $this->tasks_disabled[38]=true;
            $this->tasks_disabled[52]=true;
            $this->tasks_disabled[51]=true;
            $this->tasks_disabled[53]=true;
        }

        if($DisableCategoriesDatabasesUpdates==1){
            $this->tasks_disabled[2]=true;
        }


        if($SQUIDEnable==0){
            $users->PROXYTINY_APPLIANCE=true;
            $this->DisableArticaProxyStatistics=1;
            $DisableArticaProxyStatistics=1;
            $this->EnableSargGenerator=0;
            $this->tasks_disabled[31]=true;
            $this->tasks_disabled[38]=true;
            $this->tasks_disabled[52]=true;
            $this->tasks_disabled[51]=true;
            $this->tasks_disabled[53]=true;
        }



        if($users->PROXYTINY_APPLIANCE){
            $this->tasks_disabled[8]=true;
            $this->tasks_disabled[31]=true;
            $this->tasks_disabled[42]=true;
            $this->tasks_disabled[44]=true;
            $this->tasks_disabled[47]=true;
            $this->tasks_disabled[49]=true;
            $this->tasks_disabled[50]=true;
            $this->tasks_disabled[53]=true;
            $this->DisableArticaProxyStatistics=1;
        }

        if($this->SquidActHasReverse==1){
            $this->tasks_disabled[2]=true;
            $this->tasks_disabled[3]=true;
            $this->tasks_disabled[8]=true;
            $this->tasks_disabled[18]=true;
            $this->tasks_disabled[42]=true;
            $this->tasks_disabled[29]=true;
            $this->tasks_disabled[23]=true;
            $this->tasks_disabled[49]=true;
            $this->tasks_disabled[50]=true;

        }

        if($this->DisableArticaProxyStatistics==1){
            $this->tasks_disabled[38]=true;
            $this->tasks_disabled[37]=true;
            $this->tasks_disabled[49]=true;
            $this->tasks_disabled[50]=true;
            $this->tasks_disabled[53]=true;
        }


        if($DisableArticaProxyStatistics==1){
            $this->tasks_disabled[15]=true;
            $this->tasks_disabled[16]=true;
            $this->tasks_disabled[9]=true;
            $this->tasks_disabled[10]=true;
            $this->tasks_disabled[11]=true;
            $this->tasks_disabled[6]=true;
            $this->tasks_disabled[7]=true;
            $this->tasks_disabled[2]=true;
            $this->tasks_disabled[23]=true;
            $this->tasks_disabled[25]=true;
            $this->tasks_disabled[14]=true;
            $this->tasks_disabled[3]=true;
            $this->tasks_disabled[28]=true;
            $this->tasks_disabled[29]=true;
            $this->tasks_disabled[34]=true;
            $this->tasks_disabled[36]=true;
            $this->tasks_disabled[40]=true;
            $this->tasks_disabled[43]=true;
            $this->tasks_disabled[44]=true;
            $this->tasks_disabled[47]=true;
            $this->tasks_disabled[49]=true;
            $this->tasks_disabled[50]=true;
            $this->tasks_disabled[53]=true;
        }

        if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
            $this->tasks_disabled[20]=true;
            $this->tasks_disabled[30]=true;
            $this->tasks_disabled[44]=true;
        }

        if($this->EnableSargGenerator==0){
            $this->tasks_disabled[26]=true;
            $this->tasks_disabled[27]=true;

        }


        $this->tasks_disabled[6]=true;
        $this->tasks_disabled[2]=true;
        $this->tasks_disabled[8]=true;
        $this->tasks_disabled[9]=true;
        $this->tasks_disabled[10]=true;
        $this->tasks_disabled[11]=true;
        $this->tasks_disabled[15]=true;
        $this->tasks_disabled[16]=true;
        $this->tasks_disabled[21]=true;
        $this->tasks_disabled[23]=true;
        $this->tasks_disabled[25]=true;
        $this->tasks_disabled[28]=true;
        $this->tasks_disabled[34]=true;
        $this->tasks_disabled[36]=true;
        $this->tasks_disabled[40]=true;
        $this->tasks_disabled[42]=true;
        $this->tasks_disabled[44]=true;
        $this->tasks_disabled[49]=true;
        $this->tasks_disabled[50]=true;
        $this->tasks_disabled[53]=true;
        $this->tasks_disabled[37]=true;
        $this->tasks_disabled[46]=true;





    }

    private function fill_task_array(){

        $this->tasks_array[0]="{select}";
        $this->tasks_array[1]="{databases_ufdbupdate}";

        $this->tasks_array[3]="{databases_compilation}";
        $this->tasks_array[4]="{restart_proxy_service}";

        $this->tasks_array[6]="{verify_urls_databases}";
        $this->tasks_array[7]="{build_hours_tables}";
        $this->tasks_array[8]="{update_tlse}";
        $this->tasks_array[9]="{rebuild_visited_sites}";
        $this->tasks_array[10]="{recategorize_schedule}";
        $this->tasks_array[11]="{build_month_tables}";
        $this->tasks_array[12]="{update_kaspersky_databases}";
        $this->tasks_array[13]="{launch_UpdateUtility}";
        $this->tasks_array[14]="{optimize_database}";
        $this->tasks_array[15]="{hourly_cache_performances}";
        $this->tasks_array[16]="{build_daily_visited_websites}";
        $this->tasks_array[18]="{backup_categories}";
        $this->tasks_array[19]="{build_crypted_tables_catz}";
        $this->tasks_array[20]="{compile_ufdb_repos}";
        //$this->tasks_array[21]="{importadmembers}";
        $this->tasks_array[22]="{synchronize_webfilter_rules}";
        $this->tasks_array[23]="{repair_categories}";
        $this->tasks_array[24]="{clean_cloud_datacenters}";
        $this->tasks_array[25]="{build_blocked_week_statistics}";
        $this->tasks_array[26]="{sarg_build_daily_stats}";
        $this->tasks_array[27]="{sarg_build_hourly_stats}";
        $this->tasks_array[28]="{thumbnail_parse}";
        $this->tasks_array[29]="{malware_uri}";
        $this->tasks_array[30]="{update_precompiled_ufdb}";
        $this->tasks_array[31]="{parse_squid_logs_queue}";
        $this->tasks_array[32]="{parse_squid_framework}";
        $this->tasks_array[33]="{squid_logrotate_perform}";
        $this->tasks_array[34]="{squid_week_stats}";
        $this->tasks_array[35]="{squid_backup_stats}";
        $this->tasks_array[36]="{members_stats}";
        //$this->tasks_array[37]="{squid_tail_injector}";
        $this->tasks_array[38]="{web_injector}";
        $this->tasks_array[39]="{reconfigure_proxy_task}";
        $this->tasks_array[40]="{hourly_bandwidth_users}";
        $this->tasks_array[41]="{squid_rrd}";
        $this->tasks_array[42]="{compile_tlse_database}";
        $this->tasks_array[43]="{squid_check_lost_tables}";
        $this->tasks_array[44]="{build_reports}";
        $this->tasks_array[45]="{rebuild_caches}";
        //$this->tasks_array[46]="{fill_squid_client_table}";
        $this->tasks_array[47]="{squid_logs_purge}";
        $this->tasks_array[48]="{squid_logs_restore}";
        $this->tasks_array[49]="{statistics_per_users}";
        $this->tasks_array[50]="{categorize_tables}";
        $this->tasks_array[51]="{restart_ufdb}";
        $this->tasks_array[52]="{proxy_status}";
        $this->tasks_array[53]="{build_proxy_statistics}";
        $this->tasks_array[54]="{perfom_proxy_log_rotation}";
        //$this->tasks_array[56]="{sarg_daily}";
        $this->tasks_array[57]="{reload_webfiltering_service}";
        $this->tasks_array[58]="{proxy_performances}";
        $this->tasks_array[59]="{static_activedirectory_group_task}";
        $this->tasks_array[60]="{local_realtime_statistics}";
        $this->tasks_array[61]="{clean_databases}";
        $this->tasks_array[62]="{ActiveDirectoryRefreshCNX}";
        $this->tasks_array[63]="{restart_webfiltering_http_service}";
        $this->tasks_array[64]="{reload_proxy_service}";
        $this->tasks_array[65]="{restart_icap_service}";



        $this->tasks_explain_array[1]="{databases_ufdbupdate_explain}";
        $this->tasks_explain_array[2]="{instant_update_explain}";
        $this->tasks_explain_array[3]="{databases_compilation_explain}";
        $this->tasks_explain_array[4]="{restart_proxy_service_explain}";
        $this->tasks_explain_array[6]="{verify_urls_databases_explain}";
        $this->tasks_explain_array[7]="{build_hours_tables_explain}";
        $this->tasks_explain_array[8]="{update_tlse_explain}";
        $this->tasks_explain_array[9]="{rebuild_visited_sites_explain}";
        $this->tasks_explain_array[10]="{www_recategorize_explain}";
        $this->tasks_explain_array[11]="{build_month_tables_explain}";
        $this->tasks_explain_array[12]="{update_kaspersky_databases_explain}";
        $this->tasks_explain_array[13]="{launch_UpdateUtility_explain}";
        $this->tasks_explain_array[14]="{squid_optimize_database_explain}";
        $this->tasks_explain_array[15]="{hourly_cache_performances_explain}";
        $this->tasks_explain_array[16]="{build_daily_visited_websites_explain}";
        $this->tasks_explain_array[18]="{backup_categories_explain}";
        $this->tasks_explain_array[19]="{build_crypted_tables_catz_explain}";
        $this->tasks_explain_array[20]="{compile_ufdb_repos_explain}";
        //$this->tasks_explain_array[21]="{importadmembers_explain}";
        $this->tasks_explain_array[22]="{synchronize_webfilter_rules_text}";
        $this->tasks_explain_array[23]="{repair_categories_explain}";
        $this->tasks_explain_array[24]="{clean_cloud_datacenters_explain}";
        $this->tasks_explain_array[25]="{build_blocked_week_statistics_explain}";
        $this->tasks_explain_array[26]="{sarg_build_daily_stats_explain}";
        $this->tasks_explain_array[27]="{sarg_build_daily_stats_explain}";
        $this->tasks_explain_array[28]="{thumbnail_parse_explain}";
        $this->tasks_explain_array[29]="{malware_uri_explain}";
        $this->tasks_explain_array[30]="{update_precompiled_ufdb_explain}";
        $this->tasks_explain_array[31]="{parse_squid_logs_queue_explain}";
        $this->tasks_explain_array[32]="{parse_squid_framework_explain}";
        $this->tasks_explain_array[33]="{squid_logrotate_perform_explain}";
        $this->tasks_explain_array[34]="{squid_week_stats_explain}";
        $this->tasks_explain_array[35]="{squid_backup_stats_explain}";
        $this->tasks_explain_array[36]="{members_stats_explain}";
        //$this->tasks_explain_array[37]="{squid_tail_injector_explain}";
        $this->tasks_explain_array[38]="{web_injector_explain}";
        $this->tasks_explain_array[39]="{reconfigure_proxy_task_explain}";
        $this->tasks_explain_array[40]="{hourly_bandwidth_users_explain}";
        $this->tasks_explain_array[41]="{squid_rrd_explain}";
        $this->tasks_explain_array[42]="{compile_tlse_database_explain}";
        $this->tasks_explain_array[43]="{squid_check_lost_tables_explain}";
        $this->tasks_explain_array[44]="{build_reports_explain}";
        $this->tasks_explain_array[45]="{rebuild_caches_explain}";
        //$this->tasks_explain_array[46]="{fill_squid_client_table_explain}";
        $this->tasks_explain_array[47]="{squid_logs_purge_explain}";
        $this->tasks_explain_array[48]="{squid_logs_restore_explain}";
        $this->tasks_explain_array[49]="{statistics_per_users_explain}";
        $this->tasks_explain_array[50]="{categorize_tables_explain}";
        $this->tasks_explain_array[51]="{restart_ufdb_explain}";
        $this->tasks_explain_array[52]="{proxy_status_explain}";
        //$this->tasks_explain_array[53]="{build_proxy_statistics_explain}";
        $this->tasks_explain_array[54]="{perfom_proxy_log_rotation_explain}";
        //$this->tasks_explain_array[56]="{sarg_daily_explain}";
        $this->tasks_explain_array[57]="{reload_webfiltering_service}";
        $this->tasks_explain_array[58]="{proxy_performances_task_explain}";
        $this->tasks_explain_array[59]="{static_activedirectory_group_task_explain}";
        $this->tasks_explain_array[60]="{local_realtime_statistics_explain}";
        $this->tasks_explain_array[61]="{clean_databases_explain}";
        //$this->tasks_explain_array[62]="{ActiveDirectoryRefreshCNX_explain}";
        $this->tasks_explain_array[62]="{restart_webfiltering_http_service_explain}";
        $this->tasks_explain_array[64]="{reload_proxy_service_explain}";
        $this->tasks_explain_array[65]="{restart_icap_service_explain}";

        $this->tasks_processes[1]="/usr/sbin/artica-phpfpm-service -categories-update";
        $this->tasks_processes[3]="/usr/sbin/artica-phpfpm-service -categories-compile-all";
        $this->tasks_processes[4]="/usr/sbin/artica-phpfpm-service -restart-proxy-schedule";

        $this->tasks_processes[6]="exec.squid.blacklists.php --inject";
        $this->tasks_processes[7]="exec.squid.stats.php --scan-hours";
        //$this->tasks_processes[8]="exec.update.squid.tlse.php";


        $this->tasks_processes[12]="exec.keepup2date.php --update";
        $this->tasks_processes[13]="exec.keepup2date.php --UpdateUtility";
        $this->tasks_processes[14]="exec.squid.stats.php --optimize";
        $this->tasks_processes[18]="exec.squid.cloud.compile.php --backup-catz";
        $this->tasks_processes[19]="exec.squid.cloud.compile.php --v2";
        $this->tasks_processes[20]="exec.squid.cloud.compile.php --ufdb";
        //$this->tasks_processes[21]="exec.adusers.php";
        $this->tasks_processes[22]="exec.squidguard.php --build --force";
        $this->tasks_processes[24]="exec.cleancloudcatz.php --all";
        //$this->tasks_processes[29]="exec.squid.updateuris.malware.php --www";
        $this->tasks_processes[30]="/usr/sbin/artica-phpfpm-service -categories-update";
        //$this->tasks_processes[31]="exec.dansguardian.injector.php";
        $this->tasks_processes[32]="exec.squid.framework.php";
        $this->tasks_processes[35]="exec.squid.dbback.php";

        //$this->tasks_processes[38]="exec.dansguardian.injector.php";
        $this->tasks_processes[39]="exec.squid.php --build --force";
        $this->tasks_processes[41]="exec.squid-rrd.php";
        //$this->tasks_processes[42]="exec.update.squid.tlse.php --compile";

        $this->tasks_processes[45]="exec.squid.rebuild.caches.php";

        $this->tasks_processes[47]="exec.squidlogs.purge.php";
        $this->tasks_processes[48]="exec.squidlogs.restore.php --restore-all";
        $this->tasks_processes[51]="exec.ufdb.php --restart";
        $this->tasks_processes[52]="exec.status.php --all-squid";
        //$this->tasks_processes[53]="exec.squid.stats.central.php";
        $this->tasks_processes[57]="exec.ufdb.php --reload --from-schedule";
        $this->tasks_processes[58]="exec.squid.squeezer.php";
        $this->tasks_processes[59]="exec.squid.static.ad.groups.php";
        $this->tasks_processes[60]="exec.squid.run.schedules.php";
        $this->tasks_processes[61]="exec.clean.postgres.php";
        $this->tasks_processes[62]="exec.squid.KerbRefresh.php";
        $this->tasks_processes[63]="exec.ufdb-http.php --restart";
        $this->tasks_processes[64]="exec.squid.reload.php";
        $this->tasks_processes[65]="exec.c-icap.php --restart-schedule";

        $this->tasks_remote_appliance["51"]=true;
        $this->tasks_remote_appliance["50"]=true;
        $this->tasks_remote_appliance["53"]=true;
        $this->tasks_remote_appliance["54"]=true;
        $this->tasks_remote_appliance["49"]=true;
        $this->tasks_remote_appliance["46"]=true;
        $this->tasks_remote_appliance["44"]=true;
        $this->tasks_remote_appliance["43"]=true;
        $this->tasks_remote_appliance["42"]=true;
        $this->tasks_remote_appliance["40"]=true;
        $this->tasks_remote_appliance["36"]=true;
        $this->tasks_remote_appliance["34"]=true;
        $this->tasks_remote_appliance["30"]=true;
        $this->tasks_remote_appliance["29"]=true;
        $this->tasks_remote_appliance["28"]=true;
        $this->tasks_remote_appliance["27"]=true;
        $this->tasks_remote_appliance["26"]=true;
        $this->tasks_remote_appliance["25"]=true;
        $this->tasks_remote_appliance["23"]=true;
        $this->tasks_remote_appliance["22"]=true;
        $this->tasks_remote_appliance["14"]=true;
        $this->tasks_remote_appliance["15"]=true;
        $this->tasks_remote_appliance["16"]=true;
        $this->tasks_remote_appliance["13"]=true;
        $this->tasks_remote_appliance["12"]=true;
        $this->tasks_remote_appliance["9"]=true;
        $this->tasks_remote_appliance["10"]=true;
        $this->tasks_remote_appliance["11"]=true;
        $this->tasks_remote_appliance["8"]=true;
        $this->tasks_remote_appliance["7"]=true;
        $this->tasks_remote_appliance["6"]=true;
        $this->tasks_remote_appliance["3"]=true;
        $this->tasks_remote_appliance["2"]=true;
        $this->tasks_remote_appliance["1"]=true;


    }




    function LoadStatusCodes(){
        $array= array(
            null=>"{none}",
            200=>"OK",
            201=>"Created",
            202=>"Accepted",
            203=>"Non-Authoritative Information",
            204=>"No Content",
            205=>"Reset Content",
            206=>"Partial Content",
            207=>"Multi Status",
            300=>"Multiple Choices",
            301=>"Moved Permanently",
            302=>"Moved Temporarily",
            303=>"See Other",
            304=>"Not Modified",
            305=>"Use Proxy",
            307=>"Temporary Redirect",
            400=>"Bad Request",
            401=>"Unauthorized",
            402=>"Payment Required",
            403=>"Forbidden",
            404=>"Not Found",
            405=>"Method Not Allowed",
            406=>"Not Acceptable",
            407=>"Proxy Authentication Required",
            408=>"Request Timeout",
            409=>"Conflict",
            410=>"Gone",
            411=>"Length Required",
            412=>"Precondition Failed",
            413=>"Request Entity Too Large",
            414=>"Request URI Too Large",
            415=>"Unsupported Media Type",
            416=>"Request Range Not Satisfiable",
            417=>"Expectation Failed",
            422=>"Unprocessable Entity",
            424=>"Failed Dependency",
            433=>"Unprocessable Entity",
            500=>"Internal Server Error",
            501=>"Not Implemented",
            502=>"Bad Gateway",
            503=>"Service Unavailable",
            504=>"Gateway Timeout",
            505=>"HTTP Version Not Supported",
            507=>"Insufficient Storage",
            600=>"Squid: header parsing error",
            601=>"Squid: header size overflow detected while parsing",
            603=>"roundcube: invalid authorization");

        foreach ($array as $num=>$line){
            if(is_numeric($num)){
                $array[$num]="[$num]: $line";
            }
        }
        reset($array);
        return $array;

    }


    public function CheckDefaultSchedules($ouput=false){
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        if(!$q->TABLE_EXISTS("webfilters_schedules")){
            $sql="CREATE TABLE IF NOT EXISTS `webfilters_schedules` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
                `TimeText` VARCHAR( 128 ) NOT NULL ,`TimeDescription` VARCHAR( 128 ) ,`TaskType` INTEGER ,`Params` TEXT,
                `enabled` INTEGER)";
            $q->QUERY_SQL($sql);
        }

        $update=false;
        $array[6]=array("TimeText"=>"20,40,59 * * * *","TimeDescription"=>"each 20mn");
        $array[8]=array("TimeText"=>"30 5,10,15,20 * * *","TimeDescription"=>"each 5 hours");
        $array[9]=array("TimeText"=>"0 3 * * *","TimeDescription"=>"each day at 03:00");
        $array[10]=array("TimeText"=>"0 5 * * *","TimeDescription"=>"each day at 05:00");
        $array[11]=array("TimeText"=>"0 1 * * *","TimeDescription"=>"each day at 01:00");
        $array[25]=array("TimeText"=>"30 2 * * *","TimeDescription"=>"each day at 02:30");
        $array[2]=array("TimeText"=>"0 * * * *","TimeDescription"=>"Each hour");
        $array[3]=array("TimeText"=>"0 3 * * *","TimeDescription"=>"each day at 03:00");

        $array[15]=array("TimeText"=>"0 * * * *","TimeDescription"=>"Calculate cache performance each hour");
        $array[16]=array("TimeText"=>"30 5,10,15,20 * * *","TimeDescription"=>"each 5 hours");
        $array[21]=array("TimeText"=>"0 2,4,6,8,10,12,14,16,18,20,22 * * *","TimeDescription"=>"Check AD server each 2H");

        $array[28]=array("TimeText"=>"10,20,30,40,50 * * * *","TimeDescription"=>"check thumbnails queue each 10mn");
        $array[29]=array("TimeText"=>"30 6 * * *","TimeDescription"=>"Update infected uris Each day at 06h30");
        $array[30]=array("TimeText"=>"30 4 * * *","TimeDescription"=>"Update precompiled databases Each day at 04h30");
        $array[31]=array("TimeText"=>"0,5,10,15,20,25,30,35,40,45,50,55 * * * *","TimeDescription"=>"Check queue requests each 5mn");


        $array[37]=array("TimeText"=>"* * * * *","TimeDescription"=>"Inject into Mysql each minute");
        $array[38]=array("TimeText"=>"* * * * *","TimeDescription"=>"Inject into Mysql each minute");
        $array[40]=array("TimeText"=>"10 * * * *","TimeDescription"=>"Each hour +10mn");
        $array[42]=array("TimeText"=>"30 4 * * *","TimeDescription"=>"Compile Toulouse databases tables Each day at 04h30");
        $array[43]=array("TimeText"=>"30 3 * * *","TimeDescription"=>"Lost tables Each day at 03h30");
        $array[46]=array("TimeText"=>"7,22,37,52 * * * *","TimeDescription"=>"each 15mn");
        $array[47]=array("TimeText"=>"30 2 * * *","TimeDescription"=>"Daily Purge Statistics at 2h30");
        $array[51]=array("TimeText"=>"30 5 * * *","TimeDescription"=>"Restart Web Filtering service each day at 05h30");
        $array[52]=array("TimeText"=>"0,5,10,15,20,25,30,35,40,45,50,55 * * * *","TimeDescription"=>"Generate Proxy status each 5mn");
        $array[53]=array("TimeText"=>"0 1 * * *","TimeDescription"=>"Generate Statistics, each day at 01h00");
        //$array[56]=array("TimeText"=>"59 23 * * *","TimeDescription"=>"each day at 23:59");
        $array[57]=array("TimeText"=>"30 3 * * *","TimeDescription"=>"each day at 03:30");
        $array[60]=array("TimeText"=>"0,6,11,16,21,26,31,36,41,46,51,56 * * * *","TimeDescription"=>"each 5 minutes");
        $array[61]=array("TimeText"=>"30 4 * * *","TimeDescription"=>"each day at 04:30");

        $this->tasks_disabled[6]=true;
        $this->tasks_disabled[2]=true;
        $this->tasks_disabled[9]=true;
        $this->tasks_disabled[10]=true;
        $this->tasks_disabled[11]=true;
        $this->tasks_disabled[15]=true;
        $this->tasks_disabled[16]=true;
        $this->tasks_disabled[17]=true;
        $this->tasks_disabled[23]=true;
        $this->tasks_disabled[25]=true;
        $this->tasks_disabled[28]=true;
        $this->tasks_disabled[32]=true;
        $this->tasks_disabled[34]=true;
        $this->tasks_disabled[36]=true;
        $this->tasks_disabled[40]=true;
        $this->tasks_disabled[43]=true;
        $this->tasks_disabled[44]=true;
        $this->tasks_disabled[49]=true;
        $this->tasks_disabled[50]=true;
        $this->tasks_disabled[55]=true;
        $this->tasks_disabled[56]=true;

        $SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
        $users=new usersMenus();


        if($SQUIDEnable==0){
            $this->tasks_disabled[52]=true;
            $this->tasks_disabled[17]=true;
        }
        unset($this->tasks_disabled[1]);
        foreach ($this->tasks_disabled as $TaskType=>$content){
            if($TaskType==3){ if($users->CORP_LICENSE){continue;} }
            if($ouput){echo "Remove Task Type: $TaskType\n";}
            if($TaskType==1){continue;}
            $q->QUERY_SQL("DELETE FROM webfilters_schedules WHERE TaskType=$TaskType");
        }


        foreach ($array as $TaskType=>$content){

            if($GLOBALS["VERBOSE"]){echo "<strong style='color:blue'>$TaskType</strong>\n";}

            if(isset($this->tasks_disabled[$TaskType])){
                if($this->tasks_disabled[$TaskType]){
                    if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>$TaskType tasks_disabled</strong>\n";}
                    continue;
                }
            }


            $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_schedules WHERE TaskType=$TaskType");
            if(!$q->ok){
                continue;
            }
            if(!isset($ligne["ID"])){
                continue;
            }

            if($ligne["ID"]>0){
                if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>$TaskType Already saved as {$ligne["ID"]}</strong>\n";}
                continue;
            }

            $sql="INSERT OR IGNORE INTO webfilters_schedules (TimeDescription,TimeText,TaskType,enabled) 
					VALUES('{$content["TimeDescription"]}','{$content["TimeText"]}','$TaskType',1)";

            if(function_exists("squid_admin_mysql")){
                squid_admin_mysql(2, "Task type $TaskType is not added into scheduler [add it]", "{$content["TimeDescription"]} / {$content["TimeText"]}",__FILE__,__LINE__);
            }

            $q->QUERY_SQL($sql);
            if(!$q->ok){
                squid_admin_mysql(1, "Task type $TaskType MySQL error", "$sql\n$q->mysql_error\n",__FILE__,__LINE__);
                continue;
            }
            $update=true;
        }

        if($update){$sock=new sockets();$sock->getFrameWork("squid.php?build-schedules=yes");}


    }

    FUNCTION DELETE_TABLE($table){
        if(!function_exists("mysqli_connect")){return 0;}
        if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
        squid_admin_mysql(2, "MySQL table $this->database/$table was deleted $called" , null, __FILE__, __LINE__);}
        $this->QUERY_SQL("DROP TABLE `$table`",$this->database);
        if(!$this->ok){return false;}
        $this->QUERY_SQL("FLUSH TABLES",$this->database);
        return true;
    }


    public function TestingConnection($called=null){
        writeToFile("---------------------------------------------------------------------------------\n","/var/log/class.mysql.queries.log");
        writeToFile("TestingConnection($called)\n","/var/log/class.mysql.queries.log");
        if(function_exists("debug_backtrace")){
            $this->trace=@debug_backtrace();
            foreach ($this->trace as $index=>$line){writeToFile("{$line["function"]} line {$line["line"]} From: {$line["file"]}\n","/var/log/class.mysql.queries.log");}
        }
    }

    public function COUNT_ROWS($table,$database=null){
        $table=str_replace("`", "", $table);
        $table=str_replace("'", "", $table);
        $table=str_replace("\"", "", $table);
        if(!function_exists("mysqli_connect")){return 0;}
        $sql="show TABLE STATUS WHERE Name='$table'";
        $ligne=@mysqli_fetch_array($this->QUERY_SQL($sql,$database));
        if($ligne["Rows"]==null){$ligne["Rows"]=0;}
        return $ligne["Rows"];
    }


    public function TABLE_SIZE($table,$database=null){
        $database=trim($database);
        if($database=="artica_backup"){$database=$this->database;}
        if($database=="artica_events"){$database=$this->database;}
        if($database=="ocsweb"){$database=$this->database;}
        if($database=="postfixlog"){$database=$this->database;}
        if($database=="powerdns"){$database=$this->database;}
        if($database=="zarafa"){$database=$this->database;}
        if($database=="syslogstore"){$database=$this->database;}
        if($database==null){$database=$this->database;}
        if(!function_exists("mysqli_connect")){return 0;}
        $sql="show TABLE STATUS WHERE Name='$table'";
        $ligne=@mysqli_fetch_array($this->QUERY_SQL($sql,$database));
        if($ligne["Data_length"]==null){$ligne["Data_length"]=0;}
        if($ligne["Index_length"]==null){$ligne["Index_length"]=0;}
        return $ligne["Index_length"]+$ligne["Data_length"];
    }



    public function TABLE_EXISTS($table,$database=null){
        $keyCache=md5(__CLASS__.__FUNCTION__.$table.__LINE__);
        if(isset($GLOBALS[$keyCache])){return $GLOBALS[$keyCache];}
        if(!is_null($database)) {
            $database = trim($database);
        }
        if($database=="artica_backup"){$database=$this->database;}
        if($database=="artica_events"){$database=$this->database;}
        if($database=="ocsweb"){$database=$this->database;}
        if($database=="postfixlog"){$database=$this->database;}
        if($database=="powerdns"){$database=$this->database;}
        if($database=="zarafa"){$database=$this->database;}
        if($database=="syslogstore"){$database=$this->database;}
        if($database==null){$database=$this->database;}
        if(function_exists("debug_backtrace")){
            try {
                $trace=@debug_backtrace();
                if(isset($trace[1])){$called="\ncalled by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
            } catch (Exception $e) {$this->writeLogs("TABLE_EXISTS:".__LINE__.": Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__LINE__);}
        }

        $table=str_replace("`", "", $table);
        $table=str_replace("'", "", $table);
        $table=str_replace("\"", "", $table);


        if(!$this->DATABASE_EXISTS($database)){

            $this->writeLogs("Database $database does not exists...create it",__CLASS__.'/'.__FUNCTION__,__FILE__);
            if(!$this->CREATE_DATABASE($database)){
                $this->writeLogs("Unable to create $database database",__CLASS__.'/'.__FUNCTION__,__LINE__);
                return false;
            }
        }

        $sql="SHOW TABLES";
        $results=$this->QUERY_SQL($sql,$database,$called);
        $result=false;
        while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
            $GLOBALS[$keyCache][$database][$ligne["Tables_in_$database"]]=true;
            if(!$GLOBALS["AS_ROOT"]){$_SESSION[$keyCache][$database][$ligne["Tables_in_$database"]]=true;}
            if(strtolower($table)==strtolower($ligne["Tables_in_$database"])){
                $GLOBALS[$keyCache]=true;
                return true;
            }
        }
        $GLOBALS[$keyCache]=false;
        return $result;

    }

    public function DomainToInt($strdomain=null){
        if($strdomain==null){return 0;}
        $strdomain=trim(strtolower($strdomain));
        if(isset($GLOBALS["DomainToInt"][$strdomain])){return $GLOBALS["DomainToInt"][$strdomain];}
        $id=$this->DomainToIntredis($strdomain);
        if($id>0){
            $GLOBALS["DomainToInt"][$strdomain]=$id;
            return $id;
        }

        $q=new postgres_sql();
        $ligne=$q->mysqli_fetch_array("SELECT id FROM domains_table WHERE sitename='$strdomain'");
        if(!$q->ok){echo $q->mysql_error."\n";}
        $id=intval($ligne["id"]);
        if($id>0){
            $this->set_DomainToIntredis($strdomain,$id);
            $GLOBALS["DomainToInt"][$strdomain]=$id;
            return $id;
        }

        $q->QUERY_SQL("INSERT INTO domains_table (sitename) VALUES ('$strdomain') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo $q->mysql_error."\n";}

        $ligne=$q->mysqli_fetch_array("SELECT id FROM domains_table WHERE sitename='$strdomain'");
        if(!$q->ok){echo $q->mysql_error."\n";}
        $id=intval($ligne["id"]);
        if($id>0){
            $this->set_DomainToIntredis($strdomain,$id);
            $GLOBALS["DomainToInt"][$strdomain]=$id;
            return $id;
        }
        return 0;

    }

    private function set_DomainToIntredis($strdomain,$id){
        $redis = new Redis();

        try {
            $redis->connect('/var/run/redis/redis.sock');
        } catch (Exception $e) {
            if ($GLOBALS["VERBOSE"]) {echo $e->getMessage() . "\n";}
            return null;
        }
        $redis->set("DomainToInt:$strdomain",$id,2880);
        $redis->close();
    }

    private function DomainToIntredis($strdomain){
        $redis = new Redis();

        try {
            $redis->connect('/var/run/redis/redis.sock');
        } catch (Exception $e) {
            if ($GLOBALS["VERBOSE"]) {echo $e->getMessage() . "\n";}
            return null;
        }

        $value=$redis->get("DomainToInt:$strdomain");
        $redis->close();
        if(!$value){return 0;}
        return intval($value);

    }



    private function DATABASE_EXISTS($database){

        $EnableMySQL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMySQL"));
        if($EnableMySQL==0){
            return false;
        }

        if(!function_exists("mysqli_query")){
            return false;
        }

        writeToFile("---------------------------------------------------------------------------------\n","/var/log/class.mysql.queries.log");
        writeToFile("DATABASE_EXISTS($database)\n","/var/log/class.mysql.queries.log");
        if(function_exists("debug_backtrace")){
            $this->trace=@debug_backtrace();
            foreach ($this->trace as $index=>$line){writeToFile("{$line["function"]} line {$line["line"]} From: {$line["file"]}\n","/var/log/class.mysql.queries.log");}
        }


        if(isset($GLOBALS[__CLASS__][__FUNCTION__][strtolower($database)])){return true;}
        $database=trim($database);
        if($database=="artica_backup"){$database=$this->database;}
        if($database=="artica_events"){$database=$this->database;}
        if($database=="ocsweb"){$database=$this->database;}
        if($database=="postfixlog"){$database=$this->database;}
        if($database=="powerdns"){$database=$this->database;}
        if($database=="zarafa"){$database=$this->database;}
        if($database=="syslogstore"){$database=$this->database;}
        if($database==null){$database=$this->database;}


        $this->BD_CONNECT();

        if(!$this->mysqli_connection){
            return false;
        }

        $results=@mysqli_query($this->mysqli_connection,"SHOW DATABASES");

        if(!$results){
            $errnum=@mysqli_errno($this->mysqli_connection);
            $des=@mysqli_error($this->mysqli_connection);
            if($GLOBALS["VERBOSE"]){echo "DATABASE_EXISTS:: $errnum $des\n";}
        }



        while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
            $GLOBALS[__CLASS__][__FUNCTION__][strtolower($ligne["Database"])]=true;
            if(strtolower($database)==strtolower($ligne["Database"])){
                return true;
            }
        }

        return false;
    }

    function PRIVILEGES($user,$password):bool{

        $sql="SELECT User FROM user WHERE User='$user'";

        $ligne=@mysqli_fetch_array($this->QUERY_SQL($sql,'mysql'));
        $userfound=$ligne["User"];
        $sql="DELETE FROM `mysql`.`db` WHERE `db`.`Db` = '$this->database'";
        $this->QUERY_SQL($sql,"mysql");
        if(!$this->ok){
            writelogs("Failed to delete privileges FROM $this->database \"$this->mysql_error\"",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            return false;
        }


        if($userfound==null){
            $sql="CREATE USER '$user'@'*' IDENTIFIED BY '$password';";
            $this->EXECUTE_SQL($sql);
            if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}
        }


        $sql="CREATE USER '$user'@'*' IDENTIFIED BY '$password';";
        $this->EXECUTE_SQL($sql);
        if(!$this->ok){
            echo "CREATE USER $user Failed with root/root+Password\n `$this->mysql_error`\n";
            return false;
        }

        $sql="GRANT USAGE ON `$this->database`. *  TO '$user'@'*' IDENTIFIED BY '$password' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;";
        $this->EXECUTE_SQL($sql);
        if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}


        $sql="GRANT ALL PRIVILEGES ON `$this->database` . * TO '$user'@'*' WITH GRANT OPTION ;";
        $this->EXECUTE_SQL($sql);
        if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}
        return true;
    }



    public function FIELD_EXISTS($table,$field,$database=null){


        writeToFile("---------------------------------------------------------------------------------\n","/var/log/class.mysql.queries.log");
        writeToFile("FIELD_EXISTS($table,$field,$database)\n","/var/log/class.mysql.queries.log");
        if(function_exists("debug_backtrace")){
            $this->trace=@debug_backtrace();
            foreach ($this->trace as $index=>$line){writeToFile("{$line["function"]} line {$line["line"]} From: {$line["file"]}\n","/var/log/class.mysql.queries.log");}
        }



        if($database==null){$database=$this->database;}
        $field=trim($field);
        if(isset($GLOBALS["__FIELD_EXISTS"])){
            if(isset($GLOBALS["__FIELD_EXISTS"][$database][$table])){
                if(isset($GLOBALS["__FIELD_EXISTS"][$database][$table][$field])){
                    if($GLOBALS["__FIELD_EXISTS"][$database][$table][$field]){return true;}
                }
            }
        }
        $sql="SHOW FULL FIELDS FROM `$table` WHERE Field='$field';";
        $ligne=@mysqli_fetch_array($this->QUERY_SQL($sql,$database));

        if(trim($ligne["Field"])<>null){
            $GLOBALS["__FIELD_EXISTS"][$database][$table][trim($field)]=true;
            return true;
        }else{
            $this->writelogs("\"$field\" does not exists in table $table  in $database",__FUNCTION__,__LINE__);
            $this->writelogs("$sql",__FUNCTION__,__LINE__);
            return false;
        }

    }

    public function BD_CONNECT($noretry=false,$called=null){
        $EnableMySQL=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMySQL");
        if($EnableMySQL==0){
            return false;
        }
        if(isset($GLOBALS["SQUID_BD_STOP_PROCESSSING"])){if($GLOBALS["SQUID_BD_STOP_PROCESSSING"]){return false;}}
        if($called==null){if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}}



        if($this->SERVER_CONNECT($noretry,$called)){
            unset($GLOBALS["SQUID_BD_STOP_PROCESSSING"]);
            return true;
        }

        if ($this->mysqli_connection instanceof mysqli) {
            if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
        }
        usleep(500);
        if($this->SERVER_CONNECT($noretry,$called)){
            unset($GLOBALS["SQUID_BD_STOP_PROCESSSING"]);
            return true;
        }
        if ($this->mysqli_connection instanceof mysqli) {
            if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
        }
        usleep(500);
        if($this->SERVER_CONNECT($noretry,$called)){
            unset($GLOBALS["SQUID_BD_STOP_PROCESSSING"]);
            return true;
        }
        $GLOBALS["SQUID_BD_STOP_PROCESSSING"]=true;
        return false;


    }


    public function SERVER_CONNECT($noretry=false,$called=null){

        if(is_null($this->mysqli_connection)){
            return false;
        }

        if(!$this->mysqli_connection){
            return false;
        }

        if(function_exists("mysqli_ping")) {
            if (@mysqli_ping($this->mysqli_connection)) {
                return true;
            }
        }


        if(!isset($GLOBALS["SERVER_CONNECT_COUNT"])){$GLOBALS["SERVER_CONNECT_COUNT"]=0;}
        $GLOBALS["SERVER_CONNECT_COUNT"]++;
        $ErrorCount="{$GLOBALS["SERVER_CONNECT_COUNT"]} retried connection";
        if(trim($this->mysql_admin)==null){$this->mysql_admin="root";}
        if($called==null){if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}}
        if($this->MySQLConnectionType==1){
            if(!$this->is_socket($this->SocketName)){
                $this->mysql_error="$this->SocketName $ErrorCount no such socket";
                $this->ToSyslog("$this->SocketName $ErrorCount no such socket");
                $GLOBALS["THIS_TestingConnection"]=false;
                return false;
            }

            if($this->SocketName=="/var/run/mysqld/mysqld.sock"){
                ini_set("mysqli.default_socket", "/var/run/mysqld/mysqld.sock");
                $bd=@mysqli_connect("localhost",$this->mysql_admin,$this->mysql_password,null,0,$this->SocketName);
            }else{
                ini_set("mysqli.default_socket", $this->SocketName);
                $bd=@mysqli_connect("localhost",$this->mysql_admin,null,null,500,$this->SocketName);
            }



            if($bd){
                $this->mysqli_connection=$bd;
                $GLOBALS["THIS_TestingConnection"]=true;
                return true;
            }





            $des=@mysqli_error($bd); $errnum=@mysqli_errno($bd);

            $this->BD_CONNECT_ERROR=__LINE__.": Socket: $this->SocketName, $ErrorCount MySQLConnectionType = $this->MySQLConnectionType failed (N:$errnum) \"$des\" $called";
            $this->ToSyslog($this->BD_CONNECT_ERROR);
            $this->writelogs($this->BD_CONNECT_ERROR,__FUNCTION__,__LINE__);
            $GLOBALS["THIS_TestingConnection"]=false;
            return false;
        }

        if($this->MySQLConnectionType==2){
            ini_set("mysqli.default_socket", $this->SocketPath);
            $bd=mysqli_connect(null,$this->mysql_admin,$this->mysql_password,null,0,$this->SocketPath);
            if($bd){$this->mysqli_connection=$bd;return true;}
            $errnum=@mysqli_errno($this->mysqli_connection);
            $des=@mysqli_error($this->mysqli_connection);

            $this->BD_CONNECT_ERROR=__LINE__.": $ErrorCount MySQL Server:$this->mysql_server: MySQLConnectionType = $this->MySQLConnectionType failed (N:$errnum) \"$des\" $called";
            $this->ToSyslog($this->BD_CONNECT_ERROR);
            $this->writelogs($this->BD_CONNECT_ERROR,__FUNCTION__,__LINE__);
            $GLOBALS["SQUID_BD_STOP_PROCESSSING"]=true;
            $GLOBALS["THIS_TestingConnection"]=false;
            return false;
        }

        if($this->mysql_server=="127.0.0.1"){
            if(!$this->is_socket($this->SocketName)){
                $this->mysql_error="$this->SocketName no such socket $ErrorCount";
                $this->ToSyslog("$this->SocketName no such socket $ErrorCount");
                $GLOBALS["THIS_TestingConnection"]=false;
                $GLOBALS["SQUID_BD_STOP_PROCESSSING"]=true;
                return false;
            }

            $bd=@mysqli_connect("localhost",$this->mysql_admin,$this->mysql_password,0,$this->SocketName);
            $FinalLog="$this->SocketName@$this->mysql_admin";
        }else{
            $bd=mysqli_connect(null,$this->mysql_admin,$this->mysql_password,null,0,$this->SocketPath);
            $FinalLog="$this->mysql_admin@$this->mysql_server:$this->mysql_port";
        }


        if($bd){$this->mysqli_connection=$bd;$GLOBALS["THIS_TestingConnection"]=true;return true;}
        $des=@mysqli_error($this->mysqli_connection); $errnum=@mysqli_errno($this->mysqli_connection);
        $this->BD_CONNECT_ERROR=__LINE__.":$ErrorCount $FinalLog = Err:$errnum $des $called";
        $this->ToSyslog($this->BD_CONNECT_ERROR);
        $this->writelogs($this->BD_CONNECT_ERROR,__FUNCTION__,__LINE__);
        $GLOBALS["THIS_TestingConnection"]=false;

        return false;

    }

    private function is_socket($fpath){
        $results=@stat($fpath);
        $ts=array(0140000=>'ssocket',0120000=>'llink',0100000=>'-file',0060000=>'bblock',0040000=>'ddir',0020000=>'cchar',0010000=>'pfifo');
        $t=decoct($results['mode'] & 0170000); // File Encoding Bit
        $octdec=octdec($t);
        if(isset($ts[$octdec])) {
            if (substr($ts[$octdec], 1) == "socket") {
                return true;
            }
        }
        return false;
    }



    public function TABLES_STATUS_CORRUPTED(){
        $ARRAY=array();
        $sql="show TABLE STATUS";
        if(!$this->BD_CONNECT()){return false;}
        $ok=@mysqli_select_db($this->mysqli_connection,$this->database);

        if (!$ok){
            if($GLOBALS["VERBOSE"]){echo "mysql_select_db -> ERROR\n";}
            $errnum=@mysqli_errno($this->mysqli_connection);
            $des=@mysqli_error($this->mysqli_connection);
            writelogs("$this->SocketPath:$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            $this->mysql_errornum=$errnum;
            $this->mysql_error="Error Number ($errnum) ($des)";
            $this->ok=false;
            return false;
        }

        $results=@mysqli_query($this->mysqli_connection,$sql);
        if(mysqli_error($this->mysqli_connection)){
            if($GLOBALS["VERBOSE"]){echo "mysql_query -> ERROR\n";}
            $time=date('h:i:s');
            $errnum=mysqli_errno($this->mysqli_connection);
            $des=mysqli_error($this->mysqli_connection);
            $this->mysql_error="Error Number ($errnum) ($des)";
            writelogs("$this->SocketPath:$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            $this->ok=false;
            return false;
        }



        if($GLOBALS["VERBOSE"]){echo "mysql_query -> ". mysqli_num_rows($results)." items\n";}
        while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){


            $Name=$ligne["Name"];
            $Comment=$ligne["Comment"];
            if(trim($Comment)==null){continue;}
            $ARRAY[$Name]=$Comment;

        }
        if($GLOBALS["VERBOSE"]){print_r($ARRAY);}

        return $ARRAY;
    }


    public function EXECUTE_SQL($sql){
        if(!$this->BD_CONNECT()){return false;}

        $results=@mysqli_query($this->mysqli_connection,$sql);
        if(mysqli_error($this->mysqli_connection)){
            $time=date('h:i:s');
            $errnum=mysqli_errno($this->mysqli_connection);
            $des=mysqli_error($this->mysqli_connection);
            $this->mysql_error="Error Number ($errnum) ($des) <hr>$sql";
            writelogs("$this->SocketPath:$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            $this->ok=false;
            return false;
        }

        $this->ok=true;
        return $results;
    }


    public function DATABASE_LIST(){
        if(!$this->BD_CONNECT()){return false;}
        $sql="SHOW DATABASES";
        $this->BD_CONNECT();
        $results=@mysqli_query($this->mysqli_connection,$sql);
        $errnum=@mysqli_error($this->mysqli_connection);
        $des=@mysqli_error($this->mysqli_connection);
        $this->mysql_error=$des;


        while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
            $Database=$ligne["Database"];
            $array[$Database]=true;
        }
        return $array;
    }

    private function writelogs($text=null,$function=null,$line=0){
        $file_source=PROGRESS_DIR."/mysql.squid.debug";
        @mkdir(dirname($file_source));
        if(!is_numeric($line)){$line=0;}
        if(function_exists("writelogs")){
            writelogs("$text (L.$line)",__CLASS__."/$function",__FILE__,$line);
        }
        if(!$GLOBALS["VERBOSE"]){return;}
        $logFile=$file_source;
        if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
        if (is_file($logFile)) {$size=filesize($logFile);if($size>1000000){unlink($logFile);}}
        $f = @fopen($logFile, 'a');
        $date=date("Y-m-d H:i:s");
        @fwrite($f, "$date:[".__CLASS__."/$function()][{$_SERVER['REMOTE_ADDR']}]:: $text (L.$line)\n");
        @fclose($f);
    }

    public function QUERY_PDO($sql){
        $this->sql=$sql;
        $pdo_opt = array ( PDO::ATTR_ERRMODE=> PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC);

        try {
            $dbh = new PDO($this->PDO_DSN, $this->mysql_admin, $this->mysql_password,$pdo_opt);
        } catch (PDOException $e) {
            $this->ok=false;
            $this->mysql_error="Connection failed $this->PDO_DSN ". $e->getMessage();
            if($GLOBALS["VERBOSE"]){echo "PDO Failed: $this->mysql_error\n";}
            return false;
        }




        $stmt = $dbh->prepare($sql);



        if (!$stmt->execute()){
            $this->ok=false;
            $this->mysql_error=implode(":",$stmt->errorInfo());
            if($GLOBALS["VERBOSE"]){echo "PDO Failed: $this->mysql_error\n";}
            return false;
        }


        return $stmt;

    }


    public function QUERY_SQL($sql,$database=null,$called=null){
        $EnableMySQL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMySQL"));
        if($EnableMySQL==0){
            $GLOBALS["SQUID_BD_STOP_PROCESSSING"]=true;
            return false;
        }
        $TableDropped_blacklist["ufdb_smtp"]=true;
        $database=trim($database);
        $sqllog=str_replace("\n", " ", $sql);
        $sqllog=str_replace("'", "`", $sqllog);
        $sqllog=str_replace("\t", " ", $sqllog);
        if($database=="artica_backup"){$database=$this->database;}
        if($database=="artica_events"){$database=$this->database;}
        if($database=="ocsweb"){$database=$this->database;}
        if($database=="postfixlog"){$database=$this->database;}
        if($database=="powerdns"){$database=$this->database;}
        if($database=="zarafa"){$database=$this->database;}
        if($database=="syslogstore"){$database=$this->database;}
        if($database=="metaclient"){$database=$this->database;}
        if($database==null){$database=$this->database;}
        $this->last_id=0;
        $this->sql=$sql;
        $CLASS=__CLASS__;
        $FUNCTION=__FUNCTION__;
        $FILENAME=basename(__FILE__);
        $LOGPRF="$FILENAME::$CLASS/$FUNCTION";
        $this->ok=false;
        $sql=trim($sql);


        writeToFile("---------------------------------------------------------------------------------\n","/var/log/class.mysql.queries.log");
        writeToFile("$database: $sql\n","/var/log/class.mysql.queries.log");



        if(function_exists("debug_backtrace")){
            $this->trace=@debug_backtrace();

            foreach ($this->trace as $index=>$line){writeToFile("{$line["function"]} line {$line["line"]} From: {$line["file"]}\n","/var/log/class.mysql.queries.log");}
            reset($this->trace);
            if(isset($this->trace[1])){
                if(!isset($this->trace[1]["file"])){$this->trace[1]["file"]="NONE";}
                if(!isset($this->trace[1]["line"])){$this->trace[1]["line"]="NONE";}
                $called="called by ". basename($this->trace[1]["file"])." {$this->trace[1]["function"]}() line {$this->trace[1]["line"]}";
            }
        }

        if($called==null){if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}}


        if(preg_match("#delete.*?webfilter_members#i", $sql)){
            $this->ToSyslog("FATAL!! CHATEAU-THIERRY $sql $called");

        }


        if($GLOBALS["DEBUG_SQL"]){echo "this->BD_CONNECT\n";}
        if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
        if(!$this->BD_CONNECT(false,$called)){
            if($GLOBALS["VERBOSE"]){echo "Unable to BD_CONNECT class mysql/QUERY_SQL\n";}
            $this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error, unable to connect to MySQL server, request failed",__CLASS__.'/'.__FUNCTION__,__LINE__);
            $this->ok=false;
            $this->mysql_error=$this->BD_CONNECT_ERROR ." Error, unable to connect to MySQL server $this->SocketName@$this->mysql_admin";
            $this->ToSyslog($this->mysql_error);
            return false;
        }

        if(preg_match("#DROP TABLE\s+(.+)$#i", $sql,$re)){
            $TableDropped=$re[1];
            if(!preg_match("#[0-9]+_curl#", $TableDropped)){
                if(!isset($TableDropped_blacklist[$TableDropped])){
                    if(function_exists("system_admin_events")){
                        $trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
                        squid_admin_mysql(2, "MySQL table $database/$TableDropped was deleted $called" , __FUNCTION__,  __LINE__, "mysql-delete");
                    }
                }
            }
        }


        if($GLOBALS["DEBUG_SQL"]){echo "mysqli_select_db()\n";}
        if($GLOBALS['VERBOSE']){$ok=mysqli_select_db($this->mysqli_connection,$database);}else{
            $ok=@mysqli_select_db($this->mysqli_connection,$database);
        }

        if (!$ok){
            $errnum=@mysqli_errno($this->mysqli_connection);
            $des=@mysqli_error($this->mysqli_connection);
            if(!is_numeric($errnum)){
                if($GLOBALS["VERBOSE"]){echo "$LOGPRF mysql_select_db/$this->database/".__LINE__."  [FAILED] error $errnum $des -> RESTART !!\n";};
                if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
                $this->BD_CONNECT(false,$called);
                $ok=@mysqli_select_db($this->mysqli_connection,$this->database);
                if (!$ok){
                    if($GLOBALS["VERBOSE"]){echo "$LOGPRF mysql_select_db/$this->database/".__LINE__." [FAILED] -> SECOND TIME !!\n";};

                    if(function_exists("squid_admin_mysql")){
                        squid_admin_mysql(0,"FATAL MySQL error Connection failed to MySQL database",$sql."\n$called\n$this->mysql_error",__FILE__,__LINE__);}
                    $this->ok=false;
                    return false;
                }
            }
        }


        if (!$ok){
            $errnum=@mysqli_errno($this->mysqli_connection);
            $des=@mysqli_error($this->mysqli_connection);
            if($GLOBALS["VERBOSE"]){echo "$LOGPRF mysql_select_db/$this->database/".__LINE__." [FAILED] N.$errnum DESC:$des mysql/QUERY_SQL\n";}
            if($GLOBALS["VERBOSE"]){echo "mysql -u $this->mysql_admin -p$this->mysql_password -h $this->mysql_server -P $this->mysql_port -A $this->database\n";}
            $this->mysql_errornum=$errnum;
            $this->mysql_error=$des;
            $time=date('h:i:s');

            $this->writeLogs("$LOGPRF Line:".__LINE__.":mysql_select_db DB:\"$database\" Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin ($called)",__CLASS__.'/'.__FUNCTION__,__LINE__);
            $this->mysql_error="$LOGPRF Line:".__LINE__.": mysql_select_db:: Error $errnum ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin line:".__LINE__." SQL=$sqllog";
            $this->ok=false;
            $this->ToSyslog($this->mysql_error);
            $this->ToSyslog($sql);
            if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
            $this->mysqli_connection=false;
            if(function_exists("squid_admin_mysql")){squid_admin_mysql(0,"FATAL MySQL error Error Number ($errnum) ($des)",$sql."\n$called\n$this->mysql_error",__FILE__,__LINE__);}



            return null;
        }


        $mysql_unbuffered_query_log=null;
        $results=@mysqli_query($this->mysqli_connection,$sql);


        if(!$results){
            $errnum=@mysqli_errno($this->mysqli_connection);
            $des=@mysqli_error($this->mysqli_connection);

            if(preg_match('#Duplicate entry#',$des)){
                $this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error $errnum $des line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__LINE__);
                $this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" ". substr($sql,0,255)."...line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__LINE__);
                $this->writelogs($sql,__CLASS__.'/'.__FUNCTION__,__FILE__);
                $this->ok=true;
                if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
                $this->mysqli_connection=false;
                return true;
            }
            $this->mysql_errornum=$errnum;
            $this->mysql_error="QUERY_SQL:".__LINE__.": $mysql_unbuffered_query_log:: $called Error $errnum ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin line:".__LINE__." SQL:$sqllog";





            $this->ToSyslog($this->mysql_error);
            $sql=str_replace("\n", " ", $sql);
            $sql=str_replace("\t", " ", $sql);
            $sql=str_replace("  ", " ", $sql);
            $this->ToSyslog($sql);
            if(preg_match("#Table\s+'(.+?)'.*?is marked as crashed#", $des,$re)){
                if(class_exists("sockets")){
                    $sock=new sockets();
                    $ARRAY["DB"]=$database;
                    $ARRAY["TABLE"]=$re[1];
                    $data=urlencode(base64_encode(serialize($ARRAY)));
                    $sock->getFrameWork("squid.php?mysql-crash=$data");
                }
            }


            if($GLOBALS["VERBOSE"]){echo "$LOGPRF $mysql_unbuffered_query_log/".__LINE__." [FAILED] N.$errnum DESC:$des $called\n";}
            if($GLOBALS["VERBOSE"]){echo "$LOGPRF $mysql_unbuffered_query_log".__LINE__." [FAILED] $sql\n";}
            if(function_exists("squid_admin_mysql")){squid_admin_mysql(0,"FATAL MySQL error Error Number ($errnum) ($des)",$sql."\n$this->mysql_error",__FILE__,__LINE__);}
            @mysqli_free_result($this->mysqli_connection);
            if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
            $this->mysqli_connection=false;
            $this->ok=false;
            return null;

        }
        if($GLOBALS["DEBUG_SQL"]){echo "SUCCESS\n";}
        $this->ok=true;
        if(intval($this->last_id)==0){
            $this->last_id=@mysqli_insert_id($this->mysqli_connection);
        }
        $result_return=$results;
        @mysqli_free_result($this->mysqli_connection);
        if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
        $this->mysqli_connection=false;
        return $result_return;


    }

    private function ToSyslog($text,$error=false){
        $text=str_replace("\n", " ", $text);
        $text=str_replace("\r", " ", $text);


        if(function_exists("debug_backtrace")){
            $trace=@debug_backtrace();
            if(isset($trace[1])){
                $function="{$trace[1]["function"]}()";
                $line="{$trace[1]["line"]}";
            }
        }

        $text="{$function}[$line]:$text";
        if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
        if(function_exists("openlog")){openlog("mysql-squid", LOG_PID , LOG_SYSLOG);}
        if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
        if(function_exists("closelog")){closelog();}
    }

    public function FIELD_TYPE($table,$field){
        $EnableMySQL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMySQL"));
        if($EnableMySQL==0){
            $GLOBALS["SQUID_BD_STOP_PROCESSSING"]=true;
            return false;
        }
        $database=$this->database;
        if(isset($GLOBALS["__FIELD_TYPE"])){
            if(isset($GLOBALS["__FIELD_TYPE"][$database][$table][$field])){
                if($GLOBALS["__FIELD_TYPE"][$database][$table][$field]<>null){return $GLOBALS["__FIELD_TYPE"][$database][$table][$field];}
            }
        }
        $sql="SHOW FULL FIELDS FROM $table WHERE Field='$field';";
        $ligne=@mysqli_fetch_array($this->QUERY_SQL($sql,$database));
        $GLOBALS["__FIELD_TYPE"][$database][$table][$field]=strtolower($ligne["Type"]);
        return strtolower($ligne["Type"]);
    }



    public FUNCTION CREATE_DATABASE($database){
        $EnableMySQL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMySQL"));
        if($EnableMySQL==0){
            $GLOBALS["SQUID_BD_STOP_PROCESSSING"]=true;
            return false;
        }
        if(isset($GLOBALS["SQUID_BD_STOP_PROCESSSING"])){if($GLOBALS["SQUID_BD_STOP_PROCESSSING"]){return false;}}
        if($GLOBALS["VERBOSE"]){echo " -> ->CREATE_DATABASE($database)<br>\n";}
        $this->mysql_password=trim($this->mysql_password);

        if(!$this->BD_CONNECT()){
            writelogs("CREATE_DATABASE Connection failed",__FUNCTION__."/".__CLASS__,__FILE__,__LINE__);
            return false;
        }


        if($GLOBALS["VERBOSE"]){echo "mysqli_query(mysqli_connection,CREATE DATABASE `$database`\n";}
        $results=mysqli_query($this->mysqli_connection,"CREATE DATABASE `$database`");
        if(@mysqli_error($this->mysqli_connection)){
            $time=date('h:i:s');
            $errnum=@mysqli_errno($this->mysqli_connection);
            $des=@mysqli_error($this->mysqli_connection);
            if(preg_match("#database exists#", $des)){$this->ok=true;return true;}
            $this->mysql_error="CREATE DATABASE $database -> Error Number ($errnum) ($des)";
            writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            return false;
        }

        $this->ok=true;
        return true;
    }

    public function StripBadChars_hostname($value){
        $value=trim(strtolower($value));
        $value=$this->replace_accents($value);
        $value=str_replace("$", "", $value);
        $value=str_replace(" ", "", $value);
        $value=str_replace("/", "_", $value);
        $value=str_replace("\\", "_", $value);
        $value=str_replace("#", "", $value);
        $value=str_replace("\"", "", $value);
        $value=str_replace("'", "`", $value);
        $value=str_replace("!", "", $value);
        $value=str_replace(";", "", $value);
        $value=str_replace(",", "", $value);
        $value=str_replace(":", "", $value);
        $value=str_replace("%", "", $value);
        $value=str_replace("*", "", $value);
        $value=str_replace("(", "", $value);
        $value=str_replace("[", "", $value);
        $value=str_replace("{", "", $value);
        $value=str_replace(")", "", $value);
        $value=str_replace("]", "", $value);
        $value=str_replace("}", "", $value);
        $value=str_replace("|", "", $value);
        $value=str_replace("&", "", $value);
        $value=str_replace("+", "", $value);
        $value=str_replace("=", "", $value);
        $value=str_replace("@", "", $value);
        $value=str_replace("£", "", $value);
        $value=str_replace("€", "", $value);
        $value=str_replace("----", "-", $value);
        $value=str_replace("---", "-", $value);
        $value=str_replace("--", "-", $value);
        $value=str_replace("--", "-", $value);
        $value=str_replace("--", "-", $value);
        $value=str_replace("__", "_", $value);
        $value=str_replace("__", "_", $value);
        $value=str_replace("__", "_", $value);
        $value=str_replace("__", "_", $value);

        return $value;

    }

    private function replace_accents($s) {
        $s = htmlentities($s);$s = preg_replace ('/&([a-zA-Z])(uml|acute|grave|circ|tilde|cedil|ring);/', '$1', $s);$s=str_replace("&Ntilde;","N",$s);$s=str_replace("&ntilde;","n",$s);$s=str_replace("&Oacute;","O",$s);$s=str_replace("&oacute;","O",$s);$s=str_replace("&Ograve;","O",$s);$s=str_replace("&ograve;","o",$s);$s=str_replace("&Ocirc;","O",$s);$s=str_replace("&ocirc;","o",$s);$s=str_replace("&Ouml;","O",$s);$s=str_replace("&ouml;","o",$s);$s=str_replace("&Otilde;","O",$s);$s=str_replace("&otilde;","o",$s);$s=str_replace("&Oslash;","O",$s);$s=str_replace("&oslash;","o",$s);$s=str_replace("&szlig;","b",$s);$s=str_replace("&Thorn;","T",$s);$s=str_replace("&thorn;","t",$s);$s=str_replace("&Uacute;","U",$s);$s=str_replace("&uacute;","u",$s);$s=str_replace("&Ugrave;","U",$s);$s=str_replace("&ugrave;","u",$s);$s=str_replace("&Ucirc;","U",$s);$s=str_replace("&ucirc;","u",$s);$s=str_replace("&Uuml;","U",$s);$s=str_replace("&uuml;","u",$s);$s=str_replace("&Yacute;","Y",$s);$s=str_replace("&yacute;","y",$s);$s=str_replace("&yuml;","y",$s);$s=str_replace("&Icirc;","I",$s);$s=str_replace("&icirc;","i",$s);$s = html_entity_decode($s);return $s;
    }

    public function CreateHyperCacheTables(){}
    public function move_category($orginal_md5,$category,$nextCategory){}
    public function move_to_unknown($orginal_md5,$category){}


    public FUNCTION TLSE_CONVERTION($officiels=false){
        $f["agressif"]="aggressive";
        $f["audio-video"]="audio-video";
        $f["youtube"]="youtube";
        $f["celebrity"]="celebrity";
        $f["cleaning"]="cleaning";
        $f["dating"]="dating";
        $f["filehosting"]="filehosting";
        $f["gambling"]="gamble";
        $f["hacking"]="hacking";
        $f["liste_bu"]="liste_bu";
        $f["manga"]="manga";
        $f["mobile-phone"]="mobile-phone";
        $f["press"]="news";
        $f["radio"]="webradio";
        $f["translation"]="translators";
        $f["bitcoin"]="paytosurf";
        $f["violence"]="violence";
        $f["drugs"]="drugs";
        $f["redirector"]="proxy";
        $f["sexual_education"]="sexual_education";
        $f["sports"]="recreation/sports";
        $f["tricheur"]="tricheur";
        $f["webmail"]="webmail";
        $f["adult"]="porn";
        $f["arjel"]="arjel";
        $f["bank"]="finance/banking";
        $f["chat"]="chat";
        $f["cooking"]="hobby/cooking";
        $f["drogue"]="drugs";
        $f["financial"]="financial";
        $f["games"]="games";
        $f["jobsearch"]="jobsearch";
        $f["marketingware"]="marketingware";
        $f["phishing"]="phishing";
        $f["remote-control"]="remote-control";
        $f["shopping"]="shopping";
        $f["strict_redirector"]="strict_redirector";
        $f["astrology"]="astrology";
        $f["blog"]="blog";
        $f["child"]="children";
        $f["dangerous_material"]="dangerous_material";
        $f["forums"]="forums";
        $f["lingerie"]="sex/lingerie";
        $f["malware"]="malware";
        $f["mixed_adult"]="mixed_adult";
        $f["publicite"]="publicite";
        $f["reaffected"]="reaffected";
        $f["sect"]="sect";
        $f["social_networks"]="socialnet";
        $f["strong_redirector"]="strong_redirector";
        $f["warez"]="warez";
        $f["verisign"]="sslsites";

        if(!$officiels){
            $f["aggressive"]="aggressive";
            $f["children"]="children";
            $f["drugs"]="drugs";
            $f["finance_banking"]="finance/banking";
            $f["gamble"]="gamble";
            $f["hobby_cooking"]="hobby/cooking";
            $f["porn"]="porn";
            $f["proxy"]="proxy";
            $f["recreation_sports"]="recreation/sports";
            $f["sex_lingerie"]="sex/lingerie";
            $f["socialnet"]="socialnet";
            $f["webradio"]="webradio";
        }



        return $f;

    }


    public function LIST_TABLES_ARTICA_SQUIDLOGS(){}


    public function LIST_TABLES_DAYS(){}

    public function LIST_TABLES_FAMILY(){}

    public function COUNT_ALL_TABLES(){

        $sql="SELECT COUNT(*) as tcount, (SUM(`INDEX_LENGTH`)+ SUM(`DATA_LENGTH`)) as x FROM information_schema.tables WHERE table_schema = 'squidlogs'";
        $ligne=@mysqli_fetch_array($this->QUERY_SQL($sql));
        return array($ligne["tcount"],$ligne["x"]);
    }


    public function LIST_TABLES_DAYS_BLOCKED(){}
    public function LIST_TABLES_MEMBERS(){}
    public function LIST_TABLES_MEMBERS_MONTH(){}
    public function LIST_TABLES_GCACHE(){}
    public function LIST_TABLES_GSIZE(){}
    public function LIST_TABLES_CACHED_RATED(){}
    public function LIST_TABLES_WWWUID(){}


    public function HIER_TIME(){
        $sql="SELECT DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 1 DAY),'%Y-%m-%d') as tdate";
        $ligne=mysqli_fetch_array($this->QUERY_SQL($sql));
        return strtotime($ligne["tdate"]." 00:00:00");
    }

    public function HIER(){
        $sql="SELECT DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 1 DAY),'%Y-%m-%d') as tdate";
        $ligne=mysqli_fetch_array($this->QUERY_SQL($sql));
        return $ligne["tdate"];
    }


    public function ACCOUNTS_ISP(){}



    public function categorize($www,$category,$noprocess=false){
        if(trim($www)==null){return false;}
        $category=intval($category);
        if($category==0){
            $this->mysql_error="Bad category '0'";
            return false;

        }
        if(preg_match("#^(http|ftp):#", $www)){
            $array=parse_url($www);
            $www=$array["host"];
            if(strpos($www, ":")>0){$t=explode(":", $www);$www=$t[0];}
        }

        $www=str_replace("^","",$www);
        if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
        if(preg_match("#^(.+?)\?#", $www,$re)){$www=$re[1];}
        if(preg_match("#^(.+?)\/#", $www,$re)){$www=$re[1];}
        if(strpos(" $www", "/")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}$this->mysql_error="$www Bad pattern";return false;}
        if(strpos(" $www", ";")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}$this->mysql_error="$www Bad pattern";return false;}
        if(strpos(" $www", ",")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}$this->mysql_error="$www Bad pattern";return false;}
        if(strpos(" $www", "$")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}$this->mysql_error="$www Bad pattern";return false;}
        if(strpos(" $www", "%")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}$this->mysql_error="$www Bad pattern";return false;}
        if(strpos(" $www", "!")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}$this->mysql_error="$www Bad pattern";return false;}
        if(strpos(" $www", "&")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}$this->mysql_error="$www Bad pattern";return false;}
        if(strpos(" $www", "<")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}$this->mysql_error="$www Bad pattern";return false;}
        if(strpos(" $www", ">")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}$this->mysql_error="$www Bad pattern";return false;}
        if(strpos(" $www", "[")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}$this->mysql_error="$www Bad pattern";return false;}
        if(strpos(" $www", "]")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}$this->mysql_error="$www Bad pattern";return false;}
        if(strpos(" $www", "(")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}$this->mysql_error="$www Bad pattern";return false;}
        if(strpos(" $www", ")")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}$this->mysql_error="$www Bad pattern";return false;}
        if(strpos(" $www", "+")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}$this->mysql_error="$www Bad pattern";return false;}
        if(strpos(" $www", "?")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}$this->mysql_error="$www Bad pattern";return false;}
        $www=trim(strtolower($www));
        if(function_exists("idn_to_ascii")){$www = @idn_to_ascii($www, "UTF-8");}

        $category_table=$this->GetCategoryTable($category);

        if(!$noprocess){
            $qz=new mysql_catz();
            $old_category_id=intval($qz->GET_CATEGORIES($www));
            if($old_category_id>9999){$old_category_id=0;}
            if($old_category_id>0){
                $this->last_id=$old_category_id;
                $this->mysql_error="Already categorized in category $old_category_id (".$qz->CategoryIntToStr($old_category_id).")";
                return false;
            }
        }



        $this->finaldomain=$www;
        $sql_add2="INSERT INTO $category_table (sitename) VALUES('$www') ON CONFLICT DO NOTHING";


        $q=new postgres_sql();
        $q->QUERY_SQL($sql_add2);
        if(!$q->ok){$this->mysql_error=$q->mysql_error;return false;}
        $q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='$www'");
        $q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite LIKE '%.$www'");


        $ligne2=pg_fetch_array($q->QUERY_SQL("SELECT count(*) as tcount FROM $category_table"));
        $CountOfLines=$ligne2["tcount"];
        $q->QUERY_SQL("UPDATE personal_categories SET items='$CountOfLines' WHERE category_id='$category'");
        return true;


    }

    public function GetCategoryTable($CategoryInt){
        $q=new postgres_sql();
        $ligne=$q->mysqli_fetch_array("SELECT categorytable FROM personal_categories WHERE category_id=$CategoryInt");
        return $ligne["categorytable"];

    }




    public function CheckReportTable(){}
    public function check_hypercacheHour($table=null){}
    public function TablePrimaireCacheHour($prefix=null,$nomem=false,$table=null){}
    public function check_SearchWords_hour($timekey=null,$table=null){}
    public function check_SearchWords_day($timekey=null){}
    public function check_SearchWords_week($timekey=null){}
    public function check_youtube_hour($timekey=null){}
    public function check_quota_hour_tmp($timekey=null,$table=null){}
    public function check_nginx_attacks_RT($timekey=null){}
    public function check_nginx_attacks_DAY($timekey=null){}
    public function check_quota_day($timekey=null){}
    public function check_quota_month($timekey=null){}
    public function check_youtube_day($timekey=null){}
    public function createWeekYoutubeTable($week=null){}
    public function CheckTablesBlocked_day($time=0,$tableblock=null){}
    public function create_webfilters_categories_caches($nofill=false){}

    public function Hotspot_SessionActive($array){}

    private  function file_time_min($path){
        if(!is_dir($path)){
            if(!is_file($path)){return 100000;}
        }
        $last_modified = filemtime($path);
        $data1 = $last_modified;
        $data2 = time();
        $difference = ($data2 - $data1);
        return round($difference/60);
    }



    public function check_hotspot_tables(){
        $this->CreateHyperCacheTables();
    }






    public function CheckTables($table=null,$force=false){
        if(isset($GLOBALS[__CLASS__]["FAILED"])){
            writelogs("Global connection is failed, aborting",__FUNCTION__,__FILE__,__LINE__);
            $this->ok=false;return false;}
        $md5=md5("CheckTables($table)");
        if(isset($GLOBALS[$md5])){return;}
        $GLOBALS[$md5]=true;

        if($this->EnableRemoteStatisticsAppliance==1){return;}

        if(!$force){
            if($GLOBALS["AS_ROOT"]){
                if(!$GLOBALS["VERBOSE"]){
                    if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
                    $unix=new unix();
                    $timefile="/etc/artica-postfix/pids/".basename(__FILE__).".time";
                    $XT_TIME=$this->file_time_min($timefile);
                    if($XT_TIME<30){return true;}
                    @unlink($timefile);
                    @file_put_contents($timefile,time());
                    $this->ToSyslog("Verify MySQL Tables ( as root) for database $this->database last check since {$XT_TIME}Mn");
                }
            }



            if(!$GLOBALS["AS_ROOT"]){
                if(!$GLOBALS["VERBOSE"]){
                    $timefile=PROGRESS_DIR."/".basename(__FILE__).".time";
                    $XT_TIME=$this->file_time_min($timefile);
                    if($XT_TIME<30){return true;}
                    $this->ToSyslog("Verify MySQL Tables ( as {$_SESSION["uid"]} ) for database $this->database last check since {$XT_TIME}Mn");
                    @unlink($timefile);
                    @file_put_contents($timefile,time());
                }
            }
        }


        if(!$this->DATABASE_EXISTS($this->database)){$this->CREATE_DATABASE($this->database);}
        if(isset($GLOBALS[__CLASS__]["FAILED"])){return false;}


        $this->create_webfilters_categories_caches();





        if($this->TABLE_EXISTS("webfilters_schedules",$this->database)){
            if(!$this->FIELD_EXISTS("webfilters_schedules","Params",$this->database)){
                $this->QUERY_SQL("ALTER TABLE `webfilters_schedules` ADD `Params` TEXT NOT NULL");
            }
        }







        if($table<>null){
            if(!$this->FIELD_EXISTS($table,"uid",$this->database)){
                $sql="ALTER TABLE `$table` ADD `uid` VARCHAR( 128 ) NOT NULL,ADD INDEX ( uid )";
                if(!$this->ok){
                    writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
                    $this->mysql_error=$this->mysql_error."\n$sql";
                }
                $this->QUERY_SQL($sql,$this->database);
            }
        }







        $sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`hypercache_white` (
			`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			`zdate` datetime NOT NULL,
			`type` smallint(1) NOT NULL DEFAULT 0,
			`domain` VARCHAR( 255 ) NOT NULL ,
			UNIQUE KEY ( `domain` )  )  ENGINE = MYISAM;";
        $this->QUERY_SQL($sql,$this->database);


        $sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`ufdbunlock` (
			`md5` VARCHAR( 90 ) NOT NULL ,
			`logintime` BIGINT UNSIGNED ,
			`finaltime` INT UNSIGNED ,
			`uid` VARCHAR(128) NOT NULL,
			`MAC` VARCHAR( 90 ) NULL,
			`www` VARCHAR( 128 ) NOT NULL ,
			`ipaddr` VARCHAR( 128 ) ,
			PRIMARY KEY ( `md5` ) ,
			KEY `MAC` (`MAC`),
			KEY `logintime` (`logintime`),
			KEY `finaltime` (`finaltime`),
			KEY `uid` (`uid`),
			KEY `www` (`www`),
			KEY `ipaddr` (`ipaddr`)
			)  ENGINE = MYISAM;";

        $this->QUERY_SQL($sql,$this->database);



        $sql="CREATE TABLE IF NOT EXISTS `UsersAgentsDB` (
				`explain` VARCHAR(255),
				`editor` VARCHAR( 90 ) NOT NULL,
				`pattern` VARCHAR(60) PRIMARY KEY,
				`bypass` smallint(1) NOT NULL DEFAULT 1,
				`deny` smallint(1) NOT NULL DEFAULT 0,
				`enabled` smallint(1) NOT NULL DEFAULT 1,
				 KEY `bypass` (`bypass`),
				 KEY `enabled` (`enabled`),
				 KEY `editor` (`editor`)
				 )  ENGINE = MYISAM;
			";
        $this->QUERY_SQL($sql,$this->database);

        $sql="CREATE TABLE IF NOT EXISTS `cicap_profiles` (
				`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				 `rulename` VARCHAR(90) NOT NULL,
				 `blacklist` smallint(2) NOT NULL,
				 `whitelist` smallint(2) NOT NULL,
				 `enabled` smallint(1) NOT NULL,
				 KEY `rulename` (`rulename`),
				 KEY `blacklist` (`blacklist`),
				 KEY `whitelist` (`whitelist`),
				 KEY `enabled` (`enabled`)
				)  ENGINE = MYISAM AUTO_INCREMENT = 5;
			";
        $this->QUERY_SQL($sql,$this->database);


        $sql="CREATE TABLE IF NOT EXISTS `ss5_fw` (
		`ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`zorder` INT( 5 ) NOT NULL,
		`mode` smallint(1) NOT NULL DEFAULT 0,
		`enabled` smallint(1) NOT NULL DEFAULT 1,
		`src_host` VARCHAR(128),
		`src_port` BIGINT UNSIGNED,
		`dst_host` VARCHAR(128),
		`dst_port` BIGINT UNSIGNED,
		`fixup` varchar(20) NULL,
		`group` VARCHAR(128),
		`bandwitdh` BIGINT UNSIGNED,
		`expdate` VARCHAR(40) NULL,
		KEY `zorder` (`zorder`),
		KEY `mode` (`mode`),
		KEY `enabled` (`enabled`),
		KEY `src_host` (`src_host`),
		KEY `dst_host` (`dst_host`)
		) ENGINE=MYISAM;";
        $this->QUERY_SQL($sql,$this->database);







        $sql="CREATE TABLE IF NOT EXISTS `ident_networks` (
				network_item VARCHAR(128) NOT NULL PRIMARY KEY
			)  ENGINE = MYISAM;";
        $this->QUERY_SQL($sql,$this->database);








        $sql="CREATE TABLE IF NOT EXISTS `webfilter_aclsdynamic` (
				  	`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
					`type` smallint(1) NOT NULL,
					`value` VARCHAR(255) NOT NULL,
					`enabled` smallint(1) NOT NULL DEFAULT '1' ,
					`gpid` INT(10) NOT NULL DEFAULT '0' ,
					`description` VARCHAR(255) NOT NULL,
					`who` VARCHAR(128) NOT NULL,
				  	KEY `type` (`type`),
				  	KEY `value` (`value`),
				  	KEY `enabled` (`enabled`),
					KEY `who` (`who`)
				)  ENGINE = MYISAM;";
        $this->QUERY_SQL($sql,$this->database);
        if(!$this->ok){writelogs("$this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}

        $sql="CREATE TABLE IF NOT EXISTS `webfilter_aclsdynlogs` (
				  	`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
					`zDate` DATETIME NOT NULL,
					`gpid` INT(10) NOT NULL DEFAULT '0' ,
					`events` VARCHAR(255) NOT NULL,
					`who` VARCHAR(128) NOT NULL,
				  	KEY `zDate` (`zDate`),
				  	KEY `gpid` (`gpid`),
					KEY `who` (`who`)
				)  ENGINE = MYISAM;";
        $this->QUERY_SQL($sql,$this->database);
        if(!$this->ok){writelogs("$this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}







        if(!$this->FIELD_EXISTS("webfilter_aclsdynamic", "maxtime")){
            $this->QUERY_SQL("ALTER TABLE `webfilter_aclsdynamic` ADD `maxtime` INT UNSIGNED ,
					ADD INDEX ( `maxtime` )");
        }
        if(!$this->FIELD_EXISTS("webfilter_aclsdynamic", "duration")){
            $this->QUERY_SQL("ALTER TABLE `webfilter_aclsdynamic` ADD `duration` INT UNSIGNED ,
					ADD INDEX ( `duration` )");
        }










        if(!$this->TABLE_EXISTS('webtests',$this->database)){
            $sql="CREATE TABLE `squidlogs`.`webtests` (
			`sitename` VARCHAR( 255 ) NOT NULL PRIMARY KEY ,
			`category` VARCHAR( 128 ) NOT NULL ,
			`family` VARCHAR( 128 ) NOT NULL,
			`Country` VARCHAR( 50 ) NOT NULL,
			`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
			`ipaddr` VARCHAR(50) NOT NULL ,
			`SiteInfos` TEXT NOT NULL ,
			`checked` SMALLINT( 1 ) NOT NULL ,
			 KEY `sitename` (`sitename`),
			 KEY `category` (`category`),
			 KEY `Country` (`Country`),
			 KEY `checked` (`checked`),
			 KEY `family` (`family`),
			 KEY `ipaddr` (`ipaddr`),
			 KEY `zDate` (`zDate`)
			)  ENGINE = MYISAM;";

            $this->QUERY_SQL($sql,$this->database);
        }else{
            if(!$this->FIELD_EXISTS("webtests", "Country")){
                $this->QUERY_SQL("ALTER TABLE `webtests` ADD `Country`  VARCHAR( 50 ) NOT NULL ,ADD INDEX ( `Country` )");
            }
        }






        $sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`rdpproxy_users` (
			`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`username` VARCHAR(128),
			`password` VARCHAR(128),
			 KEY `username`(`username`),
			 KEY `password`(`password`)
			 )  ENGINE = MYISAM;";
        $this->QUERY_SQL($sql,$this->database);






        $sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`rdpproxy_items` (
			`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`userid` BIGINT(11),
			`service` VARCHAR(128) ,
			`rhost` VARCHAR(255),
			`username` VARCHAR(128),
			`domain` VARCHAR(255),
			`password` VARCHAR(128),
			`servicetype` VARCHAR(15),
			`serviceport` smallint(15),
			`alive` INT UNSIGNED NOT NULL,
			`is_rec` smallint(1),
			 KEY `username`(`username`),
			 KEY `password`(`password`),
			 KEY `service`(`service`),
			 KEY `rhost`(`rhost`),
			 KEY `userid`(`userid`)
			 )  ENGINE = MYISAM;";
        $this->QUERY_SQL($sql,$this->database);

        if(!$this->FIELD_EXISTS("rdpproxy_items", "domain")){
            $this->QUERY_SQL("ALTER TABLE `rdpproxy_items` ADD `domain`  VARCHAR(255)");
        }
        $sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`main_cache_rules` (
			`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`rulename` VARCHAR(128),
			`zorder` smallint(2) NOT NULL DEFAULT 0,
			`enabled` smallint(1) NOT NULL DEFAULT 1,
			 KEY `rulename`(`rulename`),
			 KEY `zorder`(`zorder`)
			 )  ENGINE = MYISAM;";
        $this->QUERY_SQL($sql,$this->database);


        $sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`main_cache_dyn` (
			`familysite` VARCHAR(128) PRIMARY KEY,
			`enabled` smallint(1) NOT NULL DEFAULT 1,
			`level` smallint(2) NOT NULL DEFAULT 5,
			`zDate` DATETIME,
			 KEY `familysite`(`familysite`),
			 KEY `enabled`(`enabled`),
			 KEY `zDate`(`zDate`)
			 )  ENGINE = MYISAM;";
        $this->QUERY_SQL($sql,$this->database);

        if(!$this->FIELD_EXISTS("main_cache_dyn", "OnlyImages")){
            $this->QUERY_SQL("ALTER TABLE `main_cache_dyn` ADD `OnlyImages`  smallint( 1 ) DEFAULT '0'");
        }
        if(!$this->FIELD_EXISTS("main_cache_dyn", "OnlyeDoc")){
            $this->QUERY_SQL("ALTER TABLE `main_cache_dyn` ADD `OnlyeDoc`  smallint( 1 ) DEFAULT '0'");
        }
        if(!$this->FIELD_EXISTS("main_cache_dyn", "OnlyFiles")){
            $this->QUERY_SQL("ALTER TABLE `main_cache_dyn` ADD `OnlyFiles`  smallint( 1 ) DEFAULT '0'");
        }
        if(!$this->FIELD_EXISTS("main_cache_dyn", "OnlyMultimedia")){
            $this->QUERY_SQL("ALTER TABLE `main_cache_dyn` ADD `OnlyMultimedia`  smallint( 1 ) DEFAULT '0'");
        }

        $sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`cache_rules` (
			`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`ruleid` INT UNSIGNED NOT NULL,
			`rulename` VARCHAR(128),
			`min` smallint(50) NOT NULL,
			`max` smallint(50) NOT NULL,
			`perc` smallint(2) NOT NULL DEFAULT 20,
			`zorder` smallint(2) NOT NULL DEFAULT 0,
			`GroupType` smallint(1) NOT NULL DEFAULT 1,
			`enabled` smallint(1) NOT NULL DEFAULT 1,
			 KEY `rulename`(`rulename`),
			 KEY `zorder`(`zorder`)
			 )  ENGINE = MYISAM;";
        $this->QUERY_SQL($sql,$this->database);


        $sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`cache_rules_items` (
			`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`zMD5` VARCHAR(90) NOT NULL,
			`ruleid` INT UNSIGNED NOT NULL,
			`item` VARCHAR(255),
			`zorder` smallint(2) NOT NULL DEFAULT 0,
			`enabled` smallint(1) NOT NULL DEFAULT 1,
			 UNIQUE KEY `zMD5`(`zMD5`),
			 KEY `item`(`item`),
			 KEY `zorder`(`zorder`),
			 KEY `ruleid`(`ruleid`)
			 )  ENGINE = MYISAM;";
        $this->QUERY_SQL($sql,$this->database);


        $sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`cache_rules_options` (
			`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`zMD5` VARCHAR(90) NOT NULL,
			`ruleid` INT UNSIGNED NOT NULL,
			`option` VARCHAR(40),
			`enabled` smallint(1) NOT NULL DEFAULT 1,
			 UNIQUE KEY `zMD5`(`zMD5`),
			 KEY `option`(`option`),
			 KEY `ruleid`(`ruleid`)
			 )  ENGINE = MYISAM;";
        $this->QUERY_SQL($sql,$this->database);


        $sql="CREATE TABLE IF NOT EXISTS `webauth_rules` (
			`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`rulename` varchar(128) NOT NULL,
			`enabled` smallint(1) NOT NULL,
			 PRIMARY KEY (`ID`),
			 KEY `rulename` (`rulename`),
			 KEY `enabled` (`enabled`)
			) ENGINE=MYISAM;";
        $this->QUERY_SQL($sql);



        $sql="CREATE TABLE IF NOT EXISTS `webauth_rules_browsers` (
			`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`ruleid` BIGINT UNSIGNED NOT NULL,
			`pattern` VARCHAR(128) NOT NULL,
			 PRIMARY KEY (`ID`),
			 KEY `ruleid` (`ruleid`)
		)  ENGINE = MYISAM;";
        $this->QUERY_SQL($sql);


        $sql="CREATE TABLE IF NOT EXISTS `webauth_settings` (
			`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`ruleid` BIGINT UNSIGNED NOT NULL,
			`MasterKey` VARCHAR(128) NOT NULL,
			`MasterValue` TEXT NULL,
			 PRIMARY KEY (`ID`),
			 KEY `ruleid` (`ruleid`),
			 KEY `MasterKey` (`MasterKey`)
		)  ENGINE = MYISAM;";
        $this->QUERY_SQL($sql);



        if(!$this->TABLE_EXISTS('RegexCatz',$this->database)){
            $sql="CREATE TABLE `squidlogs`.`RegexCatz` (
			`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`zMD5` VARCHAR( 90 ) NOT NULL,
			`RegexPattern` VARCHAR( 255 ) NOT NULL,
			`category` VARCHAR( 90 ) NOT NULL,
			`enabled` TINYINT( 1 ) NOT NULL,
			`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			INDEX (`enabled`,`zDate`),
			KEY `zMD5`(`zMD5`),
			KEY `category`(`category`)
			 
			)  ENGINE = MYISAM;";

            $this->QUERY_SQL($sql,$this->database);
        }


        if(!$this->FIELD_EXISTS("webtests", "checked")){
            $this->QUERY_SQL("ALTER TABLE `webtests` ADD `checked`  SMALLINT( 1 ) NOT NULL ,ADD INDEX ( `checked` )");
        }

        if(!$this->TABLE_EXISTS('websites_caches_params',$this->database)){
            $sql="CREATE TABLE `squidlogs`.`websites_caches_params` (
			`sitename` VARCHAR(255) NOT NULL PRIMARY KEY,
			`MIN_AGE` INT( 10 ) NOT NULL,
			`PERCENT` INT( 10 ) NOT NULL,
			`MAX_AGE` INT( 10 ) NOT NULL,
			`options` TINYINT(1 ) NOT NULL,
			INDEX ( `MIN_AGE` , `PERCENT` , `MAX_AGE`,`options`)
			)  ENGINE = MYISAM;";

            $this->QUERY_SQL($sql,$this->database);
        }



        if(!$this->FIELD_EXISTS("webtests", "family")){$this->QUERY_SQL("ALTER TABLE `webtests` ADD `family` VARCHAR( 128 ) NOT NULL,ADD INDEX (`family`)");}
        if(!$this->FIELD_EXISTS("webtests", "zDate")){$this->QUERY_SQL("ALTER TABLE `webtests` ADD `zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,ADD INDEX (`zDate`)");}
        if(!$this->FIELD_EXISTS("webtests", "ipaddr")){$this->QUERY_SQL("ALTER TABLE `webtests` ADD `ipaddr` VARCHAR(50) NOT NULL,ADD INDEX (`ipaddr`)");}
        if(!$this->FIELD_EXISTS("webtests", "SiteInfos")){$this->QUERY_SQL("ALTER TABLE `webtests` ADD `SiteInfos`  TEXT NOT NULL");}







        $sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`portals` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`PortalName` VARCHAR( 255 ) NOT NULL ,
			`ListenInterface` VARCHAR(20) NOT NULL ,
			`enabled` SMALLINT( 1 ) NOT NULL ,
			INDEX ( `PortalName` , `enabled`,`ListenInterface`)
			)  ENGINE = MYISAM;";

        $this->QUERY_SQL($sql,$this->database);


        $sql="CREATE TABLE IF NOT EXISTS `cicap_profiles_blks` (
				   `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				    mainid INT(3) NOT NULL,
				  	bltype smallint(1) NOT NULL,
				  	category VARCHAR(128) NOT NULL,
				  KEY `mainid` (`mainid`),
				  KEY `category` (`category`),
				  KEY `bltype` (`bltype`)
				)  ENGINE = MYISAM;";
        $this->QUERY_SQL($sql,$this->database);


        $sql="CREATE TABLE IF NOT EXISTS `cicap_rules` (
				   `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				    rulename VARCHAR(128) NOT NULL,
				  	GroupType smallint(1) NOT NULL,
				  	ProfileID INT(10) NOT NULL,
					enabled smallint(1) NOT NULL,
				  KEY `rulename` (`rulename`),
				  KEY `GroupType` (`GroupType`),
				  KEY `enabled` (`enabled`)
				)  ENGINE = MYISAM;";
        $this->QUERY_SQL($sql,$this->database);



    }

    function COUNT_CATEGORIES(){}


    FUNCTION MAC_TO_NAME($MAC=null){
        if($MAC=="00:00:00:00:00:00"){return null;}
        if($MAC==null){return null;}
        include_once(dirname(__FILE__)."/class.tcpip.inc");
        $ip=new IP();
        $tt=array();
        if(!$ip->IsvalidMAC($MAC)){return null;}
        return $this->UID_FROM_ALL($MAC);
    }

    public function PostedServerToHost($posteddata){
        $posteddata=trim(strtolower($posteddata));
        if(preg_match("#^http(.*?):#", $posteddata)){
            $arrayURI=parse_url($posteddata);
            if(isset($arrayURI["host"])){$posteddata=$arrayURI["host"];}
        }

        if(preg_match("#^http(.*?):\/\/(.+)$#", $posteddata,$re)){
            $posteddata=$re[2];
        }

        if(preg_match("#^(.*?):([0-9]+)#", $posteddata,$re)){$posteddata=$re[1];}
        if(preg_match("#^www\.(.*?):([0-9]+)#", $posteddata,$re)){$posteddata=$re[1];}
        return $posteddata;

    }


    function WebsiteStrip($www){

        $www=trim(strtolower($www));
        if(preg_match("#^(http|ftp).*?:\/\/(.+)#i", $www,$re)){$www=$re[2];}
        if($www==null){return;}
        if(strpos($www, "/")>0){
            preg_match("#^(.+?)\/#", $www,$re);
            $www=$re[1];
        }


        $www=stripslashes($www);
        if($www==null){return;}

        $www=str_replace(";", ".", $www);
        if(preg_match("#href=\"(.+?)\">#", $www,$re)){$www=$re[1];}
        if(preg_match("#<a href.*?http://(.+?)([\/\"'>])#i",$www,$re)){$www=$re[1];}
        $www=str_replace("http://", "", $www);
        if(preg_match("#http.*?:\/\/(.+?)[\/\s]+#",$www,$re)){$www=$re[1];return $www;}
        if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
        $www=str_replace("<a href=", "", $www);
        if(strpos($www, "/")>0){$www=substr($www, 0,strpos($www, "/"));}
        if(preg_match("#\.php$#", $www,$re)){return;}
        if(!preg_match("#\.[a-z0-9]+$#",$www,$re)){return;}
        $www=trim(strtolower($www));
        $www=str_replace("\t", "", $www);
        $www=str_replace(chr(194),"",$www);
        $www=str_replace(chr(32),"",$www);
        $www=str_replace(chr(160),"",$www);
        return $www;
    }


    function ADD_CATEGORYZED_WEBSITE($sitename,$category){
        $category=trim($category);
        $sitename=$this->WebsiteStrip($sitename);
        if(trim($sitename)==null){return;}
        if(trim($category)==null){return;}
        if(strlen($sitename)<4){return;}

        $sock=new sockets();
        if(!isset($GLOBALS["MY_UUID"])){
            $GLOBALS["MY_UUID"]=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
        }
        $uuid=$GLOBALS["MY_UUID"];
        if(strpos($category, ",")>0){$categories=explode(",",$category);}else{$categories[]=$category;}
        foreach ($categories as $index=>$cat){
            $cat=trim($cat);
            $md5=md5("$cat$sitename");
            $category_table="category_".$this->category_transform_name($cat);
            if(!$this->TABLE_EXISTS($category_table)){
                writelogs("$category_table no such table",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return false;
            }

            $ligneX=mysqli_fetch_array($this->QUERY_SQL("SELECT zmd5 FROM $category_table WHERE pattern='$sitename'"));
            if($ligneX["zmd5"]<>null){continue;}
            $this->QUERY_SQL("INSERT IGNORE INTO $category_table (zmd5,zDate,category,pattern,uuid) VALUES('$md5',NOW(),'$cat','$sitename','$uuid')");
            if(!$this->ok){echo "categorize $sitename failed $this->mysql_error\n";return false;}

            $this->QUERY_SQL("INSERT IGNORE INTO categorize (zmd5,zDate,category,pattern,uuid) VALUES('$md5',NOW(),'$cat','$sitename','$uuid')");
            if(!$this->ok){echo $this->mysql_error."\n";return false;}

        }

        if(!isset($GLOBALS["export-community"])){
            $sock=new sockets();
            $sock->getFrameWork("cmd.php?export-community-categories=yes");
            $GLOBALS["export-community"]=true;
        }
        return true;
    }


    FUNCTION REMOVE_CATEGORIZED_SITENAME($sitename,$category){
        $table=null;
        if(preg_match("#category_(.+?)#",$category)){$table=$category;}
        if($table==null){$table="category_".$this->category_transform_name($category);}
        writelogs("UPDATE `$table` SET `enabled`=0 WHERE `pattern`='$sitename'",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
        $this->QUERY_SQL("UPDATE `$table` SET `enabled`=0 WHERE `pattern`='$sitename'");
        if(!$this->ok){echo $this->mysql_error;return;}
        $md5=md5("$category$sitename");
        $sql="INSERT IGNORE INTO categorize_delete (sitename,category,zmd5) VALUES ('$sitename','$category','$md5')";
        writelogs($sql,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
        $this->QUERY_SQL($sql);
        $sock=new sockets();
        $categories=$this->GET_CATEGORIES($sitename,true,true,true);
        $sql="UPDATE visited_sites SET category='$categories' WHERE sitename='$sitename'";
        writelogs($sql,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
        $this->QUERY_SQL($sql);
    }

    FUNCTION UID_FROM_ALL($pattern){
        include_once(dirname(__FILE__)."/class.postgres.inc");
        $q=new postgres_sql();
        $proxyalias=null;
        if(!isset($GLOBALS["TCP_CLASS"])){$GLOBALS["TCP_CLASS"]=new IP();}
        if($GLOBALS["TCP_CLASS"]->IsvalidMAC($pattern)){
            $ligne=pg_fetch_array($q->QUERY_SQL("SELECT proxyalias FROM hostsnet WHERE mac='$pattern'"));
            if(isset($ligne["proxyalias"])){$proxyalias=$ligne["proxyalias"];}
            if($proxyalias<>null){return $proxyalias;}

        }
        if($GLOBALS["TCP_CLASS"]->isValid($pattern)){
            $ligne=pg_fetch_array($q->QUERY_SQL("SELECT proxyalias FROM hostsnet WHERE ipaddr='$pattern'"));
            if(isset($ligne["proxyalias"])){$proxyalias=$ligne["proxyalias"];}
            if($proxyalias<>null){return $proxyalias;}
        }


    }
    function GET_FULL_CATEGORIES($sitename){

        $cat[]=$this->GET_CATEGORIES_DB($sitename);

        if(!is_array($cat)){return null;}
        foreach ($cat as $index=>$categories){
            if(strpos($categories, ",")>0){
                $f=explode(",",$categories); foreach ($f as $a=>$b){ $category[]=$b; }
                continue;
            }
            $category[]=$categories;
        }
        foreach ($category as $index=>$categories){
            $CLEANED[$categories]=$categories;
        }
        foreach ($CLEANED as $index=>$categories){
            $F_CATZ[]=$categories;
        }
        $final=@implode(",", $F_CATZ);
        $final=str_replace(",,", ",", $final);
        return $final;

    }

// ***************************************************************************************************************
    function GET_CATEGORIES($sitename,$nocache=false,$nok9=false,$noheuristic=false,$noArticaDB=false){

        $catz=new mysql_catz();
        return $catz->GET_CATEGORIES($sitename);
    }
    private function GET_CATEGORIES_DB($sitename){
        $pagename=CurrentPageName();
        $t=time();
        $qz=new mysql_catz();


        $catz=$qz->GET_CATEGORIES($sitename);


        if($GLOBALS["VERBOSE"]){$took=distanceOfTimeInWords($t,time(),true); echo "qz->GET_CATEGORIES_DB($sitename) = $catz took $took<br>\n";}
        if($catz==null){return;}

        if(!isset($GLOBALS["ARTICADB"])){$GLOBALS["ARTICADB"]=0;}
        $GLOBALS["ARTICADB"]++;
        $GLOBALS["CATZWHY"]="INTERNAL-CATZ";
        return trim($catz);

    }
// ***************************************************************************************************************
    public function GetFamilySites($sitename){
        if(isset($GLOBALS["GetFamilySites"][$sitename])){return $GLOBALS["GetFamilySites"][$sitename];}
        if(!class_exists("squid_familysite")){include_once(dirname(__FILE__)."/class.squid.familysites.inc");}
        $fam=new squid_familysite();
        $GLOBALS["GetFamilySites"][$sitename]=$fam->GetFamilySites($sitename);
        return $GLOBALS["GetFamilySites"][$sitename];
    }


    private function ExtractAllUris($content){
        $matches=array();
        if(!preg_match_all("/a[\s]+[^>]*?href[\s]?=[\s\"\']+(.*?)[\"\']+.*?>"."([^<]+|.*?)?<\/a>/",$content, $matches)){return array();}
        $matches = $matches[1];
        foreach($matches as $var){
            $array=parse_url($var);
            if(isset($array["host"])){
                if(preg_match("#^www\.(.+)#", $array["host"],$re)){$array["host"]=$re[1];}
                $array[$array["host"]]=$array["host"];
            }

        }

        return $array;
    }


    private function already_Cats($www){
        $array[]="addthis.com";
        $array[]="google.";
        $array[]="w3.org";
        $array[]="icra.org";
        $array[]="facebook.";
        foreach ($array as $wwws){
            $pattern=str_replace(".", "\.", $wwws);
            if(preg_match("#$pattern#", $www)){return true;}

        }
        return false;
    }
    private function free_categorizeSave_CDIR($ipaddr){

        $tt=explode(".",$ipaddr);
        $ipnew="{$tt[0]}.{$tt[1]}.{$tt[2]}";

        for($i=1;$i<255;$i++){
            $zip[]="$ipnew.$i";
        }


        return @implode("\n",$zip);
    }


    private function free_categorize_logs($logid,$text){
        if($logid==0){return;}
        $f = @fopen("/usr/share/artica-postfix/ressources/logs/web/$logid.log", 'a');
        @fwrite($f, "$text\n");
        @fclose($f);

    }
    private function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
    public function free_categorizeSave($PostedDatas=null,$category_id=0,$ForceCat=0,$ForceExt=0,$logid=0){
        include_once(dirname(__FILE__)."/class.html2text.inc");
        $sock=new sockets();


        $PostedDatasZ=explode("\n",$PostedDatas);
    foreach ($PostedDatasZ as $ligne){
            $ligne=trim($ligne);
            if($ligne==null){continue;}
            if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)\/24#", $ligne,$re)){
                $cdir="{$re[1]}-{$re[2]}-{$re[3]}.cdir";
                $PostedDatasZ1[]=$cdir;
                continue;
            }
            $PostedDatasZ1[]=$ligne;
        }
        $PostedDatas=@implode("\n", $PostedDatasZ1);
        $PostedDatasZ1=array();

        $f=array();
        $ExtractAllUris=$this->ExtractAllUris($PostedDatas);
        if(count($ExtractAllUris)>0){
            foreach ($ExtractAllUris as $num=>$ligne){$f[]=$num;}
            $PostedDatas=null;
        }

        $h2t =new html2text($PostedDatas);
        $h2t->get_text();

        foreach ($h2t->_link_array as $ligne){
            if(trim($ligne)==null){continue;}
            $ligne=strtolower($ligne);
            $ligne=str_replace("(whois)", "", $ligne);
            $ligne=str_replace("||", "", $ligne);
            $ligne=str_replace("^", "", $ligne);
            $ligne=trim($ligne);
            if(preg_match("#^([0-9\.]+):[0-9]+#", $ligne,$re)){
                $websitesToscan[]=$re[1];
                continue;
            }
            if(strpos(" $ligne", "http")==0){$ligne="http://$ligne";}
            $hostname=parse_url($ligne,PHP_URL_HOST);
            if(preg_match("#^www\.(.+)#", $hostname,$re)){$hostname=$re[1];}
            if(preg_match("#^\.(.+)#", $hostname,$re)){$hostname=$re[1];}
            if(preg_match("#^\*\.(.+)#", $hostname,$re)){$hostname=$re[1];}
            writelogs("$ligne = $hostname",__FUNCTION__,__FILE__,__LINE__);
            $websitesToscan[]=$ligne;
        }



        $PostedDatas=str_replace("<", "\n<", $PostedDatas);
        $PostedDatas=str_replace(' rel="nofollow"', "", $PostedDatas);
        $PostedDatas=str_replace("\r", "\n", $PostedDatas);
        $PostedDatas=str_replace("https:", "http:", $PostedDatas);
        if($PostedDatas<>null){$f=explode("\n",$PostedDatas );}


        if(!is_numeric($ForceExt)){$ForceExt=0;}
        if(!is_numeric($ForceCat)){$ForceCat=0;}
        $ipClass=new IP();
        foreach ($f as $www){
            $www=trim($www);
            if($www==null){continue;}
            if(preg_match("#--------------#", $www)){continue;}
            if(preg_match("#No extension#", $www)){continue;}
            if(preg_match("#no website#i", $www)){continue;}
            if(preg_match("#^analyze\s+[0-9]+\s+#", $www)){continue;}
            if(preg_match("#(false|true):\s+(.+?)\s+already#i", $www,$re)){$www=$re[2];}
            writelogs("Scanning $www",__FUNCTION__,__FILE__,__LINE__);
            if(preg_match("#^(.+?)\"\s+#", $www,$re)){$www=$re[1];}
            if(preg_match("#^([0-9\.]+):[0-9]+#", $www,$re)){$www=$re[1];}
            if(preg_match("#^\##", $www)){continue;}
            $www=str_replace("(whois)", "", $www);
            $www=str_replace("\r", "", $www);
            $www=str_replace("||", "", $www);
            $www=str_replace("^", "", $www);

            $www=trim(strtolower($www));

            if($ipClass->isValid($www)){$www=ip2long($www).".addr";$websitesToscan[]=$www;continue;}

            if($www==null){continue;}
            $www=stripslashes($www);
            if(preg_match("#href=\"(.+?)\">#", $www,$re)){$www=$re[1];}
            if(preg_match('#<a rel=.+?href="(.+?)"#', $www,$re)){$www=$re[1];}
            if(preg_match("#<a href.*?http://(.+?)([\/\"'>])#i",$www,$re)){$www=$re[1];}
            if(preg_match("#<span>www\.(.+?)\.([a-z]+)</span>#i",$www,$re)){$www=$re[1].".".$re[2];}
            $www=str_replace("http://", "", $www);
            if(preg_match("#\/\/.+?@(.+)#",$www,$re)){$websitesToscan[]=$re[1];}
            if(preg_match("#http.*?:\/\/(.+?)[\/\s]+#",$www,$re)){$websitesToscan[]=$re[1];continue;}
            if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
            $www=str_replace("<a href=", "", $www);
            $www=str_replace("<img src=", "", $www);
            $www=str_replace("title=", "", $www);
            if(preg_match("#^(.*?)\/#", $www,$re)){$www=$re[1];}
            if(preg_match("#\.php$#", $www,$re)){$this->free_categorize_logs($logid,"<span class='text-danger'>$www php script...</psan>");continue;}
            $www=str_replace("/", "", $www);
            $www=trim($www);
            if($ForceExt==0){
                if(!preg_match("#\.([a-z0-9]+)$#",$www,$re)){continue;}
                if(strlen($re[1])<2){
                    if(!is_numeric($re[1])){
                        $this->free_categorize_logs($logid,"<span class='text-danger'>$www bad extension `.{$re[1]}` [$ForceExt]</psan>");
                        continue;
                    }
                }
            }

            $www=str_replace('"', "", $www);
            $websitesToscan[]=$www;
        }

        foreach ($websitesToscan as $www){$cleaned[$www]=$www;}
        $websitesToscan=array();
        foreach ($cleaned as $num=>$www){$websitesToscan[]=$www;}


        foreach ($websitesToscan as $www){
            $www=strtolower($www);
            $www=replace_accents($www);
            if($www=="www"){continue;}
            if($www=="ssl"){continue;}
            $www=str_replace("http://", "", $www);
            $www=str_replace("https://", "", $www);
            $www=str_replace("ftp://", "", $www);
            $www=str_replace("ftps://", "", $www);
            if(preg_match("#.+?@(.+)#",$www,$ri)){$www=$ri[1];}
            if(preg_match("#^www\.(.+?)$#i",$www,$ri)){$www=$ri[1];}
            if($ForceCat==0){
                if($this->already_Cats($www)){continue;}
            }

            if(strpos($www, '"')>0){$www=substr($www, 0,strpos($www, '"'));}
            if(strpos($www, "'")>0){$www=substr($www, 0,strpos($www, "'"));}
            if(strpos($www, ">")>0){$www=substr($www, 0,strpos($www, ">"));}
            if(strpos($www, "?")>0){$www=substr($www, 0,strpos($www, "?"));}
            if(strpos($www, "\\")>0){$www=substr($www, 0,strpos($www, "\\"));}
            if(strpos($www, "/")>0){$www=substr($www, 0,strpos($www, "/")-1);}
            if(preg_match("#^\.(.+)#", $www,$re)){$www=$re[1];}
            if(preg_match("#^\*\.(.+)#", $www,$re)){$www=$re[1];}
            if(preg_match("#\.html$#i",$www,$re)){continue;}
            if(preg_match("#\.htm$#i",$www,$re)){continue;}
            if(preg_match("#\.gif$#i",$www,$re)){continue;}
            if(preg_match("#\.png$#i",$www,$re)){continue;}
            if(preg_match("#\.jpeg$#i",$www,$re)){continue;}
            if(preg_match("#\.jpg$#i",$www,$re)){continue;}
            if(preg_match("#\.php$#i",$www,$re)){continue;}
            if(preg_match("#\.js$#i",$www,$re)){continue;}
            if($ForceExt==0){
                if(!preg_match("#\.[a-z0-9]+$#",$www,$re)){;
                    $this->free_categorize_logs($logid,"<span class='text-danger'>$www bad extension [$ForceExt]</span>");
                    continue;
                }
            }
            if(strpos(" ", trim($www))>0){continue;}
            $sites[$www]=$www;
        }




        $memcached=new lib_memcached();
        if(count($sites)==0){$this->free_categorize_logs($logid,"NO websites");return;}
        $q=new postgres_sql();
        $ligne=pg_fetch_array($q->QUERY_SQL("SELECT categorytable FROM personal_categories WHERE category_id='$category_id'"));
        $category_table=$ligne["categorytable"];
        $fn=array();
        $tpl=new template_admin();
        foreach ($sites as $num=>$www){
            $www=trim($www);
            if($www==null){continue;}
            if(preg_match("#^www\.(.+?)$#", $www,$re)){$www=$re[1];}
            if(preg_match("#^\$.+?\.(.+)#", $www,$re)){$www=$re[1];}

            if($ForceCat==0){
                $cats=intval($this->GET_CATEGORIES($www,true,true,true));
                if($cats>999){$cats=0;}
                if($cats>0){
                    $mcatz=new mysql_catz();
                    $categories_descriptions=$mcatz->categories_descriptions();
                    $categoryname=$categories_descriptions[$cats]["categoryname"];
                    if($categoryname==null){
                        $categoryname=$mcatz->CategoryIntToStr($mcatz);
                    }
                    $js="Loadjs('fw.ufdb.categories.php?category-js=$cats')";
                    $category_description=$tpl->_ENGINE_parse_body($categories_descriptions[$cats]["category_description"]);
                    $category_text=$tpl->td_href("$categoryname",$category_description,$js);
                    $this->free_categorize_logs($logid,"<span class='text-danger'><strong>$www</strong> Already categorized as <strong>$category_text</strong></span>");
                    continue;
                }
            }
            $this->free_categorize_logs($logid,"<span class='text-success'>$www Added in $category_table</psan>");
            $fn[]="('$www')";
            $memcached->saveKey("CATEGORY:$www", $category_id,86400);

        }

        if(count($fn)>0){
            if(!$q->TABLE_EXISTS($category_table)){$this->CreateCategoryTable(null,$category_table);}

            if(isset($GLOBALS["ROOT_UID"])) {
                if (function_exists("admin_tracks")) {
                    admin_tracks("Categorize ".@implode(",", $fn)." into category ID $category_id $category_table");
                }
            }


            $sql="INSERT INTO $category_table (sitename) VALUES ".@implode(",", $fn)." ON CONFLICT DO NOTHING";
            $q->QUERY_SQL($sql);
            if(!$q->ok){echo "categorize $www failed $q->mysql_error line ". __LINE__ ." in file ".__FILE__."\n";}
            $ligne2=pg_fetch_array($q->QUERY_SQL("SELECT count(*) as tcount FROM $category_table"));
            $CountOfLines=$ligne2["tcount"];
            $this->free_categorize_logs($logid,"$category_table:" .$this->FormatNumber($CountOfLines)." elements");
            $q->QUERY_SQL("UPDATE personal_categories SET items='$CountOfLines' WHERE category_id='$category_id'");


        }


    }

    public function CreateCategoryTable($category,$fulltablename=null){
        if(!class_exists("categories")){include_once(dirname(__FILE__)."/class.categories.inc");}
        $catz=new categories();$catz->CreateCategoryTable($category,$fulltablename);}
    public function GetFamilySitestt($domain,$getpartOnly=false){
        if(!class_exists("squid_familysite")){include_once(dirname(__FILE__)."/class.squid.familysites.inc"); }
        $fam=new squid_familysite();
        return $fam->GetFamilySitestt($domain,$getpartOnly);
    }

    function category_transform_name($category){
        $q=new mysql_catz(true);return $q->category_transform_name($category);
    }



    function TransArray(){
        $q=new mysql_catz(true);
        return $q->TransArray();

    }

    function cat_totablename($category){
        $category=trim(strtolower($category));
        $trans=$this->TransArray();
        foreach ($trans as $table=> $categories){
            if($categories==$category){return $table;}
        }

        return "category_{$category}";

    }

    function tablename_tocat($tablename){
        if(strpos($tablename, ",")>0){$pp=explode(",",$tablename);$tablename=$pp[0];}
        if(isset($GLOBALS["tablename_tocat"][$tablename])){return $GLOBALS["tablename_tocat"][$tablename];}
        $trans=$this->TransArray();
        if(!isset($trans[$tablename])){
            if($this->TABLE_EXISTS("$tablename")){
                $ligne2=mysqli_fetch_array($this->QUERY_SQL("SELECT category FROM $tablename LIMIT 0,1"));
                if(trim($ligne2["category"])<>null){
                    $GLOBALS["tablename_tocat"][$tablename]=trim($ligne2["category"]);
                    return trim($ligne2["category"]);
                }

                if(preg_match("#category_(.+)#", $tablename,$re)){
                    $GLOBALS["tablename_tocat"][$tablename]=trim(strtolower($re[1]));
                    return trim(strtolower($re[1]));
                }
            }


        }else{
            $GLOBALS["tablename_tocat"][$tablename]=$trans[$tablename];
            return $trans[$tablename];
        }
    }

    function filaname_tocat($filename){
        if(strpos($filename, "/domains.ufdb")>0){$filename=str_replace("/domains.ufdb", "",$filename);}
        $q=new mysql_catz(true);
        $trans=$q->TransArray();
        $filename=basename($filename);
        $filename=str_replace(".ufdb", "", $filename);
        if(preg_match("#^category_(.*)#", $filename,$re)){$filename=$re[1];}
        if(isset($trans["category_{$filename}"])){return $trans["category_{$filename}"];}
        $array["audio-video"]="audio-video";
        $array["gambling"]="gamble";
        $array["cooking"]="hobby/cooking";
        $array["bank"]="finance/banking";
        $array["lingerie"]="sex/lingerie";
        $array["drogue"]="drugs";
        $array["child"]="children";
        $array["adult"]="porn";
        $array["aggressive"]="agressive";
        $array["agressif"]="agressive";
        $array["radio"]="webradio";
        $array["remote-control"]="remote-control";
        $array["social_networks"]="socialnet";
        $array["mobile-phone"]="mobile-phone";
        $array["sports"]="recreation/sports";
        $array["verisign"]="sslsites";
        $array["associations"]="associations";
        $array["translation"]="translators";

        $array["arjel"]="arjel";
        if(isset($array["$filename"])){return $array["$filename"];}

        return "$filename";
    }


    function uid_to_tablename($uid){
        $uid=trim($uid);
        if(preg_match("#(.+?)\/(.+)#", $uid,$re)){$uid=$re[2];}
        if(!class_exists("class.html.tools.inc")){include_once(dirname(__FILE__)."/class.html.tools.inc");}
        $t=new htmltools_inc();
        $uid=$t->replace_accents($uid);
        $uid=str_replace("$", "", $uid);
        $uid=str_replace(" ", "_", $uid);
        return $uid;

    }


    public function GET_THUMBNAIL($sitename,$width){
        $sitename=trim(strtolower($sitename));
        return "
		<a href=\"javascript:blur();\" OnClick=\"Loadjs('squid.statistics.php?thumbnail-zoom-js=$sitename');\">
		<img src='/squid.statistics.php?thumbnail=$sitename&width=$width' class=img-polaroid></a>";
    }


    public function WEEK_TITLE($weekNumber, $year){
        if(strlen($weekNumber)==1){$weekNumber="0$weekNumber";}
        $xtime= strtotime("{$weekNumber}W{$year}");
        $t1=date("{l} d {F}",$xtime);
        $t2 = date("{l} d {F}",strtotime(date("Y-m-d",$xtime)." 00:00:00 + 7 days"));
        return "{week}:&nbsp;&nbsp;{from}&nbsp;$t1&nbsp;{to}&nbsp;$t2 $year";
    }

    public function WEEK_TOTIMEHASH_FROM_TABLENAME($tablename){
        $Cyear=substr($tablename, 0,4);
        $Cweek=substr($tablename,4,2);
        $Cweek=str_replace("_", "", $Cweek);
        $xtime= strtotime("{$Cweek}W{$Cyear}");
        return $xtime;

    }

    public function WEEK_TIME_FROM_TABLENAME($tablename){
        $Cyear=substr($tablename, 0,4);
        $Cweek=substr($tablename,4,2);
        $Cweek=str_replace("_", "", $Cweek);
        return strtotime("{$Cyear}W{$Cweek}");

    }


    public function TIME_FROM_DANSGUARDIAN_EVENTS_TABLE($tablename){
        preg_match("#dansguardian_events_([0-9]+)#", $tablename,$re);
        $intval=$re[1];
        $Cyear=substr($intval, 0,4);
        $CMonth=substr($intval,4,2);
        $CDay=substr($intval,6,2);
        $CDay=str_replace("_", "", $CDay);
        return strtotime("$Cyear-$CMonth-$CDay 00:00:00");
    }

    public function MacToUid($mac=null){
        if($mac==null){return;}
        if(!isset($GLOBALS["USERSDB"])){$GLOBALS["USERSDB"]=unserialize(@file_get_contents("/etc/squid3/usersMacs.db"));}
        if(!isset($GLOBALS["USERSDB"]["MACS"][$mac]["UID"])){return;}
        if($GLOBALS["USERSDB"]["MACS"][$mac]["UID"]==null){return;}
        return trim($GLOBALS["USERSDB"]["MACS"][$mac]["UID"]);

    }
    public function IpToUid($ipaddr=null){
        if($ipaddr==null){return;}
        if(!isset($GLOBALS["USERSDB"])){$GLOBALS["USERSDB"]=unserialize(@file_get_contents("/etc/squid3/usersMacs.db"));}
        if(!isset($GLOBALS["USERSDB"]["MACS"][$ipaddr]["UID"])){return;}
        if($GLOBALS["USERSDB"]["MACS"][$ipaddr]["UID"]==null){return;}
        $uid=trim($GLOBALS["USERSDB"]["MACS"][$ipaddr]["UID"]);

    }
    public function MacToHost($mac=null){
        if($mac==null){return;}
        if(!isset($GLOBALS["USERSDB"]["MACS"][$mac]["HOST"])){return;}
        if($GLOBALS["USERSDB"]["MACS"][$mac]["HOST"]==null){return;}
        $uid=trim($GLOBALS["USERSDB"]["MACS"][$mac]["HOST"]);
    }
    public function IpToHost($ipaddr=null){
        if($ipaddr==null){return;}
        if(!isset($GLOBALS["USERSDB"]["MACS"][$ipaddr]["HOST"])){return;}
        if($GLOBALS["USERSDB"]["MACS"][$ipaddr]["HOST"]==null){return;}
        $uid=trim($GLOBALS["USERSDB"]["MACS"][$ipaddr]["HOST"]);
    }


}
function writelogs_squid($text,$function=null,$file=null,$line=0,$category=null,$nosql=false){
    if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
    if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=@getmypid();}
    if(!isset($GLOBALS["AS_ROOT"])) {
        if (function_exists("posix_getuid")) {
            if (posix_getuid() == 0) {
                $GLOBALS["AS_ROOT"] = true;
            } else {
                $GLOBALS["AS_ROOT"] = false;
            }
        }
    }
    $array_load=sys_getloadavg();
    $internal_load=$array_load[0];
    if($file<>null){$me=basename($file);}else{$me=basename(__FILE__);}
    $date=@date("H:i:s");

    if(function_exists("debug_backtrace")){
        $trace=debug_backtrace();
        if(isset($trace[1])){
            $sourcefile=basename($trace[1]["file"]);
            $sourcefunction=$trace[1]["function"];
            $sourceline=$trace[1]["line"];}
    }

    if($function==__FUNCTION__){$function=null;}
    if($function==null){$function=$sourcefunction;}
    if($line==0){$line=$sourceline;}
    if($file==null){$line=$sourcefile;}

    if(function_exists("squid_admin_mysql")){
        squid_admin_mysql(2,$text,"$date $me"."[".$GLOBALS["MYPID"]."/$internal_load]:$category::$function",$sourcefile,$sourceline);
    }

    if($GLOBALS["AS_ROOT"]){

        $logFile="/var/log/artica-squid-stats.log";
        if(is_file($logFile)){
            $size=filesize($logFile);
            if($size>5000000){unlink($logFile);}
        }

        $f = fopen($logFile, 'a');
        fwrite($f, "$date $me"."[".$GLOBALS["MYPID"]."/$internal_load]:$category::$function::$line: $text\n");
        fclose($f);
    }
    if($nosql){return;}
    if(function_exists("ufdbguard_admin_events")){
        ufdbguard_admin_events($text, $function, $file, $line, $category);
    }
}



class webfilter_rules{
    private $database;
    function __construct($database="webfilter.db"){
        if($database==null){$this->database="webfilter.db";}else{
            $this->database=$database;
        }

    }

    public function rule_time_list_explain($TimeSpace,$ID,$t):string{
        $tpl=new templates();
        $MyPage=CurrentPageName();


        $TimeSpace=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($TimeSpace);
        if(!is_array($TimeSpace)){if($GLOBALS["VERBOSE"]){echo "<HR>$ID not an array\n";}}
        if($GLOBALS["VERBOSE"]){echo "<HR>\n";print_r($TimeSpace);}

        $tpl=new templates();
        if(!is_array($TimeSpace)){return "";}
        if(!isset($TimeSpace["TIMES"])){return "";}
        if(count($TimeSpace["TIMES"])==0){return "";}
        $FINAL=array();
        if($TimeSpace["RuleMatchTime"]==null){$TimeSpace["RuleMatchTime"]="none";}
        if($TimeSpace["RuleMatchTime"]=="none"){
            return $tpl->_ENGINE_parse_body("&nbsp;<i class='text-danger'>{time}: {no_position_set} !</i>");
        }

        $daysARR=array("m"=>"Monday","t"=>"Tuesday","w"=>"Wednesday","h"=>"Thursday","f"=>"Friday","a"=>"Saturday","s"=>"Sunday");

        foreach ($TimeSpace["TIMES"] as $TIMEID=>$array){
            $dd=array();
            if(is_array($array["DAYS"])){
                foreach ($array["DAYS"] as $day=>$val){
                    if($val==1){$dd[]="{".$daysARR[$day]."}";}
                }
                $daysText=@implode(", ", $dd);
            }
            if(strlen($array["BEGINH"])==1){$array["BEGINH"]="0{$array["BEGINH"]}";}
            if(strlen($array["BEGINM"])==1){$array["BEGINM"]="0{$array["BEGINM"]}";}
            if(strlen($array["ENDH"])==1){$array["ENDH"]="0{$array["ENDH"]}";}
            if(strlen($array["ENDM"])==1){$array["ENDM"]="0{$array["ENDM"]}";}
            $daysText=$daysText.$tpl->_ENGINE_parse_body("<br>{from} {$array["BEGINH"]}:{$array["BEGINM"]} {to} {$array["ENDH"]}:{$array["ENDM"]}",1);
            $textfinal=$tpl->_ENGINE_parse_body("{each} $daysText");


            $FINAL[]="<i>$textfinal</i>";
        }
        if(count($FINAL)>0){return @implode("<br>", $FINAL);}
        return "";
    }

    function COUNTDEGROUPES($ruleid):int{
        $q=new lib_sqlite("/home/artica/SQLITE/$this->database");
        $sql="SELECT COUNT(ID) as tcount FROM webfilter_assoc_groups WHERE webfilter_id='$ruleid'";
        $ligne=$q->mysqli_fetch_array($sql);
        if(!$q->ok){
            return 0;
        }
        if(!is_numeric($ligne["tcount"])){$ligne["tcount"]=0;}
        return intval($ligne["tcount"]);
    }

    function COUNTDEGBLKS($ruleid){
        $q=new lib_sqlite("/home/artica/SQLITE/$this->database");
        $sql="SELECT COUNT(ID) as tcount FROM webfilter_blks WHERE webfilter_id='$ruleid' AND modeblk=0" ;
        $ligne=$q->mysqli_fetch_array($sql);
        if(!is_numeric($ligne["tcount"])){$ligne["tcount"]=0;}
        $C=$ligne["tcount"];
        $C=$C+$this->COUNTDEGBLKS_GROUPS($ruleid,0);
        return $C;
    }

    function COUNTDEGBLKS_GROUPS($ruleid,$modeblk){
        $q=new lib_sqlite("/home/artica/SQLITE/$this->database");
        $sql="SELECT webfilter_blkid FROM webfilter_blklnk WHERE webfilter_ruleid=$ruleid AND blacklist=$modeblk";
        $results=$q->QUERY_SQL($sql);
        if(!$q->ok){writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
        $Count=0;
        foreach ($results as $index=>$ligne){
            $groupid=$ligne["webfilter_blkid"];
            $ligne2=$q->mysqli_fetch_array("SELECT enabled FROM webfilter_blkgp WHERE ID=$groupid");
            if($ligne2["enabled"]==0){continue;}
            $sql="SELECT COUNT(`category`) AS tcount FROM webfilter_blkcnt WHERE `webfilter_blkid`='$groupid'";
            $ligne2=$q->mysqli_fetch_array($sql);
            if(!$q->ok){writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
            if(!is_numeric($ligne2["tcount"])){continue;}
            $Count=$Count+$ligne2["tcount"];

        }
        return $Count;

    }


    function COUNTDEGBWLS($ruleid){
        $q=new lib_sqlite("/home/artica/SQLITE/$this->database");
        $sql="SELECT COUNT(ID) as tcount FROM webfilter_blks WHERE webfilter_id='$ruleid' AND modeblk=1";
        $ligne=$q->mysqli_fetch_array($sql);
        if(!$q->ok){writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
        if(!is_numeric($ligne["tcount"])){$ligne["tcount"]=0;}
        $C=$ligne["tcount"];
        $C=$C+$this->COUNTDEGBLKS_GROUPS($ruleid,1);
        return $C;
    }

    function TimeToText($TimeSpace){
        $RuleBH=array("inside"=>"{inside_time}","outside"=>"{outside_time}","none"=>"{disabled}");
        if($TimeSpace["RuleMatchTime"]==null){$TimeSpace["RuleMatchTime"]="none";}
        if($TimeSpace["RuleAlternate"]==null){$TimeSpace["RuleAlternate"]="none";}
        if($TimeSpace["RuleMatchTime"]=="none"){return;}
        $q=new lib_sqlite("/home/artica/SQLITE/$this->database");

        $RULESS["none"]="{none}";
        $RULESS[0]="{default}";
        $sql="SELECT ID,enabled,groupmode,groupname FROM webfilter_rules WHERE enabled=1 ORDER BY groupname";
        $results=$q->QUERY_SQL($sql);

        foreach ($results as $index=>$ligne){$RULESS[$ligne["ID"]]=$ligne["groupname"];}


        $daysARR=array("m"=>"Monday","t"=>"Tuesday","w"=>"Wednesday","h"=>"Thursday","f"=>"Friday","a"=>"Saturday","s"=>"Sunday");
        foreach ($TimeSpace["TIMES"] as $TIMEID=>$array){
            if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
            $dd=array();

            if(!is_array($array["DAYS"])){return;}
            foreach ($array["DAYS"] as $day=>$val){if($val==1){$dd[]="{".$daysARR[$day]."}";}}
            $daysText=@implode(", ", $dd);

            if(strlen($array["BEGINH"])==1){$array["BEGINH"]="0{$array["BEGINH"]}";}
            if(strlen($array["BEGINM"])==1){$array["BEGINM"]="0{$array["BEGINM"]}";}
            if(strlen($array["ENDH"])==1){$array["ENDH"]="0{$array["ENDH"]}";}
            if(strlen($array["ENDM"])==1){$array["ENDM"]="0{$array["ENDM"]}";}

            $f[]="<div style='font-weight:normal'>{$RuleBH[$TimeSpace["RuleMatchTime"]]} $daysText {from} {$array["BEGINH"]}:{$array["BEGINM"]} {to} {$array["ENDH"]}:{$array["ENDM"]} {then}
		 {alternate_rule} {to} {$RULESS[$TimeSpace["RuleAlternate"]]}</div>";

        }


        return @implode("\n", $f);


    }

}
function CategoriesCheckRightsRead(){
    $users=new usersMenus();
    if($users->AsDansGuardianAdministrator){return true;}
    $q=new mysql_squid_builder();
    $sql="SELECT PublicMode FROM personal_categories WHERE category='{$_REQUEST["category"]}'";
    $ligne=mysqli_fetch_array($q->QUERY_SQL($sql));

    if(!$q->ok){
        echo $q->mysql_error_html();
        return;
    }

    $GLOBALS["CategoriesCheckRights"][]="{$_REQUEST["category"]}: Public Mode: {$ligne["PublicMode"]}";
    if($ligne["PublicMode"]==1){return true;}
    $CategoriesCheckPerms=CategoriesCheckPerms();
    if($CategoriesCheckPerms[$_REQUEST["category"]]==1){return true;}
    return false;
}
function CategoriesCheckRightsWrite(){
    $users=new usersMenus();
    if($users->AsDansGuardianAdministrator){return true;}
    $CategoriesCheckPerms=CategoriesCheckPerms();
    if($CategoriesCheckPerms[$_REQUEST["category"]]==1){return true;}
}
function lkdfjozif_uehfe(){
    $users=new usersMenus();
    $tpl=new templates();
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        define("kdfjozif", "<p class=text-error>".$tpl->_ENGINE_parse_body("{ERROR_NO_LICENSE}")."</p>");}

}
function CategoriesCheckPerms($nocache=false){
    if(!$nocache){if(isset($_SESSION["ProxyCategoriesPermissions"])){return $_SESSION["ProxyCategoriesPermissions"];}}
    $_SESSION["ProxyCategoriesPermissions"]=array();
    $ARRAYPERS=CategoriesCheckGroupsArray();
    $q=new mysql_squid_builder();
    $sql="SELECT *  FROM `webfilter_catprivs`";
    $results = $q->QUERY_SQL($sql);
    while ($ligne = mysqli_fetch_assoc($results)) {
        $md5=$ligne["zmd5"];
        $categorykey=$ligne["categorykey"];
        $groupdata=$ligne["groupdata"];
        preg_match("#^@(.+?):(.+)#", $groupdata,$re);
        $GroupName=$re[1];
        $gpdata=strtolower(trim(base64_decode($re[2])));
        $allowrecompile=$ligne["allowrecompile"];
        if(!isset($ARRAYPERS[$gpdata])){continue;}
        $_SESSION["ProxyCategoriesPermissions"][$categorykey]=$allowrecompile;
    }

    return $_SESSION["ProxyCategoriesPermissions"];

}
function CategoriesCheckGroupsArray(){

    if(isset($GLOBALS[__CLASS__.__FUNCTION__])){return $GLOBALS[__CLASS__.__FUNCTION__];}
    $ldap=new clladp();
    $ARRAYPERS=array();
    if($ldap->IsKerbAuth()){
        include_once(dirname(__FILE__)."/class.external.ad.inc");
        $ad=new external_ad_search();
        $groups=$ad->GroupsOfMember($_SESSION["uid"]);
        if(!is_array($groups)){$groups=array();}
        foreach ($groups as $dn=>$name){
            $ARRAYPERS[strtolower($dn)]=true;
        }

    }else{
        $users = new user ( $_SESSION["uid"]);
        $groups = $users->Groups_list();
        if(!is_array($groups)){$groups=array();}
        foreach ($groups as $gid=>$name){
            $ARRAYPERS[$gid]=true;
        }
    }

    $GLOBALS[__CLASS__.__FUNCTION__]=$ARRAYPERS;
    return $ARRAYPERS;

}

