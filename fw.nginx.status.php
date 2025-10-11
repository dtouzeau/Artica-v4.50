<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");

if(isset($_GET["chart-top-sites-hours"])){chart_top_sites_hours();exit;}
if(isset($_GET["chart-top-sites-days"])){chart_top_sites_days();exit;}

if(isset($_POST["nginxCachesDir"])){section_cache_save();exit;}
if(isset($_GET["uninstall-service"])){uninstall_service_js();exit;}
if(isset($_POST["uninstall-service"])){uninstall_service_confirm();exit;}
if(isset($_GET["pagespeed-enable"])){pagespeed_enable();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table2"])){table2();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["parameters1"])){parameters1();exit;}
if(isset($_POST["NginxProxyProtocol"])){Save();exit;}
if(isset($_POST["PrometheusNginxInterface"])){SavePrometheus();exit;}
if(isset($_POST["NginxCacheRedis"])){Save();exit;}
if(isset($_POST["NginxDebugMode"])){Save();exit;}
if(isset($_GET["SmoothieChartNginxRTIntervall"])){SmoothieChartNginxRTIntervall();exit;}

if(isset($_GET["updatesoft-js"])){updatesoft_js();exit;}
if(isset($_GET["updatesoft-popup"])){updatesoft_popup();exit;}
if(isset($_GET["file-uploaded"])){updatesoft_uploaded();exit;}
if(isset($_GET["nginx-status-line"])){nginx_status_line();exit;}
if(isset($_GET["nginx-status"])){nginx_status();exit;}
if(isset($_GET["nginx-status-firewall"])){nginx_status_firewall();exit;}
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
if(isset($_GET["graphs-line-total"])){graphs_line_total();exit;}
if(isset($_GET["graphs-line-total-hour"])){graphs_line_total_hour();exit;}
if(isset($_GET["graphs-pie-cache"])){graphs_pie_total_cache();exit;}
if(isset($_GET["reconfigure-js"])){reconfigure_js();exit;}

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

    $html[]="LoadAjax('web-firewall-status','$page?nginx-status-firewall=yes');";
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
        "LoadAjax('nginx-status-flat','$page?table2=yes');" . section_js_form(), $security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function section_maxdaystats_save():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NginxMaxDaysStats", $_POST["NginxMaxDaysStats"]);
    return admin_tracks("Set Max statistics retention to {$_POST["NginxMaxDaysStats"]} days for reverse-proxy");
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
        "LoadAjax('nginx-status-flat','$page?table2=yes');" . section_js_form(), $security);
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
        "LoadAjax('nginx-status-flat','$page?table2=yes');".section_js_form(),$security);
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
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $file=$_GET["file-uploaded"];
    $fullpath="/usr/share/artica-postfix/ressources/conf/upload/$file";

    if(!preg_match("#\.gz$#",$file)){
            $tpl->js_error("$file: {incorrect_package}");
            @unlink($fullpath);
            return false;
    }

    $jsafeter="dialogInstance1.close();LoadAjax('table-nginx','$page?tabs=yes');";
    $file=urlencode($file);
    admin_tracks("Reverse-Proxy Software with $file package as been successfully uploaded");

    echo $tpl->framework_buildjs("nginx.php?upload-package=$file", "nginx.upgrade.progress", "nginx.upgrade.log",  "appnginx-upload",$jsafeter
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
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $nginxsock=new socksngix(0);
    $NginxProxyProtocol=$nginxsock->GET_INFO("NginxProxyProtocol");
    $NginxLBIpaddr=$nginxsock->GET_INFO("set_real_ip_from");
    $NginxDebugMode=intval($nginxsock->GET_INFO("NginxDebugMode"));
    $NginxProxyStorePath=$nginxsock->GET_INFO("NginxProxyStorePath");
    if($NginxProxyStorePath==null){$NginxProxyStorePath="/home/nginx";}
    $NginxMaxLogFileSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxMaxLogFileSize"));
    if($NginxMaxLogFileSize==0){$NginxMaxLogFileSize=300;}
    $NginxRealTimeLogSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxRealTimeLogSize"));
    if($NginxRealTimeLogSize==0){$NginxRealTimeLogSize=50;}
    $NginxDisableFrontEndSLA=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxDisableFrontEndSLA"));

    $NginxMaxDaysStats=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxMaxDaysStats"));
    if($NginxMaxDaysStats==0){
        $NginxMaxDaysStats=180;
    }



    $tpl->table_form_field_js("Loadjs('$page?section-lb-js=yes')","AsWebMaster");
    if($NginxProxyProtocol==0){
        $tpl->table_form_field_bool("Load-Balancer",0,ico_load_balancer);
    }else {
        $tpl->table_form_field_text("Load-Balancer", "{proxy_protocol} [$NginxLBIpaddr]", ico_load_balancer);
    }
    $tpl->table_form_field_js("Loadjs('$page?section-options-js=yes')","AsWebMaster");
    $tpl->table_form_field_bool("{debug}",$NginxDebugMode,ico_bug);
    $tpl->table_form_field_text("{working_directory}", $NginxProxyStorePath, ico_folder);
    $tpl->table_form_field_text("{ArticaMaxLogsSize}", "{$NginxMaxLogFileSize}MB", ico_filesize);
    $tpl->table_form_field_text("{maximum_realtime_size}", "{$NginxRealTimeLogSize}MB", ico_filesize);


    $nginxsock=new socksngix(0);
    $NginxCacheRedis=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedis"));
    $APP_NGINX_SRCACHE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_SRCACHE"));
    $NginxCacheRedisHost=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisHost"));
    $NginxCacheRedisPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisPort"));
    $NginxCacheRedisLocal=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisLocal"));
    if($NginxCacheRedisPort==0){$NginxCacheRedisPort=6379;}
    $nginxCachesDir=intval($nginxsock->GET_INFO("nginxCachesDir"));
    $nginxCacheSize=intval($nginxsock->GET_INFO("nginxCacheSize"));

    if($nginxCacheSize==0){$nginxCacheSize=2;}
    $nginxCacheSizeBytes=$nginxCacheSize*1073741824;




     $tpl->table_form_field_js("Loadjs('$page?section-sla-frontend-js=yes')","AsWebMaster");
     if($NginxDisableFrontEndSLA==1){
         $tpl->table_form_field_bool("{NginxDisableFrontEndSLA}",0,ico_list);
     }else{
         $tpl->table_form_field_bool("{NginxDisableFrontEndSLA}",1,ico_list);
     }


    $tpl->table_form_field_js("Loadjs('$page?section-cache-js=yes')","AsWebMaster");
    if($nginxCachesDir==0){
        $tpl->table_form_field_text("{cache_on_disk}","{inactive2}",ico_hd);
    }else{
        $tpl->table_form_field_text("{cache_on_disk}","{active2} ".FormatBytes($nginxCacheSizeBytes/1024),ico_hd);
    }


    if($APP_NGINX_SRCACHE==0){
        $tpl->table_form_field_js(null);
        $tpl->table_form_field_text("{caching_using_redis}","{missing_module}",ico_mem);
    }else{
        $EnableRedisServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedisServer"));
        $tpl->table_form_field_js("Loadjs('$page?section-redis-js=yes')","AsWebMaster");
        if($EnableRedisServer==0){
            $tpl->table_form_field_bool("{caching_using_redis}",0,ico_mem);
        }else{
            if($NginxCacheRedis==1) {

                if ($NginxCacheRedisLocal == 1) {
                    $NginxCacheRedis_text = "{use_local_service}";
                } else {
                    $NginxCacheRedis_text = "$NginxCacheRedisHost:$NginxCacheRedisPort";
                }
            }else{
                $NginxCacheRedis_text="{inactive2}";
            }
            $tpl->table_form_field_text("{caching_using_redis}",$NginxCacheRedis_text,ico_hd);
        }
    }
    $tpl->table_form_field_js("Loadjs('$page?maxdaystats-js=yes')","AsWebMaster");
    $tpl->table_form_field_text("{statistics_retention}","$NginxMaxDaysStats {days}",ico_timeout);



    $EnablePrometheusNginx=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePrometheusNginx");
    $tpl->table_form_field_js("Loadjs('$page?prometheus-js=yes')","AsWebMaster");
    if($EnablePrometheusNginx==0){
        $tpl->table_form_field_bool("Prometheus Exporter",0,ico_chart_line);
    }else{
        $PrometheusNginxInterface = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrometheusNginxInterface"));
        if($PrometheusNginxInterface==""){$PrometheusNginxInterface="{all}";}
        $PrometheusNginxPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrometheusNginxPort"));
        $PrometheusNginxNameSpace = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrometheusNginxNameSpace"));
        if (strlen($PrometheusNginxNameSpace) < 3) {
            $PrometheusNginxNameSpace = "nginx";
        }
        if ($PrometheusNginxPort == 0) {
            $PrometheusNginxPort = 9913;
        }
        $tpl->table_form_field_text("Prometheus Exporter","$PrometheusNginxInterface:$PrometheusNginxPort@$PrometheusNginxNameSpace",ico_chart_line);
    }


    echo $tpl->table_form_compile();
    return true;

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
    $EnableRedisServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedisServer"));

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
    return true;
}

