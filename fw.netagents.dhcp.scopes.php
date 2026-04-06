<?php
/**
 * DHCP Scopes and Option-82 Relay classes management for a single remote agent.
 * Relays are shown first since relay-type scopes reference a relay_id.
 * After every scope save the config is validated; changes must be applied
 * explicitly from the Config tab.
 *
 * Entry:  LoadAjax('div','fw.netagents.dhcp.scopes.php?id={agent_id}')     — full list
 *         Loadjs('fw.netagents.dhcp.scopes.php?scope-add-js={agent_id}')   — add dialog
 *         Loadjs('fw.netagents.dhcp.scopes.php?scope-edit-js={agent_id}&scope_id={s}') — edit dialog
 *         Loadjs('fw.netagents.dhcp.scopes.php?relay-add-js={agent_id}')   — add relay dialog
 * API:    GET    /netagents/dhcp/{id}/scopes
 *         POST   /netagents/dhcp/{id}/scopes            — add
 *         POST   /netagents/dhcp/{id}/scopes/{scope_id} — update
 *         DELETE /netagents/dhcp/{id}/scopes/{scope_id}
 *         GET    /netagents/dhcp/{id}/relays
 *         POST   /netagents/dhcp/{id}/relays            — add
 *         DELETE /netagents/dhcp/{id}/relays/{relay_id}
 *         POST   /netagents/dhcp/{id}/config/validate   — validate after save
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }
if(isset($_GET["scopes-boutons"])){scope_buttons();exit;}
if (isset($_GET["scope-add-js"]))   { scope_add_js();    exit; }
if (isset($_GET["scope-edit-js"]))  { scope_edit_js();   exit; }
if (isset($_GET["relay-add-js"]))   { relay_add_js();    exit; }
if (isset($_POST["save-scope"]))    { scope_save();      exit; }
if(isset($_GET["scope-delete-js"])){scope_delete_js();exit;}
if (isset($_POST["delete-scope"]))  { scope_delete();    exit; }
if(isset($_GET["scope-delete-all-js"])){scope_delete_all_js();exit;}
if(isset($_POST["delete-all-scopes"])){scope_delete_perform();exit;}
if (isset($_POST["save-relay"]))    { relay_save();      exit; }
if (isset($_POST["delete-relay"]))  { relay_delete();    exit; }
if (isset($_GET["scope-form"])) { scope_form(); exit; }
if (isset($_GET["relay-form"])) { relay_form(); exit; }
if(isset($_GET["scopes-table"])){ scopes_table();exit;}
scopes_list();

// ── Helpers ───────────────────────────────────────────────────────────────────

function aid(): int {
    return intval($_GET["id"] ?? $_POST["id"]
        ?? $_GET["scope-add-js"] ?? $_GET["scope-edit-js"] ?? $_GET["relay-add-js"] ?? 0);
}

function scope_options_to_form($options): array {
    $routers = []; $dns = []; $ntp = []; $time = [];
    $next_server = ''; $filename = ''; $rfc3442 = ''; $broadcast = '';
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
        if ($name === 'broadcast-address')   $broadcast   = is_string($val) ? $val : '';
        if ($name === 'rfc3442-classless-static-routes') {
            $rfc3442 = is_array($val) ? implode(', ', array_map('strval', $val)) : (is_string($val) ? $val : '');
        }
    }
    return [
        'routers'     => implode(', ', $routers),
        'dns'         => implode(', ', $dns),
        'ntp'         => implode(', ', $ntp),
        'time'        => implode(', ', $time),
        'next_server' => $next_server,
        'filename'    => $filename,
        'broadcast'   => $broadcast,
        'rfc3442'     => $rfc3442,
    ];
}

// ── Dispatcher actions ────────────────────────────────────────────────────────

/** Full scopes + relays list page. */
function scopes_list(): void {
    $tpl  = new template_admin();
    $id   = aid();
    $page = CurrentPageName();

    // ── Fetch relays (for relay-type scope display) ──
    $relays_json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/$id/relays"));
    $relays = [];
    if (is_array($relays_json)) $relays = $relays_json;
    elseif (is_object($relays_json) && isset($relays_json->relays)) $relays = (array)$relays_json->relays;

    // ── Fetch scopes ──


    $h   = [];
    $h[] = "<div id='scopes-boutons-$id' style='margin-top:10px;margin-bottom: 2px'></div>";
    $h[] = "<div id='scopes-result-$id' style='margin-bottom:10px'></div>";

    $h[] = "<div class='alert alert-info' style='margin-bottom:15px'>";
    $h[] = "  <i class='fas fa-network-wired'></i> <strong>{dhcp_scopes}</strong>";
    $h[] = "  <br><small class='text-muted'>{scope_explain}</small>";
    $h[] = "</div>";

    // ── Relays section ──────────────────────────────────────────────────────
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'>";
    $h[] = "    <h5><i class='fas fa-route'></i>&nbsp; {dhcp_relays} <small class='text-muted'>{option82_relay_classes}</small></h5>";
    $h[] = "  </div>";
    $h[] = "  <div class='ibox-content' style='padding:0'>";

    if (empty($relays)) {
        $h[] = "    <p style='padding:15px;color:#999'><i>{no_relays_defined}</i></p>";
    } else {
        $h[] = "    <table class='table table-striped table-condensed' style='margin:0'>";
        $h[] = "      <thead><th>{class_name}</th><th>{circuit_id}</th><th>{remote_id}</th><th style='width:1%'></th></tr></thead><tbody>";
        foreach ($relays as $rel) {
            if (!is_object($rel)) continue;
            $rid       = htmlspecialchars($rel->id ?? '');
            $rdesc     = htmlspecialchars($rel->description ?? '');
            $className = htmlspecialchars($rel->class_name ?? '—');
            $circuitId = htmlspecialchars($rel->match->circuit_id ?? '—');
            $remoteId  = htmlspecialchars($rel->match->remote_id  ?? '—');
            $label     = $rdesc !== '' ? " <small class='text-muted'>($rdesc)</small>" : '';
            $delJs = "if(confirm('{confirm_delete}')){";
            $delJs .= "\$.post('$page',{id:$id,'delete-relay':'$rid'},function(r){\$('#scopes-result-$id').html(r);LoadAjaxSilent('dhcp-scopes-div-$id','$page?id=$id');});}";
            $h[] = "      <tr>";
            $h[] = "        <td><code>$className</code>$label</td>";
            $h[] = "        <td><small>$circuitId</small></td>";
            $h[] = "        <td><small>$remoteId</small></td>";
            $h[] = "        <td>" . $tpl->icon_delete("$delJs", "AsSystemAdministrator") . "</td>";
            $h[] = "      </tr>";
        }
        $h[] = "      </tbody></table>";
    }
    $h[] = "  </div></div>";

    // ── Scopes section ──────────────────────────────────────────────────────
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'>";
    $h[] = "    <h5><i class='fas fa-network-wired'></i>&nbsp; {dhcp_scopes}</h5>";
    $h[] = "  </div>";
    $h[] = "  <div id='dhcp-scopes-div-$id' class='ibox-content' style='padding:0'>";
    $h[] = "  </div></div>";
    $h[]="<script>";
    $h[]="  LoadAjaxSilent('scopes-boutons-$id','$page?scopes-boutons=$id');";
    $h[]="  LoadAjaxSilent('dhcp-scopes-div-$id','$page?scopes-table=$id');";
    $h[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}
function scope_delete_js():bool{
    $agent_id=intval($_GET["scope-delete-js"]);
    $scope_id=$_GET["scope_id"];
    $subnet=$_GET["subnet"];
    $md=$_GET["md"];
    $tpl=new template_admin();
    return $tpl->js_confirm_delete($subnet, "delete-scope","$agent_id|$scope_id","$('$md').remove()");

}

function scope_delete_all_js():void{
    $id=intval($_GET["scope-delete-all-js"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $confirm_delete_all_scopes=$tpl->javascript_parse_text("{confirm}");
    $all_scopes_will_be_removed=$tpl->javascript_parse_text("{delete_all}: {scopes}");
    $delete_all=$tpl->_ENGINE_parse_body("{delete_all_scopes}");
    $cancel=$tpl->_ENGINE_parse_body("{cancel}");
    $js=[];
    $js[]="swal({";
    $js[]="  title:'$confirm_delete_all_scopes',";
    $js[]="  text:'$all_scopes_will_be_removed',";
    $js[]="  type:'warning',";
    $js[]="  showCancelButton:true,";
    $js[]="  confirmButtonColor:'#ed5565',";
    $js[]="  confirmButtonText:'$delete_all',";
    $js[]="  cancelButtonText:'$cancel'";
    $js[]="}, function(isConfirm){";
    $js[]="  if(!isConfirm) return;";
    $js[]="  \$.post('$page',{'delete-all-scopes':'$id'},function(r){";
    $js[]="    \$('#scopes-result-$id').html(r);";
    $js[]="    LoadAjaxSilent('dhcp-scopes-div-$id','$page?scopes-table=$id');";
    $js[]="  });";
    $js[]="});";
    echo implode("\n",$js);
}

function scope_delete_perform():bool{
    $tpl=new template_admin();
    $id=intval($_POST["delete-all-scopes"] ?? 0);
    if($id < 1){
        echo $tpl->_ENGINE_parse_body("{invalid_agent_id}");
        return false;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/netagents/dhcp/$id/scopes"));
    if(!is_object($json)){
        echo $tpl->_ENGINE_parse_body("{protocol_error}");
        return false;
    }
    $deleted=intval($json->deleted ?? 0);
    $errors=(array)($json->errors ?? []);
    if(!empty($errors)){
        echo $tpl->_ENGINE_parse_body("{deleted}: $deleted — {errors}: " . htmlspecialchars(implode(', ',$errors)));
        return false;
    }
    return admin_tracks("Netagent #$id: all DHCP scopes deleted ($deleted removed)");
}


function scopes_table():bool{
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id=intval($_GET["scopes-table"]);
    $scopes_json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/$id/scopes"));
    $scopes = [];
    if (is_array($scopes_json)) $scopes = $scopes_json;
    elseif (is_object($scopes_json) && isset($scopes_json->scopes)) $scopes = (array)$scopes_json->scopes;

    if (is_object($scopes_json) && isset($scopes_json->Status) && !$scopes_json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($scopes_json->Error ?? "{error}")));
        return false;
    }

    if (empty($scopes)) {
        $h[] = "    <p style='padding:15px;color:#999'><i>{no_scopes_defined}</i></p>";
    } else {
        $h[] = "    <table class='table table-striped table-condensed' style='margin:0'>";
        $h[] = "      <thead><tr>";
        $h[] = "        <th>{type}</th><th>{subnet}</th>";
        $h[] = "        <th></th><th>{pools}</th><th style='width:1%'></th>";
        $h[] = "      </tr></thead><tbody>";
        foreach ($scopes as $sc) {
            if (!is_object($sc)) continue;
            $sid      = htmlspecialchars($sc->id ?? '');
            $stype    = htmlspecialchars($sc->type ?? 'direct');
            $subnet   = htmlspecialchars(($sc->subnet ?? '') . '/' . ($sc->netmask ?? ''));
            $iface    = htmlspecialchars($sc->interface ?? '');
            $vlanId   = intval($sc->vlan_id ?? 0);
            $poolCnt  = count((array)($sc->pools ?? []));
            $typeBadge = $stype === 'relay'
                ? "<span class='badge' style='background:#f8ac59;color:#fff'>relay</span>"
                : "<span class='badge' style='background:#1ab394;color:#fff'>direct</span>";
            $editJs   = "Loadjs('$page?scope-edit-js=$id&scope_id=" . urlencode($sid) . "');";
            $subnetenc=urlencode($subnet);
            $delJs=$tpl->icon_delete("Loadjs('$page?scope-delete-js=$id&scope_id=" . urlencode($sid) . "&md=scope-$sid&subnet=$subnetenc');");
            $ifaceCell = $iface;
            $ifacido=ico_nic;
            $iface="";
            if(strlen($ifaceCell)>2){
                $iface="<i class='$ifacido'></i>&nbsp;";
            }
            if ($vlanId > 0) $ifaceCell .= " <span class='badge' style='background:#9b59b6;color:#fff'>VLAN $vlanId</span>";
            $h[] = "      <tr id='scope-$sid'>";
            $h[] = "        <td style='width:1%' nowrap>$typeBadge</td>";
            $h[] = "        <td style='width:99%'>$subnet</td>";
            $h[] = "        <td style='width:1%' nowrap>$iface$ifaceCell</td>";
            $h[] = "        <td style='width:1%' nowrap><span class='badge' style='background:#1c84c6;color:#fff'>$poolCnt</span></td>";
            $h[] = "        <td style='width:1%;white-space:nowrap'>";
            $h[] = "          " . $tpl->icon_edit_field($editJs, "AsSystemAdministrator");
            $h[] = "          " . $delJs;
            $h[] = "        </td>";
            $h[] = "      </tr>";
        }
        $h[] = "      </tbody></table>";
    }
    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    return true;
}

function scope_buttons():bool{
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id=intval($_GET["scopes-boutons"]);
    $topbuttons[] = array("Loadjs('$page?scope-add-js=$id');", ico_plus, "{add_scope}");
    $topbuttons[] = array("Loadjs('$page?relay-add-js=$id');", ico_plus, "{add_relay}");
    $topbuttons[] = array("Loadjs('$page?scope-delete-all-js=$id');", ico_trash, "{delete_all_scopes}");
    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}

/** Opens an add-scope dialog (820px). */
function scope_add_js(): bool {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["scope-add-js"]);
    return $tpl->js_dialog2("{add_scope}", "$page?id=$id&scope-form=1", 820);
}

/** Opens an edit-scope dialog pre-filled with existing scope data. */
function scope_edit_js(): bool {
    $tpl      = new template_admin();
    $page     = CurrentPageName();
    $id       = intval($_GET["scope-edit-js"]);
    $scope_id = urlencode($_GET["scope_id"] ?? '');
    return $tpl->js_dialog2("{edit_scope}", "$page?id=$id&scope-form=1&scope_id=$scope_id", 820);
}

/** Opens an add-relay dialog. */
function relay_add_js(): bool {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["relay-add-js"]);
    return $tpl->js_dialog1("{add_relay}", "$page?id=$id&relay-form=1", 600);
}

