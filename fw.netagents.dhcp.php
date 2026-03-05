<?php
/**
 * DHCP management main page for a single remote agent.
 * Handles health check, install flow, restart, and leases (Status tab).
 * Scopes, Reservations and Config are loaded from dedicated sub-pages.
 *
 * Entry:  Loadjs('fw.netagents.dhcp.php?dhcp-js={agent_id}')    — opens dialog
 *         LoadAjax('div','fw.netagents.dhcp.php?id={agent_id}')  — inline with tabs
 * Inline handlers (loaded into tab panes):
 *         ?leases={id}         — active leases table (Status tab content)
 *         ?health-badge={id}   — status badge only (for periodic refresh)
 *         POST install={id}    — trigger dhcpd installation (202/409)
 *         ?install-status={id} — poll installation progress
 *         POST restart={id}    — trigger dhcpd service restart (202/409)
 *         ?restart-status={id} — poll restart progress
 * API:    GET  /netagents/dhcp/{id}/health
 *         POST /netagents/dhcp/{id}/install
 *         GET  /netagents/dhcp/{id}/install/status
 *         POST /netagents/dhcp/{id}/restart
 *         GET  /netagents/dhcp/{id}/restart/status
 *         GET  /netagents/dhcp/{id}/assigned-ips
 *         GET  /netagents/status/{id}          (for dhcp3 version field)
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }

if (isset($_GET["dhcp-js"]))        { dhcp_js();              exit; }
if (isset($_GET["leases"]))         { dhcp_leases();          exit; }
if (isset($_GET["health-badge"]))   { dhcp_health_badge();    exit; }
if (isset($_GET["install-status"])) { dhcp_install_status();  exit; }
if (isset($_POST["install"]))       { dhcp_install();         exit; }
if (isset($_GET["restart-status"]))  { dhcp_restart_status();  exit; }
if (isset($_POST["restart"]))        { dhcp_restart();         exit; }
if (isset($_GET["running-banner"]))  { dhcp_running_banner_ajax(); exit; }
if(isset($_GET["label-status"])){dhcp_running_label();exit;}
if(isset($_GET["table"])){dhcp_leases_table();exit;}
dhcp_form();

// ── Helpers ───────────────────────────────────────────────────────────────────

function aid(): int {
    return intval($_GET["id"] ?? $_POST["id"] ?? $_GET["leases"] ?? $_GET["health-badge"]
        ?? $_GET["install-status"] ?? $_POST["install"]
        ?? $_GET["restart-status"] ?? $_POST["restart"]
        ?? $_GET["running-banner"] ?? 0);
}

// ── Dispatcher actions ────────────────────────────────────────────────────────

/** Opens a 970px dialog containing the DHCP management tabs. */
function dhcp_js(): bool {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["dhcp-js"]);
    return $tpl->js_dialog5("{dhcp_management}", "$page?id=$id", 970);
}

/** Renders the full page: health → not installed panel or tabbed UI. */
function dhcp_form(): bool {
    $tpl  = new template_admin();
    $id   = aid();
    $page = CurrentPageName();

    // ── Fetch health + agent status (version info) ──


    $h = [];
    // ── Running state badge ──
    $h[] = dhcp_running_banner($id, $page);

    // ── Tab shell — content loaded by separate files ──
    $tabs = [
        "{status_leases}"  => "$page?leases=$id",
        "{dhcp_scopes}"         => "fw.netagents.dhcp.scopes.php?id=$id",
        "{reservations}"   => "fw.netagents.dhcp.reservations.php?id=$id",
        "{configuration}"  => "fw.netagents.dhcp.config.php?id=$id",
    ];
    $h[] = $tpl->tabs_default($tabs);

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    return true;
}

