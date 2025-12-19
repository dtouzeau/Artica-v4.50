<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/http-audit.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
    $GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["ICAP-start"])){icap_start();exit;}
if(isset($_POST["INTERFACE"])){save();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["icap-step1"])){icap_step1();exit;}
if(isset($_GET["icap-step2"])){icap_step2();exit;}
if(isset($_POST["SimulateICAPURL"])){icap_save();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded_js();exit;}
if(isset($_GET["curl-butts"])){popup_buttons();exit;}
if(isset($_GET["report"])){popup_report();exit;}
js();

function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{request_simulation}"]="$page?popup=yes";
    $array["ICAP"]="$page?ICAP-start=yes";
    echo $tpl->tabs_default($array);
    return true;
}
function icap_start(){
    $page=CurrentPageName();
    echo "<div id='icap-section'></div>\n";
    echo "<script>LoadAjaxSilent('icap-section','$page?icap-step1=yes');</script>";
}

function icap_step1(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sql="SELECT * FROM c_icap_services WHERE enabled=1 ORDER BY zOrder";
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $ligne){
        $text="{$ligne["ipaddr"]}:{$ligne["listenport"]} - {$ligne["service_name"]}";
        $ID=$ligne["ID"];
        $HASH[$ID]=$text;
    }

    if(count($HASH)==0){
        echo $tpl->div_warning("{error}||No ICAP connection set");
        return true;

    }

    $SimulateICAPID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SimulateICAPID"));
    $SimulateICAPURL=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SimulateICAPURL"));
    if(strlen($SimulateICAPURL)< 5){
        $SimulateICAPURL="http://dummyurl.simulated.com";
    }
    $html[]="<div id='icap-section2'>".icap_results()."</div>";
    $form[]=$tpl->field_array_hash($HASH,"SimulateICAPID","nonull:{icap_service}",$SimulateICAPID);
    $form[]=$tpl->field_text("SimulateICAPURL","URL",$SimulateICAPURL);
    $after="LoadAjaxSilent('icap-section2','$page?icap-step2=yes');";
    $html[]=$tpl->form_outside("{simulate} ICAP",$form,"{icap_simulate_1}","{next}",$after);
    echo $tpl->_ENGINE_parse_body($html);
}
function icap_step2(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<center style='margin:30px'>".$tpl->button_upload("{threat_sample}",$page)."</center>";

}
function popup_report():bool{
    $request_simulation_html=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("request_simulation_html"));
    echo $request_simulation_html;
    return true;
}

