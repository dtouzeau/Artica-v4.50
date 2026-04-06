<?php
/**
 * VLAN Management for remote netagents.
 * Loaded as a tab from fw.netagents.network.php tabs() via:
 *   LoadAjaxTiny('tab-xxx', 'fw.netagents.network.vlan.php?agent-id={id}')
 *
 * API:  /netagents/vlan/{id}              — list / create
 *       /netagents/vlan/{id}/{vid}         — get / update / delete
 *       /netagents/vlan/{id}/{vid}/apply   — apply to kernel
 *       /netagents/vlan/{id}/reconcile     — reconcile all
 *       /netagents/vlan/{id}/support       — kernel module status
 *       /netagents/vlan/{id}/support/load  — load 8021q module
 */
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}

$users=new usersMenus();
if(!$users->AsSystemAdministrator){exit();}

// POST dispatchers
if(isset($_POST["load-module"]))    {vlan_load_module();    exit;}
if(isset($_POST["vlan-save"]))      {vlan_save();           exit;}
if(isset($_POST["vlan-update"]))    {vlan_update();         exit;}
if(isset($_POST["vlan-delete"]))    {vlan_delete();         exit;}
if(isset($_POST["vlan-apply"]))     {vlan_apply();          exit;}
if(isset($_POST["reconcile-all"]))  {vlan_reconcile_all();  exit;}

// GET dispatchers
if(isset($_GET["support-badge"]))   {vlan_support_badge();  exit;}
if(isset($_GET["list"]))            {vlan_list();            exit;}
if(isset($_GET["vlan-add-form"]))   {vlan_add_form();       exit;}
if(isset($_GET["vlan-edit-form"]))  {vlan_edit_form();      exit;}
if(isset($_GET["vlan-add-js"]))     {vlan_add_js();         exit;}
if(isset($_GET["vlan-edit-js"]))    {vlan_edit_js();        exit;}
if(isset($_GET["metrics-js"]))      {vlan_metrics_js();     exit;}
if(isset($_GET["metrics-shell"]))   {vlan_metrics_shell();  exit;}
if(isset($_GET["metrics-charts"]))  {vlan_metrics_charts(); exit;}

// Default entry — loaded by tabs_default
vlan_start();

// ── Helpers ──────────────────────────────────────────────────────────────

function aid():int{
    return intval($_GET["agent-id"] ?? $_GET["list"] ?? $_GET["support-badge"] ?? 0);
}

function netagent_err($json):string{
    if(!is_object($json)) return "{error}";
    // Agent returns lowercase "error", central proxy returns "Error"
    return htmlspecialchars($json->error ?? $json->Error ?? "{error}");
}

function netagent_is_error($json):bool{
    if(!is_object($json)) return true;
    // Agent returns lowercase "success":false, central proxy returns "Status":false
    if(isset($json->success)&&$json->success===false) return true;
    if(isset($json->Status)&&!$json->Status) return true;
    return false;
}

// ── vlan_start() — main shell loaded into the tab pane ───────────────────

function vlan_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["agent-id"]);
    if($id<1){echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));return true;}

    $h=[];

    // Support badge area
    $h[]="<div id='vlan-support-$id' style='margin-bottom:12px;margin-top:10px'></div>";

    // VLAN list area
    $h[]="<div id='vlan-list-$id'></div>";

    // Result area for apply/reconcile feedback
    $h[]="<div id='vlan-result-$id'></div>";

    // Load support badge + list on init
    $h[]="<script>";
    $h[]="LoadAjaxSilent('vlan-support-$id','$page?support-badge=$id');";
    $h[]="LoadAjax('vlan-list-$id','$page?list=$id');";
    $h[]="</script>";

    // JS functions scoped to this agent
    $h[]=vlan_js_block($id);

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
    return true;
}

// ── Support badge (8021q module status) ──────────────────────────────────

function vlan_support_badge():void{
    $tpl=new template_admin();
    $id=intval($_GET["support-badge"]);
    $page=CurrentPageName();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/vlan/$id/support"));
    if(netagent_is_error($json)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(netagent_err($json)));
        return;
    }

    $h=[];
    if($json->module_loaded ?? false){
        $h[]="<span class='label label-success' style='color:#fff'>";
        $h[]="<i class='fas fa-check-circle'></i>&nbsp; 8021q {loaded}";
        $h[]="</span>";
    }else{
        $h[]="<span class='label label-warning' style='color:#fff'>";
        $h[]="<i class='fas fa-exclamation-triangle'></i>&nbsp; 8021q {not_loaded}";
        $h[]="</span>";
        $h[]="&nbsp; <button class='btn btn-xs btn-primary' OnClick=\"VlanLoadModule_$id()\">";
        $h[]="<i class='fas fa-download'></i>&nbsp; {load_module}</button>";
    }

    $h[]="&nbsp; <span class='label label-info' style='color:#fff'>";
    $h[]="<i class='fas fa-server'></i>&nbsp; ".htmlspecialchars($json->kernel_version ?? '');
    $h[]="</span>";

    $ifaces=$json->interfaces ?? [];
    if(is_array($ifaces)&&count($ifaces)>0){
        $h[]="&nbsp; <span class='text-muted'>{interfaces}: ";
        $h[]=htmlspecialchars(implode(', ',$ifaces));
        $h[]="</span>";
    }

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Load 8021q kernel module ─────────────────────────────────────────────

function vlan_load_module():void{
    $tpl=new template_admin();
    $id=intval($_POST["load-module"]);
    $page=CurrentPageName();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/vlan/$id/support/load",[]));
    if(netagent_is_error($json)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(netagent_err($json)));
        return;
    }
    echo $tpl->_ENGINE_parse_body($tpl->div_success("{success}"));
}

// ── VLAN list table ──────────────────────────────────────────────────────

