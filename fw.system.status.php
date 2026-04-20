<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.cpu.percent.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/prefetch-info.inc"); __prefetchCommonSettings($GLOBALS["CLASS_SOCKETS"]);
$GLOBALS["PEITYCONF"]="{ width:255,fill: [\"#eeeeee\"],stroke:\"#18a689\",strokeWidth: 2 }";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}
clean_xss_deep();
if(isset($_GET["system"])){app_status();exit;}
if(isset($_GET["system-start"])){system_start();exit;}
if(isset($_GET["didyouknow-sshportal"])){didyouknow_sshportal();}
if(isset($_GET["doughnut-ps-mem"])){doughnut_ps_mem();exit;}
if(isset($_GET["frontend-notifications"])){top_notifications();}
if(isset($_GET["sysmemory"])){sysmemory();exit;}
if(isset($_GET["syscpu"])){die();}
if(isset($_GET["sysload"])){die();}
if(isset($_GET["sysdisk"])){sysdisk();exit;}
if(isset($_GET["bandwidth"])){bandwidth();exit;}
if(isset($_GET["docker-instances"])){docker_instances();exit;}
if(isset($_GET["top-widget"])){top_widgets();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["top-cpu"])){echo top_cpus();exit;}
die();
function didyouknow_sshportal(){
    header("content-type: application/x-javascript");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("didyouknow_sshportal",1);
    echo "$('#didyouknow_sshportal').remove();";
}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    $EnableRedisServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedisService"));
    $EnableStatsCommunicator=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStatsCommunicator"));
    $SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
    $EnablePostfix=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix");
    $EnableDNSFilterd=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSFilterd"));
    $EnableElasticSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableElasticSearch"));
    $DHCPDInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDInstalled"));
    $EnableDHCPServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer"));
    $EnablenDPI=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablenDPI"));
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    $suffix_gen=null;
    $array=array();
    if($DHCPDInstalled==0){$EnableDHCPServer=0;}

    if(count($users->SIMPLE_ACLS)>0){
        $array["{access_rules}"] = "fw.proxy.rules.php?start=yes";
    }

    if(is_callable(array($users, 'isCertifManagerOnly'))) {
        if ($users->isCertifManagerOnly()) {
            $array["{certificates_center}"] = "fw.certificates-center.php?dashboard=yes";
        }
    }

    if(!$users->AsWebMaster) {
        if (count($users->NGINX_SERVICES) > 0) {
            $EnableNginx = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx");
            if ($EnableNginx == 1) {
                $array["{myWebServices}"] = "fw.nginx.sites.php?table-form=yes&MiniAdm=yes";
            }
        }
    }


    if($users->AsAnAdministratorGeneric) {
        $array["{your_system}"] = "$page?system-start=yes";
    }
    $EnableConntrackd=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableConntrackd"));
    if($EnableConntrackd==1){
        $array["{connections}"] = "fw.conntrackd.php";
    }





    if($EnableStatsCommunicator==1){
        $suffix_gen=" ({your_server})";
    }

    if($users->AsWebStatisticsAdministrator){
        if($EnablenDPI==1){
            $array["{bandwidth}{$suffix_gen}"] = "fw.dashboard.bandwidth.php";
        }
        if($EnableStatsCommunicator==1){
            //$array["{bandwidth} ({your_proxy})"] = "fw.dashboard.bandwidth.proxy.php";
            if($users->isWebStatisticsAdministratorOnly()){
                $array["{STATS_BROWSING}"] = "fw.statscom.browse.php";
                $array["{data}"] = "fw.statscom.data.php";
            }
        }
    }

    if($users->AsAnAdministratorGeneric){
        if($users->AsProxyMonitor) {
            if($HaClusterClient==1){
                $array["HaCluster"] = "fw.dashboard.HaCluster.php";
            }
        }
        if($EnableElasticSearch==1){
            $array["{APP_ELASTICSEARCH}"] = "fw.dashboard.elasticsearch.php";
        }
        if($EnableDHCPServer==1){
            $array["{APP_DHCP}"] = "fw.dashboard.dhcpd.php";
        }
    }

    if($users->AsDnsAdministrator) {
        if ($EnableDNSFilterd == 1) {
            $array["{APP_DNSFILTERD}"] = "fw.dashboard.dnsfilterd.php";
        }
    }

    if($users->AsProxyMonitor) {
        if ($SQUIDEnable == 1) {
            $array["{your_proxy}"] = "fw.dashboard.YourProxy.php";
        }
    }
    if($users->AsPostfixAdministrator) {
        if ($EnablePostfix == 1) {
            $array["{messaging}"] = "fw.dashboard.postfix.php";

        }
    }




