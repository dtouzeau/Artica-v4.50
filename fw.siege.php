<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.siege-daemon.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
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
if(isset($_POST["edit-test-urls"])){edit_test_urls_save();exit;}

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

if(isset($_GET["report-html"])){report_html();exit;}
if(isset($_GET["report-html-js"])){report_html_js();exit;}
if(isset($_GET["report-html-popup"])){report_html_popup();exit;}
if(isset($_GET["report-json"])){report_json();exit;}
if(isset($_GET["import-table"])){import_table();exit;}
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
       return $tpl->js_error($siege->getLastError());
    }
    return admin_tracks("Starting Proxy test stress (Siege) id $testId");
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
    $html[]="<div id='import-access-progress'></div>";
    $html[]="<div id='import-access-next'>";
    $html[]=$tpl->div_explain("{import_access_log_v4_explain}");
    $html[]="<div class=center>".$tpl->button_upload("{upload_file}",$page,null)."</div>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);

}
function import_executed(){
    $tpl=new template_admin();
    $tsource="/usr/share/artica-postfix/ressources/logs/web/access.log.csv";
    $fsize=@filesize($tsource);
    $ico="<i class='fas fa-file-csv'></i>";

    $size=FormatBytes($fsize/1024);

    echo $tpl->_ENGINE_parse_body(
        "<div class=\"widget-head-color-box navy-bg p-lg text-center\">
                                <h1>access.log.csv</h1>
                                <div class=\"m-b-sm\">
                                        <a href=\"ressources/logs/web/access.log.csv\">
                                        <img src=\"img/csv-64.png\" class=\"img-circle circle-border m-b-md\" alt=\"image\"></a>
                                </div>
                                        <p class=\"font-bold\">$size</p>


                            </div>");
}


function import_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{import} access.log","$page?import-popup=yes");
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
        echo "jserror:" . $tpl->javascript_parse_text("{error}: {name} {required}");
        return false;
    }

    if(empty($urlsRaw)){
        echo "jserror:" . $tpl->javascript_parse_text("{error}: URLs {required}");
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
        echo "jserror:" . $tpl->javascript_parse_text("{error}: {invalid} URLs");
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
        echo "jserror:" . $tpl->javascript_parse_text("{error}: " . $siege->getLastError());
        return false;
    }

    $testId = $test['id'] ?? '';
    if(empty($testId)){
        echo "jserror:" . $tpl->javascript_parse_text("{error}: {failed} - No test ID returned");
        return false;
    }

    // Auto-start if requested
    $autoStart = ($_POST["auto_start"] ?? '0') === '1';
    if($autoStart){
        $startResult = $siege->startTest($testId);
        if($startResult === null){
            // Test created but failed to start
            admin_tracks("Created Siege test '$testName' (ID: $testId) but failed to start: " . $siege->getLastError());
            echo "jserror:" . $tpl->javascript_parse_text("{warning}: Test created but failed to start - " . $siege->getLastError());
            return false;
        }
        admin_tracks("Created and started Siege test '$testName' (ID: $testId) with $concurrentUsers users for $duration");
    } else {
        admin_tracks("Created Siege test '$testName' (ID: $testId) with $concurrentUsers users for $duration");
    }

    return true;
}
function import_uploaded_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=$_GET["file-uploaded"];
    $fileencode=urlencode($filename);


    $js=$tpl->framework_buildjs("squid2.php?analyze-access=$fileencode","access.log.parser",
        "access.log.parser.debug","import-access-progress",
        "LoadAjax('import-access-next','$page?import-executed=yes')");

   echo $js;

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
        echo "jserror:" . $tpl->javascript_parse_text("{error}: Test ID {required}");
        return false;
    }

    $siege = new SiegeDaemon();

    // Get test name for logging
    $test = $siege->getTest($testId);
    $testName = $test['name'] ?? $testId;

    // Delete the test
    $result = $siege->deleteTest($testId);

    if($result === null){
        echo "jserror:" . $tpl->javascript_parse_text("{error}: " . $siege->getLastError());
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
    $array[$testName]="$page?edit-test-popup=$testId&function=$function";
    $array["Kerberos"]="$page?create-keytab=$testId&function=$function";
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

    // Extract URLs
    $urls = $test['urls'] ?? [];
    $urlLines = [];
    foreach($urls as $urlConfig){
        if(isset($urlConfig['method']) && $urlConfig['method'] !== 'GET'){
            $line = $urlConfig['method'] . '|' . $urlConfig['url'];
            if(isset($urlConfig['body'])){
                $line .= '|' . $urlConfig['body'];
            }
            $urlLines[] = $line;
        } else {
            $urlLines[] = $urlConfig['url'] ?? '';
        }
    }
    $urlsText = implode("\n", $urlLines);

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
    $js = "dialogInstance1.close();$function();";
    echo $tpl->form_outside("{edit}: $testName", @implode("\n", $form), null, "{save}", $js, "AsProxyMonitor");
    return true;
}

