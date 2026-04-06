<?php
/**
 * Advanced Routing Management for remote netagents.
 * Loaded as a tab from fw.netagents.network.php tabs() via:
 *   LoadAjaxTiny('tab-xxx', 'fw.netagents.network.routing.php?agent-id={id}')
 *
 * API:  /netagents/routing/{id}/inspect       — kernel state snapshot
 *       /netagents/routing/{id}/capabilities   — feature detection
 *       /netagents/routing/{id}/tables         — list / create custom tables
 *       /netagents/routing/{id}/tables/{tid}   — delete custom table
 *       /netagents/routing/{id}/routes         — list / add routes
 *       /netagents/routing/{id}/routes/delete  — delete route (POST with body)
 *       /netagents/routing/{id}/rules          — list / add rules
 *       /netagents/routing/{id}/rules/delete   — delete rule (POST with body)
 *       /netagents/routing/{id}/plan           — dry-run
 *       /netagents/routing/{id}/apply          — plan + apply with rollback
 *
 * Scope boundary with VLAN routes (fw.netagents.network.vlan.php):
 *   VLAN routes are per-VLAN sub-interface, managed via /netagents/vlan/{id}/{vid}/ipv4/route.
 *   Advanced routing is system-wide: custom tables, policy rules, blackhole, ECMP, fwmark.
 *   If a route belongs to a VLAN sub-interface, manage it through the VLAN page.
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
if(!$users->AsArticaMetaAdmin){exit();}

// POST dispatchers
if(isset($_POST["table-save"]))     {table_save();       exit;}
if(isset($_POST["table-delete"]))   {table_delete();     exit;}
if(isset($_POST["route-save"]))     {route_save();       exit;}
if(isset($_POST["route-delete"]))   {route_delete();     exit;}
if(isset($_POST["rule-save"]))      {rule_save();        exit;}
if(isset($_POST["rule-delete"]))    {rule_delete();      exit;}
if(isset($_POST["plan-run"]))       {plan_run();         exit;}
if(isset($_POST["apply-run"]))      {apply_run();        exit;}

// GET dispatchers
if(isset($_GET["capabilities"]))    {routing_capabilities();  exit;}
if(isset($_GET["inspect-tab"]))     {routing_inspect_tab();   exit;}
if(isset($_GET["tables-tab"]))      {routing_tables_tab();    exit;}
if(isset($_GET["routes-tab"]))      {routing_routes_tab();    exit;}
if(isset($_GET["rules-tab"]))       {routing_rules_tab();     exit;}
if(isset($_GET["table-add-js"]))    {table_add_js();          exit;}
if(isset($_GET["table-add-form"]))  {table_add_form();        exit;}
if(isset($_GET["route-add-js"]))    {route_add_js();          exit;}
if(isset($_GET["route-add-form"]))  {route_add_form();        exit;}
if(isset($_GET["rule-add-js"]))     {rule_add_js();           exit;}
if(isset($_GET["rule-add-form"]))   {rule_add_form();         exit;}
if(isset($_GET["plan-js"]))         {plan_js();               exit;}
if(isset($_GET["plan-form"]))       {plan_form();             exit;}

// Default entry — loaded by tabs_default
routing_start();

// ── Helpers ──────────────────────────────────────────────────────────────

function aid():int{
    return intval($_GET["agent-id"] ?? $_GET["inspect-tab"] ?? $_GET["tables-tab"]
        ?? $_GET["routes-tab"] ?? $_GET["rules-tab"] ?? $_GET["capabilities"]
        ?? $_GET["table-add-js"] ?? $_GET["table-add-form"]
        ?? $_GET["route-add-js"] ?? $_GET["route-add-form"]
        ?? $_GET["rule-add-js"] ?? $_GET["rule-add-form"]
        ?? $_GET["plan-js"] ?? $_GET["plan-form"] ?? 0);
}

function rt_err($json):string{
    if(!is_object($json)) return "{error}";
    return htmlspecialchars($json->error ?? $json->Error ?? "{error}");
}

function rt_is_error($json):bool{
    if(!is_object($json)) return true;
    if(isset($json->success) && $json->success===false) return true;
    if(isset($json->Status) && !$json->Status) return true;
    return false;
}

/** Badge for route type */
function rt_type_badge(string $type):string{
    $map=[
        "blackhole" =>"label-danger",
        "unreachable"=>"label-warning",
        "prohibit"  =>"label-warning",
        "unicast"   =>"label-success",
        "throw"     =>"label-default",
        "local"     =>"label-info",
        "broadcast" =>"label-info",
    ];
    $cls=$map[$type] ?? "label-default";
    $color=($cls==="label-default")?"":"color:#fff;";
    return "<span class='label $cls' style='$color'>".htmlspecialchars($type)."</span>";
}

/** Badge for rule action */
function rt_action_badge(string $action):string{
    $map=[
        "lookup"     =>"label-primary",
        "goto"       =>"label-info",
        "blackhole"  =>"label-danger",
        "unreachable"=>"label-warning",
        "prohibit"   =>"label-warning",
    ];
    $cls=$map[$action] ?? "label-default";
    $color=($cls==="label-default")?"":"color:#fff;";
    return "<span class='label $cls' style='$color'>".htmlspecialchars($action)."</span>";
}

/** Badge for plan action kind */
function rt_plan_badge(string $kind):string{
    $map=[
        "add"     =>"label-success",
        "delete"  =>"label-danger",
        "replace" =>"label-warning",
        "keep"    =>"label-default",
    ];
    $cls=$map[$kind] ?? "label-default";
    $color=($cls==="label-default")?"":"color:#fff;";
    return "<span class='label $cls' style='$color'>".strtoupper(htmlspecialchars($kind))."</span>";
}

/** Monospace CIDR display, "all" if empty */
function rt_cidr(string $v):string{
    if($v==='') return "all";
    return "<span style='font-family:monospace'>".htmlspecialchars($v)."</span>";
}

/** Monospace interface name */
function rt_iface(string $v):string{
    if($v==='') return "&mdash;";
    $label="<span style='font-family:monospace'>".htmlspecialchars($v)."</span>";
    // Annotate VLAN sub-interfaces
    if(preg_match('/\.\d+$/',$v)){
        $label.=" <span class='label label-default'><i class='fas fa-tag'></i> VLAN</span>";
    }
    return $label;
}

/** Table name badge */
function rt_table_badge($tbl):string{
    $name='';$tid=0;
    if(is_object($tbl)){
        $name=$tbl->name ?? '';
        $tid=intval($tbl->id ?? 0);
    }
    if($name==='') $name="$tid";
    return "<span class='badge' style='background:#2c3e50;color:#fff'>".htmlspecialchars($name)."</span>";
}

/** Fetch inspect data (cached in static var for single request) */
function rt_fetch_inspect(int $id):?object{
    static $cache=[];
    if(isset($cache[$id])) return $cache[$id];
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/routing/$id/inspect"));
    if(!is_object($json)||(isset($json->Status)&&!$json->Status)) return null;
    $cache[$id]=$json;
    return $json;
}

/** Fetch capabilities */
function rt_fetch_caps(int $id):?object{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/routing/$id/capabilities"));
    if(!is_object($json)||(isset($json->Status)&&!$json->Status)) return null;
    return $json;
}

/** Build interface dropdown options from inspect links */
function rt_iface_options(int $id,string $selected=""):string{
    $state=rt_fetch_inspect($id);
    $links=$state->links ?? [];
    $opts="<option value=''>--</option>";
    if(is_array($links)){
        foreach($links as $l){
            $nm=htmlspecialchars($l->name ?? '');
            $sel=($nm===$selected)?"selected":"";
            $opts.="<option value='$nm' $sel>$nm</option>";
        }
    }
    return $opts;
}

/** Build table dropdown options from inspect tables */
function rt_table_options(int $id,string $selectedName="main"):string{
    $state=rt_fetch_inspect($id);
    $tables=$state->tables ?? [];
    $opts="";
    if(is_array($tables)){
        foreach($tables as $t){
            $nm=htmlspecialchars($t->name ?? '');
            $tid=intval($t->id ?? 0);
            $sel=($nm===$selectedName)?"selected":"";
            $opts.="<option value='".htmlspecialchars(json_encode(["id"=>$tid,"name"=>$nm]),ENT_QUOTES)."' $sel>$nm ($tid)</option>";
        }
    }
    return $opts;
}
// ── routing_start() — main shell loaded into the tab pane ────────────────

function routing_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["agent-id"]);
    if($id<1){echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));return true;}

    $h=[];

    // Capabilities badge bar
    $h[]="<div id='rt-caps-$id' style='margin-bottom:12px;margin-top:10px'></div>";

    // Result area for feedback
    $h[]="<div id='rt-result-$id'></div>";

    // Tabs
    $tabs=[
        "{inspect}"         =>"$page?inspect-tab=$id",
        "{routing_tables}"  =>"$page?tables-tab=$id",
        "{routes}"          =>"$page?routes-tab=$id",
        "{policy_rules}"    =>"$page?rules-tab=$id",
    ];
    $h[]=$tpl->tabs_default($tabs);

    // Load capabilities on init
    $h[]="<script>LoadAjaxSilent('rt-caps-$id','$page?capabilities=$id');</script>";

    // JS functions scoped to this agent
    $h[]=routing_js_block($id);

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
    return true;
}

