<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/prefetch-info.inc"); __prefetchCommonSettings($GLOBALS["CLASS_SOCKETS"]);
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
// Server-side gate: the dashboard used to answer any authenticated session.
$hacdUsers=new usersMenus();
if(!$hacdUsers->AsSystemAdministrator && !$hacdUsers->AsSquidAdministrator){exit();}
if(isset($_POST["HaClusterID"])){save();exit;}
if(isset($_GET["page"])){page();exit;}
if(isset($_GET["purge"])){purge();exit;}
if(isset($_GET["purge-progress"])){purge_popup_js();exit;}
if(isset($_GET["purge-popup"])){purge_popup();exit;}
if(isset($_GET["flat"])){haclient_flat();exit;}
if(isset($_GET["status"])){Status();exit;}
if(isset($_GET["form"])){form_js();exit;}
if(isset($_GET["form-popup"])){form_popup();exit;}
if(isset($_GET["action"])){action();exit;}
if(isset($_POST["HaClusterClientDebug"])){hacluster_clientdebug_save();exit;}
if(isset($_GET["top"])){hacd_top();exit;}
if(isset($_GET["pool-out-js"])){hacd_pool_out_js();exit;}
if(isset($_POST["hacd-pool-out"])){hacd_pool_out();exit;}
if(isset($_GET["disconnect-js"])){hacd_disconnect_js();exit;}
if(isset($_POST["hacd-disconnect"])){hacd_disconnect_post();exit;}
start();

function purge(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog_confirm_action("{squid_purge_dns_explain}","none","none","Loadjs('$page?purge-progress=yes')");
}
function purge_popup_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{empty_cache}","$page?purge-popup=yes");
}

// "Put back in the pool" (Loadjs): answers JavaScript. Taking the node OUT goes through
// hacd_pool_out_js() -> confirmation -> POST hacd-pool-out.
function action():bool{
    $tpl=new template_admin();
    if(!hacd_can_write()){ $tpl->js_error("{ERROR_NO_PRIVS}"); return false; }
    header("content-type: application/x-javascript");
    $action=intval($_GET["action"]);
    if($action==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/client/start");
        admin_tracks("HaCluster client: node put back in the load-balancer pool");
    }
    echo hacd_refresh_js();
    return true;
}

function form_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
   return  $tpl->js_dialog1("{parameters}","$page?form-popup=yes");
}
function purge_popup(){
    $t=time();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/dnsfilterd.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/dnsfilterd.progress.log";
    $ARRAY["CMD"]="dnsfilterd.php?purge=yes";
    $ARRAY["TITLE"]="{empty_cache}";
    $ARRAY["AFTER"]="dialogInstance1s.close();LoadAjax('dashboard-dnsfilterd','$page?page=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=$t')";
    $html="<div id='$t'></div><script>$jsrestart</script>";
    echo $html;
}

function start(){
    $page=CurrentPageName();
    $html="<div id='dashboard-hacluster'></div>
<script>LoadAjax('dashboard-hacluster','$page?page=yes');</script>";

    echo $html;
}
function page(){

    $tpl=new template_admin();
    $page=CurrentPageName();

    // Not a member: one card, nothing else to show (no status, no settings, no actions).
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"))==0){
        echo hacd_style().$tpl->_ENGINE_parse_body(hacd_not_member());
        return;
    }
    $html[]="<div class='hacd'>";
    $html[]="<div id='hacd-top'></div>";
    $html[]="<table class='hacd-cols'><tr>
        <td class='hacd-left'>
            <div id='hacluster-client-status'></div>
            <div id='package-cluster-status'></div>
       </td>
        <td class='hacd-right'>
         <div id='hacluster-client-flat'></div>
        </td>
        </tr></table>";
    $html[]=hacd_danger_zone();
    $html[]="</div>";
    $js0=$tpl->RefreshInterval_js("hacd-top",$page,"top=yes");
    $js1=$tpl->RefreshInterval_js("hacluster-client-status",$page,"status=yes");
    $js3=$tpl->RefreshInterval_js("hacluster-client-flat",$page,"flat=yes");
    $js2=$tpl->RefreshInterval_js("package-cluster-status","fw.system.cluster.client.php","status=yes");

    $html[]="<script>";
    $html[]=$js0;
    $html[]=$js1;
    $html[]=$js2;
    $html[]=$js3;
    $html[]="</script>";
    echo hacd_style().$tpl->_ENGINE_parse_body($html);
}

function Status():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();


    $html[]="<div id='haclient-restart'></div>";
    $html[]=HaClusterClientStatus();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function HaClusterClientStatus():string{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $jsrestart=$tpl->framework_buildjs("/hacluster/client/restart",
        "haclient.progress",
        "haclient.progress.txt",
        "haclient-restart",
        "LoadAjax('hacluster-client-status','$page?status=yes');"
    );

    $removedb=$tpl->button_autnonome("{hacd_disconnect}","Loadjs('$page?disconnect-js=yes')","fas fa-trash","AsSystemAdministrator",250,"btn-danger");
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==0){
        return $tpl->_ENGINE_parse_body($tpl->widget_grey("{feature_disabled}", "{inactive2}"));
    }

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/client/status"));
    if (!$json->Status) {
        $html[] = $tpl->widget_rouge($json->Error, "{error}");
        $html[] = "<div class='center' style='margin:20px'>$removedb</div>";
        return $tpl->_ENGINE_parse_body($html);
    }

    $ini = new Bs_IniHandler();
    $ini->loadString($json->Info);
    $html[] = $tpl->SERVICE_STATUS($ini, "APP_HACLUSTER_CLIENT:width=400", $jsrestart);
    // Take out of / put back in the pool: in the hero. Disconnect: in the danger zone (confirmed).
    return $tpl->_ENGINE_parse_body($html);
}
function hacluster_clientdebug_save():bool{
    $tpl=new template_admin();
    if(!hacd_can_write()){ echo $tpl->_ENGINE_parse_body("{hacd_f_admin_only}"); return false; }
    $tpl->CLEAN_POST();
    $HaClusterClientDebug=intval($_POST["HaClusterClientDebug"])==1?1:0;
    $GLOBALS["CLASS_SOCKETS"]->HACLUSTERCLIENT_API("/debug/$HaClusterClientDebug");
    return admin_tracks("Set HaCluster Client debug mode to $HaClusterClientDebug");
}

