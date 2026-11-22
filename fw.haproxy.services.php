<?php
// POST SP10 20261023-12
// Load-Balancer services. Every read and write goes through the daemon
// (/haproxy/services, /haproxy/service, /haproxy/service/{save,enable,delete},
// /haproxy/backend/save): the page used to drive haproxy_multi from
// class.haproxy.inc, which appliances no longer ship, and to run its own SQL.
include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"] = new sockets();
}
include_once(dirname(__FILE__) . "/ressources/class.system.network.inc");
include_once(dirname(__FILE__) . "/ressources/class.system.nics.inc");
// awc_table_style()/awc_pager_js()/awc_search_bar()/awc_filter_js() must stay at
// the top level: inside the class_exists() block above it would never run.
include_once(dirname(__FILE__) . "/ressources/class.statistics_appliance.inc");
if (isset($_GET["verbose"])) {
    $GLOBALS["VERBOSE"] = true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}
if (isset($_GET["table"])) {table();exit;}
if (isset($_GET["balancer-js"])) {balancer_js();exit;}
if (isset($_GET["balancer-choose"])) {balancer_choose();exit;}
if (isset($_POST["create-service"])) {balancer_create();exit;}
if (isset($_GET["balancer-tabs"])) {balancer_tabs();exit;}
if (isset($_GET["balancer-parameters"])) {balancer_parameters();exit;}
if (isset($_POST["servicename"])) {balancer_parameters_save();exit;}
if (isset($_GET["balancer-enable-js"])) {balancer_enable();exit;}
if (isset($_GET["balancer-duplicate-js"])) {balancer_duplicate();exit;}
if (isset($_GET["balancer-duplicate-dialog"])) {balancer_duplicate_dialog();exit;}
if (isset($_POST["origBackend"])) {balancer_duplicate_save();exit;}
if (isset($_GET["balancer-delete-js"])) {balancer_delete_js();exit;}
if (isset($_POST["balancer-delete"])) {balancer_delete();exit;}
page();

// ---------------------------------------------------------------- helpers

// Service types, as HaProxy.ServiceTypeNames numbers them.
function lbsvc_types(): array
{
    return array(
        0 => array("{lbsvc_type0}", "{lbsvc_type0_text}", "fas fa-users", "#e6eeed", "#005447", "proxy"),
        1 => array("{lbsvc_type1}", "{lbsvc_type1_text}", "fas fa-globe", "#f0fdfa", "#0f766e", "http"),
        2 => array("{lbsvc_type2}", "{lbsvc_type2_text}", "fas fa-lock", "#ecfdf5", "#047857", "reverse"),
        3 => array("{lbsvc_type3}", "{lbsvc_type3_text}", "fas fa-network-wired", "#f1f5f9", "#334155", "tcp"),
        4 => array("{lbsvc_type4}", "{lbsvc_type4_text}", "fas fa-envelope", "#fffbeb", "#b45309", "smtp"),
    );
}

// Mirrors HaProxy.AlgoNames.
function lbsvc_algos(): array
{
    return array("roundrobin" => "{round-robin}", "leastconn" => "{leastconn}", "source" => "{strict-hashed-ip}", "uri" => "HTTP URL");
}

// Mirrors HaProxy.HTTPReuseValues.
function lbsvc_http_reuse(): array
{
    $out = array();
    foreach (array("Disable", "never", "safe", "aggressive", "always") as $v) {$out[$v] = $v;}
    return $out;
}

function lbsvc_get($route)
{
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($route));
    if (!is_object($json)) {
        $e = $GLOBALS["CLASS_SOCKETS"]->mysql_error;
        return array(null, $e != "" ? $e : "{error}");
    }
    if (!$json->Status) {return array(null, (string)$json->Error);}
    return array($json, "");
}

// Writes never rebuild the Load-Balancer, enabling and deleting included: only the
// {apply_configuration} button of the toolbar applies the configuration.
function lbsvc_post($route, $data): string
{
    $data["reload"] = false;
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON($route, $data));
    if (!is_object($json)) {
        $e = $GLOBALS["CLASS_SOCKETS"]->mysql_error;
        return $e != "" ? $e : "{error}";
    }
    if (!$json->Status) {return (string)$json->Error;}
    return "";
}

// /haproxy/service answers an empty service for an unknown name (LoadService
// returns emptyService on sql.ErrNoRows): existence is read from the list.
function lbsvc_exists($name): bool
{
    list($json, $err) = lbsvc_get("/haproxy/services");
    if ($json === null || !is_array($json->services)) {return false;}
    foreach ($json->services as $s) {if ((string)$s->servicename === (string)$name) {return true;}}
    return false;
}

