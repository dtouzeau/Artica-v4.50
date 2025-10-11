<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.smtpd.notifications.inc");
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_POST["TheShieldsCguard"])){cguard_settings_save();exit;}
if(isset($_POST["KRSN_DEBUG"])){Save();exit;}
if(isset($_POST["GoShieldSave"])){Save();exit;}
if(isset($_POST["KsrnEnableAdverstising"])){Save();exit;}

if(isset($_GET["nrds-js"])){nrds_js();exit;}
if(isset($_GET["nrds-popup"])){nrds_popup();exit;}
if(isset($_GET["nrds-search"])){nrds_search();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["go-shield-server-status-left"])){go_shield_status_left();exit;}
if(isset($_GET["go-shield-server-status"])){status();exit;}
if(isset($_GET["emergency-enable"])){emergency_enable();exit;}
if(isset($_GET["logfile-js"])){logfile_js();exit;}
if(isset($_GET["go-shield-server-form-server"])){ksrn_form_server();exit;}
if(isset($_GET["emergency-disable"])){emergency_disable();exit;}
if(isset($_GET["clean-cache"])){clean_cache();exit;}
if(isset($_GET["go-shield-server-top-status"])){go_shield_server_top_status();exit;}
if(isset($_GET["go-shield-form-js"])){go_shield_form_js();exit;}
if(isset($_GET["go-shield-form"])){go_shield_form();exit;}
if(isset($_GET["refresh-section"])){refresh_section();exit;}
if(isset($_GET["section-cguard-js"])){cguard_js();exit;}
if(isset($_GET["section-cguard-popup"])){cguard_settings();exit;}
if(isset($_GET["section-shields-js"])){the_shields_js();exit;}
if(isset($_GET["section-shields-popup"])){the_shields_popup();exit;}
page();

function clean_cache(){
    $tpl=new template_admin();
    $after=null;
    $page=CurrentPageName();
    if(isset($_GET["after"])){$after=base64_decode($_GET["after"]);}
    $Go_Shield_Server_Addr = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Addr");
    $Go_Shield_Server_Port = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Port"));
    if ($Go_Shield_Server_Addr==null){$Go_Shield_Server_Addr="127.0.0.1";}
    if($Go_Shield_Server_Port==0){$Go_Shield_Server_Port=3333;}
    $dblen=$GLOBALS["CLASS_SOCKETS"]->GET_GOSHIELD_CACHES_ENTRIES();

    $cURLConnection = curl_init();
    curl_setopt($cURLConnection, CURLOPT_URL, "http://$Go_Shield_Server_Addr:$Go_Shield_Server_Port/db/flush");
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
    $resp = curl_exec($cURLConnection);
    curl_close($cURLConnection);

    $tpl->js_display_results("$dblen {records} {removed}");
    echo "$after\n";
    echo "LoadAjaxSilent('go-shield-server-status','$page?go-shield-server-status=yes');\n";
    return true;
}


