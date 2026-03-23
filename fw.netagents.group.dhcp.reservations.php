<?php
/**
 * DHCP reservations for an agent group.
 * Shows all reservations across all agents with server-side pagination + filtering,
 * and provides a "push reservation to all agents" action.
 *
 * Entry:  LoadAjax('div','fw.netagents.group.dhcp.reservations.php?id={group_id}')
 *         Loadjs('fw.netagents.group.dhcp.reservations.php?res-add-js={group_id}') — dialog
 * API:    GET  /netagents/dhcp/group/{id}/reservations — paginated list (all agents)
 *         POST /netagents/dhcp/group/{id}/reservations — push to all agents
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }

if (isset($_GET["res-add-js"])) { gres_add_js(); exit; }
if (isset($_GET["res-form"]))   { gres_form();   exit; }
if (isset($_POST["push-res"]))  { gres_push();   exit; }
if (isset($_GET["res-data"]))   { gres_data();   exit; }
gres_page();

// ── Helpers ───────────────────────────────────────────────────────────────────

function gid(): int {
    return intval($_GET["id"] ?? $_POST["id"] ?? $_GET["res-add-js"] ?? 0);
}

// ── Main page ─────────────────────────────────────────────────────────────────

function gres_page(): void {
    $tpl  = new template_admin();
    $id   = gid();
    $page = CurrentPageName();

    $grp = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    if (!is_object($grp)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    if (isset($grp->Status) && !$grp->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($grp->Error ?? "{error}")));
        return;
    }

    $h   = [];
    $h[] = "<div id='gres-result-$id' style='margin-bottom:10px'></div>";

    // ── Group info banner ──
    $h[] = "<div style='margin-bottom:10px'><small class='text-muted'>{group_reservation_push_explain}</small></div>";


    // ── Push button ──
    $h[] = "<div style='margin-bottom:20px'>";
    $h[] = "  <a href='#' onclick=\"Loadjs('$page?res-add-js=$id');return false;\" class='btn btn-primary'>";
    $h[] = "    <i class='fas fa-bookmark'></i> {push_reservation_to_group}";
    $h[] = "  </a>";
    $h[] = "</div>";

    // ── Filter bar ──
    $h[] = "<div class='ibox' style='margin-bottom:12px'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-filter'></i>&nbsp; {filters}</h5></div>";
    $h[] = "  <div class='ibox-content' style='padding:10px 15px'>";
    $h[] = "  <div class='row'>";

    // Search (ID, name, MAC, IP)
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label style='font-size:11px;margin-bottom:3px;color:#999'>{search}</label>";
    $h[] = "      <div class='input-group'>";
    $h[] = "        <input type='text' id='gr-search-$id' class='form-control' placeholder='ID, MAC, IP...'>";
    $h[] = "        <span class='input-group-btn'>";
    $h[] = "          <button class='btn btn-default btn-sm' onclick='GrLoad_$id(1)'><i class='fas fa-search'></i></button>";
    $h[] = "        </span>";
    $h[] = "      </div>";
    $h[] = "    </div>";

    // IP prefix
    $h[] = "    <div class='col-md-3'>";
    $h[] = "      <label style='font-size:11px;margin-bottom:3px;color:#999'>{search_by_ip}</label>";
    $h[] = "      <div class='input-group'>";
    $h[] = "        <input type='text' id='gr-ip-$id' class='form-control' placeholder='192.168.1.'>";
    $h[] = "        <span class='input-group-btn'>";
    $h[] = "          <button class='btn btn-default btn-sm' onclick='GrLoad_$id(1)'><i class='fas fa-search'></i></button>";
    $h[] = "        </span>";
    $h[] = "      </div>";
    $h[] = "    </div>";

    // Agent filter
    $h[] = "    <div class='col-md-3'>";
    $h[] = "      <label style='font-size:11px;margin-bottom:3px;color:#999'>{agent}</label>";
    $h[] = "      <select id='gr-agent-$id' class='form-control' onchange='GrLoad_$id(1)'>";
    $h[] = "        <option value=''>{all}</option>";
    $h[] = "      </select>";
    $h[] = "    </div>";

    // Reset
    $h[] = "    <div class='col-md-2' style='padding-top:18px'>";
    $h[] = "      <button class='btn btn-default btn-block' onclick='GrReset_$id()'><i class='fas fa-times'></i> {reset}</button>";
    $h[] = "    </div>";

    $h[] = "  </div>";
    $h[] = "  </div></div>";

    // ── Summary + table ──
    $h[] = "<div id='gr-summary-$id' style='margin-bottom:10px'></div>";
    $h[] = "<div id='gr-table-$id'><div class='alert alert-info'><i class='fas fa-spinner fa-spin'></i> {loading}...</div></div>";
    $h[] = "<div id='gr-pager-$id' style='margin-top:8px;text-align:center'></div>";

    $h[] = gres_list_js($id, $page);

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── JSON data endpoint ────────────────────────────────────────────────────────

/**
 * Returns JSON for the reservations table (server-side pagination).
 * Called via ?res-data=1&id=N&page=P&limit=L&search=S&ip=I&agent=A
 */
