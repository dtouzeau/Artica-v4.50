<?php
/**
 * DNS Records CRUD for Unbound on a single remote agent.
 * Operates on the `records` array of the full UnboundConfig JSON blob.
 *
 * Entry:  LoadAjax('div','fw.netagents.unbound.records.php?id={agent_id}')
 *         Loadjs('fw.netagents.unbound.records.php?rec-add-js={agent_id}')
 *         Loadjs('fw.netagents.unbound.records.php?rec-edit-js={agent_id}&rec_id={n}')
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }
$users = new usersMenus();
if (!$users->AsSystemAdministrator) { exit(); }

if(isset($_GET["delete-js"])){rec_delete_js();exit;}
if (isset($_GET["rec-add-js"]))  { rec_add_js();  exit; }
if (isset($_GET["rec-edit-js"])) { rec_edit_js();  exit; }
if (isset($_GET["rec-buttons"])) { records_button();  exit; }

if (isset($_GET["rec-form"]))    { rec_form();     exit; }
if (isset($_GET["search"]))    { records_list();     exit; }
if (isset($_POST["save-rec"]))   { rec_save();     exit; }
if (isset($_POST["delete-rec"])) { rec_delete();   exit; }
records_head();

// ── Helpers ──────────────────────────────────────────────────────────────────

function aid(): int {
    return intval($_GET["id"] ?? $_POST["id"] ?? 0);
}

function get_config(int $id) {
    $raw = $GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/unbound/config/$id");
    return json_decode($raw);
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

function rec_add_js(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["rec-add-js"]);
    $function=$_GET["function"];
    $tpl->js_dialog2("{new_record}", "$page?rec-form=yes&id=$id&function=$function", 750);
}

function rec_edit_js(): void {
    $tpl   = new template_admin();
    $page  = CurrentPageName();
    $id    = intval($_GET["rec-edit-js"]);
    $recId = intval($_GET["rec_id"]);
    $function=$_GET["function"];
    $tpl->js_dialog2("{edit} #$recId", "$page?rec-form=yes&id=$id&rec_id=$recId&function=$function", 750);
}

// ── List ─────────────────────────────────────────────────────────────────────

function records_head():bool{
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = aid();
    echo "<div style='margin-top:10px;margin-bottom: 5px' id='unbound-records-buttons-$id'></div>";
    echo $tpl->search_block($page,"","","","&id=$id");
    return true;
}

function records_button():bool{
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $function=$_GET["function"];
    $id   = aid();

    $topbuttons[]=array("Loadjs('$page?rec-add-js=$id&function=$function')",ico_plus,"{new_record}");
    echo $tpl->th_buttons($topbuttons);
    return true;
}

function records_list(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = aid();
    $t    = time();
    $function=$_GET["function"];
    $cfg = get_config($id);
    if (!is_object($cfg) || (isset($cfg->Status) && !$cfg->Status)) {
        $err = is_object($cfg) ? htmlspecialchars($cfg->Error ?? "{error}") : "{error}";
        $h[] = $tpl->_ENGINE_parse_body($tpl->div_error($err));
        $h[] = "<script>";
        $h[] = "LoadAjaxSilent('unbound-records-buttons-$id','$page?rec-buttons=yes&id=$id&function=$function')";
        $h[] = "</script>";
        echo @implode("\n", $h);
        return;
    }

    $records = $cfg->records ?? [];

    $h = [];

    if (empty($records)) {
        $h[] = $tpl->div_info("{no_records_defined}");
        $h[] = "<script>";
        $h[] = "LoadAjaxSilent('unbound-records-buttons-$id','$page?rec-buttons=yes&id=$id&function=$function')";
        $h[] = "</script>";
        echo @implode("\n", $h);
        return;
    }

    $h[] = "<div id='rec-result-$id' style='margin-bottom:10px'></div>";
    $h[] = "<table id='table-$t' class='table table-striped' data-page-size='50'>";
    $h[] = "<thead><tr>";
    $h[] = "  <th data-sortable='true' data-type='text' nowrap>{record}</th>";
    $h[] = "  <th data-sortable='true' data-type='text' nowrap>{type}</th>";
    $h[] = "  <th data-sortable='true' data-type='text' nowrap>{content}</th>";
    $h[] = "  <th data-sortable='true' data-type='number' nowrap>TTL</th>";
    $h[] = "  <th data-sortable='true' data-type='text' nowrap>{zone}</th>";
    $h[] = "  <th></th>";
    $h[] = "  <th></th>";
    $h[] = "</tr></thead><tbody>";

    $TRCLASS = null;
    foreach ($records as $i => $rec) {
        if ($TRCLASS === "footable-odd") { $TRCLASS = null; } else { $TRCLASS = "footable-odd"; }

        $name    = htmlspecialchars($rec->name ?? '');
        $type    = htmlspecialchars($rec->type ?? '');
        $content = htmlspecialchars($rec->content ?? '');
        $ttl     = intval($rec->ttl ?? 3600);
        $zone    = htmlspecialchars($rec->zone ?? '');
        $disabled = !empty($rec->disabled);

        $typeBadge = "<span class='badge' style='background:#1c84c6;color:#fff'>$type</span>";
        if ($disabled) {
            $typeBadge .= " <span class='badge' style='background:#676a6c;color:#fff'>{disabled}</span>";
        }

        $editJs   = "Loadjs('$page?rec-edit-js=$id&rec_id=$i')";
        $md=md5(serialize($rec));
        $editBtn  = $tpl->icon_edit_field($editJs, "AsSystemAdministrator");
        $delBtn   = $tpl->icon_delete("Loadjs('$page?delete-js=$id&rec_id=$i&md=$md')", "AsSystemAdministrator");

        $h[] = "<tr class='$TRCLASS' id='$md'>";
        $h[] = "  <td><strong>$name</strong></td>";
        $h[] = "  <td style='width:1%'>$typeBadge</td>";
        $h[] = "  <td><span style='font-family:monospace'>$content</span></td>";
        $h[] = "  <td style='width:1%'>$ttl</td>";
        $h[] = "  <td>$zone</td>";
        $h[] = "  <td style='width:1%'>$editBtn</td>";
        $h[] = "  <td style='width:1%'>$delBtn</td>";
        $h[] = "</tr>";
    }
    $h[] = "</tbody>";
    $h[] = "<tfoot><tr><td colspan='7'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $h[] = "</table>";
    $h[] = "<script>NoSpinner();\n" . implode("\n", $tpl->ICON_SCRIPTS);
    $h[] = "LoadAjaxSilent('unbound-records-buttons-$id','$page?rec-buttons=yes&id=$id&function=$function')";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Form ─────────────────────────────────────────────────────────────────────

function getDomains($id,$cfg,$default):string{

    $domains = $cfg->local_domains ?? [];

    $h[] = "      <select id='rec-zone-$id' class='form-control'>";



    foreach ($domains as $i => $dom) {
        $selected="";
        $domain = htmlspecialchars($dom->domain ?? '');
        if(strlen($selected)==0 && $dom->domain==$default){
            $selected="selected";
        }
        $h[] = "        <option value='$domain' $selected>$domain</option>";
    }

    $h[] = "      </select>";
    return implode("\n", $h);
}

function rec_form(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = aid();
    $recId = isset($_GET["rec_id"]) ? intval($_GET["rec_id"]) : -1;
    $function=$_GET["function"];
    $cfg = get_config($id);
    $name = $content = $zone = $comment = '';
    $type = 'A';
    $ttl  = 3600;
    $prio = 0;
    $disabled = 0;

    if ($recId >= 0) {
        if (is_object($cfg) && !empty($cfg->records[$recId])) {
            $rec     = $cfg->records[$recId];
            $name    = htmlspecialchars($rec->name ?? '');
            $type    = htmlspecialchars($rec->type ?? 'A');
            $content = htmlspecialchars($rec->content ?? '');
            $ttl     = intval($rec->ttl ?? 3600);
            $prio    = intval($rec->prio ?? 0);
            $zone    = htmlspecialchars($rec->zone ?? '');
            $comment = htmlspecialchars($rec->comment ?? '');
            $disabled = !empty($rec->disabled) ? 1 : 0;
        }
    }

    $types = ['A','AAAA','CNAME','MX','NS','TXT','PTR','SRV'];
    $typeOpts = '';
    foreach ($types as $t) {
        $sel = ($t === $type) ? "selected" : "";
        $typeOpts .= "<option value='$t' $sel>$t</option>";
    }

    $prioDisplay = in_array($type, ['MX','SRV']) ? '' : 'display:none';
    $disabledChk = $disabled ? 'checked' : '';


    $h = [];
    $h[] = "<div id='rec-save-result-$id'></div>";
    $h[] = "<div class='form-group'><label>{name}</label>";
    $h[] = "  <input type='text' id='rec-name-$id' class='form-control' value='$name' placeholder='host.example.com'></div>";
    $h[] = "<div class='row'>";
    $h[] = "  <div class='col-md-6'><div class='form-group'><label>{type}</label>";
    $h[] = "    <select id='rec-type-$id' class='form-control' onchange='RecTypeChange_$id()'>$typeOpts</select></div></div>";
    $h[] = "  <div class='col-md-6'><div class='form-group'><label>{ttl}</label>";
    $h[] = "    <input type='number' id='rec-ttl-$id' class='form-control' value='$ttl' min='1'></div></div>";
    $h[] = "</div>";
    $h[] = "<div class='form-group'><label>{content}</label>";
    $h[] = "  <input type='text' id='rec-content-$id' class='form-control' value='$content'></div>";
    $h[] = "<div id='rec-prio-box-$id' style='$prioDisplay'>";
    $h[] = "  <div class='form-group'><label>{priority}</label>";
    $h[] = "    <input type='number' id='rec-prio-$id' class='form-control' value='$prio' min='0'></div>";
    $h[] = "</div>";
    $h[] = "<div class='row'>";
    $h[] = "  <div class='col-md-6'>";
    $h[] = "        <div class='form-group'><label>{zone}</label>";
    $h[] = getDomains($id,$cfg,$zone);
    $h[] = "  </div>";
    $h[] =  "</div>";
    $h[] = "  <div class='col-md-6'><div class='form-group'><label>{comment}</label>";
    $h[] = "    <input type='text' id='rec-comment-$id' class='form-control' value='$comment'></div></div>";
    $h[] = "</div>";
    $h[] = "<div class='checkbox'><label><input type='checkbox' id='rec-disabled-$id' $disabledChk> {disabled}</label></div>";
    $h[] = "<div style='margin-top:15px;text-align:right'>";
    $h[] = "  <button class='btn btn-primary' onclick='RecSave_$id()'>";
    $h[] = "    <i class='fas fa-save'></i> {save}</button>";
    $h[] = "</div>";

    $h[] = "<script>";
    $h[] = "function RecTypeChange_$id(){";
    $h[] = "  var t=\$('#rec-type-$id').val();";
    $h[] = "  if(t==='MX'||t==='SRV'){\$('#rec-prio-box-$id').show();}else{\$('#rec-prio-box-$id').hide();}";
    $h[] = "}";
    $h[] = "function RecSave_$id(){";
    $h[] = "  var name=\$.trim(\$('#rec-name-$id').val());";
    $h[] = "  if(!name){ alert('{record_name}'); return; }";
    $h[] = "  \$('#rec-save-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i></div>');";
    $h[] = "  \$.post('$page',{";
    $h[] = "    'save-rec':1, id:$id, rec_id:$recId,";
    $h[] = "    name:name,";
    $h[] = "    type:\$('#rec-type-$id').val(),";
    $h[] = "    content:\$.trim(\$('#rec-content-$id').val()),";
    $h[] = "    ttl:\$('#rec-ttl-$id').val(),";
    $h[] = "    prio:\$('#rec-prio-$id').val(),";
    $h[] = "    zone:\$.trim(\$('#rec-zone-$id').val()),";
    $h[] = "    comment:\$.trim(\$('#rec-comment-$id').val()),";
    $h[] = "    disabled:\$('#rec-disabled-$id').is(':checked')?1:0";
    $h[] = "  },function(r){";
    $h[] = "    \$('#rec-save-result-$id').html(r);";
    if(strlen($function)>3) {
        $h[] = "    $function();";
    }
    $h[] = "    if(r.indexOf('alert-success')>-1){ dialogInstance2.close(); }";
    $h[] = "  });";
    $h[] = "}";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Save ─────────────────────────────────────────────────────────────────────

function rec_save(): void {
    $tpl   = new template_admin();
    $id    = aid();
    $recId = intval($_POST["rec_id"] ?? -1);

    $cfg = get_config($id);
    if (!is_object($cfg) || (isset($cfg->Status) && !$cfg->Status)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $records = isset($cfg->records) ? json_decode(json_encode($cfg->records), true) : [];

    $entry = [
        "name"     => trim($_POST["name"] ?? ''),
        "type"     => trim($_POST["type"] ?? 'A'),
        "content"  => trim($_POST["content"] ?? ''),
        "ttl"      => intval($_POST["ttl"] ?? 3600),
        "prio"     => intval($_POST["prio"] ?? 0),
        "zone"     => trim($_POST["zone"] ?? ''),
        "comment"  => trim($_POST["comment"] ?? ''),
        "disabled" => intval($_POST["disabled"] ?? 0) === 1,
    ];

    if ($recId >= 0 && $recId < count($records)) {
        $entry["id"] = $records[$recId]["id"] ?? $recId;
        $records[$recId] = $entry;
    } else {
        $maxId = 0;
        foreach ($records as $r) { $maxId = max($maxId, intval($r["id"] ?? 0)); }
        $entry["id"] = $maxId + 1;
        $records[] = $entry;
    }

    $cfgArr = json_decode(json_encode($cfg), true);
    $cfgArr["records"] = $records;

    // Validate
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

    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {record_saved}"));
    admin_tracks("Netagent #$id: DNS record saved ({$entry['type']} {$entry['name']})");
}

// ── Delete ───────────────────────────────────────────────────────────────────


function rec_delete_js():bool{
    //delete-js=$id&rec_id=$i&md=$md
    $id=intval($_GET["delete-js"]);
    $rec_id=intval($_GET["rec_id"]);
    $md=$_GET["md"];
    $tpl=new template_admin();
    return $tpl->js_confirm_delete("#$rec_id","delete-rec","$id|$rec_id","$('#$md').remove()");

}
function rec_delete(): void {
    $tpl   = new template_admin();
    $tpl->CLEAN_POST();
    $tb=explode("|",$_POST["delete-rec"]);
    $id    = $tb[0];
    $recId = $tb[1];

    $cfg = get_config($id);
    if (!is_object($cfg) || (isset($cfg->Status) && !$cfg->Status)) {
        echo $tpl->_ENGINE_parse_body("{error}");
        return;
    }

    $records = isset($cfg->records) ? json_decode(json_encode($cfg->records), true) : [];
    if ($recId < 0 || $recId >= count($records)) {
        echo $tpl->_ENGINE_parse_body("{error}");
        return;
    }

    array_splice($records, $recId, 1);
    $cfgArr = json_decode(json_encode($cfg), true);
    $cfgArr["records"] = $records;

    $result = save_config($id, $cfgArr);
    if (!is_object($result) || (isset($result->Status) && !$result->Status)) {
        $err = is_object($result) ? htmlspecialchars($result->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($err);
        return;
    }


    admin_tracks("Netagent #$id: DNS record #$recId deleted");
}

// ── JS block for list page ───────────────────────────────────────────────────