function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $Go_Shield_Server_Addr = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Addr");
    $Go_Shield_Server_Port = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Port"));
    if ($Go_Shield_Server_Addr==null){$Go_Shield_Server_Addr="127.0.0.1";}
    if($Go_Shield_Server_Port==0){$Go_Shield_Server_Port=3333;}

    $Go_Shield_Server_Version = "0.0.0.0";
    $cURLConnection = curl_init();

    curl_setopt($cURLConnection, CURLOPT_URL, "http://$Go_Shield_Server_Addr:$Go_Shield_Server_Port/get-version");
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);

    $resp = curl_exec($cURLConnection);
    curl_close($cURLConnection);
    $jsonArrayResponse = json_decode($resp,true);
    if(isset($jsonArrayResponse["version"])){$Go_Shield_Server_Version = $jsonArrayResponse["version"];}

    VERBOSE("GO_SHIELD_SERVER_VERSION = [$Go_Shield_Server_Version]",__LINE__);


    $html=$tpl->page_header("{KSRN_SERVER2} v{$Go_Shield_Server_Version}",
        "fad fa-compress-arrows-alt",
        "",
        "$page?table=yes","go-shield-server","progress-go-shield-server-restart",
        false,"table-loader-go-shield-server-pages"

    );

    if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return;}
    echo $tpl->_ENGINE_parse_body($html);

}
function refresh_section():bool{
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $f[]="if ( document.getElementById('go-shield-server-status') ){";
    $f[]="LoadAjaxSilent('go-shield-server-status','$page?go-shield-server-status=yes');";
    $f[]="LoadAjaxSilent('go-shield-server-form-server','$page?go-shield-server-form-server=yes');";
    $f[]="}";
    $f[]="if ( document.getElementById('table-loader-ksrn-features-pages') ){";
    $f[]="Loadjs('fw.ksrn.filtering.features.php?jstiny=yes');";
    $f[]="}";
    $f[]="if(typeof dialogInstance5 == 'object'){ dialogInstance5.close();}";
    echo @implode("\n",$f);
    return true;
}
function go_shield_form():bool{
    $section=$_GET["go-shield-form"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $Go_Shield_Server_Addr = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Addr");
    $Go_Shield_Server_Port = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Port"));
    $Go_Shield_Server_Debug = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Debug"));
    $Go_Shield_Server_Dns1=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Dns1"));

    if ($Go_Shield_Server_Addr==null){$Go_Shield_Server_Addr="127.0.0.1";}
    if($Go_Shield_Server_Port==0){$Go_Shield_Server_Port=3333;}

    $Go_Shield_Server_DB_Size=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_DB_Size"));
    $Go_Shield_Server_Cache_Time=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Cache_Time"));
    $Go_Shield_Server_TimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_TimeOut"));
    $Go_Shield_Server_Max_Threads=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Max_Threads"));
    $Go_Shield_Server_Max_Servers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Max_Servers"));

    $Go_Shield_Server_Purge=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Purge"));
    $Go_Shield_Server_Use_Local_Categories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsUseLocalCats"));

    $KSRNOnlyCategorization=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNOnlyCategorization"));

    $GoShieldRestartMemoryExceed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldRestartMemoryExceed"));
    $TheShieldsCguard       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsCguard"));
    $SelfDisableHardCoded=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SelfDisableHardCoded"));
    if($Go_Shield_Server_Cache_Time==0){$Go_Shield_Server_Cache_Time=84600;}
    if($Go_Shield_Server_DB_Size==0){$Go_Shield_Server_DB_Size=2048;}
    if($Go_Shield_Server_TimeOut==0){$Go_Shield_Server_TimeOut=5;}
    if($Go_Shield_Server_Max_Threads<5){$Go_Shield_Server_Max_Threads=5;}
    if($Go_Shield_Server_Max_Servers<1){$Go_Shield_Server_Max_Servers=2;}
    $Go_Shield_Server_DB_Shards = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_DB_Shards"));
    $Go_Shield_FS = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_FS"));
    $shards[256]=256;
    $shards[512]=512;
    $shards[1024]=1024;
    $shards[2048]=2048;
    $shards[4096]=4096;
    $shards[8192]=8192;
    if($GoShieldRestartMemoryExceed==0){$GoShieldRestartMemoryExceed=2300;}


    $EnableRemoteCategoriesServices = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteCategoriesServices"));
    $RemoteCategoriesServicesRemote = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesRemote"));
    $RemoteCategoriesServicesAddress = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesAddress"));
    $RemoteCategoriesServicesPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesPort"));
    $RemoteCategoriesServicesDomain = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesDomain"));

    if ($RemoteCategoriesServicesAddress == null) {
        $RemoteCategoriesServicesAddress = "127.0.0.1";
    }
    if ($RemoteCategoriesServicesPort == 0) {
        $RemoteCategoriesServicesPort = 3477;
    }
    if ($RemoteCategoriesServicesDomain == null) {
        $RemoteCategoriesServicesDomain = "categories.tld";
    }


    $disableMetric=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Disable_Metrics"));
    if($Go_Shield_FS==0){
        $Go_Shield_FS=200000;
    }

    if ($Go_Shield_Server_DB_Shards == 0) {
        $Go_Shield_Server_DB_Shards = 1024;
    }

    $ips[null] = "{all}";
    $ips["127.0.0.1"] = "Interface lo (127.0.0.1)";

    $nic = new networking();
    $nicZ = $nic->Local_interfaces(true);
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
        $ips[$znic->IPADDR] = "$znic->NICNAME ($znic->IPADDR)";
    }

    $GoShieldNotCategorized=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldNotCategorized"));
    $SelfGosShieldCacheNoCatz=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GosShieldCacheNoCatz"));
    $SelfGosShieldCacheNoCatzTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GosShieldCacheNoCatzTTL"));
    if(!file_exists("/etc/artica-postfix/settings/Daemons/GoShieldNotCategorized")){
        $GoShieldNotCategorized=0;
    }
    if(!file_exists("/etc/artica-postfix/settings/Daemons/GosShieldCacheNoCatz")){
        $SelfGosShieldCacheNoCatz=1;
    }
    if ($SelfGosShieldCacheNoCatzTTL==0){
        $SelfGosShieldCacheNoCatzTTL=1;
    }
    $NoCatzTTL[1]="1 {hour}";
    $NoCatzTTL[2]="2 {hours}";
    $NoCatzTTL[3]="3 {hours}";
    $NoCatzTTL[4]="4 {hours}";
    $NoCatzTTL[5]="5 {hours}";
    $NoCatzTTL[6]="6 {hours}";
    $jsRestart      = restart_js_form();

    $GoShieldUseSpecificDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldUseSpecificDNS"));

    $form[]=$tpl->field_hidden("GoShieldSave",1);

    if($section=="listen") {
        $jsRestart      = restart_js_form();
        $form[]=$tpl->field_section("{icapserver_1}","{service_remote_explain}");
        $form[] = $tpl->field_checkbox("Go_Shield_Server_Debug", "{debug}", $Go_Shield_Server_Debug, false, null);
        $form[] = $tpl->field_array_hash($ips, "Go_Shield_Server_Addr", "{listen_interface}", $Go_Shield_Server_Addr);
        $form[] = $tpl->field_numeric("Go_Shield_Server_Port", "{listen_port}", $Go_Shield_Server_Port);

    }

    if($section=="features") {
        $jsRestart      = restart_js_form();
        $form[] = $tpl->field_section("{features}");
        $KsrnQueryIPAddr        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnQueryIPAddr"));
        $form[] = $tpl->field_checkbox("TheShieldsUseLocalCats", "{UseLocalDatabase}", $Go_Shield_Server_Use_Local_Categories, false, "{TheShieldsUseLocalCats}");
        $form[] = $tpl->field_checkbox("KSRNOnlyCategorization", "{KSRNOnlyCategorization}", $KSRNOnlyCategorization);
        $form[] = $tpl->field_checkbox("SelfDisableHardCoded", "{DisableHardCodedCategories}", $SelfDisableHardCoded, false);
        $form[] = $tpl->field_checkbox("KsrnQueryIPAddr", "{KsrnQueryIPAddr}", $KsrnQueryIPAddr, false);
    }

    if($section=="ufdbcat") {
        $jsRestart      = restart_js_form();
        $form[] = $tpl->field_section("{APP_UFDBCAT}");
        if($EnableRemoteCategoriesServices==1) {
            $UFDBCAT_DNS_ERROR = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UFDBCAT_DNS_ERROR"));
            if (strlen($UFDBCAT_DNS_ERROR) > 3) {
                echo $tpl->div_error($UFDBCAT_DNS_ERROR);
            }

        }


        $form[] = $tpl->field_checkbox("EnableRemoteCategoriesServices", "{use_remote_categories_services}", $EnableRemoteCategoriesServices, "RemoteCategoriesServicesDomain,RemoteCategoriesServicesRemote,RemoteCategoriesServicesAddress,RemoteCategoriesServicesPort,RemoteCategoriesServicesDomain");
        $form[] = $tpl->field_checkbox("RemoteCategoriesServicesRemote", "{direct_connection}", $RemoteCategoriesServicesRemote, "RemoteCategoriesServicesAddress,RemoteCategoriesServicesPort");
        $form[] = $tpl->field_text("RemoteCategoriesServicesAddress", "{remote_server_address}", $RemoteCategoriesServicesAddress);
        $form[] = $tpl->field_text("RemoteCategoriesServicesPort", "{remote_server_port}", $RemoteCategoriesServicesPort);
        $form[] = $tpl->field_text("RemoteCategoriesServicesDomain", "{SiteDomain}", $RemoteCategoriesServicesDomain);
    }

    if($section=="dns") {
        $jsRestart      = restart_js_form();
        $GoShieldDNSOutgoingIface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldDNSOutgoingIface"));
        $form[] = $tpl->field_section("{dns_resolution}");
        $form[] = $tpl->field_numeric("Go_Shield_Server_TimeOut", "DNS {timeout} ({seconds})", $Go_Shield_Server_TimeOut);
        $form[] = $tpl->field_checkbox("GoShieldUseSpecificDNS", "{overwrite_local_dns}", $GoShieldUseSpecificDNS, "Go_Shield_Server_Dns1");
        $form[] = $tpl->field_ipv4("Go_Shield_Server_Dns1", "{primary_dns}", $Go_Shield_Server_Dns1);
        $form[]=$tpl->field_interfaces("GoShieldDNSOutgoingIface","{outgoing_interface}",$GoShieldDNSOutgoingIface);


    }

    if($section=="memory") {
        $jsRestart      = restart_js_form();
        $form[] = $tpl->field_section("{memory_caching}");

        $purge[0] = "{daily}";
        $purge[1] = "{weekly}";
        $purge[2] = "{every} 12 {hours}";
        $purge[3] = "{every} 6 {hours}";
        $purge[4] = "{every} 3 {hours}";

        $form[] = $tpl->field_array_hash($shards, "Go_Shield_Server_DB_Shards", "{shards}", $Go_Shield_Server_DB_Shards, false, null, null, false);
        $form[] = $tpl->field_numeric("Go_Shield_Server_DB_Size", "{database} {size} (Mb)", $Go_Shield_Server_DB_Size, false, null, false);
        $form[] = $tpl->field_numeric("Go_Shield_Server_Cache_Time", "{max_records_time_memory} ({seconds})", $Go_Shield_Server_Cache_Time, false, null, false);

        $form[] = $tpl->field_array_hash($purge, "Go_Shield_Server_Purge", "{empty_cache}", $Go_Shield_Server_Purge);

    }
    if ($section=="watchdog"){
        $jsRestart      = "LoadAjaxSilent('go-shield-server-form-server','$page?go-shield-server-form-server=yes');dialogInstance5.close();";
        $form[] = $tpl->field_numeric("GoShieldRestartMemoryExceed", "{restart_when_memory_exceed} (MB)", $GoShieldRestartMemoryExceed);
    }

    if($section=="perf") {

        $form[] = $tpl->field_section("{performance}");
        $form[] = $tpl->field_multiple_64("Go_Shield_FS", "{file_descriptors} (Go Shield Server)", $Go_Shield_FS, "");
        $form[] = $tpl->field_checkbox("Go_Shield_Server_Disable_Metrics", "{disable} {metrics}", $disableMetric);
    }

    if($section=="nocatz") {
        $jsRestart      = restart_js_form();
        $form[] = $tpl->field_section("{no} {categories}");
        $form[] = $tpl->field_checkbox("GoShieldNotCategorized", "{LogNotCategorizedWebsite}", $GoShieldNotCategorized);
        $form[] = $tpl->field_checkbox("GosShieldCacheNoCatz", "{cache} {no} {categorized} {websites}", $SelfGosShieldCacheNoCatz,"GosShieldCacheNoCatzTTL");
        $form[] = $tpl->field_array_hash($NoCatzTTL, "GosShieldCacheNoCatzTTL", "{cache} {ttl}", $SelfGosShieldCacheNoCatzTTL, false, null, null, false);

    }

    $priv           = "AsSystemAdministrator";


    $myform         = $tpl->form_outside(null, $form,null,"{apply}",$jsRestart,$priv);
    $html[]="<div id='restart-goserver-form'></div>";
    $html[]=$tpl->_ENGINE_parse_body($myform);
    echo @implode("\n",$html);
    return true;
}
function ksrn_form_server():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $Go_Shield_Server_Addr = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Addr");
    $Go_Shield_Server_Port = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Port"));
    $Go_Shield_Server_Debug = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Debug"));
    $Go_Shield_Server_Dns1=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Dns1"));

    if ($Go_Shield_Server_Addr==null){$Go_Shield_Server_Addr="127.0.0.1";}
    if($Go_Shield_Server_Port==0){$Go_Shield_Server_Port=3333;}
    $KsrnQueryIPAddr=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnQueryIPAddr"));

    $Go_Shield_Server_DB_Size=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_DB_Size"));
    $Go_Shield_Server_Cache_Time=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Cache_Time"));
    $Go_Shield_Server_TimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_TimeOut"));
    $Go_Shield_Server_Max_Threads=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Max_Threads"));
    $Go_Shield_Server_Max_Servers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Max_Servers"));

    $Go_Shield_Server_Purge=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Purge"));
    $Go_Shield_Server_Use_Local_Categories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsUseLocalCats"));

    $KSRNOnlyCategorization=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNOnlyCategorization"));

    $GoShieldRestartMemoryExceed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldRestartMemoryExceed"));
    $TheShieldsCguard       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsCguard"));
    $SelfDisableHardCoded=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SelfDisableHardCoded"));
    if($Go_Shield_Server_Cache_Time==0){$Go_Shield_Server_Cache_Time=84600;}
    if($Go_Shield_Server_DB_Size==0){$Go_Shield_Server_DB_Size=2048;}
    if($Go_Shield_Server_TimeOut==0){$Go_Shield_Server_TimeOut=5;}
    if($Go_Shield_Server_Max_Threads<5){$Go_Shield_Server_Max_Threads=5;}
    if($Go_Shield_Server_Max_Servers<1){$Go_Shield_Server_Max_Servers=2;}
    $Go_Shield_Server_DB_Shards = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_DB_Shards"));
    $Go_Shield_FS = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_FS"));
    $shards[256]=256;
    $shards[512]=512;
    $shards[1024]=1024;
    $shards[2048]=2048;
    $shards[4096]=4096;
    $shards[8192]=8192;
    if($GoShieldRestartMemoryExceed==0){$GoShieldRestartMemoryExceed=2300;}
    $disableMetric=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Disable_Metrics"));
    if($Go_Shield_FS==0){$Go_Shield_FS=200000;}
    if ($Go_Shield_Server_DB_Shards == 0) {$Go_Shield_Server_DB_Shards = 1024;}
    $EnableRemoteCategoriesServices = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteCategoriesServices"));

    $RemoteCategoriesServicesRemote = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesRemote"));
    $RemoteCategoriesServicesAddress = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesAddress"));
    $RemoteCategoriesServicesPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesPort"));
    $RemoteCategoriesServicesDomain = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesDomain"));

    if ($RemoteCategoriesServicesAddress == null) {$RemoteCategoriesServicesAddress = "127.0.0.1";}
    if ($RemoteCategoriesServicesPort == 0) {$RemoteCategoriesServicesPort = 3477;}
    if ($RemoteCategoriesServicesDomain == null) {$RemoteCategoriesServicesDomain = "categories.tld";}

    $GoShieldUseSpecificDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldUseSpecificDNS"));

    $GoShieldNotCategorized=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldNotCategorized"));
    $SelfGosShieldCacheNoCatz=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GosShieldCacheNoCatz"));
    $SelfGosShieldCacheNoCatzTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GosShieldCacheNoCatzTTL"));
    if ($SelfGosShieldCacheNoCatzTTL==0){
        $SelfGosShieldCacheNoCatzTTL=1;
    }

    if(!file_exists("/etc/artica-postfix/settings/Daemons/GoShieldNotCategorized")){
        $GoShieldNotCategorized=0;
    }
    if(!file_exists("/etc/artica-postfix/settings/Daemons/GosShieldCacheNoCatz")){
        $SelfGosShieldCacheNoCatz=1;
    }

    $tpl->table_form_section("{icapserver_1}");
    $tpl->table_form_field_js("Loadjs('$page?go-shield-form-js=yes&section=listen')","AsDansGuardianAdministrator");
    $tpl->table_form_field_bool("{debug}",$Go_Shield_Server_Debug,ico_bug);
    $tpl->table_form_field_text("{listen_interface}","$Go_Shield_Server_Addr:$Go_Shield_Server_Port",ico_nic);


    $UfdbguardSMTPNotifs=smtp_defaults();
    $ENABLED_SQUID_WATCHDOG=intval($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]);

    if($ENABLED_SQUID_WATCHDOG==0){
        $tpl->table_form_field_js("","AsDansGuardianAdministrator");
        $tpl->table_form_field_text("{notify_categories}","{smtp_notifications} {disabled}",ico_message);

    }else{
        $tpl->table_form_field_js("Loadjs('fw.ufdb.settings.php?notify-js=yes')","AsDansGuardianAdministrator");
        $WebFilteringCategoriesToLog=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebFilteringCategoriesToLog"));
        $WebFilteringCategories=array();
        if(strlen($WebFilteringCategoriesToLog)>2) {
            if(strpos($WebFilteringCategoriesToLog,",")>0)
            $WebFilteringCategories = explode(",", $WebFilteringCategoriesToLog);
        }else{
            $WebFilteringCategories[]=$WebFilteringCategoriesToLog;
        }
        $notify_categories=0;
        if(is_array($WebFilteringCategories)){
            foreach ($WebFilteringCategories as $catnum){
                if(!is_numeric($catnum)){continue;}
                $notify_categories++;
            }
        }

        if($notify_categories==0){
            $tpl->table_form_field_bool("{notify_categories}",0,ico_message);
        }else{
            $WebFilteringCategoriesToLogRecipient=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebFilteringCategoriesToLogRecipient"));
            $WebFilteringCategoriesToLogTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebFilteringCategoriesToLogTimeOut"));

            if($WebFilteringCategoriesToLogRecipient==null){
                $WebFilteringCategoriesToLogRecipient=$UfdbguardSMTPNotifs["smtp_dest"];
            }

            if($WebFilteringCategoriesToLogTimeOut==0){$WebFilteringCategoriesToLogTimeOut=15;}
            $tpl->table_form_field_text("{notify_categories}","$notify_categories {categories} <small>{to} $WebFilteringCategoriesToLogRecipient, {scan_each} $WebFilteringCategoriesToLogTimeOut {minutes}</small>",ico_message);
        }

    }

    $tpl->table_form_field_js("Loadjs('$page?go-shield-form-js=yes&section=dns')","AsDansGuardianAdministrator");
    if(strlen("$Go_Shield_Server_Dns1")>0 && $GoShieldUseSpecificDNS==1){
        $iface="";
        $GoShieldDNSOutgoingIface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldDNSOutgoingIface"));

        if(strlen($GoShieldDNSOutgoingIface)>2){
            $iface=" <small>({outgoing_interface}: $GoShieldDNSOutgoingIface)</small>";
        }

        $tpl->table_form_field_text("{dns_resolution}","$Go_Shield_Server_Dns1$iface",ico_server);



    }else{
        $tpl->table_form_field_text("{dns_resolution}","{default}",ico_server);
    }



    $tpl->table_form_field_js("Loadjs('$page?go-shield-form-js=yes&section=memory')","AsDansGuardianAdministrator");


    $tpl->table_form_field_text("{memory_caching}","{shards} $Go_Shield_Server_DB_Shards, {database}  {$Go_Shield_Server_DB_Size}MB, $Go_Shield_Server_Cache_Time {seconds}",ico_memory);

    $tpl->table_form_field_js("Loadjs('$page?go-shield-form-js=yes&section=watchdog')","AsDansGuardianAdministrator");
    $GoShieldRestartMemoryExceed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldRestartMemoryExceed"));
    if($GoShieldRestartMemoryExceed==0){
        $GoShieldRestartMemoryExceed=2300;
    }
    if($GoShieldRestartMemoryExceed>14){
        $tpl->table_form_field_text("{watchdog}","{restart_when_memory_exceed} $GoShieldRestartMemoryExceed MB",ico_watchdog);
    }else{
        $tpl->table_form_field_bool("{watchdog}",0,ico_watchdog);
    }



    $tpl->table_form_field_js("Loadjs('$page?go-shield-form-js=yes&section=perf')","AsDansGuardianAdministrator");

    $metrics="ON";
    if($disableMetric==1){$metrics="OFF";}
    $tpl->table_form_field_text("{performance}","{file_descriptors} $Go_Shield_FS, {metrics} $metrics",ico_performance);



    $tpl->table_form_section("{reputation_services} (The Shields)");

    $tpl=the_shields_settings($tpl);

    $tpl->table_form_section("{features}");
    $tpl->table_form_field_js("Loadjs('$page?go-shield-form-js=yes&section=features')","AsDansGuardianAdministrator");
    $tpl->table_form_field_bool("{KsrnQueryIPAddr}",$KsrnQueryIPAddr,ico_params);
    $tpl->table_form_field_bool("{DisableHardCodedCategories}",$SelfDisableHardCoded,ico_shield_disabled);
    $tpl->table_form_field_bool("{UseLocalDatabase}",$Go_Shield_Server_Use_Local_Categories,ico_database);
    $tpl->table_form_field_bool("{KSRNOnlyCategorization}",$KSRNOnlyCategorization,ico_database);
    $tpl->table_form_field_js("Loadjs('$page?go-shield-form-js=yes&section=ufdbcat')","AsDansGuardianAdministrator");

    if($EnableRemoteCategoriesServices==0){
        $tpl->table_form_field_bool("{use_remote_categories_services}",$EnableRemoteCategoriesServices,ico_servcloud2);

    }else{
        $error=false;
        $UFDBCAT_DNS_ERROR=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UFDBCAT_DNS_ERROR"));
        if(strlen($UFDBCAT_DNS_ERROR)>3){
            $error=true;
        }
        if($RemoteCategoriesServicesRemote==0){
            $tpl->table_form_field_text("{use_remote_categories_services}","$RemoteCategoriesServicesDomain",ico_servcloud2,$error);

        }else{
            $tpl->table_form_field_text("{use_remote_categories_services}","$RemoteCategoriesServicesAddress:$RemoteCategoriesServicesPort@$RemoteCategoriesServicesDomain",ico_servcloud2,$error);
        }

    }
    $tpl->table_form_section("{no} {categories}");
    $tpl->table_form_field_js("Loadjs('$page?go-shield-form-js=yes&section=nocatz')","AsDansGuardianAdministrator");

    $tpl->table_form_field_bool("{LogNotCategorizedWebsite}",$GoShieldNotCategorized,"fa-solid fa-file-pen");
    $tpl->table_form_field_bool("{cache} {no} {categorized} {websites}",$SelfGosShieldCacheNoCatz,ico_database);
    $tpl->table_form_field_text("{cache} {ttl}",$SelfGosShieldCacheNoCatzTTL." {hour}","fa-solid fa-timer");


    echo $tpl->table_form_compile();
    return true;
}
function nrds_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog5("{category}: {nrds_cat} {records}","$page?nrds-popup=yes");
}
function nrds_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->search_block($page,null,null,null,"&nrds-search=yes");
}
function nrds_search(){
    $search="NONE";$TRCLASS="";
    $tpl=new template_admin();
    if(isset($_GET["search"])){
        $search=$_GET["search"];
    }
    if(strlen($search)<2){
        $search="NONE";
    }
    $MAIN=$tpl->format_search_protocol($search);
    if(strlen($MAIN["TERM"])==0){ $MAIN["TERM"]="NONE"; }
    $terms=base64_encode($MAIN["TERM"]);
    $EndPoint="/nrds/search/{$MAIN["MAX"]}/$terms";
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API($EndPoint);

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>$data".json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }
   $html[]="<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>&nbsp;</th>
        	<th>{websites}</th>
        </tr>
  	</thead>
	<tbody>
";
    $ico_earth=ico_earth;
    foreach ($json->Logs as $line){
        $line=trim($line);
        if(strlen($line)==0){
            continue;
        }
        if(substr($line,0,1)=="#"){
            continue;
        }
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td nowrap style='width:1%;'><li class='$ico_earth'></li></td>";
        $html[]="<td>$line</td>";
        $html[]="</tr>";

    }
    $html[]="</tbody>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function go_shield_form_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $section=$_GET["section"];
    return $tpl->js_dialog5("{icapserver_1}: $section","$page?go-shield-form=$section");
}
function cguard_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog5("CGuard","$page?section-cguard-popup=yes");
}
function the_shields_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog5("The Shields","$page?section-shields-popup=yes");
}

