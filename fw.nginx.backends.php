<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// class.backendsla.inc — sla_rest()/sla_err()/kpi_value()/kpi_round()/
// bsla_state_label()/bsla_grade_label(), shared with fw.nginx.backends.sla.php
// and fw.nginx.backends.sla.statistics.php (spec §29, task 7): the BackendSLA
// state/availability/latency/security columns added to this table reuse the
// exact same daemon-published verdicts, never a locally invented threshold.
include_once(dirname(__FILE__)."/ressources/class.backendsla.inc");
$GLOBALS["HASHLB"][0]="Round Robin";
$GLOBALS["HASHLB"][1]="{least-connections}";
$GLOBALS["HASHLB"][2]="{strict-hashed-ip}";
$GLOBALS["HASHLB"][3]="Cookie";
$GLOBALS["HASHLB"][4]="{lax-hashed-ip}";
$GLOBALS["HASHLB"][5]="failover";

// Pentest 2026-11-04 : service, delete, id-js, serviceid, diagnostic-js, options-popup, top-buttons et md5 etaient recopies sans encodage dans la page (XSS).
// Ce sont des identifiants numeriques ; md5 est un identifiant DOM.
foreach(array("service","delete","id-js","serviceid","diagnostic-js","options-popup","top-buttons") as $k){
    if(isset($_GET[$k])){$_GET[$k]=intval($_GET[$k]);}
}
if(isset($_GET["md5"])){$_GET["md5"]=preg_replace('/[^A-Za-z0-9_-]/','',strval($_GET["md5"]));}
// Droits : lecture pour AsWebMaster/AsSystemWebMaster, toute ecriture pour AsSystemWebMaster seulement
// (aucun handler ne le verifiait, sauf relay_save).
$nbkUsers=new usersMenus();
if(!$nbkUsers->AsWebMaster && !$nbkUsers->AsSystemWebMaster){exit();}
if(!$nbkUsers->AsSystemWebMaster){
    if(count($_POST)>0){$t=new template_admin();echo $t->javascript_parse_text("{ERROR_NO_PRIVS2}");exit;}
    foreach(array("backend-move","backend-enable","ssl-switch","insecure-switch") as $k){
        if(isset($_GET[$k])){$t=new template_admin();header("content-type: application/x-javascript");echo "alert('".$t->javascript_parse_text("{ERROR_NO_PRIVS2}")."');";exit;}
    }
}
if(isset($_GET["backend-move"])){move();exit;}
if(isset($_GET["backend-enable"])){id_enable();exit;}
if(isset($_POST["lb_method"])){load_balancing_save();exit;}
// Before the "serviceid" catch-all below: the relay form posts serviceid too.
if(isset($_POST["relay-save"])){relay_save();exit;}
if(isset($_POST["BackendSave"])){id_save();exit;}
if(isset($_POST["serviceid"])){options_save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["id-js"])){id_js();exit;}
if(isset($_GET["id-popup"])){id_popup();exit;}
if(isset($_GET["relay-js"])){relay_js();exit;}
if(isset($_GET["relay-popup"])){relay_popup();exit;}
if(isset($_GET["relay-options"])){relay_options();exit;}
if(isset($_GET["diagnostic-js"])){diagnostic_js();exit;}
if(isset($_GET["backend-sla-run"])){backend_sla_run_js();exit;}
if(isset($_GET["delete"])){delete_js();exit;}
if(isset($_POST["BackEndsKeepAlive"])){backends_keepalive_save();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["ssl-switch"])){ssl_switch();exit;}
if(isset($_GET["insecure-switch"])){unsecure_switch();exit;}
if(isset($_GET["top-buttons"])){top_buttons();exit;}
if(isset($_GET["backends-keepalive-js"])){backends_keepalive_js();exit;}
if(isset($_GET["UseHaBackend-js"])){UseHaBackend_js();exit;}
if(isset($_GET["UseHaBackend-popup"])){UseHaBackend_popup();exit;}
if(isset($_POST["UseHaBackend"])){UseHaBackend_save();exit;}
if(isset($_GET["Shadow-js"])){Shadow_js();exit;}
if(isset($_GET["Shadow-popup"])){Shadow_popup();exit;}
if(isset($_POST["ShadowBackend"])){Shadow_save();exit;}
if(isset($_GET["NTLMPassthrough-js"])){NTLMPassthrough_js();exit;}


if(isset($_GET["backends-keepalive-popup"])){backends_keepalive_popup();exit;}
if(isset($_GET["options-js"])){options_js();exit;}
if(isset($_GET["options-popup"])){options_popup();exit;}
if(isset($_GET["load-balancing-js"])){load_balancing_js();exit;}
if(isset($_GET["load-balancing-popup"])){load_balancing_popup();exit;}

if(isset($_GET["active-health-check-none"])){active_health_check_none();exit;}
if(isset($_GET["active-health-check-js"])){active_health_check_js();exit;}
if(isset($_GET["active-health-check-popup"])){active_health_check_popup();exit;}
if(isset($_GET["active-health-check-popup-0"])){active_health_check_popup_0();exit;}
if(isset($_GET["active-health-check-popup-1"])){active_health_check_popup_1();exit;}
if(isset($_POST["active_health_check_serviceid"])){save_active_health_check();exit;}
table_start();


function table_start():bool{
	$page=CurrentPageName();
	$ID=$_GET["service"];
	echo "<div id='backends-reverse-$ID'></div>
	<script>LoadAjax('backends-reverse-$ID','$page?table=$ID');</script>";
    return true;
}
function delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["delete"];
	$md5=$_GET["md5"];
	$ligne=fetch_backend_by_id($ID);
	$label="#$ID";
	if(is_array($ligne) && trim((string)(isset($ligne["relay_uuid"])?$ligne["relay_uuid"]:""))!==""){
		$label="Docker Relay ".(string)$ligne["relay_uuid"];
	}elseif(is_array($ligne) && trim((string)(isset($ligne["local_socket"])?$ligne["local_socket"]:""))!==""){
		$label="unix:".(string)$ligne["local_socket"];
	}elseif(isset($ligne["hostname"])){
		$label=$ligne["hostname"].":".(isset($ligne["port"])?$ligne["port"]:"");
	}
	$sid=is_array($ligne) && isset($ligne["serviceid"])?intval($ligne["serviceid"]):intval($_GET["serviceid"]);
	$tpl->js_confirm_delete($label, "delete", "$ID","LoadAjaxSilent('backends-reverse-$sid','$page?table=$sid');if(typeof NgixSitesReload==='function'){NgixSitesReload();}");
}
function move(){
    $tpl=new template_admin();
    $ID=intval($_GET["backend-move"]);
    $serviceid=intval($_GET["serviceid"]);
    $direction=intval($_GET["acl-rule-dir"]);
    $result=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/backends/reorder", [
        "ID"        => $ID,
        "serviceid" => $serviceid,
        "direction" => $direction
    ]),true);
    if(!is_array($result) || !$result["Status"]){
        $err=is_array($result) ? $result["Error"] : "daemon unavailable";
        echo "alert('".$tpl->javascript_parse_text($err)."');";
        return;
    }
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('backends-reverse-$serviceid','$page?table=$serviceid');";
}
function  delete():bool{
	$ID=intval($_POST["delete"]);
	$result=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/backends/delete", ["ID"=>$ID]),true);
	if(!is_array($result) || !$result["Status"]){
	    $err=is_array($result) ? $result["Error"] : "daemon unavailable";
	    echo $err;return false;
	}
    return admin_tracks("Removed #$ID backend from reverse-proxy rule");
}

function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
}

// Unix socket path of backend $ID, "" for any other backend. Such a backend
// (Docker Store installer) has no host nor port: the classic form would break it.
function nbk_socket_backend($ID):string{
    if(intval($ID)<=0){return "";}
    $l=fetch_backend_by_id(intval($ID));
    return trim((string)(isset($l["local_socket"])?$l["local_socket"]:""));
}

function id_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["id-js"];
    $relayFetch=relay_fetch_row(intval($_GET["id-js"]));
    if(intval($_GET["id-js"])>0 && !$relayFetch["ok"]){
        // Unreadable row: never fall back to the classic form for it.
        echo "alert(".json_encode($tpl->javascript_parse_text("{dr_status_unreadable}: ".$relayFetch["err"])).");";
        return false;
    }
    $relayRow=$relayFetch["row"];
    if(is_array($relayRow) && trim((string)(isset($relayRow["relay_uuid"])?$relayRow["relay_uuid"]:""))!==""){
        $page=CurrentPageName();
        $sid=intval($relayRow["serviceid"]);
        echo "Loadjs('$page?relay-js=".intval($_GET["id-js"])."&serviceid=$sid');";
        return true;
    }
    if(nbk_socket_backend(intval($_GET["id-js"]))!==""){
        $tpl->js_error("{nbk_socket_not_editable}");
        return false;
    }
    $md5="";
	$serviceid=0;
    $hostname="";

    if(isset($_GET["serviceid"])){
        $serviceid=$_GET["serviceid"];
        $hostname=get_servicename($serviceid);
    }

    if($ID>0){
        $ligne=fetch_backend_by_id($ID);
        $serviceid=isset($ligne["serviceid"])?intval($ligne["serviceid"]):0;
        $hostname=get_servicename($serviceid);
    }

    if(isset($_GET["md5"])) {
        $md5 = $_GET["md5"];
    }
	$title="$hostname: {backend}: $ID";
	if($ID==0){$title="$hostname: {new_entry}";}
	return $tpl->js_dialog2($title, "$page?id-popup=$ID&serviceid=$serviceid&md5=$md5");
}
function diagnostic_js():bool{
	$tpl=new template_admin();
	$ID=$_GET["diagnostic-js"];
	$serviceid=intval($_GET["serviceid"]);
	$title=$tpl->_ENGINE_parse_body("{backend_diagnostic}");
	return $tpl->js_dialog8($title, "fw.nginx.backends.audit.php?serviceid=$serviceid&backendid=$ID", 750);
}

// backend_sla_run_js — manual deep probe (spec §29/§24.6), POST
// /backend-sla/run/{serviceid}/{backendid}. RunManual is synchronous and
// returns the samples it just collected (BackendSLA/api.go handleRunNow), so
// the feedback shown here is the actual measurement (skill §5b rule 1: "when
// the call is synchronous and returns a MEASUREMENT, show the measurement"),
// never a bare "done". js_ok()/js_error() only — never alert() (skill §5b):
// alert() would print the raw {token}/JSON literal instead of resolving it.
// The table itself is not refreshed here: a single probe does not change the
// bulk snapshot fetched once per table render (task-7-supplement.md §1), so
// the operator would see identical values and might think the click did
// nothing — the message says explicitly to reopen the statistics page to see
// it reflected there.
// backend_sla_run_rest — dedicated curl call for the manual deep probe, NOT
// sla_rest() (fix-round-1, M2). RunManual (BackendSLA/backendsla.go) bounds
// its own context to Policy.Timeout * (MaxDomainsPerDeepCycle+1) — 5s * 6 =
// 30s with the DEFAULT policy — which is EXACTLY sla_rest()'s hardcoded
// CURLOPT_TIMEOUT of 30. On the button's own worst case (a backend that does
// not respond at all — the reason the operator clicked it), every domain
// probe eats its full per-domain timeout, so curl and the daemon race to the
// same finish line with no margin for the scoring/RecordBatchWithSecurity/
// bolt-write tail after the last probe returns. curl loses that race just
// often enough to report "daemon unreachable" for a probe that actually
// SUCCEEDED at measuring the outage — the one moment the message matters
// most. Raised to 60s (comfortably past the 30s default-policy budget, still
// short of a custom Policy.Timeout up to MaxTimeout=60s, whose absolute
// worst case is 360s and no fixed client timeout can always outlast) AND the
// curl timeout itself is distinguished from every other failure (module
// disabled, daemon down, stale binary): CURLE_OPERATION_TIMEDOUT gets its
// own token instead of being folded into "unreachable". Scoped to this one
// call — sla_rest() in class.backendsla.inc is shared by the two other
// BackendSLA pages, whose reads are all fast; changing its default would
// widen the blast radius for no benefit there.
function backend_sla_run_rest($route){
    $ch=curl_init();
    curl_setopt($ch,CURLOPT_UNIX_SOCKET_PATH,"/run/reverse-proxy.sock");
    curl_setopt($ch,CURLOPT_URL,"http://localhost".$route);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_TIMEOUT,60);
    curl_setopt($ch,CURLOPT_POST,true);
    $raw=curl_exec($ch);
    $timedOut=(curl_errno($ch)===CURLE_OPERATION_TIMEDOUT);
    curl_close($ch);
    return array($raw,$timedOut);
}

function backend_sla_run_js():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["backend-sla-run"]);
    $serviceid=intval($_GET["serviceid"]);
    if($ID<=0 || $serviceid<=0){
        $tpl->js_error("{backend_sla_err_invalid_ids}");
        return false;
    }
    list($raw,$timedOut)=backend_sla_run_rest("/backend-sla/run/$serviceid/$ID");
    if($timedOut){
        // The daemon may well have finished the measurement after curl gave
        // up — never presented as "unreachable" (that would be a genuine
        // measurement rendered as an infrastructure failure, spec Pattern
        // #50's discipline extended to the client side of this call).
        $tpl->js_error("{backend_sla_probe_timeout}");
        return false;
    }
    $e=sla_err($raw);
    if($e!==""){
        $tpl->js_error($e);
        return false;
    }
    $decoded=json_decode((string)$raw);
    $lastOk=null;
    $lastMs=null;
    if(is_object($decoded) && isset($decoded->samples) && is_array($decoded->samples) && count($decoded->samples)>0){
        $last=$decoded->samples[count($decoded->samples)-1];
        if(is_object($last)){
            if(property_exists($last,"ok") && is_bool($last->ok)){ $lastOk=$last->ok; }
            if(isset($last->total_ms)){ $lastMs=intval($last->total_ms); }
        }
    }
    $token=($lastOk===false)?"{backend_sla_probe_failed}":"{backend_sla_probe_ok}";
    $msg=$tpl->_ENGINE_parse_body($token);
    if($lastMs!==null){ $msg.=" ($lastMs ms)"; }
    $tpl->js_ok($msg);
    return true;
}

function  backends_keepalive_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["backends-keepalive-js"]);
    $title="{keep_alive}: #$serviceid";
    return $tpl->js_dialog2($title, "$page?backends-keepalive-popup=$serviceid");
}
function UseHaBackend_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["UseHaBackend-js"]);
    $title="HaBackend: #$serviceid";
    return $tpl->js_dialog2($title, "$page?UseHaBackend-popup=$serviceid");
}

function options_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["options-js"]);
    $title="{options}: #$serviceid";
    return $tpl->js_dialog2($title, "$page?options-popup=$serviceid");
}

function active_health_check_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["active-health-check-js"]);
    $title="active {health} {check} {settings}: #$serviceid";
    return $tpl->js_dialog2($title, "$page?active-health-check-popup=$serviceid");
}
function active_health_check_none():bool{
    $tpl=new template_admin();
    return $tpl->js_error("{active_health_none}");
}