function vlan_list():void{
    $tpl=new template_admin();
    $id=intval($_GET["list"]);
    $page=CurrentPageName();

    $vlans=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/vlan/$id"));
    if(!is_array($vlans)){
        if(netagent_is_error($vlans)){
            echo $tpl->_ENGINE_parse_body($tpl->div_error(netagent_err($vlans)));
            return;
        }
        $vlans=[];
    }

    $h=[];

    // Top buttons
    $h[]="<div style='text-align:right;margin-bottom:10px'>";
    $h[]="<button class='btn btn-success btn-sm' OnClick=\"Loadjs('$page?vlan-add-js=$id')\">";
    $h[]="<i class='fas fa-plus'></i>&nbsp; {new_vlan}</button>";
    $h[]="&nbsp; <button class='btn btn-info btn-sm' OnClick=\"VlanReconcileAll_$id()\">";
    $h[]="<i class='fas fa-sync'></i>&nbsp; {rebuild}</button>";
    $h[]="</div>";

    if(count($vlans)===0){
        $h[]=$tpl->div_info("{no_data}");
        echo $tpl->_ENGINE_parse_body(implode("\n",$h));
        return;
    }

    $t=time();
    $h[]="<table id='table-$t' class='table table-stripped' data-page-size='50'>";
    $h[]="<thead><tr>";
    $h[]="<th data-sortable='true' data-type='number'>ID</th>";
    $h[]="<th data-sortable='true' data-type='text'>{name}</th>";
    $h[]="<th data-sortable='true' data-type='text'></th>";
    $h[]="<th data-sortable='true' data-type='text'></th>";
    $h[]="<th>MTU</th>";
    $h[]="<th>{state}</th>";
    $h[]="<th>IP(s)</th>";
    $h[]="<th>{actions}</th>";
    $h[]="</tr></thead><tbody>";

    $TRCLASS=null;
    foreach($vlans as $v){
        if(!is_object($v)) continue;
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $vid    =htmlspecialchars($v->id ?? '');
        $tag    =intval($v->vlan_id ?? 0);
        $name   =htmlspecialchars($v->name ?? '');
        $mtu    =intval($v->mtu ?? 0);
        $parent =htmlspecialchars($v->parent_interface ?? '');
        $enabled=($v->enabled ?? false);

        // State from embedded state object
        $state    =$v->state ?? null;
        $ifName   =htmlspecialchars($state->interface_name ?? "$parent.$tag");
        $linkUp   =($state->link_up ?? false);
        $applied  =($state->applied ?? false);
        $lastError=($state->last_error ?? '');

        // State badge
        if(!$enabled){
            $stateBadge="<span class='label label-default'>{disabled}</span>";
        }elseif($linkUp){
            $stateBadge="<span class='label label-success' style='color:#fff'>UP</span>";
        }elseif($applied){
            $stateBadge="<span class='label label-warning' style='color:#fff'>DOWN</span>";
        }else{
            $stateBadge="<span class='label label-danger' style='color:#fff'>{not_applied}</span>";
        }

        if($lastError!==''){
            $errTip=htmlspecialchars($lastError);
            $stateBadge.=" <span class='text-danger' title='$errTip'><i class='fas fa-exclamation-circle'></i></span>";
        }

        // Count addresses
        $addrCount=0;
        if(isset($v->ipv4->addresses)&&is_array($v->ipv4->addresses)){$addrCount+=count($v->ipv4->addresses);}
        if(isset($v->ipv6->addresses)&&is_array($v->ipv6->addresses)){$addrCount+=count($v->ipv6->addresses);}

        // Action buttons
        $safeVid=htmlspecialchars(json_encode($vid),ENT_QUOTES);
        $safeLabel=htmlspecialchars(json_encode("$name ($tag)"),ENT_QUOTES);

        $editJs    ="Loadjs('$page?vlan-edit-js=$id&vid=".urlencode($vid)."')";
        $deleteJs  ="VlanDelete_$id($safeVid,$safeLabel)";
        $applyJs   ="VlanApply_$id($safeVid)";
        $metricsJs ="Loadjs('$page?metrics-js=$id&vid=".urlencode($vid)."')";

        $actions ="<button class='btn btn-xs btn-primary' OnClick=\"$editJs\" title='{edit}'>";
        $actions.="<i class='fas fa-edit'></i></button> ";
        $actions.="<button class='btn btn-xs btn-danger' OnClick=\"$deleteJs\" title='{delete}'>";
        $actions.="<i class='fas fa-trash'></i></button> ";
        if($enabled){
            $actions.="<button class='btn btn-xs btn-success' OnClick=\"$applyJs\" title='{apply}'>";
            $actions.="<i class='fas fa-play'></i></button> ";
        }
        $actions.="<button class='btn btn-xs btn-info' OnClick=\"$metricsJs\" title='{metrics}'>";
        $actions.="<i class='fas fa-chart-line'></i></button>";

        $h[]="<tr class='$TRCLASS'>";
        $h[]="<td style='width:1%' nowrap><span class='badge' style='background:#9b59b6;color:#fff'>$tag</span></td>";
        $h[]="<td>$name</td>";
        $h[]="<td style='width:1%' nowrap><span style='font-family:monospace'>$ifName</span></td>";
        $h[]="<td style='width:1%' nowrap><span style='font-family:monospace'>$parent</span></td>";
        $h[]="<td style='width:1%' nowrap>".($mtu>0?$mtu:"{inherit}")."</td>";
        $h[]="<td style='width:1%' nowrap>$stateBadge</td>";
        $h[]="<td style='width:1%' nowrap>$addrCount</td>";
        $h[]="<td style='width:1%' nowrap>$actions</td>";
        $h[]="</tr>";
    }

    $h[]="</tbody>";
    $h[]="<tfoot><tr><td colspan='8'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $h[]="</table>";
    $h[]="<script>NoSpinner();\n".implode("\n",$tpl->ICON_SCRIPTS);
    $h[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Add VLAN dialog opener ───────────────────────────────────────────────

function vlan_add_js():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["vlan-add-js"]);
    $tpl->js_dialog5("{new_vlan}","$page?vlan-add-form=$id",820);
}

// ── Add VLAN form ────────────────────────────────────────────────────────

