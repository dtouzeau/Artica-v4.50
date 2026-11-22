<?php
$GLOBALS["LAYER_STATUS"]["UNK"]="unknown";
$GLOBALS["LAYER_STATUS"]["INI"]="initializing";
$GLOBALS["LAYER_STATUS"]["SOCKERR"]="socket error";
$GLOBALS["LAYER_STATUS"]["L4OK"]="check passed on layer 4, no upper layers testing enabled";
$GLOBALS["LAYER_STATUS"]["L4TMOUT"]="{timeout}";
$GLOBALS["LAYER_STATUS"]["L4CON"]="{connection_error}";
$GLOBALS["LAYER_STATUS"]["L6OK"]="check passed on layer 6";
$GLOBALS["LAYER_STATUS"]["L6TOUT"]="{timeout}";
$GLOBALS["LAYER_STATUS"]["L6RSP"]="layer 6 invalid response - protocol error";
$GLOBALS["LAYER_STATUS"]["L7OK"]="check passed on layer 7";
$GLOBALS["LAYER_STATUS"]["L7OKC"]="check conditionally passed on layer 7, for example 404 with disable-on-404";
$GLOBALS["LAYER_STATUS"]["L7TOUT"]="{timeout}";
$GLOBALS["LAYER_STATUS"]["L7RSP"]="{protocol_error}";
$GLOBALS["LAYER_STATUS"]["L7STS"]="{protocol_error}";
$GLOBALS["PEITYCONF"]="{ width:200,fill: [\"#eeeeee\"],stroke:\"#18a689\",strokeWidth: 2 }";
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.hacluster-backends.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.haproxy.inc");
include_once(dirname(__FILE__)."/ressources/class.kerberos.spn.inc");
include_once(dirname(__FILE__)."/ressources/class.hacluster.clock.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Pentest 2026-11-04 : start-activate/stop-js/start-js/period/td-row-implode etaient recopies sans encodage dans la page (XSS).
// Identifiants de backend (entier ou T-<n>), periode de graphe, liste d'entiers.
foreach(array("start-activate","stop-js","start-js","period") as $k){if(isset($_GET[$k])){$_GET[$k]=preg_replace('/[^A-Za-z0-9_-]/','',(string)$_GET[$k]);}}
if(isset($_GET["td-row-implode"])){$_GET["td-row-implode"]=preg_replace('/[^0-9,]/','',(string)$_GET["td-row-implode"]);}
if(isset($_GET["td-row-implode"])){td_rows();exit;}
if(isset($_POST["backend-agent"])){backend_agent_save();exit;}
if(isset($_GET["metrics"])){metrics();exit;}
if(isset($_GET["start-js"])){start_js();exit;}
if(isset($_GET["start-ready"])){start_ready();exit;}
if(isset($_GET["weight-js"])){weight_js();exit;}
if(isset($_GET["weight-popup"])){weight_popup();exit;}
if(isset($_POST["weightid"])){weight_save();exit;}

if(isset($_GET["backend-register"])){backend_register();exit;}
if(isset($_GET["start-activate"])){start_activate();exit;}
if(isset($_GET["btn-action"])){td_btnActionBack();exit;}
if(isset($_GET["td-row"])){td_row();exit;}
if(isset($_GET["allrows"])){RefreshTableRows();exit;}
if(isset($_GET["backend-status"])){backup_status_start();exit;}
if(isset($_GET["backend-status-popup"])){backup_status();exit;}
if(isset($_GET["clusteragent"])){clusteragent_enable();exit;}
if(isset($_GET["dumpmetrics"])){peityLoads(intval($_GET["dumpmetrics"]));exit;}
if(isset($_GET["stop-js"])){stop_js();exit;}
if(isset($_POST["ID"])){backends_save();exit;}
if(isset($_POST["scopesid"])){backend_scope_save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["backend-js"])){backend_js();exit;}
if(isset($_GET["backend-agent"])){backend_agent();exit;}
if(isset($_GET["refresh-table"])){refresh_table();exit;}
if(isset($_GET["backend-tab"])){backend_tab();exit;}
if(isset($_GET["backend-popup"])){backend_popup();exit;}
if(isset($_GET["backend-reload-all"])){backends_reload_all();exit;}
if(isset($_GET["backend-enable-js"])){backends_enable();exit;}
if(isset($_GET["backend-delete-js"])){backend_delete_js();exit;}
if(isset($_GET["backend-reboot-js"])){backend_reboot_js();exit;}
if(isset($_GET["backend-ademergency-js"])){backend_ademergency_js();exit;}
if(isset($_GET["backend-kerberos-js"])){backend_kerberos_js();exit;}
if(isset($_GET["backend-kerberos-popup"])){backend_kerberos_popup();exit;}
if(isset($_GET["backend-clock-js"])){backend_clock_js();exit;}
if(isset($_GET["backend-clock-popup"])){backend_clock_popup();exit;}
if(isset($_GET["backend-clock-content"])){backend_clock_content(intval($_GET["backend-clock-content"]));exit;}
if(isset($_GET["backend-clock-resync"])){backend_clock_resync();exit;}
if(isset($_POST["backend-ademergency"])){backend_ademergency();exit;}
if(isset($_GET["backend-iperf3-js"])){backend_iperf3_js();exit;}
if(isset($_GET["backend-cluster-force-js"])){backend_cluster_force_js();exit;}

if(isset($_GET["backend-reconfigure-js"])){backend_reconfigure_js();exit;}
if(isset($_GET["backend-dnsreconfigure-js"])){backend_reconfiguredns_js();exit;}
if(isset($_GET["checkCurStatus"])){checkCurStatus();exit;}

if(isset($_POST["backend-reconfiguredns"])){backend_reconfiguredns();exit;}
if(isset($_POST["backend-reconfigure"])){backend_reconfigure();exit;}
if(isset($_POST["backend-reboot"])){backend_reboot();exit;}
if(isset($_POST["backend-delete"])){backend_delete();exit;}
if(isset($_POST["backend-speed"])){backend_speed();exit;}
if(isset($_POST["backend-cluster"])){backend_cluster_perform();exit;}
if(isset($_GET["backend-scope"])){backend_scope();exit;}
if(isset($_GET["backend-scope-js"])){backend_scope_js();exit;}
if(isset($_GET["backend-scope-popup"])){backend_scope_popup();exit;}
if(isset($_GET["backend-scope-table"])){backend_scope_table();exit;}
if(isset($_GET["page"])){page();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["graphs"])){graphs_js();exit;}
if(isset($_GET["graph-tabs"])){graphs_tabs();exit;}
if(isset($_GET["graph-popup"])){graphs_popup();exit;}
if(isset($_GET["metrics-popup"])){metrics_popup();exit;}
if(isset($_GET["notify-backend"])){notify_backend();exit;}
if(isset($_POST["notify-backend"])){notify_backend_log();exit;}

if(isset($_GET["hacluster-client-error-js"])){hacluster_client_error_js();exit;}


start_tabs();

function hacluster_client_error_js():bool{
    $tpl=new template_admin();
    echo $tpl->js_error(base64_decode($_GET["hacluster-client-error-js"]));
    return true;
}
function start_activate():bool{
    $ID=$_GET["start-activate"];
    $page=CurrentPageName();
    $sock=new sockets();
    $sock->REST_API("/hacluster/server/checkprod/node/$ID");
    header("content-type: application/x-javascript");
    echo "Loadjs('$page?td-row=$ID');";
    return true;
}

function clusteragent_enable():bool{
    $tpl=new template_admin();
    $HaClusterGBConfig = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
    $HaClusterUseHaClient=intval($HaClusterGBConfig["HaClusterUseHaClient"]);
    if($HaClusterUseHaClient==1){
        $tpl->js_error("Defined globally");
        return true;
    }
    $ID=intval($_GET["clusteragent"]);
    $ligne=hacluster_db_get_backend($ID);
    $clusteragent=0;
    if(isset($ligne["clusteragent"])) {
        if (!is_null($ligne["clusteragent"])) {
            $clusteragent = intval($ligne["clusteragent"]);
        }
    }
    if($clusteragent==0){
        $clusteragent=1;
    }else{
        $clusteragent=0;
    }

    if(!hacluster_db_update_backend($ID, array("clusteragent"=>$clusteragent))){
        $tpl->js_error("clusteragent update failed");
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/flush");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/reconfigure");
    $page=currentPageName();
    echo "Loadjs('$page?btn-action=$ID')";
    return true;

}



function notify_backend(){
    $ID=intval($_GET["notify-backend"]);
    $ligne=hacluster_db_get_backend($ID);
    $title=$ligne["backendname"] ?? "";

    $tpl=new template_admin();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/node/$ID"));
    if (json_last_error()> JSON_ERROR_NONE) {
        $tpl->js_error_stop(json_last_error_msg());
        return true;
    }

    if(!$json->Status){
        $tpl->js_error_stop($json->Error);
        return true;
    }
    $tpl->js_ok("{success}");
    return admin_tracks("HaCluster: Notify $title with new parameters");
   // $tpl->js_confirm_execute("{reconfigure_this_node}: $title ?","notify-backend",$ID,$js);

}
function notify_backend_log(){
    $ID=intval($_GET["notify-backend"]);
    $ligne=hacluster_db_get_backend($ID);
    $title=$ligne["backendname"] ?? "";

}

function backend_agent():bool{
    $ID=intval($_GET["backend-agent"]);
    $ligne=hacluster_db_get_backend($ID);
    $tpl        = new template_admin();

    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }

    VERBOSE("agentcfg={$ligne["agentcfg"]}",__LINE__);

    $agentcfg=unserializeb64($ligne["agentcfg"]);
    $page       = CurrentPageName();
    $tpl        = new template_admin();

    $HaClusterClientMaxLoad=intval($agentcfg["HaClusterClientMaxLoad"]);
    $HaClusterClientMaxLoadPeriod=intval($agentcfg["HaClusterClientMaxLoadPeriod"]);
    $HaClusterClientMoniCPU=intval($agentcfg["HaClusterClientMoniCPU"]);
    $HaClusterClientMaxMember=intval($agentcfg["HaClusterClientMaxMember"]);
    $HaClusterClientEmergencyLoad=intval($agentcfg["HaClusterClientEmergencyLoad"]);
    if($HaClusterClientMaxMember==0){
        $HaClusterClientMaxMember=500;
    }
    if($HaClusterClientEmergencyLoad==0){
        $HaClusterClientEmergencyLoad=15;
    }
    if($HaClusterClientEmergencyLoad<3){
        $HaClusterClientEmergencyLoad=3;
    }
    $periods[0]="{realtime}";
    $periods[5]="5 {minutes}";
    $periods[15]="15 {minutes}";

    if($HaClusterClientMaxLoad==0){$HaClusterClientMaxLoad=3;}

    $form[]=$tpl->field_hidden("backend-agent",$ID);
    $form[]=$tpl->field_checkbox("clusteragent","{use_haclusterclient}",$ligne["clusteragent"],
        "HaClusterClientMoniCPU,HaClusterClientMaxLoad,HaClusterClientMaxLoadPeriod,HaClusterClientMaxMember");
    $form[]=$tpl->field_checkbox("HaClusterClientMoniCPU","{monitor_cpu_usage}",$HaClusterClientMoniCPU);
    $form[]=$tpl->field_numeric("HaClusterClientMaxLoad","{Max_Load}",$HaClusterClientMaxLoad);
    $form[]=$tpl->field_numeric("HaClusterClientEmergencyLoad","{Max_Load} ({emergency})",$HaClusterClientEmergencyLoad);
    $form[]=$tpl->field_numeric("HaClusterClientMaxMember","Max. {members}",$HaClusterClientMaxMember);
    $form[]=$tpl->field_array_buttons($periods,"HaClusterClientMaxLoadPeriod","{period}",$HaClusterClientMaxLoadPeriod);

    $js="LoadAjax('backend-list','$page?table=yes');";

    echo $tpl->form_outside(null,$form,null,"{apply}",$js,"AsSquidAdministrator",true);
    return true;
}
function backend_agent_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $data=base64_encode(serialize($_POST));
    $ID=intval($_POST["backend-agent"]);
    $clusteragent=intval($_POST["clusteragent"]);
    hacluster_db_update_backend($ID, array("clusteragent"=>$clusteragent, "agentcfg"=>$data));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/flush");
    $sock=new sockets();
    $sock->REST_API("/hacluster/server/reconfigure");
    $sock->REST_API("/hacluster/server/notify/haclient/$ID");
    return admin_tracks_post("Save HaCluster backend ID:$ID");

}

function start_tabs(){

    if(isset($_GET["main-page"])){page();exit;}
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    // Un vrai en-tete (titre, icone, #tiny-button) : sans lui, le TINY des
    // onglets n'avait nulle part ou ecrire, et les filtres de l'onglet Metrics
    // restaient dans le corps de la page.
    $html=$tpl->page_header("{hb2_title}","fas fa-server","{hb2_explain}",
        "$page?tabs=yes","hacluster-backends",false,false,"hacluster-start-tabs");
    echo $tpl->_ENGINE_parse_body($html);

}



function weight_js(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $ID=intval($_GET["weight-js"]);
    $ligne=hacluster_db_get_backend($ID);
    $title=$ligne["backendname"] ?? "";
    $tpl->js_dialog3("{weight}: $title","$page?weight-popup=$ID",890);
}

function graphs_js(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $ID=intval($_GET["graphs"]);
    $ligne=hacluster_db_get_backend($ID);
    $title=$ligne["backendname"] ?? "";
    $tpl->js_dialog3("{statistics}: $title","$page?graph-tabs=$ID",1200);

}
function graphs_tabs(){
    $ID=intval($_GET["graph-tabs"]);
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $array["{this_hour}"]="$page?graph-popup=$ID&period=hourly";
    $array["{today}"]="$page?graph-popup=$ID&period=day";
    $array["{yesterday}"]="$page?graph-popup=$ID&period=yesterday";
    $array["{this_week}"]="$page?graph-popup=$ID&period=week";
    $array["{this_month}"]="$page?graph-popup=$ID&period=month";
    $array["{this_year}"]="$page?graph-popup=$ID&period=year";
    echo $tpl->tabs_default($array);
}
function graphs_popup():bool{
    $ID=intval($_GET["graph-popup"]);
    $tpl        = new template_admin();
    $period =$_GET["period"];

    $imgs["allqueries"]="{queries}";
    $imgs["proxyusers"]="{members}";
    $imgs["system_memory"]="{memory} {OS}";
    $imgs["squidmem"]="{memory_usage} {APP_SQUID}";
    $imgs["squid_memory"]="{memory_usage}";
    $imgs["squidwatch-bandwidth"]="{bandwidth}";
    $imgs["squidwatch-latency"]="{latency}";
    $imgs["filedesc"]="{file_descriptors}";
    $imgs["load"]="{load}";
    $imgs["system_cpu"]="CPUs {OS}";
    


    $html=array();
    foreach ($imgs as $prefix=>$title){
        $title=$tpl->_ENGINE_parse_body($title);
        $pic="$prefix-$period.png";

        $data=metrics_get_png($ID,$pic);

        if(strlen($data)<20){
            VERBOSE("$pic not found in $prefix",__LINE__);
            continue;
        }
        $html[]="<H2>$title</H2>";
        $html[]="<img src='data:image/png;base64,$data' alt='$pic'>";
    }
    echo @implode("\n",$html);
    return true;
}
function tabs():bool{

    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $array["<i class='".ico_server."'></i>&nbsp;{backends}"]="$page?page=yes";
    $array["<i class='".ico_statistics."'></i>&nbsp;{metrics}"]="fw.hacluster.backends.metrics.php?main-view=yes&range=1h";
    echo $tpl->tabs_default($array);
    return true;
}

