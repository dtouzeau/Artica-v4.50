<?php
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

// Pentest 2026-11-04 : id et interval etaient recopies sans encodage dans la page (XSS).
if(isset($_GET["id"])){$_GET["id"]=preg_replace('/[^A-Za-z0-9_-]/','',(string)$_GET["id"]);}
if(isset($_GET["interval"])){$_GET["interval"]=preg_replace('/[^A-Za-z0-9_ -]/','',(string)$_GET["interval"]);}
if(isset($_GET["uninstall-decisionip"])){uninstall_decision_ip();exit;}
if(isset($_GET["mtls-install"])){mtls_install();exit;}
if(isset($_GET["mtls-uninstall"])){mtls_uninstall();exit;}
if(isset($_GET["vitrification-enable"])){vitrification_enabled();exit;}
if(isset($_GET["vitrification-disable"])){vitrification_disabled();exit;}
if(isset($_POST["nginxCachesDir"])){section_cache_save();exit;}
if(isset($_GET["uninstall-service"])){uninstall_service_js();exit;}
if(isset($_POST["uninstall-service"])){uninstall_service_confirm();exit;}
if(isset($_GET["pagespeed-enable"])){pagespeed_enable();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table2"])){table2();exit;}
if(isset($_GET["ngs-body"])){ngs_body();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["parameters1"])){parameters1();exit;}
if(isset($_GET["nps-debug-off"])){nps_debug_off();exit;}
if(isset($_POST["NginxProxyProtocol"])){Save();exit;}
if(isset($_POST["PrometheusNginxInterface"])){SavePrometheus();exit;}
if(isset($_POST["NginxCacheRedis"])){Save();exit;}
if(isset($_POST["NginxDebugMode"])){Save();exit;}

if(isset($_GET["updatesoft-js"])){updatesoft_js();exit;}
if(isset($_GET["updatesoft-popup"])){updatesoft_popup();exit;}
if(isset($_GET["file-uploaded"])){updatesoft_uploaded();exit;}
if(isset($_GET["cache-enable"])){cache_enable();exit;}
if(isset($_GET["section-lb-js"])){section_lb_js();exit;}
if(isset($_GET["section-lb-popup"])){section_lb_popup();exit;}
if(isset($_GET["prometheus-js"])){section_prometheus_js();exit;}
if(isset($_GET["prometheus-popup"])){section_prometheus_popup();exit;}

if(isset($_GET["maxdaystats-js"])){section_maxdaystats_js();exit;}
if(isset($_GET["maxdaystats-popup"])){section_maxdaystats_popup();exit;}
if(isset($_POST["NginxMaxDaysStats"])){section_maxdaystats_save();exit;}


if(isset($_GET["section-redis-js"])){section_redis_js();exit;}
if(isset($_GET["section-redis-popup"])){section_redis_popup();exit;}
if(isset($_GET["section-options-js"])){section_options_js();exit;}
if(isset($_GET["section-options-popup"])){section_options_popup();exit;}
if(isset($_GET["section-cache-js"])){section_cache_js();exit;}
if(isset($_GET["section-sla-frontend-js"])){section_sla_frontend_js();exit;}
if(isset($_GET["section-sla-frontend-popup"])){section_sla_frontend_popup();exit;}
if(isset($_POST["NginxEnableFrontEndSLA"])){section_sla_frontend_save();exit;}

if(isset($_GET["section-cache-popup"])){section_cache_popup();exit;}
if(isset($_GET["reconfigure-js"])){reconfigure_js();exit;}
if(isset($_GET["active-health-check-js"])){section_active_health_check_js();exit;}
if(isset($_GET["active-health-check-popup"])){section_active_health_check_popup();exit;}
if(isset($_POST["NginxActiveHealthCheckWorkersCount"])){section_active_health_check_save();exit;}
page();




function reconfigure_js():bool{
    $sock=new sockets();
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $content=$sock->REST_API_NGINX("/reverse-proxy/reconfigure");
    $json=json_decode($content);
    if (json_last_error()> JSON_ERROR_NONE) {
        return  $tpl->js_error(json_last_error_msg());

    }

    if (!$json->Status) {
        return $tpl->js_error($json->Error);

    }

    $html[]="if(typeof NgsRefresh==='function'){NgsRefresh();}";
    echo @implode("\n",$html);
    return $tpl->js_ok("{success}");
}

function section_lb_js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    return $tpl->js_dialog("Load-balancer","$page?section-lb-popup=yes");
}
function section_options_js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    return $tpl->js_dialog("{options}","$page?section-options-popup=yes");
}
function section_redis_js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    return $tpl->js_dialog("{caching}","$page?section-redis-popup=yes");
}
function section_prometheus_js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    return $tpl->js_dialog("Prometheus Exporter","$page?prometheus-popup=yes");
}
function section_maxdaystats_js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    return $tpl->js_dialog("{statistics_retention}","$page?maxdaystats-popup=yes");
}


function section_cache_js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    return $tpl->js_dialog("{sites_caching}","$page?section-cache-popup=yes");
}
function section_sla_frontend_js():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    return $tpl->js_dialog("{NginxDisableFrontEndSLA}","$page?section-sla-frontend-popup=yes");
}
function section_cache_save():bool{
    if(nps_deny()){return false;}
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $nginxsock=new socksngix(0);

    $tpl->CLEAN_POST();
    foreach ($_POST as $key=>$value){
        $nginxsock->SET_INFO($key,$value);
    }

    $sock=new sockets();
    $sock->REST_API_NGINX("/reverse-proxy/reconfigure");
    return admin_tracks_post("Saving Reverse Proxy cache configuration");
}
function section_maxdaystats_popup():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $security = "AsWebMaster";

    $NginxMaxDaysStats=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxMaxDaysStats"));
    if($NginxMaxDaysStats==0){
        $NginxMaxDaysStats=180;
    }
    $form[] = $tpl->field_numeric("NginxMaxDaysStats", "{statistics_retention} ({days})", $NginxMaxDaysStats);
    $html[] = $tpl->form_outside(null, $form, "", "{apply}",
        section_js_form(), $security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function section_maxdaystats_save():bool{
    if(nps_deny()){return false;}
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    // articarest (CleanDB) ramène toute valeur sous 7 jours à 7 : on enregistre ce qu'il appliquera.
    $days=intval($_POST["NginxMaxDaysStats"]);
    if($days<7){$days=7;}
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NginxMaxDaysStats", $days);
    return admin_tracks("Set Max statistics retention to $days days for reverse-proxy");
}

function section_prometheus_popup():bool
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $security = "AsWebMaster";
    $EnablePrometheusNginx = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePrometheusNginx"));
    $PrometheusNginxInterface = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrometheusNginxInterface"));
    $PrometheusNginxPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrometheusNginxPort"));
    $PrometheusNginxNameSpace = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrometheusNginxNameSpace"));
    if (strlen($PrometheusNginxNameSpace) < 3) {
        $PrometheusNginxNameSpace = "nginx";
    }
    $form[] = $tpl->field_checkbox("EnablePrometheusNginx", "{enable_feature}", $EnablePrometheusNginx, true);
    $form[] = $tpl->field_interfaces("PrometheusNginxInterface", "{listen_interface}", $PrometheusNginxInterface);
    if ($PrometheusNginxPort == 0) {
        $PrometheusNginxPort = 9913;
    }
    $form[] = $tpl->field_numeric("PrometheusNginxPort", "{listen_port}", $PrometheusNginxPort);
    $form[] = $tpl->field_text("PrometheusNginxNameSpace", "{namespace}", $PrometheusNginxNameSpace);
    $html[] = $tpl->form_outside(null, $form, "", "{apply}",
        section_js_form(), $security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function section_cache_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $nginxsock=new socksngix(0);
    $nginxCachesDir=intval($nginxsock->GET_INFO("nginxCachesDir"));
    $nginxCachesPath=trim($nginxsock->GET_INFO("nginxCachesPath"));
    $nginxCacheSize=intval($nginxsock->GET_INFO("nginxCacheSize"));
    $nginxCacheMem=intval($nginxsock->GET_INFO("nginxCacheMem"));
    $security="AsWebMaster";

    if($nginxCacheSize==0){$nginxCacheSize=2;}

    if(strlen($nginxCachesPath)<3){
        $nginxCachesPath="/home/nginx/BigCache";

    }
    $form[]=$tpl->field_checkbox("nginxCachesDir","{enable_caching_squid}",$nginxCachesDir,true);
    $form[]=$tpl->field_checkbox("nginxCacheMem","{squid_cache_memory}",$nginxCacheMem,false);
    $form[]=$tpl->field_numeric("nginxCacheSize","{cache_size} (GB)",$nginxCacheSize);

    $form[]=$tpl->field_browse_directory("nginxCachesPath","{change_main_cache_path}",$nginxCachesPath);
    
    $html[]=$tpl->form_outside(null, $form,"{nginx_caching_explain}","{apply}",
        section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}


function section_lb_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $nginxsock=new socksngix(0);
    $NginxProxyProtocol=$nginxsock->GET_INFO("NginxProxyProtocol");
    $NginxLBIpaddr=$nginxsock->GET_INFO("set_real_ip_from");
    $security="AsWebMaster";

    $form[]=$tpl->field_checkbox("NginxProxyProtocol","{proxy_protocol}",$NginxProxyProtocol,false,"{proxy_protocol_explain}");
    $form[]=$tpl->field_text("set_real_ip_from", "{set_real_ip_from}", $NginxLBIpaddr,false,"{set_real_ip_from_explain}");

    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}

function uninstall_service_js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $js=$tpl->framework_buildjs(
        "nginx.php?disable-service=yes",
    "nginx-enable.progress","nginx-enable.log","progress-nginx-restart","document.location.href='/index'");

    return $tpl->js_confirm_execute("{delete_this_service} {APP_NGINX} ?","uninstall-service","yes",$js);

}
function uninstall_service_confirm():bool{
    return admin_tracks("Uninstall Reverse-Proxy service");
}

function restart_array():string{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;

    return $tpl->framework_buildjs("nginx:/reverse-proxy/restarthup",
        "nginx.restart.progress","nginx.restart.progress.txt",
        "progress-nginx-restart","LoadAjax('table-nginx','$page?tabs=yes');");

}

function section_js_form():string{
    $page=CurrentPageName();
    return "BootstrapDialog1.close();LoadAjaxSilent('nginx-global-parameters','$page?parameters1=yes');";
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
	$APP_NGINX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_VERSION");
    $title="{APP_NGINX} v$APP_NGINX_VERSION &raquo;&raquo; {service_status}";
    if(is_file("/etc/artica-postfix/AS_DOCKER_SERVICE")){
        $title="{APP_NGINX} &raquo;&raquo; {service_status}";
    }

    $html=$tpl->page_header($title,"fas fa-globe-africa","{enable_nginx_text}","$page?tabs=yes",
        "nginx-status",
        "progress-nginx-restart",false,
        "table-nginx"
    );

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: {APP_NGINX} {status}",$html);
		echo $tpl->build_firewall();
		return;
	}


    $tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function cache_enable(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $nginxCachesDir=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginxCachesDir"));

    if($nginxCachesDir==0){
        admin_tracks("Activate sites caching on reverse Proxy");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("nginxCachesDir",1);
    }else{
        admin_tracks("Disable sites caching on reverse Proxy");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("nginxCachesDir",0);
    }

    $sock=new sockets();
    $sock->REST_API_NGINX("/reverse-proxy/hupreconfigure");
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-nginx','$page?tabs=yes');";

}

function updatesoft_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->js_dialog1("{APP_NGINX}: {install_upgrade}","$page?updatesoft-popup=yes");
}
function updatesoft_uploaded(){
    if(!fw_progress_page_priv("AsWebMaster")){return false;}   // task link signed for this session: built only for whom may run it
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    // basename(): the name comes from the request and ends up in an unlink and in a REST path.
    $file=basename((string)$_GET["file-uploaded"]);
    $fullpath="/usr/share/artica-postfix/ressources/conf/upload/$file";

    if(!preg_match("#\.(tar\.gz|tgz|gz)$#",$file) || fw_progress_seg($file)===""){
            $tpl->js_error(htmlspecialchars($file,ENT_QUOTES).": {incorrect_package}");
            if($file<>"" && is_file($fullpath)){@unlink($fullpath);}
            return false;
    }

    $jsafeter="dialogInstance1.close();LoadAjax('table-nginx','$page?tabs=yes');";
    admin_tracks("Reverse-Proxy Software with $file package as been successfully uploaded");

    // The reverse-proxy daemon installs the package in the background and removes it.
    echo $tpl->framework_buildjs("nginx:/reverse-proxy/upgrade/package/".rawurlencode($file),
        "nginx.upgrade.progress", "nginx.upgrade.log",  "appnginx-upload",$jsafeter,null,null,"AsWebMaster"
    );
    return true;

}

function updatesoft_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $upgrade_soft_trough_artica=$tpl->_ENGINE_parse_body("{upgrade_soft_trough_artica}");
    $upgrade_soft_trough_artica=str_replace("%s","{APP_NGINX}",$upgrade_soft_trough_artica);
    $html[]=$tpl->div_explain("$upgrade_soft_trough_artica");
    $html[]="<center style='margin:30px'>";
    $html[]="<div id='appnginx-upload'>";
    $html[]=$tpl->button_upload("{upload_package}",$page,null);
    $html[]="</div>";
    $html[]="</center>";
    echo $tpl->_ENGINE_parse_body($html);
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
	$array["{status}"]="$page?table=yes";
	$array["{parameters}"]="$page?parameters=yes";
    $array["{application_images}"]="fw.nginx.docker.php?build-page=yes";



	echo $tpl->tabs_default($array);
}
function parameters(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;

    $APP_NGINX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_NGINX} v$APP_NGINX_VERSION &raquo;&raquo; {parameters}";
    if(is_file("/etc/artica-postfix/AS_DOCKER_SERVICE")){
        $TINY_ARRAY["TITLE"]="{APP_NGINX} &raquo;&raquo; {parameters}";
    }

    $TINY_ARRAY["ICO"]="fas fa-globe-africa";
    $TINY_ARRAY["EXPL"]="{enable_nginx_text}";
    $TINY_ARRAY["URL"]="nginx-status";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    echo "<div id='nginx-global-parameters'></div>
<script>LoadAjaxSilent('nginx-global-parameters','$page?parameters1=yes');
    $jstiny;$('#smoothie-nginx-realtime').remove();</script>";


}
function parameters1():bool{
    // Maquette stitch_reverse_proxy_settings (2026-11-06) : le rendu est dans le bloc nps_*.
    nps_render();
    return true;
}

