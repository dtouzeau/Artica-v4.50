<?php
// Service Pack 9 2026-10-10
if(isset($_POST["NONE"])){die();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");
include_once(dirname(__FILE__)."/ressources/class.icon.top.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Pentest 2026-11-04 : restart-id etait recopie sans encodage dans la page (XSS).
if(isset($_GET["restart-id"])){$_GET["restart-id"]=preg_replace('/[^A-Za-z0-9_-]/','',(string)$_GET["restart-id"]);}
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
if(isset($_GET["pxg-toggle-js"])){pxg_toggle_js();exit;}
if(isset($_POST["pxg-toggle-caching"])){pxg_toggle_save("caching");exit;}
if(isset($_POST["pxg-toggle-wu"])){pxg_toggle_save("wu");exit;}
if(isset($_POST["pxg-toggle-ftp"])){pxg_toggle_save("ftp");exit;}
if(isset($_GET["EnableParentProxy-js"])){EnableParentProxy_js();exit;}
if(isset($_GET["EnableParentProxy-popup"])){EnableParentProxy_popup();exit;}
if(isset($_POST["EnableParentProxy"])){EnableParentProxy_save();exit;}


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
if(isset($_GET["timeouts-flat"])){timeouts_flat();exit;}
if(isset($_GET["timeouts-js"])){timeouts_js();exit;}
if(isset($_GET["timeouts-popup"])){timeouts_popup();exit;}
if(isset($_POST["proxy-timeouts-group"])){timeouts_save();exit;}
if(isset($_GET["limits"])){limits();exit;}
if(isset($_POST["request_header_max_size"])){limits_save();exit;}
if(isset($_GET["ntlm-js"])){ntlm_js();exit;}
if(isset($_GET["ntlm-auth"])){ntlm_auth();exit;}
if(isset($_GET["ntlm-auth-flat"])){ntlm_auth_flat();exit;}
if(isset($_GET["ntlm-auth-popup"])){ntlm_auth_popup();exit;}

if(isset($_GET["mmc-js"])){mmc_js();exit;}
if(isset($_GET["mmc-popup"])){mmc_popup();exit;}
if(isset($_POST["SquidMicrosoftConnectedCache"])){mmc_save();exit;}

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
if(isset($_GET["sfp-new-js"])){sfp_new_js();exit;}
if(isset($_GET["sfp-new-popup"])){sfp_new_popup();exit;}
if(isset($_POST["sfp-toggle"])){sfp_toggle();exit;}
if(isset($_POST["sfp-del"])){sfp_delete();exit;}
if(isset($_POST["sfp-add"])){sfp_add();exit;}
if(isset($_GET["safe-port-desc"])){SafePorts_desc();exit;}
if(isset($_GET["safe-port-desc-popup"])){SafePorts_desc_popup();exit;}
if(isset($_POST["safe-port-desc"])){SafePorts_desc_save();exit;}
if(isset($_POST["auth_param_basic_children"])){basic_authentication_save();exit;}
if(isset($_POST["DynamicGroupsAclsTTL"])){basic_authentication_save();exit;}

if(isset($_GET["basic-authentication-js"])){basic_authentication_js();exit;}
if(isset($_GET["basic-auth"])){basic_authentication_start();exit;}
if(isset($_GET["basic-auth-popup"])){basic_authentication_popup();exit;}
if(isset($_GET["basic-auth-flat"])){basic_authentication_flat();exit;}


if(isset($_GET["Safe-port-disable"])){SafePorts_disable();exit;}
if(isset($_GET["Safe-port-enable"])){SafePorts_enable();exit;}

if(isset($_GET["squid-syslog-js"])){remote_syslog_js();exit;}
if(isset($_GET["squid-syslog-popup"])){remote_syslog_popup();exit;}
if(isset($_POST["squid-syslog-save"])){remote_syslog_save();exit;}

if(isset($_GET["syslog-perso-js"])){remote_syslog_perso_js();exit;}
if(isset($_GET["squid-syslog-perso-popup"])){remote_syslog_perso_popup();exit;}
if(isset($_POST["UsePersonalFormat"])){remote_syslog_save();exit;}
if(isset($_GET["wazuh-js"])){wazuh_js();exit;}
if(isset($_GET["wazuh-popup"])){wazuh_popup();exit;}
if(isset($_POST["wazuh-save"])){wazuh_save();exit;}


if(isset($_GET["filedesc-form-submit"])){filedesc_form_submit();exit;}





if(isset($_GET["restart-js"])){restart_js();exit;}
if(isset($_GET["restart-popup"])){restart_popup();exit;}

if(isset($_GET["pad-status"])){pad_status();exit;}
if(isset($_GET["pad-clean-js"])){pad_clean_js();exit;}
if(isset($_POST["pad-clean-cache"])){pad_clean_save();exit;}

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



function wazuh_settings():array{
    $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazuhLogFileDaemon"));
    if(!is_array($array)){$array=array();}
    if(!isset($array["ENABLE"])){$array["ENABLE"]=0;}
    if(!isset($array["FILTER"])){$array["FILTER"]="security";}
    if(!isset($array["MAX_SIZE_MB"])){$array["MAX_SIZE_MB"]=100;}
    return $array;
}
function wazuh_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("Wazuh","$page?wazuh-popup=yes",650);
}
function wazuh_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $array=wazuh_settings();
    $filters["security"]="{security_events}";
    $filters["all"]="{all_events}";
    $form[]=$tpl->field_hidden("wazuh-save","yes");
    $form[]=$tpl->field_checkbox("ENABLE","{enabled}",intval($array["ENABLE"]),"FILTER,MAX_SIZE_MB");
    $form[]=$tpl->field_array_hash($filters,"FILTER","{filter}",$array["FILTER"],true,"{wazuh_filter_explain}");
    $form[]=$tpl->field_numeric("MAX_SIZE_MB","{wazuh_max_file_size}",intval($array["MAX_SIZE_MB"]),"{wazuh_max_size_explain}");
    $form[]=$tpl->field_info("WAZUH_PATH","{path}","/var/log/squid/wazuh/access.json");
    $html[]=$tpl->div_explain("Wazuh||{wazuh_events_explain}");
    $html[]=$tpl->form_outside("",$form,"","{apply}",
        "LoadAjax('proxy-general-table','$page?table1=yes');dialogInstance1.close();","AsSquidAdministrator",true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function wazuh_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $array=wazuh_settings();
    $array["ENABLE"]=intval($_POST["ENABLE"]);
    $array["FILTER"]=$_POST["FILTER"]=="all"?"all":"security";
    $size=intval($_POST["MAX_SIZE_MB"]);
    if($size<0){$size=0;}
    if($size>10240){$size=10240;}
    $array["MAX_SIZE_MB"]=$size;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WazuhLogFileDaemon",base64_encode(serialize($array)));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/logging/wazuh");
    return admin_tracks("Saving Wazuh proxy events ENABLE={$array["ENABLE"]} FILTER={$array["FILTER"]} MAX_SIZE_MB=$size");
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



    $jsafter="BootstrapDialog1.close();LoadAjax('proxy-general-table','$page?table1=yes');";



    $form[]=$tpl->field_numeric("request_header_max_size","{request_header_max_size} (KB)",$request_header_max_size,"{request_header_max_size_text}");

    $form[]=$tpl->field_numeric("reply_header_max_size","{reply_header_max_size} (KB)",$reply_header_max_size,null);

    $form[]=$tpl->field_numeric("client_request_buffer_max_size","{client_request_buffer_max_size} (KB)",$client_request_buffer_max_size,"{client_request_buffer_max_size_text}");

    $form[]=$tpl->field_numeric("request_body_max_size","{request_body_max_size} (KB)",$request_body_max_size,"{request_body_max_size_text}");

    $form[]=$tpl->field_numeric("reply_body_max_size","{reply_body_max_size} (KB)",$reply_body_max_size,"{reply_body_max_size_text}");

    $html[]=$tpl->form_outside("{limits}", @implode("\n", $form),null,"{apply}",$jsafter,$security,false);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

// ---- Onglet TimeOuts (maquette stitch_dashboard_timeout_proxy) -----------------
// Un modele lu une seule fois (timeouts_model) alimente le bandeau, les quatre
// cartes de groupe et la carte noyau. Chaque carte ouvre SA modale (group=...).

// Valeurs par defaut, identiques a celles qu'applique articarest
// (squid/SquidTimeOuts.go, TuneKernel) quand le reglage vaut 0.
function timeouts_defaults():array{
    return array(
        "SquidClientLifetime"=>86400,
        "SquidUploadTimeouts"=>0,
        "SquidReadTimeout"=>900,
        "dns_timeout"=>5,
        "SquidConnectTimeout"=>10,
        "SquidConnectRetries"=>0,
        "SquidForwardTimeout"=>240,
        "forward_max_tries"=>30,
        "SquidPeerConnectTimeout"=>60,
        "SquidDeadPeerTimeout"=>10,
        "SquidShutdownLifetime"=>10,
        "DisableTCPEn"=>0,
        "DisableTCPWindowScaling"=>0,
    );
}

// Valeurs effectives, defauts compris : lues une seule fois pour les cartes ET
// les modales, qui ne peuvent ainsi jamais afficher deux valeurs differentes.
function timeouts_values():array{
    $s=$GLOBALS["CLASS_SOCKETS"];
    $v=array();
    foreach(timeouts_defaults() as $key=>$def){
        $v[$key]=intval($s->GET_INFO($key));
        if($v[$key]<0){$v[$key]=0;}
        // 0 = la valeur par defaut, sauf pour ceux dont 0 EST la valeur par defaut.
        if($v[$key]==0 && $def>0){$v[$key]=$def;}
    }
    if($v["SquidShutdownLifetime"]<10){$v["SquidShutdownLifetime"]=10;}
    return $v;
}

// Les cinq groupes : titre, explication, icone et reglages (dans l'ordre d'affichage).
function timeouts_groups():array{
    return array(
        "client"=>array("TITLE"=>"{pto_group_client}","EXPLAIN"=>"{pto_group_client_explain}","ICO"=>"fas fa-laptop",
            "KEYS"=>array("SquidClientLifetime","SquidUploadTimeouts","SquidReadTimeout")),
        "origin"=>array("TITLE"=>"{pto_group_origin}","EXPLAIN"=>"{pto_group_origin_explain}","ICO"=>"fas fa-server",
            "KEYS"=>array("dns_timeout","SquidConnectTimeout","SquidConnectRetries","SquidForwardTimeout","forward_max_tries")),
        "peers"=>array("TITLE"=>"{pto_group_peers}","EXPLAIN"=>"{pto_group_peers_explain}","ICO"=>"fas fa-sitemap",
            "KEYS"=>array("SquidPeerConnectTimeout","SquidDeadPeerTimeout")),
        "service"=>array("TITLE"=>"{pto_group_service}","EXPLAIN"=>"{pto_group_service_explain}","ICO"=>"fas fa-power-off",
            "KEYS"=>array("SquidShutdownLifetime")),
        "kernel"=>array("TITLE"=>"{pto_group_kernel}","EXPLAIN"=>"{pto_group_kernel_explain}","ICO"=>"fas fa-microchip",
            "KEYS"=>array("DisableTCPEn","DisableTCPWindowScaling")),
    );
}

// Libelle, aide et type de saisie de chaque reglage.
function timeouts_fields():array{
    return array(
        "SquidClientLifetime"=>array("{pto_client_lifetime}","{pto_client_lifetime_explain}","seconds"),
        "SquidUploadTimeouts"=>array("{pto_uploads_timeout}","{pto_uploads_timeout_explain}","upload"),
        "SquidReadTimeout"=>array("{pto_read_timeout}","{pto_read_timeout_explain}","seconds"),
        "dns_timeout"=>array("{pto_dns_timeout}","{pto_dns_timeout_explain}","seconds"),
        "SquidConnectTimeout"=>array("{pto_connect_timeout}","{pto_connect_timeout_explain}","seconds"),
        "SquidConnectRetries"=>array("{pto_connect_retries}","{pto_connect_retries_explain}","retries"),
        "SquidForwardTimeout"=>array("{pto_forward_timeout}","{pto_forward_timeout_explain}","seconds"),
        "forward_max_tries"=>array("{pto_forward_max_tries}","{pto_forward_max_tries_explain}","attempts"),
        "SquidPeerConnectTimeout"=>array("{pto_peer_connect_timeout}","{pto_peer_connect_timeout_explain}","seconds"),
        "SquidDeadPeerTimeout"=>array("{pto_dead_peer_timeout}","{pto_dead_peer_timeout_explain}","seconds"),
        "SquidShutdownLifetime"=>array("{pto_shutdown_lifetime}","{pto_shutdown_lifetime_explain}","seconds"),
        "DisableTCPEn"=>array("{pto_ecn}","{pto_ecn_explain}","kernel"),
        "DisableTCPWindowScaling"=>array("{pto_wscale}","{pto_wscale_explain}","kernel"),
    );
}

function timeouts_upload_choices():array{
    $c=array();
    $c[0]="{pto_same_as_read}";
    foreach(array(10,15,20,30,60,120,180,240) as $min){
        $c[$min]=timeouts_human($min*60);
    }
    return $c;
}

function timeouts_retries_choices():array{
    $c=array();
    $c[0]="{pto_no_retry}";
    for($i=1;$i<11;$i++){$c[$i]=$i;}
    return $c;
}

// Une duree lisible : "1 day", "4 minutes", "1 hour 30 minutes".
function timeouts_human($sec):string{
    $sec=intval($sec);
    if($sec<=0){return "0 {pto_seconds}";}
    $units=array(array(86400,"pto_day","pto_days"),array(3600,"pto_hour","pto_hours"),
        array(60,"pto_minute","pto_minutes"),array(1,"pto_second","pto_seconds"));
    $parts=array();
    foreach($units as $u){
        $n=intval(floor($sec/$u[0]));
        if($n<1){continue;}
        $parts[]=$n." {".($n==1?$u[1]:$u[2])."}";
        $sec-=$n*$u[0];
    }
    return @implode(" ",$parts);
}

// La valeur d'un reglage telle qu'on la lit.
function timeouts_display($key,$value):string{
    $value=intval($value);
    if($key=="SquidUploadTimeouts"){return $value==0?"{pto_same_as_read}":timeouts_human($value*60);}
    if($key=="SquidConnectRetries"){return $value==0?"{pto_no_retry}":$value." {pto_attempts}";}
    if($key=="forward_max_tries"){return $value." {pto_attempts}";}
    return timeouts_human($value);
}

function timeouts_model():array{
    $tpl=new template_admin();
    $v=timeouts_values();
    $m=array("v"=>$v,"def"=>timeouts_defaults(),"custom"=>array(),"ncustom"=>0);
    foreach($m["def"] as $k=>$d){
        $m["custom"][$k]=(intval($v[$k])!=intval($d));
        if($m["custom"][$k]){$m["ncustom"]++;}
    }
    $m["total"]=count($m["def"]);
    $m["can"]=$tpl->IsSecurity("AsSquidAdministrator");
    $m["upload_overrides_read"]=intval($v["SquidUploadTimeouts"])>0;
    return $m;
}

// Onglet "TimeOuts" : un conteneur, les cartes s'y chargent et y sont
// rechargees apres chaque enregistrement.
function timeouts(){
    $page=CurrentPageName();
    $html[]="<div id='proxy-timeouts-flat'></div>";
    $html[]="<script>LoadAjaxSilent('proxy-timeouts-flat','$page?timeouts-flat=yes');</script>";

    if(isset($_GET["timeouts-main"])){
        $html[]="<script>$.address.state('/');$.address.value('/proxy-timeouts');</script>";
        $tpl=new template_admin(null,@implode("\n", $html));
        echo $tpl->build_firewall();
        return;
    }
    $html[]="<script>$.address.state('/');$.address.value('/proxy-timeouts');</script>";
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);
}

function timeouts_group_param($raw):string{
    $g=strtolower(preg_replace('/[^a-z]/i','',(string)$raw));
    $groups=timeouts_groups();
    return isset($groups[$g])?$g:"";
}

function timeouts_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gid=timeouts_group_param(isset($_GET["group"])?$_GET["group"]:"");
    if($gid==""){$gid="client";}
    $groups=timeouts_groups();
    return $tpl->js_dialog2($groups[$gid]["TITLE"],"$page?timeouts-popup=yes&group=$gid",650);
}

// ---- Widgets -------------------------------------------------------------------

function timeouts_flat():bool{
    $tpl=new template_admin();
    $m=timeouts_model();
    $h=array();
    $h[]="<div class='ak pto-wrap'>";
    $h[]=timeouts_summary($m);
    $h[]="<div class='pto-grid'>";
    $h[]=timeouts_card_client($m);
    $h[]=timeouts_card_origin($m);
    $h[]=timeouts_card_peers($m);
    $h[]=timeouts_card_service($m);
    $h[]="</div>";
    $h[]=timeouts_card_kernel($m);
    $h[]="</div>";
    echo timeouts_style().$tpl->_ENGINE_parse_body(@implode("\n",$h));
    return true;
}

function timeouts_summary($m):string{
    $n=intval($m["ncustom"]);
    if($n==0){
        $chip="<span class='ak-pill ak-off'><i class='fas fa-circle-check'></i> {pto_all_defaults}</span>";
    }else{
        $chip="<span class='ak-pill ak-warn'><i class='fas fa-pen-to-square'></i> $n {pto_customized}</span>";
    }
    $h="<section class='ak-card pto-summary'>";
    $h.="<div class='pto-summary-l'><span class='ak-ico ak-ico-sq'><i class='fas fa-stopwatch'></i></span>";
    $h.="<div><div class='pto-summary-t'>{$m["total"]} {pto_settings} &middot; $n {pto_customized}</div>";
    $h.="<div class='pto-sub'>{pto_summary_explain}</div></div></div>";
    $h.="<div>$chip</div></section>";
    return $h;
}

function timeouts_card_head($m,$gid):string{
    $page=CurrentPageName();
    $groups=timeouts_groups();
    $g=$groups[$gid];
    $h="<div class='pto-head'>";
    $h.="<span class='ak-ico ak-ico-sm ak-ico-sq".($gid=="kernel"?" ak-warn":"")."'><i class='{$g["ICO"]}'></i></span>";
    $h.="<div class='pto-head-t'><h3 class='pto-title'>{$g["TITLE"]}</h3><div class='pto-sub'>{$g["EXPLAIN"]}</div></div>";
    if($gid=="kernel"){
        $h.="<span class='ak-pill ak-warn pto-chip-sm'>{pto_kernel_scope}</span>";
    }
    if($m["can"]){
        $h.="<button type='button' class='ak-btn ak-btn-ghost ak-btn-sm' aria-label=\"{pto_edit} - {$g["TITLE"]}\" OnClick=\"Loadjs('$page?timeouts-js=yes&group=$gid')\"><i class='fas fa-pen'></i> {pto_edit}</button>";
    }
    $h.="</div>";
    return $h;
}