function backend_status_farm($ligne,$tpl){
    $page=currentPageName();
    $function="";
    if(isset($_GET["function"])) {
        $function = $_GET["function"];
    }
    $ID=intval($ligne["ID"]);
    $BackendState=to_opstate($ligne);
    if($BackendState==99){

        $reload=$tpl->framework_buildjs("/hacluster/server/reload",
            "hacluster.progress","hacluster.progress.log","backend-status-progress-$ID","LoadAjaxSilent('backend-status-$ID','$page?backend-status-popup=$ID&function=$function');",
            "LoadAjaxSilent('backend-status-$ID','$page?backend-status-popup=$ID&function=$function');");

        $tpl->table_form_field_text("{backend_status_in_the_pool}","{disconnected}",ico_unlink,true);
        $tpl->table_form_button("{reload_service}",$reload,"AsProxyMonitor",ico_refresh);
        return $tpl;
    }
    if($BackendState==0){
        $BackendStateAdm=to_opFront($ligne);
        if($BackendStateAdm==1) {
            $tpl->table_form_field_text("{backend_status_in_the_pool}", "{maintenance}", ico_clock_wait, true);
            return $tpl;
        }
    }
    if($BackendState==2){
        $tpl->table_form_field_text("{backend_status_in_the_pool}","{available}",ico_performance,false);
        return $tpl;
    }

    $tpl->table_form_field_text("{backend_status_in_the_pool}",$BackendState,ico_unlink,true);

    //
    //$BackendStateAdm=to_opFront($ligne);
    return $tpl;

}
function backup_status_start():bool{
    $function="";
    if(isset($_GET["function"])) {
        $function = $_GET["function"];
    }
    $page=currentPageName();
    $ID=intval($_GET["backend-status"]);
    echo "<div id='backend-status-progress-$ID'></div>";
    echo "<div id='backend-status-$ID'></div>";
    echo "<script>LoadAjaxSilent('backend-status-$ID','$page?backend-status-popup=$ID&function=$function');</script>";
    return true;
}
function MetricsArray($ID):array{
    $workdir="ressources/logs/hacluster/$ID";
    $workfile="$workdir/HaClusterMetrics.array";
    if(!is_file($workfile)){
        VERBOSE("$workfile does not exist",__LINE__);
        return array();
    }

    $serialized=$GLOBALS["CLASS_SOCKETS"]->unserializeb64(file_get_contents($workfile));
    if(!$serialized){
        return array();
    }
    if(!is_array($serialized)){
        return array();
    }
    return $serialized;
}
function backup_status_clusterP($ID,$tpl){
    $MyCluster=__LocalClusterInfo();

    if(intval($MyCluster["MasterID"])==0){
        $tpl->table_form_field_bool("{cluster_package} <small>No master</small>",0,ico_file_zip);
        return $tpl;
    }
    if($MyCluster["MasterID"]==$ID){
        $tpl->table_form_field_text("{cluster_package}","{master_server}",ico_file_zip);
        return $tpl;
    }

    $MetricsArray=MetricsArray($ID);

    if(!isset($MetricsArray["CLUSTER_SLAVE_INFO"])) {
        $tpl->table_form_field_bool("{cluster_package} <small>No metrics</small>",0,ico_file_zip);
        return $tpl;
    }
    $Locaver=$MyCluster["ClusterVersion"];
    $ClusterInfo = json_decode($MetricsArray["CLUSTER_SLAVE_INFO"], true);
    $PackageVersion=$ClusterInfo["PackageVersion"];
    if( version_is_lower($Locaver,$PackageVersion)) {
        $tpl->table_form_field_text("{cluster_package}",$PackageVersion,ico_file_zip,true);
        return $tpl;
    }
    $tpl->table_form_field_text("{cluster_package}",$PackageVersion,ico_file_zip);
    return $tpl;

}
function backup_status():bool{
    $function="";
    if(isset($_GET["function"])) {
        $function = $_GET["function"];
    }
    $ID=intval($_GET["backend-status-popup"]);
    $ligne=hacluster_db_get_backend($ID);
    $tpl        = new template_admin();
    $enabled=$ligne["enabled"] ?? 0;
    $status=$ligne["status"];
    $isDisconnected=$ligne["isDisconnected"];
    $status_textin=StatusIntToStr($status);
    $certificateid=intval($ligne["certificateid"]);
    $workdir="ressources/logs/hacluster/$ID";
    $TimeDiff="";
    $updated=intval(@file_get_contents("$workdir/uptated.int"));
    if($updated>0) {
        $TimeDiff = distanceOfTimeInWords($updated, time());
    }

    $ActiveDirectoryEmergency=intval($ligne["ActiveDirectoryEmergency"]);
    $HaClusterMetrics=array();

    if(!is_dir($workdir)) {
        $tpl->table_form_field_text("{metrics}", "{none}", ico_params,true);
    }else{
        $workfile="$workdir/HaClusterMetrics.array";
        if(!is_file($workfile)) {
            $tpl->table_form_field_text("{metrics}", "{unknown}", ico_params,true);
        }else{
            $HaClusterMetrics= $GLOBALS["CLASS_SOCKETS"]->unserializeb64(@file_get_contents($workfile));

        }
    }

    $ico_cpu=ico_cpu;
    $ico_load=ico_monitor;
    $status_textin="$status_textin&nbsp;&nbsp;<small>(<i class='$ico_cpu'></i>&nbsp;{$HaClusterMetrics["CPU_AVG"]}%&nbsp;&nbsp;<i class='$ico_load'></i> {$HaClusterMetrics["LOAD"]})</small>";

    if($ActiveDirectoryEmergency==1){
        // Le proxy du noeud laisse tout passer sans authentification : l'operateur
        // doit pouvoir l'en sortir d'ici, le LB relaie la demande au noeud.
        $page=CurrentPageName();
        $tpl->table_form_field_js("Loadjs('$page?backend-ademergency-js=$ID')");
        $tpl->table_form_field_text("{emergency}","{activedirectory_emergency_mode}||{leave_ad_emergency_explain}",ico_bug,true);
        $tpl->table_form_field_js("");
    }
    // Erreur Kerberos prouvee par le noeud (rapport report.json, champ KerberosSPN) :
    // sans elle, un SPN porte par un autre compte AD n'etait visible nulle part ici.
    $krb=kerberos_spn_from_node_report($ID);
    if(kerberos_spn_is_problem($krb)){
        $page=CurrentPageName();
        $krbtitles=kerberos_spn_titles($krb->state);
        list($krbspn,,)=kerberos_spn_failing($krb);
        $krbspn=kerberos_spn_h(preg_replace('#@.*$#','',$krbspn));
        $tpl->table_form_field_js("Loadjs('$page?backend-kerberos-js=$ID')");
        $tpl->table_form_field_text("Kerberos","$krbspn||{$krbtitles[0]}",ico_key,true);
        $tpl->table_form_field_js("");
    }
    $clk=hacluster_clock_node(intval($ID));
    if(is_object($clk) && !empty($clk->problem) && is_object($clk->clock ?? null)){
        $page=CurrentPageName();
        $tpl->table_form_field_js("Loadjs('$page?backend-clock-js=$ID')");
        $tpl->table_form_field_text("{clock_out_of_sync}",hacluster_clock_skew_text(intval($clk->clock->skew_ms))."||{clock_out_of_sync_short}",ico_clock,true);
        $tpl->table_form_field_js("");
    }
    $date="{never}";
    if(isset($HaClusterMetrics["TIME"])){
        if(intval($HaClusterMetrics["TIME"])>0) {
            $date = $tpl->time_to_date($HaClusterMetrics["TIME"], true) . "&nbsp;/&nbsp;";
        }
    }

    if($enabled==0){
        $tpl->table_form_field_bool("{enabled}",0,ico_check);
    }else{
        if($isDisconnected==1){
            $tpl->table_form_field_bool("{disonnect_from_farm}",1,ico_unlink);
        }else{
             $tpl->table_form_field_text("{status}",$status_textin,ico_timeout);
        }
        $tpl=backend_status_farm($ligne,$tpl);
    }
    $tpl->table_form_field_text("{last_com}","$date$TimeDiff",ico_clock);

    $tpl=backup_status_clusterP($ID,$tpl);


    $HaClusterNodesPings=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterNodesPings");
    if(strlen($HaClusterNodesPings)>5) {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterNodesPings"));
        if (json_last_error() == JSON_ERROR_NONE) {
            if(property_exists($json,"hosts")) {
                $Hosts=$json->hosts;
                if(property_exists($Hosts,$ID)) {
                    if(!$Hosts->{$ID}->available){
                        $xtime=$Hosts->{$ID}->ScanTime;
                        $distance=distanceOfTimeInWords($xtime,time());
                        $tpl->table_form_field_text($Hosts->{$ID}->ConnStr,"<small>".$Hosts->{$ID}->Error."  ($distance)</small>",ico_bug,true);
                    }
                }

            }
        }
    }
    $Names=array();
    if(strlen($ligne["backendname"])>2){
        $Names[]=$ligne["backendname"];
    }
    if(strlen($ligne["realname"])>1) {
        $Names[]=$ligne["realname"];

    }
    if(count($Names)>0){
        $tpl->table_form_field_text("{name}", @implode(" ,",$Names), ico_server);
    }

    if(!isset($HaClusterMetrics["IPERF3"])){$HaClusterMetrics["IPERF3"]="0";}

    if($certificateid==0){
        $tpl->table_form_field_text("{client_certificate}","{missing_certificate}",ico_certificate,true);
    }else{
        include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
        $cdb=new CertificatesDB();
        $sub=$cdb->subGetById(intval($certificateid));
        $commonName=is_object($sub)?(string)($sub->commonName ?? ""):"";
        $tpl->table_form_field_js("Loadjs('fw.certificates-center.php?show-server-certificate=$certificateid')");
        $tpl->table_form_field_text("{client_certificate}",$commonName,ico_certificate);
        $tpl->table_form_field_js("");
    }

    $IPERF3=intval($HaClusterMetrics["IPERF3"]);
    if($IPERF3==1){
        if(isset($HaClusterMetrics["IPERF3_VERSION"])) {
            if (strlen($HaClusterMetrics["IPERF3_VERSION"]) > 1) {
                $tpl->table_form_field_text("iPERF3", $HaClusterMetrics["IPERF3_VERSION"], ico_speed);
            }
        }

        list($r,$s)=iperf3Report($ID);
        if(strlen($r)>1) {
            $tpl->table_form_field_text("{bandwidth}","<span style='text-transform: none'>{upload} $s / {download} $r</span>",ico_speed);

        }
    }
    if(isset($HaClusterMetrics["HACLUSTERCLIENT_VERSION"])){
        $tpl->table_form_field_text("HaCluster Client", $HaClusterMetrics["HACLUSTERCLIENT_VERSION"], ico_infoi);
    }
    if(isset($HaClusterMetrics["WEBERRORPAGE_VERSION"])){
        $tpl->table_form_field_text("{WEB_ERROR_PAGE}", $HaClusterMetrics["WEBERRORPAGE_VERSION"], ico_infoi);
    }
    if(isset($HaClusterMetrics["ARTICAREST_VERSION"])){
        $tpl->table_form_field_text("{SQUID_AD_RESTFULL}", $HaClusterMetrics["ARTICAREST_VERSION"], ico_infoi);
    }

    $tpl->table_form_field_text("Artica {version}",$ligne["articaversion"],ico_infoi);
    $proxyversion=trim($ligne["proxyversion"]);
    if($proxyversion<>null){
        $tb=explode(".",$proxyversion);
        $MAJOR=intval($tb[0]);
        if($MAJOR>0) {
            $tpl->table_form_field_text("{APP_SQUID}","$proxyversion",ico_infoi);
        }
    }
    $page=CurrentPageName();
    $tpl->table_form_field_js("Loadjs('$page?backend-delete-js=$ID&function=$function')");
    $tpl->table_form_field_button("{remove}","{remove_this_backend}",ico_trash);

    echo $tpl->table_form_compile();
    return true;
}

function backend_tab():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $ID         = intval($_GET["backend-tab"]);
    $function="";
    if(isset($_GET["function"])) {
        $function = $_GET["function"];
    }
    if($ID==0){
        $array["{deploy}"]="$page?backend-popup=$ID&function=$function";
        $array["{register}"]="$page?backend-register=yes&function=$function";
        echo $tpl->tabs_default($array);
        return true;
    }


    if($ID>0) {
        $array["{status}"] = "$page?backend-status=$ID&function=$function";
        $array["{global_settings}"]="$page?backend-popup=$ID&function=$function";
        $array["{HACLUSTER_AGENT}"]="$page?backend-agent=$ID&function=$function";
        $array["{scope}"] = "$page?backend-scope=$ID&function=$function";
    }
    echo $tpl->tabs_default($array);
    return true;
}
function backend_scope_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $ID=intval($_GET["backend-scope-js"]);
    $ligne=hacluster_db_get_backend($ID);
    $title=$ligne["backendname"] ?? "";
    return $tpl->js_dialog3("{scope}: $title","$page?backend-scope-popup=$ID",600);

}
function backend_scope_popup():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $ID=intval($_GET["backend-scope-popup"]);
    $ligne=hacluster_db_get_backend($ID);
    $scope=unserializeb64($ligne["scopes"] ?? "");
    $data=@implode("\n",$scope);
    $tpl->field_hidden("scopesid",$ID);
    $form[]=$tpl->field_textareacode("scopes", null, $data);

    $js[]="dialogInstance3.close();";
    $js[]="LoadAjax('backend-scope-$ID','$page?backend-scope-table=$ID')";

    echo $tpl->form_outside("{scope}", @implode("", $form),"{INTERNAL_NETWROK_ADD_EXPLAIN}","{apply}",
        @implode(";",$js),
        "AsSquidAdministrator");
    return true;
}
function backend_scope_save():bool{
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $ID         = intval($_POST["scopesid"]);
    $f          = explode("\n",$_POST["scopes"]);
    $ligne      = hacluster_db_get_backend($ID);
    $title      = $ligne["backendname"] ?? "";
    $new        = base64_encode(serialize($f));

    if(!hacluster_db_update_backend($ID, array("scopes"=>$new))){
        echo $tpl->javascript_parse_text("scope save failed");
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/flush");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/reconfigure");
    return admin_tracks("{scope} {saved} for {backend} $title");
}
function backend_scope():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $ID         = intval($_GET["backend-scope"]);
    echo "<div id='backend-scope-$ID'></div><script>LoadAjax('backend-scope-$ID','$page?backend-scope-table=$ID')</script>";
    return true;
}
function backend_scope_table():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $ID         = intval($_GET["backend-scope-table"]);
    $t          = time();
    // scopes column is provisioned by articarest at startup (csqlite.FieldExistCreateTEXT)
    $ligne=hacluster_db_get_backend($ID);
    $scopes=unserializeb64($ligne["scopes"] ?? "");

    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?backend-scope-js=$ID');\">";
    $html[]="<i class='fa fa-plus'></i> {edit} </label>";
    $html[]="</div>";
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{limit_computers_scope_to}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $c=0;
    foreach ($scopes as $ligne) {
        $ligne=trim($ligne);
        if($ligne==null){continue;}
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $c++;
        $md = md5($ligne);
        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = "<td style='width:99%' nowrap>". $tpl->td_href($ligne,null,"Loadjs('$page?backend-scope-js=$ID')")."</td>";
        $html[] = "</tr>";

    }
    if($c==0){
        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = "<td style='width:99%' nowrap>{all_computers}</td>";
        $html[] = "</tr>";
    }
    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='1'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function stop_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $ID=$_GET["stop-js"];
    $pname="proxy$ID";
    $json=json_decode($sock->REST_API("/hacluster/backend/stop/$pname"));
    if (!$json->Status){
        $tpl->js_error($json->Error);
        return false;
    }
    header("content-type: application/x-javascript");
    echo "Loadjs('$page?td-row=$ID');";
    return admin_tracks("Hacluster: Stop backend $pname");
}

function metrics(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $array["{this_hour}"]="$page?metrics-popup=yes&period=hourly";
    $array["{today}"]="$page?metrics-popup=yes&period=day";
    $array["{yesterday}"]="$page?metrics-popup=yes&period=yesterday";
    $array["{this_week}"]="$page?metrics-popup=yes&period=week";
    $array["{this_month}"]="$page?metrics-popup=yes&period=month";
    $array["{this_year}"]="$page?metrics-popup=yes&period=year";
    $html[]= "<div style='padding-top:10px;padding-left:10px;padding-right:20px;background-color: #ffffff'>";
    $html[]=$tpl->tabs_default($array);
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
}

function metrics_get_png($ID,$picture):string{

    $fname="ressources/logs/hacluster/$ID/HaClusterClientPNGS.array";
    VERBOSE("loading $fname",__LINE__);
    if(!is_file($fname)){
        VERBOSE("$fname no such file",__LINE__);
        return "";
    }
    $PNGS=unserialize(file_get_contents($fname));

    if(count($PNGS)==0){
        VERBOSE("[$ID]: NO PNGS found",__LINE__);
        return "";
    }


    if(!isset($PNGS[$picture])) {
        VERBOSE("[$ID]: $picture not found", __LINE__);
        return "";
    }
    return $PNGS[$picture];
}

function metrics_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $results=hacluster_db_list_backends("bweight", "DESC");
    $period=$_GET["period"];
    $function="";
    if(isset($_GET["function"])) {
        $function = $_GET["function"];
    }

    foreach ($results as $index=>$ligne){

        $ID=$ligne["ID"];
        $listen_port=$ligne["listen_port"];
        $listen_ip=$ligne["listen_ip"];
        $interface="$listen_ip:$listen_port";
        $hostname=$ligne["hostname"];
        $backendname=$tpl->td_href($ligne["backendname"],"$listen_ip:$listen_port","Loadjs('$page?backend-js=$ID&function=$function');')");

        $allqueries_name="allqueries-$period.flat.png";
        $png=metrics_get_png($ID,$allqueries_name);
        if(strlen($png)<20){
            continue;
        }

        $html[]="<div style='text-align: left;padding-right:20px;'><H3>$backendname ($interface - $hostname)</H3>";
        $html[]="<img src='data:image/png;base64,$png' alt='$allqueries_name'>";
        $html[]="</div>";

    }
    $html[]="<hr>";
    foreach ($results as $index=>$ligne){

        $ID=$ligne["ID"];
        $listen_port=$ligne["listen_port"];
        $listen_ip=$ligne["listen_ip"];
        $interface="$listen_ip:$listen_port";
        $hostname=$ligne["hostname"];
        $backendname=$tpl->td_href($ligne["backendname"],"$listen_ip:$listen_port","Loadjs('$page?backend-js=$ID&function=$function');')");


        $load_name="load-$period.flat.png";
        $png=metrics_get_png($ID,$load_name);
        if(strlen($png)<20){
            continue;
        }
        $html[]="<div style='text-align: left'><H3>$backendname ($interface - $hostname)</H3></div>";
        $html[]="<img src='data:image/png;base64,$png' alt='$load_name'>";
    }


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function start_ready(){
    $page=CurrentPageName();
    $sock=new sockets();
    $tpl=new template_admin();
    $ID=intval($_GET["start-ready"]);
    $pname="proxy$ID";

    if(preg_match("#T-([0-9]+)#",$ID,$re)){
        $pname="Tproxy{$re[1]}";
        // /hacluster/tproxy/backend/{id}/start : Tproxy<n> + Zproxy<n> sortis de maintenance
        // (portage de framework/hacluster.php?start-socket).
        $json=json_decode((string)$sock->REST_API("/hacluster/tproxy/backend/".intval($re[1])."/start"));
        if(!is_object($json) || empty($json->Status)){
            $tpl->js_error((is_object($json) && isset($json->Error))?$json->Error:"articarest: no answer");
            return false;
        }
        header("content-type: application/x-javascript");
        echo "LoadAjaxSilent('backend-list','$page?table=yes');";
        return admin_tracks("hacluster: Start backend $pname");
    }

    $json=json_decode($sock->REST_API("/hacluster/backend/start/$pname"));
    if (!$json->Status){
        $tpl->js_error($json->Error);
        return false;
    }

    header("content-type: application/x-javascript");
    echo "Loadjs('$page?td-row=$ID');";
    return admin_tracks("hacluster: order backend $pname to be ready");
}