function logfile_js():bool{

    $tpl=new template_admin();
    echo $tpl->framework_buildjs("ksrn.php?log-file=yes","ksrn.progress","ksrn.log",
        "progress-go-shield-server-restart","document.location.href='/ressources/logs/web/ksrn.log.gz';");
    return true;
}

function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $html="<table style='width:100%'>
	<tr>
	<td style='vertical-align:top;width:240px'><div id='go-shield-server-status' style='margin-top:15px'></div></td>
	<td	style='vertical-align:top;width:90%'>
	    <div id='go-shield-server-top-status'></div>
	    <div id='go-shield-server-form-server'></div>
    </td>
	</tr>
	</table>
	<script>
	    LoadAjaxSilent('go-shield-server-status','$page?go-shield-server-status=yes');
	    LoadAjaxSilent('go-shield-server-form-server','$page?go-shield-server-form-server=yes');    
	</script>
	";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function restart_js():string{
    $page=CurrentPageName();
    $tpl=new template_admin();

    return $tpl->framework_buildjs("/goshield/restarthup",
        "go.shield.server.progress",
        "go.shield.server.log",
        "progress-go-shield-server-restart",
        "Loadjs('$page?refresh-section=yes')");
}
function restart_js_form():string{
    $page=CurrentPageName();
    $tpl=new template_admin();

    return $tpl->framework_buildjs("/goshield/restarthup",
        "go.shield.server.progress",
        "go.shield.server.log",
        "restart-goserver-form",
        "Loadjs('$page?refresh-section=yes')");
}
function emergency_enable():bool{
    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KSRNEmergency", 1);
    admin_tracks("The Shields Emergency method was enabled");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ksrn.php?emergency=yes");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('go-shield-server-status','$page?go-shield-server-status=yes');";
    return true;
}
function emergency_disable(){
    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KSRNEmergency", 0);
    admin_tracks("The Shields Emergency method was Disable");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ksrn.php?emergency-disable=yes");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('go-shield-server-status','$page?go-shield-server-status=yes');";

}
function top_buttons():string{
    $page=CurrentPageName();
    $jscache="Loadjs('$page?clean-cache=yes')";
    $tpl=new template_admin();

    $dblen=$GLOBALS["CLASS_SOCKETS"]->GET_GOSHIELD_CACHES_ENTRIES();

    $btns=array();
    if($dblen>0) {
        $dblen=$tpl->FormatNumber($dblen);
        $btns[] = "<div class=\"btn-group\" data-toggle=\"buttons\">";
        $btns[] = "<label class=\"btn btn btn-warning\" OnClick=\"$jscache;\">
	    <i class='fa fa-trash'></i> {empty_cache} - $dblen {records}</label>";
        $btns[] = "</div>";
    }
    return @implode("",$btns);
}

