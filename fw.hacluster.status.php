<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
// awc_table_style()/awc_pager_js(): kit des tableaux d'entites. Include au
// PREMIER NIVEAU obligatoire -- glisse dans un bloc conditionnel il ne
// s'executerait jamais et la page mourrait sur « Call to undefined function ».
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.hacluster-bbr.inc");
include_once(dirname(__FILE__)."/ressources/class.hacluster.meta.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["refreshimage"])){refresh_image();exit;}
if(isset($_GET["remote-categories-service"])){remote_categories_service_js();exit;}
if(isset($_GET["remote-categories-service-popup"])){remote_categories_service_popup();exit;}
if(isset($_POST["ServerPersistentConnections"])){section_keepalive_save();exit;}
if(isset($_POST["ClientPersistentConnections"])){section_keepalive_save();exit;}
if(isset($_POST["AgentDynamicWeight"])){section_dynweight_save();exit;}
if(isset($_POST["HaClusterBBRManaged"])){section_bbr_save();exit;}
if(isset($_POST["HaClusterBBREnable"])){section_bbr_save();exit;}
if(isset($_GET["section-bbr-js"])){section_bbr_js();exit;}
if(isset($_GET["section-bbr-popup"])){section_bbr_popup();exit;}
if(isset($_GET["section-bbr-inner"])){echo section_bbr_inner();exit;}
if(isset($_POST["HaClusterUseRemoteCategoriesService"])){remote_categories_service_save();exit;}
if(isset($_POST["HaClusterUseCentralErrorPage"])){section_errorpage_save();exit;}
if(isset($_POST["HaClusterDeployArticaUpdates"])){SaveParams();exit;}
if(isset($_POST["HaClusterAsAD"])){SaveParams();exit;}
if(isset($_POST["HaClusterUseHaClient"])){SaveParams();exit;}
if(isset($_POST["HaClusterUseAddr"])){SaveParams();exit;}
if(isset($_POST["HaClusterUseLBAsDNS"])){SaveParamsGeneral();exit;}
if(isset($_POST["HaClusterServeDNS"])){SaveParamsGeneral();exit;}
if(isset($_POST["HaClusterSquidNameServer1"])){SaveParams();exit;}
if(isset($_POST["HaClusterSquidNameServer2"])){SaveParams();exit;}
if(isset($_POST["HaClusterSquidNameServer3"])){SaveParams();exit;}
if(isset($_POST["HaClusterUseLocalDNSCache"])){SaveParams();exit;}
if(isset($_POST["DNS1"])){SaveParams();exit;}
if(isset($_POST["DNS2"])){SaveParams();exit;}
if(isset($_POST["DOMAINS1"])){SaveParams();exit;}
if(isset($_POST["DOMAINS2"])){SaveParams();exit;}
if(isset($_POST["HaClusterEnableITChart"])){section_itcharter_save();exit;}
if(isset($_POST["HaClusterReplicateACLs"])){section_replicacls_save();exit;}
if(isset($_POST["HaClusterProxyUseOwnDNS"])){SaveParams();exit;}
if(isset($_POST["HaClusterProxyUseBackendDNSDIST"])){SaveParams();exit;}
if(isset($_POST["HaClusterEnableProxyProtocol"])){SaveParams();exit;}
if(isset($_POST["HaClusterDecryptSSL"])){SaveParams();exit;}
if(isset($_POST["HaClusterRemoveRealtimeLogs"])){SaveParams();exit;}
if(isset($_GET["section-simuad-js"])){section_simuad_js();exit;}
if(isset($_GET["section-simuad-popup"])){section_simuad_popup();exit;}
if(isset($_POST["HaClusterTimeOutConnect"])){SaveParams();exit;}
if(isset($_POST["HaClusterCheckInt"])){SaveParams();exit;}
if(isset($_POST["HaClusterWorkers"])){SaveParams();exit;}
if(isset($_POST["HaClusterProto"])){SaveParams();exit;}
if(isset($_POST["HaClusterBalance"])){SaveParams();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["hacluster-table-status-left"])){status();exit;}
if(isset($_GET["hacluster-table-status-top"])){status_top();exit;}
if(isset($_GET["hacluster-table-status-center"])){status_center();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["parameters-table"])){parameters_table();exit;}
if(isset($_GET["hacp-reset-js"])){hacp_reset_js();exit;}
if(isset($_POST["hacp-reset-cert"])){hacp_reset_perform();exit;}
if(isset($_GET["maxconns-js"])){maxconns_js();exit;}
if(isset($_GET["maxconns-popup"])){maxconns_popup();exit;}
if(isset($_POST["HaClusterMaxConn"])){maxconns_save();exit;}

if(isset($_GET["restart-debug-js"])){restart_debug_js();exit;}
if(isset($_GET["restart-debug-popup"])){restart_debug_popup();exit;}
if(isset($_GET["restart-debug-step1"])){restart_debug_step1();exit;}
if(isset($_POST["HaClusterTimeDebug"])){restart_debug_save();exit;}
if(isset($_GET["restart-debug-step2"])){restart_debug_step2();exit;}


if(isset($_GET["section-keepalive-js"])){section_keepalive_js();exit;}
if(isset($_GET["section-keepalive-popup"])){section_keepalive_popup();exit;}
if(isset($_GET["section-dynweight-js"])){section_dynweight_js();exit;}
if(isset($_GET["section-dynweight-popup"])){section_dynweight_popup();exit;}


if(isset($_GET["section-errorpage-js"])){section_errorpage_js();exit;}
if(isset($_GET["section-errorpage-popup"])){section_errorpage_popup();exit;}

if(isset($_GET["section-itcharter-js"])){section_itcharter_js();exit;}
if(isset($_GET["section-itcharter-popup"])){section_itcharter_popup();exit;}

if(isset($_GET["section-replicacls-js"])){section_replicacls_js();exit;}
if(isset($_GET["section-replicacls-popup"])){section_replicacls_popup();exit;}

if(isset($_GET["section-limit-access-js"])){section_limit_access_js();exit;}
if(isset($_GET["section-limit-access-popup"])){section_limit_access_popup();exit;}
if(isset($_GET["section-limit-access-table"])){section_limit_access_table();exit;}
if(isset($_GET["section-limit-access-add"])){section_limit_access_add();exit;}
if(isset($_POST["section-limit-access-add"])){section_limit_access_add_perform();exit;}
if(isset($_GET["section-limit-access-delete"])){section_limit_access_delete();exit;}

if(isset($_GET["section-listen-js"])){section_listen_js();exit;}
if(isset($_GET["section-listen-popup"])){section_listen_popup();exit;}

if(isset($_GET["section-ipaddr-js"])){section_ipaddr_js();exit;}
if(isset($_GET["section-ipaddr-popup"])){section_ipaddr_popup();exit;}

if(isset($_GET["section-deploy-js"])){section_deploy_js();exit;}
if(isset($_GET["section-deploy-popup"])){section_deploy_popup();exit;}


if(isset($_GET["section-performance-js"])){section_performance_js();exit;}
if(isset($_GET["section-performance-popup"])){section_performance_popup();exit;}

if(isset($_GET["section-health-js"])){section_health_js();exit;}
if(isset($_GET["section-health-popup"])){section_health_popup();exit;}

if(isset($_GET["section-timeout-js"])){section_timeout_js();exit;}
if(isset($_GET["section-timeout-popup"])){section_timeout_popup();exit;}

if(isset($_GET["section-pproto-js"])){section_pproto_js();exit;}
if(isset($_GET["section-pproto-popup"])){section_pproto_popup();exit;}

if(isset($_GET["section-haclient-js"])){section_haclient_js();exit;}
if(isset($_GET["section-haclient-popup"])){section_haclient_popup();exit;}

if(isset($_GET["section-dns-js"])){section_dns_js();exit;}
if(isset($_GET["section-dns-general"])){section_dns_general();exit;}
if(isset($_GET["section-dns-general2"])){section_dns_general2();exit;}

if(isset($_GET["section-dns-popup"])){section_dns_popup();exit;}
if(isset($_GET["section-dns-tabs"])){section_dns_tabs();exit;}
if(isset($_GET["section-dns-proxy-start"])){section_dns_proxy_start();exit;}
if(isset($_GET["section-dns-proxy"])){section_dns_proxy_popup();exit;}

if(isset($_GET["section-ssl-js"])){section_ssl_js();exit;}
if(isset($_GET["section-ssl-popup"])){section_ssl_popup();exit;}

if(isset($_GET["section-logs-js"])){section_logs_js();exit;}
if(isset($_GET["section-logs-popup"])){section_logs_popup();exit;}

if(isset($_GET["graphs"])){status_graphs();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_VERSION");
    $html=$tpl->page_header("{APP_HAPROXY_CLUSTER} v$version",
        "fas fa-code-branch",
        "{APP_HAPROXY_CLUSTER_EXPLAIN}",
        "$page?tabs=yes",
        "hacluster-status",
        "progress-hacluster-restart");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_HAPROXY_CLUSTER} v$version",$html);
        echo $tpl->build_firewall();
        return;
    }

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function maxconns_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{maxconn}","$page?maxconns-popup=yes",550);
}
function maxconns_popup():bool{
    $tpl=new template_admin();
    echo hacluster_meta_popup_head(array("HaClusterMaxConn"),$tpl);
    $HaClusterMaxConn=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterMaxConn");
    $form[]=$tpl->field_numeric("HaClusterMaxConn","{maxconn}",$HaClusterMaxConn,"{haproxy_maxconn}");
    $jsafter[]="dialogInstance2.close();";
    echo $tpl->form_outside(null, $form,null,"{apply}", @implode(";",$jsafter), "AsSquidAdministrator");
    return true;
}
function maxconns_save():bool{
    if(hacluster_meta_gate("post")){return false;}
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterMaxConn",$_POST["HaClusterMaxConn"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/maxconns");
    return admin_tracks("Enforce HaCluster to {$_POST["HaClusterMaxConn"]} Max connections number");
}
function top_status(){

    //widget_style2

}

function section_deploy_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{deploy_artica_updates}","$page?section-deploy-popup=yes",650);
}
function restart_debug_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog8("{restart} {debug}","$page?restart-debug-popup=yes");
}
function restart_debug_popup():bool{
    $page=CurrentPageName();
    echo "<div id='restart-debug-popup'></div>";
    echo "<div id='restart-debug-results'></div>";
    echo "<script>LoadAjax('restart-debug-popup','$page?restart-debug-step1=yes')</script>";
    return true;
}
function restart_debug_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    return admin_tracks("Restart the Hacluster in debug mode");
}
function restart_debug_step2():bool{
    $data=@file_get_contents("ressources/logs/hacluster-debug.log");
    echo "<textarea style='width:99%;height:550px'>$data</textarea>";
    return true;
}
function restart_debug_step1():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $HaClusterTimeDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTimeDebug"));
    if($HaClusterTimeDebug==0){
        $HaClusterTimeDebug=10;
    }

    $form[]=$tpl->field_numeric("HaClusterTimeDebug","{timeout} ({seconds})",$HaClusterTimeDebug);

    $jsreload=$tpl->framework_buildjs("/hacluster/server/debug/restart",
        "hacluster.debug.progress",
        "hacluster.debug.progress.txt",
        "restart-debug-popup",
        "LoadAjax('restart-debug-results','$page?restart-debug-step2=yes')"

    );

    echo $tpl->form_outside(null,$form,null,"{apply}",$jsreload,"AsSquidAdministrator",true);
    return true;
}


function section_listen_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{listen}","$page?section-listen-popup=yes",650);
}
function section_limit_access_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{enable_limit_access}","$page?section-limit-access-popup=yes",650);
}
function section_itcharter_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{APP_ITCHARTER}","$page?section-itcharter-popup=yes",650);
}
function section_replicacls_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{acls_replication}","$page?section-replicacls-popup=yes",650);
}


function section_errorpage_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{error_page_service}","$page?section-errorpage-popup=yes",650);
}
function section_keepalive_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{persistent_connections}","$page?section-keepalive-popup=yes",650);
}
function section_dynweight_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{hacluster_dynamic_weight}","$page?section-dynweight-popup=yes",650);
}
function section_ipaddr_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{ipaddr}","$page?section-ipaddr-popup=yes",650);
}
function section_simuad_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{simulate_the_active_directory}","$page?section-simuad-popup=yes",650);
}
function section_performance_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{performance}","$page?section-performance-popup=yes",650);
}
function section_health_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{health_checks_small}","$page?section-health-popup=yes",650);
}
function section_timeout_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{timeouts}","$page?section-timeout-popup=yes",650);
}
function section_pproto_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{proxy_protocol}","$page?section-pproto-popup=yes",650);
}
function section_haclient_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{APP_HACLUSTER_CLIENT}","$page?section-haclient-popup=yes",850);
}
function section_dns_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{dns_parameters}","$page?section-dns-tabs=yes",950);
}
function section_ssl_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{decrypt_ssl}","$page?section-ssl-popup=yes",650);
}
function section_logs_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{events}","$page?section-logs-popup=yes",650);
}
function section_limit_access_popup():bool{
    $page=CurrentPageName();
    echo "<div id='section-limit-access-popup'></div>";
    echo "<script>LoadAjax('section-limit-access-popup','$page?section-limit-access-table=yes');</script>";
    return true;
}
function section_limit_access_add():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $refresh="LoadAjax('section-limit-access-popup','$page?section-limit-access-table=yes');LoadAjaxSilent('hacluster-parameters','$page?parameters-table=yes');";
    return $tpl->js_prompt("{new_item}","{enable_limit_access}",ico_plus,$page,"section-limit-access-add",$refresh);

}
function section_limit_access_delete():bool{
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ipaddr=urlencode($_GET["section-limit-access-delete"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/limit/item/delete/$ipaddr"));
    if(!$json->Status){
        return $tpl->js_error($json->Error);
    }

    $md=$_GET["md"];
    echo "$('#$md').remove();LoadAjaxSilent('hacluster-parameters','$page?parameters-table=yes');";

    return admin_tracks("Delete IP restriction {$_POST['section-limit-access-delete']} for the HaCluster load-balancer ");
}

function section_limit_access_add_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $value=urlencode($_POST['section-limit-access-add']);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/limit/item/add/$value"));
    if(!$json->Status){
        echo $json->Error;
        return false;
    }

return admin_tracks("Adding IP restriction {$_POST['section-limit-access-add']} for the HaCluster load-balancer ");
}
function section_limit_access_table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $HaClusterLimitAccesses=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterLimitAccesses"));
    if(!$HaClusterLimitAccesses){
        $ACLREST[] = "192.168.0.0/16";
        $ACLREST[] = "10.0.0.0/8";
        $ACLREST[] = "172.16.0.0/12";

        $H=json_encode($ACLREST);
        $HaClusterLimitAccesses=json_decode($H);
    }

    $topbuttons[] = array("Loadjs('$page?section-limit-access-add=yes')",ico_plus,"{new_entry}");
    $iconet="<i class='".ico_networks."'></i>";
    $html[]=$tpl->table_buttons($topbuttons);
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>"
           ."<table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{items}</th>";
    $html[]="<th class='center'>DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='hclim-rows'>";
    $TRCLASS=null;
        foreach ($HaClusterLimitAccesses as $address){
            // .awc-tbl separe les lignes par une bordure et gere le survol:
            // le zebrage manuel n'a plus lieu d'etre.
            $TRCLASS=null;
            $md=md5(serialize($address));
            $item=$address;
            $itemEncode=urlencode($item);
            $delete=$tpl->icon_delete("Loadjs('$page?section-limit-access-delete=$itemEncode&md=$md')");

            // L'id de la ligne est la cible de $('#$md').remove() dans
            // section_limit_access_delete(): le perdre laisserait la ligne
            // supprimee visible jusqu'au rechargement suivant.
            $html[]="<tr class='$TRCLASS' id='$md'>";
            $html[]="<td><div class='awc-ident'><span class='awc-ico'>$iconet</span>"
                   ."<span class='awc-name'>$item</span></div></td>";
            $html[]="<td style='width:1%'><div class='awc-acts'>$delete</div></td>";
            $html[]="</tr>";
        }

    $html[]="</tbody>";
    $html[]="</table></div><div class='awc-foot'><span>&nbsp;</span>"
           ."<span class='awc-pg' id='hclim-pager'></span></div></div></div>";
    $html[]="<script>NoSpinner();</script>";

    // Le pager se concatene APRES le parse: la regex #{(.+?)}# du moteur de
    // traduction lirait chaque bloc {...} de son JS comme un {token}.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body($html)
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"hclim-rows","hclim-pager");
    return true;

}


function refresh_image(){
    // Legacy — no longer used with Chart.js, kept as no-op for compatibility
    header("content-type: application/x-javascript");
    echo "// charts now rendered by Chart.js";
}

function tabs():bool{
    $page=CurrentPageName();
    $EnableZabbixAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableZabbixAgent"));
    $tpl=new template_admin();
    $array["{status}"]="$page?table-start=yes";
    $array["{parameters}"]="$page?parameters=yes";



    if($EnableZabbixAgent==1) {
        $array["{APP_ZABBIX_AGENT}"] = "fw.hacluster.zabbix.php";
    }

    echo $tpl->tabs_default($array);
    return true;
}

