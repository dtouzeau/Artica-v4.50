<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_POST["log-sink-confirm"])){exit;}
if(isset($_POST["RsyslogInterface"])){save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["top-status"])){top_status();exit;}
if(isset($_GET["install-log-sink-js"])){log_sink_install_ask();exit;}
if(isset($_GET["logs-sink-install"])){log_sink_install();exit;}
if(isset($_GET["logs-sink-uninstall"])){log_sink_uninstall();exit;}
if(isset($_GET["disable-ssl-ask"])){disable_ssl_ask();exit;}
if(isset($_POST["disable-ssl"])){disable_ssl_confirm();exit;}
if(isset($_GET["download-ca"])){download_ca();exit;}
if(isset($_GET["main-form-js"])){main_form_js();exit;}
if(isset($_GET["main-form-popup"])){main_form_popup();exit;}
if(isset($_GET["syslogd-status"])){daemon_status();exit;}
page();

function tabs():bool{
    $ActAsASyslogServer     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActAsASyslogServer"));
    $page=CurrentPageName();
    $tpl=new template_admin();
    if($ActAsASyslogServer==1) {
        $array["{APP_SYSLOGD}"] = "$page?status=yes";
    }

    $array["{remote_logging}"]="fw.syslogd.remote.php";
    echo $tpl->tabs_default($array);
    return true;
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{APP_SYSLOGD} v{$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SYSLOGD_VERSION")}",
    ico_eye,"{APP_SYSLOG_SERVER_EXPLAIN}","$page?tabs=yes","syslogd",
        "progress-syslod-restart",false,"table-loader-syslod-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_SYSLOGD}",$html);
        echo $tpl->build_firewall();
        return true;
    }


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function download_ca():bool{
    $RsyslogCertificates=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogCertificates")));

    $data=$RsyslogCertificates["CA"];
    $fsize=strlen($data);
    $hostname=php_uname('n');
    header('Content-type:application/x-x509-ca-cert');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$hostname.ca\"");
    header("Pragma: public");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    echo $data;
    return true;
}
function log_sink_install_ask():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog_confirm_action("{install_logsink_ask}",
        "log-sink-confirm",$tpl->_ENGINE_parse_body("{logs_sink} {install}"),"Loadjs('$page?logs-sink-install=yes')");
    return true;
}
function log_sink_install():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    header("content-type: application/x-javascript");


    $install_daemon=$tpl->framework_buildjs(
        "/syslog/server/install","syslog.install.progress","syslog.install.progress.log","progress-syslod-restart",
        "window.location='/syslogd?t=install'");

    echo $install_daemon;
    admin_tracks("Activate Logs Sink feature in syslog");
    return true;
}
function log_sink_uninstall():bool{
    $tpl=new template_admin();
    header("content-type: application/x-javascript");

    $uninstall_daemon=$tpl->framework_buildjs(
        "/syslog/server/uninstall","syslog.install.progress","syslog.install.progress.log","progress-syslod-restart",
        "window.location='/index'");

    echo $uninstall_daemon;
    admin_tracks("Disable Logs Sink feature in syslog");
    return true;
}
function ServiceStatus():string{
    $tpl        = new template_admin();
    $sock=new sockets();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/syslog/status"));
    $page       = CurrentPageName();



    $jsrestart=$tpl->framework_buildjs("/syslog/reconfigure",
        "syslog.restart.progress",
        "syslog.restart.log",
        "progress-syslod-restart","LoadAjaxSilent('syslog-status','$page?syslogd-status=yes');");

    if (json_last_error()> JSON_ERROR_NONE) {
        return  $tpl->widget_rouge("{error}",json_last_error_msg());
    }
    if (!$json->Status) {
        return $tpl->widget_rouge("{error}", $json->Error);
    }
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);


    $json=json_decode($sock->REST_API("/dns/collector/status"));
    $bsiniCollector=new Bs_IniHandler();
    $bsiniCollector->loadString($json->Info);
    $jsRestartCollector=$tpl->framework_buildjs("/dns/collector/restart","dns-collector.progress",
        "dns-collector.log",
        "progress-syslod-restart","LoadAjaxSilent('syslog-status','$page?syslogd-status=yes');");

    $ActAsASyslogServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActAsASyslogServer"));
    if($ActAsASyslogServer==0){
        $final[]="<div style='text-align:left'>".$tpl->div_warning("{feature_still_visible}")."</div>";
    }

    $final[]=$tpl->SERVICE_STATUS($ini, "APP_RSYSLOG",$jsrestart);
    $final[]=$tpl->SERVICE_STATUS($bsiniCollector, "APP_DNS_COLLECTOR",$jsRestartCollector);

    return $tpl->_ENGINE_parse_body($final);
}


