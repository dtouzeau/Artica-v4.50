<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["refreshimage"])){refresh_image();exit;}
if(isset($_GET["remote-categories-service"])){remote_categories_service_js();exit;}
if(isset($_GET["remote-categories-service-popup"])){remote_categories_service_popup();exit;}
if(isset($_POST["HaClusterUseRemoteCategoriesService"])){remote_categories_service_save();exit;}
if(isset($_POST["HaClusterUseCentralErrorPage"])){section_errorpage_save();exit;}
if(isset($_POST["HaClusterDeployArticaUpdates"])){SaveParams();exit;}
if(isset($_POST["HaClusterAsAD"])){SaveParams();exit;}
if(isset($_POST["HaClusterUseHaClient"])){SaveParams();exit;}
if(isset($_POST["HaClusterUseAddr"])){SaveParams();exit;}
if(isset($_POST["HaClusterUseLBAsDNS"])){SaveParams();exit;}
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
if(isset($_GET["maxconns-js"])){maxconns_js();exit;}
if(isset($_GET["maxconns-popup"])){maxconns_popup();exit;}
if(isset($_POST["HaClusterMaxConn"])){maxconns_save();exit;}

if(isset($_GET["restart-debug-js"])){restart_debug_js();exit;}
if(isset($_GET["restart-debug-popup"])){restart_debug_popup();exit;}
if(isset($_GET["restart-debug-step1"])){restart_debug_step1();exit;}
if(isset($_POST["HaClusterTimeDebug"])){restart_debug_save();exit;}
if(isset($_GET["restart-debug-step2"])){restart_debug_step2();exit;}