function section_active_health_check_js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    return $tpl->js_dialog("{active_health_check_daemon}","$page?active-health-check-popup=yes");

}
function section_active_health_check_popup():bool
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $security = "AsWebMaster";
    $NginxActiveHealthCheckDaemonEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxActiveHealthCheckDaemonEnabled"));
    $NginxActiveHealthCheckWorkersCount=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxActiveHealthCheckWorkersCount"));
    $NginxActiveHealthCheckQueueSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxActiveHealthCheckQueueSize"));
    $NginxActiveHealthCheckConcurrency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxActiveHealthCheckConcurrency"));
    $NginxActiveHealthCheckLogLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxActiveHealthCheckLogLevel"));
    $NginxActiveHealthCheckOutgoingInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxActiveHealthCheckOutgoingInterface"));

    $NginxActiveHealthCheckMetricsMaxAge=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxActiveHealthCheckMetricsMaxAge"));
    if($NginxActiveHealthCheckMetricsMaxAge==0){$NginxActiveHealthCheckMetricsMaxAge=30;}
    $NginxActiveHealthCheckMetricsMaxEntries=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxActiveHealthCheckMetricsMaxEntries"));
    if($NginxActiveHealthCheckMetricsMaxEntries==0){$NginxActiveHealthCheckMetricsMaxEntries=100000;}
    $NginxActiveHealthCheckLogMaxAge=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxActiveHealthCheckLogMaxAge"));
    if($NginxActiveHealthCheckLogMaxAge==0){$NginxActiveHealthCheckLogMaxAge=7;}
    $NginxActiveHealthCheckLogMaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxActiveHealthCheckLogMaxSize"));
    if($NginxActiveHealthCheckLogMaxSize==0){$NginxActiveHealthCheckLogMaxSize=100;}

    if($NginxActiveHealthCheckWorkersCount==0){$NginxActiveHealthCheckWorkersCount=4;}
    if($NginxActiveHealthCheckQueueSize==0){$NginxActiveHealthCheckQueueSize=1000;}
    if($NginxActiveHealthCheckConcurrency==0){$NginxActiveHealthCheckConcurrency=100;}
    $logLevel=array();
    $logLevel[0]="info";
    $logLevel[1]="debug";
    $logLevel[2]="warn";
    $logLevel[3]="error";

    $form[] = $tpl->field_checkbox("NginxActiveHealthCheckDaemonEnabled", "{enable_active_health_check_daemon}", $NginxActiveHealthCheckDaemonEnabled);
    $form[] = $tpl->field_numeric("NginxActiveHealthCheckWorkersCount", "{active_health_check_workers}", $NginxActiveHealthCheckWorkersCount);
    $form[] = $tpl->field_numeric("NginxActiveHealthCheckQueueSize", "{active_health_check_queue}", $NginxActiveHealthCheckQueueSize);
    $form[] = $tpl->field_numeric("NginxActiveHealthCheckConcurrency", "{active_health_check_concurrency}", $NginxActiveHealthCheckConcurrency);
    $form[]=$tpl->field_array_hash($logLevel,"NginxActiveHealthCheckLogLevel","nonull:{active_health_check_log_level}",$NginxActiveHealthCheckLogLevel);
    $form[]=$tpl->field_interfaces("NginxActiveHealthCheckOutgoingInterface","nooloopNone:{outgoing_interface}",$NginxActiveHealthCheckOutgoingInterface);
    $form[]=$tpl->field_section("{metrics}");
    $form[] = $tpl->field_numeric("NginxActiveHealthCheckMetricsMaxAge", "{active_health_check_metrics_max_age}", $NginxActiveHealthCheckMetricsMaxAge);
    $form[] = $tpl->field_numeric("NginxActiveHealthCheckMetricsMaxEntries", "{active_health_check_metrics_entries}", $NginxActiveHealthCheckMetricsMaxEntries);
    $form[]=$tpl->field_section("{log_rotation}");
    $form[] = $tpl->field_numeric("NginxActiveHealthCheckLogMaxAge", "{active_health_check_logs_max_age}", $NginxActiveHealthCheckLogMaxAge);
    $form[] = $tpl->field_numeric("NginxActiveHealthCheckLogMaxSize", "{active_health_check_logs_max_size}", $NginxActiveHealthCheckLogMaxSize);
    $html[] = $tpl->form_outside(null, $form, "", "{apply}",
        section_js_form(), $security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function section_active_health_check_save():bool{
    if(nps_deny()){return false;}
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();

    foreach ($_POST as $key=>$val){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($key,$val);
    }

    $sock=new sockets();
    $sock->REST_API_NGINX("/active-health-check/restart");
    return admin_tracks_post("Save reverse-proxy Active Health Check Daemon configuration");
}
function section_sla_frontend_popup():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $NginxDisableFrontEndSLA=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxDisableFrontEndSLA"));
    $NginxEnableFrontEndSLA=1;
    if($NginxDisableFrontEndSLA==1){
        $NginxEnableFrontEndSLA=0;
    }
    $form[]=$tpl->field_checkbox("NginxEnableFrontEndSLA","{enable_feature}",$NginxEnableFrontEndSLA,false);
    $security="AsWebMaster";
    $html[]=$tpl->form_outside(null, $form,"{NginxDisableFrontEndSLA_EXPLAIN}","{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function section_sla_frontend_save():bool{
    if(nps_deny()){return false;}
    $NginxEnableFrontEndSLA=intval($_POST["NginxEnableFrontEndSLA"]);
    if($NginxEnableFrontEndSLA==0){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NginxDisableFrontEndSLA",1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NginxDisableFrontEndSLA",0);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/sla/frontend/sync");
    return admin_tracks("Change Reverse-Proxy SLA feature frontend to $NginxEnableFrontEndSLA");
// Check monitored entry in db
}

function section_options_popup():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $nginxsock=new socksngix(0);

    $NginxDebugMode=intval($nginxsock->GET_INFO("NginxDebugMode"));
    $NginxProxyStorePath=$nginxsock->GET_INFO("NginxProxyStorePath");
    if($NginxProxyStorePath==null){$NginxProxyStorePath="/home/nginx";}
    $NginxMaxLogFileSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxMaxLogFileSize"));
    if($NginxMaxLogFileSize==0){$NginxMaxLogFileSize=300;}

    $NginxRealTimeLogSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxRealTimeLogSize"));
    if($NginxRealTimeLogSize==0){$NginxRealTimeLogSize=50;}


    $form[]=$tpl->field_checkbox("NginxDebugMode","{debug}",$NginxDebugMode,false);
    $form[]=$tpl->field_browse_directory("NginxProxyStorePath", "{working_directory}", $NginxProxyStorePath);
    $form[]=$tpl->field_numeric("NginxMaxLogFileSize","{ArticaMaxLogsSize} (MB)",$NginxMaxLogFileSize);
    $form[]=$tpl->field_numeric("NginxRealTimeLogSize","{maximum_realtime_size} (MB)",$NginxRealTimeLogSize);

    $security="AsWebMaster";
    $html[]=$tpl->form_outside(null, $form,null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function section_redis_popup():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $nginxsock=new socksngix(0);
    $disabled_form=false;

    $nginxCachesDir=intval($nginxsock->GET_INFO("nginxCachesDir"));
    if($nginxCachesDir==1){
        $disabled_form=true;
    }

    $NginxCacheRedis=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedis"));
    $NginxCacheRedisHost=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisHost"));
    $NginxCacheRedisPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisPort"));
    $NginxCacheRedisPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisPassword"));
    $NginxCacheRedisLocal=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisLocal"));
    if($NginxCacheRedisPort==0){$NginxCacheRedisPort=6379;}


    $form[]=$tpl->field_checkbox("NginxCacheRedis","{caching_using_redis}",$NginxCacheRedis,"NginxCacheRedisHost,NginxCacheRedisPort,NginxCacheRedisPassword,NginxCacheRedisLocal","{nginx_redis_caching_explain}");
    $EnableRedisServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedisService"));

    if($EnableRedisServer==1) {
        $form[] = $tpl->field_checkbox_disbaleON("NginxCacheRedisLocal", "{use_local_service}", $NginxCacheRedisLocal,"NginxCacheRedisHost,NginxCacheRedisPort,NginxCacheRedisPassword");
    }else{
        $tpl->field_hidden("NginxCacheRedisLocal",0);
    }

    $form[]=$tpl->field_text("NginxCacheRedisHost","{remote_server_address}",$NginxCacheRedisHost);
    $form[]=$tpl->field_numeric("NginxCacheRedisPort","{remote_server_port}",$NginxCacheRedisPort);
    $form[]=$tpl->field_password("NginxCacheRedisPassword","{password}",$NginxCacheRedisPassword);

    $security="AsWebMaster";
    $html[]=$tpl->form_outside(null, $form,"{nginx_redis_caching_explain}","{apply}", section_js_form(),$security,false,$disabled_form);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}
function SavePrometheus():bool{
    if(nps_deny()){return false;}
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();

    if(intval($_POST["EnablePrometheusNginx"])==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/prometheus/nginx/install");
    }else{
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/prometheus/nginx/uninstall");
    }
    return admin_tracks_post("Save Reverse-proxy Prometheus configuration");
}
function Save():bool{
    if(nps_deny()){return false;}
	$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
	$tpl->CLEAN_POST();

	$nginxsock=new socksngix(0);
	foreach ($_POST as $key=>$val){
        if(preg_match("#^NginxCache#",$key)){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO($key,$val);
            continue;
        }
		$nginxsock->SET_INFO($key,$val);
	}

    $sock=new sockets();
    $sock->REST_API_NGINX("/reverse-proxy/hupreconfigure");
    return admin_tracks_post("Save reverse-proxy global parameters");
}

function table():bool{
    $page=CurrentPageName();
    echo "<div id='nginx-status-flat'></div>
<script>LoadAjax('nginx-status-flat','$page?table2=yes')</script>";
    return true;
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
}
function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}
function pagespeed_enable(){
    $page=CurrentPageName();
    $nginx_pagespeed_enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_pagespeed_enabled"));

    if($nginx_pagespeed_enabled==0){
        admin_tracks("Activate Web optimization module on reverse Proxy");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("nginx_pagespeed_enabled",1);
    }else{
        admin_tracks("Disable Web optimization module on reverse Proxy");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("nginx_pagespeed_enabled",0);
    }
    $sock=new sockets();
    $sock->REST_API_NGINX("/reverse-proxy/hupreconfigure");
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-nginx','$page?tabs=yes');";

}
function NGINX_FW_IPSET_COUNT():int{
    $NGINX_FW_IPSET_COUNT = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NGINX_FW_IPSET_COUNT"));
    if($NGINX_FW_IPSET_COUNT>0){
        return $NGINX_FW_IPSET_COUNT;
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/firewall/ipsetcount"));

   
    $NGINX_FW_IPSET_COUNT = $json->Count;
    if ($NGINX_FW_IPSET_COUNT > 0) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NGINX_FW_IPSET_COUNT", $NGINX_FW_IPSET_COUNT);
        return $NGINX_FW_IPSET_COUNT;
    }
    return 0;
}
function vitrification_enabled():bool{
    if(!ngs_webmaster()){$tpl=new template_admin();return $tpl->js_error("{ERROR_NO_PRIVS2}");}
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("Vitrification",1);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/flush-cache");
    header("content-type: application/x-javascript");
    echo "if(typeof NgsRefresh==='function'){NgsRefresh();}";
    return admin_tracks("Enable reverse-proxy Vitrification feature");
}
function uninstall_decision_ip():bool{
    if(!ngs_webmaster()){$tpl=new template_admin();return $tpl->js_error("{ERROR_NO_PRIVS2}");}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/uninstall");
    header("content-type: application/x-javascript");
    echo "if(typeof NgsRefresh==='function'){NgsRefresh();}";
    return admin_tracks("Uninstall DecisionIP feature");
}
function vitrification_disabled():bool{
    if(!ngs_webmaster()){$tpl=new template_admin();return $tpl->js_error("{ERROR_NO_PRIVS2}");}
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("Vitrification",0);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/flush-cache");
    header("content-type: application/x-javascript");
    echo "if(typeof NgsRefresh==='function'){NgsRefresh();}";
    return admin_tracks("Disable reverse-proxy Vitrification feature");
}
function mtls_install():bool{
    if(!ngs_webmaster()){$tpl=new template_admin();return $tpl->js_error("{ERROR_NO_PRIVS2}");}
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/mtls/install"),true);
    if(!isset($json["Status"])){
        return $tpl->js_error("{protocol_error}");
    }
    if(!$json["Status"]){
        return $tpl->js_error($json["Error"]);
    }
    header("content-type: application/x-javascript");
    echo "document.location.href=\"/reverse-proxy-admin-clients\";";
    return admin_tracks("Install Admin Clients feature");
}
function mtls_uninstall():bool{
    if(!ngs_webmaster()){$tpl=new template_admin();return $tpl->js_error("{ERROR_NO_PRIVS2}");}
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/mtls/uninstall"),true);
    if(!isset($json["Status"])){
        return $tpl->js_error("{protocol_error}");
    }
    if(!$json["Status"]){
        return $tpl->js_error($json["Error"]);
    }
    header("content-type: application/x-javascript");
    echo "if(typeof NgsRefresh==='function'){NgsRefresh();}";
    return admin_tracks("Uninstall Admin Clients feature");
}
function isKey():bool{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/config"),true);

    if(!isset($json["data"]["sections"])){
        return false;
    }
    $user_key=$json["data"]["sections"]["auth"]["user_key"];
    if(strlen($user_key)<5){
        return false;
    }
    return true;
}

function srcache_redis_pwd():string{

    $NginxCacheRedisLocal=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisLocal"));
    $EnableRedisServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedisService"));
    if($EnableRedisServer==0){$NginxCacheRedisLocal=0;}
    if($NginxCacheRedisLocal==1){
        $RedisPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisPassword"));
        if ($RedisPassword<>null){
            return "\t\tredis2_query auth $RedisPassword;";
        }
        return "";
    }
    $NginxCacheRedisPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisPassword"));
    if ($NginxCacheRedisPassword<>null){
        return "\t\tredis2_query auth $NginxCacheRedisPassword;";
    }
    return "";
}
function srcache_redis_pass():array{
    $NginxCacheRedisHost=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisHost"));
    $NginxCacheRedisPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisPort"));
    if($NginxCacheRedisPort==0){$NginxCacheRedisPort=6379;}
    $NginxCacheRedisLocal=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisLocal"));
    $EnableRedisServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedisService"));
    if($EnableRedisServer==0){$NginxCacheRedisLocal=0;}

    if($NginxCacheRedisLocal==1){
        $RedisBindInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisBindInterface");
        if($RedisBindInterface==null){$RedisBindInterface="lo";}
        $NginxCacheRedisPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisBindPort"));
        if($RedisBindInterface=="lo"){
            $NginxCacheRedisHost="127.0.0.1";
        }else{
            $nic=new system_nic($RedisBindInterface);
            $NginxCacheRedisHost=$nic->IPADDR;
        }
        return array($NginxCacheRedisHost,$NginxCacheRedisPort);
    }

    return array($NginxCacheRedisHost,$NginxCacheRedisPort);

}
function redis_status(){

    if (!class_exists("Redis")) {
        return array(false, "{REDIS_PHP_EXTENSION_NOT_LOADED}");

    }

    try {
        $redis = new Redis();
        $addrs = srcache_redis_pass();
        $redis->connect($addrs[0], $addrs[1], 2);

    } catch (Exception $e) {
        return array(false, $e->getMessage());
    }

    $infos = $redis->info();
    foreach ($infos as $key => $val) {
        if (!preg_match("#db([0-9]+)#", $key, $re)) {
            continue;
        }
        $dbs[$re[1]] = true;
    }
    $size = 0;
    foreach ($dbs as $int => $none) {
        if ($redis->select($int)) {
            $size = $size + intval($redis->dbSize());
        }
    }

    return array(true, $size);

}

// ── Réglages globaux du reverse proxy (maquette stitch_reverse_proxy_settings, 2026-11-06) ──
// Onglet Parameters : six cartes de réglages lus par GET_INFO, plus l'état réel des deux
// processus annexes (exporteur Prometheus, démon de health check actif) lu dans leur StatusIni.
// Aucune mesure inventée : ni occupation du cache, ni taille des journaux, ni historique.

function nps_deny():bool{
    if(ngs_webmaster()){return false;}
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");
    return true;
}
/** Phrase d'une seule ligne : les [br] imposés par translate-mcp deviennent des espaces. */
function nps_line(string $token,array $args=array()):string{
    return trim(preg_replace('#<br\s*/?>#i'," ",ngs_tr($token,$args)));
}
function nps_iface_ip(string $iface):string{
    if($iface==""){return "";}
    $nic=new system_nic($iface);
    $ip=trim((string)$nic->IPADDR);
    return filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)?$ip:"";
}
/** Adresse par laquelle l'administrateur joint la console : sert à l'URL de collecte quand l'exporteur écoute partout. */
function nps_console_host():string{
    $h=(string)($_SERVER["HTTP_HOST"] ?? "");
    $h=preg_replace('/:\\d+$/','',$h);
    if(!preg_match('/^[A-Za-z0-9.\\-]{1,253}$/',$h)){return "";}
    return $h;
}
function nps_restart_js(string $route,string $prefix):string{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    return $tpl->framework_buildjs($route,"$prefix.progress","$prefix.progress.txt",
        "progress-nginx-restart","NpsRefresh();");
}