function docker_status():bool{

    include_once(dirname(__FILE__).'/ressources/class.docker.inc');
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $Main=unserialize(@file_get_contents("Docker/info.json"));
    $dock=new dockerd();

    $PermimeterID=$Main["perimeter"];
    $permietername=$Main["permietername"];
    $groupname=$Main["groupname"];
    $MaxInstances=$Main["MaxInstances"];
    $groupid=$Main["groupid"];
    $tag="com.articatech.artica.scope.$PermimeterID.backend.$groupid";
    $array=$dock->ContainersListByTag($tag);
    $CountOfInstances=count($array);

    if($CountOfInstances==0){
        echo $tpl->widget_grey("{instances}","0/$MaxInstances");
        return true;
    }
    echo $tpl->widget_vert("{instances}","$CountOfInstances/$MaxInstances");
    return true;



}
function PrometheusStatus():string{
    $sock=new sockets();
    $tpl=new template_admin();
    $ini=new Bs_IniHandler();
    $data=$sock->REST_API("/prometheus/nginx/status");
    $json=json_decode($data);

    $service_restart=$tpl->framework_buildjs("/prometheus/nginx/restart",
        "vts-exporter.progress","vts-exporter.progress.txt",
        "progress-nginx-restart");

    if (json_last_error()> JSON_ERROR_NONE) {
        return $tpl->widget_rouge("{error}",json_last_error_msg());
    }

    if(!$json->Status){
        echo $tpl->widget_rouge("{error}","Framework return false!");
        return false;
    }
    $ini->loadString($json->Info);

    return $tpl->SERVICE_STATUS($ini, "APP_VTS_EXPORTER",$service_restart);

}