// ── Capabilities badge bar ───────────────────────────────────────────────

function routing_capabilities():void{
    $tpl=new template_admin();
    $id=intval($_GET["capabilities"]);

    $caps=rt_fetch_caps($id);
    if($caps===null){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{error}"));
        return;
    }

    $h=[];
    $items=[
        "IPv4"            =>($caps->ipv4 ?? false),
        "IPv6"            =>($caps->ipv6 ?? false),
        "Multipath"       =>($caps->multi_path ?? false),
        "Port Rules"      =>($caps->rule_port_range ?? false),
        "UID Rules"       =>($caps->rule_uid_range ?? false),
        "Managed Tables"  =>($caps->managed_rt_tables ?? false),
    ];

    foreach($items as $label=>$present){
        if($present){
            $h[]="<span class='label label-success' style='color:#fff'><i class='fas fa-check'></i> $label</span> ";
        }else{
            $h[]="<span class='label label-default'><i class='fas fa-times'></i> $label</span> ";
        }
    }

    echo $tpl->_ENGINE_parse_body(implode("",$h));
}

// ── TAB: INSPECT ─────────────────────────────────────────────────────────

function routing_inspect_tab():void{
    $tpl=new template_admin();
    $id=intval($_GET["inspect-tab"]);
    $page=CurrentPageName();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/routing/$id/inspect"));
    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(rt_err($json)));
        return;
    }

    $h=[];

    // Refresh button
    $h[]="<div style='text-align:right;margin-bottom:10px;margin-top:10px'>";
    $h[]="<button class='btn btn-default btn-sm' OnClick=\"LoadAjax('rt-inspect-$id','$page?inspect-tab=$id')\">";
    $h[]="<i class='fas fa-sync'></i>&nbsp; {refresh}</button>";
    $h[]="</div>";

    // ── Tables section ──
    $tables=$json->tables ?? [];
    $h[]="<div class='ibox'><div class='ibox-title'><h5>";
    $h[]="<i class='fas fa-layer-group'></i>&nbsp; {routing_tables}";
    $h[]="</h5></div><div class='ibox-content'>";
    if(is_array($tables)&&count($tables)>0){
        $h[]="<table class='table table-stripped'><thead><tr>";
        $h[]="<th>ID</th><th>{name}</th><th>{type}</th>";
        $h[]="</tr></thead><tbody>";
        foreach($tables as $t){
            $tid=intval($t->id ?? 0);
            $tname=htmlspecialchars($t->name ?? '');
            $builtIn=($t->built_in ?? false);
            $typeBadge=$builtIn
                ?"<span class='label label-default'>built-in</span>"
                :"<span class='label label-info' style='color:#fff'>custom</span>";
            $rowStyle=$builtIn?"style='color:#999'":"";
            $h[]="<tr $rowStyle><td>$tid</td><td><span style='font-family:monospace'>$tname</span></td><td>$typeBadge</td></tr>";
        }
        $h[]="</tbody></table>";
    }else{
        $h[]=$tpl->div_info("{no_data}");
    }
    $h[]="</div></div>";

    // ── Routes section ──
    $routes=$json->routes ?? [];
    $h[]="<div class='ibox'><div class='ibox-title'><h5>";
    $h[]="<i class='fas fa-route'></i>&nbsp; {routes}";
    $h[]="</h5></div><div class='ibox-content'>";
    if(is_array($routes)&&count($routes)>0){
        $h[]="<table class='table table-stripped'><thead><tr>";
        $h[]="<th>{destination}</th><th>{gateway}</th><th>{interface}</th><th>{type}</th>";
        $h[]="<th>Scope</th><th>Metric</th><th>Protocol</th><th>Table</th>";
        $h[]="</tr></thead><tbody>";
        foreach($routes as $r){
            $dst=rt_cidr($r->dst_cidr ?? '');
            $gw=!empty($r->gateway)?"<span style='font-family:monospace'>".htmlspecialchars($r->gateway)."</span>":"&mdash;";
            $iface=rt_iface($r->out_interface ?? '');
            $type=rt_type_badge($r->type ?? 'unicast');
            $scope=htmlspecialchars($r->scope ?? '');
            $metric=intval($r->metric ?? 0);
            $proto=htmlspecialchars($r->protocol ?? '');
            $tbl=rt_table_badge($r->table ?? null);

            // Multipath display
            $mp=$r->multi_path ?? [];
            if(is_array($mp)&&count($mp)>1){
                $gw="<i class='fas fa-code-branch text-info'></i> ECMP (".count($mp)." hops)";
            }

            $h[]="<tr><td>$dst</td><td>$gw</td><td>$iface</td><td>$type</td>";
            $h[]="<td>$scope</td><td>$metric</td><td>$proto</td><td>$tbl</td></tr>";
        }
        $h[]="</tbody></table>";
    }else{
        $h[]=$tpl->div_info("{no_data}");
    }
    $h[]="</div></div>";

    // ── Rules section ──
    $rules=$json->rules ?? [];
    $h[]="<div class='ibox'><div class='ibox-title'><h5>";
    $h[]="<i class='fas fa-gavel'></i>&nbsp; {policy_rules}";
    $h[]="</h5></div><div class='ibox-content'>";
    if(is_array($rules)&&count($rules)>0){
        $h[]="<table class='table table-stripped'><thead><tr>";
        $h[]="<th>Priority</th><th>Source</th><th>{destination}</th><th>FWMark</th>";
        $h[]="<th>{interface}</th><th>Table</th><th>Action</th>";
        $h[]="</tr></thead><tbody>";
        foreach($rules as $r){
            $prio=intval($r->priority ?? 0);
            $isSystem=in_array($prio,[0,32766,32767]);
            $rowStyle=$isSystem?"style='color:#999'":"";
            $src=rt_cidr($r->src_cidr ?? '');
            $dst=rt_cidr($r->dst_cidr ?? '');
            $fwmark=intval($r->fwmark ?? 0);
            $fwmarkStr=$fwmark>0?"<span style='font-family:monospace'>$fwmark</span>":"&mdash;";
            $iif=!empty($r->in_interface)?rt_iface($r->in_interface):"&mdash;";
            $tbl=rt_table_badge($r->table ?? null);
            $act=rt_action_badge($r->action ?? 'lookup');

            $h[]="<tr $rowStyle><td>$prio</td><td>$src</td><td>$dst</td><td>$fwmarkStr</td>";
            $h[]="<td>$iif</td><td>$tbl</td><td>$act</td></tr>";
        }
        $h[]="</tbody></table>";
    }else{
        $h[]=$tpl->div_info("{no_data}");
    }
    $h[]="</div></div>";

    // ── Links section ──
    $links=$json->links ?? [];
    if(is_array($links)&&count($links)>0){
        $h[]="<div class='ibox'><div class='ibox-title'><h5>";
        $h[]="<i class='fas fa-ethernet'></i>&nbsp; {network_interfaces}";
        $h[]="</h5></div><div class='ibox-content'>";
        $h[]="<div class='row'>";
        foreach($links as $l){
            $lname=htmlspecialchars($l->name ?? '');
            $lstate=($l->state ?? '')==='up';
            $lmtu=intval($l->mtu ?? 0);
            $addrs=$l->addrs ?? [];
            $stateBadge=$lstate
                ?"<span class='label label-success' style='color:#fff'>UP</span>"
                :"<span class='label label-danger' style='color:#fff'>DOWN</span>";

            $h[]="<div class='col-md-3' style='margin-bottom:10px'>";
            $h[]="<div class='panel panel-default' style='margin-bottom:0'>";
            $h[]="<div class='panel-heading' style='padding:8px 12px'>";
            $h[]="<span style='font-family:monospace;font-weight:bold'>$lname</span> $stateBadge";
            $h[]="</div><div class='panel-body' style='padding:8px 12px;font-size:12px'>";
            $h[]="MTU: $lmtu";
            if(is_array($addrs)){
                foreach($addrs as $addr){
                    $h[]="<br><span style='font-family:monospace'>".htmlspecialchars($addr)."</span>";
                }
            }
            $h[]="</div></div></div>";
        }
        $h[]="</div></div></div>";
    }

    $h[]="<script>NoSpinner();</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── TAB: TABLES ──────────────────────────────────────────────────────────