function daemon_status():bool{
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body(ServiceStatus().top_status_syslog());
    echo "\n<script>document.getElementById('progress-syslod-restart').innerHTML='';</script>";
    return true;
}

function status():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();


    $html[]="<table style='width:100%;margin-top:20px'>
	<tr>
		<td style='vertical-align: top;width:350px'><div class='center' id='syslog-status'></div></td>
		<td style='vertical-align: top'>";
    $html[]="<div id='top-status-syslog' style='margin-bottom:10px;padding-left: 10px'></div>";

    $RsyslogInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogInterface");
    $RsyslogPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogPort"));
    $RsyslogProtoTCP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogProtoTCP"));
    $RsyslogDisableProtoUDP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogDisableProtoUDP"));
    $RsyslogTCPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogTCPPort"));
    if($RsyslogTCPPort==0){$RsyslogTCPPort=5514;}
    if($RsyslogPort==0){$RsyslogPort=514;}
    if($RsyslogInterface==null){$RsyslogInterface="{all_interfaces}";}
    $tpl->table_form_section("{general_settings}");

    $EnableSyslogLogSink=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSyslogLogSink"));
    $tpl->table_form_field_install("{logs_sink}", $EnableSyslogLogSink,
        "Loadjs('$page?install-log-sink-js=yes');","Loadjs('$page?logs-sink-uninstall=yes');");

    $tpl->table_form_field_js("Loadjs('$page?main-form-js=yes')");

    if($RsyslogDisableProtoUDP==1) {
        $tpl->table_form_field_bool("{UDP_PROTOCOL}", 0, ico_proto);
    }else{
        $tpl->table_form_field_bool("{UDP_PROTOCOL}", 1, ico_proto);
    }

    $tpl->table_form_field_text("{listen_interface}", "$RsyslogInterface:$RsyslogPort", ico_nic);

    $LogsSinkDNSTAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsSinkDNSTAP"));
    $LogsSinkDNSTAPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsSinkDNSTAPPort"));
    if($LogsSinkDNSTAPPort==0){$LogsSinkDNSTAPPort="6000";}

    if($LogsSinkDNSTAP==0){
        $tpl->table_form_field_bool("{dnstap_protocol}", 0, ico_proto);
    }else{
        $tpl->table_form_field_text("{dnstap_protocol}", "$RsyslogInterface:$LogsSinkDNSTAPPort", ico_proto);
    }


    $tpl->table_form_section("{tcp_protocol}");

    $RsyslogTCPUseSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogTCPUseSSL"));
    if($RsyslogTCPUseSSL==1){$RsyslogProtoTCP=1;}
    $tpl->table_form_field_bool("{enable_tcpsockets}", $RsyslogProtoTCP, ico_proto);
    if($RsyslogProtoTCP==1) {
        $tpl->table_form_field_text("{listen_port}", "$RsyslogInterface:$RsyslogTCPPort", ico_nic);
    }




    $html[]=$tpl->table_form_compile();



    $install_daemon=$tpl->framework_buildjs(
        "/syslog/server/uninstall","syslog.install.progress","syslog.install.progress.log","progress-syslod-restart",
        "document.location.reload()");


    $topbuttons[] = array("$install_daemon;", "fas fa-trash-alt", "{uninstall}:{APP_SYSLOGD}");
    $s_PopUp="s_PopUp('https://wiki.articatech.com/system/syslog','1024','800')";
    $topbuttons[] = array($s_PopUp, ico_support, "Wiki URL");

    $TINY_ARRAY["TITLE"]="{APP_SYSLOGD} v{$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SYSLOGD_VERSION")}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_SYSLOG_SERVER_EXPLAIN}";
    $TINY_ARRAY["URL"]="syslogd";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $Interval=$tpl->RefreshInterval_js("syslog-status",$page,"syslogd-status=yes");

    $html[]="<script>";
    $html[]=$Interval;
    $html[]=$jstiny;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}

