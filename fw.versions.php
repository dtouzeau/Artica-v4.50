<?php
//SP119,SP206,SP28 HF
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.upload.handler.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["range1"])){dhcp_save();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["phpinfo-js"])){phpinfo_js();exit;}
if(isset($_GET["phpinfo-popup"])){phpinfo_popup();exit;}
if(isset($_GET["table-start"])){table_start();exit;}



if(isset($_GET["tabs"])){tabs();exit;}
page();


function phpinfo_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title="PHP v".PHP_VERSION;
    return $tpl->js_dialog2($title, "$page?phpinfo-popup=yes");
}

function phpinfo_popup():bool{
    $s=php_ini_path();
    $s=str_replace('<div class="center">', "", $s);
    $s=str_replace('<table>', "<table class=\"table table-striped\">", $s);
    $s=str_replace('<td class="e">', "<td nowrap>", $s);
    $s=str_replace('Winstead,', "Winstead,<br>", $s);
    $s=str_replace('Belski,', "Belski,<br>", $s);
    $s=str_replace('Rethans,', "Rethans,<br>", $s);
    $s=str_replace('Zarkos,', "Zarkos,<br>", $s);
    $s=str_replace('auth_plugin_mysql_clear_password,', "auth_plugin_mysql_clear_password,<br>", $s);
    $s=str_replace(":/usr","<br>/usr","$s");
    $s=str_replace(":/var","<br>/var","$s");
    echo $s;
    return true;
}



function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $REVERSE_APPLIANCE=false;
    if(is_file("/etc/artica-postfix/ARTICA_REVERSE_PROXY_APPLIANCE")){
        $REVERSE_APPLIANCE=true;
    }

    $SEE_PYTHON=true;

    if($REVERSE_APPLIANCE){
        $SEE_PYTHON=false;
    }
    if(is_file("/etc/artica-postfix/ARTICA_SMTP_APPLIANCE")){
        $SEE_PYTHON=false;
    }


    $array["{APP_ARTICA}"]="$page?table-start=yes";
    $array["{operating_system}"]="fw.versions.debian.php";
    if($SEE_PYTHON) {
        if (is_file("/home/artica/SQLITE/python-packages.db")) {
            $array["{python_packages}"] = "fw.versions.python.php";
        }
    }
    echo $tpl->tabs_default($array);
    return true;
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{packages_center}: {versions}","fa fa-info", "{fw_artica_versions_explain}","$page?tabs=yes","packages-center","progress-articaupd-restart",false,"table-loader-versions-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function TinyJS():string{
    $page=CurrentPageName();
    $tpl=new template_admin();


    $jsProgress=$tpl->framework_buildjs("/system/softwares/refresh",
    "UpdateReposIndex.progress","UpdateReposIndex.log",
    "progress-articaupd-restart",
    "LoadAjax('update-softwares-index','$page?table=yes');");

    $topbuttons[]=array($jsProgress,
        ico_refresh,"{update_softwares_index}","AsSystemAdministrator");

    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["TITLE"]="{packages_center}: {versions}";
    $TINY_ARRAY["ICO"]="fa fa-info";
    $TINY_ARRAY["EXPL"]="{fw_artica_versions_explain}";
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}

function table_start():bool{
    $page=CurrentPageName();
    echo "<div id='update-softwares-index'></div>
    <script>LoadAjax('update-softwares-index','$page?table=yes');</script>
        ";
    return true;
}