function routing_tables_tab():void{
    $tpl=new template_admin();
    $id=intval($_GET["tables-tab"]);
    $page=CurrentPageName();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/routing/$id/tables"));
    if(!is_array($json)){
        if(rt_is_error($json)){
            echo $tpl->_ENGINE_parse_body($tpl->div_error(rt_err($json)));
            return;
        }
        $json=[];
    }

    $h=[];

    // Top buttons
    $h[]="<div style='text-align:right;margin-bottom:10px;margin-top:10px'>";
    $h[]="<button class='btn btn-success btn-sm' OnClick=\"Loadjs('$page?table-add-js=$id')\">";
    $h[]="<i class='fas fa-plus'></i>&nbsp; {add}</button>";
    $h[]="&nbsp; <button class='btn btn-default btn-sm' OnClick=\"RtRefreshTables_$id()\">";
    $h[]="<i class='fas fa-sync'></i>&nbsp; {refresh}</button>";
    $h[]="</div>";

    if(count($json)===0){
        $h[]=$tpl->div_info("{no_data}");
        echo $tpl->_ENGINE_parse_body(implode("\n",$h));
        return;
    }

    $t=time();
    $h[]="<table id='table-$t' class='table table-stripped'>";
    $h[]="<thead><tr>";
    $h[]="<th>ID</th><th>{name}</th><th>{type}</th><th>{actions}</th>";
    $h[]="</tr></thead><tbody>";

    $TRCLASS=null;
    foreach($json as $tbl){
        if(!is_object($tbl)) continue;
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $tid=intval($tbl->id ?? 0);
        $tname=htmlspecialchars($tbl->name ?? '');
        $builtIn=($tbl->built_in ?? false);
        $typeBadge=$builtIn
            ?"<span class='label label-default'>built-in</span>"
            :"<span class='label label-info' style='color:#fff'>custom</span>";
        $rowStyle=$builtIn?"style='color:#999'":"";

        $actions="";
        if(!$builtIn){
            $safeName=htmlspecialchars(json_encode($tname),ENT_QUOTES);
            $actions="<button class='btn btn-xs btn-danger' OnClick=\"RtTableDelete_$id($tid,$safeName)\" title='{delete}'>";
            $actions.="<i class='fas fa-trash'></i></button>";
        }

        $h[]="<tr class='$TRCLASS' $rowStyle>";
        $h[]="<td style='width:1%' nowrap>$tid</td>";
        $h[]="<td><span style='font-family:monospace'>$tname</span></td>";
        $h[]="<td style='width:1%' nowrap>$typeBadge</td>";
        $h[]="<td style='width:1%' nowrap>$actions</td>";
        $h[]="</tr>";
    }

    $h[]="</tbody></table>";
    $h[]="<script>NoSpinner();</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Add Table dialog opener ──────────────────────────────────────────────

function table_add_js():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["table-add-js"]);
    $tpl->js_dialog5("{add} {routing_tables}","$page?table-add-form=$id",650);
}

// ── Add Table form ───────────────────────────────────────────────────────

function table_add_form():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["table-add-form"]);

    $h=[];
    $h[]="<div id='rt-tbl-result-$id'></div>";
    $h[]="<form id='rt-tbl-frm-$id'>";
    $h[]="<input type='hidden' name='table-save' value='$id'>";

    $h[]="<div class='row'>";
    $h[]="  <div class='col-md-4'>";
    $h[]="    <div class='form-group'>";
    $h[]="      <label>Table ID (1-252)</label>";
    $h[]="      <input type='number' id='rt-tbl-id-$id' name='table_id' class='form-control' min='1' max='252' value='200'>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="  <div class='col-md-8'>";
    $h[]="    <div class='form-group'>";
    $h[]="      <label>{name}</label>";
    $h[]="      <input type='text' id='rt-tbl-name-$id' name='name' class='form-control' placeholder='wan2' maxlength='64'>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="</div>";

    $h[]="<div class='row'>";
    $h[]="  <div class='col-md-6'>";
    $h[]="    <label><input type='checkbox' id='rt-tbl-persist-$id' name='persist' value='1' checked>";
    $h[]="    &nbsp; Persistent (/etc/iproute2/rt_tables)</label>";
    $h[]="  </div>";
    $h[]="  <div class='col-md-6'>";
    $h[]="    <div class='form-group'>";
    $h[]="      <label>{description}</label>";
    $h[]="      <input type='text' id='rt-tbl-desc-$id' name='description' class='form-control' placeholder='{description}'>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="</div>";

    $h[]="</form>";
    $h[]="<div style='text-align:right;margin-top:15px'>";
    $h[]="<button class='btn btn-primary' OnClick=\"RtTableSave_$id()\">";
    $h[]="<i class='fas fa-save'></i>&nbsp; {save}</button>";
    $h[]="</div>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Save table (POST handler) ────────────────────────────────────────────

function table_save():void{
    $tpl=new template_admin();
    $id=intval($_POST["table-save"]);

    $data=[
        "id"          =>intval($_POST["table_id"] ?? 0),
        "name"        =>trim($_POST["name"] ?? ""),
        "persist"     =>($_POST["persist"] ?? "0")==="1",
        "description" =>trim($_POST["description"] ?? ""),
    ];

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/routing/$id/tables",$data
    ));

    if(rt_is_error($json)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(rt_err($json)));
        return;
    }

    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {success}"));
}

// ── Delete table (POST handler) ──────────────────────────────────────────

function table_delete():void{
    $tpl=new template_admin();
    $id=intval($_POST["table-delete"]);
    $tid=intval($_POST["table_id"] ?? 0);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE(
        "/netagents/routing/$id/tables/$tid"
    ));

    if(rt_is_error($json)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(rt_err($json)));
        return;
    }

    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {deleted}"));
}

// ── TAB: ROUTES ──────────────────────────────────────────────────────────

function routing_routes_tab():void{
    $tpl=new template_admin();
    $id=intval($_GET["routes-tab"]);
    $page=CurrentPageName();
    $family=$_GET["family"] ?? "ipv4";
    $filterTable=$_GET["filter-table"] ?? "";

    $qs="?family=$family";
    if($filterTable!=='') $qs.="&table=".urlencode($filterTable);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/routing/$id/routes$qs"));
    if(!is_array($json)){
        if(rt_is_error($json)){
            echo $tpl->_ENGINE_parse_body($tpl->div_error(rt_err($json)));
            return;
        }
        $json=[];
    }

    $h=[];

    // Family toggle + filter + action buttons
    $h[]="<div style='margin-bottom:10px;margin-top:10px;display:flex;align-items:center;flex-wrap:wrap;gap:8px'>";
    $ipv4Active=($family==="ipv4")?"btn-primary":"btn-white";
    $ipv6Active=($family==="ipv6")?"btn-primary":"btn-white";
    $h[]="<button class='btn btn-sm $ipv4Active' OnClick=\"RtRoutesFamily_$id('ipv4')\">IPv4</button>";
    $h[]="<button class='btn btn-sm $ipv6Active' OnClick=\"RtRoutesFamily_$id('ipv6')\">IPv6</button>";
    $h[]="<div style='flex:1'></div>";
    $h[]="<button class='btn btn-success btn-sm' OnClick=\"Loadjs('$page?route-add-js=$id')\">";
    $h[]="<i class='fas fa-plus'></i>&nbsp; {add}</button>";
    $h[]="&nbsp; <button class='btn btn-default btn-sm' OnClick=\"RtRefreshRoutes_$id()\">";
    $h[]="<i class='fas fa-sync'></i>&nbsp; {refresh}</button>";
    $h[]="</div>";

    if(count($json)===0){
        $h[]=$tpl->div_info("{no_data}");
        echo $tpl->_ENGINE_parse_body(implode("\n",$h));
        return;
    }

    // Group routes by table name
    $grouped=[];
    foreach($json as $r){
        if(!is_object($r)) continue;
        $tblName=$r->table->name ?? (string)($r->table->id ?? 'unknown');
        $grouped[$tblName][]=$r;
    }

    foreach($grouped as $tblName=>$routes){
        $safeTbl=htmlspecialchars($tblName);
        $familyBadge=($family==="ipv4")
            ?"<span class='badge' style='background:#3498db;color:#fff'>IPv4</span>"
            :"<span class='badge' style='background:#9b59b6;color:#fff'>IPv6</span>";

        $h[]="<div class='ibox'><div class='ibox-title'><h5>";
        $h[]="<i class='fas fa-route'></i>&nbsp; $safeTbl $familyBadge";
        $h[]="</h5></div><div class='ibox-content'>";
        $h[]="<table class='table table-stripped'><thead><tr>";
        $h[]="<th>{destination}</th><th>{gateway}</th><th>{interface}</th>";
        $h[]="<th>{type}</th><th>Metric</th><th>Src</th><th style='width:1%'></th>";
        $h[]="</tr></thead><tbody>";

        foreach($routes as $r){
            $dst=rt_cidr($r->dst_cidr ?? '');
            $gw=!empty($r->gateway)?"<span style='font-family:monospace'>".htmlspecialchars($r->gateway)."</span>":"&mdash;";
            $iface=rt_iface($r->out_interface ?? '');
            $type=rt_type_badge($r->type ?? 'unicast');
            $metric=intval($r->metric ?? 0);
            $psrc=!empty($r->preferred_src)?"<span style='font-family:monospace'>".htmlspecialchars($r->preferred_src)."</span>":"&mdash;";

            $mp=$r->multi_path ?? [];
            if(is_array($mp)&&count($mp)>1){
                $gw="<i class='fas fa-code-branch text-info'></i> ECMP (".count($mp)." hops)";
            }

            // Delete button: encode the RouteSpec to identify this route
            $routeSpec=json_encode([
                "family"       =>$r->family ?? $family,
                "table"        =>$r->table ?? null,
                "dst_cidr"     =>$r->dst_cidr ?? "",
                "gateway"      =>$r->gateway ?? "",
                "out_interface"=>$r->out_interface ?? "",
                "type"         =>$r->type ?? "unicast",
                "metric"       =>intval($r->metric ?? 0),
                "scope"        =>$r->scope ?? "global",
            ]);
            $safeSpec=htmlspecialchars($routeSpec,ENT_QUOTES);
            $delBtn="<button class='btn btn-xs btn-danger' OnClick=\"RtRouteDelete_$id($safeSpec)\" title='{delete}'>";
            $delBtn.="<i class='fas fa-trash'></i></button>";

            $h[]="<tr><td>$dst</td><td>$gw</td><td>$iface</td><td>$type</td>";
            $h[]="<td>$metric</td><td>$psrc</td><td nowrap>$delBtn</td></tr>";
        }

        $h[]="</tbody></table></div></div>";
    }

    $h[]="<script>NoSpinner();</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Add Route dialog opener ──────────────────────────────────────────────

function route_add_js():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["route-add-js"]);
    $tpl->js_dialog5("{add} {routes}","$page?route-add-form=$id",820);
}

