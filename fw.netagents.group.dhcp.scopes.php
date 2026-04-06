<?php
/**
 * Push DHCP scope or Option-82 relay definitions to all agents in a group.
 * Each submit calls the group endpoint and shows a per-agent results table.
 *
 * Entry:  LoadAjax('div','fw.netagents.group.dhcp.scopes.php?id={group_id}')
 *         Loadjs('fw.netagents.group.dhcp.scopes.php?scope-add-js={group_id}') — scope dialog
 *         Loadjs('fw.netagents.group.dhcp.scopes.php?relay-add-js={group_id}') — relay dialog
 * API:    POST /netagents/dhcp/group/{id}/scopes   — push scope to all agents
 *         POST /netagents/dhcp/group/{id}/relays   — push relay to all agents
 * Response: {group_id, total, success, failed, results:[{agent_id,hostname,success,message}]}
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }

if (isset($_GET["scope-add-js"])) { gscope_add_js(); exit; }
if (isset($_GET["relay-add-js"])) { grelay_add_js(); exit; }
if (isset($_GET["scope-form"]))   { gscope_form();   exit; }
if (isset($_GET["relay-form"]))   { grelay_form();   exit; }
if (isset($_POST["push-scope"]))  { gscope_push();   exit; }
if (isset($_POST["push-relay"]))  { grelay_push();   exit; }
gscopes_page();

// ── Helpers ───────────────────────────────────────────────────────────────────

function gid(): int {
    return intval($_GET["id"] ?? $_POST["id"]
        ?? $_GET["scope-add-js"] ?? $_GET["relay-add-js"] ?? 0);
}

// ── Main page ─────────────────────────────────────────────────────────────────

function gscopes_page(): void {
    $tpl  = new template_admin();
    $id   = gid();
    $page = CurrentPageName();


    $h   = [];
    $h[] = "<div id='gscopes-result-$id' style='margin-bottom:10px'></div>";

    $h[] = "<div style='margin-bottom:15px'>";
    $h[] = "<small class='text-muted'>{group_scope_push_explain}</small>";
    $h[] = "</div>";

    $h[] = "<div style='margin-bottom:20px'>";
    $h[] = "  <a href='#' onclick=\"Loadjs('$page?scope-add-js=$id');return false;\" class='btn btn-primary'>";
    $h[] = "    <i class='fas fa-network-wired'></i> {push_scope_to_group}";
    $h[] = "  </a>";
    $h[] = "  &nbsp;";
    $h[] = "  <a href='#' onclick=\"Loadjs('$page?relay-add-js=$id');return false;\" class='btn btn-default'>";
    $h[] = "    <i class='fas fa-route'></i> {push_relay_to_group}";
    $h[] = "  </a>";
    $h[] = "</div>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Dialog openers ────────────────────────────────────────────────────────────

function gscope_add_js(): bool {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["scope-add-js"]);
    return $tpl->js_dialog2("{push_scope_to_group}", "$page?id=$id&scope-form=1", 820);
}

function grelay_add_js(): bool {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["relay-add-js"]);
    return $tpl->js_dialog1("{push_relay_to_group}", "$page?id=$id&relay-form=1", 600);
}

// ── Form renders ──────────────────────────────────────────────────────────────

/** Scope push form — mirrors single-agent scope form without edit mode. */
function gscope_form(): void {
    $tpl  = new template_admin();
    $id   = gid();
    $page = CurrentPageName();

    $h   = [];
    $h[] = "<div id='gscope-form-result-$id'></div>";

    // ── Identity & Type ──
    $srid="gprp-$id-".time();

    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-id-card'></i>&nbsp; {scope_identity}</h5></div>";
    $h[] = "  <div class='ibox-content'><div class='row'>";
    $h[] = "      <input type='hidden' id='gscope-id-$id' value='$srid'>";
    $h[] = "    <div class='col-md-3'>";
    $h[] = "      <label>{type}</label>";
    $h[] = "      <select id='gscope-type-$id' class='form-control' onchange='GScopeTypeChange_$id()'>";
    $h[] = "        <option value='direct'>direct</option>";
    $h[] = "        <option value='relay'>relay</option>";
    $h[] = "      </select>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-2'>";
    $h[] = "      <label>{interface}</label>";
    $h[] = "      <input type='text' id='gscope-iface-$id' class='form-control' placeholder='ens192'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-1'>";
    $h[] = "      <label>VLAN ID <small class='text-muted'>({optional})</small></label>";
    $h[] = "      <input type='number' id='gscope-vlan-id-$id' class='form-control' min='0' max='4094' placeholder='0' value='0'>";
    $h[] = "    </div>";
    $h[] = "  </div></div></div>";

    // ── Relay ID (hidden by default) ──
    $h[] = "<div id='gscope-relay-row-$id' class='ibox' style='display:none'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-route'></i>&nbsp; {relay_class_id}</h5></div>";
    $h[] = "  <div class='ibox-content'>";
    $h[] = "    <input type='number' id='gscope-relay-id-$id' class='form-control' style='max-width:200px' min='1' placeholder='1'>";
    $h[] = "    <p class='help-block'>{relay_id_must_exist_on_each_agent}</p>";
    $h[] = "  </div></div>";

    // ── Network ──
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-network-wired'></i>&nbsp; {network}</h5></div>";
    $h[] = "  <div class='ibox-content'><div class='row'>";
    $h[] = "    <div class='col-md-5'>";
    $h[] = "      <label>{subnet}</label>";
    $h[] = "      <input type='text' id='gscope-subnet-$id' class='form-control' placeholder='192.168.1.0'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-5'>";
    $h[] = "      <label>{netmask}</label>";
    $h[] = "      <input type='text' id='gscope-netmask-$id' class='form-control' placeholder='255.255.255.0'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-2'>";
    $h[] = "      <label>&nbsp;</label><br>";
    $h[] = "      <div class='checkbox'><label><input type='checkbox' id='gscope-auth-$id' checked> authoritative</label></div>";
    $h[] = "    </div>";
    $h[] = "  </div></div></div>";

    // ── Lease times ──
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-clock'></i>&nbsp; {lease_times}</h5></div>";
    $h[] = "  <div class='ibox-content'><div class='row'>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>{default_lease} <small class='text-muted'>(s)</small></label>";
    $h[] = "      <input type='number' id='gscope-ldef-$id' class='form-control' value='28800' min='60'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>{max_lease} <small class='text-muted'>(s)</small></label>";
    $h[] = "      <input type='number' id='gscope-lmax-$id' class='form-control' value='28800' min='60'>";
    $h[] = "    </div>";
    $h[] = "  </div></div></div>";

    // ── DHCP Options ──
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-sliders-h'></i>&nbsp; {dhcp_options}</h5></div>";
    $h[] = "  <div class='ibox-content'>";
    $h[] = "  <div class='row' style='margin-bottom:10px'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{routers} <small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='gscope-routers-$id' class='form-control' placeholder='192.168.1.1'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{dns_servers} <small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='gscope-dns-$id' class='form-control' placeholder='8.8.8.8, 8.8.4.4'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row' style='margin-bottom:10px'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>option ntp-servers <small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='gscope-ntp-$id' class='form-control' placeholder='192.168.1.1'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{time-servers} <small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='gscope-time-$id' class='form-control' placeholder='192.168.1.1'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row' style='margin-bottom:10px'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{next-server} <small class='text-muted'>PXE/TFTP</small></label>";
    $h[] = "      <input type='text' id='gscope-nextserver-$id' class='form-control' placeholder='192.168.1.10'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{filename} <small class='text-muted'>{pxe_file}</small></label>";
    $h[] = "      <input type='text' id='gscope-filename-$id' class='form-control' placeholder='pxelinux.0'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row'>";
    $h[] = "    <div class='col-md-12'>";
    $h[] = "      <label>{rfc3442-classless-static-routes} <small class='text-muted'>CIDR gateway pairs, comma-separated</small></label>";
    $h[] = "      <input type='text' id='gscope-rfc3442-$id' class='form-control' placeholder='192.168.10.0/24 10.0.0.1, 0.0.0.0/0 192.168.1.254'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  </div></div>";

    // ── Behavior Flags ──
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-toggle-on'></i>&nbsp; {behavior_flags}</h5></div>";
    $h[] = "  <div class='ibox-content'>";
    $h[] = "  <div class='row' style='margin-bottom:10px'>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>{deny_unkown_clients}</label>";
    $h[] = "      <select id='gscope-allow-unknown-$id' class='form-control'>";
    $h[] = "        <option value=''>{default}</option>";
    $h[] = "        <option value='allow'>allow</option>";
    $h[] = "        <option value='deny'>deny</option>";
    $h[] = "      </select>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-4'>";
    list($tooltip,$none)=$tpl->Tooltips("gscope-ddns-domain-expl-$id","{ddns-domainname-explain}");
    $h[] = "      <label id='gscope-ddns-domain-expl-$id' $tooltip>{ddns-domainname}</label>";
    $h[] = "      <input type='text' id='gscope-ddns-domain-$id' class='form-control' placeholder='example.com'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>{broadcast}</label>";
    $h[] = "      <input type='text' id='gscope-broadcast-$id' class='form-control' placeholder='192.168.1.255'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row'>";
    $h[] = "    <div class='col-md-3'>";
    list($tooltip,$nine)=$tpl->Tooltips("gscope-ping-check-$id-expl","{DHCPPing_check_explain}");

    $h[] = "      <div class='checkbox'><label><input type='checkbox' id='gscope-ping-check-$id'> <span id='gscope-ping-check-$id-expl' $tooltip>{DHCPPing_check}</span></label></div>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-3' style='padding-top:25px'>";
    $h[] = "      <div class='checkbox'><label><input type='checkbox' id='gscope-always-broadcast-$id'> {AllwaysBrodcast}</label></div>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-3' style='padding-top:25px'>";
    $h[] = "      <div class='checkbox'><label><input type='checkbox' id='gscope-get-lease-hostnames-$id'> {get_lease_hostnames}</label></div>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  </div></div>";

    // ── Pools ──
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'>";
    $h[] = "    <h5><i class='fas fa-layer-group'></i>&nbsp; {pools}</h5>";
    $h[] = "    <div class='ibox-tools'>";
    $h[] = "      <a href='#' onclick='GAddPool_$id();return false;' class='btn btn-xs btn-default'>";
    $h[] = "        <i class='fas fa-plus'></i> {add_pool}";
    $h[] = "      </a>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='ibox-content'>";
    $h[] = "    <div id='gscope-pools-$id'>";
    $h[] = "      <div class='row gscope-pool-row' style='margin-bottom:8px'>";
    $h[] = "        <div class='col-md-5'><input type='text' class='form-control pool-start' placeholder='192.168.1.100'></div>";
    $h[] = "        <div class='col-md-5'><input type='text' class='form-control pool-end' placeholder='192.168.1.254'></div>";
    $h[] = "        <div class='col-md-2'><button type='button' class='btn btn-danger btn-sm' onclick=\"\$(this).closest('.gscope-pool-row').remove()\"><i class='fas fa-times'></i></button></div>";
    $h[] = "      </div>";
    $h[] = "    </div>";
    $h[] = "    <p class='help-block'>{pool_explain}</p>";
    $h[] = "  </div></div>";

    $h[] = "<div class='text-right' style='margin-bottom:20px'>";
    $h[] = "  <button class='btn btn-success btn-lg' onclick='GScopePush_$id()'>";
    $h[] = "    <i class='fas fa-broadcast-tower'></i> {push_to_all_agents}";
    $h[] = "  </button>";
    $h[] = "</div>";

    $h[] = gscope_form_js_block($id, $page);

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

/** Relay push form. */
function grelay_form(): void {
    $tpl  = new template_admin();
    $id   = gid();
    $page = CurrentPageName();

    $h   = [];
    $h[] = "<div id='grelay-form-result-$id'></div>";
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-route'></i>&nbsp; {relay_class}</h5></div>";
    $h[] = "  <div class='ibox-content'><div class='row'>";
    $h[] = "    <div class='col-md-2'>";
    $h[] = "      <label>ID</label>";
    $h[] = "      <input type='number' id='grelay-id-$id' class='form-control' min='1' placeholder='1'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>{class_name} <span class='text-danger'>*</span></label>";
    $h[] = "      <input type='text' id='grelay-classname-$id' class='form-control' placeholder='relay-branch-1'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-3'>";
    $h[] = "      <label>{description} <small class='text-muted'>({optional})</small></label>";
    $h[] = "      <input type='text' id='grelay-desc-$id' class='form-control' placeholder='{relay_description}'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row' style='margin-top:10px'>";
    $h[] = "    <div class='col-md-5'>";
    $h[] = "      <label>{circuit_id} <small class='text-muted'>Option-82</small></label>";
    $h[] = "      <input type='text' id='grelay-circuit-id-$id' class='form-control' placeholder='0006aabbccdd'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-5'>";
    $h[] = "      <label>{remote_id} <small class='text-muted'>Option-82</small></label>";
    $h[] = "      <input type='text' id='grelay-remote-id-$id' class='form-control' placeholder='branch-switch-1'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-2' style='padding-top:25px'>";
    $h[] = "      <small class='text-muted'>{at_least_one_match_required}</small>";
    $h[] = "    </div>";
    $h[] = "  </div></div></div>";
    $h[] = "<div class='text-right' style='margin-bottom:20px'>";
    $h[] = "  <button class='btn btn-success btn-lg' onclick='GRelayPush_$id()'>";
    $h[] = "    <i class='fas fa-broadcast-tower'></i> {push_to_all_agents}";
    $h[] = "  </button>";
    $h[] = "</div>";
    $h[] = grelay_form_js_block($id, $page);

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── POST handlers ─────────────────────────────────────────────────────────────

function gscope_push(): void {
    $tpl = new template_admin();
    $id  = gid();

    $routers     = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $_POST["routers"]      ?? ''))));
    $dnss        = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $_POST["dns_servers"]  ?? ''))));
    $ntps        = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $_POST["ntp_servers"]  ?? ''))));
    $times       = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $_POST["time_servers"] ?? ''))));
    $next_server = trim($_POST["next_server"] ?? '');
    $filename    = trim($_POST["filename"]    ?? '');

    $options = [];
    if (!empty($routers))    $options[] = ["name" => "routers",             "type" => "ip-addresses", "value" => $routers];
    if (!empty($dnss))       $options[] = ["name" => "domain-name-servers", "type" => "ip-addresses", "value" => $dnss];
    if (!empty($ntps))       $options[] = ["name" => "ntp-servers",         "type" => "ip-addresses", "value" => $ntps];
    if (!empty($times))      $options[] = ["name" => "time-servers",        "type" => "ip-addresses", "value" => $times];
    if ($next_server !== '') $options[] = ["name" => "next-server",         "type" => "ip-address",   "value" => $next_server];
    if ($filename !== '')    $options[] = ["name" => "filename",            "type" => "text",          "value" => $filename];
    $rfc3442_routes = array_values(array_filter(array_map('trim', preg_split('/[,\n]+/', $_POST["rfc3442_routes"] ?? ''))));
    if (!empty($rfc3442_routes)) $options[] = ["name" => "rfc3442-classless-static-routes", "type" => "classless-routes", "value" => $rfc3442_routes];
    $broadcast = trim($_POST["broadcast_address"] ?? '');
    if ($broadcast !== '') $options[] = ["name" => "broadcast-address", "type" => "ip-address", "value" => $broadcast];

    $flags = [];
    $allow_unknown_mode = trim($_POST["allow_unknown_clients"] ?? '');
    if ($allow_unknown_mode === 'allow')     $flags["allow-unknown-clients"]  = true;
    elseif ($allow_unknown_mode === 'deny')  $flags["allow-unknown-clients"]  = false;
    if (!empty($_POST["always_broadcast"]))      $flags["always-broadcast"]     = true;
    if (!empty($_POST["get_lease_hostnames"]))   $flags["get-lease-hostnames"]  = true;
    if (!empty($_POST["ping_check"]))              $flags["ping-check"]          = true;
    $ddns_domain = trim($_POST["ddns_domainname"] ?? '');
    if ($ddns_domain !== '') $flags["ddns-domainname"] = $ddns_domain;

    $pools = json_decode(trim($_POST["pools_json"] ?? '[]'), true) ?: [];

    $scope = [
        "id"            => trim($_POST["scope_id"]   ?? ''),
        "type"          => trim($_POST["scope_type"]  ?? 'direct'),
        "interface"     => trim($_POST["iface"]       ?? ''),
        "vlan_id"       => intval($_POST["vlan_id"]   ?? 0),
        "subnet"        => trim($_POST["subnet"]      ?? ''),
        "netmask"       => trim($_POST["netmask"]     ?? ''),
        "authoritative" => !empty($_POST["authoritative"]),
        "lease"         => [
            "default" => max(60, intval($_POST["lease_default"] ?? 28800)),
            "max"     => max(60, intval($_POST["lease_max"]     ?? 28800)),
        ],
        "options"       => $options,
        "flags"         => $flags,
        "pools"         => $pools,
    ];
    if (($scope["type"] ?? '') === 'relay') {
        $scope["relay"] = ["relay_id" => intval($_POST["relay_id"] ?? 0)];
    }
    if ($scope["subnet"] === '' || $scope["netmask"] === '') {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{subnet_and_netmask_required}"));
        return;
    }
    if (empty($pools)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{at_least_one_pool_required}"));
        return;
    }

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/dhcp/group/$id/scopes", $scope
    ));
    gscopes_render_results($tpl, $json);
    admin_tracks("Group #$id: DHCP scope pushed to all agents");
}

