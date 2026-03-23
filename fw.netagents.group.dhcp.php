<?php
/**
 * DHCP management overview for an agent group.
 * Shows DHCP install status per agent and links to per-section group pages.
 * Group operations (scopes, reservations, apply) are delegated to sub-pages.
 *
 * Entry:  Loadjs('fw.netagents.group.dhcp.php?dhcp-js={group_id}')    — opens dialog
 *         LoadAjax('div','fw.netagents.group.dhcp.php?id={group_id}')  — inline with tabs
 * API:    GET /netagents/groups/{id}                    — group metadata + agent list
 *         GET /netagents/dhcp/{agent_id}/health         — per-agent health
 *         (Group-level routes: POST /netagents/dhcp/group/{id}/config/apply)
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }
if(isset($_GET["tabs"]))        { gdhcp_tabs();        exit; }
if (isset($_GET["dhcp-js"]))   { gdhcp_js();          exit; }
if (isset($_GET["status"]))    { gdhcp_status();       exit; }
if (isset($_GET["leases-data"])){ gdhcp_leases_data(); exit; }
if (isset($_GET["leases"]))    { gdhcp_leases();       exit; }

// ── Helpers ───────────────────────────────────────────────────────────────────

function gid(): int {
    return intval($_GET["id"] ?? $_POST["id"] ?? $_GET["dhcp-js"] ?? 0);
}

// ── Dispatcher actions ────────────────────────────────────────────────────────

/** Opens a 970px dialog containing the group DHCP tabs. */
function gdhcp_js(): bool {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["dhcp-js"]);
    $grp       = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    $groupName = (is_object($grp) && !empty($grp->name)) ? htmlspecialchars($grp->name) : "Group #$id";
    return $tpl->js_dialog5("{dhcp_management} — $groupName", "$page?tabs=$id", 970);
}



/** Renders the group DHCP overview with per-agent DHCP status and tab links. */
function gdhcp_tabs(): bool {
    $tpl  = new template_admin();
    $id   = intval($_GET["tabs"]);
    $page = CurrentPageName();

    $grp = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    if (!is_object($grp)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return false;
    }
    if (isset($grp->Status) && !$grp->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($grp->Error ?? "{error}")));
        return false;
    }

    $groupName  = htmlspecialchars($grp->name ?? "Group #$id");
    $agentCount = intval($grp->agent_count ?? 0);
    $agents     = $grp->agents ?? [];
    $clusterMode = intval($grp->cluster_mode ?? 0);

    $h   = [];

    // ── Group info banner ──
    $h[] = "<div class='alert alert-info' style='margin-bottom:15px;margin-top:10px'>";
    $h[] = "  <i class='fas fa-layer-group'></i> <strong>$groupName</strong>";
    $h[] = "  &nbsp;<span class='badge' style='background:#1c84c6;color:#fff'>$agentCount</span> {agents}";
    if ($clusterMode == 1) {
        $h[] = "  &nbsp;<span class='label label-warning'><i class='fas fa-server'></i> {cluster_mode}</span>";
    } else {
        $h[] = "  &nbsp;<span class='label label-default'><i class='fas fa-broadcast-tower'></i> {push_mode}</span>";
    }
    $h[] = "</div>";

    // ── Tabs: Status + group action pages ──
    if ($clusterMode == 1) {
        $tabs = [
            "{status}"       => "$page?id=$id&status=1",
            "{leases}"       => "$page?id=$id&leases=1",
            "{scopes}"       => "fw.netagents.group.dhcp.central.scopes.php?id=$id",
            "{APP_DHCP_RELAY}"       => "fw.netagents.group.dhcp.central.relays.php?id=$id",
            "{reservations}" => "fw.netagents.group.dhcp.central.reservations.php?id=$id",
            "{apply}"        => "fw.netagents.group.dhcp.central.apply.php?id=$id",
        ];
    } else {
        $tabs = [
            "{status}"       => "$page?id=$id&status=1",
            "{leases}"       => "$page?id=$id&leases=1",
            "{scopes}"       => "fw.netagents.group.dhcp.scopes.php?id=$id",
            "{reservations}" => "fw.netagents.group.dhcp.reservations.php?id=$id",
            "{configuration}"=> "fw.netagents.group.dhcp.config.php?id=$id",
        ];
    }
    $h[] = $tpl->tabs_default($tabs);

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    return true;
}

