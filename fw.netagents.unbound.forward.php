<?php
/**
 * Forward Zones CRUD for Unbound on a single remote agent.
 * Operates on the `forward_zones` array of the full UnboundConfig JSON blob.
 *
 * Entry:  LoadAjax('div','fw.netagents.unbound.forward.php?id={agent_id}')
 *         Loadjs('fw.netagents.unbound.forward.php?fwd-add-js={agent_id}')
 *         Loadjs('fw.netagents.unbound.forward.php?fwd-edit-js={agent_id}&fwd_id={n}')
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }
$users = new usersMenus();
if (!$users->AsSystemAdministrator) { exit(); }

if (isset($_GET["fwd-btn-js"]))  { fwd_btn_js();  exit; }
if (isset($_GET["fwd-add-js"]))  { fwd_add_js();  exit; }
if (isset($_GET["fwd-edit-js"])) { fwd_edit_js();  exit; }
if (isset($_GET["fwd-form"]))    { fwd_form();     exit; }
if (isset($_POST["save-fwd"]))   { fwd_save();     exit; }
if (isset($_POST["delete-fwd"])) { fwd_delete();   exit; }
if(isset($_GET["search"])){fwd_search();exit;}
forward_head();

// ── Helpers ──────────────────────────────────────────────────────────────────

function aid(): int {
    return intval($_GET["id"] ?? $_POST["id"] ?? 0);
}
function fwd_btn_js():bool{
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $function=$_GET["function"];
    $id   = aid();
    $topbuttons[]=array("Loadjs('$page?fwd-add-js=$id&function=$function')",ico_plus,"{add_forward_zone}");
    echo $tpl->th_buttons($topbuttons);
    return true;
}
function get_config(int $id) {
    return json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/unbound/config/$id"));
}

function save_config(int $id, $cfg): ?object {
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/unbound/config/$id", $cfg
    ));
    return is_object($json) ? $json : null;
}

function validate_config(int $id, $cfg): ?object {
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/unbound/config/validate/$id", $cfg
    ));
    return is_object($json) ? $json : null;
}

// ── Dialogs ──────────────────────────────────────────────────────────────────

function fwd_add_js(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["fwd-add-js"]);
    $function = $_GET["function"];
    $tpl->js_dialog2("{add_forward_zone}", "$page?fwd-form=yes&id=$id&function=$function", 750);
}

function fwd_edit_js(): void {
    $tpl   = new template_admin();
    $page  = CurrentPageName();
    $id    = intval($_GET["fwd-edit-js"]);
    $fwdId = intval($_GET["fwd_id"]);
    $tpl->js_dialog2("{edit_forward_zone}", "$page?fwd-form=yes&id=$id&fwd_id=$fwdId", 750);
}

// ── List ─────────────────────────────────────────────────────────────────────
function forward_head(){
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = aid();
    $t    = time();
    echo "<div id='foward-zone-btns-$id' style='margin-top:10px;margin-bottom:10px'></div>";
    echo $tpl->search_block($page,"","","","&id=$id&t=$t");
}
function fwd_search(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = aid();
    $t    = time();
    $function=$_GET["function"] ?? "";

    $cfg = get_config($id);
    if (!is_object($cfg) || (isset($cfg->Status) && !$cfg->Status)) {
        $err = is_object($cfg) ? htmlspecialchars($cfg->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }

    $zones = $cfg->forward_zones ?? [];

    $h = [];


    if (empty($zones)) {
        $h[] = $tpl->div_info("{no_forward_zones_defined}");
        $h[] = "<script>";
        $h[] ="LoadAjax('foward-zone-btns-$id','$page?fwd-btn-js=yes&id=$id&function=$function');";
        $h[] = "</script>";
        echo $tpl->_ENGINE_parse_body(implode("\n", $h));
        return;
    }

    $h[] = "<div id='fwd-result-$id' style='margin-bottom:10px'></div>";
    $h[] = "<table id='table-$t' class='table table-striped' data-page-size='50'>";
    $h[] = "<thead><tr>";
    $h[] = "  <th data-sortable='true' data-type='text'>{forward_zone_name}</th>";
    $h[] = "  <th data-sortable='true' data-type='text'>TLS</th>";
    $h[] = "  <th data-sortable='true' data-type='text'>{forward_zone_servers}</th>";
    $h[] = "  <th></th>";
    $h[] = "  <th></th>";
    $h[] = "</tr></thead><tbody>";

    $search=$_GET["search"] ??  "";
    $TRCLASS = null;
    foreach ($zones as $i => $fz) {
        if ($TRCLASS === "footable-odd") { $TRCLASS = null; } else { $TRCLASS = "footable-odd"; }

        $zone    = htmlspecialchars($fz->zone ?? '');
        $tls     = !empty($fz->tls);
        $servers = is_array($fz->servers ?? null) ? array_map('htmlspecialchars', $fz->servers) : [];
        if(strlen($search)>1){
            $search=str_replace("*",".*?",$search);
            if(!preg_match("#$search#i", serialize($fz))) {
                continue;
            }
        }
        $zoneTxt = $zone === '.' ? "<strong>.</strong> <span class='text-muted' style='font-size:11px'>({full_forwarder_note})</span>" : "<strong>$zone</strong>";
        $tlsBadge = $tls
            ? "<span class='badge' style='background:#1ab394;color:#fff'>TLS</span>"
            : "<span class='badge' style='background:#676a6c;color:#fff'>Plain</span>";

        $srvList = implode('', array_map(function($s) {
            return "<div style='margin-top:3px'><i class='".ico_server."'></i>&nbsp;$s</div>";
        }, $servers));

        $editJs   = "Loadjs('$page?fwd-edit-js=$id&fwd_id=$i')";
        $deleteJs = "FwdDelete_{$id}($i)";
        $editBtn  = $tpl->icon_edit_field($editJs, "AsSystemAdministrator");
        $delBtn   = $tpl->icon_delete($deleteJs, "AsSystemAdministrator");

        $h[] = "<tr class='$TRCLASS'>";
        $h[] = "  <td>$zoneTxt</td>";
        $h[] = "  <td style='width:1%' nowrap>$tlsBadge</td>";
        $h[] = "  <td>$srvList</td>";
        $h[] = "  <td style='width:1%'>$editBtn</td>";
        $h[] = "  <td style='width:1%'>$delBtn</td>";
        $h[] = "</tr>";
    }
    $h[] = "</tbody>";
    $h[] = "<tfoot><tr><td colspan='5'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $h[] = "</table>";
    $h[] = fwd_js_block($id, $page);
    $h[] = "<script>NoSpinner();\n" . implode("\n", $tpl->ICON_SCRIPTS);
    $h[] ="LoadAjax('foward-zone-btns-$id','$page?fwd-btn-js=yes&id=$id&function=$function');";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Form ─────────────────────────────────────────────────────────────────────

function fwd_form(): void {
    $function=$_GET["function"];
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = aid();
    $fwdId = isset($_GET["fwd_id"]) ? intval($_GET["fwd_id"]) : -1;

    $zone = '';
    $tls  = 0;
    $servers = [''];

    if ($fwdId >= 0) {
        $cfg = get_config($id);
        if (is_object($cfg) && !empty($cfg->forward_zones[$fwdId])) {
            $fz      = $cfg->forward_zones[$fwdId];
            $zone    = htmlspecialchars($fz->zone ?? '');
            $tls     = !empty($fz->tls) ? 1 : 0;
            $servers = is_array($fz->servers ?? null) ? $fz->servers : [''];
            if (empty($servers)) $servers = [''];
        }
    }

    $tlsChk = $tls ? 'checked' : '';

    $h = [];
    $h[] = "<div id='fwd-save-result-$id'></div>";
    $h[] = "<div class='form-group'><label>{forward_zone_name}</label>";
    $h[] = "  <input type='text' id='fwd-zone-$id' class='form-control' value='$zone' placeholder='. or corp.local'>";
    $h[] = "  <p class='help-block'>{full_forwarder_note}</p></div>";
    $h[] = "<div class='checkbox'><label>";
    $h[] = "  <input type='checkbox' id='fwd-tls-$id' $tlsChk> {forward_zone_tls}</label></div>";

    $h[] = "<div style='margin-top:15px'><label>{forward_zone_servers}</label>";
    $h[] = "<div id='fwd-servers-$id'>";
    foreach ($servers as $si => $srv) {
        $h[] = fwd_server_row($id, $si, htmlspecialchars($srv));
    }
    $h[] = "</div>";
    $h[] = "<button type='button' class='btn btn-default btn-sm' style='margin-top:5px' onclick='AddFwdServer_$id()'>";
    $h[] = "  <i class='fas fa-plus'></i> {add_server}</button>";
    $h[] = "</div>";

    $h[] = "<div style='margin-top:20px;text-align:right'>";
    $h[] = "  <button class='btn btn-primary' onclick='FwdSave_$id()'>";
    $h[] = "    <i class='fas fa-save'></i> {save}</button>";
    $h[] = "</div>";

    $h[] = "<script>";
    $h[] = "var _fwdSrvCount_$id=" . count($servers) . ";";
    $h[] = "function AddFwdServer_$id(){";
    $h[] = "  var idx=_fwdSrvCount_{$id}++;";
    $h[] = "  var row='<div class=\"input-group\" style=\"margin-top:5px\" id=\"fwd-srv-row-'+idx+'-$id\">';";
    $h[] = "  row+='<input type=\"text\" class=\"form-control fwd-srv-$id\" placeholder=\"IP address\">';";
    $h[] = "  row+='<span class=\"input-group-btn\"><button class=\"btn btn-danger\" type=\"button\" onclick=\"RemoveFwdServer_{$id}('+idx+')\"><i class=\"fas fa-times\"></i></button></span>';";
    $h[] = "  row+='</div>';";
    $h[] = "  \$('#fwd-servers-$id').append(row);";
    $h[] = "}";
    $h[] = "function RemoveFwdServer_$id(idx){";
    $h[] = "  \$('#fwd-srv-row-'+idx+'-$id').remove();";
    $h[] = "}";
    $h[] = "function FwdSave_$id(){";
    $h[] = "  var zone=\$.trim(\$('#fwd-zone-$id').val());";
    $h[] = "  if(!zone){ alert('{forward_zone_name}'); return; }";
    $h[] = "  var srvs=[];";
    $h[] = "  \$('.fwd-srv-$id').each(function(){ var v=\$.trim(\$(this).val()); if(v) srvs.push(v); });";
    $h[] = "  if(srvs.length===0){ alert('{forward_zone_servers}'); return; }";
    $h[] = "  \$('#fwd-save-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i></div>');";
    $h[] = "  \$.post('$page',{";
    $h[] = "    'save-fwd':1, id:$id, fwd_id:$fwdId,";
    $h[] = "    zone:zone,";
    $h[] = "    tls:\$('#fwd-tls-$id').is(':checked')?1:0,";
    $h[] = "    servers_json:JSON.stringify(srvs)";
    $h[] = "  },function(r){";
    $h[] = "    \$('#fwd-save-result-$id').html(r);";
    if($fwdId==-1){
        $h[] = "dialogInstance2.close();";
        $h[] = "$function();";
    }
    $h[] = "  });";
    $h[] = "}";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

function fwd_server_row(int $id, int $index, string $value): string {
    $h = [];
    $h[] = "<div class='input-group' style='margin-top:5px' id='fwd-srv-row-$index-$id'>";
    $h[] = "  <input type='text' class='form-control fwd-srv-$id' value='$value' placeholder='IP address'>";
    if ($index > 0) {
        $h[] = "  <span class='input-group-btn'><button class='btn btn-danger' type='button' onclick='RemoveFwdServer_{$id}($index)'><i class='fas fa-times'></i></button></span>";
    }
    $h[] = "</div>";
    return implode("\n", $h);
}

// ── Save ─────────────────────────────────────────────────────────────────────

function fwd_save(): void {
    $tpl   = new template_admin();
    $id    = aid();
    $fwdId = intval($_POST["fwd_id"] ?? -1);

    $cfg = get_config($id);
    if (!is_object($cfg) || (isset($cfg->Status) && !$cfg->Status)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $zones = isset($cfg->forward_zones) ? json_decode(json_encode($cfg->forward_zones), true) : [];

    $servers = json_decode($_POST["servers_json"] ?? '[]', true);
    if (!is_array($servers)) $servers = [];
    $servers = array_values(array_filter(array_map('trim', $servers)));

    $entry = [
        "zone"    => trim($_POST["zone"] ?? ''),
        "servers" => $servers,
        "tls"     => intval($_POST["tls"] ?? 0) === 1,
    ];

    if ($fwdId >= 0 && $fwdId < count($zones)) {
        $zones[$fwdId] = $entry;
    } else {
        $zones[] = $entry;
    }

    $cfgArr = json_decode(json_encode($cfg), true);
    $cfgArr["forward_zones"] = $zones;

    $vResult = validate_config($id, $cfgArr);
    if (is_object($vResult) && isset($vResult->valid) && !$vResult->valid) {
        $errs = implode("<br>", array_map('htmlspecialchars', $vResult->errors ?? ['{config_invalid}']));
        echo $tpl->_ENGINE_parse_body($tpl->div_error($errs));
        return;
    }

    $result = save_config($id, $cfgArr);
    if (!is_object($result) || (isset($result->Status) && !$result->Status)) {
        $err = is_object($result) ? htmlspecialchars($result->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }

    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {forward_zone_saved}"));
    admin_tracks("Netagent #$id: Forward zone saved ({$entry['zone']})");
}

// ── Delete ───────────────────────────────────────────────────────────────────

function fwd_delete(): void {
    $tpl   = new template_admin();
    $id    = aid();
    $fwdId = intval($_POST["delete-fwd"]);

    $cfg = get_config($id);
    if (!is_object($cfg) || (isset($cfg->Status) && !$cfg->Status)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $zones = isset($cfg->forward_zones) ? json_decode(json_encode($cfg->forward_zones), true) : [];
    if ($fwdId < 0 || $fwdId >= count($zones)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    array_splice($zones, $fwdId, 1);
    $cfgArr = json_decode(json_encode($cfg), true);
    $cfgArr["forward_zones"] = $zones;

    $result = save_config($id, $cfgArr);
    if (!is_object($result) || (isset($result->Status) && !$result->Status)) {
        $err = is_object($result) ? htmlspecialchars($result->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }

    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {forward_zone_deleted}"));
    admin_tracks("Netagent #$id: Forward zone #$fwdId deleted");
}

// ── JS block for list page ───────────────────────────────────────────────────

function fwd_js_block(int $id, string $page): string {
    $l = [];
    $l[] = "<script>";
    $l[] = "function FwdDelete_$id(idx){";
    $l[] = "  swal({title:'{forward_zone_deleted}?',type:'warning',showCancelButton:true,";
    $l[] = "    confirmButtonColor:'#ed5565',confirmButtonText:'{yes}',cancelButtonText:'{cancel}'},";
    $l[] = "  function(ok){ if(!ok) return;";
    $l[] = "    \$('#fwd-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i></div>');";
    $l[] = "    \$.post('$page',{'delete-fwd':idx,id:$id},function(r){";
    $l[] = "      \$('#fwd-result-$id').html(r);";
    $l[] = "      if(r.indexOf('alert-success')>-1){ setTimeout(function(){ LoadAjax(\$('#fwd-result-$id').closest('.tab-pane').attr('id'),'$page?id=$id'); },500); }";
    $l[] = "    });";
    $l[] = "  });";
    $l[] = "}";
    $l[] = "</script>";
    return implode("\n", $l);
}