function active_health_check_popup_0():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $serviceid=intval($_GET["active-health-check-popup-0"]);
    $sock=new socksngix($serviceid);
    $ActiveHealthCheck=intval($sock->GET_INFO("ActiveHealthCheckEnabled"));
    $HealthCheckType=array();
    $HealthCheckType[0]="tcp";
    $HealthCheckType[1]="http";
    $HealthCheckType[2]="https";
    $HealthCheckType[3]="udp";
    $HealthCheckType[4]="gRPC";
    $HealthCheckTypeSelected=intval($sock->GET_INFO("HealthCheckTypeSelected"));
    $ActiveHealthCheckFalls=intval($sock->GET_INFO("ActiveHealthCheckFalls"));
    $ActiveHealthCheckRaises=intval($sock->GET_INFO("ActiveHealthCheckRaises"));
    $ActiveHealthCheckPersistent=intval($sock->GET_INFO("ActiveHealthCheckPersistent"));
    $ActiveHealthCheckInitialDown=intval($sock->GET_INFO("ActiveHealthCheckInitialDown"));
    $ActiveHealthCheckTimeout=intval($sock->GET_INFO("ActiveHealthCheckTimeout"));
    $ActiveHealthCheckInterval=intval($sock->GET_INFO("ActiveHealthCheckInterval"));
    $ActiveHealthCheckOverwritePort=intval($sock->GET_INFO("ActiveHealthCheckOverwritePort"));
    $ActiveHealthCheckEnableDNSDiscovery=intval($sock->GET_INFO("ActiveHealthCheckEnableDNSDiscovery"));
    $ActiveHealthCheckDNSDiscoveryDNS=trim($sock->GET_INFO("ActiveHealthCheckDNSDiscoveryDNS"));
    $ActiveHealthCheckDNSDiscoveryDNSType=intval($sock->GET_INFO("ActiveHealthCheckDNSDiscoveryDNSType"));
    $ActiveHealthCheckDNSDiscoveryDNSInterval=intval($sock->GET_INFO("ActiveHealthCheckDNSDiscoveryDNSInterval"));
    $ActiveHealthCheckDNSDiscoveryDNSResolver=trim($sock->GET_INFO("ActiveHealthCheckDNSDiscoveryDNSResolver"));
    $ActiveHealthCheckOverwriteInterface=trim($sock->GET_INFO("ActiveHealthCheckOverwriteInterface"));

    $ActiveHealthCheckEnableAdaptivePerformanceTracking=intval($sock->GET_INFO("ActiveHealthCheckEnableAdaptivePerformanceTracking"));
    $ActiveHealthCheckAdaptivePerformanceTrackingAutoWeightResponseTimeThreshold=intval($sock->GET_INFO("ActiveHealthCheckAdaptivePerformanceTrackingAutoWeightResponseTimeThreshold"));

    $ActiveHealthCheckAdaptivePerformanceRemoveThreshold=intval($sock->GET_INFO("ActiveHealthCheckAdaptivePerformanceRemoveThreshold"));

    $ActiveHealthCheckAdaptivePerformanceTrackingAutoWeightFactor=intval($sock->GET_INFO("ActiveHealthCheckAdaptivePerformanceTrackingAutoWeightFactor"));
    if($ActiveHealthCheckAdaptivePerformanceTrackingAutoWeightResponseTimeThreshold==0){
        $ActiveHealthCheckAdaptivePerformanceTrackingAutoWeightResponseTimeThreshold=1;
    }
    if($ActiveHealthCheckAdaptivePerformanceRemoveThreshold==0){
        $ActiveHealthCheckAdaptivePerformanceRemoveThreshold=5;
    }
    if($ActiveHealthCheckAdaptivePerformanceTrackingAutoWeightFactor==0){
        $ActiveHealthCheckAdaptivePerformanceTrackingAutoWeightFactor=2;
    }


    if( $ActiveHealthCheckDNSDiscoveryDNSInterval==0){ $ActiveHealthCheckDNSDiscoveryDNSInterval=30;}
    $dnsTypes=array();
    $dnsTypes[0]="A";
    $dnsTypes[1]="AAAA";
    $dnsTypes[2]="SRV";
    if($ActiveHealthCheckTimeout==0){$ActiveHealthCheckTimeout=3;}
    if($ActiveHealthCheckInterval==0){$ActiveHealthCheckInterval=5;}

    if($ActiveHealthCheckFalls==0){$ActiveHealthCheckFalls=3;}
    if($ActiveHealthCheckRaises==0){$ActiveHealthCheckRaises=2;}
    $lblMethods=array();
    $lblMethods[0]="{rr}";
    $lblMethods[1]="{weighted_rr}";
    $lblMethods[2]="{least-connections}";
    $lblMethods[3]="{ip_hash}";
    $lblMethods[4]="{uri_hash}";
    $lblMethods[5]="{generic_hash}";
    $lblMethods[6]="{random}";
    $lblMethods[7]="{adaptive_least_response}";
    $lblMethods[8]="{adaptive_rr}";
    $lblMethods[9]="{sticky_cookie}";
    $lblMethods[10]="{sticky_route}";
    $lblMethods[11]="{sticky_ip_hash}";
    $LBZone=intval($sock->GET_INFO("LBZone"));

    if($LBZone==0){
        $LBZone=64;
    }

    $zone["32"]="32k";
    $zone["64"]="64K";
    $zone["128"]="128K";
    $zone["256"]="256K";
    $zone["512"]="512K";
    $zone["1024"]="1024K";
    $ActiveHealthChekLBMethod=intval($sock->GET_INFO("ActiveHealthChekLBMethod"));
    //Active Health Checks
    $form[]=$tpl->field_section("{active_health_check_settings}","{active_health_check_explain}");
    $form[]=$tpl->field_hidden("active_health_check_serviceid",$serviceid);
    $form[]=$tpl->field_hidden("active_health_check_serviceid_popup",0);

    $form[]=$tpl->field_checkbox("ActiveHealthCheckEnabled","{enable} active {health} {check}",$ActiveHealthCheck,"HealthCheckTypeSelected,ActiveHealthCheckRaises,ActiveHealthCheckFalls,ActiveHealthCheckPersistent,ActiveHealthCheckInitialDown,ActiveHealthCheckTimeout,ActiveHealthCheckInterval,ActiveHealthCheckOverwritePort,ActiveHealthCheckEnableDNSDiscovery,ActiveHealthCheckDNSDiscoveryDNSType,ActiveHealthCheckDNSDiscoveryDNSInterval,ActiveHealthCheckOverwriteInterface,ActiveHealthCheckEnableAdaptivePerformanceTracking");
    $form[]=$tpl->field_array_hash( $zone,"LBZone","nonull:{zone}",$LBZone);
    $form[]=$tpl->field_array_hash( $lblMethods,"ActiveHealthChekLBMethod","nonull:{load-balancer} {method}",$ActiveHealthChekLBMethod);
    $form[]=$tpl->field_array_hash( $HealthCheckType,"HealthCheckTypeSelected","nonull:{active_health_check_type}",$HealthCheckTypeSelected);
    $form[]=$tpl->field_interfaces("ActiveHealthCheckOverwriteInterface","nooloopNone:{override_outgoing_interface}",$ActiveHealthCheckOverwriteInterface);
    $form[]=$tpl->field_numeric("ActiveHealthCheckTimeout","{timeout} ({seconds})",$ActiveHealthCheckTimeout,"{active_health_check_timeout_explain}");
    $form[]=$tpl->field_numeric("ActiveHealthCheckInterval","{interval} ({seconds})",$ActiveHealthCheckInterval,"{active_health_check_interval_explain}");
    $form[]=$tpl->field_numeric("ActiveHealthCheckRaises","{raise}",$ActiveHealthCheckRaises,"{raises_explain}");
    $form[]=$tpl->field_numeric("ActiveHealthCheckFalls","{fall}",$ActiveHealthCheckFalls,"{falls_explain}");
    $form[]=$tpl->field_checkbox("ActiveHealthCheckPersistent","{persistent}",$ActiveHealthCheckPersistent,null,"{persistent_explain}");
    $form[]=$tpl->field_checkbox("ActiveHealthCheckInitialDown","{mandatory}",$ActiveHealthCheckInitialDown,null,"{mandatory_explain}");
    $form[]=$tpl->field_numeric("ActiveHealthCheckOverwritePort","{override_port}",$ActiveHealthCheckOverwritePort,"{override_port_explain}");

    $form[]=$tpl->field_checkbox("ActiveHealthCheckEnableDNSDiscovery","{enable_dns_discovery}",$ActiveHealthCheckEnableDNSDiscovery,"ActiveHealthCheckDNSDiscoveryDNS,ActiveHealthCheckDNSDiscoveryDNSType,ActiveHealthCheckDNSDiscoveryDNSInterval,ActiveHealthCheckDNSDiscoveryDNSResolver","{enable_dns_discovery_explain}");
    $form[]=$tpl->field_text("ActiveHealthCheckDNSDiscoveryDNS","{custom_hostname}",$ActiveHealthCheckDNSDiscoveryDNS);
    $form[]=$tpl->field_array_hash($dnsTypes,"ActiveHealthCheckDNSDiscoveryDNSType","nonull:{type}",$ActiveHealthCheckDNSDiscoveryDNSType);
    $form[]=$tpl->field_numeric("ActiveHealthCheckDNSDiscoveryDNSInterval","{interval} ({seconds})",$ActiveHealthCheckDNSDiscoveryDNSInterval);
    $form[]=$tpl->field_text("ActiveHealthCheckDNSDiscoveryDNSResolver","{custom_dns}",$ActiveHealthCheckDNSDiscoveryDNSResolver);

    $form[]=$tpl->field_checkbox("ActiveHealthCheckEnableAdaptivePerformanceTracking","{enable_adaptive_performance_tracking}",$ActiveHealthCheckEnableAdaptivePerformanceTracking,"ActiveHealthCheckAdaptivePerformanceTrackingAutoWeightResponseTimeThreshold,ActiveHealthCheckAdaptivePerformanceTrackingAutoWeightFactor,ActiveHealthCheckAdaptivePerformanceRemoveThreshold","{enable_adaptive_performance_tracking_explain}");

    $form[]=$tpl->field_numeric("ActiveHealthCheckAdaptivePerformanceTrackingAutoWeightResponseTimeThreshold","{auto_weight_threshold}",$ActiveHealthCheckAdaptivePerformanceTrackingAutoWeightResponseTimeThreshold,"{auto_weight_threshold_explain}");

    $form[]=$tpl->field_numeric("ActiveHealthCheckAdaptivePerformanceTrackingAutoWeightFactor","{auto_weight_factor}",$ActiveHealthCheckAdaptivePerformanceTrackingAutoWeightFactor,"{auto_weight_factor_explain}");

    $form[]=$tpl->field_numeric("ActiveHealthCheckAdaptivePerformanceRemoveThreshold","{remove_threshold}",$ActiveHealthCheckAdaptivePerformanceRemoveThreshold,"{remove_threshold_explain}");



    //$jsafter="dialogInstance2.close();LoadAjax('top-buttons-backends-$serviceid','$page?top-buttons=$serviceid');";
    $jsafter="LoadAjaxSilent('active-health-check-popup','$page?active-health-check-popup-1=true&active_health_check_serviceid_popup_0=$serviceid');";
    echo $tpl->form_outside(null,$form,null,"{next}",$jsafter,"AsWebAdministrator");
    return true;
}
function active_health_check_popup_1():bool
{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $serviceid=intval($_GET["active_health_check_serviceid_popup_0"]);
    $sock=new socksngix($serviceid);
    $HealthCheckTypeSelected=intval($sock->GET_INFO("HealthCheckTypeSelected"));
    $ActiveHealthCheckLBMethod=intval($sock->GET_INFO("ActiveHealthChekLBMethod"));
    $ActiveHealthCheckIPHashConsistent=intval($sock->GET_INFO("ActiveHealthCheckIPHashConsistent"));
    $ActiveHealthCheckIPHashUseBinary=intval($sock->GET_INFO("ActiveHealthCheckIPHashUseBinary"));
    $ActiveHealthCheckURIHashConsistent=intval($sock->GET_INFO("ActiveHealthCheckURIHashConsistent"));
    $ActiveHealthCheckURIHashUseRequestURI=intval($sock->GET_INFO("ActiveHealthCheckURIHashUseRequestURI"));
    $ActiveHealthCheckGenericHashConsistent=intval($sock->GET_INFO("ActiveHealthCheckGenericHashConsistent"));
    //Sticky Cookie Parameters
    $ActiveHealthCheckStickyCookieName=trim($sock->GET_INFO("ActiveHealthCheckStickyCookieName"));

    $ActiveHealthCheckStickyCookieSet=intval($sock->GET_INFO("ActiveHealthCheckStickyCookieSet"));
    $ActiveHealthCheckStickyCookieTime=intval($sock->GET_INFO("ActiveHealthCheckStickyCookieTime"));
    if($ActiveHealthCheckStickyCookieTime==0){
        $ActiveHealthCheckStickyCookieTime=3600;
    }
    $ActiveHealthCheckStickyCookieDomain=trim($sock->GET_INFO("ActiveHealthCheckStickyCookieDomain"));
    $ActiveHealthCheckStickyCookiePath=trim($sock->GET_INFO("ActiveHealthCheckStickyCookiePath"));
    $ActiveHealthCheckStickyCookieHTTPOnly=intval($sock->GET_INFO("ActiveHealthCheckStickyCookieHTTPOnly"));
    $ActiveHealthCheckStickyCookieSecure=intval($sock->GET_INFO("ActiveHealthCheckStickyCookieSecure"));
    $sameSite=array();
    $sameSite[""]="{not_used}";
    $sameSite["None"]="{none}";
    $sameSite["Strict"]="Strict";
    $sameSite["Lax"]="Lax";
    $ActiveHealthCheckStickyCookieSameSite=trim($sock->GET_INFO("ActiveHealthCheckStickyCookieSameSite"));

    $ActiveHealthCheckStickyCookieFailoverBackup=intval($sock->GET_INFO("ActiveHealthCheckStickyCookieFailoverBackup"));
    $ActiveHealthCheckStickyCookieFailoverDown=intval($sock->GET_INFO("ActiveHealthCheckStickyCookieFailoverDown"));
    //Sticky Route Parameters
    $ActiveHealthCheckStickyRouteParam=trim($sock->GET_INFO("ActiveHealthCheckStickyRouteParam"));
    $ActiveHealthCheckStickyRouteHeader=trim($sock->GET_INFO("ActiveHealthCheckStickyRouteHeader"));

    $ActiveHealthCheckStickyRouteFailoverBackup=intval($sock->GET_INFO("ActiveHealthCheckStickyRouteFailoverBackup"));
    $ActiveHealthCheckStickyRouteFailoverDown=intval($sock->GET_INFO("ActiveHealthCheckStickyRouteFailoverDown"));
    //Sticky IP HASH Parameters
    $ActiveHealthCheckStickyIPHashFailoverBackup=intval($sock->GET_INFO("ActiveHealthCheckStickyIPHashFailoverBackup"));
    $ActiveHealthCheckStickyIPHashFailoverDown=intval($sock->GET_INFO("ActiveHealthCheckStickyIPHashFailoverDown"));
    $ActiveHealthCheckStickyIPHashConsistent=intval($sock->GET_INFO("ActiveHealthCheckStickyIPHashConsistent"));
    $ActiveHealthCheckStickyIPHashUseBinary=intval($sock->GET_INFO("ActiveHealthCheckStickyIPHashUseBinary"));
    //wrr
    $ActiveHealthCheckWRRStrict=intval($sock->GET_INFO("ActiveHealthCheckWRRStrict"));
    $ActiveHealthCheckWRRMaxRetries=intval($sock->GET_INFO("ActiveHealthCheckWRRMaxRetries"));
    if($ActiveHealthCheckWRRMaxRetries==0){$ActiveHealthCheckWRRMaxRetries=3;}
    //LeastCoon
    $ActiveHealthCheckLeastConnStrict=intval($sock->GET_INFO("ActiveHealthCheckLeastConnStrict"));
    $ActiveHealthCheckLeastConnMaxRetries=intval($sock->GET_INFO("ActiveHealthCheckLeastConnMaxRetries"));
    if($ActiveHealthCheckLeastConnMaxRetries==0){$ActiveHealthCheckLeastConnMaxRetries=3;}
    //ALR
    $ActiveHealthCheckALREWMAAlpha=floatval($sock->GET_INFO("ActiveHealthCheckALREWMAAlpha"));
    if($ActiveHealthCheckALREWMAAlpha==0){$ActiveHealthCheckALREWMAAlpha=0.3;}
    $ActiveHealthCheckALRDefaultRT=intval($sock->GET_INFO("ActiveHealthCheckALRDefaultRT"));
    if($ActiveHealthCheckALRDefaultRT==0){$ActiveHealthCheckALRDefaultRT=500;}
    $ActiveHealthCheckALRFailurePenalty=intval($sock->GET_INFO("ActiveHealthCheckALRFailurePenalty"));
    if($ActiveHealthCheckALRFailurePenalty==0){$ActiveHealthCheckALRFailurePenalty=5000;}
    $ActiveHealthCheckALRQueueFactor=floatval($sock->GET_INFO("ActiveHealthCheckALRQueueFactor"));
    if($ActiveHealthCheckALRQueueFactor==0){$ActiveHealthCheckALRQueueFactor=1.0;}
    $ActiveHealthCheckALRAgePenaltyMax=floatval($sock->GET_INFO("ActiveHealthCheckALRAgePenaltyMax"));
    if($ActiveHealthCheckALRAgePenaltyMax==0){$ActiveHealthCheckALRAgePenaltyMax=1.0;}
    $ActiveHealthCheckALRAgePenaltyWindow=intval($sock->GET_INFO("ActiveHealthCheckALRAgePenaltyWindow"));
    if($ActiveHealthCheckALRAgePenaltyWindow==0){$ActiveHealthCheckALRAgePenaltyWindow=300;}
    $ActiveHealthCheckALRTieThreshold=floatval($sock->GET_INFO("ActiveHealthCheckALRTieThreshold"));
    if($ActiveHealthCheckALRTieThreshold==0){$ActiveHealthCheckALRTieThreshold=0.01;}
    $ActiveHealthCheckALRDefaultRTJitter=floatval($sock->GET_INFO("ActiveHealthCheckALRDefaultRTJitter"));
    if($ActiveHealthCheckALRDefaultRTJitter==0){$ActiveHealthCheckALRDefaultRTJitter=0.1;}
    $ActiveHealthCheckALRLogFilteredServers=intval($sock->GET_INFO("ActiveHealthCheckALRLogFilteredServers"));
    $ActiveHealthCheckALRWarnMissingMetrics=intval($sock->GET_INFO("ActiveHealthCheckALRWarnMissingMetrics"));
    //ARR
    $ActiveHealthCheckARROutlierThreshold =floatval($sock->GET_INFO("ActiveHealthCheckARROutlierThreshold"));
    if($ActiveHealthCheckARROutlierThreshold==0){$ActiveHealthCheckARROutlierThreshold=0.01;}
    $ActiveHealthCheckEnableAdaptivePerformanceTracking=intval($sock->GET_INFO("ActiveHealthCheckEnableAdaptivePerformanceTracking"));
    $adaptivePerformanceTrackingTXT="";
    if($ActiveHealthCheckEnableAdaptivePerformanceTracking==1){
        $adaptivePerformanceTrackingTXT="{adaptivePerformanceTrackingTXT}";
    }
    $form[]=$tpl->field_hidden("active_health_check_serviceid",$serviceid);
    $form[]=$tpl->field_hidden("active_health_check_serviceid_popup",1);

    if ($HealthCheckTypeSelected==0) {
        //tcp
        $ActiveHealthCheckTCPSend=trim($sock->GET_INFO("ActiveHealthCheckTCPSend"));
        $ActiveHealthCheckTCPExpected=trim($sock->GET_INFO("ActiveHealthCheckTCPExpected"));
        $form[]=$tpl->field_section("{active_health_check_p_settings}","{tcp_settings} ({optional})");
        $form[]=$tpl->field_text("ActiveHealthCheckTCPSend","{tcp_send}",$ActiveHealthCheckTCPSend,false,"{tcp_send_explain}");
        $form[]=$tpl->field_text("AtiveHealthCheckTCPExpected","{tcp_expect}",$ActiveHealthCheckTCPExpected,false,"{tcp_expect_explain}");
    }
    if ($HealthCheckTypeSelected==1 || $HealthCheckTypeSelected==2) {
        //http
        $ActiveHealthCheckHTTPUri=trim($sock->GET_INFO("ActiveHealthCheckHTTPUri"));
        if($ActiveHealthCheckHTTPUri==""){$ActiveHealthCheckHTTPUri="/";}
        $ActiveHealthCheckHTTPMatchBody=trim($sock->GET_INFO("ActiveHealthCheckHTTPMatchBody"));
        $ActiveHealthCheckHTTPMatchHeader=trim($sock->GET_INFO("ActiveHealthCheckHTTPMatchHeader"));
        $ActiveHealthCheckHTTPHHeader=trim($sock->GET_INFO("ActiveHealthCheckHTTPHHeader"));
        $ActiveHealthCheckHTTPExpectedStatus=trim($sock->GET_INFO("ActiveHealthCheckHTTPExpectedStatus"));
        $form[]=$tpl->field_section("{active_health_check_p_settings}","{http_settings} ({optional})");
        $form[]=$tpl->field_text("ActiveHealthCheckHTTPUri","{http_uri_path}",$ActiveHealthCheckHTTPUri,true,"{http_uri_path_explain}");
        $form[]=$tpl->field_text("ActiveHealthCheckHTTPHHeader","{http_headers}",$ActiveHealthCheckHTTPHHeader,false,"{http_header_explain}");
        $form[]=$tpl->field_text("ActiveHealthCheckHTTPMatchHeader","{http_match_headers}",$ActiveHealthCheckHTTPMatchHeader,false,"{http_match_header_explain}");
        $form[]=$tpl->field_text("ActiveHealthCheckHTTPMatchBody","{http_match_body}",$ActiveHealthCheckHTTPMatchBody,false,"{http_match_body_explain}");
        $form[]=$tpl->field_text("ActiveHealthCheckHTTPExpectedStatus","{http_expected_status}",$ActiveHealthCheckHTTPExpectedStatus,false,"{http_expected_status_explain}");

    }
    if ($HealthCheckTypeSelected==3) {
        //udp
        $form[]=$tpl->field_section("{active_health_check_p_settings}","{udp_settings} ({optional})");
        $ActiveHealthCheckUDPSend=trim($sock->GET_INFO("ActiveHealthCheckUDPSend"));
        $ActiveHealthCheckUDPExpected=trim($sock->GET_INFO("ActiveHealthCheckUDPExpected"));
        $form[]=$tpl->field_text("ActiveHealthCheckUDPSend","{udp_send}",$ActiveHealthCheckUDPSend,false,"{udp_send_explain}");
        $form[]=$tpl->field_text("AtiveHealthCheckUDPExpected","{udp_expect}",$ActiveHealthCheckUDPExpected,false,"{udp_expect_explain}");
    }
    if ($HealthCheckTypeSelected==4) {
        //gRPC
        $ActiveHealthCheckgRPCService=trim($sock->GET_INFO("ActiveHealthCheckgRPCService"));
        $ActiveHealthCheckgRPCStatus=intval($sock->GET_INFO("ActiveHealthCheckgRPCStatus"));
        if($ActiveHealthCheckgRPCStatus==0){$ActiveHealthCheckgRPCStatus=12;}
        $form[]=$tpl->field_section("{active_health_check_p_settings}","{grpc_settings} ({optional})");
        $form[]=$tpl->field_text("ActiveHealthCheckgRPCService","{grpc_service}",$ActiveHealthCheckgRPCService,false,"{grpc_service_explain}");
        $form[]=$tpl->field_numeric("ActiveHealthCheckgRPCStatus","{grpc_status_code_expected}",$ActiveHealthCheckgRPCStatus,"{grpc_status_code_expected_explain}");
    }
    if($ActiveHealthCheckLBMethod==0){
        $form[]=$tpl->field_section("{lbl_settings}","{rr_no_p}");
    }
    if($ActiveHealthCheckLBMethod==1){
        $form[]=$tpl->field_section("{lbl_settings}","{wrr_p}{caution_parameters}",true);
        $form[]=$tpl->field_checkbox("ActiveHealthCheckWRRStrict","{wrr_strict}",$ActiveHealthCheckWRRStrict,"ActiveHealthCheckWRRMaxRetries","{wrr_strict_explain}");
        $form[]=$tpl->field_numeric("ActiveHealthCheckWRRMaxRetries","{wrr_max_retries}",$ActiveHealthCheckWRRMaxRetries,"{wrr_max_retries_explain}");


    }
    if($ActiveHealthCheckLBMethod==2){
        $form[]=$tpl->field_section("{lbl_settings}","{lc_p}{caution_parameters}",true);
        $form[]=$tpl->field_checkbox("ActiveHealthCheckLeastConnStrict","{wrr_strict}",$ActiveHealthCheckLeastConnStrict,"ActiveHealthCheckLeastConnMaxRetries","{wrr_strict_explain}");
        $form[]=$tpl->field_numeric("ActiveHealthCheckLeastConnMaxRetries","{wrr_max_retries}",$ActiveHealthCheckLeastConnMaxRetries,"{wrr_max_retries_explain}");


    }
    if($ActiveHealthCheckLBMethod==3){
        $form[]=$tpl->field_section("{lbl_settings}","{ip_hash_p}$adaptivePerformanceTrackingTXT");
        $form[]=$tpl->field_checkbox("ActiveHealthCheckIPHashConsistent","{consistent}",$ActiveHealthCheckIPHashConsistent,"","{consistent_explain}");
        $form[]=$tpl->field_checkbox("ActiveHealthCheckIPHashUseBinary","{use_binary}",$ActiveHealthCheckIPHashUseBinary);
    }
    if($ActiveHealthCheckLBMethod==4){
        $form[]=$tpl->field_section("{lbl_settings}","{uri_hash_p}$adaptivePerformanceTrackingTXT");
        $form[]=$tpl->field_checkbox("ActiveHealthCheckURIHashConsistent","{consistent}",$ActiveHealthCheckURIHashConsistent,"","{consistent_explain}");
        $form[]=$tpl->field_checkbox("ActiveHealthCheckURIHashUseRequestURI","{use_request_uri}",$ActiveHealthCheckURIHashUseRequestURI);

    }
    if($ActiveHealthCheckLBMethod==5){
        $form[]=$tpl->field_section("{lbl_settings}","{generic_hash_p}$adaptivePerformanceTrackingTXT");
        $form[]=$tpl->field_checkbox("ActiveHealthCheckGenericHashConsistent","{consistent}",$ActiveHealthCheckGenericHashConsistent,"","{consistent_explain}");
    }
    if($ActiveHealthCheckLBMethod==6){
        $form[]=$tpl->field_section("{lbl_settings}","{random_no_p}");
    }
    if($ActiveHealthCheckLBMethod==7){
        $form[]=$tpl->field_section("{lbl_settings}","{alr_p}{caution_parameters}",true);
        $form[]=$tpl->field_numeric("ActiveHealthCheckALREWMAAlpha","{ewma_alpha}",$ActiveHealthCheckALREWMAAlpha,"{ewma_alpha_explain}");
        $form[]=$tpl->field_numeric("ActiveHealthCheckALRDefaultRT","{default_rt_ms}",$ActiveHealthCheckALRDefaultRT,"{default_rt_ms_explain}");
        $form[]=$tpl->field_numeric("ActiveHealthCheckALRDefaultRTJitter","{default_rt_jitter}",$ActiveHealthCheckALRDefaultRTJitter,"{default_rt_jitter_explain}");
        $form[]=$tpl->field_numeric("ActiveHealthCheckALRFailurePenalty","{failure_penalty_ms}",$ActiveHealthCheckALRFailurePenalty,"{failure_penalty_ms_explain}");
        $form[]=$tpl->field_numeric("ActiveHealthCheckALRQueueFactor","{queue_factor}",$ActiveHealthCheckALRQueueFactor,"{queue_factor_explain}");
        $form[]=$tpl->field_numeric("ActiveHealthCheckALRAgePenaltyMax","{age_penalty_max}",$ActiveHealthCheckALRAgePenaltyMax,"{age_penalty_max_explain}");
        $form[]=$tpl->field_numeric("ActiveHealthCheckALRAgePenaltyWindow","{age_penalty_window}",$ActiveHealthCheckALRAgePenaltyWindow,"{age_penalty_window_explain}");
        $form[]=$tpl->field_numeric("ActiveHealthCheckALRTieThreshold","{tie_threshold}",$ActiveHealthCheckALRTieThreshold,"{tie_threshold_explain}");
        $form[]=$tpl->field_checkbox("ActiveHealthCheckALRLogFilteredServers","{log_filtered_servers}",$ActiveHealthCheckALRLogFilteredServers,"","{log_filtered_servers_explain}");
        $form[]=$tpl->field_checkbox("ActiveHealthCheckALRWarnMissingMetrics","{warn_missing_metrics}",$ActiveHealthCheckALRWarnMissingMetrics,"","{warn_missing_metrics_explain}");

    }
    if($ActiveHealthCheckLBMethod==8){
        $form[]=$tpl->field_section("{lbl_settings}","{arr_p}{caution_parameters}",true);
        $form[]=$tpl->field_numeric("ActiveHealthCheckARROutlierThreshold","{arr_outlier_threshold}",$ActiveHealthCheckARROutlierThreshold,"{arr_outlier_threshold_explain}");
    }
    if($ActiveHealthCheckLBMethod==9){
        $form[]=$tpl->field_section("{lbl_settings}","{sc_p}");
        $form[]=$tpl->field_text("ActiveHealthCheckStickyCookieName","{cookie_name}",$ActiveHealthCheckStickyCookieName,false,"{cookie_name_explain}");
        $form[]=$tpl->field_checkbox("ActiveHealthCheckStickyCookieSet","{set_cookie}",$ActiveHealthCheckStickyCookieSet,"ActiveHealthCheckStickyCookieTime,ActiveHealthCheckStickyCookieDomain,ActiveHealthCheckStickyCookiePath,ActiveHealthCheckStickyCookieHTTPOnly,ActiveHealthCheckStickyCookieSecure,ActiveHealthCheckStickyCookieSameSite","{set_cookie_explain}");
        $form[]=$tpl->field_numeric("ActiveHealthCheckStickyCookieTime","{cookie_time}",$ActiveHealthCheckStickyCookieTime,"{cookie_time_explain}");
        $form[]=$tpl->field_text("ActiveHealthCheckStickyCookieDomain","{cookie_domain}",$ActiveHealthCheckStickyCookieDomain,false,"{cookie_domain_explain}");
        $form[]=$tpl->field_text("ActiveHealthCheckStickyCookiePath","{cookie_path}",$ActiveHealthCheckStickyCookiePath,false,"{cookie_path_explain}");
        $form[]=$tpl->field_checkbox("ActiveHealthCheckStickyCookieHTTPOnly","{cookie_http_only}",$ActiveHealthCheckStickyCookieHTTPOnly,"","{cookie_http_only_explain}");
        $form[]=$tpl->field_checkbox("ActiveHealthCheckStickyCookieSecure","{cookie_secure}",$ActiveHealthCheckStickyCookieSecure,"","{cookie_secure_explain}");
        $form[]=$tpl->field_array_hash( $sameSite,"ActiveHealthCheckStickyCookieSameSite","nonull:{same_site}",$ActiveHealthCheckStickyCookieSameSite,false,"{same_site_explain}");
        $form[]=$tpl->field_checkbox("ActiveHealthCheckStickyCookieFailoverBackup","{cookie_backup}",$ActiveHealthCheckStickyCookieFailoverBackup,"","{cookie_backup_explain}");
        $form[]=$tpl->field_checkbox("ActiveHealthCheckStickyCookieFailoverDown","{cookie_down}",$ActiveHealthCheckStickyCookieFailoverDown,"","{cookie_down_explain}");
    }
    if($ActiveHealthCheckLBMethod==10){
        $form[]=$tpl->field_section("{lbl_settings}","{sr_p}");
        $form[]=$tpl->field_text("ActiveHealthCheckStickyRouteParam","{route_param}",$ActiveHealthCheckStickyRouteParam,false,"{route_param_explain}");
        $form[]=$tpl->field_text("ActiveHealthCheckStickyRouteHeader","{route_header}",$ActiveHealthCheckStickyRouteHeader,false,"{route_header_explain}");
        $form[]=$tpl->field_checkbox("ActiveHealthCheckStickyRouteFailoverBackup","{cookie_backup}",$ActiveHealthCheckStickyRouteFailoverBackup,"","{cookie_backup_explain}");
        $form[]=$tpl->field_checkbox("ActiveHealthCheckStickyRouteFailoverDown","{cookie_down}",$ActiveHealthCheckStickyRouteFailoverDown,"","{cookie_down_explain}");
    }
    if($ActiveHealthCheckLBMethod==11){
        $form[]=$tpl->field_section("{lbl_settings}","{sih_p}$adaptivePerformanceTrackingTXT");
        $form[]=$tpl->field_checkbox("ActiveHealthCheckStickyIPHashConsistent","{consistent}",$ActiveHealthCheckStickyIPHashConsistent);
        $form[]=$tpl->field_checkbox("ActiveHealthCheckStickyIPHashFailoverBackup","{cookie_backup}",$ActiveHealthCheckStickyIPHashFailoverBackup,"","{cookie_backup_explain}");
        $form[]=$tpl->field_checkbox("ActiveHealthCheckStickyIPHashFailoverDown","{cookie_down}",$ActiveHealthCheckStickyIPHashFailoverDown,"","{cookie_down_explain}");
        $form[]=$tpl->field_checkbox("ActiveHealthCheckStickyIPHashUseBinary","{use_binary}",$ActiveHealthCheckStickyIPHashUseBinary,"","{use_binary_explain}");
    }


    $tpl->form_add_button("{back}", "LoadAjax('active-health-check-popup','$page?active-health-check-popup-0=$serviceid');");
    $jsafter="dialogInstance2.close();LoadAjax('top-buttons-backends-$serviceid','$page?top-buttons=$serviceid');";
    echo $tpl->form_outside(null,$form,null,"{apply}",$jsafter,"AsWebAdministrator");
    return true;
}
function save_active_health_check():bool
{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $serviceid=$_POST["active_health_check_serviceid"];
    $sock=new socksngix($serviceid);
    if(intval($_POST["active_health_check_serviceid_popup"])==0){

        if(intval($_POST["ActiveHealthCheckEnabled"])==0){
            $sock->SET_INFO("ActiveHealthCheckEnabled",$_POST["ActiveHealthCheckEnabled"]);
            $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");

            echo (new template_admin())->post_error("".$tpl->javascript_parse_text("{active_health_check_disable}"));
        }
        if(intval($_POST["ActiveHealthCheckEnableAdaptivePerformanceTracking"])==1){
            if(intval($_POST["ActiveHealthCheckAdaptivePerformanceTrackingAutoWeightResponseTimeThreshold"])>=intval($_POST["ActiveHealthCheckAdaptivePerformanceRemoveThreshold"])){
                echo (new template_admin())->post_error("".$tpl->javascript_parse_text("{auto_weight_greater_remove}"));
            }
        }

        foreach ($_POST as $key => $value) {
            if($key=="active_health_check_serviceid"){continue;}
            if($key=="active_health_check_serviceid_popup"){continue;}
            $sock->SET_INFO($key,$value);
        }


        return true;
    }

    foreach ($_POST as $key => $value) {
        $sock->SET_INFO($key,$value);
    }

    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Save Balancing method for service #$serviceid ({$_POST["lb_method"]})");
}



