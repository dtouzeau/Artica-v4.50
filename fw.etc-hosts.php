<?php
// Post SP10 - 13/10/2026
// fw.etc-hosts.php — Entrées du fichier hosts de l'appliance (table net_hosts
// de /home/artica/SQLITE/etc_hosts.db). L'écriture de /etc/hosts appartient à
// articarest (module hostfile, route /system/hosts) : chaque enregistrement
// appelle la route, qui réécrit le fichier en tâche de fond, rafraîchit le DNS
// local (Unbound, s'il est activé) et redémarre le cache DNS. La convergence
// tourne aussi toutes les 5 minutes.
//
// Refonte stitch (2026-11-17) : bandeau d'accroche, alertes conditionnelles et
// un statut par entrée (Applied / Managed by Artica / Skipped + raison) calculé
// avec les MÊMES règles que renderHosts() et CheckMyHost()
// (articarest/hostfile/hostfile.go). Modifier l'une sans l'autre ferait mentir
// la page : voir etch_status().
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
// Politique Meta « Fichier des Hôtes » : lignes net_hosts metaid<>'' en lecture seule, bandeau du verrou.
include_once(dirname(__FILE__)."/ressources/class.hosts.meta.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Pentest 2026-11-04 M-02 : function finissait dans du JavaScript, host et
// zmd5 dans du SQL. zmd5 vaut un md5 ou une adresse IP (lignes héritées).
if(isset($_GET["function"])){$_GET["function"]=preg_replace('/[^A-Za-z0-9_]/','',strval($_GET["function"]));}
foreach(array("host","host-popup","host-delete") as $k){if(isset($_GET[$k])){$_GET[$k]=preg_replace('/[^A-Za-z0-9.:_-]/','',strval($_GET[$k]));}}
foreach(array("zmd5","host-delete") as $k){if(isset($_POST[$k])){$_POST[$k]=preg_replace('/[^A-Za-z0-9.:_-]/','',strval($_POST[$k]));}}

if(isset($_GET["build-page"])){etch_content();exit;}
if(isset($_GET["list"])){etch_list();exit;}
if(isset($_GET["host"])){host_js();exit;}
if(isset($_GET["host-popup"])){host_popup();exit;}
if(isset($_POST["zmd5"])){host_save();exit;}
if(isset($_GET["host-delete"])){host_delete_js();exit;}
if(isset($_POST["host-delete"])){host_delete();exit;}
page();

// ── Outils ─────────────────────────────────────────────────────────────────

function etch_db(): lib_sqlite
{
    return new lib_sqlite("/home/artica/SQLITE/etc_hosts.db");
}

function etch_h($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8");
}

// Jeton traduit en texte brut ([br] retirés, accents décodés), %s remplacés.
function etch_t(string $token, ...$args): string
{
    $tpl = new template_admin();
    $t = trim(preg_replace('#\s+#', " ", str_replace(array("[br]", "<br>"), " ", $tpl->_ENGINE_parse_body("{" . $token . "}"))));
    $t = html_entity_decode($t, ENT_QUOTES, "UTF-8");
    return count($args) > 0 ? vsprintf($t, $args) : $t;
}

function etch_num($n): string
{
    return number_format(intval($n), 0, ".", ",");
}

function etch_refresh_fn(): string
{
    return "EtchRefresh";
}

// Les écritures exigent un droit DNS (ou l'administrateur système) CÔTÉ
// SERVEUR : masquer un bouton n'a jamais été un contrôle.
function etch_can_write(): bool
{
    $users = new usersMenus();
    return $users->AsDnsAdministrator || $users->AsSystemAdministrator;
}

// Clé d'une ligne : zmd5, ou l'adresse pour les lignes héritées sans zmd5.
function etch_key(array $r): string
{
    $k = trim((string)(isset($r["zmd5"]) ? $r["zmd5"] : ""));
    return strlen($k) < 5 ? trim((string)(isset($r["ipaddr"]) ? $r["ipaddr"] : "")) : $k;
}

function etch_find(string $key): array
{
    if ($key === "") { return array(); }
    $q = etch_db();
    $ligne = $q->mysqli_fetch_array("SELECT * FROM net_hosts WHERE zmd5='$key'");
    if (!is_array($ligne) || !isset($ligne["ipaddr"])) {
        // Repli des lignes héritées sans zmd5 (etch_key) : jamais une ligne Meta, qui a toujours un zmd5.
        $ligne = $q->mysqli_fetch_array("SELECT * FROM net_hosts WHERE ipaddr='$key' AND (zmd5 IS NULL OR length(zmd5)<5)" . hosts_meta_local_sql($q) . " LIMIT 1");
    }
    return is_array($ligne) && isset($ligne["ipaddr"]) ? $ligne : array();
}

// ── Squelette ──────────────────────────────────────────────────────────────

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{etc_hosts}","fa-duotone fa-laptop-file",
        "{etch_header_explain}","$page?build-page=yes",
        "hostfile","progress-etchost-restart",false,"table-loader-my-etchosts"
    );
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{etc_hosts}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

