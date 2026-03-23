<?php
/**
 * Central DHCP reservation management (cluster_mode).
 * CRUD operations on the management server's central config JSON.
 *
 * Entry:  LoadAjax('div','fw.netagents.group.dhcp.central.reservations.php?id={group_id}')
 * API:    GET    /netagents/dhcp/group/{id}/central/reservations
 *         POST   /netagents/dhcp/group/{id}/central/reservations          — add
 *         POST   /netagents/dhcp/group/{id}/central/reservations/{rid}    — update
 *         DELETE /netagents/dhcp/group/{id}/central/reservations/{rid}    — delete
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }

if (isset($_GET["res-add-js"]))    { cres_add_js();    exit; }
if (isset($_GET["res-form"]))      { cres_form();      exit; }
if (isset($_GET["res-edit-js"]))   { cres_edit_js();   exit; }
if (isset($_GET["res-edit-form"])) { cres_edit_form();  exit; }
if (isset($_GET["res-table"])) { cres_page();  exit; }
if (isset($_GET["res-buttons"])) { cres_buttons();  exit; }
if (isset($_POST["save-res"]))     { cres_save();      exit; }
if (isset($_POST["edit-res"]))     { cres_edit_save(); exit; }
if (isset($_POST["delete-res"]))   { cres_delete();    exit; }
cres_head();

// ── Helpers ───────────────────────────────────────────────────────────────────

function gid(): int {
    return intval($_GET["id"] ?? $_POST["id"]
        ?? $_GET["res-add-js"] ?? $_GET["res-edit-js"] ?? 0);
}

// ── Main page: list reservations ─────────────────────────────────────────────
function cres_head():bool{
    $tpl  = new template_admin();
    $id   = gid();
    $page = CurrentPageName();
    $h[] = "<div id='cres-buttons-$id' style='margin-top:10px'></div>";
    $h[] = "<div id='cres-msg-$id' style='margin-bottom:10px'></div>";
    $h[] = $tpl->search_block($page,"","","","&res-table=yes&id=$id");
    echo $tpl->_ENGINE_parse_body($h);
    return true;
}
function cres_buttons():bool{
    $tpl  = new template_admin();
    $id   = gid();
    $page = CurrentPageName();
    $function=$_GET["function"];
    $topbuttons[] = array("Loadjs('$page?res-add-js=$id&function=$function');", ico_plus, "{add_reservation}");
    echo $tpl->table_buttons($topbuttons);
    return true;

}
function cres_page(): bool {
    $tpl  = new template_admin();
    $id   = gid();
    $page = CurrentPageName();
    $function=$_GET["function"];
    $reservations = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/group/$id/central/reservations"));
    if (!is_array($reservations)) { $reservations = []; }

    $h   = [];




    if (empty($reservations)) {
        $h[] = "<div class='alert alert-info'><i class='fas fa-info-circle'></i> {no_reservations}</div>";
        $h[] = "<script>LoadAjax('cres-buttons-$id','$page?res-buttons=yes&id=$id&function=$function');</script>";
        echo $tpl->_ENGINE_parse_body($h);
        return true;
    }
        $t = time();
        $h[] = "<table id='tbl-cres-$t' class='table table-stripped' data-page-size='50'>";
        $h[] = "<thead><tr>";
        $h[] = "  <th data-sortable=true data-type='text'>ID</th>";
        $h[] = "  <th data-sortable=true data-type='text'>{name}</th>";
        $h[] = "  <th data-sortable=true data-type='text'>MAC</th>";
        $h[] = "  <th data-sortable=true data-type='text'>{fixed_ipaddr}</th>";
        $h[] = "  <th>{actions}</th>";
        $h[] = "</tr></thead><tbody>";

        $TRCLASS = null;
        foreach ($reservations as $r) {
            if (!is_object($r)) continue;
            if ($TRCLASS === 'footable-odd') { $TRCLASS = null; } else { $TRCLASS = 'footable-odd'; }
            $rid     = htmlspecialchars($r->id ?? '');
            $name    = htmlspecialchars($r->name ?? '');
            $mac     = htmlspecialchars($r->hardware_address ?? '');
            $fixedIp = htmlspecialchars($r->fixed_ip ?? '');
            $ridAttr = htmlspecialchars(json_encode($rid), ENT_QUOTES);
            $editJs = "Loadjs('$page?res-edit-js=$id&rid='+encodeURIComponent($ridAttr))";
            $delJs  = "CresDelete_$id($ridAttr)";

            $h[] = "<tr class='$TRCLASS'>";
            $h[] = "  <td><span style='font-family:monospace'>$rid</span></td>";
            $h[] = "  <td>$name</td>";
            $h[] = "  <td><span style='font-family:monospace'>$mac</span></td>";
            $h[] = "  <td>$fixedIp</td>";
            $h[] = "  <td>";
            $h[] = "    <a href='#' onclick=\"$editJs;return false;\" class='btn btn-xs btn-primary'><i class='fas fa-edit'></i></a>";
            $h[] = "    <a href='#' onclick=\"$delJs;return false;\" class='btn btn-xs btn-danger'><i class='fas fa-trash'></i></a>";
            $h[] = "  </td>";
            $h[] = "</tr>";
        }
        $h[] = "</tbody>";
        $h[] = "<tfoot><tr><td colspan='6'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
        $h[] = "</table>";
        $h[] = "<script>NoSpinner();\n" . implode("\n", $tpl->ICON_SCRIPTS);
        $h[]="  LoadAjax('cres-buttons-$id','$page?res-buttons=yes&id=$id&function=$function');";
        $h[] = "</script>";


    // Delete JS
    $h[] = "<script>";
    $h[] = "function CresDelete_$id(rid){";
    $h[] = "  swal({title:'{confirm_delete}',text:rid,type:'warning',showCancelButton:true,";
    $h[] = "    confirmButtonColor:'#ed5565',confirmButtonText:'{delete}',cancelButtonText:'{cancel}'";
    $h[] = "  },function(ok){ if(!ok) return;";
    $h[] = "    $.post('$page',{'delete-res':1,id:$id,rid:rid},function(r){";
    $h[] = "      try{r=JSON.parse(r);}catch(e){}";
    $h[] = "      if(r&&r.Status===false){swal('{error}',r.Error||'{error}','error');return;}";
    $h[] = "      LoadAjax('cres-container-$id','$page?id=$id');";
    $h[] = "    });";
    $h[] = "  });";
    $h[] = "}";

    $h[] = "</script>";
    $h[] = "<div id='cres-container-$id'></div>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    return true;
}

// ── Dialog openers ────────────────────────────────────────────────────────────

function cres_add_js(): bool {
    $function=$_GET["function"];
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["res-add-js"]);
    $extra = '';
    if (!empty($_GET["from_ip"]))       $extra .= "&from_ip="       . urlencode($_GET["from_ip"]);
    if (!empty($_GET["from_mac"]))      $extra .= "&from_mac="      . urlencode($_GET["from_mac"]);
    if (!empty($_GET["from_hostname"])) $extra .= "&from_hostname=" . urlencode($_GET["from_hostname"]);
    return $tpl->js_dialog2("{add_reservation}", "$page?id=$id&res-form=1$extra&function=$function", 650);
}

function cres_edit_js(): bool {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["res-edit-js"]);
    $rid  = urlencode($_GET["rid"] ?? '');
    return $tpl->js_dialog2("{edit_reservation}", "$page?id=$id&res-edit-form=1&rid=$rid", 650);
}

// ── Add form ──────────────────────────────────────────────────────────────────

function cres_form(): void {
    $tpl  = new template_admin();
    $id   = gid();
    $page = CurrentPageName();

    $h = cres_render_form($id, $page, null);
    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Edit form ─────────────────────────────────────────────────────────────────

function cres_edit_form(): void {
    $tpl  = new template_admin();
    $id   = gid();
    $page = CurrentPageName();
    $rid  = trim($_GET["rid"] ?? '');

    $reservations = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/group/$id/central/reservations"));
    $res = null;
    if (is_array($reservations)) {
        foreach ($reservations as $r) {
            if (is_object($r) && ($r->id ?? '') == $rid) {
                $res = $r;
                break;
            }
        }
    }
    if ($res === null) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{reservation_not_found}"));
        return;
    }

    $h = cres_render_form($id, $page, $res);
    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Shared form renderer ─────────────────────────────────────────────────────

function cres_render_form(int $id, string $page, ?object $res): array {
    $isEdit = ($res !== null);
    $function=$_GET["function"];
    $rid     = $isEdit ? ($res->id ?? '') : '';
    $name    = $isEdit ? ($res->name ?? '') : trim($_GET["from_hostname"] ?? '');
    $mac     = $isEdit ? ($res->hardware_address ?? '') : trim($_GET["from_mac"] ?? '');
    $fixedIp = $isEdit ? ($res->fixed_ip ?? '') : trim($_GET["from_ip"] ?? '');
    $scopeId = $isEdit ? ($res->scope_id ?? '') : '';

    // Load scopes for dropdown
    $scopes = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/group/$id/central/scopes"));
    if (!is_array($scopes)) { $scopes = []; }

    // Extract DHCP options if present
    $routers = $dns = $hostname = $nextSrv = $filename = '';
    if ($isEdit && is_array($res->options ?? null)) {
        foreach ($res->options as $opt) {
            if (!is_object($opt)) continue;
            $n = $opt->name ?? '';
            $v = $opt->value ?? '';
            if (is_array($v)) $v = implode(', ', $v);
            if ($n === 'routers')             $routers  = $v;
            if ($n === 'domain-name-servers') $dns      = $v;
            if ($n === 'host-name')           $hostname = $v;
            if ($n === 'next-server')         $nextSrv  = $v;
            if ($n === 'filename')            $filename = $v;
        }
    }

    $h = [];
    $h[] = "<div id='cres-form-msg-$id'></div>";

    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-bookmark'></i>&nbsp; {reservation_details}</h5></div>";
    $h[] = "  <div class='ibox-content'>";
    if (!$isEdit) {
        $rid="res-$id-".time();
        $h[] = "  <input type='hidden' id='cres-id-$id' value='" . htmlspecialchars($rid) . "'>";
    } else {
        $h[] = "  <input type='hidden' id='cres-id-$id' value='" . htmlspecialchars($rid) . "'>";
    }
    $h[] = "  <div class='row' style='margin-bottom:10px'>";
    $h[] = "    <div class='col-md-6'><label>{name}</label>";
    $h[] = "      <input type='text' id='cres-name-$id' class='form-control' value='" . htmlspecialchars($name) . "'></div>";
    $h[] = "    <div class='col-md-6'><label>MAC <span class='text-danger'>*</span></label>";
    $h[] = "      <input type='text' id='cres-mac-$id' class='form-control' placeholder='aa:bb:cc:dd:ee:ff' value='" . htmlspecialchars($mac) . "'></div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row' style='margin-bottom:10px'>";
    $h[] = "    <div class='col-md-6'><label>{fixed_ipaddr} <span class='text-danger'>*</span></label>";
    $h[] = "      <input type='text' id='cres-ip-$id' class='form-control' placeholder='192.168.1.50' value='" . htmlspecialchars($fixedIp) . "'></div>";
    $h[] = "    <div class='col-md-6'><label>{scope}</label>";
    $h[] = "      <select id='cres-scope-$id' class='form-control'>";
    $h[] = "        <option value=''>-- {none} --</option>";
    foreach ($scopes as $sc) {
        if (!is_object($sc)) continue;
        $scId   = htmlspecialchars($sc->id ?? '');
        $scNet  = htmlspecialchars(($sc->subnet ?? '') . '/' . ($sc->netmask ?? ''));
        $scSel  = ($scId === $scopeId) ? ' selected' : '';
        $h[] = "        <option value='$scId'$scSel>$scNet</option>";
    }
    $h[] = "      </select></div>";
    $h[] = "  </div>";
    $h[] = "  </div></div>";

    // Optional DHCP options
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-sliders-h'></i>&nbsp; {dhcp_options} <small class='text-muted'>({optional})</small></h5></div>";
    $h[] = "  <div class='ibox-content'>";
    $h[] = "  <div class='row' style='margin-bottom:10px'>";
    $h[] = "    <div class='col-md-6'><label>{gateway}&nbsp;<small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='cres-routers-$id' class='form-control' value='" . htmlspecialchars($routers) . "'></div>";
    $h[] = "    <div class='col-md-6'><label>{dns_servers}&nbsp;<small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='cres-dns-$id' class='form-control' value='" . htmlspecialchars($dns) . "'></div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row' style='margin-bottom:10px'>";
    $h[] = "    <div class='col-md-6'><label>{hostname}</label>";
    $h[] = "      <input type='text' id='cres-hostname-$id' class='form-control' value='" . htmlspecialchars($hostname) . "'></div>";
    $h[] = "    <div class='col-md-6'><label>{next-server} <small class='text-muted'>PXE/TFTP</small></label>";
    $h[] = "      <input type='text' id='cres-nextserver-$id' class='form-control' value='" . htmlspecialchars($nextSrv) . "'></div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row'>";
    $h[] = "    <div class='col-md-6'><label>{filename}</label>";
    $h[] = "      <input type='text' id='cres-filename-$id' class='form-control' value='" . htmlspecialchars($filename) . "'></div>";
    $h[] = "  </div>";
    $h[] = "  </div></div>";

    $btnLabel = $isEdit ? '{save}' : '{add}';
    $h[] = "<div class='text-right' style='margin-bottom:20px'>";
    $h[] = "  <button class='btn btn-success btn-lg' onclick='CresSave_$id()'>";
    $h[] = "    <i class='fas fa-save'></i> $btnLabel";
    $h[] = "  </button>";
    $h[] = "</div>";

    // JavaScript
    $h[] = "<script>";
    $h[] = "function CresSave_$id(){";
    $h[] = "  var rid=$('#cres-id-$id').val().trim();";
    $h[] = "  var mac=$('#cres-mac-$id').val().trim();";
    $h[] = "  var ip=$('#cres-ip-$id').val().trim();";
    $h[] = "  if(!rid){ swal('{error}','ID {required}','warning'); return; }";
    $h[] = "  if(!mac){ swal('{error}','MAC {required}','warning'); return; }";
    $h[] = "  if(!ip){ swal('{error}','{fixed_ipaddr} {required}','warning'); return; }";

    $h[] = "  var opts=[];";
    $h[] = "  var r=$('#cres-routers-$id').val().trim(); if(r) opts.push({name:'routers',type:'ip-addresses',value:r.split(/[\\s,]+/).filter(Boolean)});";
    $h[] = "  var d=$('#cres-dns-$id').val().trim(); if(d) opts.push({name:'domain-name-servers',type:'ip-addresses',value:d.split(/[\\s,]+/).filter(Boolean)});";
    $h[] = "  var hn=$('#cres-hostname-$id').val().trim(); if(hn) opts.push({name:'host-name',type:'text',value:hn});";
    $h[] = "  var ns=$('#cres-nextserver-$id').val().trim(); if(ns) opts.push({name:'next-server',type:'ip-address',value:ns});";
    $h[] = "  var fn=$('#cres-filename-$id').val().trim(); if(fn) opts.push({name:'filename',type:'text',value:fn});";

    $h[] = "  var res={id:rid,name:$('#cres-name-$id').val().trim(),";
    $h[] = "    hardware_address:mac,fixed_ip:ip,scope_id:$('#cres-scope-$id').val().trim()";
    $h[] = "  };";
    $h[] = "  if(opts.length) res.options=opts;";

    $postField = $isEdit ? 'edit-res' : 'save-res';
    $h[] = "  $.post('$page',{'$postField':1,id:$id,res_json:JSON.stringify(res)},function(r){";
    $h[] = "    try{r=JSON.parse(r);}catch(e){}";
    $h[] = "    if(r&&r.Status===false){swal('{error}',r.Error||'{error}','error');return;}";
    $h[] = "    dialogInstance2.close();";
    if(strlen($function)>1) {
        $h[] = "$function();";
    }
    $h[] = "  });";
    $h[] = "}";
    $h[] = "</script>";

    return $h;
}

// ── POST handlers ─────────────────────────────────────────────────────────────

function cres_save(): void {
    $id = intval($_POST["id"] ?? 0);
    $data = json_decode($_POST["res_json"] ?? '{}');
    if (!is_object($data)) {
        echo json_encode(["Status" => false, "Error" => "Invalid JSON"]);
        return;
    }
    echo $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/dhcp/group/$id/central/reservations", $data
    );
}

function cres_edit_save(): void {
    $id = intval($_POST["id"] ?? 0);
    $data = json_decode($_POST["res_json"] ?? '{}');
    if (!is_object($data)) {
        echo json_encode(["Status" => false, "Error" => "Invalid JSON"]);
        return;
    }
    $rid = urlencode($data->id ?? '');
    echo $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/dhcp/group/$id/central/reservations/$rid", $data
    );
}

function cres_delete(): void {
    $id  = intval($_POST["id"] ?? 0);
    $rid = urlencode($_POST["rid"] ?? '');
    echo $GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE(
        "/netagents/dhcp/group/$id/central/reservations/$rid"
    );
}
