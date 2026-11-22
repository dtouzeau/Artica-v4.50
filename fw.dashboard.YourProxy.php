<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.sqstats.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(isset($_GET["page"])){page();exit;}
if(isset($_POST["none"])){exit;}
if(isset($_GET["purge"])){purge();exit;}
if(isset($_GET["purge-progress"])){purge_popup_js();exit;}
if(isset($_GET["purge-popup"])){purge_popup();exit;}


start();

function purge(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog_confirm_action("{empty_dns_cache_squid_explain}","none","none","Loadjs('$page?purge-progress=yes')");
}
function purge_popup_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{empty_cache}","$page?purge-popup=yes");
}
function purge_popup(){
    $t=time();
    $page=CurrentPageName();
    // articarest squid/dns_purge.go (ex exec.squid.purge.dns.php).
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/squid.dns.purge.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/squid.dns.purge.progress.log";
    $ARRAY["CMD"]="/proxy/dns/purge";
    $ARRAY["PRIV"]="AsSquidAdministrator";
    $ARRAY["TITLE"]="{empty_dns_cache}";
    $ARRAY["AFTER"]="dialogInstance1.close();LoadAjax('dashboard-proxy-status','$page?page=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=$t')";
    $html="<div id='$t'></div><script>$jsrestart</script>";
    echo $html;
}


function start(){
    $page=CurrentPageName();
    echo "<div id='dashboard-proxy-restart'></div><div id='dashboard-proxy-status'></div><script>LoadAjax('dashboard-proxy-status','$page?page=yes');</script>";
}


/* ---------------------------------------------------------------------------
 * Precision Network Monitor (PNM) dashboard
 *
 * Layout reference:  /home/claude/images/stitch_widget_graph_redesign/
 * Metrics endpoints: /home/claude/articarest/docs/IA_LOGFILE_DAEMON_METRICS.md
 *
 * Each visible widget — card, banner, or chart — has its own function so
 * widgets are individually locatable, swappable, and testable.
 * Shared infrastructure: pnm_fetch, pnm_gather, pnm_fmt_*, pnm_card_open,
 * pnm_styles, pnm_action_bar, pnm_value_histogram, pnm_disk_guard,
 * pnm_disk_metrics, pnm_render_charts_js.
 * ------------------------------------------------------------------------- */

function page(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
    if($LockActiveDirectoryToKerberos==1){$EnableKerbAuth=1;}
    $ActiveDirectoryEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryEmergency"));

    $AD_FOUND=false;
    if($EnableKerbAuth==1){
        foreach(explode("\n",@file_get_contents("/etc/squid3/authenticate.conf")) as $line){
            if(preg_match("#^auth_param (negotiate|ntlm) program#",trim($line))){$AD_FOUND=true;break;}
        }
    }

    $ctx = pnm_gather($EnableKerbAuth, $AD_FOUND, $LockActiveDirectoryToKerberos, $ActiveDirectoryEmergency);

    $html=[];
    $html[] = pnm_styles();
    $html[] = "<div class='pnm-dashboard'>";
    $html[] = pnm_action_bar($page, $tpl, $EnableKerbAuth, $AD_FOUND);
    $html[] = "<div class='pnm-grid'>";

    /* Traffic */
    $html[] = widget_members($ctx);
    $html[] = widget_client_ips($ctx);
    $html[] = widget_downloaded($ctx);
    $html[] = widget_requests($ctx);


    /* Caches + throughput */
    $html[] = widget_cache_phys($ctx);
    $html[] = widget_dns_cache($ctx);
    $html[] = widget_rps($ctx);

    /* Bandwidth Calculation (conditional — speedtest feature enabled) */
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableBandwithCalculation"))==1){
        $html[] = widget_bandwidth();
    }

    /* Web filtering */
    $html[] = banner_filtering_status();
    $html[] = widget_banned();
    $html[] = widget_categorized();

    /* Disk caching — single full-width guard card OR three normal cards */
    $guard = pnm_disk_guard();
    if($guard !== null){
        $html[] = $guard;
    } else {
        $html[] = widget_disk_caching();
        $html[] = widget_disk_capacity();
        $html[] = widget_performance();
    }

    /* Active Directory (conditional) */
    if($EnableKerbAuth==1){
        $html[] = widget_ad_status($ctx);
        $html[] = widget_ad_link($ctx);
        if($LockActiveDirectoryToKerberos==1){
            $html[] = widget_ad_requests();
        }
    }

    $html[] = "</div>"; /* end pnm-grid */

    /* Charts below the grid (only when daemon ready + licensed) */
    if($ctx['daemon_ready'] && $ctx['has_license']){
        $html[] = "<div class='pnm-charts-row'>";
        $html[] = chart_top_categories();
        $html[] = chart_top_domains();
        $html[] = "</div>";
        $html[] = "<div class='pnm-charts-row' style='grid-template-columns:1fr 1fr;margin-top:16px'>";
        $html[] = chart_top_sources($ctx);
        $html[] = chart_top_users($ctx);
        $html[] = "</div>";
    }

    /* ;
     * Chart.min.4.5.1.js is the matching 4.x bundle for our syntax. */
    $html[] = "";
    $html[] = "<script>".pnm_render_charts_js($ctx, $tpl)."</script>";
    $html[] = "</div>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
    return true;
}

/* =========================== Data layer ================================== */

function pnm_fetch($path){
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API($path);
    $r=json_decode($raw);
    if(!is_object($r) || empty($r->success)){return null;}
    return $r->data;
}