function start_js():bool{
    $page=CurrentPageName();
    $sock=new sockets();
    $tpl=new template_admin();
    $ID=$_GET["start-js"];
    $pname="proxy$ID";

    if(preg_match("#T-([0-9]+)#",$ID,$re)){
        $pname="Tproxy{$re[1]}";
        // /hacluster/tproxy/backend/{id}/start : Tproxy<n> + Zproxy<n> sortis de maintenance
        // (portage de framework/hacluster.php?start-socket).
        $json=json_decode((string)$sock->REST_API("/hacluster/tproxy/backend/".intval($re[1])."/start"));
        if(!is_object($json) || empty($json->Status)){
            $tpl->js_error((is_object($json) && isset($json->Error))?$json->Error:"articarest: no answer");
            return false;
        }
        header("content-type: application/x-javascript");
        echo "LoadAjaxSilent('backend-list','$page?table=yes');";
        return admin_tracks("hacluster: Start backend $pname");
    }

    $json=json_decode($sock->REST_API("/hacluster/backend/start/$pname"));
    if (!$json->Status){
        $tpl->js_error($json->Error);
        return false;
    }


    header("content-type: application/x-javascript");
    echo "Loadjs('$page?td-row=$ID');";
    return admin_tracks("hacluster: Start backend $pname");
}

function checkCurStatus():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $HACLUSTER_CONFIG_FAILED=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HACLUSTER_CONFIG_FAILED"));
    if(strlen($HACLUSTER_CONFIG_FAILED)<5) {
        return false;
    }

    $prgress=$tpl->framework_buildjs("/hacluster/server/reconfigure",
        "hacluster.progress",
        "hacluster.progress.txt",
        "squid-bungled-explain",
        "LoadAjaxSilent('checkCurStatus','$page?checkCurStatus=yes');"
    );
    $CheckAgbtn=$tpl->button_autnonome("{check_again}",$prgress,ico_refresh,"AsSquidAdministrator",
        250,"btn-danger");
    $HACLUSTER_CONFIG_FAILED_TEXT=base64_decode($HACLUSTER_CONFIG_FAILED);
    $HACLUSTER_CONFIG_FAILED_TEXT= str_replace("\n", "<br>",$HACLUSTER_CONFIG_FAILED_TEXT);
    $explain= $tpl->div_error("<strong>{squid_bungled_explain}:</strong>
        <p>$HACLUSTER_CONFIG_FAILED_TEXT
        <div style='margin-top:10px;margin-right:20px'>$CheckAgbtn</div>
        </p>");
    $html[]="<div id='squid-bungled-explain'>$explain</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html="               
<div class='row'><div id='hacluster-backend-restart' class='white-bg'></div>
	<div class='ibox-content'>
	    <div id='checkCurStatus'></div>
		<div id='backend-list'></div>
     </div>
</div>
<script>
	$.address.state('/');
	$.address.value('/hacluster-backends');
    LoadAjax('backend-list','$page?table=yes');
    LoadAjaxSilent('checkCurStatus','$page?checkCurStatus=yes');
</script>";
    // Chaque onglet pose son en-tete : sans ce TINY, revenir de l'onglet Metrics
    // laissait ses filtres affiches au-dessus de la liste des backends.
    $TINY_ARRAY=array("TITLE"=>"{hb2_title}","ICO"=>"fas fa-server","EXPL"=>"{hb2_explain}",
        "URL"=>"hacluster-backends","BUTTONS"=>"");
    $html.="<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');</script>";


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{backends}",$html);
        echo $tpl->build_firewall();
        return;
    }

    echo $tpl->_ENGINE_parse_body($html);

}
function backend_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $function="";
    if(isset($_GET["function"])) {
        $function = $_GET["function"];
    }
	$new_backend=$tpl->_ENGINE_parse_body("{new_backend}");
	$ID=intval($_GET["backend-js"]);


	if($ID==0){$title="$new_backend";}else{
        $ligne=hacluster_db_get_backend($ID);
        $title=$ligne["backendname"] ?? "";
	}

	return $tpl->js_dialog2("#$ID $title", "$page?backend-tab=$ID&function=$function");
}
function backends_enable():bool{
    $page=CurrentPageName();
	$ID=intval($_GET["backend-enable-js"]);
    $ligne=hacluster_db_get_backend($ID);
	if(intval($ligne["enabled"] ?? 0)==1){$enabled=0;}else{$enabled=1;}
    hacluster_db_update_backend($ID, array("enabled"=>$enabled));
    $backendname=$ligne["backendname"] ?? "";

    header("content-type: application/x-javascript");
    $sock=new sockets();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/flush");
    $sock->REST_API("/hacluster/server/checkprod/node/$ID");
    $HaClusterServeDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterServeDNS"));
    if($HaClusterServeDNS==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/dns/restart");
    }

    echo "Loadjs('$page?td-row=$ID');Loadjs('$page?btn-action=$ID')";
    return admin_tracks("HaCluster $backendname {enable} = $enabled");

}
function backend_delete_js():bool{
    $function="";
    if(isset($_GET["function"])) {
        $function = $_GET["function"];
    }
	$tpl=new template_admin();
	$ID=intval($_GET["backend-delete-js"]);
    $ligne=hacluster_db_get_backend($ID);
    $title=$ligne["backendname"] ?? "";
    header("content-type: application/x-javascript");
	$js[]="$('#{$_GET["md"]}').remove()";
    if(strlen($function)>3){
        $js[]="$function();";
    }
    $sock=new sockets();
    $sock->REST_API("/hacluster/server/checkprod/node/$ID");
	return $tpl->js_confirm_delete($title , "backend-delete", $ID,@implode(";", $js));
	
}
function backend_reboot_js():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["backend-reboot-js"]);
    $ligne=hacluster_db_get_backend($ID);
    $title=$ligne["backendname"] ?? "";
    return $tpl->js_confirm_execute("{reboot} $title" , "backend-reboot", $ID);
}
function backend_kerberos_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["backend-kerberos-js"]);
    $ligne=hacluster_db_get_backend($ID);
    $title=$ligne["backendname"] ?? "";
    return $tpl->js_dialog2("Kerberos: $title","$page?backend-kerberos-popup=$ID");
}
// Detail de l'erreur Kerberos du noeud : meme tuile que la page AD du proxy.
function backend_kerberos_popup():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["backend-kerberos-popup"]);
    $krb=kerberos_spn_from_node_report($ID);
    if(!kerberos_spn_is_problem($krb)){
        echo $tpl->div_explain("Kerberos||{success}");
        return true;
    }
    echo $tpl->_ENGINE_parse_body(kerberos_spn_render($tpl,$krb,"node",""));
    return true;
}
function backend_ademergency_js():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["backend-ademergency-js"]);
    $ligne=hacluster_db_get_backend($ID);
    $title=$ligne["backendname"] ?? "";
    return $tpl->js_confirm_execute("{leave_ad_emergency} $title" , "backend-ademergency", $ID);
}
// Reponse lue par js_confirm_execute : vide = succes, sinon le texte s'affiche.
function backend_ademergency():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["backend-ademergency"]);
    if($ID<1){
        echo $tpl->javascript_parse_text("{protocol_error}");
        return false;
    }
    $ligne=hacluster_db_get_backend($ID);
    $title=$ligne["backendname"] ?? "";
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/backend/$ID/ad-emergency/off");
    $json=json_decode((string)$resp);
    if(!is_object($json) || !property_exists($json,"Status")){
        $raw=trim((string)$resp);
        echo htmlspecialchars($raw==""?$tpl->javascript_parse_text("{protocol_error}"):substr($raw,0,300));
        return false;
    }
    if(!$json->Status){
        echo htmlspecialchars((string)($json->Error ?? ""));
        return false;
    }
    return admin_tracks("HaCluster: ask $title to leave the Active Directory emergency mode");
}
function backends_reload_all():bool{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/clients/reload-all"),true);
    if(!isset($json["Status"])){
        return $tpl->js_error("{protocol_error}");
    }
    return $tpl->js_ok("{hacluster_reload_all_explain}");
}


function backend_iperf3_js():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["backend-iperf3-js"]);
    if($ID==0){
        return $tpl->js_error("ID == 0 ???");
    }
    $ligne=hacluster_db_get_backend($ID);
    $title=$ligne["backendname"] ?? "";
    return $tpl->js_confirm_execute("{speed_test} $title" , "backend-speed", $ID);
}
function backend_cluster_force_js():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["backend-cluster-force-js"]);
    if($ID==0){
        return $tpl->js_error("ID == 0 ???");
    }
    $ligne=hacluster_db_get_backend($ID);
    $title=$ligne["backendname"] ?? "";
    return $tpl->js_confirm_execute("{cluster_package} > {force_synchronization} > $title" , "backend-cluster", $ID);

}


function backend_reconfigure_js():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["backend-reconfigure-js"]);
    if($ID==0){
        return $tpl->js_error("ID == 0 ???");
    }
    $ligne=hacluster_db_get_backend($ID);
    $title=$ligne["backendname"] ?? "";
    return $tpl->js_confirm_execute("{reconfigure} $title" , "backend-reconfigure", $ID);
}
function backend_reconfiguredns_js():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["backend-dnsreconfigure-js"]);
    if($ID==0){
        return $tpl->js_error("ID == 0 ???");
    }
    $ligne=hacluster_db_get_backend($ID);
    $title=$ligne["backendname"] ?? "";
    return $tpl->js_confirm_execute("{dns_settings} $title" , "backend-reconfiguredns", $ID);
}
function backend_register():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $f[]=$tpl->div_success("{token}||{hacluster_deploy_explain2}");
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/get-token"),true);


    if(!isset($json["Status"])){
        echo $tpl->div_error("{protocol_error}");
        return false;

    }
    if(!$json["Status"]){
        echo $tpl->div_error($json["Error"]);
        return false;

    }
    $ttl=$json["ttl"];
    $hours   = floor($ttl / 3600);
    $minutes = floor(($ttl % 3600) / 60);
    $seconds = $ttl % 60;
    $display = sprintf("%dh %02dm %02ds", $hours, $minutes, $seconds);

    // Or as a future DateTime:
    $expires = new DateTime();
    $expires->modify("+$ttl seconds");
    $expiresStr = $expires->format("Y-m-d H:i:s");


    $f[]="<div style='font-size:22px;margin:30px' class=center><strong>{$json["token"]}<br><small>{expire} $expiresStr ($display)</small></strong>";


    echo $tpl->_ENGINE_parse_body($f);
    return true;



}





function backend_reconfigure():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["backend-reconfigure"]);
    $ligne=hacluster_db_get_backend($ID);
    $title=$ligne["backendname"] ?? "";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/flush");
    hacluster_db_update_backend($ID, array("orderqueue"=>2));
    $res=backend_common_notify($ID);
    if(strlen($res)>0){
        $tpl->js_error_stop($res);
        return true;
    }
   echo $tpl->_ENGINE_parse_body("{success}");
    return admin_tracks("HaCluster: Notify $title for reinstall Proxy configuration");
}

function backend_reconfiguredns():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=$_POST["backend-reconfiguredns"];
    if($ID==0){
        echo "ID is null ??\n";
        return false;
    }
    $ligne=hacluster_db_get_backend($ID);
    $title=$ligne["backendname"] ?? "";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/flush");
    hacluster_db_update_backend($ID, array("orderqueue"=>5));
    $res=backend_common_notify($ID);
    if(strlen($res)>0){
        echo $res;
        return true;
    }
    echo $tpl->_ENGINE_parse_body("{success}");
    return admin_tracks("HaCluster: Notify $title for reconfigure remote DNS backend configuration");
}

