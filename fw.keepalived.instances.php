<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"] = new sockets();
}

include_once(dirname(__FILE__) . "/ressources/class.patch.tables.fw.inc");
// Inclusion INCONDITIONNELLE: awc_table_style()/awc_pager_js()/awc_search_bar() vivent ici.
include_once(dirname(__FILE__) . "/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
// Pentest 2026-11-04 : les identifiants d'instance et de noeud etaient recopies sans encodage dans la page (XSS) et concatenes dans la requete.
// Ce sont des entiers (ID auto-incrementes) ; une valeur vide reste vide (assistant de creation).
foreach(array("primary_node_id","primary_node-js","primary_node-enable-js","primary_node-delete-js","primary_node-stats-js","primary_node-stats",
    "edit-instance","primary_node-parameters","primary_node-parameters-final","primary_node-services","primary_node-services-list",
    "primary_node-virtualip","primary_node-virtualip-list","primary_node-trackinterfaces","primary_node-trackinterfaces-list",
    "primary_node-scripts_params","secondary_node","secondary_node-list","secondary_node-enable-js","secondary_node-delete-js") as $k){
    if(isset($_GET[$k]) && $_GET[$k]!==""){$_GET[$k]=intval($_GET[$k]);}
}
$secondary_nodeIsenable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE"));
if($secondary_nodeIsenable==1){
    echo "Failover secondary_node is installed";
    die();
}
if (isset($_GET["verbose"])) {
    $GLOBALS["VERBOSE"] = true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}
if (isset($_GET["table"])) {
    table();
    exit;
}
if (isset($_GET["primary_node-js"])) {
    primary_node_js();
    exit;
}

if (isset($_GET["new-primary_node-wizard-start"])) {
    new_primary_node_wizard_start();
    exit;
}
if (isset($_GET["new-primary_node-wizard-1"])) {
    new_primary_node_wizard_1();
    exit;
}
if (isset($_GET["new-primary_node-wizard-2"])) {
    new_primary_node_wizard_2();
    exit;
}
if (isset($_GET["new-primary_node-wizard-3"])) {
    new_primary_node_wizard_3();
    exit;
}

if (isset($_POST["SAVE_primary_node_WIZARD"])) {
    primary_node_wizard_session_vals();
    exit;
}
if (isset($_POST["new-primary_node-wizard-save"])) {
    create_primary_node_node();
    exit;
}
if (isset($_GET["edit-instance"])) {
    edit_instance();
    exit;
}

if (isset($_GET["primary_node-parameters"])) {
    primary_node_parameters();
    exit;
}
if (isset($_GET["primary_node-parameters-final"])) {
    primary_node_parameters_final();
    exit;
}

if (isset($_GET["primary_node-services"])) {
    primary_node_services();
    exit;
}
if (isset($_GET["primary_node-services-list"])) {
    primary_node_services_list();
    exit;
}
if (isset($_GET["primary_node-services-js"])) {
    primary_node_services_js();
    exit;
}
if (isset($_GET["add-primary_node-service"])) {
    add_primary_node_service();
    exit;
}
if (isset($_POST["save_primary_node_service"])) {
    save_primary_node_service();
    exit;
}
if (isset($_GET["primary_node-services-delete-js"])) {
    delete_services_js();
    exit;
}
if (isset($_POST["delete-service"])) {
    delete_service();
    exit;
}

//SCRIPT PARAMS

if (isset($_GET["primary_node-scripts-params-js"])) {
    primary_node_scripts_params_js();
    exit;
}
if (isset($_GET["add-primary_node-scripts_params"])) {
    add_primary_node_scripts_params();
    exit;
}
if (isset($_POST["save_primary_node_scripts_params"])) {
    save_primary_node_scripts_params();
    exit;
}


if (isset($_GET["primary_node-virtualip"])) {
    primary_node_virtualip();
    exit;
}
if (isset($_GET["primary_node-virtualip-list"])) {
    primary_node_virtualip_list();
    exit;
}
if (isset($_GET["primary_node-virtualip-js"])) {
    primary_node_virtualip_js();
    exit;
}
if (isset($_GET["add-primary_node-virtualip"])) {
    add_primary_node_virtualip();
    exit;
}
if (isset($_POST["save_primary_node_virtualip"])) {
    save_primary_node_virtualip();
    exit;
}
if (isset($_GET["primary_node-virtualip-delete-js"])) {
    delete_virtualip_js();
    exit;
}
if (isset($_POST["delete-virtualip"])) {
    delete_virtualip();
    exit;
}

if (isset($_GET["secondary_node"])) {
    secondary_node();
    exit;
}
if (isset($_GET["secondary_node-list"])) {
    secondary_node_list();
    exit;
}
if (isset($_GET["secondary_node-js"])) {
    secondary_node_js();
    exit;
}
if (isset($_GET["add-secondary_node"])) {
    add_secondary_node();
    exit;
}
if (isset($_POST["save_secondary_node"])) {
    save_secondary_node();
    exit;
}
if (isset($_GET["secondary_node-delete-js"])) {
    delete_secondary_node_js();
    exit;
}
if (isset($_POST["delete-secondary_node"])) {
    delete_secondary_node();
    exit;
}

if (isset($_GET["primary_node-trackinterfaces"])) {
    primary_node_trackinterfaces();
    exit;
}
if (isset($_GET["primary_node-trackinterfaces-list"])) {
    primary_node_trackinterfaces_list();
    exit;
}
if (isset($_GET["primary_node-trackinterfaces-js"])) {
    primary_node_trackinterfaces_js();
    exit;
}
if (isset($_GET["add-primary_node-trackinterfaces"])) {
    add_primary_node_trackinterfaces();
    exit;
}
if (isset($_POST["save_primary_node_trackinterfaces"])) {
    save_primary_node_trackinterfaces();
    exit;
}
if (isset($_GET["primary_node-trackinterfaces-delete-js"])) {
    delete_trackinterfaces_js();
    exit;
}
if (isset($_POST["delete-trackinterfaces"])) {
    delete_trackinterfaces();
    exit;
}


if (isset($_POST["primary_node_id"])) {
    primary_node_parameters_save();
    exit;
}

if (isset($_GET["primary_node-enable-js"])) {
    primary_node_enable_js();
    exit;
}

if (isset($_POST["primary_node-enable"])) {
    primary_node_enable();
    exit;
}

if (isset($_GET["primary_node-delete-js"])) {
    primary_node_delete_js();
    exit;
}
if (isset($_POST["primary_node-delete"])) {
    primary_node_delete();
    exit;
}
if (isset($_GET["secondary_node-enable-js"])) {
    secondary_node_enable_js();
    exit;
}

if (isset($_POST["secondary_node-enable"])) {
    secondary_node_enable();
    exit;
}

if (isset($_GET["secondary_node-delete-js"])) {
    secondary_node_delete_js();
    exit;
}
if (isset($_POST["secondary_node-delete"])) {
    secondary_node_delete();
    exit;
}

page();


// keepalived_progress_js : barre de progression après un enregistrement.
// Le div primary_node_save n'existe que dans la fenêtre d'une instance ;
// ouverte depuis le tableau (fiche d'un nœud secondaire, par exemple), la
// fenêtre fermée l'emporte et fw.progress.php affichait
// « div layer primary_node_save not found » sans rafraîchir la liste (RWE 29/10).
// On se rabat alors sur le div de progression de la page.
function keepalived_progress_js(string $prgress):string{
    $url="fw.progress.php?".fw_progress_args_b64($prgress);
    return "Loadjs('$url&mainid='+(document.getElementById('primary_node_save')?'primary_node_save':'progress-keepalived-restart'))";
}

// ---------------------------------------------------------------------------
// Toutes les donnees viennent d'articarest (/keepalived/*): la page ne lit plus
// keepalived.db et ne passe plus par une classe. Les lignes sont des tableaux
// aux noms de colonnes de la base (ceux du JSON d'articarest).
// ---------------------------------------------------------------------------

/** GET articarest, rend le JSON decode (Status/Error toujours presents). */
function keepalived_rest_get(string $route): array{
    $raw = $GLOBALS["CLASS_SOCKETS"]->REST_API($route);
    $json = json_decode((string)$raw, true);
    if (!is_array($json)) { return array("Status"=>false, "Error"=>trim((string)$raw)); }
    $json["Status"] = !empty($json["Status"]);
    if (!isset($json["Error"])) { $json["Error"] = (string)($json["error"] ?? ""); }
    return $json;
}

/**
 * POST JSON articarest. Les nombres partent en texte: les champs numeriques du
 * daemon (flexInt) acceptent "7", ses champs texte refusent un nombre JSON
 * (synckey = microtime(true) est un flottant).
 */
function keepalived_rest_post(string $route, array $data): array{
    foreach ($data as $k => $v) {
        if (is_int($v) || is_float($v)) { $data[$k] = (string)$v; }
        if (is_bool($v)) { $data[$k] = $v ? "1" : "0"; }
        if (is_null($v)) { $data[$k] = ""; }
    }
    $raw = $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON($route, $data);
    $json = json_decode((string)$raw, true);
    if (!is_array($json)) {
        $raw = trim((string)$raw);
        return array("Status"=>false, "id"=>0, "Error"=>($raw == "" ? "articarest: no answer" : $raw));
    }
    return array("Status"=>!empty($json["Status"]), "id"=>intval($json["id"] ?? 0),
        "Error"=>(string)($json["Error"] ?? $json["error"] ?? ""));
}

/** Affiche l'erreur d'un POST rate; rend true si l'appel a reussi. */
function keepalived_rest_ok(array $r): bool{
    if ($r["Status"]) { return true; }
    echo (new template_admin())->post_error($r["Error"] == "" ? "articarest: request failed" : $r["Error"]);
    return false;
}

/** Une instance VRRP (ligne de keepalived_primary_nodes), vide si absente. */
function keepalived_node($primary_node_id): array{
    $primary_node_id = intval($primary_node_id);
    if ($primary_node_id == 0) { return array(); }
    $r = keepalived_rest_get("/keepalived/node?id=$primary_node_id");
    if (!$r["Status"] || !is_array($r["node"] ?? null) || intval($r["node"]["ID"] ?? 0) == 0) { return array(); }
    return $r["node"];
}

/** L'instance est geree par un maitre distant: formulaires en lecture seule. */
function keepalived_node_readonly(array $node): bool{
    if (count($node) == 0) { return false; }
    return intval($node["isPrimaryNode"]) == 0 && intval($node["secondaryNodeIsDisconnected"]) == 0;
}

/** Enregistre une instance: $node est la ligne complete (POST /keepalived/instance/save). */
function keepalived_node_save(array $node): array{
    $node["id"] = intval($node["ID"] ?? 0);
    unset($node["ID"]);
    return keepalived_rest_post("/keepalived/instance/save", $node);
}

/**
 * Les lignes rattachees a une instance.
 * $kind: services, vips, tracks, secondary/nodes.
 */
function keepalived_children(string $kind, $primary_node_id): array{
    $keys = array("services"=>"services", "vips"=>"virtual_ips",
        "tracks"=>"track_interfaces", "secondary/nodes"=>"secondary_nodes");
    $r = keepalived_rest_get("/keepalived/$kind?primaryid=" . intval($primary_node_id));
    if (!$r["Status"] || !is_array($r[$keys[$kind]] ?? null)) { return array(); }
    return $r[$keys[$kind]];
}

/** Une ligne rattachee, par ID; vide si absente. */
function keepalived_child(string $kind, $primary_node_id, $id): array{
    $id = intval($id);
    if ($id == 0) { return array(); }
    foreach (keepalived_children($kind, $primary_node_id) as $ligne) {
        if (intval($ligne["ID"] ?? 0) == $id) { return $ligne; }
    }
    return array();
}

/** Controles d'unicite (kind: vip, vnic, service, track, secondary, priority). */
function keepalived_duplicates(string $kind, $value, $primary_node_id = 0): int{
    $r = keepalived_rest_get("/keepalived/duplicates?kind=" . urlencode($kind)
        . "&value=" . urlencode((string)$value) . "&primaryid=" . intval($primary_node_id));
    return $r["Status"] ? intval($r["count"] ?? 0) : 0;
}

/** Les services surveillables: nom => commande de test (derivee cote articarest). */
function keepalived_services_available(): array{
    $r = keepalived_rest_get("/keepalived/services?primaryid=0");
    if ($r["Status"] && is_array($r["available"] ?? null) && count($r["available"]) > 0) {
        ksort($r["available"]);
        return $r["available"];
    }
    return array("Proxy"=>"/bin/pidof squid", "SMTP"=>"/bin/pidof master", "HACluster"=>"/bin/pidof hacluster",
        "HAProxy"=>"/bin/pidof haproxy", "Unbound"=>"/bin/pidof unbound", "PowerDNS"=>"/bin/pidof pdns_server",
        "DNSFirewall"=>"/bin/pidof dnsdist", "DNSCache"=>"/bin/pidof dnscache", "Nginx"=>"/bin/pidof nginx");
}

/** Liste nom => nom pour field_array_hash(). */
function keepalived_services_choices(): array{
    $choices = array();
    foreach (keepalived_services_available() as $name => $cmd) { $choices[$name] = $name; }
    return $choices;
}

function page(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    // Same header as the Load-Balancer lists (fw.haproxy.services.php). The ids
    // table-keepalived-services and progress-keepalived-restart are kept: the
    // dialogs reload the list and follow progress through them.
    $html = $tpl->page_header("{APP_KEEPALIVED} &raquo; {services}", "fas fa-shield-halved", "{APP_KEEPALIVED_INSTANCES}",
        "$page?table=yes", "failover-services", "progress-keepalived-restart", false, "table-keepalived-services");

    if (isset($_GET["main-page"])) {
        $tpl = new template_admin(null, $html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

function primary_node_enable_js()
{

    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node-enable-js"];
    $value = "$primary_node_id";
    $keepalived_node = keepalived_node($primary_node_id);
    $name = req_html($keepalived_node["primary_node_name"] ?? "");

    if (intval($keepalived_node["enable"] ?? 0) == 1) {
        $action_text = "disable";
        $action = "Disable $name";
    } else {
        $action_text = "enable";
        $action = "Enable $name";
    }
    $extra = "";
    if (intval($keepalived_node["isPrimaryNode"] ?? 0) == 1) {
        $extra = "This operation will $action_text all secondary_nodes as well.";
    }
    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "/keepalived/reconfigure";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsrestart = "Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-keepalived-restart')";


    $tpl->js_confirm_execute("$action. $extra", "primary_node-enable", $value, $jsrestart);
}

function primary_node_enable()
{

    $keepalived_node = keepalived_node($_POST["primary_node-enable"]);
    if (count($keepalived_node) == 0) {
        echo (new template_admin())->post_error("Instance not found");
        return false;
    }
    $keepalived_node["enable"] = (intval($keepalived_node["enable"]) == 1) ? 0 : 1;
    return keepalived_rest_ok(keepalived_node_save($keepalived_node));
}

function primary_node_delete_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $instancename = $_GET["primary_node-name"];
    $primary_node_id = $_GET["primary_node-delete-js"];
    // Pentest 2026-11-04 : primary_node-name etait recopie sans encodage dans la page (XSS).
    $value = "$instancename|$primary_node_id"; // js_confirm_delete() encode deja la valeur (req_js)
    $instancename = req_html($instancename);
    $keepalived_node = keepalived_node($primary_node_id);
    $extra = "";
    if (intval($keepalived_node["isPrimaryNode"] ?? 0) == 1) {
        $extra = "<br><strong>This operation will delete all secondary_nodes as well</strong>";
    }


    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "/keepalived/reconfigure";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsrestart = "Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-keepalived-restart')";
    $tpl->js_confirm_delete("$instancename/$primary_node_id $extra", "primary_node-delete", $value, $jsrestart);

}

function primary_node_delete()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $f = explode("|", $_POST["primary_node-delete"]);
    // frommaster: articarest envoie l'ordre de suppression aux noeuds secondaires
    // (comme le faisait class.keepalived.inc).
    return keepalived_rest_ok(keepalived_rest_post("/keepalived/instance/delete",
        array("id" => intval($f[1] ?? 0), "frommaster" => true)));
}

function secondary_node_enable_js()
{

    $page = CurrentPageName();
    $tpl = new template_admin();
    $secondary_node_id = $_GET["secondary_node-enable-js"];
    $primary_node_id = $_GET["primary_node_id"];
    $value = "$primary_node_id|$secondary_node_id";
    $keepalived_node = keepalived_child("secondary/nodes", $primary_node_id, $secondary_node_id);
    $ip = req_html($keepalived_node["secondary_node_ip"] ?? "");

    if (intval($keepalived_node["enable"] ?? 0) == 1) {
        $action = "Disable secondary_node $ip";
    } else {
        $action = "Enable secondary_node $ip";
    }


    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "/keepalived/reconfigure";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsrestart = "Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-keepalived-restart')";
    $tpl->js_confirm_execute("$action.", "secondary_node-enable", $value, $jsrestart);
}

function secondary_node_enable()
{
    $ids = explode("|", $_POST["secondary_node-enable"]);
    $ligne = keepalived_child("secondary/nodes", $ids[0] ?? 0, $ids[1] ?? 0);
    if (count($ligne) == 0) {
        echo (new template_admin())->post_error("Secondary node not found");
        return false;
    }
    $ligne["enable"] = (intval($ligne["enable"]) == 1) ? 0 : 1;
    return keepalived_rest_ok(keepalived_secondary_save($ligne));
}

/** Enregistre un noeud secondaire: $ligne est la ligne complete. */
function keepalived_secondary_save(array $ligne): array{
    $data = array("primaryid" => intval($ligne["primary_node_id"] ?? 0), "id" => intval($ligne["ID"] ?? 0));
    foreach (array("secondary_node_ip", "secondary_node_port", "primary_node_ip",
                 "secondary_node_can_overwrite_settings", "enable", "synckey", "nopreempt",
                 "preempt_delay", "priority", "rewriteInterface") as $f) {
        $data[$f] = $ligne[$f] ?? "";
    }
    return keepalived_rest_post("/keepalived/secondary/save", $data);
}

function secondary_node_delete_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node_id"];
    $secondary_node_id = $_GET["secondary_node-delete-js"];
    $value = "$primary_node_id|$secondary_node_id";
    $keepalived_node = keepalived_child("secondary/nodes", $primary_node_id, $secondary_node_id);
    $extra = "Remove Slave " . req_html($keepalived_node["secondary_node_ip"] ?? "") . " ";

    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "/keepalived/reconfigure";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsrestart = "Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-keepalived-restart')";
    $tpl->js_confirm_delete("$extra", "secondary_node-delete", $value, $jsrestart);

}

function secondary_node_delete()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $f = explode("|", $_POST["secondary_node-delete"]);
    return keepalived_rest_ok(keepalived_rest_post("/keepalived/secondary/delete",
        array("primaryid" => intval($f[0] ?? 0), "id" => intval($f[1] ?? 0))));
}

