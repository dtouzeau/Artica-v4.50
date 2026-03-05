<?php
/**
 * Push a static host reservation to all agents in a group.
 * Each submit calls the group endpoint and shows a per-agent results table.
 *
 * Entry:  LoadAjax('div','fw.netagents.group.dhcp.reservations.php?id={group_id}')
 *         Loadjs('fw.netagents.group.dhcp.reservations.php?res-add-js={group_id}') — dialog
 * API:    POST /netagents/dhcp/group/{id}/reservations
 * Response: {group_id, total, success, failed, results:[{agent_id,hostname,success,message}]}
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }

if (isset($_GET["res-add-js"])) { gres_add_js(); exit; }
if (isset($_GET["res-form"]))   { gres_form();   exit; }
if (isset($_POST["push-res"]))  { gres_push();   exit; }
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

    $groupName  = htmlspecialchars($grp->name ?? "Group #$id");
    $agentCount = intval($grp->agent_count ?? 0);

    $h   = [];
    $h[] = "<div id='gres-result-$id' style='margin-bottom:10px'></div>";

    $h[] = "<div class='alert alert-info' style='margin-bottom:15px'>";
    $h[] = "  <i class='fas fa-layer-group'></i> <strong>$groupName</strong>";
    $h[] = "  &nbsp;<span class='badge' style='background:#1c84c6;color:#fff'>$agentCount</span> {agents}";
    $h[] = "  <br><small class='text-muted'>{group_reservation_push_explain}</small>";
    $h[] = "</div>";

    $h[] = "<div style='margin-bottom:20px'>";
    $h[] = "  <a href='#' onclick=\"Loadjs('$page?res-add-js=$id');return false;\" class='btn btn-primary'>";
    $h[] = "    <i class='fas fa-bookmark'></i> {push_reservation_to_group}";
    $h[] = "  </a>";
    $h[] = "</div>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Dialog opener ─────────────────────────────────────────────────────────────

function gres_add_js(): bool {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["res-add-js"]);
    // Support pre-fill from lease: ?from_ip=... &from_mac=...
    $extra = '';
    if (!empty($_GET["from_ip"]))  $extra .= "&from_ip="  . urlencode($_GET["from_ip"]);
    if (!empty($_GET["from_mac"])) $extra .= "&from_mac=" . urlencode($_GET["from_mac"]);
    return $tpl->js_dialog2("{push_reservation_to_group}", "$page?id=$id&res-form=1$extra", 700);
}

// ── Form render ───────────────────────────────────────────────────────────────

function gres_form(): void {
    $tpl  = new template_admin();
    $id   = gid();
    $page = CurrentPageName();

    // Pre-fill from URL params (assign from lease shortcut)
    $rid   = '';
    $rname = '';
    $rmac  = htmlspecialchars(trim($_GET["from_mac"] ?? ''));
    $rip   = htmlspecialchars(trim($_GET["from_ip"]  ?? ''));
    $scope = '';

    $h   = [];
    $h[] = "<div id='gres-form-result-$id'></div>";

    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-bookmark'></i>&nbsp; {reservation_details}</h5></div>";
    $h[] = "  <div class='ibox-content'>";

    // ID + Name row
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

    // MAC + IP row
    $h[] = "  <div class='row' style='margin-bottom:15px'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>MAC</label>";
    $h[] = "      <input type='text' id='gres-mac-$id' class='form-control' value='$rmac' placeholder='aa:bb:cc:dd:ee:ff'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{fixed_ip}</label>";
    $h[] = "      <input type='text' id='gres-ip-$id' class='form-control' value='$rip' placeholder='192.168.1.50'>";
    $h[] = "    </div>";
    $h[] = "  </div>";

    // Scope ID (text field — agents may have different scope IDs)
    $h[] = "  <div class='row' style='margin-bottom:15px'>";
    $h[] = "    <div class='col-md-8'>";
    $h[] = "      <label>{scope_id} <small class='text-muted'>{optional}</small></label>";
    $h[] = "      <input type='text' id='gres-scope-$id' class='form-control' value='$scope' placeholder='eth0-192.168.1.0/24'>";
    $h[] = "      <p class='help-block'>{group_scope_id_must_exist_on_each_agent}</p>";
    $h[] = "    </div>";
    $h[] = "  </div>";

    // ── Per-host DHCP Options ──
    $h[] = "  <div class='row' style='margin-bottom:8px'>";
    $h[] = "    <div class='col-md-12'><label><i class='fas fa-sliders-h'></i>&nbsp; {dhcp_options} <small class='text-muted'>({optional})</small></label></div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row' style='margin-bottom:8px'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>option routers <small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='gres-routers-$id' class='form-control' placeholder='192.168.1.1'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>option domain-name-servers <small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='gres-dns-$id' class='form-control' placeholder='8.8.8.8'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row' style='margin-bottom:8px'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>option ntp-servers <small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='gres-ntp-$id' class='form-control' placeholder='192.168.1.1'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>option time-servers <small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='gres-time-$id' class='form-control' placeholder='192.168.1.1'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row' style='margin-bottom:8px'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>next-server <small class='text-muted'>PXE/TFTP</small></label>";
    $h[] = "      <input type='text' id='gres-nextserver-$id' class='form-control' placeholder='192.168.1.10'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>filename <small class='text-muted'>PXE boot file</small></label>";
    $h[] = "      <input type='text' id='gres-filename-$id' class='form-control' placeholder='pxelinux.0'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row'>";
    $h[] = "    <div class='col-md-12'>";
    $h[] = "      <label>option rfc3442-classless-static-routes <small class='text-muted'>CIDR gateway pairs, comma-separated</small></label>";
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
    if (!empty($routers))        $options[] = ["name" => "routers",                           "type" => "ip-addresses", "value" => implode(', ', $routers)];
    if (!empty($dnss))           $options[] = ["name" => "domain-name-servers",               "type" => "ip-addresses", "value" => implode(', ', $dnss)];
    if (!empty($ntps))           $options[] = ["name" => "ntp-servers",                       "type" => "ip-addresses", "value" => implode(', ', $ntps)];
    if (!empty($times))          $options[] = ["name" => "time-servers",                      "type" => "ip-addresses", "value" => implode(', ', $times)];
    if ($next_server !== '')     $options[] = ["name" => "next-server",                       "type" => "ip-address",   "value" => $next_server];
    if ($filename !== '')        $options[] = ["name" => "filename",                          "type" => "text",         "value" => $filename];
    if (!empty($rfc3442_routes)) $options[] = ["name" => "rfc3442-classless-static-routes",  "type" => "classless-routes", "value" => $rfc3442_routes];

    $data = [
        "id"       => $rid,
        "name"     => $rname !== '' ? $rname : $rid,
        "mac"      => strtolower($rmac),
        "fixed_ip" => $rip,
    ];
    if ($rscope !== '')     $data["scope_id"] = $rscope;
    if (!empty($options))   $data["options"]  = $options;

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
        $h[] = "    <th style='width:1%'></th>";
        $h[] = "    <th>{hostname}</th>";
        $h[] = "    <th>{status}</th>";
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

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    admin_tracks("Group #$id: DHCP reservation '$rid' pushed to all agents ($success/$total success)");
}

// ── JavaScript block ──────────────────────────────────────────────────────────

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
