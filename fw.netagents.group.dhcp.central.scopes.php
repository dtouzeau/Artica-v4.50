<?php
/**
 * Central DHCP scope management (cluster_mode).
 * CRUD operations on the management server's central config JSON.
 *
 * Entry:  LoadAjax('div','fw.netagents.group.dhcp.central.scopes.php?id={group_id}')
 * API:    GET    /netagents/dhcp/group/{id}/central/scopes
 *         POST   /netagents/dhcp/group/{id}/central/scopes          — add
 *         POST   /netagents/dhcp/group/{id}/central/scopes/{sid}    — update
 *         DELETE /netagents/dhcp/group/{id}/central/scopes/{sid}    — delete
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }

if (isset($_GET["scope-add-js"]))   { cscope_add_js();     exit; }
if (isset($_GET["scope-form"]))     { cscope_form();       exit; }
if (isset($_GET["scope-edit-js"]))  { cscope_edit_js();    exit; }
if (isset($_GET["scope-edit-form"])){ cscope_edit_form();   exit; }
if (isset($_POST["save-scope"]))    { cscope_save();       exit; }
if (isset($_POST["edit-scope"]))    { cscope_edit_save();  exit; }
if (isset($_POST["delete-scope"]))  { cscope_delete();     exit; }
if (isset($_GET["table-scope"]))    { cscopes_page();     exit; }
if (isset($_GET["button-scope"]))    { cscopes_buttons();     exit; }
cscopes_head();

// ── Helpers ───────────────────────────────────────────────────────────────────

function gid(): int {
    return intval($_GET["id"] ?? $_POST["id"]
        ?? $_GET["scope-add-js"] ?? $_GET["scope-edit-js"] ?? 0);
}

function cscopes_buttons():bool{
    $page = CurrentPageName();
    $tpl  = new template_admin();
    $id   = gid();
    $function=$_GET["function"];
    $topbuttons[] = array("Loadjs('$page?scope-add-js=$id&function=$function');", ico_plus, "{add_scope}");
    echo $tpl->table_buttons($topbuttons);
    return true;
}

// ── Main page: list scopes ───────────────────────────────────────────────────
function cscopes_head():bool{
    $tpl  = new template_admin();
    $id   = gid();
    $page = CurrentPageName();
    $h   = [];
    $h[] = "<div id='cscopes-buttons-$id' style='margin-top:10px'></div>";
    $h[] = "<div id='cscopes-msg-$id' style='margin-bottom:10px'></div>";
    $h[] = $tpl->search_block($page,"","","","&table-scope=yes&id=$id");
    echo $tpl->_ENGINE_parse_body($h);
    return true;
}
function cscopes_page(): bool {
    $tpl  = new template_admin();
    $id   = gid();
    $page = CurrentPageName();
    $function=$_GET["function"];
    $scopes = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/group/$id/central/scopes"));
    if (!is_array($scopes)) { $scopes = []; }
    $h   = [];


    if (empty($scopes)) {
        $h[] = "<div class='alert alert-info'><i class='fas fa-info-circle'></i> {no_scopes_configured}</div>";
        $h[] = "<script>LoadAjax('cscopes-buttons-$id','$page?button-scope=yes&id=$id&function=$function');</script>";
        echo $tpl->_ENGINE_parse_body($h);
        return true;
    }
    $t = time();
    $h[] = "<table id='tbl-cs-$t' class='table table-stripped' data-page-size='50'>";
    $h[] = "<thead><tr>";
    $h[] = "  <th data-sortable=true data-type='text'>{subnet}/{netmask}</th>";
    $h[] = "  <th data-sortable=true data-type='text' nowrap>{interface}</th>";
    $h[] = "  <th data-sortable=true data-type='text'>{type}</th>";
    $h[] = "  <th>{pools}</th>";
    $h[] = "  <th>{actions}</th>";
    $h[] = "</tr></thead><tbody>";

    $TRCLASS = null;
    foreach ($scopes as $s) {
        if (!is_object($s)) continue;
        if ($TRCLASS === 'footable-odd') { $TRCLASS = null; } else { $TRCLASS = 'footable-odd'; }
        $sid    = htmlspecialchars($s->id ?? '');
        $subnet = htmlspecialchars($s->subnet ?? '');
        $mask   = htmlspecialchars($s->netmask ?? '');
        $iface  = htmlspecialchars($s->interface ?? '');
        $stype  = htmlspecialchars($s->type ?? 'direct');
        $pools  = is_array($s->pools ?? null) ? count($s->pools) : 0;

        $sidAttr = htmlspecialchars(json_encode($sid), ENT_QUOTES);
        $editJs = "Loadjs('$page?scope-edit-js=$id&sid='+encodeURIComponent($sidAttr))";
        $delJs  = "CscopeDelete_$id($sidAttr)";

        $h[] = "<tr class='$TRCLASS'>";
            $h[] = "  <td style='width:100%'><strong>$subnet / $mask</strong></td>";
            $h[] = "  <td style='width:1%' nowrap>$iface</td>";
            $h[] = "  <td style='width:1%' nowrap>$stype</td>";
            $h[] = "  <td style='width:1%' nowrap><span class='badge' style='background:#676a6c;color:#fff'>$pools</span></td>";
            $h[] = "  <td style='width:1%' nowrap>";
            $h[] = "    <a href='#' onclick=\"$editJs;return false;\" class='btn btn-xs btn-primary'><i class='fas fa-edit'></i></a>";
            $h[] = "    <a href='#' onclick=\"$delJs;return false;\" class='btn btn-xs btn-danger'><i class='fas fa-trash'></i></a>";
            $h[] = "  </td>";
            $h[] = "</tr>";
    }
    $h[] = "</tbody>";
    $h[] = "<tfoot><tr><td colspan='5'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $h[] = "</table>";
    $h[] = "<script>";
    $h[] = "    NoSpinner();";
    $h[] =      implode("\n", $tpl->ICON_SCRIPTS);
    $h[] = "</script>";


    // Delete JS
    $h[] = "<script>";
    $h[] = "function CscopeDelete_$id(sid){";
    $h[] = "  swal({title:'{confirm_delete}',text:sid,type:'warning',showCancelButton:true,";
    $h[] = "    confirmButtonColor:'#ed5565',confirmButtonText:'{delete}',cancelButtonText:'{cancel}'";
    $h[] = "  },function(ok){ if(!ok) return;";
    $h[] = "    $.post('$page',{'delete-scope':1,id:$id,sid:sid},function(r){";
    $h[] = "      try{r=JSON.parse(r);}catch(e){}";
    $h[] = "      if(r&&r.Status===false){swal('{error}',r.Error||'{error}','error');return;}";
    $h[] = "      LoadAjax('cscopes-container-$id','$page?id=$id');";
    $h[] = "    });";
    $h[] = "  });";
    $h[] = "}";
    $h[]="LoadAjax('cscopes-buttons-$id','$page?button-scope=yes&id=$id&function=$function');";
    $h[] = "</script>";
    $h[] = "<div id='cscopes-container-$id'></div>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    return true;
}

// ── Dialog openers ────────────────────────────────────────────────────────────

function cscope_add_js(): bool {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["scope-add-js"]);
    return $tpl->js_dialog2("{add_scope}", "$page?id=$id&scope-form=1", 820);
}

function cscope_edit_js(): bool {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["scope-edit-js"]);
    $sid  = urlencode($_GET["sid"] ?? '');
    $function="";
    if(isset($_GET["function"])) {
        $function = $_GET["function"];
    }
    return $tpl->js_dialog2("{edit_scope}", "$page?id=$id&scope-edit-form=1&sid=$sid&function=$function", 820);
}

// ── Add form ──────────────────────────────────────────────────────────────────

function cscope_form(): void {
    $tpl  = new template_admin();
    $id   = gid();
    $page = CurrentPageName();

    $h = cscope_render_form($id, $page, null, 'save-scope');
    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Edit form ─────────────────────────────────────────────────────────────────

function cscope_edit_form(): void {
    $tpl  = new template_admin();
    $id   = gid();
    $page = CurrentPageName();
    $sid  = trim($_GET["sid"] ?? '');


    // Load existing scope from central config
    $scopes = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/group/$id/central/scopes"));
    $scope  = null;
    if (is_array($scopes)) {
        foreach ($scopes as $s) {
            if (is_object($s) && ($s->id ?? '') == $sid) {
                $scope = $s;
                break;
            }
        }
    }
    if ($scope === null) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{scope_not_found}"));
        return;
    }

    $h = cscope_render_form($id, $page, $scope, 'edit-scope');
    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Shared form renderer ─────────────────────────────────────────────────────

function cscope_render_form(int $id, string $page, ?object $scope, string $action): array {
    $tpl = new template_admin();
    $isEdit = ($scope !== null);
    $srid = $isEdit ? ($scope->id ?? '') : 'cscope-' . $id . '-' . time();
    $function="";
    if(isset($_GET["function"])) {
        $function = $_GET["function"];
    }

    // Extract values from existing scope
    $stype     = $isEdit ? ($scope->type ?? 'direct') : 'direct';
    $iface     = $isEdit ? ($scope->interface ?? '') : '';
    $vlanId    = $isEdit ? intval($scope->vlan_id ?? 0) : 0;
    $subnet    = $isEdit ? ($scope->subnet ?? '') : '';
    $netmask   = $isEdit ? ($scope->netmask ?? '') : '';
    $auth      = $isEdit ? (!empty($scope->authoritative)) : true;
    $ldef      = $isEdit ? intval($scope->lease->default ?? 28800) : 28800;
    $lmax      = $isEdit ? intval($scope->lease->max ?? 28800) : 28800;

    // Extract DHCP options
    $routers=$dnss=$ntps=$times=$nextSrv=$filename=$rfc3442=$broadcast='';
    if ($isEdit && is_array($scope->options ?? null)) {
        foreach ($scope->options as $opt) {
            if (!is_object($opt)) continue;
            $n = $opt->name ?? '';
            $v = $opt->value ?? '';
            if (is_array($v)) $v = implode(', ', $v);
            if ($n === 'routers')             $routers  = $v;
            if ($n === 'domain-name-servers') $dnss     = $v;
            if ($n === 'ntp-servers')         $ntps     = $v;
            if ($n === 'time-servers')        $times    = $v;
            if ($n === 'next-server')         $nextSrv  = $v;
            if ($n === 'filename')            $filename = $v;
            if ($n === 'rfc3442-classless-static-routes') $rfc3442 = is_array($opt->value ?? null) ? implode(', ', $opt->value) : $v;
            if ($n === 'broadcast-address')   $broadcast= $v;
        }
    }

    // Extract flags
    $allowUnknown = '';
    $ddnsDomain = '';
    $alwaysBroadcast = false;
    $getLeaseHostnames = false;
    $pingCheck = false;
    if ($isEdit && is_object($scope->flags ?? null)) {
        $flags = (array)$scope->flags;
        if (isset($flags['allow-unknown-clients'])) {
            $allowUnknown = $flags['allow-unknown-clients'] ? 'allow' : 'deny';
        }
        $alwaysBroadcast   = !empty($flags['always-broadcast']);
        $getLeaseHostnames = !empty($flags['get-lease-hostnames']);
        $pingCheck         = !empty($flags['ping-check']);
        $ddnsDomain        = $flags['ddns-domainname'] ?? '';
    }

    // Extract relay info
    $relayIdVal = 0;
    if ($isEdit && is_object($scope->relay ?? null)) {
        $relayIdVal = intval($scope->relay->relay_id ?? 0);
    }

    $authChecked    = $auth ? 'checked' : '';
    $abChecked      = $alwaysBroadcast ? 'checked' : '';
    $glhChecked     = $getLeaseHostnames ? 'checked' : '';
    $relayDisplay   = ($stype === 'relay') ? '' : 'display:none';
    $directSel      = ($stype === 'direct') ? 'selected' : '';
    $relaySel       = ($stype === 'relay') ? 'selected' : '';
    $auSel          = [''=>'','allow'=>'','deny'=>''];
    $auSel[$allowUnknown] = 'selected';

    $h = [];
    $h[] = "<div id='cscope-form-msg-$id'></div>";

    // Identity & Type
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-id-card'></i>&nbsp; {scope_identity}</h5></div>";
    $h[] = "  <div class='ibox-content'><div class='row'>";
    $h[] = "    <input type='hidden' id='cscope-id-$id' value='" . htmlspecialchars($srid) . "'>";
    $h[] = "    <div class='col-md-3'>";
    $h[] = "      <label>{type}</label>";
    $h[] = "      <select id='cscope-type-$id' class='form-control' onchange='CScopeTypeChg_$id()'>";
    $h[] = "        <option value='direct' $directSel>direct</option>";
    $h[] = "        <option value='relay' $relaySel>relay</option>";
    $h[] = "      </select>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>{interface}</label>";
    $h[] = "      <input type='text' id='cscope-iface-$id' class='form-control' placeholder='ens192' value='" . htmlspecialchars($iface) . "'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>VLAN</label>";
    $h[] = "      <input type='number' id='cscope-vlan-$id' class='form-control' min='0' max='4094' value='$vlanId'>";
    $h[] = "    </div>";
    $h[] = "  </div></div></div>";

    // Relay row
    $h[] = "<div id='cscope-relay-row-$id' class='ibox' style='$relayDisplay'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-route'></i>&nbsp; {relay_class_id}</h5></div>";
    $h[] = "  <div class='ibox-content'>";
    $h[] = "    <input type='number' id='cscope-relay-id-$id' class='form-control' style='max-width:200px' min='1' value='$relayIdVal'>";
    $h[] = "  </div></div>";

    // Network
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-network-wired'></i>&nbsp; {network}</h5></div>";
    $h[] = "  <div class='ibox-content'><div class='row'>";
    $h[] = "    <div class='col-md-5'>";
    $h[] = "      <label>{subnet}</label>";
    $h[] = "      <input type='text' id='cscope-subnet-$id' class='form-control' placeholder='192.168.1.0' value='" . htmlspecialchars($subnet) . "'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-5'>";
    $h[] = "      <label>{netmask}</label>";
    $h[] = "      <input type='text' id='cscope-netmask-$id' class='form-control' placeholder='255.255.255.0' value='" . htmlspecialchars($netmask) . "'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-2'>";
    $h[] = "      <label>&nbsp;</label><br>";
    $h[] = "      <div class='checkbox'><label><input type='checkbox' id='cscope-auth-$id' $authChecked> authoritative</label></div>";
    $h[] = "    </div>";
    $h[] = "  </div></div></div>";

    // Lease times
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-clock'></i>&nbsp; {lease_times}</h5></div>";
    $h[] = "  <div class='ibox-content'><div class='row'>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>{default_lease} <small class='text-muted'>(s)</small></label>";
    $h[] = "      <input type='number' id='cscope-ldef-$id' class='form-control' value='$ldef' min='60'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>{max_lease} <small class='text-muted'>(s)</small></label>";
    $h[] = "      <input type='number' id='cscope-lmax-$id' class='form-control' value='$lmax' min='60'>";
    $h[] = "    </div>";
    $h[] = "  </div></div></div>";

    // DHCP Options
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-sliders-h'></i>&nbsp; {dhcp_options}</h5></div>";
    $h[] = "  <div class='ibox-content'>";
    $h[] = "  <div class='row' style='margin-bottom:10px'>";
    $h[] = "    <div class='col-md-6'><label>{routers}</label> <small class='text-muted'>{comma_separated}</small>";
    $h[] = "      <input type='text' id='cscope-routers-$id' class='form-control' value='" . htmlspecialchars($routers) . "'></div>";
    $h[] = "    <div class='col-md-6'><label>{dns_servers}</label> <small class='text-muted'>{comma_separated}</small>";
    $h[] = "      <input type='text' id='cscope-dns-$id' class='form-control' value='" . htmlspecialchars($dnss) . "'></div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row' style='margin-bottom:10px'>";
    $h[] = "    <div class='col-md-6'><label>NTP</label>";
    $h[] = "      <input type='text' id='cscope-ntp-$id' class='form-control' value='" . htmlspecialchars($ntps) . "'></div>";
    $h[] = "    <div class='col-md-6'><label>{time-servers}</label>";
    $h[] = "      <input type='text' id='cscope-time-$id' class='form-control' value='" . htmlspecialchars($times) . "'></div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row' style='margin-bottom:10px'>";
    $h[] = "    <div class='col-md-6'><label>{next-server}</label>";
    $h[] = "      <input type='text' id='cscope-nextserver-$id' class='form-control' value='" . htmlspecialchars($nextSrv) . "'></div>";
    $h[] = "    <div class='col-md-6'><label>{filename}</label>";
    $h[] = "      <input type='text' id='cscope-filename-$id' class='form-control' value='" . htmlspecialchars($filename) . "'></div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row'>";
    $h[] = "    <div class='col-md-12'><label>{rfc3442-classless-static-routes}</label>";
    $h[] = "      <input type='text' id='cscope-rfc3442-$id' class='form-control' placeholder='192.168.10.0/24 10.0.0.1, 0.0.0.0/0 192.168.1.254' value='" . htmlspecialchars($rfc3442) . "'></div>";
    $h[] = "  </div>";
    $h[] = "  </div></div>";

    // Behavior
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-toggle-on'></i>&nbsp; {behavior_flags}</h5></div>";
    $h[] = "  <div class='ibox-content'>";
    $h[] = "  <div class='row' style='margin-bottom:10px'>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>{deny_unkown_clients}</label>";
    $h[] = "      <select id='cscope-allow-unknown-$id' class='form-control'>";
    $h[] = "        <option value='' {$auSel['']}>{default}</option>";
    $h[] = "        <option value='allow' {$auSel['allow']}>allow</option>";
    $h[] = "        <option value='deny' {$auSel['deny']}>deny</option>";
    $h[] = "      </select>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>{ddns-domainname}</label>";
    $h[] = "      <input type='text' id='cscope-ddns-$id' class='form-control' value='" . htmlspecialchars($ddnsDomain) . "'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>{broadcast}</label>";
    $h[] = "      <input type='text' id='cscope-broadcast-$id' class='form-control' value='" . htmlspecialchars($broadcast) . "'>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='row'>";
    $h[] = "    <div class='col-md-4'>";
    $pcChk = $pingCheck ? 'checked' : '';
    $h[] = "      <div class='checkbox'><label><input type='checkbox' id='cscope-ping-$id' $pcChk> {DHCPPing_check}</label></div>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-4' >";
    $h[] = "      <div class='checkbox'><label><input type='checkbox' id='cscope-ab-$id' $abChecked> {AllwaysBrodcast}</label></div>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-4' >";
    $h[] = "      <div class='checkbox'><label><input type='checkbox' id='cscope-glh-$id' $glhChecked> {get_lease_hostnames}</label></div>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  </div></div>";

    // Pools
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'>";
    $h[] = "    <h5><i class='fas fa-layer-group'></i>&nbsp; {pools}</h5>";
    $h[] = "    <div class='ibox-tools'>";
    $h[] = "      <a href='#' onclick='CAddPool_$id();return false;' class='btn btn-xs btn-default'><i class='fas fa-plus'></i> {add_pool}</a>";
    $h[] = "    </div>";
    $h[] = "  </div>";
    $h[] = "  <div class='ibox-content'><div id='cscope-pools-$id'>";

    // Pre-fill existing pools
    $pools = $isEdit ? ($scope->pools ?? []) : [];
    if (empty($pools)) {
        $h[] = "    <div class='row cscope-pool-row' style='margin-bottom:8px'>";
        $h[] = "      <div class='col-md-5'><input type='text' class='form-control pool-start' placeholder='192.168.1.100'></div>";
        $h[] = "      <div class='col-md-5'><input type='text' class='form-control pool-end' placeholder='192.168.1.254'></div>";
        $h[] = "      <div class='col-md-2'><button type='button' class='btn btn-danger btn-sm' onclick=\"$(this).closest('.cscope-pool-row').remove()\"><i class='fas fa-times'></i></button></div>";
        $h[] = "    </div>";
    } else {
        foreach ($pools as $p) {
            $ps = htmlspecialchars($p->range_start ?? '');
            $pe = htmlspecialchars($p->range_end ?? '');
            $h[] = "    <div class='row cscope-pool-row' style='margin-bottom:8px'>";
            $h[] = "      <div class='col-md-5'><input type='text' class='form-control pool-start' value='$ps'></div>";
            $h[] = "      <div class='col-md-5'><input type='text' class='form-control pool-end' value='$pe'></div>";
            $h[] = "      <div class='col-md-2'><button type='button' class='btn btn-danger btn-sm' onclick=\"$(this).closest('.cscope-pool-row').remove()\"><i class='fas fa-times'></i></button></div>";
            $h[] = "    </div>";
        }
    }
    $h[] = "  </div></div></div>";

    // Save button
    $btnLabel = $isEdit ? '{save}' : '{add}';
    $h[] = "<div class='text-right' style='margin-bottom:20px'>";
    $h[] = "  <button class='btn btn-success btn-lg' onclick='CScopeSave_$id()'>";
    $h[] = "    <i class='fas fa-save'></i> $btnLabel";
    $h[] = "  </button>";
    $h[] = "</div>";

    // JavaScript
    $h[] = "<script>";
    $h[] = "function CScopeTypeChg_$id(){";
    $h[] = "  $('#cscope-relay-row-$id').toggle($('#cscope-type-$id').val()==='relay');";
    $h[] = "}";

    $h[] = "function CAddPool_$id(){";
    $h[] = "  var row='<div class=\"row cscope-pool-row\" style=\"margin-bottom:8px\">'";
    $h[] = "    +'<div class=\"col-md-5\"><input type=\"text\" class=\"form-control pool-start\" placeholder=\"192.168.1.100\"></div>'";
    $h[] = "    +'<div class=\"col-md-5\"><input type=\"text\" class=\"form-control pool-end\" placeholder=\"192.168.1.254\"></div>'";
    $h[] = "    +'<div class=\"col-md-2\"><button type=\"button\" class=\"btn btn-danger btn-sm\" onclick=\"$(this).closest(\\'.cscope-pool-row\\').remove()\"><i class=\"fas fa-times\"></i></button></div>'";
    $h[] = "    +'</div>';";
    $h[] = "  $('#cscope-pools-$id').append(row);";
    $h[] = "}";

    $h[] = "function CScopeSave_$id(){";
    $h[] = "  var subnet=$('#cscope-subnet-$id').val().trim();";
    $h[] = "  var netmask=$('#cscope-netmask-$id').val().trim();";
    $h[] = "  if(!subnet||!netmask){ swal('{error}','{subnet_and_netmask_required}','warning'); return; }";
    $h[] = "  var pools=[];";
    $h[] = "  $('#cscope-pools-$id .cscope-pool-row').each(function(i){";
    $h[] = "    var s=$(this).find('.pool-start').val().trim();";
    $h[] = "    var e=$(this).find('.pool-end').val().trim();";
    $h[] = "    if(s&&e) pools.push({id:'pool-'+i,range_start:s,range_end:e});";
    $h[] = "  });";
    $h[] = "  if(!pools.length){ swal('{error}','{at_least_one_pool_required}','warning'); return; }";

    // Build options array
    $h[] = "  var opts=[];";
    $h[] = "  var r=$('#cscope-routers-$id').val().trim(); if(r) opts.push({name:'routers',type:'ip-addresses',value:r.split(/[\\s,]+/).filter(Boolean)});";
    $h[] = "  var d=$('#cscope-dns-$id').val().trim(); if(d) opts.push({name:'domain-name-servers',type:'ip-addresses',value:d.split(/[\\s,]+/).filter(Boolean)});";
    $h[] = "  var n=$('#cscope-ntp-$id').val().trim(); if(n) opts.push({name:'ntp-servers',type:'ip-addresses',value:n.split(/[\\s,]+/).filter(Boolean)});";
    $h[] = "  var t=$('#cscope-time-$id').val().trim(); if(t) opts.push({name:'time-servers',type:'ip-addresses',value:t.split(/[\\s,]+/).filter(Boolean)});";
    $h[] = "  var ns=$('#cscope-nextserver-$id').val().trim(); if(ns) opts.push({name:'next-server',type:'ip-address',value:ns});";
    $h[] = "  var fn=$('#cscope-filename-$id').val().trim(); if(fn) opts.push({name:'filename',type:'text',value:fn});";
    $h[] = "  var rf=$('#cscope-rfc3442-$id').val().trim(); if(rf) opts.push({name:'rfc3442-classless-static-routes',type:'classless-routes',value:rf.split(/[,\\n]+/).map(function(x){return x.trim();}).filter(Boolean)});";
    $h[] = "  var bc=$('#cscope-broadcast-$id').val().trim(); if(bc) opts.push({name:'broadcast-address',type:'ip-address',value:bc});";

    // Build flags
    $h[] = "  var flags={};";
    $h[] = "  var au=$('#cscope-allow-unknown-$id').val();";
    $h[] = "  if(au==='allow') flags['allow-unknown-clients']=true;";
    $h[] = "  else if(au==='deny') flags['allow-unknown-clients']=false;";
    $h[] = "  if($('#cscope-ab-$id').is(':checked')) flags['always-broadcast']=true;";
    $h[] = "  if($('#cscope-glh-$id').is(':checked')) flags['get-lease-hostnames']=true;";
    $h[] = "  if($('#cscope-ping-$id').is(':checked')) flags['ping-check']=true;";
    $h[] = "  var dd=$('#cscope-ddns-$id').val().trim(); if(dd) flags['ddns-domainname']=dd;";

    $h[] = "  var scope={";
    $h[] = "    id:$('#cscope-id-$id').val().trim(),";
    $h[] = "    type:$('#cscope-type-$id').val(),";
    $h[] = "    'interface':$('#cscope-iface-$id').val().trim(),";
    $h[] = "    vlan_id:parseInt($('#cscope-vlan-$id').val(),10)||0,";
    $h[] = "    subnet:subnet,netmask:netmask,";
    $h[] = "    authoritative:$('#cscope-auth-$id').is(':checked'),";
    $h[] = "    lease:{'default':parseInt($('#cscope-ldef-$id').val(),10)||28800,max:parseInt($('#cscope-lmax-$id').val(),10)||28800},";
    $h[] = "    options:opts,flags:flags,pools:pools";
    $h[] = "  };";
    $h[] = "  if(scope.type==='relay') scope.relay={relay_id:parseInt($('#cscope-relay-id-$id').val(),10)||0};";

    $postField = $isEdit ? 'edit-scope' : 'save-scope';
    $h[] = "  $.post('$page',{'$postField':1,id:$id,scope_json:JSON.stringify(scope)},function(r){";
    $h[] = "    try{r=JSON.parse(r);}catch(e){}";
    $h[] = "    if(r&&r.Status===false){swal('{error}',r.Error||'{error}','error');return;}";
    $h[] = "    dialogInstance2.close();";
    if(strlen($function)>2){
        $h[] = "    $function();";

    }

    $h[] = "  });";
    $h[] = "}";
    $h[] = "</script>";

    return $h;
}

// ── POST handlers ─────────────────────────────────────────────────────────────

function cscope_save(): void {
    $id = intval($_POST["id"] ?? 0);
    $data = json_decode($_POST["scope_json"] ?? '{}');
    if (!is_object($data)) {
        echo json_encode(["Status" => false, "Error" => "Invalid JSON"]);
        return;
    }
    echo $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/dhcp/group/$id/central/scopes", $data
    );
}

function cscope_edit_save(): void {
    $id = intval($_POST["id"] ?? 0);
    $data = json_decode($_POST["scope_json"] ?? '{}');
    if (!is_object($data)) {
        echo json_encode(["Status" => false, "Error" => "Invalid JSON"]);
        return;
    }
    $sid = urlencode($data->id ?? '');
    echo $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/dhcp/group/$id/central/scopes/$sid", $data
    );
}

function cscope_delete(): void {
    $id  = intval($_POST["id"] ?? 0);
    $sid = urlencode($_POST["sid"] ?? '');
    echo $GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE(
        "/netagents/dhcp/group/$id/central/scopes/$sid"
    );
}
