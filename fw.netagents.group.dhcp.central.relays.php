<?php
/**
 * Central DHCP relay management (cluster_mode).
 * CRUD operations on the management server's central config JSON.
 *
 * Entry:  LoadAjax('div','fw.netagents.group.dhcp.central.relays.php?id={group_id}')
 * API:    GET    /netagents/dhcp/group/{id}/central/relays
 *         POST   /netagents/dhcp/group/{id}/central/relays          — add
 *         DELETE /netagents/dhcp/group/{id}/central/relays/{rid}    — delete
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }

if (isset($_GET["relay-add-js"]))   { crelay_add_js(); exit; }
if (isset($_GET["relay-form"]))     { crelay_form();   exit; }
if (isset($_POST["save-relay"]))    { crelay_save();   exit; }
if (isset($_POST["delete-relay"]))  { crelay_delete(); exit; }
crelays_page();

// ── Helpers ───────────────────────────────────────────────────────────────────

function gid(): int {
    return intval($_GET["id"] ?? $_POST["id"] ?? $_GET["relay-add-js"] ?? 0);
}

// ── Main page: list relays ───────────────────────────────────────────────────

function crelays_page(): void {
    $tpl  = new template_admin();
    $id   = gid();
    $page = CurrentPageName();

    $relays = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/group/$id/central/relays"));
    if (!is_array($relays)) { $relays = []; }

    $h   = [];
    $h[] = "<div id='crelays-msg-$id' style='margin-bottom:10px'></div>";

    $h[] = "<div style='margin-bottom:15px'>";
    $h[] = "  <a href='#' onclick=\"Loadjs('$page?relay-add-js=$id');return false;\" class='btn btn-primary'>";
    $h[] = "    <i class='fas fa-plus'></i> {add_relay}";
    $h[] = "  </a>";
    $h[] = "</div>";

    if (empty($relays)) {
        $h[] = "<div class='alert alert-info'><i class='fas fa-info-circle'></i> {no_relays_defined}</div>";
    } else {
        $t = time();
        $h[] = "<table id='tbl-cr-$t' class='footable table table-stripped' data-page-size='50'>";
        $h[] = "<thead><tr>";
        $h[] = "  <th data-sortable=true data-type='text'>ID</th>";
        $h[] = "  <th data-sortable=true data-type='text'>{class_name}</th>";
        $h[] = "  <th data-sortable=true data-type='text'>{description}</th>";
        $h[] = "  <th>{circuit_id}</th>";
        $h[] = "  <th>{remote_id}</th>";
        $h[] = "  <th>{actions}</th>";
        $h[] = "</tr></thead><tbody>";

        $TRCLASS = null;
        foreach ($relays as $r) {
            if (!is_object($r)) continue;
            if ($TRCLASS === 'footable-odd') { $TRCLASS = null; } else { $TRCLASS = 'footable-odd'; }
            $rid       = htmlspecialchars($r->id ?? '');
            $className = htmlspecialchars($r->class_name ?? '');
            $desc      = htmlspecialchars($r->description ?? '');
            $match     = $r->match ?? null;
            $circuitId = is_object($match) ? htmlspecialchars($match->circuit_id ?? '') : '';
            $remoteId  = is_object($match) ? htmlspecialchars($match->remote_id ?? '') : '';

            $ridAttr = htmlspecialchars(json_encode($rid), ENT_QUOTES);
            $delJs = "CrelayDelete_$id($ridAttr)";

            $h[] = "<tr class='$TRCLASS'>";
            $h[] = "  <td><span style='font-family:monospace'>$rid</span></td>";
            $h[] = "  <td>$className</td>";
            $h[] = "  <td>$desc</td>";
            $h[] = "  <td><span style='font-family:monospace'>$circuitId</span></td>";
            $h[] = "  <td><span style='font-family:monospace'>$remoteId</span></td>";
            $h[] = "  <td>";
            $h[] = "    <a href='#' onclick=\"$delJs;return false;\" class='btn btn-xs btn-danger'><i class='fas fa-trash'></i></a>";
            $h[] = "  </td>";
            $h[] = "</tr>";
        }
        $h[] = "</tbody>";
        $h[] = "<tfoot><tr><td colspan='6'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
        $h[] = "</table>";
        $h[] = "<script>NoSpinner();\n" . implode("\n", $tpl->ICON_SCRIPTS);
        $h[] = "$(document).ready(function(){ $('#tbl-cr-$t').footable({";
        $h[] = "  \"filtering\":{\"enabled\":true},\"sorting\":{\"enabled\":true},\"paging\":{\"size\":50}";
        $h[] = "}); });</script>";
    }

    // Delete JS
    $h[] = "<script>";
    $h[] = "function CrelayDelete_$id(rid){";
    $h[] = "  swal({title:'{confirm_delete}',text:'Relay '+rid,type:'warning',showCancelButton:true,";
    $h[] = "    confirmButtonColor:'#ed5565',confirmButtonText:'{delete}',cancelButtonText:'{cancel}'";
    $h[] = "  },function(ok){ if(!ok) return;";
    $h[] = "    $.post('$page',{'delete-relay':1,id:$id,rid:rid},function(r){";
    $h[] = "      try{r=JSON.parse(r);}catch(e){}";
    $h[] = "      if(r&&r.Status===false){swal('{error}',r.Error||'{error}','error');return;}";
    $h[] = "      LoadAjax('crelays-container-$id','$page?id=$id');";
    $h[] = "    });";
    $h[] = "  });";
    $h[] = "}";
    $h[] = "</script>";
    $h[] = "<div id='crelays-container-$id'></div>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Dialog opener ─────────────────────────────────────────────────────────────

function crelay_add_js(): bool {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["relay-add-js"]);
    return $tpl->js_dialog1("{add_relay}", "$page?id=$id&relay-form=1", 600);
}

// ── Add form ──────────────────────────────────────────────────────────────────

function crelay_form(): void {
    $tpl  = new template_admin();
    $id   = gid();
    $page = CurrentPageName();

    $h = [];
    $h[] = "<div id='crelay-form-msg-$id'></div>";

    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-route'></i>&nbsp; {relay_class}</h5></div>";
    $h[] = "  <div class='ibox-content'><div class='row'>";
    $h[] = "    <div class='col-md-2'>";
    $h[] = "      <label>ID</label>";
    $h[] = "      <input type='number' id='crelay-id-$id' class='form-control' min='1' placeholder='1'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>{class_name} <span class='text-danger'>*</span></label>";
    $h[] = "      <input type='text' id='crelay-classname-$id' class='form-control' placeholder='relay-branch-1'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-3'>";
    $h[] = "      <label>{description} <small class='text-muted'>({optional})</small></label>";
    $h[] = "      <input type='text' id='crelay-desc-$id' class='form-control'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row' style='margin-top:10px'>";
    $h[] = "    <div class='col-md-5'>";
    $h[] = "      <label>{circuit_id} <small class='text-muted'>Option-82</small></label>";
    $h[] = "      <input type='text' id='crelay-circuit-$id' class='form-control' placeholder='0006aabbccdd'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-5'>";
    $h[] = "      <label>{remote_id} <small class='text-muted'>Option-82</small></label>";
    $h[] = "      <input type='text' id='crelay-remote-$id' class='form-control' placeholder='branch-switch-1'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-2' style='padding-top:25px'>";
    $h[] = "      <small class='text-muted'>{at_least_one_match_required}</small>";
    $h[] = "    </div>";
    $h[] = "  </div></div></div>";

    $h[] = "<div class='text-right' style='margin-bottom:20px'>";
    $h[] = "  <button class='btn btn-success btn-lg' onclick='CrelaySave_$id()'>";
    $h[] = "    <i class='fas fa-save'></i> {add}";
    $h[] = "  </button>";
    $h[] = "</div>";

    $h[] = "<script>";
    $h[] = "function CrelaySave_$id(){";
    $h[] = "  var rid=parseInt($('#crelay-id-$id').val(),10);";
    $h[] = "  if(isNaN(rid)||rid<1){ swal('{error}','{relay_id_required}','warning'); return; }";
    $h[] = "  var cn=$('#crelay-classname-$id').val().trim();";
    $h[] = "  if(!cn){ swal('{error}','{class_name_required}','warning'); return; }";
    $h[] = "  var cid=$('#crelay-circuit-$id').val().trim();";
    $h[] = "  var rem=$('#crelay-remote-$id').val().trim();";
    $h[] = "  if(!cid&&!rem){ swal('{error}','{at_least_one_match_required}','warning'); return; }";
    $h[] = "  var match={};";
    $h[] = "  if(cid) match.circuit_id=cid;";
    $h[] = "  if(rem) match.remote_id=rem;";
    $h[] = "  var relay={id:rid,class_name:cn,description:$('#crelay-desc-$id').val().trim(),match:match};";
    $h[] = "  $.post('$page',{'save-relay':1,id:$id,relay_json:JSON.stringify(relay)},function(r){";
    $h[] = "    try{r=JSON.parse(r);}catch(e){}";
    $h[] = "    if(r&&r.Status===false){swal('{error}',r.Error||'{error}','error');return;}";
    $h[] = "    dialogInstance1.close();";
    $h[] = "    LoadAjax('crelays-container-$id','$page?id=$id');";
    $h[] = "  });";
    $h[] = "}";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── POST handlers ─────────────────────────────────────────────────────────────

function crelay_save(): void {
    $id = intval($_POST["id"] ?? 0);
    $data = json_decode($_POST["relay_json"] ?? '{}');
    if (!is_object($data)) {
        echo json_encode(["Status" => false, "Error" => "Invalid JSON"]);
        return;
    }
    echo $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/dhcp/group/$id/central/relays", $data
    );
}

function crelay_delete(): void {
    $id  = intval($_POST["id"] ?? 0);
    $rid = urlencode($_POST["rid"] ?? '');
    echo $GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE(
        "/netagents/dhcp/group/$id/central/relays/$rid"
    );
}