// Une ligne : libelle + "Default: ..." a gauche, valeur (et "Custom") a droite.
function timeouts_row($m,$key,$extra="",$off=false,$note=""):string{
    $fields=timeouts_fields();
    $custom=$m["custom"][$key];
    $h="<div class='pto-row".($off?" pto-row-off":"")."'>";
    $h.="<div class='pto-row-l'><div class='pto-label'>{$fields[$key][0]}</div>";
    $h.="<div class='pto-meta'>{pto_default_is} ".timeouts_display($key,$m["def"][$key]).$extra."</div>";
    if($note!=""){$h.="<div class='pto-note'><i class='fas fa-circle-info'></i> $note</div>";}
    $h.="</div><div class='pto-row-r'>";
    if($custom && !$off){$h.="<span class='pto-custom'><i class='fas fa-circle'></i> {pto_custom}</span>";}
    $h.="<span class='ak-pill ak-off'>".timeouts_display($key,$m["v"][$key])."</span>";
    $h.="</div></div>";
    return $h;
}

function timeouts_card_client($m):string{
    $h="<article class='ak-card pto-card'>".timeouts_card_head($m,"client")."<div class='pto-rows'>";
    $h.=timeouts_row($m,"SquidClientLifetime");
    $h.=timeouts_row($m,"SquidUploadTimeouts");
    // Uploads timeout regle : articarest ecrit read_timeout avec SA valeur, Read timeout est ignore.
    $note="";
    if($m["upload_overrides_read"]){
        $note="{pto_replaced_by_upload} (".timeouts_display("SquidUploadTimeouts",$m["v"]["SquidUploadTimeouts"]).")";
    }
    $h.=timeouts_row($m,"SquidReadTimeout","",$m["upload_overrides_read"],$note);
    $h.="</div></article>";
    return $h;
}

function timeouts_card_origin($m):string{
    $h="<article class='ak-card pto-card'>".timeouts_card_head($m,"origin")."<div class='pto-rows'>";
    $h.=timeouts_row($m,"dns_timeout");
    $h.=timeouts_row($m,"SquidConnectTimeout");
    $h.=timeouts_row($m,"SquidConnectRetries"," &bull; <em>{pto_retries_hint}</em>");
    $h.=timeouts_row($m,"SquidForwardTimeout");
    $h.=timeouts_row($m,"forward_max_tries");
    $h.="</div>";
    if(intval($m["v"]["SquidConnectTimeout"])>intval($m["v"]["SquidForwardTimeout"])){
        $h.="<div class='ak-banner ak-warn pto-warn' role='status'><i class='fas fa-triangle-exclamation'></i> {pto_warn_connect_forward}</div>";
    }
    $h.="</article>";
    return $h;
}

function timeouts_card_peers($m):string{
    $h="<article class='ak-card pto-card'>".timeouts_card_head($m,"peers")."<div class='pto-rows'>";
    $h.=timeouts_row($m,"SquidPeerConnectTimeout");
    $h.=timeouts_row($m,"SquidDeadPeerTimeout");
    $h.="</div></article>";
    return $h;
}

function timeouts_card_service($m):string{
    $h="<article class='ak-card pto-card'>".timeouts_card_head($m,"service")."<div class='pto-rows'>";
    $h.=timeouts_row($m,"SquidShutdownLifetime"," &bull; <em>{pto_min_10s}</em>");
    $h.="</div></article>";
    return $h;
}

// Carte noyau : les deux sysctl, formules en positif (stockes en "Disable ...").
function timeouts_card_kernel($m):string{
    $page=CurrentPageName();
    $js="Loadjs('$page?timeouts-js=yes&group=kernel')";
    $h="<section class='ak-card pto-card pto-kernel'>".timeouts_card_head($m,"kernel")."<div class='pto-rows'>";
    $h.=timeouts_switch_row($m,"DisableTCPEn","{pto_ecn_hint}","",$js);
    $perf="<span class='ak-pill ak-warn pto-chip-sm'><i class='fas fa-gauge-simple-low'></i> {pto_perf_reduced}</span>";
    $h.=timeouts_switch_row($m,"DisableTCPWindowScaling","{pto_wscale_hint}",$perf,$js);
    $h.="</div></section>";
    return $h;
}

function timeouts_switch_row($m,$key,$hint,$chipWhenOff,$js):string{
    $fields=timeouts_fields();
    $on=intval($m["v"][$key])==0;
    $chips="";
    if(!$on){$chips="<span class='pto-custom'><i class='fas fa-circle'></i> {pto_custom}</span>".$chipWhenOff;}
    $state=$on?"{pto_enabled}":"{pto_disabled}";
    $h="<div class='pto-row'>";
    $h.="<div class='pto-row-l'><div class='pto-label'>{$fields[$key][0]} $chips</div><div class='pto-meta'>$hint</div></div>";
    $h.="<div class='pto-row-r'>";
    $attr=$m["can"]?"OnClick=\"$js\"":"disabled";
    $h.="<button type='button' role='switch' aria-checked='".($on?"true":"false")."' aria-label=\"{$fields[$key][0]}\" class='ak-sw' $attr></button>";
    $h.="<span class='pto-sw-label".($on?" pto-sw-label-on":"")."'>$state</span>";
    $h.="</div></div>";
    return $h;
}

// ---- Modale d'un groupe --------------------------------------------------------

function timeouts_popup():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=True;
    $page=CurrentPageName();
    $gid=timeouts_group_param(isset($_GET["group"])?$_GET["group"]:"");
    if($gid==""){$gid="client";}
    $groups=timeouts_groups();
    $g=$groups[$gid];
    $fields=timeouts_fields();
    $v=timeouts_values();

    $form=array();
    $form[]=$tpl->field_hidden("proxy-timeouts-group",$gid);
    foreach($g["KEYS"] as $k){
        list($label,$explain,$type)=$fields[$k];
        if($type=="seconds"){
            $form[]=$tpl->field_numeric($k,"$label ({pto_seconds})",$v[$k],$explain);
            continue;
        }
        if($type=="attempts"){
            $form[]=$tpl->field_numeric($k,"$label ({pto_attempts})",$v[$k],$explain);
            continue;
        }
        if($type=="upload" || $type=="retries"){
            $hash=$type=="upload"?timeouts_upload_choices():timeouts_retries_choices();
            // Une valeur hors liste (posee a la main) reste selectionnee au lieu d'etre ecrasee.
            if(!isset($hash[$v[$k]])){$hash[$v[$k]]=timeouts_display($k,$v[$k]);}
            $form[]=$tpl->field_array_hash($hash,$k,$label,$v[$k],false,$explain);
            continue;
        }
        if($type=="kernel"){
            // Coche = fonction active ; stocke a l'envers ("Disable...").
            $form[]=$tpl->field_checkbox("pto_on_$k",$label,intval($v[$k])==0?1:0,false,$explain);
        }
    }

    $jsafter="dialogInstance2.close();LoadAjaxSilent('proxy-timeouts-flat','$page?timeouts-flat=yes');";
    $html[]=$tpl->div_explain("{$g["TITLE"]}||{$g["EXPLAIN"]}");
    $html[]=$tpl->form_outside(null,$form,null,"{apply}",$jsafter,"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
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
    if($negative_dns_ttl==0){$negative_dns_ttl=30;}

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

    $html[]=$tpl->table_form_compile(2);
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

function dns_settings_form():bool{
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
    $form[]=$tpl->field_checkbox("ProxyUseOwnDNS","{proxy_use_its_own_dns}",$ProxyUseOwnDNS,
        "SquidNameServer1,SquidNameServer2,SquidNameServer3");
    $form[]=$tpl->field_text("SquidNameServer1","{primary_dns}",$SquidNameServer1);
    $form[]=$tpl->field_text("SquidNameServer2","{secondary_dns}",$SquidNameServer2);
    $form[]=$tpl->field_text("SquidNameServer3","{nameserver} 3",$SquidNameServer3);
    $jsrestart="Loadjs('$page?RestApiDNS=yes');dialogInstance4.close();";

    $html[]=$tpl->form_outside(null,$form,null,"{apply}",
        "LoadAjax('proxy-dns-settings-new','$page?proxy-dns-settings-new=yes');$jsrestart"
        ,$security);


    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;


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
    if($negative_dns_ttl==0){$negative_dns_ttl=30;}

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

// Enregistrement d'une modale de groupe : seuls les reglages de CE groupe sont
// ecrits (liste blanche), jamais une cle quelconque du POST.
function timeouts_save():bool{
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){
        echo $tpl->post_error("{ERROR_NO_PRIVS}");
        return false;
    }
    $gid=timeouts_group_param(isset($_POST["proxy-timeouts-group"])?$_POST["proxy-timeouts-group"]:"");
    if($gid==""){
        echo $tpl->post_error("{pto_unknown_group}");
        return false;
    }
    $groups=timeouts_groups();
    $fields=timeouts_fields();
    $save=array();
    $track=array();
    foreach($groups[$gid]["KEYS"] as $k){
        if($fields[$k][2]=="kernel"){
            if(!isset($_POST["pto_on_$k"])){continue;}
            $save[$k]=intval($_POST["pto_on_$k"])==1?0:1;
        }else{
            if(!isset($_POST[$k]) || trim((string)$_POST[$k])===""){continue;}
            $n=intval($_POST[$k]);
            if($n<0){$n=0;}
            $save[$k]=$n;
        }
        if($k=="SquidShutdownLifetime" && $save[$k]<10){$save[$k]=10;}
        $track[]="$k=".$save[$k];
    }
    if(count($save)==0){return true;}
    $_POST=$save;
    $tpl->SAVE_POSTs();
    timeouts_apply();
    return admin_tracks("Saving Proxy timeouts ($gid): ".@implode(", ",$track));
}

// Fenetre Limits (meme fichier timeouts.conf).
function limits_save():bool{
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){
        echo $tpl->post_error("{ERROR_NO_PRIVS}");
        return false;
    }
    $tpl->SAVE_POSTs();
    timeouts_apply();
    return admin_tracks_post("Saving Proxy limits settings");
}

// Regenere /etc/squid3/timeouts.conf puis, en arriere-plan, applique les sysctl
// (TuneKernel) et recharge Squid. La route timeouts n'ecrit que le fichier : sans
// le rechargement, rien n'etait pris en compte avant le prochain redemarrage.
function timeouts_apply(){
    $sock=new sockets();
    $sock->REST_API("/proxy/config/timeouts/reload");
    $sock->REST_API("/proxy/nohup/reload");
}

