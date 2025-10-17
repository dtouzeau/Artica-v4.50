<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.left-menus.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
$tpl=new template_admin();
if(!$tpl->xPrivs()){die();}

if(isset($_GET["verbose"])){
    $GLOBALS["VERBOSE"]=true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string',null);
    ini_set('error_append_string',null);
}

clean_xss_deep();
xgen();

function GetAdDisplayName():string{
    if(!isset($_SESSION["ACTIVE_DIRECTORY_INFO"])){
        return "";
    }
    $EnableExternalACLADAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableExternalACLADAgent"));
    if($EnableExternalACLADAgent==1){
        return $_SESSION["ACTIVE_DIRECTORY_INFO"]["name"];
    }
    if(isset($_SESSION["ACTIVE_DIRECTORY_INFO"]["displayName"][0])){
        return $_SESSION["ACTIVE_DIRECTORY_INFO"]["displayName"][0];
    }
    if(isset($_SESSION["ACTIVE_DIRECTORY_INFO"]["name"][0])){
        return $_SESSION["ACTIVE_DIRECTORY_INFO"]["name"][0];
    }
    if(isset($_SESSION["ACTIVE_DIRECTORY_INFO"]["sAMAccountName"][0])){
        return $_SESSION["ACTIVE_DIRECTORY_INFO"]["sAMAccountName"][0];
    }
    return "Unknown";
}

