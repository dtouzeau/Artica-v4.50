<?php
/**
 * System Internet Proxy configuration pushed to every enabled agent in a group.
 * Hostname is NOT changed.
 *
 * Entry:  Loadjs('fw.netagents.group.proxy.php?proxy-js={group_id}')    — opens dialog
 *         LoadAjax('div','fw.netagents.group.proxy.php?id={group_id}')  — inline form
 * API:    POST   /netagents/proxy-config/group/{id}
 *         DELETE /netagents/proxy-config/group/{id}
 *         Body:  { proxy_address, proxy_port, username, password }
 *         Response: {group_id, total, success, failed, results:[{agent_id,hostname,success,message}]}
 *
 * Pre-fill: reads proxy config from the first agent in the group via
 *           GET /netagents/proxy-config/{first_agent_id}
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }

if (isset($_GET["proxy-js"])) { gproxy_js();     exit; }
if (isset($_POST["save"]))    { gproxy_save();   exit; }
if (isset($_POST["delete"]))  { gproxy_delete(); exit; }
gproxy_form();

// ── Helpers ───────────────────────────────────────────────────────────────────

function gid(): int {
    return intval($_GET["id"] ?? $_POST["id"] ?? 0);
}

// ── Dispatcher actions ────────────────────────────────────────────────────────

/** Opens an 800px dialog containing the group proxy config form. */
function gproxy_js(): bool {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["proxy-js"]);

    $grp       = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    $groupName = (is_object($grp) && !empty($grp->name)) ? htmlspecialchars($grp->name) : "Group #$id";
    return $tpl->js_dialog4("{system_proxy_settings} — $groupName", "$page?id=$id", 800);
}

/** Renders the group proxy form with group info header; pre-fills from first agent. */
function gproxy_form(): bool {
    $tpl  = new template_admin();
    $id   = gid();
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

    // ── Pre-fill from first agent in the group ──
    $address = $port = $user = $pass = '';
    $refHostname = '';
    $hasProxy    = false;
    $agents = $grp->agents ?? [];
    if (!empty($agents) && is_object($agents[0])) {
        $firstId     = intval($agents[0]->id ?? 0);
        $refHostname = htmlspecialchars($agents[0]->hostname ?? "Agent #$firstId");
        if ($firstId > 0) {
            $px = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/proxy-config/$firstId"));
            if (is_object($px) && !empty($px->proxy_address)) {
                $address  = htmlspecialchars($px->proxy_address ?? '');
                $port     = intval($px->proxy_port ?? 0) ?: '';
                $user     = htmlspecialchars($px->username ?? '');
                $pass     = htmlspecialchars($px->password ?? '');
                $hasProxy = true;
            }
        }
    }

    $h   = [];

    // ── Group info banner ──
    $h[] = "<div class='alert alert-info' style='margin-bottom:15px;margin-top:10px'>";
    $h[] = "  <i class='fas fa-layer-group'></i> <strong>$groupName</strong>";
    $h[] = "  &nbsp;<span class='badge' style='background:#1c84c6;color:#fff'>$agentCount</span> {agents}";
    $h[] = "  &nbsp;&mdash; {group_proxy_explain}";
    if ($refHostname !== '') {
        $h[] = "  <br><small class='text-muted' style='margin-top:4px;display:inline-block'>"
             . "<i class='fas fa-info-circle'></i> {from}: <strong>$refHostname</strong></small>";
    }
    $h[] = "</div>";

    if (!$hasProxy) {
        $h[] = "<div class='alert alert-warning'>";
        $h[] = "  <i class='fas fa-info-circle'></i> {no_proxy_configured_on_reference_agent}";
        $h[] = "</div>";
    }

    $h[] = "<div id='gproxy-result-$id'></div>";
    $h[] = gproxy_address_box($id, $address, $port);
    $h[] = gproxy_auth_box($id, $user, $pass);

    // Action buttons row
    $h[] = "<div class='row' style='margin-bottom:20px'>";
    $h[] = "  <div class='col-md-6'>";
    if ($hasProxy) {
        $h[] = "    <button class='btn btn-danger' onclick='GproxyDelete_$id()'>";
        $h[] = "      <i class='fas fa-trash-alt'></i> {remove_proxy}";
        $h[] = "    </button>";
    }
    $h[] = "  </div>";
    $h[] = "  <div class='col-md-6 text-right'>";
    $h[] = "    <button class='btn btn-primary btn-lg' onclick='GproxySave_$id()'>";
    $h[] = "      <i class='fas fa-save'></i> {save}";
    $h[] = "    </button>";
    $h[] = "  </div>";
    $h[] = "</div>";
    $h[] = gproxy_js_block($id, $page, $hasProxy);

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    return true;
}