function etch_content(): void
{
    $page = CurrentPageName();
    $fn = etch_refresh_fn();
    echo "<div id='etch-host' class='ak etch'></div>"
       . "<script>"
       . "window.$fn=function(){LoadAjaxSilent('etch-host','$page?list=yes');};"
       . "LoadAjax('etch-host','$page?list=yes');"
       . "</script>";
}

// ── Modèle ─────────────────────────────────────────────────────────────────

// Réplique de renderHosts() / CheckMyHost() (articarest/hostfile/hostfile.go).
// Rend array(statut, raison) ; statut = applied | managed | skipped.
// $seen porte les couples (adresse, nom) déjà écrits, lignes automatiques
// comprises, dans l'ordre du démon (ORDER BY hostname, ipaddr).
function etch_status(array $r, array $ctx, array &$seen): array
{
    $ip = trim((string)(isset($r["ipaddr"]) ? $r["ipaddr"] : ""));
    $name = trim((string)(isset($r["hostname"]) ? $r["hostname"] : ""));
    $lname = strtolower($name);
    if (preg_match('/(ip6-localhost|ip6-loopback|localhost|localdomain)/', $name)) { return array("skipped", "etch_why_reserved"); }
    // ChHostNameEtcHost insère lui-même 127.0.0.1 et ::1 au nom de l'appliance :
    // ces lignes sont les siennes (le démon écrit 127.0.1.1 → myhostname), pas
    // des entrées de l'opérateur à signaler comme ignorées.
    if ($ctx["myhost"] !== "" && in_array($ip, array("127.0.0.1", "::1", "127.0.1.1"), true)
        && ($lname === strtolower($ctx["myhost"]) || $lname === strtolower($ctx["alias"]))) {
        return array("managed", "");
    }
    if ($name === "::1" || in_array($ip, array("::1", "127.0.0.1", "0.0.0.0", "127.0.0.55", "127.0.1.1", "127.0.0.118", "127.0.0.119"), true)) {
        return array("skipped", "etch_why_loopback");
    }
    $isV6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    if ($isV6 && !$ctx["ipv6"]) { return array("skipped", "etch_why_ipv6"); }
    if (filter_var($ip, FILTER_VALIDATE_IP) === false) { return array("skipped", "etch_why_invalid"); }
    if (strlen($name) < 3) { return array("skipped", "etch_why_short"); }
    // Une adresse de l'appliance : seul son propre nom y est gardé, toute
    // autre liaison est purgée par CheckMyHost.
    if (isset($ctx["our_ips"][$ip])) {
        if ($ctx["myhost"] !== "" && ($lname === strtolower($ctx["myhost"]) || $lname === strtolower($ctx["alias"]))) {
            return array("managed", "");
        }
        if ($ctx["myhost"] !== "") { return array("skipped", "etch_why_own_ip"); }
    }
    $key = $ip . "\x00" . $lname;
    if (isset($seen[$key])) { return array("skipped", "etch_why_duplicate"); }
    $seen[$key] = true;
    return array("applied", "");
}

function etch_model(): array
{
    $m = array("error" => "", "rows" => array(), "total" => 0, "max" => 500, "applied" => 0, "skipped" => 0,
               "disabled" => false, "unbound" => false, "meta" => 0);
    $sock = $GLOBALS["CLASS_SOCKETS"];
    $m["disabled"] = intval($sock->GET_INFO("DisableEtcHosts")) === 1;
    $m["unbound"] = intval($sock->GET_INFO("UnboundEnabled")) === 1;
    $myhost = trim((string)$sock->GET_INFO("myhostname"));
    $ourIps = array();
    if (strlen($myhost) > 3) {
        $net = new networking();
        foreach ($net->ALL_IPS_GET_ARRAY() as $ip) { $ourIps[$ip] = true; }
    }
    $ctx = array("ipv6" => intval($sock->GET_INFO("EnableipV6")) === 1, "myhost" => $myhost,
                 "alias" => explode(".", $myhost)[0], "our_ips" => $ourIps);

    $q = etch_db();
    $results = $q->QUERY_SQL("SELECT * FROM net_hosts ORDER BY hostname,ipaddr");
    if (!$q->ok) { $m["error"] = (string)$q->mysql_error; return $m; }
    if (!is_array($results)) { $results = array(); }
    // renderHosts écrit les entrées Meta avant les entrées locales : une entrée Meta gagne donc sur une
    // entrée locale de même adresse et même nom. Même ordre ici pour que les statuts disent vrai.
    $metaRows = array();
    $localRows = array();
    foreach ($results as $r) {
        if (!is_array($r)) { continue; }
        if (hosts_meta_row($r)) { $metaRows[] = $r; $m["meta"]++; } else { $localRows[] = $r; }
    }
    $results = array_merge($metaRows, $localRows);
    // Les liaisons automatiques passent avant les entrées, comme dans le démon.
    $seen = array("127.0.0.1\x00localhost.localdomain" => true);
    if (strlen($myhost) > 3) {
        foreach ($ourIps as $ip => $none) { $seen[$ip . "\x00" . strtolower($myhost)] = true; }
    }
    foreach ($results as $r) {
        if (!is_array($r)) { continue; }
        $m["total"]++;
        list($st, $why) = etch_status($r, $ctx, $seen);
        if ($st === "skipped") { $m["skipped"]++; } else { $m["applied"]++; }
        if (count($m["rows"]) < $m["max"]) {
            $r["_status"] = $st;
            $r["_why"] = $why;
            $m["rows"][] = $r;
        }
    }
    return $m;
}

