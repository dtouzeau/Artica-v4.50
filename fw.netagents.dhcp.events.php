<?php
/**
 * DHCP events viewer for a single remote agent.
 * Fetches the last hour of isc-dhcp3 journal lines via articarest,
 * parses them server-side, and renders a filterable event table.
 *
 * Entry:  LoadAjax('div','fw.netagents.dhcp.events.php?id={agent_id}')
 * Handlers:
 *   ?events-js={id}  — JavaScript that builds/refreshes the table (Loadjs)
 * API:    GET /netagents/dhcp/{id}/journalctl/lasthour
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }

if (isset($_GET["search"])) { dhcp_search(); exit; }
if (isset($_GET["events-js"])) { dhcp_events_js(); exit; }
dhcp_start();

// ── Helpers ───────────────────────────────────────────────────────────────────

function aid(): int {
    return intval($_GET["id"] ?? $_GET["events-js"] ?? 0);
}

/**
 * Parse a single journalctl line into structured fields.
 * Format (systemd default):
 *   "Mar 06 14:23:45 hostname dhcpd[1234]: DHCPREQUEST for 192.168.1.100 from aa:bb:cc:dd:ee:ff via eth0"
 *
 * Returns ['ts'=>'', 'type'=>'', 'detail'=>'', 'raw'=>''] or null to skip.
 */
function parse_dhcp_line(string $line): ?array {
    $line = trim($line);
    if ($line === '') return null;

    // Extract timestamp (first 3 tokens: "Mar 06 14:23:45")
    $ts = '';
    if (preg_match('/^(\w{3}\s+\d{1,2}\s+\d{2}:\d{2}:\d{2})/', $line, $m)) {
        $ts = $m[1];
    }

    // Find dhcpd message after "dhcpd[N]:" prefix
    $msg = '';
    if (preg_match('/dhcpd\[\d+\]:\s*(.+)$/', $line, $m)) {
        $msg = trim($m[1]);
    } else {
        // Not a dhcpd line (kernel, systemd lifecycle, etc.) — show as-is
        return ['ts' => $ts, 'type' => '', 'detail' => $line, 'raw' => $line];
    }

    // Determine DHCP message type from start of message
    $type = '';
    foreach (['DHCPACK','DHCPNAK','DHCPOFFER','DHCPREQUEST','DHCPDISCOVER',
               'DHCPRELEASE','DHCPINFORM','DHCPDECLINE','BOOTREPLY','BOOTREQUEST'] as $t) {
        if (str_starts_with($msg, $t)) { $type = $t; break; }
    }

    return ['ts' => $ts, 'type' => $type, 'detail' => $msg, 'raw' => $line];
}

/**
 * Return an HTML badge for a DHCP message type.
 */
function event_badge(string $type): string {
    $colors = [
        'DHCPACK'      => '#27ae60',
        'DHCPNAK'      => '#c0392b',
        'DHCPOFFER'    => '#2980b9',
        'DHCPREQUEST'  => '#e67e22',
        'DHCPDISCOVER' => '#7f8c8d',
        'DHCPRELEASE'  => '#8e44ad',
        'DHCPINFORM'   => '#16a085',
        'DHCPDECLINE'  => '#c0392b',
        'BOOTREPLY'    => '#2980b9',
        'BOOTREQUEST'  => '#7f8c8d',
    ];
    if ($type === '') return '';
    $color = $colors[$type] ?? '#555';
    return "<span class='badge' style='background:$color;color:#fff;font-size:11px'>$type</span>";
}

// ── Main page ─────────────────────────────────────────────────────────────────

function dhcp_start():bool{
    $tpl  = new template_admin();
    $id   = aid();
    $page = CurrentPageName();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,"","","","&id=$id");
    echo "</div>";
    return true;
}

function dhcp_search(): bool {
    $tpl  = new template_admin();
    $id   = aid();
    $search=$_GET["search"];
    $search=str_replace("*",".*?",$search);
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dhcp/$id/journalctl/lasthour"));

    if (!is_object($json) || (isset($json->Status) && !$json->Status)) {
        $err  = is_object($json) ? ($json->Error ?? 'unknown error') : 'no response';
        $html = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> "
              . htmlspecialchars($err) . "</div>";
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }

    $rawLines = (array)($json->lines ?? []);

    $events = [];
    foreach ($rawLines as $line) {
        if(strlen($search)>2){
            if(!preg_match("#$search#i",$line)){
                continue;
            }
        }

        $ev = parse_dhcp_line((string)$line);
        if ($ev !== null) $events[] = $ev;
    }
    $events = array_reverse($events);

    if (empty($events)) {
        $html = "<p class='text-muted text-center' style='padding:20px'>No DHCP events in the last hour</p>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    $rows = '';
    foreach ($events as $ev) {
        $ts     = htmlspecialchars($ev['ts']);
        $badge  = event_badge($ev['type']);
        $detail = htmlspecialchars($ev['detail']);
        $dtype  = htmlspecialchars($ev['type']);
        $strlen=strlen(trim($detail));
        if($strlen<7){continue;}
        $rows  .= "<tr data-type=\"$dtype\">";
        $rows  .= "<td style='white-space:nowrap;color:#888;font-size:12px;width:120px'>$ts</td>";
        $rows  .= "<td style='width:130px'>$badge</td>";
        $rows  .= "<td style='font-size:12px;font-family:monospace;word-break:break-all'>$detail</td>";
        $rows  .= "</tr>";
    }

    $total = count($events);
    $table  = "<div style='margin-bottom:6px'><small class='text-muted'>$total events (last hour, newest first)</small></div>";
    $table .= "<table class='table table-condensed table-hover' style='margin:0'>";
    $table .= "<thead><tr>";
    $table .= "<th style='width:120px'>Time</th>";
    $table .= "<th style='width:130px'>Type</th>";
    $table .= "<th>Detail</th>";
    $table .= "</tr></thead>";
    $table .= "<tbody>$rows</tbody></table>";

    echo $tpl->_ENGINE_parse_body($table);
    return true;
}

// ── JS handler: fetch + render table ─────────────────────────────────────────