function backend_common_notify($ID):string{
    if(intval($ID)==0){
        return "id:$ID == 0 ? ?";
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/node/$ID"));
    if (json_last_error()> JSON_ERROR_NONE) {
        return json_last_error_msg();

    }

    if(!$json->Status){
        return $json->Error;

    }
    return "";

}

function backend_delete():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=intval($_POST["backend-delete"]);
    $ligne=hacluster_db_get_backend($ID);
    $title=$ligne["backendname"] ?? "";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/nodes/remove/$ID");
    return admin_tracks("HaCluster {delete} backend $title");
}
function backend_reboot():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["backend-reboot"]);
    $ligne=hacluster_db_get_backend($ID);
    $title=$ligne["backendname"] ?? "";

    hacluster_db_update_backend($ID, array("orderqueue"=>3));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/flush");
    $res=backend_common_notify($ID);
    if(strlen($res)>0){
        $tpl->js_error_stop($res);
        return true;
    }
    $tpl->js_ok("{success}");
    return admin_tracks("HaCluster: Notify $title for rebooting entire system");
}
function backend_speed():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["backend-speed"]);
    $ligne=hacluster_db_get_backend($ID);
    $title=$ligne["backendname"] ?? "";

    if(!hacluster_db_update_backend($ID, array("orderqueue"=>4))){
        echo "update failed";
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/flush");
    $res=backend_common_notify($ID);

    if(strlen($res)>0){
       echo $res;
        return true;
    }
    echo $tpl->_ENGINE_parse_body("{success}");
    return admin_tracks("HaCluster: Notify $title for test the speed between $title and the HaCluster ");
}
function backend_cluster_perform():bool{
    $tpl=new template_admin();
    $ID=intval($_POST["backend-cluster"]);
    $ligne=hacluster_db_get_backend($ID);
    $title=$ligne["backendname"] ?? "";


    if(!hacluster_db_update_backend($ID, array("orderqueue"=>6))){
        echo "update failed";
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/flush");
    $res=backend_common_notify($ID);

    if(strlen($res)>0){
        echo $res;
        return true;
    }
    echo $tpl->_ENGINE_parse_body("{success}");
    return admin_tracks("HaCluster: Notify $title for synchronizing cluster package between $title and the HaCluster ");
}
function backend_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["backend-popup"]);
    $function="";
    if(isset($_GET["function"])) {
        $function = $_GET["function"];
    }
    $ligne=array();
    if($ID>0) {
        $ligne=hacluster_db_get_backend($ID);
    }

    $jsadd=null;

    if($ID>0) {
        $buttonname="{apply}";

    }else{
        $expl="{hacluster_deploy_explain}";
        $ligne["backendname"]="proxy-".time();
        $title="{deploy}: {new_backend}";
		$jsadd="dialogInstance2.close();";
		$ligne["listen_port"]=8090;
        $ligne["noauth_port"]=8091;
		$ligne["bweight"]=1;
        $buttonname="{add}";
        $ligne["artica_port"]=9000;
        $ligne["enabled"]=1;
        $ligne["isMaster"]=0;
        $ligne["proxyversion"]=null;
        $ligne["ComPort"]=58787;

	}


    if($ID>0) {
        $jsrestart = $jsadd;

    }else{
        $jsrestart="$jsadd;LoadAjax('backend-list','$page?table=yes');";
    }

    $tpl->field_hidden("ID",$ID);
    $form[]=$tpl->field_checkbox("enabled","{enabled}", $ligne["enabled"],true);
    $form[]=$tpl->field_checkbox("isMaster","{master_proxy}", $ligne["isMaster"]);
    $form[]=$tpl->field_checkbox("isDisconnected","{disonnect_from_farm}", $ligne["isDisconnected"]);

    $form[]=$tpl->field_text("backendname", "{backendname}", $ligne["backendname"],true);
	$form[]=$tpl->field_ipaddr("listen_ip", "{destination_address}", $ligne["listen_ip"]);
	$form[]=$tpl->field_numeric("listen_port","{destination_port} ({APP_SQUID})", $ligne["listen_port"]);
    $form[]=$tpl->field_numeric("noauth_port","{destination_port} ({disable_authentication})", $ligne["noauth_port"]);


    $form[]=$tpl->field_numeric("ComPort","{destination_port} ({communication})", $ligne["ComPort"]);


    $form[]=$tpl->field_numeric("artica_port","{artica_listen_port}", $ligne["artica_port"]);
    if($ID==0){
        $form[]=$tpl->field_text("artica_username","{admin_name} (Manager)","",true);
        $form[]=$tpl->field_password("artica_password","{admin_password}","",true);
    }



	$form[]=$tpl->field_numeric("bweight","{weight}", $ligne["bweight"]);
    $html="<div id='nodes-connect'></div>".$tpl->form_outside($title, @implode("\n", $form),$expl,
            $buttonname,$jsrestart,"AsSquidAdministrator",true);
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}
function backends_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);
    if(hacluster_weight_refused($_POST["bweight"])){return false;}

    // Clear the master flag on every other backend before this save (insert or update).
    // The gate used to be `if($ID>0)` only, which let a new isMaster=1 row coexist
    // with a pre-existing master — producing two masters until the next edit.
    if (intval($_POST["isMaster"]) == 1) {
        hacluster_db_clear_master();
    }
    $backendname=$_POST["backendname"];
    $bweight=intval($_POST["bweight"]);
    $enabled=intval($_POST["enabled"]);
    $isMaster=intval($_POST["isMaster"]);
    $isDisconnected=intval($_POST["isDisconnected"]);
    $listen_ip=$_POST["listen_ip"];
    $listen_port=intval($_POST["listen_port"]);
    $artica_port=intval($_POST["artica_port"]);
    $ComPort=intval($_POST["ComPort"]);
    $noauth_port=intval($_POST["noauth_port"]);

    if( $ComPort==0 OR $ComPort==443 OR $ComPort==80 OR $ComPort==3128){
        echo $tpl->post_error("Communication port $ComPort invalid");
        return false;
    }
    if($ComPort==$noauth_port){
        $noauth_port=8091;
    }

    if($ID==0){
        $hostname="tmp-".time().".localhost.localdomain";
        $Username=urlencode($_POST["artica_username"]);
        $Password=urlencode($_POST["artica_password"]);
        $listen_ip_trim=trim($listen_ip);

        $newID = hacluster_db_insert_backend(array(
            "backendname"    => $backendname,
            "listen_ip"      => $listen_ip,
            "listen_port"    => $listen_port,
            "artica_port"    => $artica_port,
            "status"         => 0,
            "enabled"        => $enabled,
            "isMaster"       => $isMaster,
            "isDisconnected" => $isDisconnected,
            "bweight"        => $bweight,
            "hostname"       => $hostname,
            "ComPort"        => $ComPort,
            "orderqueue"     => 2,
            "noauth_port"    => $noauth_port,
        ));

        if($newID == 0){
            echo (new template_admin())->post_error("". $tpl->javascript_parse_text("insert failed"));
            return false;
        }

        $ID = $newID;
        // Replace the placeholder hostname with the user-supplied backendname.
        hacluster_db_update_backend($ID, array("hostname" => $backendname));

        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/register/node/$Username/$Password/$artica_port/$listen_ip_trim/$ComPort/$ID"));
        if(!$json->Status){
            hacluster_db_delete_backend($ID);
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/flush");
            echo $tpl->post_error($json->Error);
            return false;
        }

        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/clients/certificates"));
        if(!$json->Status){
            echo $tpl->post_error("clients/certificates: $json->Error");
            hacluster_db_delete_backend($ID);
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/flush");
            return false;
        }
        return admin_tracks("HaCluster {add} {new} backend $backendname");
    }

    $ligne=hacluster_db_get_backend($ID);
    $md5=($ligne["listen_ip"] ?? "") . ($ligne["listen_port"] ?? "") . ($ligne["artica_port"] ?? "");
    $md52=$listen_ip . $listen_port . $artica_port;
    $update = array(
        "backendname"    => $backendname,
        "listen_ip"      => $listen_ip,
        "listen_port"    => $listen_port,
        "enabled"        => $enabled,
        "isMaster"       => $isMaster,
        "isDisconnected" => $isDisconnected,
        "bweight"        => $bweight,
        "ComPort"        => $ComPort,
        "noauth_port"    => $noauth_port,
        "artica_port"    => $artica_port,
    );
    // Reset status on address change so the watchdog re-probes.
    if($md5 !== $md52){$update["status"] = 0;}

    if(!hacluster_db_update_backend($ID, $update)){
        echo (new template_admin())->post_error("". $tpl->javascript_parse_text("update failed"));
        return true;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/flush");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/node/$ID");


   return admin_tracks("HaCluster {edit} backend {$_POST["backendname"]}");
}
function status_zoom($ID,$class,$text){
    $tpl=new template_admin();
    return $tpl->td_href("<span class='label $class'>$text</span>","{action}","Loadjs('fw.hacluster.backends.zoom.php?backend-zoom-js=$ID');");
}
function refresh_table(){
    $page=CurrentPageName();
    $f[]="$('#table-haproxy-backends').remove();";
    $f[]="LoadAjax('backend-list','$page?table=yes');";
    echo @implode("\n",$f);
}
function table(){

    td_prepare();
	$page=CurrentPageName();
	$tpl=new template_admin();
    $jsSync=$tpl->framework_buildjs("/hacluster/server/notify/all",
    "hacluster.connect.progress",
    "hacluster.connect.txt","hacluster-backend-restart", ""
    );

    $jsReloadAll="Loadjs('$page?backend-reload-all=yes');";

    $function="";
    if(isset($_GET["function"])) {
        $function = $_GET["function"];
    }

    $HaClusterUseAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterUseAddr");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/globalstats");


        if(strlen($HaClusterUseAddr)>3) {
            $topbuttons[] = array("Loadjs('$page?backend-js=0&function=$function')", ico_plus, "{new_backend}");
        }else{
            $topbuttons[] = array("", ico_plus, "{new_backend}");
        }
    $topbuttons[] = array($jsSync, "fa-solid fa-bolt", "{reconfigure}:{nodes}");
    $topbuttons[] = array($jsReloadAll, "fal fa-sync-alt", "{reload}:{nodes}");


    $HaClusterDeployArticaUpdates=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterDeployArticaUpdates"));
    if($HaClusterDeployArticaUpdates==1){
        $ico_down=ico_download;
        $jsDeploy="Loadjs('fw.meta.updates.php')";
        $topbuttons[] = array($jsDeploy, $ico_down, "{updates}");
    }

    $zip=ico_file_zip;
    $topbuttons[] = array("", $zip, "{cluster_package}","ids:masterPackageStatus-btn,masterPackageStatus-ico,masterPackageStatus-text");
    $topbuttons[] = array("Loadjs('$page?refresh-table=yes');", "fal fa-sync-alt", "{refresh} {table}");



	$html[]=$tpl->th_buttons($topbuttons);
    // Seul le LB porte le keytab du cluster : une erreur sur lui touche tous les noeuds.
    $lbkrb=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/kerberos/spn"));
    if(is_object($lbkrb) && !empty($lbkrb->problem) && isset($lbkrb->kerberos) && is_object($lbkrb->kerberos)){
        $lbt=kerberos_spn_titles($lbkrb->kerberos->state);
        $btn=$tpl->button_autnonome("{kerberos_cluster_keytab}","document.location.href='/hacluster-kerberos'",ico_key,null,0,"btn-danger");
        $html[]=$tpl->div_error("{kerberos_cluster_keytab}||{$lbt[0]}||$btn");
    }
	$html[]="<table id='table-haproxy-backends' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' colspan='2'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{address}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{notify}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{load}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{sessions}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{requests}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{clients}</th>";

    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;&nbsp;&nbsp;&nbsp;IN&nbsp;&nbsp;&nbsp;&nbsp;</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;&nbsp;&nbsp;&nbsp;OUT&nbsp;&nbsp;&nbsp;&nbsp;</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>CNX</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{action}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{weight}</th>";

	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";



	$results = hacluster_db_list_backends("bweight", "DESC");

	$TRCLASS=null;
	$LoadStatus=LoadStatus();
    $jsAdd=array();
    $HaClusterServeDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterServeDNS"));
    if($HaClusterServeDNS==1){
        $DnsdistJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/backends/status"));
        $ligne["HaClusterServeDNS"]=$DnsdistJson;
    }

    $BACKENDS_STATS=$_SESSION["BACKENDS_STATS"];


	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$ID=$ligne["ID"];
        if(!isset($LoadStatus[$ID])){
            $LoadStatus[$ID]=array();
        }

        unset($BACKENDS_STATS["proxy$ID"]);
        unset($BACKENDS_STATS["proxyNoAuth$ID"]);

        $md="MDBackendID$ID";
        $curclients=getCurClient($ID);

        VERBOSE("CURCLIENTS: $ID: [$curclients]",__LINE__);

        if(strpos($ligne["backendname"],".")>0){
            $tb=explode(".",$ligne["backendname"]);
            $ligne["backendname"]=$tb[0];
        }
        VERBOSE("peityLoads($ID)",__LINE__);
        $Metrics=peityLoads($ID);
        if(count($Metrics)>0){
            $LoadLines=$Metrics["LOADLINE"];
            if(strlen($LoadLines)>2) {
                VERBOSE("LoadLines:$LoadLines",__LINE__);
            }

        }
        $reconfigure=$tpl->icon_repeat("Loadjs('$page?notify-backend=$ID')","AsSystemAdministrator");
        $StyleTD1="style='width:1%' nowrap";
        $StyleTD1Cen="class='center' style='width:1%' nowrap";
        $StyleTD0="style='width:99%'";
        $StyleTD2="style='width:1%;text-align:right' nowrap";

        $BTNS=td_btnAction($ligne);
        list($btnClass,$status_text)=td_status_text($ligne);
        $Triangle=td_status_warningDB($ligne);
        $status2=td_status2($ligne);
        $button=td_button($ligne);
        $bweight=td_bweight($ligne);
        $sessions=td_sessions($ligne);
        $CNX=td_CNX($ligne);
        $interface=td_interface($ligne);
        $articaversion=td_version($ligne);
        list($IN,$OUT)=td_inout($ligne);
        list($ClientLoad,$requests,$NumberoFClients)=td_requests($ligne);
        $tdHostname=td_hostname($ligne);


		$html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $StyleTD1 id='$index'><span id='STATUS-TEXT-$ID'>$status_text$Triangle</span></td>";
        $html[]="<td $StyleTD1><span id='STATUS-2-$ID'>$status2</span></td>";
        $html[]="<td $StyleTD1>
        <span id='STATUS-INTERFACE-$ID'>$interface</span>
        <span id='STATUS-VER-$ID'>$articaversion</span><div id=\"haloadT-$ID\" style='width:100%'></div>
       </td>";
		$html[]="<td $StyleTD0>
        <span id='STATUS-HOST-$ID'>$tdHostname</span></td>";
        //$GLOBALS["backends"]
        $html[]="<td $StyleTD1><span id='STATUS-HACNX-$ID'></span></td>";
        $html[]="<td $StyleTD1><span id='STATUS-MODE-$ID'>$BTNS</span></td>";
        $html[]="<td $StyleTD1>$reconfigure</td>";
        $html[]="<td $StyleTD1><span id='STATUS-LOAD-$ID'>$ClientLoad</span></td>";
        $html[]="<td $StyleTD2><span id='STATUS-SESS-$ID'>$sessions</span></td>";
        $html[]="<td $StyleTD2><span id='STATUS-RQS-$ID'>$requests</span></td>";
        $html[]="<td $StyleTD2><span id='STATUS-CLI-$ID'>$NumberoFClients</span></td>";
        $html[]="<td $StyleTD1><span id='STATUS-OUT-$ID'>$IN</span></td>";
		$html[]="<td $StyleTD1><span id='STATUS-IN-$ID'>$OUT</span></td>";
        $html[]="<td $StyleTD1><span id='STATUS-CNX-$ID'>$CNX</span></td>";
        $html[]= "<td $StyleTD1><span id='STATUS-BTN-$ID'>$button</span></td>";
		$html[]="<td $StyleTD1Cen><strong id='STATUS-WEIGHT-$ID'>$bweight</strong></td>";
		$html[]="</tr>";
	
	
	}
    if(!is_array($BACKENDS_STATS)){$BACKENDS_STATS=array();}

    if(count($BACKENDS_STATS)>0){

        foreach ($BACKENDS_STATS as $ServName=>$jsClass){
            if(preg_match("#^(proxyNoAuth|WebEHTTP)#",$ServName)){
                continue;
            }

            $srv_name=$jsClass->srv_name;
            VERBOSE("GHOST $ServName $srv_name",__LINE__);
            if(preg_match("#^proxy([0-9]+)#",$srv_name,$re)){
                $ID=$re[1];
            }


            $ligne["ID"]=$ID;
            $ligne["status"]=9999;
            $ligne["listen_ip"]=$jsClass->srv_addr;
            $ligne["listen_port"]=$jsClass->srv_port;
            $ligne["realname"]=$jsClass->srv_addr;
            $ligne["backendname"]=$jsClass->srv_addr;
            $ligne["isDisconnected"]=0;
            $ligne["hostname"]="{unknown}-$ID";

            list($btnClass,$status_text)=td_status_text($ligne);
            $Triangle=td_status_warningDB($ligne);
            $status2=td_status2($ligne);
            $button=td_button($ligne);
            $bweight=td_bweight($ligne);
            $CNX=td_CNX($ligne);
            $interface=td_interface($ligne);
            list($IN,$OUT)=td_inout($ligne);
            $tdHostname=td_hostname($ligne);
            $BTNS=td_btnAction($ligne);
            $index++;
            $md="MDBackendID$ID";
            $html[]="<tr class='$TRCLASS' id='$md'>";
            $html[]="<td $StyleTD1 id='$index'><span id='STATUS-TEXT-$ID'>$status_text$Triangle</span></td>";
            $html[]="<td $StyleTD1><span id='STATUS-2-$ID'>$status2</span></td>";
            $html[]="<td $StyleTD1>
        <span id='STATUS-INTERFACE-$ID'>$interface</span>
        <span id='STATUS-VER-$ID'></span><div id=\"haloadT-$ID\" style='width:100%'></div>
       </td>";
            $html[]="<td $StyleTD0>
        <span id='STATUS-HOST-$ID'>$tdHostname</span></td>";
            $html[]="<td $StyleTD1><span id='STATUS-MODE-$ID'>$BTNS</span></td>";
            $html[]="<td $StyleTD1>$reconfigure</td>";
            $html[]="<td $StyleTD1><span id='STATUS-LOAD-$ID'>-</span></td>";
            $html[]="<td $StyleTD1><span id='STATUS-RQS-$ID'>-</span></td>";
            $html[]="<td $StyleTD1><span id='STATUS-CLI-$ID'>-</span></td>";
            $html[]="<td $StyleTD1><span id='STATUS-OUT-$ID'>$IN</span></td>";
            $html[]="<td $StyleTD1><span id='STATUS-IN-$ID'>$OUT</span></td>";
            $html[]="<td $StyleTD1><span id='STATUS-CNX-$ID'>$CNX</span></td>";
            $html[]= "<td $StyleTD1><span id='STATUS-BTN-$ID'>$button</span></td>";
            $html[]="<td $StyleTD1Cen><strong id='STATUS-WEIGHT-$ID'>$bweight</strong></td>";
            $html[]="</tr>";

        }

    }

	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="<tr>";
	$html[]="<td colspan='20'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]=@implode("\n",$jsAdd);
	if(!isset($_GET["noscript"])) {
        $html[]=$tpl->RefreshInterval_Loadjs("table-haproxy-backends",$page,"allrows=yes&function=ReloadBackendTable",6);
    }
    $html[]="function ReloadBackendTable(){ Loadjs('$page?refresh-table=yes'); }";

	$html[]="</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
}
function getCurClient($ID){
    $workdir="ressources/logs/hacluster/$ID";
    return intval(@file_get_contents("$workdir/curclients.int"));
}
function td_btnActionBack():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["btn-action"]);
    $ligne=hacluster_db_get_backend($ID);
    $mode=base64_encode($tpl->_ENGINE_parse_body(td_btnAction($ligne)));
    header("content-type: application/x-javascript");
    echo "document.getElementById('STATUS-MODE-$ID').innerHTML=base64_decode('$mode');";
    return true;
}
function td_btnAction($ligne):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $BackenEnabled=intval($ligne["enabled"]);
    $clusteragent=intval($ligne["clusteragent"]);
    $Status=to_opstate($ligne);
    $StateAdmin=to_opFront($ligne);
    $ID=$ligne["ID"];

    if($BackenEnabled==1){
        $ico=ico_check;
        $label_class="btn-primary";
        $filters["{active2}"]="$ico:color:green||Loadjs('$page?backend-enable-js=$ID');";
    }else{
        $label_class="btn-default";
        $ico=ico_disabled;
        $filters["{inactive}"]="$ico:color:grey||Loadjs('$page?backend-enable-js=$ID');";
    }
    VERBOSE("Action#$ID Status=$Status StateAdmin=$StateAdmin");
    if($BackenEnabled==1) {
        if ($Status == 0) {
            if ($StateAdmin == 1) {
                $label_class = "btn-warning";
            }
        }
    }




    $filters["BUTTON"]=array("type"=>"xs","ID"=>"BTN-ACTION-LIST-$ID",
        "NoCleanJs"=>"yes","GLOBAL_CLASS"=>$label_class);
    list($title,$cont)=td_mode($ligne);
    $filters[$title]=$cont;

    if($clusteragent==1){
        $ico=ico_check;
        $filters["Agent"]="$ico:color:green||Loadjs('$page?clusteragent=$ID');";
    }else{
        $ico=ico_disabled;
        $filters["Agent"]="$ico:color:grey||Loadjs('$page?clusteragent=$ID');";

    }

    list($title,$cont)=td_statsjs($ligne);
    $filters[$title]=$cont;

    $realname=clean_host($ligne["realname"]);
    if($realname<>null) {
        $filters["{events}"]="fas fa-list-ul:color:black||Loadjs('fw.hacluster.backends.events.php?sysevnts=$ID');";
    }




    $md="MDBackendID{$ligne["ID"]}";
    $filters["SPACER"]=true;
    $MyCluster=__LocalClusterInfo();
    $MyClusterID=0;
    if(intval($MyCluster["MasterID"])>0){
        $MyClusterID=intval($MyCluster["MasterID"]);
    }
    if($MyClusterID>0 && $MyClusterID<>intval($ligne["ID"])){
        $filters["{force_synchronize}"] = ico_file_zip . ":color:black||Loadjs('$page?backend-cluster-force-js=$ID&md=$md')";
    }


    $EnableIperf3=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIperf3"));
    $Iperf3Installed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Iperf3Installed"));
    if($Iperf3Installed==0){
        $EnableIperf3=0;
    }

    if($EnableIperf3==1){
        if(is_backend_iperf($ID)) {
            $filters["{speed_test}"] = ico_speed . ":color:black||Loadjs('$page?backend-iperf3-js=$ID&md=$md')";
        }
    }
    $HaClusterServeDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterServeDNS"));
    if($HaClusterServeDNS==1){
        $filters["{reconfigure} DNS"]=ico_retweet.":color:black||Loadjs('$page?backend-dnsreconfigure-js=$ID&md=$md')";
    }

    if(intval($ligne["ActiveDirectoryEmergency"] ?? 0)==1){
        $filters["{leave_ad_emergency}"]=ico_bug.":color:red||Loadjs('$page?backend-ademergency-js=$ID&md=$md')";
    }
    if(kerberos_spn_is_problem(kerberos_spn_from_node_report($ID))){
        $filters["Kerberos {error}"]=ico_key.":color:red||Loadjs('$page?backend-kerberos-js=$ID')";
    }
    $filters["{resync_clock}"]=ico_clock.":color:".(hacluster_clock_is_problem(intval($ID))?"orange":"black")."||Loadjs('$page?backend-clock-js=$ID')";
    $filters["{reconfigure}"]=ico_cd.":color:black||Loadjs('$page?backend-reconfigure-js=$ID&md=$md')";
    $filters["{reboot}"]=ico_refresh.":color:red||Loadjs('$page?backend-reboot-js=$ID&md=$md')";
    $filters["{delete}"]=ico_trash.":color:red||Loadjs('$page?backend-delete-js=$ID&md=$md')";
    return $tpl->button_dropdown_table("{actions}",$filters,"AsProxyMonitor");
}
function is_backend_iperf($ID):bool{
    $workdir="ressources/logs/hacluster/$ID";
    if(!is_dir($workdir)) {
        return false;
    }
    $workfile="$workdir/HaClusterMetrics.array";
    if(!is_file($workfile)){
        return false;
    }
    $HaClusterMetrics= $GLOBALS["CLASS_SOCKETS"]->unserializeb64(@file_get_contents($workfile));
    if(!isset($HaClusterMetrics["IPERF3"])){
        $HaClusterMetrics["IPERF3"]="0";
    }
    $IPERF3=intval($HaClusterMetrics["IPERF3"]);
    if($IPERF3==0){
        return false;
    }
    return true;
}