// ── Form renders (called via ?scope-form=1 / ?relay-form=1) ──────────────────



function scope_form(): void {
    $tpl      = new template_admin();
    $id       = aid();
    $page     = CurrentPageName();
    $scope_id = trim($_GET["scope_id"] ?? '');

    // Fetch network interfaces including VLAN sub-interfaces from the DHCP interfaces endpoint
    $ifaces     = [];  // array of stdClass {name, up, is_vlan, vlan_id, parent}
    $ifacesJSON = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/$id/interfaces"));
    if (is_array($ifacesJSON)) {
        foreach ($ifacesJSON as $iobj) {
            if (is_object($iobj) && !empty($iobj->name)) $ifaces[] = $iobj;
        }
    }

    // Fetch relays for relay_id dropdown
    $relays_json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/$id/relays"));
    $relays = [];
    if (is_array($relays_json)) $relays = $relays_json;
    elseif (is_object($relays_json) && isset($relays_json->relays)) $relays = (array)$relays_json->relays;

    // Existing scope data (if editing)
    $sc      = null;
    $sid     = '';
    $stype   = 'direct';
    $iface   = '';
    $vlan_id = 0;
    $subnet  = '';
    $netmask = '';
    $auth    = true;
    $ldef    = 28800;
    $lmax    = 28800;
    $routers     = '';
    $dns         = '';
    $ntp         = '';
    $time        = '';
    $next_server = '';
    $filename    = '';
    $rfc3442     = '';
    $broadcast   = '';
    $server_identifier  = '';
    $allow_unknown      = '';   // 'allow', 'deny', or '' (inherit)
    $always_broadcast   = false;
    $get_lease_hostnames= false;
    $ping_check         = false;
    $ddns_domain        = '';
    $relayId = 0;
    $pools   = [];

    if ($scope_id !== '') {
        // Agent only supports list endpoint — find scope by ID from the list
        $allJSON = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/$id/scopes"));
        $scList  = [];
        if (is_array($allJSON)) $scList = $allJSON;
        elseif (is_object($allJSON) && isset($allJSON->scopes)) $scList = (array)$allJSON->scopes;
        foreach ($scList as $candidate) {
            if (is_object($candidate) && ($candidate->id ?? '') === $scope_id) {
                $sc = $candidate;
                break;
            }
        }
        if ($sc !== null) {
            $sid     = htmlspecialchars($sc->id ?? '');
            $stype   = htmlspecialchars($sc->type ?? 'direct');
            $iface   = htmlspecialchars($sc->interface ?? '');
            $vlan_id = intval($sc->vlan_id ?? 0);
            $subnet  = htmlspecialchars($sc->subnet  ?? '');
            $netmask = htmlspecialchars($sc->netmask  ?? '');
            $auth    = !isset($sc->authoritative) || $sc->authoritative=false;
            $ldef    = intval($sc->lease->default ?? 28800);
            $lmax    = intval($sc->lease->max     ?? 28800);
            $relayId = intval($sc->relay->relay_id ?? 0);
            $server_identifier = htmlspecialchars($sc->server_identifier ?? '');
            $optArr      = scope_options_to_form($sc->options ?? []);
            $routers     = htmlspecialchars($optArr['routers']);
            $dns         = htmlspecialchars($optArr['dns']);
            $ntp         = htmlspecialchars($optArr['ntp']);
            $time        = htmlspecialchars($optArr['time']);
            $next_server = htmlspecialchars($optArr['next_server']);
            $filename    = htmlspecialchars($optArr['filename']);
            $broadcast   = htmlspecialchars($optArr['broadcast']);
            $rfc3442     = htmlspecialchars($optArr['rfc3442']);
            $sflags      = (array)($sc->flags ?? []);
            if (isset($sflags["allow-unknown-clients"])) $allow_unknown = $sflags["allow-unknown-clients"] ? "allow" : "deny";
            $always_broadcast    = !empty($sflags["always-broadcast"]);
            $get_lease_hostnames = !empty($sflags["get-lease-hostnames"]);
            $ping_check          = !empty($sflags["ping-check"]);
            $ddns_domain         = htmlspecialchars($sflags["ddns-domainname"] ?? '');
            $pools   = (array)($sc->pools ?? []);
        }
    }

    $isEdit = ($scope_id !== '');



    $h   = [];
    $h[] = "<div id='scope-form-result-$id'></div>";
    $h[] = "<input type='hidden' id='scope-existing-id-$id' value='" . htmlspecialchars($scope_id) . "'>";

    // ── Identity & Type ──

    if($scope_id==""){
        $scope_id=md5(uniqid(rand(), true));
    }
    $h[] = "<input type='hidden' id='scope-id-$id' value='$scope_id'>";
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-id-card'></i>&nbsp; {scope_identity}</h5></div>";
    $h[] = "  <div class='ibox-content'><div class='row'>";
    $h[] = "    <div class='col-md-3'>";
    $h[] = "      <label>{type}</label>";
    $h[] = "      <select id='scope-type-$id' class='form-control' onchange='ScopeTypeChange_$id()'>";
    $h[] = "        <option value='direct'" . ($stype === 'direct' ? " selected" : "") . ">direct</option>";
    $h[] = "        <option value='relay'"  . ($stype === 'relay'  ? " selected" : "") . ">relay</option>";
    $h[] = "      </select>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-3'>";
    $h[] = "      <label>{interface} <span class='text-danger'>*</span></label>";
    $h[] = "      <select id='scope-iface-$id' class='form-control' onchange='ScopeIfaceChange_$id(this)'>";
    $h[] = "        <option value=''>{select_interface}</option>";
    $ifaceNames = [];
    foreach ($ifaces as $ifaceObj) {
        $ifname  = htmlspecialchars($ifaceObj->name);
        $vid     = intval($ifaceObj->vlan_id ?? 0);
        $isVlan  = !empty($ifaceObj->is_vlan);
        $label   = $ifname . ($isVlan ? " [VLAN $vid]" : '') . (empty($ifaceObj->up) ? ' (down)' : '');
        $sel     = ($ifname === $iface) ? " selected" : "";
        $h[] = "        <option value='$ifname' data-vlan='$vid'$sel>$label</option>";
        $ifaceNames[] = $ifname;
    }
    if ($iface !== '' && !in_array($iface, $ifaceNames)) {
        // Preserve existing value even if not in current list
        $h[] = "        <option value='$iface' data-vlan='$vlan_id' selected>$iface" . ($vlan_id > 0 ? " [VLAN $vlan_id]" : '') . "</option>";
    }
    $h[] = "      </select>";
    $h[] = "      <input type='hidden' id='scope-vlan-id-$id' value='$vlan_id'>";
    $h[] = "    </div>";
    $h[] = "  </div></div></div>";

    // ── Relay selector (visible only for relay type) ──
    $relayDisplay = $stype === 'relay' ? '' : 'display:none';
    $h[] = "<div id='scope-relay-row-$id' class='ibox' style='$relayDisplay'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-route'></i>&nbsp; {relay_class}</h5></div>";
    $h[] = "  <div class='ibox-content'>";
    $h[] = "    <select id='scope-relay-id-$id' class='form-control' style='max-width:400px'>";
    $h[] = "      <option value='0'>{select_relay}</option>";
    foreach ($relays as $rel) {
        if (!is_object($rel)) continue;
        $rid       = htmlspecialchars($rel->id ?? '');
        $className = htmlspecialchars($rel->class_name ?? $rid);
        $rdesc     = ($rel->description ?? '') !== '' ? ' (' . htmlspecialchars($rel->description) . ')' : '';
        $sel       = ($relayId && intval($rid) === $relayId) ? " selected" : "";
        $h[] = "      <option value='$rid'$sel>$rid — $className$rdesc</option>";
    }
    $h[] = "    </select>";
    $h[] = "  </div></div>";

    // ── Network ──
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-network-wired'></i>&nbsp; {network}</h5></div>";
    $h[] = "  <div class='ibox-content'><div class='row'>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>{subnet}</label>";
    $h[] = "      <input type='text' id='scope-subnet-$id' class='form-control' value='$subnet' placeholder='192.168.1.0'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>{netmask}</label>";
    $h[] = "      <input type='text' id='scope-netmask-$id' class='form-control' value='$netmask' placeholder='255.255.255.0'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-2'>";
    $h[] = "      <label>&nbsp;</label><br>";
    $authChk = $auth ? "checked" : "";
    $h[] = "      <div class='checkbox'><label><input type='checkbox' id='scope-auth-$id' $authChk> authoritative</label></div>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    list($tooltip,$none)=$tpl->Tooltips("scope-serverid-expl-$id","{server_identifier_explain}");
    $h[] = "  <div class='row' style='margin-top:10px'>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label id='scope-serverid-expl-$id' $tooltip>{server_identifier} <small class='text-muted'>option 54</small></label>";
    $h[] = "      <input type='text' id='scope-serverid-$id' class='form-control' value='$server_identifier' placeholder='{auto_detect}'>";
    $h[] = "    </div>";
    $h[] = "  </div></div></div>";

    // ── Lease times ──
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-clock'></i>&nbsp; {lease_times}</h5></div>";
    $h[] = "  <div class='ibox-content'><div class='row'>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>{default_lease} <small class='text-muted'>(s)</small></label>";
    $h[] = "      <input type='number' id='scope-ldef-$id' class='form-control' value='$ldef' min='60'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>{max_lease} <small class='text-muted'>(s)</small></label>";
    $h[] = "      <input type='number' id='scope-lmax-$id' class='form-control' value='$lmax' min='60'>";
    $h[] = "    </div>";
    $h[] = "  </div></div></div>";

    // ── DHCP Options ──
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-sliders-h'></i>&nbsp; {dhcp_options}</h5></div>";
    $h[] = "  <div class='ibox-content'>";
    $h[] = "  <div class='row' style='margin-bottom:10px'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{routers} <small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='scope-routers-$id' class='form-control' value='$routers' placeholder='192.168.1.1'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{dns_servers} <small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='scope-dns-$id' class='form-control' value='$dns' placeholder='8.8.8.8, 8.8.4.4'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row' style='margin-bottom:10px'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{ntp-servers} <small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='scope-ntp-$id' class='form-control' value='$ntp' placeholder='192.168.1.1'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{time-servers} <small class='text-muted'>{comma_separated}</small></label>";
    $h[] = "      <input type='text' id='scope-time-$id' class='form-control' value='$time' placeholder='192.168.1.1'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row' style='margin-bottom:10px'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{next-server} <small class='text-muted'>PXE/TFTP</small></label>";
    $h[] = "      <input type='text' id='scope-nextserver-$id' class='form-control' value='$next_server' placeholder='192.168.1.10'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>filename <small class='text-muted'>{pxe_file}</small></label>";
    $h[] = "      <input type='text' id='scope-filename-$id' class='form-control' value='$filename' placeholder='pxelinux.0'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row'>";
    $h[] = "    <div class='col-md-12'>";
    $h[] = "      <label>{rfc3442-classless-static-routes} <small class='text-muted'>CIDR gateway pairs, comma-separated</small></label>";
    $h[] = "      <input type='text' id='scope-rfc3442-$id' class='form-control' value='$rfc3442' placeholder='192.168.10.0/24 10.0.0.1, 0.0.0.0/0 192.168.1.254'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  </div></div>";

    // ── Behavior Flags ──
    $auSelInherit = ($allow_unknown === '')      ? " selected" : "";
    $auSelAllow   = ($allow_unknown === 'allow') ? " selected" : "";
    $auSelDeny    = ($allow_unknown === 'deny')  ? " selected" : "";
    $abChk  = $always_broadcast    ? "checked" : "";
    $glhChk = $get_lease_hostnames ? "checked" : "";
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-toggle-on'></i>&nbsp; {behavior_flags}</h5></div>";
    $h[] = "  <div class='ibox-content'>";
    $h[] = "  <div class='row' style='margin-bottom:10px'>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>{deny_unkown_clients}</label>";
    $h[] = "      <select id='scope-allow-unknown-$id' class='form-control'>";
    $h[] = "        <option value=''$auSelInherit>{default}</option>";
    $h[] = "        <option value='allow'$auSelAllow>allow</option>";
    $h[] = "        <option value='deny'$auSelDeny>deny</option>";
    $h[] = "      </select>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-4'>";
    list($tooltip,$none)=$tpl->Tooltips("gscope-ddns-domain-expl-$id","{ddns-domainname-explain}");
    $h[] = "      <label id='gscope-ddns-domain-expl-$id' $tooltip>{ddns-domainname}</label>";
    $h[] = "      <input type='text' id='scope-ddns-domain-$id' class='form-control' value='$ddns_domain' placeholder='example.com'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>{broadcast}</label>";
    $h[] = "      <input type='text' id='scope-broadcast-$id' class='form-control' value='$broadcast' placeholder='192.168.1.255'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row'>";
    $h[] = "    <div class='col-md-3'>";
    $pcChk = $ping_check ? 'checked' : '';
    list($tooltip,$nine)=$tpl->Tooltips("gscope-ping-check-$id-expl","{DHCPPing_check_explain}");

    $h[] = "      <div class='checkbox'><label><input type='checkbox' id='scope-ping-check-$id' $pcChk> <span id='gscope-ping-check-$id-expl' $tooltip>{DHCPPing_check}</span></label></div>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-3' style='padding-top:25px'>";

    list($tooltip,$none)=$tpl->Tooltips("scope-always-broadcastexpl-$id","{AllwaysBrodcast_explain}");
    $h[] = "      <div class='checkbox'><label><input type='checkbox' id='scope-always-broadcast-$id' $abChk> <span id='scope-always-broadcastexpl-$id' $tooltip>{AllwaysBrodcast}</span></label></div>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-3' style='padding-top:25px'>";
    $h[] = "      <div class='checkbox'><label><input type='checkbox' id='scope-get-lease-hostnames-$id' $glhChk> {get_lease_hostnames}</label></div>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  </div></div>";



    // ── Pools ──
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'>";
    $h[] = "    <h5><i class='fas fa-layer-group'></i>&nbsp; {pools}</h5>";
    $h[] = "    <div class='ibox-tools'>";
    $h[] = "      <a href='#' onclick='AddPool_$id();return false;' class='btn btn-xs btn-default'>";
    $h[] = "        <i class='fas fa-plus'></i> {add_pool}";
    $h[] = "      </a>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='ibox-content'>";
    $h[] = "    <div id='scope-pools-$id'>";

    if (empty($pools)) {
        // one empty row by default
        $h[] = scope_pool_row($id, '', '');
    } else {
        foreach ($pools as $pool) {
            if (!is_object($pool)) continue;
            $h[] = scope_pool_row($id,
                htmlspecialchars($pool->range_start ?? ''),
                htmlspecialchars($pool->range_end   ?? '')
            );
        }
    }

    $h[] = "    </div>";
    $h[] = "    <p class='help-block'>{pool_explain}</p>";
    $h[] = "  </div></div>";

    // ── Save button ──
    $h[] = "<div class='text-right' style='margin-bottom:20px'>";
    $h[] = "  <button class='btn btn-primary btn-lg' onclick='ScopeSave_$id()'>";
    $h[] = "    <i class='fas fa-save'></i> {save}";
    $h[] = "  </button>";
    $h[] = "</div>";

    $h[] = scope_form_js_block($id, $page, $isEdit ? $scope_id : '');

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

function scope_pool_row(int $id, string $start = '', string $end = ''): string {
    return "<div class='row scope-pool-row' style='margin-bottom:8px'>"
         . "  <div class='col-md-5'><input type='text' class='form-control pool-start' value='$start' placeholder='192.168.1.100'></div>"
         . "  <div class='col-md-5'><input type='text' class='form-control pool-end'   value='$end'   placeholder='192.168.1.254'></div>"
         . "  <div class='col-md-2'><button type='button' class='btn btn-danger btn-sm' onclick=\"\$(this).closest('.scope-pool-row').remove()\">"
         . "    <i class='fas fa-times'></i></button></div>"
         . "</div>";
}

function relay_form(): void {
    $tpl  = new template_admin();
    $id   = aid();
    $page = CurrentPageName();

    $h   = [];
    $h[] = "<div id='relay-form-result-$id'></div>";
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-route'></i>&nbsp; {relay_class}</h5></div>";
    $h[] = "  <div class='ibox-content'><div class='row'>";
    $h[] = "    <div class='col-md-2'>";
    $h[] = "      <label>ID</label>";
    $h[] = "      <input type='number' id='relay-id-$id' class='form-control' min='1' placeholder='1'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>{class_name} <span class='text-danger'>*</span></label>";
    $h[] = "      <input type='text' id='relay-classname-$id' class='form-control' placeholder='relay-branch-1'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-3'>";
    $h[] = "      <label>{description} <small class='text-muted'>({optional})</small></label>";
    $h[] = "      <input type='text' id='relay-desc-$id' class='form-control' placeholder='{relay_description}'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row' style='margin-top:10px'>";
    $h[] = "    <div class='col-md-5'>";
    $h[] = "      <label>{circuit_id} <small class='text-muted'>Option-82</small></label>";
    $h[] = "      <input type='text' id='relay-circuit-id-$id' class='form-control' placeholder='0006aabbccdd'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-5'>";
    $h[] = "      <label>{remote_id} <small class='text-muted'>Option-82</small></label>";
    $h[] = "      <input type='text' id='relay-remote-id-$id' class='form-control' placeholder='branch-switch-1'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-2' style='padding-top:25px'>";
    $h[] = "      <small class='text-muted'>{at_least_one_match_required}</small>";
    $h[] = "    </div>";
    $h[] = "  </div></div></div>";
    $h[] = "<div class='text-right' style='margin-bottom:20px'>";
    $h[] = "  <button class='btn btn-primary' onclick='RelaySave_$id()'>";
    $h[] = "    <i class='fas fa-save'></i> {save}";
    $h[] = "  </button>";
    $h[] = "</div>";
    $h[] = relay_form_js_block($id, $page);

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── POST handlers ─────────────────────────────────────────────────────────────

function scope_save(): void {
    $tpl      = new template_admin();
    $id       = aid();
    $existing = trim($_POST["existing_scope_id"] ?? '');

    $routers     = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $_POST["routers"]      ?? ''))));
    $dnss        = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $_POST["dns_servers"]  ?? ''))));
    $ntps        = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $_POST["ntp_servers"]  ?? ''))));
    $times       = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $_POST["time_servers"] ?? ''))));
    $next_server = trim($_POST["next_server"] ?? '');
    $filename    = trim($_POST["filename"]    ?? '');

    $options = [];
    if (!empty($routers))    $options[] = ["name" => "routers",             "type" => "ip-addresses", "value" => $routers];
    if (!empty($dnss))       $options[] = ["name" => "domain-name-servers", "type" => "ip-addresses", "value" => $dnss];
    if (!empty($ntps))       $options[] = ["name" => "ntp-servers",         "type" => "ip-addresses", "value" => $ntps];
    if (!empty($times))      $options[] = ["name" => "time-servers",        "type" => "ip-addresses", "value" => $times];
    if ($next_server !== '') $options[] = ["name" => "next-server",         "type" => "ip-address",   "value" => $next_server];
    if ($filename !== '')    $options[] = ["name" => "filename",            "type" => "text",          "value" => $filename];
    $rfc3442_routes = array_values(array_filter(array_map('trim', preg_split('/[,\n]+/', $_POST["rfc3442_routes"] ?? ''))));
    if (!empty($rfc3442_routes)) $options[] = ["name" => "rfc3442-classless-static-routes", "type" => "classless-routes", "value" => $rfc3442_routes];
    $broadcast = trim($_POST["broadcast_address"] ?? '');
    if ($broadcast !== '') $options[] = ["name" => "broadcast-address", "type" => "ip-address", "value" => $broadcast];

    $flags = [];
    $allow_unknown_mode = trim($_POST["allow_unknown_clients"] ?? '');
    if ($allow_unknown_mode === 'allow')     $flags["allow-unknown-clients"]  = true;
    elseif ($allow_unknown_mode === 'deny')  $flags["allow-unknown-clients"]  = false;
    if (!empty($_POST["always_broadcast"]))      $flags["always-broadcast"]     = true;
    if (!empty($_POST["get_lease_hostnames"]))   $flags["get-lease-hostnames"]  = true;
    if (!empty($_POST["ping_check"]))              $flags["ping-check"]          = true;
    $ddns_domain = trim($_POST["ddns_domainname"] ?? '');
    if ($ddns_domain !== '') $flags["ddns-domainname"] = $ddns_domain;

    // Pools passed as JSON string from JS
    $pools_raw = trim($_POST["pools_json"] ?? '[]');
    $pools     = json_decode($pools_raw, true) ?: [];

    $server_identifier = trim($_POST["server_identifier"] ?? '');

    $scope = [
        "id"            => trim($_POST["scope_id"] ?? ''),
        "type"          => trim($_POST["scope_type"] ?? 'direct'),
        "interface"     => trim($_POST["iface"]     ?? ''),
        "vlan_id"       => intval($_POST["vlan_id"] ?? 0),
        "subnet"        => trim($_POST["subnet"]    ?? ''),
        "netmask"       => trim($_POST["netmask"]   ?? ''),
        "authoritative" => !empty($_POST["authoritative"]),
        "server_identifier" => $server_identifier,
        "lease"         => [
            "default" => max(60, intval($_POST["lease_default"] ?? 28800)),
            "max"     => max(60, intval($_POST["lease_max"]     ?? 28800)),
        ],
        "options"       => $options,
        "flags"         => empty($flags) ? new stdClass() : (object)$flags,
        "pools"         => $pools,
    ];

    if (($scope["type"] ?? '') === 'relay') {
        $scope["relay"] = ["relay_id" => intval($_POST["relay_id"] ?? 0)];
    }
    if ($scope["subnet"] === '' || $scope["netmask"] === '') {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{subnet_and_netmask_required}"));
        return;
    }
    if (empty($pools)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{at_least_one_pool_required}"));
        return;
    }

    // Agent has no update endpoint — delete old scope first, then re-add
    if ($existing !== '') {
        $GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE(
            "/netagents/dhcp/$id/scopes/" . urlencode($existing)
        );
    }

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/dhcp/$id/scopes", $scope
    ));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    if (isset($json->Status) && !$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->Error ?? "{error}")));
        return;
    }

    // ── Auto-validate after save (per spec) ──
    $validate = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST(
        "/netagents/dhcp/$id/config/validate", []
    ));
    $h   = [];
    $h[] = $tpl->div_success("<i class='fas fa-check-circle'></i> {scope_saved}");
    if (is_object($validate)) {
        if (!empty($validate->valid)) {
            $h[] = "<div class='alert alert-success'><i class='fas fa-shield-alt'></i> {config_valid}</div>";
        } else {
            $errs = (array)($validate->errors ?? []);
            $h[] = "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> {config_has_errors}";
            if (!empty($errs)) {
                $h[] = "<ul style='margin-top:6px'>";
                foreach (array_slice($errs, 0, 5) as $e) { $h[] = "<li><small>" . htmlspecialchars($e) . "</small></li>"; }
                $h[] = "</ul>";
            }
            $h[] = "</div>";
        }
    }
    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    admin_tracks("Netagent #$id: DHCP scope " . ($existing ? "updated" : "added"));
}