function xgen(){
    $tpl=new template_admin();
    $sock=new sockets();
    $tt=array();$right=null;
    $name=$_SESSION["uid"];
    $users=new usersMenus();
    if($name==-100){
        $ldap=new clladp();
        $name=$ldap->ldap_admin;
        $right="{administrator}";
    }

    if(isset($_SESSION["ACTIVE_DIRECTORY_INFO"])){
        $name=GetAdDisplayName();
    }

    if(strpos($name, "@")>0){
        $tt=explode("@",$name);
        $name=$tt[0];
    }

    $page                   = CurrentPageName();
    $SQUIDEnable            = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
    $SquidInRouterMode      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidInRouterMode"));
    $HideCorporateFeatures  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideCorporateFeatures"));
    $ActAsASyslogServer     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActAsASyslogServer"));
    $EnableClamavDaemon     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavDaemon"));

    if($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$HideCorporateFeatures=0;}
    $leftmenus=new left_menus();
    $isFireWall=$leftmenus->isFireWall();



    foreach ($_GET as $key=>$val){$tt[]="$key=".urlencode($val);}

    $f[]="<input type='hidden' id='fw-left-menus-uri' value='$page?none=yes".@implode("&", $tt)."'>";

    $content_page="fw.index.php?content=yes";

    if(isset($_GET["dynacls"])){
        $content_page="fw.dynacls.php?content=yes";
    }

    if($users->isProxyManagerOnly()){
        $content_page="fw.proxy.php?content=yes";
    }

    if(isset($_GET["choose-proxy"])){$content_page="fw.proxy.php?content=yes";}


    $f[]="<nav class=\"navbar-default navbar-static-side\" role=\"navigation\">";
    $f[]="        <div class=\"sidebar-collapse\">";
    $f[]="            <ul class=\"nav metismenu\" id=\"side-menu\">";
    $f[]="                <li class=\"nav-header\">";
    $f[]="                    <div class=\"dropdown profile-element\">";
    $f[]="                            <a data-toggle=\"dropdown\" class=\"dropdown-toggle\" href=\"#\">";
    $f[]="                            <span class=\"clear\"> <span class=\"block m-t-xs\"> <strong class=\"font-bold\">{$name}</strong>";
    $f[]="                             </span> <span class=\"text-muted text-xs block\">$right <b class=\"caret\"></b></span> </span> </a>";
    $f[]="                            <ul class=\"dropdown-menu animated fadeInRight m-t-xs\">";
    $f[]="                                <li><a href=\"javascript:blur();\" OnClick=\"LoadAjaxSilent('MainContent','fw.account.php');\">{myaccount}</a></li>";
    $f[]="                            </ul>";
    $f[]="                    </div>";
    $f[]="                    <div class=\"logo-element\">";
    $f[]="                        IN+";
    $f[]="                    </div>";
    $f[]="                </li>";



    $f[]="                <li class=\"active\" id='left-menu'>";
    $f[]="                    <a href='#' OnClick=\"MenuRoot( $(this),'$content_page');\">
		<i class=\"far fa-columns\"></i> <span class=\"nav-label\">{dashboard}</span></a>";
    $f[]="                </li>";

    $IsWizardExecuted=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IsWizardExecuted"));
    $f[]="<!-- IsWizardExecuted ===  $IsWizardExecuted -->";
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $f[]="<!-- CORP_LICENSE ===  FALSE -->";
    }else{
        $f[]="<!-- CORP_LICENSE ===  TRUE -->";
    }
    $f[]="<!-- SquidInRouterMode ===  $SquidInRouterMode -->";
    $EnableWazhuCLient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWazhuCLient"));
    if($users->AsAnAdministratorGeneric OR $users->AsFirewallManager){
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fa fa-server\"></i> <span class=\"nav-label\">{your_system}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.system.information.php","ICO"=>"fas fa-server","TEXT"=>"{system_information}"));
        if($users->AsSystemAdministrator) {
            if(!$users->AsDockerWeb) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.system.hd.php", "ICO" => "fa-hdd", "TEXT" => "{your_hard_disks}"));
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.system.memory.config.php", "ICO" => "fad fa-memory", "TEXT" => "{memory_info}"));
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.system.swap.php", "ICO" => "fa-hdd", "TEXT" => "{swap_label}"));

                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.system.users.php", "ICO" => ico_group, "TEXT" => "{system_users}"));
            }

            $EnableFluentBit=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFluentBit");
            $f[]="<!-- EnableFluentBit ===  $EnableFluentBit -->";
            if($EnableFluentBit){
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.system.fluent.php", "ICO" => "fa-solid fa-dove", "TEXT" => "Fluent Bit"));
            }


            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.system.monit.php", "ICO" => "fas fa-dog", "TEXT" => "{watchdog}"));

            if($EnableWazhuCLient==0){
                if(!$users->AsDockerWeb) {
                    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.system.fsm.php", "ICO" => "fa-solid fa-folder-magnifying-glass", "TEXT" => "{APP_ARTICAFSMON}"));
                }
            }
        }




        if($users->AsSystemAdministrator){
            if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ISCSI_CLIENT_INSTALLED"))==1){
                if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableISCSI")==1)){
                    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.iscsi.php","ICO"=>"fa-hdd","TEXT"=>"{APP_IETD}"));
                }
            }


            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.activedirectory.rest.php",
            "ICO" => "fad fa-monitor-heart-rate", "TEXT" => "{webapi_service}"));



        }


        if($users->AsSystemAdministrator){
            $AutoFSEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AutoFSEnabled"));
            if($users->autofs_installed){
                if($AutoFSEnabled==1){

                    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.system.autofs.php",
                        "ICO" => "fa-conveyor-belt-alt", "TEXT" => "{automount_center}",
                        "LEVEL3" => array("ID" => "fw-autofs-left-menu",
                            "PAGE" => "fw-left-menus-autofs.php")));

                }
            }}


        if($users->AsAnAdministratorGeneric) {
            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.system.watchdog.php",
                    "ICO" => ico_eye, "TEXT" => "{system_events}"));
        }

        if($users->AsSystemAdministrator) {
            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.system.notifications.php",
                    "ICO" => "fa fa-envelope", "TEXT" => "{notifications}"));
        }




        if($users->AsAnAdministratorGeneric){
            $PowerDNSEnableClusterMaster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMaster"));
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterMaster==1){
//<i class="fas fa-clone"></i>
                $f[]=$tpl->LeftMenu(
                    array("PAGE"=>"fw.system.cluster.master.php",
                        "ICO"=>"fas fa-clone","TEXT"=>"Cluster ({master_mode})"));
            }
            if($PowerDNSEnableClusterSlave==1){
//<i class="fas fa-clone"></i>
                $f[]=$tpl->LeftMenu(
                    array("PAGE"=>"fw.system.cluster.client.php",
                        "ICO"=>"fas fa-clone","TEXT"=>"Cluster ({slave_mode})"));
            }
        }
        

        if($users->AsSystemAdministrator){
            if(!$users->AsDockerWeb) {
                $f[] = $tpl->LeftMenu(
                    array("PAGE" => "fw.system.services.php",
                        "ICO" => ico_cd, "TEXT" => "{features}"));
            }
        }




        if($users->AsAnAdministratorGeneric OR $users->AsFirewallManager){
            if($users->AsSystemAdministrator){
                $EnableOpenSSH=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenSSH"));
                $EnableSSHProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSSHProxy"));
                if($EnableOpenSSH==1) {
                    $f[] = $tpl->LeftMenu(
                        array("PAGE" => "fw.sshd.php",
                            "ICO" => ico_terminal, "TEXT" => "{APP_OPENSSH}"));
                }
                    if($EnableSSHProxy==1) {
                        $f[] = $tpl->LeftMenu(
                            array("PAGE" => "fw.sshproxy.php",
                                "ICO" => ico_terminal, "TEXT" => "{APP_SSHPROXY}"));

                }
            }
        }

        $ChronydInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChronydInstalled"));
        $ChronydEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChronydEnabled"));

        $f[]="<!-- NTPD START  ChronydInstalled = $ChronydInstalled Enabled = $ChronydEnabled Systemadmin: $users->AsSystemAdministrator -->";
        if($ChronydInstalled==0){$ChronydEnabled=0;}
        if(!$users->AsSystemAdministrator){$ChronydEnabled=0;}
        if($ChronydEnabled==1){
            $f[]=$tpl->LeftMenu(
                array("PAGE"=>"fw.system.ntpd.php",
                    "ICO"=>"fa-clock","TEXT"=>"{time_server}"));

        }


        if($EnableWazhuCLient==1){
            $f[]=$tpl->LeftMenu(
                array("PAGE"=>"fw.wazhu.client.php",
                    "ICO"=>"fa-solid fa-sensor","TEXT"=>"{APP_WAZHU}"));

        }
        $EnableNagiosClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNagiosClient"));
        if($EnableNagiosClient==1){
            $f[]=$tpl->LeftMenu(
                array("PAGE"=>"fw.nagios.client.php",
                    "ICO"=>"fa-solid fa-sensor","TEXT"=>"{APP_NAGIOS_CLIENT}"));
        }

        if($users->AsSystemAdministrator){
            $EnableGlances=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGlances"));
            if($EnableGlances==1){
                $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.system.glances.php","ICO"=>"fas fa-microchip","TEXT"=>"{APP_GLANCES}"));

            }
        }

        if($users->AsSystemAdministrator){
            if(!$users->AsDockerWeb) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.system.tasks.php", "ICO" => "fa-clock", "TEXT" => "{tasks}"));
            }
        }


        if(!$users->isCertifManagerOnly()) {
            if ($users->AsCertifsManager) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.certificates-center.php", "ICO" => "fa-certificate", "TEXT" => "{certificates_center}"));
            }
        }

        if($users->APP_NETDATA_INSTALLED){
            $NetDATAEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetDATAEnabled"));
            if($NetDATAEnabled==1){$f[]=$tpl->LeftMenu(array("PAGE"=>"fw.netdata.php","ICO"=>"fa-heart","TEXT"=>"{system_monitoring}"));}
        }


        $EnableSNMPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSNMPD"));
        VERBOSE("EnableSNMPD=$EnableSNMPD", __LINE__);
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSNMPD"))==1){
            $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.system.snmpd.php","ICO"=>"fas fa-comment-check","TEXT"=>"SNMPv3"));

        }