function jstiny(){
    $Go_Shield_Server_Addr = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Addr");
    $Go_Shield_Server_Port = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Port"));
    if ($Go_Shield_Server_Addr==null){$Go_Shield_Server_Addr="127.0.0.1";}
    if($Go_Shield_Server_Port==0){$Go_Shield_Server_Port=3333;}

    $Go_Shield_Server_Version = "0.0.0.0";
    $cURLConnection = curl_init();

    curl_setopt($cURLConnection, CURLOPT_URL, "http://$Go_Shield_Server_Addr:$Go_Shield_Server_Port/get-version");
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);

    $resp = curl_exec($cURLConnection);
    curl_close($cURLConnection);
    $jsonArrayResponse = json_decode($resp,true);
    if(isset($jsonArrayResponse["version"])){$Go_Shield_Server_Version = $jsonArrayResponse["version"];}

    $TINY_ARRAY["TITLE"]="{KSRN_SERVER2} v$Go_Shield_Server_Version";
    $TINY_ARRAY["ICO"]="fad fa-compress-arrows-alt";
    $TINY_ARRAY["EXPL"]="{GO_SERVER_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=top_buttons();
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}

function local_databases_status():bool{
    $KSRN_PATTERNS=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRN_PATTERNS"));
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $max=159;
    $current=$KSRN_PATTERNS[0];
    $percent=$current/$max;
    $percent=round($percent*100);
    $size=FormatBytes($KSRN_PATTERNS[1]/1024);
    if($percent>=100){
        $btn=null;
        echo $tpl->widget_vert("{databases} 100%",$size,$btn);
        return true;
    }
    if($percent>=70){
        $btn=null;
        echo $tpl->widget_jaune("{databases} {$percent}%",$size,$btn);
        return true;
    }
    if($percent>=0){
        $btn=null;
        echo $tpl->widget_rouge("{databases} {$percent}%",$size,$btn);
        return true;
    }
    return false;
}