// Statistics: fw.keepalived.stats.php (graphs and current counters).

function primary_node_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $new_service = $tpl->_ENGINE_parse_body("{new_instance}");
    $primary_node_id = urlencode($_GET["primary_node-js"]);
    if ($primary_node_id == null) {
        $title = $new_service;
        $tpl->js_dialog2($title, "$page?new-primary_node-wizard-start=yes");
        return;
    }

    // Pentest 2026-11-04 : title etait recopie sans encodage dans la page (XSS).
    $title = req_html(req_html($_GET["title"])); // titre de fenetre : javascript_parse_text() decode une fois les entites
    $tpl->js_dialog1($title, "$page?edit-instance=$primary_node_id");
}

function edit_instance():bool{

    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["edit-instance"];
    $array["{parameters}"] = "$page?primary_node-parameters=$primary_node_id";
    $array["{services}"] = "$page?primary_node-services=$primary_node_id";
    $array["{virtual_ip}"] = "$page?primary_node-virtualip=$primary_node_id";
    $array["{secondary_nodes}"] = "$page?secondary_node=$primary_node_id";
    $array["{track_interfaces}"] = "$page?primary_node-trackinterfaces=$primary_node_id";
    $masterInfo=keepalived_node($_GET["edit-instance"]);
    $text="{master}";
    if(intval($masterInfo["secondaryNodeIsDisconnected"] ?? 0)==1){
        $text="{management_by} {primary_node_keepalived}";

    }
    if(count($masterInfo)>0 && intval($masterInfo["isPrimaryNode"])==0){
        $title= '<h3><strong class="text-success">'.$text.' '.req_html($masterInfo["primaryNodeIP"]).'</strong></h3>';
        echo $tpl->_ENGINE_parse_body($title);
    }
    echo $tpl->tabs_default($array);
    return true;
}

//ADD INSTANCE Master
function new_primary_node_wizard_start()
{
    $page = CurrentPageName();
    echo "<div id='wizard-primary_node-for-steps'></div>
		<script>LoadAjax('wizard-primary_node-for-steps','$page?new-primary_node-wizard-1=yes');</script>
	";
}

