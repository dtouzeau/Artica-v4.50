<?php
// Failover (keepalived) events, laid out like fw.postgresql.events.php: the awc
// search bar wired to the server, and the syslog lines in an awc table. The
// lines come from articarest (/keepalived/syslog), which runs grep itself.
include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"] = new sockets();
}
// Inclusion INCONDITIONNELLE: awc_table_style()/awc_pager_js()/awc_search_bar() vivent ici.
include_once(dirname(__FILE__) . "/ressources/class.statistics_appliance.inc");
$users = new usersMenus();
if (!$users->AsVPNManager) {
    exit();
}
if (isset($_GET["verbose"])) {
    $GLOBALS["VERBOSE"] = true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}

if (isset($_GET["search"])) {
    search();
    exit;
}
if (isset($_GET["body"])) {
    body();
    exit;
}

page();


// The page header, as the other Failover pages (fw.keepalived.instances.php):
// page_header() draws it and loads body() into keepalived-events-body. The TINY
// array sent by body() then fills in its buttons.
function page()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $version = htmlspecialchars((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_VERSION"));
    $html = $tpl->page_header("{APP_KEEPALIVED} v$version &raquo; {events}", "fas fa-list", "{keepalived_what_is}",
        "$page?body=yes", "failover-events", "progress-firehol-restart", false, "keepalived-events-body");

    if (isset($_GET["main-page"])) {
        $tpl = new template_admin('Artica: Failover Events', $html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

function body()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $t = time();
    if (empty($_SESSION["KEEPALIVED_SEARCH"])) {
        $_SESSION["KEEPALIVED_SEARCH"] = "today max 500 everything";
    }
    // Valeur injectee dans un litteral JS: json_encode + HEX_TAG, pour qu'un
    // "</script>" ou un guillemet stocke en session ne sorte pas de la chaine.
    $initial = json_encode((string)$_SESSION["KEEPALIVED_SEARCH"], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);

    $html = array();
    $html[] = awc_search_bar("kae-search-$t");
    $html[] = "<div class='row'>";
    $html[] = "<div id='table-loader'></div>";
    $html[] = "</div>";

    $btn = $tpl->button_inline("{online_help}", "s_PopUp('https://wiki.articatech.com/en/artica-failover-service','1024','800')", "fa-solid fa-headset", null, null, "btn-blue");
    $version = htmlspecialchars((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_VERSION"));
    $TINY_ARRAY = array();
    $TINY_ARRAY["TITLE"] = "{APP_KEEPALIVED} v$version &raquo;&raquo; {events}";
    $TINY_ARRAY["ICO"] = "fas fa-list";
    $TINY_ARRAY["EXPL"] = "{keepalived_what_is}";
    $TINY_ARRAY["URL"] = "failover-events";
    $TINY_ARRAY["BUTTONS"] = $btn;
    $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";

    // Script concatene APRES le parsing: _ENGINE_parse_body lirait chaque bloc
    // {...} du JavaScript comme un jeton a traduire.
    $js = "<script>
    (function(){
        var inp=document.getElementById('kae-search-$t');
        if(!inp){return;}
        inp.value=$initial;
        function ss$t(){
            LoadAjax('table-loader','$page?search='+encodeURIComponent(inp.value));
        }
        inp.onkeypress=function(e){ if(checkEnter(e)){ ss$t(); } };
        var go=document.getElementById('kae-search-$t-go');
        if(go){ go.onclick=function(){ ss$t(); return false; }; }
        window.ss$t=ss$t;
        ss$t();
    })();
    $jstiny
    </script>";

    echo $tpl->search_block_css() . $tpl->_ENGINE_parse_body(@implode("\n", $html)) . $js;
}

function kae_style(): string
{
    return "<style>
.kae-ico-err{background:rgba(186,26,26,.10);color:#ba1a1a}
.kae-ico-warn{background:rgba(134,83,0,.10);color:#865300}
.kae-b{display:inline-block;font-size:10px;font-weight:700;letter-spacing:.02em;text-transform:uppercase;padding:3px 7px;border-radius:4px;white-space:nowrap;background:#e8ecec;color:#3d4947}
.kae-msg{font-size:13px;line-height:19px;color:#111d23;word-break:break-word}
.kae-msg-err{color:#ba1a1a}
.kae-msg-warn{color:#865300}
</style>";
}

// Severity of a line, with the words the old table coloured on.
function kae_severity(string $msg): string
{
    foreach (array("failed", "fatal", "error", "Error") as $w) {
        if (strpos($msg, $w) !== false) {
            return "err";
        }
    }
    foreach (array("Stopped", "WARNING") as $w) {
        if (strpos($msg, $w) !== false) {
            return "warn";
        }
    }
    return "";
}

function search()
{
    $tpl = new template_admin();
    $sock = new sockets();

    $_SESSION["KEEPALIVED_SEARCH"] = $_GET["search"];
    $MAIN = $tpl->format_search_protocol($_GET["search"]);

    // Les lignes viennent du daemon (/keepalived/syslog): il lance grep sans
    // shell et rend le resultat, au lieu d'un fichier partage entre sessions.
    $search = "";
    $term = trim((string)($MAIN["TERM"] ?? ""));
    if ($term != "") {
        $search = str_replace("\\*", ".*", preg_quote($term));
    }
    $zdate = trim((string)($MAIN["DATE"] ?? ""));
    if ($zdate != "") {
        $search = ($search == "") ? $zdate : "$zdate.*$search";
    }
    $max = intval($MAIN["MAX"] ?? 0);
    if ($max <= 0) {
        $max = 200;
    }
    $json = json_decode($sock->REST_API("/keepalived/syslog?max=$max&search=" . urlencode($search)), true);
    if (!is_array($json)) {
        echo $tpl->div_error("{error}<hr>" . json_last_error_msg());
        return;
    }
    if (empty($json["Status"])) {
        echo $tpl->div_error("{error}<hr>" . req_html($json["Error"] ?? ""));
        return;
    }
    $data = is_array($json["lines"] ?? null) ? $json["lines"] : array();

    $rows = array();
    foreach (array_reverse($data) as $line) {
        $line = trim(str_replace("RwFri", "", (string)$line));
        if ($line == "") {
            continue;
        }
        $datetext = "";
        $daemon = "";
        $msg = $line;
        if (preg_match("#([a-zA-Z]+)\s+([0-9]+)\s+([0-9:]+)\s+([a-zA-Z-0-9]+)\s+([a-zA-Z_]+)(.*)#", $line, $re)) {
            $datetext = $tpl->time_to_date(strtotime("{$re[1]} {$re[2]} {$re[3]}"), true);
            $daemon = $re[5];
            $parts = explode(':', $re[6], 2);
            $msg = trim($parts[1] ?? $re[6]);
        }
        $sev = kae_severity($msg);
        $icocls = ($sev == "") ? "awc-ico" : "awc-ico kae-ico-$sev";
        $ico = ($sev == "err") ? "fas fa-circle-exclamation" : (($sev == "warn") ? "fas fa-triangle-exclamation" : "fas fa-circle-info");
        $msgcls = ($sev == "") ? "kae-msg" : "kae-msg kae-msg-$sev";
        $name = ($datetext == "") ? "<span class='awc-muted'>&mdash;</span>" : req_html($datetext);

        // Pentest 2026-11-04 : demon/message syslog keepalived (donnee externe) affiches sans encodage (XSS stockee).
        $h = "<tr>";
        $h .= "<td style='width:1%' nowrap><div class='awc-ident'><span class='$icocls'><i class='$ico'></i></span>";
        $h .= "<span><span class='awc-name'>$name</span></span></div></td>";
        $h .= "<td class='center' style='width:1%' nowrap>" . ($daemon == "" ? "" : "<span class='kae-b'>" . req_html($daemon) . "</span>") . "</td>";
        $h .= "<td><div class='$msgcls'>" . req_html($msg) . "</div></td>";
        $h .= "</tr>";
        $rows[] = $h;
    }
    $total = count($rows);

    $html = array();
    $html[] = "<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[] = "<table class='awc-tbl'><thead><tr>";
    $html[] = "<th>{date}</th>";
    $html[] = "<th class='center'>{daemon}</th>";
    $html[] = "<th>{events}</th>";
    $html[] = "</tr></thead><tbody id='kae-rows'>";
    if ($total == 0) {
        $html[] = "<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
    } else {
        $html[] = @implode("\n", $rows);
    }
    $html[] = "</tbody></table></div>";
    $html[] = "<div class='awc-foot'><span>$total {events}</span><span class='awc-pg' id='kae-pager'></span></div>";
    $html[] = "</div></div>";

    // Feuilles et scripts concatenes AUTOUR du parsing: la regex #{(.+?)}# de
    // _ENGINE_parse_body lit chaque corps de regle CSS et chaque bloc JS comme un jeton.
    echo awc_table_style() . kae_style()
        . $tpl->_ENGINE_parse_body(@implode("\n", $html))
        . awc_pager_js(50, "kae-rows", "kae-pager")
        . "<script>NoSpinner();\n" . $tpl->ICON_SCRIPTS_COMPILE() . "</script>";
}