function status():bool{
    $tpl            = new template_admin();
    $jsRestart      = restart_js();
    $page=CurrentPageName();
    $stats="";
    $Go_Shield_Server_Enable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));
    $Go_Shield_Server_Addr = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Addr");
    $Go_Shield_Server_Port = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Port"));
    $Go_Shield_Server_DB_Size=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_DB_Size"));
    if($Go_Shield_Server_DB_Size==0){$Go_Shield_Server_DB_Size=2048;}
    if ($Go_Shield_Server_Addr==null){$Go_Shield_Server_Addr="127.0.0.1";}
    if($Go_Shield_Server_Port==0){$Go_Shield_Server_Port=3333;}

    $Go_Shield_Server_Use_Local_Categories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsUseLocalCats"));
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ksrn.php?go-shield-status=yes");




    if($Go_Shield_Server_Enable==1) {
        $cURLConnection = curl_init();

        curl_setopt($cURLConnection, CURLOPT_URL, "http://$Go_Shield_Server_Addr:$Go_Shield_Server_Port/db/len");
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);

        $resp = curl_exec($cURLConnection);
        curl_close($cURLConnection);
        $dblen = json_decode($resp,true);


        $cURLConnection = curl_init();
        curl_setopt($cURLConnection, CURLOPT_URL, "http://$Go_Shield_Server_Addr:$Go_Shield_Server_Port/db/stats");
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);

        $resp = curl_exec($cURLConnection);
        curl_close($cURLConnection);
        $dbstats = json_decode($resp,true);

        $request = intval($dbstats["hits"])+intval($dbstats["misses"]);


        $cURLConnection = curl_init();

        curl_setopt($cURLConnection, CURLOPT_URL, "http://$Go_Shield_Server_Addr:$Go_Shield_Server_Port/db/capacity");
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);

        $resp = curl_exec($cURLConnection);
        curl_close($cURLConnection);
        $dbcapacity = json_decode($resp,true);


        $GO_SHIELD_SERVER_CACHE = $tpl->FormatNumber($dblen);
        $QUERIES = $tpl->FormatNumber($request);
        $HITS = $tpl->FormatNumber(intval($dbstats["hits"]));
        $MISSES = $tpl->FormatNumber(intval($dbstats["misses"]));
        $dbcapacity = _formatBytes($dbcapacity);
        $dbsize = _formatBytes($Go_Shield_Server_DB_Size * 1048576);

        $prc = ($QUERIES > 0 ? $HITS / $QUERIES : 0.0);
        $prc = round($prc * 100, 2);
        if ($prc > 99) {
            $prc = 100;
        }

        $stats = "
                    <div class=\"ibox-content\">
                    <h1>Cache Ratio $prc%</h1>    
                    <span class=\"label label-success \">$QUERIES {requests}</span>
                    <span class=\"label label-success \">Hits $HITS</span>
                    <span class=\"label label-warning \">Misses: $MISSES</span>
                    <span class=\"label label-info \">$GO_SHIELD_SERVER_CACHE {items}</span>
                    <hr></hr> 
                    <h5>Cache Used: $dbcapacity of $dbsize</h5>
                    </div>
                  
                    
                    ";
    }

    echo "<div id='go-shield-server-status-left'></div>";

    // $refresh="LoadAjaxSilent('go-shield-server-status','$page?go-shield-server-status=yes');";

    $go_shield_server_src=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GO_SHIELD_SERVER_SRC"));
    $go_shield_server_dst=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GO_SHIELD_SERVER_DST"));

    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($Go_Shield_Server_Use_Local_Categories==1){ local_databases_status();}

    if($SQUIDEnable==1) {
        if ($go_shield_server_src <> $go_shield_server_dst) {
            $btn[0]["name"] = "{fix_it}";
            $btn[0]["icon"] = ico_play;
            $btn[0]["js"] = $jsRestart;
            echo $tpl->widget_jaune("{need_update_ksrn}", "{update2}", $btn);
        }
    }



    $download_logs= $tpl->button_autnonome("{logfile}", "Loadjs('$page?logfile-js=yes')", "fas fa-eye","AsProxyMonitor","335");

    $stats=$tpl->_ENGINE_parse_body($stats);
    $html[]="<div style='margin-top:10px'>$stats</div>";
    $html[]="<script>";
    $html[]=jstiny();
    $html[]="LoadAjaxSilent('all-go-shield-server-versions','fw.ksrn.client.php?all-go-shield-server-versions=yes');";
    $html[]="LoadAjaxSilent('go-shield-server-top-status','$page?go-shield-server-top-status=yes');";
    $html[]="LoadAjax('go-shield-server-status-left','$page?go-shield-server-status-left=yes');";
    $html[]="function checkAndExecuteGoShieldStatus() {";
    $html[]="\tvar element = document.getElementById('go-shield-server-status-left');";
    $html[]="\tif (element) {";
    $html[]="\t\tLoadAjaxSilent('go-shield-server-status-left','$page?go-shield-server-status-left=yes');";
    $html[]="\treturn;";
    $html[]="\t}";
    $html[]="\tclearInterval(GoShieldIntervalID);";
    $html[]="}";
    $html[]="var GoShieldIntervalID = setInterval(checkAndExecuteGoShieldStatus, 3000);";
    $html[]="</script>";
    echo @implode("\n",$html);
    return true;

}
function go_shield_status_left():bool{
    $tpl            = new template_admin();
    $jsRestart      = restart_js();
    $page=CurrentPageName();

    $sock=new sockets();
    $data=$sock->REST_API("/goshield/status");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>$sock->mysql_error","{error}"));

    }else {
        if (!$json->Status) {
            echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>$sock->mysql_error", "{error}"));
            return true;
        }
    }
    if($json->Status) {
        $ini = new Bs_IniHandler();
        $ini->loadString($json->Info);
        echo  $tpl->_ENGINE_parse_body($tpl->SERVICE_STATUS($ini, "APP_GO_SHIELD_SERVER", $jsRestart));
    }

    return true;
}