function new_primary_node_wizard_1():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $hostname = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname");

    $tpl->field_hidden("SAVE_primary_node_WIZARD", 1);
    $form[] = $tpl->field_text("primary_node_name", "{instance} {hostname} / {ipaddr}", isset($_SESSION["MASTER"]["hostname"]) ? $_SESSION["MASTER"]["hostname"] : $hostname, true);
    $form[] = $tpl->field_interfaces('interface', "nooloopNoDef:{listen_interface}", $_SESSION["MASTER"]["interface"]);
    $form[] = $tpl->field_hidden("primary_node_state", "MASTER");
    $form[] = $tpl->field_hidden("virtual_router_id", 30);
    $form[] = $tpl->field_hidden("priority", 200);
    $form[] = $tpl->field_hidden("advert_int", 1);
    $form[] = $tpl->field_array_hash(keepalived_services_choices(), "service", "{monitor_service}", isset($_SESSION["MASTER"]["service"]) ? $_SESSION["MASTER"]['service'] : 'Proxy', true, "{monitor_service_explain}",null,false);
    $jsafter = "LoadAjax('wizard-primary_node-for-steps','$page?new-primary_node-wizard-2=yes');";

    $html = $tpl->form_outside("{lets_get_start}", $form, "{configure_peers_alert}", "{next}", $jsafter, "AsSquidAdministrator", true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function new_primary_node_wizard_2():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $tpl->field_hidden("SAVE_primary_node_WIZARD", 1);
    $form[] = $tpl->field_ipaddr("virtualip", "{ipaddr}", $_SESSION["MASTER"]["virtualip"], true);
    $form[] = $tpl->field_maskcdir('netmask', '{netmask}', isset($_SESSION["MASTER"]["netmask"]) ? $_SESSION["MASTER"]["netmask"] : 24, true);
    $form[] = $tpl->field_interfaces('dev', "nooloopNoDef:{local_interface_dev}", $_SESSION["MASTER"]["dev"]);

    $jsafter = "LoadAjax('wizard-primary_node-for-steps','$page?new-primary_node-wizard-3=yes');";

    // Back Button
    $tpl->form_add_button("{back}", "LoadAjax('wizard-primary_node-for-steps','$page?new-primary_node-wizard-1=yes');");

    $html = $tpl->form_outside("{now_lets_configure_the_shared_ip}", $form, "{virtual_ip_explain}", "{next}", $jsafter, "AsSquidAdministrator", true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function new_primary_node_wizard_3():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();

    //$tpl->field_hidden("SAVE_primary_node_WIZARD", 1);
    $tpl->field_hidden("new-primary_node-wizard-save", 1);
    $form[] = $tpl->field_ipv4('secondary_node_ip', '{ipaddr}', $_SESSION['MASTER']['secondary_node_ip'], true);
    $form[] = $tpl->field_numeric('secondary_node_port', '{artica_listen_port}', isset($_SESSION['MASTER']['secondary_node_port']) ? $_SESSION['MASTER']['secondary_node_port'] : 9000);


    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "/keepalived/reconfigure";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "dialogInstance2.close();LoadAjax('table-keepalived-services','$page?table=yes');";
    $prgress = base64_encode(serialize($ARRAY));
    $jsrestart = "Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-keepalived-restart')";


    // Back Button

    $tpl->form_add_button("{back}", "LoadAjax('wizard-primary_node-for-steps','$page?new-primary_node-wizard-2=yes');");

    $html = $tpl->form_outside("{almost_there_secondary_nodes}", $form, "{secondary_nodes_explain}", "{save}", $jsrestart, "AsSquidAdministrator", true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function primary_node_wizard_session_vals():bool
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();

    foreach ($_POST as $key => $value) {
        $_SESSION["MASTER"][$key] = $value;
    }
    return true;
}

function create_primary_node_node():bool{
    $unix = new unix();
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    foreach ($_POST as $key => $value) {
        $_SESSION["MASTER"][$key] = $value;
    }
    if (empty($_SESSION['MASTER']["secondary_node_port"])) {
        $_SESSION['MASTER']["secondary_node_port"] = 9000;
    }
    $ArticaHttpsPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpsPort"));
    if ($ArticaHttpsPort == 0) {
        $ArticaHttpsPort = 9000;
    }
    if ($unix->InterfaceToIPv4($_SESSION['MASTER']['interface']) == $_SESSION['MASTER']['virtualip']) {
        echo (new template_admin())->post_error("Listen IP is equal to Virtual IP");
        return false;
    }
    if ($unix->InterfaceToIPv4($_SESSION['MASTER']['interface']) == $_SESSION['MASTER']['secondary_node_ip']) {
        echo (new template_admin())->post_error("Listen IP is equal to Slave IP");
        return false;
    }

    if ($_SESSION['MASTER']['virtualip'] == $_SESSION['MASTER']['secondary_node_ip']) {
        echo (new template_admin())->post_error("Virtual IP is equal to Slave IP");
        return false;
    }
    if (!filter_var($_SESSION['MASTER']['virtualip'], FILTER_VALIDATE_IP)) {
        echo (new template_admin())->post_error("Invalid Virtual IP Address");
        return false;
    }
    if (!filter_var($_SESSION['MASTER']['secondary_node_ip'], FILTER_VALIDATE_IP)) {
        echo (new template_admin())->post_error("Invalid Slave IP Address");
        return false;
    }

    $virtualip = $_SESSION['MASTER']['virtualip'];
    if (keepalived_duplicates("vip", $virtualip) > 0 || keepalived_duplicates("vnic", $virtualip) > 0) {
        echo (new template_admin())->post_error("Virtual IP ".req_html($virtualip)." already exist");
        return false;
    }
    if (keepalived_duplicates("secondary", $_SESSION['MASTER']['secondary_node_ip']) > 0) {
        echo (new template_admin())->post_error("The Slave with ip ".req_html($_SESSION['MASTER']['secondary_node_ip'])." already exist");
        return false;
    }

    //SAVE NODE (valeurs par defaut des controles de sante: celles de l'ancienne classe)
    $primaryIP = $unix->InterfaceToIPv4($_SESSION['MASTER']['interface']);
    $priority = intval($_SESSION['MASTER']['priority']);
    $r = keepalived_node_save(array(
        "ID" => 0,
        "primary_node_name" => $_SESSION['MASTER']['primary_node_name'],
        "interface" => $_SESSION['MASTER']['interface'],
        "state" => $_SESSION['MASTER']['primary_node_state'],
        "virtual_router_id" => intval($_SESSION['MASTER']['virtual_router_id']),
        "priority" => $priority,
        "advert_int" => intval($_SESSION['MASTER']['advert_int']),
        "enable" => 1,
        "isPrimaryNode" => 1,
        "primaryNodeIP" => $primaryIP,
        "primaryNodePort" => $ArticaHttpsPort,
        "secondaryNodeIsDisconnected" => 0,
        "synckey" => microtime(true),
        "interval" => 2,
        "enableProxyCurl" => 1,
        "disklimit" => 95,
        "ramlimit" => 95,
        "loadlimit" => 4,
        "checkDisk" => 1,
        "checkRam" => 1,
        "checkLoad" => 1,
        "dnstesttimeout" => 3,
        "proxytesttimeout" => 3,
        "checkDebugLevel" => 3,
        "enableDnsResolver" => 1,
    ));
    if (!keepalived_rest_ok($r)) { return false; }
    $last_id = intval($r["id"]);
    if ($last_id == 0) {
        echo (new template_admin())->post_error("articarest did not return the new instance ID");
        return false;
    }

    //SAVE SERVICE
    $r = keepalived_rest_post("/keepalived/service/save", array(
        "primaryid" => $last_id, "id" => 0,
        "service" => $_SESSION['MASTER']['service'],
        "enable" => 1, "synckey" => microtime(true)));
    if (!keepalived_rest_ok($r)) { return false; }

    //SAVE VIP
    $r = keepalived_rest_post("/keepalived/vip/save", array(
        "primaryid" => $last_id, "id" => 0,
        "dev" => $_SESSION['MASTER']['dev'],
        "virtual_ip" => $virtualip,
        "netmask" => $_SESSION['MASTER']['netmask'],
        "enable" => 1, "synckey" => microtime(true)));
    if (!keepalived_rest_ok($r)) { return false; }

    //SAVE SLAVE (premier noeud secondaire de l'instance)
    $r = keepalived_secondary_save(array(
        "ID" => 0, "primary_node_id" => $last_id,
        "secondary_node_ip" => $_SESSION['MASTER']["secondary_node_ip"],
        "secondary_node_port" => intval($_SESSION['MASTER']["secondary_node_port"]),
        "primary_node_ip" => $primaryIP,
        "secondary_node_can_overwrite_settings" => 0,
        "enable" => 1,
        "synckey" => microtime(true),
        "priority" => $priority - $last_id,
        "nopreempt" => 0, "preempt_delay" => 0, "rewriteInterface" => ""));
    if (!keepalived_rest_ok($r)) { return false; }
    unset($_SESSION['MASTER']);
    return true;
}

//END ADD INSTANCE

//EDIT INSTANCE
function primary_node_parameters()
{
    $page = CurrentPageName();
    $primary_node_id = $_GET["primary_node-parameters"];
    $primary_node_id = urlencode($primary_node_id);
    $md5 = md5(time() . $primary_node_id);
    echo "<div id='primary_node_parameters_$md5'></div>
    <script>
        function InstanceMainParameters(){
            LoadAjax('primary_node_parameters_$md5','$page?primary_node-parameters-final=$primary_node_id')
            
        }
        InstanceMainParameters();
    </script>
    ";
}

function primary_node_parameters_final()
{

    $tt = $_GET["t"];
    $users = new usersMenus();
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node-parameters-final"];
    $keepalived_node = keepalived_node($primary_node_id);
    $buttonname = "{apply}";
    if ($primary_node_id == null) {
        $buttonname = "{add}";
    }
    $title = "{edit_VRRP_instance}";
    //$jsafter[] = "LoadAjax('table-keepalived-services','$page?table=yes');";


    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "/keepalived/reconfigure";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('table-keepalived-services','$page?table=yes');";
    $prgress = base64_encode(serialize($ARRAY));
    $jsafter = "".keepalived_progress_js($prgress);

    $isDisable = false;

    if (keepalived_node_readonly($keepalived_node)) {
        $tpl->KEEPALIVED_CLI = true;
        $isDisable = true;
        $jsafter = "blur()";
    }
    $n = $keepalived_node;
    $form[] = $tpl->form_add_button_title('Expert Mode', 'switchModes(this)');
    $form[] = $tpl->field_hidden("enable", $n["enable"] ?? 0);
    $form[] = $tpl->field_hidden("primary_node_id", $n["ID"] ?? 0);
    $form[] = $tpl->field_text("primary_node_name", "{instance} {hostname} / {ipaddr}", $n["primary_node_name"] ?? "", true);
    if ($isDisable) {
        $form[] = $tpl->field_text("interface", "{listen_interface}", $n["interface"] ?? "", true, null, $isDisable, $isDisable);
    } else {
        $form[] = $tpl->field_interfaces('interface', "nooloopNoDef:{listen_interface}", $n["interface"] ?? "");
    }


    $form[] = $tpl->field_text("primary_node_state", "{state}", $n["state"] ?? "", true, null, true, true);
    //EXPERT ONLY
    $form[] = $tpl->field_numeric("virtual_router_id", '{virtual_router_id}', $n["virtual_router_id"] ?? 1, '{virtual_router_id_explain}');
    $form[] = $tpl->field_numeric("priority", '{priority}', $n["priority"] ?? 100, '{priority_explain}');
    $form[] = $tpl->field_numeric("advert_int", '{advert_int}', $n["advert_int"] ?? 1, '{advert_int_explain}');
    $form[] = $tpl->field_checkbox("no_preempt", "{nopreempt_1}", $n["nopreempt"] ?? 0, false, "{nopreempt}", $isDisable);

    $form[] = $tpl->field_numeric("preempt_delay", "{preempt_delay}", $n["preempt_delay"] ?? 0,"{preempt_delay_explain}");
    $form[] = $tpl->field_numeric("garp_master_refresh", "{garp_master_refresh}", intval($n["garp_master_refresh"] ?? 0), "{garp_master_refresh_explain}");
    $form[] = $tpl->field_checkbox("use_vmac", "{use_vmac}", $n["use_vmac"] ?? 0, "vmac_xmit_base", "{use_vmac_explain}");
    $form[] = $tpl->field_checkbox("vmac_xmit_base", "{vmac_xmit_base}", $n["vmac_xmit_base"] ?? 0, false, "{vmac_xmit_base_explain}");
    $form[] = $tpl->field_checkbox("unicast_src_ip", "{unicast_src_ip}", $n["unicast_src_ip"] ?? 0, false, "{unicast_src_ip_explain}", $isDisable);
    $form[] = $tpl->field_checkbox("enable_peers_ttl", "{enable_ttl}", $n["enable_peers_ttl"] ?? 0, "min_peers_ttl,max_peers_ttl,unicast_ttl", null, $isDisable);
    $form[] = $tpl->field_numeric("min_peers_ttl", "{min_ttl}", $n["min_peers_ttl"] ?? 0);
    $form[] = $tpl->field_numeric("max_peers_ttl", "{max_ttl_1}", $n["max_peers_ttl"] ?? 0);
    $form[] = $tpl->field_numeric("unicast_ttl", "{unicast_ttl}", intval($n["unicast_ttl"] ?? 0) > 0 ? intval($n["unicast_ttl"]) : 10, "{unicast_ttl_explain}");
    $form[] = $tpl->field_checkbox("auth_enable", "{authentication}", $n["auth_enable"] ?? 0, "auth_pass", null, $isDisable);
    $form[] = $tpl->field_password("auth_pass", "{password}", $n["auth_pass"] ?? "");
    $form[] = $tpl->field_checkbox("notifty_enable", "{enable_notify}", $n["notifty_enable"] ?? 0, "notifty", null, $isDisable);
    $form[] = $tpl->field_text("notifty", "{notify_script}", $n["notifty"] ?? "", false, "{notify_script_explain}");

    $form[] = '<script>';
    if ($isDisable == true) {
        $form[] = '
         $( document ).ready(function() {      
             $("input").attr("readonly", "readonly");
             toggle(false)
         });
        ';
    } else {
        $form[] = '
           $( document ).ready(function() {      
                toggle(false)
            });
       ';
    }

    $form[] = '

    function toggle(display){
    var fields = ["virtual_router_id","priority","advert_int","no_preempt","preempt_delay","garp_master_refresh","unicast_ttl",
        "unicast_src_ip","enable_peers_ttl","min_peers_ttl","max_peers_ttl",
        "auth_enable","auth_pass","notifty_enable","notifty","use_vmac","vmac_xmit_base"];
    fields.forEach(function(n){
        var $tr = $("[name=" + n + "]").closest("tr");
        $tr.toggle(display);
        $tr.find(".afrm-h").toggle(display);
        $tr.next("tr").filter(function(){
            return $(this).find(".afrm-h").length && !$(this).find("input,select,textarea").length;
        }).toggle(display);
    });
}
    function switchModes(btn){
            const $label = $(btn).find("span");
    const btnName = $label.text().trim();
        
        if(btnName=="Expert Mode"){
            $label.text("Basic Mode");
            toggle(true)
        }
       if(btnName=="Basic Mode"){
            $label.text("Expert Mode");
            toggle(false)
        }
    }
    </script>';

    $html = "<div id='primary_node_save'></div>" . $tpl->form_outside($title, $form, null, $buttonname, $jsafter, "AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);

}

function primary_node_parameters_save()
{
    $unix = new unix();

    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $keepalived_node = keepalived_node($_POST['primary_node_id']);
    if (count($keepalived_node) == 0) {
        echo (new template_admin())->post_error("Instance not found");
        return false;
    }


    if ($_POST["auth_enable"] == 1) {
        if (empty($_POST["auth_pass"])) {
            echo (new template_admin())->post_error("Empty password");
            return false;
        }
    }

    if ($_POST["notifty_enable"] == 1) {
        if (empty($_POST["notifty"])) {
            echo (new template_admin())->post_error("Empty notify script");
            return false;
        }
    }

    if (empty($_SESSION['MASTER']["secondary_node_port"])) {
        $_SESSION['MASTER']["secondary_node_port"] = 9000;
    }
    $ArticaHttpsPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpsPort"));
    if ($ArticaHttpsPort == 0) {
        $ArticaHttpsPort = 9000;
    }

    if ($_POST["enable_peers_ttl"] == 1) {
        if (empty($_POST["min_peers_ttl"]) || intval($_POST["min_peers_ttl"])==0) {
            echo (new template_admin())->post_error("Empty or 0 {min_ttl}");
            return false;
        }
        if (empty($_POST["max_peers_ttl"]) || intval($_POST["max_peers_ttl"])==0) {
            echo (new template_admin())->post_error("Empty or 0 {max_ttl}");
            return false;
        }
    }

    if(intval($_POST['no_preempt'])==1 && intval($_POST['preempt_delay'])>0){
        echo (new template_admin())->post_error("".$tpl->javascript_parse_text("{preempt_delay_alert}"));
        return false;
    }

    $keepalived_node["primary_node_name"] = $_POST['primary_node_name'];
    $keepalived_node["interface"] = $_POST['interface'];
    $keepalived_node["virtual_router_id"] = intval($_POST['virtual_router_id']);
    $keepalived_node["priority"] = intval($_POST['priority']);
    $keepalived_node["advert_int"] = intval($_POST['advert_int']);
    $keepalived_node["preempt_delay"] = intval($_POST['preempt_delay']);
    if(intval($_POST['preempt_delay'])>0){
        $_POST['no_preempt']=0;
    }
    $keepalived_node["nopreempt"] = intval($_POST['no_preempt']);

    $keepalived_node["unicast_src_ip"] = intval($_POST['unicast_src_ip']);
    $keepalived_node["enable_peers_ttl"] = intval($_POST['enable_peers_ttl']);
    $keepalived_node["max_peers_ttl"] = intval($_POST['max_peers_ttl']);
    $keepalived_node["min_peers_ttl"] = intval($_POST['min_peers_ttl']);
    $keepalived_node["auth_enable"] = intval($_POST['auth_enable']);
    $keepalived_node["auth_pass"] = $_POST['auth_pass'];
    $keepalived_node["notifty_enable"] = intval($_POST['notifty_enable']);
    $keepalived_node["notifty"] = $_POST['notifty'];
    $keepalived_node["enable"] = intval($_POST['enable']);
    $keepalived_node["primaryNodeIP"] = $unix->InterfaceToIPv4($_POST['interface']);
    $keepalived_node["primaryNodePort"] = $ArticaHttpsPort;
    $keepalived_node["secondaryNodeIsDisconnected"] = 0;
    $keepalived_node["use_vmac"] = intval($_POST['use_vmac']);
    $keepalived_node["vmac_xmit_base"] = intval($_POST['vmac_xmit_base']);
    $keepalived_node["garp_master_refresh"] = max(0, intval($_POST['garp_master_refresh'] ?? 0));
    $keepalived_node["unicast_ttl"] = intval($_POST['unicast_ttl'] ?? 10);
    return keepalived_rest_ok(keepalived_node_save($keepalived_node));
}

//END EDIT INSTANCE

//SERVICES
function primary_node_services(){
    $primary_node_id = $_GET["primary_node-services"];
    $tpl = new template_admin();
    $page = CurrentPageName();
    $primary_node_info = keepalived_node($primary_node_id);
    $isDisable = '';
    $go_failver_checker_ver=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("go-failover-checker-ver");
    if (empty($go_failver_checker_ver)){
        $go_failver_checker_ver="0.0";
    }
    $js = "Loadjs('$page?primary_node-services-js=0&primary_node_id=$primary_node_id');";
    $jsparams = "Loadjs('$page?primary_node-scripts-params-js=0&primary_node_id=$primary_node_id');";
    if (intval($primary_node_info["isPrimaryNode"] ?? 1) == 0) {
        if (intval($primary_node_info["secondaryNodeIsDisconnected"] ?? 0) == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = 'disabled';
            $js = "blur()";
        }
    }
    echo $tpl->_ENGINE_parse_body("
<p style=\"margin-top: 10px\">{monitor_service_explain}<br><b>Failover Checker version $go_failver_checker_ver</b></p>
<div id='primary_node_save'></div>
			<div style='margin:15px 0 5px 0'>" . $tpl->table_buttons(kai_toolbar($js, "{new_service}", $jsparams, $isDisable)) . "</div>");

    echo "<div id='primary_node-services'></div>
	<script>
		LoadAjax('primary_node-services','$page?primary_node-services-list=$primary_node_id');
	</script>	
	";

}

function primary_node_services_list()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node-services-list"];
    $t = time();
    $primary_node_info = keepalived_node($primary_node_id);
    $isDisable = false;

    if (intval($primary_node_info["isPrimaryNode"] ?? 1) == 0) {
        if (intval($primary_node_info["secondaryNodeIsDisconnected"] ?? 0) == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = true;
        }
    }
    $html[] = $tpl->_ENGINE_parse_body("
			<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[] = "<thead>";
    $html[] = "<tr>";
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text'>{monitor_service}</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</center></th>");

    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";


    // La liste vient du point d'entree: meme ordre, plus de requete.
    $results = keepalived_children("services", $primary_node_id);
    $TRCLASS = null;
    foreach ($results as $index => $ligne) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = "footable-even";
        } else {
            $TRCLASS = "footable-odd";
        }
        $md = md5(serialize($ligne));
        $text_class = null;


        $url = "Loadjs('$page?primary_node-services-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}');";
        $href = "<a href=\"javascript:blur()\" OnClick=\"$url\" style='font-weight:bold'>";
        if ($isDisable) {
            $delete = "";
        } else {
            $delete = $tpl->icon_delete("Loadjs('$page?primary_node-services-delete-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}&md=$md&service={$ligne["service"]}')", "AsSystemAdministrator");
        }
        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">$href{$ligne["service"]}</a></td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\" class='center'>$delete</td>");
        $html[] = "</tr>";
    }

    $html[] = "</tbody>";
    $html[] = "<tfoot>";

    $html[] = "<tr>";
    $html[] = "<td colspan='2'>";
    $html[] = "<ul class='pagination pull-right'></ul>";
    $html[] = "</td>";
    $html[] = "</tr>";
    $html[] = "</tfoot>";
    $html[] = "</table>";
    $html[] = "
		<script>
		NoSpinner();\n" . @implode("\n", $tpl->ICON_SCRIPTS) . "
		$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
		</script>";

    echo @implode("\n", $html);
}

function primary_node_services_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node_id"];
    $service_id = intval($_GET["primary_node-services-js"]);
    $service_id_text = "{edit_service}";
    if ($service_id == 0) {
        $service_id_text = "{new_service}";
    }

    $title = "$service_id_text";
    $tpl->js_dialog2($title, "$page?add-primary_node-service=$service_id&primary_node_id=$primary_node_id");
}