/** Pushes proxy config to all agents in the group. Returns an HTML result snippet. */
function gproxy_save(): void {
    $tpl = new template_admin();
    $id  = gid();

    $address = trim($_POST["proxy_address"] ?? '');
    $port    = intval($_POST["proxy_port"]   ?? 0);
    $user    = trim($_POST["username"] ?? '');
    $pass    = trim($_POST["password"] ?? '');

    if ($address === '') {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{proxy_address_required}"));
        return;
    }
    if ($port < 1 || $port > 65535) {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{invalid_port_range}"));
        return;
    }

    $data = [
        "proxy_address" => $address,
        "proxy_port"    => $port,
        "username"      => $user,
        "password"      => $pass,
    ];

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/proxy-config/group/$id", $data
    ));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    if (isset($json->Status) && !$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->Error ?? "{error}")));
        return;
    }

    echo $tpl->_ENGINE_parse_body(gproxy_results_html($json));
    admin_tracks("Group #$id: system proxy pushed to {$json->total} agents ({$json->success} success, {$json->failed} failed)");
}

/** Sends DELETE to remove proxy config from all agents in the group. Returns an HTML snippet. */
function gproxy_delete(): void {
    $tpl = new template_admin();
    $id  = gid();

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE(
        "/netagents/proxy-config/group/$id"
    ));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    if (isset($json->Status) && !$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->Error ?? "{error}")));
        return;
    }

    echo $tpl->_ENGINE_parse_body(gproxy_results_html($json));
    admin_tracks("Group #$id: system proxy removed from {$json->total} agents ({$json->success} success, {$json->failed} failed)");
}

