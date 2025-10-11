<?php
if(isset($_POST["NONE"])){die();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");
include_once(dirname(__FILE__)."/ressources/class.icon.top.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["KerberosReplayCache"])){ntlm_auth_rcache_save();exit;}
if(isset($_GET["rcache-js"])){ntlm_auth_rcache_js();exit;}
if(isset($_GET["rcache-popup"])){ntlm_auth_rcache_popup();exit;}
if(isset($_GET["dns-perfs-js"])){dns_settings_perfs_js();exit;}
if(isset($_GET["dns-perfs-popup"])){dns_settings_perfs_popup();exit;}
if(isset($_GET["dns-real"])){dns_settings_form_js();exit;}
if(isset($_GET["dns-real-popup"])){dns_settings_form();exit;}
if(isset($_GET["proxy-dns-settings-new"])){dns_settings_info();exit;}
if(isset($_POST["enable_memory_cache"])){PerformanceSave();exit;}
if(isset($_POST["KSRNDns1"])){external_acl_save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table1"])){table1();exit;}
if(isset($_GET["performance"])){performance();exit;}
if(isset($_GET["performance-main"])){performance();exit;}
if(isset($_GET["performance-after"])){performance_save_after();exit;}
if(isset($_POST["NONE"])){die();}
if(isset($_GET["section-proxy-performance"])){performance_section();exit;}
if(isset($_GET["section-identity-js"])){section_identity_js();exit;}
if(isset($_GET["section-limit-js"])){section_limit_js();exit;}
if(isset($_GET["section-identity-popup"])){section_identity_popup();exit;}
if(isset($_GET["section-table1-js"])){table1_js();exit;}
if(isset($_GET["section-table1-popup"])){table1_real();exit;}

if(isset($_GET["section-ftp-js"])){section_ftp_js();exit;}
if(isset($_GET["section-ftp-popup"])){section_ftp_popup();exit;}

if(isset($_GET["section-memory-js"])){section_memory_js();exit;}
if(isset($_GET["section-memory-popup"])){performance_section();exit;}

if(isset($_GET["section-pconnections-js"])){section_pconnections_js();exit;}
if(isset($_GET["section-pconnections-popup"])){section_pconnections_popup();exit;}

if(isset($_GET["SquidNoAccessLogs-js"])){SquidNoAccessLogs_js();exit;}
if(isset($_GET["SquidNoAccessLogs-popup"])){SquidNoAccessLogs_popup();exit;}
if(isset($_POST["SquidNoAccessLogs"])){SquidNoAccessLogs_save();exit;}

if(isset($_POST["SquidClientPersistentConnections"])){section_pconnections_save();exit;}


if(isset($_GET["timeouts-main"])){timeouts();exit;}
if(isset($_GET["timeouts"])){timeouts();exit;}
if(isset($_POST["DisableTCPEn"])){timeouts_save();exit;}
if(isset($_GET["limits"])){limits();exit;}
if(isset($_POST["request_header_max_size"])){timeouts_save();exit;}
if(isset($_GET["ntlm-js"])){ntlm_js();exit;}
if(isset($_GET["ntlm-auth"])){ntlm_auth();exit;}
if(isset($_GET["ntlm-auth-flat"])){ntlm_auth_flat();exit;}
if(isset($_GET["ntlm-auth-popup"])){ntlm_auth_popup();exit;}

if(isset($_GET["dns-main"])){dns_settings();exit;}
if(isset($_GET["dns"])){dns_settings();exit;}
if(isset($_POST["DNSSave"])){dns_settings_save();exit;}
if(isset($_GET["RestApiDNS"])){dns_settings_api();exit;}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_POST["SquidVisibleHostname"])){save();exit;}
if(isset($_POST["SquidLogUsersAgents"])){save();exit;}
if(isset($_POST["ftp_user"])){save();exit;}
if(isset($_POST["PerformanceSave"])){PerformanceSave();exit;}
if(isset($_GET["SafePorts"])){SafePorts();exit;}
if(isset($_GET["SafePorts-main"])){SafePorts();exit;}
if(isset($_GET["Anonymous-Browsing"])){Anonymous_Browsing();exit;}
if(isset($_POST["SquidAnonymousBrowsing"])){SquidAnonymousBrowsing();exit;}

if(isset($_GET["SafePortTable"])){SafePorts_table();exit;}
if(isset($_GET["safeport-add"])){SafePorts_add();exit;}
if(isset($_POST["SafePort"])){SafePorts_save();exit;}
if(isset($_GET["safeport-http"])){SafePorts_switch();exit;}
if(isset($_GET["safeport-del"])){SafePorts_del();exit;}
if(isset($_GET["safe-port-desc"])){SafePorts_desc();exit;}
if(isset($_GET["safe-port-desc-popup"])){SafePorts_desc_popup();exit;}
if(isset($_GET["safeport-row"])){SafePorts_row();exit;}
if(isset($_POST["safe-port-desc"])){SafePorts_desc_save();exit;}
if(isset($_POST["auth_param_basic_children"])){basic_authentication_save();exit;}
if(isset($_POST["DynamicGroupsAclsTTL"])){basic_authentication_save();exit;}

if(isset($_GET["basic-authentication-js"])){basic_authentication_js();exit;}
if(isset($_GET["basic-auth"])){basic_authentication();exit;}
if(isset($_GET["Safe-port-disable"])){SafePorts_disable();exit;}
if(isset($_GET["Safe-port-enable"])){SafePorts_enable();exit;}

if(isset($_GET["squid-syslog-js"])){remote_syslog_js();exit;}
if(isset($_GET["squid-syslog-popup"])){remote_syslog_popup();exit;}
if(isset($_POST["squid-syslog-save"])){remote_syslog_save();exit;}

if(isset($_GET["syslog-perso-js"])){remote_syslog_perso_js();exit;}
if(isset($_GET["squid-syslog-perso-popup"])){remote_syslog_perso_popup();exit;}
if(isset($_POST["UsePersonalFormat"])){remote_syslog_save();exit;}


if(isset($_GET["filedesc-form-submit"])){filedesc_form_submit();exit;}





if(isset($_GET["restart-js"])){restart_js();exit;}
if(isset($_GET["restart-popup"])){restart_popup();exit;}

if(isset($_GET["go-squid-auth-status"])){go_squid_auth_status();exit;}
if(isset($_GET["clean-cache"])){clean_plugin_cache();exit;}

page();

function table1_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog("{general_settings}","$page?section-table1-popup=yes");
    return true;
}
function section_ftp_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->js_dialog("FTP","$page?section-ftp-popup=yes");
    return true;
}
function section_memory_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->js_dialog("{central_memory}","$page?section-memory-popup=yes");
    return true;
}


function section_identity_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog("{general_settings}","$page?section-identity-popup=yes");
    return true;
}
function section_limit_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog("{limits}","$page?limits=yes");
    return true;
}
function remote_syslog_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("Syslog","$page?squid-syslog-popup=yes",950);

}
function remote_syslog_perso_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
   return  $tpl->js_dialog2("Syslog","$page?squid-syslog-perso-popup=yes",950);

}
function filedesc_form_submit():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_confirm_execute("{you_need_to_restart_proxy_service}","NONE","NONE","Loadjs('$page?restart-js=yes')");
    return true;
}

function restart_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{restart_service} {APP_SQUID} {fast}","$page?restart-popup=yes",500);
    return true;
}
function restart_popup(){
    $tpl=new template_admin();
    $t=time();

    $js_restart=$tpl->framework_buildjs("/proxy/restart/single","squid.quick.rprogress",
        "squid.quick.rprogress.log","restart-$t");

    $html[]="<div id='restart-$t'></div>";
    $html[]="<script>$js_restart</script>";
    echo $tpl->_ENGINE_parse_body($html);
}





function external_acl_save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
}


function remote_syslog_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSyslogAdd"));
    foreach ($_POST as $key=>$val){
        $array[$key]=$val;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidSyslogAdd",base64_encode(serialize($array)));
    $sock=new sockets();
    $sock->REST_API("/proxy/logging/remotesyslog");

}
function remote_syslog_perso_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSyslogAdd"));
    $UsePersonalFormat=intval($array["UsePersonalFormat"]);
    if(trim($array["PERSO_EVENT"])==null){$array["PERSO_EVENT"]="%>eui %>a %[ui %[un %tl %rm %ru HTTP/%rv %>Hs %<st %Ss:%Sh %{User-Agent}>h %{X-Forwarded-For}>h %<A %>A %tr %mt";}

    $form[] = $tpl->field_checkbox("UsePersonalFormat","{personalized_events}",$UsePersonalFormat,true);
    $form[] = $tpl->field_textareacode("PERSO_EVENT", "{pattern}: {personalized_events}", $array["PERSO_EVENT"]);

    $html[]=$tpl->form_outside("",$form,null,"{apply}",
        "LoadAjax('proxy-general-table','$page?table1=yes');dialogInstance2.close();","AsSquidAdministrator",true);
    echo $tpl->_ENGINE_parse_body($html);

}
function remote_syslog_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSyslogAdd"));
    $RemoteSyslogAddr = $array["SERVER"];
    $form[] = $tpl->field_hidden("squid-syslog-save", "yes");
    $form[] = $tpl->field_checkbox("ENABLE", "{enabled}", $array["ENABLE"], "GoogleSafeRemoteUseTCPPort,RemoteSyslogAddr,RemoteSyslogPort");
    $form[] = $tpl->field_text("SERVER", "{remote_syslog_server}", "$RemoteSyslogAddr");
    if (intval($array["RemoteSyslogPort"]) == 0) {
           $array["RemoteSyslogPort"] = 514;
        }
        $form[] = $tpl->field_checkbox("UseTCPPort", "{useTCPPort}", intval($array["UseTCPPort"]));
        $form[] = $tpl->field_numeric("RemoteSyslogPort", "{listen_port}", $array["RemoteSyslogPort"], false);
        $form[]=$tpl->field_button("{personalized_events}","{settings}","Loadjs('$page?syslog-perso-js=yes')");


       $tpl->form_add_button("{logs_sink}","Loadjs('fw.proxy.rotate.php?main-logsink-js=yes')");

    $html[]="<div id='proxy-syslog'></div>";
    $html[]=$tpl->form_outside("",$form,null,"{apply}",
        "LoadAjax('proxy-general-table','$page?table1=yes');dialogInstance1.close();","AsSquidAdministrator",true);
    echo $tpl->_ENGINE_parse_body($html);



}


function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();


    $html=$tpl->page_header("{your_proxy} {general_settings}",
        "fas fa-cogs","{your_proxy_general_settings_text}","$page?tabs=yes","proxy-parameters","progress-squidgene-restart");




    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);

}

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));

    $array["{general_settings}"]="$page?table=yes";
    $array["{timeouts}"]="$page?timeouts=yes";

    if($EnableKerbAuth==1){
        $array["{http_authentication}"]="$page?basic-auth=yes";
    }
    $array["{active_directory}"]="$page?ntlm-auth=yes";
    $array["{remote_ports}"]="$page?SafePorts=yes";
    $array["{cloud_mode}"]="fw.proxy.general.cloud.php";

    echo $tpl->tabs_default($array);
}