function vlan_add_form():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["vlan-add-form"]);

    // Fetch available parent interfaces
    $support=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/vlan/$id/support"));
    $ifaces=[];
    if(is_object($support)&&is_array($support->interfaces ?? null)){
        $ifaces=$support->interfaces;
    }

    $h=[];
    $h[]="<div id='vlan-form-result-$id'></div>";
    $h[]="<form id='vlan-add-frm-$id'>";
    $h[]="<input type='hidden' name='vlan-save' value='$id'>";

    // Identity section
    $h[]="<div class='row'>";
    $h[]="  <div class='col-md-6'>";
    $h[]="    <div class='form-group'>";
    $h[]="      <label>{parent} {interface}</label>";
    $h[]="      <select id='vlan-parent-$id' name='parent_interface' class='form-control'>";
    foreach($ifaces as $iface){
        $esc=htmlspecialchars($iface);
        $h[]="        <option value='$esc'>$esc</option>";
    }
    $h[]="      </select>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="  <div class='col-md-3'>";
    $h[]="    <div class='form-group'>";
    $h[]="      <label>{vlan_id} (1-4094)</label>";
    $h[]="      <input type='number' id='vlan-tag-$id' name='vlan_id' class='form-control' min='1' max='4094' value='100'>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="  <div class='col-md-3'>";
    $h[]="    <div class='form-group'>";
    $h[]="      <label>MTU (0={inherit})</label>";
    $h[]="      <input type='number' id='vlan-mtu-$id' name='mtu' class='form-control' min='0' max='9216' value='0'>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="</div>";

    $h[]="<div class='row'>";
    $h[]="  <div class='col-md-8'>";
    $h[]="    <div class='form-group'>";
    $h[]="      <label>{name}</label>";
    $h[]="      <input type='text' id='vlan-name-$id' name='name' class='form-control' placeholder='Management VLAN'>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="  <div class='col-md-4' style='padding-top:28px'>";
    $h[]="    <label><input type='checkbox' id='vlan-enabled-$id' name='enabled' value='1' checked> {enabled}</label>";
    $h[]="  </div>";
    $h[]="</div>";

    // IPv4 section
    $h[]="<h4 style='margin-top:15px;border-bottom:1px solid #e7eaec;padding-bottom:5px'>IPv4</h4>";
    $h[]="<div id='vlan-ipv4-addrs-$id'></div>";
    $h[]="<button type='button' class='btn btn-xs btn-default' style='margin-bottom:10px' ";
    $h[]="  onclick=\"VlanAddAddrRow_$id('vlan-ipv4-addrs-$id','ipv4-addr','10.0.0.1/24')\">";
    $h[]="<i class='fas fa-plus'></i> {add_address}</button>";

    $h[]="<div id='vlan-ipv4-routes-$id'></div>";
    $h[]="<button type='button' class='btn btn-xs btn-default' style='margin-bottom:10px' ";
    $h[]="  onclick=\"VlanAddRouteRow_$id('vlan-ipv4-routes-$id','ipv4-route')\">";
    $h[]="<i class='fas fa-plus'></i> {new_route}</button>";

    // IPv6 section
    $h[]="<h4 style='margin-top:15px;border-bottom:1px solid #e7eaec;padding-bottom:5px'>IPv6</h4>";
    $h[]="<div id='vlan-ipv6-addrs-$id'></div>";
    $h[]="<button type='button' class='btn btn-xs btn-default' style='margin-bottom:10px' ";
    $h[]="  onclick=\"VlanAddAddrRow_$id('vlan-ipv6-addrs-$id','ipv6-addr','fd00::1/64')\">";
    $h[]="<i class='fas fa-plus'></i> {add_address}</button>";

    $h[]="<div id='vlan-ipv6-routes-$id'></div>";
    $h[]="<button type='button' class='btn btn-xs btn-default' style='margin-bottom:10px' ";
    $h[]="  onclick=\"VlanAddRouteRow_$id('vlan-ipv6-routes-$id','ipv6-route')\">";
    $h[]="<i class='fas fa-plus'></i> {new_route}</button>";

    $h[]="</form>";

    // Save button
    $h[]="<div style='text-align:right;margin-top:15px'>";
    $h[]="<button class='btn btn-primary' OnClick=\"VlanSaveNew_$id()\">";
    $h[]="<i class='fas fa-save'></i>&nbsp; {save}</button>";
    $h[]="</div>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Save new VLAN (POST handler) ─────────────────────────────────────────

function vlan_save():void{
    $tpl=new template_admin();
    $id=intval($_POST["vlan-save"]);

    $data=[
        "parent_interface" => trim($_POST["parent_interface"] ?? ""),
        "vlan_id"          => intval($_POST["vlan_id"] ?? 0),
        "name"             => trim($_POST["name"] ?? ""),
        "enabled"          => ($_POST["enabled"] ?? "0")==="1",
        "mtu"              => intval($_POST["mtu"] ?? 0),
        "ipv4"             => json_decode($_POST["ipv4_json"] ?? "{}"),
        "ipv6"             => json_decode($_POST["ipv6_json"] ?? "{}"),
    ];

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/vlan/$id",$data
    ));

    if(netagent_is_error($json)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(netagent_err($json)));
        return;
    }

    // Auto-apply after creation if enabled
    $vid=$json->id ?? '';
    if($vid!==''&&($data["enabled"] ?? false)){
        $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/vlan/$id/$vid/apply",[]);
    }

    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {vlan_created}"));
}

// ── Edit VLAN dialog opener ──────────────────────────────────────────────

function vlan_edit_js():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["vlan-edit-js"]);
    $vid=trim($_GET["vid"] ?? "");
    $tpl->js_dialog5("{edit}","$page?vlan-edit-form=$id&vid=".urlencode($vid),820);
}

// ── Edit VLAN form ───────────────────────────────────────────────────────