function gres_data(): void {
    $tpl      = new template_admin();
    $id       = intval($_GET["id"] ?? 0);
    $page     = max(1, intval($_GET["page"]  ?? 1));
    $limit    = intval($_GET["limit"] ?? 100);
    $limit    = ($limit < 10 || $limit > 500) ? 100 : $limit;
    $search   = trim($_GET["search"] ?? '');
    $ip       = trim($_GET["ip"]     ?? '');
    $agent    = trim($_GET["agent"]  ?? '');

    $qs = http_build_query(array_filter([
        'page'   => $page,
        'limit'  => $limit,
        'search' => $search,
        'ip'     => $ip,
        'agent'  => $agent,
    ], fn($v) => $v !== '' && $v !== 0));

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/group/$id/reservations?$qs"));
    if (!is_object($json) || (isset($json->Status) && !$json->Status)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => htmlspecialchars(is_object($json) ? ($json->Error ?? 'error') : 'error')]);
        return;
    }

    $reservations = $json->reservations ?? [];
    $agents       = $json->agents       ?? [];
    $total        = intval($json->total ?? 0);
    $pages        = intval($json->pages ?? 1);

    // Build HTML rows server-side so translation keys are resolved
    $rows    = [];
    $TRCLASS = null;
    foreach ($reservations as $r) {
        if ($TRCLASS === 'footable-odd') { $TRCLASS = null; } else { $TRCLASS = 'footable-odd'; }
        $rid     = htmlspecialchars($r->id       ?? '');
        $rname   = htmlspecialchars($r->name     ?? '');
        $mac     = htmlspecialchars($r->mac      ?? '');
        $ip_addr = htmlspecialchars($r->fixed_ip ?? '');
        $scope   = htmlspecialchars($r->scope_id ?? '—');
        $lagent  = htmlspecialchars($r->agent_name ?? '');
        $rows[] = "<tr class='$TRCLASS'>"
                . "<td>$lagent</td>"
                . "<td><strong>$rid</strong>" . ($rname !== $rid && $rname !== '' ? " <small class='text-muted'>$rname</small>" : '') . "</td>"
                . "<td><span style='font-family:monospace'>$mac</span></td>"
                . "<td>$ip_addr</td>"
                . "<td><small class='text-muted'>$scope</small></td>"
                . "</tr>";
    }

    header('Content-Type: application/json');
    echo json_encode([
        'total'     => $total,
        'page'      => $page,
        'pages'     => $pages,
        'limit'     => $limit,
        'agents'    => $agents,
        'rows_html' => $tpl->_ENGINE_parse_body(implode('', $rows)),
    ]);
}

// ── Dialog opener ─────────────────────────────────────────────────────────────

function gres_add_js(): bool {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["res-add-js"]);
    $extra = '';
    if (!empty($_GET["from_ip"]))       $extra .= "&from_ip="       . urlencode($_GET["from_ip"]);
    if (!empty($_GET["from_mac"]))      $extra .= "&from_mac="      . urlencode($_GET["from_mac"]);
    if (!empty($_GET["from_hostname"])) $extra .= "&from_hostname=" . urlencode($_GET["from_hostname"]);
    return $tpl->js_dialog2("{push_reservation_to_group}", "$page?id=$id&res-form=1$extra", 700);
}

// ── Form render ───────────────────────────────────────────────────────────────