if(isset($_SESSION["MANAGE_CATEGORIES"])){
    $EnablePersonalCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('EnablePersonalCategories'));
    $EnableLocalUfdbCatService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        VERBOSE("LICENSE -> FALSE EnablePersonalCategories=0", __LINE__);
        $EnablePersonalCategories=0;
    }
    if($EnablePersonalCategories==1  OR $EnableLocalUfdbCatService==1) {
        if (count($_SESSION["MANAGE_CATEGORIES"]) > 0) {
            $array["{your_categories}"] = "fw.ufdb.categories.php";
        }
    }
}


    $EnableRDPProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRDPProxy"));
    if($EnableRDPProxy==1) {
        $RDPProxyVersion = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION");
        if (!preg_match("#^(9|10|11|12)\.#", $RDPProxyVersion)) {
            $RDPProxyVersion = $tpl->add_decimal($RDPProxyVersion);
            $RDPProxyVersionBin = intval(str_replace(".", "", $RDPProxyVersion));
            if ($RDPProxyVersionBin < 8111) {
                $warn_upgrade_rdpproxy_8111 = $tpl->_ENGINE_parse_body("{warn_upgrade_rdpproxy_8111}");
                $warn_upgrade_rdpproxy_8111 = str_replace("%s", $RDPProxyVersion, $warn_upgrade_rdpproxy_8111);
                echo $tpl->div_error("<strong>$warn_upgrade_rdpproxy_8111</strong>");
            }
        }
    }
    if(count($array)==0){
        if($users->AllowAddUsers OR $users->AllowAddGroup ){
            $array["{members}"]="fw.members.ldap.php";
        }
    }

   echo "".$tpl->tabs_default($array)."";

}
function system_start(){
    $page                   = CurrentPageName();
    $tpl                    = new template_admin();
    $EnableBandwithCalculation=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableBandwithCalculation"));
    $ytblink                = "https://www.youtube.com/articaproject";
    $ERROR_FATAL            = null;
    $bandwidth_js           = null;
    $ERROR_FATALS           = array();


    if(count($ERROR_FATALS)>0) {
        $ERROR_FATAL = "<div class='alert alert-danger' style='margin-bottom:-20px'>" . @implode("", $ERROR_FATALS) . "</div>";
    }

    if($EnableBandwithCalculation==1){

        $bandwidth_js="LoadAjaxTiny('bandwidth-dashboard','fw.system.status.php?bandwidth=yes');";
    }

    // Pre-render widgets inline to avoid race condition with Loadjs
    $memory = sysmemory();
    list($widget_cpu, $Status, $metrics) = widget_cpu();
    $widget_load = widget_load($Status, $metrics);
    $widget_sysdisk = widget_sysdisk();

    $w_memory = (strlen($memory) > 10) ? $tpl->_ENGINE_parse_body($memory) : "";
    $w_cpu    = (strlen($widget_cpu) > 10) ? $tpl->_ENGINE_parse_body($widget_cpu) : "";
    $w_load   = (strlen($widget_load) > 10) ? $tpl->_ENGINE_parse_body($widget_load) : "";
    $w_disk   = (strlen($widget_sysdisk) > 10) ? $tpl->_ENGINE_parse_body($widget_sysdisk) : "";

    $html[]="<div style='margin-top:10px'>$ERROR_FATAL</div>";
    $html[]="<div class='container-fluid'>
  <div class='row' style='vertical-align:top;padding:5px'>
    <div class='col-sm-3'>
    <div id='sysmemory'>$w_memory</div>
    </div>
    <div class='col-sm-3'>
    <div id='syscpu'>$w_cpu</div>
    </div>
    <div class='col-sm-3'>
    <div id='sysload'>$w_load</div>
    </div>
        <div class='col-sm-2'>
    <div id='sysdisk'>$w_disk</div>
    </div>
            <div class='col-sm-2'>
    <div id='bandwidth-dashboard'></div>
    </div>
  </div>
</div>";
    $html[]="<div class=\"container-fluid\">";
    $html[]="<div class=\"row\">";
    $html[]="                    <div class=\"col-lg-6\" id='frontend-notifications'>";
    $html[]="                    </div>";
    $html[]="";
    $html[]="                    <div class=\"col-lg-6\">";
    $html[]="";
    $html[]="                        <div class=\"row\">";


   $licInfo                     = $tpl->ClickMouse("pop:https://wiki.articatech.com/license/overview");
   $ytb                         = $tpl->ClickMouse("pop:$ytblink");
   $Docsjs                      = $tpl->ClickMouse("pop:http://articatech.net/documentation.php");
   $VersionsUnbound             = $tpl->ClickMouse("Loadjs('fw.system.upgrade-software.php?product=APP_UNBOUND');");
   $Whatsnewjs                  = $tpl->ClickMouse("Loadjs('fw.whatsnew.php');");
   $NetDATAEnabled              = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetDATAEnabled"));

    $infos="<i class=\"text-success fas fa-info-circle\"></i>";
    $youtube="<i class=\"text-danger fab fa-youtube\"></i>";
    $pdf="<i class=\"text-danger fas fa-file-pdf\"></i>";
    $alert="<i class=\"text-danger fas fa-exclamation-triangle\"></i>";
    $download_ico="<i class=\"text-warning fas fa-download\"></i>";
    $AS_DOCKER_SERVICE=false;
    if(is_file("/etc/artica-postfix/AS_DOCKER_SERVICE")){$AS_DOCKER_SERVICE=true;}
    $html[]="<div class=\"col\">";
    $html[]="<div class=\"ibox float-e-margins\">";
    $html[]="<div class=\"ibox-title\">";
    $html[]="\t<h5>";
    if(!$AS_DOCKER_SERVICE) {
        $html[] = "\t{memory} &nbsp;-&nbsp; {top_processes} {used}:&nbsp;<span id='doughnut-ps-mem-title'></span>";
    }else{
        $html[] = "\t{instances}";
    }
    $html[]="\t</h5>";
    $html[]="</div>";
    $html[]="\t    <div class=\"row text-center\" id='ff-status-doughnut'>";
    if(!$AS_DOCKER_SERVICE) {
        $html[] = "\t        <div class=\"col-md-6\" id='dashboard-ps-mem'>";
        $html[] = "\t            <canvas id=\"doughnut-ps-mem\" width=250px height=250px style=\"margin: 0 auto 0\"></canvas>";
        $html[] = "\t        </div>";
        $html[] = "\t        <div class=\"col-md-6\">";
        $html[] = "\t            <div id='dashboard-top-processes' style='text-align:left;padding-left:45px'></div>";
        $html[] = "\t        </div>";
    }
    $html[]="\t    </div>";
    $html[]="";
    $html[]="\t</div>";
    $html[]="";
    $html[]="<script>";

    if($AS_DOCKER_SERVICE) {$html[]="\tLoadAjax('ff-status-doughnut','$page?docker-instances=yes');";}
    $Ordonnancer=$tpl->RefreshInterval_Loadjs("sysmemory","fw.system.status.php","top-widget=yes",30);
    $html[]="\t$bandwidth_js;";
    $html[]="\t$Ordonnancer;";
    $html[]="</script>";

   // $html[]="                            <div class=\"col-lg-6\">";
    $html[]="                                <div class=\"ibox float-e-margins\">";
    $html[]="                                    <div class=\"ibox-title\">";
    $html[]="                                        <h5>{tips}...</h5>";
    $html[]="                                    </div>";
    $html[]="                                    <div class=\"ibox-content\">";
    $html[]="                                        <ul class=\"todo-list m-t small-list\">";

    $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
    if ($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        if(isset($LicenseInfos["assigned_to_company"])) {
            if ($LicenseInfos["assigned_to_company"] == 1807) {
                $html[] = Tips_paragraph("<i class=\"text-success fa-duotone fa-file-certificate\"></i>", $licInfo, "{WELCOME_ARTICA_EVAL}");
            }
        }
    }

    if(preg_match("#START_WHATSNEW(.*?)STOP_WHATSNEW#s",@file_get_contents("WHATSNEW"),$re)){
        $c      = 0;
        $table  = explode("\n",$re[1]);
        foreach ($table as $line){
            $line=trim($line);
            if($line==null){continue;}
            $c++;
        }

        if($c>0){
            $VERSION=trim(@file_get_contents("VERSION"));
            $SP=null;
            $CURPATCH= $GLOBALS["CLASS_SOCKETS"]->SPVersion();
            if($CURPATCH>0){
                $VERSION="$VERSION&nbsp;Service Pack $CURPATCH";
            }


            $whatsnew_text=$tpl->_ENGINE_parse_body("{whatsnew_text}");
            $whatsnew_text=str_replace("%num","<strong>$c</strong>",$whatsnew_text);
            $whatsnew_text=str_replace("%nom","<strong>$c</strong>",$whatsnew_text);
            $whatsnew_text=str_replace("%ver","<strong>$VERSION</strong>",$whatsnew_text);
            $html[]=Tips_paragraph($infos,$Whatsnewjs,$whatsnew_text);
        }
    }



    $html[]=Tips_paragraph($youtube,$ytb,"{visit_youtube}");
    $HTTP_X_ARTICA_SUBFOLDER="/";
    if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
        $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/";
    }



    $MEMCACHED_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MEMCACHED_VERSION");
    VERBOSE("MEMCACHED_VERSION=$MEMCACHED_VERSION",__LINE__);
    $zLATEST=$tpl->GetCloudLastversion("APP_MEMCACHED",$MEMCACHED_VERSION);
    $zFinal=intval($zLATEST[0]);
    if($zFinal>0){
        $NewVer=trim($zLATEST[1]);
        if($NewVer<>null) {
            $NEW_VERSION_TEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
            $NEW_VERSION_TEXT = str_replace("%product", "{APP_MEMCACHED}", $NEW_VERSION_TEXT);
            $NEW_VERSION_TEXT = str_replace("%ver", $MEMCACHED_VERSION, $NEW_VERSION_TEXT);
            $NEW_VERSION_TEXT = str_replace("%next", $NewVer, $NEW_VERSION_TEXT);
            $uri = $tpl->ClickMouse("Loadjs('{$HTTP_X_ARTICA_SUBFOLDER}fw.system.upgrade-software.php?product=APP_MEMCACHED')");
            $html[] = Tips_paragraph($download_ico, $uri, "<strong>$NEW_VERSION_TEXT</strong><br>{click_to_upgrade_explain}");
        }
    }

    if($NetDATAEnabled==1){
        $APP_NETDATA_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NETDATA_VERSION");
        VERBOSE("APP_NETDATA_VERSION=$APP_NETDATA_VERSION",__LINE__);
        $zLATEST=$tpl->GetCloudLastversion("APP_NETDATA",$APP_NETDATA_VERSION);
        $zFinal=intval($zLATEST[0]);
        if($zFinal>0){
            $NewVer=trim($zLATEST[1]);
            if($NewVer<>null) {
                $NEW_VERSION_TEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
                $NEW_VERSION_TEXT = str_replace("%product", "{APP_NETDATA}", $NEW_VERSION_TEXT);
                $NEW_VERSION_TEXT = str_replace("%ver", $APP_NETDATA_VERSION, $NEW_VERSION_TEXT);
                $NEW_VERSION_TEXT = str_replace("%next", $NewVer, $NEW_VERSION_TEXT);
                $uri = $tpl->ClickMouse("Loadjs('{$HTTP_X_ARTICA_SUBFOLDER}fw.system.upgrade-software.php?product=APP_NETDATA')");
                $html[] = Tips_paragraph($download_ico, $uri, "<strong>$NEW_VERSION_TEXT</strong><br>{click_to_upgrade_explain}");
            }
        }

    }

    $UnboundV=$tpl->UnBoundVersionArray();
    if(!isset($UnboundV["MAJOR"])){
        $UnboundV["MAJOR"]=0;
    }
    if(!isset($UnboundV["MINOR"])){
        $UnboundV["MINOR"]=0;
    }
    if(!isset($UnboundV["REVISION"])){$UnboundV["REVISION"]=0;}

    $UnboundVStatus=False;
    if($UnboundV["MAJOR"]>0){
        if($UnboundV["MINOR"]>8){
            $UnboundVStatus=True;
        }
    }
    if(!$UnboundVStatus){
        $unbound_wrong_version_text=$tpl->_ENGINE_parse_body("{unbound_wrong_version_text}");
        $unbound_wrong_version_text=str_replace("%ver",$UnboundV["MAJOR"].".".$UnboundV["MINOR"].
            ".".$UnboundV["REVISION"],$unbound_wrong_version_text);
        $html[]=Tips_paragraph($alert,$VersionsUnbound,$unbound_wrong_version_text);
    }



        $didyouknow_sshportal=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("didyouknow_sshportal"));
        $Click=$tpl->ClickMouse("s_PopUpFull('https://youtu.be/_9vJEg197RA',1024,768,'Reverse SSH Proxy')");
        $Click2=$tpl->ClickMouse("Loadjs('$page?didyouknow-sshportal=yes')");
        if($didyouknow_sshportal==0) {
            $html[]=Tips_paragraph($youtube,$Click,"{didyouknow_sshportal}",$Click2,"didyouknow_sshportal");
        }

    $html[]="                                        </ul>";
    $html[]="                                    </div>";
    $html[]="                                </div>";
    $html[]="                            </div>";
    $html[]="                        </div>";
    $html[]="                        <div class=\"row\">";
    $html[]="                            <div class=\"col-lg-12\"></div>";
    $html[]="                                            <div class=\"col-lg-6\">";
    $html[]="                                    </div>";
    $html[]="                                    </div>";
    $html[]="                                </div>";
    $html[]="                            </div>";
    $html[]="                        </div>";
    $html[]="";
    $html[]="                    </div>";
    $html[]="";
    $html[]="";
    $html[]="                </div>";
    $html[]="</div>";
    $html[]="</div>";
    $html[]="<script>";
    $html[]="Loadjs('{$HTTP_X_ARTICA_SUBFOLDER}fw.system.status.php?top-widget=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);

}

