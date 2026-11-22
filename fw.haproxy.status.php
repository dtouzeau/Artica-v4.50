<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.openvpn.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["service-status"])){service_status();exit;}
if(isset($_GET["flat-config"])){flat_config();exit;}
if(isset($_GET["config-js"])){config_js();exit;}
if(isset($_GET["config-popup"])){config_popup();exit;}
if(isset($_POST["HaClusterMaxConn"])){Save();exit;}

page();
function Save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
}

function page(){
	$page=CurrentPageName();
	$version=htmlspecialchars((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_VERSION"));
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{load_balancing} v$version</h1></div>
	</div>
	<div class='row'><div id='progress-haproxy-restart'></div>
	<div class='ibox-content' style='background:#f8fafc'>
	<div id='table-loader'></div>
	</div>
	</div>
	<script>
	LoadAjax('table-loader','$page?table=yes');
	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}

function lbst_style():string{
	return "<style>
.lbs-feats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.lbs-feat{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px;display:flex;gap:12px;align-items:flex-start;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.lbs-feat-ico{width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;font-size:14px}
.lbs-feat-t{font-size:13px;font-weight:700;color:#1e293b}
.lbs-feat-x{font-size:12px;color:#64748b;line-height:1.5;margin-top:2px}
.lbs-acts{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-top:22px}
.lbs-act{display:flex;align-items:center;justify-content:center;gap:10px;height:48px;border-radius:12px;font-size:14px;font-weight:600;color:#fff!important;text-decoration:none!important;cursor:pointer;box-shadow:0 1px 2px rgba(15,23,42,.08);transition:background .15s}
.lbs-act-teal{background:#0f766e}.lbs-act-teal:hover{background:#115e59}
.lbs-act-red{background:#e11d48}.lbs-act-red:hover{background:#be123c}
.lbs-act-amber{background:#d97706}.lbs-act-amber:hover{background:#b45309}
.lbs-last{margin-top:14px;display:flex;align-items:center;justify-content:space-between;gap:12px;font-size:12px;padding:10px 16px;border-radius:12px}
.lbs-last-ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}
.lbs-last-err{background:#fff1f2;border:1px solid #fecdd3;color:#9f1239}
.lbs-last-run{background:#f1f5f9;border:1px solid #e2e8f0;color:#334155}
.lbs-last-time{font-size:11px;opacity:.8;white-space:nowrap}
.lbs-cols{display:grid;grid-template-columns:350px minmax(0,1fr);gap:24px;margin-top:24px;align-items:start}
.lbs-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:24px;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.lbs-card-head{padding-bottom:16px;border-bottom:1px solid #f1f5f9;margin-bottom:6px}
.lbs-card-title{font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#0f172a;display:flex;align-items:center;gap:8px}
.lbs-card-title i{color:#0d9488}
.lbs-card-sub{font-size:12px;color:#64748b;margin-top:3px}
@media(max-width:1100px){.lbs-feats,.lbs-acts{grid-template-columns:repeat(2,minmax(0,1fr))}.lbs-cols{grid-template-columns:1fr}}
@media(max-width:600px){.lbs-feats,.lbs-acts{grid-template-columns:1fr}}
</style>";
}

// The last start/restart/reload result, read from the progress file the daemon
// writes (HaProxy.buildProgressRestart): a serialized {POURC, TEXT}.
function lbst_last_operation():string{
	$path=PROGRESS_DIR."/haproxy.progress";
	if(!is_file($path)){return "";}
	$a=@unserialize((string)@file_get_contents($path),array("allowed_classes"=>false));
	if(!is_array($a) || !isset($a["POURC"])){return "";}
	$pourc=intval($a["POURC"]);
	$text=htmlspecialchars((string)($a["TEXT"] ?? ""));
	$class="lbs-last-run";$ico="fas fa-spinner";
	if($pourc==100){$class="lbs-last-ok";$ico="fas fa-circle-check";}
	if($pourc>100){$class="lbs-last-err";$ico="fas fa-circle-exclamation";}
	$tpl=new template_admin();
	$when=$tpl->time_to_date(filemtime($path),true);
	return "<div class='lbs-last $class'><span><i class='$ico'></i>&nbsp;&nbsp;<strong>{lbst_last_operation}:</strong> $text</span>".
		"<span class='lbs-last-time'>".htmlspecialchars($when)."</span></div>";
}

function haproxy_jsaction($route,$progress):string{
	$tpl=new template_admin();
	$page=CurrentPageName();
	return $tpl->framework_buildjs($route,$progress,"$progress.log","progress-haproxy-restart",
		"LoadAjax('table-loader','$page?table=yes');");
}

// The service state. It used to come from framework/haproxy.php?main-status=yes,
// which shelled out to exec.status.php: the daemon builds the status now
// (/haproxy/status). Drawn with the page, then refreshed by RefreshInterval_js().
function service_status_html():string{
	$tpl=new template_admin();
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/haproxy/status"));
	if(json_last_error()>JSON_ERROR_NONE || !is_object($json)){
		return $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
	}
	if(!$json->Status){
		return $tpl->_ENGINE_parse_body($tpl->div_error($json->Error));
	}
	if(!property_exists($json,"Info")){
		return $tpl->_ENGINE_parse_body($tpl->div_error("Missing property Info"));
	}
	$ini=new Bs_IniHandler();
	$ini->loadString($json->Info);
	return $tpl->SERVICE_STATUS($ini,"APP_HAPROXY",haproxy_jsaction("/haproxy/restart","haproxy.progress"));
}

function service_status():bool{
	echo service_status_html();
	return true;
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	// Start/Restart/Reload report into haproxy.progress, Stop into
	// haproxy-stop.progress (HaProxy.buildProgressRestart / buildProgressStop).
	$jsrestart=haproxy_jsaction("/haproxy/restart","haproxy.progress");
	$jsreload=haproxy_jsaction("/haproxy/reload","haproxy.progress");
	$jsstop=haproxy_jsaction("/haproxy/stop","haproxy-stop.progress");
	$jsstart=haproxy_jsaction("/haproxy/start","haproxy.progress");

	$feats=array(
		array("fas fa-shield-halved","#f0fdfa","#0f766e","{lbst_feat_ha}","{lbst_feat_ha_text}"),
		array("fas fa-arrows-turn-to-dots","#ecfdf5","#047857","{lbst_feat_sessions}","{lbst_feat_sessions_text}"),
		array("fas fa-gauge-high","#e6eeed","#005447","{lbst_feat_capacity}","{lbst_feat_capacity_text}"),
		array("fas fa-puzzle-piece","#fffbeb","#b45309","{lbst_feat_integration}","{lbst_feat_integration_text}"),
	);
	$html[]="<div class='lbs-feats'>";
	foreach($feats as $f){
		$html[]="<div class='lbs-feat'><span class='lbs-feat-ico' style='background:$f[1];color:$f[2]'><i class='$f[0]'></i></span>".
			"<div><div class='lbs-feat-t'>$f[3]</div><div class='lbs-feat-x'>$f[4]</div></div></div>";
	}
	$html[]="</div>";

	$html[]="<div class='lbs-acts'>";
	$html[]="<a class='lbs-act lbs-act-teal' href=\"javascript:blur();\" OnClick=\"$jsrestart\"><i class='fas fa-rotate'></i> {restart}</a>";
	$html[]="<a class='lbs-act lbs-act-teal' href=\"javascript:blur();\" OnClick=\"$jsreload\"><i class='fas fa-bolt' style='color:#fcd34d'></i> {reload}</a>";
	$html[]="<a class='lbs-act lbs-act-red' href=\"javascript:blur();\" OnClick=\"$jsstop\"><i class='fas fa-circle-stop'></i> {stop_service}</a>";
	$html[]="<a class='lbs-act lbs-act-amber' href=\"javascript:blur();\" OnClick=\"$jsstart\"><i class='fas fa-circle-play'></i> {start_service}</a>";
	$html[]="</div>";
	$html[]=lbst_last_operation();

	$html[]="<div class='lbs-cols'>";
	$html[]="<div id='haproxy-service-status'>".service_status_html()."</div>";
	$html[]="<div class='lbs-card'><div class='lbs-card-head'>".
		"<div class='lbs-card-title'><i class='fas fa-sliders'></i> {lbst_engine_parameters}</div>".
		"<div class='lbs-card-sub'>{lbst_engine_parameters_explain}</div></div>".
		"<div id='haproxy-flat-config'></div></div>";
	$html[]="</div>";

	// Every 30 s: each pass asks the daemon for the status again.
	$html[]="<script>".$tpl->RefreshInterval_js("haproxy-service-status",$page,"service-status=yes",30)."\nLoadAjaxSilent('haproxy-flat-config','$page?flat-config=yes');</script>";
	echo lbst_style().$tpl->_ENGINE_parse_body(@implode("\n", $html));
}

// The global parameters, with the defaults haproxy.cfg is built with
// (HaProxy configBuilder.build): below 2000 connections means 2000, no object
// size means 10000 bytes.
function haproxy_settings():array{
    $s["HaClusterMaxConn"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterMaxConn"));
    $s["HaProxyCPUS"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaProxyCPUS"));
    $s["HaProxyMemoryCache"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaProxyMemoryCache"));
    $s["HaProxyMaxMemoryObjects"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaProxyMaxMemoryObjects"));
    if($s["HaProxyMaxMemoryObjects"]==0){$s["HaProxyMaxMemoryObjects"]=10000;}
    if($s["HaClusterMaxConn"]<2000){$s["HaClusterMaxConn"]=2000;}
    return $s;
}

function flat_config():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $s=haproxy_settings();

    $cpus="{auto} {performance}";
    if($s["HaProxyCPUS"]>0){$cpus=$s["HaProxyCPUS"]." {cpu}".($s["HaProxyCPUS"]>1?"s":"");}
    $cache="{disabled}";
    if($s["HaProxyMemoryCache"]>0){$cache=$s["HaProxyMemoryCache"]." MB";}

    $tpl->table_form_field_js("Loadjs('$page?config-js=yes')","AsSquidAdministrator");
    $tpl->table_form_field_text("{maxconn}||{lbst_maxconn_explain}",$s["HaClusterMaxConn"],ico_link);
    $tpl->table_form_field_text("{SquidCpuNumber}||{lbst_cpus_explain}",$cpus,ico_cpu);
    $tpl->table_form_field_text("{squid_cache_memory}||{lbst_cache_explain}",$cache,ico_memory);
    $tpl->table_form_field_text("{maximum_object_size_in_memory}||{lbst_objsize_explain}",$s["HaProxyMaxMemoryObjects"]." bytes",ico_database);
    echo $tpl->table_form_compile();
    return true;
}

function config_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{parameters}","$page?config-popup=yes",650);
}

function config_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $s=haproxy_settings();

    $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    if($CPU_NUMBER==0){
        $_cpuJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/cpu-number"));
        $CPU_NUMBER=(is_object($_cpuJson) && !empty($_cpuJson->success))?intval($_cpuJson->cpu_number):2;
    }
    // 0 lets haproxy pick the thread count itself: without it a server left on
    // automatic opened on "1 cpu", and {apply} pinned it there.
    $CPUz=array(0=>"{auto} {performance}");
    for($i=1;$i<$CPU_NUMBER+1;$i++){
        $CPUz[$i]="$i {cpu}".($i>1?"s":"");
    }

    $form[]=$tpl->field_numeric("HaClusterMaxConn","{maxconn}",$s["HaClusterMaxConn"],"{haproxy_maxconn}");
    $form[]=$tpl->field_array_hash($CPUz,"HaProxyCPUS","nonull:{SquidCpuNumber}",$s["HaProxyCPUS"],false,"{haproxy_nbproc}");
    $form[]=$tpl->field_numeric("HaProxyMemoryCache","{squid_cache_memory} (MB)",$s["HaProxyMemoryCache"],"");
    $form[]=$tpl->field_numeric("HaProxyMaxMemoryObjects","{maximum_object_size_in_memory} (bytes)",$s["HaProxyMaxMemoryObjects"],"");

    // On success: close, redraw the card, then reload the Load-Balancer so
    // haproxy.cfg picks the new values up (what the inline form used to do).
    $jsafter="dialogInstance2.close();LoadAjaxSilent('haproxy-flat-config','$page?flat-config=yes');".
        haproxy_jsaction("/haproxy/reload","haproxy.progress");

    $html[]=$tpl->div_explain("{parameters}||{haproxy_global_settings_explain}");
    $html[]=$tpl->form_outside("",$form,"","{apply}",$jsafter,"AsSquidAdministrator",true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
