<?php
/**
 * Unbound DNS Manager — Group Overview.
 * Shows per-agent health status and allows pushing config from one agent to all others.
 *
 * Entry:  Loadjs('fw.netagents.group.unbound.php?gunbound-js={group_id}')
 *         LoadAjax('div','fw.netagents.group.unbound.php?id={group_id}')
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }
$users = new usersMenus();
if (!$users->AsArticaMetaAdmin) { exit(); }

if (isset($_GET["gunbound-js"]))    { gunbound_js();          exit; }
if (isset($_GET["status"]))         { gunbound_status();      exit; }
if (isset($_POST["push-config"]))   { gunbound_push_config(); exit; }
gunbound_form();

// ── Helpers ──────────────────────────────────────────────────────────────────

function gid(): int {
    return intval($_GET["id"] ?? $_POST["id"] ?? 0);
}

// ── Dispatcher actions ───────────────────────────────────────────────────────

/** Opens js_dialog6 (970 px) with group name. */
function gunbound_js(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["gunbound-js"]);

    $grp = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    $groupName = (is_object($grp) && !empty($grp->name))
        ? htmlspecialchars($grp->name) : "Group #$id";

    $tpl->js_dialog6("{unbound_dns_manager} — $groupName", "$page?id=$id", 970);
}