//<i class="fas fa-heart-rate"></i>
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableZabbixAgent")==1)){
            $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.system.zabbix.php","ICO"=>ico_health_check,"TEXT"=>"{APP_ZABBIX_AGENT}"));
        }


        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SPLUNK_FORWARDER_INSTALLED"))==1){
            $SplunkForwarderEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SplunkForwarderEnabled"));
            if($SplunkForwarderEnabled==1){
                $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.splunk.general.php","ICO"=>"far fa-arrow-right","TEXT"=>"Splunk"));
            }
        }


        if($users->AsSystemAdministrator){
            if(!$users->AsDockerWeb) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.artica.backup.php", "ICO" => "fa-archive", "TEXT" => "{backup}"));
            }
        }


        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.license.php","ICO"=>"fa-key","TEXT"=>"{license2}"));

        if($users->AsAnAdministratorGeneric) {
            if(!$users->AsDockerWeb) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.updates.php",
                    "ICO" => "far fa-cloud-download", "TEXT" => "{update2}",
                    "LEVEL3" => array("ID" => "fw-updates-left-menu",
                        "PAGE" => "fw-left-menus-updates.php")));
            }

        }
        if($users->AsAnAdministratorGeneric) {
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.articaweb.php", "ICO" => "far fa-browser",
                "TEXT" => "{web_console}"));




            $EnableRESTFulSystem = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRESTFulSystem"));
            if ($EnableRESTFulSystem == 1) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.system.restful.php", "ICO" => "fa-heart",
                    "TEXT" => "RESTful"));
            }
        }

        if(!$users->AsDockerWeb) {
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.support.php", "ICO" => "fas fa-bug", "TEXT" => "Support"));
        }

        $f[]="					 </ul>";
        $f[]="                </li>";

    }
    $EnableLinkBalancer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLinkBalancer"));
    $EnablenDPI=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablenDPI"));
    if(!$users->AsDockerWeb) {
        if($users->AsAnAdministratorGeneric OR $users->AsFirewallManager){
        $f[]="<!-- NETWORK START -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fa fa-sitemap\"></i> <span class=\"nav-label\">{network}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";
        $SYSTEMD_NETWORK_ENABLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMD_NETWORK_INSTALLED"));
        $SYSTEMD_NETWORK_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMD_NETWORK_INSTALLED"));
        $SYSTEMD_NETWORK_REMOVED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMD_NETWORK_REMOVED"));
        if($SYSTEMD_NETWORK_INSTALLED==0){
            $SYSTEMD_NETWORK_ENABLED=0;
        }
        if($SYSTEMD_NETWORK_REMOVED==1){
            $SYSTEMD_NETWORK_ENABLED=0;
        }



        if($SYSTEMD_NETWORK_ENABLED==1){
            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.networkd.php",
                    "ICO" => ico_params, "TEXT" => "{APP_NETWORKD}"));

        }

            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.network.interfaces.php",
                    "ICO" => "far fa-exchange", "TEXT" => "{interfaces}"));

        $APP_OPENVSWITCH_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_OPENVSWITCH_INSTALLED"));
        $OpenVswitchEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVswitchEnable"));
        if($APP_OPENVSWITCH_INSTALLED==0){
            $OpenVswitchEnable=0;
        }

        if($OpenVswitchEnable==1){
            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.openvswitch.php", "ICO" => "fas fa-bezier-curve", "TEXT" => "{virtual_switch}"));
        }


        if($isFireWall==0) {
            if($users->AsFirewallManager) {
                    $f[] = $tpl->LeftMenu(
                        array("PAGE" => "fw.bridges.php", "ICO" => "fas fa-bezier-curve", "TEXT" => "{interfaces_connectors}"));

                    $f[] = $tpl->LeftMenu(
                    array("PAGE" => "fw.ipfeeds.php",
                        "ICO" => "fas fa-hockey-mask", "TEXT" => "{CybercrimeIPFeeds}"));

            }
        }

        if($users->AsSystemAdministrator) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.network.routing.php",
                    "ICO" => "fa-road",
                    "TEXT" => "{routing_rules}"));



            $EnableOSPFD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOSPFD"));
            VERBOSE("EnableOSPFD: $EnableOSPFD",__LINE__);
            if($EnableOSPFD==1){
                $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.network.ospf.php",
                    "ICO"=>"fad fa-route",
                    "TEXT"=>"{APP_OSPF}"));
            }


            if($EnableLinkBalancer==1){
                $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.network.balancer.php",
                    "ICO"=>"fas fa-bezier-curve",
                    "TEXT"=>"{APP_LINK_BALANCER}"));

            }


            $EnablePPTPClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePPTPClient"));

            if($EnablePPTPClient==1){
                $f[]=$tpl->LeftMenu(
                    array("PAGE"=>"fw.pptp.client.php",
                        "ICO"=>"fad fa-router","TEXT"=>"{APP_PPTP_CLIENT_SHORT}"));
            }
        }

            $f[]=$tpl->LeftMenu(
                array("PAGE"=>"fw.networks.php",
                    "ICO"=>"fa-wifi","TEXT"=>"{your_networks}"));

            if(method_exists($leftmenus,"PRADS")) {
                $f[] = $leftmenus->PRADS();
            }else{
                $f[]="<!-- ".__LINE__." PRADS Property doesn't exists -->";
            }

            if($isFireWall==0) {
                if($users->AsFirewallManager) {
                    $f[] = $tpl->LeftMenu(
                        array("PAGE" => "fw.ipfeeds.php",
                            "ICO" => "fas fa-hockey-mask", "TEXT" => "{CybercrimeIPFeeds}"));

                    $f[] = $tpl->LeftMenu(
                        array("PAGE" => "fw.ids.wizard.php",
                            "ICO" => ico_sensor, "TEXT" => "{IDS}"));


                }
            }