/** Returns the leases table HTML for the Status tab. */
function dhcp_leases(): void {
    $tpl = new template_admin();
    $id  = intval($_GET["leases"]);
    $page = CurrentPageName();

    $h   = [];
    $t   = time();
    $h[] = "<div style='margin-bottom:10px;margin-top:10px;text-align:right'>";
    $h[] = "  <button class='btn btn-default btn-sm' onclick=\"LoadAjaxSilent('dhcp-leases-div-$id','$page?table=$id')\">";
    $h[] = "    <i class='fas fa-sync-alt'></i> {refresh}";
    $h[] = "  </button>";
    $h[] = "</div>";
    $h[] = "<div id='dhcp-leases-div-$id'>";
    $h[] = "</div>";
    $h[] = "<script>LoadAjaxSilent('dhcp-leases-div-$id','$page?table=$id')</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

function dhcp_leases_table():bool{
    $tpl = new template_admin();
    $id  = intval($_GET["table"]);
    $page = CurrentPageName();

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/$id/assigned-ips"),true);
    if (!is_object($json) && !is_array($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return false;
    }
    if (is_object($json) && isset($json["Status"]) && !$json["Status"]) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json["Error"] ?? "{error}")));
        return false;
    }

    $leases = is_array($json) ? $json : ($json["leases"]?? []);

    $h   = [];



    if (empty($leases)) {
        echo $tpl->div_info("{no_active_leases}");
        return false;

    }

    $h[] = "<table class='table table-stripped' data-page-size='50'>";
    $h[] = "  <thead><tr>";
    $h[] = "    <th data-sortable='true' data-type='text'>IP</th>";
    $h[] = "    <th data-sortable='true' data-type='text'>MAC</th>";
    $h[] = "    <th data-sortable='true' data-type='text'>{hostname}</th>";
    $h[] = "    <th data-sortable='true' data-type='text'>{expires}</th>";
    $h[] = "  </tr></thead><tbody>";
    $TRCLASS = null;

    foreach ($leases["leases"] as $l) {
            if ($TRCLASS === 'footable-odd') { $TRCLASS = null; } else { $TRCLASS = 'footable-odd'; }
            $ip       = htmlspecialchars($l["ip_address"]   ?? $l["ip_address"] ?? '');
            $mac      = htmlspecialchars($l["hardware_address"] ?? $l["mac"] ?? '');
            $hn       = htmlspecialchars($l["client_hostname"]  ?? $l["hostname"] ?? '—');
            // Go timestamps use Z suffix — use new DateTime() not substr()
            $endsRaw  = $l["ends"] ?? $l["expire"] ?? '';
            $endsStr  = '—';
            if ($endsRaw !== '') {
                try { $endsStr = (new DateTime($endsRaw))->format('Y-m-d H:i'); } catch (Exception $e) {}
            }
            $h[] = "    <tr class='$TRCLASS'>";
            $h[] = "    <td>$ip</td>";
            $h[] = "    <td><span style='font-family:monospace'>$mac</span></td>";
            $h[] = "    <td>$hn</td><td>$endsStr</td>";
            $h[] = "    </tr>";
    }

    $h[] = "  </tbody><tfoot><tr><td colspan='4'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $h[] = "</table>";
    $h[] = "<script>NoSpinner();" . implode("\n", $tpl->ICON_SCRIPTS);
    $h[] = "</script>";
    $h[] = "</div>";
    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    return true;
}

/** Returns a small health status badge (for auto-refresh). */
function dhcp_health_badge(): void {
    $tpl = new template_admin();
    $id  = intval($_GET["health-badge"]);

    $health = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/$id/health"));
    if (!is_object($health) || !$health->installed) {
        echo "<span class='label label-danger'><i class='fas fa-times-circle'></i> {not_installed}</span>";
        return;
    }
    if ($health->running) {
        $pid = intval($health->pid ?? 0);
        echo "<span class='label label-success'><i class='fas fa-check-circle'></i> {running}"
           . ($pid > 0 ? " (PID $pid)" : '') . "</span>";
    } else {
        echo "<span class='label label-warning'><i class='fas fa-pause-circle'></i> {stopped}</span>";
    }
}

/** Triggers dhcpd installation. Returns JSON-like HTML response. */
function dhcp_install(): void {
    $tpl = new template_admin();
    $id  = intval($_POST["install"]);

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/dhcp/$id/install", []));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    if (isset($json->Status) && !$json->Status) {
        $err = htmlspecialchars($json->Error ?? "{error}");
        // 409 = already running
        if (stripos($err, 'already') !== false || stripos($err, 'progress') !== false) {
            echo $tpl->_ENGINE_parse_body($tpl->div_warning("<i class='fas fa-info-circle'></i> {installation_already_in_progress}"));
        } else {
            echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        }
        return;
    }
    // 202 accepted — signal JS to start polling
    echo "INSTALLING";
}