function gres_form(): void {
    $tpl  = new template_admin();
    $id   = gid();
    $page = CurrentPageName();

    $rid   = htmlspecialchars(trim($_GET["from_hostname"] ?? ''));
    $rname = '';
    $rmac  = htmlspecialchars(trim($_GET["from_mac"] ?? ''));
    $rip   = htmlspecialchars(trim($_GET["from_ip"]  ?? ''));
    $scope = '';

    $h   = [];
    $h[] = "<div id='gres-form-result-$id'></div>";

    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-bookmark'></i>&nbsp; {reservation_details}</h5></div>";
    $h[] = "  <div class='ibox-content'>";

    $h[] = "  <div class='row' style='margin-bottom:15px'>";
    $h[] = "    <div class='col-md-5'>";
    $h[] = "      <label>ID / {hostname}</label>";
    $h[] = "      <input type='text' id='gres-id-$id' class='form-control' value='$rid' placeholder='pc01'>";
    $h[] = "      <p class='help-block'>{reservation_id_explain}</p>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-7'>";
    $h[] = "      <label>{description} <small class='text-muted'>{optional}</small></label>";
    $h[] = "      <input type='text' id='gres-name-$id' class='form-control' value='$rname' placeholder='PC-01 (Reception)'>";
    $h[] = "    </div>";
    $h[] = "  </div>";

    $h[] = "  <div class='row' style='margin-bottom:15px'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>MAC</label>";
    $h[] = "      <input type='text' id='gres-mac-$id' class='form-control' value='$rmac' placeholder='aa:bb:cc:dd:ee:ff'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{fixed_ipaddr}</label>";
    $h[] = "      <input type='text' id='gres-ip-$id' class='form-control' value='$rip' placeholder='192.168.1.50'>";
    $h[] = "    </div>";
    $h[] = "  </div>";

    $h[] = "  <div class='row' style='margin-bottom:15px'>";
    $h[] = "    <div class='col-md-8'>";
    $h[] = "      <label>{scope_id} <small class='text-muted'>{optional}</small></label>";
    $h[] = "      <input type='text' id='gres-scope-$id' class='form-control' value='$scope' placeholder='ens192-192.168.1.0/24'>";
    $h[] = "      <p class='help-block'>{group_scope_id_must_exist_on_each_agent}</p>";
    $h[] = "    </div>";
    $h[] = "  </div>";

    $h[] = "  <div class='row' style='margin-bottom:8px'>";
    $h[] = "    <div class='col-md-12'><label><i class='fas fa-sliders-h'></i>&nbsp; {dhcp_options} <small class='text-muted'>({optional})</small></label></div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row' style='margin-bottom:8px'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{gateways} <small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='gres-routers-$id' class='form-control' placeholder='192.168.1.1'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{domain-name-servers} <small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='gres-dns-$id' class='form-control' placeholder='8.8.8.8'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row' style='margin-bottom:8px'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{ntp-servers} <small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='gres-ntp-$id' class='form-control' placeholder='192.168.1.1'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{time-servers} <small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='gres-time-$id' class='form-control' placeholder='192.168.1.1'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row' style='margin-bottom:8px'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{next-server} <small class='text-muted'>PXE/TFTP</small></label>";
    $h[] = "      <input type='text' id='gres-nextserver-$id' class='form-control' placeholder='192.168.1.10'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>filename <small class='text-muted'>{pxe_file}</small></label>";
    $h[] = "      <input type='text' id='gres-filename-$id' class='form-control' placeholder='pxelinux.0'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row'>";
    $h[] = "    <div class='col-md-12'>";
    $h[] = "      <label>{rfc3442-classless-static-routes} <small class='text-muted'>CIDR gateway pairs, comma-separated</small></label>";
    $h[] = "      <input type='text' id='gres-rfc3442-$id' class='form-control' placeholder='192.168.10.0/24 10.0.0.1, 0.0.0.0/0 192.168.1.254'>";
    $h[] = "    </div>";
    $h[] = "  </div>";

    $h[] = "  </div></div>";

    $h[] = "<div class='text-right' style='margin-bottom:20px'>";
    $h[] = "  <button class='btn btn-success btn-lg' onclick='GResPush_$id()'>";
    $h[] = "    <i class='fas fa-broadcast-tower'></i> {push_to_all_agents}";
    $h[] = "  </button>";
    $h[] = "</div>";

    $h[] = gres_form_js_block($id, $page);

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── POST handler ──────────────────────────────────────────────────────────────

function gres_push(): void {
    $tpl = new template_admin();
    $id  = gid();

    $rid   = trim($_POST["res_id"]    ?? '');
    $rname = trim($_POST["res_name"]  ?? '');
    $rmac  = trim($_POST["res_mac"]   ?? '');
    $rip   = trim($_POST["res_ip"]    ?? '');
    $rscope= trim($_POST["res_scope"] ?? '');

    if ($rid === '') {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{reservation_id_required}"));
        return;
    }
    if (!preg_match('/^([0-9a-fA-F]{2}:){5}[0-9a-fA-F]{2}$/', $rmac)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{invalid_mac_address}"));
        return;
    }
    if ($rip === '') {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{fixed_ip_required}"));
        return;
    }

    $routers     = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $_POST["res_routers"]      ?? ''))));
    $dnss        = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $_POST["res_dns_servers"]  ?? ''))));
    $ntps        = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $_POST["res_ntp_servers"]  ?? ''))));
    $times       = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $_POST["res_time_servers"] ?? ''))));
    $next_server = trim($_POST["res_next_server"]   ?? '');
    $filename    = trim($_POST["res_filename"]      ?? '');
    $rfc3442_routes = array_values(array_filter(array_map('trim', preg_split('/[,\n]+/', $_POST["res_rfc3442_routes"] ?? ''))));

    $options = [];
    if (!empty($routers))        $options[] = ["name" => "routers",                          "type" => "ip-addresses",    "value" => implode(', ', $routers)];
    if (!empty($dnss))           $options[] = ["name" => "domain-name-servers",              "type" => "ip-addresses",    "value" => implode(', ', $dnss)];
    if (!empty($ntps))           $options[] = ["name" => "ntp-servers",                      "type" => "ip-addresses",    "value" => implode(', ', $ntps)];
    if (!empty($times))          $options[] = ["name" => "time-servers",                     "type" => "ip-addresses",    "value" => implode(', ', $times)];
    if ($next_server !== '')     $options[] = ["name" => "next-server",                      "type" => "ip-address",      "value" => $next_server];
    if ($filename !== '')        $options[] = ["name" => "filename",                         "type" => "text",            "value" => $filename];
    if (!empty($rfc3442_routes)) $options[] = ["name" => "rfc3442-classless-static-routes", "type" => "classless-routes","value" => $rfc3442_routes];

    $data = [
        "id"       => $rid,
        "name"     => $rname !== '' ? $rname : $rid,
        "mac"      => strtolower($rmac),
        "fixed_ip" => $rip,
    ];
    if ($rscope !== '')   $data["scope_id"] = $rscope;
    if (!empty($options)) $data["options"]  = $options;

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/dhcp/group/$id/reservations", $data
    ));

    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    if (isset($json->Status) && !$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->Error ?? "{error}")));
        return;
    }

    $total   = intval($json->total   ?? 0);
    $success = intval($json->success ?? 0);
    $failed  = intval($json->failed  ?? 0);
    $results = $json->results ?? [];

    $h   = [];
    $h[] = "<div style='margin-bottom:12px'>";
    $h[] = "  <span class='badge' style='background:#676a6c;color:#fff;font-size:13px;padding:5px 10px;margin-right:5px'>{total}: $total</span>";
    $h[] = "  <span class='badge' style='background:#1ab394;color:#fff;font-size:13px;padding:5px 10px;margin-right:5px'>{success}: $success</span>";
    if ($failed > 0) {
        $h[] = "  <span class='badge' style='background:#ed5565;color:#fff;font-size:13px;padding:5px 10px'>{failed}: $failed</span>";
    }
    $h[] = "</div>";

    if (!empty($results)) {
        $h[] = "<table class='table table-striped table-condensed'>";
        $h[] = "  <thead><tr style='background:#f5f5f5'>";
        $h[] = "    <th style='width:1%'></th><th>{hostname}</th><th>{status}</th>";
        $h[] = "  </tr></thead><tbody>";
        foreach ($results as $r) {
            if (!is_object($r)) continue;
            $hostname = htmlspecialchars($r->hostname ?? "Agent #{$r->agent_id}");
            $msg      = htmlspecialchars($r->message ?? '');
            if (!empty($r->success)) {
                $ico  = "<i class='fas fa-check-circle' style='color:#1ab394'></i>";
                $cell = "<span style='color:#1ab394'>{success}</span>";
            } else {
                $ico  = "<i class='fas fa-times-circle' style='color:#ed5565'></i>";
                $cell = "<span style='color:#ed5565'>{failed}</span>"
                      . ($msg !== '' ? " &mdash; <small>$msg</small>" : '');
            }
            $h[] = "    <tr><td>$ico</td><td><strong>$hostname</strong></td><td>$cell</td></tr>";
        }
        $h[] = "  </tbody></table>";
    }

    // Refresh the list after a successful push
    $h[] = "<script>GrLoad_$id(1);</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    admin_tracks("Group #$id: DHCP reservation '$rid' pushed to all agents ($success/$total success)");
}