function haclient_flat(){
    echo hacd_cards();
}


function form_popup(){
    $page=currentPageName();
    $tpl=new template_admin();
    if(!hacd_can_write()){ echo $tpl->_ENGINE_parse_body($tpl->div_error("{hacd_f_admin_only}")); return; }
    $S=$GLOBALS["CLASS_SOCKETS"];
    $form=array();
    $form[]=$tpl->field_info("HaClusterID","{hacd_cluster_id}",$S->GET_INFO("HaClusterID"),"{hacd_f_ro}");
    $form[]=$tpl->field_text("HaClusterIP","{hacd_f_lb}",$S->GET_INFO("HaClusterIP"),false,"{hacd_f_lb_help}");
    $form[]=$tpl->field_interfaces("HaClusterClientInterface","{outgoing_interface}",$S->GET_INFO("HaClusterClientInterface"),"{hacd_f_iface_help}");
    $form[]=$tpl->field_section("{active_directory}");
    $form[]=$tpl->field_email("KerberosUsername","{hacd_f_account}",$S->GET_INFO("KerberosUsername"),false,"{hacd_f_account_help}");
    // The stored password is never sent back to the browser: empty keeps the current one.
    $form[]=$tpl->field_password2("KerberosPassword","{password}","",false,"{hacd_f_password_help}");
    $form[]=$tpl->field_text("kerberosActiveDirectoryHost","{hacd_f_dc}",$S->GET_INFO("kerberosActiveDirectoryHost"),false,"{ad_quick_1}");
    $form[]=$tpl->field_text("kerberosActiveDirectory2Host","{hacd_f_dc2}",$S->GET_INFO("kerberosActiveDirectory2Host"),false,"{hacd_f_dc2_help}");
    $form[]=$tpl->field_text("kerberosActiveDirectorySuffix","{hacd_f_suffix}",trim((string)$S->GET_INFO("kerberosActiveDirectorySuffix")),false,"{hacd_f_suffix_help}");
    $form[]=$tpl->field_text("kerberosRealm","{hacd_f_realm}",$S->GET_INFO("kerberosRealm"),false,"{hacd_f_realm_help}");
    $form[]=$tpl->field_info("KerberosSPN","{hacd_f_spn}",$S->GET_INFO("KerberosSPN"),"{hacd_f_ro}");

    $html=array();
    $html[]="<div class='hacdf-pushnote'><i class='fas fa-circle-info'></i> {hacd_f_push_note}</div>";
    $html[]=$tpl->form_outside("{parameters}",$form,null,"{apply}","LoadAjaxSilent('hacluster-client-flat','$page?flat=yes');","AsSystemAdministrator",true);
    echo "<style>.hacdf-pushnote{background:#fffbeb;border:1px solid #fde68a;color:#78350f;border-radius:10px;padding:10px 14px;font-size:13px;margin:0 0 12px;line-height:1.5}</style>".$tpl->_ENGINE_parse_body($html);
}

// Only the keys of the form are written (it used to save any posted key), and only by a system administrator.
function save(){
    $tpl=new template_admin();
    if(!hacd_can_write()){ echo $tpl->_ENGINE_parse_body("{hacd_f_admin_only}"); return; }
    $tpl->CLEAN_POST();
    $allowed=array("HaClusterIP","HaClusterClientInterface","KerberosUsername","KerberosPassword",
        "kerberosActiveDirectoryHost","kerberosActiveDirectory2Host","kerberosActiveDirectorySuffix","kerberosRealm");
    $keys=array();
    $track=array();
    foreach($allowed as $k){
        if(!isset($_POST[$k])){ continue; }
        $v=trim((string)$_POST[$k]);
        if($k==="KerberosPassword" && $v===""){ continue; }
        $keys[]=array("key"=>$k,"value"=>$v);
        $track[]="$k: ".admin_tracks_mask_value($k,$v);
    }
    if(count($keys)>0){
        $GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/setinfo",array("Keys"=>$keys));
    }
    admin_tracks("HaCluster client: attachment settings saved (".implode(", ",$track).")");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/client/setup");
}




// ============================================================================================
// Client node dashboard (maquette stitch_hacluster_client_node_dashboard).
// The load balancer probes this node on TCP 27899; the client answers "up", "50%" or "down",
// re-evaluated every 10 s (HaClusterClient health_evaluator.go). /status gives that answer, the
// number of probes received since the daemon started (NOT user connections), the configured
// thresholds (never the current load) and the last error. The detailed reason of a "down" is
// only written to a state file of the node: it is not shown here.
// ============================================================================================

function hacd_can_write():bool{
    $users=new usersMenus();
    return !empty($users->AsSystemAdministrator);
}
// Operator/daemon text for HTML: escaped, braces neutralised for the token engine.
function hacd_h($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"));
}
// Token with %s: translated first, then substituted.
function hacd_fmt(string $token,string $v):string{
    $tpl=new template_admin();
    return str_replace("%s",$v,$tpl->_ENGINE_parse_body("{".$token."}"));
}
function hacd_refresh_js():string{
    $page=CurrentPageName();
    return "LoadAjaxSilent('hacd-top','$page?top=yes');LoadAjaxSilent('hacluster-client-status','$page?status=yes');";
}

