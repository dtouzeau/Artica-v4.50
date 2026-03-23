<?php
/**
 * Unbound DNS Manager for a single remote agent.
 * Main tab shell + install/uninstall/restart lifecycle.
 *
 * Entry:  Loadjs('fw.netagents.unbound.php?unbound-js={agent_id}')  — opens dialog
 *         LoadAjax('div','fw.netagents.unbound.php?id={agent_id}')   — inline
 * API:    GET  /netagents/unbound/health/{id}
 *         POST /netagents/unbound/install/{id}
 *         GET  /netagents/unbound/install/status/{id}
 *         POST /netagents/unbound/uninstall/{id}
 *         GET  /netagents/unbound/uninstall/status/{id}
 *         POST /netagents/unbound/restart/{id}
 *         GET  /netagents/unbound/restart/status/{id}
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }
$users = new usersMenus();
if (!$users->AsSystemAdministrator) { exit(); }

if (isset($_GET["unbound-js"]))      { unbound_js();             exit; }
if (isset($_GET["health-badge"]))    { unbound_health_badge();   exit; }
if (isset($_GET["health"]))          { unbound_health_tab();     exit; }
if (isset($_GET["install-status"]))  { unbound_install_status(); exit; }
if (isset($_POST["install"]))        { unbound_install();        exit; }
if (isset($_POST["uninstall"]))      { unbound_uninstall();      exit; }
if (isset($_POST["restart"]))        { unbound_restart();        exit; }
unbound_form();

// ── Helpers ──────────────────────────────────────────────────────────────────

function aid(): int {
    if(isset($_GET["health"])){
        $id=intval($_GET["health"]);
        if($id>0){
            return $id;
        }
    }
    return intval($_GET["id"] ?? $_POST["id"] ?? 0);
}

// ── Dispatcher actions ───────────────────────────────────────────────────────

/** Opens js_dialog6 (970 px) with the agent hostname in the title. */
function unbound_js(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["unbound-js"]);

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/get/$id"));
    $hostname = (is_object($json) && !empty($json->hostname))
        ? htmlspecialchars($json->hostname) : "Agent #$id";

    $tpl->js_dialog6("{APP_UNBOUND} — $hostname", "$page?id=$id", 970);
}