/** Main form: group banner + tab shell. */
function gunbound_form(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = gid();

    $grp = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    if (!is_object($grp) || (isset($grp->Status) && !$grp->Status)) {
        $err = is_object($grp) ? htmlspecialchars($grp->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }

    $groupName  = htmlspecialchars($grp->name ?? "Group #$id");
    $agentCount = intval($grp->agent_count ?? 0);

    $h = [];
    $h[] = "<div class='alert alert-info' style='margin-bottom:15px;margin-top:10px'>";
    $h[] = "  <i class='fas fa-layer-group'></i> <strong>$groupName</strong>";
    $h[] = "  &nbsp;<span class='badge' style='background:#1c84c6;color:#fff'>$agentCount</span> {agents}";
    $h[] = "</div>";

    $tabs = [];
    $tabs["{status}"]        = "$page?id=$id&status=1";
    $tabs["{configuration}"] = "$page?id=$id&push-form=1";

    // We use inline tab for push form since it's part of this same page
    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    echo $tpl->tabs_default($tabs);
}

/** Per-agent health status table + push config form. */
function gunbound_status(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = gid();

    // Check if this is actually the push form tab
    if (isset($_GET["push-form"])) {
        gunbound_push_form($id, $tpl, $page);
        return;
    }

    $grp = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    if (!is_object($grp) || (isset($grp->Status) && !$grp->Status)) {
        $err = is_object($grp) ? htmlspecialchars($grp->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }

    $agents = $grp->agents ?? [];
    if (empty($agents)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_agents}"));
        return;
    }

    $h = [];
    $h[] = "<div style='margin-top:10px'><h4><i class='fas fa-heartbeat'></i> {unbound_group_status}</h4></div>";
    $h[] = "<table class='table table-striped'>";
    $h[] = "<thead><tr style='background:#f5f5f5'>";
    $h[] = "  <th>{hostname}</th>";
    $h[] = "  <th>{installed}</th>";
    $h[] = "  <th>{status}</th>";
    $h[] = "  <th>PID</th>";
    $h[] = "  <th>{unbound_uptime}</th>";
    $h[] = "  <th></th>";
    $h[] = "</tr></thead><tbody>";

    foreach ($agents as $ag) {
        $agId     = intval($ag->id ?? 0);
        $hostname = htmlspecialchars($ag->hostname ?? "Agent #$agId");
        $enabled  = !empty($ag->enabled);

        if (!$enabled) continue;

        // Fetch health for each agent
        $health = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/unbound/health/$agId"));

        if (!is_object($health) || (isset($health->Status) && !$health->Status)) {
            $errMsg = is_object($health) ? htmlspecialchars($health->Error ?? '') : 'unreachable';
            $h[] = "<tr>";
            $h[] = "  <td><strong>$hostname</strong></td>";
            $h[] = "  <td><span class='badge' style='background:#676a6c;color:#fff'>?</span></td>";
            $h[] = "  <td><span class='text-danger'>$errMsg</span></td>";
            $h[] = "  <td>-</td><td>-</td>";
            $h[] = "  <td></td>";
            $h[] = "</tr>";
            continue;
        }

        $installed = !empty($health->installed);
        $running   = !empty($health->running);
        $pid       = intval($health->pid ?? 0);
        $uptime    = htmlspecialchars($health->uptime ?? '-');

        $instBadge = $installed
            ? "<span class='badge' style='background:#1ab394;color:#fff'>{yes}</span>"
            : "<span class='badge' style='background:#ed5565;color:#fff'>{no}</span>";

        $runBadge = '';
        if ($installed) {
            $runBadge = $running
                ? "<span class='badge' style='background:#1ab394;color:#fff'>{unbound_running}</span>"
                : "<span class='badge' style='background:#ed5565;color:#fff'>{unbound_stopped}</span>";
        } else {
            $runBadge = "<span class='text-muted'>-</span>";
        }

        $manageBtn = "<button class='btn btn-primary btn-xs' onclick=\"Loadjs('fw.netagents.unbound.php?unbound-js=$agId')\">"
                   . "<i class='fas fa-cog'></i> {manage}</button>";

        $h[] = "<tr>";
        $h[] = "  <td><strong>$hostname</strong></td>";
        $h[] = "  <td>$instBadge</td>";
        $h[] = "  <td>$runBadge</td>";
        $h[] = "  <td>" . ($installed && $running ? $pid : '-') . "</td>";
        $h[] = "  <td>" . ($installed && $running ? $uptime : '-') . "</td>";
        $h[] = "  <td>$manageBtn</td>";
        $h[] = "</tr>";
    }
    $h[] = "</tbody></table>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

/** Push config form: select source agent, push to all others. */
function gunbound_push_form(int $id, $tpl, string $page): void {
    $grp = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    if (!is_object($grp) || (isset($grp->Status) && !$grp->Status)) {
        $err = is_object($grp) ? htmlspecialchars($grp->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }

    $agents = $grp->agents ?? [];
    $enabledAgents = [];
    foreach ($agents as $ag) {
        if (!empty($ag->enabled)) {
            $enabledAgents[] = $ag;
        }
    }

    if (count($enabledAgents) < 2) {
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{push_config_explain}"));
        return;
    }

    $opts = '';
    foreach ($enabledAgents as $ag) {
        $agId = intval($ag->id ?? 0);
        $hostname = htmlspecialchars($ag->hostname ?? "Agent #$agId");
        $opts .= "<option value='$agId'>$hostname (#$agId)</option>";
    }

    $h = [];
    $h[] = "<div style='margin-top:10px'>";
    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-upload'></i>&nbsp; {push_config_from_agent}</h5></div>";
    $h[] = "<div class='ibox-content'>";
    $h[] = "  <p>{push_config_explain}</p>";
    $h[] = "  <div class='form-group'><label>{source_agent}</label>";
    $h[] = "    <select id='gpush-source-$id' class='form-control'>$opts</select></div>";
    $h[] = "  <div style='text-align:right'>";
    $h[] = "    <button class='btn btn-primary btn-lg' onclick='GPushConfig_$id()'>";
    $h[] = "      <i class='fas fa-upload'></i> {push_config_to_group}</button>";
    $h[] = "  </div>";
    $h[] = "  <div id='gpush-result-$id' style='margin-top:15px'></div>";
    $h[] = "</div></div>";
    $h[] = "</div>";

    $h[] = "<script>";
    $h[] = "function GPushConfig_$id(){";
    $h[] = "  swal({title:'{confirm_push_unbound_config}',type:'warning',showCancelButton:true,";
    $h[] = "    confirmButtonColor:'#1c84c6',confirmButtonText:'{yes}',cancelButtonText:'{cancel}'},";
    $h[] = "  function(ok){ if(!ok) return;";
    $h[] = "    var src=\$('#gpush-source-$id').val();";
    $h[] = "    \$('#gpush-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {saving}...</div>');";
    $h[] = "    \$.post('$page',{'push-config':1,id:$id,source_id:src},function(r){";
    $h[] = "      \$('#gpush-result-$id').html(r);";
    $h[] = "    });";
    $h[] = "  });";
    $h[] = "}";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Push config ──────────────────────────────────────────────────────────────

function gunbound_push_config(): void {
    $tpl      = new template_admin();
    $id       = gid();
    $sourceId = intval($_POST["source_id"] ?? 0);

    if ($sourceId <= 0) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{source_agent}"));
        return;
    }

    // Get source config
    $srcCfg = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/unbound/config/$sourceId"));
    if (!is_object($srcCfg) || (isset($srcCfg->Status) && !$srcCfg->Status)) {
        $err = is_object($srcCfg) ? htmlspecialchars($srcCfg->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }

    // Get group agents
    $grp = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    if (!is_object($grp) || (isset($grp->Status) && !$grp->Status)) {
        $err = is_object($grp) ? htmlspecialchars($grp->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }

    $agents = $grp->agents ?? [];

    // Validate source agent belongs to this group
    $sourceInGroup = false;
    foreach ($agents as $ag) {
        if (intval($ag->id ?? 0) === $sourceId) { $sourceInGroup = true; break; }
    }
    if (!$sourceInGroup) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    $results = [];
    $total = 0;
    $success = 0;
    $failed = 0;

    $cfgData = json_decode(json_encode($srcCfg), true);

    foreach ($agents as $ag) {
        $agId = intval($ag->id ?? 0);
        if (!$agId || !($ag->enabled ?? false) || $agId === $sourceId) continue;

        $total++;
        $hostname = htmlspecialchars($ag->hostname ?? "Agent #$agId");

        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
            "/netagents/unbound/config/$agId", $cfgData
        ));

        if (is_object($json) && (!isset($json->Status) || $json->Status !== false)) {
            $success++;
            $results[] = ["agent_id" => $agId, "hostname" => $hostname, "success" => true, "message" => ""];
        } else {
            $failed++;
            $errMsg = is_object($json) ? ($json->Error ?? "{error}") : "{error}";
            $results[] = ["agent_id" => $agId, "hostname" => $hostname, "success" => false, "message" => htmlspecialchars($errMsg)];
        }
    }

    // Render results
    echo $tpl->_ENGINE_parse_body(gunbound_render_results($tpl, $total, $success, $failed, $results));
    admin_tracks("Group #$id: Unbound config pushed from agent #$sourceId to $total agents ($success success, $failed failed)");
}

function gunbound_render_results($tpl, int $total, int $success, int $failed, array $results): string {
    $h = [];

    // Summary badges
    $h[] = "<div style='margin-bottom:12px'>";
    $h[] = "  <span class='badge' style='background:#676a6c;color:#fff;font-size:13px;padding:5px 10px;margin-right:5px'>{total}: $total</span>";
    $h[] = "  <span class='badge' style='background:#1ab394;color:#fff;font-size:13px;padding:5px 10px;margin-right:5px'>{success}: $success</span>";
    if ($failed > 0) {
        $h[] = "  <span class='badge' style='background:#ed5565;color:#fff;font-size:13px;padding:5px 10px'>{failed}: $failed</span>";
    }
    $h[] = "</div>";

    // Per-agent table
    if (!empty($results)) {
        $h[] = "<table class='table table-striped table-condensed'>";
        $h[] = "  <thead><tr style='background:#f5f5f5'>";
        $h[] = "    <th style='width:1%'></th>";
        $h[] = "    <th>{hostname}</th>";
        $h[] = "    <th>{status}</th>";
        $h[] = "  </tr></thead><tbody>";
        foreach ($results as $r) {
            $hostname = $r["hostname"];
            $msg      = $r["message"];
            if ($r["success"]) {
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