function table_start():bool{
    $page=CurrentPageName();
    echo "<div id='hacluster-table-loader'></div><script>LoadAjax('hacluster-table-loader','$page?table=yes');</script>";
    return true;
}
function table(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_VERSION");
    $ACLUSTER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ACLUSTER_VERSION");
    $PowerDNSEnableClusterMasterTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMasterTime"));
    $PowerDNSEnableClusterMasterSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMasterSize"));
    $HaCkusterMasterServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaCkusterMasterServer"));
    $addon="";

    if(strlen($HaCkusterMasterServer)>3){
        if($PowerDNSEnableClusterMasterTime>0) {
            $pgzize="0";
            $Di = distanceOfTimeInWords($PowerDNSEnableClusterMasterTime, time());
            $hacluster_pckg=$tpl->_ENGINE_parse_body("{hacluster_pckg}");
            if($PowerDNSEnableClusterMasterSize>1024) {
                $pgzize = FormatBytes($PowerDNSEnableClusterMasterSize / 1024);
            }
            $hacluster_pckg=str_replace("%v","<strong>$ACLUSTER_VERSION ($pgzize)</strong>",$hacluster_pckg);
            $hacluster_pckg=str_replace("%t","<strong>$Di</strong>",$hacluster_pckg);
            $hacluster_pckg=str_replace("%n","<strong>$HaCkusterMasterServer</strong>",$hacluster_pckg);
            $ico=ico_file_zip;
            $addon="<p><strong><i class='$ico'></i>&nbsp;$hacluster_pckg</p>";
        }
    }

    $TINY_ARRAY["TITLE"]="{APP_HAPROXY_CLUSTER} v$version";
    $TINY_ARRAY["ICO"]="fas fa-code-branch";
    $TINY_ARRAY["EXPL"]="{APP_HAPROXY_CLUSTER_EXPLAIN}$addon";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons(top_buttons());


    $jsRefres=$tpl->RefreshInterval_js("hacluster-table-status-left",$page,"hacluster-table-status-left=yes");


    $html[]="<div style='display:flex;flex-wrap:wrap;gap:10px;margin-top:20px'>";
    // hacluster-table-status-left est le SENTINELLE de RefreshInterval_js()
    // ci-dessus: ArticaRefresh.tick() detruit son minuteur -- sans un mot et
    // avant la premiere requete -- si cet id manque du DOM.
    $html[]="<div style='flex:0 0 350px;max-width:100%'>";
    $html[]="<div id='hacluster-table-status-left'></div>";
    $html[]="</div>";
    $html[]="<div style='flex:1 1 420px;min-width:0'>";
    $html[]="<div id='hacluster-table-status-top'></div>";
    $html[]="<div id='hacluster-table-status-center'></div>";
    $html[]="</div>";
    $html[]="</div>";
    $html[]="<script>";
    $html[]= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]=$jsRefres;
    $html[]="LoadAjaxSilent('hacluster-table-status-center','$page?hacluster-table-status-center=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function top_buttons():array{
    $tpl=new template_admin();

    $topbuttons[]=array("Loadjs('fw.hacluster.backends.php?backend-js=0');",
        ico_plus,"{new_backend}");

    $jsrestart=$tpl->framework_buildjs("/hacluster/server/restart",
        "hacluster.progress",
        "hacluster.progress.txt",
        "progress-hacluster-restart"
    );

    $jsreload=$tpl->framework_buildjs("/hacluster/server/reload",
        "hacluster.progress",
        "hacluster.progress.txt",
        "progress-hacluster-restart"

    );

    $jsstop=$tpl->framework_buildjs("/hacluster/server/stop",
        "hacluster-stop.progress",
        "hacluster.progress.txt",
        "progress-hacluster-restart"

    );
    $page=currentPageName();
    $jsstart="Loadjs('$page?restart-debug-js=yes');";
    $topbuttons[]=array($jsstart,
        ico_refresh,"{restart} ({debug})");

    $topbuttons[]=array($jsreload,
        ico_retweet,"{reload}");

    $topbuttons[]=array($jsstop,
        ico_stop,"{stop_service}");

    $topbuttons[]=array($jsstart,
        ico_run,"{start_service}");

    return $topbuttons;
}
function status(){
	$tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/status"));
    $page=CurrentPageName();
    $jsrestart=$tpl->framework_buildjs("/hacluster/server/restart",
        "hacluster.progress",
        "hacluster.progress.txt",
        "progress-hacluster-restart"
    );



    if(!$json->Status){
        $html[]=$tpl->widget_rouge($json->Error,"{error}");
    }else{
        $ini=new Bs_IniHandler();
        $ini->loadString($json->Info);
        $html[]=$tpl->SERVICE_STATUS($ini, "APP_HAPROXY_CLUSTER",$jsrestart);
    }
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    VERBOSE("HaClusterServeDNS_status--->",__LINE__);
    HaClusterServeDNS_status();
    transparent_status();
    echo "<script>LoadAjaxSilent('hacluster-table-status-top','$page?hacluster-table-status-top=yes');</script>";
	
}
function HaClusterServeDNS_status():bool{

    $HaClusterServeDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterServeDNS"));
    VERBOSE("--------> HaClusterServeDNS=$HaClusterServeDNS <------------",__LINE__);
    if($HaClusterServeDNS==0){
        return false;
    }
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/dns/status"));

    $jsrestart=$tpl->framework_buildjs("/hacluster/server/dns/restart",
        "haclusterdns.progress",
        "haclusterdns.progress.txt",
        "progress-hacluster-restart"
    );

    if(!$json->Status){
        $html[]=$tpl->widget_rouge($json->Error,"{error}");
    }else{
        $ini=new Bs_IniHandler();
        $ini->loadString($json->Info);
        $html[]=$tpl->SERVICE_STATUS($ini, "APP_HACLUSTER_DNS",$jsrestart);
    }
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

    return true;
}
function transparent_status():bool{
    $tpl=new template_admin();
    $HaClusterTransParentMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode"));

    if($HaClusterTransParentMode==0){
        echo $tpl->widget_grey("{transparent_mode}","{disabled}");
        return false;

    }

    $jsRestart=$tpl->framework_buildjs(
        "/hacluster/server/transparent/restart",
        "hacluster.progress",
        "hacluster.progress.txt",
        "progress-hacluster-restart"
    );

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/transparent/status"));
    if (!$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>$json->Error", "{error}"));

    } else{
        $ini = new Bs_IniHandler();
        $ini->loadString($json->Info);
        echo $tpl->_ENGINE_parse_body($tpl->SERVICE_STATUS($ini, "APP_HAPROXY_CLUSTER_TRANSPARENT", $jsRestart));

        $ports[]=3150;
        $ports[]=3154;
        $ports[]=3155;

        foreach ($ports as $portnum){

            $fp=@fsockopen("127.0.0.1", $portnum, $errno, $errstr, 1);
            if(!$fp){
                echo $tpl->widget_rouge($errstr,"Port: $portnum");
                return false;
            }

        }
        echo $tpl->widget_vert("{transparent_mode}","OK");
    }
return true;
}
function parameters():bool{
    $page=CurrentPageName();
    echo "<div id='hacluster-parameters'></div>";
    echo "<script>\n";
    echo "LoadAjaxSilent('hacluster-parameters','$page?parameters-table=yes');";
    echo "</script>";
    return true;
}
function CreateCertifJs():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->framework_buildjs("/cluster/server/create/certificate",
        "artica.cluster.progress", "artica.cluster.log",
        "progress-hacluster-restart",
        "if(document.getElementById('hacluster-parameters')){LoadAjaxSilent('hacluster-parameters','$page?parameters-table=yes');}"
    );
}
function section_keepalive_popup():bool{
    $tpl=new template_admin();
    echo hacluster_meta_popup_head(array("ServerPersistentConnections","ClientPersistentConnections"),$tpl);
    $page=CurrentPageName();
    $HaClusterDisableClientPersistentConnections=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterDisableClientPersistentConnections");
    $HaClusterDisableServerPersistentConnections=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterDisableServerPersistentConnections");
    $ClientPersistentConnections=1;
    $ServerPersistentConnections=1;
    if($HaClusterDisableClientPersistentConnections==1){
        $ClientPersistentConnections=0;
    }
    if($HaClusterDisableServerPersistentConnections==1){
        $ServerPersistentConnections=0;
    }
    $html[]=$tpl->BigCircleCheckbox("ClientPersistentConnections","{client_persistent_connections}",
        "{client_persistent_connections_explain}",
        $ClientPersistentConnections,"LoadAjaxSilent('hacluster-parameters','$page?parameters-table=yes');");
    $html[]=$tpl->BigCircleCheckbox("ServerPersistentConnections",
        "{server_persistent_connections}",
        "{server_persistent_connections_explain}",
        $ServerPersistentConnections,"LoadAjaxSilent('hacluster-parameters','$page?parameters-table=yes');");

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

/** Repli seulement (route REST injoignable) : activation permise si le facteur d'echelle
 *  recopie par articarest (HaClusterWeightScaleActive, copie d'affichage) vaut 10. */
function hacluster_dynweight_can_enable(int $scaleActive):bool{
    return $scaleActive==10;
}
/** Etat du poids gradue lu par la route d'articarest (GET /hacluster/dynamicweight), qui lit le
 *  HAProxy en service. null si l'appel echoue ou si le JSON est invalide. */
function hacluster_dynweight_state():?array{
    $j=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/dynamicweight"),true);
    if(!is_array($j) || empty($j["status"])){return null;}
    if(!array_key_exists("enabled",$j) || !array_key_exists("can_enable",$j)){return null;}
    return $j;
}
function section_dynweight_popup():bool{
    $tpl=new template_admin();
    echo hacluster_meta_popup_head(array("AgentDynamicWeight"),$tpl);
    $page=CurrentPageName();
    $st=hacluster_dynweight_state();
    if($st!==null){
        $current=intval($st["enabled"])==1?1:0;
        $canEnable=!empty($st["can_enable"]);
        $partialIgnored=!empty($st["partial_weights_ignored"]);
        $metaHeld=!empty($st["meta_held"]);
        $reason=trim((string)($st["reason"] ?? ""));
    }else{
        // Repli : comportement d'avant (lecture des reglages).
        $current=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAgentDynamicWeight"))==1?1:0;
        $scale=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterWeightScaleActive"));
        $balance=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterBalance"));
        $canEnable=hacluster_dynweight_can_enable($scale);
        $partialIgnored=($balance=="roundrobin");
        $metaHeld=false;
        $reason="";
    }
    if(!$canEnable && $current!=1){
        $html[]=$tpl->div_warning("{hacluster_dynamic_weight_needs_scale}");
    }
    if(!$canEnable && $reason!=""){
        // Raison donnee par la route : texte anglais de demon, affiche tel quel, echappe.
        $html[]=$tpl->div_warning(htmlspecialchars($reason,ENT_QUOTES,"UTF-8"));
    }
    if($partialIgnored){
        $html[]=$tpl->div_warning("{hacluster_dynamic_weight_static_rr}");
    }
    // Case grisee si l'activation n'est pas permise ET que le reglage n'est pas deja actif, ou si Meta tient la cle.
    $disabled=((!$canEnable && $current!=1) || $metaHeld || hacluster_meta_post_held("AgentDynamicWeight"));
    $after="if(resp && String(resp).trim().length>0){alert(String(resp).trim());}"
        ."LoadAjaxSilent('hacluster-parameters','$page?parameters-table=yes');";
    $html[]=$tpl->BigCircleCheckbox("AgentDynamicWeight","{hacluster_dynamic_weight}",
        "{hacluster_dynamic_weight_explain}",$current,$after,"AsSquidAdministrator",$disabled);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function section_dynweight_save():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");return false;}
    if(hacluster_meta_gate("text")){return false;}
    // Champ retire par la porte Meta (cle tenue) : ne rien ecrire.
    if(!isset($_POST["AgentDynamicWeight"])){return false;}
    $requested=intval($_POST["AgentDynamicWeight"])==1?1:0;
    // La route decide sur le HAProxy en service, ecrit le reglage et pousse aux noeuds (notify/all).
    $resp=(string)$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/hacluster/dynamicweight",array("enabled"=>$requested));
    $j=json_decode($resp,true);
    if(is_array($j) && !empty($j["status"])){
        if(!empty($j["changed"])){
            admin_tracks(($requested==1?"Enable":"Disable")." graded weight of the proxies on the HaCluster");
        }
        return true;
    }
    // Refus (409), corps refuse (400), route absente ou socket injoignable : texte affiche par $after (alert).
    if(is_array($j) && !empty($j["error"])){
        echo (string)$j["error"];
        return false;
    }
    $raw=trim($resp);
    echo $raw==""?$tpl->javascript_parse_text("{protocol_error}"):substr($raw,0,300);
    return false;
}

function section_keepalive_save():bool{
    if(hacluster_meta_gate("text")){return false;}



    if(isset($_POST["ServerPersistentConnections"])){
        $ServerPersistentConnections=intval($_POST["ServerPersistentConnections"]);
        if($ServerPersistentConnections==1){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterDisableServerPersistentConnections",0);
        }else{
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterDisableServerPersistentConnections",1);
        }
    }
    if(isset($_POST["ClientPersistentConnections"])) {
        $ClientPersistentConnections=intval($_POST["ClientPersistentConnections"]);
        if ($ClientPersistentConnections == 1) {
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterDisableClientPersistentConnections", 0);
        } else {
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterDisableClientPersistentConnections", 1);
        }
    }
    return true;
}
function section_bbr_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{tcp_bbr}","$page?section-bbr-popup=yes",650);
}
function section_bbr_popup():bool{
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body("<div id='bbr-cluster-popup'>".section_bbr_inner()."</div>");
    return true;
}
/** JS apres enregistrement : BigCircleCheckbox ne met la reponse qu'en console,
 *  or section_bbr_save() n'ecrit rien en cas de succes -- une reponse non vide
 *  est un refus a montrer. */
function section_bbr_after_js(string $reload):string{
    return "if(resp && String(resp).trim().length>0){alert(String(resp).trim());}".$reload;
}
/** Tri-etat par deux cases : « Gerer » puis, seulement si gere, « Activer ». */
function section_bbr_inner():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $managed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterBBRManaged"))==1?1:0;
    $enable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterBBREnable"))==1?1:0;
    $after=section_bbr_after_js("LoadAjaxSilent('hacluster-parameters','$page?parameters-table=yes');"
        ."LoadAjaxSilent('bbr-cluster-popup','$page?section-bbr-inner=yes');");
    $html=array();
    $html[]="<div class='row'><div class='col-md-12'>".$tpl->div_explain("{tcp_bbr_explain}")."</div></div>";
    $html[]=$tpl->BigCircleCheckbox("HaClusterBBRManaged","{tcp_bbr_manage_cluster}","{tcp_bbr_cluster_explain}",
        $managed,$after,"AsSquidAdministrator");
    if($managed==1){
        $html[]=$tpl->BigCircleCheckbox("HaClusterBBREnable","{tcp_bbr_enable_cluster}","{tcp_bbr_kernel_required}",
            $enable,$after,"AsSquidAdministrator");
        $h="<table class='table' style='margin-top:15px'><thead><tr><th>{backend}</th><th>{status}</th></tr></thead><tbody>";
        $lb=hacluster_bbr_lb_state(hacluster_bbr_lb_status());
        $h.="<tr><td><strong>{this_server}</strong> (".htmlspecialchars(php_uname('n')).")</td><td>".hacluster_bbr_state_label($lb)."</td></tr>";
        foreach(hacluster_bbr_nodes() as $n){
            $h.="<tr><td>".htmlspecialchars($n["name"])."</td><td>".hacluster_bbr_state_label($n["state"])."</td></tr>";
        }
        $html[]=$h."</tbody></table>";
        // Le LB applique en arriere-plan : relire tant qu'il est « en attente ».
        if($lb=="pending"){
            $html[]="<script>setTimeout(function(){if(document.getElementById('bbr-cluster-popup')){LoadAjaxSilent('bbr-cluster-popup','$page?section-bbr-inner=yes');}},4000);</script>";
        }
    }
    return $tpl->_ENGINE_parse_body(@implode("\n",$html)).hacluster_meta_popup_head(array("HaClusterBBRManaged","HaClusterBBREnable"),$tpl);
}
/** Enregistre puis pousse tout de suite aux noeuds (notify/all est asynchrone
 *  cote daemon) au lieu d'attendre le cycle de 2 minutes. Rien en sortie si OK. */
