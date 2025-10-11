<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["server-section"])){server_section_js();exit;}
if(isset($_GET["server-section-popup"])){server_section_popup();exit;}

if(isset($_GET["timeout-section"])){timeout_section_js();exit;}
if(isset($_GET["timeout-section-popup"])){timeout_section_popup();exit;}
if(isset($_GET["build-plugins"])){build_plugins();exit;}

if(isset($_GET["flat"])){flat_config();exit;}
if(isset($_GET["table"])){table();exit;}

if(isset($_POST["SavePost"])){Save();exit;}
if(isset($_GET["go-shield-connector-status"])){status();exit;}
if(isset($_GET["emergency-enable"])){emergency_enable();exit;}
if(isset($_GET["logfile-js"])){logfile_js();exit;}
if(isset($_GET["go-shield-connector-form-server"])){ksrn_form_server();exit;}
if(isset($_GET["enable-ufdbguard-temp"])){enable_ufdbguard_temp();exit;}
if(isset($_GET["enable-connector-temp"])){enable_connector_temp();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["top-status"])){top_status();exit;}

if(isset($_GET["top-status"])){top_buttons();exit;}
if(isset($_GET["link-connector"])){link_connector_js();exit;}
if(isset($_GET["link-connector-popup"])){link_connector_popup();exit;}
if(isset($_GET["link-connector-action"])){link_connector_action();exit;}

page();

function server_section_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{server}","$page?server-section-popup=yes",650);
}
function timeout_section_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{performance}","$page?timeout-section-popup=yes",650);
}


function link_connector_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{connector}","$page?link-connector-popup=yes",650);
}
function link_connector_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $Go_Shield_Connector_Enable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Enable"));
    if($Go_Shield_Connector_Enable==0) {
        $btn["name"] = "{activate}";
        $btn["icon"] = ico_plug;
        $btn["js"] = "Loadjs('$page?link-connector-action=1')";
        echo $tpl->_ENGINE_parse_body($tpl->widget_h("gray", ico_disabled, "{inactive2}", "{APP_SQUID} {connector}", $btn));
        return true;

    }
    $btn["name"] = "{disable}";
    $btn["icon"] = ico_unlink;
    $btn["js"] = "Loadjs('$page?link-connector-action=0')";
    echo $tpl->_ENGINE_parse_body($tpl->widget_h("green", ico_run, "{active2}", "{APP_SQUID} {connector}", $btn));
    return true;
}
function link_connector_action():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("Go_Shield_Connector_Enable",intval($_GET["link-connector-action"]));
    $sock=new sockets();
    $sock->REST_API("/proxy/config/filter");


    header("content-type: application/x-javascript");
    echo "dialogInstance2.close();\n";
    echo "if(document.getElementById('go-shield-quick')){\n";
    echo "LoadAjaxSilent('go-shield-quick','fw.proxy.disable.php?go-shield-quick=yes');\n";
    echo "}\n";
    echo "if(document.getElementById('go-shield-connector-status')){\n";
    echo "LoadAjaxSilent('go-shield-connector-status','$page?go-shield-connector-status=yes');\n";
    echo "}\n";
    echo "if(document.getElementById('global-status')){\n";
    echo "LoadAjaxSilent('global-status','fw.ksrn.filtering.features.php?global-status=yes');\n";
    echo "}\n";


    return admin_tracks("Set Go-Shield Connector activation to: ".intval($_GET["link-connector-action"]));
}

function tabs(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $array["{main}"]="$page?table=yes";
    echo $tpl->tabs_default($array);

}