// One read of everything the top of the page shows.
function hacd_model():array{
    $S=$GLOBALS["CLASS_SOCKETS"];
    $m=array("ok"=>false,"error"=>"","st"=>array(),"manual"=>false,"run_ok"=>false,"report_error"=>"","ping_error"=>"");
    $raw=$S->HACLUSTERCLIENT_API("/status");
    $st=json_decode((string)$raw,true);
    if(is_array($st) && !empty($st["Status"])){ $m["ok"]=true; $m["st"]=$st; }
    else{ $m["error"]=is_array($st)&&isset($st["Error"])?(string)$st["Error"]:"{hacd_status_unreadable}"; }
    $run=json_decode((string)$S->REST_API("/hacluster/client/run"),true);
    if(is_array($run) && !empty($run["Status"])){ $m["run_ok"]=true; $m["manual"]=intval($run["Rule"] ?? 1)==0; }
    $f="/usr/share/artica-postfix/ressources/logs/web/HaClusterClient.report.error";
    if(is_file($f)){ $m["report_error"]=trim((string)@file_get_contents($f)); }
    $ping=json_decode((string)$S->REST_API("/hacluster/client/server/ping"),true);
    if(!is_array($ping) || empty($ping["Status"])){
        $e=is_array($ping)&&isset($ping["Error"])?(string)$ping["Error"]:"";
        $m["ping_error"]=str_replace("ClusterClientHTTP.go[acluster/ClusterClientHTTP.HTTPGetData:","[",$e);
        if($m["ping_error"]===""){ $m["ping_error"]="?"; }
    }
    return $m;
}
// Pool state: manual (stop file) wins, then the answer given to the load balancer.
function hacd_state(array $m):string{
    if($m["manual"]){ return "manual"; }
    if(!$m["ok"]){ return "unknown"; }
    $r=strtolower(trim((string)($m["st"]["HaClusterStatus"] ?? "")));
    if($r==="up"){ return "in"; }
    if($r==="50%"){ return "half"; }
    return "out";
}
function hacd_ago(int $ts):string{
    $d=time()-$ts;
    if($d<60){ return "{hacd_ago_now}"; }
    if($d<3600){ return hacd_fmt("hacd_ago_min",(string)intval(floor($d/60))); }
    if($d<86400){ return hacd_fmt("hacd_ago_h",(string)intval(floor($d/3600))); }
    return hacd_fmt("hacd_ago_d",(string)intval(floor($d/86400)));
}

// ---- Widgets ---------------------------------------------------------------------------------
function hacd_ping_banner(array $m):string{
    if($m["ping_error"]===""){ return ""; }
    return "<div class='ak-banner ak-err hacd-ping' role='alert'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i>"
        ."<div class='ak-banner-m'><strong>{hacd_ping_title}</strong> ".hacd_h($m["ping_error"])."</div></div>";
}
function hacd_hero(array $m):string{
    $page=CurrentPageName();
    $map=array(
        "in"=>array("hacd-in","fa-circle-check","{hacd_in}","{hacd_in_txt}"),
        "half"=>array("hacd-half","fa-circle-half-stroke","{hacd_half}","{hacd_half_txt}"),
        "out"=>array("hacd-out","fa-circle-xmark","{hacd_out}","{hacd_out_txt}"),
        "manual"=>array("hacd-manual","fa-circle-pause","{hacd_manual}","{hacd_manual_txt}"),
        "unknown"=>array("hacd-unknown","fa-circle-question","{hacd_unknown}","{hacd_unknown_txt}"),
    );
    $state=hacd_state($m);
    list($cls,$ico,$title,$txt)=$map[$state];
    $h=array();
    $h[]="<div class='hacd-hero $cls' role='status' aria-live='polite'>";
    $h[]="<div class='hacd-hero-m'><div class='hacd-hero-t'><i class='fas $ico' aria-hidden='true'></i> $title</div>";
    $h[]="<div class='hacd-hero-s'>$txt</div>";
    if($state==="unknown" && $m["error"]!==""){
        $h[]="<div class='hacd-hero-e'><i class='fas fa-circle-info' aria-hidden='true'></i> ".hacd_h($m["error"])."</div>";
    }
    $err=$m["ok"]?trim((string)($m["st"]["LastError"] ?? "")):"";
    if(strlen($err)>3){
        $err=str_replace("HaClusterClient.go","",$err);
        $when=intval($m["st"]["LastErrorTime"] ?? 0);
        $prefix=$when>0?hacd_fmt("hacd_last_error",hacd_ago($when)):"{hacd_last_error_nodate}";
        $h[]="<div class='hacd-hero-e'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> $prefix ".hacd_h($err)."</div>";
    }
    if($m["report_error"]!==""){
        $h[]="<div class='hacd-hero-e'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> {hacd_report_error} ".hacd_h($m["report_error"])."</div>";
    }
    $h[]="</div>";
    if(hacd_can_write() && $m["run_ok"]){
        if($m["manual"]){
            $h[]="<div class='hacd-hero-a'><button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('$page?action=1')\"><i class='fas fa-circle-play'></i> {hacd_put_back}</button>"
                ."<div class='hacd-hero-ah'>{hacd_put_back_help}</div></div>";
        }else{
            $h[]="<div class='hacd-hero-a'><button type='button' class='ak-btn' OnClick=\"Loadjs('$page?pool-out-js=yes')\"><i class='fas fa-circle-pause'></i> {hacd_take_out}</button>"
                ."<div class='hacd-hero-ah'>{hacd_take_out_help}</div></div>";
        }
    }
    $h[]="</div>";
    return implode("",$h);
}
function hacd_tile(string $label,string $value,string $sub):string{
    return "<div class='hacd-tile'><div class='hacd-tile-l'>$label</div><div class='hacd-tile-v'>$value</div><div class='hacd-tile-s'>$sub</div></div>";
}
function hacd_tile_probes(array $m):string{
    $v=$m["ok"]?number_format(intval($m["st"]["Connection"] ?? 0),0,"."," "):"—";
    return hacd_tile("{hacd_probes}",$v,"{hacd_probes_sub}");
}
function hacd_tile_load(array $m):string{
    if(!$m["ok"]){ return hacd_tile("{hacd_load}","—","&nbsp;"); }
    $st=$m["st"];
    $p=intval($st["HaClusterClientMaxLoadPeriod"] ?? 0);
    $per=$p<5?"{hacd_realtime}":hacd_fmt("hacd_avg_min",(string)$p);
    $sub=$per." · ".hacd_fmt("hacd_emergency",(string)intval($st["HaClusterClientEmergencyLoad"] ?? 0));
    return hacd_tile("{hacd_load}",(string)intval($st["HaClusterClientMaxLoad"] ?? 0),$sub);
}
function hacd_tile_members(array $m):string{
    if(!$m["ok"]){ return hacd_tile("{hacd_members}","—","&nbsp;"); }
    $n=intval($m["st"]["HaClusterClientMaxClient"] ?? 0);
    return hacd_tile("{hacd_members}",$n>0?(string)$n:"{hacd_no_limit}","{hacd_members_sub}");
}
function hacd_tile_agent(array $m):string{
    $port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClientListenPort"));
    if($port==0){ $port=58787; }
    $v="—";
    if($m["ok"] && array_key_exists("HTTPServerRunning",$m["st"])){
        $v=!empty($m["st"]["HTTPServerRunning"])
            ?"<span class='hacd-ok'><i class='fas fa-circle-check' aria-hidden='true'></i> ".hacd_fmt("hacd_listening",(string)$port)."</span>"
            :"<span class='hacd-bad'><i class='fas fa-circle-xmark' aria-hidden='true'></i> ".hacd_fmt("hacd_not_listening",(string)$port)."</span>";
    }
    return hacd_tile("{hacd_agent}",$v,"{hacd_agent_sub}");
}

