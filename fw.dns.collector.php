<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["flat"])){flat_config();exit;}

if(isset($_GET["RemoteSyslogServer-js"])){RemoteSyslogServer_js();exit;}
if(isset($_GET["RemoteSyslogServer-popup"])){RemoteSyslogServer_popup();exit;}

if(isset($_GET["RetentionParams-js"])){RetentionParams_js();exit;}
if(isset($_GET["RetentionParams-popup"])){RetentionParams_popup();exit;}


if(isset($_POST["UnboundMaxLogsize"])){SyslogSave();exit;}
if(isset($_POST["DNSCollectorSyslog"])){SyslogSave();exit;}

page();



function delete_js(){
	$tpl=new template_admin();
	$hostname=urlencode($_GET["delete-js"]);
	$md=$_GET["md"];
	$sock=new sockets();
	$sock->getFrameWork("unbound.php?cache-clear=$hostname");
	header("content-type: application/x-javascript");
	echo "$('#$md').remove();";
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();


    $TINY_ARRAY["TITLE"] ="{APP_DNS_COLLECTOR}";
    $TINY_ARRAY["ICO"] = ico_script;
    $TINY_ARRAY["EXPL"] = "{APP_DNS_COLLECTOR_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"] = null;


	
    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td style='width:350px;vertical-align: top'><div id='dns-collector-status'></div></td>";
    $html[]="<td style='width:99%;vertical-align: top'><div id='dns-collector-flat'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('dns-collector-status','$page?status=yes');";
    $html[]="LoadAjax('dns-collector-flat','$page?flat=yes');";
    $html[]=$jstiny;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);

}

function flat_config(){
    $sock=new sockets();
    $page=CurrentPageName();
    $tpl=new template_admin();

    $UnboundMaxLogsize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundMaxLogsize"));
    if($UnboundMaxLogsize==0){$UnboundMaxLogsize=500;}
    $DNSCollectorSyslog=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSCollectorSyslog"));
    $DNSCollectorDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSCollectorDebug"));

    $tpl->table_form_field_js("Loadjs('$page?RetentionParams-js=yes')","AsDnsAdministrator");
    $tpl->table_form_field_text("{ArticaMaxLogsSize}","{$UnboundMaxLogsize}MB",ico_weight);
    $tpl->table_form_field_bool("{debug}",$DNSCollectorDebug,ico_bug);

    $tpl->table_form_field_js("Loadjs('$page?RemoteSyslogServer-js=yes')","AsDnsAdministrator");
    if($DNSCollectorSyslog==0) {
        $tpl->table_form_field_bool("{logs_sink}",0,ico_sensor);
    }else{
        $DNSCollectorSyslogAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSCollectorSyslogAddr");
        $DNSCollectorSyslogPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSCollectorSyslogPort"));
        if($DNSCollectorSyslogPort==0){
            $DNSCollectorSyslogPort=6000;
        }
        $tpl->table_form_field_text("{logs_sink}","dnstap://$DNSCollectorSyslogAddr:$DNSCollectorSyslogPort",ico_sensor);
    }
    echo $tpl->table_form_compile();
}
function SyslogSave():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $sock=new sockets();
    $sock->REST_API("/dns/collector/restart");
    return admin_tracks_post("Saving DNS Collector remote syslog parameters");
}
function RemoteSyslogServer_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{logs_sink}","$page?RemoteSyslogServer-popup=yes",500);
}
function RetentionParams_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{events}","$page?RetentionParams-popup=yes",500);
}
function RetentionParams_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $jsafter[]="dialogInstance2.close()";
    $jsafter[]="LoadAjax('dns-collector-flat','$page?flat=yes')";

    $DNSCollectorDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSCollectorDebug"));
    $UnboundMaxLogsize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundMaxLogsize"));
    if($UnboundMaxLogsize==0){$UnboundMaxLogsize=500;}
    $form[] = $tpl->field_checkbox("DNSCollectorDebug","{debug}",$DNSCollectorDebug);
    $form[] = $tpl->field_numeric("UnboundMaxLogsize","{ArticaMaxLogsSize}",$UnboundMaxLogsize);
    echo $tpl->form_outside("",$form,"","{apply}",@implode(";",$jsafter),"AsDnsAdministrator");
    return true;
}

function RemoteSyslogServer_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $jsafter[]="dialogInstance2.close()";
    $jsafter[]="LoadAjax('dns-collector-flat','$page?flat=yes')";

    $DNSCollectorSyslog=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSCollectorSyslog"));
    $DNSCollectorSyslogAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSCollectorSyslogAddr");
    $DNSCollectorSyslogPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSCollectorSyslogPort"));

    if($DNSCollectorSyslogPort==0){
        $DNSCollectorSyslogPort=6000;
    }

    $form[] = $tpl->field_checkbox("DNSCollectorSyslog", "{enable_feature}", $DNSCollectorSyslog,true);
    $form[] = $tpl->field_ipv4("DNSCollectorSyslogAddr", "{remote_server}", $DNSCollectorSyslogAddr);
    $form[] = $tpl->field_numeric("DNSCollectorSyslogPort","{listen_port}",$DNSCollectorSyslogPort);
    echo $tpl->form_outside("",$form,"{log_sink_explain}","{apply}",@implode(";",$jsafter),"AsDnsAdministrator");
    return true;
}

function status(){
    $sock=new sockets();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $json=json_decode($sock->REST_API("/dns/collector/status"));
    $bsiniCollector=new Bs_IniHandler();
    $bsiniCollector->loadString($json->Info);
    $jsRestartCollector=$tpl->framework_buildjs("/dns/collector/restart","dns-collector.progress",
        "dns-collector.log",
        "progress-unbound-restart","LoadAjaxSilent('dns-collector-status','$page?status=yes')");

    $html[]=$tpl->SERVICE_STATUS($bsiniCollector, "APP_DNS_COLLECTOR",$jsRestartCollector);
    echo $tpl->_ENGINE_parse_body($html);
}



