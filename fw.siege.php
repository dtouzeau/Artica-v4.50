<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.siege-daemon.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux
// d'entites. Include INCONDITIONNEL, au premier niveau: dans un bootstrap conditionnel
// il ne s'executerait jamais et la page mourrait sur awc_table_style() indefinie.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Pentest 2026-11-04 : les identifiants de test et file-uploaded etaient recopies sans encodage dans la page (XSS).
foreach(array("start-test-js","stop-test-js","duplicate-test-js","check-test-js","check-test-popup","report-html","report-html-popup","report-json","import-popup","import-upload-js","testId","delete-test-js","report-html-js","edit-test-js","edit-test-tabs","edit-test-popup","edit-test-kerberos-popup","edit-test-urls-popup","edit-test-urls-table","urls-table","urls-download","pdf-report","create-keytab") as $xssk){
    if(isset($_GET[$xssk])){$_GET[$xssk]=preg_replace('/[^A-Za-z0-9_.-]/','',(string)$_GET[$xssk]);}
}
foreach(array("delete-test","edit-test","edit-test-kerberos","urls-add","urls-delete","edit-test-urls","create-keytab") as $xssk){
    if(isset($_POST[$xssk])){$_POST[$xssk]=preg_replace('/[^A-Za-z0-9_.-]/','',(string)$_POST[$xssk]);}
}
if(isset($_GET["status-ids"])){$_GET["status-ids"]=preg_replace('/[^A-Za-z0-9_.|-]/','',(string)$_GET["status-ids"]);}
if(isset($_GET["file-uploaded"])){$_GET["file-uploaded"]=basename(str_replace("\\","/",(string)$_GET["file-uploaded"]));}
if(isset($_GET["pdf-report"])){report_pdf();exit;}
if(isset($_GET["status-ids"])){td_states();exit;}
if(isset($_GET["file-uploaded"])){import_uploaded_js();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["urls"])){urls();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["params"])){settings();exit;}
if(isset($_GET["search-top"])){search_top();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_POST["REMOTE_PROXY"])){Save();exit;}
if(isset($_POST["SIEGE_URLS"])){SaveUrls();exit;}
if(isset($_GET["reports"])){reports();exit;}
if(isset($_GET["report"])){report_js();exit;}
if(isset($_GET["report-popup"])){report_popup();exit;}
if(isset($_GET["create-test-js"])){create_tests_js();exit;}
if(isset($_GET["create-test-popup"])){create_tests_popup();exit;}
if(isset($_POST["create-test-save"])){create_tests_save();exit;}

if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["delete-test-js"])){delete_test_js();exit;}
if(isset($_POST["delete-test"])){delete_test();exit;}
if(isset($_GET["edit-test-js"])){edit_test_js();exit;}
if(isset($_GET["edit-test-popup"])){edit_test_popup();exit;}
if(isset($_GET["edit-test-tabs"])){edit_test_tabs();exit;}
if(isset($_POST["edit-test"])){edit_test_save();exit;}
if(isset($_GET["edit-test-kerberos-popup"])){edit_test_kerberos_popup();exit;}
if(isset($_POST["edit-test-kerberos"])){edit_test_kerberos_save();exit;}
if(isset($_GET["edit-test-urls-popup"])){edit_test_urls_popup();exit;}
if(isset($_GET["edit-test-urls-table"])){edit_test_urls_table();exit;}
if(isset($_POST["edit-test-urls"])){edit_test_urls_save();exit;}
if(isset($_GET["urls-table"])){urls_table();exit;}
if(isset($_POST["urls-add"])){urls_add();exit;}
if(isset($_POST["urls-delete"])){urls_delete();exit;}
if(isset($_GET["urls-download"])){urls_download();exit;}

if(isset($_GET["create-keytab"])){create_keytab_popup();exit;}
if(isset($_POST["create-keytab"])){create_keytab_save();exit;}
if(isset($_POST["update-keytab"])){update_keytab();exit;}
if(isset($_GET["kerberos-status"])){kerberos_status();exit;}
if(isset($_POST["kinit"])){kinit();exit;}
if(isset($_GET["subject-js"])){subject_js();exit;}
if(isset($_GET["subject-popup"])){subject_popup();exit;}
if(isset($_GET["subject-fill"])){subject_fill();exit;}
if(isset($_POST["subject_edit"])){subject_edit();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["stop-js"])){stop_js();exit;}
if(isset($_GET["start-test-js"])){start_test_js();exit;}
if(isset($_GET["stop-test-js"])){stop_test_js();exit;}
if(isset($_GET["check-test-js"])){check_test_js();exit;}
if(isset($_GET["check-test-popup"])){check_test_popup();exit;}
if(isset($_GET["duplicate-test-js"])){duplicate_test_js();exit;}

if(isset($_GET["report-html"])){report_html();exit;}
if(isset($_GET["report-html-js"])){report_html_js();exit;}
if(isset($_GET["report-html-popup"])){report_html_popup();exit;}
if(isset($_GET["report-json"])){report_json();exit;}

if(isset($_GET["import-upload-js"])){import_js();exit;}
if(isset($_GET["import-popup"])){import_popup();exit;}
if(isset($_GET["import-executed"])){import_executed();exit;}
page();


function stop_js(){
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/siege/stop");

}

function start_test_js(){
    $testId = $_GET["start-test-js"];
    $page = CurrentPageName();
    $siege = new SiegeDaemon();
    $result = $siege->startTest($testId);
    $tpl=new template_admin();
    if($result === null){
        // Distinguish "already running" (race from a double-click) from a
        // real failure. The former is harmless — just confirm the running
        // state in the UI and silently swallow the error so the user
        // doesn't see a scary popup for what is already the desired state.
        $err = $siege->getLastError();
        if(stripos($err, 'already running') !== false){
            $safe = htmlspecialchars($testId, ENT_QUOTES);
            $runningHtml = $tpl->_ENGINE_parse_body(
                "<span class='label label-success' OnClick=\"Loadjs('$page?stop-test-js=$safe');\" "
                . "OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\">"
                . "<i class='fas fa-play'></i> {running}</span>"
            );
            $runningJs = addslashes($runningHtml);
            echo "var c=document.getElementById('status-$safe');if(c){c.innerHTML='$runningJs';}";
            return;
        }
        return $tpl->js_error($err);
    }
    // Server confirmed the start. Repaint the row's status cell to the
    // running state right now, instead of waiting for the next polling
    // tick. This is what fixes the "user clicks twice because the table
    // didn't update" UX bug — without this, the cell stays "stopped"
    // until the periodic refresh, and the user assumes the click failed.
    admin_tracks("Starting Proxy test stress (Siege) id $testId");
    $safe = htmlspecialchars($testId, ENT_QUOTES);
    $runningHtml = $tpl->_ENGINE_parse_body(
        "<span class='label label-success' OnClick=\"Loadjs('$page?stop-test-js=$safe');\" "
        . "OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\">"
        . "<i class='fas fa-play'></i> {running}</span>"
    );
    $runningJs = addslashes($runningHtml);
    echo "var c=document.getElementById('status-$safe');if(c){c.innerHTML='$runningJs';}";
}

function stop_test_js(){
    $testId = $_GET["stop-test-js"];
    $md = $_GET["md"] ?? '';
    $page = CurrentPageName();
    $tpl = new template_admin();

    $siege = new SiegeDaemon();
    $result = $siege->stopTest($testId);

    if($result === null){
        echo "alert('" . addslashes($siege->getLastError()) . "');";
        return;
    }

    // Refresh the search results
    echo "LoadAjax('siege-search','$page?search=yes');";
}

/**
 * One-shot duplicate action. Calls the daemon's /duplicate endpoint, which
 * clones the test under a new UUID with " (copied)" appended to the name,
 * then re-renders the search table so the new row appears immediately.
 */
function duplicate_test_js(){
    $testId = $_GET["duplicate-test-js"];
    $page = CurrentPageName();
    $tpl = new template_admin();

    $siege = new SiegeDaemon();
    $result = $siege->duplicateTest($testId);
    if($result === null){
        echo "alert('" . addslashes($siege->getLastError()) . "');";
        return;
    }
    $newName = $result['name'] ?? '';
    admin_tracks("Duplicated siege test '$testId' as '" . ($result['test_id'] ?? '?') . "' ($newName)");
    // Refresh the listing so the operator sees the new row right away.
    echo "LoadAjax('siege-search','$page?search=yes');";
}

/**
 * Thin JS entry point: opens a modal dialog that loads the results of the
 * smoke check. The popup itself calls the daemon synchronously.
 */
function check_test_js(){
    $testId = $_GET["check-test-js"];
    $page = CurrentPageName();
    $tpl = new template_admin();
    $siege = new SiegeDaemon();
    $test = $siege->getTest($testId);
    $testName = $test['name'] ?? $testId;
    $tpl->js_dialog1("{check_test}: " . $testName, "$page?check-test-popup=$testId");
}

/**
 * Render the smoke-check result popup. Calls the daemon's one-user / two-URL
 * check endpoint and displays each URL's status code + response time. On
 * failure the raw error (transport + HTTP code) is surfaced so operators can
 * distinguish DNS / TCP / TLS / auth / HTTP-layer problems at a glance.
 */
function check_test_popup():bool{
    $testId = $_GET["check-test-popup"];
    $tpl = new template_admin();
    $siege = new SiegeDaemon();

    $test = $siege->getTest($testId);
    if($test === null){
        echo $tpl->_ENGINE_parse_body(
            $tpl->div_error($tpl->javascript_parse_text("{error}") . ": " . $siege->getLastError())
        );
        return false;
    }
    $testName = $test['name'] ?? $testId;

    // The daemon synchronously runs two HTTP requests; this blocks for up to
    // ~30 seconds. Displayed under a spinner handled by js_dialog1.
    $result = $siege->checkTest($testId);

    $out = [];
    $out[] = "<h4>{check_test}: " . htmlspecialchars($testName) . "</h4>";

    if($result === null){
        $out[] = $tpl->div_error(
            $tpl->javascript_parse_text("{error}") . ": " . htmlspecialchars($siege->getLastError())
        );
        echo $tpl->_ENGINE_parse_body(implode("\n", $out));
        return false;
    }

    if(!empty($result['error'])){
        $out[] = $tpl->div_error(
            $tpl->javascript_parse_text("{error}") . ": " . htmlspecialchars($result['error'])
        );
    }

    $overallOk = !empty($result['success']);
    if($overallOk){
        $out[] = $tpl->div_info("{check_test_all_ok}");
    } else {
        $out[] = $tpl->div_warning("{check_test_some_failed}");
    }

    $rows=array();
    foreach(($result['results'] ?? array()) as $row){
        $rows[]=siege_check_row($row);
    }
    $c=count($rows);

    $out[] = "<div class='awc-wrap' style='margin:10px 0 0 0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
    $out[] = "<thead><tr>";
    $out[] = "<th>URL</th>";
    $out[] = "<th class='center'>{status}</th>";
    $out[] = "<th class='center'>{response_time_ms}</th>";
    $out[] = "<th>{error}</th>";
    $out[] = "</tr></thead><tbody>";
    if($c==0){
        $out[] = "<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $out[] = @implode("\n",$rows);
    }
    $out[] = "</tbody></table></div></div></div>";

    // La feuille de style est concatenee AUTOUR de l'analyse: la regex #{(.+?)}#
    // de _ENGINE_parse_body lirait chaque corps de regle CSS comme un {token}.
    echo awc_table_style().$tpl->_ENGINE_parse_body(implode("\n", $out));
    return true;
}

/**
 * Le resultat d'une URL testee.
 *
 * L'etat garde ses trois cas distincts: succes, code HTTP d'echec, et echec de
 * transport (DNS / TCP / TLS / auth) ou aucun code n'existe -- afficher 0 pour
 * ce dernier laisserait croire a une reponse du serveur.
 */
function siege_check_row($row):string{
    $url     = htmlspecialchars($row['url'] ?? '');
    $code    = intval($row['status_code'] ?? 0);
    $success = !empty($row['success']);
    $err     = htmlspecialchars($row['error'] ?? '');
    $rt      = intval($row['response_time_ms'] ?? 0);

    if($success){
        $badge = "<span class='awc-badge awc-badge-on'>$code</span>";
        $ico   = "awc-ico";
    } elseif($code > 0){
        // We reached the server and got an HTTP status — show it.
        $badge = "<span class='awc-badge' style='background:#ba1a1a'>$code</span>";
        $ico   = "awc-ico awc-ico-off";
    } else {
        // Transport-layer failure (DNS / TCP / TLS / auth) — no status.
        $badge = "<span class='awc-badge awc-badge-off'>&mdash;</span>";
        $ico   = "awc-ico awc-ico-off";
    }

    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='$ico'><i class='fas fa-link'></i></span>";
    $h.="<span><span class='awc-name' style='word-break:break-all;font-weight:400;font-size:14px'>$url</span></span>";
    $h.="</div></td>";
    $h.="<td class='center'>$badge</td>";
    $h.="<td class='center'><span class='awc-seen'>$rt</span> <span class='awc-muted'>ms</span></td>";
    $h.="<td>".($err!==""?"<span class='awc-down'>$err</span>":"<span class='awc-none'>&mdash;</span>")."</td>";
    $h.="</tr>";
    return $h;
}

function td_progress($testId):string{

    $tpl=new template_admin();
    $siege = new SiegeDaemon();
    $progress = $siege->getTestProgress($testId);

    if($progress === null){

        return  "&nbsp;";
    }

    // Add running flag based on state
    $state = $progress['state'] ?? '';


    if($state=="running"){
        $progressPrc=$progress["progress"];
        $transactions=intval($progress["transactions"]);
        $rps=0;
        if (isset($progress['current_rps'])) {
            $rps = round($progress['current_rps'], 1);

        }
        $itext=array();
        if($transactions>0) {
            $itext[] = "rqs: $transactions";
        }
        if($rps>0) {
            $itext[] = "$rps req/sec";
        }
        $text=@implode("&nbsp;&nbsp;-&nbsp;&nbsp;",$itext);
        return $tpl->progress_barr_static($progressPrc,$text,true);
    }
    return "&nbsp;";
}

/**
 * Display HTML report directly (full page)
 */
function report_html(){
    $testId = $_GET["report-html"] ?? '';

    if(empty($testId)){
        echo "<h1>Error: Test ID required</h1>";
        return;
    }

    $siege = new SiegeDaemon();
    $html = $siege->getTestReportHtml($testId);

    if($html === null){
        echo "<h1>Error: " . htmlspecialchars($siege->getLastError()) . "</h1>";
        return;
    }

    // Output the HTML report directly
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
}

/**
 * Display HTML report in a popup/modal
 */
