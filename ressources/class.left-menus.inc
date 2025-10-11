<?php


class left_menus{
    private $HideCorporateFeatures;
    private $LOGS=array();
    private $users;

    public function __construct(){
        $this->HideCorporateFeatures=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideCorporateFeatures"));
        $this->users=new usersMenus();

    }

    public function harmpManaged():string{
        $EnableManagedReverseProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableManagedReverseProxy"));
        if($EnableManagedReverseProxy==0){return "";}
        if(!$this->users->AsHamrpAdmin){return "";}

        $tpl=new template_admin();
        $f[] = "<li id='left-menu'>";
        $f[] = "<a href='#' ><i class=\"".ico_load_balancer."\"></i> <span class=\"nav-label\">{APP_HAMRP}</span> </a>";
        $f[] = "<ul class='nav nav-second-level'>";

        $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");

        foreach ($_SESSION["HAMRP_PRIVS"] as $Groupid=>$none){
            if($Groupid==0){continue;}
            $ligne = $q->mysqli_fetch_array("SELECT * FROM groups WHERE ID='$Groupid'");
            $groupname       = $ligne["groupname"];
            $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.nginx.sites.php?harmpid=$Groupid",
                "ICO"=>ico_earth,"TEXT"=>"$groupname",
                "LEVEL3" => array(
                    "ID" => "fw-left-menus-harmp-$Groupid",
                    "PAGE" => "fw-left-menus-harmp.php?id=$Groupid")));
        }
        $f[] = "</ul>";
        return @implode("\n", $f);

    }

    public function ArticaMeta():string{
        $ArticaMetaEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaMetaEnabled"));
        if($ArticaMetaEnabled==0){
            return "";
        }
        $tpl=new template_admin();

        $f[] = "<li id='left-menu'>";
        $f[] = "<a href='#' ><i class=\"".ico_ameta."\"></i> <span class=\"nav-label\">{artica_meta}</span> </a>";
        $f[] = "<ul class='nav nav-second-level'>";
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.meta.status.php", "ICO"=>ico_dashboard,"TEXT"=>"{status}"));
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.meta.nodes.php", "ICO"=>ico_computer,"TEXT"=>"{managed_nodes}"));
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.meta.events.php", "ICO"=>ico_eye,"TEXT"=>"{events}"));
        $f[] = "</ul>";
        return @implode("\n", $f);
    }


    public function hamrp():string{
        $EnableManagedReverseProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableManagedReverseProxy"));
        if($EnableManagedReverseProxy==0){return "";}
        if(!$this->users->AsWebMaster){return "";}

        $tpl=new template_admin();
        $f[] = "<li id='left-menu'>";
        $f[] = "<a href='#' ><i class=\"".ico_load_balancer."\"></i> <span class=\"nav-label\">{APP_HAMRP}</span> </a>";
        $f[] = "<ul class='nav nav-second-level'>";
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.hamrp.status.php", "ICO"=>ico_dashboard,"TEXT"=>"{status}"));
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.hamrp.nodes.php", "ICO"=>ico_computer,"TEXT"=>"{managed_nodes}"));
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.hamrp.events.php", "ICO"=>ico_eye,"TEXT"=>"{events}"));
        $f[] = "</ul>";
        return @implode("\n", $f);


    }

    public function FireCracker(): string{
        if(!$this->users->AsVirtualBoxManager){return "";}
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_FIRECRACKER_INSTALLED"))==0){return "";}
        $tpl=new template_admin();
        $EnableFirecracker=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFirecracker"));
        if($EnableFirecracker==0){return "";}
        $f[]="<!-- APP_FIRECRACKER START -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fa-solid fa-layer-group\"></i> <span class=\"nav-label\">{APP_FIRECRACKER}</span> </a>";
        $f[] = "<ul class='nav nav-second-level'>";
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.firecracker.status.php", "ICO"=>ico_dashboard,"TEXT"=>"{status}"));
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.firecracker.nodes.php", "ICO"=>ico_computer,"TEXT"=>"{virtual_machines}"));
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.firecracker.images.php", "ICO"=>ico_cd,"TEXT"=>"{images}"));

        $f[] = "</ul>";
        return @implode("\n", $f);
    }

    public function NetWorkAgent(): string{
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KLNAGENT_INSTALLED"))==0){return "";}
        $EnableKlnagent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKlnagent"));
        if($EnableKlnagent==0){return "";}
        $f[]="<!-- KLNAGENT START -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fa fa-square\"></i> <span class=\"nav-label\">{KLNAGNT}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";
        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.klnagent.status.php');\">
													<i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{status}</span> </a>";
        $f[]="							</li>";
        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- KLNAGENT END -->";
        return @implode("\n",$f);

    }

    public function DWAgent():string{
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DWAgentEnable"))==0){return "";}
        $f[]="<!-- DWAGENT START -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fad fa-laptop-house\"></i> <span class=\"nav-label\">DWService</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";
        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.dwagent.status.php');\">
													<i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{status}</span> </a>";
        $f[]="							</li>";
        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- DWAGENT END -->";
        return @implode("\n",$f);
    }

    private function FireQOS_privs(): bool {
        if ($this->users->AsFirewallManager) {
            return true;
        }
        if ($this->users->AsSquidAdministrator) {
            return true;
        }
        return false;
    }
    public function Strongswan(): string {
        $EnableIPSEC=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStrongswanServer"));
        if($EnableIPSEC==0){return "";}
        if($this->users->isProxyManagerOnly()){return "";}
        if(!$this->users->AsVPNManager){return "";}
        if(!$this->users->STRONGSWAN_INSTALLED){return "";}
        $strongswan=new strongswan();
        if(intval($strongswan->main_array["GLOBAL"]["ENABLE_SERVER"]==0)){return "";}
        $f[]="<!-- STRONGSWAN START -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fab fa-connectdevelop\"></i> <span class=\"nav-label\">{APP_STRONGSWAN}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.strongswan.settings.php');\">
                                                    <i class=\"fas fa-cogs\"></i> <span class=\"nav-label\">{parameters}</span> </a>";
        $f[]="							</li>";
        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.strongswan.certs.php');\">
                                                    <i class=\"fas fa-file-certificate\"></i> <span class=\"nav-label\">{certificates}</span> </a>";
        $f[]="							</li>";
        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.strongswan.tunnels.php');\">
                                                    <i class=\"fas fa-road\"></i> <span class=\"nav-label\">{tunnel}</span> </a>";
        $f[]="							</li>";



        // $f[]="                			<li id='left-menu'>";
        // $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.strongswan.routes.php');\">
        //                                     <i class=\"fa fa-truck\"></i> <span class=\"nav-label\">{additional_routes}</span> </a>";
        // $f[]="							</li>";



        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.strongswan.sessions.php');\">
                                                    <i class=\"fa fa-users\"></i> <span class=\"nav-label\">{sessions}</span> </a>";
        $f[]="							</li>";
        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.strongswan.stats.php');\">
                                                <i class=\"fas fa-chart-area\"></i> <span class=\"nav-label\">{statistics}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.strongswan.connections.php');\">
                                                <i class=\"fas fa-link\"></i> <span class=\"nav-label\">{connections}</span> </a>";
        $f[]="							</li>";


        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.strongswan.events.php');\">
                                                <i class=\"".ico_eye."\"></i> <span class=\"nav-label\">{events}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.strongswan.about.php');\">
                                                <i class=\"".ico_eye."\"></i> <span class=\"nav-label\">{about2}</span> </a>";
        $f[]="							</li>";


        $f[] = "					 </ul>";
        $f[] = "                </li>";
        $f[] = "<!-- OPENVPN END -->";
        return @implode("\n", $f);
    }

    public function rustdesk():string{
        $EnableRustDeskServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRustDeskServer"));
        if($EnableRustDeskServer==0){return "";}
        $users=new usersMenus();
        if(!$users->AsFirewallManager){return "";}

        $tpl=new template_admin();
        $f[] = "<li id='left-menu'>";
        $f[] = "<a href='#' ><i class=\"".ico_laptop_down."\"></i> <span class=\"nav-label\">RustDesk</span> </a>";
        $f[] = "<ul class='nav nav-second-level'>";
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.rustdesk.status.php", "ICO"=>ico_dashboard,"TEXT"=>"{status}"));
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.rustdesk.clients.php", "ICO"=>ico_computer,"TEXT"=>"{clients}"));
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.rustdesk.events.php", "ICO"=>ico_eye,"TEXT"=>"{events}"));
        $f[] = "</ul>";
        return @implode("\n", $f);
    }

    public function dockerd():string{
        $EnableDockerService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDockerService"));
        if($EnableDockerService==0){return "";}
        $users=new usersMenus();
        if(!$users->AsAnAdministratorGeneric){return "";}
        $tpl=new template_admin();

        /*
        $f[] = "<li id='left-menu'>";
        $f[] = "<a href='#' ><i class=\"".ico_docker."\"></i> <span class=\"nav-label\">{web_services}</span> </a>";
        $f[] = "<ul class='nav nav-second-level'>";
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.docker.frontends.php", "ICO"=>ico_clouds,"TEXT"=>"{perimeters}"));
        $f[] = "</ul>";
        $f[] = "</li>";
*/
        $f[] = "<!-- DOCKER START -->";
        $f[] = "<li id='left-menu'>";
        $f[] = "<a href='#' ><i class=\"".ico_docker."\"></i> <span class=\"nav-label\">{APP_DOCKER}</span> </a>";
        $f[] = "<ul class='nav nav-second-level'>";
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.docker.status.php", "ICO"=>ico_dashboard,"TEXT"=>"{status}"));
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.docker.containers.php", "ICO"=>ico_containers,"TEXT"=>"{containers}"));
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.docker.images.php", "ICO"=>ico_cd,"TEXT"=>"{images}"));
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.docker.networks.php", "ICO"=>ico_networks,"TEXT"=>"{networks}"));
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.docker.volumes.php", "ICO"=>ico_hd,"TEXT"=>"{volumes}"));
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.docker.events.php", "ICO"=>ico_eye,"TEXT"=>"{events}"));
        $f[] = "</ul>";
        $f[] = "</li>";
        return @implode("\n", $f);
    }

    //KEEPALIVED
    public function keepalived(): string
    {
        $slaveIsenable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE"));
        if ($slaveIsenable == 1) {
            return "";
        }
        $APP_KEEPALIVED_ENABLE = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE"));
        if ($APP_KEEPALIVED_ENABLE == 0) {
            return "";
        }
        $f[] = "<!-- KEEPALIVED START -->";
        $f[] = "<li id='left-menu'>";
        $f[] = "<a href='#' ><i class=\"fas fa-project-diagram\"></i> <span class=\"nav-label\">{APP_KEEPALIVED} {keepalived_master}</span> </a>";
        $f[] = "<ul class='nav nav-second-level'>";
        $f[] = "                			<li id='left-menu'>";
        $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.keepalived.parameters.php');\">
                                                    <i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{parameters}</span> </a>";
        $f[] = "							</li>";
        $f[] = "                			<li id='left-menu'>";
        $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.keepalived.instances.php');\">
                                                    <i class=\"fas fa-network-wired\"></i> <span class=\"nav-label\">{services}</span> </a>";
        $f[] = "							</li>";
        $f[] = "                			<li id='left-menu'>";
        $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.keepalived.events.php');\">
                                                    <i class=\"fas fa-eye\"></i> <span class=\"nav-label\">{events}</span> </a>";
        $f[] = "							</li>";
        $f[] = "</ul>";
        $f[] = "</li>";

        return @implode("\n", $f);
    }
    //END KEEPALIVED

    //KEEPALIVED SLAVE
    public function keepalived_slave(): string
    {
        $masterIsenable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE"));
        if ($masterIsenable == 1) {
            return "";
        }
        $APP_KEEPALIVED_ENABLE = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE"));
        if ($APP_KEEPALIVED_ENABLE == 0) {
            return "";
        }
        $f[] = "<!-- KEEPALIVED START -->";
        $f[] = "<li id='left-menu'>";
        $f[] = "<a href='#' ><i class=\"fas fa-project-diagram\"></i> <span class=\"nav-label\">{APP_KEEPALIVED} {keepalived_secondary_nodes}</span> </a>";
        $f[] = "<ul class='nav nav-second-level'>";
        $f[] = "                			<li id='left-menu'>";
        $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.keepalived.slave.status.php');\">
                                                    <i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{status}</span> </a>";

        $f[] = "                			<li id='left-menu'>";
        $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.keepalived.events.php');\">
                                                    <i class=\"fas fa-eye\"></i> <span class=\"nav-label\">{events}</span> </a>";
        $f[] = "							</li>";
        $f[] = "</ul>";
        $f[] = "</li>";

        return @implode("\n", $f);
    }

    //END KEEPALIVED

    public function reverse_proxys(): string {
        if(!$this->users->AsProxyMonitor){return "";}
        $SHOWIT         = false;
        $EnableSSHPortal= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSSHPortal"));
        $tpl            = new template_admin();

        if($EnableSSHPortal==1){$SHOWIT=true;}
        if(!$SHOWIT){return "";}
        $f[]="<!-- REVERSE PROXY START -->";
        $f[]="    <li id='left-menu'>";
        $f[]="    <a href='#' ><i class=\"fas fa-water-lower\"></i> <span class=\"nav-label\">{reverse_proxy_server}</span> </a>";
        $f[]="    <ul class='nav nav-second-level'>";

        if($EnableSSHPortal==1){
            $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.sshportal.php", "ICO"=>"fas fa-terminal","TEXT"=>"{SSHPORTAL}"));
            $f[]="<ul class='nav nav-third-level'>";
            $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.sshportal.syslog.php", "ICO"=>ico_eye,"TEXT"=>"{events}"));
            $f[]="</ul>";
        }

        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- REVERSE PROXY END -->";
        return @implode("\n",$f);
    }
    public function LEGAL_LOGS():string{
        $users=new usersMenus();
        if(!$users->AsProxyMonitor) {
            return "";
        }
        $tpl=new template_admin();
        $f[]="<!-- LEGAL LOGS START -->";
        $f[]="    <li id='left-menu'>";
        $f[]="    <a href='#' ><i class=\"fas fa-file-archive\"></i> <span class=\"nav-label\">{legal_logs}</span> </a>";
        $f[]="    <ul class='nav nav-second-level'>";
        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.rotate.php", "ICO" => ico_params, "TEXT" => "{parameters}"));
        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.rotate.search.php", "ICO" => ico_search_in_file, "TEXT" => "{history_search}"));

        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.rotate.list.php", "ICO" => ico_database, "TEXT" => "{storage}"));

        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.rotate.events.php", "ICO" => ico_eye, "TEXT" => "{events}"));


        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- LEGAL LOGS END -->";
        return @implode("\n",$f);
    }

    public function LOG_SINK():string{
        $tpl=new template_admin();
        if(!$this->users->AsDebianSystem){return "";}
        $ActAsASyslogServer     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActAsASyslogServer"));
        if($ActAsASyslogServer==0){return "";}
        $EnableSyslogLogSink=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSyslogLogSink"));
        if($EnableSyslogLogSink==0){return "";}
        $f[]="<!-- LOG SINK START -->";
        $f[]="    <li id='left-menu'>";
        $f[]="    <a href='#' ><i class=\"fa-solid fa-cabinet-filing\"></i> <span class=\"nav-label\">{logs_sink}</span> </a>";
        $f[]="    <ul class='nav nav-second-level'>";

        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.syslogd.logsink.php",
            "ICO" => "fas fa-tachometer-alt", "TEXT" => "{status}"));
        $LogSynRTEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSynRTEnabled"));

        if($LogSynRTEnabled==1){
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.syslogd.logsink.realtime.php",
                "ICO" => ico_search_in_file, "TEXT" => "{realtime_events_squid}"));

        }
        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.relatime.php",
            "ICO" => ico_search_in_file, "TEXT" => "{APP_SQUID}"));
        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.dns.unbound.queries.php",
            "ICO" => ico_search_in_file, "TEXT" => "{DNS_QUERIES}"));

        //fw-left-menus-fortigate.php


        $f[] = $tpl->LeftMenu(
            array("PAGE" => "fw.fortinet.today.php",
                "ICO" => ico_firewall,
                "TEXT" => "Fortigate",
                "LEVEL3" => array(
                    "ID" => "fw-left-menus-fortigate",
                    "PAGE" => "fw-left-menus-fortigate.php")
            )
        );





        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.syslogd.browse.php",
            "ICO" => ico_history, "TEXT" => "{browse_events}"));

        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.syslogd.logsink.parameters.php",
            "ICO" => ico_params, "TEXT" => "{parameters}"));

        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.syslog.logs.php",
            "ICO" => ico_eye, "TEXT" => "{events}"));

        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- LOG SINK END -->";
        return @implode("\n",$f);
    }

    public function FireQOS(): string
    {
        if(!$this->FireQOS_privs()){return "";}
        $FIREQOS_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FIREQOS_INSTALLED"));
        if($FIREQOS_INSTALLED==0){return "";}
        $EnableFireQOS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFireQOS"));
        if($EnableFireQOS==0){return "";}
        $tpl=new template_admin();

//<i class="fas fa-list-ol"></i>
//<i class="fas fa-water-lower"></i>
        $f[]="<!-- FIREQOS START -->";
        $f[]="    <li id='left-menu'>";
        $f[]="    <a href='#' ><i class=\"fas fa-water-lower\"></i> <span class=\"nav-label\">{traffic_shapping}</span> </a>";
        $f[]="    <ul class='nav nav-second-level'>";
        $f[]=$tpl->LeftMenu(
            array("PAGE"=>"fw.network.fireqos.php","ICO"=>"fas fa-list-ol","TEXT"=>"{rules}"));
        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- FIREQOS END -->";

        return @implode("\n",$f);

    }

    public function ImapBox(): string
    {
        // <i class="fad fa-inbox-in"></i>
        //<i class="fad fa-mailbox"></i>
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableImapBox"))==0){return "";}
        if(!$this->users->AsMailBoxAdministrator){return "";}
        $tpl=new template_admin();
        $f[]="<!-- IMAPBOX START -->";
        $f[]="    <li id='left-menu'>";
        $f[]="        <a href='#' ><i class=\"fad fa-inbox-in\"></i> <span class=\"nav-label\">{APP_IMAPBOX}</span> </a>";
        $f[]="    <ul class='nav nav-second-level'>";


        $f[]=$tpl->LeftMenu(
            array("PAGE"=>"fw.imapbox.params.php",
                "ICO"=>"fad fa-wrench","TEXT"=>"{parameters}"));


        $f[]=$tpl->LeftMenu(
            array("PAGE"=>"fw.imapbox.accounts.php",
                "ICO"=>"fad fa-mailbox","TEXT"=>"{mailboxes}"));
        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- IMAPBOX END -->";
        return @implode("\n",$f);
    }


    public function FreeRadius(): string
    {

        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FREERADIUS_INSTALLED"))==0){return "";}
        $EnableFreeRadius=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFreeRadius"));
        if($EnableFreeRadius==0){return "";}
        if(!$this->users->AsProxyMonitor){return "";}
        $f[]="<!-- FREERADIUS START -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fa-solid fa-users-rays\"></i> <span class=\"nav-label\">{APP_FREERADIUS}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";
        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.freeradius.status.php');\">
													<i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{status}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.freeradius.services.php');\">
													<i class=\"fa fas fa-database\"></i> <span class=\"nav-label\">{databases}</span> </a>";
        $f[]="							</li>";


        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.freeradius.clients.php');\">
													<i class=\"fa fa-desktop\"></i> <span class=\"nav-label\">{radius_clients}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.freeradius.events.php');\">
													<i class=\"".ico_eye."\"></i> <span class=\"nav-label\">{events}</span> </a>";
        $f[]="							</li>";

        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- FREERADIUS END -->";
        return @implode("\n",$f);
    }

    public function WSUSOFFLINE(): string
    {

        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WSUSOFFLINE_INSTALLED"))==0){return "";}
        $EnableWsusOffline=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWsusOffline"));
        if($EnableWsusOffline==0){return "";}
        if(!$this->users->AsProxyMonitor){return "";}
        $f[]="<!-- WSUSOFFLINE START -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fa fab fa-windows\"></i> <span class=\"nav-label\">{APP_WSUSOFFLINE}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";
        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.wsusoffline.php');\">
														<i class=\"fa fa-wrench\"></i> <span class=\"nav-label\">{parameters}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.wsusoffline.client.php');\">
														<i class=\"fa fa-info\"></i> <span class=\"nav-label\">{clients} (howto)</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.wsusoffline.files.php');\">
														<i class=\"fa fa-list\"></i> <span class=\"nav-label\">{storage}</span> </a>";
        $f[]="							</li>";

        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- WSUSOFFLINE END -->";
        return @implode("\n",$f);
    }

    public function Rsync(): string
    {
        if(!$this->users->AsSystemAdministrator){return "";}
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RSYNC_INSTALLED"))==0){return "";}
        $EnableRsyncDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRsyncDaemon"));
        if($EnableRsyncDaemon==0){return "";}

        $f[]="<!-- RSYNC START -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fa fa-folder\"></i> <span class=\"nav-label\">{APP_RSYNC_SERVER}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.rsyncd.php');\">
														<i class=\"fa fa-wrench\"></i> <span class=\"nav-label\">{parameters}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.rsyncd.folders.php');\">
														<i class=\"fa fa-folder\"></i> <span class=\"nav-label\">{shared_folders}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.rsyncd.events.php');\">
														<i class=\"".ico_eye."\"></i> <span class=\"nav-label\">{events}</span> </a>";
        $f[]="							</li>";

        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- RSYNC END -->";
        return @implode("\n",$f);
    }

    private function isAdPrivs(): bool
    {

        if($this->users->AsSambaAdministrator){return true;}
        if($this->users->AsSystemAdministrator){return true;}
        if($this->users->AsSquidAdministrator){return true;}
        return false;
    }

    private function IsStandardAdConnection():bool{
        $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
        if($LockActiveDirectoryToKerberos==1){return true;}
        $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
        if($EnableKerbAuth==1){return true;}
        $EnableKerbNTLM=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbNTLM"));
        if($EnableKerbNTLM==1){return true;}
        return false;
    }

    public function ActiveDirectory(): string{
        $tpl=new template_admin();
        $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
        $EnableAdLDAPAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAdLDAPAuth"));
        if(!$this->users->CORP_LICENSE){return "<!-- CORP LICENSE FALSE -->";}
        if(!$this->isAdPrivs()){return "<!-- isAdPrivs FALSE -->";}
        if(!$this->users->MSKTUTIL_INSTALLED){return "<!-- MSKTUTIL_INSTALLED FALSE -->";}
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"))==0){return "";}
        if($this->HideCorporateFeatures==1){return "<!-- HideCorporateFeatures=1 -->";}
        $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
        $EnableKerbNTLM=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbNTLM"));

        $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
        $EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));

        $HIDEADCONNECT=false;
        if($Enablehacluster==1){
            $EnableAdLDAPAuth=0;
            $HIDEADCONNECT=true;
        }
        if($HaClusterClient==1){$HIDEADCONNECT=true;}
        if($EnablePostfix==1){$HIDEADCONNECT=true;}
        if($SQUIDEnable==0){$HIDEADCONNECT=true;}

        $f[]="<!-- SQUIDEnable=$SQUIDEnable -->";
        $f[]="<!-- HIDEADCONNECT=$HIDEADCONNECT -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fab fab fa-windows\"></i> <span class=\"nav-label\">Active Directory</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";

        if($Enablehacluster==0) {
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.ad.status.php",
                "ICO" => "fas fa-tachometer-alt", "TEXT" => "{status}"));
        }


        if(!$HIDEADCONNECT) {

            if($EnableAdLDAPAuth==0) {

                if ($EnableKerbNTLM == 0) {
                    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.system.kerberos-single.php",
                        "ICO" => "fas fa-plug", "TEXT" => "{kerberos_authentication}"));
                }


                if ($LockActiveDirectoryToKerberos == 0) {
                    if ($EnableKerbNTLM == 0) {
                        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.system.kerberos.php",
                            "ICO" => "fas fa-plug", "TEXT" => "{cluster_mode}"));
                    }

                    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.system.ActiveDirectory.php",
                        "ICO" => "fas fa-plug", "TEXT" => "{join_domain} (NTLM)"));
                }

            }

            if($SQUIDEnable==1){
                if(!$this->IsStandardAdConnection()) {
                    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.system.squidadldap.php",
                        "ICO" => "fas fa-plug", "TEXT" => "{squid_ldap_auth}"));
                }
            }

        }

        if($SQUIDEnable==0){
            if($Enablehacluster==0) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.system.ActiveDirectory.php",
                    "ICO" => "fas fa-plug", "TEXT" => "{join_domain}"));
            }

        }


        $APP_SAFEGUARD_AUTH_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SAFEGUARD_AUTH_INSTALLED"));
        if($APP_SAFEGUARD_AUTH_INSTALLED==1){
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.system.Safeguard.php",
                "ICO" => "fas fa-plug", "TEXT" => "SafeGuard Auth."));

        }
        $ADAGENT_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADAGENT_INSTALLED"));
        if($ADAGENT_INSTALLED==1) {
            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.ad-agent-intro.php",
                    "ICO" => "fas fa-users-viewfinder",
                    "TEXT" => "{AD_GROUP_SEARCH}",
                    "LEVEL3" => array(
                        "ID" => "fw-left-menus-ad-conns",
                        "PAGE" => "fw-left-menus-ad-conns.php")
                )
            );
        }else{
            $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.system.activedirectory.ldap.php",
                "ICO"=>"fas fa-link","TEXT"=>"{connections}"));
        }


        if($Enablehacluster==0) {
            if($EnablePostfix==0) {
                if($SQUIDEnable==1) {
                    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.ad.white.php",
                        "ICO" => "fa fa-align-justify", "TEXT" => "{whitelists}"));
                }
            }
            if($EnablePostfix==0) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.activedirectory.watchdog.php",
                    "ICO" => ico_eye, "TEXT" => "{events}"));
            }
        }
        if($EnablePostfix==0) {
            if ($EnableKerbNTLM == 1) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.winbindd.events.php",
                    "ICO" => ico_eye, "TEXT" => "{events}: Winbind"));

            }
        }

        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- ACTIVE DIRECTORY END -->";
        return @implode("\n",$f);

    }


    public function FileBeat():string{
        $EnableFileBeat=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFileBeat"));
        if(!$this->users->AsWebStatisticsAdministrator) {return "";}
        if ($EnableFileBeat == 0) {return "";}
        $f[] = "<!-- FILEBEAT START -->";
        $f[] = "<li id='left-menu'>";
        $f[] = "    <a href='#' ><i class=\"fas fa-file-medical-alt\"></i> <span class=\"nav-label\">{APP_FILEBEAT}</span> </a>";
        $f[] = "<ul class='nav nav-second-level'>";
        $f[] = "<li id='left-menu'>";
        $f[] = "<a href='#' OnClick=\"MenuRoot( $(this),'fw.filebeat.status.php');\">
                <i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{status}</span> </a>";
        $f[] = "</li>";
        $f[] = "</ul>";
        $f[] = "</li>";
        $f[] = "<!-- FILEBEAT END -->";
        return @implode("\n",$f);
    }

    public function Syncthing():string{
        $EnableSyncThing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSyncThing"));
        if($EnableSyncThing==0){return "";}
        if(!$this->users->AsSambaAdministrator){return "";}
        $tpl=new template_admin();
        $f[]="<!-- EnableSyncThing=$EnableSyncThing -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fas fa-chart-network\"></i> <span class=\"nav-label\">{APP_SYNCTHING}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.syncthing.php",
            "ICO"=>ico_dashboard,"TEXT"=>"{status}"));

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.syncthing.instances.php",
            "ICO"=>ico_servcloud2,"TEXT"=>"{instances}"));

        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- SYNCTHING END -->";

        return @implode("\n",$f);
    }

    public function Samba():string{
        //--------------------------- SAMBA ----------------------------------------------------------------------------
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SAMBA_INSTALLED"))==0){return "";}
        $EnableSamba=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSamba"));
        if($EnableSamba==0){return "";}
        $disk=ico_hd;
        $tpl=new template_admin();
        if(!$this->users->AsSambaAdministrator){return "";}
        $f[]="<!-- SAMBA START -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fa fa-folder\"></i> <span class=\"nav-label\">{fileshare}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.samba.php');\">
														<i class=\"fa fa-wrench\"></i> <span class=\"nav-label\">{status}</span> </a>";
        $f[]="							</li>";


        $f[]=$tpl->LeftMenu(
            array("PAGE"=>"fw.samba.instances.php",
                "ICO"=>"fas fa-layer-group","TEXT"=>"{instances}",
            ) );

        $f[]=$tpl->LeftMenu(
            array("PAGE"=>"fw.samba.shares.php",
                "ICO"=>ico_directory,"TEXT"=>"{shares_center}",
            ) );


        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.btrfs.php');\">
														<i class=\"$disk\"></i> <span class=\"nav-label\">{storage}</span> </a>";
        $f[]="							</li>";



        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.smbd.events.php');\">
														<i class=\"".ico_eye."\"></i> <span class=\"nav-label\">{events}</span> </a>";
        $f[]="							</li>";

        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- RSYNC END -->";

        return @implode("\n",$f);
    }

    public function HaProxy(){

        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_INSTALLED"))==0){return "";}
        $EnableHaProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHaProxy"));
        if($EnableHaProxy==0){return "";}
        if(!$this->users->AsProxyMonitor){return "";}
        $f[]="<!-- HAPPROXY START -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fas fa-code-branch\"></i> <span class=\"nav-label\">Load-Balancing/Reverse</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.haproxy.monitor.php');\">
														<i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{backends_status}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.haproxy.tail.php');\">
														<i class=\"".ico_eye."\"></i> <span class=\"nav-label\">{requests}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.haproxy.status.php');\">
														<i class=\"fa fa-th-large\"></i> <span class=\"nav-label\">{service2}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.haproxy.services.php');\">
														<i class=\"fa fa-share-alt\"></i> <span class=\"nav-label\">{tcp_services}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.haproxy.acls.php');\">
														<i class=\"far fa-sliders-h\"></i> <span class=\"nav-label\">{rules}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.haproxy.groups.php');\">
														<i class=\"fa fa-cubes\"></i> <span class=\"nav-label\">{objects}</span> </a>";
        $f[]="							</li>";




        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- HAPPROXY END -->";
        return @implode("\n",$f);
    }

    public function GreenSQL(){
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGreenSQL"))==0){return "";}
        if(!$this->users->AsProxyMonitor){return "";}
        $f[]="<!-- GREENSQL START -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fas fa-database\"></i> <span class=\"nav-label\">{APP_GREENSQL}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.greensql.status.php');\">
														<i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{status}</span> </a>";
        $f[]="							</li>";


        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.greensql.routers.php');\">
														<i class=\"fas fa-exchange\"></i> <span class=\"nav-label\">{routers}</span> </a>";
        $f[]="							</li>";


        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.greensql.rules.php');\">
														<i class=\"fas fa-bars\"></i> <span class=\"nav-label\">{rules}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.greensql.alerts.php');\">
														<i class=\"fas fa-bell\"></i> <span class=\"nav-label\">{alerts}</span> </a>";
        $f[]="							</li>";
        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.greensql.approve.php');\">
														<i class=\"fas fa-thumbs-up\"></i> <span class=\"nav-label\">{approve}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.greensql.events.php');\">
														<i class=\"".ico_eye."\"></i> <span class=\"nav-label\">{events}</span> </a>";
        $f[]="							</li>";



        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- GREENSQL END -->";
        return @implode("\n",$f);


    }


    public function Xapian(){
        $EnableXapianSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableXapianSearch"));
        if($EnableXapianSearch==0){return "";}
        if(!$this->users->AsAnAdministratorGeneric){return "";}
        $f[]="<!-- InstantSearch START -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fa fa-search\"></i> <span class=\"nav-label\">{InstantSearch}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.xapian.php');\">
												<i class=\"fas fa-cogs\"></i> <span class=\"nav-label\">{parameters}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.xapian.resources.php');\">
												<i class=\"fa fas fa-database\"></i> <span class=\"nav-label\">{resources}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.system.tasks.php?ForceTaskType=24');\">
												<i class=\"fa fa-clock\"></i> <span class=\"nav-label\">{schedules}</span> </a>";
        $f[]="							</li>";

        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- InstantSearch END -->";
        return @implode("\n",$f);
    }

    private function UnboundActive(){
        $EnablePDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
        $UnboundInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundInstalled"));
        $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
        if($EnablePDNS==0) {
            if ($UnboundInstalled == 1) {
                if ($UnboundEnabled == 1) {
                    return 1;
                }
            }
        }
        return 0;

    }

    function PRADS(){
        $f[]="<!-- ".__LINE__." PRADS START -->";
        $EnablePrads=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePrads"));
        if($EnablePrads==0){return "";}
        if(!$this->users->AsAnAdministratorGeneric){return "";}
        $tpl=new template_admin();
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.network.prads.php", "ICO"=>"fal fa-radar","TEXT"=>"{passive_detection}"));
        return @implode("\n",$f);
    }

    public function DNS()
    {
        $EnablePDNS = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
        $MAIN_TITLE = "DNS";
        $DNS_SERVICE=false;

        $APP_RBLDNSD_ENABLED = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RBLDNSD_ENABLED"));
        $PDNSStatsEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSStatsEnabled"));
        $EnableDNSDist = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));

        if ($EnablePDNS == 1) {
            $DNS_SERVICE = true;
        }

        $f[] = "<!-- " . __LINE__ . " DNS START -->";
        if (!$this->users->AsAnAdministratorGeneric) {
            return "";
        }
        $tpl = new template_admin();

        $f[] = "                <li id='left-menu'>";
        $f[] = "                    <a href='#' ><i class=\"fa fas fa-database\"></i> <span class=\"nav-label\">$MAIN_TITLE</span> </a>";
        $f[] = "                    <ul class='nav nav-second-level'>";

        if ($this->users->AsDnsAdministrator) {
            if (($this->UnboundActive() == 1)) {
                $f[] = $tpl->LeftMenu(
                    array("PAGE" => "fw.dns.unbound.php",
                        "ICO" => "fas fa-database",
                        "TEXT" => "{APP_UNBOUND}",
                        "LEVEL3" => array(
                            "ID" => "fw-left-menus-dns",
                            "PAGE" => "fw-left-menus-dns.php")
                    )
                );
                $DNS_SERVICE = true;
            }


        if ($EnableDNSDist == 1) {
            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.dns.unbound.php",
                    "ICO" => "fab fa-gripfire",
                    "TEXT" => "{APP_DNSDIST}",
                    "LEVEL3" => array(
                        "ID" => "fw-left-menus-dns",
                        "PAGE" => "fw-left-menus-dns.php")
                )
            );

            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.dns.forward.zone.php",  "ICO"=>"far fa-arrows","TEXT"=>"{forward_zones}"));

        }
        if ($EnablePDNS == 1) {

            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.pdns.status.php",
                    "ICO" => "fas fa-database",
                    "TEXT" => "{APP_PDNS}",
                    "LEVEL3" => array(
                        "ID" => "fw-left-menus-pdns",
                        "PAGE" => "fw-left-menus-pdns.php")
                )
            );
        }
        }
        if($this->users->AsPostfixAdministrator OR $this->users->AsDnsAdministrator){
            if($APP_RBLDNSD_ENABLED==1){
                $f[]=$tpl->LeftMenu( array("PAGE"=>"fw.rbldnsd.php","ICO"=>"fas fa-database","TEXT"=>"{APP_RBLDNSD}","LEVEL3" => array(
                    "ID" => "fw-left-menus-rbldnsd",
                    "PAGE" => "fw-left-menus-rbldnsd.php")));
            }
        }

        $f[]=$tpl->LeftMenu(
            array("PAGE"=>"fw.dns.servers.php",
                "ICO"=>"fa-solid fa-arrows-turn-to-dots","TEXT"=>"{dns_servers}",
            ) );
       $f[] = $tpl->LeftMenu(array("PAGE" => "fw.reputations.php", "ICO" => ico_clouds, "TEXT" => "{reputation}"));


        if($this->users->AsDnsAdministrator==1){
            $f[]=$tpl->LeftMenu(  array("PAGE"=>"fw.dns.watchdog.php",
                "ICO"=>ico_watchdog,"TEXT"=>"{watchdog}",
            ) );
        }



        if($DNS_SERVICE){
            if($this->users->AsDnsAdministrator) {
                if ($PDNSStatsEnabled == 1) {
                    $f[]=$tpl->LeftMenu(
                        array("PAGE"=>"fw.dsc.statistics.php",
                            "ICO"=>"fas fa-chart-pie","TEXT"=>"{statistics}",
                        ) );

                }
            }

        }

        if(!$this->users->AsDockerWeb) {
            if ($this->users->AsDnsAdministrator) {
                $EnableDNSCryptProxy = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSCryptProxy"));
                if ($EnableDNSCryptProxy == 1) {
                    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.dnscrypt-proxy.events.php",
                        "ICO" => ico_eye, "TEXT" => "DNSCrypt: {events}"));

                }
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.etc-hosts.php", "ICO" => "fa-duotone fa-laptop-file", "TEXT" => "{etc_hosts}"));
            }

            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.computers.php", "ICO" => "fa-solid fa-computer", "TEXT" => "{my_computers}"));
        }
        $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
        if ($UnboundEnabled == 1) {
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.dns.unbound.queries.php", "ICO" => ico_eye, "TEXT" => "{DNS_QUERIES}"));
        }


        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- DNS END -->";
        return @implode("\n",$f);
    }

    function TailScale():string{
        $tpl=new template_admin();
        $users  = new usersMenus();
        if(!$users->AsVPNManager){return "";}
        $APP_TAILSCALE_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_TAILSCALE_INSTALLED"));
        $EnableTailScaleService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTailScaleService"));
        if($APP_TAILSCALE_INSTALLED==0){$EnableTailScaleService=0;}
        if($EnableTailScaleService==0){return "";}

        $f[]="<!-- TAILSCALE START -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fa fa-compress\"></i> <span class=\"nav-label\">TailScale VPN</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.tailscale.status.php",
            "ICO"=>"fas fa-tachometer-alt","TEXT"=>"{status}"));

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.tailscale.events.php",
            "ICO"=>ico_eye,"TEXT"=>"{events}"));


        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- TAILSCALE END -->";
        return @implode("\n",$f);

    }

    function OpenVPN(){
        if($this->users->isProxyManagerOnly()){return "";}
        if(!$this->users->AsVPNManager){return "";}
        if(!$this->users->OPENVPN_INSTALLED){return "";}
        $EnableOpenVPNServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenVPNServer"));
        if($EnableOpenVPNServer==0){return "";}

        $vpn=new openvpn();
        if(intval($vpn->main_array["GLOBAL"]["ENABLE_SERVER"]==0)){return "";}
        $f[]="<!-- OPENVPN START -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fa fa-compress\"></i> <span class=\"nav-label\">OpenVPN</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.openvpn.settings.php');\">
												<i class=\"fas fa-cogs\"></i> <span class=\"nav-label\">{parameters}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.openvpn.clients.php');\">
												<i class=\"fas fa-users-crown\"></i> <span class=\"nav-label\">{clients}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.openvpn.profiles.php');\">
												<i class=\"fas fa-scroll\"></i> <span class=\"nav-label\">{profiles}</span> </a>";
        $f[]="							</li>";
        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.openvpn.sessions.php');\">
												<i class=\"fa fa-users\"></i> <span class=\"nav-label\">{sessions}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.openvpn.connections.php');\">
											<i class=\"fas fa-link\"></i> <span class=\"nav-label\">{connections}</span> </a>";
        $f[]="							</li>";
        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.openvpn.events.php');\">
											<i class=\"".ico_eye."\"></i> <span class=\"nav-label\">{events}</span> </a>";
        $f[]="							</li>";


        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- OPENVPN END -->";
        return @implode("\n",$f);
    }


    function Threeproxy(){
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enable3Proxy"))==0){return "";}
        $f[]="<!-- 3PRoxY START -->";


        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fas fa-globe\"></i> <span class=\"nav-label\">{APP_3PROXY}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";
        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.3proxy.status.php');\">
												<i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{status}</span> </a>";
        $f[]="							</li>";

        if($this->users->AsFirewallManager){
            $f[]="                			<li id='left-menu'>";
            $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.3proxy.services.php');\">
													<i class=\"fa fa-align-justify\"></i> <span class=\"nav-label\">{services}</span> </a>";
            $f[]="							</li>";
            $f[]="                			<li id='left-menu'>";
            $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.3proxy.errors.php');\">
													<i class=\"fas fa-file-alt\"></i> <span class=\"nav-label\">{errors_pages}</span> </a>";
            $f[]="							</li>";
        }
        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.3proxy.requests.php');\">
												<i class=\"".ico_eye."\"></i> <span class=\"nav-label\">{requests}</span> </a>";
        $f[]="							</li>";

        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- 3PRoxY END -->";
        return @implode("\n",$f);
    }


    public function ProFTPD():string{
        if(!$this->users->AsSystemAdministrator){return "";}
        $ProFTPDInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProFTPDInstalled"));
        $EnableProFTPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProFTPD"));

        if($ProFTPDInstalled==0){return "";}
        if($EnableProFTPD==0){return "";}
        $f[]="<!-- PROFTPD START -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fas fa-folder-open\"></i> <span class=\"nav-label\">{FTP_SERVICE}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proftpd.settings.php');\">
												<i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{status}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proftpd.members.php');\">
												<i class=\"far fa-users\"></i> <span class=\"nav-label\">{members}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proftpd.daemon.events.php');\">
											<i class=\"".ico_eye."\"></i> <span class=\"nav-label\">{service_events}</span> </a>";
        $f[]="							</li>";


        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- PROFTPD END -->";
        return @implode("\n",$f);

    }


    public function Fail2Ban(){


        $FAIL2BAN_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FAIL2BAN_INSTALLED"));
        if($FAIL2BAN_INSTALLED==0){return "";}
        $EnableFail2Ban=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFail2Ban"));
        if($EnableFail2Ban==0){return "";}
        if(!$this->users->AsFirewallManager){return "";}
        $f[]="<!-- FAIL2BAN START -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fas fa-boxing-glove\"></i> <span class=\"nav-label\">{APP_FAIL2BAN}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";


        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.fail2ban.dashboard.php');\">
														<i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{dashboard}</span> </a>";
        $f[]="							</li>";


        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.fail2ban.settings.php');\">
												<i class=\"fas fa-cogs\"></i> <span class=\"nav-label\">{parameters}</span> </a>";
        $f[]="							</li>";


        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.fail2ban.events.php');\">
											<i class=\"".ico_eye."\"></i> <span class=\"nav-label\">{ids_events}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.fail2ban.daemon.events.php');\">
											<i class=\"".ico_eye."\"></i> <span class=\"nav-label\">{service_events}</span> </a>";
        $f[]="							</li>";

        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- FAIL2BAN END -->";

        return @implode("\n",$f);
    }


    private function isIds():bool{
        if($this->users->isProxyManagerOnly()){return false;}
        if(isset($_GET["dynacls"])){return false;}
        if(!$this->users->AsFirewallManager){return false;}
        $EnableNetMonix=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNetMonix");
        $NetMonixInstalled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetMonixInstalled");
        if($NetMonixInstalled==0){$EnableNetMonix=0;}
        if($EnableNetMonix==1){return true;}
        if(!$this->users->SURICATA_INSTALLED){return false;}
        $EnableSuricata=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSuricata"));
        if(!$this->users->SURICATA_INSTALLED){
            $EnableSuricata=0;
        }
        if($EnableSuricata==1){return true;}
        return false;
    }

    public function Suricata(){
        if(!$this->isIds()){
            return "";
        }

        $EnableNetMonix=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNetMonix");
        $NetMonixInstalled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetMonixInstalled");
        if($NetMonixInstalled==0){$EnableNetMonix=1;}


        $EnableSuricata=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSuricata"));
        if(!$this->users->SURICATA_INSTALLED){
            $EnableSuricata=0;
        }
        $tpl=new template_admin();
        $f[]="<!-- IDS START -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fa fa-binoculars\"></i> <span class=\"nav-label\">IDS</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";

        $f[] = $tpl->LeftMenu(
            array("PAGE" => "fw.ids.dashboard.php", "ICO" => "fas fa-tachometer-alt", "TEXT" => "{dashboard}", ));



        if($EnableSuricata==1) {

            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.ids.categories.php", "ICO" => "fa fa-sitemap", "TEXT" => "{categories}", ));



            $f[] = "                			<li id='left-menu'>";
            $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.ids.rules.php');\">
											<i class=\"fas fa-list\"></i> <span class=\"nav-label\">{rules}</span> </a>";
            $f[] = "
											</li>";


            $f[] = "                			<li id='left-menu'>";
            $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.ids.updates.php');\">
											<i class=\"" . ico_download . "\"></i> <span class=\"nav-label\">{updates}</span> </a>";
            $f[] = "							</li>";


            $f[] = "                			<li id='left-menu'>";
            $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.ids.firewall.php');\">
											<i class=\"fab fa-free-code-camp\"></i> <span class=\"nav-label\">{firewall_rules}</span> </a>";
            $f[] = "							</li>";

            $f[] = "                			<li id='left-menu'>";
            $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.ids.events.php');\">
											<i class=\"" . ico_hacker . "\"></i> <span class=\"nav-label\">{detected_threats}</span> </a>";
            $f[] = "							</li>";

            $f[] = "                			<li id='left-menu'>";
            $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.ids.service-events.php');\">
											<i class=\"" . ico_eye . "\"></i> <span class=\"nav-label\">{service_events}</span> </a>";
            $f[] = "							</li>";
        }



        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- IDS END -->";
        return @implode("\n",$f);
    }
    public function isFireWall():int{
        if($this->users->isProxyManagerOnly()){return 0;}
        if(isset($_GET["dynacls"])){return 0;}
        if(!$this->users->AsFirewallManager){return 0;}
        $EnableCrowdSec=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCrowdSec"));
        if($EnableCrowdSec==1){return 1;}
        $ArticaPSnifferDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferDaemon"));
        if($ArticaPSnifferDaemon==1){return 1;}

        $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
        if($FireHolEnable==0){return 0;}

        return 1;
    }

    public function FireHole(){
        $tpl=new template_admin();
        if($this->isFireWall()==0){return "";}
        $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
        $EnableCrowdSec=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCrowdSec"));
        $ArticaPSnifferDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferDaemon"));
        $CrowdSecAdded=false;

        $f[]="<!-- FIREWALL START -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fab fa-free-code-camp\"></i> <span class=\"nav-label\">{your_firewall}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";

        if($FireHolEnable==1) {
            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.settings.php",
                    "ICO" => ico_options, "TEXT" => "{parameters}",
                ));

        }


        if ($EnableCrowdSec == 1) {
            $CrowdSecAdded=true;
            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.crowdsec.php", "ICO" => "far fa-shield-cross", "TEXT" => "CrowdSec"));
        }

        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.ipfeeds.php", "ICO" => "fas fa-hockey-mask", "TEXT" => "{CybercrimeIPFeeds}"));

        if($FireHolEnable==1) {
            $f[]=$tpl->LeftMenu(
                array("PAGE"=>"fw.rules.php",
                    "ICO"=>ico_list_opt,"TEXT"=>"{firewall_rules}",
                ) );

            $f[]=$tpl->LeftMenu(
                array("PAGE"=>"fw.services.php",
                    "ICO"=>ico_table,"TEXT"=>"{firewall_services}",
                ) );

            $f[]=$tpl->LeftMenu(
                array("PAGE"=>"fw.proxy.objects.list.php?firewall=yes",
                    "ICO"=>"fas fa-cubes","TEXT"=>"{firewall_objects}",
                ) );

            $f[]=$tpl->LeftMenu(
            array("PAGE"=>"fw.bridges.php",
                "ICO"=>"fad fa-router","TEXT"=>"{interfaces_connectors}"));

            $f[]=$tpl->LeftMenu(
                array("PAGE"=>"fw.nat.php",
                "ICO"=>ico_arrow_right,"TEXT"=>"N.A.T"));

            $f[]=$tpl->LeftMenu(
                array("PAGE"=>"fw.masquerade.php",
                "ICO"=>"fas fa-mask","TEXT"=>"Masquerade"));

            if ($EnableCrowdSec == 1) {
                if(!$CrowdSecAdded) {
                    $f[] = $tpl->LeftMenu(
                        array("PAGE" => "fw.crowdsec.php",
                            "ICO" => "far fa-shield-cross", "TEXT" => "CrowdSec",
                        ));
                }
            }
        }

        $EnableRedSocks=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedSocks"));
        if($FireHolEnable==1) {
            if ($EnableRedSocks == 1) {
                $f[]=$tpl->LeftMenu(
                    array("PAGE"=>"fw.redsocks.php",
                        "ICO"=>"fa fa-arrow-right","TEXT"=>"{APP_REDSOCKS}"));
            }

            if ($EnableCrowdSec == 0) {
                $f[] = $tpl->LeftMenu(
                    array("PAGE" => "fw.ipfeeds.php",
                        "ICO" => "fas fa-hockey-mask", "TEXT" => "{CybercrimeIPFeeds}"));
            }


            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.traffic.shaping.php",
                    "ICO" => "fas fa-filter", "TEXT" => "{traffic_shaping}"));
        }

        $f[]=$tpl->LeftMenu(
            array("PAGE"=>"fw.firewall.config.php",
                "ICO"=>"fa fa-align-justify","TEXT"=>"{config}"));

        $f[]=$tpl->LeftMenu(
            array("PAGE"=>"fw.syslog.php",
                "ICO"=>ico_eye,"TEXT"=>"{events}"));

        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- FIREWALL END -->";

        return @implode("\n",$f);
    }

    public function KEA():string{
        $tpl=new template_admin();
        $KEA_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KEA_INSTALLED"));
        $EnableKEA=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKEA"));
        $NTPDEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDEnabled"));
        if($KEA_INSTALLED==0){$EnableKEA=0;}
        if($EnableKEA==0){return "";}
        $f[]="<!-- KEA START -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fa fa-desktop\"></i> <span class=\"nav-label\">DHCP</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.kea.php",
            "ICO"=>"fas fa-tachometer-alt","TEXT"=>"{APP_KEA_DHCPD4}"));

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.dhcp.last.php",
            "ICO"=>ico_computer,"TEXT"=>"{last_computers}"));

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.dhcp.reservations.php",
            "ICO"=>ico_computer_down,"TEXT"=>"{reservations}"));

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.kea.ddns.php",
            "ICO"=>ico_retweet,"TEXT"=>"DDNS"));


        if($NTPDEnabled==1){
            $f[]=$tpl->LeftMenu(
                array("PAGE"=>"fw.system.ntpd.php",
                    "ICO"=>"fa-clock","TEXT"=>"{time_server}"));

        }


        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.kea.events.php",
            "ICO"=>ico_eye,"TEXT"=>"{events}"));



        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- KEA END -->";
        return @implode("\n",$f);
    }

    public function APP_NETBOX():string{
        $tpl=new template_admin();
        $EnableNetBox=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNetBox"));
        if($EnableNetBox==0){
            return "<!-- NETBOX DISABLED  -->";
        }

        $f[]="<!-- NETBOX START  -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fas fa-box-full\"></i> <span class=\"nav-label\">{APP_NETBOX}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.netbox.status.php",
            "ICO"=>"fas fa-tachometer-alt","TEXT"=>"{status}"));

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.netbox.administrators.php",
            "ICO"=>ico_administrator,"TEXT"=>"{administrators}"));

        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- NETBOX END -->";
        return @implode("\n",$f);
    }

    public function DHCP(){
        $SHOWMENU=false;
        $tpl=new template_admin();
        $DHCPDInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDInstalled"));
        $EnableDHCPRelay=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPRelay"));
        $EnableDHCPServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer"));
        VERBOSE("DHCPDInstalled == $DHCPDInstalled", __LINE__);
        VERBOSE("EnableDHCPRelay == $EnableDHCPRelay", __LINE__);
        VERBOSE("EnableDHCPServer == $EnableDHCPServer", __LINE__);
        if($DHCPDInstalled==0){$EnableDHCPServer=0;}

        if($EnableDHCPServer==1){$SHOWMENU=true;}
        if($EnableDHCPRelay==1){$SHOWMENU=true;}
        if(!$SHOWMENU){return "";}

        $f[]="<!-- DHCP START -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fa fa-desktop\"></i> <span class=\"nav-label\">DHCP/TFTP</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";

        if($EnableDHCPRelay==1){
            $f[]="                			<li id='left-menu'>";
            $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.dhcprelay.php');\">
													<i class=\"fas fa-exchange\"></i> <span class=\"nav-label\">{APP_DHCP_RELAY}</span> </a>";
            $f[]="							</li>";
            $f[]="                			<li id='left-menu'>";
            $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.dhcp.events.php');\">
													<i class=\"".ico_eye."\"></i> <span class=\"nav-label\">{events}</span> </a>";
            $f[]="							</li>";

        }


        if($EnableDHCPServer==1){

            $f[]="                			<li id='left-menu'>";
            $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.dhcp.status.php');\">
													<i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{status}</span> </a>";
            $f[]="							</li>";

            $f[]="                			<li id='left-menu'>";
            $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.dhcp.configuration.php');\">
														<i class=\"fas fa-cogs\"></i> <span class=\"nav-label\">{APP_DHCP}</span> </a>";
            $f[]="							</li>";

            $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.dhcp.reservations.php",
                "ICO"=>ico_computer_down,"TEXT"=>"{reservations}"));

            $f[]="                			<li id='left-menu'>";
            $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.dhcp.last.php');\">
													<i class=\"fa fa-desktop\"></i> <span class=\"nav-label\">{last_computers}</span> </a>";
            $f[]="							</li>";


            $f[]="                			<li id='left-menu'>";
            $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.dhcp.leases.php');\">
													<i class=\"".ico_timeout."\"></i> <span class=\"nav-label\">{leases}</span> </a>";
            $f[]="							</li>";

            $f[]="                			<li id='left-menu'>";
            $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.dhcp.requests.php');\">
													<i class=\"".ico_eye."\"></i> <span class=\"nav-label\">{dhcp_requests}</span> </a>";
            $f[]="							</li>";
            $f[]="                			<li id='left-menu'>";
            $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.dhcp.events.php');\">
													<i class=\"".ico_eye."\"></i> <span class=\"nav-label\">{events}</span> </a>";
            $f[]="							</li>";
        }
        $f[]=$this->TFPServer();
        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- DHCP END -->";
        return @implode("\n",$f);
    }

    public function TFPServer(){
        if(!$this->users->TFTPD_INSTALLED){return "";}
        $EnableTFTPDServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTFTPDServer"));
        if($EnableTFTPDServer==0){return "";}

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.tftpd.configuration.php');\">
													<i class=\"fas fa-cogs\"></i> <span class=\"nav-label\">TFTP</span> </a>";
        $f[]="							</li>";
        return @implode("\n",$f);
    }

    public function MessagingSub($ID=0,$name=null):string{
        $tpl=new template_admin();
        $EnableDKFilter=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDKFilter"));
        $MilterGreyListEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreyListEnabled"));
        $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));

        $main_title="{smtp_router}";
        if($ID>0){
            $main_title=$name;
        }

        $f[]="<!-- MESSAGING START  -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"far fa-envelope\"></i> <span class=\"nav-label\">$main_title</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";



        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.status.php?instance-id=$ID",
            "ICO"=>"fas fa-tachometer-alt","TEXT"=>"{status}"));

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.settings.php?instance-id=$ID",
            "ICO"=>"fa-solid fa-sliders","TEXT"=>"{global_settings}"));

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.queue.php?instance-id=$ID",
            "ICO"=>"fal fa-list-ul","TEXT"=>"{queue}"));

        $f[] = $tpl->LeftMenu(
            array("PAGE" => "fw.articamilter.php?instance-id=$ID",
                "ICO" => ico_plug,
                "TEXT" => "Artica Milter",
                "LEVEL3" => array(
                    "ID" => "fw-left-menus-articamilter",
                    "PAGE" => "fw-left-menus-articamilter.php?instance-id=$ID")
            )
        );

        if($ID==0){
            if($this->isFireWall()==1){
                $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.firewall.php",
                    "ICO"=>"fa-free-code-camp","TEXT"=>"{firewall}"));
            }
            if ($EnableActiveDirectoryFeature == 1) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.postfix.activedirectory.php",
                    "ICO" => "fab fa-windows", "TEXT" => "Active Directory"));
            }
        }

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.routing.php?instance-id=$ID",
            "ICO"=>"fas fa-bus","TEXT"=>"{transport_settings}"));

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.rewrite.php?instance-id=$ID",
            "ICO"=>"fas fa-repeat-alt","TEXT"=>"{smtp_generic_maps}"));

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.rbl.php?instance-id=$ID",
            "ICO"=>"fas fa-badge-check","TEXT"=>"{ip_reputation}"));


        if($EnableDKFilter==1){
            $f[]="                			<li id='left-menu'>";
            $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.postfix.dkim.php');\">
															<i class=\"fas fa-badge-check\"></i> <span class=\"nav-label\">{APP_OPENDKIM}</span> </a>";
            $f[]="							</li>";


        }




        if($MilterGreyListEnabled==1){
            $f[]="                			<li id='left-menu'>";
            $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.postfix.milter-greylist.php');\">
															<i class=\"fas fa-badge-check\"></i> <span class=\"nav-label\">{APP_MILTERGREYLIST}</span> </a>";
            $f[]="							</li>";


        }

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.rules.php?instance-id=$ID",
            "ICO"=>"fas fa-filter","TEXT"=>"{global_smtp_rules}"));


        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.blackwite.php?instance-id=$ID",
            "ICO"=>"fas fa-filter","TEXT"=>"{blacklist}/{whitelist}"));

        if($ID==0) {
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.postfix.refused.php",
                "ICO" => ico_eye, "TEXT" => "{refused}"));
        }


        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- MESSAGING END  -->";
        return @implode("\n",$f);

    }

    public function Messaging(){
        $tpl=new template_admin();
        $EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
        $POSTFIX_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_INSTALLED"));
        if($POSTFIX_INSTALLED==0){$EnablePostfix=0;}
        if($EnablePostfix==0){return "";}
        if(!$this->users->AsPostfixAdministrator){return "";}
        $EnablePostfixMultiInstance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance"));





        if($EnablePostfixMultiInstance==1){
            $f[]="<!-- MESSAGING START  -->";
            $f[]="                <li id='left-menu'>";
            $f[]="                    <a href='#' ><i class=\"far fa-envelope\"></i> <span class=\"nav-label\">{InternalRouter}</span> </a>";
            $f[]="                    <ul class='nav nav-second-level'>";

            $f[]="                			<li id='left-menu'>";
            $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.postfix.status.php');\">
															<i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{status}</span> </a>";
            $f[]="							</li>";

            $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.settings.php?onlyMulti=yes",
                "ICO"=>ico_history,"TEXT"=>"{instances}"));

            $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.blackwite.php?instance-id=0",
                "ICO"=>"fas fa-filter","TEXT"=>"{blacklist}/{whitelist}"));

            $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.rbl.php?instance-id=0",
                "ICO"=>"fas fa-badge-check","TEXT"=>"{ip_reputation}"));

            if($this->isFireWall()==1){
                $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.firewall.php",
                    "ICO"=>"fa-free-code-camp","TEXT"=>"{firewall}"));
            }
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.postfix.refused.php",
                "ICO" => ico_eye, "TEXT" => "{refused}"));

            $f[]="					 </ul>";
            $f[]="                </li>";
            $f[]="<!-- MESSAGING END  -->";

            $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
            $results=$q->QUERY_SQL("SELECT ID,instancename FROM postfix_instances ORDER BY instancename");
            foreach ($results as $index=>$ligne){
                $f[]=$this->MessagingSub($ligne["ID"],$ligne["instancename"]);
            }
            return @implode("\n",$f);
        }



        return $this->MessagingSub(0);




    }


    public function MimeDefang(){
        $MimeDefangEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangEnabled"));
        $MimeDefangInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangInstalled"));
        if($MimeDefangInstalled==0){$MimeDefangEnabled=0;}
        if(!$this->users->AsPostfixAdministrator){return "";}
        if($MimeDefangEnabled==0){return "";}

        $f[]="<!-- MIMEDEFANG START  -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fas fa-crosshairs\"></i> <span class=\"nav-label\">{APP_VALVUAD}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";
        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.mimedefang.status.php');\">
															<i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{status}</span> </a>";
        $f[]="							</li>";
        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.mimedefang.spamassassin.php');\">
														<i class=\"fas fa-location-slash\"></i> <span class=\"nav-label\">{Anti-Spam Engine}</span> </a>";
        $f[]="							</li>";




        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.mimedefang.spamassassin.rules.php');\">
														<i class=\"fas fa-location-slash\"></i> <span class=\"nav-label\">{antispam_rules}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.mimedefang.clamav.php');\">
														<i class=\"fab fa-medrt\"></i> <span class=\"nav-label\">{antivirus_rules}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.mimedefang.urls.php');\">
														<i class=\"fab fa-medrt\"></i> <span class=\"nav-label\">{url_filtering}</span> </a>";
        $f[]="							</li>";





        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.mimedefang.backup.php');\">
														<i class=\"fas fa-hdd\"></i> <span class=\"nav-label\">{backupemail_behavior}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.mimedefang.autowhite.php');\">
														<i class=\"fas fa-thumbs-up\"></i> <span class=\"nav-label\">{smtp_AutoWhiteList}</span> </a>";
        $f[]="							</li>";


        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.mimedefang.quarantine.php');\">
														<i class=\"fas fa-archive\"></i> <span class=\"nav-label\">{quarantine}</span> </a>";
        $f[]="							</li>";


        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- MIMEDEFANG END  -->";
        return @implode("\n",$f);
    }


    function PulseReverse():string{
        $EnablePulseReverse=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePulseReverse"));
        if($EnablePulseReverse==0){return "";}
        if((!$this->users->AsWebSecurity)) {return "";}
        $tpl=new template_admin();
        $f[] = "<!-- PULSEREVERSE START  -->";
        $f[] = "                <li id='left-menu'>";
        $f[] = "                    <a href='#' ><i class=\"fas fa-code-branch\"></i> <span class=\"nav-label\">PulseReverse</span> </a>";
        $f[] = "                    <ul class='nav nav-second-level'>";
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.pulsereverse.status.php",
            "ICO"=>"fas fa-tachometer-alt","TEXT"=>"{status}"));

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.pulsereverse.connectors.php",
            "ICO"=>"fas fa-code-branch","TEXT"=>"{frontends}"));

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.pulsereverse.backends.php",
            "ICO"=>ico_server,"TEXT"=>"{backends}"));

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.pulsereverse.websites.php",
            "ICO"=>ico_earth,"TEXT"=>"{websites}"));

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.pulsereverser.requests.php",
            "ICO"=>ico_eye,"TEXT"=>"{requests}"));



        $f[] = "					 </ul>";
        $f[] = "                </li>";
        $f[] = "<!-- PULSEREVERSE END  -->";
        return @implode("\n",$f);

    }


    function HaCluster():string{
        $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
        if($Enablehacluster==0){return "";}
        if((!$this->users->AsProxyMonitor)) {return "";}
        $Iperf3Installed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Iperf3Installed"));
        $tpl=new template_admin();
        $f[] = "<!-- HACLUSTER START  -->";
        $f[] = "                <li id='left-menu'>";
        $f[] = "                    <a href='#' ><i class=\"fas fa-code-branch\"></i> <span class=\"nav-label\">HaCluster</span> </a>";
        $f[] = "                    <ul class='nav nav-second-level'>";

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.hacluster.status.php",
            "ICO"=>"fas fa-tachometer-alt","TEXT"=>"{status}"));


        $f[] = $tpl->LeftMenu(
            array("PAGE" => "fw.hacluster.AD.php",
                "ICO" => "fab fab fa-windows",
                "TEXT" => "{ActiveDirectory}",
                "LEVEL3" => array(
                    "ID" => "fw-left-menus-hacluster-ad",
                    "PAGE" => "fw-left-menus-hacluster-ad.php")
            )
        );

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.hacluster.backends.php",
            "ICO"=>"fas fa-server","TEXT"=>"{backends}"));

        if($Iperf3Installed==1){
            $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.iperf3.php",
                "ICO"=>ico_speed,"TEXT"=>"{speed_test}"));
        }

        if(is_file("/usr/share/artica-postfix/fw.hacluster.events.php")) {
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.hacluster.events.php",
                "ICO" => ico_eye, "TEXT" => "{service_events}"));
        }

        if(is_file("/usr/share/artica-postfix/fw.hacluster.connections.php")) {
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.hacluster.connections.php",
                "ICO" => ico_eye, "TEXT" => "{trace_connexions}"));
        }



        if(is_file("/usr/share/artica-postfix/fw.hacluster.events.backends.php")) {
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.hacluster.events.backends.php",
                "ICO" => ico_eye, "TEXT" => "{backends_events}"));
        }

        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.dns.unbound.queries.php", "ICO" => ico_eye, "TEXT" => "{DNS_QUERIES}"));


        //--------------------------- HACLUSTER - END
        $f[] = "					 </ul>";
        $f[] = "                </li>";
        $f[] = "<!-- HACLUSTER END  -->";
        return @implode("\n",$f);
    }


    function RDPProxy(){
        $EnableRDPProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRDPProxy"));
        //--------------------------- RDP PROXY <i class="fab fa-kickstarter"></i>--------------------------------------------------------------------------
        if($EnableRDPProxy==0){return "";}
        if((!$this->users->AsProxyMonitor)) {return "";}
        $tpl=new template_admin();
        $f[] = "<!-- RDP PROXY START  -->";
        $f[] = "                <li id='left-menu'>";
        $f[] = "                    <a href='#' ><i class=\"fas fa-globe\"></i> <span class=\"nav-label\">{APP_RDPPROXY}</span> </a>";
        $f[] = "                    <ul class='nav nav-second-level'>";

        $f[] = "                			<li id='left-menu'>";
        $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.rdpproxy.status.php');\">
													<i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{status}</span> </a>";
        $f[] = "							</li>";


        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.rdpproxy.connections.php",
            "ICO"=>"fas fa-users","TEXT"=>"{policies}"));


        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.rdpproxy.sessions.php",
            "ICO"=>"fa fa-desktop","TEXT"=>"{sessions}"));

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.rdpproxy.videos.php",
            "ICO"=>"fas fa-film","TEXT"=>"{videos}"));



        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.rdpproxy.events.php",
            "ICO"=>ico_eye,"TEXT"=>"{events}"));


        //--------------------------- RDP PROXY - END
        $f[] = "					 </ul>";
        $f[] = "                </li>";
        $f[] = "<!-- RDP PROXY END  -->";
        return @implode("\n",$f);
    }

    function isProxy(){
        if($this->users->isWebStatisticsAdministratorOnly()){return false;}
        $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
        if(!$this->users->SQUID_INSTALLED){return false;}
        if($SQUIDEnable==0){return false;}
        if(($this->users->AsProxyMonitor) OR ($this->users->AsWebStatisticsAdministrator) OR ($this->users->AsDansGuardianAdministrator)){

            return true;
        }
        return false;

    }

    function Proxy(){

        if (!$this->isProxy()) {return "";}
        $users=new usersMenus();
        $EnableITChart              =   0;
        $SquidWCCPEnabled           = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWCCPEnabled"));
        $SquidInRouterMode          = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidInRouterMode"));
        $EnableStatsCommunicator    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStatsCommunicator"));
        $SquidMikrotikEnabled       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMikrotikEnabled"));

        VERBOSE("SquidMikrotikEnabled = $SquidMikrotikEnabled",__LINE__);


        if ($this->users->CORP_LICENSE) {
            $EnableITChart              = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableITChart"));
            unset($_SESSION["SQUID_DYNAMIC_ACLS"]);
            if ($GLOBALS["VERBOSE"]) {
                echo "dynamic_acls_auth({$_SESSION["uid"]})<br>\n";
            }
        }
        $tpl = new template_admin();
        $f[] = "<!-- PROXY START  -->";
        $f[] = "                <li id='left-menu'>";
        $f[] = "                    <a href='#' ><i class=\"fas fa-globe\"></i> <span class=\"nav-label\">{your_proxy}</span> </a>";


        if( $users->AsProxyMonitor) {
            $f[] = "                    <ul class='nav nav-second-level'>";

        $f[] = "                			<li id='left-menu'>";
        $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxy.status.php');\">
												<i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{status}</span> </a>";
        $f[] = "							</li>";
        $f[] = "					 </ul>";
        }
        $f[] = "                <ul class='nav nav-second-level'>";

        if($users->AsSquidAdministrator) {
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.general.php",
                "ICO" => "fas fa-cogs", "TEXT" => "{global_settings}",));

            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.smp.php",
                "ICO" => "fas fa-microchip", "TEXT" => "{multiple_cpus}",));

            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.filedesc.php",
                "ICO" => "fas fa-file-medical-alt", "TEXT" => "{file_descriptors}",));

            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.dns.php",
                "ICO" => "fas fa-database", "TEXT" => "{dns_settings}",));
            if($SquidMikrotikEnabled==1){
               $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.mikrotik.php", "ICO" => "fas fa-router", "TEXT" => "MikroTik",));
            }

        }

        if($users->AsDansGuardianAdministrator) {
            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.proxy.rules.php",
                    "ICO" => "fa-shield", "TEXT" => "{access_rules}",
                ));
        }

        if($users->AsProxyMonitor) {
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.ports.php", "ICO" => "fa-wrench", "TEXT" => "{listen_ports}",));

            $f[] = "<li id='left-menu'>";
            $f[] = "<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxy.ssl.status.php');\">
		<i class=\"fa fa-square\"></i> <span class=\"nav-label\">{ssl_protocol}</span> </a>";
            $f[] = "</li>";
        }
        $UseNativeKerberosAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseNativeKerberosAuth"));
        $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
        $sHowAuth=true;
        $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
        if($EnableKerbAuth==1 OR $UseNativeKerberosAuth==1
            OR $LockActiveDirectoryToKerberos==1 or $SquidInRouterMode == 1){
            $sHowAuth=false;
        }


        if($users->AsProxyMonitor) {
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.members.php",
                "ICO" => "fas fa-users", "TEXT" => "{authentication}",));

        }

        if($users->AsProxyMonitor) {
            if ($this->HideCorporateFeatures == 0) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.errors.page.php", "ICO" => "fas fa-file-alt", "TEXT" => "{errors_pages}",));
            }
        }
        if($users->AsProxyMonitor) {
            if ($SquidInRouterMode == 0) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.watchdog.php", "ICO" => ico_monitor, "TEXT" => "{monitoring}", "LEVEL3" => array("ID" => "fw-left-menus-proxy-logs", "PAGE" => "fw-left-menus-proxy-logs.php")));
            }
        }


        if($users->AsSquidAdministrator) {
            if ($SquidWCCPEnabled == 1) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.wccp.php", "ICO" => "fas fa-router", "TEXT" => "WCCP",));
            }
        }


        if($users->AsSquidAdministrator) {
            $EnableeCapClamav=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableeCapClamav"));
            if($EnableeCapClamav==1){
                $f[] = $tpl->LeftMenu(array(
                    "PAGE" => "fw.proxy.ecap.av.php",
                    "ICO" => ico_antivirus,
                    "TEXT" => "{antivirus_proxy}",));

            }
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.icap.center.php", "ICO" => "fas fa-plug", "TEXT" => "{icap_center}",));
        }




        if($users->AllowViewStatistics) {
            if ($EnableStatsCommunicator == 0) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.statistics.service.php", "ICO" => "fad fa-chart-pie", "TEXT" => "{statistics_service}",));
            }
        }

        if($users->AsProxyMonitor) {
            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.proxy.global.rules.php",
                    "ICO" => "fa-square",
                    "TEXT" => "{global_rules}",
                    "LEVEL3" => array(
                        "ID" => "fw-left-menus-proxy-global-rules",
                        "PAGE" => "fw-left-menus-proxy-global-rules.php")
                )
            );
        }


        if($users->AsSquidAdministrator) {
            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.proxy.tasks.php", "ICO" => "fa-clock", "TEXT" => "{proxy_tasks}"
                ));
        }

        //--------------------------- PROXY - END
        $f[] = "					 </ul>";
        $f[] = "                </li>";
        $f[] = "<!-- PROXY END  -->";
        if($users->AsSquidAdministrator) {
            $f[] = $this->Proxy_cache();
        }
        $f[] = $this->Proxy_acls();
        if($users->AsSquidAdministrator) {
            $f[] = $this->Proxy_parents();
        }

        return @implode("\n",$f);

    }

    public function C_ICAP_SERVICE(){
        $EnableClamavInCiCap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavInCiCap"));
        $C_ICAP_INSTALLED = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("C_ICAP_INSTALLED"));
        $CicapEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled"));

        if($C_ICAP_INSTALLED==0){return "";}
        if($CicapEnabled==0){return "";}
        $tpl=new template_admin();
        $f[] = "<!-- C-ICAP  -->";
        $f[] = "<li id='left-menu'>";
        $f[] = "	<a href='#' ><i class=\"fa fa-shield\"></i> <span class=\"nav-label\">{ICAP_SERVICE}</span> </a>";
        $f[] = "  <ul class='nav nav-second-level'>";

        $f[]=$tpl->LeftMenu(
            array("PAGE"=>"fw.proxy.c-icap.php",
                "ICO"=>"fas fa-tachometer-alt","TEXT"=>"{service_parameters}",
            ) );

       // $f[]=$tpl->LeftMenu(
         //   array("PAGE"=>"fw.proxy.c-icap.sandbox.php",
           //     "ICO"=>"far fa-box","TEXT"=>"{sandbox_connector}",
            //) );




        if($EnableClamavInCiCap==1) {
            $f[]=$tpl->LeftMenu(
                array("PAGE"=>"fw.clamav.white.php",
                    "ICO"=>"fas fa-thumbs-up","TEXT"=>"{whitelist}",
                ) );


            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.proxy.c-icap.updates.php",
                    "ICO" => ico_download, "TEXT" => "{updates}",
                ));
        }
        $f[]=$tpl->LeftMenu(
            array("PAGE"=>"fw.proxy.c-icap.events.php",
                "ICO"=>ico_eye,"TEXT"=>"{events}",
            ) );

        if($this->users->AsDansGuardianAdministrator) {

            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.proxy.c-icap.detections.php",
                    "ICO" => ico_eye, "TEXT" => "{detections}",
                ));
        }
        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- C-ICAP  -->";
        return @implode("\n",$f);

    }


    private function Proxy_acls(){
        if (!$this->users->AsDansGuardianAdministrator) {return "";}
        $SQUIDACLsEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDACLsEnabled"));
        $SQUIDACLsSimpleEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDACLsSimpleEnabled"));
        if ($SQUIDACLsEnabled == 0) {return "";}

        $f[] = "<!-- PROXY ACLS  -->";
        $f[] = "<li id='left-menu'>";
        $f[] = "	<a href='#' ><i class=\"fa fa-shield\"></i> <span class=\"nav-label\">{WAF_LEFT}</span> </a>";
        $f[] = "  <ul class='nav nav-second-level'>";
        $tpl=new template_admin();

        $f[]=$tpl->LeftMenu(
            array("PAGE"=>"fw.proxy.acls.php",
                "ICO"=>"fa-shield","TEXT"=>"{advanced_access_rules}",
            ) );

        $f[]=$tpl->LeftMenu(
            array("PAGE"=>"fw.http_reply_access.php",
                "ICO"=>"fa-shield","TEXT"=>"{reply_access_rules}",
            ) );

        $f[]=$tpl->LeftMenu(
            array("PAGE"=>"fw.proxy.headers.php",
                "ICO"=>"fa-shield","TEXT"=>"{headers_rules_mini}",
            ) );

        $f[]=$tpl->LeftMenu(
            array("PAGE"=>"fw.proxy.browsers.php",
                "ICO"=>"fab fa-internet-explorer","TEXT"=>"{browsers_rules}",
            ) );

        if ($this->users->AsSquidAdministrator) {
            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.proxy.bandwidth.php",
                    "ICO" => "fas fa-filter", "TEXT" => "{bandwidth_rules_mini}",
                ));
            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.proxy.tcp_outgoing_mark.php",
                    "ICO" => "fa-shield", "TEXT" => "{tcp_outgoing_mark_rules}",
                ));
        }

        $f[]=$tpl->LeftMenu(
            array("PAGE"=>"fw.proxy.objects.list.php",
                "ICO"=>"fas fa-cubes","TEXT"=>"{proxy_objects}",
            ) );

        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- WAF END  -->";

        return @implode("\n",$f);
    }

    public function Hotspot(){
        if (!$this->users->AsHotSpotManager) {return "";}
        $EnableSquidMicroHotSpot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidMicroHotSpot"));
        if($EnableSquidMicroHotSpot==0){ return "";}
        $tpl=new template_admin();

        $f[]="<!-- HOTSPOT START  -->";
        $f[]="<li id='left-menu'>";
        $f[]="<a href='#' ><i class=\"fas fa-hotel\"></i> <span class=\"nav-label\">HotSpot</span> </a>";
        $f[]="<ul class='nav nav-second-level'>";

        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.hotspot.status.php","ICO" => "fas fa-tachometer-alt", "TEXT" => "{status}",));

        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.hotspot.php","ICO" => "fas fa-hotel", "TEXT" => "{parameters}",));

        //hotspot-vouchers
        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.hotspot.vouchers.php","ICO" => "fad fa-ticket", "TEXT" => "{vouchers_rooms}",));

        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.hotspot.forms.php",
            "ICO" => "fa-brands fa-wpforms", "TEXT" => "{custom_form}",));

        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.hotspot.sessions.php","ICO" => "fas fa-address-card", "TEXT" => "{sessions}",));


        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.hotspot.events.php","ICO" => ico_eye, "TEXT" => "{events}",));

        $f[] = "					 </ul>";
        $f[] = "                </li>";
        $f[] = "<!-- HOTSPOT END  -->";

        return @implode("\n",$f);
    }

    private function Proxy_cache(){
        if(!$this->users->CORP_LICENSE) {return "";}
        if (!$this->users->AsProxyMonitor) {return "";}
        $SquidCachesProxyEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCachesProxyEnabled"));
        if ($SquidCachesProxyEnabled == 0) {return "";}
        $f[] = "<!-- PROXY CACHE  -->";
        $f[] = "                <li id='left-menu'>";
        $f[] = "                    <a href='#' ><i class=\"".ico_caching."\"></i> <span class=\"nav-label\">{caching}</span> </a>";
        $f[] = "                    <ul class='nav nav-second-level'>";

        $f[] = "                			<li id='left-menu'>";
        $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxy.cache.status.php');\">
															<i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{status}</span> </a>";
        $f[] = "							</li>";

        $f[] = "                			<li id='left-menu'>";
        $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxy.cache.level.php');\">
												<i class=\"fa fa-signal\"></i> <span class=\"nav-label\">{cache_level}</span> </a>";
        $f[] = "							</li>";


        $f[] = "                			<li id='left-menu'>";
        $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxy.caches.center.php');\">
												<i class=\"".ico_hd."\"></i> <span class=\"nav-label\">{caches_center}</span> </a>";
        $f[] = "							</li>";

        $f[] = "                			<li id='left-menu'>";
        $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxy.caches.rock.php');\">
												<i class=\"".ico_rocket."\"></i> <span class=\"nav-label\">{central_cache}</span> </a>";
        $f[] = "							</li>";


        $f[] = "                			<li id='left-menu'>";
        $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxy.cache.details.php');\">
												<i class=\"fas fa-list-ul\"></i> <span class=\"nav-label\">{cached_items}</span> </a>";
        $f[] = "							</li>";


        $f[] = "                			<li id='left-menu'>";
        $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxy.denycache.php');\">
												<i class=\"fa fa-ban\"></i> <span class=\"nav-label\">{deny_from_cache}</span> </a>";
        $f[] = "							</li>";

        $f[] = "					 </ul>";
        $f[] = "                </li>";
        $f[] = "<!-- PROXY CACHE END  -->";
        return @implode("\n",$f);
    }

    public function WanProxy(){
        $WanProxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyEnabled"));
        $WANPROXY_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WANPROXY_INSTALLED"));
        if($WANPROXY_INSTALLED==0){return "";}
        if($WanProxyEnabled==0){return "";}
        if (!$this->users->AsProxyMonitor) {return "";}
        $f[]="<!-- WANPROXY -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fad fa-compress-alt\"></i> <span class=\"nav-label\">{APP_WANPROXY_MENU}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.wanproxy.status.php');\">
																<i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{status}</span> </a>";
        $f[]="							</li>";
        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.wanproxy.events.php');\">
																<i class=\"".ico_eye."\"></i> <span class=\"nav-label\">{events}</span> </a>";
        $f[]="							</li>";
        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- WANPROXY END  -->";
        return @implode("\n",$f);
    }


    public function ZipProxy(){
        if (!$this->users->AsProxyMonitor) {return "";}
        $EnableProxyCompressor=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyCompressor"));
        $ZIPROXY_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZIPROXY_INSTALLED"));
        if($ZIPROXY_INSTALLED==0){return "";}
        if($EnableProxyCompressor==0){return "";}
        $f[]="<!-- ZIPROXY -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fa fas fa-database\"></i> <span class=\"nav-label\">{APP_ZIPROXY_MENU}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.ziproxy.status.php');\">
																<i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{status}</span> </a>";
        $f[]="							</li>";
        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.ziproxy.events.php');\">
																<i class=\"".ico_eye."\"></i> <span class=\"nav-label\">{events}</span> </a>";
        $f[]="							</li>";
        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- ZIPROXY END  -->";
        return @implode("\n",$f);
    }


    private function KSRN_PRIVS(){
        if($this->users->AsSystemAdministrator){return true;}
        if($this->users->AsFirewallManager){return true;}
        if($this->users->AsDansGuardianAdministrator){return true;}
        if($this->users->AsDansGuardianGroupRule){return true;}
        if($this->users->AsSquidAdministrator){return true;}
        if($this->users->AsProxyMonitor){return true;}
        return false;
    }
    public function KSRN():string{
        $users=new usersMenus();
        if($users->AsDockerWeb) { return "";}
        if(!$this->KSRN_PRIVS()){return "<!-- KSRN_PRIVS FALSE  -->";}
        $tpl=new template_admin();
        $f[]="<!-- KSRN START  -->";
        $GoShieldServerHide=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldServerHide"));
        $f[]="<!-- GoShieldServerHide <$GoShieldServerHide>  -->";
        if($GoShieldServerHide==1){return "";}
        $EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
        $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
        if($Enablehacluster==1){return "";}
        if($EnablePostfix==1){return "";}
        $CORP_LICENSE=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();
        $KSRNEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNEnable"));
        $EnableITChart=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableITChart"));
        $EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
        $EnableCategoriesCache = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCategoriesCache"));
        $SQUIDEnable    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
        $UseRemoteUfdbguardService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseRemoteUfdbguardService"));
        $EnableLocalUfdbCatService= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));

        if(!$CORP_LICENSE){$EnableITChart=0;}


        $f[]="<li id='left-menu'>";
        $f[]="<a href='#' ><i class=\"fas fa-shield-virus\"></i> <span class=\"nav-label\">{filtering_service}</span> </a>";
        $f[]="<ul class='nav nav-second-level'>";

        if($this->users->AsProxyMonitor) {
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.ksrn.filtering.features.php", "ICO" => ico_cd,
                "TEXT" => "{features}"));
        }

        if($SQUIDEnable==1) {
            if($this->users->AsProxyMonitor) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.go.shield.connector.php",
                    "ICO" => "fad fa-exchange-alt",
                    "TEXT" => "Proxy {connector}"));
            }
        }




        if($this->users->AsDansGuardianAdministrator) {
            if ($KSRNEnable == 1) {
                $f[] = $tpl->LeftMenu(
                    array("PAGE" => "fw.ksrn.graphs.php",
                        "ICO" => "fa fa-shield",
                        "TEXT" => "{SRN}",
                        "LEVEL3" => array(
                            "ID" => "fw-left-menus-ksrn",
                            "PAGE" => "fw-left-menus-ksrn.php")
                    )
                );
            }
        }

        if($this->users->AsDansGuardianAdministrator) {
            if($EnableUfdbGuard==1) {
                $UseRemoteUfdbguardService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseRemoteUfdbguardService"));
                if($UseRemoteUfdbguardService==0) {
                    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.ufdb.rules.php", "ICO" => "fa fa-align-justify",
                        "TEXT" => "{filtering_rules}"));
                }
            }

            if($EnableLocalUfdbCatService==0) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.ufdb.categories.php", "ICO" => "fad fa-books",
                    "TEXT" => "{your_categories}"));
            }



            if($this->users->AsProxyMonitor) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.ufdbweb.php", "ICO" => "fad fa-page-break",
                    "TEXT" => "{error_page}"));
            }





        }
        if($SQUIDEnable==1) {
            if($this->users->AsDansGuardianAdministrator) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.urlrewrite.php", "ICO" => "fa fa-shield", "TEXT" => "{web_filter_policies}"));
            }
        }



        if($EnableITChart==1) {
            if($this->users->AsDansGuardianAdministrator) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.itcharter.php",
                    "ICO" => "fa-file-signature", "TEXT" => "{it_charters}",));
            }
        }
        if($this->users->AsProxyMonitor) {
            if ($EnableCategoriesCache == 1) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.categories-cache.php",
                    "ICO" => "fad fa-memory", "TEXT" => "{APP_CATEGORIES_CACHE}",));
            }
        }

          //