// ── Add Route form ───────────────────────────────────────────────────────

function route_add_form():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["route-add-form"]);

    $ifaceOpts=rt_iface_options($id);
    $tableOpts=rt_table_options($id);

    $h=[];
    $h[]="<div id='rt-route-result-$id'></div>";
    $h[]="<form id='rt-route-frm-$id'>";
    $h[]="<input type='hidden' name='route-save' value='$id'>";

    // Identity row
    $h[]="<div class='row'>";
    $h[]="  <div class='col-md-3'>";
    $h[]="    <div class='form-group'>";
    $h[]="      <label>Family</label>";
    $h[]="      <select id='rt-route-family-$id' name='family' class='form-control'>";
    $h[]="        <option value='ipv4' selected>IPv4</option>";
    $h[]="        <option value='ipv6'>IPv6</option>";
    $h[]="      </select>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="  <div class='col-md-4'>";
    $h[]="    <div class='form-group'>";
    $h[]="      <label>Table</label>";
    $h[]="      <select id='rt-route-table-$id' name='table_json' class='form-control'>$tableOpts</select>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="  <div class='col-md-5'>";
    $h[]="    <div class='form-group'>";
    $h[]="      <label>{type}</label>";
    $h[]="      <select id='rt-route-type-$id' name='type' class='form-control' OnChange=\"RtRouteTypeChanged_$id()\">";
    $h[]="        <option value='unicast' selected>unicast</option>";
    $h[]="        <option value='blackhole'>blackhole</option>";
    $h[]="        <option value='unreachable'>unreachable</option>";
    $h[]="        <option value='prohibit'>prohibit</option>";
    $h[]="        <option value='throw'>throw</option>";
    $h[]="      </select>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="</div>";

    // Destination & gateway
    $h[]="<div class='row'>";
    $h[]="  <div class='col-md-6'>";
    $h[]="    <div class='form-group'>";
    $h[]="      <label>{destination} CIDR</label>";
    $h[]="      <input type='text' id='rt-route-dst-$id' name='dst_cidr' class='form-control' placeholder='0.0.0.0/0'>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="  <div class='col-md-6' id='rt-route-gw-wrap-$id'>";
    $h[]="    <div class='form-group'>";
    $h[]="      <label>{gateway}</label>";
    $h[]="      <input type='text' id='rt-route-gw-$id' name='gateway' class='form-control' placeholder='192.168.1.1'>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="</div>";

    // Interface & preferred source (hidden for blackhole/unreachable/prohibit)
    $h[]="<div id='rt-route-unicast-$id'>";
    $h[]="<div class='row'>";
    $h[]="  <div class='col-md-6'>";
    $h[]="    <div class='form-group'>";
    $h[]="      <label>{interface}</label>";
    $h[]="      <select id='rt-route-iface-$id' name='out_interface' class='form-control'>$ifaceOpts</select>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="  <div class='col-md-6'>";
    $h[]="    <div class='form-group'>";
    $h[]="      <label>Preferred Source</label>";
    $h[]="      <input type='text' id='rt-route-src-$id' name='preferred_src' class='form-control' placeholder=''>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="</div>";

    // Multipath ECMP section
    $h[]="<div style='margin-bottom:10px'>";
    $h[]="  <label><input type='checkbox' id='rt-route-mp-toggle-$id' OnChange=\"RtMultipathToggle_$id()\">";
    $h[]="  &nbsp; Multipath (ECMP)</label>";
    $h[]="</div>";
    $h[]="<div id='rt-route-mp-$id' style='display:none;margin-bottom:10px'>";
    $h[]="  <div id='rt-route-mp-rows-$id'></div>";
    $h[]="  <button type='button' class='btn btn-xs btn-default' OnClick=\"RtAddNextHop_$id()\">";
    $h[]="  <i class='fas fa-plus'></i> {add}</button>";
    $h[]="</div>";
    $h[]="</div>"; // end rt-route-unicast

    // Advanced (collapsible)
    $h[]="<div style='margin-top:10px'>";
    $h[]="<a data-toggle='collapse' href='#rt-route-adv-$id' style='font-size:12px'>";
    $h[]="<i class='fas fa-cog'></i> Advanced</a>";
    $h[]="<div id='rt-route-adv-$id' class='collapse' style='margin-top:8px'>";
    $h[]="<div class='row'>";
    $h[]="  <div class='col-md-3'><div class='form-group'><label>Metric</label>";
    $h[]="    <input type='number' id='rt-route-metric-$id' name='metric' class='form-control' value='0' min='0'></div></div>";
    $h[]="  <div class='col-md-3'><div class='form-group'><label>Scope</label>";
    $h[]="    <select id='rt-route-scope-$id' name='scope' class='form-control'>";
    $h[]="      <option value='global' selected>global</option><option value='site'>site</option>";
    $h[]="      <option value='link'>link</option><option value='host'>host</option></select></div></div>";
    $h[]="  <div class='col-md-3'><div class='form-group'><label>MTU</label>";
    $h[]="    <input type='number' id='rt-route-mtu-$id' name='mtu' class='form-control' value='0' min='0' max='65535'></div></div>";
    $h[]="  <div class='col-md-3'><div class='form-group'><label>AdvMSS</label>";
    $h[]="    <input type='number' id='rt-route-advmss-$id' name='advmss' class='form-control' value='0' min='0'></div></div>";
    $h[]="</div>";
    $h[]="<div class='row'>";
    $h[]="  <div class='col-md-3'><div class='form-group'><label>InitCwnd</label>";
    $h[]="    <input type='number' id='rt-route-cwnd-$id' name='initcwnd' class='form-control' value='0' min='0'></div></div>";
    $h[]="  <div class='col-md-3'><div class='form-group'><label>InitRwnd</label>";
    $h[]="    <input type='number' id='rt-route-rwnd-$id' name='initrwnd' class='form-control' value='0' min='0'></div></div>";
    $h[]="  <div class='col-md-3' style='padding-top:28px'>";
    $h[]="    <label><input type='checkbox' id='rt-route-onlink-$id' name='on_link' value='1'> On-link</label></div>";
    $h[]="  <div class='col-md-3' style='padding-top:28px'>";
    $h[]="    <label><input type='checkbox' id='rt-route-replace-$id' name='replace' value='1'> Replace</label></div>";
    $h[]="</div>";
    $h[]="<div class='row'>";
    $h[]="  <div class='col-md-12'><div class='form-group'><label>Comment</label>";
    $h[]="    <input type='text' id='rt-route-comment-$id' name='comment' class='form-control'></div></div>";
    $h[]="</div>";
    $h[]="</div></div>"; // end collapse + advanced

    $h[]="</form>";
    $h[]="<div style='text-align:right;margin-top:15px'>";
    $h[]="<button class='btn btn-primary' OnClick=\"RtRouteSave_$id()\">";
    $h[]="<i class='fas fa-save'></i>&nbsp; {save}</button>";
    $h[]="</div>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Save route (POST handler) ────────────────────────────────────────────

function route_save():void{
    $tpl=new template_admin();
    $id=intval($_POST["route-save"]);

    $type=$_POST["type"] ?? "unicast";
    $data=[
        "family"        =>$_POST["family"] ?? "ipv4",
        "table"         =>json_decode($_POST["table_json"] ?? '{"name":"main"}'),
        "dst_cidr"      =>trim($_POST["dst_cidr"] ?? ""),
        "type"          =>$type,
        "gateway"       =>trim($_POST["gateway"] ?? ""),
        "out_interface" =>trim($_POST["out_interface"] ?? ""),
        "preferred_src" =>trim($_POST["preferred_src"] ?? ""),
        "metric"        =>intval($_POST["metric"] ?? 0),
        "scope"         =>$_POST["scope"] ?? "global",
        "protocol"      =>"static",
        "mtu"           =>intval($_POST["mtu"] ?? 0),
        "advmss"        =>intval($_POST["advmss"] ?? 0),
        "initcwnd"      =>intval($_POST["initcwnd"] ?? 0),
        "initrwnd"      =>intval($_POST["initrwnd"] ?? 0),
        "on_link"       =>($_POST["on_link"] ?? "0")==="1",
        "replace"       =>($_POST["replace"] ?? "0")==="1",
        "blackhole"     =>$type==="blackhole",
        "unreachable"   =>$type==="unreachable",
        "prohibit"      =>$type==="prohibit",
        "comment"       =>trim($_POST["comment"] ?? ""),
    ];

    // Multipath next-hops
    if(!empty($_POST["multi_path_json"])){
        $data["multi_path"]=json_decode($_POST["multi_path_json"]);
        unset($data["gateway"],$data["out_interface"]);
    }

    // Remove empty optional fields
    foreach(["gateway","out_interface","preferred_src","comment"] as $k){
        if(empty($data[$k])) unset($data[$k]);
    }
    foreach(["metric","mtu","advmss","initcwnd","initrwnd"] as $k){
        if(($data[$k] ?? 0)===0) unset($data[$k]);
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/routing/$id/routes",$data
    ));

    if(rt_is_error($json)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(rt_err($json)));
        return;
    }

    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {route_added}"));
}

