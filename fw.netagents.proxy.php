<?php
/**
 * System Internet proxy of one remote agent (maquette stitch_netagents_proxy).
 * The agent writes http_proxy / https_proxy to /etc/environment and to APT.
 *
 * Entry:  Loadjs('fw.netagents.proxy.php?proxy-js={agent_id}')    — opens a dialog
 *         LoadAjax('div','fw.netagents.proxy.php?id={agent_id}')  — "Proxy" tab of the agent dialog
 *         ?inner={id}[&saved=<b64 url>]                           — content only, reloaded in napx-host-{id}
 *         ?delete-js={id}                                         — removal confirmation
 *         POST napx-save={id}   -> JSON {ok, url, error}
 *         POST napx-delete={id} -> nothing on success, the error otherwise
 * API:    GET|POST|DELETE /netagents/proxy-config/{id}
 *         Body: { proxy_address, proxy_port, username, password } — POST replaces the whole object.
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }

if (isset($_GET["proxy-js"]))    { napx_dialog_js(); exit; }
if (isset($_GET["delete-js"]))   { napx_delete_js(); exit; }
if (isset($_GET["inner"]))       { napx_inner(); exit; }
if (isset($_POST["napx-save"]))  { napx_save(); exit; }
if (isset($_POST["napx-delete"])){ napx_delete(); exit; }
napx_page();

function napx_is_admin(): bool {
    $users = new usersMenus();
    return meta_can_view($users) || (bool)$users->AsSystemAdministrator;
}

function napx_h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}

// ── Model ────────────────────────────────────────────────────────────────────

// Reads the cached proxy of the agent. The password never leaves this function
// towards the browser: only has_pass does.
function napx_read(int $id, string &$err): array {
    $err = "";
    $m = array("address" => "", "port" => 0, "user" => "", "pass" => "", "has_pass" => false);
    $raw = $GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/proxy-config/$id");
    $json = json_decode((string)$raw, true);
    if (!is_array($json)) { $err = "{error}: articarest"; return $m; }
    if (isset($json["Status"]) && !$json["Status"]) {
        $err = (string)($json["Error"] ?? "{error}");
        return $m;
    }
    $m["address"]  = trim((string)($json["proxy_address"] ?? ""));
    $m["port"]     = intval($json["proxy_port"] ?? 0);
    $m["user"]     = (string)($json["username"] ?? "");
    $m["pass"]     = (string)($json["password"] ?? "");
    $m["has_pass"] = $m["pass"] !== "";
    return $m;
}

// http://[user@]host:port, as the agent builds it. No password, ever.
function napx_url(string $address, int $port, string $user): string {
    if ($address === "") { return ""; }
    $host = (strpos($address, ":") !== false && substr($address, 0, 1) !== "[") ? "[$address]" : $address;
    $cred = $user !== "" ? rawurlencode($user) . "@" : "";
    return "http://$cred$host:$port";
}

// ── Rendering ────────────────────────────────────────────────────────────────

function napx_page(): bool {
    $id = intval($_GET["id"] ?? 0);
    echo napx_style() . "<div id='napx-host-$id'>";
    napx_content($id, "");
    echo "</div>";
    return true;
}

function napx_inner(): bool {
    $id = intval($_GET["inner"]);
    $saved = base64_decode((string)($_GET["saved"] ?? ""), true);
    napx_content($id, $saved === false ? "" : $saved);
    return true;
}

function napx_content(int $id, string $saved): bool {
    $tpl = new template_admin();
    $page = CurrentPageName();
    $err = "";
    $m = napx_read($id, $err);
    if ($err !== "") {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(napx_h($err)));
        return false;
    }
    $h = array();
    $h[] = "<div class='ak napx'>";
    $h[] = napx_status_strip($m);
    $h[] = "<div class='ak-card ak-card-pad'>";
    $h[] = napx_server_section($id, $m);
    $h[] = "<div class='ak-sep' role='separator'></div>";
    $h[] = napx_auth_section($id, $m);
    $h[] = napx_banner($id, $saved);
    $h[] = napx_actions($id, $m);
    $h[] = "</div></div>";
    echo $tpl->_ENGINE_parse_body(implode("\n", $h)) . napx_js($id, $page);
    return true;
}

function napx_status_strip(array $m): string {
    if ($m["address"] === "") {
        return "<div class='ak-strip'><span class='ak-ico ak-ico-sm ak-off'><i class='fas fa-globe' aria-hidden='true'></i></span>"
            . "<div class='ak-strip-main'><span class='ak-strip-t'>{no_proxy_configured}</span>"
            . "<span class='ak-strip-s'>{napx_direct}</span></div></div>";
    }
    $url = napx_h(napx_url($m["address"], $m["port"], $m["user"]));
    $pill = $m["user"] !== "" ? "<span class='ak-pill'><i class='fas fa-key' aria-hidden='true'></i> {authentication}</span>" : "";
    return "<div class='ak-strip'><span class='ak-ico ak-ico-sm'><i class='fas fa-route' aria-hidden='true'></i></span>"
        . "<div class='ak-strip-main'><span class='ak-strip-t'>{proxy_server} <span class='napx-slash'>/</span> <span class='ak-mono'>$url</span></span>"
        . "<span class='ak-strip-s'>{napx_scope}</span></div>$pill</div>";
}

function napx_server_section(int $id, array $m): string {
    $addr = napx_h($m["address"]);
    $port = $m["port"] > 0 ? (string)$m["port"] : "";
    return "<section aria-labelledby='napx-h1-$id'><h3 class='ak-sec-t' id='napx-h1-$id'>{proxy_server}</h3>"
        . "<div class='ak-row'>"
        . "<div class='ak-f napx-f3'><label for='napx-addr-$id'>{proxy_address}</label>"
        . "<input type='text' id='napx-addr-$id' class='ak-in ak-mono' value='$addr' placeholder='192.168.1.100' autocomplete='off' aria-describedby='napx-addr-help-$id napx-addr-err-$id'>"
        . "<p class='ak-ferr' id='napx-addr-err-$id' hidden><i class='fas fa-circle-exclamation' aria-hidden='true'></i> {napx_addr_required}</p>"
        . "<p class='ak-help' id='napx-addr-help-$id'>{proxy_address_explain}</p></div>"
        . "<div class='ak-f napx-f1'><label for='napx-port-$id'>{proxy_port}</label>"
        . "<input type='number' id='napx-port-$id' class='ak-in ak-mono' value='$port' min='1' max='65535' placeholder='3128' aria-describedby='napx-port-help-$id napx-port-err-$id'>"
        . "<p class='ak-ferr' id='napx-port-err-$id' hidden><i class='fas fa-circle-exclamation' aria-hidden='true'></i> {napx_port_invalid}</p>"
        . "<p class='ak-help' id='napx-port-help-$id'>{proxy_port_explain}</p></div>"
        . "</div></section>";
}

function napx_auth_section(int $id, array $m): string {
    $user = napx_h($m["user"]);
    $ph = $m["has_pass"] ? "{napx_unchanged}" : "{password}";
    $help = $m["has_pass"] ? "{napx_pass_keep}" : "{napx_pass_help}";
    return "<section aria-labelledby='napx-h2-$id'><h3 class='ak-sec-t' id='napx-h2-$id'>{authentication} <span class='ak-tag'>{optional}</span></h3>"
        . "<div class='ak-row'>"
        . "<div class='ak-f'><label for='napx-user-$id'>{username}</label>"
        . "<input type='text' id='napx-user-$id' class='ak-in' value='$user' placeholder='{username}' autocomplete='off'>"
        . "<p class='ak-help'>{napx_user_help}</p></div>"
        . "<div class='ak-f'><label for='napx-pass-$id'>{password}</label>"
        . "<div class='napx-pw'><input type='password' id='napx-pass-$id' class='ak-in' value='' placeholder='$ph' autocomplete='new-password' aria-describedby='napx-pass-help-$id'>"
        . "<button type='button' class='ak-btn ak-btn-icon napx-eye' id='napx-eye-$id' aria-label='{napx_show_password}' aria-pressed='false' OnClick=\"NapxEye($id)\">"
        . "<span class='napx-eye-on'><i class='fas fa-eye' aria-hidden='true'></i></span><span class='napx-eye-off' hidden><i class='fas fa-eye-slash' aria-hidden='true'></i></span></button></div>"
        . "<p class='ak-help' id='napx-pass-help-$id'>$help</p></div>"
        . "</div></section>";
}

// Result banner, above the action bar. Filled by JS; after a save the content is
// reloaded with the URL the agent wrote, so the banner survives the reload.
function napx_banner(int $id, string $saved): string {
    $hidden = $saved === "" ? " hidden" : "";
    $msg = $saved === "" ? "" : "{napx_saved}: <span class='ak-mono'>" . napx_h($saved) . "</span>";
    return "<div class='ak-banner ak-ok napx-banner' id='napx-banner-$id' role='status' aria-live='polite'$hidden>"
        . "<span class='napx-bi-ok'><i class='fas fa-circle-check' aria-hidden='true'></i></span>"
        . "<span class='napx-bi-err' hidden><i class='fas fa-triangle-exclamation' aria-hidden='true'></i></span>"
        . "<span class='napx-bi-run' hidden><i class='fas fa-spinner fa-spin' aria-hidden='true'></i></span>"
        . "<span class='ak-banner-m' id='napx-banner-m-$id'>$msg</span>"
        . "<button type='button' class='ak-btn ak-btn-icon' aria-label='{napx_dismiss}' OnClick=\"document.getElementById('napx-banner-$id').hidden=true\"><i class='fas fa-xmark' aria-hidden='true'></i></button></div>";
}

function napx_actions(int $id, array $m): string {
    $page = CurrentPageName();
    $remove = "";
    if ($m["address"] !== "") {
        $remove = "<button type='button' class='ak-btn ak-btn-lg ak-btn-danger' OnClick=\"Loadjs('$page?delete-js=$id')\"><i class='fas fa-trash-can' aria-hidden='true'></i> {remove_proxy}</button>";
    }
    return "<div class='ak-actions'>$remove<span class='ak-grow'></span>"
        . "<button type='button' class='ak-btn ak-btn-lg ak-btn-primary' id='napx-save-$id' OnClick=\"NapxSave($id)\"><i class='fas fa-floppy-disk' aria-hidden='true'></i> {save}</button></div>";
}

// Seules les regles propres a l'onglet : le reste vient du kit ak- (angular.css.php).
function napx_style(): string {
    if (isset($GLOBALS["NAPX_STYLE"])) { return ""; }
    $GLOBALS["NAPX_STYLE"] = true;
    $c = array();
    $c[] = ".napx{padding:4px 2px 8px}.napx .ak-strip{margin-bottom:16px}.napx-slash{color:#94a3b8;font-weight:400;margin:0 4px}";
    $c[] = ".napx-f3{flex:3 1 0}.napx-f1{flex:0 0 230px}";
    $c[] = ".napx-pw{position:relative}.napx-pw .ak-in{padding-right:44px}.napx-eye{position:absolute;top:4px;right:4px}";
    $c[] = ".napx-banner{margin-top:20px}";
    $c[] = "@media (max-width:1000px){.napx-f1{flex:1 1 auto}}";
    return "<style>" . implode("\n", $c) . "</style>";
}

// Functions shared by every agent tab (defined once per window), plus the
// already-translated messages of this agent.
function napx_js(int $id, string $page): string {
    $tpl = new template_admin();
    $t = function ($k) use ($tpl) { return json_encode(html_entity_decode(strip_tags($tpl->_ENGINE_parse_body("{" . $k . "}")), ENT_QUOTES, "UTF-8")); };
    $msgs = "{saving:" . $t("napx_saving") . ",saved:" . $t("napx_saved") . ",show:" . $t("napx_show_password")
        . ",hide:" . $t("napx_hide_password") . ",bad:" . $t("napx_bad_reply") . "}";
    return <<<JS
<script>
window.NapxPage='$page';
window.NapxMsg=$msgs;
if(typeof window.NapxSave!=='function'){
window.NapxBanner=function(id,kind,html){
    var b=document.getElementById('napx-banner-'+id); if(!b){return;}
    b.className='ak-banner napx-banner ak-'+({ok:'ok',ko:'err',run:'info'}[kind]||'info');
    jQuery(b).find('.napx-bi-ok').prop('hidden',kind!=='ok');
    jQuery(b).find('.napx-bi-err').prop('hidden',kind!=='ko');
    jQuery(b).find('.napx-bi-run').prop('hidden',kind!=='run');
    document.getElementById('napx-banner-m-'+id).innerHTML=html;
    b.hidden=false;
};
window.NapxEsc=function(s){ return jQuery('<div>').text(String(s)).html(); };
window.NapxEye=function(id){
    var i=document.getElementById('napx-pass-'+id), b=document.getElementById('napx-eye-'+id);
    var show=(i.type==='password'); i.type=show?'text':'password';
    b.setAttribute('aria-pressed',show?'true':'false');
    b.setAttribute('aria-label',show?NapxMsg.hide:NapxMsg.show);
    jQuery(b).find('.napx-eye-on').prop('hidden',show); jQuery(b).find('.napx-eye-off').prop('hidden',!show);
};
window.NapxSave=function(id){
    var a=document.getElementById('napx-addr-'+id), p=document.getElementById('napx-port-'+id);
    var addr=jQuery.trim(a.value), port=parseInt(p.value,10), ok=true;
    var badA=(addr===''||/\s/.test(addr)), badP=(isNaN(port)||port<1||port>65535);
    jQuery(a).toggleClass('ak-bad',badA); document.getElementById('napx-addr-err-'+id).hidden=!badA;
    jQuery(p).toggleClass('ak-bad',badP); document.getElementById('napx-port-err-'+id).hidden=!badP;
    if(badA){a.focus();return;} if(badP){p.focus();return;}
    var s=document.getElementById('napx-save-'+id); s.disabled=true;
    NapxBanner(id,'run',NapxEsc(NapxMsg.saving));
    jQuery.post(NapxPage,{'napx-save':id,proxy_address:addr,proxy_port:port,
        username:jQuery.trim(document.getElementById('napx-user-'+id).value),
        password:document.getElementById('napx-pass-'+id).value},function(r){
        s.disabled=false;
        var j=null; try{ j=JSON.parse(r); }catch(e){ j=null; }
        if(!j){ NapxBanner(id,'ko',NapxEsc(NapxMsg.bad)); return; }
        if(!j.ok){ NapxBanner(id,'ko',NapxEsc(j.error||NapxMsg.bad)); return; }
        LoadAjaxSilent('napx-host-'+id,NapxPage+'?inner='+id+'&saved='+encodeURIComponent(j.b64||''));
    }).fail(function(x){ s.disabled=false; NapxBanner(id,'ko',NapxEsc('HTTP '+x.status)); });
};
}
</script>
JS;
}

// ── Actions ──────────────────────────────────────────────────────────────────

function napx_dialog_js(): bool {
    $tpl = new template_admin();
    $page = CurrentPageName();
    $id = intval($_GET["proxy-js"]);
    return $tpl->js_dialog4("{napx_title}", "$page?id=$id", 1000);
}

function napx_delete_js(): bool {
    $tpl = new template_admin();
    $page = CurrentPageName();
    $id = intval($_GET["delete-js"]);
    return $tpl->js_confirm_delete("<strong>{remove_proxy}</strong><br>{napx_remove_confirm}", "napx-delete", $id,
        "LoadAjaxSilent('napx-host-$id','$page?inner=$id');", true);
}

// JSON answer: the save is driven by NapxSave(), which shows the result inline.
function napx_save(): bool {
    $tpl = new template_admin();
    header("Content-Type: application/json");
    $id = intval($_POST["napx-save"]);
    $out = function ($ok, $error = "", $url = "") {
        echo json_encode(array("ok" => $ok, "error" => $error, "b64" => $url === "" ? "" : base64_encode($url)));
        return $ok;
    };
    $tr = function ($k) use ($tpl) { return html_entity_decode(strip_tags($tpl->_ENGINE_parse_body("{" . $k . "}")), ENT_QUOTES, "UTF-8"); };
    if (!napx_is_admin()) { return $out(false, $tr("ERROR_NO_PRIVS")); }
    if ($id <= 0) { return $out(false, "invalid agent"); }
    $address = trim((string)($_POST["proxy_address"] ?? ""));
    $port = intval($_POST["proxy_port"] ?? 0);
    $user = trim(str_replace(array("\r", "\n"), "", (string)($_POST["username"] ?? "")));
    $pass = str_replace(array("\r", "\n"), "", (string)($_POST["password"] ?? ""));
    if ($address === "" || preg_match('/[\s\'"<>\\\\]/', $address)) { return $out(false, $tr("napx_addr_required")); }
    if ($port < 1 || $port > 65535) { return $out(false, $tr("napx_port_invalid")); }

    // Empty password = keep the stored one, as long as the account stays the same.
    $err = "";
    $cur = napx_read($id, $err);
    if ($err !== "") { return $out(false, $err); }
    if ($user === "") {
        $pass = "";
    } elseif ($pass === "" && $user === $cur["user"]) {
        $pass = $cur["pass"];
    }

    $raw = $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/proxy-config/$id",
        array("proxy_address" => $address, "proxy_port" => $port, "username" => $user, "password" => $pass));
    $json = json_decode((string)$raw, true);
    if (!is_array($json)) { return $out(false, $tr("napx_bad_reply")); }
    if (isset($json["Status"]) && !$json["Status"]) { return $out(false, (string)($json["Error"] ?? $tr("error"))); }
    // The agent answers {success, proxy_url, error}: a refusal is not a success.
    if (isset($json["success"]) && !$json["success"]) {
        $e = trim((string)($json["error"] ?? ""));
        return $out(false, $tr("napx_agent_refused") . ($e !== "" ? ": $e" : ""));
    }
    admin_tracks("Netagent #$id: system proxy set to $address:$port" . ($user !== "" ? " (user $user)" : ""));
    return $out(true, "", napx_url($address, $port, $user));
}

function napx_delete(): bool {
    $tpl = new template_admin();
    $id = intval($_POST["napx-delete"]);
    if (!napx_is_admin()) { echo $tpl->post_error("{ERROR_NO_PRIVS}"); return false; }
    if ($id <= 0) { echo $tpl->post_error("invalid agent"); return false; }
    $json = json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/netagents/proxy-config/$id"), true);
    if (!is_array($json)) { echo $tpl->post_error("{napx_bad_reply}"); return false; }
    if (isset($json["Status"]) && !$json["Status"]) { echo $tpl->post_error((string)($json["Error"] ?? "{error}")); return false; }
    admin_tracks("Netagent #$id: system proxy removed");
    return true;
}