// ---- Top fragment (auto-refreshed) -------------------------------------------------------------
function hacd_top():bool{
    $tpl=new template_admin();
    // Asks the client to ping the load balancer in the background (kept from the former settings card).
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/client/micro-ping");
    $m=hacd_model();
    $h=array();
    $h[]=hacd_ping_banner($m);
    $h[]=hacd_hero($m);
    $h[]="<div class='hacd-tiles'>".hacd_tile_probes($m).hacd_tile_load($m).hacd_tile_members($m).hacd_tile_agent($m)."</div>";
    echo $tpl->_ENGINE_parse_body(implode("",$h));
    return true;
}

// ---- Right column (?flat=yes): what keeps this node in the pool --------------------------------
// Stitch "cluster_node_dashboard_redesign". Data: HaClusterClient /status (WebService.GetData) and
// the settings pushed by the load balancer. No per-URL result, no AD reachability: none is exposed.
function hacd_cards():string{
    $tpl=new template_admin();
    $S=$GLOBALS["CLASS_SOCKETS"];
    $st=json_decode((string)$S->HACLUSTERCLIENT_API("/status"),true);
    $ok=is_array($st) && !empty($st["Status"]);
    if(!$ok){ $st=array(); }
    $h=array();
    $h[]="<div class='hacdf'>";
    $h[]=hacdf_intro();
    $h[]=hacdf_checks($ok,$st);
    $h[]=hacdf_attach($ok,$st);
    $h[]=hacdf_ad();
    $h[]="</div>";
    return hacdf_style().$tpl->_ENGINE_parse_body(implode("",$h)).hacdf_js();
}

function hacdf_val($v):string{
    $v=trim((string)$v);
    if($v===""){ return "<span class='hacdf-notset'>{hacd_not_set}</span>"; }
    return "<span class='hacdf-mono'>".hacd_h($v)."</span>";
}

function hacdf_intro():string{
    return "<div class='hacdf-intro'><span class='hacdf-ico'><i class='fas fa-diagram-project' aria-hidden='true'></i></span>"
        ."<div class='hacdf-intro-m'><div class='hacdf-intro-t'>{hacd_f_intro_title}</div><div class='hacdf-intro-s'>{hacd_f_intro_text}</div></div>"
        ."<span class='hacdf-chip'><i class='fas fa-arrows-rotate' aria-hidden='true'></i> {hacd_f_managed}</span></div>";
}

function hacdf_card_head(string $ico,string $title,string $text,string $btn=""):string{
    return "<div class='hacdf-head'><span class='hacdf-ico'><i class='$ico' aria-hidden='true'></i></span>"
        ."<div class='hacdf-head-m'><div class='hacdf-title'>$title</div><div class='hacdf-sub'>$text</div></div>$btn</div>";
}

function hacdf_pill(bool $on):string{
    return $on?"<span class='hacdf-pill hacdf-pill-on'><i class='fas fa-check' aria-hidden='true'></i> {hacd_f_on}</span>"
        :"<span class='hacdf-pill hacdf-pill-off'><i class='fas fa-minus' aria-hidden='true'></i> {hacd_f_off}</span>";
}

function hacdf_rule(string $ico,string $title,string $tech,bool $on,string $body):string{
    return "<div class='hacdf-rule'><span class='hacdf-rule-ico'><i class='$ico' aria-hidden='true'></i></span>"
        ."<div class='hacdf-rule-m'><div class='hacdf-rule-t'>$title <span class='hacdf-tech'>$tech</span></div>$body</div>".hacdf_pill($on)."</div>";
}