function enable_ufdbguard_temp(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $UfdbGuardDisabledTemp=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardDisabledTemp"));
    if($UfdbGuardDisabledTemp==0){
        admin_tracks("Disable the Go Web-filtering client");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UfdbGuardDisabledTemp",1);
    }else{
        admin_tracks("Enable the Go Web-filtering client");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UfdbGuardDisabledTemp",0);
    }

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/goshield/connector/reconfigure");

    echo "if(document.getElementById('go-shield-quick')){\n";
    echo "LoadAjaxSilent('go-shield-quick','fw.proxy.disable.php?go-shield-quick=yes');\n";
    echo "}\n";
    echo "if(document.getElementById('go-shield-connector-status')){\n";
    echo "LoadAjaxSilent('go-shield-connector-status','$page?go-shield-connector-status=yes');\n";
    echo "}\n";

    return true;
}
function enable_connector_temp(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $GoShieldConnectorDisableACL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldConnectorDisableACL"));
    if($GoShieldConnectorDisableACL==0){
        admin_tracks("Disable the Go Connector client");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GoShieldConnectorDisableACL",1);
    }else{
        admin_tracks("Enable the Go Connector client");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GoShieldConnectorDisableACL",0);
    }

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/goshield/connector/restart");
    echo "LoadAjaxSilent('go-shield-connector-status','$page?go-shield-connector-status=yes');";
}









function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/goshield/connector/version"));
    $Go_Shield_Connector_Version=$json->Info->Version;


    VERBOSE("Go-Shield-Connector-Version = $Go_Shield_Connector_Version",__LINE__);
    if(trim($Go_Shield_Connector_Version==null)){
        $Go_Shield_Connector_Version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("GO_SHIELD_CONNECTOR_VERSION");
    }
    if(strlen($Go_Shield_Connector_Version)>1){
        $Go_Shield_Connector_Version="v$Go_Shield_Connector_Version";
    }

    $html=$tpl->page_header("Proxy {connector} $Go_Shield_Connector_Version",
        "fad fa-exchange-alt",
        "{go_shield_connector_explain}",
        "$page?tabs=yes",
        "go-shield-connector",
        "progress-go-shield-connector-restart",
        false,
        "table-loader-go-shield-connector-pages");

    if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return true;}
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}



function logfile_js(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    echo $tpl->framework_buildjs("ksrn.php?log-file=yes","ksrn.progress","ksrn.log",
        "progress-go-shield-connector-restart","document.location.href='/ressources/logs/web/ksrn.log.gz';");

}

function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $StatusRefersh=$tpl->RefreshInterval_js("go-shield-connector-status",$page,"go-shield-connector-status=yes");

    $html="<table style='width:100%'>
    
	<tr>
	<td style='vertical-align:top;width:240px'>
	    <div id='go-shield-connector-status' style='margin-top:15px'></div>
	</td>
	<td	style='vertical-align:top;width:90%;padding-left:10px'>
	    <div id='go-shield-client-top' style='margin-top: 5px'></div>
	    <div id='ufdbguard-client'></div></td>
	</tr>
	</table>
	<script>
	    LoadAjax('ufdbguard-client','$page?flat=yes');
	   $StatusRefersh
        Loadjs('$page?top-status=yes');
	</script>
	";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function top_status(){
    $page=CurrentPageName();
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/redirector/stats"));
    $requests_status=requests_status($json);
    $processes_status=processes_status($json);
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:50%'>";
    $html[]=$processes_status;
    $html[]="</td>";
    $html[]="<td style='width:50%;padding-left:10px'>";
    $html[]=$requests_status;
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo @implode("\n",$html);
}
function btn_status():array{
    $btn=array();
    $page=CurrentPageName();
    $btn["name"]="{reconfigure}";
    $btn["icon"]=ico_refresh;
    $btn["js"] ="Loadjs('$page?build-plugins=yes')";
    return $btn;
    //
}
function build_plugins():bool{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/plugins"));
    if(!$json->Status){
        echo $tpl->js_error($json->Error);
        return false;
    }
    echo $tpl->js_ok("{success}");
    return true;
}
function requests_status($json):string{
    $tpl=new template_admin();
    $btn=btn_status();
    if (!$json->Status) {
        return $tpl->widget_h("red", "fab fa-soundcloud", "{error}", "{GO_SHIELD_CONNECTOR} $json->Error", $btn);
    }
    if (!property_exists($json, "Stats")) {
        return $tpl->widget_h("red", "fab fa-soundcloud", "{error}", "{GO_SHIELD_CONNECTOR} Stats!", $btn);
    }
    if($json->Stats->Disabled){
        $btn=array();
        return $tpl->_ENGINE_parse_body($tpl->widget_h("grey", "fab fa-soundcloud", "{inactive2}", "{GO_SHIELD_CONNECTOR} ({webfiltering})", $btn));
    }

    $RequestsSent = $tpl->FormatNumber($json->Stats->RequestsSent);
    return $tpl->widget_h("green", "fab fa-soundcloud", "$RequestsSent", "{requests}", $btn);
}