function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();

    $CURVER=@file_get_contents("VERSION");
    $UPDATES_ARRAY=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("v4softsRepo")));
    if(is_file("/etc/artica-postfix/ARTICA_REVERSE_PROXY_APPLIANCE")){
        $UPDATES_ARRAY["REVERSE_APPLIANCE"]=true;
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/harmp/status"));
    $DistriFullName="";
    if(property_exists($json,"DistributionName")){
        $DistriFullName=$json->DistributionName;
    }

    $html[]="<div id='system-progress-barr' class='row white-bg'></div>";
    $html[]="<table id='table-fw-versions' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize'>{software}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{version}</th>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_ARTICA}:</strong></td>";
    $html[]="<td>$CURVER</td>";
    $html[]="<td>&nbsp;</td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{OS}:</strong></td>";
    $html[]="<td>$DistriFullName</td>";
    $html[]="<td>&nbsp;</td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{kernel_version}:</strong></td>";
    $html[]="<td nowrap>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LinuxKernelVersion")."</td>";
    $html[]="<td>&nbsp;</td>";
    $html[]="</tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>PHP Version:</strong></td>";
    $html[]="<td>".$tpl->td_href(PHP_VERSION,"{information}","Loadjs('$page?phpinfo-js=yes')")."</td>";
    $html[]="<td>&nbsp;</td>";
    $html[]="</tr>";

    $html[]="<tr>";
    $html[]="<th colspan='3' nowrap style='background-color:#CCCCCC'><H2>{system}</H2></td>";
    $html[]="</tr>";
    $html[]=APP_NGINX_CONSOLE($UPDATES_ARRAY);
    $html[]=APP_GO_EXEC($UPDATES_ARRAY);
    $html[]=APP_AUTOFS($UPDATES_ARRAY);
    $html[]=APP_FIRMWARES($UPDATES_ARRAY);
    $html[]=PFRING($UPDATES_ARRAY);
    $html[]=APP_DOCKER($UPDATES_ARRAY);
    $html[]=APP_FIRECRACKER($UPDATES_ARRAY);

    $html[]=APP_IWCONFIG($UPDATES_ARRAY);
    $html[]=APP_JAVA($UPDATES_ARRAY);
    $html[]=APP_VMTOOLS($UPDATES_ARRAY);
    $html[]=APP_IETD($UPDATES_ARRAY);
    $html[]=APP_SNMPD($UPDATES_ARRAY);
    $html[]=APP_FPING($UPDATES_ARRAY);
    $html[]=APP_DBUS($UPDATES_ARRAY);
    $html[]=APP_XAPIAN($UPDATES_ARRAY);
    $html[]=APP_PHP_GEOIP2($UPDATES_ARRAY);
    $html[]=APP_ZABBIX_AGENT($UPDATES_ARRAY);
    $html[]=APP_SYNO_BACKUP($UPDATES_ARRAY);
    $html[]=APP_URBACKUP($UPDATES_ARRAY);
    $html[]=APP_SYSLOGD($UPDATES_ARRAY);
    $html[]=APP_CLAMAV($UPDATES_ARRAY);
    $html[]="<tr>";
    $html[]="<th colspan='3' nowrap style='background-color:#CCCCCC'><H2>{databases}</H2></td>";
    $html[]="</tr>";
   // $html[]=APP_LOKI($UPDATES_ARRAY);
    $html[]=APP_MYSQL($UPDATES_ARRAY);
    $html[]=APP_MANTICORE($UPDATES_ARRAY);
    $html[]=APP_POSTGRES($UPDATES_ARRAY);
    $html[]=APP_ELASTICSEARCH($UPDATES_ARRAY);
    $html[]=APP_FILEBEAT($UPDATES_ARRAY);

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("openldap_installed"))==1){
        $html[]="<tr>";
        $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_OPENLDAP}:</strong></td>";
        $html[]="<td nowrap>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("openldap_version")."</td>";
        $html[]="<td>&nbsp;</td>";
        $html[]="</tr>";

    }
    $OPENSSH_VER=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OPENSSH_VER");
    $html[]=APP_REDIS_SERVER($UPDATES_ARRAY);
    $html[]="<tr>";
    $html[]="<th colspan='3' nowrap style='background-color:#CCCCCC'><H2>{network_services}</H2></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_OPENSSH}:</strong></td>";
    $html[]="<td>$OPENSSH_VER</td>";
    $html[]="<td>&nbsp;</td>";
    $html[]="</tr>";
    $html[]=APP_SSHPORTAL($UPDATES_ARRAY);
    $html[]=APP_SHELLINABOX($UPDATES_ARRAY);
    $html[]=APP_RUSTDESK($UPDATES_ARRAY);
    $html[]=APP_FRONTAIL_LINUX($UPDATES_ARRAY);
    $html[]=APP_TAILON($UPDATES_ARRAY);
    $html[]=APP_DHCP($UPDATES_ARRAY);

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TFTPD_INSTALLED"))==1){
        $html[]="<tr>";
        $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_TFTPD}:</strong></td>";
        $html[]="<td nowrap>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("TFTPD_VERSION")."</td>";
        $html[]="<td>&nbsp;</td>";
        $html[]="</tr>";
    }
    $html[]=APP_PROFTPD($UPDATES_ARRAY);


    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FREERADIUS_INSTALLED"))==1){
        $html[]="<tr>";
        $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_FREERADIUS}:</strong></td>";
        $html[]="<td nowrap>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FREERADIUS_VERSION")."</td>";
        $html[]="<td>&nbsp;</td>";
        $html[]="</tr>";
    }

    $html[]=APP_SAMBA($UPDATES_ARRAY);

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDInstalled"))==1){
        $html[]="<tr>";
        $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_NTPD}:</strong></td>";
        $html[]="<td nowrap>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDVersion")."</td>";
        $html[]="<td>&nbsp;</td>";
        $html[]="</tr>";
    }

    $html[]=APP_MSKTUTIL($UPDATES_ARRAY);
    $html[]=APP_KLNAGENT($UPDATES_ARRAY);
    $html[]=APP_TAILSCALE($UPDATES_ARRAY);
    $html[]=APP_OPENVPN($UPDATES_ARRAY);
    $html[]=APP_PPTP_CLIENT($UPDATES_ARRAY);
    $html[]=APP_STRONGSWAN($UPDATES_ARRAY);
    $html[]=APP_WSUSOFFLINE($UPDATES_ARRAY);
    $html[]=APP_SPLUNK_FORWARDER($UPDATES_ARRAY);
    $html[]=APP_QAT($UPDATES_ARRAY);


    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RSYNC_INSTALLED"))==1){
        $html[]="<tr>";
        $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_RSYNC}:</strong></td>";
        $html[]="<td nowrap>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RSYNC_VERSION")."</td>";
        $html[]="<td>&nbsp;</td>";
        $html[]="</tr>";
    }
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CURLFTPFS_INSTALLED"))==1){
        $html[]="<tr>";
        $html[]="<td style='width:1%;text-align:right' nowrap><strong>CurlFtpFS:</strong></td>";
        $html[]="<td nowrap>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CURLFTPFS_VERSION")."</td>";
        $html[]="<td>&nbsp;</td>";
        $html[]="</tr>";
    }

    $MESSAGING=true;
    $DNS = true;
    $PROXY=true;
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){$MESSAGING=false;}
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){$DNS=false;}
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){$PROXY=false;}

    $html[]="<tr>";
    $html[]="<th colspan='3' nowrap style='background-color:#CCCCCC'><H2>{web_services}</H2></td>";
    $html[]="</tr>";
    $html[]=APP_NGINX($UPDATES_ARRAY);
    $html[]=WP_CLIENT($UPDATES_ARRAY);
    $html[]=APP_PHP_REVERSE($UPDATES_ARRAY);
    if($MESSAGING) {
        $html[] = "<tr>";
        $html[] = "<th colspan='3' nowrap style='background-color:#CCCCCC'><H2>{messaging}</H2></td>";
        $html[] = "</tr>";
        $html[] = APP_POSTFIX($UPDATES_ARRAY);
        $html[] = APP_MSMTP($UPDATES_ARRAY);
        $html[] = APP_MILTER_GREYLIST($UPDATES_ARRAY);
        $html[] = APP_MILTER_REGEX($UPDATES_ARRAY);
        $html[] = APP_MIMEDEFANG($UPDATES_ARRAY);
        $html[] = APP_RBLDNSD($UPDATES_ARRAY);
        $html[] = APP_CYRUS($UPDATES_ARRAY);
    }
    if($DNS) {
        $html[] = "<tr>";
        $html[] = "<th colspan='3' nowrap style='background-color:#CCCCCC'><H2>{DNS_SERVICES}</H2></td>";
        $html[] = "</tr>";
        $html[] = APP_DNSCRYPT_PROXY($UPDATES_ARRAY);
        $html[] = APP_UNBOUND($UPDATES_ARRAY);
        $html[] = APP_DNSDIST($UPDATES_ARRAY);
        $html[] = APP_DNSDIST9($UPDATES_ARRAY);
        $html[] = APP_DSC($UPDATES_ARRAY);
        $html[] = APP_PDNS($UPDATES_ARRAY);
        $html[] = APP_DOH_PROXY($UPDATES_ARRAY);
        $html[] = APP_CLOUDFLARE_DNS($UPDATES_ARRAY);
        $html[] = APP_DDNS_AGENT($UPDATES_ARRAY);
    }

    $html[]="<tr>";
    $html[]="<th colspan='3' nowrap style='background-color:#CCCCCC'><H2>{APP_FIREWALL}</H2></td>";
    $html[]="</tr>";
    $IPTABLES_VERSION   = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPTABLES_VERSION");
    $bton=$tpl->icon_nothing();
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_IPTABLES}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$IPTABLES_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";


    $APP_XTABLES_VERSION=$tpl->icon_nothing();
    if(isset($UPDATES_ARRAY["APP_XTABLES"])){
        $APP_XTABLES_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_XTABLES_VERSION");
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_XTABLES');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_XTABLES}:</strong></td>";
    $html[]="<td nowrap>$APP_XTABLES_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";

    $APP_NDPI_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NDPI_INSTALLED"))==1){
        $APP_NDPI_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NDPI_VERSION");
    }
    if(isset($UPDATES_ARRAY["APP_NDPI"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_NDPI');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_NDPI}:</strong></td>";
    $html[]="<td nowrap>$APP_NDPI_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";

    $warn_ico="";
    $APP_CROWDSEC_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_CROWDSEC_INSTALLED"))==1){
        $APP_CROWDSEC_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_CROWDSEC_VERSION");
    }
    if(isset($UPDATES_ARRAY["APP_CROWDSEC"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_CROWDSEC');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");

        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_CROWDSEC",
                "TOKEN_VER"=>"APP_CROWDSEC_VERSION",
                "TOKEN_ENABLED"=>"EnableCrowdSec")
        );
        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }
    }

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_CROWDSEC}:</strong></td>";
    $html[]="<td nowrap>$APP_CROWDSEC_VERSION$warn_ico</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";
    $html[]=APP_FAIL2BAN($UPDATES_ARRAY);


    $SURICATA_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SURICATA_INSTALLED"))==1) {
        $SURICATA_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SURICATA_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_SURICATA"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_SURICATA');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_SURICATA}:</strong></td>";
    $html[]="<td nowrap>$SURICATA_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";
    $html[]=APP_NETMONIX($UPDATES_ARRAY);

//--------------------------------------------------------------------------------------------------
    $APP_SAMHAIN_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SAMHAIN_INSTALLED"))==1) {
        $APP_SAMHAIN_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SAMHAIN_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_SAMHAIN"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_SAMHAIN');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_SAMHAIN}:</strong></td>";
    $html[]="<td nowrap>$APP_SAMHAIN_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";

//--------------------------------------------------------------------------------------------------
    $APP_KEEPALIVED_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_INSTALLED"))==1) {
        $APP_KEEPALIVED_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_KEEPALIVED"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_KEEPALIVED');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_KEEPALIVED}:</strong></td>";
    $html[]="<td nowrap>$APP_KEEPALIVED_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";


    $APP_FAILOVER_CHECKER_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_INSTALLED"))==1) {
        $APP_FAILOVER_CHECKER_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("go-failover-checker-ver");
    }

    if(isset($UPDATES_ARRAY["APP_FAILOVER_CHECKER"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_FAILOVER_CHECKER');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_KEEPALIVED} Checker:</strong></td>";
    $html[]="<td nowrap>$APP_FAILOVER_CHECKER_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";

//--------------------------------------------------------------------------------------------------




    $FIREQOS_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FIREQOS_INSTALLED"))==1){
        $FIREQOS_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FIREQOS_VERSION");
    }
    if(isset($UPDATES_ARRAY["APP_FIREHOL"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_FIREHOL');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_FIREQOS}:</strong></td>";
    $html[]="<td nowrap>$FIREQOS_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<th colspan='3' nowrap style='background-color:#CCCCCC'><H2>{monitoring}</H2></td>";
    $html[]="</tr>";
    //-------------------------------------------------------------------------------------------
    $warn_ico=null;
    $APP_MONIT_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MONIT_INSTALLED"))==1){
        $APP_MONIT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MONIT_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_MONIT"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_MONIT');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");

        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_MONIT",
                "TOKEN_VER"=>"APP_MONIT_VERSION",
                "TOKEN_ENABLED"=>"MONIT_INSTALLED"),false
        );

        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }

    }
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_MONIT}:</strong></td>";
    $html[]="<td nowrap>$APP_MONIT_VERSION$warn_ico</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";

//--------------------------------------------------------------------------------------------
    $APP_WAZHU_VERSION=$tpl->icon_nothing();
    $warn_ico=null;
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_WAZHU_INSTALLED"))==1){
        $APP_WAZHU_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_WAZHU_VERSION");
    }



    if(isset($UPDATES_ARRAY["APP_WAZHU"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_WAZHU');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");

        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_WAZHU",
                "TOKEN_VER"=>"APP_WAZHU_VERSION",
                "TOKEN_ENABLED"=>"EnableWazhuCLient")
        );

        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }
    }

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_WAZHU}:</strong></td>";
    $html[]="<td nowrap>$APP_WAZHU_VERSION$warn_ico</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";