// ── Status tab (per-agent DHCP health) ───────────────────────────────────────



function gdhcp_status(): void {
    $tpl = new template_admin();
    $id  = intval($_GET["id"] ?? 0);

    $grp    = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    $agents = (is_object($grp) && isset($grp->agents)) ? (array)$grp->agents : [];

    $h   = [];
    $h[] = "<div class='ibox' style='margin-top:10px'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-server'></i>&nbsp; {dhcp_status_per_agent}</h5></div>";
    $h[] = "  <div class='ibox-content' style='padding:0'>";

    if (empty($agents)) {
        $h[] = "<p style='padding:15px;color:#999'><i>{no_agents_in_group}</i></p>";
    } else {
        $h[] = "<table class='table table-striped table-condensed' style='margin:0'>";
        $h[] = "  <thead><tr>";
        $h[] = "    <th>{hostname}</th>";
        $h[] = "    <th>{version}</th>";
        $h[] = "    <th>{status}</th>";
        $h[] = "    <th>{actions}</th>";
        $h[] = "  </tr></thead><tbody>";

        foreach ($agents as $agent) {
            if (!is_object($agent)) continue;
            $agentId  = intval($agent->id ?? 0);
            $hostname = htmlspecialchars($agent->hostname ?? "Agent #$agentId");

            // Fetch health + status for this agent
            $health = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/$agentId/health"));
            $status = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$agentId"));
            $dhcp3  = (is_object($status) && isset($status->dhcp3)) ? $status->dhcp3 : null;
            $version = is_object($dhcp3) ? htmlspecialchars($dhcp3->version ?? '') : '';

            if (!is_object($health) || (isset($health->Status) && !$health->Status)) {
                $badge   = "<span class='label label-warning'><i class='fas fa-question-circle'></i> {unknown}</span>";
                $actions = '';
            } elseif (!$health->installed) {
                $badge   = "<span class='label label-default'><i class='fas fa-times'></i> {not_installed}</span>";
                $actions = "<a href='#' onclick=\"Loadjs('fw.netagents.dhcp.php?dhcp-js=$agentId');return false;\" class='btn btn-xs btn-default'>"
                         . "<i class='fas fa-download'></i> {install}</a>";
            } elseif ($health->running) {
                $pid     = intval($health->pid ?? 0);
                $badge   = "<span class='label label-success'><i class='fas fa-check-circle'></i> {running}"
                         . ($pid > 0 ? " (PID $pid)" : '') . "</span>";
                $actions = "<a href='#' onclick=\"Loadjs('fw.netagents.dhcp.php?dhcp-js=$agentId');return false;\" class='btn btn-xs btn-primary'>"
                         . "<i class='fas fa-cogs'></i> {manage}</a>";
            } else {
                $badge   = "<span class='label label-danger'><i class='fas fa-stop-circle'></i> {stopped}</span>";
                $actions = "<a href='#' onclick=\"Loadjs('fw.netagents.dhcp.php?dhcp-js=$agentId');return false;\" class='btn btn-xs btn-warning'>"
                         . "<i class='fas fa-redo'></i> {restart}</a>";
            }

            $h[] = "  <tr>";
            $h[] = "    <td><strong>$hostname</strong></td>";
            $h[] = "    <td>" . ($version !== '' && $version !== '0.0.0' ? "<span class='badge' style='background:#676a6c;color:#fff'>$version</span>" : "—") . "</td>";
            $h[] = "    <td>$badge</td>";
            $h[] = "    <td>$actions</td>";
            $h[] = "  </tr>";
        }
        $h[] = "  </tbody></table>";
    }
    $h[] = "  </div></div>";
    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Leases tab (all agents, server-side pagination + filtering) ───────────────

/**
 * Renders the leases shell: filter bar + result container.
 * Data is fetched via ?leases-data=1 (JSON) and rendered by JS.
 * The Go endpoint fetches all agents concurrently, filters, and paginates.
 */
function gdhcp_leases(): void {
    $tpl  = new template_admin();
    $id   = intval($_GET["id"] ?? 0);
    $page = CurrentPageName();
    $grp  = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    $clusterMode = (is_object($grp) ? intval($grp->cluster_mode ?? 0) : 0);

    $h = [];
    $h[] = "<div style='margin-top:10px'>";

    // ── Filter bar ──
    $h[] = "<div class='ibox' style='margin-bottom:12px'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-filter'></i>&nbsp; {filters}</h5></div>";
    $h[] = "  <div class='ibox-content' style='padding:10px 15px'>";
    $h[] = "  <div class='row'>";

    // IP search — prefix match (e.g. "192.168.1." finds whole subnet)
    $h[] = "    <div class='col-md-3'>";
    $h[] = "      <label style='font-size:11px;margin-bottom:3px;color:#999'>{search_by_ip}</label>";
    $h[] = "      <div class='input-group'>";
    $h[] = "        <input type='text' id='gl-ip-$id' class='form-control' placeholder='192.168.1.'>";
    $h[] = "        <span class='input-group-btn'>";
    $h[] = "          <button class='btn btn-default btn-sm' onclick='GlLoad_$id(1)'><i class='fas fa-search'></i></button>";
    $h[] = "        </span>";
    $h[] = "      </div>";
    $h[] = "    </div>";

    // Hostname search — substring match
    $h[] = "    <div class='col-md-3'>";
    $h[] = "      <label style='font-size:11px;margin-bottom:3px;color:#999'>{search_by_hostname}</label>";
    $h[] = "      <div class='input-group'>";
    $h[] = "        <input type='text' id='gl-hostname-$id' class='form-control' placeholder='{hostname}'>";
    $h[] = "        <span class='input-group-btn'>";
    $h[] = "          <button class='btn btn-default btn-sm' onclick='GlLoad_$id(1)'><i class='fas fa-search'></i></button>";
    $h[] = "        </span>";
    $h[] = "      </div>";
    $h[] = "    </div>";

    // State filter
    $h[] = "    <div class='col-md-2'>";
    $h[] = "      <label style='font-size:11px;margin-bottom:3px;color:#999'>{state}</label>";
    $h[] = "      <select id='gl-state-$id' class='form-control' onchange='GlLoad_$id(1)'>";
    $h[] = "        <option value=''>{all}</option>";
    $h[] = "        <option value='active'>{active2}</option>";
    $h[] = "        <option value='expired'>{expired}</option>";
    $h[] = "        <option value='abandoned'>{abandoned}</option>";
    $h[] = "      </select>";
    $h[] = "    </div>";

    // Agent filter
    $h[] = "    <div class='col-md-2'>";
    $h[] = "      <label style='font-size:11px;margin-bottom:3px;color:#999'>{agent}</label>";
    $h[] = "      <select id='gl-agent-$id' class='form-control' onchange='GlLoad_$id(1)'>";
    $h[] = "        <option value=''>{all}</option>";
    $h[] = "      </select>";
    $h[] = "    </div>";

    // Actions
    $h[] = "    <div class='col-md-2' style='padding-top:18px'>";
    $h[] = "      <button class='btn btn-default btn-block' onclick='GlReset_$id()'><i class='fas fa-times'></i> {reset}</button>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  </div></div>";

    // ── Summary badges ──
    $h[] = "<div id='gl-summary-$id' style='margin-bottom:10px'></div>";

    // ── Table + pagination ──
    $h[] = "<div id='gl-table-$id'><div class='alert alert-info'><i class='fas fa-spinner fa-spin'></i> {loading}...</div></div>";
    $h[] = "<div id='gl-pager-$id' style='margin-top:8px;text-align:center'></div>";

    $h[] = "</div>";
    $h[] = gdhcp_leases_js($id, $page, $clusterMode);

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

/**
 * Returns JSON for the leases table (server-side pagination via Go endpoint).
 * Called by ?leases-data=1&page=N&limit=100&ip=X&hostname=Y&state=Z&agent=W
 */
function gdhcp_leases_data(): void {
    $tpl      = new template_admin();
    $id       = intval($_GET["id"] ?? 0);
    $page     = max(1, intval($_GET["page"]  ?? 1));
    $limit    = intval($_GET["limit"] ?? 100);
    $limit    = ($limit < 10 || $limit > 500) ? 100 : $limit;
    $ip       = trim($_GET["ip"]       ?? '');
    $hostname = trim($_GET["hostname"] ?? '');
    $state    = trim($_GET["state"]    ?? '');
    $agent    = trim($_GET["agent"]    ?? '');

    // Build query string — agent filter goes into generic search when set
    $qs = http_build_query(array_filter([
        'page'     => $page,
        'limit'    => $limit,
        'ip'       => $ip,
        'hostname' => $hostname,
        'state'    => $state,
        'search'   => $agent,   // agent name goes into generic search
    ], fn($v) => $v !== '' && $v !== 0));

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/group/$id/leases?$qs"));
    if (!is_object($json) || (isset($json->Status) && !$json->Status)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => htmlspecialchars(is_object($json) ? ($json->Error ?? 'error') : 'error')]);
        return;
    }

    $leases    = $json->leases    ?? [];
    $agents    = $json->agents    ?? [];
    $total     = intval($json->total     ?? 0);
    $active    = intval($json->active    ?? 0);
    $expired   = intval($json->expired   ?? 0);
    $abandoned = intval($json->abandoned ?? 0);
    $pages     = intval($json->pages     ?? 1);

    // Build table rows HTML server-side (translations resolved here)
    $rows = [];
    $TRCLASS = null;
    foreach ($leases as $l) {
        if ($TRCLASS === 'footable-odd') { $TRCLASS = null; } else { $TRCLASS = 'footable-odd'; }
        $lipAddr = htmlspecialchars($l->ip_address      ?? '');
        $mac     = htmlspecialchars($l->hardware_address ?? '');
        $hn      = htmlspecialchars($l->client_hostname  ?? '—');
        $lagent  = htmlspecialchars($l->agent_name       ?? '');
        $lstate  = strtolower($l->binding_state ?? '');
        $endsRaw = $l->ends ?? '';
        $endsStr = '—';
        if ($endsRaw !== '') {
            try { $endsStr = (new DateTime($endsRaw))->format('Y-m-d H:i'); } catch (Exception $e) {}
        }
        if ($lstate === 'active') {
            $badge = "<span class='label label-success'>{active2}</span>";
        } elseif ($lstate === 'abandoned') {
            $badge = "<span class='label label-danger'>{abandoned}</span>";
        } else {
            $badge = "<span class='label label-default'>{expired}</span>";
        }
        $macAttr = htmlspecialchars(json_encode($l->hardware_address ?? ''), ENT_QUOTES);
        $ipAttr  = htmlspecialchars(json_encode($l->ip_address ?? ''), ENT_QUOTES);
        $hnAttr  = htmlspecialchars(json_encode($l->client_hostname ?? ''), ENT_QUOTES);
        $fixBtn  = "<a href='#' onclick=\"GlFixLease_$id($macAttr,$ipAttr,$hnAttr);return false;\" "
                 . "class='btn btn-xs btn-primary' title='{fixed}'>"
                 . "<i class='fas fa-plus'></i> {fixed}</a>";
        $rows[] = "<tr class='$TRCLASS'>"
                . "<td>$lagent</td>"
                . "<td>$lipAddr</td>"
                . "<td><span style='font-family:monospace'>$mac</span></td>"
                . "<td>$hn</td>"
                . "<td>$badge</td>"
                . "<td>$endsStr</td>"
                . "<td style='white-space:nowrap'>$fixBtn</td>"
                . "</tr>";
    }

    header('Content-Type: application/json');
    echo json_encode([
        'total'    => $total,
        'active'   => $active,
        'expired'  => $expired,
        'abandoned'=> $abandoned,
        'page'     => $page,
        'pages'    => $pages,
        'limit'    => $limit,
        'agents'   => $agents,
        'rows_html'=> $tpl->_ENGINE_parse_body(implode('', $rows)),
    ]);
}