function icap_results(){
    $tpl=new template_admin();
    if(isset($_GET["clean"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SimulateICAP",serialize(array()));
    }
    $SimulateICAP=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SimulateICAP"));
    if(count($SimulateICAP)<2){return null;}
    $page=CurrentPageName();
    if(isset($SimulateICAP["HEADERS"])){
        $html[]="<table style='width:100%'>";
        foreach ($SimulateICAP["HEADERS"] as $key=>$val){
            $html[]="<tr>";
            $html[]="<td style='width:1%;text-align:right' nowrap>$key:</td>";
            $html[]="<td style='width:100%;text-align:left;padding-left:10px'><strong>$val</strong></td>";
            $html[]="</tr>";
        }
//<i class="fa-solid fa-broom"></i>
        $html[]="<tr><td colspan='2' style='text-align: right'><hr>";
        $html[]=$tpl->button_autnonome("{clean}",
            "LoadAjaxSilent('icap-section','$page?icap-step1=yes&clean=yes');",
            "fa-solid fa-broom",null,178,"btn-danger");

            $html[]="</td> </tr>";

        $html[]="</table>";
    }
    $TITLE=$SimulateICAP["TITLE"];
    if(isset($SimulateICAP["HEADERS"]["x-infection-found"])){
        $SimulateICAP["CONN_STATUS"]=false;
    }

    if(!$SimulateICAP["CONN_STATUS"]){
        if(isset($SimulateICAP["DESCRIPTION"])){
            $html[]="<p><strong>{$SimulateICAP["DESCRIPTION"]}</strong></p>";
        }

        return $tpl->div_error("$TITLE: {results}||".@implode("\n",$html));
    }
    return $tpl->div_explain("$TITLE: {results}||".@implode("\n",$html));
}

function js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog6("{request_simulation}","$page?tabs=yes",890);
}
function icap_save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
}
function popup_buttons():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $request_simulation_html=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("request_simulation_html"));
    if(strlen($request_simulation_html)<5){
        return false;
    }

    $topbuttons[] = array("s_PopUpFull('$page?report=yes','1024','900');;", "fad fa-chart-pie", "{report}");


    echo $tpl->_ENGINE_parse_body( $tpl->table_buttons($topbuttons));
    return true;
}
function popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();



    $jsafter=$tpl->framework_buildjs("/httpclient/simulate","curl.progress",
        "curl.txt","curl-rqs","LoadAjax('curl-butts','$page?curl-butts=yes')");

    // Load configuration using HttpAuditConfig
    $configJson = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("request_simulation_json");
    if (!empty($configJson)) {
        $tmpFile = tempnam(sys_get_temp_dir(), 'http-audit-');
        file_put_contents($tmpFile, $configJson);
        try {
            $auditConfig = HttpAuditConfig::load($tmpFile);
            $configArray = $auditConfig->toArray();
            $CONF["URL"] = $configArray['target']['url'];
            $CONF["USERAGENT"] = $configArray['http']['user_agent'];
            $CONF["ACCEPTLANG"] = $configArray['http']['accept_language'];
            $CONF["ACCEPTENCODING"] = $configArray['http']['accept_encoding'];
            $CONF["CONNECTION"] = $configArray['http']['connection'];
            $CONF["REFERER"] = $configArray['http']['referer'];
            $CONF["XFORWARDEDFOR"] = $configArray['http']['x_forwarded_for'];
            $CONF["INTERFACE"] = $configArray['network']['interface'];
            $CONF["LOCAL_PROXY"] = $configArray['proxy']['enabled'] ? 1 : 0;
            $CONF["REMOTE_PROXY"] = $configArray['proxy']['url'];
            // Proxy authentication settings
            $CONF["PROXY_AUTH_TYPE"] = $configArray['proxy']['auth']['type'] ?? 'none';
            $CONF["PROXY_AUTH_USERNAME"] = $configArray['proxy']['auth']['username'] ?? '';
            $CONF["PROXY_AUTH_PASSWORD"] = $configArray['proxy']['auth']['password'] ?? '';
            // Proxy Kerberos settings
            $CONF["PROXY_KRB_USERNAME"] = $configArray['proxy']['auth']['kerberos']['username'] ?? '';
            $CONF["PROXY_KRB_PASSWORD"] = $configArray['proxy']['auth']['kerberos']['password'] ?? '';
            $CONF["PROXY_KRB_KDC"] = $configArray['proxy']['auth']['kerberos']['kdc_server'] ?? '';
            $CONF["PROXY_KRB_REALM"] = $configArray['proxy']['auth']['kerberos']['realm'] ?? '';
            $CONF["PROXY_KRB_KEYTAB"] = $configArray['proxy']['auth']['kerberos']['keytab_path'] ?? '/tmp/proxy.keytab';
            $CONF["PROXY_KRB_GENERATE_KEYTAB"] = ($configArray['proxy']['auth']['kerberos']['generate_keytab'] ?? false) ? 1 : 0;
        } catch (Exception $e) {
            $CONF = [];
        }
        @unlink($tmpFile);
    } else {
        $CONF = [];
    }

    // Apply defaults using HttpAuditConfig defaults
    if (empty($CONF["URL"])) {
        $CONF["URL"] = "https://www.ibm.com/industries/banking-financial-markets?lnk=hpmps_buin&lnk2=learn";
    }
    if (empty($CONF["USERAGENT"])) {
        $CONF["USERAGENT"] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:80.0) Gecko/20100101 Firefox/80.0";
    }
    if (empty($CONF["ACCEPTLANG"])) {
        $CONF["ACCEPTLANG"] = "fr,fr-FR;q=0.8,en-US;q=0.5,en;q=0.3";
    }
    if (empty($CONF["CONNECTION"])) {
        $CONF["CONNECTION"] = "keep-alive";
    }
    if (empty($CONF["REFERER"])) {
        $CONF["REFERER"] = $CONF["URL"];
    }
    if (empty($CONF["ACCEPTENCODING"])) {
        $CONF["ACCEPTENCODING"] = "gzip, deflate, br";
    }

    $ProxyUrl=parseProxyHostPort($CONF["REMOTE_PROXY"]);

    // Proxy auth defaults
    if (empty($CONF["PROXY_AUTH_TYPE"])) {
        $CONF["PROXY_AUTH_TYPE"] = "none";
    }

    $form[]=$tpl->field_text("URL","{url}",$CONF["URL"],true);
    $form[]=$tpl->field_text("USERAGENT","User-Agent",$CONF["USERAGENT"]);
    $form[]=$tpl->field_text("ACCEPTLANG","Accept-Language",$CONF["ACCEPTLANG"]);
    $form[]=$tpl->field_text("ACCEPTENCODING","Accept-Encoding",$CONF["ACCEPTENCODING"]);
    $form[]=$tpl->field_text("CONNECTION","Connection",$CONF["CONNECTION"]);
    $form[]=$tpl->field_text("REFERER","Referer",$CONF["REFERER"]);
    $form[]=$tpl->field_text("XFORWARDEDFOR","X-Forwarded-For",$_SERVER["REMOTE_ADDR"]);
    $form[]=$tpl->field_interfaces("INTERFACE","{outgoing_interface}",$CONF["INTERFACE"]);
    $form[]=$tpl->field_checkbox("LOCAL_PROXY","{use_proxy}",$CONF["LOCAL_PROXY"],"PROXY_KRB_KDC,PROXY_KRB_REALM,PROXY_AUTH_PASSWORD,PROXY_AUTH_USERNAME,REMOTE_PROXY,PROXY_AUTH_TYPE,REMOTE_PROXY_PORT");
    $form[]=$tpl->field_text("REMOTE_PROXY","{proxy_address}",$ProxyUrl["hostname"]);
    $form[]=$tpl->field_text("REMOTE_PROXY_PORT","{proxy_port}",$ProxyUrl["port"]);

    // Proxy authentication type selector
    $proxyAuthTypes = ["none" => "{none}", "basic" => "Basic", "kerberos" => "Kerberos"];
    $form[]=$tpl->field_array_hash($proxyAuthTypes,"PROXY_AUTH_TYPE","{type}",$CONF["PROXY_AUTH_TYPE"]);

    // Basic auth fields for proxy
    $form[]=$tpl->field_text("PROXY_AUTH_USERNAME","{proxy_username}",$CONF["PROXY_AUTH_USERNAME"] ?? '');
    $form[]=$tpl->field_password("PROXY_AUTH_PASSWORD","{proxy_password}",$CONF["PROXY_AUTH_PASSWORD"] ?? '');

    // Kerberos auth fields for proxy
    //$form[]=$tpl->field_text("PROXY_KRB_USERNAME","{proxy_kerberos_username}",$CONF["PROXY_KRB_USERNAME"] ?? '');
    //$form[]=$tpl->field_password("PROXY_KRB_PASSWORD","{proxy_kerberos_password}",$CONF["PROXY_KRB_PASSWORD"] ?? '');
    $form[]=$tpl->field_text("PROXY_KRB_KDC","{ad_server}",$CONF["PROXY_KRB_KDC"] ?? '');
    $form[]=$tpl->field_text("PROXY_KRB_REALM","{activedirectory_domain}",$CONF["PROXY_KRB_REALM"] ?? '');


    $tpl->field_hidden("PROXY_KRB_GENERATE_KEYTAB",1);
    $tpl->field_hidden("PROXY_KRB_KEYTAB","/tmp/proxy.keytab");
    $html[]="<div id='curl-rqs' style='margin:5px'></div>";
    $html[]="<div id='curl-butts' style='margin:5px'></div>";
    $html[]=$tpl->form_outside("",$form,"","{submit}",$jsafter);
    $html[]="<script>LoadAjax('curl-butts','$page?curl-butts=yes');</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS("URL");
    $_POST["URL"]=url_decode_special_tool($_POST["URL"]);

    // Create HttpAuditConfig from POST data
    $auditConfig = HttpAuditConfig::create($_POST["URL"]);

    // Set HTTP options
    if (!empty($_POST["USERAGENT"])) {
        $auditConfig->setUserAgent($_POST["USERAGENT"]);
    }
    if (!empty($_POST["ACCEPTLANG"])) {
        $configArray = $auditConfig->toArray();
        $configArray['http']['accept_language'] = $_POST["ACCEPTLANG"];
        $auditConfig = HttpAuditConfig::create($_POST["URL"]);
        $auditConfig->setUserAgent($_POST["USERAGENT"] ?? 'http-audit/1.0');
    }
    if (!empty($_POST["REFERER"])) {
        $auditConfig->setReferer($_POST["REFERER"]);
    }
    if (!empty($_POST["XFORWARDEDFOR"])) {
        $auditConfig->setXForwardedFor($_POST["XFORWARDEDFOR"]);
    }

    // Set network interface
    if (!empty($_POST["INTERFACE"])) {
        $auditConfig->setInterface($_POST["INTERFACE"]);
    }


    if (!empty($_POST["LOCAL_PROXY"]) && $_POST["LOCAL_PROXY"] == 1) {
        $proxyUrl = $_POST["REMOTE_PROXY"] ?? '';
        if (!empty($_POST["REMOTE_PROXY_PORT"])) {
            $proxyUrl = "http://".rtrim($proxyUrl, '/') . ':' . $_POST["REMOTE_PROXY_PORT"];
        }
        if (!empty($proxyUrl)) {
            // Set proxy with basic auth if selected
            $proxyAuthType = $_POST["PROXY_AUTH_TYPE"] ?? 'none';
            if ($proxyAuthType === 'basic') {
                $auditConfig->setProxy(
                    $proxyUrl,
                    $_POST["PROXY_AUTH_USERNAME"] ?? '',
                    $_POST["PROXY_AUTH_PASSWORD"] ?? ''
                );
            } else {
                $auditConfig->setProxy($proxyUrl);
            }
        }
    }

    // Build complete config with all HTTP settings
    $configArray = $auditConfig->toArray();
    $configArray['http']['accept_language'] = $_POST["ACCEPTLANG"] ?? 'fr,fr-FR;q=0.8,en-US;q=0.5,en;q=0.3';
    $configArray['http']['accept_encoding'] = $_POST["ACCEPTENCODING"] ?? 'gzip, deflate, br';
    $configArray['http']['connection'] = $_POST["CONNECTION"] ?? 'keep-alive';

    // Set proxy authentication in config array
    if (!empty($_POST["LOCAL_PROXY"]) && $_POST["LOCAL_PROXY"] == 1) {
        $proxyAuthType = $_POST["PROXY_AUTH_TYPE"] ?? 'none';
        $configArray['proxy']['auth']['type'] = $proxyAuthType;

        $_POST["PROXY_KRB_USERNAME"]=$_POST["PROXY_AUTH_USERNAME"];
        $_POST["PROXY_KRB_PASSWORD"]=$_POST["PROXY_AUTH_PASSWORD"];

        if ($proxyAuthType === 'basic') {
            $configArray['proxy']['auth']['username'] = $_POST["PROXY_AUTH_USERNAME"] ?? '';
            $configArray['proxy']['auth']['password'] = $_POST["PROXY_AUTH_PASSWORD"] ?? '';
        } elseif ($proxyAuthType === 'kerberos') {
            if(!isset($_POST["PROXY_KRB_KDC"])){
                echo $tpl->post_error("No active Directory server");
                return false;
            }
            // Kerberos proxy authentication
            $configArray['proxy']['auth']['kerberos'] = [
                'username' => $_POST["PROXY_KRB_USERNAME"] ?? '',
                'password' => $_POST["PROXY_KRB_PASSWORD"] ?? '',
                'kdc_server' => $_POST["PROXY_KRB_KDC"] ?? '',
                'realm' => $_POST["PROXY_KRB_REALM"] ?? '',
                'keytab_path' => $_POST["PROXY_KRB_KEYTAB"] ?? '/tmp/proxy.keytab',
                'generate_keytab' => !empty($_POST["PROXY_KRB_GENERATE_KEYTAB"]) && $_POST["PROXY_KRB_GENERATE_KEYTAB"] == 1,
                'service_name' => 'HTTP',
            ];
        }
    }

    // Save as JSON string
    $configJson = json_encode($configArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("request_simulation_json", $configJson);
    return admin_tracks("Save HTTP client simulator for {$_POST["URL"]}");
}
function file_uploaded_js(){
    $page=CurrentPageName();
    $filename=$_GET["file-uploaded"];
    $fileencode=urlencode($filename);
    $sock=new sockets();
    $after="LoadAjaxSilent('icap-section','$page?icap-step1=yes');";
    $sock->getFrameWork("cicap.php?simulate=$fileencode");
    echo "$after\n";
}


