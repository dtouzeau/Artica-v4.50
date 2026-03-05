<?php
/**
 * DHCP config management: diff preview, validate, apply, import, rollback.
 *
 * Apply is synchronous (<2s) — done via inline $.post, not exec.*.php.
 * A 409 Conflict means another apply is already running.
 *
 * Entry:  LoadAjax('div','fw.netagents.dhcp.config.php?id={agent_id}')
 * Handlers:
 *   ?diff={id}         — side-by-side diff view (refreshable)
 *   POST validate={id} — validate candidate config
 *   POST apply={id}    — apply config (validate → backup → reload)
 *   POST import={id}   — import existing dhcpd.conf (requires confirm)
 *   ?revisions={id}    — revision history table
 *   POST rollback={id} — rollback to a named revision
 * API:    GET  /netagents/dhcp/{id}/config/diff
 *         POST /netagents/dhcp/{id}/config/validate
 *         POST /netagents/dhcp/{id}/config/apply
 *         POST /netagents/dhcp/{id}/config/import
 *         GET  /netagents/dhcp/{id}/config/revisions
 *         POST /netagents/dhcp/{id}/config/rollback
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }

if (isset($_GET["diff"]))      { dhcp_diff();      exit; }
if (isset($_GET["revisions"])) { dhcp_revisions(); exit; }
if (isset($_POST["validate"])) { dhcp_validate();  exit; }
if (isset($_POST["apply"]))    { dhcp_apply();     exit; }
if (isset($_POST["import"]))   { dhcp_import();    exit; }
if (isset($_POST["rollback"])) { dhcp_rollback();  exit; }
dhcp_config_page();

// ── Helpers ───────────────────────────────────────────────────────────────────

function aid(): int {
    return intval($_GET["id"] ?? $_POST["id"]
        ?? $_GET["diff"] ?? $_GET["revisions"] ?? 0);
}

// ── Main page ─────────────────────────────────────────────────────────────────

function dhcp_config_page(): void {
    $tpl  = new template_admin();
    $id   = aid();
    $page = CurrentPageName();

    $h   = [];
    $h[] = "<div id='dhcp-config-result-$id' style='margin-bottom:10px'></div>";

    // ── Diff section ──
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'>";
    $h[] = "    <h5><i class='fas fa-code-branch'></i>&nbsp; {config_diff}</h5>";
    $h[] = "    <div class='ibox-tools'>";
    $h[] = "      <a href='#' class='btn btn-xs btn-default' onclick=\"LoadAjaxSilent('dhcp-diff-div-$id','$page?diff=$id');return false;\">";
    $h[] = "        <i class='fas fa-sync-alt'></i> {refresh}";
    $h[] = "      </a>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div id='dhcp-diff-div-$id' class='ibox-content'>";
    $h[] = "    <div class='text-center'><i class='fas fa-spinner fa-spin'></i></div>";
    $h[] = "  </div>";
    $h[] = "</div>";

    // ── Actions section ──
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-tools'></i>&nbsp; {actions}</h5></div>";
    $h[] = "  <div class='ibox-content'>";
    $h[] = "  <div class='row'>";

    // Validate
    $h[] = "    <div class='col-md-3 text-center' style='margin-bottom:10px'>";
    $h[] = "      <button class='btn btn-default btn-block' onclick='DhcpValidate_$id()'>";
    $h[] = "        <i class='fas fa-check-double'></i><br>{validate_config}";
    $h[] = "      </button>";
    $h[] = "      <p class='help-block'>{validate_dhcp_explain}</p>";
    $h[] = "    </div>";

    // Apply
    $h[] = "    <div class='col-md-3 text-center' style='margin-bottom:10px'>";
    $h[] = "      <button id='dhcp-apply-btn-$id' class='btn btn-primary btn-block' onclick='DhcpApply_$id()'>";
    $h[] = "        <i class='fas fa-play-circle'></i><br>{apply_config}";
    $h[] = "      </button>";
    $h[] = "      <p class='help-block'>{apply_dhcp_explain}</p>";
    $h[] = "    </div>";

    // Import
    $h[] = "    <div class='col-md-3 text-center' style='margin-bottom:10px'>";
    $h[] = "      <button class='btn btn-warning btn-block' onclick='DhcpImport_$id()'>";
    $h[] = "        <i class='fas fa-file-import'></i><br>{import_dhcpd_conf}";
    $h[] = "      </button>";
    $h[] = "      <p class='help-block'>{import_dhcp_explain}</p>";
    $h[] = "    </div>";

    $h[] = "  </div></div></div>";

    // ── Revision history ──
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'>";
    $h[] = "    <h5><i class='fas fa-history'></i>&nbsp; {revision_history}</h5>";
    $h[] = "    <div class='ibox-tools'>";
    $h[] = "      <a href='#' class='btn btn-xs btn-default' onclick=\"LoadAjaxSilent('dhcp-revisions-div-$id','$page?revisions=$id');return false;\">";
    $h[] = "        <i class='fas fa-sync-alt'></i> {refresh}";
    $h[] = "      </a>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div id='dhcp-revisions-div-$id' class='ibox-content' style='padding:0'>";
    $h[] = "    <div class='text-center' style='padding:10px'><i class='fas fa-spinner fa-spin'></i></div>";
    $h[] = "  </div>";
    $h[] = "</div>";

    $h[] = dhcp_config_js_block($id, $page);

    // Auto-load on page ready
    $h[] = "<script>";
    $h[] = "\$(document).ready(function(){";
    $h[] = "  LoadAjaxSilent('dhcp-diff-div-$id','$page?diff=$id');";
    $h[] = "  LoadAjaxSilent('dhcp-revisions-div-$id','$page?revisions=$id');";
    $h[] = "});";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Handlers ──────────────────────────────────────────────────────────────────

/** Side-by-side diff view. */
function dhcp_diff(): void {
    $tpl = new template_admin();
    $id  = intval($_GET["diff"]);

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/$id/config/diff"));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    if (isset($json->Status) && !$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->Error ?? "{error}")));
        return;
    }

    $changed = !empty($json->changed);
    $current   = htmlspecialchars($json->current   ?? '');
    $candidate = htmlspecialchars($json->candidate ?? '');

    $h   = [];

    // Changed badge
    if ($changed) {
        $h[] = "<div class='alert alert-warning' style='margin:0 0 10px 0'>";
        $h[] = "  <i class='fas fa-exclamation-triangle'></i> <strong>{config_has_pending_changes}</strong>";
        $h[] = "</div>";
    } else {
        $h[] = "<div class='alert alert-success' style='margin:0 0 10px 0'>";
        $h[] = "  <i class='fas fa-check-circle'></i> {config_up_to_date}";
        $h[] = "</div>";
    }

    $h[] = "<div class='row'>";
    $h[] = "  <div class='col-md-6'>";
    $h[] = "    <label style='font-weight:bold'><i class='fas fa-file-alt'></i> {current_config}</label>";
    $h[] = "    <pre style='background:#f8f8f8;border:1px solid #ddd;max-height:300px;overflow-y:auto;font-size:12px'>$current</pre>";
    $h[] = "  </div>";
    $h[] = "  <div class='col-md-6'>";
    $h[] = "    <label style='font-weight:bold'><i class='fas fa-file-code'></i> {candidate_config}</label>";
    $bg = $changed ? '#fffde7' : '#f8f8f8';
    $h[] = "    <pre style='background:$bg;border:1px solid #ddd;max-height:300px;overflow-y:auto;font-size:12px'>$candidate</pre>";
    $h[] = "  </div>";
    $h[] = "</div>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