function scope_delete(): void {
    $tpl      = new template_admin();
    $tpl->CLEAN_POST();
    $scope_posted = trim($_POST["delete-scope"] ?? '');
    $tb=explode("|", $scope_posted);
    $id=intval($tb[0]);
    $scope_id = $tb[1];

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE(
        "/netagents/dhcp/$id/scopes/" . urlencode($scope_id)
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
    admin_tracks("Netagent #$id: DHCP scope '$scope_id' deleted");
}

function relay_save(): void {
    $tpl  = new template_admin();
    $id   = aid();

    $rid       = intval($_POST["relay_id"]        ?? 0);
    $className = trim($_POST["relay_class_name"]  ?? '');
    $desc      = trim($_POST["relay_desc"]        ?? '');
    $circuitId = trim($_POST["relay_circuit_id"]  ?? '');
    $remoteId  = trim($_POST["relay_remote_id"]   ?? '');
    if ($rid < 1) {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{relay_id_required}"));
        return;
    }
    if ($className === '') {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{class_name_required}"));
        return;
    }
    if ($circuitId === '' && $remoteId === '') {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{at_least_one_match_required}"));
        return;
    }
    $match = [];
    if ($circuitId !== '') $match["circuit_id"] = $circuitId;
    if ($remoteId  !== '') $match["remote_id"]  = $remoteId;
    $data = ["id" => $rid, "class_name" => $className, "description" => $desc, "match" => $match];
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/dhcp/$id/relays", $data
    ));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    if (isset($json->Status) && !$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->Error ?? "{error}")));
        return;
    }
    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {relay_added}"));
    admin_tracks("Netagent #$id: DHCP relay $rid added");
}