function _formatBytes($size, $precision = 2)
{
    $base = log($size, 1024);
    $suffixes = array('', 'Kb', 'Mb', 'Gb', 'Tb');

    return round(pow(1024, $base - floor($base)), $precision) .''. $suffixes[floor($base)];
}

function go_shield_server_top_status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $Go_Shield_Server_Enable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));
    if($Go_Shield_Server_Enable==0){return null;}
//<i class="fa-solid fa-newspaper"></i>
    $EnableNRDS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNRDS"));



    $button_help_nrds["ico"]="fa-solid fa-headset";
    $button_help_nrds["name"] = "{online_help}";
    $button_help_nrds["js"] = "s_PopUp('https://wiki.articatech.com/en/filtering-service/newly-registered-domains','1024','800')";



    $button["ico"]="fa-solid fa-headset";
    $button["name"] = "{online_help}";
    $button["js"] = "s_PopUp('https://wiki.articatech.com/en/filtering-service','1024','800')";

    $about=$tpl->widget_h("blue","fa-solid fa-square-info","Wiki","{online_help}: {KSRN_SERVER2}",$button);



    if($EnableNRDS==0){
        $jsbut=$tpl->framework_buildjs(
            "/nrds/enable",
            "nrds.progress",
            "nrds.logs",
            "go-shield-server-top-progress",
            "LoadAjaxSilent('go-shield-server-top-status','$page?go-shield-server-top-status=yes');"
        );
        $button=array();
        $button["ico"]="fa-solid fa-shield-check";
        $button["name"] = "{enable}";
        $button["js"] = $jsbut;

        $NRDS=$tpl->widget_h("grey","fa-solid fa-newspaper","{disabled}","{category}: {nrds_cat}",$button,$button_help_nrds);

    }


    if($EnableNRDS==1){
        $NRDSData=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NRDSData"));
        $jsbut=$tpl->framework_buildjs(
            "/nrds/disable",
            "nrds.progress",
            "nrds.logs",
            "go-shield-server-top-progress",
            "LoadAjaxSilent('go-shield-server-top-status','$page?go-shield-server-top-status=yes');"
        );
        $button=array();
        $button["ico"]="fa-solid fa-shield-slash";
        $button["name"] = "{disable}";
        $button["js"] = $jsbut;
        $Elements=intval($NRDSData["ROWS"]);

        if($Elements>0) {
            $Elementsx=$tpl->FormatNumber($Elements);

            if($Elements>0){

                $button_help_nrds["ico"]=ico_list;
                $button_help_nrds["name"] = "{records}";
                $button_help_nrds["js"] = "Loadjs('$page?nrds-js=yes')";
            }


            $NRDS = $tpl->widget_h("green", "fa-solid fa-newspaper", $Elementsx, "{category}: {nrds_cat} {records}", $button,$button_help_nrds);
        }else{
            $NRDS = $tpl->widget_h("yellow", "fa-solid fa-newspaper", "{no_data}", "{category}: {nrds_cat}", $button,$button_help_nrds);
        }


    }




    $html[]="<table style='width:100%;margin-left:10px'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top'>";
    $html[]=$NRDS;
    $html[]="</td>";
    $html[]="<td style='vertical-align:top;padding-left:5px'>";
    $html[]=$about;
    $html[]="</td>";


    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<div id='go-shield-server-top-progress' style='margin-bottom:10px;margin-top:10px;margin-left:10px'></div>";
    echo $tpl->_ENGINE_parse_body($html);


}