function vlan_edit_form():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["vlan-edit-form"]);
    $vid=trim($_GET["vid"] ?? "");

    // Fetch current VLAN config
    $v=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/vlan/$id/".rawurlencode($vid)));
    if(!is_object($v)||(isset($v->Status)&&!$v->Status)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(netagent_err($v)));
        return;
    }

    $safeVid=htmlspecialchars($vid);
    $parentIf=htmlspecialchars($v->parent_interface ?? '');
    $tag=intval($v->vlan_id ?? 0);
    $vname=htmlspecialchars($v->name ?? '');
    $mtu=intval($v->mtu ?? 0);
    $enabled=($v->enabled ?? false);
    $enabledChk=$enabled?"checked":"";

    $h=[];
    $h[]="<div id='vlan-form-result-$id'></div>";
    $h[]="<form id='vlan-edit-frm-$id'>";
    $h[]="<input type='hidden' name='vlan-update' value='$id'>";
    $h[]="<input type='hidden' name='vid' value='$safeVid'>";
    $h[]="<input type='hidden' name='parent_interface' value='$parentIf'>";
    $h[]="<input type='hidden' name='vlan_id' value='$tag'>";

    // Identity section (parent + tag are read-only)
    $h[]="<div class='row'>";
    $h[]="  <div class='col-md-6'>";
    $h[]="    <div class='form-group'>";
    $h[]="      <label>{parent} {interface}</label>";
    $h[]="      <input type='text' class='form-control' value='$parentIf' disabled>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="  <div class='col-md-3'>";
    $h[]="    <div class='form-group'>";
    $h[]="      <label>{vlan_id}</label>";
    $h[]="      <input type='text' class='form-control' value='$tag' disabled>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="  <div class='col-md-3'>";
    $h[]="    <div class='form-group'>";
    $h[]="      <label>MTU (0={inherit})</label>";
    $h[]="      <input type='number' id='vlan-mtu-$id' name='mtu' class='form-control' min='0' max='9216' value='$mtu'>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="</div>";

    $h[]="<div class='row'>";
    $h[]="  <div class='col-md-8'>";
    $h[]="    <div class='form-group'>";
    $h[]="      <label>{name}</label>";
    $h[]="      <input type='text' id='vlan-name-$id' name='name' class='form-control' value='$vname'>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="  <div class='col-md-4' style='padding-top:28px'>";
    $h[]="    <label><input type='checkbox' id='vlan-enabled-$id' name='enabled' value='1' $enabledChk> {enabled}</label>";
    $h[]="  </div>";
    $h[]="</div>";

    // IPv4 section
    $h[]="<h4 style='margin-top:15px;border-bottom:1px solid #e7eaec;padding-bottom:5px'>IPv4</h4>";
    $h[]="<div id='vlan-ipv4-addrs-$id'>";
    if(isset($v->ipv4->addresses)&&is_array($v->ipv4->addresses)){
        foreach($v->ipv4->addresses as $addr){
            $cidr=htmlspecialchars($addr->cidr ?? '');
            $h[]=vlan_addr_row_html("ipv4-addr",$cidr);
        }
    }
    $h[]="</div>";
    $h[]="<button type='button' class='btn btn-xs btn-default' style='margin-bottom:10px' ";
    $h[]="  onclick=\"VlanAddAddrRow_$id('vlan-ipv4-addrs-$id','ipv4-addr','10.0.0.1/24')\">";
    $h[]="<i class='fas fa-plus'></i> {add_address}</button>";

    $h[]="<div id='vlan-ipv4-routes-$id'>";
    if(isset($v->ipv4->routes)&&is_array($v->ipv4->routes)){
        foreach($v->ipv4->routes as $rt){
            $dest=htmlspecialchars($rt->destination ?? '');
            $gw=htmlspecialchars($rt->gateway ?? '');
            $metric=intval($rt->metric ?? 0);
            $h[]=vlan_route_row_html("ipv4-route",$dest,$gw,$metric);
        }
    }
    $h[]="</div>";
    $h[]="<button type='button' class='btn btn-xs btn-default' style='margin-bottom:10px' ";
    $h[]="  onclick=\"VlanAddRouteRow_$id('vlan-ipv4-routes-$id','ipv4-route')\">";
    $h[]="<i class='fas fa-plus'></i> {new_route}</button>";

    // IPv6 section
    $h[]="<h4 style='margin-top:15px;border-bottom:1px solid #e7eaec;padding-bottom:5px'>IPv6</h4>";
    $h[]="<div id='vlan-ipv6-addrs-$id'>";
    if(isset($v->ipv6->addresses)&&is_array($v->ipv6->addresses)){
        foreach($v->ipv6->addresses as $addr){
            $cidr=htmlspecialchars($addr->cidr ?? '');
            $h[]=vlan_addr_row_html("ipv6-addr",$cidr);
        }
    }
    $h[]="</div>";
    $h[]="<button type='button' class='btn btn-xs btn-default' style='margin-bottom:10px' ";
    $h[]="  onclick=\"VlanAddAddrRow_$id('vlan-ipv6-addrs-$id','ipv6-addr','fd00::1/64')\">";
    $h[]="<i class='fas fa-plus'></i> {add_address}</button>";

    $h[]="<div id='vlan-ipv6-routes-$id'>";
    if(isset($v->ipv6->routes)&&is_array($v->ipv6->routes)){
        foreach($v->ipv6->routes as $rt){
            $dest=htmlspecialchars($rt->destination ?? '');
            $gw=htmlspecialchars($rt->gateway ?? '');
            $metric=intval($rt->metric ?? 0);
            $h[]=vlan_route_row_html("ipv6-route",$dest,$gw,$metric);
        }
    }
    $h[]="</div>";
    $h[]="<button type='button' class='btn btn-xs btn-default' style='margin-bottom:10px' ";
    $h[]="  onclick=\"VlanAddRouteRow_$id('vlan-ipv6-routes-$id','ipv6-route')\">";
    $h[]="<i class='fas fa-plus'></i> {new_route}</button>";

    $h[]="</form>";

    // Save button
    $h[]="<div style='text-align:right;margin-top:15px'>";
    $h[]="<button class='btn btn-primary' OnClick=\"VlanSaveEdit_$id()\">";
    $h[]="<i class='fas fa-save'></i>&nbsp; {save}</button>";
    $h[]="</div>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── HTML helpers for address/route rows ──────────────────────────────────