function processes_status($json):string{

    $tpl=new template_admin();
    $btn=btn_status();

    if (!$json->Status) {
        return $tpl->_ENGINE_parse_body($tpl->widget_h("red", ico_cpu, "{error}", "{GO_SHIELD_CONNECTOR} $json->Error", $btn));
    }
    if (!property_exists($json, "Stats")) {
        return $tpl->_ENGINE_parse_body($tpl->widget_h("red", ico_cpu, "{error}", "{GO_SHIELD_CONNECTOR} Stats!", $btn));
    }
    if($json->Stats->Disabled){
        $btn=array();
        return $tpl->_ENGINE_parse_body($tpl->widget_h("grey", ico_cpu, "{inactive2}", "{GO_SHIELD_CONNECTOR} ({webfiltering})", $btn));
    }
    $NumberActive = $json->Stats->NumberActive;
    return $tpl->widget_h("green", ico_cpu, "$NumberActive", "{processes}", $btn);
}
function flat_config(){
    $EnableDNSFirewall=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSFirewall"));
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $kInfos         = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kInfos")));
    if(!isset($kInfos["enable"])){$kInfos["enable"]=0;}

    $Go_Shield_Server_Enable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));
    $Go_Shield_Connector_Addr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Addr"));
    $Go_Shield_Connector_Port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Port"));
    $Go_Shield_Connector_TimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_TimeOut"));
    $KSRNClientCacheTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNClientCacheTime"));
    $NetCoreSomaxConn=$GLOBALS["CLASS_SOCKETS"]->getFrameWork("cmd.php?sysctl-value=yes&key=".base64_encode("net.core.somaxconn"));

    $UfdbGuardWebFilteringCacheTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardWebFilteringCacheTime"));
    if($UfdbGuardWebFilteringCacheTime==0){$UfdbGuardWebFilteringCacheTime=300;}
    //net.core.somaxconn=

    $Go_Shield_Connector_Debug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Debug"));
    $SQUIDEnable    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));

    if(empty($Go_Shield_Connector_Addr)){$Go_Shield_Connector_Addr="127.0.0.1";}
    if($Go_Shield_Connector_Port==0){$Go_Shield_Connector_Port=3333;}
    if($Go_Shield_Connector_TimeOut==0){$Go_Shield_Connector_TimeOut=5;}


    $tpl->table_form_field_js("Loadjs('$page?server-section=yes')","AsSquidAdministrator");
    $tpl->table_form_field_bool("{debug}",$Go_Shield_Connector_Debug,ico_bug);
    $tpl->table_form_field_text("{server_address}","$Go_Shield_Connector_Addr:$Go_Shield_Connector_Port",ico_server);

    $tpl->table_form_field_js("Loadjs('$page?timeout-section=yes')","AsSquidAdministrator");
    $tpl->table_form_field_text("{timeout}","$Go_Shield_Connector_TimeOut {seconds}",ico_timeout);
    $tpl->table_form_field_text("{ttl_cache_webfiltering}","$UfdbGuardWebFilteringCacheTime {seconds}",ico_timeout);

    $SquidClientParams=SquidClientParams();
    $perfs[]="{CHILDREN_MAX} {$SquidClientParams["go_shield_connector_param_daemon_children"]}";
    $perfs[]="{CHILDREN_CONCURRENCY} {$SquidClientParams["go_shield_connector_param_daemon_concurrency"]}";
    $perfs[]="{CHILDREN_STARTUP} {$SquidClientParams["go_shield_connector_param_daemon_startup"]}";
    $perfs[]="{CHILDREN_IDLE} {$SquidClientParams["go_shield_connector_param_daemon_idle"]}";
    $perfs[]="{POSITIVE_CACHE_TTL} {$SquidClientParams["go_shield_connector_param_daemon_positive_ttl"]} {seconds}";

    $tpl->table_form_field_text("{performance}",@implode(", ",$perfs),ico_timeout);
    echo $tpl->table_form_compile();
    return true;
}
function server_section_popup():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $Go_Shield_Connector_Addr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Addr"));
    $Go_Shield_Connector_Port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Port"));
    $Go_Shield_Connector_Debug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Debug"));
    if(empty($Go_Shield_Connector_Addr)){$Go_Shield_Connector_Addr="127.0.0.1";}
    if($Go_Shield_Connector_Port==0){$Go_Shield_Connector_Port=3333;}
    $form[] = $tpl->field_hidden("SavePost","yes");
    $form[] = $tpl->field_checkbox("Go_Shield_Connector_Debug","{debug}",$Go_Shield_Connector_Debug);
    $form[] = $tpl->field_text("Go_Shield_Connector_Addr", "{server_address}", $Go_Shield_Connector_Addr);
    $form[] = $tpl->field_numeric("Go_Shield_Connector_Port", "{server_port}", $Go_Shield_Connector_Port);
    echo $tpl->form_outside(null, $form,null,"{apply}",restart_js(),"AsSquidAdministrator");
    return true;
}