function grelay_push(): void {
    $tpl = new template_admin();
    $id  = gid();

    $rid       = intval($_POST["relay_id"]         ?? 0);
    $className = trim($_POST["relay_class_name"]   ?? '');
    $desc      = trim($_POST["relay_desc"]         ?? '');
    $circuitId = trim($_POST["relay_circuit_id"]   ?? '');
    $remoteId  = trim($_POST["relay_remote_id"]    ?? '');
    if ($rid < 1) {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{relay_id_required}"));
        return;
    }
    if ($className === '') {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{class_name_required}"));
        return;
    }
    if ($circuitId === '' && $remoteId === '') {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{at_least_one_match_required}"));
        return;
    }
    $match = [];
    if ($circuitId !== '') $match["circuit_id"] = $circuitId;
    if ($remoteId  !== '') $match["remote_id"]  = $remoteId;
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/dhcp/group/$id/relays",
        ["id" => $rid, "class_name" => $className, "description" => $desc, "match" => $match]
    ));
    gscopes_render_results($tpl, $json);
    admin_tracks("Group #$id: DHCP relay $rid pushed to all agents");
}

// ── Result renderer ───────────────────────────────────────────────────────────

function gscopes_render_results(template_admin $tpl, $json): void {
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
}

// ── JavaScript blocks ─────────────────────────────────────────────────────────