//--------------------------------------------------------------------------------------------
    $APP_NAGIOS_CLIENT_VERSION=$tpl->icon_nothing();
    $warn_ico=null;
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NAGIOS_CLIENT_INSTALLED"))==1){
        $APP_NAGIOS_CLIENT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NAGIOS_CLIENT_VERSION");
    }



    if(isset($UPDATES_ARRAY["APP_NAGIOS_CLIENT"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_NAGIOS_CLIENT');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");

        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_NAGIOS_CLIENT",
                "TOKEN_VER"=>"APP_NAGIOS_CLIENT_VERSION",
                "TOKEN_ENABLED"=>"EnableNagiosClient")
        );

        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }
    }

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_NAGIOS_CLIENT}:</strong></td>";
    $html[]="<td nowrap>$APP_NAGIOS_CLIENT_VERSION$warn_ico</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";

//--------------------------------------------------------------------------------------------

    $html[]=APP_VNSTAT($UPDATES_ARRAY);


    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_INSTALLED"))==1){
        $html[]="<tr>";
        $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_MUNIN}:</strong></td>";
        $html[]="<td nowrap>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_VERSION")."</td>";
        $html[]="<td>&nbsp;</td>";
        $html[]="</tr>";
    }

    $warn_ico=null;
    $APP_NETDATA_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetDataInstalled"))==1){
        $APP_NETDATA_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NETDATA_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_NETDATA"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_NETDATA');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");


        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_NETDATA",
                "TOKEN_VER"=>"APP_NETDATA_VERSION",
                "TOKEN_ENABLED"=>"NetDataInstalled"),false
        );

        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }
    }

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_NETDATA} (NetData):</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_NETDATA_VERSION$warn_ico</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";

    $html[]=APP_NTOPNG($UPDATES_ARRAY);

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPAUDIT_INSTALLED"))==1){
        $html[]="<tr>";
        $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_IPAUDIT}:</strong></td>";
        $html[]="<td nowrap>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("IpAuditVersion")."</td>";
        $html[]="<td>&nbsp;</td>";
        $html[]="</tr>";
    }

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NMAPInstalled"))==1){
        $html[]="<tr>";
        $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_NMAP}:</strong></td>";
        $html[]="<td nowrap>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NMAPVersion")."</td>";
        $html[]="<td>&nbsp;</td>";
        $html[]="</tr>";
    }
    if($PROXY) {
        $html[] = "<tr>";
        $html[] = "<th colspan='3' nowrap style='background-color:#CCCCCC'><H2>{proxy_services}</H2></td>";
        $html[] = "</tr>";


//------------------------------------------------------------------------------------------------------
        $bton = $tpl->icon_nothing();
        $warn_ico = null;
        $SquidRealVersion = $tpl->icon_nothing();
        if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_INSTALLED")) == 1) {
            $SquidRealVersion = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRealVersion");
        }
        if (!preg_match("#^6\.#", $SquidRealVersion)) {
            $SquidRealVersion = $tpl->icon_nothing();
        }

        if (isset($UPDATES_ARRAY["APP_SQUID6"])) {
            $bton = $tpl->button_autnonome("{install_upgrade2}",
                "Loadjs('fw.system.upgrade-software.php?product=APP_SQUID6');",
                "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");


            $RESULTS = $tpl->NOTIF_ARRAY(
                array("UPDATES_ARRAY" => $UPDATES_ARRAY,
                    "TOKEN_UPDATE_ARRAY" => "APP_SQUID6",
                    "TOKEN_VER" => "SquidRealVersion",
                    "TOKEN_ENABLED" => "SQUIDEnable")
            );


            if (isset($RESULTS["NEW_VER"])) {
                $warn_ico = "&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
            }

        }

        $html[] = "<tr>";
        $html[] = "<td style='width:1%;text-align:right' nowrap><strong>{APP_SQUID6}:</strong></td>";
        $html[] = "<td nowrap>$SquidRealVersion$warn_ico</td>";
        $html[] = "<td>$bton</td>";
        $html[] = "</tr>";
        $bton = $tpl->icon_nothing();
        $warn_ico = null;
        $SquidRealVersion = $tpl->icon_nothing();
        if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_INSTALLED")) == 1) {
            $SquidRealVersion = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRealVersion");
        }
        if (!preg_match("#^5\.#", $SquidRealVersion)) {
            $SquidRealVersion = $tpl->icon_nothing();
        }

        if (isset($UPDATES_ARRAY["APP_SQUID"])) {
            $bton = $tpl->button_autnonome("{install_upgrade2}",
                "Loadjs('fw.system.upgrade-software.php?product=APP_SQUID');",
                "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");


            $RESULTS = $tpl->NOTIF_ARRAY(
                array("UPDATES_ARRAY" => $UPDATES_ARRAY,
                    "TOKEN_UPDATE_ARRAY" => "APP_SQUID",
                    "TOKEN_VER" => "SquidRealVersion",
                    "TOKEN_ENABLED" => "SQUIDEnable")
            );


            if (isset($RESULTS["NEW_VER"])) {
                $warn_ico = "&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
            }

        }

        $html[] = "<tr>";
        $html[] = "<td style='width:1%;text-align:right' nowrap><strong>{APP_SQUID} 5.x:</strong></td>";
        $html[] = "<td nowrap>$SquidRealVersion$warn_ico</td>";
        $html[] = "<td>$bton</td>";
        $html[] = "</tr>";
//------------------------------------------------------------------------------------------------------
        $bton = $tpl->icon_nothing();
        $SquidRealVersion = $tpl->icon_nothing();
        if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_INSTALLED")) == 1) {
            $SquidRealVersion = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRealVersion");
        }

        if (preg_match("#^([0-9]+)\.#", $SquidRealVersion, $re)) {
            if (intval($re[1]) > 4) {
                $SquidRealVersion = $tpl->icon_nothing();
            }
        }

        if (isset($UPDATES_ARRAY["APP_SQUID_REV4"])) {
            $bton = $tpl->button_autnonome("{install_upgrade2}",
                "Loadjs('fw.system.upgrade-software.php?product=APP_SQUID_REV4');",
                "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");
        }

        $html[] = "<tr>";
        $html[] = "<td style='width:1%;text-align:right' nowrap><strong>{APP_SQUID_REV4}:</strong></td>";
        $html[] = "<td nowrap>$SquidRealVersion</td>";
        $html[] = "<td>$bton</td>";
        $html[] = "</tr>";
//-------------------------------------------------------------------------------------------------------------------


    }
    $html[]=APP_UFDBGUARDD($UPDATES_ARRAY);
    $html[]=APP_HAPROXY($UPDATES_ARRAY);
    $html[]=APP_GREENSQL($UPDATES_ARRAY);
    $html[]=APP_RDPPROXY($UPDATES_ARRAY);
    $html[]=APP_3PROXY($UPDATES_ARRAY);
    $html[]=APP_REDSOCKS($UPDATES_ARRAY);
    $html[]=APP_WANPROXY($UPDATES_ARRAY);
    $html[]=APP_C_ICAP($UPDATES_ARRAY);
    $html[]=APP_GO_SHIELD_SERVER($UPDATES_ARRAY);
    $html[]=APP_GO_SHIELD_CONNECTOR($UPDATES_ARRAY);
    $html[]=APP_GO_EXEC($UPDATES_ARRAY);
    $html[]=APP_GO_AD_GROUP_SEARCH($UPDATES_ARRAY);
    $html[]=APP_GO_HOTSPOT_ENGINE($UPDATES_ARRAY);
    $html[]=APP_GO_PAC_ENGINE($UPDATES_ARRAY);
    $html[]=APP_GO_WEBFITLER_ERROR_PAGE_ENGINE($UPDATES_ARRAY);
    $html[]=APP_ADAGENT_CONNECTOR($UPDATES_ARRAY);
    $html[]=APP_ADAGENT_LBL($UPDATES_ARRAY);

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='3'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $TinyJS=TinyJS();

    $html[]="<script>";
    $html[]=$TinyJS;
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-fw-versions').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function php_ini_path():string{
    ob_start();
    phpinfo();
    $s = ob_get_contents();
    ob_end_clean();
    if(preg_match("#<body>(.*?)</body>#is", $s,$re)){$s=$re[1];}
    return $s;
}