function Tips_paragraph($ico,$js,$text,$hidejs=null,$id=null):string{
    $icon_hide=null;
    if($hidejs<>null){
        $icon_hide="<small class='label label-warning' $hidejs>{hide}</small>&nbsp;&nbsp;";
    }

    $f[]="<li id='$id' style='margin-top: 10px'>";
    $f[]="<table style='width:100%'>";
    $f[]="<tr>";
    $f[]="<td style='width:1%;vertical-align:top;padding:0' $js nowrap>
    <div style='font-size:28px;padding-left:3px;padding-top:0;margin-top:-5px;vertical-align: top;'>$ico</div></td>";
    $f[]="<td style='vertical-align:top;width:99%'><div style='padding-left:8px;margin-bottom: 8px'><span class=\"m-l-xs\">$text</span></div></td>";
    $f[]="</tr>";
    $f[]="<tr><td>&nbsp;</td><td style='text-align:right;border-top:1px solid #CCCCCC;padding-top: 3px' >$icon_hide<small class='label label-primary' $js>{gotit}</small></td></tr>";
    $f[]="</table>";
    $f[]="</li>";
    return @implode("\n",$f);

}
function app_status():bool{
    $page       = CurrentPageName();
    echo "<div id='system-status-start'></div><script>LoadAjax('system-status-start','$page?system-start=yes')</script>";
    return true;

}
function doughnut_ps_mem(): bool {
    $tpl = new template_admin();
    $id = "doughnut-ps-mem";
    $textinfo = array();
    $f = array();

    $sock = new sockets();
    $dataJson = $sock->REST_API("/system/mempy");

    $json = json_decode($dataJson,true);
    if (json_last_error() > JSON_ERROR_NONE) {
        return false;
    }
    if (!isset($json["Status"]) || !$json["Status"]) {
        return false;
    }


    $TOTAL = $json["Info"]["total_ram"]*1.024;
    $sizeu = 0;
    $i = 0;

    $colorz = array(
        "#a383d5", "#8783d5", "#8399d5", "#9bc2da", "#9bdab5",
        "#bdda9b", "#dada9b", "#dac59b", "#dab09b", "#da9b9b",
        "#da9cb1", "#da9cc5", "#da9cda", "#c59cda", "#00aa7f"
    );

    $labels = array();
    $data = array();
    $bgcolor = array();

    foreach ($json["Info"]["programs"] as $line) {
        $size_text = FormatBytes($line["total"]*1.024);
        $sizeu += $line["total"]*1.024;
        $proc= $line["program"];
        // Chart expects KiB-ish (you divide by 1024), keep same logic
        $size =$line["total"]*1.024;

        // friendly names
        $proc = str_replace("HaClusterClient", "HaCluster Client", $proc);
        $proc = str_replace("htopweb", $tpl->_ENGINE_parse_body("HTOP Web"), $proc);
        $proc = str_replace("ufdbguardd", $tpl->_ENGINE_parse_body("{APP_UFDBGUARD}"), $proc);
        $proc = str_replace("artica-phpfpm", $tpl->_ENGINE_parse_body("{APP_FRAMEWORK}"), $proc);
        $proc = str_replace("postgres", $tpl->_ENGINE_parse_body("{APP_POSTGRES}"), $proc);
        $proc = str_replace("nginx", $tpl->_ENGINE_parse_body("{APP_NGINX}"), $proc);
        $proc = str_replace("squid", $tpl->_ENGINE_parse_body("{APP_SQUID}"), $proc);
        $proc = str_replace("rsyslogd", $tpl->_ENGINE_parse_body("{APP_SYSLOG}"), $proc);
        $proc = str_replace("articarest", $tpl->_ENGINE_parse_body("{SQUID_AD_RESTFULL}"), $proc);
        $proc = str_replace("memcached", $tpl->_ENGINE_parse_body("{APP_MEMCACHED}"), $proc);
        $proc = str_replace("php7.3", $tpl->_ENGINE_parse_body("{APP_PHP5}"), $proc);
        $proc = str_replace("php7.4", $tpl->_ENGINE_parse_body("{APP_PHP5}"), $proc);
        $proc = str_replace("php8.2", $tpl->_ENGINE_parse_body("{APP_PHP5}"), $proc);
        $proc = str_replace("slapd", $tpl->_ENGINE_parse_body("{APP_OPENLDAP}"), $proc);
        $proc = str_replace("unbound", $tpl->_ENGINE_parse_body("{APP_UNBOUND}"), $proc);
        $proc = str_replace("Suricata-Main", $tpl->_ENGINE_parse_body("IDS Service"), $proc);
        $proc = str_replace("artica-suricata", $tpl->_ENGINE_parse_body("IDS Artica"), $proc);
        $proc = str_replace("artwatch", $tpl->_ENGINE_parse_body("Artica Watchdog"), $proc);
        $proc = str_replace("systemd-journald", $tpl->_ENGINE_parse_body("Systemd Journal"), $proc);

        $proc = str_replace("crowdsec-firewall-bouncer", $tpl->_ENGINE_parse_body("{APP_IPTABLES_BOUNCER}"), $proc);
        $proc = str_replace("proxy-pac", $tpl->_ENGINE_parse_body("{APP_PROXY_PAC}"), $proc);
        $proc = str_replace("go-shield-server", $tpl->_ENGINE_parse_body("{APP_GO_SHIELD_SERVER}"), $proc);
        $proc = str_replace("go-shield-connector", $tpl->_ENGINE_parse_body("{APP_GO_SHIELD_CONNECTOR}"), $proc);
        $proc = str_replace("artica-webconsole", $tpl->_ENGINE_parse_body("{APP_ARTICAWEBCONSOLE}"), $proc);
        $proc = str_replace("dns-collector", $tpl->_ENGINE_parse_body("{APP_DNS_COLLECTOR}"), $proc);
        $proc = str_replace("artica-smtpd", $tpl->_ENGINE_parse_body("{APP_ARTICA_NOTIFIER}"), $proc);
        $proc = str_replace("crowdsec", $tpl->_ENGINE_parse_body("{APP_CROWDSEC}"), $proc);
        $proc = str_replace("sshd", $tpl->_ENGINE_parse_body("{APP_OPENSSH}"), $proc);
        $proc = str_replace("redis-server", $tpl->_ENGINE_parse_body("Redis Server"), $proc);

        $proc = html_entity_decode($proc);

        $labels[] = json_encode($proc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $data[] = round($size);
        $bgcolor[] = json_encode($colorz[$i]);

        $textinfo[] = "<div><i class=\"fas fa-square-full\" style=\"color:{$colorz[$i]}\"></i><small>&nbsp;{$proc} ({$size_text})</small></div>";

        $i++;
        if ($i > 14) { break; }
    }

    // Others
    $labels[] = json_encode("Others");
    $othersBytes = $TOTAL - $sizeu;
    if ($othersBytes < 0) { $othersBytes = 0; }
    $others = ($othersBytes / 1024);
    $data[] = round($others);
    $bgcolor[] = json_encode("#00aa7f");

    $t = time();

    $f[] = "var doughnutData{$t} = {";
    $f[] = "  labels: [" . implode(",", $labels) . "],";
    $f[] = "  datasets: [{";
    $f[] = "    data: [" . implode(",", $data) . "],";
    $f[] = "    backgroundColor: [" . implode(",", $bgcolor) . "]";
    $f[] = "  }]";
    $f[] = "};";

    $f[] = "var doughnutOptions = {";
    $f[] = "  responsive: false,";
    $f[] = "  plugins: {";
    $f[] = "    legend: { display: false },";
    $f[] = "    datalabels: { display: false }";
    $f[] = "  },";
    $f[] = "  layout: { padding: { left:0,right:0,top:0,bottom:0 } }";
    $f[] = "};";

    // Critical fix: destroy existing chart on this canvas before reusing it
    $f[] = "if( document.getElementById('$id') ){";
    $f[] = "var canvas = document.getElementById(" . json_encode($id) . ");";
    $f[] = "if(!canvas){ console.error(" . json_encode("$id not found") . "); } else {";
    $f[] = "  try {";
    $f[] = "    if (typeof Chart !== 'undefined' && Chart.getChart) {";
    $f[] = "      var existing = Chart.getChart(canvas);";
    $f[] = "      if (existing) { existing.destroy(); }";
    $f[] = "    }";
    $f[] = "  } catch(e) { console.error(e); }";
    $f[] = "  var ctx4 = canvas.getContext('2d');";
    $f[] = "  window.__chart_doughnut_ps_mem = new Chart(ctx4, {type:'doughnut', data:doughnutData{$t}, options:doughnutOptions});";
    $f[] = "}";
    $f[] = "}";
    // Legend text list
    $f[] = "var el = document.getElementById('dashboard-top-processes');";
    $f[] = "if(el){ el.innerHTML=" . json_encode(implode("", $textinfo), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . "; }";

    header("content-type: application/x-javascript");
    echo implode("\n", $f);
    return true;
}


function sysdisk():bool{
    $tpl                    = new template_admin();
    $html=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WEBCONSOLE_SYS_DISK");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function bandwidth():bool{
    $tpl                    = new template_admin();
    $html=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WEBCONSOLE_SYS_BAND");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function Cronos():string{
    $HTMLTITLE=null;
    $FileCookyKey=md5($_SERVER["REMOTE_ADDR"].$_SERVER["HTTP_USER_AGENT"]);
    if(!$GLOBALS["CLASS_SOCKETS"]->INFO_EXISTS("$FileCookyKey.HTMLTITLE")){$HTMLTITLE=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("$FileCookyKey.HTMLTITLE");}
    if(isset($_COOKIE["HTMLTITLE"])){$HTMLTITLE=$_COOKIE["HTMLTITLE"];}

    if(!is_null($HTMLTITLE)){
        $HTMLTITLE=trim($HTMLTITLE);
    }else{
        $HTMLTITLE="%s (%v)";
    }


    if(strpos("  $HTMLTITLE ", "%s")>0){
        $MyHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
        $HTMLTITLE=str_replace("%s", $MyHostname, $HTMLTITLE);
    }
    if(strpos("  $HTMLTITLE ", "%v")>0){
        $MyHostname=trim(@file_get_contents("VERSION"));
        $HTMLTITLE=str_replace("%v", $MyHostname, $HTMLTITLE);
    }

    $HTMLTITLE=str_replace("'", "`", $HTMLTITLE);
    $f[]="document.title = '$HTMLTITLE'";

    $date=date("H:i:s");
    $f[]='$("#faclock").html("'.$date.'");';

    return  @implode("\n",$f);

}

function top_cpus($Status=array()):string{
    if(!is_array($Status)){
        $Status=array();
    }

    if(is_array($Status) && count($Status)==0){
        $Status=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/sysmonitor/stats"),true);
    }

    if(!isset($Status["data"]["latest"])){return "";}

    $MEM_USED_PERC=floatval($Status["data"]["latest"]["mem_percent"]);
    $cpu = floatval($Status["data"]["latest"]["cpu_percent"]);
    VERBOSE("CPU PERCEN $cpu MEM PERC: $MEM_USED_PERC", __LINE__);
    $cpu_color = "text-muted";
    $mem_color = "text-muted";
    if ($MEM_USED_PERC > 70) {
        $mem_color = "text-primary";
    }
    if ($MEM_USED_PERC > 80) {
        $mem_color = "text-warning";
    }
    if ($MEM_USED_PERC > 90) {
        $mem_color = "text-danger";
    }

    if ($cpu > 70) {
        $cpu_color = "text-primary";
    }
    if ($cpu > 80) {
        $cpu_color = "text-warning";
    }
    if ($cpu > 90) {
        $cpu_color = "text-danger";
    }
    $docid="document.getElementById";
    $f[] = "function updateBars() {";
    $f[] = "const cpuUsage = '$cpu'";
    $f[] = "const memUsage = '$MEM_USED_PERC'";
    $f[] = "if( $docid('top-cpu-text') ){";
    $f[] = "    $docid('top-cpu-text').className='$cpu_color';";
    $f[] = "    $docid('top-ram-text').className='$mem_color';";
    $f[] = "    $docid('cpu-fill').style.width = cpuUsage + '%';";
    $f[] = "    $docid('mem-fill').style.width = memUsage + '%';";
    $f[] = "    $docid('cpu-percent').textContent = cpuUsage + '%';";
    $f[] = "    $docid('mem-percent').textContent = memUsage + '%';";
    $f[] = "    $docid('cpu-fill').style.backgroundColor = getColor(cpuUsage);";
    $f[] = "    $docid('mem-fill').style.backgroundColor = getColor(memUsage);";
    $f[] = "    }";
    $f[] = "if( $docid('dash-cpu-title') ){";
    $f[] = "    $docid('dash-cpu-title').textContent = cpuUsage + '%';";
    $f[] = "    }";
    $f[] = "}";
    $f[] = "function getColor(usage) {";
    $f[] = "if (usage < 50) return '#4caf50'; // Green";
    $f[] = "if (usage < 80) return '#ff9800'; // Orange";
    $f[] = "return '#f44336'; // Red";
    $f[] = "}";
    $f[]="updateBars()";

    return @implode("\n",$f);

}



function top_notifications(){
    $tpl = new template_admin();
    $sfile = "/usr/share/artica-postfix/ressources/logs/frontend_notifications.html";
    if (!is_file($sfile)) {
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/frontend/notifications");
    }
    echo $tpl->_ENGINE_parse_body(@file_get_contents($sfile));
}

function top_widgets(): bool {
    $tpl = new template_admin();
    $page=CurrentPageName();
    $memory = sysmemory();
    list($widget_cpu, $Status, $metrics) = widget_cpu();
    $widget_load = widget_load($Status, $metrics);
    $widget_sysdisk = widget_sysdisk();
    $payload = [];
    $FW_INDEX_PHP_HOSTNAME=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FW_INDEX_PHP_HOSTNAME");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/webconsole/widget/hostname");
    $HTTP_X_ARTICA_SUBFOLDER="/";
    if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
        $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/";
    }

    if (strlen($FW_INDEX_PHP_HOSTNAME) > 10) {
        $payload["#widget-hostname"] = $tpl->_ENGINE_parse_body($FW_INDEX_PHP_HOSTNAME);
    }
    $top_cpu_mem=top_cpus($Status);

    if (strlen($memory) > 10) {
        $payload["#sysmemory"] = $tpl->_ENGINE_parse_body($memory);
    }
    if (strlen($widget_cpu) > 10) {
        $payload["#syscpu"] = $tpl->_ENGINE_parse_body($widget_cpu);
    }
    if (strlen($widget_load) > 10) {
        $payload["#sysload"] = $tpl->_ENGINE_parse_body($widget_load);
    }
    if (strlen($widget_sysdisk) > 10) {
        $payload["#sysdisk"] = $tpl->_ENGINE_parse_body($widget_sysdisk);
    }

    // Encode payload safely for JS
    $jsonPayload = json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
    );

    // Return one JS block that:
    // - injects HTML
    // - extracts and executes embedded <script> blocks (peity/popover init, etc.)
    $js = <<<JS