function limits():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $security="AsSquidAdministrator";

    $request_body_max_size=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("request_body_max_size"));
    $request_header_max_size=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("request_header_max_size"));
    $reply_header_max_size=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("reply_header_max_size"));
    $reply_body_max_size=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("reply_body_max_size"));
    $client_request_buffer_max_size=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("client_request_buffer_max_size"));

    if($request_header_max_size==0){$request_header_max_size=128;}
    if($reply_header_max_size==0){$reply_header_max_size=128;}
    if($client_request_buffer_max_size==0){$client_request_buffer_max_size=512;}



    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    $ARRAY["CMD"]="squid2.php?global-timeouts-center=yes";
    $ARRAY["TITLE"]="{GLOBAL_ACCESS_CENTER} {limits}";

    $prgress=base64_encode(serialize($ARRAY));
    $jsafter="BootstrapDialog1.close();LoadAjax('proxy-general-table','$page?table1=yes');Loadjs('fw.progress.php?content=$prgress&mainid=progress-squidgene-restart')";



    $form[]=$tpl->field_numeric("request_header_max_size","{request_header_max_size} (KB)",$request_header_max_size,"{request_header_max_size_text}");

    $form[]=$tpl->field_numeric("reply_header_max_size","{reply_header_max_size} (KB)",$reply_header_max_size,null);

    $form[]=$tpl->field_numeric("client_request_buffer_max_size","{client_request_buffer_max_size} (KB)",$client_request_buffer_max_size,"{client_request_buffer_max_size_text}");

    $form[]=$tpl->field_numeric("request_body_max_size","{request_body_max_size} (KB)",$request_body_max_size,"{request_body_max_size_text}");

    $form[]=$tpl->field_numeric("reply_body_max_size","{reply_body_max_size} (KB)",$reply_body_max_size,"{reply_body_max_size_text}");

    $html[]=$tpl->form_outside("{limits}", @implode("\n", $form),null,"{apply}",$jsafter,$security,false);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function timeouts(){

    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=True;

    $DisableTCPEn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableTCPEn"));
    $DisableTCPWindowScaling=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableTCPWindowScaling"));
    $SquidUploadTimeouts=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUploadTimeouts"));
    $SquidConnectRetries=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidConnectRetries"));



    $forward_max_tries=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("forward_max_tries"));
    if($forward_max_tries==0){$forward_max_tries=30;}
    $SquidReadTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidReadTimeout"));
    if($SquidReadTimeout==0){$SquidReadTimeout=900;}

    $SquidForwardTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidForwardTimeout"));
    if($SquidForwardTimeout==0){$SquidForwardTimeout=240;}

    $SquidClientLifetime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientLifetime"));
    if($SquidClientLifetime==0){$SquidClientLifetime=86400;}

    $SquidShutdownLifetime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidShutdownLifetime"));
    if($SquidShutdownLifetime<10){$SquidShutdownLifetime=10;}

    $SquidDeadPeerTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDeadPeerTimeout"));
    if($SquidDeadPeerTimeout==0){$SquidDeadPeerTimeout=10;}

    $dns_timeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("dns_timeout"));
    if($dns_timeout==0){$dns_timeout=5;}

    $SquidConnectTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidConnectTimeout"));
    if($SquidConnectTimeout==0){$SquidConnectTimeout=120;}

    $SquidPeerConnectTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPeerConnectTimeout"));
    if($SquidPeerConnectTimeout==0){$SquidPeerConnectTimeout=60;}

    $Upload_timedout[0]="{default}";
    $Upload_timedout[10]="10 {minutes}";
    $Upload_timedout[15]="15 {minutes}";
    $Upload_timedout[20]="20 {minutes}";
    $Upload_timedout[30]="30 {minutes}";
    $Upload_timedout[60]="1 {hour}";
    $Upload_timedout[120]="2 {hours}";
    $Upload_timedout[180]="3 {hours}";
    $Upload_timedout[240]="4 {hours}";



    for($i=0;$i<11;$i++){
        $SquidConnectRetriesZ[$i]=$i;
    }

    $SquidConnectRetriesZ[0]="{nottoretry}";

    $form[]=$tpl->field_checkbox("DisableTCPEn","{DisableTCPEn}",$DisableTCPEn,false,"{DisableTCPEn_explain}");
    $form[]=$tpl->field_checkbox("DisableTCPWindowScaling","{DisableTCPWindowScaling}",$DisableTCPWindowScaling,false,"{DisableTCPWindowScaling_explain}");
    $form[]=$tpl->field_array_hash($Upload_timedout, "SquidUploadTimeouts", "{uploads_timeout}", $SquidUploadTimeouts);
    $form[]=$tpl->field_array_hash($SquidConnectRetriesZ, "SquidConnectRetries", "{connect_retries}", $SquidConnectRetries,false,"{connect_retries_text}");
    $form[]=$tpl->field_numeric("forward_max_tries","{forward_max_tries} ({attempts})",$forward_max_tries,"{forward_max_tries_text}");
    $form[]=$tpl->field_numeric("SquidForwardTimeout","{forward_timeout} ({seconds})",$SquidForwardTimeout,"{forward_timeout_text}");
    $form[]=$tpl->field_numeric("SquidClientLifetime","{client_lifetime} ({seconds})",$SquidClientLifetime,"{client_lifetime_text}");
    $form[]=$tpl->field_numeric("SquidShutdownLifetime","{shutdown_lifetime} ({seconds})",$SquidShutdownLifetime,"{shutdown_lifetime_text}");
    $form[]=$tpl->field_numeric("SquidReadTimeout","{read_timeout} ({seconds})",$SquidReadTimeout,"{read_timeout_text}");
    $form[]=$tpl->field_numeric("SquidDeadPeerTimeout","{dead_peer_timeout} ({seconds})",$SquidDeadPeerTimeout,"{dead_peer_timeout_text}");
    $form[]=$tpl->field_numeric("dns_timeout","{dns_timeout} ({seconds})",$dns_timeout,"{dns_timeout_text}");
    $form[]=$tpl->field_numeric("SquidConnectTimeout","{connect_timeout} ({seconds})",$SquidConnectTimeout,"{connect_timeout_text}");
    $form[]=$tpl->field_numeric("SquidPeerConnectTimeout","{peer_connect_timeout} ({seconds})",$SquidPeerConnectTimeout,"{peer_connect_timeout_text}");

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    $ARRAY["CMD"]="squid2.php?global-timeouts-center=yes";
    $ARRAY["TITLE"]="{GLOBAL_ACCESS_CENTER} {timeouts}";

    $prgress=base64_encode(serialize($ARRAY));
    $jsafter="Loadjs('fw.progress.php?content=$prgress&mainid=progress-squidgene-restart')";


    if(isset($_GET["timeouts-main"])){
        $html[]="
		<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\">
		<h1 class=ng-binding>{your_proxy} &raquo;&raquo; {timeouts}</h1>
		<p>{your_proxy_general_settings_text}</p></div></div>
		<div class='row'><div id='progress-squidgene-restart'></div>
		<div class='ibox-content'><div id='table-loader-squid-service'>";
    }


    $html[]=$tpl->form_outside("{timeouts}", $form,null,"{apply}",$jsafter,"AsSquidAdministrator");

    if(isset($_GET["timeouts-main"])){$html[]="</div></div>";}
    $html[]="<script>$.address.state('/');$.address.value('/proxy-timeouts');</script>";


    if(isset($_GET["timeouts-main"])){
        $tpl=new template_admin(null,@implode("\n", $html));
        echo $tpl->build_firewall();
        return;
    }

    echo $tpl->_ENGINE_parse_body($html);


}

function HaClusterGBConfig():array{
    $HaClusterGBConfigA=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig");
    if(is_null($HaClusterGBConfigA)){
        $HaClusterGB["HaClusterUseLBAsDNS"]=0;
        return $HaClusterGB;
    }
    $HaClusterGBConfig=unserialize($HaClusterGBConfigA);
    if(!$HaClusterGBConfig){
        $HaClusterGB["HaClusterUseLBAsDNS"]=0;
        return $HaClusterGB;
    }
    if(!is_array($HaClusterGBConfig)){
        $HaClusterGB["HaClusterUseLBAsDNS"]=0;
        return $HaClusterGB;
    }
    if(!isset( $HaClusterGB["HaClusterUseLBAsDNS"])){
        $HaClusterGB["HaClusterUseLBAsDNS"]=0;
    }
    return $HaClusterGBConfig;
}



function dns_settings():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<div id='proxy-dns-settings-new'></div>";
    $html[]="<script>LoadAjax('proxy-dns-settings-new','$page?proxy-dns-settings-new=yes');</script>";

    if(isset($_GET["dns-main"])){
        $html[]="</div></div>";
        $html[]="<script>$.address.state('/');$.address.value('/proxy-dns');</script>";
    }

    if(isset($_GET["tiny"])) {
        $TINY_ARRAY["TITLE"] = "{your_proxy} &raquo;&raquo; {dns_settings}/{cache_parameters}";
        $TINY_ARRAY["ICO"] = "fa fa-gears";
        $TINY_ARRAY["EXPL"] = "{APP_PROXY_DNS_SETTINGS}";
        $TINY_ARRAY["URL"] = null;
        $TINY_ARRAY["BUTTONS"] = null;
        $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";
        $html[] = "<script>";
        $html[] = $jstiny;
        $html[] = "</script>";
    }


    if(isset($_GET["dns-main"])){
        $tpl=new template_admin(null,@implode("\n", $html));
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function dns_settings_info():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/explain/dns"));
    $text=$data->Info;
    $SquidAppendDomain=1;
    $SquidAppendDomainDisabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAppendDomainDisabled"));

    if($SquidAppendDomainDisabled==1){$SquidAppendDomain=0;}
    $SquidEnablePinger=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidEnablePinger"));
    $fqdncache_size=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("fqdncache_size"));
    if($fqdncache_size==0){$fqdncache_size=16384;}

    $ipcache_size=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ipcache_size"));
    if($ipcache_size==0){$ipcache_size=16384;}

    $ipcache_low=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ipcache_low"));
    if($ipcache_low==0){$ipcache_low=98;}

    $ipcache_high=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ipcache_high"));
    if($ipcache_high==0){$ipcache_high=99;}

    $positive_dns_ttl=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("positive_dns_ttl"));
    if($positive_dns_ttl==0){$positive_dns_ttl=12;}

    $negative_dns_ttl=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("negative_dns_ttl"));
    if($negative_dns_ttl==0){$negative_dns_ttl=5;}

    $dns_timeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("dns_timeout"));
    if($dns_timeout==0){$dns_timeout=5;}

    $tpl->table_form_field_js("Loadjs('$page?dns-real=yes')");
    $tpl->table_form_field_info("{used_dns}","<small>$text</small>",ico_server);

    $tpl->table_form_field_js("Loadjs('$page?dns-perfs-js=yes')");
    $tpl->table_form_field_bool("{enable_append_domain}",$SquidAppendDomain,ico_earth);
    $tpl->table_form_field_bool("{enable_pinger_process}",$SquidEnablePinger,ico_proto);
    $tpl->table_form_field_text("{fqdncache_size}","$fqdncache_size {records}",ico_caching);
    $tpl->table_form_field_text("{ipcache_size}","$ipcache_size {records}",ico_caching);

    $tpl->table_form_field_text("{ipcache_low}","$ipcache_low%",ico_caching);
    $tpl->table_form_field_text("{ipcache_high}","$ipcache_high%",ico_caching);

    $tpl->table_form_field_text("{dns_timeout}","$dns_timeout {seconds}",ico_timeout);
    $tpl->table_form_field_text("{positive_dns_ttl}","$positive_dns_ttl {hours}",ico_timeout);
    $tpl->table_form_field_text("{negative_dns_ttl}","$negative_dns_ttl {seconds}",ico_timeout);

    $html[]=$tpl->table_form_compile();
    echo $tpl->_ENGINE_parse_body($html);
    return true;


}

function dns_settings_form_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog4("{dns_settings}","$page?dns-real-popup=yes");
    return true;
}
function dns_settings_perfs_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog4("{cache_parameters}","$page?dns-perfs-popup=yes");
    return true;

}

function dns_settings_form(){
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $security="AsSquidAdministrator";

    $ProxyUseOwnDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyUseOwnDNS"));
    $html[]="<div id='squid-dns-settings'>";



    $SquidNameServer1=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNameServer1");
    $SquidNameServer2=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNameServer2");
    $SquidNameServer3=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNameServer3");
    if(strlen(trim("$SquidNameServer1$SquidNameServer2$SquidNameServer3"))<3){
        $ProxyUseOwnDNS=0;
    }


    $html[]="</div>";
    $form[]=$tpl->field_hidden("DNSSave", "yes");
    $form[]=$tpl->field_checkbox("ProxyUseOwnDNS","{proxy_use_its_own_dns}",$ProxyUseOwnDNS,"SquidNameServer1,SquidNameServer2,SquidNameServer3");
    $form[]=$tpl->field_text("SquidNameServer1","{primary_dns}",$SquidNameServer1);
    $form[]=$tpl->field_text("SquidNameServer2","{secondary_dns}",$SquidNameServer2);
    $form[]=$tpl->field_text("SquidNameServer3","{nameserver} 3",$SquidNameServer3);
    $jsrestart="Loadjs('$page?RestApiDNS=yes');dialogInstance4.close();";

    $html[]=$tpl->form_outside(null,$form,null,"{apply}",
        "LoadAjax('proxy-dns-settings-new','$page?proxy-dns-settings-new=yes');$jsrestart"
        ,$security);


    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));



}
function dns_settings_perfs_popup(){
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $security="AsSquidAdministrator";
    $SquidAppendDomain=1;
    $SquidAppendDomainDisabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAppendDomainDisabled"));
    $SquidIpv6DNSPrio=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidIpv6DNSPrio"));
    if($SquidAppendDomainDisabled==1){$SquidAppendDomain=0;}
    $SquidEnablePinger=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidEnablePinger"));


    $fqdncache_size=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("fqdncache_size"));
    if($fqdncache_size==0){$fqdncache_size=16384;}

    $ipcache_size=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ipcache_size"));
    if($ipcache_size==0){$ipcache_size=16384;}

    $ipcache_low=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ipcache_low"));
    if($ipcache_low==0){$ipcache_low=98;}

    $ipcache_high=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ipcache_high"));
    if($ipcache_high==0){$ipcache_high=99;}

    $positive_dns_ttl=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("positive_dns_ttl"));
    if($positive_dns_ttl==0){$positive_dns_ttl=12;}

    $negative_dns_ttl=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("negative_dns_ttl"));
    if($negative_dns_ttl==0){$negative_dns_ttl=5;}

    $dns_timeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("dns_timeout"));
    if($dns_timeout==0){$dns_timeout=5;}



    for($i=0;$i<101;$i++){$ipcaches[$i]="$i%";}


    $IpV4Prio=1;
    if($SquidIpv6DNSPrio==1){$IpV4Prio=0;}


    $html[]="<div id='squid-dns-settings'>";
    $DoNotUseLocalDNSCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DoNotUseLocalDNSCache"));

    $zDNS=array();
    $f=explode("\n",@file_get_contents("/etc/squid3/dns.conf"));
    foreach ($f as $line){
        if(!preg_match("#^dns_nameservers (.+)#",$line,$re)){continue;}
        $nameservers=explode(" ",$re[1]);
        foreach ($nameservers as $dns){
            $dns=trim($dns);
            if($dns==null){continue;}
            $zDNS[]=$dns;
        }

    }

    if(count($zDNS)>0){
        $html[] = $tpl->div_explain("{proxy_using_the_dns}||{proxy_use_its_own_dns}: <strong>".@implode(",&nbsp;",$zDNS)."</strong>");
    }else {
        if ($DoNotUseLocalDNSCache == 0) {
            $html[] = $tpl->div_explain("{APP_LOCAL_DNSCACHE}||{proxy_use_unbound_text}");

        }
    }

    $html[]="</div>";
    $form[]=$tpl->field_hidden("DNSSave", "yes");
    $form[]=$tpl->field_checkbox("SquidAppendDomain","{enable_append_domain}",$SquidAppendDomain,false,"{squid_enable_append_domain}");
    $form[]=$tpl->field_checkbox("SquidIpv6DNSPrio","{squid_ipv4_dns_prio}",$IpV4Prio,false,"{squid_ipv4_dns_prio_explain}");
    $form[]=$tpl->field_checkbox("SquidEnablePinger","{enable_pinger_process}",$SquidEnablePinger,false,"{enable_pinger_process_text}");


    $form[]=$tpl->field_numeric("fqdncache_size","{fqdncache_size}",$fqdncache_size,"{fqdncache_size_text}");
    $form[]=$tpl->field_numeric("ipcache_size","{ipcache_size}",$ipcache_size,"{ipcache_size_text}");


    $form[]=$tpl->field_array_hash($ipcaches, "ipcache_low", "{ipcache_low}", $ipcache_low,false,"{ipcache_low_text}");
    $form[]=$tpl->field_array_hash($ipcaches, "ipcache_high", "{ipcache_high}", $ipcache_high,false,"{ipcache_high_text}");


    $form[]=$tpl->field_numeric("dns_timeout","{dns_timeout} ({seconds})",$dns_timeout,"{dns_timeout_text}");
    $form[]=$tpl->field_numeric("positive_dns_ttl","{positive_dns_ttl} ({hours})",$positive_dns_ttl,"{positive_dns_ttl_text}");
    $form[]=$tpl->field_numeric("negative_dns_ttl","{negative_dns_ttl} ({seconds})",$negative_dns_ttl,"{negative_dns_ttl_text}");

    $jsrestart="Loadjs('fw.proxy.dns.servers.php?RestApiDNS=yes');document.getElementById('squid-dns-settings').innerHTML='';";



    if(isset($_GET["dns-main"])){
        $html[]="
		<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\">
		<h1 class=ng-binding>{your_proxy} &raquo;&raquo; {dns_settings}</h1>
		<p>{your_proxy_general_settings_text}</p></div></div>
		<div class='row'><div id='progress-squidgene-restart'></div>
		<div class='ibox-content'><div id='table-loader-squid-service'>";
    }

    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",
        "LoadAjax('proxy-dns-settings-new','$page?proxy-dns-settings-new=yes');$jsrestart"
        ,$security);


    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));



}