function ServiceStatus():string{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ini=new Bs_IniHandler();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/service/status"));
    $page=CurrentPageName();

    $service_restart=$tpl->framework_buildjs("nginx:/reverse-proxy/restarthup",
        "nginx.restart.progress","nginx.restart.progress.txt",
        "progress-nginx-restart","LoadAjax('table-nginx','$page?table=yes');");

    if (json_last_error()> JSON_ERROR_NONE) {
        return $tpl->widget_rouge("{error}",json_last_error_msg());
    }
    if(!$json->Status){
        $service_restart=$tpl->framework_buildjs("/reverse-proxy/start",
            "artica-reverse-proxy.start.progress","artica-reverse-proxy.start.progress.txt",
            "progress-nginx-restart");
        $wbutton[0]["name"] = "{start}";
        $wbutton[0]["icon"] = ico_run;
        $wbutton[0]["js"] = $service_restart;
        $line=__LINE__;
        echo $tpl->widget_rouge("{error}","Framework return false! ($line)",$wbutton,ico_bug);
        return false;
    }
    $ini->loadString($json->Info);
    $html[]=$tpl->SERVICE_STATUS($ini, "APP_NGINX",$service_restart);

    $ini=new Bs_IniHandler();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/nginx/artica-reverse/status"));

    if(!$json->Status){
        $service_restart=$tpl->framework_buildjs("/reverse-proxy/start",
            "artica-reverse-proxy.start.progress","artica-reverse-proxy.start.progress.txt",
            "progress-nginx-restart");
        $wbutton[0]["name"] = "{start}";
        $wbutton[0]["icon"] = ico_run;
        $wbutton[0]["js"] = $service_restart;
        $line=__LINE__;
        $html[]=$tpl->widget_rouge("{APP_REVERSE_PROXY}","Framework return fals ($line)!",$wbutton,ico_bug);
    }else {
        $service_restart = $tpl->framework_buildjs("/nginx/artica-reverse/restart",
            "artica-reverse-proxy.progress", "artica-reverse-proxy.progress.txt",
            "progress-nginx-restart", "LoadAjax('table-nginx','$page?table=yes');");
        $ini->loadString($json->Info);
        $html[]=$tpl->SERVICE_STATUS($ini, "APP_REVERSE_PROXY",$service_restart);
    }
    return @implode("\n",$html);
}