/** Renders the per-agent results table shared by save and delete responses. */
function gproxy_results_html(object $json): string {
    $total   = intval($json->total   ?? 0);
    $success = intval($json->success ?? 0);
    $failed  = intval($json->failed  ?? 0);
    $results = $json->results ?? [];

    $h   = [];

    // ── Summary badges ──
    $h[] = "<div style='margin-bottom:12px'>";
    $h[] = "  <span class='badge' style='background:#676a6c;color:#fff;font-size:13px;padding:5px 10px;margin-right:5px'>{total}: $total</span>";
    $h[] = "  <span class='badge' style='background:#1ab394;color:#fff;font-size:13px;padding:5px 10px;margin-right:5px'>{success}: $success</span>";
    if ($failed > 0) {
        $h[] = "  <span class='badge' style='background:#ed5565;color:#fff;font-size:13px;padding:5px 10px'>{failed}: $failed</span>";
    }
    $h[] = "</div>";

    // ── Per-agent results table ──
    if (!empty($results)) {
        $h[] = "<table class='table table-striped table-condensed'>";
        $h[] = "  <thead><tr style='background:#f5f5f5'>";
        $h[] = "    <th style='width:1%'></th>";
        $h[] = "    <th>{hostname}</th>";
        $h[] = "    <th>{status}</th>";
        $h[] = "  </tr></thead><tbody>";
        foreach ($results as $r) {
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

    return implode("\n", $h);
}

// ── UI box builders ───────────────────────────────────────────────────────────

function gproxy_address_box(int $id, string $address = '', $port = ''): string {
    $h   = [];
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-network-wired'></i>&nbsp; {proxy_server}</h5></div>";
    $h[] = "  <div class='ibox-content'><div class='row'>";
    $h[] = "    <div class='col-md-8'>";
    $h[] = "      <label>{proxy_address}</label>";
    $h[] = "      <input type='text' id='gproxy-addr-$id' class='form-control' value='$address' placeholder='192.168.1.100'>";
    $h[] = "      <p class='help-block'>{proxy_address_explain}</p>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>{proxy_port}</label>";
    $h[] = "      <input type='number' id='gproxy-port-$id' class='form-control' value='$port' min='1' max='65535' placeholder='3128'>";
    $h[] = "    </div>";
    $h[] = "  </div></div></div>";
    return implode("\n", $h);
}

function gproxy_auth_box(int $id, string $user = '', string $pass = ''): string {
    $h   = [];
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-key'></i>&nbsp; {authentication} <small class='text-muted'>&nbsp;{optional}</small></h5></div>";
    $h[] = "  <div class='ibox-content'><div class='row'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{username}</label>";
    $h[] = "      <input type='text' id='gproxy-user-$id' class='form-control' value='$user' placeholder='{username}'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{password}</label>";
    $h[] = "      <div class='input-group'>";
    $h[] = "        <input type='password' id='gproxy-pass-$id' class='form-control' value='$pass' placeholder='{password}'>";
    $h[] = "        <span class='input-group-btn'>";
    $h[] = "          <button type='button' class='btn btn-default'";
    $h[] = "            onclick=\"var e=document.getElementById('gproxy-pass-$id');e.type=e.type==='password'?'text':'password'\">";
    $h[] = "            <i class='fas fa-eye'></i>";
    $h[] = "          </button>";
    $h[] = "        </span>";
    $h[] = "      </div>";
    $h[] = "    </div>";
    $h[] = "  </div></div></div>";
    return implode("\n", $h);
}

/** Emits GproxySave_{id}() and (when $hasProxy) GproxyDelete_{id}() JavaScript functions. */
function gproxy_js_block(int $id, string $page, bool $hasProxy): string {
    $l   = [];
    $l[] = "<script>";

    $l[] = "function GproxySave_$id(){";
    $l[] = "  var addr=\$.trim(\$('#gproxy-addr-$id').val());";
    $l[] = "  var port=parseInt(\$('#gproxy-port-$id').val(),10);";
    $l[] = "  if(!addr){ alert('{proxy_address_required}'); return; }";
    $l[] = "  if(isNaN(port)||port<1||port>65535){ alert('{invalid_port_range}'); return; }";
    $l[] = "  \$('#gproxy-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {saving}...</div>');";
    $l[] = "  \$.post('$page',{";
    $l[] = "    save:1, id:$id,";
    $l[] = "    proxy_address:addr,";
    $l[] = "    proxy_port:port,";
    $l[] = "    username:\$.trim(\$('#gproxy-user-$id').val()),";
    $l[] = "    password:\$('#gproxy-pass-$id').val()";
    $l[] = "  },function(r){ \$('#gproxy-result-$id').html(r); });";
    $l[] = "}";

    if ($hasProxy) {
        $l[] = "function GproxyDelete_$id(){";
        $l[] = "  swal({";
        $l[] = "    title:'{remove_proxy}',";
        $l[] = "    text:'{confirm_remove_proxy_group}',";
        $l[] = "    type:'warning',";
        $l[] = "    showCancelButton:true,";
        $l[] = "    confirmButtonColor:'#ed5565',";
        $l[] = "    confirmButtonText:'{remove}',";
        $l[] = "    cancelButtonText:'{cancel}'";
        $l[] = "  },function(isConfirm){";
        $l[] = "    if(!isConfirm) return;";
        $l[] = "    \$('#gproxy-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {removing}...</div>');";
        $l[] = "    \$.post('$page',{delete:1,id:$id},function(r){ \$('#gproxy-result-$id').html(r); });";
        $l[] = "  });";
        $l[] = "}";
    }

    $l[] = "</script>";
    return implode("\n", $l);
}