function gdhcp_leases_js(int $id, string $page, int $clusterMode = 0): string {
    if ($clusterMode == 1) {
        $resPage = 'fw.netagents.group.dhcp.central.reservations.php';
    } else {
        $resPage = 'fw.netagents.group.dhcp.reservations.php';
    }
    $l = [];
    $l[] = "<script>";
    $l[] = "(function(){";
    $l[] = "var glPage_$id=1, glLimit_$id=100;";
    $l[] = "window.GlFixLease_$id=function(mac,ip,hn){";
    $l[] = "  Loadjs('$resPage?res-add-js=$id&from_mac='+encodeURIComponent(mac)+'&from_ip='+encodeURIComponent(ip)+'&from_hostname='+encodeURIComponent(hn));";
    $l[] = "};";

    $l[] = "window.GlReset_$id=function(){";
    $l[] = "  \$('#gl-ip-$id,#gl-hostname-$id').val('');";
    $l[] = "  \$('#gl-state-$id,#gl-agent-$id').val('');";
    $l[] = "  GlLoad_$id(1);";
    $l[] = "};";

    $l[] = "window.GlLoad_$id=function GlLoad_$id(pg){";
    $l[] = "  glPage_$id = pg || glPage_$id;";
    $l[] = "  var params = {";
    $l[] = "    id: $id, 'leases-data': 1,";
    $l[] = "    page: glPage_$id, limit: glLimit_$id,";
    $l[] = "    ip:       \$.trim(\$('#gl-ip-$id').val()),";
    $l[] = "    hostname: \$.trim(\$('#gl-hostname-$id').val()),";
    $l[] = "    state:    \$('#gl-state-$id').val(),";
    $l[] = "    agent:    \$('#gl-agent-$id').val()";
    $l[] = "  };";
    $l[] = "  \$('#gl-table-$id').html('<div style=\"padding:20px;text-align:center\"><i class=\"fas fa-spinner fa-spin fa-2x\" style=\"color:#aaa\"></i></div>');";
    $l[] = "  \$.getJSON('$page', params, function(d){";
    $l[] = "    if(d.error){ \$('#gl-table-$id').html('<div class=\"alert alert-danger\">'+d.error+'</div>'); return; }";

    // Populate agent dropdown once from first response
    $l[] = "    if(\$('#gl-agent-$id option').length<=1 && d.agents && d.agents.length){";
    $l[] = "      \$.each(d.agents,function(i,a){ \$('#gl-agent-$id').append(\$('<option>').val(a).text(a)); });";
    $l[] = "    }";

    // Summary badges
    $l[] = "    var s='<span class=\"badge\" style=\"background:#676a6c;color:#fff;font-size:12px;padding:4px 8px;margin-right:4px\">{total}: '+d.total+'</span>';";
    $l[] = "    if(d.active)    s+='<span class=\"badge\" style=\"background:#1ab394;color:#fff;font-size:12px;padding:4px 8px;margin-right:4px\"><i class=\"fas fa-check-circle\"></i> {active2}: '+d.active+'</span>';";
    $l[] = "    if(d.expired)   s+='<span class=\"badge\" style=\"background:#676a6c;color:#fff;font-size:12px;padding:4px 8px;margin-right:4px\">{expired}: '+d.expired+'</span>';";
    $l[] = "    if(d.abandoned) s+='<span class=\"badge\" style=\"background:#ed5565;color:#fff;font-size:12px;padding:4px 8px\">{abandoned}: '+d.abandoned+'</span>';";
    $l[] = "    \$('#gl-summary-$id').html(s);";

    // Table
    $l[] = "    if(!d.rows_html||!d.total){";
    $l[] = "      \$('#gl-table-$id').html('<div class=\"alert alert-info\">{no_leases}</div>');";
    $l[] = "      \$('#gl-pager-$id').html(''); return;";
    $l[] = "    }";
    $l[] = "    var tbl='<table class=\"table table-striped table-condensed\" style=\"margin:0\">';";
    $l[] = "    tbl+='<thead><tr style=\"background:#f5f5f5\">';";
    $l[] = "    tbl+='<th>{agent}</th><th>IP</th><th>MAC</th><th>{hostname}</th><th>{state}</th><th>{expires}</th><th></th>';";
    $l[] = "    tbl+='</tr></thead><tbody>'+d.rows_html+'</tbody></table>';";
    $l[] = "    \$('#gl-table-$id').html(tbl);";

    // Pagination
    $l[] = "    var p='';";
    $l[] = "    if(d.pages>1){";
    $l[] = "      p+='<ul class=\"pagination\" style=\"margin:0\">';";
    $l[] = "      p+='<li class=\"'+(d.page<=1?'disabled':'')+'\"><a href=\"#\" onclick=\"GlLoad_$id('+(d.page-1)+');return false\">&#8249;</a></li>';";
    $l[] = "      var from=Math.max(1,d.page-3),to=Math.min(d.pages,d.page+3);";
    $l[] = "      if(from>1) p+='<li><a href=\"#\" onclick=\"GlLoad_$id(1);return false\">1</a></li><li class=\"disabled\"><a>…</a></li>';";
    $l[] = "      for(var i=from;i<=to;i++) p+='<li class=\"'+(i===d.page?'active':'')+'\"><a href=\"#\" onclick=\"GlLoad_$id('+i+');return false\">'+i+'</a></li>';";
    $l[] = "      if(to<d.pages) p+='<li class=\"disabled\"><a>…</a></li><li><a href=\"#\" onclick=\"GlLoad_$id('+d.pages+');return false\">'+d.pages+'</a></li>';";
    $l[] = "      p+='<li class=\"'+(d.page>=d.pages?'disabled':'')+'\"><a href=\"#\" onclick=\"GlLoad_$id('+(d.page+1)+');return false\">&#8250;</a></li>';";
    $l[] = "      p+='</ul>';";
    $l[] = "      p+='<small class=\"text-muted\" style=\"display:block;margin-top:4px\">{page} '+d.page+' / '+d.pages+' &mdash; '+d.total+' {leases}</small>';";
    $l[] = "    }";
    $l[] = "    \$('#gl-pager-$id').html(p);";
    $l[] = "  }).fail(function(){ \$('#gl-table-$id').html('<div class=\"alert alert-danger\">{error}</div>'); });";
    $l[] = "};";

    // Enter on IP or hostname triggers search
    $l[] = "\$('#gl-ip-$id,#gl-hostname-$id').on('keydown',function(e){ if(e.keyCode===13) GlLoad_$id(1); });";

    // Initial load
    $l[] = "GlLoad_$id(1);";
    $l[] = "})();";
    $l[] = "</script>";
    return implode("\n", $l);
}