function td_hostname($ligne):string{
    $function="";
    $HaClusterUseHaClient=0;
    if(isset($_GET["function"])) {
        $function = $_GET["function"];
    }
    $HaClusterGBConfig = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
    if(isset($HaClusterGBConfig["HaClusterUseHaClient"])) {
        $HaClusterUseHaClient = intval($HaClusterGBConfig["HaClusterUseHaClient"]);
    }
    $snmps_status=null;
    $proxyversion=trim($ligne["proxyversion"]);
    $proxyversionWarn=null;
    $tpl=new template_admin();
    $page=currentPageName();
    $ID=$ligne["ID"];
    $listen_ip=$ligne["listen_ip"];
    $listen_port=$ligne["listen_port"];
    $realname=clean_host($ligne["realname"]);
    $backend_host=clean_host($ligne["backendname"]);
    $isMaster=$ligne["isMaster"];
    $microproxy=$ligne["microproxy"];
    $clusteragent=intval($ligne["clusteragent"]);
    $certificateid=intval($ligne["certificateid"]);
    $enabled=intval($ligne["enabled"]);
    $ActiveDirectoryEmergency=intval($ligne["ActiveDirectoryEmergency"]);
    $orderqueue=intval($ligne["orderqueue"]);
    $labelDanger="label-danger";
    $labeSucces="label-success";
    $labeWarn="label-warning";
    $replicmaster=intval($ligne["replicmaster"]);
    $optname="";
    $RemotePortStatus=true;
    $HaClusterNodesPings=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterNodesPings");
    if(strlen($HaClusterNodesPings)>5) {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterNodesPings"));
        if (json_last_error() == JSON_ERROR_NONE) {
            if(property_exists($json,"hosts")) {
                $Hosts=$json->hosts;
                if(property_exists($Hosts,$ID)) {
                    if(!$Hosts->{$ID}->available){
                        $RemotePortStatus=false;
                    }
                }

            }
        }
    }

    if($enabled==0){
        $labelDanger="label-default";
        $labeSucces="label-default";
        $labeWarn="label-default";
    }
    $ToolTips=array();
    //

    if($HaClusterUseHaClient==1){
        $clusteragent=1;
    }

    if($clusteragent==1){
        $ToolTips[]="<span class='label label-default'>Agent</span>";
    }
    if($ActiveDirectoryEmergency==1){
        $ToolTips[]="<span class='label $labelDanger'>{activedirectory_emergency_mode}</span>";
    }
    if(kerberos_spn_is_problem(kerberos_spn_from_node_report($ID))){
        $ToolTips[]="<span class='label $labelDanger' style='cursor:pointer' OnClick=\"Loadjs('fw.hacluster.backends.php?backend-kerberos-js=$ID')\">Kerberos {error}</span>";
    }
    // Horloge du noeud ecartee de celle du LB (mesuree par le LB a chaque rapport).
    $clocklabel=hacluster_clock_label(intval($ID));
    if($clocklabel<>""){
        $ToolTips[]=$clocklabel;
    }

    if($realname<>null){
        if($realname<>$backend_host){
            $optname="&nbsp;<small>($realname)</small>";
        }
    }

    if(!$RemotePortStatus){
        $ToolTips[]="<span class='label $labelDanger'>Agent {error}</span>";
    }

    if($isMaster==1){$ToolTips[]="<span class='label label-success'>{master_proxy}</span>";}
    if($microproxy==1){
        $ToolTips[]="<span class='label $labeSucces'>MicroNode</span>";
    }

    if($proxyversion<>null){
        $tb=explode(".",$proxyversion);
        $MAJOR=intval($tb[0]);
        if($MAJOR>0) {
            if ($tb[0] < 5) {
                $proxyversionWarn="<i class=\"text-warning fas fa-exclamation-triangle\"></i>&nbsp;";
            }
        }
    }

    if($certificateid==0){
        $ToolTips[]="<span class='label $labelDanger'>{missing_certificate}</span>";
    }


    if ($orderqueue==2){
        $ToolTips[]="<span class='label $labelDanger'>{installing}</span>";
    }
    if ($orderqueue==3){
        $ToolTips[]="<span class='label $labelDanger'>{rebooting}</span>";
    }
    if ($orderqueue==4){
        $ToolTips[]="<span class='label $labelDanger'>{speed_test}</span>";
    }
    if ($orderqueue==5){
        $ToolTips[]="<span class='label $labelDanger'>{dns_settings}</span>";
    }
    if ($orderqueue==6){
        $ToolTips[]="<span class='label $labelDanger'>{force_synchronize}</span>";
    }

    if($isMaster==0){
        if($replicmaster==1){
            $ToolTips[]="<span class='label $labeWarn'>{ClusterWaitNotify}</span>";
        }
    }

    if(isset($ligne["HaClusterServeDNS"])){
        $pname="proxy$ID";
        if(property_exists($ligne["HaClusterServeDNS"],"Backends")){
            foreach ($ligne["HaClusterServeDNS"]->Backends as $clb=>$clbstat){
                if($clb==$pname){
                    VERBOSE("----> DNS <------ FOUND $pname --> $clbstat",__LINE__);
                    if(intval($clbstat)==1){
                        $ToolTips[]="<span class='label label-primary'>DNS</span>";
                    }else{
                        VERBOSE("----> DNS <------ DNS FAILED",__LINE__);
                        $ToolTips[]="<span class='label label-danger'>DNS</span>";
                    }
                }
            }
        }
    }
    $Array=td_array($ID);
    if(isset($Array["WEBERROR"])){
        if($Array["WEBERROR"]["status"]=="DOWN"){
            $ToolTips[]="<span class='label label-danger'>{WEB_ERROR_PAGE}</span>";
        }
        if($Array["WEBERROR"]["status"]=="MAINT"){
            $ToolTips[]="<span class='label label-warning'>{WEB_ERROR_PAGE}</span>";
        }
        if($Array["WEBERROR"]["status"]=="UP"){
            $ToolTips[]="<span class='label label-primary'>{WEB_ERROR_PAGE}</span>";
        }
    }
    $MyCluster=__LocalClusterInfo();
    if($MyCluster["MasterID"]>0){
        $Locaver=$MyCluster["ClusterVersion"];
        $MetricsArray=MetricsArray($ID);
        if(isset($MetricsArray["CLUSTER_SLAVE_INFO"])) {
            $ClusterInfo = json_decode($MetricsArray["CLUSTER_SLAVE_INFO"], true);
            $PackageVersion=$ClusterInfo["PackageVersion"];
            if( version_is_lower($Locaver,$PackageVersion)){
                $ToolTips[]="<span class='label label-warning'>{cluster_package} $PackageVersion</span>";
            }
        }
    }


    $ToolTipsText="";
    if(count($ToolTips)>0){
        $ToolTipsText="<div style='margin-top:5px'>".implode("&nbsp;",$ToolTips)."</div>";
    }
    $bname=$ligne["backendname"];
    $bits_per_second=td_bandwidth($ID);

    if(strlen($bits_per_second)>0){
        $bname="$bname <small>(<strong>$bits_per_second</strong>)</small>";
    }



    $backendname=$proxyversionWarn.$tpl->td_href("$bname","$listen_ip:$listen_port",
            "Loadjs('$page?backend-js=$ID&function=$function')").$optname;
    return $tpl->_ENGINE_parse_body("<strong>$snmps_status$backendname</strong>&nbsp;$ToolTipsText");
}
$a = "20260321-0902";
$b = "20260121-0902";
function version_is_lower($b, $a) {
    return (int)str_replace('-', '', $b) < (int)str_replace('-', '', $a);
}

function iperf3Report($ID):array{

    $WorkDir="/usr/share/artica-postfix/ressources/logs/hacluster/$ID";
    if(!is_dir($WorkDir)){
        VERBOSE("$WorkDir No such directory",__LINE__);
        return array("-","-");
    }
    $jsonIperf=null;

    if(is_file("$WorkDir/iperf.json")){
        $jsonIperf=json_decode(file_get_contents("$WorkDir/iperf.json"));
    }else{
        $workfile="$WorkDir/HaClusterMetrics.array";
        if(is_file($workfile)) {
            $HaClusterMetrics = $GLOBALS["CLASS_SOCKETS"]->unserializeb64(@file_get_contents($workfile));
            if(isset($HaClusterMetrics["IPERF3_REPORT"])) {
                $jsonIperf = json_decode($HaClusterMetrics["IPERF3_REPORT"]);
            }
        }
    }
    if(is_null($jsonIperf)){
        return array("-","-");
    }

    if(!property_exists($jsonIperf->end,"sum_sent")){
        VERBOSE("jsonIperf->end->sum_sent no attribute",__LINE__);
        return array("-","-");
    }
    if(!property_exists($jsonIperf->end->sum_sent,"bits_per_second")){
        VERBOSE("jsonIperf->end->sum_sent->bits_per_second no attribute",__LINE__);
        return array("-","-");
    }
    if(!property_exists($jsonIperf->end,"sum_received")){
        VERBOSE("jsonIperf->end->sum_received no attribute",__LINE__);
        return array("-","-");
    }
    if(!property_exists($jsonIperf->end->sum_received,"bits_per_second")){
        VERBOSE("jsonIperf->end->sum_received->bits_per_second no attribute",__LINE__);
        return array("-","-");
    }
    $bits_per_second_receiver=intval($jsonIperf->end->sum_received->bits_per_second);
    $bits_per_second_receiver=FormatBytes($bits_per_second_receiver/1024)."/s";


    $bits_per_second=intval($jsonIperf->end->sum_sent->bits_per_second);
    $bits_per_second=FormatBytes($bits_per_second/1024)."/s";
    return array($bits_per_second_receiver,$bits_per_second);
}