function add_primary_node_service()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node_id"];
    $service_id = intval($_GET["add-primary_node-service"]);
    $service_id_text = "{edit_service}";
    if ($service_id == 0) {
        $service_id_text = "{new_service}";
    }

    $title = "$service_id_text";
    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "/keepalived/reconfigure";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('primary_node-services','$page?primary_node-services-list=$primary_node_id');LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsafter = "dialogInstance2.close();".keepalived_progress_js($prgress);

    $title_button = "{add}";
    if ($service_id > 0) {
        //$jsafter = "dialogInstance2.close();LoadAjax('primary_node-services','$page?primary_node-services-list=$primary_node_id');";
        // Lecture unitaire par le point d'entree, filtree sur l'instance.
        $results = array(keepalived_child("services", $primary_node_id, $service_id));
        $ligne = $results[0];
        $title_button = "{apply}";
    } else {
        $ligne["service"] = 'Proxy';
    }
    $primary_node_info = keepalived_node($primary_node_id);
    $SCRIPTS = keepalived_services_available();
    $isDisable = false;

    if (intval($primary_node_info["isPrimaryNode"] ?? 1) == 0) {
        if (intval($primary_node_info["secondaryNodeIsDisconnected"] ?? 0) == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = true;
            $jsafter = "blur()";
        }
    }


    $form[] = $tpl->field_hidden("save_primary_node_service", $primary_node_id);
    $form[] = $tpl->field_hidden("service_id", $service_id);
    $form[] = $tpl->field_array_hash(keepalived_services_choices(), "service", "{monitor_service}", "{$ligne["service"] }", true, "{monitor_service_explain}", "KaScriptShow", false);

    // La commande de test, en lecture seule: derivee du service et lancee en
    // root par keepalived. L'afficher evite d'avoir a deviner ce que
    // "surveiller Proxy" declenche reellement.
    $cmd = htmlspecialchars($SCRIPTS[(string)($ligne["service"] ?? "")] ?? "");
    $form[] = "<div style='display:flex;align-items:baseline;gap:10px;margin:2px 0 10px'>"
        . "<span style='font-size:12px;color:#6c7b6d;text-transform:uppercase;"
        . "letter-spacing:.05em;white-space:nowrap'>" . $tpl->_ENGINE_parse_body("{command}") . "</span>"
        . "<code id='ka-script' style='font-size:13px;color:#3d4947'>$cmd</code></div>";
    $form[] = "<script>
    var KA_SCRIPTS = " . json_encode($SCRIPTS) . ";
    function KaScriptShow(v){
        var e = document.getElementById('ka-script');
        if (!e) { return; }
        e.innerHTML = (KA_SCRIPTS[v] === undefined) ? '' : KA_SCRIPTS[v];
    }
    </script>";

    $form[] = '<script>';
    if ($isDisable) {
        $form[] = '
         $( document ).ready(function() {      
             $("input").attr("readonly", "readonly");
         });
        ';
    }
    $form[] = '</script>';


    $security = "AsSystemAdministrator";
    $html = "<div id='primary_node_save'></div>" . $tpl->form_outside($title, $form, null, $title_button, $jsafter, $security);
    echo $tpl->_ENGINE_parse_body($html);
}

function save_primary_node_service()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    // La commande de test (script) n'est pas envoyee: articarest la derive du service.
    return keepalived_rest_ok(keepalived_rest_post("/keepalived/service/save", array(
        "primaryid" => intval($_POST['save_primary_node_service']),
        "id" => intval($_POST['service_id']),
        "service" => $_POST['service'],
        "enable" => 1,
        "synckey" => microtime(true))));
}

/**
 * Supprime une ligne rattachee (service, vip, track). frommaster: articarest
 * transmet la suppression aux noeuds secondaires, identifies par le synckey de
 * la ligne (comme le faisait class.keepalived.inc).
 */
function keepalived_child_delete(string $kind, string $listkind, $primary_node_id, $id): bool{
    $ligne = keepalived_child($listkind, $primary_node_id, $id);
    return keepalived_rest_ok(keepalived_rest_post("/keepalived/$kind/delete", array(
        "primaryid" => intval($primary_node_id),
        "id" => intval($id),
        "synckey" => (string)($ligne["synckey"] ?? ""),
        "frommaster" => true)));
}

function delete_services_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = intval($_GET["primary_node_id"]);
    $service_id = intval($_GET["primary_node-services-delete-js"]);
    $value = "$primary_node_id|$service_id";

    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "/keepalived/reconfigure";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('primary_node-services','$page?primary_node-services-list=$primary_node_id');LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsafter = "dialogInstance2.close();".keepalived_progress_js($prgress);
    // Pentest 2026-11-04 : service etait recopie sans encodage dans la page (XSS).
    $tpl->js_confirm_delete(req_html($_GET["service"]), "delete-service", $value, $jsafter);
}

function delete_service()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $f = explode("|", $_POST["delete-service"]);
    return keepalived_child_delete("service", "services", $f[0] ?? 0, $f[1] ?? 0);
}
//END SERVICES


