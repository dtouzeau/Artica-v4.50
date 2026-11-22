<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/prefetch-info.inc"); __prefetchCommonSettings($GLOBALS["CLASS_SOCKETS"]);

if(isset($_GET["after-reboot-js"])){after_reboot_js();exit;}
if(isset($_GET["after-reboot-popup"])){after_reboot_popup();exit;}
if(isset($_POST["ReloadProxyAfterReboot"])){save_silent();exit;}

if(isset($_GET["url-checking-js"])){url_checking_js();exit;}
if(isset($_GET["url-checking-popup"])){url_checking_popup();exit;}
if(isset($_POST["UrlNetAnalyze"])){save_silent();exit;}

if(isset($_GET["monit-js"])){monit_js();exit;}
if(isset($_GET["monit-popup"])){monit_popup();exit;}
if(isset($_POST["watchdog"])){monit_save();exit;}
if(isset($_GET["monit-memory-js"])){monit_memory_js();exit;}
if(isset($_GET["monit-memory-popup"])){monit_memory_popup();exit;}
if(isset($_POST["MonitSquidMaxMemoryReload"])){monit_save();exit;}

if(isset($_GET["flat"])){flat_page();exit;}

page();

function page():bool{
    $page=CurrentPageName();
echo "<div id='proxy-status-watchdog'></div>";
echo "<script>LoadAjaxSilent('proxy-status-watchdog','$page?flat=yes');</script>";
return true;
}

function after_reboot_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog4("{after_reboot}","$page?after-reboot-popup=yes");
    return true;
}
function url_checking_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog4("URLs Checking","$page?url-checking-popup=yes");
    return true;
}
function monit_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog4("{monitor}","$page?monit-popup=yes");
    return true;
}
function monit_memory_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog4("{monitor} {reload_service}","$page?monit-memory-popup=yes");
    return true;
}


function save_silent():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    // URL checking / network analysis crons are applied by articarest.
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/watchdog/monit");
    return admin_tracks_post("Save Proxy Watchdog configuration");

}
function after_reboot_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ReloadProxyAfterReboot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ReloadProxyAfterReboot"));
    $RestoreSnapshotProxyAfterReboot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RestoreSnapshotProxyAfterReboot"));

    $jsrestart[]="LoadAjaxSilent('proxy-status-watchdog','$page?flat=yes');";
    $jsrestart[]="dialogInstance4.close();";
    $jsrestartjs=implode(";",$jsrestart);

    $form[]=$tpl->field_checkbox("ReloadProxyAfterReboot","span:{reconfigure_proxy_services}",$ReloadProxyAfterReboot,false,"url:https://wiki.articatech.com/en/proxy-service/troubleshooting/reconfigure-reboot;{ReloadProxyAfterReboot}");
    $form[]=$tpl->field_checkbox("RestoreSnapshotProxyAfterReboot","{RestoreSnapshotProxyAfterReboot}",$RestoreSnapshotProxyAfterReboot,false,"{RestoreSnapshotProxyAfterReboot_explain}");
    echo $tpl->form_outside(null,$form,null,"{apply}",$jsrestartjs,"AsSquidAdministrator");
    return true;
}