// ── Répondeur de la liste ──────────────────────────────────────────────────

function etch_list(): void
{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $fn = etch_refresh_fn();
    $topbuttons = array();
    if (etch_can_write()) {
        $topbuttons[] = array("Loadjs('$page?host=&function=$fn');", ico_plus, "{etch_new}");
        $configure = $tpl->framework_buildjs("/system/hosts", "system.hosts", "system.hosts.log", "progress-etchost-restart", "$fn();");
        $topbuttons[] = array($configure, ico_refresh, "{etch_rewrite}");
    }
    $TINY_ARRAY = array(
        "TITLE"   => "{etc_hosts}",
        "ICO"     => "fa-duotone fa-laptop-file",
        "EXPL"    => "{etch_header_explain}",
        "URL"     => "hostfile",
        "BUTTONS" => $tpl->table_buttons($topbuttons),
    );
    $tiny = "<script>Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');NoSpinner();</script>";

    $m = etch_model();
    if ($m["error"] !== "") {
        echo etch_style() . $tpl->_ENGINE_parse_body("<div class='ak-banner ak-err'><i class='fa-solid fa-circle-exclamation'></i><span>"
            . etch_h($m["error"]) . "</span></div>") . $tiny;
        return;
    }
    $h = array();
    $h[] = etch_hero($m);
    $h[] = etch_disabled_alert($m);
    $h[] = hosts_meta_banner($tpl, $m["meta"] > 0);
    if ($m["total"] === 0) {
        $h[] = etch_empty();
    } else {
        $h[] = etch_skipped_alert($m);
        $h[] = etch_card($m);
    }
    echo etch_style() . $tpl->_ENGINE_parse_body(implode("\n", $h)) . $tiny . etch_js();
}

// ── 1. Bandeau d'accroche ──────────────────────────────────────────────────

function etch_hero(array $m): string
{
    return "<section class='etch-hero'>"
         . "<span class='etch-hero-ico'><i class='fa-solid fa-signs-post'></i></span>"
         . "<div class='etch-hero-main'>"
         . "<h2 class='etch-hero-t'>{etch_hero_title}</h2>"
         . "<p class='etch-hero-p'>{etch_hero_text}</p>"
         . "<div class='etch-chips'>"
         . etch_chip("fa-solid fa-bolt", "{etch_chip_background}")
         . etch_chip("fa-solid fa-rotate", "{etch_chip_recheck}")
         . etch_chip("fa-solid fa-shield-halved", "{etch_chip_own_name}")
         . "</div></div>"
         . etch_counter($m)
         . "</section>";
}

function etch_chip(string $icon, string $label): string
{
    return "<span class='etch-chip'><i class='$icon'></i><span>$label</span></span>";
}

function etch_counter(array $m): string
{
    if ($m["total"] === 0) { return ""; }
    $title = $m["total"] === 1 ? etch_t("etch_count_one") : etch_t("etch_count_many", etch_num($m["total"]));
    if ($m["disabled"]) {
        $sub = "<span class='etch-count-err'>" . etch_h(etch_t("etch_count_off")) . "</span>";
    } else {
        $sub = etch_h(etch_t("etch_count_applied", etch_num($m["applied"])));
        if ($m["skipped"] > 0) {
            $sub .= " &middot; <span class='etch-count-warn'>" . etch_h(etch_t("etch_count_skipped", etch_num($m["skipped"]))) . "</span>";
        }
    }
    return "<div class='etch-count'><div class='etch-count-v'>" . etch_h($title) . "</div><div class='etch-count-s'>$sub</div></div>";
}

// ── 2. Alertes ─────────────────────────────────────────────────────────────

function etch_disabled_alert(array $m): string
{
    if (!$m["disabled"]) { return ""; }
    return "<div class='ak-banner ak-err etch-strip' role='alert'><i class='fa-solid fa-circle-exclamation'></i>"
         . "<span class='ak-grow'><strong>{etch_off_title}</strong> {etch_off_text}</span></div>";
}

function etch_skipped_alert(array $m): string
{
    if ($m["skipped"] === 0 || $m["disabled"]) { return ""; }
    $msg = $m["skipped"] === 1 ? etch_t("etch_skipped_one") : etch_t("etch_skipped_many", etch_num($m["skipped"]));
    return "<div class='ak-banner ak-warn etch-strip' role='status'><i class='fa-solid fa-circle-minus'></i>"
         . "<span class='ak-grow'>" . etch_h($msg) . "</span>"
         . "<button type='button' class='ak-link etch-strip-btn' id='etch-skipbtn' aria-pressed='false' "
         . "data-on=\"" . etch_h(etch_t("etch_show_skipped")) . "\" data-off=\"" . etch_h(etch_t("etch_show_all")) . "\" "
         . "OnClick=\"EtchSkipped();\"><i class='fa-solid fa-filter'></i> {etch_show_skipped}</button></div>";
}