function timeouts_save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $sock=new sockets();
    $sock->REST_API("/proxy/config/timeouts/reload");
    return admin_tracks_post("Saving Proxy Timeouts settings");

}

function dns_settings_api():bool{
    $page=CurrentPageName();
    $sock=new sockets();
    $sock->REST_API("/proxy/config/dnstuning/reload");
    header("content-type: application/x-javascript");
    echo "LoadAjax('proxy-dns-settings-new','$page?proxy-dns-settings-new=yes');\n";
    return admin_tracks("Configure Proxy DNS settings");
}

function dns_settings_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $sock=new sockets();


    if(isset($_POST["SquidIpv6DNSPrio"])) {
        if ($_POST["SquidIpv6DNSPrio"] == 1) {
            $sock->SET_INFO("SquidIpv6DNSPrio", 0);
        } else {
            $sock->SET_INFO("SquidIpv6DNSPrio", 1);
        }
        unset($_POST["SquidIpv6DNSPrio"]);
    }


    if(isset($_POST["SquidAppendDomain"])) {
        if ($_POST["SquidAppendDomain"] == 1) {
            $sock->SET_INFO("SquidAppendDomainDisabled", 0);
        } else {
            $sock->SET_INFO("SquidAppendDomainDisabled", 1);
        }
        unset($_POST["SquidAppendDomain"]);
    }

    $tpl->SAVE_POSTs();
    return admin_tracks_post("Saving Proxy DNS settings");
}
function ntlm_js():bool{
    $page = currentPageName();
    $tpl        = new template_admin();
    return $tpl->js_dialog1("Active Directory","$page?ntlm-auth-popup=yes",650);
}
function ntlm_auth_rcache_js():bool{
    $page = currentPageName();
    $tpl        = new template_admin();
    return $tpl->js_dialog1("{Kerberos_replay_cache}","$page?rcache-popup=yes",650);
}

function ntlm_auth():bool{
    $page = currentPageName();
    echo "<div id='squid-auth-schemes-progress' style='margin-top:10px'></div>";
    echo "<div id='proxy-general-ntlm-auth'></div>";
    echo "<script>LoadAjaxSilent('proxy-general-ntlm-auth','$page?ntlm-auth-flat=yes');</script>";
    return true;
}
function ntlm_auth_rcache_popup():bool{
    $page = currentPageName();
    $tpl        = new template_admin();
    $KerberosReplayCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerberosReplayCache"));
    $form[]=$tpl->field_checkbox("KerberosReplayCache","{enable}",$KerberosReplayCache,false);
    $security   = "AsSquidAdministrator";

    $js[]="dialogInstance1.close()";
    $js[]=$tpl->framework_buildjs("/proxy/nohup/reconfigure","squid.articarest.nohup","squid.articarest.log","squid-auth-schemes-progress", "");
    $js[]="LoadAjaxSilent('proxy-general-ntlm-auth','$page?ntlm-auth-flat=yes');";

    $myform=$tpl->form_outside(null,$form,"{Kerberos_replay_cache_explain}","{apply}",@implode(";",$js),$security);
    echo $tpl->_ENGINE_parse_body($myform);
    return true;
}
function ntlm_auth_rcache_save():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerberosReplayCache",$_POST["KerberosReplayCache"]);
    return admin_tracks("Enable Proxy Kerberos Replay Cache to {$_POST["KerberosReplayCache"]}");
}
function ntlm_auth_flat():bool{
    $security   = "AsSquidAdministrator";
    $tpl        = new template_admin();
    $page=CurrentPageName();
    $tpl->CLUSTER_CLI=true;
    $SquidClientParams=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));
    $DynamicGroupsAclsTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DynamicGroupsAclsTTL"));
    $SquidNTLMKeepAlive=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNTLMKeepAlive"));
    $KerberosReplayCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerberosReplayCache"));




    $Go_Shield_External_ACL_Ldap_SearchTimelimit=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_External_ACL_Ldap_SearchTimelimit"));
    $Go_Shield_External_ACL_Ldap_ConnTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_External_ACL_Ldap_ConnTimeout"));
    if ( $Go_Shield_External_ACL_Ldap_SearchTimelimit==0){
        $Go_Shield_External_ACL_Ldap_SearchTimelimit=3;
    }
    if($Go_Shield_External_ACL_Ldap_ConnTimeout==0){
        $Go_Shield_External_ACL_Ldap_ConnTimeout=5;
    }

    if($DynamicGroupsAclsTTL==0){$DynamicGroupsAclsTTL=3600;}
    if(!isset($SquidClientParams["auth_param_ntlm_children"])){$SquidClientParams["auth_param_ntlm_children"]=20;}
    if(!isset($SquidClientParams["auth_param_ntlm_startup"])){$SquidClientParams["auth_param_ntlm_startup"]=5;}
    if(!isset($SquidClientParams["auth_param_ntlm_idle"])){$SquidClientParams["auth_param_ntlm_idle"]=1;}
    if(!isset($SquidClientParams["auth_param_ntlmgroup_children"])){$SquidClientParams["auth_param_ntlmgroup_children"]=15;}
    if(!isset($SquidClientParams["auth_param_ntlmgroup_startup"])){$SquidClientParams["auth_param_ntlmgroup_startup"]=1;}
    if(!isset($SquidClientParams["auth_param_ntlmgroup_idle"])){$SquidClientParams["auth_param_ntlmgroup_idle"]=1;}
    if(!is_numeric($SquidClientParams["auth_param_ntlm_children"])){$SquidClientParams["auth_param_ntlm_children"]=20;}
    if(!is_numeric($SquidClientParams["auth_param_ntlm_startup"])){$SquidClientParams["auth_param_ntlm_startup"]=5;}
    if(!is_numeric($SquidClientParams["auth_param_ntlm_idle"])){$SquidClientParams["auth_param_ntlm_idle"]=1;}

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

    $Ldap_ConnTimeout_ttl[1]=1;
    $Ldap_ConnTimeout_ttl[2]=2;
    $Ldap_ConnTimeout_ttl[3]=3;
    $Ldap_ConnTimeout_ttl[4]=4;
    $Ldap_ConnTimeout_ttl[5]=5;
    $Ldap_ConnTimeout_ttl[6]=6;
    $Ldap_ConnTimeout_ttl[7]=7;
    $Ldap_ConnTimeout_ttl[8]=8;
    $Ldap_ConnTimeout_ttl[9]=9;
    $Ldap_ConnTimeout_ttl[10]=10;
    $Ldap_ConnTimeout_ttl[11]=11;
    $Ldap_ConnTimeout_ttl[12]=12;
    $Ldap_ConnTimeout_ttl[13]=13;
    $Ldap_ConnTimeout_ttl[14]=14;
    $Ldap_ConnTimeout_ttl[15]=15;
    $Ldap_ConnTimeout_ttl[16]=16;
    $Ldap_ConnTimeout_ttl[17]=17;
    $Ldap_ConnTimeout_ttl[18]=18;
    $Ldap_ConnTimeout_ttl[19]=19;
    $Ldap_ConnTimeout_ttl[20]=20;
    $Ldap_ConnTimeout_ttl[21]=21;
    $Ldap_ConnTimeout_ttl[22]=22;
    $Ldap_ConnTimeout_ttl[23]=23;
    $Ldap_ConnTimeout_ttl[24]=24;
    $Ldap_ConnTimeout_ttl[25]=25;
    $Ldap_ConnTimeout_ttl[26]=26;
    $Ldap_ConnTimeout_ttl[27]=27;
    $Ldap_ConnTimeout_ttl[28]=28;
    $Ldap_ConnTimeout_ttl[29]=29;
    $Ldap_ConnTimeout_ttl[30]=30;

    $Ldap_SearchTimeLimit_ttl[1]=1;
    $Ldap_SearchTimeLimit_ttl[2]=2;
    $Ldap_SearchTimeLimit_ttl[3]=3;
    $Ldap_SearchTimeLimit_ttl[4]=4;
    $Ldap_SearchTimeLimit_ttl[5]=5;
    $Ldap_SearchTimeLimit_ttl[6]=6;
    $Ldap_SearchTimeLimit_ttl[7]=7;
    $Ldap_SearchTimeLimit_ttl[8]=8;
    $Ldap_SearchTimeLimit_ttl[9]=9;
    $Ldap_SearchTimeLimit_ttl[10]=10;
    $Ldap_SearchTimeLimit_ttl[11]=11;
    $Ldap_SearchTimeLimit_ttl[12]=12;
    $Ldap_SearchTimeLimit_ttl[13]=13;
    $Ldap_SearchTimeLimit_ttl[14]=14;
    $Ldap_SearchTimeLimit_ttl[15]=15;
    $Ldap_SearchTimeLimit_ttl[16]=16;
    $Ldap_SearchTimeLimit_ttl[17]=17;
    $Ldap_SearchTimeLimit_ttl[18]=18;
    $Ldap_SearchTimeLimit_ttl[19]=19;
    $Ldap_SearchTimeLimit_ttl[20]=20;
    $Ldap_SearchTimeLimit_ttl[21]=21;
    $Ldap_SearchTimeLimit_ttl[22]=22;
    $Ldap_SearchTimeLimit_ttl[23]=23;
    $Ldap_SearchTimeLimit_ttl[24]=24;
    $Ldap_SearchTimeLimit_ttl[25]=25;
    $Ldap_SearchTimeLimit_ttl[26]=26;
    $Ldap_SearchTimeLimit_ttl[27]=27;
    $Ldap_SearchTimeLimit_ttl[28]=28;
    $Ldap_SearchTimeLimit_ttl[29]=29;
    $Ldap_SearchTimeLimit_ttl[30]=30;

    $Go_Shield_External_ACL_Ldap_Debug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_External_ACL_Ldap_Debug"));
    if(!isset($SquidClientParams["Go_Squid_Auth_Concurrency"])){$SquidClientParams["Go_Squid_Auth_Concurrency"]=50;}


    $tpl->table_form_field_js("Loadjs('$page?ntlm-js=yes')",$security);
    $tpl->table_form_section("","{SquidClientParams_text}");
    $tpl->table_form_field_bool("{debug}",$Go_Shield_External_ACL_Ldap_Debug,ico_bug);
    $tpl->table_form_field_bool("{keep_alive}",$SquidNTLMKeepAlive,ico_timeout);

    $tpl->table_form_field_js("Loadjs('$page?rcache-js=yes')",$security);
    $tpl->table_form_field_bool("{Kerberos_replay_cache}",$KerberosReplayCache,ico_database);

    $tpl->table_form_field_js("Loadjs('$page?ntlm-js=yes')",$security);
    $tpl->table_form_field_text("{CHILDREN_MAX}",$SquidClientParams["auth_param_ntlm_children"]. " {processes}",ico_params);
    $tpl->table_form_field_text("{CHILDREN_STARTUP}",$SquidClientParams["auth_param_ntlm_startup"]. " {processes}",ico_params);
    $tpl->table_form_field_text("{CHILDREN_IDLE}",$SquidClientParams["auth_param_ntlm_idle"]. " {processes}",ico_params);
    $tpl->table_form_field_text("{CHILDREN_CONCURRENCY}",$SquidClientParams["Go_Squid_Auth_Concurrency"]. " {processes}",ico_params);

    $tpl->table_form_field_text("{connection} {timeout}","$Go_Shield_External_ACL_Ldap_ConnTimeout {seconds}",ico_timeout);
    $tpl->table_form_field_text("{search} {timeout}","$Ldap_SearchTimeLimit_ttl[$Go_Shield_External_ACL_Ldap_SearchTimelimit] {seconds}",ico_timeout);

    $tpl->table_form_section("{groups_checking}");

    $tpl->table_form_field_text("{QUERY_GROUP_TTL_CACHE}","$ttl_interval[$DynamicGroupsAclsTTL] {seconds}",ico_timeout);
    $tpl->table_form_field_text("{max_processes}",$SquidClientParams["auth_param_ntlmgroup_children"]." {processes}",ico_params);

    $tpl->table_form_field_text("{preload_processes}",$SquidClientParams["auth_param_ntlmgroup_startup"]." {processes}",ico_params);
    $tpl->table_form_field_text("{prepare_processes}",$SquidClientParams["auth_param_ntlmgroup_idle"]." {processes}",ico_params);

    $myform=$tpl->table_form_compile();
    $js=$tpl->RefreshInterval_js("go-squid-auth-status",$page,"go-squid-auth-status=yes");

    $html="<table style='width:100%'>
	<tr>
	<td style='vertical-align:top;width:240px'><div id='go-squid-auth-status' style='margin-top:15px'></div></td>
	<td	style='vertical-align:top;width:90%'>$myform</td>
	</tr>
	</table>
	<script>
	   $js
        //Loadjs('$page?top-status=yes');
	</script>
	";

    echo $tpl->_ENGINE_parse_body($html);