function section_bbr_save():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");return false;}
    if(hacluster_meta_gate("text")){return false;}
    foreach(array("HaClusterBBRManaged","HaClusterBBREnable") as $k){
        if(isset($_POST[$k])){$GLOBALS["CLASS_SOCKETS"]->SET_INFO($k,intval($_POST[$k])==1?1:0);}
    }
    // Le LB applique lui-meme le reglage du cluster (sysctl.Build()), puis le
    // pousse aux noeuds sans attendre le cycle de 2 minutes.
    // nowait : sysctl.Build() depasse les 13 s du client REST, la case restait
    // en attente puis l'application etait abandonnee cote console.
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/bbr/apply?nowait=1");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
    return true;
}
// ---------------------------------------------------------------------------
// Onglet "Parameters" : vue recherchable (maquette stitch_hacluster_load_balancer_console).
// Un modele (hacp_model) calcule une fois toutes les lignes ; chaque widget est
// une fonction. La recherche tourne dans le navigateur : chaque ligne porte les
// libelles des champs de sa modale (data-kw), si bien que "client timeout"
// trouve la ligne TimeOuts.
// ---------------------------------------------------------------------------
function hacp_h($s):string{
    $s=htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
    return str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),$s);
}
function hacp_t(string $tok,array $args=array()):string{
    $tpl=new template_admin();
    $s=$tpl->_ENGINE_parse_body("{".$tok."}");
    foreach($args as $a){
        $p=strpos($s,"%s");
        if($p===false){break;}
        $s=substr_replace($s,(string)$a,$p,2);
    }
    return $s;
}
function hacp_info(string $k):string{
    return trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO($k));
}
function hacp_int(string $k):int{
    return intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($k));
}
function hacp_gbconfig():array{
    $a=@unserialize((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"),array("allowed_classes"=>false));
    if(!is_array($a)){$a=array();}
    foreach(array("HaClusterDisableProxyProtocol","HaClusterRemoveRealtimeLogs","HaClusterDecryptSSL",
                "HaClusterUseLBAsDNS","HaClusterUseHaClient","HaClusterClientMaxLoad",
                "HaClusterClientMaxLoadPeriod","HaClusterClientMoniCPU","HaClusterClientDisableCheckUrl",
                "sslcrtd_program_dbsize") as $k){
        if(!isset($a[$k])){$a[$k]=0;}
    }
    foreach(array("HaClusterCertif","DNS1","DNS2") as $k){
        if(!isset($a[$k]) || $a[$k]===null){$a[$k]="";}
    }
    return $a;
}
function hacp_reload_js():string{
    $page=CurrentPageName();
    return "if(document.getElementById('hacluster-parameters')){LoadAjaxSilent('hacluster-parameters','$page?parameters-table=yes');}";
}
// $chips : texte deja pret (tokens bruts, valeurs passees par hacp_h).
function hacp_row(string $id,int $sec,string $label,string $ico,string $js,$priv,string $state,array $chips,array $kw,string $attn=""):array{
    return array("id"=>$id,"sec"=>$sec,"label"=>$label,"ico"=>$ico,"js"=>$js,"priv"=>$priv,
        "state"=>$state,"chips"=>$chips,"kw"=>$kw,"attn"=>$attn);
}
function hacp_chip(string $text,bool $mono=false):array{
    return array("t"=>$text,"m"=>$mono);
}

function hacp_model():array{
    $page=CurrentPageName();
    $P="AsSquidAdministrator";
    $gb=hacp_gbconfig();
    $rows=array();
    $cert=array("configured"=>false,"id"=>0);

    // ---------- 1. Load-balancer: Service options
    $proto=hacp_info("HaClusterProto");
    $protos=array("tcp"=>"TCP/IP","http"=>"HTTP");
    if(!isset($protos[$proto])){$proto="tcp";}
    $iface=hacp_info("HaClusterInterface");
    $port=hacp_int("HaClusterPort");
    if($port==0){$port=3128;}
    $balance=hacp_info("HaClusterBalance");
    $algo=array("source"=>"{strict-hashed-ip}","roundrobin"=>"{round-robin}","leastconn"=>"{leastconn}");
    if(!isset($algo[$balance])){$balance="leastconn";}
    $ifaceT=$iface==""?"*":hacp_h($iface);
    $rows[]=hacp_row("listen",1,"{hacp_listen_browsers}",ico_interface,"Loadjs('$page?section-listen-js=yes')",$P,"val",
        array(hacp_chip($protos[$proto]),hacp_chip("$ifaceT:$port",true),hacp_chip($algo[$balance])),
        array("{listen_interface}","{listen_port}","{outgoing_interface}","{protocol}","{method}",
            "{strict-hashed-ip}","{round-robin}","{leastconn}"));

    // Service des agents (ClusterService)
    $csPort=hacp_int("ClusterServicePort");
    if($csPort==0){$csPort=58787;}
    $csIface=hacp_info("ClusterServiceInterface");
    $csIfaceT=$csIface==""?"*":hacp_h($csIface);
    $csCert=hacp_info("ClusterServiceCertificate");
    $agentsJs="Loadjs('fw.system.cluster.master.php?interface-js=yes')";
    $agentsKw=array("{listen_interface}","{listen_port}","{certificate}");
    if(strlen($csCert)>3){
        $cert["configured"]=true;
        $rows[]=hacp_row("agents",1,"{hacp_listen_agents}",ico_interface,$agentsJs,null,"val",
            array(hacp_chip("https://$csIfaceT:$csPort",true)),$agentsKw);
        include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
        $cdb=new CertificatesDB();
        list($exists,$CertID)=$cdb->exists($csCert);
        if(!$exists){$CertID=0;}
        $CertID=intval($CertID);
        $cert["id"]=$CertID;
        $count=$CertID>0?count((array)$cdb->subList($CertID)):0;
        $certT=hacp_h($csCert);
        $rows[]=hacp_row("srvcert",1,"{server_certificate}",ico_ssl,
            "Loadjs('fw.certificates-center.php?certificate-js=".rawurlencode($csCert)."&ID=$CertID')",null,"val",
            array(hacp_chip($certT,true)),array("{certificate}","{ssl_certificate}"),
            $CertID==0?"{hacp_attn_certgone}":"");
        $rows[]=hacp_row("clicerts",1,"{client_certificates}",ico_ssl,
            "Loadjs('fw.certificates-center.php?subcertificates-js=$CertID&OnlyClient=yes&NoPass=yes')",null,"val",
            $count>0?array(hacp_chip(hacp_t("hacp_n_issued",array($count)))):array(),array("{certificate}"),
            $count==0?"{hacp_attn_noclient}":"");
    }else{
        $rows[]=hacp_row("agents",1,"{hacp_listen_agents}",ico_interface,$agentsJs,null,"val",
            array(),$agentsKw,"{hacp_attn_nocert}");
    }

    $maxconn=hacp_int("HaClusterMaxConn");
    if($maxconn<2000){$maxconn=2000;}
    $workers=hacp_int("HaClusterWorkers");
    if($workers==0){$workers=1;}
    $threads=hacp_int("HaClusterTHreads");
    if($threads==0){$threads=1;}
    $rows[]=hacp_row("perf",1,"{performance}",ico_performance,"Loadjs('$page?section-performance-js=yes')",$P,"val",
        array(hacp_chip("{maxconn} $maxconn"),hacp_chip("{SquidCpuNumber} $workers"),hacp_chip("{ThreadsPerChild} $threads")),
        array("{maxconn}","{SquidCpuNumber}","{ThreadsPerChild}"));

    $acc=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterLimitAccesses"));
    if(!is_array($acc)){$acc=array("192.168.0.0/16","10.0.0.0/8","172.16.0.0/12");}
    $nacc=count($acc);
    if($nacc==0){
        $rows[]=hacp_row("access",1,"{limit_access}",ico_firewall,"Loadjs('$page?section-limit-access-js=yes')",$P,"off",
            array(),array("{networks}","{address}"));
    }else{
        $rows[]=hacp_row("access",1,"{limit_access}",ico_firewall,"Loadjs('$page?section-limit-access-js=yes')",$P,"on",
            array(hacp_chip(hacp_t("hacp_n_networks",array($nacc)))),array("{networks}","{address}"));
    }

    $rows[]=hacp_row_health();
    $tc=hacp_int("HaClusterTimeOutConnect");
    if($tc==0){$tc=10;}
    $ts=hacp_int("HaClusterSeverTimeout");
    if($ts==0){$ts=30;}
    $tcl=hacp_int("HaClusterClientTimeout");
    if($tcl==0){$tcl=300;}
    $rows[]=hacp_row("timeouts",1,"{hacp_timeouts}",ico_timeout,"Loadjs('$page?section-timeout-js=yes')",$P,"val",
        array(hacp_chip("{connect_timeout} $tc s"),hacp_chip("{server_timeout} $ts s"),hacp_chip("{client_timeout} $tcl s")),
        array("{connect_timeout}","{server_timeout}","{client_timeout}","{hacluster_tcp_keepalives}","{timeout}"));

    $pp=intval($gb["HaClusterDisableProxyProtocol"])==0;
    $rows[]=hacp_row("pproto",1,"{proxy_protocol}",ico_networks,"Loadjs('$page?section-pproto-js=yes')",$P,$pp?"on":"off",
        array(),array("PROXY protocol"));

    $rows[]=hacp_row_transparent();

    $asad=hacp_int("HaClusterAsAD");
    $adChips=array();
    if($asad==1){
        $adIf=hacp_info("HaClusterAsADIface");
        $adChips[]=hacp_chip("{interface} ".(strlen($adIf)<2?"{all}":hacp_h($adIf)));
    }
    $rows[]=hacp_row("simuad",1,"{simulate_the_active_directory}",ico_microsoft,"Loadjs('$page?section-simuad-js=yes')",$P,
        $asad==1?"on":"off",$adChips,array("{listen_interface}","Active Directory","LDAP","Kerberos"));

    // ---------- 2. Centralized configuration of backends
    $lbip=hacp_info("HaClusterUseAddr");
    if(strlen($lbip)<5){
        $rows[]=hacp_row("lbip",2,"{lbIP}",ico_interface,"Loadjs('$page?section-ipaddr-js=yes')",$P,"val",
            array(),array("{address}"),"{hacp_attn_notset}");
    }else{
        $rows[]=hacp_row("lbip",2,"{lbIP}",ico_interface,"Loadjs('$page?section-ipaddr-js=yes')",$P,"val",
            array(hacp_chip(hacp_h($lbip),true)),array("{address}"));
    }
    $rows[]=hacp_row("itchart",2,"{APP_ITCHARTER}",ico_html,"Loadjs('$page?section-itcharter-js=yes')",$P,
        hacp_int("HaClusterEnableITChart")==1?"on":"off",array(),array());
    $rows[]=hacp_row("replacls",2,"{acls_replication}",ico_rsync,"Loadjs('$page?section-replicacls-js=yes')",$P,
        hacp_int("HaClusterReplicateACLs")==1?"on":"off",array(),array("ACL"));
    $rows[]=hacp_row("kacli",2,"{client_persistent_connections}",ico_timeout,"Loadjs('$page?section-keepalive-js=yes')",$P,
        hacp_int("HaClusterDisableClientPersistentConnections")==0?"on":"off",array(),array("keep-alive"));
    $rows[]=hacp_row("kasrv",2,"{server_persistent_connections}",ico_timeout,"Loadjs('$page?section-keepalive-js=yes')",$P,
        hacp_int("HaClusterDisableServerPersistentConnections")==0?"on":"off",array(),array("keep-alive"));
    $rows[]=hacp_row("bbr",2,"{tcp_bbr}",ico_speed,"Loadjs('$page?section-bbr-js=yes')",$P,"val",
        array(hacp_chip(hacluster_bbr_summary())),array("{tcp_bbr_manage_cluster}","{tcp_bbr_enable_cluster}"));
    $rows[]=hacp_row_haclient($gb);
    $rows[]=hacp_row("dynweight",2,"{hacluster_dynamic_weight}",ico_weight,"Loadjs('$page?section-dynweight-js=yes')",$P,
        hacp_int("HaClusterAgentDynamicWeight")==1?"on":"off",array(),array("{hacluster_dynamic_weight_explain}"));
    $rows[]=hacp_row("inetcheck",2,"{lb_internet_check}",ico_health_check,"Loadjs('$page?section-haclient-js=yes')",$P,
        hacp_int("HaClusterDisableInfraCheck")==1?"off":"on",array(),array("{lb_internet_check_disable}","{urlsTotest}"));

    $sslChips=array();
    $ssl=intval($gb["HaClusterDecryptSSL"])==1;
    if($ssl){
        $db=intval($gb["sslcrtd_program_dbsize"]);
        if($db==0){$db=8;}
        $sslChips[]=hacp_chip("{proxy_certificate}: ".hacp_h($gb["HaClusterCertif"]));
        $sslChips[]=hacp_chip("{hacp_cert_cache} $db MB");
    }
    $rows[]=hacp_row("ssl",2,"{activate_ssl_on_http_port}",ico_ssl,"Loadjs('$page?section-ssl-js=yes')",$P,
        $ssl?"on":"off",$sslChips,array("{proxy_certificate}","{sslcrtd_program_dbsize}","SSL","TLS"));

    $rows[]=hacp_row_categories();
    $rows[]=hacp_row_dns($gb);
    $rows[]=hacp_row_errorpage();

    // ---------- 3. Access logs and retention
    $sinkChips=array();
    $sink=hacp_int("LogSinkClient")==1;
    if($sink){
        $sproto=hacp_int("LogSinkClientTCP")==1?"tcp":"udp";
        $sinkChips[]=hacp_chip("$sproto://".hacp_h(hacp_info("LogSinClientServer")).":".hacp_int("LogSinkClientPort"),true);
    }
    $rows[]=hacp_row("logsink",3,"{logs_sink}",ico_forward,"Loadjs('fw.proxy.rotate.php?main-logsink-js=yes')",null,
        $sink?"on":"off",$sinkChips,array("syslog","rsyslog"));
    $rows[]=hacp_row("rtlogs",3,"{disable_realtime_backends}",ico_eye,"Loadjs('$page?section-logs-js=yes')",$P,
        intval($gb["HaClusterRemoveRealtimeLogs"])==1?"on":"off",array(),array("{realtime}"));
    $rows[]=hacp_row_retention();

    $attn=array();
    foreach($rows as $r){
        if($r["attn"]<>""){$attn[]=$r;}
    }
    $tpl=new template_admin();
    return array("rows"=>$rows,"attn"=>$attn,"cert"=>$cert,"can"=>$tpl->IsSecurity($P));
}

function hacp_row_health():array{
    $page=CurrentPageName();
    $int=hacp_int("HaClusterCheckInt");
    if($int==0){$int=10;}
    if($int<5){$int=5;}
    $fall=hacp_int("HaClusterCheckFall");
    if($fall==0){$fall=5;}
    $rise=hacp_int("HaClusterCheckRise");
    if($rise==0){$rise=2;}
    $reload=hacp_int("HaClusterReloadEach");
    $c=array();
    if(hacp_int("HaClusterDisableWatchDog")==0){$c[]=hacp_chip("{verify_the_full_chain}");}
    if($reload>=3 && $reload<24){$c[]=hacp_chip(hacp_t("hacp_reload_each",array($reload)));}
    if(hacp_int("HaClusterCheckRestart")==1){$c[]=hacp_chip("{restart_if_needed}");}
    $c[]=hacp_chip("{check_interval} $int s");
    $c[]=hacp_chip("{fall} $fall");
    $c[]=hacp_chip("{rise} $rise");
    return hacp_row("health",1,"{health_checks_small}",ico_watchdog,"Loadjs('$page?section-health-js=yes')","AsSquidAdministrator","val",
        $c,array("{EnableWatchdog}","{check_url}","{check_interval}","{fall}","{rise}","{backends}","{reload_service}","{restart_if_needed}"));
}
function hacp_row_transparent():array{
    $kw=array("{masquerading}","{certificate}","{debug}","{transparent_mode}");
    $js="Loadjs('fw.hacluster.transparent.php?params-js=yes')";
    if(hacp_int("HaClusterTransParentMode")==0){
        return hacp_row("transp",1,"{transparent_mode}",ico_sensor,$js,"AsSquidAdministrator","off",array(),$kw);
    }
    $c=array();
    if(hacp_int("HaClusterTransParentMasquerade")==1){$c[]=hacp_chip("{masquerading}");}
    $crt=hacp_info("HaClusterTransParentCertif");
    if(strlen($crt)>1){$c[]=hacp_chip("{certificate} ".hacp_h($crt));}
    $attn=hacp_int("HaClusterTransParentDebug")==1?"{hacp_attn_debug}":"";
    return hacp_row("transp",1,"{transparent_mode}",ico_sensor,$js,"AsSquidAdministrator","on",$c,$kw,$attn);
}
function hacp_row_haclient(array $gb):array{
    $page=CurrentPageName();
    $kw=array("{use_haclusterclient}","{Max_Load}","{monitor_cpu_usage}","{urlsTotest}","{period}");
    $js="Loadjs('$page?section-haclient-js=yes')";
    if(intval($gb["HaClusterUseHaClient"])==0){
        return hacp_row("haclient",2,"{APP_HACLUSTER_CLIENT}",ico_watchdog,$js,"AsSquidAdministrator","off",array(),$kw);
    }
    $max=intval($gb["HaClusterClientMaxLoad"]);
    if($max==0){$max=3;}
    $periods=array(0=>"{realtime}",5=>"5 {minutes}",15=>"15 {minutes}");
    $per=intval($gb["HaClusterClientMaxLoadPeriod"]);
    $c=array();
    if(intval($gb["HaClusterClientMoniCPU"])==1){$c[]=hacp_chip("{monitor_cpu_usage}");}
    $c[]=hacp_chip("{Max_Load} $max");
    if(isset($periods[$per])){$c[]=hacp_chip("{period} ".$periods[$per]);}
    if(intval($gb["HaClusterClientDisableCheckUrl"])==1){$c[]=hacp_chip("{urlsTotestDisabled}");}
    return hacp_row("haclient",2,"{APP_HACLUSTER_CLIENT}",ico_watchdog,$js,"AsSquidAdministrator","on",$c,$kw);
}
function hacp_row_categories():array{
    $kw=array("{remote_server_port}","{use_ssl}","{debug}");
    $js="Loadjs('".CurrentPageName()."?remote-categories-service=yes')";
    if(hacp_int("HaClusterUseRemoteCategoriesService")==0){
        return hacp_row("categories",2,"{use_remote_categories_services}",ico_database,$js,"AsSquidAdministrator","off",array(),$kw);
    }
    $proto=hacp_int("RemoteCategoriesServiceSSL")==1?"https":"http";
    $url="$proto://".hacp_h(hacp_info("RemoteCategoriesServiceAddr")).":".hacp_int("RemoteCategoriesServicePort");
    return hacp_row("categories",2,"{use_remote_categories_services}",ico_database,$js,"AsSquidAdministrator","on",
        array(hacp_chip($url,true)),$kw);
}
function hacp_row_dns(array $gb):array{
    $page=CurrentPageName();
    $kw=array("{primary_dns}","{secondary_dns}","{InternalDomain}","{nameserver}","{use_load_balancer_as_dns}",
        "{hacluster_lb_dns}","{proxy_use_its_own_dns}","{local_dns_service}");
    $js="Loadjs('$page?section-dns-js=yes')";
    $serve=hacp_int("HaClusterServeDNS");
    $useLB=intval($gb["HaClusterUseLBAsDNS"]);
    if($serve==1){$useLB=0;}
    if($useLB==1){
        return hacp_row("dns",2,"{dns_servers}",ico_database,$js,"AsSquidAdministrator","val",
            array(hacp_chip("{use_load_balancer_as_dns}")),$kw);
    }
    $c=array();
    $attn="";
    if($serve==1){
        $c[]=hacp_chip("{hacluster_lb_dns}");
        if(count(hacluster_dns_loops_in($gb,1))>0){$attn="{hacp_attn_dnsloop}";}
    }
    $own=hacp_int("HaClusterProxyUseOwnDNS");
    if($own==1 && hacp_int("HaClusterProxyUseBackendDNSDIST")==1){$c[]=hacp_chip("{use_local_dns_service}");}
    if(hacp_int("HaClusterUseLocalDNSCache")==1){$c[]=hacp_chip("{local_dns_service}");}
    foreach(array("DNS1","DNS2") as $k){
        if(trim((string)$gb[$k])<>""){$c[]=hacp_chip(hacp_h($gb[$k]),true);}
    }
    if($own==1){
        $fa=array();
        foreach(array("HaClusterSquidNameServer1","HaClusterSquidNameServer2","HaClusterSquidNameServer3") as $k){
            $v=hacp_info($k);
            if($v<>""){$fa[]=hacp_h($v);}
        }
        if(count($fa)>0){$c[]=hacp_chip("{hacp_proxy_dns}: ".implode(", ",$fa));}
    }
    if(count($c)==0){$c[]=hacp_chip("{hacp_dns_default}");}
    return hacp_row("dns",2,"{dns_servers}",ico_database,$js,"AsSquidAdministrator","val",$c,$kw,$attn);
}
function hacp_row_errorpage():array{
    $kw=array("{hostname}","{listen_port}","{ssl_certificate}","{useSSL}");
    $js="Loadjs('".CurrentPageName()."?section-errorpage-js=yes')";
    if(hacp_int("HaClusterUseCentralErrorPage")==0){
        return hacp_row("errpage",2,"{error_page_service}",ico_web_error_page,$js,"AsSquidAdministrator","off",array(),$kw);
    }
    $http=hacp_int("HaClusterErrorPageHTTPPort");
    if($http==0){$http=80;}
    $https=hacp_int("HaClusterErrorPageHTTPsPort");
    if($https==0){$https=443;}
    $host=hacp_info("HaClusterErrorPageServiceHostname");
    if(strlen($host)<2){$host=hacp_info("myhostname");}
    $c=array();
    if(hacp_int("HaClusterErrorPageUseSSL")==1){
        $crt=hacp_info("HaClusterErrorPageCert");
        if(preg_match("#^SUB:([0-9]+)#",$crt,$re)){
            include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
            $cdb=new CertificatesDB();
            $sub=$cdb->subGetById(intval($re[1]));
            $crt=is_object($sub)&&isset($sub->commonName)?(string)$sub->commonName:"";
        }
        $c[]=hacp_chip("https://".hacp_h($host).":$https",true);
        if($crt<>""){$c[]=hacp_chip("{certificate} ".hacp_h($crt));}
    }else{
        $c[]=hacp_chip("http://".hacp_h($host).":$http",true);
    }
    return hacp_row("errpage",2,"{error_page_service}",ico_web_error_page,$js,"AsSquidAdministrator","on",$c,$kw);
}
function hacp_row_retention():array{
    $size=hacp_int("HaClusterRemoveLogsSize");
    if($size==0){$size=500;}
    $port=hacp_int("HaClusterRemoteSyslogPort");
    if($port==0){$port=514;}
    $c=array();
    if(hacp_int("HaClusterRemoveLogsEnabled")==1){
        $c[]=hacp_chip("{remove_local_access_events} ($size MB)");
    }else{
        $c[]=hacp_chip("{use_default}");
    }
    if(hacp_int("HaClusterRemoteSyslogEnabled")==1){
        $c[]=hacp_chip("syslog ".hacp_h(hacp_info("HaClusterRemoteSyslogAddr")).":$port",true);
    }
    return hacp_row("retention",3,"{log_retention}",ico_eye,"Loadjs('fw.hacluster.logs.php?js=yes')","AsSquidAdministrator","val",
        $c,array("{remove_local_access_events}","syslog"));
}

// ---------- Widgets
function hacp_searchbar(array $m):string{
    $total=count($m["rows"]);
    $na=count($m["attn"]);
    $badge=$na>0?"<span class='hacp-fbadge'>$na</span>":"";
    $f=array("all"=>"{hacp_f_all}","on"=>"{hacp_f_on}","off"=>"{hacp_f_off}","attn"=>"{hacp_f_attn}");
    $chips=array();
    foreach($f as $k=>$lbl){
        $checked=$k=="all"?"true":"false";
        $dot="";
        if($k=="on"){$dot="<span class='ak-dot ak-ok'></span>";}
        if($k=="off"){$dot="<span class='ak-dot ak-off'></span>";}
        $extra=$k=="attn"?$badge:"";
        $chips[]="<button type='button' role='radio' aria-checked='$checked' data-f='$k' class='hacp-fchip'>$dot$lbl$extra</button>";
    }
    return "<div class='hacp-bar'>
        <div class='hacp-search'>
            <label for='hacp-q' class='hacp-sr'>{hacp_search_label}</label>
            <span class='hacp-qico'><i class='fas fa-magnifying-glass'></i></span>
            <input id='hacp-q' type='search' autocomplete='off' spellcheck='false' placeholder=\"{hacp_search_ph}\">
            <button type='button' class='hacp-clear' id='hacp-clear' aria-label=\"{hacp_clear}\" hidden><i class='fas fa-xmark'></i></button>
            <kbd class='hacp-kbd' title=\"{hacp_kbd}\">/</kbd>
        </div>
        <div class='hacp-filters' role='radiogroup' aria-label=\"{hacp_filter_label}\">".implode("",$chips)."</div>
        <div class='hacp-counter' id='hacp-counter' aria-live='polite'>".hacp_t("hacp_counter",array($total,$total))."</div>
    </div>";
}
function hacp_banner(array $m):string{
    $n=count($m["attn"]);
    if($n==0){return "";}
    $title=$n==1?hacp_t("hacp_attn_title1"):hacp_t("hacp_attn_title",array($n));
    $li=array();
    foreach($m["attn"] as $r){
        $li[]="<li><button type='button' class='hacp-blink' OnClick=\"HacpGo('hacp-r-{$r["id"]}')\">"
            ."<i class='fas fa-arrow-right'></i><span><b>{$r["label"]}:</b> {$r["attn"]}</span></button></li>";
    }
    return "<section class='hacp-banner' role='status'>
        <span class='hacp-bico'><i class='fas fa-triangle-exclamation'></i></span>
        <div><div class='hacp-btitle'>$title</div><ul>".implode("",$li)."</ul></div>
    </section>";
}
function hacp_empty():string{
    return "<div class='hacp-empty' id='hacp-empty' hidden>
        <span class='hacp-eico'><i class='fas fa-filter-circle-xmark'></i></span>
        <div class='hacp-etitle' id='hacp-etitle'></div>
        <button type='button' class='ak-btn' id='hacp-ereset'><i class='fas fa-xmark'></i> {hacp_clear}</button>
    </div>";
}
function hacp_cert_actions(array $m):string{
    if(!$m["can"]){return "";}
    $page=CurrentPageName();
    $b=array();
    if(!$m["cert"]["configured"] || $m["cert"]["id"]==0){
        $js=str_replace('"',"'",CreateCertifJs());
        $b[]="<button type='button' class='ak-btn' OnClick=\"$js\"><i class='fas fa-certificate'></i> {create_certificate}</button>";
    }
    if($m["cert"]["configured"]){
        $b[]="<button type='button' class='ak-btn ak-btn-danger' OnClick=\"Loadjs('$page?hacp-reset-js=yes')\"><i class='fas fa-rotate'></i> {hacp_reset_cert}</button>";
    }
    return "<div class='hacp-pki' id='hacp-pki'>
        <span class='hacp-pki-t'><i class='fas fa-shield-halved'></i><b>{hacp_pki}</b></span>
        <span class='hacp-pki-b'>".implode("",$b)."</span>
    </div>";
}
function hacp_pill(string $state):string{
    if($state=="on"){return "<span class='ak-pill ak-ok'><i class='fas fa-circle-check'></i> {hacp_f_on}</span>";}
    if($state=="off"){return "<span class='ak-pill ak-off'><i class='fas fa-circle-minus'></i> {hacp_f_off}</span>";}
    return "";
}
function hacp_row_html(array $r,template_admin $tpl):string{
    $click=$r["js"]<>"" && $tpl->IsSecurity($r["priv"]);
    $vals=array();
    $p=hacp_pill($r["state"]);
    if($p<>""){$vals[]=$p;}
    foreach($r["chips"] as $c){
        $cls=$c["m"]?"hacp-chip hacp-mono hacp-hl":"hacp-chip hacp-hl";
        $vals[]="<span class='$cls'>{$c["t"]}</span>";
    }
    if($r["attn"]<>""){
        $vals[]="<span class='ak-pill ak-warn'><i class='fas fa-triangle-exclamation'></i> {$r["attn"]}</span>";
    }
    $attn=$r["attn"]<>""?"1":"0";
    $cls="hacp-row".($r["attn"]<>""?" hacp-row-attn":"");
    $kw=implode("|",$r["kw"]);
    $inner="<span class='hacp-ico'><i class='{$r["ico"]}'></i></span>
        <span class='hacp-main'>
            <span class='hacp-lbl hacp-hl'>{$r["label"]}</span>
            <span class='hacp-vals'>".implode("",$vals)."</span>
            <span class='hacp-kwm' hidden></span>
        </span>";
    if($click){
        return "<div class='$cls' id='hacp-r-{$r["id"]}' data-state='{$r["state"]}' data-attn='$attn' data-kw=\"$kw\">
            <button type='button' class='hacp-btn' OnClick=\"{$r["js"]}\">$inner<span class='hacp-chev'><i class='fas fa-chevron-right'></i></span></button>
        </div>";
    }
    return "<div class='$cls' id='hacp-r-{$r["id"]}' data-state='{$r["state"]}' data-attn='$attn' data-kw=\"$kw\">
        <div class='hacp-btn hacp-ro'>$inner<span class='hacp-chev' title=\"{hacp_readonly}\"><i class='fas fa-lock'></i></span></div>
    </div>";
}
function hacp_section(array $m,int $sec):string{
    $titles=array(1=>"{APP_PARENTLB}: {service_options}",2=>"{CentralizedBackendsSettings}",3=>"{hacp_sec_logs}");
    $icons=array(1=>"fas fa-server",2=>"fas fa-network-wired",3=>"fas fa-file-lines");
    $tpl=new template_admin();
    $rows=array();
    foreach($m["rows"] as $r){
        if($r["sec"]==$sec){$rows[]=hacp_row_html($r,$tpl);}
    }
    $n=count($rows);
    $extra=$sec==1?hacp_cert_actions($m):"";
    return "<section class='ak-card hacp-sec' data-sec='$sec'>
        <header class='hacp-sec-h'>
            <span class='hacp-sec-ico hacp-sec-ico$sec'><i class='{$icons[$sec]}'></i></span>
            <h3>{$titles[$sec]}</h3>
            <span class='hacp-sec-cnt' data-total='$n'>".hacp_t("hacp_sec_count",array($n))."</span>
        </header>
        $extra
        <div class='hacp-sec-none' hidden>{hacp_sec_nomatch}</div>
        <div class='hacp-grid'>".implode("\n",$rows)."</div>
    </section>";
}
function hacp_style():string{
    $ac=array("accent"=>"#10b981","accent_soft"=>"rgba(16,185,129,.12)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){$ac=brand_ui_accents(brand_palette());}
    $a=$ac["accent"];
    $as=$ac["accent_soft"];
    return "<style>
.hacp{--hacp-bd:#e2e8f0;--hacp-tx:#0f172a;--hacp-tx2:#475569;--hacp-focus:#2563eb;color:var(--hacp-tx);font-size:13px;padding:8px 0 24px}
.hacp-sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
.hacp-bar{position:sticky;top:0;z-index:5;display:flex;align-items:center;gap:16px;flex-wrap:wrap;background:#fff;border:1px solid var(--hacp-bd);border-radius:10px;padding:10px 16px;min-height:56px;box-shadow:0 1px 2px rgba(15,23,42,.06);margin-bottom:16px}
.hacp-search{position:relative;flex:1 1 360px;display:flex;align-items:center;min-width:280px}
.hacp-qico{position:absolute;left:10px;width:16px;height:16px;color:#94a3b8;display:flex;align-items:center}
.hacp-qico svg,.hacp-qico i{width:15px;height:15px}
.hacp-search input{width:100%;height:38px;border:1px solid var(--hacp-bd);border-radius:8px;padding:0 70px 0 34px;font-size:14px;color:var(--hacp-tx);background:#f8fafc;outline:none}
.hacp-search input:focus{border-color:var(--hacp-focus);box-shadow:0 0 0 2px rgba(37,99,235,.35);background:#fff}
.hacp-search input::-webkit-search-cancel-button{display:none}
.hacp-clear{position:absolute;right:38px;width:24px;height:24px;border:0;background:transparent;color:#64748b;border-radius:6px;cursor:pointer;display:flex;align-items:center;justify-content:center}
.hacp-clear svg{width:12px;height:12px}
.hacp-clear:hover{background:#f1f5f9}
.hacp-kbd{position:absolute;right:10px;font:600 12px/1 ui-monospace,monospace;color:#64748b;border:1px solid var(--hacp-bd);border-bottom-width:2px;border-radius:4px;padding:3px 6px;background:#fff}
.hacp-filters{display:flex;gap:2px;background:#f1f5f9;border:1px solid var(--hacp-bd);border-radius:8px;padding:3px}
.hacp-fchip{border:0;background:transparent;border-radius:6px;padding:6px 12px;font-size:13px;font-weight:500;color:var(--hacp-tx2);cursor:pointer;display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
.hacp-fchip[aria-checked=true]{background:#fff;color:var(--hacp-tx);font-weight:600;box-shadow:0 1px 2px rgba(15,23,42,.12)}
.hacp-fchip:focus-visible,.hacp-btn:focus-visible,.hacp-blink:focus-visible,.hacp-clear:focus-visible{outline:2px solid var(--hacp-focus);outline-offset:2px}
.hacp-fbadge{background:#fef3c7;color:#b45309;border:1px solid #fcd34d;border-radius:999px;font-size:11px;font-weight:700;padding:0 7px;line-height:18px}
.hacp-counter{margin-left:auto;font:500 13px/1 ui-monospace,'JetBrains Mono',monospace;color:var(--hacp-tx2);white-space:nowrap}
.hacp-counter b{color:var(--hacp-tx)}
.hacp-banner{display:flex;gap:14px;background:#fffbeb;border:1px solid #fde68a;border-left:4px solid #d97706;border-radius:10px;padding:14px 18px;margin-bottom:16px}
.hacp-bico{width:34px;height:34px;flex:0 0 34px;border-radius:8px;background:#fef3c7;color:#b45309;display:flex;align-items:center;justify-content:center}
.hacp-bico svg{width:16px;height:16px}
.hacp-btitle{font-size:15px;font-weight:600;color:#92400e;margin:6px 0 6px}
.hacp-banner ul{list-style:none;margin:0;padding:0}
.hacp-banner li{margin:2px 0}
.hacp-blink{border:0;background:transparent;padding:3px 4px;color:#92400e;cursor:pointer;display:inline-flex;gap:8px;align-items:center;text-align:left;font-size:13px;border-radius:4px}
.hacp-blink:hover span{text-decoration:underline}
.hacp-blink svg{width:12px;height:12px;color:#d97706}
.hacp-empty{background:#fff;border:1px dashed #cbd5e1;border-radius:12px;padding:40px 24px;text-align:center;margin-bottom:16px}
.hacp-eico{display:inline-flex;width:44px;height:44px;border-radius:50%;background:#f1f5f9;color:#64748b;align-items:center;justify-content:center;margin-bottom:10px}
.hacp-eico svg{width:20px;height:20px}
.hacp-etitle{font-size:15px;font-weight:600;margin-bottom:12px}
.hacp-sec{padding:20px 24px 24px;margin-bottom:16px}
.hacp-sec[hidden]{display:none}
.hacp-sec-h{display:flex;align-items:center;gap:12px;padding-bottom:14px;margin-bottom:16px;border-bottom:1px solid #f1f5f9}
.hacp-sec-h h3{margin:0;font-size:18px;font-weight:600;color:var(--hacp-tx);flex:1;text-transform:none}
.hacp-sec-ico{width:36px;height:36px;flex:0 0 36px;border-radius:9px;display:flex;align-items:center;justify-content:center}
.hacp-sec-ico svg{width:17px;height:17px}
.hacp-sec-ico1{background:#eff6ff;color:#2563eb}.hacp-sec-ico2{background:$as;color:$a}.hacp-sec-ico3{background:#e6eeed;color:#005447}
.hacp-sec-cnt{font:500 12px/1 ui-monospace,'JetBrains Mono',monospace;color:var(--hacp-tx2);background:#f1f5f9;border-radius:999px;padding:6px 12px;white-space:nowrap}
.hacp-sec-none{color:#64748b;font-style:italic;padding:4px 0}
.hacp-pki{display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:#f8fafc;border:1px solid var(--hacp-bd);border-radius:8px;padding:10px 14px;margin-bottom:16px}
.hacp-pki-t{display:flex;align-items:center;gap:8px;flex:1;color:var(--hacp-tx2)}
.hacp-pki-t svg{width:14px;height:14px;color:#64748b}
.hacp-pki-t b{color:var(--hacp-tx);font-weight:600}
.hacp-pki-b{display:flex;gap:8px}
.hacp-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.hacp-grid[hidden]{display:none}
@media (max-width:1200px){.hacp-grid{grid-template-columns:minmax(0,1fr)}}
.hacp-row[hidden]{display:none}
.hacp-btn{width:100%;height:100%;display:flex;align-items:flex-start;gap:14px;text-align:left;background:#fff;border:1px solid var(--hacp-bd);border-radius:8px;padding:14px 16px;min-height:64px;cursor:pointer;color:inherit;font:inherit;transition:background-color .12s,border-color .12s}
.hacp-btn:hover{background:#f8fafc;border-color:#cbd5e1}
.hacp-ro{cursor:default}
.hacp-ro:hover{background:#fff;border-color:var(--hacp-bd)}
.hacp-row-attn .hacp-btn{border-left:3px solid #d97706}
.hacp-row.hacp-flash .hacp-btn{box-shadow:0 0 0 3px rgba(217,119,6,.45)}
.hacp-ico{width:34px;height:34px;flex:0 0 34px;border-radius:8px;background:#f1f5f9;color:#475569;display:flex;align-items:center;justify-content:center}
.hacp-ico svg,.hacp-ico i{width:16px;height:16px;font-size:16px}
.hacp-main{flex:1;min-width:0;display:flex;flex-direction:column;gap:6px}
.hacp-lbl{font-size:14px;font-weight:600;color:var(--hacp-tx);line-height:20px;text-transform:none}
.hacp-vals{display:flex;flex-wrap:wrap;gap:6px}
.hacp-chip{display:inline-block;max-width:100%;overflow-wrap:anywhere;background:#f1f5f9;color:var(--hacp-tx2);border-radius:999px;padding:2px 10px;font-size:13px;line-height:20px;text-transform:none}
.hacp-mono{font-family:'JetBrains Mono',ui-monospace,monospace;font-size:12.5px;color:#1e293b}
.hacp-vals .ak-pill{padding:2px 10px;line-height:20px}
.hacp-kwm{font-size:12.5px;color:#64748b}
.hacp-kwm[hidden]{display:none}
.hacp mark{background:#fef08a;color:inherit;font-weight:700;padding:0 1px;border-radius:2px}
.hacp-chev{flex:0 0 16px;color:#94a3b8;align-self:center;display:flex}
.hacp-chev svg{width:12px;height:12px}
@media (prefers-reduced-motion:reduce){.hacp-btn{transition:none}}
</style>";
}
function hacp_js():string{
    $keys=array("counter"=>"hacp_counter","secCount"=>"hacp_sec_count","secMatch"=>"hacp_sec_match",
        "matches"=>"hacp_matches","emptyQ"=>"hacp_empty_q","emptyF"=>"hacp_empty_f");
    $I=array();
    foreach($keys as $k=>$tok){$I[$k]=hacp_t($tok);}
    $json=json_encode($I,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
    $js=<<<'JS'
<script>
(function(){
var root=document.getElementById('hacp-root');if(!root){return;}
var I=__I18N__,dec=document.createElement('textarea');
for(var k in I){if(I.hasOwnProperty(k)){dec.innerHTML=I[k];I[k]=dec.value;}}
var S=window.HacpState||(window.HacpState={q:'',f:'all'});
var q=document.getElementById('hacp-q'),clr=document.getElementById('hacp-clear');
function fmt(s,a){var i=0;return s.replace(/%s/g,function(){return a[i++];});}
function esc(s){return String(s).replace(/[&<>"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];});}
function rx(t){return new RegExp('('+t.map(function(x){return x.replace(/[.*+?^${}()|[\]\\]/g,'\\$&');}).join('|')+')','gi');}
function mark(t,re){if(!re){return esc(t);}var p=t.split(re),o='';for(var i=0;i<p.length;i++){o+=(i%2)?'<mark>'+esc(p[i])+'</mark>':esc(p[i]);}return o;}
function orig(el){var v=el.getAttribute('data-orig');if(v===null){v=el.textContent;el.setAttribute('data-orig',v);}return v;}
function apply(){
  var qq=S.q.toLowerCase().replace(/^\s+|\s+$/g,''),terms=qq?qq.split(/\s+/):[],re=terms.length?rx(terms):null;
  var active=terms.length>0||S.f!=='all',shown=0,total=0;
  var secs=root.querySelectorAll('.hacp-sec');
  for(var s=0;s<secs.length;s++){
    var rows=secs[s].querySelectorAll('.hacp-row'),ns=0;
    for(var r=0;r<rows.length;r++){
      var row=rows[r],st=row.getAttribute('data-state'),at=row.getAttribute('data-attn')==='1';total++;
      var okF=S.f==='all'||(S.f==='on'&&st==='on')||(S.f==='off'&&st==='off')||(S.f==='attn'&&at);
      var hl=row.querySelectorAll('.hacp-hl'),vis='';
      for(var h=0;h<hl.length;h++){vis+=' '+orig(hl[h]).toLowerCase();}
      var kws=(row.getAttribute('data-kw')||'').split('|'),okQ=true,hid=[];
      for(var t=0;t<terms.length;t++){
        if(vis.indexOf(terms[t])>=0){continue;}
        var f=null;for(var j=0;j<kws.length;j++){if(kws[j]&&kws[j].toLowerCase().indexOf(terms[t])>=0){f=kws[j];break;}}
        if(f===null){okQ=false;break;}if(hid.indexOf(f)<0){hid.push(f);}
      }
      var show=okF&&okQ;row.hidden=!show;if(show){ns++;}
      for(h=0;h<hl.length;h++){hl[h].innerHTML=mark(orig(hl[h]),show?re:null);}
      var km=row.querySelector('.hacp-kwm');
      if(show&&hid.length){km.innerHTML=esc(I.matches)+' '+hid.map(function(x){return mark(x,re);}).join(', ');km.hidden=false;}else{km.innerHTML='';km.hidden=true;}
    }
    var cnt=secs[s].querySelector('.hacp-sec-cnt'),tot=cnt.getAttribute('data-total');
    cnt.textContent=active?fmt(I.secMatch,[ns,tot]):fmt(I.secCount,[tot]);
    secs[s].querySelector('.hacp-sec-none').hidden=ns>0;
    secs[s].querySelector('.hacp-grid').hidden=ns===0;
    shown+=ns;
  }
  for(s=0;s<secs.length;s++){secs[s].hidden=(shown===0);}
  document.getElementById('hacp-counter').innerHTML=esc(fmt(I.counter,['\u0001'+shown+'\u0002',total])).replace('\u0001','<b>').replace('\u0002','</b>');
  var em=document.getElementById('hacp-empty');em.hidden=shown>0;
  if(shown===0){document.getElementById('hacp-etitle').textContent=terms.length?fmt(I.emptyQ,[S.q]):I.emptyF;}
  var pki=document.getElementById('hacp-pki');if(pki){pki.hidden=active;}
  clr.hidden=!S.q;
  var ch=root.querySelectorAll('.hacp-fchip');for(var c=0;c<ch.length;c++){ch[c].setAttribute('aria-checked',ch[c].getAttribute('data-f')===S.f?'true':'false');}
}
function reset(){S.q='';S.f='all';q.value='';apply();}
q.value=S.q;
q.addEventListener('input',function(){S.q=q.value;apply();});
q.addEventListener('keydown',function(e){if(e.key==='Escape'){S.q='';q.value='';apply();}});
clr.addEventListener('click',function(){S.q='';q.value='';apply();q.focus();});
document.getElementById('hacp-ereset').addEventListener('click',function(){reset();q.focus();});
var ch=root.querySelectorAll('.hacp-fchip');
for(var c=0;c<ch.length;c++){ch[c].addEventListener('click',function(){S.f=this.getAttribute('data-f');apply();});}
window.HacpGo=function(id){
  var row=document.getElementById(id);if(!row){return;}
  if(row.hidden){reset();}
  row.scrollIntoView({block:'center',behavior:(window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches)?'auto':'smooth'});
  row.className+=' hacp-flash';setTimeout(function(){row.className=row.className.replace(' hacp-flash','');},1600);
  var b=row.querySelector('button.hacp-btn');if(b){b.focus({preventScroll:true});}
};
if(!window.HacpKeyBound){
  window.HacpKeyBound=true;
  document.addEventListener('keydown',function(e){
    if(e.key!=='/'||e.ctrlKey||e.metaKey||e.altKey){return;}
    var t=e.target,tag=t&&t.tagName?t.tagName.toLowerCase():'';
    if(tag==='input'||tag==='textarea'||tag==='select'||(t&&t.isContentEditable)){return;}
    var x=document.getElementById('hacp-q');if(!x||!x.offsetParent){return;}
    e.preventDefault();x.focus();
  });
}
apply();
})();
</script>
JS;
    return str_replace("__I18N__",$json,$js);
}

function parameters_table():bool{
    $tpl=new template_admin();
    $m=hacp_model();
    $html=array();
    $html[]="<div class='ak hacp' id='hacp-root'>";
    $html[]=hacp_searchbar($m);
    $html[]=hacp_banner($m);
    $html[]=hacp_empty();
    $html[]=hacp_section($m,1);
    $html[]=hacp_section($m,2);
    $html[]=hacp_section($m,3);
    $html[]="</div>";
    echo hacluster_meta_banner($tpl).hacp_style().$tpl->_ENGINE_parse_body(implode("\n",$html)).hacp_js();
    return true;
}

// Reinitialisation du certificat du service des agents : confirmation, droit
// verifie cote serveur, puis progression via le daemon.
function hacp_reset_js():bool{
    $tpl=new template_admin();
    $after=$tpl->framework_buildjs("/hacluster/server/resetcert",
        "reset-master-certificate.progress","reset-master-certificate.log",
        "progress-hacluster-restart",hacp_reload_js());
    return $tpl->js_dialog_confirm_action("{hacp_reset_confirm}","hacp-reset-cert","none",$after);
}
function hacp_reset_perform():bool{
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){
        echo hacp_t("ERROR_NO_PRIVS");
        return false;
    }
    admin_tracks("HaCluster: reset of the agents service certificate and all client certificates");
    return true;
}
function section_performance_popup():bool{
    $tpl=new template_admin();
    echo hacluster_meta_popup_head(array("HaClusterMaxConn"),$tpl);

    $HaClusterWorkers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterWorkers"));
    $HaClusterMaxConn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterMaxConn"));
    $HaClusterTHreads=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTHreads"));
    $CPUza=array();$CPUz=array();
    if($HaClusterMaxConn<2000){$HaClusterMaxConn=2000;}

    $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    if($CPU_NUMBER==0){
        $_cpuJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/cpu-number"));
        $CPU_NUMBER=(is_object($_cpuJson) && !empty($_cpuJson->success))?intval($_cpuJson->cpu_number):2;
    }

    if($HaClusterWorkers==0){$HaClusterWorkers=1;}
    if($HaClusterTHreads==0){$HaClusterTHreads=1;}
    for($i=0;$i<$CPU_NUMBER+1;$i++){
        $s=null;
        if($i==0){$CPUza[$i]="{none}";continue;}
        if($i>1){$s="s";}
        $CPUza[$i]="$i Thread{$s}";
    }

    for($i=1;$i<$CPU_NUMBER+1;$i++){
        $s=null;
        if($i>1){$s="s";}
        $CPUz[$i]="$i {cpu}{$s}";
    }

    $form[]=$tpl->field_array_hash($CPUz,"HaClusterWorkers","nonull:{SquidCpuNumber}",$HaClusterWorkers,false,"{haproxy_nbproc}");
    $form[]=$tpl->field_array_hash($CPUza,"HaClusterTHreads","nonull:{ThreadsPerChild}",$HaClusterTHreads,false,null);
    $form[]=$tpl->field_numeric("HaClusterMaxConn","{maxconn}",$HaClusterMaxConn,"{haproxy_maxconn}");
    echo $tpl->form_outside(null,$form,null,"{apply}",form_js(),"AsSquidAdministrator",true);
    return true;

}
function section_haclient_popup():bool{
    $tpl = new template_admin();
    echo hacluster_meta_popup_head(array("HaClusterUseHaClient","HaClusterClientDisableCheckUrl","HaClusterClientCheckUrl","HaClusterClientCheckGlobal","HaClusterClientMoniCPU","HaClusterClientMaxLoad","HaClusterClientEmergencyLoad","HaClusterClientMaxLoadPeriod","HaClusterDisableInfraCheck"),$tpl);
    $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));

    if(!isset($HaClusterGBConfig["HaClusterClientCheckUrl"])){
        $HaClusterGBConfig["HaClusterClientCheckUrl"]="";
    }

    $HaClusterUseHaClient=intval($HaClusterGBConfig["HaClusterUseHaClient"]);
    $HaClusterClientMaxLoad=intval($HaClusterGBConfig["HaClusterClientMaxLoad"]);
    $HaClusterClientMaxLoadPeriod=intval($HaClusterGBConfig["HaClusterClientMaxLoadPeriod"]);
    $HaClusterClientMoniCPU=intval($HaClusterGBConfig["HaClusterClientMoniCPU"]);
    $HaClusterClientCheckUrl=$HaClusterGBConfig["HaClusterClientCheckUrl"];
    $HaClusterClientCheckGlobal=$HaClusterGBConfig["HaClusterClientCheckGlobal"];
    // Customer request: the agents may skip the web-site checks entirely.
    $HaClusterClientDisableCheckUrl=intval(isset($HaClusterGBConfig["HaClusterClientDisableCheckUrl"])?$HaClusterGBConfig["HaClusterClientDisableCheckUrl"]:0);

    $periods[0]="{realtime}";
    $periods[5]="5 {minutes}";
    $periods[15]="15 {minutes}";
    if(strlen($HaClusterClientCheckUrl)<3){
        $HaClusterClientCheckUrl="http://www.msftncsi.com/ncsi.txt\nhttp://www.msftconnecttest.com/connecttest.txt\nhttp://captive.apple.com\nhttp://edge-http.microsoft.com/captiveportal/generate_204\nhttp://www.gstatic.com/generate_204";
    }
    if($HaClusterClientMaxLoad==0){$HaClusterClientMaxLoad=3;}
    // Read the emergency threshold from its OWN key. It used to be prefilled from
    // HaClusterClientCheckGlobal (a 0/1 checkbox): with that box ticked the field
    // showed 3, and a plain "Apply" pushed load>3 as the emergency threshold to
    // every agent — 12-core proxies dropped out of the LB every ~10 min (qce-lb, 2026-10-15).
    $HaClusterClientEmergencyLoad=intval(isset($HaClusterGBConfig["HaClusterClientEmergencyLoad"])?$HaClusterGBConfig["HaClusterClientEmergencyLoad"]:0);
    if($HaClusterClientEmergencyLoad==0){$HaClusterClientEmergencyLoad=15;}
    if($HaClusterClientEmergencyLoad<3){$HaClusterClientEmergencyLoad=3;}
    $form[]=$tpl->field_checkbox("HaClusterUseHaClient","{use_haclusterclient}",$HaClusterUseHaClient,
        "HaClusterClientMoniCPU,HaClusterClientMaxLoad,HaClusterClientMaxLoadPeriod,HaClusterClientCheckUrl");

    // Checked: the URL list and "test every URL" are greyed out (and kept).
    $form[]=$tpl->field_checkbox_disbaleON("HaClusterClientDisableCheckUrl","{urlsTotestDisable}",$HaClusterClientDisableCheckUrl,
        "HaClusterClientCheckUrl,HaClusterClientCheckGlobal","{urlsTotestDisable_explain}");
    $form[]=$tpl->field_textarea("HaClusterClientCheckUrl","{urlsTotest}",$HaClusterClientCheckUrl);
    $form[]=$tpl->field_checkbox("HaClusterClientCheckGlobal","{urlsTotestGlobal}",$HaClusterClientCheckGlobal);
    // Local to this load-balancer (not pushed to the nodes): turns off its own
    // end-to-end probe, a HEAD to articatech.net through its own listener.
    $HaClusterDisableInfraCheck=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterDisableInfraCheck"));
    $form[]=$tpl->field_checkbox("HaClusterDisableInfraCheck","{lb_internet_check_disable}",$HaClusterDisableInfraCheck,false,"{lb_internet_check_disable_explain}");

    $form[]=$tpl->field_checkbox("HaClusterClientMoniCPU","{monitor_cpu_usage}",$HaClusterClientMoniCPU);

    $form[]=$tpl->field_numeric("HaClusterClientMaxLoad","{Max_Load}",$HaClusterClientMaxLoad);
    $form[]=$tpl->field_numeric("HaClusterClientEmergencyLoad","{Max_Load} ({emergency})",$HaClusterClientEmergencyLoad);

    $form[]=$tpl->field_array_buttons($periods,"HaClusterClientMaxLoadPeriod","{period}",$HaClusterClientMaxLoadPeriod);

    echo $tpl->form_outside(null,$form,null,"{apply}",form_paramsjs(),"AsSquidAdministrator",true);
    return true;
}
function section_dns_tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{main_settings_samba}"]="$page?section-dns-general=yes";
    $array["{dns_used_by_the_system}"]="$page?section-dns-popup=yes";
    $array["{dns_used_by_the_proxy}"]="$page?section-dns-proxy-start=yes";
    $array["DNS {rules}"]="fw.proxy.dns.php?dns-rules=yes&hacluster=yes";
    $array["SafeSearch(s)"]="fw.dns.SafeSearch.php?table-start=yes&byHacluster=yes";
    echo $tpl->tabs_default($array);
    return true;

}
function section_dns_proxy_start():bool{
    $page=CurrentPageName();
    echo "<div id='section-dns-proxy-popup'></div>";
    echo "<script>LoadAjaxSilent('section-dns-proxy-popup','$page?section-dns-proxy=yes');</script>";
    return true;
}

function section_dns_proxy_popup():bool{
    $page=CurrentPageName();
    $tpl = new template_admin();
    echo hacluster_meta_popup_head(array("HaClusterProxyUseOwnDNS","HaClusterProxyUseBackendDNSDIST","HaClusterProxyUseDNSCacheDenyPTR","HaClusterSquidNameServer1","HaClusterSquidNameServer2","HaClusterSquidNameServer3"),$tpl);
    $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
    $js="LoadAjaxSilent('section-dns-proxy-popup','$page?section-dns-proxy=yes');";
    $HaClusterUseLBAsDNS=intval($HaClusterGBConfig["HaClusterUseLBAsDNS"]);

    if($HaClusterUseLBAsDNS==1){
        echo $tpl->div_warning("{use_load_balancer_as_dns}||{use_load_balancer_as_dns_explain}");
        return true;
    }

    $form[]="<div style='margin-top:10px'>";

    $HaClusterProxyUseOwnDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProxyUseOwnDNS"));

    $form[]=$tpl->BigCircleCheckbox("HaClusterProxyUseOwnDNS",
        "{proxy_use_its_own_dns}","{proxy_use_its_own_dns_explain}",
        $HaClusterProxyUseOwnDNS,$js.form_paramsjs(true));

    if($HaClusterProxyUseOwnDNS==0){
        $form[]="</div>";
        echo $tpl->_ENGINE_parse_body($form);
        return true;
    }
    $SquidNameServer1=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterSquidNameServer1");
    $SquidNameServer2=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterSquidNameServer2");
    $SquidNameServer3=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterSquidNameServer3");

    $HaClusterProxyUseBackendDNSDIST=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProxyUseBackendDNSDIST"));
    $HaClusterProxyUseDNSCacheDenyPTR=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProxyUseDNSCacheDenyPTR"));
    $form[]=$tpl->BigCircleCheckbox("HaClusterProxyUseBackendDNSDIST",
        "{use_local_dns_service}","{hacluster_use_local_dns_service_explain}",
        $HaClusterProxyUseBackendDNSDIST,$js.form_paramsjs(true));

    if($HaClusterProxyUseBackendDNSDIST==1){
        $form[]=$tpl->BigCircleCheckbox("HaClusterProxyUseDNSCacheDenyPTR",
            "{deny} PTR","{hacluster_use_local_dns_service_ptr_explain}",
            $HaClusterProxyUseDNSCacheDenyPTR,$js.form_paramsjs(true));
    }




    $form[]=$tpl->BigTextField("HaClusterSquidNameServer1","{primary_dns}","",$SquidNameServer1,
        $js.form_paramsjs(true));
    $form[]=$tpl->BigTextField("HaClusterSquidNameServer2","{secondary_dns}","",
        $SquidNameServer2,$js.form_paramsjs(true));
    $form[]=$tpl->BigTextField("HaClusterSquidNameServer3","{nameserver} 3","",
        $SquidNameServer3,$js.form_paramsjs(true));
    $form[]="</div>";
    echo $tpl->_ENGINE_parse_body($form);
    return true;

}
function section_dns_general():bool{
    $page=CurrentPageName();
    echo "<div id='section-dns-general'></div>";
    echo "<script>LoadAjaxSilent('section-dns-general','$page?section-dns-general2=yes');</script>";
    return true;
}
function section_dns_general2():bool{
    $page=CurrentPageName();
    $tpl = new template_admin();
    echo hacluster_meta_popup_head(array("HaClusterUseLBAsDNS","HaClusterServeDNS"),$tpl);
    $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
    $HaClusterServeDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterServeDNS"));
    $HaClusterUseLBAsDNS=intval($HaClusterGBConfig["HaClusterUseLBAsDNS"]);
    $form[]="<div style='margin-top:10px'>";

    $form[]=$tpl->BigCircleCheckbox("HaClusterUseLBAsDNS","{use_load_balancer_as_dns}",
        "{use_load_balancer_as_dns_explain}",
        $HaClusterUseLBAsDNS,
        "LoadAjaxSilent('section-dns-general','$page?section-dns-general2=yes');".form_paramsjs(true));

    $form[]=$tpl->BigCircleCheckbox("HaClusterServeDNS",
        "{hacluster_lb_dns}",
        "{hacluster_use_dns_service_explain}<br>{hacluster_dns_method1}",$HaClusterServeDNS,
        "LoadAjaxSilent('section-dns-general','$page?section-dns-general2=yes');".
        form_paramsjs(true));

    $form[]="</div>";
    echo $tpl->_ENGINE_parse_body($form);

    return true;
}

function section_dns_popup():bool{
    $tpl = new template_admin();
    echo hacluster_meta_popup_head(array("HaClusterUseLocalDNSCache","DNS1","DNS2","DOMAINS1","DOMAINS2"),$tpl);
    $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));

    $HaClusterUseLBAsDNS=intval($HaClusterGBConfig["HaClusterUseLBAsDNS"]);

    if($HaClusterUseLBAsDNS==1){
        echo $tpl->div_warning("{use_load_balancer_as_dns}||{use_load_balancer_as_dns_explain}");
        return true;
    }
    $form[]="<div style='margin-top:10px'>";
    $loops=hacluster_dns_loops_in($HaClusterGBConfig,intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterServeDNS")));
    if(count($loops)>0){
        $form[]=$tpl->div_error("{dns_servers}||".hacluster_dns_loop_text($loops));
    }


    $HaClusterUseLocalDNSCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterUseLocalDNSCache"));
    $form[]=$tpl->BigCircleCheckbox("HaClusterUseLocalDNSCache",
        "{local_dns_service}","{hacluster_local_dns_service_explain}",
        $HaClusterUseLocalDNSCache,form_paramsjs(true));

       $form[]=$tpl->BigTextField("DNS1","{primary_dns}","",$HaClusterGBConfig["DNS1"],
           form_paramsjs(true));
       $form[]=$tpl->BigTextField("DNS2","{secondary_dns}","",$HaClusterGBConfig["DNS2"],
           form_paramsjs(true));
       $form[]=$tpl->BigTextField("DOMAINS1","{InternalDomain} 1","",$HaClusterGBConfig["DOMAINS1"],
           form_paramsjs(true));
       $form[]=$tpl->BigTextField("DOMAINS2","{InternalDomain} 2","",$HaClusterGBConfig["DOMAINS2"],
           form_paramsjs(true));
    $form[]="</div>";
    echo $tpl->_ENGINE_parse_body($form);
    return true;
}
function section_ssl_popup():bool{
    $tpl = new template_admin();
    echo hacluster_meta_popup_head(array("HaClusterDecryptSSL","HaClusterCertif"),$tpl);
    $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));

    $form[]=$tpl->field_checkbox("HaClusterDecryptSSL",
        "{activate_ssl_on_http_port}",
        $HaClusterGBConfig["HaClusterDecryptSSL"],false,"{activate_ssl_on_http_port_explain}");

    $form[]=$tpl->field_certificate("HaClusterCertif","{proxy_certificate}",
        $HaClusterGBConfig["HaClusterCertif"],"{hacluster_Tproxy_certificate}",null);

    if(intval($HaClusterGBConfig["sslcrtd_program_dbsize"])==0){$HaClusterGBConfig["sslcrtd_program_dbsize"]=8;}
    $form[]=$tpl->field_numeric("sslcrtd_program_dbsize","{sslcrtd_program_dbsize} (MB)",$HaClusterGBConfig["sslcrtd_program_dbsize"]);

    echo $tpl->form_outside(null,$form,null,"{apply}",form_paramsjs(),"AsSquidAdministrator",true);
    return true;
}
function section_logs_popup():bool{
    $tpl = new template_admin();
    echo hacluster_meta_popup_head(array("HaClusterRemoveRealtimeLogs"),$tpl);
    $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
    $HaClusterRemoveRealtimeLogs=intval($HaClusterGBConfig["HaClusterRemoveRealtimeLogs"]);

    $form[]=$tpl->field_checkbox("HaClusterRemoveRealtimeLogs","{disable_realtime_backends}",$HaClusterRemoveRealtimeLogs);
    echo $tpl->form_outside(null,$form,null,"{apply}",form_paramsjs(),"AsSquidAdministrator",true);
    return true;
}
function section_deploy_popup():bool{
    $tpl = new template_admin();
    echo hacluster_meta_popup_head(array("HaClusterDeployArticaUpdates"),$tpl);
    $HaClusterDeployArticaUpdates=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterDeployArticaUpdates"));

    $form[]=$tpl->field_checkbox("HaClusterDeployArticaUpdates","{deploy_artica_updates})",$HaClusterDeployArticaUpdates);

    echo $tpl->form_outside(null,$form,"{deploy_artica_updates_explain}","{apply}",form_js(),"AsSquidAdministrator",true);
    return true;
}
function section_health_popup():bool{
    $tpl=new template_admin();
    echo hacluster_meta_popup_head(array("HaClusterReloadEach","HaClusterEnableWatchDog","HaClusterCheckURI","HaClusterCheckInt","HaClusterCheckFall","HaClusterCheckRise"),$tpl);
    $HaClusterReloadEach=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterReloadEach"));
    $HaClusterCheckInt=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterCheckInt"));
    $HaClusterCheckFall=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterCheckFall"));
    $HaClusterCheckRise=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterCheckRise"));
    if($HaClusterCheckInt==0){$HaClusterCheckInt=10;}
    if($HaClusterCheckInt<5){$HaClusterCheckInt=5;}
    if($HaClusterCheckFall==0){$HaClusterCheckFall=5;}
    if($HaClusterCheckRise==0){$HaClusterCheckRise=2;}

    $HaClusterCheckURI=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterCheckURI"));
    $HaClusterCheckRestart=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterCheckRestart"));
    if(strlen($HaClusterCheckURI)<2){$HaClusterCheckURI="http://www.msftncsi.com/ncsi.txt";}

    for($i=3;$i<24;$i++){
        $reload[$i]="{each} $i {hours}";

    }
    $reload[999]="{never}";
    $HaClusterEnableWatchDog=1;
    $HaClusterDisableWatchDog=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterDisableWatchDog"));
    if($HaClusterDisableWatchDog==1){
        $HaClusterEnableWatchDog=0;
    }

    $form[]=$tpl->field_section("{APP_PARENTLB}","{APP_PARENT_LB_EXPLAIN1}");

    $form[]=$tpl->field_array_hash($reload,"HaClusterReloadEach","nonull:{reload_service}",$HaClusterReloadEach);

    $form[]=$tpl->field_checkbox("HaClusterEnableWatchDog","{EnableWatchdog}",$HaClusterEnableWatchDog);
    $form[]=$tpl->field_text("HaClusterCheckURI","{check_url}",$HaClusterCheckURI);



    $form[]=$tpl->field_section("{backends}","{APP_PARENT_LB_EXPLAIN2}");
    $form[]=$tpl->field_numeric("HaClusterCheckInt","{check_interval} ({seconds})",$HaClusterCheckInt);
    $form[]=$tpl->field_numeric("HaClusterCheckFall","{fall} ({attempts})",$HaClusterCheckFall,"{fall_explain}");
    $form[]=$tpl->field_numeric("HaClusterCheckRise","{rise} ({attempts})",$HaClusterCheckRise,"{rise_explain}");
    echo $tpl->form_outside(null,$form,null,"{apply}",form_js(),"AsSquidAdministrator",true);
    return true;

}
function section_timeout_popup():bool{
    $tpl=new template_admin();
    echo hacluster_meta_popup_head(array("HaClusterTimeOutConnect","HaClusterSeverTimeout","HaClusterClientTimeout","HaClusterTCPKeepalives","HaClusterTimeOutHTTPRequest","HaClusterTimeOutHTTPKeepAlive","HaClusterTimeOutTunnel","HaClusterTimeOutClientFin","HaClusterTimeOutServerFin","HaClusterTimeOutQueue"),$tpl);
    $HaClusterTimeOutConnect=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTimeOutConnect"));
    $HaClusterSeverTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterSeverTimeout"));
    $HaClusterClientTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClientTimeout"));

    $HaClusterProto=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProto");
    if($HaClusterProto==null){$HaClusterProto="tcp";}


    $HaClusterTimeOutHTTPRequest=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTimeOutHTTPRequest"));
    $HaClusterTimeOutHTTPKeepAlive=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTimeOutHTTPKeepAlive"));
    $HaClusterTimeOutQueue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTimeOutQueue"));
    $HaClusterTimeOutTunnel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTimeOutTunnel"));
    $HaClusterTimeOutClientFin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTimeOutClientFin"));
    $HaClusterTimeOutServerFin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTimeOutServerFin"));

    if($HaClusterTimeOutConnect==0){$HaClusterTimeOutConnect=10;}
    if($HaClusterSeverTimeout==0){$HaClusterSeverTimeout=30;}
    if($HaClusterClientTimeout==0){$HaClusterClientTimeout=300;}

    $form[]=$tpl->field_numeric("HaClusterTimeOutConnect","{connect_timeout} ({seconds})",$HaClusterTimeOutConnect,"{haproxy_timeout_connect}");
    $form[]=$tpl->field_numeric("HaClusterSeverTimeout","{server_timeout} ({seconds})",$HaClusterSeverTimeout,"{haproxy_timeout_server}");
    $form[]=$tpl->field_numeric("HaClusterClientTimeout","{client_timeout} ({seconds})",$HaClusterClientTimeout,"{haproxy_timeout_client}");

    $HaClusterTCPKeepalives=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTCPKeepalives"));
    $form[]=$tpl->field_checkbox("HaClusterTCPKeepalives","{hacluster_tcp_keepalives}",$HaClusterTCPKeepalives,false,"{hacluster_tcp_keepalives_explain}");

    if($HaClusterProto=="http") {
        $form[] = $tpl->field_numeric("HaClusterTimeOutHTTPRequest", "{timeout} http-request ({seconds})", $HaClusterTimeOutHTTPRequest);
        $form[] = $tpl->field_numeric("HaClusterTimeOutHTTPKeepAlive", "{timeout} http-keep-alive ({seconds})", $HaClusterTimeOutHTTPKeepAlive);
        $form[]=$tpl->field_numeric("HaClusterTimeOutTunnel","{timeout} Tunnel ({seconds})",$HaClusterTimeOutTunnel);
    }

    $form[]=$tpl->field_numeric("HaClusterTimeOutClientFin","{timeout} client-fin ({seconds})",$HaClusterTimeOutClientFin);
    $form[]=$tpl->field_numeric("HaClusterTimeOutServerFin","{timeout} server-fin ({seconds})",$HaClusterTimeOutServerFin);
    $form[]=$tpl->field_numeric("HaClusterTimeOutQueue","{timeout} Queue ({seconds})",$HaClusterTimeOutQueue);
    echo $tpl->form_outside(null,$form,null,"{apply}",form_js(),"AsSquidAdministrator",true);
    return true;

}
function section_simuad_popup():bool{
    $tpl=new template_admin();
    $HaClusterAsAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAsAD"));
    $HaClusterAsADIface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAsADIface"));
    $form[]=$tpl->field_checkbox("HaClusterAsAD","{simulate_the_active_directory}",$HaClusterAsAD);
    $form[]=$tpl->field_interfaces("HaClusterAsADIface","{listen_interface}",$HaClusterAsADIface);
    echo $tpl->form_outside(null,$form,"{simulate_the_active_directory_explain}","{apply}",form_js(),"AsSquidAdministrator",true);
    return true;
}
function section_ipaddr_popup():bool{
    $tpl=new template_admin();
    $HaClusterUseAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterUseAddr");
    $form[]=$tpl->field_text("HaClusterUseAddr","{address} (NAT/Masquerade)",$HaClusterUseAddr);
    $add=";LoadAjax('hacluster-failover-flat','fw.hacluster.failover.php?hacluster-failover-flat=yes');";
    echo $tpl->form_outside(null,$form,"{nat_explain_addr}","{apply}",form_js().$add,"AsSquidAdministrator",true);
    return true;
}

function section_listen_popup():bool{
    $tpl=new template_admin();
    echo hacluster_meta_popup_head(array("HaClusterProto","HaClusterBalance","HaClusterInterface","HaClusterOutface","HaClusterPort"),$tpl);
    $HaClusterInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterInterface"));
    $HaClusterOutface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterOutface"));
    $HaClusterPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterPort"));
    $HaClusterBalance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterBalance");
    $HaClusterProto=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProto");

    if($HaClusterPort==0){$HaClusterPort=3128;}
    if($HaClusterProto==null){$HaClusterProto="tcp";}

    $HaProto["tcp"]="TCP/IP";
    $HaProto["http"]="HTTP";

    $algo["source"]="{strict-hashed-ip}";
    $algo["roundrobin"]="{round-robin}";
    $algo["leastconn"]="{leastconn}";

    $form[]=$tpl->field_array_hash($HaProto,"HaClusterProto","{protocol}",$HaClusterProto);
    $form[]=$tpl->field_array_hash($algo,"HaClusterBalance","{method}",$HaClusterBalance);
    $form[]=$tpl->field_interfaces("HaClusterInterface","{listen_interface}",$HaClusterInterface);
    $form[]=$tpl->field_interfaces("HaClusterOutface","{outgoing_interface}",$HaClusterOutface);


    $form[]=$tpl->field_section("{APP_SQUID}");
    $form[]=$tpl->field_numeric("HaClusterPort","{listen_port}",$HaClusterPort);
    echo $tpl->form_outside(null,$form,null,"{apply}",restart_js(),"AsSquidAdministrator",true);
    return true;

}
function section_pproto_popup():bool{
    $tpl=new template_admin();
    echo hacluster_meta_popup_head(array("HaClusterEnableProxyProtocol"),$tpl);
    $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
    $HaClusterDisableProxyProtocol=intval($HaClusterGBConfig["HaClusterDisableProxyProtocol"]);

    if($HaClusterDisableProxyProtocol==0){
        $HaClusterEnableProxyProtocol=1;
    }else{
        $HaClusterEnableProxyProtocol=0;
    }

    $form[]=$tpl->field_section("{infrastructure}");
    $form[]=$tpl->field_checkbox("HaClusterEnableProxyProtocol","{proxy_protocol}",$HaClusterEnableProxyProtocol);

    echo $tpl->form_outside(null,$form,null,"{apply}",form_paramsjs(),"AsSquidAdministrator",true);
    return true;

}
function restart_js():string{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $prgress=$tpl->framework_buildjs("/hacluster/server/restart",
        "hacluster.progress",
        "hacluster.progress.txt",
        "progress-hacluster-restart",
        "LoadAjaxSilent('hacluster-table-status-left','$page?hacluster-table-status-left=yes');"
    );

    $f[]="LoadAjaxSilent('hacluster-parameters','$page?parameters-table=yes');";
    $f[]="dialogInstance2.close()";
    $f[]=$prgress;
    return @implode(";",$f);

}
function form_js():string{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $prgress=$tpl->framework_buildjs("/hacluster/server/reconfigure",
        "hacluster.progress",
        "hacluster.progress.txt",
        "progress-hacluster-restart",
        "LoadAjaxSilent('hacluster-table-status-left','$page?hacluster-table-status-left=yes');"
    );

    $f[]="LoadAjaxSilent('hacluster-parameters','$page?parameters-table=yes');";
    $f[]="dialogInstance2.close()";
    $f[]=$prgress;
    return @implode(";",$f);

}
function form_paramsjs($Noclose=false):string{
    $tpl=new template_admin();
    $page=CurrentPageName();


    $prgress=$tpl->framework_buildjs("/hacluster/server/notify/all",
        "hacluster.connect.progress",
        "hacluster.connect.txt",
        "progress-hacluster-restart",
        "LoadAjaxSilent('hacluster-table-status-left','$page?hacluster-table-status-left=yes');"
    );

    $f[]="LoadAjaxSilent('hacluster-parameters','$page?parameters-table=yes');";
    if(!$Noclose) {
        $f[] = "dialogInstance2.close()";
    }
    $f[]=$prgress;
    return @implode(";",$f);

}
// Relais DNS de la ferme (HaClusterServeDNS=1) : le LB transmet chaque requete
// a l'unbound :5353 des proxys, et DNS1/DNS2 ("DNS utilise par la ferme")
// deviennent l'amont de cet unbound. Un amont egal a une IP de ce LB, ou en
// boucle locale (le proxy lui-meme), renvoie la requete au relais : boucle
// LB -> proxy -> LB, carre DNS rouge et tous les proxys DOWN. Meme regle que
// HaClusterClient.FarmDNSLoops cote daemon.
function hacluster_dns_loops(string $addr):bool{
    $addr=trim($addr);
    if(!filter_var($addr,FILTER_VALIDATE_IP)){return false;}
    if(preg_match("#^127\\.#",$addr) || $addr=="::1" || $addr=="0.0.0.0" || $addr=="::"){return true;}
    if(!function_exists("net_get_interfaces")){return false;}
    $ifaces=net_get_interfaces();
    if(!is_array($ifaces)){return false;}
    foreach ($ifaces as $iface){
        if(!isset($iface["unicast"])){continue;}
        foreach ($iface["unicast"] as $unicast){
            if(isset($unicast["address"]) && $unicast["address"]==$addr){return true;}
        }
    }
    return false;
}
// Liste les DNS de la ferme qui bouclent, seulement si le relais DNS est actif.
function hacluster_dns_loops_in($HaClusterGBConfig,int $ServeDNS):array{
    $loops=array();
    if($ServeDNS<>1 || !is_array($HaClusterGBConfig)){return $loops;}
    foreach (array("DNS1","DNS2") as $key){
        if(!isset($HaClusterGBConfig[$key])){continue;}
        if(hacluster_dns_loops(strval($HaClusterGBConfig[$key]))){$loops[]=trim($HaClusterGBConfig[$key]);}
    }
    return $loops;
}
function hacluster_dns_loop_text(array $loops):string{
    return "DNS loop: ".@implode(", ",$loops)." points back to this load-balancer while it relays DNS to the proxies. Use real DNS servers (Active Directory, corporate or provider DNS) for the farm, never 127.0.0.1 nor an address of the load-balancer.";
}
function SaveParamsGeneral(){
    if(hacluster_meta_gate("text")){return false;}

    $HaClusterServeDNSOld=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterServeDNS"));

    if(isset($_POST["HaClusterUseLBAsDNS"])){
        $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
        $HaClusterGBConfig["HaClusterUseLBAsDNS"]=$_POST["HaClusterUseLBAsDNS"];
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterGBConfig", serialize($HaClusterGBConfig));

        if($_POST["HaClusterUseLBAsDNS"]==1 && !hacluster_meta_key_held("HaClusterServeDNS")){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterServeDNS",0);
        }
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/reconfigure");
        return admin_tracks("Set HaCluster as DNS server for nodes to {$_POST["HaClusterUseLBAsDNS"]}");
    }

    if(isset($_POST["HaClusterServeDNS"])){
        $HaClusterServeDNSNew=intval($_POST["HaClusterServeDNS"]);
        if($HaClusterServeDNSOld==$HaClusterServeDNSNew){
            return true;
        }
        if($HaClusterServeDNSNew==1){
            $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
            $loops=hacluster_dns_loops_in($HaClusterGBConfig,1);
            if(count($loops)>0){
                http_response_code(400);
                echo (new template_admin())->post_error(hacluster_dns_loop_text($loops));
                return false;
            }
            if(!hacluster_meta_key_held("HaClusterUseLBAsDNS")){
            $HaClusterGBConfig["HaClusterUseLBAsDNS"]=0;
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterGBConfig", serialize($HaClusterGBConfig));
            }

        }
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterServeDNS",$HaClusterServeDNSNew);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/reconfigure");
        return admin_tracks("Set HaCluster as DNS server for Client $HaClusterServeDNSNew");

    }
    return true;

}

function SaveParams():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    if(hacluster_meta_gate("post")){return false;}


    if(isset($_POST["HaClusterSquidNameServer1"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterSquidNameServer1",$_POST["HaClusterSquidNameServer1"]);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
        return true;
    }
    if(isset($_POST["HaClusterSquidNameServer2"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterSquidNameServer2",$_POST["HaClusterSquidNameServer2"]);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
        return true;
    }
    if(isset($_POST["HaClusterSquidNameServer3"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterSquidNameServer3",$_POST["HaClusterSquidNameServer3"]);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
        return true;
    }

    $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));


    // Seuls les champs envoyes sont juges : une valeur deja stockee ne doit
    // pas bloquer la correction de l'autre.
    $tpl_posted_dns=array();
    if(isset($_POST["DNS1"])){$tpl_posted_dns["DNS1"]=$_POST["DNS1"];}
    if(isset($_POST["DNS2"])){$tpl_posted_dns["DNS2"]=$_POST["DNS2"];}
    // DNS1/DNS2 sont retires de $_POST : sans ce drapeau, le compteur $c de la
    // boucle finale restait a 0 et HaClusterGBConfig n'etait jamais enregistre
    // (l'onglet « DNS utilise par la ferme » ne sauvegardait rien).
    $GBChanged=false;
    if(isset($_POST["DNS1"])){
        $HaClusterGBConfig["DNS1"]=$_POST["DNS1"];
        unset($_POST["DNS1"]);
        $GBChanged=true;
    }
   if(isset($_POST["DNS2"])){
       $HaClusterGBConfig["DNS2"]=$_POST["DNS2"];
       unset($_POST["DNS2"]);
       $GBChanged=true;
   }
    $ServeDNSNow=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterServeDNS"));
    $posted=array();
    foreach (array("DNS1","DNS2") as $key){
        if(isset($tpl_posted_dns[$key])){$posted[$key]=$tpl_posted_dns[$key];}
    }
    $loops=hacluster_dns_loops_in($posted,$ServeDNSNow);
    if(count($loops)>0){
        http_response_code(400);
        echo $tpl->post_error(hacluster_dns_loop_text($loops));
        return false;
    }




    if(isset($_POST["HaClusterEnableProxyProtocol"])) {
        if ($_POST["HaClusterEnableProxyProtocol"] == 1) {
            $_POST["HaClusterDisableProxyProtocol"] = 0;
        } else {
            $_POST["HaClusterDisableProxyProtocol"] = 1;
        }
    }
    if(isset($_POST["HaClusterDisableInfraCheck"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterDisableInfraCheck",intval($_POST["HaClusterDisableInfraCheck"]));
        unset($_POST["HaClusterDisableInfraCheck"]);
    }
    if(isset($_POST["HaClusterUseLocalDNSCache"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterUseLocalDNSCache",$_POST["HaClusterUseLocalDNSCache"]);
        unset($_POST["HaClusterUseLocalDNSCache"]);
    }

    if(isset($_POST["HaClusterProxyUseOwnDNS"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterProxyUseOwnDNS",$_POST["HaClusterProxyUseOwnDNS"]);
        unset($_POST["HaClusterProxyUseOwnDNS"]);
    }
    if(isset($_POST["HaClusterProxyUseDNSCacheDenyPTR"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterProxyUseDNSCacheDenyPTR",$_POST["HaClusterProxyUseDNSCacheDenyPTR"]);
        unset($_POST["HaClusterProxyUseDNSCacheDenyPTR"]);
    }
    if(isset($_POST["HaClusterProxyUseBackendDNSDIST"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterProxyUseBackendDNSDIST",intval($_POST["HaClusterProxyUseBackendDNSDIST"]));
        unset($_POST["HaClusterProxyUseBackendDNSDIST"]);
    }


    if(isset($_POST["HaClusterServeDNS"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterServeDNS",intval($_POST["HaClusterServeDNS"]));
        if(intval($_POST["HaClusterServeDNS"])==1 && !hacluster_meta_key_held("HaClusterUseLBAsDNS")){
            $HaClusterGBConfig["HaClusterUseLBAsDNS"]=0;
        }
        unset($_POST["HaClusterServeDNS"]);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/rebuild");
    }

    if(isset($_POST["HaClusterAsAD"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterAsAD",$_POST["HaClusterAsAD"]);
        $haClusterAD=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));
        $haClusterAD["HaClusterAsAD"]=$_POST["HaClusterAsAD"];
        $haClusterADSer=serialize($haClusterAD);
        $haClusterADSEnc=base64_encode($haClusterADSer);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterAD",$haClusterADSEnc);
    }

    if(isset($_POST["HaClusterAsADIface"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterAsADIface",$_POST["HaClusterAsADIface"]);
    }
    if(isset($_POST["HaClusterUseAddr"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterUseAddr",$_POST["HaClusterUseAddr"]);
        unset($_POST["HaClusterUseAddr"]);
    }
    if(isset($_POST["HaClusterEnableProxyProtocol"])) {
        if ($_POST["HaClusterEnableProxyProtocol"] == 1) {
            $_POST["HaClusterDisableProxyProtocol"] = 0;
        } else {
            $_POST["HaClusterDisableProxyProtocol"] = 1;
        }
    }
    if(isset($_POST["HaClusterDecryptSSL"])) {
        if ($_POST["HaClusterDecryptSSL"] == 1) {
            if (trim($_POST["HaClusterCertif"]) == null) {
                echo $tpl->post_error("{select} ! {proxy_certificate}");
                return false;
            }
        }
    }
    $c=0;
    foreach ($_POST as $key=>$val) {
        $HaClusterGBConfig[$key]=$val;
        $c++;
        admin_tracks("Update HaCluster backends option $key with $val");
    }
    $tpl->SAVE_POSTs();
    if($c>0 || $GBChanged) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterGBConfig", serialize($HaClusterGBConfig));
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/reconfigure");

    return true;

}

function remote_categories_service_js():bool{
    $function=null;
    if(isset($_GET["function"])){
        $function="&function=".$_GET["function"];
    }
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI   = true;
    $page=CurrentPageName();
    return $tpl->js_dialog2("{use_remote_categories_services}",
        "$page?remote-categories-service-popup=yes$function",550);
}
function section_itcharter_popup():bool{
    $tpl=new template_admin();
    echo hacluster_meta_popup_head(array("HaClusterEnableITChart"),$tpl);
    $HaClusterEnableITChart=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterEnableITChart"));
    $html[]=$tpl->BigCircleCheckbox("HaClusterEnableITChart",
        "{APP_ITCHARTER}","{APP_ITCHARTER_HACLUSTER}",$HaClusterEnableITChart,
        "dialogInstance2.close();".form_js().";LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function section_replicacls_popup():bool{
    $tpl=new template_admin();
    echo hacluster_meta_popup_head(array("HaClusterReplicateACLs"),$tpl);
    $HaClusterReplicateACLs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterReplicateACLs"));
    $html[]=$tpl->BigCircleCheckbox(
        "HaClusterReplicateACLs",
        "{acls_replication}",
        "{acls_hacluster_replication_explain}",
         $HaClusterReplicateACLs,
        "dialogInstance2.close();".form_js().";LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function section_itcharter_save():bool{
    if(hacluster_meta_gate("text")){return false;}
    $HaClusterEnableITChart=intval($_POST["HaClusterEnableITChart"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterEnableITChart",$HaClusterEnableITChart);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/itcharter/check");

    if($HaClusterEnableITChart==0){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/clients/itchart/uninstall");
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableITChart",1);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/itcharter/install");
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
    return admin_tracks("Turn ITCharter From the HaCluster to {$_POST["HaClusterEnableITChart"]}");
}
function section_replicacls_save():bool{
    if(hacluster_meta_gate("text")){return false;}
    $HaClusterReplicateACLs=intval($_POST["HaClusterReplicateACLs"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterReplicateACLs",$HaClusterReplicateACLs);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
    return admin_tracks("Turn HaCluster ACL replication to {$_POST["HaClusterReplicateACLs"]}");
}



function section_errorpage_popup():bool{

    $tpl=new template_admin();
    echo hacluster_meta_popup_head(array("HaClusterUseCentralErrorPage","HaClusterErrorPageHTTPPort","HaClusterErrorPageUseSSL","HaClusterErrorPageHTTPsPort","HaClusterErrorPageCert"),$tpl);
    $HaClusterUseCentralErrorPage=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterUseCentralErrorPage"));
    $HaClusterErrorPageHTTPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterErrorPageHTTPPort"));
    $HaClusterErrorPageUseSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterErrorPageUseSSL"));
    $HaClusterErrorPageHTTPsPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterErrorPageHTTPsPort"));

    if($HaClusterErrorPageHTTPPort==0){
        $HaClusterErrorPageHTTPPort=80;
    }
    if($HaClusterErrorPageHTTPsPort==0){
        $HaClusterErrorPageHTTPsPort=443;
    }

    $HaClusterErrorPageServiceHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterErrorPageServiceHostname"));

    $HaClusterErrorPageCert=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterErrorPageCert"));

    if(strlen($HaClusterErrorPageServiceHostname)<2){
        $HaClusterErrorPageServiceHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
    }

    $form[] = $tpl->field_checkbox("HaClusterUseCentralErrorPage","{enable}",$HaClusterUseCentralErrorPage);

    $form[] = $tpl->field_text("HaClusterErrorPageServiceHostname","{hostname}",$HaClusterErrorPageServiceHostname);

    $form[] = $tpl->field_numeric("HaClusterErrorPageHTTPPort","{listen_port} (HTTP)",$HaClusterErrorPageHTTPPort);

    $form[] = $tpl->field_checkbox("HaClusterErrorPageUseSSL","{useSSL}",$HaClusterErrorPageUseSSL);

    $form[] = $tpl->field_numeric("HaClusterErrorPageHTTPsPort","{listen_port} (SSL)",$HaClusterErrorPageHTTPsPort);

    $form[] = $tpl->field_certificate("HaClusterErrorPageCert","{ssl_certificate}",$HaClusterErrorPageCert);

    echo $tpl->form_outside("", $form,"","{apply}","dialogInstance2.close();".form_js(),"AsSquidAdministrator");
    return true;
}
function section_errorpage_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(hacluster_meta_gate("post")){return false;}
    // Without a certificate the LB writes no weberrorssl frontend: the nodes
    // would send every blocked user to an https port where nothing listens.
    // A key held by Artica Meta was stripped from $_POST: judge on the stored value.
    $UseCentral=isset($_POST["HaClusterUseCentralErrorPage"])?intval($_POST["HaClusterUseCentralErrorPage"]):intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterUseCentralErrorPage"));
    $UseSSL=isset($_POST["HaClusterErrorPageUseSSL"])?intval($_POST["HaClusterErrorPageUseSSL"]):intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterErrorPageUseSSL"));
    $Cert=isset($_POST["HaClusterErrorPageCert"])?(string)$_POST["HaClusterErrorPageCert"]:(string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterErrorPageCert");
    if($UseCentral==1 && $UseSSL==1){
        if(strlen(trim($Cert))<3){
            echo $tpl->post_error("{useSSL}: {select} ! {ssl_certificate}");
            return false;
        }
    }
    $tpl->SAVE_POSTs();
    // The LB must publish its frontends (weberrorhttp/weberrorssl) BEFORE the
    // nodes start redirecting to it; otherwise they appeared only at the next
    // 10-minute reconfigure, and blocked users got no page meanwhile.
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/build");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
    return admin_tracks_post("Saving Section Error Page");
}

function remote_categories_service_popup():bool{
    $tpl=new template_admin();
    echo hacluster_meta_popup_head(array("HaClusterUseRemoteCategoriesService","HaClusterUseRemoteCategoriesServiceDebug","RemoteCategoriesServiceAddr","RemoteCategoriesServicePort","RemoteCategoriesServiceSSL"),$tpl);
    $HaClusterUseRemoteCategoriesService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterUseRemoteCategoriesService"));

    $RemoteCategoriesServiceSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServiceSSL"));

    $RemoteCategoriesServicePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicePort"));

    $RemoteCategoriesServiceAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServiceAddr");

    $HaClusterUseRemoteCategoriesServiceDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterUseRemoteCategoriesServiceDebug"));

    $form[]=$tpl->field_checkbox("HaClusterUseRemoteCategoriesService","{enabled}",$HaClusterUseRemoteCategoriesService);


    $form[] = $tpl->field_checkbox("HaClusterUseRemoteCategoriesServiceDebug","{debug}",$HaClusterUseRemoteCategoriesServiceDebug);

    $form[]=$tpl->field_text("RemoteCategoriesServiceAddr",
        "{remote_server_address}",
        $RemoteCategoriesServiceAddr);

    if($RemoteCategoriesServicePort==0){
        $RemoteCategoriesServicePort=9905;
    }

    $form[] = $tpl->field_numeric("RemoteCategoriesServicePort","{remote_server_port}",$RemoteCategoriesServicePort);

    $form[] = $tpl->field_checkbox("RemoteCategoriesServiceSSL","{use_ssl}",$RemoteCategoriesServiceSSL);

    echo $tpl->form_outside("", $form,"{remote_categories_service_ufdb}","{apply}","dialogInstance2.close();".form_js(),"AsSquidAdministrator");
    return true;

}
function remote_categories_service_save():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI   = true;
    $tpl->CLEAN_POST();
    if(hacluster_meta_gate("post")){return false;}
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/categories/remote/service/check");
    return admin_tracks("Saving the use a remote categories service");

}
// ───────────────────────────── Tableau d'état (table=yes) ─────────────────────────────
// Maquette : /home/claude/images/stitch_hacluster_load_balancer_status.zip
// En haut 4 tuiles (écoute réelle, latence, trafic sortant, connexions), puis la
// télémétrie : période, 4 cartes de synthèse sur la période, une courbe, la
// topologie des nœuds. La première tuile montre l'adresse que HAProxy écoute
// VRAIMENT (GET /hacluster/server/listen) : avec la bascule, c'est l'IP
// flottante, et l'ancien « ens192:3128 » faisait croire que l'adresse de
// l'interface servait les clients.

function hcst_accents():array{
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        return brand_ui_accents(brand_palette());
    }
    return array("accent"=>"#10b981","accent_dark"=>"#0d9488","accent_soft"=>"rgba(16,185,129,.12)");
}

// Feuille de style émise HORS de _ENGINE_parse_body (chaque bloc {...} y serait
// lu comme un jeton), une fois par requête.
function hcst_style():string{
    if(isset($GLOBALS["HCST_STYLE"])){return "";}
    $GLOBALS["HCST_STYLE"]=true;
    $a=hcst_accents();
    $teal=$a["accent_dark"];
    return "<style>
.hcst-kpis{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(230px,1fr))}
.hcst-kpi{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px 20px 16px;box-shadow:0 1px 2px rgba(15,23,42,.04);display:flex;flex-direction:column;gap:8px;min-width:0}
.hcst-kpi-head{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
.hcst-kpi-label{font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#334155;line-height:16px}
.hcst-kpi-ico{width:36px;height:36px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;font-size:16px;flex:0 0 auto}
.hcst-kpi-btn{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#2563eb;background:#eff6ff;border-radius:8px;padding:5px 10px;text-decoration:none!important;white-space:nowrap}
.hcst-kpi-btn:hover{background:#dbeafe}
.hcst-kpi-value{font-size:26px;font-weight:700;color:#0f172a;line-height:32px;font-variant-numeric:tabular-nums;overflow-wrap:anywhere}
.hcst-kpi-addr{font-size:21px;line-height:28px;overflow-wrap:normal}
.hcst-kpi-unit{font-size:13px;font-weight:500;color:#64748b;margin-left:4px}
.hcst-kpi-tags{display:flex;flex-wrap:wrap;gap:6px}
.hcst-kpi-note{font-size:12px;color:#64748b}
.hcst-kpi-alert{font-size:12px;font-weight:600;color:#b91c1c}
.hcst-kpi-sub{display:flex;justify-content:space-between;gap:10px;font-size:13px;color:#64748b}
.hcst-kpi-sub b{font-weight:600}
.hcst-dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:6px;vertical-align:middle}
.hcst-bar{height:4px;border-radius:4px;background:#f1f5f9;overflow:hidden;margin-top:2px}
.hcst-bar>span{display:block;height:100%;border-radius:4px}
.hcst-t-teal .hcst-kpi-ico{background:#ccfbf1;color:$teal}.hcst-t-teal .hcst-tone{color:$teal}.hcst-t-teal .hcst-bar>span,.hcst-t-teal .hcst-dot{background:$teal}
.hcst-t-blue .hcst-kpi-ico{background:#dbeafe;color:#2563eb}.hcst-t-blue .hcst-tone{color:#2563eb}.hcst-t-blue .hcst-bar>span,.hcst-t-blue .hcst-dot{background:#2563eb}
.hcst-t-amber .hcst-kpi-ico{background:#fef3c7;color:#b45309}.hcst-t-amber .hcst-tone{color:#b45309}.hcst-t-amber .hcst-bar>span,.hcst-t-amber .hcst-dot{background:#d97706}
.hcst-t-red .hcst-kpi-ico{background:#fee2e2;color:#b91c1c}.hcst-t-red .hcst-tone{color:#b91c1c}.hcst-t-red .hcst-bar>span,.hcst-t-red .hcst-dot{background:#dc2626}
.hcst-t-gray .hcst-kpi-ico{background:#f1f5f9;color:#64748b}.hcst-t-gray .hcst-tone{color:#64748b}.hcst-t-gray .hcst-bar>span,.hcst-t-gray .hcst-dot{background:#94a3b8}
.hcst-card{padding:20px;margin-top:16px}
.hcst-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding-bottom:16px;border-bottom:1px solid #f1f5f9}
#hcst-periods{flex-wrap:wrap}#hcst-periods a{text-decoration:none!important}
#hcst-periods a::first-letter{text-transform:uppercase}
#hcst-periods a.on{color:$teal}
.hcst-stats{display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));margin-top:16px}
.hcst-stat{border-radius:10px;padding:14px 16px;color:#fff;cursor:pointer;border:2px solid transparent;transition:transform .1s}
.hcst-stat:hover{transform:translateY(-1px)}
.hcst-stat.on{border-color:#0f172a;box-shadow:0 0 0 2px #fff inset}
.hcst-stat-head{display:flex;justify-content:space-between;align-items:center;font-size:13px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;opacity:.95}
.hcst-stat-val{font-size:26px;font-weight:700;margin-top:6px;font-variant-numeric:tabular-nums}
.hcst-stat-val small{font-size:14px;font-weight:500;opacity:.85;margin-left:4px}
.hcst-stat-sub{font-size:12px;opacity:.85;margin-top:4px}
.hcst-s-latency{background:$teal}.hcst-s-connections{background:#2563eb}.hcst-s-queries{background:#1e293b}.hcst-s-bandwidth{background:#d97706}
.hcst-chart-head{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin:22px 0 10px}
.hcst-chart-title{font-size:16px;font-weight:700;color:#0f172a}
.hcst-chart-title small{font-size:13px;font-weight:400;color:#64748b;margin-left:6px}
.hcst-chart-btns{display:flex;gap:6px}
.hcst-chart-btns a{border:1px solid #e2e8f0;border-radius:8px;padding:4px 12px;font-size:13px;color:#475569;text-decoration:none!important;background:#fff;white-space:nowrap}
.hcst-chart-btns a.on{border-color:$teal;color:$teal;background:#f0fdfa;font-weight:600}
.hcst-chart-box{position:relative;height:280px;border:1px solid #e2e8f0;border-radius:10px;padding:12px}
.hcst-topo-head{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin:26px 0 10px;padding-top:18px;border-top:1px solid #f1f5f9}
.hcst-topo-title{font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#334155}
.hcst-topo-algo{font-size:13px;color:#64748b}.hcst-topo-algo b{color:#0f172a}
.hcst-tbl{border:1px solid #e2e8f0}
.hcst-tbl th{white-space:nowrap}
.hcst-tbl tr:hover td{background:#fbfeff}
.hcst-tbl .ak-pill i{font-size:7px}
.hcst-ping-ok{color:$teal;font-weight:600}.hcst-ping-warn{color:#b45309;font-weight:600}.hcst-ping-bad{color:#b91c1c;font-weight:600}
.hcst-edit{color:$teal;font-weight:600;text-decoration:none!important}
.hcst-muted{color:#94a3b8}
.hcst-empty{padding:24px;text-align:center;color:#64748b}
@media(max-width:820px){.hcst-tbl thead{display:none}.hcst-tbl td{display:block;border-top:0;padding:4px 14px}.hcst-tbl tr{display:block;border-top:1px solid #f1f5f9;padding:8px 0}}
</style>";
}

// Une tuile KPI. $k : label, icon, value, unit, tags (html), note, alert, left,
// right, pct (null = pas de barre), tone (teal|blue|amber|red|gray), btn (js,label).
function hcst_kpi(array $k):string{
    $tone=$k["tone"] ?? "gray";
    $h=array();
    $h[]="<div class='hcst-kpi hcst-t-$tone'>";
    $h[]="<div class='hcst-kpi-head'><span class='hcst-kpi-label'>{$k["label"]}</span>";
    if(isset($k["btn"])){
        $h[]="<a class='hcst-kpi-btn' href=\"javascript:blur();\" OnClick=\"{$k["btn"][0]}\"><i class='".ico_params."'></i>{$k["btn"][1]}</a>";
    }else{
        $h[]="<span class='hcst-kpi-ico'><i class='{$k["icon"]}'></i></span>";
    }
    $h[]="</div>";
    $unit=isset($k["unit"])&&$k["unit"]!==""?"<span class='hcst-kpi-unit'>{$k["unit"]}</span>":"";
    $vcls=!empty($k["addr"])?" hcst-kpi-addr":"";
    $h[]="<div class='hcst-kpi-value$vcls'>{$k["value"]}$unit</div>";
    if(!empty($k["tags"])){$h[]="<div class='hcst-kpi-tags'>{$k["tags"]}</div>";}
    if(!empty($k["note"])){$h[]="<div class='hcst-kpi-note'>{$k["note"]}</div>";}
    if(!empty($k["alert"])){$h[]="<div class='hcst-kpi-alert'><i class='fas fa-triangle-exclamation'></i>&nbsp;{$k["alert"]}</div>";}
    if(isset($k["left"]) || isset($k["right"])){
        $h[]="<div class='hcst-kpi-sub'><span class='hcst-tone'>".($k["left"] ?? "")."</span><span>".($k["right"] ?? "")."</span></div>";
    }
    if(isset($k["pct"]) && $k["pct"]!==null){
        $pct=max(0,min(100,floatval($k["pct"])));
        $h[]="<div class='hcst-bar'><span style='width:{$pct}%'></span></div>";
    }
    $h[]="</div>";
    return implode("",$h);
}

// Les valeurs affichées viennent du daemon : jamais injectées brutes.
function hcst_e($s):string{
    return htmlspecialchars((string)$s,ENT_QUOTES);
}

function hcst_algo_label():string{
    $HaClusterBalance=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterBalance"));
    $algo=array("source"=>"{strict-hashed-ip}","roundrobin"=>"{round-robin}","leastconn"=>"{leastconn}");
    if(!isset($algo[$HaClusterBalance])){$HaClusterBalance="leastconn";}
    return $algo[$HaClusterBalance];
}

// État d'écoute observé ; null quand le daemon ne connaît pas encore la route.
function hcst_listen_state(){
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/listen");
    $json=json_decode((string)$raw);
    if(!is_object($json) || empty($json->success) || !is_object($json->data)){
        return null;
    }
    return $json->data;
}

function hcst_tile_listen($json):string{
    $page=CurrentPageName();
    $label="{hacluster_listening}";
    $HaClusterUseAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterUseAddr");
    if(strlen($HaClusterUseAddr)<3){
        return hcst_kpi(array("label"=>"{MISSING_PARAMETER}","icon"=>ico_server,"value"=>"{server_address}","tone"=>"red",
            "btn"=>array("Loadjs('$page?section-ipaddr-js=yes')","{modify}")));
    }
    $cert=hcst_certificate_problem();
    if($cert!==null){return $cert;}
    if(!is_object($json) || !$json->status){
        $err=is_object($json)?hcst_e($json->Error):"";
        return hcst_kpi(array("label"=>$label,"icon"=>ico_bug,"value"=>"{error}","note"=>$err,"tone"=>"red"));
    }

    // Nœuds de la ferme principale : proxyN, état opérationnel 2 = UP.
    $up=0;$total=0;
    if(property_exists($json,"Backends") && $json->Backends){
        foreach($json->Backends as $name=>$class){
            if(!preg_match("#^proxy([0-9]+)$#",$name)){continue;}
            $total++;
            if(intval($class->srv_op_state)==2){$up++;}
        }
    }
    $port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterPort"));
    if($port==0){$port=3128;}
    $iface=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterInterface"));
    $k=array("label"=>$label,"icon"=>ico_server,"tone"=>"teal","addr"=>true);

    $ls=hcst_listen_state();
    if($ls===null){
        // Daemon antérieur à /hacluster/server/listen : on ne sait que la config.
        $k["value"]=hcst_e("$iface:$port");
    }else{
        $listening=is_array($ls->listening)?$ls->listening:array();
        $ifaceIP=(string)$ls->interface_ip;
        $k["note"]="{hacluster_configured_interface}: ".hcst_e($ls->interface).($ifaceIP!=""?" (".hcst_e($ifaceIP).")":"");
        if(count($listening)==0){
            $k["value"]="&mdash;";
            $k["tone"]="red";
            $k["alert"]=$ls->running?"{hacluster_listen_none}":"{not_running}";
        }else{
            $k["value"]=hcst_e(implode(" · ",$listening));
            $tags=array();
            $fo=$ls->failover;
            if($fo->enabled){
                $vip=(string)$fo->floating_ip;
                $onVIP=false;
                foreach($listening as $addr){
                    if($vip!="" && strpos($addr,"$vip:")===0){$onVIP=true;}
                }
                if($onVIP){
                    $tags[]="<span class='ak-tag ak-ok'><i class='fas fa-location-crosshairs'></i>{floating_ip}</span>";
                }elseif(!$fo->holds_floating_ip){
                    $tags[]="<span class='ak-tag ak-warn'><i class='fas fa-right-left'></i>{hacluster_vip_on_peer}</span>";
                }
            }
            $k["tags"]=implode("",$tags);
            if(!$ls->match){
                $k["tone"]="red";
                $k["alert"]="{hacluster_listen_mismatch} ".hcst_e($ls->expected);
            }
        }
    }

    if($total==0){
        $k["left"]="0 {nodes}";
        if($k["tone"]=="teal"){$k["tone"]="amber";}
        $k["pct"]=0;
    }else{
        $k["left"]="<span class='hcst-dot'></span><b>$up/$total</b> {hacluster_nodes_up}";
        if($up<$total && $k["tone"]=="teal"){$k["tone"]="amber";}
        $k["pct"]=round($up/$total*100);
    }
    $k["right"]=hcst_algo_label();
    return hcst_kpi($k);
}

// Certificat du service de cluster absent : tuile rouge avec création.
function hcst_certificate_problem(){
    $ClusterServiceCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterServiceCertificate");
    if(strlen($ClusterServiceCertificate)<3){
        return hcst_kpi(array("label"=>"{certificate}","icon"=>ico_certificate,"value"=>"{missing}","tone"=>"red",
            "btn"=>array(CreateCertifJs(),"{create_certificate}")));
    }
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb=new CertificatesDB();
    [$exists,$ID]=$cdb->exists($ClusterServiceCertificate);
    if($cdb->lastError!==""){
        return hcst_kpi(array("label"=>"{certificate}","icon"=>ico_certificate,"value"=>"{error}","note"=>hcst_e($cdb->lastError),"tone"=>"red"));
    }
    if($exists && $ID>0){return null;}
    return hcst_kpi(array("label"=>"{certificate}","icon"=>ico_certificate,"value"=>"{missing}","tone"=>"red",
        "btn"=>array(CreateCertifJs(),"{create_certificate}")));
}

function hcst_tile_latency():string{
    $ms=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HACLUSTER_LATENCY_MS"));
    $k=array("label"=>"{latency}","icon"=>"fas fa-clock","value"=>sprintf("%.2f",$ms),"unit"=>"ms");
    if($ms<=200){$k["tone"]="teal";$k["left"]="{hacluster_latency_good}";$k["right"]="&lt; 200 ms";}
    elseif($ms<=500){$k["tone"]="blue";$k["left"]="{hacluster_latency_fair}";$k["right"]="&lt; 500 ms";}
    elseif($ms<=1200){$k["tone"]="amber";$k["left"]="{hacluster_latency_slow}";$k["right"]="&lt; 1200 ms";}
    else{$k["tone"]="red";$k["left"]="{hacluster_latency_critical}";$k["right"]="&gt; 1200 ms";}
    $k["pct"]=round(min($ms,1200)/1200*100);
    return hcst_kpi($k);
}

function hcst_split_bytes(string $s):array{
    $s=trim(strip_tags($s));
    $p=strrpos($s," ");
    if($p===false){return array($s,"");}
    return array(substr($s,0,$p),substr($s,$p+1));
}

function hcst_tile_traffic($json):string{
    $k=array("label"=>"{mb_out}","icon"=>"fas fa-arrow-right-arrow-left","tone"=>"blue","right"=>"HaCluster");
    if(!is_object($json) || !property_exists($json,"TotalBytesOut")){
        $k["value"]="{error}";$k["tone"]="red";
        return hcst_kpi($k);
    }
    if(intval($json->TotalBytesOut)==0){
        $k["value"]="0";$k["tone"]="gray";
        return hcst_kpi($k);
    }
    list($k["value"],$k["unit"])=hcst_split_bytes(FormatBytes($json->TotalBytesOut/1024));
    $rate=intval($json->BytesOutRate ?? 0);
    $k["left"]="{hacluster_out_rate}: ".($rate>0?trim(strip_tags(FormatBytes($rate/1024)))."/s":"0");
    return hcst_kpi($k);
}

function hcst_tile_connections($json):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $btn=array("Loadjs('$page?maxconns-js=yes')","{modify}");
    $cur=-1;$max=0;
    $jsonStats=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/stats"));
    if(is_object($jsonStats) && property_exists($jsonStats,"Stats") && is_array($jsonStats->Stats)){
        foreach($jsonStats->Stats as $stat){
            if($stat->svname<>"FRONTEND" || $stat->pxname<>"hacluster"){continue;}
            if(!property_exists($stat,"slim")){continue;}
            $cur=intval($stat->scur);$max=intval($stat->slim);
            break;
        }
    }
    if($cur<0 && is_object($json) && property_exists($json,"Maxconn")){
        $cur=intval($json->CurrConns);$max=intval($json->Maxconn);
    }
    if($cur<0 || $max<=0){
        return hcst_kpi(array("label"=>"{connections}","value"=>"{error}","tone"=>"red","btn"=>$btn));
    }
    $prc=round($cur/$max*100,2);
    $tone="teal";
    if($prc>90){$tone="amber";}
    if($prc>95){$tone="red";}
    return hcst_kpi(array("label"=>"{connections} ($prc%)","value"=>$tpl->FormatNumber($cur),
        "unit"=>"/ ".$tpl->FormatNumber($max)." max","tone"=>$tone,"btn"=>$btn,
        "left"=>"{hacluster_free_capacity}: ".round(100-$prc,2)."%","pct"=>$prc));
}

function status_top():bool{
    $tpl=new template_admin();
    $page=currentPageName();

    $HaClusterInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterInterface"));
    if(strlen($HaClusterInterface)<3) {
        $button="<div style='text-align:right;margin:10px'>".
            $tpl->button_autnonome("{listen_interface}","Loadjs('$page?section-listen-js=yes')",ico_nic,"",0,"btn-warning",256)."</div>";
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{listen_interface}||{lb_select_nic}$button"));
        return true;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/info"));
    $html=array();
    $html[]="<div id='checkCurStatus'></div>";
    $html[]="<div class='hcst-kpis'>";
    $html[]=hcst_tile_listen($json);
    $html[]=hcst_tile_latency();
    $html[]=hcst_tile_traffic($json);
    $html[]=hcst_tile_connections($json);
    $html[]="</div>";
    $html[]="<script>LoadAjaxSilent('checkCurStatus','fw.hacluster.backends.php?checkCurStatus=yes');</script>";
    echo hcst_style().$tpl->_ENGINE_parse_body(implode("",$html));
    return true;
}

function hcst_periods():array{
    return array("hourly"=>"{hourly}","day"=>"{day}","yesterday"=>"{yesterday}","week"=>"{week}","month"=>"{month}","year"=>"{year}");
}
function hcst_metrics():array{
    return array("latency","connections","queries","bandwidth");
}

// Carte de télémétrie : la barre (période + rafraîchir) est rendue une fois ;
// le contenu (cartes, courbe, topologie) se recharge seul dans hcst-telemetry.
function status_center():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM hacluster_backends WHERE enabled=1");
    if(intval($ligne["tcount"])==0){
        echo "<div style='margin:20px'>".$tpl->_ENGINE_parse_body($tpl->div_warning("<p style='margin-left:20px;font-size:16px'>{haclutserno_nodes}</p>")."</div>");
        return true;
    }
    $h=array();
    $h[]="<div class='ak-card hcst-card'>";
    $h[]="<div class='hcst-toolbar'><div class='ak-seg' id='hcst-periods'>";
    foreach(hcst_periods() as $key=>$label){
        $on=$key=="hourly"?" on":"";
        $h[]="<a href=\"javascript:blur();\" data-p='$key' class='ak-seg-b$on' OnClick=\"HcstPeriod('$key',this)\">$label</a>";
    }
    $h[]="</div>";
    $h[]="<a class='ak-btn ak-btn-primary' href=\"javascript:blur();\" OnClick=\"HcstRefresh()\"><i class='fas fa-rotate'></i>{refresh}</a>";
    $h[]="</div>";
    $h[]="<div id='hcst-telemetry'></div>";
    $h[]="</div>";
    $js="<script>
window._hcstPeriod='hourly';window._hcstMetric=window._hcstMetric||'latency';
function HcstDestroy(){ if(window._hcstChart){ try{window._hcstChart.destroy();}catch(e){} window._hcstChart=null; } }
function HcstRefresh(){ HcstDestroy(); LoadAjaxSilent('hcst-telemetry','$page?graphs='+window._hcstPeriod+'&metric='+window._hcstMetric); }
function HcstPeriod(p,el){ window._hcstPeriod=p; $('#hcst-periods a').removeClass('on'); $(el).addClass('on'); HcstRefresh(); }
function HcstMetric(m){ window._hcstMetric=m; HcstRefresh(); }
HcstRefresh();
</script>";
    echo hcst_style().$tpl->_ENGINE_parse_body(implode("",$h)).$js;
    return true;
}

function _period_to_hours($period):int{
    $map=array("hourly"=>1,"day"=>24,"yesterday"=>48,"week"=>168,"month"=>720,"year"=>2160);
    return isset($map[$period]) ? $map[$period] : 24;
}

function _ts_format($hours){
    if($hours>168) return 'M d';
    if($hours>48) return 'M d H:i';
    return 'H:i';
}

// Synthèse de la période, depuis les mêmes séries que la courbe : latence
// moyenne pondérée par les échantillons, pic de connexions, totaux de requêtes
// et de volume (points bruts = deltas ; points agrégés = sum_*).
function hcst_period_summary(array $lat,array $met):array{
    $out=array("latency"=>null,"connections"=>null,"queries"=>null,"bandwidth"=>null);
    $sum=0;$n=0;
    foreach($lat as $p){
        $w=isset($p->samples)?max(1,intval($p->samples)):1;
        $sum+=floatval($p->latency_ms)*$w;$n+=$w;
    }
    if($n>0){$out["latency"]=$sum/$n;}
    if(count($met)>0){
        $peak=0;$q=0;$bw=0;
        foreach($met as $p){
            $peak=max($peak,floatval($p->max_connections ?? $p->connections ?? 0));
            $q+=isset($p->sum_queries)?floatval($p->sum_queries):floatval($p->queries ?? 0)*max(1,intval($p->samples ?? 1));
            $bw+=isset($p->sum_bandwidth_mb)?floatval($p->sum_bandwidth_mb):floatval($p->bandwidth_mb ?? 0)*max(1,intval($p->samples ?? 1));
        }
        $out["connections"]=$peak;$out["queries"]=$q;$out["bandwidth"]=$bw;
    }
    return $out;
}

function status_graphs():bool{
    $tpl=new template_admin();
    $period=(string)$_GET["graphs"];
    if(!isset(hcst_periods()[$period])){$period="hourly";}
    $metric=(string)($_GET["metric"] ?? "latency");
    if(!in_array($metric,hcst_metrics(),true)){$metric="latency";}
    $hours=_period_to_hours($period);
    $fmt=_ts_format($hours);

    $latJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/latency?hours=$hours"));
    $metJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/metrics?hours=$hours"));
    $latPts=array();$latRes="raw";$metPts=array();$metRes="raw";
    if(is_object($latJson) && !empty($latJson->success) && is_object($latJson->data)){
        $latPts=(array)($latJson->data->points ?? array());$latRes=(string)($latJson->data->resolution ?? "raw");
    }
    if(is_object($metJson) && !empty($metJson->success) && is_object($metJson->data)){
        $metPts=(array)($metJson->data->points ?? array());$metRes=(string)($metJson->data->resolution ?? "raw");
    }
    $sum=hcst_period_summary($latPts,$metPts);

    $cards=array(
        "latency"=>array("{latency}","fas fa-bolt",$sum["latency"]===null?"&mdash;":round($sum["latency"]),"ms","{hacluster_avg_period}"),
        "connections"=>array("{connections}","fas fa-arrow-right-arrow-left",$sum["connections"]===null?"&mdash;":$tpl->FormatNumber(round($sum["connections"])),"","{hacluster_peak_period}"),
        "queries"=>array("{requests}","fas fa-file-lines",$sum["queries"]===null?"&mdash;":$tpl->FormatNumber(round($sum["queries"])),"","{hacluster_total_period}"),
        "bandwidth"=>array("{bandwidth}","fas fa-download",$sum["bandwidth"]===null?"&mdash;":$tpl->FormatNumber(round($sum["bandwidth"])),"MB","{hacluster_total_period}"),
    );
    $h=array();
    $h[]="<div class='hcst-stats'>";
    foreach($cards as $key=>$c){
        $on=$key==$metric?" on":"";
        $unit=$c[3]!=""?"<small>{$c[3]}</small>":"";
        $h[]="<div class='hcst-stat hcst-s-$key$on' OnClick=\"HcstMetric('$key')\">";
        $h[]="<div class='hcst-stat-head'><span>{$c[0]}</span><i class='{$c[1]}'></i></div>";
        $h[]="<div class='hcst-stat-val'>{$c[2]}$unit</div>";
        $h[]="<div class='hcst-stat-sub'>{$c[4]}</div></div>";
    }
    $h[]="</div>";

    // Série de la métrique choisie.
    $colors=array("latency"=>hcst_accents()["accent_dark"],"connections"=>"#2563eb","queries"=>"#1e293b","bandwidth"=>"#d97706");
    $titles=array("latency"=>"{latency} (ms)","connections"=>"{connections}","queries"=>"{requests}","bandwidth"=>"{bandwidth} (MB)");
    $labels=array();$main=array();$peak=array();
    $pts=$metric=="latency"?$latPts:$metPts;
    $res=$metric=="latency"?$latRes:$metRes;
    $field=array("latency"=>"latency_ms","connections"=>"connections","queries"=>"queries","bandwidth"=>"bandwidth_mb")[$metric];
    $maxField=array("latency"=>"max_ms","connections"=>"max_connections","queries"=>"max_queries","bandwidth"=>"max_bandwidth_mb")[$metric];
    foreach($pts as $p){
        $labels[]=date($fmt,intval($p->ts));
        $main[]=round(floatval($p->$field ?? 0),2);
        if($res!=="raw"){$peak[]=round(floatval($p->$maxField ?? 0),2);}
    }
    $periodLabel=hcst_periods()[$period];
    $h[]="<div class='hcst-chart-head'>";
    $h[]="<div class='hcst-chart-title'><span class='hcst-dot' style='background:{$colors[$metric]}'></span>{$titles[$metric]}<small>&mdash; $periodLabel ($res)</small></div>";
    $h[]="<div class='hcst-chart-btns'>";
    $h[]="<a href=\"javascript:blur();\" id='hcst-mode-curve' class='on' OnClick=\"HcstMode(false)\">{hacluster_curve}</a>";
    $h[]="<a href=\"javascript:blur();\" id='hcst-mode-raw' OnClick=\"HcstMode(true)\">{hacluster_raw}</a>";
    $h[]="<a href=\"javascript:blur();\" title=\"{hacluster_csv}\" OnClick=\"HcstCsv()\"><i class='fas fa-download'></i></a>";
    $h[]="</div></div>";
    if(count($main)==0){
        $h[]="<div class='hcst-chart-box'><div class='hcst-empty'>{no_data}</div></div>";
    }else{
        $h[]="<div class='hcst-chart-box'><canvas id='hcst-canvas'></canvas></div>";
    }
    $h[]=hcst_topology();

    $chartjs="";
    if(count($main)>0){
        $mainName=$tpl->javascript_parse_text($titles[$metric]);
        $peakName=$tpl->javascript_parse_text("{hacluster_peak}");
        $data=array("labels"=>$labels,"main"=>$main,"peak"=>$peak,"color"=>$colors[$metric],
            "mainName"=>$mainName,"peakName"=>$peakName,"file"=>"hacluster-$metric-$period.csv");
        $chartjs="<script>
window._hcstData=".json_encode($data).";
function HcstEnsureChart(cb){ if(typeof Chart!=='undefined'){cb();return;} var s=document.createElement('script'); s.src='angular/js/plugins/chartJs/Chart.min.4.5.1.js'; s.onload=function(){cb();}; document.head.appendChild(s); }
function HcstHexA(hex,a){ var n=parseInt(hex.replace('#',''),16); return 'rgba('+((n>>16)&255)+','+((n>>8)&255)+','+(n&255)+','+a+')'; }
function HcstDraw(){
  var c=document.getElementById('hcst-canvas'); if(!c){return;}
  HcstDestroy();
  var d=window._hcstData, ctx=c.getContext('2d'), g=ctx.createLinearGradient(0,0,0,260);
  g.addColorStop(0,HcstHexA(d.color,0.25)); g.addColorStop(1,HcstHexA(d.color,0));
  var ds=[{label:d.mainName,data:d.main,borderColor:d.color,backgroundColor:g,fill:true,tension:0.35,borderWidth:2.5,pointRadius:0,pointHoverRadius:5}];
  if(d.peak.length){ ds.push({label:d.peakName,data:d.peak,borderColor:HcstHexA(d.color,0.45),borderDash:[5,3],fill:false,tension:0.35,borderWidth:1.5,pointRadius:0}); }
  window._hcstChart=new Chart(c,{type:'line',data:{labels:d.labels,datasets:ds},options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},plugins:{legend:{display:d.peak.length>0,position:'bottom',labels:{usePointStyle:true}}},scales:{x:{grid:{display:false},ticks:{autoSkip:true,maxTicksLimit:8,color:'#94a3b8'}},y:{beginAtZero:true,grid:{color:'#f1f5f9'},ticks:{color:'#94a3b8'}}}}});
}
function HcstMode(raw){
  $('#hcst-mode-curve').toggleClass('on',!raw); $('#hcst-mode-raw').toggleClass('on',raw);
  var ch=window._hcstChart; if(!ch){return;}
  ch.data.datasets.forEach(function(x){ x.tension=raw?0:0.35; x.pointRadius=raw?2:0; });
  ch.update();
}
function HcstCsv(){
  var d=window._hcstData; if(!d){return;}
  var lines=['time;'+d.mainName+(d.peak.length?';'+d.peakName:'')];
  for(var i=0;i<d.labels.length;i++){ lines.push(d.labels[i]+';'+d.main[i]+(d.peak.length?';'+d.peak[i]:'')); }
  var a=document.createElement('a'); a.href=URL.createObjectURL(new Blob([lines.join('\\n')],{type:'text/csv'}));
  a.download=d.file; document.body.appendChild(a); a.click(); setTimeout(function(){URL.revokeObjectURL(a.href);a.remove();},500);
}
HcstEnsureChart(HcstDraw);
</script>";
    }
    echo $tpl->_ENGINE_parse_body(implode("",$h)).$chartjs."<script>NoSpinner();</script>";
    return true;
}

// Topologie : les serveurs de la ferme principale (backend « proxys »), état
// et durée du dernier contrôle tels que HAProxy les voit, noms depuis haproxy.db.
function hcst_topology():string{
    $page=CurrentPageName();
    $rows=array();
    $jsonStats=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/stats"));
    if(is_object($jsonStats) && property_exists($jsonStats,"Stats") && is_array($jsonStats->Stats)){
        foreach($jsonStats->Stats as $stat){
            if($stat->pxname<>"proxys" || !preg_match("#^proxy([0-9]+)$#",(string)$stat->svname,$re)){continue;}
            $rows[intval($re[1])]=$stat;
        }
    }
    $names=array();
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $results=$q->QUERY_SQL("SELECT ID,backendname,listen_ip,listen_port FROM hacluster_backends");
    if(is_array($results)){
        foreach($results as $l){$names[intval($l["ID"])]=$l;}
    }
    ksort($rows);
    $totalWeight=0;
    foreach($rows as $stat){
        if(strtoupper(substr((string)$stat->status,0,2))=="UP"){$totalWeight+=intval($stat->weight);}
    }

    $h=array();
    $h[]="<div class='hcst-topo-head'><span class='hcst-topo-title'>{hacluster_topology} (".count($rows).")</span>";
    $h[]="<span class='hcst-topo-algo'>{method}: <b>".hcst_algo_label()."</b></span></div>";
    $h[]="<table class='ak-tbl hcst-tbl'><thead><tr><th>{status}</th><th style='width:99%'>{proxy_server}</th><th>{listen_port}</th><th>{weight}</th><th>{hacluster_last_check}</th><th style='text-align:right'>{actions}</th></tr></thead><tbody>";
    if(count($rows)==0){
        $h[]="<tr><td colspan='6'><div class='hcst-empty'>{no_data}</div></td></tr>";
    }
    foreach($rows as $id=>$stat){
        $raw=is_object($stat->raw)?$stat->raw:new stdClass();
        $status=strtoupper((string)$stat->status);
        $backup=intval($raw->bck ?? 0)==1;
        if($backup && strpos($status,"UP")===0){$cls="standby";$txt="{hacluster_standby}";}
        elseif(strpos($status,"UP")===0){$cls="up";$txt="UP";}
        elseif(strpos($status,"MAINT")===0 || strpos($status,"DRAIN")===0 || strpos($status,"NOLB")===0){$cls="maint";$txt=hcst_e($status);}
        else{$cls="down";$txt=hcst_e($status!=""?$status:"DOWN");}
        $addr=(string)$stat->addr;
        $port="";
        if(preg_match("#^(.+):([0-9]+)$#",$addr,$m)){$addr=$m[1];$port=$m[2];}
        $name=isset($names[$id])?(string)$names[$id]["backendname"]:"proxy$id";
        $weight=intval($stat->weight);
        if($backup){$wtxt="{hacluster_standby}";}
        elseif($totalWeight>0 && $cls=="up"){$wtxt="$weight <span class='hcst-muted'>(".round($weight/$totalWeight*100)."%)</span>";}
        else{$wtxt="$weight";}
        $dur=(string)($raw->check_duration ?? "");
        $chk=hcst_e((string)($raw->check_status ?? ""));
        if($dur===""){$ping="<span class='hcst-muted'>&mdash;</span>";}
        else{
            $d=intval($dur);
            $pc=$d<100?"hcst-ping-ok":($d<1000?"hcst-ping-warn":"hcst-ping-bad");
            $ping="<span class='$pc'>$d ms</span>".($chk!=""?" <span class='hcst-muted'>$chk</span>":"");
        }
        $js="Loadjs('fw.hacluster.backends.php?backend-js=$id');";
        $h[]="<tr>";
        $tone=array("up"=>"ok","down"=>"err","maint"=>"warn","standby"=>"info");
        $h[]="<td nowrap><span class='ak-pill ak-{$tone[$cls]}'><i class='fas fa-circle'></i>$txt</span></td>";
        $h[]="<td nowrap>".hcst_e($addr)." <span class='hcst-muted'>(".hcst_e($name).")</span></td>";
        $h[]="<td nowrap>".hcst_e($port)."</td>";
        $h[]="<td nowrap>$wtxt</td>";
        $h[]="<td nowrap>$ping</td>";
        $h[]="<td nowrap style='text-align:right'><a class='hcst-edit' href=\"javascript:blur();\" OnClick=\"$js\">{modify}</a></td>";
        $h[]="</tr>";
    }
    $h[]="</tbody></table>";
    return implode("",$h);
}