function edit_test_save(){
    $tpl = new template_admin();
    $tpl->CLEAN_POST();

    $testId = $_POST["edit-test"];
    if(empty($testId)){
        echo "jserror:" . $tpl->javascript_parse_text("{error}: Test ID {required}");
        return false;
    }

    $siege = new SiegeDaemon();

    // Get current test to preserve unchanged fields
    $currentTest = $siege->getTest($testId);
    if($currentTest === null){
        echo "jserror:" . $tpl->javascript_parse_text("{error}: " . $siege->getLastError());
        return false;
    }

    // Validate required fields
    $testName = trim($_POST["test_name"] ?? '');
    $urlsRaw = trim($_POST["test_urls"] ?? '');
    $concurrentUsers = intval($_POST["concurrent_users"] ?? 10);
    $duration = trim($_POST["duration"] ?? '60s');

    if(empty($testName)){
        echo "jserror:" . $tpl->javascript_parse_text("{error}: {name} {required}");
        return false;
    }

    // Use default URLs if empty
    if(strlen($urlsRaw) < 5){
        $urlsRaw = @file_get_contents("/usr/share/artica-postfix/bin/install/squid/urls.txt");
    }

    if(empty($urlsRaw)){
        echo "jserror:" . $tpl->javascript_parse_text("{error}: URLs {required}");
        return false;
    }

    // Parse URLs
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
        echo "jserror:" . $tpl->javascript_parse_text("{error}: {invalid} URLs");
        return false;
    }

    // Build updated config
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

    // Realistic-load fields (v2)
    siege_apply_realistic_load_post($config);

    // Proxy settings
    $proxyAddress = trim($_POST["proxy_address"] ?? '');
    $proxyPort = intval($_POST["proxy_port"] ?? 3128);
    if(!empty($proxyAddress)){
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

    // Preserve Kerberos settings if they exist
    if(isset($currentTest['proxy_auth']['type']) && $currentTest['proxy_auth']['type'] === 'kerberos'){
        if(empty($proxyAddress) || empty($_POST["proxy_username"])){
            $config['proxy_auth'] = $currentTest['proxy_auth'];
        }
    }

    // Update test via API (delete and recreate with same ID is not possible, so we delete and create new)
    $deleteResult = $siege->deleteTest($testId);
    if($deleteResult === null){
        echo "jserror:" . $tpl->javascript_parse_text("{error}: " . $siege->getLastError());
        return false;
    }

    $newTest = $siege->createTest($config);
    if($newTest === null){
        echo "jserror:" . $tpl->javascript_parse_text("{error}: " . $siege->getLastError());
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
        echo "jserror:" . $tpl->javascript_parse_text("{error}: Test ID {required}");
        return false;
    }

    $siege = new SiegeDaemon();
    $currentTest = $siege->getTest($testId);
    if($currentTest === null){
        echo "jserror:" . $tpl->javascript_parse_text("{error}: " . $siege->getLastError());
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
        echo "jserror:" . $tpl->javascript_parse_text("{error}: Hostname {and} Realm {required}");
        return false;
    }

    // Enforce FQDN: prevents the classic failure where an operator types
    // their own AD username (e.g. "dtouzeau"), msktutil collides with the
    // existing user account, and kinit fails preauth because AD's stored
    // key uses a salt derived from the pre-existing SPN rather than the
    // name we claimed. Rejecting non-FQDN input makes that whole class of
    // misconfiguration impossible at the form boundary.
    if(strpos($hostname, ".") === false){
        echo "jserror:" . $tpl->javascript_parse_text(
            "{error}: {kerberos_fqdn_required}: $hostname"
        );
        return false;
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
            echo "jserror:" . $tpl->javascript_parse_text("{error}: msktutil {failed}: " . $error);
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
        echo "jserror:" . $tpl->javascript_parse_text("{error}: {validation} {failed}: " . $siege->getLastError());
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
        echo "jserror:" . $tpl->javascript_parse_text("{error}: Kerberos {validation} {failed}: " . $errorMsg);
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
        echo "jserror:" . $tpl->javascript_parse_text("{error}: kinit {failed}: " . $error);
        return false;
    }

    // Step 4: Update test configuration with Kerberos auth
    $config = $currentTest;
    unset($config['id']);
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

    // Delete old and create new
    $siege->deleteTest($testId);
    $newTest = $siege->createTest($config);

    if($newTest === null){
        echo "jserror:" . $tpl->javascript_parse_text("{error}: " . $siege->getLastError());
        return false;
    }

    $testName = $config['name'] ?? $testId;
    admin_tracks("Updated Kerberos settings for siege test '$testName' (keytab created and validated)");
    return true;
}