return true;
}

function ntlm_auth_popup(){
    $security   = "AsSquidAdministrator";
    $tpl        = new template_admin();
    $page=CurrentPageName();
    $tpl->CLUSTER_CLI=true;
    $SquidClientParams=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));

    $DynamicGroupsAclsTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DynamicGroupsAclsTTL"));
    $SquidNTLMKeepAlive=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNTLMKeepAlive"));
    $SquidPipelinePrefetch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPipelinePrefetch"));
    $Go_Shield_External_ACL_Ldap_SearchTimelimit=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_External_ACL_Ldap_SearchTimelimit"));
    $Go_Shield_External_ACL_Ldap_ConnTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_External_ACL_Ldap_ConnTimeout"));
    if ( $Go_Shield_External_ACL_Ldap_SearchTimelimit==0){
        $Go_Shield_External_ACL_Ldap_SearchTimelimit=3;
    }
    if($Go_Shield_External_ACL_Ldap_ConnTimeout==0){
        $Go_Shield_External_ACL_Ldap_ConnTimeout=5;
    }

    if($DynamicGroupsAclsTTL==0){$DynamicGroupsAclsTTL=3600;}
    if(!isset($SquidClientParams["auth_param_ntlm_children"])){$SquidClientParams["auth_param_ntlm_children"]=20;}
    if(!isset($SquidClientParams["auth_param_ntlm_startup"])){$SquidClientParams["auth_param_ntlm_startup"]=5;}
    if(!isset($SquidClientParams["auth_param_ntlm_idle"])){$SquidClientParams["auth_param_ntlm_idle"]=1;}
    if(!isset($SquidClientParams["auth_param_ntlmgroup_children"])){$SquidClientParams["auth_param_ntlmgroup_children"]=15;}
    if(!isset($SquidClientParams["auth_param_ntlmgroup_startup"])){$SquidClientParams["auth_param_ntlmgroup_startup"]=1;}
    if(!isset($SquidClientParams["auth_param_ntlmgroup_idle"])){$SquidClientParams["auth_param_ntlmgroup_idle"]=1;}
    if(!is_numeric($SquidClientParams["auth_param_ntlm_children"])){$SquidClientParams["auth_param_ntlm_children"]=20;}
    if(!is_numeric($SquidClientParams["auth_param_ntlm_startup"])){$SquidClientParams["auth_param_ntlm_startup"]=5;}
    if(!is_numeric($SquidClientParams["auth_param_ntlm_idle"])){$SquidClientParams["auth_param_ntlm_idle"]=1;}

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

    $Ldap_ConnTimeout_ttl[1]=1;
    $Ldap_ConnTimeout_ttl[2]=2;
    $Ldap_ConnTimeout_ttl[3]=3;
    $Ldap_ConnTimeout_ttl[4]=4;
    $Ldap_ConnTimeout_ttl[5]=5;
    $Ldap_ConnTimeout_ttl[6]=6;
    $Ldap_ConnTimeout_ttl[7]=7;
    $Ldap_ConnTimeout_ttl[8]=8;
    $Ldap_ConnTimeout_ttl[9]=9;
    $Ldap_ConnTimeout_ttl[10]=10;
    $Ldap_ConnTimeout_ttl[11]=11;
    $Ldap_ConnTimeout_ttl[12]=12;
    $Ldap_ConnTimeout_ttl[13]=13;
    $Ldap_ConnTimeout_ttl[14]=14;
    $Ldap_ConnTimeout_ttl[15]=15;
    $Ldap_ConnTimeout_ttl[16]=16;
    $Ldap_ConnTimeout_ttl[17]=17;
    $Ldap_ConnTimeout_ttl[18]=18;
    $Ldap_ConnTimeout_ttl[19]=19;
    $Ldap_ConnTimeout_ttl[20]=20;
    $Ldap_ConnTimeout_ttl[21]=21;
    $Ldap_ConnTimeout_ttl[22]=22;
    $Ldap_ConnTimeout_ttl[23]=23;
    $Ldap_ConnTimeout_ttl[24]=24;
    $Ldap_ConnTimeout_ttl[25]=25;
    $Ldap_ConnTimeout_ttl[26]=26;
    $Ldap_ConnTimeout_ttl[27]=27;
    $Ldap_ConnTimeout_ttl[28]=28;
    $Ldap_ConnTimeout_ttl[29]=29;
    $Ldap_ConnTimeout_ttl[30]=30;

    $Ldap_SearchTimeLimit_ttl[1]=1;
    $Ldap_SearchTimeLimit_ttl[2]=2;
    $Ldap_SearchTimeLimit_ttl[3]=3;
    $Ldap_SearchTimeLimit_ttl[4]=4;
    $Ldap_SearchTimeLimit_ttl[5]=5;
    $Ldap_SearchTimeLimit_ttl[6]=6;
    $Ldap_SearchTimeLimit_ttl[7]=7;
    $Ldap_SearchTimeLimit_ttl[8]=8;
    $Ldap_SearchTimeLimit_ttl[9]=9;
    $Ldap_SearchTimeLimit_ttl[10]=10;
    $Ldap_SearchTimeLimit_ttl[11]=11;
    $Ldap_SearchTimeLimit_ttl[12]=12;
    $Ldap_SearchTimeLimit_ttl[13]=13;
    $Ldap_SearchTimeLimit_ttl[14]=14;
    $Ldap_SearchTimeLimit_ttl[15]=15;
    $Ldap_SearchTimeLimit_ttl[16]=16;
    $Ldap_SearchTimeLimit_ttl[17]=17;
    $Ldap_SearchTimeLimit_ttl[18]=18;
    $Ldap_SearchTimeLimit_ttl[19]=19;
    $Ldap_SearchTimeLimit_ttl[20]=20;
    $Ldap_SearchTimeLimit_ttl[21]=21;
    $Ldap_SearchTimeLimit_ttl[22]=22;
    $Ldap_SearchTimeLimit_ttl[23]=23;
    $Ldap_SearchTimeLimit_ttl[24]=24;
    $Ldap_SearchTimeLimit_ttl[25]=25;
    $Ldap_SearchTimeLimit_ttl[26]=26;
    $Ldap_SearchTimeLimit_ttl[27]=27;
    $Ldap_SearchTimeLimit_ttl[28]=28;
    $Ldap_SearchTimeLimit_ttl[29]=29;
    $Ldap_SearchTimeLimit_ttl[30]=30;

    $Go_Shield_External_ACL_Ldap_Debug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_External_ACL_Ldap_Debug"));
    if(!isset($SquidClientParams["Go_Squid_Auth_Concurrency"])){$SquidClientParams["Go_Squid_Auth_Concurrency"]=50;}
    //if($SquidClientParams["Go_Squid_Auth_Concurrency"]<100){$SquidClientParams["Go_Squid_Auth_Concurrency"]=100;}
    $form[]=$tpl->field_checkbox("SquidNTLMKeepAlive","{keep_alive}",$SquidNTLMKeepAlive,false,"{SquidNTLMKeepAlive_explain}");

    $form[]=$tpl->field_checkbox("Go_Shield_External_ACL_Ldap_Debug","{debug}",$Go_Shield_External_ACL_Ldap_Debug,false);


    $form[]=$tpl->field_array_hash($start_up,"auth_param_ntlm_children","{CHILDREN_MAX}",$SquidClientParams["auth_param_ntlm_children"]);
    $form[]=$tpl->field_array_hash($start_up,"auth_param_ntlm_startup","{CHILDREN_STARTUP}",$SquidClientParams["auth_param_ntlm_startup"]);
    $form[]=$tpl->field_array_hash($start_up,"auth_param_ntlm_idle","{CHILDREN_IDLE}",$SquidClientParams["auth_param_ntlm_idle"]);
    $form[] = $tpl->field_numeric("Go_Squid_Auth_Concurrency", "{CHILDREN_CONCURRENCY}", $SquidClientParams["Go_Squid_Auth_Concurrency"]);
    $form[]=$tpl->field_array_hash($Ldap_ConnTimeout_ttl,"Go_Shield_External_ACL_Ldap_ConnTimeout","{connection} {timeout}",$Go_Shield_External_ACL_Ldap_ConnTimeout);
    $form[]=$tpl->field_array_hash($Ldap_SearchTimeLimit_ttl,"Go_Shield_External_ACL_Ldap_SearchTimelimit","{search} {timeout}",$Go_Shield_External_ACL_Ldap_SearchTimelimit);

    $form[]=$tpl->field_section("{groups_checking}");
    $form[]=$tpl->field_array_hash($ttl_interval,"DynamicGroupsAclsTTL","{QUERY_GROUP_TTL_CACHE}",$DynamicGroupsAclsTTL);
    $form[]=$tpl->field_array_hash($start_up,"auth_param_ntlmgroup_children","{max_processes}",$SquidClientParams["auth_param_ntlmgroup_children"]);
    $form[]=$tpl->field_array_hash($start_up,"auth_param_ntlmgroup_startup","{preload_processes}",$SquidClientParams["auth_param_ntlmgroup_startup"]);
    $form[]=$tpl->field_array_hash($start_up,"auth_param_ntlmgroup_idle","{prepare_processes}",$SquidClientParams["auth_param_ntlmgroup_idle"]);

    $js[]="dialogInstance1.close()";
    $js[]=$tpl->framework_buildjs("/proxy/nohup/reconfigure","squid.articarest.nohup","squid.articarest.log","squid-auth-schemes-progress", "");
    $js[]="LoadAjaxSilent('proxy-general-ntlm-auth','$page?ntlm-auth-flat=yes');";

    $myform=$tpl->form_outside(null,$form,null,"{apply}",@implode(";",$js),$security);



    echo $tpl->_ENGINE_parse_body($myform);
    return true;
}

function clean_plugin_cache():bool{
    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/goshield/proxy/auth/clean");
    return admin_tracks("Clean the cache of Go Proxy Auth plugin");
}

function go_squid_auth_status():bool{
    $tpl            = new template_admin();
    $page=CurrentPageName();
    $libmem=new lib_memcached();
    $c=0;
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ksrn.php?go-squid-auth=yes");
    $EnableExternalACLADAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableExternalACLADAgent"));
    if($EnableExternalACLADAgent==1){
        $bsini = new Bs_IniHandler(PROGRESS_DIR."/go_squid_auth_ad_agent.status");
        $Go_Squid_Auth_Version=trim($libmem->getKey("Go-Squid-AD-Agent-Client-Version"));
        echo $tpl->SERVICE_STATUS($bsini, "GO_SQUID_AUTH_AD_AGENT",null,$Go_Squid_Auth_Version);
    }
    else{
        $Go_Squid_Auth_Version=trim($libmem->getKey("Go-Squid-Auth-Version"));
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/goshield/proxy/auth/status"));
        if(property_exists($json,"Info")) {
            $bsini = new Bs_IniHandler();
            $bsini->loadString($json->Info);
            echo $tpl->SERVICE_STATUS($bsini, "GO_SQUID_AUTH", null, $Go_Squid_Auth_Version);
            $c=$json->Keys;

        }
    }
    if($c>0) {
        $button["ico"] = "fa-solid fa-trash";
        $button["name"] = "{clean}";
        $button["js"] = "Loadjs('$page?clean-cache=yes');";
        echo $tpl->widget_h("yellow", "fa-solid fa-database", $c, "{cached} {items}", $button);
    }
    return true;

}
function basic_authentication_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $restart_id=$_GET["restart-id"];
    return $tpl->js_dialog2("{parameters}","$page?basic-auth=yes&restart-id=$restart_id");
}

