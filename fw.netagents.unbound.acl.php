<?php
/**
 * Access Control rules CRUD for Unbound on a single remote agent.
 * Operates on the `access_control` array of the full UnboundConfig JSON blob.
 * Order matters — rules are evaluated top-to-bottom by Unbound.
 *
 * Entry:  LoadAjax('div','fw.netagents.unbound.acl.php?id={agent_id}')
 *         Loadjs('fw.netagents.unbound.acl.php?acl-add-js={agent_id}')
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }
$users = new usersMenus();
if (!$users->AsSystemAdministrator) { exit(); }

if(isset($_GET["delete-js"])){acl_delete_js();exit;}
if (isset($_GET["acl-add-js"]))    { acl_add_js();    exit; }
if (isset($_GET["acl-form"]))      { acl_form();      exit; }
if (isset($_POST["save-acl"]))     { acl_save();      exit; }
if (isset($_POST["delete-acl"]))   { acl_delete();    exit; }
if (isset($_POST["reorder-acl"]))  { acl_reorder();   exit; }
if (isset($_POST["toggle-acl"]))   { acl_toggle();    exit; }
if(isset($_GET["search"])){acl_search();exit;}
if(isset($_GET["acl-buttons"])){acl_buttons();exit;}
acl_head();

// ── Helpers ──────────────────────────────────────────────────────────────────

function aid(): int {
    return intval($_GET["id"] ?? $_POST["id"] ?? 0);
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

// ── Dialog ───────────────────────────────────────────────────────────────────

function acl_add_js(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["acl-add-js"]);
    $function=$_GET["function"];
    $tpl->js_dialog2("{add_acl_rule}", "$page?acl-form=yes&id=$id&function$function", 700);
}

// ── List ─────────────────────────────────────────────────────────────────────

function acl_head():bool{
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = aid();
    echo "<div id='acl-buttons-$id' style='margin-top:10px;margin-bottom: 10px'></div>";
    echo $tpl->search_block($page,"","","","&id=$id");
    return true;
}
function acl_buttons():bool{
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = aid();
    $function=$_GET["function"];

    $cfg = get_config($id);
    $acEnabled = 0;
    if (is_object($cfg) && !(isset($cfg->Status) && !$cfg->Status)) {
        $acEnabled = intval($cfg->parameters->UnboundAccessControl ?? 0);
    }

    $th[]=array("Loadjs('$page?acl-add-js=$id&function=$function')",ico_plus,"{add_acl_rule}");

    if ($acEnabled) {
        $th[]=array("AclToggle_$id(0)","fas fa-toggle-on","{disable_access_control}");
    } else {
        $th[]=array("AclToggle_$id(1)","fas fa-toggle-off","{enable_access_control}");
    }
    $h[]=$tpl->th_buttons($th);

    if (!is_object($cfg) || (isset($cfg->Status) && !$cfg->Status)) {
        $err = is_object($cfg) ? htmlspecialchars($cfg->Error ?? "{error}") : "{error}";
        $h[]= $tpl->div_error($err);
        echo $tpl->_ENGINE_parse_body($h);
        return true;
    }

    $rules = $cfg->access_control ?? [];

    if (!$acEnabled && !empty($rules)) {
        $h[] = $tpl->div_warning("<i class='fas fa-exclamation-triangle'></i> {acl_disabled_note}");
    }

    if (empty($rules)) {
        $h[] = $tpl->div_info("{no_acl_rules_defined}");
    }

    $h[] = "<script>";
    $h[] = "function AclToggle_$id(val){";
    $h[] = "  \$.post('$page',{'toggle-acl':val,id:$id},function(r){";
    $h[] = "    LoadAjaxSilent('acl-buttons-$id','$page?acl-buttons=yes&id=$id&function=$function');";
    $h[] = "  });";
    $h[] = "}";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body($h);
    return true;
}
function acl_search(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = aid();
    $function=$_GET["function"];

    $cfg = get_config($id);
    if (!is_object($cfg) || (isset($cfg->Status) && !$cfg->Status)) {
        $h[] = "<script>";
        $h[]="LoadAjax('acl-buttons-$id','$page?acl-buttons=yes&id=$id&function=$function');";
        $h[]="</script>";
        echo $tpl->_ENGINE_parse_body($h);
        return;
    }

    // Check if access control is disabled in parameters
    $acEnabled = intval($cfg->parameters->UnboundAccessControl ?? 0);
    $rules     = $cfg->access_control ?? [];


    if (empty($rules)) {
        $h[] = "<script>";
        $h[]="LoadAjax('acl-buttons-$id','$page?acl-buttons=yes&id=$id&function=$function');";
        $h[]="</script>";
        echo $tpl->_ENGINE_parse_body($h);
        return;
    }
    $h = [];
    $h[] = "<div id='acl-result-$id' style='margin-bottom:10px'></div>";
    $h[] = "<p class='text-muted'><i class='fas fa-info-circle'></i> {acl_order_note}</p>";
    $search=$_GET["search"];
    $search=str_replace("*",".*?",$search);

    $h[] = "<table class='table table-striped' id='acl-table-$id'><tbody>";
    foreach ($rules as $i => $rule) {
        $cidr   = htmlspecialchars($rule->cidr ?? '');
        $action = htmlspecialchars($rule->action ?? '');
        if(strlen($search)>1){
            if(!preg_match("#$search#i",serialize($rule))){
                continue;
            }
        }

        $actionColors = [
            'allow'       => '#1ab394',
            'deny'        => '#ed5565',
            'refuse'      => '#f8ac59',
            'allow_snoop' => '#1c84c6',
        ];
        $bgColor = $actionColors[$action] ?? '#676a6c';
        $actionBadge = "<span class='badge' style='background:$bgColor;color:#fff'>$action</span>";
        $md=md5(serialize($rule));
        $cidrEnc=urlencode($cidr);
        $deleteJs = "Loadjs('$page?delete-js=$id&id=$i&md=$md&val=$cidrEnc')";
        $delBtn   = $tpl->icon_delete($deleteJs, "AsSystemAdministrator");

        $h[] = "<tr draggable='true' data-idx='$i' class='acl-row-$id' id='$md'"
             . " ondragstart='aclDragStart_$id(event)' ondragover='aclDragOver_$id(event)'"
             . " ondrop='aclDrop_$id(event)' style='cursor:move'>";
        $h[] = "  <td style='width:1%;vertical-align:middle'><i class='fas fa-grip-vertical' style='color:#ccc'></i></td>";
        $h[] = "  <td><strong style='font-family:monospace'>$cidr</strong></td>";
        $h[] = "  <td>$actionBadge</td>";
        $h[] = "  <td style='width:1%'>$delBtn</td>";
        $h[] = "</tr>";
    }
    $h[] = "</tbody></table>";

    $h[] = "<script>";
    $h[]="LoadAjax('acl-buttons-$id','$page?acl-buttons=yes&id=$id&function=$function');";
    $h[] = "NoSpinner();\n" . implode("\n", $tpl->ICON_SCRIPTS);
    $h[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Form ─────────────────────────────────────────────────────────────────────

function acl_form(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = aid();
    $function="";

    $actions = ['allow','deny','refuse','allow_snoop'];
    $actOpts = '';
    foreach ($actions as $a) {
        $actOpts .= "<option value='$a'>$a</option>";
    }

    $h = [];
    $h[] = "<div id='acl-save-result-$id'></div>";
    $h[] = "<div class='form-group'><label>{acl_cidr}</label>";
    $h[] = "  <input type='text' id='acl-cidr-$id' class='form-control' placeholder='192.168.0.0/16'></div>";
    $h[] = "<div class='form-group'><label>{acl_action}</label>";
    $h[] = "  <select id='acl-action-$id' class='form-control'>$actOpts</select></div>";
    $h[] = "<div style='margin-top:15px;text-align:right'>";
    $h[] = "  <button class='btn btn-primary' onclick='AclSave_$id()'>";
    $h[] = "    <i class='fas fa-save'></i> {save}</button>";
    $h[] = "</div>";

    $h[] = "<script>";
    $h[] = "function AclSave_$id(){";
    $h[] = "  var cidr=\$.trim(\$('#acl-cidr-$id').val());";
    $h[] = "  if(!cidr){ alert('{acl_cidr}'); return; }";
    $h[] = "  \$('#acl-save-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i></div>');";
    $h[] = "  \$.post('$page',{";
    $h[] = "    'save-acl':1, id:$id,";
    $h[] = "    cidr:cidr,";
    $h[] = "    action:\$('#acl-action-$id').val()";
    $h[] = "  },function(r){";
    if(strlen($function)>1){
        $h[] = "    $function();";
    }
    $h[] = "    dialogInstance2.close();";
    $h[] = "  });";
    $h[] = "}";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Save ─────────────────────────────────────────────────────────────────────

function acl_save(): void {
    $tpl = new template_admin();
    $id  = aid();

    $cfg = get_config($id);
    if (!is_object($cfg) || (isset($cfg->Status) && !$cfg->Status)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $rules = isset($cfg->access_control) ? json_decode(json_encode($cfg->access_control), true) : [];

    $cidr   = trim($_POST["cidr"] ?? '');
    $action = trim($_POST["action"] ?? 'allow');
    $allowedActions = ['allow','deny','refuse','allow_snoop'];
    if (!in_array($action, $allowedActions, true)) { $action = 'allow'; }

    $rules[] = [
        "cidr"   => $cidr,
        "action" => $action,
    ];

    $cfgArr = json_decode(json_encode($cfg), true);
    $cfgArr["access_control"] = $rules;

    $result = save_config($id, $cfgArr);
    if (!is_object($result) || (isset($result->Status) && !$result->Status)) {
        $err = is_object($result) ? htmlspecialchars($result->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }

    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {acl_rule_saved}"));
    admin_tracks("Netagent #$id: ACL rule added (" . htmlspecialchars($cidr) . " $action)");
}

// ── Delete ───────────────────────────────────────────────────────────────────

function acl_delete_js():bool{
    $tpl = new template_admin();
    $agentid=intval($_GET['delete-js']);
    $id=intval($_GET['id']);
    $md=$_GET['md'];
    $val=$_GET['val'];
    return $tpl->js_confirm_delete($val,"delete-acl","$agentid|$id","$('#$md').remove()");


}
function acl_delete(): void {
    $tpl   = new template_admin();
    $tpl->CLEAN_POST();
    list($id,$aclId)=explode("|",$_POST['delete-acl']);
    $id=intval($id);
    $aclId=intval($aclId);

    $cfg = get_config($id);
    if (!is_object($cfg) || (isset($cfg->Status) && !$cfg->Status)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $rules = isset($cfg->access_control) ? json_decode(json_encode($cfg->access_control), true) : [];
    if ($aclId < 0 || $aclId >= count($rules)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    array_splice($rules, $aclId, 1);
    $cfgArr = json_decode(json_encode($cfg), true);
    $cfgArr["access_control"] = $rules;

    $result = save_config($id, $cfgArr);
    if (!is_object($result) || (isset($result->Status) && !$result->Status)) {
        $err = is_object($result) ? htmlspecialchars($result->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }

    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {acl_rule_deleted}"));
    admin_tracks("Netagent #$id: ACL rule #$aclId deleted");
}

// ── Reorder ──────────────────────────────────────────────────────────────────

function acl_reorder(): void {
    $tpl = new template_admin();
    $id  = aid();

    $order = $_POST["order"] ?? [];
    if (!is_array($order) || empty($order)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $cfg = get_config($id);
    if (!is_object($cfg) || (isset($cfg->Status) && !$cfg->Status)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $rules = isset($cfg->access_control) ? json_decode(json_encode($cfg->access_control), true) : [];
    $newRules = [];
    foreach ($order as $idx) {
        $idx = intval($idx);
        if (isset($rules[$idx])) {
            $newRules[] = $rules[$idx];
        }
    }

    if (count($newRules) !== count($rules)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $cfgArr = json_decode(json_encode($cfg), true);
    $cfgArr["access_control"] = $newRules;

    $result = save_config($id, $cfgArr);
    if (!is_object($result) || (isset($result->Status) && !$result->Status)) {
        $err = is_object($result) ? htmlspecialchars($result->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }

    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {acl_rule_saved}"));
    admin_tracks("Netagent #$id: ACL rules reordered");
}

// ── Toggle access control ────────────────────────────────────────────────────

function acl_toggle(): void {
    $tpl = new template_admin();
    $id  = aid();
    $val = intval($_POST["toggle-acl"] ?? 0);

    $cfg = get_config($id);
    if (!is_object($cfg) || (isset($cfg->Status) && !$cfg->Status)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $cfgArr = json_decode(json_encode($cfg), true);
    $cfgArr["parameters"]["UnboundAccessControl"] = $val ? 1 : 0;

    $result = save_config($id, $cfgArr);
    if (!is_object($result) || (isset($result->Status) && !$result->Status)) {
        $err = is_object($result) ? htmlspecialchars($result->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }

    $state = $val ? "{enabled}" : "{disabled}";
    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {access_control}: $state"));
    admin_tracks("Netagent #$id: Unbound access control " . ($val ? "enabled" : "disabled"));
}

// ── JS block ─────────────────────────────────────────────────────────────────

function acl_js_block(int $id, string $page): string {
    $l = [];
    $l[] = "<script>";

    // Delete
    $l[] = "function AclDelete_$id(idx){";
    $l[] = "  swal({title:'{acl_rule_deleted}?',type:'warning',showCancelButton:true,";
    $l[] = "    confirmButtonColor:'#ed5565',confirmButtonText:'{yes}',cancelButtonText:'{cancel}'},";
    $l[] = "  function(ok){ if(!ok) return;";
    $l[] = "    \$('#acl-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i></div>');";
    $l[] = "    \$.post('$page',{'delete-acl':idx,id:$id},function(r){";
    $l[] = "      \$('#acl-result-$id').html(r);";
    $l[] = "      if(r.indexOf('alert-success')>-1){ setTimeout(function(){ LoadAjax(\$('#acl-result-$id').closest('.tab-pane').attr('id'),'$page?id=$id'); },500); }";
    $l[] = "    });";
    $l[] = "  });";
    $l[] = "}";

    // Drag-and-drop reorder
    $l[] = "var _aclDragIdx_$id=null;";
    $l[] = "function aclDragStart_$id(e){";
    $l[] = "  _aclDragIdx_$id=parseInt(e.target.closest('tr').getAttribute('data-idx'));";
    $l[] = "  e.dataTransfer.effectAllowed='move';";
    $l[] = "}";
    $l[] = "function aclDragOver_$id(e){ e.preventDefault(); e.dataTransfer.dropEffect='move'; }";
    $l[] = "function aclDrop_$id(e){";
    $l[] = "  e.preventDefault();";
    $l[] = "  var targetIdx=parseInt(e.target.closest('tr').getAttribute('data-idx'));";
    $l[] = "  if(_aclDragIdx_$id===null||_aclDragIdx_$id===targetIdx) return;";
    $l[] = "  var rows=\$('#acl-table-$id tbody tr');";
    $l[] = "  var order=[];";
    $l[] = "  rows.each(function(){ order.push(parseInt(\$(this).attr('data-idx'))); });";
    $l[] = "  var fromPos=order.indexOf(_aclDragIdx_$id);";
    $l[] = "  var toPos=order.indexOf(targetIdx);";
    $l[] = "  order.splice(fromPos,1);";
    $l[] = "  order.splice(toPos,0,_aclDragIdx_$id);";
    $l[] = "  \$('#acl-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i></div>');";
    $l[] = "  \$.post('$page',{'reorder-acl':1,id:$id,'order[]':order},function(r){";
    $l[] = "    \$('#acl-result-$id').html(r);";
    $l[] = "    if(r.indexOf('alert-success')>-1){ setTimeout(function(){ LoadAjax(\$('#acl-result-$id').closest('.tab-pane').attr('id'),'$page?id=$id'); },500); }";
    $l[] = "  });";
    $l[] = "}";

    $l[] = "</script>";
    return implode("\n", $l);
}