// ── JavaScript blocks ─────────────────────────────────────────────────────────

function gres_list_js(int $id, string $page): string {
    $l = [];
    $l[] = "<script>";
    $l[] = "(function(){";
    $l[] = "var grPage_$id=1, grLimit_$id=100;";

    $l[] = "window.GrReset_$id=function(){";
    $l[] = "  \$('#gr-search-$id,#gr-ip-$id').val('');";
    $l[] = "  \$('#gr-agent-$id').val('');";
    $l[] = "  GrLoad_$id(1);";
    $l[] = "};";

    $l[] = "window.GrLoad_$id=function GrLoad_$id(pg){";
    $l[] = "  grPage_$id = pg || grPage_$id;";
    $l[] = "  var params = {";
    $l[] = "    id: $id, 'res-data': 1,";
    $l[] = "    page: grPage_$id, limit: grLimit_$id,";
    $l[] = "    search: \$.trim(\$('#gr-search-$id').val()),";
    $l[] = "    ip:     \$.trim(\$('#gr-ip-$id').val()),";
    $l[] = "    agent:  \$('#gr-agent-$id').val()";
    $l[] = "  };";
    $l[] = "  \$('#gr-table-$id').html('<div style=\"padding:20px;text-align:center\"><i class=\"fas fa-spinner fa-spin fa-2x\" style=\"color:#aaa\"></i></div>');";
    $l[] = "  \$.getJSON('$page', params, function(d){";
    $l[] = "    if(d.error){ \$('#gr-table-$id').html('<div class=\"alert alert-danger\">'+d.error+'</div>'); return; }";

    // Populate agent dropdown once
    $l[] = "    if(\$('#gr-agent-$id option').length<=1 && d.agents && d.agents.length){";
    $l[] = "      \$.each(d.agents,function(i,a){ \$('#gr-agent-$id').append(\$('<option>').val(a).text(a)); });";
    $l[] = "    }";

    // Summary badge
    $l[] = "    \$('#gr-summary-$id').html('<span class=\"badge\" style=\"background:#676a6c;color:#fff;font-size:12px;padding:4px 8px\">{total}: '+d.total+'</span>');";

    // Table
    $l[] = "    if(!d.rows_html||!d.total){";
    $l[] = "      \$('#gr-table-$id').html('<div class=\"alert alert-info\">{no_reservations}</div>');";
    $l[] = "      \$('#gr-pager-$id').html(''); return;";
    $l[] = "    }";
    $l[] = "    var tbl='<table class=\"table table-striped table-condensed\" style=\"margin:0\">';";
    $l[] = "    tbl+='<thead><tr style=\"background:#f5f5f5\">';";
    $l[] = "    tbl+='<th>{agent}</th><th>ID / {description}</th><th>MAC</th><th>IP</th><th>{scope_id}</th>';";
    $l[] = "    tbl+='</tr></thead><tbody>'+d.rows_html+'</tbody></table>';";
    $l[] = "    \$('#gr-table-$id').html(tbl);";

    // Pagination
    $l[] = "    var p='';";
    $l[] = "    if(d.pages>1){";
    $l[] = "      p+='<ul class=\"pagination\" style=\"margin:0\">';";
    $l[] = "      p+='<li class=\"'+(d.page<=1?'disabled':'')+'\"><a href=\"#\" onclick=\"GrLoad_$id('+(d.page-1)+');return false\">&#8249;</a></li>';";
    $l[] = "      var from=Math.max(1,d.page-3),to=Math.min(d.pages,d.page+3);";
    $l[] = "      if(from>1) p+='<li><a href=\"#\" onclick=\"GrLoad_$id(1);return false\">1</a></li><li class=\"disabled\"><a>…</a></li>';";
    $l[] = "      for(var i=from;i<=to;i++) p+='<li class=\"'+(i===d.page?'active':'')+'\"><a href=\"#\" onclick=\"GrLoad_$id('+i+');return false\">'+i+'</a></li>';";
    $l[] = "      if(to<d.pages) p+='<li class=\"disabled\"><a>…</a></li><li><a href=\"#\" onclick=\"GrLoad_$id('+d.pages+');return false\">'+d.pages+'</a></li>';";
    $l[] = "      p+='<li class=\"'+(d.page>=d.pages?'disabled':'')+'\"><a href=\"#\" onclick=\"GrLoad_$id('+(d.page+1)+');return false\">&#8250;</a></li>';";
    $l[] = "      p+='</ul>';";
    $l[] = "      p+='<small class=\"text-muted\" style=\"display:block;margin-top:4px\">{page} '+d.page+' / '+d.pages+' &mdash; '+d.total+' {reservations}</small>';";
    $l[] = "    }";
    $l[] = "    \$('#gr-pager-$id').html(p);";
    $l[] = "  }).fail(function(){ \$('#gr-table-$id').html('<div class=\"alert alert-danger\">{error}</div>'); });";
    $l[] = "};";

    // Enter key on text inputs triggers search
    $l[] = "\$('#gr-search-$id,#gr-ip-$id').on('keydown',function(e){ if(e.keyCode===13) GrLoad_$id(1); });";

    // Initial load
    $l[] = "GrLoad_$id(1);";
    $l[] = "})();";
    $l[] = "</script>";
    return implode("\n", $l);
}

