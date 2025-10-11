<?php
//SP119
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.features.inc");


include_once(dirname(__FILE__)."/ressources/class.openvpn.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["main-page"])){main_page();exit;}
if(isset($_GET["table"])){features();exit;}
if(isset($_POST["lang"])){lang();exit;}
if(isset($_GET["bts-menus"])){echo base64_decode($_GET["bts-menus"]);exit;}
page();


function main_page(){
    $page=CurrentPageName();
    $html="<div id='main-service-page'></div>
	<script>LoadAjax('main-service-page','$page');</script>";
    $tpl=new template_admin(null,$html);
    echo $tpl->build_firewall();
}

function page(){
    $page=CurrentPageName();
    $network_services=1;
    $members_services=1;
    $network_monitoring=1;
    $proxy_features=1;
    $statistics=1;
    $network_security=1;
    $data_transfer=1;
    $messaging_services=1;
    $expan=0;
    if(isset($_COOKIE["network_services"])){$network_services=$_COOKIE["network_services"];}
    if(isset($_COOKIE["members_services"])){$members_services=$_COOKIE["members_services"];}
    if(isset($_COOKIE["network_monitoring"])){$network_monitoring=$_COOKIE["network_monitoring"];}
    if(isset($_COOKIE["proxy_features"])){$proxy_features=$_COOKIE["proxy_features"];}
    if(isset($_COOKIE["statistics"])){$statistics=$_COOKIE["statistics"];}
    if(isset($_COOKIE["network_security"])){$network_security=$_COOKIE["network_security"];}
    if(isset($_COOKIE["data_transfer"])){$data_transfer=$_COOKIE["data_transfer"];}
    if(isset($_COOKIE["messaging_services"])){$messaging_services=$_COOKIE["messaging_services"];}
    if(isset($_COOKIE["expand"])){$expan=$_COOKIE["expand"];}
    $tpl=new template_admin();
    $html=$tpl->page_header("{activate_disable_features}",
        ico_cd,"{activate_disable_features_section}<div id='bts-menus'></div>",
        "$page?table=yes&network_services=$network_services&members_services=$members_services&network_monitoring=$network_monitoring&proxy_features=$proxy_features&statistics=$statistics&network_security=$network_security&data_transfer=$data_transfer&messaging_services=$messaging_services&expand=$expan",
        "features","progress-firehol-restart",false,"table-loader"
    );


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}
function features():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $features=new features();
    if(!isset($_GET["onlyactive"])){$_GET["onlyactive"]="no";}
    if($_GET["onlyactive"]=="yes"){
        $features->OnlyActive=true;
    }

    $APP_DNS_FIREWALL       = null;
    $EnablePostfix          = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
    $SQUIDEnable            = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));

    $UBOUND=$features->UBOUND();
    $APP_PDNS=$features->APP_PDNS();
    $APP_PDNS_RECURSOR=$features->APP_PDNS_RECURSOR();
    $ISCSI_CLIENT=$features->ISCSI_CLIENT();
    $OPENLDAP=$features->OPENLDAP();

    $RSYNC=$features->RSYNC();
    $NTPD_SERVER=$features->NTPD_SERVER();
    $HAPROXY=$features->HAPROXY();
    $WSUSOFFLINE=$features->WSUSOFFLINE();
    $INSTANTSEARCH=$features->INSTANTSEARCH();
    $SPLUNK=$features->SPLUNK();
    $FIREHOL=$features->FIREHOL();
    $SURICATA=$features->SURICATA();
    $APP_STATS_COMMUNICATOR=$features->APP_STATS_COMMUNICATOR();

    //$APP_CISCO_REPORTER=$features->APP_CISCO_REPORTER();
    $APP_CGROUPS=$features->APP_CGROUPS();
    if(method_exists($features,"APP_LEGAL_LOGS_SERVER")) {
        $APP_LEGAL_LOGS_SERVER = $features->APP_LEGAL_LOGS_SERVER();
    }
    if(method_exists($features,"APP_QAT")) {
        $APP_QAT = $features->APP_QAT();
    }
    $APP_SMOKEPING      = null;
    $SQUID_AD_RESTFULL  = null;
    $STRONGSWAN         = null;
    //KEEPALIVED
    $KEEPALIVED = null;
    $KEEPALIVED_SLAVE = null;
    //END KEEPALIVED
    $APP_IMAPBOX        = null;
    $KSRN               = null;
    $APP_SSHPROXY       = null;
    $APP_IPAUDIT        = null;
    $OPENSSH=$features->OPENSSH();

    if(method_exists($features,"APP_SSHPROXY")) {
        $APP_SSHPROXY = $features->APP_SSHPROXY();
    }

    $NTOPNG=$features->NTOPNG();

    if(method_exists($features,"APP_OSPFD")) {
        $APP_OSPFD = $features->APP_OSPFD();
    }
    $APP_SAMBA=$features->APP_SAMBA();

    $ADMIN_TRACK=$features->ADMIN_TRACK();
    $DHCPD=$features->DHCPD();
    $APP_KEA_DHCPD4=$features->APP_KEA_DHCPD4();
    $OPENVPN=$features->OPENVPN();
    if(method_exists($features,"APP_TAILSCALE")) {
        $TAILSCALE = $features->APP_TAILSCALE();
    }
    $DWAGENT    = null;
    if(method_exists($features,"APP_PPTP_CLIENT")) {
        $APP_PPTP_CLIENT = $features->APP_PPTP_CLIENT();
    }
    if(method_exists($features,"STRONGSWAN")) {
        $STRONGSWAN = $features->STRONGSWAN();
    }
    //KEEPALIVED
    if (method_exists($features, "KEEPALIVED")) {
        $KEEPALIVED = $features->KEEPALIVED();
    }
    if (method_exists($features, "KEEPALIVED_SLAVE")) {
        $KEEPALIVED_SLAVE = $features->KEEPALIVED_SLAVE();
    }
    if (method_exists($features, "APP_IMAPBOX")) {
        $APP_IMAPBOX = $features->APP_IMAPBOX();
    }

    if(method_exists($features,"KSRN")) {
        $KSRN = $features->KSRN();
    }
    if(method_exists($features,"DWAGENT")) {
        $DWAGENT = $features->DWAGENT();
    }
    if(method_exists($features,"MOD_SECURITY")) {
        $MOD_SECURITY = $features->MOD_SECURITY();
    }
    if(method_exists($features,"ARPD")) {
        $ARPD = $features->ARPD();
    }
    $APP_KWTS_CONNECTOR=null;
    if(method_exists($features,"APP_KWTS_CONNECTOR")) {
        $APP_KWTS_CONNECTOR = $features->APP_KWTS_CONNECTOR();
    }


    if(method_exists($features,"APP_DNS_FIREWALL")) {
        $APP_DNS_FIREWALL = $features->APP_DNS_FIREWALL();
    }
    $APP_SMOKEPING=null;
    if(method_exists($features,"APP_SMOKEPING")) {
        $APP_SMOKEPING = $features->APP_SMOKEPING();
    }
    $APP_META_SERVER=null;
    if(method_exists($features,"APP_META_SERVER")) {
        $APP_META_SERVER = $features->APP_META_SERVER();
    }


    $APP_WAZHU=null;
    if(method_exists($features,"APP_WAZHU")) {
        $APP_WAZHU = $features->APP_WAZHU();
    }
    $APP_RUSTDESK=null;
    if(method_exists($features,"APP_RUSTDESK")) {
        $APP_RUSTDESK = $features->APP_RUSTDESK();
    }

    $APP_OPENVSWITCH=$features->APP_OPENVSWITCH();
    $APP_FIRECRACKER=$features->APP_FIRECRACKER();

    $APP_GOSHIELD=null;
    if(method_exists($features,"APP_GOSHIELD")) {
        $APP_GOSHIELD = $features->APP_GOSHIELD();
    }

    $APP_DOCKER=null;
    if(method_exists($features,"APP_DOCKER")) {
        $APP_DOCKER = $features->APP_DOCKER();
    }
    $APP_CROWDSEC=null;
    if(method_exists($features,"APP_CROWDSEC")) {
        $APP_CROWDSEC=$features->APP_CROWDSEC();
    }

    $APP_CYBERCRIMEIPFEEDS=null;
    if(method_exists($features,"APP_CYBERCRIMEIPFEEDS")) {
        $APP_CYBERCRIMEIPFEEDS=$features->APP_CYBERCRIMEIPFEEDS();
    }

    $APP_NAGIOS_CLIENT=null;
    if(method_exists($features,"APP_NAGIOS_CLIENT")) {
        $APP_NAGIOS_CLIENT=$features->APP_NAGIOS_CLIENT();
    }

    $APP_HAMRP=null;
    $PULSEREVERSE=null;
    if(method_exists($features,"APP_HAMRP")) {
        $APP_HAMRP=$features->APP_HAMRP();
    }
    if(method_exists($features,"PULSEREVERSE")) {
        $PULSEREVERSE=$features->PULSEREVERSE();
    }
    if(method_exists($features,"APP_IPAUDIT")) {
        VERBOSE("APP_IPAUDIT: OK",__LINE__);
        $APP_IPAUDIT=$features->APP_IPAUDIT();
    }else{
        VERBOSE("APP_IPAUDIT: NO METHOD",__LINE__);
    }


    $AUTOFS=$features->AUTOFS();
    $NETDATA=$features->NETDATA();
    $DARKSTAT=$features->DARKSTAT();
    $IWLWIFI=$features->IWLWIFI();



    $APP_IWCONFIG=$features->APP_IWCONFIG();
    $APP_FRONTAIL_LINUX=$features->APP_FRONTAIL_LINUX();
    $APP_PRADS=$features->APP_PRADS();

    $SQUID=$features->SQUID();
    $SQUID_ACLS=$features->SQUID_ACLS();
    $SQUID_RESTFULL=$features->SQUID_RESTFULL();
    $SQUID_CACHE=$features->SQUID_CACHE();
    $SQUID_PARENTS=$features->SQUID_PARENTS();
