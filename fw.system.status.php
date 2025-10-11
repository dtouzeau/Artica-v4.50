<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.cpu.percent.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}
clean_xss_deep();
if(isset($_GET["system"])){app_status();exit;}
if(isset($_GET["system-start"])){system_start();exit;}
if(isset($_GET["didyouknow-sshportal"])){didyouknow_sshportal();}
if(isset($_GET["doughnut-ps-mem"])){doughnut_ps_mem();exit;}
if(isset($_GET["frontend-notifications"])){frontend_notifications();exit;}
if(isset($_GET["sysmemory"])){sysmemory();exit;}
if(isset($_GET["syscpu"])){syscpu();exit;}
if(isset($_GET["sysload"])){sysload();exit;}
if(isset($_GET["sysdisk"])){sysdisk();exit;}
if(isset($_GET["bandwidth"])){bandwidth();exit;}
if(isset($_GET["docker-instances"])){docker_instances();exit;}
tabs();
function didyouknow_sshportal(){
    header("content-type: application/x-javascript");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("didyouknow_sshportal",1);
    echo "$('#didyouknow_sshportal').remove();";
}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    $EnableSquidLogger=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidLogger"));
    $EnableRedisServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedisServer"));
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
            if ($EnableRedisServer == 1) {
                if ($EnableSquidLogger == 1) {
                    $array["{proxy_statistics}"] = "fw.dashboard.proxy.php";
                    $array["{proxy_members}"] = "fw.dashboard.proxy.members.php";
                }
            }
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

    if($users->AsAnAdministratorGeneric) {

        if(is_file("/usr/share/artica-postfix/img/philesight/system.png")){
            $array["{disk_usage}"] = "fw.dashboard.philesight.php";

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

    $html[]="<div style='margin-top:10px'>$ERROR_FATAL</div>";
    $html[]='<div class="container-fluid">
  <div class="row"  style="vertical-align:top;padding:5px">
    <div class="col-sm-3">
    <div id="sysmemory"><script>LoadAjaxTiny("sysmemory","fw.system.status.php?sysmemory=yes");</script></div>
    </div>
    <div class="col-sm-3">
    <div id="syscpu"></div>
    </div>
    <div class="col-sm-3">
    <div id="sysload"></div>
    </div>
        <div class="col-sm-2">
    <div id="sysdisk"></div>
    </div>
            <div class="col-sm-2">
    <div id="bandwidth-dashboard"></div>
    </div>
  </div>
</div>';
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
    if(!$AS_DOCKER_SERVICE) {$html[]="\tLoadjs('$page?doughnut-ps-mem=yes');";}
    if($AS_DOCKER_SERVICE) {$html[]="\tLoadAjax('ff-status-doughnut','$page?docker-instances=yes');";}
    $html[]="\t$bandwidth_js;";
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
            $CURPATCH=$GLOBALS["CLASS_SOCKETS"]->getFrameWork("artica.php?SPVersion=yes");
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
            $uri = $tpl->ClickMouse("Loadjs('fw.system.upgrade-software.php?product=APP_MEMCACHED')");
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
                $uri = $tpl->ClickMouse("Loadjs('fw.system.upgrade-software.php?product=APP_NETDATA')");
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
    echo $tpl->_ENGINE_parse_body($html);

}
function frontend_notifications(){
    $sfile="/usr/share/artica-postfix/ressources/logs/frontend_notifications.html";
    if(!is_file($sfile)){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/frontend/notifications");
    }
    $html=@file_get_contents($sfile);
    $tpl=new template_admin();
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
    $f[]="<td width=99% style='vertical-align:top'><div style='padding-left:8px;margin-bottom: 8px'><span class=\"m-l-xs\">$text</span></div></td>";
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
function doughnut_ps_mem():bool{
    $tpl=new template_admin();
    $id         ="doughnut-ps-mem";
    $TOTAL_UNIT = "";
    $textinfo   = array();
        $sock=new sockets();
    $dataJson=$sock->REST_API("/system/mempy");

    $json=json_decode($dataJson);
    if (json_last_error()> JSON_ERROR_NONE) {
        return false;
    }
    if(!$json->Status){
        return false;
    }

    $datas=explode("\n",$json->Info);
    $TOTAL=0;$MAIN=array();
    foreach ($datas as $line) {
        $line = trim($line);
        if ($line == null) {
            continue;
        }

        if(preg_match("#^([0-9\.]+)\s+([A-Za-z]+)$#",$line,$re)){

            $f[]="document.getElementById('doughnut-ps-mem-title').innerHTML='{$re[1]} {$re[2]}';";
            $TOTAL=floatval($re[1]);
            $TOTAL_UNIT=$re[2];
            continue;
        }

        if(preg_match("#^[0-9\.]+.*?\+.*?[0-9\.]+.*?=.*?([0-9\.]+)\s+([a-zA-z]+)\s+([a-z0-9A-Z\-\._]+)#",$line,$re)) {
            $value=floatval($re[1]);
            $unit=$re[2];
            $process=$re[3];
            if($unit=="MiB"){$value=$value*1024;}
            if($unit=="GiB"){$value=$value*1024;$value=$value*1024;}
            $value_key = (string) $value;
            if(isset($MAIN[$value_key])){
                continue;
            }
            $MAIN[$value_key]=$process;
        }
    }
    if(!is_array($MAIN)){$MAIN=array();}

    krsort($MAIN);
    $sizeu=0;
    $i=0;

    if($TOTAL>0){
        if($TOTAL_UNIT=="MiB"){$TOTAL=$TOTAL*1024;}
        if($TOTAL_UNIT=="GiB"){$TOTAL=$TOTAL*1024;$TOTAL=$TOTAL*1024;}
    }
    $colorz[]="#a383d5";
    $colorz[]="#8783d5";
    $colorz[]="#8399d5";
    $colorz[]="#9bc2da";
    $colorz[]="#9bdab5";
    $colorz[]="#bdda9b";
    $colorz[]="#dada9b";
    $colorz[]="#dac59b";
    $colorz[]="#dab09b";
    $colorz[]="#da9b9b";
    $colorz[]="#da9cb1";
    $colorz[]="#da9cc5";
    $colorz[]="#da9cda";
    $colorz[]="#c59cda";
    $colorz[]="#00aa7f";


    foreach ($MAIN as $size=>$proc){
        $size_text=FormatBytes($size);
        $sizeu=$sizeu+$size;
        $size=$size/1024;
        $proc=str_replace("HaClusterClient","HaCluster Client",$proc);
        $proc=str_replace("htopweb",$tpl->_ENGINE_parse_body("HTOP Web"),$proc);
        $proc=str_replace("ufdbguardd",$tpl->_ENGINE_parse_body("{APP_UFDBGUARD}"),$proc);
        $proc=str_replace("artica-phpfpm",$tpl->_ENGINE_parse_body("{APP_FRAMEWORK}"),$proc);
        $proc=str_replace("postgres",$tpl->_ENGINE_parse_body("{APP_POSTGRES}"),$proc);
        $proc=str_replace("nginx",$tpl->_ENGINE_parse_body("{APP_NGINX}"),$proc);
        $proc=str_replace("squid",$tpl->_ENGINE_parse_body("{APP_SQUID}"),$proc);
        $proc=str_replace("rsyslogd",$tpl->_ENGINE_parse_body("{APP_SYSLOG}"),$proc);
        $proc=str_replace("articarest",$tpl->_ENGINE_parse_body("{SQUID_AD_RESTFULL}"),$proc);
        $proc=str_replace("memcached",$tpl->_ENGINE_parse_body("{APP_MEMCACHED}"),$proc);
        $proc=str_replace("php7.3",$tpl->_ENGINE_parse_body("{APP_PHP5}"),$proc);
        $proc=str_replace("php7.4",$tpl->_ENGINE_parse_body("{APP_PHP5}"),$proc);
        $proc=str_replace("php8.2",$tpl->_ENGINE_parse_body("{APP_PHP5}"),$proc);
        $proc=str_replace("slapd",$tpl->_ENGINE_parse_body("{APP_OPENLDAP}"),$proc);
        $proc=str_replace("unbound",$tpl->_ENGINE_parse_body("{APP_UNBOUND}"),$proc);
        $proc=str_replace("crowdsec-firewall-bouncer",$tpl->_ENGINE_parse_body("{APP_IPTABLES_BOUNCER}"),$proc);
        $proc=str_replace("proxy-pac",$tpl->_ENGINE_parse_body("{APP_PROXY_PAC}"),$proc);
        $proc=str_replace("go-shield-server",$tpl->_ENGINE_parse_body("{APP_GO_SHIELD_SERVER}"),$proc);
        $proc=str_replace("go-shield-connector",$tpl->_ENGINE_parse_body("{APP_GO_SHIELD_CONNECTOR}"),$proc);
        $proc=str_replace("artica-webconsole",$tpl->_ENGINE_parse_body("{APP_ARTICAWEBCONSOLE}"),$proc);
        $proc=str_replace("dns-collector",$tpl->_ENGINE_parse_body("{APP_DNS_COLLECTOR}"),$proc);
        $proc=str_replace("artica-smtpd",$tpl->_ENGINE_parse_body("{APP_ARTICA_NOTIFIER}"),$proc);
        $proc=str_replace("crowdsec",$tpl->_ENGINE_parse_body("{APP_CROWDSEC}"),$proc);
        $proc=str_replace("sshd",$tpl->_ENGINE_parse_body("{APP_OPENSSH}"),$proc);
        $proc=str_replace("redis-server",$tpl->_ENGINE_parse_body("Redis Server"),$proc);


        $proc=html_entity_decode($proc);
        $labels[]="\"$proc\"";
        $data[]=round($size);
        $bgcolor[]="\"$colorz[$i]\"";
        $textinfo[]="<div><i class=\"fas fa-square-full\" style=\"color:$colorz[$i]\"></i><small>&nbsp;$proc ($size_text)</small></div>";
        $i++;
        if($i>14){break;}
    }

    $labels[]="\"Others\"";
    $size=intval($TOTAL-$sizeu);
    $size=$size/1024;
    $data[]=round($size);
    $bgcolor[]="\"#00aa7f\"";




    $t=time();
    $f[]="var doughnutData$t = {";
    $f[]="labels: [".@implode(",",$labels)."],";
    $f[]="datasets: [{";
    $f[]="data: [".@implode(",",$data)."],";
    $f[]="backgroundColor: [".@implode(",",$bgcolor)."],";
    $f[]=" }]";
    $f[]="};";


    $f[]="var doughnutOptions = {
            layout: {
            padding: {
                left: 0,
                right: 0,
                top: 0,
                bottom: 0
            }
        },
        responsive: false,
        plugins: {
        legend: {
            display: false // Ensure legends are hidden
        },
        datalabels: {
            display: false // Hide the labels (if using the datalabels plugin)
        }
    }
    
    };";

    $f[]="if(!document.getElementById('$id')){alert('$id not found');}";
    $f[]="var ctx4 = document.getElementById('$id').getContext('2d');";
    $f[]="var myChart= new Chart(ctx4, {type: 'doughnut', data: doughnutData$t, options:doughnutOptions});";
    $f[]="document.getElementById('dashboard-top-processes').innerHTML='".@implode("",$textinfo)."';";
    header("content-type: application/x-javascript");
    echo @implode("\n",$f);
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

function sysload():bool{
    $tpl                    = new template_admin();
    $html=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WEBCONSOLE_SYS_LOAD");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function syscpu():bool{
    $tpl                    = new template_admin();
    $html=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WEBCONSOLE_SYS_CPU");
    VERBOSE("CPU: WEBCONSOLE_SYS_CPU --  ".strlen($html)." bytes",__LINE__);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function sysmemory():bool{


    $tpl                    = new template_admin();
    $html=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WEBCONSOLE_SYS_MEM");
    VERBOSE("WEBCONSOLE_SYS_MEM: Len data ".strlen($html)." bytes",__LINE__);
    echo $tpl->_ENGINE_parse_body($html);
    return true;

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