function hacdf_checks(bool $ok,array $st):string{
    $h=array();
    $h[]="<div class='hacdf-card'>".hacdf_card_head("fas fa-stethoscope","{hacd_checks}","{hacd_f_checks_text}");
    if(!$ok){
        $h[]="<div class='hacdf-body'><div class='hacdf-warn'><i class='fas fa-circle-info' aria-hidden='true'></i> {hacd_status_unreadable}</div></div></div>";
        return implode("",$h);
    }
    $h[]="<div class='hacdf-body'>";
    $cpu=intval($st["HaClusterForceAgentForceMoniCPU"] ?? 0)==1;
    $h[]=hacdf_rule("fas fa-microchip","{hacd_f_cpu}","{hacd_f_cpu_tech}",$cpu,"<div class='hacdf-cons'>".($cpu?"{hacd_f_cpu_on}":"{hacd_f_cpu_off}")."</div>");
    $urls=(isset($st["Urls"]) && is_array($st["Urls"]))?$st["Urls"]:array();
    if(count($urls)>0){
        $chips=array();
        foreach($urls as $u){ $chips[]="<span class='hacdf-url'>".hacd_h($u)."</span>"; }
        $body="<div class='hacdf-urls'>".implode("",$chips)."</div><div class='hacdf-cons'>{hacd_f_urls_on}</div>";
    }else{
        $body="<div class='hacdf-cons'>{hacd_f_urls_off}</div>";
    }
    $h[]=hacdf_rule("fas fa-globe","{hacd_f_urls}","{hacd_f_urls_tech}",count($urls)>0,$body);
    $itc=intval($st["ITCharter"] ?? 0)==1;
    $h[]=hacdf_rule("fas fa-file-signature","{hacd_f_itc}","{hacd_f_itc_tech}",$itc,"<div class='hacdf-cons'>".($itc?"{hacd_f_itc_on}":"{hacd_f_itc_off}")."</div>");
    $h[]=hacdf_debug(intval($st["HaClusterClientDebug"] ?? 0)==1);
    $h[]="</div></div>";
    return implode("",$h);
}

function hacdf_debug(bool $on):string{
    $can=hacd_can_write();
    $sw="<button type='button' class='hacdf-switch".($on?" hacdf-switch-on":"")."' role='switch' aria-checked='".($on?"true":"false")."' aria-label='{debug_mode}'"
        .($can?" OnClick=\"HacdfDebug(".($on?0:1).");\"":" disabled title=\"{hacd_f_admin_only}\"")."><span></span></button>";
    return "<div class='hacdf-debug'><span class='hacdf-rule-ico hacdf-ico-amber'><i class='fas fa-bug' aria-hidden='true'></i></span>"
        ."<div class='hacdf-rule-m'><div class='hacdf-rule-t'>{debug_mode} ".hacdf_pill($on)."</div><div class='hacdf-cons'>{hacd_f_debug_help}</div>"
        .($can?"":"<div class='hacdf-cons hacdf-why'>{hacd_f_admin_only}</div>")."</div>$sw</div>";
}

function hacdf_grid_row(string $label,string $tech,string $value):string{
    return "<div class='hacdf-dl'><div class='hacdf-dl-l'>$label".($tech!==""?"<div class='hacdf-tech'>$tech</div>":"")."</div><div class='hacdf-dl-v'>$value</div></div>";
}

function hacdf_attach(bool $ok,array $st):string{
    $page=CurrentPageName();
    $S=$GLOBALS["CLASS_SOCKETS"];
    $btn=hacd_can_write()?"<button type='button' class='ak-btn hacdf-btn' OnClick=\"Loadjs('$page?form=yes')\"><i class='fas fa-sliders'></i> {parameters}</button>":"";
    $iface=trim((string)$S->GET_INFO("HaClusterClientInterface"));
    $h=array();
    $h[]="<div class='hacdf-card'>".hacdf_card_head("fas fa-diagram-project","{hacd_attach}","{hacd_attach_explain}",$btn);
    $h[]="<div class='hacdf-body'><div class='hacdf-grid'>";
    $h[]=hacdf_grid_row("{hacd_f_lb}","",hacdf_val($S->GET_INFO("HaClusterIP")));
    $h[]=hacdf_grid_row("{hacd_f_source}","{hacd_f_source_tech}",hacdf_val($S->GET_INFO("HaClusterMyMasterAddr")));
    $h[]=hacdf_grid_row("{hacd_cluster_id}","",hacdf_val($S->GET_INFO("HaClusterID")));
    $h[]=hacdf_grid_row("{outgoing_interface}","",strlen($iface)>2?hacdf_val($iface):"<span class='hacdf-plain'>{hacd_all_interfaces}</span>");
    $h[]=hacdf_grid_row("{hacd_f_version}","",$ok?hacdf_val($st["version"] ?? ""):"&mdash;");
    $h[]="</div>".hacdf_cert($ok,$st)."</div></div>";
    return implode("",$h);
}