// $APP_ARTICALOGGER=$features->APP_ARTICALOGGER();
//  $UFDBGUARD=$features->UFDBGUARD();
//    $PERSONAL_CATEGORIES=$features->PERSONAL_CATEGORIES();
    $ACTIVEDIRECTORY=$features->ACTIVEDIRECTORY();
    $ACTIVEDIRECTORY_AD_AGENT=$features->ACTIVEDIRECTORY_AD_AGENT();
    $FAIL2BAN=$features->FAIL2BAN();
//  $FIREQOS=$features->FIREQOS();
    $PROFTPD=$features->PROFTPD();
    $ELASTICSEARCH=$features->ELASTICSEARCH();
    $KIBANA=$features->KIBANA();
    $PROXY_PAC=$features->PROXY_PAC();



    $APP_VNSTAT=$features->APP_VNSTAT();
    $APP_MUNIN=$features->APP_MUNIN();
    $APP_FREERADIUS=$features->APP_FREERADIUS();
    $APP_WANPROXY=$features->APP_WANPROXY();
    $APP_ZIPROXY=$features->APP_ZIPROXY();
//  $LM_SENSORS=$features->LM_SENSORS();
    $APP_PRIVOXY=$features->APP_PRIVOXY();
    $GLANCES=$features->GLANCES();
    $POSTFIX=$features->POSTFIX();