/** Polls installation status. Returns HTML status block. */
function dhcp_install_status(): void {
    $tpl = new template_admin();
    $id  = intval($_GET["install-status"]);
    $page = CurrentPageName();

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/$id/install/status"));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $running = !empty($json->running);
    $done    = !empty($json->done);
    $ok      = !empty($json->ok);
    $logs    = $json->logs ?? [];
    $last5   = array_slice((array)$logs, -5);

    $h = [];
    if ($running && !$done) {
        $h[] = "<div class='alert alert-info'>";
        $h[] = "  <i class='fas fa-spinner fa-spin'></i> <strong>{installing_dhcp}</strong>";
        $h[] = "  <div id='install-log-$id' style='margin-top:8px;font-family:monospace;font-size:12px;max-height:80px;overflow-y:auto'>";
        foreach ($last5 as $line) { $h[] = htmlspecialchars($line) . "<br>"; }
        $h[] = "  </div>";
        $h[] = "</div>";
        $h[] = "<script>setTimeout(function(){LoadAjaxSilent('dhcp-install-status-$id','$page?install-status=$id');},2000);</script>";
    } elseif ($done && $ok) {
        $h[] = "<div class='alert alert-success'>";
        $h[] = "  <i class='fas fa-check-circle'></i> <strong>{dhcp_installed_successfully}</strong>";
        $h[] = "</div>";
        // Reload the full tab content
        $h[] = "<script>setTimeout(function(){LoadAjaxSilent('dhcp-main-div-$id','$page?id=$id');},1500);</script>";
    } else {
        $lastErr = htmlspecialchars(end($last5) ?: "{unknown_error}");
        $h[] = "<div class='alert alert-danger'>";
        $h[] = "  <i class='fas fa-times-circle'></i> <strong>{installation_failed}</strong><br>";
        $h[] = "  <small style='font-family:monospace'>$lastErr</small>";
        $h[] = "</div>";
    }

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

/** Returns only the running banner (for banner-only refresh after restart). */
function dhcp_running_banner_ajax(): void {
    $tpl    = new template_admin();
    $id     = intval($_GET["running-banner"]);
    $page   = CurrentPageName();
    $health = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/$id/health"));
    $status = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$id"));
    echo $tpl->_ENGINE_parse_body(dhcp_running_banner($id, $page, $health, $status));
}

/** Triggers dhcpd service restart. Returns "RESTARTING" sentinel or error HTML. */
function dhcp_restart(): void {
    $tpl = new template_admin();
    $id  = intval($_POST["dhcp_agent_id"] ?? 0);
    if ($id <= 0) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{invalid_agent_id}"));
        return;
    }

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/dhcp/$id/restart", []));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    if (isset($json->Status) && !$json->Status) {
        $err = htmlspecialchars($json->Error ?? "{error}");
        if (stripos($err, 'already') !== false || stripos($err, 'progress') !== false) {
            echo $tpl->_ENGINE_parse_body($tpl->div_warning("<i class='fas fa-info-circle'></i> {restart_already_in_progress}"));
        } else {
            echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        }
        return;
    }
    // 202 accepted — signal JS to start polling
    echo "RESTARTING";
}