function Save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();


    if(intval($_POST["GoogleSafeEnable"])==0){
        $_POST["GoogleSafeEnable"]=1;

    }else{
        $_POST["GoogleSafeEnable"]=0;
    }

    if(intval($_POST["CloudFlareSafeEnabgle"])==0){
        $_POST["CloudFlareSafeEnabgle"]=1;
    }else{
        $_POST["CloudFlareSafeEnabgle"]=0;
    }

    if(intval($_POST["KsrnEnableAdverstising"])==0){
        $_POST["KsrnEnableAdverstising"]=1;
    }else{
        $_POST["KsrnEnableAdverstising"]=0;
    }
    $tpl->SAVE_POSTs();
    $sock=new sockets();
    $sock->REST_API("/goshield/reconfigure");

}

function cguard_settings():bool{
    $TheShieldsCguard       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsCguard"));
    $tpl=new template_admin();
    $form[] = $tpl->field_checkbox("TheShieldsCguard", "{TheShieldsCguard}", $TheShieldsCguard, false);

    $priv           = "AsSystemAdministrator";
    $jsRestart      = restart_js_form();
    $myform         = $tpl->form_outside(null, $form,"{about_cguard}","{apply}",$jsRestart,$priv);
    $html[]="<div id='restart-goserver-form'></div>";
    $html[]=$tpl->_ENGINE_parse_body($myform);
    echo @implode("\n",$html);
    return true;
}

function cguard_settings_save():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("TheShieldsCguard",$_POST["TheShieldsCguard"]);
    return true;
}