function vlan_addr_row_html(string $cls,string $value=""):string{
    $esc=htmlspecialchars($value);
    return "<div class='input-group' style='margin-bottom:5px'>"
        ."<input type='text' class='form-control $cls' value='$esc' placeholder='CIDR'>"
        ."<span class='input-group-btn'>"
        ."<button class='btn btn-danger btn-sm' type='button' onclick=\"$(this).closest('.input-group').remove()\">"
        ."<i class='fas fa-times'></i></button>"
        ."</span></div>";
}

function vlan_route_row_html(string $cls,string $dest="",string $gw="",int $metric=0):string{
    $escD=htmlspecialchars($dest);
    $escG=htmlspecialchars($gw);
    return "<div class='row $cls-row' style='margin-bottom:5px'>"
        ."<div class='col-md-5'><input type='text' class='form-control route-dest' value='$escD' placeholder='{destination} CIDR'></div>"
        ."<div class='col-md-4'><input type='text' class='form-control route-gw' value='$escG' placeholder='{gateway}'></div>"
        ."<div class='col-md-2'><input type='number' class='form-control route-metric' value='$metric' placeholder='metric' min='0'></div>"
        ."<div class='col-md-1'><button class='btn btn-danger btn-sm' type='button' onclick=\"$(this).closest('.row').remove()\">"
        ."<i class='fas fa-times'></i></button></div>"
        ."</div>";
}

// ── Update VLAN (POST handler) ───────────────────────────────────────────

function vlan_update():void{
    $tpl=new template_admin();
    $id=intval($_POST["vlan-update"]);
    $vid=trim($_POST["vid"] ?? "");

    $data=[
        "parent_interface" => trim($_POST["parent_interface"] ?? ""),
        "vlan_id"          => intval($_POST["vlan_id"] ?? 0),
        "name"             => trim($_POST["name"] ?? ""),
        "enabled"          => ($_POST["enabled"] ?? "0")==="1",
        "mtu"              => intval($_POST["mtu"] ?? 0),
        "ipv4"             => json_decode($_POST["ipv4_json"] ?? "{}"),
        "ipv6"             => json_decode($_POST["ipv6_json"] ?? "{}"),
    ];

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/vlan/$id/".rawurlencode($vid),$data
    ));

    if(netagent_is_error($json)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(netagent_err($json)));
        return;
    }

    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {success}"));
}

// ── Delete VLAN (POST handler) ───────────────────────────────────────────

function vlan_delete():void{
    $tpl=new template_admin();
    $id=intval($_POST["vlan-delete"]);
    $vid=trim($_POST["vid"] ?? "");

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE(
        "/netagents/vlan/$id/".rawurlencode($vid)
    ));

    if(netagent_is_error($json)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(netagent_err($json)));
        return;
    }

    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {deleted}"));
}

// ── Apply single VLAN (POST handler) ─────────────────────────────────────

function vlan_apply():void{
    $tpl=new template_admin();
    $id=intval($_POST["vlan-apply"]);
    $vid=trim($_POST["vid"] ?? "");

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/vlan/$id/".rawurlencode($vid)."/apply",[]
    ));

    if(netagent_is_error($json)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(netagent_err($json)));
        return;
    }

    $h=[];
    $ok=($json->success ?? false);
    $ifName=htmlspecialchars($json->interface_name ?? '');
    if($ok){
        $h[]=$tpl->div_success("<i class='fas fa-check-circle'></i> $ifName {applied}");
    }else{
        $h[]=$tpl->div_warning("<i class='fas fa-exclamation-triangle'></i> $ifName: {error}");
    }
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Reconcile all VLANs (POST handler) ───────────────────────────────────

function vlan_reconcile_all():void{
    $tpl=new template_admin();
    $id=intval($_POST["reconcile-all"]);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/vlan/$id/reconcile",[]
    ));

    if(!is_array($json)){
        if(netagent_is_error($json)){
            echo $tpl->_ENGINE_parse_body($tpl->div_error(netagent_err($json)));
            return;
        }
        echo $tpl->_ENGINE_parse_body($tpl->div_success("{success}"));
        return;
    }

    $okCount=0;$errCount=0;
    foreach($json as $r){
        if(!is_object($r)) continue;
        if($r->success ?? false){$okCount++;}else{$errCount++;}
    }

    if($errCount>0){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning(
            "<i class='fas fa-exclamation-triangle'></i> $okCount {success}, $errCount {errors}"
        ));
    }else{
        echo $tpl->_ENGINE_parse_body($tpl->div_success(
            "<i class='fas fa-check-circle'></i> $okCount VLANs {applied}"
        ));
    }
}

// ── Metrics dialog opener ────────────────────────────────────────────────

function vlan_metrics_js():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["metrics-js"]);
    $vid=trim($_GET["vid"] ?? "");
    $tpl->js_dialog7(
        "<i class='fas fa-chart-area'></i> VLAN {metrics}",
        "$page?metrics-shell=$id&vid=".urlencode($vid),
        1050
    );
}

// ── Metrics shell (period buttons + container, loaded once by dialog) ────

function vlan_metrics_shell():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["metrics-shell"]);
    $vid=trim($_GET["vid"] ?? "");
    $vidEnc=urlencode($vid);

    $h=[];
    $h[]="<div style='margin-bottom:12px;margin-top:8px'>";
    $periods=["last-hour"=>"{last_hour}","today"=>"{today}","last-24h"=>"24h","last-7-days"=>"{last_7_days}","last-30-days"=>"30d"];
    foreach($periods as $pval=>$plabel){
        $active=($pval==="last-hour")?"btn-primary":"btn-white";
        $h[]="<button type='button' class='btn btn-sm $active' id='vlan-mbtn-$pval-$id'";
        $h[]="  onclick=\"VlanMetricsPeriod_$id('$pval')\">$plabel</button>";
    }
    $h[]="</div>";
    $h[]="<div id='vlan-metrics-content-$id'></div>";
    $h[]="<script>";
    $h[]="function VlanMetricsPeriod_$id(p){";
    $h[]="  $('#vlan-metrics-content-$id').parent().find('.btn-primary').removeClass('btn-primary').addClass('btn-white');";
    $h[]="  document.getElementById('vlan-mbtn-'+p+'-$id').className=document.getElementById('vlan-mbtn-'+p+'-$id').className.replace('btn-white','btn-primary');";
    $h[]="  LoadAjax('vlan-metrics-content-$id','$page?metrics-charts=$id&vid=$vidEnc&period='+p);";
    $h[]="}";
    $h[]="VlanMetricsPeriod_$id('last-hour');";
    $h[]="</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Metrics charts (content only, no buttons) ────────────────────────────