(function(){
  function setHtmlAndRunScripts(selector, html){
    var \$container = \$(selector);
    if (!\$container || \$container.length === 0) return;

    var \$wrap = \$("<div>").html(html);
    var scripts = \$wrap.find("script").map(function(_, s){ return (s.textContent || ""); }).get();
    \$wrap.find("script").remove();

    \$container.html(\$wrap.html());

    for (var i = 0; i < scripts.length; i++) {
      var code = scripts[i];
      if (!code || !code.trim()) continue;
      try { (0, eval)(code); } catch(e){ console.error("Embedded script failed:", e); }
    }
  }

  var payload = $jsonPayload;
  for (var sel in payload) {
    if (!payload.hasOwnProperty(sel)) continue;
    setHtmlAndRunScripts(sel, payload[sel]);
  }
})();
JS;

    header("content-type: application/x-javascript");
    echo $js."\n";
    echo "LoadAjaxSilent('frontend-notifications','$page?frontend-notifications=yes');\n";
    //echo "LoadAjaxSilent('widget-hostname','fw.index.php?widget-hostname=yes');\n";
    echo "Loadjs('$page?doughnut-ps-mem=yes');\n";
    echo "LoadAjaxSilent('widget-info','{$HTTP_X_ARTICA_SUBFOLDER}fw.index.php?widget-info=yes&from-hostname=yes');\n";
    echo "LoadAjaxSilent('frontend-notifications','{$HTTP_X_ARTICA_SUBFOLDER}fw.system.status.php?frontend-notifications=yes');\n";
    echo "LoadAjaxSilent('artica-notifs-barr','{$HTTP_X_ARTICA_SUBFOLDER}fw.icon.top.php?notifs=yes');\n";
    echo "$top_cpu_mem\n";
    echo Cronos();
    return true;
}



