<?php
/**
 * Apply central DHCP config to all agents in a group.
 * Shows a summary of the central config and an "Apply" button.
 * The apply operation REPLACES all DHCP configuration on every agent
 * with the central config (delete all → re-create → reload).
 *
 * Entry:  LoadAjax('div','fw.netagents.group.dhcp.central.apply.php?id={group_id}')
 * API:    GET  /netagents/dhcp/group/{id}/central       — full central config
 *         POST /netagents/dhcp/group/{id}/central/apply  — push to all agents
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }

if (isset($_POST["apply"])) { capply_run(); exit; }
capply_page();

// ── Helpers ───────────────────────────────────────────────────────────────────

function gid(): int {
    return intval($_GET["id"] ?? $_POST["id"] ?? 0);
}

// ── Main page ─────────────────────────────────────────────────────────────────

function capply_page(): void {
    $tpl  = new template_admin();
    $id   = gid();
    $page = CurrentPageName();

    // Load central config summary
    $cfg = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/group/$id/central"));
    if (!is_object($cfg)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $scopeCount = is_array($cfg->scopes ?? null) ? count($cfg->scopes) : 0;
    $relayCount = is_array($cfg->relays ?? null) ? count($cfg->relays) : 0;
    $resCount   = is_array($cfg->reservations ?? null) ? count($cfg->reservations) : 0;
    $updatedAt  = htmlspecialchars($cfg->updated_at ?? '');

    // Load group info for agent count
    $grp = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    $agentCount = is_object($grp) ? intval($grp->agent_count ?? 0) : 0;
    $groupName  = is_object($grp) ? htmlspecialchars($grp->name ?? "Group #$id") : "Group #$id";

    $h = [];
    $h[] = "<div id='capply-result-$id' style='margin-bottom:10px'></div>";

    // Summary
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-clipboard-list'></i>&nbsp; {central_config_summary}</h5></div>";
    $h[] = "  <div class='ibox-content'>";
    $h[] = "    <div style='margin-bottom:15px'>";
    $h[] = "      <span class='badge' style='background:#1c84c6;color:#fff;font-size:13px;padding:5px 12px;margin-right:8px'>";
    $h[] = "        <i class='fas fa-network-wired'></i> {scopes}: $scopeCount</span>";
    $h[] = "      <span class='badge' style='background:#23c6c8;color:#fff;font-size:13px;padding:5px 12px;margin-right:8px'>";
    $h[] = "        <i class='fas fa-route'></i> {dhcp_relays}: $relayCount</span>";
    $h[] = "      <span class='badge' style='background:#ab7df6;color:#fff;font-size:13px;padding:5px 12px;margin-right:8px'>";
    $h[] = "        <i class='fas fa-bookmark'></i> {reservations}: $resCount</span>";
    $h[] = "    </div>";
    if ($updatedAt !== '') {
        $h[] = "    <p class='text-muted'><i class='fas fa-clock'></i> {last_updated}: $updatedAt</p>";
    }
    if ($scopeCount === 0 && $relayCount === 0 && $resCount === 0) {
        $h[] = "    <div class='alert alert-info'><i class='fas fa-info-circle'></i> {central_config_dhcp_empty}</div>";
    }
    $h[] = "  </div></div>";

    // Warning + Apply button
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-play-circle'></i>&nbsp; {apply_central_config}</h5></div>";
    $h[] = "  <div class='ibox-content'>";
    $h[] = "    <div class='alert alert-danger'>";
    $h[] = "      <i class='fas fa-exclamation-triangle'></i>";
    $h[] = "      <strong>{warning}</strong>: {central_apply_warning}";
    $h[] = "      <ul style='margin-top:8px;margin-bottom:0'>";
    $h[] = "        <li>{central_apply_step1}</li>";
    $h[] = "        <li>{central_apply_step2}</li>";
    $h[] = "        <li>{central_apply_step3}</li>";
    $h[] = "      </ul>";
    $h[] = "    </div>";
    $h[] = "    <p><i class='fas fa-layer-group'></i> <strong>$groupName</strong>";
    $h[] = "      &nbsp;<span class='badge' style='background:#1c84c6;color:#fff'>$agentCount</span> {agents}</p>";
    $h[] = "    <button id='capply-btn-$id' class='btn btn-primary btn-lg' onclick='CApply_$id()'>";
    $h[] = "      <i class='fas fa-server'></i> {apply_central_to_all_agents}";
    $h[] = "    </button>";
    $h[] = "  </div></div>";

    // JavaScript
    $confirm_central_apply=$tpl->javascript_parse_text("{apply_central_config}");
    $central_apply_confirm_text=$tpl->javascript_parse_text("{central_apply_confirm_text}");
    $h[] = "<script>";
    $h[] = "function CApply_$id(){";
    $h[] = "  swal({title:'$confirm_central_apply',";
    $h[] = "    text:'$central_apply_confirm_text',type:'warning',showCancelButton:true,";
    $h[] = "    confirmButtonColor:'#1c84c6',confirmButtonText:'{apply}',cancelButtonText:'{cancel}'";
    $h[] = "  },function(ok){ if(!ok) return;";
    $h[] = "    var btn=$('#capply-btn-$id');";
    $h[] = "    btn.prop('disabled',true).html('<i class=\"fas fa-spinner fa-spin\"></i> {applying}...');";
    $h[] = "    $('#capply-result-$id').html('');";
    $h[] = "    $.post('$page',{apply:1,id:$id},function(r){";
    $h[] = "      $('#capply-result-$id').html(r);";
    $h[] = "      btn.prop('disabled',false).html('<i class=\"fas fa-server\"></i> {apply_central_to_all_agents}');";
    $h[] = "    });";
    $h[] = "  });";
    $h[] = "}";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── POST handler ──────────────────────────────────────────────────────────────

function capply_run(): void {
    $tpl = new template_admin();
    $id  = intval($_POST["id"] ?? 0);

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/dhcp/group/$id/central/apply", []
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

    $h = [];

    // Summary badges
    $h[] = "<div style='margin-bottom:12px'>";
    $h[] = "  <span class='badge' style='background:#676a6c;color:#fff;font-size:13px;padding:5px 10px;margin-right:5px'>{total}: $total</span>";
    $h[] = "  <span class='badge' style='background:#1ab394;color:#fff;font-size:13px;padding:5px 10px;margin-right:5px'>{success}: $success</span>";
    if ($failed > 0) {
        $h[] = "  <span class='badge' style='background:#ed5565;color:#fff;font-size:13px;padding:5px 10px'>{failed}: $failed</span>";
    }
    $h[] = "</div>";

    // Per-agent results table
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
    admin_tracks("Group #$id: Central DHCP config applied to all agents ($success/$total success)");
}