function hacdf_cert(bool $ok,array $st):string{
    $pem=$ok?trim((string)($st["ClientCertificate"] ?? "")):"";
    $badge="<span class='hacdf-badge hacdf-b-muted'><i class='fas fa-circle-minus' aria-hidden='true'></i> {hacd_no_cert}</span>";
    $cells="";
    if($pem!==""){
        $d=@openssl_x509_parse($pem);
        if(!is_array($d)){
            $badge="<span class='hacdf-badge hacdf-b-bad'><i class='fas fa-circle-xmark' aria-hidden='true'></i> {hacd_cert_unreadable}</span>";
        }else{
            $cn=(string)($d["subject"]["CN"] ?? "");
            $iss=(string)($d["issuer"]["CN"] ?? ($d["issuer"]["O"] ?? ""));
            $to=intval($d["validTo_time_t"] ?? 0);
            $days=$to>0?intval(floor(($to-time())/86400)):0;
            if($to>0 && $to<time()){
                $badge="<span class='hacdf-badge hacdf-b-bad'><i class='fas fa-circle-xmark' aria-hidden='true'></i> {hacd_f_cert_expired}</span>";
                $rel="<span class='hacdf-t-bad'>{hacd_f_cert_expired}</span>";
            }elseif($to>0 && $days<30){
                $badge="<span class='hacdf-badge hacdf-b-warn'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> ".hacd_fmt("hacd_f_cert_soon",(string)$days)."</span>";
                $rel="<span class='hacdf-t-warn'>(".hacd_fmt("hacd_f_in_days",(string)$days).")</span>";
            }else{
                $badge="<span class='hacdf-badge hacdf-b-ok'><i class='fas fa-circle-check' aria-hidden='true'></i> {hacd_f_cert_valid}</span>";
                $rel=$to>0?"<span class='hacdf-t-ok'>(".hacd_fmt("hacd_f_in_days",(string)$days).")</span>":"";
            }
            $cells="<div class='hacdf-cert-cells'>"
                ."<div class='hacdf-cell'><div class='hacdf-cell-l'>{hacd_f_cert_cn}</div>".hacdf_val($cn)."</div>"
                ."<div class='hacdf-cell'><div class='hacdf-cell-l'>{hacd_f_cert_issuer}</div>".hacdf_val($iss)."</div>"
                ."<div class='hacdf-cell'><div class='hacdf-cell-l'>{hacd_f_cert_until}</div><div class='hacdf-cell-r'>".($to>0?"<span class='hacdf-mono'>".date("Y-m-d",$to)."</span>":"&mdash;")." ".(isset($rel)?$rel:"")."</div></div>"
                ."</div>";
        }
    }
    return "<div class='hacdf-cert'><div class='hacdf-cert-h'><span class='hacdf-cert-t'><i class='fas fa-id-card' aria-hidden='true'></i> {hacd_f_cert_title} <span class='hacdf-tech'>{hacd_f_cert_tech}</span></span>$badge</div>"
        .$cells."<div class='hacdf-cons'>{hacd_f_cert_help}</div></div>";
}

function hacdf_ad():string{
    $S=$GLOBALS["CLASS_SOCKETS"];
    $dc=trim((string)$S->GET_INFO("kerberosActiveDirectoryHost"));
    $h=array();
    $h[]="<div class='hacdf-card'>".hacdf_card_head("fas fa-building-user","{active_directory}","{hacd_f_ad_text}");
    $h[]="<div class='hacdf-body'>";
    if($dc===""){
        $h[]="<div class='hacdf-note'><i class='fas fa-circle-info' aria-hidden='true'></i> {hacd_f_ad_unused}</div>";
    }else{
        $h[]="<div class='hacdf-list'>";
        $h[]=hacdf_grid_row("{hacd_f_dc}","",hacdf_val($dc));
        $h[]=hacdf_grid_row("{hacd_f_dc2}","",hacdf_val($S->GET_INFO("kerberosActiveDirectory2Host")));
        $h[]=hacdf_grid_row("{hacd_f_suffix}","",hacdf_val($S->GET_INFO("kerberosActiveDirectorySuffix")));
        $h[]=hacdf_grid_row("{hacd_f_spn}","{hacd_f_spn_tech}",hacdf_val($S->GET_INFO("KerberosSPN")));
        $h[]=hacdf_grid_row("{hacd_f_realm}","{hacd_f_realm_tech}",hacdf_val($S->GET_INFO("kerberosRealm")));
        $h[]=hacdf_grid_row("{hacd_f_account}","",hacdf_val($S->GET_INFO("KerberosUsername")));
        $h[]="</div>";
    }
    $h[]="</div></div>";
    return implode("",$h);
}

function hacdf_js():string{
    $page=CurrentPageName();
    return "<script>
function HacdfDebug(v){
  $.post('$page',{HaClusterClientDebug:v},function(r){
    r=String(r||'').trim();
    if(r.length>0){ alert(r); }
    LoadAjaxSilent('hacluster-client-flat','$page?flat=yes');
  });
}
</script>";
}

function hacdf_style():string{
    if(isset($GLOBALS["HACDF_CSS"])){ return ""; }
    $GLOBALS["HACDF_CSS"]=true;
    $A="#047857"; $AS="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_dark"])){ $A=$a["accent_dark"]; }
        if(!empty($a["accent_soft"])){ $AS=$a["accent_soft"]; }
    }
    return "<style>