function td_bandwidth($ID):string{
   $f=iperf3Report($ID);
   return $f[1];

}
function td_bandwidth_down($ID):string{
    $f=iperf3Report($ID);
    return $f[0];
}
function td_button_stop($ID){
    $users=new usersMenus();
    $page=CurrentPageName();
    $tpl=new template_admin();
    return  $tpl->icon_stop("Loadjs('$page?stop-js=$ID')","AsProxyMonitor");

}
function td_button_stop_warning($ID):string{
    $users=new usersMenus();
    $page=CurrentPageName();
    $button="<button class='btn btn-w-m btn-warning' type='button' OnClick=\"Loadjs('$page?stop-js=$ID')\">{stop}</button>";
    if(!$users->AsProxyMonitor){
        $button="<button class='btn btn-w-m btn-default' type='button'>{stop}</button>";
    }
    return $button;
}
function td_button_start($ID):string{
    $users=new usersMenus();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $button=$tpl->icon_start_error("Loadjs('$page?start-js=$ID')","AsProxyMonitor");
    return $button;
}
function td_button_start_warning($ID):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->icon_pause_warning("Loadjs('$page?start-js=$ID')","AsProxyMonitor");

}
function td_button_start_paused($ID):string{
    $users=new usersMenus();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $button=$tpl->icon_pause("Loadjs('$page?start-js=$ID')","AsProxyMonitor");
    return $button;
}
function td_button_start_paused_error($ID):string{
    $users=new usersMenus();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $button=$tpl->icon_pause_error("Loadjs('$page?start-js=$ID')","AsProxyMonitor");
    return $button;
}
function td_button_start_toready($ID):string{
    $users=new usersMenus();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $button=$tpl->icon_pause_error("Loadjs('$page?start-ready=$ID')","AsProxyMonitor");
    return $button;
}
function td_button_activate($ID){
    $users=new usersMenus();
    $page=CurrentPageName();
    $button="<button class='btn btn-w-m btn-warning' type='button' OnClick=\"Loadjs('$page?start-activate=$ID')\">{activate}</button>";
    if(!$users->AsProxyMonitor){
        $button="<button class='btn btn-w-m btn-default' type='button'>{activate}</button>";
    }
    return $button;
}
function td_button($ligne):string{

    $ID=intval($ligne["ID"]);
    $Status=to_opstate($ligne);
    $StateAdmin=to_opFront($ligne);
    $tpl=new template_admin();

    VERBOSE("#$ID status=$Status state admin=$StateAdmin",__LINE__);

    if($Status==99){
        return $tpl->icon_stop("");
    }

    if($Status==0){
        if($StateAdmin==1){
            VERBOSE("#$ID status=$Status state admin=$StateAdmin -->td_button_start_warning",__LINE__);
            return td_button_start_warning($ID);
        }
    }


    if($Status==2){

        if($StateAdmin==0){
            return td_button_stop($ID);
        }
        if($StateAdmin==1){
            VERBOSE("#$ID status=$Status state admin=$StateAdmin -->td_button_start_warning",__LINE__);
            return td_button_start_warning($ID);
        }
        if($StateAdmin==2){
            return td_button_start_paused($ID);
        }
        if($StateAdmin==4){
            return td_button_start_paused_error($ID);
        }
        if($StateAdmin==20){
            return td_button_start_paused_error($ID);
        }
        return td_button_start($ID);
    }
    if($Status==1){
        return td_button_stop($ID);
    }

    return $tpl->icon_stop("");
}
function td_array($ID):array{
    if(isset($GLOBALS["TDARRAY"][$ID])){
        return $GLOBALS["TDARRAY"][$ID];
    }
    if(!isset($_SESSION["jsonstats"])){
        td_prepare();
    }
    $StatsDecoded=base64_decode($_SESSION["jsonstats"]);
    VERBOSE("DECODED: $StatsDecoded",__LINE__);

    $json=json_decode($StatsDecoded);


    if(!is_object($json)){
        return array();
    }

    $MyCode="proxy$ID";
    $MyWebCode="WebEHTTP$ID";

    if(!is_object($json->servers)){
        return array();
    }
    if(!property_exists($json->servers,"proxys")){
        return array();
    }
    $MyStats=array();

     if(property_exists($json->servers,"weberror_backends")){
         if(property_exists($json->servers->weberror_backends,$MyWebCode)) {
            $zClass=$json->servers->weberror_backends->{$MyWebCode};
             foreach ($zClass as $key=>$val){
                 $MyStats["WEBERROR"][$key]=$val;
             }
         }
     }

    $jsonStats=$json->servers->proxys;

    if(!property_exists($jsonStats,$MyCode)){
        VERBOSE("Key [$MyCode] doesn't exists!",__LINE__);
        $backends=$GLOBALS["backends"];
        $MyStats=array();
        if(!property_exists($backends,$MyCode)) {
            VERBOSE("Key $ID doesn't exists! in backends", __LINE__);
            return array();
        }

        if(!property_exists($backends,$MyCode)){
            VERBOSE("Key [$ID] doesn't exists!",__LINE__);
            return $MyStats;
        }

        $agentClass=$backends->{$MyCode};
        if(!property_exists($agentClass,"srv_agent_state")){
            VERBOSE("Key [srv_agent_state] doesn't exists!",__LINE__);
            return $MyStats;
        }

        $AgtnPort=intval($agentClass->srv_agent_port);
        if ($AgtnPort>0) {
            VERBOSE("Agent state=$agentClass->srv_agent_state", __LINE__);
            if(intval($agentClass->srv_agent_state)==22){
                $MyStats["agent_status"]="L7OK";
            }else{
                $MyStats["agent_status"]="SOCKERR";
            }
            return $MyStats;
        }
        $srv_op_state=intval($agentClass->srv_op_state);
        switch ($srv_op_state) {
            case 1:
                $MyStats["status"]="MAINT";
                break;
            case 2:
                $MyStats["status"]="UP";
                break;
            case 0:
                $MyStats["status"]="DOWN";
                break;
            case 3:
                $MyStats["status"]="MAINT";
                break;
            default:
                $MyStats["status"]="CODE $srv_op_state?";
        }
        return $MyStats;

    }

    $MyStatsOrg=$jsonStats->{$MyCode};

    foreach ($MyStatsOrg as $key=>$val){
        $MyStats[$key]=$val;
    }
    $GLOBALS["TDARRAY"][$ID]=$MyStats;
    return $MyStats;
}
function td_version($ligne):string{
    $isDisconnected=intval($ligne["isDisconnected"]);
    $articarest=$ligne["articarest"];
    $color="";
    $BackenEnabled=intval($ligne["enabled"]);

    if($BackenEnabled==0){
        $color="style='color:#CCC7C7FF'";
    }
    $v=array();
    if(isset($ligne["articaversion"])){
        $ligne["articaversion"]=str_replace(".000000","",$ligne["articaversion"]);
        $v[]=$ligne["articaversion"];
    }
    if($articarest<>null){
        $v[]=$articarest;
    }

    if(count($v)>0){
        return "<div $color>".@implode("&nbsp;/&nbsp;",$v)."</div>";
    }

    return "";


}
function td_status2($ligne):string{
    $isDisconnected=intval($ligne["isDisconnected"]);
    $listen_ip=$ligne["listen_ip"];
    $listen_port=47887;
    $ID=intval($ligne["ID"]);
    $BackenEnabled=intval($ligne["enabled"]);
    if($BackenEnabled==0){
        return "<div class='label label' style='display:block;padding:5px'>{disabled}</div>";
    }

    $BackendName="proxy$ID";
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterRestStatus"));

    if(property_exists($json,$BackendName)){
        if(!$json->{$BackendName}->status){
            return "<div class='label label-danger' style='display:block;padding:5px'>{error_network}</div>";
        }
    }

    if($isDisconnected==1){
        return "<div class='label label-warning' style='display:block;padding:5px'>{disconnected}</div>";
    }



    $BackendState=to_opstate($ligne);
    VERBOSE("BackendState = $BackendState",__LINE__);
    $BackendStateAdm=to_opFront($ligne);

    if ($BackendState==2) {
        return "<div class='label label-primary' style='display:block;padding:5px'>{running}</div>";

    }
    if($BackendStateAdm==1){
        return "<div class='label label-warning' style='display:block;padding:5px'>{maintenance}</div>";
    }
    return "<div class='label label-danger' style='display:block;padding:5px'>{stopped} </div>";

}
function td_mode($ligne):array{
    $isDisconnected=intval($ligne["isDisconnected"]);
    $BackenEnabled=intval($ligne["enabled"]);
    $Icon=ico_server;
    if($BackenEnabled==0){
        return array("{mode} ({standard})","$Icon:color:grey||blur();");
    }
    if($isDisconnected==1){
        return array("{mode} ({standard})","$Icon:color:grey||blur();");
    }
    return array("{mode} ({standard})","$Icon:color:blue||blur();");
}
function td_statsjs($ligne):array{
    $ID=$ligne["ID"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $isDisconnected=intval($ligne["isDisconnected"]);
    $BackenEnabled=intval($ligne["enabled"]);
    $Icon="fas fa-chart-pie";
    if($BackenEnabled==0){
        return array("{graphs}","$Icon:color:grey||blur();");


    }

    if($isDisconnected==1){
        return array("{graphs}","$Icon:color:grey||blur();");
    }
    return array("{graphs}","$Icon:color:black||Loadjs('$page?graphs=$ID');");

}
function td_CNX($ligne):string{
    $tpl=new template_admin();
    $ID=$ligne["ID"];
    $BackenEnabled=intval($ligne["enabled"]);
    $SessionsNum=0;
    if(!isset($_SESSION["backendsSess"])){
        td_prepare();
    }
    $Sessionns=json_decode(base64_decode($_SESSION["backendsSess"]));

    if(!is_object($Sessionns)){
        return "";
    }

    if(property_exists($Sessionns,"proxies")){
        $ProxiesSessions=$Sessionns->proxies;
        VERBOSE("Sessionns/proxies EXISTS",__LINE__);
        if(!is_null($ProxiesSessions)) {
            if (property_exists($ProxiesSessions, $ID)) {
                $SessionsNum = intval($ProxiesSessions->$ID);
            }
        }
    }

    if ($BackenEnabled==0){
        if($SessionsNum>0) {
            return "$SessionsNum/-";
        }
        return "-";
    }
    $isDisconnected=intval($ligne["isDisconnected"]);
    if($isDisconnected==1){
        if($SessionsNum>0) {
            return "$SessionsNum/-";
        }
        return "-";
    }
    $MyStats=td_array($ID);
    if(!isset($MyStats["connect"])){
        $MyStats["connect"]=0;
    }
    $Cnxs= $tpl->FormatNumber($MyStats["connect"]);
    return "$SessionsNum/$Cnxs";

}
function td_bweight($ligne):string{
    if(!isset($GLOBALS["backends"])){
        td_prepare();
    }
    $BackenEnabled=intval($ligne["enabled"]);
    if ($BackenEnabled==0){
        return "-";
    }
    $isDisconnected=intval($ligne["isDisconnected"]);
    if($isDisconnected==1){
        return "-";
    }
    $ID=intval($ligne["ID"]);
    $backends=$GLOBALS["backends"];


    if(!is_object($backends)){
        return "?!";
    }

    if(!property_exists($backends,"proxy$ID")){
        return "?";
    }




    $o=$backends->{"proxy$ID"};
    $srv_uweight=isset($o->srv_uweight)?strval($o->srv_uweight):"";
    VERBOSE("proxy$ID srv_uweight == $srv_uweight",__LINE__);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $js="Loadjs('$page?weight-js=$ID')";

    // On montre le poids CONFIGURE (bweight, 1, 2, 3...), pas le poids HAProxy (x10).
    // Poids effectif nul = backend draine : ecrit en toutes lettres, pas seulement en couleur.
    $bweight=intval(isset($ligne["bweight"])?$ligne["bweight"]:0);
    $pct=isset($o->weight_effective_pct)?intval($o->weight_effective_pct):-1;
    if($srv_uweight!==""){
        $drained=intval($srv_uweight)==0;
    }else{
        $drained=isset($o->metrics->raw->weight) && intval($o->metrics->raw->weight)==0;
    }
    $state=$drained?"{bti_drained}":"{bti_in_service}";
    $txt=hacluster_weight_text($bweight,$pct);
    $aria=$tpl->_ENGINE_parse_body("{weight}")." ".$txt;
    if($pct>=0 && $pct<100){
        $aria.=" (".$tpl->_ENGINE_parse_body("{hacluster_weight_effective_share}").")";
    }
    return $tpl->inline_metric($bweight,"",$js,ico_weight,"AsProxyMonitor",$state,$drained,$aria,$txt);
}

// Texte de la cellule poids : poids configure, plus la part annoncee par l'agent si elle est reduite.
// $pct < 0 = inconnue (articarest ancien). Fonction pure.
function hacluster_weight_text($bweight,$pct):string{
    $bweight=intval($bweight);
    if($bweight<=0){$bweight=1;}
    $pct=intval($pct);
    if($pct>=0 && $pct<100){return $bweight." &middot; ".$pct." %";}
    return strval($bweight);
}

// Poids > 25 refuse tant que le poids gradue des agents est actif (HaClusterAgentDynamicWeight=1) :
// le facteur HAProxy repasserait a 1 et les poids relatifs annonces seraient tronques a 0.
// Rend true (et affiche l'erreur) si le poids est refuse.
function hacluster_weight_refused($weight):bool{
    if(intval($weight)<=25){return false;}
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAgentDynamicWeight"))!=1){return false;}
    echo (new template_admin())->post_error("{hacluster_weight_over_25_dynamic}");
    return true;
}

function weight_save():bool{
    $ID=intval($_POST["weightid"]);
    $weight=intval($_POST["weight"]);
    if(hacluster_weight_refused($weight)){return false;}
    if(!hacluster_db_update_backend($ID, array("bweight"=>$weight))){
        writelogs("weight update failed for ID $ID",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }
    // La base est ecrite ; la route pose le poids dans HAProxy et rend l'echec (facteur illisible...).
    // post_error echappe lui-meme le texte.
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/node/weight/$ID");
    $json=json_decode((string)$resp);
    if(!is_object($json) || !property_exists($json,"Status")){
        $raw=trim((string)$resp);
        echo (new template_admin())->post_error($raw==""?"{protocol_error}":substr($raw,0,300));
        return false;
    }
    if(!$json->Status){
        echo (new template_admin())->post_error((string)($json->Error ?? ""));
        return false;
    }
    return admin_tracks("Set Weight $weight for HaCluster node $ID");

}
function weight_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["weight-popup"]);
    if(!isset($GLOBALS["backends"])){
        td_prepare();
    }
    $backends=$GLOBALS["backends"];

    if(!is_object($backends)){
       echo $tpl->div_error("TD Prepare failed, unknown error");
       return false;
    }

    if(!property_exists($backends,"proxy$ID")){
        //var_dump($backends);

        echo $tpl->div_error("This node [proxy$ID] cannot be seen from the list ??? Strange ?");
        return false;
    }

    $row=hacluster_db_get_backend($ID);
    $weight=intval(is_array($row) && isset($row["bweight"])?$row["bweight"]:0);
    if($weight<=0){$weight=1;}
    $after="dialogInstance3.close();";
    echo $tpl->BigFieldIntegerCheckbox("weight|weightid:$ID","{weight}",
        "{haproxy_weight_explain}",$weight,1,255,"$after");
    return true;
}