function active_health_check_popup():bool
{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $serviceid=intval($_GET["active-health-check-popup"]);

    $html[]="<div id='active-health-check-popup'></div>";
    $html[]="<script>LoadAjaxSilent('active-health-check-popup','$page?active-health-check-popup-0=$serviceid');</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function load_balancing_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["load-balancing-js"]);
    $title="{lb_settings}: #$serviceid";
    return $tpl->js_dialog2($title, "$page?load-balancing-popup=$serviceid",650);
}
function load_balancing_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $serviceid=intval($_GET["load-balancing-popup"]);
    $sock=new socksngix($serviceid);
    $lb_method=intval($sock->GET_INFO("lb_method"));
    $LBCookiesTime=intval($sock->GET_INFO("LBCookiesTime"));
    $LBZone=intval($sock->GET_INFO("LBZone"));
    $UseHaBackend=intval($sock->GET_INFO("UseHaBackend"));
    if($LBCookiesTime<30){
        $LBCookiesTime=3600;
    }
    if($LBZone==0){
        $LBZone=64;
    }

    $zone["32"]="32k";
    $zone["64"]="64K";
    $zone["128"]="128K";
    $zone["256"]="256K";
    $zone["512"]="512K";
    $zone["1024"]="1024K";
    if($UseHaBackend==1){
        $GLOBALS["HASHLB"][5]="failover";
    }

    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_array_hash( $GLOBALS["HASHLB"],"lb_method","nonull:{method}",$lb_method);
    $form[]=$tpl->field_array_hash( $zone,"LBZone","nonull:{zone}",$LBZone);
    $form[]=$tpl->field_numeric("LBCookiesTime","Cookie ({seconds})",$LBCookiesTime);
    $jsafter="dialogInstance2.close();LoadAjax('top-buttons-backends-$serviceid','$page?top-buttons=$serviceid');";
    echo $tpl->form_outside(null,$form,null,"{apply}",$jsafter,"AsWebAdministrator");
    return true;
}
function load_balancing_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $serviceid=$_POST["serviceid"];
    $sock=new socksngix($serviceid);
    $sock->SET_INFO("lb_method",$_POST["lb_method"]);
    $sock->SET_INFO("LBZone",$_POST["LBZone"]);
    $sock->SET_INFO("LBCookiesTime",$_POST["LBCookiesTime"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Save Balancing method for service #$serviceid ({$_POST["lb_method"]})");

}
function UseHaBackend_popup():bool{
    $serviceid=intval($_GET["UseHaBackend-popup"]);
    $sock=new socksngix($serviceid);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $UseHaBackend              = intval($sock->GET_INFO("UseHaBackend"));
    $jsafter="dialogInstance2.close();LoadAjax('top-buttons-backends-$serviceid','$page?top-buttons=$serviceid');";
    echo $tpl->BigCircleCheckbox("serviceid:$serviceid|UseHaBackend","HaBackend","{use_habackend_explain}",$UseHaBackend,$jsafter);
    return true;
}
function UseHaBackend_save():bool{
    $serviceid=intval($_POST["serviceid"]);
    $sock=new socksngix($serviceid);
    $sock->SET_INFO("UseHaBackend",intval($_POST["UseHaBackend"]));

    if($_POST["UseHaBackend"]==1){
        $sock->SET_INFO("BackEndsKeepAlive",intval($_POST["UseHaBackend"]));
    }


    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Save UseHaBackend method for service #$serviceid to {$_POST["UseHaBackend"]}");
}
function Shadow_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["Shadow-js"]);
    $title="Shadow backend: #$serviceid";
    return $tpl->js_dialog2($title, "$page?Shadow-popup=$serviceid");
}
function Shadow_popup():bool{
    $serviceid=intval($_GET["Shadow-popup"]);
    $sock=new socksngix($serviceid);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ShadowBackend = intval($sock->GET_INFO("ShadowBackend"));
    $jsafter="dialogInstance2.close();LoadAjax('top-buttons-backends-$serviceid','$page?top-buttons=$serviceid');";
    echo $tpl->BigCircleCheckbox("serviceid:$serviceid|ShadowBackend","{shadow_backend}","{shadow_backend_explain}",$ShadowBackend,$jsafter);
    return true;
}
function Shadow_save():bool{
    $serviceid=intval($_POST["serviceid"]);
    $sock=new socksngix($serviceid);
    $sock->SET_INFO("ShadowBackend",intval($_POST["ShadowBackend"]));
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Save ShadowBackend for service #$serviceid to {$_POST["ShadowBackend"]}");
}
function backends_keepalive_popup():bool{
    $serviceid=intval($_GET["backends-keepalive-popup"]);
    $sock=new socksngix($serviceid);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $KeepAlive              = intval($sock->GET_INFO("BackEndsKeepAlive"));
    $BackEndsKeepAliveRQS              = intval($sock->GET_INFO("BackEndsKeepAliveRQS"));
    $BackEndsKeepAliveHour              = intval($sock->GET_INFO("BackEndsKeepAliveHour"));
    $BackEndsKeepAliveTimeOut              = intval($sock->GET_INFO("BackEndsKeepAliveTimeOut"));

    if($BackEndsKeepAliveTimeOut==0){
        $BackEndsKeepAliveTimeOut=60;
    }
    if($BackEndsKeepAliveHour==0){
        $BackEndsKeepAliveHour=1;
    }
    if($BackEndsKeepAliveRQS==0){
        $BackEndsKeepAliveRQS=100;
    }


    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_numeric("BackEndsKeepAlive","{connections}",$KeepAlive);
    $form[]=$tpl->field_numeric("BackEndsKeepAliveRQS","Max {requests}",$BackEndsKeepAliveRQS);
    $form[]=$tpl->field_numeric("BackEndsKeepAliveHour","TTL ({hour})",$BackEndsKeepAliveHour);
    $form[]=$tpl->field_numeric("BackEndsKeepAliveTimeOut","{timeout} ({seconds})",$BackEndsKeepAliveTimeOut);



    $jsafter="dialogInstance2.close();LoadAjax('top-buttons-backends-$serviceid','$page?top-buttons=$serviceid');";
    echo $tpl->form_outside(null,$form,"{BackEndsKeepAlive}","{apply}",$jsafter,"AsWebAdministrator");
    return true;
}
function backends_keepalive_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $serviceid=$_POST["serviceid"];
    $sock=new socksngix($serviceid);
    $sock->SET_INFO("BackEndsKeepAlive",$_POST["BackEndsKeepAlive"]);

    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Save Backends Keep alive for service #$serviceid ({$_POST["BackEndsKeepAlive"]})");

}
function id_enable():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["backend-enable"]);
    $result=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/backends/enable", ["ID"=>$ID]),true);
    if(!is_array($result) || !$result["Status"]){
        $err=is_array($result) ? $result["Error"] : "daemon unavailable";
        $tpl->js_mysql_alert($err);return false;
    }
    $admint=($result["enabled"]==1) ? "Enabled" : "Disabled";
    $serviceid=intval($result["serviceid"]);
    header("content-type: application/x-javascript");
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    echo "LoadAjaxSilent('backends-reverse-$serviceid','$page?table=$serviceid');";
    return admin_tracks("Set backend #$ID to $admint");
}
function id_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["id-popup"]);
    if(nbk_socket_backend($ID)!==""){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{nbk_socket_not_editable}"));
        return false;
    }
    $serviceid=0;

    $Type=0;
    if(isset($_GET["serviceid"])) {
        $serviceid = intval($_GET["serviceid"]);
    }
	$md5=$_GET["md5"];
	$title="{new_item}";
    $ligne["port"]=80;

    if($serviceid>0) {
        $sock=new socksngix($serviceid);
        $ligne = $sock->GetCache();
        $Type = intval($ligne["type"]);
    }

	$btname="{add}";

	if($ID>0){
		$ligne=fetch_backend_by_id($ID);
		$btname="{apply}";
		$hostname0=isset($ligne["hostname"])?$ligne["hostname"]:"#$ID";
		$port0=isset($ligne["port"])?$ligne["port"]:"";
		$title="$hostname0:$port0";
        $sock=new socksngix($serviceid);
        $ligne2 = $sock->GetCache();
        $Type = intval($ligne2["type"]);
    }
	$js="dialogInstance2.close();LoadAjaxSilent('backends-reverse-$serviceid','$page?table=$serviceid');NgixSitesReload()";
	


    if($ID==0){
        if($Type==2){$ligne["port"]=80;}
        if($Type==13){$ligne["port"]=443;}
        if($Type==15){$ligne["port"]=53;}
    }
    if(intval($ligne["port"])==1){
        $ligne["port"]=80;
    }

    if(!isset($ligne["ssl"])){
        $ligne["ssl"]=0;
    }
    if(!isset($ligne["proxyproto"])){
        $ligne["proxyproto"]=0;
    }
    if(!isset($ligne["weight"])){
        $ligne["weight"]=1;
    }
    if(!isset($ligne["down"])){
        $ligne["down"]= 0;
    }
    if(!isset($ligne["backup"])){
        $ligne["backup"]= 0;
    }
    $weight = intval($ligne["weight"]);
    if ($weight==0){$weight=1;}

    $down= intval($ligne["down"]);
    $fail_timeout=10;

    if (!empty($ligne["fail_timeout"])){
        $fail_timeout= intval($ligne["fail_timeout"]);
    }

    $max_fails=1;
     if (!empty($ligne["max_fails"])){
         $max_fails= intval($ligne["max_fails"]);
     }

    if($ID==0){
        if($ligne["port"]<5){
            $ligne["port"]=rand(1024,64000);
        }
    }

    $sock=new socksngix($serviceid);
    $ligneH = $sock->GetCache();
    $hostname=$ligneH["hostname"];

	$form[]=$tpl->field_hidden("BackendSave", $ID);
	$form[]=$tpl->field_hidden("md5", $md5);
	$form[]=$tpl->field_hidden("serviceid", $serviceid);
	$form[]=$tpl->field_text("hostname", "{hostname}/{ipaddr}", $ligne["hostname"]);
	$form[]=$tpl->field_numeric("port","{port}",$ligne["port"]);
    if($Type==2){
        $form[]=$tpl->field_checkbox("CheckSsl","{UseSSL}",$ligne["ssl"]);
        $form[]=$tpl->field_checkbox("proxyproto","{proxy_protocol}",$ligne["proxyproto"]);
    }
    $form[]=$tpl->field_numeric("weight","{weight}",$weight);
    $form[]=$tpl->field_numeric("fail_timeout","{failure} {timeout}",$fail_timeout);
    $form[]=$tpl->field_numeric("max_fails","max {failure}",$max_fails);
    $form[]=$tpl->field_checkbox("backup","{backup}",intval($ligne["backup"]));
    $form[]=$tpl->field_checkbox("down","{down}",$down);
    $diag_js="Loadjs('$page?diagnostic-js=$ID&serviceid=$serviceid')";

    $tpl->form_add_button("{backend_diagnostic}",$diag_js);

    echo $tpl->form_outside("", $form,"",$btname,"$js","AsSystemWebMaster");
    return true;
	
}