// ── Delete route (POST handler, uses POST /routes/delete to send body) ───

function route_delete():void{
    $tpl=new template_admin();
    $id=intval($_POST["route-delete"]);
    $routeSpec=json_decode($_POST["route_json"] ?? "{}");

    if(!is_object($routeSpec)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/routing/$id/routes/delete",$routeSpec
    ));

    if(rt_is_error($json)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(rt_err($json)));
        return;
    }

    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {deleted}"));
}

// ── TAB: RULES ───────────────────────────────────────────────────────────

function routing_rules_tab():void{
    $tpl=new template_admin();
    $id=intval($_GET["rules-tab"]);
    $page=CurrentPageName();
    $family=$_GET["family"] ?? "ipv4";

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/routing/$id/rules?family=$family"));
    if(!is_array($json)){
        if(rt_is_error($json)){
            echo $tpl->_ENGINE_parse_body($tpl->div_error(rt_err($json)));
            return;
        }
        $json=[];
    }

    $h=[];

    // Family toggle + action buttons
    $h[]="<div style='margin-bottom:10px;margin-top:10px;display:flex;align-items:center;flex-wrap:wrap;gap:8px'>";
    $ipv4Active=($family==="ipv4")?"btn-primary":"btn-white";
    $ipv6Active=($family==="ipv6")?"btn-primary":"btn-white";
    $h[]="<button class='btn btn-sm $ipv4Active' OnClick=\"RtRulesFamily_$id('ipv4')\">IPv4</button>";
    $h[]="<button class='btn btn-sm $ipv6Active' OnClick=\"RtRulesFamily_$id('ipv6')\">IPv6</button>";
    $h[]="<div style='flex:1'></div>";
    $h[]="<button class='btn btn-success btn-sm' OnClick=\"Loadjs('$page?rule-add-js=$id')\">";
    $h[]="<i class='fas fa-plus'></i>&nbsp; {add}</button>";
    $h[]="&nbsp; <button class='btn btn-default btn-sm' OnClick=\"RtRefreshRules_$id()\">";
    $h[]="<i class='fas fa-sync'></i>&nbsp; {refresh}</button>";
    $h[]="</div>";

    if(count($json)===0){
        $h[]=$tpl->div_info("{no_data}");
        echo $tpl->_ENGINE_parse_body(implode("\n",$h));
        return;
    }

    $h[]="<table class='table table-stripped'><thead><tr>";
    $h[]="<th>Priority</th><th>Source</th><th>{destination}</th><th>FWMark</th>";
    $h[]="<th>{interface}</th><th>Table</th><th>Action</th><th style='width:1%'></th>";
    $h[]="</tr></thead><tbody>";

    $TRCLASS=null;
    foreach($json as $r){
        if(!is_object($r)) continue;
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $prio=intval($r->priority ?? 0);
        $isSystem=in_array($prio,[0,32766,32767]);
        $rowStyle=$isSystem?"style='color:#999'":"";
        $src=rt_cidr($r->src_cidr ?? '');
        $dst=rt_cidr($r->dst_cidr ?? '');
        $fwmark=intval($r->fwmark ?? 0);
        $fwmarkStr=$fwmark>0?"<span style='font-family:monospace'>$fwmark</span>":"&mdash;";
        $iif=!empty($r->in_interface)?rt_iface($r->in_interface):"&mdash;";
        $tbl=rt_table_badge($r->table ?? null);
        $act=rt_action_badge($r->action ?? 'lookup');

        $delBtn="";
        if(!$isSystem){
            $ruleSpec=json_encode([
                "family"    =>$r->family ?? $family,
                "priority"  =>$prio,
                "src_cidr"  =>$r->src_cidr ?? "",
                "dst_cidr"  =>$r->dst_cidr ?? "",
                "fwmark"    =>$fwmark,
                "table"     =>$r->table ?? null,
                "action"    =>$r->action ?? "lookup",
            ]);
            $safeSpec=htmlspecialchars($ruleSpec,ENT_QUOTES);
            $delBtn="<button class='btn btn-xs btn-danger' OnClick=\"RtRuleDelete_$id($safeSpec)\" title='{delete}'>";
            $delBtn.="<i class='fas fa-trash'></i></button>";
        }

        $h[]="<tr class='$TRCLASS' $rowStyle><td>$prio</td><td>$src</td><td>$dst</td>";
        $h[]="<td>$fwmarkStr</td><td>$iif</td><td>$tbl</td><td>$act</td><td nowrap>$delBtn</td></tr>";
    }

    $h[]="</tbody></table>";
    $h[]="<script>NoSpinner();</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Add Rule dialog opener ───────────────────────────────────────────────

function rule_add_js():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["rule-add-js"]);
    $tpl->js_dialog5("{add} {policy_rules}","$page?rule-add-form=$id",750);
}

// ── Add Rule form ────────────────────────────────────────────────────────

function rule_add_form():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["rule-add-form"]);

    $ifaceOpts=rt_iface_options($id);
    $tableOpts=rt_table_options($id);
    $caps=rt_fetch_caps($id);
    $hasPortRange=($caps->rule_port_range ?? false);
    $hasUidRange=($caps->rule_uid_range ?? false);

    $h=[];
    $h[]="<div id='rt-rule-result-$id'></div>";
    $h[]="<form id='rt-rule-frm-$id'>";
    $h[]="<input type='hidden' name='rule-save' value='$id'>";

    // Identity
    $h[]="<div class='row'>";
    $h[]="  <div class='col-md-3'><div class='form-group'><label>Family</label>";
    $h[]="    <select name='family' class='form-control'>";
    $h[]="      <option value='ipv4' selected>IPv4</option><option value='ipv6'>IPv6</option></select></div></div>";
    $h[]="  <div class='col-md-3'><div class='form-group'><label>Priority</label>";
    $h[]="    <input type='number' name='priority' class='form-control' value='1000' min='0' max='32767'></div></div>";
    $h[]="  <div class='col-md-3'><div class='form-group'><label>Action</label>";
    $h[]="    <select name='action' id='rt-rule-action-$id' class='form-control' OnChange=\"RtRuleActionChanged_$id()\">";
    $h[]="      <option value='lookup' selected>lookup</option><option value='goto'>goto</option>";
    $h[]="      <option value='blackhole'>blackhole</option><option value='unreachable'>unreachable</option>";
    $h[]="      <option value='prohibit'>prohibit</option></select></div></div>";
    $h[]="  <div class='col-md-3'><div class='form-group'><label>Table</label>";
    $h[]="    <select name='table_json' id='rt-rule-table-$id' class='form-control'>$tableOpts</select></div></div>";
    $h[]="</div>";

    // Goto priority (hidden unless action=goto)
    $h[]="<div class='row' id='rt-rule-goto-$id' style='display:none'>";
    $h[]="  <div class='col-md-4'><div class='form-group'><label>Goto Priority</label>";
    $h[]="    <input type='number' name='goto_priority' class='form-control' value='0' min='0' max='32767'></div></div>";
    $h[]="</div>";

    // Matching criteria
    $h[]="<h5 style='border-bottom:1px solid #e7eaec;padding-bottom:5px;margin-top:10px'>Matching</h5>";
    $h[]="<div class='row'>";
    $h[]="  <div class='col-md-6'><div class='form-group'><label>Source CIDR</label>";
    $h[]="    <input type='text' name='src_cidr' class='form-control' placeholder='10.20.0.0/24'></div></div>";
    $h[]="  <div class='col-md-6'><div class='form-group'><label>{destination} CIDR</label>";
    $h[]="    <input type='text' name='dst_cidr' class='form-control' placeholder='192.168.0.0/16'></div></div>";
    $h[]="</div>";
    $h[]="<div class='row'>";
    $h[]="  <div class='col-md-6'><div class='form-group'><label>Input {interface}</label>";
    $h[]="    <select name='in_interface' class='form-control'>$ifaceOpts</select></div></div>";
    $h[]="  <div class='col-md-6'><div class='form-group'><label>Output {interface}</label>";
    $h[]="    <select name='out_interface' class='form-control'>$ifaceOpts</select></div></div>";
    $h[]="</div>";
    $h[]="<div class='row'>";
    $h[]="  <div class='col-md-4'><div class='form-group'><label>FWMark</label>";
    $h[]="    <input type='number' name='fwmark' class='form-control' value='0' min='0'></div></div>";
    $h[]="  <div class='col-md-4'><div class='form-group'><label>FWMask</label>";
    $h[]="    <input type='number' name='fwmask' class='form-control' value='0' min='0'></div></div>";
    $h[]="  <div class='col-md-4'><div class='form-group'><label>TOS</label>";
    $h[]="    <input type='number' name='tos' class='form-control' value='0' min='0' max='255'></div></div>";
    $h[]="</div>";

    // IP protocol
    $h[]="<div class='row'>";
    $h[]="  <div class='col-md-4'><div class='form-group'><label>IP Protocol (0=any)</label>";
    $h[]="    <input type='number' name='ip_proto' class='form-control' value='0' min='0' max='255'></div></div>";

    // Suppress prefix length
    $h[]="  <div class='col-md-4'><div class='form-group'><label>Suppress prefix len</label>";
    $h[]="    <input type='number' name='suppress_prefixlen' class='form-control' value='0' min='0'></div></div>";
    $h[]="</div>";

    // Port ranges (capability-gated)
    if($hasPortRange){
        $h[]="<div class='row'>";
        $h[]="  <div class='col-md-3'><div class='form-group'><label>Src port start</label>";
        $h[]="    <input type='number' name='src_port_start' class='form-control' value='0' min='0' max='65535'></div></div>";
        $h[]="  <div class='col-md-3'><div class='form-group'><label>Src port end</label>";
        $h[]="    <input type='number' name='src_port_end' class='form-control' value='0' min='0' max='65535'></div></div>";
        $h[]="  <div class='col-md-3'><div class='form-group'><label>Dst port start</label>";
        $h[]="    <input type='number' name='dst_port_start' class='form-control' value='0' min='0' max='65535'></div></div>";
        $h[]="  <div class='col-md-3'><div class='form-group'><label>Dst port end</label>";
        $h[]="    <input type='number' name='dst_port_end' class='form-control' value='0' min='0' max='65535'></div></div>";
        $h[]="</div>";
    }

    // UID range (capability-gated)
    if($hasUidRange){
        $h[]="<div class='row'>";
        $h[]="  <div class='col-md-6'><div class='form-group'><label>UID start</label>";
        $h[]="    <input type='number' name='uid_start' class='form-control' value='0' min='0'></div></div>";
        $h[]="  <div class='col-md-6'><div class='form-group'><label>UID end</label>";
        $h[]="    <input type='number' name='uid_end' class='form-control' value='0' min='0'></div></div>";
        $h[]="</div>";
    }

    // Comment
    $h[]="<div class='row'>";
    $h[]="  <div class='col-md-12'><div class='form-group'><label>Comment</label>";
    $h[]="    <input type='text' name='comment' class='form-control'></div></div>";
    $h[]="</div>";

    $h[]="</form>";
    $h[]="<div style='text-align:right;margin-top:15px'>";
    $h[]="<button class='btn btn-primary' OnClick=\"RtRuleSave_$id()\">";
    $h[]="<i class='fas fa-save'></i>&nbsp; {save}</button>";
    $h[]="</div>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Save rule (POST handler) ─────────────────────────────────────────────

