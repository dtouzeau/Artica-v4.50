<?php
/**
 * Netagent APT Upgrade Reports
 * Displays decoded APT upgrade reports from a remote agent.
 * Reports include: timestamp, success/failure, package name, duration, upgraded packages, apt output.
 * APIs: GET /netagents/packages/reports/{id}, GET /netagents/packages/reports/{id}/{index}
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
include_once dirname(__FILE__) . '/ressources/class.sockets.inc';
include_once dirname(__FILE__) . '/ressources/class.netagent.artica.inc';

if (isset($_GET["table"])) { table(); exit; }
if (isset($_GET["detail"])) { detail(); exit; }
if (isset($_GET["detail-js"])) { detail_js(); exit; }
js();

function js(): bool {
    $page = CurrentPageName();
    $tpl = new template_admin();
    $users = new usersMenus();
    if (!$users->AsArticaMetaAdmin) {
        return $tpl->js_error("{no_privileges}");
    }
    $id = intval($_GET["id"]);

    $net = new ArticaNetAgents($id);
    $AgentName = $net->GetAgentHostname();

    return $tpl->js_dialog12("{reports} - $AgentName", "$page?table=yes&id=$id", 850);
}

function table(): bool {
    $tpl = new template_admin();
    $page = CurrentPageName();
    $id = intval($_GET["id"]);

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/packages/reports/$id"));

    // Error from API
    if (isset($json->Status) && !$json->Status) {
        $err = $json->Error ?? "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("ERR." . __LINE__ . "||" . $err));
        return false;
    }

    $reports = $json->reports ?? [];
    $count = intval($json->count ?? 0);

    $html = array();
    if (!is_array($reports) || $count == 0) {
        $html[] = $tpl->div_info("<i class='fas fa-info-circle'></i> {no_reports_available}");
        echo $tpl->_ENGINE_parse_body(implode("\n", $html));
        return true;
    }

    // Table
    $t = time();
    $html[] = "<table id='table-rpt-$t' class='table table-stripped' data-page-size='50'>";
    $html[] = "<thead>";
    $html[] = "<tr>";
    $html[] = "<th data-sortable=true data-type='text'>{date}</th>";
    $html[] = "<th data-sortable=true data-type='text'>{package_name}</th>";
    $html[] = "<th data-sortable=true data-type='text'>{status}</th>";
    $html[] = "<th data-sortable=true data-type='text'>{duration}</th>";
    $html[] = "<th data-sortable=true data-type='number'>{packages2}</th>";
    $html[] = "<th data-sortable=false style='width:1%' nowrap>&nbsp;</th>";
    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";

    $TRCLASS = null;
    $idx = 0;
    foreach ($reports as $rpt) {
        $timestamp = $rpt->timestamp ?? "";
        $success = (bool)($rpt->success ?? false);
        $packageName = htmlspecialchars($rpt->package_name ?? "");
        $duration = htmlspecialchars($rpt->duration ?? "");
        $packages = $rpt->packages ?? [];

        // Format timestamp
        $dateStr = "";
        if ($timestamp != "") {
            try {
                $dt = new DateTime($timestamp);
                $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
                $dateStr = $dt->format("Y-m-d H:i:s");
            } catch (Exception $e) {
                $dateStr = htmlspecialchars($timestamp);
            }
        }

        // Status badge
        if ($success) {
            $statusBadge = "<span class='label' style='background:#1ab394;color:#fff'><i class='fas fa-check'></i> {success}</span>";
        } else {
            $statusBadge = "<span class='label' style='background:#ed5565;color:#fff'><i class='fas fa-times'></i> {failed}</span>";
        }

        // Package count
        $pkgCount = is_array($packages) ? count($packages) : 0;

        // Detail button — fetches single report by index via Go endpoint
        $detailBtn = "<button class='btn btn-default btn-xs' onclick=\"Loadjs('$page?detail-js=yes&id=$id&idx=$idx')\">"
            . "<i class='fas fa-eye'></i></button>";

        if ($TRCLASS == "footable-odd") { $TRCLASS = null; } else { $TRCLASS = "footable-odd"; }

        $html[] = "<tr class='$TRCLASS'>";
        $html[] = "<td style='white-space:nowrap'>$dateStr</td>";
        $html[] = "<td><strong>$packageName</strong></td>";
        $html[] = "<td>$statusBadge</td>";
        $html[] = "<td style='white-space:nowrap'>$duration</td>";
        $html[] = "<td style='text-align:center'>$pkgCount</td>";
        $html[] = "<td>$detailBtn</td>";
        $html[] = "</tr>";
        $idx++;
    }

    $html[] = "</tbody>";
    $html[] = "<tfoot><tr><td colspan='6'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $html[] = "</table>";

    $html[] = "<script>";
    $html[] = "NoSpinner();";
    $html[] = implode("\n", $tpl->ICON_SCRIPTS);
    $html[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $html));
    return true;
}

function detail_js(): void {
    header("content-type: application/x-javascript");
    $tpl = new template_admin();
    $page = CurrentPageName();
    $id = intval($_GET["id"]);
    $idx = intval($_GET["idx"]);

    echo $tpl->_ENGINE_parse_body($tpl->js_dialog9("{report_details}", "$page?detail=yes&id=$id&idx=$idx", 900));
}

function detail(): void {
    $tpl = new template_admin();
    $id = intval($_GET["id"]);
    $idx = intval($_GET["idx"]);

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/packages/reports/$id/$idx"));

    if (!is_object($json) || (isset($json->Status) && !$json->Status)) {
        $err = is_object($json) ? ($json->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_warning($err));
        return;
    }

    $data = $json->report ?? null;
    if (!is_object($data)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_data}"));
        return;
    }

    $success = (bool)($data->success ?? false);
    $packageName = htmlspecialchars($data->package_name ?? "");
    $duration = htmlspecialchars($data->duration ?? "");
    $error = $data->error ?? "";
    $aptOutput = $data->apt_output ?? "";
    $packages = $data->packages ?? [];
    $timestamp = $data->timestamp ?? "";

    // Format timestamp
    $dateStr = "";
    if ($timestamp != "") {
        try {
            $dt = new DateTime($timestamp);
            $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
            $dateStr = $dt->format("Y-m-d H:i:s");
        } catch (Exception $e) {
            $dateStr = htmlspecialchars($timestamp);
        }
    }

    $h = array();
    $h[] = "<div style='padding:15px'>";

    // Header info
    if ($success) {
        $statusHtml = "<span class='label' style='background:#1ab394;color:#fff;font-size:13px'><i class='fas fa-check'></i> {success}</span>";
    } else {
        $statusHtml = "<span class='label' style='background:#ed5565;color:#fff;font-size:13px'><i class='fas fa-times'></i> {failed}</span>";
    }

    $h[] = "<div class='row' style='margin-bottom:10px'>";
    $h[] = "<div class='col-md-3'><strong>{date}:</strong><br>$dateStr</div>";
    $h[] = "<div class='col-md-3'><strong>{package_name}:</strong><br>$packageName</div>";
    $h[] = "<div class='col-md-3'><strong>{duration}:</strong><br>$duration</div>";
    $h[] = "<div class='col-md-3'>$statusHtml</div>";
    $h[] = "</div>";

    // Error message
    if ($error != "") {
        $errorSafe = htmlspecialchars($error);
        $h[] = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> $errorSafe</div>";
    }

    // Packages table
    if (is_array($packages) && count($packages) > 0) {
        $h[] = "<h4 style='margin-top:15px'>{upgraded_packages} (" . count($packages) . ")</h4>";
        $h[] = "<table class='table table-stripped table-condensed'>";
        $h[] = "<thead><tr>";
        $h[] = "<th>{package_name}</th>";
        $h[] = "<th>{old_version}</th>";
        $h[] = "<th>{new_version}</th>";
        $h[] = "</tr></thead><tbody>";
        foreach ($packages as $pkg) {
            $pName = htmlspecialchars($pkg->name ?? "");
            $oldV = htmlspecialchars($pkg->old_version ?? "");
            $newV = htmlspecialchars($pkg->new_version ?? "");
            $h[] = "<tr>";
            $h[] = "<td><strong>$pName</strong></td>";
            $h[] = "<td>$oldV</td>";
            $h[] = "<td><span style='color:#1ab394;font-weight:bold'>$newV</span></td>";
            $h[] = "</tr>";
        }
        $h[] = "</tbody></table>";
    }

    // Apt output
    if ($aptOutput != "") {
        $aptOutputSafe = htmlspecialchars($aptOutput);
        $h[] = "<h4 style='margin-top:15px'>{apt_output}</h4>";
        $h[] = "<div style='max-height:300px;overflow:auto;background:#2f4050;color:#fff;padding:10px;border-radius:4px;font-family:monospace;font-size:12px;white-space:pre-wrap'>";
        $h[] = $aptOutputSafe;
        $h[] = "</div>";
    }

    $h[] = "</div>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}