function the_shields_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $KSRNEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNEnable"));

    if($KSRNEnable==0){
        echo $tpl->FATAL_ERROR_SHOW_128("{ksrn_error_disabled}");
        return true;
    }

    $priv           = "AsSystemAdministrator";
    $KsrnPornEnable         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnPornEnable"));
    $KsrnMixedAdultEnable   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnMixedAdultEnable"));
    $KsrnHatredEnable       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnHatredEnable"));
    $KsrnDisableAdverstising= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnDisableAdverstising"));
    $KsrnDisableGoogleAdServices=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnDisableGoogleAdServices"));

    if($KsrnDisableAdverstising==0){
        $KsrnEnableAdverstising=1;
    }else{
        $KsrnEnableAdverstising=0;
    }

    $form[]         = $tpl->field_section("Threats Shield","{threats_shield_explain}");
    $form[]         = $tpl->field_checkbox("KsrnAllEnable","{KsrnAllEnable}",1,false,"{KsrnAllEnable}",true);

    $form[]         = $tpl->field_section("Privacy Shield","{privacy_shield_explain}");
    $form[]         = $tpl->field_checkbox("KsrnDisableGoogleAdServices","{allow}: Google Ad Services",$KsrnDisableGoogleAdServices);
    $form[]         = $tpl->field_checkbox("KsrnEnableAdverstising","{KsrnEnableAdverstising}",$KsrnEnableAdverstising);

    $form[]         = $tpl->field_section("Inappropriate Shield","{inappropriate_shield_explain}");
    $form[]         = $tpl->field_checkbox("KsrnPornEnable","{KsrnPornEnable}",$KsrnPornEnable);
    $form[]         = $tpl->field_checkbox("KsrnMixedAdultEnable","{KsrnMixedAdultEnable}",$KsrnMixedAdultEnable);
    $form[]         = $tpl->field_checkbox("KsrnHatredEnable","{KsrnHatredEnable}",$KsrnHatredEnable);

    $jsRestart = restart_js_form();
    $html[]="<div id='restart-goserver-form'></div>";
    $myform         = $tpl->form_outside(null, $form,"","{apply}",$jsRestart,$priv);
    $html[]=$tpl->_ENGINE_parse_body($myform);
    echo @implode("\n",$html);
    return true;
}

function the_shields_settings($tpl){

    $page=CurrentPageName();
    $KSRNEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNEnable"));
    $license_text=null;

    $KsrnPornEnable         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnPornEnable"));
    $KsrnMixedAdultEnable   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnMixedAdultEnable"));
    $KsrnHatredEnable       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnHatredEnable"));
    $KsrnDisableAdverstising= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnDisableAdverstising"));
    $TheShieldsCguard       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsCguard"));
    $KsrnDisableGoogleAdServices=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KsrnDisableGoogleAdServices"));

    if($KsrnDisableAdverstising==0){
        $KsrnEnableAdverstising=1;
    }else{
        $KsrnEnableAdverstising=0;
    }
    $text1=array();
    $tpl->table_form_field_js("Loadjs('$page?section-cguard-js=yes')");
    $tpl->table_form_field_bool("{TheShieldsCguard}",$TheShieldsCguard,ico_servcloud);



    if($KSRNEnable==1) {
        $tpl->table_form_field_js("Loadjs('fw.ksrn.license.php?js=yes');");
        $tpl->table_form_field_text("{license}",the_shields_license(),ico_certificate);
    }

    $tpl->table_form_field_js("Loadjs('$page?section-shields-js=yes')");
    if($license_text<>null){
        $tpl->table_form_field_text("Threats Shield","{KsrnAllEnable} $license_text",ico_shield);
    }else{
        if($KSRNEnable==1) {
            $tpl->table_form_field_text("Threats Shield", "{KsrnAllEnable}", ico_shield);
        }else{
            $tpl->table_form_field_bool("Threats Shield", 0, ico_shield);
        }
    }


    if($KsrnEnableAdverstising==1) {
        $text1[] = "{KsrnEnableAdverstising}";
    }
    if($KsrnDisableGoogleAdServices==1){
        $text1[] = "{allow}: Google Ad Services";
    }
    if($license_text<>null){$text1[]=$license_text;}
    if($KSRNEnable==0) {
        unset($text1);
        $tpl->table_form_field_bool("Privacy Shield",0,ico_shield);
    }else{
        $tpl->table_form_field_text("Privacy Shield",@implode(" ",$text1),ico_shield);
    }



    $text2=array();
    if($KsrnPornEnable==1) {
        $text2[] = "{KsrnPornEnable}";
    }
    if($KsrnMixedAdultEnable==1) {
        $text2[] = "{KsrnMixedAdultEnable}";
    }
    if($KsrnHatredEnable==1) {
        $text2[] = "{KsrnHatredEnable}";
    }
    if(count($text2)==0){
        $text2[]="{disabled}";
    }
    if($license_text<>null){$text2[]=$license_text;}

    if($KSRNEnable==0) {
        unset($text2);
        $tpl->table_form_field_bool("Inappropriate Shield",0,ico_shield);
    }else{
        $tpl->table_form_field_text("Inappropriate Shield",@implode(" ",$text2),ico_shield);
    }



    return $tpl;
}
function the_shields_license():string{

    if($GLOBALS["VERBOSE"]){VERBOSE(__FUNCTION__,__LINE__);}
    $tpl            = new template_admin();
    $kInfos            =unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kInfos"));
    if(!isset($kInfos["enable"])){$kInfos["enable"]=0;}
    if(!isset($kInfos["status"])){$kInfos["status"]=null;}

    if($kInfos["enable"]==0 && $kInfos["status"]==null){
        return "{license_invalid}";
    }
    if($kInfos["enable"]==0){
        return $kInfos["status"];
    }

    if($kInfos["enable"]==1){
        if(!isset($kInfos["ispaid"])){$kInfos["ispaid"]=0;}
        if(intval($kInfos["expire"])>0){
            VERBOSE("Expire in {$kInfos["expire"]}",__LINE__);
            $reste_days=$tpl->TimeToDays($kInfos["expire"]);
            if($reste_days<15){
                return "{trial_period}, {expire_in}: $reste_days {days}";
            }
            return "{$kInfos["status"]} {expire_in}: $reste_days {days}";


        }

        if(intval($kInfos["expire"])==0){
            if($kInfos["status"]=="{gold_license}"){
                return "{$kInfos["status"]} {expire_in}: {never}";
            }
        }
    }
    return "";

}
