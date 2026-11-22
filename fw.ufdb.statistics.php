<?php
/**
 * Web filtering statistics (ufdbGuard) — what the web filter blocked, for whom, and why.
 * Data: articarest /ufdb/stats/* (BoltDB aggregates of EventsReceiver), /ufdb/events/live,
 * /ufdb/stats/db (history storage size).
 *
 * Layout (Stitch mock-up "web_filtering_analytics_console", 2026-11-18):
 *   page_header + TINY (period segment, refresh)
 *   build-tabs: value banner, verdict sentence, four key figures, tabs, storage footer
 *   tabs: Overview, Users, Kinds of websites, Filtering rules, Activity right now, Cross-analysis
 * No Chart.js: every figure is a ranked list whose bar is measured against a real total
 * of the same period (blocked requests or all requests).
 */
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}

$users = new usersMenus();
if (!$users->AsDansGuardianAdministrator) { exit(); }

if(isset($_GET["build-tabs"]))        { ufst_tabs(); exit; }
if(isset($_GET["dashboard"]))         { ufst_overview(); exit; }
if(isset($_GET["users"]))             { ufst_users(); exit; }
if(isset($_GET["user-drill"]))        { ufst_user_drill_js(); exit; }
if(isset($_GET["user-drill-content"])){ ufst_user_drill_content(); exit; }
if(isset($_GET["categories"]))        { ufst_categories(); exit; }
if(isset($_GET["rules"]))             { ufst_rules(); exit; }
if(isset($_GET["realtime"]))          { ufst_realtime(); exit; }
if(isset($_GET["analysis"]))          { ufst_analysis_form(); exit; }
if(isset($_GET["analysis-data"]))     { ufst_analysis_data(); exit; }
page();

// ── Helpers ──────────────────────────────────────────────────────────────────

// Periods the daemon really knows (EventsReceiver rangeToBucket). Anything else
// silently falls back to 24h there, so it is never offered here.
function ufst_ranges(): array {
    return array("1h"=>"1h","6h"=>"6h","24h"=>"24h","7d"=>"7d","30d"=>"30d","90d"=>"90d");
}

function ufst_range(): string {
    $r = isset($_GET["range"]) ? (string)$_GET["range"] : "24h";
    $valid = ufst_ranges();
    return isset($valid[$r]) ? $r : "24h";
}

// ufst_t — translate a token, then fill %1, %2… with already-escaped values.
// Placeholders are substituted AFTER the parse: the token must match first.
function ufst_t(string $token, array $args = array()): string {
    $tpl = new template_admin();
    $s = $tpl->_ENGINE_parse_body("{" . $token . "}");
    $s = preg_replace('#<br\s*/?>\s*$#i', '', $s);
    for ($i = count($args); $i >= 1; $i--) {
        $s = str_replace("%$i", (string)$args[$i - 1], $s);
    }
    return $s;
}

// ufst_query — one REST call, normalised: ok / siem / error / groups / total / tier.
function ufst_query(string $path): array {
    $out = array("ok"=>false, "siem"=>false, "error"=>"", "groups"=>array(), "total"=>0, "tier"=>"");
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($path));
    if (!is_object($json)) {
        $out["error"] = "{ufst_daemon_unreachable}";
        return $out;
    }
    if (empty($json->Status)) {
        $err = isset($json->Error) ? (string)$json->Error : "";
        if ($err === "siem-active") { $out["siem"] = true; return $out; }
        $out["error"] = ($err === "") ? "{ufst_daemon_unreachable}" : $err;
        return $out;
    }
    $out["ok"] = true;
    if (isset($json->Groups) && is_array($json->Groups)) { $out["groups"] = $json->Groups; }
    // Total = number of distinct groups BEFORE the top-N cut (EventsReceiver QueryStats).
    $out["total"] = isset($json->Total) ? intval($json->Total) : count($out["groups"]);
    $out["tier"]  = isset($json->Tier) ? (string)$json->Tier : "";
    return $out;
}

// ufst_summary — counts per action (BLOCK / PASS / REDIR) for the period, optionally for one user.
function ufst_summary(string $range, string $user = ""): array {
    $path = "/ufdb/stats/summary?range=$range";
    if ($user !== "") { $path .= "&filter_username=" . urlencode($user); }
    $q = ufst_query($path);
    $s = array("ok"=>$q["ok"], "siem"=>$q["siem"], "error"=>$q["error"], "tier"=>$q["tier"],
               "blocks"=>0, "passes"=>0, "redirs"=>0, "total"=>0);
    foreach ($q["groups"] as $g) {
        $action = isset($g->keys->action) ? (string)$g->keys->action : "";
        $c = intval($g->count);
        if ($action === "BLOCK") { $s["blocks"] += $c; }
        elseif ($action === "PASS") { $s["passes"] += $c; }
        elseif ($action === "REDIR") { $s["redirs"] += $c; }
    }
    $s["total"] = $s["blocks"] + $s["passes"] + $s["redirs"];
    return $s;
}

function ufst_category_name(string $cat): string {
    if (!preg_match('/^P(\d+)$/', $cat, $m)) { return $cat; }
    if (!isset($GLOBALS["UFST_CATZ"])) {
        include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
        $GLOBALS["UFST_CATZ"] = new mysql_catz();
        $GLOBALS["UFST_CATNAMES"] = array();
    }
    $id = intval($m[1]);
    if (!isset($GLOBALS["UFST_CATNAMES"][$id])) {
        $name = $GLOBALS["UFST_CATZ"]->CategoryIntToStr($id);
        $GLOBALS["UFST_CATNAMES"][$id] = ($name === null || $name === "") ? $cat : (string)$name;
    }
    return $GLOBALS["UFST_CATNAMES"][$id];
}

function ufst_pct(int $part, int $total): string {
    if ($total <= 0) { return "0"; }
    $p = ($part / $total) * 100;
    if ($p > 0 && $p < 0.1) { return "&lt;0.1"; }
    return sprintf('%.1f', $p);
}

function ufst_width(int $part, int $total): string {
    if ($total <= 0) { return "0"; }
    return sprintf('%.2f', min(100, ($part / $total) * 100));
}

