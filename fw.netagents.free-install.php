<?php
/**
 * FreeSetup package installer popup.
 *
 * Entry (Loadjs):  fw.netagents.free-install.php?js=yes&product=APP_DHCP&debver=12
 * Popup content:   fw.netagents.free-install.php?popup={agent_id}&product=APP_DHCP&debver=12
 * Push trigger:    POST fw.netagents.free-install.php  (push=1, agent_id, local_path, filename, product)
 * Status poll:     GET  fw.netagents.free-install.php?push_status={op_id}&agent_id={id}
 *
 * API used:
 *   GET  /netagents/freepackage/{id}/list?product=...&debver=...
 *   POST /netagents/freepackage/{id}/push  (local_path, filename, product)
 *   GET  /netagents/freepackage/push/status/{op_id}
 */
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"] = new sockets();
}

if (isset($_GET["popup"]))      { popup();         exit; }
if (isset($_POST["push"]))      { push_handler();  exit; }
if (isset($_GET["push_status"])) { push_status();  exit; }
if (isset($_POST["hostname"]))  { save();          exit; }
if (isset($_GET["syslog-point"])){ syslog_point(); exit; }
if (isset($_GET["syslog-search"])){ syslog_search(); exit; }
if (isset($_GET["search"]))     { search();        exit; }
js();

// ── Dialog opener ──────────────────────────────────────────────────────────────

// fw.netagents.free-install.php?js=yes&product=APP_DHCP&debver=12
function js(): bool {
    $tpl     = new template_admin();
    $page    = CurrentPageName();
    $id      = intval($_GET["js"]);
    $product = preg_replace('/[^A-Za-z0-9_]/', '', $_GET["product"] ?? '');
    $debver  = intval($_GET["debver"] ?? 0);
    return $tpl->js_dialog11(
        "{{$product}}",
        "$page?popup=$id&product=$product&debver=$debver",
        700
    );
}

// ── Popup content ──────────────────────────────────────────────────────────────

function popup(): bool {
    $tpl     = new template_admin();
    $page    = CurrentPageName();
    $id      = intval($_GET["popup"]);
    $product = preg_replace('/[^A-Za-z0-9_]/', '', $_GET["product"] ?? '');
    $debver  = intval($_GET["debver"] ?? 0);

    if (!$id || !$product || !$debver) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return true;
    }

    // Fetch available packages from local repository
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/netagents/freepackage/$id/list?product=" . urlencode($product) . "&debver=$debver"
    ));

    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return true;
    }
    if (isset($json->Status) && !$json->Status) {
        $err = htmlspecialchars($json->Error ?? "{error}");
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return true;
    }

    $packages = $json->packages ?? [];

    $h   = [];
    $h[] = "<div id='fpush-result-$id' style='margin-bottom:10px'></div>";

    if (empty($packages)) {
        $h[] = $tpl->div_warning("{no_packages_available}");
        echo $tpl->_ENGINE_parse_body(implode("\n", $h));
        return true;
    }

    $count = count($packages);
    $h[] = "<div class='alert alert-info' style='margin-bottom:12px'>";
    $h[] = "  <i class='fas fa-box-open'></i> <strong>$product</strong>";
    $h[] = "  &nbsp;<span class='badge' style='background:#1c84c6;color:#fff'>$count</span> {packages}";
    $h[] = "  &nbsp;&mdash; <small>Debian $debver</small>";
    $h[] = "</div>";

    $h[] = "<table class='table table-striped table-condensed'>";
    $h[] = "  <thead><tr style='background:#f5f5f5'>";
    $h[] = "    <th>{filename}</th>";
    $h[] = "    <th style='width:90px'>{version}</th>";
    $h[] = "    <th style='width:80px'>{size}</th>";
    $h[] = "    <th style='width:80px'></th>";
    $h[] = "  </tr></thead><tbody>";

    foreach ($packages as $pkg) {
        if (!is_object($pkg)) continue;
        $fname     = htmlspecialchars($pkg->filename  ?? '');
        $version   = htmlspecialchars($pkg->version   ?? '');
        $localPath = $pkg->local_path ?? '';
        $size      = intval($pkg->size ?? 0);
        $sizeHuman = FormatBytes(intval($size / 1024), true);

        // htmlspecialchars(json_encode()) produces &quot;value&quot; which is safe
        // inside a double-quoted HTML attribute — the browser restores the real
        // double-quotes before executing the onclick handler. Without this,
        // json_encode's literal " chars would close the onclick="..." attribute
        // prematurely, causing SyntaxError: Unexpected end of input.
        $fnameAttr = htmlspecialchars(json_encode($fname),     ENT_QUOTES);
        $lpathAttr = htmlspecialchars(json_encode($localPath), ENT_QUOTES);
        $h[] = "    <tr>";
        $h[] = "      <td><span style='font-family:monospace;font-size:12px'>$fname</span></td>";
        $h[] = "      <td>$version</td>";
        $h[] = "      <td><small>$sizeHuman</small></td>";
        $h[] = "      <td><button class='btn btn-xs btn-primary' onclick=\"FPushInstall_$id($fnameAttr,$lpathAttr)\">";
        $h[] = "        <i class='fas fa-download'></i> {install}";
        $h[] = "      </button></td>";
        $h[] = "    </tr>";
    }

    $h[] = "  </tbody></table>";
    $h[] = popup_js($id, $page, $product);

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    return true;
}

// ── JavaScript ─────────────────────────────────────────────────────────────────