function pnm_gather($EnableKerbAuth, $AD_FOUND, $LockADToKerb, $ADEmergency){
    $ctx = [];
    $ctx['resume'] = pnm_fetch("/logfile/daemon/stats/resume");
    $ctx['ts_rps'] = pnm_fetch("/logfile/daemon/stats/timeseries?metric=rps");
    $ctx['ts_req'] = pnm_fetch("/logfile/daemon/stats/timeseries?metric=requests");
    $ctx['ts_usr'] = pnm_fetch("/logfile/daemon/stats/timeseries?metric=users");
    $ctx['ts_ips']   = pnm_fetch("/logfile/daemon/stats/timeseries?metric=ips");
    $ctx['ts_bytes'] = pnm_fetch("/logfile/daemon/stats/timeseries?metric=bytes");
    $ctx['daemon_ready'] = is_object($ctx['resume']) && intval($ctx['resume']->schema_version ?? 0) >= 1;

    $ctx['has_license'] = $GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE() ? true : false;
    if($ctx['daemon_ready'] && $ctx['has_license']){
        $from=time()-21600; $to=time();
        $ctx['top_cat'] = pnm_fetch("/logfile/daemon/stats/top?dimension=categories&by=bytes&from=$from&to=$to&limit=8");
        $ctx['top_dom'] = pnm_fetch("/logfile/daemon/stats/top?dimension=domains&by=bytes&from=$from&to=$to&limit=10");
        /* IPs/users: daemon doesn't track per-key bytes — sort by count only */
        $ctx['top_ips'] = pnm_fetch("/logfile/daemon/stats/top?dimension=ips&by=count&from=$from&to=$to&limit=10");
        $ctx['top_usr'] = pnm_fetch("/logfile/daemon/stats/top?dimension=users&by=count&from=$from&to=$to&limit=10");
    } else {
        $ctx['top_cat'] = $ctx['top_dom'] = $ctx['top_ips'] = $ctx['top_usr'] = null;
    }

    $squidstat = new squidstat();
    if($squidstat->connect()){
        $data = $squidstat->makeQuery();
        $squidstat->ReturnOnlyTitle = true;
        $ctx['members_html'] = $squidstat->makeHtmlReport($data, false, [], "host");
    } else {
        $ctx['members_html'] = "??";
    }

    $mgr = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/mgrinfo"));
    $ctx['mgr_info'] = (is_object($mgr) && isset($mgr->Info)) ? $mgr->Info : null;
    $ctx['mem_disabled'] = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableMemoryCache"))==1;

    $ctx['ipcache_entries'] = $mgr->Info->ip_cache_entries->Entries;
    $ctx['ipcache_requests'] = $mgr->Info->ip_cache_entries->Requests;
    $ctx['ipcache_hits'] = $mgr->Info->ip_cache_entries->Hits;
    $ctx['EnableKerbAuth'] = $EnableKerbAuth;
    $ctx['AD_FOUND'] = $AD_FOUND;
    $ctx['LockADToKerb'] = $LockADToKerb;
    $ctx['ADEmergency'] = $ADEmergency;
    return $ctx;
}

/* =========================== Formatters ================================== */

function pnm_fmt_bytes($n){
    $n=floatval($n);
    if($n < 1024) return intval($n)." B";
    if($n < 1048576) return sprintf('%.1f KB', $n/1024);
    if($n < 1073741824) return sprintf('%.1f MB', $n/1048576);
    if($n < 1099511627776) return sprintf('%.2f GB', $n/1073741824);
    return sprintf('%.2f TB', $n/1099511627776);
}

function pnm_fmt_number($n){
    return number_format(intval($n), 0, '.', ' ');
}

/* Compact K/M suffix for narrow contexts (histogram bar labels etc).
 *   837 → "837",  12345 → "12.3K",  1234567 → "1.2M" */
function pnm_fmt_compact($n){
    $n = intval($n);
    if($n < 1000)     return strval($n);
    if($n < 1000000)  return sprintf('%.1fK', $n/1000);
    return sprintf('%.1fM', $n/1000000);
}

/* Tight byte formatter for narrow contexts (histogram bar labels).
 *   500 → "500B",  12K → "12K",  461MB → "461M",  3.2GB → "3.2G" */
function pnm_fmt_bytes_compact($n){
    $n = floatval($n);
    if($n < 1024)        return intval($n)."B";
    if($n < 1048576)     return intval(round($n/1024))."K";
    if($n < 1073741824)  return intval(round($n/1048576))."M";
    return sprintf('%.1fG', $n/1073741824);
}

/* =========================== Shared layout ============================== */

/* Opens a card with the standard icon + value head row.
 * Caller appends optional subtitle/sparkline body + closing </section>. */
function pnm_card_open($icon_variant, $icon, $headline, $section_attrs = ''){
    $sa = $section_attrs ? " $section_attrs" : '';
    return "<section class='pnm-card'$sa>\n"
         . "  <div class='pnm-card-head pnm-card-head-spread'>"
         . "<div class='pnm-card-icon pnm-card-icon-$icon_variant'><i class='$icon'></i></div>"
         . "<span class='pnm-card-value'>$headline</span>"
         . "</div>\n";
}