function lbsvc_service($name)
{
    if (trim((string)$name) == "" || !lbsvc_exists($name)) {return null;}
    list($json, $err) = lbsvc_get("/haproxy/service?servicename=" . urlencode((string)$name));
    if ($json === null) {return null;}
    return $json->service;
}

function lbsvc_decode_config($b64): array
{
    $a = @unserialize(base64_decode((string)$b64), array("allowed_classes" => false));
    return is_array($a) ? $a : array();
}

// Every field of a service, from its detail, so a save never resets the
// columns a form does not show.
// The certificates are only edited by fw.haproxy.certificates.php: they are sent
// back exactly as stored, so a settings save can neither drop nor rewrite them.
function lbsvc_payload($svc): array
{
    $p = array(
        "servicename" => (string)$svc->servicename, "servicetype" => intval($svc->servicetype),
        "listen_ip" => (string)$svc->listen_ip, "listen_port" => intval($svc->listen_port),
        "enabled" => $svc->enabled ? 1 : 0, "loadbalancetype" => intval($svc->loadbalancetype),
        "dispatch_mode" => (string)$svc->dispatch_mode, "transparent" => intval($svc->transparent),
        "transparentsrcport" => intval($svc->transparentsrcport), "tunnel_mode" => intval($svc->tunnel_mode),
        "crtype" => intval($svc->crtype), "client_timout" => intval($svc->client_timout),
        "checkup_interval" => intval($svc->checkup_interval), "wakeup_interval" => intval($svc->wakeup_interval),
        "ssl" => intval($svc->ssl), "MainConfig" => (string)$svc->MainConfig,
    );
    if (isset($svc->certificates)) {$p["certificates"] = (string)$svc->certificates;}
    return $p;
}

// Backend names and states of a service, for the list column.
function lbsvc_backends($name): array
{
    list($json, $err) = lbsvc_get("/haproxy/backends?servicename=" . urlencode((string)$name));
    if ($json === null || !is_array($json->backends)) {return array();}
    return $json->backends;
}