function top_status_css():string{
return "minheight:257px;margin-top:3px;width:350px";

}
function main_form_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog2("{parameters}","$page?main-form-popup=yes");
    return true;

}
function main_form_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $RsyslogInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogInterface");
    $RsyslogPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogPort"));
    $RsyslogProtoTCP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogProtoTCP"));
    $RsyslogDisableProtoUDP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogDisableProtoUDP"));
    $RsyslogTCPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogTCPPort"));
    if($RsyslogTCPPort==0){$RsyslogTCPPort=5514;}
    if($RsyslogPort==0){$RsyslogPort=514;}
    $form[]=$tpl->field_interfaces("RsyslogInterface", "{listen_interface}", $RsyslogInterface);

    $form[]=$tpl->field_section("{UDP_PROTOCOL}");
    $form[]=$tpl->field_checkbox_disbaleON("RsyslogDisableProtoUDP","{disable_udpsockets}",$RsyslogDisableProtoUDP,"RsyslogPort");
    $form[]=$tpl->field_numeric("RsyslogPort","{listen_port}",$RsyslogPort);
     $form[]=$tpl->field_section("{tcp_protocol}");

    $RsyslogProtoTCPDisabled=false;
    $RsyslogTCPUseSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogTCPUseSSL"));
    if($RsyslogTCPUseSSL==1){
        $RsyslogProtoTCPDisabled=true;
        $RsyslogProtoTCP=1;
    }

    $form[]=$tpl->field_checkbox("RsyslogProtoTCP","{enable_tcpsockets}",$RsyslogProtoTCP,"RsyslogTCPPort",null,$RsyslogProtoTCPDisabled);
    $form[]=$tpl->field_numeric("RsyslogTCPPort","{listen_port}",$RsyslogTCPPort);

    $LogsSinkDNSTAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsSinkDNSTAP"));
    $LogsSinkDNSTAPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsSinkDNSTAPPort"));
    if($LogsSinkDNSTAPPort==0){$LogsSinkDNSTAPPort="6000";}
    $form[]=$tpl->field_section("{dnstap_protocol}");
    $form[]=$tpl->field_checkbox("LogsSinkDNSTAP","{enable}",$LogsSinkDNSTAP,"LogsSinkDNSTAPPort");
    $form[]=$tpl->field_numeric("LogsSinkDNSTAPPort","{listen_port}",$LogsSinkDNSTAPPort);

    $jsrestart=$tpl->framework_buildjs("/syslog/reconfigure",
        "syslog.restart.progress",
        "syslog.restart.log","progress-syslod-restart",
        "dialogInstance2.close();LoadAjax('table-loader-syslod-service','$page?tabs=yes');");



    $html[]=$tpl->form_outside("", @implode("\n", $form),null,"{apply}",$jsrestart,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}





function top_status_syslog():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $SYSLOG_MSG_RECEIVED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSLOG_MSG_RECEIVED"));
    if($SYSLOG_MSG_RECEIVED>0){
        $SYSLOG_MSG_RECEIVED=$tpl->FormatNumber($SYSLOG_MSG_RECEIVED);
       return $tpl->widget_vert("{received_messages}",$SYSLOG_MSG_RECEIVED);
    }
    return $tpl->widget_grey("{received_messages}","{no_data}");

}




function save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();

}