function timeouts_style():string{
    if(isset($GLOBALS["PTO_STYLE"])){return "";}
    $GLOBALS["PTO_STYLE"]=true;
    // Aplats (interrupteur, fond d'icone) = accent de la marque ; icones = accent fonce
    // (3:1 mini pour un graphique) ; TEXTE = #006a62, l'accent clair restant sous AA.
    $accent="#10b981";
    $dark="#0d9488";
    $soft="rgba(16,185,129,.12)";
    $text="#006a62";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.pto-wrap{margin-top:10px}
.pto-card:hover,.pto-summary:hover{box-shadow:0 2px 8px rgba(17,29,35,.08)}
.pto-summary{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:16px 24px;margin-bottom:16px}
.pto-summary-l{display:flex;align-items:center;gap:16px}
.pto-summary-t{font-size:15px;font-weight:600}
.pto-sub{font-size:13px;color:#3d4947;margin-top:2px;line-height:1.45}
.pto-chip-sm{margin-left:6px}
.pto-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-bottom:16px}
@media(max-width:1200px){.pto-grid{grid-template-columns:1fr}}
.pto-card{padding:20px}
.pto-head{display:flex;align-items:flex-start;gap:12px;padding-bottom:12px;border-bottom:1px solid rgba(188,201,198,.5)}
.pto-head-t{flex:1 1 auto;min-width:0}
.pto-title{font-size:15px;font-weight:600;margin:0;line-height:22px;color:#111d23}
.pto-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 0;border-top:1px solid rgba(188,201,198,.35)}
.pto-rows .pto-row:first-child{border-top:0}
.pto-row-l{min-width:0}
.pto-row-r{display:flex;align-items:center;gap:8px;flex:0 0 auto}
.pto-label{font-size:14px;color:#111d23}
.pto-meta{font-size:12px;color:#6d7a77;margin-top:2px}
.pto-meta em{color:#3d4947}
.pto-note{font-size:12px;color:$text;margin-top:6px}
.pto-custom{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:#865300;white-space:nowrap}
.pto-custom svg,.pto-custom i{font-size:7px}
.pto-row-off .pto-label{color:#6d7a77}
.pto-row-off .ak-pill{text-decoration:line-through;color:#6d7a77;background:#f3f6f8}
.pto-warn{align-items:flex-start;margin-top:12px;font-size:13px}
.pto-kernel{border-left:4px solid #865300}
.pto-sw-label{font-size:13px;font-weight:600;color:#6d7a77;min-width:64px}
.pto-sw-label-on{color:$text}
</style>";
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
// Onglet Active Directory (maquette stitch_proxy_active_directory, 2026-10-31).
// pad_model() = reglages, pad_status_model() = etat du helper de groupes go-squid-auth.
function ntlm_auth_flat():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $m=pad_model();
    $html=array();
    $html[]="<div class='ak pad'>";
    $html[]="<div id='pad-status-host'>".pad_status_html()."</div>";
    $html[]=pad_debug_banner($m);
    $html[]=pad_explain();
    $html[]="<div class='pad-grid'>";
    $html[]=pad_card_auth($m);
    $html[]=pad_card_groups($m);
    $html[]="</div></div>";
    echo pad_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).pad_js($page);
    return true;
}

// Rafraichissement du seul bandeau de statut (timer de pad_js).
function pad_status():bool{
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body(pad_status_html())."<script>PadPeity();</script>";
    return true;
}

function pad_int($key):int{
    return intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($key));
}

// Valeur numerique de SquidClientParams, avec le meme defaut que le daemon.
function pad_param($p,$key,$def):int{
    if(!isset($p[$key]) || !is_numeric($p[$key])){return $def;}
    $v=intval($p[$key]);
    return $v<=0?$def:$v;
}

function pad_model():array{
    $users=new usersMenus();
    $p=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));
    if(!is_array($p)){$p=array();}
    $m=array();
    $m["admin"]=$users->AsSquidAdministrator?true:false;
    $m["page"]=CurrentPageName();
    // Helper Kerberos/NTLM de Squid
    $m["auth_max"]=pad_param($p,"auth_param_ntlm_children",20);
    $m["auth_start"]=pad_param($p,"auth_param_ntlm_startup",5);
    $m["auth_idle"]=pad_param($p,"auth_param_ntlm_idle",1);
    $m["keepalive"]=pad_int("SquidNTLMKeepAlive");
    $m["rcache"]=pad_int("KerberosReplayCache");
    // Helper de groupes go-squid-auth (external_acl ads_group)
    $m["grp_max"]=pad_param($p,"auth_param_ntlmgroup_children",15);
    $m["grp_start"]=pad_param($p,"auth_param_ntlmgroup_startup",1);
    $m["grp_idle"]=pad_param($p,"auth_param_ntlmgroup_idle",1);
    $m["concurrency"]=pad_param($p,"Go_Squid_Auth_Concurrency",50);
    $m["ttl"]=pad_int("DynamicGroupsAclsTTL");
    if($m["ttl"]==0){$m["ttl"]=3600;}
    $m["conn_timeout"]=pad_int("Go_Shield_External_ACL_Ldap_ConnTimeout");
    if($m["conn_timeout"]==0){$m["conn_timeout"]=5;}
    $m["search_timeout"]=pad_int("Go_Shield_External_ACL_Ldap_SearchTimelimit");
    if($m["search_timeout"]==0){$m["search_timeout"]=3;}
    $m["debug"]=pad_int("Go_Shield_External_ACL_Ldap_Debug");
    $m["ad_agent"]=pad_int("EnableExternalACLADAgent");
    return $m;
}

// Meme calcul que squid.getWorkers() : en SMP chaque worker a son propre pool de helpers
// de groupes, et le daemon repartit le maximum configure (2 par worker au minimum).
function pad_workers():int{
    if(pad_int("SquidSMPDisabled")==1){return 1;}
    $cpus=0;
    $info=@file_get_contents("/proc/cpuinfo");
    if($info!==false){$cpus=preg_match_all('/^processor\s*:/m',$info);}
    if($cpus<1){$cpus=1;}
    $smp=@unserialize((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPConfig"));
    $c=is_array($smp)?count($smp):0;
    if($c<1){$c=1;}
    if($c>$cpus){$c=$cpus;}
    return $c;
}
function pad_group_ceiling($max):int{
    $w=pad_workers();
    if($w<=1){return $max;}
    $per=intval(ceil($max/$w));
    if($per<2){$per=2;}
    return $per*$w;
}

function pad_status_model():array{
    $s=array("error"=>"","agent"=>false,"disabled"=>false,"running"=>false,"processes"=>0,
        "memory_kb"=>0,"uptime"=>"","curfd"=>0,"maxfd"=>0,"keys"=>0,"version"=>"");
    $libmem=new lib_memcached();
    if(pad_int("EnableExternalACLADAgent")==1){
        // Connecteur AD Agent : le statut vient encore du fichier ecrit par exec.status.php.
        $s["agent"]=true;
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ksrn.php?go-squid-auth=yes");
        $bsini=new Bs_IniHandler(PROGRESS_DIR."/go_squid_auth_ad_agent.status");
        $sec=isset($bsini->_params["GO_SQUID_AUTH_AD_AGENT"])?$bsini->_params["GO_SQUID_AUTH_AD_AGENT"]:array();
        $s["running"]=intval($sec["running"] ?? 0)==1;
        $s["memory_kb"]=intval($sec["master_memory"] ?? 0);
        $s["uptime"]=trim((string)($sec["uptime"] ?? ""));
        $s["version"]=trim((string)$libmem->getKey("Go-Squid-AD-Agent-Client-Version"));
        return $s;
    }
    $s["version"]=trim((string)$libmem->getKey("Go-Squid-Auth-Version"));
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/goshield/proxy/auth/status"));
    if(!is_object($json) || !property_exists($json,"Info")){
        $s["error"]="REST API";
        if(is_object($json) && property_exists($json,"Error") && trim((string)$json->Error)<>""){$s["error"]=(string)$json->Error;}
        return $s;
    }
    $s["keys"]=property_exists($json,"Keys")?intval($json->Keys):0;
    $bsini=new Bs_IniHandler();
    $bsini->loadString((string)$json->Info);
    $sec=isset($bsini->_params["GO_SQUID_AUTH"])?$bsini->_params["GO_SQUID_AUTH"]:array();
    if(isset($sec["service_disabled"]) && intval($sec["service_disabled"])==0){$s["disabled"]=true;}
    $s["running"]=intval($sec["running"] ?? 0)==1;
    $s["processes"]=intval($sec["processes_number"] ?? 0);
    $s["memory_kb"]=intval($sec["master_memory"] ?? 0);
    $s["uptime"]=trim((string)($sec["uptime"] ?? ""));
    $s["curfd"]=intval($sec["curfd"] ?? 0);
    $s["maxfd"]=intval($sec["maxfd"] ?? 0);
    return $s;
}

// ---- Bandeau de statut ----

function pad_status_html():string{
    $m=pad_model();
    $s=pad_status_model();
    $h=array();
    $h[]="<section class='ak-card pad-band' aria-live='polite'>";
    $h[]=pad_band_identity($s);
    if($s["error"]<>""){
        $h[]="<div class='pad-band-msg pad-band-msg-err'><span class='pad-ico'><i class='fas fa-triangle-exclamation'></i></span>{pad_status_error} ".pad_esc($s["error"])."</div>";
    }elseif($s["disabled"]){
        $h[]="<div class='pad-band-msg'>{pad_proxy_disabled}</div>";
    }elseif(!$s["running"]){
        $h[]="<div class='pad-band-msg pad-band-msg-err'>{pad_not_running}</div>";
    }else{
        $h[]="<div class='pad-metrics'>";
        if(!$s["agent"]){$h[]=pad_metric_processes($s,$m);}
        $h[]=pad_metric_memory($s);
        $h[]=pad_metric_uptime($s);
        if(!$s["agent"]){$h[]=pad_metric_fd($s);}
        $h[]="</div>";
    }
    if(!$s["agent"]){$h[]=pad_metric_cache($s,$m);}
    $h[]="</section>";
    return @implode("",$h);
}

function pad_esc($s):string{
    return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
}

function pad_band_identity($s):string{
    $title=$s["agent"]?"{GO_SQUID_AUTH_AD_AGENT}":"{GO_SQUID_AUTH}";
    if($s["error"]<>""){
        $pill="<span class='ak-pill ak-err'><span class='pad-ico'><i class='fas fa-circle-xmark'></i></span>{pad_unknown}</span>";
    }elseif($s["disabled"]){
        $pill="<span class='ak-pill ak-off'><span class='pad-ico'><i class='fas fa-circle-minus'></i></span>{disabled}</span>";
    }elseif($s["running"]){
        $pill="<span class='ak-pill ak-ok'><span class='pad-ico'><i class='fas fa-circle-check'></i></span>{pad_running}</span>";
    }else{
        $pill="<span class='ak-pill ak-err'><span class='pad-ico'><i class='fas fa-circle-xmark'></i></span>{pad_stopped}</span>";
    }
    $ver=$s["version"]<>""?"<span class='ak-tag pad-ver'>v".pad_esc($s["version"])."</span>":"";
    return "<div class='pad-ident'><span class='ak-ico ak-ico-sq'><i class='fas fa-user-shield'></i></span>"
        ."<div><div class='pad-ident-title'>$title $ver</div><div class='pad-ident-sub'>{pad_band_sub}</div><div>$pill</div></div></div>";
}

function pad_metric($label,$value,$caption,$donut="",$cls=""):string{
    $cap=$caption<>""?"<div class='pad-cap $cls'>$caption</div>":"";
    return "<div class='pad-metric'>$donut<div><div class='ak-kicker'>$label</div><div class='pad-mvalue'>$value</div>$cap</div></div>";
}

// Donut peity mesure contre un plafond reel ; couleur selon le taux d'occupation.
function pad_donut($used,$max,$label):string{
    $max=max(1,intval($max));
    $used=max(0,intval($used));
    $ratio=$used/$max;
    $color="#0d9488";
    if($ratio>=0.8){$color="#b45309";}
    if($ratio>=1){$color="#ba1a1a";}
    $shown=min($used,$max);
    return "<span class='pad-donut' role='img' aria-label=\"$label\" data-peity='{\"fill\":[\"$color\",\"#e2e8f0\"],\"radius\":18,\"innerRadius\":12}'>$shown/$max</span>";
}

function pad_metric_processes($s,$m):string{
    $ceil=pad_group_ceiling($m["grp_max"]);
    $n=$s["processes"];
    $label="$n {pad_of} $ceil {pad_processes_running}";
    $cap="$n {pad_of} $ceil {pad_running_lc}";
    $cls="";
    if($n>=$ceil){$cap="<span class='pad-ico'><i class='fas fa-triangle-exclamation'></i></span>{pad_at_limit}";$cls="pad-cap-err";}
    elseif($n>=0.8*$ceil){$cls="pad-cap-warn";}
    return pad_metric("{pad_processes}","$n <span class='pad-max'>/ $ceil</span>",$cap,pad_donut($n,$ceil,$label),$cls);
}

function pad_metric_memory($s):string{
    $v=$s["memory_kb"]>0?FormatBytes($s["memory_kb"],true):"&ndash;";
    return pad_metric("{pad_memory}",$v,"{pad_all_processes}");
}

function pad_metric_uptime($s):string{
    $v=$s["uptime"]<>""?pad_esc($s["uptime"]):"&ndash;";
    return pad_metric("{pad_uptime}",$v,"{pad_since_start}");
}

function pad_metric_fd($s):string{
    if($s["maxfd"]<=0){return pad_metric("{pad_file_descriptors}",intval($s["curfd"]),"");}
    $pct=intval(round(100*$s["curfd"]/$s["maxfd"]));
    $label="{$s["curfd"]} {pad_of} {$s["maxfd"]} {pad_file_descriptors}";
    $cls=$pct>=80?"pad-cap-warn":"";
    return pad_metric("{pad_file_descriptors}",intval($s["curfd"])." <span class='pad-max'>/ ".intval($s["maxfd"])."</span>",
        "$pct% {pad_used}",pad_donut($s["curfd"],$s["maxfd"],$label),$cls);
}

function pad_metric_cache($s,$m):string{
    $n=intval($s["keys"]);
    $btn="";
    if($m["admin"]){
        if($n>0){
            $btn="<button type='button' class='ak-btn' OnClick=\"Loadjs('{$m["page"]}?pad-clean-js=yes');\">"
                ."<span class='pad-ico'><i class='fas fa-broom'></i></span>{pad_clear_cache}</button>";
        }else{
            $btn="<button type='button' class='ak-btn' disabled title=\"{pad_nothing_to_clear}\">"
                ."<span class='pad-ico'><i class='fas fa-broom'></i></span>{pad_clear_cache}</button>";
        }
    }
    return "<div class='pad-cache'><div><div class='ak-kicker'>{pad_cached_lookups}</div>"
        ."<div class='pad-mvalue'>".number_format($n,0,"."," ")."</div></div>$btn</div>";
}

// ---- Reglages ----

function pad_debug_banner($m):string{
    if($m["debug"]<>1){return "";}
    return "<div class='ak-banner ak-warn pad-debug' role='status'><span class='pad-ico'><i class='fas fa-bug'></i></span>{pad_debug_on}</div>";
}

function pad_explain():string{
    return "<p class='pad-explain'><span class='pad-ico'><i class='fas fa-circle-info'></i></span>{pad_explain}</p>";
}

function pad_card_open($m,$icon,$title,$sub,$editjs):string{
    $btn="";
    if($m["admin"]){
        $btn="<button type='button' class='ak-btn ak-btn-ghost ak-btn-sm' aria-label=\"{pxg_edit}: $title\" OnClick=\"$editjs\">"
            ."<span class='pad-ico'><i class='fas fa-pen'></i></span>{pxg_edit}</button>";
    }
    return "<section class='ak-card pad-card'><div class='pad-head'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='$icon'></i></span>"
        ."<div class='pad-head-txt'><h3 class='pad-title'>$title</h3><div class='pad-sub'>$sub</div></div>$btn</div><div class='pad-body'>";
}

function pad_row($m,$label,$value,$sub="",$js=""):string{
    $s=$sub<>""?"<div class='pad-sub'>$sub</div>":"";
    if($js<>"" && $m["admin"]){
        return "<div class='pad-row pad-click' role='button' tabindex='0' OnClick=\"$js\" "
            ."onkeydown=\"if(event.key==='Enter'||event.key===' '){this.click();return false;}\">"
            ."<div class='pad-label'>$label$s</div><div class='pad-value'>$value</div>"
            ."<span class='pad-chev' aria-hidden='true'><i class='fas fa-chevron-right'></i></span></div>";
    }
    return "<div class='pad-row'><div class='pad-label'>$label$s</div><div class='pad-value'>$value</div></div>";
}

function pad_chip($on,$warn=false):string{
    if(intval($on)==1){
        if($warn){return "<span class='ak-pill ak-warn'><span class='pad-ico'><i class='fas fa-bug'></i></span>{enabled}</span>";}
        return "<span class='ak-pill ak-ok'><span class='pad-ico'><i class='fas fa-circle-check'></i></span>{enabled}</span>";
    }
    return "<span class='ak-pill ak-off'><span class='pad-ico'><i class='fas fa-circle-minus'></i></span>{disabled}</span>";
}

// Echelle 0..max avec les reperes "a preparer" et "au demarrage".
function pad_scale($idle,$start,$max,$lblIdle,$lblStart,$lblMax,$aria):string{
    $max=max(1,intval($max));
    $pos=function($v)use($max){ return min(100,max(0,round(100*intval($v)/$max,1))); };
    $marks=array();
    if(intval($idle)==intval($start)){
        $marks[]=array($pos($idle),intval($idle),"$lblIdle / $lblStart");
    }else{
        $marks[]=array($pos($idle),intval($idle),$lblIdle);
        $marks[]=array($pos($start),intval($start),$lblStart);
    }
    $h="<div class='pad-scale' role='img' aria-label=\"$aria\"><div class='pad-track'><div class='pad-fill'></div>";
    foreach($marks as $mk){
        $h.="<span class='pad-mark' style='left:{$mk[0]}%'></span>";
    }
    $h.="<span class='pad-mark pad-mark-max' style='left:100%'><i class='fas fa-check'></i></span></div><div class='pad-ticks'>";
    // Pres d'un bord, le libelle s'aligne sur ce bord au lieu de deborder de la carte.
    $nearZero=false;
    foreach($marks as $mk){if($mk[0]<8){$nearZero=true;}}
    if(!$nearZero){$h.="<span class='pad-tick pad-tick-zero' style='left:0'>0</span>";}
    foreach($marks as $mk){
        $cls="pad-tick";
        if($mk[0]<12){$cls.=" pad-tick-l";}
        elseif($mk[0]>80){$cls.=" pad-tick-r";}
        $h.="<span class='$cls' style='left:{$mk[0]}%'><strong>{$mk[1]}</strong><small>{$mk[2]}</small></span>";
    }
    $h.="<span class='pad-tick pad-tick-max' style='left:100%'><strong>$max</strong><small>$lblMax</small></span></div></div>";
    return $h;
}

function pad_duration($sec):string{
    $sec=intval($sec);
    $units=array(604800=>array("{pad_week}","{pad_weeks}"),86400=>array("{pad_day}","{pad_days}"),3600=>array("{pad_hour}","{pad_hours}"),60=>array("{pad_minute}","{pad_minutes}"));
    foreach($units as $u=>$names){
        if($sec>=$u && $sec%$u==0){
            $n=$sec/$u;
            return $n." ".($n>1?$names[1]:$names[0]);
        }
    }
    return "$sec {pad_seconds}";
}

function pad_card_auth($m):string{
    $page=$m["page"];
    $h=array();
    $h[]=pad_card_open($m,"fas fa-key","{pad_auth_processes}","{pad_auth_processes_sub}","Loadjs('$page?ntlm-js=yes')");
    $h[]="<div class='pad-scale-wrap'><div class='pad-scale-head'><span>{pad_scale}</span><span class='pad-sub'>0 &ndash; {$m["auth_max"]} {processes}</span></div>"
        .pad_scale($m["auth_idle"],$m["auth_start"],$m["auth_max"],"{pad_prepare}","{pad_to_start}","{pad_max_limit}","{pad_auth_processes}")."</div>";
    $h[]=pad_row($m,"{keep_alive}",pad_chip($m["keepalive"]),"{pad_keepalive_sub}");
    $h[]=pad_row($m,"{Kerberos_replay_cache}",pad_chip($m["rcache"]),"{pad_rcache_sub}","Loadjs('$page?rcache-js=yes')");
    $h[]="</div></section>";
    return @implode("",$h);
}

function pad_card_groups($m):string{
    $page=$m["page"];
    $h=array();
    $h[]=pad_card_open($m,"fas fa-users","{groups_checking}","{pad_groups_sub}","Loadjs('$page?ntlm-js=yes')");
    $ceil=pad_group_ceiling($m["grp_max"]);
    $smp=$ceil<>$m["grp_max"]?"<div class='pad-sub'>{pad_smp_note} $ceil</div>":"";
    $h[]="<div class='pad-scale-wrap'><div class='pad-scale-head'><span>{pad_scale}</span><span class='pad-sub'>0 &ndash; {$m["grp_max"]} {processes}</span></div>"
        .pad_scale($m["grp_idle"],$m["grp_start"],$m["grp_max"],"{pad_prepare}","{pad_preload}","{pad_max_limit}","{groups_checking}")."$smp</div>";
    $h[]=pad_row($m,"{CHILDREN_CONCURRENCY}","<span class='pad-tag'><strong>{$m["concurrency"]}</strong> {pad_requests_per_process}</span>","{pad_concurrency_sub}");
    $h[]=pad_row($m,"{QUERY_GROUP_TTL_CACHE}","<span class='pad-tag'>".pad_duration($m["ttl"])."</span>");
    $h[]="<div class='pad-caption'>LDAP</div>";
    $h[]=pad_row($m,"{pad_conn_timeout}","<span class='pad-tag'><strong>{$m["conn_timeout"]}</strong> s</span>");
    $h[]=pad_row($m,"{pad_search_timeout}","<span class='pad-tag'><strong>{$m["search_timeout"]}</strong> s</span>");
    $h[]=pad_row($m,"{debug_mode}",pad_chip($m["debug"],true));
    $h[]="</div></section>";
    return @implode("",$h);
}

// ---- Vidage du cache des recherches AD (confirme) ----

function pad_clean_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){return $tpl->js_no_privileges();}
    return $tpl->js_dialog_confirm_action("{pad_confirm_clean}","pad-clean-cache","none",
        "LoadAjaxSilent('pad-status-host','$page?pad-status=yes');");
}

function pad_clean_save():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){echo $tpl->post_error("{no_privileges}");return false;}
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/goshield/proxy/auth/clean"));
    if(!is_object($json) || !property_exists($json,"Status") || !$json->Status){
        $err="REST API";
        if(is_object($json) && property_exists($json,"Error") && trim((string)$json->Error)<>""){$err=(string)$json->Error;}
        echo $tpl->post_error($err);
        return false;
    }
    return admin_tracks("Clean the cache of the Go Proxy Auth plugin");
}

function pad_style():string{
    if(isset($GLOBALS["PAD_STYLE"])){return "";}
    $GLOBALS["PAD_STYLE"]=true;
    $accent="#10b981";$dark="#0d9488";$soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.pad{margin-top:10px}
.pad-ico{display:inline-flex;font-size:13px}
.pad-band{display:flex;flex-wrap:wrap;align-items:center;gap:16px 24px;padding:18px 20px}
.pad-ident{display:flex;align-items:center;gap:12px;min-width:240px;padding-right:24px;border-right:1px solid #eef2f6}
.pad-ident-title{font-size:15px;font-weight:600;line-height:20px}
.pad-ident-sub{font-size:12px;color:#6d7a77;margin:2px 0 6px}
.pad-ver{margin-left:4px}
.pad-metrics{display:flex;flex-wrap:wrap;gap:16px 22px;flex:1 1 auto}
.pad-metric{display:flex;align-items:center;gap:10px;min-width:130px}
.pad-mvalue{font-size:20px;font-weight:600;line-height:26px;color:#0f172a;white-space:nowrap}
.pad-max{font-size:13px;font-weight:400;color:#6d7a77}
.pad-cap{font-size:12px;color:#6d7a77}
.pad-cap-warn{color:#865300;font-weight:600}
.pad-cap-err{color:#ba1a1a;font-weight:600;display:inline-flex;align-items:center;gap:4px}
.pad-donut{display:none}
.pad-band-msg{flex:1 1 auto;font-size:13px;color:#475569}
.pad-band-msg-err{color:#ba1a1a;display:inline-flex;align-items:center;gap:6px}
.pad-cache{display:flex;align-items:center;gap:14px;margin-left:auto;padding-left:24px;border-left:1px solid #eef2f6}
.pad-click:focus-visible{outline:2px solid $dark;outline-offset:2px}
.pad-debug{margin-top:12px;font-size:13px;font-weight:600}
.pad-explain{display:flex;gap:8px;align-items:flex-start;margin:16px 0;font-size:13px;color:#475569;line-height:19px}
.pad-explain .pad-ico{color:$dark;margin-top:2px}
.pad-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(480px,1fr));gap:16px}
.pad-card{padding:18px 20px}
.pad-head{display:flex;align-items:flex-start;gap:10px;padding-bottom:12px;border-bottom:1px solid #eef2f6}
.pad-head-txt{flex:1 1 auto;min-width:0}
.pad-title{font-size:15px;font-weight:600;margin:0;line-height:20px;text-transform:none;color:#111d23}
.pad-sub{font-size:12px;color:#6d7a77;margin-top:2px;line-height:16px}
.pad-scale-wrap{padding:14px 0 6px}
.pad-scale-head{display:flex;justify-content:space-between;align-items:baseline;font-size:13px;color:#3d4947;margin-bottom:12px}
.pad-scale{padding:0 12px}
.pad-track{position:relative;height:8px;border-radius:999px;background:#eef2f6;margin:10px 0}
.pad-fill{position:absolute;left:0;top:0;bottom:0;width:100%;border-radius:999px;background:$soft}
.pad-mark{position:absolute;top:50%;width:20px;height:20px;margin:-10px 0 0 -10px;border-radius:50%;background:#fff;border:3px solid $dark}
.pad-mark-max{background:$dark;color:#fff;font-size:10px;display:inline-flex;align-items:center;justify-content:center;border-color:$dark}
.pad-ticks{position:relative;height:36px}
.pad-tick{position:absolute;top:0;transform:translateX(-50%);text-align:center;font-size:13px;line-height:16px;white-space:nowrap}
.pad-tick small{display:block;font-size:12px;color:#6d7a77}
.pad-tick-zero{transform:none;color:#94a3b8;margin-left:-12px}
.pad-tick-max{transform:translateX(-100%);text-align:right;margin-left:12px}
.pad-tick-l{transform:none;text-align:left;margin-left:-12px}
.pad-tick-r{transform:translateX(-100%);text-align:right;margin-left:12px}
.pad-row{display:flex;align-items:center;gap:16px;min-height:46px;padding:9px 0;border-top:1px solid #f1f5f9}
.pad-click{cursor:pointer;border-radius:6px;margin:0 -8px;padding-left:8px;padding-right:8px}
.pad-click:hover{background:#f8fafc}
.pad-label{flex:1 1 auto;min-width:150px;font-size:13px;color:#111d23}
.pad-value{display:flex;align-items:center;gap:6px;font-size:13px}
.pad-chev{color:#94a3b8;font-size:12px;display:inline-flex}
.pad-tag{display:inline-block;padding:3px 9px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;font-size:13px;white-space:nowrap}
.pad-caption{margin-top:10px;padding:10px 0 4px;border-top:1px solid #f1f5f9;font-size:12px;font-weight:700;letter-spacing:.06em;color:#475569}
.pad-caption + .pad-row{border-top:0}
</style>";
}

function pad_js($page):string{
    return "<script>
function PadPeity(){
    var run=function(){ if(!jQuery.fn.peity){return;} jQuery('.pad-donut').peity('donut'); };
    if(window.jQuery && jQuery.fn.peity){run();return;}
    if(window.PadPeityLoading){return;}
    window.PadPeityLoading=true;
    var sc=document.createElement('script');sc.src='js/jquery.peity.min.js';
    sc.onload=function(){window.PadPeityLoading=false;run();};
    document.head.appendChild(sc);
}
function PadBusy(){
    if(document.querySelector('.modal.in,.modal.show')){return true;}
    var sw=document.querySelector('.sweet-alert');
    return !!(sw && sw.offsetParent!==null);
}
if(window.PadTimer){clearInterval(window.PadTimer);}
window.PadTimer=setInterval(function(){
    if(!document.getElementById('pad-status-host')){clearInterval(window.PadTimer);window.PadTimer=null;return;}
    if(PadBusy()){return;}
    LoadAjaxSilent('pad-status-host','$page?pad-status=yes');
},10000);
PadPeity();
NoSpinner();
</script>";
}
// Fin de l'onglet Active Directory

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
    $start_up[400]=400;
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
    // Une valeur hors liste (saisie ailleurs ou ancienne) doit rester selectionnee, sinon le save l'ecrase.
    foreach(array("auth_param_ntlm_children","auth_param_ntlm_startup","auth_param_ntlm_idle","auth_param_ntlmgroup_children","auth_param_ntlmgroup_startup","auth_param_ntlmgroup_idle") as $k){
        $v=intval($SquidClientParams[$k]);
        if($v>0 && !isset($start_up[$v])){$start_up[$v]=$v;}
    }
    ksort($start_up);
    if(!isset($ttl_interval[$DynamicGroupsAclsTTL])){$ttl_interval[$DynamicGroupsAclsTTL]="$DynamicGroupsAclsTTL {seconds}";ksort($ttl_interval);}
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


function basic_authentication_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $restart_id=$_GET["restart-id"];
    return $tpl->js_dialog2("{parameters}","$page?basic-auth-popup=yes&restart-id=$restart_id");
}
function basic_authentication_start():bool{
    $page=CurrentPageName();
    $restart_id=$_GET["restart-id"];
    echo "<div id='basic-authentication-start'></div>
    <script>LoadAjaxSilent('basic-authentication-start','$page?basic-auth-flat=yes&restart-id=$restart_id');</script>";
    return true;
}
function basic_authentication_flat():bool{
    $page=CurrentPageName();
    $restart_id=$_GET["restart-id"];
    $tpl=new template_admin();
    $SquidClientParams=basic_authentication_defaults();
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
    $security="AsSquidAdministrator";
    $tpl->table_form_field_js("Loadjs('$page?basic-authentication-js')",$security);
    $tpl->table_form_field_text("{max_processes}",$SquidClientParams["auth_param_basic_children"]. " {processes}",ico_params);
    $tpl->table_form_field_text("{preload_processes}",$SquidClientParams["auth_param_basic_startup"]. " {processes}",ico_params);
    $tpl->table_form_field_text("{prepare_processes}",$SquidClientParams["auth_param_basic_idle"]. " {processes}",ico_params);


    $tpl->table_form_section("{sessions_cache}");

    $tpl->table_form_field_text("{authenticate_cache_garbage_interval}",$ttl_interval[$SquidClientParams["authenticate_cache_garbage_interval"]],ico_timeout);
    $tpl->table_form_field_text("{authenticate_ttl_title}",$ttl_interval[$SquidClientParams["authenticate_ttl"]],ico_timeout);
    $tpl->table_form_field_text("{authenticate_ip_ttl_title}",$ttl_interval[$SquidClientParams["authenticate_ip_ttl"]],ico_timeout);
    $tpl->table_form_field_text("{credentialsttl}",$ttl_interval[$SquidClientParams["credentialsttl"]],ico_timeout);
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->div_explain("{http_authentication}||{SquidClientParams_text}");
    $html[]=$tpl->table_form_compile(2);
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function basic_authentication_defaults():array{
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
    return $SquidClientParams;
}

function basic_authentication_popup():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $security="AsSquidAdministrator";
    $SquidClientParams=basic_authentication_defaults();
    $page=CurrentPageName();

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
    $start_up[400]=400;
    $start_up[500]=500;
    $start_up[600]=600;
    $start_up[700]=700;
    $start_up[800]=800;
    $start_up[900]=900;
    $start_up[1000]=1000;
    $start_up[1500]=1500;



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
        "dialogInstance2.close();LoadAjaxSilent('basic-authentication-start','$page?basic-auth-flat=yes&restart-id=$restart_id');LoadAjax('auth-table','fw.proxy.members.engines.php?table=yes');
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
function mmc_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog2("Microsoft Connected Cache","$page?mmc-popup=yes",650);
}

function mmc_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $SquidMicrosoftConnectedCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMicrosoftConnectedCache"));
    $html=$tpl->BigCircleCheckbox("SquidMicrosoftConnectedCache",
        "Microsoft Connected Cache","{microsoft_connected_cache_explain}",
        $SquidMicrosoftConnectedCache,
        "LoadAjax('proxy-general-table','$page?table1=yes');dialogInstance2.close();");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function mmc_save():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidMicrosoftConnectedCache",$_POST["SquidMicrosoftConnectedCache"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/ccm");
    return admin_tracks("Set Proxy Microsoft Connected Cache feature to {$_POST["SquidMicrosoftConnectedCache"]}");
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


    $SquidMicrosoftConnectedCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMicrosoftConnectedCache"));
    $SquidServerPersistentConnections=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidServerPersistentConnections"));

    $html[]=$tpl->div_explain("{persistent_connections_explain}");

    $PersistentConnectionsShow=1;
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($SquidMicrosoftConnectedCache==1){
        $SquidServerPersistentConnections=1;
        $SquidClientPersistentConnections=1;
        $PersistentConnectionsShow=0;
    }
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){
        $HaClusterDisableClientPersistentConnections=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterDisableClientPersistentConnections");
        $HaClusterDisableServerPersistentConnections=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterDisableServerPersistentConnections");
        $SquidServerPersistentConnections=1;
        $SquidClientPersistentConnections=1;
        if($HaClusterDisableClientPersistentConnections==1){
            $SquidClientPersistentConnections=0;
        }
        if($HaClusterDisableServerPersistentConnections==1){
            $SquidServerPersistentConnections=0;
        }
        $PersistentConnectionsShow=0;
    }

    $form[]=$tpl->field_section("{clients}&nbsp;$arrow&nbsp;{APP_SQUID}","");
    if($PersistentConnectionsShow==1) {
        $form[] = $tpl->field_checkbox("SquidClientPersistentConnections", "{client_persistent_connections}", $SquidClientPersistentConnections, false, "{client_persistent_connections_explain}");
    }else{
        $form[] = $tpl->field_hidden("SquidClientPersistentConnections",$SquidClientPersistentConnections);
    }
    $form[]=$tpl->field_numeric("client_idle_pconn_timeout","{timeout} ({seconds})",$client_idle_pconn_timeout,false);

    $form[]=$tpl->field_section("{APP_SQUID}&nbsp;$arrow&nbsp;{domains}","");

    if($PersistentConnectionsShow==1) {
        $form[] = $tpl->field_checkbox("SquidServerPersistentConnections", "{server_persistent_connections}", $SquidServerPersistentConnections, false, "{server_persistent_connections_explain}");
    }else{
        $form[] = $tpl->field_hidden("SquidServerPersistentConnections",$SquidServerPersistentConnections);
    }
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
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-squidgene-restart')";
    return "BootstrapDialog1.close();LoadAjax('proxy-general-table','$page?table1=yes');$jsrestart";
}
function section_ftp_popup(){
    $tpl=new template_admin();
    $FTP_PARAMS=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidFTPParams"));
    if(!is_array($FTP_PARAMS)){$FTP_PARAMS=array();}
    if(!isset($FTP_PARAMS["ftp_user"])){$FTP_PARAMS["ftp_user"]="";}
    if(!isset($FTP_PARAMS["ftp_passive"])){$FTP_PARAMS["ftp_passive"]=1;}
    if(!isset($FTP_PARAMS["ftp_sanitycheck"])){$FTP_PARAMS["ftp_sanitycheck"]=1;}
    if(!isset($FTP_PARAMS["ftp_epsv"])){$FTP_PARAMS["ftp_epsv"]="1";}
    $SquidAllowFTP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAllowFTP"));
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


    $form[]=$tpl->field_checkbox("SquidAllowFTP","{allowftp_access}",$SquidAllowFTP,false,"{allowftp_access_explain}");
    $form[]=$tpl->Field_text("ftp_user","{ftp_user}",$FTP_PARAMS["ftp_user"],false,"{squid_ftp_user_explain}");
    $form[]=$tpl->field_checkbox("ftp_passive","{ftp_passive}",$FTP_PARAMS["ftp_passive"],false,"{ftp_passive_explain}");

    $form[]=$tpl->field_checkbox("ftp_sanitycheck","{ftp_sanitycheck}",$FTP_PARAMS["ftp_sanitycheck"],false,"{ftp_sanitycheck_explain}");
    $form[]=$tpl->field_checkbox("ftp_epsv","{ftp_epsv}",$FTP_PARAMS["ftp_epsv"],false,"{ftp_epsv_explain}");
    $form[]=$tpl->field_checkbox("ftp_epsv_all","{ftp_epsv_all}",$FTP_PARAMS["ftp_epsv_all"],false,"{ftp_epsv_all_explain}");
    $form[]=$tpl->field_checkbox("ftp_telnet_protocol","{ftp_telnet_protocol}",$FTP_PARAMS["ftp_telnet_protocol"],false,"{ftp_telnet_protocol_explain}");

    $security="AsSquidAdministrator";
    $html[]=$tpl->div_explain("{protocol} FTP||{allowftp_access_explain}");
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function SquidAllowFTP_apply_js():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->framework_buildjs("/proxy/acls/php/compile",
        "squid.access.artica.progress","squid.access.center.progress.log",
        "progress-squidgene-restart","LoadAjax('proxy-general-table','$page?table1=yes');");
}
function EnableParentProxy_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{squid_parent_feature}","$page?EnableParentProxy-popup=yes",650);
}
function EnableParentProxy_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableParentProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableParentProxy"));
    echo $tpl->BigCircleCheckbox("EnableParentProxy","{squid_parent_feature}",
        "{squid_parent_feature_explain}",
        $EnableParentProxy,
    "LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');LoadAjax('proxy-general-table','$page?table1=yes');");
return true;
}
function EnableParentProxy_save():bool{
    $EnableParentProxy=intval($_POST["EnableParentProxy"]);
    if($EnableParentProxy==1){
        $tt="Install";
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/parents/install");
    }else{
        $tt="Uninstall";
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/parents/uninstall");
    }
    return admin_tracks("Proxy $tt the parent Proxy feature");
}

// Tableau de bord des reglages generaux (maquette stitch_dashboard_params_proxy, 2026-10-31).
// Un modele calcule une fois (pxg_model) alimente une fonction par carte.
function table1():bool{
    $tpl=new template_admin();
    $m=pxg_model();
    $html=array();
    $html[]="<div class='ak pxg'>";
    $html[]=pxg_alerts($m);
    $html[]="<div class='pxg-grid'>";
    $html[]=pxg_card_identity($m);
    $html[]=pxg_card_infrastructure($m);
    $html[]=pxg_card_limits($m);
    $html[]=pxg_card_logging($m);
    $html[]=pxg_card_forwarding($m);
    $html[]=pxg_card_headers($m);
    $html[]="</div>";
    $html[]=pxg_card_ftp($m);
    $html[]="</div>";
    echo pxg_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).pxg_js();
    return true;
}

function pxg_get($key):string{
    return trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO($key));
}
function pxg_int($key):int{
    return intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($key));
}

function pxg_model():array{
    $m=array();
    $users=new usersMenus();
    $m["admin"]=$users->AsSquidAdministrator?true:false;
    $m["page"]=CurrentPageName();

    // Identite
    $m["anonymous"]=pxg_int("SquidAnonymousBrowsing");
    $m["add_version"]=pxg_int("SquidAddVersion");
    $m["hostname"]=pxg_get("myhostname");
    $m["uuid"]=pxg_get("SYSTEMID");
    $cache_mgr_user=pxg_get("cache_mgr_user");
    if($cache_mgr_user==""){
        $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
        if(!is_array($LicenseInfos)){$LicenseInfos=array();}
        $Wizard=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings"));
        if(!is_array($Wizard)){$Wizard=array();}
        $email=isset($LicenseInfos["EMAIL"])?trim((string)$LicenseInfos["EMAIL"]):"";
        if($email=="" && isset($Wizard["mail"])){$email=trim((string)$Wizard["mail"]);}
        if($email==""){$email="contact@articatech.com";}
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("cache_mgr_user",$email);
        $cache_mgr_user=$email;
    }
    $m["cache_mgr_user"]=$cache_mgr_user;

    // Infrastructure : MCC force le cache et la memoire a off
    $m["mcc"]=pxg_int("SquidMicrosoftConnectedCache");
    $m["parents"]=pxg_int("EnableParentProxy");
    $m["wu"]=pxg_int("AllowWindowsUpdates");
    $disableCaching=pxg_int("SquidDisableCaching");
    $disableMemory=pxg_int("SquidDisableMemoryCache");
    $m["caching_setting"]=$disableCaching==0?1:0;
    if($m["mcc"]==1){$disableCaching=1;$disableMemory=1;}
    if($disableCaching==1){$disableMemory=1;}
    $m["caching"]=$disableCaching==0?1:0;
    $m["memory"]=$disableMemory==0?1:0;
    $m["api_key"]="";
    if(pxg_int("SQUIDRESTFulEnabled")==1){$m["api_key"]=pxg_get("CategoriesRESTFulAPIKey");}
    $m["mem_size"]=pxg_int("squid_cache_mem");
    if($m["mem_size"]==0){$m["mem_size"]=256;}
    $m["mem_mode"]=pxg_get("SquidMemoryCacheMode");
    if($m["mem_mode"]==""){$m["mem_mode"]="always";}
    $m["mem_policy"]=pxg_get("SquidMemoryReplacementPolicy");
    if($m["mem_policy"]==""){$m["mem_policy"]="heap_LFUDA";}
    $m["mem_maxobj"]=pxg_int("maximum_object_size_in_memory");
    if($m["mem_maxobj"]==0){$m["mem_maxobj"]=512;}

    // Limites (request = ce que le client envoie, reply = ce qu'il recoit)
    $m["req_header"]=pxg_int("request_header_max_size");
    if($m["req_header"]==0){$m["req_header"]=128;}
    $m["rep_header"]=pxg_int("reply_header_max_size");
    if($m["rep_header"]==0){$m["rep_header"]=128;}
    $m["buffer"]=pxg_int("client_request_buffer_max_size");
    if($m["buffer"]==0){$m["buffer"]=512;}
    $m["req_body"]=pxg_int("request_body_max_size");
    $m["rep_body"]=pxg_int("reply_body_max_size");

    $client=pxg_int("SquidClientPersistentConnections");
    $server=pxg_int("SquidServerPersistentConnections");
    if($m["mcc"]==1){$client=1;$server=1;}
    $m["hacluster"]=pxg_int("HaClusterClient");
    if($m["hacluster"]==1){
        $client=pxg_int("HaClusterDisableClientPersistentConnections")==1?0:1;
        $server=pxg_int("HaClusterDisableServerPersistentConnections")==1?0:1;
    }
    $m["pconn_client"]=$client;
    $m["pconn_server"]=$server;
    $m["pconn_client_timeout"]=pxg_int("client_idle_pconn_timeout");
    if($m["pconn_client_timeout"]==0){$m["pconn_client_timeout"]=120;}
    $m["pconn_server_timeout"]=pxg_int("server_idle_pconn_timeout");
    if($m["pconn_server_timeout"]==0){$m["pconn_server_timeout"]=30;}

    // Journaux
    $m["stats_communicator"]=pxg_int("EnableStatsCommunicator");
    $m["minimal_stats"]=pxg_int("DisableMinimalSquidStatistics")==0?1:0;
    $m["no_access_logs"]=pxg_int("SquidNoAccessLogs");
    $m["access_path"]="";
    if($m["no_access_logs"]==0){
        $path=urlencode(base64_encode("/var/log/squid/access.log"));
        $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/file/realpath/$path"));
        if(is_object($json) && property_exists($json,"Info")){$m["access_path"]=trim((string)$json->Info);}
        if($m["access_path"]==""){$m["access_path"]="/var/log/squid/access.log";}
    }
    $m["debug_acls"]=pxg_int("SquidDebugAcls");
    $m["debug_all"]=pxg_int("SquidDebug5");
    $m["debug_dns"]=pxg_int("SquidDebugDNS");
    $m["log_ua"]=pxg_int("SquidLogUsersAgents");
    $m["log_iface"]=pxg_int("SquidLogIface");
    $m["log_internal"]=pxg_int("SquidLogInternalNets");
    $m["resolve"]=pxg_int("resolveIP2HOST");

    // Export des journaux
    $m["corp"]=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()?true:false;
    $m["syslog"]=array();
    $SquidSyslogAdd=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSyslogAdd"));
    if(!is_array($SquidSyslogAdd)){$SquidSyslogAdd=array();}
    if($m["corp"] && intval($SquidSyslogAdd["ENABLE"] ?? 0)==1){
        $m["syslog"][]=array("proto"=>intval($SquidSyslogAdd["UseTCPPort"] ?? 0)==1?"TCP":"UDP",
            "addr"=>trim((string)($SquidSyslogAdd["SERVER"] ?? "")).":".trim((string)($SquidSyslogAdd["RemoteSyslogPort"] ?? "")),
            "kind"=>"{pxg_remote_syslog}");
    }
    if(pxg_int("LogSinkClient")==1){
        $m["syslog"][]=array("proto"=>pxg_int("LogSinkClientTCP")==1?"TCP":"UDP",
            "addr"=>pxg_get("LogSinClientServer").":".pxg_int("LogSinkClientPort"),
            "kind"=>"{pxg_log_sink}");
    }
    $Wazuh=wazuh_settings();
    $m["wazuh"]=intval($Wazuh["ENABLE"]);
    $m["wazuh_all"]=$Wazuh["FILTER"]=="all"?1:0;

    // En-tetes HTTP et relais de confiance
    $m["xff"]=pxg_get("forwarded_for");
    if($m["xff"]==""){$m["xff"]="on";}
    $m["relays"]=array();
    $m["relays_error"]="";
    $jsb=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/balancers"),true);
    if(!is_array($jsb) || empty($jsb["Status"])){
        $m["relays_error"]=is_array($jsb) && isset($jsb["Error"])?(string)$jsb["Error"]:"REST API";
    }elseif(isset($jsb["Data"]) && is_array($jsb["Data"])){
        foreach($jsb["Data"] as $row){
            if(intval($row["enabled"] ?? 0)==1){$m["relays"][]=(string)$row["ipsrc"];}
        }
    }
    $m["xauth_supported"]=false;
    $m["xauth"]=0;
    $jsx=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/balancers/xauthuser"),true);
    if(is_array($jsx) && !empty($jsx["Status"]) && !empty($jsx["Data"]["supported"])){
        $m["xauth_supported"]=true;
        $m["xauth"]=!empty($jsx["Data"]["enabled"])?1:0;
    }

    // FTP
    $m["ftp"]=pxg_int("SquidAllowFTP");
    $m["ftp_flags"]=pxg_ftp_flags();
    $m["ftp_user"]="";
    $FTP=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidFTPParams"));
    if(is_array($FTP) && isset($FTP["ftp_user"])){$m["ftp_user"]=trim((string)$FTP["ftp_user"]);}
    return $m;
}

// Valeurs par defaut et normalisation yes/no reprises de l'ancien affichage.
function pxg_ftp_flags():array{
    $FTP=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidFTPParams"));
    if(!is_array($FTP)){$FTP=array();}
    $defaults=array("ftp_passive"=>1,"ftp_sanitycheck"=>1,"ftp_epsv"=>1,"ftp_epsv_all"=>0,"ftp_telnet_protocol"=>1);
    $flags=array();
    foreach($defaults as $key=>$def){
        $v=isset($FTP[$key])?$FTP[$key]:null;
        if($v===null || $v===""){$v=$def;}
        if($v==="yes"){$v=1;}
        if($v==="no"){$v=0;}
        if(intval($v)==1){$flags[]="{".$key."}";}
    }
    return $flags;
}

// ---- Briques communes ----

function pxg_esc($s):string{
    return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
}

function pxg_card_open($m,$icon,$title,$editjs="",$extra="",$id=""):string{
    $btn="";
    if($editjs<>"" && $m["admin"]){
        $btn="<button type='button' class='ak-btn ak-btn-ghost ak-btn-sm' aria-label=\"{pxg_edit}: $title\" OnClick=\"$editjs\">"
            ."<span class='pxg-edit-ico'><i class='fas fa-pen'></i></span>{pxg_edit}</button>";
    }
    $idattr=$id<>""?" id='$id'":"";
    return "<section class='ak-card pxg-card $extra'$idattr><div class='pxg-head'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='$icon'></i></span>"
        ."<h3 class='pxg-title'>$title</h3>$btn</div><div class='pxg-body'>";
}
function pxg_card_close():string{
    return "</div></section>";
}

// Une ligne ; $js non vide = la ligne entiere ouvre l'editeur (chevron visible).
function pxg_row($m,$label,$value,$js="",$sub=""):string{
    $subhtml=$sub<>""?"<div class='pxg-sub'>$sub</div>":"";
    if($js<>"" && $m["admin"]){
        return "<div class='pxg-row pxg-click' role='button' tabindex='0' OnClick=\"$js\" "
            ."onkeydown=\"if(event.key==='Enter'||event.key===' '){this.click();return false;}\">"
            ."<div class='pxg-label'>$label$subhtml</div><div class='pxg-value'>$value</div>"
            ."<span class='pxg-chev' aria-hidden='true'><i class='fas fa-chevron-right'></i></span></div>";
    }
    return "<div class='pxg-row'><div class='pxg-label'>$label$subhtml</div><div class='pxg-value'>$value</div></div>";
}

function pxg_chip_bool($on):string{
    if(intval($on)==1){
        return "<span class='ak-pill ak-ok'><span class='pxg-chip-ico'><i class='fas fa-circle-check'></i></span>{enabled}</span>";
    }
    return "<span class='ak-pill ak-off'><span class='pxg-chip-ico'><i class='fas fa-circle-minus'></i></span>{disabled}</span>";
}

function pxg_tag($text,$title=""):string{
    $t=$title<>""?" title='".pxg_esc($title)."'":"";
    return "<span class='pxg-tag'$t>".pxg_esc($text)."</span>";
}

function pxg_copy_btn($text,$label):string{
    return "<button type='button' class='ak-act' aria-label=\"{pxg_copy}: $label\" title=\"{pxg_copy}\" "
        ."data-copy='".pxg_esc($text)."' OnClick=\"PxgCopy(this);\"><i class='far fa-copy'></i></button>";
}

// Interrupteur : il n'agit pas directement, il ouvre la confirmation (pxg-toggle-js).
function pxg_switch($m,$key,$on,$label,$locked=false):string{
    $checked=intval($on)==1?"true":"false";
    $state=intval($on)==1?"{enabled}":"{disabled}";
    $cls="ak-sw";
    $disabled="";
    $js="Loadjs('{$m["page"]}?pxg-toggle-js=$key');";
    if($locked || !$m["admin"]){
        $disabled=" disabled";
        $js="";
    }
    $lock=$locked?"<span class='pxg-lock' aria-hidden='true'><i class='fas fa-lock'></i></span>":"";
    return "<span class='pxg-sw-wrap'>$lock<span class='pxg-sw-state'>$state</span>"
        ."<button type='button' role='switch' aria-checked='$checked' aria-label=\"$label\" class='$cls'$disabled OnClick=\"$js\">"
        ."</button></span>";
}

function pxg_size_kb($kb):string{
    if(intval($kb)==0){return "<span class='pxg-unlimited'>{pxg_unlimited}</span>";}
    return "<strong>".intval($kb)."</strong>&nbsp;KB";
}

// ---- Bandeau d'alertes ----

function pxg_alerts($m):string{
    $pills=array();
    if($m["debug_acls"]==1){
        $pills[]="<a class='ak-pill ak-err' href=\"javascript:blur();\" OnClick=\"PxgGo('pxg-logging')\"><span class='pxg-pill-ico'><i class='fas fa-bug'></i></span>"
            ."<span><strong>{acl_in_debug_mode}</strong> <span class='pxg-pill-note'>{pxg_debug_acls_note}</span></span></a>";
    }
    if($m["debug_all"]==1 || $m["debug_dns"]==1){
        $pills[]="<a class='ak-pill ak-warn' href=\"javascript:blur();\" OnClick=\"PxgGo('pxg-logging')\"><span class='pxg-pill-ico'><i class='fas fa-bug'></i></span>"
            ."<strong>{pxg_debug_on}</strong></a>";
    }
    if($m["ftp"]==1){
        $pills[]="<a class='ak-pill ak-warn' href=\"javascript:blur();\" OnClick=\"PxgGo('pxg-ftp')\"><span class='pxg-pill-ico'><i class='fas fa-triangle-exclamation'></i></span>"
            ."<strong>{pxg_alert_ftp}</strong></a>";
    }
    if($m["mcc"]==1){
        $pills[]="<a class='ak-pill ak-off' href=\"javascript:blur();\" OnClick=\"PxgGo('pxg-infra')\"><span class='pxg-pill-ico'><i class='fas fa-circle-info'></i></span>"
            ."<span>{pxg_alert_mcc}</span></a>";
    }
    if(count($pills)==0){return "";}
    return "<div class='pxg-alerts' role='region' aria-label=\"{pxg_attention}\">"
        ."<span class='pxg-alerts-title'><span class='pxg-pill-ico'><i class='fas fa-shield-halved'></i></span>{pxg_attention}</span>"
        .@implode("",$pills)."</div>";
}

// ---- Cartes ----

function pxg_card_identity($m):string{
    $page=$m["page"];
    $h=array();
    $h[]=pxg_card_open($m,"fas fa-id-card","{identity}","Loadjs('$page?section-identity-js=yes')");
    $h[]=pxg_row($m,"{pxg_anonymous_browsing}",pxg_chip_bool($m["anonymous"]));
    $h[]=pxg_row($m,"{display_servername_version}",pxg_chip_bool($m["add_version"]));
    $host=$m["hostname"]==""?"<span class='pxg-muted'>{pxg_none}</span>"
        :pxg_tag($m["hostname"],$m["hostname"]).pxg_copy_btn($m["hostname"],"{visible_hostname}");
    $h[]=pxg_row($m,"{visible_hostname}",$host);
    $uuid="<span class='pxg-muted'>{pxg_none}</span>";
    if($m["uuid"]<>""){
        $short=$m["uuid"];
        if(strlen($short)>20){$short=pxg_esc(substr($short,0,8))."&hellip;".pxg_esc(substr($short,-8));}else{$short=pxg_esc($short);}
        $uuid="<span class='pxg-tag' title='".pxg_esc($m["uuid"])."'>$short</span>".pxg_copy_btn($m["uuid"],"{unique_hostname}");
    }
    $h[]=pxg_row($m,"{unique_hostname}",$uuid);
    $h[]=pxg_row($m,"{cache_mgr_user}","<span class='pxg-strong'>".pxg_esc($m["cache_mgr_user"])."</span>");
    $h[]=pxg_card_close();
    return @implode("",$h);
}

function pxg_card_infrastructure($m):string{
    $page=$m["page"];
    $h=array();
    $h[]=pxg_card_open($m,"fas fa-sitemap","{infrastructure}","","","pxg-infra");
    $h[]=pxg_row($m,"{squid_parent_feature}",pxg_chip_bool($m["parents"]),"Loadjs('$page?EnableParentProxy-js=yes')");

    if($m["mcc"]==1){
        $h[]=pxg_row($m,"{enable_caching_squid}",pxg_switch($m,"caching",0,"{enable_caching_squid}",true),"","{pxg_forced_by_mcc}");
    }else{
        $h[]=pxg_row($m,"{enable_caching_squid}",pxg_switch($m,"caching",$m["caching"],"{enable_caching_squid}"));
    }
    $h[]=pxg_row($m,"{ProxyDedicateMicrosoftRules}",pxg_switch($m,"wu",$m["wu"],"{ProxyDedicateMicrosoftRules}"));
    $h[]=pxg_row($m,"Microsoft Connected Cache",pxg_chip_bool($m["mcc"]),"Loadjs('$page?mmc-js=yes')");

    if($m["api_key"]<>""){
        $key=$m["api_key"];
        $masked="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;".pxg_esc(substr($key,-4));
        $val="<span class='pxg-tag'><span id='pxg-key-mask'>$masked</span>"
            ."<span id='pxg-key-clear' style='display:none'>".pxg_esc($key)."</span></span>"
            ."<button type='button' class='ak-act' aria-label=\"{pxg_show_key}\" title=\"{pxg_show_key}\" aria-pressed='false' OnClick=\"PxgReveal(this);\">"
            ."<i class='far fa-eye'></i></button>"
            .pxg_copy_btn($key,"{API_KEY}");
        $h[]=pxg_row($m,"{API_KEY}",$val);
    }

    $policies=array("lru"=>"LRU","heap_GDSF"=>"Heap GDSF","heap_LFUDA"=>"Heap LFUDA","heap_LRU"=>"Heap LRU");
    $policyTitles=array("lru"=>"{cache_lru}","heap_GDSF"=>"{heap_GDSF}","heap_LFUDA"=>"{heap_LFUDA}","heap_LRU"=>"{heap_LRU}");
    if($m["memory"]==1){
        $pol=isset($policies[$m["mem_policy"]])?$policies[$m["mem_policy"]]:$m["mem_policy"];
        $polTitle=isset($policyTitles[$m["mem_policy"]])?$policyTitles[$m["mem_policy"]]:"";
        $facts="<span class='pxg-facts'>"
            ."<span class='pxg-fact'><strong>".intval($m["mem_size"])."</strong>&nbsp;MB</span>"
            ."<span class='pxg-fact'>{pxg_mode}: <strong>".pxg_esc(ucfirst($m["mem_mode"]))."</strong></span>"
            ."<span class='pxg-fact' title=\"$polTitle\">{pxg_policy}: <strong>".pxg_esc($pol)."</strong></span>"
            ."<span class='pxg-fact'>{pxg_max_object}: <strong>".intval($m["mem_maxobj"])."</strong>&nbsp;KB</span>"
            ."</span>";
        $h[]=pxg_row($m,"{central_memory}",$facts,"Loadjs('$page?section-memory-js=yes')");
    }else{
        $sub="";
        $js="Loadjs('$page?section-memory-js=yes')";
        if($m["mcc"]==1){$sub="{pxg_forced_by_mcc}";$js="";}
        elseif($m["caching"]==0){$sub="{pxg_forced_by_caching}";}
        $h[]=pxg_row($m,"{central_memory}",pxg_chip_bool(0),$js,$sub);
    }
    $h[]=pxg_card_close();
    return @implode("",$h);
}

function pxg_card_limits($m):string{
    $page=$m["page"];
    $h=array();
    $h[]=pxg_card_open($m,"fas fa-gauge-simple","{pxg_limits}","Loadjs('$page?section-limit-js=yes')");
    $h[]=pxg_row($m,"{http_headers}","<span class='pxg-inline'>{pxg_request} ".pxg_size_kb($m["req_header"])
        ."<span class='pxg-dot'>&middot;</span>{pxg_reply} ".pxg_size_kb($m["rep_header"])
        ."<span class='pxg-dot'>&middot;</span>{buffer} ".pxg_size_kb($m["buffer"])."</span>");
    $h[]=pxg_row($m,"{Body}","<span class='pxg-inline'>{pxg_request} ".pxg_size_kb($m["req_body"])
        ."<span class='pxg-dot'>&middot;</span>{pxg_reply} ".pxg_size_kb($m["rep_body"])."</span>");
    $h[]=pxg_pconn_row($m);
    $h[]=pxg_card_close();
    return @implode("",$h);
}

// Chemin des connexions persistantes : client -> proxy -> sites, un segment par cote.
function pxg_pconn_row($m):string{
    $page=$m["page"];
    $sub=$m["hacluster"]==1?"{pxg_managed_hacluster}":"";
    if($m["pconn_client"]==0 && $m["pconn_server"]==0){
        return pxg_row($m,"{persistent_connections}",pxg_chip_bool(0),"Loadjs('$page?section-pconnections-js=yes')",$sub);
    }
    $seg=function($on,$seconds){
        if(intval($on)==1){
            return "<span class='pxg-seg'><span class='pxg-seg-lbl'>".intval($seconds)."&nbsp;s</span></span>";
        }
        return "<span class='pxg-seg pxg-seg-off'><span class='pxg-seg-lbl'>{pxg_off}</span></span>";
    };
    $path="<span class='pxg-path' role='img' aria-label=\"{persistent_connections}\">"
        ."<span class='pxg-node'><span class='pxg-node-ico'><i class='fas fa-laptop'></i></span>{pxg_client}</span>"
        .$seg($m["pconn_client"],$m["pconn_client_timeout"])
        ."<span class='pxg-node pxg-node-main'><span class='pxg-node-ico'><i class='fas fa-server'></i></span>{APP_SQUID}</span>"
        .$seg($m["pconn_server"],$m["pconn_server_timeout"])
        ."<span class='pxg-node'><span class='pxg-node-ico'><i class='fas fa-cloud'></i></span>{pxg_domains}</span>"
        ."</span>";
    return "<div class='pxg-row pxg-row-stack".($m["admin"]?" pxg-click":"")."'"
        .($m["admin"]?" role='button' tabindex='0' OnClick=\"Loadjs('$page?section-pconnections-js=yes')\" onkeydown=\"if(event.key==='Enter'||event.key===' '){this.click();return false;}\"":"")
        ."><div class='pxg-label'>{persistent_connections}".($sub<>""?"<div class='pxg-sub'>$sub</div>":"")."</div>"
        ."<div class='pxg-value pxg-value-full'>$path</div></div>";
}

function pxg_card_logging($m):string{
    $page=$m["page"];
    $h=array();
    $h[]=pxg_card_open($m,"fas fa-list","{pxg_logging}","Loadjs('$page?section-table1-js=yes')","","pxg-logging");
    if($m["no_access_logs"]==0){
        $access=pxg_tag($m["access_path"]);
    }else{
        $access=pxg_chip_bool(0);
    }
    $h[]=pxg_row($m,"{access_events}",$access,"Loadjs('$page?SquidNoAccessLogs-js=yes')");
    if($m["stats_communicator"]==0){
        $h[]=pxg_row($m,"{minimal_statistics}",pxg_chip_bool($m["minimal_stats"]));
    }
    $h[]=pxg_row($m,"{UserAgentInLogs}",pxg_chip_bool($m["log_ua"]));
    $h[]=pxg_row($m,"{outgoing_interface}",pxg_chip_bool($m["log_iface"]));
    $h[]=pxg_row($m,"{log_requests_to_internals}",pxg_chip_bool($m["log_internal"]));
    $h[]=pxg_row($m,"{try_resolve_ip_to_hostname}",pxg_chip_bool($m["resolve"]));
    $dbg=array();
    if($m["debug_acls"]==1){$dbg[]="<span class='ak-pill ak-err'><span class='pxg-chip-ico'><i class='fas fa-bug'></i></span>ACLs</span>";}
    if($m["debug_dns"]==1){$dbg[]="<span class='ak-pill ak-warn'><span class='pxg-chip-ico'><i class='fas fa-bug'></i></span>DNS</span>";}
    if($m["debug_all"]==1){$dbg[]="<span class='ak-pill ak-warn'><span class='pxg-chip-ico'><i class='fas fa-triangle-exclamation'></i></span>{all}</span>";}
    $dbgval=count($dbg)==0?"<span class='pxg-muted'>{pxg_none}</span>":"<span class='pxg-chips'>".@implode("",$dbg)."</span>";
    $h[]=pxg_row($m,"{debug_mode}",$dbgval);
    $h[]=pxg_card_close();
    return @implode("",$h);
}

function pxg_card_forwarding($m):string{
    $page=$m["page"];
    $h=array();
    $h[]=pxg_card_open($m,"fas fa-satellite-dish","{pxg_log_forwarding}");
    if(!$m["corp"] && count($m["syslog"])==0){
        $h[]=pxg_row($m,"{remote_syslog_server}",pxg_chip_bool(0),"","{pxg_corp_required}");
    }elseif(count($m["syslog"])==0){
        $h[]=pxg_row($m,"{remote_syslog_server}",pxg_chip_bool(0),"Loadjs('$page?squid-syslog-js=yes')");
    }else{
        $eps=array();
        foreach($m["syslog"] as $ep){
            $eps[]="<span class='pxg-ep'><span class='ak-tag ak-off'>{$ep["proto"]}</span>"
                ."<span class='pxg-ep-addr' title='".pxg_esc($ep["addr"])."'>".pxg_esc($ep["addr"])."</span><span class='pxg-ep-kind'>{$ep["kind"]}</span></span>";
        }
        $js=$m["corp"]?"Loadjs('$page?squid-syslog-js=yes')":"";
        $h[]=pxg_row($m,"{remote_syslog_server}","<span class='pxg-eps'>".@implode("",$eps)."</span>",$js);
    }
    if($m["wazuh"]==1){
        $txt=$m["wazuh_all"]==1?"{all_events}":"{security_events}";
        $val="<span class='ak-pill ak-ok'><span class='pxg-chip-ico'><i class='fas fa-circle-check'></i></span>$txt</span>";
        $h[]=pxg_row($m,"Wazuh",$val,"Loadjs('$page?wazuh-js=yes')","/var/log/squid/wazuh/access.json");
    }else{
        $h[]=pxg_row($m,"Wazuh",pxg_chip_bool(0),"Loadjs('$page?wazuh-js=yes')");
    }
    $h[]=pxg_card_close();
    return @implode("",$h);
}

function pxg_card_headers($m):string{
    $page=$m["page"];
    $h=array();
    $h[]=pxg_card_open($m,"fas fa-shuffle","{pxg_headers_relays}","Loadjs('$page?section-table1-js=yes')");
    $modes=array("on"=>"{enabled}","off"=>"{disabled}","transparent"=>"{pxg_xff_pass}","delete"=>"{pxg_xff_anonymous}","truncate"=>"{pxg_xff_hide}");
    $seg=array();
    foreach($modes as $key=>$label){
        $cur=$key==$m["xff"];
        $seg[]="<span class='pxg-segm".($cur?" pxg-segm-cur":"")."'".($cur?" aria-current='true'":"").">$label</span>";
    }
    $h[]=pxg_row($m,"X-Forwarded-For","<span class='pxg-segmented' role='group' aria-label='X-Forwarded-For'>".@implode("",$seg)."</span>");

    $relaysJs="Loadjs('fw.proxy.general.follow_x_forwarded_for.php')";
    if($m["relays_error"]<>""){
        $val="<span class='ak-state ak-err'><span class='pxg-chip-ico'><i class='fas fa-triangle-exclamation'></i></span>{pxg_relays_error} ".pxg_esc($m["relays_error"])."</span>";
        $h[]=pxg_row($m,"{follow_x_forwarded_for}",$val,$relaysJs);
    }elseif(count($m["relays"])==0){
        $h[]=pxg_row($m,"{follow_x_forwarded_for}","<span class='pxg-muted'>{pxg_none}</span>",$relaysJs);
    }else{
        $chips=array();
        $max=6;
        foreach($m["relays"] as $i=>$ip){
            if($i>=$max){break;}
            $chips[]=pxg_tag($ip);
        }
        $more=count($m["relays"])-$max;
        if($more>0){$chips[]="<span class='pxg-tag pxg-tag-more' title='".pxg_esc(@implode(", ",$m["relays"]))."'>+$more</span>";}
        $sub=count($m["relays"])." {pxg_trusted_relays}";
        $h[]=pxg_row($m,"{follow_x_forwarded_for}","<span class='pxg-chips'>".@implode("",$chips)."</span>",$relaysJs,$sub);
    }
    if($m["xauth_supported"]){
        $h[]=pxg_row($m,"{xauthuser_header}",pxg_chip_bool($m["xauth"]),$relaysJs);
    }
    $h[]=pxg_card_close();
    return @implode("",$h);
}

function pxg_card_ftp($m):string{
    $page=$m["page"];
    $h=array();
    $h[]=pxg_card_open($m,"fas fa-folder-tree","{pxg_ftp_protocol}","Loadjs('$page?section-ftp-js=yes')",$m["ftp"]==1?"pxg-card-warn pxg-card-wide":"pxg-card-wide","pxg-ftp");
    $h[]=pxg_row($m,"{allowftp_access}",pxg_switch($m,"ftp",$m["ftp"],"{allowftp_access}"));
    $flags=array();
    foreach($m["ftp_flags"] as $f){
        $flags[]="<span class='pxg-flag'><span class='pxg-chip-ico'><i class='fas fa-check'></i></span>$f</span>";
    }
    if($m["ftp_user"]<>""){
        $flags[]="<span class='pxg-flag'><span class='pxg-chip-ico'><i class='fas fa-user'></i></span>{pxg_ftp_login}: ".pxg_esc($m["ftp_user"])."</span>";
    }
    $val=count($flags)==0?"<span class='pxg-muted'>{pxg_none}</span>":"<span class='pxg-chips'>".@implode("",$flags)."</span>";
    $h[]=pxg_row($m,"{parameters}",$val);
    $h[]=pxg_card_close();
    return @implode("",$h);
}

// ---- Bascules confirmees (cache, Windows Update, FTP) ----

function pxg_toggle_keys():array{
    return array(
        "caching"=>array("setting"=>"SquidDisableCaching","inverted"=>true,"label"=>"{enable_caching_squid}"),
        "wu"=>array("setting"=>"AllowWindowsUpdates","inverted"=>false,"label"=>"{ProxyDedicateMicrosoftRules}"),
        "ftp"=>array("setting"=>"SquidAllowFTP","inverted"=>false,"label"=>"{allowftp_access}"),
    );
}

// Etat affiche (1 = actif) d'une bascule, en tenant compte de l'inversion de SquidDisableCaching.
function pxg_toggle_state($key):int{
    $keys=pxg_toggle_keys();
    $v=pxg_int($keys[$key]["setting"]);
    if($keys[$key]["inverted"]){return $v==1?0:1;}
    return $v==1?1:0;
}

function pxg_toggle_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){return $tpl->js_no_privileges();}
    $key=preg_replace('/[^a-z]/','',(string)$_GET["pxg-toggle-js"]);
    $keys=pxg_toggle_keys();
    if(!isset($keys[$key])){return false;}
    $next=pxg_toggle_state($key)==1?"off":"on";
    $after="LoadAjax('proxy-general-table','$page?table1=yes');";
    if($key=="ftp"){$after=SquidAllowFTP_apply_js();}
    return $tpl->js_dialog_confirm_action("{pxg_confirm_{$key}_{$next}}","pxg-toggle-$key","none",$after);
}

function pxg_toggle_save($key):bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){echo $tpl->post_error("{no_privileges}");return false;}
    $keys=pxg_toggle_keys();
    if(!isset($keys[$key])){return false;}
    if($key=="caching" && pxg_int("SquidMicrosoftConnectedCache")==1){
        echo $tpl->post_error("{pxg_forced_by_mcc}");
        return false;
    }
    $setting=$keys[$key]["setting"];
    $value=pxg_int($setting)==1?0:1;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO($setting,$value);
    // FTP vit dans la chaine http_access : la reconstruction complete part du $jsafter.
    if($key<>"ftp"){$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/ccm");}
    return admin_tracks("Set Proxy $setting to $value");
}

function pxg_style():string{
    if(isset($GLOBALS["PXG_STYLE"])){return "";}
    $GLOBALS["PXG_STYLE"]=true;
    $accent="#10b981";$dark="#0d9488";$soft="rgba(16,185,129,.12)";$line="rgba(16,185,129,.35)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    return "<style>
.pxg{margin-top:10px}
.pxg-alerts{display:flex;flex-wrap:wrap;align-items:center;gap:8px 10px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:12px 16px;margin-bottom:16px}
.pxg-alerts-title{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#3d4947;margin-right:4px}
.pxg-alerts .ak-pill{padding:6px 12px}
.pxg-alerts .ak-pill:focus-visible{outline:2px solid $dark;outline-offset:2px}
.pxg-pill-ico{font-size:13px;display:inline-flex}
.pxg-pill-note{font-weight:400}
.pxg-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(480px,1fr));gap:16px;margin-bottom:16px}
.pxg-card{padding:18px 20px}
.pxg-card-warn{border-left:4px solid #865300}
.pxg-head{display:flex;align-items:center;gap:10px;padding-bottom:12px;border-bottom:1px solid #eef2f6}
.pxg-title{font-size:15px;font-weight:600;margin:0;flex:1 1 auto;color:#111d23;text-transform:none}
.pxg-click:focus-visible{outline:2px solid $dark;outline-offset:2px}
.pxg-edit-ico{font-size:12px;display:inline-flex}
.pxg-row{display:flex;align-items:center;gap:16px;min-height:44px;padding:9px 0;border-top:1px solid #f1f5f9}
.pxg-body .pxg-row:first-child{border-top:0}
.pxg-click{cursor:pointer;border-radius:6px;margin:0 -8px;padding-left:8px;padding-right:8px}
.pxg-click:hover{background:#f8fafc}
.pxg-label{flex:1 1 40%;min-width:150px;font-size:13px;color:#3d4947;line-height:18px}
.pxg-sub{font-size:12px;color:#6d7a77;margin-top:2px}
.pxg-value{flex:0 1 auto;display:flex;max-width:72%;align-items:center;justify-content:flex-end;flex-wrap:wrap;gap:6px;font-size:13px;color:#111d23;text-align:right;min-width:0}
.pxg-row-stack{flex-wrap:wrap}
.pxg-value-full{flex:1 1 100%;max-width:none;justify-content:flex-start}
.pxg-chev{color:#94a3b8;font-size:12px;display:inline-flex}
.pxg-chip-ico{font-size:12px;display:inline-flex}
.pxg-chips{display:flex;flex-wrap:wrap;gap:6px;justify-content:flex-end}
.pxg-tag{display:inline-block;padding:3px 8px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;font-size:13px;color:#111d23;max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:middle}
.pxg-tag-more{background:$soft;border-color:$line;color:#00504a;font-weight:600;cursor:help}
.pxg-strong{font-weight:600;word-break:break-all}
.pxg-muted{color:#6d7a77;font-style:italic}
.pxg-unlimited{color:#6d7a77;font-style:italic}
.pxg-inline{display:inline-flex;flex-wrap:wrap;gap:4px 6px;justify-content:flex-end;align-items:baseline}
.pxg-dot{color:#94a3b8}
.ak-act.pxg-done{color:$dark}
.pxg-facts{display:flex;flex-wrap:wrap;gap:6px;justify-content:flex-end}
.pxg-fact{display:inline-block;padding:4px 8px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;font-size:12px;color:#3d4947;white-space:nowrap}
.pxg-fact strong{color:#111d23}
.pxg-sw-wrap{display:inline-flex;align-items:center;gap:8px}
.pxg-sw-state{font-size:12px;font-weight:600;color:#475569}
.pxg-lock{color:#6d7a77;font-size:12px;display:inline-flex}
.pxg-path{display:flex;align-items:center;gap:0;width:100%;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc}
.pxg-node{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#3d4947;white-space:nowrap;padding:3px 8px;border-radius:6px}
.pxg-node-main{background:$soft;color:#00504a}
.pxg-node-ico{font-size:12px;display:inline-flex}
.pxg-seg{flex:1 1 auto;min-width:56px;height:2px;background:$accent;position:relative;margin:0 6px}
.pxg-seg-off{background:repeating-linear-gradient(90deg,#cbd5e1 0 6px,transparent 6px 10px)}
.pxg-seg-lbl{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:0 6px;font-size:12px;font-weight:600;color:#111d23;white-space:nowrap;line-height:18px}
.pxg-seg-off .pxg-seg-lbl{color:#6d7a77;font-style:italic}
.pxg-eps{display:flex;flex-direction:column;gap:6px;align-items:flex-end}
.pxg-ep{display:inline-flex;align-items:center;gap:8px;padding:4px 8px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;font-size:13px}
.pxg-ep-addr{max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pxg-ep-kind{white-space:nowrap;font-size:12px;color:#6d7a77}
.pxg-segmented{display:inline-flex;flex-wrap:wrap;border:1px solid #e2e8f0;border-radius:8px;background:#f1f5f9;padding:2px;gap:2px}
.pxg-segm{padding:4px 10px;border-radius:6px;font-size:12px;color:#475569;white-space:nowrap}
.pxg-segm-cur{background:#006a62;color:#fff;font-weight:600}
.pxg-flag{display:inline-flex;align-items:center;gap:6px;padding:3px 9px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;color:#111d23;background:#fff;white-space:nowrap}
.pxg-flag .pxg-chip-ico{color:$dark}
</style>";
}

function pxg_js():string{
    return "<script>
function PxgCopy(btn){
    var t=btn.getAttribute('data-copy')||'';
    var ok=function(){btn.className+=' pxg-done';setTimeout(function(){btn.className=btn.className.replace(/ ?pxg-done/g,'');},1200);};
    if(navigator.clipboard && window.isSecureContext){navigator.clipboard.writeText(t).then(ok,function(){PxgCopyOld(t);ok();});return;}
    PxgCopyOld(t);ok();
}
function PxgCopyOld(t){
    var a=document.createElement('textarea');a.value=t;a.setAttribute('readonly','');
    a.style.position='absolute';a.style.left='-9999px';document.body.appendChild(a);a.select();
    try{document.execCommand('copy');}catch(e){}
    document.body.removeChild(a);
}
function PxgGo(id){var e=document.getElementById(id);if(e&&e.scrollIntoView){e.scrollIntoView({behavior:'smooth',block:'start'});}}
function PxgReveal(btn){
    var m=document.getElementById('pxg-key-mask'),c=document.getElementById('pxg-key-clear');
    if(!m||!c){return;}
    var show=c.style.display=='none';
    c.style.display=show?'':'none';m.style.display=show?'none':'';
    btn.setAttribute('aria-pressed',show?'true':'false');
}
NoSpinner();
</script>";
}
// Fin du tableau de bord des reglages generaux

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
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-squidgene-restart')";
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
    $SquidAllowFTPBefore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAllowFTP"));
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

    // The FTP allow/deny rule is in the main squid.conf: only the full rebuild applies it.
    if(isset($_POST["SquidAllowFTP"])){
        $SquidAllowFTP=intval($_POST["SquidAllowFTP"]);
        if($SquidAllowFTP<>$SquidAllowFTPBefore){
            $sock->REST_API("/proxy/acls/php/compile");
            admin_tracks("Set Proxy Allow FTP protocol to $SquidAllowFTP");
        }
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

// Interrupteur general de la restriction des ports distants (SquidAllowRemotePorts=1
// = le proxy accepte tous les ports). Ces deux gestionnaires n'avaient aucun controle
// de droits : un simple GET suffisait a couper la restriction.
function SafePorts_can_edit():bool{
    $users=new usersMenus();
    return $users->AsSquidAdministrator || $users->AsDansGuardianAdministrator;
}
function SafePorts_disable(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!SafePorts_can_edit()){header("content-type: application/x-javascript");echo $tpl->js_no_privileges();return;}
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"))==1){
        header("content-type: application/x-javascript");echo "alert('Cluster Client Enabled');";return;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidAllowRemotePorts",1);
    admin_tracks("Proxy remote ports restriction disabled (all destination ports allowed)");
    $jsrestart=$tpl->framework_buildjs("/proxy/http/access/default",
        "squid.access.center.progress",
        "squid.access.center.progress.log",
    "squid-safe-port-progress","LoadAjaxSilent('SafePortTable','$page?SafePortTable=yes&applied=yes')");

    header("content-type: application/x-javascript");
    echo "$jsrestart;";

}
function SafePorts_enable(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!SafePorts_can_edit()){header("content-type: application/x-javascript");echo $tpl->js_no_privileges();return;}
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"))==1){
        header("content-type: application/x-javascript");echo "alert('Cluster Client Enabled');";return;
    }
    $SquidDisableAllFilters=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableAllFilters"));
    if($SquidDisableAllFilters==1){
        echo "LoadAjaxSilent('SafePortTable','$page?SafePortTable=yes');";
        return;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidAllowRemotePorts",0);
    admin_tracks("Proxy remote ports restriction enabled (only listed ports allowed)");
    $jsrestart=$tpl->framework_buildjs("/proxy/http/access/default",
        "squid.access.center.progress",
        "squid.access.center.progress.log",
        "squid-safe-port-progress","LoadAjaxSilent('SafePortTable','$page?SafePortTable=yes&applied=yes')");

    header("content-type: application/x-javascript");
    echo "$jsrestart;";

}
// ---------------------------------------------------------------------------
// Onglet « Remote ports » (maquette stitch_proxy_remote_ports_redesign).
// Liste blanche des ports de destination : table safeports de proxy.db, mise en
// service par /proxy/http/access/default (squid/AclSafePorts/SafePorts.go).
// Ce que le generateur fait, et que la page doit dire tel quel :
//   - 80 et 443 sont toujours dans Safe_ports, 443 toujours dans SSL_ports ;
//   - 20, 21 et 80 ne vont jamais dans SSL_ports ;
//   - un port qui n'est pas un entier seul (plage, liste) est ignore (StrToInt -> 0) ;
//   - aucune ligne HTTP active : 20 et 21 sont ajoutes ; aucune ligne HTTPS active
//     hors 20/21/80/443 : 9000 est ajoute.
// Les modifications de la liste ne sont en service qu'apres « Apply configuration » :
// la page ne peut le savoir que pour la session courante ($_SESSION["SFP_PENDING"]).

function SafePorts_table(){
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    if(isset($_GET["applied"])){ unset($_SESSION["SFP_PENDING"]); }
    $m=sfp_model();
    $h=array();
    $h[]="<div id='squid-safe-port-progress'></div>";
    $h[]=sfp_banner($m);
    $h[]=sfp_state_bar($m);
    $h[]=sfp_toolbar($m);
    $h[]=sfp_list($m);
    echo sfp_style().sfp_js($m)
        ."<div class='sfp'>".$tpl->_ENGINE_parse_body(@implode("\n",$h))."</div>"
        ."<script>NoSpinner();SfpApply();\n".sfp_tiny($tpl,$m)."</script>";
}

/** Le modele unique de l'onglet. */
function sfp_model():array{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $m=array("allow_all"=>intval($sock->GET_INFO("SquidAllowRemotePorts"))==1,
        "all_filters_off"=>intval($sock->GET_INFO("SquidDisableAllFilters"))==1,
        "cluster"=>intval($sock->GET_INFO("PowerDNSEnableClusterSlave"))==1,
        "pending"=>!empty($_SESSION["SFP_PENDING"]),
        "rows"=>array(),"n_all"=>0,"n_on"=>0,"n_off"=>0,"http_count"=>0,"ssl_count"=>0,"error"=>"");
    if($m["all_filters_off"]){ $m["allow_all"]=true; }
    $m["can"]=SafePorts_can_edit() && !$m["cluster"];

    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $results=$q->QUERY_SQL("SELECT * FROM safeports");
    if(!$q->ok){ $m["error"]=(string)$q->mysql_error; }
    foreach((is_array($results) ? $results : array()) as $l){
        $raw=trim((string)($l["port"] ?? ""));
        $num=preg_match('/^[0-9]{1,5}$/',$raw) ? intval($raw) : 0;
        $r=array("id"=>intval($l["ID"] ?? 0),"raw"=>$raw,"port"=>$num,"desc"=>(string)($l["description"] ?? ""),
            "http"=>intval($l["http"] ?? 0)==1,"ssl"=>intval($l["ssl"] ?? 0)==1,"enabled"=>intval($l["enabled"] ?? 0)==1,
            "ignored"=>($num<1 || $num>65535));
        $m["rows"][]=$r;
        $m["n_all"]++;
        if($r["enabled"]){ $m["n_on"]++; } else { $m["n_off"]++; }
        if($r["enabled"] && !$r["ignored"] && $r["http"]){ $m["http_count"]++; }
        if($r["enabled"] && !$r["ignored"] && $r["ssl"] && !in_array($num,array(20,21,80,443),true)){ $m["ssl_count"]++; }
    }
    // Tri numerique ; les entrees ignorees (plages, listes) a la fin.
    usort($m["rows"],function($a,$b){
        if($a["ignored"]!=$b["ignored"]){ return $a["ignored"] ? 1 : -1; }
        return $a["port"]-$b["port"];
    });
    return $m;
}

/** Ce que le generateur impose a un port, quoi que dise la table. */
function sfp_locks(array $r):array{
    $p=$r["port"];
    return array(
        "http"=>($p==80 || $p==443) ? "on" : "",
        "ssl"=>($p==443) ? "on" : (in_array($p,array(20,21,80),true) ? "off" : ""),
        "always"=>($p==80 || $p==443));
}

function sfp_chip(string $cls,string $ico,string $text,string $title=""):string{
    $t=$title!=="" ? " title=\"$title\"" : "";
    return "<span class='sfp-chip sfp-chip-$cls'$t><i class='$ico' aria-hidden='true'></i>$text</span>";
}

function sfp_fmt(string $token,$a,$b=null):string{
    $tpl=new template_admin();
    $t=$tpl->_ENGINE_parse_body($token);
    $t=preg_replace('/%s/',(string)$a,$t,1);
    if($b!==null){ $t=preg_replace('/%s/',(string)$b,$t,1); }
    return $t;
}

function sfp_jstext(string $token):string{
    $tpl=new template_admin();
    $t=str_ireplace(array("<br>","<br/>","<br />")," ",$tpl->_ENGINE_parse_body($token));
    $t=trim(preg_replace('/\s+/'," ",html_entity_decode(strip_tags($t),ENT_QUOTES,"UTF-8")));
    return str_replace(array("\\","'","</"),array("\\\\","\\'","<\\/"),$t);
}

// --- 1. Bandeau d'accroche

function sfp_banner(array $m):string{
    $c=array();
    $c[]=$m["allow_all"] ? sfp_chip("err","fas fa-triangle-exclamation","{sfp2_chip_open}") : sfp_chip("ok","fas fa-shield-halved","{sfp2_chip_on}");
    $c[]=sfp_chip("n","fas fa-lock","{sfp2_chip_floor}");
    $c[]=sfp_chip("n","fas fa-list-ol",sfp_fmt("{sfp2_chip_count}",$m["n_all"],$m["n_on"]));
    if($m["pending"]){ $c[]=sfp_chip("warn","fas fa-clock","{sfp2_chip_pending}"); }
    return "<section class='sfp-card sfp-hero'>
<div class='sfp-hero-ico'><i class='fas fa-door-closed' aria-hidden='true'></i></div>
<div class='sfp-hero-body'><h2>{sfp2_hero_title}</h2><p>{sfp2_hero_text}</p>
<p class='sfp-ex'><i class='fas fa-lightbulb' aria-hidden='true'></i><span><b>{sfp2_example}</b> {sfp2_hero_example}</span></p>
<div class='sfp-chips'>".@implode("",$c)."</div></div></section>";
}

// --- 2. Bandeau d'etat (un seul a la fois)

function sfp_bar_box(string $cls,string $ico,string $title,string $text,string $action=""):string{
    return "<div class='sfp-alert sfp-alert-$cls' role='".($cls==="err" ? "alert" : "status")."'>"
        ."<span class='sfp-alert-ico'><i class='$ico' aria-hidden='true'></i></span>"
        ."<div class='sfp-alert-body'><div class='sfp-alert-title'>$title</div><div class='sfp-alert-text'>$text</div></div>$action</div>";
}

function sfp_state_bar(array $m):string{
    if($m["cluster"]){ return sfp_bar_box("info","fas fa-circle-info","{sfp2_ro_title}","{sfp_restrict_cluster}"); }
    if($m["all_filters_off"]){ return sfp_bar_box("info","fas fa-circle-info","{sfp_restrict_off_title}","{sfp_restrict_allfilters}"); }
    if($m["allow_all"]){
        $btn=$m["can"] ? "<button type='button' class='sfp-btn sfp-btn-pri' onclick='SfpRestrict()'><i class='fas fa-lock' aria-hidden='true'></i>{sfp_restrict_enable}</button>" : "";
        return sfp_bar_box("err","fas fa-triangle-exclamation","{sfp2_open_title}","{sfp2_open_text}",$btn);
    }
    if($m["pending"]){
        $btn=$m["can"] ? "<button type='button' class='sfp-btn sfp-btn-warn' onclick='SfpApplyConf()'><i class='fas fa-check' aria-hidden='true'></i>{apply_configuration}</button>" : "";
        return sfp_bar_box("warn","fas fa-clock-rotate-left","{sfp2_pending_title}","{sfp2_pending_text}",$btn);
    }
    return "";
}

// --- 3. Barre d'outils

function sfp_toolbar(array $m):string{
    $segs=array("all"=>array("{sfp2_f_all}",$m["n_all"]),"on"=>array("{sfp2_f_on}",$m["n_on"]),"off"=>array("{sfp2_f_off}",$m["n_off"]));
    $b=array();
    foreach($segs as $k=>$v){
        $b[]="<button type='button' class='sfp-seg' data-f='$k' aria-pressed='false' onclick=\"SfpFilter('$k')\">{$v[0]} <span>(".intval($v[1]).")</span></button>";
    }
    $act="";
    if($m["can"]){
        $act.=$m["allow_all"] ? "" : "<button type='button' class='sfp-link-danger' onclick='SfpOpenAll()'><i class='fas fa-lock-open' aria-hidden='true'></i>{sfp_restrict_disable}</button>";
        $act.="<button type='button' class='sfp-btn' onclick='SfpApplyConf()'><i class='fas fa-floppy-disk' aria-hidden='true'></i>{apply_configuration}</button>";
        $act.="<button type='button' class='sfp-btn sfp-btn-pri' onclick='SfpNew()'><i class='fas fa-plus' aria-hidden='true'></i>{new_port}</button>";
    }
    return "<div class='sfp-card sfp-tools'>
<label class='sfp-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i><input type='search' id='sfp-q' placeholder='{sfp2_search}' aria-label='{sfp2_search}' oninput='SfpSearch(this.value)'></label>
<div class='sfp-segs' role='group' aria-label='{sfp2_f_label}'>".@implode("",$b)."</div>
<div class='sfp-tools-r'>$act</div></div>";
}

// --- 4. Liste des ports

/** Interrupteur : role=switch, mot visible, verrou quand le generateur impose la valeur. */
function sfp_switch(array $r,string $key,bool $on,string $label,string $help,string $lock,bool $can):string{
    $state=$on ? "{sfp2_on}" : "{sfp2_off}";
    $id=$r["id"];
    if($lock!==""){
        $on=($lock==="on");
        $tip=$lock==="on" ? "{sfp2_lock_on_tip}" : "{sfp2_lock_off_tip}";
        return "<span class='sfp-sw sfp-sw-locked".($on ? " sfp-sw-on" : "")."' role='switch' aria-checked='".($on ? "true" : "false")."' aria-disabled='true' title=\"$tip\">"
            ."<span class='sfp-sw-track'><span></span></span><span class='sfp-sw-lbl'><i class='fas fa-lock' aria-hidden='true'></i>$label <em>".($on ? "{sfp2_on}" : "{sfp2_off}")."</em></span></span>";
    }
    $dis=$can ? "" : " disabled";
    $p=$r["port"]>0 ? $r["port"] : 0;
    return "<button type='button' class='sfp-sw".($on ? " sfp-sw-on" : "")."' role='switch' aria-checked='".($on ? "true" : "false")."' title=\"$help\"$dis"
        ." data-id='$id' data-port='".htmlspecialchars($r["raw"],ENT_QUOTES)."' onclick=\"SfpToggle(this,'$key')\">"
        ."<span class='sfp-sw-track'><span></span></span><span class='sfp-sw-lbl'>$label <em>$state</em></span></button>";
}

function sfp_row(array $r,array $m):string{
    $lk=sfp_locks($r);
    $can=$m["can"];
    $num=$r["ignored"] ? htmlspecialchars($r["raw"]) : (string)$r["port"];
    $chips="";
    if($r["ignored"]){ $chips.=sfp_chip("err","fas fa-ban","{sfp2_ignored}","{sfp2_ignored_tip}"); }
    elseif($lk["always"]){ $chips.=sfp_chip("n","fas fa-lock","{sfp2_always}","{sfp2_always_tip}"); }
    if(in_array($r["port"],array(20,21),true)){ $chips.=sfp_chip("info","fas fa-file-arrow-down","FTP","{sfp2_ftp_tip}"); }
    if(!$r["enabled"] && !$lk["always"]){ $chips.=sfp_chip("off","fas fa-circle-pause","{sfp2_disabled}"); }
    $desc=trim($r["desc"])!=="" ? htmlspecialchars($r["desc"]) : "<span class='sfp-ph'>{sfp2_note_ph}</span>";
    $edit=$can ? "<button type='button' class='sfp-icon' aria-label='{sfp2_edit_note}' title='{sfp2_edit_note}' onclick=\"Loadjs('".CurrentPageName()."?safe-port-desc={$r["id"]}')\"><i class='fas fa-pen' aria-hidden='true'></i></button>" : "";
    $sw=sfp_switch($r,"http",$r["http"],"{sfp2_http}","{sfp2_http_help}",$lk["http"],$can && !$r["ignored"])
        .sfp_switch($r,"ssl",$r["ssl"],"{sfp2_https}","{sfp2_https_help}",$lk["ssl"],$can && !$r["ignored"]);
    $en=$lk["always"]
        ? "<span class='sfp-always'><i class='fas fa-circle' aria-hidden='true'></i>{sfp2_always_active}</span>"
        : sfp_switch($r,"enabled",$r["enabled"],"{sfp2_enabled}","{sfp2_enabled_help}","",$can && !$r["ignored"]);
    $del=($can && !$lk["always"])
        ? "<button type='button' class='sfp-icon sfp-icon-del' aria-label=\"".sfp_fmt("{sfp2_del_aria}",htmlspecialchars($r["raw"],ENT_QUOTES))."\" data-id='{$r["id"]}' data-port='".htmlspecialchars($r["raw"],ENT_QUOTES)."' onclick='SfpDelete(this)'><i class='fas fa-trash' aria-hidden='true'></i></button>"
        : "";
    $q=strtolower($r["raw"]." ".$r["desc"]);
    $off=(!$r["enabled"] && !$lk["always"]) || $r["ignored"];
    return "<div class='sfp-row".($off ? " sfp-row-off" : "")."' data-on='".($r["enabled"] ? 1 : 0)."' data-q=\"".htmlspecialchars($q,ENT_QUOTES)."\">"
        ."<div class='sfp-port'><span class='sfp-num'>$num</span><div class='sfp-note'>$chips<span>$desc</span>$edit</div></div>"
        ."<div class='sfp-protos'>$sw</div><div class='sfp-status'>$en</div><div class='sfp-del'>$del</div></div>";
}

function sfp_list(array $m):string{
    if($m["error"]!==""){
        return sfp_bar_box("err","fas fa-database","{sfp2_db_error}",htmlspecialchars($m["error"]));
    }
    $notes=array();
    if(!$m["allow_all"] && $m["http_count"]==0 && $m["n_all"]>0){ $notes[]="<i class='fas fa-circle-info' aria-hidden='true'></i> {sfp2_fallback_http}"; }
    if(!$m["allow_all"] && $m["ssl_count"]==0){ $notes[]="<i class='fas fa-circle-info' aria-hidden='true'></i> {sfp2_fallback_ssl}"; }
    if(count($m["rows"])==0){
        $btn=$m["can"] ? "<button type='button' class='sfp-btn sfp-btn-pri' onclick='SfpNew()'><i class='fas fa-plus' aria-hidden='true'></i>{new_port}</button>" : "";
        return "<div class='sfp-card sfp-empty'><i class='fas fa-door-open' aria-hidden='true'></i><h3>{sfp2_empty_title}</h3><p>{sfp2_empty_text}</p>$btn</div>";
    }
    $rows=array();
    foreach($m["rows"] as $r){ $rows[]=sfp_row($r,$m); }
    return "<div class='sfp-card sfp-list".($m["allow_all"] ? " sfp-list-ignored" : "")."'>"
        ."<div class='sfp-head'><span>{sfp2_th_port} <em>{sfp2_th_sorted}</em></span><span>{sfp2_th_protos}</span><span>{sfp2_th_status}</span><span></span></div>"
        ."<div id='sfp-rows'>".@implode("\n",$rows)."</div>"
        ."<div id='sfp-nomatch' class='sfp-nomatch' style='display:none'>{sfp2_nomatch}</div>"
        ."<div class='sfp-foot'><span>".sfp_fmt("{sfp2_foot}",$m["n_on"],$m["n_all"])."</span>"
        .(count($notes) ? "<span class='sfp-notes'>".@implode("<br>",$notes)."</span>" : "")."</div></div>";
}

/** Le JS d'application (barre de progression), partage par le bandeau, la barre et le TINY. */
function sfp_apply_js():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->framework_buildjs("/proxy/http/access/default",
        "squid.access.center.progress","squid.access.center.progress.log",
        "squid-safe-port-progress","LoadAjaxSilent('SafePortTable','$page?SafePortTable=yes&applied=yes');");
}

function sfp_tiny($tpl,array $m):string{
    $btns=array();
    if($m["can"]){
        $btns[]=array("SfpNew()",ico_plus,"{new_port}");
        $btns[]=array("SfpApplyConf()",ico_save,"{apply_configuration}");
    }
    $TINY=array();
    $TINY["TITLE"]="{remote_ports}: {ports_restrictions}";
    $TINY["ICO"]="fad fa-plug";
    $TINY["EXPL"]="{safe_ports_explain}";
    $TINY["URL"]="proxy-safe-ports";
    $TINY["BUTTONS"]=$tpl->table_buttons($btns);
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
}

function sfp_js(array $m):string{
    $page=CurrentPageName();
    $apply=sfp_apply_js();
    $T=array();
    foreach(array("ask_open","ask_open_txt","ask_del","ask_del_txt","open_go","del_go","cancel","failed") as $k){ $T[$k]=sfp_jstext("{sfp2_$k}"); }
    return "<script>
window.SfpF=window.SfpF||{f:'all',q:''};
function SfpReload(){ LoadAjaxSilent('SafePortTable','$page?SafePortTable=yes'); }
function SfpApplyConf(){ $apply }
function SfpNew(){ Loadjs('$page?sfp-new-js=yes'); }
function SfpFilter(f){ window.SfpF.f=f; SfpApply(); }
function SfpSearch(v){ window.SfpF.q=String(v||'').toLowerCase().trim(); SfpApply(); }
function SfpApply(){
  var F=window.SfpF, l=document.getElementById('sfp-rows'), s=document.querySelectorAll('.sfp-seg');
  for(var j=0;j<s.length;j++){ var on=s[j].getAttribute('data-f')===F.f; s[j].className='sfp-seg'+(on?' sfp-seg-on':''); s[j].setAttribute('aria-pressed',on?'true':'false'); }
  var q=document.getElementById('sfp-q'); if(q && q.value!==F.q && document.activeElement!==q){ q.value=F.q; }
  if(!l){return;}
  var r=l.querySelectorAll('.sfp-row'), shown=0;
  for(var i=0;i<r.length;i++){ var on=r[i].getAttribute('data-on')==='1', ok=(F.f==='all'||(F.f==='on'&&on)||(F.f==='off'&&!on));
    if(ok&&F.q!==''){ ok=(r[i].getAttribute('data-q')||'').indexOf(F.q)>=0; } r[i].style.display=ok?'':'none'; if(ok){shown++;} }
  var nm=document.getElementById('sfp-nomatch'); if(nm){ nm.style.display=(shown===0&&r.length>0)?'':'none'; }
}
function SfpPost(data,done){
  var x=new XHRConnection(); for(var k in data){ x.appendData(k,data[k]); }
  x.sendAndLoad('$page','POST',function(o){ var t=((o&&o.responseText)||'').trim();
    if(t.length>0){ if(t.indexOf('jserror:')===0){t=t.substring(8);} swal({title:'{$T["failed"]}',text:t.replace(/<[^>]+>/g,' '),type:'error'}); }
    if(done){done();} SfpReload(); });
}
function SfpToggle(el,key){ SfpPost({'sfp-toggle':el.getAttribute('data-id'),'key':key}); }
function SfpDelete(el){
  var p=el.getAttribute('data-port')||'';
  swal({title:'{$T["ask_del"]}'.replace('%s',p),text:'{$T["ask_del_txt"]}'.replace('%s',p),type:'warning',showCancelButton:true,confirmButtonColor:'#b91c1c',confirmButtonText:'{$T["del_go"]}',cancelButtonText:'{$T["cancel"]}'},
    function(y){ if(!y){return;} SfpPost({'sfp-del':el.getAttribute('data-id')}); });
}
function SfpOpenAll(){
  swal({title:'{$T["ask_open"]}',text:'{$T["ask_open_txt"]}',type:'warning',showCancelButton:true,confirmButtonColor:'#b91c1c',confirmButtonText:'{$T["open_go"]}',cancelButtonText:'{$T["cancel"]}'},
    function(y){ if(!y){return;} Loadjs('$page?Safe-port-disable=yes'); });
}
function SfpRestrict(){ Loadjs('$page?Safe-port-enable=yes'); }
</script>";
}

function sfp_accent():string{
    $ac="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_dark"])){ $ac=$a["accent_dark"]; }
    }
    return $ac;
}

function sfp_style():string{
    $ac=sfp_accent();
    $soft=function_exists("brand_rgba") ? brand_rgba($ac,".14") : "";
    if($soft==""){ $soft="rgba(13,148,136,.14)"; }
    return "<style>
.sfp,#sfpn{--sfp-ac:$ac;--sfp-soft:$soft;color:#1e293b;font-size:14px}
.sfp{display:flex;flex-direction:column;gap:16px;margin-top:10px}
.sfp *,#sfpn *{box-sizing:border-box}
.sfp-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.sfp-hero{display:flex;gap:20px;padding:22px 24px;border-left:4px solid var(--sfp-ac)}
.sfp-hero-ico{flex:0 0 52px;height:52px;border-radius:12px;background:var(--sfp-soft);color:var(--sfp-ac);display:flex;align-items:center;justify-content:center;font-size:24px}
.sfp-hero-body{flex:1;min-width:0}
.sfp-hero h2{font-size:18px;font-weight:600;margin:0 0 6px;color:#0f172a}
.sfp-hero p{font-size:14px;line-height:1.6;color:#475569;margin:0 0 10px;max-width:980px}
.sfp-ex{display:flex;gap:8px;align-items:flex-start;font-size:13px!important}
.sfp-ex i{color:#d97706;margin-top:3px}
.sfp-ex b{color:#334155}
.sfp-chips{display:flex;flex-wrap:wrap;gap:8px}
.sfp-chip{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:500;line-height:1.3;padding:3px 10px;border-radius:999px;white-space:nowrap}
.sfp-chip i{font-size:12px}
.sfp-chip-n{background:#f1f5f9;color:#334155}
.sfp-chip-ok{background:#d1fae5;color:#047857}
.sfp-chip-warn{background:#fef3c7;color:#92400e}
.sfp-chip-err{background:#fee2e2;color:#b91c1c}
.sfp-chip-off{background:#f1f5f9;color:#64748b}
.sfp-chip-info{background:#dbeafe;color:#1d4ed8}
.sfp-alert{display:flex;align-items:center;gap:14px;padding:14px 18px;border-radius:12px;border:1px solid}
.sfp-alert-ico{flex:0 0 36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px}
.sfp-alert-body{flex:1;min-width:0}
.sfp-alert-title{font-weight:600}
.sfp-alert-text{font-size:13px;margin-top:2px;line-height:1.5}
.sfp-alert-warn{background:#fffbeb;border-color:#fde68a;color:#92400e}
.sfp-alert-warn .sfp-alert-ico{background:#fef3c7}
.sfp-alert-err{background:#fef2f2;border-color:#fecaca;color:#991b1b}
.sfp-alert-err .sfp-alert-ico{background:#fee2e2}
.sfp-alert-info{background:#f8fafc;border-color:#e2e8f0;color:#475569}
.sfp-alert-info .sfp-alert-ico{background:#f1f5f9}
.sfp-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;border:1px solid #cbd5e1;background:#fff;color:#334155;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap}
.sfp-btn:hover{background:#f8fafc;border-color:#94a3b8}
.sfp-btn-pri{background:var(--sfp-ac);border-color:var(--sfp-ac);color:#fff}
.sfp-btn-pri:hover{background:var(--sfp-ac);color:#fff;filter:brightness(.92)}
.sfp-btn-warn{background:#b45309;border-color:#b45309;color:#fff}
.sfp-btn-warn:hover{background:#92400e;color:#fff}
.sfp-btn:focus-visible,.sfp-seg:focus-visible,.sfp-sw:focus-visible,.sfp-icon:focus-visible,.sfp-link-danger:focus-visible{outline:2px solid var(--sfp-ac);outline-offset:2px}
.sfp-link-danger{border:0;background:transparent;color:#b91c1c;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;gap:6px;align-items:center;padding:6px 8px}
.sfp-link-danger:hover{text-decoration:underline}
.sfp-tools{display:flex;align-items:center;gap:12px;padding:10px 12px;flex-wrap:wrap}
.sfp-search{position:relative;margin:0;font-weight:400;flex:0 1 320px}
.sfp-search i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px}
.sfp-search input{width:100%;height:34px;padding:0 10px 0 32px;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;background:#fff;color:#1e293b}
.sfp-segs{display:inline-flex;gap:2px;background:#f1f5f9;border-radius:10px;padding:3px}
.sfp-seg{border:0;background:transparent;padding:6px 12px;border-radius:8px;font-size:13px;font-weight:500;color:#475569;cursor:pointer;white-space:nowrap}
.sfp-seg-on{background:#fff;color:#0f172a;font-weight:600;box-shadow:0 1px 2px rgba(15,23,42,.08)}
.sfp-tools-r{display:flex;align-items:center;gap:10px;margin-left:auto;flex-wrap:wrap}
.sfp-list{overflow:hidden}
.sfp-list-ignored .sfp-row{opacity:.6}
.sfp-head,.sfp-row{display:grid;grid-template-columns:minmax(260px,1.6fr) minmax(360px,2fr) minmax(150px,.8fr) 48px;gap:16px;align-items:center;padding:0 22px}
.sfp-head{background:#f8fafc;border-bottom:1px solid #e2e8f0;padding-top:12px;padding-bottom:12px;font-size:12px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#64748b}
.sfp-head em{font-style:normal;font-weight:500;text-transform:none;letter-spacing:0;margin-left:6px}
.sfp-row{min-height:68px;border-bottom:1px solid #f1f5f9;padding-top:10px;padding-bottom:10px}
.sfp-row:last-child{border-bottom:0}
.sfp-row-off{background:#fafafa}
.sfp-row-off .sfp-num,.sfp-row-off .sfp-note>span{color:#94a3b8}
.sfp-port{display:flex;align-items:center;gap:16px;min-width:0}
.sfp-num{font-family:'JetBrains Mono',ui-monospace,SFMono-Regular,Menlo,monospace;font-size:18px;font-weight:600;color:#0f172a;min-width:64px;overflow-wrap:anywhere}
.sfp-note{display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:13px;color:#334155;min-width:0}
.sfp-ph{color:#94a3b8;font-style:italic}
.sfp-icon{border:0;background:transparent;color:#94a3b8;cursor:pointer;padding:4px;font-size:13px;border-radius:6px}
.sfp-icon:hover{color:#334155;background:#f1f5f9}
.sfp-icon-del:hover{color:#b91c1c;background:#fef2f2}
.sfp-protos{display:flex;gap:18px;flex-wrap:wrap}
.sfp-sw{display:inline-flex;align-items:center;gap:8px;border:0;background:transparent;padding:2px;cursor:pointer;font-size:13px;color:#334155;text-align:left}
.sfp-sw[disabled]{cursor:default;opacity:.55}
.sfp-sw-track{position:relative;flex:0 0 38px;height:22px;border-radius:11px;background:#cbd5e1;transition:background .15s}
.sfp-sw-track span{position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:left .15s}
.sfp-sw-on .sfp-sw-track{background:var(--sfp-ac)}
.sfp-sw-on .sfp-sw-track span{left:19px}
.sfp-sw-lbl em{font-style:normal;font-size:12px;font-weight:600;color:#64748b;margin-left:4px}
.sfp-sw-on .sfp-sw-lbl em{color:var(--sfp-ac)}
.sfp-sw-locked{opacity:.65;cursor:help}
.sfp-sw-locked .sfp-sw-lbl i{font-size:11px;margin-right:4px;color:#64748b}
.sfp-always{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#047857}
.sfp-always i{font-size:8px}
.sfp-del{text-align:right}
.sfp-nomatch{padding:24px;text-align:center;color:#64748b}
.sfp-foot{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:12px 22px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:13px;color:#64748b}
.sfp-notes{max-width:640px;text-align:right}
.sfp-notes i{color:#2563eb}
.sfp-empty{padding:44px 24px;text-align:center}
.sfp-empty>i{font-size:40px;color:var(--sfp-ac)}
.sfp-empty h3{font-size:17px;font-weight:600;margin:12px 0 6px;color:#0f172a}
.sfp-empty p{font-size:14px;color:#475569;max-width:600px;margin:0 auto 16px;line-height:1.6}
@media (max-width:1200px){.sfp-head{display:none}.sfp-row{grid-template-columns:minmax(0,1fr) auto;row-gap:10px}.sfp-protos{grid-column:1}.sfp-status{grid-column:1}.sfp-del{grid-column:2;grid-row:1}}
@media (prefers-reduced-motion:reduce){.sfp-sw-track,.sfp-sw-track span{transition:none}}
#sfpn .sfpn-f{margin-bottom:14px}
#sfpn label.sfpn-l{display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:4px}
#sfpn input[type=text],#sfpn input[type=number]{width:100%;height:36px;padding:0 10px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px}
#sfpn input:focus{outline:2px solid var(--sfp-ac);outline-offset:1px}
#sfpn .sfpn-h{font-size:13px;color:#64748b;margin-top:4px;line-height:1.45}
#sfpn .sfpn-c{display:flex;gap:10px;align-items:flex-start;margin:0 0 10px;font-weight:400}
#sfpn .sfpn-c input{margin-top:3px;accent-color:var(--sfp-ac)}
#sfpn .sfpn-c b{display:block;font-size:13px;color:#0f172a}
#sfpn .sfpn-c span{font-size:13px;color:#64748b}
#sfpn .sfpn-intro{font-size:14px;color:#475569;margin:0 0 16px;line-height:1.6}
#sfpn .sfpn-err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:8px;padding:9px 12px;font-size:13px;margin-bottom:12px}
#sfpn .sfpn-foot{display:flex;justify-content:flex-end;gap:10px}
</style>";
}

// --- Actions (POST, droit verifie, trace d'audit)

/** Refus commun : droit manquant ou client de cluster. "" si l'action est permise. */
function sfp_refusal():string{
    if(!SafePorts_can_edit()){ return "{sfp2_no_privs}"; }
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"))==1){ return "{sfp_restrict_cluster}"; }
    return "";
}

function sfp_toggle(){
    $tpl=new template_admin();
    $err=sfp_refusal();
    if($err!==""){ echo $tpl->post_error($err); return; }
    // Le nom de colonne vient d'une liste fermee, jamais de l'URL.
    $allowed=array("http"=>true,"ssl"=>true,"enabled"=>true);
    $key=(string)($_POST["key"] ?? "");
    $id=intval($_POST["sfp-toggle"] ?? 0);
    if(!isset($allowed[$key]) || $id<=0){ echo $tpl->post_error("{bad_request}"); return; }
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT port,$key FROM safeports WHERE ID=$id");
    if(!is_array($ligne) || !isset($ligne[$key])){ echo $tpl->post_error("{bad_request}"); return; }
    $val=intval($ligne[$key])==1 ? 0 : 1;
    $q->QUERY_SQL_PARAMS("UPDATE safeports SET $key=? WHERE ID=?",array($val,$id));
    if(!$q->ok){ echo $tpl->post_error($q->mysql_error); return; }
    $_SESSION["SFP_PENDING"]=1;
    admin_tracks("Proxy allowed port {$ligne["port"]}: $key set to $val (not applied yet)");
}

function sfp_delete(){
    $tpl=new template_admin();
    $err=sfp_refusal();
    if($err!==""){ echo $tpl->post_error($err); return; }
    $id=intval($_POST["sfp-del"] ?? 0);
    if($id<=0){ echo $tpl->post_error("{bad_request}"); return; }
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT port FROM safeports WHERE ID=$id");
    $q->QUERY_SQL_PARAMS("DELETE FROM safeports WHERE ID=?",array($id));
    if(!$q->ok){ echo $tpl->post_error($q->mysql_error); return; }
    $_SESSION["SFP_PENDING"]=1;
    admin_tracks("Proxy allowed port ".($ligne["port"] ?? "#$id")." deleted (not applied yet)");
}

function sfp_new_js(){
    $tpl=new template_admin();
    $tpl->js_dialog1("{sfp2_new_title}",CurrentPageName()."?sfp-new-popup=yes",560);
}

function sfp_new_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $h=array();
    $h[]="<div id='sfpn'>";
    $h[]="<p class='sfpn-intro'>{sfp2_new_intro}</p>";
    $h[]="<div class='sfpn-f'><label class='sfpn-l' for='sfpn-port'>{sfp2_new_port}</label><input type='number' id='sfpn-port' min='1' max='65535' inputmode='numeric'><div class='sfpn-h'>{sfp2_new_port_help}</div></div>";
    $h[]="<div class='sfpn-f'><label class='sfpn-l' for='sfpn-note'>{sfp2_new_note}</label><input type='text' id='sfpn-note' maxlength='128'><div class='sfpn-h'>{sfp2_new_note_help}</div></div>";
    $h[]="<label class='sfpn-c'><input type='checkbox' id='sfpn-http' checked><span><b>{sfp2_http}</b><span>{sfp2_http_help}</span></span></label>";
    $h[]="<label class='sfpn-c'><input type='checkbox' id='sfpn-ssl' checked><span><b>{sfp2_https}</b><span>{sfp2_https_help}</span></span></label>";
    $h[]="<div class='sfpn-err' id='sfpn-err' role='alert' style='display:none'></div>";
    $h[]="<div class='sfpn-foot'><button type='button' class='sfp-btn' onclick='dialogInstance1.close();'>{sfp2_cancel}</button>";
    $h[]="<button type='button' class='sfp-btn sfp-btn-pri' id='sfpn-go' onclick='SfpnSubmit()'><i class='fas fa-plus' aria-hidden='true'></i>{sfp2_new_go}</button></div></div>";
    $bad=sfp_jstext("{sfp2_err_port}");
    $js="<script>
function SfpnSubmit(){
  var p=String(document.getElementById('sfpn-port').value||'').trim(), e=document.getElementById('sfpn-err');
  if(!/^[0-9]{1,5}$/.test(p) || parseInt(p,10)<1 || parseInt(p,10)>65535){ e.textContent='$bad'; e.style.display=''; return; }
  var x=new XHRConnection();
  x.appendData('sfp-add',p);
  x.appendData('note',encodeURIComponent(document.getElementById('sfpn-note').value||''));
  x.appendData('http',document.getElementById('sfpn-http').checked?'1':'0');
  x.appendData('ssl',document.getElementById('sfpn-ssl').checked?'1':'0');
  var go=document.getElementById('sfpn-go'); go.disabled=true;
  x.sendAndLoad('$page','POST',function(o){ go.disabled=false; var t=((o&&o.responseText)||'').trim();
    if(t.length>0){ if(t.indexOf('jserror:')===0){t=t.substring(8);} e.innerHTML=t; e.style.display=''; return; }
    dialogInstance1.close(); if(typeof SfpReload==='function'){SfpReload();} });
}
setTimeout(function(){ var i=document.getElementById('sfpn-port'); if(i){i.focus();} },300);
</script>";
    echo sfp_style().$tpl->_ENGINE_parse_body(@implode("\n",$h)).$js;
}

function sfp_add(){
    $tpl=new template_admin();
    $err=sfp_refusal();
    if($err!==""){ echo $tpl->post_error($err); return; }
    // Un seul port entier : le generateur ignore en silence plages et listes (StrToInt -> 0).
    $port=trim((string)($_POST["sfp-add"] ?? ""));
    if(!preg_match('/^[0-9]{1,5}$/',$port) || intval($port)<1 || intval($port)>65535){ echo $tpl->post_error("{sfp2_err_port}"); return; }
    $port=strval(intval($port));
    $note=trim(rawurldecode((string)($_POST["note"] ?? "")));
    $note=mb_substr(preg_replace('/[\x00-\x1F\x7F]/u',"",$note),0,128,"UTF-8");
    $http=intval($_POST["http"] ?? 1)==1 ? 1 : 0;
    $ssl=intval($_POST["ssl"] ?? 1)==1 ? 1 : 0;
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ex=$q->mysqli_fetch_array("SELECT ID FROM safeports WHERE port='$port'");
    if(is_array($ex) && intval($ex["ID"] ?? 0)>0){ echo $tpl->post_error("{sfp2_err_dup}"); return; }
    $q->QUERY_SQL_PARAMS("INSERT INTO safeports (port,ssl,http,description,enabled) VALUES (?,?,?,?,1)",array($port,$ssl,$http,$note));
    if(!$q->ok){ echo $tpl->post_error($q->mysql_error); return; }
    $_SESSION["SFP_PENDING"]=1;
    admin_tracks("Proxy allowed port $port added (http=$http ssl=$ssl, not applied yet)");
}

// --- Note d'un port (modale form_outside)

function SafePorts_desc(){
    $ID=intval($_GET["safe-port-desc"]);
    $tpl=new template_admin();
    $tpl->js_dialog1("{sfp2_edit_note}",CurrentPageName()."?safe-port-desc-popup=$ID",560);
}

function SafePorts_desc_popup(){
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["safe-port-desc-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT port,description FROM safeports WHERE ID='$ID'");
    $form[]=$tpl->field_hidden("safe-port-desc",$ID);
    $form[]=$tpl->field_text("desc","{sfp2_new_note}",$ligne["description"] ?? "",false,"{sfp2_new_note_help}");
    echo $tpl->form_outside(sfp_fmt("{sfp2_note_of}",htmlspecialchars((string)($ligne["port"] ?? ""))),$form,null,"{apply}",
        "dialogInstance1.close();if(typeof SfpReload==='function'){SfpReload();}","AsDansGuardianAdministrator");
}

function SafePorts_desc_save(){
    $tpl=new template_admin();
    $err=sfp_refusal();
    if($err!==""){ echo $tpl->post_error($err); return; }
    $tpl->CLEAN_POST();
    $ID=intval($_POST["safe-port-desc"]);
    $desc=mb_substr(trim((string)($_POST["desc"] ?? "")),0,128,"UTF-8");
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    // La note seule ne change rien a la configuration de Squid (commentaire) : pas de « pending ».
    $q->QUERY_SQL_PARAMS("UPDATE safeports SET description=? WHERE ID=?",array($desc,$ID));
    if(!$q->ok){ echo $tpl->post_error($q->mysql_error); return; }
    admin_tracks("Proxy allowed port #$ID: note changed");
}