if(isset($_GET["section-errorpage-js"])){section_errorpage_js();exit;}
if(isset($_GET["section-errorpage-popup"])){section_errorpage_popup();exit;}

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
if(isset($_GET["section-dns-popup"])){section_dns_popup();exit;}

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
    $page=CurrentPageName();
    $HaClusterMaxConn=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterMaxConn");
    $form[]=$tpl->field_numeric("HaClusterMaxConn","{maxconn}",$HaClusterMaxConn,"{haproxy_maxconn}");
    $jsafter[]="dialogInstance2.close();";
    echo $tpl->form_outside(null, $form,null,"{apply}", @implode(";",$jsafter), "AsSquidAdministrator");
    return true;
}
function maxconns_save():bool{
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
    return $tpl->js_dialog8("{restart} {debug}","$page?restart-debug-popup=yes",650);
}
function restart_debug_popup():bool{
    $tpl=new template_admin();
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
    $tpl=new template_admin();
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
function section_errorpage_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{error_page_service}","$page?section-errorpage-popup=yes",650);
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
    return $tpl->js_dialog2("{dns_used_by_the_system}","$page?section-dns-popup=yes",650);
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
    $t=time();
    $HaClusterLimitAccesses=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterLimitAccesses"));
    if(!$HaClusterLimitAccesses){
        $ACLREST[] = "192.168.0.0/16";
        $ACLREST[] = "10.0.0.0/8";
        $ACLREST[] = "172.16.0.0/12";

        $H=json_encode($ACLREST);
        $HaClusterLimitAccesses=json_decode($H);
    }

    $topbuttons[] = array("Loadjs('$page?section-limit-access-add=yes')",ico_plus,"{new_entry}");
    $iconet="<i class='".ico_networks."'></i>&nbsp;";
    $html[]=$tpl->table_buttons($topbuttons);
    $html[]="<table id='table-$t' class=\"table table-hover\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th class='text-capitalize'>{items}</center></th>";
    $html[]="<th class='text-capitalize center'>DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
        foreach ($HaClusterLimitAccesses as $address){
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $md=md5(serialize($address));
            $item=$address;
            $itemEncode=urlencode($item);
            $delete=$tpl->icon_delete("Loadjs('$page?section-limit-access-delete=$itemEncode&md=$md')");

            $html[]="<tr class='$TRCLASS' id='$md'>";
            $html[]="<td>$iconet<strong>$item</strong></td>";
            $html[]="<td style='vertical-align:middle;width:1%'>$delete</td>";
            $html[]="</tr>";
        }

    $html[]="</tbody>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}


function status_center():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array=array();
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM hacluster_backends WHERE enabled=1");
    $CountOfBackend=$ligne["tcount"];

    if ($CountOfBackend==0){
        echo "<div style='margin:20px'>".$tpl->_ENGINE_parse_body($tpl->div_warning("<p style='margin-left:20px;font-size:16px'>{haclutserno_nodes}</p>")."</div>");
        return true;
    }
    $periods[]="hourly";
    $periods[]="day";
    $periods[]="yesterday";
    $periods[]="week";
    $periods[]="month";
    $periods[]="year";
    foreach ($periods as $pp){
        $array["{statistics}: {{$pp}}"]="$page?graphs=$pp";
    }

    echo $tpl->tabs_default($array);
    return true;
}
function status_graphs():bool{
    $tpl=new template_admin();
    $pp=$_GET["graphs"];
    $page=CurrentPageName();
    $time=time();
    $html[]="<div id='RefreshThisGraph$pp$time'></div>";
    $html[]="<p><img id='haclusterConnections$pp' src='img/squid/hacluster-connections-$pp.flat.png' alt='1'></p>";
    $html[]="<p><img id='haclusterLatency$pp' src='img/squid/hacluster-latency-$pp.flat.png' alt='1'></p>";
    $html[]="<p><img id='haclusterQueries$pp' src='img/squid/hacluster-queries-$pp.flat.png' alt='1'></p>";
    $html[]="<p><img id='haclusterBandwidth$pp' src='img/squid/hacluster-bandwidth-$pp.flat.png' alt='1'></p>";
    $html[]="<script>";
    $html[]=$tpl->RefreshInterval_Loadjs("RefreshThisGraph$pp$time",$page,"refreshimage=$pp");
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function refresh_image(){
    $pp=$_GET["refreshimage"];
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/refresh/graphs");
    $f[]="haclusterLatency$pp";
    $f[]="haclusterQueries$pp";
    $f[]="haclusterBandwidth$pp";
    foreach ($f as $item) {
        $html[]="if ( document.getElementById('$item') ){";
        $html[]="const img = document.getElementById('$item');";
        $html[]="const baseUrl = img.src.split('?')[0]; // Remove existing query parameters";
        $html[]="img.src = `$"."{baseUrl}?t=$"."{new Date().getTime()}`; // Append timestamp";
        $html[]="}";
    }
    echo @implode("\n", $html);
}

function tabs(){
    $page=CurrentPageName();
    $EnableZabbixAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableZabbixAgent"));
    $tpl=new template_admin();
    $array["{status}"]="$page?table-start=yes";
    $array["{parameters}"]="$page?parameters=yes";


    if($EnableZabbixAgent==1) {
        $array["{APP_ZABBIX_AGENT}"] = "fw.hacluster.zabbix.php";
    }

    echo $tpl->tabs_default($array);
}

function table_start(){
    $page=CurrentPageName();
    echo "<div id='hacluster-table-loader'></div><script>LoadAjax('hacluster-table-loader','$page?table=yes');</script>";

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


    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:350px'>";
    $html[]="<div id='hacluster-table-status-left'></div>";
    $html[]="</td>";
    $html[]="<td style='vertical-align:top;width:99%;padding-left:10px'>";
    $html[]="<div id='hacluster-table-status-top'></div>";
    $html[]="<div id='hacluster-table-status-center'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]=$jsRefres;
    $html[]="LoadAjaxSilent('hacluster-table-status-center','$page?hacluster-table-status-center=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function widget_certificate():string{
    $tpl=new template_admin();
    $ClusterServiceCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterServiceCertificate");
    if(strlen($ClusterServiceCertificate)<3){
        return $tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg",ico_certificate,"{certificate}", "{missing}"));
    }
    $q=new lib_sqlite("/home/artica/SQLITE/certificates.db",true);
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM sslcertificates WHERE CommonName='$ClusterServiceCertificate'");
    if(!$q->ok){
        return $tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg",ico_certificate,$q->mysql_error, "SQL Error"));
    }
    if(!isset($ligne["ID"])){
        $ligne["ID"]=0;
    }



    if(intval($ligne["ID"])>0) {
        return "";
    }

    $btn = array();
    $btn[0]["margin"] = 0;
    $btn[0]["name"] = "{create_certificate}";
    $btn[0]["icon"] = ico_run;
    $btn[0]["js"] = CreateCertifJs();

    return $tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg",ico_certificate,"{certificate}", "{missing}",$btn));


}
function widget_backends($json):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $HaClusterUseAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterUseAddr");
    if(strlen($HaClusterUseAddr)<3){
        $btn = array();
        $btn[0]["margin"] = 0;
        $btn[0]["name"] = "{modify}";
        $btn[0]["icon"] = ico_nic;
        $btn[0]["js"] = "Loadjs('$page?section-ipaddr-js=yes')";
        return $tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg",ico_server,"{MISSING_PARAMETER}", "{server_address}",$btn));
    }

    $widget=widget_certificate();
    if(strlen($widget)>3){
        return $widget;
    }
    if(!$json->status){
        return $tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg",ico_bug,$json->Error, "{error}"));
    }

    if(!property_exists($json,"TotalBytesOut")) {
        return $tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg",ico_server,"{status}", "{error}"));

    }
    $bb=0;
    $bf=0;
    if(property_exists($json,"Backends")){

        foreach ($json->Backends as $Backend=>$class) {
            if(!preg_match("#^proxy([0-9]+)#",$Backend)){
                VERBOSE("SKIP $Backend..",__LINE__);
                continue;
            }
            $srv_op_state=$class->srv_op_state;
            VERBOSE("FOUND $Backend state=$srv_op_state",__LINE__);
            if($srv_op_state<>2){
                $bf++;
                continue;
            }
            $bb++;

        }
    }
    if($bb==0){
        return $tpl->_ENGINE_parse_body($tpl->widget_style1("yellow-bg",ico_server,"{backends}", 0));
    }
    if($bf==0){
        return $tpl->_ENGINE_parse_body($tpl->widget_style1("navy-bg",ico_server,"{backends}", $bb));
    }
    $Sum=$bf+$bb;
    return $tpl->_ENGINE_parse_body($tpl->widget_style1("yellow-bg",ico_server,"{backends}", "$bb/$Sum"));


}
function widget_mbout($json):string{
    $tpl=new template_admin();
    if(!property_exists($json,"TotalBytesOut")) {
        return $tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg",ico_weight,"{mb_out}", "{error}"));
    }
    if($json->TotalBytesOut==0){
        return  $tpl->_ENGINE_parse_body($tpl->widget_style1("gray-bg",ico_weight,"{mb_out}", 0));
    }

    $TotalBytesOut=FormatBytes($json->TotalBytesOut/1024);
    return  $tpl->_ENGINE_parse_body($tpl->widget_style1("blue-bg",ico_weight,"{mb_out}", $TotalBytesOut));
}
function widget_latency():string{
    $tpl=new template_admin();
    $HACLUSTER_LATENCY_MS=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HACLUSTER_LATENCY_MS");
    $bg="navy-bg";

    if(intval($HACLUSTER_LATENCY_MS)>200){
        $bg="lazur-bg";
    }

    if(intval($HACLUSTER_LATENCY_MS)>500){
        $bg="yellow-bg";
    }
    if(intval($HACLUSTER_LATENCY_MS)>1200){
        $bg="red-bg";
    }
    return $tpl->_ENGINE_parse_body($tpl->widget_style1($bg,ico_timeout,"{latency}",
        "{$HACLUSTER_LATENCY_MS}ms"));
}
function widget_connections($json):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $btn = array();
    $btn[0]["name"] = "{modify}";
    $btn[0]["icon"] = ico_params;
    $btn[0]["js"] = "Loadjs('$page?maxconns-js=yes')";

    $jsonStats=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/stats"));
    if(property_exists($jsonStats, "Stats")) {
        foreach ($jsonStats->Stats as $stat) {
            $svname=$stat->svname;
            if($svname<>"FRONTEND"){
                continue;
            }
            $pxname=$stat->pxname;
            if($pxname<>"hacluster"){
                continue;
            }
            echo "<!-- Frontend:$pxname -->\n";

            if(!property_exists($stat, "slim")) {
                var_dump($stat);
                echo "<!-- Frontend:slim Not found -->\n";
                continue;
            }
            $Maxconn=$tpl->FormatNumber($stat->slim);
            $prc=round(($stat->scur/$stat->slim)*100,2);
            if($prc>95){
                return $tpl->_ENGINE_parse_body($tpl->widget_style1("bg-red",ico_clouds,"{connections} $prc%", $tpl->FormatNumber($stat->scur)."/$Maxconn",$btn));
            }
            if($prc>90){
                return $tpl->_ENGINE_parse_body($tpl->widget_style1("yellow-bg",ico_clouds,"{connections} $prc%", $tpl->FormatNumber($stat->scur)."/$Maxconn",$btn));
            }

            return $tpl->_ENGINE_parse_body($tpl->widget_style1("blue-bg",ico_clouds,"{connections} $prc%", $tpl->FormatNumber($stat->scur)."/$Maxconn",$btn));
        }
    }
    echo "// Not found\n";

    if(!property_exists($json,"Maxconn")) {
        return $tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg",ico_clouds,"{connections}", "{error}"));
    }
    if($json->CurrConns==0){
        return  $tpl->_ENGINE_parse_body($tpl->widget_style1("gray-bg",ico_clouds,"{connections}", 0,$btn));
    }
    $Maxconn=$tpl->FormatNumber($json->Maxconn);
    $prc=round(($json->CurrConns/$json->Maxconn)*100,2);
    if($prc>95){
        return $tpl->_ENGINE_parse_body($tpl->widget_style1("bg-red",ico_clouds,"{connections} $prc%", $tpl->FormatNumber($json->CurrConns)."/$Maxconn",$btn));
    }
    if($prc>90){
        return $tpl->_ENGINE_parse_body($tpl->widget_style1("yellow-bg",ico_clouds,"{connections} $prc%", $tpl->FormatNumber($json->CurrConns)."/$Maxconn",$btn));
    }

    return $tpl->_ENGINE_parse_body($tpl->widget_style1("blue-bg",ico_clouds,"{connections} $prc%", $tpl->FormatNumber($json->CurrConns)."/$Maxconn",$btn));

}