function id_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=intval($_POST["BackendSave"]);
	$serviceid=intval($_POST['serviceid']);
    // Never rewrite a Docker socket backend through the host:port form.
    if(nbk_socket_backend($ID)!==""){
        admin_tracks("Refused to edit reverse-proxy backend #$ID: Unix socket of a Docker application");
        echo $tpl->post_error("{nbk_socket_not_editable}");
        return false;
    }

	if($serviceid==0){
        echo $tpl->post_error("Service ID missing or null");
        return false;
    }

	$hostname=trim($_POST["hostname"]);
    if(preg_match("#^http.*?:#i",$hostname)){
        $parse_url=parse_url($hostname);
        $hostname=$parse_url["host"];
    }
	$port=intval($_POST["port"]);
	$options=base64_encode(serialize($_POST));
    $get_servicename=get_servicename($serviceid);
    $Type=get_ServiceType($serviceid);
    $weight = intval($_POST["weight"]);
    if ($weight==0){$weight=1;}
    $backup=intval($_POST["backup"]);
    $down= intval($_POST["down"]);
    $fail_timeout=$_POST["fail_timeout"];
    $max_fails=intval($_POST["max_fails"]);
    $ssl=intval($_POST["CheckSsl"]);
    $proxyproto=intval($_POST["proxyproto"]);

    if($Type==13){$ssl=1;}
    if($Type==15){$ssl=0;}

    if($port==443 && $ssl==0){
        echo $tpl->post_error("Port 443 But no SSL checked ?");
        return false;
    }

    $result=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/backends/save", [
        "ID"           => $ID,
        "serviceid"    => $serviceid,
        "hostname"     => $hostname,
        "port"         => $port,
        "options"      => $options,
        "weight"       => $weight,
        "backup"       => $backup,
        "fail_timeout" => $fail_timeout,
        "max_fails"    => $max_fails,
        "down"         => $down,
        "ssl"          => $ssl,
        "proxyproto"   => $proxyproto
    ]),true);
    if(!is_array($result) || !$result["Status"]){
        $err=is_array($result) ? $result["Error"] : "daemon unavailable";
        echo $tpl->post_error($err);return false;
    }
    if($ID==0){
        return admin_tracks("Saving New Reverse-proxy Backend $hostname:$port for service $get_servicename");
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Modify Reverse-proxy Backend $hostname:$port for service $get_servicename");

}
function get_ServiceType($ID):int{
    $row=fetch_nginx_service($ID);
    return isset($row["type"]) ? intval($row["type"]) : 0;
}


function ssl_switch():bool{
    $page               = CurrentPageName();
    $serviceid          = intval($_GET["ssl-switch"]);
    $sock               = new socksngix($serviceid);
    $UseSSL             = $sock->GET_INFO("UseSSL");
    if($UseSSL==0){
        $sock->SET_INFO("UseSSL",1);
    }else{
        $sock->SET_INFO("UseSSL",0);
    }
    header("content-type: application/x-javascript");
    echo "LoadAjax('backends-reverse-$serviceid','$page?table=$serviceid');NgixSitesReload();";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return true;

}
function unsecure_switch():bool{
    $page               = CurrentPageName();
    $serviceid          = intval($_GET["insecure-switch"]);
    $sock               = new socksngix($serviceid);
    $DisableInsecure              = intval($sock->GET_INFO("DisableInsecure"));
    if($DisableInsecure==0){
        $sock->SET_INFO("DisableInsecure",1);
    }else{
        $sock->SET_INFO("DisableInsecure",0);
    }
    header("content-type: application/x-javascript");
    echo "LoadAjax('top-buttons-backends-$serviceid','$page?top-buttons=$serviceid');";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return true;
}
// out-of-scope: backend_move() was dead code (never dispatched) that used
// lib_sqlite directly on dnsdist_rules. Removed — the live reorder path is
// move() above which calls REST_API_NGINX_POST_JSON("/backends/reorder").


function UpStreamZonesStatus():array{
   // $tpl=new template_admin();
    $sock=new sockets();
    $ARR=array();
    $data=$sock->REST_API_NGINX("/reverse-proxy/upstreamzones");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
       return $ARR;
    }
    if(!$json->Status){
        return $ARR;

    }

    foreach ($json->Maps as $index=>$array){
        $ARR["MAPS"][$array->serviceid][$array->Host]=true;

    }

    foreach ($json->Zones as $zone=>$array){
        foreach ($array as $index=>$Servers){
            $ServerName=$Servers->server;
            $requestCounter=$Servers->requestCounter;
            $ARR[$ServerName]=$requestCounter;
        }
    }

    return $ARR;
}

// ---------- Onglet Backends : tuiles (maquette Stitch 2026-11-05) ----------
// Un modele (nbk_model) charge UNE fois par rendu : supervision BackendSLA (un seul appel
// groupe), upstreams nginx, Docker Relay et reglages du site. Chaque widget est une fonction.
// Aucun seuil n'est invente ici : l'etat, la disponibilite, la latence et la note viennent
// tels quels du demon (Pattern #50 : une mesure absente s'affiche « — », jamais 0).