/** Polls restart progress. Returns HTML status block. */
function dhcp_restart_status(): void {
    $tpl  = new template_admin();
    $id   = intval($_GET["restart-status"]);
    $page = CurrentPageName();

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/$id/restart/status"));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $running = !empty($json->running);
    $done    = !empty($json->done);
    $ok      = !empty($json->ok);
    $logs    = $json->logs ?? [];
    $last5   = array_slice((array)$logs, -5);

    $h = [];
    if ($running && !$done) {
        $h[] = "<div class='alert alert-info'>";
        $h[] = "  <i class='fas fa-spinner fa-spin'></i> <strong>{restarting_dhcp}</strong>";
        $h[] = "  <div style='margin-top:8px;font-family:monospace;font-size:12px;max-height:80px;overflow-y:auto'>";
        foreach ($last5 as $line) { $h[] = htmlspecialchars($line) . "<br>"; }
        $h[] = "  </div>";
        $h[] = "</div>";
        $h[] = "<script>setTimeout(function(){LoadAjaxSilent('dhcp-restart-result-$id','$page?restart-status=$id');},2000);</script>";
    } elseif ($done && $ok) {
        $h[] = "<div class='alert alert-success'>";
        $h[] = "  <i class='fas fa-check-circle'></i> <strong>{dhcp_restarted_successfully}</strong>";
        $h[] = "</div>";
        $h[] = "<script>setTimeout(function(){LoadAjaxSilent('dhcp-banner-$id','$page?running-banner=$id');},1200);</script>";
    } else {
        $lastErr = htmlspecialchars(end($last5) ?: "{unknown_error}");
        $h[] = "<div class='alert alert-danger'>";
        $h[] = "  <i class='fas fa-times-circle'></i> <strong>{restart_failed}</strong><br>";
        $h[] = "  <small style='font-family:monospace'>$lastErr</small>";
        $h[] = "</div>";
    }

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── UI helpers ────────────────────────────────────────────────────────────────

/** Renders the "not installed" panel with install button and version info. */
function dhcp_not_installed_box(int $id, string $page, $status): string {
    // dhcp3 field from cached agent status (omitted entirely when /usr/sbin/dhcpd absent)
    $dhcp3   = (is_object($status) && isset($status->dhcp3)) ? $status->dhcp3 : null;
    $exists  = is_object($dhcp3) && !empty($dhcp3->exists);
    $version = is_object($dhcp3) ? htmlspecialchars($dhcp3->version ?? '') : '';

    $h   = [];
    $h[] = "<div id='dhcp-main-div-$id'>";
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-server'></i>&nbsp; ISC DHCP Server</h5></div>";
    $h[] = "  <div class='ibox-content'>";

    if ($exists && $version !== '' && $version !== '0.0.0') {
        $h[] = "    <p><i class='fas fa-check-circle' style='color:#1ab394'></i> {dhcp_binary_found}";
        $h[] = "       &nbsp;<span class='badge' style='background:#1c84c6;color:#fff'>$version</span></p>";
    } elseif ($exists) {
        $h[] = "    <p><i class='fas fa-check-circle' style='color:#1ab394'></i> {dhcp_binary_found}</p>";
    } else {
        $h[] = "    <div class='alert alert-warning'>";
        $h[] = "      <i class='fas fa-exclamation-triangle'></i> {dhcp_not_installed_explain}";
        $h[] = "    </div>";
    }

    $h[] = "    <div id='dhcp-install-status-$id'></div>";
    $h[] = "    <div id='dhcp-install-btn-$id' style='margin-top:15px'>";
    $h[] = "      <button class='btn btn-primary' onclick='DhcpInstall_$id()'>";
    $h[] = "        <i class='fas fa-download'></i> {install_dhcp_server}";
    $h[] = "      </button>";
    $h[] = "    </div>";
    $h[] = "  </div></div>";
    $h[] = "</div>";
    $h[] = dhcp_install_js_block($id, $page);
    return implode("\n", $h);
}
function dhcp_running_label():bool{

    $id=intval($_GET["label-status"]);

    $health = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/$id/health"),true);
    $status = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$id"),true);

    if(!isset($health["installed"])){
        $health["installed"]=false;
        $health["running"]=false;
        $health["pid"]=0;
    }

    if(!isset($status["dhcp3"])){
        $status["dhcp3"]["version"]=$health["version"]="";
    }

    $version   = $status["dhcp3"]["version"];

    if ($health["running"]) {
        $h[] = "    <span class='label label-primary'><i class='fas fa-check-circle'></i> {running}</span>";
        if ($health["pid"] > 0) $h[] = " &nbsp;<small class='text-muted'>PID {$health["pid"]}</small>";
    } else {
        $h[] = "    <span class='label label-danger'><i class='fas fa-times-circle'></i> {stopped}</span>";
    }
    if ($version !== '' && $version !== '0.0.0') {
        $h[] = "    &nbsp;<span class='badge' style='background:#676a6c;color:#fff'>$version</span>";
    }
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    return true;
}
/** Renders the running state banner + restart button + restart result area. */
function dhcp_running_banner(int $id, string $page): string {
    $tpl=new template_admin();
    $refresh=$tpl->RefreshInterval_js("dhcp-service-status-$id",$page,"label-status=$id");

    $h   = [];
    $h[] = "<div id='dhcp-banner-$id'>";
    $h[] = "<div class='ibox' style='margin-bottom:10px'>";
    $h[] = "  <div class='ibox-content' style='padding:10px 15px'>";
    $h[] = "  <div class='row'>";
    $h[] = "  <div class='col-md-8'><span id='dhcp-service-status-$id'></span>";
    $h[] = "  </div>";
    $h[] = "  <div class='col-md-4 text-right'>";
    $h[] = "    <button class='btn btn-warning btn-xs' onclick='DhcpRestart_$id()'>";
    $h[] = "      <i class='fas fa-redo'></i> {restart}";
    $h[] = "    </button>";
    $h[] = "  </div></div>";
    $h[] = "  <div id='dhcp-restart-result-$id'></div>";
    $h[] = "  </div></div>";
    $h[] = "</div>";
    $h[] = dhcp_restart_js_block($id, $page);
    $h[]="<script>$refresh</script>";
    return implode("\n", $h);
}

/** Emits DhcpRestart_{id}() JavaScript with swal confirmation and polling. */
function dhcp_restart_js_block(int $id, string $page): string {
    $tpl=new template_admin();
    $restart_dhcp=$tpl->javascript_parse_text("{restart_dhcp}");
    $confirm_restart_dhcp=$tpl->javascript_parse_text("{confirm_restart_dhcp}");
    $l   = [];
    $l[] = "<script>";
    $l[] = "function DhcpRestart_$id(){";
    $l[] = "  swal({";
    $l[] = "    title:'$restart_dhcp',";
    $l[] = "    text:'$confirm_restart_dhcp',";
    $l[] = "    type:'warning',";
    $l[] = "    showCancelButton:true,";
    $l[] = "    confirmButtonColor:'#f8ac59',";
    $l[] = "    confirmButtonText:'{restart}',";
    $l[] = "    cancelButtonText:'{cancel}'";
    $l[] = "  },function(isConfirm){";
    $l[] = "    if(!isConfirm) return;";
    $l[] = "    \$('#dhcp-restart-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {restarting}...</div>');";
    $l[] = "    \$.post('$page',{restart:1,dhcp_agent_id:$id},function(r){";
    $l[] = "      if(r==='RESTARTING'){";
    $l[] = "        LoadAjaxSilent('dhcp-restart-result-$id','$page?restart-status=$id');";
    $l[] = "      } else {";
    $l[] = "        \$('#dhcp-restart-result-$id').html(r);";
    $l[] = "      }";
    $l[] = "    });";
    $l[] = "  });";
    $l[] = "}";
    $l[] = "</script>";
    return implode("\n", $l);
}

/** Emits DhcpInstall_{id}() JavaScript and polling logic. */
function dhcp_install_js_block(int $id, string $page): string {
    $l   = [];
    $l[] = "<script>";
    $l[] = "function DhcpInstall_$id(){";
    $l[] = "  \$('#dhcp-install-btn-$id').hide();";
    $l[] = "  \$('#dhcp-install-status-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {starting_installation}...</div>');";
    $l[] = "  \$.post('$page',{install:$id},function(r){";
    $l[] = "    if(r==='INSTALLING'){";
    $l[] = "      LoadAjaxSilent('dhcp-install-status-$id','$page?install-status=$id');";
    $l[] = "    } else {";
    $l[] = "      \$('#dhcp-install-status-$id').html(r);";
    $l[] = "      \$('#dhcp-install-btn-$id').show();";
    $l[] = "    }";
    $l[] = "  });";
    $l[] = "}";
    $l[] = "</script>";
    return implode("\n", $l);
}