//VIRTUAL IPS
function primary_node_virtualip():bool{
    $primary_node_id = $_GET["primary_node-virtualip"];
    $tpl = new template_admin();
    $page = CurrentPageName();
    $primary_node_info = keepalived_node($primary_node_id);
    $jsafter = "Loadjs('$page?primary_node-virtualip-js=0&primary_node_id=$primary_node_id')";
    $isDisable = '';

    if (intval($primary_node_info["isPrimaryNode"] ?? 1) == 0) {
        if (intval($primary_node_info["secondaryNodeIsDisconnected"] ?? 0) == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = 'disabled';
            $jsafter = "blur()";
        }
    }


    echo $tpl->_ENGINE_parse_body("
<p style=\"margin-top: 10px\">{keepalived_virtualip}</p>
<div id='primary_node_save'></div>
			<div style='margin:15px 0 5px 0'>" . $tpl->table_buttons(kai_toolbar($jsafter, "{new_virtualip}", '', $isDisable)) . "</div>");

    echo "<div id='primary_node-virtualip'></div>
	<script>
		LoadAjax('primary_node-virtualip','$page?primary_node-virtualip-list=$primary_node_id');
	</script>	
	";
    return true;
}

function primary_node_virtualip_list()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node-virtualip-list"];
    $t = time();
    $primary_node_info = keepalived_node($primary_node_id);
    $isDisable = false;

    if (intval($primary_node_info["isPrimaryNode"] ?? 1) == 0) {
        if (intval($primary_node_info["secondaryNodeIsDisconnected"] ?? 0) == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = true;
        }
    }

    $html[] = $tpl->_ENGINE_parse_body("
			<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[] = "<thead>";
    $html[] = "<tr>";
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text'>{dev}</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text'>{label}</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</center></th>");

    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";


    // La liste vient du point d'entree: meme ordre, plus de requete.
    $results = keepalived_children("vips", $primary_node_id);

    $TRCLASS = null;
    foreach ($results as $index => $ligne) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = "footable-even";
        } else {
            $TRCLASS = "footable-odd";
        }
        $md = md5(serialize($ligne));
        $text_class = null;


        $url = "Loadjs('$page?primary_node-virtualip-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}');";
        $href = "<a href=\"javascript:blur()\" OnClick=\"$url\" style='font-weight:bold'>";
        if ($isDisable) {
            $delete = "";
        } else {
            $delete = $tpl->icon_delete("Loadjs('$page?primary_node-virtualip-delete-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}&md=$md&virtualip={$ligne["virtual_ip"]}')", "AsSystemAdministrator");
        }
        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">$href{$ligne["virtual_ip"]}/{$ligne["netmask"]}</a></td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">{$ligne["dev"]}</td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">{$ligne["label"]}</td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\" class='center'>$delete</td>");
        $html[] = "</tr>";
    }

    $html[] = "</tbody>";
    $html[] = "<tfoot>";

    $html[] = "<tr>";
    $html[] = "<td colspan='5'>";
    $html[] = "<ul class='pagination pull-right'></ul>";
    $html[] = "</td>";
    $html[] = "</tr>";
    $html[] = "</tfoot>";
    $html[] = "</table>";
    $html[] = "
		<script>
		NoSpinner();\n" . @implode("\n", $tpl->ICON_SCRIPTS) . "
		$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
		</script>";

    echo @implode("\n", $html);
}

function primary_node_virtualip_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node_id"];
    $virtualip_id = intval($_GET["primary_node-virtualip-js"]);
    $virtualip_id_text = "{edit_virtualip}";
    if ($virtualip_id == 0) {
        $virtualip_id_text = "{new_virtualip}";
    }

    $title = "$virtualip_id_text";
    $tpl->js_dialog2($title, "$page?add-primary_node-virtualip=$virtualip_id&primary_node_id=$primary_node_id");
}

function add_primary_node_virtualip()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node_id"];
    $virtualip_id = intval($_GET["add-primary_node-virtualip"]);
    $virtualip_id_text = "{edit_virtualip}";
    if ($virtualip_id == 0) {
        $virtualip_id_text = "{new_virtualip}";
    }

    $title = "$virtualip_id_text";


    //$jsafter = "LoadAjax('table-keepalived-services','$page?table=yes');";
    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "/keepalived/reconfigure";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('primary_node-virtualip','$page?primary_node-virtualip-list=$primary_node_id');LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsafter = "dialogInstance2.close();".keepalived_progress_js($prgress);

    $title_button = "{add}";
    if ($virtualip_id > 0) {
        //$jsafter = "dialogInstance2.close();LoadAjax('primary_node-virtualip','$page?primary_node-virtualip-list=$primary_node_id');";
        // Lecture unitaire par le point d'entree, filtree sur l'instance.
        $results = array(keepalived_child("vips", $primary_node_id, $virtualip_id));
        $ligne = $results[0];
        $title_button = "{apply}";
    } else {
        $ligne["virtual_ip"] = null;
        $ligne["interface"] = null;
        $ligne["netmask"] = 24;
        $ligne["label"] = null;
    }


    $ip = new networking();
    $Interfaces[null] = "{not_used}";
    $Interfaces = $ip->Local_interfaces();

    unset($Interfaces["lo"]);
    $primary_node_info = keepalived_node($primary_node_id);
    $isDisable = false;

    if (intval($primary_node_info["isPrimaryNode"] ?? 1) == 0) {
        if (intval($primary_node_info["secondaryNodeIsDisconnected"] ?? 0) == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = true;
            $jsafter = "blur()";
        }
    }

    $form[] = $tpl->field_hidden("save_primary_node_virtualip", $primary_node_id);
    $form[] = $tpl->field_hidden("virtualip_id", $virtualip_id);
    $form[] = $tpl->field_ipaddr("virtualip", "{ipaddr}", $ligne["virtual_ip"], true, null, $isDisable);
    $form[] = $tpl->field_maskcdir('netmask', '{netmask}', "{$ligne["netmask"]}", true, null, $isDisable);
    $form[] = $tpl->field_interfaces('interface', "nooloopNoDef:{dev}", "$Interfaces", $explain = null);
    $form[] = $tpl->field_hidden("label", "{$ligne["label"]}");


    $security = "AsSystemAdministrator";
    $html = $tpl->form_outside($title, $form, null, $title_button, $jsafter, $security);
    echo $tpl->_ENGINE_parse_body($html);
}

function save_primary_node_virtualip():bool
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $primary_node_id = intval($_POST['save_primary_node_virtualip']);
    $virtualip_id = intval($_POST['virtualip_id']);

    if (empty($_POST['netmask'])) {
        $_POST['netmask'] = 24;
    }

    if (!filter_var($_POST['virtualip'], FILTER_VALIDATE_IP)) {
        echo (new template_admin())->post_error("Invalid Virtual IP Address");
        return false;
    }
    if ($virtualip_id == 0) {
        if (keepalived_duplicates("vip", $_POST['virtualip']) > 0) {
            echo (new template_admin())->post_error("Virtual ip ".req_html($_POST["virtualip"])." already exist");
            return false;
        }
    }

    // Les champs non presents dans le formulaire (label, interface virtuelle)
    // reprennent la valeur de la ligne existante.
    $ligne = keepalived_child("vips", $primary_node_id, $virtualip_id);
    return keepalived_rest_ok(keepalived_rest_post("/keepalived/vip/save", array(
        "primaryid" => $primary_node_id,
        "id" => $virtualip_id,
        "virtual_ip" => $_POST['virtualip'],
        "netmask" => (string)intval($_POST['netmask']),
        "dev" => $_POST['interface'],
        "virtual_interface_id" => intval($ligne["virtual_interface_id"] ?? 0),
        "enable" => intval($ligne["enable"] ?? 1),
        "synckey" => microtime(true))));
}

function delete_virtualip_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = intval($_GET["primary_node_id"]);
    $virtualip_id = intval($_GET["primary_node-virtualip-delete-js"]);
    $value = "$primary_node_id|$virtualip_id";

    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "/keepalived/reconfigure";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('primary_node-virtualip','$page?primary_node-virtualip-list=$primary_node_id');LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsafter = "dialogInstance2.close();".keepalived_progress_js($prgress);
    // Pentest 2026-11-04 : virtualip etait recopie sans encodage dans la page (XSS).
    $tpl->js_confirm_delete(req_html($_GET["virtualip"]), "delete-virtualip", $value, $jsafter);
}

function delete_virtualip()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $f = explode("|", $_POST["delete-virtualip"]);
    return keepalived_child_delete("vip", "vips", $f[0] ?? 0, $f[1] ?? 0);
}

//END VIRTUAL IPS


//TRACK INTERFACES
function primary_node_trackinterfaces()
{
    $page = CurrentPageName();
    $primary_node_id = $_GET["primary_node-trackinterfaces"];
    $tpl = new template_admin();
    $page = CurrentPageName();
    $primary_node_info = keepalived_node($primary_node_id);
    $isDisable = '';
    $js = "Loadjs('$page?primary_node-trackinterfaces-js=0&primary_node_id=$primary_node_id')";
    if (intval($primary_node_info["isPrimaryNode"] ?? 1) == 0) {
        if (intval($primary_node_info["secondaryNodeIsDisconnected"] ?? 0) == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = 'disabled';
            $js = "blur()";
        }
    }

    echo $tpl->_ENGINE_parse_body("
<p style=\"margin-top: 10px\">{keepalived_trackinterfaces}</p>
<div id='primary_node_save'></div>
			<div style='margin:15px 0 5px 0'>" . $tpl->table_buttons(kai_toolbar($js, "{new_trackinterfaces}", '', $isDisable)) . "</div>");

    echo "<div id='primary_node-trackinterfaces'></div>
	<script>
		LoadAjax('primary_node-trackinterfaces','$page?primary_node-trackinterfaces-list=$primary_node_id');
	</script>	
	";

}

function primary_node_trackinterfaces_list()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node-trackinterfaces-list"];
    $t = time();
    $primary_node_info = keepalived_node($primary_node_id);
    $isDisable = false;
    if (intval($primary_node_info["isPrimaryNode"] ?? 1) == 0) {
        if (intval($primary_node_info["secondaryNodeIsDisconnected"] ?? 0) == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = true;
        }
    }
    $html[] = $tpl->_ENGINE_parse_body("
			<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[] = "<thead>";
    $html[] = "<tr>";
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text'>{interface}</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text'>{weight}</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</center></th>");

    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";


    // La liste vient du point d'entree: meme ordre, plus de requete.
    $results = keepalived_children("tracks", $primary_node_id);
    $TRCLASS = null;
    foreach ($results as $index => $ligne) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = "footable-even";
        } else {
            $TRCLASS = "footable-odd";
        }
        $md = md5(serialize($ligne));
        $text_class = null;


        $url = "Loadjs('$page?primary_node-trackinterfaces-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}');";
        $href = "<a href=\"javascript:blur()\" OnClick=\"$url\" style='font-weight:bold'>";
        if ($isDisable) {
            $delete = "";
        } else {
            $delete = $tpl->icon_delete("Loadjs('$page?primary_node-trackinterfaces-delete-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}&md=$md&trackinterfaces={$ligne["interface"]}')", "AsSystemAdministrator");
        }
        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">$href{$ligne["interface"]}</a></td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">{$ligne["weight"]}</td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\" class='center'>$delete</td>");
        $html[] = "</tr>";
    }

    $html[] = "</tbody>";
    $html[] = "<tfoot>";

    $html[] = "<tr>";
    $html[] = "<td colspan='5'>";
    $html[] = "<ul class='pagination pull-right'></ul>";
    $html[] = "</td>";
    $html[] = "</tr>";
    $html[] = "</tfoot>";
    $html[] = "</table>";
    $html[] = "
		<script>
		NoSpinner();\n" . @implode("\n", $tpl->ICON_SCRIPTS) . "
		$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
		</script>";

    echo @implode("\n", $html);
}

function primary_node_trackinterfaces_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node_id"];
    $trackinterfaces_id = intval($_GET["primary_node-trackinterfaces-js"]);
    $trackinterfaces_id_text = "{edit_trackinterfaces}";
    if ($trackinterfaces_id == 0) {
        $trackinterfaces_id_text = "{new_trackinterfaces}";
    }

    $title = "$trackinterfaces_id_text";
    $tpl->js_dialog2($title, "$page?add-primary_node-trackinterfaces=$trackinterfaces_id&primary_node_id=$primary_node_id");
}

function add_primary_node_trackinterfaces()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node_id"];
    $trackinterfaces_id = intval($_GET["add-primary_node-trackinterfaces"]);
    $trackinterfaces_id_text = "{edit_trackinterfaces}";
    if ($trackinterfaces_id == 0) {
        $trackinterfaces_id_text = "{new_trackinterfaces}";
    }

    $title = "$trackinterfaces_id_text";


    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "/keepalived/reconfigure";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('primary_node-trackinterfaces','$page?primary_node-trackinterfaces-list=$primary_node_id');LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsafter = "dialogInstance2.close();".keepalived_progress_js($prgress);
    $title_button = "{add}";
    if ($trackinterfaces_id > 0) {
        //$jsafter = "dialogInstance2.close();LoadAjax('primary_node-trackinterfaces','$page?primary_node-trackinterfaces-list=$primary_node_id');";
        // Lecture unitaire par le point d'entree, filtree sur l'instance.
        $results = array(keepalived_child("tracks", $primary_node_id, $trackinterfaces_id));
        $ligne = $results[0];
        $title_button = "{apply}";
    } else {
        $ligne["interface"] = null;
        $ligne["weight"] = 0;
    }

    $primary_node_info = keepalived_node($primary_node_id);
    $isDisable = false;
    if (intval($primary_node_info["isPrimaryNode"] ?? 1) == 0) {
        if (intval($primary_node_info["secondaryNodeIsDisconnected"] ?? 0) == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = true;
            $jsafter = "blur()";
        }
    }


    $form[] = $tpl->field_hidden("save_primary_node_trackinterfaces", $primary_node_id);
    $form[] = $tpl->field_hidden("trackinterfaces_id", $trackinterfaces_id);
    if ($isDisable) {
        $form[] = $tpl->field_text('interface', "{local_interface}", "{$ligne["interface"]}", false, null, $isDisable, $isDisable);
        $form[] = $tpl->field_text("weight", "{weight}", "{$ligne["weight"]}", false, null, $isDisable, $isDisable);
    } else {
        $form[] = $tpl->field_interfaces('interface', "nooloopNoDef:{local_interface}", "{$ligne["interface"]}", $explain = null);
        $form[] = $tpl->field_numeric("weight", "{weight}", "{$ligne["weight"]}", false);
    }


    $security = "AsSystemAdministrator";
    $html = "<div id='primary_node_save'></div>" . $tpl->form_outside($title, $form, null, $title_button, $jsafter, $security);
    echo $tpl->_ENGINE_parse_body($html);
}