function nbk_lb_label($sock,$lb_method,$UseHaBackend):string{
    if(!isset($GLOBALS["HASHLB"][$lb_method])){$lb_method=0;}
    if($lb_method==3){
        $t=intval($sock->GET_INFO("LBCookiesTime"));
        if($t<30){$t=3600;}
        return "Cookie ({$t}s)";
    }
    return $GLOBALS["HASHLB"][$lb_method];
}

// Reglages du site, tels que les boutons d'origine les lisaient (effets de bord compris).
function nbk_settings($serviceid):array{
    $sock=new socksngix($serviceid);
    $Type=get_ServiceType($serviceid);
    $s=array("type"=>$Type,"forcessl"=>false);
    if($Type==13){
        $sock->SET_INFO("UseSSL",1);
        $s["forcessl"]=true;
    }
    $s["dynamics"]=intval($sock->GET_INFO("ForwardServersDynamics"))==1;
    $s["keepalive"]=intval($sock->GET_INFO("BackEndsKeepAlive"))==1;
    $s["insecure"]=intval($sock->GET_INFO("DisableInsecure"))==0;
    $s["habackend"]=intval($sock->GET_INFO("UseHaBackend"))==1;
    $s["shadow"]=intval($sock->GET_INFO("ShadowBackend"))==1;
    $s["ntlm"]=intval($sock->GET_INFO("NTLMPassthrough"))==1;
    $s["lb"]=nbk_lb_label($sock,intval($sock->GET_INFO("lb_method")),$s["habackend"]);
    $daemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxActiveHealthCheckDaemonEnabled"))==1;
    if(!$daemon){$sock->SET_INFO("ActiveHealthCheckEnabled",0);}
    $s["ahc_daemon"]=$daemon;
    $s["ahc"]=intval($sock->GET_INFO("ActiveHealthCheckEnabled"))==1;
    $methods=array("{rr}","{weighted_rr}","{least-connections}","{ip_hash}","{uri_hash}","{generic_hash}","{random}",
        "{adaptive_least_response}","{adaptive_rr}","{sticky_cookie}","{sticky_route}","{sticky_ip_hash}");
    $m=intval($sock->GET_INFO("ActiveHealthChekLBMethod"));
    $s["ahc_method"]=isset($methods[$m])?$methods[$m]:$methods[0];
    $s["options"]=nbk_options_summary($sock,$Type);
    return $s;
}
function nbk_options_summary($sock,$type):string{
    if($type==15 || $type==5){return "";}
    $text=array();
    $resolvers=trim((string)$sock->GET_INFO("resolvers"));
    if(strlen($resolvers)>3){$text[]="DNS ".nbk_h($resolvers);}
    $HostHeader=(string)$sock->GET_INFO("HostHeader");
    if(strlen($HostHeader)>2){$text[]="{HostHeader} ".nbk_h($HostHeader);}
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHTTPSubModule"))==1){
        if(intval($sock->GET_INFO("HostHeaderReplace"))==1){$text[]="{HostHeaderReplace}";}
        if(intval($sock->GET_INFO("ProtoReplaceContent"))==1){$text[]="{ProtoReplaceContent}";}
    }
    if(strlen((string)$sock->GET_INFO("RemotePath"))>2){$text[]="{TargetRemotePath}";}
    if(strlen(trim((string)$sock->GET_INFO("default_page")))>2){$text[]="{nginx_default_page}";}
    $v=trim((string)$sock->GET_INFO("proxy_http_version"));
    if($v==""){$v="1.0";}
    $text[]="{proxy_http_version} ".nbk_h($v);
    return implode(" &middot; ",$text);
}

function nbk_h($s):string{
    $s=htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
    return str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),$s);
}
function nbk_t($k,$vars=array()):string{
    $tpl=new template_admin();
    $s=$tpl->_ENGINE_parse_body("{".$k."}");
    return str_replace(array_keys($vars),array_values($vars),$s);
}
function nbk_can_write():bool{
    $users=new usersMenus();
    return (bool)$users->AsSystemWebMaster;
}
// « 2h 14m » depuis un horodatage RFC3339 du demon ; "" pour une date absente ou nulle.
function nbk_since($ts):string{
    $t=strtotime((string)$ts);
    if($t===false || $t<=86400){return "";}
    $d=time()-$t;
    if($d<0){$d=0;}
    if($d>=86400){return intval($d/86400)."d ".intval(($d%86400)/3600)."h";}
    if($d>=3600){return intval($d/3600)."h ".intval(($d%3600)/60)."m";}
    return max(1,intval($d/60))."m";
}

function nbk_model($serviceid):array{
    $m=array("id"=>intval($serviceid),"can_write"=>nbk_can_write(),"settings"=>nbk_settings($serviceid),
        "sla_err"=>"","backends"=>array(),"dynamic"=>null,
        "counts"=>array("total"=>0,"disabled"=>0,"up"=>0,"degraded"=>0,"down"=>0));
    $sock=new socksngix($serviceid);
    $type=$m["settings"]["type"];

    $sla=array();
    $raw=sla_rest("GET","/backend-sla/backends/".intval($serviceid));
    $m["sla_err"]=sla_err($raw);
    if($m["sla_err"]===""){
        $dec=json_decode((string)$raw);
        if(is_object($dec) && isset($dec->backends) && is_array($dec->backends)){
            foreach($dec->backends as $b){
                if(is_object($b) && isset($b->key) && isset($b->key->backend_id)){$sla[intval($b->key->backend_id)]=$b;}
            }
        }
    }
    $up=UpStreamZonesStatus();
    $upOK=isset($up["MAPS"]);
    $inConf=($upOK && isset($up["MAPS"][$serviceid]))?$up["MAPS"][$serviceid]:array();
    $relayIdx=relay_status_index();

    $snih2="";
    if(intval($sock->GET_INFO("proxy_ssl_server_name"))==1){$snih2=trim((string)$sock->GET_INFO("proxy_ssl_name"));}

    if($m["settings"]["dynamics"]){
        $src=trim((string)$sock->GET_INFO("FSDynamicsSrc"));
        $dst=trim((string)$sock->GET_INFO("FSDynamicsDst"));
        if(intval($sock->GET_INFO("FSDynamicsExt"))==1){
            $src=preg_match("#\.([^.]*)$#",$src)?preg_replace("#\.[^.]*$#",".*",$src):$src.".*";
            $dst=preg_match("#\.([^.]*)$#",$dst)?preg_replace("#\.[^.]*$#",".*",$dst):$dst.".*";
        }
        $proto=intval($sock->GET_INFO("UseSSL"))==1?"https":"http";
        $m["dynamic"]=array("src"=>"*.".$src,"dst"=>"$proto://*.".$dst."/");
    }

    foreach(fetch_backends_for_service($serviceid) as $l){
        $b=array("id"=>intval($l["ID"]),"enabled"=>intval($l["enabled"])==1,"relay"=>false,
            "weight"=>max(1,intval($l["weight"])),"timeout"=>intval($l["fail_timeout"]),"maxfails"=>intval($l["max_fails"]),
            "proxyproto"=>intval($l["proxyproto"])==1,"backup"=>false,"mismatch"=>"","requests"=>null,"loaded"=>null,
            "state"=>"UNKNOWN","since"=>"","role"=>"","error"=>"","http"=>0,"avail"=>null,"lat"=>null,"p95"=>null,"grade"=>"","socket"=>"");
        if($b["timeout"]==0){$b["timeout"]=10;}
        if($b["maxfails"]==0){$b["maxfails"]=1;}
        $uuid=trim((string)(isset($l["relay_uuid"])?$l["relay_uuid"]:""));
        if($uuid!==""){
            $b["relay"]=true;
            $b["backup"]=intval($l["backup"])==1;
            $b["label"]="Docker Relay ".$uuid;
            $b["search"]="docker relay ".$uuid;
            $b["relay_missing"]=false;
            $u=relay_uuid_ok($uuid);
            if($relayIdx["ok"] && $u!=="" && isset($relayIdx["backends"][$u])){
                $rb=$relayIdx["backends"][$u];
                $ag=isset($relayIdx["agents"][(string)$rb["agent_uuid"]])?(string)$relayIdx["agents"][(string)$rb["agent_uuid"]]["name"]:(string)$rb["agent_uuid"];
                $b["label"]=$ag." / ".(string)$rb["name"];
                $b["search"]="docker relay ".$ag." ".(string)$rb["name"];
                $h=isset($rb["health"])&&is_array($rb["health"])?$rb["health"]:array();
                $st=isset($h["status"])?(string)$h["status"]:"UNKNOWN";
                $b["state"]=in_array($st,array("UP","DOWN","DEGRADED"))?$st:"UNKNOWN";
                $b["error"]=isset($h["last_error"])?(string)$h["last_error"]:"";
            }elseif($relayIdx["ok"] && $u!==""){
                $b["relay_missing"]=true;
            }
            if(intval($l["down"])==1){$b["relay_down"]=true;}
        }elseif(($sockp=trim((string)(isset($l["local_socket"])?$l["local_socket"]:"")))!==""){
            // Unix socket of a Docker application of this machine (Store installer):
            // no host nor port (it showed ":0"), managed by the installer.
            $b["socket"]=$sockp;
            $stack=preg_match('#^/srv/artica-docker/sockets/([a-z0-9][a-z0-9_-]{0,62})/#',$sockp,$sm)?$sm[1]:"";
            $b["label"]=nbk_js_text("ngxs2_local_docker_socket").($stack!==""?" · ".$stack:"");
            $b["search"]="docker socket unix ".$stack." ".$sockp;
            $b["proto"]="unix";
        }else{
            $host=(string)$l["hostname"];
            $port=intval($l["port"]);
            $ssl=intval($l["ssl"])==1;
            if(preg_match("#^https?://#i",$host)){
                $ssl=preg_match("#^https#i",$host)==1;
                $pu=parse_url($host);
                $host=isset($pu["host"])?$pu["host"]:$host;
            }
            $proto=$ssl?"https":"http";
            if($type==5){$proto="tcp";}
            $b["host"]=$host;$b["port"]=$port;$b["proto"]=$proto;
            $hostTxt=strpos($host,":")!==false?"[$host]":$host;
            $portTxt=":$port";
            if($proto=="https" && $port==443){$portTxt="";}
            if($proto=="http" && $port==80){$portTxt="";}
            $b["label"]=$proto=="tcp"?"$hostTxt:$port":"$proto://$hostTxt$portTxt";
            $b["search"]="$proto $host $port $host:$port";
            if($proto=="https" && $port==80){$b["mismatch"]=nbk_t("nbk_port_mismatch",array("%p"=>"80","%s"=>"HTTPS"));}
            if($proto=="http" && $port==443){$b["mismatch"]=nbk_t("nbk_port_mismatch",array("%p"=>"443","%s"=>"HTTP"));}
            $key="$host:$port";
            if($upOK){
                $b["loaded"]=isset($inConf[$key]);
                if(isset($up[$key]) && is_numeric($up[$key])){$b["requests"]=intval($up[$key]);}
            }
            $s=isset($sla[$b["id"]])?$sla[$b["id"]]:null;
            if($s!==null){
                $b["state"]=isset($s->state)?(string)$s->state:"UNKNOWN";
                $b["since"]=isset($s->since)?nbk_since($s->since):"";
                $b["role"]=isset($s->role)?(string)$s->role:"";
                $b["avail"]=isset($s->availability_24h)?kpi_round($s->availability_24h,1,100):null;
                $b["lat"]=isset($s->latency_current_ms)?$s->latency_current_ms:null;
                $b["p95"]=isset($s->latency_p95_ms)?$s->latency_p95_ms:null;
                $b["grade"]=isset($s->security_grade)?(string)$s->security_grade:"";
                $b["http"]=isset($s->last_http_status)?intval($s->last_http_status):0;
                $lastOK=isset($s->last_ok)?$s->last_ok:null;
                if(($lastOK===false || in_array($b["state"],array("DOWN","DEGRADED"))) && isset($s->last_error)){$b["error"]=(string)$s->last_error;}
            }
        }
        if($b["backup"] && $b["role"]==""){$b["role"]="backup";}
        $b["filter"]=!$b["enabled"]?"disabled":strtolower($b["state"]);
        $m["counts"]["total"]++;
        if(!$b["enabled"]){$m["counts"]["disabled"]++;}
        elseif($b["state"]=="UP"){$m["counts"]["up"]++;}
        elseif($b["state"]=="DEGRADED"){$m["counts"]["degraded"]++;}
        elseif($b["state"]=="DOWN"){$m["counts"]["down"]++;}
        $m["backends"][]=$b;
    }
    $m["sni"]=$snih2;
    return $m;
}

// ---- Barre d'actions (rechargee seule par les fenetres de reglages : ?top-buttons=) ----
function top_buttons():bool{
    $tpl=new template_admin();
    $serviceid=intval($_GET["top-buttons"]);
    $s=nbk_settings($serviceid);
    echo $tpl->_ENGINE_parse_body(nbk_actionbar($serviceid,$s,nbk_can_write()));
    return true;
}
function nbk_actionbar($serviceid,$s,$w):string{
    $page=CurrentPageName();
    $h=array();
    $h[]="<div class='nbk-bar'>";
    if($s["dynamics"]){
        $h[]="<div class='nbk-split'><button type='button' class='nbk-btn nbk-primary' disabled aria-disabled='true' title=\"{nbk_dynamic_no_add}\"><i class='fa-solid fa-lock' aria-hidden='true'></i><span>{new_backend}</span></button></div>";
    }elseif(!$w){
        $h[]="<div class='nbk-split'><button type='button' class='nbk-btn nbk-primary' disabled aria-disabled='true'><i class='fa-solid fa-lock' aria-hidden='true'></i><span>{new_backend}</span></button></div>";
    }else{
        $h[]="<div class='nbk-split nbk-dd'>";
        $h[]="<button type='button' class='nbk-btn nbk-primary nbk-split-main' OnClick=\"Loadjs('$page?id-js=0&serviceid=$serviceid&md5=');\"><i class='fa-solid fa-plus' aria-hidden='true'></i><span>{new_backend}</span></button>";
        $h[]="<button type='button' class='nbk-btn nbk-primary nbk-split-caret' data-nbk-dd aria-haspopup='true' aria-expanded='false' aria-label=\"{nbk_more_add}\"><i class='fa-solid fa-chevron-down' aria-hidden='true'></i></button>";
        $h[]="<div class='nbk-menu' role='menu'>";
        $h[]="<button type='button' role='menuitem' OnClick=\"Loadjs('$page?id-js=0&serviceid=$serviceid&md5=');\"><i class='fa-solid fa-server' aria-hidden='true'></i><span>{new_backend}</span></button>";
        $h[]="<button type='button' role='menuitem' OnClick=\"Loadjs('$page?relay-js=0&serviceid=$serviceid');\"><i class='fa-brands fa-docker' aria-hidden='true'></i><span>{dr_site_backend_new}</span></button>";
        $h[]="</div></div>";
    }
    $h[]=nbk_settings_dd($serviceid,$s,$w);
    $h[]="</div>";
    return implode("",$h);
}
function nbk_onoff($on):string{
    return $on?"<span class='nbk-val nbk-on'>{nbk_on}</span>":"<span class='nbk-val nbk-off'>{nbk_off}</span>";
}
function nbk_settings_dd($serviceid,$s,$w):string{
    $page=CurrentPageName();
    $rows=array();
    $add=function($ico,$label,$val,$js) use (&$rows,$w){
        $dis=$w?"":" disabled aria-disabled='true'";
        $click=$w?" OnClick=\"$js\"":"";
        $rows[]="<button type='button' role='menuitem' class='nbk-set'$dis$click><i class='$ico' aria-hidden='true'></i><span class='nbk-set-l'>$label</span>$val</button>";
    };
    if(!$s["ahc"] || !$s["ahc_daemon"]){
        $add("fa-solid fa-scale-balanced","{nbk_load_balancing}","<span class='nbk-val nbk-info'>".nbk_h($s["lb"])."</span>","Loadjs('$page?load-balancing-js=$serviceid&md5=');");
    }
    $add("fa-solid fa-hourglass-half","{keep_alive}",nbk_onoff($s["keepalive"]),"Loadjs('$page?backends-keepalive-js=$serviceid&md5=');");
    if($s["type"]<>5){
        $add("fa-solid fa-unlock","{insecure}",nbk_onoff($s["insecure"]),"Loadjs('$page?insecure-switch=$serviceid&md5=');");
    }
    $add("fa-solid fa-diagram-project","HaBackend",nbk_onoff($s["habackend"]),"Loadjs('$page?UseHaBackend-js=$serviceid&md5=');");
    $add("fa-solid fa-clone","Shadow",nbk_onoff($s["shadow"]),"Loadjs('$page?Shadow-js=$serviceid&md5=');");
    $add("fa-brands fa-microsoft","SSO NTLM",nbk_onoff($s["ntlm"]),"Loadjs('$page?NTLMPassthrough-js=$serviceid&md5=');");
    if(!$s["ahc_daemon"]){
        $add("fa-solid fa-heart-pulse","{nbk_active_hc}","<span class='nbk-val nbk-off'>{nbk_daemon_off}</span>","Loadjs('$page?active-health-check-none=yes');");
    }elseif(!$s["ahc"]){
        $add("fa-solid fa-heart-pulse","{nbk_active_hc}",nbk_onoff(false),"Loadjs('$page?active-health-check-js=$serviceid&md5=&popup=0');");
    }else{
        $add("fa-solid fa-heart-pulse","{nbk_active_hc}","<span class='nbk-val nbk-on'>".$s["ahc_method"]."</span>","Loadjs('$page?active-health-check-js=$serviceid&md5=&popup=0');");
    }
    if($s["options"]<>""){
        $dis=$w?"":" disabled aria-disabled='true'";
        $click=$w?" OnClick=\"Loadjs('$page?options-js=$serviceid');\"":"";
        $rows[]="<button type='button' role='menuitem' class='nbk-set nbk-set-opt'$dis$click><i class='fa-solid fa-gear' aria-hidden='true'></i><span class='nbk-set-l'>{nbk_options}<small>".$s["options"]."</small></span></button>";
    }
    if($s["forcessl"]){
        $rows[]="<div class='nbk-set nbk-set-ro'><i class='fa-solid fa-lock' aria-hidden='true'></i><span class='nbk-set-l'>{remote_server_use_ssl}</span><span class='nbk-val nbk-off'>{nbk_forced}</span></div>";
    }
    $cur=(!$s["ahc"] || !$s["ahc_daemon"])?"<span class='nbk-val nbk-info'>".nbk_h($s["lb"])."</span>":"";
    return "<div class='nbk-dd nbk-dd-right'><button type='button' class='nbk-btn nbk-ghost' data-nbk-dd aria-haspopup='true' aria-expanded='false'>"
        ."<i class='fa-solid fa-sliders' aria-hidden='true'></i><span>{nbk_site_settings}</span>$cur<i class='fa-solid fa-chevron-down' aria-hidden='true'></i></button>"
        ."<div class='nbk-menu nbk-menu-set' role='menu'>".implode("",$rows)."</div></div>";
}