function nginx_status():bool{
    $page=currentPageName();
    if(is_file("/etc/artica-postfix/AS_DOCKER_SERVICE")){
        return docker_status();
    }
    $EnablePrometheusNginx = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePrometheusNginx"));
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $html[]=ServiceStatus();
    $html[]="<!-- EnablePrometheusNginx:$EnablePrometheusNginx -->";    if($EnablePrometheusNginx==1) {
        $html[] = PrometheusStatus();
    }

    $html[]="<script>";
    $html[]="LoadAjaxSilent('nginx_status_line','$page?nginx-status-line=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function table():bool{
    $page=CurrentPageName();
    echo "<div id='nginx-status-flat'></div>
<script>LoadAjax('nginx-status-flat','$page?table2=yes')</script>";
    return true;
}
function nginx_status_geoip():string{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $EnableGeoipUpdate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate");

    if($EnableGeoipUpdate==0){
        $s_PopUp="s_PopUp('https://wiki.articatech.com/reverse-proxy/security/limit-access-by-countries','1024','800')";
        $button["name"] = "{help}";
        $button["js"] = $s_PopUp;
        $button["ico"] = ico_save;
        $GEOIP_STATUS=$tpl->widget_h("gray",ico_database,"{not_installed}","{APP_GEOIP}",$button);
        return $tpl->_ENGINE_parse_body($GEOIP_STATUS);

    }


    if(!is_file("/etc/nginx/maps.d/00_GeoIP.map")) {
        $button["name"] = "{reconfigure}";
        $button["js"] = "Loadjs('$page?reconfigure-js=yes')";
        $button["ico"] = ico_save;
        $GEOIP_STATUS = $tpl->widget_h("yellow", ico_database, "{missing_databases}", "{APP_GEOIP}", $button);
        return $tpl->_ENGINE_parse_body($GEOIP_STATUS);

    }
    $button["name"] = "{reconfigure}";
    $button["js"] = "Loadjs('$page?reconfigure-js=yes')";
    $button["ico"] = ico_save;
    $GEOIP_STATUS=$tpl->widget_h("green",ico_database,"{active2}","{APP_GEOIP}",$button);
    return $tpl->_ENGINE_parse_body($GEOIP_STATUS);

}
function table2():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $NginxEmergency_div=null;
    $NginxEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxEmergency"));
    $GLOBAL_NGINX_REQUESTS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GLOBAL_NGINX_REQUESTS"));


	if($NginxEmergency==1){
        $NginxEmergency_div="<p>".$tpl->widget_rouge("{nginx_emergency_mode}","{emergency2}")."</p>";

    }

    $bt_update=$tpl->button_autnonome("{APP_NGINX}: {update2}","Loadjs('$page?updatesoft-js=yes')","fa-compact-disc","AsWebMaster",334);
    if(is_file("/etc/artica-postfix/AS_DOCKER_SERVICE")){$bt_update=null;}
	
	$html[]="<table style='width:100%'>";
	$html[]="    <tr>";
	$html[]="        <td style='width:260px;vertical-align:top'>";
    $html[]="            <div style='margin-top:10px' id='nginx-service-status'></div>";
    $html[]="            <div id='web-firewall-status'></div>";
    $html[]=             $NginxEmergency_div;
    $html[]="             <div style='margin-top:10px'>$bt_update</div>";
    $html[]="        </td>";
    $html[]="";


    $APP_NGINX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_NGINX} v$APP_NGINX_VERSION &raquo;&raquo; {service_status}";
    if(is_file("/etc/artica-postfix/AS_DOCKER_SERVICE")){
        $TINY_ARRAY["TITLE"]="{APP_NGINX} &raquo;&raquo; {parameters}";
    }
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));

    if($PowerDNSEnableClusterSlave==0) {
        $topbuttons[] = array("Loadjs('$page?uninstall-service=yes')", ico_trash, "{uninstall}");
    }


    if($GLOBAL_NGINX_REQUESTS>0){
        $topbuttons[] = array("Loadjs('fw.nginx.metrics.php?serviceid=0')","fad fa-cloud-showers-heavy", $tpl->FormatNumber($GLOBAL_NGINX_REQUESTS)."&nbsp;{requests}");
    }

    $nginxsock=new socksngix(0);
    $nginxCachesDir=intval($nginxsock->GET_INFO("nginxCachesDir"));
    VERBOSE("BUTTON: nginxCachesDir = $nginxCachesDir",__LINE__);
    if($nginxCachesDir==1){
        $cachscan=$tpl->framework_buildjs("nginx.php?cache-disk-scan=yes",
            "nginx.scan.progress","nginx.scan.log",
            "progress-nginx-restart");

        $topbuttons[] = array($cachscan,ico_refresh, "{analyze_your_cache}");

    }
    $topbuttons[] = array("Loadjs('fw.nginx.license.php')", ico_certificate, "{license}");
    $topbuttons[] = array("Loadjs('$page?reconfigure-js=yes')",ico_save, "{reconfigure}");


    $TINY_ARRAY["ICO"]="fas fa-globe-africa";
    $TINY_ARRAY["EXPL"]="{enable_nginx_text}";
    $TINY_ARRAY["URL"]="nginx-status";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["DANGER"]=false;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";




    $html[]="<td style='padding-left:10px;padding-top:0;vertical-align:top'>";
    $html[]="<div id='nginx_status_line'></div>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<tr>";
    $html[]="<td></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td><div id='graphs-line-total-hour'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td><div id='graphs-line-total'></div></td>";
    $html[]="</tr>";

    $html[]="<tr>";
    $html[]="<td>";
    $html[]="    <table style='width:100%'>";
    $html[]="        <tr>";
    $html[]="            <td style='width:50%'><div id='chart-top-sites-hours'></div></td>";
    $html[]="            <td style='width:50%'><div id='chart-top-sites-days'></div></td>";
    $html[]="        </tr>";
    $html[]="    </table>";
    $html[]="</td>";
    $html[]="</tr>";

    $html[]="<tr>";
    $html[]="<td><div id='graphs-pie-cache'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";


	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
    $html[]="";
    $html[]="<script>";
    $html[]=$jstiny;

    $js=$tpl->RefreshInterval_js("nginx-service-status",$page,"nginx-status=yes");
    $html[]=$js;
    $html[]="LoadAjaxSilent('web-firewall-status','$page?nginx-status-firewall=yes');";

    $nginxsock=new socksngix(0);
    $nginxCachesDir=intval($nginxsock->GET_INFO("nginxCachesDir"));
    if($nginxCachesDir==1){
        $html[]="Loadjs('$page?graphs-pie-cache=yes&id=graphs-pie-cache');";
    }
    $html[]="Loadjs('$page?chart-top-sites-hours=yes')";
    $html[]="Loadjs('$page?chart-top-sites-days=yes')";
    $html[]="Loadjs('$page?graphs-line-total-hour=yes&id=graphs-line-total-hour')";
    $html[]="Loadjs('$page?graphs-line-total=yes&id=graphs-line-total')";
    /*
    $millisPerLine=3000;
    $html[]="if (SmoothieChartNginxRTIntervall){ clearInterval(SmoothieChartNginxRTIntervall);}";
    $html[]="var SmoothieChartNginxRT = new SmoothieChart({grid:{fillStyle:'#ffffff',strokeStyle:'rgba(119,119,119,0.03)',millisPerLine:$millisPerLine},labels:{fillStyle:'#2e3436',fontSize:9,showIntermediateLabels:true},tooltip:true,tooltipLine:{strokeStyle:'#bbbbbb'},minValue:0}),canvas = document.getElementById('smoothie-nginx-realtime'),SmoothieChartNginxRTSeries = new TimeSeries();";

    $html[]="SmoothieChartNginxRT.addTimeSeries(SmoothieChartNginxRTSeries, {lineWidth:4.1,strokeStyle:'#008000',interpolation:'bezier'});";
    $html[]="SmoothieChartNginxRT.streamTo(canvas, $millisPerLine);";
    $html[]="var SmoothieChartNginxRTIntervall = setInterval(function() {";
    $html[]="Loadjs('$page?SmoothieChartNginxRTIntervall=yes');";
    $html[]="}, $millisPerLine);";
*/
    $html[]="</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
	
}
function SmoothieChartNginxRTIntervall():bool{

    $sock=new sockets();
    $data=$sock->REST_API_NGINX("/reverse-proxy/CurCons");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
       return false;
    }
    if (!$json->Status){
        return false;
    }
    header("content-type: application/x-javascript");
    $Value=$json->Value;
    $f[]="if( !document.getElementById('smoothie-nginx-realtime') ){";
    $f[]="\tclearInterval(SmoothieChartNginxRTIntervall);";
    $f[]="}else{";
    $f[]="SmoothieChartNginxRTSeries.append(Date.now(), $Value);";
    $f[]="}";
    echo @implode("\n",$f);
    return true;
}