function MEMCACHED($UPDATES_ARRAY=array()):string{
    $tpl=new template_admin();
    $APP_MEMCACHED_VERSION=$tpl->icon_nothing();
    $warn_ico=null;
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MEMCACHED_INSTALLED"))==1){
        $APP_MEMCACHED_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MEMCACHED_VERSION");
    }


    $bton=$tpl->button_autnonome("{install_upgrade2}",
        "Loadjs('fw.apt-memcached.php');",
        "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_MEMCACHED",
            "TOKEN_VER"=>"APP_MEMCACHED_VERSION",
            "TOKEN_ENABLED"=>""),false);

    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }


    if(isset($UPDATES_ARRAY["APP_MEMCACHED"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_MEMCACHED');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }



    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_MEMCACHED}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_MEMCACHED_VERSION$warn_ico</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_FIRECRACKER($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $APP_VERSION=$tpl->icon_nothing();
    $warn_ico=null;
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_FIRECRACKER_INSTALLED"))==1){
        $APP_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_FIRECRACKER_VERSION");
    }


    $bton="";
    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_FIRECRACKER",
            "TOKEN_VER"=>"APP_FIRECRACKER_VERSION",
            "TOKEN_ENABLED"=>"EnableFireCracker"),false);

    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }


    if(isset($UPDATES_ARRAY["APP_FIRECRACKER"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_FIRECRACKER');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_FIRECRACKER}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_VERSION$warn_ico</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_NETMONIX($UPDATES_ARRAY):string
{
    $warn_ico="";
    $tpl = new template_admin();
    $bton = $tpl->icon_nothing();
    $html = array();
    if (isset($UPDATES_ARRAY["APP_NETMONIX"])) {
        $bton = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_NETMONIX');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");
    }
    $RESULTS=$tpl->NOTIF_ARRAY(array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_NETMONIX",
            "TOKEN_VER"=>"NDPID_VERSION",
            "TOKEN_ENABLED"=>"EnableNetMonix"),false);

    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }



    $NDPID_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NDPID_VERSION");

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_NETMONIX}:</strong></td>";
    $html[]="<td nowrap>$NDPID_VERSION$warn_ico</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_GO_AD_GROUP_SEARCH($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton=$tpl->icon_nothing();
    $html=array();
    if(isset($UPDATES_ARRAY["APP_GO_AD_GROUP_SEARCH"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_GO_AD_GROUP_SEARCH');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"))==1){
        $libmem=new lib_memcached();
        $Go_Shield_ADG_Version=trim($libmem->getKey("Go-Squid-Auth-Version"));
        if(trim($Go_Shield_ADG_Version==null)){
            $Go_Shield_ADG_Version="0.0.0";
        }
        $html[]="<tr>";
        $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_GO_AD_GROUP_SEARCH}:</strong></td>";
        $html[]="<td nowrap>".$Go_Shield_ADG_Version."</td>";
        $html[]="<td>$bton</td>";
        $html[]="</tr>";
    }
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_AUTOFS($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $bton="";
    $APP_AUTOFS_VERSION=$tpl->icon_nothing();
    $warn_ico=null;
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AutoFSInstalled"))==1){
        $APP_AUTOFS_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_AUTOFS_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_AUTOFS"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_AUTOFS');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_AUTOFS",
            "TOKEN_VER"=>"APP_AUTOFS_VERSION",
            "TOKEN_ENABLED"=>""),false);


    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning ".ico_emergency."'></i>";
    }

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{automount_center}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_AUTOFS_VERSION$warn_ico</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);

}

function APP_FIRMWARES($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $APP_FIRMWARES_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_FIRMWARES_INSTALLED"))==1){
        $APP_FIRMWARES_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_FIRMWARES_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_FIRMWARES"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_FIRMWARES');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_FIRMWARES}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_FIRMWARES_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);

}
function APP_DOCKER($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $EnableDockerManagement=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDockerManagement"));
    if($EnableDockerManagement==0){return "";}
        $warn_ico = null;
        $bton = $tpl->icon_nothing();
        $APP_DOCKER_VERSION = $tpl->icon_nothing();
        if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOCKER_INSTALLED")) == 1) {
            $APP_DOCKER_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOCKER_VERSION");
        }

        if (isset($UPDATES_ARRAY["APP_DOCKER"])) {
            $bton = $tpl->button_autnonome("{install_upgrade2}",
                "Loadjs('fw.system.upgrade-software.php?product=APP_DOCKER');",
                "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");


            $RESULTS = $tpl->NOTIF_ARRAY(
                array("UPDATES_ARRAY" => $UPDATES_ARRAY,
                    "TOKEN_UPDATE_ARRAY" => "APP_DOCKER",
                    "TOKEN_VER" => "APP_DOCKER_VERSION",
                    "TOKEN_ENABLED" => "EnableDockerService")
            );

            if (isset($RESULTS["NEW_VER"])) {
                $warn_ico = "&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
            }


        }

        $html[]="<tr>";
        $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_DOCKER}:</strong></td>";
        $html[]="<td nowrap>$APP_DOCKER_VERSION$warn_ico</td>";
        $html[]="<td>$bton</td>";
        $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_IWCONFIG($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_IWCONFIG=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IWCONFIG_INSTALLED"))==1){$APP_IWCONFIG=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("IWCONFIG_VERSION");}


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_IWCONFIG}:</strong></td>";
    $html[]="<td nowrap>$APP_IWCONFIG</td>";
    $html[]="<td>&nbsp;</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_JAVA($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $JAVA_VERSION=$tpl->icon_nothing();

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("JAVA_INSTALLED"))==1){$JAVA_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("JAVA_VERSION");}
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_JAVA}:</strong></td>";
    $html[]="<td nowrap>$JAVA_VERSION</td>";
    $html[]="<td>&nbsp;</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_VMTOOLS($UPDATES_ARRAY):string{

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VMWARE_TOOLS_INSTALLED"))==0){return "";}
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_VMTOOLS}:</strong></td>";
    $html[]="<td nowrap>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("VMWARE_TOOLS_VERSION")."</td>";
    $html[]="<td>&nbsp;</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_IETD($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ISCSI_CLIENT_INSTALLED"))==0){return "";}
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_IETD}:</strong></td>";
    $html[]="<td nowrap>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ISCSI_VERSION")."</td>";
    $html[]="<td>&nbsp;</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_NGINX_CONSOLE($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $warn_ico=null;
    $bton=$tpl->icon_nothing();
    $APP_NGINX_CONSOLE_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_CONSOLE_VERSION");



    if(isset($UPDATES_ARRAY["APP_NGINX_CONSOLE"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_NGINX_CONSOLE');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_NGINX_CONSOLE",
            "TOKEN_VER"=>"APP_NGINX_CONSOLE_VERSION",
            "TOKEN_ENABLED"=>""),false);

    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_NGINX_CONSOLE}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_NGINX_CONSOLE_VERSION$warn_ico</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
        $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_SNMPD($UPDATES_ARRAY):string{
    $tpl=new template_admin();

    $SNMPD_VERSION=$tpl->icon_nothing();
    $SNMPD_BTN=$tpl->icon_nothing();

    if(isset($UPDATES_ARRAY["APP_SNMPD"])) {
        $SNMPD_BTN = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_SNMPD');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");
    }


    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPD_INSTALLED"))==1){ $SNMPD_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPD_VERSION"); }
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_SNMPD}:</strong></td>";
    $html[]="<td nowrap>$SNMPD_VERSION</td>";
    $html[]="<td>$SNMPD_BTN</td>";
$html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_FPING($UPDATES_ARRAY):string{
        $tpl=new template_admin();
    $FPING_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FPING_INSTALLED"))==1){
        $FPING_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FPING_VERSION");
    }
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_FPING}:</strong></td>";
    $html[]="<td nowrap>$FPING_VERSION</td>";
    $html[]="<td>&nbsp;</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_DBUS($UPDATES_ARRAY):string{
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_DBUS}:</strong></td>";
    $html[]="<td nowrap>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("dbus_daemon_version")."</td>";
    $html[]="<td>&nbsp;</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_XAPIAN($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