function table():bool{
    $tpl=new template_admin();
    $serviceid=intval($_GET["table"]);
    $m=nbk_model($serviceid);
    $h=array();
    $h[]="<div class='nbk' id='nbk-$serviceid'>";
    $h[]="<div id='top-buttons-backends-$serviceid'>".nbk_actionbar($serviceid,$m["settings"],$m["can_write"])."</div>";
    $h[]=nbk_summary($m);
    $h[]=nbk_searchbar($m);
    $h[]=nbk_grid($m);
    $h[]="</div>";
    echo nbk_style().$tpl->_ENGINE_parse_body(implode("",$h)).nbk_js($m);
    return true;
}

function nbk_summary($m):string{
    $c=$m["counts"];
    if($m["sla_err"]!==""){
        $banner="<div class='nbk-banner' role='status'><i class='fa-solid fa-circle-info' aria-hidden='true'></i><span>{backend_sla_unavailable_notice}: ".htmlspecialchars((string)$m["sla_err"],ENT_QUOTES,"UTF-8")."</span></div>";
    }else{
        $banner="";
    }
    if($c["total"]==0){return $banner;}
    $active=$c["total"]-$c["disabled"];
    $sub=$c["disabled"]>0?"<span class='nbk-stat-sub'>".nbk_t("nbk_n_disabled",array("%s"=>$c["disabled"]))."</span>":"";
    $h=array();
    $h[]=$banner;
    $h[]="<div class='nbk-stats'>";
    $h[]="<div class='nbk-stat'><span class='nbk-caps'>{nbk_backends}</span><div class='nbk-stat-v'>{$c["total"]}</div>".($sub!==""?$sub:"<span class='nbk-stat-sub'>".nbk_t("nbk_n_active",array("%s"=>$active))."</span>")."</div>";
    if($m["sla_err"]===""){
        foreach(array("up"=>array("fa-circle-check","nbk_up"),"degraded"=>array("fa-triangle-exclamation","nbk_degraded"),"down"=>array("fa-circle-xmark","nbk_down")) as $k=>$o){
            $h[]="<button type='button' class='nbk-stat nbk-stat-$k' data-nbk-go='$k'><span class='nbk-caps'>{".$o[1]."}</span><div class='nbk-stat-v'>{$c[$k]}</div><i class='fa-solid {$o[0]} nbk-stat-ico' aria-hidden='true'></i></button>";
        }
    }
    $h[]="</div>";
    return implode("",$h);
}

function nbk_searchbar($m):string{
    $c=$m["counts"];
    if($c["total"]==0){return "";}
    $chips=array("all"=>array("nbk_all",$c["total"]),"up"=>array("nbk_up",$c["up"]),"degraded"=>array("nbk_degraded",$c["degraded"]),
        "down"=>array("nbk_down",$c["down"]),"disabled"=>array("nbk_disabled",$c["disabled"]));
    $h=array();
    foreach($chips as $k=>$o){
        $h[]="<button type='button' role='radio' aria-checked='".($k=="all"?"true":"false")."' class='nbk-chip' data-nbk-f='$k'>{".$o[0]."} <span>({$o[1]})</span></button>";
    }
    return "<div class='nbk-search'><label class='nbk-q'><i class='fa-solid fa-magnifying-glass' aria-hidden='true'></i>"
        ."<input type='search' data-nbk-q placeholder=\"{nbk_search_ph}\" aria-label=\"{nbk_search_ph}\"></label>"
        ."<div class='nbk-chips' role='radiogroup' aria-label=\"{nbk_filter_state}\">".implode("",$h)."</div>"
        ."<div class='nbk-count' aria-live='polite' data-nbk-count></div></div>";
}

function nbk_state_pill($b):string{
    if(!$b["enabled"]){return "<span class='nbk-pill nbk-p-off'><i class='fa-solid fa-power-off' aria-hidden='true'></i>{nbk_disabled}</span>";}
    $map=array("UP"=>array("nbk-p-up","fa-circle-check","nbk_up"),"DEGRADED"=>array("nbk-p-deg","fa-triangle-exclamation","nbk_degraded"),
        "DOWN"=>array("nbk-p-down","fa-circle-xmark","nbk_down"),"MAINTENANCE"=>array("nbk-p-off","fa-wrench","backend_sla_state_maintenance"),
        "INITIALIZING"=>array("nbk-p-off","fa-hourglass-start","backend_sla_state_initializing"),"DISABLED"=>array("nbk-p-off","fa-power-off","nbk_disabled"),
        "REMOVED"=>array("nbk-p-off","fa-ban","backend_sla_state_removed"));
    $o=isset($map[$b["state"]])?$map[$b["state"]]:array("nbk-p-off","fa-circle-question","nbk_unknown");
    return "<span class='nbk-pill {$o[0]}'><i class='fa-solid {$o[1]}' aria-hidden='true'></i>{".$o[2]."}</span>";
}
function nbk_ring($avail):string{
    if($avail===null || !is_numeric($avail) || $avail<0){
        return "<div class='nbk-ring-wrap'><span class='nbk-ring nbk-ring-na' aria-hidden='true'></span><span class='nbk-mv nbk-na'>&mdash;</span></div>";
    }
    $p=max(0,min(100,floatval($avail)));
    $col="#047857";
    return "<div class='nbk-ring-wrap'><span class='nbk-ring' role='img' aria-label='".nbk_h($p)." %' style='background:conic-gradient($col ".($p*3.6)."deg,#e2e8f0 0)'></span><span class='nbk-mv'>".nbk_h($avail)."%</span></div>";
}
function nbk_ms($v):string{
    if($v===null || !is_numeric($v) || $v<0){return "";}
    return number_format(floatval($v),0,".",",")." ms";
}
function nbk_tile($m,$b):string{
    $page=CurrentPageName();
    $sid=$m["id"];
    $w=$m["can_write"];
    $ID=$b["id"];
    $edit=$b["relay"]?"Loadjs('$page?relay-js=$ID&serviceid=$sid');":"Loadjs('$page?id-js=$ID&md5=');";
    // A Docker socket backend has no host nor port: the host:port form would
    // break it. Not edited here, nor probed (diagnostic and SLA use host:port).
    $isSock=$b["socket"]!=="";
    $probe=!$b["relay"] && !$isSock;
    $accent=!$b["enabled"]?"off":(in_array($b["state"],array("UP","DEGRADED","DOWN"))?strtolower($b["state"]):"na");
    $name=nbk_h($b["label"]);
    // En-tete : etat, depuis, interrupteur, menu
    $since=($b["since"]<>"" && $b["enabled"])?"<span class='nbk-since'>".nbk_t("nbk_since",array("%s"=>$b["since"]))."</span>":"";
    $aria=nbk_h(nbk_js_text("nbk_enable_backend")." ".$b["label"]);
    $sw=$w?"<button type='button' role='switch' aria-checked='".($b["enabled"]?"true":"false")."' class='nbk-sw' aria-label=\"$aria\" OnClick=\"Loadjs('$page?backend-enable=$ID');\"><span class='nbk-knob'></span></button>"
          :"<button type='button' role='switch' aria-checked='".($b["enabled"]?"true":"false")."' class='nbk-sw' aria-disabled='true' disabled aria-label=\"$aria\"><span class='nbk-knob'></span></button>";
    $menu=array();
    if(!$isSock){$menu[]="<button type='button' role='menuitem' OnClick=\"$edit\"><i class='fa-solid fa-pen' aria-hidden='true'></i>{nbk_edit}</button>";}
    if($probe){
        $menu[]="<button type='button' role='menuitem' OnClick=\"Loadjs('$page?diagnostic-js=$ID&serviceid=$sid');\"><i class='fa-solid fa-stethoscope' aria-hidden='true'></i>{backend_diagnostic}</button>";
        $menu[]="<button type='button' role='menuitem' OnClick=\"LoadAjax('MainContent','fw.nginx.backends.sla.statistics.php?serviceid=$sid&backendid=$ID');\"><i class='fa-solid fa-chart-line' aria-hidden='true'></i>{backend_sla_statistics}</button>";
        $menu[]="<button type='button' role='menuitem' OnClick=\"Loadjs('$page?backend-sla-run=$ID&serviceid=$sid');\"><i class='fa-solid fa-satellite-dish' aria-hidden='true'></i>{backend_sla_run_now}</button>";
    }
    if($w){
        $menu[]="<button type='button' role='menuitem' OnClick=\"Loadjs('$page?backend-move=$ID&serviceid=$sid&acl-rule-dir=1');\"><i class='fa-solid fa-arrow-up' aria-hidden='true'></i>{nbk_move_up}</button>";
        $menu[]="<button type='button' role='menuitem' OnClick=\"Loadjs('$page?backend-move=$ID&serviceid=$sid&acl-rule-dir=0');\"><i class='fa-solid fa-arrow-down' aria-hidden='true'></i>{nbk_move_down}</button>";
        $menu[]="<div class='nbk-sep' role='separator'></div>";
        $menu[]="<button type='button' role='menuitem' class='nbk-danger' OnClick=\"Loadjs('$page?delete=$ID&serviceid=$sid');\"><i class='fa-solid fa-trash-can' aria-hidden='true'></i>{nbk_delete}</button>";
    }
    $kebab="<div class='nbk-dd nbk-dd-right'><button type='button' class='nbk-kebab' data-nbk-dd aria-haspopup='true' aria-expanded='false' aria-label=\"".nbk_h(nbk_js_text("nbk_actions")." ".$b["label"])."\"><i class='fa-solid fa-ellipsis-vertical' aria-hidden='true'></i></button>"
        ."<div class='nbk-menu' role='menu'>".implode("",$menu)."</div></div>";
    // Adresse et pastilles
    $pills=array();
    if($isSock){
        $pills[]="<span class='nbk-tag nbk-tag-docker'><i class='fa-brands fa-docker' aria-hidden='true'></i>Docker</span>";
        $pills[]="<span class='nbk-tag'><i class='fa-solid fa-plug' aria-hidden='true'></i>Unix socket</span>";
    }elseif($b["relay"]){
        $pills[]="<span class='nbk-tag nbk-tag-docker'><i class='fa-brands fa-docker' aria-hidden='true'></i>Docker Relay</span>";
        if(!empty($b["relay_missing"])){$pills[]="<span class='nbk-tag nbk-tag-err'>{dr_backend_missing}</span>";}
        if(!empty($b["relay_down"])){$pills[]="<span class='nbk-tag'>{dr_down}</span>";}
    }elseif($b["proto"]=="https"){
        $pills[]="<span class='nbk-tag'><i class='fa-solid fa-lock' aria-hidden='true'></i>HTTPS</span>";
    }elseif($b["proto"]=="http"){
        $pills[]="<span class='nbk-tag'><i class='fa-solid fa-globe' aria-hidden='true'></i>HTTP</span>";
    }else{
        $pills[]="<span class='nbk-tag'><i class='fa-solid fa-network-wired' aria-hidden='true'></i>TCP/UDP</span>";
    }
    if($b["proxyproto"] && $probe){$pills[]="<span class='nbk-tag'>PROXY protocol</span>";}
    if($b["role"]=="primary"){$pills[]="<span class='nbk-tag nbk-tag-role'>{nbk_primary}</span>";}
    if($b["role"]=="backup"){$pills[]="<span class='nbk-tag'>{nbk_backup}</span>";}
    $notes=array();
    if($isSock){$notes[]="<div class='nbk-note'><i class='fa-solid fa-folder-tree' aria-hidden='true'></i><span style='word-break:break-all'>unix:".nbk_h($b["socket"])."</span></div>";}
    if($b["mismatch"]<>""){$notes[]="<div class='nbk-note nbk-note-warn'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i><span>".$b["mismatch"]."</span></div>";}
    if($b["enabled"] && $b["loaded"]===false){$notes[]="<div class='nbk-note nbk-note-warn'><i class='fa-solid fa-circle-info' aria-hidden='true'></i><span>{nbk_not_loaded}</span></div>";}
    if($b["enabled"] && $b["error"]<>""){
        $code=$b["http"]>0?" (HTTP ".intval($b["http"]).")":"";
        $cls=$b["state"]=="DEGRADED"?"nbk-note-warn":"nbk-note-err";
        $notes[]="<div class='nbk-note $cls' title=\"".nbk_h($b["error"])."\"><i class='fa-solid fa-circle-xmark' aria-hidden='true'></i><span>".nbk_h($b["error"]).$code."</span></div>";
    }
    // Mesures (non applicables aux backends Docker Relay : la supervision ne les sonde pas)
    $metrics="";
    if($probe){
        $lat=nbk_ms($b["lat"]);
        $p95=nbk_ms($b["p95"]);
        $latH=$lat<>""?"<span class='nbk-mv'>$lat</span>":"<span class='nbk-mv nbk-na'>&mdash;</span>";
        $latS=$p95<>""?"<span class='nbk-ms'>P95 $p95</span>":"<span class='nbk-ms'>{not_available}</span>";
        $grade=$b["grade"]<>""?nbk_h($b["grade"]):"&mdash;";
        $metrics="<div class='nbk-metrics'>"
            ."<div><span class='nbk-caps'>{backend_sla_col_availability_24h}</span>".nbk_ring($b["avail"])."</div>"
            ."<div><span class='nbk-caps'>{nbk_latency}</span>$latH$latS</div>"
            ."<div><span class='nbk-caps'>{backend_sla_col_security}</span><span class='nbk-grade'>$grade</span></div></div>";
    }
    $foot=array();
    if($probe){$foot[]=nbk_t("nbk_tuning",array("%w"=>$b["weight"],"%t"=>$b["timeout"],"%f"=>$b["maxfails"]));}
    else{$foot[]=nbk_t("nbk_weight",array("%w"=>$b["weight"]));}
    $req=$b["requests"]!==null?"<span>".nbk_t("nbk_requests",array("%s"=>number_format($b["requests"],0,".",",")))."</span>":"";
    $search=nbk_h(strtolower($b["search"]." ".$b["filter"]." ".$b["role"]));
    return "<article class='nbk-tile nbk-a-$accent".($b["enabled"]?"":" nbk-tile-off")."' data-nbk-state='{$b["filter"]}' data-nbk-text=\"$search\">"
        ."<div class='nbk-th'>".nbk_state_pill($b).$since."<span class='nbk-sp'></span>$sw$kebab</div>"
        .($isSock?"<div class='nbk-addr nbk-addr-label'><i class='fa-brands fa-docker' aria-hidden='true'></i><span>$name</span></div>"
            :"<button type='button' class='nbk-addr' OnClick=\"$edit\">".($b["relay"]?"<i class='fa-brands fa-docker' aria-hidden='true'></i>":"")."<span>$name</span></button>")
        ."<div class='nbk-tags'>".implode("",$pills)."</div>"
        .implode("",$notes).$metrics
        ."<div class='nbk-foot'><span>".implode(" &middot; ",$foot)."</span>$req</div>"
        ."</article>";
}
// Texte d'un token pour un attribut (aria-label) : traduit, entites decodees puis re-encodees par nbk_h().
function nbk_js_text($k):string{
    return trim(html_entity_decode(str_replace(array("<br>","[br]"),"",nbk_t($k)),ENT_QUOTES,"UTF-8"));
}

