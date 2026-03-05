<?php
/**
 * DHCP static host reservations management for a single remote agent.
 * Provides add / edit / delete. "Assign from lease" pre-fills the form
 * from the leases table by passing ?from-lease=1&ip=...&mac=... params.
 *
 * Entry:  LoadAjax('div','fw.netagents.dhcp.reservations.php?id={agent_id}') — full list
 *         Loadjs('fw.netagents.dhcp.reservations.php?res-add-js={agent_id}')  — add dialog
 *         Loadjs('fw.netagents.dhcp.reservations.php?res-edit-js={agent_id}&res_id={r}') — edit dialog
 * API:    GET    /netagents/dhcp/{id}/reservations
 *         POST   /netagents/dhcp/{id}/reservations            — add
 *         POST   /netagents/dhcp/{id}/reservations/{res_id}   — update
 *         DELETE /netagents/dhcp/{id}/reservations/{res_id}
 *         GET    /netagents/dhcp/{id}/scopes                  — for scope_id dropdown
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }

if (isset($_GET["res-add-js"]))    { res_add_js();    exit; }
if (isset($_GET["res-edit-js"]))   { res_edit_js();   exit; }
if (isset($_GET["res-form"]))      { res_form();      exit; }
if (isset($_POST["save-res"]))     { res_save();      exit; }
if (isset($_POST["delete-res"]))   { res_delete();    exit; }
if(isset($_GET["list"])){res_table();exit();}
res_list();

// ── Helpers ───────────────────────────────────────────────────────────────────

function aid(): int {
    return intval($_GET["id"] ?? $_POST["id"]
        ?? $_GET["res-add-js"] ?? $_GET["res-edit-js"] ?? 0);
}

function res_options_to_form($options): array {
    $routers = []; $dns = []; $ntp = []; $time = [];
    $next_server = ''; $filename = '';
    foreach ((array)$options as $opt) {
        if (!is_object($opt)) continue;
        $name = $opt->name ?? '';
        $val  = $opt->value ?? null;
        if ($name === 'routers')             $routers     = (array)$val;
        if ($name === 'domain-name-servers') $dns         = (array)$val;
        if ($name === 'ntp-servers')         $ntp         = (array)$val;
        if ($name === 'time-servers')        $time        = (array)$val;
        if ($name === 'next-server')         $next_server = is_string($val) ? $val : '';
        if ($name === 'filename')            $filename    = is_string($val) ? $val : '';
    }
    return [
        'routers'     => implode(', ', $routers),
        'dns'         => implode(', ', $dns),
        'ntp'         => implode(', ', $ntp),
        'time'        => implode(', ', $time),
        'next_server' => $next_server,
        'filename'    => $filename,
    ];
}

// ── Dispatcher actions ────────────────────────────────────────────────────────

/** Full reservations list. */
function res_list(): void {
    $tpl  = new template_admin();
    $id   = aid();
    $page = CurrentPageName();




    $h   = [];
    $h[] = "<div id='res-result-$id' style='margin-bottom:10px'></div>";
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'>";
    $h[] = "    <div><i class='fas fa-bookmark'></i>&nbsp;<strong>{reservations}</strong>";
    $h[] = "    <div style='float:right'>";
    $h[] = "      <a href='#' onclick=\"Loadjs('$page?res-add-js=$id');return false;\" class='btn btn-xs btn-primary'>";
    $h[] = "        <i class='fas fa-plus'></i> {add_reservation}";
    $h[] = "      </a>";
    $h[] = "    </div>";
    $h[] = "    </div>";
    $h[] = "    <div><small class='text-muted'>{dhcp_reservations_explain}</small></div>";
    $h[] = "  </div>";
    $h[] = "  <div class='ibox-content' style='padding:0' id='reservations-list-$id'>";



    $h[] = "  </div></div>";
    $h[] = "<div id='res-list-div-$id'></div>";
    $h[] = "<script>LoadAjaxSilent('reservations-list-$id','$page?list=$id');</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

function res_table():bool{
    $rsvs = [];
    $tpl  = new template_admin();
    $id=intval($_GET["list"] ?? $_POST["list"] ?? 0);
    $page = CurrentPageName();
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/$id/reservations"));


    if (is_object($json) && isset($json->Status) && !$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->Error ?? "{error}")));
        return false;
    }

    if (is_array($json))  $rsvs = $json;
    elseif (is_object($json) && isset($json->reservations)) $rsvs = (array)$json->reservations;
    if (empty($rsvs)) {
        $h[] = "    <p style='padding:15px;color:#999'><i>{no_reservations_defined}</i></p>";
        echo $tpl->_ENGINE_parse_body(implode("\n", $h));
        return true;
    }

        $h[] = "    <table class='table table-stripped' data-page-size='50'>";
        $h[] = "      <thead><tr>";
        $h[] = "        <th data-sortable='true' data-type='text'>ID</th>";
        $h[] = "        <th data-sortable='true' data-type='text'>{hostname}</th>";
        $h[] = "        <th data-sortable='true' data-type='text'>MAC</th>";
        $h[] = "        <th data-sortable='true' data-type='text'>{fixed_ipaddr}</th>";
        $h[] = "        <th data-sortable='true' data-type='text'>{scope}</th>";
        $h[] = "        <th style='width:1%'></th>";
        $h[] = "      </tr></thead><tbody>";
        $TRCLASS = null;

        foreach ($rsvs as $r) {
            if (!is_object($r)) continue;
            $zid=md5(serialize($r));
            if ($TRCLASS === 'footable-odd') { $TRCLASS = null; } else { $TRCLASS = 'footable-odd'; }
            $rid      = htmlspecialchars($r->id       ?? '');
            $rname    = htmlspecialchars($r->name     ?? $rid);
            $rmac     = htmlspecialchars($r->mac      ?? '');
            $rip      = htmlspecialchars($r->fixed_ip ?? '');
            $rscope   = htmlspecialchars($r->scope_id ?? '');
            $editJs   = "Loadjs('$page?res-edit-js=$id&res_id=" . urlencode($rid) . "');";
            $delJs    = "if(confirm('{confirm_delete}')){";
            $delJs   .= "\$.post('$page',{id:$id,'delete-res':'" . addslashes($rid) . "'},";
            $delJs   .= "function(r){\$('#res-result-$id').html(r);$('#$zid').remove();});}";
            $assignJs = "Loadjs('$page?res-add-js=$id&from_ip=" . urlencode($rip) . "&from_mac=" . urlencode($rmac) . "');";

            $h[] = "      <tr class='$TRCLASS' id='$zid'>";
            $h[] = "        <td>$rid</td>";
            $h[] = "        <td><strong>$rname</strong></td>";
            $h[] = "        <td><span style='font-family:monospace'>$rmac</span></td>";
            $h[] = "        <td>$rip</td>";
            $h[] = "        <td><small>$rscope</small></td>";
            $h[] = "        <td style='white-space:nowrap'>";
            $h[] = "          " . $tpl->icon_edit_field($editJs, "AsSystemAdministrator");
            $h[] = "          " . $tpl->icon_delete($delJs, "AsSystemAdministrator");
            $h[] = "        </td>";
            $h[] = "      </tr>";
        }
        $h[] = "      </tbody>";
        $h[] = "      <tfoot><tr><td colspan='6'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
        $h[] = "    </table>";
        $h[] = "    <script>NoSpinner();" . implode("\n", $tpl->ICON_SCRIPTS);
        $h[] = "    </script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    return true;
}