function rule_save():void{
    $tpl=new template_admin();
    $id=intval($_POST["rule-save"]);

    $data=[
        "family"            =>$_POST["family"] ?? "ipv4",
        "priority"          =>intval($_POST["priority"] ?? 0),
        "src_cidr"          =>trim($_POST["src_cidr"] ?? ""),
        "dst_cidr"          =>trim($_POST["dst_cidr"] ?? ""),
        "in_interface"      =>trim($_POST["in_interface"] ?? ""),
        "out_interface"     =>trim($_POST["out_interface"] ?? ""),
        "fwmark"            =>intval($_POST["fwmark"] ?? 0),
        "fwmask"            =>intval($_POST["fwmask"] ?? 0),
        "tos"               =>intval($_POST["tos"] ?? 0),
        "ip_proto"          =>intval($_POST["ip_proto"] ?? 0),
        "src_port_start"    =>intval($_POST["src_port_start"] ?? 0),
        "src_port_end"      =>intval($_POST["src_port_end"] ?? 0),
        "dst_port_start"    =>intval($_POST["dst_port_start"] ?? 0),
        "dst_port_end"      =>intval($_POST["dst_port_end"] ?? 0),
        "uid_start"         =>intval($_POST["uid_start"] ?? 0),
        "uid_end"           =>intval($_POST["uid_end"] ?? 0),
        "table"             =>json_decode($_POST["table_json"] ?? '{"name":"main"}'),
        "action"            =>$_POST["action"] ?? "lookup",
        "goto_priority"     =>intval($_POST["goto_priority"] ?? 0),
        "suppress_prefixlen"=>intval($_POST["suppress_prefixlen"] ?? 0),
        "comment"           =>trim($_POST["comment"] ?? ""),
    ];

    // Remove zero/empty optional fields
    foreach(["src_cidr","dst_cidr","in_interface","out_interface","comment"] as $k){
        if(empty($data[$k])) unset($data[$k]);
    }
    foreach(["fwmark","fwmask","tos","ip_proto","src_port_start","src_port_end",
             "dst_port_start","dst_port_end","uid_start","uid_end",
             "goto_priority","suppress_prefixlen"] as $k){
        if(($data[$k] ?? 0)===0) unset($data[$k]);
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/routing/$id/rules",$data
    ));

    if(rt_is_error($json)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(rt_err($json)));
        return;
    }

    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {success}"));
}

// ── Delete rule (POST handler) ───────────────────────────────────────────

function rule_delete():void{
    $tpl=new template_admin();
    $id=intval($_POST["rule-delete"]);
    $ruleSpec=json_decode($_POST["rule_json"] ?? "{}");

    if(!is_object($ruleSpec)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/routing/$id/rules/delete",$ruleSpec
    ));

    if(rt_is_error($json)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(rt_err($json)));
        return;
    }

    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {deleted}"));
}

// ── Plan dialog opener ───────────────────────────────────────────────────

function plan_js():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["plan-js"]);
    $tpl->js_dialog5("{plan_apply}","$page?plan-form=$id",950);
}

// ── Plan form ────────────────────────────────────────────────────────────

function plan_form():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["plan-form"]);

    $template=json_encode(["tables"=>[],"routes"=>[],"rules"=>[]],JSON_PRETTY_PRINT);

    $h=[];
    $h[]="<div id='rt-plan-result-$id'></div>";

    $h[]="<p style='margin-bottom:8px'>{description}:</p>";
    $h[]="<textarea id='rt-plan-json-$id' class='form-control' rows='15' style='font-family:monospace;font-size:12px'>";
    $h[]=htmlspecialchars($template);
    $h[]="</textarea>";

    // Template buttons
    $h[]="<div style='margin-top:8px;margin-bottom:12px'>";
    $h[]="<span class='text-muted' style='font-size:12px'>Templates:</span> ";
    $tpls=[
        "Multi-WAN"       =>json_encode(["tables"=>[["id"=>200,"name"=>"wan2","persist"=>true]],"routes"=>[["family"=>"ipv4","table"=>["name"=>"wan2"],"dst_cidr"=>"0.0.0.0/0","gateway"=>"192.168.2.1","out_interface"=>"eth1","protocol"=>"static"]],"rules"=>[["family"=>"ipv4","priority"=>1000,"src_cidr"=>"10.20.0.0/24","table"=>["name"=>"wan2"],"action"=>"lookup"]]]),
        "FWMark VPN"      =>json_encode(["tables"=>[["id"=>201,"name"=>"vpn","persist"=>true]],"routes"=>[["family"=>"ipv4","table"=>["name"=>"vpn"],"dst_cidr"=>"0.0.0.0/0","out_interface"=>"tun0","protocol"=>"static"]],"rules"=>[["family"=>"ipv4","priority"=>2000,"fwmark"=>32,"table"=>["name"=>"vpn"],"action"=>"lookup"]]]),
        "Blackhole"       =>json_encode(["tables"=>[],"routes"=>[["family"=>"ipv4","table"=>["name"=>"main"],"dst_cidr"=>"203.0.113.0/24","type"=>"blackhole","blackhole"=>true,"protocol"=>"static"]],"rules"=>[]]),
    ];
    foreach($tpls as $label=>$jsonStr){
        $safeJson=htmlspecialchars($jsonStr,ENT_QUOTES);
        $h[]="<button class='btn btn-xs btn-white' OnClick=\"RtPlanTemplate_$id($safeJson)\">$label</button> ";
    }
    $h[]="</div>";

    // Buttons
    $h[]="<div style='display:flex;justify-content:flex-end;gap:8px'>";
    $h[]="<button class='btn btn-warning' OnClick=\"RtPlanDryRun_$id()\">";
    $h[]="<i class='fas fa-clipboard-list'></i>&nbsp; Dry Run</button>";
    $h[]="<button class='btn btn-danger' id='rt-plan-apply-btn-$id' style='display:none' OnClick=\"RtPlanApply_$id()\">";
    $h[]="<i class='fas fa-bolt'></i>&nbsp; {apply}</button>";
    $h[]="</div>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Plan dry-run (POST handler) ──────────────────────────────────────────