// ── 3. Carte des entrées ───────────────────────────────────────────────────

function etch_card(array $m): string
{
    $rows = array();
    foreach ($m["rows"] as $r) { $rows[] = etch_row($m, $r); }
    $ph = etch_h(etch_t("etch_filter_ph"));
    $shown = count($m["rows"]);
    $foot = $shown < $m["total"] ? etch_t("etch_foot_cut", etch_num($shown)) : ($shown === 1 ? etch_t("etch_foot_one") : etch_t("etch_foot_many", etch_num($shown)));
    $dns = $m["unbound"] ? "<span class='etch-foot-dns'><i class='fa-solid fa-circle-check'></i> {etch_foot_dns}</span>" : "";
    return "<section class='ak-card etch-card'>"
         . "<div class='etch-card-h'><div>"
         . "<h3 class='etch-h3'>{etch_card_title} <span class='etch-badge'>" . etch_num($m["total"]) . "</span></h3>"
         . "<p class='etch-sub'>{etch_card_help}</p></div>"
         . "<label class='ak-search etch-filter'><i class='fa-solid fa-filter'></i>"
         . "<input type='search' id='etch-q' autocomplete='off' aria-label=\"$ph\" placeholder=\"$ph\" oninput=\"EtchFilter();\"></label>"
         . "</div>"
         . "<table class='ak-tbl etch-tbl'><thead><tr>"
         . "<th>{etch_col_name} <span class='etch-th-sub'>({etch_col_name_sub})</span></th>"
         . "<th>{etch_col_address} <span class='etch-th-sub'>({etch_col_address_sub})</span></th>"
         . "<th>{status}</th><th class='ak-end'>{etch_col_actions}</th>"
         . "</tr></thead><tbody id='etch-rows'>" . implode("", $rows) . "</tbody></table>"
         . "<div class='ak-empty-c etch-nomatch' id='etch-nomatch' hidden><i class='fa-solid fa-filter-circle-xmark'></i>"
         . "<span>{etch_no_match}</span><button type='button' class='ak-link' OnClick=\"EtchClear();\">{etch_clear_filter}</button></div>"
         . "<div class='etch-foot'><span>" . etch_h($foot) . "</span>$dns</div>"
         . "</section>";
}

function etch_row(array $m, array $r): string
{
    $st = $r["_status"];
    $ip = (string)(isset($r["ipaddr"]) ? $r["ipaddr"] : "");
    $name = (string)(isset($r["hostname"]) ? $r["hostname"] : "");
    $alias = trim((string)(isset($r["alias"]) ? $r["alias"] : ""));
    $hay = strtolower($name . " " . $alias . " " . $ip . (hosts_meta_row($r) ? " artica meta" : ""));
    $cls = $st === "skipped" ? " etch-skip" : "";
    return "<tr class='etch-row$cls' data-q=\"" . etch_h($hay) . "\" data-skip='" . ($st === "skipped" ? "1" : "0") . "'>"
         . "<td>" . etch_cell_name($r) . "</td>"
         . "<td>" . etch_cell_address($ip) . "</td>"
         . "<td>" . etch_cell_status($m, $r) . "</td>"
         . "<td class='ak-end ak-nowrap'>" . etch_cell_actions($r) . "</td>"
         . "</tr>";
}

function etch_edit_js(array $r): string
{
    return "Loadjs('" . CurrentPageName() . "?host=" . urlencode(etch_key($r)) . "&function=" . etch_refresh_fn() . "')";
}

function etch_cell_name(array $r): string
{
    $name = trim((string)(isset($r["hostname"]) ? $r["hostname"] : ""));
    $alias = trim((string)(isset($r["alias"]) ? $r["alias"] : ""));
    $label = $name !== "" ? etch_h($name) : "&mdash;";
    if (etch_can_write() && !hosts_meta_row($r)) {
        $lbl = etch_h(etch_t("etch_edit_aria", $name));
        $label = "<a href=\"javascript:blur();\" class='etch-name' title=\"$lbl\" OnClick=\"" . etch_edit_js($r) . "\">$label</a>";
    } else {
        $label = "<span class='etch-name'>$label</span>";
    }
    $meta = $alias !== "" ? "<div class='etch-meta'>{etch_short_name} <b>" . etch_h($alias) . "</b></div>" : "";
    return "<div class='etch-ident'><span class='ak-ico ak-ico-sm ak-ico-sq " . ($r["_status"] === "skipped" ? "ak-off" : "ak-ok") . "'><i class='fa-solid fa-desktop'></i></span>"
         . "<div class='etch-ident-t'>$label" . hosts_meta_badge($r) . "$meta</div></div>";
}

function etch_cell_address(string $ip): string
{
    $v6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    return "<span class='etch-ip'>" . etch_h($ip) . "</span>" . ($v6 ? " <span class='ak-tag etch-v6'>IPv6</span>" : "");
}