function edit_test_urls_popup(){
    $testId = $_GET["edit-test-urls-popup"];
    $page = CurrentPageName();
    $tpl = new template_admin();
    $function = $_GET["function"] ?? "LoadAjax('siege-search','$page?search=yes')";

    $siege = new SiegeDaemon();
    $test = $siege->getTest($testId);

    if($test === null){
        echo $tpl->div_error("{error}: " . $siege->getLastError());
        return false;
    }

    $testName = $test['name'] ?? $testId;

    // Extract URLs from test config
    $urls = $test['urls'] ?? [];
    $urlLines = [];
    foreach($urls as $urlConfig){
        if(isset($urlConfig['method']) && $urlConfig['method'] !== 'GET'){
            $line = $urlConfig['method'] . '|' . $urlConfig['url'];
            if(isset($urlConfig['body'])){
                $line .= '|' . $urlConfig['body'];
            }
            $urlLines[] = $line;
        } else {
            $urlLines[] = $urlConfig['url'] ?? '';
        }
    }
    $urlsText = implode("\n", $urlLines);
    $urlCount = count($urls);

    // Build form
    $form[] = $tpl->field_hidden("edit-test-urls", $testId);
    $form[] = $tpl->div_explain("{urls_format_explain}");
    $form[] = $tpl->field_textarea_normal("test_urls", "URLs ($urlCount)", $urlsText, "100%", 15);

    $js = "dialogInstance1.close();$function";
    echo $tpl->form_outside("URLs: $testName", @implode("\n", $form), null, "{save}", $js, "AsProxyMonitor");
    return true;
}