//fas fa-cog <i class="fad fa-exchange-alt"></i>
        if($SQUIDEnable==1) {
            if($this->users->AsDansGuardianAdministrator) {
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.whitelist.php", "ICO" => "fa fa-thumbs-up",
                    "TEXT" => "{whitelist}"));

                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.blacklists.php", "ICO" => "fa fa-ban",
                    "TEXT" => "{deny_websites}"));
            }
        }


        if($this->users->AsProxyMonitor) {
            if($EnableUfdbGuard==1) {
                if ($UseRemoteUfdbguardService == 0) {
                    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.ufdb.databases.php", "ICO" => ico_download,
                        "TEXT" => "{categories_update}"));
                }
            }

        }
        if($this->users->AsProxyMonitor) {
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.ksrn.uncategorized.php",
                "ICO" => "fa-solid fa-book-circle-arrow-up",
                "TEXT" => "{uncategorized_websites}"));

        }

        if($this->users->AsProxyMonitor) {
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.ksrn.events.php", "ICO" => ico_eye,
                "TEXT" => "{service_events}"));
        }

        if($EnableUfdbGuard==1) {
            if($UseRemoteUfdbguardService==0) {
                if($this->users->AsProxyMonitor) {
                    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.ufdb.logs.php", "ICO" => ico_eye,
                        "TEXT" => "{events}: {url_filtering}"));
                }
            }
        }




        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- KSRN END  -->";
        return @implode("\n",$f);


    }


    public function Privoxy(){

        if (!$this->users->AsProxyMonitor) {return "";}
        $PrivoxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('PrivoxyEnabled'));
        if($PrivoxyEnabled==0){return "";}
        //--------------------------- PRIVOXY ----------------------------------------------------------------------------
        $f[]="<!-- PRIVOXY START  -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fa fa-sitemap\"></i> <span class=\"nav-label\">{APP_PRIVOXY}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";
        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.privoxy.status.php');\">
														<i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{status}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.privoxy.rules.php');\">
														<i class=\"far fa-exchange\"></i> <span class=\"nav-label\">{forward_rules}</span> </a>";
        $f[]="							</li>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.privoxy.logs.php');\">
													<i class=\"".ico_eye."\"></i> <span class=\"nav-label\">{events}</span> </a>";
        $f[]="							</li>";

        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- PRIVOXY END  -->";
        return @implode("\n",$f);

    }

    private function Proxy_parents():string{
        if (!$this->users->AsProxyMonitor) {
            return "<!-- PROXY PARENTS START AsProxyMonitor=FALSE --> ";
        }
        $EnableParentProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableParentProxy"));
        if($EnableParentProxy==0){
            writelogs("$EnableParentProxy == 0",__FUNCTION__,__FILE__,__LINE__);
            return "<!-- PROXY PARENTS START EnableParentProxy=$EnableParentProxy --> ";
        }
        $eye=ico_eye;
        $f[]="<!-- PROXY PARENTS START  -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fa fa-sitemap\"></i> <span class=\"nav-label\">{proxy_parents}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxy.parents.status.php');\">
													<i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{status}</span> </a>";
        $f[]="							</li>";
        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxy.parents.php');\">
													<i class=\"fa fa-align-justify\"></i> <span class=\"nav-label\">{rules}</span> </a>";
        $f[]="							</li>";
        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxy.parents.events.php');\">
													<i class=\"$eye\"></i> <span class=\"nav-label\">{events}</span> </a>";
        $f[]="							</li>";



        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- PROXY PARENTS END  -->";


        return @implode("\n",$f);
    }

    private function isFilterRights(){
        $this->users=new usersMenus();
        if($this->users->AsDansGuardianAdministrator OR $this->users->AsProxyMonitor OR $this->users->AsDnsAdministrator) {
            return true;
        }
        return false;

    }

    private function isFilter(){
        $EnableDNSFilterd=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSFilterd"));
        $APP_UFDBGUARD_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_UFDBGUARD_INSTALLED"));
        $EnableUfdbGuard=0;

        if(!$this->isFilterRights()){
            $this->LOGS[]="<!-- isFilterRights() return false  -->";
            return false;
        }
        if($APP_UFDBGUARD_INSTALLED==1){
            $EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('EnableUfdbGuard'));
        }else{
            $this->LOGS[]="<!-- APP_UFDBGUARD_INSTALLED <>1  -->";
        }

        $this->LOGS[]="<!-- UFDB: $EnableUfdbGuard -->";
        $this->LOGS[]="<!-- DNSF: $EnableDNSFilterd  -->";
        if($EnableUfdbGuard==1){return true;}
        if($EnableDNSFilterd==1){return true;}
        return false;
    }

    private function Webfilter_title(){
        $EnableDNSFilterd=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSFilterd"));
        $EnableUfdbGuard=0;
        if($this->users->APP_UFDBGUARD_INSTALLED){
            $EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('EnableUfdbGuard'));
        }
        if($EnableUfdbGuard==1){return "{webfiltering2}";}
        if($EnableDNSFilterd==1){return "{dnsfiltering}";}
        return "";
    }


    public function Webfilter(){
        return "";
    }


    private function YourCategoriesEnabled(){
        return true;
    }


    public function APP_HAPROXY_EXCHANGE():string{

        $EnableHaExchange=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHaExchange"));
        //<i class="fa-brands fa-windows"></i>
        if($EnableHaExchange==0){return "";}
        $tpl=new template_admin();
        $f[]="<!-- CATEGORIES SERVICE START  -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fa-brands fa-windows\"></i> <span class=\"nav-label\">{APP_HAPROXY_EXCHANGE}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.haexchange.service.php","ICO"=>"fas fa-tachometer-alt","TEXT"=>"{status}",) );
        $f[]="					 </ul>";
        $f[]="</li>";
        $f[]="<!--  CATEGORIES SERVICE END  -->";

        return @implode("\n",$f);
    }




    public function YourCategories():string{
        $f[]="<!-- CATEGORIES SERVICE START  -->";
        if(!$this->users->AsSquidPersonalCategories){
            $f[]="<!-- AsSquidPersonalCategories -> FALSE  -->";
            return @implode("\n",$f);
        }
        $EnableLocalUfdbCatService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));
        if($EnableLocalUfdbCatService==0){
            $f[]="<!-- EnableLocalUfdbCatService -> FALSE  -->";
            return @implode("\n",$f);

        }
        $tpl=new template_admin();
        $f[]="<!-- CATEGORIES SERVICE START  -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fad fa-books\"></i> <span class=\"nav-label\">{APP_UFDBCAT}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";

        if($this->users->AsDansGuardianAdministrator OR $this->users->AsSquidAdministrator) {
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.dnscat.status.php", "ICO" => "fas fa-tachometer-alt", "TEXT" => "{status}",));
        }

        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.ufdb.categories.php", "ICO" => "fad fa-books",
            "TEXT" => "{your_categories}"));

        if($this->users->AsDansGuardianAdministrator OR $this->users->AsSquidPersonalCategories) {
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.categorize.php", "ICO" => "fas fa-book", "TEXT" => "{categorisation}",));
        }

        if($this->users->AsDansGuardianAdministrator OR $this->users->AsSquidAdministrator) {
            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.dnscat.events.php", "ICO" => ico_eye, "TEXT" => "{events}",));
        }




        $f[]="					 </ul>";
        $f[]="</li>";
        $f[]="<!--  CATEGORIES SERVICE END  -->";

        return @implode("\n",$f);
    }

    public function ufdbcat(){
        return "";
    }



    public function ProxyPac():string{
        if($this->users->isWebStatisticsAdministratorOnly()){return "";}
        if($this->users->isCertifManagerOnly()){return "";}
        if(!$this->users->AsProxyMonitor){return "";}
        $EnableProxyPac=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyPac"));
        if($EnableProxyPac==0){return "";}
        $tpl=new template_admin();

        $f[]="<!-- PROXY PAC START  -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fad fa-scroll-old\"></i> <span class=\"nav-label\">{wpad_service}</span> </a>";
        $f[]="	                  <ul class='nav nav-second-level'>";

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxypac.status.php');\">
													<i class=\"fas fa-tachometer-alt\"></i> <span class=\"nav-label\">{status}</span> </a>";
        $f[]="							</li>";


        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxypac.rules.php');\">
													<i class=\"fa fa-align-justify\"></i> <span class=\"nav-label\">{wpad_rules}</span> </a>";
        $f[]="							</li>";

        $f[]=$tpl->LeftMenu(
            array("PAGE"=>"fw.proxy.objects.list.php?ProxyPac=1",
                "ICO"=>"fas fa-cubes","TEXT"=>"{objects}",
            ) );

        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxypac.events.php');\">
                                                        <i class=\"".ico_eye."\"></i> <span class=\"nav-label\">{events}</span> </a>";
        $f[]="							</li>";


        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxypac.requests.php');\">
                                                        <i class=\"".ico_eye."\"></i> <span class=\"nav-label\">{realtime_requests}</span> </a>";
        $f[]="							</li>";



/*
        $f[]="                			<li id='left-menu'>";
        $f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxypac.stats.php');\">
                                                        <i class=\"fas fa-chart-area\"></i> <span class=\"nav-label\">{statistics}</span> </a>";
        $f[]="							</li>";
*/

        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- PROXY PAC END  -->";

        return @implode("\n",$f);

    }

    public function nginx():string{
        $tpl=new template_admin();
        $EnableWordpressManagement=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWordpressManagement"));
        $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
        if(is_file("/etc/artica-postfix/AS_DOCKER_SERVICE")) { $EnableNginx =1;}

        if($EnableNginx==0){return $this->hamrp();}
        $f[]=$this->hamrp();
        $FRONTAIL_LINUX_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FRONTAIL_LINUX_INSTALLED"));
        if(!$this->users->AsWebMaster){return "";}
        $f[]="<!-- NGINX START  -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fas fa-globe-africa\"></i> <span class=\"nav-label\">{web_services}</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.nginx.status.php","ICO"=>"fas fa-tachometer-alt","TEXT"=>"{status}",) );
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.nginx.sites.php","ICO"=>"fas fa-list-alt","TEXT"=>"{services}",) );

        //$f[]=$tpl->LeftMenu(array("PAGE"=>"fw.nginx.services.php","ICO"=>"fas fa-list-alt","TEXT"=>"{services} 2",) );

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.nginx.sla.php","ICO"=>ico_performance,"TEXT"=>"SLA",) );

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.certificates-center.php","ICO"=>ico_certificate,"TEXT"=>"{certificates_center}",) );

        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.nginx.clients.certificates.php","ICO"=>"fas fa-file-certificate","TEXT"=>"{client_certificates}",) );

        $PHPReverseEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PHPReverseEnabled"));

        if($PHPReverseEnabled==1){
            $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.nginx.phpreverse.php","ICO"=>"fa-brands fa-php","TEXT"=>"{APP_PHP_REVERSE}",) );
        }

        if($EnableWordpressManagement==1){
            $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.wordpress.php","ICO"=>"fab fa-wordpress","TEXT"=>"{wordpress_websites}",) );
        }

        $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
        $f[]="<!-- DisablePostGres $DisablePostGres  -->";
        if($DisablePostGres==0){
            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.nginx.statistics.status.php",
                    "ICO" => "fas fa-chart-area",
                    "TEXT" => "{statistics}",
                    "LEVEL3" => array(
                        "ID" => "fw-left-menus-stats-nginx",
                        "PAGE" => "fw-left-menus-nginx-statistics.php")
                )
            );

        }


        //$f[]=$tpl->LeftMenu(array("PAGE"=>"fw.nginx.webcopy.php","ICO"=>"fad fa-copy","TEXT"=>"WebCopy",) );
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.nginx.requests.php","ICO"=>ico_eye,"TEXT"=>"{requests}",) );
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.nginx.events.php","ICO"=>ico_eye,"TEXT"=>"{service_events}",));
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.nginx.rest.events.php","ICO"=>ico_eye,"TEXT"=>"{APP_REVERSE_PROXY}",));

        if($FRONTAIL_LINUX_INSTALLED==1) {
            $f[] = "		<li id='left-menu'>";
            $f[] = "      	<a href='#' OnClick=\"s_PopUpFull('/nginx-access/','1024','900');\">
									<i class=\"".ico_eye."\"></i> <span class=\"nav-label\">{events}</span> </a>";
            $f[] = "		</li>";
        }


        //--------------------------- NGINX - END
        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- NGINX END  -->";
        return @implode("\n",$f);
    }

    public function WebFirewall(){
        $EnableNginxFW=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginxFW"));
        $EnableModSecurityIngix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableModSecurityIngix"));

        if(($EnableNginxFW==0) && ($EnableModSecurityIngix==0)){
            return null;
        }
        if(!$this->users->AsWebSecurity){return "";}


        $tpl=new template_admin();
        $f[]="<!-- WEB Firewall START -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fab fa-free-code-camp\"></i> <span class=\"nav-label\">Web Firewall</span> </a>";
        $f[]="                    <ul class='nav nav-second-level'>";

        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.nginx.fw.status.php",
            "ICO" => "fas fa-tachometer-alt", "TEXT" => "{status}",));




        if($EnableModSecurityIngix==1){

            $f[]=$tpl->LeftMenu(
                array("PAGE"=>"fw.modsecurity.rules.php",
                    "ICO"=>"fa fa-bars","TEXT"=>"{rules}",
                ) );


          //  $f[]=$tpl->LeftMenu(
            //    array("PAGE"=>"fw.modsecurity.atomic.php",
              //      "ICO"=>"fal fa-atom","TEXT"=>"Atomic CORP",
                //) );


            $f[]=$tpl->LeftMenu(
                array("PAGE"=>"fw.modsecurity.white.php",
                    "ICO"=>"fas fa-thumbs-up","TEXT"=>"{whitelists}",
                ) );

            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.modsecurity.threats.php",
                "ICO" => ico_audit, "TEXT" => "Audit",
            ) );
        }

        if($EnableNginxFW==1) {

            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.wordpress.firewall.php",
                "ICO" => "fa-solid fa-block-brick-fire", "TEXT" => "{outgoing_wbl}",));

            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.wordpress.firewall.inbound.php",
                "ICO" => "fa-solid fa-block-brick-fire", "TEXT" => "{incoming_firewall}",));


            if($this->isFireWall()==0){
                $f[] = $tpl->LeftMenu(array("PAGE" => "fw.ipfeeds.php",
                    "ICO" => "fas fa-hockey-mask", "TEXT" => "{CybercrimeIPFeeds}",));

            }

            $f[] = $tpl->LeftMenu(array("PAGE" => "fw.wordpress.firewall.events.php",
                "ICO" => ico_eye, "TEXT" => "{firewall}",));
        }

        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- WEB-Firewall stop -->";

        return @implode("\n",$f);


    }




    private function sub_statistics(){
        $EnableSquidAnalyzer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidAnalyzer"));
        $ArrayLeftStats=array();
        if(!$this->users->AllowViewStatistics) {return "";}
        $EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
        $AsElasticClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AsElasticClient"));
        $EnableStatsCommunicator=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStatsCommunicator"));


        if($EnableStatsCommunicator==1){
            $tpl=new template_admin();

            $ArrayLeftStats[] = "<li id='left-menu'>";
            $ArrayLeftStats[] = "<a href='#' OnClick=\"MenuRoot( $(this),'fw.statscom.php');\">";
            $ArrayLeftStats[] = "<i class=\"fas fa-cogs\"></i> <span class=\"nav-label\">{APP_STATS_COMMUNICATOR}</span> </a>";
            $ArrayLeftStats[] = "</li>";

            $ArrayLeftStats[] = $tpl->LeftMenu(
                array("PAGE" => "fw.statscom.browse.php",
                    "ICO" => "fas fa-chart-area",
                    "TEXT" => "{APP_SQUID}",
                    "LEVEL3" => array(
                        "ID" => "fw-left-menus-stats-proxies",
                        "PAGE" => "fw-left-menus-statscom-proxies.php")
                )
            );

            $ArrayLeftStats[] = $tpl->LeftMenu(
                array("PAGE" => "fw.statscom.dnfw.browse.php",
                    "ICO" => "fas fa-chart-area",
                    "TEXT" => "{APP_DNSDIST}",
                    "LEVEL3" => array(
                        "ID" => "fw-left-menus-stats-dnsdist",
                        "PAGE" => "fw-left-menus-statscom-dnsdist.php")
                )
            );




        }


        if($AsElasticClient==1){
            $ArrayLeftStats[] = "<li id='left-menu'>";
            $ArrayLeftStats[] = "<a href='#' OnClick=\"MenuRoot( $(this),'fw.elastic.client.php');\">";
            $ArrayLeftStats[] = "<i class=\"fas fa-cogs\"></i> <span class=\"nav-label\">{APP_ELASTICSEARCH_CLIENT}</span> </a>";
            $ArrayLeftStats[] = "</li>";

        }


        if ($EnablePostfix == 1) {
            $ArrayLeftStats[] = "<li id='left-menu'>";
            $ArrayLeftStats[] = "<a href='#' OnClick=\"MenuRoot( $(this),'fw.postfix.statistics.members.php');\">";
            $ArrayLeftStats[] = "<i class=\"fas fa-chart-pie\"></i> <span class=\"nav-label\">SMTP:{members}</span> </a>";
            $ArrayLeftStats[] = "</li>";
        }



        if(count($ArrayLeftStats)==0){return "";}
        return @implode("\n",$ArrayLeftStats);

    }


    public function statitics(){
        $data=$this->sub_statistics();
        if($data==null){return "";}
        $f[] = "<!-- PROXY STATISTICS START  -->";
        $f[] = "                <li id='left-menu'>";
        $f[] = "                    <a href='#' ><i class=\"fas fa-chart-area\"></i> <span class=\"nav-label\">{statistics}</span> </a>";
        $f[] = "	                  <ul class='nav nav-second-level'>";
        $f[]=$data;

        $f[] = "					 </ul>";
        $f[] = "                </li>";
        $f[] = "<!-- PROXY STATISTICS END  -->";
        return @implode("\n",$f);

    }

    public function Databases(){
        if(!$this->users->IsDBAdmin()){return "";}
        $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
        $EnableClamavDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavDaemon"));
        $c=0;
        $f[]="<!-- DATABASES START  -->";
        $f[]="                <li id='left-menu'>";
        $f[]="                    <a href='#' ><i class=\"fas fa-database\"></i> <span class=\"nav-label\">{databases}</span> </a>";
        $f[]="	                  <ul class='nav nav-second-level'>";

        if($DisablePostGres==0) {
                $c++;
                $f[] = "	<li id='left-menu'>";
                $f[] = "  	<a href='#' OnClick=\"MenuRoot( $(this),'fw.postgresql.status.php');\"><i class=\"fas fa-database\"></i> <span class=\"nav-label\">PostGreSQL</span> </a>";
                $f[] = "	</li>";
        }

        $APP_MANTICORE_INSTALLED    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MANTICORE_INSTALLED"));
        $MantiCoreSearchEnabled     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MantiCoreSearchEnabled"));
        $ELASTICSEARCH_INSTALLED    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ELASTICSEARCH_INSTALLED"));
        $EnableElasticSearch        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableElasticSearch"));
        $EnableGeoipUpdate          = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"));
        $EnableUfdbGuard            = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
        $UseRemoteUfdbguardService= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseRemoteUfdbguardService"));
        $tpl=new template_admin();

        if($APP_MANTICORE_INSTALLED==0){$MantiCoreSearchEnabled=0;}

        if ($ELASTICSEARCH_INSTALLED == 1) {
            if ($EnableElasticSearch == 1) {
                    $c++;
                    $f[] = $tpl->LeftMenu(
                        array("PAGE" => "fw.elasticsearch.php",
                            "ICO" => "fas fa-database", "TEXT" => "{APP_ELASTICSEARCH}"));

            }
        }
        $EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
        $openldap_installed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("openldap_installed"));
        if($openldap_installed==0){
            $EnableOpenLDAP=0;
        }

        if ($EnableOpenLDAP == 1) {
                $c++;
                $f[] = $tpl->LeftMenu(
                    array("PAGE" => "fw.openldap.php",
                        "ICO" => "fas fa-database",
                        "TEXT" => "{APP_OPENLDAP}",
                        "LEVEL3" => array(
                            "ID" => "fw-left-menus-openldap",
                            "PAGE" => "fw-left-menus-openldap.php")
                    )
                );
        }


        $EnableMySQL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMySQL"));
        if ($EnableMySQL == 1) {
                $c++;
                $f[] = $tpl->LeftMenu(
                    array("PAGE" => "fw.mysql.php",
                        "ICO" => "fas fa-database", "TEXT" => "{mysql_server}"));
        }

        $EnableLokiDB=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLokiDB"));

        if($EnableLokiDB==1){
            $c++;
            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.loki.php",
                    "ICO" => "fas fa-database",
                    "TEXT" => "{APP_LOKI}",
                    "LEVEL3" => array(
                        "ID" => "fw-left-menus-loki",
                        "PAGE" => "fw-left-menus-loki.php")
                )
            );
        }

        if($MantiCoreSearchEnabled==1){
            $c++;
            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.manticore.php",
                    "ICO" => "fas fa-database", "TEXT" => "{APP_MANTICORE}"));
        }

        $EnableRedisService = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedisService"));
            if ($EnableRedisService == 1) {
                $c++;
                $f[] = $tpl->LeftMenu(
                    array("PAGE" => "fw.redis.php",
                        "ICO" => "fas fa-database", "TEXT" => "{APP_REDIS_SERVER}"));
            }


            if ($EnableGeoipUpdate == 1) {
                $c++;
                $f[] = $tpl->LeftMenu(
                    array("PAGE" => "fw.geoip.php",
                        "ICO" => "fas fa-database", "TEXT" => "{APP_GEOIP_TITLE}"));

            }

        if($this->users->AsDansGuardianAdministrator) {
            if($EnableUfdbGuard==1) {
                if ($UseRemoteUfdbguardService == 0) {
                    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.ufdb.databases.php", "ICO" => "fas fa-database",
                        "TEXT" => "{categories_databases}"));
                }
            }

        }



            if ($EnableClamavDaemon ==1){
                $c++;
                $f[] = $tpl->LeftMenu(
                    array("PAGE" => "fw.clamav.patterns.php",
                        "ICO" => "fab fa-medrt", "TEXT" => "{clamav_antivirus_databases}"));
            }



            $c++;
            $f[] = $tpl->LeftMenu(
                array("PAGE" => "fw.databases.errors.php",
                    "ICO" => ico_eye, "TEXT" => "{sql_errors}"));




        $f[]="					 </ul>";
        $f[]="                </li>";
        $f[]="<!-- DATABASES END  -->";


        if($c==0){return "";}

        return @implode("\n",$f);


    }


}
