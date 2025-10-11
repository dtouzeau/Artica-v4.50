<?php
include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"] = new sockets();
}
include_once(dirname(__FILE__) . "/ressources/class.keepalived.inc");
$users = new usersMenus();
if (!$users->AsVPNManager) {
    exit();
}
$secondary_nodeIsenable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE"));

if ($secondary_nodeIsenable == 1) {
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
if (isset($_GET["main"])) {
    main();
    exit;
}
if (isset($_GET["keepalived-status"])) {
    keepalived_status();
    exit;
}
if (isset($_POST["keepalived-global-def"])) {
    keepalived_save_global_def();
    exit;
}
if (isset($_GET["build-progress"])) {
    build_progress();
    exit;
}
page();
function build_progress()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $sock = new sockets();
    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "keepalived.php?reconfigure=yes";
    $ARRAY["TITLE"] = "{APP_KEEPALIVED} {restarting_service}";
    //$ARRAY["AFTER"]="LoadAjaxSilent('keepalived-status','$page?keepalived-status=yes');";
    $prgress = base64_encode(serialize($ARRAY));
    $jsrestart = "Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";

    echo $jsrestart;
}

function keepalived_status()
{
    $page = CurrentPageName();


    $sock = new sockets();
    $sock->getFrameWork("keepalived.php?status=yes");
    $bsini = new Bs_IniHandler("/usr/share/artica-postfix/ressources/logs/web/keepalived.status");
    $tpl = new template_admin();


    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "keepalived.php?restart=yes";
    $ARRAY["TITLE"] = "{APP_KEEPALIVED} {restarting_service}";
    $ARRAY["AFTER"] = "LoadAjaxSilent('keepalived-status','$page?keepalived-status=yes');";
    $prgress = base64_encode(serialize($ARRAY));
    $jsRestart = "Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";

    $final[] = $tpl->SERVICE_STATUS($bsini, "APP_KEEPALIVED", $jsRestart);

    VERBOSE(count($final) . " row", __LINE__);
    echo $tpl->_ENGINE_parse_body($final);

}

function page()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $KEEPALIVED_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_VERSION");
    $about = $tpl->_ENGINE_parse_body("{keepalived_what_is}");
    $about = str_replace("#Keepalived#", "<a href=\"https://www.keepalived.org\" target='_NEW' style='text-decoration:underline;font-weight:bold'>Keepalived</a>", $about);
    $btn=$tpl->button_inline("{online_help}","s_PopUp('https://wiki.articatech.com/en/artica-failover-service','1024','800')","fa-solid fa-headset",null,null,"btn-blue");


    $html = "
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_KEEPALIVED} v$KEEPALIVED_VERSION</h1><p>$about</p>$btn</div>
	
	</div>



	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>
	<script>
	$.address.state('/');
	$.address.value('/failover-parameters');	
	
	LoadAjax('table-loader','$page?main=yes');

	</script>";

    $tpl = new templates();
    if (isset($_GET["main-page"])) {
        $tpl = new template_admin('Artica: Failover Parameters', $html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);

}