function etch_cell_status(array $m, array $r): string
{
    $st = $r["_status"];
    if ($st === "skipped") {
        return "<span class='ak-tag ak-warn etch-pill'><i class='fa-solid fa-circle-minus'></i> {etch_st_skipped}</span>"
             . "<div class='etch-why'>{" . $r["_why"] . "}</div>";
    }
    // Fichier désactivé : rien n'est appliqué, quelle que soit l'entrée.
    if ($m["disabled"]) {
        return "<span class='ak-tag ak-off etch-pill'><i class='fa-solid fa-circle-pause'></i> {etch_st_kept}</span>";
    }
    if ($st === "managed") {
        return "<span class='ak-tag ak-info etch-pill' title=\"" . etch_h(etch_t("etch_managed_help")) . "\"><i class='fa-solid fa-shield-halved'></i> {etch_st_managed}</span>"
             . "<div class='etch-why'>{etch_managed_help}</div>";
    }
    return "<span class='ak-tag ak-ok etch-pill'><i class='fa-solid fa-circle-check'></i> {etch_st_applied}</span>";
}

function etch_cell_actions(array $r): string
{
    if (!etch_can_write()) { return ""; }
    if (hosts_meta_row($r)) {
        return "<div class='ak-acts'><span class='ak-act' title=\"{hosts_meta_row_locked}\" aria-label=\"{hosts_meta_row_locked}\"><i class='fa-solid fa-lock'></i></span></div>";
    }
    $page = CurrentPageName();
    $name = trim((string)(isset($r["hostname"]) ? $r["hostname"] : ""));
    $out = "";
    $lbl = etch_h(etch_t("etch_edit_aria", $name));
    $out .= "<button type='button' class='ak-act' title=\"$lbl\" aria-label=\"$lbl\" OnClick=\"" . etch_edit_js($r) . "\"><i class='fa-solid fa-pen'></i></button>";
    if ($r["_status"] === "managed") {
        $why = etch_h(etch_t("etch_remove_managed"));
        $out .= "<button type='button' class='ak-act' disabled title=\"$why\" aria-label=\"$why\"><i class='fa-solid fa-trash-can'></i></button>";
    } else {
        $lbl = etch_h(etch_t("etch_remove_aria", $name));
        $out .= "<button type='button' class='ak-act ak-danger' title=\"$lbl\" aria-label=\"$lbl\" "
              . "OnClick=\"Loadjs('$page?host-delete=" . urlencode(etch_key($r)) . "');\"><i class='fa-solid fa-trash-can'></i></button>";
    }
    return "<div class='ak-acts'>$out</div>";
}

// ── 4. État vide ───────────────────────────────────────────────────────────

function etch_empty(): string
{
    $btn = "";
    if (etch_can_write()) {
        $btn = "<button type='button' class='ak-btn ak-btn-primary ak-btn-lg' OnClick=\"Loadjs('" . CurrentPageName() . "?host=&function=" . etch_refresh_fn() . "');\">"
             . "<i class='fa-solid fa-plus'></i> {etch_new}</button>";
    }
    return "<section class='ak-card'><div class='etch-empty'>"
         . "<span class='etch-empty-ico'><i class='fa-solid fa-signs-post'></i></span>"
         . "<h3 class='etch-empty-t'>{etch_empty_title}</h3>"
         . "<p class='etch-empty-p'>{etch_empty_text}</p>$btn</div></section>";
}

// ── JavaScript de la liste ─────────────────────────────────────────────────

function etch_js(): string
{
    return "<script>
(function(){
var st=window.EtchState=window.EtchState||{q:'',skip:false};
function apply(){
  var tb=document.getElementById('etch-rows'); if(!tb){return;}
  var q=st.q.toLowerCase(), shown=0, rows=tb.rows;
  for(var i=0;i<rows.length;i++){
    var r=rows[i], ok=true;
    if(q!=='' && (r.getAttribute('data-q')||'').indexOf(q)<0){ok=false;}
    if(st.skip && r.getAttribute('data-skip')!=='1'){ok=false;}
    r.style.display=ok?'':'none'; if(ok){shown++;}
  }
  var nm=document.getElementById('etch-nomatch'); if(nm){nm.hidden=(shown>0);}
  var b=document.getElementById('etch-skipbtn');
  if(b){var s=b.querySelector('span.etch-btxt'); if(!s){s=document.createElement('span');s.className='etch-btxt';while(b.lastChild&&b.lastChild.nodeType===3){b.removeChild(b.lastChild);}b.appendChild(s);}
    s.textContent=' '+(st.skip?b.getAttribute('data-off'):b.getAttribute('data-on'));b.setAttribute('aria-pressed',st.skip?'true':'false');}
}
window.EtchFilter=function(){var i=document.getElementById('etch-q');st.q=i?i.value.replace(/^\\s+|\\s+\$/g,''):'';apply();};
window.EtchSkipped=function(){st.skip=!st.skip;apply();};
window.EtchClear=function(){st.q='';st.skip=false;var i=document.getElementById('etch-q');if(i){i.value='';}apply();};
var inp=document.getElementById('etch-q'); if(inp){inp.value=st.q;}
if(!document.getElementById('etch-skipbtn')){st.skip=false;}
apply();
})();
</script>";
}

// ── CSS propre à la page (hors _ENGINE_parse_body) ─────────────────────────