function ttl_interval():array{
    $ttl_interval[30]="30 {seconds}";
    $ttl_interval[60]="1 {minute}";
    $ttl_interval[300]="5 {minutes}";
    $ttl_interval[600]="10 {minutes}";
    $ttl_interval[900]="15 {minutes}";
    $ttl_interval[1800]="30 {minutes}";
    $ttl_interval[3600]="1 {hour}";
    $ttl_interval[7200]="2 {hours}";
    $ttl_interval[14400]="4 {hours}";
    $ttl_interval[18000]="5 {hours}";
    $ttl_interval[86400]="1 {day}";
    $ttl_interval[172800]="2 {days}";
    $ttl_interval[259200]="3 {days}";
    $ttl_interval[432000]="5 {days}";
    $ttl_interval[604800]="1 {week}";
    return $ttl_interval;
}
function start_up():array{
    $start_up[1]=1;
    $start_up[2]=2;
    $start_up[3]=3;
    $start_up[4]=4;
    $start_up[5]=5;
    $start_up[10]=10;
    $start_up[15]=15;
    $start_up[20]=20;
    $start_up[25]=25;
    $start_up[30]=30;
    $start_up[35]=35;
    $start_up[40]=40;
    $start_up[45]=45;
    $start_up[50]=50;
    $start_up[55]=55;
    $start_up[60]=60;
    $start_up[65]=65;
    $start_up[70]=70;
    $start_up[80]=80;
    $start_up[85]=85;
    $start_up[90]=90;
    $start_up[100]=100;
    $start_up[150]=150;
    $start_up[200]=200;
    $start_up[300]=300;
    $start_up[400]=300;
    $start_up[500]=500;
    $start_up[600]=600;
    $start_up[700]=700;
    $start_up[800]=800;
    $start_up[900]=900;
    $start_up[1000]=1000;
    $start_up[1500]=1500;
    return $start_up;
}