function graphs_line_total_hour():bool{
    $today=date("Y-m-d 00:00:00");
    $q=new postgres_sql();
    $sql="SELECT to_char( date_trunc('hour', zdate) + ((extract(minute FROM zdate)::int / 10) * interval '10 minutes'), 'HH24:MI' ) AS hh_mm, SUM(requestcounter) AS event_count FROM nginx_stats WHERE zdate > '$today' GROUP BY 1";
    $ydata=array();
    $xdata=array();
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        header("content-type: application/x-javascript");
        echo "// $q->mysql_error\n";
        return false;
    }
    while($ligne=@pg_fetch_assoc($results)){
        $xdata[]=$ligne["hh_mm"];
        $ydata[]=$ligne["event_count"];
    }

    if(count($xdata)<2){
        header("content-type: application/x-javascript");
        echo "// Count < 2\n";
        return true;
    }
    $timetext="";
    $title="{requests} {today}";
    if(isset($_GET["interval"])) {
        $timetext = $_GET["interval"];
    }
    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="{requests}";
    $highcharts->xAxis_labels=true;
    $highcharts->height=250;
    $highcharts->LegendSuffix="{time}";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{requests}"=>$ydata);
    echo $highcharts->ApexChart();
    return true;
}

function graphs_pie_total_cache():bool{
    $MODSECURITY_PIE_DAY=array();
    $NginxSitesCacheSize=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxSitesCacheSize"));
    if(!$NginxSitesCacheSize){
        return false;
    }
    if(count($NginxSitesCacheSize)==0){
        return false;
    }
    foreach ($NginxSitesCacheSize as $serviceid=>$size){
        $servicename=get_servicename($serviceid);
        $MODSECURITY_PIE_DAY[$servicename]=$size;
    }

    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->PieDatas=$MODSECURITY_PIE_DAY;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{top} {cache}";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();
    return true;


}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return strval($ligne["servicename"]);
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
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}
function graphs_line_total():bool{

    $q=new postgres_sql();
    $sql="SELECT to_char(date_trunc('hour', zdate), 'MM-DD') AS hour_formatted, SUM(requestcounter) AS event_count FROM nginx_stats GROUP BY 1 ORDER BY 1;";
    $ydata=array();
    $xdata=array();
    $results=$q->QUERY_SQL($sql);
    while($ligne=@pg_fetch_assoc($results)){
        $xdata[]=$ligne["hour_formatted"];
        $ydata[]=$ligne["event_count"];
    }
    if(count($xdata)<2){
        header("content-type: application/x-javascript");
        echo "// $sql;";
        echo "// Count = ".count($xdata)." <2";
        return true;
    }

    $title="{requests} {by_day}";
    if(!isset($_GET["interval"])){
        $_GET["interval"]="Day";
    }

    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="{requests}";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="{days}";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{requests}"=>$ydata);
    echo $highcharts->ApexChart();
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
function nginx_status_firewall():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $EnableNginxFW              = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginxFW"));
    $refreshAll="Loadjs('fw.progress.php?refresh-menus=yes');";
    if($EnableNginxFW==0){

        $install_firewall=$tpl->framework_buildjs("/reverse-proxy/firewall/install","nginxfw.progress","nginxfw.log",
            "progress-nginx-restart","LoadAjaxSilent('nginx-status-line','$page?nginx-status-line=yes');$refreshAll");

        $button["name"] = "{install}";
        $button["js"] = $install_firewall;
        $button["ico"]=ico_cd;

        $WIDGET=$tpl->widget_h("gray",ico_firewall,"{disabled}","{firewall_for_web}",$button);
        echo $tpl->_ENGINE_parse_body($WIDGET);
        return true;


    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/reverse-proxy/firewall/status"));
    $OUT=unserialize($json->Info);
    $status_service=boolval($OUT["STATUS"]);


    $uninstall=$tpl->framework_buildjs("/reverse-proxy/firewall/uninstall","nginxfw.progress","nginxfw.log", "progress-nginx-restart","LoadAjaxSilent('nginx-status-line','$page?nginx-status-line=yes');$refreshAll");

    $stop=$tpl->framework_buildjs("/reverse-proxy/firewall/stop","nginxfw.stop.progress","nginxfw.stop.txt","progress-nginx-restart","LoadAjaxSilent('nginx-status-line','$page?nginx-status-line=yes');");

    $start=$tpl->framework_buildjs("/reverse-proxy/firewall/start","nginxfw.start.progress","nginxfw.start.txt", "progress-nginx-restart","LoadAjaxSilent('nginx-status-line','$page?nginx-status-line=yes');");


    $button_install["name"] = "{uninstall}";
    $button_install["js"] = $uninstall;
    $button_install["ico"]="fas fa-trash-alt";


    if($status_service) {
        $button_action["name"] = "{stop}";
        $button_action["js"] = $stop;
        $button_action["ico"]="fas fa-stop-circle";
    }else{
        $button_action["name"] = "{start}";
        $button_action["js"] = $start;
        $button_action["ico"]=ico_play;

    }


    if($status_service) {
        $NGINX_FW_IPSET_COUNT=NGINX_FW_IPSET_COUNT();
            if ($NGINX_FW_IPSET_COUNT > 0) {
                $NGINX_FW_IPSET_COUNT=$tpl->FormatNumber($NGINX_FW_IPSET_COUNT);
                $WIDGET=$tpl->widget_h("green",ico_firewall,
                    "$NGINX_FW_IPSET_COUNT {rules}","{firewall_for_web}",$button_action,$button_install);
                echo $tpl->_ENGINE_parse_body($WIDGET);
                return true;
            }

        $WIDGET=$tpl->widget_h("green",ico_firewall,
            "{active2}","{firewall_for_web}",$button_action,$button_install);
        echo $tpl->_ENGINE_parse_body($WIDGET);
        return true;

    }
    $WIDGET=$tpl->widget_h("grey",ico_firewall,
        "{inactive2}","{firewall_for_web}",$button_action,$button_install);
    echo $tpl->_ENGINE_parse_body($WIDGET);
    return true;
 }
function NGINX_FW_IPSET_COUNT():int{
    $NGINX_FW_IPSET_COUNT = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NGINX_FW_IPSET_COUNT"));
    if($NGINX_FW_IPSET_COUNT>0){
        return $NGINX_FW_IPSET_COUNT;
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/reverse-proxy/firewall/ipsetcount"));

   
    $NGINX_FW_IPSET_COUNT = $json->Count;
    if ($NGINX_FW_IPSET_COUNT > 0) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NGINX_FW_IPSET_COUNT", $NGINX_FW_IPSET_COUNT);
        return $NGINX_FW_IPSET_COUNT;
    }
    return 0;
}
function chart_top_sites_days():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $today=date("Y-m-d")." 00:00:00";
    $sql="SELECT serviceid,SUM(requestcounter) as tcount FROM nginx_stats WHERE serviceid > 0 GROUP by serviceid ORDER BY tcount DESC LIMIT 5";
    $q=new postgres_sql();
    $MAIN=array();
    $results=$q->QUERY_SQL($sql);
    while($ligne=@pg_fetch_assoc($results)){
        $instance=get_servicename($ligne['serviceid']);
        $tcount=$ligne["tcount"];
        $MAIN[$instance]=$tcount;

    }


    $highcharts=new highcharts();
    $highcharts->container="chart-top-sites-days";
    $highcharts->PieDatas=$MAIN;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{top} 5 {websites} {total}";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->ApexPie();
    return true;

}
function chart_top_sites_hours():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $today=date("Y-m-d")." 00:00:00";
    $sql="SELECT serviceid,SUM(requestcounter) as tcount FROM nginx_stats WHERE serviceid > 0 AND zdate > '$today' GROUP by serviceid ORDER BY tcount DESC LIMIT 5";
    $q=new postgres_sql();

    $results=$q->QUERY_SQL($sql);
    while($ligne=@pg_fetch_assoc($results)){
        $instance=get_servicename($ligne['serviceid']);
        $tcount=$ligne["tcount"];
        $MAIN[$instance]=$tcount;

    }


    $highcharts=new highcharts();
    $highcharts->container="chart-top-sites-hours";
    $highcharts->PieDatas=$MAIN;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{top} 5 {websites} {today}";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->ApexPie();
    return true;

}
function nginx_status_line():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();

    $sstyle="padding-left:5px;padding-top:0px;vertical-align:top";
    $html[]="<table style='width:99%'>";
    $html[]="<tr>";


    $nginx_caches=nginx_cache_status();
    $nginx_pagespeed = nginx_pagespeed();
    $nginx_status_geoip=nginx_status_geoip();
    $width="33%";
    $html[] = "<td style='$sstyle;width:33%'>$nginx_pagespeed</td>";
    $html[]="<td style='$sstyle;width:$width'>$nginx_caches</td>";
    $html[]="<td style='$sstyle;width:$width'>$nginx_status_geoip</div>";
    $html[]="</tr>";

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/stats"));
    $widget_connections=widget_connections($json);
    $widget_requests=widget_requests($json);
    $widget_domains=widget_domains($json);

    $html[]="<tr>";
    $html[]="<td style='$sstyle;width:$width'>$widget_connections</td>";
    $html[]="<td style='$sstyle;width:$width'>$widget_requests</div>";
    $html[]="<td style='$sstyle;width:$width'>$widget_domains</div>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function widget_connections($json):string{
    $button="";
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    if(!$json->Status){
        return $tpl->widget_h("red",ico_bug, "{error}", "{connections}",$button);
    }
    if(!property_exists($json, "Stats")){
        return $tpl->widget_h("red",ico_bug, "{error}", "{connections}",$button);
    }
    if($json->Stats->connections->active==0){
        return $tpl->widget_h("gray",ico_nic, 0, "{connections}",$button);
    }
    return $tpl->widget_h("green",ico_nic,$tpl->FormatNumber($json->Stats->connections->active),  "{connections}",$button);

}
function widget_requests($json):string{
    $button="";
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    if(!$json->Status){
        return $tpl->widget_h("red","far fa-cloud-showers-heavy", "{error}", "{requests}",$button);
    }
    if(!property_exists($json, "Stats")){
        return $tpl->widget_h("red","far fa-cloud-showers-heavy", "{error}", "{requests}",$button);
    }
    if($json->Stats->connections->requests==0){
        return $tpl->widget_h("gray","far fa-cloud-showers-heavy", 0, "{requests}",$button);
    }


    return $tpl->widget_h("green","far fa-cloud-showers-heavy",$tpl->FormatNumber($json->Stats->connections->requests),  "{requests}",$button);

}
function widget_domains($json):string{
    $button = "";
    $ico="far fa-globe";
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    if(!$json->Status){
        return $tpl->widget_h("red",$ico, "{error}", "{domains}",$button);
    }
    if(!property_exists($json, "Stats")){
        return $tpl->widget_h("red",$ico, "{error}", "{domains}",$button);
    }
    $c=0;
    foreach ($json->Stats->serverZones as $domain=>$class){
        if($domain=="*"){
            continue;
        }
        $requestCounter=$class->requestCounter;
        if($requestCounter==0){
            continue;
        }
        $c++;
    }
    if($c==0){
        return $tpl->widget_h("gray",$ico, 0, "{domains}",$button);
    }

    return $tpl->widget_h("green",$ico,$tpl->FormatNumber($c),  "{domains}",$button);



}
function nginx_pagespeed():string{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $nginx_pagespeed_installed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_pagespeed_installed"));

    if($nginx_pagespeed_installed==0) {
        $WIDGET = $tpl->widget_h("gray", "fa-solid fa-gauge-circle-bolt", "{not_installed}", "{APP_MOD_PAGESPEED}");
        return $tpl->_ENGINE_parse_body($WIDGET);
    }
    $nginx_pagespeed_enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_pagespeed_enabled"));


    if($nginx_pagespeed_enabled==0){

        $button["name"] = "{enable_feature}";
        $button["js"] = "Loadjs('$page?pagespeed-enable=yes')";
        $button["ico"]=ico_cd;

        $WIDGET = $tpl->widget_h("gray", "fa-solid fa-gauge-circle-bolt", "{inactive2}", "{APP_MOD_PAGESPEED}",$button);
        return $tpl->_ENGINE_parse_body($WIDGET);
    }

    $CountOfSites=0;
    $button["name"] = "{disable_feature}";
    $button["js"] = "Loadjs('$page?pagespeed-enable=yes')";
    $button["ico"]=ico_cd;
    $EnableWordpressManagement=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWordpressManagement");
    if($EnableWordpressManagement==1){
        $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
        $sline=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM wp_sites WHERE pagespeed=1");
        $CountOfSites=$CountOfSites+intval($sline["tcount"]);
    }
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $sline=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM service_parameters WHERE zkey='pagespeed' and zvalue='1'");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    $CountOfSites=$CountOfSites+intval($sline["tcount"]);

    if($CountOfSites==0) {
        $WIDGET = $tpl->widget_h("gray", "fa-solid fa-gauge-circle-bolt", "0  {websites}", "{APP_MOD_PAGESPEED}",$button);
        return $tpl->_ENGINE_parse_body($WIDGET);
    }
    $WIDGET = $tpl->widget_h("green", "fa-solid fa-gauge-circle-bolt", "$CountOfSites  {websites}", "{APP_MOD_PAGESPEED}",$button);
    return $tpl->_ENGINE_parse_body($WIDGET);



}
function nginx_cache_status():string{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $nginxsock=new socksngix(0);
    $page=CurrentPageName();

    $button["name"] = "{parameters}";
    $button["js"] = "Loadjs('$page?section-cache-js=yes');";
    $button["ico"]=ico_params;

    $nginxCachesDir=intval($nginxsock->GET_INFO("nginxCachesDir"));
    $NginxCacheRedis=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedis"));
    $EnableRedisServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedisServer"));
    if($EnableRedisServer==0){
        $NginxCacheRedis=0;
    }


    if($nginxCachesDir==1){
        $unit="({disk})";
        $nginxCacheMem=intval($nginxsock->GET_INFO("nginxCacheMem"));
        if($nginxCacheMem==1){
            $unit="({memory})";
        }
        $Title="{active2}";
        $NginxGlobalCacheSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxGlobalCacheSize"));
        if($NginxGlobalCacheSize>1024){
            $Title=FormatBytes($NginxGlobalCacheSize/1024);
        }
        $WIDGET = $tpl->widget_h("green", ico_hd, $Title, "{sites_caching} $unit",$button);
        return $tpl->_ENGINE_parse_body($WIDGET);
    }

    if ($NginxCacheRedis==0){
        $WIDGET = $tpl->widget_h("grey", ico_hd, "{inactive2}", "{sites_caching} ({disk})",$button);
        return $tpl->_ENGINE_parse_body($WIDGET);
    }

    $redis_status=redis_status();
    if(!$redis_status[0]) {
        $WIDGET = $tpl->widget_h("green", ico_mem, $redis_status[1], "{sites_caching}", $button);
        return $tpl->_ENGINE_parse_body($WIDGET);
    }
    if($redis_status[1]>1) {
        $items = $tpl->FormatNumber($redis_status[1]);
        $WIDGET = $tpl->widget_h("green", ico_mem, "$items {elements}", "{sites_caching}", $button);
        return $tpl->_ENGINE_parse_body($WIDGET);
    }
    if($redis_status[1]==0) {
        $WIDGET = $tpl->widget_h("grey", ico_mem, "{no_data}", "{sites_caching}", $button);
        return $tpl->_ENGINE_parse_body($WIDGET);
    }
    if($redis_status[1]==1) {
        $WIDGET = $tpl->widget_h("green", ico_mem, "1 {element}", "{sites_caching}", $button);
        return $tpl->_ENGINE_parse_body($WIDGET);
    }
    return "";

}
function srcache_redis_pwd():string{

    $NginxCacheRedisLocal=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisLocal"));
    $EnableRedisServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedisServer"));
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
    $EnableRedisServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedisServer"));
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