.hacdf{display:flex;flex-direction:column;gap:16px;color:#111d23;font-size:14px}
.hacdf *{box-sizing:border-box}
.hacdf-intro,.hacdf-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px}
.hacdf-intro{display:flex;align-items:center;gap:14px;padding:16px 20px}
.hacdf-intro-m{flex:1 1 auto;min-width:0}
.hacdf-intro-t{font-size:16px;font-weight:600}
.hacdf-intro-s{font-size:13px;color:#3d4947;margin-top:2px;line-height:1.5}
.hacdf-chip{display:inline-flex;align-items:center;gap:7px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:999px;padding:5px 12px;font-size:13px;color:#3d4947;white-space:nowrap}
.hacdf-chip svg,.hacdf-chip i{color:$A}
.hacdf-ico{flex:0 0 36px;height:36px;border-radius:9px;background:$AS;color:$A;display:flex;align-items:center;justify-content:center}
.hacdf-head{display:flex;align-items:flex-start;gap:14px;padding:16px 20px;border-bottom:1px solid #f1f5f9}
.hacdf-head-m{flex:1 1 auto;min-width:0}
.hacdf-title{font-size:16px;font-weight:600}
.hacdf-sub{font-size:13px;color:#6d7a77;margin-top:2px;line-height:1.5}
.hacdf-btn{white-space:nowrap}
.hacdf-body{padding:16px 20px;display:flex;flex-direction:column;gap:12px}
.hacdf-rule,.hacdf-debug{display:flex;align-items:flex-start;gap:14px;border:1px solid #eef2f6;background:#fbfcfd;border-radius:10px;padding:14px 16px}
.hacdf-debug{background:#fff;border-style:dashed}
.hacdf-rule-ico{flex:0 0 32px;height:32px;border-radius:8px;background:#fff;border:1px solid #e2e8f0;color:#3d4947;display:flex;align-items:center;justify-content:center}
.hacdf-ico-amber{background:#fffbeb;border-color:#fde68a;color:#b45309}
.hacdf-rule-m{flex:1 1 auto;min-width:0}
.hacdf-rule-t{font-size:14px;font-weight:600;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.hacdf-tech{font-size:13px;font-weight:400;color:#6d7a77}
.hacdf-cons{font-size:13px;color:#3d4947;margin-top:4px;line-height:1.5}
.hacdf-why{color:#865300}
.hacdf-pill{display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:999px;font-size:13px;font-weight:600;white-space:nowrap;border:1px solid}
.hacdf-pill-on{background:#ecfdf5;color:$A;border-color:#bbf7d0}
.hacdf-pill-off{background:#f1f5f9;color:#3d4947;border-color:#e2e8f0}
.hacdf-urls{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.hacdf-url{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:3px 8px;overflow-wrap:anywhere}
.hacdf-switch{flex:0 0 44px;width:44px;height:24px;border-radius:999px;background:#cbd5e1;border:0;position:relative;cursor:pointer;padding:0}
.hacdf-switch span{position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:left .15s}
.hacdf-switch-on{background:$A}
.hacdf-switch-on span{left:23px}
.hacdf-switch[disabled]{opacity:.5;cursor:not-allowed}
.hacdf-switch:focus-visible,.hacdf-btn:focus-visible{outline:2px solid $A;outline-offset:2px}
.hacdf-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 28px}
.hacdf-list{display:flex;flex-direction:column}
.hacdf-dl{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:10px 0;border-bottom:1px solid #f1f5f9;min-width:0}
.hacdf-dl-l{font-size:13px;color:#3d4947;font-weight:600}
.hacdf-dl-l .hacdf-tech{font-weight:400}
.hacdf-dl-v{text-align:right;min-width:0;overflow-wrap:anywhere}
.hacdf-mono{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;color:#111d23}
.hacdf-plain{font-size:13px;color:#111d23}
.hacdf-notset{color:#6d7a77;font-style:italic;font-size:13px}
.hacdf-cert{border:1px solid #e2e8f0;background:#fbfcfd;border-radius:10px;padding:14px 16px}
.hacdf-cert-h{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.hacdf-cert-t{font-size:14px;font-weight:600}
.hacdf-cert-t svg,.hacdf-cert-t i{color:$A;margin-right:4px}
.hacdf-cert-cells{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:12px}
.hacdf-cell{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;min-width:0;overflow-wrap:anywhere}
.hacdf-cell-l{font-size:13px;color:#6d7a77;margin-bottom:2px}
.hacdf-cell-r{display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;font-size:13px}
.hacdf-badge{display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:999px;font-size:13px;font-weight:600;border:1px solid}
.hacdf-b-ok{background:#ecfdf5;color:$A;border-color:#bbf7d0}.hacdf-b-warn{background:#fffbeb;color:#865300;border-color:#fde68a}
.hacdf-b-bad{background:#fef2f2;color:#ba1a1a;border-color:#fecaca}.hacdf-b-muted{background:#f1f5f9;color:#3d4947;border-color:#e2e8f0}
.hacdf-t-ok{color:$A}.hacdf-t-warn{color:#865300}.hacdf-t-bad{color:#ba1a1a}
.hacdf-note{font-size:14px;color:#3d4947}
.hacdf-warn{font-size:13px;color:#865300}
@media(max-width:1200px){.hacdf-grid{grid-template-columns:minmax(0,1fr)}.hacdf-cert-cells{grid-template-columns:minmax(0,1fr)}}
</style>";
}

function hacd_danger_zone():string{
    $page=CurrentPageName();
    $btn=hacd_can_write()
        ?"<button type='button' class='ak-btn hacd-danger-btn' OnClick=\"Loadjs('$page?disconnect-js=yes')\"><i class='fas fa-trash-can'></i> {hacd_disconnect}</button>"
        :"<button type='button' class='ak-btn hacd-danger-btn' disabled title=\"{ERROR_NO_PRIVS}\"><i class='fas fa-trash-can'></i> {hacd_disconnect}</button>";
    return "<div class='hacd-danger'><div class='hacd-danger-m'><div class='hacd-danger-t'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> {hacd_danger_title}</div>"
        ."<div class='hacd-danger-s'>{hacd_danger_txt}</div><div id='hacd-uninstall-progress'></div></div>$btn</div>";
}
function hacd_not_member():string{
    return "<div class='hacd-none'><span class='hacd-none-ico'><i class='fas fa-diagram-project' aria-hidden='true'></i></span>"
        ."<div class='hacd-none-t'>{hacd_not_member}</div><div class='hacd-none-s'>{feature_disabled}</div></div>";
}

// ---- Confirmed actions ---------------------------------------------------------------------------
function hacd_pool_out_js():bool{
    $tpl=new template_admin();
    if(!hacd_can_write()){ $tpl->js_error("{ERROR_NO_PRIVS}"); return false; }
    return $tpl->js_dialog_confirm_action("{hacd_take_out_confirm}","hacd-pool-out","none",hacd_refresh_js());
}
function hacd_pool_out():bool{
    $tpl=new template_admin();
    if(!hacd_can_write()){ echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}"); return false; }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/client/stop");
    return admin_tracks("HaCluster client: node taken out of the load-balancer pool");
}
function hacd_disconnect_js():bool{
    $tpl=new template_admin();
    if(!hacd_can_write()){ $tpl->js_error("{ERROR_NO_PRIVS}"); return false; }
    $uninstall=$tpl->framework_buildjs("/hacluster/client/uninstall","haclient.progress","haclient.progress.txt",
        "hacd-uninstall-progress","window.location.reload();");
    return $tpl->js_dialog_confirm_action("{hacd_disconnect_confirm}","hacd-disconnect","none",$uninstall);
}
// The confirmation only checks the right; the uninstall itself runs through the progress bar.
function hacd_disconnect_post():bool{
    $tpl=new template_admin();
    if(!hacd_can_write()){ echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}"); return false; }
    return admin_tracks("HaCluster client: disconnection from the cluster confirmed");
}

function hacd_style():string{
    if(isset($GLOBALS["HACD_CSS"])){ return ""; }
    $GLOBALS["HACD_CSS"]=true;
    $A="#047857"; $AS="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_dark"])){ $A=$a["accent_dark"]; }
        if(!empty($a["accent_soft"])){ $AS=$a["accent_soft"]; }
    }
    return "<style>
.hacd{margin-top:15px}
.hacd *{box-sizing:border-box}
.hacd-ping{margin-bottom:14px}
.hacd-hero{display:flex;align-items:center;gap:24px;background:#fff;border:1px solid #e2e8f0;border-left:6px solid #94a3b8;border-radius:12px;padding:20px 24px;margin-bottom:16px}
.hacd-hero-m{flex:1 1 auto;min-width:0}
.hacd-hero-t{font-size:24px;font-weight:700;color:#111d23;display:flex;align-items:center;gap:10px}
.hacd-hero-s{font-size:14px;color:#3d4947;margin-top:4px}
.hacd-hero-e{font-size:13px;color:#865300;margin-top:8px;word-break:break-word}
.hacd-in{border-left-color:$A}.hacd-in .hacd-hero-t>svg,.hacd-in .hacd-hero-t>i{color:$A}
.hacd-half{border-left-color:#d97706}.hacd-half .hacd-hero-t>svg,.hacd-half .hacd-hero-t>i{color:#865300}
.hacd-out{border-left-color:#ba1a1a}.hacd-out .hacd-hero-t>svg,.hacd-out .hacd-hero-t>i{color:#ba1a1a}
.hacd-manual .hacd-hero-t>svg,.hacd-manual .hacd-hero-t>i{color:#3d4947}
.hacd-unknown .hacd-hero-t>svg,.hacd-unknown .hacd-hero-t>i{color:#6d7a77}
.hacd-hero-a{flex:0 0 auto;max-width:300px;text-align:right}
.hacd-hero-ah{font-size:13px;color:#6d7a77;margin-top:6px}
.hacd-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:16px}
.hacd-tile{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:16px 18px;min-width:0}
.hacd-tile-l{font-size:13px;font-weight:600;color:#3d4947;text-transform:uppercase;letter-spacing:.04em}
.hacd-tile-v{font-size:24px;font-weight:700;color:#111d23;margin-top:6px;font-variant-numeric:tabular-nums}
.hacd-tile-s{font-size:13px;color:#6d7a77;margin-top:4px}
.hacd-ok{color:$A;font-size:18px}.hacd-bad{color:#ba1a1a}
.hacd-tile-v .hacd-bad{font-size:18px}
.hacd-cols{width:100%;border-collapse:separate}
.hacd-left{width:360px;vertical-align:top;padding-right:16px}
.hacd-right{vertical-align:top}
.hacd-urls{display:flex;flex-direction:column;gap:3px;align-items:flex-end}
.hacd-url{font-family:monospace;font-size:13px;word-break:break-all;text-align:right}
.hacd-notset{color:#6d7a77;font-style:italic}
.hacd-danger{display:flex;align-items:center;gap:24px;background:#fff;border:1px solid #ffdad6;border-radius:12px;padding:18px 24px;margin-top:16px}
.hacd-danger-m{flex:1 1 auto}
.hacd-danger-t{font-size:16px;font-weight:700;color:#ba1a1a;display:flex;align-items:center;gap:8px}
.hacd-danger-s{font-size:13px;color:#3d4947;margin-top:4px}
.hacd-danger-btn{color:#ba1a1a;border-color:#ba1a1a;background:#fff}
.hacd-danger-btn:hover{background:#ffdad6}
.hacd-none{display:flex;flex-direction:column;align-items:center;gap:8px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:40px 24px;margin-top:15px;text-align:center}
.hacd-none-ico{width:60px;height:60px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:26px;background:#f1f5f9;color:#6d7a77}
.hacd-none-t{font-size:18px;font-weight:600;color:#111d23}
.hacd-none-s{font-size:14px;color:#3d4947}
@media(max-width:1200px){.hacd-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}.hacd-left,.hacd-right{display:block;width:100%;padding-right:0}}
@media(prefers-reduced-motion:no-preference){.hacd-hero{transition:border-color .15s}}
</style>";
}


function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}