<?php
include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"] = new sockets();
}
include_once(dirname(__FILE__) . "/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__) . "/ressources/class.haproxy.inc");
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
if (isset($_POST["create-service"])) {
    balancer_create();
    exit;
}
if (isset($_GET["balancer-js"])) {
    balancer_js();
    exit;
}
if (isset($_GET["balancer-tabs"])) {
    balancer_tabs();
    exit;
}
if (isset($_GET["balancer-parameters"])) {
    balancer_parameters();
    exit;
}
if (isset($_GET["balancer-parameters-final"])) {
    balancer_parameters_final();
    exit;
}

if (isset($_GET["balancer-enable-js"])) {
    balancer_enable();
    exit;
}
if (isset($_GET["balancer-duplicate-js"])) {
    balancer_duplicate();
    exit;
}

if (isset($_GET["balancer-duplicate-dialog"])) {
    balancer_duplicate_dialog();
    exit;
}

if (isset($_GET["balancer-duplicate-dialog"])) {
    balancer_duplicate_dialog();
    exit;
}
if (isset($_POST["origBackend"])) {
    balancer_duplicate_save();
    exit;
}



if (isset($_GET["balancer-delete-js"])) {
    balancer_delete_js();
    exit;
}
if (isset($_POST["servicename"])) {
    balancer_parameters_save();
    exit;
}
if (isset($_POST["balancer-delete"])) {
    balancer_delete();
    exit;
}
if (isset($_GET["balancer-choose"])) {
    balancer_choose();
    exit;
}

page();


function page()
{
    $page = CurrentPageName();
    $tpl = new template_admin();

    $version = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_VERSION");
    $html = "
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{load_balancing} &nbsp;&raquo;&nbsp; {tcp_services}</h1>
	<p>{APP_HAPROXY_TCPSERV}</p>

	</div>

	</div>



	<div class='row'><div id='progress-haproxy-restart'></div>
	<div class='ibox-content'>

	<div id='table-haproxy-services'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/lb-services');		
	LoadAjax('table-haproxy-services','$page?table=yes');

	</script>";

    if (isset($_GET["main-page"])) {
        $tpl = new template_admin(null, $html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl = new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

function balancer_duplicate(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $serviceName=$_GET["balancer-duplicate-js"];
    $tpl->js_dialog1("{duplicate}", "$page?balancer-duplicate-dialog=$serviceName");
    return;
}
function balancer_duplicate_dialog(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $jsafter[] = "LoadAjax('table-haproxy-services','$page?table=yes');";
    $jsafter[] = "dialogInstance1.close();";
    $form[]=$tpl->field_hidden("origBackend",$_GET["balancer-duplicate-dialog"]);
    $form[]=$tpl->field_text("newBackendName","{servicename}","");
    $form[]=$tpl->field_text("newBackendPort","{listen_port}","");
    $html = $tpl->form_outside("{new_service}", @implode("\n", $form), "{haproxy_choose_service}", "{add}", @implode("", $jsafter), "AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);

}

function balancer_duplicate_save()
{
    $hap_orig = new haproxy_multi($_POST["origBackend"]);
    $hap = new haproxy_multi($_POST["newBackendName"]);
    $hap->listen_ip = $hap_orig->listen_ip;
    $hap->listen_port = intval($_POST["newBackendPort"]);
    $hap->loadbalancetype = $hap_orig->loadbalancetype;
    $hap->servicetype = $hap_orig->servicetype;
    $hap->ssl = $hap_orig->ssl;
    $hap->dispatch_mode = $hap_orig->dispatch_mode;
    $hap->tunnel_mode = $hap_orig->tunnel_mode;
    $hap->transparent = $hap_orig->transparent;
    $hap->transparentsrcport = 0;
    $hap->enabled = $hap_orig->enabled;
    $hap->MainConfig=$hap_orig->MainConfig;


    $hap->save();
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $sql="SELECT *  FROM `haproxy_backends` WHERE servicename='{$_POST["origBackend"]}' ORDER BY bweight";
    $results = $q->QUERY_SQL($sql,"artica_backup");
    foreach ($results as $index=>$ligne){
        $hapBacked=new haproxy_backends($_POST["newBackendName"], $ligne["backendname"]);
        $hapBacked->listen_ip=$ligne["listen_ip"];
        $hapBacked->listen_port=intval($_POST["newBackendPort"]);
        $hapBacked->bweight=$ligne["bweight"];
        $hapBacked->localInterface=$ligne["localInterface"];
        $hapBacked->enabled=$ligne["enabled"];
        if (strlen($ligne["MainConfig"]) > 20) {
            $mainConfig = unserialize(base64_decode($ligne["MainConfig"]));
            foreach ($mainConfig as $k => $v) {
                $hapBacked->MainConfig[$k] = $v;
            }
        }

        $hapBacked->save();

    }

}
function balancer_enable()
{

    $hap = new haproxy_multi($_GET["balancer-enable-js"]);
    if ($hap->enabled == 1) {
        $hap->enabled = 0;
    } else {
        $hap->enabled = 1;
    }
    $hap->save();
}

function balancer_delete_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $servicename = $_GET["balancer-delete-js"];
    $tpl->js_confirm_delete($servicename, "balancer-delete", $servicename, "LoadAjax('table-haproxy-services','$page?table=yes');");
}

function balancer_delete()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $hap = new haproxy_multi($_POST["balancer-delete"]);
    $hap->DeleteService();
}

function balancer_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $new_service = $tpl->_ENGINE_parse_body("{new_service}");
    $servicename = urlencode($_GET["balancer-js"]);
    if ($servicename == null) {
        $title = $new_service;
        $tpl->js_dialog1($title, "$page?balancer-choose=yes");
        return;
    }

    $title = $_GET["balancer-js"];
    $tpl->js_dialog1($title, "$page?balancer-tabs=$servicename");
}

function balancer_tabs()
{

    $page = CurrentPageName();
    $tpl = new template_admin();

    $servicename = $_GET["balancer-tabs"];
    $hap = new haproxy_multi($servicename);


    $backendname = $_GET["backendname"];
    $servicenameenc = urlencode($servicename);
    if ($servicename == null) {
        $title = "{new_service}";
    } else {
        $title = $servicename;
    }

    $array["{$title}"] = "$page?balancer-parameters=$servicenameenc";

    $url = "fw.haproxy.backends.php?servicename=$servicenameenc";
    $backends = "{backends}";

    if ($hap->servicetype == 0) {
        $backends = "{proxy_clients}";
    }
    if ($hap->servicetype == 2) {
        $backends = "{web_servers}";
        $url = "fw.haproxy.webservers.php?servicename=$servicenameenc";
    }

    if ($servicename <> null) {
        $array[$backends] = $url;
    }

    echo $tpl->tabs_default($array);

}

function balancer_choose()
{
    $users = new usersMenus();
    $page = CurrentPageName();
    $tpl = new template_admin();
    $hap = new haproxy_multi();
    $tcp = new networking();

    $ips[null] = "{all}";

    $nic = new networking();
    $nicZ = $nic->Local_interfaces();
    foreach ($nicZ as $yinter => $line) {
        if ($yinter == "lo") {
            continue;
        }
        $znic = new system_nic($yinter);
        if (preg_match("#^dummy#", $yinter)) {
            continue;
        }
        if (preg_match("#-ifb$#", $yinter)) {
            continue;
        }
        if ($znic->Bridged == 1) {
            continue;
        }
        if ($znic->enabled == 0) {
            continue;
        }
        $ips[$znic->IPADDR] = "$znic->NICNAME ($yinter/$znic->IPADDR)";
    }

    $hap->listen_port = "8080";
    $form[] = $tpl->field_hidden("create-service", "yes");
    $form[] = $tpl->field_text("servicename", "{servicename}", null, true);
    $form[] = $tpl->field_array_hash($hap->servicetype_array, "servicetype", "{servicetype}", 0, true);
    $form[] = $tpl->field_array_hash($ips, "listen_ip", "{listen_ip}", $hap->listen_ip);
    $form[] = $tpl->field_numeric("listen_port", "{listen_port}", $hap->listen_port);
    $jsafter[] = "LoadAjax('table-haproxy-services','$page?table=yes');";
    $jsafter[] = "dialogInstance1.close();";

    $html = $tpl->form_outside("{new_service}", @implode("\n", $form), "{haproxy_choose_service}", "{add}", @implode("", $jsafter), "AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);


}

function balancer_create()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    if ($_POST["listen_ip"] == null) {
        $_POST["listen_ip"] = "*";
    }
    $hap = new haproxy_multi($_POST["servicename"]);
    $hap->listen_ip = $_POST["listen_ip"];
    $hap->listen_port = $_POST["listen_port"];
    $hap->loadbalancetype = $_POST["mode"];
    $hap->servicetype = $_POST["servicetype"];
    $servicetype_array = array(
        0 => "HTTP/HTTPs Proxy Load-balancer",
        1 => "HTTP Load-balancer",
        2 => "Reverse Proxy",
        3 => "TCP redirect",
        4 => "SMTP redirect"
    );

    if ($_POST["servicetype"] == 0) {
        $hap->loadbalancetype = 2;
        $hap->MainConfig["asSquidArtica"] = 1;
    }
    if ($_POST["servicetype"] == 1) {
        $hap->loadbalancetype = 1;
    }
    if ($_POST["servicetype"] == 2) {
        $hap->loadbalancetype = 1;
    }
    if ($_POST["servicetype"] == 3) {
        $hap->loadbalancetype = 0;
    }
    if ($_POST["servicetype"] == 4) {
        $users = new usersMenus();
        $hap->loadbalancetype = 0;
        $hap->MainConfig["UseSMTPProto"] = 1;
        $hap->MainConfig["smtpchk_EHLO"] = $users->hostname;
    }
    $hap->save();
}

function balancer_parameters()
{
    $page = CurrentPageName();
    $servicename = $_GET["balancer-parameters"];
    $servicename = urlencode($servicename);
    $md5 = md5(time() . $servicename);
    echo "<div id='balancer_parameters_$md5'></div>
    <script>
        function HaProxyBalancerParametersMain(){
            LoadAjax('balancer_parameters_$md5','$page?balancer-parameters-final=$servicename')
            
        }
        HaProxyBalancerParametersMain();
    </script>
    ";
}


function balancer_parameters_final()
{
    $tt = $_GET["t"];
    $users = new usersMenus();
    $page = CurrentPageName();
    $tpl = new template_admin();
    $servicename = $_GET["balancer-parameters-final"];
    $havp_transparent_not_same_port = $tpl->javascript_parse_text("{havp_transparent_not_same_port}");
    $hap = new haproxy_multi($servicename);
    $tcp = new networking();
    $ips = $tcp->ALL_IPS_GET_ARRAY();
    $ips["*"] = "{all}";
    $nic = new networking();
    $nicZ = $nic->Local_interfaces();
    foreach ($nicZ as $yinter => $line) {
        if ($yinter == "lo") {
            continue;
        }
        $znic = new system_nic($yinter);
        if (preg_match("#^dummy#", $yinter)) {
            continue;
        }
        if (preg_match("#-ifb$#", $yinter)) {
            continue;
        }
        if ($znic->Bridged == 1) {
            continue;
        }
        if ($znic->enabled == 0) {
            continue;
        }
        $ips[$znic->IPADDR] = "$znic->NICNAME ($yinter/$znic->IPADDR)";
    }
    $buttonname = "{apply}";
    if ($servicename == null) {
        $buttonname = "{add}";
    }
    $mode = array(0 => "TCP", 1 => "HTTP Web", 2 => "HTTP Proxy");
    if (!isset($hap->MainConfig["smtpchk_EHLO"])) {
        $hap->MainConfig["smtpchk_EHLO"] = $users->hostname;
    }
    if (!is_numeric($hap->MainConfig["contimeout"])) {
        $hap->MainConfig["contimeout"] = 4000;
    }
    if (!is_numeric($hap->MainConfig["srvtimeout"])) {
        $hap->MainConfig["srvtimeout"] = 50000;
    }
    if (!is_numeric($hap->MainConfig["clitimeout"])) {
        $hap->MainConfig["clitimeout"] = 15000;
    }
    if (!is_numeric($hap->MainConfig["retries"])) {
        $hap->MainConfig["retries"] = 3;
    }
    if (!is_numeric($hap->MainConfig["UseCookies"])) {
        $hap->MainConfig["UseCookies"] = 0;
    }
    if (!is_numeric($hap->MainConfig["NTLM_COMPATIBILITY"])) {
        $hap->MainConfig["NTLM_COMPATIBILITY"] = 0;
    }
    if (!is_numeric($hap->MainConfig["asSquidArtica"])) {
        $hap->MainConfig["asSquidArtica"] = 0;
    }
    if (intval($hap->MainConfig["HttpKeepAliveTimeout"]) == 0) {
        $hap->MainConfig["HttpKeepAliveTimeout"] = 15000;
    }
    if (intval($hap->MainConfig["TimeoutTunnel"]) == 0) {
        $hap->MainConfig["TimeoutTunnel"] = 30000;
    }
    if (intval($hap->MainConfig["HttpRequestTimeout"]) == 0) {
        $hap->MainConfig["HttpRequestTimeout"] = 50000;
    }
    if (intval($hap->MainConfig["HttpQueueTimeout"]) == 0) {
        $hap->MainConfig["HttpQueueTimeout"] = 50000;
    }


    $jsafter[] = "LoadAjax('table-haproxy-services','$page?table=yes');";
    if ($servicename <> null) {
        $form[] = $tpl->field_info("servicename", "{servicename}", $servicename);
        $title = $servicename;

    } else {
        $form[] = $tpl->field_text("servicename", "{servicename}", null);
        $title = "{new_service}";
        $jsafter[] = "dialogInstance1.close();";
    }

    if ($hap->servicetype == 0) {
        $title = $title . "&nbsp;({HTTP_PROXY_MODE})";
    }
    if ($hap->servicetype == 2) {
        $title = $title . "&nbsp;({reverse_proxy})";
    }

    if ($hap->listen_port < 2) {
        $hap->listen_port = 8080;
    }

    $form[] = $tpl->field_array_hash($ips, "listen_ip", "{listen_ip}", $hap->listen_ip);
    $form[] = $tpl->field_numeric("listen_port", "{listen_port}", $hap->listen_port);


    if ($hap->servicetype == 0) {
        $form[] = $tpl->field_checkbox("NTLM_COMPATIBILITY", "{NTLM_COMPATIBLE}", $hap->MainConfig["NTLM_COMPATIBILITY"], false, "{HAP_NTLM_COMPATIBLE}");

        $form[] = $tpl->field_checkbox("KERBEROS_COMPATIBILITY", "{kerberos_authentication_support}", $hap->MainConfig["KERBEROS_COMPATIBILITY"], false, "{kerberos_authentication_support}");


    }

    if ($hap->servicetype == 0 or $hap->servicetype == 1) {

        $certificates_count = count($hap->certificates);
        $serviceid = urlencode($servicename);

        $certificates_text = "{certificates}";
        if ($certificates_count < 2) {
            $certificates_text = "{certificate}";
        }

        $form[] = $tpl->field_section("{ssl_protocol}");
        $form[] = $tpl->field_checkbox("ssl", "{enable_ssl}", $hap->ssl);
        $form[] = $tpl->field_info("replace_rules", "{certificates}",

            array("VALUE" => null,
                "BUTTON" => true,
                "BUTTON_CAPTION" => "$certificates_count $certificates_text",
                "BUTTON_JS" => "Loadjs('fw.haproxy.certificates.php?service-js=$serviceid')"
            ), "{certificates}");

        $form[] = $tpl->field_section("{header_checks}");
        $form[] = $tpl->field_checkbox("forwardfor", "X-Forwarded-For", intval($hap->MainConfig["forwardfor"]));

        $form[] = $tpl->field_section("{caching}");
        $form[] = $tpl->field_checkbox("cache-use", "{enable_caching_squid}", intval($hap->MainConfig["cache-use"]));


    }

    $form[] = $tpl->field_section("{protocol}");
    $form[] = $tpl->field_checkbox("http-keep-alive", "HTTP-keep-alive", intval($hap->MainConfig["http-keep-alive"]));


    $form[] = $tpl->field_section("{method}");
    //$form[]=$tpl->field_array_hash($mode, "mode", "{method}", $hap->loadbalancetype);
    $form[] = $tpl->field_array_hash($hap->algo, "dispatch_mode", "{dispatch_method2}", $hap->dispatch_mode);
    $form[] = $tpl->field_checkbox("tunnel_mode", "{tunnel_mode}", $hap->tunnel_mode);
    $form[] = $tpl->field_checkbox("UseCookies", "{UseCookies}", $hap->MainConfig["UseCookies"]);
    $form[] = $tpl->field_checkbox("http-use-proxy-header", "HTTP Proxy Header", $hap->MainConfig["http-use-proxy-header"]);
    $form[] = $tpl->field_checkbox("accept-invalid-http-request", "Accept Invalid HTTP Request", $hap->MainConfig["accept-invalid-http-request"]);

    $form[] = $tpl->field_array_hash($hap->http_reuse_array, "http_reuse", "HTTP Reuse", $hap->MainConfig["http_reuse"]);
    if ($hap->servicetype == 4) {
        $form[] = $tpl->field_section("{SMTP_MODE}");
        $form[] = $tpl->field_checkbox("UseSMTPProto", "{UseSMTPProto}", $hap->MainConfig["UseSMTPProto"]);
        $form[] = $tpl->field_text("smtpchk_EHLO", "{smtpchk_EHLO}", $hap->MainConfig["smtpchk_EHLO"]);
    }
    $form[] = $tpl->field_section("{timeouts}");
    $form[] = $tpl->field_numeric("HttpRequestTimeout", "{HttpRequestTimeout} ({milliseconds})", $hap->MainConfig["HttpRequestTimeout"]);
    $form[] = $tpl->field_numeric("TimeoutTunnel", "{TimeoutTunnel} ({milliseconds})", $hap->MainConfig["TimeoutTunnel"]);
    $form[] = $tpl->field_numeric("contimeout", "{contimeout} ({milliseconds})", $hap->MainConfig["contimeout"]);
    $form[] = $tpl->field_numeric("srvtimeout", "{srvtimeout} ({milliseconds})", $hap->MainConfig["srvtimeout"]);
    $form[] = $tpl->field_numeric("clitimeout", "{clitimeout} ({milliseconds})", $hap->MainConfig["clitimeout"]);
    $form[] = $tpl->field_numeric("HttpKeepAliveTimeout", "{HttpKeepAliveTimeout} ({milliseconds})", $hap->MainConfig["HttpKeepAliveTimeout"]);
    $form[] = $tpl->field_numeric("HttpQueueTimeout", "{HttpQueueTimeout} ({milliseconds})", $hap->MainConfig["HttpQueueTimeout"]);


    $form[] = $tpl->field_numeric("retries", "{maxretries}", $hap->MainConfig["retries"]);


    $html = $tpl->form_outside($title, @implode("\n", $form), null, $buttonname, @implode("", $jsafter), "AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);

}

function balancer_parameters_save()
{

    $tpl = new template_admin();
    $tpl->CLEAN_POST();

    $SQUIDEnable = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
    if ($SQUIDEnable == 1) {
        $q = new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $ligne = $q->mysqli_fetch_array("SELECT PortName,ID FROM proxy_ports WHERE port='{$_POST["listen_port"]}'");
        $ID = $ligne["ID"];
        $ID = intval($ID);

        if ($ID > 0) {
            $PortName = $ligne["PortName"];
            echo "Unable to listen {$_POST["listen_port"]}, it used by the HTTP Proxy service $PortName ID $ID\n";
            return;
        }
    }

    if ($_POST["KERBEROS_COMPATIBILITY"] == 1) {
        $_POST["NTLM_COMPATIBILITY"] = 0;
    }


    $hap = new haproxy_multi($_POST["servicename"]);
    $hap->listen_ip = $_POST["listen_ip"];
    $hap->listen_port = $_POST["listen_port"];
    $hap->loadbalancetype = $_POST["mode"];
    $hap->ssl = intval($_POST["ssl"]);
    $hap->dispatch_mode = $_POST["dispatch_mode"];
    if (isset($_POST["forwardfor"])) {
        $hap->MainConfig["forwardfor"] = intval($_POST["forwardfor"]);
    }
    $hap->MainConfig["http-keep-alive"] = $_POST["http-keep-alive"];
    $hap->MainConfig["smtpchk_EHLO"] = $_POST["smtpchk_EHLO"];
    $hap->MainConfig["UseSMTPProto"] = $_POST["UseSMTPProto"];
    $hap->MainConfig["contimeout"] = $_POST["contimeout"];
    $hap->MainConfig["srvtimeout"] = $_POST["srvtimeout"];
    $hap->MainConfig["clitimeout"] = $_POST["clitimeout"];
    $hap->MainConfig["HttpRequestTimeout"] = $_POST["HttpRequestTimeout"];
    $hap->MainConfig["HttpQueueTimeout"] = $_POST["HttpQueueTimeout"];
    $hap->MainConfig["retries"] = $_POST["retries"];
    $hap->MainConfig["UseCookies"] = $_POST["UseCookies"];
    $hap->MainConfig["TimeoutTunnel"] = $_POST["TimeoutTunnel"];
    $hap->MainConfig["HttpKeepAliveTimeout"] = $_POST["HttpKeepAliveTimeout"];
    $hap->MainConfig["NTLM_COMPATIBILITY"] = $_POST["NTLM_COMPATIBILITY"];
    $hap->MainConfig["asSquidArtica"] = $_POST["asSquidArtica"];
    $hap->MainConfig["KERBEROS_COMPATIBILITY"] = $_POST["KERBEROS_COMPATIBILITY"];
    if (isset($_POST["cache-use"])) {
        $hap->MainConfig["cache-use"] = $_POST["cache-use"];
    }
    $hap->tunnel_mode=$_POST["tunnel_mode"];
    $hap->transparent=$_POST["transparent"];
    $hap->transparentsrcport=0;
    $hap->MainConfig["http_reuse"]=$_POST["http_reuse"];
    $hap->MainConfig["http-use-proxy-header"]=intval($_POST["http-use-proxy-header"]);
    $hap->MainConfig["accept-invalid-http-request"]=intval($_POST["accept-invalid-http-request"]);

    $hap->save();

}

function table()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $q = new lib_sqlite("/home/artica/SQLITE/haproxy.db");


    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/recusor.restart.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/recusor.restart.log";
    $ARRAY["CMD"] = "pdns.php?restart-recusor=yes";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_PDNS_RECURSOR}";

    $prgress = base64_encode(serialize($ARRAY));
    $jsrestart = "Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";

    $html[] = "<div class=\"btn-group\" data-toggle=\"buttons\">";
    $html[] = "<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?balancer-js=');\">";
    $html[] = "<i class='fa fa-plus'></i> {new_service} </label>";
    $html[] = "</div>";
    $html[] = "<table id='table-haproxy-balancers' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[] = "<thead>";
    $html[] = "<tr>";
    $html[] = "<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{address}</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' data-type='text'>{servicename}</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' data-type='text'>{method}</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{backends}</th>";
    $html[] = "<th data-sortable=false class='text-capitalize' data-type='text'>{active2}</th>";
    $html[] = "<th data-sortable=false class='text-capitalize' data-type='text'>{duplicate}</th>";
    $html[] = "<th data-sortable=false class='text-capitalize' data-type='text'>Del</th>";
    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";

    $sql = "SELECT * FROM `haproxy`";
    $results = $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo $q->mysql_error_html();
    }

    $TRCLASS = null;
    $ligne = null;

    $servicetype_array = array(
        0 => "HTTP/HTTPs Proxy Load-balancer",
        1 => "HTTP Load-balancer",
        2 => "Reverse Proxy",
        3 => "TCP redirect",
        4 => "SMTP redirect"
    );

    $mode = array(0 => "TCP", 1 => "HTTP Web", 2 => "HTTP Proxy");
    foreach ($results as $index => $ligne) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }

        $md = md5(serialize($ligne));
        $delete = imgsimple("delete-32.png", null, "BalancerDeleteService('{$ligne['servicename']}','$md5')");
        if ($ligne["enabled"] == 0) {
            $color = "#8a8a8a";
        }
        $listen_ip = $ligne["listen_ip"];
        if ($listen_ip == null) {
            $listen_ip = "0.0.0.0";
        }
        $listen_port = $ligne["listen_port"];
        $interface = "$listen_ip:$listen_port";
        $servicenameenc = urlencode($ligne["servicename"]);
        $servicetype = intval($ligne["servicetype"]);

        $sql = "SELECT COUNT(*) as Tcount from haproxy_backends WHERE servicename='{$ligne['servicename']}'";
        $ligne2 = $q->mysqli_fetch_array($sql);
        if (!$q->ok) {
            $Tcount = $q->mysql_error;
        } else {
            $Tcount = $ligne2["Tcount"];
        }


        $method = $servicetype_array[$servicetype] . "/" . $mode[$ligne["loadbalancetype"]];
        $disable = $tpl->icon_check($ligne["enabled"], "Loadjs('$page?balancer-enable-js=$servicenameenc')", null, "AsSquidAdministrator");
        $duplicate = $tpl->icon_copy("Loadjs('$page?balancer-duplicate-js=$servicenameenc')", null, "AsSquidAdministrator");

        $delete = $tpl->icon_delete("Loadjs('$page?balancer-delete-js=$servicenameenc')", "AsSquidAdministrator");

        $interface = $tpl->td_href($interface, "{$ligne["servicename"]}", "Loadjs('$page?balancer-js=$servicenameenc')");
        $servicename = $tpl->td_href($ligne["servicename"], "$listen_ip:$listen_port", "Loadjs('$page?balancer-js=$servicenameenc')");
        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = "<td width=1% nowrap>$interface</td>";
        $html[] = "<td><strong>$servicename</strong></td>";
        $html[] = "<td style='width:1%' nowrap>$method</a></td>";
        $html[] = "<td style='width:1%' nowrap>$Tcount</a></td>";
        $html[] = "<td style='width:1%' nowrap>$disable</td>";
        $html[] = "<td style='width:1%' nowrap>$duplicate</td>";
        $html[] = "<td style='width:1%' nowrap>$delete</td>";
        $html[] = "</tr>";


    }
    $html[] = "</tbody>";
    $html[] = "<tfoot>";
    $html[] = "<tr>";
    $html[] = "<td colspan='6'>";
    $html[] = "<ul class='pagination pull-right'></ul>";
    $html[] = "</td>";
    $html[] = "</tr>";
    $html[] = "</tfoot>";
    $html[] = "</table>";
    $html[] = "
	<script>
	NoSpinner();\n" . @implode("\n", $tpl->ICON_SCRIPTS) . "
	$(document).ready(function() { $('#table-haproxy-balancers').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}