/*
            $NetMonixInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetMonixInstalled"));

            if($NetMonixInstalled==1){
                $f[]=$tpl->LeftMenu(
                    array("PAGE"=>"fw.network.ndpid.php","ICO"=>ico_sensor,"TEXT"=>"{inspection}"));

            }else{
                if($EnablenDPI==1){
                    $f[]=$tpl->LeftMenu(
                    array("PAGE"=>"fw.network.ndpifw.php","ICO"=>ico_sensor,"TEXT"=>"{APP_NDPI}"));
                }
            }

*/

          $IPAUDIT_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPAUDIT_INSTALLED"));
            if($IPAUDIT_INSTALLED==1){
                $f[]=$tpl->LeftMenu(
                    array("PAGE"=>"fw.network.ipaudit.php","ICO"=>ico_chart_line,"TEXT"=>"{APP_IPAUDIT}"));

            }



            $EnableDarkStat=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDarkStat"));
            if($EnableDarkStat==1){
                $f[]=$tpl->LeftMenu(
                    array("PAGE"=>"fw.network.darkstat.php","ICO"=>"fas fa-chart-area","TEXT"=>"{network_monitor}"));
            }

            $NtopNGInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NtopNGInstalled"));
            if($NtopNGInstalled==1){
                $Enablentopng=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablentopng"));
                if($Enablentopng==1){
                    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.network.ntopng.php","ICO"=>"fa-heart","TEXT"=>"{network_monitor}"));
                }
            }




            $EnableSmokePing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSmokePing"));

            if($EnableSmokePing==1){
                $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.network.smokeping.php",
                    "ICO"=>ico_health_check,"TEXT"=>"{APP_SMOKEPING}"));


            }
            $f[]="					 </ul>";
            $f[]="                </li>";
        }
        $f[]="<!-- NETWORK END -->";

    }

    $EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
    $POSTFIX_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_INSTALLED"));
    if($POSTFIX_INSTALLED==0){$EnablePostfix=0;}

    if(method_exists($leftmenus,"harmpManaged")) {
        $f[] = $leftmenus->harmpManaged();
    }else{
        $f[] = "<!-- method_exists  harmpManaged NONE -->";
    }
    if(method_exists($leftmenus,"ArticaMeta")) {
        $f[] = $leftmenus->ArticaMeta();
    }else{
        $f[] = "<!-- method_exists  ArticaMeta NONE -->";
    }



    $f[]=$leftmenus->FireCracker();
    $f[]=$leftmenus->reverse_proxys();
    $f[]=$leftmenus->FireQOS();
    $f[]=$leftmenus->NetWorkAgent();

    if(method_exists($leftmenus,"DWAgent")) {
        $f[] = $leftmenus->DWAgent();
    }else{
        $f[] = "<!-- DWAgent property doesn't exists -->";
    }
    $f[]=$leftmenus->FreeRadius();
    $f[]=$leftmenus->WSUSOFFLINE();
    $f[]=$leftmenus->Rsync();
    $f[]="<!-- [".__LINE__."]: ACTIVE DIRECTORY START -->";
    $f[]=$leftmenus->ActiveDirectory();
    $f[]=$leftmenus->FileBeat();
    $f[]=$leftmenus->Samba();
    $f[]=$leftmenus->Syncthing();
    $f[]=$leftmenus->HaProxy();
    $f[]=$leftmenus->APP_HAPROXY_EXCHANGE();
    $f[]=$leftmenus->GreenSQL();
    $f[]=$leftmenus->Xapian();
    $f[]=$leftmenus->DNS();
    $f[]=$leftmenus->DHCP();
    $f[]=$leftmenus->APP_NETBOX();
    $f[]=$leftmenus->KEA();
    $f[]=$leftmenus->rustdesk();
    $f[]=$leftmenus->FireHole();
    $f[]=$leftmenus->Suricata();
    $f[]=$leftmenus->Fail2Ban();
    $f[]=$leftmenus->ProFTPD();
    $f[]=$leftmenus->OpenVPN();
    $f[]=$leftmenus->TailScale();
    if(method_exists($leftmenus,"Strongswan")) {
        $f[] = $leftmenus->Strongswan();
    }else{
        $f[] = "<!-- Strongswan property doesn't exists -->";
    }
    $f[]=$leftmenus->Threeproxy();

    if(method_exists($leftmenus,"dockerd")) {
        $f[] = $leftmenus->dockerd();
    }else{
        $f[] = "<!-- dockerd property doesn't exists -->";
    }


    if($EnablePostfix==1){
        $f[]=$leftmenus->Messaging();
        $f[]=$leftmenus->MimeDefang();
    }
    if(method_exists($leftmenus,"ImapBox")) {
        $f[] = $leftmenus->ImapBox();
    }else{
        $f[] = "<!-- ImapBox property doesn't exists -->";
    }
    $f[]=$leftmenus->RDPProxy();
    $f[]=$leftmenus->HaCluster();
    $f[]=$leftmenus->PulseReverse();

    //KEEPALIVED
    $f[] = $leftmenus->keepalived();
    $f[] = $leftmenus->keepalived_slave();
    //END KEEPALIVED
    $f[]=$leftmenus->Proxy();
    if(method_exists($leftmenus,"hotspot")) {
        $f[]=$leftmenus->hotspot();
    }

    $f[]=$leftmenus->ProxyPac();
    $f[]=$leftmenus->WanProxy();
    $f[]=$leftmenus->ZipProxy();
    $f[]=$leftmenus->Privoxy();
    $f[] = "<!-- WEB-FILTERING START  -->";

    if(method_exists($leftmenus,"KSRN")) {
        $f[] = $leftmenus->KSRN();
    }else{
        $f[] = "<!-- KSRN property doesn't exists -->";
    }


    $f[]=$leftmenus->YourCategories();
    $f[]=$leftmenus->ufdbcat();
    $f[]=$leftmenus->C_ICAP_SERVICE();
    $f[]=$leftmenus->nginx();
    $f[]=$leftmenus->WebFirewall();
    $f[]=$leftmenus->statitics();
    $f[]=$leftmenus->Databases();

    $EnableDNSFilterd=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSFilterd"));
    $CicapEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled"));

    $EnableUfdbGuard=0;
    if($users->AsDansGuardianAdministrator OR $users->AsProxyMonitor){
        if($users->APP_UFDBGUARD_INSTALLED){
            $EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('EnableUfdbGuard'));
        }
    }





    if($users->AsDansGuardianAdministrator OR $users->AsProxyMonitor OR $users->AsSystemAdministrator) {
        $EnableModSecurityIngix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableModSecurityIngix"));
        $f[] = "<!-- EnableUfdbGuard=$EnableUfdbGuard  -->";
        $f[] = "<!-- EnableDNSFilterd=$EnableDNSFilterd  -->";
        $f[] = "<!-- EnableDNSFilterd=$EnableDNSFilterd  -->";


        if ($EnableClamavDaemon == 1) {
            $f[] = "<!-- CLAMAV START  -->";
            $f[] = "                <li id='left-menu'>";
            $f[] = "                    <a href='#' ><i class=\"fab fa-medrt\"></i> <span class=\"nav-label\">{APP_CLAMAV}</span> </a>";
            $f[] = "	                  <ul class='nav nav-second-level'>";
            $f[] = "                			<li id='left-menu'>";
            $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.clamav.status.php');\"><i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{status}</span> </a>";
            $f[] = "							</li>";

            $f[] = "                			<li id='left-menu'>";
            $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.clamav.white.php');\"><i class=\"fas fa-thumbs-up\"></i> <span class=\"nav-label\">{whitelist}</span> </a>";
            $f[] = "							</li>";

            if($users->AsDansGuardianAdministrator) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.clamav.waf.php", "ICO" => ico_eye, "TEXT" => "{WAF_LONG}"));
            }

            $f[] = "					 </ul>";
            $f[] = "                </li>";
            $f[] = "<!-- CLAMAV END  -->";
        }
    }

    $Aslog=false;
    if($users->AsProxyMonitor){$Aslog=true;}
    if($users->AsSystemAdministrator){$Aslog=true;}
    $C_ICAP_INSTALLED = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("C_ICAP_INSTALLED"));
    $CicapEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled"));
    $C_ICAP_RECORD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("C_ICAP_RECORD"));
    if($C_ICAP_INSTALLED==0){$CicapEnabled=0;}
    $f[]=$leftmenus->LOG_SINK();
    $f[]=$leftmenus->LEGAL_LOGS();

    if($Aslog) {
        $f[] = "<!-- LOGS_CENTER START  -->";
        $f[] = "                <li id='left-menu'>";
        $f[] = "                    <a href='#' ><i class=\"fad fa-file-alt\"></i> <span class=\"nav-label\">{logs_center}</span> </a>";
        $f[] = "	                  <ul class='nav nav-second-level'>";


        $EnableTailon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTailon"));
        $EnableFrontail=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFrontail"));



        if($users->AsAnAdministratorGeneric) {
                $f[] = $tpl->LeftMenu(
                    array("PAGE" => "fw.syslogd.php",
                        "ICO" => "fad fa-wrench", "TEXT" => "Syslog"));

        }
        if ($users->AsSystemAdministrator) {
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.retention.php", "ICO" => "fas fa-cogs", "TEXT" => "{retentions}"));

        }



        if($EnableTailon==1){
            $f[] = "<li id='left-menu'>";
            $f[] = "<a href='#' OnClick=\"s_PopUpFull('/tailon/','1024','900');\"><i class=\"fa fa-eye\"></i> <span class=\"nav-label\">{APP_TAILON}</span> </a>";
            $f[] = "</li>";
        }

        if($EnableFrontail==1) {
            $f[] = "<li id='left-menu'>";
            $f[] = "<a href='#' OnClick=\"s_PopUpFull('/syslog/','1024','900');\"><i class=\"fa fa-eye\"></i> <span class=\"nav-label\">{syslog}</span> </a>";
            $f[] = "</li>";
        }




        if($users->AsAnAdministratorGeneric){
            if ($ActAsASyslogServer == 1) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.dns.unbound.queries.php", "ICO" => ico_eye, "TEXT" => "{DNS_QUERIES}"));

                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.syslog.php", "ICO" => ico_eye, "TEXT" => "{firewall}"));

            }
        }
        if ($SQUIDEnable == 1) {
            if ($SquidInRouterMode == 0) {
                if ($users->AsProxyMonitor) {
                    $f[] = $tpl->LeftMenu(
                        array("PAGE" => "fw.proxy.daemon.php", "ICO" => ico_eye, "TEXT" => "{PROXY_EVENTS}",
                            "LEVEL3" => array("ID" => "fw-left-menus-proxy-logs",
                                "PAGE" => "fw-left-menus-proxy-logs.php")));
                    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.icap.logs.php", "ICO" => ico_eye, "TEXT" => "{icap_events}"));
                }

            }
        }

        // -----------------------------------------------------------------------
        $f[] ="<!-- CicapEnabled = $CicapEnabled  C_ICAP_RECORD = $C_ICAP_RECORD-->";
        if ($CicapEnabled == 1) {
            if($C_ICAP_RECORD==1) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.sandbox.logs.php",
                        "ICO" => ico_eye, "TEXT" => "{sandbox_connector}"));
            }
        }

        // -----------------------------------------------------------------------

        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.kernel.logs.php", "ICO" => ico_eye, "TEXT" => "{events}:{kernel}"));
        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.syslog.logs.php", "ICO" => ico_eye, "TEXT" => "{events}:Syslogd"));
        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.php.logs.php", "ICO" => "fab fa-php", "TEXT" => "{events}:PHP"));

        $f[]="					 </ul>";
        $f[]="                </li>";
    }




    $f[]="<!-- LOGS_CENTER END  -->";
    $f[]="<!-- LEFT MENU END  -->";
    $f[]="";
    $f[]="        </div>";
    $f[]="    </nav>";
    $f[]="<script>";
    $f[]="$('#side-menu').metisMenu();";
    $f[]="var ff='';";
    $f[]="function MenuRoot(me,page,thirdLevel,thirdLevelid){";
   // $f[]="\talert($( this ).attr('id'));";
    $f[]="\tif(thirdLevel){";
    $f[]="\tvar html=document.getElementById(thirdLevelid).innerHTML;";
    $f[]="\tif(html.length>3){";
    $f[]="\t\t$( '#'+thirdLevelid  ).slideUp( 'slow', function() { $( '#'+thirdLevelid ).empty(); });";
    $f[]="\t}else{";
    $f[]="\t\tLoadAjaxSilent(thirdLevelid,thirdLevel);";
    $f[]="\t\t$( '#'+thirdLevelid  ).slideDown( 'slow');";
    $f[]="\t}";
    $f[]="}";
    $f[]="$('.nav').find('*').each(";
    $f[]="\t\tfunction(index,currentElement) {";
    $f[]="\t\t\tif($( this ).attr('id')=='left-menu'){";
    $f[]="\t\t\t\t$( this ).removeClass( \"active\" );";
    $f[]="\t\t\t}";
    $f[]="\t\t}";
    $f[]=");";
    $f[]="me.parent('li').addClass( 'active' );";
    $f[]="LoadAjaxSilent('MainContent',page);";
    $f[]="LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');";
    $f[]="}";

    $f[] = "function RemoveMiniNav(){";
    $f[] = "    if( $('body').hasClass('mini-navbar')){";
    $f[] = "        $('body').removeClass('mini-navbar');";
    $f[] = "    }";
    $f[] = "}";
    if(isset($_GET["removeclass"])) {
        $f[] = "setTimeout(\"RemoveMiniNav()\",1000);";
    }


    $f[]="</script>";

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}