/** Opens a 700px add-reservation dialog. Supports pre-fill from lease via ?from_ip/from_mac. */
function res_add_js(): bool {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["res-add-js"]);
    $extra = '';
    if (!empty($_GET["from_ip"]))  $extra .= "&from_ip="  . urlencode($_GET["from_ip"]);
    if (!empty($_GET["from_mac"])) $extra .= "&from_mac=" . urlencode($_GET["from_mac"]);
    return $tpl->js_dialog2("{add_reservation}", "$page?id=$id&res-form=1$extra", 700);
}

/** Opens a 700px edit-reservation dialog pre-filled from API. */
function res_edit_js(): bool {
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $id     = intval($_GET["res-edit-js"]);
    $res_id = urlencode($_GET["res_id"] ?? '');
    return $tpl->js_dialog2("{edit_reservation}", "$page?id=$id&res-form=1&res_id=$res_id", 700);
}

/** Renders the add/edit form. */
function res_form(): void {
    $tpl    = new template_admin();
    $id     = aid();
    $page   = CurrentPageName();
    $res_id = trim($_GET["res_id"] ?? '');

    // Fetch scopes for the scope_id dropdown
    $scopes_json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/$id/scopes"));
    $scopes = [];
    if (is_array($scopes_json)) $scopes = $scopes_json;
    elseif (is_object($scopes_json) && isset($scopes_json->scopes)) $scopes = (array)$scopes_json->scopes;

    // Pre-fill values
    $rid         = '';
    $rname       = '';
    $rmac        = htmlspecialchars(trim($_GET["from_mac"] ?? ''));
    $rip         = htmlspecialchars(trim($_GET["from_ip"]  ?? ''));
    $rscope      = '';
    $routers     = ''; $rdns = ''; $rntp = ''; $rtime = '';
    $rnext_server = ''; $rfilename = '';
    $isEdit  = false;

    if ($res_id !== '') {
        $rjson = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
            "/netagents/dhcp/$id/reservations/" . urlencode($res_id)
        ));
        if (is_object($rjson) && !isset($rjson->Status)) {
            $rid    = htmlspecialchars($rjson->id       ?? '');
            $rname  = htmlspecialchars($rjson->name     ?? '');
            $rmac   = htmlspecialchars($rjson->mac      ?? '');
            $rip    = htmlspecialchars($rjson->fixed_ip ?? '');
            $rscope = htmlspecialchars($rjson->scope_id ?? '');
            $optArr = res_options_to_form($rjson->options ?? []);
            $routers      = htmlspecialchars($optArr['routers']);
            $rdns         = htmlspecialchars($optArr['dns']);
            $rntp         = htmlspecialchars($optArr['ntp']);
            $rtime        = htmlspecialchars($optArr['time']);
            $rnext_server = htmlspecialchars($optArr['next_server']);
            $rfilename    = htmlspecialchars($optArr['filename']);
            $isEdit = true;
        }
    }

    $h   = [];
    $h[] = "<div id='res-form-result-$id'></div>";
    $h[] = "<input type='hidden' id='res-existing-id-$id' value='" . htmlspecialchars($res_id) . "'>";

    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-bookmark'></i>&nbsp; {reservation_details}</h5></div>";
    $h[] = "  <div class='ibox-content'>";

    // ID + Name row
    $h[] = "  <div class='row' style='margin-bottom:15px'>";
    $h[] = "    <div class='col-md-5'>";
    $h[] = "      <label>ID / {hostname}</label>";
    $h[] = "      <input type='text' id='res-id-$id' class='form-control'" . ($isEdit ? " readonly" : "") . " value='$rid' placeholder='pc01'>";
    $h[] = "      <p class='help-block'>{reservation_id_explain}</p>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-7'>";
    $h[] = "      <label>{description} <small class='text-muted'>{optional}</small></label>";
    $h[] = "      <input type='text' id='res-name-$id' class='form-control' value='$rname' placeholder='PC-01 (Reception)'>";
    $h[] = "    </div>";
    $h[] = "  </div>";

    // MAC + IP row
    $h[] = "  <div class='row' style='margin-bottom:15px'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>MAC</label>";
    $h[] = "      <input type='text' id='res-mac-$id' class='form-control' value='$rmac' placeholder='aa:bb:cc:dd:ee:ff'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{fixed_ipaddr}</label>";
    $h[] = "      <input type='text' id='res-ip-$id' class='form-control' value='$rip' placeholder='192.168.1.50'>";
    $h[] = "    </div>";
    $h[] = "  </div>";

    // Scope dropdown
    $h[] = "  <div class='row'>";
    $h[] = "    <div class='col-md-8'>";
    $h[] = "      <label>{scope} <small class='text-muted'>{optional}</small></label>";
    $h[] = "      <select id='res-scope-$id' class='form-control'>";
    $h[] = "        <option value=''>{none}</option>";
    foreach ($scopes as $sc) {
        if (!is_object($sc)) continue;
        $sid  = htmlspecialchars($sc->id ?? '');
        $snet = htmlspecialchars(($sc->subnet ?? '') . '/' . ($sc->netmask ?? ''));
        $sel  = ($rscope === $sid) ? " selected" : "";
        $h[] = "        <option value='$sid'$sel>$sid ($snet)</option>";
    }
    $h[] = "      </select>";
    $h[] = "    </div>";
    $h[] = "  </div>";

    // ── Per-host DHCP Options ──
    $h[] = "  <div class='row' style='margin-top:15px;margin-bottom:10px'>";
    $h[] = "    <div class='col-md-12'><label><i class='fas fa-sliders-h'></i>&nbsp; {dhcp_options} <small class='text-muted'>({optional})</small></label></div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row' style='margin-bottom:8px'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>option routers <small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='res-routers-$id' class='form-control' value='$routers' placeholder='192.168.1.1'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>option domain-name-servers <small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='res-dns-$id' class='form-control' value='$rdns' placeholder='8.8.8.8'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row' style='margin-bottom:8px'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>option ntp-servers <small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='res-ntp-$id' class='form-control' value='$rntp' placeholder='192.168.1.1'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>option time-servers <small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='res-time-$id' class='form-control' value='$rtime' placeholder='192.168.1.1'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>next-server <small class='text-muted'>PXE/TFTP</small></label>";
    $h[] = "      <input type='text' id='res-nextserver-$id' class='form-control' value='$rnext_server' placeholder='192.168.1.10'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>filename <small class='text-muted'>PXE boot file</small></label>";
    $h[] = "      <input type='text' id='res-filename-$id' class='form-control' value='$rfilename' placeholder='pxelinux.0'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  </div></div>";

    // ── Save button ──
    $h[] = "<div class='text-right' style='margin-bottom:20px'>";
    $h[] = "  <button class='btn btn-primary btn-lg' onclick='ResSave_$id()'>";
    $h[] = "    <i class='fas fa-save'></i> {save}";
    $h[] = "  </button>";
    $h[] = "</div>";

    $h[] = res_form_js_block($id, $page, $isEdit ? $res_id : '');

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