function save_primary_node_trackinterfaces()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $primary_node_id = intval($_POST['save_primary_node_trackinterfaces']);
    $trackinterfaces_id = intval($_POST['trackinterfaces_id']);
    if ($trackinterfaces_id == 0) {
        if (keepalived_duplicates("track", $_POST['interface']) > 0) {
            echo (new template_admin())->post_error("Interface ".req_html($_POST["interface"])." already in use");
            return false;
        }
    }
    $ligne = keepalived_child("tracks", $primary_node_id, $trackinterfaces_id);
    return keepalived_rest_ok(keepalived_rest_post("/keepalived/track/save", array(
        "primaryid" => $primary_node_id,
        "id" => $trackinterfaces_id,
        "interface" => $_POST['interface'],
        "weight" => intval($_POST['weight']),
        "enable" => intval($ligne["enable"] ?? 1),
        "synckey" => microtime(true))));
}

function delete_trackinterfaces_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = intval($_GET["primary_node_id"]);
    $trackinterfaces_id = intval($_GET["primary_node-trackinterfaces-delete-js"]);
    $value = "$primary_node_id|$trackinterfaces_id";

    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "/keepalived/reconfigure";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('primary_node-trackinterfaces','$page?primary_node-trackinterfaces-list=$primary_node_id');LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsafter = "dialogInstance2.close();".keepalived_progress_js($prgress);
    // Pentest 2026-11-04 : trackinterfaces etait recopie sans encodage dans la page (XSS).
    $tpl->js_confirm_delete(req_html($_GET["trackinterfaces"]), "delete-trackinterfaces", $value, $jsafter);
}

function delete_trackinterfaces()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $f = explode("|", $_POST["delete-trackinterfaces"]);
    return keepalived_child_delete("track", "tracks", $f[0] ?? 0, $f[1] ?? 0);
}

//END TRACK INTERFACES

//scripts_params
function primary_node_scripts_params()
{
    $page = CurrentPageName();
    $primary_node_id = $_GET["primary_node-scripts_params"];
    $tpl = new template_admin();
    $page = CurrentPageName();
    $primary_node_info = keepalived_node($primary_node_id);
    $isDisable = '';
    $js = "Loadjs('$page?primary_node-scripts_params-js=0&primary_node_id=$primary_node_id');";
    $jsparams = "Loadjs('$page?primary_node-scripts_params-parameters-js=0&primary_node_id=$primary_node_id');";
    if (intval($primary_node_info["isPrimaryNode"] ?? 1) == 0) {
        if (intval($primary_node_info["secondaryNodeIsDisconnected"] ?? 0) == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = 'disabled';
            $js = "blur()";
        }
    }
    echo $tpl->_ENGINE_parse_body("
<p style=\"margin-top: 10px\">{monitor_scripts_params_explain}</p>
<div id='primary_node_save'></div>
			<div style='margin:15px 0 5px 0'>" . $tpl->table_buttons(kai_toolbar($js, "{new_scripts_params}", $jsparams, $isDisable)) . "</div>");

    echo "<div id='primary_node-scripts_params'></div>
	<script>
		LoadAjax('primary_node-scripts_params','$page?primary_node-scripts_params-list=$primary_node_id');
	</script>	
	";

}



function primary_node_scripts_params_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node_id"];
    $scripts_params_id = intval($_GET["primary_node-scripts_params-js"]);
    $scripts_params_id_text = "{edit_scripts_params}";
    if ($scripts_params_id == 0) {
        $scripts_params_id_text = "{new_scripts_params}";
    }

    $title = "$scripts_params_id_text";
    $tpl->js_dialog2($title, "$page?add-primary_node-scripts_params=$scripts_params_id&primary_node_id=$primary_node_id");
}

function add_primary_node_scripts_params():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node_id"];
    $primary_node_id_text = "{edit_scripts_params}";
    if ($primary_node_id == 0) {
        $primary_node_id_text = "{new_scripts_params}";
    }

    $title = "$primary_node_id_text";
    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "/keepalived/reconfigure";
    $ARRAY["TITLE"] = "{reconfigure_scripts_params} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsafter = "dialogInstance2.close();".keepalived_progress_js($prgress);

    $title_button = "{add}";
    if ($primary_node_id > 0) {
        $title_button = "{apply}";
    }

    $primary_node_info = keepalived_node($primary_node_id);
    $isDisable = false;

    if (intval($primary_node_info["isPrimaryNode"] ?? 1) == 0) {
        if (intval($primary_node_info["secondaryNodeIsDisconnected"] ?? 0) == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = true;
            $jsafter = "blur()";
        }
    }
    $debugLevel[-1]="trace";
    $debugLevel[0]="debug";
    $debugLevel[1]="info";
    $debugLevel[2]="warn";
    $debugLevel[3]="error";
    $debugLevel[4]="fatal";
    $debugLevel[5]="panic";

    $cpu_num=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    if (count($primary_node_info)>0 && $cpu_num>intval($primary_node_info["loadlimit"])){
        $primary_node_info["loadlimit"]=$cpu_num;
    }

    $form[] = $tpl->field_hidden("save_primary_node_scripts_params",($primary_node_info["ID"] ?? 0));
    $form[] = $tpl->field_numeric("interval", '{interval}', ($primary_node_info["interval"] ?? ""), '{interval_explain}');
    $form[] = $tpl->field_numeric("fall", '{fall}', ($primary_node_info["fall"] ?? ""), '{fall_explain}');
    $form[] = $tpl->field_numeric("rise", '{rise}', ($primary_node_info["rise"] ?? ""), '{rise_explain}');
    $form[] = $tpl->field_numeric("weight", '{weight}', ($primary_node_info["weight"] ?? ""), '{weight_explain}');
    $form[] = $tpl->field_numeric("timeout", '{timeout}', ($primary_node_info["timeout"] ?? ""), '{timeout_explain}');
    $form[] = $tpl->field_checkbox("init_fail","{init_fail}","{$primary_node_info["init_fail"]}",false,"{init_fail_explain}");
    $form[] = $tpl->field_checkbox("enableProxyCurl","{enable} curl {test} (Proxy)","{$primary_node_info["enableProxyCurl"]}",'testDom,proxytesttimeout');
    $form[] = $tpl->field_text("testDom", '{test} {domain} Proxy', "{$primary_node_info["testDom"]}");
    $form[] = $tpl->field_numeric("proxytesttimeout", 'Curl {test} {timeout}', "{$primary_node_info["proxytesttimeout"]}");

    $form[] = $tpl->field_checkbox("enableDnsResolver","{enable} DNS {test}","{$primary_node_info["enableDnsResolver"]}",'testDomDNS,dnstesttimeout');
    $form[] = $tpl->field_text("testDomDNS", '{test} {domain} DNS', "{$primary_node_info["testDomDNS"]}");
    $form[] = $tpl->field_numeric("dnstesttimeout", 'DNS {test} {timeout}', "{$primary_node_info["dnstesttimeout"]}");


    $form[] = $tpl->field_checkbox("checkLoad","{enable} {server} {load} {test}","{$primary_node_info["checkLoad"]}",'loadlimit');
    $form[] = $tpl->field_numeric("loadlimit", '{server} {load} {limit}', "{$primary_node_info["loadlimit"]}");
    $form[] = $tpl->field_checkbox("checkRam","{enable} {memory_usage} {test}","{$primary_node_info["checkRam"]}",'ramlimit');
    $form[] = $tpl->field_numeric("ramlimit", '{memory_usage} {limit}', "{$primary_node_info["ramlimit"]}");
    $form[] = $tpl->field_checkbox("checkDisk","{enable} {disk_usage} {test}","{$primary_node_info["checkDisk"]}",'disklimit');
    $form[] = $tpl->field_numeric("disklimit", '{disk_usage} {limit}', "{$primary_node_info["disklimit"]}");
    $form[] = $tpl->field_array_hash($debugLevel, "checkDebugLevel", "{debug_level}", ($primary_node_info["checkDebugLevel"] ?? ""));





    $form[] = '<script>';
    if ($isDisable == true) {
        $form[] = '
         $( document ).ready(function() {      
             $("input").attr("readonly", "readonly");
         });
        ';
    }
    $form[] = '</script>';


    $security = "AsSystemAdministrator";
    $html = "<div id='primary_node_save'></div>" . $tpl->form_outside($title, $form, null, $title_button, $jsafter, $security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function save_primary_node_scripts_params()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();

    $testDom=trim($_POST["testDom"]);
    if ($testDom==null){
        $testDom="http://articatech.com";
    }

    $testDomDNS=trim($_POST["testDomDNS"]);
    if ($testDomDNS==null){
        $testDomDNS="cloudflare.com";
    }


    $primary_node_info = keepalived_node(intval($_POST["save_primary_node_scripts_params"]));
    if (count($primary_node_info) == 0) {
        echo (new template_admin())->post_error("Instance not found");
        return false;
    }

    $primary_node_info["interval"] = intval($_POST['interval']);
    $primary_node_info["fall"] = intval($_POST['fall']);
    $primary_node_info["rise"] = intval($_POST['rise']);
    $primary_node_info["weight"] = intval($_POST['weight']);
    $primary_node_info["timeout"] = intval($_POST['timeout']);
    $primary_node_info["init_fail"] = intval($_POST['init_fail']);

    $primary_node_info["testDom"] = $testDom;
    $primary_node_info["testDomDNS"] = $testDomDNS;
    $primary_node_info["enableProxyCurl"] = intval($_POST["enableProxyCurl"]);

    $cpu_num=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    if ($cpu_num>intval($_POST["loadlimit"])){
        $_POST["loadlimit"]=$cpu_num;
    }

    $primary_node_info["disklimit"] = intval($_POST["disklimit"]);
    $primary_node_info["ramlimit"] = intval($_POST["ramlimit"]);
    $primary_node_info["loadlimit"] = intval($_POST["loadlimit"]);
    $primary_node_info["checkDisk"] = intval($_POST["checkDisk"]);
    $primary_node_info["checkRam"] = intval($_POST["checkRam"]);
    $primary_node_info["checkLoad"] = intval($_POST["checkLoad"]);
    $primary_node_info["dnstesttimeout"] = intval($_POST["dnstesttimeout"]);
    $primary_node_info["proxytesttimeout"] = intval($_POST["proxytesttimeout"]);
    $primary_node_info["checkDebugLevel"] = intval($_POST["checkDebugLevel"]);
    $primary_node_info["enableDnsResolver"] = intval($_POST["enableDnsResolver"]);



    return keepalived_rest_ok(keepalived_node_save($primary_node_info));
}


//END scripts_params