function td_inout($ligne):array{
    $ID=$ligne["ID"];
    $BackenEnabled=intval($ligne["enabled"]);

    if ($BackenEnabled==0){
        return array("-","-");
    }
    $isDisconnected=intval($ligne["isDisconnected"]);
    if($isDisconnected==1){
        return array("-","-");
    }

    $MyStats=td_array($ID);
    if(!isset($MyStats["bin"])){
        $MyStats["bin"]=0;
    }
    if(!isset($MyStats["bout"])){
        $MyStats["bout"]=0;
    }
    $bin=$MyStats["bin"];
    $bout=$MyStats["bout"];
    if(strpos($bin,".")>0){
        $floatValue = floatval($bin);
        $bin = (int)$floatValue;
    }
    if(strpos($bout,".")>0){
        $floatValue = floatval($bin);
        $bout = (int)$floatValue;
    }
    if($bin==0){
        return array("","");
    }

    return array(FormatBytes($bin/1024),FormatBytes($bout/1024));

}
function td_prepare(){
    $_SESSION["jsonstats"]="";
    $_SESSION["backendsSess"]="";
    $_SESSION["inprod"]="";
    $GLOBALS["backends"]="";
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/nodes/inproduction"));

    if(!$json->Status){
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HACLUSTER_TD_PREPARE_INPRODUCTION"));
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HACLUSTER_TD_PREPARE_INPRODUCTION",json_encode($json));
    }

    $_SESSION["inprod"]=$json->Info;
    if(property_exists($json,"Stats") && isset($json->Stats)) {
        $_SESSION["jsonstats"] = base64_encode(json_encode($json->Stats));
    }
    if(property_exists($json,"Sessions") && isset($json->Sessions)) {
        $_SESSION["backendsSess"] = base64_encode(json_encode($json->Sessions));
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/clients"));
    if(!$json->Status){
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HACLUSTER_TD_PREPARE_CLIENTS"));
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HACLUSTER_TD_PREPARE_CLIENTS",json_encode($json));
    }

    if(property_exists($json,"backends")) {
        $GLOBALS["backends"] = $json->backends;
    }
    if(isset($_SESSION["BACKENDS_STATS"])) {
        unset($_SESSION["BACKENDS_STATS"]);
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/nodes/stats"));
    if(!$json->Status){
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HACLUSTER_TD_PREPARE_STATS"));
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HACLUSTER_TD_PREPARE_STATS",json_encode($json));
    }
    if(property_exists($json,"Status")) {
        if ($json->Status) {
            foreach ($json->Stats as $SrvProxy => $class) {
                $_SESSION["BACKENDS_STATS"][$SrvProxy] = $class;
            }
        }
    }
}
function to_opstate($ligne):int{
    $ID=intval($ligne["ID"]);

    $SrvProxy="proxy$ID";
    if(!isset($_SESSION["BACKENDS_STATS"][$SrvProxy])){
        return 99;
    }

    $json=$_SESSION["BACKENDS_STATS"][$SrvProxy];

    if(!property_exists($json,"srv_op_state")){
        return 99;
    }
    return $json->srv_op_state;



}
function to_opFront($ligne){
    $ID=intval($ligne["ID"]);
    if(!isset($GLOBALS["backends"])){
        td_prepare();

    }

    $backends=$GLOBALS["backends"];

    if(!is_object($backends)){
        VERBOSE("$ID) backends object not found",__LINE__);
        return 99;
    }
    if(!property_exists($backends,"proxy$ID")){
        VERBOSE("$ID no property",__LINE__);
        return 99;
    }
    $backendState=$backends->{"proxy$ID"};
    if(!property_exists($backendState,"srv_admin_state")){
        VERBOSE("$ID no property srv_admin_state",__LINE__);
        return 99;
    }
    return intval($backendState->srv_admin_state);

}
function td_status_warningDB($ligne){
    if(!isset($ligne["ID"])){
        return "";
    }
    if(!isset($ligne["status"])){
        return "";
    }
    if($ligne["status"]==100){
        return "";
    }
    return "&nbsp;<i class='text-warning fas fa-exclamation-triangle'></i>";
}
//ptch 1
function td_status_text($ligne):array{
    if(!isset($ligne["ID"])){
        $ligne["ID"]=0;
    }
    if(!isset($ligne["status"])){
        $ligne["status"]=0;
    }

    $ID=intval($ligne["ID"]);
    $status=intval($ligne["status"]);

    $status_textin=StatusIntToStr($status);

    if(!isset($ligne["isDisconnected"])){
        $ligne["isDisconnected"]=0;
    }
    if(!isset($ligne["enabled"])){
        $ligne["enabled"]=0;
    }
    $isDisconnected=intval($ligne["isDisconnected"]);
    $BackenEnabled=intval($ligne["enabled"]);
    $BackendState=to_opstate($ligne);
    $BackendStateAdm=to_opFront($ligne);

    VERBOSE("BTN#$ID status=$status, Enabled=$BackenEnabled BackendState=$BackendState BackendStateAdm=$BackendStateAdm isDisconnected=$isDisconnected",__LINE__);

    if($BackendState==99 or $BackendState==999){
        if($BackenEnabled==0) {
            return array("btn-default",status_zoom($ID, "label", "{disabled}") );
        }
        if($isDisconnected==1){
            return array("btn-default",status_zoom($ID,"label","{disconnected}"));
        }

        return array("btn-warning",status_zoom($ID,"label-warning","{disconnected}"));
    }



    if($BackenEnabled==0){
        if($BackendState==0) {
            return array("btn-default",status_zoom($ID, "label-default", "{in_production} OFF"));
        }
        if($BackendState==1){
            return array("btn-default",status_zoom($ID, "label-default", "{in_production}"));
        }

        if($BackendState==2) {
            return array("btn-default",status_zoom($ID, "label-default", "{in_production}"));
        }
        if($BackendState==3) {
            return array("btn-default",status_zoom($ID, "label-default", "{in_production} OFF"));
        }
    }
    if($isDisconnected==1){
        if($BackendState==0) {
            return array("btn-default", status_zoom($ID, "label-default", "{in_production} OFF"));
        }
        if($BackendState==1) {
            return array("btn-default", status_zoom($ID, "label-default", "{in_production}"));
        }

        if($BackendState==2) {
            return array("btn-default", status_zoom($ID, "label-default", "{in_production}"));
        }
        if($BackendState==3) {
            return array("btn-default", status_zoom($ID, "label-default", "{in_production} OFF"));
        }
        return array("btn-default", status_zoom($ID,"label","{disconnected}"));

    }

    if($BackendState==0){
        VERBOSE("BTN#$ID <strong>BackendState==0</strong> status=$status, Enabled=$BackenEnabled BackendState=$BackendState BackendStateAdm=$BackendStateAdm isDisconnected=$isDisconnected",__LINE__);
        if($BackendStateAdm==1) {
            VERBOSE("BTN#$ID btn-warning",__LINE__);
            return array("btn-warning",  "<span class='label label-warning'>$status_textin</span>");
        }
    }

    VERBOSE("BTN#$ID btnClass PASS status=$status, Enabled=$BackenEnabled BackendState=$BackendState BackendStateAdm=$BackendStateAdm",__LINE__);

    if($status==1){
        return array("btn-danger",  status_zoom($ID,"label-danger",$status_textin));
    }
    if($status==2){
        return array("btn-warning",  "<span class='label label-warning'>$status_textin</span>");
    }

    if($status==3){
        return array("btn-danger",  status_zoom($ID,"label-danger",$status_textin));
    }
    if($status==4){
        return array("btn-warning",  "<span class='label label-warning'>$status_textin</span>");
    }
    if($status==5){
        return array("btn-warning",  "<span class='label label-warning'>$status_textin</span>");
    }
    if($status==6){
        return array("btn-warning",  "<span class='label label-warning'>$status_textin</span>");
    }
    if($status==7){
        return array("btn-warning",  "<span class='label label-warning'>$status_textin</span>");
    }
    if($status==8){
        return array("btn-warning",  "<span class='label label-warning'>$status_textin</span>");
    }
    if($status==9){
        return array("btn-warning",  "<span class='label label-warning'>$status_textin</span>");
    }
    if($status==10){
        return array("btn-warning",  "<span class='label label-warning'>$status_textin</span>");

    }

    if($status==100){
        $ARRAY=array();
        $MAIN=LoadStatus();
        if(isset($MAIN[$ID])) {
            $ARRAY = $MAIN[$ID];
        }

        if(isset($ARRAY["STOPPED"])){
            if(isset($GLOBALS["LAYER_STATUS"][$ARRAY["LAYER_STATUS"]])){
                $status_textin=$GLOBALS["LAYER_STATUS"][$ARRAY["LAYER_STATUS"]];
            }
            return array("btn-danger",  status_zoom($ID,"label-danger",$status_textin));
        }


        if($BackendState==0) {
            if($BackendStateAdm==1) {
                return array("btn-warning",  status_zoom($ID,"label-warning",$status_textin));
            }

            if($BackendStateAdm==2){
                return array("btn-warning",  status_zoom($ID,"label-warning",$status_textin));
            }
            if($BackendStateAdm==4){
                return array("btn-danger",  status_zoom($ID,"label-danger",$status_textin));
            }
            if($BackendStateAdm==20){
                return array("btn-danger",  status_zoom($ID,"label-danger",$status_textin));
            }

        }
        return array("btn-primary",  status_zoom($ID,"label-primary",$status_textin));
    }
    if($status==110){
        return array("btn-danger",  "<span class='label label-danger'>$status_textin</span>");

    }

    return array("btn-warning",  "<span class=label>$status_textin</span>");
}
function StatusIntToStr($status):string{
    if($status==0){return "{waiting_registration}";}
    if($status==1){return "{error}";}
    if($status==2){return "{configuring}";}
    if($status==3){return "{setup_error}";}
    if($status==4){return "{setup} 50%";}
    if($status==5){return "PING OK";}
    if($status==6){return "{configuring} 70%";}
    if($status==7){return "{configuring} 80%";}
    if($status==8){return "{configuring} 90%";}
    if($status==9){return "{configuring} 95%";}
    if($status==10){return "{success} 100%";}
    if($status==100){return "{in_production}";}
    if($status==110){return "{rebooting}";}
    if($status==999){return "{ghost_server}";}
    return "{unknown}";
}
function peityLoads($ID):array{
    $fname="ressources/logs/hacluster/$ID/HaClusterMetrics.array";
    if(!is_file($fname)){
        return array();
    }


    $tpl=new template_admin();
    $HaClusterMetrics= $GLOBALS["CLASS_SOCKETS"]->unserializeb64(@file_get_contents($fname));
    if(!isset($HaClusterMetrics["LOAD"])){
        writelogs("No Load from $fname",__FUNCTION__,__FILE__,__LINE__);
        $HaClusterMetrics["LOAD"]=0;
    }
    $Metrics["LOAD"]=$HaClusterMetrics["LOAD"];
    if($GLOBALS["VERBOSE"]){
        echo "----------------------------- Server ID $ID\n";
        foreach ($HaClusterMetrics as $key=>$val){
            echo "\tKey  = [$key]\n";
        }
    }
    $REQUESTS=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($HaClusterMetrics["REQUESTS"]);
    if($GLOBALS["VERBOSE"]) {
        print_r($REQUESTS);
    }
    if(!isset($REQUESTS["RQS"])){
        $REQUESTS["RQS"]=0;
    }
    $Metrics["RQS"]=$tpl->FormatNumber($REQUESTS["RQS"]);
    if(!isset($REQUESTS["RQSS"])){$REQUESTS["RQSS"]=0;}
    $Metrics["HTTPS"]=$REQUESTS["RQSS"]."/s";
    if(isset($HaClusterMetrics["REQUESTS_SECONDS"])){
        $Metrics["HTTPS"]=$HaClusterMetrics["REQUESTS_SECONDS"]."/s";
    }
    if(isset($HaClusterMetrics["CPU_AVG"])) {
        VERBOSE("CPU_AVG: {$HaClusterMetrics["CPU_AVG"]}",__LINE__);
        $Metrics["CPU_AVG"] = "&nbsp;CPU:".$HaClusterMetrics["CPU_AVG"]."%";
    }


    if(isset($HaClusterMetrics["LOADLINE"])) {
        VERBOSE("LoadLine: {$HaClusterMetrics["LOADLINE"]}",__LINE__);
        $Metrics["LOADLINE"] = $HaClusterMetrics["LOADLINE"];
    }
    if(isset($HaClusterMetrics["LOADLINE"])) {
        VERBOSE("LoadLine: {$HaClusterMetrics["LOADLINE"]}",__LINE__);
        $Metrics["LOADLINE"] = $HaClusterMetrics["LOADLINE"];
    }

    $zload=array();
    if(!isset($Metrics["LOADLINE"])) {
        if(isset($REQUESTS["TIMES"])) {
            foreach ($REQUESTS["TIMES"] as $time => $value) {
                $value = floatval($value);
                $zload[] = $value;
            }
        }
        $Metrics["LOADLINE"] = implode(",", $zload);
        VERBOSE("LOADLINE = {$Metrics["LOADLINE"]}");
    }

    return $Metrics;


}
function HaClusterUseHaClient_checks($ligne){

    $HaClusterNode=intval($ligne["ID"]);
    if(!isset($GLOBALS["HaclusterServerNodesStatus"])){
        $GLOBALS["HaclusterServerNodesStatus"]=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/nodes/status"));
    }
    if(!$GLOBALS["HaclusterServerNodesStatus"]->Status){
        return array("ERROR"=>true,"ERROR_TEXT"=>"Protocol error ".__LINE__);


    }
    if(!property_exists($GLOBALS["HaclusterServerNodesStatus"],"Backends")){
        return array("ERROR"=>true,"ERROR_TEXT"=>"Protocol error ".__LINE__);

    }
    foreach ($GLOBALS["HaclusterServerNodesStatus"]->Backends as $backend){
        if($backend->ID==$HaClusterNode){
            $listen_ip=$backend->Ipstr;
            $listen_port=$backend->Port;
            if(!$backend->Status){
                $errstr=$backend->Error;
                return array("ERROR"=>true,"ERROR_TEXT"=>"($listen_ip:$listen_port) $errstr");
            }
            break;
        }
    }


    /*
    if(!$fp) {
        return array("ERROR"=>true,"ERROR_TEXT"=>"$errstr ($errno)"." (tcp:$listen_ip:27899)");
    }
    */

    if(!is_file("ressources/logs/HaClusterClientMetrics.$HaClusterNode")){
        return array("ERROR"=>false,"ERROR_TEXT"=>"");
    }

    $data=@file_get_contents("ressources/logs/HaClusterClientMetrics.$HaClusterNode");
    $data=trim($data);

    $MAIN["ERROR"]=false;
    $Heads=MetricsToMain($data);
    foreach ($Heads as $key=>$val){
        $MAIN[$key]=$val;
    }
    return $MAIN;

}
function MetricsToMain($row):array{
    $MAIN=array();
    $array=explode(",",$row);
    if(count($MAIN)<4){
        return $MAIN;
    }

    $MAIN["CurLoad"]=$array[0];
    $MAIN["MaxLoad"]=$array[1];
    $MAIN["Cpunumber"]=$array[2];
    $MAIN["cpupercent"]=$array[3];
    $MAIN["requests"]=$array[4];
    $MAIN["clients"]=$array[5];
    $MAIN["maxfd"]=$array[6];
    $MAIN["curfd"]=$array[7];

    return $MAIN;
}
function clean_host($hostname):string{
    $hostname=trim(strtolower($hostname));
    if($hostname==null){return "";}
    if(strpos($hostname,".")==0){return $hostname;}
    $tb=explode(".",$hostname);
    return $tb[0];

}

function LoadStatus(){
    $MAIN=array();
    $page=CurrentPageName();
    $users=new usersMenus();
    if(isset($GLOBALS["LOAD_STATUS"])){
        return $GLOBALS["LOAD_STATUS"];
    }

    $GLOBALS["makeQueryForce"]=true;
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/globalstats");




    $HaClusterTransParentMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode"));
    $xtable=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/hacluster.status.dmp"));

    if($HaClusterTransParentMode==1) {
        $manager = new cache_manager("127.0.0.1:3150");
        $data = $manager->makeQuery("server_list");
        $tt = explode("\n", $data);
        foreach ($tt as $zline) {
            if (preg_match("#Parent\s+:\s+(.+)#", $zline, $re)) {
                $PARENT = trim($re[1]);
                continue;
            }
            if (preg_match("#^(.+?)\s+:\s+(.+)#", $zline, $re)) {
                $key=strtoupper(trim($re[1]));

                if(isset($TRANSPARENT[$PARENT][$key])){
                    if(is_numeric($TRANSPARENT[$PARENT][$key])){
                        $TRANSPARENT[$PARENT][$key]=intval($TRANSPARENT[$PARENT][$key])+intval($re[2]);
                        continue;
                    }
                }
                $TRANSPARENT[$PARENT][$key] = trim($re[2]);
            }

        }


        foreach ($TRANSPARENT as $ID=>$array){
            VERBOSE("Transparent: $ID  == {$array["STATUS"]}");
            if($array["STATUS"]=="Up"){
                $button=null;
                $color="#D20C0C";
                $img="<div class='label label-primary' style='display:block;padding:5px'>{running}</div>";
                $button="<button class='btn btn-w-m btn-primary' type='button' OnClick=\"Loadjs('$page?stop-transparent-js=$ID')\">{stop}</button>";
                if(!$users->AsProxyMonitor){$button="<button class='btn btn-w-m btn-default' type='button'>{stop}</button>";}
            }else{
                $button=null;
                $img="<div class='label label-danger' style='display:block;padding:5px'>{stopped}</div>";
                $color="#D20C0C";
                $button="<button class='btn btn-w-m btn-danger' type='button' OnClick=\"Loadjs('$page?start-transparent-js=$ID')\">{start}</button>";
                if(!$users->AsProxyMonitor){$button="<button class='btn btn-w-m btn-default' type='button'>{start}</button>";}
            }

            $req_tot=$array["FETCHES"];
            $MAIN[$ID]["IMG"]=$img;
            $MAIN[$ID]["BUTTON"]=$button;
            $MAIN[$ID]["COLOR"]=$color;
            $MAIN[$ID]["REQS"]=intval($req_tot);


        }

    }
    $typof=array(0=>"frontend", 1=>"backend", 2=>"server", 3=>"socket");
    $status["UNK"]="unknown";
    $status["INI"]="initializing";
    $status["SOCKERR"]="socket error";
    $status["L4OK"]="check passed on layer 4, no upper layers testing enabled";
    $status["L4TMOUT"]="layer 1-4 timeout";
    $status["L4CON"]="layer 1-4 connection problem";
    $status["L6OK"]="check passed on layer 6";
    $status["L6TOUT"]="layer 6 (SSL) timeout";
    $status["L6RSP"]="layer 6 invalid response - protocol error";
    $status["L7OK"]="check passed on layer 7";
    $status["L7OKC"]="check conditionally passed on layer 7, for example 404 with disable-on-404";
    $status["L7TOUT"]="layer 7 (HTTP/SMTP) timeout";
    $status["L7RSP"]="layer 7 invalid response - protocol error";
    $status["L7STS"]="layer 7 response error, for example HTTP 5xx";

    $ERR["SOCKERR"]=true;
    $ERR["L4TMOUT"]=true;
    $ERR["L4CON"]=true;
    $ERR["L6TOUT"]=true;
    $ERR["L6RSP"]=true;
    $ERR["L7TOUT"]=true;
    $ERR["L7RSP"]=true;
    $ERR["L7STS"]=true;


    foreach ($xtable as $ligne) {
        $check_status_text=null;
        $ligne = trim($ligne);
        if ($ligne == null) {
            continue;
        }
        if (preg_match("#\##", $ligne)) {
            continue;
        }

        VERBOSE($ligne,__LINE__);
        $f = explode(",", $ligne);


        $pxname = $f[0];
        $svname = trim($f[1]);

        if($GLOBALS["VERBOSE"]){
            VERBOSE("Found server $svname - $pxname",__LINE__);
        }

        if(!preg_match("#^(T|)proxy([0-9]+)#",$svname,$re)){continue;}
        $ID=$re[2];


        $qcur = $f[2];
        $qmax = $f[3];
        $scur = $f[4];
        $smax = $f[5];
        $slim = $f[6];
        $stot = $f[7];
        $bin = FormatBytes($f[8] / 1024);
        $bout = FormatBytes($f[9] / 1024);
        $dreq = $f[10];
        $dresp = $f[11];
        $ereq = $f[12];
        $econ = $f[13];
        $eresp = $f[14];
        $wretr = $f[15];
        $wredis = $f[16];
        $status = $f[17];
        $weight = $f[18];
        $act = $f[19];
        $bck = $f[20];
        $chkfail = $f[21];
        $chkdown = $f[22];
        $lastchg = $f[23];
        $downtime = $f[24];
        $qlimit = $f[25];
        $pid = $f[26];
        $iid = $f[27];
        $sid = $f[28];
        $throttle = $f[29];
        $lbtot = $f[30];
        $tracked = $f[31];
        $type = $typof[$f[32]];
        $rate = $f[33];
        $rate_lim = $f[34];
        $rate_max = $f[35];
        $check_status = $f[36];
        $check_code = $f[37];
        $check_duration = $f[38];
        $hrsp_1xx = $f[39];
        $hrsp_2xx = $f[40];
        $hrsp_3xx = $f[41];
        $hrsp_4xx = $f[42];
        $hrsp_5xx = $f[43];
        $hrsp_other = $f[44];
        $hanafail = $f[45];
        $req_rate = $f[46];
        $req_rate_max = $f[47];
        $req_tot = intval($f[48]);
        $cli_abrt = $f[49];
        $srv_abrt = $f[50];
        $issue_cnx=$f[57];
        $last_agent_check=$f[57];
        $LAYER_STATUS=$f[62]; // L7OK
        $agent_status=$f[66];



        if($status=="no check" && $check_status==""){
            $check_status=$LAYER_STATUS;
            if(isset($ERR[$check_status])){
                $status="DOWN";
            }
        }


        VERBOSE("$pxname; Status:[$status] check_status:[$check_status] LAYER_STATUS[$LAYER_STATUS]",__LINE__);
        $img=null;
        $color=null;

        if(isset($ERR[$check_status])){$img="<div class='label label-danger' style='display:block;padding:5px'>$check_status</div>";$color="#D20C0C";}


        if($status=="MAINT"){
            $color="#F8AC59";
            $img="<div class='label label-warning' style='display:block;padding:5px'>{maintenance}</div>";
            $button="<button class='btn btn-w-m btn-warning' type='button' OnClick=\"Loadjs('$page?start-js=$ID')\">{start}</button>";
            if(!$users->AsProxyMonitor){$button="<button class='btn btn-w-m btn-default' type='button'>{start}</button>";}

        }

        if(preg_match("#DOWN#", $status)){
            $img="<div class='label label-danger' style='display:block;padding:5px'>{stopped}</div>";
            $MAIN[$ID]["STOPPED"]=true;
            $color="#D20C0C";
            $button="<button class='btn btn-w-m btn-danger' type='button' OnClick=\"Loadjs('$page?start-js=$ID')\">{start}</button>";
            if(!$users->AsProxyMonitor){$button="<button class='btn btn-w-m btn-default' type='button'>{start}</button>";}

        }

        if(preg_match("#UP#", $status)){
            $img="<div class='label label-primary' style='display:block;padding:5px'>{running}</div>";
            $button="<button class='btn btn-w-m btn-primary' type='button' OnClick=\"Loadjs('$page?stop-js=$ID')\">{stop}</button>";
            if(!$users->AsProxyMonitor){$button="<button class='btn btn-w-m btn-default' type='button'>{stop}</button>";}

        }

        if(preg_match("#no check#", $status)){
            $img="<div class='label label-warning' style='display:block;padding:5px'>{running}</div>";
            $button="<button class='btn btn-w-m btn-primary' type='button' OnClick=\"Loadjs('$page?stop-js=$ID')\">{stop}</button>";
            if(!$users->AsProxyMonitor){$button="<button class='btn btn-w-m btn-default' type='button'>{stop}</button>";}
        }


        $req_tot=FormatNumber($stot);
        $MAIN[$ID]["NAME"]="$pxname/$svname";
        $MAIN[$ID]["LAYER_STATUS"]=$LAYER_STATUS;
        $MAIN[$ID]["IMG"]=$img;
        $MAIN[$ID]["BUTTON"]=$button;
        $MAIN[$ID]["COLOR"]=$color;
        $MAIN[$ID]["BIN"]=intval($bin);
        $MAIN[$ID]["BOUT"]=intval($bout);
        $MAIN[$ID]["REQS"]=intval($req_tot);





    }
    $GLOBALS["LOAD_STATUS"]=$MAIN;
    return $MAIN;
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
function RefreshTableRows(){
    $page=CurrentPageName();
    $function="";
    if(isset($_GET["function"])) {
        $function = $_GET["function"];
    }
    td_prepare();
    header("content-type: application/x-javascript");
    $f[]="function RefreshTableRows(){";
    $f[]="\tif (!document.getElementById('table-haproxy-backends') ){";
    $f[]="\t\treturn;";
    $f[]="\t}";
    $f[]="\tvar i=0;";
    $f[]="\tlet squares = [];";
    $f[]="\t$(\"span[id^='STATUS-TEXT-']\").each(function() {";
    $f[]="\t\tvar id = $(this).attr('id');";
    $f[]="\t\tvar number = id.replace(/[^0-9]/g, '');";
    $f[]="\t\tif (!document.getElementById('table-haproxy-backends') ){";
    $f[]="\t\t\treturn;";
    $f[]="\t\t}";
    $f[]="\tsquares.push(number);";
    $f[]="\t});";
    $f[]="\tlet ids = squares.join(',');";
    $f[]="\tLoadjs('$page?function=$function&td-row-implode='+ids);";
    $f[]="}";
    $f[]="\tRefreshTableRows();";

    echo @implode("\n", $f);
}
function __LocalClusterInfo():array{
    if(isset($GLOBALS["__LocalClusterInfo"])){
        return $GLOBALS["__LocalClusterInfo"];
    }
    $GLOBALS["__LocalClusterInfo"]["ClusterVersion"]="";

    $nodes_status=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/nodes/status"),true);
    if(!isset($nodes_status["MasterID"])){
        VERBOSE("No MasterID",__LINE__);
        $GLOBALS["__LocalClusterInfo"]["MasterID"]=0;
        return $GLOBALS["__LocalClusterInfo"];
    }
    $master_id=intval($nodes_status["MasterID"]);
    if($master_id==0){
        VERBOSE("MasterID == 0",__LINE__);
        $GLOBALS["__LocalClusterInfo"]["MasterID"]=0;
        return $GLOBALS["__LocalClusterInfo"];
    }

    $ClusterVersion=$nodes_status["ClusterVersion"];
    if(strlen($ClusterVersion)<5) {
        VERBOSE("$ClusterVersion len <5",__LINE__);
        $GLOBALS["__LocalClusterInfo"]["MasterID"]=0;
        $GLOBALS["__LocalClusterInfo"]["ClusterVersion"]="";
        return $GLOBALS["__LocalClusterInfo"];
    }
    VERBOSE("$master_id --> $ClusterVersion",__LINE__);
    $GLOBALS["__LocalClusterInfo"]["MasterID"]=$master_id;
    $GLOBALS["__LocalClusterInfo"]["ClusterVersion"]=$ClusterVersion;
    return $GLOBALS["__LocalClusterInfo"];
}

function __td_button_master():string{
    $tpl=new template_admin();
    $nodes_status= __LocalClusterInfo();
    $f[]="$('#masterPackageStatus-btn').removeClass();";
    $f[]="$('#masterPackageStatus-btn').removeAttr('onclick');";
    $Inactive=base64_encode($tpl->_ENGINE_parse_body('{cluster_package} {inactive}'));

    $master_id=intval($GLOBALS["__LocalClusterInfo"]["MasterID"]);
    if($master_id==0){
        $f[]="$('#masterPackageStatus-btn').addClass('btn abt abt-i abt-o abt-sm');";
        $f[]="$('#masterPackageStatus-text').html(base64_decode('$Inactive'))";
        return @implode("\n",$f);
    }

    $page=CurrentPageName();
    $tpl=new template_admin();

    $ClusterVersion=$GLOBALS["__LocalClusterInfo"]["ClusterVersion"];
    if(strlen($ClusterVersion)<5) {
        $f[]="$('#masterPackageStatus-btn').addClass('btn abt abt-i abt-o abt-sm');";
        $text ="{cluster_package} ({none})";
        $bs64=base64_encode($tpl->_ENGINE_parse_body($text));
        $f[]="$('#masterPackageStatus-text').html(base64_decode('$bs64'))";
        return @implode("\n",$f);
    }

    $f[]="$('#masterPackageStatus-btn').addClass('btn abt abt-i abt-c3 abt-sm');";
    $f[]="$('#masterPackageStatus-btn').prop('disabled',false);";
    $text ="{cluster_package} $ClusterVersion";
    $bs64=base64_encode($tpl->_ENGINE_parse_body($text));
    $f[]="$('#masterPackageStatus-text').html(base64_decode('$bs64'))";
    $f[]="$('#masterPackageStatus-btn').on('click', function () {";
    $f[]=" Loadjs('$page?masterPackageStatus-js=yes');";
     $f[]="});";
    return @implode("\n",$f);
}
function td_rows():bool{
    $f=array();
    $ids=explode(",",$_GET["td-row-implode"]);
    foreach ($ids as $id){
       $ss[$id]=true;
    }
    $startA1 = microtime(true);
    foreach ($ss as $id=>$none){
        $start = microtime(true);
        $f[]=td_row(intval($id));
        ExecTAtime($start,"Server id $id",__LINE__);
    }

    $f[]=__td_button_master();
    //

    ExecTAtime($startA1,"<H3>FINAL</H3>",__LINE__);
    header("content-type: application/x-javascript");
    echo @implode("\n", $f);
    return true;
}

function td_row($ReturnID=0):string{
    $start = microtime(true);
    $page=CurrentPageName();
    $function="";
    if(isset($_GET["function"])) {
        $function = $_GET["function"];
    }
    if ($ReturnID==0){
        if(!isset($_GET["td-row"])){
            echo "// No ID returned";
            return "";
        }
        $ID=intval($_GET["td-row"]);
    }
    if($ReturnID>0){
        $ID=$ReturnID;
    }

    if($ID==0){
        return "";
    }
    $tpl=new template_admin();
    $ligne=hacluster_db_get_backend($ID);
    VERBOSE("ligne=".count($ligne),__LINE__);
    if(count($ligne)==0){
        VERBOSE("No inside database ---------------- GHOST #$ID");
        $jsClass=$_SESSION["BACKENDS_STATS"]["proxy$ID"];
        $ligne["ID"]=$ID;
        $ligne["status"]=999;
        $ligne["enabled"]=1;
        $ligne["microproxy"]=0;
        $ligne["isMaster"]=0;
        $ligne["clusteragent"]=1;
        $ligne["certificateid"]=0;
        $ligne["ActiveDirectoryEmergency"]=0;
        $ligne["orderqueue"]=0;
        $ligne["replicmaster"]=0;
        $ligne["proxyversion"]="0.0";
        $ligne["articarest"]="0.0";
        $ligne["listen_ip"]=$jsClass->srv_addr;
        $ligne["listen_port"]=$jsClass->srv_port;
        $ligne["realname"]=$jsClass->srv_addr;
        $ligne["backendname"]=$jsClass->srv_addr;
        $ligne["isDisconnected"]=0;
        $ligne["hostname"]="{unknown}-$ID";
    }



    list($btnClass,$status_text)=td_status_text($ligne);
    $triangle=td_status_warningDB($ligne);
    $status_text=base64_encode($tpl->_ENGINE_parse_body($status_text).$triangle);
    $status2_text=td_status2($ligne);
    $button=td_button($ligne);
    $bweight=td_bweight($ligne);
    $cnx=td_CNX($ligne);
    list($IN,$OUT)=td_inout($ligne);

    $HaClusterServeDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterServeDNS"));
    if($HaClusterServeDNS==1){
        $DnsdistJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/backends/status"));
        $ligne["HaClusterServeDNS"]=$DnsdistJson;
    }

    //STATUS-LOAD-$ID STATUS-RQS-$ID STATUS-CLI-$ID  //STATUS-HACNX-$ID
    list($ClientLoad,$requests,$NumberoFClients)=td_requests($ligne);
    $interface=base64_encode($tpl->_ENGINE_parse_body(td_interface($ligne)));
    $articaversion=base64_encode($tpl->_ENGINE_parse_body(td_version($ligne)));
    $tdhostname=base64_encode($tpl->_ENGINE_parse_body(td_hostname($ligne)));
    $tdHaCNX=base64_encode($tpl->_ENGINE_parse_body(td_hacnx($ligne)));
    $sessions=td_sessions($ligne);
    $weight=base64_encode($tpl->_ENGINE_parse_body($bweight));


   if(!$ReturnID==0){ header("content-type: application/x-javascript");}
    $f[]="function NodeRow$ID(){";
    $f[]="\tif (!document.getElementById('STATUS-TEXT-$ID') ){";
    $f[]="\t\treturn;";
    $f[]="\t}";

    $array[]="TEXT";
    $array[]="HACNX";
    $array[]="BTN";
    $array[]="WEIGHT";
    $array[]="CNX";
    $array[]="IN";
    $array[]="OUT";
    $array[]="LOAD";
    $array[]="RQS";
    $array[]="CLI";
    $array[]="INTERFACE";
    $array[]="VER";
    $array[]="MODE";


    foreach($array as $key){
        $f[]="if(!document.getElementById('STATUS-$key-$ID') ){";
        $f[]="//alert('STATUS-$key-$ID not found')";
        $f[]="}";
    }

    $f[]="document.getElementById('STATUS-TEXT-$ID').innerHTML=base64_decode('$status_text');";
    if(strlen($status2_text)>5) {
        $status2=base64_encode($tpl->_ENGINE_parse_body($status2_text));
        $f[] = "document.getElementById('STATUS-2-$ID').innerHTML=base64_decode('$status2');";
    }
    if(strlen($button)>5) {
        $btnEnc=base64_encode($tpl->_ENGINE_parse_body($button));
        $f[] = "document.getElementById('STATUS-BTN-$ID').innerHTML=base64_decode('$btnEnc');";
    }
    $f[] = "document.getElementById('STATUS-WEIGHT-$ID').innerHTML=base64_decode('$weight');";
    $f[] = "document.getElementById('STATUS-CNX-$ID').innerHTML='$cnx';";

    if(strlen($IN)>0) {
        $f[] = "document.getElementById('STATUS-IN-$ID').innerHTML='$IN';";
        $f[] = "document.getElementById('STATUS-OUT-$ID').innerHTML='$OUT';";
    }
    $f[] = "document.getElementById('STATUS-VER-$ID').innerHTML=base64_decode('$articaversion');";
    $f[] = "document.getElementById('STATUS-LOAD-$ID').innerHTML='$ClientLoad';";
    $f[] = "document.getElementById('STATUS-RQS-$ID').innerHTML='$requests';";
    $f[] = "document.getElementById('STATUS-SESS-$ID').innerHTML='$sessions';";
    $f[] = "document.getElementById('STATUS-HACNX-$ID').innerHTML=base64_decode('$tdHaCNX');";
    $f[] = "document.getElementById('STATUS-CLI-$ID').innerHTML='$NumberoFClients';";
    $f[] = "document.getElementById('STATUS-INTERFACE-$ID').innerHTML=base64_decode('$interface');";
    $f[] = "document.getElementById('STATUS-HOST-$ID').innerHTML=base64_decode('$tdhostname');";

    $f[]="if(document.getElementById('STATUS-INTERFACE2-$ID') ){";
    $f[] = "\tdocument.getElementById('STATUS-INTERFACE2-$ID').innerHTML=base64_decode('$interface');";
    $f[] = "}";

    // Le bouton "Action" porte l'etat du backend par sa couleur (button_dropdown_table) :
    // vert actif, orange en alerte, rouge en erreur, blanc inactif.
    $btnTones=array("btn-primary"=>"abt-s","btn-warning"=>"abt-c4","btn-danger"=>"abt-x");
    $btnTone=isset($btnTones[$btnClass])?$btnTones[$btnClass]:"abt-o";
    $f[]="if(document.getElementById('BTN-ACTION-LIST-$ID') ){";
    $f[] = "\t$('#BTN-ACTION-LIST-$ID').removeClass('abt-s abt-c2 abt-c3 abt-c4 abt-c5 abt-x abt-o').addClass('$btnTone');";
    $f[] = "}";

    $LoadLines=td_peity($ligne);
    if(strlen($LoadLines)>3){
        $f[]="\tif(document.getElementById('haloadT-$ID')){";
        $f[]="\t\t\$graph = $(\"#haloadT-$ID\");";
        $f[]="\t\t\$graph.text('$LoadLines');";
        $f[]="\t\t\$graph.peity(\"line\",{$GLOBALS["PEITYCONF"]});";
        $f[]="\t}";
    }

    $f[]="}";
    $f[]="NodeRow$ID();";
    if($ReturnID>0){
        return implode("\n", $f);
    }
    echo @implode("\n", $f);
    ExecTAtime($start,"",__LINE__);
    return "";
}

function td_peity($ligne):string{

    $ID=$ligne["ID"];
    $Metrics=peityLoads($ID);
    if(count($Metrics)>0){
        $LoadLines=$Metrics["LOADLINE"];
        if(strlen($LoadLines)>2) {
            return $LoadLines;
        }
    }
    return "";
}
function td_interface($ligne):string{
    $listen_ip=$ligne["listen_ip"];
    $listen_port=$ligne["listen_port"];
    $hostname=$ligne["hostname"];

    if(strpos($hostname,".")>0){
        $tb=explode(".",$hostname);
        $hostname=$tb[0];
    }

    $BackenEnabled=intval($ligne["enabled"]);

    if ($BackenEnabled==0){
        $text="<span style='color:#CCC7C7FF'>$hostname&nbsp;($listen_ip:$listen_port)</span>";
        return $text;

    }
    $isDisconnected=intval($ligne["isDisconnected"]);
    if($isDisconnected==1){
        $text="<span style='color:#CCC7C7FF'>$hostname&nbsp;($listen_ip:$listen_port)</span>";
        return $text;
    }
    $text="<strong>$hostname</strong>&nbsp;($listen_ip:$listen_port)";
    return $text;



}
function td_clients($ligne):string{
    $ID=$ligne["ID"];
    $tpl=new template_admin();
    $Path="/usr/share/artica-postfix/ressources/logs/hacluster/$ID/report.json";
    if(!is_file($Path)){
        return "-";
    }
    $json=json_decode(file_get_contents($Path));
    if(!property_exists($json,"MetricsSessions")){
        return "-";
    }
    if(!property_exists($json->MetricsSessions,"numberofUsers")){
        return "-";
    }
    $numberofUsers=$json->MetricsSessions->numberofUsers;
    if($json->MetricsSessions->numberOfIPs>$numberofUsers){
        $numberofUsers=$json->MetricsSessions->numberOfIPs;
    }
    $f[]=$tpl->FormatNumber($numberofUsers);

    if(property_exists($json->MetricsSessions,"maxClients")){
        $f[]="/";
        $f[]=$tpl->FormatNumber($json->MetricsSessions->maxClients);
    }
    return @implode("",$f);
}
function td_sessions($ligne):string{
    $ID=$ligne["ID"];
    $tpl=new template_admin();
    $Path="/usr/share/artica-postfix/ressources/logs/hacluster/$ID/report.json";
    if(!is_file($Path)){
        return "-";
    }
    $json=json_decode(file_get_contents($Path),true);
    if(!isset($json["MetricsSessions"])){
        return "-";
    }
    return $tpl->FormatNumber($json["MetricsSessions"]["numberOfSessions"]);
}
function td_hacnx($ligne):string{

    $ID=$ligne["ID"];
    $Proxy="proxy$ID";

    foreach ($GLOBALS["backends"] as $NamePrx => $json0){
        if($NamePrx<>$Proxy){
            continue;
        }
        if(property_exists($json0,"metrics")){
            //var_dump($json0->metrics);
            return $json0->metrics->scur ."/".$json0->metrics->slim;
        }

    }
    return "0/0";
}

function td_requests($ligne){
    $ID=$ligne["ID"];
    $HaClusterGBConfig = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
    if(!$HaClusterGBConfig){$HaClusterGBConfig=array();}
    if(!isset($HaClusterGBConfig["HaClusterUseHaClient"])){
        $HaClusterGBConfig["HaClusterUseHaClient"]=0;
    }
    $HaClusterUseHaClient=intval($HaClusterGBConfig["HaClusterUseHaClient"]);
    $clusteragent=intval($ligne["clusteragent"]);
    $HaClusterClientLoad=0;
    $HaClusterClientCPUPerc=0;
    $resquests=0;
    $ClientLoad=0;
    $HaClusterClientRQs=0;
    $HaClusterClientCls="";
    $NumberoFClients=0;
    $tpl=new template_admin();

    if($HaClusterUseHaClient==1){
        $clusteragent=1;
    }


    $BackenEnabled=intval($ligne["enabled"]);

    if ($BackenEnabled==0){
        return array("-","-", "-");
    }
    $isDisconnected=intval($ligne["isDisconnected"]);
    if($isDisconnected==1){
        return array("-","-", "-");
    }

    if($clusteragent==1){
        $HaClusterUseHaClient_array = HaClusterUseHaClient_checks($ligne);
        if(isset($HaClusterUseHaClient_array["CurLoad"])) {
                if(!isset($HaClusterUseHaClient_array["cpupercent"])){$HaClusterUseHaClient_array["cpupercent"]=0;}
                $HaClusterClientLoad = $HaClusterUseHaClient_array["CurLoad"];
                if(is_numeric($HaClusterUseHaClient_array["cpupercent"])) {
                    $HaClusterUseHaClient_array["cpupercent"] = round($HaClusterUseHaClient_array["cpupercent"], 2);
                }
                $HaClusterClientCPUPerc = "&nbsp;Cpu:{$HaClusterUseHaClient_array["cpupercent"]}%";
                $HaClusterClientRQs = $tpl->FormatNumber($HaClusterUseHaClient_array["requests"]);
                $HaClusterClientCls = td_clients($ligne);
            }

    }
    $HTTPS="-";
    $Metrics=peityLoads($ID);
    if(count($Metrics)>0){
        $ClientLoad=$Metrics["LOAD"];
        $resquests=$Metrics["RQS"];
        if(isset($Metrics["CPU_AVG"])){
            $HaClusterClientCPUPerc=$Metrics["CPU_AVG"];
        }
        if(isset($Metrics["HTTPS"])) {
            $HTTPS = $Metrics["HTTPS"];
        }
    }
    if($HaClusterClientLoad<>null){
        $ClientLoad=$HaClusterClientLoad;
    }
    if($HaClusterClientRQs<>null){
        $resquests=$HaClusterClientRQs;
    }
    if($HaClusterClientCls<>null){
        $NumberoFClients=$HaClusterClientCls;
    }
    $curclients=getCurClient($ID);
    if(strlen($curclients)>2){
        $NumberoFClients=$curclients;
    }


    return array("$ClientLoad$HaClusterClientCPUPerc","$resquests - <strong>$HTTPS</strong>", $NumberoFClients);


}