//  $APP_CYRUS=$features->APP_CYRUS();
    $APP_KLNAGENT=$features->APP_KLNAGENT();
    $APP_SNMPD=$features->APP_SNMPD();
    //$APP_MAIL_SPY=$features->APP_MAIL_SPY();
    $APP_MIMEDEFANG=$features->APP_MIMEDEFANG();
    $APP_RBLDNSD=$features->APP_RBLDNSD();
    $APP_NGINX=$features->APP_NGINX();

    $APP_GREENSQL=$features->APP_GREENSQL();
    $APP_MYSQL=$features->APP_MYSQL();
    $APP_MANTICORE=$features->APP_MANTICORE();
    $UBOUND_DNS=$features->UBOUND_DNS();
    $CICAP=$features->CICAP();
    $UFDBCAT=$features->DNSCATS();
    $APP_MILTERGREYLIST=$features->APP_MILTERGREYLIST();
    $APP_MULTIPATH_TCP=$features->APP_MULTIPATH_TCP();
    $network_services_enabled=false;
    $network_monitoring_enabled=false;
    $members_services_enabled=false;
    $onlyactive_enabled=false;
    $statistics_enabled=false;
    $network_security_enabled=false;
    $data_transfer_enabled=false;
    $proxy_features_enabled=false;
    $APP_DSC=$features->APP_DSC();
    $APP_3PROXY=$features->APP_3PROXY();
    $VLANS=$features->VLANS();
    $APP_REDSOCKS=$features->APP_REDSOCKS();
    $APP_CLAMAV=$features->APP_CLAMAV();
    $OPENLDAP_REST=$features->OPENLDAP_REST();
    $SYSTEM_RESTFULL=$features->SYSTEM_RESTFULL();
    $APP_MILTER_REGEX=$features->APP_MILTER_REGEX();
    $APP_DNSCRYPT_PROXY=$features->APP_DNSCRYPT_PROXY();
    $APP_DOH_SERVER=$features->APP_DOH_SERVER();
    $APP_REDIS_SERVER=$features->APP_REDIS_SERVER();
    $APP_NETMONIX=$features->APP_NETMONIX();

    $APP_WORDPRESS=$features->APP_WORDPRESS();
    $APP_PHP_REVERSE=$features->APP_PHP_REVERSE();
    //$APP_SYSLOG=$features->APP_SYSLOG_SERVER();
    $APP_POWERDNS_RESTFUL=$features->APP_POWERDNS_RESTFUL();
    $APP_RDPPROXY=$features->APP_RDPPROXY();
    $APP_NDPI=$features->APP_NDPI();
    $APP_LINK_BALANCER=$features->APP_LINK_BALANCER();