function gres_form_js_block(int $id, string $page): string {
    $macRegex = "^([0-9a-fA-F]{2}:){5}[0-9a-fA-F]{2}$";
    $l   = [];
    $l[] = "<script>";
    $l[] = "function GResPush_$id(){";
    $l[] = "  var rid=\$.trim(\$('#gres-id-$id').val());";
    $l[] = "  var mac=\$.trim(\$('#gres-mac-$id').val());";
    $l[] = "  var ip=\$.trim(\$('#gres-ip-$id').val());";
    $l[] = "  var macRe=/$macRegex/;";
    $l[] = "  if(!rid){ alert('{reservation_id_required}'); return; }";
    $l[] = "  if(!macRe.test(mac)){ alert('{invalid_mac_address}'); return; }";
    $l[] = "  if(!ip){ alert('{fixed_ip_required}'); return; }";
    $l[] = "  \$('#gres-form-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {pushing}...</div>');";
    $l[] = "  \$.post('$page',{";
    $l[] = "    'push-res':1, id:$id,";
    $l[] = "    res_id:rid,";
    $l[] = "    res_name:\$.trim(\$('#gres-name-$id').val()),";
    $l[] = "    res_mac:mac,";
    $l[] = "    res_ip:ip,";
    $l[] = "    res_scope:\$.trim(\$('#gres-scope-$id').val()),";
    $l[] = "    res_routers:\$.trim(\$('#gres-routers-$id').val()),";
    $l[] = "    res_dns_servers:\$.trim(\$('#gres-dns-$id').val()),";
    $l[] = "    res_ntp_servers:\$.trim(\$('#gres-ntp-$id').val()),";
    $l[] = "    res_time_servers:\$.trim(\$('#gres-time-$id').val()),";
    $l[] = "    res_next_server:\$.trim(\$('#gres-nextserver-$id').val()),";
    $l[] = "    res_filename:\$.trim(\$('#gres-filename-$id').val()),";
    $l[] = "    res_rfc3442_routes:\$.trim(\$('#gres-rfc3442-$id').val())";
    $l[] = "  },function(r){";
    $l[] = "    \$('#gres-result-$id').html(r);";
    $l[] = "    dialogInstance2.close();";
    $l[] = "  });";
    $l[] = "}";
    $l[] = "</script>";
    return implode("\n", $l);
}