function basic_authentication():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $security="AsSquidAdministrator";

    $SquidClientParams=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));

    if(!is_numeric($SquidClientParams["auth_param_basic_children"])){$SquidClientParams["auth_param_basic_children"]=3;}
    if(!is_numeric($SquidClientParams["auth_param_basic_startup"])){$SquidClientParams["auth_param_basic_startup"]=2;}
    if(!is_numeric($SquidClientParams["auth_param_basic_idle"])){$SquidClientParams["auth_param_basic_idle"]=1;}

    if(intval($SquidClientParams["authenticate_cache_garbage_interval"])==0){$SquidClientParams["authenticate_cache_garbage_interval"]=3600;}
    if(intval($SquidClientParams["authenticate_ttl"])==0){$SquidClientParams["authenticate_ttl"]=3600;}
    if(intval($SquidClientParams["authenticate_ip_ttl"])==0){$SquidClientParams["authenticate_ip_ttl"]=1;}


    if(intval($SquidClientParams["auth_param_basic_children"])==0){$SquidClientParams["auth_param_basic_children"]=3;}
    if(intval($SquidClientParams["auth_param_basic_startup"])==0){$SquidClientParams["auth_param_basic_startup"]=2;}
    if(intval($SquidClientParams["auth_param_basic_idle"])==0){$SquidClientParams["auth_param_basic_idle"]=1;}


    if($SquidClientParams["authenticate_ttl"]>$SquidClientParams["authenticate_cache_garbage_interval"]){
        $SquidClientParams["authenticate_cache_garbage_interval"]=$SquidClientParams["authenticate_ttl"];
    }

    if(intval($SquidClientParams["credentialsttl"])==0){$SquidClientParams["credentialsttl"]=7200;}

    $ttl_interval[1]="1 {second}";
    $ttl_interval[10]="10 {seconds}";
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


    $form[]=$tpl->field_section(null,"{SquidClientParams_text}");
    $form[]=$tpl->field_array_hash($start_up,"auth_param_basic_children","{max_processes}",$SquidClientParams["auth_param_basic_children"]);
    $form[]=$tpl->field_array_hash($start_up,"auth_param_basic_startup","{preload_processes}",$SquidClientParams["auth_param_basic_startup"]);
    $form[]=$tpl->field_array_hash($start_up,"auth_param_basic_idle","{prepare_processes}",$SquidClientParams["auth_param_basic_idle"]);
    $form[]=$tpl->field_section("{sessions_cache}");


    $form[]=$tpl->field_array_hash($ttl_interval,"authenticate_cache_garbage_interval","{authenticate_cache_garbage_interval}",$SquidClientParams["authenticate_cache_garbage_interval"],false,"{authenticate_cache_garbage_interval_explain}");

    $form[]=$tpl->field_array_hash($ttl_interval,"authenticate_ttl","{authenticate_ttl_title}",$SquidClientParams["authenticate_ttl"],false,"{authenticate_ttl_explain}");
    $form[]=$tpl->field_array_hash($ttl_interval,"authenticate_ip_ttl","{authenticate_ip_ttl_title}",$SquidClientParams["authenticate_ip_ttl"],false,"{authenticate_ip_ttl_explain}");
    $form[]=$tpl->field_array_hash($ttl_interval,"credentialsttl","{credentialsttl}",$SquidClientParams["credentialsttl"],false,"{credentialsttl_explain}");

    $restart_id="progress-squidgene-restart";
    if(isset($_GET["restart-id"])){
        $restart_id=$_GET["restart-id"];
    }

    $jsrestart=$tpl->framework_buildjs("/proxy/nohup/reconfigure","squid.articarest.nohup","squid.articarest.log","$restart_id", "");

    $html=$tpl->form_outside(null,$form,null,"{apply}",
        "dialogInstance2.close();LoadAjax('auth-table','fw.proxy.members.engines.php?table=yes');
        $jsrestart",$security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function basic_authentication_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $SquidClientParams=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));

    if(isset($_POST["DynamicGroupsAclsTTL"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DynamicGroupsAclsTTL",$_POST["DynamicGroupsAclsTTL"]);
        unset($_POST["DynamicGroupsAclsTTL"]);

    }

    if(isset($_POST["Go_Shield_External_ACL_Ldap_Debug"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("Go_Shield_External_ACL_Ldap_Debug",$_POST["Go_Shield_External_ACL_Ldap_Debug"]);
        unset($_POST["Go_Shield_External_ACL_Ldap_Debug"]);
    }
    if(isset($_POST["Go_Shield_External_ACL_Ldap_SearchTimelimit"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("Go_Shield_External_ACL_Ldap_SearchTimelimit", $_POST["Go_Shield_External_ACL_Ldap_SearchTimelimit"]);
        unset($_POST["Go_Shield_External_ACL_Ldap_SearchTimelimit"]);
    }

    if(isset($_POST["Go_Shield_External_ACL_Ldap_ConnTimeout"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("Go_Shield_External_ACL_Ldap_ConnTimeout", $_POST["Go_Shield_External_ACL_Ldap_ConnTimeout"]);
        unset($_POST["Go_Shield_External_ACL_Ldap_ConnTimeout"]);
    }


    if(isset($_POST["SquidNTLMKeepAlive"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidNTLMKeepAlive",$_POST["SquidNTLMKeepAlive"]);
        unset($_POST["SquidNTLMKeepAlive"]);
    }


    foreach ($_POST as $key=>$value){
        $SquidClientParams[$key]=$value;
    }

    if($SquidClientParams["authenticate_ttl"]>$SquidClientParams["authenticate_cache_garbage_interval"]){
        $SquidClientParams["authenticate_cache_garbage_interval"]=$SquidClientParams["authenticate_ttl"];
    }

    $NewSquidClientParams=base64_encode(serialize($SquidClientParams));
    $sock=new sockets();
    $sock->SET_INFO("SquidClientParams",$NewSquidClientParams);
    return true;
}




function performance():bool{

    if(isset($_GET["fullpage"])){$_GET["main-page"]="yes";page();exit;}
    $page = CurrentPageName();
    echo "<div id='section-proxy-performance'></div>
    <script>LoadAjax('section-proxy-performance','$page?section-proxy-performance=yes')</script>";
    return true;
}
function performance_section(){

    $tpl=new template_admin();
    $page=CurrentPageName();
    $security="AsSquidAdministrator";
    $SquidSMPConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPConfig"));
    $SquidSMPConfig_md5=md5(serialize($SquidSMPConfig));


    $squid_cache_mem=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("squid_cache_mem"));
    $SquidMemoryCacheMode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMemoryCacheMode"));
    $SquidMemoryReplacementPolicy=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMemoryReplacementPolicy"));

    $shared_memory_locking_disable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("shared_memory_locking_disable"));

       if($squid_cache_mem==0){$squid_cache_mem=256;}


    $tpl->CLUSTER_CLI=True;

    $SquidMemoryCacheMode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMemoryCacheMode"));
    $SquidMemoryReplacementPolicy=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMemoryReplacementPolicy"));
    $maximum_object_size_in_memory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("maximum_object_size_in_memory"));


    $memory_cache_mode["always"]="always";
    $memory_cache_mode["disk"]="disk";
    $memory_cache_mode["network"]="network";

    $memory_replacement_policy["lru"]="{cache_lru}";
    $memory_replacement_policy["heap_GDSF"]="{heap_GDSF}";
    $memory_replacement_policy["heap_LFUDA"]="{heap_LFUDA}";
    $memory_replacement_policy["heap_LRU"]="{heap_LRU}";

    if($squid_cache_mem==0){$squid_cache_mem=256;}
    if($SquidMemoryReplacementPolicy==null){$SquidMemoryReplacementPolicy="heap_LFUDA";}
    if($SquidMemoryCacheMode==null){$SquidMemoryCacheMode="always";}
    if($maximum_object_size_in_memory==0){$maximum_object_size_in_memory=512;}
    $SquidDisableMemoryCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableMemoryCache"));
    if($SquidDisableMemoryCache==0){$enable_memory_cache=1;}else{$enable_memory_cache=0;}

    $form[]=$tpl->field_checkbox("enable_memory_cache","{enable_feature}",$enable_memory_cache,"squid_cache_mem,maximum_object_size_in_memory,shared_memory_locking_disable");
    $form[]=$tpl->field_numeric("squid_cache_mem","{central_memory} (MB)",$squid_cache_mem,"{cache_mem_text}");

    $form[]=$tpl->field_array_hash($memory_cache_mode, "SquidMemoryCacheMode", "{memory_cache_mode}", $SquidMemoryCacheMode,true,"{memory_cache_mode_text}");
    $form[]=$tpl->field_array_hash($memory_replacement_policy, "SquidMemoryReplacementPolicy", "{cache_replacement_policy}", $SquidMemoryReplacementPolicy,true,"{cache_replacement_policy_explain}");
    $form[]=$tpl->field_numeric("maximum_object_size_in_memory","{maximum_object_size_in_memory} (KB)",$maximum_object_size_in_memory,"{maximum_object_size_in_memory_text}");
    $form[]=$tpl->field_checkbox("shared_memory_locking_disable","{shared_memory_locking_disable}",$shared_memory_locking_disable,false,"{shared_memory_locking_disable_explain}");




    $jsrestart="Loadjs('$page?performance-after=yes&smp=$SquidSMPConfig_md5');";


    if(isset($_GET["performance-main"])){
        $html[]="
		<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\">
		<h1 class=ng-binding>{your_proxy} &raquo;&raquo; {performance}</h1>
		<p>{your_proxy_general_settings_text}</p></div></div>
		<div class='row'><div id='progress-squidgene-restart'></div>
		<div class='ibox-content'><div id='table-loader-squid-service'>";
    }


    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td style='width:390px;vertical-align: top'><div id='filedesc_status'></div></td>";
    $html[]="<td style='width:99%;vertical-align: top'>";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),"{squid_cache_memory_explain}","{apply}",
            "BootstrapDialog1.close();LoadAjax('proxy-general-table','$page?table1=yes');$jsrestart",$security,true)."</td>";
    $html[]="</tr>";
    $html[]="</table>";

    if(isset($_GET["performance-main"])){$html[]="</div></div>";}
    $html[]="<script>";
    $html[]="$.address.state('/');";
    $html[]="$.address.value('/proxy-performance');";
    $html[]="</script>";

    if(isset($_GET["performance-main"])){
        $tpl=new template_admin(null,@implode("\n", $html));
        echo $tpl->build_firewall();
        return;
    }

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));



}

function table(){
    $page=CurrentPageName();
    echo "<div id='proxy-general-table'></div><script>LoadAjax('proxy-general-table','$page?table1=yes')</script>";

}
function Anonymous_Browsing(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SquidAnonymousBrowsing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAnonymousBrowsing"));

    if($SquidAnonymousBrowsing==0){

        $tpl->js_dialog_confirm_action("<strong>{enable_feature}: {anonymous_browsing}</strong><br><br><hr><p><small>{anonymous_browsing_explain}</small></p>","SquidAnonymousBrowsing",1,"LoadAjax('proxy-general-table','$page?table1=yes')","");

    }else{
        $tpl->js_confirm_delete("{feature} {anonymous_browsing}","SquidAnonymousBrowsing",0,"LoadAjax('proxy-general-table','$page?table1=yes')");
    }

}

function SquidAnonymousBrowsing(){
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidAnonymousBrowsing",$_POST["SquidAnonymousBrowsing"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/anonymous");
}

function section_pconnections_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog("{persistent_connections}","$page?section-pconnections-popup=yes");
    return true;
}
function SquidNoAccessLogs_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
   return  $tpl->js_dialog("{access_events}","$page?SquidNoAccessLogs-popup=yes");

}
function SquidNoAccessLogs_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSquidAdministrator";
    $SquidNoAccessLogs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNoAccessLogs"));
    $form[] = $tpl->field_checkbox("SquidNoAccessLogs","{remove_local_access_events}",$SquidNoAccessLogs);
    $html[]=$tpl->form_outside("", $form,null,"{apply}",
        "LoadAjax('proxy-general-table','$page?table1=yes');dialogInstance2.close();LoadAjaxSilent('top-barr','fw-top-bar.php');",
        $security
    );

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function SquidNoAccessLogs_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidNoAccessLogs",$_POST["SquidNoAccessLogs"]);
    $sock=new sockets();
    $sock->REST_API("/proxy/config/logging");
}

function section_pconnections_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSquidAdministrator";
    $arrow="<i class=\"fa-solid fa-arrow-right-long-to-line\"></i>";

    $SquidClientPersistentConnections=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientPersistentConnections"));

    $client_idle_pconn_timeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("client_idle_pconn_timeout"));
    $server_idle_pconn_timeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("server_idle_pconn_timeout"));
    $detect_broken_pconn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("detect_broken_pconn"));
    $SquidPconnLifetime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPconnLifetime"));

    if($client_idle_pconn_timeout==0){$client_idle_pconn_timeout=120;}
    if($server_idle_pconn_timeout==0){$server_idle_pconn_timeout=30;}
    if($SquidPconnLifetime==0){$SquidPconnLifetime=7200;}



    $SquidServerPersistentConnections=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidServerPersistentConnections"));

    $html[]=$tpl->div_explain("{persistent_connections_explain}");


    $form[]=$tpl->field_section("{clients}&nbsp;$arrow&nbsp;{APP_SQUID}","");
    $form[]=$tpl->field_checkbox("SquidClientPersistentConnections","{client_persistent_connections}",$SquidClientPersistentConnections,false,"{client_persistent_connections_explain}");
    $form[]=$tpl->field_numeric("client_idle_pconn_timeout","{timeout} ({seconds})",$client_idle_pconn_timeout,false);

    $form[]=$tpl->field_section("{APP_SQUID}&nbsp;$arrow&nbsp;{domains}","");
    $form[]=$tpl->field_checkbox("SquidServerPersistentConnections","{server_persistent_connections}",$SquidServerPersistentConnections,false,"{server_persistent_connections_explain}");
    $form[]=$tpl->field_numeric("server_idle_pconn_timeout","{timeout} ({seconds})",$server_idle_pconn_timeout,false);
    $form[]=$tpl->field_checkbox("detect_broken_pconn","{detect_broken_pconn}",$detect_broken_pconn,false);
    $form[]=$tpl->field_numeric("SquidPconnLifetime","{pconn_lifetime} ({seconds})",$SquidPconnLifetime,"{pconn_lifetime_text}");


    $html[]=$tpl->form_outside("", $form,null,"{apply}",section_js_timeout(),$security);

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function section_pconnections_save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $sock=new sockets();
    $sock->REST_API("/proxy/config/timeouts/reload");
    return admin_tracks_post("Saving proxy Persistent connections settings");
}
function section_js_timeout():string{
    $page=CurrentPageName();
    return "BootstrapDialog1.close();LoadAjax('proxy-general-table','$page?table1=yes');";
}