//SLAVES
function secondary_node()
{
    $primary_node_id = $_GET["secondary_node"];
    $tpl = new template_admin();
    $page = CurrentPageName();
    $jsafter = "Loadjs('$page?secondary_node-js=0&primary_node_id=$primary_node_id')";

    echo $tpl->_ENGINE_parse_body("
<p style=\"margin-top: 10px\">{nodes_explain}</p>
<div id='primary_node_save'></div>
			<div style='margin:15px 0 5px 0'>" . $tpl->table_buttons(kai_toolbar($jsafter, "{new_secondary_node}", '', '')) . "</div>");

    echo "<div id='secondary_node'></div>
	<script>
		LoadAjax('secondary_node','$page?secondary_node-list=$primary_node_id');
	</script>	
	";

}

function secondary_node_list()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = intval($_GET["secondary_node-list"]);
    $t = time();

    $html[] = $tpl->_ENGINE_parse_body("
			<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[] = "<thead>";
    $html[] = "<tr>";
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text'>{priority}</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</th>");
    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";

    // Lecture unitaire par le point d'entree, filtree sur l'instance.
    $results = keepalived_children("secondary/nodes", $primary_node_id);
    $TRCLASS = null;
    foreach ($results as $ligne) {
        $TRCLASS = ($TRCLASS == "footable-odd") ? "footable-even" : "footable-odd";
        $md = md5(serialize($ligne));
        $ip = (string)($ligne["secondary_node_ip"] ?? "");
        $name = req_html($ip);
        if (!empty($ligne["hostname"])) {
            $name = req_html((string)$ligne["hostname"]) . " <span class='awc-muted'>(" . req_html($ip) . ")</span>";
        }
        // Same states and colours as the instance table (kai_secondary_state).
        list($bcls, $btext) = kai_secondary_state($ligne);
        $sid = intval($ligne["ID"]);
        $pid = intval($ligne["primary_node_id"]);
        $js = "Loadjs('$page?secondary_node-js=$sid&primary_node_id=$pid')";
        $label = "<a class='kai-node' href=\"javascript:blur();\" OnClick=\"$js\"><span class='awc-badge $bcls'>$btext</span>" .
            "<span class='kai-node-name'>$name" . kai_fault_reason($ligne) . "</span></a>";

        $delete = $tpl->icon_delete("Loadjs('$page?secondary_node-delete-js=$sid&primary_node_id=$pid&md=$md&secondary_node-ip=" . urlencode($ip) . "')", "AsSystemAdministrator");

        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = "<td>$label</td>";
        $html[] = "<td style='width:1%' nowrap>" . intval($ligne["priority"] ?? 0) . "</td>";
        $html[] = "<td style='width:1%' class='center' nowrap>$delete</td>";
        $html[] = "</tr>";
    }

    $html[] = "</tbody>";
    $html[] = "<tfoot>";
    $html[] = "<tr>";
    $html[] = "<td colspan='3'>";
    $html[] = "<ul class='pagination pull-right'></ul>";
    $html[] = "</td>";
    $html[] = "</tr>";
    $html[] = "</tfoot>";
    $html[] = "</table>";
    $script = "
		<script>
		NoSpinner();\n" . @implode("\n", $tpl->ICON_SCRIPTS) . "
		$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
		</script>";

    // The badge styles, in case the dialog is opened before the instance table.
    // The footable script stays outside the parsing: its {...} blocks would be read as tokens.
    echo awc_table_style() . kai_style() . $tpl->_ENGINE_parse_body(@implode("\n", $html)) . $script;
}

function secondary_node_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node_id"];
    $secondary_node_id = intval($_GET["secondary_node-js"]);
    $secondary_node_id_text = "{edit_secondary_node}";
    if ($secondary_node_id == 0) {
        $secondary_node_id_text = "{new_secondary_node}";
    }

    $title = "$secondary_node_id_text";
    $tpl->js_dialog2($title, "$page?add-secondary_node=$secondary_node_id&primary_node_id=$primary_node_id");
}

function add_secondary_node()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = intval($_GET["primary_node_id"]);
    $secondary_node_id = intval($_GET["add-secondary_node"]);

    if ($secondary_node_id == 0) {
        $secondary_node_id_text = "{new_secondary_node}";
    }


    $isDisable = false;

    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "/keepalived/reconfigure";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('secondary_node','$page?secondary_node-list=$primary_node_id');LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsafter = "dialogInstance2.close();".keepalived_progress_js($prgress);
    $title_button = "{add}";
    if ($secondary_node_id > 0) {
        //$jsafter = "dialogInstance2.close();LoadAjax('secondary_node','$page?secondary_node-list=$primary_node_id');";
        // Lecture unitaire par le point d'entree, filtree sur l'instance.
        $results = array(keepalived_child("secondary/nodes", $primary_node_id, $secondary_node_id));
        $ligne = $results[0];
        $secondary_node_name = $ligne["secondary_node_ip"];
        if (!empty($ligne["hostname"])) {
            $secondary_node_name = $ligne["hostname"] . " (" . $ligne["secondary_node_ip"] . ")";
        }
        $secondary_node_id_text = "{edit_secondary_node}: $secondary_node_name";
        $title_button = "{apply}";
        $isDisable = true;
    } else {
        $ligne["secondary_node_port"] = 9000;
        $ligne["priority"] = 100;
        $ligne["allow_secondary_node_overwrite"] = 0;
        $ligne["nopreempt"] = 0;
        $ligne["preempt_delay"] = 0;
    }
    $title = "$secondary_node_id_text";

    $form[] = $tpl->field_hidden("save_secondary_node", $primary_node_id);
    $form[] = $tpl->field_hidden("secondary_node_id", $secondary_node_id);
    if ($isDisable) {
        $form[] = $tpl->field_hidden("secondary_node_ip", $ligne["secondary_node_ip"]);
        $form[] = $tpl->field_text("secondary_node_ip_text", "{ipaddr}", $ligne["secondary_node_ip"], true, null, $isDisable, $isDisable);
    } else {
        $form[] = $tpl->field_ipv4("secondary_node_ip", "{ipaddr}", $ligne["secondary_node_ip"], true);

    }
    $form[] = $tpl->field_numeric("secondary_node_port", "{artica_listen_port}", $ligne["secondary_node_port"]);
    $form[] = $tpl->field_numeric("priority", "{priority}", $ligne["priority"], '{priority_explain}');
    $form[] = $tpl->field_checkbox("nopreempt", "{nopreempt_1}", $ligne["nopreempt"], false, "{nopreempt}");
    $form[] = $tpl->field_numeric("preempt_delay", "{preempt_delay}", $ligne["preempt_delay"], "{preempt_delay_explain}");

    $form[] = $tpl->field_text("rewriteInterface","{rewrite} {interface}",$ligne["rewriteInterface"]);
    //$form[] = $tpl->field_section("DANGER ZONE", "If you disconnect the Slave from farm, means that any changes made on Master will not be replicated to the Slave.", true);
    //$form[] = $tpl->field_checkbox("allow_secondary_node_overwrite", "{disonnect_from_farm}", $ligne["secondary_node_can_overwrite_settings"], false, "{allow_overwrite_explain}");


    $security = "AsSystemAdministrator";
    $html = $tpl->form_outside($title, @implode("\n", $form), null, $title_button, $jsafter, $security);
    echo $tpl->_ENGINE_parse_body($html);
}

function save_secondary_node()
{
    $tpl = new template_admin();
    $unix = new unix();
    $tpl->CLEAN_POST();
    $primary_node_id = intval($_POST["save_secondary_node"]);
    $secondary_node_id = intval($_POST["secondary_node_id"]);
    $keepalived_primary_node = keepalived_node($primary_node_id);
    if (count($keepalived_primary_node) == 0) {
        echo (new template_admin())->post_error("Instance not found");
        return false;
    }
    // Ligne existante (edition) ou nouvelle ligne active.
    $ligne = keepalived_child("secondary/nodes", $primary_node_id, $secondary_node_id);
    if (count($ligne) == 0) {
        $secondary_node_id = 0;
        $ligne = array("ID" => 0, "enable" => 1);
    }
    if ($secondary_node_id == 0) {
        if (!filter_var($_POST['secondary_node_ip'], FILTER_VALIDATE_IP)) {
            echo (new template_admin())->post_error("Invalid Slave IP Address");
            return false;
        }

        if (keepalived_duplicates("secondary", $_POST['secondary_node_ip']) > 0) {
            echo (new template_admin())->post_error("A secondary_node with ip ".req_html($_POST["secondary_node_ip"])." already exist");
            return false;
        }

        if (keepalived_duplicates("priority", intval($_POST['priority']), $primary_node_id) > 0) {
            echo (new template_admin())->post_error("A secondary_node with priority ".intval($_POST["priority"])." already exist");
            return false;
        }
    }

    $masterPriority = intval($keepalived_primary_node["priority"]);
    if (intval($_POST['priority']) >= $masterPriority) {
        echo (new template_admin())->post_error("The priority of secondary_node should be less than the priority of master ".intval($_POST['priority'])." $masterPriority $primary_node_id");
        return false;
    }

    if(intval($_POST['nopreempt'])==1 && intval($_POST['preempt_delay'])>0){
        echo (new template_admin())->post_error("".$tpl->javascript_parse_text("{preempt_delay_alert}"));
        return false;
    }
    if(intval($_POST['preempt_delay'])>0){
        $_POST['nopreempt']=0;
    }

    $ligne["primary_node_id"] = $primary_node_id;
    $ligne["secondary_node_port"] = intval($_POST['secondary_node_port']);
    $ligne["secondary_node_ip"] = $_POST["secondary_node_ip"];
    $ligne["primary_node_ip"] = $unix->InterfaceToIPv4($keepalived_primary_node["interface"]);
    $ligne["synckey"] = microtime(true);
    $ligne["priority"] = intval($_POST['priority']);
    $ligne["secondary_node_can_overwrite_settings"] = 0;
    $ligne["preempt_delay"] = intval($_POST['preempt_delay']);
    $ligne["nopreempt"] = intval($_POST['nopreempt']);
    $ligne["rewriteInterface"] = $_POST['rewriteInterface'];
    return keepalived_rest_ok(keepalived_secondary_save($ligne));
}

function delete_secondary_node_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = intval($_GET["primary_node_id"]);
    $secondary_node_id = intval($_GET["secondary_node-delete-js"]);
    $value = "$primary_node_id|$secondary_node_id";
    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "/keepalived/reconfigure";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('secondary_node','$page?secondary_node-list=$primary_node_id');LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsafter = "dialogInstance2.close();".keepalived_progress_js($prgress);
    // Pentest 2026-11-04 : secondary_node-ip etait recopie sans encodage dans la page (XSS).
    $tpl->js_confirm_delete("Remove Slave ".req_html($_GET["secondary_node-ip"]), "delete-secondary_node", $value, $jsafter);
}

function delete_secondary_node()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $f = explode("|", $_POST["delete-secondary_node"]);
    return keepalived_rest_ok(keepalived_rest_post("/keepalived/secondary/delete",
        array("primaryid" => intval($f[0] ?? 0), "id" => intval($f[1] ?? 0))));
}

//END SLAVES

//TABLE
// Toolbar of a dialog tab ("new ..." and, for services, "parameters"), drawn
// with table_buttons() like the other new-style toolbars. On an instance pushed
// by a primary node the buttons are inert: table_buttons() neutralises a button
// whose JavaScript is shorter than two characters.
function kai_toolbar($js, string $label, string $jsparams = '', string $disabled = ''): array
{
    $readonly = ($disabled != '');
    $btns = array();
    $btns[] = array($readonly ? '' : $js, ico_plus, $label);
    if ($jsparams != '') {
        $btns[] = array($readonly ? '' : $jsparams, ico_params, "{parameters}");
    }
    return $btns;
}

// A virtual address on one line: IP/prefix and the interface it is raised on.
// A row saved without an interface (label ":1") is raised by keepalived on the
// interface of its instance, which is then shown instead.
function kai_vip_html(array $vip, string $extraClass = '', string $fallbackDev = ''): string
{
    if (empty($vip["virtual_ip"])) {
        return "<span class='awc-muted'>&mdash;</span>";
    }
    $ip = req_html((string)$vip["virtual_ip"]);
    $mask = trim((string)($vip["netmask"] ?? ""));
    if ($mask != "") {
        $ip .= "/" . req_html($mask);
    }
    $dev = trim((string)($vip["dev"] ?? ""));
    if ($dev == "") {
        $dev = trim($fallbackDev);
    }
    $tag = ($dev != "") ? "<span class='awc-tag'>" . req_html($dev) . "</span>" : "";
    return "<span style='white-space:nowrap'><span class='awc-mono $extraClass'>$ip</span> $tag</span>";
}