function report_html_popup(){
    $testId = $_GET["report-html-popup"] ?? '';
    $tpl = new template_admin();
    $page = CurrentPageName();

    if(empty($testId)){
        echo $tpl->div_error("Test ID required");
        return;
    }

    $siege = new SiegeDaemon();
    $html = $siege->getTestReportHtml($testId);

    if($html === null){
        echo $tpl->div_error(__LINE__." ".$siege->getLastError());
        return;
    }

    // Wrap in iframe for popup display
    $iframeSrc = "$page?report-html=$testId";

    $output = [];
    $output[] = "<div style='width:100%;height:600px;'>";
    $output[] = "<iframe src='$iframeSrc' style='width:100%;height:100%;border:none;'></iframe>";
    $output[] = "</div>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $output));
}

/**
 * Get JSON report data for a test
 */
function report_json(){
    header('Content-Type: application/json');

    $testId = $_GET["report-json"] ?? '';

    if(empty($testId)){
        echo json_encode(['error' => 'Test ID required']);
        return;
    }

    $siege = new SiegeDaemon();
    $report = $siege->getTestReport($testId);

    if($report === null){
        echo json_encode(['error' => $siege->getLastError()]);
        return;
    }

    echo json_encode($report);
}

function import_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $testId=$_GET["import-popup"];
    $html[]="<div id='import-access-progress'></div>";
    $html[]="<div id='import-access-next'>";
    $html[]="<p style='font-size: 16px;margin-bottom: 27px;padding-left: 30px;'>$testId<br>{import_stress_tool_explain}</p>";
    $html[]="<hr>";
    $html[]="<div class=center>";
    $html[]=$tpl->button_upload("{upload_file}",$page,null,"&testId=$testId")."</div>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);

}
function import_executed(){
    $tpl=new template_admin();
    $tsource="/usr/share/artica-postfix/ressources/logs/web/access.log.csv";
    $fsize=@filesize($tsource);

    $size=FormatBytes($fsize/1024);

    $link="<a href='ressources/logs/web/access.log.csv' class='btn btn-primary'><i class='fas fa-download'></i> {download}</a>";
    echo $tpl->_ENGINE_parse_body($tpl->widget_style1("navy-bg","fas fa-file-csv","access.log.csv",$size,
        array(),"<div style='text-align:right;margin-top:10px'>$link</div>"));
}


function import_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $testId=$_GET["import-upload-js"];
    return $tpl->js_dialog5("{import} {to} $testId","$page?import-popup=$testId",550);
}
function create_tests_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function = $_GET["function"] ?? '';
    return $tpl->js_dialog1("{create_test}","$page?create-test-popup=yes&function=".urlencode($function));
}

function create_tests_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function = $_GET["function"] ?? "LoadAjax('siege-search','$page?search=yes')";

    // Duration options
    $durations = [
        "30s" => "30 {seconds}",
        "60s" => "1 {minute}",
        "120s" => "2 {minutes}",
        "300s" => "5 {minutes}",
        "600s" => "10 {minutes}",
        "1800s" => "30 {minutes}",
        "3600s" => "1 {hour}",
    ];

    // Keep-alive options
    $keepalive = [
        "1" => "{yes}",
        "0" => "{no}",
    ];

    // Build form
    $form[] = $tpl->field_hidden("create-test-save", "yes");
    $form[] = $tpl->field_hidden("auto_start", "0");

    // Basic settings
    $form[] = $tpl->field_text("test_name", "{name}", "", true);
    $form[] = $tpl->field_interfaces("source_interface","{outgoing_interface}","");


    $form[] = $tpl->field_numeric("concurrent_users", "{members}", 50);
    $form[] = $tpl->field_array_hash($durations, "duration", "{duration}", "60s");

    $form[] = $tpl->field_text("delay", "{delay_between_requests}", "0s");
    $form[] = $tpl->field_text("timeout", "{timeout}", "30s");
    $form[] = $tpl->field_array_hash($keepalive, "keepalive", "Keep-Alive", "1");
    $form[] = $tpl->field_text("user_agent", "User-Agent", "siege-daemon/1.1");

    // Realistic load (v2): think-time distribution, VU stagger, UA pool.
    // Leave fields blank to fall back to legacy uniform(0,delay) behavior.
    // Realistic load (v2)
    $thinkTimeOpts = [
        ""          => "{think_time_distribution0}",
        "lognormal" => "{think_time_distribution2}",
        "uniform"   => "{think_time_distribution3}",
        "none"      => "{think_time_distribution1}",
    ];

    $userAgent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36\nMozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36\nMozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0\nMozilla/5.0 (Macintosh; Intel Mac OS X 14_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15\nMozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0\nMozilla/5.0 (iPhone; CPU iPhone OS 17_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Mobile/15E148 Safari/604.1\nMozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36\nMozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36";

    $form[] = $tpl->field_section("{realistic_load}");
    $form[] = $tpl->field_array_hash($thinkTimeOpts, "think_time_distribution", "{think_time_distribution}", "");
    $form[] = $tpl->field_text("think_time_mean_ms", "{think_time_median_ms}", "2000");
    $form[] = $tpl->field_text("think_time_sigma_ms", "{think_time_sigma}", "0.5");
    $form[] = $tpl->field_text("vu_startup_stagger", "{vu_startup_stagger}", "5s");
    $form[] = $tpl->field_textarea_normal("user_agents", "{user_agents_pool}", $userAgent, "100%", 5);

    // Proxy settings (optional)
    $form[] = $tpl->field_section("{your_proxy}");
    $form[] = $tpl->field_text("proxy_address", "{proxy_address}", "");
    $form[] = $tpl->field_numeric("proxy_port", "{proxy_port}", 3128);
    $form[] = $tpl->field_text("proxy_username", "{username}", "");
    $form[] = $tpl->field_password("proxy_password", "{password}", "");
    $form[] = $tpl->field_textarea_normal(
        "proxy_exclusions",
        "{proxy_exclusions}",
        "",
        "100%",
        4,
        "{proxy_exclusions_tooltip}"
    );

    // Redirect handling — controls how the worker reacts to 3xx responses.
    $form[] = $tpl->field_array_hash(
        ["1" => "{yes}", "0" => "{no}"],
        "follow_redirects", "{follow_redirects}", "1"
    );
    $form[] = $tpl->field_numeric("max_redirects", "{max_redirects}", 10);
    $form[] = "<hr>";


    $js = "dialogInstance1.close();$function";
    echo $tpl->form_outside("", @implode("\n", $form), null, "{create}", $js, "AsProxyMonitor");
    return true;
}

function create_tests_save():bool{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();

    $siege = new SiegeDaemon();

    // Validate required fields
    $testName = trim($_POST["test_name"] ?? '');
    $urlsRaw = trim($_POST["test_urls"] ?? '');
    $concurrentUsers = intval($_POST["concurrent_users"] ?? 10);
    $duration = trim($_POST["duration"] ?? '60s');
    if(strlen($urlsRaw)<5){
        $urlsRaw=@file_get_contents("/usr/share/artica-postfix/bin/install/squid/urls.txt");
    }

    if(empty($testName)){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: {name} {required}"));
        return false;
    }

    if(empty($urlsRaw)){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: URLs {required}"));
        return false;
    }

    // Parse URLs (one per line)
    $urlLines = array_filter(array_map('trim', explode("\n", $urlsRaw)));
    $urls = [];
    foreach($urlLines as $url){
        if(!empty($url)){
            // Support format: URL or METHOD|URL or METHOD|URL|BODY
            $parts = explode("|", $url);
            $urlConfig = ['url' => $parts[0]];
            if(isset($parts[1])){
                $urlConfig = ['method' => $parts[0], 'url' => $parts[1]];
            }
            if(isset($parts[2])){
                $urlConfig['body'] = $parts[2];
                $urlConfig['content_type'] = 'application/json';
            }
            // Default to GET if only URL provided
            if(!isset($urlConfig['method'])){
                $urlConfig['method'] = 'GET';
            }
            $urls[] = $urlConfig;
        }
    }

    if(empty($urls)){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: {invalid} URLs"));
        return false;
    }

    // Build test configuration
    $config = [
        'name' => $testName,
        'urls' => $urls,
        'concurrent_users' => $concurrentUsers,
        'duration' => $duration,
        'delay' => $_POST["delay"] ?? '0s',
        'timeout' => $_POST["timeout"] ?? '30s',
        'keepalive' => ($_POST["keepalive"] ?? '1') === '1',
        'user_agent' => $_POST["user_agent"] ?? 'siege-daemon/1.1',
        'source_interface'=>$_POST["source_interface"] ?? '',
    ];

    // Realistic-load fields (v2). Omit when blank so the daemon uses legacy
    // uniform(0,delay) behavior instead.
    siege_apply_realistic_load_post($config);
    siege_apply_proxy_options_post($config);

    // Add proxy settings if provided
    $proxyAddress = trim($_POST["proxy_address"] ?? '');
    $proxyPort = intval($_POST["proxy_port"] ?? 3128);
    if(!empty($proxyAddress)){
        // Build proxy URL from address and port
        $proxyUrl = "http://{$proxyAddress}:{$proxyPort}";
        $config['proxy_url'] = $proxyUrl;

        $proxyUsername = trim($_POST["proxy_username"] ?? '');
        $proxyPassword = trim($_POST["proxy_password"] ?? '');
        if(!empty($proxyUsername)){
            $config['proxy_auth'] = [
                'type' => 'basic',
                'username' => $proxyUsername,
                'password' => $proxyPassword,
            ];
        }
    }

    // Create the test
    $test = $siege->createTest($config);

    if($test === null){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: " . $siege->getLastError()));
        return false;
    }

    $testId = $test['id'] ?? '';
    if(empty($testId)){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: {failed} - No test ID returned"));
        return false;
    }

    // Auto-start if requested
    $autoStart = ($_POST["auto_start"] ?? '0') === '1';
    if($autoStart){
        $startResult = $siege->startTest($testId);
        if($startResult === null){
            // Test created but failed to start
            admin_tracks("Created Siege test '$testName' (ID: $testId) but failed to start: " . $siege->getLastError());
            echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{warning}: Test created but failed to start - " . $siege->getLastError()));
            return false;
        }
        admin_tracks("Created and started Siege test '$testName' (ID: $testId) with $concurrentUsers users for $duration");
    } else {
        admin_tracks("Created Siege test '$testName' (ID: $testId) with $concurrentUsers users for $duration");
    }

    return true;
}
function import_uploaded_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=$_GET["file-uploaded"];

    $testId = $_GET["testId"];
    if($testId === '' ){
        return  $tpl->js_error($tpl->javascript_parse_text("{error}: TestID ?? $testId"));

    }
    $tmp =dirname(__FILE__)."/ressources/conf/upload/$filename";
    if(!is_file($tmp)){
        return $tpl->js_error($tpl->javascript_parse_text("{error_unable_to_upload_file}"));

    }
    $contents = @file_get_contents($tmp);
    if($contents === false){
        return $tpl->js_error("{error}: read upload");

    }
    $siege = new SiegeDaemon();
    $r = $siege->importTestURLs($testId, $contents);
    if($r === null){
        return $tpl->js_error("{error}: " . $siege->getLastError());

    }
    $total = intval($r['total'] ?? 0);
    header("content-type: application/x-javascript");
    echo "LoadAjax('edit-test-urls-start','$page?edit-test-urls-table=$testId');dialogInstance5.close();";
    return admin_tracks("Imported $total URLs into siege test $testId");





}
function search_top():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page);
    return true;
}