function gscope_form_js_block(int $id, string $page): string {
    $l   = [];
    $l[] = "<script>";

    $l[] = "function GAddPool_$id(){";
    $l[] = "  var row='<div class=\"row gscope-pool-row\" style=\"margin-bottom:8px\">'";
    $l[] = "    +'<div class=\"col-md-5\"><input type=\"text\" class=\"form-control pool-start\" placeholder=\"192.168.1.100\"></div>'";
    $l[] = "    +'<div class=\"col-md-5\"><input type=\"text\" class=\"form-control pool-end\" placeholder=\"192.168.1.254\"></div>'";
    $l[] = "    +'<div class=\"col-md-2\"><button type=\"button\" class=\"btn btn-danger btn-sm\" onclick=\"\$(this).closest(\\'.gscope-pool-row\\').remove()\"><i class=\"fas fa-times\"></i></button></div>'";
    $l[] = "    +'</div>';";
    $l[] = "  \$('#gscope-pools-$id').append(row);";
    $l[] = "}";

    $l[] = "function GScopeTypeChange_$id(){";
    $l[] = "  if(\$('#gscope-type-$id').val()==='relay'){";
    $l[] = "    \$('#gscope-relay-row-$id').show();";
    $l[] = "  } else {";
    $l[] = "    \$('#gscope-relay-row-$id').hide();";
    $l[] = "  }";
    $l[] = "}";

    $l[] = "function GScopePush_$id(){";
    $l[] = "  var subnet=\$.trim(\$('#gscope-subnet-$id').val());";
    $l[] = "  var netmask=\$.trim(\$('#gscope-netmask-$id').val());";
    $l[] = "  if(!subnet||!netmask){ alert('{subnet_and_netmask_required}'); return; }";
    $l[] = "  var pools=[];";
    $l[] = "  \$('#gscope-pools-$id .gscope-pool-row').each(function(i){";
    $l[] = "    var s=\$.trim(\$(this).find('.pool-start').val());";
    $l[] = "    var e=\$.trim(\$(this).find('.pool-end').val());";
    $l[] = "    if(s&&e) pools.push({id:'pool-'+i,range_start:s,range_end:e});";
    $l[] = "  });";
    $l[] = "  if(pools.length===0){ alert('{at_least_one_pool_required}'); return; }";
    $l[] = "  \$('#gscope-form-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {pushing}...</div>');";
    $l[] = "  \$.post('$page',{";
    $l[] = "    'push-scope':1, id:$id,";
    $l[] = "    scope_id:\$.trim(\$('#gscope-id-$id').val()),";
    $l[] = "    scope_type:\$('#gscope-type-$id').val(),";
    $l[] = "    iface:\$.trim(\$('#gscope-iface-$id').val()),";
    $l[] = "    vlan_id:parseInt(\$('#gscope-vlan-id-$id').val(),10)||0,";
    $l[] = "    subnet:subnet, netmask:netmask,";
    $l[] = "    authoritative:\$('#gscope-auth-$id').is(':checked')?1:0,";
    $l[] = "    lease_default:\$('#gscope-ldef-$id').val(),";
    $l[] = "    lease_max:\$('#gscope-lmax-$id').val(),";
    $l[] = "    routers:\$.trim(\$('#gscope-routers-$id').val()),";
    $l[] = "    dns_servers:\$.trim(\$('#gscope-dns-$id').val()),";
    $l[] = "    ntp_servers:\$.trim(\$('#gscope-ntp-$id').val()),";
    $l[] = "    time_servers:\$.trim(\$('#gscope-time-$id').val()),";
    $l[] = "    next_server:\$.trim(\$('#gscope-nextserver-$id').val()),";
    $l[] = "    filename:\$.trim(\$('#gscope-filename-$id').val()),";
    $l[] = "    rfc3442_routes:\$.trim(\$('#gscope-rfc3442-$id').val()),";
    $l[] = "    broadcast_address:\$.trim(\$('#gscope-broadcast-$id').val()),";
    $l[] = "    allow_unknown_clients:\$('#gscope-allow-unknown-$id').val(),";
    $l[] = "    always_broadcast:\$('#gscope-always-broadcast-$id').is(':checked')?1:0,";
    $l[] = "    get_lease_hostnames:\$('#gscope-get-lease-hostnames-$id').is(':checked')?1:0,";
    $l[] = "    ping_check:\$('#gscope-ping-check-$id').is(':checked')?1:0,";
    $l[] = "    ddns_domainname:\$.trim(\$('#gscope-ddns-domain-$id').val()),";
    $l[] = "    relay_id:\$('#gscope-relay-id-$id').val(),";
    $l[] = "    pools_json:JSON.stringify(pools)";
    $l[] = "  },function(r){";
    $l[] = "    \$('#gscopes-result-$id').html(r);";
    $l[] = "    dialogInstance2.close();";
    $l[] = "  });";
    $l[] = "}";
    $l[] = "</script>";
    return implode("\n", $l);
}

