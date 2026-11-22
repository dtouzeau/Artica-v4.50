<?php
include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"] = new sockets();
}
include_once(dirname(__FILE__) . "/ressources/class.statistics_appliance.inc");
// parseURL(): family site and the category link of the INFO/LINK column.
include_once(dirname(__FILE__) . "/ressources/class.logfile_daemon.inc");
$users = new usersMenus();
if (!$users->AsProxyMonitor) {
    exit();
}
if (isset($_GET["verbose"])) {
    $GLOBALS["VERBOSE"] = true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}
if (isset($_GET["build-page"])) {
    build_page();
    exit;
}
if (isset($_GET["search"])) {
    search();
    exit;
}
if (isset($_GET["zoom-js"])) {
    zoom_js();
    exit;
}
if (isset($_GET["zoom"])) {
    zoom();
    exit;
}

page();

// "&logfile=..." when the page reads another log than the default one.
function haproxy_tail_logfile(): string{
    $f = (string)($_GET["logfile"] ?? "");
    return $f == "" ? "" : "&logfile=" . urlencode($f);
}

function page(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $html = $tpl->page_header("{realtime_requests}", "fas fa-list-ul", "{haproxy_realtime_requests_explain}",
        "$page?build-page=yes" . haproxy_tail_logfile(), null, "progress-haproxy-tail", false, "table-haproxy-tail");
    if (isset($_GET["main-page"])) {
        $tpl = new template_admin("{realtime_requests}", $html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

function build_page(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $html[] = "<div style='margin-top:10px'>";
    $html[] = $tpl->search_block($page, "", "", "", haproxy_tail_logfile());
    $html[] = "</div>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

// Plain-text label of a HAProxy termination state (%ts), "" when unreadable.
function haproxy_termination_label($flags): string{
    $flags = (string)$flags;
    if (strlen($flags) < 2) {
        return "";
    }
    $pair = substr($flags, 0, 2);
    $COMMON = array(
        "--" => "Normal termination",
        "CC" => "Client aborted before the connection was established",
        "CD" => "Client aborted during data transfer",
        "cD" => "Client timeout, no data exchanged",
        "CH" => "Client aborted while waiting for the server response",
        "cH" => "Client timeout while sending the request body",
        "CQ" => "Client aborted while queued",
        "CR" => "Client aborted before sending a complete request",
        "cR" => "Client timeout before a complete request",
        "CT" => "Client aborted while tarpitted",
        "LR" => "Request handled locally by HAProxy",
        "SC" => "Server refused the TCP connection",
        "sC" => "Connection timeout to the server",
        "SD" => "Server connection died during data transfer",
        "sD" => "Server timeout, no data exchanged",
        "SH" => "Server aborted before sending response headers",
        "sH" => "Server timeout waiting for response headers",
        "PC" => "Proxy refused the connection, limit reached",
        "PD" => "Proxy blocked a malformed chunked message",
        "PH" => "Proxy blocked an invalid server response",
        "PR" => "Proxy blocked the request",
        "PT" => "Proxy tarpitted the request",
        "RC" => "A proxy resource was exhausted",
        "DC" => "Session killed, server detected down",
        "IC" => "Internal proxy error",
    );
    if (isset($COMMON[$pair])) {
        return $COMMON[$pair];
    }
    $CAUSE = array("C" => "Client aborted", "S" => "Server aborted or refused",
        "P" => "Blocked by the proxy", "L" => "Handled locally by HAProxy",
        "R" => "Proxy resource exhausted", "I" => "Internal proxy error",
        "D" => "Killed, server down", "U" => "Killed, backup released",
        "K" => "Killed by an administrator", "c" => "Client timeout",
        "s" => "Server timeout", "-" => "Normal");
    $PHASE = array("R" => "waiting for the request", "Q" => "queued",
        "C" => "connecting to the server", "H" => "waiting for response headers",
        "D" => "data transfer", "L" => "sending the last data",
        "T" => "tarpitted", "-" => "completed");
    $c1 = substr($pair, 0, 1);
    $c2 = substr($pair, 1, 1);
    return ($CAUSE[$c1] ?? "State $c1") . " (" . ($PHASE[$c2] ?? "phase $c2") . ")";
}

// Termination state read back from a raw HAProxy log line, when the daemon row does
// not carry it. The syslog header before "haproxy[<pid>]:" has no fixed length, so the
// fields are counted from that marker. Two formats only differ by the timers field:
//   TCP  : client:port [date] frontend backend/server Tw/Tc/Tt bytes <ts:2> ...
//   HTTP : client:port [date] frontend backend/server TR/Tw/Tc/Tr/Ta code bytes cookie cookie <ts:4> ...
// HASTATS lines use ":::" separators, labelled ("termination_state_with_cookie_status=")
// or positional (field 14).
function haproxy_tail_raw_termination($line): string{
    if (!preg_match('#haproxy\[[0-9]+\]:\s*(.*)$#', (string)$line, $re)) {
        return "";
    }
    $msg = trim($re[1]);
    if (substr($msg, 0, 7) == "HASTATS") {
        $P = explode(":::", $msg);
        $term = "";
        foreach ($P as $v) {
            if (preg_match('#^termination_state_with_cookie_status=(.*)$#s', $v, $m)) {
                $term = $m[1];
            }
        }
        if ($term == "") {
            $term = $P[14] ?? "";
        }
        $term = trim($term);
        return preg_match('#^[-A-Za-z]{2,4}$#', $term) ? $term : "";
    }
    $F = preg_split("/[\s]+/", $msg);
    if (count($F) < 7) {
        return "";
    }
    if (count(explode("/", $F[4])) >= 5) {
        return (isset($F[9]) && preg_match('#^[-A-Za-z]{4}$#', $F[9])) ? $F[9] : "";
    }
    return preg_match('#^[-A-Za-z]{2}$#', $F[6]) ? $F[6] : "";
}

// First non-empty property among $names: the daemon row shape is not fixed here.
function haproxy_tail_field($e, $names): string{
    foreach ($names as $n) {
        if (isset($e->$n) && (string)$e->$n !== "") {
            return (string)$e->$n;
        }
    }
    return "";
}

// HTTP status as a badge: green for 2xx, amber for 3xx, orange for a 307, red from 400.
function haproxy_tail_code(int $code): string{
    if ($code == 0) {
        return "<span class='awc-muted'>-</span>";
    }
    $class = "hpt-ok";
    if ($code >= 300) {
        $class = "hpt-redir";
    }
    if ($code == 307) {
        $class = "hpt-warn";
    }
    if ($code >= 400) {
        $class = "hpt-err";
    }
    return "<span class='awc-badge $class'>$code</span>";
}

// The termination state IS the status of a TCP session: flag, then its meaning.
// "--" is a normal end, a first letter P means the Load-Balancer cut it itself.
function haproxy_tail_termination($flags): string{
    $pair = trim((string)substr((string)$flags, 0, 2));
    if ($pair == "") {
        return "<span class='awc-muted'>-</span>";
    }
    $class = "hpt-err";
    if ($pair == "--") {
        $class = "hpt-ok";
    } elseif (substr($pair, 0, 1) == "P") {
        $class = "hpt-block";
    }
    $h = "<span class='awc-badge $class' style='font-family:monospace'>" . htmlspecialchars($pair) . "</span>";
    $label = haproxy_termination_label($flags);
    if ($label != "") {
        $h .= "<div class='awc-meta' style='white-space:normal;max-width:180px'>" . htmlspecialchars($label) . "</div>";
    }
    return $h;
}

function haproxy_tail_duration(int $ms): string{
    if ($ms <= 0) {
        return "<span class='awc-muted'>-</span>";
    }
    if ($ms < 1000) {
        return "{$ms}ms";
    }
    return round($ms / 1000, 1) . "s";
}

function haproxy_tail_style(): string{
    return "<style>
.hpt-ok{background:#26a69a}
.hpt-redir{background:#865300}
.hpt-warn{background:#F59C44}
.hpt-err{background:#ba1a1a}
.hpt-block{background:#A01E1E}
.hpt-url{word-break:break-all}
.hpt-row-err .awc-name a,.hpt-row-err .awc-name{color:#ba1a1a}
.hpt-row-block .awc-name a,.hpt-row-block .awc-name{color:#A01E1E}
.hpt-cmd{font-size:10px;color:#6c7b6d;margin:6px 2px;word-break:break-all}
</style>";
}

function zoom_js(){
    header("content-type: application/x-javascript");
    $tpl = new template_admin();
    $page = CurrentPageName();
    $data = urlencode((string)$_GET["data"]);
    $tpl->js_dialog($tpl->_ENGINE_parse_body("{realtime_requests}::ZOOM"), "$page?zoom=yes&data=$data");
}

// Detail of one request, key on the left, value in bold. Values come from the raw
// log, so they are escaped; the raw line reads in a fixed-width font.
function zoom(){
    $tpl = new template_admin();
    $data = unserializeb64((string)$_GET["data"]);
    $html[] = "<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[] = "<table class='awc-tbl awc-tbl-compact'><tbody>";
    // Pentest 2026-11-04 : url/valeurs du journal (donnee externe) affiche sans encodage (XSS stockee).
    foreach ($data as $key => $val) {
        $val = req_html($val);
        // An URL longer than 80 characters is cut, and opens; a CONNECT host:port is not an URL.
        if ($key == "{url}" && preg_match("#^(http|ftp)s?://#", (string)$data[$key])) {
            $url = (string)$data[$key];
            $text = (strlen($url) > 80) ? substr($url, 0, 77) . "..." : $url;
            $js = req_js($url);
            $val = $tpl->td_href(req_html($text), req_html($url), "s_PopUp('$js',1024,768,'');");
        }
        if ($key == "Raw") {
            $val = "<span style='font-family:monospace;font-size:12px;word-break:break-all'>$val</span>";
        }
        $html[] = "<tr><td class='awc-kv-k'>".req_html($key)."</td><td><span class='awc-name'>$val</span></td></tr>";
    }
    $html[] = "</tbody></table></div></div></div>";
    // The stylesheet goes AFTER the parse: _ENGINE_parse_body would read CSS bodies as {tokens}.
    echo awc_table_style() . $tpl->_ENGINE_parse_body(@implode("", $html));
}

function search(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $t = time();
    $function = preg_replace('/[^A-Za-z0-9_]/', '', (string)($_GET["function"] ?? ""));
    // The search block sends an empty string on its first call: without a fallback
    // format_search_protocol() sets no line limit.
    $query = trim((string)($_GET["search"] ?? ""));
    if ($query == "") {
        $query = "50 events";
    }
    $MAIN = $tpl->format_search_protocol($query);
    $SearchString = (string)($_GET["SearchString"] ?? "");
    $FinderList = (string)($_GET["FinderList"] ?? "");

    // The daemon runs the search and decodes each line (HaProxy.AccessRealRows),
    // newest first: the page only renders.
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/haproxy/access-real?rp=" . intval($MAIN["MAX"]) .
        "&query=" . urlencode((string)$MAIN["TERM"]) . "&SearchString=" . urlencode($SearchString) .
        "&FinderList=" . urlencode($FinderList) . haproxy_tail_logfile()));
    $error = "";
    $events = array();
    if (!is_object($json)) {
        $error = $GLOBALS["CLASS_SOCKETS"]->mysql_error;
    } elseif (!$json->Status) {
        $error = (string)$json->Error;
    } elseif (is_array($json->Rows)) {
        $events = $json->Rows;
    }

    $logfileD = new logfile_daemon();
    $rows = array();
    foreach ($events as $e) {
        $code = intval($e->code);
        $epoch = intval($e->epoch);
        if ($epoch > 0) {
            $clock = date("H:i:s", $epoch);
            $when = $tpl->time_to_date($epoch);
        } else {
            // A message: the syslog stamp as written ("Oct 25 18:30:00").
            $parts = preg_split("/\s+/", trim((string)$e->date));
            $clock = (string)array_pop($parts);
            $when = @implode(" ", $parts);
        }
        $client = $e->client == "" ? "<span class='awc-muted'>-</span>" : htmlspecialchars((string)$e->client);
        $method = (string)$e->method;
        $url = (string)$e->url;
        $raw = haproxy_tail_field($e, array("raw", "line"));
        $term = haproxy_tail_field($e, array("termination", "ts", "termination_state"));
        if ($term == "" && $raw != "") {
            $term = haproxy_tail_raw_termination($raw);
        }

        $proto = (string)$e->proto;
        $status = haproxy_tail_code($code);
        $rowclass = "";
        $link = "";
        if ($e->message != "") {
            $what = "<span class='awc-name'>" . htmlspecialchars((string)$e->message) . "</span>";
        } elseif ($url != "") {
            $label = $e->site != "" ? (string)$e->site : $url;
            // A CONNECT carries host:port, not an URL: a popup on it would open an invalid address.
            if ($method == "CONNECT") {
                $what = "<span class='awc-name'><i class='fas fa-lock' style='opacity:.45'></i>&nbsp;" . htmlspecialchars($label) . "</span>";
            } else {
                $popup = htmlspecialchars("s_PopUp(" . json_encode($url) . ",1024,768)", ENT_QUOTES);
                $what = "<span class='awc-name'><a href=\"javascript:blur();\" onclick=\"$popup\">" . htmlspecialchars($label) . "</a></span>";
                // parseURL() only feeds the category link of the INFO/LINK column.
                $MAINUTI = $logfileD->parseURL($url, false, $method, true);
                if (is_array($MAINUTI) && isset($MAINUTI["LINK"])) {
                    $link = (string)$MAINUTI["LINK"];
                }
            }
            $what .= "<div class='awc-meta hpt-url'>" . htmlspecialchars($method . " " . $url) . "</div>";
            if ($code >= 400) {
                $rowclass = "hpt-row-err";
            }
        } else {
            // A TCP session has no URL: its termination state takes the Status column.
            $what = "<span class='awc-name'>TCP</span>";
            $proto = "TCP";
            $status = haproxy_tail_termination($term);
            $pair = substr($term, 0, 2);
            if ($pair != "" && $pair != "--") {
                $rowclass = substr($pair, 0, 1) == "P" ? "hpt-row-block" : "hpt-row-err";
            }
        }
        $destination = $e->service == "" ? "<span class='awc-muted'>-</span>" : htmlspecialchars((string)$e->service);
        if ($e->server != "") {
            $destination .= "<div class='awc-meta'>" . htmlspecialchars((string)$e->server) . "</div>";
        }
        $bytes = intval($e->bytes);
        $size = $bytes > 0 ? ($bytes > 1024 ? FormatBytes($bytes / 1024) : "$bytes Bytes") : "<span class='awc-muted'>-</span>";

        // Detail window: zoom() renders this array key by key; the raw line is joined,
        // it is the one to read again when the decoding surprises.
        $Z = array();
        $Z["{zDate}"] = trim("$when $clock");
        $Z["{client}"] = (string)$e->client;
        $Z["{servicename}"] = (string)$e->service;
        $Z["{backend}"] = (string)$e->server;
        $Z["{mode}"] = $e->message != "" ? "Service" : ($url != "" ? "HTTP" : "TCP");
        if ($e->message != "") {
            $Z["{message}"] = (string)$e->message;
        }
        if ($url != "") {
            $Z["{method}"] = $method;
            $Z["{url}"] = $url;
            $Z["{http_status_code}"] = $code;
        }
        $Z["{size}"] = "$bytes bytes";
        $Z["{duration}"] = intval($e->latency) . " ms";
        if ($term != "") {
            $Z["Termination"] = $term . " - " . haproxy_termination_label($term);
        }
        if ($raw != "") {
            $Z["Raw"] = $raw;
        }
        $info = $tpl->icon_loupe(1, "Loadjs('$page?zoom-js=yes&data=" . urlencode(base64_encode(serialize($Z))) . "')");
        if ($link != "") {
            $info .= "&nbsp;&nbsp;$link";
        }

        $r = array();
        $r[] = "<tr" . ($rowclass != "" ? " class='$rowclass'" : "") . ">";
        $r[] = "<td style='width:1%' nowrap><div class='awc-seen'>" . htmlspecialchars($clock) . "</div><div class='awc-seen-sub'>" . htmlspecialchars($when) . "</div></td>";
        $r[] = "<td class='awc-mono' style='width:1%' nowrap>$client</td>";
        $r[] = "<td class='center' style='width:1%' nowrap>$status</td>";
        $r[] = "<td style='width:1%' nowrap><span class='awc-tag'>" . htmlspecialchars($proto) . "</span></td>";
        $r[] = "<td style='width:99%'>$what</td>";
        $r[] = "<td class='awc-mono' style='width:1%' nowrap>$destination</td>";
        $r[] = "<td class='awc-mono' style='width:1%' nowrap>$size</td>";
        $r[] = "<td class='awc-mono' style='width:1%' nowrap>" . haproxy_tail_duration(intval($e->latency)) . "</td>";
        $r[] = "<td class='center' style='width:1%' nowrap>$info</td>";
        $r[] = "</tr>";
        $rows[] = @implode("", $r);
    }

    $html[] = "<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[] = "<table class='awc-tbl'><thead><tr>";
    $html[] = "<th>{zDate}</th><th>{members}</th><th class='center'>{status}</th><th>{proto}</th>";
    $html[] = "<th>{url}</th><th>{destinations}</th><th>{size}</th><th>{duration}</th><th class='center'>INFO/LINK</th>";
    $html[] = "</tr></thead><tbody id='awc-rows-$t'>";
    if ($error != "") {
        $html[] = "<tr><td colspan='9'><div class='awc-empty'>" . htmlspecialchars($error) . "</div></td></tr>";
    } elseif (count($rows) == 0) {
        $html[] = "<tr><td colspan='9'><div class='awc-empty'>{no_data}</div></td></tr>";
    }
    // Rows carry log content (client URLs): kept out of _ENGINE_parse_body, which
    // would read any {…} in a URL as a translation token.
    $foot[] = "</tbody></table></div>";
    $foot[] = "<div class='awc-foot'>";
    $foot[] = "<span>" . count($rows) . " {items}</span>";
    $foot[] = "<span class='awc-pg' id='awc-pager-$t'></span>";
    $foot[] = "</div></div></div>";
    // The command the search ran, when the daemon leaves it behind.
    $cmd = trim((string)@file_get_contents("/usr/share/artica-postfix/ressources/logs/haproxy.log.cmd"));
    $cmdline = $cmd != "" ? "<div class='hpt-cmd'>" . htmlspecialchars($cmd) . "</div>" : "";

    $topbuttons = array();
    $topbuttons[] = array("$function()", ico_refresh, "{refresh}");
    $TINY = array();
    $TINY["TITLE"] = "{realtime_requests}";
    $TINY["ICO"] = "fas fa-list-ul";
    $TINY["EXPL"] = "{haproxy_realtime_requests_explain}";
    $TINY["BUTTONS"] = $tpl->table_buttons($topbuttons);
    $tiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY))) . "');";

    $script = "<script>NoSpinner();\n" . $tpl->ICON_SCRIPTS_COMPILE() . "\n$tiny</script>";
    echo awc_table_style() . haproxy_tail_style() . $tpl->_ENGINE_parse_body(@implode("\n", $html)) . @implode("\n", $rows) .
        $tpl->_ENGINE_parse_body(@implode("\n", $foot)) . $cmdline . $script .
        awc_pager_js($GLOBALS["FOOTABLE_PSIZE"], "awc-rows-$t", "awc-pager-$t");
}