function section_identity_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSquidAdministrator";

    $myhostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
    $SquidAnonymousBrowsing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAnonymousBrowsing"));
    $form[]=$tpl->field_section("{identity}");
    $form[]=$tpl->field_onoff_newlayer("{anonymous_browsing}",$SquidAnonymousBrowsing,"Loadjs('$page?Anonymous-Browsing=yes')","{anonymous_browsing_explain}");
    $form[]=$tpl->field_info("SquidVisibleHostname", "{visible_hostname}", $myhostname,false,"{visible_hostname_text}");

    $SQUIDRESTFulEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDRESTFulEnabled"));

    if($SQUIDRESTFulEnabled==1){
        $SquidRestFulApi=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRestFulApi"));
        $form[]=$tpl->field_text("SquidRestFulApi", "RESTFul, {API_KEY}", $SquidRestFulApi);

    }

    $SquidAddVersion=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAddVersion"));
    $cache_mgr_user=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("cache_mgr_user");
    if($cache_mgr_user==null){
        $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
        $WizardSavedSettings=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings"));
        if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
        if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]="contact@articatech.com";}
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("cache_mgr_user", $LicenseInfos["EMAIL"]);
        $cache_mgr_user=$LicenseInfos["EMAIL"];
    }


    $form[]=$tpl->field_email("cache_mgr_user", "{cache_mgr_user}",$cache_mgr_user,false,"{cache_mgr_user_text}");
    $form[]=$tpl->field_checkbox("SquidAddVersion","{display_servername_version}",$SquidAddVersion,false,"{SquidAddVersion}");


    $jsrestart=$tpl->framework_buildjs("/proxy/acls/identity",
        "squid.identity.progress",
        "squid.identity.log","squid-identity-progress","BootstrapDialog1.close();");
    $html[]="<div id='squid-identity-progress'></div>";

    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",
        "LoadAjax('proxy-general-table','$page?table1=yes');$jsrestart",$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function table1_real():bool{
    $tpl=new template_admin();
    $SquidLogUsersAgents=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLogUsersAgents"));
    $SquidLogInternalNets=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLogInternalNets"));
    $resolveIP2HOST=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolveIP2HOST"));
    $SquidDebug5=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDebug5"));
    $SquidDebugAcls=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDebugAcls"));
    $SquidDebugDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDebugDNS"));
    $forwarded_for=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("forwarded_for"));
    $SquidLogIface=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLogIface"));
    $DisableMinimalSquidStatistics=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMinimalSquidStatistics"));
    $EnableStatsCommunicator=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStatsCommunicator"));
    $security="AsSquidAdministrator";

    $form[]=$tpl->field_section("{events}","");

    if($DisableMinimalSquidStatistics==0){
        $DisableMinimalSquidStatistics=1;
    }

    if($EnableStatsCommunicator==0) {
        $form[] = $tpl->field_checkbox("DisableMinimalSquidStatistics", "{minimal_statistics}", $DisableMinimalSquidStatistics);
    }
    $form[]=$tpl->field_checkbox("SquidDebugDNS","{debug_mode} DNS",$SquidDebugDNS);
    $form[]=$tpl->field_checkbox("SquidDebugAcls","{debug_acls}",$SquidDebugAcls);
    $form[]=$tpl->field_checkbox("SquidDebug5","{debug_mode} {all}",$SquidDebug5);
    $form[]=$tpl->field_checkbox("SquidLogUsersAgents","{UserAgentInLogs}",$SquidLogUsersAgents);
    $form[]=$tpl->field_checkbox("SquidLogIface","{outgoing_interface}",$SquidLogIface);
    $form[]=$tpl->field_checkbox("SquidLogInternalNets","{log_requests_to_internals}",$SquidLogInternalNets);
    $form[]=$tpl->field_checkbox("resolveIP2HOST","{try_resolve_ip_to_hostname}",$resolveIP2HOST,false,"{try_resolve_ip_to_hostname_explain}");


    $arrayParams["on"]="{enabled}";
    $arrayParams["off"]="{disabled}";
    $arrayParams["transparent"]="{pass}";
    $arrayParams["delete"]="{anonymous}";
    $arrayParams["truncate"]="{hide}";
    $form[]=$tpl->field_section("{protocol} X-Forwarded-For");
    $form[]=$tpl->field_array_hash($arrayParams, "forwarded_for", "X-Forwarded-For", $forwarded_for,false,"{x-Forwarded-For_explain}");


    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $squid_balancers=array();
    $results=$q->QUERY_SQL("SELECT ipsrc FROM squid_balancers WHERE enabled=1");
    if(!$q->ok){$squid_balancers[]=$q->mysql_error;}
    foreach ($results as $index=>$ligne){
        $squid_balancers[]=$ligne["ipsrc"];
    }
    $form[]=$tpl->field_none_bt("none","{follow_x_forwarded_for}",@implode(",",$squid_balancers),"{remote_servers}","Loadjs('fw.proxy.general.follow_x_forwarded_for.php')","{squid_balancersHapxy_explain}");

    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}
function section_js_form():string{
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.general.config.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.general.config.log";
    $ARRAY["CMD"]="squid2.php?build-general-config=yes";
    $ARRAY["TITLE"]="";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-squidgene-restart')";
    return "BootstrapDialog1.close();LoadAjax('proxy-general-table','$page?table1=yes');$jsrestart";
}
function section_ftp_popup(){
    $tpl=new template_admin();
    $FTP_PARAMS=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidFTPParams"));
    if($FTP_PARAMS["ftp_passive"]==null){$FTP_PARAMS["ftp_passive"]=1;}
    if($FTP_PARAMS["ftp_passive"]=='yes'){$FTP_PARAMS["ftp_passive"]=1;}
    if($FTP_PARAMS["ftp_passive"]=='no'){$FTP_PARAMS["ftp_passive"]=0;}

    if($FTP_PARAMS["ftp_sanitycheck"]==null){$FTP_PARAMS["ftp_sanitycheck"]=1;}
    if($FTP_PARAMS["ftp_sanitycheck"]=='yes'){$FTP_PARAMS["ftp_sanitycheck"]=1;}
    if($FTP_PARAMS["ftp_sanitycheck"]=='no'){$FTP_PARAMS["ftp_sanitycheck"]="0";}

    if($FTP_PARAMS["ftp_epsv"]==null){$FTP_PARAMS["ftp_epsv"]="1";}
    if($FTP_PARAMS["ftp_epsv"]=='yes'){$FTP_PARAMS["ftp_epsv"]="1";}
    if($FTP_PARAMS["ftp_epsv"]=='no'){$FTP_PARAMS["ftp_epsv"]="0";}

    if(!isset($FTP_PARAMS["ftp_epsv_all"])){$FTP_PARAMS["ftp_epsv_all"]="0";}
    if($FTP_PARAMS["ftp_epsv_all"]==null){$FTP_PARAMS["ftp_epsv_all"]="0";}
    if($FTP_PARAMS["ftp_epsv_all"]=='yes'){$FTP_PARAMS["ftp_epsv_all"]="1";}
    if($FTP_PARAMS["ftp_epsv_all"]=='no'){$FTP_PARAMS["ftp_epsv_all"]="0";}

    if(!isset($FTP_PARAMS["ftp_telnet_protocol"])){$FTP_PARAMS["ftp_telnet_protocol"]="1";}
    if($FTP_PARAMS["ftp_telnet_protocol"]==null){$FTP_PARAMS["ftp_telnet_protocol"]="1";}
    if($FTP_PARAMS["ftp_telnet_protocol"]=='yes'){$FTP_PARAMS["ftp_telnet_protocol"]="1";}
    if($FTP_PARAMS["ftp_telnet_protocol"]=='no'){$FTP_PARAMS["ftp_telnet_protocol"]="0";}


    $form[]=$tpl->Field_text("ftp_user","{ftp_user}",$FTP_PARAMS["ftp_user"],false,"{squid_ftp_user_explain}");
    $form[]=$tpl->field_checkbox("ftp_passive","{ftp_passive}",$FTP_PARAMS["ftp_passive"],false,"{ftp_passive_explain}");

    $form[]=$tpl->field_checkbox("ftp_sanitycheck","{ftp_sanitycheck}",$FTP_PARAMS["ftp_sanitycheck"],false,"{ftp_sanitycheck_explain}");
    $form[]=$tpl->field_checkbox("ftp_epsv","{ftp_epsv}",$FTP_PARAMS["ftp_epsv"],false,"{ftp_epsv_explain}");
    $form[]=$tpl->field_checkbox("ftp_epsv_all","{ftp_epsv_all}",$FTP_PARAMS["ftp_epsv_all"],false,"{ftp_epsv_all_explain}");
    $form[]=$tpl->field_checkbox("ftp_telnet_protocol","{ftp_telnet_protocol}",$FTP_PARAMS["ftp_telnet_protocol"],false,"{ftp_telnet_protocol_explain}");

    $security="AsSquidAdministrator";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function table1():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $SquidLogUsersAgents=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLogUsersAgents"));
    $SquidLogInternalNets=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLogInternalNets"));
    $SquidLogIface=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLogIface"));

    $resolveIP2HOST=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolveIP2HOST"));
    $SquidAnonymousBrowsing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAnonymousBrowsing"));
    $forwarded_for=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("forwarded_for"));
    if($forwarded_for==null){$forwarded_for="on";}


    $SquidAddVersion=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAddVersion"));
    $cache_mgr_user=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("cache_mgr_user");
    if($cache_mgr_user==null){
        $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
        $WizardSavedSettings=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings"));
        if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
        if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]="contact@articatech.com";}
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("cache_mgr_user", $LicenseInfos["EMAIL"]);
        $cache_mgr_user=$LicenseInfos["EMAIL"];
    }

    $uuid=base64_decode($GLOBALS["CLASS_SOCKETS"]->getFrameWork("cmd.php?system-unique-id=yes"));
    $myhostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
    $SquidDebug5=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDebug5"));
    $SquidDebugAcls=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDebugAcls"));

    $tpl->table_form_field_js("Loadjs('$page?section-identity-js=yes')");

    $tpl->table_form_field_bool("{anonymous_browsing}",$SquidAnonymousBrowsing,ico_anonymous);
    $tpl->table_form_field_bool("{display_servername_version}",$SquidAddVersion,ico_version);
    $tpl->table_form_field_text("{visible_hostname}",$myhostname,ico_server);
    $tpl->table_form_field_text("{unique_hostname}",$uuid,ico_server);
    $tpl->table_form_field_text("{cache_mgr_user}",$cache_mgr_user,ico_message);


    $tpl->table_form_field_js("Loadjs('$page?section-table1-js=yes')");

    $SQUIDRESTFulEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDRESTFulEnabled"));
    if($SQUIDRESTFulEnabled==1){
        $SquidRestFulApi=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesRESTFulAPIKey"));
        $tpl->table_form_field_text("{API_KEY}",$SquidRestFulApi,ico_keys);

    }
    $squid_cache_mem=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("squid_cache_mem"));
    $SquidMemoryCacheMode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMemoryCacheMode"));
    $SquidMemoryReplacementPolicy=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMemoryReplacementPolicy"));
    $SquidReadAheadGap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidReadAheadGap"));

    if($SquidMemoryReplacementPolicy==null){$SquidMemoryReplacementPolicy="lru";}
    if($SquidReadAheadGap==0){$SquidReadAheadGap=1024;}
    if($squid_cache_mem==0){$squid_cache_mem=256;}

    if($SquidMemoryCacheMode==null){$SquidMemoryCacheMode="always";}
    $tpl->CLUSTER_CLI=True;



    $maximum_object_size_in_memory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("maximum_object_size_in_memory"));


    $memory_cache_mode["always"]="always";
    $memory_cache_mode["disk"]="disk";
    $memory_cache_mode["network"]="network";

    $memory_replacement_policy["lru"]="{cache_lru}";
    $memory_replacement_policy["heap_GDSF"]="{heap_GDSF}";
    $memory_replacement_policy["heap_LFUDA"]="{heap_LFUDA}";
    $memory_replacement_policy["heap_LRU"]="{heap_LRU}";

    if($squid_cache_mem==0){$squid_cache_mem=256;}


    if($maximum_object_size_in_memory==0){$maximum_object_size_in_memory=512;}
    $SquidDisableMemoryCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableMemoryCache"));
    if($SquidDisableMemoryCache==0){$enable_memory_cache=1;}else{$enable_memory_cache=0;}

    $tpl->table_form_field_js("Loadjs('$page?section-memory-js=yes')");

    if($enable_memory_cache==1){
        $SquidMemoryCacheMode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMemoryCacheMode"));
        if($SquidMemoryCacheMode==null){$SquidMemoryCacheMode="always";}
        $SquidMemoryReplacementPolicy=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMemoryReplacementPolicy"));
        if($SquidMemoryReplacementPolicy==null){$SquidMemoryReplacementPolicy="heap_LFUDA";}

        $ttmem[]=$squid_cache_mem."MB";
        $ttmem[]=$memory_cache_mode[$SquidMemoryCacheMode]."/".$memory_replacement_policy[$SquidMemoryReplacementPolicy];
        $ttmem[]="MAX {$maximum_object_size_in_memory}KB";
        $tpl->table_form_field_text("{central_memory}",@implode(" ",$ttmem),ico_memory);
        
    }else{
        $tpl->table_form_field_bool("{central_memory}",0,ico_memory);
    }


    $request_body_max_size=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("request_body_max_size"));
    $request_header_max_size=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("request_header_max_size"));
    $reply_header_max_size=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("reply_header_max_size"));
    $reply_body_max_size=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("reply_body_max_size"));
    $client_request_buffer_max_size=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("client_request_buffer_max_size"));

    if($request_header_max_size==0){$request_header_max_size=128;}
    if($reply_header_max_size==0){$reply_header_max_size=128;}
    if($client_request_buffer_max_size==0){$client_request_buffer_max_size=512;}
    $tpl->table_form_field_js("Loadjs('$page?section-limit-js=yes')");
    $tpl->table_form_section("{limits}");
    $tpl->table_form_field_text("{http_headers}","{download} {$request_header_max_size}KB/ {upload} {$reply_header_max_size}KB {buffer} {$client_request_buffer_max_size}KB",ico_timeout);
    if($request_body_max_size==0){
        $request_body_max_size="{unlimited}";
    }else{
        $request_body_max_size="{$request_body_max_size}KB";
    }
    if($reply_body_max_size==0){$reply_body_max_size="{unlimited}";
    }else{
        $reply_body_max_size="{$reply_body_max_size}KB";
    }
    $tpl->table_form_field_text("{Body}","{download} $request_body_max_size / {upload} $reply_body_max_size",ico_timeout);
    $PersistentConnections="{none}";
    $PPCnx=array();
    $arrow="<i class=\"fa-solid fa-arrow-right-long-to-line\"></i>";
    $SquidClientPersistentConnections=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientPersistentConnections"));
    $SquidServerPersistentConnections=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidServerPersistentConnections"));

    $client_idle_pconn_timeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("client_idle_pconn_timeout"));
    $server_idle_pconn_timeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("server_idle_pconn_timeout"));
    $detect_broken_pconn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("detect_broken_pconn"));
    $SquidPconnLifetime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPconnLifetime"));

    if($client_idle_pconn_timeout==0){$client_idle_pconn_timeout=120;}
    if($server_idle_pconn_timeout==0){$server_idle_pconn_timeout=30;}

    if($SquidClientPersistentConnections==1) {
        $PPCnx[] = "{client}&nbsp;$arrow&nbsp;($client_idle_pconn_timeout {seconds})&nbsp;{APP_SQUID}";
    }

    if($SquidServerPersistentConnections==1){
        if($SquidClientPersistentConnections==0) {
            $PPCnx[] = "{APP_SQUID}&nbsp;$arrow&nbsp;($server_idle_pconn_timeout {seconds})&nbsp;{domains}";
        }else{
            $PPCnx[] = "&nbsp;$arrow&nbsp;($server_idle_pconn_timeout {seconds})&nbsp;{domains}";
        }
    }


    if(count($PPCnx)>0){
        $PersistentConnections=@implode("",$PPCnx);
    }
    $uri="https://wiki.articatech.com/proxy-service/tuning/pcon";
    $tpl->table_form_field_js("Loadjs('$page?section-pconnections-js=yes')");

    if(count($PPCnx)==0){
        $tpl->table_form_field_bool("{persistent_connections}",0,ico_timeout,$uri);
    }else{
        $tpl->table_form_field_text("{persistent_connections}",$PersistentConnections,ico_timeout,$uri);
    }


    $tpl->table_form_section("{events}");
    $debg=array();

    $SquidSyslogAdd=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSyslogAdd"));

    if(!isset($SquidSyslogAdd["ENABLE"])){
        $SquidSyslogAdd["ENABLE"]=0;
    }
    if(!isset($SquidSyslogAdd["RemoteSyslogPort"])){
        $SquidSyslogAdd["RemoteSyslogPort"]="";
    }
    if(!isset($SquidSyslogAdd["SERVER"])){
        $SquidSyslogAdd["SERVER"]="";
    }
    if(!isset($SquidSyslogAdd["UseTCPPort"])){
        $SquidSyslogAdd["UseTCPPort"]=0;
    }


    $tpl->table_form_field_js("Loadjs('$page?section-table1-js=yes')","AsSquidAdministrator");
    $EnableStatsCommunicator=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStatsCommunicator"));
    if($EnableStatsCommunicator==0) {
        $DisableMinimalSquidStatistics = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMinimalSquidStatistics"));

        if ($DisableMinimalSquidStatistics == 0) {
            $tpl->table_form_field_bool("{minimal_statistics}", 1, ico_statistics);
        } else {
            $tpl->table_form_field_bool("{minimal_statistics}", 0, ico_statistics);
        }
    }




    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $tpl->table_form_field_js("","AsSquidAdministrator");
        $SquidSyslogAdd["ENABLE"]=0;
    }
    $tpl->table_form_field_js("Loadjs('$page?squid-syslog-js=yes')","AsSquidAdministrator");
    $syslog=array();
    if($SquidSyslogAdd["ENABLE"]==1) {
        $proto = "udp";
        $RemoteSyslogPort = $SquidSyslogAdd["RemoteSyslogPort"];
        $RemoteSyslogAddr = $SquidSyslogAdd["SERVER"];
        $UseTCPPort = $SquidSyslogAdd["UseTCPPort"];
        if ($UseTCPPort == 1) {
            $proto = "tcp";
        }

        $syslog[] ="$proto://$RemoteSyslogAddr:$RemoteSyslogPort";

    }
$LogSinkClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClient"));
    if($LogSinkClient==1) {
        $LogSinkClientPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClientPort"));
        $LogSinkClientServer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinClientServer");
        $LogSinkClientTCP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClientTCP"));
          $proto = "udp";
          if ($LogSinkClientTCP == 1) {
              $proto = "tcp";
          }
          $syslog[] ="$proto://$LogSinkClientServer:$LogSinkClientPort";
    }
    if(count($syslog)>0) {
        $tpl->table_form_field_text("{remote_syslog_server}","<span style='text-transform: initial'>".@implode(", ",$syslog)."</span>",ico_sensor);
    }else{
        $tpl->table_form_field_bool("{remote_syslog_server}",0,ico_sensor);
    }


    $SquidNoAccessLogs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNoAccessLogs"));
    $tpl->table_form_field_js("Loadjs('$page?SquidNoAccessLogs-js=yes')","AsSquidAdministrator");
    if($SquidNoAccessLogs==0){
        $path=urlencode(base64_encode("/var/log/squid/access.log"));
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/file/realpath/$path"));
        $RealPath=$json->Info;
        $tpl->table_form_field_text("{access_events}","<span style='text-transform: initial'>$RealPath</span>",ico_list);
    }else{
        $tpl->table_form_field_text("{access_events}","{none}",ico_list);
    }





    $tpl->table_form_field_js("Loadjs('$page?section-table1-js=yes')","AsSquidAdministrator");


    if($SquidDebugAcls==1){
        $debg[]="<strong class='text-danger'>{acl_in_debug_mode}</strong><br><small>{acl_in_debug_mode_explain}</small>";
    }
    if($SquidDebug5==1){
        $debg[]="{debug_mode} ON";
    }

    if(count($debg)==0){
        $debugtext="{none}";
    }else{
        $debugtext=@implode("<br>",$debg);
    }
    $tpl->table_form_field_text("{debug_mode}",$debugtext,ico_bug);
    $tpl->table_form_field_bool("{UserAgentInLogs}",$SquidLogUsersAgents,ico_ie);
    $tpl->table_form_field_bool("{outgoing_interface}",$SquidLogIface,ico_nic);
    $tpl->table_form_field_bool("{log_requests_to_internals}",$SquidLogInternalNets,ico_network_chart);
    $tpl->table_form_field_bool("{try_resolve_ip_to_hostname}",$resolveIP2HOST,ico_localnet);
    $arrayParams["on"]="{enabled}";
    $arrayParams["off"]="{disabled}";
    $arrayParams["transparent"]="{pass}";
    $arrayParams["delete"]="{anonymous}";
    $arrayParams["truncate"]="{hide}";
    $tpl->table_form_field_text("{protocol} X-Forwarded-For",$arrayParams[$forwarded_for],ico_eye);


    if($forwarded_for=="on"){
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $squid_balancers=array();
        $results=$q->QUERY_SQL("SELECT ipsrc FROM squid_balancers WHERE enabled=1");
        if(!$q->ok){$squid_balancers[]=$q->mysql_error;}
        foreach ($results as $index=>$ligne){
            $squid_balancers[]=$ligne["ipsrc"];
        }
        $list=trim( @implode(", ",$squid_balancers));
        if($list==null){$list="{none}";}
        $tpl->table_form_field_text("{follow_x_forwarded_for}",$list,ico_server);
    }


    $FTP_PARAMS=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidFTPParams"));
    if(!isset($FTP_PARAMS["ftp_passive"])){$FTP_PARAMS["ftp_passive"]=1;}
    if($FTP_PARAMS["ftp_passive"]==null){$FTP_PARAMS["ftp_passive"]=1;}
    if($FTP_PARAMS["ftp_passive"]=='yes'){$FTP_PARAMS["ftp_passive"]=1;}
    if($FTP_PARAMS["ftp_passive"]=='no'){$FTP_PARAMS["ftp_passive"]=0;}

    if(!isset($FTP_PARAMS["ftp_sanitycheck"])){$FTP_PARAMS["ftp_sanitycheck"]=1;}
    if($FTP_PARAMS["ftp_sanitycheck"]==null){$FTP_PARAMS["ftp_sanitycheck"]=1;}
    if($FTP_PARAMS["ftp_sanitycheck"]=='yes'){$FTP_PARAMS["ftp_sanitycheck"]=1;}
    if($FTP_PARAMS["ftp_sanitycheck"]=='no'){$FTP_PARAMS["ftp_sanitycheck"]="0";}

    if(!isset($FTP_PARAMS["ftp_epsv"])){$FTP_PARAMS["ftp_epsv"]=1;}
    if($FTP_PARAMS["ftp_epsv"]==null){$FTP_PARAMS["ftp_epsv"]="1";}
    if($FTP_PARAMS["ftp_epsv"]=='yes'){$FTP_PARAMS["ftp_epsv"]="1";}
    if($FTP_PARAMS["ftp_epsv"]=='no'){$FTP_PARAMS["ftp_epsv"]="0";}

    if(!isset($FTP_PARAMS["ftp_epsv_all"])){$FTP_PARAMS["ftp_epsv_all"]="0";}
    if($FTP_PARAMS["ftp_epsv_all"]==null){$FTP_PARAMS["ftp_epsv_all"]="0";}
    if($FTP_PARAMS["ftp_epsv_all"]=='yes'){$FTP_PARAMS["ftp_epsv_all"]="1";}
    if($FTP_PARAMS["ftp_epsv_all"]=='no'){$FTP_PARAMS["ftp_epsv_all"]="0";}

    if(!isset($FTP_PARAMS["ftp_telnet_protocol"])){$FTP_PARAMS["ftp_telnet_protocol"]="1";}
    if($FTP_PARAMS["ftp_telnet_protocol"]==null){$FTP_PARAMS["ftp_telnet_protocol"]="1";}
    if($FTP_PARAMS["ftp_telnet_protocol"]=='yes'){$FTP_PARAMS["ftp_telnet_protocol"]="1";}
    if($FTP_PARAMS["ftp_telnet_protocol"]=='no'){$FTP_PARAMS["ftp_telnet_protocol"]="0";}

    if($FTP_PARAMS["ftp_user"]<>null) {
        $text[] = "{user}: " . $FTP_PARAMS["ftp_user"];
    }
    if($FTP_PARAMS["ftp_passive"]==1){
        $text[]="{ftp_passive}";
    }
    if($FTP_PARAMS["ftp_sanitycheck"]==1){
        $text[]="{ftp_sanitycheck}";
    }
    if($FTP_PARAMS["ftp_epsv"]==1){
        $text[]="{ftp_epsv}";
    }
    if($FTP_PARAMS["ftp_epsv_all"]==1){
        $text[]="{ftp_epsv_all}";
    }
    if($FTP_PARAMS["ftp_telnet_protocol"]==1){
        $text[]="{ftp_telnet_protocol}";
    }
    $tpl->table_form_field_js("Loadjs('$page?section-ftp-js=yes')");
    $tpl->table_form_field_text("{protocol} FTP",@implode(", ",$text),ico_proto);
    $html[]=$tpl->table_form_compile();
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function performance_restart(){
    $page=CurrentPageName();


}

function performance_save_after(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $RESTART=false;
    $filedesc=$_GET["filedesc"];
    $max_filedesc=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("max_filedesc"));


    if($max_filedesc<>$filedesc){
        $RESTART=true;

    }

    if($RESTART){
        $jsrestart=$tpl->framework_buildjs(
            "/proxy/general/nohup/restart",
            "squid.articarest.nohup","squid.articarest.nohup.log",
            "progress-squidgene-restart",
            "LoadAjax('section-proxy-performance','$page?section-proxy-performance=yes')");

        echo $tpl->js_confirm_execute("{need_restart_reconfigure_proxy}",
            "NONE","Restart Proxy service",$jsrestart);
        die();

    }

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    $ARRAY["CMD"]="squid2.php?global-caches-tuning=yes";
    $ARRAY["TITLE"]="{performance}";
    $ARRAY["AFTER"]="LoadAjax('section-proxy-performance','$page?section-proxy-performance=yes')";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-squidgene-restart')";
    header("content-type: application/x-javascript");
    echo $jsrestart;
}

function PerformanceSave(){
    $sock=new sockets();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    unset($_POST["fs_filemax"]);
    if(isset($_POST["enable_memory_cache"])){
        $_POST["SquidDisableMemoryCache"]=0;
        if($_POST["enable_memory_cache"]==0){$_POST["SquidDisableMemoryCache"]=1;}
        unset($_POST["enable_memory_cache"]);
    }

    $tpl->SAVE_POSTs();



}