function status_top():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    $HACLUSTER_CONFIG_FAILED=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HACLUSTER_CONFIG_FAILED"));
    if(strlen($HACLUSTER_CONFIG_FAILED)>4) {
        $explain = $tpl->div_error("<strong>{squid_bungled_explain}:</strong><p>" . str_replace("\n", "<br>", base64_decode($HACLUSTER_CONFIG_FAILED)) . "</p>");
        echo $tpl->_ENGINE_parse_body($explain);
        return true;
    }
    $HaClusterInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterInterface"));
    if(strlen($HaClusterInterface)<3) {
        $button="<div style='text-align:right;margin:10px'>".
            $tpl->button_autnonome("{listen_interface}","Loadjs('$page?section-listen-js=yes')",ico_nic,"",0,"btn-warning",256)."</div>";
        $explain = $tpl->div_warning("{listen_interface}||{lb_select_nic}$button");
        echo $tpl->_ENGINE_parse_body($explain);
        return true;
    }

    //

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/info"));
    $stylePad="vertical-align:top;width:25%;padding-left:5px";
    $widget_backends=widget_backends($json);
    $widget_mbout=widget_mbout($json);
    $widget_connections=widget_connections($json);

    $widget_latency=widget_latency();

    $html[]="<table style='width:100%;margin-top:-7px'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:25%'>$widget_backends</td>";
    $html[]="<td style='$stylePad'>$widget_latency</td>";
    $html[]="<td style='$stylePad'>$widget_mbout</td>";
    $html[]="<td style='$stylePad'>$widget_connections</td>";
    $html[]="</tr>";
    $html[]="</table>";

    //
