<?php
/**
 * System Internet Proxy configuration for a single remote agent.
 * Writes http_proxy / https_proxy to /etc/environment and /etc/apt/apt.conf.d/99proxy.
 *
 * Entry:  Loadjs('fw.netagents.proxy.php?proxy-js={agent_id}')    — opens dialog
 *         LoadAjax('div','fw.netagents.proxy.php?id={agent_id}')  — inline form
 * API:    GET    /netagents/proxy-config/{id}
 *         POST   /netagents/proxy-config/{id}
 *         DELETE /netagents/proxy-config/{id}
 *         Body:  { proxy_address, proxy_port, username, password }
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }

if (isset($_GET["proxy-js"])) { proxy_js();     exit; }
if (isset($_POST["save"]))    { proxy_save();   exit; }
if (isset($_POST["delete"]))  { proxy_delete(); exit; }
proxy_form();

// ── Helpers ──────────────────────────────────────────────────────────────────

function aid(): int {
    return intval($_GET["id"] ?? $_POST["id"] ?? 0);
}

// ── Dispatcher actions ────────────────────────────────────────────────────────

/** Opens a 750px dialog containing the proxy config form. */
function proxy_js(): bool {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["proxy-js"]);
    return $tpl->js_dialog4("{system_proxy_settings}", "$page?id=$id", 750);
}

/** Renders the proxy form pre-filled with current agent values. */
function proxy_form(): bool {
    $tpl  = new template_admin();
    $id   = aid();
    $page = CurrentPageName();

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/proxy-config/$id"));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error} (not an object)"));
        return false;
    }
    if (isset($json->Status) && !$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->Error ?? "{error}")));
        return false;
    }

    // outJSON returns the system_proxy object directly (or empty fields when unconfigured)
    $address  = htmlspecialchars($json->proxy_address ?? '');
    $port     = intval($json->proxy_port ?? 0) ?: '';
    $user     = htmlspecialchars($json->username     ?? '');
    $pass     = htmlspecialchars($json->password     ?? '');
    $hasProxy = ($address !== '');

    $h   = [];
    $h[] = "<div id='proxy-result-$id' style='margin-top:10px'></div>";

    if (!$hasProxy) {
        $h[] = "<div class='alert alert-info' style='margin-bottom:15px'>";
        $h[] = "  <i class='fas fa-info-circle'></i> {no_proxy_configured}";
        $h[] = "</div>";
    }

    $h[] = proxy_address_box($id, $address, $port);
    $h[] = proxy_auth_box($id, $user, $pass);

    // Action buttons row
    $h[] = "<div class='row' style='margin-bottom:20px'>";
    $h[] = "  <div class='col-md-6'>";
    if ($hasProxy) {
        $h[] = "    <button class='btn btn-danger' onclick='ProxyDelete_$id()'>";
        $h[] = "      <i class='fas fa-trash-alt'></i> {remove_proxy}";
        $h[] = "    </button>";
    }
    $h[] = "  </div>";
    $h[] = "  <div class='col-md-6 text-right'>";
    $h[] = "    <button class='btn btn-primary btn-lg' onclick='ProxySave_$id()'>";
    $h[] = "      <i class='fas fa-save'></i> {save}";
    $h[] = "    </button>";
    $h[] = "  </div>";
    $h[] = "</div>";
    $h[] = proxy_js_block($id, $page, $hasProxy);

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    return true;
}

/** POSTs proxy config to the Go handler. Returns an HTML snippet. */
function proxy_save(): void {
    $tpl  = new template_admin();
    $id   = aid();

    $address = trim($_POST["proxy_address"] ?? '');
    $port    = intval($_POST["proxy_port"]   ?? 0);
    $user    = trim($_POST["username"] ?? '');
    $pass    = trim($_POST["password"] ?? '');

    if ($address === '') {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{proxy_address_required}"));
        return;
    }
    if ($port < 1 || $port > 65535) {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{invalid_port_range}"));
        return;
    }

    $data = [
        "proxy_address" => $address,
        "proxy_port"    => $port,
        "username"      => $user,
        "password"      => $pass,
    ];

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/proxy-config/$id", $data
    ));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    if (isset($json->Status) && !$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->Error ?? "{error}")));
        return;
    }
    echo $tpl->_ENGINE_parse_body(
        $tpl->div_success("<i class='fas fa-check-circle'></i> {success}")
    );
    admin_tracks("Netagent #$id: system proxy configured to $address:$port");
}