// Block-rate level: same thresholds as before (20 % / 10 %), now also said in words.
function ufst_rate_level(int $blk, int $tot): array {
    if ($tot <= 0) { return array("ak-off", "", ""); }
    $p = ($blk / $tot) * 100;
    if ($p >= 20) { return array("ak-err", "fas fa-circle-exclamation", "{ufst_level_high}"); }
    if ($p >= 10) { return array("ak-warn", "fas fa-triangle-exclamation", "{ufst_level_moderate}"); }
    return array("ak-ok", "fas fa-circle-check", "{ufst_level_low}");
}

function ufst_rate_pill(int $blk, int $tot, bool $withPct = true): string {
    list($tone, $ico, $word) = ufst_rate_level($blk, $tot);
    if ($word === "") { return "<span class='ak-pill ak-off'>—</span>"; }
    $pct = $withPct ? ufst_pct($blk, $tot) . "&nbsp;% · " : "";
    return "<span class='ak-pill $tone'><i class='$ico'></i> $pct$word</span>";
}

function ufst_style(): string {
    if (!empty($GLOBALS["UFST_CSS_DONE"])) { return ""; }
    $GLOBALS["UFST_CSS_DONE"] = true;
    $accent = "#10b981"; $soft = "rgba(16,185,129,.12)";
    if (function_exists("brand_ui_accents") && function_exists("brand_palette")) {
        $a = brand_ui_accents(brand_palette());
        if (!empty($a["accent"]))      { $accent = $a["accent"]; }
        if (!empty($a["accent_soft"])) { $soft = $a["accent_soft"]; }
    }
    return "<style>
.ufst{margin-top:14px}
.ufst-hero{display:flex;gap:20px;align-items:flex-start;padding:24px;border:1px solid #e2e8f0;border-radius:12px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.ufst-hero-t{font-size:20px;font-weight:700;color:#111d23;margin:0 0 6px 0;line-height:1.3}
.ufst-hero-p{font-size:14px;color:#3d4947;line-height:1.55;margin:0 0 14px 0;max-width:980px}
.ufst-hero .ak-ico{background:$soft}
.ufst-hero .ak-tag{border-radius:999px;padding:5px 12px}
.ufst-verdict{margin:22px 4px 18px 4px}
.ufst-verdict-m{font-size:16px;font-weight:600;color:#111d23;margin:0}
.ufst-verdict-s{font-size:13px;color:#6d7a77;margin:3px 0 0 0}
.ufst-kpis{margin-bottom:22px}
.ufst-kpis .ak-fig{min-height:112px;align-items:flex-start}
.ufst-kpis .ak-ico{border-radius:10px}
.ufst-v-err{color:#ba1a1a!important}
.ufst-kpis .ak-pill{margin-top:2px;align-self:flex-start}
.ufst-row{display:grid;grid-template-columns:minmax(0,5fr) minmax(0,7fr);gap:24px}
@media(max-width:1200px){.ufst-row{grid-template-columns:minmax(0,1fr)}}
.ufst-card{display:flex;flex-direction:column;padding:20px}
.ufst-card-hd{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
.ufst-card-t{font-size:15px;font-weight:600;color:#111d23;margin:0}
.ufst-card-s{font-size:13px;color:#6d7a77;margin:2px 0 14px 0}
.ufst-chip{font-size:13px;font-weight:500;color:#6d7a77;background:#f1f5f9;border-radius:6px;padding:2px 8px;white-space:nowrap}
.ufst-card-f{margin-top:auto;padding-top:12px;border-top:1px solid #f1f5f9;text-align:right;font-size:13px;color:#6d7a77}
.ufst-card-f .ak-link{font-size:13px}
.ufst-list{width:100%;border-collapse:collapse}
.ufst-list th{font-size:13px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#475569;padding:8px 6px;border-bottom:1px solid #e2e8f0;text-align:left}
.ufst-list td{padding:9px 6px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.ufst-list tr:last-child td{border-bottom:0}
.ufst-list tbody tr:hover{background:#f8fafc}
.ufst-list .ufst-n{width:1%;color:#6d7a77;font-size:13px;font-variant-numeric:tabular-nums;white-space:nowrap;padding-right:12px}
.ufst-list .ufst-nm{max-width:0;width:100%}
.ufst-list .ufst-c{width:1%;white-space:nowrap;text-align:right;font-weight:500;color:#111d23;font-variant-numeric:tabular-nums}
.ufst-list th.ufst-c{font-weight:600;color:#475569}
.ufst-lbl{font-weight:700;color:#111d23;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ufst-lbl a{color:#111d23;text-decoration:none}
.ufst-lbl a:hover{color:#006a62;text-decoration:underline}
.ufst-sub{font-size:13px;color:#6d7a77}
.ufst-barline{display:flex;align-items:center;gap:10px;margin-top:5px}
.ufst-barline .ak-bar{flex:0 0 46%;max-width:240px}
.ufst-barline span.ufst-pc{font-size:13px;color:#6d7a77;font-variant-numeric:tabular-nums}
.ufst-split{display:flex;height:8px;border-radius:6px;overflow:hidden;background:#e2e8f0;min-width:120px}
.ufst-split>span{display:block;height:100%}
.ufst-split .ufst-b{background:#ba1a1a}
.ufst-split .ufst-p{background:$accent}
.ufst-legend{display:flex;gap:16px;font-size:13px;color:#3d4947;white-space:nowrap}
.ufst-legend i.ufst-sq{display:inline-block;width:10px;height:10px;border-radius:3px;margin-right:6px;vertical-align:-1px}
.ufst-legend .ufst-sq-b{background:#ba1a1a}
.ufst-legend .ufst-sq-p{background:$accent}
.ufst-wide td.ufst-name{max-width:0;width:34%}
.ufst-wide .ufst-barcell{width:26%}
.ufst-wide td.ak-num,.ufst-wide th.ak-num{white-space:nowrap}
.ufst-foot{display:flex;flex-wrap:wrap;justify-content:space-between;gap:16px;margin-top:28px;padding-top:14px;border-top:1px solid #e2e8f0;font-size:13px;color:#6d7a77}
.ufst-foot strong{color:#3d4947;font-weight:600}
.ufst-form{display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap}
.ufst-form .ak-f{min-width:220px}
.ufst-tabs-host .nav-tabs{margin-bottom:18px}
</style>";
}

// Every fragment: the page sheet outside the parser, then the parsed HTML.
function ufst_echo(template_admin $tpl, array $h): void {
    echo ufst_style() . $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// The two non-nominal answers, each with its own message.
function ufst_unavailable(array $q): string {
    if (!empty($q["siem"])) {
        return "<div class='ak-banner ak-info'><i class='fas fa-circle-info'></i>"
             . "<div class='ak-banner-m'><strong>{ufst_siem_title}</strong><br>{ufst_siem_text}"
             . "<div style='margin-top:10px'><a class='ak-btn ak-btn-sm' href=\"javascript:blur();\" "
             . "OnClick=\"LoadAjaxSilent('MainContent','fw.siem.ufdb.php');\">"
             . "<i class='fas fa-arrow-right'></i> {ufst_siem_open}</a></div></div></div>";
    }
    return "<div class='ak-banner ak-err'><i class='fas fa-triangle-exclamation'></i>"
         . "<div class='ak-banner-m'><strong>{ufst_error_title}</strong><br>"
         . htmlspecialchars((string)$q["error"]) . "</div></div>";
}

// Empty period: an invitation to widen it, never a grey "No data".
function ufst_empty(string $range, string $title = "{ufst_empty_title}"): string {
    $longer = array();
    $order = array_keys(ufst_ranges());
    $pos = array_search($range, $order);
    if ($pos !== false) { $longer = array_slice($order, $pos + 1, 2); }
    $btns = "";
    foreach ($longer as $r) {
        $btns .= "<button type='button' class='ak-btn ak-btn-sm' OnClick=\"UfstPeriod('$r')\">"
               . "<i class='fas fa-clock-rotate-left'></i> {ufst_try} $r</button> ";
    }
    return "<div class='ak-card'><div class='ak-empty-c'><i class='fas fa-filter'></i>"
         . "<div><strong>$title</strong></div><div class='ak-note'>{ufst_empty_text}</div>"
         . ($btns === "" ? "" : "<div class='ak-actions' style='justify-content:center;margin-top:10px'>$btns</div>")
         . "</div></div>";
}

// Rows of a ranked list built from a top-N answer.
function ufst_top_rows(array $groups, string $key): array {
    $rows = array();
    foreach ($groups as $g) {
        $v = isset($g->keys->{$key}) ? (string)$g->keys->{$key} : "";
        $name = ($key === "category") ? ufst_category_name($v) : $v;
        $sub = "";
        if ($key === "category" && preg_match('/^P\d+$/', $name)) { $sub = "{ufst_unknown_code}"; }
        if ($name === "") { $name = "—"; }
        $rows[] = array("html"=>htmlspecialchars($name), "title"=>$name, "sub"=>$sub, "count"=>intval($g->count));
    }
    return $rows;
}

// A ranked-list card: header (title, chip), subtitle, rows (name over its share bar), footer.
function ufst_rank_card(string $title, string $sub, string $chip, array $rows, int $ceiling, string $col, string $foot): string {
    $h = array();
    $h[] = "<section class='ak-card ufst-card'>";
    $h[] = "<div class='ufst-card-hd'><h3 class='ufst-card-t'>$title</h3>"
         . ($chip === "" ? "" : "<span class='ufst-chip'>$chip</span>") . "</div>";
    $h[] = "<p class='ufst-card-s'>$sub</p>";
    if (count($rows) == 0) {
        $h[] = "<div class='ak-empty'>{ufst_nothing_blocked}</div>";
    } else {
        $h[] = "<table class='ufst-list'><thead><tr><th>#</th><th>$col</th><th class='ufst-c'>{ufst_blocked_requests}</th></tr></thead><tbody>";
        $i = 0;
        foreach ($rows as $r) {
            $i++;
            $c = intval($r["count"]);
            $h[] = "<tr><td class='ufst-n'>$i</td><td class='ufst-nm'>"
                 . "<div class='ufst-lbl' title=\"" . htmlspecialchars($r["title"]) . "\">" . $r["html"] . "</div>"
                 . ($r["sub"] === "" ? "" : "<div class='ufst-sub'>" . $r["sub"] . "</div>")
                 . "<div class='ufst-barline'><div class='ak-bar ak-err'><span style='width:" . ufst_width($c, $ceiling) . "%'></span></div>"
                 . "<span class='ufst-pc'>" . ufst_pct($c, $ceiling) . "&nbsp;%</span></div></td>"
                 . "<td class='ufst-c'>" . number_format($c) . "</td></tr>";
        }
        $h[] = "</tbody></table>";
    }
    if ($foot !== "") { $h[] = "<div class='ufst-card-f'>$foot</div>"; }
    $h[] = "</section>";
    return implode("\n", $h);
}

function ufst_chip_top(int $shown, int $total): string {
    if ($total > $shown) {
        return ufst_t("ufst_top_n_of", array(number_format($shown), number_format($total)));
    }
    return ufst_t("ufst_top_n", array(number_format($shown)));
}

// ── Main page ────────────────────────────────────────────────────────────────

function page(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $html = $tpl->page_header("{ufst_title}", ico_statistics, "{ufst_explain}",
        "$page?build-tabs=yes", "webfiltering-statistics", "progress-ufst", false, "ufst-main");
    if (isset($_GET["main-page"])) {
        $tpl = new template_admin("{ufst_title}", $html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

// ufst_model — the period figures shared by the banner, the verdict and the tiles.
function ufst_model(string $range): array {
    $m = ufst_summary($range);
    $m["range"] = $range;
    $m["topcat"] = "";
    if ($m["ok"] && $m["blocks"] > 0) {
        $q = ufst_query("/ufdb/stats/top/categories?range=$range&top=1&filter_action=BLOCK");
        if ($q["ok"] && count($q["groups"]) > 0) {
            $v = isset($q["groups"][0]->keys->category) ? (string)$q["groups"][0]->keys->category : "";
            $n = ufst_category_name($v);
            if (!preg_match('/^P\d+$/', $n)) { $m["topcat"] = $n; }
        }
    }
    $m["storage_mb"] = null;
    $db = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/stats/db"));
    if (is_object($db) && !empty($db->Status) && isset($db->FileSizeMB)) {
        $m["storage_mb"] = floatval($db->FileSizeMB);
    }
    return $m;
}

function ufst_tabs(): void {
    $tpl   = new template_admin();
    $page  = CurrentPageName();
    $range = ufst_range();
    $m     = ufst_model($range);
    $r     = "&range=$range";

    $h = array();
    $h[] = "<div class='ak ufst'>";
    $h[] = ufst_hero();
    if ($m["ok"]) {
        $h[] = ufst_verdict($m);
    } else {
        $h[] = "<div style='height:22px'></div>";
    }
    $h[] = ufst_kpis($m);
    $h[] = "</div>";

    $tabs = "";
    if (!$m["ok"] && empty($m["siem"])) {
        $h[] = "<div class='ak'>" . ufst_unavailable($m) . "</div>";
    } elseif (!empty($m["siem"])) {
        // SIEM mode: only the live counters stay local.
        $h[] = "<div class='ak' style='margin-bottom:18px'>" . ufst_unavailable($m) . "</div>";
        $array = array();
        $array["<i class='fas fa-bolt'></i> {ufst_tab_live}"] = "$page?realtime=yes";
        $tabs = $tpl->tabs_default($array);
    } elseif ($m["total"] == 0) {
        $h[] = "<div class='ak'>" . ufst_empty($range) . "</div>";
    } else {
        $array = array();
        $array["<i class='fas fa-chart-pie'></i> {overview}"]             = "$page?dashboard=yes$r";
        $array["<i class='fas fa-users'></i> {ufst_tab_users}"]           = "$page?users=yes$r";
        $array["<i class='fas fa-tags'></i> {ufst_tab_kinds}"]            = "$page?categories=yes$r";
        $array["<i class='fas fa-sliders'></i> {ufst_tab_frules}"]        = "$page?rules=yes$r";
        $array["<i class='fas fa-bolt'></i> {ufst_tab_live}"]             = "$page?realtime=yes";
        $array["<i class='fas fa-table-cells'></i> {ufst_tab_cross}"]     = "$page?analysis=yes$r";
        $tabs = $tpl->tabs_default($array);
    }

    $foot = ufst_footer($m);
    echo siem_tiny_filters_style() . ufst_style() . $tpl->_ENGINE_parse_body(implode("\n", $h))
       . "<div class='ufst-tabs-host'>" . $tabs . "</div>" . $tpl->_ENGINE_parse_body($foot);

    // TINY after the tabs: the period reloads build-tabs (banner, figures and all panes).
    $topbuttons = array();
    $topbuttons[] = array("UfstPeriod('$range')", ico_refresh, "{refresh}");

    $fields = array();
    $fields[] = array("TYPE"=>"segment", "ID"=>"ufst-period", "LABEL"=>"{period}",
        "VALUE"=>$range, "OPTIONS"=>ufst_ranges(), "JS"=>"UfstPeriod");

    $TINY = array();
    $TINY["TITLE"]   = "{ufst_title}";
    $TINY["ICO"]     = ico_statistics;
    $TINY["EXPL"]    = "{ufst_explain}";
    $TINY["URL"]     = "webfiltering-statistics";
    $TINY["BUTTONS"] = siem_tiny_filters($fields, $tpl->table_buttons($topbuttons));

    echo "<script>"
       . "function UfstPeriod(p){LoadAjax('ufst-main','$page?build-tabs=yes&range='+encodeURIComponent(p));}"
       . "function UfstTab(i){var a=$('#ufst-main ul.nav-tabs li a').eq(i);if(a.length){a.click();}}"
       . "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY))) . "');"
       . "</script>";
}

// ── Header widgets (above the tabs) ─────────────────────────────────────────

function ufst_hero(): string {
    return "<div class='ufst-hero'>"
         . "<span class='ak-ico ak-ico-lg ak-accent'><i class='fas fa-shield-halved'></i></span>"
         . "<div class='ak-grow'><div class='ufst-hero-t'>{ufst_hero_title}</div>"
         . "<p class='ufst-hero-p'>{ufst_hero_text}</p>"
         . "<div class='ak-chips'>"
         . "<span class='ak-tag ak-ok'><i class='fas fa-eye'></i> {ufst_chip_readonly}</span>"
         . "<span class='ak-tag ak-ok'><i class='fas fa-server'></i> {ufst_chip_local}</span>"
         . "<span class='ak-tag ak-ok'><i class='fas fa-calendar-days'></i> {ufst_chip_periods}</span>"
         . "</div></div></div>";
}

// "In the last 24 hours, 1 request in 8 was blocked — mostly Social networks."
function ufst_verdict(array $m): string {
    $period = ufst_t("ufst_period_" . $m["range"]);
    if ($m["total"] == 0) { return "<div style='height:22px'></div>"; }
    if ($m["blocks"] == 0) {
        $main = ufst_t("ufst_verdict_none", array($period));
    } else {
        $ratio = $m["total"] / $m["blocks"];
        if ($ratio >= 2) {
            $main = ufst_t("ufst_verdict_ratio", array($period, number_format(round($ratio))));
        } else {
            $main = ufst_t("ufst_verdict_pct", array($period, ufst_pct($m["blocks"], $m["total"])));
        }
        if ($m["topcat"] !== "") {
            $main .= " — " . ufst_t("ufst_verdict_mostly", array(htmlspecialchars($m["topcat"])));
        }
    }
    $sub = ufst_t("ufst_verdict_sub", array(number_format($m["total"])));
    return "<div class='ufst-verdict'><p class='ufst-verdict-m'>$main.</p><p class='ufst-verdict-s'>$sub</p></div>";
}

function ufst_kpi(string $label, string $value, string $sub, string $ico, string $tone, string $vclass = ""): string {
    return "<div class='ak-fig $tone'><div class='ak-fig-txt'><span class='ak-fig-l'>$label</span>"
         . "<span class='ak-fig-v $vclass'>$value</span>$sub</div>"
         . "<span class='ak-ico $tone'><i class='$ico'></i></span></div>";
}

function ufst_kpis(array $s): string {
    $ok = $s["ok"];
    $dash = "—";
    $total = $ok ? number_format($s["total"]) : $dash;
    $blk   = $ok ? number_format($s["blocks"]) : $dash;
    $pass  = $ok ? number_format($s["passes"]) : $dash;
    $rate  = ($ok && $s["total"] > 0) ? ufst_pct($s["blocks"], $s["total"]) . "&nbsp;%" : $dash;
    $subTotal = ($ok && $s["redirs"] > 0) ? number_format($s["redirs"]) . " {ufst_redirected}" : "{ufst_kpi_total_s}";
    $ratePill = ($ok && $s["total"] > 0) ? ufst_rate_pill($s["blocks"], $s["total"], false)
              : "<span class='ak-fig-s'>{ufst_kpi_rate_s}</span>";
    $h = array();
    $h[] = "<div class='ak-grid-4 ufst-kpis'>";
    $h[] = ufst_kpi("{ufst_kpi_total}", $total, "<span class='ak-fig-s'>$subTotal</span>", "fas fa-globe", "ak-info");
    $h[] = ufst_kpi("{ufst_kpi_blocked}", $blk, "<span class='ak-fig-s'>{ufst_kpi_blocked_s}</span>", "fas fa-ban", "ak-err",
        ($ok && $s["blocks"] > 0) ? "ufst-v-err" : "");
    $h[] = ufst_kpi("{ufst_kpi_allowed}", $pass, "<span class='ak-fig-s'>{ufst_kpi_allowed_s}</span>", "fas fa-circle-check", "ak-ok");
    $h[] = ufst_kpi("{ufst_kpi_rate}", $rate, $ratePill, "fas fa-percent", "ak-off");
    $h[] = "</div>";
    return implode("\n", $h);
}

// Storage footer: two real facts, both optional.
function ufst_footer(array $m): string {
    $left = "";
    if ($m["storage_mb"] !== null) {
        $left = "<span><i class='fas fa-database'></i> "
              . ufst_t("ufst_storage", array($m["storage_mb"] < 0.1 ? "&lt;0.1" : sprintf('%.1f', $m["storage_mb"]))) . "</span>";
    }
    $right = "";
    $tiers = array("raw5m"=>"ufst_tier_raw5m", "hourly"=>"ufst_tier_hourly", "daily"=>"ufst_tier_daily", "weekly"=>"ufst_tier_weekly");
    if ($m["ok"] && isset($tiers[$m["tier"]])) {
        $right = "<span>{ufst_aggregation} <strong>{" . $tiers[$m["tier"]] . "}</strong></span>";
    }
    if ($left === "" && $right === "") { return ""; }
    return "<div class='ufst-foot'>" . ($left === "" ? "<span></span>" : $left) . $right . "</div>";
}

// ── Tab 1: Overview ──────────────────────────────────────────────────────────

function ufst_overview(): void {
    $tpl   = new template_admin();
    $range = ufst_range();
    $s     = ufst_summary($range);

    $h = array();
    $h[] = "<div class='ak'>";
    if (!$s["ok"]) {
        $h[] = ufst_unavailable($s);
    } elseif ($s["blocks"] == 0) {
        $h[] = ufst_empty($range, "{ufst_nothing_blocked}");
    } else {
        $cats = ufst_query("/ufdb/stats/top/categories?range=$range&top=8&filter_action=BLOCK");
        $doms = ufst_query("/ufdb/stats/top/domains?range=$range&top=10&filter_action=BLOCK");
        $catFoot = "<a class='ak-link' href=\"javascript:blur();\" OnClick=\"UfstTab(2)\">{ufst_view_all_kinds} <i class='fas fa-arrow-right'></i></a>";
        $domFoot = ufst_t("ufst_total_domains", array(number_format($doms["total"])));
        $h[] = "<div class='ufst-row'>";
        $h[] = ufst_rank_card("{ufst_top_categories}", "{ufst_share_of_blocked}",
            ufst_chip_top(count($cats["groups"]), $cats["total"]),
            ufst_top_rows($cats["groups"], "category"), $s["blocks"], "{ufst_kind_col}", $catFoot);
        $h[] = ufst_rank_card("{ufst_top_domains}", "{ufst_share_of_blocked}",
            ufst_chip_top(count($doms["groups"]), $doms["total"]),
            ufst_top_rows($doms["groups"], "domain"), $s["blocks"], "{ufst_website_col}",
            $doms["ok"] ? $domFoot : "");
        $h[] = "</div>";
    }
    $h[] = "</div><script>NoSpinner();</script>";
    ufst_echo($tpl, $h);
}

// ── Tab 2: Users ─────────────────────────────────────────────────────────────

function ufst_users(): void {
    $tpl   = new template_admin();
    $page  = CurrentPageName();
    $range = ufst_range();
    $s     = ufst_summary($range);

    $h = array();
    $h[] = "<div class='ak'>";
    if (!$s["ok"]) {
        $h[] = ufst_unavailable($s);
    } elseif ($s["blocks"] == 0) {
        $h[] = ufst_empty($range, "{ufst_empty_users}");
    } else {
        $q = ufst_query("/ufdb/stats/top/users?range=$range&top=15&filter_action=BLOCK");
        $rows = array();
        foreach ($q["groups"] as $g) {
            $u = isset($g->keys->username) ? (string)$g->keys->username : "";
            $label = ($u === "") ? "—" : $u;
            $html = htmlspecialchars($label);
            if ($u !== "") {
                $js = "Loadjs('$page?user-drill=yes&username=" . rawurlencode($u) . "&range=$range')";
                $html = "<i class='fas fa-user' style='color:#6d7a77;margin-right:6px'></i>"
                      . "<a href=\"javascript:blur();\" OnClick=\"$js\">" . htmlspecialchars($u) . "</a>";
            }
            $rows[] = array("html"=>$html, "title"=>$label, "sub"=>"", "count"=>intval($g->count));
        }
        $foot = $q["ok"] ? ufst_t("ufst_total_users", array(number_format($q["total"]))) : "";
        $h[] = ufst_rank_card("{ufst_top_users}", "{ufst_top_users_s}",
            ufst_chip_top(count($q["groups"]), $q["total"]), $rows, $s["blocks"], "{ufst_user}", $foot);
    }
    $h[] = "</div><script>NoSpinner();</script>";
    ufst_echo($tpl, $h);
}

function ufst_user_drill_js(): void {
    $tpl      = new template_admin();
    $page     = CurrentPageName();
    $username = isset($_GET["username"]) ? (string)$_GET["username"] : "";
    if ($username === "") { return; }
    $url = "$page?user-drill-content=yes&username=" . rawurlencode($username) . "&range=" . ufst_range();
    $safe = str_replace(array("'", "\\", "\"", "<", ">"), "", $username);
    $tpl->js_dialog1("{ufst_user_details}: $safe", $url, 760);
}

function ufst_user_drill_content(): void {
    $tpl      = new template_admin();
    $username = isset($_GET["username"]) ? (string)$_GET["username"] : "";
    $range    = ufst_range();
    $s        = ufst_summary($range, $username);

    $h = array();
    $h[] = "<div class='ak ufst' style='margin-top:0'>";
    if (!$s["ok"]) {
        $h[] = ufst_unavailable($s);
    } else {
        $rate = $s["total"] > 0 ? ufst_pct($s["blocks"], $s["total"]) . "&nbsp;%" : "—";
        $pill = $s["total"] > 0 ? ufst_rate_pill($s["blocks"], $s["total"], false) : "";
        $h[] = "<div class='ak-grid-3 ufst-kpis'>";
        $h[] = ufst_kpi("{ufst_kpi_total}", number_format($s["total"]), "<span class='ak-fig-s'>$range</span>", "fas fa-globe", "ak-info");
        $h[] = ufst_kpi("{ufst_kpi_blocked}", number_format($s["blocks"]), "", "fas fa-ban", "ak-err", $s["blocks"] > 0 ? "ufst-v-err" : "");
        $h[] = ufst_kpi("{ufst_kpi_rate}", $rate, $pill, "fas fa-percent", "ak-off");
        $h[] = "</div>";
        $q = ufst_query("/ufdb/stats/query?range=$range&group_by=category&filter_username="
            . urlencode($username) . "&filter_action=BLOCK&top=20");
        $h[] = ufst_rank_card("{ufst_user_categories}", "{ufst_user_categories_s}",
            ufst_chip_top(count($q["groups"]), $q["total"]),
            ufst_top_rows($q["groups"], "category"), $s["blocks"], "{ufst_kind_col}", "");
    }
    $h[] = "</div><script>NoSpinner();</script>";
    ufst_echo($tpl, $h);
}

// ── Tabs 3 & 4: Kinds of websites / Filtering rules (blocked vs allowed) ────

// Pivot a group_by=<dim>,action answer into per-item blocked / total, biggest first.
function ufst_split_rows(array $groups, string $dim): array {
    $items = array();
    foreach ($groups as $g) {
        $k = isset($g->keys->{$dim}) ? (string)$g->keys->{$dim} : "";
        $a = isset($g->keys->action) ? (string)$g->keys->action : "";
        if (!isset($items[$k])) { $items[$k] = array("blk"=>0, "tot"=>0); }
        $c = intval($g->count);
        $items[$k]["tot"] += $c;
        if ($a === "BLOCK") { $items[$k]["blk"] += $c; }
    }
    uasort($items, function($x, $y) { return $y["tot"] - $x["tot"]; });
    return $items;
}

function ufst_split_bar(int $blk, int $tot): string {
    return "<div class='ufst-split' role='img' aria-label='" . ufst_pct($blk, $tot) . " %'>"
         . "<span class='ufst-b' style='width:" . ufst_width($blk, $tot) . "%'></span>"
         . "<span class='ufst-p' style='width:" . ufst_width($tot - $blk, $tot) . "%'></span></div>";
}

function ufst_legend(): string {
    return "<div class='ufst-legend'><span><i class='ufst-sq ufst-sq-b'></i>{ufst_blocked_l}</span>"
         . "<span><i class='ufst-sq ufst-sq-p'></i>{ufst_allowed_l}</span></div>";
}

function ufst_split_card(string $title, string $sub, string $col, array $items, int $total, string $dim, string $foot): string {
    $h = array();
    $h[] = "<section class='ak-card ufst-card'>";
    $h[] = "<div class='ufst-card-hd'><h3 class='ufst-card-t'>$title</h3>" . ufst_legend() . "</div>";
    $h[] = "<p class='ufst-card-s'>$sub</p>";
    $h[] = "<table class='ufst-list ufst-wide'><thead><tr><th>$col</th><th class='ak-num'>{ufst_requests}</th>"
         . "<th>{ufst_blocked_vs_allowed}</th><th class='ak-num'>{ufst_kpi_blocked}</th><th>{ufst_kpi_rate}</th>"
         . "<th class='ak-num'>{ufst_traffic_share}</th></tr></thead><tbody>";
    foreach ($items as $k => $v) {
        $name = ($dim === "category") ? ufst_category_name((string)$k) : (string)$k;
        $subl = ($dim === "category" && preg_match('/^P\d+$/', $name)) ? "<div class='ufst-sub'>{ufst_unknown_code}</div>" : "";
        if ($name === "") { $name = "—"; }
        $tot = intval($v["tot"]); $blk = intval($v["blk"]);
        $h[] = "<tr><td class='ufst-name'><div class='ufst-lbl' title=\"" . htmlspecialchars($name) . "\">" . htmlspecialchars($name) . "</div>$subl</td>"
             . "<td class='ak-num'>" . number_format($tot) . "</td>"
             . "<td class='ufst-barcell'>" . ufst_split_bar($blk, $tot) . "</td>"
             . "<td class='ak-num'>" . number_format($blk) . "</td>"
             . "<td>" . ufst_rate_pill($blk, $tot) . "</td>"
             . "<td class='ak-num'>" . ufst_pct($tot, $total) . "&nbsp;%</td></tr>";
    }
    $h[] = "</tbody></table>";
    if ($foot !== "") { $h[] = "<div class='ufst-card-f'>$foot</div>"; }
    $h[] = "</section>";
    return implode("\n", $h);
}

function ufst_split_tab(string $dim, string $title, string $sub, string $col, string $foot): void {
    $tpl   = new template_admin();
    $range = ufst_range();
    $s     = ufst_summary($range);
    $h = array();
    $h[] = "<div class='ak'>";
    if (!$s["ok"]) {
        $h[] = ufst_unavailable($s);
    } elseif ($s["total"] == 0) {
        $h[] = ufst_empty($range);
    } else {
        $q = ufst_query("/ufdb/stats/query?range=$range&group_by=$dim,action&top=300");
        if (!$q["ok"]) {
            $h[] = ufst_unavailable($q);
        } else {
            $items = array_slice(ufst_split_rows($q["groups"], $dim), 0, 25, true);
            $h[] = ufst_split_card($title, $sub, $col, $items, $s["total"], $dim, $foot);
        }
    }
    $h[] = "</div><script>NoSpinner();</script>";
    ufst_echo($tpl, $h);
}

function ufst_categories(): void {
    ufst_split_tab("category", "{ufst_cat_title}", "{ufst_cat_sub}", "{ufst_kind_col}", "");
}

function ufst_rules(): void {
    $foot = "<a class='ak-link' href=\"javascript:blur();\" OnClick=\"LoadAjaxSilent('MainContent','fw.ufdb.rules.php');\">"
          . "{ufst_open_rules} <i class='fas fa-arrow-right'></i></a>";
    ufst_split_tab("rule", "{ufst_rule_title}", "{ufst_rule_sub}", "{ufst_rule}", $foot);
}

// ── Tab 5: Activity right now ────────────────────────────────────────────────

// "5m0s" (Go duration) -> "5"; anything unexpected -> "".
function ufst_window_minutes(string $d): string {
    if (preg_match('/^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+(?:\.\d+)?)s)?$/', $d, $m) && $d !== "") {
        $min = intval(isset($m[1]) ? $m[1] : 0) * 60 + intval(isset($m[2]) ? $m[2] : 0);
        if ($min > 0) { return (string)$min; }
    }
    return "";
}

function ufst_realtime(): void {
    $tpl = new template_admin();

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/events/live"));
    $rules = array(); $window = ""; $source = "live"; $fbRange = "";
    $daemonOk = is_object($json);

    if ($daemonOk && !empty($json->Status)) {
        $rules  = (isset($json->Rules) && is_array($json->Rules)) ? $json->Rules : array();
        $window = isset($json->WindowDuration) ? (string)$json->WindowDuration : "";
    }

    // Live window empty: fall back to the most recent period that has traffic.
    if (count($rules) == 0) {
        $source = "recent";
        foreach (array("1h", "6h", "24h", "7d") as $r) {
            $q = ufst_query("/ufdb/stats/query?range=$r&group_by=rule,action&top=300");
            if ($q["ok"] && count($q["groups"]) > 0) {
                $fbRange = $r;
                foreach (ufst_split_rows($q["groups"], "rule") as $k => $v) {
                    $rules[] = (object)array("rule"=>$k, "total"=>$v["tot"], "blocks"=>$v["blk"],
                        "passes"=>$v["tot"] - $v["blk"]);
                }
                break;
            }
        }
    }

    $chip = "";
    if ($source === "live") {
        $min = ufst_window_minutes($window);
        $txt = ($min === "") ? htmlspecialchars($window) : ufst_t("ufst_live_counting", array($min));
        $chip = "<span class='ak-tag ak-ok'><span class='ak-dot'></span> $txt</span>";
    } elseif ($fbRange !== "") {
        $chip = "<span class='ak-tag ak-warn'><i class='fas fa-clock-rotate-left'></i> {ufst_live_fallback} $fbRange</span>";
    }

    $h = array();
    $h[] = "<div class='ak'>";
    $h[] = "<section class='ak-card ufst-card'>";
    $h[] = "<div class='ufst-card-hd'><h3 class='ufst-card-t'>{ufst_live_title}</h3>$chip</div>";
    $h[] = "<p class='ufst-card-s'>{ufst_live_sub}</p>";

    if (!$daemonOk) {
        $h[] = ufst_unavailable(array("error"=>"{ufst_daemon_unreachable}"));
    } elseif (count($rules) == 0) {
        $h[] = "<div class='ak-empty-c'><i class='fas fa-clock'></i><div><strong>{ufst_live_empty}</strong></div>"
             . "<div class='ak-note'>{ufst_live_empty_s}</div></div>";
    } else {
        $h[] = "<table class='ufst-list ufst-wide'><thead><tr><th>{ufst_rule}</th><th class='ak-num'>{ufst_requests}</th>"
             . "<th>{ufst_blocked_vs_allowed}</th><th class='ak-num'>{ufst_kpi_blocked}</th>"
             . "<th class='ak-num'>{ufst_kpi_allowed}</th><th>{ufst_kpi_rate}</th></tr></thead><tbody>";
        foreach ($rules as $r) {
            $name = isset($r->rule) ? (string)$r->rule : "";
            if ($name === "") { $name = "—"; }
            $tot = intval(isset($r->total) ? $r->total : 0);
            $blk = intval(isset($r->blocks) ? $r->blocks : 0);
            $pas = intval(isset($r->passes) ? $r->passes : 0);
            $h[] = "<tr><td class='ufst-name'><div class='ufst-lbl'>" . htmlspecialchars($name) . "</div></td>"
                 . "<td class='ak-num'>" . number_format($tot) . "</td>"
                 . "<td class='ufst-barcell'>" . ufst_split_bar($blk, $tot) . "</td>"
                 . "<td class='ak-num'>" . number_format($blk) . "</td>"
                 . "<td class='ak-num'>" . number_format($pas) . "</td>"
                 . "<td>" . ufst_rate_pill($blk, $tot) . "</td></tr>";
        }
        $h[] = "</tbody></table>";
    }
    $h[] = "</section></div><script>NoSpinner();</script>";
    ufst_echo($tpl, $h);
}

// ── Tab 6: Cross-analysis ────────────────────────────────────────────────────

function ufst_dims(): array {
    return array("username"=>"{ufst_user}", "domain"=>"{ufst_website}", "rule"=>"{ufst_rule}", "category"=>"{ufst_kind_col}");
}

function ufst_analysis_form(): void {
    $tpl   = new template_admin();
    $page  = CurrentPageName();
    $range = ufst_range();

    $sel = function($id, $selected) {
        $o = "<select class='ak-sel' id='$id'>";
        foreach (ufst_dims() as $k => $v) {
            $o .= "<option value='$k'" . ($k === $selected ? " selected" : "") . ">$v</option>";
        }
        return $o . "</select>";
    };

    $h = array();
    $h[] = "<div class='ak'>";
    $h[] = "<section class='ak-card ufst-card'>";
    $h[] = "<div class='ufst-card-hd'><h3 class='ufst-card-t'>{ufst_an_title}</h3><span class='ufst-chip'>{ufst_an_expert}</span></div>";
    $h[] = "<p class='ufst-card-s'>{ufst_an_text}</p>";
    $h[] = "<div class='ufst-form'>";
    $h[] = "<div class='ak-f'><label for='ufst-g1'>{ufst_an_first}</label>" . $sel("ufst-g1", "username") . "</div>";
    $h[] = "<div class='ak-f'><label for='ufst-g2'>{ufst_an_then}</label>" . $sel("ufst-g2", "category") . "</div>";
    $h[] = "<div class='ak-f'><button type='button' class='ak-btn ak-btn-primary' OnClick=\"UfstAnalysisLoad();\">"
         . "<i class='fas fa-play'></i> {ufst_an_run}</button></div>";
    $h[] = "</div></section>";
    $h[] = "<div id='ufst-analysis-results' style='margin-top:24px'></div>";
    $h[] = "</div>";

    $js = "<script>function UfstAnalysisLoad(){var g1=document.getElementById('ufst-g1').value,"
        . "g2=document.getElementById('ufst-g2').value;"
        . "LoadAjax('ufst-analysis-results','$page?analysis-data=yes&range=$range&g1='+encodeURIComponent(g1)+'&g2='+encodeURIComponent(g2));}"
        . "NoSpinner();UfstAnalysisLoad();</script>";
    echo ufst_style() . $tpl->_ENGINE_parse_body(implode("\n", $h)) . $js;
}

function ufst_analysis_data(): void {
    $tpl   = new template_admin();
    $range = ufst_range();
    $dims  = ufst_dims();
    $g1 = isset($_GET["g1"]) ? (string)$_GET["g1"] : "username";
    $g2 = isset($_GET["g2"]) ? (string)$_GET["g2"] : "category";
    if (!isset($dims[$g1])) { $g1 = "username"; }
    if (!isset($dims[$g2])) { $g2 = "category"; }

    $s = ufst_summary($range);
    $h = array();
    $h[] = "<div class='ak'>";
    if (!$s["ok"]) {
        $h[] = ufst_unavailable($s);
    } elseif ($s["blocks"] == 0) {
        $h[] = ufst_empty($range, "{ufst_nothing_blocked}");
    } else {
        $groupBy = ($g1 === $g2) ? $g1 : "$g1,$g2";
        $q = ufst_query("/ufdb/stats/query?range=$range&group_by=$groupBy&filter_action=BLOCK&top=50");
        $h[] = "<section class='ak-card ufst-card'>";
        $h[] = "<div class='ufst-card-hd'><h3 class='ufst-card-t'>{$dims[$g1]}" . ($g1 !== $g2 ? " × {$dims[$g2]}" : "") . "</h3>"
             . "<span class='ufst-chip'>" . ufst_chip_top(count($q["groups"]), $q["total"]) . "</span></div>";
        $h[] = "<p class='ufst-card-s'>{ufst_share_of_blocked}</p>";
        $h[] = "<table class='ufst-list ufst-wide'><thead><tr><th>{$dims[$g1]}</th>";
        if ($g1 !== $g2) { $h[] = "<th>{$dims[$g2]}</th>"; }
        $h[] = "<th>{ufst_share}</th><th class='ufst-c'>{ufst_blocked_requests}</th></tr></thead><tbody>";
        foreach ($q["groups"] as $g) {
            $v1 = isset($g->keys->{$g1}) ? (string)$g->keys->{$g1} : "";
            $v2 = isset($g->keys->{$g2}) ? (string)$g->keys->{$g2} : "";
            if ($g1 === "category") { $v1 = ufst_category_name($v1); }
            if ($g2 === "category") { $v2 = ufst_category_name($v2); }
            $c = intval($g->count);
            $h[] = "<tr><td class='ufst-name'><div class='ufst-lbl'>" . htmlspecialchars($v1 === "" ? "—" : $v1) . "</div></td>";
            if ($g1 !== $g2) { $h[] = "<td class='ufst-name'><div class='ufst-lbl' style='font-weight:500'>" . htmlspecialchars($v2 === "" ? "—" : $v2) . "</div></td>"; }
            $h[] = "<td class='ufst-barcell'><div class='ufst-barline' style='margin:0'><div class='ak-bar ak-err'><span style='width:" . ufst_width($c, $s["blocks"]) . "%'></span></div>"
                 . "<span class='ufst-pc'>" . ufst_pct($c, $s["blocks"]) . "&nbsp;%</span></div></td>"
                 . "<td class='ufst-c'>" . number_format($c) . "</td></tr>";
        }
        $h[] = "</tbody></table></section>";
    }
    $h[] = "</div><script>NoSpinner();</script>";
    ufst_echo($tpl, $h);
}