/** Returns revision history table HTML. */
function dhcp_revisions(): void {
    $tpl  = new template_admin();
    $id   = intval($_GET["revisions"]);
    $page = CurrentPageName();

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/$id/config/revisions"));
    $revs = [];
    if (is_array($json)) $revs = $json;
    elseif (is_object($json) && isset($json->revisions)) $revs = (array)$json->revisions;

    if (empty($revs)) {
        echo $tpl->_ENGINE_parse_body("<p style='padding:15px;color:#999'><i>{no_revisions_available}</i></p>");
        return;
    }

    $h   = [];
    $h[] = "<table class='table table-striped table-condensed' style='margin:0'>";
    $h[] = "  <thead><tr>";
    $h[] = "    <th>{revision}</th>";
    $h[] = "    <th style='width:1%'></th>";
    $h[] = "  </tr></thead><tbody>";
    foreach ($revs as $rev) {
        $revName = htmlspecialchars(is_string($rev) ? $rev : ($rev->name ?? $rev->filename ?? ''));
        // Parse timestamp from filename (e.g. iscdhcp3.json.20260302T100000Z)
        $dateStr = preg_match('/(\d{8}T\d{6}Z)/', $revName, $m) ? $m[1] : $revName;
        try { $dateStr = (new DateTime($dateStr))->format('Y-m-d H:i:s'); } catch (Exception $e) {}
        $rollbackJs = "if(confirm('{confirm_rollback}')){";
        $rollbackJs .= "\$.post('$page',{id:$id,rollback:1,revision:'" . addslashes($revName) . "'},";
        $rollbackJs .= "function(r){\$('#dhcp-config-result-$id').html(r);LoadAjaxSilent('dhcp-diff-div-$id','$page?diff=$id');LoadAjaxSilent('dhcp-revisions-div-$id','$page?revisions=$id');});}";
        $h[] = "  <tr>";
        $h[] = "    <td><span style='font-family:monospace;font-size:12px'>$dateStr</span></td>";
        $h[] = "    <td><button class='btn btn-xs btn-warning' onclick=\"$rollbackJs\">";
        $h[] = "        <i class='fas fa-undo'></i> {rollback}";
        $h[] = "    </button></td>";
        $h[] = "  </tr>";
    }
    $h[] = "  </tbody></table>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

/** Validate candidate config and return pass/fail + error list. */
function dhcp_validate(): void {
    $tpl = new template_admin();
    $id  = intval($_POST["validate"]);

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST(
        "/netagents/dhcp/$id/config/validate", []
    ));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    if (isset($json->Status) && !$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->Error ?? "{error}")));
        return;
    }

    $valid = !empty($json->valid);
    $errs  = (array)($json->errors ?? []);

    $h   = [];
    if ($valid) {
        $h[] = $tpl->div_success("<i class='fas fa-shield-alt'></i> <strong>{config_valid}</strong> &mdash; {dhcpd_syntax_ok}");
    } else {
        $h[] = "<div class='alert alert-danger'>";
        $h[] = "  <i class='fas fa-times-circle'></i> <strong>{config_invalid}</strong>";
        if (!empty($errs)) {
            $h[] = "  <ul style='margin-top:8px;margin-bottom:0'>";
            foreach ($errs as $e) { $h[] = "    <li><small style='font-family:monospace'>" . htmlspecialchars($e) . "</small></li>"; }
            $h[] = "  </ul>";
        }
        $h[] = "</div>";
    }
    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