function timeout_section_popup():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $Go_Shield_Connector_TimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_TimeOut"));
    if($Go_Shield_Connector_TimeOut==0){$Go_Shield_Connector_TimeOut=5;}
    $NetCoreSomaxConn=$GLOBALS["CLASS_SOCKETS"]->getFrameWork("cmd.php?sysctl-value=yes&key=".base64_encode("net.core.somaxconn"));
    $KSRNClientCacheTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNClientCacheTime"));


    $UfdbGuardWebFilteringCacheTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardWebFilteringCacheTime"));
    if($UfdbGuardWebFilteringCacheTime==0){$UfdbGuardWebFilteringCacheTime=300;}
    $ttl_interval=ttl_interval();
    $form[] = $tpl->field_hidden("SavePost","yes");
    $form[] = $tpl->field_numeric("Go_Shield_Connector_TimeOut","{timeout} ({seconds})", $Go_Shield_Connector_TimeOut);
    $form[]         = $tpl->field_numeric("NetCoreSomaxConn","{acl_maxconn}", $NetCoreSomaxConn);
    $form[]         = $tpl->field_numeric("UfdbGuardWebFilteringCacheTime","{ttl_cache_webfiltering} ({seconds})", $UfdbGuardWebFilteringCacheTime,"{ttl_cache_webfiltering_explain}");
    $form[] = $tpl->field_array_hash($ttl_interval, "KSRNClientCacheTime", "{TTL_CACHE}", $KSRNClientCacheTime);

    $SquidClientParams=SquidClientParams();
    $start_up=start_up();
    $form[] = $tpl->field_array_hash($start_up, "go_shield_connector_param_daemon_children", "{CHILDREN_MAX}", $SquidClientParams["go_shield_connector_param_daemon_children"]);

    $form[] = $tpl->field_numeric("go_shield_connector_param_daemon_concurrency", "{CHILDREN_CONCURRENCY}", $SquidClientParams["go_shield_connector_param_daemon_concurrency"]);
    $form[] = $tpl->field_array_hash($start_up, "go_shield_connector_param_daemon_startup", "{CHILDREN_STARTUP}", $SquidClientParams["go_shield_connector_param_daemon_startup"]);
    $form[] = $tpl->field_array_hash($start_up, "go_shield_connector_param_daemon_idle", "{CHILDREN_IDLE}", $SquidClientParams["go_shield_connector_param_daemon_idle"]);
    $form[] = $tpl->field_numeric("go_shield_connector_param_daemon_positive_ttl", "{POSITIVE_CACHE_TTL} ({seconds})",
        $SquidClientParams["go_shield_connector_param_daemon_positive_ttl"]);
    $form[] = $tpl->field_numeric("go_shield_connector_param_daemon_negative_ttl", "{NEGATIVE_CACHE_TTL} ({seconds})",
        $SquidClientParams["go_shield_connector_param_daemon_negative_ttl"]);
    echo $tpl->form_outside(null, $form,null,"{apply}",restart_js(),"AsSquidAdministrator");
    return true;
}

