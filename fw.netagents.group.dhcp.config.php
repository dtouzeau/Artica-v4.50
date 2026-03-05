<?php
/**
 * Apply DHCP config to all agents in a group (fan-out apply).
 * Each agent runs its own validate → backup → rename → reload pipeline.
 * Shows a per-agent success/failure table in the result.
 *
 * Entry:  LoadAjax('div','fw.netagents.group.dhcp.config.php?id={group_id}')
 * API:    POST /netagents/dhcp/group/{id}/config/apply
 * Response: {group_id, total, success, failed, results:[{agent_id,hostname,success,message}]}
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }

if (isset($_POST["apply"])) { gconfig_apply(); exit; }
gconfig_page();

// ── Helpers ───────────────────────────────────────────────────────────────────

function gid(): int {
    return intval($_GET["id"] ?? $_POST["id"] ?? 0);
}

// ── Main page ─────────────────────────────────────────────────────────────────

function gconfig_page(): void {
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
    $h[] = "<div id='gconfig-result-$id' style='margin-bottom:10px'></div>";

    // ── Group banner ──
    $h[] = "<div class='alert alert-info' style='margin-bottom:15px'>";
    $h[] = "  <i class='fas fa-layer-group'></i> <strong>$groupName</strong>";
    $h[] = "  &nbsp;<span class='badge' style='background:#1c84c6;color:#fff'>$agentCount</span> {agents}";
    $h[] = "</div>";

    // ── Apply section ──
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-play-circle'></i>&nbsp; {group_apply_config}</h5></div>";
    $h[] = "  <div class='ibox-content'>";
    $h[] = "    <div class='alert alert-warning'>";
    $h[] = "      <i class='fas fa-exclamation-triangle'></i>";
    $h[] = "      <strong>{group_apply_warning}</strong>: {group_apply_explain}";
    $h[] = "      <ul style='margin-top:8px;margin-bottom:0'>";
    $h[] = "        <li>{group_apply_explain_validate}</li>";
    $h[] = "        <li>{group_apply_explain_backup}</li>";
    $h[] = "        <li>{group_apply_explain_reload}</li>";
    $h[] = "        <li>{group_apply_explain_409}</li>";
    $h[] = "      </ul>";
    $h[] = "    </div>";
    $h[] = "    <button id='gconfig-apply-btn-$id' class='btn btn-primary btn-lg' onclick='GConfigApply_$id()'>";
    $h[] = "      <i class='fas fa-broadcast-tower'></i> {apply_config_on_all_agents}";
    $h[] = "    </button>";
    $h[] = "    <p class='help-block' style='margin-top:10px'>{group_apply_individual_note}</p>";
    $h[] = "  </div></div>";

    $h[] = gconfig_js_block($id, $page);

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── POST handler ──────────────────────────────────────────────────────────────

/**
 * Fans out config/apply to all agents in the group.
 * Each agent runs its own flock-protected apply pipeline.
 */
function gconfig_apply(): void {
    $tpl  = new template_admin();
    $id   = intval($_POST["id"] ?? 0);

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST(
        "/netagents/dhcp/group/$id/config/apply", []
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

    // ── Summary ──
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
            if (!is_object($r)) continue;
            $hostname = htmlspecialchars($r->hostname ?? "Agent #{$r->agent_id}");
            $msg      = htmlspecialchars($r->message ?? '');

            if (!empty($r->success)) {
                $ico  = "<i class='fas fa-check-circle' style='color:#1ab394'></i>";
                $cell = "<span style='color:#1ab394'><i class='fas fa-play-circle'></i> {apply_successful}</span>";
            } else {
                // Distinguish 409 (apply locked) from other errors
                $is409 = stripos($msg, 'already') !== false || stripos($msg, 'progress') !== false;
                $ico   = "<i class='fas fa-times-circle' style='color:#ed5565'></i>";
                if ($is409) {
                    $cell = "<span style='color:#f8ac59'><i class='fas fa-lock'></i> {apply_already_in_progress}</span>";
                } else {
                    $cell = "<span style='color:#ed5565'>{failed}</span>"
                          . ($msg !== '' ? " &mdash; <small>$msg</small>" : '');
                }
            }
            $h[] = "    <tr><td>$ico</td><td><strong>$hostname</strong></td><td>$cell</td></tr>";
        }

        $h[] = "  </tbody></table>";
    }

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    admin_tracks("Group #$id: DHCP config applied to all agents ($success/$total success)");
}

// ── JavaScript block ──────────────────────────────────────────────────────────

function gconfig_js_block(int $id, string $page): string {
    $l   = [];
    $l[] = "<script>";
    $l[] = "function GConfigApply_$id(){";
    $l[] = "  if(!confirm('{confirm_group_apply_dhcp}')) return;";
    $l[] = "  var btn=\$('#gconfig-apply-btn-$id');";
    $l[] = "  btn.prop('disabled',true).html('<i class=\"fas fa-spinner fa-spin\"></i> {applying}...');";
    $l[] = "  \$('#gconfig-result-$id').html('');";
    $l[] = "  \$.post('$page',{apply:1,id:$id},function(r){";
    $l[] = "    \$('#gconfig-result-$id').html(r);";
    $l[] = "    btn.prop('disabled',false).html('<i class=\"fas fa-broadcast-tower\"></i> {apply_config_on_all_agents}');";
    $l[] = "  });";
    $l[] = "}";
    $l[] = "</script>";
    return implode("\n", $l);
}