function res_save(): void {
    $tpl      = new template_admin();
    $id       = aid();
    $existing = trim($_POST["existing_res_id"] ?? '');

    $rid         = trim($_POST["res_id"]      ?? '');
    $rname       = trim($_POST["res_name"]    ?? '');
    $rmac        = trim($_POST["res_mac"]     ?? '');
    $rip         = trim($_POST["res_ip"]      ?? '');
    $rscope      = trim($_POST["res_scope"]   ?? '');
    $routers     = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $_POST["res_routers"]      ?? ''))));
    $dnss        = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $_POST["res_dns_servers"]  ?? ''))));
    $ntps        = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $_POST["res_ntp_servers"]  ?? ''))));
    $times       = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $_POST["res_time_servers"] ?? ''))));
    $next_server = trim($_POST["res_next_server"] ?? '');
    $filename    = trim($_POST["res_filename"]    ?? '');

    if ($rid === '') {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{reservation_id_required}"));
        return;
    }
    // Validate MAC format (client-side already validates, this is server defence)
    if (!preg_match('/^([0-9a-fA-F]{2}:){5}[0-9a-fA-F]{2}$/', $rmac)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{invalid_mac_address}"));
        return;
    }
    if ($rip === '') {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{fixed_ipaddr} {required}"));
        return;
    }

    $options = [];
    if (!empty($routers))    $options[] = ["name" => "routers",             "type" => "ip-addresses", "value" => $routers];
    if (!empty($dnss))       $options[] = ["name" => "domain-name-servers", "type" => "ip-addresses", "value" => $dnss];
    if (!empty($ntps))       $options[] = ["name" => "ntp-servers",         "type" => "ip-addresses", "value" => $ntps];
    if (!empty($times))      $options[] = ["name" => "time-servers",        "type" => "ip-addresses", "value" => $times];
    if ($next_server !== '') $options[] = ["name" => "next-server",         "type" => "ip-address",   "value" => $next_server];
    if ($filename !== '')    $options[] = ["name" => "filename",            "type" => "text",          "value" => $filename];

    $data = [
        "id"       => $rid,
        "name"     => $rname !== '' ? $rname : $rid,
        "mac"      => strtolower($rmac),
        "fixed_ip" => $rip,
        "options"  => $options,
    ];
    if ($rscope !== '') $data["scope_id"] = $rscope;

    $endpoint = $existing !== ''
        ? "/netagents/dhcp/$id/reservations/" . urlencode($existing)
        : "/netagents/dhcp/$id/reservations";

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON($endpoint, $data));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    if (isset($json->Status) && !$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->Error ?? "{error}")));
        return;
    }
    echo $tpl->_ENGINE_parse_body(
        $tpl->div_success("<i class='fas fa-check-circle'></i> {reservation_saved}")
    );
    admin_tracks("Netagent #$id: DHCP reservation '$rid' " . ($existing ? "updated" : "added"));
}