function relay_delete(): void {
    $tpl      = new template_admin();
    $id       = aid();
    $relay_id = trim($_POST["delete-relay"] ?? '');

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE(
        "/netagents/dhcp/$id/relays/" . urlencode($relay_id)
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
    admin_tracks("Netagent #$id: DHCP relay '$relay_id' deleted");
}

// ── JavaScript blocks ─────────────────────────────────────────────────────────

function scope_form_js_block(int $id, string $page, string $existingId): string {
    $l   = [];
    $l[] = "<script>";

    // Add pool row
    $l[] = "function AddPool_$id(){";
    $l[] = "  var row='<div class=\"row scope-pool-row\" style=\"margin-bottom:8px\">'";
    $l[] = "    +'<div class=\"col-md-5\"><input type=\"text\" class=\"form-control pool-start\" placeholder=\"192.168.1.100\"></div>'";
    $l[] = "    +'<div class=\"col-md-5\"><input type=\"text\" class=\"form-control pool-end\" placeholder=\"192.168.1.254\"></div>'";
    $l[] = "    +'<div class=\"col-md-2\"><button type=\"button\" class=\"btn btn-danger btn-sm\" onclick=\"\$(this).closest(\\'.scope-pool-row\\').remove()\"><i class=\"fas fa-times\"></i></button></div>'";
    $l[] = "    +'</div>';";
    $l[] = "  \$('#scope-pools-$id').append(row);";
    $l[] = "}";

    // Auto-fill hidden vlan_id when interface is selected
    $l[] = "function ScopeIfaceChange_$id(sel){";
    $l[] = "  var vid=parseInt(\$(sel).find(':selected').data('vlan')||0,10);";
    $l[] = "  \$('#scope-vlan-id-$id').val(isNaN(vid)?0:vid);";
    $l[] = "}";

    // Show/hide relay row on type change
    $l[] = "function ScopeTypeChange_$id(){";
    $l[] = "  if(\$('#scope-type-$id').val()==='relay'){";
    $l[] = "    \$('#scope-relay-row-$id').show();";
    $l[] = "  } else {";
    $l[] = "    \$('#scope-relay-row-$id').hide();";
    $l[] = "  }";
    $l[] = "}";

    // Save function
    $l[] = "function ScopeSave_$id(){";
    $l[] = "  var iface=\$('#scope-iface-$id').val();";
    $l[] = "  if(!iface){ alert('{interface_required}'); return; }";
    $l[] = "  var subnet=\$.trim(\$('#scope-subnet-$id').val());";
    $l[] = "  var netmask=\$.trim(\$('#scope-netmask-$id').val());";
    $l[] = "  if(!subnet||!netmask){ alert('{subnet_and_netmask_required}'); return; }";
    $l[] = "  var pools=[];";
    $l[] = "  \$('#scope-pools-$id .scope-pool-row').each(function(i){";
    $l[] = "    var s=\$.trim(\$(this).find('.pool-start').val());";
    $l[] = "    var e=\$.trim(\$(this).find('.pool-end').val());";
    $l[] = "    if(s&&e) pools.push({id:'pool-'+i,range_start:s,range_end:e});";
    $l[] = "  });";
    $l[] = "  if(pools.length===0){ alert('{at_least_one_pool_required}'); return; }";
    $l[] = "  \$('#scope-form-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i></div>');";
    $l[] = "  \$.post('$page',{";
    $l[] = "    'save-scope':1, id:$id,";
    $l[] = "    existing_scope_id:'" . addslashes($existingId) . "',";
    $l[] = "    scope_id:\$.trim(\$('#scope-id-$id').val()),";
    $l[] = "    scope_type:\$('#scope-type-$id').val(),";
    $l[] = "    iface:\$.trim(\$('#scope-iface-$id').val()),";
    $l[] = "    vlan_id:parseInt(\$('#scope-vlan-id-$id').val(),10)||0,";
    $l[] = "    subnet:subnet,";
    $l[] = "    netmask:netmask,";
    $l[] = "    authoritative:\$('#scope-auth-$id').is(':checked')?1:0,";
    $l[] = "    lease_default:\$('#scope-ldef-$id').val(),";
    $l[] = "    lease_max:\$('#scope-lmax-$id').val(),";
    $l[] = "    routers:\$.trim(\$('#scope-routers-$id').val()),";
    $l[] = "    dns_servers:\$.trim(\$('#scope-dns-$id').val()),";
    $l[] = "    ntp_servers:\$.trim(\$('#scope-ntp-$id').val()),";
    $l[] = "    time_servers:\$.trim(\$('#scope-time-$id').val()),";
    $l[] = "    next_server:\$.trim(\$('#scope-nextserver-$id').val()),";
    $l[] = "    filename:\$.trim(\$('#scope-filename-$id').val()),";
    $l[] = "    rfc3442_routes:\$.trim(\$('#scope-rfc3442-$id').val()),";
    $l[] = "    broadcast_address:\$.trim(\$('#scope-broadcast-$id').val()),";
    $l[] = "    allow_unknown_clients:\$('#scope-allow-unknown-$id').val(),";
    $l[] = "    always_broadcast:\$('#scope-always-broadcast-$id').is(':checked')?1:0,";
    $l[] = "    get_lease_hostnames:\$('#scope-get-lease-hostnames-$id').is(':checked')?1:0,";
    $l[] = "    ping_check:\$('#scope-ping-check-$id').is(':checked')?1:0,";
    $l[] = "    ddns_domainname:\$.trim(\$('#scope-ddns-domain-$id').val()),";
    $l[] = "    relay_id:\$('#scope-relay-id-$id').val(),";
    $l[] = "    server_identifier:\$.trim(\$('#scope-serverid-$id').val()),";
    $l[] = "    pools_json:JSON.stringify(pools)";
    $l[] = "  },function(r){";
    $l[] = "    \$('#scope-form-result-$id').html(r);";
    $l[] = "    LoadAjaxSilent('dhcp-scopes-div-$id','$page?scopes-table=$id');";
    $l[] = "    dialogInstance2.close();";
    $l[] = "  });";
    $l[] = "}";
    $l[] = "</script>";
    return implode("\n", $l);
}

function relay_form_js_block(int $id, string $page): string {
    $l   = [];
    $l[] = "<script>";
    $l[] = "function RelaySave_$id(){";
    $l[] = "  var rid=parseInt(\$('#relay-id-$id').val(),10);";
    $l[] = "  if(isNaN(rid)||rid<1){ alert('{relay_id_required}'); return; }";
    $l[] = "  var cn=\$.trim(\$('#relay-classname-$id').val());";
    $l[] = "  if(!cn){ alert('{class_name_required}'); return; }";
    $l[] = "  var cid=\$.trim(\$('#relay-circuit-id-$id').val());";
    $l[] = "  var rid2=\$.trim(\$('#relay-remote-id-$id').val());";
    $l[] = "  if(!cid&&!rid2){ alert('{at_least_one_match_required}'); return; }";
    $l[] = "  \$('#relay-form-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i></div>');";
    $l[] = "  \$.post('$page',{";
    $l[] = "    'save-relay':1, id:$id,";
    $l[] = "    relay_id:rid,";
    $l[] = "    relay_class_name:cn,";
    $l[] = "    relay_desc:\$.trim(\$('#relay-desc-$id').val()),";
    $l[] = "    relay_circuit_id:cid,";
    $l[] = "    relay_remote_id:rid2";
    $l[] = "  },function(r){";
    $l[] = "    \$('#relay-form-result-$id').html(r);";
    $l[] = "    dialogInstance1.close();";
    $l[] = "  });";
    $l[] = "}";
    $l[] = "</script>";
    return implode("\n", $l);
}