function nps_model():array{
    $S=$GLOBALS["CLASS_SOCKETS"];
    $ng=new socksngix(0);
    $m=array("edit"=>ngs_webmaster());

    $ips=array();
    foreach(explode(",",(string)$ng->GET_INFO("set_real_ip_from")) as $ip){
        $ip=trim($ip);
        if($ip!=""){$ips[]=$ip;}
    }
    $m["lb"]=array("on"=>intval($ng->GET_INFO("NginxProxyProtocol"))==1,"ips"=>$ips);

    $size=intval($ng->GET_INFO("nginxCacheSize"));
    if($size==0){$size=2;}
    $path=trim((string)$ng->GET_INFO("nginxCachesPath"));
    if(strlen($path)<3){$path="/home/nginx/BigCache";}
    $m["disk"]=array("on"=>intval($ng->GET_INFO("nginxCachesDir"))==1,"size"=>$size,
        "mem"=>intval($ng->GET_INFO("nginxCacheMem"))==1,"path"=>$path);

    // Redis : le module srcache doit exister ; le cache disque verrouille la modale (section_redis_popup).
    $local=intval($S->GET_INFO("NginxCacheRedisLocal"))==1 && intval($S->GET_INFO("EnableRedisService"))==1;
    $port=intval($S->GET_INFO("NginxCacheRedisPort"));
    if($port==0){$port=6379;}
    $state="off";
    if(intval($S->GET_INFO("APP_NGINX_SRCACHE"))==0){$state="missing";}
    elseif($m["disk"]["on"]){$state="locked";}
    elseif(intval($S->GET_INFO("NginxCacheRedis"))==1){$state="on";}
    $m["redis"]=array("state"=>$state,"local"=>$local,
        "server"=>trim((string)$S->GET_INFO("NginxCacheRedisHost")).":".$port);

    $dir=(string)$ng->GET_INFO("NginxProxyStorePath");
    if($dir==""){$dir="/home/nginx";}
    $maxlog=intval($S->GET_INFO("NginxMaxLogFileSize"));
    if($maxlog==0){$maxlog=300;}
    $rt=intval($S->GET_INFO("NginxRealTimeLogSize"));
    if($rt==0){$rt=50;}
    $m["log"]=array("debug"=>intval($ng->GET_INFO("NginxDebugMode"))==1,"dir"=>$dir,"max"=>$maxlog,"rt"=>$rt);

    $ret=intval($S->GET_INFO("NginxMaxDaysStats"));
    if($ret==0){$ret=180;}
    if($ret<7){$ret=7;}   // articarest CleanDB ramène toute valeur plus basse à 7
    $m["mon"]=array("sla"=>intval($S->GET_INFO("NginxDisableFrontEndSLA"))!=1,"retention"=>$ret);

    $pon=intval($S->GET_INFO("EnablePrometheusNginx"))==1;
    $iface=trim((string)$S->GET_INFO("PrometheusNginxInterface"));
    if(strlen($iface)<4){$iface="";}   // le démon ignore un nom de 3 caractères ou moins et écoute partout
    $pport=intval($S->GET_INFO("PrometheusNginxPort"));
    if($pport==0){$pport=9913;}
    $ns=trim((string)$S->GET_INFO("PrometheusNginxNameSpace"));
    if(strlen($ns)<3){$ns="nginx";}
    $ip=nps_iface_ip($iface);
    $host=$iface==""?nps_console_host():$ip;
    $m["prom"]=array("on"=>$pon,"iface"=>$iface,"ip"=>$ip,"port"=>$pport,"ns"=>$ns,
        "url"=>$host==""?"":"http://$host:$pport/metrics",
        "svc"=>ngs_service("APP_VTS_EXPORTER","Prometheus Exporter",
            $pon?ngs_rest("/prometheus/nginx/status"):array(),
            nps_restart_js("/prometheus/nginx/restart","vts-exporter"),
            nps_restart_js("/prometheus/nginx/restart","vts-exporter"),$pon));

    $aon=intval($S->GET_INFO("NginxActiveHealthCheckDaemonEnabled"))==1;
    $def=array("NginxActiveHealthCheckWorkersCount"=>4,"NginxActiveHealthCheckQueueSize"=>1000,
        "NginxActiveHealthCheckMetricsMaxAge"=>30,"NginxActiveHealthCheckMetricsMaxEntries"=>100000,
        "NginxActiveHealthCheckLogMaxAge"=>7,"NginxActiveHealthCheckLogMaxSize"=>100);
    $a=array();
    foreach($def as $k=>$v){
        $x=intval($S->GET_INFO($k));
        $a[$k]=$x==0?$v:$x;
    }
    // La modale affiche 100 quand la valeur vaut 0 : c'est donc ce que le démon reçoit au prochain enregistrement.
    $conc=intval($S->GET_INFO("NginxActiveHealthCheckConcurrency"));
    if($conc==0){$conc=100;}
    $levels=array(0=>"info",1=>"debug",2=>"warn",3=>"error");
    $lvl=intval($S->GET_INFO("NginxActiveHealthCheckLogLevel"));
    $ahcjs=nps_restart_js("nginx:/active-health-check/restart","nginx.restart");
    $m["ahc"]=array("on"=>$aon,"workers"=>$a["NginxActiveHealthCheckWorkersCount"],
        "queue"=>$a["NginxActiveHealthCheckQueueSize"],"conc"=>$conc,
        "level"=>$levels[$lvl] ?? "info",
        "iface"=>trim((string)$S->GET_INFO("NginxActiveHealthCheckOutgoingInterface")),
        "mage"=>$a["NginxActiveHealthCheckMetricsMaxAge"],"mentries"=>$a["NginxActiveHealthCheckMetricsMaxEntries"],
        "lage"=>$a["NginxActiveHealthCheckLogMaxAge"],"lsize"=>$a["NginxActiveHealthCheckLogMaxSize"],
        "svc"=>ngs_service("APP_ACTIVE_HEALTH_CHECK","{active_health_check_daemon}",
            $aon?ngs_rest_nginx("/active-health-check/status"):array(),$ahcjs,$ahcjs,$aon));
    return $m;
}

// ── Petits éléments ──

/** Pastille d'état : toujours une icône et un mot. $kind : ok, warn, err, off, lock. */
function nps_pill(string $kind,string $text):string{
    $ico=array("ok"=>"fa-circle-check","warn"=>"fa-triangle-exclamation","err"=>"fa-circle-xmark",
        "off"=>"fa-circle-minus","lock"=>"fa-lock");
    $i=$ico[$kind] ?? "fa-circle-minus";
    return "<span class='ak-pill ak-".($kind=="lock"?"off":$kind)."'><i class='fas $i' aria-hidden='true'></i>$text</span>";
}
function nps_onoff(bool $on):string{
    return $on?nps_pill("ok","{nps_on}"):nps_pill("off","{nps_off}");
}
function nps_mono(string $s):string{
    return "<span class='ak-chip nps-mono'>".ngs_h($s)."</span>";
}
function nps_row(string $label,string $value,string $hint=""):string{
    $h=$hint==""?"":"<div class='nps-hint'>$hint</div>";
    return "<div class='nps-row'><div class='nps-row-l'><span class='nps-lbl'>$label</span>$h</div><div class='nps-val'>$value</div></div>";
}
function nps_edit_btn(array $m,string $js,string $title,bool $disabled=false,string $reason=""):string{
    if(!$m["edit"]){return "";}
    if($disabled){
        return "<button type='button' class='ak-btn' disabled title=\"$reason\" aria-label=\"{nps_edit} $title — $reason\"><i class='fas fa-lock' aria-hidden='true'></i>{nps_edit}</button>";
    }
    return "<button type='button' class='ak-btn' onclick=\"$js\" aria-label=\"{nps_edit} $title\"><i class='fas fa-pen' aria-hidden='true'></i>{nps_edit}</button>";
}
function nps_card_head(array $m,string $ico,string $tone,string $title,string $right):string{
    $lock=$m["edit"]?"":"<i class='fas fa-lock nps-ro' role='img' aria-label='{nps_readonly}' title='{nps_readonly}'></i>";
    return "<div class='nps-head'><div class='nps-head-l'><span class='ak-ico ak-ico-sm ak-ico-sq $tone'><i class='fas $ico' aria-hidden='true'></i></span><h3>$title</h3>$lock</div><div class='nps-head-r'>$right</div></div>";
}
/** Libellé et pastille de l'état d'un processus annexe. */
function nps_svc_state(array $svc):array{
    $st=$svc["state"] ?? "unknown";
    if($st=="running"){return array("ok","{nps_running}");}
    if($st=="disabled"){return array("off","{nps_off}");}
    if($st=="stopped"){return array("err","{nps_stopped}");}
    return array("err","{nps_unknown}");
}
function nps_proc_line(array $svc):string{
    if(($svc["state"] ?? "")!="running"){return "";}
    $p=array();
    if(intval($svc["pid"] ?? 0)>0){$p[]="PID ".intval($svc["pid"]);}
    $up=ngs_uptime(intval($svc["age"] ?? 0));
    if($up!=""){$p[]=ngs_tr("nps_up",array($up));}
    if(intval($svc["memory"] ?? 0)>0){$p[]=FormatBytes(intval($svc["memory"]),true);}
    if(count($p)==0){return "";}
    return "<div class='nps-proc'><i class='fas fa-microchip' aria-hidden='true'></i>".implode(" · ",$p)."</div>";
}

// ── Widgets ──

function nps_strip_cell(string $label,string $kind,string $value):string{
    return "<div class='nps-cell'><div class='nps-cell-l'>$label</div><div class='nps-cell-v nps-c-$kind'><span class='ak-dot ak-$kind' aria-hidden='true'></span><span title=\"$value\">$value</span></div></div>";
}
function nps_strip(array $m):string{
    $c=array();
    $c[]=nps_strip_cell("Load-Balancer",$m["lb"]["on"]?"ok":"off",$m["lb"]["on"]?"{nps_proxy_protocol}":"{nps_off}");
    $c[]=nps_strip_cell("{cache_on_disk}",$m["disk"]["on"]?"ok":"off",$m["disk"]["on"]?$m["disk"]["size"]." GB":"{nps_off}");
    $r=$m["redis"];
    $rv=array("missing"=>array("warn","{nps_module_missing}"),"locked"=>array("off","{nps_locked_disk}"),
        "off"=>array("off","{nps_off}"),"on"=>array("ok",$r["local"]?"{nps_local_service}":ngs_h($r["server"])));
    $c[]=nps_strip_cell("{caching_using_redis}",$rv[$r["state"]][0],$rv[$r["state"]][1]);
    $c[]=nps_strip_cell("{nps_debug}",$m["log"]["debug"]?"warn":"off",$m["log"]["debug"]?"{nps_on}":"{nps_off}");
    list($k,$t)=nps_svc_state($m["prom"]["svc"]);
    $c[]=nps_strip_cell("Prometheus Exporter",$k,$t);
    list($k,$t)=nps_svc_state($m["ahc"]["svc"]);
    $c[]=nps_strip_cell("{nps_ahc_short}",$k,$t);
    return "<section class='nps-strip' aria-label='{parameters}'>".implode("",$c)."</section>";
}
function nps_debug_banner(array $m):string{
    if(!$m["log"]["debug"]){return "";}
    $page=CurrentPageName();
    $btn=$m["edit"]?"<button type='button' class='ak-link' onclick=\"Loadjs('$page?nps-debug-off=yes')\">{nps_disable_debug}</button>":"";
    return "<div class='ak-banner ak-warn nps-banner' role='status'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><div class='nps-banner-t'>".nps_line("nps_debug_banner")."</div>$btn</div>";
}
function nps_down_banner(array $m):string{
    $down=array();
    foreach(array($m["prom"]["svc"],$m["ahc"]["svc"]) as $svc){
        if(in_array($svc["state"],array("stopped","unknown"))){$down[]=$svc;}
    }
    if(count($down)==0){return "";}
    $names=array();
    $btns=array();
    foreach($down as $svc){
        $names[]=$svc["label"];
        if($m["edit"] && $svc["js"]!=""){
            $btns[]="<button type='button' class='ak-link' onclick=\"".$svc["js"]."\" aria-label=\"{nps_restart} ".$svc["label"]."\">{nps_restart}".(count($down)>1?" ".$svc["label"]:"")."</button>";
        }
    }
    $txt=nps_line("nps_down_banner",array(implode(", ",$names)));
    return "<div class='ak-banner ak-err nps-banner' role='status'><i class='fas fa-circle-xmark' aria-hidden='true'></i><div class='nps-banner-t'>$txt</div>".implode("",$btns)."</div>";
}
function nps_card_lb(array $m):string{
    $page=CurrentPageName();
    $chips="<span class='ak-muted'>{nps_none}</span>";
    if(count($m["lb"]["ips"])>0){
        $x=array();
        foreach($m["lb"]["ips"] as $ip){$x[]=nps_mono($ip);}
        $chips="<span class='ak-chips nps-chips'>".implode("",$x)."</span>";
    }
    $b=nps_row("{proxy_protocol}",nps_onoff($m["lb"]["on"]));
    $b.=nps_row("{set_real_ip_from}",$chips);
    $b.="<div class='nps-note'><i class='fas fa-circle-info' aria-hidden='true'></i>".nps_line("nps_lb_note")."</div>";
    $head=nps_card_head($m,"fa-scale-balanced","ak-accent","Load-Balancer",
        nps_edit_btn($m,"Loadjs('$page?section-lb-js=yes')","Load-Balancer"));
    return "<article class='ak-card nps-card'>$head<div class='nps-body'>$b</div></article>";
}
function nps_card_cache(array $m):string{
    $page=CurrentPageName();
    $d=$m["disk"];$r=$m["redis"];
    $disk="<div class='nps-sub'><h4>{cache_on_disk}</h4>".nps_edit_btn($m,"Loadjs('$page?section-cache-js=yes')","{cache_on_disk}")."</div>";
    $disk.=nps_row("{nps_state}",$d["on"]?nps_pill("ok","{nps_active}"):nps_pill("off","{nps_off}"));
    $disk.=nps_row("{cache_size}",$d["size"]." GB");
    $disk.=nps_row("{squid_cache_memory}",nps_onoff($d["mem"]));
    $disk.=nps_row("{nps_cache_path}",nps_mono($d["path"]));

    $note="<div class='nps-note nps-note-box'><i class='fas fa-lock' aria-hidden='true'></i>".nps_line("nps_exclusive_note")."</div>";

    $dis=false;$why="";
    if($r["state"]=="missing"){$dis=true;$why="{nps_srcache_missing}";}
    if($r["state"]=="locked"){$dis=true;$why="{nps_locked_disk}";}
    $pill=array("missing"=>nps_pill("warn","{nps_module_missing}"),"locked"=>nps_pill("lock","{nps_locked_disk}"),
        "off"=>nps_pill("off","{nps_off}"),"on"=>nps_pill("ok","{nps_active}"));
    $red="<div class='nps-sub'><h4>{caching_using_redis}</h4>".nps_edit_btn($m,"Loadjs('$page?section-redis-js=yes')","{caching_using_redis}",$dis,$why)."</div>";
    $red.=nps_row("{nps_state}",$pill[$r["state"]]);
    if($r["state"]=="on"){
        $red.=nps_row("{nps_server}",$r["local"]?"{nps_local_service}":nps_mono($r["server"]));
    }
    $head=nps_card_head($m,"fa-database","ak-info","{caching}","");
    return "<article class='ak-card nps-card'>$head<div class='nps-body'>$disk$note$red</div></article>";
}
function nps_card_log(array $m):string{
    $page=CurrentPageName();
    $l=$m["log"];
    $b=nps_row("{nps_debug}",$l["debug"]?nps_pill("warn","{nps_debug_verbose}"):nps_pill("off","{nps_off}"));
    $b.=nps_row("{nps_working_directory}",nps_mono($l["dir"]));
    $b.=nps_row("{ArticaMaxLogsSize}",$l["max"]." MB");
    $b.=nps_row("{maximum_realtime_size}",$l["rt"]." MB");
    $head=nps_card_head($m,"fa-file-lines","ak-warn","{system_log}",
        nps_edit_btn($m,"Loadjs('$page?section-options-js=yes')","{system_log}"));
    return "<article class='ak-card nps-card'>$head<div class='nps-body'>$b</div></article>";
}
function nps_card_mon(array $m):string{
    $page=CurrentPageName();
    $o=$m["mon"];
    // Deux réglages, deux modales distinctes : un bouton par ligne plutôt qu'un seul en en-tête.
    $b=nps_row("{NginxDisableFrontEndSLA}",nps_onoff($o["sla"]).nps_edit_btn($m,"Loadjs('$page?section-sla-frontend-js=yes')","{NginxDisableFrontEndSLA}"),nps_line("nps_sla_hint"));
    $b.=nps_row("{statistics_retention}",ngs_tr("nps_days",array($o["retention"])).nps_edit_btn($m,"Loadjs('$page?maxdaystats-js=yes')","{statistics_retention}"),"{nps_min_days}");
    $head=nps_card_head($m,"fa-heart-pulse","ak-err","{monitoring}","");
    return "<article class='ak-card nps-card'>$head<div class='nps-body'>$b</div></article>";
}
function nps_card_prom(array $m):string{
    $page=CurrentPageName();
    $p=$m["prom"];
    list($k,$t)=nps_svc_state($p["svc"]);
    $b=nps_proc_line($p["svc"]);
    $if="{nps_all_interfaces}";
    if($p["iface"]!=""){$if=nps_mono($p["iface"].($p["ip"]!=""?" (".$p["ip"].")":""));}
    $b.=nps_row("{listen_interface}",$if);
    $b.=nps_row("{nps_listen_port}",nps_mono((string)$p["port"]));
    $b.=nps_row("{namespace}",nps_mono($p["ns"]));
    if($p["on"] && $p["url"]!=""){
        $u=ngs_h($p["url"]);
        $b.=nps_row("{nps_scrape_url}","<span class='ak-chip nps-mono' id='nps-scrape'>$u</span><button type='button' class='ak-act' onclick=\"NpsCopy('nps-scrape',this)\" aria-label='{nps_copy}' title='{nps_copy}'><i class='far fa-copy' aria-hidden='true'></i></button>");
    }
    $head=nps_card_head($m,"fa-chart-line","ak-violet","Prometheus Exporter",
        nps_pill($k,$t).nps_edit_btn($m,"Loadjs('$page?prometheus-js=yes')","Prometheus Exporter"));
    $cls=$p["on"]?"":" nps-card-off";
    return "<article class='ak-card nps-card$cls'>$head<div class='nps-body'>$b</div></article>";
}
function nps_card_ahc(array $m):string{
    $page=CurrentPageName();
    $a=$m["ahc"];
    list($k,$t)=nps_svc_state($a["svc"]);
    $b=nps_proc_line($a["svc"]);
    $b.=nps_row("{active_health_check_workers}",number_format($a["workers"]));
    $b.=nps_row("{active_health_check_queue}",number_format($a["queue"]));
    $b.=nps_row("{nps_concurrency}",number_format($a["conc"]));
    $b.=nps_row("{active_health_check_log_level}",nps_mono($a["level"]));
    $b.=nps_row("{outgoing_interface}",$a["iface"]==""?"{nps_default}":nps_mono($a["iface"]));
    $b.=nps_row("{metrics}",ngs_tr("nps_metrics_val",array($a["mage"],number_format($a["mentries"]))));
    $b.=nps_row("{log_rotation}",ngs_tr("nps_logrot_val",array($a["lage"],$a["lsize"])));
    $head=nps_card_head($m,"fa-stethoscope","ak-ok","{active_health_check_daemon}",
        nps_pill($k,$t).nps_edit_btn($m,"Loadjs('$page?active-health-check-js=yes')","{active_health_check_daemon}"));
    $cls=$a["on"]?"":" nps-card-off";
    return "<article class='ak-card nps-card$cls'>$head<div class='nps-body'>$b</div></article>";
}