/**
 * Apply config: validate → backup → rename → reload.
 * 409 = another apply already running.
 * 500 = apply failed, backup restored automatically by agent.
 */
function dhcp_apply(): void {
    $tpl = new template_admin();
    $id  = intval($_POST["apply"]);
    $page = CurrentPageName();

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST(
        "/netagents/dhcp/$id/config/apply", []
    ));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    if (isset($json->Status) && !$json->Status) {
        $err = htmlspecialchars($json->Error ?? "{error}");
        // 409 — another apply running
        if (stripos($err, 'already') !== false || stripos($err, 'progress') !== false) {
            echo $tpl->_ENGINE_parse_body($tpl->div_warning("{apply_already_in_progress}"));
        } else {
            echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        }
        return;
    }
    $h   = [];
    $h[] = $tpl->div_success("<i class='fas fa-check-circle'></i> <strong>{apply_successful}</strong> &mdash; {dhcpd_reloaded}");
    // Refresh diff to show no pending changes
    $h[] = "<script>LoadAjaxSilent('dhcp-diff-div-$id','$page?diff=$id');LoadAjaxSilent('dhcp-revisions-div-$id','$page?revisions=$id');</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    admin_tracks("Netagent #$id: DHCP config applied");
}

/** Import existing dhcpd.conf into the JSON store. */
function dhcp_import(): void {
    $tpl = new template_admin();
    $id  = intval($_POST["import"]);
    $page = CurrentPageName();

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST(
        "/netagents/dhcp/$id/config/import", []
    ));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    if (isset($json->Status) && !$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->Error ?? "{error}")));
        return;
    }
    $h   = [];
    $h[] = $tpl->div_success("<i class='fas fa-check-circle'></i> {import_successful}");
    $h[] = "<script>LoadAjaxSilent('dhcp-diff-div-$id','$page?diff=$id');</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    admin_tracks("Netagent #$id: DHCP config imported from dhcpd.conf");
}

/** Rollback to a named revision. */
function dhcp_rollback(): void {
    $tpl      = new template_admin();
    $id       = intval($_POST["id"]);
    $revision = trim($_POST["revision"] ?? '');
    $page     = CurrentPageName();

    if ($revision === '') {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{revision_required}"));
        return;
    }
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/dhcp/$id/config/rollback", ["revision" => $revision]
    ));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    if (isset($json->Status) && !$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->Error ?? "{error}")));
        return;
    }
    echo $tpl->_ENGINE_parse_body(
        $tpl->div_success("<i class='fas fa-undo'></i> {rollback_successful}")
    );
    admin_tracks("Netagent #$id: DHCP config rolled back to '$revision'");
}

// ── JavaScript block ──────────────────────────────────────────────────────────

function dhcp_config_js_block(int $id, string $page): string {
    $l   = [];
    $l[] = "<script>";

    // Validate
    $l[] = "function DhcpValidate_$id(){";
    $l[] = "  \$('#dhcp-config-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {validating}...</div>');";
    $l[] = "  \$.post('$page',{validate:$id},function(r){ \$('#dhcp-config-result-$id').html(r); });";
    $l[] = "}";

    // Apply (disable button during request, re-enable on error)
    $l[] = "function DhcpApply_$id(){";
    $l[] = "  var btn=\$('#dhcp-apply-btn-$id');";
    $l[] = "  btn.prop('disabled',true).html('<i class=\"fas fa-spinner fa-spin\"></i> {applying}...');";
    $l[] = "  \$('#dhcp-config-result-$id').html('');";
    $l[] = "  \$.post('$page',{apply:$id},function(r){";
    $l[] = "    \$('#dhcp-config-result-$id').html(r);";
    $l[] = "    btn.prop('disabled',false).html('<i class=\"fas fa-play-circle\"></i><br>{apply_config}');";
    $l[] = "  });";
    $l[] = "}";

    // Import (requires confirmation)
    $l[] = "function DhcpImport_$id(){";
    $l[] = "  if(!confirm('{confirm_import_dhcpd_conf}')) return;";
    $l[] = "  \$('#dhcp-config-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {importing}...</div>');";
    $l[] = "  \$.post('$page',{import:$id},function(r){ \$('#dhcp-config-result-$id').html(r); });";
    $l[] = "}";

    $l[] = "</script>";
    return implode("\n", $l);
}