function widget_load($Status,$metrics):string{

    list($ORG_LOAD,$CPU_NUMBER,$DASHBOARD_LOAD)=widget_load_GetInfo($Status,$metrics);
    if($CPU_NUMBER==0){
        return "";
    }
    $tpl                    = new template_admin();
    $label_load             = "label-primary";
    $text_load              = "OK";
    $MAXOVER                = $CPU_NUMBER*1.7;

    if(!isset($GLOBALS["PEITYCONF"])) {
        $GLOBALS["PEITYCONF"] = "{ width:255,fill: [\"#eeeeee\"],stroke:\"#18a689\",strokeWidth: 2 }";
    }



    if($ORG_LOAD>$MAXOVER){
        $text_load="{critical}";
        $label_load="label-danger";
    }
    $js2="Loadjs('fw.system.metrics.os.php?js=yes')";
    $title_load="<span style='color:#337AB7'>". $tpl->td_href("{load2}","{load}:{statistics}",$js2)."</span>";
    $dashjs=null;
    $html[]="                <div class=\"ibox float-e-margins\">";
    $html[]="                    <div class=\"ibox-title\">";
    $html[]="                        <span class=\"label $label_load pull-right\">$text_load</span>";
    $html[]="                        <h5><i class='fas fa-tachometer'></i>&nbsp;{load2}</h5>";
    $html[]="                    </div>";
    $html[]="                    <div class=\"ibox-content\">";
    $html[]="                        <h1 class=\"no-margins\">$ORG_LOAD</h1>";
    $html[]="                        <div class=\"stat-percent font-bold text-success\">Max: $MAXOVER <i class=\"fa fa-bolt\"></i></div>";
    $html[]="                        <small>$title_load</small>";
    $html[]="                    </div>";
    if(count($DASHBOARD_LOAD)>1){
        $peity_conf=$GLOBALS["PEITYCONF"];
        $html[]="<span id=\"dashboard-load-line\">".@implode(",",$DASHBOARD_LOAD)."</span>";
        $dashjs="\t$(\"#dashboard-load-line\").peity(\"line\",$peity_conf);";
    }
    $html[]="                </div>";
    $html[]="<script>";
    $html[]="$dashjs";
    $html[]="</script>";
    return $tpl->_ENGINE_parse_body(@implode($html));

}
function widget_sysdisk():string{
    $tpl=new template_admin();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/harddrives/partitions/inodes"));
    if(!is_object($json)||!property_exists($json,"fs_filemax_prc")){
        return "";
    }
    $fs_filemax_prc=$json->fs_filemax_prc;
    $fs_class=null;
    $srcjs=array();
    $INODE_TEXT2=null;

    $prc=$fs_filemax_prc;
    if($prc>90){$fs_class="text-warning";}
    if($prc>95){$fs_class="text-danger";}
    $INODE_TEXT1=$tpl->td_href("<small class='$fs_class'>Descriptors  $prc%</small>",null,"javascript:Loadjs('fw.rrd.php?img=system_fd');");
    if(!is_file(dirname(__FILE__)."/img/squid/system_fd-day.png")){
        $INODE_TEXT1="<small class='$fs_class'>Descriptors  $prc%</small>";

    }

    $fs_class=null;
    $nf_conntrack_loaded=intval($json->nf_conntrack_loaded ?? 0);
    if($nf_conntrack_loaded==1) {
        $nf_conntrack_prc = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nf_conntrack_prc"));
        $prc = $nf_conntrack_prc;
        if ($prc > 90) {
            $fs_class = "text-warning";
        }
        if ($prc > 95) {
            $fs_class = "text-danger";
        }
        $INODE_TEXT2 = "&nbsp;|&nbsp;".$tpl->td_href("<small class='$fs_class'>Connections tracking {$prc}%</small>", null, "javascript:Loadjs('fw.rrd.php?img=nf_conntrack_count');");

        if(!is_file(dirname(__FILE__)."/img/squid/nf_conntrack_count-day.png")){
            $INODE_TEXT2="&nbsp;|&nbsp;<small class='$fs_class'>Connections tracking {$prc}%</small>";

        }

    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SYSTEM_FSNTRCK","$INODE_TEXT1$INODE_TEXT2");


    if(property_exists($json,"Partitions")) {
        foreach ($json->Partitions as $PartData) {
            $inodes_row = array();
            $INODES_USED = $PartData->used_inodes;
            $INODES_TOT = $PartData->total_inodes;
            $INODES_PRC = round($PartData->used_percent,1);
            $INODE_TEXT = "<small>{inode_size} $INODES_USED / $INODES_TOT</small>";
            $inodes_row[] = "<div>";
            $inodes_row[] = "            <div class=\"stat-percent font-bold text-success\">";
            $inodes_row[] = "                    $INODES_PRC% <i class=\"fa fa-bolt\"></i>";
            $inodes_row[] = "            </div>";
            $inodes_row[] = "            $INODE_TEXT";
            $inodes_row[] = "</div>";
            $inodes_text = @implode("\n", $inodes_row);
            $ID_FS_LABEL = $PartData->label;

            if ($ID_FS_LABEL == "") {
                $ID_FS_LABEL = $PartData->mountpoint;
            }
            if ($ID_FS_LABEL == "/boot") {
                continue;
            }

            $SIZE = round($PartData->partition_size / 1024);
            if ($SIZE == 0) {
                continue;
            }
            $PERCENT = round($PartData->partition_percent, 2);
            $value1 = intval($PartData->partition_used);
            $value2 = intval($PartData->partition_free);

            $SIZE_TEXT = FormatBytes($SIZE);
            $UTIL_TEXT = FormatBytes($PartData->partition_used/1024);
            $label_part = "label-primary";
            $text_part = "OK";
            if (($PERCENT > 70) or ($INODES_PRC > 70)) {
                $label_part = "label-warning";
                $text_part = "{warning}";
            }
            if (($PERCENT > 95) or ($INODES_PRC > 95)) {
                $label_part = "label-danger";
                $text_part = "{critical}";
            }
            if ($ID_FS_LABEL <> "/") {
                continue;
            }
            if ($ID_FS_LABEL == "/") {
                $ID_FS_LABEL = "&nbsp;{system2}";
            }

            $dahs = "<span id=\"dashboard-" . md5($ID_FS_LABEL) . "\">$value1,$value2</span>";

            $srcjs[] = "$(\"#dashboard-" . md5($ID_FS_LABEL) . "\").peity(\"pie\",{ fill: [\"#18a689\", \"#eeeeee\"], height:38,width:38 });";
            $ic=ico_hd;
            $html[] = "    <div class=\"ibox float-e-margins\">";
            $html[] = "        <div class=\"ibox-title\">";
            $html[] = "            <span class=\"label $label_part pull-right\">$text_part</span>";
            $html[] = "            <h5><i class='$ic'></i>&nbsp;{partition}:$ID_FS_LABEL</h5>";
            $html[] = "         </div>";
            $html[] = "         <div class=\"ibox-content\">";
            $html[] = "<table>";
            $html[] = "<tr>";
            $html[] = "<td style='width:99%'>";
            $html[] = "            <h1 class=\"no-margins\">$PERCENT%&nbsp;</h1>";
            $html[] = "</td>";
            $html[] = "<td style='width:1%' nowrap=''>$dahs</td>";
            $html[] = "</tr>";
            $html[] = "</table>";
            $html[] = "            <div class=\"stat-percent font-bold text-success\">";
            $html[] = "                    $SIZE_TEXT <i class=\"fa fa-bolt\"></i>";
            $html[] = "            </div>";
            $html[] = "            <small>{used} $UTIL_TEXT</small>";
            $html[] = "$inodes_text";
            $html[] = "        </div>";
            $html[] = "    </div>";
            $html[] = "";

        }
    }
    $html[]="<script>";
    $html[]=@implode("\n",$srcjs);
    $html[]="</script>";
    return $tpl->_ENGINE_parse_body($html);


}
function widget_cpu():array{
    $tpl=new template_admin();
    $label_cpu              = "label-primary";
    $text_cpu               = "OK";
    list($CPUPERCENT,$CPU_NUMBER,$acpu,$status,$metrics)=widget_cpu_GetInfo();
    if($CPU_NUMBER==0){
        return array("",$status,$metrics);
    }

    if($CPUPERCENT>70){
        $label_cpu="label-warning";
        $text_cpu="{warning}";
    }
    if($CPUPERCENT>90){
        $label_cpu="label-danger";
        $text_cpu="{critical}";

    }
    $dashjs=null;
    $label_a="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('fw.system.metrics.os.php?js=yes');\">";
    $page=CurrentPageName();

    $label="$label_a<span class='label $label_cpu'>{history}</span></a>";
    $ico=ico_cpu;
    $html[]="                <div class=\"ibox float-e-margins\">";
    $html[]="                    <div class=\"ibox-title\">";
    $html[]="                        <span class=\"label $label_cpu pull-right\">$text_cpu</span>";
    $html[]="                        <h5><i class='$ico'></i>&nbsp;{cpu}</h5>";
    $html[]="                    </div>";
    $html[]="                    <div class=\"ibox-content\">";
    $html[]="                        <h1 class=\"no-margins\" id='dash-cpu-title'>$CPUPERCENT%</h1>";
    $html[]="                        <div class=\"stat-percent font-bold text-success\">$CPU_NUMBER CPUs <i class=\"fa fa-bolt\"></i>&nbsp;&nbsp;&nbsp;$label</div>";
    $html[]="                        <small>{cpu_use}</small>";
    $html[]="                    </div>";
    if(count($acpu)>0){
        $html[]="<span id=\"dashboard-cpu-line\">".@implode(",",$acpu)."</span>";
        $peity_conf=$GLOBALS["PEITYCONF"];
        $dashjs="$(\"#dashboard-cpu-line\").peity(\"line\",$peity_conf);";
    }

    $html[]="                </div>";
    $html[]="<script>";
    $html[]="$dashjs";
    $html[]="</script>";
    return array($tpl->_ENGINE_parse_body($html),$status,$metrics);

}
function widget_load_GetInfo($status,$metrics):array{

    $num_cpu=intval($status["data"]["num_cpu"] ?? 0);
    if(!isset($status["data"]["latest"])){
        return array(0,$num_cpu,array());
    }
    $latest=$status["data"]["latest"];
    $CURLOAD=round($latest["load1"],1);

    $zMetrics=array();

    if(!isset($metrics["data"])){
        return array($CURLOAD,$num_cpu,$zMetrics);
    }
    foreach ($metrics["data"] as $metric) {
        $zMetrics[]=$metric["load1"];
    }

    return array($CURLOAD,$num_cpu,$zMetrics);

}
function widget_cpu_GetInfo():array{

    $status=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/sysmonitor/stats"),true);
    $num_cpu=intval($status["data"]["num_cpu"] ?? 0);
    if(!isset($status["data"]["latest"])){
        return array(0,$num_cpu,array(),$status,array());
    }
    $latest=$status["data"]["latest"];
    $CPUPERCENT=round($latest["cpu_percent"],1);
    $zMetrics=array();
    $metrics=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/sysmonitor/metrics?hours=1"),true);
    if(!isset($metrics["data"])){
        return array($CPUPERCENT,$num_cpu,$zMetrics,$status,array());
    }
    foreach ($metrics["data"] as $metric) {
        $zMetrics[]=$metric["cpu_percent"];
    }

    return array($CPUPERCENT,$num_cpu,$zMetrics,$status,$metrics);

}

function sysmemory():string{
    VERBOSE(" -- START --",__LINE__);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/widget/memory"));
    if(!is_object($json)||!property_exists($json,"Status")){
        VERBOSE("Status not a property!",__LINE__);
        return "";
    }
    if(!$json->Status){
        return "";
    }
    return base64_decode($json->Widget);
}

function docker_instances():bool{
    include_once(dirname(__FILE__).'/ressources/class.docker.inc');
    $tpl=new template_admin();
    $bg="white-bg";
    if(!is_file("Docker/info.json")){
        echo "Docker/info.json no such file...";
    }

    $Main=unserialize(@file_get_contents("Docker/info.json"));


    if(!is_array($Main)){$Main=array();}
    $dock=new dockerd();

    $PermimeterID=$Main["perimeter"];
    $permietername=$Main["permietername"];
    $groupname=$Main["groupname"];
    $MaxInstances=$Main["MaxInstances"];
    $groupid=$Main["groupid"];
    $tag="com.articatech.artica.scope.$PermimeterID.backend.$groupid";
    $array=$dock->ContainersListByTag($tag);

    $html[]="<table id='table-fireqos-interfaces' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th >{status}</th>";
    $html[]="<th >{name}</th>";
    $html[]="<th >{network}</th>";
    $html[]="<th >{cpu}</th>";
    $html[]="<th  colspan='2'>{memory}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $DockerContainersStats=unserialize(@file_get_contents("Docker/stats.json"));
    if(!is_array($DockerContainersStats)){$DockerContainersStats=array();}
    foreach ($array as $ID=>$name){
        $cpu="-";
        $MemPerc="-";
        $MemUsage="-";
        $status="-";
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $stateInt=$dock->GetContainerState($ID);
        $GetContainerNetworks=$dock->GetContainerNetworks($ID);
        if($stateInt==2){
            $status="<span class='label label-warning'>{paused}</span>";
        }
        if($stateInt==1){
            $status="<span class='label label-primary'>{running}</span>";
        }
        if($stateInt==0){
            $status="<span class='label label-danged'>{stopped}</span>";
        }

        if(isset($DockerContainersStats[$ID])){
            $cpu=$DockerContainersStats[$ID]["CPUPerc"]."%";
            $MemPerc=$DockerContainersStats[$ID]["MemPerc"]."%";
            $MemUsage=$DockerContainersStats[$ID]["MemUsage"];


        }
        $nets=sys_docker_row_container($GetContainerNetworks);

        $html[]="<tr class='$TRCLASS' id='id-$ID'>";
        $html[]="<td width='1%'>$status</td>";
        $html[]="<td nowrap width='99%' style='text-align: left'><strong>$name</strong></td>";
        $html[]="<td width='1%' nowrap>$nets</td>";
        $html[]="<td width='1%' nowrap>$cpu</td>";
        $html[]="<td width='1%' nowrap>$MemPerc</td>";
        $html[]="<td width='1%' nowrap>$MemUsage</td>";
        $html[]="</tr>";



    }
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function sys_docker_row_container($array):string{
    if(count($array)==0){return "-";}
    $f[]="<table>";

    foreach ($array as $NetworkID=>$ligne){

        $IPAddress=$ligne["IPAddress"];
        if($IPAddress==null){continue;}

        $f[]="<tr>";
        $f[]="<td width='1%'><i class='".ico_networks."'></i></td>";
        $f[]="<td>$IPAddress</td>";
        $f[]="</tr>";

    }
    $f[]="</table>";
    return @implode("",$f);

}