function vlan_metrics_charts():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["metrics-charts"]);
    $vid=trim($_GET["vid"] ?? "");
    $period=$_GET["period"] ?? "last-hour";
    $ok_periods=["last-hour","today","last-24h","last-7-days","last-30-days"];
    if(!in_array($period,$ok_periods)){$period="last-hour";}

    $h=[];

    // Fetch metrics
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/vlan/$id/".rawurlencode($vid)."/metrics?period=$period");
    $mj=json_decode($raw);

    if(netagent_is_error($mj)){
        $h[]=$tpl->div_error(netagent_err($mj));
        $h[]="</div>";
        echo $tpl->_ENGINE_parse_body(implode("\n",$h));
        return;
    }

    $data=$mj->data ?? [];
    if(!is_array($data)||count($data)===0){
        $h[]=$tpl->div_info("{no_data}");
        $h[]="</div>";
        echo $tpl->_ENGINE_parse_body(implode("\n",$h));
        return;
    }

    // Build chart arrays
    $labels=[];
    $rxBpsAvg=[];$txBpsAvg=[];$rxBpsMax=[];$txBpsMax=[];
    $rxPpsAvg=[];$txPpsAvg=[];$rxPpsMax=[];$txPpsMax=[];
    $date_fmt=($period==="last-hour"||$period==="today")?"H:i":"M d H:00";

    foreach($data as $pt){
        if(!is_object($pt)) continue;
        $ts=$pt->time ?? '';
        if(!empty($ts)){
            try{$dt=new DateTime($ts);$labels[]=$dt->format($date_fmt);}
            catch(\Exception $e){$labels[]="";}
        }else{$labels[]='';}

        $r=$pt->rollup ?? null;
        $rxBpsAvg[]=round(($r->RxBpsAvg ?? 0)/1024,2);
        $txBpsAvg[]=round(($r->TxBpsAvg ?? 0)/1024,2);
        $rxBpsMax[]=round(($r->RxBpsMax ?? 0)/1024,2);
        $txBpsMax[]=round(($r->TxBpsMax ?? 0)/1024,2);
        $rxPpsAvg[]=intval($r->RxPpsAvg ?? 0);
        $txPpsAvg[]=intval($r->TxPpsAvg ?? 0);
        $rxPpsMax[]=intval($r->RxPpsMax ?? 0);
        $txPpsMax[]=intval($r->TxPpsMax ?? 0);
    }

    // Auto-scale bandwidth unit
    $unit="KB/s";
    $maxBw=max(array_merge($rxBpsAvg,$txBpsAvg,$rxBpsMax,$txBpsMax,[0]));
    if($maxBw>1024){
        $unit="MB/s";
        $rxBpsAvg=array_map(fn($v)=>round($v/1024,2),$rxBpsAvg);
        $txBpsAvg=array_map(fn($v)=>round($v/1024,2),$txBpsAvg);
        $rxBpsMax=array_map(fn($v)=>round($v/1024,2),$rxBpsMax);
        $txBpsMax=array_map(fn($v)=>round($v/1024,2),$txBpsMax);
    }

    // Format arrays for JS (avoid PHP float precision issues)
    $fmtF=fn(array $a):string=>'['.implode(',',array_map(fn($v)=>sprintf("%.2f",$v),$a)).']';
    $fmtI=fn(array $a):string=>'['.implode(',',array_map('intval',$a)).']';
    $labelsJs=json_encode($labels);
    $rxBwJs=$fmtF($rxBpsAvg);  $txBwJs=$fmtF($txBpsAvg);
    $rxBwMaxJs=$fmtF($rxBpsMax); $txBwMaxJs=$fmtF($txBpsMax);
    $rxPpsJs=$fmtI($rxPpsAvg);  $txPpsJs=$fmtI($txPpsAvg);
    $rxPpsMaxJs=$fmtI($rxPpsMax); $txPpsMaxJs=$fmtI($txPpsMax);

    $t=time();

    // Bandwidth chart
    $h[]="<div class='ibox'>";
    $h[]="  <div class='ibox-title'><h5><i class='fas fa-tachometer-alt' style='color:#1c84c6'></i>&nbsp; {bandwidth}</h5></div>";
    $h[]="  <div class='ibox-content'><div style='height:300px'><canvas id='vlan-bw-$t'></canvas></div></div>";
    $h[]="</div>";

    // Packets chart
    $h[]="<div class='ibox'>";
    $h[]="  <div class='ibox-title'><h5><i class='fas fa-stream' style='color:#ab7df6'></i>&nbsp; {packets}</h5></div>";
    $h[]="  <div class='ibox-content'><div style='height:250px'><canvas id='vlan-pkt-$t'></canvas></div></div>";
    $h[]="</div>";

    // Chart.js script (must be in same HTML response)
    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";
    $h[]="<script>";

    // Shared options
    $h[]="var _vlOpts=function(yLabel){return{";
    $h[]="  responsive:true,maintainAspectRatio:false,";
    $h[]="  interaction:{mode:'index',intersect:false},";
    $h[]="  scales:{x:{ticks:{maxTicksLimit:20,font:{size:10}}},y:{beginAtZero:true,title:{display:!!yLabel,text:yLabel||''}}},";
    $h[]="  elements:{point:{radius:1,hoverRadius:4},line:{tension:0.3,borderWidth:2}},";
    $h[]="  plugins:{legend:{position:'top',labels:{usePointStyle:true,padding:12}},tooltip:{mode:'index',intersect:false}}";
    $h[]="};};";

    // Bandwidth
    $h[]="(function(){";
    $h[]="new Chart(document.getElementById('vlan-bw-$t'),{type:'line',data:{labels:$labelsJs,datasets:[";
    $h[]="  {label:'RX Avg ($unit)',data:$rxBwJs,borderColor:'rgb(28,132,198)',backgroundColor:'rgba(28,132,198,0.08)',fill:true},";
    $h[]="  {label:'TX Avg ($unit)',data:$txBwJs,borderColor:'rgb(248,172,89)',backgroundColor:'rgba(248,172,89,0.08)',fill:true},";
    $h[]="  {label:'RX Peak ($unit)',data:$rxBwMaxJs,borderColor:'rgba(28,132,198,0.4)',borderDash:[5,5],fill:false,borderWidth:1,pointRadius:0},";
    $h[]="  {label:'TX Peak ($unit)',data:$txBwMaxJs,borderColor:'rgba(248,172,89,0.4)',borderDash:[5,5],fill:false,borderWidth:1,pointRadius:0}";
    $h[]="]},options:_vlOpts('$unit')});";
    $h[]="})();";

    // Packets
    $h[]="(function(){";
    $h[]="new Chart(document.getElementById('vlan-pkt-$t'),{type:'line',data:{labels:$labelsJs,datasets:[";
    $h[]="  {label:'RX Avg pkt/s',data:$rxPpsJs,borderColor:'rgb(54,162,235)',fill:false},";
    $h[]="  {label:'TX Avg pkt/s',data:$txPpsJs,borderColor:'rgb(171,125,246)',fill:false},";
    $h[]="  {label:'RX Peak pkt/s',data:$rxPpsMaxJs,borderColor:'rgba(54,162,235,0.4)',borderDash:[5,5],fill:false,borderWidth:1,pointRadius:0},";
    $h[]="  {label:'TX Peak pkt/s',data:$txPpsMaxJs,borderColor:'rgba(171,125,246,0.4)',borderDash:[5,5],fill:false,borderWidth:1,pointRadius:0}";
    $h[]="]},options:_vlOpts('pkt/s')});";
    $h[]="})();";

    $h[]="</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── JavaScript block (agent-ID-scoped functions) ─────────────────────────