/** Main form: health check → install panel or tab shell. */
function unbound_form(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = aid();

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/unbound/health/$id"));
    if (!is_object($json) || (isset($json->Status) && !$json->Status)) {
        $err = is_object($json) ? htmlspecialchars($json->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }

    echo "<div id='unbound-main-$id'>";

    $installed = !empty($json->installed);

    if (!$installed) {
        unbound_install_panel($id, $tpl, $page);
        echo "</div>";
        return;
    }

    // ── Tab shell ──
    $tabs = [];
    $tabs["{status}"]          = "$page?health=$id&id=$id";
    $tabs["{dns_records}"]     = "fw.netagents.unbound.records.php?id=$id";
    $tabs["{local_domains}"]   = "fw.netagents.unbound.domains.php?id=$id";
    $tabs["{forward_zones}"]   = "fw.netagents.unbound.forward.php?id=$id";
    $tabs["{access_control}"]  = "fw.netagents.unbound.acl.php?id=$id";
    $tabs["{configuration}"]   = "fw.netagents.unbound.config.php?id=$id";

    echo $tpl->tabs_default($tabs);
    echo "</div>";
}

/** Install panel shown when Unbound is not installed. */
function unbound_install_panel(int $id, $tpl, string $page): void {
    $h = [];
    $h[] = "<div id='unbound-install-$id'>";
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-download'></i>&nbsp; {unbound_not_installed}</h5></div>";
    $h[] = "  <div class='ibox-content'>";
    $h[] = "    <p>{unbound_not_installed}</p>";
    $h[] = "    <button class='btn btn-primary btn-lg' onclick='UnboundInstall_$id()'>";
    $h[] = "      <i class='fas fa-download'></i> {unbound_install}";
    $h[] = "    </button>";
    $h[] = "    <div id='unbound-install-result-$id' style='margin-top:15px'></div>";
    $h[] = "  </div>";
    $h[] = "</div>";
    $h[] = "</div>";
    $h[] = unbound_js_block($id, $page);
    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

/** Status tab content: running badge, PID, uptime, restart/uninstall buttons. */
function unbound_health_tab(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = aid();

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/unbound/health/$id"));
    if (!is_object($json) || (isset($json->Status) && !$json->Status)) {
        $err = is_object($json) ? htmlspecialchars($json->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err."<br>ID:$id/".__LINE__));
        return;
    }

    $running = !empty($json->running);
    $pid     = intval($json->pid ?? 0);
    $uptime  = htmlspecialchars($json->uptime ?? '');

    if ($running) {
        $badge = "<span class='badge' style='background:#1ab394;color:#fff;font-size:13px;padding:5px 12px'>"
               . "<i class='fas fa-check-circle'></i> {running}</span>";
    } else {
        $badge = "<span class='badge' style='background:#ed5565;color:#fff;font-size:13px;padding:5px 12px'>"
               . "<i class='fas fa-times-circle'></i> {stopped}</span>";
    }

    $h = [];
    $h[] = "<div id='unbound-health-$id'>";
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-heartbeat'></i>&nbsp; {status}</h5></div>";
    $h[] = "  <div class='ibox-content'>";
    $h[] = "    <div style='margin-bottom:15px'>$badge</div>";

    if ($running) {
        $tpl->table_form_field_text("PID", "$pid", "fas fa-microchip");
        $tpl->table_form_field_text("{uptime}", $uptime, "fas fa-clock");
        $h[] = $tpl->table_form_compile();
    }

    $h[] = "    <div style='margin-top:20px'>";
    $h[] = "      <button class='btn btn-primary' onclick='UnboundRestart_$id()'>";
    $h[] = "        <i class='fas fa-sync-alt'></i> {restart}";
    $h[] = "      </button>";
    $h[] = "      <button class='btn btn-danger' style='margin-left:10px' onclick='UnboundUninstall_$id()'>";
    $h[] = "        <i class='fas fa-trash'></i> {uninstall}";
    $h[] = "      </button>";
    $h[] = "    </div>";
    $h[] = "    <div id='unbound-action-result-$id' style='margin-top:15px'></div>";
    $h[] = "  </div>";
    $h[] = "</div>";
    $h[] = "</div>";
    $h[] = unbound_js_block($id, $page);

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

/** Single badge for polling. */
function unbound_health_badge(): void {
    $tpl = new template_admin();
    $id  = aid();

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/unbound/health/$id"));
    if (!is_object($json) || (isset($json->Status) && !$json->Status)) {
        echo $tpl->_ENGINE_parse_body("<span class='badge' style='background:#676a6c;color:#fff'>?</span>");
        return;
    }
    if (!empty($json->running)) {
        echo $tpl->_ENGINE_parse_body("<span class='badge' style='background:#1ab394;color:#fff'>{unbound_running}</span>");
    } else {
        echo $tpl->_ENGINE_parse_body("<span class='badge' style='background:#ed5565;color:#fff'>{unbound_stopped}</span>");
    }
}

/** Triggers install, returns "INSTALLING" to start client-side polling. */
function unbound_install(): void {
    $tpl = new template_admin();
    $id  = aid();

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/unbound/install/$id", new stdClass()
    ));
    if (!is_object($json) || (isset($json->Status) && !$json->Status)) {
        $err = is_object($json) ? htmlspecialchars($json->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }
    echo "INSTALLING";
    admin_tracks("Netagent #$id: Unbound install triggered");
}

/** Polled during install/uninstall. Returns JSON from the status endpoint. */
function unbound_install_status(): void {
    header("content-type: application/json");
    $id   = aid();
    $type = trim($_GET["install-status"]);

    if ($type === "uninstall") {
        $ep = "/netagents/unbound/uninstall/status/$id";
    } elseif ($type === "restart") {
        $ep = "/netagents/unbound/restart/status/$id";
    } else {
        $ep = "/netagents/unbound/install/status/$id";
    }

    echo $GLOBALS["CLASS_SOCKETS"]->REST_API($ep);
}

/** Triggers uninstall. */
function unbound_uninstall(): void {
    $tpl = new template_admin();
    $id  = aid();

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/unbound/uninstall/$id", new stdClass()
    ));
    if (!is_object($json) || (isset($json->Status) && !$json->Status)) {
        $err = is_object($json) ? htmlspecialchars($json->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }
    echo "UNINSTALLING";
    admin_tracks("Netagent #$id: Unbound uninstall triggered");
}

/** Triggers restart. */
function unbound_restart(): void {
    $tpl = new template_admin();
    $id  = aid();

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/unbound/restart/$id", new stdClass()
    ));
    if (!is_object($json) || (isset($json->Status) && !$json->Status)) {
        $err = is_object($json) ? htmlspecialchars($json->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }
    echo "RESTARTING";
    admin_tracks("Netagent #$id: Unbound restart triggered");
}