function res_delete(): void {
    $tpl    = new template_admin();
    $id     = aid();
    $res_id = trim($_POST["delete-res"] ?? '');

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE(
        "/netagents/dhcp/$id/reservations/" . urlencode($res_id)
    ));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    if (isset($json->Status) && !$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->Error ?? "{error}")));
        return;
    }
    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {deleted}"));
    admin_tracks("Netagent #$id: DHCP reservation '$res_id' deleted");
}

// ── JavaScript block ──────────────────────────────────────────────────────────

function res_form_js_block(int $id, string $page, string $existingId): string {
    $macRegex = "^([0-9a-fA-F]{2}:){5}[0-9a-fA-F]{2}$";
    $l   = [];
    $l[] = "<script>";
    $l[] = "function ResSave_$id(){";
    $l[] = "  var rid=\$.trim(\$('#res-id-$id').val());";
    $l[] = "  var mac=\$.trim(\$('#res-mac-$id').val());";
    $l[] = "  var ip=\$.trim(\$('#res-ip-$id').val());";
    $l[] = "  var macRe=/$macRegex/;";
    $l[] = "  if(!rid){ alert('{reservation_id_required}'); return; }";
    $l[] = "  if(!macRe.test(mac)){ alert('{invalid_mac_address}'); return; }";
    $l[] = "  if(!ip){ alert('{fixed_ip_required}'); return; }";
    $l[] = "  \$('#res-form-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i></div>');";
    $l[] = "  \$.post('$page',{";
    $l[] = "    'save-res':1, id:$id,";
    $l[] = "    existing_res_id:'" . addslashes($existingId) . "',";
    $l[] = "    res_id:rid,";
    $l[] = "    res_name:\$.trim(\$('#res-name-$id').val()),";
    $l[] = "    res_mac:mac,";
    $l[] = "    res_ip:ip,";
    $l[] = "    res_scope:\$('#res-scope-$id').val(),";
    $l[] = "    res_routers:\$.trim(\$('#res-routers-$id').val()),";
    $l[] = "    res_dns_servers:\$.trim(\$('#res-dns-$id').val()),";
    $l[] = "    res_ntp_servers:\$.trim(\$('#res-ntp-$id').val()),";
    $l[] = "    res_time_servers:\$.trim(\$('#res-time-$id').val()),";
    $l[] = "    res_next_server:\$.trim(\$('#res-nextserver-$id').val()),";
    $l[] = "    res_filename:\$.trim(\$('#res-filename-$id').val())";
    $l[] = "  },function(r){";
    $l[] = "    \$('#res-form-result-$id').html(r);";
    $l[] = "    dialogInstance2.close();";
    $l[] = "  });";
    $l[] = "}";
    $l[] = "</script>";
    return implode("\n", $l);
}