function plan_run():void{
    $tpl=new template_admin();
    $id=intval($_POST["plan-run"]);
    $desired=json_decode($_POST["desired_json"] ?? "{}");

    if(!is_object($desired)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("Invalid JSON"));
        return;
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/routing/$id/plan",$desired
    ));

    if(rt_is_error($json)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(rt_err($json)));
        return;
    }

    $h=[];
    $sections=["table_actions"=>"{routing_tables}","route_actions"=>"{routes}","rule_actions"=>"{policy_rules}"];
    $totalActions=0;
    foreach($sections as $key=>$title){
        $actions=$json->$key ?? [];
        if(!is_array($actions)||count($actions)===0) continue;
        $h[]="<h5>$title</h5><ul style='list-style:none;padding-left:0'>";
        foreach($actions as $a){
            $badge=rt_plan_badge($a->kind ?? '');
            $summary=htmlspecialchars($a->summary ?? '');
            $risk='';
            if(!empty($a->risk)){
                $risk=" <span class='text-warning'><i class='fas fa-exclamation-triangle'></i> ".htmlspecialchars($a->risk)."</span>";
            }
            $h[]="<li style='margin-bottom:4px'>$badge &nbsp; $summary$risk</li>";
            if(($a->kind ?? '')==='keep') continue;
            $totalActions++;
        }
        $h[]="</ul>";
    }

    $warnings=$json->warnings ?? [];
    if(is_array($warnings)&&count($warnings)>0){
        $h[]="<div class='alert alert-warning' style='margin-top:10px'>";
        foreach($warnings as $w){
            $h[]="<i class='fas fa-exclamation-triangle'></i> ".htmlspecialchars($w)."<br>";
        }
        $h[]="</div>";
    }

    if($totalActions===0){
        $h[]=$tpl->div_info("{no_data}");
    }

    // Signal to JS whether Apply button should show
    $h[]="<input type='hidden' id='rt-plan-count-$id' value='$totalActions'>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Apply (POST handler) ─────────────────────────────────────────────────