echo @implode("", $html);

    return true;
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
function parameters_haclusterTimeouts($tpl){
    $page=CurrentPageName();
    for($i=3;$i<24;$i++){
        $reload[$i]="{each} $i {hours}";

    }
    $reload[999]="{never}";
    $HaClusterTimeOutConnect=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTimeOutConnect"));
    $HaClusterSeverTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterSeverTimeout"));
    $HaClusterClientTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClientTimeout"));
    $HaClusterReloadEach=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterReloadEach"));
    $HaClusterCheckInt=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterCheckInt"));
    $HaClusterCheckFall=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterCheckFall"));
    $HaClusterCheckRise=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterCheckRise"));
    if($HaClusterCheckInt==0){$HaClusterCheckInt=10;}
    if($HaClusterCheckInt<5){$HaClusterCheckInt=5;}
    if($HaClusterCheckFall==0){$HaClusterCheckFall=5;}
    if($HaClusterCheckRise==0){$HaClusterCheckRise=2;}
    $reload_t=null;
    if($HaClusterReloadEach<>999){
        $reload_t="{reload_service} $reload[$HaClusterReloadEach], ";
    }

    $restart_if_needed="";
    $HaClusterCheckRestart=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterCheckRestart"));

    if($HaClusterCheckRestart==1){
        $restart_if_needed=" {restart_if_needed}, ";
    }
    $tpl->table_form_field_js("Loadjs('$page?section-health-js=yes')","AsSquidAdministrator");
    $tpl->table_form_field_text("{health_checks_small}","$reload_t$restart_if_needed{check_interval} $HaClusterCheckInt {seconds}, {fall} $HaClusterCheckFall {attempts}, {rise} $HaClusterCheckRise {attempts}",ico_watchdog);


    $tpl->table_form_field_js("Loadjs('$page?section-timeout-js=yes')","AsSquidAdministrator");
    $tpl->table_form_field_text("{timeouts}","{connect_timeout} $HaClusterTimeOutConnect {seconds}, {server_timeout} $HaClusterSeverTimeout {seconds}, {client_timeout} $HaClusterClientTimeout {seconds}",ico_timeout);

    return $tpl;
}
function parameters_ClusterService($tpl){
    $page=CurrentPageName();
    $ClusterServicePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterServicePort"));
    if($ClusterServicePort==0){$ClusterServicePort="58787";}
    $ClusterServiceInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterServiceInterface");

    if($ClusterServiceInterface==null){
        $ClusterServiceInterface="{all}";
    }

    $ClusterServiceCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterServiceCertificate");


    $tpl->table_form_field_js("Loadjs('fw.system.cluster.master.php?interface-js=yes')");
    if (strlen($ClusterServiceCertificate) > 3) {
        $tpl->table_form_field_text("{listen} {agents}", "<span style='text-transform:none'>https://$ClusterServiceInterface:$ClusterServicePort</span>", ico_interface);

        $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM sslcertificates WHERE CommonName='$ClusterServiceCertificate'");
        if(!isset($ligne['ID'])){$ligne['ID']=0;}
        $CertID=intval($ligne['ID']);

        if($CertID==0){
            $CreateCertifJs=CreateCertifJs();
            $tpl->table_form_field_js($CreateCertifJs);
            $tpl->table_form_field_button("{create_certificate}","{create_certificate}",ico_certificate);
        }
        $ligne=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM subcertificates WHERE certid='$CertID'");


        $tpl->table_form_field_js("Loadjs('fw.certificates-center.php?certificate-js=$ClusterServiceCertificate&ID=$CertID')");
        $tpl->table_form_field_text("{server_certificate}", "$ClusterServiceCertificate", ico_ssl);
        $Count=$ligne['tcount'];
        $tpl->table_form_field_js("Loadjs('fw.certificates-center.php?subcertificates-js=$CertID&OnlyClient=yes&NoPass=yes')");
        if($Count==0) {
            $tpl->table_form_field_bool("{client_certificates}", "$Count", ico_ssl);
        }else{
            $tpl->table_form_field_text("{client_certificates}", "$Count", ico_ssl);
        }



        $resetjs=$tpl->framework_buildjs("/hacluster/server/resetcert",
            "reset-master-certificate.progress","reset-master-certificate.log",
            "progress-hacluster-restart",
            "if(document.getElementById('hacluster-parameters'){LoadAjaxSilent('hacluster-parameters','$page?parameters-table=yes');}"
        );
        $tpl->table_form_field_js($resetjs);
        $tpl->table_form_field_button("{reset} {certificate}","{reset}",ico_certificate);


    } else {
        $tpl->table_form_field_js("Loadjs('$page?interface-js=yes')");
        $tpl->table_form_field_text("{listen}", "<span style='text-transform:none'>{no_certificate}</span>", ico_interface);
    }

    return $tpl;
}
function CreateCertifJs():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->framework_buildjs("/cluster/server/create/certificate",
        "artica.cluster.progress", "artica.cluster.log",
        "progress-hacluster-restart",
        "if(document.getElementById('hacluster-parameters'){LoadAjaxSilent('hacluster-parameters','$page?parameters-table=yes');}"
    );
}
function parameters_table():bool{

    $tpl=new template_admin();
    $page=CurrentPageName();
    $HaClusterInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterInterface"));
    $HaClusterPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterPort"));
    $HaClusterBalance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterBalance");
    $HaClusterProto=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProto");
    if($HaClusterPort==0){$HaClusterPort=3128;}
    if($HaClusterProto==null){$HaClusterProto="tcp";}

    $HaClusterDeployArticaUpdates=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterDeployArticaUpdates"));


    $HaClusterAsAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAsAD"));
    $HaClusterWorkers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterWorkers"));
    $HaClusterMaxConn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterMaxConn"));
    $HaClusterTHreads=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTHreads"));

    $HaClusterTimeOutConnect=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTimeOutConnect"));
    $HaClusterSeverTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterSeverTimeout"));
    $HaClusterClientTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClientTimeout"));

    $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));

    if(!isset($HaClusterGBConfig["HaClusterDisableProxyProtocol"])){
        $HaClusterGBConfig["HaClusterDisableProxyProtocol"]=0;
    }
    if(!isset($HaClusterGBConfig["HaClusterRemoveRealtimeLogs"])){
        $HaClusterGBConfig["HaClusterRemoveRealtimeLogs"]=0;
    }
    if(!isset($HaClusterGBConfig["HaClusterDecryptSSL"])){
        $HaClusterGBConfig["HaClusterDecryptSSL"]=0;
    }
    if(!isset($HaClusterGBConfig["HaClusterUseLBAsDNS"])){
        $HaClusterGBConfig["HaClusterUseLBAsDNS"]=0;
    }


    $HaClusterDisableProxyProtocol=intval($HaClusterGBConfig["HaClusterDisableProxyProtocol"]);
    $HaClusterRemoveRealtimeLogs=intval($HaClusterGBConfig["HaClusterRemoveRealtimeLogs"]);
    $HaClusterUseHaClient=intval($HaClusterGBConfig["HaClusterUseHaClient"]);
    $HaClusterClientMaxLoad=intval($HaClusterGBConfig["HaClusterClientMaxLoad"]);
    $HaClusterClientMaxLoadPeriod=intval($HaClusterGBConfig["HaClusterClientMaxLoadPeriod"]);
    $HaClusterClientMoniCPU=intval($HaClusterGBConfig["HaClusterClientMoniCPU"]);

    if($HaClusterClientMaxLoad==0){$HaClusterClientMaxLoad=3;}
    if($HaClusterDisableProxyProtocol==0){
        $HaClusterEnableProxyProtocol=1;
    }else{
        $HaClusterEnableProxyProtocol=0;
    }

    if($HaClusterTimeOutConnect==0){$HaClusterTimeOutConnect=10;}
    if($HaClusterSeverTimeout==0){$HaClusterSeverTimeout=30;}
    if($HaClusterClientTimeout==0){$HaClusterClientTimeout=300;}

    if($HaClusterMaxConn<2000){$HaClusterMaxConn=2000;}

    if($HaClusterWorkers==0){$HaClusterWorkers=1;}
    if($HaClusterTHreads==0){$HaClusterTHreads=1;}
    $HaProto["tcp"]="TCP/IP";
    $HaProto["http"]="HTTP";

    $algo["source"]="{strict-hashed-ip}";
    $algo["roundrobin"]="{round-robin}";
    $algo["leastconn"]="{leastconn}";
    if($HaClusterInterface==null){$HaClusterInterface="{all}";}
    if($HaClusterBalance==null){$HaClusterBalance="leastconn";}



    $tpl->table_form_section("{APP_PARENTLB}: {service_options}");
    $tpl->table_form_field_js("Loadjs('$page?section-listen-js=yes')","AsSquidAdministrator");
    $tpl->table_form_field_text("{listen} {browser}","$HaProto[$HaClusterProto],&nbsp;$HaClusterInterface:$HaClusterPort $algo[$HaClusterBalance]",ico_interface);

    $tpl=parameters_ClusterService($tpl);

    $tpl->table_form_field_js("Loadjs('$page?section-performance-js=yes')","AsSquidAdministrator");
    $tpl->table_form_field_text("{performance}","{maxconn}: $HaClusterMaxConn, {SquidCpuNumber}: $HaClusterWorkers, {ThreadsPerChild}: $HaClusterTHreads",ico_performance);

    $tpl=parameters_limit_access($tpl);
    $tpl=parameters_haclusterTimeouts($tpl);


    $tpl->table_form_field_js("Loadjs('$page?section-pproto-js=yes')","AsSquidAdministrator");
    $tpl->table_form_field_bool("{proxy_protocol}",$HaClusterEnableProxyProtocol,ico_networks);

    $HaClusterTransParentMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode"));

    $tpl->table_form_field_js("Loadjs('fw.hacluster.transparent.php?params-js=yes')","AsSquidAdministrator");
    if($HaClusterTransParentMode==0){
        $tpl->table_form_field_bool("{transparent_mode}",0,ico_sensor);
    }else{
        $HaClusterTransParentMasquerade=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMasquerade"));
        $HaClusterTransParentCertif=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentCertif"));
        $HaClusterTransParentDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentDebug"));
        $tr=array();
        if($HaClusterTransParentMasquerade==1){
            $tr[]="{masquerading}";
        }
        if(strlen($HaClusterTransParentCertif)>1){
            $tr[]="{certificate} $HaClusterTransParentCertif";
        }
        if($HaClusterTransParentDebug==1){
            $tr[]="<span class='text-danger'>{debug}</span>";
        }
        if(count($tr)>0){
            $tpl->table_form_field_text("{transparent_mode}","<small>".@implode(", ",$tr)."</small>",ico_sensor);
        }
    }

    $tpl->table_form_field_js("Loadjs('$page?section-simuad-js=yes')","AsSquidAdministrator");
    if($HaClusterAsAD==0) {
        $tpl->table_form_field_bool("{simulate_the_active_directory}", $HaClusterAsAD, ico_microsoft);
    }else{
        $HaClusterAsADIface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAsADIface"));
        if(strlen($HaClusterAsADIface)<2){
            $HaClusterAsADIface="{all}";
        }
        $tpl->table_form_field_text("{simulate_the_active_directory}","{active2} {interface} $HaClusterAsADIface",ico_microsoft);
    }

    $tpl->table_form_section("{CentralizedBackendsSettings}");

    $HaClusterUseAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterUseAddr");
    $tpl->table_form_field_js("Loadjs('$page?section-ipaddr-js=yes')","AsSquidAdministrator");
    if(strlen($HaClusterUseAddr)<5){
        $tpl->table_form_field_text("{lbIP}","{missing}",ico_interface,true);
    }else{
        $tpl->table_form_field_text("{lbIP}",$HaClusterUseAddr,ico_interface);
    }

    $tpl->table_form_field_js("Loadjs('$page?section-haclient-js=yes')","AsSquidAdministrator");
    if($HaClusterUseHaClient==0){
        $tpl->table_form_field_bool("{APP_HACLUSTER_CLIENT}",0,ico_watchdog);

    }else{
        if($HaClusterClientMoniCPU==1){
            $ha[]="{monitor_cpu_usage}";
        }

        $periods[0]="{realtime}";
        $periods[5]="5 {minutes}";
        $periods[15]="15 {minutes}";

        $ha[]="{Max_Load} $HaClusterClientMaxLoad";
        $ha[]="{period} $periods[$HaClusterClientMaxLoadPeriod]";
        $tpl->table_form_field_text("{APP_HACLUSTER_CLIENT}",@implode(", ",$ha),ico_watchdog);
    }


    $tpl->table_form_field_js("Loadjs('$page?section-ssl-js=yes')","AsSquidAdministrator");
    if($HaClusterGBConfig["HaClusterDecryptSSL"]==0){
        $tpl->table_form_field_bool("{activate_ssl_on_http_port}",0,ico_ssl);
    }else{
        if(intval($HaClusterGBConfig["sslcrtd_program_dbsize"])==0){$HaClusterGBConfig["sslcrtd_program_dbsize"]=8;}
        $tpl->table_form_field_text("{activate_ssl_on_http_port}","{proxy_certificate}: {$HaClusterGBConfig["HaClusterCertif"]}, {sslcrtd_program_dbsize} {$HaClusterGBConfig["sslcrtd_program_dbsize"]} MB",ico_interface);
    }
    $tpl=parameters_categories_service($tpl);
    $tpl=parameters_dns($tpl);
    $tpl=parameters_error_page($tpl);
    $tpl->table_form_section("{access_logs}/{log_retention}");



    $tpl->table_form_field_js("Loadjs('fw.proxy.rotate.php?main-logsink-js=yes')");
    $LogSinkClient = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClient"));
    if ($LogSinkClient == 0) {
        $tpl->table_form_field_info("{logs_sink}", "{inactive2}", ico_forward);

    } else {
        $proto = "udp";
        $LogSinkClientPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClientPort"));
        $LogSinkClientServer = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinClientServer"));
        $LogSinkClientTCP = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClientTCP"));
        if ($LogSinkClientTCP == 1) {
            $proto = "tcp";
        }
        $tpl->table_form_field_info("{logs_sink}", "$proto://$LogSinkClientServer:$LogSinkClientPort", ico_forward);
    }