function kai_style(): string
{
    return "<style>
.kai-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin:10px 0 16px 0}
.kai-tile{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 18px}
.kai-tile-l{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#64748b}
.kai-tile-v{font-size:26px;font-weight:800;color:#0f172a;margin-top:4px}
.kai-tile-ok .kai-tile-v{color:#047857}
.kai-tile-err .kai-tile-v{color:#be123c}
.kai-badge-info{background:#0284c7}
.kai-node{display:inline-flex;align-items:center;gap:6px;white-space:nowrap;text-decoration:none!important;cursor:pointer;margin:2px 0}
.kai-node-name{font-size:13px;font-weight:600;color:#0f172a}
.kai-nodes{display:flex;flex-direction:column;align-items:flex-start;gap:3px}
.kai-vip-ok{color:#047857;font-weight:700}
.kai-vip-err{color:#be123c;font-weight:700}
@media(max-width:1100px){.kai-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.kai-tiles{grid-template-columns:1fr}}
</style>";
}

// VRRP state of an instance -> badge, as the old buttons coloured it:
// MASTER/UP green, BACKUP blue, anything else red and pulsing.
function kai_state_badge(string $state): string
{
    $label = ($state == "") ? "&mdash;" : req_html($state);
    if ($state == "MASTER" || $state == "UP") {
        return "<span class='awc-badge awc-badge-on'>$label</span>";
    }
    if ($state == "BACKUP") {
        return "<span class='awc-badge kai-badge-info'>$label</span>";
    }
    return "<span class='awc-badge awc-badge-alert animated infinite pulse'>$label</span>";
}

// A secondary node's registration status -> array(badge class, text, ok).
// Same table as the old buttons: 0/2 waiting, 1/3/110 error text, 5 ping,
// 120 disconnected, 100 the VRRP state the node reported.
// The health-check failure behind a FAULT, under the node name (fault_reason,
// kept by articarest across the bare "FAULT" report that follows it).
function kai_fault_reason(array $node): string
{
    $reason = trim((string)($node["fault_reason"] ?? ""));
    if ($reason == "" || strtoupper((string)($node["service_state"] ?? "")) != "FAULT") {
        return "";
    }
    return "<div style='color:#ba1a1a;font-size:11px;font-weight:600;white-space:normal'><i class='fas fa-triangle-exclamation'></i> " . req_html($reason) . "</div>";
}

// Addresses outside this failover group seen in the keepalived log with this
// VRID (foreign_speakers, from articarest): another router on the segment can
// take the VIP or make the nodes flap.
function kai_foreign_html(array $node, int $vrid): string
{
    $list = is_array($node["foreign_speakers"] ?? null) ? $node["foreign_speakers"] : array();
    if (count($list) == 0) {
        return "";
    }
    $tpl = new template_admin();
    $items = array();
    foreach ($list as $s) {
        $when = $tpl->time_to_date(intval($s["last_seen"] ?? 0), true);
        $items[] = "<span title=\"" . req_html((string)($s["last_line"] ?? "")) . "\"><strong>" . req_html((string)($s["ip"] ?? "")) . "</strong> (" . req_html($when) . ", " . intval($s["count"] ?? 0) . "x)</span>";
    }
    return "<div style='color:#865300;font-size:11px;font-weight:600;white-space:normal;margin-top:2px'><i class='fas fa-triangle-exclamation'></i> VRID $vrid {kai_foreign_vrrp}: " . implode(", ", $items) . "</div>";
}

function kai_secondary_state(array $node): array
{
    $status = intval($node["status"] ?? 0);
    $err = req_html((string)($node["errortext"] ?? ""));
    $state = (string)($node["service_state"] ?? "");
    switch ($status) {
        case 0:
            return array("awc-badge-warn", "{waiting_registration}", false);
        case 2:
            return array("awc-badge-warn", "{waiting_confirmation}", false);
        case 1:
        case 3:
        case 110:
            return array("awc-badge-alert animated infinite pulse", $err, false);
        case 5:
            return array("kai-badge-info", "Ping OK", true);
        case 120:
            return array("awc-badge-warn", $err, false);
        case 100:
            if ($state == "MASTER" || $state == "UP") {
                return array("awc-badge-on", req_html($state), true);
            }
            if ($state == "BACKUP") {
                return array("kai-badge-info", req_html($state), true);
            }
            return array("awc-badge-alert animated infinite pulse", ($state == "" ? "&mdash;" : req_html($state)), false);
    }
    return array("awc-badge-off", "&mdash;", false);
}

function table()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $reload = "LoadAjax('table-keepalived-services','$page?table=yes');";

    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "/keepalived/reconfigure";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = $reload;
    $prgress = base64_encode(serialize($ARRAY));
    $jsrestart = "Loadjs('fw.progress.php?" . fw_progress_args_b64($prgress) . "&mainid=progress-keepalived-restart')";

    // Les instances viennent du point d'entree.
    $r = keepalived_rest_get("/keepalived/nodes");
    if (!$r["Status"]) {
        echo $tpl->div_error(req_html($r["Error"]));
        return;
    }
    $results = is_array($r["nodes"] ?? null) ? $r["nodes"] : array();

    $rows = array();
    $active = 0;
    $nodesTotal = 0;
    $nodesOk = 0;
    $vipsTotal = 0;
    foreach ($results as $ligne) {
        // Un seul appel par instance rend les listes rattachees: services,
        // adresses virtuelles et noeuds secondaires.
        $KA = keepalived_rest_get("/keepalived/instance?primaryid=" . intval($ligne["ID"]));
        $KA = is_array($KA["instance"] ?? null) ? $KA["instance"] : array();
        foreach (array("secondary_nodes", "services", "virtual_ips", "track_interfaces") as $k) {
            if (!is_array($KA[$k] ?? null)) { $KA[$k] = array(); }
        }
        $vipsTotal += count($KA["virtual_ips"]);

        $primary_node_id = intval($ligne["ID"]);
        $enabled = intval($ligne["enable"] ?? 0) == 1;
        if ($enabled) { $active++; }
        $nameRaw = (string)($ligne['primary_node_name'] ?? "");
        $name = req_html($nameRaw);
        $edit = "Loadjs('$page?primary_node-js=$primary_node_id&title=" . urlencode($nameRaw) . "')";
        $state = (string)($ligne['service_state'] ?? "");
        $vipOk = ($state == "MASTER" || $state == "UP");

        // An instance pushed by a primary node is read-only here, unless the
        // primary is disconnected.
        $isDisable = false;
        if (intval($ligne['isPrimaryNode'] ?? 1) == 0 && intval($ligne['secondaryNodeIsDisconnected'] ?? 0) == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = true;
        }
        if ($isDisable) {
            $enable = $enabled ? "<span class='awc-badge awc-badge-on'>{enable}</span>" : "<span class='awc-badge awc-badge-warn'>{disabled}</span>";
            $delete = "";
        } else {
            $enable = $tpl->icon_check($ligne["enable"], "Loadjs('$page?primary_node-enable-js=$primary_node_id')", null, "AsSquidAdministrator");
            $delete = $tpl->icon_delete("Loadjs('$page?primary_node-delete-js=$primary_node_id&primary_node-name=" . urlencode($nameRaw) . "')", "AsSquidAdministrator");
        }

        $tags = array();
        foreach ($KA["services"] as $svc) {
            $tags[] = "<span class='awc-tag'>" . req_html((string)($svc["service"] ?? "")) . "</span>";
        }
        $services = (count($tags) > 0) ? @implode(" ", $tags) : "<span class='awc-muted'>&mdash;</span>";

        $secondary_nodes = $KA["secondary_nodes"];
        usort($secondary_nodes, function ($a, $b) {
            return intval($b["priority"] ?? 0) - intval($a["priority"] ?? 0);
        });
        $nodeLines = array();
        foreach ($secondary_nodes as $secondary_node) {
            $nodesTotal++;
            list($bcls, $btext, $ok) = kai_secondary_state($secondary_node);
            if ($ok) { $nodesOk++; }
            $sstate = (string)($secondary_node["service_state"] ?? "");
            if (intval($secondary_node["status"] ?? 0) == 100 && ($sstate == "MASTER" || $sstate == "UP")) {
                $vipOk = true;
            }
            $sname = (string)($secondary_node["secondary_node_ip"] ?? "");
            if (!empty($secondary_node["hostname"])) {
                $sname = (string)$secondary_node["hostname"];
            }
            $sjs = "Loadjs('$page?secondary_node-js=" . intval($secondary_node["ID"]) . "&primary_node_id=" . intval($secondary_node["primary_node_id"]) . "')";
            $nodeLines[] = "<a class='kai-node' href=\"javascript:blur();\" OnClick=\"$sjs\"><span class='awc-badge $bcls'>$btext</span>" .
                "<span class='kai-node-name'>" . req_html($sname) . kai_fault_reason($secondary_node) . "</span></a>";
        }
        $nodes = (count($nodeLines) > 0) ? "<div class='kai-nodes'>" . @implode("", $nodeLines) . "</div>" : "<span class='awc-muted'>&mdash;</span>";

        // La premiere adresse virtuelle, comme le faisait le SELECT sans tri.
        $vip = $KA["virtual_ips"][0] ?? array();
        $vipText = kai_vip_html($vip, $vipOk ? "kai-vip-ok" : "kai-vip-err", (string)($ligne["interface"] ?? ""));

        $stats = $tpl->icon_stats("Loadjs('fw.keepalived.stats.php?stats-js=$primary_node_id&name=" . urlencode($nameRaw) . "')", "AsSquidAdministrator", !$enabled);

        $row = "<tr>";
        $row .= "<td style='width:1%' nowrap><div class='awc-ident'>" . kai_state_badge($state) .
            "<span><span class='awc-name" . ($enabled ? "" : " awc-name-off") . "'><a href=\"javascript:blur();\" OnClick=\"$edit\">$name</a></span>" .
            "<div class='awc-meta'>VRID " . intval($ligne["virtual_router_id"] ?? 0) . " &middot; " . req_html((string)($ligne["interface"] ?? "")) . "</div>" .
            ((string)($ligne["fault_reason"] ?? "") == "" ? "" : "<div class='awc-meta' style='color:#ba1a1a;font-weight:600;white-space:normal'><i class='fas fa-triangle-exclamation'></i> " . req_html((string)$ligne["fault_reason"]) . "</div>") .
            kai_foreign_html($ligne, intval($ligne["virtual_router_id"] ?? 0)) .
            "</span></div></td>";
        $row .= "<td>$services</td>";
        $row .= "<td nowrap>$vipText</td>";
        $row .= "<td nowrap>$nodes</td>";
        $row .= "<td class='center' style='width:1%'>$enable</td>";
        $row .= "<td class='center' style='width:1%'>$delete</td>";
        $row .= "<td class='center' style='width:1%'>$stats</td>";
        $row .= "</tr>";
        $rows[] = $row;
    }
    $total = count($results);

    $html[] = "<div class='kai-tiles'>";
    $html[] = "<div class='kai-tile'><div class='kai-tile-l'>{instances}</div><div class='kai-tile-v'>$total</div></div>";
    $html[] = "<div class='kai-tile" . ($total > 0 && $active == $total ? " kai-tile-ok" : "") . "'><div class='kai-tile-l'>{active2}</div><div class='kai-tile-v'>$active / $total</div></div>";
    $html[] = "<div class='kai-tile" . ($nodesTotal > 0 ? ($nodesOk == $nodesTotal ? " kai-tile-ok" : " kai-tile-err") : "") . "'><div class='kai-tile-l'>{secondary_nodes}</div><div class='kai-tile-v'>$nodesOk / $nodesTotal</div></div>";
    $html[] = "<div class='kai-tile'><div class='kai-tile-l'>{floating_ip}</div><div class='kai-tile-v'>$vipsTotal</div></div>";
    $html[] = "</div>";

    $html[] = awc_search_bar("kai-search", "{search}");
    $html[] = "<div class='awc-wrap' style='padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
    $html[] = "<thead><tr><th>{primary_node_name}</th><th>{health_checks}</th><th>{floating_ip}</th><th>{secondary_nodes}</th>";
    $html[] = "<th class='center'>{active2}</th><th class='center'>{delete}</th><th class='center'>{statistics}</th></tr></thead>";
    $html[] = "<tbody id='kai-rows'>";
    if ($total == 0) {
        $html[] = "<tr><td colspan='7'><div class='awc-empty'>{no_data}<br><br>" .
            "<a class='awc-btn' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?primary_node-js=');\"><i class='fas fa-plus'></i> {new_primary_node}</a></div></td></tr>";
    } else {
        $html[] = @implode("\n", $rows);
    }
    $html[] = "</tbody></table></div>";
    $footer = "$total {instances}";
    $version = (string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_VERSION");
    if ($version != "") { $footer .= " &mdash; Keepalived " . htmlspecialchars($version); }
    $html[] = "<div class='awc-foot'><span>$footer</span><span class='awc-pg' id='kai-pager'></span></div></div></div>";

    $topbuttons = array();
    $topbuttons[] = array("Loadjs('$page?primary_node-js=');", ico_plus, "{new_primary_node}");
    $topbuttons[] = array($jsrestart, ico_save, "{apply_configuration}");
    $topbuttons[] = array($reload, ico_refresh, "{refresh}");
    $topbuttons[] = array("s_PopUp('https://wiki.articatech.com/en/artica-failover-service','1024','800')", "fa-solid fa-headset", "{online_help}");
    $TINY = array();
    $TINY["TITLE"] = "{APP_KEEPALIVED} &raquo; {services}";
    $TINY["ICO"] = "fas fa-shield-halved";
    $TINY["EXPL"] = "{APP_KEEPALIVED_INSTANCES}";
    $TINY["URL"] = "failover-services";
    $TINY["BUTTONS"] = $tpl->table_buttons($topbuttons);

    $html[] = "<script>NoSpinner();\n" . $tpl->ICON_SCRIPTS_COMPILE() . "\nLoadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY))) . "');</script>";
    echo awc_table_style() . kai_style()
        . $tpl->_ENGINE_parse_body(@implode("\n", $html))
        . awc_pager_js($GLOBALS["FOOTABLE_PSIZE"], "kai-rows", "kai-pager")
        . awc_filter_js("kai-search", "kai-rows", "kai-pager");
}