function url_checking_ports():array{
    $PortDirectionS=array();
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    $HaClusterProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProxyPort"));
    $ql=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $sql="SELECT * FROM proxy_ports WHERE enabled=1";
    $resultsPorts = $ql->QUERY_SQL($sql);
    foreach ($resultsPorts as $index=>$lignePorts){
        $eth=$lignePorts["nic"];
        $port=$lignePorts["port"];
        $IDPort=$lignePorts["ID"];
        if($HaClusterClient==1){
            if($port==$HaClusterProxyPort){continue;}
        }
        $PortDirectionS[$IDPort]="{port} $port [$eth]";
    }

    return $PortDirectionS;
}
function url_checking_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $internetwatchuri=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("internetwatchuri"));
    $UrlNetAnalyze=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlNetAnalyze"));
    if($internetwatchuri==null){$internetwatchuri="https://www.google.com";}

    $UrlCheckingEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlCheckingEnable"));
    $UrlCheckingAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlCheckingAddress"));
    $UrlCheckingAction=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlCheckingAction"));
    $UrlCheckingProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlCheckingProxyPort"));
    $UrlCheckingInterval=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlCheckingInterval"));
    if($UrlCheckingAction==0){$UrlCheckingAction=3;}
    if($UrlCheckingAddress==null){$UrlCheckingAddress="https://www.google.com";}

    $zUrlCheckingAction[0]="{do_nothing}";
    $zUrlCheckingAction[1]="{APP_SQUID}: {reconfigure}";
    $zUrlCheckingAction[2]="{APP_SQUID}: {restart}";
    $zUrlCheckingAction[3]="{APP_SQUID}: {reload}";

    $timeout[0]="1mn";
    $timeout[5]="5mn";
    $timeout[10]="10mn";
    $timeout[15]="15mn";
    $timeout[30]="30mn";


    $jsrestart[]="LoadAjaxSilent('proxy-status-watchdog','$page?flat=yes');";
    $jsrestart[]="dialogInstance4.close();";
    $jsrestartjs=implode(";",$jsrestart);

    $form[]=$tpl->field_checkbox("UrlNetAnalyze","{UrlNetAnalyze}",$UrlNetAnalyze,"internetwatchuri","{UrlNetAnalyze_explain}");
    $form[]=$tpl->field_text("internetwatchuri","{url}",$internetwatchuri);

    $form[]=$tpl->field_checkbox("UrlCheckingEnable","{enable}",$UrlCheckingEnable,"UrlCheckingAddress,UrlCheckingAction,UrlCheckingProxyPort");
    $form[]=$tpl->field_text("UrlCheckingAddress","{url}",$UrlCheckingAddress);
    $form[]=$tpl->field_array_hash(url_checking_ports(),"UrlCheckingProxyPort","{listen_port}",$UrlCheckingProxyPort);
    $form[]=$tpl->field_array_hash($zUrlCheckingAction,"UrlCheckingAction","{then}",$UrlCheckingAction);
    $form[]=$tpl->field_array_hash($timeout,"UrlCheckingInterval","{interval}",$UrlCheckingInterval);
    echo $tpl->form_outside(null,$form,null,"{apply}",$jsrestartjs,"AsSquidAdministrator");
    return true;
}


function monit_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(isset($_POST["watchdog"])) {
        $MonitConfig = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchdogMonitConfig")));
        $MonitConfig["watchdog"] = intval($_POST["watchdog"]);
        $newMonit = base64_encode(serialize($MonitConfig));
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidWatchdogMonitConfig", $newMonit);
        unset($_POST["watchdog"]);
    }

    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/watchdog/monit");
    return admin_tracks_post("Save Proxy Watchdog configuration");
}
function monit_memory_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $MonitSquidMaxMemoryReload=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitSquidMaxMemoryReload"));
    $MonitSquidMaxMemoryCyclesReload=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitSquidMaxMemoryCyclesReload"));

    $CPU[0]="{disabled}";
    for($i=50;$i<101;$i++){ $CPU[$i]="$i%"; }
    $CPU[110]="{disabled}";
    $MINUTES[1]="{during} 1 {minute}";
    for($i=2;$i<121;$i++){
        $MINUTES[$i]="{during} $i {minutes}";
    }
    $jsrestart[]="LoadAjaxSilent('proxy-status-watchdog','$page?flat=yes');";
    $jsrestart[]="dialogInstance4.close();";
    $jsrestartjs=implode(";",$jsrestart);

    $form[]=$tpl->field_array_hash($CPU,"MonitSquidMaxMemoryReload","{if_system_memory_exceed}",$MonitSquidMaxMemoryReload);
    $form[]=$tpl->field_array_hash($MINUTES,"MonitSquidMaxMemoryCyclesReload","&nbsp;",$MonitSquidMaxMemoryCyclesReload);
    echo $tpl->form_outside(null,$form,null,"{apply}",$jsrestartjs,"AsSquidAdministrator");
    return true;
}
function monit_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $MonitConfig=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchdogMonitConfig")));
    $MonitSquidMaxCPU=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitSquidMaxCPU"));
    $MonitSquidMaxCPUCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitSquidMaxCPUCycles"));
    if($MonitSquidMaxCPUCycles==0){$MonitSquidMaxCPUCycles=5;}
    $MonitSquidMaxMemory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitSquidMaxMemory"));
    $MonitSquidMaxMemoryCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitSquidMaxMemoryCycles"));

    $CPU[0]="{disabled}";
    for($i=50;$i<101;$i++){ $CPU[$i]="$i%"; }
    $CPU[110]="{disabled}";
    $MINUTES[1]="{during} 1 {minute}";
    for($i=2;$i<121;$i++){
        $MINUTES[$i]="{during} $i {minutes}";
    }

    $jsrestart[]="LoadAjaxSilent('proxy-status-watchdog','$page?flat=yes');";
    $jsrestart[]="dialogInstance4.close();";
    $jsrestartjs=implode(";",$jsrestart);


    $form[]=$tpl->field_checkbox("watchdog","{enable}",$MonitConfig["watchdog"],"");
    $form[]=$tpl->field_array_hash($CPU,"MonitSquidMaxCPU","{if_system_cpu_exceed}",$MonitSquidMaxCPU);
    $form[]=$tpl->field_array_hash($MINUTES,"MonitSquidMaxCPUCycles","&nbsp;",$MonitSquidMaxCPUCycles);

    $form[]=$tpl->field_array_hash($CPU,"MonitSquidMaxMemory","{if_system_memory_exceed}",$MonitSquidMaxMemory);
    $form[]=$tpl->field_array_hash($MINUTES,"MonitSquidMaxMemoryCycles","&nbsp;",$MonitSquidMaxMemoryCycles);
    echo $tpl->form_outside(null,$form,null,"{apply}",$jsrestartjs,"AsSquidAdministrator");
    return true;
}