$XAPIAN_OMINDEX_VERSION=$tpl->icon_nothing();
if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XAPIAN_PHP_INSTALLED"))==1){
    $XAPIAN_OMINDEX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("XAPIAN_OMINDEX_VERSION");
}
$html[]="<tr>";
$html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_XAPIAN}:</strong></td>";
$html[]="<td nowrap>$XAPIAN_OMINDEX_VERSION</td>";
$html[]="<td>&nbsp;</td>";
$html[]="</tr>";if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_PHP_GEOIP2($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $bton=$tpl->icon_nothing();
    $APP_PHP_GEOIP2_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PHP_GEOIP_INSTALLED"))==1){
        $APP_PHP_GEOIP2_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("GEOIPUPDATE_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_PHP_GEOIP2"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_PHP_GEOIP2');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_PHP_GEOIP2}:</strong></td>";
    $html[]="<td nowrap>$APP_PHP_GEOIP2_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_ZABBIX_AGENT($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $warn_ico=null;
    $bton=$tpl->icon_nothing();
    $APP_ZABBIX_AGENT_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_ZABBIX_AGENT_INSTALLED"))==1){
        $APP_ZABBIX_AGENT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_ZABBIX_AGENT_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_ZABBIX_AGENT"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_ZABBIX_AGENT');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");


        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_ZABBIX_AGENT",
                "TOKEN_VER"=>"APP_ZABBIX_AGENT_VERSION",
                "TOKEN_ENABLED"=>"EnableZabbixAgent")
        );

        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }


    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_ZABBIX_AGENT}:</strong></td>";
    $html[]="<td nowrap>$APP_ZABBIX_AGENT_VERSION$warn_ico</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_SYNO_BACKUP($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $bton=$tpl->icon_nothing();
    $APP_SYNO_BACKUP_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SYNO_BACKUP_INSTALLED"))==1){
        $APP_SYNO_BACKUP_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SYNO_BACKUP_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_SYNO_BACKUP"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_SYNO_BACKUP');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_SYNO_BACKUP}:</strong></td>";
    $html[]="<td nowrap>$APP_SYNO_BACKUP_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_URBACKUP($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton=$tpl->icon_nothing();
    $APP_URBACKUP_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_URBACKUP_INSTALLED"))==1){
        $APP_URBACKUP_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_URBACKUP_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_URBACKUP"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_URBACKUP');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_URBACKUP}:</strong></td>";
    $html[]="<td nowrap>$APP_URBACKUP_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_SYSLOGD($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $warn_ico=null;
    $APP_RSYSLOGD_VERSION=$tpl->icon_nothing();
    $bton = $tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RSYSLOGD_INSTALLED"))==1){
        $APP_RSYSLOGD_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SYSLOGD_VERSION");
    }

    if (isset($UPDATES_ARRAY["APP_SYSLOGD"])) {
        $bton = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_SYSLOGD');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");

        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_SYSLOGD",
                "TOKEN_VER"=>"APP_SYSLOGD_VERSION",
                "TOKEN_ENABLED"=>"ALWAYS")
        );


        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }

    }

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_RSYSLOG}:</strong></td>";
    $html[]="<td nowrap>$APP_RSYSLOGD_VERSION$warn_ico</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_CLAMAV($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $warn_ico=null;
    $bton=$tpl->icon_nothing();
    $ClamAVDaemonVersion=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamAVDaemonInstalled"))==1){
        $ClamAVDaemonVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamAVDaemonVersion");
    }

    if(isset($UPDATES_ARRAY["APP_CLAMAV"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_CLAMAV');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_CLAMAV",
            "TOKEN_VER"=>"ClamAVDaemonVersion",
            "TOKEN_ENABLED"=>"EnableClamavDaemon")
    );

    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_CLAMAV}:</strong></td>";
    $html[]="<td nowrap>$ClamAVDaemonVersion$warn_ico</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_LOKI($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $warn_ico=null;
    $bton=$tpl->icon_nothing();
    $DaemonVersion=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LokiInstalled"))==1){
        $DaemonVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LokiVersion");
    }

    if(isset($UPDATES_ARRAY["APP_LOKI"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_LOKI');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_LOKI",
            "TOKEN_VER"=>"LokiVersion",
            "TOKEN_ENABLED"=>"EnableLokiDB")
    );

    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_LOKI}:</strong></td>";
    $html[]="<td nowrap>$DaemonVersion$warn_ico</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_MYSQL($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $warn_ico=null;
    $bton="&nbsp;";
    $APP_MYSQL_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MYSQL_INSTALLED"))==1){
        $APP_MYSQL_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MYSQL_VERSION");
    }


    if(isset($UPDATES_ARRAY["APP_MYSQL"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_MYSQL');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");

        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_MYSQL",
                "TOKEN_VER"=>"APP_MYSQL_VERSION",
                "TOKEN_ENABLED"=>"EnableMySQL")
        );


        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }


    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_MYSQL}:</strong></td>";
    $html[]="<td nowrap>$APP_MYSQL_VERSION$warn_ico</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_MANTICORE($UPDATES_ARRAY):string{
    $warn_ico="";
    $bton="";
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_MANTICORE_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MANTICORE_INSTALLED"))==1){
        $APP_MANTICORE_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MANTICORE_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_MANTICORE"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_MANTICORE');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");


        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MANTICORE_INSTALLED"))==1) {
            $RESULTS = $tpl->NOTIF_ARRAY(
                array("UPDATES_ARRAY" => $UPDATES_ARRAY,
                    "TOKEN_UPDATE_ARRAY" => "APP_MANTICORE",
                    "TOKEN_VER" => "APP_MANTICORE_VERSION",
                    "TOKEN_ENABLED" => "MantiCoreSearchEnabled")
            );
        }


        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }


    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_MANTICORE}:</strong></td>";
    $html[]="<td nowrap>$APP_MANTICORE_VERSION$warn_ico</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_PHP_REVERSE($UPDATES_ARRAY):string{
    $bton="";
    $warn_ico="";
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_PHP_REVERSE_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_PHP_REVERSE_INSTALLED"))==1){
        $APP_PHP_REVERSE_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_PHP_REVERSE_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_PHP_REVERSE"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_PHP_REVERSE');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");


        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_PHP_REVERSE_INSTALLED"))==1) {
            $RESULTS = $tpl->NOTIF_ARRAY(
                array("UPDATES_ARRAY" => $UPDATES_ARRAY,
                    "TOKEN_UPDATE_ARRAY" => "APP_PHP_REVERSE",
                    "TOKEN_VER" => "APP_PHP_REVERSE_VERSION",
                    "TOKEN_ENABLED" => "PHPReverseEnabled")
            );
        }

        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_PHP_REVERSE}:</strong></td>";
    $html[]="<td nowrap>$APP_PHP_REVERSE_VERSION$warn_ico</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}