$tpl->table_form_field_js("Loadjs('$page?section-logs-js=yes')","AsSquidAdministrator");
    $tpl->table_form_field_bool("{disable_realtime_backends}",$HaClusterRemoveRealtimeLogs,ico_eye);
    $tpl=parameters_logs($tpl);

    echo $tpl->table_form_compile();
    return true;
}
function parameters_logs($tpl){

    $page=CurrentPageName();


    $HaClusterRemoveLogsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterRemoveLogsEnabled"));
    $HaClusterRemoveLogsSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterRemoveLogsSize"));
    $HaClusterRemoteSyslogEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterRemoteSyslogEnabled"));
    $HaClusterRemoteUseTCPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterRemoteUseTCPPort"));
    $HaClusterRemoteSyslogPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterRemoteSyslogPort"));
    $HaClusterRemoteSyslogAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterRemoteSyslogAddr"));
    if($HaClusterRemoteSyslogPort==0){$HaClusterRemoteSyslogPort=514;}
    if($HaClusterRemoveLogsSize==0){$HaClusterRemoveLogsSize=500;}

    $tpl->table_form_field_js("Loadjs('fw.hacluster.logs.php?js=yes')","AsSquidAdministrator");
    if ($HaClusterRemoveLogsEnabled==0){
        if($HaClusterRemoteSyslogEnabled==0){
            $tpl->table_form_field_text("{log_retention}","{use_default}",ico_eye);
            return $tpl;
        }
        if($HaClusterRemoteSyslogEnabled==1){
            $tpl->table_form_field_text("{log_retention}","{use_default}/$HaClusterRemoteSyslogAddr:$HaClusterRemoteSyslogPort",ico_eye);
            return $tpl;
        }
    }
    if ($HaClusterRemoveLogsEnabled==1){
        if($HaClusterRemoteSyslogEnabled==0){
            $tpl->table_form_field_text("{log_retention}","{remove_local_access_events} ({$HaClusterRemoveLogsSize}MB)",ico_eye);
            return $tpl;
        }

        if($HaClusterRemoteSyslogEnabled==1){
            $tpl->table_form_field_text("{log_retention}","{remove_local_access_events} ({$HaClusterRemoveLogsSize}MB)/$HaClusterRemoteSyslogAddr:$HaClusterRemoteSyslogPort",ico_eye);
            return $tpl;
        }

    }
    return $tpl;
}
function parameters_categories_service($tpl){
    $page=CurrentPageName();
    $tpl->table_form_field_js("Loadjs('$page?remote-categories-service=yes')","AsSquidAdministrator");

    $HaClusterUseRemoteCategoriesService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterUseRemoteCategoriesService"));

    if($HaClusterUseRemoteCategoriesService==0){
        $tpl->table_form_field_bool("{use_remote_categories_services}",0,ico_database);
        return $tpl;
    }


    $RemoteCategoriesServiceSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServiceSSL"));

    $RemoteCategoriesServicePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicePort"));

    $RemoteCategoriesServiceAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServiceAddr");
    $proto="http";
    if($RemoteCategoriesServiceSSL==1){
        $proto="https";
    }
    $tpl->table_form_field_text("{use_remote_categories_services}","$proto://$RemoteCategoriesServiceAddr:$RemoteCategoriesServicePort",ico_database);
    return $tpl;
}
function parameters_limit_access($tpl){
    $page=CurrentPageName();
    $tpl->table_form_field_js("Loadjs('$page?section-limit-access-js=yes')","AsSquidAdministrator");

    $HaClusterLimitAccesses=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterLimitAccesses"));
    if(!$HaClusterLimitAccesses){
        $ACLREST[] = "192.168.0.0/16";
        $ACLREST[] = "10.0.0.0/8";
        $ACLREST[] = "172.16.0.0/12";

        $H=json_encode($ACLREST);
        $HaClusterLimitAccesses=json_decode($H);
    }
    if(count($HaClusterLimitAccesses)==0){
        $tpl->table_form_field_bool("{limit_access}",0,ico_firewall);
        return $tpl;
    }
    $tpl->table_form_field_text("{limit_access}",count($HaClusterLimitAccesses)." {items}",ico_firewall);
    return $tpl;
}
function parameters_error_page($tpl){
    $page=CurrentPageName();
    $tpl->table_form_field_js("Loadjs('$page?section-errorpage-js=yes')","AsSquidAdministrator");
    $HaClusterUseCentralErrorPage=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterUseCentralErrorPage"));

    if($HaClusterUseCentralErrorPage==0){
        $tpl->table_form_field_bool("{error_page_service}",0,ico_web_error_page);
        return $tpl;
    }
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
    $pp=array();
    $proto="";
    $cert="";
    if($HaClusterErrorPageHTTPPort>5){
        $proto="http";
        $pp[]=$HaClusterErrorPageHTTPPort;
    }
    if($HaClusterErrorPageUseSSL==1){
        if(preg_match("#^SUB:([0-9]+)#",$HaClusterErrorPageCert,$re)){
            $qSSL=new lib_sqlite("/home/artica/SQLITE/certificates.db");
            $ligneSSL=$qSSL->mysqli_fetch_array("SELECT commonName FROM subcertificates WHERE ID=$re[1]");
            $HaClusterErrorPageCert=$ligneSSL["commonName"];
        }

        $proto = "https";
        $pp=array();
        $pp[]=$HaClusterErrorPageHTTPsPort;
        $cert=" <small>($HaClusterErrorPageCert)</small>";
    }
    $port=@implode("|",$pp);
    $tpl->table_form_field_text("{error_page_service}","<span style='text-transform:none'>$proto://$HaClusterErrorPageServiceHostname:$port$cert</span>",ico_web_error_page);
    return $tpl;

}
function parameters_dns($tpl){
    $page=CurrentPageName();
    $tpl->table_form_field_js("Loadjs('$page?section-dns-js=yes')","AsSquidAdministrator");
    $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
    $HaClusterUseLocalDNSCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterUseLocalDNSCache"));
    $HaClusterServeDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterServeDNS"));
    $HaClusterProxyUseOwnDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProxyUseOwnDNS"));
    if($HaClusterServeDNS==1){
        $HaClusterGBConfig["HaClusterUseLBAsDNS"]=0;
    }
    $HaClusterProxyUseUnbound=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProxyUseUnbound"));

    if($HaClusterGBConfig["HaClusterUseLBAsDNS"]==1){
        $tpl->table_form_field_text("{dns_servers}","{use_load_balancer_as_dns}",ico_database);
        return $tpl;
    }


        $ff=array();
        $fa=array();

        if($HaClusterServeDNS==1){
            $ff[]="{hacluster_lb_dns}";
        }

    if($HaClusterProxyUseOwnDNS==0){ $HaClusterProxyUseUnbound=0; }

        if($HaClusterProxyUseUnbound==1){
            $ff[]="<small>{use_local_dns_service}</small>";
        }

        if($HaClusterUseLocalDNSCache==1){
            $ff[]="<small>{local_dns_service}</small>";
        }

        if(isset($HaClusterGBConfig["DNS1"])){
            if($HaClusterGBConfig["DNS1"]<>null) {
                $ff[] = $HaClusterGBConfig["DNS1"];
            }
        }
        if(isset($HaClusterGBConfig["DNS1"])){
            if($HaClusterGBConfig["DNS2"]<>null) {
                $ff[] = $HaClusterGBConfig["DNS2"];
            }
        }

        $HaClusterProxyUseOwnDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProxyUseOwnDNS"));
        $SquidNameServer1=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterSquidNameServer1");
        $SquidNameServer2=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterSquidNameServer2");
        $SquidNameServer3=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterSquidNameServer3");


         if($HaClusterProxyUseOwnDNS==1){
            if(strlen($SquidNameServer1)>0){
                $fa[]=$SquidNameServer1;
            }
             if(strlen($SquidNameServer2)>0){
                 $fa[]=$SquidNameServer2;
             }
             if(strlen($SquidNameServer3)>0){
                 $fa[]=$SquidNameServer3;
             }
         }
         if(count($fa)>0){
             $ff[]=$tpl->_ENGINE_parse_body("<small>({proxy_use_its_own_dns}: ".@implode(",",$fa)."</small>)");
         }

        if(count($ff)==0) {
            $tpl->table_form_field_text("{dns_servers}", "<small>{default} {or} {cluster_package}</small>", ico_database);
            return $tpl;
        }
        $tpl->table_form_field_text("{dns_servers}",@implode(", ",$ff),ico_database);
        return $tpl;
}
function section_performance_popup():bool{
    $tpl=new template_admin();

    $HaClusterWorkers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterWorkers"));
    $HaClusterMaxConn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterMaxConn"));
    $HaClusterTHreads=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTHreads"));
    $CPUza=array();$CPUz=array();
    if($HaClusterMaxConn<2000){$HaClusterMaxConn=2000;}

    $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    if($CPU_NUMBER==0){
        $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->getFrameWork("services.php?CPU-NUMBER=yes"));
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

    $periods[0]="{realtime}";
    $periods[5]="5 {minutes}";
    $periods[15]="15 {minutes}";
    if(strlen($HaClusterClientCheckUrl)<3){
        $HaClusterClientCheckUrl="http://www.msftncsi.com/ncsi.txt\nhttp://www.msftconnecttest.com/connecttest.txt\nhttp://captive.apple.com\nhttp://edge-http.microsoft.com/captiveportal/generate_204\nhttp://www.gstatic.com/generate_204";
    }

    if($HaClusterClientMaxLoad==0){$HaClusterClientMaxLoad=3;}
    $HaClusterClientEmergencyLoad=intval($HaClusterGBConfig["HaClusterClientCheckGlobal"]);
    if($HaClusterClientEmergencyLoad==0){$HaClusterClientEmergencyLoad=15;}
    if($HaClusterClientEmergencyLoad<3){$HaClusterClientEmergencyLoad=3;}


    $form[]=$tpl->field_checkbox("HaClusterUseHaClient","{use_haclusterclient}",$HaClusterUseHaClient,
        "HaClusterClientMoniCPU,HaClusterClientMaxLoad,HaClusterClientMaxLoadPeriod,HaClusterClientCheckUrl");

    $form[]=$tpl->field_textarea("HaClusterClientCheckUrl","{urlsTotest}",$HaClusterClientCheckUrl);
    $form[]=$tpl->field_checkbox("HaClusterClientCheckGlobal","{urlsTotestGlobal}",$HaClusterClientCheckGlobal);

    $form[]=$tpl->field_checkbox("HaClusterClientMoniCPU","{monitor_cpu_usage}",$HaClusterClientMoniCPU);

    $form[]=$tpl->field_numeric("HaClusterClientMaxLoad","{Max_Load}",$HaClusterClientMaxLoad);
    $form[]=$tpl->field_numeric("HaClusterClientEmergencyLoad","{Max_Load} ({emergency})",$HaClusterClientEmergencyLoad);

    $form[]=$tpl->field_array_buttons($periods,"HaClusterClientMaxLoadPeriod","{period}",$HaClusterClientMaxLoadPeriod);

    echo $tpl->form_outside(null,$form,null,"{apply}",form_paramsjs(),"AsSquidAdministrator",true);
    return true;
}
function section_dns_popup():bool{
    $tpl = new template_admin();
    $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));

    $form[]=$tpl->field_checkbox("HaClusterUseLBAsDNS","{use_load_balancer_as_dns}",intval($HaClusterGBConfig["HaClusterUseLBAsDNS"]));
    $HaClusterUseLocalDNSCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterUseLocalDNSCache"));
    $HaClusterProxyUseUnbound=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProxyUseUnbound"));
    $HaClusterProxyUseDNSCacheDenyPTR=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProxyUseDNSCacheDenyPTR"));

    $HaClusterServeDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterServeDNS"));

    $form[]=$tpl->field_checkbox("HaClusterServeDNS","{hacluster_lb_dns}",$HaClusterServeDNS,false,"{hacluster_use_dns_service_explain}");

    $form[]=$tpl->field_checkbox("HaClusterUseLocalDNSCache","{local_dns_service}",$HaClusterUseLocalDNSCache,false,"{hacluster_local_dns_service_explain}");

    $HaClusterProxyUseOwnDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProxyUseOwnDNS"));
    $SquidNameServer1=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterSquidNameServer1");
    $SquidNameServer2=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterSquidNameServer2");
    $SquidNameServer3=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterSquidNameServer3");


    $form[]=$tpl->field_ipv4("DNS1", "{primary_dns} ", $HaClusterGBConfig["DNS1"]);
    $form[]=$tpl->field_ipv4("DNS2", "{secondary_dns} ", $HaClusterGBConfig["DNS2"]);
    $form[]=$tpl->field_text("DOMAINS1", "{InternalDomain} 1", $HaClusterGBConfig["DOMAINS1"]);
    $form[]=$tpl->field_text("DOMAINS2", "{InternalDomain} 2", $HaClusterGBConfig["DOMAINS2"]);
    $form[]=$tpl->field_section("{dns_used_by_the_proxy_service}");

    $form[]=$tpl->field_checkbox("HaClusterProxyUseOwnDNS","{proxy_use_its_own_dns}",$HaClusterProxyUseOwnDNS,"HaClusterSquidNameServer1,HaClusterSquidNameServer2,HaClusterSquidNameServer3,HaClusterProxyUseUnbound");
    $form[]=$tpl->field_checkbox("HaClusterProxyUseUnbound","{use_local_dns_service}",$HaClusterProxyUseUnbound,"HaClusterProxyUseDNSCacheDenyPTR");
    $form[]=$tpl->field_checkbox("HaClusterProxyUseDNSCacheDenyPTR","{deny} PTR",$HaClusterProxyUseDNSCacheDenyPTR);

    $form[]=$tpl->field_text("HaClusterSquidNameServer1","{primary_dns}",$SquidNameServer1);
    $form[]=$tpl->field_text("HaClusterSquidNameServer2","{secondary_dns}",$SquidNameServer2);
    $form[]=$tpl->field_text("HaClusterSquidNameServer3","{nameserver} 3",$SquidNameServer3);

    echo $tpl->form_outside(null,$form,null,"{apply}",form_paramsjs(),"AsSquidAdministrator",true);
    return true;
}
function section_ssl_popup():bool{
    $tpl = new template_admin();
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
    $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
    $HaClusterRemoveRealtimeLogs=intval($HaClusterGBConfig["HaClusterRemoveRealtimeLogs"]);

    $form[]=$tpl->field_checkbox("HaClusterRemoveRealtimeLogs","{disable_realtime_backends}",$HaClusterRemoveRealtimeLogs);
    echo $tpl->form_outside(null,$form,null,"{apply}",form_paramsjs(),"AsSquidAdministrator",true);
    return true;
}
function section_deploy_popup():bool{
    $tpl = new template_admin();
    $HaClusterDeployArticaUpdates=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterDeployArticaUpdates"));

    $form[]=$tpl->field_checkbox("HaClusterDeployArticaUpdates","{deploy_artica_updates})",$HaClusterDeployArticaUpdates);

    echo $tpl->form_outside(null,$form,"{deploy_artica_updates_explain}","{apply}",form_js(),"AsSquidAdministrator",true);
    return true;
}
function section_health_popup():bool{
    $tpl=new template_admin();
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

    $form[]=$tpl->field_section("{APP_PARENTLB}");

    $form[]=$tpl->field_array_hash($reload,"HaClusterReloadEach","nonull:{reload_service}",$HaClusterReloadEach);
    $form[]=$tpl->field_text("HaClusterCheckURI","{check_url}",$HaClusterCheckURI);
    $form[]=$tpl->field_checkbox("HaClusterCheckRestart","{restart_if_needed}",$HaClusterCheckRestart);

    $form[]=$tpl->field_section("{backends}");
    $form[]=$tpl->field_numeric("HaClusterCheckInt","{check_interval} ({seconds})",$HaClusterCheckInt);
    $form[]=$tpl->field_numeric("HaClusterCheckFall","{fall} ({attempts})",$HaClusterCheckFall,"{fall_explain}");
    $form[]=$tpl->field_numeric("HaClusterCheckRise","{rise} ({attempts})",$HaClusterCheckRise,"{rise_explain}");


    echo $tpl->form_outside(null,$form,null,"{apply}",form_js(),"AsSquidAdministrator",true);
    return true;

}
function section_timeout_popup():bool{
    $tpl=new template_admin();
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
    echo $tpl->form_outside(null,$form,"{nat_explain_addr}","{apply}",form_js(),"AsSquidAdministrator",true);
    return true;
}