function nbk_grid($m):string{
    $page=CurrentPageName();
    $h=array();
    if($m["dynamic"]!==null){
        $h[]="<article class='nbk-tile nbk-dyn'><span class='nbk-pill nbk-p-info'><i class='fa-solid fa-shuffle' aria-hidden='true'></i>{nbk_dynamic}</span>"
            ."<div class='nbk-dyn-map'><span>".nbk_h($m["dynamic"]["src"])."</span><i class='fa-solid fa-arrow-right' aria-hidden='true'></i><span>".nbk_h($m["dynamic"]["dst"])."</span></div>"
            .($m["sni"]<>""?"<div class='nbk-ms'>{snih2}: ".nbk_h($m["sni"])."</div>":"")
            ."<button type='button' class='nbk-link' OnClick=\"Loadjs('fw.nginx.sites.dynamics.php?service-id={$m["id"]}');\">{nbk_edit_dynamic}</button></article>";
    }
    if(count($m["backends"])==0){
        if($m["dynamic"]!==null){return "<div class='nbk-grid'>".implode("",$h)."</div>";}
        $btn="";
        if($m["can_write"]){
            $btn="<button type='button' class='nbk-btn nbk-primary' OnClick=\"Loadjs('$page?id-js=0&serviceid={$m["id"]}&md5=');\"><i class='fa-solid fa-plus' aria-hidden='true'></i><span>{new_backend}</span></button>";
        }
        return "<div class='nbk-empty'><i class='fa-solid fa-server' aria-hidden='true'></i><strong>{nbk_empty_title}</strong><p>{nbk_empty}</p>$btn</div>";
    }
    foreach($m["backends"] as $b){$h[]=nbk_tile($m,$b);}
    $h[]="<div class='nbk-nomatch' data-nbk-nomatch hidden><i class='fa-solid fa-magnifying-glass' aria-hidden='true'></i><p>{nbk_no_match}</p><button type='button' class='nbk-link' data-nbk-clear>{nbk_clear_search}</button></div>";
    return "<div class='nbk-grid'>".implode("",$h)."</div>";
}

function nbk_js($m):string{
    $L=array("count"=>nbk_js_text("nbk_showing"));
    $flags=JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP;
    $j=array();
    $j[]="<script>(function(){";
    $j[]="var R=document.getElementById('nbk-{$m["id"]}');if(!R){return;}var L=".json_encode($L,$flags).";";
    // Menus deroulants : un seul ecouteur global, installe une fois pour toute la console.
    // Clavier en phase de CAPTURE : sinon la fenetre modale du site recoit Echap avant nous et se ferme.
    $j[]="if(!window.NbkMenus){window.NbkMenus=true;";
    $j[]=" var closeAll=function(except){var o=document.querySelectorAll('.nbk-dd.nbk-open');for(var i=0;i<o.length;i++){if(o[i]!==except){o[i].classList.remove('nbk-open');var t=o[i].querySelector('[data-nbk-dd]');if(t){t.setAttribute('aria-expanded','false');}}}};";
    $j[]=" document.addEventListener('click',function(e){var t=e.target.closest?e.target.closest('[data-nbk-dd]'):null;";
    $j[]="  if(t){var dd=t.closest('.nbk-dd');var open=!dd.classList.contains('nbk-open');closeAll(dd);dd.classList.toggle('nbk-open',open);t.setAttribute('aria-expanded',open?'true':'false');";
    $j[]="   if(open){var f=dd.querySelector('.nbk-menu [role=menuitem]:not([disabled])');if(f){f.focus();}}e.stopPropagation();return;}";
    $j[]="  closeAll(null);});";
    $j[]=" document.addEventListener('keydown',function(e){var dd=document.querySelector('.nbk-dd.nbk-open');if(!dd){return;}";
    $j[]="  var it=[].slice.call(dd.querySelectorAll('.nbk-menu [role=menuitem]:not([disabled])')),i=it.indexOf(document.activeElement);";
    $j[]="  if(e.key==='Escape'){closeAll(null);var t=dd.querySelector('[data-nbk-dd]');if(t){t.focus();}e.preventDefault();e.stopPropagation();window.NbkEatEsc=true;}";
    $j[]="  else if(e.key==='ArrowDown'&&it.length){it[(i+1)%it.length].focus();e.preventDefault();}";
    $j[]="  else if(e.key==='ArrowUp'&&it.length){it[(i-1+it.length)%it.length].focus();e.preventDefault();}},true);";
    // BootstrapDialog ferme la fenetre sur le keyup d'Echap : on avale celui qui suit la fermeture d'un menu.
    $j[]=" document.addEventListener('keyup',function(e){if(e.key==='Escape'&&window.NbkEatEsc){window.NbkEatEsc=false;e.stopPropagation();e.preventDefault();}},true);";
    $j[]="}";
    // Recherche et filtres, cote navigateur : toutes les tuiles sont deja dans la page.
    $j[]="var q=R.querySelector('[data-nbk-q]'),cnt=R.querySelector('[data-nbk-count]'),nm=R.querySelector('[data-nbk-nomatch]'),f='all';";
    $j[]="function apply(){var tiles=R.querySelectorAll('.nbk-tile[data-nbk-state]'),s=q?q.value.trim().toLowerCase():'',n=0;";
    $j[]=" for(var i=0;i<tiles.length;i++){var t=tiles[i],ok=(f==='all'||t.getAttribute('data-nbk-state')===f)&&(s===''||t.getAttribute('data-nbk-text').indexOf(s)>=0);t.hidden=!ok;if(ok){n++;}}";
    $j[]=" if(cnt){cnt.textContent=L.count.replace('%n',n).replace('%t',tiles.length);}if(nm){nm.hidden=n>0;}}";
    $j[]="function setF(v){f=v;var c=R.querySelectorAll('[data-nbk-f]');for(var i=0;i<c.length;i++){c[i].setAttribute('aria-checked',c[i].getAttribute('data-nbk-f')===v?'true':'false');}apply();}";
    $j[]="if(q){q.addEventListener('input',apply);}";
    $j[]="var c=R.querySelectorAll('[data-nbk-f]');for(var i=0;i<c.length;i++){c[i].addEventListener('click',function(){setF(this.getAttribute('data-nbk-f'));});}";
    $j[]="var g=R.querySelectorAll('[data-nbk-go]');for(var k=0;k<g.length;k++){g[k].addEventListener('click',function(){setF(this.getAttribute('data-nbk-go'));});}";
    $j[]="var cl=R.querySelector('[data-nbk-clear]');if(cl){cl.addEventListener('click',function(){if(q){q.value='';}setF('all');});}";
    $j[]="apply();if(typeof NoSpinner==='function'){NoSpinner();}";
    $j[]="})();</script>";
    return implode("\n",$j);
}

function nbk_style():string{
    $f=array();
    $f[]="<style>";
    $f[]=".nbk{display:flex;flex-direction:column;gap:16px;margin-top:16px;color:#0f172a;font-size:14px}";
    $f[]=".nbk *{box-sizing:border-box}";
    $f[]=".nbk [hidden]{display:none!important}";
    $f[]=".nbk-caps{display:block;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#64748b}";
    $f[]=".nbk-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}";
    $f[]=".nbk-btn{display:inline-flex;align-items:center;gap:8px;height:40px;padding:0 18px;border-radius:8px;border:1px solid transparent;font-size:14px;font-weight:600;cursor:pointer;white-space:nowrap}";
    $f[]=".nbk-btn[disabled]{opacity:.6;cursor:not-allowed}";
    $f[]=".nbk-primary{background:#0369a1;border-color:#0369a1;color:#fff}.nbk-primary:not([disabled]):hover{background:#075985}";
    $f[]=".nbk-ghost{background:#fff;border-color:#cbd5e1;color:#334155}.nbk-ghost:hover{background:#f8fafc}";
    $f[]=".nbk-split{display:inline-flex}";
    $f[]=".nbk-split-main{border-radius:8px 0 0 8px}.nbk-split-caret{border-radius:0 8px 8px 0;padding:0 12px;border-left:1px solid rgba(255,255,255,.35)}";
    $f[]=".nbk-dd{position:relative}";
    $f[]=".nbk-menu{display:none;position:absolute;top:calc(100% + 6px);left:0;z-index:50;min-width:240px;padding:6px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 12px 32px rgba(15,23,42,.16)}";
    $f[]=".nbk-dd-right .nbk-menu{left:auto;right:0}";
    $f[]=".nbk-open>.nbk-menu{display:block}";
    $f[]=".nbk-menu [role=menuitem],.nbk-set-ro{display:flex;align-items:center;gap:10px;width:100%;padding:9px 10px;border:0;border-radius:6px;background:none;color:#0f172a;font-size:14px;text-align:left;cursor:pointer}";
    $f[]=".nbk-menu [role=menuitem]:hover,.nbk-menu [role=menuitem]:focus-visible{background:#f1f5f9;outline:none}";
    $f[]=".nbk-menu [role=menuitem][disabled]{cursor:not-allowed;opacity:.6}";
    $f[]=".nbk-menu i,.nbk-menu svg{width:16px;color:#64748b;text-align:center}";
    $f[]=".nbk-menu .nbk-danger,.nbk-menu .nbk-danger i,.nbk-menu .nbk-danger svg{color:#b91c1c}";
    $f[]=".nbk-sep{height:1px;margin:4px 0;background:#e2e8f0}";
    $f[]=".nbk-menu-set{width:360px}";
    $f[]=".nbk-set-l{flex:1;display:flex;flex-direction:column;gap:2px;min-width:0}";
    $f[]=".nbk-set-l small{font-size:12px;color:#64748b;white-space:normal}";
    $f[]=".nbk-set-ro{cursor:default;color:#475569}";
    $f[]=".nbk-val{padding:2px 9px;border-radius:999px;font-size:12px;font-weight:600;white-space:nowrap}";
    $f[]=".nbk-on,.nbk-info{background:#e0f2fe;color:#075985}.nbk-off{background:#f1f5f9;color:#475569}";
    $f[]=".nbk-btn .nbk-val{margin-left:4px}";
    $f[]=".nbk-banner{display:flex;gap:10px;align-items:flex-start;padding:12px 16px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;color:#475569;font-size:13px}";
    $f[]=".nbk-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}";
    $f[]=".nbk-stat{position:relative;display:flex;flex-direction:column;gap:2px;min-height:76px;padding:12px 16px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;text-align:left;font:inherit;color:inherit}";
    $f[]="button.nbk-stat{cursor:pointer}button.nbk-stat:hover{border-color:#94a3b8}";
    $f[]=".nbk-stat-v{font-size:26px;font-weight:700;line-height:1.1;font-variant-numeric:tabular-nums}";
    $f[]=".nbk-stat-sub{font-size:13px;color:#64748b}";
    $f[]=".nbk-stat-ico{position:absolute;top:14px;right:16px;font-size:22px}";
    $f[]=".nbk-stat-up .nbk-caps,.nbk-stat-up .nbk-stat-ico{color:#047857}.nbk-stat-degraded .nbk-caps,.nbk-stat-degraded .nbk-stat-ico{color:#b45309}.nbk-stat-down .nbk-caps,.nbk-stat-down .nbk-stat-ico{color:#b91c1c}";
    $f[]=".nbk-search{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:8px;background:#fff;border:1px solid #e2e8f0;border-radius:10px}";
    $f[]=".nbk-q{flex:1;min-width:260px;display:flex;align-items:center;gap:8px;height:40px;margin:0;padding:0 12px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;color:#64748b;font-weight:400}";
    $f[]=".nbk-q input{flex:1;min-width:0;border:0;background:none;font-size:14px;color:#0f172a;outline:none}";
    $f[]=".nbk-q:focus-within{border-color:#0369a1;box-shadow:0 0 0 2px rgba(3,105,161,.2)}";
    $f[]=".nbk-chips{display:flex;gap:4px;padding:3px;background:#f1f5f9;border-radius:8px;flex-wrap:wrap}";
    $f[]=".nbk-chip{height:32px;padding:0 12px;border:0;border-radius:6px;background:none;color:#475569;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap}";
    $f[]=".nbk-chip span{font-weight:500;color:#64748b}";
    $f[]=".nbk-chip[aria-checked=true]{background:#fff;color:#0369a1;box-shadow:0 1px 2px rgba(15,23,42,.12)}";
    $f[]=".nbk-count{margin-left:auto;padding-right:8px;font-size:13px;color:#64748b;white-space:nowrap}";
    $f[]=".nbk-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px}";
    $f[]=".nbk-tile{position:relative;display:flex;flex-direction:column;gap:10px;padding:16px 16px 0 20px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06);transition:box-shadow .15s}";
    $f[]=".nbk-tile:hover{box-shadow:0 6px 16px rgba(15,23,42,.10)}";
    $f[]=".nbk-tile:before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;border-radius:12px 0 0 12px;background:#94a3b8}";
    $f[]=".nbk-a-up:before{background:#047857}.nbk-a-degraded:before{background:#d97706}.nbk-a-down:before{background:#b91c1c}.nbk-a-off:before{background:#cbd5e1}";
    $f[]=".nbk-tile-off>*:not(.nbk-th){opacity:.6}";
    $f[]=".nbk-th{display:flex;align-items:center;gap:8px}";
    $f[]=".nbk-sp{flex:1}";
    $f[]=".nbk-since{font-size:12px;color:#64748b;white-space:nowrap}";
    $f[]=".nbk-pill{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;border:1px solid transparent;font-size:13px;font-weight:600;white-space:nowrap}";
    $f[]=".nbk-p-up{background:#ecfdf5;color:#047857;border-color:#a7f3d0}.nbk-p-deg{background:#fffbeb;color:#b45309;border-color:#fde68a}.nbk-p-down{background:#fef2f2;color:#b91c1c;border-color:#fecaca}";
    $f[]=".nbk-p-off{background:#f1f5f9;color:#475569;border-color:#e2e8f0}.nbk-p-info{background:#e0f2fe;color:#075985;border-color:#bae6fd}";
    $f[]=".nbk-sw{position:relative;width:44px;height:24px;flex-shrink:0;border:0;border-radius:999px;background:#cbd5e1;cursor:pointer;padding:0}";
    $f[]=".nbk-knob{position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.25);transition:transform .12s}";
    $f[]=".nbk-sw[aria-checked=true]{background:#0369a1}.nbk-sw[aria-checked=true] .nbk-knob{transform:translateX(20px)}";
    $f[]=".nbk-sw[disabled]{cursor:not-allowed;opacity:.6}";
    $f[]=".nbk-kebab{width:32px;height:32px;border:0;border-radius:6px;background:none;color:#64748b;cursor:pointer}.nbk-kebab:hover{background:#f1f5f9;color:#0f172a}";
    $f[]=".nbk-addr{display:flex;align-items:center;gap:8px;padding:0;border:0;background:none;font-family:monospace;font-size:18px;font-weight:700;color:#0f172a;text-align:left;cursor:pointer;min-width:0}";
    $f[]=".nbk-addr span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}";
    $f[]=".nbk-addr:hover span{text-decoration:underline}";
    // A Docker socket backend shows a sentence, not an address: wrap it, no link style.
    $f[]=".nbk-addr.nbk-addr-label{font-family:inherit;font-size:16px;cursor:default}";
    $f[]=".nbk-addr.nbk-addr-label span{white-space:normal;overflow:visible}";
    $f[]=".nbk-addr.nbk-addr-label:hover span{text-decoration:none}";
    $f[]=".nbk-addr .fa-docker,.nbk-addr svg{color:#0284c7}";
    $f[]=".nbk-tags{display:flex;flex-wrap:wrap;gap:6px}";
    $f[]=".nbk-tag{display:inline-flex;align-items:center;gap:5px;padding:2px 9px;border-radius:6px;background:#f1f5f9;border:1px solid #e2e8f0;color:#334155;font-size:12px;font-weight:600}";
    $f[]=".nbk-tag-role{background:#e0f2fe;border-color:#bae6fd;color:#075985}.nbk-tag-docker{background:#e0f2fe;border-color:#bae6fd;color:#0369a1}.nbk-tag-err{background:#fef2f2;border-color:#fecaca;color:#b91c1c}";
    $f[]=".nbk-note{display:flex;gap:8px;align-items:flex-start;padding:7px 10px;border-radius:6px;font-size:13px;font-weight:600;line-height:1.4}";
    $f[]=".nbk-note span{overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;word-break:break-word}";
    $f[]=".nbk-note>i,.nbk-note>svg{margin-top:2px;flex-shrink:0}";
    $f[]=".nbk-note-warn{background:#fffbeb;color:#92400e;border:1px solid #fde68a}.nbk-note-err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}";
    $f[]=".nbk-metrics{display:grid;grid-template-columns:1.2fr 1fr .8fr;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc}";
    $f[]=".nbk-metrics>div{display:flex;flex-direction:column;gap:6px;padding:10px 12px;border-left:1px solid #e2e8f0;min-width:0}";
    $f[]=".nbk-metrics>div:first-child{border-left:0}";
    $f[]=".nbk-ring-wrap{display:flex;align-items:center;gap:8px}";
    $f[]=".nbk-ring{position:relative;width:26px;height:26px;flex-shrink:0;border-radius:50%}";
    $f[]=".nbk-ring:after{content:'';position:absolute;inset:4px;border-radius:50%;background:#f8fafc}";
    $f[]=".nbk-ring-na{background:#e2e8f0}";
    $f[]=".nbk-mv{font-size:18px;font-weight:700;font-variant-numeric:tabular-nums;white-space:nowrap}";
    $f[]=".nbk-na{color:#94a3b8}";
    $f[]=".nbk-ms{font-size:12px;color:#64748b}";
    $f[]=".nbk-grade{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;font-size:16px;font-weight:700;color:#334155}";
    $f[]=".nbk-foot{display:flex;justify-content:space-between;gap:12px;margin:0 -16px 0 -20px;padding:10px 16px 10px 20px;border-top:1px solid #f1f5f9;font-size:12px;color:#64748b;margin-top:auto}";
    $f[]=".nbk-foot span:last-child{white-space:nowrap;font-variant-numeric:tabular-nums}";
    $f[]=".nbk-dyn{grid-column:1/-1;padding-bottom:16px}";
    $f[]=".nbk-dyn-map{display:flex;align-items:center;gap:12px;flex-wrap:wrap;font-family:monospace;font-size:16px;font-weight:700}";
    $f[]=".nbk-link{align-self:flex-start;padding:0;border:0;background:none;color:#0369a1;font-size:13px;font-weight:600;cursor:pointer}.nbk-link:hover{text-decoration:underline}";
    $f[]=".nbk-empty,.nbk-nomatch{grid-column:1/-1;display:flex;flex-direction:column;align-items:center;gap:10px;padding:40px 24px;background:#fff;border:1px dashed #cbd5e1;border-radius:12px;color:#475569;text-align:center}";
    $f[]=".nbk-empty>i,.nbk-empty>svg,.nbk-nomatch>i,.nbk-nomatch>svg{font-size:36px;color:#cbd5e1}.nbk-empty p,.nbk-nomatch p{margin:0}";
    $f[]=".nbk button:focus-visible,.nbk input:focus-visible{outline:2px solid #0369a1;outline-offset:2px}";
    $f[]="@media (max-width:1199px){.nbk-stats{grid-template-columns:repeat(2,1fr)}}";
    $f[]="@media (prefers-reduced-motion:reduce){.nbk *{transition:none!important}}";
    $f[]="</style>";
    return implode("",$f);
}

