<?php
// awc_table_style(): feuille de style partagee des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
/**
 * Network Listeners (TCP/UDP sockets) for a single remote agent.
 * Shows listening ports grouped by network interface, with process info.
 *
 * Entry:  LoadAjax('div','fw.netagents.netstats.php?id={agent_id}')
 * API:    GET /netagents/netstats/listeners/{id}
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }
$users = new usersMenus();
if (!meta_node_admin($users)) { exit(); }

if (isset($_GET["search"]))     { netstats_list(); exit; }
if (isset($_GET["flat"]))       { netstats_flat(); exit; }
if (isset($_GET["js"]))       { netstats_js(); exit; }
netstats_head();

function aid(): int {
    return intval($_GET["id"] ?? $_POST["id"] ?? 0);
}

function netstats_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["js"]);
    return $tpl->js_dialog7("{open_ports}","$page?id=$id",850);
}

function fetch_listeners(int $id): ?array {
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/netstats/listeners/$id"));
    if (is_object($json) && isset($json->Status) && !$json->Status) { return null; }
    if (!is_array($json)) { return null; }
    return $json;
}

function iface_display(string $name): string {
    if ($name === '*')       return '{all_interfaces} (0.0.0.0 / ::)';
    if ($name === 'lo')      return 'Loopback (127.0.0.1 / ::1)';
    if ($name === 'unknown') return '{unknown}';
    return htmlspecialchars($name);
}

function iface_icon(string $name): string {
    if ($name === '*')  return 'fas fa-globe';
    if ($name === 'lo') return 'fas fa-undo';
    return 'fas fa-ethernet';
}

function proto_badge(string $proto): string {
    $colors = [
        'tcp'  => '#3498db', 'tcp6' => '#2980b9',
        'udp'  => '#27ae60', 'udp6' => '#229954',
    ];
    $bg = $colors[$proto] ?? '#676a6c';
    $label = strtoupper(htmlspecialchars($proto));
    return "<span class='badge' style='background:$bg;color:#fff'>$label</span>";
}

function sort_groups(array $groups): array {
    $wildcard = []; $lo = []; $named = [];
    foreach ($groups as $g) {
        $iface = $g->interface ?? '';
        if ($iface === '*')      $wildcard[] = $g;
        elseif ($iface === 'lo') $lo[] = $g;
        else                     $named[] = $g;
    }
    // Plancher PHP 7.3: pas de fonction flechee (elle casse tout le fichier).
    usort($named, function($a, $b){ return strcmp($a->interface ?? '', $b->interface ?? ''); });
    return array_merge($wildcard, $named, $lo);
}

function sort_sockets(array $sockets): array {
    usort($sockets, function($a, $b){ return ($a->local_port ?? 0) <=> ($b->local_port ?? 0); });
    return $sockets;
}

// ── Head ─────────────────────────────────────────────────────────────────────

function netstats_head(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = aid();

    $h = [];
    $h[] = "<div style='margin-top:10px;margin-bottom:10px'>";
    $h[] = "  <button class='btn btn-sm btn-primary' onclick=\"LoadAjax('netstats-main-$id','$page?search=yes&id=$id')\">";
    $h[] = "    <i class='fas fa-sync-alt'></i> {refresh}";
    $h[] = "  </button>";
    $h[] = "  <div class='btn-group' style='margin-left:10px'>";
    $h[] = "    <button id='btn-grouped-$id' class='btn btn-sm btn-default active' onclick=\"NeStatsView_$id('grouped')\">";
    $h[] = "      <i class='fas fa-layer-group'></i> {grouped}";
    $h[] = "    </button>";
    $h[] = "    <button id='btn-flat-$id' class='btn btn-sm btn-default' onclick=\"NeStatsView_$id('flat')\">";
    $h[] = "      <i class='fas fa-list'></i> {flat_view}";
    $h[] = "    </button>";
    $h[] = "  </div>";
    $h[] = "</div>";
    $h[] = "<div id='netstats-main-$id'></div>";
    $h[] = "<script>";
    $h[] = "LoadAjax('netstats-main-$id','$page?search=yes&id=$id');";
    $h[] = "function NeStatsView_$id(mode){";
    $h[] = "  \$('#btn-grouped-$id,#btn-flat-$id').removeClass('active');";
    $h[] = "  if(mode==='flat'){";
    $h[] = "    \$('#btn-flat-$id').addClass('active');";
    $h[] = "    LoadAjax('netstats-main-$id','$page?flat=yes&id=$id');";
    $h[] = "  } else {";
    $h[] = "    \$('#btn-grouped-$id').addClass('active');";
    $h[] = "    LoadAjax('netstats-main-$id','$page?search=yes&id=$id');";
    $h[] = "  }";
    $h[] = "}";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Grouped View ─────────────────────────────────────────────────────────────

function netstats_list(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = aid();

    $groups = fetch_listeners($id);
    if ($groups === null) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{connection_error}"));
        return;
    }
    if (empty($groups)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        return;
    }

    // Summary counts
    $totalSockets = 0; $tcpCount = 0; $udpCount = 0; $wildcardCount = 0;
    $processes = [];
    foreach ($groups as $g) {
        $sockets = (array)($g->sockets ?? []);
        $totalSockets += count($sockets);
        foreach ($sockets as $s) {
            $proto = $s->protocol ?? '';
            if (str_starts_with($proto, 'tcp')) $tcpCount++;
            if (str_starts_with($proto, 'udp')) $udpCount++;
            if (($g->interface ?? '') === '*') $wildcardCount++;
            $pname = $s->process_name ?? '';
            if ($pname !== '') $processes[$pname] = true;
        }
    }
    $uniqueProcs = count($processes);

    $h = [];
    // Summary bar
    $h[] = "<div class='row' style='margin-bottom:15px'>";
    $h[] = summary_widget('navy-bg', 'fas fa-headphones', '{total_listeners}', $totalSockets);
    $h[] = summary_widget('#3498db', 'fas fa-exchange-alt', 'TCP', $tcpCount);
    $h[] = summary_widget('#27ae60', 'fas fa-arrows-alt-v', 'UDP', $udpCount);
    $h[] = summary_widget('#f8ac59', 'fas fa-globe', '{all} (*)', $wildcardCount);
    $h[] = summary_widget('#676a6c', 'fas fa-cogs', '{processes}', $uniqueProcs);
    $h[] = "</div>";

    // Grouped panels
    $groups = sort_groups($groups);
    foreach ($groups as $g) {
        $iface  = $g->interface ?? 'unknown';
        $addrs  = implode(', ', array_map('htmlspecialchars', (array)($g->addresses ?? [])));
        $sockets = sort_sockets((array)($g->sockets ?? []));
        $cnt    = count($sockets);
        $icon   = iface_icon($iface);
        $label  = iface_display($iface);
        $panelId = 'ns-panel-' . md5($iface) . '-' . $id;

        $h[] = "<div class='ibox'>";
        $h[] = "  <div class='ibox-title' style='cursor:pointer' onclick=\"\$('#$panelId').toggle()\">";
        $h[] = "    <h5><i class='$icon'></i>&nbsp; $label";
        if ($addrs && $iface !== '*' && $iface !== 'lo') {
            $h[] = " <small class='text-muted'>$addrs</small>";
        }
        $h[] = "    </h5>";
        $h[] = "    <div class='ibox-tools'>";
        $h[] = "      <span class='badge' style='background:#1c84c6;color:#fff'>$cnt</span>";
        $h[] = "    </div>";
        $h[] = "  </div>";
        $h[] = "  <div id='$panelId' class='ibox-content' style='padding:0'>";
        $h[] = render_sockets_table($sockets, $iface);
        $h[] = "  </div>";
        $h[] = "</div>";
    }
    $h[] = "<script>NoSpinner();</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Flat View ────────────────────────────────────────────────────────────────

function netstats_flat(): void {
    $tpl  = new template_admin();
    $id   = aid();
    $t    = time();

    $groups = fetch_listeners($id);
    if ($groups === null) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{connection_error}"));
        return;
    }
    if (empty($groups)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        return;
    }

    // Flatten all sockets
    $all = [];
    foreach ($groups as $g) {
        $iface = $g->interface ?? 'unknown';
        foreach ((array)($g->sockets ?? []) as $s) {
            $s->_iface = $iface;
            $all[] = $s;
        }
    }
    usort($all, function($a, $b){ return ($a->local_port ?? 0) <=> ($b->local_port ?? 0); });

    $h = [];
    $h[] = "<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
    $h[] = "<thead><tr>";
    $h[] = "  <th data-sortable='true' data-type='text'>{interface}</th>";
    $h[] = "  <th data-sortable='true' data-type='text'>{protocol}</th>";
    $h[] = "  <th data-sortable='true' data-type='number'>{port}</th>";
    $h[] = "  <th data-sortable='true' data-type='text'>{address}</th>";
    $h[] = "  <th data-sortable='true' data-type='number'>PID</th>";
    $h[] = "  <th data-sortable='true' data-type='text'>{process}</th>";
    $h[] = "  <th>{command}</th>";
    $h[] = "</tr></thead><tbody id='ntnetst-rows'>";

    $TRCLASS = null;
    foreach ($all as $s) {
        $TRCLASS=null;
        $h[] = "<tr class='$TRCLASS'>" . socket_cells($s, true) . "</tr>";
    }

    $h[] = "</tbody>";
    $h[] = "</table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='ntnetst-pager'></span></div></div></div>";
    $h[] = "<script>NoSpinner();\n" . implode("\n", $tpl->ICON_SCRIPTS) . "</script>";

    // Pagination reprise de FooTable (jamais initialise depuis le passage awc-*).
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(implode("\n", $h))
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"] ?? 100,"ntnetst-rows","ntnetst-pager");
}

// ── Render helpers ───────────────────────────────────────────────────────────

function summary_widget(string $bg, string $ico, string $label, int $count): string {
    return "<div class='col-md-2 col-sm-4'>"
         . "<div style='background:$bg;color:#fff;padding:12px 15px;border-radius:4px;text-align:center'>"
         . "<div style='font-size:24px;font-weight:700'>$count</div>"
         . "<div style='font-size:12px'><i class='$ico'></i> $label</div>"
         . "</div></div>";
}

function render_sockets_table(array $sockets, string $iface): string {
    if (empty($sockets)) {
        return "<p style='padding:15px;color:#999'><i>{no_data}</i></p>";
    }
    $h = [];
    $h[] = "<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
    $h[] = "<thead><tr>";
    $h[] = "  <th style='width:1%'>{protocol}</th>";
    $h[] = "  <th style='width:1%'>{port}</th>";
    $h[] = "  <th style='width:1%'>{address}</th>";
    $h[] = "  <th style='width:1%'>PID</th>";
    $h[] = "  <th style='width:1%'>{process}</th>";
    $h[] = "  <th>{command}</th>";
    $h[] = "</tr></thead><tbody>";

    $TRCLASS = null;
    foreach ($sockets as $s) {
        $TRCLASS=null;
        $h[] = "<tr class='$TRCLASS'>" . socket_cells($s, false) . "</tr>";
    }
    $h[] = "</tbody></table></div></div></div>";
    return implode("\n", $h);
}

function socket_cells(object $s, bool $showIface): string {
    $tpl=new template_admin();
    $proto     = $s->protocol ?? '';
    $port      = intval($s->local_port ?? 0);
    $addr      = htmlspecialchars($s->local_addr ?? '');
    $pid       = intval($s->pid ?? 0);
    $pname     = htmlspecialchars($s->process_name ?? '');
    $cmdline   = htmlspecialchars($s->cmdline ?? '');
    $cmdShort  = mb_strlen($cmdline) > 80 ? mb_substr($cmdline, 0, 80) . '...' : $cmdline;
    $iface     = $s->_iface ?? '*';

    // Security indicators
    $warning = '';
    $id=md5(serialize($s));
    if ($iface === '*' && $port < 1024) {
        list($tooltip,$none)=$tpl->Tooltips($id,"{exposed_service}");
        $warning = " <i class='fas fa-exclamation-triangle text-warning' id='$id' $tooltip></i>";
    } elseif ($iface === '*') {
        list($tooltip,$none)=$tpl->Tooltips($id,"{accessible_all_networks}");
        $warning = " <i class='fas fa-info-circle text-muted' id='$id' $tooltip></i>";
    }

    $pidCell = $pid > 0
        ? "<span style='font-family:monospace'>$pid</span>"
        : "<span style='color:#999;font-style:italic'>&mdash;</span>";

    $pnameCell = $pname !== ''
        ? "<strong>$pname</strong>"
        : "<span style='color:#999;font-style:italic'>{unknown_process}</span>";

    $c = [];
    if ($showIface) {
        $c[] = "<td nowrap><i class='" . iface_icon($iface) . "'></i> " . iface_display($iface) . "</td>";
    }
    $c[] = "<td nowrap>" . proto_badge($proto) . "</td>";
    $c[] = "<td nowrap><strong style='font-size:14px'>$port</strong>$warning</td>";
    $c[] = "<td nowrap><span style='font-family:monospace'>$addr</span></td>";
    $c[] = "<td nowrap>$pidCell</td>";
    $c[] = "<td nowrap>$pnameCell</td>";
    $c[] = "<td><span style='font-size:11px' title='$cmdline'>$cmdShort</span></td>";
    return implode("\n", $c);
}