function section_listen_popup():bool{
    $tpl=new template_admin();
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
function form_paramsjs():string{
    $tpl=new template_admin();
    $page=CurrentPageName();


    $prgress=$tpl->framework_buildjs("/hacluster/server/notify/all",
        "hacluster.connect.progress",
        "hacluster.connect.txt",
        "progress-hacluster-restart",
        "LoadAjaxSilent('hacluster-table-status-left','$page?hacluster-table-status-left=yes');"
    );

    $f[]="LoadAjaxSilent('hacluster-parameters','$page?parameters-table=yes');";
    $f[]="dialogInstance2.close()";
    $f[]=$prgress;
    return @implode(";",$f);

}
function SaveParams():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));

    if(isset($_POST["HaClusterEnableProxyProtocol"])) {
        if ($_POST["HaClusterEnableProxyProtocol"] == 1) {
            $_POST["HaClusterDisableProxyProtocol"] = 0;
        } else {
            $_POST["HaClusterDisableProxyProtocol"] = 1;
        }
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
    if(isset($_POST["HaClusterProxyUseUnbound"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterProxyUseUnbound",$_POST["HaClusterProxyUseUnbound"]);
        unset($_POST["HaClusterProxyUseUnbound"]);
    }
    if(isset($_POST["HaClusterSquidNameServer1"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterSquidNameServer1",$_POST["HaClusterSquidNameServer1"]);
        unset($_POST["HaClusterSquidNameServer1"]);
    }
    if(isset($_POST["HaClusterSquidNameServer2"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterSquidNameServer2",$_POST["HaClusterSquidNameServer2"]);
        unset($_POST["HaClusterSquidNameServer2"]);
    }
    if(isset($_POST["HaClusterSquidNameServer3"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterSquidNameServer3",$_POST["HaClusterSquidNameServer3"]);
        unset($_POST["HaClusterSquidNameServer3"]);
    }

    if(isset($_POST["HaClusterServeDNS"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterServeDNS",$_POST["HaClusterServeDNS"]);
        if($_POST["HaClusterServeDNS"]==1){
            $HaClusterGBConfig["HaClusterUseLBAsDNS"]=0;
        }
        unset($_POST["HaClusterServeDNS"]);
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
    foreach ($_POST as $key=>$val) {
        $HaClusterGBConfig[$key]=$val;
        admin_tracks("Update HaCluster backends option $key with $val");
    }
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterGBConfig",serialize($HaClusterGBConfig));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/restart");

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
function section_errorpage_popup():bool{

    $tpl=new template_admin();
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
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
    return admin_tracks_post("Saving Section Error Page");
}

function remote_categories_service_popup():bool{
    $tpl=new template_admin();
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
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/categories/remote/service/check");
    return admin_tracks("Saving the use a remote categories service");

}