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

if (isset($_GET["dhcp-js"])) { gdhcp_js();   exit; }
gdhcp_form();

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
    return $tpl->js_dialog5("{dhcp_management} — $groupName", "$page?id=$id", 970);
}

/** Renders the group DHCP overview with per-agent DHCP status and tab links. */
function gdhcp_form(): bool {
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
    $agents     = $grp->agents ?? [];

    $h   = [];

    // ── Group info banner ──
    $h[] = "<div class='alert alert-info' style='margin-bottom:15px;margin-top:10px'>";
    $h[] = "  <i class='fas fa-layer-group'></i> <strong>$groupName</strong>";
    $h[] = "  &nbsp;<span class='badge' style='background:#1c84c6;color:#fff'>$agentCount</span> {agents}";
    $h[] = "</div>";

    // ── Tabs: Status + group action pages ──
    $tabs = [
        "{status}"       => "$page?id=$id&status=1",
        "{scopes}"       => "fw.netagents.group.dhcp.scopes.php?id=$id",
        "{reservations}" => "fw.netagents.group.dhcp.reservations.php?id=$id",
        "{configuration}"=> "fw.netagents.group.dhcp.config.php?id=$id",
    ];
    $h[] = $tpl->tabs_default($tabs);

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    return true;
}

// ── Status tab (per-agent DHCP health) ───────────────────────────────────────

if (isset($_GET["status"])) { gdhcp_status(); exit; }

function gdhcp_status(): void {
    $tpl = new template_admin();
    $id  = intval($_GET["id"] ?? 0);

    $grp    = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    $agents = (is_object($grp) && isset($grp->agents)) ? (array)$grp->agents : [];

    $h   = [];
    $h[] = "<div class='ibox'>";
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