function edit_test_urls_save(){
    $tpl = new template_admin();
    $tpl->CLEAN_POST();

    $testId = $_POST["edit-test-urls"];
    if(empty($testId)){
        echo "jserror:" . $tpl->javascript_parse_text("{error}: Test ID {required}");
        return false;
    }

    $siege = new SiegeDaemon();
    $currentTest = $siege->getTest($testId);
    if($currentTest === null){
        echo "jserror:" . $tpl->javascript_parse_text("{error}: " . $siege->getLastError());
        return false;
    }

    // Get URLs from POST
    $urlsRaw = trim($_POST["test_urls"] ?? '');

    // Use default URLs if empty
    if(strlen($urlsRaw) < 5){
        $urlsRaw = @file_get_contents("/usr/share/artica-postfix/bin/install/squid/urls.txt");
    }

    if(empty($urlsRaw)){
        echo "jserror:" . $tpl->javascript_parse_text("{error}: URLs {required}");
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
        echo "jserror:" . $tpl->javascript_parse_text("{error}: {invalid} URLs");
        return false;
    }

    // Build updated config - only change URLs
    $config = $currentTest;
    unset($config['id']);
    unset($config['created_at']);
    $config['urls'] = $urls;

    // Delete old and create new
    $siege->deleteTest($testId);
    $newTest = $siege->createTest($config);

    if($newTest === null){
        echo "jserror:" . $tpl->javascript_parse_text("{error}: " . $siege->getLastError());
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
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return false;}
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
    $t = time();

    $siege=new SiegeDaemon();
    $function = $_GET["function"] ?? "LoadAjax('siege-search','$page?search=yes')";
    $functionEncoded = urlencode($function);

    if(!$siege->isHealthy()){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{daemon_not_running}: " . $siege->getLastError()));
        return;
    }

    // Get running tests for status indication
    $runningTests = $siege->getRunningTests();
    $html[]="<table id='table-siege-after-search' class=\"footable table table-stripped\" data-page-size=\"50\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize'>{name}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>URLs</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{members}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{duration}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $results=$siege->listTests();
    $AllID[]=0;
    if($results === null){
        $html[]="<tr><td colspan='6'>".$tpl->div_error($siege->getLastError())."</td></tr>";
    } elseif(empty($results)){
        $html[]="<tr><td colspan='6' class='text-center'><em>{no_data}</em></td></tr>";
    } else {
        $TRCLASS=null;

        foreach ($results as $index=>$ligne){
            $md=md5(serialize($ligne));
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

            $testId = $ligne["id"] ?? '';
            $testName = $ligne["name"] ?? $testId;
            $concurrentUsers = intval($ligne["concurrent_users"] ?? 10);
            $duration = $ligne["duration"] ?? '-';
            $urlCount = count($ligne["urls"] ?? []);
            $AllID[]=$testId;

            $status=td_status($testId,$runningTests);
            $delete=$tpl->icon_delete("Loadjs('$page?delete-test-js=$testId&md=$md')");
            $html[]="<tr class='$TRCLASS' id='$md' data-testid='$testId'>";
            $html[]="<td style='width:1%' nowrap>".$tpl->td_href($testName,null,"Loadjs('$page?edit-test-js=$testId&function=$functionEncoded');")."<span id='error-$testId'></span></td>";
            $html[]="<td style='width:1%' nowrap><span class='badge'>$urlCount</span></td>";
            $html[]="<td style='width:1%' nowrap>$concurrentUsers</td>";
            $html[]="<td style='width:1%' nowrap>$duration</td>";
            $html[]="<td style='width:1%' nowrap><span id='report-$testId'></span></td>";
            $html[]="<td style='width:1%' nowrap><span id='pdf-$testId'></span></td>";
            $html[]="<td style='width:10%' nowrap><span id='progress-$testId'></span></td>";
            $html[]="<td style='width:1%' nowrap><span id='status-$testId'>$status</span></td>";
            $html[]="<td style='width:1%' nowrap><span id='delete-$testId'>$delete</span></td>";

            $html[]="</tr>";
        }
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='9'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";

    $jsrestart=$tpl->framework_buildjs(
        "/siege/restart","siege.install.progress",
        "siege.install.progress.logs",
        "progress-siege-restart");

    $version=$siege->getVersionString();

    $topbuttons[] = array($jsrestart, ico_retweet,"{restart}");
    $topbuttons[] = array("Loadjs('$page?create-test-js=yes&function=$function')", ico_plus, "{new_stress_test}");
    $TINY_ARRAY["TITLE"]="{APP_SIEGE} v.$version";
    $TINY_ARRAY["ICO"]="ico_performance";
    $TINY_ARRAY["EXPL"]="{APP_SIEGE_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $ids=@implode("|",$AllID);
    $refreshjs=$tpl->RefreshInterval_Loadjs("table-siege-after-search",$page,"status-ids=$ids");

    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]="$(document).ready(function() { ";
    $html[]="  $('#table-siege-after-search').footable({ ";
    $html[]="    \"filtering\": { \"enabled\": false }, ";
    $html[]="    \"sorting\": { \"enabled\": true }, ";
    $html[]="    \"paging\": { \"size\": 50 } ";
    $html[]="  }); ";
    $html[]=$refreshjs;
    $html[]="});";
    $html[]="$jstiny";




    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
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

function td_status($testId,$runningTests):string{
    $page=CurrentPageName();
    $tpl = new template_admin();

    if(isRunning($testId,$runningTests)){
        $OnMouse[]= "OnClick=\"Loadjs('$page?stop-test-js=$testId');\"";
        $OnMouse[]="OnMouseOver=\";this.style.cursor='pointer';\"";
        $OnMouse[]="OnMouseOut=\";this.style.cursor='default';\"";
        $js=@implode(" ",$OnMouse);
        return $tpl->_ENGINE_parse_body("<span class='label label-success' $js><i class='fas fa-play'></i> {running}</span>");
    } 
        $OnMouse[]= "OnClick=\"Loadjs('$page?start-test-js=$testId');\"";
        $OnMouse[]="OnMouseOver=\";this.style.cursor='pointer';\"";
        $OnMouse[]="OnMouseOut=\";this.style.cursor='default';\"";
        $js=@implode(" ",$OnMouse);
        return $tpl->_ENGINE_parse_body("<span class='label label-default' $js><i class='fas fa-stop'></i> {stopped}</span>");


}
function td_error($testId,$runningTests):string{

    if (isRunning($testId,$runningTests)){
        return "&nbsp;";
    }

    $siege = new SiegeDaemon();

    // Check if it failed to start
    if ($siege->hasTestStartupError($testId)) {
        $error = $siege->getTestStartupErrorMessage($testId);
        $error=str_replace(":",":<br>",$error);
        return "<br><span class='text-danger'>$error</span>";
    }
    return "";


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
        $js = "OnClick=\"javascript:LoadAjax('table-loader','$page?table=yes&eth=');\"";
    $jshelp="s_PopUpFull('https://wiki.articatech.com/en/proxy-service/tuning/stress-your-proxy-server',1024,768,'Stress Tool')";
        $t = time();

    $html[] = "<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
    $html[] = "<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?import-upload-js=yes')\"><i class='fas fa-file-import'></i> {analyze_access_log} </label>";
    $html[]="<label class=\"btn btn btn-info\" OnClick=\"$jshelp\"><i class='fas fa-question-circle'></i> Wiki </label>";
    $html[] = "</div>";
        $html[] = "<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
        $html[] = "<thead>";
        $html[] = "<tr>";
        $html[] = "<th data-sortable=true class='text-capitalize'>{date}</th>";
        $html[] = "<th data-sortable=true class='text-capitalize'>{duration}</th>";
        $html[] = "<th data-sortable=true class='text-capitalize'>{members}</th>";
        $html[] = "<th data-sortable=true class='text-capitalize'>{target}</th>";
        $html[] = "<th data-sortable=true class='text-capitalize' nowrap>{subject}</th>";
        $html[] = "<th data-sortable=true class='text-capitalize'></th>";
        $html[] = "<th data-sortable=true class='text-capitalize'></th>";
        $html[] = "</tr>";
        $html[] = "</thead>";
        $html[] = "<tbody>";


        $sql = "SELECT * FROM reports ORDER BY ID DESC";
        $results = $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error . "<br>$sql");
            return;
        }




        $TRCLASS = null;
       foreach ($results as $index=>$ligne){
           if ($TRCLASS == "footable-odd") {$TRCLASS = null;} else {$TRCLASS = "footable-odd";}
            $md = md5(serialize($ligne));
            $date = strtotime($ligne["zdate"]);
            $date_text = $tpl->time_to_date($date, true);
            $zend=strtotime($ligne["zend"]);
            $users = $ligne["users"];
            $target = $ligne["target"];
            $duration = distanceOfTimeInWords($date,$zend);
           $ID=$ligne["ID"];
            $subject = $tpl->td_href($ligne["subject"],null,"Loadjs('$page?report=$ID')");
            $delete=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$md')","AsProxyMonitor");
            $edit=$tpl->icon_parameters("Loadjs('$page?subject-js=$ID&md=$md')");
            $html[] = "<tr class='$TRCLASS' id='$md'>";
            $html[] = "<td style='width:1%' nowrap>{$date_text}</td>";
            $html[] = "<td style='width:1%' nowrap>{$duration}</td>";
            $html[] = "<td style='width:1%' nowrap>$users</td>";
            $html[] = "<td  style='width:1%' nowrap>$target</td>";
            $html[] = "<td  width=99% nowrap><span id='subject-$md'>$subject</span></td>";
           $html[] = "<td  style='width:1%' nowrap>$edit</td>";
           $html[] = "<td  style='width:1%' nowrap>$delete</td>";
            $html[] = "</tr>";

        }


        $html[] = "</tbody>";
        $html[] = "<tfoot>";

        $html[] = "<tr>";
        $html[] = "<td colspan='7'>";
        $html[] = "<ul class='pagination pull-right'></ul>";
        $html[] = "</td>";
        $html[] = "</tr>";
        $html[] = "</tfoot>";
        $html[] = "</table>";
        $html[] = "<div><small>$sql</small></div>
	<script>
	NoSpinner();\n" . @implode("\n", $tpl->ICON_SCRIPTS) . "
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
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
    $form[] = $tpl->div_info("{create_keytab_user_account_hint}");

    // Build form
    $form[] = $tpl->field_hidden("create-keytab", $testId);
    $form[] = $tpl->field_text(
        "principal",
        "{username}",
        $principal,
        true,
        "{create_keytab_username_tooltip}"
    );
    $form[] = $tpl->field_password("password", "{password}", $password, true,"");
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
    $keytabPath = isset($_POST['keytab_path']) ? trim($_POST['keytab_path']) : "/etc/siege-daemon/$testId.keytab";

    // Accept admin-friendly formats: bare username ("dtouzeau"),
    // user@realm ("dtouzeau@ARTICATECH.INT"), or stray SPN prefix
    // ("HTTP/dtouzeau" — copied from the msktutil popup).
    if(preg_match("#^HTTP:?/+([^@]+)#i", $principal, $re)){
        $principal = $re[1];
    }
    if(strpos($principal, '@') !== false){
        $parts = explode('@', $principal, 2);
        $principal = trim($parts[0]);
        // If the realm field was left blank, borrow the one embedded in the username.
        if(empty($realm) && !empty($parts[1])){
            $realm = strtoupper(trim($parts[1]));
        }
    }

    // Validate required fields
    if(empty($principal)){
        echo "jserror:" . $tpl->javascript_parse_text("{error}: Principal/Username {required}");
        return false;
    }

    if(empty($password)){
        echo "jserror:" . $tpl->javascript_parse_text("{error}: Password {required}");
        return false;
    }

    if(empty($realm)){
        echo "jserror:" . $tpl->javascript_parse_text("{error}: Realm {required}");
        return false;
    }

    $siege = new SiegeDaemon();

    // Check if daemon is healthy
    if(!$siege->isHealthy()){
        echo $tpl->post_error($tpl->javascript_parse_text("{error}: Siege daemon {not_running}"));
        return false;
    }

    // Create keytab using ktutil
    $result = $siege->createKeytabWithKtutil(
        $principal,
        $password,
        $realm,
        $keytabPath
    );

    if($result === null){
        echo $tpl->post_error($tpl->javascript_parse_text("{error}: " . $siege->getLastError()));
        return false;
    }

    if(!isset($result['success']) || !$result['success']){
        $error = siege_format_daemon_error($result, $siege->getLastError());
        echo $tpl->post_error($tpl->javascript_parse_text("{error}: " . $error));
        return false;
    }

    // If KDC host provided, validate the keytab
    if(!empty($kdcHost)){
        $validateResult = $siege->validateKeytab(
            $keytabPath,
            $principal,
            $realm,
            $kdcHost
        );

        if($validateResult === null || !isset($validateResult['valid']) || !$validateResult['valid']){
            // Concatenate every diagnostic field the daemon returns: per-check
            // messages, raw `error`, and `stderr` (which carries the literal
            // kinit failure like "Preauthentication failed"). The previous code
            // dropped the raw error whenever any checks existed, hiding the
            // specific Kerberos cause from the operator.
            $parts = [];
            if($validateResult !== null && !empty($validateResult['checks'])){
                foreach($validateResult['checks'] as $check){
                    if(empty($check['passed'])){
                        $parts[] = $check['message'] ?? $check['name'] ?? 'unknown check';
                    }
                }
            }
            if($validateResult !== null && !empty($validateResult['error'])){
                $parts[] = $validateResult['error'];
            }
            if($validateResult !== null && !empty($validateResult['stderr'])){
                $parts[] = trim($validateResult['stderr']);
            }
            $validationError = !empty($parts) ? implode(' | ', $parts) : 'Unknown error';

            // If the error points at a preauth salt mismatch, show the actionable
            // hint up front so the operator knows this is not a wrong-password
            // problem but an AD-side SPN/salting issue.
            $hint = '';
            if(stripos($validationError, 'preauthentication failed') !== false){
                $hint = ' — ' . $tpl->javascript_parse_text("{kerberos_preauth_salt_hint}");
            }

            echo $tpl->post_error($tpl->javascript_parse_text(
                "{warning}: Keytab created but validation failed: " . $validationError
            ) . $hint);
            return false;
        }
    }

    // Update the test configuration with Kerberos proxy_auth settings
    if(!empty($testId)){
        $currentTest = $siege->getTest($testId);
        if($currentTest !== null){
            // Build updated config - preserve existing settings
            $config = $currentTest;
            unset($config['id']);
            unset($config['created_at']);

            // Set Kerberos proxy authentication
            $config['proxy_auth'] = array(
                'type' => 'kerberos',
                'keytab_path' => $keytabPath,
                'principal' => $principal,
                'realm' => $realm
            );

            // Add KDC host if provided
            if(!empty($kdcHost)){
                $config['proxy_auth']['kdc_host'] = $kdcHost;
            }

            // Delete old and create new test with updated config
            $siege->deleteTest($testId);
            $newTest = $siege->createTest($config);

            if($newTest === null){
                $tpl->post_error($tpl->javascript_parse_text("{error}: Keytab created but failed to update test: " . $siege->getLastError()));
                return false;
            }

            $testName = isset($config['name']) ? $config['name'] : $testId;
            return admin_tracks("Created Kerberos keytab and updated test '$testName' with Kerberos proxy auth for $principal@$realm");

        }
    }

    return admin_tracks("Created Kerberos keytab for $principal@$realm using ktutil");

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
        echo "jserror:" . $tpl->javascript_parse_text("{error}: Principal {required}");
        return false;
    }

    $siege = new SiegeDaemon();

    if(!$siege->isHealthy()){
        echo "jserror:" . $tpl->javascript_parse_text("{error}: Siege daemon {not_running}");
        return false;
    }

    $result = $siege->kinit($principal, $keytabPath, $lifetime, $renewable);

    if($result === null){
        echo "jserror:" . $tpl->javascript_parse_text("{error}: " . $siege->getLastError());
        return false;
    }

    if(!isset($result['success']) || !$result['success']){
        $error = isset($result['error']) ? $result['error'] : 'Unknown error';
        echo "jserror:" . $tpl->javascript_parse_text("{error}: kinit {failed}: " . $error);
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