function SquidClientParams():array{

    $SquidClientParams=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GO_SHIELD_CONNECTOR_DAEMONS"));
    if(!isset($SquidClientParams["go_shield_connector_param_daemon_children"])){$SquidClientParams["go_shield_connector_param_daemon_children"]=10;}
    if(!isset($SquidClientParams["go_shield_connector_param_daemon_startup"])){$SquidClientParams["go_shield_connector_param_daemon_startup"]=2;}
    if(!isset($SquidClientParams["go_shield_connector_param_daemon_idle"])){$SquidClientParams["go_shield_connector_param_daemon_idle"]=1;}

    if(!isset($SquidClientParams["go_shield_connector_param_daemon_negative_ttl"])){$SquidClientParams["go_shield_connector_param_daemon_negative_ttl"]=360;}
    if(!isset($SquidClientParams["go_shield_connector_param_daemon_positive_ttl"])){$SquidClientParams["go_shield_connector_param_daemon_positive_ttl"]=360;}

    if(!is_numeric($SquidClientParams["go_shield_connector_param_daemon_children"])){$SquidClientParams["go_shield_connector_param_daemon_children"]=10;}
    if(!is_numeric($SquidClientParams["go_shield_connector_param_daemon_startup"])){$SquidClientParams["go_shield_connector_param_daemon_startup"]=2;}
    if(!is_numeric($SquidClientParams["go_shield_connector_param_daemon_idle"])){$SquidClientParams["go_shield_connector_param_daemon_idle"]=1;}

    if(!is_numeric($SquidClientParams["go_shield_connector_param_daemon_negative_ttl"])){$SquidClientParams["go_shield_connector_param_daemon_negative_ttl"]=360;}
    if(!is_numeric($SquidClientParams["go_shield_connector_param_daemon_positive_ttl"])){$SquidClientParams["go_shield_connector_param_daemon_positive_ttl"]=360;}

    if(!isset($SquidClientParams["go_shield_connector_param_daemon_concurrency"])){$SquidClientParams["go_shield_connector_param_daemon_concurrency"]=500;}
    if($SquidClientParams["go_shield_connector_param_daemon_concurrency"]<100){$SquidClientParams["go_shield_connector_param_daemon_concurrency"]=100;}
    return $SquidClientParams;

}



function restart_js():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return "LoadAjax('ufdbguard-client','$page?flat=yes');dialogInstance2.close();".$tpl->framework_buildjs("/goshield/connector/restart","go.shield.connector.progress","go.shield.connector.log","progress-go-shield-connector-restart","");

    //LoadAjaxSilent('go-shield-connector-status','$page?go-shield-connector-status=yes')
}

function emergency_enable():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GoShieldConnectorEmergency", 1);
    admin_tracks("The Go Shield Connector Emergency method was enabled");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/goshield/connector/emergency/on");
    header("content-type: application/x-javascript");
    return admin_tracks("The Go Shield Connector Emergency method was enabled");
}

function status_dns_firewall():bool{
    $tpl            = new template_admin();
    $EnableDNSFirewall=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSFirewall"));
    if($EnableDNSFirewall==0){
        echo $tpl->widget_grey("{APP_SQUID} {disabled}","{KSRN_CLIENT}");
        return false;
    }

    $Go_Shield_Connector_Addr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Addr"));
    $Go_Shield_Connector_Port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Port"));

        $fp = fsockopen($Go_Shield_Connector_Addr,$Go_Shield_Connector_Port,$errno,$errstr,1);
        if(!$fp){
            echo $tpl->widget_rouge($errstr."<br>$Go_Shield_Connector_Addr:$Go_Shield_Connector_Port","{connection_error}");
            return false;
        }
        @fclose($fp);


    echo $tpl->widget_vert("{APP_DNS_FIREWALL}","{active2}");
    return true;
}