//    $APP_SQUIDANALYZER=$features->APP_SQUIDANALYZER(); DEPRECIATED
    $APP_CLUSTER_MASTER=$features->APP_CLUSTER_MASTER();
    $APP_CLUSTER_SLAVE=$features->APP_CLUSTER_SLAVE();
    $APP_FILEBEAT=$features->APP_FILEBEAT();
    $APP_OPENDKIM=$features->APP_OPENDKIM();
    //$APP_CENTRAL_NODE=$features->APP_CENTRAL_NODE();
    $APP_GEOIP_UPDATES=$features->APP_GEOIP_UPDATES();
    $APP_TAILON=$features->APP_TAILON();
    $HA_CLUSTER=$features->HA_CLUSTER();
    $FIREQOS=$features->FIREQOS();
    $APP_DNSDIST=$features->APP_DNSDIST();
    $APP_ZABBIX_AGENT=$features->APP_ZABBIX_AGENT();
    $APP_VMTOOLS=$features->APP_VMTOOLS();
    $CLOUD_CATEGORIES=$features->CLOUD_CATEGORIES();
    $APP_DHCP_RELAY=$features->APP_DHCP_RELAY();
    $APP_SSHPORTAL=$features->APP_SSHPORTAL();
    $SQUID_WCCP=$features->SQUID_WCCP();
    $APP_SYNCTHING=$features->APP_SYNCTHING();
    $SQUID_MIKROTIK=$features->SQUID_MIKROTIK();
    $IT_CHARTERS=$features->IT_CHARTERS();
    $speedtest=$features->speedtest();
    $APP_POSTGRESQL=$features->APP_POSTGRESQL();
    $APP_SYNO_BACKUP=$features->APP_SYNO_BACKUP();
    $HAPROXY_EXCHANGE=$features->HAPROXY_EXCHANGE();
    //$APP_LOKI=$features->APP_LOKI();
    $NETWORKD=$features->NETWORKD();

    "&network_services=1&members_services=1&network_monitoring=1&";

    if(intval($_GET["network_services"])==0){
        $network_services_uri="&network_services=1";
        $network_services_class="<i class='fal fa-circle'></i>&nbsp;";;
        $uri_main["network_services"]="&network_services=0";


    }else{
        $network_services_enabled=true;
        $network_services_uri="&network_services=0";
        $network_services_class="<i class='fas fa-check-circle'></i> <strong>";
        $uri_main["network_services"]="network_services=1";

    }


    if(intval($_GET["messaging_services"])==0){
        $messaging_services=false;
        $messaging_services_uri="&messaging_services=1";
        $messaging_services_class="<i class='fal fa-circle'></i>&nbsp;";;
        $uri_main["messaging_services"]="messaging_services=0";


    }else{
        $messaging_services=true;
        $messaging_services_uri="&messaging_services=0";
        $messaging_services_class="<i class='fas fa-check-circle'></i> <strong>";
        $uri_main["messaging_services"]="messaging_services=1";

    }


    if(intval($_GET["network_monitoring"])==0){

        $network_monitoring_uri="&network_monitoring=1";
        $network_monitoring_class="<i class='fal fa-circle'></i>&nbsp;";
        $uri_main["network_monitoring"]="network_monitoring=0";

    }else{
        $network_monitoring_enabled=true;
        $network_monitoring_uri="&network_monitoring=0";
        $network_monitoring_class="<i class='fas fa-check-circle'></i> <strong>";
        $uri_main["network_monitoring"]="network_monitoring=1";
    }

    if(intval($_GET["proxy_features"])==0){
        $proxy_features_uri="&proxy_features=1";
        $proxy_features_class="<i class='fal fa-circle'></i>&nbsp;";;
        $uri_main["proxy_features"]="proxy_features=0";

    }else{
        $proxy_features_enabled=true;
        $proxy_features_uri="&network_monitoring=0";
        $proxy_features_class="<i class='fas fa-check-circle'></i> <strong>";
        $uri_main["proxy_features"]="proxy_features=1";
    }



    if(intval($_GET["statistics"])==0){
        $statistics_uri="&statistics=1";
        $statistics_class="<i class='fal fa-circle'></i>&nbsp;";;
        $uri_main["statistics"]="statistics=0";

    }else{
        $statistics_enabled=true;
        $statistics_uri="&statistics=0";
        $statistics_class="<i class='fas fa-check-circle'></i> <strong>";
        $uri_main["statistics"]="statistics=1";
    }

    if(intval($_GET["network_security"])==0){

        $network_security_uri="&network_security=1";
        $network_security_class="<i class='fal fa-circle'></i>&nbsp;";;
        $uri_main["network_security"]="network_security=0";

    }else{
        $network_security_enabled=true;
        $network_security_uri="&statistics=0";
        $network_security_class="<i class='fas fa-check-circle'></i> <strong>";
        $uri_main["network_security"]="network_security=1";
    }



    if(intval($_GET["members_services"])==0){
        $members_services_uri="&members_services=1";
        $members_services_class="<i class='fal fa-circle'></i>&nbsp;";;
        $uri_main["members_services"]="members_services=0";

    }else{
        $members_services_enabled=true;
        $members_services_uri="&members_services=0";
        $members_services_class="<i class='fas fa-check-circle'></i> <strong>";
        $uri_main["members_services"]="members_services=1";
    }

    if(intval($_GET["data_transfer"])==0){
        $data_transfer_uri="&data_transfer=1";
        $data_transfer_class="<i class='fal fa-circle'></i>&nbsp;";;
        $uri_main["data_transfer"]="data_transfer=0";

    }else{
        $data_transfer_enabled=true;
        $data_transfer_uri="&data_transfer=0";
        $data_transfer_class="<i class='fas fa-check-circle'></i> <strong>";
        $uri_main["data_transfer"]="data_transfer=1";
    }

    $expand=0;
    if(isset($_GET["expand"])){
        $expand=intval($_GET["expand"]);
    }

    $uri_main["expand"]=$expand;


    if($_GET["onlyactive"]=="yes"){
        $onlyactive_class="<i class='fas fa-check-circle'></i> <strong>";
        $onlyactive_uri="&onlyactive=no";
        $uri_main["onlyactive"]="onlyactive=no";
    }else{
        $onlyactive_class="<i class='fal fa-circle'></i>&nbsp;";
        $onlyactive_uri="&onlyactive=yes";
        $uri_main["onlyactive"]="onlyactive=yes";
    }

    $array["$onlyactive_class{OnlyActive}</strong>"]="LoadAjax('table-loader','$page?table=yes$onlyactive_uri".build_uri($uri_main,'onlyactive')."')";
    $array["<hr>"]=true;
    if($EnablePostfix==0){
        $array["$proxy_features_class{proxy_features}</strong>"]="LoadAjax('table-loader','$page?table=yes$proxy_features_uri".build_uri($uri_main,'proxy_features')."')";
    }

    if($SQUIDEnable==0) {
        $array["$messaging_services_class{messagging}</strong>"] = "LoadAjax('table-loader','$page?table=yes$messaging_services_uri" . build_uri($uri_main, 'messaging_services') . "')";
    }
    $array["$network_monitoring_class{monitoring}</strong>"]="LoadAjax('table-loader','$page?table=yes$network_monitoring_uri".build_uri($uri_main,'network_monitoring')."')";
    $array["$network_services_class{network_services}</strong>"]="LoadAjax('table-loader','$page?table=yes$network_services_uri".build_uri($uri_main,'network_services')."')";
    $array["$network_security_class{network_security}</strong>"]="LoadAjax('table-loader','$page?table=yes$network_security_uri".build_uri($uri_main,'network_security')."')";
    $array["$members_services_class{members_services}</strong>"]="LoadAjax('table-loader','$page?table=yes$members_services_uri".build_uri($uri_main,'members_services')."')";
    $array["$data_transfer_class{data_transfer}</strong>"]="LoadAjax('table-loader','$page?table=yes$data_transfer_uri".build_uri($uri_main,'data_transfer')."')";
    $array["$statistics_class{statistics}<strong>"]="LoadAjax('table-loader','$page?table=yes$statistics_uri".build_uri($uri_main,'statistics')."')";

    $DropDownButton=$tpl->DropDownButton("{select}", $array);



    $bts[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-bottom:20px;z-index:50000000'>";
    $bts[]="$DropDownButton";

    if($expand==0){
        $jsex="LoadAjax('table-loader','$page?table=yes&expand=1".build_uri($uri_main,'expand')."')";
        $bts[]="&nbsp;<label class=\"btn btn btn-info\" OnClick=\"$jsex\"><i class='fas fa-plus-square'></i> {expand} </label>";

    }else{
        $jsex="LoadAjax('table-loader','$page?table=yes&expand=0".build_uri($uri_main,'expand')."')";
        $bts[]="&nbsp;<label class=\"btn btn btn-info\" OnClick=\"$jsex\"><i class='fas fa-minus-square'></i> {collapse} </label>";

    }

    $bts[]="&nbsp;<label class=\"btn btn btn-info\" OnClick=\"LoadAjax('features-templates','fw.system.wizards.php?popup=yes')\"><i class='fas fa-hat-wizard'></i> {wizards} </label>";

    $bts[]="</div>";


    $t=time();
    $html[]="<div id='features-templates'>";
    $html[]="<table class='footable table table-hover' id='table-$t' style='margin-top:0'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' >{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{software}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>Wiki</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{action}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $network_services[]="<tr><th colspan=4 class=label style='text-align:left'><H2>{network_services}</th></tr>";
    $network_services[]=$NETWORKD;
    $network_services[]=$VLANS;
    $network_services[]=$ARPD;
    $network_services[]=$APP_MULTIPATH_TCP;
    $network_services[]=$APP_QAT;
    $network_services[]=$APP_OPENVSWITCH;
    $network_services[]=$APP_FIRECRACKER;
    $network_services[]=$APP_CGROUPS;
    $network_services[]=$APP_IWCONFIG;
    $network_services[]=$IWLWIFI;
    $network_services[]=$APP_OSPFD;
    $network_services[]=$APP_DOCKER;
    $network_services[]=$APP_META_SERVER;

    $network_services[]=$UBOUND;
    $network_services[]=$UBOUND_DNS;
    $network_services[]=$APP_DNS_FIREWALL;
    $network_services[]=$APP_DNSDIST;
    $network_services[]=$APP_DOH_SERVER;
    $network_services[]=$APP_DNSCRYPT_PROXY;
    $network_services[]=$APP_PDNS;
    $network_services[]=$APP_PDNS_RECURSOR;
    $network_services[]=$APP_POWERDNS_RESTFUL;
    $network_services[]=$APP_DSC;


    $network_services[]=$APP_CLUSTER_MASTER;
    $network_services[]=$APP_CLUSTER_SLAVE;
    $network_services[]=$APP_RBLDNSD;

    $network_services[]=$NTPD_SERVER;
    $network_services[]=$APP_SSHPORTAL;
    $network_services[]=$APP_SSHPROXY;
    $network_services[]=$OPENSSH;
    $network_services[]=$DWAGENT;
    $network_services[]=$APP_SAMBA;
    $network_services[]=$APP_SYNCTHING;
    $network_services[]=$DHCPD;
    $network_services[]=$APP_KEA_DHCPD4;
    $network_services[]=$APP_DHCP_RELAY;
    $network_services[]=$APP_WORDPRESS;
    $network_services[]=$PULSEREVERSE;
    $network_services[]=$APP_NGINX;
    $network_services[]=$APP_PHP_REVERSE;
    $network_services[]=$APP_HAMRP;
    $network_services[]=$MOD_SECURITY;
    //$network_services[]=$APP_PPTP_CLIENT;
    $network_services[] = $OPENVPN;
    $network_services[] = $STRONGSWAN;
    //KEEPALIVED
    $slaveIsenable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE"));
    if ($slaveIsenable == 0) {
        $network_services[] = $KEEPALIVED;
    }
    $masterIsenable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE"));
    if ($masterIsenable == 0) {
        $network_services[] = $KEEPALIVED_SLAVE;
    }
    //END KEEPALIVED
    $network_services[] = $TAILSCALE;
    $network_services[] = $WSUSOFFLINE;
    $network_services[] = $APP_LINK_BALANCER;



    if($network_services_enabled){
        $html[]=@implode("\n", $network_services);
    }

    $members_services[]="<tr><td colspan=4 class=label style='text-align:left'><H2>{members_services}</td></tr>";
    $members_services[]=$ACTIVEDIRECTORY;
    $members_services[]=$ACTIVEDIRECTORY_AD_AGENT;
    $members_services[]=$APP_POSTGRESQL;
    $members_services[]=$APP_MANTICORE;
    $members_services[]=$APP_FREERADIUS;
    $members_services[]=$APP_MYSQL;
    $members_services[]=$OPENLDAP;
    $members_services[]=$OPENLDAP_REST;
    if($members_services_enabled){
        $html[]=@implode("\n", $members_services);
    }
    $network_monitoring[]="<tr><td colspan=4 class=label style='text-align:left'><H2>{monitoring}</td></tr>";
    $VMWARE_HOST=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("VMWARE_HOST");

    if($VMWARE_HOST==1){
        $network_monitoring[]=$APP_VMTOOLS;
    }

    $network_monitoring[]=$speedtest;
    $network_monitoring[]=$ADMIN_TRACK;
    $network_monitoring[]=$SYSTEM_RESTFULL;
    //$network_monitoring[]=$APP_CISCO_REPORTER;
    $network_monitoring[]=$APP_WAZHU;
   // $network_monitoring[]=$APP_LOKI;
    $network_monitoring[]=$APP_NAGIOS_CLIENT;
    $network_monitoring[]=$APP_RUSTDESK;
    $network_monitoring[]=$APP_PRADS;
    $network_monitoring[]=$APP_VNSTAT;
    $network_monitoring[]=$NETDATA;
    $network_monitoring[]=$APP_SMOKEPING;
    $network_monitoring[]=$DARKSTAT;
   // $network_monitoring[]=$APP_SYSLOG;
    $network_monitoring[]=$APP_LEGAL_LOGS_SERVER;
    $network_monitoring[]=$NTOPNG;
    $network_monitoring[]=$SPLUNK;
    $network_monitoring[]=$APP_ZABBIX_AGENT;
    $network_monitoring[]=$APP_SNMPD;
    $network_monitoring[]=$APP_GEOIP_UPDATES;

    if($network_monitoring_enabled){
        $html[]=@implode("\n", $network_monitoring);
    }

    $statistics[]="<tr><td colspan=4 class=label style='text-align:left'><H2>{statistics}</td></tr>";
    $statistics[]=$KSRN;
    $statistics[]=$CLOUD_CATEGORIES;
    $statistics[]=$APP_TAILON;
    $statistics[]=$APP_FRONTAIL_LINUX;
    $statistics[]=$APP_REDIS_SERVER;
    $statistics[]=$GLANCES;
    $statistics[]=$APP_MUNIN;
    $statistics[]=$APP_STATS_COMMUNICATOR;
    $statistics[]=$APP_FILEBEAT;
    $statistics[]=$ELASTICSEARCH;
    $statistics[]=$KIBANA;

    if($statistics_enabled){
        $html[]=@implode("\n", $statistics);
    }

    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){$Enablehacluster=1;}

    $network_security[]="<tr><td colspan=4 class=label style='text-align:left'><H2>{network_security}</td></tr>";
    if($Enablehacluster==0) {
        $network_security[] = $FIREHOL;
    }
    $network_security[]=$APP_CROWDSEC;
    $network_security[]=$APP_CYBERCRIMEIPFEEDS;
    $network_security[]=$FAIL2BAN;
    $network_security[]=$APP_NDPI;
    VERBOSE("APP_IPAUDIT: ".strlen($APP_IPAUDIT),__LINE__);
    $network_security[]=$APP_IPAUDIT;
    $network_security[]=$APP_NETMONIX;
    $network_security[]=$SURICATA;
    $network_security[]=$APP_CLAMAV;
    $network_security[]=$APP_3PROXY;
    $network_security[]=$APP_REDSOCKS;

    if($network_security_enabled){
        $html[]=@implode("\n", $network_security);
    }

    $data_transfer[]="<tr><td colspan=4 class=label style='text-align:left'><H2>{data_transfer}</td></tr>";
    $data_transfer[]=$PROFTPD;
    $data_transfer[]=$RSYNC;
    $data_transfer[]=$ISCSI_CLIENT;
    $data_transfer[]=$INSTANTSEARCH;
    $data_transfer[]=$APP_KLNAGENT;
    $data_transfer[]=$AUTOFS;
    $data_transfer[]=$APP_SYNO_BACKUP;

    if($data_transfer_enabled){
        $html[]=@implode("\n", $data_transfer);

    }


    $messaging_features[] = "<tr><td colspan=4 class=label style='text-align:left'><H2>{messaging_features}</td></tr>";
    $messaging_features[] = $POSTFIX;
    $messaging_features[] = $APP_OPENDKIM;
    $messaging_features[] = $APP_MILTERGREYLIST;
    $messaging_features[] = $APP_MILTER_REGEX;
    $messaging_features[] = $APP_MIMEDEFANG;
    $messaging_features[] = $APP_IMAPBOX;

    if(is_file("/etc/artica-postfix/ARTICA_REVERSE_PROXY_APPLIANCE")){
        $messaging_features=array();
    }



    if($SQUIDEnable==0) {
        if($Enablehacluster==0) {
            if ($messaging_services) {
                $html[] = @implode("\n", $messaging_features);
            }
        }
    }

    $DISABLE_SQUID=false;
    $DISABLE_HAPROXY=false;
    if($Enablehacluster==1){$DISABLE_SQUID=true;$DISABLE_HAPROXY=true;}


    if($EnablePostfix==0){
        $proxy_features[]="<tr><td colspan=4 class=label style='text-align:left'><H2>{proxy_features}</td></tr>";

        if(!$DISABLE_SQUID) {
            $proxy_features[] = $SQUID;
        }
        $proxy_features[] = $SQUID_MIKROTIK;
        $proxy_features[] = $SQUID_AD_RESTFULL;
        $proxy_features[] = $SQUID_RESTFULL;
        $proxy_features[] = $SQUID_ACLS;
        $proxy_features[] = $APP_GOSHIELD;
        $proxy_features[] = $SQUID_CACHE;
        $proxy_features[] = $SQUID_WCCP;
        $proxy_features[] = $SQUID_PARENTS;
        $proxy_features[] = $PROXY_PAC;
        $proxy_features[] = $CICAP;
        $proxy_features[] = $APP_KWTS_CONNECTOR;
        $proxy_features[] = $UFDBCAT;
        $proxy_features[]=$HA_CLUSTER;
        $proxy_features[]=$APP_GREENSQL;

        if(!$DISABLE_SQUID) {$proxy_features[]=$APP_PRIVOXY;}
        if(!$DISABLE_SQUID) {$proxy_features[]=$APP_WANPROXY;}
        if(!$DISABLE_SQUID) {$proxy_features[]=$APP_ZIPROXY;}
        $proxy_features[]=$APP_RDPPROXY;
        if(!$DISABLE_HAPROXY){
            $proxy_features[]=$HAPROXY;
            $proxy_features[]=$HAPROXY_EXCHANGE;
        }

        if(is_file("/etc/artica-postfix/ARTICA_REVERSE_PROXY_APPLIANCE")){
            $proxy_features=array();
        }
        if(is_file("/etc/artica-postfix/ARTICA_SMTP_APPLIANCE")){
            $proxy_features=array();
        }


        if($proxy_features_enabled){
            $html[]=@implode("\n", $proxy_features);

        }

    }



    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="</div>";
    $html[]="<script>";
    $btst=$tpl->_ENGINE_parse_body(@implode("",$bts));
    $btsmenus=urlencode(base64_encode($btst));
    $html[]="LoadAjaxSilent('bts-menus','$page?bts-menus=$btsmenus');
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";


    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function build_uri($array,$key=null){

    if($key<>"onlyactive"){
        $array["onlyactive"]=$_GET["onlyactive"];
    }
    foreach ($array as $xkey=>$value){
        if($xkey==$key){continue;}
        $uri_main[]=$value;
    }
    return "&".@implode("&", $uri_main);
}