function popup_js(int $id, string $page, string $product): string {
    $productJs = json_encode($product);
    $l   = [];
    $l[] = "<script>";
    $tpl=new template_admin();
    $installing=$tpl->_ENGINE_parse_body("{installing}");
    $errort=$tpl->_ENGINE_parse_body("{error}");
    $confirm_install_package=$tpl->javascript_parse_text("{confirm_install_package}");
    // FPushInstall_{id}(fname, lpath) — swal confirmation, then async push
    $l[] = "function FPushInstall_$id(fname, lpath){";
    $l[] = "  swal({";
    $l[] = "    title:'$confirm_install_package',";
    $l[] = "    text:fname,";
    $l[] = "    type:'warning',";
    $l[] = "    showCancelButton:true,";
    $l[] = "    confirmButtonColor:'#1c84c6',";
    $l[] = "    confirmButtonText:'{install}',";
    $l[] = "    cancelButtonText:'{cancel}'";
    $l[] = "  },function(isConfirm){";
    $l[] = "    if(!isConfirm) return;";
    $l[] = "    var res=\$('#fpush-result-$id');";
    $l[] = "    res.html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> $installing...</div>');";
    $l[] = "    \$.post('$page',{push:1,agent_id:$id,filename:fname,local_path:lpath,product:$productJs},function(r){";
    $l[] = "      if(!r||!r.op_id){";
    $l[] = "        res.html('<div class=\"alert alert-danger\"><i class=\"fas fa-times-circle\"></i> $errort</div>');";
    $l[] = "        return;";
    $l[] = "      }";
    $l[] = "      FPushPoll_$id(r.op_id);";
    $l[] = "    },'json').fail(function(){";
    $l[] = "      res.html('<div class=\"alert alert-danger\"><i class=\"fas fa-times-circle\"></i> $errort</div>');";
    $l[] = "    });";
    $l[] = "  });";
    $l[] = "}";
    $success=$tpl->_ENGINE_parse_body("{success}");
    $failed=$tpl->_ENGINE_parse_body("{failed}");
    $files_extracted=$tpl->_ENGINE_parse_body("{files_extracted}");
    // FPushPoll_{id}(opid) — polls status every 2 s until completed/failed
    $l[] = "function FPushPoll_$id(opid){";
    $l[] = "  \$.get('$page',{push_status:opid,agent_id:$id},function(r){";
    $l[] = "    if(!r){setTimeout(function(){FPushPoll_$id(opid);},2000);return;}";
    $l[] = "    var res=\$('#fpush-result-$id');";
    $l[] = "    if(r.status==='completed'){";
    $l[] = "      res.html('<div class=\"alert alert-success\"><i class=\"fas fa-check-circle\"></i> $success &mdash; '+r.extracted+' $files_extracted</div>');";
    $l[] = "    } else if(r.status==='failed'){";
    $l[] = "      var msg=r.error?(' &mdash; <small>'+\$('<span>').text(r.error).html()+'</small>'):'';";
    $l[] = "      res.html('<div class=\"alert alert-danger\"><i class=\"fas fa-times-circle\"></i> $failed'+msg+'</div>');";
    $l[] = "    } else {";
    $l[] = "      setTimeout(function(){FPushPoll_$id(opid);},2000);";
    $l[] = "    }";
    $l[] = "  },'json').fail(function(){setTimeout(function(){FPushPoll_$id(opid);},3000);});";
    $l[] = "}";

    $l[] = "</script>";

    return $tpl->_ENGINE_parse_body( $l);
}

// ── POST: start non-blocking push ─────────────────────────────────────────────

function push_handler(): void {
    header('Content-Type: application/json');

    $agentId   = intval($_POST["agent_id"]   ?? 0);
    $localPath = trim($_POST["local_path"]   ?? '');
    $filename  = trim($_POST["filename"]     ?? '');
    $product   = preg_replace('/[^A-Za-z0-9_]/', '', $_POST["product"] ?? '');

    if (!$agentId || !$localPath) {
        echo json_encode(["Status" => false, "Error" => "Invalid parameters"]);
        return;
    }

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST(
        "/netagents/freepackage/$agentId/push",
        ["local_path" => $localPath, "filename" => $filename, "product" => $product]
    ));

    if (!is_object($json)) {
        echo json_encode(["Status" => false, "Error" => "Connection error"]);
        return;
    }
    if (isset($json->Status) && !$json->Status) {
        echo json_encode(["Status" => false, "Error" => htmlspecialchars($json->Error ?? "{error}")]);
        return;
    }

    $opId = $json->op_id ?? '';
    echo json_encode(["Status" => true, "op_id" => $opId]);
    admin_tracks("Agent #$agentId: FreePackage push started — $filename (op: $opId)");
}

// ── GET: poll push operation status ──────────────────────────────────────────

function push_status(): void {
    header('Content-Type: application/json');

    $opId = trim($_GET["push_status"] ?? '');
    if (!$opId || !preg_match('/^fpush-[0-9a-f]+$/', $opId)) {
        echo json_encode(["status" => "failed", "error" => "Invalid operation ID"]);
        return;
    }

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/netagents/freepackage/push/status/" . urlencode($opId)
    ));

    if (!is_object($json)) {
        echo json_encode(["status" => "failed", "error" => "Connection error"]);
        return;
    }
    if (isset($json->Status) && !$json->Status) {
        echo json_encode(["status" => "failed", "error" => htmlspecialchars($json->Error ?? "{error}")]);
        return;
    }

    echo json_encode([
        "status"    => $json->status    ?? "unknown",
        "op_id"     => $json->op_id     ?? $opId,
        "extracted" => intval($json->extracted ?? 0),
        "error"     => $json->error     ?? '',
        "files"     => $json->files     ?? [],
    ]);
}