function status():bool{
    $tpl            = new template_admin();
    $SQUIDEnable= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    $GoShieldConnectorEmergency  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldConnectorEmergency"));
    $GoShieldConnectorDisableACL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldConnectorDisableACL"));
    $jsRestart      = restart_js();
    $page=CurrentPageName();
    $html=array();
    $EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
    $MacToUidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MacToUidUrgency"));

    echo "<script>Loadjs('$page?top-status=yes')</script>\n";
    if($SQUIDEnable==0) {
        return true;
    }


    if($MacToUidUrgency==0) {
        $Go_Shield_Connector_Addr = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Addr"));
        $Go_Shield_Connector_Port = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Port"));
        if ($Go_Shield_Connector_Addr == null) {
            $Go_Shield_Connector_Addr = "127.0.0.1";
        }
        if ($Go_Shield_Connector_Port == 0) {
            $Go_Shield_Connector_Port = 3333;
        }
        $connection = fsockopen($Go_Shield_Connector_Addr, $Go_Shield_Connector_Port, $errno, $errstr, 1);
        if (!is_resource($connection)) {
            echo $tpl->widget_rouge("{failed_to_connect}", "$errstr<br><small class=text-white>$Go_Shield_Connector_Addr:$Go_Shield_Connector_Port</small>");
        }else {
            fclose($connection);
        }
    }
    $refresh = "LoadAjaxSilent('go-shield-connector-status','$page?go-shield-connector-status=yes');";



    //Web-Filtering mode ( with UFDB )
    if($EnableUfdbGuard==1) {
        $data = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/goshield/connector/status"));
        $bsini = new Bs_IniHandler();
        $bsini->loadString($data->Info);
        $Go_Shield_Connector_Addr = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Addr"));
        $Go_Shield_Connector_Port = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Port"));
        if (empty($Go_Shield_Connector_Addr)) {
            $Go_Shield_Connector_Addr = "127.0.0.1";
        }
        if ($Go_Shield_Connector_Port == 0) {
            $Go_Shield_Connector_Port = 3333;
        }
        VERBOSE("ADDR $Go_Shield_Connector_Addr Port $Go_Shield_Connector_Port", __LINE__);

        echo $tpl->SERVICE_STATUS($bsini, "GO_SHIELD_CONNECTOR", restart_js(), null, $refresh);

     $jsonVer=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/goshield/connector/version"));
        //$Go_Shield_Connector_Version=$json->Info->Version;
        $go_shield_connector_src = $jsonVer->Info->md5_src;
        $go_shield_connector_dst =$jsonVer->Info->md5_dst;

        if ($go_shield_connector_src <> $go_shield_connector_dst) {
            $btn[0]["name"] = "{fix_it}";
            $btn[0]["icon"] = ico_play;
            $btn[0]["js"] = $jsRestart;
            $html[] = "<hr>";
            $html[] = $tpl->widget_jaune("{need_update_ksrn}", "{update2}", $btn);
        }
    }
    if($GoShieldConnectorDisableACL==1){
        echo $tpl->div_warning("{ERROR_GO_CONNECTOR_STOPPED}");
    }
    if ($GoShieldConnectorEmergency == 1) {
        $btn[0]["name"] = "{disable_emergency_mode}";
        $btn[0]["icon"] = ico_play;
        $btn[0]["js"] = $jsRestart;
        echo $tpl->widget_rouge("{emergency_mode}", "{emergency_mode}", $btn);
        return false;
    }
    if ($MacToUidUrgency == 1) {
        $btn[0]["name"] = "{disable_emergency_mode}";
        $btn[0]["icon"] = ico_play;
        $btn[0]["js"] = "Loadjs('fw.proxy.emergency.MacToUid.php')";
        echo $tpl->widget_rouge("{proxy_in_MacToUid_emergency_mode}", "{emergency_mode}", $btn);
        return false;

    }


    $data = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/goshield/proxy/connector/status"));
    $bsini = new Bs_IniHandler();
    $bsini->loadString($data->Info);
    echo $tpl->SERVICE_STATUS($bsini, "GO_SHIELD_CONNECTOR_PROXY", restart_js(), null, $refresh);
    echo "<script>LoadAjaxSilent('go-shield-client-top','$page?top-status=yes');</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}


function top_buttons(){
    $libmem=new lib_memcached();
    $tpl=new template_admin();
    $GoShieldConnectorEmergency  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldConnectorEmergency"));
    $GoShieldConnectorDisableACL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldConnectorDisableACL"));
    $EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
    $Go_Shield_Connector_Version=trim($libmem->getKey("Go-Shield-Connector-Version"));
    VERBOSE("Go-Shield-Connector-Version = $Go_Shield_Connector_Version",__LINE__);
    if(trim($Go_Shield_Connector_Version==null)){
        $Go_Shield_Connector_Version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("GO_SHIELD_CONNECTOR_VERSION");
    }
    $page=CurrentPageName();


    if($GoShieldConnectorEmergency==1) {
        if (strlen($Go_Shield_Connector_Version)>1){
            $Go_Shield_Connector_Version="v$Go_Shield_Connector_Version";
        }

        $TINY_ARRAY["TITLE"]="Proxy {connector} $Go_Shield_Connector_Version";
        $TINY_ARRAY["ICO"]="fad fa-exchange-alt";
        $TINY_ARRAY["EXPL"]="{go_shield_connector_explain}";
        $TINY_ARRAY["BUTTONS"]=null;
        $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
        header("content-type: application/x-javascript");
        echo "$jstiny\n";
        return true;
    }

    $Go_Shield_Connector_Addr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Addr"));
    if(empty($Go_Shield_Connector_Addr)){
        $Go_Shield_Connector_Addr="127.0.0.1";
    }
    $topbuttons=array();

    if($GoShieldConnectorEmergency==0) {

        $topbuttons[]=array("Loadjs('$page?emergency-enable=yes');", "fa fa-bell", "{enable_emergency_mode}");



        if($GoShieldConnectorDisableACL==1){
            $topbuttons[]=array("Loadjs('$page?enable-connector-temp=yes')",
                ico_play,"{GO_SHIELD_CONNECTOR} ({stopped})");
        }else{
            $topbuttons[]=array("Loadjs('$page?enable-connector-temp=no')",
                ico_stop,"{GO_SHIELD_CONNECTOR} ({running})");

        }


        if($EnableUfdbGuard==1) {

            $UfdbGuardDisabledTemp = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardDisabledTemp"));

                if ($UfdbGuardDisabledTemp == 1) {
                    $topbuttons[]=array("Loadjs('$page?enable-ufdbguard-temp=yes')",
                        ico_play,"{web_filtering} ({stopped})");


                } else {
                    $topbuttons[]=array("Loadjs('$page?enable-ufdbguard-temp=no')",
                        ico_stop,"{web_filtering} ({running})");
                }

        }




    }
    if($GoShieldConnectorEmergency==0) {
        if($Go_Shield_Connector_Addr=="127.0.0.1") {
            $jscache = "Loadjs('fw.go.shield.server.php?clean-cache=yes')";
            $topbuttons[]=array($jscache, ico_trash,"{empty_cache}");
        }
    }

    if (strlen($Go_Shield_Connector_Version)>1){
        $Go_Shield_Connector_Version="v$Go_Shield_Connector_Version";
    }

    $TINY_ARRAY["TITLE"]="Proxy {connector} $Go_Shield_Connector_Version";
    $TINY_ARRAY["ICO"]="fad fa-exchange-alt";
    $TINY_ARRAY["EXPL"]="{go_shield_connector_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    header("content-type: application/x-javascript");
    echo "$jstiny\n";
    return true;

}

function Save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(isset($_POST["Go_Shield_Connector_Addr"])) {
        $Go_Shield_Connector_Addr = trim($_POST["Go_Shield_Connector_Addr"]);
        $Go_Shield_Connector_Port = intval($_POST["Go_Shield_Connector_Port"]);
        $fp = fsockopen($Go_Shield_Connector_Addr, $Go_Shield_Connector_Port, $errno, $errstr, 1);
        if (!$fp) {
            echo $tpl->post_error($tpl->javascript_parse_text("$Go_Shield_Connector_Addr:$Go_Shield_Connector_Port {connection_error}") . "<br> $errstr");
            return false;
        }
    }

    $NetCoreSomaxConn=$_POST["NetCoreSomaxConn"];
    $GLOBALS["CLASS_SOCKETS"]->KERNEL_SET("net.core.somaxconn",$NetCoreSomaxConn);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");


    $tpl->SAVE_POSTs();
    $SquidClientParams=base64_encode(serialize($_POST));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GO_SHIELD_CONNECTOR_DAEMONS",$SquidClientParams);
    return true;
}