function parseProxyHostPort($proxyUrl="", $defaultPort = 80): array{
    $result = [
        'hostname' => '',
        'port'     => '',
    ];

    $proxyUrl = trim($proxyUrl);
    if ($proxyUrl === '') {
        return $result;
    }

    // If no scheme, force one so parse_url works correctly
    if (!preg_match('#^[a-z][a-z0-9+\-.]*://#i', $proxyUrl)) {
        $proxyUrl = 'http://' . $proxyUrl;
    }

    $parts = parse_url($proxyUrl);
    if ($parts === false) {
        return $result;
    }

    $host = $parts['host'] ?? '';
    $port = $parts['port'] ?? '';

    // Fallback (rare cases)
    if ($host === '' && isset($parts['path'])) {
        $host = $parts['path'];
    }

    if ($host === '') {
        return $result;
    }

    if ($port === '') {
        if ($defaultPort !== null) {
            $port = (string)$defaultPort;
        } else {
            // Best-effort defaults
            $scheme = strtolower($parts['scheme'] ?? 'http');
            $port = match ($scheme) {
                'https' => '443',
                'socks5','socks5h','socks4','socks4a' => '1080',
                default => '3128',
            };
        }
    }

    $result['hostname'] = $host;
    $result['port']     = (string)$port;

    return $result;
}