function nps_style():string{
    return "<style>
.nps{font-family:'Open Sans',Helvetica,Arial,sans-serif;color:#111827;max-width:1320px;margin-top:8px}
.nps-strip{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));background:#fff;border:1px solid #E5E7EB;border-radius:10px;margin-bottom:16px}
.nps-cell{padding:16px 18px;border-left:1px solid #EEF0F3;min-width:0}
.nps-cell:first-child{border-left:0}
.nps-cell-l{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#6B7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.nps-cell-v{display:flex;align-items:center;gap:8px;margin-top:6px;font-size:15px;font-weight:600;min-width:0}
.nps-cell-v span:last-child{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.nps-c-warn{color:#B45309}.nps-c-err{color:#B91C1C}.nps-c-off{color:#6B7280}
.nps-banner{gap:12px;margin-bottom:16px;font-size:13px}
.nps-banner-t{flex:1;min-width:0}
.nps-banner .ak-link{color:inherit;text-decoration:underline}
.nps-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:24px;align-items:start}
.nps-col{display:flex;flex-direction:column;gap:24px;min-width:0}
.nps-head{display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:56px;padding:10px 20px;border-bottom:1px solid #F1F2F4}
.nps-head-l,.nps-head-r{display:flex;align-items:center;gap:10px;min-width:0}
.nps-head h3{margin:0;font-size:15px;font-weight:600;color:#111827}
.nps-ro{color:#9CA3AF;font-size:13px}
.nps-body{padding:6px 20px 14px}
.nps-row{display:flex;align-items:center;justify-content:space-between;gap:16px;min-height:44px;padding:6px 0;border-bottom:1px solid #F3F4F6}
.nps-row:last-child{border-bottom:0}
.nps-row-l{min-width:0;flex:1 1 auto}
.nps-lbl{font-size:13px;color:#374151}
.nps-hint{font-size:12px;color:#6B7280;margin-top:2px;line-height:1.45}
.nps-val{display:flex;align-items:center;justify-content:flex-end;gap:8px;font-size:13px;color:#111827;text-align:right;min-width:0;flex:0 1 auto}
.nps-mono{max-width:100%;overflow:hidden;text-overflow:ellipsis}
.nps-chips{justify-content:flex-end;max-width:360px}
.nps .ak-btn[disabled]{cursor:not-allowed;color:#9CA3AF;background:#F9FAFB;border-color:#E5E7EB}
.nps-sub{display:flex;align-items:center;justify-content:space-between;padding:12px 0 8px;border-bottom:1px solid #F3F4F6}
.nps-sub h4{margin:0;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#475569}
.nps-note{display:flex;gap:8px;align-items:flex-start;font-size:12px;color:#6B7280;padding-top:10px;line-height:1.5}
.nps-note i{margin-top:2px}
.nps-note-box{background:#F8FAFC;border:1px solid #E5E7EB;border-radius:8px;padding:10px 12px;margin:12px 0 4px}
.nps-proc{display:flex;align-items:center;gap:8px;font-size:12px;color:#6B7280;padding:10px 0;border-bottom:1px solid #F3F4F6}
.nps-card-off .nps-body .nps-val,.nps-card-off .nps-body .nps-mono{color:#9CA3AF}
@media (max-width:1200px){.nps-grid{grid-template-columns:minmax(0,1fr)}.nps-strip{grid-template-columns:repeat(3,minmax(0,1fr))}.nps-cell:nth-child(4){border-left:0}.nps-cell:nth-child(n+4){border-top:1px solid #EEF0F3}}
</style>";
}

function nps_js():string{
    $page=CurrentPageName();
    return "<script>
function NpsRefresh(){ if(document.getElementById('nginx-global-parameters')){LoadAjaxSilent('nginx-global-parameters','$page?parameters1=yes');} }
function NpsCopy(id,btn){
  var e=document.getElementById(id); if(!e){return;} var t=e.textContent.trim();
  var done=function(){ btn.setAttribute('title','".ngs_tr("nps_copied")."'); };
  if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(t).then(done); return; }
  var a=document.createElement('textarea'); a.value=t; a.style.position='fixed'; a.style.opacity='0';
  document.body.appendChild(a); a.select(); try{document.execCommand('copy');done();}catch(x){} document.body.removeChild(a);
}
if(window.NpsTimer){clearInterval(window.NpsTimer);}
window.NpsTimer=setInterval(function(){
  if(!document.getElementById('nps-root')){clearInterval(window.NpsTimer);window.NpsTimer=null;return;}
  if(document.querySelector('.modal.in,.modal.show,.sweet-alert.visible')){return;}
  NpsRefresh();
},60000);
</script>";
}

function nps_render():bool{
    $tpl=new template_admin();
    $m=nps_model();
    $h=array();
    $h[]="<div class='nps ak' id='nps-root'>";
    $h[]=nps_strip($m);
    $h[]=nps_debug_banner($m);
    $h[]=nps_down_banner($m);
    $h[]="<div class='nps-grid'><div class='nps-col'>";
    $h[]=nps_card_lb($m);
    $h[]=nps_card_log($m);
    $h[]=nps_card_prom($m);
    $h[]="</div><div class='nps-col'>";
    $h[]=nps_card_cache($m);
    $h[]=nps_card_mon($m);
    $h[]=nps_card_ahc($m);
    $h[]="</div></div></div>";
    echo nps_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).nps_js();
    return true;
}

/** Bouton « Disable debug » du bandeau : même effet que décocher Debug dans la modale. */
function nps_debug_off():bool{
    header("content-type: application/x-javascript");
    if(!ngs_webmaster()){$tpl=new template_admin();return $tpl->js_error("{ERROR_NO_PRIVS2}");}
    $ng=new socksngix(0);
    $ng->SET_INFO("NginxDebugMode",0);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/hupreconfigure");
    echo "NpsRefresh();";
    return admin_tracks("Disable reverse-proxy debug mode");
}

// ── Tableau de bord du service (maquette stitch_reverse_proxy_dashboard_status, 2026-11-06) ──
// Données réelles seulement : /stats (compteurs VTS depuis le démarrage de nginx),
// la table PostgreSQL nginx_stats (une ligne par site toutes les 5 minutes :
// requêtes, octets sortants, hit, miss, 4xx, 5xx) et les réglages des modules.
// La latence n'a pas d'historique (la colonne requestmsecs n'est jamais écrite) :
// seul le temps de réponse moyen en direct des backends est montré.

function ngs_periods():array{
    return array("today"=>"{ngs_p_today}","7d"=>"{ngs_p_7d}","30d"=>"{ngs_p_30d}","180d"=>"{ngs_p_180d}");
}
function ngs_period($p):string{
    $p=(string)$p;
    $all=ngs_periods();
    return isset($all[$p])?$p:"today";
}
/** Pas de la série (secondes). */
function ngs_step(string $period):int{
    if($period=="today"){return 600;}
    if($period=="7d"){return 3600;}
    return 86400;
}
/**
 * Bornes en temps « naïf » : zdate est un timestamp SANS fuseau écrit en heure
 * locale ; extract(epoch) le lit comme de l'UTC. On compte donc en heure locale
 * exprimée comme de l'UTC, et les libellés passent par gmdate().
 */
function ngs_naive_now():int{
    return time()+intval(date("Z"));
}
function ngs_start(string $period):int{
    $now=ngs_naive_now();
    $midnight=$now-($now%86400);
    if($period=="today"){return $midnight;}
    if($period=="7d"){return ($now-($now%3600))-(7*86400)+3600;}
    if($period=="30d"){return $midnight-29*86400;}
    return $midnight-179*86400;
}
function ngs_ts_label(int $ts,string $period):string{
    if($period=="today"){return gmdate("H:i",$ts);}
    if($period=="7d"){return gmdate("D H:00",$ts);}
    return gmdate("d M",$ts);
}

function ngs_accents():array{
    $ac=array("accent"=>"#0f766e","accent_dark"=>"#0d9488",
        "accent_soft"=>"rgba(15,118,110,.12)","accent_line"=>"rgba(15,118,110,.35)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
    }
    return $ac;
}
function ngs_rgba(string $hex,string $alpha):string{
    $h=ltrim(trim($hex),'#');
    if(strlen($h)==3){$h=$h[0].$h[0].$h[1].$h[1].$h[2].$h[2];}
    if(!preg_match('/^[0-9a-fA-F]{6}$/',$h)){return $hex;}
    return sprintf("rgba(%d,%d,%d,%s)",hexdec(substr($h,0,2)),hexdec(substr($h,2,2)),hexdec(substr($h,4,2)),$alpha);
}
/** Traduit un jeton puis remplace ses %s dans l'ordre. */
function ngs_tr(string $token,array $args=array()):string{
    $tpl=new template_admin();
    $s=$tpl->_ENGINE_parse_body("{".$token."}");
    foreach($args as $a){
        $p=strpos($s,"%s");
        if($p===false){break;}
        $s=substr($s,0,$p).$a.substr($s,$p+2);
    }
    return $s;
}
function ngs_h($s):string{
    return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
}
function ngs_bytes($b):string{
    $b=floatval($b);
    $u=array("B","KB","MB","GB","TB","PB");
    $i=0;
    while($b>=1024 && $i<count($u)-1){$b=$b/1024;$i++;}
    return ($i==0?number_format($b,0):number_format($b,1))." ".$u[$i];
}
function ngs_bps($bitsPerSec):string{
    $v=floatval($bitsPerSec);
    $u=array("bps","Kbps","Mbps","Gbps","Tbps");
    $i=0;
    while($v>=1000 && $i<count($u)-1){$v=$v/1000;$i++;}
    return number_format($v,$i==0?0:1)." ".$u[$i];
}
function ngs_short($v):string{
    $v=floatval($v);
    if($v>=1e9){return number_format($v/1e9,1)." G";}
    if($v>=1e6){return number_format($v/1e6,1)." M";}
    if($v>=1e4){return number_format($v/1e3,1)." k";}
    return number_format($v,0);
}
function ngs_pct($part,$total,int $dec=1):string{
    $total=floatval($total);
    if($total<=0){return "0 %";}
    $p=floatval($part)*100/$total;
    if($p>0 && $p<0.1){return "&lt;0.1 %";}
    return number_format($p,$dec)." %";
}
function ngs_uptime(int $sec):string{
    if($sec<=0){return "";}
    $d=intdiv($sec,86400); $h=intdiv($sec%86400,3600); $m=intdiv($sec%3600,60);
    if($d>0){return "{$d} d {$h} h";}
    if($h>0){return "{$h} h {$m} min";}
    return "{$m} min";
}
function ngs_webmaster():bool{
    $users=new usersMenus();
    return $users->AsWebMaster || $users->AsSystemAdministrator;
}

// ── Modèle ──

function ngs_rest_nginx(string $path):array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX($path),true);
    return is_array($json)?$json:array();
}
function ngs_rest(string $path):array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($path),true);
    return is_array($json)?$json:array();
}

/** Une ligne d'état de service : clé ini, libellé, état, action. */
function ngs_service(string $key,string $label,array $json,string $restartjs,string $startjs,bool $enabled=true):array{
    $s=array("key"=>$key,"label"=>$label,"state"=>"unknown","memory"=>0,"js"=>"","info"=>"");
    // Service non activé : gris, sans bouton (le démon refuse de démarrer tant que le réglage vaut 0).
    if(!$enabled){
        $s["state"]="disabled";
        return $s;
    }
    if(empty($json["Status"])){
        $s["js"]=$startjs;
        return $s;
    }
    $s["info"]=(string)($json["Info"] ?? "");
    $ini=new Bs_IniHandler();
    $ini->loadString($s["info"]);
    $a=$ini->_params[$key] ?? array();
    if(!is_array($a) || count($a)==0){return $s;}
    if(intval($a["running"] ?? 0)==1){
        $s["state"]="running";
        $s["memory"]=intval($a["master_memory"] ?? 0);
        $s["pid"]=intval($a["master_pid"] ?? 0);
        $s["age"]=intval($a["master_time"] ?? 0);
        $s["js"]=$restartjs;
        return $s;
    }
    $s["state"]="stopped";
    $s["js"]=$startjs;
    return $s;
}
function ngs_services_model():array{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $after="NgsRefresh();";
    $out=array();
    $start=$tpl->framework_buildjs("/reverse-proxy/start","artica-reverse-proxy.start.progress",
        "artica-reverse-proxy.start.progress.txt","progress-nginx-restart",$after);
    $out[]=ngs_service("APP_NGINX","{APP_NGINX}",ngs_rest_nginx("/reverse-proxy/service/status"),
        $tpl->framework_buildjs("nginx:/reverse-proxy/restarthup","nginx.restart.progress",
            "nginx.restart.progress.txt","progress-nginx-restart",$after),$start);
    $out[]=ngs_service("APP_REVERSE_PROXY","{APP_REVERSE_PROXY}",ngs_rest("/nginx/artica-reverse/status"),
        $tpl->framework_buildjs("/nginx/artica-reverse/restart","artica-reverse-proxy.progress",
            "artica-reverse-proxy.progress.txt","progress-nginx-restart",$after),$start);
    $ahc=$tpl->framework_buildjs("nginx:/active-health-check/restart","nginx.restart.progress",
        "nginx.restart.progress.txt","progress-nginx-restart",$after);
    $ahcOn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxActiveHealthCheckDaemonEnabled"))==1;
    $out[]=ngs_service("APP_ACTIVE_HEALTH_CHECK","{APP_ACTIVE_HEALTH_CHECK}",
        $ahcOn?ngs_rest_nginx("/active-health-check/status"):array(),$ahc,$ahc,$ahcOn);
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePrometheusNginx"))==1){
        $vts=$tpl->framework_buildjs("/prometheus/nginx/restart","vts-exporter.progress",
            "vts-exporter.progress.txt","progress-nginx-restart",$after);
        $out[]=ngs_service("APP_VTS_EXPORTER","{APP_VTS_EXPORTER}",ngs_rest("/prometheus/nginx/status"),$vts,$vts);
    }
    return $out;
}

/** Série de la période, trous remis à zéro. */
function ngs_series(string $period):array{
    $step=ngs_step($period);
    $start=ngs_start($period);
    $now=ngs_naive_now();
    $r=array("ok"=>false,"step"=>$step,"points"=>array());
    if($step==600){
        $bucket="date_trunc('hour',zdate)+((extract(minute FROM zdate)::int/10)*interval '10 minutes')";
    }elseif($step==3600){
        $bucket="date_trunc('hour',zdate)";
    }else{
        $bucket="date_trunc('day',zdate)";
    }
    $sql="SELECT extract(epoch FROM $bucket)::bigint AS ts, SUM(requestcounter) AS req, SUM(outbytes) AS bytes,"
        ." SUM(fourxx) AS e4, SUM(fivexx) AS e5, SUM(hit) AS hit, SUM(miss) AS miss"
        ." FROM nginx_stats WHERE zdate >= (to_timestamp($start) AT TIME ZONE 'UTC') GROUP BY 1 ORDER BY 1";
    $q=new postgres_sql();
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){return $r;}
    $r["ok"]=true;
    $byTs=array();
    while($l=@pg_fetch_assoc($results)){
        $byTs[intval($l["ts"])]=array("req"=>floatval($l["req"]),"bytes"=>floatval($l["bytes"]),
            "e4"=>floatval($l["e4"]),"e5"=>floatval($l["e5"]),"hit"=>floatval($l["hit"]),"miss"=>floatval($l["miss"]));
    }
    $zero=array("req"=>0,"bytes"=>0,"e4"=>0,"e5"=>0,"hit"=>0,"miss"=>0);
    for($ts=$start;$ts<=$now;$ts+=$step){
        $p=isset($byTs[$ts])?$byTs[$ts]:$zero;
        $p["ts"]=$ts;
        $r["points"][]=$p;
    }
    return $r;
}
function ngs_col(array $series,string $k):array{
    $out=array();
    foreach($series["points"] as $p){$out[]=$p[$k];}
    return $out;
}

/** Totaux par site sur la période (requêtes, octets, erreurs). */
function ngs_ranking(string $period,array $names):array{
    $start=ngs_start($period);
    $sql="SELECT serviceid, SUM(requestcounter) AS req, SUM(outbytes) AS bytes, SUM(fourxx)+SUM(fivexx) AS err"
        ." FROM nginx_stats WHERE zdate >= (to_timestamp($start) AT TIME ZONE 'UTC') GROUP BY serviceid";
    $q=new postgres_sql();
    $results=$q->QUERY_SQL($sql);
    $rows=array();
    if(!$q->ok){return $rows;}
    while($l=@pg_fetch_assoc($results)){
        $id=intval($l["serviceid"]);
        $name=isset($names[$id])?$names[$id]:($id==0?"{ngs_unknown_site}":"#$id");
        $rows[]=array("id"=>$id,"name"=>$name,"req"=>floatval($l["req"]),
            "bytes"=>floatval($l["bytes"]),"err"=>floatval($l["err"]));
    }
    return $rows;
}

function ngs_model(string $period):array{
    $m=array("period"=>$period,"docker"=>is_file("/etc/artica-postfix/AS_DOCKER_SERVICE"));
    $stats=ngs_rest_nginx("/stats");
    $m["live"]=!empty($stats["Status"]) && isset($stats["Stats"]) && is_array($stats["Stats"]);
    $S=$m["live"]?$stats["Stats"]:array();
    $m["conn"]=isset($S["connections"]) && is_array($S["connections"])?$S["connections"]:array();
    $m["zones"]=isset($S["serverZones"]) && is_array($S["serverZones"])?$S["serverZones"]:array();
    $m["upstreams"]=isset($S["upstreamZones"]) && is_array($S["upstreamZones"])?$S["upstreamZones"]:array();
    $m["shared"]=isset($S["sharedZones"]) && is_array($S["sharedZones"])?$S["sharedZones"]:array();
    $m["uptime"]=0;
    if(intval($S["loadMsec"] ?? 0)>0 && intval($S["nowMsec"] ?? 0)>intval($S["loadMsec"] ?? 0)){
        $m["uptime"]=intdiv(intval($S["nowMsec"])-intval($S["loadMsec"]),1000);
    }
    // La zone « * » est le total de toutes les zones.
    $m["total"]=isset($m["zones"]["*"]) && is_array($m["zones"]["*"])?$m["zones"]["*"]:array();
    $m["responses"]=isset($m["total"]["responses"]) && is_array($m["total"]["responses"])?$m["total"]["responses"]:array();

    $ids=ngs_rest_nginx("/sites/ids");
    $m["sites"]=array();
    if(isset($ids["sites"]) && is_array($ids["sites"])){
        foreach($ids["sites"] as $id=>$name){$m["sites"][intval($id)]=(string)$name;}
    }
    $m["series"]=ngs_series($period);
    $m["ranking"]=ngs_ranking($period,$m["sites"]);
    $m["services"]=$m["docker"]?array():ngs_services_model();
    $m["emergency"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxEmergency"))==1;
    $m["version"]=(string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_VERSION");
    $m["webmaster"]=ngs_webmaster();
    return $m;
}

// ── Composants ──

function ngs_style():string{
    if(!empty($GLOBALS["NGS_CSS_DONE"])){return "";}
    $GLOBALS["NGS_CSS_DONE"]=true;
    $ac=ngs_accents();
    $ad=$ac["accent_dark"];
    return "<style>
.ngs-wrap{margin-top:4px;color:#0f172a;font-size:13px}
.ngs-sec{padding:18px 20px;margin-bottom:16px;min-width:0}
.ngs-sec-h{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px;flex-wrap:wrap}
.ngs-sec-t{font-size:16px;font-weight:600;line-height:24px;letter-spacing:-.01em;color:#0f172a;display:flex;align-items:center;gap:8px}
.ngs-sec-t i{color:$ad}
.ngs-sec-s{font-size:12px;line-height:16px;color:#64748b;margin-top:2px}
.ngs-emer{gap:12px;padding:12px 16px;margin-bottom:16px;font-weight:600}
.ngs-emer i{font-size:18px}
.ngs-banner{display:flex;align-items:center;gap:16px;padding:14px 20px;margin-bottom:16px;flex-wrap:wrap}
.ngs-orb{width:44px;height:44px;border-radius:9999px;display:inline-flex;align-items:center;justify-content:center;font-size:20px;flex:0 0 auto}
.ngs-ok .ngs-orb{background:#dcfce7;color:#15803d}
.ngs-warn .ngs-orb{background:#fef3c7;color:#b45309}
.ngs-crit .ngs-orb{background:#fee2e2;color:#b91c1c}
.ngs-btitle{font-size:18px;font-weight:700;letter-spacing:-.02em;line-height:24px}
.ngs-bsub{font-size:13px;color:#64748b;line-height:18px}
.ngs-layout{display:grid;grid-template-columns:300px minmax(0,1fr);gap:16px;align-items:start}
.ngs-aside{min-width:0}
.ngs-aside .svcst-wrap{width:100%!important}
.ngs-main{min-width:0}
.ngs-layout .ngs-tiles{grid-template-columns:repeat(3,minmax(0,1fr))}
@media(max-width:1599px){.ngs-layout .ngs-donut-row{flex-direction:column;align-items:stretch;gap:16px}
.ngs-layout .ngs-donut-row>.ngs-ring{align-self:center}
.ngs-layout .ngs-lgg{flex-wrap:wrap;justify-content:center}
.ngs-layout .ngs-duo64{grid-template-columns:minmax(0,1fr)}}
@media(min-width:1600px){.ngs-layout .ngs-tiles{grid-template-columns:repeat(6,minmax(0,1fr))}}
.ngs-tiles{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:16px;margin:0 0 16px 0}
.ngs-tile{position:relative;display:flex;flex-direction:column;padding:14px 16px 12px;min-width:0;height:100%}
.ngs-head{display:flex;align-items:center;justify-content:space-between;gap:8px}
.ngs-label{font-size:11px;font-weight:700;line-height:16px;letter-spacing:.06em;text-transform:uppercase;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ngs-chip{width:28px;height:28px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:13px;flex:0 0 auto}
.ngs-val{font-size:28px;font-weight:700;line-height:36px;letter-spacing:-.02em;margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-variant-numeric:tabular-nums}
.ngs-off .ngs-val{color:#94a3b8}
.ngs-unit{font-size:14px;font-weight:500;letter-spacing:0;color:#64748b;margin-left:4px}
.ngs-spark{display:block;width:100%;height:30px;margin-top:6px}
.ngs-spark-none{height:30px;margin-top:6px;border-bottom:1px dashed #e2e8f0}
.ngs-foot{margin-top:auto;padding-top:6px;font-size:12px;line-height:16px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ngs-wrap button:focus-visible,.ngs-wrap a:focus-visible{outline:2px solid $ad;outline-offset:2px}
.ngs-legend{display:flex;gap:14px;flex-wrap:wrap;font-size:13px;color:#475569;align-items:center}
.ngs-legend span{display:inline-flex;align-items:center;gap:6px}
.ngs-sw{width:10px;height:10px;border-radius:3px;display:inline-block;flex:0 0 auto}
.ngs-chart{position:relative;height:280px}
.ngs-strip-h{display:flex;justify-content:space-between;gap:12px;margin-top:14px;padding-top:12px;border-top:1px solid #f1f5f9;font-size:12px;color:#64748b}
.ngs-strip{position:relative;height:64px;margin-top:6px}
.ngs-empty{display:flex;align-items:center;justify-content:center;gap:10px;min-height:140px;color:#64748b;font-size:13px;background:#f8fafc;border:1px dashed #e2e8f0;border-radius:10px;text-align:center;padding:16px}
.ngs-duo{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px}
.ngs-duo64{grid-template-columns:minmax(0,3fr) minmax(0,2fr)}
.ngs-duo>.ngs-sec{margin-bottom:16px}
.ngs-donut-row{display:flex;align-items:center;gap:24px}
.ngs-ring{position:relative;width:168px;height:168px;flex:0 0 auto}
.ngs-ring svg{width:100%;height:100%;transform:rotate(-90deg)}
.ngs-ring-c{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center}
.ngs-ring-v{font-size:24px;font-weight:700;letter-spacing:-.02em;line-height:28px;font-variant-numeric:tabular-nums}
.ngs-ring-l{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#64748b}
.ngs-ring.sm{width:120px;height:120px}
.ngs-ring.sm .ngs-ring-v{font-size:20px}
.ngs-lgl{flex:1;min-width:0;list-style:none;margin:0;padding:0}
.ngs-lgl li{display:grid;grid-template-columns:12px minmax(0,1fr) auto 64px;gap:10px;align-items:center;padding:6px 0;border-bottom:1px solid #f1f5f9}
.ngs-lgl li:last-child{border-bottom:0}
.ngs-lgl .n{font-weight:600;text-align:right;font-variant-numeric:tabular-nums}
.ngs-lgl .p{color:#64748b;text-align:right;font-size:12px}
.ngs-lgl .t{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ngs-lgg{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px 18px;flex:1;min-width:0}
.ngs-lgg div{display:flex;align-items:center;gap:8px;white-space:nowrap}
.ngs-lgg b{font-variant-numeric:tabular-nums}
.ngs-cfoot{margin-top:16px;padding-top:12px;border-top:1px solid #f1f5f9}
.ngs-cfoot-h{display:flex;justify-content:space-between;gap:12px;font-size:13px;color:#475569;margin-bottom:8px}
.ngs-bar{height:8px}
.ngs-rank{list-style:none;margin:0;padding:0}
.ngs-rank li{display:grid;grid-template-columns:minmax(0,220px) minmax(0,1fr) 110px 64px;gap:16px;align-items:center;padding:9px 0;border-bottom:1px solid #f1f5f9}
.ngs-rank li:last-child{border-bottom:0}
.ngs-rank .nm{font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ngs-rank .v{text-align:right;font-weight:700;font-variant-numeric:tabular-nums}
.ngs-rank .p{text-align:right;color:#64748b;font-size:12px}
.ngs-rank li.other .nm{color:#64748b;font-weight:500}
.ngs-rank li.other .ak-bar>span{background:#94a3b8}
.ngs-tbl{width:100%;border-collapse:collapse;font-size:13px;color:#0f172a}
.ngs-tbl th{font-size:12px;font-weight:600;color:#64748b;text-align:left;padding:8px 8px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.ngs-tbl td{padding:9px 8px;border-bottom:1px solid #f1f5f9;color:#0f172a;white-space:nowrap}
.ngs-tbl .r{text-align:right;font-variant-numeric:tabular-nums}
.ngs-tbl .mono{font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-size:12px}
.ngs-tbl tr.down td{background:#fef2f2}
.ngs-tbl td.ell{max-width:240px;overflow:hidden;text-overflow:ellipsis}
.ngs-st{display:inline-flex;align-items:center;gap:6px;font-weight:600}
.ngs-st.up{color:#15803d}.ngs-st.down{color:#b91c1c}.ngs-st.backup{color:#64748b}
.ngs-note{font-size:12px;color:#64748b;margin-top:10px}
.ngs-kv{background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px;padding:8px 14px;margin-top:16px}
.ngs-kv div{display:flex;justify-content:space-between;gap:12px;padding:5px 0;font-size:13px;color:#475569}
.ngs-kv b{color:#0f172a;font-variant-numeric:tabular-nums}
.ngs-kv .bad b{color:#b91c1c}.ngs-kv .good b{color:#15803d}
.ngs-mem{display:flex;align-items:center;gap:24px}
.ngs-mem-v{font-size:20px;font-weight:700;letter-spacing:-.02em}
.ngs-mem-v small{font-size:13px;font-weight:500;color:#64748b}
.ngs-mods{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:16px;margin-bottom:16px}
.ngs-mod{padding:14px 16px;display:flex;flex-direction:column;gap:10px;min-width:0}
.ngs-mod-h{display:flex;align-items:center;justify-content:space-between;gap:8px}
.ngs-mod-h .ak-pill{padding:3px 8px}
.ngs-mod.off .ak-ico{--ak-tbg:#f1f5f9;--ak-tfg:#64748b}
.ngs-mod-t{font-size:14px;font-weight:600;line-height:20px}
.ngs-mod-d{font-size:13px;color:#475569;line-height:18px;min-height:18px}
.ngs-mod-a{display:flex;flex-wrap:wrap;gap:4px 14px;margin-top:auto;padding-top:10px;border-top:1px solid #f1f5f9}
.ngs-mod-a a{font-size:13px;font-weight:500;color:$ad!important;text-decoration:none!important;cursor:pointer}
.ngs-mod-a a:hover{text-decoration:underline!important}
.ngs-mod-a a.dz{color:#b91c1c!important}
.ngs-a{color:$ad;font-weight:500;cursor:pointer;margin-left:10px}
.ngs-foot-bar{display:flex;justify-content:flex-end;margin-bottom:8px}
@media(max-width:1200px){.ngs-tiles{grid-template-columns:repeat(3,minmax(0,1fr))}
.ngs-duo,.ngs-duo64{grid-template-columns:minmax(0,1fr)}
.ngs-mods{grid-template-columns:repeat(3,minmax(0,1fr))}
.ngs-layout{grid-template-columns:minmax(0,1fr)}
.ngs-aside{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px}}
@media(prefers-reduced-motion:no-preference){.ngs-val,.ngs-ring-v{animation:ngsIn .2s ease-out}}
@keyframes ngsIn{from{opacity:0}to{opacity:1}}
</style>";
}

/** Sparkline SVG : aire en dégradé sous une ligne, échelle 0 → maximum. */
function ngs_spark(array $vals,string $color):string{
    $n=count($vals);
    if($n<2 || max($vals)<=0){return "<div class='ngs-spark-none' aria-hidden='true'></div>";}
    $max=max($vals);
    $line=array();
    foreach($vals as $i=>$v){
        $line[]=sprintf('%s%.2f,%.2f',$i==0?'M':'L',$i*100/($n-1),28-($v/$max)*25);
    }
    $d=implode(' ',$line);
    $gid='ngsg'.substr(md5(uniqid('',true)),0,10);
    return "<svg class='ngs-spark' viewBox='0 0 100 30' preserveAspectRatio='none' aria-hidden='true'>"
        ."<defs><linearGradient id='$gid' x1='0' y1='0' x2='0' y2='1'>"
        ."<stop offset='0' stop-color='$color' stop-opacity='.28'/><stop offset='1' stop-color='$color' stop-opacity='0'/>"
        ."</linearGradient></defs><path d='$d L100,30 L0,30 Z' fill='url(#$gid)'/>"
        ."<path d='$d' fill='none' stroke='$color' stroke-width='1.6' stroke-linejoin='round' vector-effect='non-scaling-stroke'/></svg>";
}

/**
 * Anneau SVG. $segs : liste de array(valeur, couleur, libellé) ; un total nul
 * ne trace que le fond.
 */
function ngs_ring(array $segs,string $center,string $label,bool $small=false):string{
    $total=0;
    foreach($segs as $s){$total+=max(0,floatval($s[0]));}
    $r=38; $c=2*M_PI*$r; $off=0;
    $h="<div class='ngs-ring".($small?" sm":"")."'><svg viewBox='0 0 100 100' role='img' aria-label='".ngs_h(strip_tags($label))."'>";
    $h.="<circle cx='50' cy='50' r='$r' fill='none' stroke='#f1f5f9' stroke-width='12'/>";
    if($total>0){
        foreach($segs as $s){
            $v=max(0,floatval($s[0]));
            if($v<=0){continue;}
            $len=$v*$c/$total;
            $h.=sprintf("<circle cx='50' cy='50' r='%d' fill='none' stroke='%s' stroke-width='12' stroke-dasharray='%.3f %.3f' stroke-dashoffset='%.3f'><title>%s</title></circle>",
                $r,$s[1],$len,$c,-$off,ngs_h(strip_tags($s[2])));
            $off+=$len;
        }
    }
    $h.="</svg><div class='ngs-ring-c'><span class='ngs-ring-v'>$center</span><span class='ngs-ring-l'>$label</span></div></div>";
    return $h;
}

function ngs_emergency(array $m):string{
    if(!$m["emergency"]){return "";}
    return "<div class='ak-banner ak-err ngs-emer' role='alert'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i>{nginx_emergency_mode}</div>";
}

/** Colonne de gauche : une tuile SERVICE_STATUS() par service, comme avant la refonte.
 *  Désactivé = gris, injoignable = rouge avec {start}. La sortie est déjà parsée (CSS comprise). */
function ngs_services_aside(array $m):string{
    if($m["docker"] || count($m["services"])==0){return "";}
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $out=array();
    foreach($m["services"] as $s){
        $key=$s["key"];
        $info=$s["info"];
        if($s["state"]=="disabled"){
            $info="[$key]\nservice_disabled=0\n";
        }elseif($s["state"]=="unknown"){
            // Démon muet ou section absente : tuile « arrêté » plutôt qu'un trou dans la colonne.
            $info="[$key]\nservice_disabled=1\nrunning=0\n";
        }
        $ini=new Bs_IniHandler();
        $ini->loadString($info);
        $js=$s["js"]!==""?$s["js"]:null;
        $ver=null;
        if($key=="APP_ACTIVE_HEALTH_CHECK" && $s["state"]=="running"){
            $v=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ACTIVE_HEALTH_CHECK_DAEMON_VER"));
            if(preg_match('/^[0-9A-Za-z._-]{1,32}$/',$v)){$ver=$v;}
        }
        $out[]=$tpl->SERVICE_STATUS($ini,"$key:width=100%",$js,$ver);
    }
    return "<aside class='ngs-aside' aria-label='Services'>".implode("\n",$out)."</aside>";
}

function ngs_banner(array $m):string{
    if($m["docker"]){
        $count=0; $max=0;
        if(class_exists("dockerd") || is_file(dirname(__FILE__).'/ressources/class.docker.inc')){
            include_once(dirname(__FILE__).'/ressources/class.docker.inc');
            $Main=unserialize(@file_get_contents("Docker/info.json"));
            if(is_array($Main)){
                $dock=new dockerd();
                $tag="com.articatech.artica.scope.{$Main["perimeter"]}.backend.{$Main["groupid"]}";
                $list=$dock->ContainersListByTag($tag);
                $count=is_array($list)?count($list):0;
                $max=intval($Main["MaxInstances"] ?? 0);
            }
        }
        $cls=$count>0?"ngs-ok":"ngs-warn";
        return "<div class='ak-card ngs-banner $cls'><span class='ngs-orb'><i class='fab fa-docker' aria-hidden='true'></i></span>"
            ."<div><div class='ngs-btitle'>{APP_NGINX}</div><div class='ngs-bsub'>{instances}: $count/$max</div></div></div>";
    }
    $level="ngs-ok"; $ico="fas fa-circle-check"; $msg="{ngs_health_ok}";
    foreach($m["services"] as $s){
        if($s["state"]=="running" || $s["state"]=="disabled"){continue;}
        if($s["key"]=="APP_NGINX"){$level="ngs-crit"; $ico="fas fa-circle-xmark"; $msg="{ngs_health_crit}"; break;}
        $level="ngs-warn"; $ico="fas fa-triangle-exclamation"; $msg="{ngs_health_warn}";
    }
    $sub=array($msg);
    if($m["uptime"]>0 && $level!="ngs-crit"){$sub[]=ngs_tr("ngs_running_for",array(ngs_uptime($m["uptime"])));}
    foreach($m["services"] as $s){
        if($s["key"]=="APP_NGINX" && $s["memory"]>0){$sub[]="{memory} ".FormatBytes($s["memory"]);}
    }
    $ver=$m["version"]!==""?" v".ngs_h($m["version"]):"";
    return "<div class='ak-card ngs-banner $level'><span class='ngs-orb'><i class='$ico' aria-hidden='true'></i></span>"
        ."<div><div class='ngs-btitle'>{APP_NGINX}$ver</div><div class='ngs-bsub'>".implode(" &middot; ",$sub)."</div></div>"
        ."</div>";
}

/** Une tuile : LABEL, ICON, TONE, VALUE, UNIT, SPARK, FOOT, OFF. */
function ngs_tile(array $t):string{
    $tone=$t["TONE"];
    $off=!empty($t["OFF"]);
    $unit=(isset($t["UNIT"]) && $t["UNIT"]!=="")?"<span class='ngs-unit'>{$t["UNIT"]}</span>":"";
    $h ="<div class='ak-card ngs-tile".($off?" ngs-off":"")."'>";
    $h.="<div class='ngs-head'><span class='ngs-label'>{$t["LABEL"]}</span>";
    $h.="<span class='ngs-chip' style='background:".ngs_rgba($tone,".12").";color:$tone'><i class='{$t["ICON"]}' aria-hidden='true'></i></span></div>";
    $h.="<div class='ngs-val'>{$t["VALUE"]}$unit</div>";
    if(isset($t["SPARK"])){$h.=ngs_spark($off?array():$t["SPARK"],$tone);}
    $tip=(isset($t["TIP"]) && $t["TIP"]!=="")?$t["TIP"]:($t["FOOT"] ?? "");
    $h.="<div class='ngs-foot' title=\"".ngs_h(html_entity_decode(strip_tags($tip),ENT_QUOTES,"UTF-8"))."\">".($t["FOOT"] ?? "")."</div></div>";
    return $h;
}
function ngs_split_unit(string $s):array{
    $p=explode(" ",$s,2);
    return array($p[0],isset($p[1])?$p[1]:"");
}

function ngs_kpi_requests(array $m):string{
    $ac=ngs_accents(); $ser=$m["series"];
    $sum=array_sum(ngs_col($ser,"req"));
    $foot="";
    if(intval($m["conn"]["requests"] ?? 0)>0){$foot=ngs_tr("ngs_since_start",array(ngs_short($m["conn"]["requests"])));}
    return ngs_tile(array("LABEL"=>"{requests}","ICON"=>"fas fa-chart-line","TONE"=>$ac["accent"],
        "VALUE"=>$ser["ok"]?number_format($sum):"&mdash;","OFF"=>!$ser["ok"],"SPARK"=>ngs_col($ser,"req"),"FOOT"=>$foot));
}
function ngs_kpi_bandwidth(array $m):string{
    $ser=$m["series"]; $tone="#0284c7";
    $bytes=ngs_col($ser,"bytes");
    list($v,$u)=ngs_split_unit(ngs_bytes(array_sum($bytes)));
    $foot=""; $tip="";
    if(count($bytes)>0 && max($bytes)>0){
        $avg=array(600=>"ngs_avg_10m",3600=>"ngs_avg_1h",86400=>"ngs_avg_1d");
        $foot=ngs_tr("ngs_peak",array(ngs_bps(max($bytes)*8/$ser["step"])));
        $tip=$foot." - ".ngs_tr($avg[$ser["step"]]);
    }
    return ngs_tile(array("LABEL"=>"{ngs_bandwidth}","ICON"=>"fas fa-arrow-right-arrow-left","TONE"=>$tone,
        "VALUE"=>$ser["ok"]?$v:"&mdash;","UNIT"=>$ser["ok"]?$u:"","OFF"=>!$ser["ok"],"SPARK"=>$bytes,"FOOT"=>$foot,"TIP"=>$tip));
}
function ngs_kpi_connections(array $m):string{
    $c=$m["conn"]; $tone="#005447";
    $foot=$m["live"]?ngs_tr("ngs_conn_detail",array(number_format(intval($c["reading"] ?? 0)),
        number_format(intval($c["writing"] ?? 0)),number_format(intval($c["waiting"] ?? 0)))):"{ngs_live_unavailable}";
    return ngs_tile(array("LABEL"=>"{connections}","ICON"=>"fas fa-plug","TONE"=>$tone,
        "VALUE"=>$m["live"]?number_format(intval($c["active"] ?? 0)):"&mdash;","OFF"=>!$m["live"],"FOOT"=>$foot));
}
/** Couleur du taux d'erreurs : jugée sur les 5xx, les 4xx (robots, 404) étant le bruit normal d'un site exposé. */
function ngs_error_tone(float $req,float $e5):string{
    if($req<=0){return "#16a34a";}
    $r=$e5*100/$req;
    if($r>=5){return "#dc2626";}
    if($r>=1){return "#d97706";}
    return "#16a34a";
}
function ngs_kpi_errors(array $m):string{
    $ser=$m["series"];
    $req=array_sum(ngs_col($ser,"req")); $e4=array_sum(ngs_col($ser,"e4")); $e5=array_sum(ngs_col($ser,"e5"));
    $rate=array();
    foreach($ser["points"] as $p){ if($p["req"]>0){$rate[]=($p["e4"]+$p["e5"])/$p["req"];} }
    $tone=ngs_error_tone($req,$e5);
    $ico=$tone=="#16a34a"?"fas fa-circle-check":"fas fa-triangle-exclamation";
    if(!$ser["ok"] || $req<=0){$tone="#64748b"; $ico="fas fa-circle-exclamation";}
    list($v,$u)=ngs_split_unit(ngs_pct($e4+$e5,$req));
    return ngs_tile(array("LABEL"=>"{ngs_error_rate}","ICON"=>$ico,"TONE"=>$tone,
        "VALUE"=>$ser["ok"]&&$req>0?$v:"&mdash;","UNIT"=>$ser["ok"]&&$req>0?$u:"","OFF"=>!$ser["ok"]||$req<=0,"SPARK"=>$rate,
        "FOOT"=>$req>0?ngs_tr("ngs_error_detail",array(ngs_pct($e5,$req),ngs_pct($e4,$req))):""));
}
function ngs_kpi_cache(array $m):string{
    $ser=$m["series"]; $tone="#005447";
    $hit=array_sum(ngs_col($ser,"hit")); $miss=array_sum(ngs_col($ser,"miss"));
    $ratio=array();
    foreach($ser["points"] as $p){$t=$p["hit"]+$p["miss"]; if($t>0){$ratio[]=$p["hit"]/$t;}}
    if($hit+$miss<=0){
        return ngs_tile(array("LABEL"=>"{ngs_cache_hit}","ICON"=>"fas fa-bolt","TONE"=>$tone,
            "VALUE"=>$ser["ok"]?"{ngs_no_cache}":"&mdash;","OFF"=>true,"SPARK"=>array(),"FOOT"=>""));
    }
    list($v,$u)=ngs_split_unit(ngs_pct($hit,$hit+$miss));
    return ngs_tile(array("LABEL"=>"{ngs_cache_hit}","ICON"=>"fas fa-bolt","TONE"=>$tone,"VALUE"=>$v,"UNIT"=>$u,
        "SPARK"=>$ratio,"FOOT"=>ngs_tr("ngs_hit_miss",array(ngs_short($hit),ngs_short($miss)))));
}
function ngs_kpi_domains(array $m):string{
    $tone="#0891b2"; $c=0;
    foreach($m["zones"] as $name=>$z){
        if($name=="*" || !is_array($z)){continue;}
        if(intval($z["requestCounter"] ?? 0)>0){$c++;}
    }
    $foot=count($m["sites"])>0?ngs_tr("ngs_of_sites",array(number_format(count($m["sites"])))):"";
    return ngs_tile(array("LABEL"=>"{ngs_active_domains}","ICON"=>"fas fa-globe","TONE"=>$tone,
        "VALUE"=>$m["live"]?number_format($c):"&mdash;","OFF"=>!$m["live"],"FOOT"=>$foot));
}
function ngs_tiles(array $m):string{
    $t=array(ngs_kpi_requests($m),ngs_kpi_bandwidth($m),ngs_kpi_connections($m),
        ngs_kpi_errors($m),ngs_kpi_cache($m),ngs_kpi_domains($m));
    return "<div class='ngs-tiles'><div>".implode("</div><div>",$t)."</div></div>";
}

function ngs_step_token(string $period):string{
    if($period=="today"){return "{ngs_step_10m}";}
    if($period=="7d"){return "{ngs_step_hour}";}
    return "{ngs_step_day}";
}
function ngs_traffic_card(array $m):string{
    $ac=ngs_accents(); $ser=$m["series"];
    $h ="<div class='ak-card ngs-sec'><div class='ngs-sec-h'><div>";
    $h.="<div class='ngs-sec-t'><i class='fas fa-chart-area' aria-hidden='true'></i>{ngs_traffic}</div>";
    $h.="<div class='ngs-sec-s'>".ngs_step_token($m["period"])."</div></div>";
    $h.="<div class='ngs-legend'><span><i class='ngs-sw' style='background:{$ac["accent"]}'></i>{requests}</span>"
        ."<span><i class='ngs-sw' style='background:#0284c7'></i>{ngs_bandwidth}</span>"
        ."<span><i class='ngs-sw' style='background:#d97706'></i>4xx</span>"
        ."<span><i class='ngs-sw' style='background:#dc2626'></i>5xx</span></div></div>";
    if(!$ser["ok"]){
        return $h."<div class='ngs-empty'><i class='fas fa-database' aria-hidden='true'></i>{ngs_stats_db_error}</div></div>";
    }
    if(array_sum(ngs_col($ser,"req"))<=0){
        return $h."<div class='ngs-empty'><i class='fas fa-chart-area' aria-hidden='true'></i>{ngs_no_traffic}</div></div>";
    }
    $errs=array_sum(ngs_col($ser,"e4"))+array_sum(ngs_col($ser,"e5"));
    $h.="<div class='ngs-chart'><canvas id='ngs-traffic' role='img' aria-label='{ngs_traffic}'></canvas></div>";
    $h.="<div class='ngs-strip-h'><span>{ngs_errors_strip}</span><span>".ngs_tr("ngs_errors_total",array(number_format($errs)))."</span></div>";
    $h.="<div class='ngs-strip'><canvas id='ngs-errors' role='img' aria-label='{ngs_errors_strip}'></canvas></div>";
    $h.="</div>";
    $GLOBALS["NGS_TRAFFIC"]=true;
    return $h;
}
/** Script des courbes, émis hors du moteur de traduction (accolades du JS). */
function ngs_traffic_js(array $m):string{
    if(empty($GLOBALS["NGS_TRAFFIC"])){return "";}
    $ac=ngs_accents(); $ser=$m["series"];
    $x=array();
    foreach($ser["points"] as $p){$x[]=ngs_ts_label($p["ts"],$m["period"]);}
    $tpl=new template_admin();
    $flags=JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT;
    $cfg=array("x"=>$x,"req"=>ngs_col($ser,"req"),"bytes"=>ngs_col($ser,"bytes"),
        "e4"=>ngs_col($ser,"e4"),"e5"=>ngs_col($ser,"e5"),"c1"=>$ac["accent"],
        "a1"=>ngs_rgba($ac["accent"],".22"),"z1"=>ngs_rgba($ac["accent"],"0"),
        "l1"=>strip_tags($tpl->_ENGINE_parse_body("{requests}")),
        "l2"=>strip_tags($tpl->_ENGINE_parse_body("{ngs_bandwidth}")));
    $json=json_encode($cfg,$flags);
    return "<script>(function(){var C=$json;
if(typeof Chart==='undefined'){return;}
if(window._ngsCharts){for(var i=0;i<window._ngsCharts.length;i++){try{window._ngsCharts[i].destroy();}catch(e){}}}
window._ngsCharts=[];
var el=document.getElementById('ngs-traffic'),er=document.getElementById('ngs-errors');if(!el||!er){return;}
function fb(b){var u=['B','KB','MB','GB','TB'],i=0;b=Number(b)||0;while(b>=1024&&i<u.length-1){b/=1024;i++;}return (i?b.toFixed(1):b)+' '+u[i];}
function fnum(v){v=Number(v)||0;if(v>=1e6){return (v/1e6).toFixed(1)+'M';}if(v>=1e3){return (v/1e3).toFixed(1)+'k';}return String(v);}
function grad(a,z){return function(c){var r=c.chart.chartArea;if(!r){return a;}var g=c.chart.ctx.createLinearGradient(0,r.top,0,r.bottom);g.addColorStop(0,a);g.addColorStop(1,z);return g;};}
var calm=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
var tip={backgroundColor:'#0f172a',titleColor:'#e2e8f0',bodyColor:'#fff',padding:10,cornerRadius:6,boxPadding:4,usePointStyle:true};
var pad={left:0,right:0};
window._ngsCharts.push(new Chart(el,{type:'line',data:{labels:C.x,datasets:[
{label:C.l1,data:C.req,yAxisID:'y',borderColor:C.c1,backgroundColor:grad(C.a1,C.z1),fill:'origin',borderWidth:2.4,tension:.35,pointRadius:0,pointHoverRadius:5,pointHoverBackgroundColor:'#fff',pointHoverBorderWidth:2},
{label:C.l2,data:C.bytes,yAxisID:'y1',borderColor:'#0284c7',borderDash:[4,3],fill:false,borderWidth:1.8,tension:.35,pointRadius:0,pointHoverRadius:4}]},
options:{responsive:true,maintainAspectRatio:false,animation:calm?false:{duration:600,easing:'easeOutQuart'},interaction:{mode:'index',intersect:false},layout:{padding:pad},
plugins:{legend:{display:false},tooltip:Object.assign({},tip,{callbacks:{label:function(c){return ' '+c.dataset.label+': '+(c.datasetIndex===1?fb(c.parsed.y):Number(c.parsed.y).toLocaleString());}}})},
scales:{x:{grid:{display:false},border:{display:false},ticks:{color:'#94a3b8',maxRotation:0,autoSkip:true,maxTicksLimit:10}},
y:{beginAtZero:true,border:{display:false},grid:{color:'#f1f5f9'},afterFit:function(s){s.width=56;},ticks:{color:'#94a3b8',maxTicksLimit:5,callback:function(v){return fnum(v);}}},
y1:{beginAtZero:true,position:'right',border:{display:false},grid:{drawOnChartArea:false},afterFit:function(s){s.width=72;},ticks:{color:'#0284c7',maxTicksLimit:5,callback:function(v){return fb(v);}}}}}}));
window._ngsCharts.push(new Chart(er,{type:'bar',data:{labels:C.x,datasets:[
{label:'4xx',data:C.e4,backgroundColor:'#d97706',stack:'e',borderRadius:2,categoryPercentage:.9,barPercentage:.9},
{label:'5xx',data:C.e5,backgroundColor:'#dc2626',stack:'e',borderRadius:2,categoryPercentage:.9,barPercentage:.9}]},
options:{responsive:true,maintainAspectRatio:false,animation:calm?false:{duration:400},interaction:{mode:'index',intersect:false},layout:{padding:pad},
plugins:{legend:{display:false},tooltip:Object.assign({},tip,{callbacks:{label:function(c){return ' '+c.dataset.label+': '+Number(c.parsed.y).toLocaleString();}}})},
scales:{x:{display:false,stacked:true},y:{stacked:true,beginAtZero:true,border:{display:false},grid:{display:false},afterFit:function(s){s.width=56;},ticks:{display:false}},
y1:{position:'right',display:true,grid:{display:false},border:{display:false},afterFit:function(s){s.width=72;},ticks:{display:false}}}}}));
})();</script>";
}

function ngs_responses_card(array $m):string{
    $r=$m["responses"];
    $classes=array(
        array("2xx","{ngs_2xx}","#16a34a"),array("3xx","{ngs_3xx}","#2563eb"),
        array("4xx","{ngs_4xx}","#d97706"),array("5xx","{ngs_5xx}","#dc2626"),
        array("1xx","{ngs_1xx}","#9ca3af"));
    $total=0;
    foreach($classes as $c){$total+=intval($r[$c[0]] ?? 0);}
    $h ="<div class='ak-card ngs-sec'><div class='ngs-sec-h'><div>";
    $h.="<div class='ngs-sec-t'><i class='fas fa-chart-pie' aria-hidden='true'></i>{ngs_responses}</div>";
    $h.="<div class='ngs-sec-s'>{ngs_since_engine}".($m["uptime"]>0?" &middot; ".ngs_uptime($m["uptime"]):"")."</div></div></div>";
    if(!$m["live"] || $total<=0){
        return $h."<div class='ngs-empty'><i class='fas fa-chart-pie' aria-hidden='true'></i>".($m["live"]?"{no_data}":"{ngs_live_unavailable}")."</div></div>";
    }
    $segs=array(); $li=array();
    foreach($classes as $c){
        $v=intval($r[$c[0]] ?? 0);
        $segs[]=array($v,$c[2],$c[1]);
        $li[]="<li><i class='ngs-sw' style='background:{$c[2]}'></i><span class='t'>{$c[1]}</span><span class='n'>".number_format($v)."</span><span class='p'>".ngs_pct($v,$total)."</span></li>";
    }
    $h.="<div class='ngs-donut-row'>".ngs_ring($segs,ngs_short($total),"{ngs_total}")."<ul class='ngs-lgl'>".implode("",$li)."</ul></div></div>";
    return $h;
}

function ngs_cache_footer(array $m):string{
    $nginxsock=new socksngix(0);
    $dir=intval($nginxsock->GET_INFO("nginxCachesDir"));
    $page=CurrentPageName();
    $param=$m["webmaster"]?"<a class='ngs-a' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?section-cache-js=yes');\">{parameters}</a>":"";
    if($dir==1){
        $used=floatval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxGlobalCacheSize"));
        $mem=intval($nginxsock->GET_INFO("nginxCacheMem"))==1;
        $gb=intval($nginxsock->GET_INFO("nginxCacheSize"));
        if($gb<=0){$gb=2;}
        $cap=$gb*1073741824;
        $pct=$cap>0?min(100,$used*100/$cap):0;
        $title=ngs_tr($mem?"ngs_cache_mem":"ngs_cache_disk",array(ngs_bytes($used)));
        return "<div class='ngs-cfoot'><div class='ngs-cfoot-h'><span>$title</span><span>".ngs_tr("ngs_cache_of",array(number_format($pct,0)." %",$gb." GB"))." $param</span></div>"
            ."<div class='ak-bar ngs-bar' role='progressbar' aria-valuemin='0' aria-valuemax='100' aria-valuenow='".intval($pct)."'><span style='width:".sprintf('%.1f',$pct)."%'></span></div></div>";
    }
    $redis=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedis"))==1
        && intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedisService"))==1;
    if($redis){
        $st=redis_status();
        $txt=$st[0]?ngs_tr("ngs_cache_redis",array(number_format(intval($st[1])))):ngs_h($st[1]);
        return "<div class='ngs-cfoot'><div class='ngs-cfoot-h'><span>$txt</span><span>$param</span></div></div>";
    }
    return "<div class='ngs-cfoot'><div class='ngs-cfoot-h'><span><i class='fas fa-hard-drive' aria-hidden='true'></i> {ngs_cache_off}</span><span>$param</span></div></div>";
}
function ngs_cache_card(array $m):string{
    $r=$m["responses"];
    $outs=array(
        array("hit","{ngs_hit}",ngs_accents()["accent"]),array("miss","{ngs_miss}","#64748b"),
        array("bypass","{ngs_bypass}","#f59e0b"),array("expired","{ngs_expired}","#94a3b8"),
        array("stale","{ngs_stale}","#cbd5e1"),array("updating","{ngs_updating}","#80aaa3"),
        array("revalidated","{ngs_revalidated}","#67e8f9"),array("scarce","{ngs_scarce}","#fda4af"));
    $total=0;
    foreach($outs as $o){$total+=intval($r[$o[0]] ?? 0);}
    $h ="<div class='ak-card ngs-sec'><div class='ngs-sec-h'><div>";
    $h.="<div class='ngs-sec-t'><i class='fas fa-bolt' aria-hidden='true'></i>{ngs_cache_outcomes}</div>";
    $h.="<div class='ngs-sec-s'>{ngs_since_engine}</div></div></div>";
    if(!$m["live"] || $total<=0){
        $h.="<div class='ngs-empty'><i class='fas fa-bolt' aria-hidden='true'></i>".($m["live"]?"{ngs_no_cache_traffic}":"{ngs_live_unavailable}")."</div>";
        return $h.ngs_cache_footer($m)."</div>";
    }
    $segs=array(); $lg=array();
    foreach($outs as $o){
        $v=intval($r[$o[0]] ?? 0);
        $segs[]=array($v,$o[2],$o[1]);
        if($v<=0 && $o[0]!="hit" && $o[0]!="miss"){continue;}
        $lg[]="<div><i class='ngs-sw' style='background:{$o[2]};border-radius:9999px'></i>{$o[1]}: <b>".number_format($v)."</b></div>";
    }
    $hit=intval($r["hit"] ?? 0); $miss=intval($r["miss"] ?? 0);
    $center=($hit+$miss)>0?number_format($hit*100/($hit+$miss),1)." %":"&mdash;";
    $h.="<div class='ngs-donut-row'>".ngs_ring($segs,$center,"{ngs_hit_ratio}")."<div class='ngs-lgg'>".implode("",$lg)."</div></div>";
    return $h.ngs_cache_footer($m)."</div>";
}

/** Classement d'un critère : 10 premiers + « Other », chaque barre en part du total. */
function ngs_rank_list(array $rows,string $k,string $metric,bool $hidden):string{
    usort($rows,function($a,$b) use($k){ if($a[$k]==$b[$k]){return 0;} return $a[$k]<$b[$k]?1:-1; });
    $total=0;
    foreach($rows as $r){$total+=$r[$k];}
    $hid=$hidden?" hidden":"";
    if($total<=0){
        return "<div id='ngs-rank-$metric' role='tabpanel'$hid><div class='ngs-empty'><i class='fas fa-chart-bar' aria-hidden='true'></i>{no_data}</div></div>";
    }
    $li=array(); $rest=0; $i=0;
    foreach($rows as $r){
        if($r[$k]<=0){continue;}
        $i++;
        if($i>10){$rest+=$r[$k]; continue;}
        $li[]=ngs_rank_row(ngs_h($r["name"]),$r[$k],$total,$k,false);
    }
    if($rest>0){$li[]=ngs_rank_row("{ngs_other}",$rest,$total,$k,true);}
    return "<div id='ngs-rank-$metric' role='tabpanel'$hid><ul class='ngs-rank'>".implode("",$li)."</ul></div>";
}
function ngs_rank_row(string $name,float $v,float $total,string $k,bool $other):string{
    $pct=$total>0?$v*100/$total:0;
    $val=$k=="bytes"?ngs_bytes($v):number_format($v);
    return "<li".($other?" class='other'":"")."><span class='nm' title=\"".ngs_h(strip_tags($name))."\">$name</span>"
        ."<div class='ak-bar ngs-bar'><span style='width:".sprintf('%.2f',max(0.6,$pct))."%'></span></div>"
        ."<span class='v'>$val</span><span class='p'>".ngs_pct($v,$total)."</span></li>";
}
function ngs_top_card(array $m):string{
    $rows=array();
    foreach($m["ranking"] as $r){ if($r["id"]>0){$rows[]=$r;} }
    $h ="<div class='ak-card ngs-sec'><div class='ngs-sec-h'><div>";
    $h.="<div class='ngs-sec-t'><i class='fas fa-ranking-star' aria-hidden='true'></i>{ngs_top_sites}</div>";
    $h.="<div class='ngs-sec-s'>{ngs_top_sub}</div></div>";
    $h.="<div class='ak-seg' role='tablist' aria-label='{ngs_top_sites}'>"
        ."<button type='button' class='ak-seg-b on' role='tab' aria-selected='true' aria-controls='ngs-rank-requests' OnClick=\"NgsMetric(this,'requests');\">{requests}</button>"
        ."<button type='button' class='ak-seg-b' role='tab' aria-selected='false' aria-controls='ngs-rank-bandwidth' OnClick=\"NgsMetric(this,'bandwidth');\">{ngs_bandwidth}</button>"
        ."<button type='button' class='ak-seg-b' role='tab' aria-selected='false' aria-controls='ngs-rank-errors' OnClick=\"NgsMetric(this,'errors');\">{ngs_errors}</button></div></div>";
    if(!$m["series"]["ok"]){
        return $h."<div class='ngs-empty'><i class='fas fa-database' aria-hidden='true'></i>{ngs_stats_db_error}</div></div>";
    }
    $h.=ngs_rank_list($rows,"req","requests",false);
    $h.=ngs_rank_list($rows,"bytes","bandwidth",true);
    $h.=ngs_rank_list($rows,"err","errors",true);
    return $h."</div>";
}

/** Serveurs amont à plat, Down d'abord, puis Backup, puis par requêtes. */
function ngs_backend_rows(array $m):array{
    $rows=array();
    foreach($m["upstreams"] as $group=>$list){
        if(!is_array($list)){continue;}
        foreach($list as $s){
            if(!is_array($s)){continue;}
            $state="up";
            if(!empty($s["down"])){$state="down";}elseif(!empty($s["backup"])){$state="backup";}
            $rows[]=array("server"=>(string)($s["server"] ?? ""),"group"=>(string)$group,"state"=>$state,
                "weight"=>intval($s["weight"] ?? 0),"req"=>intval($s["requestCounter"] ?? 0),
                "rt"=>intval($s["responseMsec"] ?? 0),"e5"=>intval($s["responses"]["5xx"] ?? 0));
        }
    }
    $rank=array("down"=>0,"backup"=>1,"up"=>2);
    usort($rows,function($a,$b) use($rank){
        if($rank[$a["state"]]!=$rank[$b["state"]]){return $rank[$a["state"]]-$rank[$b["state"]];}
        if($a["req"]==$b["req"]){return 0;}
        return $a["req"]<$b["req"]?1:-1;
    });
    return $rows;
}
function ngs_backends_card(array $m):string{
    $rows=ngs_backend_rows($m);
    $down=0;
    foreach($rows as $r){if($r["state"]=="down"){$down++;}}
    $badge=$down>0?" <span class='ak-pill ak-err'><i class='fas fa-circle-xmark' aria-hidden='true'></i>".ngs_tr("ngs_down_count",array($down))."</span>":"";
    $h ="<div class='ak-card ngs-sec'><div class='ngs-sec-h'><div>";
    $h.="<div class='ngs-sec-t'><i class='fas fa-server' aria-hidden='true'></i>{ngs_backends}$badge</div>";
    $h.="<div class='ngs-sec-s'>{ngs_backends_sub}</div></div></div>";
    if(count($rows)==0){
        return $h."<div class='ngs-empty'><i class='fas fa-server' aria-hidden='true'></i>".($m["live"]?"{ngs_no_upstream}":"{ngs_live_unavailable}")."</div></div>";
    }
    $st=array("up"=>array("{ngs_up}","fas fa-circle-check"),"down"=>array("{ngs_down}","fas fa-circle-xmark"),
        "backup"=>array("{ngs_backup}","fas fa-life-ring"));
    $h.="<div style='overflow-x:auto'><table class='ngs-tbl'><thead><tr><th>{ngs_server}</th><th>{ngs_group}</th><th>{ngs_status}</th>"
        ."<th class='r'>{ngs_weight}</th><th class='r'>{requests}</th><th class='r'>{ngs_avg_rt}</th><th class='r'>5xx</th></tr></thead><tbody>";
    $max=12;
    foreach(array_slice($rows,0,$max) as $r){
        $g=$r["group"]=="::nogroups"?"{ngs_direct}":ngs_h($r["group"]);
        $rt=($r["req"]>0 && !($r["state"]=="down" && $r["rt"]==0))?number_format($r["rt"])." ms":"&ndash;";
        $sv=ngs_h($r["server"]);
        $h.="<tr class='{$r["state"]}'><td class='mono ell' title=\"$sv\">$sv</td><td class='ell' title=\"$g\">$g</td>"
            ."<td><span class='ngs-st {$r["state"]}'><i class='{$st[$r["state"]][1]}' aria-hidden='true'></i>{$st[$r["state"]][0]}</span></td>"
            ."<td class='r'>{$r["weight"]}</td><td class='r'>".number_format($r["req"])."</td><td class='r'>$rt</td>"
            ."<td class='r'".($r["e5"]>0?" style='color:#b91c1c;font-weight:600'":"").">".number_format($r["e5"])."</td></tr>";
    }
    $h.="</tbody></table></div>";
    if(count($rows)>$max){$h.="<div class='ngs-note'>".ngs_tr("ngs_more_rows",array(number_format(count($rows)-$max)))."</div>";}
    return $h."</div>";
}

function ngs_memory_card(array $m):string{
    $sz=$m["shared"]; $c=$m["conn"];
    $h ="<div class='ak-card ngs-sec'><div class='ngs-sec-h'><div>";
    $h.="<div class='ngs-sec-t'><i class='fas fa-memory' aria-hidden='true'></i>{ngs_memory}</div>";
    $h.="<div class='ngs-sec-s'>{ngs_memory_sub}</div></div>";
    if(!empty($sz["name"])){$h.="<span class='ak-pill ak-off'>".ngs_h($sz["name"])."</span>";}
    $h.="</div>";
    if(!$m["live"]){
        return $h."<div class='ngs-empty'><i class='fas fa-memory' aria-hidden='true'></i>{ngs_live_unavailable}</div></div>";
    }
    $maxS=floatval($sz["maxSize"] ?? 0); $used=floatval($sz["usedSize"] ?? 0);
    if($maxS>0){
        $pct=$used*100/$maxS;
        $tone=$pct>=90?"#dc2626":($pct>=75?"#d97706":ngs_accents()["accent"]);
        $h.="<div class='ngs-mem'>".ngs_ring(array(array($used,$tone,"{ngs_used}"),array(max(0,$maxS-$used),"#f1f5f9","")),
            number_format($pct,0)." %","{ngs_used}",true);
        $h.="<div><div class='ngs-mem-v'>".ngs_bytes($used)." <small>".ngs_tr("ngs_mem_of",array(ngs_bytes($maxS)))."</small></div>";
        $h.="<div class='ngs-note'>".ngs_tr("ngs_mem_nodes",array(number_format(intval($sz["usedNode"] ?? 0))))."</div></div></div>";
    }
    $acc=intval($c["accepted"] ?? 0); $hand=intval($c["handled"] ?? 0); $drop=max(0,$acc-$hand);
    $h.="<div class='ngs-kv'><div><span>{ngs_accepted}</span><b>".number_format($acc)."</b></div>"
        ."<div><span>{ngs_handled}</span><b>".number_format($hand)."</b></div>"
        ."<div class='".($drop>0?"bad":"good")."'><span>{ngs_dropped}</span><b><i class='fas ".($drop>0?"fa-triangle-exclamation":"fa-check")."' aria-hidden='true'></i> ".number_format($drop)."</b></div></div>";
    return $h."</div>";
}

/** Carte de module : ICON, TITLE, BADGE (array texte, classe), DETAIL, ACTIONS (array texte, js, danger). */
function ngs_module_card(array $c):string{
    $off=in_array($c["BADGE"][1],array("ak-off"));
    $acts=array();
    foreach($c["ACTIONS"] as $a){
        $acts[]="<a href=\"javascript:blur();\"".(!empty($a[2])?" class='dz'":"")." OnClick=\"{$a[1]}\">{$a[0]}</a>";
    }
    $h ="<div class='ak-card ngs-mod".($off?" off":"")."'><div class='ngs-mod-h'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='{$c["ICON"]}' aria-hidden='true'></i></span>";
    $h.="<span class='ak-pill {$c["BADGE"][1]}'>{$c["BADGE"][0]}</span></div>";
    $h.="<div><div class='ngs-mod-t'>{$c["TITLE"]}</div><div class='ngs-mod-d'>".($c["DETAIL"] ?? "")."</div></div>";
    if(count($acts)>0){$h.="<div class='ngs-mod-a'>".implode("",$acts)."</div>";}
    return $h."</div>";
}
function ngs_mod_firewall(array $m):string{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $refresh="NgsRefresh();Loadjs('fw.progress.php?refresh-menus=yes');";
    $acts=array();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginxFW"))==0){
        if($m["webmaster"]){$acts[]=array("{ngs_install}",$tpl->framework_buildjs("nginx:/reverse-proxy/firewall/install","nginxfw.progress","nginxfw.log","progress-nginx-restart",$refresh));}
        return ngs_module_card(array("ICON"=>"fas fa-shield-halved","TITLE"=>"{firewall_for_web}",
            "BADGE"=>array("{disabled}","ak-off"),"DETAIL"=>"","ACTIONS"=>$acts));
    }
    $stopped=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NGINX_FW_STOPPED"))==1;
    if($m["webmaster"]){
        if($stopped){
            $acts[]=array("{start}",$tpl->framework_buildjs("nginx:/reverse-proxy/firewall/start","nginxfw.start.progress","nginxfw.start.txt","progress-nginx-restart","NgsRefresh();"));
        }else{
            $acts[]=array("{stop}",$tpl->framework_buildjs("nginx:/reverse-proxy/firewall/stop","nginxfw.stop.progress","nginxfw.stop.txt","progress-nginx-restart","NgsRefresh();"));
        }
        $acts[]=array("{ngs_uninstall}",$tpl->framework_buildjs("nginx:/reverse-proxy/firewall/uninstall","nginxfw.progress","nginxfw.log","progress-nginx-restart",$refresh),true);
    }
    if($stopped){
        return ngs_module_card(array("ICON"=>"fas fa-shield-halved","TITLE"=>"{firewall_for_web}",
            "BADGE"=>array("{inactive2}","ak-off"),"DETAIL"=>"","ACTIONS"=>$acts));
    }
    $n=NGINX_FW_IPSET_COUNT();
    return ngs_module_card(array("ICON"=>"fas fa-shield-halved","TITLE"=>"{firewall_for_web}",
        "BADGE"=>array("{active2}","ak-ok"),"DETAIL"=>$n>0?ngs_tr("ngs_rules",array(number_format($n))):"","ACTIONS"=>$acts));
}
function ngs_mod_geoip(array $m):string{
    $page=CurrentPageName();
    $help=array("{online_help}","s_PopUp('https://wiki.articatech.com/reverse-proxy/security/limit-access-by-countries','1024','800')");
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"))==0){
        return ngs_module_card(array("ICON"=>"fas fa-earth-europe","TITLE"=>"{APP_GEOIP}",
            "BADGE"=>array("{not_installed}","ak-off"),"DETAIL"=>"","ACTIONS"=>array($help)));
    }
    $acts=array();
    if($m["webmaster"]){$acts[]=array("{ngs_reconfigure}","Loadjs('$page?reconfigure-js=yes')");}
    $acts[]=$help;
    if(!is_file("/etc/nginx/maps.d/00_GeoIP.map")){
        return ngs_module_card(array("ICON"=>"fas fa-earth-europe","TITLE"=>"{APP_GEOIP}",
            "BADGE"=>array("{missing_databases}","ak-warn"),"DETAIL"=>"","ACTIONS"=>$acts));
    }
    return ngs_module_card(array("ICON"=>"fas fa-earth-europe","TITLE"=>"{APP_GEOIP}",
        "BADGE"=>array("{active2}","ak-ok"),"DETAIL"=>"","ACTIONS"=>$acts));
}
function ngs_mod_vitrification(array $m):string{
    $page=CurrentPageName();
    $help=array("{online_help}","s_PopUp('https://wiki.articatech.com/reverse-proxy/vitrification','1024','800')");
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Vitrification"))==0){
        $acts=array();
        if($m["webmaster"]){$acts[]=array("{enable_feature}","Loadjs('$page?vitrification-enable=yes')");}
        $acts[]=$help;
        return ngs_module_card(array("ICON"=>"fas fa-wine-glass","TITLE"=>"{vitrification}",
            "BADGE"=>array("{disabled}","ak-off"),"DETAIL"=>"","ACTIONS"=>$acts));
    }
    // /vitrification/all rend Data comme une CHAINE JSON (vu sur 192.168.90.88).
    $array=ngs_rest_nginx("/vitrification/all");
    $data=$array["Data"] ?? null;
    if(is_string($data)){$data=json_decode($data,true);}
    $n=is_array($data)?count($data):0;
    $acts=array();
    if($m["webmaster"]){$acts[]=array("{disable_feature}","Loadjs('$page?vitrification-disable=yes')",true);}
    $acts[]=$help;
    return ngs_module_card(array("ICON"=>"fas fa-wine-glass","TITLE"=>"{vitrification}",
        "BADGE"=>array("{active2}","ak-ok"),"DETAIL"=>ngs_tr("ngs_sites_n",array(number_format($n))),"ACTIONS"=>$acts));
}
function ngs_mod_decisionip(array $m):string{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $help=array("{online_help}","s_PopUp('https://wiki.articatech.com/en/reverse-proxy/security/decisionip','1024','800')");
    $acts=array();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDecisionIP"))==0){
        if($m["webmaster"]){
            $acts[]=array("{enable_feature}",$tpl->framework_buildjs("/decisionip/install","decisionip.install.progress",
                "decisionip.install.log","progress-nginx-restart","document.location.href='/decisionip'"));
        }
        $acts[]=$help;
        return ngs_module_card(array("ICON"=>"fas fa-user-shield","TITLE"=>"DecisionIP",
            "BADGE"=>array("{inactive2}","ak-off"),"DETAIL"=>"","ACTIONS"=>$acts));
    }
    if($m["webmaster"]){$acts[]=array("{disable_feature}","Loadjs('$page?uninstall-decisionip=yes')",true);}
    $acts[]=$help;
    if(!isKey()){
        return ngs_module_card(array("ICON"=>"fas fa-user-shield","TITLE"=>"DecisionIP",
            "BADGE"=>array("{ngs_register}","ak-warn"),"DETAIL"=>"","ACTIONS"=>$acts));
    }
    return ngs_module_card(array("ICON"=>"fas fa-user-shield","TITLE"=>"DecisionIP",
        "BADGE"=>array("{active2}","ak-ok"),"DETAIL"=>"","ACTIONS"=>$acts));
}
function ngs_mod_adminclients(array $m):string{
    $page=CurrentPageName();
    $help=array("{online_help}","s_PopUp('https://wiki.articatech.com/en/reverse-proxy/admin-mtls','1024','800')");
    $on=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ReverseAppClients"))==1;
    $acts=array();
    if($m["webmaster"]){
        $acts[]=$on?array("{disable_feature}","Loadjs('$page?mtls-uninstall=yes');",true):array("{enable_feature}","Loadjs('$page?mtls-install=yes');");
    }
    $acts[]=$help;
    return ngs_module_card(array("ICON"=>"fas fa-user-gear","TITLE"=>"{admin_clients}",
        "BADGE"=>$on?array("{active2}","ak-ok"):array("{inactive2}","ak-off"),"DETAIL"=>"","ACTIONS"=>$acts));
}
function ngs_modules(array $m):string{
    return "<div class='ngs-mods'>".ngs_mod_firewall($m).ngs_mod_geoip($m).ngs_mod_vitrification($m)
        .ngs_mod_decisionip($m).ngs_mod_adminclients($m)."</div>";
}
function ngs_update_button(array $m):string{
    if($m["docker"] || !$m["webmaster"]){return "";}
    $page=CurrentPageName();
    return "<div class='ngs-foot-bar'><button type='button' class='ak-btn' OnClick=\"Loadjs('$page?updatesoft-js=yes')\">"
        ."<i class='fas fa-compact-disc' aria-hidden='true'></i>{APP_NGINX}: {update2}</button></div>";
}

/** ?ngs-body=yes : tout le tableau de bord, rechargé par le minuteur et par la période. */
function ngs_body():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $period=ngs_period($_GET["period"] ?? "today");
    $m=ngs_model($period);
    $h=array();
    $h[]="<div class='ngs-main'>";
    $h[]=ngs_emergency($m);
    $h[]=ngs_banner($m);
    $h[]=ngs_tiles($m);
    $h[]=ngs_traffic_card($m);
    $h[]="<div class='ngs-duo'>".ngs_responses_card($m).ngs_cache_card($m)."</div>";
    $h[]=ngs_top_card($m);
    $h[]="<div class='ngs-duo ngs-duo64'>".ngs_backends_card($m).ngs_memory_card($m)."</div>";
    $h[]=ngs_modules($m);
    $h[]=ngs_update_button($m);
    $h[]="</div>";
    // Les tuiles de service sont déjà parsées (CSS incluse) : hors _ENGINE_parse_body().
    $aside=ngs_services_aside($m);
    $main=$tpl->_ENGINE_parse_body(implode("\n",$h));
    if($aside!==""){$main="<div class='ngs-layout'>$aside$main</div>";}
    echo ngs_style()."<div class='ngs-wrap ak'>$main</div>".ngs_traffic_js($m);
    return true;
}

function table2():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $period=ngs_period($_GET["period"] ?? "today");
    $GLOBAL_NGINX_REQUESTS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GLOBAL_NGINX_REQUESTS"));
    $APP_NGINX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_VERSION");

    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{APP_NGINX} v$APP_NGINX_VERSION &raquo;&raquo; {service_status}";
    if(is_file("/etc/artica-postfix/AS_DOCKER_SERVICE")){
        $TINY_ARRAY["TITLE"]="{APP_NGINX} &raquo;&raquo; {parameters}";
    }
    $topbuttons=array();
    if($GLOBAL_NGINX_REQUESTS>0){
        $topbuttons[]=array("Loadjs('fw.nginx.metrics.php?serviceid=0')","fad fa-cloud-showers-heavy",$tpl->FormatNumber($GLOBAL_NGINX_REQUESTS)."&nbsp;{requests}");
    }
    $topbuttons[]=array("Loadjs('fw.nginx.license.php')",ico_certificate,"{ngs_license}");
    $topbuttons[]=array("Loadjs('fw.support-tool.php?support-tool=yes&reverse=yes')",ico_support,"SupportTool");
    $topbuttons[]=array("Loadjs('$page?reconfigure-js=yes')",ico_save,"{ngs_reconfigure}");
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"))==0){
        $topbuttons[]=array("Loadjs('$page?uninstall-service=yes')",ico_trash,"{ngs_uninstall}");
    }
    // La période gouverne les tuiles, la courbe et le classement : elle va dans l'en-tête.
    $fields=array();
    $fields[]=array("TYPE"=>"segment","ID"=>"ngs-period","LABEL"=>"{period}","VALUE"=>$period,
        "OPTIONS"=>ngs_periods(),"JS"=>"NgsPeriod");
    $fields[]=array("TYPE"=>"action","LABEL"=>"{refresh}","ICON"=>"fas fa-sync-alt","JS"=>"NgsRefresh");
    $TINY_ARRAY["ICO"]="fas fa-globe-africa";
    $TINY_ARRAY["EXPL"]="{enable_nginx_text}";
    $TINY_ARRAY["URL"]="nginx-status";
    $TINY_ARRAY["BUTTONS"]=siem_tiny_filters($fields,$tpl->table_buttons($topbuttons));
    $TINY_ARRAY["DANGER"]=false;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $keys=json_encode(array_keys(ngs_periods()));
    $h=array();
    $h[]="<div id='ngs-host'></div>";
    $h[]="<script>";
    $h[]="var _ngsPeriod='$period';";
    $h[]="function NgsPeriod(p){var i=$keys.indexOf(p);if(i<0){return;}_ngsPeriod=p;\$('#ngs-period a').removeClass('on').eq(i).addClass('on');LoadAjax('ngs-host','$page?ngs-body=yes&period='+_ngsPeriod);}";
    $h[]="function NgsRefresh(){if(!document.getElementById('ngs-host')){return;}LoadAjaxSilent('ngs-host','$page?ngs-body=yes&period='+_ngsPeriod);}";
    $h[]="function NgsMetric(btn,k){var tl=btn.parentNode;var b=tl.querySelectorAll('button');for(var i=0;i<b.length;i++){b[i].setAttribute('aria-selected',b[i]===btn?'true':'false');b[i].className='ak-seg-b'+(b[i]===btn?' on':'');}";
    $h[]="  var ks=['requests','bandwidth','errors'];for(var j=0;j<ks.length;j++){var p=document.getElementById('ngs-rank-'+ks[j]);if(p){p.hidden=(ks[j]!==k);}}}";
    // Un seul minuteur ; il saute quand une modale ou une alerte est ouverte et s'arrête quand la page est quittée.
    $h[]="if(window.NgsTimer){clearInterval(window.NgsTimer);}";
    $h[]="window.NgsTimer=setInterval(function(){if(!document.getElementById('ngs-host')){clearInterval(window.NgsTimer);window.NgsTimer=null;return;}";
    $h[]="  if(document.querySelector('.modal.in,.modal.show,.sweet-alert.visible,.swal2-container')){return;}NgsRefresh();},60000);";
    $h[]="LoadAjax('ngs-host','$page?ngs-body=yes&period='+_ngsPeriod);";
    $h[]=$jstiny;
    $h[]="</script>";
    echo siem_tiny_filters_style().$tpl->_ENGINE_parse_body(implode("\n",$h));
    return true;
}