// ── JS block ─────────────────────────────────────────────────────────────────

function unbound_js_block(int $id, string $page): string {
    $l = [];
    $l[] = "<script>";

    // Install
    $l[] = "function UnboundInstall_$id(){";
    $l[] = "  \$('#unbound-install-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {unbound_installing}</div>');";
    $l[] = "  \$.post('$page',{install:1,id:$id},function(r){";
    $l[] = "    if(r!=='INSTALLING'){ \$('#unbound-install-result-$id').html(r); return; }";
    $l[] = "    var poll=setInterval(function(){";
    $l[] = "      \$.getJSON('$page',{'install-status':'install',id:$id},function(d){";
    $l[] = "        if(!d.done) return;";
    $l[] = "        clearInterval(poll);";
    $l[] = "        if(d.ok){ LoadAjax('unbound-main-$id','$page?id=$id'); }";
    $l[] = "        else{";
    $l[] = "          var msg=(d.logs&&d.logs.length)?d.logs[d.logs.length-1]:'{error}';";
    $l[] = "          \$('#unbound-install-result-$id').html('<div class=\"alert alert-danger\">'+msg+'</div>');";
    $l[] = "        }";
    $l[] = "      });";
    $l[] = "    },2000);";
    $l[] = "  });";
    $l[] = "}";

    // Uninstall (with swal confirm)
    $l[] = "function UnboundUninstall_$id(){";
    $l[] = "  swal({title:'{confirm_uninstall_unbound}',type:'warning',showCancelButton:true,";
    $l[] = "    confirmButtonColor:'#ed5565',confirmButtonText:'{yes}',cancelButtonText:'{cancel}'},";
    $l[] = "  function(ok){ if(!ok) return;";
    $l[] = "    \$('#unbound-action-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {unbound_uninstalling}</div>');";
    $l[] = "    \$.post('$page',{uninstall:1,id:$id},function(r){";
    $l[] = "      if(r!=='UNINSTALLING'){ \$('#unbound-action-result-$id').html(r); return; }";
    $l[] = "      var poll=setInterval(function(){";
    $l[] = "        \$.getJSON('$page',{'install-status':'uninstall',id:$id},function(d){";
    $l[] = "          if(!d.done) return;";
    $l[] = "          clearInterval(poll);";
    $l[] = "          if(d.ok){ LoadAjax('unbound-main-$id','$page?id=$id'); }";
    $l[] = "          else{";
    $l[] = "            var msg=(d.logs&&d.logs.length)?d.logs[d.logs.length-1]:'{error}';";
    $l[] = "            \$('#unbound-action-result-$id').html('<div class=\"alert alert-danger\">'+msg+'</div>');";
    $l[] = "          }";
    $l[] = "        });";
    $l[] = "      },2000);";
    $l[] = "    });";
    $l[] = "  });";
    $l[] = "}";

    // Restart
    $l[] = "function UnboundRestart_$id(){";
    $l[] = "  \$('#unbound-action-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {unbound_restarting}</div>');";
    $l[] = "  \$.post('$page',{restart:1,id:$id},function(r){";
    $l[] = "    if(r!=='RESTARTING'){ \$('#unbound-action-result-$id').html(r); return; }";
    $l[] = "    var poll=setInterval(function(){";
    $l[] = "      \$.getJSON('$page',{'install-status':'restart',id:$id},function(d){";
    $l[] = "        if(!d.done) return;";
    $l[] = "        clearInterval(poll);";
    $l[] = "        if(d.ok){";
    $l[] = "          \$('#unbound-action-result-$id').html('<div class=\"alert alert-success\"><i class=\"fas fa-check-circle\"></i> {unbound_restarted}</div>');";
    $l[] = "          LoadAjaxSilent('unbound-health-$id','$page?health=$id');";
    $l[] = "        } else {";
    $l[] = "          var msg=(d.logs&&d.logs.length)?d.logs[d.logs.length-1]:'{error}';";
    $l[] = "          \$('#unbound-action-result-$id').html('<div class=\"alert alert-danger\">'+msg+'</div>');";
    $l[] = "        }";
    $l[] = "      });";
    $l[] = "    },2000);";
    $l[] = "  });";
    $l[] = "}";

    $l[] = "</script>";
    return implode("\n", $l);
}