function pnm_styles(){
    return "<style>
.pnm-dashboard{ color:#161d1b;background:#ffffff;padding:24px;border-radius:12px;margin:10px 0}
.pnm-dashboard *{box-sizing:border-box}
.pnm-dashboard .pnm-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid #bbcac4;flex-wrap:wrap;gap:12px}
.pnm-dashboard .pnm-title{display:flex;align-items:center;gap:12px;font-size:18px;font-weight:600;color:#006b5c}
.pnm-dashboard .pnm-title i{font-size:20px}
.pnm-dashboard .pnm-actions{display:flex;gap:8px;flex-wrap:wrap}
.pnm-dashboard .pnm-btn{font-family:inherit;font-size:12px;font-weight:500;padding:8px 14px;border-radius:8px;border:1px solid #bbcac4;background:#ffffff;color:#3c4a46;cursor:pointer;transition:all .15s;display:inline-flex;align-items:center;gap:6px}
.pnm-dashboard .pnm-btn:hover{background:#eef5f1}
.pnm-dashboard .pnm-btn-danger{background:#9f4128;color:#ffffff;border-color:#9f4128}
.pnm-dashboard .pnm-btn-danger:hover{background:#7f2a13;border-color:#7f2a13}
.pnm-dashboard .pnm-btn-warning{background:#fe896a;color:#ffffff;border-color:#fe896a}
.pnm-dashboard .pnm-btn-warning:hover{background:#9f4128;border-color:#9f4128}
.pnm-dashboard .pnm-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
@media(max-width:900px){.pnm-dashboard .pnm-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:520px){.pnm-dashboard .pnm-grid{grid-template-columns:1fr}}
.pnm-dashboard .pnm-card{background:#ffffff;border:1px solid #bbcac4;border-radius:12px;padding:20px;box-shadow:0 2px 4px rgba(0,0,0,.04);display:flex;flex-direction:column;gap:12px;min-height:130px}
.pnm-dashboard .pnm-card-head{display:flex;align-items:center;gap:12px}
.pnm-dashboard .pnm-card-head-spread{justify-content:space-between}
.pnm-dashboard .pnm-card-head .pnm-card-value{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pnm-dashboard .pnm-card-icon{width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.pnm-dashboard .pnm-card-icon-primary{background:rgba(0,191,165,.12);color:#006b5c}
.pnm-dashboard .pnm-card-icon-secondary{background:rgba(102,217,204,.2);color:#006a62}
.pnm-dashboard .pnm-card-icon-error{background:#ffdad6;color:#ba1a1a}
.pnm-dashboard .pnm-card-icon-warning{background:#ffe5d6;color:#9f4128}
.pnm-dashboard .pnm-card-icon-neutral{background:#e3eae6;color:#3c4a46}
.pnm-dashboard .pnm-card-value{font-size:24px;font-weight:700;color:#161d1b;line-height:32px;letter-spacing:-.02em;margin:0}
.pnm-dashboard .pnm-card-value-primary{color:#006b5c}
.pnm-dashboard .pnm-card-value-sm{font-size:18px;line-height:24px}
.pnm-dashboard .pnm-card-sub{font-size:12px;color:#3c4a46;margin:0}
.pnm-dashboard .pnm-card-mono{;font-size:13px;font-weight:500}
.pnm-dashboard .pnm-card-sparkline{height:75px;margin-top:auto;position:relative}
.pnm-dashboard .pnm-card-sparkline canvas{width:100%!important;height:75px!important}
.pnm-dashboard .pnm-card-donut{position:relative;width:150px;height:150px;margin:0 auto}
.pnm-dashboard .pnm-card-donut canvas{width:150px!important;height:150px!important}
.pnm-dashboard .pnm-card-donut-label{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;color:#161d1b}
.pnm-dashboard .pnm-card-centered{align-items:center;text-align:center;justify-content:center}
.pnm-dashboard .pnm-bar-histogram{display:flex;align-items:stretch;gap:6px;height:150px !important;margin-top:auto}
.pnm-dashboard .pnm-bar-histogram-col{flex:1;min-width:0;display:flex;flex-direction:column;align-items:stretch;height:100%}
.pnm-dashboard .pnm-bar-histogram-label{font-size:11px;font-weight:500;color:#3c4a46;text-align:center;line-height:14px;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.pnm-dashboard .pnm-bar-histogram-area{flex:1;display:flex;align-items:flex-end;min-height:0}
.pnm-dashboard .pnm-bar-histogram-bar{width:100%;background:rgba(0,191,165,.3);border-radius:3px 3px 0 0;min-height:4px;transition:height .3s}
.pnm-dashboard .pnm-bar-histogram-bar-active{background:#00bfa5}
.pnm-dashboard .pnm-bar-histogram-time{font-size:10px;color:#6c7a76;text-align:center;line-height:14px;margin-top:4px;white-space:nowrap;}
.pnm-dashboard .pnm-banner{grid-column:1 / -1;background:#eef5f1;border:1px solid #bbcac4;border-radius:12px;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.pnm-dashboard .pnm-banner-left{display:flex;align-items:center;gap:12px;color:#3c4a46;font-size:18px;font-weight:600}
.pnm-dashboard .pnm-banner-left i{font-size:22px}
.pnm-dashboard .pnm-banner-status{font-size:18px;font-weight:700;color:#6c7a76}
.pnm-dashboard .pnm-banner-status-active{color:#006b5c}
.pnm-dashboard .pnm-banner-status-warning{color:#9f4128}
.pnm-dashboard .pnm-banner-status-error{color:#ba1a1a}
.pnm-dashboard .pnm-charts-row{display:grid;grid-template-columns:1fr 1.6fr;gap:16px;margin-top:24px}
@media(max-width:900px){.pnm-dashboard .pnm-charts-row{grid-template-columns:1fr}}
.pnm-dashboard .pnm-chart-card{background:#ffffff;border:1px solid #bbcac4;border-radius:12px;padding:20px;box-shadow:0 2px 4px rgba(0,0,0,.04)}
.pnm-dashboard .pnm-chart-title{font-size:14px;font-weight:600;color:#3c4a46;margin:0 0 12px 0}
</style>";
}

function pnm_action_bar($page, $tpl, $EnableKerbAuth, $AD_FOUND){
    $h = [];
    $h[] = "<div class='pnm-header'>";
    //$h[] = "<div class='pnm-title'><i class='fas fa-shield-halved'></i><span>{your_proxy}</span></div>";
    $h[] = "<div class='pnm-actions'>";
    $h[] = "<button class='pnm-btn pnm-btn-danger' onclick=\"Loadjs('$page?purge=yes');\"><i class='fas fa-trash-can'></i> {empty_dns_cache}</button>";
    $h[] = "<button class='pnm-btn' onclick=\"Loadjs('fw.proxy.actions.php');\"><i class='fas fa-gears'></i> {services_operations}</button>";
    if($EnableKerbAuth==1 && !$AD_FOUND){
        $jsreconnect=$tpl->framework_buildjs("/proxy/nohup/reconfigure","squid.articarest.nohup","squid.articarest.log","dashboard-proxy-restart","LoadAjax('dashboard-proxy-status','$page?page=yes');");
        $h[] = "<button class='pnm-btn pnm-btn-warning' onclick=\"$jsreconnect;\"><i class='fas fa-link'></i> {reconnect_to_ad}</button>";
        $h[] = "<button class='pnm-btn' onclick=\"Loadjs('fw.proxy.auth.conf.php');\"><i class='fas fa-link'></i> {file_configuration}</button>";
    }
    $h[] = "</div>";
    $h[] = "</div>";
    return implode("\n",$h);
}

/* =========================== Widgets: traffic ============================ */

function widget_members($ctx){
    $connections = "";
    $u=0;
    if($ctx['daemon_ready']){
        $u = intval($ctx['resume']->unique_authenticated_users ?? 0);
    }
    $h = [];
    $h[] = pnm_card_open('primary', 'fas fa-users', $u ." {members}");
    $h[] = "  <div class='pnm-card-sparkline'><canvas id='pnm-sparkline-users'></canvas></div>";
    $h[] = "</section>";
    return implode("\n",$h);
}

function widget_downloaded($ctx){
    $bytes_text = $ctx['daemon_ready'] ? pnm_fmt_bytes(floatval($ctx['resume']->total_bandwidth_bytes ?? 0)) : "—";
    $sub_text = $ctx['daemon_ready']
        ? pnm_fmt_number(intval($ctx['resume']->unique_master_domains ?? 0))." {domains}"
        : "{please_wait}";
    return pnm_card_open('secondary', 'fas fa-cloud-arrow-down', $bytes_text)
         . "  <p class='pnm-card-sub pnm-card-mono'>$sub_text</p>\n"
         . "  ".pnm_value_histogram($ctx['ts_bytes'], $ctx['daemon_ready'], 'pnm_fmt_bytes_compact')."\n"
         . "</section>";
}

function widget_requests($ctx){
    $reqs_text = $ctx['daemon_ready'] ? pnm_fmt_number(intval($ctx['resume']->total_forwarded_requests ?? 0)) : "—";
    return pnm_card_open('primary', 'fas fa-chart-column', $reqs_text. "&nbsp;{requests}")
         . "  ".pnm_value_histogram($ctx['ts_req'], $ctx['daemon_ready'], 'pnm_fmt_compact')."\n"
         . "</section>";
}

/* Generic 6-bar histogram for any /timeseries metric.
 * $format_fn is a callable taking the bucket-sum value and returning the
 * compact bar label (e.g. 'pnm_fmt_compact' for counts, 'pnm_fmt_bytes_compact'
 * for bytes). The peak bar is highlighted, and each bar shows its end-of-range
 * hour underneath. */
function pnm_value_histogram($ts, $daemon_ready, $format_fn){
    if($daemon_ready && is_object($ts) && !empty($ts->points)){
        $points = $ts->points;
        $n = count($points);
        $bars   = array_fill(0, 6, 0);
        $times  = array_fill(0, 6, 0);
        $bucket_size = max(1, intval(ceil($n/6)));
        for($i=0;$i<$n;$i++){
            $b = min(5, intval($i/$bucket_size));
            $bars[$b]  += floatval($points[$i]->value ?? 0);
            $times[$b] = intval($points[$i]->epoch ?? $times[$b]);
        }
        $max = max(max($bars), 1);
        $active_idx = 0;
        for($i=1;$i<6;$i++){ if($bars[$i] > $bars[$active_idx]) $active_idx=$i; }
        $rendered = [];
        for($i=0;$i<6;$i++){
            $pct = max(8, intval(($bars[$i]/$max)*100));
            $cls = ($i==$active_idx) ? "pnm-bar-histogram-bar pnm-bar-histogram-bar-active" : "pnm-bar-histogram-bar";
            $lbl = $format_fn($bars[$i]);
            $tm  = $times[$i] > 0 ? date('G', $times[$i]).'h' : '';
            $rendered[] = "<div class='pnm-bar-histogram-col'>"
                        . "<div class='pnm-bar-histogram-label'>$lbl</div>"
                        . "<div class='pnm-bar-histogram-area'><div class='$cls' style='height:{$pct}%'></div></div>"
                        . "<div class='pnm-bar-histogram-time'>$tm</div>"
                        . "</div>";
        }
        return "<div class='pnm-bar-histogram'>".implode('',$rendered)."</div>";
    }
    /* Placeholder when daemon has no points yet */
    $rendered = [];
    foreach([50,40,30,20,30,40] as $pct){
        $rendered[] = "<div class='pnm-bar-histogram-col'>"
                    . "<div class='pnm-bar-histogram-label'>&nbsp;</div>"
                    . "<div class='pnm-bar-histogram-area'><div class='pnm-bar-histogram-bar' style='height:{$pct}%'></div></div>"
                    . "<div class='pnm-bar-histogram-time'>&nbsp;</div>"
                    . "</div>";
    }
    return "<div class='pnm-bar-histogram'>".implode('',$rendered)."</div>";
}

function widget_client_ips($ctx){
    $ips_text = $ctx['daemon_ready'] ? pnm_fmt_number(intval($ctx['resume']->unique_client_ips ?? 0)) : "—";
    return pnm_card_open('secondary', 'fas fa-network-wired', $ips_text." {ip_addresses}")
         . "  <div class='pnm-card-sparkline'><canvas id='pnm-sparkline-ips'></canvas></div>\n"
         . "</section>";
}

/* =========================== Widgets: caches ============================= */

function widget_cache_phys($ctx){
    $memSize = 0; $memPrc = 0;
    if(!$ctx['mem_disabled'] && is_object($ctx['mgr_info'])){
        $memSize = intval($ctx['mgr_info']->storage_memsize ?? 0);
        $memPrc = floatval($ctx['mgr_info']->storage_mem_capacity ?? 0);
    }
    $memSize_text = $memSize > 0 ? pnm_fmt_bytes($memSize) : ($ctx['mem_disabled'] ? "{disabled}" : "—");
    return pnm_card_open('primary', 'fas fa-memory', "{memory_cache} ".intval($memPrc)."%")
         . "  <div class='pnm-card-donut'><canvas id='pnm-donut-memcap' width='150' height='150'></canvas><div class='pnm-card-donut-label'>".intval($memPrc)."%</div></div>\n"
         . "  <p class='pnm-card-sub pnm-card-mono' style='text-align:center'>$memSize_text</p>\n"
         . "</section>";
}

function widget_dns_cache($ctx){
    $hits = intval($ctx['ipcache_hits'] ?? 0);
    $reqs = intval($ctx['ipcache_requests'] ?? 0);
    $rate = $reqs > 0 ? intval(round(($hits / $reqs) * 100)) : 0;
    return pnm_card_open('secondary', 'fas fa-database',"DNS: ". pnm_fmt_number($ctx['ipcache_entries'])." <small style='font-size:x-small'>{record} ({cached})</small>")
         . "  <div class='pnm-card-donut'><canvas id='pnm-donut-dnscache' width='150' height='150'></canvas><div class='pnm-card-donut-label'>{$rate}%</div></div>\n"
         . "  <p class='pnm-card-sub pnm-card-mono' style='text-align:center'>".pnm_fmt_compact($hits)." / ".pnm_fmt_compact($reqs)." {hits}</p>\n"
         . "</section>";
}

function widget_rps($ctx){
    $rps_text = "—";
    if($ctx['daemon_ready'] && is_object($ctx['ts_rps']) && !empty($ctx['ts_rps']->points)){
        $last = end($ctx['ts_rps']->points);
        $rps_text = sprintf('%.1f/s', floatval($last->value ?? 0));
    }
    return pnm_card_open('primary', 'fas fa-gauge-high', $rps_text."&nbsp;&nbsp;<small style='font-size: x-small'>({requests}/{second})</small>")
         . "  <div class='pnm-card-sparkline'><canvas id='pnm-sparkline-rps'></canvas></div>\n"
         . "</section>";
}

/* Bandwidth Calculation (speedtest) — dernière mesure via GET /speedtests/latest.
 * download/upload sont stockés en Mbps, ping en ms (speedtest.go parseData:
 * Bandwidth/125000). Carte affichée seulement quand EnableBandwithCalculation=1 ;
 * tant qu'aucune mesure n'a réussi (table vide → data null), état {no_data_available}. */
function widget_bandwidth(){
    $tpl=new template_admin();
    $d=null;
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/speedtests/latest"));
    if(is_object($json) && !empty($json->Status) && isset($json->data) && is_object($json->data)){
        $d=$json->data;
    }
    if($d===null || intval($d->ztime)==0){
        return pnm_card_open('secondary','fas fa-gauge-high',"{bandwidth}")
             . "  <p class='pnm-card-sub'>{no_data_available}</p>\n"
             . "</section>";
    }
    $down=intval($d->download); $up=intval($d->upload); $ping=intval($d->ping);
    $when=htmlspecialchars($tpl->time_to_date(intval($d->ztime),true));
    return pnm_card_open('primary','fas fa-gauge-high',
             pnm_fmt_number($down)." <small style='font-size:x-small'>Mbps ({download})</small>")
         . "  <p class='pnm-card-sub pnm-card-mono'><i class='fas fa-arrow-up'></i> ".pnm_fmt_number($up)." Mbps ({upload}) · ".$ping." ms ({latency})</p>\n"
         . "  <p class='pnm-card-sub'>{last_check}: ".$when."</p>\n"
         . "</section>";
}

/* =========================== Widgets: filtering ========================== */

function banner_filtering_status(){
    $EnableUfdbGuard = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
    if($EnableUfdbGuard == 0){
        return "<div class='pnm-banner'>"
             . "<div class='pnm-banner-left'><i class='fas fa-filter-circle-xmark'></i><span>{web_filtering}</span></div>"
             . "<div class='pnm-banner-status'>{inactive}</div>"
             . "</div>";
    }
    $UfdbUsedDatabases = @unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUsedDatabases"));
    if(!is_array($UfdbUsedDatabases)) $UfdbUsedDatabases = [];
    if(!isset($UfdbUsedDatabases["MISSING"]) || !is_array($UfdbUsedDatabases["MISSING"])) $UfdbUsedDatabases["MISSING"] = [];
    $CountDeMissing = count($UfdbUsedDatabases["MISSING"]);
    if($CountDeMissing > 0){
        $banner_text = "$CountDeMissing {missing_databases}";
        $banner_cls  = "pnm-banner-status-warning";
    } else {
        $banner_text = "{active2}";
        $banner_cls  = "pnm-banner-status-active";
    }
    return "<div class='pnm-banner'>"
         . "<div class='pnm-banner-left'><i class='fas fa-filter'></i><span>{web_filtering}</span></div>"
         . "<div class='pnm-banner-status $banner_cls'>$banner_text</div>"
         . "</div>";
}

function widget_banned(){
    $EnableUfdbGuard         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
    $EnableStatsCommunicator = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStatsCommunicator"));
    if($EnableUfdbGuard == 0){
        return pnm_card_open('neutral', 'fas fa-eye-slash', '0 {banned}')
             . "</section>";
    }
    $banned = 0;
    if($EnableStatsCommunicator == 1){
        try {
            $redis = new Redis();
            if(@$redis->connect("127.0.0.1", 4322, 2)){
                $banned = intval($redis->get("DASHBOARD.TOTAL.BLOCKED"));
            }
        } catch (\RedisException $e) {
            /* fall through to Valkey-cached counter */
        } catch (\Exception $e) {
            /* belt-and-braces for older phpredis builds */
        }
    }
    if($banned == 0){
        $banned = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCounter"));
    }
    $variant = $banned > 0 ? "error" : "neutral";
    return pnm_card_open($variant, 'fas fa-eye-slash', pnm_fmt_number($banned) ." {banned}")
         . "</section>";
}

function widget_categorized(){
    /* Magic thresholds: ~50M URLs = fully loaded DB; <1M URLs = critically sparse. */
    $DB_FULL_THRESHOLD   = 50000010;
    $DB_SPARSE_THRESHOLD = 1000000;

    $EnableUfdbGuard         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
    $EnableStatsCommunicator = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStatsCommunicator"));
    if($EnableUfdbGuard == 0){
        return pnm_card_open('neutral', 'fas fa-folder-tree', '0')
             . "  <p class='pnm-card-sub'>{websites_categorized}</p>\n"
             . "</section>";
    }
    $items = 0;
    $UfdbMasterCache = @unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbMasterCache"));
    if(!is_array($UfdbMasterCache)) $UfdbMasterCache = [];
    $has_corp = $GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE() ? true : false;
    foreach($UfdbMasterCache as $array){
        if(!is_array($array)) continue;
        if($has_corp){
            if(intval($array["official_category"] ?? 0) == 1) $items += intval($array["items"] ?? 0);
        } else {
            if(intval($array["free_category"] ?? 0) == 1) $items += intval($array["items"] ?? 0);
        }
    }
    if($EnableStatsCommunicator == 1){
        $alt = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DASHBOARD.TOTAL.CATEGORIZED"));
        if($alt > $items) $items = $alt;
    }
    if($items > $DB_FULL_THRESHOLD)         $variant = "primary";
    elseif($items < $DB_SPARSE_THRESHOLD)   $variant = "error";
    else                                    $variant = "warning";
    return pnm_card_open($variant, 'fas fa-folder-tree', pnm_fmt_number($items))
         . "  <p class='pnm-card-sub'>{websites_categorized}</p>\n"
         . "</section>";
}

/* =========================== Widgets: disk caching ======================= */

/* Returns the full-width guard card HTML when disk caching is unavailable,
 * or null when the three normal disk widgets should render. */
function pnm_disk_guard(){
    $SquidCachesProxyEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCachesProxyEnabled"));
    $DisableAnyCache         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableAnyCache"));
    $guards = [
        [!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE(), 'neutral', '{license_error}', '#6c7a76'],
        [$SquidCachesProxyEnabled == 0,              'neutral', '{disabled}',      '#6c7a76'],
        [$DisableAnyCache == 1,                      'warning', '{paused}',        '#9f4128'],
    ];
    foreach($guards as $g){
        list($cond, $variant, $msg, $color) = $g;
        if(!$cond) continue;
        return pnm_card_open($variant, 'fas fa-hard-drive', "<span style='color:$color;font-size:18px'>$msg</span>", "style='grid-column:1 / -1'")
             . "  <p class='pnm-card-sub'>{disk_caching}</p>\n"
             . "</section>";
    }
    return null;
}

/* Shared metrics for the 3 disk widgets — cached in $GLOBALS so the
 * cache list + SQUID_STORE_DIR parse happens once per request. */
function pnm_disk_metrics(){
    if(isset($GLOBALS["pnm_disk_metrics_cache"])) return $GLOBALS["pnm_disk_metrics_cache"];
    /* Declared caches through articarest (squid/proxy_caches_crud.go), not caches.db.
     * The usedcache column was never written: the used size comes from
     * SQUID_STORE_DIR below, as before. */
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/caches"), true);
    $results = (is_array($json) && !empty($json["success"]) && isset($json["data"]) && is_array($json["data"])) ? $json["data"] : [];
    $cache_size = 0; $usedcache = 0;
    foreach($results as $ligne){
        if(empty($ligne["enabled"]) || !empty($ligne["remove"])) continue;
        $cache_size += intval($ligne["cache_size"] ?? 0);
    }
    $cache_size *= 1024;
    $m = [
        'StoreEntries'    => 0,
        'cache_size_Text' => pnm_fmt_bytes($cache_size),
        'usedcache_text'  => pnm_fmt_bytes($usedcache),
        'prc'             => $cache_size > 0 ? round(($usedcache/$cache_size) * 100, 1) : 0,
    ];
    $store_dir = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_STORE_DIR");
    foreach(explode("\n",$store_dir) as $line){
        $line = trim($line);
        if($line == "") continue;
        if(preg_match("#^Store Entries.*?([0-9]+)#i", $line, $re)){
            $m['StoreEntries'] = intval($re[1]);
        } elseif(preg_match("#^Maximum Swap Size.*?([0-9]+)#i", $line, $re)){
            $m['cache_size_Text'] = pnm_fmt_bytes(intval($re[1])*1024);
        } elseif(preg_match("#^Current Store Swap Size.*?([0-9]+)#i", $line, $re)){
            $m['usedcache_text'] = pnm_fmt_bytes(intval($re[1])*1024);
        } elseif(preg_match("#^Current Capacity.*?([0-9\\.]+).*?used,#i", $line, $re)){
            $m['prc'] = floatval($re[1]);
        }
    }
    $GLOBALS["pnm_disk_metrics_cache"] = $m;
    return $m;
}

function widget_disk_caching(){
    $m = pnm_disk_metrics();
    return pnm_card_open('secondary', 'fas fa-download', pnm_fmt_number($m['StoreEntries']))
         . "  <p class='pnm-card-sub'>{disk_caching} · {items}</p>\n"
         . "</section>";
}

function widget_disk_capacity(){
    $m = pnm_disk_metrics();
    $prc = $m['prc'];
    if($prc < 5 || $prc > 90) $variant = "warning";
    elseif($prc > 95)         $variant = "error";
    else                      $variant = "primary";
    return pnm_card_open($variant, 'fas fa-hard-drive', sprintf('%.1f',$prc)."%")
         . "  <p class='pnm-card-sub pnm-card-mono'>{capacity}: {$m['usedcache_text']} / {$m['cache_size_Text']}</p>\n"
         . "</section>";
}

function widget_performance(){
    return pnm_card_open('neutral', 'fas fa-percent', '0%')
         . "  <p class='pnm-card-sub'>{performance}</p>\n"
         . "</section>";
}

/* =========================== Widgets: Active Directory =================== */

function widget_ad_status($ctx){
    if($ctx['ADEmergency'] == 0){
        return pnm_card_open('primary', 'fab fa-windows', "<span style='color:#006b5c'>Active Directory - {active2}</span>")
             . "</section>";
    }
    return pnm_card_open('error', 'fab fa-windows', "<span style='color:#ba1a1a'>Active Directory - {emergency}</span>")
         . "</section>";
}

function widget_ad_link($ctx){
    if($ctx['AD_FOUND']){
        return pnm_card_open('secondary', 'fas fa-link', "<span style='color:#006a62'>Active Directory: {linked}</span>")
             . "</section>";
    }
    return pnm_card_open('error', 'fas fa-link-slash', "<span style='color:#ba1a1a'>Active Directory: {disconnected}</span>")
         . "</section>";
}

function widget_ad_requests(){
    $arr = @unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_NEGOTIATE_AUTHENTICATOR"));
    if(!is_array($arr)) $arr = [];
    $MAX  = intval($arr["MAX"] ?? 0);
    $SENT = intval($arr["SENT"] ?? 0);
    return pnm_card_open('primary', 'fas fa-microchip', "<span class='pnm-card-mono'>$SENT / $MAX</span>")
         . "  <p class='pnm-card-sub'>Active Directory: {requests}</p>\n"
         . "</section>";
}

/* =========================== Charts: HTML containers ===================== */

function chart_top_categories(){
    return "<div class='pnm-chart-card'><h3 class='pnm-chart-title'>{top_categories} <span style='color:#6c7a76;font-weight:400'>(6h)</span></h3>"
         . "<div style='position:relative;height:240px'><canvas id='pnm-chart-cat'></canvas></div></div>";
}

function chart_top_domains(){
    return "<div class='pnm-chart-card'><h3 class='pnm-chart-title'>{top_domains} <span style='color:#6c7a76;font-weight:400'>(6h)</span></h3>"
         . "<div style='position:relative;height:240px'><canvas id='pnm-chart-dom'></canvas></div></div>";
}

function chart_top_sources($ctx){
    $empty = !is_object($ctx['top_ips'] ?? null) || empty($ctx['top_ips']->entries);
    $body  = $empty
        ? "<div style='height:280px;display:flex;align-items:center;justify-content:center;color:#6c7a76;font-size:14px'>{no_data}</div>"
        : "<div style='position:relative;height:280px'><canvas id='pnm-chart-ips'></canvas></div>";
    return "<div class='pnm-chart-card'><h3 class='pnm-chart-title'>{top_sources} <span style='color:#6c7a76;font-weight:400'>(6h)</span></h3>$body</div>";
}

function chart_top_users($ctx){
    $empty = !is_object($ctx['top_usr'] ?? null) || empty($ctx['top_usr']->entries);
    $body  = $empty
        ? "<div style='height:280px;display:flex;align-items:center;justify-content:center;color:#6c7a76;font-size:14px'>{no_data}</div>"
        : "<div style='position:relative;height:280px'><canvas id='pnm-chart-usr'></canvas></div>";
    return "<div class='pnm-chart-card'><h3 class='pnm-chart-title'>{top_users} <span style='color:#6c7a76;font-weight:400'>(6h)</span></h3>$body</div>";
}

/* =========================== Charts: per-chart JS init =================== */

function chartjs_sparkline_rps($ctx, $sparkOpts){
    $vals = [];
    if(is_object($ctx['ts_rps']) && !empty($ctx['ts_rps']->points)){
        $tail = array_slice($ctx['ts_rps']->points, -12);
        foreach($tail as $p) $vals[] = sprintf('%.2f', floatval($p->value ?? 0));
    }
    if(count($vals) == 0) $vals = ['0','0','0','0','0','0'];
    $data_js   = implode(',', $vals);
    $labels_js = rtrim(str_repeat("'',", count($vals)), ',');
    return "if((pnmEl=document.getElementById('pnm-sparkline-rps'))){new Chart(pnmEl,{type:'line',data:{labels:[$labels_js],datasets:[{label:'',data:[$data_js],borderColor:'#006b5c',backgroundColor:'rgba(0,191,165,0.18)',borderWidth:2,fill:true,tension:0.35}]},$sparkOpts});}";
}

function chartjs_sparkline_users($ctx, $sparkOpts){
    $vals = [];
    if(is_object($ctx['ts_usr']) && !empty($ctx['ts_usr']->points)){
        $tail = array_slice($ctx['ts_usr']->points, -24);
        foreach($tail as $p) $vals[] = intval($p->value ?? 0);
    }
    if(count($vals) == 0) $vals = [0,0,0,0,0,0];
    $data_js   = implode(',', $vals);
    $labels_js = rtrim(str_repeat("'',", count($vals)), ',');
    return "if((pnmEl=document.getElementById('pnm-sparkline-users'))){new Chart(pnmEl,{type:'line',data:{labels:[$labels_js],datasets:[{label:'',data:[$data_js],borderColor:'#006a62',backgroundColor:'rgba(102,217,204,0.25)',borderWidth:2,fill:true,tension:0.35}]},$sparkOpts});}";
}

function chartjs_sparkline_ips($ctx, $sparkOpts){
    $vals = [];
    if(is_object($ctx['ts_ips']) && !empty($ctx['ts_ips']->points)){
        $tail = array_slice($ctx['ts_ips']->points, -12);
        foreach($tail as $p) $vals[] = intval($p->value ?? 0);
    }
    if(count($vals) == 0) $vals = [0,0,0,0,0,0];
    $data_js   = implode(',', $vals);
    $labels_js = rtrim(str_repeat("'',", count($vals)), ',');
    return "if((pnmEl=document.getElementById('pnm-sparkline-ips'))){new Chart(pnmEl,{type:'line',data:{labels:[$labels_js],datasets:[{label:'',data:[$data_js],borderColor:'#fe896a',backgroundColor:'rgba(254,137,106,0.20)',borderWidth:2,fill:true,tension:0.35}]},$sparkOpts});}";
}

function chartjs_donut_memcap($ctx){
    $memPrc = 0;
    if(!$ctx['mem_disabled'] && is_object($ctx['mgr_info'])){
        $memPrc = intval(floatval($ctx['mgr_info']->storage_mem_capacity ?? 0));
    }
    $memFree = max(0, 100 - $memPrc);
    /* responsive:false so Chart.js paints at the explicit 150px CSS size */
    return "if((pnmEl=document.getElementById('pnm-donut-memcap'))){new Chart(pnmEl,{type:'doughnut',data:{labels:['',''],datasets:[{label:'',data:[$memPrc,$memFree],backgroundColor:['#006b5c','#dde4e0'],borderWidth:0}]},options:{cutout:'60%',plugins:{legend:{display:false},tooltip:{enabled:false}},responsive:false,maintainAspectRatio:false,animation:{duration:600}}});}";
}

function chartjs_donut_dnscache($ctx){
    $hits = intval($ctx['ipcache_hits'] ?? 0);
    $reqs = intval($ctx['ipcache_requests'] ?? 0);
    /* Hit/miss share of total requests. When requests=0 (cold cache), draw an
     * empty ring so the donut shape stays visible. */
    if($reqs > 0){
        $hitPrc  = intval(round(($hits / $reqs) * 100));
        $missPrc = max(0, 100 - $hitPrc);
    } else {
        $hitPrc = 0; $missPrc = 100;
    }
    return "if((pnmEl=document.getElementById('pnm-donut-dnscache'))){new Chart(pnmEl,{type:'doughnut',data:{labels:['',''],datasets:[{label:'',data:[$hitPrc,$missPrc],backgroundColor:['#006a62','#dde4e0'],borderWidth:0}]},options:{cutout:'60%',plugins:{legend:{display:false},tooltip:{enabled:false}},responsive:false,maintainAspectRatio:false,animation:{duration:600}}});}";
}

function chartjs_top_categories($ctx):string{
    if(!is_object($ctx['top_cat']) || empty($ctx['top_cat']->entries)) return "";
    $labels = []; $values = [];
    foreach($ctx['top_cat']->entries as $e){
        $lbl = isset($e->category->label) ? $e->category->label : ($e->key ?? "?");
        if(preg_match("#[0-9]+-(.+)#", $lbl,$re)){
            $lbl=$re[1];
        }
        $labels[] = json_encode($lbl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $values[] = intval($e->bytes ?? 0);
    }
    return "if((pnmEl=document.getElementById('pnm-chart-cat'))){new Chart(pnmEl,{type:'doughnut',data:{labels:[".implode(',',$labels)."],datasets:[{data:[".implode(',',$values)."],backgroundColor:['#006b5c','#00bfa5','#66d9cc','#fe896a','#9f4128','#6c7a76','#bbcac4','#dde4e0'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'right',labels:{boxWidth:12,font:{size:11}}},tooltip:{callbacks:{label:function(ctx){var n=ctx.parsed;if(n<1048576)return ctx.label+': '+(n/1024).toFixed(1)+' KB';if(n<1073741824)return ctx.label+': '+(n/1048576).toFixed(1)+' MB';return ctx.label+': '+(n/1073741824).toFixed(2)+' GB';}}}}}});}";
}

function chartjs_top_domains($ctx, $reqs_label_js):string{
    if(!is_object($ctx['top_dom']) || empty($ctx['top_dom']->entries)) return "";
    $labels = []; $counts = []; $kb = [];
    foreach($ctx['top_dom']->entries as $e){
        $labels[] = json_encode($e->key ?? "?", JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $counts[] = intval($e->count ?? 0);
        $kb[]     = intval(round(floatval($e->bytes ?? 0) / 1024));
    }
    return "if((pnmEl=document.getElementById('pnm-chart-dom'))){new Chart(pnmEl,{type:'bar',data:{labels:[".implode(',',$labels)."],datasets:[{label:$reqs_label_js,data:[".implode(',',$counts)."],backgroundColor:'rgba(0,107,92,0.75)'},{label:'KB',data:[".implode(',',$kb)."],backgroundColor:'rgba(254,137,106,0.75)'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top',labels:{font:{size:11}}}},scales:{x:{ticks:{font:{size:10},maxRotation:45,minRotation:30}},y:{beginAtZero:true,type:'logarithmic'}}}});}";
}

function chartjs_top_sources($ctx, $reqs_label_js, $hbarOpts){
    if(!is_object($ctx['top_ips']) || empty($ctx['top_ips']->entries)) return "";
    $labels = []; $counts = [];
    foreach($ctx['top_ips']->entries as $e){
        $labels[] = json_encode($e->key ?? "?", JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $counts[] = intval($e->count ?? 0);
    }
    return "if((pnmEl=document.getElementById('pnm-chart-ips'))){new Chart(pnmEl,{type:'bar',data:{labels:[".implode(',',$labels)."],datasets:[{label:$reqs_label_js,data:[".implode(',',$counts)."],backgroundColor:'rgba(0,107,92,0.75)',borderRadius:4}]},$hbarOpts});}";
}

function chartjs_top_users($ctx, $reqs_label_js, $hbarOpts){
    if(!is_object($ctx['top_usr']) || empty($ctx['top_usr']->entries)) return "";
    $labels = []; $counts = [];
    foreach($ctx['top_usr']->entries as $e){
        $labels[] = json_encode($e->key ?? "?", JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $counts[] = intval($e->count ?? 0);
    }
    return "if((pnmEl=document.getElementById('pnm-chart-usr'))){new Chart(pnmEl,{type:'bar',data:{labels:[".implode(',',$labels)."],datasets:[{label:$reqs_label_js,data:[".implode(',',$counts)."],backgroundColor:'rgba(159,65,40,0.75)',borderRadius:4}]},$hbarOpts});}";
}

/* Aggregates per-chart JS init blocks into one inline <script> body. */
function pnm_render_charts_js($ctx, $tpl){
    $sparkOpts = "options:{responsive:true,maintainAspectRatio:false,layout:{padding:0},plugins:{legend:{display:false},tooltip:{enabled:false}},scales:{x:{display:false,grid:{display:false},border:{display:false},ticks:{display:false}},y:{display:false,grid:{display:false},border:{display:false},ticks:{display:false},beginAtZero:true}},elements:{point:{radius:0}}}";
    $reqs_label_js = json_encode($tpl->javascript_parse_text("{requests}"), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $hbarOpts = "options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:function(ctx){return ctx.parsed.x.toLocaleString()+' '+$reqs_label_js;}}}},scales:{x:{beginAtZero:true,ticks:{font:{size:11}}},y:{ticks:{font:{family:'JetBrains Mono, monospace',size:11}}}}}";

    $js = ["var pnmEl;"];
    $js[] = chartjs_sparkline_rps($ctx, $sparkOpts);
    $js[] = chartjs_sparkline_users($ctx, $sparkOpts);
    $js[] = chartjs_sparkline_ips($ctx, $sparkOpts);
    $js[] = chartjs_donut_memcap($ctx);
    $js[] = chartjs_donut_dnscache($ctx);
    if($ctx['has_license'] && $ctx['daemon_ready']){
        $js[] = chartjs_top_categories($ctx);
        $js[] = chartjs_top_domains($ctx, $reqs_label_js);
        $js[] = chartjs_top_sources($ctx, $reqs_label_js, $hbarOpts);
        $js[] = chartjs_top_users($ctx, $reqs_label_js, $hbarOpts);
    }
    return implode("\n", array_filter($js, function($s){ return $s !== ""; }));
}


function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