function delete_js(){
    $ID=intval($_GET["delete-js"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/siege.db");
    $ligne=$q->mysqli_fetch_array("SELECT subject FROM reports WHERE ID=$ID");
    $subject=$ligne["subject"];
    $md=$_GET["md"];
    $tpl->js_confirm_delete($subject,"delete",$ID,"$('#$md').remove()");
}
function subject_js(){
    $ID=intval($_GET["subject-js"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/siege.db");
    $ligne=$q->mysqli_fetch_array("SELECT subject FROM reports WHERE ID=$ID");
    $subject=$ligne["subject"];
    $md=$_GET["md"];
    $tpl->js_dialog2($subject,"$page?subject-popup=$ID&md=$md");

}

function delete(){
    $ID=intval($_POST["delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/siege.db");
    $ligne=$q->mysqli_fetch_array("SELECT subject FROM reports WHERE ID=$ID");
    $subject=$ligne["subject"];
    $q->QUERY_SQL("DELETE FROM reports WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error;return false;}
    admin_tracks("Removed proxy stress report $subject");
    return true;
}

function delete_test_js(){
    $testId = $_GET["delete-test-js"];
    $md = $_GET["md"] ?? '';
    $page = CurrentPageName();
    $tpl = new template_admin();

    // Get test name from daemon API
    $siege = new SiegeDaemon();
    $test = $siege->getTest($testId);
    $testName = $test['name'] ?? $testId;

    $tpl->js_confirm_delete($testName, "delete-test", $testId, "\$('#$md').remove()");
}

function delete_test(){
    $tpl = new template_admin();
    $testId = $_POST["delete-test"];

    if(empty($testId)){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: Test ID {required}"));
        return false;
    }

    $siege = new SiegeDaemon();

    // Get test name for logging
    $test = $siege->getTest($testId);
    $testName = $test['name'] ?? $testId;

    // Delete the test
    $result = $siege->deleteTest($testId);

    if($result === null){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: " . $siege->getLastError()));
        return false;
    }

    admin_tracks("Deleted siege stress test '$testName' (ID: $testId)");
    return true;
}
function report_html_js(){
    $testId = $_GET["report-html-js"];
    $page = CurrentPageName();
    $tpl = new template_admin();
    $siege = new SiegeDaemon();
    $test = $siege->getTest($testId);
    $testName = $test['name'] ?? $testId;
    $tpl->js_dialog1("{report}: $testName", "$page?report-html-popup=$testId",1024);
}
function edit_test_js(){
    $testId = $_GET["edit-test-js"];
    $page = CurrentPageName();
    $tpl = new template_admin();
    $function=urlencode($_GET["function"]);
    $siege = new SiegeDaemon();
    $test = $siege->getTest($testId);
    $testName = $test['name'] ?? $testId;
    $tpl->js_dialog1("{edit}: $testName", "$page?edit-test-tabs=$testId&function=$function");
}
function edit_test_tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $testId = $_GET["edit-test-tabs"];
    $function=urlencode($_GET["function"]);
    $siege = new SiegeDaemon();
    $test = $siege->getTest($testId);
    $testName = $test['name'] ?? $testId;
    // Include the current principal-pool size in the Kerberos tab title so
    // operators can tell at a glance whether this test has a single user or
    // a pool of N that round-robin across VUs.
    $kerbCount = isset($test['kerberos_principals']) && is_array($test['kerberos_principals'])
        ? count($test['kerberos_principals'])
        : (isset($test['proxy_auth']['type']) && $test['proxy_auth']['type'] === 'kerberos' ? 1 : 0);
    $kerbLabel = "Kerberos" . ($kerbCount > 0 ? " ($kerbCount)" : "");

    $array[$testName]="$page?edit-test-popup=$testId&function=$function";
    $array[$kerbLabel]="$page?create-keytab=$testId&function=$function";
    $array["URls"]="$page?edit-test-urls-popup=$testId&function=$function";
    echo $tpl->tabs_default($array);
    return true;
}
function edit_test_popup(){
    $testId = $_GET["edit-test-popup"];
    $page = CurrentPageName();
    $tpl = new template_admin();
    $function=$_GET["function"];

    $siege = new SiegeDaemon();
    $test = $siege->getTest($testId);

    if($test === null){
        echo $tpl->div_error("{error}: " . $siege->getLastError());
        return false;
    }

    // Extract current values
    $testName = $test['name'] ?? '';
    $concurrentUsers = intval($test['concurrent_users'] ?? 10);
    $duration = $test['duration'] ?? '60s';
    $delay = $test['delay'] ?? '0s';
    $timeout = $test['timeout'] ?? '30s';
    $source_interface=$test['source_interface'] ?? '';
    $keepalive = isset($test['keepalive']) && $test['keepalive'] ? '1' : '0';
    $userAgent = $test['user_agent'];

    if(strlen($userAgent)<20){
        $userAgent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36\nMozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36\nMozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0\nMozilla/5.0 (Macintosh; Intel Mac OS X 14_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15\nMozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0\nMozilla/5.0 (iPhone; CPU iPhone OS 17_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Mobile/15E148 Safari/604.1\nMozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36\nMozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36";
    }

    // Realistic-load (v2) values, defaulted for empty configs
    $thinkDist  = $test['think_time']['distribution'] ?? '';
    $thinkMean  = isset($test['think_time']['mean_ms'])  ? strval($test['think_time']['mean_ms'])  : '2000';
    $thinkSigma = isset($test['think_time']['sigma_ms']) ? strval($test['think_time']['sigma_ms']) : '0.5';
    $vuStagger  = $test['vu_startup_stagger'] ?? '5s';
    $uaPoolText = isset($test['user_agents']) && is_array($test['user_agents'])
        ? implode("\n", $test['user_agents'])
        : '';

    // Extract proxy settings
    $proxyUrl = $test['proxy_url'] ?? '';
    $proxyAddress = '';
    $proxyPort = 3128;
    if(!empty($proxyUrl)){
        $parsed = parse_url($proxyUrl);
        $proxyAddress = $parsed['host'] ?? '';
        $proxyPort = $parsed['port'] ?? 3128;
    }
    $proxyUsername = $test['proxy_auth']['username'] ?? '';
    $proxyPassword = $test['proxy_auth']['password'] ?? '';

    // (URLs are NOT loaded here — for tests with thousands of URLs this loop
    // alone added measurable latency to every popup open. URLs are managed
    // in their own dedicated dialog via edit_test_urls_popup, which fetches
    // a paginated subset from the daemon instead of inlining the whole list.)

    // Duration options
    $durations = [
        "30s" => "30 {seconds}",
        "60s" => "1 {minute}",
        "120s" => "2 {minutes}",
        "300s" => "5 {minutes}",
        "600s" => "10 {minutes}",
        "1800s" => "30 {minutes}",
        "3600s" => "1 {hour}",
    ];

    // Keep-alive options
    $keepaliveOpts = [
        "1" => "{yes}",
        "0" => "{no}",
    ];

    // Build form
    $form[] = $tpl->field_hidden("edit-test", $testId);

    // Smoke-check button: quickly runs 1 user against 2 canonical URLs using
    // the stored proxy + auth config. Lets the operator validate the current
    // config without launching the full load. Rendered inline at the top of
    // the form so it's reachable before any submit, because a misconfigured
    // test is the most common reason operators open this dialog.
    $checkTitle = $tpl->javascript_parse_text("{check_test}");
    $checkExplain = $tpl->javascript_parse_text("{check_test_explain}");
    $page = CurrentPageName();
    $form[] = "<div class='alert alert-info' style='margin-bottom:12px'>"
            . "<a href=\"javascript:Loadjs('$page?check-test-js=$testId');\" "
            .   "class='btn btn-sm btn-primary' style='margin-right:8px'>"
            . "<i class='fas fa-vial'></i> $checkTitle"
            . "</a>"
            . "<span>$checkExplain</span>"
            . "</div>";

    $form[] = $tpl->field_text("test_name", "{name}", $testName, true);
    $form[] = $tpl->field_interfaces("source_interface", "{outgoing_interface}", $source_interface, true);


    $form[] = $tpl->field_numeric("concurrent_users", "{members}", $concurrentUsers);
    $form[] = $tpl->field_array_hash($durations, "duration", "{duration}", $duration);
    $form[] = $tpl->field_text("delay", "{delay_between_requests}", $delay);
    $form[] = $tpl->field_text("timeout", "{timeout}", $timeout);
    $form[] = $tpl->field_array_hash($keepaliveOpts, "keepalive", "Keep-Alive", $keepalive);
    $form[] = $tpl->field_text("user_agent", "User-Agent", $userAgent);

    // Realistic load (v2)
    $thinkTimeOpts = [
        ""          => "{think_time_distribution0}",
        "lognormal" => "{think_time_distribution2}",
        "uniform"   => "{think_time_distribution3}",
        "none"      => "{think_time_distribution1}",
    ];
    $form[] = $tpl->field_section("{realistic_load}");
    $form[] = $tpl->field_array_hash($thinkTimeOpts,
        "think_time_distribution",
        "{think_time_distribution}", $thinkDist,false,"{think_time_distribution_explain}");
    $form[] = $tpl->field_text("think_time_mean_ms", "{think_time_median_ms}", $thinkMean);
    $form[] = $tpl->field_text("think_time_sigma_ms", "{think_time_sigma}", $thinkSigma);
    $form[] = $tpl->field_text("vu_startup_stagger", "{vu_startup_stagger}", $vuStagger);
    $form[] = $tpl->field_textarea_normal("user_agents", "{user_agents_pool}", $uaPoolText, "100%", 5);

    // Proxy settings
    $form[] = $tpl->field_section("{your_proxy}");
    $form[] = $tpl->field_text("proxy_address", "{proxy_address}", $proxyAddress);
    $form[] = $tpl->field_numeric("proxy_port", "{proxy_port}", $proxyPort);
    $form[] = $tpl->field_text("proxy_username", "{username}", $proxyUsername);
    $form[] = $tpl->field_password("proxy_password", "{password}", $proxyPassword);

    // Proxy exclusions (NoProxy-style list) — pre-populated from existing config
    $proxyExclusionsText = "";
    if(!empty($test['proxy_exclusions']) && is_array($test['proxy_exclusions'])){
        $proxyExclusionsText = implode("\n", $test['proxy_exclusions']);
    }
    $form[] = $tpl->field_textarea_normal(
        "proxy_exclusions",
        "{proxy_exclusions}",
        $proxyExclusionsText,
        "100%",
        4,
        "{proxy_exclusions_tooltip}"
    );

    // Redirect handling — pre-populated from existing config
    $followRedirects = empty($test['disable_redirects']) ? "1" : "0";
    $maxRedirects = isset($test['max_redirects']) ? intval($test['max_redirects']) : 10;
    $form[] = $tpl->field_array_hash(
        ["1" => "{yes}", "0" => "{no}"],
        "follow_redirects", "{follow_redirects}", $followRedirects
    );
    $form[] = $tpl->field_numeric("max_redirects", "{max_redirects}", $maxRedirects);

    $js = "dialogInstance1.close();$function();";
    echo $tpl->form_outside("{edit}: $testName", @implode("\n", $form), null, "{save}", $js, "AsProxyMonitor");
    return true;
}

function edit_test_save(){
    $tpl = new template_admin();
    $tpl->CLEAN_POST();

    $testId = $_POST["edit-test"];
    if(empty($testId)){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: Test ID {required}"));
        return false;
    }

    $siege = new SiegeDaemon();

    // Get current test to preserve unchanged fields
    $currentTest = $siege->getTest($testId);
    if($currentTest === null){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: " . $siege->getLastError()));
        return false;
    }

    // Validate required fields
    $testName = trim($_POST["test_name"] ?? '');
    $urlsRaw = trim($_POST["test_urls"] ?? '');
    $concurrentUsers = intval($_POST["concurrent_users"] ?? 10);
    $duration = trim($_POST["duration"] ?? '60s');

    if(empty($testName)){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: {name} {required}"));
        return false;
    }

    // Start the updated config from the existing test record so any
    // field the form doesn't surface (urls, kerberos_principals,
    // host_pacing, …) is preserved as-is. The previous code rebuilt
    // $config from scratch using only the form fields, which silently
    // dropped every config knob the form doesn't render — including
    // the URL list. Symptom: duplicating a rule then opening it for
    // any small edit (rename, change concurrent_users, anything) and
    // saving wiped the 600+ URLs that duplicate had just copied.
    $config = $currentTest;
    unset($config['created_at']);

    // Overlay form fields onto the existing config.
    $config['name']             = $testName;
    $config['concurrent_users'] = $concurrentUsers;
    $config['duration']         = $duration;
    $config['delay']            = $_POST["delay"] ?? '0s';
    $config['timeout']          = $_POST["timeout"] ?? '30s';
    $config['keepalive']        = ($_POST["keepalive"] ?? '1') === '1';
    $config['user_agent']       = $_POST["user_agent"] ?? 'siege-daemon/1.1';
    $config['source_interface'] = $_POST["source_interface"] ?? '';

    // URLs: only update when the form actually carries them. The
    // edit-test popup deliberately does NOT render URLs into a
    // textarea (a paginated /tests/{id}/urls dialog handles URL CRUD
    // separately to avoid loading thousands of URLs into a single
    // textarea on every popup open). An empty test_urls field means
    // "the operator didn't touch URLs in this dialog" — keep the
    // existing list. The previous code, on empty, loaded the default
    // URL list from /usr/share/.../urls.txt and overwrote whatever
    // the test already had, which is the URL-loss bug behind the
    // "duplicate then edit returns to default URLs" report.
    if(strlen($urlsRaw) >= 5){
        $urlLines = array_filter(array_map('trim', explode("\n", $urlsRaw)));
        $urls = [];
        foreach($urlLines as $url){
            if(!empty($url)){
                $parts = explode("|", $url);
                $urlConfig = ['url' => $parts[0]];
                if(isset($parts[1])){
                    $urlConfig = ['method' => $parts[0], 'url' => $parts[1]];
                }
                if(isset($parts[2])){
                    $urlConfig['body'] = $parts[2];
                    $urlConfig['content_type'] = 'application/json';
                }
                if(!isset($urlConfig['method'])){
                    $urlConfig['method'] = 'GET';
                }
                $urls[] = $urlConfig;
            }
        }
        if(empty($urls)){
            echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: {invalid} URLs"));
            return false;
        }
        $config['urls'] = $urls;
    }
    if(empty($config['urls'])){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: URLs {required}"));
        return false;
    }

    // Realistic-load fields (v2)
    siege_apply_realistic_load_post($config);
    siege_apply_proxy_options_post($config);

    // Proxy settings (basic-auth path). Only writes proxy_auth when
    // the form supplies a username; otherwise the existing proxy_auth
    // (which may be Kerberos with a populated kerberos_principals
    // pool) is left untouched.
    $proxyAddress = trim($_POST["proxy_address"] ?? '');
    $proxyPort = intval($_POST["proxy_port"] ?? 3128);
    if(!empty($proxyAddress)){
        $config['proxy_url'] = "http://{$proxyAddress}:{$proxyPort}";

        $proxyUsername = trim($_POST["proxy_username"] ?? '');
        $proxyPassword = trim($_POST["proxy_password"] ?? '');
        if(!empty($proxyUsername)){
            $config['proxy_auth'] = [
                'type' => 'basic',
                'username' => $proxyUsername,
                'password' => $proxyPassword,
            ];
        }
    }

    // Upsert in place. Keeping $config['id'] (preserved from
    // $currentTest above) means the daemon's INSERT OR REPLACE
    // updates the same row, no UUID change, no row-disappears bug.
    $newTest = $siege->createTest($config);
    if($newTest === null){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: " . $siege->getLastError()));
        return false;
    }

    admin_tracks("Updated siege stress test '$testName'");
    return true;
}

function edit_test_kerberos_popup():bool{
    $testId = $_GET["edit-test-kerberos-popup"];
    $tpl = new template_admin();
    $MSKTUTIL_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MSKTUTIL_INSTALLED"));
    if($MSKTUTIL_INSTALLED==0){
        echo $tpl->div_error("{APP_MSKTUTIL}||{APP_MSKTUTIL_NOT_INSTALLED}");
        return true;
    }

    $page = CurrentPageName();

    $function=$_GET["function"];
    $siege = new SiegeDaemon();
    $test = $siege->getTest($testId);

    if($test === null){
        echo $tpl->div_error("{error}: " . $siege->getLastError());
        return false;
    }
    //$keytabPath = $test['proxy_auth']['keytab_path'] ?? '/etc/siege-daemon/siege.keytab';
    // Default to the local FQDN (php_uname("n") returns shortname — we want the fully-qualified form)
    $defaultHost = php_uname("n");
    if(strpos($defaultHost, ".") === false){
        $fqdn = gethostname();
        $resolved = gethostbyname($fqdn);
        if(!empty($resolved) && $resolved !== $fqdn){
            $ptr = gethostbyaddr($resolved);
            if(!empty($ptr) && strpos($ptr, ".") !== false){
                $defaultHost = $ptr;
            }
        }
    }
    $principal = $test['proxy_auth']['principal'] ?? $defaultHost;
    $realm = $test['proxy_auth']['realm'] ?? '';
    $kdcHost = $test['proxy_auth']['kdc_host'] ?? '';

    // Prominent warning about reusing existing AD accounts. Past incidents:
    // entering a short username (e.g. "dtouzeau") caused msktutil to target an
    // existing user whose Kerberos salt is derived from a pre-existing SPN,
    // producing a keytab whose keys the KDC rejected as "Preauthentication failed".
    $form[] = $tpl->div_warning("{kerberos_service_hostname_warning}");

    // Build form
    $form[] = $tpl->field_hidden("edit-test-kerberos", $testId);
    $form[] = $tpl->field_text(
        "hostname",
        "{kerberos_service_hostname_fqdn}",
        $principal,
        true,
        "{kerberos_service_hostname_tooltip}"
    );
    $form[] = $tpl->field_text("realm", "{activedirectory_domain}", $realm, true);
    $form[] = $tpl->field_text("kdc_host", "{activedirectory_server}", $kdcHost);

    $js = "$function();";
    echo $tpl->form_outside("", @implode("\n", $form), null, "{save}", $js, "AsProxyMonitor");
    return true;
}
function edit_test_kerberos_save():bool{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();

    $keytabPath = "/etc/siege-daemon/siege.keytab";
    $testId = $_POST["edit-test-kerberos"];

    if(empty($testId)){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: Test ID {required}"));
        return false;
    }

    $siege = new SiegeDaemon();
    $currentTest = $siege->getTest($testId);
    if($currentTest === null){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: " . $siege->getLastError()));
        return false;
    }

    // Validate required fields
    $hostname = trim($_POST["hostname"] ?? '');
    $realm = trim($_POST["realm"] ?? '');
    $kdcHost = trim($_POST["kdc_host"] ?? '');

    // Strip any of the formats we document/accept:
    //   HTTP://host@REALM   (double slash, as the old tooltip showed)
    //   HTTP/host@REALM     (SPN form, single slash)
    //   host@REALM          (bare UPN)
    // ...leaving just the hostname portion.
    if(preg_match("#^HTTP:?/+([^@]+?)@#i", $hostname, $re)){
        $hostname = $re[1];
    } elseif(preg_match("#^([^@]+?)@#", $hostname, $re)){
        $hostname = $re[1];
    }

    if(empty($hostname) || empty($realm)){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: Hostname {and} Realm {required}"));
        return false;
    }

    // Enforce FQDN: prevents the classic failure where an operator types
    // their own AD username (e.g. "dtouzeau"), msktutil collides with the
    // existing user account, and kinit fails preauth because AD's stored
    // key uses a salt derived from the pre-existing SPN rather than the
    // name we claimed. Rejecting non-FQDN input makes that whole class of
    // misconfiguration impossible at the form boundary.
    if(strpos($hostname, ".") === false){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text(
            "{error}: {kerberos_fqdn_required}: $hostname"
        ));
        return false;
    }

    // Preflight: verify reachability to the KDC before msktutil fires. The
    // msktutil flow additionally needs LDAP (TCP/389) to look up/create the
    // computer account; the preflight endpoint reports per-port status so we
    // can spot that too before the ~30s msktutil timeout.
    if(!empty($kdcHost)){
        $pf = $siege->kerberosPreflight($kdcHost);
        if($pf !== null && empty($pf['success'])){
            $pfMsg = siege_format_preflight_error($pf, $tpl);
            echo (new template_admin())->post_error("" . $tpl->javascript_parse_text(
                "{error}: {kerberos_preflight_failed}: " . $pfMsg
            ));
            return false;
        }
    }

    // Step 1: Create/Update keytab using msktutil
    // Extract computer name from principal (e.g., HTTP/hostname@REALM -> hostname)
    $computerName = $hostname;


    $msktutilConfig = [
        'keytab_path' => $keytabPath,
        'computer_name' => strtoupper(str_replace('.', '', $computerName)),
        'realm' => $realm,
        'upn' => $computerName . '@' . $realm,
        'service' => 'HTTP',
    ];

    if(!empty($kdcHost)){
        $msktutilConfig['server'] = $kdcHost;
    }

    // Try to create keytab first, if fails try update
    $keytabResult = $siege->createKeytab($msktutilConfig);
    if($keytabResult === null || !($keytabResult['success'] ?? false)){
        // Try update instead
        $keytabResult = $siege->updateKeytab($msktutilConfig);
        if($keytabResult === null || !($keytabResult['success'] ?? false)){
            $error = siege_format_daemon_error($keytabResult, $siege->getLastError());
            echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: msktutil {failed}: " . $error));
            return false;
        }
    }
    $principal="HTTP/$computerName@".strtoupper($realm);
    // Step 2: Validate the keytab
    $validateConfig = [
        'keytab_path' => $keytabPath,
        'principal' => $principal,
        'realm' => $realm,
    ];

    if(!empty($kdcHost)){
        $validateConfig['kdc_host'] = $kdcHost;
    }

    $validateResult = $siege->post('/api/v1/kerberos/validate', $validateConfig);

    if($validateResult === null){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: {validation} {failed}: " . $siege->getLastError()));
        return false;
    }

    if(!($validateResult['valid'] ?? false)){
        // Surface the full daemon response: each failed check's message AND the
        // raw top-level error (which carries the underlying kinit stderr, e.g.
        // "Preauthentication failed"). The previous code dropped the raw error
        // whenever any checks were present, masking the actual Kerberos cause.
        $parts = [];
        foreach(($validateResult['checks'] ?? []) as $check){
            if(!($check['passed'] ?? true)){
                $parts[] = $check['message'] ?? $check['name'] ?? 'unknown check';
            }
        }
        if(!empty($validateResult['error'])){
            $parts[] = $validateResult['error'];
        }
        if(!empty($validateResult['stderr'])){
            $parts[] = trim($validateResult['stderr']);
        }
        $errorMsg = !empty($parts) ? implode(' | ', $parts) : 'Unknown error';
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: Kerberos {validation} {failed}: " . $errorMsg));
        return false;
    }

    // Step 3: Obtain a ticket (kinit) to ensure everything works
    $kinitConfig = [
        'keytab_path' => $keytabPath,
        'principal' => $principal . '@' . $realm,
        'lifetime' => '24h',
        'renewable' => '7d',
    ];

    $kinitResult = $siege->kinit($principal . '@' . $realm, $keytabPath, '24h', '7d');

    if($kinitResult === null || !($kinitResult['success'] ?? false)){
        $error = siege_format_daemon_error($kinitResult, $siege->getLastError());
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: kinit {failed}: " . $error));
        return false;
    }

    // Step 4: Update test configuration with Kerberos auth.
    //
    // Critical: keep $config['id'] intact and DON'T deleteTest first.
    // The daemon's POST /api/v1/tests handler is upsert (SaveTest uses
    // INSERT OR REPLACE on the id column), so submitting the same id
    // updates the existing row in place. The previous flow stripped
    // the id and called deleteTest+createTest, which assigned a fresh
    // UUID server-side — the original test row vanished from the
    // listTests result and the operator's main table appeared empty
    // (the row "disappeared" after saving Kerberos settings).
    $config = $currentTest;
    unset($config['created_at']);

    $config['proxy_auth'] = [
        'type' => 'kerberos',
        'keytab_path' => $keytabPath,
        'principal' => $principal,
        'realm' => $realm,
    ];

    if(!empty($kdcHost)){
        $config['proxy_auth']['kdc_host'] = $kdcHost;
    }

    $newTest = $siege->createTest($config);

    if($newTest === null){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: " . $siege->getLastError()));
        return false;
    }

    $testName = $config['name'] ?? $testId;
    admin_tracks("Updated Kerberos settings for siege test '$testName' (keytab created and validated)");
    return true;
}

/**
 * URL editor dialog. Replaces the legacy "load-all-urls-into-a-textarea"
 * implementation that turned every popup open into a multi-second hang for
 * tests with thousands of URLs.
 *
 * The new dialog renders nothing but a chrome shell — a search box, an
 * "add URL" form, an import/export pair, and a result-table container.
 * The actual table is loaded asynchronously via the urls-table sub-handler
 * below, which fetches only the first 150 matching URLs from the daemon.
 */

function edit_test_urls_popup():bool{
    $testId = $_GET["edit-test-urls-popup"];
    $page = CurrentPageName();
    echo "<div id='edit-test-urls-start'></div>";
    echo "<script>LoadAjax('edit-test-urls-start','$page?edit-test-urls-table=$testId');</script>";
    return true;

}

function edit_test_urls_table(){
    $testId = $_GET["edit-test-urls-table"];
    $page = CurrentPageName();
    $tpl = new template_admin();

    $siege = new SiegeDaemon();
    $test = $siege->getTest($testId);
    if($test === null){
        echo $tpl->div_error("{error}: " . $siege->getLastError());
        return false;
    }
    $testName = $test['name'] ?? $testId;
    $totalCount = isset($test['urls']) && is_array($test['urls']) ? count($test['urls']) : 0;

    // Sanitize the test id for element IDs and JS variable names. Hyphens
    // are legal in HTML id attributes but NOT in JS identifiers — the prior
    // version built per-test JS function names like "urlsReload_<id>" with
    // hyphens, which the parser treats as a subtraction expression and the
    // function silently never gets defined ("urlsReload_urls is not defined"
    // at runtime). Underscore-only avoids that pitfall while still letting
    // the same key serve both element IDs and JS keys.
    $safe = preg_replace('/[^A-Za-z0-9]/', '_', $testId);
    $tableContainer = "urls_table_container_" . $safe;

    $h = [];
    // Les regles table.urls-table ont disparu avec le tableau qu'elles habillaient:
    // urls_table() rend desormais une carte awc, qui apporte sa propre feuille de
    // style. Ne restent que la barre de pagination et l'etat vide, qui vivent hors
    // de la carte.
    $h[] = "<style>"
         . "#$tableContainer .urls-empty{padding:18px;color:#777;text-align:center}"
         . "#$tableContainer .urls-pagebar{padding:6px 0;font-size:90%;color:#555}"
         . "</style>";

    $h[] = "<div style='margin-bottom:8px;margin-top:10px'><strong>" . htmlspecialchars($testName) . "</strong> &mdash; "
         . "<span id='urls_total_$safe'>$totalCount</span> URL(s)</div>";

    // Toolbar: search + pagination
    $h[] = "<table style='width:100%;margin-bottom:6px'><tr>";
    $h[] = "  <td style='width:50%;padding-right:6px'>"
         . "<input type='text' id='urls_search_$safe' placeholder='" . $tpl->javascript_parse_text("{search}") . "' "
         . "       style='width:100%;padding:5px' "
         . "       onkeyup=\"clearTimeout(window.urlsTmr_$safe); window.urlsTmr_$safe=setTimeout(function(){document.getElementById('urls_offset_$safe').value='0';urlsReload('$testId');},250);\"></td>";
    $h[] = "  <td style='white-space:nowrap'>"
         . "<input type='hidden' id='urls_offset_$safe' value='0'>"
         . "<button type='button' class='btn btn-sm btn-default' onclick=\"document.getElementById('urls_offset_$safe').value=Math.max(0,parseInt(document.getElementById('urls_offset_$safe').value)-150);urlsReload('$testId');\">&lt;&lt; "
         . $tpl->javascript_parse_text("{prev}") . "</button> "
         . "<button type='button' class='btn btn-sm btn-default' onclick=\"document.getElementById('urls_offset_$safe').value=parseInt(document.getElementById('urls_offset_$safe').value)+150;urlsReload('$testId');\">"
         . $tpl->javascript_parse_text("{next}") . " &gt;&gt;</button>"
         . "</td>";
    $h[] = "</tr></table>";

    // Add URL inline form
    // Use jQuery $.post — POSTHidden does NOT exist in the Artica JS bundle;
    // jQuery itself is already loaded (LoadAjax depends on it).
    $addJs = "(function(){"
           . "var u=document.getElementById('urls_add_url_$safe').value.trim();"
           . "if(!u){alert('" . $tpl->javascript_parse_text("{url} {required}") . "');return;}"
           . "var m=document.getElementById('urls_add_method_$safe').value;"
           . "\$.post('$page',{'urls-add':'$testId','url':u,'method':m},function(){"
           . "  document.getElementById('urls_add_url_$safe').value='';urlsReload('$testId');"
           . "});})();";
    $h[] = "<table style='width:100%;margin-bottom:6px'><tr>";
    $h[] = "  <td style='width:90px'><select id='urls_add_method_$safe' style='width:100%;padding:8px'>"
         . "<option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option><option>HEAD</option>"
         . "</select></td>";
    $h[] = "  <td style='padding-left:6px'><input type='text' id='urls_add_url_$safe' placeholder='https://example.com/path' style='width:100%;padding:5px'></td>";
    $h[] = "  <td style='padding-left:6px;width:80px'><button type='button' class='btn btn-sm btn-primary' onclick=\"$addJs\">+ "
         . $tpl->javascript_parse_text("{add}") . "</button></td>";
    $h[] = "</tr></table>";

    // Import / export bar
    $h[] = "<div style='margin-bottom:8px'>";
    $h[] = "  <a class='btn btn-sm btn-default' href='$page?urls-download=$testId' target='_blank'><i class='fas fa-download'></i> "
         . $tpl->javascript_parse_text("{download}") . " (.txt)</a> ";
    $h[] = "  <button type='button' class='btn btn-sm btn-default' onclick=\"Loadjs('$page?import-upload-js=$testId')\"><i class='fas fa-upload'></i> "
         . $tpl->javascript_parse_text("{import_from_file}") . " (.txt)</button>";
    $h[] = "</div>";

    // Result container
    $h[] = "<div id='$tableContainer' style='max-height:60vh;overflow:auto;border:1px solid #ddd'></div>";

    // Single shared JS function — defined once on the page and parameterised
    // by testId. All event handlers call urlsReload('<testId>'). This avoids
    // building per-test function names (which fail to parse when the testId
    // contains hyphens — the original bug).
    $pageJson = json_encode($page);
    $tidJson  = json_encode($testId);
    $h[] = "<script>";
    // Always overwrite window.urlsReload — if a stale popup script defined an
    // older closure (different param names, etc), unconditional reassignment
    // ensures the latest version wins. The build marker below lets us verify
    // in DevTools which copy of the JS is actually running.
    $h[] = "/* siege-urls-popup build: 2026-04-25T19:40Z urls-q-rev2 */";
    $h[] = "window.urlsReload = function(testId) {";
    $h[] = "  var safe = testId.replace(/[^A-Za-z0-9]/g, '_');";
    $h[] = "  var page = $pageJson;";
    $h[] = "  var s = encodeURIComponent((document.getElementById('urls_search_'+safe)||{}).value || '');";
    $h[] = "  var o = encodeURIComponent((document.getElementById('urls_offset_'+safe)||{}).value || '0');";
    // Distinct query-param names (urls-q, urls-offset). The page-level
    // dispatcher chain at the top of fw.siege.php matches on bare "search"
    // FIRST — reusing that name routes the request to search() and the
    // urls-table handler never runs. The trailing &_=<timestamp> cache-busts
    // the browser-side AJAX cache so pagination always hits fresh data.
    $h[] = "  var url = page+'?urls-table='+encodeURIComponent(testId)+'&urls-q='+s+'&urls-offset='+o+'&_='+Date.now();";
    $h[] = "  LoadAjax('urls_table_container_'+safe, url);";
    $h[] = "};";
    $h[] = "urlsReload($tidJson);";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    return true;
}

/**
 * AJAX renderer: builds the URL table HTML for one page (search + offset).
 * Called from urlsReload_<container>() in the popup. Hits the daemon's
 * GET /api/v1/tests/{id}/urls endpoint with search/offset/limit query
 * params and renders the result rows. Each row carries an inline delete
 * button that issues a POSTHidden urls-delete and re-triggers the reload.
 */
function urls_table(){
    $tpl = new template_admin();
    $page = CurrentPageName();
    $testId = $_GET["urls-table"];
    // Read from urls-q / urls-offset (not the bare names), see comment in
    // edit_test_urls_popup() about the dispatcher collision.
    $search = trim($_GET["urls-q"] ?? '');
    $offset = max(0, intval($_GET["urls-offset"] ?? 0));
    $limit  = 150;

    $siege = new SiegeDaemon();
    $resp = $siege->listTestURLs($testId, $search, $offset, $limit);
    if($resp === null){
        echo $tpl->div_error($tpl->javascript_parse_text("{error}") . ": " . $siege->getLastError());
        return;
    }

    $total = intval($resp['total'] ?? 0);
    $matched = intval($resp['filtered'] ?? 0);
    $urls = $resp['urls'] ?? [];

    $h = [];
    // Pagination header
    $start = $offset + 1;
    $end   = $offset + count($urls);
    if(empty($urls)){
        $h[] = "<div class='urls-pagebar' style='padding-left:5px'>" . $tpl->javascript_parse_text("{no_results}") . "</div>";
    } else {
        $h[] = "<div class='urls-pagebar' style='padding-left:5px'>"
             . "{$start} – $end / $matched"
             . ($search !== '' ? " (" . $tpl->javascript_parse_text("{search}") . ": <code>" . htmlspecialchars($search) . "</code>)" : "")
             . "</div>";
    }

    if(!empty($urls)){
        $h[] = "<div class='awc-wrap' style='margin:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
        $h[] = "<thead><tr>"
             . "<th class='center' style='width:1%'>{method}</th>"
             . "<th>URL</th>"
             . "<th style='text-align:right'>{action}</th>"
             . "</tr></thead><tbody>";
        foreach($urls as $u){
            $h[]=siege_url_row($u,$testId,$page,$tpl);
        }
        $h[] = "</tbody></table></div></div></div>";
    }

    // La feuille de style est concatenee AUTOUR de l'analyse: la regex #{(.+?)}#
    // de _ENGINE_parse_body lirait chaque corps de regle CSS comme un {token}.
    echo awc_table_style().$tpl->_ENGINE_parse_body(implode("\n", $h));
    // Sync the total counter shown in the popup header
    // Sync the popup's total counter — IDs are now urls_total_<safe-key>
    echo "<script>var t=document.querySelectorAll('[id^=\"urls_total_\"]');if(t.length)t[0].textContent='$total';</script>";
}

/**
 * Une URL du test. La suppression reste un $.post suivi d'un urlsReload():
 * la liste est paginee cote serveur, retirer la ligne du DOM laisserait la
 * page courante incomplete.
 */
function siege_url_row($u,$testId,$page,$tpl):string{
    $idx    = intval($u['index'] ?? 0);
    $method = htmlspecialchars($u['method'] ?? 'GET');
    $url    = htmlspecialchars($u['url'] ?? '');
    // jQuery $.post (POSTHidden is not part of the Artica JS bundle).
    $delJs  = "if(confirm('" . $tpl->javascript_parse_text("{delete} ?") . "')){"
            . "\$.post('$page',{'urls-delete':'$testId','index':'$idx'},function(){"
            . "if(typeof window.urlsReload==='function')window.urlsReload('$testId');});}";

    $h ="<tr>";
    $h.="<td class='center' style='width:1%' nowrap><span class='awc-tag'>$method</span></td>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-link'></i></span>";
    $h.="<span><span class='awc-name' style='word-break:break-all;font-weight:400;font-size:14px'>$url</span></span>";
    $h.="</div></td>";
    $h.="<td><div class='awc-acts'>"
       ."<a class='awc-act awc-act-danger' href='javascript:;' onclick=\"$delJs\" title='"
       . $tpl->javascript_parse_text("{delete}") . "'><i class='fas fa-trash-can'></i></a>"
       ."</div></td>";
    $h.="</tr>";
    return $h;
}

/**
 * Append one URL to the test's URL list. Returns nothing meaningful; the
 * caller is expected to refresh the table afterwards.
 */
function urls_add(){
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $testId = $_POST["urls-add"];
    $url    = trim($_POST["url"] ?? '');
    $method = trim($_POST["method"] ?? 'GET');
    if($testId === '' || $url === ''){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: {url} {required}"));
        return;
    }
    $siege = new SiegeDaemon();
    $r = $siege->addTestURL($testId, $url, $method);
    if($r === null){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: " . $siege->getLastError()));
        return;
    }
    admin_tracks("Added URL '$url' to siege test $testId");
}

/**
 * Delete a single URL by its position in the test's URL list.
 */
function urls_delete(){
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $testId = $_POST["urls-delete"];
    $index  = intval($_POST["index"] ?? -1);
    if($testId === '' || $index < 0){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: index {required}"));
        return;
    }
    $siege = new SiegeDaemon();
    $r = $siege->deleteTestURL($testId, $index);
    if($r === null){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: " . $siege->getLastError()));
        return;
    }
    admin_tracks("Deleted URL #$index from siege test $testId");
}

/**
 * Replace the test's URL list with the contents of an uploaded text file.
 * Format matches the export endpoint output. Capped at 32 MiB upload.
 */


/**
 * Stream the test's URLs as a downloadable text file. Produced by the
 * daemon's /urls/export endpoint — same line format that import accepts.
 */
function urls_download(){
    $testId = $_GET["urls-download"];
    if($testId === ''){
        http_response_code(400); echo "test id required"; return;
    }
    $siege = new SiegeDaemon();
    $body = $siege->exportTestURLs($testId);
    if($body === null){
        http_response_code(500); echo $siege->getLastError(); return;
    }
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $testId . '-urls.txt"');
    header('Content-Length: ' . strlen($body));
    echo $body;
}

function edit_test_urls_save(){
    $tpl = new template_admin();
    $tpl->CLEAN_POST();

    $testId = $_POST["edit-test-urls"];
    if(empty($testId)){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: Test ID {required}"));
        return false;
    }

    $siege = new SiegeDaemon();
    $currentTest = $siege->getTest($testId);
    if($currentTest === null){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: " . $siege->getLastError()));
        return false;
    }

    // Get URLs from POST
    $urlsRaw = trim($_POST["test_urls"] ?? '');

    // Use default URLs if empty
    if(strlen($urlsRaw) < 5){
        $urlsRaw = @file_get_contents("/usr/share/artica-postfix/bin/install/squid/urls.txt");
    }

    if(empty($urlsRaw)){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: URLs {required}"));
        return false;
    }

    // Parse URLs (one per line)
    // Format: URL or METHOD|URL or METHOD|URL|BODY
    $urlLines = array_filter(array_map('trim', explode("\n", $urlsRaw)));
    $urls = [];
    foreach($urlLines as $url){
        if(!empty($url)){
            $parts = explode("|", $url);
            $urlConfig = ['url' => $parts[0]];
            if(isset($parts[1])){
                $urlConfig = ['method' => $parts[0], 'url' => $parts[1]];
            }
            if(isset($parts[2])){
                $urlConfig['body'] = $parts[2];
                $urlConfig['content_type'] = 'application/json';
            }
            if(!isset($urlConfig['method'])){
                $urlConfig['method'] = 'GET';
            }
            $urls[] = $urlConfig;
        }
    }

    if(empty($urls)){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: {invalid} URLs"));
        return false;
    }

    // Build updated config - only change URLs.
    // Keep $config['id'] so createTest upserts in place (INSERT OR
    // REPLACE on id) — see the explanatory comment in
    // edit_test_kerberos_save for why stripping the id makes the
    // row disappear from the main table.
    $config = $currentTest;
    unset($config['created_at']);
    $config['urls'] = $urls;

    $newTest = $siege->createTest($config);

    if($newTest === null){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: " . $siege->getLastError()));
        return false;
    }

    $testName = $config['name'] ?? $testId;
    $urlCount = count($urls);
    admin_tracks("Updated URLs ($urlCount) for siege test '$testName'");
    return true;
}

function subject_popup(){
    $ID=intval($_GET["subject-popup"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/siege.db");
    $ligne=$q->mysqli_fetch_array("SELECT subject FROM reports WHERE ID=$ID");
    $subject=$ligne["subject"];
    $md=$_GET["md"];
    $form[]=$tpl->field_hidden("subject_id",$ID);
    $form[]=$tpl->field_text("subject_edit","{description}",$subject,true);
    $js="dialogInstance2.close();LoadAjaxSilent('subject-$md','$page?subject-fill=$ID')";
    echo $tpl->form_outside("{report}: $ID", @implode("\n", $form),null,"{save}",$js,"AsProxyMonitor");
}
function subject_edit(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/siege.db");
    $id=intval($_POST["subject_id"]);
    $subject=$q->sqlite_escape_string2($_POST["subject_edit"]);
    $q->QUERY_SQL("UPDATE reports SET subject='$subject' WHERE ID=$id");
    if(!$q->ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($q->mysql_error));return false;}
    admin_tracks("Change stress tool report subject to $subject");
    return true;
}

function subject_fill(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["subject-fill"]);
    $q=new lib_sqlite("/home/artica/SQLITE/siege.db");
    $ligne=$q->mysqli_fetch_array("SELECT subject FROM reports WHERE ID=$ID");
    $subject = $tpl->td_href($ligne["subject"],null,"Loadjs('$page?report=$ID')");
    echo $subject;
}
function report_js(){
    $ID=intval($_GET["report"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/siege.db");
    $ligne=$q->mysqli_fetch_array("SELECT subject FROM reports WHERE ID=$ID");
    $subject=$ligne["subject"];
    $tpl->js_dialog1("{report} $subject","$page?report-popup=$ID");

}

function report_popup(){
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/siege.db");
    $ID=intval($_GET["report-popup"]);
    $ligne=$q->mysqli_fetch_array("SELECT report FROM reports WHERE ID=$ID");
    $report=base64_decode($ligne["report"]);
    if(!preg_match("#\{(.+?)\}#is",$report,$re)){
        echo $tpl->div_error("{failed} <code>$report</code>");
        return false;
    }
    $json=json_decode("{".$re[1]."}");
    $transactions=$json->transactions;
    $availability=$json->availability;
    $data_transferred=$json->data_transferred;
    $availability_w=$tpl->widget_h("green","fa-thumbs-up","$availability%","{availability}");
    if($availability<60){
        $availability_w=$tpl->widget_h("yellow","fa-thumbs-up","$availability","{availability}");
    }
    if($availability<30){
        $availability_w=$tpl->widget_h("red","fa-thumbs-down","$availability","{availability}");
    }

    $response_time=$json->response_time;
    $transaction_rate=$json->transaction_rate;
    $throughput=$json->throughput;
    $concurrency=$json->concurrency;
    $s_transactions=$json->successful_transactions;
    $f_transactions=$json->failed_transactions;
    $longest_transaction=$json->longest_transaction;
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='padding-left:5px'>";
    $html[]=$tpl->widget_h("blue","fa-cloud","$transactions/{$data_transferred}MB","{transactions}");
    $html[]="</td>";
    $html[]="<td style='padding-left:5px'>";
    $html[]=$tpl->widget_h("blue","far fa-arrow-to-right","{$throughput}MB","{throughput}");
    $html[]="</td>";
    $html[]="</tr><tr>";
    $html[]="<td style='padding-left:5px'>";
    $html[]=$availability_w;
    $html[]="</td>";
    $html[]="<td style='padding-left:5px'>";
    $html[]=$tpl->widget_h("lazur","fas fa-exchange-alt","$transaction_rate trans/sec","{transaction_rate}");
    $html[]="</td>";
    $html[]="</tr><tr>";
    $html[]="<td style='padding-left:5px'>";
    $html[]=$tpl->widget_h("blue","fas fa-random","$s_transactions/$f_transactions",
        "{s_transactions}/{f_transactions}");
    $html[]="</td>";
    $html[]="<td style='padding-left:5px'>";
    $html[]=$tpl->widget_h("yellow","fas fa-hourglass","$longest_transaction sec","{longest_transaction}");
    $html[]="</td>";
    $html[]="</tr><tr>";
    $html[]="<td style='padding-left:5px'>";
    $html[]=$tpl->widget_h("blue","fa-clock","$response_time sec","{response_time}");
    $html[]="</td>";
    $html[]="<td style='padding-left:5px'>";
    $html[]=$tpl->widget_h("blue","fal fa-compress-arrows-alt","$concurrency","{concurrency}");
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function urls(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SIEGE_URLS=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SIEGE_URLS");
    if($SIEGE_URLS==null){$SIEGE_URLS=@file_get_contents("/usr/share/artica-postfix/bin/install/squid/urls.txt");}
    $form[]=$tpl->field_textarea_normal("SIEGE_URLS", "", $SIEGE_URLS,"100%");

     echo $tpl->form_outside("URLs: ".count(explode("\n",$SIEGE_URLS)), @implode("\n", $form),null,"{save}",null,"AsProxyMonitor");
}
function SaveUrls(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SIEGE_URLS",$_POST["SIEGE_URLS"]);
    admin_tracks("Stress stool urls updated");
}


function status(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/siege/status"));
    if(!$json->Status){
        echo $tpl->widget_rouge($json->Error,"{failed}");
        return false;
    }

    $ini = new Bs_IniHandler();
    $ini->loadString($json->Info);
    $jsrestart=$tpl->framework_buildjs(
        "/siege/restart","siege.install.progress",
        "siege.install.progress.logs",
        "progress-siege-restart");
    echo $tpl->SERVICE_STATUS($ini,"APP_SIEGE",$jsrestart);
    return true;
}


function page():bool{
   $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{APP_SIEGE}",ico_performance,
        "{APP_SIEGE_EXPLAIN}","$page?table=yes","siege","progress-siege-restart",false,"table-siege-status");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica:{APP_SIEGE}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html[]="<table style='width:100%;margin-top:10px'>";
	$html[]="<tr>";
	$html[]="<td style='width:260px;vertical-align: top'>";
	$html[]="<div id='siege-status'></div></td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:15px'>";
    $html[]="<div id='siege-search'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $js=$tpl->RefreshInterval_js("siege-status",$page,"status=yes");

    $html[]="LoadAjax('siege-search','$page?search-top=yes');";
    $html[]=$js;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}
function search(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $siege=new SiegeDaemon();
    $function = $_GET["function"] ?? "LoadAjax('siege-search','$page?search=yes')";
    $functionEncoded = urlencode($function);

    if(!$siege->isHealthy()){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{daemon_not_running}: " . $siege->getLastError()));
        return;
    }

    // Get running tests for status indication
    $runningTests = $siege->getRunningTests();
    $results=$siege->listTests();

    $AllID=array(0);
    $rows=array();
    $fatal=null;
    if($results === null){
        $fatal=$tpl->div_error($siege->getLastError());
    }elseif(is_array($results)){
        foreach ($results as $ligne){
            $testId = $ligne["id"] ?? '';
            $AllID[]=$testId;
            $rows[]=siege_test_row($ligne,$runningTests,$functionEncoded,$tpl,$page);
        }
    }
    $c=count($rows);

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    // L'id table-siege-after-search est une SENTINELLE, pas une ancre footable:
    // RefreshInterval_Loadjs() s'en sert pour savoir si le fragment est encore a
    // l'ecran, et ArticaRefresh.tick() detruit le minuteur -- en silence, AVANT
    // la premiere requete -- s'il ne le trouve pas. Le perdre en passant au
    // design awc n'aurait produit aucune erreur: les lignes s'afficheraient, et
    // toutes les cellules remplies par JS (etat, progression, rapport, PDF)
    // resteraient vides pour toujours.
    $html[]="<table id='table-siege-after-search' class='awc-tbl'><thead><tr>";
    $html[]="<th>{name}</th>";
    $html[]="<th class='center'>URLs</th>";
    $html[]="<th class='center'>{members}</th>";
    $html[]="<th class='center'>Auth.</th>";
    $html[]="<th class='center'>{duration}</th>";
    $html[]="<th>{progress}</th>";
    $html[]="<th class='center'>{status}</th>";
    $html[]="<th style='text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='siege-test-rows'>";
    if($fatal!==null){
        $html[]="<tr><td colspan='8'>$fatal</td></tr>";
    }elseif($c==0){
        $html[]="<tr><td colspan='8'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $c {items}</span>";
    $html[]="<span class='awc-pg' id='siege-test-pager'></span>";
    $html[]="</div></div></div>";

    $jsrestart=$tpl->framework_buildjs(
        "/siege/restart","siege.install.progress",
        "siege.install.progress.logs",
        "progress-siege-restart");

    $version=$siege->getVersionString();

    $topbuttons=array();
    $topbuttons[] = array($jsrestart, ico_retweet,"{restart}");
    $topbuttons[] = array("Loadjs('$page?create-test-js=yes&function=$function')", ico_plus, "{new_stress_test}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{APP_SIEGE} v.$version";
    $TINY_ARRAY["ICO"]="ico_performance";
    $TINY_ARRAY["EXPL"]="{APP_SIEGE_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $ids=@implode("|",$AllID);
    $refreshjs=$tpl->RefreshInterval_Loadjs("table-siege-after-search",$page,"status-ids=$ids");

    // Le <script> est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body prendrait chaque accolade JavaScript pour un {token}.
    $js=array();
    $js[]="NoSpinner();";
    $js[]=$tpl->ICON_SCRIPTS_COMPILE();
    $js[]="$(document).ready(function() { ";
    $js[]=$refreshjs;
    $js[]="});";
    $js[]=$jstiny;
    $script="<script>".@implode("\n",$js)."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$script
        .awc_pager_js(50,"siege-test-rows","siege-test-pager");
}

/**
 * Une ligne « test de charge ».
 *
 * Douze colonnes -- dont quatre sans en-tete -- portaient une icone chacune. Le
 * design awc les regroupe dans une seule cellule d'actions, ce qui rend la
 * colonne du nom lisible sans rien retirer.
 *
 * TOUS les identifiants sont conserves a l'identique, car ils sont ecrits de
 * l'exterieur: la <tr> porte $md (delete_test_js fait $('#<md>').remove()), et
 * les six <span> status-/progress-/report-/pdf-/error-/delete-<testId> sont
 * remplis par td_states() a chaque tick de rafraichissement.
 */
function siege_test_row($ligne,$runningTests,$functionEncoded,$tpl,$page):string{
    $md=md5(serialize($ligne));
    $testId = $ligne["id"] ?? '';
    $testName = $ligne["name"] ?? $testId;
    $concurrentUsers = intval($ligne["concurrent_users"] ?? 10);
    $duration = $ligne["duration"] ?? '-';
    $urlCount = count($ligne["urls"] ?? array());

    $status=td_status($testId,$runningTests);
    $delete=$tpl->icon_delete("Loadjs('$page?delete-test-js=$testId&md=$md')");
    $check=td_check($testId, $runningTests);
    $duplicate=td_duplicate($testId, $runningTests);
    $auth=td_auth($ligne);
    $running=isRunning($testId,$runningTests);

    $h ="<tr id='$md' data-testid='$testId'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico".($running?"":" awc-ico-off")."'><i class='fas fa-gauge-high'></i></span>";
    // Le nom du test vient du demon et partait BRUT dans le HTML.
    $h.="<span><span class='awc-name'>"
       .$tpl->td_href(htmlspecialchars((string)$testName),"{click_to_edit}","Loadjs('$page?edit-test-js=$testId&function=$functionEncoded');")
       ."</span>";
    $h.="<div class='awc-meta awc-mono'>".htmlspecialchars((string)$testId)."<span id='error-$testId'></span></div>";
    $h.="</span></div></td>";
    $h.="<td class='center'><span class='awc-tag'>$urlCount</span></td>";
    $h.="<td class='center'>$concurrentUsers</td>";
    $h.="<td class='center'>$auth</td>";
    $h.="<td class='center awc-mono'>".htmlspecialchars((string)$duration)."</td>";
    $h.="<td><span id='progress-$testId'></span></td>";
    $h.="<td class='center'><span id='status-$testId'>$status</span></td>";
    $h.="<td><div class='awc-acts'>"
       ."$check$duplicate"
       ."<span id='report-$testId'></span>"
       ."<span id='pdf-$testId'></span>"
       ."<span id='delete-$testId'>$delete</span>"
       ."</div></td>";
    $h.="</tr>";
    return $h;
}
function td_states(){
    header("content-type: application/x-javascript");
    $ids=explode("|",$_GET["status-ids"]);
    $siege=new SiegeDaemon();
    $runningTests = $siege->getRunningTests();
    $f=array();
    foreach ($ids as $testId){
        if($testId==0){
            continue;
        }
        echo "// $testId ?\n";
        $status=base64_encode(td_status($testId,$runningTests));
        $progress=base64_encode(td_progress($testId));
        $report=base64_encode(td_reports($testId,$runningTests));
        $reportpdf=base64_encode(td_pdf($testId,$runningTests));
        $error_report=base64_encode(td_error($testId,$runningTests));
        $f[]="if ( document.getElementById('status-$testId') ){";
        $f[]="\tdocument.getElementById('status-$testId').innerHTML=base64_decode('$status');";
        $f[]="}";
        $f[]="if ( document.getElementById('progress-$testId') ){";
        $f[]="\tdocument.getElementById('progress-$testId').innerHTML=base64_decode('$progress');";
        $f[]="}";
        $f[]="if ( document.getElementById('report-$testId') ){";
        $f[]="\tdocument.getElementById('report-$testId').innerHTML=base64_decode('$report');";
        $f[]="}";
        $f[]="if ( document.getElementById('pdf-$testId') ){";
        $f[]="\tdocument.getElementById('pdf-$testId').innerHTML=base64_decode('$reportpdf');";
        $f[]="}";
        $f[]="if ( document.getElementById('error-$testId') ){";
        $f[]="\tdocument.getElementById('error-$testId').innerHTML=base64_decode('$error_report');";
        $f[]="}";
        // status-$testId report-$testId //error-$testId
    }
    echo @implode("\n",$f);
}

function isRunning($testId,$runningTests):bool{
    $isRunning=false;
    if($GLOBALS["VERBOSE"]){
        echo "-- > $testId\n";
        print_r($runningTests);
    }
    $runningIds = array();
    if($runningTests !== null && isset($runningTests['count'])){

        if($runningTests["count"]>0){
            foreach ($runningTests['running_tests'] as $key){
                $runningIds[$key]=true;
            }

        }
    }
    if(isset($runningIds[$testId])){
        return true;
    }
    return false;
}

// td_auth renders the "Auth" column for a single test row. Counts
// configured Kerberos principals and returns a badge with the number,
// or an em-dash when the test isn't using Kerberos. Two data sources:
//
//   - kerberos_principals: the multi-user pool the daemon round-robins
//     across VUs. When present, its length is the count we display.
//   - proxy_auth.principal: the single-user fallback path. Present
//     in older configs and still used when kerberos_principals is
//     empty. Counted as 1.
//
// Non-Kerberos auth (basic, none, ntlm) and tests with no proxy_auth
// at all render as "—" rather than "0" so operators don't confuse
// "no Kerberos configured" with "Kerberos misconfigured to zero users".
function td_auth(array $test):string{
    $count = 0;
    if(isset($test['kerberos_principals']) && is_array($test['kerberos_principals'])){
        foreach($test['kerberos_principals'] as $p){
            if(!empty($p['principal'])){ $count++; }
        }
    }
    if($count === 0
        && !empty($test['proxy_auth']['type'])
        && strtolower($test['proxy_auth']['type']) === 'kerberos'
        && !empty($test['proxy_auth']['principal'])){
        $count = 1;
    }
    if($count === 0){
        return "<span style='color:var(--text-muted, #94a3b8)'>&mdash;</span>";
    }
    $title = htmlspecialchars(
        "Kerberos principals: " . $count,
        ENT_QUOTES
    );
    return "<span class='badge' title='$title'><i class='fas fa-key' style='margin-right:4px'></i>$count</span>";
}

function td_status($testId,$runningTests):string{
    $page=CurrentPageName();
    $tpl = new template_admin();
    $safe = htmlspecialchars($testId, ENT_QUOTES);

    if(isRunning($testId,$runningTests)){
        // Stopping: optimistic flip to "Stopping..." so the cell can't
        // be re-clicked (and read as a fresh "Run" action) in the gap
        // between click and server response.
        $stoppingLabel = $tpl->javascript_parse_text("{stopping}");
        $onClick = "var c=document.getElementById('status-$safe');"
            . "if(c){c.innerHTML='<span class=\\'label label-warning\\'>"
            . "<i class=\\'fas fa-spinner fa-spin\\'></i> $stoppingLabel</span>';}"
            . "Loadjs('$page?stop-test-js=$safe');";
        $attrs = "OnClick=\"$onClick\" OnMouseOver=\"this.style.cursor='pointer';\" OnMouseOut=\"this.style.cursor='default';\"";
        return $tpl->_ENGINE_parse_body(
            "<span class='label label-success' $attrs><i class='fas fa-play'></i> {running}</span>"
        );
    }

    // Starting: optimistic flip to "Starting..." with no click handler,
    // so a frustrated second click does nothing (no race, no accidental
    // stop, no duplicate start that returns "already running"). The
    // PHP handler's response then replaces this with the canonical
    // "running" cell with its stop click handler.
    $startingLabel = $tpl->javascript_parse_text("{starting}");
    $onClick = "var c=document.getElementById('status-$safe');"
        . "if(c){c.innerHTML='<span class=\\'label label-info\\'>"
        . "<i class=\\'fas fa-spinner fa-spin\\'></i> $startingLabel</span>';}"
        . "Loadjs('$page?start-test-js=$safe');";
    $attrs = "OnClick=\"$onClick\" OnMouseOver=\"this.style.cursor='pointer';\" OnMouseOut=\"this.style.cursor='default';\"";
    return $tpl->_ENGINE_parse_body(
        "<span class='label label-default' $attrs><i class='fas fa-stop'></i> {stopped}</span>"
    );
}
function td_error($testId,$runningTests):string{

    if (isRunning($testId,$runningTests)){
        return "&nbsp;";
    }

    $siege = new SiegeDaemon();

    // Check if it failed to start
    if ($siege->hasTestStartupError($testId)) {
        $rawError = $siege->getTestStartupErrorMessage($testId);
        // Translate known Kerberos failure patterns into actionable hints BEFORE
        // the colon-to-<br> transformation (the hint text contains colons too
        // and would get mangled otherwise).
        $hint  = siege_humanize_kerberos_startup_error_hint($rawError);
        $error = str_replace(":", ":<br>", $rawError);
        return "<br><span class='text-danger'>$error</span>" . $hint;
    }
    return "";


}
/**
 * Render the per-row "Check" button: a small link that opens the smoke-check
 * popup. Hidden while the test is running (would race with the live engine
 * for proxy credentials and muddy the check result).
 */
function td_check($testId, $runningTests):string{
    if (isRunning($testId, $runningTests)){
        return "&nbsp;";
    }
    $tpl = new template_admin();
    $page = CurrentPageName();
    $title = $tpl->javascript_parse_text("{check_test}");
    // Use an inline anchor with the bolt/flask icon — visually distinct from
    // the play/stop buttons so operators don't mistake this for launching.
    return "<a href=\"javascript:Loadjs('$page?check-test-js=$testId');\" "
         . "title=\"$title\" class='btn btn-xs btn-default'>"
         . "<i class='fas fa-vial'></i></a>";
}

/**
 * Render the per-row "Duplicate" button. Single click: the daemon copies
 * the test config, names it "<original> (copied)", and the search table is
 * refreshed so the new row appears at the bottom. Hidden while the source
 * test is running so we don't snapshot a config the engine is in the middle
 * of mutating.
 */
function td_duplicate($testId, $runningTests):string{
    if (isRunning($testId, $runningTests)){
        return "&nbsp;";
    }
    $tpl = new template_admin();
    $page = CurrentPageName();
    $title = $tpl->javascript_parse_text("{duplicate}");
    return "<a href=\"javascript:Loadjs('$page?duplicate-test-js=$testId');\" "
         . "title=\"$title\" class='btn btn-xs btn-default'>"
         . "<i class='fas fa-clone'></i></a>";
}

function td_reports($testId,$runningTests):string{
    $tpl = new template_admin();
    if (isRunning($testId,$runningTests)){
        return "&nbsp;";
    }

    if(!hasReport($testId)){
        return "&nbsp;";
    }
    $page=CurrentPageName();

    return $tpl->icon_stats("s_PopUpFull('$page?report-html=$testId',1024,1024)");
}
function td_pdf($testId,$runningTests):string{
    $tpl = new template_admin();
    if (isRunning($testId,$runningTests)){
        return "&nbsp;";
    }

    if(!hasReport($testId)){
        return "&nbsp;";
    }
    $page=CurrentPageName();

    return $tpl->icon_pdf("document.location.href='$page?pdf-report=$testId'");
}
function report_pdf():bool{
    $siege = new SiegeDaemon();
    $testId = $_GET["pdf-report"];

    $pdf = $siege->getTestReportPdf($testId);

    if ($pdf !== null) {
        // Send to browser for download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="siege-report-' . $testId . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    } else {
        echo "Error: " . $siege->getLastError();
    }
    return true;
}

function reports(){
    $tpl = new template_admin();
    $page = CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/siege.db");
    $jshelp="s_PopUpFull('https://wiki.articatech.com/en/proxy-service/tuning/stress-your-proxy-server',1024,768,'Stress Tool')";

    $sql = "SELECT * FROM reports ORDER BY ID DESC";
    $results = $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error . "<br>$sql");
        return;
    }

    $rows=array();
    foreach ($results as $ligne){
        $rows[]=siege_report_row($ligne,$tpl,$page);
    }
    $c=count($rows);

    $html=array();
    $html[] = "<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
    $html[] = "<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?import-upload-js=yes')\"><i class='fas fa-file-import'></i> {analyze_access_log} </label>";
    $html[] = "<label class=\"btn btn btn-info\" OnClick=\"$jshelp\"><i class='fas fa-question-circle'></i> Wiki </label>";
    $html[] = "</div>";

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
    $html[]="<thead><tr>";
    $html[]="<th>{subject}</th>";
    $html[]="<th>{target}</th>";
    $html[]="<th class='center'>{members}</th>";
    $html[]="<th class='center'>{duration}</th>";
    $html[]="<th style='text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='siege-rep-rows'>";
    if($c==0){
        $html[]="<tr><td colspan='5'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $c {items}</span>";
    $html[]="<span class='awc-pg' id='siege-rep-pager'></span>";
    $html[]="</div></div></div>";

    // Le <script> est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body prendrait chaque accolade JavaScript pour un {token}.
    // La requete SQL brute qui s'affichait sous le tableau etait un reste de
    // mise au point: elle exposait le schema a l'operateur sans rien lui
    // apprendre d'utile.
    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$script
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"siege-rep-rows","siege-rep-pager");
}

/**
 * Une ligne « rapport ». La date, jusqu'ici premiere colonne, devient le
 * sous-titre du sujet: c'est le sujet que l'operateur cherche, la date le
 * situe.
 *
 * Les deux identifiants sont conserves: la <tr> porte $md (delete_js fait
 * $('#<md>').remove()) et le sujet reste dans <span id='subject-<md>'>, que
 * subject_popup() recharge par LoadAjaxSilent apres un renommage.
 */
function siege_report_row($ligne,$tpl,$page):string{
    $md = md5(serialize($ligne));
    $ID = intval($ligne["ID"]);
    $date = strtotime($ligne["zdate"]);
    $zend = strtotime($ligne["zend"]);
    // time_to_date() renvoie du texte UTF-8 simple: l'echapper ici est correct.
    $date_text = htmlspecialchars($tpl->time_to_date($date, true));
    $duration = distanceOfTimeInWords($date,$zend);
    $users = intval($ligne["users"]);
    $target = (string)$ligne["target"];

    $subject = $tpl->td_href(htmlspecialchars((string)$ligne["subject"]),"{view2}","Loadjs('$page?report=$ID')");
    $delete=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$md')","AsProxyMonitor");
    $edit=$tpl->icon_parameters("Loadjs('$page?subject-js=$ID&md=$md')");

    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-file-chart-column'></i></span>";
    $h.="<span><span class='awc-name'><span id='subject-$md'>$subject</span></span>";
    $h.="<div class='awc-meta'>$date_text</div>";
    $h.="</span></div></td>";
    $h.="<td class='awc-mono'>".($target!==""
        ?htmlspecialchars($target)
        :"<span class='awc-none'>&mdash;</span>")."</td>";
    $h.="<td class='center'>$users</td>";
    $h.="<td class='center awc-mono'>$duration</td>";
    $h.="<td><div class='awc-acts'>$edit$delete</div></td>";
    $h.="</tr>";
    return $h;
}



function settings(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ARRAY=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSiegeConfig"));
    if(!is_numeric($ARRAY["GRAB_URLS"])){$ARRAY["GRAB_URLS"]=0;}
    if(!is_numeric($ARRAY["USE_LOCAL_PROXY"])){$ARRAY["USE_LOCAL_PROXY"]=0;}
    if(!is_numeric($ARRAY["SESSIONS"])){$ARRAY["SESSIONS"]=150;}
    if(!is_numeric($ARRAY["MAX_TIME"])){$ARRAY["MAX_TIME"]=30;}
    if(!is_numeric($ARRAY["REMOTE_PROXY_PORT"])){$ARRAY["REMOTE_PROXY_PORT"]=3128;}
    if(!isset($ARRAY["CONNECTION"])){$ARRAY["CONNECTION"]="keep-alive";}


    $cnxs["keep-alive"]="keep-alive";
    $cnxs["close"]="close";

    $script=$tpl->framework_buildjs("/siege/run",
        "squid.siege.progress","squid.siege.progress.txt","progress-siege-restart");

    $form[]=$tpl->field_text("REMOTE_PROXY","{remote_proxy}",$ARRAY["REMOTE_PROXY"]);
    $form[]=$tpl->field_numeric("REMOTE_PROXY_PORT","{remote_port}",$ARRAY["REMOTE_PROXY_PORT"]);
    $form[]=$tpl->field_text("USERNAME","{username}",$ARRAY["USERNAME"]);
    $form[]=$tpl->field_password("PASSWORD","{password}",$ARRAY["PASSWORD"]);
    $form[]=$tpl->field_numeric("SESSIONS","{simulate} ({members})",$ARRAY["SESSIONS"]);
    $form[]=$tpl->field_numeric("MAX_TIME","{execution_time} ({seconds})",$ARRAY["MAX_TIME"]);
    $form[]=$tpl->field_array_hash($cnxs,"CONNECTION","{connection}",$ARRAY["CONNECTION"]);

    $html[]=$tpl->form_outside("",@implode("\n", $form),null,"{launch_test}",$script,"AsProxyMonitor");

    $refresh=$tpl->RefreshInterval_js("siege-status",$page,"status=yes");

    $html[]="<script>$refresh</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));



}

function Save(){

	$tpl=new template_admin();
	$tpl->CLEAN_POST();

    $ARRAY=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSiegeConfig"));
	
	foreach ($_POST as $key=>$value){
        $ARRAY[$key]=$value;
	}
    admin_tracks("Running a stress tool to the targeted proxy {$_POST["REMOTE_PROXY"]}");
    $newval=base64_encode(serialize($ARRAY));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidSiegeConfig",$newval);

}
function hasReport(string $testId): bool {
    $siege = new SiegeDaemon();
    return ($siege->getTestReport($testId) !== null);
}

/**
 * Create a keytab using ktutil from username/password
 * POST parameters:
 *   - principal: Username (without @REALM)
 *   - password: User password
 *   - realm: Kerberos realm (uppercase)
 *   - kdc_host: KDC/AD server hostname or IP (optional, for validation)
 *   - keytab_path: Output keytab path (optional)
 */

function create_keytab_popup(){
    $testId = $_GET["create-keytab"];
    $tpl = new template_admin();
    $page = CurrentPageName();

    $function=$_GET["function"];
    $siege = new SiegeDaemon();
    $test = $siege->getTest($testId);

    if($test === null){
        echo $tpl->div_error("{error}: " . $siege->getLastError());
        return false;
    }

    //$keytabPath = $test['proxy_auth']['keytab_path'] ?? '/etc/siege-daemon/siege.keytab';
    $principal = $test['proxy_auth']['principal'] ?? '';
    $realm = $test['proxy_auth']['realm'] ?? '';
    $kdcHost = $test['proxy_auth']['kdc_host'] ?? '';
    $password = $test['proxy_auth']['password'] ?? '';

    // Informational note about the SPN-salt pitfall (see create_keytab_save).
    // If the target AD account has a servicePrincipalName, ktutil's default
    // salt will not match AD's salt and kinit will fail preauthentication.
    // We cannot detect this from the form alone, so warn the operator.

    // Summarize the currently-stored principal pool, if any — usernames only,
    // never passwords. Lets an operator see at a glance which accounts this
    // test already has keytabs for, and re-enter passwords for those users to
    // refresh (or add new ones below).
    $existing = $test['kerberos_principals'] ?? [];
    $ADDON1="";
    $users="";
    if(!empty($existing) && is_array($existing)){
        $names = [];
        foreach($existing as $p){
            if(!empty($p['principal'])){ $names[] = htmlspecialchars($p['principal']); }
        }
        if(!empty($names)){
            $ADDON1="{kerberos_existing_users}";
            $users="<div style='font-weight:bold;font-size:18px'><i class='fa-1x ".ico_users."'></i>&nbsp;{members}: ".implode(', ', $names)."</div><p style='font-size:11px;color: #a7a5a5;'>{kerberos_existing_users_note}</p>";
        }
        $form[] = $tpl->div_info("$ADDON1$users");
    }






    // Build form
    $form[] = $tpl->field_hidden("create-keytab", $testId);

    // Primary user: the existing single-user fields. On creation this is the
    // only required entry; the "additional users" textarea below is optional.
    $form[] = $tpl->field_section("{kerberos_primary_user}","{create_keytab_user_account_hint}",false,ico_user);
    $form[] = $tpl->field_text(
        "principal",
        "{username}",
        $principal,
        true,
        "{create_keytab_username_tooltip}"
    );
    $form[] = $tpl->field_password("password", "{password}", $password, true,"");

    // Additional users (optional). Each line "username:password" creates one
    // extra keytab. At test time the daemon's PrincipalPool round-robins these
    // across virtual users, so every VU authenticates as a distinct AD user —
    // realistic KDC load and realistic per-user ticket lifecycle, instead of
    // 1,500 VUs sharing one TGT.
    $form[] = $tpl->field_section("{kerberos_additional_users}");
    $form[] = $tpl->field_textarea_normal(
        "additional_users",
        "{kerberos_additional_users_label}",
        "",
        "100%",
        6,
        "{kerberos_additional_users_tooltip}"
    );

    $form[] = $tpl->field_section("{activedirectory_domain}");
    $form[] = $tpl->field_text("realm", "{activedirectory_domain}", $realm, true);
    $form[] = $tpl->field_text("kdc_host", "{activedirectory_server}", $kdcHost);

    $js = "dialogInstance1.close();$function();";
    echo $tpl->form_outside("", @implode("\n", $form), null, "{save}", $js, "AsProxyMonitor");
    return true;
}
function create_keytab_save():bool{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();

    $testId=$_POST["create-keytab"];
    $principal = isset($_POST['principal']) ? trim($_POST['principal']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $realm = isset($_POST['realm']) ? trim(strtoupper($_POST['realm'])) : '';
    $kdcHost = isset($_POST['kdc_host']) ? trim($_POST['kdc_host']) : '';
    $additionalUsersRaw = isset($_POST['additional_users']) ? $_POST['additional_users'] : '';

    // Accept admin-friendly formats on the primary user: bare username,
    // user@realm, or stray SPN prefix.
    if(preg_match("#^HTTP:?/+([^@]+)#i", $principal, $re)){
        $principal = $re[1];
    }
    if(strpos($principal, '@') !== false){
        $parts = explode('@', $principal, 2);
        $principal = trim($parts[0]);
        if(empty($realm) && !empty($parts[1])){
            $realm = strtoupper(trim($parts[1]));
        }
    }

    // Validate required fields
    if(empty($principal)){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: Principal/Username {required}"));
        return false;
    }

    if(empty($password)){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: Password {required}"));
        return false;
    }

    if(empty($realm)){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: Realm {required}"));
        return false;
    }

    // Collect the full principal list: primary first, then each parsed
    // "username:password" line from the additional-users textarea.
    // Lines starting with "#" are ignored (comments). Blank lines skipped.
    // Duplicate usernames (case-insensitive) are rejected — two keytabs for
    // the same account would collide during distribution and mask misconfig.
    $principals = [
        ['username' => $principal, 'password' => $password],
    ];
    $seenUsers = [strtolower($principal) => true];

    $lineNum = 1;
    foreach(preg_split("/\r?\n/", trim($additionalUsersRaw)) as $line){
        $lineNum++;
        $line = trim($line);
        if($line === '' || $line[0] === '#'){ continue; }
        if(strpos($line, ':') === false){
            echo (new template_admin())->post_error("" . $tpl->javascript_parse_text(
                "{error}: {kerberos_additional_users_bad_line} $lineNum"
            ));
            return false;
        }
        [$u, $p] = explode(':', $line, 2);
        $u = trim($u);
        // Trim the password too: a space after "user:" is almost always a
        // visual separator from copy-paste, not intentional password content.
        // Leaving it in silently produces "Preauthentication failed" at
        // validation time, which is impossible for an operator to diagnose
        // without dumping the raw POST body. (Real passwords with leading or
        // trailing whitespace are extremely rare and would also break any
        // copy/paste flow, so trimming them is a safer default.)
        $p = trim($p);
        if($u === '' || $p === ''){
            echo (new template_admin())->post_error("" . $tpl->javascript_parse_text(
                "{error}: {kerberos_additional_users_bad_line} $lineNum"
            ));
            return false;
        }
        // Strip realm and SPN prefix on additional users too
        if(preg_match("#^HTTP:?/+([^@]+)#i", $u, $re)){ $u = $re[1]; }
        if(strpos($u, '@') !== false){ $u = trim(explode('@', $u, 2)[0]); }

        $key = strtolower($u);
        if(isset($seenUsers[$key])){
            echo (new template_admin())->post_error("" . $tpl->javascript_parse_text(
                "{error}: {kerberos_additional_users_duplicate}: $u"
            ));
            return false;
        }
        $seenUsers[$key] = true;
        $principals[] = ['username' => $u, 'password' => $p];
    }

    $siege = new SiegeDaemon();

    // Check if daemon is healthy
    if(!$siege->isHealthy()){
        echo $tpl->post_error($tpl->javascript_parse_text("{error}: Siege daemon {not_running}"));
        return false;
    }

    // Preflight: verify the KDC is reachable on TCP/88 BEFORE wasting ~30s
    // on kinit timeout. Runs once for the whole pool — all principals share
    // the same realm/KDC in this UI, so one probe covers all of them.
    if(!empty($kdcHost)){
        $pf = $siege->kerberosPreflight($kdcHost);
        if($pf !== null && empty($pf['success'])){
            $pfMsg = siege_format_preflight_error($pf, $tpl);
            echo $tpl->post_error(
                $tpl->javascript_parse_text("{kerberos_preflight_failed}") . ": " . $pfMsg
            );
            return false;
        }
    }

    // Generate one keytab per principal and, when KDC is known, validate each
    // one via kinit. Each keytab lives at /etc/siege-daemon/<testId>-<user>.keytab
    // so multiple principals in the same test coexist without collision.
    // The first successful entry doubles as the "primary" entry written into
    // proxy_auth, preserving backward compatibility with daemon code that still
    // references the single proxy_auth field.
    $createdPrincipals = []; // [{keytab_path, principal, realm, kdc_host}]

    foreach($principals as $i => $entry){
        $u = $entry['username'];
        $p = $entry['password'];
        $keytabPath = "/etc/siege-daemon/" . $testId . "-" . preg_replace('/[^A-Za-z0-9._-]/', '_', $u) . ".keytab";

        $result = $siege->createKeytabWithKtutil($u, $p, $realm, $keytabPath);
        if($result === null){
            echo $tpl->post_error($tpl->javascript_parse_text(
                "{error}: {kerberos_keytab_create_failed} ($u): " . $siege->getLastError()
            ));
            return false;
        }
        if(!isset($result['success']) || !$result['success']){
            $err = siege_format_daemon_error($result, $siege->getLastError());
            echo $tpl->post_error($tpl->javascript_parse_text(
                "{error}: {kerberos_keytab_create_failed} ($u): " . $err
            ));
            return false;
        }

        // Validate each keytab against the live KDC. A single bad user would
        // otherwise silently corrupt the pool and surface only at runtime as
        // intermittent auth failures for a subset of virtual users.
        if(!empty($kdcHost)){
            $vr = $siege->validateKeytab($keytabPath, $u, $realm, $kdcHost);
            if($vr === null || empty($vr['valid'])){
                $parts = [];
                foreach(($vr['checks'] ?? []) as $c){
                    if(empty($c['passed'])){
                        $parts[] = $c['message'] ?? $c['name'] ?? 'unknown check';
                    }
                }
                if(!empty($vr['error'])){ $parts[] = $vr['error']; }
                if(!empty($vr['stderr'])){ $parts[] = trim($vr['stderr']); }
                $validationError = !empty($parts) ? implode(' | ', $parts) : 'Unknown error';

                $hint = '';
                if(stripos($validationError, 'preauthentication failed') !== false){
                    $hint = ' — ' . $tpl->javascript_parse_text("{kerberos_preauth_generic_hint}");
                }
                echo $tpl->post_error($tpl->javascript_parse_text(
                    "{warning}: {kerberos_keytab_validate_failed} ($u): " . $validationError
                ) . $hint);
                return false;
            }
        }

        $entryCfg = [
            'type'        => 'kerberos',
            'keytab_path' => $keytabPath,
            'principal'   => $u,
            'realm'       => $realm,
        ];
        if(!empty($kdcHost)){
            $entryCfg['kdc_host'] = $kdcHost;
        }
        $createdPrincipals[] = $entryCfg;
    }

    // Legacy variables kept so downstream code reading them still works.
    $principal  = $createdPrincipals[0]['principal'];
    $keytabPath = $createdPrincipals[0]['keytab_path'];

    // Update the test configuration with Kerberos proxy_auth settings
    if(!empty($testId)){
        $currentTest = $siege->getTest($testId);
        if($currentTest !== null){
            // Build updated config - preserve existing settings.
            // Keep $config['id'] so createTest upserts in place — see
            // edit_test_kerberos_save for the full explanation of
            // why stripping the id made rows disappear from the table.
            $config = $currentTest;
            unset($config['created_at']);

            // Primary principal written into proxy_auth. The daemon falls back
            // to this when kerberos_principals is empty, and some PHP paths
            // still read only proxy_auth — keep it populated.
            $config['proxy_auth'] = array(
                'type' => 'kerberos',
                'keytab_path' => $keytabPath,
                'principal' => $principal,
                'realm' => $realm
            );
            if(!empty($kdcHost)){
                $config['proxy_auth']['kdc_host'] = $kdcHost;
            }

            // Full principal pool. The daemon's PrincipalPool round-robins
            // these across virtual users at test start — one TGT per VU group,
            // not one TGT shared by 1,500 VUs. Always written even when only
            // one principal exists so the daemon takes a consistent code path.
            $config['kerberos_principals'] = $createdPrincipals;

            $newTest = $siege->createTest($config);

            if($newTest === null){
                $tpl->post_error($tpl->javascript_parse_text("{error}: Keytab created but failed to update test: " . $siege->getLastError()));
                return false;
            }

            $testName = isset($config['name']) ? $config['name'] : $testId;
            $count = count($createdPrincipals);
            $userList = implode(', ', array_map(function($p){ return $p['principal']; }, $createdPrincipals));
            return admin_tracks("Created $count Kerberos keytab(s) and updated test '$testName' with principal pool [$userList] @ $realm");

        }
    }

    return admin_tracks("Created " . count($createdPrincipals) . " Kerberos keytab(s) for " . count($createdPrincipals) . " principal(s) @ $realm using ktutil");

}

/**
 * Update/recreate a keytab using ktutil
 * Same as create_keytab but designed for updating existing keytabs
 */
function update_keytab(){
    // For ktutil-based keytabs, update is the same as create
    // (we regenerate the keytab with the new password)
    return create_keytab();
}

/**
 * Get Kerberos status (keytab and ticket cache)
 */
function kerberos_status(){
    header('Content-Type: application/json');

    $keytabPath = isset($_GET['keytab_path']) ? trim($_GET['keytab_path']) : null;

    $siege = new SiegeDaemon();

    if(!$siege->isHealthy()){
        echo json_encode(array('error' => 'Siege daemon not running'));
        return;
    }

    $status = $siege->getKerberosStatus($keytabPath);

    if($status === null){
        echo json_encode(array('error' => $siege->getLastError()));
        return;
    }

    echo json_encode($status);
}

/**
 * Obtain a Kerberos ticket using kinit
 * POST parameters:
 *   - principal: Full principal (user@REALM)
 *   - keytab_path: Path to keytab file (optional)
 *   - lifetime: Ticket lifetime (optional, e.g., "24h")
 *   - renewable: Renewable lifetime (optional, e.g., "7d")
 */
function kinit(){
    $tpl = new template_admin();
    $tpl->CLEAN_POST();

    $principal = isset($_POST['principal']) ? trim($_POST['principal']) : '';
    $keytabPath = isset($_POST['keytab_path']) ? trim($_POST['keytab_path']) : null;
    $lifetime = isset($_POST['lifetime']) ? trim($_POST['lifetime']) : '24h';
    $renewable = isset($_POST['renewable']) ? trim($_POST['renewable']) : '7d';

    if(empty($principal)){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: Principal {required}"));
        return false;
    }

    $siege = new SiegeDaemon();

    if(!$siege->isHealthy()){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: Siege daemon {not_running}"));
        return false;
    }

    $result = $siege->kinit($principal, $keytabPath, $lifetime, $renewable);

    if($result === null){
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: " . $siege->getLastError()));
        return false;
    }

    if(!isset($result['success']) || !$result['success']){
        $error = isset($result['error']) ? $result['error'] : 'Unknown error';
        echo (new template_admin())->post_error("" . $tpl->javascript_parse_text("{error}: kinit {failed}: " . $error));
        return false;
    }

    header('Content-Type: application/json');
    echo json_encode(array(
        'success' => true,
        'message' => isset($result['message']) ? $result['message'] : 'Ticket obtained successfully',
        'principal' => isset($result['principal']) ? $result['principal'] : $principal
    ));

    return true;
}

/**
 * Format a preflight response into a compact one-line error suitable for
 * jserror / post_error display. Lists each required-but-blocked port so the
 * operator can tell their network team exactly what to open, rather than
 * repeating the full preflight JSON.
 *
 * Example output:
 *   "TCP/88 (Kerberos AS/TGS) blocked on SERVAD1.sdis77.fr (192.9.203.63): i/o timeout. Open TCP/88 on the upstream firewall."
 */
function siege_format_preflight_error(array $pf, $tpl): string {
    $host     = htmlspecialchars($pf['host'] ?? '');
    $resolved = htmlspecialchars($pf['resolved'] ?? '');
    $blocked  = [];
    foreach (($pf['checks'] ?? []) as $c) {
        if (!empty($c['required']) && empty($c['open'])) {
            $label = strtoupper($c['proto'] ?? '') . '/' . intval($c['port'] ?? 0);
            $why   = !empty($c['error']) ? ' (' . htmlspecialchars($c['error']) . ')' : '';
            $blocked[] = $label . $why;
        }
    }
    $target = $host . ($resolved !== '' ? " ($resolved)" : '');
    if (!empty($blocked)) {
        return "$target: " . implode(', ', $blocked)
             . ' — ' . $tpl->javascript_parse_text("{kerberos_preflight_open_fw_hint}");
    }
    // Top-level daemon error (DNS failure etc.)
    return $target . ': ' . htmlspecialchars($pf['error'] ?? 'unknown preflight failure');
}

/**
 * Detect common Kerberos startup-error patterns and return an HTML hint block
 * explaining the likely cause and remediation in plain operator language.
 * Returns an empty string when the error does not match a known pattern.
 *
 * Two patterns are handled today:
 *   1. "matching key not found in keytab" (gokrb5 AS_REP decryption failure)
 *      Cause: keytab key does not match AD's current key for the account.
 *      Either the AD password has rotated since the keytab was generated,
 *      or the account has a servicePrincipalName whose salt differs from
 *      ktutil's default.
 *   2. "Preauthentication failed" (MIT kinit AS exchange rejection)
 *      Cause: AD rejected the preauth timestamp — most commonly the SPN
 *      salt pitfall, same as case 1's second branch.
 *
 * Both hints point operators toward the correct remediation path (refresh
 * password, or switch to the msktutil/service-account flow) without forcing
 * them to read gokrb5 stack traces.
 */


function siege_humanize_kerberos_startup_error_hint(string $rawError): string {
    if (stripos($rawError, 'matching key not found in keytab') !== false) {
        $tpl = new template_admin();
        $hintTitle = $tpl->javascript_parse_text("{hint}");
        $hintBody  = $tpl->javascript_parse_text("{kerberos_startup_hint_stale_keytab}");
        return "<div class='alert alert-warning' style='margin-top:8px'>"
             . "<strong>$hintTitle:</strong> $hintBody"
             . "</div>";
    }
    if (stripos($rawError, 'preauthentication failed') !== false) {
        $tpl = new template_admin();
        $hintTitle = $tpl->javascript_parse_text("{hint}");
        $hintBody  = $tpl->javascript_parse_text("{kerberos_startup_hint_preauth_generic}");
        return "<div class='alert alert-warning' style='margin-top:8px'>"
             . "<strong>$hintTitle:</strong> $hintBody"
             . "</div>";
    }
    return "";
}
/**
 * Build a human-readable error string from a daemon JSON response.
 * Concatenates `error`, `stderr`, `stdout`, and `message` fields (whichever
 * are present) so the UI surfaces the full underlying failure — for example
 * the raw kinit stderr line "Preauthentication failed while getting initial
 * credentials" which is critical for diagnosing Kerberos misconfiguration.
 * Falls back to the last error from the SiegeDaemon class when the daemon
 * did not return a usable body.
 */
function siege_format_daemon_error($response, string $fallback):string{
    if(!is_array($response)){
        return $fallback !== '' ? $fallback : 'Unknown error';
    }
    $parts = [];
    foreach(['error', 'message', 'stderr', 'stdout'] as $k){
        if(!empty($response[$k])){
            $v = is_string($response[$k]) ? trim($response[$k]) : json_encode($response[$k]);
            if($v !== ''){ $parts[] = $v; }
        }
    }
    if(empty($parts) && $fallback !== ''){
        $parts[] = $fallback;
    }
    return !empty($parts) ? implode(' | ', $parts) : 'Unknown error';
}

/**
 * Map proxy_exclusions / follow_redirects / max_redirects POST fields into
 * a daemon test-config array. Empty fields are removed so the daemon falls
 * back to its defaults (no exclusions, follow up to 10 redirects).
 *
 *   proxy_exclusions textarea — one entry per line; comments (#) and blank
 *     lines are skipped. Each entry follows NO_PROXY syntax: a hostname
 *     ("intranet.local"), a wildcard suffix ("*.corp.local" or
 *     ".corp.local"), an IP ("10.0.0.1") or a CIDR ("10.0.0.0/8").
 *
 *   follow_redirects "1" or "0" — translates to disable_redirects in the
 *     daemon config (the daemon's flag is named negatively because Go's
 *     zero-value bool defaults to "follow", matching browser behavior).
 *
 *   max_redirects integer — only applied when > 0 and follow_redirects=1.
 *
 * Shared by create_tests_save() and edit_test_save().
 */
function siege_apply_proxy_options_post(array &$config):void{
    // Proxy exclusions
    $rawExcl = $_POST["proxy_exclusions"] ?? '';
    $exclusions = [];
    foreach(preg_split("/\r?\n/", trim($rawExcl)) as $line){
        $line = trim($line);
        if($line === '' || $line[0] === '#'){ continue; }
        $exclusions[] = $line;
    }
    if(!empty($exclusions)){
        $config['proxy_exclusions'] = array_values(array_unique($exclusions));
    } else {
        unset($config['proxy_exclusions']);
    }

    // Redirect handling
    $follow = ($_POST["follow_redirects"] ?? '1') === '1';
    if(!$follow){
        $config['disable_redirects'] = true;
    } else {
        unset($config['disable_redirects']);
    }
    $maxRed = intval($_POST["max_redirects"] ?? 0);
    if($follow && $maxRed > 0 && $maxRed != 10){
        // Only persist when explicitly set to something other than the
        // daemon's default (10). Avoids cluttering stored configs.
        $config['max_redirects'] = $maxRed;
    } else {
        unset($config['max_redirects']);
    }
}

/**
 * Map the v2 "realistic load" POST fields into a daemon test-config array.
 * Empty fields are omitted so the daemon keeps its legacy defaults.
 * Shared by create_tests_save() and edit_test_save().
 */
function siege_apply_realistic_load_post(array &$config):void{
    $dist = trim($_POST["think_time_distribution"] ?? '');
    if($dist !== ''){
        $think = ['distribution' => $dist];
        if($dist === 'lognormal'){
            $mean  = floatval($_POST["think_time_mean_ms"]  ?? 0);
            $sigma = floatval($_POST["think_time_sigma_ms"] ?? 0);
            if($mean  > 0){ $think['mean_ms']  = $mean;  }
            if($sigma > 0){ $think['sigma_ms'] = $sigma; }
        }
        $config['think_time'] = $think;
    }

    $stagger = trim($_POST["vu_startup_stagger"] ?? '');
    if($stagger !== ''){
        $config['vu_startup_stagger'] = $stagger;
    }

    $uaRaw = trim($_POST["user_agents"] ?? '');
    if($uaRaw !== ''){
        $uas = array_values(array_filter(array_map('trim', explode("\n", $uaRaw)), 'strlen'));
        if(!empty($uas)){
            $config['user_agents'] = $uas;
        }
    }
}