function grelay_form_js_block(int $id, string $page): string {
    $l   = [];
    $l[] = "<script>";
    $l[] = "function GRelayPush_$id(){";
    $l[] = "  var rid=parseInt(\$('#grelay-id-$id').val(),10);";
    $l[] = "  if(isNaN(rid)||rid<1){ alert('{relay_id_required}'); return; }";
    $l[] = "  var cn=\$.trim(\$('#grelay-classname-$id').val());";
    $l[] = "  if(!cn){ alert('{class_name_required}'); return; }";
    $l[] = "  var cid=\$.trim(\$('#grelay-circuit-id-$id').val());";
    $l[] = "  var rid2=\$.trim(\$('#grelay-remote-id-$id').val());";
    $l[] = "  if(!cid&&!rid2){ alert('{at_least_one_match_required}'); return; }";
    $l[] = "  \$('#grelay-form-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {pushing}...</div>');";
    $l[] = "  \$.post('$page',{";
    $l[] = "    'push-relay':1, id:$id,";
    $l[] = "    relay_id:rid,";
    $l[] = "    relay_class_name:cn,";
    $l[] = "    relay_desc:\$.trim(\$('#grelay-desc-$id').val()),";
    $l[] = "    relay_circuit_id:cid,";
    $l[] = "    relay_remote_id:rid2";
    $l[] = "  },function(r){";
    $l[] = "    \$('#gscopes-result-$id').html(r);";
    $l[] = "    dialogInstance1.close();";
    $l[] = "  });";
    $l[] = "}";
    $l[] = "</script>";
    return implode("\n", $l);
}