/** Sends DELETE to remove proxy config from the agent. Returns an HTML snippet. */
function proxy_delete(): void {
    $tpl = new template_admin();
    $id  = aid();

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE(
        "/netagents/proxy-config/$id"
    ));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    if (isset($json->Status) && !$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->Error ?? "{error}")));
        return;
    }
    echo $tpl->_ENGINE_parse_body(
        $tpl->div_success("<i class='fas fa-check-circle'></i> {proxy_removed}")
    );
    admin_tracks("Netagent #$id: system proxy removed");
}

// ── UI box builders ───────────────────────────────────────────────────────────

function proxy_address_box(int $id, string $address, $port): string {
    $h   = [];
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-network-wired'></i>&nbsp; {proxy_server}</h5></div>";
    $h[] = "  <div class='ibox-content'><div class='row'>";
    $h[] = "    <div class='col-md-8'>";
    $h[] = "      <label>{proxy_address}</label>";
    $h[] = "      <input type='text' id='proxy-addr-$id' class='form-control' value='$address' placeholder='192.168.1.100'>";
    $h[] = "      <p class='help-block'>{proxy_address_explain}</p>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-4'>";
    $h[] = "      <label>{proxy_port}</label>";
    $h[] = "      <input type='number' id='proxy-port-$id' class='form-control' value='$port' min='1' max='65535' placeholder='3128'>";
    $h[] = "      <p class='help-block'>{proxy_port_explain}</p>";
    $h[] = "    </div>";
    $h[] = "  </div></div></div>";
    return implode("\n", $h);
}

function proxy_auth_box(int $id, string $user, string $pass): string {
    $h   = [];
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-key'></i>&nbsp; {authentication} <small class='text-muted'>&nbsp;{optional}</small></h5></div>";
    $h[] = "  <div class='ibox-content'><div class='row'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{username}</label>";
    $h[] = "      <input type='text' id='proxy-user-$id' class='form-control' value='$user' placeholder='{username}'>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{password}</label>";
    $h[] = "      <div class='input-group'>";
    $h[] = "        <input type='password' id='proxy-pass-$id' class='form-control' value='$pass' placeholder='{password}'>";
    $h[] = "        <span class='input-group-btn'>";
    $h[] = "          <button type='button' class='btn btn-default'";
    $h[] = "            onclick=\"var e=document.getElementById('proxy-pass-$id');e.type=e.type==='password'?'text':'password'\">";
    $h[] = "            <i class='fas fa-eye'></i>";
    $h[] = "          </button>";
    $h[] = "        </span>";
    $h[] = "      </div>";
    $h[] = "    </div>";
    $h[] = "  </div></div></div>";
    return implode("\n", $h);
}

/** Emits ProxySave_{id}() and (when $hasProxy) ProxyDelete_{id}() JavaScript functions. */
function proxy_js_block(int $id, string $page, bool $hasProxy): string {
    $l   = [];
    $l[] = "<script>";

    $l[] = "function ProxySave_$id(){";
    $l[] = "  var addr=\$.trim(\$('#proxy-addr-$id').val());";
    $l[] = "  var port=parseInt(\$('#proxy-port-$id').val(),10);";
    $l[] = "  if(!addr){ alert('{proxy_address_required}'); return; }";
    $l[] = "  if(isNaN(port)||port<1||port>65535){ alert('{invalid_port_range}'); return; }";
    $l[] = "  \$('#proxy-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i></div>');";
    $l[] = "  \$.post('$page',{";
    $l[] = "    save:1, id:$id,";
    $l[] = "    proxy_address:addr,";
    $l[] = "    proxy_port:port,";
    $l[] = "    username:\$.trim(\$('#proxy-user-$id').val()),";
    $l[] = "    password:\$('#proxy-pass-$id').val()";
    $l[] = "  },function(r){ \$('#proxy-result-$id').html(r); });";
    $l[] = "}";

    if ($hasProxy) {
        $l[] = "function ProxyDelete_$id(){";
        $l[] = "  swal({";
        $l[] = "    title:'{remove_proxy}',";
        $l[] = "    text:'{confirm_remove_proxy}',";
        $l[] = "    type:'warning',";
        $l[] = "    showCancelButton:true,";
        $l[] = "    confirmButtonColor:'#ed5565',";
        $l[] = "    confirmButtonText:'{remove}',";
        $l[] = "    cancelButtonText:'{cancel}'";
        $l[] = "  },function(isConfirm){";
        $l[] = "    if(!isConfirm) return;";
        $l[] = "    \$('#proxy-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i></div>');";
        $l[] = "    \$.post('$page',{delete:1,id:$id},function(r){ \$('#proxy-result-$id').html(r); });";
        $l[] = "  });";
        $l[] = "}";
    }

    $l[] = "</script>";
    return implode("\n", $l);
}