function flat_page(){

    $tpl=new template_admin();
    $page=CurrentPageName();

    $MonitConfig=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchdogMonitConfig")));


    $ReloadProxyAfterReboot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ReloadProxyAfterReboot"));
    $RestoreSnapshotProxyAfterReboot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RestoreSnapshotProxyAfterReboot"));

    $zUrlCheckingAction[0]="{do_nothing}";
    $zUrlCheckingAction[1]="{APP_SQUID}: {reconfigure}";
    $zUrlCheckingAction[2]="{APP_SQUID}: {restart}";
    $zUrlCheckingAction[3]="{APP_SQUID}: {reload}";

    $timeout[0]="1mn";
    $timeout[5]="5mn";
    $timeout[10]="10mn";
    $timeout[15]="15mn";
    $timeout[30]="30mn";

    if(!isset($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
    if(!is_numeric($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
    $LogsRotateDefaultSizeRotation=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsRotateDefaultSizeRotation"));
    if($LogsRotateDefaultSizeRotation<5){$LogsRotateDefaultSizeRotation=100;}
    $UrlNetAnalyze=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlNetAnalyze"));



    $after_reboot=array();
    if($ReloadProxyAfterReboot==1){
        $after_reboot[]="{reconfigure_proxy_services}";
    }
    if($RestoreSnapshotProxyAfterReboot==1){
        $after_reboot[]="{RestoreSnapshotProxyAfterReboot}";
    }
    $tpl->table_form_field_js("Loadjs('$page?after-reboot-js=yes')","AsSquidAdministrator");
    if(count($after_reboot)==0){
        $tpl->table_form_field_bool("{after_reboot}",0,ico_refresh);
    }else{
        $tpl->table_form_field_text("{after_reboot}",@implode(",",$after_reboot),ico_refresh);
    }

    $tpl->table_form_field_js("Loadjs('$page?url-checking-js=yes')","AsSquidAdministrator");

    if($UrlNetAnalyze==0){
        $tpl->table_form_field_bool("{UrlNetAnalyze}",0,ico_link);
    }else{
        $internetwatchuri=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("internetwatchuri"));
        if($internetwatchuri==null){$internetwatchuri="https://www.google.com";}
        $tpl->table_form_field_text("{UrlNetAnalyze}",$internetwatchuri,ico_link);
    }

    $UrlCheckingEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlCheckingEnable"));
    if($UrlCheckingEnable==0){
        $tpl->table_form_field_bool("URL Checking",0,ico_link);
    }else{
        $UrlCheckingAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlCheckingAddress"));
        $UrlCheckingAction=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlCheckingAction"));
        $UrlCheckingProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlCheckingProxyPort"));
        $UrlCheckingInterval=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlCheckingInterval"));
        if($UrlCheckingAction==0){$UrlCheckingAction=3;}
        if($UrlCheckingAddress==null){$UrlCheckingAddress="https://www.google.com";}
        $tpl->table_form_field_text("URL Checking","<small>$UrlCheckingAddress {listen_port} $UrlCheckingProxyPort {interval} $timeout[$UrlCheckingInterval] {then} $zUrlCheckingAction[$UrlCheckingAction]</small>",ico_watchdog);
    }

    $MINUTES[1]="{during} 1 {minute}";
    for($i=2;$i<121;$i++){
        $MINUTES[$i]="{during} $i {minutes}";
    }
    $CPU[0]="{disabled}";
    for($i=50;$i<101;$i++){ $CPU[$i]="$i%"; }
    $CPU[110]="{disabled}";


    $tpl->table_form_field_js("Loadjs('$page?monit-js=yes')","AsSquidAdministrator");
    if($MonitConfig["watchdog"]==0){
        $tpl->table_form_field_bool("{restart_service} {if_system_cpu_exceed}",0,ico_refresh);
        $tpl->table_form_field_bool("{restart_service} {if_system_memory_exceed}",0,ico_refresh);
    }else{
        $MonitSquidMaxMemory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitSquidMaxMemory"));
        $MonitSquidMaxMemoryCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitSquidMaxMemoryCycles"));
        $MonitSquidMaxCPU=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitSquidMaxCPU"));
        $MonitSquidMaxCPUCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitSquidMaxCPUCycles"));
        if($MonitSquidMaxCPUCycles==0){$MonitSquidMaxCPUCycles=5;}


        if($MonitSquidMaxCPU==0 OR $MonitSquidMaxCPU==110){
            $tpl->table_form_field_bool("{restart_service} {if_system_cpu_exceed}",0,ico_refresh);
        }else{
            $tpl->table_form_field_text("{restart_service} {if_system_cpu_exceed}","$CPU[$MonitSquidMaxCPU] $MINUTES[$MonitSquidMaxCPUCycles]",ico_refresh);
        }
        if($MonitSquidMaxMemory==0 OR $MonitSquidMaxMemory==110){
            $tpl->table_form_field_bool("{restart_service} {if_system_memory_exceed}",0,ico_refresh);
        }else{
            $tpl->table_form_field_text("{restart_service}","{if_system_memory_exceed} $CPU[$MonitSquidMaxMemory] $MINUTES[$MonitSquidMaxMemoryCycles]",ico_refresh);
        }
    }

    $MonitSquidMaxMemoryReload=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitSquidMaxMemoryReload"));
    $MonitSquidMaxMemoryCyclesReload=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitSquidMaxMemoryCyclesReload"));
    $tpl->table_form_field_js("Loadjs('$page?monit-memory-js=yes')","AsSquidAdministrator");
    if($MonitSquidMaxMemoryReload==0 OR $MonitSquidMaxMemoryReload==110){
        $tpl->table_form_field_bool("{reload_service} {if_system_memory_exceed}",0,ico_refresh);
    }else{
        $tpl->table_form_field_text("{reload_service}","{if_system_memory_exceed} $CPU[$MonitSquidMaxMemoryReload] $MINUTES[$MonitSquidMaxMemoryCyclesReload]",ico_refresh);
    }




    $html[]=$tpl->table_form_compile();
    $html[]="<script>";
    $TINY_ARRAY["TITLE"]="{watchdog}";
    $TINY_ARRAY["ICO"]="fa-solid fa-shield-dog";
    $TINY_ARRAY["EXPL"]="{proxy_service_about}";
    $TINY_ARRAY["URL"]="proxy-status";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]=$jstiny;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