function vlan_js_block(int $id):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $h=[];
    $h[]="<script>";

    // Show result then auto-clear after 5s
    $h[]="function VlanFlash_$id(divId,html){";
    $h[]="  var el=document.getElementById(divId);";
    $h[]="  if(!el) return;";
    $h[]="  el.innerHTML=html;";
    $h[]="  setTimeout(function(){el.innerHTML='';},5000);";
    $h[]="}";

    // Refresh list
    $h[]="function VlanRefreshList_$id(){";
    $h[]="  LoadAjaxSilent('vlan-list-$id','$page?list=$id');";
    $h[]="}";

    // Load 8021q module
    $h[]="function VlanLoadModule_$id(){";
    $h[]="  $.post('$page',{'load-module':$id},function(r){";
    $h[]="    VlanFlash_$id('vlan-result-$id',r);";
    $h[]="    LoadAjaxSilent('vlan-support-$id','$page?support-badge=$id');";
    $h[]="  }).fail(function(){";
    $h[]="    $('#vlan-result-$id').html('<div class=\"alert alert-danger\">{error}</div>');";
    $h[]="  });";
    $h[]="}";

    // Delete (swal confirmation)
    $confirm_delete=$tpl->javascript_parse_text("{delete_this_rule}");
    $yes=$tpl->javascript_parse_text("{yes}");
    $cancel=$tpl->javascript_parse_text("{cancel}");
    $h[]="function VlanDelete_$id(vid,label){";
    $h[]="  swal({title:'$confirm_delete',text:label,type:'warning',";
    $h[]="    showCancelButton:true,confirmButtonColor:'#ed5565',confirmButtonText:'$yes',cancelButtonText:'$cancel'";
    $h[]="  },function(isConfirm){";
    $h[]="    if(!isConfirm) return;";
    $h[]="    $.post('$page',{'vlan-delete':$id,'vid':vid},function(r){";
    $h[]="      VlanFlash_$id('vlan-result-$id',r);";
    $h[]="      VlanRefreshList_$id();";
    $h[]="    });";
    $h[]="  });";
    $h[]="}";

    // Apply single VLAN
    $h[]="function VlanApply_$id(vid){";
    $h[]="  $.post('$page',{'vlan-apply':$id,'vid':vid},function(r){";
    $h[]="    VlanFlash_$id('vlan-result-$id',r);";
    $h[]="    VlanRefreshList_$id();";
    $h[]="  });";
    $h[]="}";

    // Reconcile all (swal confirmation)
    $confirm_reconcile=$tpl->javascript_parse_text("{rebuild}");
    $build_the_network=$tpl->javascript_parse_text("{build_the_network}");

    $h[]="function VlanReconcileAll_$id(){";
    $h[]="  swal({title:'$confirm_reconcile',text:'$build_the_network',type:'info',";
    $h[]="    showCancelButton:true,confirmButtonColor:'#1c84c6',confirmButtonText:'$yes',cancelButtonText:'$cancel'";
    $h[]="  },function(isConfirm){";
    $h[]="    if(!isConfirm) return;";
    $h[]="    $.post('$page',{'reconcile-all':$id},function(r){";
    $h[]="      VlanFlash_$id('vlan-result-$id',r);";
    $h[]="      VlanRefreshList_$id();";
    $h[]="    });";
    $h[]="  });";
    $h[]="}";

    // Add address row dynamically
    $h[]="function VlanAddAddrRow_$id(containerId,cls,placeholder){";
    $h[]="  var row='<div class=\"input-group\" style=\"margin-bottom:5px\">';";
    $h[]="  row+='<input type=\"text\" class=\"form-control '+cls+'\" placeholder=\"'+placeholder+'\">';";
    $h[]="  row+='<span class=\"input-group-btn\">';";
    $h[]="  row+='<button class=\"btn btn-danger btn-sm\" type=\"button\" onclick=\"$(this).closest(\\'.input-group\\').remove()\">';";
    $h[]="  row+='<i class=\"fas fa-times\"></i></button></span></div>';";
    $h[]="  document.getElementById(containerId).insertAdjacentHTML('beforeend',row);";
    $h[]="}";

    // Add route row dynamically
    $h[]="function VlanAddRouteRow_$id(containerId,cls){";
    $h[]="  var row='<div class=\"row '+cls+'-row\" style=\"margin-bottom:5px\">';";
    $h[]="  row+='<div class=\"col-md-5\"><input type=\"text\" class=\"form-control route-dest\" placeholder=\"CIDR\"></div>';";
    $h[]="  row+='<div class=\"col-md-4\"><input type=\"text\" class=\"form-control route-gw\" placeholder=\"gateway\"></div>';";
    $h[]="  row+='<div class=\"col-md-2\"><input type=\"number\" class=\"form-control route-metric\" value=\"0\" min=\"0\"></div>';";
    $h[]="  row+='<div class=\"col-md-1\"><button class=\"btn btn-danger btn-sm\" type=\"button\" onclick=\"$(this).closest(\\'.row\\').remove()\">';";
    $h[]="  row+='<i class=\"fas fa-times\"></i></button></div></div>';";
    $h[]="  document.getElementById(containerId).insertAdjacentHTML('beforeend',row);";
    $h[]="}";

    // Collect form data and build JSON for ipv4/ipv6 sub-objects
    $h[]="function VlanCollectIP_$id(prefix){";
    $h[]="  var addrs=[];var routes=[];";
    $h[]="  $('#vlan-'+prefix+'-addrs-$id .'+prefix+'-addr').each(function(){";
    $h[]="    var v=$.trim($(this).val());if(v) addrs.push({cidr:v});";
    $h[]="  });";
    $h[]="  $('#vlan-'+prefix+'-routes-$id .'+prefix+'-route-row').each(function(){";
    $h[]="    var d=$.trim($(this).find('.route-dest').val());";
    $h[]="    if(d) routes.push({destination:d,gateway:$.trim($(this).find('.route-gw').val()),";
    $h[]="      metric:parseInt($(this).find('.route-metric').val())||0});";
    $h[]="  });";
    $h[]="  return {addresses:addrs,routes:routes};";
    $h[]="}";

    // Save new VLAN
    $h[]="function VlanSaveNew_$id(){";
    $h[]="  var tag=parseInt($('#vlan-tag-$id').val())||0;";
    $h[]="  if(tag<1||tag>4094){swal('{error}','VLAN ID: 1-4094','error');return;}";
    $h[]="  var mtu=parseInt($('#vlan-mtu-$id').val())||0;";
    $h[]="  if(mtu!==0&&(mtu<68||mtu>9216)){swal('{error}','MTU: 0 or 68-9216','error');return;}";
    $h[]="  var ipv4=VlanCollectIP_$id('ipv4');";
    $h[]="  var ipv6=VlanCollectIP_$id('ipv6');";
    $h[]="  $.post('$page',{";
    $h[]="    'vlan-save':$id,";
    $h[]="    'parent_interface':$('#vlan-parent-$id').val(),";
    $h[]="    'vlan_id':tag,";
    $h[]="    'name':$('#vlan-name-$id').val(),";
    $h[]="    'enabled':$('#vlan-enabled-$id').is(':checked')?'1':'0',";
    $h[]="    'mtu':mtu,";
    $h[]="    'ipv4_json':JSON.stringify(ipv4),";
    $h[]="    'ipv6_json':JSON.stringify(ipv6)";
    $h[]="  },function(r){";
    $h[]="    VlanFlash_$id('vlan-form-result-$id',r);";
    $h[]="    if(r.indexOf('panel-primary')!==-1){";
    $h[]="      VlanRefreshList_$id();";
    $h[]="      setTimeout(function(){dialogInstance5.close();},800);";
    $h[]="    }";
    $h[]="  });";
    $h[]="}";

    // Save edited VLAN
    $h[]="function VlanSaveEdit_$id(){";
    $h[]="  var mtu=parseInt($('#vlan-mtu-$id').val())||0;";
    $h[]="  if(mtu!==0&&(mtu<68||mtu>9216)){swal('{error}','MTU: 0 or 68-9216','error');return;}";
    $h[]="  var frm=$('#vlan-edit-frm-$id');";
    $h[]="  var ipv4=VlanCollectIP_$id('ipv4');";
    $h[]="  var ipv6=VlanCollectIP_$id('ipv6');";
    $h[]="  $.post('$page',{";
    $h[]="    'vlan-update':$id,";
    $h[]="    'vid':frm.find('input[name=vid]').val(),";
    $h[]="    'parent_interface':frm.find('input[name=parent_interface]').val(),";
    $h[]="    'vlan_id':frm.find('input[name=vlan_id]').val(),";
    $h[]="    'name':$('#vlan-name-$id').val(),";
    $h[]="    'enabled':$('#vlan-enabled-$id').is(':checked')?'1':'0',";
    $h[]="    'mtu':mtu,";
    $h[]="    'ipv4_json':JSON.stringify(ipv4),";
    $h[]="    'ipv6_json':JSON.stringify(ipv6)";
    $h[]="  },function(r){";
    $h[]="    VlanFlash_$id('vlan-form-result-$id',r);";
    $h[]="    if(r.indexOf('panel-primary')!==-1){";
    $h[]="      VlanRefreshList_$id();";
    $h[]="      setTimeout(function(){dialogInstance5.close();},800);";
    $h[]="    }";
    $h[]="  });";
    $h[]="}";

    $h[]="</script>";
    return $tpl->_ENGINE_parse_body(implode("\n",$h));
}