function apply_run():void{
    $tpl=new template_admin();
    $id=intval($_POST["apply-run"]);
    $desired=json_decode($_POST["desired_json"] ?? "{}");

    if(!is_object($desired)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("Invalid JSON"));
        return;
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/routing/$id/apply",$desired
    ));

    if(!is_object($json)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $h=[];
    $ok=($json->success ?? false);

    if($ok){
        $count=count($json->applied_actions ?? []);
        $h[]="<div class='alert alert-success'>";
        $h[]="<i class='fas fa-check-circle'></i> Applied $count actions successfully.";
        $h[]="</div>";
        if(is_array($json->applied_actions ?? null)){
            $h[]="<ul style='list-style:none;padding-left:0'>";
            foreach($json->applied_actions as $a){
                $h[]="<li><i class='fas fa-check text-success'></i> ".htmlspecialchars($a->summary ?? '')."</li>";
            }
            $h[]="</ul>";
        }
    }else{
        $err=htmlspecialchars($json->error ?? '');
        $failedSummary=htmlspecialchars($json->failed_action->summary ?? '');
        $h[]="<div class='alert alert-danger'>";
        $h[]="<i class='fas fa-times-circle'></i> Failed: $err";
        if($failedSummary!==''){
            $h[]="<br>Failed action: $failedSummary";
        }
        $h[]="</div>";

        $rollbackActions=$json->rollback_actions ?? [];
        $rollbackError=$json->rollback_error ?? '';
        if(!empty($rollbackError)){
            $h[]="<div class='alert alert-danger'>";
            $h[]="<i class='fas fa-bomb'></i> CRITICAL: Rollback also failed.";
            $h[]="<br>".htmlspecialchars($rollbackError);
            $h[]="<br><strong>Manual intervention required.</strong>";
            $h[]="</div>";
        }elseif(is_array($rollbackActions)&&count($rollbackActions)>0){
            $h[]="<div class='alert alert-warning'>";
            $h[]="<i class='fas fa-undo'></i> Rollback: ".count($rollbackActions)." actions reversed.";
            $h[]="</div>";
        }
    }

    $warnings=$json->warnings ?? [];
    if(is_array($warnings)&&count($warnings)>0){
        foreach($warnings as $w){
            $h[]="<div class='text-warning'><i class='fas fa-exclamation-triangle'></i> ".htmlspecialchars($w)."</div>";
        }
    }

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── JavaScript block (agent-ID-scoped functions) ─────────────────────────

function routing_js_block(int $id):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $h=[];
    $h[]="<script>";

    // Track current family per tab
    $h[]="var _rtRoutesFamily_$id='ipv4';";
    $h[]="var _rtRulesFamily_$id='ipv4';";

    // Show result then auto-clear after 5s
    $h[]="function RtFlash_$id(html){";
    $h[]="  var el=document.getElementById('rt-result-$id');";
    $h[]="  if(!el) return;";
    $h[]="  el.innerHTML=html;";
    $h[]="  setTimeout(function(){el.innerHTML='';},5000);";
    $h[]="}";

    // Refresh functions for each tab
    $h[]="function RtRefreshTables_$id(){";
    $h[]="  LoadAjaxSilent('rt-tables-content-$id','$page?tables-tab=$id');";
    $h[]="}";

    $h[]="function RtRefreshRoutes_$id(){";
    $h[]="  LoadAjaxSilent('rt-routes-content-$id','$page?routes-tab=$id&family='+_rtRoutesFamily_$id);";
    $h[]="}";

    $h[]="function RtRefreshRules_$id(){";
    $h[]="  LoadAjaxSilent('rt-rules-content-$id','$page?rules-tab=$id&family='+_rtRulesFamily_$id);";
    $h[]="}";

    // Family toggle for routes tab
    $h[]="function RtRoutesFamily_$id(f){";
    $h[]="  _rtRoutesFamily_$id=f;";
    $h[]="  RtRefreshRoutes_$id();";
    $h[]="}";

    // Family toggle for rules tab
    $h[]="function RtRulesFamily_$id(f){";
    $h[]="  _rtRulesFamily_$id=f;";
    $h[]="  RtRefreshRules_$id();";
    $h[]="}";

    // ── Table operations ──

    $confirm_delete=$tpl->javascript_parse_text("{delete_this_rule}");
    $yes=$tpl->javascript_parse_text("{yes}");
    $cancel=$tpl->javascript_parse_text("{cancel}");

    $h[]="function RtTableSave_$id(){";
    $h[]="  var tid=parseInt($('#rt-tbl-id-$id').val())||0;";
    $h[]="  if(tid<1||tid>252){swal('{error}','Table ID: 1-252','error');return;}";
    $h[]="  var nm=$('#rt-tbl-name-$id').val().trim();";
    $h[]="  if(!nm||!/^[a-zA-Z0-9_-]{1,64}$/.test(nm)){swal('{error}','Invalid name','error');return;}";
    $h[]="  $.post('$page',{";
    $h[]="    'table-save':$id,";
    $h[]="    'table_id':tid,";
    $h[]="    'name':nm,";
    $h[]="    'persist':$('#rt-tbl-persist-$id').is(':checked')?'1':'0',";
    $h[]="    'description':$('#rt-tbl-desc-$id').val()";
    $h[]="  },function(r){";
    $h[]="    $('#rt-tbl-result-$id').html(r);";
    $h[]="    if(r.indexOf('panel-primary')!==-1){";
    $h[]="      RtRefreshTables_$id();";
    $h[]="      setTimeout(function(){dialogInstance5.close();},800);";
    $h[]="    }";
    $h[]="  });";
    $h[]="}";

    $h[]="function RtTableDelete_$id(tid,name){";
    $h[]="  swal({title:'$confirm_delete',text:name+' (ID '+tid+')',type:'warning',";
    $h[]="    showCancelButton:true,confirmButtonColor:'#ed5565',confirmButtonText:'$yes',cancelButtonText:'$cancel'";
    $h[]="  },function(isConfirm){";
    $h[]="    if(!isConfirm) return;";
    $h[]="    $.post('$page',{'table-delete':$id,'table_id':tid},function(r){";
    $h[]="      RtFlash_$id(r);";
    $h[]="      RtRefreshTables_$id();";
    $h[]="    });";
    $h[]="  });";
    $h[]="}";

    // ── Route operations ──

    // Route type change: show/hide unicast-only fields
    $h[]="function RtRouteTypeChanged_$id(){";
    $h[]="  var t=$('#rt-route-type-$id').val();";
    $h[]="  var isUnicast=(t==='unicast');";
    $h[]="  $('#rt-route-gw-wrap-$id').toggle(isUnicast);";
    $h[]="  $('#rt-route-unicast-$id').toggle(isUnicast);";
    $h[]="}";

    // Multipath toggle
    $h[]="function RtMultipathToggle_$id(){";
    $h[]="  var on=$('#rt-route-mp-toggle-$id').is(':checked');";
    $h[]="  $('#rt-route-mp-$id').toggle(on);";
    $h[]="  $('#rt-route-gw-wrap-$id').toggle(!on);";
    $h[]="}";

    // Add ECMP next-hop row
    $h[]="function RtAddNextHop_$id(){";
    $h[]="  var row='<div class=\"row rt-mp-row\" style=\"margin-bottom:5px\">';";
    $h[]="  row+='<div class=\"col-md-5\"><input type=\"text\" class=\"form-control mp-gw\" placeholder=\"gateway\"></div>';";
    $h[]="  row+='<div class=\"col-md-4\"><input type=\"text\" class=\"form-control mp-iface\" placeholder=\"interface\"></div>';";
    $h[]="  row+='<div class=\"col-md-2\"><input type=\"number\" class=\"form-control mp-weight\" value=\"1\" min=\"0\" max=\"256\"></div>';";
    $h[]="  row+='<div class=\"col-md-1\"><button class=\"btn btn-danger btn-sm\" type=\"button\" onclick=\"$(this).closest(\\'.row\\').remove()\">';";
    $h[]="  row+='<i class=\"fas fa-times\"></i></button></div></div>';";
    $h[]="  document.getElementById('rt-route-mp-rows-$id').insertAdjacentHTML('beforeend',row);";
    $h[]="}";

    // Collect multipath data
    $h[]="function RtCollectMultipath_$id(){";
    $h[]="  var hops=[];";
    $h[]="  $('#rt-route-mp-rows-$id .rt-mp-row').each(function(){";
    $h[]="    var g=$.trim($(this).find('.mp-gw').val());";
    $h[]="    if(g) hops.push({gateway:g,if_name:$.trim($(this).find('.mp-iface').val()),weight:parseInt($(this).find('.mp-weight').val())||1});";
    $h[]="  });";
    $h[]="  return hops;";
    $h[]="}";

    $h[]="function RtRouteSave_$id(){";
    $h[]="  var dst=$('#rt-route-dst-$id').val().trim();";
    $h[]="  if(!dst){swal('{error}','{destination} CIDR required','error');return;}";
    $h[]="  var postData={";
    $h[]="    'route-save':$id,";
    $h[]="    'family':$('#rt-route-family-$id').val(),";
    $h[]="    'table_json':$('#rt-route-table-$id').val(),";
    $h[]="    'type':$('#rt-route-type-$id').val(),";
    $h[]="    'dst_cidr':dst,";
    $h[]="    'gateway':$('#rt-route-gw-$id').val(),";
    $h[]="    'out_interface':$('#rt-route-iface-$id').val(),";
    $h[]="    'preferred_src':$('#rt-route-src-$id').val(),";
    $h[]="    'metric':$('#rt-route-metric-$id').val(),";
    $h[]="    'scope':$('#rt-route-scope-$id').val(),";
    $h[]="    'mtu':$('#rt-route-mtu-$id').val(),";
    $h[]="    'advmss':$('#rt-route-advmss-$id').val(),";
    $h[]="    'initcwnd':$('#rt-route-cwnd-$id').val(),";
    $h[]="    'initrwnd':$('#rt-route-rwnd-$id').val(),";
    $h[]="    'on_link':$('#rt-route-onlink-$id').is(':checked')?'1':'0',";
    $h[]="    'replace':$('#rt-route-replace-$id').is(':checked')?'1':'0',";
    $h[]="    'comment':$('#rt-route-comment-$id').val()";
    $h[]="  };";
    $h[]="  if($('#rt-route-mp-toggle-$id').is(':checked')){";
    $h[]="    var hops=RtCollectMultipath_$id();";
    $h[]="    if(hops.length<2){swal('{error}','ECMP requires at least 2 next-hops','error');return;}";
    $h[]="    postData['multi_path_json']=JSON.stringify(hops);";
    $h[]="  }";
    $h[]="  $.post('$page',postData,function(r){";
    $h[]="    $('#rt-route-result-$id').html(r);";
    $h[]="    if(r.indexOf('panel-primary')!==-1){";
    $h[]="      RtRefreshRoutes_$id();";
    $h[]="      setTimeout(function(){dialogInstance5.close();},800);";
    $h[]="    }";
    $h[]="  });";
    $h[]="}";

    $h[]="function RtRouteDelete_$id(specJson){";
    $h[]="  var r=(typeof specJson==='string')?JSON.parse(specJson):specJson;";
    $h[]="  var label=(r.dst_cidr||'default')+(r.gateway?' via '+r.gateway:'')+(r.table&&r.table.name?' table '+r.table.name:'');";
    $h[]="  swal({title:'$confirm_delete',text:label,type:'warning',";
    $h[]="    showCancelButton:true,confirmButtonColor:'#ed5565',confirmButtonText:'$yes',cancelButtonText:'$cancel'";
    $h[]="  },function(isConfirm){";
    $h[]="    if(!isConfirm) return;";
    $h[]="    $.post('$page',{'route-delete':$id,'route_json':JSON.stringify(r)},function(r){";
    $h[]="      RtFlash_$id(r);";
    $h[]="      RtRefreshRoutes_$id();";
    $h[]="    });";
    $h[]="  });";
    $h[]="}";

    // ── Rule operations ──

    // Rule action change: show/hide goto field and table field
    $h[]="function RtRuleActionChanged_$id(){";
    $h[]="  var a=$('#rt-rule-action-$id').val();";
    $h[]="  $('#rt-rule-goto-$id').toggle(a==='goto');";
    $h[]="  $('#rt-rule-table-$id').closest('.form-group').toggle(a==='lookup'||a==='goto');";
    $h[]="}";

    $h[]="function RtRuleSave_$id(){";
    $h[]="  var frm=$('#rt-rule-frm-$id');";
    $h[]="  var data={};";
    $h[]="  frm.find('input,select,textarea').each(function(){";
    $h[]="    var n=$(this).attr('name');if(!n) return;";
    $h[]="    if($(this).attr('type')==='checkbox'){data[n]=$(this).is(':checked')?'1':'0';}";
    $h[]="    else{data[n]=$(this).val();}";
    $h[]="  });";
    $h[]="  $.post('$page',data,function(r){";
    $h[]="    $('#rt-rule-result-$id').html(r);";
    $h[]="    if(r.indexOf('panel-primary')!==-1){";
    $h[]="      RtRefreshRules_$id();";
    $h[]="      setTimeout(function(){dialogInstance5.close();},800);";
    $h[]="    }";
    $h[]="  });";
    $h[]="}";

    $h[]="function RtRuleDelete_$id(specJson){";
    $h[]="  var r=(typeof specJson==='string')?JSON.parse(specJson):specJson;";
    $h[]="  var label='Priority '+r.priority+(r.src_cidr?' from '+r.src_cidr:'')+(r.table&&r.table.name?' -> '+r.table.name:'');";
    $h[]="  swal({title:'$confirm_delete',text:label,type:'warning',";
    $h[]="    showCancelButton:true,confirmButtonColor:'#ed5565',confirmButtonText:'$yes',cancelButtonText:'$cancel'";
    $h[]="  },function(isConfirm){";
    $h[]="    if(!isConfirm) return;";
    $h[]="    $.post('$page',{'rule-delete':$id,'rule_json':JSON.stringify(r)},function(r){";
    $h[]="      RtFlash_$id(r);";
    $h[]="      RtRefreshRules_$id();";
    $h[]="    });";
    $h[]="  });";
    $h[]="}";

    // ── Plan/Apply operations ──

    $h[]="function RtPlanTemplate_$id(jsonStr){";
    $h[]="  try{var o=JSON.parse(jsonStr);$('#rt-plan-json-$id').val(JSON.stringify(o,null,2));}";
    $h[]="  catch(e){swal('{error}','Invalid template','error');}";
    $h[]="}";

    $h[]="function RtPlanDryRun_$id(){";
    $h[]="  var raw=$('#rt-plan-json-$id').val();";
    $h[]="  try{JSON.parse(raw);}catch(e){swal('{error}','Invalid JSON: '+e.message,'error');return;}";
    $h[]="  $.post('$page',{'plan-run':$id,'desired_json':raw},function(r){";
    $h[]="    $('#rt-plan-result-$id').html(r);";
    $h[]="    var cnt=parseInt($('#rt-plan-count-$id').val())||0;";
    $h[]="    $('#rt-plan-apply-btn-$id').toggle(cnt>0);";
    $h[]="  });";
    $h[]="}";

    $confirm_apply=$tpl->javascript_parse_text("{apply}");
    $confirm_apply_text=$tpl->javascript_parse_text("{confirm_action}");
    $h[]="function RtPlanApply_$id(){";
    $h[]="  swal({title:'$confirm_apply',text:'$confirm_apply_text',type:'warning',";
    $h[]="    showCancelButton:true,confirmButtonColor:'#ed5565',confirmButtonText:'$yes',cancelButtonText:'$cancel'";
    $h[]="  },function(isConfirm){";
    $h[]="    if(!isConfirm) return;";
    $h[]="    var raw=$('#rt-plan-json-$id').val();";
    $h[]="    $.post('$page',{'apply-run':$id,'desired_json':raw},function(r){";
    $h[]="      $('#rt-plan-result-$id').html(r);";
    $h[]="      $('#rt-plan-apply-btn-$id').hide();";
    $h[]="    });";
    $h[]="  });";
    $h[]="}";

    $h[]="</script>";
    return $tpl->_ENGINE_parse_body(implode("\n",$h));
}
