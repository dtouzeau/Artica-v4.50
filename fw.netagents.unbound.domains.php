<?php
/**
 * Local Domains CRUD for Unbound on a single remote agent.
 * Operates on the `local_domains` array of the full UnboundConfig JSON blob.
 *
 * Entry:  LoadAjax('div','fw.netagents.unbound.domains.php?id={agent_id}')
 *         Loadjs('fw.netagents.unbound.domains.php?dom-add-js={agent_id}')
 *         Loadjs('fw.netagents.unbound.domains.php?dom-edit-js={agent_id}&dom_id={n}')
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }
$users = new usersMenus();
if (!$users->AsSystemAdministrator) { exit(); }

if(isset($_GET["delete-js"])){dom_delete_js();exit;}
if (isset($_GET["search"]))  { domains_list();  exit; }
if (isset($_GET["dom-buttons"]))  { dom_button();  exit; }
if (isset($_GET["dom-add-js"]))  { dom_add_js();  exit; }
if (isset($_GET["dom-edit-js"])) { dom_edit_js();  exit; }
if (isset($_GET["dom-form"]))    { dom_form();     exit; }
if (isset($_POST["save-dom"]))   { dom_save();     exit; }
if (isset($_POST["delete-dom"])) { dom_delete();   exit; }
domains_head();

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

function validate_config(int $id, $cfg): ?object {
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/unbound/config/validate/$id", $cfg
    ));
    return is_object($json) ? $json : null;
}

// ── Dialogs ──────────────────────────────────────────────────────────────────

function dom_add_js(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["dom-add-js"]);
    $function=$_GET["function"];
    $tpl->js_dialog2("#$id: {add_domain}", "$page?dom-form=yes&id=$id&function=$function", 750);
}
function domains_head():bool{
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = aid();
    echo "<div id='dom-buttons-$id' style='margin-top:10px;margin-bottom:5px'></div>";
    echo $tpl->search_block($page,"","","","&id=$id");
    return true;
}

function dom_edit_js(): void {
    $tpl   = new template_admin();
    $page  = CurrentPageName();
    $id    = intval($_GET["dom-edit-js"]);
    $domId = intval($_GET["dom_id"]);
    $tpl->js_dialog2("{edit_domain}", "$page?dom-form=yes&id=$id&dom_id=$domId", 750);
}
function dom_button():bool{
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $function=$_GET["function"];
    $id   = aid();

    $topbuttons[]=array("Loadjs('$page?dom-add-js=$id&function=$function')",ico_plus,"{new_domain}");
    echo $tpl->th_buttons($topbuttons);
    return true;
}

// ── List ─────────────────────────────────────────────────────────────────────

function domains_list(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = aid();
    $t    = time();
    $function=$_GET["function"];

    $cfg = get_config($id);
    if (!is_object($cfg) || (isset($cfg->Status) && !$cfg->Status)) {
        $err = is_object($cfg) ? htmlspecialchars($cfg->Error ?? "{error}") : "{error}";
        $h[] = $tpl->div_info($err);
        $h[] = "<script>";
        $h[] = "LoadAjaxSilent('dom-buttons-$id','$page?dom-buttons=yes&id=$id&function=$function');";
        $h[] = "</script>";
        echo $tpl->_ENGINE_parse_body($h);
        return;
    }

    $domains = $cfg->local_domains ?? [];

    $h = [];
    if (empty($domains)) {
        $h[] = $tpl->div_info("{no_data}");
        $h[] = "<script>";
        $h[] = "LoadAjaxSilent('dom-buttons-$id','$page?dom-buttons=yes&id=$id&function=$function');";
        $h[] = "</script>";
        echo $tpl->_ENGINE_parse_body($h);
        return;
    }

    $h[] = "<div id='dom-result-$id' style='margin-bottom:10px'></div>";
    $h[] = "<table id='table-$t' class='table table-striped' data-page-size='50'>";
    $h[] = "<thead><tr>";
    $h[] = "  <th data-sortable='true' data-type='text'>{domain_name}</th>";
    $h[] = "  <th data-sortable='true' data-type='text'>{internal}</th>";
    $h[] = "  <th data-sortable='true' data-type='text' nowrap>{key_name}</th>";
    $h[] = "  <th></th>";
    $h[] = "  <th></th>";
    $h[] = "</tr></thead><tbody>";

    $TRCLASS = null;
    foreach ($domains as $i => $dom) {
        if ($TRCLASS === "footable-odd") { $TRCLASS = null; } else { $TRCLASS = "footable-odd"; }

        $domain  = htmlspecialchars($dom->domain ?? '');
        $ddns    = !empty($dom->allow_ddns_update);
        $tsigKey = htmlspecialchars($dom->tsig_key_name ?? '');
        $comment = htmlspecialchars($dom->comment ?? '');
        if(strlen($comment)>0){
            $comment="<br><small class='text-muted'>$comment</small>";
        }

        $ddnsBadge = $ddns
            ? "<span class='badge' style='background:#1ab394;color:#fff'>{yes}</span>"
            : "<span class='badge' style='background:#676a6c;color:#fff'>{no}</span>";

        $md =md5(serialize($dom));
        $editJs   = "Loadjs('$page?dom-edit-js=$id&dom_id=$i')";
        $editBtn  = $tpl->icon_edit_field($editJs, "AsSystemAdministrator");
        $domainEnc=urlencode($domain);
        $delBtn   = $tpl->icon_delete("Loadjs('$page?delete-js=$id&dom_id=$i&md=$md&dom=$domainEnc')", "AsSystemAdministrator");

        $h[] = "<tr class='$TRCLASS' id='$md'>";
        $h[] = "  <td style='width:99%'><strong>$domain</strong>$comment</td>";
        $h[] = "  <td style='width:1%' nowrap>$ddnsBadge</td>";
        $h[] = "  <td style='width:1%' nowrap><span style='font-family:monospace'>$tsigKey</span></td>";
        $h[] = "  <td style='width:1%'>$editBtn</td>";
        $h[] = "  <td style='width:1%'>$delBtn</td>";
        $h[] = "</tr>";
    }
    $h[] = "</tbody>";
    $h[] = "<tfoot><tr><td colspan='5'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $h[] = "</table>";
    $h[] = "<script>NoSpinner();\n" . implode("\n", $tpl->ICON_SCRIPTS);
    $h[]="LoadAjaxSilent('dom-buttons-$id','$page?dom-buttons=yes&id=$id&function=$function');";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}
function dom_delete_js():bool{
    $id=intval($_GET["delete-js"]);
    $dom_id=intval($_GET["dom_id"]);
    $md=$_GET["md"];
    $dom=$_GET["dom"];
   // $page?delete-js=$id&dom_id=$i&md=$md&dom=$domainEnc
    $tpl=new template_admin();
    return $tpl->js_confirm_delete("$dom","delete-dom","$id|$dom_id","$('#$md').remove()");

}

// ── Form ─────────────────────────────────────────────────────────────────────

function dom_form(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = aid();
    $function = "";
    if(isset($_GET["function"])){
        $function = $_GET["function"];
    }
    $domId = isset($_GET["dom_id"]) ? intval($_GET["dom_id"]) : -1;

    $domain = $tsigKeyName = $algorithm = $secretKey = $comment = '';
    $ddns = 0;

    if ($domId >= 0) {
        $cfg = get_config($id);
        if (is_object($cfg) && !empty($cfg->local_domains[$domId])) {
            $dom        = $cfg->local_domains[$domId];
            $domain     = htmlspecialchars($dom->domain ?? '');
            $ddns       = !empty($dom->allow_ddns_update) ? 1 : 0;
            $tsigKeyName = htmlspecialchars($dom->tsig_key_name ?? '');
            $algorithm  = htmlspecialchars($dom->algorithm ?? '');
            $secretKey  = htmlspecialchars($dom->secret_key ?? '');
            $comment    = htmlspecialchars($dom->comment ?? '');
        }
    }

    $ddnsChk = $ddns ? 'checked' : '';
    $tsigDisplay = $ddns ? '' : 'display:none';

    $algos = ['HMAC-SHA256','HMAC-MD5','HMAC-SHA1'];
    $algoOpts = '';
    foreach ($algos as $a) {
        $sel = ($a === $algorithm) ? "selected" : "";
        $algoOpts .= "<option value='$a' $sel>$a</option>";
    }

    $h = [];
    $h[] = "<div id='dom-save-result-$id'></div>";
    $h[] = "<div class='form-group'><label>{domain_name}</label>";
    $h[] = "  <input type='text' id='dom-domain-$id' class='form-control' value='$domain' placeholder='lan.example.com'></div>";
    $h[] = "<div class='checkbox'><label>";
    $h[] = "  <input type='checkbox' id='dom-ddns-$id' $ddnsChk onchange='DomDDNSChange_$id()'> {allow_ddns_update}</label></div>";
    $h[] = "<div id='dom-tsig-box-$id' style='$tsigDisplay;margin-top:10px'>";
    $h[] = "  <div class='ibox' style='margin-bottom:0'><div class='ibox-content'>";
    $h[] = "    <div class='form-group'><label>{tsig_key_name}</label>";
    $h[] = "      <input type='text' id='dom-tsig-name-$id' class='form-control' value='$tsigKeyName'></div>";
    $h[] = "    <div class='form-group'><label>{tsig_algorithm}</label>";
    $h[] = "      <select id='dom-tsig-algo-$id' class='form-control'>$algoOpts</select></div>";
    $h[] = "    <div class='form-group'><label>{tsig_secret}</label>";
    $h[] = "      <input type='text' id='dom-tsig-secret-$id' class='form-control' value='$secretKey'></div>";
    $h[] = "  </div></div>";
    $h[] = "</div>";
    $h[] = "<div class='form-group' style='margin-top:10px'><label>{comment}</label>";
    $h[] = "  <input type='text' id='dom-comment-$id' class='form-control' value='$comment'></div>";
    $h[] = "<div style='margin-top:15px;text-align:right'>";
    $h[] = "  <button class='btn btn-primary' onclick='DomSave_$id()'>";
    $h[] = "    <i class='fas fa-save'></i> {save}</button>";
    $h[] = "</div>";

    $h[] = "<script>";
    $h[] = "function DomDDNSChange_$id(){";
    $h[] = "  if(\$('#dom-ddns-$id').is(':checked')){\$('#dom-tsig-box-$id').show();}";
    $h[] = "  else{\$('#dom-tsig-box-$id').hide();}";
    $h[] = "}";
    $h[] = "function DomSave_$id(){";
    $h[] = "  var domain=\$.trim(\$('#dom-domain-$id').val());";
    $h[] = "  if(!domain){ alert('{domain_name}'); return; }";
    $h[] = "  \$('#dom-save-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i></div>');";
    $h[] = "  \$.post('$page',{";
    $h[] = "    'save-dom':1, id:$id, dom_id:$domId,";
    $h[] = "    domain:domain,";
    $h[] = "    allow_ddns_update:\$('#dom-ddns-$id').is(':checked')?1:0,";
    $h[] = "    tsig_key_name:\$.trim(\$('#dom-tsig-name-$id').val()),";
    $h[] = "    algorithm:\$('#dom-tsig-algo-$id').val(),";
    $h[] = "    secret_key:\$.trim(\$('#dom-tsig-secret-$id').val()),";
    $h[] = "    comment:\$.trim(\$('#dom-comment-$id').val())";
    $h[] = "  },function(r){";
    $h[] = "    \$('#dom-save-result-$id').html(r);";
    if(strlen($function)>1) {
        $h[] = "$function();";
    }
    if($domId<1) {
        $h[] = "      dialogInstance2.close();";
    }
    $h[] = "  });";
    $h[] = "}";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Save ─────────────────────────────────────────────────────────────────────

function dom_save(): void {
    $tpl   = new template_admin();
    $id    = aid();
    $domId = intval($_POST["dom_id"] ?? -1);

    $cfg = get_config($id);
    if (!is_object($cfg) || (isset($cfg->Status) && !$cfg->Status)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $domains = isset($cfg->local_domains) ? json_decode(json_encode($cfg->local_domains), true) : [];

    $entry = [
        "domain"            => trim($_POST["domain"] ?? ''),
        "allow_ddns_update" => intval($_POST["allow_ddns_update"] ?? 0) === 1,
        "tsig_key_name"     => trim($_POST["tsig_key_name"] ?? ''),
        "algorithm"         => trim($_POST["algorithm"] ?? ''),
        "secret_key"        => trim($_POST["secret_key"] ?? ''),
        "comment"           => trim($_POST["comment"] ?? ''),
    ];

    if ($domId >= 0 && $domId < count($domains)) {
        $domains[$domId] = $entry;
    } else {
        $domains[] = $entry;
    }

    $cfgArr = json_decode(json_encode($cfg), true);
    $cfgArr["local_domains"] = $domains;

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

    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {domain_saved}"));
    admin_tracks("Netagent #$id: Local domain saved ({$entry['domain']})");
}

// ── Delete ───────────────────────────────────────────────────────────────────

function dom_delete(): void {
    $tpl   = new template_admin();
    $tpl->CLEAN_POST();
    $tb=explode("|",$_POST["delete-dom"]);
    $id    = $tb[0];
    $domId = $tb[1];

    $cfg = get_config($id);
    if (!is_object($cfg) || (isset($cfg->Status) && !$cfg->Status)) {
        echo $tpl->_ENGINE_parse_body("{error}");
        return;
    }

    $domains = isset($cfg->local_domains) ? json_decode(json_encode($cfg->local_domains), true) : [];
    if ($domId < 0 || $domId >= count($domains)) {
        echo $tpl->_ENGINE_parse_body("{error}");
        return;
    }

    array_splice($domains, $domId, 1);
    $cfgArr = json_decode(json_encode($cfg), true);
    $cfgArr["local_domains"] = $domains;

    $result = save_config($id, $cfgArr);
    if (!is_object($result) || (isset($result->Status) && !$result->Status)) {
        $err = is_object($result) ? htmlspecialchars($result->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($err);
        return;
    }
    admin_tracks("Netagent #$id: Local domain #$domId deleted");
}

// ── JS block for list page ───────────────────────────────────────────────────