function save(){
    $FTP_PARAMS=array();
    $FTPSAVE=false;
    if(isset($_POST["ftp_passive"])) {
        $FTP_PARAMS = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidFTPParams"));
        if ($FTP_PARAMS["ftp_passive"] == null) {
            $FTP_PARAMS["ftp_passive"] = 1;
        }
        if ($FTP_PARAMS["ftp_passive"] == 'yes') {
            $FTP_PARAMS["ftp_passive"] = 1;
        }
        if ($FTP_PARAMS["ftp_passive"] == 'no') {
            $FTP_PARAMS["ftp_passive"] = 0;
        }

        if ($FTP_PARAMS["ftp_sanitycheck"] == null) {
            $FTP_PARAMS["ftp_sanitycheck"] = 1;
        }
        if ($FTP_PARAMS["ftp_sanitycheck"] == 'yes') {
            $FTP_PARAMS["ftp_sanitycheck"] = 1;
        }
        if ($FTP_PARAMS["ftp_sanitycheck"] == 'no') {
            $FTP_PARAMS["ftp_sanitycheck"] = "0";
        }

        if ($FTP_PARAMS["ftp_epsv"] == null) {
            $FTP_PARAMS["ftp_epsv"] = "1";
        }
        if ($FTP_PARAMS["ftp_epsv"] == 'yes') {
            $FTP_PARAMS["ftp_epsv"] = "1";
        }
        if ($FTP_PARAMS["ftp_epsv"] == 'no') {
            $FTP_PARAMS["ftp_epsv"] = "0";
        }

        if (!isset($FTP_PARAMS["ftp_epsv_all"])) {
            $FTP_PARAMS["ftp_epsv_all"] = "0";
        }
        if ($FTP_PARAMS["ftp_epsv_all"] == null) {
            $FTP_PARAMS["ftp_epsv_all"] = "0";
        }
        if ($FTP_PARAMS["ftp_epsv_all"] == 'yes') {
            $FTP_PARAMS["ftp_epsv_all"] = "1";
        }
        if ($FTP_PARAMS["ftp_epsv_all"] == 'no') {
            $FTP_PARAMS["ftp_epsv_all"] = "0";
        }

        if (!isset($FTP_PARAMS["ftp_telnet_protocol"])) {
            $FTP_PARAMS["ftp_telnet_protocol"] = "1";
        }
        if ($FTP_PARAMS["ftp_telnet_protocol"] == null) {
            $FTP_PARAMS["ftp_telnet_protocol"] = "1";
        }
        if ($FTP_PARAMS["ftp_telnet_protocol"] == 'yes') {
            $FTP_PARAMS["ftp_telnet_protocol"] = "1";
        }
        if ($FTP_PARAMS["ftp_telnet_protocol"] == 'no') {
            $FTP_PARAMS["ftp_telnet_protocol"] = "0";
        }
    }

    $sock=new sockets();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $SquidCpuNumber=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCpuNumber"));
    $SquidLogUsersAgents=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLogUsersAgents"));
    if($SquidCpuNumber==0){$SquidCpuNumber=1;}
    $squid=new squidbee();
    $REST_API_LOG=false;

    if(isset($_POST["SquidLogUsersAgents"])) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidLogUsersAgents",$_POST["SquidLogUsersAgents"]);
    }

    if(isset($_POST["SquidLogIface"])) {
        $REST_API_LOG=true;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidLogIface",$_POST["SquidLogIface"]);
        $sock->REST_API("/proxy/config/logging");
    }


    if(isset($_POST["DisableMinimalSquidStatistics"])){
        if(intval($_POST["DisableMinimalSquidStatistics"])==1){
            $_POST["DisableMinimalSquidStatistics"]=0;
        }else{
            $_POST["DisableMinimalSquidStatistics"]=1;
        }

        if(!$REST_API_LOG) {
            $REST_API_LOG = true;
            $sock->REST_API("/proxy/config/logging");
        }
    }

    if(isset($_POST["SquidRestFulApi"])){
        $sock->SET_INFO("SquidRestFulApi", $_POST["SquidRestFulApi"]);
    }

    if(isset($_POST["ldap_auth"])){

        if($_POST["ldap_auth"]==1){
            $squid->LDAP_AUTH=1;
            $squid->LDAP_EXTERNAL_AUTH=0;
        }else{
            $squid->LDAP_AUTH=0;
        }

        unset($_POST["ldap_auth"]);
    }

    if(isset($_POST["forwarded_for"])) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("forwarded_for", $_POST["forwarded_for"]);
        $squid->SaveToLdap();
    }

    if(isset($_POST["SquidCpuNumber"])) {
        if ($_POST["SquidCpuNumber"] > 1) {
            if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
                echo $tpl->_ENGINE_parse_body("<strong>{SquidCpuNumber}:</strong><br>{this_feature_is_disabled_corp_license}");
            }
            $_POST["SquidCpuNumber"] = 1;
        }
    }

    foreach ($_POST as $num=>$val){
        if(isset($FTP_PARAMS[$num])){
            if($FTP_PARAMS[$num]<>$val){$FTP_PARAMS[$num]=$val;$FTPSAVE=true;}
            continue;
        }

        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($num, $val);
    }

    if(isset($_POST["SquidCpuNumber"])) {
        if ($SquidCpuNumber <> $_POST["SquidCpuNumber"]) {
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?global-common-cache=yes");
        } else {
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?global-common-center=yes");
        }
    }

    if(isset($_POST["SquidLogUsersAgents"])) {
        if ($SquidLogUsersAgents <> $_POST["SquidLogUsersAgents"]) {
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?global-logging-center=yes");
            if(!$REST_API_LOG){
                $sock->REST_API("/proxy/config/logging");
            }
        }

    }

    if($FTPSAVE){
        $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(base64_encode(serialize($FTP_PARAMS)), "SquidFTPParams");
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?ftp-params=yes");

    }




}

function SafePorts(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;


    if(isset($_GET["SafePorts-main"])){
        $html[]="
		<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\">
		<h1 class=ng-binding>{your_proxy} &raquo;&raquo; {ports_restrictions}</h1>
		<p>{your_proxy_general_settings_text}</p></div></div>
		<div class='row'><div id='progress-squidgene-restart'></div>
		<div class='ibox-content'><div id='table-loader-squid-service'>";
    }

    $html[]="<div id='SafePortTable'></div>";

    if(isset($_GET["SafePorts-main"])){$html[]="</div></div>";}
    $html[]="<script>$.address.state('/');$.address.value('/proxy-safe-ports');LoadAjaxSilent('SafePortTable','$page?SafePortTable=yes');</script>";

    if(isset($_GET["SafePorts-main"])){
        $tpl=new template_admin(null,@implode("\n", $html));
        echo $tpl->build_firewall();
        return;
    }

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));


}

function SafePorts_disable(){
    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidAllowRemotePorts",1);
    $tpl=new template_admin();
    $jsrestart=$tpl->framework_buildjs("/proxy/http/access/default",
        "squid.access.center.progress",
        "squid.access.center.progress.log",
    "squid-safe-port-progress","LoadAjaxSilent('SafePortTable','$page?SafePortTable=yes')");

    header("content-type: application/x-javascript");
    echo "$jsrestart;";

}
function SafePorts_enable(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SquidDisableAllFilters=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableAllFilters"));
    if($SquidDisableAllFilters==1){
        echo "LoadAjaxSilent('SafePortTable','$page?SafePortTable=yes');";
        return;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidAllowRemotePorts",0);
    $jsrestart=$tpl->framework_buildjs("/proxy/http/access/default",
        "squid.access.center.progress",
        "squid.access.center.progress.log",
        "squid-safe-port-progress","LoadAjaxSilent('SafePortTable','$page?SafePortTable=yes')");

    header("content-type: application/x-javascript");
    echo "$jsrestart;";

}
function SafePorts_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLUSTER_CLI=true;
    $t=time();
    $note=$tpl->_ENGINE_parse_body("{note}");
    $check_ico="<i class='fas fa-check'></i>";
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $SquidAllowRemotePorts=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAllowRemotePorts"));
    $SquidDisableAllFilters=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableAllFilters"));
    if($SquidDisableAllFilters==1){
        $SquidAllowRemotePorts=1;
    }

    $jsrestart=$tpl->framework_buildjs("/proxy/http/access/default",
        "squid.access.center.progress",
        "squid.access.center.progress.log",
        "squid-safe-port-progress","document.getElementById('squid-safe-port-progress').innerHTML=''");


    $btns=array();
    $html[]="<div id='squid-safe-port-progress'></div>";

    if($PowerDNSEnableClusterSlave==0) {
        $btns[] = "<div class=\"btn-group\" data-toggle=\"buttons\">";
        if($SquidAllowRemotePorts==0) {
            $btns[] = "<label class=\"btn btn btn-primary\" 
            OnClick=\"Loadjs('$page?safeport-add=yes');\">
            <i class='fa fa-plus'></i> {new_port} </label>";
        }

        if($SquidAllowRemotePorts==0){
            $btns[] = "<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?Safe-port-disable=yes')\">
                        <i class='fas fa-check-square'></i> {disable_feature} </label>";
        }else{
            $btns[] = "<label class=\"btn btn btn-danger\" OnClick=\"Loadjs('$page?Safe-port-enable=yes')\">
                        <i class='fas fa-square'></i> {enable_feature} </label>";
        }

        $btns[] = "<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_configuration} </label>";
        $btns[] = "</div>";
    }


    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{ports}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$note</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>HTTP</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>SSL</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{enabled}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>Del</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    if($SquidAllowRemotePorts==0) {
        $q = new lib_sqlite("/home/artica/SQLITE/proxy.db");

        $results = $q->QUERY_SQL("SELECT * FROM safeports ORDER by port");

        foreach ($results as $index => $ligne) {
            if ($TRCLASS == "footable-odd") {
                $TRCLASS = null;
            } else {
                $TRCLASS = "footable-odd";
            }
            $PORT=intval($ligne["port"]);
            $md = md5(serialize($ligne));
            $ID = $ligne["ID"];
            $html[] = "<tr class='$TRCLASS' id='$md'>";
            $html[] = "<td width=1%><strong>{$ligne["port"]}</strong></td>";
            $html[] = "<td ><span id='save-port-desc-$ID'>" . texttooltip($ligne["description"], null, "Loadjs('$page?safe-port-desc=$ID')") . "</span></td>";

            $js1    = "Loadjs('$page?safeport-http=" . urlencode($ligne["port"]) . "&key=http')";
            $js2    = "Loadjs('$page?safeport-http=" . urlencode($ligne["port"]) . "&key=ssl')";
            $js3    = "Loadjs('$page?safeport-http=" . urlencode($ligne["port"]) . "&key=enabled')";
            $jsdel  = "Loadjs('$page?safeport-del=$ID&md=$md')";
            $check_http=$tpl->icon_check($ligne["http"], $js1, null, "AsDansGuardianAdministrator");
            $check_ssl=$tpl->icon_check($ligne["ssl"], $js2, null, "AsDansGuardianAdministrator");
            $check_enabled= $tpl->icon_check($ligne["enabled"], $js3, null, "AsDansGuardianAdministrator");
            $DEL=$tpl->icon_delete($jsdel, "AsDansGuardianAdministrator") ;

            if($PORT==443){
                $check_http="&nbsp;";
                $check_ssl=$check_ico;
                $check_enabled=$check_ico;
                $DEL=$tpl->icon_delete(null, "AsDansGuardianAdministrator") ;
            }
            if($PORT==20 OR $PORT==21 OR $PORT==80){
                $check_http=$check_ico;
                $check_ssl="&nbsp;";
                $check_enabled=$check_ico;
                $DEL=$tpl->icon_delete(null, "AsDansGuardianAdministrator") ;
            }

            $html[] = "<td width=1%>$check_http</td>";
            $html[] = "<td width=1%>$check_ssl</td>";
            $html[] = "<td width=1%>$check_enabled</td>";
            $html[] = "<td width=1%>$DEL</td>";
            $html[] = "</tr>";
        }
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='6'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({\"filtering\": {\"enabled\": true},\"sorting\": {\"enabled\": true } } ); });";

    $text_error=null;
    if($SquidAllowRemotePorts==1) {
        $text_error = "<br><span class='text-danger'>{this_feature_is_disabled}</span>";
    }


    $TINY_ARRAY["TITLE"]="{remote_ports}: {ports_restrictions}";
    $TINY_ARRAY["ICO"]="fad fa-plug";
    $TINY_ARRAY["EXPL"]="{safe_ports_explain}$text_error";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]=$jstiny;
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);
}

function SafePorts_switch(){
    $port=$_GET["safeport-http"];
    $key=$_GET["key"];
    header("content-type: application/x-javascript");

    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");

    $ligne=$q->mysqli_fetch_array("SELECT $key FROM safeports WHERE port='$port'");

    $VAL=intval($ligne[$key]);
    if($VAL==1){$VAL=0;}else{$VAL=1;}
    $q->QUERY_SQL("UPDATE safeports SET $key=$VAL WHERE port='$port'");


}
function SafePorts_desc_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLUSTER_CLI=true;
    $ID=$_GET["safe-port-desc-popup"];
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT description FROM safeports WHERE ID='$ID'");

    $form[]=$tpl->field_hidden("safe-port-desc",$ID);
    $form[]=$tpl->field_text("desc","{description}",$ligne["description"]);
    echo $tpl->form_outside("{description}",$form,null,"{apply}","dialogInstance1.close();Loadjs('$page?safeport-row=$ID')","AsDansGuardianAdministrator");

}
function SafePorts_row(){
    $ID=$_GET["safeport-row"];
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM safeports WHERE ID='$ID'");
    $page=CurrentPageName();
    $desc=texttooltip("{$ligne["description"]}",null,"Loadjs('$page?safe-port-desc=$ID')");
    $desc=str_replace('"','\"',$desc);
    $desc=str_replace("\n","\\n",$desc);
    echo "document.getElementById('save-port-desc-$ID').innerHTML=\"$desc\"\n";

}

function SafePorts_desc_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=$_POST["safe-port-desc"];
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $desc=$q->sqlite_escape_string2($_POST["desc"]);
    $q->QUERY_SQL("UPDATE safeports SET description='$desc' WHERE ID='$ID'");
    if(!$q->ok){$tpl=new template_admin();$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);echo "jserror:$q->mysql_error";}
}

function SafePorts_desc(){
    $ID=intval($_GET["safe-port-desc"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{description}","$page?safe-port-desc-popup=$ID",650);

}

function SafePorts_add(){
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $HTTP_ADD_SAFE_PORTS_EXPLAIN=$tpl->javascript_parse_text("{HTTP_ADD_SAFE_PORTS_EXPLAIN}");
    $GIVE_A_NOTE=$tpl->javascript_parse_text("{GIVE_A_NOTE}");
    $t=time();
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    if($PowerDNSEnableClusterSlave==1){
        echo "alert('Cluster Client Enabled');";
        return;

    }


    echo "
			
var xHTTPSafePort$t=function (obj) {
	var results=obj.responseText;
	if (results.length>0){alert(results);}
	LoadAjaxSilent('SafePortTable','$page?SafePortTable=yes');
}	
			
function HTTPSafePort$t(){
	var XHR = new XHRConnection();	
	var explain='';
	var value=prompt('$HTTP_ADD_SAFE_PORTS_EXPLAIN');
	if(!value){return;}
	explain=prompt('$GIVE_A_NOTE','my specific web port...');
	if(value){
		XHR.appendData('SafePort',value);
		XHR.appendData('SafePortExplain',explain);
		XHR.sendAndLoad('$page', 'POST',xHTTPSafePort$t);
	}
}
	
	
	HTTPSafePort$t()";
}

function SafePorts_del(){
    $ID=$_GET["safeport-del"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $q->QUERY_SQL("DELETE FROM safeports WHERE ID='$ID'");
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();";

}

function SafePorts_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $port=$tpl->CLEAN_BAD_CHARMAIL($_POST["SafePort"]);
    $_POST["SafePortExplain"]=$tpl->CLEAN_BAD_CHARSNET($_POST["SafePortExplain"]);
    if(!preg_match("#([0-9\-\s]+)#", $port)){echo "Invalid $port\n";return;}
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $_POST["SafePortExplain"]=$q->sqlite_escape_string2($_POST["SafePortExplain"]);
    $q->QUERY_SQL("INSERT OR IGNORE INTO safeports (port,ssl,http,description,enabled) VALUES ('$port',1,1,'{$_POST["SafePortExplain"]}',1)");
}