function APP_POSTGRES($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton="&nbsp;";
    $APP_POSTGRES_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_POSTGRES_INSTALLED"))==1){
        $APP_POSTGRES_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_POSTGRES_VERSION");
    }


    if(isset($UPDATES_ARRAY["APP_POSTGRES"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_POSTGRES');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_POSTGRES}:</strong></td>";
    $html[]="<td nowrap>$APP_POSTGRES_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_ELASTICSEARCH($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
        $tpl=new template_admin();
    $bton=$tpl->icon_nothing();
    $ELASTICSEARCH_VERSION=$tpl->icon_nothing();
    $APP_KIBANA_VERSION=$tpl->icon_nothing();

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ELASTICSEARCH_INSTALLED"))==1){
        $ELASTICSEARCH_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ELASTICSEARCH_VERSION");
    }
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KIBANA_INSTALLED"))==1){
        $APP_KIBANA_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KIBANA_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_ELASTICSEARCH"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_ELASTICSEARCH');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_ELASTICSEARCH}:</strong></td>";
    $html[]="<td nowrap>$ELASTICSEARCH_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_KIBANA}:</strong></td>";
    $html[]="<td nowrap>$APP_KIBANA_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_FILEBEAT($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $bton=$tpl->icon_nothing();
    $FILEBEAT_VERSION=$tpl->icon_nothing();



    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FILEBEAT_INSTALLED"))==1){
        $FILEBEAT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FILEBEAT_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_FILEBEAT"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_FILEBEAT');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_FILEBEAT}:</strong></td>";
    $html[]="<td nowrap>$FILEBEAT_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_REDIS_SERVER($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $bton=$tpl->icon_nothing();
    $APP_REDIS_SERVER_VERSION=$tpl->icon_nothing();

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_REDIS_SERVER_INSTALLED"))==1){
        $APP_REDIS_SERVER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_REDIS_SERVER_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_REDIS_SERVER"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_REDIS_SERVER');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_REDIS_SERVER}:</strong></td>";
    $html[]="<td nowrap>$APP_REDIS_SERVER_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_SSHPORTAL($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_SSHPORTAL_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SSHPORTAL_INSTALLED"))==1){
        $APP_SSHPORTAL_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SSHPORTAL_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_SSHPORTAL"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_SSHPORTAL');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_SSHPORTAL}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_SSHPORTAL_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_SHELLINABOX($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_SHELLINABOX_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SHELLINABOX_INSTALLED"))==1){
        $APP_SHELLINABOX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SHELLINABOX_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_SHELLINABOX"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_SHELLINABOX');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_SHELLINABOX}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_SHELLINABOX_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_RUSTDESK($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_RUSTDESK_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RUSTDESK_INSTALLED"))==1){
        $APP_RUSTDESK_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RUSTDESK_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_RUSTDESK"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_RUSTDESK');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_RUSTDESK}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_RUSTDESK_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_FRONTAIL_LINUX($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $FRONTAIL_LINUX_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FRONTAIL_LINUX_INSTALLED"))==1){
        $FRONTAIL_LINUX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FRONTAIL_LINUX_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_FRONTAIL_LINUX"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_FRONTAIL_LINUX');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_FRONTAIL_LINUX}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$FRONTAIL_LINUX_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_DDNS_AGENT($UPDATES_ARRAY):string{

    $tpl=new template_admin();
    $APP_DDNS_AGENT_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DDNS_AGENT_INSTALLED"))==1){
        $APP_DDNS_AGENT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DDNS_AGENT_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_DDNS_AGENT"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_DDNS_AGENT');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_DDNS_AGENT}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_DDNS_AGENT_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_TAILON($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $TAILON_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TAILON_INSTALLED"))==1){
        $TAILON_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("TAILON_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_TAILON"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_TAILON');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_TAILON}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$TAILON_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_PROFTPD($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $warn_ico="";
    $VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProFTPDInstalled"))==1){
        $VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProFTPDVersion");
    }

    if(isset($UPDATES_ARRAY["APP_PROFTPD"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_PROFTPD');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_PROFTPD",
            "TOKEN_VER"=>"ProFTPDVersion",
            "TOKEN_ENABLED"=>"EnableProFTPD"),false
    );

    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_PROFTPD}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$VERSION$warn_ico</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function PFRING($UPDATES_ARRAY):string{
    $tpl=new template_admin();

    $bton=$tpl->icon_nothing();


    if(isset($UPDATES_ARRAY["PFRING"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=PFRING');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $DHCPD_VERSION=php_uname("r");


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{PFRING}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$DHCPD_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_DHCP($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $DHCPD_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPD_INSTALLED"))==1){
        $DHCPD_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDVersion");
    }

    if(isset($UPDATES_ARRAY["APP_DHCP"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_DHCP');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_DHCP}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$DHCPD_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_MSKTUTIL($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $MSKTUTIL_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    $warn_ico=null;
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MSKTUTIL_INSTALLED"))==1){
        $MSKTUTIL_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MSKTUTIL_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_MSKTUTIL"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_MSKTUTIL');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_MSKTUTIL",
            "TOKEN_VER"=>"MSKTUTIL_VERSION",
            "TOKEN_ENABLED"=>"MSKTUTIL_INSTALLED"),false
    );

    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_MSKTUTIL}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$MSKTUTIL_VERSION$warn_ico</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_KLNAGENT($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $KLNAGENT_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KLNAGENT_INSTALLED"))==1){
        $KLNAGENT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KLNAGENT_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_KLNAGENT"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_KLNAGENT');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_KLNAGENT}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$KLNAGENT_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_TAILSCALE($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_TAILSCALE_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_TAILSCALE_INSTALLED"))==1){
        $APP_TAILSCALE_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_TAILSCALE_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_TAILSCALE"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_TAILSCALE');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_TAILSCALE}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_TAILSCALE_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
        if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_SAMBA($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    //if(isset($UPDATES_ARRAY["APP_SAMBA"])){return "";}
    $tpl=new template_admin();
    $APP_SAMBA_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SAMBA_INSTALLED"))==1){
        $APP_SAMBA_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SAMBA_VERSION");
    }
    $warn_ico=null;

    if(isset($UPDATES_ARRAY["APP_SAMBA"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_SAMBA');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");


        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_SAMBA",
                "TOKEN_VER"=>"APP_SAMBA_VERSION",
                "TOKEN_ENABLED"=>"EnableSamba"),false
        );

        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_SAMBA_SMBD}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_SAMBA_VERSION$warn_ico</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}


function APP_OPENVPN($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_OPENVPN_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OPENVPN_INSTALLED"))==1){
        $APP_OPENVPN_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_OPENVPN_VERSION");
    }

    $warn_ico=null;

    if(isset($UPDATES_ARRAY["APP_OPENVPN"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_OPENVPN');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");


        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_OPENVPN",
                "TOKEN_VER"=>"OpenVPNVersion",
                "TOKEN_ENABLED"=>"EnableOpenVPNServer"),false
        );

        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_OPENVPN}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_OPENVPN_VERSION$warn_ico</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_PPTP_CLIENT($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_PPTP_CLIENT_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_PPTP_CLIENT_INSTALLED"))==1){
        $APP_PPTP_CLIENT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_PPTP_CLIENT_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_PPTP_CLIENT"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_PPTP_CLIENT');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_PPTP_CLIENT}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_PPTP_CLIENT_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_STRONGSWAN($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $IPSEC_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPSEC_INSTALLED"))==1){
        $IPSEC_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPSEC_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_STRONGSWAN"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_STRONGSWAN');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_STRONGSWAN}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$IPSEC_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_WSUSOFFLINE($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WSUSOFFLINE_INSTALLED"))==0){return "";}
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_WSUSOFFLINE}:</strong></td>";
    $html[]="<td nowrap>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WSUSOFFLINE_VERSION")."</td>";
    $html[]="<td>&nbsp;</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_SPLUNK_FORWARDER($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $SPLUNK_UF_VERSION = $tpl->icon_nothing();
    $bton = $tpl->icon_nothing();
    if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SPLUNK_FORWARDER_INSTALLED")) == 1) {
        $SPLUNK_UF_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SPLUNK_FORWARDER_VERSION");
    }

    if (isset($UPDATES_ARRAY["APP_SPLUNK_FORWARDER"])) {
        $bton = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_SPLUNK_FORWARDER');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");
    }

    $html[] = "<tr>";
    $html[] = "<td style='width:1%;text-align:right' nowrap><strong>{APP_SPLUNK_FORWARDER}:</strong></td>";
    $html[] = "<td nowrap>$SPLUNK_UF_VERSION</td>";
    $html[] = "<td>$bton</td>";
    $html[] = "</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_NGINX($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $APP_NGINX_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_INSTALLED"))==1){
        $APP_NGINX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_NGINX"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_NGINX');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_NGINX}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_NGINX_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function WP_CLIENT($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $WP_CLIENT_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WP_CLIENT_INSTALLED"))==1){$WP_CLIENT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WP_CLIENT_VERSION");}
    $warn_ico=null;
    if(isset($UPDATES_ARRAY["WP_CLIENT"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=WP_CLIENT');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");

        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"WP_CLIENT",
                "TOKEN_VER"=>"WP_CLIENT_VERSION",
                "TOKEN_ENABLED"=>"EnableWordpressManagement"),false
        );

        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }

    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{WP_CLIENT}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$WP_CLIENT_VERSION$warn_ico</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_POSTFIX($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_POSTFIX_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_INSTALLED"))==1){
        $APP_POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_POSTFIX"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_POSTFIX');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_POSTFIX}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_POSTFIX_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_MSMTP($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $MSMTP_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MSMTP_INSTALLED"))==1){
        $MSMTP_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MSMTP_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_MSMTP"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_MSMTP');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_MSMTP}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$MSMTP_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_MILTER_GREYLIST($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_MILTER_GREYLIST_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MILTER_GREYLIST_INSTALLED"))==1){
        $APP_MILTER_GREYLIST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MILTER_GREYLIST_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_MILTER_GREYLIST"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_MILTER_GREYLIST');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_MILTERGREYLIST}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_MILTER_GREYLIST_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_MILTER_REGEX($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();

    $APP_MILTER_REGEX_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MILTER_REGEX_INSTALLED"))==1){
        $APP_MILTER_REGEX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MILTER_REGEX_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_MILTER_REGEX"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_MILTER_REGEX');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_MILTER_REGEX}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_MILTER_REGEX_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_MIMEDEFANG($UPDATES_ARRAY):string{
        if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
        $tpl=new template_admin();
    $APP_MIMEDEFANG_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangInstalled"))==1){
        $APP_MIMEDEFANG_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangVersion");
    }

    if(isset($UPDATES_ARRAY["APP_MIMEDEFANG"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_MIMEDEFANG');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_VALVUAD}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_MIMEDEFANG_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_RBLDNSD($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_RBLDNSD_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RBLDNSD_INSTALLED"))==1){
        $APP_RBLDNSD_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RBLDNSD_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_RBLDNSD"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_RBLDNSD');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_RBLDNSD}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_RBLDNSD_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_CYRUS($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}

if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_CYRUS_INSTALLED"))==0){return "";}
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_CYRUS}:</strong></td>";
    $html[]="<td nowrap>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_CYRUS_VERSION")."</td>";
    $html[]="<td>&nbsp;</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_DNSCRYPT_PROXY($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_DNSCRYPT_PROXY_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSCRYPT_PROXY_INSTALLED"))==1){$APP_DNSCRYPT_PROXY_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSCRYPT_PROXY_VERSION");}

    if(isset($UPDATES_ARRAY["APP_DNSCRYPT_PROXY"])) {
        $bton = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_DNSCRYPT_PROXY');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");
    }
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_DNSCRYPT_PROXY}:</strong></td>";
    $html[]="<td nowrap>$APP_DNSCRYPT_PROXY_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_UNBOUND($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $UnboundVersion=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundInstalled"))==1){$UnboundVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundVersion");}

    if(isset($UPDATES_ARRAY["APP_UNBOUND"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_UNBOUND');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }



    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_UNBOUND}:</strong></td>";
    $html[]="<td nowrap>$UnboundVersion</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}


function APP_DOH_PROXY($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $CurVersion=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    $warn_ico="";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DOH_PROXY_INSTALLED"))==1){$CurVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOH_PROXY_VERSION");}

    if(isset($UPDATES_ARRAY["APP_DOH_PROXY"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_DOH_PROXY');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_DOH_PROXY",
            "TOKEN_VER"=>"APP_DOH_PROXY_VERSION",
            "TOKEN_ENABLED"=>"EnableDohProxy"),false
    );


    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }




    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_DOH_PROXY}:</strong></td>";
    $html[]="<td nowrap>$CurVersion$warn_ico</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_CLOUDFLARE_DNS($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $CurVersion=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    $warn_ico="";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CLOUDFLARED_INSTALLED"))==1){$CurVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CLOUDFLARED_VERSION");}

    if(isset($UPDATES_ARRAY["APP_DOH_PROXY"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_CLOUDFLARE_DNS');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_CLOUDFLARE_DNS",
            "TOKEN_VER"=>"CLOUDFLARED_VERSION",
            "TOKEN_ENABLED"=>"EnableCloudflared"),false
    );


    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }




    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_CLOUDFLARE_DNS}:</strong></td>";
    $html[]="<td nowrap>$CurVersion$warn_ico</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_VNSTAT($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_VNSTAT_VERSION");
    $bton=$tpl->icon_nothing();
    if($version==null){ $version=$tpl->icon_nothing(); }

    if (isset($UPDATES_ARRAY["APP_VNSTAT"])) {
        $bton = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_VNSTAT');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");

        $RESULTS = $tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY" => $UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY" => "APP_VNSTAT",
                "TOKEN_VER" => "APP_VNSTAT_VERSION",
                "TOKEN_ENABLED" => "EnableVnStat")
        );

        if (isset($RESULTS["NEW_VER"])) {
            $warn_ico = "&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }
    }
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_VNSTAT}:</strong></td>";
    $html[]="<td nowrap>$version$warn_ico</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_DNSDIST($UPDATES_ARRAY):string{
    $warn_ico="";
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $dnsdist_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSDIST_VERSION");
    $bton=$tpl->icon_nothing();
    if($dnsdist_version==null){ $dnsdist_version=$tpl->icon_nothing(); }

    if (isset($UPDATES_ARRAY["APP_DNSDIST"])) {
        $bton = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_DNSDIST');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");


        $RESULTS = $tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY" => $UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY" => "APP_DNSDIST",
                "TOKEN_VER" => "APP_DNSDIST_VERSION",
                "TOKEN_ENABLED" => "EnableDNSDist")
        );

        if (isset($RESULTS["NEW_VER"])) {
            $warn_ico = "&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_DNSDIST}:</strong></td>";
    $html[]="<td nowrap>$dnsdist_version$warn_ico</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_DNSDIST9($UPDATES_ARRAY):string{
    $warn_ico="";
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $dnsdist_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSDIST_VERSION");
    $bton=$tpl->icon_nothing();
    if($dnsdist_version==null){ $dnsdist_version=$tpl->icon_nothing(); }

    if (isset($UPDATES_ARRAY["APP_DNSDIST9"])) {
        $bton = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_DNSDIST9');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");


        $RESULTS = $tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY" => $UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY" => "APP_DNSDIST9",
                "TOKEN_VER" => "APP_DNSDIST_VERSION",
                "TOKEN_ENABLED" => "EnableDNSDist")
        );

        if (isset($RESULTS["NEW_VER"])) {
            $warn_ico = "&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_DNSDIST9}:</strong></td>";
    $html[]="<td nowrap>$dnsdist_version$warn_ico</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}


function APP_DSC($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $DSCVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DSCVersion");
    $bton=$tpl->icon_nothing();

    if(isset($UPDATES_ARRAY["APP_DSC"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_DSC');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    if($DSCVersion==null){
        $DSCVersion=$tpl->icon_nothing();
    }
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_DSC}:</strong></td>";
    $html[]="<td nowrap>$DSCVersion</td>";
    $html[]="<td>$bton</td>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}


function APP_PDNS($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_PDNS_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSVersion");
    $APP_PDNS_RECURSOR_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSRecursorVersion");

    $bton=$tpl->icon_nothing();
    if(isset($UPDATES_ARRAY["APP_PDNS"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_PDNS');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    if($APP_PDNS_VERSION==null){
        $APP_PDNS_VERSION=$tpl->icon_nothing();
    }
    if($APP_PDNS_RECURSOR_VERSION==null){
        $APP_PDNS_RECURSOR_VERSION=$tpl->icon_nothing();
    }

    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_PDNS}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_PDNS_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_PDNS_RECURSOR}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$APP_PDNS_RECURSOR_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_FAIL2BAN($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $FAIL2BAN_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FAIL2BAN_INSTALLED"))==1) {
        $FAIL2BAN_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("FAIL2BAN_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_FAIL2BAN"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_FAIL2BAN');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_FAIL2BAN}:</strong></td>";
    $html[]="<td nowrap>$FAIL2BAN_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_NTOPNG($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $NTOPNG_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NtopNGInstalled"))==1) {
        $NTOPNG_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTOPNG_VERSION");

    }
    if(isset($UPDATES_ARRAY["APP_NTOPNG"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_NTOPNG');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_NTOPNG} (NtopNG):</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$NTOPNG_VERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_GREENSQL($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton=$tpl->icon_nothing();
    $APP_GREENSQL_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_GREENSQL_INSTALLED"))==1){
        $APP_GREENSQL_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_GREENSQL_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_GREENSQL"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_GREENSQL');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_GREENSQL}:</strong></td>";
    $html[]="<td nowrap>$APP_GREENSQL_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_RDPPROXY($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton=$tpl->icon_nothing();
    $APP_RDPPROXY_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_INSTALLED"))==1){
        $APP_RDPPROXY_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_RDPPROXY"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_RDPPROXY');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_RDPPROXY}:</strong></td>";
    $html[]="<td nowrap>$APP_RDPPROXY_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_3PROXY($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton=$tpl->icon_nothing();
    $APP_3PROXY_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_3PROXY_INSTALLED"))==1){
        $APP_3PROXY_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_3PROXY_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_3PROXY"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_3PROXY');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_3PROXY}:</strong></td>";
    $html[]="<td nowrap>$APP_3PROXY_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_REDSOCKS($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_REDSOCKS_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_REDSOCKS_INSTALLED"))==1){
        $APP_REDSOCKS_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_REDSOCKS_VERSION");
    }


    $bton=$tpl->button_autnonome("{install_upgrade2}",
        "Loadjs('fw.apt-redsocks.php');",
        "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_REDSOCKS}:</strong></td>";
    $html[]="<td nowrap>$APP_REDSOCKS_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_HAPROXY($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton=$tpl->icon_nothing();
    $VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_INSTALLED"))==1){$VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_VERSION");}

    if(isset($UPDATES_ARRAY["APP_HAPROXY"])){
    $bton=$tpl->button_autnonome("{install_upgrade2}",
        "Loadjs('fw.system.upgrade-software.php?product=APP_HAPROXY');",
        "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_HAPROXY_SERVICE}:</strong></td>";
    $html[]="<td nowrap>$VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_QAT($UPDATES_ARRAY):string{

    $tpl=new template_admin();
    $warn_ico=null;
    $IntelQATVersion=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IntelQATInstalled"))==1){
        $IntelQATVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("IntelQATVersion");
    }

    if(isset($UPDATES_ARRAY["APP_QAT"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_QAT');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_QAT",
            "TOKEN_VER"=>"IntelQATVersion",
            "TOKEN_ENABLED"=>"EnableIntelQAT"),false
    );


    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>Intel QuickAssist:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$IntelQATVersion$warn_ico</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function APP_UFDBGUARDD($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $warn_ico=null;
    $UFDBDaemonVersion=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_UFDBGUARD_INSTALLED"))==1){
        $UFDBDaemonVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UFDBDaemonVersion");
    }

    if(isset($UPDATES_ARRAY["APP_UFDBGUARDD"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_UFDBGUARDD');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_UFDBGUARDD",
            "TOKEN_VER"=>"UFDBDaemonVersion",
            "TOKEN_ENABLED"=>"EnableUfdbGuard"),false
    );

    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_UFDBGUARD}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$UFDBDaemonVersion$warn_ico</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_WANPROXY($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $WANPROXYVERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WANPROXY_INSTALLED"))==1){
        $WANPROXYVERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WANPROXY_VERSION");
        if($WANPROXYVERSION==null){$WANPROXYVERSION="2170101";}
    }

    if(isset($UPDATES_ARRAY["APP_WANPROXY"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_WANPROXY');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_WANPROXY}:</strong></td>";
    $html[]="<td style='width:1%'  nowrap>$WANPROXYVERSION</td>";
    $html[]="<td style='text-align:left;width:99%' nowrap>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_C_ICAP($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $C_ICAP_VERSION=$tpl->icon_nothing();
    $bton=$tpl->icon_nothing();

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("C_ICAP_INSTALLED"))==1){
        $C_ICAP_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapVersion");
    }

    if(isset($UPDATES_ARRAY["APP_C_ICAP"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_C_ICAP');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_C_ICAP}:</strong></td>";
    $html[]="<td nowrap>$C_ICAP_VERSION</td>";
    $html[]="<td>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_GO_SHIELD_SERVER($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton=$tpl->icon_nothing();
    $warn_ico=null;

    if(isset($UPDATES_ARRAY["APP_GO_SHIELD_SERVER"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_GO_SHIELD_SERVER');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html=array();

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"))==1){
        $Go_Shield_Server_Addr = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Addr");
        $Go_Shield_Server_Port = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Port"));
        if ($Go_Shield_Server_Addr==null){$Go_Shield_Server_Addr="127.0.0.1";}
        if($Go_Shield_Server_Port==0){$Go_Shield_Server_Port=3333;}
        $cURLConnection = curl_init();

        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_GO_SHIELD_SERVER",
                "TOKEN_VER"=>"APP_GO_SHIELD_VERSION",
                "TOKEN_ENABLED"=>"Go_Shield_Server_Enable"),false
        );

        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }

        if(function_exists("json_decode")) {
            curl_setopt($cURLConnection, CURLOPT_URL, "http://$Go_Shield_Server_Addr:$Go_Shield_Server_Port/get-version");
            curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
            $resp = curl_exec($cURLConnection);
            curl_close($cURLConnection);
            $jsonArrayResponse = json_decode($resp, true);
            $Go_Shield_Server_Version = $tpl->icon_nothing();
            if (isset($jsonArrayResponse["version"])) {
                $Go_Shield_Server_Version = $jsonArrayResponse["version"];
            }
            $html[] = "<tr>";
            $html[] = "<td style='width:1%;text-align:right' nowrap><strong>{APP_GO_SHIELD_SERVER}:</strong></td>";
            $html[] = "<td nowrap>" . $Go_Shield_Server_Version . "$warn_ico</td>";
            $html[] = "<td>$bton</td>";
            $html[] = "</tr>";
        }

        $bton=$tpl->icon_nothing();

        if(isset($UPDATES_ARRAY["APP_GO_FS"])){
            $bton=$tpl->button_autnonome("{install_upgrade2}",
                "Loadjs('fw.system.upgrade-software.php?product=APP_GO_FS');",
                "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
        }


            $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("go-shield-server-fs-watcher-ver");
            $html[]="<tr>";
            $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_GO_FS}:</strong></td>";
            $html[]="<td nowrap>".$version."</td>";
            $html[]="<td>$bton</td>";
            $html[]="</tr>";

    }
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_GO_SHIELD_CONNECTOR($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton=$tpl->icon_nothing();
    $html=array();
    if(isset($UPDATES_ARRAY["APP_GO_SHIELD_CONNECTOR"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_GO_SHIELD_CONNECTOR');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"))==1){
        $libmem=new lib_memcached();
        $Go_Shield_Connector_Version=trim($libmem->getKey("Go-Shield-Connector-Version"));
        if(trim($Go_Shield_Connector_Version==null)){
            $Go_Shield_Connector_Version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("GO_SHIELD_CONNECTOR_VERSION");
        }
        $html[]="<tr>";
        $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_GO_SHIELD_CONNECTOR}:</strong></td>";
        $html[]="<td nowrap>".$Go_Shield_Connector_Version."</td>";
        $html[]="<td>$bton</td>";
        $html[]="</tr>";
    }

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_GO_EXEC($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $bton=$tpl->icon_nothing();
    $html=array();
    if(isset($UPDATES_ARRAY["APP_GO_EXEC"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_GO_EXEC');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Exec_Enable"))==1){
        $sock=new sockets();
        $version=$sock->go_exec_version();
        $html[]="<tr>";
        $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_GO_EXEC}:</strong></td>";
        $html[]="<td nowrap>".$version."</td>";
        $html[]="<td>$bton</td>";
        $html[]="</tr>";

    }
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_GO_HOTSPOT_ENGINE($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton=$tpl->icon_nothing();
    $html=array();
    if(isset($UPDATES_ARRAY["APP_GO_HOTSPOT_ENGINE"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_GO_HOTSPOT_ENGINE');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidMicroHotSpot"))==1){
        $Go_Hotspot_Ver=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HOTSPOTWEB_VERSION");
        $html[]="<tr>";
        $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_GO_HOTSPOT_ENGINE}:</strong></td>";
        $html[]="<td nowrap>".$Go_Hotspot_Ver."</td>";
        $html[]="<td>$bton</td>";
        $html[]="</tr>";
    }

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_GO_PAC_ENGINE($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton=$tpl->icon_nothing();
    $html=array();
    if(isset($UPDATES_ARRAY["APP_GO_PAC_ENGINE"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_GO_PAC_ENGINE');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyPac"))==1){
        $Go_Pac_Ver=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PROXYPAC_VERSION");
        $html[]="<tr>";
        $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_GO_PAC_ENGINE}:</strong></td>";
        $html[]="<td nowrap>".$Go_Pac_Ver."</td>";
        $html[]="<td>$bton</td>";
        $html[]="</tr>";
    }
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_GO_WEBFITLER_ERROR_PAGE_ENGINE($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton=$tpl->icon_nothing();
    $html=array();
    if(isset($UPDATES_ARRAY["APP_GO_WEBFITLER_ERROR_PAGE_ENGINE"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_GO_WEBFITLER_ERROR_PAGE_ENGINE');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalService"))==1){

        $Go_ErrorPage_Ver=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WEBERRORPAGE_VERSION");

        $html[]="<tr>";
        $html[]="<td style='width:1%;text-align:right' nowrap><strong>{APP_GO_WEBFITLER_ERROR_PAGE_ENGINE}:</strong></td>";
        $html[]="<td nowrap>".$Go_ErrorPage_Ver."</td>";
        $html[]="<td>$bton</td>";
        $html[]="</tr>";
    }

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_ADAGENT_LBL($UPDATES_ARRAY):string
{
    if (isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])) {
        return "";
    }
    $tpl = new template_admin();
    $html = array();
    if (isset($UPDATES_ARRAY["APP_ADAGENT_LBL"])) {
        $bton = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_ADAGENT_LBL');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");
        $Ver = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADAGENT_VERSION");
        $html[] = "<tr>";
        $html[] = "<td style='width:1%;text-align:right' nowrap><strong>{APP_ADAGENT_LBL}:</strong></td>";
        $html[] = "<td nowrap>" . $Ver . "</td>";
        $html[] = "<td>$bton</td>";
        $html[] = "</tr>";
    }
    return @implode("\n", $html);
}
function APP_ADAGENT_CONNECTOR($UPDATES_ARRAY):string{
    if (isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])) {
        return "";
    }
    $tpl = new template_admin();
    $html = array();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableExternalACLADAgent"))==1){
        if(isset($UPDATES_ARRAY["APP_ADAGENT_CONNECTOR"])) {
            $bton = $tpl->button_autnonome("{install_upgrade2}",
                "Loadjs('fw.system.upgrade-software.php?product=APP_ADAGENT_CONNECTOR');",
                "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");
            $libmem=new lib_memcached();
            $ad_agent_Ver=trim($libmem->getKey("Go-Squid-AD-Agent-Client-Version"));
            if(trim($ad_agent_Ver==null)){
                $ad_agent_Ver="0.0.0";
            }
            $html[] = "<tr>";
            $html[] = "<td style='width:1%;text-align:right' nowrap><strong>{APP_ADAGENT_CONNECTOR}:</strong></td>";
            $html[] = "<td nowrap>" . $ad_agent_Ver . "</td>";
            $html[] = "<td>$bton</td>";
            $html[] = "</tr>";
        }
    }
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}