function etch_style(): string
{
    if (isset($GLOBALS["ETCH_STYLE_DONE"])) { return ""; }
    $GLOBALS["ETCH_STYLE_DONE"] = true;
    return "<style>
.etch{display:flex;flex-direction:column;gap:20px;margin-top:10px}
.etch-hero{display:flex;align-items:flex-start;gap:20px;padding:24px;border-radius:14px;border:1px solid var(--ak-line,#a7f3d0);background:linear-gradient(135deg,#f8fafc 0%,var(--ak-soft,rgba(16,185,129,.12)) 100%),#fff}
.etch-hero-ico{flex:0 0 56px;width:56px;height:56px;border-radius:14px;background:#fff;border:1px solid var(--ak-border,#e2e8f0);display:inline-flex;align-items:center;justify-content:center;font-size:24px;color:var(--ak-fill,#006a62);box-shadow:0 1px 2px rgba(15,23,42,.06)}
.etch-hero-main{flex:1 1 auto;min-width:0}
.etch-hero-t{margin:0 0 6px;font-size:20px;font-weight:700;color:var(--ak-text,#0f172a);line-height:1.3}
.etch-hero-p{margin:0 0 14px;font-size:14px;line-height:1.6;color:var(--ak-label,#475569);max-width:920px}
.etch-chips{display:flex;flex-wrap:wrap;gap:8px}
.etch-chip{display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border-radius:999px;background:#fff;border:1px solid var(--ak-border,#e2e8f0);font-size:13px;font-weight:500;color:var(--ak-text2,#334155)}
.etch-chip>svg,.etch-chip>i{color:var(--ak-fill,#006a62)}
.etch-count{flex:0 0 auto;align-self:center;text-align:right;padding-left:24px;border-left:1px solid var(--ak-border,#e2e8f0)}
.etch-count-v{font-size:26px;font-weight:700;color:var(--ak-text,#0f172a);line-height:1.1;white-space:nowrap}
.etch-count-s{margin-top:6px;font-size:13px;color:var(--ak-muted,#64748b);white-space:nowrap}
.etch-count-warn{color:#865300;font-weight:600}
.etch-count-err{color:#ba1a1a;font-weight:600}
.etch-strip{margin:0;padding:12px 16px}
.etch-strip.ak-err{border:1px solid #fecaca}.etch-strip.ak-warn{border:1px solid #fcd9a8}
.etch-strip-btn{color:inherit;font-weight:600;text-decoration:underline;white-space:nowrap}
.etch-card-h{display:flex;align-items:flex-start;justify-content:space-between;gap:24px;padding:20px 24px 16px}
.etch-h3{margin:0 0 4px;font-size:16px;font-weight:600;color:var(--ak-text,#0f172a)}
.etch-badge{display:inline-block;margin-left:6px;padding:1px 8px;border-radius:999px;background:#f1f5f9;font-size:13px;font-weight:600;color:var(--ak-label,#475569);vertical-align:middle}
.etch-sub{margin:0;font-size:13px;line-height:1.5;color:var(--ak-muted,#64748b)}
.etch-filter{flex:0 0 280px;width:280px}
.etch-tbl{width:100%}
.etch-tbl th{white-space:nowrap}
.etch-th-sub{font-weight:400;text-transform:none;letter-spacing:0;color:var(--ak-muted,#64748b)}
.etch-tbl td{padding:14px 16px}
.etch-tbl th:first-child,.etch-tbl td:first-child{padding-left:24px}
.etch-tbl th:last-child,.etch-tbl td:last-child{padding-right:24px}
.etch-row:hover td{background:#f8fafc}
.etch-row.etch-skip td{background:#fffcf5}
.etch-ident{display:flex;align-items:center;gap:12px;min-width:0}
.etch-ident-t{min-width:0}
.etch-name{font-size:14px;font-weight:600;color:var(--ak-text,#0f172a);text-decoration:none;word-break:break-all}
a.etch-name:hover,a.etch-name:focus{color:var(--ak-fill,#006a62);text-decoration:underline}
a.etch-name:focus-visible{outline:2px solid var(--ak-fill,#006a62);outline-offset:2px}
.etch-meta{margin-top:2px;font-size:13px;color:var(--ak-muted,#64748b)}
.etch-meta b{font-weight:600;color:var(--ak-text2,#334155)}
.etch-ip{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;color:var(--ak-text,#0f172a);word-break:break-all}
.etch-v6{font-size:13px}
.etch-pill{padding:3px 10px;border-radius:999px}
.etch-why{margin-top:4px;font-size:13px;color:var(--ak-muted,#64748b);max-width:340px}
.etch-skip .etch-why{color:#865300}
.etch-nomatch{border-top:1px solid var(--ak-border2,#f1f5f9)}
.etch-foot{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:12px 24px;border-top:1px solid var(--ak-border,#e2e8f0);background:var(--ak-surface,#f8fafc);font-size:13px;color:var(--ak-muted,#64748b)}
.etch-foot-dns>svg,.etch-foot-dns>i{color:var(--ak-fill,#006a62)}
.etch-empty{max-width:600px;margin:8px auto;padding:48px 24px;text-align:center;display:flex;flex-direction:column;align-items:center;gap:12px}
.etch-empty-ico{width:72px;height:72px;border-radius:50%;background:var(--ak-soft,rgba(16,185,129,.12));color:var(--ak-fill,#006a62);display:inline-flex;align-items:center;justify-content:center;font-size:32px}
.etch-empty-t{margin:4px 0 0;font-size:18px;font-weight:600;color:var(--ak-text,#0f172a)}
.etch-empty-p{margin:0 0 8px;font-size:14px;line-height:1.6;color:var(--ak-label,#475569)}
.etch-check{margin:4px 0 8px}
@media (max-width:1200px){
 .etch-hero{flex-wrap:wrap}.etch-count{flex-basis:100%;text-align:left;padding:12px 0 0;border-left:0;border-top:1px solid var(--ak-border,#e2e8f0)}
 .etch-card-h{flex-wrap:wrap}.etch-filter{flex-basis:100%;width:100%}
}
@media (prefers-reduced-motion:reduce){.etch *{transition:none!important;animation:none!important}}
</style>";
}

// ── Modale ─────────────────────────────────────────────────────────────────

function host_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!etch_can_write()){
        $tpl->js_no_privileges();
        die();
    }
    $host=$_GET["host"];
    $title="{etch_new}";
    if($host<>null){
        $ligne=etch_find($host);
        if(hosts_meta_row($ligne)){ return hosts_meta_refuse_js(); }
        if(isset($ligne["hostname"])){ $title=etch_t("etch_modal_edit",(string)$ligne["hostname"]); }
    }
    return $tpl->js_dialog1(etch_h($title), "$page?host-popup=$host&function={$_GET["function"]}");
}

function host_popup(){
    $tpl=new template_admin();
    if(!etch_can_write()){ echo $tpl->_ENGINE_parse_body($tpl->div_error("{etch_no_privs}")); return; }
    $host=$_GET["host-popup"];
    $function=preg_replace('/[^A-Za-z0-9_]/','',strval($_GET["function"] ?? ""));
    $bt="{etch_add}";
    $jsafter="dialogInstance1.close();".($function<>""?"$function();":"");
    $ligne=array("zmd5"=>"","ipaddr"=>"","hostname"=>"","alias"=>"");
    if($host<>null){
        $found=etch_find($host);
        if(hosts_meta_row($found)){ echo $tpl->_ENGINE_parse_body($tpl->div_error("{hosts_meta_row_locked}")); return; }
        if(count($found)>0){ $ligne=array_merge($ligne,$found); $bt="{etch_save}"; }
        // Ligne héritée sans zmd5 : on l'édite par son adresse.
        if(strlen((string)$ligne["zmd5"])<5){ $ligne["zmd5"]=(string)$ligne["ipaddr"]; }
    }
    $ipId=md5("ipaddr".$tpl->suffixid);
    $form=array();
    $form[]=$tpl->field_hidden("zmd5", $ligne["zmd5"]);
    $form[]=$tpl->field_ipaddr("ipaddr", "{etch_f_address}", $ligne["ipaddr"],true,"{etch_f_address_help}");
    $form[]=$tpl->field_text("hostname", "{etch_f_name}", $ligne["hostname"],true,"{etch_f_name_help}");
    $form[]=$tpl->field_text("alias", "{etch_f_alias}", $ligne["alias"],false,"{etch_f_alias_help}");
    $html=array();
    $html[]="<div id='etch-modal-$ipId'>";
    $html[]=$tpl->div_explain("{etc_hosts}||{etch_form_intro}");
    // Avertissement AVANT l'enregistrement : mêmes règles que etch_status().
    $html[]="<div id='etch-check-$ipId' class='etch-check' style='display:none'>".$tpl->div_warning("{etch_check_title}||<span id='etch-check-txt-$ipId'></span>")."</div>";
    $html[]=$tpl->form_outside("", $form,null,$bt,$jsafter,"AsDnsAdministrator");
    $html[]="</div>";
    $why=array(
        "loopback"=>etch_t("etch_why_loopback"), "reserved"=>etch_t("etch_why_reserved"),
        "short"=>etch_t("etch_why_short"), "tpl"=>etch_t("etch_check_text"),
    );
    $js="<script>
(function(){
var root=document.getElementById('etch-modal-$ipId'); if(!root){return;}
var W=".json_encode($why).";
function check(){
  var ipE=document.getElementById('$ipId'), nE=root.querySelector('[name=hostname]');
  var ip=ipE?ipE.value.replace(/\\s+/g,''):'', n=nE?nE.value.replace(/\\s+/g,''):'', r='';
  if(/(ip6-localhost|ip6-loopback|localhost|localdomain)/.test(n)){r=W.reserved;}
  else if(['::1','127.0.0.1','0.0.0.0','127.0.0.55','127.0.1.1','127.0.0.118','127.0.0.119'].indexOf(ip)>=0){r=W.loopback;}
  else if(n.length>0 && n.length<3){r=W.short;}
  var box=document.getElementById('etch-check-$ipId'), t=document.getElementById('etch-check-txt-$ipId');
  if(t){t.textContent=r?W.tpl.replace('%s',r):'';}
  if(box){box.style.display=r?'':'none';}
}
jQuery(root).on('input change','input',check);
check();
})();
</script>";
    echo $tpl->_ENGINE_parse_body($html).$js;
}

function host_save(){
    $tpl=new template_admin();
    if(!etch_can_write()){ echo $tpl->post_error("{etch_no_privs}"); return false; }
    $tpl->CLEAN_POST();
    $q=etch_db();
    $md5=$_POST["zmd5"];

    $_POST["ipaddr"]=$tpl->CLEAN_BAD_CHARSNET($_POST["ipaddr"]);
    $_POST["alias"]=$tpl->CLEAN_BAD_CHARSNET($_POST["alias"]);
    $_POST["hostname"]=$tpl->CLEAN_BAD_CHARSNET($_POST["hostname"]);
    if(trim($_POST["hostname"])==""){ echo $tpl->post_error("{etch_f_name}: {required}"); return false; }
    if(filter_var(trim($_POST["ipaddr"]),FILTER_VALIDATE_IP)===false){ echo $tpl->post_error("{etch_why_invalid}"); return false; }
    $sock=new sockets();

    if($md5==null){
        $md5=md5("{$_POST["ipaddr"]}{$_POST["hostname"]}");
        // Pentest 2026-11-04 : ipaddr, hostname et alias etaient concatenes dans la requete.
        $q->QUERY_SQL_PARAMS("INSERT OR IGNORE INTO net_hosts (`zmd5`,`ipaddr`,`hostname`,`alias`) VALUES
            (?,?,?,?)",array($md5,$_POST["ipaddr"],$_POST["hostname"],$_POST["alias"]));
        if(!$q->ok){echo $q->mysql_error;return false;}
        admin_tracks("Add a new host in hosts file {$_POST["hostname"]} - {$_POST["ipaddr"]}");
        $sock->REST_API("/system/hosts");
        return true;
    }
    // Une ligne posée par Artica Meta ne se modifie jamais ici (politique « Fichier des Hôtes »).
    if(hosts_meta_key_is_meta($q,(string)$md5)){ return hosts_meta_refuse_post(); }
    // Ligne héritée identifiée par son adresse (zmd5 vide en base). Le WHERE ne vise que des lignes
    // locales : sans cela, une adresse partagée avec une ligne Meta modifierait aussi celle-ci.
    $where=filter_var($md5,FILTER_VALIDATE_IP)!==false ? "ipaddr=? AND (zmd5 IS NULL OR length(zmd5)<5)" : "zmd5=?";
    $where.=hosts_meta_local_sql($q);
    $q->QUERY_SQL_PARAMS("UPDATE net_hosts SET ipaddr=?,hostname=?,alias=? WHERE $where",
        array($_POST["ipaddr"],$_POST["hostname"],$_POST["alias"],$md5));
    if(!$q->ok){echo $q->mysql_error;return false;}

    $sock->REST_API("/system/hosts");
    return admin_tracks("Edit a host in hosts file {$_POST["hostname"]} - {$_POST["ipaddr"]}");
}

// ── Suppression ────────────────────────────────────────────────────────────

function host_delete_js(){
    $tpl=new template_admin();
    if(!etch_can_write()){ $tpl->js_no_privileges(); return; }
    $host=$_GET["host-delete"];
    $ligne=etch_find($host);
    if(hosts_meta_row($ligne)){ hosts_meta_refuse_js(); return; }
    $label=isset($ligne["hostname"]) ? $ligne["hostname"]." &rarr; ".$ligne["ipaddr"] : $host;
    $tpl->js_confirm_delete("{etch_remove_confirm}<br><strong>".etch_h($label)."</strong>", "host-delete", $host, etch_refresh_fn()."();", true);
}

function host_delete():bool{
    $tpl=new template_admin();
    if(!etch_can_write()){ echo trim($tpl->_ENGINE_parse_body("{etch_no_privs}")); return false; }
    $q=etch_db();
    $host=$_POST["host-delete"];
    $ligne=etch_find($host);
    $hostname=isset($ligne["hostname"]) ? $ligne["hostname"] : $host;
    // Une ligne posée par Artica Meta ne se supprime jamais ici (réponse lue par js_confirm_delete).
    if(hosts_meta_key_is_meta($q,(string)$host)){ echo trim($tpl->javascript_parse_text("{hosts_meta_row_locked}")); return false; }
    $local=hosts_meta_local_sql($q);
    // Pentest 2026-11-04 : les deux DELETE concatenaient la cle.
    $q->QUERY_SQL_PARAMS("DELETE FROM net_hosts WHERE zmd5=?$local",array($host));
    if(!$q->ok){echo $q->mysql_error;return false;}
    // Lignes héritées sans zmd5 : identifiées par leur adresse.
    if(filter_var($host,FILTER_VALIDATE_IP)!==false){
        $q->QUERY_SQL_PARAMS("DELETE FROM net_hosts WHERE ipaddr=? AND (zmd5 IS NULL OR length(zmd5)<5)$local",array($host));
    }
    $sock=new sockets();
    $sock->REST_API("/system/hosts");
    return admin_tracks("Delete $hostname host from hosts file");
}