function main()
{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $keepalive_global_settings = new keepalived_global_settings();

    if ($keepalive_global_settings->log_retation_time == 0) {
        $keepalive_global_settings->log_retation_time = 15;
    }
    if ($keepalive_global_settings->smtp_connect_timeout == 0) {
        $keepalive_global_settings->smtp_connect_timeout = 30;
    }
    $form[] = $tpl->field_hidden("keepalived-global-def", '1');
    //$form[]=$tpl->field_section("{LOG_STRONGSWAN}","{LOG_STRONGSWAN_EXPLAIN}");
    $form[] = $tpl->field_checkbox("keepalived_log_detail", "{debug_mode}", $keepalive_global_settings->log_detail, false, null);

//    $form[]=$tpl->field_checkbox("APP_KEEPALIVED_ENABLE_smtp","{enable} SMTP {notifications}",$keepalive_global_settings->enable_smtp,"keepalived_smtp_server,keepalived_smtp_connect_timeout,keepalived_notification_email,keepalived_notification_email_from",null);
//    $form[] = $tpl->field_text("keepalived_smtp_server", "SMTP {server}", $keepalive_global_settings->smtp_server);
//    $form[] = $tpl->field_numeric("keepalived_smtp_connect_timeout", "SMTP {timeout}", $keepalive_global_settings->smtp_connect_timeout);
//    $form[] = $tpl->field_textarea("keepalived_notification_email", "{to}", $keepalive_global_settings->notification_email);
//    $form[] = $tpl->field_text("keepalived_notification_email_from", "{from}", $keepalive_global_settings->notification_email_from);
//
//
//    $form[]=$tpl->field_section("{LOG_STRONGSWAN}","{LOG_STRONGSWAN_EXPLAIN}");
//
//    $form[]=$tpl->field_numeric("keepalived_log_retation_time","{retention_time} (Days)",$keepalive_global_settings->log_retation_time,"{retention_time_text}");
//    $form[] = $tpl->field_checkbox("keepalived_send_logs_by_syslog", "{send_syslog_logs}", $keepalive_global_settings->send_logs_by_syslog, "keepalived_do_not_store_log_locally,keepalived_remote_syslog_server,keepalived_remote_syslog_syslog_port,keepalived_syslog_tcp_ip,keepalived_syslog_ssl,keepalived_sylog_certificate");
//
//    $form[] = $tpl->field_checkbox("keepalived_do_not_store_log_locally", "{not_store_log_locally}", $keepalive_global_settings->keepalived_do_not_store_log_locally);
//    $form[] = $tpl->field_ipv4("keepalived_remote_syslog_server", "{remote_syslog_server}", $keepalive_global_settings->remote_syslog_server);
//    $form[] = $tpl->field_numeric("keepalived_remote_syslog_syslog_port", "{listen_port}", $keepalive_global_settings->remote_syslog_syslog_port);
//    $form[] = $tpl->field_checkbox("keepalived_syslog_tcp_ip", "{enable_tcpsockets}", $keepalive_global_settings->syslog_tcp_ip);
//    $form[] = $tpl->field_checkbox("keepalived_syslog_ssl", "{useSSL}", $keepalive_global_settings->syslog_ssl);
//    $form[] = $tpl->field_certificate("keepalived_sylog_certificate", "{certificate}", $keepalive_global_settings->sylog_certificate);


    $tpl->FORM_IN_ARRAY = false;
    $jsAfter2 = "Loadjs('$page?build-progress=yes')";
    //$myform=$tpl->form_outside("{general_settings}", @implode("\n", $form),"{keepalived_whatis}","{save}",$jsAfter2);
    $myform = $tpl->form_outside("{LOG_STRONGSWAN}", @implode("\n", $form), "{LOG_STRONGSWAN_EXPLAIN}", "{save}", $jsAfter2);
    $html[] = "<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:240px'><div id='keepalived-status' style='margin-top:15px'></div></td>
		<td	style='vertical-align:top;width:90%'>$myform</td>
	</tr>
	</table>
	<script>LoadAjaxSilent('keepalived-status','$page?keepalived-status=yes');</script>	
	";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function keepalived_save_global_def()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
//    if($_POST["APP_KEEPALIVED_ENABLE_smtp"]==1){
//        if(empty($_POST["keepalived_smtp_server"])){
//            echo "jserror:Empty SMTP Server";
//            return false;
//        }
//        if(empty($_POST["keepalived_smtp_connect_timeout"])){
//            $_POST["keepalived_smtp_connect_timeout"]=30;
//        }
//        if(empty($_POST["keepalived_notification_email"])){
//            echo "jserror:Empty Recipients (to)";
//            return false;
//        }
//        if(empty($_POST["keepalived_notification_email_from"])){
//            echo "jserror:Empty Sender (from)";
//            return false;
//        }
//    }
    $keepalived_global_settings = new keepalived_global_settings();
    $keepalived_global_settings->log_detail = intval($_POST["keepalived_log_detail"]);
//    $keepalived_global_settings->enable_smtp=intval($_POST["APP_KEEPALIVED_ENABLE_smtp"]);
//    $keepalived_global_settings->smtp_server=$_POST["keepalived_smtp_server"];
//    $keepalived_global_settings->smtp_connect_timeout=intval($_POST["keepalived_smtp_connect_timeout"]);
//    $keepalived_global_settings->notification_email=$_POST["keepalived_notification_email"];
//    $keepalived_global_settings->notification_email_from=$_POST["keepalived_notification_email_from"];
//    $keepalived_global_settings->log_retation_time=intval($_POST["keepalived_log_retation_time"]);
//    $keepalived_global_settings->send_logs_by_syslog=intval($_POST["keepalived_send_logs_by_syslog"]);
//    $keepalived_global_settings->do_not_store_log_locally=intval($_POST["keepalived_do_not_store_log_locally"]);
//    $keepalived_global_settings->remote_syslog_server=$_POST["keepalived_remote_syslog_server"];
//    $keepalived_global_settings->remote_syslog_syslog_port=intval($_POST["keepalived_remote_syslog_syslog_port"]);
//    $keepalived_global_settings->syslog_tcp_ip=intval($_POST["keepalived_syslog_tcp_ip"]);
//    $keepalived_global_settings->syslog_ssl=intval($_POST["keepalived_syslog_ssl"]);
//    $keepalived_global_settings->sylog_certificate=$_POST["keepalived_sylog_certificate"];
    $keepalived_global_settings->save();
}