function options_popup():bool{
    $serviceid=$_GET["options-popup"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $NginxHTTPSubModule = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHTTPSubModule"));
    $sock               = new socksngix($serviceid);
    $default_page       = trim($sock->GET_INFO("default_page"));
    $resolvers =  trim($sock->GET_INFO("resolvers"));

    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_text("HostHeader","{HostHeader}",$sock->GET_INFO("HostHeader"));
    $EnableFalainacloudCapabilites=intval($sock->GET_INFO("EnableFalainacloudCapabilites"));
    $MapBackendsByPort=intval($sock->GET_INFO("MapBackendsByPort"));
    $proxy_http_version=trim($sock->GET_INFO("proxy_http_version"));
    if($proxy_http_version==null){$proxy_http_version="1.0";}

    $zproxy_http_version["1.0"]="1.0: {default} ";
    $zproxy_http_version["1.1"]="1.1: KeepAlive {or} NTLM";


    $form[]=$tpl->field_array_hash($zproxy_http_version,"proxy_http_version","{proxy_http_version}",$proxy_http_version);


    if($NginxHTTPSubModule==0){$tpl->field_hidden("HostHeaderReplace",0);}
    if($NginxHTTPSubModule==1) {
        $form[] = $tpl->field_checkbox("HostHeaderReplace", "{HostHeaderReplace}", $sock->GET_INFO("HostHeaderReplace"));
        $form[] = $tpl->field_checkbox("ProtoReplaceContent","{ProtoReplaceContent}", $sock->GET_INFO("ProtoReplaceContent"));
    }
    $form[] = $tpl->field_checkbox("MapBackendsByPort", "{map} {backends} {by} {port}", $MapBackendsByPort);
    $form[] = $tpl->field_checkbox("EnableFalainacloudCapabilites", "{enable} Falainacloud {support}", $EnableFalainacloudCapabilites);
    $form[]=$tpl->field_text("RemotePath","{TargetRemotePath}",$sock->GET_INFO("RemotePath"));
    $form[]=$tpl->field_text("default_page","{nginx_default_page}",$default_page,false,"url:https://wiki.articatech.com/en/reverse-proxy/architecture/default-index;{nginx_default_page_explain}");
    $form[]=$tpl->field_text("resolvers","{nic_static_dns}",$resolvers);

    $form[]=$tpl->field_section("SSL");
    if($NginxHTTPSubModule==1) {
        $form[] = $tpl->field_checkbox("ProtoReplaceContent", "{ProtoReplaceContent}", $sock->GET_INFO("ProtoReplaceContent"));
    }
    $proxy_ssl_server_name=intval($sock->GET_INFO("proxy_ssl_server_name"));
    $proxy_ssl_name =trim($sock->GET_INFO("proxy_ssl_name"));
    $form[]=$tpl->field_checkbox("proxy_ssl_server_name","{snih2}",$proxy_ssl_server_name,"proxy_ssl_name");
    $form[]=$tpl->field_text("proxy_ssl_name","{domain}",$proxy_ssl_name,false,"{proxy_ssl_server_name_explain}");

    $js="LoadAjax('backends-reverse-$serviceid','$page?table=$serviceid')";
    echo $tpl->form_outside(null, $form,"","{apply}","$js","AsSystemWebMaster");
    return true;
}
function options_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $serviceid=$_POST["serviceid"];
    unset($_POST["serviceid"]);
    $sock=new socksngix($serviceid);
    foreach ($_POST as $key=>$val){
        $sock->SET_INFO($key,$val);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks_post("Saving backends options for Reverse-Proxy ".get_servicename($serviceid));

}
function NTLMPassthrough_js():bool{
    $serviceid=intval($_GET["NTLMPassthrough-js"]);
    $sock                   = new socksngix($serviceid);
    $NTLMPassthrough=intval($sock->GET_INFO("NTLMPassthrough"));
    if($NTLMPassthrough==0){
        $NTLMPassthrough=1;
    }else{
        $NTLMPassthrough=0;
    }
    $sock->SET_INFO("NTLMPassthrough",$NTLMPassthrough);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    $UseHaBackend           = intval($sock->GET_INFO("UseHaBackend"));
    if($UseHaBackend==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/habackend/restart");
    }
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('top-buttons-backends-$serviceid','$page?top-buttons=$serviceid');";
    return admin_tracks("Set the reverse-proxy SSO NTLM to $NTLMPassthrough");
}
// ---------- Docker Relay rows (lot D) ----------

// One backends row, telling "absent" from "unreadable" (fetch_backend_by_id
// returns array() for both, Pattern #54).
function relay_fetch_row($ID):array{
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/backends/ID/".intval($ID)),true);
    if(!is_array($json) || empty($json["Status"])){
        $err=is_array($json) && isset($json["Error"])?(string)$json["Error"]:"daemon unreachable";
        return array("ok"=>false,"err"=>$err,"row"=>array());
    }
    $row=(isset($json["rows"]) && is_array($json["rows"]) && count($json["rows"])>0)?$json["rows"][0]:array();
    return array("ok"=>true,"err"=>"","row"=>$row);
}

function relay_uuid_ok($s):string{
    $s=strtolower(trim((string)$s));
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',$s)?$s:"";
}

// ONE /docker-relay/status call per table render; a failure is kept as a
// reason (badges say "unknown"), never as "no relay backend" (Pattern #52).
function relay_status_index():array{
    $out=array("ok"=>false,"err"=>"","agents"=>array(),"backends"=>array());
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/docker-relay/status"),true);
    if(!is_array($json) || empty($json["Status"]) || !isset($json["status"]) || !is_array($json["status"])){
        $out["err"]=is_array($json) && isset($json["Error"])?(string)$json["Error"]:"daemon unreachable";
        return $out;
    }
    $out["ok"]=true;
    foreach((array)$json["status"]["agents"] as $a){if(is_array($a) && isset($a["uuid"])){$out["agents"][(string)$a["uuid"]]=$a;}}
    foreach((array)$json["status"]["backends"] as $b){if(is_array($b) && isset($b["uuid"])){$out["backends"][(string)$b["uuid"]]=$b;}}
    return $out;
}

function relay_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["relay-js"]);
    $sid=intval($_GET["serviceid"]);
    return $tpl->js_dialog2($ID>0?"{dr_site_backend_edit}":"{dr_site_backend_new}","$page?relay-popup=$ID&serviceid=$sid",600);
}

function relay_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["relay-popup"]);
    $sid=intval($_GET["serviceid"]);
    $idx=relay_status_index();
    if(!$idx["ok"]){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{dr_status_unreadable}||".htmlspecialchars($idx["err"])));
        return false;
    }
    $cur=array("relay_uuid"=>"","weight"=>1,"backup"=>0,"down"=>0);
    if($ID>0){
        $f=relay_fetch_row($ID);
        $row=$f["row"];
        if(!$f["ok"] || count($row)==0 || trim((string)(isset($row["relay_uuid"])?$row["relay_uuid"]:""))===""){
            // Unreadable row and absent/classic row are different answers.
            $why=!$f["ok"]?"{dr_status_unreadable}: ".htmlspecialchars($f["err"]):"#$ID: {dr_backend_missing}";
            echo $tpl->_ENGINE_parse_body($tpl->div_error("{dr_site_backend_edit}||$why"));
            return false;
        }
        $cur=array_merge($cur,$row);
    }
    $curUUID=relay_uuid_ok($cur["relay_uuid"]);
    $curAgent=($curUUID!=="" && isset($idx["backends"][$curUUID]))?(string)$idx["backends"][$curUUID]["agent_uuid"]:"";
    $agents=array();
    foreach($idx["agents"] as $u=>$a){$agents[$u]=(string)$a["name"];}
    // A single agent is the only possible choice: preselect it, so its
    // backends are listed at once instead of an empty "select an agent first".
    if($curAgent==="" && count($agents)==1){$keys=array_keys($agents);$curAgent=(string)$keys[0];}
    $form=array();
    $form[]=$tpl->field_hidden("relay-save","yes");
    $form[]=$tpl->field_hidden("ID",$ID);
    $form[]=$tpl->field_hidden("serviceid",$sid);
    // form_outside posts only the fields it built, and a field_hidden value
    // is frozen into its JS at build time: the AJAX-loaded backend list
    // writes its choice into this read-only text field (row hidden below).
    $form[]=$tpl->field_text("relay_uuid","{dr_backend}",$curUUID,true,null,false,true);
    $form[]=$tpl->field_array_hash($agents,"relay_agent","{dr_agent}",$curAgent,true,null,"relayAgentChanged");
    // Same cell layout as the framework's field_* rows (label column 30 %).
    $form[]="<tr><td style='width:30%;text-align:right;vertical-align:middle;padding-bottom:10px;' nowrap><span class='labelform'>{dr_backend}:</span></td>"
        ."<td style='padding-bottom:10px;padding-left:10px'><div id='relay-backend-select'></div></td></tr>";
    $form[]=$tpl->field_numeric("weight","{weight}",intval($cur["weight"])>0?intval($cur["weight"]):1);
    $form[]=$tpl->field_checkbox("backup","{dr_backup}",intval($cur["backup"]));
    $form[]=$tpl->field_checkbox("down","{dr_down}",intval($cur["down"]));
    $jsafter="dialogInstance2.close();LoadAjax('backends-reverse-$sid','$page?table=$sid');";
    $html=array();
    $html[]=$tpl->form_outside("",$form,"{dr_site_backend_explain}",$ID>0?"{apply}":"{add}",$jsafter,"AsSystemWebMaster");
    // The select's DOM id is framework-generated: the value is passed by the
    // OnChange attribute itself, and the first load uses the current agent.
    $js="<script>function relayPicked(v){var h=document.querySelector(\".bootstrap-dialog input[name='relay_uuid']\");if(h){h.value=v;}}"
        ."(function(){var h=document.querySelector(\".bootstrap-dialog input[name='relay_uuid']\");if(h){var tr=h.closest('tr');if(tr){tr.style.display='none';}}})();"
        ."function relayAgentChanged(v){relayPicked('');"
        ."LoadAjaxSilent('relay-backend-select','$page?relay-options='+encodeURIComponent(v||'')+'&current=$curUUID');}"
        ."relayAgentChanged('$curAgent');</script>";
    echo $tpl->_ENGINE_parse_body($html).$js;
    return true;
}

// <select name="relay_uuid"> with the backends of one agent.
function relay_options():bool{
    $tpl=new template_admin();
    $agent=relay_uuid_ok($_GET["relay-options"]);
    $current=relay_uuid_ok(isset($_GET["current"])?$_GET["current"]:"");
    if($agent===""){echo $tpl->_ENGINE_parse_body("<em>{dr_select_agent_first}</em>");return true;}
    $idx=relay_status_index();
    if(!$idx["ok"]){echo $tpl->_ENGINE_parse_body("{dr_status_unreadable}: ").htmlspecialchars($idx["err"]);return false;}
    $opts=array();
    foreach($idx["backends"] as $u=>$b){
        if((string)$b["agent_uuid"]!==$agent){continue;}
        $sel=$u===$current?" selected":"";
        $opts[]="<option value='".htmlspecialchars($u,ENT_QUOTES)."'$sel>".htmlspecialchars((string)$b["name"]." — ".(string)$b["target_type"]." ".(string)$b["target"])."</option>";
    }
    if(count($opts)==0){echo $tpl->_ENGINE_parse_body("<em>{dr_no_backend_for_agent}</em>");return true;}
    // The hidden relay_uuid field (relay_popup) is what gets posted.
    echo "<select id='relay_uuid_pick' class='form-control' onchange=\"relayPicked(this.value)\">".implode("",$opts)."</select>"
        ."<script>relayPicked(document.getElementById('relay_uuid_pick').value);</script>";
    return true;
}

// Echo nothing on success: the daemon applies every relay guard, then the
// site is rebuilt like after the classic form.
function relay_save():bool{
    $users=new usersMenus();
    if(!$users->AsSystemWebMaster){$t=new template_admin();echo $t->javascript_parse_text("{ERROR_NO_PRIVS2}");return false;}
    $sid=intval($_POST["serviceid"]);
    $uuid=relay_uuid_ok(rawurldecode((string)(isset($_POST["relay_uuid"])?$_POST["relay_uuid"]:"")));
    $tpl=new template_admin();
    if($uuid===""){echo $tpl->javascript_parse_text("{dr_backend}: {error}");return false;}
    $data=array("ID"=>intval($_POST["ID"]),"serviceid"=>$sid,"relay_uuid"=>$uuid,
        "weight"=>intval($_POST["weight"]),"backup"=>intval($_POST["backup"])==1?1:0,"down"=>intval($_POST["down"])==1?1:0);
    $r=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/docker-relay/site-backends",$data),true);
    if(!is_array($r) || empty($r["Status"])){
        $err=is_array($r)?(isset($r["Error"])?$r["Error"]:(isset($r["error"])?$r["error"]:"error")):"daemon unreachable";
        echo htmlspecialchars($tpl->javascript_parse_text((string)$err),ENT_NOQUOTES,'UTF-8');
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$sid");
    return admin_tracks("Docker Relay backend $uuid ".($data["ID"]>0?"updated":"attached")." on site ".get_servicename($sid));
}