// haproxy_listen_vips() returns the enabled keepalived VIPs, by IP, as listen
// addresses. Without them only the NIC addresses were offered: on the PASSIVE
// node the VIP is not local, so a service published on the VIP could not be
// configured the same way on both nodes. is_file first: lib_sqlite would create
// an empty database.
function haproxy_listen_vips(): array
{
    $vips = array();
    $enabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE"));
    $enabledSlave = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE"));
    if ($enabled == 0 && $enabledSlave == 0) {return $vips;}
    $db = "/home/artica/SQLITE/keepalived.db";
    if (!is_file($db)) {return $vips;}
    $q = new lib_sqlite($db);
    $results = $q->QUERY_SQL("SELECT v.virtual_ip, v.label FROM keepalived_virtual_interfaces v
        INNER JOIN keepalived_primary_nodes p ON p.ID = v.primary_node_id
        WHERE v.enable = '1' AND p.enable = '1' ORDER BY v.ID");
    if (!$q->ok || !is_array($results)) {return $vips;}
    foreach ($results as $ligne) {
        $ip = trim($ligne["virtual_ip"] ?? "");
        if ($ip == "") {continue;}
        $label = trim($ligne["label"] ?? "");
        $vips[$ip] = ($label == "") ? "VIP keepalived ($ip)" : "VIP keepalived ($label/$ip)";
    }
    return $vips;
}

// The addresses a service can listen on: every enabled NIC plus the VIPs.
function lbsvc_listen_ips($current): array
{
    $ips = array("*" => "{all}");
    $nic = new networking();
    foreach ($nic->Local_interfaces() as $yinter => $line) {
        if ($yinter == "lo" || preg_match("#^dummy#", $yinter) || preg_match("#-ifb$#", $yinter)) {continue;}
        $znic = new system_nic($yinter);
        if ($znic->Bridged == 1 || $znic->enabled == 0) {continue;}
        $ips[$znic->IPADDR] = "$znic->NICNAME ($yinter/$znic->IPADDR)";
    }
    foreach (haproxy_listen_vips() as $vip => $vipLabel) {$ips[$vip] = $vipLabel;}
    // A stored address missing from the list (VIP removed, NIC gone) would show
    // as the first option, and the next save would overwrite it unchosen.
    $current = trim((string)$current);
    if ($current != "" && !isset($ips[$current])) {$ips[$current] = "$current ({lbsvc_not_found})";}
    return $ips;
}

// The Squid port a listen port collides with, "" when free.
function lbsvc_port_conflict($port): string
{
    if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable")) != 1) {return "";}
    if (!is_file("/home/artica/SQLITE/proxy.db")) {return "";}
    $q = new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne = $q->mysqli_fetch_array("SELECT PortName,ID FROM proxy_ports WHERE port='" . intval($port) . "'");
    if (intval($ligne["ID"] ?? 0) == 0) {return "";}
    return "{lbsvc_port_used_by_proxy} " . intval($port) . " (" . htmlspecialchars((string)$ligne["PortName"]) . ")";
}

function lbsvc_style(): string
{
    return "<style>
.lbv-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-top:10px}
.lbv-tile{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 18px}
.lbv-tile-l{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#64748b}
.lbv-tile-v{font-size:26px;font-weight:800;color:#0f172a;margin-top:4px}
.lbv-tile-s{font-size:12px;color:#64748b;margin-top:2px}
.lbv-tile-ok .lbv-tile-s{color:#047857}
.lbv-sec-t{font-size:13px;font-weight:700;color:#0f172a;margin:26px 0 2px 0}
.lbv-sec-x{font-size:12px;color:#64748b;margin-bottom:12px}
.lbv-types{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}
.lbv-type{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px;cursor:pointer;transition:border-color .15s,box-shadow .15s;text-decoration:none!important;display:block}
.lbv-type:hover{border-color:#0d9488;box-shadow:0 4px 12px rgba(15,23,42,.06)}
.lbv-type-ico{width:34px;height:34px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:15px;margin-bottom:10px}
.lbv-type-t{font-size:13px;font-weight:700;color:#0f172a}
.lbv-type-x{font-size:12px;color:#64748b;line-height:1.5;margin-top:3px}
.lbv-chips{display:flex;gap:6px;flex-wrap:wrap;margin:18px 0 0 0}
.lbv-chip{font-size:12px;font-weight:600;padding:5px 12px;border-radius:999px;border:1px solid #cbd5e1;color:#334155!important;background:#fff;text-decoration:none!important;cursor:pointer}
.lbv-chip-on{background:#0f766e;border-color:#0f766e;color:#fff!important}
.lbv-addr{font-weight:600;color:#0f172a;white-space:nowrap}
.lbv-type-badge{display:inline-block;font-size:11px;font-weight:700;padding:2px 8px;border-radius:4px;white-space:nowrap}
.lbv-algo{font-size:12px;color:#475569;white-space:nowrap}
@media(max-width:1100px){.lbv-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}.lbv-types{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.lbv-tiles,.lbv-types{grid-template-columns:1fr}}
</style>";
}

// ---------------------------------------------------------------- page + table

function page()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $html = $tpl->page_header("{load_balancing} &raquo; {lbsvc_title}", "fas fa-network-wired", "{lbsvc_explain}",
        "$page?table=yes", "lb-services", "progress-haproxy-restart", false, "table-haproxy-services");
    if (isset($_GET["main-page"])) {
        $tpl = new template_admin("{lbsvc_title}", $html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

function table()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $types = lbsvc_types();
    $algos = lbsvc_algos();
    $filter = preg_replace('/[^a-z]/', '', (string)($_GET["type"] ?? ""));
    $reload = "LoadAjaxSilent('table-haproxy-services','$page?table=yes&type=$filter');";

    list($json, $err) = lbsvc_get("/haproxy/services");
    $services = ($json !== null && is_array($json->services)) ? $json->services : array();
    usort($services, function ($a, $b) {return strcasecmp((string)$a->servicename, (string)$b->servicename);});

    // Indicator tiles, over every service whatever the filter.
    $addresses = array();
    $active = 0;
    $backends = 0;
    foreach ($services as $s) {
        $addresses[(string)$s->listen_ip == "" ? "*" : (string)$s->listen_ip] = true;
        if ($s->enabled) {$active++;}
        $backends += intval($s->backends);
    }
    $vips = count(haproxy_listen_vips());
    $html[] = "<div class='lbv-tiles'>";
    $html[] = "<div class='lbv-tile'><div class='lbv-tile-l'>{lbsvc_published}</div><div class='lbv-tile-v'>" . count($services) .
        "</div><div class='lbv-tile-s'>" . count($addresses) . " {lbsvc_listen_addresses}</div></div>";
    $html[] = "<div class='lbv-tile" . ($active == count($services) && $active > 0 ? " lbv-tile-ok" : "") . "'><div class='lbv-tile-l'>{lbsvc_active}</div><div class='lbv-tile-v'>$active / " . count($services) .
        "</div><div class='lbv-tile-s'>" . (count($services) > 0 ? round($active * 100 / count($services)) . "% {lbsvc_operational}" : "&nbsp;") . "</div></div>";
    $html[] = "<div class='lbv-tile'><div class='lbv-tile-l'>{lbsvc_backends}</div><div class='lbv-tile-v'>$backends</div><div class='lbv-tile-s'>{servers}</div></div>";
    $html[] = "<div class='lbv-tile" . ($vips > 0 ? " lbv-tile-ok" : "") . "'><div class='lbv-tile-l'>{lbsvc_ha}</div><div class='lbv-tile-v' style='font-size:20px;line-height:38px'>" .
        ($vips > 0 ? "{lbsvc_vrrp_active}" : "{lbsvc_not_configured}") . "</div><div class='lbv-tile-s'>" . ($vips > 0 ? "$vips {lbsvc_vips}" : "&nbsp;") . "</div></div>";
    $html[] = "</div>";

    // Publication templates: each card opens the creation dialog on its type.
    $html[] = "<div class='lbv-sec-t'>{lbsvc_templates}</div><div class='lbv-sec-x'>{lbsvc_templates_explain}</div>";
    $html[] = "<div class='lbv-types'>";
    foreach ($types as $id => $t) {
        $html[] = "<a class='lbv-type' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?balancer-js=&servicetype=$id')\">" .
            "<span class='lbv-type-ico' style='background:$t[3];color:$t[4]'><i class='$t[2]'></i></span>" .
            "<div class='lbv-type-t'>$t[0]</div><div class='lbv-type-x'>$t[1]</div></a>";
    }
    $html[] = "</div>";

    // Type filter.
    $chips = array("" => "{all}");
    foreach ($types as $t) {$chips[$t[5]] = $t[0];}
    $html[] = "<div class='lbv-chips'>";
    foreach ($chips as $key => $label) {
        $on = $key == $filter ? " lbv-chip-on" : "";
        $html[] = "<a class='lbv-chip$on' href=\"javascript:blur();\" OnClick=\"LoadAjaxSilent('table-haproxy-services','$page?table=yes&type=$key');\">$label</a>";
    }
    $html[] = "</div>";

    $html[] = "<div style='margin-top:12px'>" . awc_search_bar("haps-search", "{search}") . "</div>";
    $html[] = "<div class='awc-wrap' style='padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
    $html[] = "<thead><tr><th>{lbsvc_listen_address}</th><th>{servicename}</th><th>{lbsvc_method_profile}</th>";
    $html[] = "<th>{backends}</th><th class='center'>{active2}</th><th class='center'>{duplicate}</th><th class='center'>{delete}</th></tr></thead>";
    $html[] = "<tbody id='haps-rows'>";

    $shown = 0;
    foreach ($services as $s) {
        $type = intval($s->servicetype);
        $t = $types[$type] ?? $types[3];
        if ($filter != "" && $t[5] != $filter) {continue;}
        $shown++;
        $name = (string)$s->servicename;
        $enc = urlencode($name);
        $ip = (string)$s->listen_ip;
        if ($ip == "" || $ip == "*") {$ip = "0.0.0.0";}
        $on = (bool)$s->enabled;
        $edit = "Loadjs('$page?balancer-js=$enc')";
        $enable = $tpl->icon_check($on ? 1 : 0, "Loadjs('$page?balancer-enable-js=$enc&state=" . ($on ? 0 : 1) . "&type=$filter')", null, "AsSquidAdministrator");
        $dup = $tpl->icon_copy("Loadjs('$page?balancer-duplicate-js=$enc&type=$filter')", "AsSquidAdministrator");
        $del = $tpl->icon_delete("Loadjs('$page?balancer-delete-js=$enc&type=$filter')", "AsSquidAdministrator");
        $algo = $algos[(string)$s->dispatch_mode] ?? htmlspecialchars((string)$s->dispatch_mode);
        // The list rather than the count: "1" did not say whether that backend
        // serves. Six at most, past that the row grows taller than wide.
        $bmax = 6;
        $blines = array();
        $nb = 0;
        foreach (lbsvc_backends($name) as $b) {
            $nb++;
            if ($nb > $bmax) {continue;}
            $bon = (bool)$b->enabled;
            $blines[] = "<div style='display:flex;align-items:center;gap:6px;white-space:nowrap" . ($bon ? "" : ";opacity:.6") . "'>" .
                "<span class='awc-badge " . ($bon ? "awc-badge-on" : "awc-badge-off") . "'>" . ($bon ? "{active2}" : "{disabled}") . "</span>" .
                "<span>" . htmlspecialchars((string)$b->backendname) . "</span></div>";
        }
        if ($nb > $bmax) {$blines[] = "<div style='font-size:11px;color:#6c7b6d'>+" . ($nb - $bmax) . "</div>";}
        $bcell = $nb > 0 ? "<div style='display:flex;flex-direction:column;gap:3px;align-items:flex-start'>" . @implode("", $blines) . "</div>"
            : "<span class='awc-muted'>0 {servers}</span>";

        $html[] = "<tr>";
        $html[] = "<td style='width:1%' nowrap><a class='lbv-addr' href=\"javascript:blur();\" OnClick=\"$edit\">" . htmlspecialchars($ip) . ":" . intval($s->listen_port) . "</a>" .
            "<div class='awc-meta'>" . htmlspecialchars((string)$s->mode) . "</div></td>";
        $html[] = "<td style='width:99%'><div class='awc-ident'><span class='awc-ico" . ($on ? "" : " awc-ico-off") . "'><i class='$t[2]'></i></span>" .
            "<span><span class='awc-name" . ($on ? "" : " awc-name-off") . "'><a href=\"javascript:blur();\" OnClick=\"$edit\">" . htmlspecialchars($name) . "</a></span>" .
            "<div class='awc-meta'>$t[1]</div></span></div></td>";
        $html[] = "<td nowrap><span class='lbv-type-badge' style='background:$t[3];color:$t[4]'>$t[0]</span> <span class='lbv-algo'>&middot; $algo</span></td>";
        $html[] = "<td nowrap>$bcell</td>";
        $html[] = "<td class='center'>$enable</td><td class='center'>$dup</td><td class='center'>$del</td>";
        $html[] = "</tr>";
    }
    if ($err != "") {
        $html[] = "<tr><td colspan='7'><div class='awc-empty'>" . htmlspecialchars($err) . "</div></td></tr>";
    } elseif ($shown == 0) {
        $html[] = "<tr><td colspan='7'><div class='awc-empty'>{lbsvc_empty}<br><br>" .
            "<a class='awc-btn' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?balancer-js=')\"><i class='fas fa-plus'></i> {lbsvc_create}</a></div></td></tr>";
    }
    $html[] = "</tbody></table></div>";
    $footer = "$shown / " . count($services) . " {items}";
    $version = (string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_VERSION");
    if ($version != "") {$footer .= " &mdash; HAProxy " . htmlspecialchars($version);}
    $html[] = "<div class='awc-foot'><span>$footer</span><span class='awc-pg' id='haps-pager'></span></div></div></div>";

    $jsapply = $tpl->framework_buildjs("/haproxy/reload", "haproxy.progress", "haproxy.progress.log", "progress-haproxy-restart", $reload);
    $topbuttons = array();
    $topbuttons[] = array("Loadjs('$page?balancer-js=')", ico_plus, "{new_service}");
    $topbuttons[] = array($jsapply, ico_save, "{apply_configuration}");
    $topbuttons[] = array($reload, ico_refresh, "{refresh}");
    $TINY = array();
    $TINY["TITLE"] = "{load_balancing} &raquo; {lbsvc_title}";
    $TINY["ICO"] = "fas fa-network-wired";
    $TINY["EXPL"] = "{lbsvc_explain}";
    $TINY["URL"] = "lb-services";
    $TINY["BUTTONS"] = $tpl->table_buttons($topbuttons);

    $html[] = "<script>NoSpinner();\n" . $tpl->ICON_SCRIPTS_COMPILE() . "\nLoadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY))) . "');</script>";
    echo awc_table_style() . lbsvc_style()
        . $tpl->_ENGINE_parse_body(@implode("\n", $html))
        . awc_pager_js($GLOBALS["FOOTABLE_PSIZE"], "haps-rows", "haps-pager")
        . awc_filter_js("haps-search", "haps-rows", "haps-pager");
}

function lbsvc_reload_js(): string
{
    $page = CurrentPageName();
    $type = preg_replace('/[^a-z]/', '', (string)($_GET["type"] ?? ""));
    return "LoadAjaxSilent('table-haproxy-services','$page?table=yes&type=$type');";
}

// ---------------------------------------------------------------- create

function balancer_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $name = (string)($_GET["balancer-js"] ?? "");
    if ($name == "") {
        $type = intval($_GET["servicetype"] ?? 0);
        $tpl->js_dialog1($tpl->javascript_parse_text("{new_service}"), "$page?balancer-choose=yes&servicetype=$type");
        return;
    }
    $tpl->js_dialog1($tpl->javascript_parse_text("{lbsvc_configuration}: ") . $name, "$page?balancer-tabs=" . urlencode($name));
}

function balancer_choose()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $type = intval($_GET["servicetype"] ?? 0);
    $types = array();
    foreach (lbsvc_types() as $id => $t) {$types[$id] = $t[0];}
    $form[] = $tpl->field_hidden("create-service", "yes");
    $form[] = $tpl->field_text("servicename", "{servicename}", null, true);
    $form[] = $tpl->field_array_hash($types, "servicetype", "{servicetype}", $type, true);
    $form[] = $tpl->field_array_hash(lbsvc_listen_ips(""), "listen_ip", "{listen_ip}", "*");
    $form[] = $tpl->field_numeric("listen_port", "{listen_port}", $type == 4 ? 25 : 8080);
    $jsafter = "dialogInstance1.close();LoadAjaxSilent('table-haproxy-services','$page?table=yes');";
    $html[] = $tpl->div_explain("{new_service}||{haproxy_choose_service}");
    $html[] = $tpl->form_outside("", @implode("\n", $form), "", "{add}", $jsafter, "AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function balancer_create()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $name = trim((string)$_POST["servicename"]);
    if ($name == "") {echo $tpl->post_error("{servicename}: {no_data}");return;}
    if (lbsvc_service($name) !== null) {echo $tpl->post_error("{servicename}: " . htmlspecialchars($name) . " {lbsvc_already_exists}");return;}
    $port = intval($_POST["listen_port"]);
    $conflict = lbsvc_port_conflict($port);
    if ($conflict != "") {echo $tpl->post_error($conflict);return;}
    $type = intval($_POST["servicetype"]);
    $ip = (string)$_POST["listen_ip"];
    if ($ip == "") {$ip = "*";}

    // The defaults of the PHP balancer_create(): the load-balance mode follows
    // the type, and the proxy/SMTP types carry their own flags.
    $mode = array(0 => 2, 1 => 1, 2 => 1, 3 => 0, 4 => 0);
    $main = array();
    if ($type == 0) {$main["asSquidArtica"] = 1;}
    if ($type == 4) {
        $users = new usersMenus();
        $main["UseSMTPProto"] = 1;
        $main["smtpchk_EHLO"] = $users->hostname;
    }
    $data = array("servicename" => $name, "servicetype" => $type, "listen_ip" => $ip, "listen_port" => $port,
        "enabled" => 1, "loadbalancetype" => $mode[$type] ?? 0, "dispatch_mode" => "roundrobin");
    if (count($main) > 0) {$data["MainConfig"] = base64_encode(serialize($main));}
    $e = lbsvc_post("/haproxy/service/save", $data);
    if ($e != "") {echo $tpl->post_error($e);}
}

// ---------------------------------------------------------------- edit

function balancer_tabs()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $name = (string)$_GET["balancer-tabs"];
    $enc = urlencode($name);
    $svc = lbsvc_service($name);
    $array["{parameters}"] = "$page?balancer-parameters=$enc";
    if ($svc !== null) {
        $type = intval($svc->servicetype);
        $label = $type == 0 ? "{proxy_clients}" : ($type == 2 ? "{web_servers}" : "{backends}");
        $url = $type == 2 ? "fw.haproxy.webservers.php?servicename=$enc" : "fw.haproxy.backends.php?servicename=$enc";
        // Counted from the list the tab itself shows: the service detail does not
        // carry the "backends" count the services list has, and read 0.
        $array["$label (" . count(lbsvc_backends($name)) . ")"] = $url;
    }
    echo $tpl->tabs_default($array);
}

function balancer_parameters()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $name = (string)$_GET["balancer-parameters"];
    $svc = lbsvc_service($name);
    if ($svc === null) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{servicename}: " . htmlspecialchars($name) . " {no_data}"));
        return;
    }
    $type = intval($svc->servicetype);
    $m = lbsvc_decode_config($svc->MainConfig);
    $defaults = array("contimeout" => 4000, "srvtimeout" => 50000, "clitimeout" => 15000, "retries" => 3,
        "HttpKeepAliveTimeout" => 15000, "TimeoutTunnel" => 30000, "HttpRequestTimeout" => 50000, "HttpQueueTimeout" => 50000);
    foreach ($defaults as $k => $v) {if (intval($m[$k] ?? 0) == 0) {$m[$k] = $v;}}
    if (!isset($m["smtpchk_EHLO"])) {$users = new usersMenus();$m["smtpchk_EHLO"] = $users->hostname;}
    $g = function ($k) use ($m) {return $m[$k] ?? "";};

    $port = intval($svc->listen_port);
    if ($port < 2) {$port = 8080;}
    $form[] = $tpl->field_hidden("servicename", $name);
    $form[] = $tpl->field_section("{lbsvc_sec_listen}");
    $form[] = $tpl->field_array_hash(lbsvc_listen_ips($svc->listen_ip), "listen_ip", "{listen_ip}", (string)$svc->listen_ip);
    $form[] = $tpl->field_numeric("listen_port", "{listen_port}", $port);

    if ($type == 0) {
        $form[] = $tpl->field_section("{lbsvc_sec_auth}");
        $form[] = $tpl->field_checkbox("NTLM_COMPATIBILITY", "{NTLM_COMPATIBLE}", intval($g("NTLM_COMPATIBILITY")), false, "{HAP_NTLM_COMPATIBLE}");
        $form[] = $tpl->field_checkbox("KERBEROS_COMPATIBILITY", "{kerberos_authentication_support}", intval($g("KERBEROS_COMPATIBILITY")), false, "{kerberos_authentication_support}");
    }
    if ($type == 0 || $type == 1) {
        $form[] = $tpl->field_section("{ssl_protocol}");
        $form[] = $tpl->field_checkbox("ssl", "{enable_ssl}", intval($svc->ssl));
        $form[] = $tpl->field_info("replace_rules", "{certificates}", array("VALUE" => null, "BUTTON" => true,
            "BUTTON_CAPTION" => "{certificates}", "BUTTON_JS" => "Loadjs('fw.haproxy.certificates.php?service-js=" . urlencode($name) . "')"), "{certificates}");
        $form[] = $tpl->field_section("{header_checks}");
        $form[] = $tpl->field_checkbox("forwardfor", "X-Forwarded-For", intval($g("forwardfor")));
        $form[] = $tpl->field_section("{caching}");
        $form[] = $tpl->field_checkbox("cache-use", "{enable_caching_squid}", intval($g("cache-use")));
    }
    $form[] = $tpl->field_section("{lbsvc_sec_algo}");
    $algos = lbsvc_algos();
    $dispatch = (string)$svc->dispatch_mode;
    if ($dispatch != "" && !isset($algos[$dispatch])) {$algos[$dispatch] = $dispatch;}
    $form[] = $tpl->field_array_hash($algos, "dispatch_mode", "{dispatch_method2}", $dispatch);
    $form[] = $tpl->field_checkbox("tunnel_mode", "{tunnel_mode}", intval($svc->tunnel_mode));
    $form[] = $tpl->field_checkbox("UseCookies", "{UseCookies}", intval($g("UseCookies")));
    $form[] = $tpl->field_checkbox("http-keep-alive", "HTTP-keep-alive", intval($g("http-keep-alive")));
    $form[] = $tpl->field_checkbox("http-use-proxy-header", "HTTP Proxy Header", intval($g("http-use-proxy-header")));
    $form[] = $tpl->field_checkbox("accept-invalid-http-request", "Accept Invalid HTTP Request", intval($g("accept-invalid-http-request")));
    $reuse = lbsvc_http_reuse();
    $curReuse = (string)$g("http_reuse");
    if ($curReuse != "" && !isset($reuse[$curReuse])) {$reuse[$curReuse] = $curReuse;}
    $form[] = $tpl->field_array_hash($reuse, "http_reuse", "HTTP Reuse", $curReuse);
    if ($type == 4) {
        $form[] = $tpl->field_section("{SMTP_MODE}");
        $form[] = $tpl->field_checkbox("UseSMTPProto", "{UseSMTPProto}", intval($g("UseSMTPProto")));
        $form[] = $tpl->field_text("smtpchk_EHLO", "{smtpchk_EHLO}", $g("smtpchk_EHLO"));
    }
    $form[] = $tpl->field_section("{lbsvc_sec_timeouts}");
    foreach (array("HttpRequestTimeout", "TimeoutTunnel", "contimeout", "srvtimeout", "clitimeout", "HttpKeepAliveTimeout", "HttpQueueTimeout") as $k) {
        $form[] = $tpl->field_numeric($k, "{" . $k . "} ({milliseconds})", intval($m[$k]));
    }
    $form[] = $tpl->field_numeric("retries", "{maxretries}", intval($m["retries"]));

    $jsafter = "LoadAjaxSilent('table-haproxy-services','$page?table=yes');";
    echo $tpl->_ENGINE_parse_body($tpl->form_outside("", @implode("\n", $form), "", "{apply}", $jsafter, "AsSquidAdministrator"));
}

function balancer_parameters_save()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $name = (string)$_POST["servicename"];
    $svc = lbsvc_service($name);
    if ($svc === null) {echo $tpl->post_error("{servicename}: " . htmlspecialchars($name) . " {no_data}");return;}
    $port = intval($_POST["listen_port"]);
    if ($port != intval($svc->listen_port)) {
        $conflict = lbsvc_port_conflict($port);
        if ($conflict != "") {echo $tpl->post_error($conflict);return;}
    }
    if (intval($_POST["KERBEROS_COMPATIBILITY"] ?? 0) == 1) {$_POST["NTLM_COMPATIBILITY"] = 0;}

    $data = lbsvc_payload($svc);
    $m = lbsvc_decode_config($svc->MainConfig);
    $data["listen_ip"] = (string)$_POST["listen_ip"];
    $data["listen_port"] = $port;
    if (isset($_POST["dispatch_mode"]) && $_POST["dispatch_mode"] !== "") {$data["dispatch_mode"] = (string)$_POST["dispatch_mode"];}
    if (isset($_POST["ssl"])) {$data["ssl"] = intval($_POST["ssl"]);}
    if (isset($_POST["tunnel_mode"])) {$data["tunnel_mode"] = intval($_POST["tunnel_mode"]);}
    $data["transparentsrcport"] = 0;
    // Only the keys this form shows: the rest of MainConfig is kept as stored.
    $ints = array("forwardfor", "cache-use", "http-keep-alive", "UseSMTPProto", "contimeout", "srvtimeout", "clitimeout",
        "HttpRequestTimeout", "HttpQueueTimeout", "retries", "UseCookies", "TimeoutTunnel", "HttpKeepAliveTimeout",
        "NTLM_COMPATIBILITY", "KERBEROS_COMPATIBILITY", "http-use-proxy-header", "accept-invalid-http-request");
    foreach ($ints as $k) {if (isset($_POST[$k])) {$m[$k] = intval($_POST[$k]);}}
    foreach (array("smtpchk_EHLO", "http_reuse") as $k) {if (isset($_POST[$k])) {$m[$k] = (string)$_POST[$k];}}
    $data["MainConfig"] = base64_encode(serialize($m));
    $e = lbsvc_post("/haproxy/service/save", $data);
    if ($e != "") {echo $tpl->post_error($e);}
}

// ---------------------------------------------------------------- row actions

function balancer_enable()
{
    header("content-type: application/x-javascript");
    $tpl = new template_admin();
    $e = lbsvc_post("/haproxy/service/enable", array("servicename" => (string)$_GET["balancer-enable-js"], "state" => intval($_GET["state"] ?? 0)));
    if ($e != "") {$tpl->js_error($e);return;}
    echo lbsvc_reload_js();
}

function balancer_delete_js()
{
    $tpl = new template_admin();
    $name = (string)$_GET["balancer-delete-js"];
    $tpl->js_confirm_delete(htmlspecialchars($name), "balancer-delete", $name, lbsvc_reload_js());
}

function balancer_delete()
{
    $tpl = new template_admin();
    $e = lbsvc_post("/haproxy/service/delete", array("servicename" => (string)$_POST["balancer-delete"]));
    if ($e != "") {echo $tpl->post_error($e);}
}

function balancer_duplicate()
{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $tpl->js_dialog1($tpl->javascript_parse_text("{duplicate}"), "$page?balancer-duplicate-dialog=" . urlencode((string)$_GET["balancer-duplicate-js"]));
}

function balancer_duplicate_dialog()
{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $orig = (string)$_GET["balancer-duplicate-dialog"];
    $svc = lbsvc_service($orig);
    $form[] = $tpl->field_hidden("origBackend", $orig);
    $form[] = $tpl->field_text("newBackendName", "{servicename}", $orig . "_copy", true);
    $form[] = $tpl->field_numeric("newBackendPort", "{listen_port}", $svc !== null ? intval($svc->listen_port) + 1 : 8081);
    $jsafter = "dialogInstance1.close();LoadAjaxSilent('table-haproxy-services','$page?table=yes');";
    $html[] = $tpl->div_explain("{duplicate}: " . htmlspecialchars($orig) . "||{lbsvc_duplicate_explain}");
    $html[] = $tpl->form_outside("", @implode("\n", $form), "", "{add}", $jsafter, "AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

// A copy of the service, settings and backends included, on another port.
function balancer_duplicate_save()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $orig = (string)$_POST["origBackend"];
    $name = trim((string)$_POST["newBackendName"]);
    $port = intval($_POST["newBackendPort"]);
    $svc = lbsvc_service($orig);
    if ($svc === null) {echo $tpl->post_error("{servicename}: " . htmlspecialchars($orig) . " {no_data}");return;}
    if ($name == "") {echo $tpl->post_error("{servicename}: {no_data}");return;}
    if (lbsvc_service($name) !== null) {echo $tpl->post_error("{servicename}: " . htmlspecialchars($name) . " {lbsvc_already_exists}");return;}
    $conflict = lbsvc_port_conflict($port);
    if ($conflict != "") {echo $tpl->post_error($conflict);return;}

    $data = lbsvc_payload($svc);
    $data["servicename"] = $name;
    $data["listen_port"] = $port;
    $data["transparentsrcport"] = 0;
    $e = lbsvc_post("/haproxy/service/save", $data);
    if ($e != "") {echo $tpl->post_error($e);return;}

    list($json, $err) = lbsvc_get("/haproxy/backends?servicename=" . urlencode($orig));
    if ($json === null) {echo $tpl->post_error($err);return;}
    foreach ((is_array($json->backends) ? $json->backends : array()) as $b) {
        $e = lbsvc_post("/haproxy/backend/save", array("servicename" => $name, "backendname" => (string)$b->backendname,
            "sitename" => (string)$b->sitename, "listen_ip" => (string)$b->listen_ip, "listen_port" => (string)$b->listen_port,
            "bweight" => (string)$b->bweight, "enabled" => $b->enabled ? 1 : 0, "localInterface" => (string)$b->localInterface,
            "MainConfig" => (string)$b->MainConfig));
        if ($e != "") {echo $tpl->post_error($e);return;}
    }
}
